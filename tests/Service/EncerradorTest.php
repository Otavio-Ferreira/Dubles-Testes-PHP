<?php

namespace Alura\Leilao\Testes\Services;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
use Alura\Leilao\Model\Leilao;
use Alura\Leilao\Service\Encerrador;
use Alura\Leilao\Service\EnviadorEmail;
use PHPUnit\Framework\TestCase;

class EncerradorTest extends TestCase
{
    private $encerrador;
    private $enviadorDeEmailMock;
    private $leilaoFiat147;
    private $leilaoVariante;

    protected function setUp(): void
    {
        $this->leilaoFiat147 = new Leilao(
            'Fiat 147 0km',
            new \DateTimeImmutable('8 days ago')
        );
        $this->leilaoVariante = new Leilao(
            'Variant',
            new \DateTimeImmutable('10 days ago')
        );
        $leilaoDao = $this->createMock(LeilaoDao::class);
        // $leilaoDao = $this->getMockBuilder(LeilaoDao::class)
        // ->setConstructorArgs([new \PDO('sqlite::memory:')])
        // ->getMock();

        $leilaoDao->method('recuperarNaoFinalizados')
            ->willReturn([$this->leilaoFiat147, $this->leilaoVariante]);
        $leilaoDao->method('recuperarFinalizados')
            ->willReturn([$this->leilaoFiat147, $this->leilaoVariante]);
        $leilaoDao->expects($this->exactly(2))
            ->method('atualiza')
            ->withConsecutive(
                [$this->leilaoFiat147],
                [$this->leilaoVariante]
            );

        $this->enviadorDeEmailMock = $this->createMock(EnviadorEmail::class);
        $this->encerrador = new Encerrador($leilaoDao, $this->enviadorDeEmailMock);
    }
    public function testLeiloesComMaisDeUmaSemanaDevemSerEncerrados()
    {

        $this->encerrador->encerra();

        $leiloes = [$this->leilaoFiat147, $this->leilaoVariante];

        self::assertCount(2,  $leiloes);
        self::assertTrue($leiloes[0]->estaFinalizado());
        self::assertTrue($leiloes[1]->estaFinalizado());
    }

    public function testDeveContinuarOProcessoamentoAoEncontrarErroAoEnviarEmail()
    {
        $e = new \DomainException('Erro ao enviar e-mail');
        $this->enviadorDeEmailMock->expects(self::exactly(2))
            ->method('notificarTerminoLeilao')
            ->willThrowException($e);

        $this->encerrador->encerra();
    }

    public function testSoDeveEnviarLeilaoPorEmailAposFinalizado()
    {
        $this->enviadorDeEmailMock->expects($this->exactly(2))
        ->method('notificarTerminoLeilao')
        ->willReturnCallBack(function(Leilao $leilao){
            static::assertTrue($leilao->estaFinalizado());
        });

        $this->encerrador->encerra();
    }
}
