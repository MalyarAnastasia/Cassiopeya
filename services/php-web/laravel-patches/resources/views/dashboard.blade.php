@extends('layouts.app')

@section('content')
<div class="container pb-5">
  {{-- верхние карточки --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm metric-card animate-fade-in text-center">
        <div class="card-body">
          <div class="small text-muted mb-2"><i class="bi bi-speedometer2"></i> Скорость МКС</div>
          <div class="fs-3 fw-bold text-primary">{{ isset(($iss['payload'] ?? [])['velocity']) ? number_format($iss['payload']['velocity'],0,'',' ') : '—' }} <small class="fs-6">км/ч</small></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm metric-card animate-fade-in text-center">
        <div class="card-body">
          <div class="small text-muted mb-2"><i class="bi bi-arrow-up"></i> Высота МКС</div>
          <div class="fs-3 fw-bold text-success">{{ isset(($iss['payload'] ?? [])['altitude']) ? number_format($iss['payload']['altitude'],0,'',' ') : '—' }} <small class="fs-6">км</small></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    {{-- левая колонка: JWST наблюдение --}}
    <div class="col-lg-7">
      <div class="card shadow-sm h-100 animate-slide-up">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-camera"></i> JWST — выбранное наблюдение</h5>
          <div class="text-muted">Основная галерея доступна на <a href="{{ route('jwst') }}">отдельной странице</a>.</div>
        </div>
      </div>
    </div>

    {{-- правая колонка: карта МКС --}}
    <div class="col-lg-5">
      <div class="card shadow-sm h-100 animate-slide-up">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-globe"></i> МКС — положение и движение</h5>
          <div id="map" class="rounded mb-2 border" style="height:300px"></div>
          <div class="row g-2">
            <div class="col-6"><canvas id="issSpeedChart" height="110"></canvas></div>
            <div class="col-6"><canvas id="issAltChart"   height="110"></canvas></div>
          </div>
          <div class="mt-2 text-center">
            <a href="{{ route('iss') }}" class="btn btn-sm btn-outline-primary">Подробнее о ISS →</a>
          </div>
        </div>
      </div>
    </div>

    {{-- НИЖНЯЯ ПОЛОСА: НОВАЯ ГАЛЕРЕЯ JWST --}}
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title m-0">JWST — последние изображения</h5>
            <form id="jwstFilter" class="row g-2 align-items-center">
              <div class="col-auto">
                <select class="form-select form-select-sm" name="source" id="srcSel">
                  <option value="jpg" selected>Все JPG</option>
                  <option value="suffix">По суффиксу</option>
                  <option value="program">По программе</option>
                </select>
              </div>
              <div class="col-auto">
                <input type="text" class="form-control form-control-sm" name="suffix" id="suffixInp" placeholder="_cal / _thumb" style="width:140px;display:none">
                <input type="text" class="form-control form-control-sm" name="program" id="progInp" placeholder="2734" style="width:110px;display:none">
              </div>
              <div class="col-auto">
                <select class="form-select form-select-sm" name="instrument" style="width:130px">
                  <option value="">Любой инструмент</option>
                  <option>NIRCam</option><option>MIRI</option><option>NIRISS</option><option>NIRSpec</option><option>FGS</option>
                </select>
              </div>
              <div class="col-auto">
                <select class="form-select form-select-sm" name="perPage" style="width:90px">
                  <option>12</option><option selected>24</option><option>36</option><option>48</option>
                </select>
              </div>
              <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit">Показать</button>
              </div>
            </form>
          </div>

          <style>
            .jwst-slider{position:relative}
            .jwst-track{
              display:flex; gap:.75rem; overflow:auto; scroll-snap-type:x mandatory; padding:.25rem;
            }
            .jwst-item{flex:0 0 180px; scroll-snap-align:start}
            .jwst-item img{width:100%; height:180px; object-fit:cover; border-radius:.5rem}
            .jwst-cap{font-size:.85rem; margin-top:.25rem}
            .jwst-nav{position:absolute; top:40%; transform:translateY(-50%); z-index:2}
            .jwst-prev{left:-.25rem} .jwst-next{right:-.25rem}
          </style>

          <div class="jwst-slider">
            <button class="btn btn-light border jwst-nav jwst-prev" type="button" aria-label="Prev">‹</button>
            <div id="jwstTrack" class="jwst-track border rounded"></div>
            <button class="btn btn-light border jwst-nav jwst-next" type="button" aria-label="Next">›</button>
          </div>

          <div id="jwstInfo" class="small text-muted mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  // ====== карта и графики МКС (как раньше) ======
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const last = @json(($iss['payload'] ?? []));
    let lat0 = Number(last.latitude || 0), lon0 = Number(last.longitude || 0);
    const map = L.map('map', { attributionControl:false }).setView([lat0||0, lon0||0], lat0?3:2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', { noWrap:true }).addTo(map);
    const trail  = L.polyline([], {weight:3}).addTo(map);
    const marker = L.marker([lat0||0, lon0||0]).addTo(map).bindPopup('МКС');

    const speedChart = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Скорость', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });
    const altChart = new Chart(document.getElementById('issAltChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Высота', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });

    async function loadTrend() {
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
    }
    loadTrend();
    setInterval(loadTrend, 15000);
  }

  // ====== JWST ГАЛЕРЕЯ ======
  const track = document.getElementById('jwstTrack');
  const info  = document.getElementById('jwstInfo');
  const form  = document.getElementById('jwstFilter');
  const srcSel = document.getElementById('srcSel');
  const sfxInp = document.getElementById('suffixInp');
  const progInp= document.getElementById('progInp');

  function toggleInputs(){
    sfxInp.style.display  = (srcSel.value==='suffix')  ? '' : 'none';
    progInp.style.display = (srcSel.value==='program') ? '' : 'none';
  }
  srcSel.addEventListener('change', toggleInputs); toggleInputs();

  async function loadFeed(qs){
    track.innerHTML = '<div class="p-3 text-muted">Загрузка…</div>';
    info.textContent= '';
    try{
      const url = '/api/jwst/feed?'+new URLSearchParams(qs).toString();
      const r = await fetch(url);
      const js = await r.json();
      track.innerHTML = '';
      (js.items||[]).forEach(it=>{
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
    }catch(e){
      track.innerHTML = '<div class="p-3 text-danger">Ошибка загрузки</div>';
    }
  }

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    const fd = new FormData(form);
    const q = Object.fromEntries(fd.entries());
    loadFeed(q);
  });

  // навигация
  document.querySelector('.jwst-prev').addEventListener('click', ()=> track.scrollBy({left:-600, behavior:'smooth'}));
  document.querySelector('.jwst-next').addEventListener('click', ()=> track.scrollBy({left: 600, behavior:'smooth'}));

  // стартовые данные
  loadFeed({source:'jpg', perPage:24});
});
</script>
@endsection

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

  .metric-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.L && window._issMapTileLayer) {
    const map  = window._issMap;
    let   tl   = window._issMapTileLayer;
    tl.on('tileerror', () => {
      try {
        map.removeLayer(tl);
      } catch(e) {}
      tl = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: ''});
      tl.addTo(map);
      window._issMapTileLayer = tl;
    });
  }
});
</script>
