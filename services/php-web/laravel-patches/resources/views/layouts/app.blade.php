<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Кассиопея - Космические данные</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    #map{height:340px}
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
    }
    .navbar {
      background: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .container {
      background: rgba(255, 255, 255, 0.98);
      border-radius: 15px;
      padding: 2rem;
      margin-top: 1rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .card {
      border: none;
      border-radius: 10px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.15) !important;
    }
  </style>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
      <i class="bi bi-stars"></i> Кассиопея
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-bold' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('iss') ? 'active fw-bold' : '' }}" href="{{ route('iss') }}">
            <i class="bi bi-globe"></i> ISS
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('osdr') ? 'active fw-bold' : '' }}" href="{{ route('osdr') }}">
            <i class="bi bi-database"></i> OSDR
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('jwst') ? 'active fw-bold' : '' }}" href="{{ route('jwst') }}">
            <i class="bi bi-camera"></i> JWST
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('astro') ? 'active fw-bold' : '' }}" href="{{ route('astro') }}">
            <i class="bi bi-moon-stars"></i> Astro
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('telemetry') ? 'active fw-bold' : '' }}" href="{{ route('telemetry') }}">
            <i class="bi bi-file-earmark-spreadsheet"></i> Telemetry
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
@yield('content')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
