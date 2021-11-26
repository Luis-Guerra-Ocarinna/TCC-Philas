<?php

use App\Controller\User\Schedules;
use App\Http\Request;
use App\Http\Response;

// ROTA ATENDIMENTO (AGENDAR)
$obRouter->get('/usuario/atendimento', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Schedules\Schedule::getNewSchedules($request));
  }
]);

// ROTA ATENDIMENTO (POST)
$obRouter->post('/usuario/atendimento', [
  'middlewares' => [],
  function (Request $request) {
    return new Response(200, Schedules\Schedule::setNewSchedules($request));
  }
]);
