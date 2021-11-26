<?php

namespace App\Utils;

use function App\Utils\better as print_better;

/**
 * Classe responsável por controlar notificações, avisos e erros
 */
class TriggerError {

  private static function stackTrace(int $limit): string {
    $stack = "\nStack Trace:\n";

    $trace = debug_backtrace(limit: ($limit) ? $limit + 2 : 0);
    array_shift($trace);
    array_shift($trace);

    foreach ($trace as $i => $step) {
      $args = $step['args'];
      $temp = '';
      $comma = 0;
      foreach ($args as $arg) {
        $temp .= ($comma ? ', ' : '') . print_better($arg);
        $comma++;
      }
      $args = preg_replace('/\R/', '\n', htmlspecialchars($temp));
      $stack .= "  #$i $step[file]($step[line]): $step[class]$step[type]$step[function]($args)\n";
    }

    return $stack;
  }

  public static function warning(string $message = '', int $limit = 0): void {
    echo '<pre>';
    trigger_error($message . @self::stackTrace($limit), E_USER_WARNING);
    echo '</pre>';
  }

  /* FIXMEx: terminar isso que é muito pika
    public static function dafault() {
      echo "<pre>";
      $debug_backtrace();
      echo "</pre>";
      exit;
    }

    private static function print(string $message, int|bool $exit) {
      echo '<pre>';
      echo $message;
      echo '</pre>';
    }

    public static function default(
      int $errno,
      string $errstr,
      ?string $errfile,
      ?int $errline,
      ?array $errcontext
    ) {

      // self::print(match ($errno) {
      //   E_USER_ERROR => "",
      // });
    }
    // https://www.php.net/manual/pt_BR/function.set-error-handler.php
    // https://stackoverflow.com/a/18121959/14561921
    // error handler function
    function myErrorHandler($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
        case E_USER_ERROR:
          echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
          echo "  Fatal error on line $errline in file $errfile";
          echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
          echo "Aborting...<br />\n";
          exit(1);
          break;
    
        case E_USER_WARNING:
          echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
          break;
    
        case E_USER_NOTICE:
          echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
          break;
    
        default:
          echo "Unknown error type: [$errno] $errstr<br />\n";
          break;
      }
    
      // Don't execute PHP internal error handler
      return true;
    }
  */
}
