<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class UserController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request) {
        $data['title'] = 'Users';
        $data['users'] = User::whereNull('role_id')->paginate(10);
        return view('admin.users.index', $data);
    }
}
