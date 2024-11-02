<?php

namespace App\Console\Commands;

use App\Models\Integrations;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RetailCrm\Api\Enum\Customers\ContragentType;
use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Model\Entity\Customers\CustomerContragent;
use RetailCrm\Api\Model\Entity\CustomersCorporate\Company;
use RetailCrm\Api\Enum\ByIdentifier;
use RetailCrm\Api\Model\Request\CustomersCorporate\CustomersCorporateCompaniesEditRequest;
use RetailCrm\Api\Model\Request\CustomersCorporate\CustomersCorporateCompaniesRequest;

class updateCustomersCorporateBusinessInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-customers-corporate-business-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update company info';

    /**
     * Execute the console command.
     */
    public function handle($id = 0)
    {

        $allClients = [];

        if ($id <= 0) {
            $allClients = Integrations::all()->toArray();
        } else {
            $allClients[] = Integrations::where('id', $id)->first()->toArray();
        };


        foreach ($allClients as $clientData) {
            $client = SimpleClientFactory::createClient($clientData['retail_url'], $clientData['retail_token']);
            $customersCorporate = $client->customersCorporate->list();
            $dadata = new \Dadata\DadataClient(config('dadata.apiKey'), config('dadata.secretKey'));


            foreach ($customersCorporate->customersCorporate as $customerCorporate) {
                $companies = $client->customersCorporate->companies($customerCorporate->id, new CustomersCorporateCompaniesRequest(ByIdentifier::ID, $customerCorporate->site));

                foreach ($companies->companies as $company) {
                    $nowDataTime = Carbon::now()->toDateTime();
                    $interval = $company->createdAt->diff($nowDataTime);

                    $minutes = ($interval->h * 60) + $interval->i;
                    $response = $dadata->findById("party", $company->contragent->INN);

                    if ($minutes > 190 && $id <= 0) {

                        Log::build([
                            'driver' => 'single',
                            'path' => storage_path('logs/' . $clientData['id'] . '/updateInnErrors.log')
                        ])->info([
                            'error' => 'Компания с ИНН: ' . $company->contragent->INN . ' была создана более 10ти минут назад.',
                            'createdAt' => $company->createdAt,
                            'minutes' => $minutes,
                        ]);
                        continue;
                    }
                    if (empty($response)) {
                        Log::build([
                            'driver' => 'single',
                            'path' => storage_path('logs/' . $clientData['id'] . '/updateInnErrors.log')
                        ])->info([
                            'error' => 'Информация по ИНН: ' . $company->contragent->INN . ' не найдена.'
                        ]);

                        continue;
                    }
                    foreach ($response as $dadataCompanyInfo) {
                        try {
                            $request = new CustomersCorporateCompaniesEditRequest();
                            $request->company = new Company();
                            $request->site = $customerCorporate->site;
                            $request->by = ByIdentifier::ID;
                            $request->entityBy = ByIdentifier::ID;
                            $request->company->isMain = true;
//                            $request->company->name = $dadataCompanyInfo['value'];
                            $request->company->contragent = new CustomerContragent();
//                            $request->company->contragent->OKPO = $dadataCompanyInfo['data']['okpo'];

//                            if ($dadataCompanyInfo['data']['type'] == "LEGAL") {
//                                $request->company->contragent->contragentType = ContragentType::LEGAL_ENTITY;
//                                $request->company->contragent->KPP = $dadataCompanyInfo['data']['kpp'];
//                                $request->company->contragent->OGRN = $dadataCompanyInfo['data']['ogrn'];
//                                $request->company->contragent->legalAddress = $dadataCompanyInfo['data']['address']['unrestricted_value'];
//
//                            } else if ($dadataCompanyInfo['data']['type'] == "INDIVIDUAL") {
//                                $request->company->contragent->contragentType = ContragentType::ENTERPRENEUR;
//                                $request->company->contragent->OGRNIP = $dadataCompanyInfo['data']['ogrn'];
//                                $request->company->contragent->legalAddress = $dadataCompanyInfo['data']['address']['unrestricted_value'];
//                                if (!empty($dadataCompanyInfo['data']['documents']['fts_registration']['number'])) {
//                                    $request->company->contragent->certificateNumber = $dadataCompanyInfo['data']['documents']['fts_registration']['number'];
//                                };
//
//                                if (!empty($dadataCompanyInfo['data']['documents']['fts_registration']['issue_date'])) {
//                                    $timestampInSeconds = $dadataCompanyInfo['data']['documents']['fts_registration']['issue_date'] / 1000;
//                                    $dateTime = Carbon::createFromTimestamp($timestampInSeconds);
//                                    $request->company->contragent->certificateDate = $dateTime;
////                                    $request->company->contragent->certificateDate = $dadataCompanyInfo['data']['documents']['fts_registration']['issue_date'];
//                                };
//                            };
                            $clientDataInputs = json_decode($clientData['selected_inputs']);
                            foreach ($clientDataInputs as $input) {
                                switch ($input) {
                                    case 'name':
                                        $request->company->name = $dadataCompanyInfo['value'];
                                        break;
                                    case 'OKPO':
                                        $request->company->contragent->OKPO = $dadataCompanyInfo['data']['okpo'];
                                        break;
                                    case 'contragentType':
                                        if ($dadataCompanyInfo['data']['type'] == "LEGAL") {
                                            $request->company->contragent->contragentType = ContragentType::LEGAL_ENTITY;
                                        } else if ($dadataCompanyInfo['data']['type'] == "INDIVIDUAL") {
                                            $request->company->contragent->contragentType = ContragentType::ENTERPRENEUR;
                                        }
                                        break;
                                    case 'KPP':
                                        $request->company->contragent->KPP = $dadataCompanyInfo['data']['kpp'];
                                        break;
                                    case 'legalAddress':
                                        $request->company->contragent->legalAddress = $dadataCompanyInfo['data']['address']['unrestricted_value'];
                                        break;
                                    case 'OGRNIP':
                                        $request->company->contragent->OGRNIP = $dadataCompanyInfo['data']['ogrn'];
                                        break;
                                    case 'OGRN':
                                        $request->company->contragent->OGRNIP = $dadataCompanyInfo['data']['ogrn'];
                                        break;
                                    case 'certificateDate':
                                        if (!empty($dadataCompanyInfo['data']['documents']['fts_registration']['issue_date'])) {
                                            $timestampInSeconds = $dadataCompanyInfo['data']['documents']['fts_registration']['issue_date'] / 1000;
                                            $dateTime = Carbon::createFromTimestamp($timestampInSeconds);
                                            $request->company->contragent->certificateDate = $dateTime;
                                        }
                                        break;
                                    case 'certificateNumber':
                                        if (!empty($dadataCompanyInfo['data']['documents']['fts_registration']['number'])) {
                                            $request->company->contragent->certificateNumber = $dadataCompanyInfo['data']['documents']['fts_registration']['number'];
                                        };
                                        break;
                                }
                            }

                            $result = $client->customersCorporate->companiesEdit($customerCorporate->id, $company->id, $request);

                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/' . $clientData['id'] . '/updateInn.log')
                            ])->info([
                                'success' => 'Информация по ИНН: ' . $company->contragent->INN . ' успешно добавлена.',
                                'result' => json_encode($result)
                            ]);

                        } catch (\Exception $e) {
                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/' . $clientData['id'] . '/updateInnErrors.log')
                            ])->info([
                                'message' => 'Произошла ошибка',
//                                'error' => $e,
                                'inn' => json_encode($company->contragent->INN)
                            ]);

                        }
                    }
                }
            }
        }
    }
}
