<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class Hmac {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if (!auth()->check()) {
            $shopify_parameter = json_decode(base64_decode($request->route()->parameters()['slug']));
            if ($shopify_parameter) {
                $params = array();
                foreach ($shopify_parameter as $param => $value) {
                    if ($param != 'signature' && $param != 'hmac') {
                        $params[$param] = "{$param}={$value}";
                    }
                }
                asort($params);
                $params = implode('&', $params);
                $hmac = $shopify_parameter->hmac;
                $calculatedHmac = hash_hmac('sha256', $params, env('SHOPIFY_APP_SECRET'));

                if ($hmac == $calculatedHmac) {
                    $shop_url = $shopify_parameter->shop;
                    $user = User::Where('shop_url', $shop_url)->first();
                    auth()->login($user);
                    return redirect()->route('load', $request->route()->parameters()['slug']);
                    return $next($request);
                }
                return redirect()->to('/404');
            }
            return redirect()->to('/404');
        } else {
            $shopify_parameter = json_decode(base64_decode($request->route()->parameters()['slug']));
            if (auth()->user()->shop_url != $shopify_parameter->shop) {
                auth()->logout();
                return redirect()->route('authenticate', $request->route()->parameters()['slug']);
            }
        }
        return $next($request);
    }

}
