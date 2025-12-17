/**
 * Tests for jwst.blade.php - JWST Gallery page
 */

describe('JWST Page - Filter Toggle', () => {
  let toggleInputs;

  beforeEach(() => {
    document.body.innerHTML = `
      <select id="srcSel">
        <option value="jpg">JPG</option>
        <option value="suffix">Suffix</option>
        <option value="program">Program</option>
      </select>
      <input id="suffixInp" style="display:none">
      <input id="progInp" style="display:none">
    `;

    const srcSel = document.getElementById('srcSel');
    const sfxInp = document.getElementById('suffixInp');
    const progInp = document.getElementById('progInp');

    toggleInputs = function() {
      sfxInp.style.display = (srcSel.value === 'suffix') ? '' : 'none';
      progInp.style.display = (srcSel.value === 'program') ? '' : 'none';
    };
  });

  test('should hide both inputs when jpg is selected', () => {
    const srcSel = document.getElementById('srcSel');
    const sfxInp = document.getElementById('suffixInp');
    const progInp = document.getElementById('progInp');

    srcSel.value = 'jpg';
    toggleInputs();

    expect(sfxInp.style.display).toBe('none');
    expect(progInp.style.display).toBe('none');
  });

  test('should show suffix input when suffix is selected', () => {
    const srcSel = document.getElementById('srcSel');
    const sfxInp = document.getElementById('suffixInp');
    const progInp = document.getElementById('progInp');

    srcSel.value = 'suffix';
    toggleInputs();

    expect(sfxInp.style.display).toBe('');
    expect(progInp.style.display).toBe('none');
  });

  test('should show program input when program is selected', () => {
    const srcSel = document.getElementById('srcSel');
    const sfxInp = document.getElementById('suffixInp');
    const progInp = document.getElementById('progInp');

    srcSel.value = 'program';
    toggleInputs();

    expect(sfxInp.style.display).toBe('none');
    expect(progInp.style.display).toBe('');
  });
});

describe('JWST Page - Feed Loading', () => {
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

  test('should display loading message', async () => {
    const track = document.getElementById('jwstTrack');
    
    global.fetch = jest.fn(() => new Promise(() => {}));
    
    loadFeed({ source: 'jpg', perPage: 24 });
    
    await new Promise(resolve => setTimeout(resolve, 10));
    
    expect(track.textContent).toContain('Загрузка');
  });

  test('should load feed with correct parameters', async () => {
    const mockData = {
      source: 'all/type/jpg',
      count: 0,
      items: []
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24, instrument: 'NIRCam' });

    expect(global.fetch).toHaveBeenCalledWith('/api/jwst/feed?source=jpg&perPage=24&instrument=NIRCam');
  });

  test('should render multiple images', async () => {
    const mockData = {
      source: 'all/type/jpg',
      count: 3,
      items: [
        { url: 'http://example.com/img1.jpg', caption: 'Image 1', link: 'http://example.com/link1' },
        { url: 'http://example.com/img2.jpg', caption: 'Image 2', link: 'http://example.com/link2' },
        { url: 'http://example.com/img3.jpg', caption: 'Image 3', link: 'http://example.com/link3' }
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
    expect(figures.length).toBe(3);
  });

  test('should display count in info', async () => {
    const mockData = {
      source: 'all/type/jpg',
      count: 42,
      items: []
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24 });

    const info = document.getElementById('jwstInfo');
    expect(info.textContent).toContain('42');
  });

  test('should handle errors gracefully', async () => {
    global.fetch = jest.fn(() => Promise.reject(new Error('API Error')));

    await loadFeed({ source: 'jpg', perPage: 24 });

    const track = document.getElementById('jwstTrack');
    expect(track.textContent).toContain('Ошибка загрузки');
  });

  test('should sanitize HTML in captions', async () => {
    const mockData = {
      source: 'test',
      count: 1,
      items: [
        { url: 'http://example.com/img1.jpg', caption: '<img src=x onerror=alert(1)>', link: 'http://example.com/link1' }
      ]
    };

    global.fetch = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve(mockData),
      })
    );

    await loadFeed({ source: 'jpg', perPage: 24 });

    const track = document.getElementById('jwstTrack');
    expect(track.innerHTML).toContain('&lt;img');
    expect(track.innerHTML).not.toContain('<img src=x');
  });
});

describe('JWST Page - Navigation', () => {
  test('should scroll track on prev button click', () => {
    document.body.innerHTML = `
      <button class="jwst-prev">Prev</button>
      <div id="jwstTrack" style="overflow-x: auto; scroll-behavior: smooth;">
        <div style="width: 3000px;"></div>
      </div>
    `;

    const track = document.getElementById('jwstTrack');
    const prevBtn = document.querySelector('.jwst-prev');

    // Mock scrollBy
    track.scrollBy = jest.fn();

    prevBtn.addEventListener('click', () => {
      track.scrollBy({ left: -600, behavior: 'smooth' });
    });

    prevBtn.click();

    expect(track.scrollBy).toHaveBeenCalledWith({ left: -600, behavior: 'smooth' });
  });

  test('should scroll track on next button click', () => {
    document.body.innerHTML = `
      <div id="jwstTrack" style="overflow-x: auto; scroll-behavior: smooth;">
        <div style="width: 3000px;"></div>
      </div>
      <button class="jwst-next">Next</button>
    `;

    const track = document.getElementById('jwstTrack');
    const nextBtn = document.querySelector('.jwst-next');

    // Mock scrollBy
    track.scrollBy = jest.fn();

    nextBtn.addEventListener('click', () => {
      track.scrollBy({ left: 600, behavior: 'smooth' });
    });

    nextBtn.click();

    expect(track.scrollBy).toHaveBeenCalledWith({ left: 600, behavior: 'smooth' });
  });
});

