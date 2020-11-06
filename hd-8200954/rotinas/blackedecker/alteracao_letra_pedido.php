<?php
error_reporting(E_ALL ^ E_NOTICE);
define('ENV', $_serverEnvironment); // 'development' ou 'production'

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

$class = new AlteracaoLetraPedido($con);

class AlteracaoLetraPedido
{
    private $_con;
    private $_emails;

    public function __construct($con){
        $this->_con = $con;
        $this->_validarQuantidadePedido();
    }

    private function _validarQuantidadePedido(){
        $sql = "SELECT DISTINCT letra FROM tbl_seu_pedido_fabrica WHERE fabrica = 1;";
        $res = pg_query($this->_con ,$sql);
        $tl  = pg_num_rows($res);
        
        for ( $i = 0; $i < $tl; $i++ ) {
            $letra = pg_fetch_result($res, $i, 'letra');
            $novaLetra = $letra;
            if (substr($letra, -1) == 'Z') {
                $novaLetra = $this->_mudarUltimaLetra($letra);
            } else {
                ++$novaLetra;
            }
            if ($this->_validarPedido24horas($letra.'90000', $letra.'90050')) {
                $this->_email($letra, $novaLetra);
            }
        }
    }

    private function _validarPedido24horas($seu_pedido, $seu_pedido2) {
        $sql = "SELECT seu_pedido,data FROM tbl_pedido WHERE fabrica = 1 AND seu_pedido between '$seu_pedido' and '$seu_pedido2' AND data > CAST(CURRENT_TIMESTAMP AS TIMESTAMP) - INTERVAL '3 DAYS';";
        $res = pg_query($this->_con, $sql);
        
        if(pg_num_rows($res) > 0){
            return true;
        }
        return false;
    }

    private function _email($antiga, $nova){
        $this->_listaEmailMaster();
        $mailer = new PHPMailer();
        $mailer->IsSMTP();
        $mailer->IsHTML(true);

        foreach ($this->_emails as $email) {
            $mailer->AddAddress($email);
        }

        $mailer->Subject = "Aviso de mudança de Sigla dos Pedidos {$antiga}";
        $mensagem  = "Prezado(a), <br />";
        $mensagem .= "<br /><p>A numeração dos pedidos no sistema Telecontrol chegou à '90000' e em breve a sigla será alterada de $antiga para $nova. </p><br />";
        $mensagem .= "<br />Qualquer dúvida entrar em contato com o suporte Telecontrol. <br />";
        $mensagem .= "<br /><br /><b>Atenciosamente, <br> Equipe Telecontrol.</b><br />";
        $mensagem .= "<br /><img src='https://telecontrol.com.br/wp-content/uploads/2019/07/logo-telecontrol-136px.png' width=136>";

        $mailer->Body = $mensagem;
        $mailer->Send();
    }

    private function _listaEmailMaster(){
        $sql = "SELECT email FROM tbl_admin WHERE fabrica = 1 AND ativo IS TRUE AND privilegios = '*'";
        $res = pg_query($this->_con, $sql);
        $tl  = pg_num_rows($res);

		$this->_emails[] = 'suporte@telecontrol.com.br';
        for ( $i = 0; $i < $tl; $i++ ) {
            $this->_emails[] = pg_fetch_result($res, $i, 'email');
        }
    }

    private function _mudarUltimaLetra($letra){
        $letra[strlen($letra)-1] = 'A';
        return $letra;
    }
}
