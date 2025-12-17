@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-4">JWST — Галерея изображений</h3>

  <div class="card shadow-sm mb-4 animate-fade-in">
    <div class="card-body">
      <form id="jwstFilter" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Источник</label>
          <select class="form-select" name="source" id="srcSel">
            <option value="jpg" selected>Все JPG</option>
            <option value="suffix">По суффиксу</option>
            <option value="program">По программе</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Суффикс / Программа</label>
          <input type="text" class="form-control" name="suffix" id="suffixInp" placeholder="_cal / _thumb" style="display:none">
          <input type="text" class="form-control" name="program" id="progInp" placeholder="2734" style="display:none">
        </div>
        <div class="col-md-2">
          <label class="form-label">Инструмент</label>
          <select class="form-select" name="instrument">
            <option value="">Любой</option>
            <option>NIRCam</option><option>MIRI</option><option>NIRISS</option><option>NIRSpec</option><option>FGS</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">На странице</label>
          <select class="form-select" name="perPage">
            <option>12</option><option selected>24</option><option>36</option><option>48</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit">Показать</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm animate-slide-up">
    <div class="card-body">
      <div class="jwst-slider position-relative">
        <button class="btn btn-light border jwst-nav jwst-prev position-absolute top-50 start-0 translate-middle-y" type="button" aria-label="Prev">‹</button>
        <div id="jwstTrack" class="jwst-track border rounded p-2"></div>
        <button class="btn btn-light border jwst-nav jwst-next position-absolute top-50 end-0 translate-middle-y" type="button" aria-label="Next">›</button>
      </div>
      <div id="jwstInfo" class="small text-muted mt-3 text-center"></div>
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

  .jwst-slider {
    position: relative;
  }
  
  .jwst-track {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    padding: 0.5rem;
    scrollbar-width: thin;
  }
  
  .jwst-item {
    flex: 0 0 200px;
    scroll-snap-align: start;
    transition: transform 0.3s ease;
  }
  
  .jwst-item:hover {
    transform: scale(1.05);
  }
  
  .jwst-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 0.5rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  
  .jwst-cap {
    font-size: 0.85rem;
    margin-top: 0.5rem;
    text-align: center;
  }
  
  .jwst-nav {
    z-index: 2;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.5rem;
    line-height: 1;
  }
  
  .jwst-prev {
    left: -20px;
  }
  
  .jwst-next {
    right: -20px;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const track = document.getElementById('jwstTrack');
  const info = document.getElementById('jwstInfo');
  const form = document.getElementById('jwstFilter');
  const srcSel = document.getElementById('srcSel');
  const sfxInp = document.getElementById('suffixInp');
  const progInp = document.getElementById('progInp');

  function toggleInputs() {
    sfxInp.style.display = (srcSel.value === 'suffix') ? '' : 'none';
    progInp.style.display = (srcSel.value === 'program') ? '' : 'none';
  }
  srcSel.addEventListener('change', toggleInputs);
  toggleInputs();

  async function loadFeed(qs) {
    track.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border" role="status"></div><div class="mt-2">Загрузка...</div></div>';
    info.textContent = '';
    try {
      const url = '/api/jwst/feed?' + new URLSearchParams(qs).toString();
      const r = await fetch(url);
      const js = await r.json();
      track.innerHTML = '';
      (js.items || []).forEach(it => {
        const fig = document.createElement('figure');
        fig.className = 'jwst-item m-0';
        fig.innerHTML = `
          <a href="${it.link || it.url}" target="_blank" rel="noreferrer">
            <img loading="lazy" src="${it.url}" alt="JWST" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22%3E%3C/svg%3E'">
          </a>
          <figcaption class="jwst-cap">${(it.caption || '').replaceAll('<', '&lt;')}</figcaption>`;
        track.appendChild(fig);
      });
      info.textContent = `Источник: ${js.source} · Показано ${js.count || 0}`;
    } catch (e) {
      track.innerHTML = '<div class="p-4 text-danger text-center">Ошибка загрузки</div>';
    }
  }

  form.addEventListener('submit', function(ev) {
    ev.preventDefault();
    const fd = new FormData(form);
    const q = Object.fromEntries(fd.entries());
    loadFeed(q);
  });

  document.querySelector('.jwst-prev').addEventListener('click', () => 
    track.scrollBy({ left: -600, behavior: 'smooth' }));
  document.querySelector('.jwst-next').addEventListener('click', () => 
    track.scrollBy({ left: 600, behavior: 'smooth' }));

  loadFeed({ source: 'jpg', perPage: 24 });
});
</script>
@endsection






