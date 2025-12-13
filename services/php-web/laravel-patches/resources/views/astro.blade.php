@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-4">Астрономические события</h3>

  <div class="card shadow-sm mb-4 animate-fade-in">
    <div class="card-body">
      <form id="astroForm" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Широта</label>
          <input type="number" step="0.0001" class="form-control" name="lat" value="55.7558" placeholder="lat">
        </div>
        <div class="col-md-3">
          <label class="form-label">Долгота</label>
          <input type="number" step="0.0001" class="form-control" name="lon" value="37.6176" placeholder="lon">
        </div>
        <div class="col-md-2">
          <label class="form-label">Дней вперед</label>
          <input type="number" min="1" max="30" class="form-control" name="days" value="7" title="дней">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary w-100" type="submit">Показать</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm animate-slide-up">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Тело</th>
              <th>Событие</th>
              <th>Когда (UTC)</th>
              <th>Дополнительно</th>
            </tr>
          </thead>
          <tbody id="astroBody">
            <tr>
              <td colspan="5" class="text-muted text-center">Загрузка данных...</td>
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
  const form = document.getElementById('astroForm');
  const body = document.getElementById('astroBody');
  const raw = document.getElementById('astroRaw');

  function normalize(node) {
    const name = node.name || node.body || node.object || node.target || node.bodyName || '';
    const type = node.type || node.event_type || node.category || node.kind || node.eventType || '';
    const when = node.time || node.date || node.occursAt || node.peak || node.instant || node.datetime || '';
    const extra = node.magnitude || node.mag || node.altitude || node.note || node.description || 
                  (node.extra ? JSON.stringify(node.extra) : '') || '';
    return { name, type, when, extra };
  }

  function collect(root) {
    const rows = [];
    (function dfs(x, depth = 0) {
      if (!x || typeof x !== 'object' || depth > 10) return;
      if (Array.isArray(x)) { 
        x.forEach(item => dfs(item, depth + 1)); 
        return; 
      }
      
      // Проверяем различные структуры ответа AstronomyAPI
      if (x.data && Array.isArray(x.data)) {
        x.data.forEach(item => dfs(item, depth + 1));
        return;
      }
      
      if (x.events && Array.isArray(x.events)) {
        x.events.forEach(item => dfs(item, depth + 1));
        return;
      }
      
      // Проверяем, является ли это событием
      const hasEventIndicator = x.type || x.event_type || x.category || x.kind || x.eventType;
      const hasName = x.name || x.body || x.object || x.target || x.bodyName;
      
      if (hasEventIndicator && hasName) {
        rows.push(normalize(x));
      }
      
      // Рекурсивно обходим все свойства
      Object.values(x).forEach(val => {
        if (val && typeof val === 'object' && val !== x) {
          dfs(val, depth + 1);
        }
      });
    })(root);
    return rows;
  }

  async function load(q) {
    body.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Загрузка...</td></tr>';
    const url = '/api/astro/events?' + new URLSearchParams(q).toString();
    
    // Создаем AbortController для таймаута
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 120000); // 120 секунд таймаут (для проверки всех комбинаций)
    
    try {
      const r = await fetch(url, {
        signal: controller.signal,
        headers: {
          'Accept': 'application/json',
        },
        method: 'GET'
      });
      clearTimeout(timeoutId);
      
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
      }
      const js = await r.json();
      raw.textContent = JSON.stringify(js, null, 2);

      // Обработка ошибок API
      if (js.error) {
        const errorMsg = js.error.message || js.error.error || js.error.raw || 'Неизвестная ошибка';
        const errorCode = js.error.code || 'N/A';
        body.innerHTML = `<tr><td colspan="5" class="text-warning text-center">
          <strong>Ошибка API:</strong> ${errorMsg}<br>
          <small class="text-muted">Код: ${errorCode}</small>
          ${js.error.hint ? `<br><small class="text-info">${js.error.hint}</small>` : ''}
        </td></tr>`;
        return;
      }
      
      // Проверяем наличие данных в различных форматах
      let rows = collect(js);
      
      // Если не нашли через collect, пробуем прямые пути
      if (!rows.length) {
        if (js.data && Array.isArray(js.data)) {
          rows = js.data.map(normalize);
        } else if (js.events && Array.isArray(js.events)) {
          rows = js.events.map(normalize);
        } else if (Array.isArray(js)) {
          rows = js.map(normalize);
        }
      }
      
      if (!rows.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-muted text-center">События не найдены в ответе API</td></tr>';
        return;
      }
      
      body.innerHTML = rows.slice(0, 200).map((r, i) => `
        <tr>
          <td>${i + 1}</td>
          <td>${r.name || '—'}</td>
          <td><span class="badge bg-info">${r.type || '—'}</span></td>
          <td><code>${r.when || '—'}</code></td>
          <td>${r.extra || ''}</td>
        </tr>
      `).join('');
    } catch (e) {
      clearTimeout(timeoutId);
      console.error('Ошибка загрузки:', e);
      
      let errorMsg = 'Неизвестная ошибка';
      if (e.name === 'AbortError' || e.message.includes('aborted')) {
        errorMsg = 'Превышено время ожидания ответа от сервера (120 секунд). API проверяет множество комбинаций регионов и методов аутентификации. Попробуйте обновить страницу через несколько секунд.';
      } else if (e.message) {
        errorMsg = e.message;
      }
      
      body.innerHTML = `<tr><td colspan="5" class="text-danger text-center">
        <strong>Ошибка загрузки:</strong> ${errorMsg}<br>
        <small class="text-muted">Проверьте подключение к интернету и попробуйте обновить страницу</small>
      </td></tr>`;
      raw.textContent = 'Ошибка: ' + errorMsg;
    }
  }

  form.addEventListener('submit', ev => {
    ev.preventDefault();
    const q = Object.fromEntries(new FormData(form).entries());
    load(q);
  });

  load({ lat: form.lat.value, lon: form.lon.value, days: form.days.value });
});
</script>
@endsection



