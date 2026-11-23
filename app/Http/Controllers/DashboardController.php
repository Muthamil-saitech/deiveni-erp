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
        $data = $this->dashboardService->getDashboardData($request->user());

        return view('dashboard', $data);
    }
}
