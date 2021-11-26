<?php

use App\Controller\Admin;
use App\Http\Response;
use App\Http\Request;

// ROTA VIEW CONTENT (ADMIN)
$obRouter->get('/admin/conteudo', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Content::getContent($request));
  }
]);

// ROTA POST CONTENT (ADMIN)
$obRouter->post('/admin/conteudo', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Admin\Content::setContent($request));
  }
]);
