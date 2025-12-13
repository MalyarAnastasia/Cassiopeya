@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">NASA OSDR</h3>
    <div class="text-muted small">Источник: {{ $src }}</div>
  </div>

  {{-- Фильтры и поиск --}}
  <div class="card shadow-sm mb-4 animate-fade-in">
    <div class="card-body">
      <form id="filterForm" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Поиск по ключевым словам</label>
          <input type="text" class="form-control" id="searchInput" placeholder="Введите текст для поиска...">
        </div>
        <div class="col-md-3">
          <label class="form-label">Сортировка по столбцу</label>
          <select class="form-select" id="sortColumn">
            <option value="inserted_at" selected>Дата добавления</option>
            <option value="updated_at">Дата обновления</option>
            <option value="title">Название</option>
            <option value="status">Статус</option>
            <option value="dataset_id">ID набора данных</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Направление</label>
          <select class="form-select" id="sortDirection">
            <option value="desc" selected>По убыванию</option>
            <option value="asc">По возрастанию</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-primary w-100" id="applyFilters">Применить</button>
          <button type="button" class="btn btn-outline-secondary ms-2" id="resetFilters">Сбросить</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Таблица с данными --}}
  <div class="card shadow-sm animate-slide-up">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="osdrTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th data-sort="dataset_id">dataset_id</th>
              <th data-sort="title">title</th>
              <th data-sort="status">status</th>
              <th>REST_URL</th>
              <th data-sort="updated_at">updated_at</th>
              <th data-sort="inserted_at">inserted_at</th>
              <th>raw</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            @forelse($items as $row)
              <tr class="table-row" data-search-text="{{ strtolower(($row['title'] ?? '') . ' ' . ($row['dataset_id'] ?? '') . ' ' . ($row['status'] ?? '')) }}">
                <td>{{ $row['id'] }}</td>
                <td>{{ $row['dataset_id'] ?? '—' }}</td>
                <td style="max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                  {{ $row['title'] ?? '—' }}
                </td>
                <td><span class="badge bg-secondary">{{ $row['status'] ?? '—' }}</span></td>
                <td>
                  @if(!empty($row['rest_url']))
                    <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener" class="text-decoration-none">
                      <i class="bi bi-box-arrow-up-right"></i> открыть
                    </a>
                  @else
                    —
                  @endif
                </td>
                <td>{{ $row['updated_at'] ?? '—' }}</td>
                <td>{{ $row['inserted_at'] ?? '—' }}</td>
                <td>
                  <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" 
                          data-bs-target="#raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                    JSON
                  </button>
                </td>
              </tr>
              <tr class="collapse" id="raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                <td colspan="8">
                  <pre class="mb-0 bg-light p-3 rounded" style="max-height:260px;overflow:auto;font-size:0.85rem">
{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted">нет данных</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div id="noResults" class="text-center text-muted d-none mt-3">
        <p>По вашему запросу ничего не найдено</p>
      </div>
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

  .table-row {
    transition: background-color 0.2s ease;
  }

  .table-row:hover {
    background-color: #f8f9fa !important;
  }

  .table th[data-sort] {
    cursor: pointer;
    user-select: none;
    position: relative;
  }

  .table th[data-sort]:hover {
    background-color: #e9ecef;
  }

  .table th[data-sort]::after {
    content: ' ↕';
    opacity: 0.5;
    font-size: 0.8em;
  }

  .table th[data-sort].sorted-asc::after {
    content: ' ↑';
    opacity: 1;
  }

  .table th[data-sort].sorted-desc::after {
    content: ' ↓';
    opacity: 1;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tableBody = document.getElementById('tableBody');
  const searchInput = document.getElementById('searchInput');
  const sortColumn = document.getElementById('sortColumn');
  const sortDirection = document.getElementById('sortDirection');
  const applyBtn = document.getElementById('applyFilters');
  const resetBtn = document.getElementById('resetFilters');
  const noResults = document.getElementById('noResults');
  let allRows = Array.from(tableBody.querySelectorAll('.table-row'));
  let currentSort = { column: 'inserted_at', direction: 'desc' };

  // Поиск
  function filterRows() {
    const searchText = searchInput.value.toLowerCase().trim();
    let visibleCount = 0;

    allRows.forEach(row => {
      const searchData = row.getAttribute('data-search-text') || '';
      const matches = !searchText || searchData.includes(searchText);
      
      row.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
  }

  // Сортировка
  function sortRows() {
    const column = sortColumn.value;
    const direction = sortDirection.value;
    currentSort = { column, direction };

    allRows.sort((a, b) => {
      const aVal = getCellValue(a, column);
      const bVal = getCellValue(b, column);
      
      let comparison = 0;
      if (column === 'inserted_at' || column === 'updated_at') {
        comparison = new Date(aVal) - new Date(bVal);
      } else {
        comparison = String(aVal).localeCompare(String(bVal));
      }
      
      return direction === 'asc' ? comparison : -comparison;
    });

    // Обновляем DOM
    allRows.forEach(row => tableBody.appendChild(row));
    
    // Обновляем индикаторы сортировки
    document.querySelectorAll('th[data-sort]').forEach(th => {
      th.classList.remove('sorted-asc', 'sorted-desc');
      if (th.getAttribute('data-sort') === column) {
        th.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
      }
    });
  }

  function getCellValue(row, column) {
    const index = Array.from(row.parentElement.querySelector('thead th')).findIndex(
      th => th.getAttribute('data-sort') === column
    );
    if (index === -1) return '';
    const cell = row.cells[index];
    return cell ? cell.textContent.trim() : '';
  }

  // Сортировка по клику на заголовок
  document.querySelectorAll('th[data-sort]').forEach(th => {
    th.addEventListener('click', () => {
      const column = th.getAttribute('data-sort');
      sortColumn.value = column;
      if (currentSort.column === column) {
        sortDirection.value = currentSort.direction === 'asc' ? 'desc' : 'asc';
      }
      sortRows();
    });
  });

  // Обработчики событий
  searchInput.addEventListener('input', filterRows);
  applyBtn.addEventListener('click', () => {
    filterRows();
    sortRows();
  });
  resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    sortColumn.value = 'inserted_at';
    sortDirection.value = 'desc';
    filterRows();
    sortRows();
  });

  // Инициализация
  sortRows();
});
</script>
@endsection
