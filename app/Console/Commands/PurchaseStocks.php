<?php

namespace App\Console\Commands;

use Exception;
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
            ['symbol' => 'SUSHI', 'volume' => 100.0],
            ['symbol' => 'STEPN', 'volume' => 250.0],
            ['symbol' => 'KUSAMA', 'volume' => 1.0],
            ['symbol' => 'GALA', 'volume' => 2500.0],
            ['symbol' => 'GRAPH', 'volume' => 1000.0],
//            ['symbol' => 'PZU.PL_4', 'volume' => 1.0],
//            ['symbol' => 'LPP.PL_4', 'volume' => 1], // крипта STEPN
//            ['symbol' => 'PKN.PL_4', 'volume' => 1.0],
        ];

        try {
            $purchaseResults = $purchaseStocksAction->execute($companies);
            $this->info('Stocks have been successfully purchased.');
        } catch (Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
