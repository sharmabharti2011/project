<?php

namespace App\Http\Middleware;

use Closure;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role_id != 1) {
            return redirect()->to('/');
        } else if (!auth()->check()) {
            $data['title'] = 'Adin Login';
            return response()->view('admin.login',$data);
        }
        return $next($request);
    }
}
