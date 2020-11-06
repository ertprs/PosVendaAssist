<?php

namespace Posvenda\Helpers;

class Os
{
    /**
     * Dispara aviso ao consumidor de alguma ação na OS
     *
     * @param string $dest Endereço de email ou número de celular
     * @param string $msg  Mensagem a ser disparada ao consumidor
     */
    public function comunicaConsumidor($dest, $msg, $fabrica = null, $sua_os = null, $hd_chamado = null , $treinamento = null)
    {
        if (filter_var($dest, FILTER_VALIDATE_EMAIL)) {
            return $this->enviaEmailConsumidor($dest, $msg);
        }

        return $this->enviaSMSConsumidor($dest, $msg, $fabrica, $sua_os, $hd_chamado, $treinamento);
    }

    /**
     * Envia email usando Tc Communicator
     *
     * @param string $dest Endereço de email ou número de celular
     * @param string $msg  Mensagem a ser disparada ao consumidor
     */
    private function enviaEmailConsumidor($dest, $msg)
    {
        require_once __DIR__ . '/../../../class/communicator.class.php';

        $mailer = new \TcComm("smtp@posvenda");

        return $mailer->sendMail(
            $dest,
            "Atualização de status de sua Ordem de Serviço",
            $msg,
            "noreply@telecontrol.com.br"
        );
    }

    /**
     *  Envia SMS usando a sms.class.php
     *
     * @param string $dest Número de celular
     * @param string $msg  Mensagem a ser disparada ao consumidor
     * @return boolean
     */
    private function enviaSMSConsumidor($dest, $msg, $fabrica = null, $sua_os = null, $hd_chamado = null, $treinamento = null)
    {
        require_once __DIR__ . '/../../../class/sms/sms.class.php';

        $sms = new \SMS($fabrica);

        return $sms->enviarMensagem($dest, $sua_os, null, $msg, $hd_chamado, $treinamento);
    }
}
