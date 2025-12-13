use std::time::Duration;

#[derive(Clone, Debug)]
pub struct Config {
    pub database_url: String,
    pub redis_url: String,
    pub nasa_url: String,
    pub nasa_key: String,
    pub where_iss_url: String,
    pub user_agent: String,
    pub fetch_intervals: FetchIntervals,
    pub timeouts: Timeouts,
    pub retry: RetryConfig,
    pub rate_limit: RateLimitConfig,
}

#[derive(Clone, Debug)]
pub struct FetchIntervals {
    pub osdr: u64,
    pub iss: u64,
    pub apod: u64,
    pub neo: u64,
    pub donki: u64,
    pub spacex: u64,
}

#[derive(Clone, Debug)]
pub struct Timeouts {
    pub http_connect: Duration,
    pub http_read: Duration,
    pub http_total: Duration,
}

#[derive(Clone, Debug)]
pub struct RetryConfig {
    pub max_attempts: u32,
    pub initial_delay_ms: u64,
    pub max_delay_ms: u64,
    pub backoff_multiplier: f64,
}

#[derive(Clone, Debug)]
pub struct RateLimitConfig {
    pub requests_per_minute: u32,
    pub burst_size: u32,
}

impl Config {
    pub fn from_env() -> Result<Self, String> {
        dotenvy::dotenv().ok();
        
        Ok(Config {
            database_url: std::env::var("DATABASE_URL")
                .map_err(|_| "DATABASE_URL is required")?,
            redis_url: std::env::var("REDIS_URL")
                .unwrap_or_else(|_| "redis://redis:6379".to_string()),
            nasa_url: std::env::var("NASA_API_URL")
                .unwrap_or_else(|_| "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json".to_string()),
            nasa_key: std::env::var("NASA_API_KEY").unwrap_or_default(),
            where_iss_url: std::env::var("WHERE_ISS_URL")
                .unwrap_or_else(|_| "https://api.wheretheiss.at/v1/satellites/25544".to_string()),
            user_agent: std::env::var("USER_AGENT")
                .unwrap_or_else(|_| "Cassiopeya-Space-Data-Collector/1.0".to_string()),
            fetch_intervals: FetchIntervals {
                osdr: env_u64("FETCH_EVERY_SECONDS", 600),
                iss: env_u64("ISS_EVERY_SECONDS", 120),
                apod: env_u64("APOD_EVERY_SECONDS", 43200),
                neo: env_u64("NEO_EVERY_SECONDS", 7200),
                donki: env_u64("DONKI_EVERY_SECONDS", 3600),
                spacex: env_u64("SPACEX_EVERY_SECONDS", 3600),
            },
            timeouts: Timeouts {
                http_connect: Duration::from_secs(10),
                http_read: Duration::from_secs(30),
                http_total: Duration::from_secs(60),
            },
            retry: RetryConfig {
                max_attempts: env_u64("RETRY_MAX_ATTEMPTS", 3) as u32,
                initial_delay_ms: env_u64("RETRY_INITIAL_DELAY_MS", 1000),
                max_delay_ms: env_u64("RETRY_MAX_DELAY_MS", 10000),
                backoff_multiplier: 2.0,
            },
            rate_limit: RateLimitConfig {
                requests_per_minute: env_u64("RATE_LIMIT_PER_MINUTE", 60) as u32,
                burst_size: env_u64("RATE_LIMIT_BURST", 10) as u32,
            },
        })
    }
}

fn env_u64(k: &str, d: u64) -> u64 {
    std::env::var(k).ok().and_then(|s| s.parse().ok()).unwrap_or(d)
}



