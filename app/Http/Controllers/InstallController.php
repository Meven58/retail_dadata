<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Request\Integration\IntegrationModulesEditRequest;
use RetailCrm\Api\Model\Request\Store\ProductPropertiesRequest;

class InstallController extends Controller
{
    const SECRET = 'fa3afe6658e414a8a4f4004108362c';

    public function index(Request $request)
    {
        $register = $request->all();

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/register.log'),
        ])->info(["register" => $register, 'headers' => $request->header()]);

        if (empty($register)) {
            return response()->json([
                'success' => true,
                'scopes' => [
                    'order_read',
                    'order_write',
                    'customer_read',
                    'customer_write',
                    'store_read',
                    'store_write',
                    'reference_read',
                    'reference_write',
                    'user_read',
                    'user_write',
                    'custom_fields_read',
                    'custom_fields_write',
                    'integration_read',
                    'integration_write',
                    'loyalty_read',
                    'loyalty_write',
                ],
                'registerUrl' => config('app.app_url')."retailregister"
            ]);
        }

        $register = json_decode($register['register'], true);

        $hash = hash_hmac('sha256', $register['apiKey'], self::SECRET);

        if ($hash != $register['token']) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/register.log'),
            ])->info(["not valid" => $token, "apikey" => $register['apiKey']]);
            return response('All fields are required', 400);
        }

        $token = $register['token'] ?? "";
        $url =  $register['account'] ?? ($register['systemUrl'] ?? '');
        $apikey = $register['apiKey'] ?? "";

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/register.log'),
        ])->info(["token" => $token, "url" => $url, "apikey" => $apikey]);

        if (trim($token) == "" || trim($url) == "" || trim($apikey) == "") {
            return response('All fields are required', 400);
        }

        $integration = Integration::updateOrCreate(
            ['retail_url' => $url],
            [
                'retail_token' => $apikey,
                'apikey' => $apikey,
            ]
        );


        $module = new IntegrationModule();
        $module->active = true;
        $module->integrationCode = config('retail.integrationName');
        $module->code = config('retail.integrationName');
        $module->clientId = config('retail.clientdata').$integration->id;
        $module->baseUrl = config('app.app_url');
        $module->accountUrl = config('app.app_url')."retailclient/".$integration->id;


        if ($integration->retail_token != '') {
            $edit = new IntegrationModulesEditRequest($module);
            $retail = SimpleClientFactory::createClient($integration->retail_url, $integration->retail_token);
            $retail->integration->edit($module->code, $edit);

            $props = new ProductPropertiesRequest();
            $props->limit = 100;
            $retail->store->productsProperties($props, $retail);

            $install = new \App\Lib\RetailCRM\InstallModule();
            $install->setIntegration($integration);
            $test = $install->install();
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/register.log'),
            ])->info(["test" => $test]);
        } else {
            //return redirect($request->header()['referer'][0].'admin/integration/list');
        }


        return response()->json([
            'success' => true,
            'accountUrl' => config('app.app_url')."retailclient/".$integration->id
        ]);
    }
}
