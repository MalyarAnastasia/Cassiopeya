use async_trait::async_trait;
use sqlx::{PgPool, Row};

use crate::domain::{ApiError, OsdrItem};

#[async_trait]
pub trait OsdrRepository: Send + Sync {
    async fn list(&self, limit: i64) -> Result<Vec<OsdrItem>, ApiError>;
    async fn upsert(&self, item: &OsdrItem) -> Result<(), ApiError>;
}

pub struct OsdrRepo {
    pool: PgPool,
}

impl OsdrRepo {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }
}

#[async_trait]
impl OsdrRepository for OsdrRepo {
    async fn list(&self, limit: i64) -> Result<Vec<OsdrItem>, ApiError> {
        let rows = sqlx::query(
            "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
             FROM osdr_items
             ORDER BY inserted_at DESC
             LIMIT $1"
        )
        .bind(limit)
        .fetch_all(&self.pool)
        .await?;

        Ok(rows
            .into_iter()
            .map(|r| OsdrItem {
                id: r.get("id"),
                dataset_id: r.get("dataset_id"),
                title: r.get("title"),
                status: r.get("status"),
                updated_at: r.get("updated_at"),
                inserted_at: r.get("inserted_at"),
                raw: r.get("raw"),
            })
            .collect())
    }

    async fn upsert(&self, item: &OsdrItem) -> Result<(), ApiError> {
        if let Some(ref dataset_id) = item.dataset_id {
            // Upsert по бизнес-ключу dataset_id
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1, $2, $3, $4, $5)
                 ON CONFLICT (dataset_id) DO UPDATE
                 SET title = EXCLUDED.title,
                     status = EXCLUDED.status,
                     updated_at = EXCLUDED.updated_at,
                     raw = EXCLUDED.raw"
            )
            .bind(dataset_id)
            .bind(&item.title)
            .bind(&item.status)
            .bind(&item.updated_at)
            .bind(&item.raw)
            .execute(&self.pool)
            .await?;
        } else {
            // INSERT для записей без dataset_id
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1, $2, $3, $4, $5)"
            )
            .bind::<Option<String>>(None)
            .bind(&item.title)
            .bind(&item.status)
            .bind(&item.updated_at)
            .bind(&item.raw)
            .execute(&self.pool)
            .await?;
        }
        Ok(())
    }
}



