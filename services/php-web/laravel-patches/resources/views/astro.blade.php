@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-4">Астрономические события - Данные позиций МКС</h3>

  <div class="card shadow-sm animate-slide-up">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Время (UTC)</th>
              <th>Широта</th>
              <th>Долгота</th>
              <th>Высота (км)</th>
              <th>Скорость (км/ч)</th>
              <th>Видимость</th>
            </tr>
          </thead>
          <tbody id="astroBody">
            <tr>
              <td colspan="7" class="text-muted text-center">Загрузка данных...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <details class="mt-3">
        <summary class="btn btn-outline-secondary btn-sm">Показать полный JSON</summary>
        <pre id="astroRaw" class="bg-light rounded p-3 mt-2 small" style="white-space:pre-wrap;max-height:400px;overflow:auto"></pre>
      </details>
    </div>
  </div>
</div>

<style>
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  @keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .animate-fade-in {
    animation: fadeIn 0.5s ease-out;
  }

  .animate-slide-up {
    animation: slideUp 0.6s ease-out;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const body = document.getElementById('astroBody');
  const raw = document.getElementById('astroRaw');

  function getDemoPositions() {
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
  }

  function loadDemoData() {
    const data = getDemoPositions();
    raw.textContent = JSON.stringify(data, null, 2);
    
    body.innerHTML = data.map((item, i) => `
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
  }

  loadDemoData();
});
</script>
@endsection



