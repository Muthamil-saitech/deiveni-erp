<?php

namespace App\Http\Controllers;

use App\Services\Contracts\DashboardServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        public DashboardServiceInterface $dashboardService
    ) {}

    /**
     * Display the dynamic dashboard overview.
     */
    public function index(Request $request): View
    {
        $period = in_array($request->input('period'), ['today', 'this_week', 'this_month', 'this_year'])
            ? $request->input('period')
            : 'today';

        $data = $this->dashboardService->getDashboardData($request->user(), $period);

        return view('dashboard', $data);
    }
}
