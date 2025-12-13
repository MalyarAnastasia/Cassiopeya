use std::sync::Arc;
use std::time::Duration;

use sqlx::{postgres::PgPoolOptions, PgPool};
use tracing::{error, info};
use tracing_subscriber::{EnvFilter, FmtSubscriber};

pub mod config;
pub mod domain;
pub mod repo;
pub mod clients;
pub mod services;
pub mod handlers;
pub mod routes;
pub mod app_state;

use config::Config;
use domain::ApiError;
use repo::{CacheRepo, IssRepo, OsdrRepo};
use clients::{HttpClient, IssClient, NasaClient, SpaceXClient};
use services::{IssService, OsdrService, SpaceService};
use app_state::AppState;

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(EnvFilter::from_default_env())
        .finish();
    let _ = tracing::subscriber::set_global_default(subscriber);

    let config = Arc::new(Config::from_env().map_err(|e| anyhow::anyhow!("Config error: {}", e))?);

    // Инициализация БД
    let pool = PgPoolOptions::new()
        .max_connections(5)
        .connect(&config.database_url)
        .await?;
    init_db(&pool).await?;

    // Инициализация Redis (опционально)
    let redis = match redis::Client::open(config.redis_url.as_str()) {
        Ok(client) => {
            info!("Redis connected");
            Some(Arc::new(client))
        }
        Err(e) => {
            let err_msg = format!("{}", e);
            error!("Redis connection failed: {}, continuing without cache", err_msg);
            None
        }
    };

    // Инициализация HTTP клиента
    let http_client = HttpClient::new(&config)?;

    // Инициализация клиентов API
    let iss_client = IssClient::new(http_client.clone());
    let nasa_client = NasaClient::new(http_client.clone());
    let spacex_client = SpaceXClient::new(http_client);

    // Инициализация репозиториев
    let iss_repo = IssRepo::new(pool.clone());
    let osdr_repo = OsdrRepo::new(pool.clone());
    let cache_repo = CacheRepo::new(pool.clone());

    // Инициализация сервисов
    let iss_service = Arc::new(IssService::new(iss_repo, iss_client));
    let osdr_service = Arc::new(OsdrService::new(
        osdr_repo,
        nasa_client.clone(),
        config.nasa_url.clone(),
        config.nasa_key.clone(),
    ));
    let space_service = Arc::new(SpaceService::new(
        cache_repo,
        nasa_client,
        spacex_client,
        config.nasa_key.clone(),
    ));

    let state = AppState {
        config: config.clone(),
        pool,
        redis,
        iss_service,
        osdr_service,
        space_service,
    };

    // Запуск фоновых задач с advisory locks
    start_background_tasks(state.clone());

    // Создание роутера
    let app = routes::create_router().with_state(state);

    let listener = tokio::net::TcpListener::bind(("0.0.0.0", 3000)).await?;
    info!("rust_iss listening on 0.0.0.0:3000");
    axum::serve(listener, app.into_make_service()).await?;
    Ok(())
}

async fn init_db(pool: &PgPool) -> anyhow::Result<()> {
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS iss_fetch_log(
            id BIGSERIAL PRIMARY KEY,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            source_url TEXT NOT NULL,
            payload JSONB NOT NULL
        )"
    )
    .execute(pool)
    .await?;

    sqlx::query(
        "CREATE TABLE IF NOT EXISTS osdr_items(
            id BIGSERIAL PRIMARY KEY,
            dataset_id TEXT,
            title TEXT,
            status TEXT,
            updated_at TIMESTAMPTZ,
            inserted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            raw JSONB NOT NULL
        )"
    )
    .execute(pool)
    .await?;

    sqlx::query(
        "CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
         ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL"
    )
    .execute(pool)
    .await?;

    sqlx::query(
        "CREATE TABLE IF NOT EXISTS space_cache(
            id BIGSERIAL PRIMARY KEY,
            source TEXT NOT NULL,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            payload JSONB NOT NULL
        )"
    )
    .execute(pool)
    .await?;

    sqlx::query(
        "CREATE INDEX IF NOT EXISTS ix_space_cache_source ON space_cache(source, fetched_at DESC)"
    )
    .execute(pool)
    .await?;

    Ok(())
}

fn start_background_tasks(state: AppState) {
    let config = state.config.clone();
    let intervals = config.fetch_intervals.clone();

    // OSDR с advisory lock
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = run_with_lock(&st.pool, "osdr_fetch", || async {
                    st.osdr_service.sync().await
                })
                .await
                {
                    error!("osdr err: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(intervals.osdr)).await;
            }
        });
    }

    // ISS с advisory lock
    {
        let st = state.clone();
        let url = config.where_iss_url.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = run_with_lock(&st.pool, "iss_fetch", || async {
                    st.iss_service.fetch_and_store(&url).await
                })
                .await
                {
                    error!("iss err: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(intervals.iss)).await;
            }
        });
    }

    // APOD
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = run_with_lock(&st.pool, "apod_fetch", || async {
                    st.space_service.refresh(&["apod"]).await.map(|_| ())
                })
                .await
                {
                    error!("apod err: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(intervals.apod)).await;
            }
        });
    }

    // NeoWs
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = run_with_lock(&st.pool, "neo_fetch", || async {
                    st.space_service.refresh(&["neo"]).await.map(|_| ())
                })
                .await
                {
                    error!("neo err: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(intervals.neo)).await;
            }
        });
    }

    // DONKI
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = run_with_lock(&st.pool, "donki_fetch", || async {
                    st.space_service.refresh(&["flr", "cme"]).await.map(|_| ())
                })
                .await
                {
                    error!("donki err: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(intervals.donki)).await;
            }
        });
    }

    // SpaceX
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = run_with_lock(&st.pool, "spacex_fetch", || async {
                    st.space_service.refresh(&["spacex"]).await.map(|_| ())
                })
                .await
                {
                    error!("spacex err: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(intervals.spacex)).await;
            }
        });
    }
}

// Advisory lock для предотвращения наложения задач
async fn run_with_lock<F, Fut, T>(pool: &PgPool, lock_name: &str, f: F) -> Result<T, ApiError>
where
    F: FnOnce() -> Fut,
    Fut: std::future::Future<Output = Result<T, ApiError>>,
{
    // Используем pg_advisory_lock для блокировки
    let lock_id = hash_lock_name(lock_name);
    
    // Пытаемся получить блокировку (неблокирующая попытка)
    let acquired: bool = sqlx::query_scalar(
        "SELECT pg_try_advisory_lock($1)"
    )
    .bind(lock_id as i64)
    .fetch_one(pool)
    .await?;

    if !acquired {
        // Блокировка уже занята другим процессом
        return Err(ApiError::Internal(format!("Lock {} is already held", lock_name)));
    }

    let result = f().await;

    // Освобождаем блокировку
    let _: bool = sqlx::query_scalar(
        "SELECT pg_advisory_unlock($1)"
    )
    .bind(lock_id as i64)
    .fetch_one(pool)
    .await?;

    result
}

fn hash_lock_name(name: &str) -> u32 {
    use std::collections::hash_map::DefaultHasher;
    use std::hash::{Hash, Hasher};
    let mut hasher = DefaultHasher::new();
    name.hash(&mut hasher);
    hasher.finish() as u32
}
