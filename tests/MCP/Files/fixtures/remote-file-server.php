<?php

// Router for the PHP built-in server that StreamRemoteFileTransportTest downloads from.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/redirect') {
    header('Location: /file', true, 302);
    echo 'the body of a redirect';

    return;
}

if ($path === '/missing') {
    http_response_code(404);
    echo 'the body of a 404';

    return;
}

if ($path === '/bytes') {
    echo str_repeat('a', (int) ($_GET['count'] ?? 0));

    return;
}

if ($path === '/slow') {
    sleep(2);
    echo 'too late';

    return;
}

header('Content-Type: application/json');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'host' => $_SERVER['HTTP_HOST'] ?? null,
    'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
]);
