<?php

require_once(__DIR__ . '/includes/app.php');

use App\Http\Router;

// INICIA O ROTEADOR
$obRouter = new Router(URL);

// INCLUI AS ROTAS DAS PÁGINAS PRINCIPAIS
include_once(__DIR__ . '/routes/index.php');

// INCLUI AS ROTAS DAS PÁGINAS DO USUÁRIO
include_once(__DIR__ . '/routes/user.php');

// INCLUI AS ROTAS DAS PÁGINAS DO ADMIN
include_once(__DIR__ . '/routes/admin.php');

// INCLUI AS ROTAS DAS APIS
include_once(__DIR__ . '/routes/api.php');

// IMPRIME O RESPONSE DA ROTA
$obRouter->run()->sendResponse();

// REMINDER: IMAGEM BASE64
// REMINDER: Exlucao de chave estrabgeiras (zap)
// FIXME: SQL Injection