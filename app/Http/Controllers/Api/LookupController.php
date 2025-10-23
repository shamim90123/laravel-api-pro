<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LookupController extends Controller
{
    public function countries()
    {
        $countries = DB::table('countries')->get();
        return response()->json(['message' => 'Countries retrieved successfully.', 'data' => $countries]);
    }
}
