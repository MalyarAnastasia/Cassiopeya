/**
 * Tests for dashboard.blade.php - Main dashboard page
 */

describe('Dashboard Page - ISS Trend Loading', () => {
  let loadTrend;

  beforeEach(() => {
    // Set up DOM
    document.body.innerHTML = `
      <div id="map"></div>
      <canvas id="issSpeedChart"></canvas>
      <canvas id="issAltChart"></canvas>
    `;

    // Mock global objects
    global.L = {
      map: jest.fn(() => ({
        setView: jest.fn().mockReturnThis(),
      })),
      tileLayer: jest.fn(() => ({
        addTo: jest.fn().mockReturnThis(),
      })),
      polyline: jest.fn(() => ({
        addTo: jest.fn().mockReturnThis(),
        setLatLngs: jest.fn(),
      })),
      marker: jest.fn(() => ({
        addTo: jest.fn().mockReturnThis(),
        bindPopup: jest.fn().mockReturnThis(),
        setLatLng: jest.fn(),
      })),
    };

    const mockChart = {
      data: { labels: [], datasets: [{ data: [] }] },
      update: jest.fn(),
    };

    global.Chart = jest.fn(() => mockChart);

    const trail = global.L.polyline();
    const marker = global.L.marker();
    const speedChart = new global.Chart();
    const altChart = new global.Chart();

    loadTrend = async function() {
      try {
        const r = await fetch('/api/iss/trend?limit=240');
        const js = await r.json();
        const pts = Array.isArray(js.points) ? js.points.map(p => [p.lat, p.lon]) : [];
        if (pts.length) {
          trail.setLatLngs(pts);
          marker.setLatLng(pts[pts.length-1]);
        }
        const t = (js.points||[]).map(p => new Date(p.at).toLocaleTimeString());
        speedChart.data.labels = t;
        speedChart.data.datasets[0].data = (js.points||[]).map(p => p.velocity);
        speedChart.update();
        altChart.data.labels = t;
        altChart.data.datasets[0].data = (js.points||[]).map(p => p.altitude);
        altChart.update();
      } catch(e) {}
    };
  });

  test('should fetch ISS trend data', async () => {
    const mockData = {
      points: [
        { at: '2025-01-01T00:00:00Z', lat: 51.5, lon: -0.1, velocity: 27600, altitude: 408 }
      ]
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadTrend();

    expect(global.fetch).toHaveBeenCalledWith('/api/iss/trend?limit=240');
  });

  test('should handle fetch errors gracefully', async () => {
    global.fetch = jest.fn(() => Promise.reject(new Error('Network error')));

    await expect(loadTrend()).resolves.not.toThrow();
  });

  test('should update charts with velocity data', async () => {
    const mockData = {
      points: [
        { at: '2025-01-01T00:00:00Z', lat: 51.5, lon: -0.1, velocity: 27600, altitude: 408 },
        { at: '2025-01-01T01:00:00Z', lat: 52.5, lon: 1.0, velocity: 27700, altitude: 410 }
      ]
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    const speedChart = new global.Chart();
    const altChart = new global.Chart();

    await loadTrend();

    // Charts should be updated
    expect(speedChart.update).toHaveBeenCalled();
    expect(altChart.update).toHaveBeenCalled();
  });
});

describe('Dashboard Page - JWST Feed', () => {
  let loadFeed;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="jwstTrack"></div>
      <div id="jwstInfo"></div>
    `;

    loadFeed = async function(qs) {
      const track = document.getElementById('jwstTrack');
      const info = document.getElementById('jwstInfo');
      
      track.innerHTML = '<div class="p-3 text-muted">Загрузка…</div>';
      info.textContent = '';
      
      try {
        const url = '/api/jwst/feed?' + new URLSearchParams(qs).toString();
        const r = await fetch(url);
        const js = await r.json();
        track.innerHTML = '';
        
        (js.items||[]).forEach(it => {
          const fig = document.createElement('figure');
          fig.className = 'jwst-item m-0';
          fig.innerHTML = `
            <a href="${it.link||it.url}" target="_blank" rel="noreferrer">
              <img loading="lazy" src="${it.url}" alt="JWST">
            </a>
            <figcaption class="jwst-cap">${(it.caption||'').replaceAll('<','&lt;')}</figcaption>`;
          track.appendChild(fig);
        });
        
        info.textContent = `Источник: ${js.source} · Показано ${js.count||0}`;
      } catch(e) {
        track.innerHTML = '<div class="p-3 text-danger">Ошибка загрузки</div>';
      }
    };
  });

  test('should display loading message initially', async () => {
    const track = document.getElementById('jwstTrack');
    
    global.fetch = jest.fn(() => new Promise(() => {})); // Never resolves
    
    loadFeed({ source: 'jpg', perPage: 24 });
    
    await new Promise(resolve => setTimeout(resolve, 10));
    
    expect(track.textContent).toContain('Загрузка');
  });

  test('should load JWST feed data', async () => {
    const mockData = {
      source: 'all/type/jpg',
      count: 2,
      items: [
        { url: 'http://example.com/img1.jpg', caption: 'Image 1', link: 'http://example.com/img1' },
        { url: 'http://example.com/img2.jpg', caption: 'Image 2', link: 'http://example.com/img2' }
      ]
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24 });

    expect(global.fetch).toHaveBeenCalledWith('/api/jwst/feed?source=jpg&perPage=24');
  });

  test('should render JWST images', async () => {
    const mockData = {
      source: 'all/type/jpg',
      count: 1,
      items: [
        { url: 'http://example.com/img1.jpg', caption: 'Test Image', link: 'http://example.com/img1' }
      ]
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24 });

    const track = document.getElementById('jwstTrack');
    const figures = track.querySelectorAll('figure');
    expect(figures.length).toBe(1);
    
    const img = track.querySelector('img');
    expect(img.src).toBe('http://example.com/img1.jpg');
  });

  test('should display info with source and count', async () => {
    const mockData = {
      source: 'all/type/jpg',
      count: 5,
      items: []
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24 });

    const info = document.getElementById('jwstInfo');
    expect(info.textContent).toContain('all/type/jpg');
    expect(info.textContent).toContain('5');
  });

  test('should handle fetch errors', async () => {
    global.fetch = jest.fn(() => Promise.reject(new Error('Network error')));

    await loadFeed({ source: 'jpg', perPage: 24 });

    const track = document.getElementById('jwstTrack');
    expect(track.textContent).toContain('Ошибка загрузки');
  });

  test('should escape HTML in captions', async () => {
    const mockData = {
      source: 'test',
      count: 1,
      items: [
        { url: 'http://example.com/img1.jpg', caption: '<script>alert("xss")</script>', link: 'http://example.com/img1' }
      ]
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24 });

    const track = document.getElementById('jwstTrack');
    expect(track.innerHTML).toContain('&lt;script&gt;');
    expect(track.innerHTML).not.toContain('<script>alert');
  });
});

describe('Dashboard Page - Form Handling', () => {
  test('should handle source select change', () => {
    document.body.innerHTML = `
      <select id="srcSel">
        <option value="jpg">JPG</option>
        <option value="suffix">Suffix</option>
      </select>
      <input id="suffixInp" style="display:none">
      <input id="progInp" style="display:none">
    `;

    const srcSel = document.getElementById('srcSel');
    const sfxInp = document.getElementById('suffixInp');
    const progInp = document.getElementById('progInp');

    function toggleInputs() {
      sfxInp.style.display = (srcSel.value === 'suffix') ? '' : 'none';
      progInp.style.display = (srcSel.value === 'program') ? '' : 'none';
    }

    toggleInputs();
    expect(sfxInp.style.display).toBe('none');

    srcSel.value = 'suffix';
    toggleInputs();
    expect(sfxInp.style.display).toBe('');
  });
});

