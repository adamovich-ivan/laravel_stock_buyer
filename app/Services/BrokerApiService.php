<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use WebSocket\Client;

class BrokerApiService
{
    private string $apiUrl;
    private Client $сlient;

    public function __construct()
    {
        $this->apiUrl = config('xtb.api_url'); // wss://ws.xtb.com/demo
        $this->client = new Client($this->apiUrl);
    }

    private function sendRequest(string $command, $arguments = [], ?string $streamSessionId = null): array
    {
        // Использование stdClass для представления пустого объекта в JSON, если массив аргументов пуст
        $payload = [
            'command' => $command,
            'arguments' => empty($arguments) ? new \stdClass() : $arguments,
            'streamSessionId' => $streamSessionId
        ];

        $jsonPayload = json_encode($payload);

        $this->client->send($jsonPayload);
        $response = json_decode($this->client->receive(), true);

        if (isset($response['status']) && $response['status']) {
            return $response;
        } else {
            throw new \Exception('Error communicating with broker API: ' . json_encode($response));
        }
    }


    public function login(string $userId, string $password, ?string $appId = null, ?string $appName = null): string
    {
        $arguments = [
            'userId' => $userId,
            'password' => $password,
            'appId' => $appId ?? 'defaultAppId',
            'appName' => $appName ?? 'defaultAppName'
        ];

        $response = $this->sendRequest('login', $arguments);

        if ($response['status'] === true && isset($response['streamSessionId'])) {
            return $response['streamSessionId'];
        } else {
            throw new \Exception('Login failed: ' . $response['errorDescr']);
        }
    }

    public function logout(string $streamSessionId): bool
    {
        $response = $this->sendRequest('logout', [], $streamSessionId);

        return $response['status'] === true;
    }

    public function getBalance(string $streamSessionId): array
    {
        $response = $this->sendRequest('getMarginLevel', [], $streamSessionId);

        if ($response['status'] === true && isset($response['returnData'])) {
            return $response['returnData'];
        } else {
            throw new \Exception('Unable to get balance: ' . $response['errorDescr']);
        }
    }

    public function getSymbol(string $symbol, string $streamSessionId): array
    {
        $response = $this->sendRequest('getSymbol', ['symbol' => $symbol], $streamSessionId);

        if ($response['status'] === true && isset($response['returnData'])) {
            return $response['returnData'];
        } else {
            throw new \Exception('Unable to get symbol information: ' . $response['errorDescr']);
        }
    }

    public function getCommissionDef(string $symbol, float $volume, string $streamSessionId): array
    {
        $response = $this->sendRequest('getCommissionDef', ['symbol' => $symbol, 'volume' => $volume], $streamSessionId);

        if ($response['status'] === true && isset($response['returnData']) && $response['returnData']['commission'] != 0) {
            throw new \Exception("Commission for symbol {$symbol} is not zero: " . $response['returnData']['commission']);
        }

        return $response['returnData'];
    }

    public function getTradingHours(array $symbols, string $streamSessionId): array
    {
        $response = $this->sendRequest('getTradingHours', ['symbols' => $symbols], $streamSessionId);

        if ($response['status'] === true && isset($response['returnData'])) {
            return $response['returnData'];
        } else {
            throw new \Exception('Unable to get trading hours: ' . $response['errorDescr']);
        }
    }

    public function tradeTransaction(array $tradeTransInfo, string $streamSessionId): array
    {
        // Подготовка аргументов для запроса
        $arguments = [
            'tradeTransInfo' => $tradeTransInfo
        ];

        $response = $this->sendRequest('tradeTransaction', $arguments, $streamSessionId);

        // Проверка статуса запроса и обработка ответа
        if ($response['status'] === true) {
            // При успешном получении запроса сервером
            // Необходимо использовать tradeTransactionStatus для проверки статуса транзакции
            return $response;
        } else {
            // Обработка ошибок
            throw new \Exception('Trade transaction failed: ' . $response['errorDescr']);
        }
    }
}
