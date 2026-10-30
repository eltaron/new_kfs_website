<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ArcGisService
{
    protected $url = 'https://portal.kfs.gov.eg/server/rest/services/TEST_KFS/Test2026/FeatureServer/0/addFeatures';
    protected $tokenUrl = 'https://portal.kfs.gov.eg/portal/sharing/rest/generateToken';

    public function getToken()
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'f' => 'pjson',
            'username' => env('ARCGIS_USERNAME'),
            'password' => env('ARCGIS_PASSWORD'),
            'expiration' => 20160,
            'referer' => 'https://portal.kfs.gov.eg/server/rest/services/TEST_KFS/Test2026/FeatureServer/0'
        ]);

        return $response->json()['token'] ?? null;
    }

    public function syncToArcGis($data)
    {
        $token = $this->getToken();

        $payload = [
            'f' => 'json',
            'token' => $token,
            'features' => json_encode([['attributes' => $data]])
        ];

        return Http::asForm()->post($this->url, $payload);
    }
}
