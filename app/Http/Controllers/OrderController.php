<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use SoapClient;
use SoapFault;
use App\User;
use App\DeveloperSetting;
use App;

class OrderController extends Controller {

    protected $_client = null;

    /**
     * WarehouseSpace_Warehouse_Model_Api constructor.
     */
    public function __construct() {
        $debug = true;
        $wsdl = env('WSDL_URL');
        try {
            $this->_client = new SoapClient($wsdl, array(
                'connection_timeout' => 5000,
                'cache_wsdl' => $debug ? WSDL_CACHE_NONE : WSDL_CACHE_MEMORY,
                'trace' => true,
                'exceptions' => true,
                'soap_version' => SOAP_1_1
                    )
            );
        } catch (SoapFault $fault) {
            Log::info('Soap client error: ' . $fault->getMessage());
        }
    }

    public function handleOrders(Request $request, $slug) {
        //Log::info('Orders ' . $slug . ':' . json_encode($request->all()));
        $client = $this->_client;
        $shopUrl = $request->headers->get('x-shopify-shop-domain');
        $user = User::Where('shop_url', $shopUrl)->first();
        if ($client != null) {
            if (isset($user->get_dev_setting)) {
                if ($slug == "create" || $slug == "update") {
                    $result = $this->createOrder($request, $user);
                    if (isset($result->OrderDetailResult->CancellationReason)) {
                        $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);
                        try {
                            $shopify->call(['URL' => 'orders/' . $request->get('id') . '.json', 'METHOD' => 'PUT', "DATA" => ['order' => ['id' => $request->get('id'), 'note' => $result->OrderDetailResult->CancellationReason]]]);
                        } catch (\Exception $e) {
                            Log::info('Error in Order cancel order note update' . $request->get('id') . $e->getMessage());
                        }
                    }
                    Log::info($shopUrl . ' Order ' . $slug . json_encode($result));
                    exit();
                } else if ($slug == "update") {
                    Log::info($shopUrl . ' Order ' . $slug);
                    exit();
                } else if ($slug == "paid" || $slug == "cancelled") {
                    $result = $this->changeOrderStatus($request, $user);
                    Log::info($shopUrl . ' Order ' . $slug . json_encode($result));
                    exit();
                } else {///    this is use to handle delete request
                    Log::info($shopUrl . ' Order ' . $slug);
                    exit();
                }
            }
            Log::info($shopUrl . ' Order ' . $slug . 'not saved account setting yet !');
            exit();
        }
        Log::info($shopUrl . ' Order ' . $slug . 'problem in soap client !');
        exit();
    }

    public function createOrder($request, $user) {
        $client = $this->_client;
        if ($request->get('financial_status') == 'pending') {
            $order_status = 6;
        } else {
            $order_status = 0;
        }


        $billing_first_name = '';
        $billing_last_name = '';
        $shipping_first_name = '';
        $shipping_last_name = '';
        if (isset($request->get('billing_address')['first_name']))
            $billing_first_name = $request->get('billing_address')['first_name'];

        if (isset($request->get('billing_address')['last_name']))
            $billing_last_name = $request->get('billing_address')['last_name'];

        if (isset($request->get('shipping_address')['first_name']))
            $shipping_first_name = $request->get('billing_address')['first_name'];

        if (isset($request->get('shipping_address')['last_name']))
            $shipping_last_name = $request->get('billing_address')['last_name'];

        $order_array = (object) array();

        $article_array = array();
        $order_create_array = array();
        foreach ($request->get('line_items') as $key => $item_data) {
            $article_array[$key] = (object) array(
                        'Article' => $item_data['sku'],
                        'ArticleDescr' => $item_data['name'],
                        'ProductID' => $item_data['variant_id'],
                        'Quantity' => $item_data['quantity']
            );
        }

        $order_array->ArticlesList = $article_array;
        $order_array->InvNumber = $request->get('id');
        $order_array->Customer = $billing_first_name . ' ' . $billing_last_name;
        $order_array->Comments = '';
        $order_array->ContactPersonName = $shipping_first_name . ' ' . $shipping_last_name;
        $order_array->ContactPersonPhone = $request->get('shipping_address')['phone'];
        $order_array->Shipper = $request->get('processing_method');
        $order_array->InvReference = $request->get('id');
        $order_array->InvStatus = $order_status;
        $order_array->InvDate = date('Y-m-d-H:i', strtotime($request->get('created_at')));
        $order_array->InvDueDate = "";
        $order_array->InvTotal = $request->get('total_price');
        $order_array->InvAmountDue = 0;
        $order_array->ErpTimestamp = date('Y-m-d-H:i');
        $order_array->PartnerKey = '';
        $order_array->DeliverAddress = $request->get('shipping_address')['address1'];
        $order_array->DeliverAddress2 = $request->get('shipping_address')['address2'];
        $order_array->DeliveryPostCodeZIP = $request->get('shipping_address')['zip'];
        $order_array->Country = $request->get('shipping_address')['country'];
        $order_array->CountryCode = $request->get('shipping_address')['country_code'];
        $order_array->City = $request->get('shipping_address')['city'];
        $order_array->StateOrProvinceCode = $request->get('shipping_address')['province_code'];
        $order_array->CompanyName = $request->get('shipping_address')['company'];
        $order_array->EmailAddress = $request->get('email');
        $order_array->PaymentMethod = $request->get('gateway');
        $order_array->PaymentDescription = $request->get('gateway');
        $order_array->OrderTotalWeight = $request->get('total_weight') / 1000;
        $order_array->OrderType = 4;
        $order_array->InvoiceID = "";
        $order_array->ShortCode = "";
        $order_array->TaxAmount = $request->get('total_tax');
        $order_array->CurrencyCode = $request->get('currency');
        $order_array->ShipmentCost = isset($request->get('shipping_lines')[0]['price']) ? $request->get('shipping_lines')[0]['price'] : 0.00;
        $order_array->Warehouse = $user->get_dev_setting->warehouse_number;
        $order_array->AccountKey = $user->get_dev_setting->account_key;
        $result = $client->OrderDetail($order_array);
        //Log::info(' Order update' . $client->__getLastRequest());
//        Log::info(' Order update' . $client->__getLastResponse());
        return $result;
    }

    public function changeOrderStatus($request, $user) {
        $client = $this->_client;

        if ($request->get('financial_status') == 'paid') {
            $order_status = 0;
        } else {
            $order_status = 7;
        }

        $order_array = (object) array();
        $order_array->LicenseKey = $user->get_dev_setting->account_key;
        $order_array->InvNumber = $request->get('id');
        $order_array->Status = $order_status;

        $result = $client->ChangeOrderStatus($order_array);
        return $result;
    }

    public function orderDetails(Request $request, $slug) {
        $client = $this->_client;

        $shopify_parameter = json_decode(base64_decode($slug));
        $order_id = $shopify_parameter->id;
        $user = auth()->user();
        $request_array = (object) array();
        $request_array->AccountKey = $user->get_dev_setting->account_key;
        $request_array->ListInvNumbers = array($order_id);

        $warehouse_order = $client->GetOrderShipmentInfo($request_array);
//        echo htmlentities($client->__getLastRequest());
//        echo "<pre>";
//        print_r($request_array);
//        dd($warehouse_order);
        if (isset($warehouse_order->GetOrderShipmentInfoResult->OrderShipmentInfo)) {
            $warehouse_shipment = $warehouse_order->GetOrderShipmentInfoResult->OrderShipmentInfo->Shipments;
            $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);

            $warehouse_order = $warehouse_order->GetOrderShipmentInfoResult->OrderShipmentInfo;

//            dd($warehouse_order);
            try {
                $orders = $shopify->call(['URL' => 'orders/' . $warehouse_order->InvNumber . '.json?fields=id,financial_status,fulfillment_status,created_at,line_items', 'METHOD' => 'GET']);
            } catch (\Exception $e) {
                return redirect()->route('dashboard', $slug)->with('error-message', $e->getMessage());
            }
            // dd($orders);

            $order_details = (object) array();
            $order_details->order_id = $orders->order->id;
            $order_details->payment_status = $orders->order->financial_status;
            $order_details->order_date = date('M d,Y', strtotime($orders->order->created_at));
            if ($warehouse_order->OrderStatus == 0)
                $order_status = "Pending";
            elseif ($warehouse_order->OrderStatus == 2)
                $order_status = "Selected/picked";
            elseif ($warehouse_order->OrderStatus == 3)
                $order_status = "In Progress";
            elseif ($warehouse_order->OrderStatus == 4)
                $order_status = "Completed";
            elseif ($warehouse_order->OrderStatus == 6)
                $order_status = "On Hold";
            elseif ($warehouse_order->OrderStatus == 7)
                $order_status = "Cancelled";
            else
                $order_status = "";

            $order_details->order_status = $order_status;

            if ($warehouse_shipment != null) {
                $warehouse_shipment = $warehouse_shipment->ShipmentDetail;
                if (count($warehouse_shipment) == 1) {
                    $shipment_array[0] = $warehouse_shipment;
                    $warehouse_shipment = $shipment_array;
                }
                $item = (object) array();
                foreach ($warehouse_shipment as $key => $shipment) {

                    $articles = $shipment->Articles->Article;
                    if (count($shipment->Articles->Article) == 1) {
                        $article_array[0] = $shipment->Articles->Article;
                        $articles = $article_array;
                    }

                    $product_id_array = array_column($articles, 'ProductID');

//                    print_r($product_id_array);
                    foreach ($orders->order->line_items as $k => $order) {
                        if (in_array($order->variant_id, $product_id_array)) {

                            $item->variant_id = $order->variant_id;
                            $item->product_name = $order->title;
                            $item->variant_title = $order->variant_title;
                            $item->product_link = 'https://' . $user->shop_url . '/admin/products/' . $order->product_id . '/variants/' . $order->variant_id;
//                $item->description = $warehouse_order[$key]->Description;
                            $item->sku = $order->sku;
//                $item->quantity = $order->quantity;
//                $item->price = $order->price;
                            $item->PackerName = $shipment->PackerName;
                            $item->FrieghtCost = $shipment->FrieghtCost;
                            $item->DispatchTime = date('M d,Y H:I A', strtotime($shipment->DispatchTime));
                            $item->PackingStartTime = date('M d,Y H:i A', strtotime($shipment->PackingStartTime));
                            $item->PackingEndTime = date('M d,Y H:i A', strtotime($shipment->PackingEndTime));
                            $item->Shipper = $shipment->Shipper;
                            $item->TrackingNumber = $shipment->TrackingNumber;

                            $video_id = explode("?v=", $shipment->YoutubeUrl);
                            $video_id = $video_id[1];
                            $item->YoutubeUrl = 'https://www.youtube.com/embed/' . $video_id . '/?controls=0';
                            $order_details->items[$k] = $item;
                        }
                    }
                }
            } else {
                foreach ($orders->order->line_items as $key => $order) {
                    $item = (object) array();
                    $item->variant_id = $order->variant_id;
                    $item->product_name = $order->title;
                    $item->variant_title = $order->variant_title;
                    $item->product_link = 'https://' . $user->shop_url . '/admin/products/' . $order->product_id . '/variants/' . $order->variant_id;
//                $item->description = $warehouse_order[$key]->Description;
                    $item->sku = $order->sku;
//                $item->quantity = $order->quantity;
//                $item->price = $order->price;
                    $item->PackerName = '******';
                    $item->FrieghtCost = '******';
                    $item->DispatchTime = '******';
                    $item->PackingStartTime = '******';
                    $item->PackingEndTime = '******';
                    $item->Shipper = '******';
                    $item->TrackingNumber = '******';
                    $item->YoutubeUrl = '';

                    $order_details->items[$key] = $item;
                }
            }
//            dd($order_details);
            $data['order_details'] = $order_details;
            $data['slug'] = $slug;
            return view('order_detail', $data);
        }

        return redirect()->route('dashboard', $slug)->with('error-message', 'sorry! this order is not found in warehouse.');
    }

    public function updateOrderStatus($id, $no, $token) {
        $client = $this->_client;
        $user = DeveloperSetting::Where([['warehouse_number', $no]])->first();

//        $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->get_user->shop_url, 'ACCESS_TOKEN' => $user->get_user->access_token]);
//        $shopify_result = $shopify->call(['URL' => 'orders/408497881140/fulfillments/382282563636/cancel.json', 'METHOD' => 'POST']);
//        dd($shopify_result);
//$shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);
        if (isset($user->get_user) && $token == $user->warehouse_token) {

            $request_array = (object) array();
            $request_array->AccountKey = $user->account_key;
            $request_array->ListInvNumbers = array($id);
            $warehouse_order = $client->GetOrderShipmentInfo($request_array);
            $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->get_user->shop_url, 'ACCESS_TOKEN' => $user->get_user->access_token]);
