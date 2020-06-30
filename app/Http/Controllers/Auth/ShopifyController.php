<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Recurring;
use App\Webhook;
use App;
use Log;
use Carbon\Carbon;

class ShopifyController extends Controller {

    public function index(Request $request, $slug) {
        $data['slug'] = $slug;
        return view('index', $data);
    }

    public function load(Request $request, $slug) {
        $shopify_parameter = json_decode(base64_decode($request->route()->parameters()['slug']));
        $data['slug'] = $slug;
        if (!auth()->check()) {
            $data['redirect_url'] = route('authenticate', $slug);
        } else {
            $data['redirect_url'] = 'https' . '://' . $shopify_parameter->shop . '/' . 'admin/apps/' . env('SHOPIFY_APP_NAME');
        }
        return view('load', $data);
    }

    public function installShop(Request $request) {
        $shopUrl = $request->get('shop');
        if (!$shopUrl) {
            return 404;
        }
        $user = User::Where('shop_url', $shopUrl)->first();

        if ($user) {
            if(!$request->get('hmac')){ /// this is used if someone tring to install and access app outside shopify app link.If app already installed in his store then we redirect to the shopify app area and if he is not logged in then shopify take him to the login screen
               $redirect_url = 'https' . '://' . $shopUrl . '/' . 'admin/apps/' . env('SHOPIFY_APP_NAME');
               return redirect()->to($redirect_url);
            }
//            if ($request->get('charge_id') != null) {
//                $user = $this->activatePlan($user);
//            }
            $slug = base64_encode(json_encode($request->all()));
            return redirect()->route('authenticate', $slug);
        }
        return $this->doAuth($shopUrl);
    }

    public function doAuth($shopUrl) {
        $scope = explode(',', env('SHOPIFY_APP_PERMISSIONS'));
        $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $shopUrl, 'ACCESS_TOKEN' => '']);
        $permission_url = $shopify->installURL([
            'permissions' => $scope,
            'redirect' => env('SHOPIFY_APP_REDIRECT_URL')
        ]);
        return redirect()->to($permission_url);
    }

    public function registerUninstallWebHook($user) {

        $sh = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);

        $webhook = $sh->call(['URL' => 'webhooks.json', 'METHOD' => 'POST', "DATA" => ["webhook" => array("topic" => "app/uninstalled", "address" => route('webhook.uninstalled'), "format" => "json")]]);
        $insert_array = array(
            'name' => "app/uninstalled",
            'webhook_id' => $webhook->webhook->id
        );

        $insert_array['webhook'] = json_encode($insert_array);
        $insert_array['user_id'] = $user->id;
        $insert_array['created_at'] = date('Y-m-d H:i:s');
        $insert_array['updated_at'] = date('Y-m-d H:i:s');

