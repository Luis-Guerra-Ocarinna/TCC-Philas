<?php

namespace App\Http\Middleware;

use App\Http\Request;
use Closure;

class Maintenance implements MiddlewareInterface  {

  /**
   * Método responsável por executar o middleware
   *
   * @param   Request  $request  
   * @param   Closure  $next     
   *
   * @return  Closure           
   */
  public function handle(Request $request, Closure $next) {
    // VERIFICA O ESTADO DE MANUTENÇÃO DA PÁGINA
    if (getenv('MAINTENANCE') == 'true') throw new \Exception("Página em manutenção. Tente mais tarde.", 503);

    // EXECUTA O PRÓXIMO NÍVEL DO MIDDLEWARE
    return $next($request);
  }
}