//            echo "<pre>";
//            print_r($request_array);
//            print_r($warehouse_order);
//            die;

            if (isset($warehouse_order->GetOrderShipmentInfoResult->OrderShipmentInfo)) {
                $warehouse_order = $warehouse_order->GetOrderShipmentInfoResult->OrderShipmentInfo;
                try {
                    $orders = $shopify->call(['URL' => 'orders/' . $id . '.json?fields=id,financial_status,fulfillment_status,created_at,line_items', 'METHOD' => 'GET']);
                    $locations = $shopify->call(['URL' => 'locations.json', 'METHOD' => 'GET']);
                } catch (\Exception $e) {
                    return json_encode(array('success' => false));
                }
                //dd($orders);

                if ($warehouse_order->OrderStatus == 4 && $orders->order->fulfillment_status == null && isset($warehouse_order->Shipments->ShipmentDetail)) {
                    $warehouse_shipment = $warehouse_order->Shipments->ShipmentDetail;
                    if (count($warehouse_order->Shipments->ShipmentDetail) == 1) {
                        $shipment_array[0] = $warehouse_order->Shipments->ShipmentDetail;
                        $warehouse_shipment = $shipment_array;
                    }
                    //echo "<pre>";
                    //print_r($warehouse_shipment);

                    foreach ($warehouse_shipment as $shipment) {
                        $articles = $shipment->Articles->Article;
                        if (count($shipment->Articles->Article) == 1) {
                            $article_array[0] = $shipment->Articles->Article;
                            $articles = $article_array;
                        }
                        $product_id_array = array_column($articles, 'ProductID');
                        if (empty($product_id_array)) {
                            return json_encode(array('success' => false));
                        }

                        //print_r($product_id_array);
                        $item_ids_array = array();
                        foreach ($orders->order->line_items as $key => $order) {
                            //echo $order->variant_id.'<br>';
                            if (in_array($order->variant_id, $product_id_array)) {
                                $item_ids_array[$key]['id'] = $order->id;
                            }
                        }
                        $item_ids_array = array_values($item_ids_array);
                        // echo count($warehouse_shipment);
                        //dd($item_ids_array);
                        try {
                            $shopify_result = $shopify->call(['URL' => 'orders/' . $id . '/fulfillments.json', 'METHOD' => 'POST', "DATA" => ["fulfillment" => array("location_id" => $locations->locations[0]->id, "tracking_number" => $shipment->TrackingNumber, "line_items" => $item_ids_array)]]);
                        } catch (\Exception $e) {
                            Log::info('Order status update error ' . $id . $e->getMessage());
                            return json_encode(array('success' => false));
                        }
//                            dd($shopify_result);
                    }
                    return json_encode(array('success' => true));
                }
            }
            if (isset($warehouse_order->OrderStatus)) {
                if ($warehouse_order->OrderStatus == 7) {
//                    try {
//                        $shopify_result = $shopify->call(['URL' => 'orders/' . $id . '/cancel.json', 'METHOD' => 'POST', "DATA" => ['email' => true]]);
//                    } catch (\Exception $e) {
//                        Log::info('Order status update error' . $id . $e->getMessage());
//                        return json_encode(array('success' => false));
//                    }
                    return json_encode(array('success' => true));
                }
            }
        }
        return json_encode(array('success' => false));
    }
    
    public function checkorderDetails() {
        $id = 787645923379;
        $no = 1305;
        $client = $this->_client;
        $user = DeveloperSetting::Where([['warehouse_number', $no]])->first();

        if (isset($user->get_user)) {

            $request_array = (object) array();
            $request_array->AccountKey = $user->account_key;
            $request_array->ListInvNumbers = array($id);
            $warehouse_order = $client->GetOrderShipmentInfo($request_array);
                    echo htmlentities($client->__getLastRequest());
        echo "<pre>";
        //print_r($request_array);
        dd($warehouse_order);
    }
    die('hello');
    }
    
      public function orderRedact(Request $request) {
          return 'true';
      }

}
