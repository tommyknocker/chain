<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\User;
use tommyknocker\chain\tests\fixtures\Order;

// Basic chaining with map
$result = Chain::of(new User('Alice', 25))
    ->getName()  // Direct method call via __call()
    ->map(fn($user) => new Order(strlen($user->getName()) * 10))  // Map to new object
    ->getTotal()  // Get the total
    ->get();  // Retrieve final result

echo "Order total: $result\n";

