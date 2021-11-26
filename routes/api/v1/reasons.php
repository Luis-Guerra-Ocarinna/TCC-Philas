<?php

use App\Controller\Api;
use App\Http\Request;
use App\Http\Response;

// ROTA DE LISTAGEM DE TODOS OS MOTIVOS
$obRouter->get('/api/v1/reasons', [
  function (Request $request) {
    return new Response(200, Api\Reason::getReasons($request), 'application/json');
  }
]);

// ROTA DE CADASTRO DOS MOTIVOS
$obRouter->post('/api/v1/reasons', [
  'middlewares' => [
    'api-auth',
    'admin'
  ],
  function (Request $request) {
    return new Response(201, Api\Reason::setNewReason($request), 'application/json');
  }
]);

// ROTA DE ATUALIZAÇÃO DE MOTIVOS
$obRouter->put('/api/v1/reasons/{id}', [
  'middlewares' => [
    'api-auth',
    'admin'
  ],
  function (Request $request, $id) {
    return new Response(200, Api\Reason::setEditReason($request, $id), 'application/json');
  }
]);

// ROTA DE ATUALIZAÇÃO DE MOTIVOS
$obRouter->delete('/api/v1/reasons/{id}', [
  'middlewares' => [
    'api-auth',
    'admin'
  ],
  function ($id) {
    return new Response(200, Api\Reason::setDeleteReason($id), 'application/json');
  }
]);
