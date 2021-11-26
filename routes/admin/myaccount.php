<?php

use App\Controller\Admin;
use App\Http\Request;
use App\Http\Response;

// ROTA MINHA CONTA (ALTERAÇÃO)
$obRouter->get('/admin/minhaConta', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\MyAccount::getMyAccount($request));
  }
]);

// ROTA MINHA CONTA (POST)
$obRouter->post('/admin/minhaConta', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\MyAccount::setEditMyAccount($request));
  }
]);

// ROTA MINHA SENHA
$obRouter->get('/admin/minhaConta/senha', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\MyAccount::getMyPassword($request));
  }
]);

// ROTA MINHA SENHA (POST)
$obRouter->post('/admin/minhaConta/senha', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\MyAccount::setMyPassword($request));
  }
]);
