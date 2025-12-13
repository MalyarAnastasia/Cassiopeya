use async_trait::async_trait;
use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::{PgPool, Row};

use crate::domain::ApiError;

#[async_trait]
pub trait CacheRepository: Send + Sync {
    async fn get_latest(&self, source: &str) -> Result<Option<CacheEntry>, ApiError>;
    async fn insert(&self, source: &str, payload: Value) -> Result<(), ApiError>;
    async fn count_osdr(&self) -> Result<i64, ApiError>;
}

pub struct CacheEntry {
    pub fetched_at: DateTime<Utc>,
    pub payload: Value,
}

pub struct CacheRepo {
    pool: PgPool,
}

impl CacheRepo {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }
}

#[async_trait]
impl CacheRepository for CacheRepo {
    async fn get_latest(&self, source: &str) -> Result<Option<CacheEntry>, ApiError> {
        let row = sqlx::query(
            "SELECT fetched_at, payload FROM space_cache
             WHERE source = $1 ORDER BY id DESC LIMIT 1"
        )
        .bind(source)
        .fetch_optional(&self.pool)
        .await?;

        Ok(row.map(|r| CacheEntry {
            fetched_at: r.get::<DateTime<Utc>, _>("fetched_at"),
            payload: r.get("payload"),
        }))
    }

    async fn insert(&self, source: &str, payload: Value) -> Result<(), ApiError> {
        sqlx::query("INSERT INTO space_cache(source, payload) VALUES ($1, $2)")
            .bind(source)
            .bind(payload)
            .execute(&self.pool)
            .await?;
        Ok(())
    }

    async fn count_osdr(&self) -> Result<i64, ApiError> {
        let row = sqlx::query("SELECT count(*) AS c FROM osdr_items")
            .fetch_one(&self.pool)
            .await?;
        Ok(row.get::<i64, _>("c"))
    }
}



