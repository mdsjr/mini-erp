<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/PHPMailer.php';
require_once __DIR__ . '/../libs/SMTP.php';
require_once __DIR__ . '/../libs/Exception.php';

// Autoload para carregar classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Roteamento simples
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/mini_erp/public/';
$route = str_replace($base_path, '', $request_uri);

switch ($route) {
    case '':
    case 'produtos':
        $controller = new App\Controllers\ProdutoController();
        $controller->index();
        break;
    case 'produtos/adicionar':
        $controller = new App\Controllers\ProdutoController();
        $controller->adicionar();
        break;
    case 'cupons':
        $controller = new App\Controllers\CupomController();
        $controller->index();
        break;
    case 'cupons/adicionar':
        $controller = new App\Controllers\CupomController();
        $controller->adicionar();
        break;
    case 'checkout':
        $controller = new App\Controllers\CheckoutController();
        $controller->index();
        break;
    case 'webhook':
        $controller = new App\Controllers\WebhookController();
        $controller->handle();
        break;
    default:
        http_response_code(404);
        echo "Página não encontrada.";
        exit;
}
