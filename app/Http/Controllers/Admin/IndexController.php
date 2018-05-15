<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class IndexController extends Controller {

    public function index() {
        $data['title'] = 'My Dashboard';
        $data['users'] = User::whereNull('role_id')->get()->count();
        return view('admin.index', $data);
    }

    public function customerLogin(Request $request, $id) {
        $user = User::find($id);
        auth()->login($user);
        return redirect()->to('/dashboard');
    }

}
