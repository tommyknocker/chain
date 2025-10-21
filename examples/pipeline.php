<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Calculator;

// Functional pipeline: (10 + 0) * 2 = 20, then 20 + 5 = 25
$result = Chain::of(new Calculator())
    ->add(10)
    ->pipe(
        fn($c) => $c->multiply(2),
        fn($c) => $c->getValue(),
        fn($v) => $v + 5
    )
    ->get();

echo "Pipeline result: $result\n";
