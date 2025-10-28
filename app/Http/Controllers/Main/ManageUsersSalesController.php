<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sale;
use Illuminate\Http\Request;

class ManageUsersSalesController extends Controller
{
    public function index(Request $request)
    {
        // Pagination dengan parameter berbeda untuk users dan sales
        $users = User::orderBy('created_at', 'desc')->paginate(10, ['*'], 'users_page');
        $sales = Sale::orderBy('created_at', 'desc')->paginate(10, ['*'], 'sales_page');

        return view('pages.owner.manage-data.users-sales', compact(
            'users',
            'sales'
        ));
    }

    public function fetchUsers(Request $request)
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10, ['*'], 'users_page');
        
        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    public function fetchSales(Request $request)
    {
        $sales = Sale::orderBy('created_at', 'desc')->paginate(10, ['*'], 'sales_page');
        
        return response()->json([
            'data' => $sales->items(),
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
            ],
        ]);
    }
}
