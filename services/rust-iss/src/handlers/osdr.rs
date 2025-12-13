use axum::extract::State;
use axum::Json;
use serde_json::Value;

use crate::domain::ApiError;
use crate::AppState;

pub async fn osdr_list(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let limit = std::env::var("OSDR_LIST_LIMIT")
        .ok()
        .and_then(|s| s.parse::<i64>().ok())
        .unwrap_or(20);

    let items = state.osdr_service.list(limit).await?;

    let out: Vec<Value> = items
        .into_iter()
        .map(|item| {
            serde_json::json!({
                "id": item.id,
                "dataset_id": item.dataset_id,
                "title": item.title,
                "status": item.status,
                "updated_at": item.updated_at,
                "inserted_at": item.inserted_at,
                "raw": item.raw,
            })
        })
        .collect();

    Ok(Json(serde_json::json!({ "items": out })))
}

pub async fn osdr_sync(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let written = state.osdr_service.sync().await?;
    Ok(Json(serde_json::json!({ "written": written })))
}

