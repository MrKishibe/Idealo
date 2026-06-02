<?php

namespace Idealo\Controllers;

class FrontController
{

    private string $url;
    private string $directory;

    private string $controller = 'InicioController';
    private string $method = 'index';
    private array $params = [];

    public function __construct()
    {
        $this->directory = dirname(__DIR__) . '/controllers/';

        // Soporta tanto ?url=controlador/metodo como ?controller=...&action=...
        if (isset($_GET['url']) && !empty($_GET['url'])) {
            $this->url = $_GET['url'];
        } else {
            $controller = $_GET['controller'] ?? null;
            $action = $_GET['action'] ?? null;
            if ($controller) {
                $this->url = $controller . '/' . ($action ?? 'index');
            } else {
                $this->url = 'auth/login';
            }
        }

        $this->validateURL();
    }

    private function validateURL(): void
    {
        if (!preg_match('/^[a-zA-Z0-9\/-]+$/', $this->url)) {

            $this->error404('URL no válida');
        }

        $this->processURL();
    }

    private function processURL(): void
    {
        $segments = explode('/', trim($this->url, '/'));

        /*
        URL:
        usuario/editar/5

        [0] => usuario
        [1] => editar
        [2] => 5
        */

        // CONTROLADOR
        if (!empty($segments[0])) {

            $this->controller = ucfirst($segments[0]) . 'Controller';
        }

        // MÉTODO
        if (!empty($segments[1])) {

            $this->method = $segments[1];
        }

        // PARÁMETROS
        $this->params = array_slice($segments, 2);

        $this->loadController();
    }

    private function loadController(): void
    {
        $file = $this->directory . $this->controller . '.php';

        if (!file_exists($file)) {

            $this->error404("El controlador {$this->controller} no existe");
        }

        require_once $file;

        $fullClass = "Idealo\\Controllers\\" . $this->controller;

        if (!class_exists($fullClass)) {

            $this->error404("La clase {$fullClass} no existe");
        }

        $controllerObject = new $fullClass();

        if (!method_exists($controllerObject, $this->method)) {

            $this->error404("El método {$this->method} no existe");
        }

        call_user_func_array(
            [$controllerObject, $this->method],
            $this->params
        );
    }

    private function error404(string $message): void
    {
        http_response_code(404);

        die("
            <h1>Error 404</h1>
            <p>{$message}</p>
        ");
    }
}
