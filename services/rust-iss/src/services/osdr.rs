use serde_json::Value;

use crate::clients::nasa::{NasaClient, NasaClientTrait};
use crate::domain::{ApiError, OsdrItem};
use crate::repo::osdr::{OsdrRepo, OsdrRepository};

pub struct OsdrService {
    repo: OsdrRepo,
    client: NasaClient,
    nasa_url: String,
    nasa_key: String,
}

impl OsdrService {
    pub fn new(repo: OsdrRepo, client: NasaClient, nasa_url: String, nasa_key: String) -> Self {
        Self {
            repo,
            client,
            nasa_url,
            nasa_key,
        }
    }

    pub async fn list(&self, limit: i64) -> Result<Vec<OsdrItem>, ApiError> {
        self.repo.list(limit).await
    }

    pub async fn sync(&self) -> Result<usize, ApiError> {
        let json = self.client.fetch_osdr(&self.nasa_url, &self.nasa_key).await?;

        let items = if let Some(a) = json.as_array() {
            a.clone()
        } else if let Some(v) = json.get("items").and_then(|x| x.as_array()) {
            v.clone()
        } else if let Some(v) = json.get("results").and_then(|x| x.as_array()) {
            v.clone()
        } else {
            vec![json.clone()]
        };

        let mut written = 0usize;
        for item in items {
            let osdr_item = self.parse_item(item)?;
            self.repo.upsert(&osdr_item).await?;
            written += 1;
        }

        Ok(written)
    }

    fn parse_item(&self, item: Value) -> Result<OsdrItem, ApiError> {
        // Валидация перед парсингом
        crate::domain::validation::validate_osdr_item(&item)
            .map_err(|e| ApiError::Validation(format!("OSDR item validation failed: {:?}", e)))?;

        let dataset_id = extract_string(&item, &["dataset_id", "id", "uuid", "studyId", "accession", "osdr_id"]);
        let title = extract_string(&item, &["title", "name", "label"]);
        let status = extract_string(&item, &["status", "state", "lifecycle"]);
        let updated_at = extract_datetime(&item, &["updated", "updated_at", "modified", "lastUpdated", "timestamp"]);

        Ok(OsdrItem {
            id: 0, // будет установлено БД
            dataset_id,
            title,
            status,
            updated_at,
            inserted_at: chrono::Utc::now(),
            raw: item,
        })
    }
}

fn extract_string(v: &Value, keys: &[&str]) -> Option<String> {
    for k in keys {
        if let Some(x) = v.get(*k) {
            if let Some(s) = x.as_str() {
                if !s.is_empty() {
                    return Some(s.to_string());
                }
            } else if x.is_number() {
                return Some(x.to_string());
            }
        }
    }
    None
}

fn extract_datetime(v: &Value, keys: &[&str]) -> Option<chrono::DateTime<chrono::Utc>> {
    use chrono::{NaiveDateTime, TimeZone};
    for k in keys {
        if let Some(x) = v.get(*k) {
            if let Some(s) = x.as_str() {
                if let Ok(dt) = s.parse::<chrono::DateTime<chrono::Utc>>() {
                    return Some(dt);
                }
                if let Ok(ndt) = NaiveDateTime::parse_from_str(s, "%Y-%m-%d %H:%M:%S") {
                    return Some(chrono::Utc.from_utc_datetime(&ndt));
                }
            } else if let Some(n) = x.as_i64() {
                return chrono::Utc.timestamp_opt(n, 0).single();
            }
        }
    }
    None
}

