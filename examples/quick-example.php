<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\User;
use tommyknocker\chain\tests\fixtures\Order;

// Process user data through multiple transformations
$orderTotal = Chain::of(new User('Alice', 25))
    ->setEmail('alice@example.com')
    ->tap(fn($u) => print("Processing order for: " . $u->getName() . "\n"))
    ->map(fn($user) => new Order(strlen($user->getName()) * 10))
    ->when(
        fn($order) => $order->getTotal() > 30,
        fn($chain) => $chain->applyDiscount(5)
    )
    ->getTotal()
    ->get();

echo "Order total: $orderTotal\n"; // 45 (50 - 5 discount)

