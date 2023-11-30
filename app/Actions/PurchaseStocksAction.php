<?php

namespace App\Actions;

use App\Services\BrokerApiService;
use DateTime;
use DateTimeZone;
use Mockery\Exception;

class PurchaseStocksAction
{
    private BrokerApiService $brokerApiService;
    private string $streamSessionId;

    public function __construct()
    {
        $this->brokerApiService = new BrokerApiService();
        // Аутентификация и получение streamSessionId
        $this->streamSessionId = $this->brokerApiService->login(
            config('xtb.userId'),
            config('xtb.password')
        );
    }

    public function execute(array $companies): array
    {
        $purchaseResults = [];

        foreach ($companies as $company) {
            $symbolInfo = $this->brokerApiService->getSymbol($company['symbol'], $this->streamSessionId);

            if ($this->isTradable($company)) {
                $transactionResult = $this->brokerApiService->buy($company['symbol'], $company['volume']);
                $purchaseResults[] = $transactionResult;
            }
            else throw new \Exception('not traidable');


        }

        $this->brokerApiService->logout();

        return $purchaseResults;
    }

    private function isTradable(array $company): bool
    {

        if (!$this->isWithinTradingHours($company['symbol'])) {
            return false;
        }

        if (!$this->isVolumeValid($company['symbol'],$company['volume'] )) {
            return false;
        }

        return $this->hasNoCommission($company['symbol'], $company['volume']);
    }

    private function isWithinTradingHours(string $symbol): bool
    {
        $tradingHoursData = $this->brokerApiService->getTradingHours([$symbol], $this->streamSessionId);
        $tradingHours = $tradingHoursData[0]['trading']; // Получение данных о торговых часах
        $currentTime = time();

        $currentCetTime = $this->convertToCetTime($currentTime);
        $currentDayOfWeek = date('N', $currentTime);

        foreach ($tradingHours as $record) {
            if ($record['day'] == $currentDayOfWeek) {
                $fromTime = $this->convertMillisecondsToTime($record['fromT']);
                $toTime = $this->convertMillisecondsToTime($record['toT']);

                if ($currentCetTime >= $fromTime && $currentCetTime <= $toTime) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isVolumeValid(string $symbol, float $volume): bool
    {
        $symbolInfo = $this->brokerApiService->getSymbol($symbol);

        if ($volume < $symbolInfo['lotMin']) {
            // Объем меньше минимального
            return false;
        }

        // Объем равен или больше минимального
        return true;
    }

    private function convertToCetTime($time): int
    {
        $dateTime = new DateTime("@$time");
        $dateTime->setTimezone(new DateTimeZone('Europe/Paris')); // CET/CEST
        return $dateTime->getTimestamp();
    }

    private function convertMillisecondsToTime($milliseconds): int
    {
        // Преобразование миллисекунд в часы и минуты
        $hours = floor($milliseconds / 3600000);
        $minutes = floor(($milliseconds % 3600000) / 60000);
        $seconds = floor(($milliseconds % 60000) / 1000);

        // Сборка времени в формате 'H:i:s' и конвертация в Unix timestamp
        $time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        $dateTime = DateTime::createFromFormat('H:i:s', $time, new DateTimeZone('Europe/Paris'));
        return $dateTime->getTimestamp();
    }

    private function hasNoCommission(string $symbol, float $volume): bool
    {
        $commissionInfo = $this->brokerApiService->getCommissionDef($symbol, $volume, $this->streamSessionId);
        return $commissionInfo['commission'] == 0;
    }

}
