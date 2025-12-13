use serde_json::Value;

use crate::clients::nasa::{NasaClient, NasaClientTrait};
use crate::clients::spacex::{SpaceXClient, SpaceXClientTrait};
use crate::domain::{ApiError, SpaceSummary};
use crate::repo::cache::{CacheRepo, CacheRepository};

pub struct SpaceService {
    cache_repo: CacheRepo,
    nasa_client: NasaClient,
    spacex_client: SpaceXClient,
    nasa_key: String,
}

impl SpaceService {
    pub fn new(
        cache_repo: CacheRepo,
        nasa_client: NasaClient,
        spacex_client: SpaceXClient,
        nasa_key: String,
    ) -> Self {
        Self {
            cache_repo,
            nasa_client,
            spacex_client,
            nasa_key,
        }
    }

    pub async fn get_latest(&self, source: &str) -> Result<Value, ApiError> {
        let entry = self.cache_repo.get_latest(source).await?;
        Ok(entry
            .map(|e| {
                serde_json::json!({
                    "source": source,
                    "fetched_at": e.fetched_at,
                    "payload": e.payload
                })
            })
            .unwrap_or_else(|| serde_json::json!({ "source": source, "message": "no data" })))
    }

    pub async fn refresh(&self, sources: &[&str]) -> Result<Vec<String>, ApiError> {
        let mut done = Vec::new();

        for src in sources {
            match *src {
                "apod" => {
                    if self.fetch_apod().await.is_ok() {
                        done.push("apod".to_string());
                    }
                }
                "neo" => {
                    if self.fetch_neo().await.is_ok() {
                        done.push("neo".to_string());
                    }
                }
                "flr" => {
                    if self.fetch_flr().await.is_ok() {
                        done.push("flr".to_string());
                    }
                }
                "cme" => {
                    if self.fetch_cme().await.is_ok() {
                        done.push("cme".to_string());
                    }
                }
                "spacex" => {
                    if self.fetch_spacex().await.is_ok() {
                        done.push("spacex".to_string());
                    }
                }
                _ => {}
            }
        }

        Ok(done)
    }

    pub async fn get_summary(&self) -> Result<SpaceSummary, ApiError> {
        let apod = self.get_latest("apod").await?;
        let neo = self.get_latest("neo").await?;
        let flr = self.get_latest("flr").await?;
        let cme = self.get_latest("cme").await?;
        let spacex = self.get_latest("spacex").await?;

        // ISS получаем из кэша или пустой объект (игнорируем ошибки)
        let iss = self.get_latest("iss").await.unwrap_or_else(|_| serde_json::json!({}));
        let osdr_count = self.cache_repo.count_osdr().await?;

        Ok(SpaceSummary {
            apod,
            neo,
            flr,
            cme,
            spacex,
            iss,
            osdr_count,
        })
    }

    async fn fetch_apod(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_apod(&self.nasa_key).await?;
        crate::domain::validation::validate_space_cache_entry("apod", &payload)
            .map_err(|e| ApiError::Validation(format!("APOD validation failed: {:?}", e)))?;
        self.cache_repo.insert("apod", payload).await
    }

    async fn fetch_neo(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_neo_feed(&self.nasa_key, 2).await?;
        crate::domain::validation::validate_space_cache_entry("neo", &payload)
            .map_err(|e| ApiError::Validation(format!("NeoWs validation failed: {:?}", e)))?;
        self.cache_repo.insert("neo", payload).await
    }

    async fn fetch_flr(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_donki_flr(&self.nasa_key, 5).await?;
        crate::domain::validation::validate_space_cache_entry("flr", &payload)
            .map_err(|e| ApiError::Validation(format!("DONKI FLR validation failed: {:?}", e)))?;
        self.cache_repo.insert("flr", payload).await
    }

    async fn fetch_cme(&self) -> Result<(), ApiError> {
        let payload = self.nasa_client.fetch_donki_cme(&self.nasa_key, 5).await?;
        crate::domain::validation::validate_space_cache_entry("cme", &payload)
            .map_err(|e| ApiError::Validation(format!("DONKI CME validation failed: {:?}", e)))?;
        self.cache_repo.insert("cme", payload).await
    }

    async fn fetch_spacex(&self) -> Result<(), ApiError> {
        let payload = self.spacex_client.fetch_next_launch().await?;
        crate::domain::validation::validate_space_cache_entry("spacex", &payload)
            .map_err(|e| ApiError::Validation(format!("SpaceX validation failed: {:?}", e)))?;
        self.cache_repo.insert("spacex", payload).await
    }
}

