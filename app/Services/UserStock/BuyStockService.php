<?php

namespace App\Services\UserStock;

use App\Database;
use App\Models\User;
use App\Services\Stock\ShowAllStocksService;

class BuyStockService
{
    private ShowAllStocksService $service;

    public function __construct(ShowAllStocksService $service)
    {
        $this->service = $service;
    }

    public function execute(string $stockSymbol, int $buyAmount, User $user): void
    {
        $userAmountOfStock = $user->getStockBySymbol($stockSymbol)['amount'];

        if ($userAmountOfStock == null) {
            $this->insertNewStock($stockSymbol, $buyAmount, $user);
            $this->insertTransaction('BUY', $stockSymbol, $buyAmount, $user);

            $total = $buyAmount * $this->getPrice($stockSymbol);
            $_SESSION['success']['purchase'] =
                "Successfully bought $buyAmount shares of $stockSymbol for $ $total";
        } elseif ($userAmountOfStock > 0) {
            $this->updateExistingStock($stockSymbol, $buyAmount, $user);
            $this->insertTransaction('BUY', $stockSymbol, $buyAmount, $user);

            $total = $buyAmount * $this->getPrice($stockSymbol);
            $_SESSION['success']['purchase'] =
                "Successfully bought $buyAmount shares of $stockSymbol for $ $total";
        } elseif ($userAmountOfStock == -$buyAmount) {
            $this->deleteStock($stockSymbol, $user);
            $this->insertTransaction('CLOSE SHORTLIST', $stockSymbol, $buyAmount, $user);

            $total = $buyAmount * $this->getPrice($stockSymbol);
            $_SESSION['success']['purchase'] =
                "Successfully closed shortlisted $buyAmount shares of $stockSymbol for $ $total";
        } elseif ($userAmountOfStock < 0) {
            $this->updateShortlist($stockSymbol, $buyAmount, $user);
            $this->insertTransaction('DECREASE SHORTLIST', $stockSymbol, $buyAmount, $user);

            $total = $buyAmount * $this->getPrice($stockSymbol);
            $_SESSION['success']['purchase'] =
                "Updated shortlist: $buyAmount additional shares of $stockSymbol for $ $total";
        }

        $this->updateUserMoney($stockSymbol, $buyAmount, $user);
    }

    private function getPrice(string $stockSymbol): float
    {
        $stock = $this->service->executeSingle($stockSymbol);
        return $stock->getCurrentPrice();
    }

    private function insertNewStock(string $stockSymbol, int $buyAmount, User $user): void
    {
        $connection = Database::getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert('stocks')
            ->values([
                'user_id' => '?',
                'symbol' => '?',
                'amount' => '?',
                'avg_price' => '?',
            ])
            ->setParameter(0, $user->getId())
            ->setParameter(1, $stockSymbol)
            ->setParameter(2, $buyAmount)
            ->setParameter(3, $this->getPrice($stockSymbol))
            ->executeQuery();
    }

    private function updateExistingStock(string $stockSymbol, int $buyAmount, User $user): void
    {
        $userStock = $user->getStockBySymbol($stockSymbol);
        $newAvgPrice = ($userStock['amount'] * $userStock['avg_price'] + $buyAmount * $this->getPrice($stockSymbol)) / ($buyAmount + $userStock['amount']);

        $connection = Database::getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->update('stocks')
            ->set('avg_price', '?')
            ->set('amount', 'amount + ?')
            ->where('user_id = ?')
            ->andWhere('symbol = ?')
            ->setParameter(0, $newAvgPrice)
            ->setParameter(1, $buyAmount)
            ->setParameter(2, $user->getId())
            ->setParameter(3, $stockSymbol)
            ->executeQuery();
    }

    private function updateShortlist(string $stockSymbol, int $buyAmount, User $user): void
    {
        $userStock = $user->getStockBySymbol($stockSymbol);
        $newAvgPrice = ($buyAmount * $this->getPrice($stockSymbol) - $userStock['amount'] * $userStock['avg_price']) / ($buyAmount - $userStock['amount']);

        $connection = Database::getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->update('stocks')
            ->set('avg_price', '?')
            ->set('amount', 'amount + ?')
            ->where('user_id = ?')
            ->andWhere('symbol = ?')
            ->setParameter(0, $newAvgPrice)
            ->setParameter(1, $buyAmount)
            ->setParameter(2, $user->getId())
            ->setParameter(3, $stockSymbol)
            ->executeQuery();
    }

    private function updateUserMoney(string $stockSymbol, int $buyAmount, User $user): void
    {
        $moneyLeft = $user->getMoney() - $buyAmount * $this->getPrice($stockSymbol);
        $connection = Database::getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->update('users')
            ->set('money', '?')
            ->where('id = ?')
            ->setParameter(0, $moneyLeft)
            ->setParameter(1, $user->getId())
            ->executeQuery();
    }

    private function insertTransaction(string $type, string $stockSymbol, int $buyAmount, User $user): void
    {
        $connection = Database::getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert('transactions')
            ->values([
                'user_id' => '?',
                'type' => '?',
                'symbol' => '?',
                'amount' => '?',
                'price' => '?',
                'total_sum' => '?',
                'date' => '?',
            ])
            ->setParameter(0, $user->getId())
            ->setParameter(1, $type)
            ->setParameter(2, $stockSymbol)
            ->setParameter(3, $buyAmount)
            ->setParameter(4, $this->getPrice($stockSymbol))
            ->setParameter(5, $buyAmount * $this->getPrice($stockSymbol))
            ->setParameter(6, date('Y-m-d H:i:s'))
            ->executeQuery();
    }

    private function deleteStock(string $stockSymbol, User $user): void
    {
        $connection = Database::getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->delete('stocks')
            ->where('user_id = ?')
            ->andWhere('symbol = ?')
            ->setParameter(0, $user->getId())
            ->setParameter(1, $stockSymbol)
            ->executeQuery();
    }
}