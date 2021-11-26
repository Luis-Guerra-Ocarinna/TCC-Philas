<?php

namespace App\Http\Middleware;

use App\Http\Request;
use Closure;

class Api implements MiddlewareInterface {

  /**
   * Método responsável por executar o middleware
   *
   * @param   Request  $request  
   * @param   Closure  $next     
   *
   * @return  Closure           
   */
  public function handle(Request $request, Closure $next) {
    // ALTERA O CONTENT TYPE PARA JSON
    $request->getRouter()->setContentType('application/json');

    // EXECUTA O PRÓXIMO NÍVEL DO MIDDLEWARE
    return $next($request);
  }
}
