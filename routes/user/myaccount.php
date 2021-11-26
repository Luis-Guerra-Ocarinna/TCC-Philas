<?php

use App\Controller\User;
use App\Http\Request;
use App\Http\Response;

// ROTA MINHA CONTA (ALTERAÇÃO)
$obRouter->get('/usuario/minhaConta', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, User\MyAccount::getMyAccount($request));
  }
]);

// ROTA MINHA CONTA (POST)
$obRouter->post('/usuario/minhaConta', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, User\MyAccount::setEditMyAccount($request));
  }
]);

// ROTA MINHA SENHA
$obRouter->get('/usuario/minhaConta/senha', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, User\MyAccount::getMyPassword($request));
  }
]);

// ROTA MINHA SENHA (POST)
$obRouter->post('/usuario/minhaConta/senha', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, User\MyAccount::setMyPassword($request));
  }
]);
