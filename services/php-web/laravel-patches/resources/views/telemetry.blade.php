@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-4">Телеметрия Legacy (CSV/XLSX)</h3>

  <div class="card shadow-sm mb-4 animate-fade-in">
    <div class="card-body">
      <form method="GET" action="{{ route('telemetry') }}" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Поиск</label>
          <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="ID сенсора, файл...">
        </div>
        <div class="col-md-3">
          <label class="form-label">Дата от</label>
          <input type="datetime-local" class="form-control" name="date_from" value="{{ $date_from }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Дата до</label>
          <input type="datetime-local" class="form-control" name="date_to" value="{{ $date_to }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Применить</button>
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
              <th>
                <a href="?{{ http_build_query(array_merge(request()->all(), ['sort' => 'id', 'direction' => $sort === 'id' && $direction === 'asc' ? 'desc' : 'asc'])) }}" 
                   class="text-decoration-none">
                  ID
                  @if($sort === 'id')
                    <i class="bi bi-arrow-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>
                  @endif
                </a>
              </th>
              <th>
                <a href="?{{ http_build_query(array_merge(request()->all(), ['sort' => 'recorded_at', 'direction' => $sort === 'recorded_at' && $direction === 'asc' ? 'desc' : 'asc'])) }}" 
                   class="text-decoration-none">
                  Время записи
                  @if($sort === 'recorded_at')
                    <i class="bi bi-arrow-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>
                  @endif
                </a>
              </th>
              <th>
                <a href="?{{ http_build_query(array_merge(request()->all(), ['sort' => 'voltage', 'direction' => $sort === 'voltage' && $direction === 'asc' ? 'desc' : 'asc'])) }}" 
                   class="text-decoration-none">
                  Напряжение
                  @if($sort === 'voltage')
                    <i class="bi bi-arrow-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>
                  @endif
                </a>
              </th>
              <th>
                <a href="?{{ http_build_query(array_merge(request()->all(), ['sort' => 'temp', 'direction' => $sort === 'temp' && $direction === 'asc' ? 'desc' : 'asc'])) }}" 
                   class="text-decoration-none">
                  Температура
                  @if($sort === 'temp')
                    <i class="bi bi-arrow-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>
                  @endif
                </a>
              </th>
              <th>Активен</th>
              <th>ID сенсора</th>
              <th>Файл источника</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $item)
              <tr>
                <td>{{ $item->id }}</td>
                <td>{{ \Carbon\Carbon::parse($item->recorded_at)->format('Y-m-d H:i:s') }}</td>
                <td>{{ number_format($item->voltage, 2) }} V</td>
                <td>{{ number_format($item->temp, 2) }} °C</td>
                <td>
                  <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                    {{ $item->is_active ? 'ИСТИНА' : 'ЛОЖЬ' }}
                  </span>
                </td>
                <td><code>{{ $item->sensor_id ?? '—' }}</code></td>
                <td class="small">{{ $item->source_file }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">Нет данных</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      
      <div class="mt-3">
        {{ $items->links() }}
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
</style>
@endsection



