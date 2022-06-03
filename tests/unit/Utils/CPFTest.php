<?php

use App\Utils\CPF;
use PHPUnit\Framework\TestCase;

class CPFTest extends TestCase {

  /** @var CPF Intância da Classe a ser testada */
  protected CPF $obCPF;

  /**
   * Método responsável por inicializar as varáveis
   */
  protected function setUp(): void {
    parent::setUp();
    $this->obCPF = new CPF;
  }

  /**
   * Método responsável por prover CPFs a serem validados
   */
  public function provedor_de_cpfs(): array {
    return [
      'Inválido: mais que números'    => ['997.890.554-55', false],
      'Inválido: falta de números'    => ['9978905', false],
      'Inválido: execesso de números' => ['9978905545573412', false],
      'Inválido: números repetidos'   => ['11111111111', false],
      'Inválido: não obece ao padrão' => ['89234917303', false],
      'Válido'                        => ['99789055455', true],
      'Válido: Com Zero'              => ['08808433307', true]
    ];
  }

  /**
   * @dataProvider provedor_de_cpfs
   * 
   * @test
   */
  public function deveria_validar_os_cpfs(string $cpf, bool $expected): void {
    $actual = $this->obCPF->isvalid($cpf);

    $this->assertEquals($expected, $actual);
  }

  /** @test */
  public function deveria_gerar_um_cpf_aleátorio_válido(): void {
    $actual = $this->obCPF->isvalid($this->obCPF->generateRandomCPF());

    $this->assertTrue($actual);
  }

  public function testCPF_aleátorio_mascarado(): void {
    $actual = (new \App\Utils\Masker)->mask($this->obCPF->generateRandomCPF(), \App\Utils\Masker::$MK_CPF);

    $this->assertMatchesRegularExpression('/\d{3}\.\d{3}\.\d{3}\-\d{2}/', $actual);
  }
}
