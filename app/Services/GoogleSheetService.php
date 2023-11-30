<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;

class GoogleSheetService
{
    private $client;
    private $service;
    private $spreadsheetId;

    public function __construct($spreadsheetId)
    {
        $this->client = $this->getClient();
        $this->service = new Google_Service_Sheets($this->client);
        $this->spreadsheetId = $spreadsheetId;
    }

    private function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName(config('google.app_name'));
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig(config('google.credentials_path'));
        $client->setAccessType('offline');

        return $client;
    }

    public function getSpreadsheetData($range)
    {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues();
    }
}
