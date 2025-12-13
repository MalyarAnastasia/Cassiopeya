use async_trait::async_trait;
use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::{PgPool, Row};

use crate::domain::{ApiError, IssRecord};

#[async_trait]
pub trait IssRepository: Send + Sync {
    async fn get_last(&self) -> Result<Option<IssRecord>, ApiError>;
    async fn insert(&self, source_url: &str, payload: Value) -> Result<(), ApiError>;
    async fn get_trend_points(&self, limit: i64) -> Result<Vec<TrendPoint>, ApiError>;
}

#[derive(Debug, Clone)]
pub struct TrendPoint {
    pub fetched_at: DateTime<Utc>,
    pub payload: Value,
}

pub struct IssRepo {
    pool: PgPool,
}

impl IssRepo {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }
}

#[async_trait]
impl IssRepository for IssRepo {
    async fn get_last(&self) -> Result<Option<IssRecord>, ApiError> {
        let row = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             ORDER BY id DESC LIMIT 1"
        )
        .fetch_optional(&self.pool)
        .await?;

        Ok(row.map(|r| IssRecord {
            id: r.get("id"),
            fetched_at: r.get::<DateTime<Utc>, _>("fetched_at"),
            source_url: r.get("source_url"),
            payload: r.try_get("payload").unwrap_or(serde_json::json!({})),
        }))
    }

    async fn insert(&self, source_url: &str, payload: Value) -> Result<(), ApiError> {
        sqlx::query("INSERT INTO iss_fetch_log (source_url, payload) VALUES ($1, $2)")
            .bind(source_url)
            .bind(payload)
            .execute(&self.pool)
            .await?;
        Ok(())
    }

    async fn get_trend_points(&self, limit: i64) -> Result<Vec<TrendPoint>, ApiError> {
        let rows = sqlx::query(
            "SELECT fetched_at, payload FROM iss_fetch_log ORDER BY id DESC LIMIT $1"
        )
        .bind(limit)
        .fetch_all(&self.pool)
        .await?;

        Ok(rows
            .into_iter()
            .map(|r| TrendPoint {
                fetched_at: r.get::<DateTime<Utc>, _>("fetched_at"),
                payload: r.get("payload"),
            })
            .collect())
    }
}


