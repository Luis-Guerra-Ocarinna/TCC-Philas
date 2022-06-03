<?php

use App\Utils\Date;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase {

  /** @var Date Instância da Classe a ser testada */
  protected Date $obDate;

  /**
   * Método responsável por inicializar as varáveis
   */
  protected function setUp(): void {
    parent::setUp();
    $this->obDate = new Date;
  }

  /**
   * Método responsável por prover datas para validação
   */
  public function provedor_de_datas_para_validar(): array {
    return [
      'Data Completa'             => ['10/10/2010 10:10:10', null, true],
      'Data Sem Segundos'         => ['10/10/2010 10:10', 'd/m/Y H:i', true],
      'Data Sem Hora'             => ['10/10/2010', 'd/m/Y', true],
      'Inválida: Dia inválido'    => ['30/02/2000', 'd/m/Y', false],
      'Inválida: String inválida' => ['aaaaaaaaaa', null, false],
    ];
  }

  /**
   * @dataProvider provedor_de_datas_para_validar
   * 
   * @test
   */
  public function deveria_validar_as_datas(string $date, ?string $format, bool $expected): void {
    $format = $format ?? 'd/m/Y H:i:s';

    $actual = $this->obDate::isvalid($date, $format);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Método responsável por prover datas para formatação
   */
  public function provedor_de_datas_para_formatar(): array {
    return [
      'BR -> SQL'                   => ['d/m/Y', '19/10/2021', 'Y-m-d', '2021-10-19'],
      'BR -> SQL com Hora & Minuto' => ['d/m/Y H:i', '19/10/2021 19:40', 'Y-m-d H:i', '2021-10-19 19:40'],
      'BR -> USA'                   => ['d/m/Y', '19/10/2021', 'm/d/Y', '10/19/2021'],
      'Random -> BR'                => ['H m$Y@d i:s', '21 10$2021@19 40:53', 'd/m/Y H:i:s', '19/10/2021 21:40:53']
    ];
  }

  /** 
   * @dataProvider provedor_de_datas_para_formatar
   * 
   * @test 
   */
  public function deveria_formatar_as_datas(string $formatiing, string $date, string $format, string $expected): void {
    $actual = $this->obDate::format($formatiing, $date, $format);

    $this->assertEquals($expected, $actual);
  }

  /** @test */
  public function deveria_lancar_uma_exececao_de_argumentos_invaldos() {
    $this->expectException(\InvalidArgumentException::class);

    $this->obDate::format('Y-m-d', '19/10/2021', 'tanto faz');
  }
}
