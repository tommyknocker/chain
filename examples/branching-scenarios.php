<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Calculator;

// Calculate different pricing scenarios from same base
$baseCalc = Chain::of(new Calculator(100));

$retailPrice = $baseCalc->clone()->multiply(1.5)->getValue()->get();  // 150
$wholesalePrice = $baseCalc->clone()->multiply(1.2)->getValue()->get();  // 120
$memberPrice = $baseCalc->clone()->multiply(0.9)->getValue()->get();  // 90

echo "Retail price: $retailPrice\n";
echo "Wholesale price: $wholesalePrice\n";
echo "Member price: $memberPrice\n";

