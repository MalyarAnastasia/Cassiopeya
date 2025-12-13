<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelemetryController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('telemetry_legacy');
        
        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sensor_id', 'like', "%{$search}%")
                  ->orWhere('source_file', 'like', "%{$search}%");
            });
        }
        
        // Фильтрация по дате
        if ($request->has('date_from')) {
            $query->where('recorded_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('recorded_at', '<=', $request->date_to);
        }
        
        // Сортировка
        $sortColumn = $request->get('sort', 'recorded_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);
        
        $items = $query->paginate(50);
        
        return view('telemetry', [
            'items' => $items,
            'search' => $request->search,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'sort' => $sortColumn,
            'direction' => $sortDirection,
        ]);
    }
}



