<?php

// Entrada temporal para compartir exclusivamente la demo pública, en loopback.
$publicRoot = realpath(__DIR__.'/../app/public');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

if (preg_match('#^/(?:css/store\.css|images/[a-zA-Z0-9_-]+\.svg|favicon\.ico)$#D', $path)) {
    $asset = realpath($publicRoot.$path);
    if ($asset && str_starts_with($asset, $publicRoot.DIRECTORY_SEPARATOR) && is_file($asset)) {
        $types = ['css' => 'text/css; charset=utf-8', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon'];
        header('Content-Type: '.$types[pathinfo($asset, PATHINFO_EXTENSION)]);
        readfile($asset);
        return true;
    }
}

if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /\n";
    return true;
}

if (! preg_match('#^/(?:productos/[a-zA-Z0-9_-]+|carro(?:/[a-zA-Z0-9_-]+)?)?/?$#D', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Página no disponible.';
    return true;
}

// Este servidor se usa exclusivamente detrás del túnel HTTPS de Cloudflare.
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
require $publicRoot.'/index.php';
