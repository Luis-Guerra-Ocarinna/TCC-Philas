<?php

use App\Controller\Admin;
use App\Http\Request;
use App\Http\Response;

// ROTA HOME
$obRouter->get('/admin', [
  'middlewares' => [],
  function () {
    return new Response(200, Admin\Home::getHome());
  }
]);
