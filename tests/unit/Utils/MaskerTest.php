<?php

use App\Utils\Masker;
use PHPUnit\Framework\TestCase;

class MaskerTest extends TestCase {

  /** @var Masker Instância da Classe a ser testada */
  protected Masker $obMasker;

  /**
   * Método responsável por inicializar as varáveis
   */
  protected function setUp(): void {
    parent::setUp();
    $this->obMasker = new Masker;
  }

  /**
   * Método responsável por prover CPFs a serem macarados
   */
  public function provedor_de_cpfs(): array {
    return [
      'CPF Válido'                     => ['99789055455', '997.890.554-55'],
      'CPF Válido com Zero Inicial'    => ['08808433307', '088.084.333-07'],
      'CPF Ivanálido com Zero Inicial' => ['09876543210', '098.765.432-10'],
    ];
  }

  /**
   * @dataProvider provedor_de_cpfs
   * 
   * @test
   */
  public function deveria_retornar_cpf_formatado(string $cpf, string $expected): void {
    $actual = $this->obMasker->mask($cpf, Masker::$MK_CPF);

    $this->assertEquals($expected, $actual);
  }

  /** @test */
  public function deveria_lançar_uma_exeção_por_falta_de_numero(): void {
    $this->expectException(\Exception::class);

    $this->obMasker->mask('123456789', Masker::$MK_CPF);
  }

  /** @test */
  public function deveria_preencher_por_falta_de_numero(): void {
    $expected = '123.456.789-00';

    $actual = $this->obMasker->mask('123456789', Masker::$MK_CPF, '0');

    $this->assertEquals($expected, $actual);
  }

  /** @test */
  public function mascarador_customizado(): void {
    $expected = '1.2.3.4';
    $actual = $this->obMasker->mask('1234', 'd.d.d.d', null, 'd');
    $this->assertEquals($expected, $actual);

    $expected = '1.2.F.F';
    $actual = $this->obMasker->mask('12', '$.$.$.$', 'F', '$');
    $this->assertEquals($expected, $actual);

    $this->expectException(\Exception::class);
    $this->obMasker->mask('12', 'd.d.d.d', null, 'd');
  }

  /** @test */
  public function retirar_mascara(): void {
    $expected = '99789055455';
    $actual = $this->obMasker->remove('997.890.554-55');
    
    $this->assertEquals($expected, $actual);
  }
}
