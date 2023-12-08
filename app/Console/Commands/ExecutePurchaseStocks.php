<?php

namespace App\Console\Commands;

use App\Services\ArrayTransformer;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Console\Command;
use App\Actions\PurchaseStocksAction;
use App\Services\BrokerApiService;
use Illuminate\Support\Arr;

class ExecutePurchaseStocks extends Command
{
    protected $signature = 'stocks:purchase';
    protected $description = 'Execute the purchase stocks action';


    public function handle()
    {
        $spreadsheetId = config('google.sheet_id');// ID таблицы Google Sheets
        $range = config('google.range'); // Диапазон ячеек для извлечения данных
        $googleSheetService = new GoogleSheetService($spreadsheetId);
        $data = $googleSheetService->getSpreadsheetData($range);

        $data = (ArrayTransformer::transformToAssociative($data));

        $companies_for_purchase = ArrayTransformer::transformArrayForBuying($data);
        $purchaseStocksAction = new PurchaseStocksAction();

        try {
            $purchaseResults = $purchaseStocksAction->execute($companies_for_purchase);
            $this->info('Stocks have been successfully purchased.');
        } catch (Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
