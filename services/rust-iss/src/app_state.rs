use std::sync::Arc;
use sqlx::PgPool;
use redis::Client as RedisClient;

use crate::config::Config;
use crate::services::{IssService, OsdrService, SpaceService};

#[derive(Clone)]
pub struct AppState {
    pub config: Arc<Config>,
    pub pool: PgPool,
    pub redis: Option<Arc<RedisClient>>,
    pub iss_service: Arc<IssService>,
    pub osdr_service: Arc<OsdrService>,
    pub space_service: Arc<SpaceService>,
}


