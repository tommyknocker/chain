<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Account;

// Explore different account scenarios without mutating original
$baseAccount = Chain::of(new Account());

$scenario1 = $baseAccount->clone()
    ->deposit(1000)
    ->withdraw(200)
    ->getBalance()->get();  // 800

$scenario2 = $baseAccount->clone()
    ->deposit(500)
    ->addBonus()
    ->getBalance()->get();  // 600 (500 + 100 bonus)

$original = $baseAccount->getBalance()->get();  // 0 (unchanged)

echo "Scenario 1: $scenario1\n";
echo "Scenario 2: $scenario2\n";
echo "Original: $original\n";

