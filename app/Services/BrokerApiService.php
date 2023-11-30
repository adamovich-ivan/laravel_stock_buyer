<?php

namespace App\Services;

use WebSocket\Client;

class BrokerApiService
{
    private Client $client;
    private string $apiUrl;
    private ?string $streamSessionId = null;
    private $lastRequestTime = 0;

    public function __construct()
    {
        $this->apiUrl = config('xtb.api_url'); // wss://ws.xtb.com/demo
        $this->client = new Client($this->apiUrl);
    }

    private function applyRequestTrottling(): void
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;

        if ($timeSinceLastRequest < 0.2) {
            usleep((0.2 - $timeSinceLastRequest) * 1000000);
        }

        $this->lastRequestTime = microtime(true);
    }

    private function sendRequest(string $command, $arguments = []): array
    {

        if ($command !== 'login' && !$this->streamSessionId) {
            throw new \Exception('Session ID is not set.');
        }

        $payload = [
            'command' => $command,
            'arguments' => empty($arguments) ? new \stdClass() : $arguments,
        ];

        if ($this->streamSessionId && $command !== 'login') {
            $payload['streamSessionId'] = $this->streamSessionId;
        }

        $jsonPayload = json_encode($payload);

        $this->applyRequestTrottling();

        $this->client->send($jsonPayload);
        $response = json_decode($this->client->receive(), true);

        if (isset($response['status']) && $response['status']) {
            return $response;
        } else {
            throw new \Exception('Error communicating with broker API: ' . json_encode($response));
        }
    }


    public function login(string $userId, string $password): string
    {
        $arguments = [
            'userId' => $userId,
            'password' => $password,
        ];

        $response = $this->sendRequest('login', $arguments);

        if ($response['status'] === true && isset($response['streamSessionId'])) {
            $this->streamSessionId = $response['streamSessionId'];
            return $this->streamSessionId;
        } else {
            throw new \Exception('Login failed: ' . $response['errorDescr']);
        }
    }

    public function logout(): bool
    {
        $response = $this->sendRequest('logout');

        return $response['status'] === true;
    }

    public function getBalance(): array
    {
        $response = $this->sendRequest('getMarginLevel');

        if ($response['status'] === true && isset($response['returnData'])) {
            return $response['returnData'];
        } else {
            throw new \Exception('Unable to get balance: ' . $response['errorDescr']);
        }
    }

    public function getSymbol(string $symbol): array
    {
        $response = $this->sendRequest('getSymbol', ['symbol' => $symbol]);

        if ($response['status'] === true && isset($response['returnData'])) {
            return $response['returnData'];
        } else {
            throw new \Exception('Unable to get symbol information: ' . $response['errorDescr']);
        }
    }

    public function getCommissionDef(string $symbol, float $volume): array
    {
        $response = $this->sendRequest('getCommissionDef', ['symbol' => $symbol, 'volume' => $volume]);

        if ($response['status'] === true && isset($response['returnData']) && $response['returnData']['commission'] != 0) {
            throw new \Exception("Commission for symbol {$symbol} is not zero: " . $response['returnData']['commission']);
        }

        return $response['returnData'];
    }

    public function getTradingHours(array $symbols): array
    {
        $response = $this->sendRequest('getTradingHours', ['symbols' => $symbols]);

        if ($response['status'] === true && isset($response['returnData'])) {
            return $response['returnData'];
        } else {
            throw new \Exception('Unable to get trading hours: ' . $response['errorDescr']);
        }
    }

    public function buy(string $symbol, float $volume): array
    {
        return $this->tradeTransaction([
            'cmd' => 0, // BUY
            'symbol' => $symbol,
            'volume' => $volume,
            'price' => 1, //just for correct work of transaction
            // Добавьте другие необходимые параметры сюда
        ]);
    }

    public function sell(string $symbol, float $volume): array
    {
        return $this->tradeTransaction([
            'cmd' => 1, // SELL
            'symbol' => $symbol,
            'volume' => $volume
            // Добавьте другие необходимые параметры сюда
        ]);
    }

    public function buyLimit(string $symbol, float $volume, float $price): array
    {
        return $this->tradeTransaction([
            'cmd' => 2, // BUY_LIMIT
            'symbol' => $symbol,
            'volume' => $volume,
            'price' => $price
            // Добавьте другие необходимые параметры сюда
        ]);
    }

    public function sellLimit(string $symbol, float $volume, float $price): array
    {
        return $this->tradeTransaction([
            'cmd' => 3, // SELL_LIMIT
            'symbol' => $symbol,
            'volume' => $volume,
            'price' => $price
            // Добавьте другие необходимые параметры сюда
        ]);
    }

    public function buyStop(string $symbol, float $volume, float $price): array
    {
        return $this->tradeTransaction([
            'cmd' => 4, // BUY_STOP
            'symbol' => $symbol,
            'volume' => $volume,
            'price' => $price
            // Добавьте другие необходимые параметры сюда
        ]);
    }

    public function sellStop(string $symbol, float $volume, float $price): array
    {
        return $this->tradeTransaction([
            'cmd' => 5, // SELL_STOP
            'symbol' => $symbol,
            'volume' => $volume,
            'price' => $price
            // Добавьте другие необходимые параметры сюда
        ]);
    }

    private function tradeTransaction(array $tradeTransInfo): array
    {
        $arguments = ['tradeTransInfo' => $tradeTransInfo];
        $response = $this->sendRequest('tradeTransaction', $arguments);

        if ($response['status'] === true) {
            return $response;
        } else {
            throw new \Exception('Trade transaction failed: ' . $response['errorDescr']);
        }
    }





}
