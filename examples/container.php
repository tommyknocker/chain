<?php

require __DIR__ . '/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\EmailService;
use tommyknocker\chain\tests\fixtures\NotificationService;
use tommyknocker\chain\tests\fixtures\SimpleContainer;

// Setup container
$container = new SimpleContainer();
$container->set('email', new EmailService());
$container->set('notification', new NotificationService());

// Set a resolver default for chains created after this call
Chain::setResolver($container);

// Change between services dynamically
$result1 = Chain::of($container->get('email'))
    ->send('user@example.com')
    ->get();

$result2 = Chain::of($container->get('email'))
    ->withResolver($container) // Optional local resolver for this chain
    ->change('notification')
    ->notify('Important update')
    ->get();

echo "$result1\n";
echo "$result2\n";
