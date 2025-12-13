use serde_json::Value;

pub fn validate_iss_payload(payload: &Value) -> Result<(), String> {
    if !payload.is_object() {
        return Err("invalid_format".to_string());
    }

    // Проверяем наличие обязательных полей
    if payload.get("latitude").is_none() && payload.get("longitude").is_none() {
        return Err("missing_coordinates".to_string());
    }

    Ok(())
}

pub fn validate_osdr_item(item: &Value) -> Result<(), String> {
    if !item.is_object() {
        return Err("invalid_format".to_string());
    }

    Ok(())
}

pub fn validate_space_cache_entry(source: &str, payload: &Value) -> Result<(), String> {
    if source.is_empty() {
        return Err("empty_source".to_string());
    }

    if !payload.is_object() && !payload.is_array() {
        return Err("invalid_payload_format".to_string());
    }

    Ok(())
}



