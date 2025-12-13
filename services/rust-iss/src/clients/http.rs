use std::sync::Arc;
use std::time::Duration;
use reqwest::Client;
use serde_json::Value;

use crate::config::{Config, RetryConfig};

#[derive(Clone)]
pub struct HttpClient {
    client: Arc<Client>,
    retry: RetryConfig,
    user_agent: String,
}

impl HttpClient {
    pub fn new(config: &Config) -> Result<Self, reqwest::Error> {
        let client = Client::builder()
            .timeout(config.timeouts.http_total)
            .connect_timeout(config.timeouts.http_connect)
            .user_agent(&config.user_agent)
            .build()?;

        Ok(Self {
            client: Arc::new(client),
            retry: config.retry.clone(),
            user_agent: config.user_agent.clone(),
        })
    }

    pub async fn get_json(&self, url: &str) -> Result<Value, reqwest::Error> {
        self.get_json_with_retry(url).await
    }

    pub async fn get_json_with_query(
        &self,
        url: &str,
        query: &[(String, String)],
    ) -> Result<Value, reqwest::Error> {
        // Конвертируем Vec<(String, String)> в Vec<(&str, &str)> для передачи
        let query_refs: Vec<(&str, &str)> = query.iter().map(|(k, v)| (k.as_str(), v.as_str())).collect();
        self.get_json_with_query_retry(url, &query_refs).await
    }

    async fn get_json_with_retry(&self, url: &str) -> Result<Value, reqwest::Error> {
        let mut last_error: Option<reqwest::Error> = None;
        let mut delay_ms = self.retry.initial_delay_ms;

        for attempt in 1..=self.retry.max_attempts {
            match self.client.get(url).send().await {
                Ok(resp) => {
                    if resp.status().is_success() {
                        return resp.json().await;
                    } else if resp.status().is_client_error() {
                        // Не повторяем при клиентских ошибках (4xx)
                        return Err(resp.error_for_status().unwrap_err());
                    }
                    // Сохраняем ошибку статуса
                    last_error = Some(resp.error_for_status().unwrap_err());
                }
                Err(e) => {
                    last_error = Some(e);
                    if attempt < self.retry.max_attempts {
                        tokio::time::sleep(Duration::from_millis(delay_ms)).await;
                        delay_ms = (delay_ms as f64 * self.retry.backoff_multiplier) as u64;
                        delay_ms = delay_ms.min(self.retry.max_delay_ms);
                    }
                }
            }
        }

        // Возвращаем последнюю ошибку или создаем новую через запрос к невалидному URL
        Err(last_error.unwrap_or_else(|| {
            // Создаем ошибку через попытку запроса к невалидному URL
            // Это будет синхронная операция, но reqwest::Error можно создать и так
            // Используем блокирующий вызов для создания ошибки
            match reqwest::blocking::get("http://[::1]:0") {
                Ok(_) => unreachable!(),
                Err(e) => e,
            }
        }))
    }

    async fn get_json_with_query_retry(
        &self,
        url: &str,
        query: &[(&str, &str)],
    ) -> Result<Value, reqwest::Error> {
        let mut last_error: Option<reqwest::Error> = None;
        let mut delay_ms = self.retry.initial_delay_ms;

        for attempt in 1..=self.retry.max_attempts {
            let mut req = self.client.get(url);
            for (k, v) in query {
                req = req.query(&[(*k, *v)]);
            }

            match req.send().await {
                Ok(resp) => {
                    if resp.status().is_success() {
                        return resp.json().await;
                    } else if resp.status().is_client_error() {
                        return Err(resp.error_for_status().unwrap_err());
                    }
                    // Сохраняем ошибку статуса
                    last_error = Some(resp.error_for_status().unwrap_err());
                }
                Err(e) => {
                    last_error = Some(e);
                    if attempt < self.retry.max_attempts {
                        tokio::time::sleep(Duration::from_millis(delay_ms)).await;
                        delay_ms = (delay_ms as f64 * self.retry.backoff_multiplier) as u64;
                        delay_ms = delay_ms.min(self.retry.max_delay_ms);
                    }
                }
            }
        }

        // Возвращаем последнюю ошибку или создаем новую
        Err(last_error.unwrap_or_else(|| {
            // Создаем ошибку через попытку запроса к невалидному URL
            match reqwest::blocking::get("http://[::1]:0") {
                Ok(_) => unreachable!(),
                Err(e) => e,
            }
        }))
    }
}


