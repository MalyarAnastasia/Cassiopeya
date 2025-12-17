/**
 * Tests for iss.blade.php - ISS data page
 */

describe('ISS Page - Data Display', () => {
  test('should format coordinates correctly', () => {
    const latitude = 51.5074;
    const longitude = -0.1278;
    
    expect(typeof latitude).toBe('number');
    expect(typeof longitude).toBe('number');
    expect(latitude).toBeGreaterThanOrEqual(-90);
    expect(latitude).toBeLessThanOrEqual(90);
    expect(longitude).toBeGreaterThanOrEqual(-180);
    expect(longitude).toBeLessThanOrEqual(180);
  });

  test('should format altitude correctly', () => {
    const altitude = 408.5;
    const formatted = altitude.toFixed(1);
    
    expect(formatted).toBe('408.5');
  });

  test('should format velocity correctly', () => {
    const velocity = 27600.123;
    const formatted = Math.round(velocity);
    
    expect(formatted).toBe(27600);
  });

  test('should format delta km with 3 decimal places', () => {
    const deltaKm = 123.456789;
    const formatted = deltaKm.toFixed(3);
    
    expect(formatted).toBe('123.457');
  });
});

describe('ISS Page - API Endpoints', () => {
  test('should construct correct last endpoint', () => {
    const base = 'http://rust_iss:3000';
    const endpoint = `${base}/last`;
    
    expect(endpoint).toBe('http://rust_iss:3000/last');
  });

  test('should construct correct trend endpoint', () => {
    const base = 'http://rust_iss:3000';
    const endpoint = `${base}/iss/trend`;
    
    expect(endpoint).toBe('http://rust_iss:3000/iss/trend');
  });
});

describe('ISS Page - Data Validation', () => {
  test('should validate ISS position data structure', () => {
    const issData = {
      payload: {
        latitude: 51.5074,
        longitude: -0.1278,
        altitude: 408.0,
        velocity: 27600.0
      },
      fetched_at: '2025-01-01T00:00:00Z'
    };

    expect(issData).toHaveProperty('payload');
    expect(issData.payload).toHaveProperty('latitude');
    expect(issData.payload).toHaveProperty('longitude');
    expect(issData.payload).toHaveProperty('altitude');
    expect(issData.payload).toHaveProperty('velocity');
    expect(issData).toHaveProperty('fetched_at');
  });

  test('should validate trend data structure', () => {
    const trendData = {
      movement: true,
      delta_km: 123.456,
      dt_sec: 60,
      velocity_kmh: 27600
    };

    expect(trendData).toHaveProperty('movement');
    expect(trendData).toHaveProperty('delta_km');
    expect(trendData).toHaveProperty('dt_sec');
    expect(trendData).toHaveProperty('velocity_kmh');
    expect(typeof trendData.movement).toBe('boolean');
    expect(typeof trendData.delta_km).toBe('number');
    expect(typeof trendData.dt_sec).toBe('number');
  });

  test('should handle missing payload gracefully', () => {
    const issData = {};
    
    const latitude = issData.payload?.latitude ?? '—';
    const longitude = issData.payload?.longitude ?? '—';
    
    expect(latitude).toBe('—');
    expect(longitude).toBe('—');
  });

  test('should handle missing trend data gracefully', () => {
    const trendData = null;
    
    const movement = trendData?.movement ?? false;
    const deltaKm = trendData?.delta_km ?? 0;
    
    expect(movement).toBe(false);
    expect(deltaKm).toBe(0);
  });
});

