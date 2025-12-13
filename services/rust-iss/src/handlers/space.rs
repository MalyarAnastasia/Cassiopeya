use std::collections::HashMap;

use axum::{extract::Path, extract::Query, extract::State, Json};
use serde_json::Value;

use crate::domain::{ApiError, SpaceSummary};
use crate::AppState;

pub async fn space_latest(
    Path(src): Path<String>,
    State(state): State<AppState>,
) -> Result<Json<Value>, ApiError> {
    let result = state.space_service.get_latest(&src).await?;
    Ok(Json(result))
}

pub async fn space_refresh(
    Query(q): Query<HashMap<String, String>>,
    State(state): State<AppState>,
) -> Result<Json<Value>, ApiError> {
    let list = q
        .get("src")
        .cloned()
        .unwrap_or_else(|| "apod,neo,flr,cme,spacex".to_string());

    let sources: Vec<&str> = list.split(',').map(|x| x.trim()).collect();
    let done = state.space_service.refresh(&sources).await?;

    Ok(Json(serde_json::json!({ "refreshed": done })))
}

pub async fn space_summary(State(state): State<AppState>) -> Result<Json<SpaceSummary>, ApiError> {
    let summary = state.space_service.get_summary().await?;
    Ok(Json(summary))
}

