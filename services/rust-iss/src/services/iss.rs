use serde_json::Value;

use crate::clients::iss::{IssClient, IssClientTrait};
use crate::domain::{ApiError, IssRecord, Trend};
use crate::repo::iss::{IssRepo, IssRepository};

pub struct IssService {
    repo: IssRepo,
    client: IssClient,
}

impl IssService {
    pub fn new(repo: IssRepo, client: IssClient) -> Self {
        Self { repo, client }
    }

    pub async fn get_last(&self) -> Result<Option<IssRecord>, ApiError> {
        self.repo.get_last().await
    }

    pub async fn fetch_and_store(&self, url: &str) -> Result<(), ApiError> {
        let payload = self.client.fetch_position(url).await?;
        
        // Валидация данных
        crate::domain::validation::validate_iss_payload(&payload)
            .map_err(|e| ApiError::Validation(format!("ISS payload validation failed: {:?}", e)))?;
        
        self.repo.insert(url, payload).await
    }

    pub async fn calculate_trend(&self) -> Result<Trend, ApiError> {
        let points = self.repo.get_trend_points(2).await?;

        if points.len() < 2 {
            return Ok(Trend {
                movement: false,
                delta_km: 0.0,
                dt_sec: 0.0,
                velocity_kmh: None,
                from_time: None,
                to_time: None,
                from_lat: None,
                from_lon: None,
                to_lat: None,
                to_lon: None,
            });
        }

        let p1 = &points[1];
        let p2 = &points[0];

        let lat1 = extract_number(&p1.payload, "latitude");
        let lon1 = extract_number(&p1.payload, "longitude");
        let lat2 = extract_number(&p2.payload, "latitude");
        let lon2 = extract_number(&p2.payload, "longitude");
        let v2 = extract_number(&p2.payload, "velocity");

        let mut delta_km = 0.0;
        let mut movement = false;

        if let (Some(a1), Some(o1), Some(a2), Some(o2)) = (lat1, lon1, lat2, lon2) {
            delta_km = haversine_km(a1, o1, a2, o2);
            movement = delta_km > 0.1;
        }

        let dt_sec = (p2.fetched_at - p1.fetched_at).num_milliseconds() as f64 / 1000.0;

        Ok(Trend {
            movement,
            delta_km,
            dt_sec,
            velocity_kmh: v2,
            from_time: Some(p1.fetched_at),
            to_time: Some(p2.fetched_at),
            from_lat: lat1,
            from_lon: lon1,
            to_lat: lat2,
            to_lon: lon2,
        })
    }
}

fn extract_number(v: &Value, key: &str) -> Option<f64> {
    v.get(key)
        .and_then(|x| {
            if let Some(n) = x.as_f64() {
                Some(n)
            } else if let Some(s) = x.as_str() {
                s.parse::<f64>().ok()
            } else {
                None
            }
        })
}

fn haversine_km(lat1: f64, lon1: f64, lat2: f64, lon2: f64) -> f64 {
    let rlat1 = lat1.to_radians();
    let rlat2 = lat2.to_radians();
    let dlat = (lat2 - lat1).to_radians();
    let dlon = (lon2 - lon1).to_radians();
    let a = (dlat / 2.0).sin().powi(2) + rlat1.cos() * rlat2.cos() * (dlon / 2.0).sin().powi(2);
    let c = 2.0 * a.sqrt().atan2((1.0 - a).sqrt());
    6371.0 * c
}

