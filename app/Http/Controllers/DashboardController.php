<?php

namespace App\Http\Controllers;

use App\Services\ExcelInsight\AnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AnalyticsService $analyticsService): View
    {
        return view('dashboard.index', $analyticsService->dashboardData());
    }
}
