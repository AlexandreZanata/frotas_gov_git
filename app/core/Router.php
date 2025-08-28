<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];

    public function get($uri, $controller)
    {
        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller)
    {
        $this->routes['POST'][$uri] = $controller;
    }

    public function dispatch($request)
    {
        $uri = $request->getUri();
        $method = $request->getMethod();

        if (array_key_exists($uri, $this->routes[$method])) {
            $this->callAction(
                ...explode('@', $this->routes[$method][$uri])
            );
        } else {
            // Se a rota não for encontrada, exibe um erro 404
            http_response_code(404);
            echo "<h1>404 - Página não encontrada</h1>";
        }
    }

    protected function callAction($controller, $action)
    {
        $controllerFile = __DIR__ . "/../controllers/{$controller}.php";
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            if (class_exists($controller)) {
                $controllerInstance = new $controller();
                if (method_exists($controllerInstance, $action)) {
                    $controllerInstance->$action();
                } else {
                    echo "Método {$action} não encontrado no controller {$controller}.";
                }
            } else {
                echo "Classe {$controller} não encontrada.";
            }
        } else {
            echo "Arquivo do controller {$controller} não encontrado.";
        }
    }
}