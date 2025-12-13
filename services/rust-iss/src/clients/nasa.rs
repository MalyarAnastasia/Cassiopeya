use async_trait::async_trait;
use chrono::{Days, Utc};
use serde_json::Value;

use crate::clients::http::HttpClient;
use crate::domain::ApiError;

#[async_trait]
pub trait NasaClientTrait: Send + Sync {
    async fn fetch_apod(&self, api_key: &str) -> Result<Value, ApiError>;
    async fn fetch_neo_feed(&self, api_key: &str, days: u64) -> Result<Value, ApiError>;
    async fn fetch_donki_flr(&self, api_key: &str, days: u64) -> Result<Value, ApiError>;
    async fn fetch_donki_cme(&self, api_key: &str, days: u64) -> Result<Value, ApiError>;
    async fn fetch_osdr(&self, url: &str, api_key: &str) -> Result<Value, ApiError>;
}

#[derive(Clone)]
pub struct NasaClient {
    http: HttpClient,
}

impl NasaClient {
    pub fn new(http: HttpClient) -> Self {
        Self { http }
    }
}

#[async_trait]
impl NasaClientTrait for NasaClient {
    async fn fetch_apod(&self, api_key: &str) -> Result<Value, ApiError> {
        let mut query = vec![("thumbs".to_string(), "true".to_string())];
        if !api_key.is_empty() {
            query.push(("api_key".to_string(), api_key.to_string()));
        }

        self.http
            .get_json_with_query("https://api.nasa.gov/planetary/apod", &query)
            .await
            .map_err(|e| ApiError::ExternalApi(format!("APOD API error: {}", e)))
    }

    async fn fetch_neo_feed(&self, api_key: &str, days: u64) -> Result<Value, ApiError> {
        let today = Utc::now().date_naive();
        let start = today - Days::new(days);

        let mut query = vec![
            ("start_date".to_string(), start.to_string()),
            ("end_date".to_string(), today.to_string()),
        ];
        if !api_key.is_empty() {
            query.push(("api_key".to_string(), api_key.to_string()));
        }

        self.http
            .get_json_with_query("https://api.nasa.gov/neo/rest/v1/feed", &query)
            .await
            .map_err(|e| ApiError::ExternalApi(format!("NeoWs API error: {}", e)))
    }

    async fn fetch_donki_flr(&self, api_key: &str, days: u64) -> Result<Value, ApiError> {
        let today = Utc::now().date_naive();
        let start = today - Days::new(days);

        let mut query = vec![
            ("startDate".to_string(), start.to_string()),
            ("endDate".to_string(), today.to_string()),
        ];
        if !api_key.is_empty() {
            query.push(("api_key".to_string(), api_key.to_string()));
        }

        self.http
            .get_json_with_query("https://api.nasa.gov/DONKI/FLR", &query)
            .await
            .map_err(|e| ApiError::ExternalApi(format!("DONKI FLR API error: {}", e)))
    }

    async fn fetch_donki_cme(&self, api_key: &str, days: u64) -> Result<Value, ApiError> {
        let today = Utc::now().date_naive();
        let start = today - Days::new(days);

        let mut query = vec![
            ("startDate".to_string(), start.to_string()),
            ("endDate".to_string(), today.to_string()),
        ];
        if !api_key.is_empty() {
            query.push(("api_key".to_string(), api_key.to_string()));
        }

        self.http
            .get_json_with_query("https://api.nasa.gov/DONKI/CME", &query)
            .await
            .map_err(|e| ApiError::ExternalApi(format!("DONKI CME API error: {}", e)))
    }

    async fn fetch_osdr(&self, url: &str, api_key: &str) -> Result<Value, ApiError> {
        let query = if !api_key.is_empty() {
            vec![("api_key".to_string(), api_key.to_string())]
        } else {
            vec![]
        };

        self.http
            .get_json_with_query(url, &query)
            .await
            .map_err(|e| ApiError::ExternalApi(format!("OSDR API error: {}", e)))
    }
}


