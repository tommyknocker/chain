<?php

require __DIR__ . '/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Account;

// Smart banking: apply bonus for high-value accounts, charge fees for low balances
$finalBalance = Chain::of(new Account())
    ->deposit(500)
    ->when(
        fn ($acc) => $acc->getBalance() > 300,
        fn ($chain) => $chain->addBonus()  // +100 bonus
    )
    ->unless(
        fn ($acc) => $acc->getBalance() < 100,
        fn ($chain) => $chain->withdraw(50)  // maintenance fee
    )
    ->getBalance()
    ->get();

echo "Final balance: $finalBalance\n"; // 550 (500 + 100 bonus - 50 fee)
