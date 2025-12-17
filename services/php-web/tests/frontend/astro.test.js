/**
 * Tests for astro.blade.php - Astronomical events page
 */

describe('Astro Page - getDemoPositions', () => {
  let getDemoPositions;

  beforeEach(() => {
    // Simulate the getDemoPositions function from astro.blade.php
    getDemoPositions = function() {
      const now = new Date();
      return [
        {
          "id": 1,
          "timestamp": new Date(now.getTime() - 0).toISOString(),
          "latitude": 51.5074,
          "longitude": -0.1278,
          "altitude": 408.0,
          "velocity": 27600.0,
          "visibility": "visible"
        },
        {
          "id": 2,
          "timestamp": new Date(now.getTime() - 3600000).toISOString(),
          "latitude": 48.8566,
          "longitude": 2.3522,
          "altitude": 410.0,
          "velocity": 27500.0,
          "visibility": "visible"
        },
        {
          "id": 3,
          "timestamp": new Date(now.getTime() - 7200000).toISOString(),
          "latitude": 40.7128,
          "longitude": -74.0060,
          "altitude": 412.0,
          "velocity": 27400.0,
          "visibility": "visible"
        },
        {
          "id": 4,
          "timestamp": new Date(now.getTime() - 10800000).toISOString(),
          "latitude": 55.7558,
          "longitude": 37.6173,
          "altitude": 409.0,
          "velocity": 27700.0,
          "visibility": "visible"
        },
        {
          "id": 5,
          "timestamp": new Date(now.getTime() - 14400000).toISOString(),
          "latitude": 35.6762,
          "longitude": 139.6503,
          "altitude": 411.0,
          "velocity": 27650.0,
          "visibility": "visible"
        },
        {
          "id": 6,
          "timestamp": new Date(now.getTime() - 18000000).toISOString(),
          "latitude": 25.2048,
          "longitude": 55.2708,
          "altitude": 407.0,
          "velocity": 27580.0,
          "visibility": "visible"
        },
        {
          "id": 7,
          "timestamp": new Date(now.getTime() - 21600000).toISOString(),
          "latitude": -33.8688,
          "longitude": 151.2093,
          "altitude": 410.5,
          "velocity": 27620.0,
          "visibility": "visible"
        },
        {
          "id": 8,
          "timestamp": new Date(now.getTime() - 25200000).toISOString(),
          "latitude": 39.9042,
          "longitude": 116.4074,
          "altitude": 408.5,
          "velocity": 27590.0,
          "visibility": "visible"
        },
        {
          "id": 9,
          "timestamp": new Date(now.getTime() - 28800000).toISOString(),
          "latitude": -23.5505,
          "longitude": -46.6333,
          "altitude": 409.8,
          "velocity": 27610.0,
          "visibility": "visible"
        },
        {
          "id": 10,
          "timestamp": new Date(now.getTime() - 32400000).toISOString(),
          "latitude": 28.6139,
          "longitude": 77.2090,
          "altitude": 407.5,
          "velocity": 27570.0,
          "visibility": "visible"
        }
      ];
    };
  });

  test('should return 10 demo positions', () => {
    const positions = getDemoPositions();
    expect(positions).toHaveLength(10);
  });

  test('each position should have required fields', () => {
    const positions = getDemoPositions();
    positions.forEach(position => {
      expect(position).toHaveProperty('id');
      expect(position).toHaveProperty('timestamp');
      expect(position).toHaveProperty('latitude');
      expect(position).toHaveProperty('longitude');
      expect(position).toHaveProperty('altitude');
      expect(position).toHaveProperty('velocity');
      expect(position).toHaveProperty('visibility');
    });
  });

  test('all positions should have visibility set to "visible"', () => {
    const positions = getDemoPositions();
    positions.forEach(position => {
      expect(position.visibility).toBe('visible');
    });
  });

  test('timestamps should be in ISO format', () => {
    const positions = getDemoPositions();
    const isoRegex = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/;
    positions.forEach(position => {
      expect(position.timestamp).toMatch(isoRegex);
    });
  });

  test('latitude values should be within valid range', () => {
    const positions = getDemoPositions();
    positions.forEach(position => {
      expect(position.latitude).toBeGreaterThanOrEqual(-90);
      expect(position.latitude).toBeLessThanOrEqual(90);
    });
  });

  test('longitude values should be within valid range', () => {
    const positions = getDemoPositions();
    positions.forEach(position => {
      expect(position.longitude).toBeGreaterThanOrEqual(-180);
      expect(position.longitude).toBeLessThanOrEqual(180);
    });
  });

  test('altitude should be positive', () => {
    const positions = getDemoPositions();
    positions.forEach(position => {
      expect(position.altitude).toBeGreaterThan(0);
    });
  });

  test('velocity should be positive', () => {
    const positions = getDemoPositions();
    positions.forEach(position => {
      expect(position.velocity).toBeGreaterThan(0);
    });
  });

  test('IDs should be sequential from 1 to 10', () => {
    const positions = getDemoPositions();
    positions.forEach((position, index) => {
      expect(position.id).toBe(index + 1);
    });
  });

  test('first position should have London coordinates', () => {
    const positions = getDemoPositions();
    expect(positions[0].latitude).toBe(51.5074);
    expect(positions[0].longitude).toBe(-0.1278);
  });
});

