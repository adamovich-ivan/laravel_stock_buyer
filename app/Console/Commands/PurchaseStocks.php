<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\PurchaseStocksAction;
use App\Services\BrokerApiService;

class PurchaseStocks extends Command
{
    protected $signature = 'stocks:purchase';
    protected $description = 'Execute the purchase stocks action';



    public function handle()
    {

        $purchaseStocksAction = new PurchaseStocksAction();

        $companies = [
            ['symbol' => 'AAPL', 'volume' => 1.0],
            ['symbol' => 'GOOGL', 'volume' => 1.0],
            ['symbol' => 'MSFT', 'volume' => 1.0]
        ];

        try {
            $purchaseResults = $purchaseStocksAction->execute($companies);
            $this->info('Stocks have been successfully purchased.');
        } catch (Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
