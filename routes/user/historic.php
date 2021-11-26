<?php

use App\Controller\User\Schedules;
use App\Http\Request;
use App\Http\Response;

// ROTA HISTÓRICO
$obRouter->get('/usuario/historico', [
  'middlewares' => [
    'required-login'
  ],
  function (Request $request) {
    return new Response(200, Schedules\Historic::getHistoric($request));
  }
]);


// ROTA DE ATENDIMENTOS ABERTOS
$obRouter->get('/usuario/atendimento/abertos', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Schedules\Historic::getOpenSchedules($request));
  }
]);

// ROTA DE ATENDIMENTOS ABERTOS
$obRouter->get('/usuario/atendimento/abertos/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Schedules\Historic::getEditOpenSchedules($request, $id));
  }
]);

// ROTA DE ATENDIMENTOS ABERTOS
$obRouter->post('/usuario/atendimento/abertos/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Schedules\Historic::setEditOpenSchedules($request, $id));
  }
]);

// ROTA VIEW DE DELETAR ATENDIMENTOS ABERTOS
$obRouter->get('/usuario/atendimento/abertos/{id}/delete', [
  'middlewares' => [],
  function ($id) {
    return new Response(200, Schedules\Historic::getDeleteOpenSchedules($id));
  }
]);

// ROTA DE DELETAR ATENDIMENTOS ABERTOS
$obRouter->post('/usuario/atendimento/abertos/{id}/delete', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Schedules\Historic::setDeleteOpenSchedules($request, $id));
  }
]);

// ROTA DE ATENDIMENTOS A SEREM CONFIRMADOS
$obRouter->get('/usuario/atendimento/confirmar', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Schedules\Historic::getConfirmSchedules($request));
  }
]);

// ROTA DE ATENDIMENTOS A SEREM CONFIRMADOS (EDIT)
$obRouter->get('/usuario/atendimento/confirmar/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Schedules\Historic::getEditConfirmSchedules($request, $id));
  }
]);

// ROTA DE ATENDIMENTOS A SEREM CONFIRMADOS (EDIT) - POST
$obRouter->post('/usuario/atendimento/confirmar/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Schedules\Historic::setEditConfirmSchedules($request, $id));
  }
]);

// ROTA DE ATENDIMENTOS A SEREM CONFIRMADOS (DELETE)
$obRouter->get('/usuario/atendimento/confirmar/{id}/delete', [
  'middlewares' => [],
  function ($id) {
    return new Response(200, Schedules\Historic::getDeleteConfirmSchedules($id));
  }
]);

// ROTA DE ATENDIMENTOS A SEREM CONFIRMADOS (DELETE) - POST
$obRouter->post('/usuario/atendimento/confirmar/{id}/delete', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Schedules\Historic::setDeleteConfirmSchedules($request, $id));
  }
]);
