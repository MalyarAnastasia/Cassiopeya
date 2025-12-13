use axum::{extract::State, Json};
use serde_json::Value;

use crate::domain::{ApiError, Trend};
use crate::AppState;

pub async fn last_iss(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    match state.iss_service.get_last().await? {
        Some(record) => Ok(Json(serde_json::json!({
            "id": record.id,
            "fetched_at": record.fetched_at,
            "source_url": record.source_url,
            "payload": record.payload
        }))),
        None => Ok(Json(serde_json::json!({"message": "no data"}))),
    }
}

pub async fn trigger_iss(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    state
        .iss_service
        .fetch_and_store(&state.config.where_iss_url)
        .await?;
    last_iss(State(state)).await
}

pub async fn iss_trend(State(state): State<AppState>) -> Result<Json<Trend>, ApiError> {
    let trend = state.iss_service.calculate_trend().await?;
    Ok(Json(trend))
}

