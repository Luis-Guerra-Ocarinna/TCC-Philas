<?php

// INCLUI ROTAS PADRÕES DA API (V1)
include_once(__DIR__ . '/api/v1/default.php');

// INCLUI ROTAS DE USUÁRIOS
include_once(__DIR__ . '/api/v1/users.php');

// INCLUI AS ROTAS DE MOTIVOS
include_once(__DIR__ . '/api/v1/reasons.php');

// INCLUI AS ROTAS DE ATENDIMENTOS
include_once(__DIR__ . '/api/v1/schedules.php');
