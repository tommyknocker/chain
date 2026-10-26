<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

match ($path) {
    '/first.txt' => printText('first'),
    '/second.txt' => printText('second'),
    '/image.jpg' => printImage(),
    '/json' => printJson(),
    '/auth' => authenticated(),
    '/large.txt' => printText(str_repeat('x', 128)),
    default => missing(),
};

function printText(string $body): never
{
    header('Content-Type: text/plain');
    echo $body;
    exit;
}

function printImage(): never
{
    header('Content-Type: image/jpeg');
    echo 'fake-image';
    exit;
}

function printJson(): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['query' => $_GET, 'body' => file_get_contents('php://input')]);
    exit;
}

function authenticated(): never
{
    if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Bearer test-token') {
        http_response_code(401);
        exit;
    }

    printJson();
}

function missing(): never
{
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'missing';
    exit;
}
