<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\StringBuilder;

// Using Chain::of to instantiate and chain
$result = Chain::of(StringBuilder::class, 'Hello')
    ->append(' ')
    ->append('World')
    ->prepend('>>> ')
    ->uppercase()
    ->toString()
    ->get();

echo "$result\n";

