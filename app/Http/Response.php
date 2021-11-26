<?php

namespace App\Http;

class Response {

  /** @var integer Código do Status HTTP */
  private $httpCode = 200;

  /** @var array Cabeçalho do Response */
  private $headers = [];

  /** @var string Tipo de conteúdo que está sendo retornado */
  private $contentType = 'text/html';

  /** @var mixed Conteúdo do Response */
  private $content;

  /**
   * Método responsável por iniciar a classe e definir os valores
   *
   * @param   integer $httpCode     
   * @param   mixed   $content      
   * @param   string  $contentType  
   */
  public function __construct(int $httpCode, mixed $content, ?string $contentType = null) {
    $contentType = $contentType ?? $this->contentType;

    $this->httpCode = $httpCode;
    $this->content = $content;
    $this->setContentType($contentType);
  }

  /**
   * Método responsável por alterar o content type do response
   *
   * @param   string  $contentType  
   */
  public function setContentType(string $contentType) {
    $this->contentType = $contentType;
    $this->addHeader('Content-Type', [$contentType]);
  }

  /**
   * Método responsável por adicionar um registro no cabeçalho de response
   *
   * @param   string  $key
   * @param   array  $value
   */
  public function addHeader(string $key, array $value) {
    $this->headers[$key] = $value;
  }

  /**
   * Método responsável por enviar os header para o navegador
   */
  private function sendHeaders() {
    // STATUS
    http_response_code($this->httpCode);

    // ENVIAR HEADERS
    foreach ($this->headers as $key => $value) {
      // CASO HAJA HEADERS MULTIVALORADOS
      foreach ($value as $v) {
        header($key . ': ' . $v, false);
      }
    }
  }

  /**
   * Método responsável por enviar a resposta para o usuário
   */
  public function sendResponse() {
    // ENIVA OS HEADERS
    $this->sendHeaders();

    // IMPREME O CONTEÚDO
    switch ($this->contentType) {
      case 'text/html':
        echo $this->content;
        exit;
      case 'application/json':
        echo json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
  }

  /**
   * Médodo responsável por enviar o redirecionamento
   *
   * @param   string  $url
   *
   * @return  never
   */
  public function sendRedirect(string $url): void {
    // ADICIONA O REDIRECIONAMENTO
    $this->addHeader('Location', [$url]);

    // ENIVA OS HEADERS (E REDIRECIONA)
    $this->sendHeaders();

    exit;
  }
}
