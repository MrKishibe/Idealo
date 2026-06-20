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
        // Define la ruta física hacia la carpeta de controladores
        $this->directory = dirname(__DIR__) . '/controllers/';

        if (isset($_GET['url']) && !empty($_GET['url'])) {
            $this->url = $_GET['url'];
        } else {
            $controller = $_GET['controller'] ?? null;
            $action = $_GET['action'] ?? null;
            if ($controller) {
                $this->url = $controller . '/' . ($action ?? 'index');
            } else {
                // Si accedes a la raíz, te redirige mediante la lógica procedimental limpia
                $this->url = 'auth/login';
            }
        }

        $this->validateURL();
    }

    private function validateURL(): void
    {
        // Se aplicó la expresión regular unificada del primer FrontController
        if (!preg_match("/^[a-zA-Z0-9-@\/.=:_#$ ]{1,700}$/", $this->url)) {
            $this->error404('URL no válida');
        }

        $this->processURL();
    }

    private function processURL(): void
    {
        $segments = explode('/', trim($this->url, '/'));

        if (!empty($segments[0])) {
            $this->controller = ucfirst($segments[0]) . 'Controller';
        }

        if (!empty($segments[1])) {
            $this->method = $segments[1];
        }

        $this->params = array_slice($segments, 2);

        $this->loadController();
    }

    private function loadController(): void
    {
        $file = $this->directory . $this->controller . '.php';

        // Lógica de validación física del archivo (Igual a tu primer ejemplo)
        if (!file_exists($file)) {
            $this->error404("Ese archivo no existe ({$this->controller}.php)");
        }

        // LÓGICA CLAVE: Se incluye el archivo.
        // Si tu controlador es procedimental, su código suelto se ejecutará AQUÍ MISMÓ 
        // y los `exit` o `require_once` internos de la vista detendrán el script.
        require_once $file;

        $fullClass = "Idealo\\Controllers\\" . $this->controller;

        // Si el script no se detuvo, verificamos si es una clase (Estructura POO)
        if (class_exists($fullClass)) {
            $controllerObject = new $fullClass();

            if (!method_exists($controllerObject, $this->method)) {
                $this->error404("El método {$this->method} no existe en la clase {$this->controller}");
            }

            call_user_func_array(
                [$controllerObject, $this->method],
                $this->params
            );
        } 
        // Si no es clase, pero definió una función con el nombre del método (procedimental estructurado)
        elseif (function_exists($this->method)) {
            call_user_func_array(
                $this->method,
                $this->params
            );
        }
        else {
            // Si es un archivo procedimental plano que no ejecuta código directo ni funciones
            // Simplemente lo dejamos pasar ya que el require_once cargó el archivo de forma plana.
            return;
        }
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