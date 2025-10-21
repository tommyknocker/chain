<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Account;

// Conditional execution with when/unless
$balance = Chain::of(new Account())
    ->deposit(500)
    ->when(
        fn($acc) => $acc->getBalance() > 300,
        fn($chain) => $chain->addBonus()
    )
    ->unless(
        fn($acc) => $acc->getBalance() < 100,
        fn($chain) => $chain->withdraw(50)
    )
    ->getBalance()
    ->get();

echo "Final balance: $balance\n";

