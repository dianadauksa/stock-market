<?php

namespace App\Repositories\UserStocks;

use App\Models\{User, UserStock, Collections\UserStocksCollection};
use GuzzleHttp\Client;

class FinnhubAPIUserStocksRepository implements UserStocksRepository
{
    public function getAll(array $stockSymbols): UserStocksCollection
    {
        // /search?q=AAPL Query text can be symbol, name, isin, or cusip
        //  /quote?symbol=AAPL
        $apiKey = $_ENV["API_KEY"];
        $baseUrl = $_ENV["BASE_URL"];
        $endPoint = "/quote?symbol=";

        $client = new Client();
        $user = new User($_SESSION['auth_id']);
        $userStocks = new UserStocksCollection();

        foreach ($stockSymbols as $symbol) {
            $query = "{$symbol}&token=" . $apiKey;
            $quoteUrl = $baseUrl . $endPoint . $query;
            $quoteResponse = $client->request('GET', $quoteUrl);
            $quoteData = json_decode($quoteResponse->getBody()->getContents());

            $userStock = $user->getStockBySymbol($symbol);
            $amountOwned = $userStock['amount'];
            $avgPrice = $userStock['avg_price'];

            $userStocks->add(new UserStock(
                $symbol,
                $quoteData->c,
                $amountOwned,
                $quoteData->c*$amountOwned - $avgPrice*$amountOwned,
                $avgPrice));
        }
        return $userStocks;
    }
}