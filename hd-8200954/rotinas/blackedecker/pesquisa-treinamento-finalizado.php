<?php
/**
 *
 * pesquisa-treinamento-finalizado.php
 *
 * Envia pesquisa sobre o treinamento que já está finalizado
 *
 * @author  Thiago Tobias
 * @version 2018.03.19
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
//define('ENV','producao');  // produção, Alterar para produção quando for subir
define('ENV','devel');  // devel, Alterar para devel ao realizar os testes

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    $helper = new \Posvenda\Helpers\Os();

    $dataLog['login_fabrica'] = 1;
    $dataLog['log'] = 2;

    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

    $fabrica = 1;
    $arquivos = "/tmp";

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    if (ENV == 'devel' ) {
        $dataLog['dest'] = 'thiago.tobias@telecontrol.com.br';
        $postoTeste = '';
        $link = " http://novodevel.telecontrol.com.br/~brandino/PosVenda/externos/blackedecker/treinamento_pesquisa_satisfacao.php";
    } else {
        $dataLog['dest'] = 'helpdesk@telecontrol.com.br';
        $postoTeste = 'AND   tbl_treinamento_posto.posto <> 6359';
        $link = " https://posvenda.telecontrol.com.br/assist/externos/blackedecker/treinamento_pesquisa_satisfacao.php";
    }

    $sql = "SELECT  tbl_treinamento.treinamento,
                    tbl_treinamento_posto.posto,
                    tbl_tecnico.celular AS tecnico_celular,
                    tbl_tecnico.telefone AS tecnico_fone,
                    tbl_tecnico.tecnico AS tecnico
                FROM tbl_treinamento
                    JOIN tbl_treinamento_posto USING(treinamento)
                    JOIN tbl_tecnico USING(tecnico)
                WHERE tbl_treinamento.fabrica = {$fabrica}
                    $postoTeste
                    AND tbl_treinamento_posto.tecnico IS NOT NULL
                    AND tbl_treinamento.ativo IS TRUE
                    AND (tbl_treinamento.data_fim + INTERVAL '1 days') = CURRENT_DATE; ";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        for($i = 0; $i < pg_num_rows($res); $i++){

            $idTreinamento = pg_fetch_result($res, $i, treinamento);
            $idTecnico = pg_fetch_result($res, $i, tecnico);

            $tecnicoCelular = pg_fetch_result($res, $i, tecnico_celular);
            $tecnicoFone = pg_fetch_result($res, $i, tecnico_fone);
            $celularSms = (!empty($tecnicoCelular))? $tecnicoCelular : $tecnicoFone;

            $msgSMS = "Favor realizar a pesquisa de satisfação do treinamento no link: ".$link."?a=$idTreinamento&b=$idTecnico";
            $helper->comunicaConsumidor($celularSms, $msgSMS, $fabrica);
        }
    } else {
        echo "Nenhum técnico cadastrado para o treinamento";
    }
    $phpCron->termino();

} catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    //echo $msg."\r\n";

    Log::envia_email($dataLog,Date('d/m/Y H:i:s')." - Erro ao enviar pesquisa de treinamento", $msg);
}
