<?php

namespace App\Utils;

/**
 * Classe responsável por controlar CPFs
 */
class CPF {

  /**
   * Método responsável por validar um cpf
   *
   * @param   string  $cpf
   *
   * @return  bool
   */
  public static function isvalid(string $cpf): bool {
    // VERIFICA SE SÓ HÁ NÚMEROS
    if (preg_match('/\D/', $cpf)) return false;

    // VERIFACA SE HÁ A QUANTIDADE CORRETA
    if (strlen($cpf) != 11) return false;

    // VERFICA SE SÃO NÚMEROS REPETIDOS
    if (preg_match('/(.)\1{10}/', $cpf)) return false;

    // REPARTE O CPF PARA VALIÇÃO
    $cpfValido = substr($cpf, 0, 9);

    // GERA UM CPF VÁLIDO
    self::generateVerifierDigits($cpfValido);

    // RETORNA A VALIDAÇÃO
    return $cpf === $cpfValido;
  }

  /**
   * Método responsável por gerar os Dígitod Verificadores dos CPFs
   *
   * @param   string  $incompleteCPF
   *
   * @return  void
   */
  private static function generateVerifierDigits(string &$incompleteCPF): void {
    // ATÉ TER O TAMANHO DE UM CPF
    while (strlen($incompleteCPF) < 11) {
      // RESULTADO DA EQUAÇÂO [Sn = (A1 * N+1) + (A2 * (N)) (A3 * (N-1)) ... (An * (2))]
      $resultado = 0;

      // COVERTE O CPF INCOMPLETO PRA UM ARRAY INVERTIDO
      foreach (array_reverse(str_split($incompleteCPF)) as $index => $value) {
        // FAZ A SOMÁTORIA
        $resultado += $value * ($index + 2);
      }

      // CONCATENA AO CPF INCOMPLETO (POR FIM, COMPLETANDO-O)
      $incompleteCPF .= (($resultado * 10) % 11) % 10;
    }
  }

  /**
   * Método responsável por gerar um CPF aleatório válido
   *
   * @return  string
   */
  public static function generateRandomCPF(): string {
    // CPF A SER GERADO
    $cpf = '';

    // GERA 9 NÚMEROS ALEATÓRISO
    for ($k = 0; $k < 9; $k++) $cpf .= rand(0, 9);

    // GERA OS DÍGITOS VERIFICADORES
    self::generateVerifierDigits($cpf);

    // RETORNA O CPF GERADO
    return $cpf;
  }
}
