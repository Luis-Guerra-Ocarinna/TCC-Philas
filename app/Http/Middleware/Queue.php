<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Utils\Arrays;
use Closure;

class Queue {

  /** @var array Mapeamento de middlewares */
  private static $map = [];

  /** @var array Mapeamento de middlewares que serão carregados em todas as rotas */
  private static $default = [];

  /** @var array Fila de middlewares a serem executados */
  private $middlewares = [];

  /** @var Closure Função de execução do controlador */
  private $controller;

  /** @var array Argumentos da função do controlador */
  private $controllerArgs = [];

  /** @var array Mapeamento de middle que serão carregados em determinados padrões de rotas */
  private static $defaultRoutes = [];


  /**
   * Método responsável por construir a classe de fila de middlewares
   *
   * @param   array    $middlewares     
   * @param   Closure  $controller      
   * @param   array    $controllerArgs
   * @param   Request  $request  
   */
  public function __construct(array $middlewares, Closure $controller, array $controllerArgs, Request $request) {
    $this->middlewares = self::$default;
    $this->setMiddlewaresPerRout($request);
    Arrays::mergeRight($this->middlewares, $middlewares);
    ksort($this->middlewares);

    $this->controller     = $controller;
    $this->controllerArgs = $controllerArgs;
  }

  /**
   * Método responsável por definir os middlewares por rotas
   *
   * @param   Request  $request
   */
  private function setMiddlewaresPerRout(Request $request): void {
    // ROTA ATUAL
    $uri = $request->getRouter()->getUri();

    foreach (self::$defaultRoutes as $key => $value) {
      // PADRÃO PRA ROTA "MÃE"
      $patternRoute = '/^\/' . $key . '.*$/';

      // VERIFICA SE A ROTA ATUAL PERTEMCE AO PADRÃO
      if (preg_match($patternRoute, $uri)) {
        Arrays::mergeRight($this->middlewares, $value);
      }
    }
  }

  /**
   * Método responsável por definir o mapeamento de middlewares
   *
   * @param   array  $map  
   */
  public static function setMap(array $map) {
    self::$map = $map;
  }

  /**
   * Método responsável por definir o mapeamento de middlewares padrões
   *
   * @param   array  $default  
   */
  public static function setDefault(array $default) {
    self::$default = $default;
  }

  /**
   * Método responsável por definir o mapeamento de middleware padrões por rotas
   *
   * @param   array  $defaultRoute  
   */
  public static function setDefaultPerRoutes(array $defaultRoutes) {
    self::$defaultRoutes = $defaultRoutes;
  }

  /**
   * Método reponsável por executar o próximo nivel da fila de middlewares
   *
   * @param   Request  $request  
   *
   * @return  Response           
   */
  public function next(Request $request) {
    // VERIFICA SE A FILA ESTÁ VAZIA
    if (empty($this->middlewares)) return call_user_func_array($this->controller, $this->controllerArgs);

    // MIDDLEWARE
    $middleware = array_shift($this->middlewares);

    // VERIFICA O MAPEAMENTO
    if (!isset(self::$map[$middleware])) throw new \Exception("Problemas ao processar o middleware da requisição", 500);

    // NEXT
    $queue = $this;
    $next = function ($request) use ($queue) {
      return $queue->next($request);
    };

    // EXECUTA O MIDDLEWARE
    return (new self::$map[$middleware])->handle($request, $next);
  }
}
