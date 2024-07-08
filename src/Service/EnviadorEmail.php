<?php

namespace Alura\Leilao\Service;
use Alura\Leilao\Model\Leilao;
use DomainException;

class EnviadorEmail
{
    public function notificarTerminoLeilao(Leilao $leilao) :void
    {
        $sucesso = mail(
            'usuario@gmail.com',
            'Leilão finalizado',
            "Leilão para {$leilao->recuperarDescricao()} finalizado."
        );

        if(!$sucesso){
            throw new \DomainException('Erro ao enviar email');
        }
    }
}