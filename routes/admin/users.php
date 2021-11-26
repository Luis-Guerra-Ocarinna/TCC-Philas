<?php

use App\Controller\Admin;
use App\Http\Request;
use App\Http\Response;

// ROTA USUARIOS (ADMIN)
$obRouter->get('/admin/usuarios', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Users::getUsers($request));
  }
]);

// ROTA VIEW DE CADASTRO DE USUARIOS (ADMIN)
$obRouter->get('/admin/usuarios/new', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Users::getNewUser($request));
  }
]);

// ROTA POST DE CADASTRO DE USUARIOS (ADMIN)
$obRouter->post('/admin/usuarios/new', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Users::setNewUser($request));
  }
]);

// ROTA VIEW DE EDITAR USUARIOS (ADMIN)
$obRouter->get('/admin/usuarios/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Users::getEditUser($request, $id));
  }
]);

// ROTA POST DE EDITAR USUARIOS (ADMIN)
$obRouter->post('/admin/usuarios/{id}/edit', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Users::setEditUser($request, $id));
  }
]);

// ROTA VIEW DE DELETAR USUARIOS (ADMIN)
$obRouter->get('/admin/usuarios/{id}/delete', [
  'middlewares' => [],
  function ($id) {
    return new Response(200, Admin\Users::getDeleteUser($id));
  }
]);

// ROTA POST DE DELETAR USUARIOS (ADMIN)
$obRouter->post('/admin/usuarios/{id}/delete', [
  'middlewares' => [],
  function (Request $request, $id) {
    return new Response(200, Admin\Users::setDeleteUser($request, $id));
  }
]);