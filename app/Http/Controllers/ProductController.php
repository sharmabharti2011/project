<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use SoapClient;
use SoapFault;
use App\User;
use App\DeveloperSetting;
use App\Job;
use App;
use ZipArchive;
use DOMDocument;

class ProductController extends Controller {

    protected $_accountKey = '';
    protected $_warehouseNumber = '';
    protected $_client = null;
    protected $_shopify = null;
    protected $_user;

    /**
     * WarehouseSpace_Warehouse_Model_Api constructor.
     */
    public function __construct() {

        $this->middleware(function ($request, $next) {
            //$route_name = $request->route()->getName();
            $this->_user = auth()->user();
            $user = $this->_user;
            if (isset($user->get_dev_setting)) {
                $this->_shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->shop_url, 'ACCESS_TOKEN' => $user->access_token]);
                $this->_accountKey = $user->get_dev_setting->account_key;
                $this->_warehouseNumber = $user->get_dev_setting->warehouse_number;
            }
            $debug = true;
            // if ($route_name == "warehouse.product.sync")
            $wsdl = env('WSDL_MATERIAL_URL');
//            else
//                $wsdl = env('WSDL_URL');

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
            return $next($request);
        });
    }

    public function handleProducts(Request $request, $slug) {
        $shopUrl = $request->headers->get('x-shopify-shop-domain');
        if ($slug == "create" || $slug == "update") {
            Job::create(array('shop_url' => $shopUrl, 'request_data' => json_encode($request->all()), 'api' => 'product', 'method' => $slug));
            return response()->json(['success' => true], 200);
        } else {
            Log::info($shopUrl . ' Product ' . $slug);  ///de;ete product request handle
            return response()->json(['success' => true], 200);
        }
    }

    public function dispatchProductByCronJob($job) {
        $wsdl = env('WSDL_URL');
        $debug = true;
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
        $request = json_decode($job->request_data);
        $client = $this->_client;
        $shopUrl = $job->shop_url;
        if ($client != null) {
            $user = User::Where('shop_url', $shopUrl)->first();
            if (isset($user->get_dev_setting)) {
                if ($job->method == "create" || $job->method == "update") {
                    $product_images = array_column($request->images, 'src');
                    $i = 0;
                    $product_array = array();
                    foreach ($request->variants as $item_value) {
                        $item_value = (object) $item_value;
                        $item_array = (object) array();
                        $item_array->ProductID = $item_value->id;
                        $item_array->Article = $item_value->sku;
                        $item_array->Title = htmlspecialchars($item_value->title);
                        $item_array->Barcode = $item_value->barcode;
                        $item_array->Description = htmlspecialchars(strip_tags($request->body_html));
//                    $item_array->ErpTimeStamp = date('Y-m-d-H:i');
//                    $item_array->TimeStamp = date('Y-m-d-H:i');
                        $item_array->HSCode = "";
                        $item_array->UOM = 'each';
                        $item_array->BuyPrice = $item_value->price;
                        $item_array->SellPrice = $item_value->compare_at_price;
                        $item_array->Supplier = "";
                        $item_array->Images = $product_images;
                        $item_array->Manufacturer = "";
                        $item_array->MinQuantity = 0;
                        $item_array->ItemWeight = $item_value->weight;
                        $item_array->ItemHeight = 0;
                        $item_array->ItemWidth = 0;
                        $item_array->ItemDepth = 0;
                        $item_array->WeightCat = 0;
                        $item_array->Model = "";
                        $item_array->Category = $request->product_type;
                        $item_array->Warehouse = $user->get_dev_setting->warehouse_number;
                        $item_array->AccountKey = $user->get_dev_setting->account_key;

                        $product_array[$i] = $item_array;
                        $i++;
                    }

                    $final_product_array = (object) array();
                    $final_product_array->ArticlesList = $product_array;

                    $result = $client->MaterialBulk($final_product_array);
                    Log::info($shopUrl . ' Product ' . $job->method . $result->MaterialBulkResult);
                } else {
                    ///  this is used to handle delete product request
                }
            } else {
                Log::info($shopUrl . ' Product ' . $job->method . 'not saved account setting yet !');
                return false;
            }
        } else {
            Log::info($shopUrl . ' Product ' . $job->method . 'problem in soap client !');
            return false;
        }
        return true;
    }

    public function synchronizeProducts(Request $request) {
        $user = $this->_user;
        $client = $this->_client;
        $shopify = $this->_shopify;
        if ($client != null && $shopify != null) {
            $productinfo = $shopify->call(['URL' => 'products.json', 'METHOD' => 'GET']);
            $dom = new DOMDocument('1.0');
            $dom->formatOutput = true;
            $root = $dom->createElement('ArrayOfMaterialArticle');
            $dom->appendChild($root);

            foreach ($productinfo->products as $key => $product) {
                $images = "";
                if ($product->images != null) {
                    $images = $dom->createElement('Images');
                    foreach ($product->images as $img) {
                        $images->appendChild($dom->createElement('string', $img->src));
                    }
                }

                foreach ($product->variants as $item_value) {
                    $items = $dom->createElement('MaterialArticle');
                    $items->appendChild($dom->createElement('AccountKey', $this->_accountKey));
                    $items->appendChild($dom->createElement('ProductID', $item_value->id));
                    if ($item_value->sku != "")
                        $items->appendChild($dom->createElement('Article', $item_value->sku));
                    $items->appendChild($dom->createElement('Title', htmlspecialchars($item_value->title)));
                    if ($item_value->barcode != "")
                        $items->appendChild($dom->createElement('Barcode', $item_value->barcode));
                    $items->appendChild($dom->createElement('BuyPrice', $item_value->price));
                    if ($product->product_type != "")
                        $items->appendChild($dom->createElement('Category', $product->product_type));
                    if ($product->body_html != "")
                        $items->appendChild($dom->createElement('Description', htmlspecialchars(strip_tags($product->body_html))));
//                    $items->appendChild($dom->createElement('ErpTimeStamp', ''));
//                    $items->appendChild($dom->createElement('TimeStamp', ''));
//                    $items->appendChild($dom->createElement('HSCode', ''));
                    if ($images != "")
                        $items->appendChild($images);
//                    $items->appendChild($dom->createElement('ItemDepth', ''));
//                    $items->appendChild($dom->createElement('ItemHeight', ''));
                    if ($item_value->weight != null)
                        $items->appendChild($dom->createElement('ItemWeight', $item_value->weight));
//                    $items->appendChild($dom->createElement('ItemWidth', ''));
//                    $items->appendChild($dom->createElement('Manufacturer', ''));
//                    $items->appendChild($dom->createElement('MinQuantity', ''));
//                    $items->appendChild($dom->createElement('Model', ''));
                    if ($item_value->compare_at_price != null)
                        $items->appendChild($dom->createElement('SellPrice', $item_value->compare_at_price));
//                    $items->appendChild($dom->createElement('Supplier', ''));
                    $items->appendChild($dom->createElement('UOM', 'each'));
                    $items->appendChild($dom->createElement('Warehouse', $this->_warehouseNumber));
//                    $items->appendChild($dom->createElement('WeightCat', ''));
                    $root->appendChild($items);
                }
            }
//            echo $dom->getElementsByTagName('MaterialArticle')->length;
//            echo '<xmp>' . $dom->saveXML() . '</xmp>';
//            $dom->save('result.xml') or die('XML Create Error');
            $tmpfile = tempnam(sys_get_temp_dir(), 'zip');
            rename($tmpfile, substr($tmpfile, 0, strlen($tmpfile) - 4) . '.zip');
            $tmpfile = substr($tmpfile, 0, strlen($tmpfile) - 4) . '.zip';
            $zip = new ZipArchive;
            $res = $zip->open($tmpfile, ZipArchive::OVERWRITE);
            if ($res === TRUE) {
                $zip->addFromString('Articles.xml', $dom->saveXML());
                $zip->close();
                $h = fopen($tmpfile, 'r');
                $file = fread($h, filesize($tmpfile));
                $zip_array = (object) array();
                $zip_array->data = $file;
                $result = $client->UploadProductsFile($zip_array);
                fclose($h);
                unset($file);
                unlink($tmpfile);
                // echo htmlentities($client->__getLastRequest());
                if ($result)
                    return redirect()->back()
                                    ->with('success-message', 'Product synchronization completed successfully!');
            }
        }
        return redirect()->back()
                        ->with('error-message', 'Something went wrong,please try again later!');
    }

    public function connectInventory($storeId, $token, $location_id, $product_id) {
        $user = DeveloperSetting::Where(['store_id' => $storeId, 'warehouse_token' => $token])->first();
        if (isset($user->get_user)) {
            $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->get_user->shop_url, 'ACCESS_TOKEN' => $user->get_user->access_token]);
            try {
                $product_variant = $shopify->call(['URL' => 'variants/' . $product_id . '.json', 'METHOD' => 'GET']);
            } catch (\Exception $e) {
                return json_encode(array('success' => false, 'message' => "problem in product variant api !"));
            }
            $inventory_item_id = $product_variant->variant->inventory_item_id;
            try {
                $shopify_result = $shopify->call(['URL' => 'inventory_levels/connect.json', 'METHOD' => 'POST', "DATA" => ["location_id" => $location_id, "inventory_item_id" => $inventory_item_id]]);
            } catch (\Exception $e) {
                return json_encode(array('success' => false, 'message' => "problem in product connect api !"));
            }
            return json_encode(array('success' => true, 'message' => 'Inventory successfully connect'));
        } else {
            return json_encode(array('success' => false, 'message' => 'user not found!'));
        }
    }

    public function setInventory($storeId, $token, $location_id, $product_id, $qnty) {
        $user = DeveloperSetting::Where(['store_id' => $storeId, 'warehouse_token' => $token])->first();
        if (isset($user->get_user)) {
            $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->get_user->shop_url, 'ACCESS_TOKEN' => $user->get_user->access_token]);
            try {
                $product_variant = $shopify->call(['URL' => 'variants/' . $product_id . '.json', 'METHOD' => 'GET']);
            } catch (\Exception $e) {
                return json_encode(array('success' => false, 'message' => "problem in product variant api !"));
            }
            $inventory_item_id = $product_variant->variant->inventory_item_id;
            try {
                $shopify_result = $shopify->call(['URL' => 'inventory_levels/set.json', 'METHOD' => 'POST', "DATA" => ["location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $qnty]]);
            } catch (\Exception $e) {
                return json_encode(array('success' => false, 'message' => "problem in product inventory set api !"));
            }
            return json_encode(array('success' => true, 'message' => 'Inventory successfully set'));
        } else {
            return json_encode(array('success' => false, 'message' => 'user not found!'));
        }
    }

    public function adjustInventory($storeId, $token, $location_id, $product_id, $qnty) {
        $user = DeveloperSetting::Where(['store_id' => $storeId, 'warehouse_token' => $token])->first();
        if (isset($user->get_user)) {
            $shopify = App::makeWith('ShopifyAPI', ['API_KEY' => env('SHOPIFY_APP_KEY'), 'API_SECRET' => env('SHOPIFY_APP_SECRET'), 'SHOP_DOMAIN' => $user->get_user->shop_url, 'ACCESS_TOKEN' => $user->get_user->access_token]);
            try {
                $product_variant = $shopify->call(['URL' => 'variants/' . $product_id . '.json', 'METHOD' => 'GET']);
            } catch (\Exception $e) {
                return json_encode(array('success' => false, 'message' => "problem in product variant api !"));
            }
            $inventory_item_id = $product_variant->variant->inventory_item_id;
            try {
                $shopify_result = $shopify->call(['URL' => 'inventory_levels/adjust.json', 'METHOD' => 'POST', "DATA" => ["location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available_adjustment" => $qnty]]);
            } catch (\Exception $e) {
                return json_encode(array('success' => false, 'message' => "problem in product inventory adjust api !"));
            }
            return json_encode(array('success' => true, 'message' => 'Inventory successfully adjust'));
        } else {
            return json_encode(array('success' => false, 'message' => 'user not found!'));
        }
    }

}