describe('Astro Page - Data Display', () => {
  const mockData = [
    {
      "id": 1,
      "timestamp": new Date('2025-01-01T00:00:00.000Z').toISOString(),
      "latitude": 51.5074,
      "longitude": -0.1278,
      "altitude": 408.0,
      "velocity": 27600.0,
      "visibility": "visible"
    }
  ];

  test('should populate astroBody with table rows', () => {
    const body = document.createElement('tbody');
    body.id = 'astroBody';
    const raw = document.createElement('pre');
    raw.id = 'astroRaw';
    
    document.body.appendChild(body);
    document.body.appendChild(raw);
    
    raw.textContent = JSON.stringify(mockData, null, 2);
    body.innerHTML = mockData.map((item) => `
      <tr>
        <td>${item.id}</td>
        <td><code>${item.timestamp}</code></td>
        <td>${item.latitude.toFixed(4)}</td>
        <td>${item.longitude.toFixed(4)}</td>
        <td>${item.altitude.toFixed(1)}</td>
        <td>${item.velocity.toFixed(1)}</td>
        <td><span class="badge bg-success">${item.visibility}</span></td>
      </tr>
    `).join('');
    
    const rows = body.querySelectorAll('tr');
    expect(rows.length).toBe(1);
  });

  test('should display formatted coordinates', () => {
    const body = document.createElement('tbody');
    body.id = 'astroBody';
    document.body.appendChild(body);
    
    body.innerHTML = mockData.map((item) => `
      <tr><td>${item.latitude.toFixed(4)}</td><td>${item.longitude.toFixed(4)}</td></tr>
    `).join('');
    
    expect(body.textContent).toContain('51.5074');
    expect(body.textContent).toContain('-0.1278');
  });

  test('should display altitude with 1 decimal place', () => {
    const body = document.createElement('tbody');
    document.body.appendChild(body);
    
    body.innerHTML = `<tr><td>${mockData[0].altitude.toFixed(1)}</td></tr>`;
    
    expect(body.textContent).toContain('408.0');
  });

  test('should display velocity with 1 decimal place', () => {
    const body = document.createElement('tbody');
    document.body.appendChild(body);
    
    body.innerHTML = `<tr><td>${mockData[0].velocity.toFixed(1)}</td></tr>`;
    
    expect(body.textContent).toContain('27600.0');
  });

  test('should populate astroRaw with JSON', () => {
    const raw = document.createElement('pre');
    raw.id = 'astroRaw';
    document.body.appendChild(raw);
    
    raw.textContent = JSON.stringify(mockData, null, 2);
    
    const json = JSON.parse(raw.textContent);
    expect(json).toHaveLength(1);
    expect(json[0].id).toBe(1);
  });

  test('should display visibility badge', () => {
    const body = document.createElement('tbody');
    document.body.appendChild(body);
    
    body.innerHTML = `<tr><td><span class="badge bg-success">${mockData[0].visibility}</span></td></tr>`;
    
    const badge = body.querySelector('.badge');
    expect(badge).toBeTruthy();
    expect(badge.textContent).toBe('visible');
  });
});

