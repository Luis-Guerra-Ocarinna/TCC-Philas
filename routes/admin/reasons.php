<?php

use App\Controller\Admin;
use App\Http\Request;
use App\Http\Response;

// ROTA REASONS (ADMIN)
$obRouter->get('/admin/motivos', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Reasons::getReasons($request));
  }
]);

// ROTA VIEW DE CADASTRO REASONS (ADMIN)
$obRouter->get('/admin/motivos/new', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Reasons::getNewReason($request));
  }
]);

// ROTA DE CADASTRO REASONS (ADMIN)
$obRouter->post('/admin/motivos/new', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Reasons::setNewReason($request));
  }
]);

// ROTA VIEW DE EDITAR REASONS (ADMIN)
$obRouter->get('/admin/motivos/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Reasons::getEditReason($request, $id));
  }
]);

// ROTA POST DE EDITAR REASONS (ADMIN)
$obRouter->post('/admin/motivos/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Reasons::setEditReason($request, $id));
  }
]);

// ROTA VIEW DE DELETAR REASONS (ADMIN)
$obRouter->get('/admin/motivos/{id}/delete', [
  'middlewares' => [],
  function ($id) {
    return new Response(200, Admin\Reasons::getDeleteReason($id));
  }
]);

// ROTA POST DE DELETAR REASONS (ADMIN)
$obRouter->post('/admin/motivos/{id}/delete', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Reasons::setDeleteReason($request, $id));
  }
]);
