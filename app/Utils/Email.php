<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Classe responsável por controlar envios de emails
 */
class Email {

  /**
   * Método responsável por enviar emails de confirmação
   */
  public static function sendConfirmEmail($obUser) {
    $mail = new PHPMailer(true);
    $link = URL . '/api/v1/confirmEmail/?token=' . JWT::encode($obUser, getenv('JWT_KEY'));
    $link = '<a href="' . $link . '" target="_blank" aria-label="Confirmar Email">confirmar</a>';

    try {
      $mail->isSMTP();
      $mail->Host     = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'philasgouveanetto@gmail.com';
      $mail->Password = getenv('GMAIL_PW');
      $mail->Port     = 587;

      $mail->setFrom('philasgouveanetto@gmail.com');
      $mail->addAddress($obUser->email);
      $mail->addReplyTo('philasgouveanetto@gmail.com', 'Informação');

      $mail->isHTML(true);
      $mail->Subject = 'Confirme seu E-mail';
      $mail->Body    = '<h1>Obrigado por usar o Philas</h1> <br> Confirme seu E-mail no link a seguir: ' . $link;
      $mail->AltBody = 'Obrigado por usar o Philas Confirme seu E-mail no link a seguir: ' . $link;

      $mail->send();
    } catch (\Exception $e) {
      throw new \Exception("Erro ao enviar mensagem: {$mail->Errorinfo}", 500);
    }
  }

  /**
   * Método responsável por enviar emails de alteração de email
   */
  public static function sendChangeEmail(string $email) {
    $mail = new PHPMailer(true);
    $link = URL . '/api/v1/changeEmail/?token=' . JWT::encode($email, getenv('JWT_KEY'));
    $link = '<a href="' . $link . '" target="_blank" aria-label="Confirmar Novo Email">confirmar</a>';

    try {
      $mail->isSMTP();
      $mail->Host     = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'philasgouveanetto@gmail.com';
      $mail->Password = getenv('GMAIL_PW');
      $mail->Port     = 587;

      $mail->setFrom('philasgouveanetto@gmail.com');
      $mail->addAddress($email);
      $mail->addReplyTo('philasgouveanetto@gmail.com', 'Informação');

      $mail->isHTML(true);
      $mail->Subject = 'Confirme seu novo E-mail';
      $mail->Body    = '<h1>Obrigado por usar o Philas</h1> <br/> Confirme seu novo E-mail no link a seguir:' . $link;
      $mail->AltBody = 'Obrigado por usar o Philas Confirme seu novo E-mail no link a seguir: ' . $link;

      $mail->send();
    } catch (\Exception $e) {
      throw new \Exception("Erro ao enviar mensagem: {$mail->Errorinfo}", 500);
    }
  }

  /**
   * Método responsável por enviar emails de mensagens
   */
  public static function sendEmail($nome, $email, $mensagem) {
    $mail = new PHPMailer(true);

    try {
      $mail->isSMTP();
      $mail->Host     = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'philasgouveanetto@gmail.com';
      $mail->Password = getenv('GMAIL_PW');
      $mail->Port     = 587;

      $mail->setFrom($email);
      $mail->addAddress('philasgouveanetto@gmail.com');
      $mail->addReplyTo($email);

      $mail->isHTML(true);
      $mail->Subject = 'Ajuda';
      $mail->Body    = '<h3>' . $nome . '</h3> <br> ' . $mensagem;
      $mail->AltBody = $nome . ' ' . $mensagem;

      $mail->send();
    } catch (\Exception $e) {
      throw new \Exception("Erro ao enviar mensagem: {$mail->Errorinfo}", 500);
    }
  }
}
