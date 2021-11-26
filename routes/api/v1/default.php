<?php

use App\Controller\Api;
use App\Http\Request;
use App\Http\Response;

// ROTA RAIZ DA API (v1)
$obRouter->get('/api/v1', [
  function () {
    return new Response(200, Api\Api::getDetails(), 'application/json');
  }
]);

// ROTA PARA GERAR AUTORIZAÇÃO PARA API
$obRouter->post('/api/v1/auth', [
  function (Request $request) {
    return new Response(200, Api\Api::genarateToken($request), 'application/json');
  }
]);

// ROTA PARA CONFIRMAÇÃO DE EMAIL
$obRouter->get('/api/v1/confirmEmail', [
  function (Request $request) {
    return new Response(200, Api\Api::confirmEmail($request), 'application/json');
  }
]);

// ROTA PARA ALTERAÇÃO DE EMAIL
$obRouter->get('/api/v1/changeEmail', [
  'middlewares' => ['api-auth'],
  function (Request $request) {
    return new Response(200, Api\Api::changeEmail($request), 'application/json');
  }
]);
