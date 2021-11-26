<?php

namespace App\Controller\Admin;

use App\Utils\View;

class Home extends Page {

  /**
   * Método responsável por retornar o conteúdo (VIEW) da Home
   *
   * @return  string  
   */
  public static function getHome(): string {
    // VIEW DA HOME
    $content =  View::render('admin/home');

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Início', $content);
  }
}
