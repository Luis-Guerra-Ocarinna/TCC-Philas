<?php

use App\Utils\Arrays;
use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase {

  /** @var Arrays Instância da Classe a ser testada */
  protected Arrays $obArrays;

  /**
   * Método responsável por inicializar as varáveis
   */
  protected function setUp(): void {
    parent::setUp();
    $this->obArrays = new Arrays;
  }

  /**
   * Método responsável por prover Arrays
   */
  public function provedor_de_arrays(): array {
    return [
      'Básico' => [
        [
          ['a'], ['b'], ['c']
        ],

        [0  => 'a', 1 => 'b', 2  => 'c']
      ],

      'Assosiativo' => [
        [
          ['a'], ['b' => 'b'], ['c']
        ],

        [0  => 'a', 'b' => 'b', 1  => 'c']
      ],

      'Com index definido' => [
        [
          ['a'], [1 => 'b'], [3 => 'd'], ['c']
        ],

        [0 => 'a', 1 => 'b', 3 => 'd', 4 => 'c']
      ],

      'Com index definido (ordem)' => [
        [
          ['a'], [1 => 'b'], [3 => 'd'], [2 => 'c']
        ],

        [0 => 'a', 1 => 'b', 3 => 'd', 2 => 'c']
      ],

      'Com index definido (negativo)' => [
        [
          ['b'], [-1 => 'a'], ['c']
        ],

        [0 => 'b', -1 => 'a', 1 => 'c']
      ],
    ];
  }

  /**
   * @dataProvider provedor_de_arrays
   * 
   * @test
   */
  public function deveria_unir_todos_valores(array $arrays, array $expected): void {
    $actual = array_shift($arrays);
    $this->obArrays::mergeRight($actual, ...$arrays);

    $this->assertEquals($expected, $actual);
  }
}
