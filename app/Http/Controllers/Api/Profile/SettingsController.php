<?php

namespace App\Http\Controllers\Api\Profile;

use App\Console\Commands\updateCustomersCorporateBusinessInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Integrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RetailCrm\Api\Factory\SimpleClientFactory;

class SettingsController extends Controller
{
    public function index($id)
    {

        $integration = Integrations::where('id', $id)->first();
        $client = SimpleClientFactory::createClient($integration['retail_url'], $integration['retail_token']);
        $customersCorporate = $client->customersCorporate->list();
        if (empty($integration)) {
            return abort(404);
        }

        $update_inputs = [
            [
                'name' => 'Полное наименование',
                'externalId' => 'name',
                'selected' => false
            ],
            [
                'name' => 'ОКПО',
                'externalId' => 'OKPO',
                'selected' => false
            ],
            [
                'name' => 'contragentType',
                'externalId' => 'contragentType',
                'selected' => false
            ],
            [
                'name' => 'КПП',
                'externalId' => 'KPP',
                'selected' => false
            ],
            [
                'name' => 'ОГРН',
                'externalId' => 'OGRN',
                'selected' => false
            ],
            [
                'name' => 'Адрес регистрации',
                'externalId' => 'legalAddress',
                'selected' => false
            ],
            [
                'name' => 'ОГРНИП (Для ИП)',
                'externalId' => 'OGRNIP',
                'selected' => false
            ],
            [
                'name' => 'Дата свидетельства (Для ИП)',
                'externalId' => 'certificateDate',
                'selected' => false
            ],
            [
                'name' => 'Номер свидетельства (Для ИП)',
                'externalId' => 'certificateNumber',
                'selected' => false
            ],
        ];

        $selected_inputs = json_decode($integration['selected_inputs']);

        foreach ($update_inputs as $key => $u_input) {
            foreach ($selected_inputs as $s_input) {
                if ($u_input['externalId'] == $s_input) {
                    $update_inputs[$key ]['selected'] = true;

                    continue;
                }
            }
        }


        return view('welcome', [
            'id' => $id,
            'active' => $integration['active'] ?? '',
            'selected_inputs' => $integration['selected_inputs'] ?? '',
            'update_inputs' => $update_inputs,
            'message' => 'Данные загружены'
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->all();

        $integration = Integrations::find($data['id']);

        $integration->active = $data['active'];
        $integration->selected_inputs = json_encode($data['selectedInputs']);

        $integration->update();

        return false;
    }

    public function updateCompanies($id = 1)
    {
        $update = new updateCustomersCorporateBusinessInfo();
        $update->handle($id);
    }
}
