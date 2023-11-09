<?php

namespace App\Actions;

use App\Services\BrokerApiService;

class PurchaseStocksAction
{
    private BrokerApiService $brokerService;
    private string $streamSessionId;

    public function __construct()
    {
        $this->brokerService = new BrokerApiService();
        // Аутентификация и получение streamSessionId
        $this->streamSessionId = $this->brokerService->login(
            config('xtb.credentials.userId'),
            config('xtb.credentials.password')
        );
    }

    public function execute(array $companies): array
    {
        $purchaseResults = [];

        foreach ($companies as $company) {
            $symbolInfo = $this->brokerService->getSymbol($company['symbol'], $this->streamSessionId);

            // Проверяем, доступна ли компания для покупки, и выполняем покупку
            if ($this->isTradable($symbolInfo)) {
                $tradeTransInfo = [
                    'cmd' => 0, // Команда BUY
                    'symbol' => $company['symbol'],
                    'volume' => $company['volume'],
                    // Другие необходимые параметры транзакции
                ];

                $transactionResult = $this->brokerService->tradeTransaction($tradeTransInfo, $this->streamSessionId);
                $purchaseResults[] = $transactionResult;
            }
        }

        // После выполнения операций выходим из системы
        $this->brokerService->logout($this->streamSessionId);

        return $purchaseResults;
    }

    private function isTradable(array $company, string $streamSessionId): bool
    {
        // Получаем торговые часы для символа
        $tradingHours = $this->brokerService->getTradingHours([$company['symbol']], $streamSessionId);

        // Предполагаем, что $tradingHours содержит массив с информацией о торговых часах
        // Проверяем, является ли текущее время торговым временем для символа
        $currentTime = time();
        $isWithinTradingHours = false;
        foreach ($tradingHours as $record) {
            if ($currentTime >= strtotime($record['openTime']) && $currentTime <= strtotime($record['closeTime'])) {
                $isWithinTradingHours = true;
                break;
            }
        }

        // Проверяем комиссию
        $commissionInfo = $this->brokerService->getCommissionDef($company['symbol'], $company['volume'], $streamSessionId);
        $hasNoCommission = $commissionInfo['commission'] == 0;

        return $isWithinTradingHours && $hasNoCommission;
    }

}