//        dd($insert_array);

        Webhook::create($insert_array);
        return true;
    }

    public function processOAuthResultRedirect(Request $request) {
        $code = $request->get('code');
        $shopUrl = $request->get('shop');

        $redirect_url = 'https' . '://' . $shopUrl . '/' . 'admin/apps/' . env('SHOPIFY_APP_NAME');

        $sh = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $shopUrl]);

        try {
            $verify = $sh->verifyRequest($request->all());
            if ($verify) {
                $accessToken = $sh->getAccessToken($code);
            } else {
                Log::info('Error: invalid request');
                echo 'Error: invalid request';
                die;
            }
        } catch (\Exception $e) {
            Log::info('Error:' . $e->getMessage());
            echo $e->getMessage();
            die;
        }
        $sh = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $shopUrl, 'ACCESS_TOKEN' => $accessToken]);

        $shopinfo = $sh->call(['URL' => 'shop.json', 'METHOD' => 'GET']);
        
        $check_user = User::where(['shop_url' => $shopUrl])->first();
        if($check_user){
           $check_user->delete();  ////  This is used if entries is not deleted on uninstalled app
        }
        $user = User::firstOrNew(['access_token' => $accessToken]);

        $user->name = $shopinfo->shop->shop_owner;
        $user->email = $shopinfo->shop->email;
        $user->shop_name = $shopinfo->shop->name;
        $user->shop_url = $shopUrl;
        $user->status = 1;
        $user->access_token = $accessToken;
        $user->created_at = Carbon::now();
        $user->updated_at = Carbon::now();


        if (!$user->exists) {
            $user->save();
//            try {
//                $recurring = $sh->call(['URL' => 'recurring_application_charges.json', 'METHOD' => 'POST', "DATA" => ["recurring_application_charge" => array("name" => "Free", "price" => 0.00, "return_url" => $redirect_url, "capped_amount" => "100", "terms" => "free plan $0.00")]]);
//                Recurring::create(array('user_id' => $user->id, 'recurring_id' => $recurring->recurring_application_charge->id, 'plan' => 'free', 'status' => 'pending'));
//            } catch (\Exception $e) {
//                Log::info('Recurring not created: ' . $e->getMessage());
//                return redirect()->to($redirect_url);
//            }
//            $return_url = $recurring->recurring_application_charge->confirmation_url;
//            return View('admin.redirect', compact('return_url'));
            return redirect()->to($redirect_url);
        } else {
            return redirect()->to($redirect_url);
        }
    }

    public function upgradeDowngrade($user) {
        $redirect_url = 'https' . '://' . $shopUrl . '/' . 'admin/apps/' . env('SHOPIFY_APP_NAME');
        $recurring = $sh->call(['URL' => 'recurring_application_charges.json', 'METHOD' => 'POST', "DATA" => ["recurring_application_charge" => array("name" => "Free", "price" => 0.00, "return_url" => $redirect_url)]]);

        Recurring::create(array('user_id' => $user->id, 'recurring_id' => $recurring->recurring_application_charge->id, 'plan' => 'free', 'status' => 'pending'));
        //$return_url = $recurring->recurring_application_charge->confirmation_url;
        return View('fb-feed.admin.redirect', compact('return_url'));
    }

    public function activatePlan($user) {
        $sh = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);

        if ($user_recurring = Recurring::where('user_id', '=', $user->id)->first()) {
            $recurrings = $sh->call(['URL' => 'recurring_application_charges/' . $user_recurring->recurring_id . '.json', 'METHOD' => 'GET']);
            
            if ($recurrings->recurring_application_charge->status == "accepted") {
                $user_recurring->status = 'active';
                $recurring = $sh->call(['URL' => 'recurring_application_charges/' . $recurrings->recurring_application_charge->id . '/activate.json', 'METHOD' => 'POST']);
            } elseif ($recurrings->recurring_application_charge->status == "declined") {
                $user->status = 'declined';
            }
            $user_recurring->save();
        }
        return $user;
    }

    public function handleAppUninstallation(Request $request) {
        $shopUrl = $request->get('domain');
        Log::info('Uninstall:' . $shopUrl);
        $user = User::where(['shop_url' => $shopUrl])->first();
        $user->delete();
    }

    public function getWebhooks(Request $request, $id) {
        $user = User::find($id);

        $sh = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);

        $webhookinfo = $sh->call(['URL' => 'webhooks.json', 'METHOD' => 'GET']);
        dd($webhookinfo);
    }

    public function storeAuthenticate(Request $request, $slug) {
        $shopify_parameter = json_decode(base64_decode($slug));
        $user = User::Where('shop_url', $shopify_parameter->shop)->first();

        if (!$user->get_webhook)
            $this->registerUninstallWebHook($user);

        if (isset($shopify_parameter->model) && $shopify_parameter->model == 'order_details') {
            return redirect()->route('warehouse.order.details', $slug);
        }
        return redirect()->route('dashboard', $slug);
    }

    public function shopRedact(Request $request) {
        $shopUrl = $request->get('shop_domain');
        Log::info('Uninstall2:' . $shopUrl);
        if ($user = User::where(['shop_url' => $shopUrl])->first()) {
            $user->delete();
        }
        return response()->json(['success' => true], 200);
    }

}
