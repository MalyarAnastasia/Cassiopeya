use async_trait::async_trait;
use serde_json::Value;

use crate::clients::http::HttpClient;
use crate::domain::ApiError;

#[async_trait]
pub trait IssClientTrait: Send + Sync {
    async fn fetch_position(&self, url: &str) -> Result<Value, ApiError>;
}

pub struct IssClient {
    http: HttpClient,
}

impl IssClient {
    pub fn new(http: HttpClient) -> Self {
        Self { http }
    }
}

#[async_trait]
impl IssClientTrait for IssClient {
    async fn fetch_position(&self, url: &str) -> Result<Value, ApiError> {
        self.http
            .get_json(url)
            .await
            .map_err(|e| ApiError::ExternalApi(format!("ISS API error: {}", e)))
    }
}



