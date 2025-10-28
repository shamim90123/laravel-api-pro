<?php

// app/Http/Controllers/Api/V1/DashboardController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Spatie permission gates per action
        $this->middleware('permission:dashboard.view')->only(['overview']);
    }

    public function overview(): JsonResponse
    {
        $totalLeads    = Lead::count();
        $totalUsers    = User::count();
        $totalProducts = Product::count();

        // Core new KPIs
        $productsUsed = DB::table('lead_products')->distinct('product_id')->count('product_id');
        $leadsWithProducts = DB::table('lead_products')->distinct('lead_id')->count('lead_id');

        // Optional (cheap) extras
        $totalLeadProductRows = DB::table('lead_products')->count();
        $avgProductsPerLeadAll   = $totalLeads > 0 ? round($totalLeadProductRows / $totalLeads, 2) : 0.0;
        $avgProductsPerActiveLead = $leadsWithProducts > 0 ? round($totalLeadProductRows / $leadsWithProducts, 2) : 0.0;

        // Optional: Top products by # of leads using them (show in a small list)
        $topProducts = DB::table('lead_products as lp')
            ->join('products as p', 'p.id', '=', 'lp.product_id')
            ->select('lp.product_id', 'p.name', DB::raw('COUNT(DISTINCT lp.lead_id) as lead_count'))
            ->groupBy('lp.product_id', 'p.name')
            ->orderByDesc('lead_count')
            ->limit(5)
            ->get();

        // Optional: Stage breakdown
        $stageBreakdown = DB::table('lead_products as lp')
            ->join('sale_stages as s', 's.id', '=', 'lp.stage_id')
            ->select('s.id', 's.name', DB::raw('COUNT(*) as items'))
            ->groupBy('s.id', 's.name')
            ->orderByDesc('items')
            ->get();

        return response()->json([
            'leads'                         => $totalLeads,
            'users'                         => $totalUsers,
            'products'                      => $totalProducts,
            'products_used'                 => $productsUsed,         // <-- NEW
            'leads_with_products'           => $leadsWithProducts,    // <-- NEW
            'avg_products_per_lead_all'     => $avgProductsPerLeadAll,
            'avg_products_per_active_lead'  => $avgProductsPerActiveLead,
            'top_products'                  => $topProducts,          // [{product_id,name,lead_count}]
            'stage_breakdown'               => $stageBreakdown,       // [{id,name,items}]
        ]);
    }
}
