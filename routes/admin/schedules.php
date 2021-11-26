<?php

use App\Controller\Admin;
use App\Http\Request;
use App\Http\Response;

// ROTA ATENDIMENTOS (ADMIN)
$obRouter->get('/admin/atendimentos', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Schedules::getSchedules($request));
  }
]);

// ROTA INICIAR ATENDIMENTO (ADMIN)
$obRouter->get('/admin/atendimentos/{id}/start', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Schedules::getStartSchedule($request, $id));
  }
]);

// ROTA FINALIZAR ATENDIMENTO (ADMIN) - POST
$obRouter->post('/admin/atendimentos/{id}/start', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Schedules::setEndSchedule($request, $id));
  }
]);

// ROTA CADASTRO DE ATENDIMENTOS (ADMIN)
$obRouter->get('/admin/atendimentos/new', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Schedules::getNewSchedules($request));
  }
]);

// ROTA CADASTRO DE ATENDIMENTOS (ADMIN) - POST
$obRouter->post('/admin/atendimentos/new', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Schedules::setNewSchedules($request));
  }
]);

// ROTA ATENDIMENTOS FINALIZADOS (ADMIN)
$obRouter->get('/admin/atendimentos/historic', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Schedules::getHistoricSchedules($request));
  }
]);

// ROTA ATENDIMENTOS ABERTOS (ADMIN)
$obRouter->get('/admin/atendimentos/open', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Schedules::getOpenSchedules($request));
  }
]);

// ROTA ATENDIMENTOS A SEREM CONFIRMADOS (ADMIN)
$obRouter->get('/admin/atendimentos/confirm', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Schedules::getConfirmSchedules($request));
  }
]);

// ROTA EDIÇÃO DE ATENDIMENTOS (ADMIN)
$obRouter->get('/admin/atendimentos/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Schedules::getEditSchedule($request, $id));
  }
]);

// ROTA EDIÇÃO DE ATENDIMENTOS (ADMIN) - POST
$obRouter->post('/admin/atendimentos/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Schedules::setEditSchedule($request, $id));
  }
]);

// ROTA EXCLUSÃO DE ATENDIMENTOS (ADMIN)
$obRouter->get('/admin/atendimentos/{id}/delete', [
  'middlewares' => [],
  function ($id) {
    return new Response(200, Admin\Schedules::getDeleteSchedule($id));
  }
]);

// ROTA EXCLUSÃO DE ATENDIMENTOS (ADMIN) - POST
$obRouter->post('/admin/atendimentos/{id}/delete', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Schedules::setDeleteSchedule($request, $id));
  }
]);
