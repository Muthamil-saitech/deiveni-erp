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
        $period = in_array($request->input('period'), ['today', 'this_week', 'this_month', 'this_year', 'custom'])
            ? $request->input('period')
            : 'today';

        $customDates = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $data = $this->dashboardService->getDashboardData($request->user(), $period, $customDates);

        return view('dashboard', $data);
    }
}
