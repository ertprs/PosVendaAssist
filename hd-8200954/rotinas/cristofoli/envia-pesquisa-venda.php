<?php

/**
 * - Rotina para envio de Pesquisa
 * de Satisfação para cliente que
 * adquiriram AUTOCLAVES e fizeram a
 * liberação do produto há SESSENTA DIAS
 *
 * @Chamado hd-4322298
 * @Author William Ap. Brandino
 * @Since 2018-06-14
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';

$fabrica        = 161;
$fabrica_nome   = "cristofoli";
$link_temp      = 'cristofoli/callcenter_pesquisa_satisfacao2.php';
$email          = new TcComm('smtp@posvenda');


// print_r(dirname(__FILE__));
$env = ($_serverEnvironment == 'development') ? 'novodevel.telecontrol.com.br/~brandino/assist/externos/' : 'https://posvenda.telecontrol.com.br/assist/externos/';
/*
 * Cron Class
 */
$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();

$sqlVer = "
    SELECT  tbl_cliente.nome,
            LOWER(tbl_cliente.email) AS email,
            tbl_venda.venda,
            tbl_venda.serie,
            TO_CHAR(tbl_venda.data_nf,'DD/MM/YYYY') AS data_compra,
            tbl_produto.referencia,
            tbl_produto.descricao
    FROM    tbl_venda
    JOIN    tbl_cliente USING(cliente)
    JOIN    tbl_produto USING(produto)
    JOIN    tbl_familia USING(familia)
    WHERE   tbl_venda.fabrica = $fabrica
    AND     tbl_venda.data_nf = CURRENT_DATE - INTERVAL '60 days'
    AND     tbl_familia.descricao ILIKE '%AUTOCLAVE%'
ORDER BY    data_nf DESC
    --LIMIT 2
";

$resVer = pg_query($con,$sqlVer);

$sqlPesquisa = "
    SELECT  texto_ajuda
    FROm    tbl_pesquisa
    WHERE   fabrica = $fabrica
    AND     ativo IS TRUE
    AND     categoria = 'externo_outros'
";
$resPesquisa = pg_query($con,$sqlPesquisa);
$texto_ajuda = pg_fetch_result($resPesquisa,0,texto_ajuda);

while ($cliente = pg_fetch_object($resVer)) {
//     echo $cliente->nome. '-'.$cliente->email. ': '.$cliente->descricao.'.'.PHP_EOL;
    $from_fabrica           = "no_reply@telecontrol.com.br";
    $from_fabrica_descricao = "Pós-Venda Cristófoli";

//     $mensagem = "Produto: ".$cliente->referencia." - ".$cliente->descricao." <br />";
//     $mensagem .= "Data da Compra: ".$cliente->data_compra ."<br />";

    $mensagem = "Olá ".$cliente->nome.", <br /><br />";
    $mensagem .= nl2br($texto_ajuda)."<br />";
    $mensagem .= "<a href='".$env.$link_temp."?venda=".$cliente->venda."' target='_blank'>Acesso Aqui</a> <br><br>Att <br>Cristófoli Biossegurança";

    if (!$email->sendMail(array($cliente->email,'silvia@cristofoli.com'),utf8_encode('Pesquisa de Satisfação - Cristófoli'),utf8_encode($mensagem),$from_fabrica)) {
        print ("Não foi possível enviar a pesquisa");
    }
}
