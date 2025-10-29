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
}

