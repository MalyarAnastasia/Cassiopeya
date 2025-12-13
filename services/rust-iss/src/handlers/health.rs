use axum::Json;
use chrono::Utc;

use crate::domain::Health;

pub async fn health() -> Json<Health> {
    Json(Health {
        status: "ok",
        now: Utc::now(),
    })
}



