<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        Artisan::call('optimize:clear');
        return view('dashboard.dashboard');
    }
    
    public function overview(Request $request)
    {
        return view('dashboard.overview');
    }
}
