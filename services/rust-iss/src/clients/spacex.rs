use async_trait::async_trait;
use serde_json::Value;

use crate::clients::http::HttpClient;
use crate::domain::ApiError;

#[async_trait]
pub trait SpaceXClientTrait: Send + Sync {
    async fn fetch_next_launch(&self) -> Result<Value, ApiError>;
}

pub struct SpaceXClient {
    http: HttpClient,
}

impl SpaceXClient {
    pub fn new(http: HttpClient) -> Self {
        Self { http }
    }
}

#[async_trait]
impl SpaceXClientTrait for SpaceXClient {
    async fn fetch_next_launch(&self) -> Result<Value, ApiError> {
        self.http
            .get_json("https://api.spacexdata.com/v4/launches/next")
            .await
            .map_err(|e| ApiError::ExternalApi(format!("SpaceX API error: {}", e)))
    }
}



