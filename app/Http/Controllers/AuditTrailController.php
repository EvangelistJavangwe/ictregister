<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditTrail::query();

        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->input('username') . '%');
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('badge_status')) {
            $query->where('badge_status', $request->input('badge_status'));
        }

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // Only apply date range when values are valid dates and the range makes sense
        if ($dateFrom && strtotime($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo && strtotime($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $trails  = $query->latest('created_at')->paginate(25)->withQueryString();
        $modules = AuditTrail::distinct()->pluck('module')->sort()->values();

        return view('audit-trail.index', compact('trails', 'modules'));
    }
}
