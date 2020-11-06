<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include "funcoes.php";

if($login_fabrica == 189){
    include_once 'pedido_press_new.php';
    exit;
}

    include_once dirname(__FILE__) . '/../class/AuditorLog.php';
    $AuditorLog = new AuditorLog;

if(in_array($login_fabrica, array(151))){
    require_once "./os_cadastro_unico/fabricas/151/classes/CancelarPedido.php";
    $cancelaPedidoClass = new CancelarPedido($login_fabrica);
}

if(in_array($login_fabrica, array(138, 142)) || isset($telaPedido0315)){
    include_once S3CLASS;
    $s3 = new AmazonTC("pedido", $login_fabrica);
}

if(isset($_POST['getLinkNF'])){
    
    $nf = $_POST['nf'];
    $cnpj = $_POST["cnpj"];

    $NFeID = getLinkNF($nf, $cnpj);

    echo json_encode(array("NFeID" => $NFeID));

    exit;
}


if(isset($_POST['ajax_conferencia_embarque'])){

    $pedido = $_POST["pedido"];
    $data = date("d-m-Y h:i:s");
    $sql_status = "INSERT INTO tbl_pedido_status (pedido, status, observacao, admin)
                        values ($pedido, 10, 'Conferencia Embarque', $login_admin)";
    $res_status = pg_query($con, $sql_status);
    if(strlen(pg_last_error($con))>0){
        $retorno = 'nao';
    }else{
        $retorno = 'sim';
        $AuditorLog->gravaLog('tbl_pedido', "$fabrica*$pedido", array("pedido" => $pedido, "situacao_anterior" => 'nao embarcado', "situacao_atual" => "embarcado", 'status_pedido'=> 'Ag.distrib - Venda Direta', 'data'=> $data, 'admin' => "$login_admin"));
    }
    echo json_encode(array('retorno'=> "$retorno"));
    exit;
}

if(isset($_POST['ajax_venda_direta'])){
    $pedido = $_POST["pedido"];
    $data = date("d-m-Y h:i:s");

   system("php ".__DIR__."/../rotinas/distrib/embarque_novo_faturado.php  $pedido");

    $sql_embarque = "SELECT tbl_embarque_item.embarque
                        from tbl_embarque_item
                        join tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
                        WHERE tbl_pedido_item.pedido = $pedido
                        limit 1 ";
    $res_embarque = pg_query($con, $sql_embarque);
    if(pg_num_rows($res_embarque)>0){
        $embarque = pg_fetch_result($res_embarque, 0, "embarque");

        $sql_status = "INSERT INTO tbl_pedido_status (pedido, status, observacao, admin)
                        values ($pedido, 11, 'Venda Direta', $login_admin)";
        $res_status = pg_query($con, $sql_status);

        $AuditorLog->gravaLog('tbl_pedido', "$fabrica*$pedido", array("pedido" => $pedido, "situacao_anterior" => 'nao embarcado', "situacao_atual" => "embarcado", 'status_pedido'=> 'Ag.distrib - Venda Direta', 'data'=> $data, 'admin' => "$login_admin"));
    }

    echo json_encode(array('embarque'=> "$embarque"));
    exit;
}


$mostra_data_aprovacao = in_array($login_fabrica, array(138));

function regiao_suframa(){

    $pedido = trim($_REQUEST["pedido"]);

    global $login_posto, $con;

    $sql_suframa = "SELECT suframa FROM tbl_posto WHERE posto IN (SELECT posto FROM tbl_pedido WHERE pedido = {$pedido})";
    $res_suframa = pg_query($con, $sql_suframa);

    $suframa = pg_fetch_result($res_suframa, 0, "suframa");

    return ($suframa == "t") ? true : false;

}

if (isset($_POST["acaoS3"])) {
    $arquivoS3 = $_FILES["arquivoS3"];
    $acaoS3    = $_POST["acaoS3"];
    $anexoS3   = $_POST["anexoS3"];
    $posicao   = $_POST["posicao"];

    if ($acaoS3 == "Excluir") {
        if ($s3->ifObjectExists($anexoS3)) {
            $s3->deleteObject($anexoS3);

            if ($s3->ifObjectExists($anexoS3)) {
                $retorno = array("erro" => utf8_encode(traduz("Erro ao excluir arquivo")));
            } else {
                $retorno = array(
                    "acaoS3"  => utf8_encode($acaoS3),
                    "posicao" => $posicao
                );

            }
        } else {
            $retorno = array("erro" => utf8_encode(traduz("Erro ao excluir, arquivo não encontrado")));
        }
    } else if ($acaoS3 == "Anexar") {
        if (strlen($arquivoS3["tmp_name"]) > 0) {
            $ext = strtolower(preg_replace("/.+\./", "", $arquivoS3["name"]));

            if ($ext == "jpeg") {
                $ext = "jpg";
            }

            if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
                $retorno = array("erro" => utf8_encode(traduz("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx")));
            } else {
                $anexoS3 = "{$pedido}_{$posicao}";

                $s3->upload($anexoS3, $arquivoS3);

                if ($s3->ifObjectExists($anexoS3.".{$ext}")) {
                    $imagemS3      = $s3->getLink("{$anexoS3}.{$ext}");

                    switch ($ext) {
                        case "pdf":
                            $thumbImagemS3 = "imagens/pdf_icone.png";
                            break;

                        case "doc":
                        case "docx":
                            $thumbImagemS3 = "imagens/docx_icone.png";
                            break;

                        default:
                            $thumbImagemS3 = $s3->getLink("thumb_{$anexoS3}.{$ext}");
                            break;
                    }

                    $retorno = array(
                        "anexoS3"       => utf8_encode($anexoS3.".{$ext}"),
                        "thumbImagemS3" => utf8_encode($thumbImagemS3),
                        "imagemS3"      => utf8_encode($imagemS3),
                        "acaoS3"        => utf8_encode($acaoS3),
                        "posicao"       => $posicao

                    );
                } else {
                    $retorno = array("erro" => utf8_encode(traduz("Erro ao anexar arquivo")));
                }
            }
        } else {
            $retorno = array("erro" => utf8_encode(traduz("Erro ao anexar, arquivo não selecionado")));
        }
    } else {
        $retorno = array("erro" => utf8_encode(traduz("Erro ao realizar ação")));
    }

    exit(json_encode($retorno));
}

function visualiza_estoque_distrib ($admin = "") {
    global $con, $login_fabrica;

    $aux_acesso_distrib = false;

    $sql_visualiza_estoque_distrib = "SELECT JSON_FIELD('visualiza_estoque_distrib', parametros_adicionais) AS visualiza_estoque_distrib
                                      FROM tbl_admin
                                      WHERE admin = $admin";
    $res_visualiza_estoque_distrib = pg_query($con, $sql_visualiza_estoque_distrib);
    $visualiza_estoque_distrib = pg_fetch_result($res_visualiza_estoque_distrib, 0, 'visualiza_estoque_distrib');

    if ($visualiza_estoque_distrib == "t") {
        $aux_acesso_distrib = true;
    }

    return $aux_acesso_distrib;
}

if (filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN)) {
    if (filter_input(INPUT_POST,"tipo") == "envio_frete_posto") {
        $volume         = filter_input(INPUT_POST,"volume");
        $valor_frete_c  = filter_input(INPUT_POST,"valor_frete_c");
        $valor_frete_t  = filter_input(INPUT_POST,"valor_frete_t");
        $pedido         = filter_input(INPUT_POST,"pedido");
        $posto          = filter_input(INPUT_POST,"posto");

        $fretes = json_decode($valor_frete_c,TRUE);

        if (!empty($valor_frete_t)) {
            array_push($fretes,array("descricao"=>"TRANSPORTADORA","valor"=>$valor_frete_t));
        }
        $dados_frete = array(
            "volume"        => $volume,
            "valor_frete"   => $fretes
        );

        $dados_frete = json_encode($dados_frete);

        /*
         * CAMPOS tbl_comunicado: mensagem(Corpo do email),tipo("comunicado"),descricao(também),fabrica,obrigatorio_site,ativo
         */

        pg_query($con,"BEGIN TRANSACTION");

        $sql = "
            UPDATE  tbl_pedido
            SET     status_pedido = 27,
                    valores_adicionais = E'$dados_frete'
            WHERE   pedido = $pedido
            AND     fabrica = $login_fabrica
        ";

        $res = pg_query($con,$sql);

        if (pg_last_error($con)) {
            pg_query($con,"ROLLBACK TRANSACTION");
            echo "erro";
            exit;
        }

        /**
         * - Envia email e comunicado para o
         * Posto Autorizado
         */

        $sqlDadosPosto = "
            SELECT  tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome_fantasia,
                    tbl_posto_fabrica.contato_email
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica USING(posto)
            WHERE   fabrica         = $login_fabrica
            AND     tbl_posto.posto = $posto
        ";
        $resDadosPosto = pg_query($con,$sqlDadosPosto);

        $dadosPosto = pg_fetch_object($resDadosPosto);

        $corpoEmail = 
            traduz("Prezado Posto Autorizado") . $dadosPosto->codigo_posto . " - ". $dadosPosto->nome_fantasia. "<br />
            <br />" .
            traduz("O pedido $pedido está aguardando aprovação do frete. <br /> Por gentileza prosseguir com a aprovação na tela de pedidos.") . "
        ";

        $sqlComunicado = "
            INSERT INTO tbl_comunicado (
                fabrica,
                posto,
                mensagem,
                tipo,
                descricao,
                obrigatorio_site,
                ativo
            ) VALUES (
                $login_fabrica,
                $posto,
                '$corpoEmail',
                'Comunicado',
                'Comunicado',
                TRUE,
                TRUE
            )
        ";
        $resComunicado = pg_query($con,$sqlComunicado);

        if (!pg_last_error($con)) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            include '../class/communicator.class.php';

            $communicator = new TcComm("noreply@tc");
            $communicator->addEmailDest($dadosPosto->contato_email);
            if (in_array($login_fabrica, array(169,170))){
                $communicator->setEmailFrom("naorespondablueservice@carrier.com.br");
            }else{
                $communicator->setEmailFrom("noreply@telecontrol.com.br");
            }

            $communicator->setEmailSubject(traduz("Valores de Frete do pedido nº ") .$pedido. traduz(" - Fábrica: ") .$login_fabrica_nome);
            $communicator->setEmailBody($corpoEmail);
            $email = $communicator->sendMail();

            if ($email) {
                echo json_encode(array("ok"=>true));
                exit;
            }
            echo json_encode(array("ok"=>true));
        }
    }
	exit;
}


$admin_privilegios = "call_center";
$layout_menu = "callcenter";

if (strlen($_POST['sedex'])    > 0) $sedex    = $_POST['sedex'];    else $sedex    = $_GET['sedex'];
if (strlen($_POST['key'])      > 0) $key      = $_POST['key'];      else $key      = $_GET['key'];
if (strlen($_POST['garantia']) > 0) $garantia = $_POST['garantia']; else $garantia = $_GET['garantia'];
if (strlen($_POST['pedido'])   > 0) $pedido   = $_POST['pedido'];   else $pedido   = $_GET['pedido'];
if (isset($_GET['imprimir'])) $imprime = $_GET['imprimir'];

if (strlen($sedex) > 0 AND ($login_admin == 232 OR $login_admin == 112)) {

    $sqlS = "UPDATE tbl_pedido SET
            pedido_sedex = 't'   ,
            admin = 232
            WHERE pedido = $sedex";

    //echo $sql;exit;
    $resS = pg_query ($con,$sqlS);
    $pedido = $sedex;

}

if ($_POST["reenviar_pedido"] && $login_fabrica == 143) {
    try {
        pg_query($con, "BEGIN");

        $update = "UPDATE tbl_pedido SET status_pedido = 19, rejeitado_motivo = '' WHERE pedido = {$pedido}";
        $resUpdate = pg_query($con, $update);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao reenviar pedido"));
        }

        pg_query($con, "COMMIT");
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
        pg_query($con, "ROLLBACK");
    }
}

if ($_POST["aprova_atualizacao_pedido"] && $login_fabrica == 143) {
    try {
        include "../rotinas/wackerneuson/exporta-pedido-funcao.php";

        pg_query($con, "BEGIN");

        $sql = "SELECT
                    pedido_item,
                    preco AS valor_unitario,
                    total_item AS valor_total,
                    acrescimo_financeiro AS novo_valor_unitario,
                    acrescimo_tabela_base AS novo_valor_total
                FROM tbl_pedido_item
                WHERE pedido = {$pedido}";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Erro ao aprovar pedido");
        }

        while ($item = pg_fetch_object($res)) {
            $update = "UPDATE tbl_pedido_item SET
                            preco                 = {$item->novo_valor_unitario},
                            total_item            = {$item->novo_valor_total},
                            acrescimo_financeiro  = {$item->valor_unitario},
                            acrescimo_tabela_base = {$item->novo_valor_total}
                       WHERE pedido = {$pedido}
                       AND pedido_item = {$item->pedido_item}";
            $resUpdate = pg_query($con, $update);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception(traduz("Erro ao aprovar pedido"));
            }
        }

        $sqlTotal = "SELECT SUM(total_item) AS total_pedido FROM tbl_pedido_item WHERE pedido = {$pedido}";
        $resTotal = pg_query($con, $sqlTotal);

        $total_pedido = pg_fetch_result($resTotal, 0, "total_pedido");

        $sqlDesconto = "SELECT desconto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
        $resDesconto = pg_query($con, $sqlDesconto);

        $desconto = pg_fetch_result($resDesconto, 0, "desconto");

        if ($desconto > 0) {
            $total_pedido += ($total_pedido / 100) * $desconto;
        }

        $update = "UPDATE tbl_pedido SET status_pedido = 2, total = {$total_pedido} WHERE pedido = {$pedido}";
        $resUpdate = pg_query($con, $update);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao aprovar pedido"));
        }

        $sql = "SELECT seu_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
        $res = pg_query($con, $sql);

        $pedido_wacker_neuson = pg_fetch_result($res, 0, "seu_pedido");

        $resultadoAprovacao = aprovaPedidoWackerNeuson($pedido_wacker_neuson);

        if (!empty($resultadoAprovacao->erroExecucao)) {
            throw new itemException(traduz("Erro ao aprovar pedido"));
        }

        pg_query($con, "COMMIT");
        $msg_sucesso = traduz("Pedido Aprovado");
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
        pg_query($con, "ROLLBACK");
    }
}

$lista_de_admin_altera_pedido = array("568","399","567","398","432","586","822","2151");

if(in_array($login_fabrica, array(2,3,6,11,15,35,45,46,52,50,74,81,80,88,94,95,98,99,101,104,106,108,111,114,115,116,117,119,120,201,121,122,123,124,125,126,127,128,129)) or $login_fabrica >= 131 && $login_fabrica != 143){
    $vet_fabrica_cancela = array($login_fabrica);
}
$vet_gama_salton_tele         = array(10,51,81);
if(in_array($login_fabrica, array(2,3,6,11,15,30,35,45,40,42,46,52,74,81,88,94,95,98,99,101,106,108,111,114,115,116,117,120,201,121,122,123,124,125,126,127,128,129)) or $login_fabrica >= 131){
    $vet_fabrica_pedido_item = array($login_fabrica);
}

$ronaldo                      = 586;

if (strlen($pedido) > 0 AND $login_fabrica == 24) {

    $sql = "SELECT sum(qtde) AS qtde,
                  sum(qtde_cancelada) AS qtde_cancelada
            FROM  tbl_pedido
            JOIN  tbl_pedido_item USING(pedido)
            WHERE tbl_pedido.pedido = $pedido
            AND   tbl_pedido.status_pedido <> 14";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $qtde           = pg_fetch_result($res, 0, 'qtde');
        $qtde_cancelada = pg_fetch_result($res, 0, 'qtde_cancelada');

        if ($qtde == $qtde_cancelada) {

            $sql2 = "UPDATE tbl_pedido SET status_pedido = 14 WHERE pedido = $pedido";
            $res2 = pg_query($con, $sql2);

        }

    }

}

if (strlen($_GET["cancelar"]) > 0 AND strlen($_GET["pedido"]) > 0) {

    $res = pg_query ($con,"BEGIN TRANSACTION");

    $pedido        = trim($_GET["pedido"]);
    $motivo        = pg_escape_string(trim($_GET["motivo"]));
    $cancelar      = trim($_GET["cancelar"]);
    $qtde_cancelar = trim($_GET["qtde_cancelar"]);
    $os_cancela    = trim($_GET["os"]);
    $Aud = new AuditorLog('insert');

    if (strlen($motivo) == 0) $msg_erro   = traduz("Por favor informe o motivo de cancelamento da peça: $referencia - $qtde");
    else                      $aux_motivo = "'$motivo'";

    //Cancela todo o pedido quando ele é distribuidor

    if (in_array($login_fabrica, $vet_fabrica_cancela)) {
        if(strlen($qtde_cancelar) == 0 and $cancelar <> "todo") $msg_erro = traduz("Por favor informe a quantidade a cancelar");
    }

    if ($cancelar <> "todo") {
        $sql  = "SELECT qtde, qtde_faturada, qtde_cancelada  FROM tbl_pedido_item WHERE pedido_item = $cancelar";
        $res  = pg_query($con, $sql);

        $qtde           = pg_fetch_result($res,0,qtde);
        $qtde_faturada  = pg_fetch_result($res,0,qtde_faturada);
        $qtde_cancelada = pg_fetch_result($res,0,qtde_cancelada);

        $qtde_aux = $qtde - $qtde_cancelada - $qtde_faturada;

        if ($qtde_cancelar > $qtde_aux ) {
            $msg_erro = traduz("Quantidade informada maior do que a quantidade de itens cadastrados");
        }
        if ($qtde_cancelar == 0) {
            $msg_erro = traduz("Quantidade informada não pode ser ZERO");
        }

    }

    if (strlen($msg_erro) == 0) {

        if ($cancelar == "todo") {
            $sql = "SELECT  PE.pedido      ,
                            PE.distribuidor,
                            PI.pedido_item ,
                            PI.peca        ,
                            PI.qtde        ,
                            OP.os
                        FROM   tbl_pedido        PE
                        JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
                        LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                        LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                        WHERE PE.pedido  = $pedido
                        AND   PE.fabrica = $login_fabrica
                        AND   PI.qtde > PI.qtde_cancelada ";

            if ((in_array($login_fabrica,array(3,10,51,81,114))) AND $distribuidor == 4311 AND in_array($login_admin, $lista_de_admin_altera_pedido)) { // HD 46988

                $sql = "SELECT  PE.pedido      ,
                                PE.distribuidor,
                                PI.pedido_item ,
                                PI.peca        ,
                                PI.qtde        ,
                                OP.os
                            FROM   tbl_pedido        PE
                            JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
                            LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                            LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                            WHERE PE.pedido  = $pedido
                            AND   PE.fabrica = $login_fabrica
                            AND   PI.qtde > PI.qtde_cancelada + qtde_faturada_distribuidor ";

            }

            /*  Tirei averificação de distribuidor, pois não há variável setada, nunca ia entrar no 'if'
                Adicionei o $login=$ronaldo para que só o admin $ronaldo possa cancelar um pedido inteiro
                Adicionei o filtro 'AND distribuidor = 4311' para que $ronaldo só possa cancelar pedidos
                distribuidos pela Telecontrol */
            if ((in_array($login_fabrica, $vet_gama_salton_tele) and in_array($login_admin,$lista_de_admin_altera_pedido)) or in_array($login_fabrica, $vet_fabrica_cancela)) {

                $sql = "SELECT  PE.pedido      ,
                                PE.distribuidor,
                                PI.pedido_item ,
                                PI.peca        ,
                                PI.qtde        ,
                                PI.qtde_cancelada,
                                OP.os,
                                (PI.qtde-PI.qtde_cancelada-qtde_faturada_distribuidor) AS cancelar
                            FROM   tbl_pedido        PE
                            JOIN   tbl_pedido_item   PI USING (pedido)
                            LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                            LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                            WHERE PE.pedido  = $pedido
                            AND   PE.fabrica = $login_fabrica
                            AND distribuidor = 4311
                            AND   PI.qtde > PI.qtde_cancelada + qtde_faturada_distribuidor";
            }

	//	echo nl2br($sql);
	//	die;

            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $peca           = pg_fetch_result ($res,$i,peca);
                    $qtde           = pg_fetch_result ($res,$i,qtde);
                    $os             = pg_fetch_result ($res,$i,os);
                    $distribuidor   = pg_fetch_result ($res,$i,distribuidor);

                    if ($login_admin == $ronaldo) {
                        $qtde_item_cancelar = pg_fetch_result ($res,$i,cancelar);
                        $qtde_cancelar = ($qtde<>$qtde_item_cancelar) ? $qtde_item_cancelar: $qtde;
                    }

                    if (strlen($qtde_cancelar) > 0) {
                        $qtde = $qtde_cancelar;
                    }

                    if (strlen($distribuidor) > 0) {

                        if (!in_array($login_fabrica,array(10,51,74,81,114))) {

                            if(empty($msg_erro) && in_array($login_fabrica, array(151))){

                                $sqlVerificaExport = "SELECT exportado FROM tbl_pedido WHERE pedido = $pedido;";
                                $resVerificaExport = pg_query($con, $sqlVerificaExport);

                                $pedido_exportado = pg_fetch_result($resVerificaExport, 0, exportado);

                                if (strlen($pedido_exportado) > 0) {

                                    $retorno_cancelamento_send = $cancelaPedidoClass->cancelaTodoPedido($pedido,$cancelar,$motivo);

                                    if(!is_bool($retorno_cancelamento_send)){
                                        $msg_erro = utf8_decode($retorno_cancelamento_send);
				    }else{
					$sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
					$resY = pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				    }
                                }

			    }else{
				$sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
				$resY = pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			    }

                        } else {

                            $sql  = "SELECT fn_pedido_cancela_gama($distribuidor,$login_fabrica,$pedido,$peca,$qtde_cancelar,$aux_motivo,$login_admin)";
                            $resY = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                        }

		    }

		    if($login_fabrica == 178){
			$sql = "UPDATE tbl_os_troca SET fabric = 0 WHERE fabric = {$login_fabrica} AND os = {$os}";
			$resY = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		    }

                }

            }

        } else {//Cancela uma peça do pedido

            if(!empty($os_cancela)){
                $cond_os = " JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                             JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto AND OP.os = $os_cancela ";
                $campo_os = " OP.os        , ";
            }else{
                $campo_os = " null AS os        , ";
            }
            $sql = "SELECT  PI.pedido_item,
                    (PI.qtde - PI.qtde_faturada - PI.qtde_faturada_distribuidor) as qtde       ,
                    PC.peca      ,
                    PC.referencia,
                    PC.descricao ,
                    $campo_os
                    PE.posto     ,
                    PE.distribuidor
                FROM    tbl_pedido       PE
                JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
                JOIN    tbl_peca         PC ON PC.peca       = PI.peca
                $cond_os
                WHERE   PI.pedido      = $pedido
                AND     PI.pedido_item = $cancelar
                AND     PE.fabrica     = $login_fabrica";


            if (!in_array($login_fabrica,$vet_fabrica_cancela)) {
                $sql .= " AND     PE.exportado   IS NULL ";
            }
            //echo nl2br($sql); exit;
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0) {

                $peca         = pg_fetch_result($res, 'peca');
                $referencia   = pg_fetch_result($res, 'referencia');
                $descricao    = pg_fetch_result($res, 'descricao');
                $qtde         = pg_fetch_result($res, 'qtde');
                $os           = pg_fetch_result($res, 'os');
                $posto        = pg_fetch_result($res, 'posto');
                $distribuidor = pg_fetch_result($res, 'distribuidor');

                if (strlen($qtde_cancelar) > 0) {
                    $qtde = $qtde_cancelar;
                }

                if (strlen($msg_erro) == 0) {
                    if (strlen($distribuidor) > 0) {

                        if (!in_array($login_fabrica, $vet_fabrica_cancela)) {

                            $sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
                            $resY = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                        } else {

                            $sql = "SELECT embarque_item,
                            		sum(tbl_pedido_item.qtde - tbl_embarque_item.qtde - tbl_pedido_item.qtde_cancelada) as qtde_embarque_rest,
                            		sum(tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_cancelada) as qtde_pedido_rest
                            	FROM tbl_embarque_item
                            	join tbl_pedido_item using(pedido_item)
                            	where pedido_item = $cancelar
                            	and tbl_embarque_item.qtde = $qtde_cancelar
                            	group by embarque_item";
                            $res = pg_exec($con,$sql);

                            if (pg_num_rows($res) > 0) {

                                $embarque_item = pg_fetch_result($res,0,0);
                                $qtde_embarque_rest = pg_fetch_result($res,0,1);
								$qtde_pedido_rest = pg_fetch_result($res,0,2);
								if($qtde_embarque_rest > 0 and $qtde_embarque_rest == $qtde_pedido_rest and $qtde_embarque_rest == $qtde_cancelar) {
										goto CANCELAR;
								}
                                $sqlcancela = "SELECT fn_cancelar_embarque_item($embarque_item)";
                                $rescancela = pg_query($con,$sqlcancela);
                                $msg_erro .= pg_errormessage($con);

                            } else {

								CANCELAR:

                                if(!empty($os_cancela)){
                                    $sqlOS = "SELECT os_item FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto WHERE os = $os_cancela AND pedido_item = $cancelar";
                                    $resOS = pg_query($con,$sqlOS);
                                    $os_item = pg_fetch_result($resOS,0,'os_item');

                                    $sql  = "SELECT fn_pedido_cancela_garantia($distribuidor,$login_fabrica,$pedido,$peca,$os_item,$aux_motivo,$login_admin)";
                                    $res = pg_query ($con,$sql);
                                }else{
                                    $sqlY = "SELECT fn_pedido_cancela_gama($distribuidor, $login_fabrica, $pedido, $peca, $qtde_cancelar, $aux_motivo,$login_admin)";
                                    $resY = pg_query($con, $sqlY);
                                    $msg_erro .= pg_errormessage($con);
                                }

                            }

                            if (strlen($msg_erro) == 0) {

                                if (in_array($login_fabrica == $vet_gama_salton_tele)) {
                                    $subject  = "Pedido $login_fabrica_nome Cancelado";
                                }

                                $message  = "<b>" . traduz("Cancelamento de Pedido") . "</b><br><br>";
                                $message .= "<b>" . traduz("Admin") . "</b>:" . $login_admin."<br>";
                                $message .= "<b>" . traduz("Posto") . "</b>:" . "<br>";
                                $message .= "<b>" . traduz("OS") . "</b>:" . $os."<br>";
                                $message .= "<b>" . traduz("Pedido") . "</b>:" . $pedido ."<br>";

                                $headers  = "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
                                $headers .= "MIME-Version: 1.0\n";
                                $headers .= "Cc: claudio.silva@telecontrol.com.br\n";
                                $headers .= "Content-type: text/html; charset=iso-8859-1\n";

                                mail("jader.abdo@telecontrol.com.br",$subject,$message,$headers);

                            }

                        }

                    } else {

                        if (in_array($login_fabrica, $vet_fabrica_cancela)) {
                            if (strlen($os_cancela) == 0) $os_cancela ="null";
                        }

                        if (strlen($os) == 0) $os ="null";
                        //Verifica se já foi faturada

                        $sql = "SELECT tbl_pedido_item.qtde - (qtde_cancelada+qtde_faturada) as pendente,                  nota_fiscal
                                    FROM tbl_pedido_item
                                    LEFT JOIN tbl_faturamento_item USING (pedido_item)
                                    LEFT JOIN tbl_faturamento USING(faturamento)
                                   WHERE pedido_item = $cancelar
                                   AND tbl_pedido_item.qtde > (qtde_cancelada+qtde_faturada);";
                        $resY     = pg_query($con, $sql);
                        $pendente = pg_fetch_result($resY, 0, 'pendente');

                        if ($pendente == 0) {
                            $msg_erro .= traduz("A peça $referencia - $descricao do pedido $pedido já está faturada com a nota fiscal"). pg_fetch_result ($resY, 'nota_fiscal');
                        } else {

                            if (in_array($login_fabrica,$vet_fabrica_cancela)) {
                                if(!empty($os_cancela) AND $os_cancela != 'null' AND $login_fabrica <> 74){
                                    $sqlOS = "SELECT os_item, qtde FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto WHERE os = $os_cancela AND pedido_item = $cancelar";
                                    $resOS = pg_query($con,$sqlOS);

                                    $os_item = pg_fetch_result($resOS,0,'os_item');
                                    $qtde_os_item = pg_fetch_result($resOS,0,'qtde');

                                    $sql  = "SELECT fn_pedido_cancela_garantia_item(null,$login_fabrica,$pedido,$peca,$os_item,$aux_motivo,$login_admin,$qtde_cancelar)";
                                    $res = pg_query ($con,$sql);

                                    if (in_array($login_fabrica, array(169,170)) && $qtde_os_item == $qtde_cancelar) {
                                        $sqlServicoCancelado = "
                                            SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND LOWER(descricao) ~ 'cancelado'
                                        ";
                                        $resServicoCancelado = pg_query($con, $sqlServicoCancelado);

                                        if (pg_num_rows($resServicoCancelado) > 0) {
                                            $servicoCancelado = pg_fetch_result($resServicoCancelado, 0, 'servico_realizado');

                                            $cancelaPecaOs = "UPDATE tbl_os_item SET servico_realizado = $servicoCancelado WHERE os_item = $os_item";
                                            $resCancelaPecaOs = pg_query($con, $cancelaPecaOs);

                                            if (strlen(pg_last_error()) > 0) {
                                                $msg_erro = traduz("Erro ao cancelar peça da Ordem de Serviço") . "<br/>";    
                                            }
                                        } else {
                                            $msg_erro = traduz("Erro ao cancelar peça da Ordem de Serviço") . "<br/>";
                                        }
                                    }

                                }else{

                                    //$sql = "SELECT fn_pedido_cancela_gama(null,$login_fabrica,$pedido,$peca,$qtde_cancelar,$aux_motivo,$login_admin)";
                                    $sql = "SELECT fn_pedido_cancela_lenoxx($login_fabrica,$pedido,$peca,$qtde_cancelar,$aux_motivo,$cancelar,$login_admin)";
                                    $res = pg_query ($con,$sql);
                                    $msg_erro .= pg_errormessage($con);
                                }

                            } else {
                                $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde - qtde_faturada - qtde_faturada_distribuidor WHERE pedido_item = $cancelar;";

                                $res = pg_query($con, $sql);
                                $msg_erro .= pg_errormessage($con);

                                $sql = "INSERT INTO tbl_pedido_cancelado (
                                        pedido ,
                                        posto  ,
                                        fabrica,
                                        os     ,
                                        peca   ,
                                        qtde   ,
                                        motivo ,
                                        data   ,
                                        admin  ,
                                        pedido_item
                                    )VALUES(
                                        $pedido,
                                        $posto,
                                        $login_fabrica,
                                        $os_cancela,
                                        $peca,
                                        $qtde,
                                        $aux_motivo,
                                        current_date,
                                        $login_admin,
                                        $cancelar
                                    );";
                                $res = pg_query ($con,$sql);
                                $msg_erro .= pg_errormessage($con);

                            }

                        }
                        if (in_array($login_fabrica, array(94))) {

                            $sqlStatus = "SELECT SUM(qtde) AS qtdeItem,
                                                 SUM(qtde_cancelada) AS qtdeItemCancelado
                                             FROM tbl_pedido_item
                                            WHERE pedido = $pedido";
                            $resStatus = pg_query($con,$sqlStatus);
                            $msg_erro .= pg_errormessage($con);
                            $qtdeItem          = pg_fetch_result($resStatus,0,qtdeItem);
                            $qtdeItemCancelado = pg_fetch_result($resStatus,0,qtdeItemCancelado);

                            if ($qtdeItem == $qtdeItemCancelado) {
                                $sqlAtualiza = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                                $resAtualiza = pg_query($con,$sqlAtualiza);
                                $msg_erro .= pg_errormessage($con);
                            }

                        }

                    }

                }

                $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                if(empty($msg_erro) && in_array($login_fabrica, array(151))){

                    $sqlVerificaExport = "SELECT exportado FROM tbl_pedido WHERE pedido = $pedido;";
                    $resVerificaExport = pg_query($con, $sqlVerificaExport);

                    $pedido_exportado = pg_fetch_result($resVerificaExport, 0, exportado);

                    if (strlen($pedido_exportado) > 0) {

                        $retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido,$cancelar,$motivo);

                        if(!is_bool($retorno_cancelamento_send)){
                            $msg_erro = utf8_decode($retorno_cancelamento_send);
                        }
                    }
		}


		if($login_fabrica == 178){
			$sql = "UPDATE tbl_os_troca SET fabric = 0 WHERE fabric = {$login_fabrica} AND os = {$os} AND peca = {$peca}";
			$resY = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

            } else $msg_erro .= traduz("Pedido já exportado, não é possível excluir peças");

        }

    }

    if (($login_fabrica == 160 or $replica_einhell) && strlen(trim($msg_erro)) == 0) {
        if (!empty($cancelar) && !empty($qtde)) {
            $sql_preco = "SELECT preco FROM tbl_pedido_item WHERE pedido_item = $cancelar";
            $res_preco = pg_query($con,$sql_preco);
            if (pg_num_rows($res_preco) > 0) {
                $xpreco = pg_fetch_result($res_preco, 0, 'preco');
                $xpreco = $xpreco * $qtde_cancelar;
                $sql_atualiza_total = "UPDATE tbl_pedido SET total = total - $xpreco WHERE pedido = $pedido AND fabrica = $login_fabrica";
                $res_atualiza_total = pg_query($con, $sql_atualiza_total);
                $msg_erro .= pg_errormessage($con);
            }
        }
    }

    if (strlen($msg_erro) == 0) {

        $res = pg_query ($con,"COMMIT TRANSACTION");

        /*HD-4004804*/
        $sql = "
            SELECT data_input
            FROM tbl_pedido_cancelado
            WHERE pedido = $pedido
            ORDER BY data_input DESC
            LIMIT 1
        ";
        $res = pg_query($con, $sql);
        $data_input = pg_fetch_result($res, 0, 'data_input');
        if (strlen($data_input) > 0) {
            $Aud->retornaDadosTabela('tbl_pedido_cancelado', array('data_input'=>$data_input, 'pedido'=>$pedido))->enviarLog('insert', "tbl_pedido_cancelado", $login_fabrica."*".$pedido);
            header ("Location: $PHP_SELF?pedido=$pedido");
            exit;
        } else {
            $msg_erro .= traduz("Erro ao gravar o log de alteração.");
        }
    } else {
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }

}

//HD-900300
if (isset($_GET['cancelaTudo']) and !empty($pedido) and strlen($msg_erro) == 0) {
    $motivo = $_GET['motivo'];
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sqlQ = "SELECT descricao,status_pedido
                FROM tbl_tipo_pedido
                JOIN tbl_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
                WHERE tbl_pedido.pedido = $pedido
                AND tbl_pedido.fabrica = $login_fabrica";
    $resQ = pg_query($con,$sqlQ);

    if (pg_num_rows($resQ) > 0) {
        $tipo_pedido = pg_fetch_result($resQ, 0, 'descricao');
 	    $status_pedido = pg_fetch_result($resQ, 0, 'status_pedido');
        if (stripos($tipo_pedido,"GARANTIA") !== FALSE AND $login_fabrica <> 74) {

            $campo = ", tbl_os_produto.os";
            $joins = "LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                      LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto";

        }else{
            $campo = ", null AS os ";
        }

    }

    $sqlT = "SELECT tbl_pedido_item.pedido_item,
            tbl_pedido_item.peca,
            (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)) AS qtde,
            tbl_pedido.posto
            $campo
        FROM tbl_pedido
            JOIN tbl_pedido_item USING(pedido)
            $joins
        WHERE tbl_pedido.fabrica = $login_fabrica
            AND tbl_pedido.pedido = $pedido AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) > 0 ";
    $resT = pg_query($con,$sqlT);

	if (pg_num_rows($resT) > 0) {

        $total = pg_num_rows($resT);

        for ($i = 0; $i < $total; $i++) {

            $pedido_item = pg_fetch_result($resT, $i, 'pedido_item');
            $qtde        = pg_fetch_result($resT, $i, 'qtde');
            $peca        = pg_fetch_result($resT, $i, 'peca');
            $posto       = pg_fetch_result($resT, $i, 'posto');
            $os          = pg_fetch_result($resT, $i, 'os');

            $os = (empty($os)) ? "null" : $os;

            if ($qtde >0 ){

				$array_pedido_item[] = pg_fetch_result($resT, $i, 'pedido_item');

                if(!empty($os) AND $os != 'null' AND $login_fabrica <> 74){

                    $sqlOS = "SELECT os_item,qtde FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto WHERE os = $os AND pedido_item = $pedido_item";
                    $resOS = pg_query($con,$sqlOS);

                    $os_item = pg_fetch_result($resOS,0,'os_item');
                    $qtde_os_item = pg_fetch_result($resOS, 0, 'qtde');

                    $sql  = "SELECT fn_pedido_cancela_garantia_item(null,$login_fabrica,$pedido,$peca,$os_item,E'" . addslashes($motivo) . "',$login_admin,$qtde)";

                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if (in_array($login_fabrica, array(169,170)) && $qtde_os_item == $qtde) {
                        $sqlServicoCancelado = "
                            SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND LOWER(descricao) ~ 'cancelado'
                        ";
                        $resServicoCancelado = pg_query($con, $sqlServicoCancelado);

                        if (pg_num_rows($resServicoCancelado) > 0) {
                            $servicoCancelado = pg_fetch_result($resServicoCancelado, 0, 'servico_realizado');

                            $cancelaPecaOs = "UPDATE tbl_os_item SET servico_realizado = $servicoCancelado WHERE os_item = $os_item";
                            $resCancelaPecaOs = pg_query($con, $cancelaPecaOs);

                            if (strlen(pg_last_error()) > 0) {
                                $msg_erro = traduz("Erro ao cancelar peças da Ordem de Serviço") . "<br/>";    
                            }
                        } else {
                            $msg_erro = traduz("Erro ao cancelar peças da Ordem de Serviço") . "<br/>"; 
                        }
                    }

                }else{
                    $sql = "SELECT fn_pedido_cancela_gama(null,$login_fabrica,$pedido,$peca,$qtde,E'". addslashes($motivo) ."',$login_admin)";
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }
        }
        $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if(empty($msg_erro) and $telecontrol_distrib){
            $sqlStatus = "INSERT INTO tbl_pedido_status (pedido, observacao, admin, status) select pedido, 'Cancelado Total', $login_admin, 14 from tbl_pedido_status where pedido = $pedido and status <> 14";
            $resStatus = pg_query($con, $sqlStatus);
            $msg_erro = pg_last_error($con);
        }

        if(empty($msg_erro) && in_array($login_fabrica, array(151))){

            $sqlVerificaExport = "SELECT exportado FROM tbl_pedido WHERE pedido = $pedido;";
            $resVerificaExport = pg_query($con, $sqlVerificaExport);

            $pedido_exportado = pg_fetch_result($resVerificaExport, 0, exportado);

            if (strlen($pedido_exportado) > 0) {

                $retorno_cancelamento_send = $cancelaPedidoClass->cancelaTodoPedido($pedido,$motivo,$array_pedido_item);

                if(!is_bool($retorno_cancelamento_send)){
                    $msg_erro = utf8_decode($retorno_cancelamento_send);
                }
            }
        }

        if (!strlen($msg_erro) && $login_fabrica == 143) {
            include_once __DIR__."/../rotinas/wackerneuson/exporta-pedido-funcao.php";

            $sql = "SELECT seu_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
            $res = pg_query($con, $sql);

            $pedido_wacker_neuson = pg_fetch_result($res, 0, "seu_pedido");

            if (!empty($pedido_wacker_neuson)) {
                $sql = "UPDATE tbl_pedido SET seu_pedido = NULL WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro = traduz("Erro ao cancelar pedido");
                }

                if (empty($msg_erro)) {
                    $result = deletaPedidoWackerNeuson($pedido_wacker_neuson);

                    if (!empty($result->erroExecucao)) {
                        throw new Exception(traduz("Erro ao cancelar pedido, por favor tente novamente dentro de alguns instantes"));
                    }
                }
            }
        }

    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF?pedido=$pedido");
        $msg_ok = traduz('Pedido $pedido cancelado.');

    } else {
	//die('432');
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        $msg_erro .= traduz("Pedido já exportado, não é possível excluir peças");
    }

}

// HD-2541451 - CANCELAMENTO PARCIAL PEDIDO
if (isset($_POST['cancelar_selecao']) && !empty($pedido)) {

    $pedidosSelecionados = $_POST;

    $res = pg_query ($con,"BEGIN TRANSACTION");

    $pedidoMarcado = false;

    for ($i = 0; $i < $pedidosSelecionados['qtde_itens']; $i++) {
        if ($pedidosSelecionados['marcar_'.$i] == "sim") {
            $pedidoMarcado = true;
            $pedido        = trim($pedidosSelecionados["pedido"]);
            $motivo        = trim($pedidosSelecionados["motivo_tudo"]);
            $cancelar      = trim($pedidosSelecionados["pedido_item_".$i]);
            $qtde_cancelar = trim($pedidosSelecionados["qtde_a_cancelar_".$i]);
            $os_cancela    = trim($pedidosSelecionados["os_".$i]);

            //echo $os_cancela;

            if (strlen($motivo) == 0) $msg_erro   = "Por favor informe o motivo de cancelamento das peças selecionadas";
            else $aux_motivo = "'$motivo'";

            //Cancela todo o pedido quando ele é distribuidor

            if (in_array($login_fabrica, $vet_fabrica_cancela)) {
                if(strlen($qtde_cancelar) == 0) $msg_erro = "Por favor informe a quantidade a cancelar";
            }

            $sql  = "SELECT qtde, qtde_faturada, qtde_cancelada  FROM tbl_pedido_item WHERE pedido_item = $cancelar";
            $res  = pg_query($con, $sql);

            $qtde           = pg_result($res,0,qtde);
            $qtde_faturada  = pg_result($res,0,qtde_faturada);
            $qtde_cancelada = pg_result($res,0,qtde_cancelada);

            $qtde_aux = $qtde - $qtde_cancelada - $qtde_faturada;

            $qtde_cancelar = ($login_fabrica == 52 AND empty($qtde_cancelar)) ? $qtde_aux : $qtde_cancelar;

            if ($qtde_cancelar > $qtde_aux ) {
                $msg_erro = traduz("Quantidade informada maior do que a quantidade de itens cadastrados");
            }
            if ($qtde_cancelar == 0) {
                $msg_erro = traduz("Quantidade informada não pode ser ZERO");
            }

            if (strlen($msg_erro) == 0) {
                if(!empty($os_cancela)) {
                    $cond_os = " JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                                 JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto AND OP.os = $os_cancela ";
                    $campo_os = " OP.os        , ";
                } else {
                    $campo_os = " null AS os        , ";
                    $cond_os = "";
                }
                $sql = "SELECT  PI.pedido_item,
                                (PI.qtde - PI.qtde_faturada - PI.qtde_faturada_distribuidor) as qtde       ,
                                PC.peca      ,
                                PC.referencia,
                                PC.descricao ,
                                $campo_os
                                PE.posto     ,
                                PE.distribuidor
                            FROM    tbl_pedido       PE
                            JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
                            JOIN    tbl_peca         PC ON PC.peca       = PI.peca
                            $cond_os
                            WHERE   PI.pedido      = $pedido
                            AND     PI.pedido_item = $cancelar
                            AND     PE.fabrica     = $login_fabrica";

                if (!in_array($login_fabrica,$vet_fabrica_cancela)) {
                    $sql .= " AND     PE.exportado   IS NULL ";
                }
                /*echo "<pre>";
                echo nl2br($sql);
                echo "</pre>";*/
                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) > 0) {

                    $peca         = pg_fetch_result($res, 'peca');
                    $referencia   = pg_fetch_result($res, 'referencia');
                    $descricao    = pg_fetch_result($res, 'descricao');
                    $qtde         = pg_fetch_result($res, 'qtde');
                    $os           = pg_fetch_result($res, 'os');
                    $posto        = pg_fetch_result($res, 'posto');
                    $distribuidor = pg_fetch_result($res, 'distribuidor');

                    if (strlen($qtde_cancelar) > 0) {
                        $qtde = $qtde_cancelar;
                    }

                    if (strlen($msg_erro) == 0) {
                        if (strlen($distribuidor) > 0) {
                            if (!in_array($login_fabrica, $vet_fabrica_cancela)) {
                                $sql  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
                                $resY = @pg_query ($con,$sql);
                                $msg_erro .= pg_errormessage($con);
                            } else {
                                $sql = "SELECT embarque_item,
                                                sum(tbl_pedido_item.qtde - tbl_embarque_item.qtde - tbl_pedido_item.qtde_cancelada) as qtde_embarque_rest,
                                                sum(tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_cancelada) as qtde_pedido_rest
                                            FROM tbl_embarque_item
                                            join tbl_pedido_item using(pedido_item)
                                            where pedido_item = $cancelar
                                            and tbl_embarque_item.qtde = $qtde_cancelar
                                            group by embarque_item";
                                $res = pg_exec($con,$sql);

                                if (pg_num_rows($res) > 0) {
                                    $embarque_item = pg_result($res,0,0);
                                    $qtde_embarque_rest = pg_result($res,0,1);
                                    $qtde_pedido_rest = pg_result($res,0,2);
                                    if($qtde_embarque_rest > 0 and $qtde_embarque_rest == $qtde_pedido_rest and $qtde_embarque_rest == $qtde_cancelar) {
                                            goto CANCELAR_SELECAO;
                                    }
                                    $sqlcancela = "SELECT fn_cancelar_embarque_item($embarque_item)";
                                    $rescancela = @pg_query($con,$sqlcancela);
                                    $msg_erro .= pg_errormessage($con);

                                } else {
                                    CANCELAR_SELECAO:
                                    if($login_fabrica == 81 AND !empty($os_cancela)){
                                        $sqlOS = "SELECT os_item FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto WHERE os = $os_cancela AND pedido_item = $cancelar";
                                        $resOS = pg_query($con,$sqlOS);
                                        $os_item = pg_result($resOS,0,'os_item');

                                        $sql  = "SELECT fn_pedido_cancela_garantia($distribuidor,$login_fabrica,$pedido,$peca,$os_item,$aux_motivo,$login_admin)";
                                        $res = pg_query ($con,$sql);
                                    }else{
                                        $sqlY = "SELECT fn_pedido_cancela_gama($distribuidor, $login_fabrica, $pedido, $peca, $qtde_cancelar, $aux_motivo,$login_admin)";
                                        $resY = @pg_query($con, $sqlY);
                                        $msg_erro .= pg_errormessage($con);
                                    }
                                }

                                if (strlen($msg_erro) == 0) {
                                    if (in_array($login_fabrica == $vet_gama_salton_tele)) {
                                        $subject  = "Pedido $login_fabrica_nome Cancelado";
                                    }

                                    $message  = "<b>" . traduz("Cancelamento de Pedido") . "</b><br><br>";
                                    $message .= "<b>" . traduz("Admin") . "</b>: ".$login_admin."<br>";
                                    $message .= "<b>" . traduz("Posto") . "</b>: ".$posto."<br>";
                                    $message .= "<b>" . traduz("OS") . "</b>: ".$os."<br>";
                                    $message .= "<b>" . traduz("Pedido") . "</b>: ".$pedido."<br>";

                                    $headers  = "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
                                    $headers .= "MIME-Version: 1.0\n";
                                    $headers .= "Cc: claudio.sliva@telecontrol.com.br\n";
                                    $headers .= "Content-type: text/html; charset=iso-8859-1\n";

                                    mail("jader.abdo@telecontrol.com.br",$subject,$message,$headers);
                                }
                            }
                        } else {
                            if (in_array($login_fabrica, $vet_fabrica_cancela)) {
                                if (strlen($os_cancela) == 0) $os_cancela ="null";
                            }

                            if (strlen($os) == 0) $os ="null";
                            //Verifica se já foi faturada

                            $sql = "SELECT tbl_pedido_item.qtde - (qtde_cancelada+qtde_faturada) as pendente,                  nota_fiscal
                                        FROM tbl_pedido_item
                                        LEFT JOIN tbl_faturamento_item USING (pedido_item)
                                        LEFT JOIN tbl_faturamento USING(faturamento)
                                       WHERE pedido_item = $cancelar
                                       AND tbl_pedido_item.qtde > (qtde_cancelada+qtde_faturada);";

                            $resY     = pg_query($con, $sql);
                            $pendente = pg_result($resY, 0, 'pendente');

                            if ($pendente == 0) {
                                $msg_erro .= "A peça $referencia - $descricao do pedido $pedido já está faturada com a nota fiscal ".pg_fetch_result($resY, 'nota_fiscal');
                            } else {
                                if (in_array($login_fabrica,$vet_fabrica_cancela)) {
                                    if(!empty($os_cancela) AND $os_cancela != 'null' AND $login_fabrica <> 74) {
                                        $sqlOS = "SELECT os_item FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto WHERE os = $os_cancela AND pedido_item = $cancelar";
                                        $resOS = pg_query($con,$sqlOS);
                                        $os_item = pg_result($resOS,0,'os_item');

                                        $sql  = "SELECT fn_pedido_cancela_garantia_item(null,$login_fabrica,$pedido,$peca,$os_item,$aux_motivo,$login_admin,$qtde_cancelar)";
                                        $res = pg_query ($con,$sql);

                                    }else{
                                        $sql = "SELECT fn_pedido_cancela_gama(null,$login_fabrica,$pedido,$peca,$qtde_cancelar,$aux_motivo,$login_admin)";
                                        $res = pg_query ($con,$sql);
                                        $msg_erro .= pg_errormessage($con);
                                    }
                                } else {

                                    $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde - qtde_faturada - qtde_faturada_distribuidor WHERE pedido_item = $cancelar;";

                                    $res = @pg_query($con, $sql);
                                    $msg_erro .= pg_errormessage($con);

                                    $sql = "INSERT INTO tbl_pedido_cancelado (
                                                                                        pedido ,
                                                                                        posto  ,
                                                                                        fabrica,
                                                                                        os     ,
                                                                                        peca   ,
                                                                                        qtde   ,
                                                                                        motivo ,
                                                                                        data   ,
                                                                                        admin  ,
                                                                                        pedido_item
                                                                                    )VALUES(
                                                                                        $pedido,
                                                                                        $posto,
                                                                                        $login_fabrica,
                                                                                        $os_cancela,
                                                                                        $peca,
                                                                                        $qtde,
                                                                                        $aux_motivo,
                                                                                        current_date,
                                                                                        $login_admin,
                                                                                        $cancelar
                                                                                    );";
                                    $res = pg_query ($con,$sql);
                                    $msg_erro .= pg_errormessage($con);
                                }
                            }

                            if (in_array($login_fabrica, array(94))) {

                                $sqlStatus = "SELECT SUM(qtde) AS qtdeItem,
                                                     SUM(qtde_cancelada) AS qtdeItemCancelado
                                                 FROM tbl_pedido_item
                                                WHERE pedido = $pedido";
                                $resStatus = pg_query($con,$sqlStatus);
                                $msg_erro .= pg_errormessage($con);
                                $qtdeItem          = pg_fetch_result($resStatus,0,qtdeItem);
                                $qtdeItemCancelado = pg_fetch_result($resStatus,0,qtdeItemCancelado);

                                if ($qtdeItem == $qtdeItemCancelado) {
                                    $sqlAtualiza = "UPDATE tbl_pedido SET status_pedido = 14 WHERE pedido = $pedido";
                                    $resAtualiza = pg_query($con,$sqlAtualiza);
                                    $msg_erro .= pg_errormessage($con);
                                }
                            }
                        }
                    }

                    $sql = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if(empty($msg_erro) && in_array($login_fabrica, array(151))){
                        $sqlVerificaExport = "SELECT exportado FROM tbl_pedido WHERE pedido = $pedido;";
                        $resVerificaExport = pg_query($con, $sqlVerificaExport);

                        $pedido_exportado = pg_fetch_result($resVerificaExport, 0, exportado);

                        if (strlen($pedido_exportado) > 0) {

                            $retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido,$cancelar,$motivo);

                            if(!is_bool($retorno_cancelamento_send)){
                                $msg_erro = utf8_decode($retorno_cancelamento_send);
                            }
                        }
                    }
                } else $msg_erro .= traduz("Pedido já exportado, não é possível excluir peças");
            }
        }
    }

    if (!$pedidoMarcado) {
        $msg_erro = traduz("Nenhuma peça foi marcada para exclusão de itens!");
    }
    if (strlen($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF?pedido=$pedido");
        exit;
    } else {
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }

}

// FIM - HD-2541451 - CANCELAMENTO PARCIAL PEDIDO

$aprovar = trim($_GET["aprovar"]);

if (strlen($aprovar) > 0 AND strlen($pedido) > 0) {

    $res_os = pg_query($con,"BEGIN TRANSACTION");

    $sql = "UPDATE tbl_pedido SET data_aprovacao = CURRENT_TIMESTAMP,status_pedido=null
            WHERE tbl_pedido.fabrica = $login_fabrica
            AND   tbl_pedido.pedido  = $pedido ";

    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg_ok = traduz('Pedido $pedido aprovado.');
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }

}

# Somente o Ronaldo irá tirar a Finalização para conseguir Alterar o pedido da Loja Virtual
if (strlen($retirar_finalizado) > 0 AND strlen($pedido) > 0) {

    $res_os = pg_query($con,"BEGIN TRANSACTION");

    $sql = "UPDATE  tbl_pedido SET finalizado = null,status_pedido = null
            WHERE   tbl_pedido.fabrica = 10
            AND     tbl_pedido.pedido  = $pedido ";

    $res        = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if (strlen($msg_erro) == 0) {

        $res    = pg_query($con,"COMMIT TRANSACTION");
        $msg_ok = traduz("O Pedido $pedido foi desfeito a finalização. É NECESSÁRIO logar como posto na LOJA VIRTUAL alterar e finalizar NOVAMENTE!");

    } else {

        $res      = pg_query($con,"ROLLBACK TRANSACTION");
        $msg_erro = traduz("Não foi possível desfazer a FINALIZAÇÃO do Pedido $pedido para que fosse possível alterar e finalizar NOVAMENTE!.");

    }

}

#------------ Le Pedido da Base de dados ------------#
//HD 11871 Paulo
if ($login_fabrica == 24) {
    $sql_admin_select = " ,admin_alteracao.login      AS login_alteracao              ";
    $sql_admin_join   = " LEFT JOIN tbl_admin as admin_alteracao ON tbl_pedido.admin_alteracao            = admin_alteracao.admin ";
}

if ($login_fabrica == 42) {

    $aux_filial_makita = " , distrib_filial.nome_fantasia AS filial_fantasia ";

    $sql_filial_makita = " LEFT JOIN tbl_posto_fabrica distrib_filial ON distrib_filial.posto = tbl_pedido.filial_posto AND distrib_filial.fabrica = $login_fabrica";

}

if ($login_fabrica == 191){
	$aux_filial_pedido = ", fp.cnpj AS cnpj_filial, fp.nome AS nome_filial";
	$sql_filial_pedido = "LEFT JOIN tbl_posto fp ON fp.posto = tbl_pedido.filial_posto";
}

if(in_array($login_fabrica ,array(88,156))){
    $nome_transportadora = " tbl_transportadora.fantasia AS transportadora_nome, ";
    $join_transportadora = " LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora ";
}

if(in_array($login_fabrica,array(94))){
    $nome_transportadora = " tbl_transportadora.nome AS transportadora_nome,tbl_forma_envio.descricao AS desc_forma_envio, ";
    $join_transportadora = " LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora LEFT JOIN tbl_forma_envio ON(tbl_forma_envio.forma_envio = tbl_pedido.forma_envio)";
}

if ($login_fabrica == 183){
    $nome_transportadora = " tbl_transportadora.nome AS transportadora_nome,    tbl_transportadora.cnpj AS transportadora_cnpj, ";
    $join_transportadora = " LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora";
}

if (strlen ($pedido) > 0) {
    if($login_fabrica == 1){
        $campo_pedido_offline = ' pedido_offline, ';
    }

    if($login_fabrica == 87){
        $nome_admin = " tbl_admin.nome_completo, ";
    }

    if ($login_fabrica == 146) {
        $column_marca = ",tbl_marca.nome AS marca";
        $join_marca = "inner join tbl_marca on tbl_marca.marca = tbl_pedido.visita_obs::integer";
    }

    $classe_previsao = "tbl_classe_pedido.classe                                                      ,
            tbl_pedido.previsao_entrega                                                   ,";


    if(in_array($login_fabrica, array(104,153,160)) || $telecontrol_distrib){
        $campo_complemento = "tbl_pedido.atende_pedido_faturado_parcial , ";
    }


    if($telecontrol_distrib){
        $pedido_hd_chamado_extra = " tbl_hd_chamado_extra.pedido as pedido_hd_chamado_extra, tbl_hd_chamado_extra.hd_chamado,  ";
    }


    if ($login_fabrica == 175){
        $valoresAdicionais = "
            JSON_FIELD('valor_frete',tbl_pedido.valores_adicionais)    AS pedidoValorFrete,
            JSON_FIELD('valor_despesa',tbl_pedido.valores_adicionais)  AS pedidoValorDespesa,
            JSON_FIELD('valor_seguro',tbl_pedido.valores_adicionais)   AS pedidoValorSeguro,
            JSON_FIELD('valor_desconto',tbl_pedido.valores_adicionais) AS pedidoValorDesconto,
        ";
    }

    if($login_fabrica == 183){
	$valoresAdicionais = " JSON_FIELD('nota_fiscal_posto_pedido',tbl_pedido.valores_adicionais)    AS notaFiscalCliente,";
    }

    $sql = "
        SELECT  tbl_pedido.pedido,
                tbl_pedido.total,
                tbl_pedido.posto,
                tbl_admin.nome_completo,
                $campo_pedido_offline
                $nome_admin
                $pedido_hd_chamado_extra
                CASE
                    WHEN tbl_pedido.pedido_blackedecker > 499999 THEN
                        LPAD ((tbl_pedido.pedido_blackedecker-500000)::TEXT,5,'0')
                    WHEN tbl_pedido.pedido_blackedecker > 399999 THEN
                        LPAD ((tbl_pedido.pedido_blackedecker-400000)::TEXT,5,'0')
                    WHEN tbl_pedido.pedido_blackedecker > 299999 THEN
                        LPAD ((tbl_pedido.pedido_blackedecker-300000)::TEXT,5,'0')
                    WHEN tbl_pedido.pedido_blackedecker > 199999 THEN
                        LPAD ((tbl_pedido.pedido_blackedecker-200000)::TEXT,5,'0')
                    WHEN tbl_pedido.pedido_blackedecker > 99999 THEN
                        LPAD ((tbl_pedido.pedido_blackedecker-100000)::TEXT,5,'0')
                ELSE
                    LPAD ((tbl_pedido.pedido_blackedecker)::TEXT,5,'0')
                END                                          AS pedido_blackedecker,
                tbl_pedido.seu_pedido,
                tbl_pedido.condicao,
                tbl_pedido.tabela,
                tbl_pedido.pedido_cliente,
                tbl_pedido.pedido_acessorio,
                tbl_pedido.pedido_sedex,
                tbl_pedido.etiqueta_servico,
                $valoresAdicionais
                JSON_FIELD('categoria_pedido',tbl_pedido.valores_adicionais)     AS categoria_pedido,
                $campo_complemento
                tbl_pedido.status_pedido,
                tbl_pedido.distribuidor,
                TO_CHAR(tbl_pedido.data,                'DD/MM/YYYY HH24:MI:SS') AS data_pedido,
                TO_CHAR(tbl_pedido.finalizado,          'DD/MM/YYYY HH24:MI:SS') AS data_finalizado,
                TO_CHAR(tbl_pedido.exportado,           'DD/MM/YYYY HH24:MI:SS') AS data_exportado,
                TO_CHAR(tbl_pedido.aprovado_cliente,    'DD/MM/YYYY HH24:MI:SS') AS aprovado_cliente,
                TO_CHAR(tbl_pedido.recebido_posto,      'DD/MM/YYYY')            AS recebido_posto,
                TO_CHAR(tbl_pedido.data_validade,       'DD/MM/YYYY')            AS data_validade,
                TO_CHAR(tbl_pedido.controle_exportacao, 'DD/MM/YYYY')            AS controle_exportacao,
                tbl_pedido.tipo_pedido                                           AS tipo_pedido,
                tbl_tipo_pedido.descricao                                        AS tipo_descricao,
                tbl_tipo_pedido.pedido_em_garantia                               AS tipo_pedido_garantia,
                tbl_pedido.valores_adicionais,
                CASE
                    WHEN $login_fabrica = 88 OR $login_fabrica = 157 THEN
                        COALESCE(tbl_posto_fabrica.desconto, 0)
                    ELSE
                        COALESCE(tbl_pedido.desconto, 0)
                END AS pedido_desconto,
                tbl_condicao.descricao                      AS condicao_descricao,
                tbl_tabela.tabela,
                tbl_tabela.descricao                        AS tabela_descricao,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.contato_cep               AS posto_cep,
                tbl_posto.nome                              AS nome_posto,
                tbl_posto.cnpj, 
                tbl_pedido.status_fabricante,
                tbl_pedido.origem_cliente,
                tbl_pedido.transportadora,
                $nome_transportadora
                tbl_pedido.tipo_frete,
                tbl_pedido.valor_frete,
                tbl_pedido.pedido_os,
                $classe_previsao
                tbl_status_pedido.descricao                 AS status,
                tbl_pedido.rejeitado_motivo,
                admin_altera.nome_completo                  AS admin_alteracao,
                tbl_pedido.obs                              AS obs,
                CASE
                    WHEN tbl_hd_chamado_extra.hd_chamado IS NOT NULL
                    THEN tbl_hd_chamado_extra.nome
                    ELSE (
                        SELECT DISTINCT ON (tbl_os.os)
                            COALESCE(tbl_os.consumidor_nome, tbl_os.revenda_nome)
                        FROM tbl_os
                        JOIN tbl_pedido_item pi ON pi.pedido = tbl_pedido.pedido
                        JOIN tbl_os_item    ON pi.pedido_item = tbl_os_item.pedido_item
                        JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        WHERE tbl_os.os = tbl_os_produto.os
                        AND tbl_os.fabrica = {$login_fabrica}
                        LIMIT 1
                    )
                END AS nome_destinatario
                {$column_marca}
                $sql_admin_select
                $aux_filial_makita
		$aux_filial_pedido
        FROM    tbl_pedido
        JOIN    tbl_posto           ON tbl_posto.posto              = tbl_pedido.posto
   LEFT JOIN    tbl_posto_fabrica   ON tbl_posto_fabrica.posto      = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
   LEFT JOIN    tbl_condicao        ON tbl_condicao.condicao        = tbl_pedido.condicao
   LEFT JOIN    tbl_tipo_pedido     ON tbl_tipo_pedido.tipo_pedido  = tbl_pedido.tipo_pedido
   LEFT JOIN    tbl_tabela          ON tbl_tabela.tabela            = tbl_pedido.tabela
   LEFT JOIN    tbl_admin               ON tbl_pedido.admin         = tbl_admin.admin
   LEFT JOIN    tbl_admin admin_altera  ON admin_altera.admin       = tbl_pedido.admin_alteracao
   LEFT JOIN    tbl_status_pedido   ON tbl_pedido.status_pedido     = tbl_status_pedido.status_pedido
   LEFT JOIN    tbl_classe_pedido   ON tbl_pedido.classe_pedido     = tbl_classe_pedido.classe_pedido
   LEFT JOIN    tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
        $sql_admin_join
        $sql_filial_makita
	$sql_filial_pedido
        $join_transportadora
        {$join_marca}
        WHERE   tbl_pedido.pedido  = $pedido
        AND     tbl_pedido.fabrica = $login_fabrica;";
    $res = pg_query ($con, $sql);

    if (pg_num_rows ($res) > 0) {

        $pedido                 = trim(pg_fetch_result($res, 0, 'pedido'));
        $etiqueta_servico                 = trim(pg_fetch_result($res, 0, 'etiqueta_servico'));
        $tbl_pedido_total       = pg_fetch_result($res, 0, "total");
        $pedido_condicao        = trim(pg_fetch_result($res, 0, 'condicao'));
        $condicao               = trim(pg_fetch_result($res, 0, 'condicao_descricao'));
        $tabela                 = trim(pg_fetch_result($res, 0, 'tabela'));
        $tabela_descricao       = trim(pg_fetch_result($res, 0, 'tabela_descricao'));
        $pedido_cliente         = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
        $pedido_acessorio       = trim(pg_fetch_result($res, 0, 'pedido_acessorio'));
        $pedido_sedex           = trim(pg_fetch_result($res, 0, 'pedido_sedex'));
        $data_pedido            = trim(pg_fetch_result($res, 0, 'data_pedido'));
        $aprovado_cliente       = trim(pg_fetch_result($res, 0, 'aprovado_cliente'));
        $data_finalizado        = trim(pg_fetch_result($res, 0, 'data_finalizado'));
        $data_exportado         = trim(pg_fetch_result($res, 0, 'data_exportado'));
        $posto                  = trim(pg_fetch_result($res, 0, 'posto'));
        $codigo_posto           = trim(pg_fetch_result($res, 0, 'codigo_posto'));
        $posto_cep              = trim(pg_fetch_result($res, 0, posto_cep));
        $nome_posto             = trim(pg_fetch_result($res, 0, 'nome_posto'));

        if($login_fabrica == 42){
            $cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
        }

        $pedido_blackedecker    = trim(pg_fetch_result($res, 0, 'pedido_blackedecker'));
        $seu_pedido             = trim(pg_fetch_result($res, 0, 'seu_pedido'));
        $nome_completo          = trim(pg_fetch_result($res, 0, 'nome_completo'));
        $data_recebido          = trim(pg_fetch_result($res, 0, 'recebido_posto'));
        $tipo_pedido_id         = trim(pg_fetch_result($res, 0, 'tipo_pedido'));
        $tipo_pedido            = trim(pg_fetch_result($res, 0, 'tipo_descricao'));
        $tipo_pedido_garantia   = trim(pg_fetch_result($res, 0, 'tipo_pedido_garantia'));
        $pedido_desconto        = trim(pg_fetch_result($res, 0, 'pedido_desconto'));
        $status_pedido          = trim(pg_fetch_result($res, 0, 'status_pedido'));
        $status                 = pg_fetch_result($res, 0, 'status');
        $distribuidor           = trim(pg_fetch_result($res, 0, 'distribuidor'));
        $status_fabricante      = trim(pg_fetch_result($res, 0, 'status_fabricante'));
        $origem_cliente         = trim(pg_fetch_result($res, 0, 'origem_cliente'));
        $pedido_os              = trim(pg_fetch_result($res, 0, 'pedido_os'));
        $transportadora         = trim(pg_fetch_result($res, 0, 'transportadora'));
        $transportadora_nome    = trim(pg_fetch_result($res, 0, 'transportadora_nome'));
        $transportadora_cnpj    = trim(pg_fetch_result($res, 0, 'transportadora_cnpj'));
        $tipo_frete             = trim(pg_fetch_result($res, 0, 'tipo_frete'));
        $valor_frete            = trim(pg_fetch_result($res, 0, 'valor_frete'));
        $valores_adicionais     = pg_fetch_result($res, 0, 'valores_adicionais');
        $rejeitado_motivo       = pg_fetch_result($res, 0, 'rejeitado_motivo');
        $nome_destinatario      = pg_fetch_result($res, 0, 'nome_destinatario');

        if($telecontrol_distrib){
            $pedido_hd_chamado_extra    = pg_fetch_result($res, 0, 'pedido_hd_chamado_extra');
            $hd_chamado                 = pg_fetch_result($res, 0, 'hd_chamado');
        }

        $venda_direta = false;
        if($tipo_pedido == "Embarcado"){
            $sql_embarque = "SELECT tbl_embarque_item.embarque_item,
                            (select status from tbl_pedido_status WHERE tbl_pedido_status.pedido = tbl_pedido_item.pedido ORDER BY tbl_pedido_status.data DESC limit 1 ) as status
                                from tbl_embarque_item
                                join tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
                                WHERE tbl_pedido_item.pedido = $pedido ";
            $res_embarque = pg_query($con, $sql_embarque);
            if(pg_num_rows($res_embarque)==0){
                $venda_direta = true;
            }else{
                $status_pedido_embarcado = pg_fetch_result($res_embarque, 0, 'status');
            }
        }

        if ($login_fabrica == 175){
            $pedidoValorFrete     = pg_fetch_result($res, 0, 'pedidoValorFrete');
            $pedidoValorDespesa   = pg_fetch_result($res, 0, 'pedidoValorDespesa');
            $pedidoValorSeguro    = pg_fetch_result($res, 0, 'pedidoValorSeguro');
            $pedidoValorDesconto  = pg_fetch_result($res, 0, 'pedidoValorDesconto');
	}

	if($login_fabrica == 183){
		$notaFiscalCliente = pg_fetch_result($res, 0, 'notaFiscalCliente');
	}

        if($login_fabrica == 1){
            $pedido_offline = pg_fetch_result($res, 0, pedido_offline);
            if(strlen($pedido_offline) >0){
                $sql_pedido_offline = "select pedido, seu_pedido from tbl_pedido where pedido_offline = $pedido_offline and fabrica = $login_fabrica and pedido_offline is not null and pedido_offline > 0";
                $res_pedido_offline = pg_query($con, $sql_pedido_offline);
                for($x=0; $x<pg_num_rows($res_pedido_offline); $x++){
                    $seuPedido = pg_fetch_result($res_pedido_offline, $x, seu_pedido);
                    $numeroPedido     = pg_fetch_result($res_pedido_offline, $x, pedido);

            $link_pedidos .= "<a target='_blank' href='pedido_admin_consulta.php?pedido=$numeroPedido'>$seuPedido</a> ";

                }
            }

            $categoria_pedido    = trim(pg_result ($res,0,categoria_pedido));

            switch($categoria_pedido) {
                case "cortesia":
                    $categoria_pedido_descricao = "CORTESIA";
                    break;
                case "credito_bloqueado":
                    $categoria_pedido_descricao = "CRÉDITO BLOQUEADO";
                    break;
                case "erro_pedido":
                    $categoria_pedido_descricao = "ERRO DE PEDIDO";
                    break;
                case "kit":
                    $categoria_pedido_descricao = "KIT DE REPARO";
                    break;
                case "midias":
                    $categoria_pedido_descricao = "MÍDIAS";
                    break;
                case "outros":
                    $categoria_pedido_descricao = "OUTROS";
                    break;
                case "valor_minimo":
                    $categoria_pedido_descricao = "VALOR MÍNIMO";
                    break;
                case "vsg":
                    $categoria_pedido_descricao = "VSG";
                    break;
                case "divergencia":
                    $categoria_pedido_descricao = "DIVERGÊNCIAS LOGÍSTICA/ESTOQUE";
                    break;
                case "problema_distribuidor":
                    $categoria_pedido_descricao = "PROBLEMAS COM DISTRIBUIDOR";
                    break;
                case "acessorios":
                    $categoria_pedido_descricao = "ACESSÓRIOS";
                    break;
                case "item_similar":
                    $categoria_pedido_descricao = "ITEM SIMILAR";
                    break;
                default:
                    $categoria_pedido_descricao = "";
                    break;
            }
        }

        if ($login_fabrica == 94) {
            $desc_forma_envio = pg_fetch_result($res, 0, 'desc_forma_envio');
            $admin_alteracao = pg_fetch_result($res, 0, 'admin_alteracao');
        }
        if(in_array($login_fabrica, [104,147,153,160]) || $telecontrol_distrib){
            $atende_parcial = pg_fetch_result($res, 0, 'atende_pedido_faturado_parcial');
            $atende_parcial = ($atende_parcial == 't') ? "Sim" : "Não";
        }
        if($login_fabrica == 87){
            $classe_pedido         = trim(pg_fetch_result($res, 0, 'classe'));
            $data_desejada         = trim(pg_fetch_result($res, 0, 'previsao_entrega'));
            $nome_completo         = trim(pg_fetch_result($res, 0, 'nome_completo'));
        }
        $controle_exportacao = trim(pg_fetch_result($res, 0, 'controle_exportacao'));
		$data_validade = pg_fetch_result($res, 0, 'data_validade');

        if ($login_fabrica == 146) {
            $marca = pg_fetch_result($res, 0, "marca");
        }

        if ($login_fabrica == 24) {
            $login_alteracao     = trim(pg_fetch_result($res, 0, 'login_alteracao'));
        }

        if (in_array($login_fabrica, array(1,157,158,169,170,175))) {
            $atendimento_sac     = trim(pg_fetch_result($res, 0, 'obs'));
        }

        if ($login_fabrica == 42) {//HD 825655

            $distrib_fantasia = trim(pg_fetch_result($res, 0, 'filial_fantasia'));
            if (strlen($distrib_fantasia) == 0) $distrib_fantasia = '<b>Fabrica</b>';

            $obs = pg_fetch_result($res, 0, 'obs');
            $obs = json_decode($obs, true);
        }

	if ($login_fabrica == 191){
		$cnpj_filial = pg_fetch_result($res, 0, 'cnpj_filial');
		$nome_filial = pg_fetch_result($res, 0, 'nome_filial'); 
	}

        if(in_array($login_fabrica, array(161,162))){
            $valores_adicionais = json_decode($valores_adicionais, true);

            $desconto_fabricante    = $valores_adicionais['valor_desconto_fabricante'];
            $adicional_fabricante   = $valores_adicionais['adicional_fabricante'];

            $pedido_desconto = $desconto_fabricante;

        }

        if (strlen ($login) == 0) $login = "Posto";

        #if ($login_fabrica <> 15) {
            $detalhar = "ok";
        #}

        if ($login_fabrica == 1 AND $pedido_acessorio == "t") {
            $pedido_blackedecker = intval($pedido_blackedecker + 1000);
        }

        if (strlen($seu_pedido) > 0) {
            $pedido_blackedecker = fnc_so_numeros($seu_pedido);
        }

        $pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

    }


    if (in_array($login_fabrica,array(6,46,95,101,108,111,115,116,117,120,201,121,122)) or $login_fabrica >= 123) {

        $sqlOs = "SELECT DISTINCT os,sua_os
                       FROM tbl_os_produto
					   JOIN tbl_os_item USING(os_produto)
						JOIN tbl_os using(os)
                    WHERE tbl_os_item.pedido = $pedido";
        $resOs = pg_query($con,$sqlOs);

        if (pg_num_rows($resOs) > 0) {
            $os = pg_fetch_result($resOs, 0, 'os');
            $sua_os = pg_fetch_result($resOs, 0, 'sua_os');
        }

        if ($login_fabrica == 178){
            $sql_os_extra = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
            $res_os_extra = pg_query($con, $sql_os_extra);

            if (pg_num_rows($res_os_extra) > 0){
                $id_os_revenda = pg_fetch_result($res_os_extra, 0, "os_revenda");
            }
        }
    }

	if ($login_fabrica == 104 AND strtoupper($tipo_pedido) == "GARANTIA"){
		$vet_fabrica_cancela = array();
	}

    /**
     * - Verificação

     */
    if ($login_fabrica == 101 && stripos($tipo_pedido,'troca') !== FALSE) {
        $trocaConsumidor = 'f';
        $sqlDest = "
            SELECT  tbl_os_troca.envio_consumidor
            FROM    tbl_os_troca
            JOIN    tbl_os_produto USING(os)
            JOIN    tbl_os_item USING(os_produto)
            WHERE   tbl_os_item.pedido = $pedido
        ";
        $resDest = pg_query($con,$sqlDest);

        if (pg_fetch_result($resDest,0,0) == 't') {
            $trocaConsumidor = 't';
            $sqlEnd = "
                SELECT  tbl_os.consumidor_nome,
                        tbl_os.consumidor_cpf
                FROM    tbl_os
                JOIN    tbl_os_produto USING(os)
                JOIN    tbl_os_item USING(os_produto)
                WHERE   tbl_os_item.pedido = $pedido
                AND     tbl_os.fabrica = $login_fabrica
            ";
            $resEnd = pg_query($con,$sqlEnd);

            $consumidorNome = pg_fetch_result($resEnd,0,consumidor_nome);
            $consumidorCpf  = pg_fetch_result($resEnd,0,consumidor_cpf);
        }
    }
}

if (strpos($msg_erro,"ERROR:") !== false) {
    $x = explode('ERROR:',$msg_erro);
    $msg_erro = $x[1];
}

$title = traduz("CONFIRMAÇÃO DE PEDIDO DE PEÇAS");

if ($login_fabrica == 1) {
    $title = traduz("CONFIRMAÇÃO DE PEDIDO DE PEÇAS / PRODUTOS");
}


include "cabecalho.php"; ?>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<style>
#venda_direta, #conferencia_embarque{
    cursor: pointer;
    color:rgb(89, 109, 155);
    text-align: center;
    font-weight: bold;
}
.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 0px solid;
    color:#ffffff;
    background-color: #596D9B
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.Tabela{
    font-family: Verdana,Sans;
    font-size: 10px;
}
.Tabela thead{
    font-size: 12px;
    font-weight:bold;
}
.table_line1 {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}
.table_line1_pendencia {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    color: #FF0000;
}

.menu_top2 {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: normal;
    color: #000000;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

table.tabela tr td{
    font-family: arial, verdana;
    font-size: 10px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<!--[if lt IE 8]>
<style>
table.tabela{
    empty-cells:show;
    border-collapse:collapse;
    border-spacing: 2px;
}
</style>
<![endif]-->

<!-- <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script> -->
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>

<script type="text/javascript">
<?php if (isFabrica(42)): ?>
function mostraPrevPeca(self) {
    var data = $(self).data();
    var qArr = [];
    var qObj = {
        categoria: 'pendencias_de_pecas',
        'peca_faltante[]': data.peca,
        pedido: '<?=$seu_pedido ? : $pedido?>',
        garantia: '<?=strpos(strtolower($tipo_pedido), 'gar') !== false ? 't':'f'?>',
        data_pedido: data.exportado,
        btnEnviar: 'Enviar'
    };

    for (i in qObj)
        qArr.push(i+"="+qObj[i]);

    data.href = 'helpdesk_cadastrar.php?' + encodeURI(qArr.join('&'));

    var pedidoPecaMsg = "<div class='message'><h2 class='titulo'>" + "<?php echo traduz("Disponibilidade da peça"); ?>" + "</h2>" +
        "<table class='table table-compact table-striped table-bordered' style='height:auto'>" +
        "<thead><tr><th>" + "<?= traduz('referencia'); ?>"+ "</th><th>" + "<?=traduz('descricao'); ?>" + "</th><th>" + "<?=traduz('previsao'); ?>" + "</th></tr></thead>" +
            "<tbody><tr><td>"+data.ref+"</td><td>"+data.desc+"</td><td>"+data.prevista+"</td></tr></tbody></table></div>";
    // return pedidoPecaMsg;
    Shadowbox.open({
        player: 'html',
        content: pedidoPecaMsg,
        width: 550,
        height: 250
    });
}
<?php endif; ?>


$().ready(function(){
    Shadowbox.init();
    $("input[name=qtde_a_cancelar]").numeric();

<?php if ($login_fabrica == 168) { ?>
    $("#volume").numeric();
    $("#valor_frete_t").numeric({allow:","});
    $("#peso_real").numeric({allow:","});

    $("#caixa").change(function(){
        var caixa = $(this).val();

        if (caixa == "tamanho_personalizado") {
            $(".box_tamanho_personalizado").css("visibility","visible");
        } else {
            $(".box_tamanho_personalizado").css("visibility","hidden");
        }
    });

    $("#tipo_tamanho_personalizado").change(function(){
        var tipo = $(this).val();
        switch (tipo) {
            case '1':
                $("#alt_pers").attr("disabled",false).val("");
                break;
            case '2':
            case '3':
                $("#alt_pers").attr("disabled",true).val(0);
                break;
        }

        if (tipo == '3') {
            $("#larg_diam").text("D");
        } else {
            $("#larg_diam").text("L");
        }
    });

    $("#cotar_frete").click(function(e){
        e.preventDefault();

        var caixa       = $("#caixa").val();
        var peso        = $("#peso_real").val();
        var volume      = $("#volume").val();
        var cep         = $("#posto_cep").val();
        var valor_nota  = $("#total_pedido").val();

        if (volume == "") {
            alert("<?php echo traduz('Favor, inserir o valor do volume'); ?>");
        }

        if (peso == "") {
            alert("<?php echo traduz('Favor, inserir o peso real do pacote'); ?>");
        }

        if (caixa == "tamanho_personalizado") {
            var c       = $("#comp_pers").val();
            var l       = $("#larg_pers").val();
            var a       = $("#alt_pers").val();
            var tipo    = $("#tipo_tamanho_personalizado").val();

            var comprimento = c == "" ? 0 : parseInt(c);
            var largura     = l == "" ? 0 : parseInt(l);
            var altura      = a == "" ? 0 : parseInt(a);

            if(comprimento == 0){

                alert("<?php echo traduz('Por favor insira o Comprimento!'); ?>")
                $("#comp_pers").focus();
                return;

            } else if (largura == 0) {

                alert("<?php echo traduz('Por favor insira a Largura!'); ?>")
                $("#larg_pers").focus();
                return;

            } else if (altura == 0 && tipo != "2" && tipo != "3") {

                alert("<?php echo traduz('Por favor insira a Altura!'); ?>")
                $("#alt_pers").focus();
                return;

            } else {

                /* Pacotes e caixas */
                if (tipo == "1") {

                    if(comprimento < 16 || comprimento > 105){

                        alert("<?php echo traduz('O Comprimento deve estar entre 16 cm e 105 cm.'); ?>")
                        $("#comp_pers").focus();
                        return;

                    }

                    if(largura < 11 || largura > 105){

                        alert("<?php echo traduz('A Largura deve estar entre 11 cm e 105 cm.'); ?>")
                        $("#larg_pers").focus();
                        return;

                    }

                    if(altura < 2 || altura > 105){

                        alert("<?php echo traduz('A Altura deve estar entre 2 cm à 105 cm.'); ?>")
                        $("#alt_pers").focus();
                        return;

                    }

                    var total_tamanho_personalizado = comprimento + largura + altura;

                    if(total_tamanho_personalizado < 29 || total_tamanho_personalizado > 200){

                        alert("<?php echo traduz('A soma das medidas deve estar entre 29 cm e 200 cm.'); ?>")
                        return;

                    }

                }

                /* Envelopes */
                if (tipo == "2") {

                    $("#alt_pers").val(0);
                    $("#alt_pers").attr("disabled", true);

                    if (comprimento < 16 || comprimento > 60) {

                        alert("<?php echo traduz('O Comprimento deve estar entre 16 cm e 60 cm.'); ?>")
                        $("#comp_pers").focus();
                        return;

                    }

                    if (largura < 11 || largura > 60) {

                         alert("<?php echo traduz('A Largura deve estar entre 11 cm e 60 cm.'); ?>")
                        $("#larg_pers").focus();
                        return;

                    }

                    altura = 0;

                }

                /* Rolos e Cilindros */
                if (tipo == "3") {

                    $("#alt_pers").val(0);
                    $("#alt_pers").attr("disabled", true);

                    if (comprimento < 18 || comprimento > 105) {

                        alert("<?php echo traduz('O Comprimento deve estar entre 18 cm e 105 cm.'); ?>")
                        $("#comp_pers").focus();
                        return;

                    }

                    if (largura < 5 || largura > 91) {

                        alert("<?php echo traduz('O diâmetro deve estar entre 5 cm e 91 cm.'); ?>")
                        $("#larg_pers").focus();
                        return;

                    }

                    altura = 0;

                    var total_tamanho_personalizado = comprimento + (2 * largura);

                    if(total_tamanho_personalizado < 28 || total_tamanho_personalizado > 200){

                        alert("A soma das medidas deve estar entre 28 cm e 200 cm.")
                        return;

                    }

                }

                caixa = comprimento+","+largura+","+altura;

            }
        }

        $.ajax({
            url:"../distrib/funcao_correio.php",
            type:"GET",
            dataType:"JSON",
            data:{
                uso:"admin",
                peso:peso,
                volume:volume,
                caixa:caixa,
                cep:cep,
                valor_nota:valor_nota,
                funcao: "calcPrecoPrazo",
                fabrica: <?=$login_fabrica?>
            },
            beforeSend: function(){
                $(".carregando-frete").html(" <img src='imagens/loading_img.gif' height='20px' style='margin-bottom: -5px;' /> Carregando, por favor aguarde...")
            }
        })
        .done(function(data){

            $(".carregando-frete").html("");

            var servicos    = "";
            var json        = "";
            var cont        = 0;
            var valor_real  = 0;

            $("#valor_frete_c_input").html("");

            $.each(data,function(key, value) {
                if (value.resultado == "false") {
                    alert(value.mensagem);
                } else {
                    if (value.valor == 0 || value.valor == 0.0 || value.valor == 0.00) {
                        value.valor = "<?php echo traduz('Não disponível esse serviço na localidade.'); ?>";
                    } else {
                        valor_real = parseFloat(value.valor) * parseInt(volume);
                        value.valor = "R$ "+valor_real.toFixed(2);
                        value.descricao = value.descricao.trim();
                    }
                    servicos += "<b>"+value.descricao+"</b>: "+value.valor+"<br />";
                    if (cont > 0) {
                        json += ",";
                    } else if (cont == 0) {
                        json += '[';
                    }
                    json += '{"descricao":"'+value.descricao+'","valor":'+valor_real.toFixed(2)+'}';
                    cont++;
                    valor_real = 0;
                }
            });
            json += ']';
            $("#valor_frete_c_input").append(servicos);
            $("#valor_frete_c").val(json);
        })
        .fail(function(){
            $(".carregando-frete").html("");
            alert("<?php echo traduz('Não foi possível calcular o frete'); ?>");
        });
    });

    $("#envio_posto").click(function(e){
        e.preventDefault();
        var volume          = $("#volume").val();
        var pedido          = <?=$pedido?>;
        var posto           = <?=$posto?>;
        var valor_frete_c   = $("#valor_frete_c").val();
        var valor_frete_t   = $("#valor_frete_t").val();

        if (valor_frete_c == "" && valor_frete_t == "") {
            alert("<?php echo traduz('Favor, preencher os campos e calcular o frete.'); ?>");
        }

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"envio_frete_posto",
                volume:volume,
                pedido:pedido,
                posto:posto,
                valor_frete_c:valor_frete_c,
                valor_frete_t:valor_frete_t,
            },
            beforeSend: function(){
                $(".carregando-frete").html(" <img src='imagens/loading_img.gif' height='20px' style='margin-bottom: -5px;' /> Carregando, por favor aguarde...")
            }
        })
        .done(function(data){
            $(".carregando-frete").html("");
            if (data.ok) {
                alert("<?php echo traduz('Frete enviado ao posto');?>");
                location.reload();
            }
        })
        .fail(function(){
            $(".carregando-frete").html("");
            alert("<?php echo traduz('Não foi possível gravar as informações');?>");
        })
    });
<?php
}
?>
<?php if (in_array($login_fabrica, [35])) { ?>
    $('[data-pedidoItem]').on('click', function(){
        Shadowbox.init();

        Shadowbox.open({
            content:    "shadowbox_pedido_faturamento_detalhado.php?pedido_item=" + $(this).data("pedidoitem"),
            player: "iframe",
            title:  "Itens Faturados",
            width:  800,
            height: 400
        });
    });
    $('[data-shadowPedido]').on('click', function(){
        Shadowbox.init();

        Shadowbox.open({
            content:    "shadowbox_pedido_faturamento_detalhado.php?pedido=" + $(this).data("shadowpedido"),
            player: "iframe",
            title:  "<?php echo traduz('Itens Faturados');?>",
            width:  800,
            height: 400
        });
    });
<?php } ?>
});

function linkLupeon(nf, cnpj){
    $.ajax({
        url:"pedido_admin_consulta.php",
        type:"POST",
        dataType:"JSON",
        data:{
            getLinkNF:true,
            nf:nf,
            cnpj:cnpj                
        },
        complete: function(data) {
            var data = $.parseJSON(data.responseText);                
            window.open('http://makita.lupeon.com.br/#/detalhes/'+data.NFeID, '_blank');
        }
    })
}


function CancelaPedidoItem(tipo,parametros,motivo) {
    var url = "<?=$PHP_SELF?>?"+parametros + "&motivo="+motivo;
    if (motivo.length == 0) alert ("<?php echo traduz('Não há motivo!'); ?>")
        else
    if (confirm('<?php echo traduz("Deseja cancelar este pedido?");?>' + '\n\n' + <?php echo traduz("Motivo:");?> +'\n'+motivo)) window.location=url
}

function cancelaTudo(pedido,obs){
    if(obs == ""){
        var motivo = document.getElementById('motivo_tudo').value;
        if(motivo == ""){
            alert("<?php echo traduz('Informe o motivo'); ?>");
            return false;
        }else{
            obs = motivo;
        }
    }
    if (confirm('<?php echo traduz("Deseja cancelar todos os ítens deste pedido?"); ?>')) {
        var url = "<?=$PHP_SELF?>?cancelaTudo=1&pedido="+pedido+"&motivo="+obs;
        window.location=url;
    }
}

function SelecionaTodos(field) {
    if($(".main").is(":checked")){
       $("input[type=checkbox].peca").each(function() {
           var input = $(this);
           var name = input.attr('name');
           var num = /\d+$/.exec(name)[0];
           if (!$("input[name=marcar_"+num+"]").is(':disabled')) {
               $("input[name=marcar_"+num+"]").attr('checked',true);
           }
       })
   }else{
       $("input[type=checkbox].peca").attr("checked",false);
   }
}
</script>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!--><?php

if (strlen($msg_erro) > 0) {
    echo "<center><div style='width:700px;' class='msg_erro'>$msg_erro</div></center>";
}

echo "<font color=blue>$msg_ok</font>";?>

<?php

if ($login_fabrica == 143 && $status_pedido == 19) {
?>
    <br />
    <div style="width: 700px; background-color: #F9A227; color: #FFFFFF; font-weight: bold; text-align: left; padding-top: 10px; padding-bottom: 10px; font-size: 14px; margin: 0 auto;" ><?php echo traduz("O pedido passará por atualização de valores referente a impostos e taxas, assim que atualizado enviaremos um comunicado para o Posto Autorizado verificar e aprovar o pedido! <br/> Somente será possível realizar alguma alteração no pedido após a atualização."); ?>
    </div>
    <br />
<?php
} else if ($login_fabrica == 143 && $status_pedido == 24) {
    $sql = "SELECT rejeitado_motivo FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
    $res = pg_query($con, $sql);

    $rejeitado_motivo = pg_fetch_result($res, 0, "rejeitado_motivo");
    ?>
    <br />
    <div style="width: 700px; background-color: #D34842; color: #FFFFFF; font-weight: bold; text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 14px; margin: 0 auto;" >
        <?=$rejeitado_motivo?>
        <br />
        <form method="post" >
            <input type="submit" name="reenviar_pedido" value="<?php echo traduz("Reenviar Pedido"); ?>"/>
        </form>
    </div>
    <br/>
<?php
}

if($login_fabrica == 164 AND strlen($rejeitado_motivo) > 0){
?>
	<br>
	<div style="width: 700px; background-color: #D34842; color: #FFFFFF; font-weight: bold; text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 14px; margin: 0 auto;" >
		<?=$rejeitado_motivo?>
	</div>
	<br>
<?php } ?>

<?php if (in_array($login_fabrica, [35])) { ?>
<style>
    .legenda {
        width: 700px;
        margin: auto;
        padding: 1px;
        text-align:center;

        -moz-border-radius:10px;
        -webkit-border-radius:10px;
         border-radius:10px;
    }
    .status_checkpoint{
        width:9px;
        height:15px;
        margin:2px 5px;
        padding:0 5px;
        border:1px solid #666;
    }
    .btn-shadowbox {
        background-color: #D9F2EF;
        border-radius: 5px;
        cursor: pointer;
    }
    .detalheFeturamento {
        color: blue;
        cursor: pointer;
        font-size: 20px;
        font-weight: bold;
    }
</style>
<div class="legenda">
<table border="0" cellspacing="0" cellpadding="0">
    <tbody>
    <tr height="18">
        <td width="18">
            <div class="status_checkpoint" style="background-color:#FAFF73">&nbsp;</div>
        </td>
        <td align="left">
            <font size="1">
                <b><?php echo traduz("Item faturado com peça diferente."); ?></b>
            </font>
        </td>
    </tr>
    </tbody>
</table>
</div>
<br>
<?php }
if ($telecontrol_distrib OR $login_fabrica == 174) {
?>
    <table width='700' cellpadding='0' align='center'>
    <tr>
        <td width="20%">
            <table  class='Tabela' width="100%">
                <tr>
                    <td class='inicio' style="text-align: center;"><?php echo traduz("CHAMADO"); ?></td>
                </tr>
                <tr>
                    <td class='conteudo' style="text-align: center;">
                        <?php echo ($hd_chamado > 0) ? "<a target='_blank' href='callcenter_interativo_new.php?callcenter={$hd_chamado}'>{$hd_chamado}</a>": 'Não tem' ;?>
                    </td>
                </tr>
            </table>
        </td>
        <td width="60%" >
        </td>
        <?php  if($login_privilegios == "*"){  ?>
        <td width="20%">
            <table class='Tabela' width="100%">
                <tr>
                    <td colspan="4" class='inicio' style="text-align: center;"><?php echo traduz("AÇÕES");?></td>
                </tr>
                <tr>
                    <?php
                        if($tipo_pedido_garantia != 't'){
                            if($venda_direta) { ?>
                            <td class='conteudo' style="text-align: center;" nowrap="">
                                <p id="venda_direta" data-pedido="<?=$pedido?>">
                                    Venda Direta
                                </p>
                                <img id="loading_venda_direta" src='imagens/loading_img.gif' height='20px' style='display: none; margin-bottom: -5px;'/>
                            </td>

                    <?php }elseif($status_pedido_embarcado != 10){ ?>
                    <td class='conteudo' style="text-align: center;" nowrap="">
                        <p id="conferencia_embarque" data-pedido="<?=$pedido?>">
                            Conferência de embarque
                        </p>
                        <img id="loading_venda_direta" src='imagens/loading_img.gif' height='20px' style='display: none; margin-bottom: -5px;'/>
                    </td>
                    <?php } } ?>


                    <?php if($login_fabrica == 123):?>
                        <td class='conteudo' style="text-align: center;">
                            <a id="embarque" onclick="gerar_embarque('<?=$os?>','<?=$login_fabrica?>','<?=$pedido?>','<?=$peca?>')" style="cursor: pointer">
                                <img border="0" src="imagens_admin/btn_embarcar_azul.png">
                            </a>
                            <img id="loading_embarque" src='imagens/loading_img.gif' height='20px' style='display: none; margin-bottom: -5px;'/>
                        </td>
                    <?php else: ?>
                        <td class='conteudo' style="text-align: center;">
                            <a href="gerar_embarque_os_press.php?os=<?=$os?>&fabrica=<?=$login_fabrica?>&pedidos=<?=$pedido?>&pecas=<?=$peca?>" target="_blank"">
                                <img border="0" src="imagens_admin/btn_embarcar_azul.png">
                            </a>
                        </td>
                    <?php endif;?>

                </tr>
            </table>
        </td>
        <?php } ?>
    </tr>
</table>
<?
}
?>
<?php if($login_fabrica == 123):?>
<script>
    function gerar_embarque(os = null, fabrica = null, pedido = null, peca = null){
        $.ajax({
            url: "gerar_embarque_os_press.php?os="+os+"fabrica="+fabrica+"pedido="+pedido+"peca="+peca+"",
            beforeSend: function(){
                        $("#loading_embarque").show();
                        $("#embarque").hide();
                }
        })
        .done(function(msg){
            alert('Embarque realizado com sucesso');
            $("#loading_embarque").hide();
            $("#embarque").show();
        })
        .fail(function(jqXHR, textStatus, msg){
            alert(msg);
            $("#loading_embarque").hide();
            $("#embarque").show();
        }); 
        console.log(os, fabrica, pedido, peca);
    }
</script>
<?php endif;?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td valign="top" align="center">
            <? if($login_fabrica <> 15) { # HD 117922?>
            <table width="700" border="0" cellspacing="5" cellpadding="0" class='texto_avulso' align='center'>
                <tr>
                    <td nowrap align='center'>
                    <b><?php echo traduz("Atenção:&nbsp;</b>Pedidos a prazo dependerão de análise do departamento de crédito."); ?>
                    </td>
                </tr>
                <?php
                    if ($login_fabrica == 160 or $replica_einhell) {
                ?>
                    <tr>
                        <td nowrap align='center'> <?php echo traduz("Todo Item Cancelado terá seu valor abatido no valor total do pedido."); ?>
                    </td>
                </tr>
                <?php } ?>
            </table>

            <table width="700" border="0" cellspacing="5" cellpadding="0" class='formulario' align='center'>
                <caption class='titulo_tabela'><?php echo traduz("Dados do Pedido"); ?></caption>
                <tr>
                    <td nowrap>
                        <b><?php echo traduz("Pedido"); ?></b>
                        <br>
                        <div id="numero_pedido">
                            <? if($login_fabrica == 1) {
                                echo $pedido_blackedecker;
                            }elseif($login_fabrica == 30){
                                if(strlen($seu_pedido) > 0 ){
                                    echo $seu_pedido;
                                }else{
                                    echo $pedido_aux;
                                }
                            }else{
                                echo $pedido_aux;
                            }  ?>
                        </div>
                    </td>
                    <?php if (in_array($login_fabrica, [175])) { ?>
                    <td nowrap>
                        <b><?php echo traduz("Pedido Ibramed"); ?></b>
                        <br>
                        <?= $seu_pedido ?>
                    </td>
                    <? } ?>
                    <?php
                    if (strlen($pedido_cliente) > 0) {?>
                        <td nowrap>
			    <?php if($login_fabrica <> 183) { ?>	
                            <b><?=traduz('Pedido Cliente');?></b>
			   <?php } else { ?>
                            <b><?=traduz('Pedido SAP');?></b>
                            <?php } ?>
			    <br>
                            <?=$pedido_cliente?>
                        </td><?php
                    }
			
		if($login_fabrica == 183){
			?>
				<td nowrap><b><?=traduz('Nota Fiscal Cliente');?></b> <br> <?=$notaFiscalCliente?> </td>
			<?php
		    }

                    if ((in_array($login_fabrica,array(6,46,95,101,108,111,115,116,117,120,201,121,122))  or $login_fabrica >= 123 ) && $tipo_pedido_garantia == 't') {?>

                        <td nowrap>
                            <b><?php echo traduz("OS"); ?></b>

                            <br>
                            <?=($login_fabrica == 178)? "$id_os_revenda": "$sua_os"?>
                        </td>
                    <? }

                    if(in_array($login_fabrica, array(151)) AND strlen($os)>0){
                        $sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra
                            WHERE os = {$os}";
                        $resAtendimento = pg_query($con,$sql);

                        if(pg_num_rows($resAtendimento) > 0){
                        ?>
                        <td nowrap>
                            <b><?php echo traduz("Atendimento"); ?></b>
                            <br>
                            <?=pg_fetch_result($resAtendimento, 0, "hd_chamado")?>
                        </td>
                        <?php
                        }
                    }

                    if ($login_fabrica == 1) {
?>
                    <td nowrap>
                        <b><?php echo traduz("Categoria Pedido");?></b>
                        <br>
                        <?=$categoria_pedido_descricao?>
                    </td>

<?php
                    }
?>

                    <td nowrap>
                        <b><?php echo traduz("Condição Pagamento");?></b>
                        <br>
                        <?=$condicao?>
                    </td>
                    <td>
                        <b><?php echo traduz("Tabela de Preços");?></b>
                        <br>
                        <?=$tabela_descricao?>
                    </td>
                    <td nowrap>
                        <b><?php echo traduz("Responsável");?></b>
                        <br>
                        <?=strtoupper($nome_completo)?>
                    </td>
                    <?php 
                    if ($login_fabrica == 186 && strlen($etiqueta_servico) > 0 ) {

                        $sqlEti = "SELECT etiqueta FROM tbl_etiqueta_servico WHERE etiqueta_servico = {$etiqueta_servico}";
                        $resEti = pg_query($con, $sqlEti);
                        if (pg_num_rows($resEti)> 0) {
                            $codRastreio = pg_fetch_result($resEti, 0, 'etiqueta');

                    ?>
                    <td nowrap>
                    <b>Código de Rastreio</b>
                    <br>
                    <?=strtoupper($codRastreio)?>
                    </td>
                    <?php } ?>
                    <?php } ?>

                    <?php if (in_array($login_fabrica,[101,183,151])) {?>
                            <td nowrap>
                            <b>Tipo Frete</b>
                            <br>
                            <?=strtoupper($tipo_frete)?>
                    </td>
                    <?php } ?>

                    <?php if($login_fabrica == 1 AND strlen($link_pedidos)> 0 ){?>
                        <td nowrap>
                        <b><?php echo traduz("Pedido(s) Desmembrado(s)");?></b>
                        <br>
                        <?=$link_pedidos ?>
                    </td>

                    <?php } ?>
		    <?php
                    // Apresenta informação "ATENDE PARCIAL"
                    if (($telecontrol_distrib && (strpos(strtoupper($condicao),"GARANTIA") === false)) || (in_array($login_fabrica, [104,147,160]))){
                        if ($controle_distrib_telecontrol) {
                            $sql_pedido_os = "SELECT os_item FROM tbl_os_item WHERE pedido = $pedido";
                            $res_pedido_os = pg_query($con, $sql_pedido_os);
                            if (pg_num_rows($res_pedido_os) == 0) {
                                echo "  <td nowrap>
                                            <b>Atende Parcial</b>
                                            <br>
                                            $atende_parcial
                                        </td>";
                            }
                        } else {
                            echo "<td nowrap>
                                <b>" . traduz("Atende Parcial") . "</b>
                                <br>
                                $atende_parcial
                            </td>";
                        }
                    }
                    ?>

                    <? //HD 11871 Paulo
                    if ($login_fabrica == 24 and strlen($login_alteracao) > 0) {?>
                        <td nowrap>
                            <b><?php echo traduz("Alterado Por"); ?></b>
                            <br>
                            <?echo strtoupper ($login_alteracao) ?>
                        </td>
                    <?} ?>
                    <? //HD 11871 Paulo
                    if ($login_fabrica == 42) {?>
                        <td nowrap>
                            <b><?php echo traduz("Data Exportação"); ?></b>
                            <br>
                            <? echo ($controle_exportacao) ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php
                if ($login_fabrica == 183){
            ?>
                <tr>
                    <td nowrap>
                        <b>CNPJ Transportadora</b>
                        <br>
                        <?=$transportadora_cnpj?>
                    </td>
                    <td nowrap>
                        <b>Nome Transportadora</b>
                        <br>
                        <?=$transportadora_nome?>
                    </td>
                </tr>
            <?php
                }


                if (in_array($login_fabrica, [42]) && is_array($obs)) {
                    $tipo_entrega = "";
                    $html_retirada = "";

                    switch ($obs['transporte']) {
                        case 'RETIRA':
                            $tipo_entrega = "Retirada";
                            $html_retirada = "
                                <td>
                                    <b>Nome do Responsável</b><br />
                                " . $obs["responsavel_retirada"]["nome"] . "
                                </td>
                                <td>
                                    <b>RG do Responsável</b><br />
                                " . $obs["responsavel_retirada"]["rg"] . "
                                </td>
                                <td>
                                    <b>WhatApp do Responsável</b><br />
                                " . $obs["responsavel_retirada"]["wapp"] . "
                                </td>
                            ";

                            break;
                        case 'SEDEX':
                            $tipo_entrega = "Sedex A Cobrar";
                            break;
                        default:
                            $tipo_entrega = "Padrão";
                    }
            ?>
                <tr>
                    <td>
                        <b>Tipo de Entrega</b><br />
                        <?= $tipo_entrega ?>
                    </td>
                    <?= $html_retirada ?>
                </tr>
            <?php
               }
            ?>
            </table><?php
            } ?>
            <?php
            if($login_fabrica == 87){
                ?>

                <table width="700" border="0" cellspacing="0" cellpadding="0" class='formulario'>
                    <tr>
                        <td nowrap align="center">
                            <strong><?php echo traduz("Admin"); ?></strong>
                            <br /><?=$nome_completo;?>
                        </td>
                        <td nowrap align="center">
                            <strong><?php echo traduz("Classe do Pedido"); ?></strong>
                            <br /><?=$classe_pedido;?>
                        </td>
                        <?php if(strlen($data_desejada) > 0){ ?>
                        <td nowrap align="center">
                            <strong><?php echo traduz("Data Desejada"); ?></strong>
                            <br /><?=$data_desejada;?>
                        </td>
                        <?php } ?>
                    </tr>
                </table>

                <?php
            }

            ?>
            <table width="700" border="0" cellspacing="5" cellpadding="0" class='formulario'>
                <tr>
<?php
                    if ($login_fabrica == 15) {# HD 117922
?>
                        <td nowrap>
                            <strong><?php echo traduz("Pedido"); ?></strong>
                            <br /><?=$pedido;?>
                        </td>
<?php
                    }
?>
                    <td nowrap>
                        <strong><?=($login_fabrica == 101 && $trocaConsumidor == 't') ? traduz("CPF") : traduz("Posto")?></strong>
                        <br />
                        <?=($login_fabrica == 101 && $trocaConsumidor == 't') ? $consumidorCpf : $codigo_posto?>
                    </td>
                    <td>
                        <strong><?=($login_fabrica == 101 && $trocaConsumidor == 't') ? traduz("Nome Consumidor") : traduz("Razão Social")?></strong>
                        <br/>
                        <?=($login_fabrica == 101 && $trocaConsumidor == 't') ? $consumidorNome : "<acronym title='$nome_posto'>".substr($nome_posto,0,20)."</acronym>"?>
                    </td><?php
                    if ($login_fabrica == 42) {?>
                        <td>
                            <strong>Atendido Por</strong>
                            <br/>
                            <acronym title="<?=$distrib_fantasia?>"><?=substr($distrib_fantasia,0,20)?></acronym>
                        </td><?php
                    } ?>

		    <?php if($login_fabrica == 191 AND strtolower($tabela_descricao) == "venda"){ ?>
			<td>
				<strong>Filial</strong><br/>
				<acronym title="<?=$nome_filial?>">
				<?php echo $cnpj_filial." - ".substr($nome_filial,0,20); ?></acronym>
			</td>			
		    <?php } ?>
                
		    <?php
                    if ($login_fabrica == 146) {
                        ?>
                        <td nowrap>
                            <strong><?php echo traduz("Marca"); ?></strong>
                            <br />
                            <?=$marca?>
                        </td>
                    <?php
                    }
                    ?>

                    <td nowrap>
                        <strong><?php echo traduz("Data"); ?></strong>
                        <br/>
                        <?=$data_pedido?>
                        &nbsp;
					</td>
                    <?php
                    /*HD - 4223006*/
                    if (in_array($login_fabrica, array(147, 160)) || $replica_einhell) {
                        $aux_sql = "
                            SELECT
                                TO_CHAR(data, 'DD/MM/YYYY HH24:MI:SS') AS data_aprovacao,
                                status,
                                observacao
                            FROM
                                tbl_pedido_status
                            WHERE
                                pedido = {$pedido}
                            ORDER BY pedido_status DESC
                            LIMIT 1
                        ";
                        $aux_res = pg_query($con, $aux_sql);
                        $aux_dat = pg_fetch_result($aux_res, 0, 'data_aprovacao');
                        $aux_sta = pg_fetch_result($aux_res, 0, 'status');
                        $aux_obs = pg_fetch_result($aux_res, 0, 'observacao');


                        if (($aux_sta == "1" || $aux_sta == "14")  && ($aux_obs == "Pedido Aprovado" || $aux_obs == "Pedido Recusado")) {
                            switch ($aux_obs) {
                                case 'Pedido Aprovado':
                                    $aux_lbl = "Liberado da Intervenção";
                                    break;
                                default:
                                    $aux_lbl = "Recusado da Intervenção";
                                    break;
                            }
                        ?>
                            <td nowrap>
                                <strong><?=$aux_lbl?></strong>
                                <br/>
                                <?=$aux_dat?>
                                &nbsp;
                            </td>
                    <? }
                    }

                    if ($login_fabrica == 139) {
                        $sql_et = " SELECT  entrega, 
                                            validade 
                                    FROM tbl_pedido 
                                    WHERE pedido = $pedido 
                                    AND fabrica = $login_fabrica
                                    AND (entrega NOTNULL OR validade NOTNULL)";
                        $res_et = pg_query($sql_et);
                        if (pg_num_rows($res_et) > 0) {
                        ?>
                            <td nowrap>
                                <strong>Validade</strong>
                                <br />
                                <?=pg_fetch_result($res_et, 0, 'validade')?>
                            </td>
                            <td nowrap>
                                <strong>Entrega</strong>
                                <br />
                                <?=pg_fetch_result($res_et, 0, 'entrega')?>
                            </td>
                        <?php
                        }
                    }

                    // HD 3500486
					if ($mostra_data_aprovacao):
						$aprovado_cliente = $aprovado_cliente ? : '&nbsp;';
					?>
					<td nowrap="">
						<strong><?php echo traduz("Aprovação Posto"); ?></strong>
						<br>
						<span><?=$aprovado_cliente?></span>
					</td>
					<?php endif; ?>
					<?php if (in_array($login_fabrica, array(1,87,158,178))) { # HD 411513
						if ($tipo_pedido_id == 203 && !in_array($login_fabrica, [1,158])) { ?>
							<td nowrap>
								<strong><?php echo traduz("Data de Validade"); ?></strong>
								<br />
								<?= $data_validade; ?>&nbsp;
							</td>
						<? } ?>
                        <td nowrap>
                            <strong><?= traduz("Status Pedido"); ?></strong>
                            <br /><?= $status ; ?>&nbsp;
                            <?php 
                            if(in_array($login_fabrica, array(1)) and $status_pedido == 14){
                                    $sql_status_pedido = "SELECT
                                                            tbl_pedido_status.status,
                                                            tbl_pedido_status.observacao,
                                                            tbl_status_pedido.descricao
                                                        FROM tbl_pedido_status
                                                        INNER JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido_status.status
                                                        WHERE
                                                            tbl_pedido_status.pedido = {$pedido}
                                                            and tbl_pedido_status.status = $status_pedido 
                                                        ORDER BY tbl_pedido_status.pedido_status DESC
                                                        LIMIT 1";
                                    $res_status_pedido = pg_query($con, $sql_status_pedido);
                                    if(pg_num_rows($res_status_pedido) > 0){
                                        $pedido_status_bd    = pg_fetch_result($res_status_pedido, 0, "status");
                                        $decrcicao_status_bd = pg_fetch_result($res_status_pedido, 0, "descricao");
                                        $obs_status_bd       = pg_fetch_result($res_status_pedido, 0, "observacao");

                                        echo "<br /> <br /> <strong>Obs</strong>: ".$obs_status_bd;
                                    }
                                    ?>
                                </td>
                                <?php
                                }
                            ?>


						</td>
<?php
                    }

                    if (in_array($login_fabrica,array(11,50,101,172))) {
?>
                        <td nowrap>
                            <strong><?php echo traduz("Tipo Pedido"); ?></strong>
                            <br /><?=$tipo_pedido;?>&nbsp;
                        </td>
<?php
                    }
?>

                    <td nowrap align='center'>
                        <strong><?php echo traduz("Finalizado"); ?></strong>
                        <br/>

                        <?php
                            if($login_fabrica == 1 ){
                                if($status_pedido == 18){
                                    echo "PENDENTE";
                                }else{
                                    echo $data_finalizado;
                                }
                            }else{
                                echo $data_finalizado;
                            }
                        ?>
                    </td>

                    <?php
                    /*if(in_array($login_fabrica, array(1))){
                    ?>
                    <td nowrap align='center' style="width: 200px;">
                        <?php

                        $sql_status_pedido = "SELECT
                                                tbl_pedido_status.status,
                                                tbl_pedido_status.observacao,
                                                tbl_status_pedido.descricao
                                            FROM tbl_pedido_status
                                            INNER JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido_status.status
                                            WHERE
                                                tbl_pedido_status.pedido = {$pedido}
                                            ORDER BY tbl_pedido_status.pedido_status DESC
                                            LIMIT 1";
                        $res_status_pedido = pg_query($con, $sql_status_pedido);

                        if(pg_num_rows($res_status_pedido) > 0){

                            $pedido_status_bd    = pg_fetch_result($res_status_pedido, 0, "status");
                            $decrcicao_status_bd = pg_fetch_result($res_status_pedido, 0, "descricao");
                            $obs_status_bd       = pg_fetch_result($res_status_pedido, 0, "observacao");

                            echo "<strong>" . traduz("Status Pedido") . "</strong> <br/>";
                            echo $decrcicao_status_bd;

                            if($pedido_status_bd == 14){

                                echo "<br /> <br /> <strong>" . traduz("Obs") . "</strong>: ".$obs_status_bd;

                            }

                        }

                        ?>
                    </td>
                    <?php
                    }*/
                    ?>

                </tr><?php
                if (in_array($login_fabrica, array(88,104,105,156))) {?>
                    <tr>
                        <td nowrap align='left' <?=$login_fabrica != 88 ? "colspan='4'" : ""?>>
                            <strong><?php echo traduz("Tipo Frete"); ?></strong>
                            <br>
                            <?
                                if($login_fabrica == 88){
                                    $tipo_frete = ($tipo_frete == "NOR") ? "NORMAL" : "URGENTE";
                                }
                                echo $tipo_frete
                            ?>
                            &nbsp;
                            </font>
                        </td>
                        <?
                                if(in_array($login_fabrica,array(88,156))){

                        ?>
                        <td nowrap align='center'>
                        <strong><?php echo traduz("Transportadora"); ?></strong>
                            <br>
                            <?echo $transportadora_nome;?>
                            &nbsp;
                            </font>
                        </td>
                        <td nowrap align='center' colspan='2'>
                        <strong><?php echo traduz("Valor Frete"); ?></strong>
                            <br>
                            <?echo number_format($valor_frete,2,',','');?>
                            &nbsp;
                            </font>
                        </td>
                        <?
                            }
                        ?>
                    </tr><?php
                }
                if($login_fabrica == 94){

				?>
					<tr>
                        <td nowrap>
                            <strong><?php echo traduz("Transportadora");?></strong>
                            <br>
                            <?if (!empty($transportadora_nome)) {
                                echo $transportadora_nome;
                            }else{
                                echo $desc_forma_envio;
                            }
                            ?>
                            &nbsp;
                            </font>
                        </td>
                        <td nowrap>
                        <strong><?php echo traduz("Valor Frete");?></strong>
                            <br>
                            <?echo number_format($valor_frete,2,',','');?>
                            &nbsp;
                            </font>
                        </td>
                        <td nowrap>
                        <strong><?php echo traduz("Valor Frete");?></strong>
                            <br>
                            <?echo number_format($valor_frete,2,',','');?>
                            &nbsp;
                            </font>
                        </td>
                        <td nowrap>
                            <strong><?php echo traduz("Responsável pelo faturamento"); ?></strong>
                            <br>
                            <?=$admin_alteracao?>
                            &nbsp;
                            </font>
                        </td>
					</tr>
				<? }
                if ($atendimento_sac && $tipo_pedido_id != 94) {
                    if ($login_fabrica == 1) { ?>
                        <tr>
                            <td nowrap align='left' colspan='4'>
                                <strong><?php echo traduz("CHAMADO SUPORTE/SAC"); ?></strong>
                                <br>
                                <?
                                    echo $atendimento_sac;
                                ?>
                                &nbsp;
                                </font>
                            </td>
                        </tr>
                    <? } else { ?>
                        <tr>
                            <td nowrap align='left' colspan='4'>
                                <strong><?php echo traduz("Observação"); ?></strong>
                                <br>
                                <?
                                    echo str_replace(array("|","\r"), "</br>",$atendimento_sac);
                                ?>
                                &nbsp;
                                </font>
                            </td>
                        </tr>
                    <? }
                }

                if ($login_fabrica == 6){

                    $sql_produto_pedido = "SELECT tbl_produto.referencia, tbl_produto.descricao from tbl_produto join tbl_pedido using (produto) where tbl_pedido.pedido=$pedido";
                    $res_produto_pedido = pg_query($con,$sql_produto_pedido);

                    if (pg_num_rows($res_produto_pedido)>0){

                    $prod_ref = pg_fetch_result($res_produto_pedido, 0, 0);
                    $prod_desc = pg_fetch_result($res_produto_pedido, 0, 1);
                ?>
                    <tr>
                        <td>
                            <strong><?php echo traduz("Produto Referência"); ?></strong>
                        </td>
                        <td colspan="3">
                            <strong><?php echo traduz("Produto Descrição"); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $prod_ref; ?>
                        </td>
                        <td colspan="3">
                            <?php echo $prod_desc; ?>
                        </td>
                    </tr>
                <?php

                    }

                }

                if ($telecontrol_distrib && !empty($nome_destinatario)) {

                    ?>
                    <tr>
                        <td>
                            <strong><?= traduz("Destinatário"); ?></strong><br />
                            <?php 
                            if($telecontrol_distrib and ($posto == 376542 OR $posto == 20682)){
                                if(!empty($os)){
                                    echo "<a href='os_press.php?os=$os' target='_blank'>$nome_destinatario </a>";
                                }elseif(!empty($pedido_hd_chamado_extra)){
                                    echo "<a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$nome_destinatario </a>";
                                } else{
                                    echo $nome_destinatario;
                                }
                            }else{
                                echo $nome_destinatario;
                            }

                            ?>
                        </td>
                    </tr>
                <?php
                }
                ?>



                <?php

                if ($login_fabrica == 1) {

                    $sql2 = "SELECT distinct referencia_fabrica,
                            nota_fiscal_locador,
                            data_nf_locador,
                            serie_locador
                            FROM tbl_pedido_item
                            JOIN tbl_produto ON tbl_produto.produto = tbl_pedido_item.produto_locador
                            WHERE pedido=$pedido ";

                    $res2 = pg_query($con, $sql2);

                    if (pg_num_rows ($res2) > 0 and strlen(trim(pg_fetch_result($res2, 0, 'nota_fiscal_locador'))) > 0) {

?>

                        <?php
                        if ($atendimento_sac) {
                            ?>
                            <table class="tabela" style="width: 700px;margin-top: 5px;">
                                <tr class="titulo_coluna" style="font-size: 15px;">
                                    <th><?php echo traduz("Observações do Pedido"); ?></th>
                                </tr>
                                <tr style="font-size: 14px;text-align: left;height: 20px;">
                                    <td><?= utf8_decode($atendimento_sac) ?></td>
                                </tr>
                            </table>
                            <br />
                            <?php
                        }
                        ?>
                        <table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0'>
                            <tr bgcolor='#C0C0C0'>
                                <td align='center' colspan='4' >
                                    <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Projeto Locador - Nota Fiscal de compra do Locador"); ?></b>
                                    </font>
                                </td>
                            </tr>
                            <tr style='font-size:11px;font-style:Geneva,Arial,Helvetica, san-serif;font-weight:bold'>
                                <td nowrap align='center'>
                                    <?php echo traduz("Nota fiscal"); ?>
                                </td>
                                <td nowrap align='center'>
                                     <?php echo traduz("Numero de série"); ?>
                                </td>
                                <td nowrap align='center'>
                                     <?php echo traduz("Modelo do produto"); ?>
                                </td>
                                <td nowrap align='center'>
                                     <?php echo traduz("Data nf locador"); ?>
                                </td>
                            </tr>
                            <?php

                            $data_nf_locador = pg_fetch_result($res2, $p, 'data_nf_locador');
                            $data_nf_locador = explode("-",$data_nf_locador);
                            $data_nf_locador = $data_nf_locador[2] . "/" . $data_nf_locador[1] . "/" . $data_nf_locador[0];

                            for($p = 0;$p < pg_num_rows($res2);$p++){
                                echo "<tr style='font-size:11px;font-style:Geneva,Arial,Helvetica, san-serif;'>";
                                echo "<td nowrap align='center'>".pg_fetch_result($res2, $p, 'nota_fiscal_locador')."</td>";
                                echo "<td nowrap align='center'>".pg_fetch_result($res2, $p, 'serie_locador')."</td>";
                                echo "<td nowrap align='center'>".pg_fetch_result($res2, $p, 'referencia_fabrica')."</td>";
                                echo "<td nowrap align='center'>". $data_nf_locador ."</td>";
                                echo "</tr>";
                            } ?>
                        </table>
                        <br>
                    <? }
                } ?>
                <tr>
                <? if ($login_fabrica == 24 && qual_tipo_posto($posto) != 696) {?>
                    <td nowrap align='center'>
                        <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Recebido Posto");?></b>
                        <br>
                        <?echo $data_recebido?>
                        &nbsp;
                        </font>
                    </td>
                <?php } ?>
                <?if ($login_fabrica == 45) {   // HD 27232?>
                    <td nowrap align='center'>
                        <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Status Fabricante");?></b>
                        <br>
                        <?echo $status_fabricante?>
                        &nbsp;
                        </font>
                    </td>
                <?php } ?>


                </tr>
            </table><?php

            if ($login_fabrica == 7) {

                $pedido_os_descricao = ($pedido_os =='t') ? traduz("Ordem Serviço") : traduz("Compra Manual");
                $origem_descricao    = ($origem_cliente == 't') ? traduz("Cliente") : traduz("PTA");?>

                <table width="700" border="0" cellspacing="5" cellpadding="0">
                    <tr>
                        <td nowrap align='center'>
                            <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Tipo do Pedido"); ?></b>
                            <br>
                            <?echo $tipo_pedido?>
                            </font>
                        </td>
                        <td nowrap align='center'>
                            <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Origem (OS/Compra)"); ?></b>
                            <br>
                            <?echo $pedido_os_descricao?>
                            </font>
                        </td>
                        <td nowrap align='center'>
                            <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Solicitante (PTA/Cliente)"); ?></b>
                            <br>
                            <?echo $origem_descricao?>
                            &nbsp;
                            </font>
                        </td>
                        <td nowrap align='center'>
                            <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Tipo Frete"); ?></b>
                            <br>
                            <?echo $tipo_frete?>
                            &nbsp;
                            </font>
                        </td>
                        <td nowrap align='center'>
                            <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><?php echo traduz("Valor Frete"); ?></b>
                            <br>
                            <?echo $valor_frete?>
                            &nbsp;
                            </font>
                        </td>

                    </tr>
                </table><?php

            } ?>

            <table width="700" border="0" cellspacing="1" cellpadding="2" align='center' class='tabela'><?php

                if (in_array($login_fabrica,array(6,11,30,43,46,74,81,86,94,99,114,115,116,117,120,201,121,122,172))  or $login_fabrica >= 123 ) {// HD 112647

                    if (isset($telaPedido0315)) {
                        $column_total_item = ", SUM(tbl_pedido_item.total_item) AS total_item";
                    }

                    if($telecontrol_distrib){
                        $condEstoque = "LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca AND tbl_posto_estoque.posto = 4311 ";
                        $column_estoque_distrib = ", tbl_posto_estoque.qtde AS estoque_distrib ";
                        $group_estoque_distrib = ", tbl_posto_estoque.qtde";
                    }

                    if ($login_fabrica == 175){
                        $valoresAdicionaisItens = "tbl_pedido_item.valores_adicionais::text AS valoresAdicionaisItens, ";
                        $valoresGroup = ",valoresAdicionaisItens";
                    }

                    $sql = "SELECT  '' as pedido_item,
							tbl_pedido_item.peca,
							tbl_pedido.desconto,
                            tbl_pedido_item.preco,
                            ".((!isset($novaTelaOs)) ? "SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) AS total," : "SUM((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) AS total," )."
                            tbl_peca.referencia            ,
                            tbl_peca.referencia_fabrica AS peca_referencia_fabrica,
                            tbl_peca.descricao             ,
                            tbl_peca.ipi                   ,
                            tbl_peca.parametros_adicionais AS peca_pa,
                            $valoresAdicionaisItens
                            tbl_pedido.valores_adicionais  ,
                            SUM(tbl_pedido_item.qtde) AS qtde,
                            SUM(tbl_pedido_item.qtde_faturada) AS qtde_faturada,
                            SUM(tbl_pedido_item.qtde_cancelada) AS qtde_cancelada,
                            sum(tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_faturada_distribuidor,
                            tbl_pedido_item.obs,
                            tbl_pedido_item.peca_alternativa,
                            tbl_pedido.previsao_entrega
                        {$column_total_item}
                        {$column_estoque_distrib}
                        FROM  tbl_pedido
                        JOIN  tbl_pedido_item USING (pedido)
                        JOIN  tbl_peca        USING (peca)
                        $condEstoque
                        WHERE tbl_pedido_item.pedido = $pedido
                        AND   tbl_pedido.fabrica     = $login_fabrica
                        GROUP BY tbl_pedido_item.peca,
                                tbl_pedido_item.preco,
                                tbl_peca.referencia            ,
                                tbl_peca.referencia_fabrica,
								tbl_peca.parametros_adicionais,
                                tbl_peca.descricao             ,
                                tbl_peca.ipi                   ,
                                tbl_pedido_item.obs            ,
                                tbl_pedido_item.peca_alternativa            ,
                                tbl_pedido.valores_adicionais  ,
								tbl_pedido.previsao_entrega,
								tbl_pedido.desconto
				$group_estoque_distrib
                $valoresGroup
				ORDER BY tbl_peca.descricao;";
                } else {

                    if($login_fabrica == 40){

                        $campo_os = ", tbl_os_produto.os ";
                        $cond_os = "
                                    LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                    LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                ";

                    }

                    $sql = "SELECT  tbl_pedido_item.pedido_item,
                            tbl_pedido_item.peca,
							tbl_pedido.desconto,
                            tbl_pedido_item.preco,
                            tbl_pedido_item.preco_base,
                            CASE
                                WHEN tbl_pedido.fabrica = 87 AND tbl_pedido_item.preco_base IS NULL THEN
                                    tbl_pedido_item.preco
                            END AS preco_base2,
                            CASE
                                WHEN tbl_pedido.fabrica = 14 THEN
                                    rpad ((tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::TEXT,7,'0')::float
                                WHEN tbl_pedido.fabrica IN(24, 88) THEN
                                    tbl_pedido_item.qtde *  tbl_pedido_item.preco
                                ELSE
                                    tbl_pedido_item.qtde *  tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))
                            END AS total,
                            tbl_peca.referencia_fabrica AS peca_referencia_fabrica,
							tbl_peca.referencia            ,
                            tbl_peca.descricao             ,
                            tbl_peca.ipi                   ,
                            tbl_peca.localizacao            ,
                            tbl_peca.peso                   ,
                            JSON_FIELD('status', tbl_peca.parametros_adicionais)          AS disponibilidade,
                            JSON_FIELD('previsaoEntrega', tbl_peca.parametros_adicionais) AS previsao_entrega_peca,
                            tbl_pedido_item.qtde           ,
                            tbl_pedido_item.qtde_faturada  ,
                            tbl_pedido_item.qtde_cancelada ,
                            tbl_pedido_item.qtde_faturada_distribuidor,
                            tbl_pedido_item.peca_alternativa ,
                            tbl_pedido_item.obs ,
                            tbl_pedido_item.estoque,
                            tbl_pedido_item.total_item,
                            tbl_condicao.descricao as condicao
                            {$campo_os}
                        FROM  tbl_pedido
                        JOIN  tbl_pedido_item USING (pedido)
                        JOIN  tbl_peca        USING (peca)
                        {$cond_os}
                        LEFT JOIN  tbl_condicao    ON tbl_pedido_item.condicao = tbl_condicao.condicao
                        WHERE tbl_pedido_item.pedido = $pedido
                        AND   tbl_pedido.fabrica     = $login_fabrica
                        ORDER BY tbl_peca.descricao,tbl_peca.peca ;";

                }
                // echo nl2br($sql);exit;
                $res = pg_query ($con,$sql);
                $total_pedido = 0 ;

                $lista_os = array();
                $ExibeCabecalho = 0;
 
                if ($login_fabrica <> 15) {
?>
                    <thead>
                        <tr height="20" class='titulo_coluna'>
<?php
                    if ($login_fabrica == 1) {
?>
                            <td><?php echo traduz("SEQ"); ?></td><?php
                    }
                    if ($login_fabrica == 147) {
?>
                            <td><?php echo traduz("Peça Referência"); ?> </td>
                            <td><?php echo traduz("Peça Descrição"); ?></td>
<?php
                    } else {
?>
                            <td <?=(isset($novaTelaOs) ? "colspan='2'" : "")?>><?php echo traduz("Componente"); ?></td>
<?php
                    }
?>

                    <td><?= traduz("Qtde");?></td>
                    <td><?= traduz("Qtde");?><br><?= traduz("Cancelada");?></td>
                    <td><?= traduz("Qtde");?><br><?= traduz("Faturada");?></td>
<?php
                    if (in_array($login_fabrica, array(6,10,11,24,35,46,51,52,81,87,94,95,98,99,101,106,108,111,114,115,116,117,120,201,121,122))  or $login_fabrica >= 123 ) { //HD 404932 - Adicionar Suggar
?>
                        <td><?php echo traduz("Pendência");?><br><?php echo traduz("do Pedido");?></td>
<?php
                    }
		    if($telecontrol_distrib && visualiza_estoque_distrib($login_admin)){
?>
			<?php echo traduz("<td>Estoque<br>Distrib</td>"); ?>
		        <td><?=traduz("Estoque")?><br><?=traduz("Total")?></td>
<?php
		    }


                    if(in_array($login_fabrica, array(152, 180, 181, 182))) { ?>

                        <td><?php echo traduz("Previsão de<br/>Faturamento"); ?></td>
                    
                    <?php  }

                    if ($login_fabrica == 101) {
?>
                        <td align='center'><?php echo traduz("Localização Estoque"); ?></td>
<?php
                    }

                    if (!isset($telaPedido0315) && !in_array($login_fabrica, array(42,120,201))) {
?>
                            <td align='center'><?php echo traduz("IPI");?></td>
<?php
                    }


                    // HD 3765012
                    if ($login_fabrica == 42) {
                        echo "<td nowrap>Disp. Status</td>";
                    }

                    if ($login_fabrica == 87) {
?>
                            <td><?php echo traduz("Preço Inicial"); ?></td>
                            <td><?php echo traduz("Preço Final"); ?></td>
                            <td><?php echo traduz("Preço Final Total s/ S.T e IPI"); ?></td>
                            <td><?php echo traduz("Preço Final Total"); ?></td>
<?php
                    } else {
                        if ($login_fabrica == 168) {
?>
                            <td>Peso Unitário</td>
                            <td>Peso Total</td>
<?php
                        }

                        if ($login_fabrica == 143) { ?>
                            <td>Disponibilidade</td>
                        <?php }

                        if ($login_fabrica == 1) {
?>
                            <td><?= traduz("Preço");?><br/><?= traduz("Unitário");?></td>
<?php 
                        } else {
?>
                            <td><?php echo traduz("Preço Unitário");?></td>
<?php                            
                        }

                        if ($login_fabrica == 175 ){  ?>
                            <td><?php echo traduz("Aliq. IPI");?></td>
                            <td><?php echo traduz("Base. IPI");?></td>
                            <td><?php echo traduz("IPI");?></td>
                            <td><?php echo traduz("Aliq. ICMS");?></td>
                            <td><?php echo traduz("Base ICMS");?></td>
                            <td><?php echo traduz("ICMS");?></td>
                            <td><?php echo traduz("Total impostos");?></td>
                        <?php } ?>
<?php
                        if(in_array($login_fabrica,[42,140])){
?>
                            <td><?php echo traduz("Preço Total");?></td>                          
<?php                   }

                        if (!in_array($login_fabrica,array(1, 42,138,140)) && !isset($novaTelaOs)) {
?>
                            <td><?php echo traduz("Preço");?></td>
<?php
                        }
                    }

                    if ($login_fabrica == 1 || $login_fabrica == 104) {
?>
                        <td><?php echo traduz("Total<br/>s/ IPI"); ?></td>
<?php
                    }

                    if (in_array($login_fabrica,array(147,149,153,156,157,168)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
?>
                            <td><?php echo traduz("IPI %");?></td>
<?php
                    }

                    if (!in_array($login_fabrica, array(42, 87,120,201)) && !isset($telaPedido0315)) {
                        if ($login_fabrica == 1) {
?>
                            <td><?= traduz("Total<br/>c/ IPI"); ?></td>
<?php                             
                        } else {
?>
                            <td>Total c/ IPI</td>
<?php
                        }
                    } else if (isset($telaPedido0315)) {
                        if (in_array($login_fabrica,array(139,147,156,165))) {
?>
                            <td><?php echo traduz("Total c/ IPI"); ?></td>
<?php
                            if ($login_fabrica == 165) {
?>
                            <td colspan="2"><?php echo traduz("Total"); ?></td>
<?php
                            }
                        } else {
                            $colspanTotal = (in_array($login_fabrica, [144])) ? 1 : 2;

?>                          
                            <td colspan='<?= $colspanTotal ?>'>Total</td>

<?php
                        }
                    } else if($login_fabrica == 120 or $login_fabrica == 201) {
?>
                            <td><?php echo traduz("Total"); ?></td>
<?php
                    }

                    if ($condicao <> 'GARANTIA' && $condicao <> 'Garantia' && (in_array($login_fabrica,array(3,10,51))) && $distribuidor == 4311 && in_array($login_admin, $lista_de_admin_altera_pedido )) {
?>
                            <td><?php echo traduz("Ação"); ?></td>
<?php
                    }

                    if ($login_fabrica == 1) {
?>
                        <td><?= traduz("Previsão"); ?></td>
<?php 
                    }

                    if (in_array($login_fabrica, [175])) { ?>
                        <td><?php echo traduz("Total com Impostos"); ?></td>
                    <?php }

                    if ($login_fabrica == 87) {
?>
                            <td><?php echo traduz("Condição"); ?></td>
<?php
                        if ($tipo_pedido_id == 203) {
?>
                            <td><?php echo traduz("Disponibilidade"); ?></td>
<?php
                        }
                    }
                    if ($login_fabrica == 147) { ?>
                            <td><?php echo traduz("Desconto (%)"); ?></td>
                            <td><?php echo traduz("Total com Desconto"); ?></td>
                <?php } ?>
                        </tr>
                    </thead>
<?php

                }

                $total_geral_ipi = 0;
				$total_tela = 0;

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $seq = $i + 1;
                    $total_sem_ipi = number_format(0,2,",",".");
                    $cor = ($i % 2 == 0) ? "#F7F5F0": "#F1F4FA";

                    $pedido_item = pg_fetch_result($res, $i, 'pedido_item');
                    $peca        = pg_fetch_result($res, $i, 'peca');
                    $peca_alternativa        = pg_fetch_result($res, $i, 'peca_alternativa');

                    /*if($login_fabrica == 15) {
                        $consumidor_revenda = pg_fetch_result ($res,$i,consumidor_revenda);
                    }*/
                    if ($login_fabrica == 171) {
                        $peca_descricao  = pg_fetch_result($res, $i, 'referencia') . " / " .  pg_fetch_result($res, $i, 'peca_referencia_fabrica') . " - " . pg_fetch_result ($res, $i, 'descricao');
                    } else {
                        $peca_descricao  = pg_fetch_result($res, $i, 'referencia') . " - " . pg_fetch_result ($res, $i, 'descricao');
                    }

                    $xpeca_referencia = pg_fetch_result($res, $i, 'referencia');
                    $xpeca_descricao  = pg_fetch_result ($res, $i, 'descricao');
                    $qtde             = pg_fetch_result($res, $i, 'qtde');
                    $ipi              = trim(pg_fetch_result($res, $i, 'ipi'));

                    if ($login_fabrica == 24 && qual_tipo_posto($posto) == 696) {
                        $ipi = 0;
                    }

                    $desconto         = trim(pg_fetch_result($res, $i, 'desconto'));
                    $preco_final      = trim(pg_fetch_result($res, $i, 'preco'));
                    $previsao_entrega = pg_fetch_result($res, $i, 'previsao_entrega');
                    $disp_entrega     = pg_fetch_result($res, $i, 'previsao_entrega_peca');

                    if($telecontrol_distrib and $login_fabrica != 174){
                        $sql_total_alternativa = "SELECT sum(tbl_posto_estoque.qtde) as total_alternativa
                                                    from tbl_peca_alternativa
                                                    join tbl_posto_estoque on tbl_posto_estoque.peca = tbl_peca_alternativa.peca_para
                                                    where peca_de = $peca and tbl_peca_alternativa.fabrica = $login_fabrica ";
                        $res_total_alternativa = pg_query($con, $sql_total_alternativa);
                        if(pg_num_rows($res_total_alternativa)>0){
                            $total_alternativa = pg_fetch_result($res_total_alternativa, 0, "total_alternativa");
                        }
                    }

                    if ($login_fabrica == 1) {
                        $xparametros_adicionais = pg_fetch_result($res,$i,'peca_pa');
                        $xparametros_adicionais = json_decode($xparametros_adicionais,true);
                        $xqtde_faturada         = pg_result($res,$i,'qtde_faturada');
                        $xestoque               = $xparametros_adicionais['estoque'];
                        $xprevisao              = $xparametros_adicionais['previsao'];
                        $xestoque               = ucfirst($xparametros_adicionais["estoque"]);
                        $xprevisao              = mostra_data($xparametros_adicionais["previsao"]);
                        $xestoque               = strtoupper($xestoque);
                        if($xestoque == "DISPONIVEL" || $xestoque == "DISPONÍVEL"){
                            if($xqtde_faturada == $qtde){
                                $xprevisao = "Faturada";
                            }else{
                                $xprevisao = $xestoque;
                            }
                        }
                    }

                    $total_item = pg_fetch_result($res, $i, 'total_item');

                    if ($login_fabrica == 175){
                        $valoresItens = pg_fetch_result($res, $i, 'valoresAdicionaisItens');

                        $valoresItens = str_replace('"{', '{', $valoresItens);
                        $valoresItens = str_replace('}"', '}', $valoresItens);
                        $valoresItens = str_replace('\\', '', $valoresItens);
                        $valoresItens = json_decode($valoresItens, true);

                        if (!empty($valoresItens['aliq_ipi'])){
                            $aliq_ipi_item = $valoresItens['aliq_ipi'];
                        }else{
                            $aliq_ipi_item = 0;
                        }
                        if (!empty($valoresItens['base_ipi'])){
                            $base_ipi_item = $valoresItens['base_ipi'];
                        }else{
                            $base_ipi_item = 0;
                        }
                        if (!empty($valoresItens['ipi'])){
                            $ipi_item = $valoresItens['ipi'];
                        }else{
                            $ipi_item = 0;
                        }
                        if (!empty($valoresItens['aliq_icms'])){
                            $aliq_icms_item = $valoresItens['aliq_icms'];
                        }else{
                            $aliq_icms_item = 0;
                        }
                        if (!empty($valoresItens['base_icms'])){
                            $base_icms_item = $valoresItens['base_icms'];
                        }else{
                            $base_icms_item = 0;
                        }
                        if (!empty($valoresItens['icms'])){
                            $icms_item = $valoresItens['icms'];
                        }else{
                            $icms_item = 0;
                        }
                        if (!empty($valoresItens['total_impostos'])){
                            $total_impostositem = $valoresItens['total_impostos'];
                        }else{
                            $total_impostositem = 0;
                        }

                    }

                    // HD 3765012
                    $disponibilidade = '';
                    if (isFabrica(42) and ($qtde_pecas - $qtde_faturada - $qtde_cancelada) > 0) {
                        $peca_pa = new Json(trim(pg_fetch_result($res, $i, 'peca_pa'), false)); // false: sem throw Exception
                        $disponibilidade  = $peca_pa->status;
                        $previsao_chegada = is_date($peca_pa->previsao_chegada, '', 'EUR') ? : '';

                        if ($disponibilidade == 'I') {
                            $disponibilidade = "<button type='button' class='abre_hd_pedido_peca' ".
                                "data-peca='$peca' data-prevista='$previsao_chegada' data-ref='$referencia' data-desc='$descricao' ".
                                "data-exportado='$pedido_data' data-pedido='$pedido' ".
                                "title='" . traduz('produto.indisponivel.em.estoque.no.momento.clique.para.ver.a.previsao.de.chegada') . "'".
                                ">" . traduz("Indisponível") . "</button>";
                        } elseif ($disponibilidade == 'D') {
                            $disponibilidade = '<span title="' . traduz('produto.disponivel.em.estoque.aguardando.transferencia.entre.as.filiais.para.entrega') . '">' . traduz("Disponível") . '</span>';
                        }
                    } else {
                        if ($login_fabrica == 143) {
                            require_once "../classes/Posvenda/Fabricas/_143/PedidoWackerNeuson.php";

                            $disponibilidade = new PedidoWackerNeuson($xpeca_referencia);

                            $disponibilidade = $disponibilidade->verificaEstoquePeca();
                            $disponibilidade = $disponibilidade->retornosEstoque->temEst == 'N' ? 'Não' : 'Sim';
                        }
                    }

                    if ($login_fabrica <> 43 AND $login_fabrica <> 99) {
                        $preco_base    = trim(pg_fetch_result($res, $i, 'preco_base'));
                        $condicaoJacto = pg_fetch_result($res, $i, 'condicao');
                    }

                    $obs_pedido_item            = (mb_check_encoding(pg_fetch_result($res, $i, 'obs'),"UTF-8")) ? utf8_decode(trim(pg_fetch_result($res, $i, 'obs'))) : trim(pg_fetch_result($res, $i, 'obs'));
                    $qtde_faturada              = pg_fetch_result($res, $i, 'qtde_faturada');
                    $qtde_cancelada             = pg_fetch_result($res, $i, 'qtde_cancelada');

                    if ($login_fabrica == 160 or $replica_einhell) {
                        $xqtde = $qtde - $qtde_cancelada;
                    }

                    $qtde_faturada_distribuidor = pg_fetch_result($res, $i, 'qtde_faturada_distribuidor');

                    if($telecontrol_distrib && visualiza_estoque_distrib($login_admin)){
                        $estoque_distrib	= pg_fetch_result($res, $i, 'estoque_distrib');

                        if (in_array($login_fabrica, [11,172])) {
                            $pecas_lenoxx = [];
                            $sql_pecas = "  SELECT peca
                                            FROM tbl_peca
                                            WHERE referencia = (
                                                                SELECT referencia
                                                                FROM tbl_peca
                                                                WHERE peca = $peca
                                                                AND fabrica = $login_fabrica
                                                               )
                                            AND fabrica IN (11, 172)";
                            $res_pecas = pg_query($con, $sql_pecas);

                            for ($q=0; $q < pg_num_rows($res_pecas); $q++) {
                                $pecas_lenoxx[] = pg_fetch_result($res_pecas, $q, 'peca');
                            }

                            $pecas_lenoxx = implode(",", $pecas_lenoxx);

                            $sqlEstoque = " SELECT  SUM(tbl_posto_estoque.qtde) AS estoque_distrib
                                            FROM    tbl_posto_estoque
                                            JOIN    tbl_peca        USING(peca)
                                            WHERE   tbl_posto_estoque.posto = 4311
                                            AND     tbl_peca.peca           IN ($pecas_lenoxx)";
                            $resEstoque = pg_query($con, $sqlEstoque);
                            if (pg_num_rows($resEstoque) > 0) {
                                $estoque_distrib = pg_fetch_result($resEstoque, 0, 'estoque_distrib');
                            }
                        }
                    }

                    if ($login_fabrica == 87) {

                        if ($preco_base == "") {
                            $preco_base = trim(pg_fetch_result($res, $i, 'preco_base2'));
                            $preco_final = $preco_base;
                        }

                    }

                    if ($login_fabrica == 168) {
                        $peso = pg_fetch_result($res,$i,peso);
                        $peso_total = $peso * $qtde;
                    }

                    $pedido_valores_adicionais = pg_fetch_result($res,$i,'valores_adicionais');

                    if($login_fabrica == 40){
                        $os = pg_fetch_result($res, $i, "os");
                    }

					switch (pg_fetch_result($res, $i, 'estoque')) {
						case 't':
							$estoque = 'SIM';
							break;
						case 'f':
							$estoque = 'NÃO';
							break;
						default:
							$estoque = '';
							break;
					}

                    if($login_fabrica == 101){
                        $localizacao_estoque        = pg_fetch_result($res, $i, 'localizacao');
                    }

                    if ($distribuidor == 4311 and $qtde_faturada_distribuidor > 0 ) $qtde_faturada = $qtde_faturada_distribuidor;


                    $total_qtde     += $qtde;
                    $total_faturada += $qtde_faturada;

                    //adicionei fabrica 93 para pegar preco do tbl_pedido_item a pedido do Tulio 31/03/2011 waldir
                    if (!in_array($login_fabrica, array(14,24,88,138,142,143)) && !isset($novaTelaOs)) {

                        if (!in_array($login_fabrica,array(1,93,7,35,10,3,94,74,104,125,81,114,128,123,122,136,160)) and !$replica_einhell) {

                        $sqlt  = "
                                SELECT  tbl_pedido_item.preco  AS preco,
                                        ''                     AS ipi
                                FROM    tbl_pedido_item
                                WHERE   tbl_pedido_item.pedido = $pedido
                                AND     tbl_pedido_item.peca   = $peca
                        ";
                        } else {

                            $sqlt  = "SELECT tbl_pedido_item.preco  AS preco,
                                            '' AS sem_ipi,
                                            ''                     AS ipi
                                    FROM    tbl_pedido_item
                                    WHERE   tbl_pedido_item.pedido = $pedido
                                    AND     tbl_pedido_item.peca   = $peca ";

                            if ($login_fabrica == '1') {
                                $sqlOrigem = "SELECT origem FROM tbl_peca WHERE peca = $peca";
                                $qryOrigem = pg_query($con, $sqlOrigem);

                                if (in_array(pg_fetch_result($qryOrigem, 0, 'origem'), array('FAB/SA', 'IMP/SA'))) {
                                    $sqlt = "
                                        SELECT (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100))) AS preco,
                                               (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6) AS sem_ipi,
                                               '' AS ipi
                                        FROM tbl_pedido
                                        JOIN tbl_pedido_item USING (pedido)
                                        JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                                        JOIN tbl_tabela_item ON tbl_tabela_item.peca= tbl_peca.peca
                                        AND tbl_tabela_item.tabela = tbl_pedido.tabela
                                        WHERE tbl_pedido_item.pedido = $pedido
                                        AND tbl_pedido_item.peca = $peca
                                        ";
                                }
                            }

                            if ($login_fabrica == 7) {

                                $sqlt  = "SELECT tbl_pedido_item.preco AS preco,
                                                tbl_pedido_item.ipi   AS ipi
                                        FROM    tbl_pedido_item
                                        WHERE   tbl_pedido_item.pedido = $pedido
                                        AND     tbl_pedido_item.peca   = $peca
                                        AND     tbl_pedido_item.pedido_item = $pedido_item ";
                            }
                        }

                        $resT = pg_query ($con,$sqlt);

                        if (@pg_num_rows ($resT) > 0) {

                            // unitario sem ipi
                            $preco_unit = pg_fetch_result ($resT, 0, 'preco');
                            if ($login_fabrica == 85){
                                $preco_unit = round($preco_unit,2);
                            }

                            if ($login_fabrica == 7) {

                                if (strlen($pedido) > 0 AND strlen($peca) > 0) {

                                    $sqlx = "SELECT tbl_os_item.preco
                                             FROM tbl_os
                                             JOIN tbl_os_produto USING(os)
                                             JOIN tbl_os_item    USING(os_produto)
                                             WHERE tbl_os.fabrica = $login_fabrica
                                             AND (tbl_os_item.pedido = $pedido OR tbl_os_item.pedido_cliente = $pedido)
                                             AND tbl_os_item.peca   = $peca";

                                    $resx = pg_query ($con,$sqlx);

                                    if (@pg_num_rows($resx) > 0) {
                                        $preco_unit    = pg_fetch_result($resx,0,preco);
                                    }

                                }

                            }

                            $preco_ipi = pg_fetch_result ($resT,0,ipi);
                            if (strlen($preco_ipi)>0){
                                $ipi = $preco_ipi;
                            }
                            // total s/ ipi
                            $preco_sem_ipi = ($login_fabrica == 11 ) ?$preco_unit *($qtde - $qtde_cancelada) : $preco_unit * ($qtde);

                            if ($login_fabrica == '1') {
                                $sem_ipi = pg_fetch_result($resT, 0, 'sem_ipi');
                                if (!empty($sem_ipi)) {
                                    $preco_sem_ipi = pg_fetch_result($resT, 0, 'sem_ipi');
                                }
                            }

                            // total pecas c/ ipi
                            if (in_array($login_fabrica, array(7,42,120,201))) {
                                $total = $preco_sem_ipi;
                            } else {
                                $total = $preco_sem_ipi + ($preco_sem_ipi * $ipi / 100);
                            }

                            $total_sem_ipi = $preco_sem_ipi;

                            // total acumulado do pedido
                            if ($login_fabrica <> 30) {
                                $total_pedido += $total;
                            }

                            $total_pedido_sem_ipi += $total_sem_ipi;


                        } else {

                            $preco      = "***";
                            $total      = "***";
                            $preco_unit = "***";

                        }

                    } else {

                        // unitario sem ipi
                        $preco_unit = trim(pg_fetch_result($res, $i, 'preco'));
                        $total      = trim(pg_fetch_result($res, $i, 'total'));

                        // total s/ ipi
                        $preco_sem_ipi = $preco_unit * $qtde;

                        if ($login_fabrica == 160 or $replica_einhell) {
                            $preco_sem_ipi = $preco_unit * $xqtde;
                        }


                        // total pecas c/ ipi
                        $total_sem_ipi = $preco_sem_ipi;

                        if (in_array($login_fabrica, [175])) {
                            $total_comimpostos = $total_sem_ipi + $total_impostositem;
                        }

                        $sql = "SELECT  case when $login_fabrica = 14 then
					    rpad (sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::TEXT,7,'0')::float
					when $login_fabrica = 88 then
						rpad (sum(tbl_pedido_item.qtde * tbl_pedido_item.preco)::TEXT,7,'0')::float
						when $login_fabrica = 160 then
							round(sum((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco)::numeric,2)
                            when $login_fabrica = 24 and tbl_pedido.tipo_pedido = 426 then
                                sum(tbl_pedido_item.qtde * tbl_pedido_item.preco)
                                    else
                                        ".((isset($novaTelaOs)) ? "sum((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))" : "sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))")."
                                    end as total_pedido
                                FROM  tbl_pedido
                                JOIN  tbl_pedido_item USING (pedido)
                                JOIN  tbl_peca        USING (peca)
                                WHERE tbl_pedido_item.pedido = $pedido
                                GROUP BY tbl_pedido.pedido";

                        $resz = pg_query ($con,$sql);

                        if (pg_num_rows($resz) > 0 AND $login_fabrica <> 104) $total_pedido  = trim(pg_fetch_result($resz, 0, 'total_pedido'));

                        $total_pedido_sem_ipi += $total_sem_ipi;

                        $preco_unit    = str_replace(".",",",$preco_unit);
                        $total         = str_replace(".",",",$total);

                    }


                    if ($login_fabrica == 15) {

                        if ($peca_anterior == $peca) {

                            $lista_os .= (!empty($lista_os)) ? ",".$os : $os;
                            $condicao1 = " AND tbl_os.os not in ($lista_os) ";

                        } else {
                            $condicao1 = " AND 1 = 1 ";
                            $lista_os  = "";
                        }

                        $sql_os = "SELECT   distinct
                                            tbl_os.os,
                                            tbl_os.sua_os,
                                            tbl_produto.descricao as descricao_produto,
                                            tbl_os.revenda_nome,
                                            tbl_os.consumidor_revenda,
                                            tbl_os.serie,
                                            tbl_produto.produto
                                    FROM    tbl_pedido
                                    JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                                    LEFT JOIN tbl_os_item     ON  tbl_os_item.peca          = tbl_pedido_item.peca AND tbl_os_item.pedido_item         = tbl_pedido_item.pedido_item
                                    LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto  = tbl_os_item.os_produto
                                    LEFT JOIN tbl_os          ON tbl_os.os                  = tbl_os_produto.os
                                    LEFT JOIN tbl_produto     ON tbl_produto.produto        = tbl_os.produto
                                    WHERE   tbl_pedido_item.pedido = $pedido
                                    AND     tbl_pedido_item.pedido_item  = $pedido_item
                                    AND   tbl_pedido.fabrica     = $login_fabrica
                                    ORDER BY tbl_os.sua_os;";

                        $res_os = pg_query($con, $sql_os);

                        if (@pg_num_rows($res_os) > 0) {
                            $os                 = pg_fetch_result($res_os, 0, 'os');
                            $sua_os             = pg_fetch_result($res_os, 0, 'sua_os');
                            $descricao_produto  = pg_fetch_result($res_os, 0, 'descricao_produto');
                            $revenda_nome       = pg_fetch_result($res_os, 0, 'revenda_nome');
                            $consumidor_revenda = pg_fetch_result($res_os, 0, 'consumidor_revenda');
                            $serie              = pg_fetch_result($res_os, 0, 'serie');
                        }

                    }


                    if ($login_fabrica == 15) {
                        $cabecalho_r="<thead><tr height='20' bgcolor='#C0C0C0'><td>". traduz("OS") . "</td><td>". traduz("Revenda") . "</td><td>" . traduz("Número de Série") . "</td><td>" . traduz("Produto") . "</td><td>" . traduz("Componente") . "</td><td align='center'>" . traduz("Qtde") . "</td><td align='center' style='font-size:9px'>" . traduz("Qtde") . "<br>" . traduz("Cancelada") . "</td><td align='center' style='font-size:9px'>" . traduz("Qtde") . "<br>" . traduz("Faturada") . "</td><td align='center'>" . traduz("Preço") . "</td></tr></thead>";
                        $cabecalho_c="<thead><tr height='20' bgcolor='#C0C0C0'><td>" . traduz("OS") . "</td><td>" . traduz("Componente") . "</td><td align='center'>" . traduz("Qtde") . "</td><td align='center' style='font-size:9px'>" . traduz("Qtde"); "<br>" . traduz("Cancelada") . "</td><td align='center' style='font-size:9px'>" . traduz("Qtde") . "<br>" . traduz("Faturada") . "</td><td align='center'>" . traduz("Preço") . "</td></tr></thead>";
                        if($consumidor_revenda == "R"){
                            $lista_r .= "<tr bgcolor='$cor' ><td align='center'><a href='os_press.php?os=$os'>$sua_os</a><td align='left'>$revenda_nome</td><td align='left'>$serie</td><td align='left'>$descricao_produto</td><td align='left'> $peca_descricao </td><td align='right'> $qtde </td><td align='right'><font color='#FF0000'> $qtde_cancelada </font></td><td align='right'> $qtde_faturada </td><td align='right'> $preco_unit </td></tr>";
                        }else{
                            $lista_c .= "<tr bgcolor='$cor' ><td align='center'><a href='os_press.php?os=$os'>$sua_os</a><td align='left'> $peca_descricao </td><td align='right'> $qtde </td><td align='right'><font color='#FF0000'> $qtde_cancelada </font></td><td align='right'> $qtde_faturada </td><td align='right'> $preco_unit</td></tr>";
                        }

                        $peca_anterior = $peca;

                    } else {

                        if($login_fabrica == 40){

                            /* Total de peças */
                            $condOs = "";
                            if (strlen($os) > 0) {
                               $condOs = " os = {$os} AND ";
                            }
                            if (strlen($os) > 0) {
                                $sql_qtde = "SELECT SUM(qtde) AS qtde FROM tbl_os_item WHERE pedido_item = {$pedido_item} AND peca = {$peca} AND os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
                            } else {
                                $sql_qtde = "SELECT SUM(qtde) AS qtde FROM tbl_pedido_item WHERE pedido_item = {$pedido_item} AND peca = {$peca}";
                            }
                            $res_qtde = pg_query($sql_qtde);

                            if(pg_num_rows($res_qtde) > 0){
                                $qtde_total = (strlen(pg_fetch_result($res_qtde, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde, 0, "qtde") : 0;
                            }else{
                                $qtde_total = 0;
                            }

                            /* Faturadas */
                            $sql_qtde_faturada = "SELECT SUM(qtde) AS qtde FROM tbl_faturamento_item WHERE {$condOs} pedido_item = {$pedido_item} AND peca = {$peca}";
                            $res_qtde_faturada = pg_query($con, $sql_qtde_faturada);

                            if(pg_num_rows($res_qtde_faturada) > 0){
                                $qtde_faturada = (strlen(pg_fetch_result($res_qtde_faturada, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde_faturada, 0, "qtde") : 0;
                            }else{
                                $qtde_faturada = 0;
                            }

                            /* Canceladas */
                            $sql_qtde_cancelada = "SELECT SUM(qtde) AS qtde FROM tbl_pedido_cancelado WHERE {$condOs} fabrica = {$login_fabrica} AND pedido_item = {$pedido_item} AND peca = {$peca}";
                            $res_qtde_cancelada = pg_query($con, $sql_qtde_cancelada);

                            if(pg_num_rows($res_qtde_cancelada) > 0){
                                $qtde_cancelada = (strlen(pg_fetch_result($res_qtde_cancelada, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde_cancelada, 0, "qtde") : 0;
                            }else{
                                $qtde_cancelada = 0;
                            }

                            $pendente = $qtde_total;

                            if($qtde_faturada > 0){
                                $pendente = $qtde_total - $qtde_faturada;
                            }else{
                                $nota_fiscal = "";
                            }

                            if($qtde_cancelada > 0){
                                $pendente = $qtde_total - $qtde_cancelada;
                            }

                            if($qtde_faturada > 0 && $qtde_cancelada > 0){
                                $pendente = $qtde_total - ($qtde_faturada + $qtde_cancelada);
                            }

                            $qtde = $qtde_total;

                        }

                        if (in_array($login_fabrica, [35])) {
                            $sqlValida = "  SELECT peca
                                            FROM tbl_faturamento_item
                                            WHERE pedido_item = {$pedido_item}
                                                AND peca != {$peca}";
                            $resValida = pg_query($con, $sqlValida);

                            if (pg_num_rows($resValida) > 0) {
                                $cor = "#FAFF73";
                            }
                        }

?>

                        <tr bgcolor="<? echo $cor ?>" >
<?php
                        if ($login_fabrica == 1) {
?>
                            <td align='center'><? echo $seq ?></td>
<?php
                        }
                        if ($login_fabrica == 147) {
?>
                            <td align='left'><?=$xpeca_referencia?></td>
                            <td align='left'><?=$xpeca_descricao?></td>
<?php
                        } else {
?>
                            <td align='left' <?=(isset($novaTelaOs) ? "colspan='2'" : "")?> ><? echo $peca_descricao ?></td>
<?php
                        }
?>
                            <td align='center'><? echo $qtde ?></td>
                            <td align='center'><font color='#FF0000'><? echo $qtde_cancelada ?></font></td>
                            <?php
                            if (in_array($login_fabrica, [35])) {
                                 echo "<td align='center'><button class='btn-shadowbox' value='{$qtde_faturada}' data-pedidoItem='$pedido_item'>{$qtde_faturada}</button</td>";
                            } else {
                                echo "<td align='center'>{$qtde_faturada}</td>";
                            }

                            if (in_array($login_fabrica, array(6,10,11,24,35,46,51,52,81,87,94,95,98,99,101,106,108,111,114,115,116,117,120,201,121,122))  or $login_fabrica >= 123 ) { //14-04-2011 - HD 404932 - Adicionar Suggar
                                $qtde_pendente = $qtde - $qtde_faturada - $qtde_cancelada;

                                echo "<td class='table_line1_pendencia' align='center'>";
                                    if ($qtde_pendente <= 0 OR strlen($qtde_pendente) == 0) echo "0";
                                    else echo $qtde_pendente;
                                echo "</td>";
                           }

			    if($telecontrol_distrib && visualiza_estoque_distrib($login_admin)){
    				echo "<td align='center'>{$estoque_distrib}</td>";
                    echo "<td align='center'>".($total_alternativa + $estoque_distrib)."</td>";
			    }

                            if(in_array($login_fabrica, array(152, 180, 181, 182))) {
                                $data_previsao_faturamento = pg_fetch_result($res, $i, "previsao_entrega");
                                list($ano, $mes, $dia) = explode("-", $data_previsao_faturamento);
                                $data_previsao_faturamento = $dia."/".$mes."/".$ano;
                            ?>
                            <td style="text-align:center;"><?=$data_previsao_faturamento?></td>
                            <?php
                            }
                            if($login_fabrica == 101){
                                echo "<td align='center'>$localizacao_estoque</td>";
                            }

                    if (!isset($telaPedido0315) && !in_array($login_fabrica, array(42, 120,201))) {
                    ?>
                        <td align='center'><? echo $ipi."%"; ?></td><?
                    }

                    // HD 3765012
                    if (isFabrica(42)) {
                        $disponibilidade = is_date($disp_entrega, '', 'EUR') ? : "Disponível";
                        echo "<td align='center' bgcolor='$cor'>$disponibilidade</td>";
                    }


                    if ($login_fabrica == 168) {
?>
                                <td style="text-align:right;"><?=number_format($peso,2,',','.')?></td>
                                <td style="text-align:right;"><?=number_format($peso_total,2,',','.')?></td>
<?php
                    }

                    if ($login_fabrica == 143) {?>
                        <td align='right'><?php echo $disponibilidade ?></td><?php
                    }

                    if ($login_fabrica != 87) {
                        echo "<td align='right'>";
                        $preco = $preco_unit = str_replace(",",".",$preco_unit);
                        echo number_format($preco_unit,2,",",".");
                        echo "</td>";
                    }

					if ($login_fabrica == 175){
						echo "<td align='right'>".number_format($aliq_ipi_item,2,",",".")."</td>";
						echo "<td align='right'>".number_format($base_ipi_item,2,",",".")."</td>";
						echo "<td align='right'>".number_format($ipi_item,2,",",".")."</td>";
						echo "<td align='right'>".number_format($aliq_icms_item,2,",",".")."</td>";
						echo "<td align='right'>".number_format($base_icms_item,2,",",".")."</td>";
						echo "<td align='right'>".number_format($icms_item,2,",",".")."</td>";
						echo "<td align='right'>".number_format($total_impostositem,2,",",".")."</td>";

						$total_icms_item += $icms_item;
						$total_ipi_item += $ipi_item;

					}

					if(in_array($login_fabrica,[35, 138,158,175,169,170,183,195])) {
						$colsxx = " colspan='2' ";
					}
					if (!in_array($login_fabrica,array(1,138,140)) && !isset($novaTelaOs)) {
?>
                                <td align='right'><?php

                        $preco_unit = str_replace(",",".",$preco_unit);

                        if ($login_fabrica == 87) {

                            $preco_base = number_format($preco_base,2,",",".");
                            echo $preco_base;

                        } else {
                            if (!in_array($login_fabrica, array(1,7,24,30,46,50,88,104,120,201,122)) and $login_fabrica < 123) {
                                $preco = $preco_unit * ($qtde - $qtde_cancelada);
                            } else {
                                $preco = $preco_unit;

                                if($login_fabrica == 24){
                                    $preco = $preco * (1 + ($ipi/100));
                                }
                            }

                            if (in_array($login_fabrica, array(81,122,123,125,114,128))){
                                echo number_format($preco,4,",",".");
                            }else{
                                echo number_format($preco,2,",",".");
                            }
                        } ?>
                                </td><?php
                            }
                            if ($login_fabrica == 87) {
                                $total_pedido += $preco_final;

                                $sqlTotItem = "SELECT total_item, total_sem_st FROM tbl_pedido_item_jacto WHERE pedido_item = $pedido_item";
                                $qryTotItem = pg_query($con, $sqlTotItem);
                                $total_sem_st = 0;

                                if (pg_num_rows($qryTotItem) == 1) {
                                    if (empty($total_item)) {
                                        $total_item = pg_fetch_result($qryTotItem, 0, 'total_item');
                                    }

                                    $total_sem_st = pg_fetch_result($qryTotItem, 0, 'total_sem_st');
                                }

                                $total_pedido_jacto+= $total_item;
                                $total_pedido_jacto_sem_st += $total_sem_st;
                                ?>
                                <td align='right'><? echo number_format($preco_final,2,",",".");?></td>
                                <td align='right'><? echo number_format($total_sem_st, 2, ",", ".");?></td>
                                <td align='right'><? echo number_format($total_item, 2, ",", ".");?></td><?php
                            }

                            if ($login_fabrica == 1 || $login_fabrica == 104) {?>
                                <td align='right'><?php echo number_format($total_sem_ipi,2,",",".") ?></td><?php
                            }

                             if ($login_fabrica == 140) {?>
                                <td align='right'><?php echo number_format($preco_unit*$qtde,2,",",".") ?></td><?php
                            }

                            if(in_array($login_fabrica, array(147,149,153,156,157,168)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){ /* HD-3844543 26/10/2017 */
                                ?>
                                <td align='right'><?=$ipi?>%</td>
                                <?php
                            }

                            if(!in_array($login_fabrica, [42,87])) { ?>
                            <td align='right' <?=$colsxx?>>
                            <?php if ($login_fabrica != 87 && !isset($telaPedido0315)) {
                                if (in_array($login_fabrica,array(24,30,50,88,104,126)) or $login_fabrica >= 138) {
                                    if(($login_fabrica == 88 OR $login_fabrica == 126 OR $login_fabrica >= 138) && !in_array($login_fabrica,array(140,146,147,156,157,165))){
										if($login_fabrica == 88){
                                            $total = $preco * ($qtde - $qtde_cancelada);
                                        }else{
                                            $total = $preco * ($qtde - $qtde_cancelada);
                                        }
                                        $subtotal += $total;
                                    }else if($login_fabrica == 24){
                                        $total = $preco * $qtde;
                                    }else if(in_array($login_fabrica,array(50,104,140))){
                                        $total = ( $preco + (($preco * $ipi) / 100) ) * ($qtde - $qtde_cancelada);
                                        $total_soma += $total;
                                    }else if($login_fabrica == 30){
                                        $total = ( $preco * (1 + ($ipi/100)) * $qtde);

                                        if ($login_fabrica == 30) {
                                            $total_pedido += $total;
                                        }
                                    }
                                }

                                if(in_array($login_fabrica, array(149,153,156,157)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                                    $total += $qtde * ($preco * ($ipi / 100));
                                }

                            } else if (isset($telaPedido0315)) {
                                if(in_array($login_fabrica,array(139,140,147,156,157,165,168,191))){
                                    $total = ( $preco * (1 + ($ipi/100)) * $qtde);
                                    $subtotal += $total;

                                    if ($login_fabrica == 160 or $replica_einhell) {
                                        $totalSipi = $preco * $qtde;
                                        $subSipi += $totalSipi;
                                    }
                                } elseif ($login_fabrica == 138) {
                                    $total = $preco * ($qtde - $qtde_cancelada);
                                    $subtotal += $total;
                                }else{
                                    $total = $total_sem_ipi;
                                    $subtotal += $total;
                                }
                            }

							if ($login_fabrica == 35) {
								$total = $preco * $qtde_pendente;
								$total_tela += $total;
							}

                            if($login_fabrica == 147) $total_geral_ipi += $total;
                            echo number_format($total,2,',','.');
?>
                        </td>
<?php
                            if ($login_fabrica == 1) {
?>
                                <td style='text-align: center;' ><?= $xprevisao ?></td>
<?php
                            }
                        }
                        if (in_array($login_fabrica,array(165))) {
?>
                         <td style='text-align: right;' ><?=number_format($total_item, 2, ",", ".")?></td>
<?php
                        }
                        if ($login_fabrica == 87) {
?>
                            <td><?php echo $condicaoJacto; ?></td>
                            <?php if ($tipo_pedido_id == 203): ?>
                                <td align="center"><?php echo $estoque; ?></td>
                            <?php endif ?>
<?php
                        }
                        if ($login_fabrica == 147) {
                            $valorDesconto = $total - ($total * ($pedido_desconto / 100));
                            $valorTotalDesconto += $valorDesconto;
?>
                            <td style='text-align: right;'><?=$pedido_desconto?>%</td>
                            <td style='text-align: right;'><?=number_format($valorDesconto,2,',','.')?></td>
<?php
                        }

                        if (in_array($login_fabrica, [175])) {

                            if ($qtde_cancelada > 0) {
                                $total_pecas                = $qtde - $qtde_cancelada;
                                $total_geral_impostos_item  += $total_pecas * $total_impostositem;
                            } else {
                                $total_geral_impostos_item  += $total_impostositem;
                            }

                            ?>
                            <td style="text-align:center;"><?= $total_comimpostos ?></td>
                        <?php } ?>
                        </tr>
<?php
                    }

                    if (strlen($previsao_entrega) > 0) {
                        echo "<tr bgcolor='$cor'>";
                        echo "<td colspan='9'>";
                        echo "<font face='Verdana' size='1' color='#CC0066'>";
                        echo traduz("Esta peça estará disponível em") . " " . $previsao_entrega;
                        echo "<br>";
                        echo traduz("Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor.");
                        echo "</font>";
                        echo "</td>";
                        echo "</tr>";
                    }

                    //HD  8412
                    /**
                     * @since HD 749085 - Black
                     */
                    $mostra_obs = array(1, 3, 35, 138, 160, 167, 178, 203);

                    if ((in_array($login_fabrica, $mostra_obs) or $telecontrol_distrib) and strlen($obs_pedido_item) > 0) {

                        echo "<tr bgcolor='$cor'>";
                            echo "<td colspan='100%' align='left'>";
                                echo "<font face='Verdana' size='1' color='#000099'> <img src='imagens/setinha_linha.gif' border='0' />  ";
                                echo "OBS: ".$obs_pedido_item;
                                echo "</font>";
                            echo "</td>";
                        echo "</tr>";

                    }

                }

                $colspan = ($login_fabrica == 11 || $login_fabrica == 138) ? 6 : 7;
                if(in_array($login_fabrica, array(147,149,153,156,157,160,161,165)) || ($usa_calculo_ipi) || $replica_einhell){
                    $colspan =  (regiao_suframa() == true && in_array($login_fabrica, array(161))) ? 7 : 8;
                }
                $colspan = (in_array($login_fabrica, array(142))) ? 7 : $colspan;
                $colspan = (in_array($login_fabrica, array(138,143,158,169,170))) ? 8 : $colspan;
                $colspan = (in_array($login_fabrica, array(165))) ? 9 : $colspan;
                $colspan = ((in_array($login_fabrica, array(160)) or $replica_einhell)) ? 10 : $colspan;

                $usa_desconto = \Posvenda\Regras::get("usa_desconto", "pedido_venda", $login_fabrica);

                //if($login_fabrica == 15) $coluna = "7"; //14-04-2011 - Inclui a 15 na condição acima
                if($usa_desconto==true){
                    $mostra_desconto = false;

                    if ($login_fabrica == 138 && $tipo_pedido == "VENDA") {
                        $mostra_desconto = true;
                    } else if(in_array($login_fabrica, [167, 203]) && $tipo_pedido == "VENDA"){
                        $mostra_desconto = true;
                    } else if ($login_fabrica == 143 && $tipo_pedido == "USO/CONSUMO") {
                        $mostra_desconto = true;
                    } else if (!in_array($login_fabrica, array(138, 143, 156, 167, 203))) {
                        $mostra_desconto = true;
                    }

                    if ($login_fabrica == 187 && $tipo_pedido == "Garantia") {
                        if (visualiza_estoque_distrib($login_admin)) {
                            $colspan = 9;
                        } else {
                            $colspan = 7;
                        }
                    }

                    if($mostra_desconto == true && $login_fabrica != 147) {
    		        ?>
                        <tr class='titulo_coluna'>
                            <td colspan='<?echo $colspan;?>' align='center'>
                                <b><?php echo traduz("Desconto sobre pedido de venda"); ?></b>
                            </td>
                            <td colspan='1' align='right'>
                                <b><? echo $pedido_desconto; ?> %</b>
                            </td>
                       </tr>
                    <?
                    }
		        }
                if ($login_fabrica == 161) {?>
                    <tr class='titulo_coluna'>
                        <td colspan='<?echo $colspan;?>' align='center'>
                            <b><?php echo traduz("Desconto do Fabricante"); ?></b>
                        </td>
                        <td colspan='1' align='right'>
                            <b><?php echo empty($desconto_fabricante) ? '0': $desconto_fabricante; ?> %</b>
                        </td>
                   </tr>
                   <tr class='titulo_coluna'>
                        <td colspan='<?echo $colspan;?>' align='center'>
                            <b><?php echo traduz("Adicional do Fabricante"); ?></b>
                        </td>
                        <td colspan='1' align='right'>
                            <b><?php echo empty($adicional_fabricante) ? '0,00' : $adicional_fabricante; ?> </b>
                        </td>
                   </tr>
                <?php
                } else {
                    $usa_desconto_fabricante = \Posvenda\Regras::get("usa_desconto_fabricante", "pedido_venda", $login_fabrica);

                    if($usa_desconto_fabricante == true and $tipo_pedido == 'FATURADO') {
                    ?>
                        <tr class='titulo_coluna'>
                            <td colspan='<?echo $colspan;?>' align='center'>
                                <b><?php echo traduz("Desconto do Fabricante"); ?></b>
                            </td>
                            <td colspan='1' align='right'>
                                <b><? echo $desconto_fabricante; ?> %</b>
                            </td>
                       </tr>
                    <?
                    }

                    $usa_adicional_fabricante = \Posvenda\Regras::get("usa_adicional_fabricante", "pedido_venda", $login_fabrica);

                    if($usa_adicional_fabricante == true and $tipo_pedido == 'FATURADO') {
                    ?>
                        <tr class='titulo_coluna'>
                            <td colspan='<?echo $colspan;?>' align='center'>
                                <b><?php echo traduz("Adicional do Fabricante"); ?></b>
                            </td>
                            <td colspan='1' align='right'>
                                <b><? echo $adicional_fabricante; ?> </b>
                            </td>
                       </tr>
                    <?
                    }

                }
                $usa_frete = \Posvenda\Regras::get("usa_frete", "pedido_venda", $login_fabrica);
                $usa_frete_estado = \Posvenda\Regras::get("usa_frete_estado", "pedido_venda", $login_fabrica);

                //if($login_fabrica == 15) $coluna = "7"; //14-04-2011 - Inclui a 15 na condição acima
                if(!empty($usa_frete) || !empty($usa_frete_estado) OR in_array($login_fabrica, array(163))) {
                    ?>
                      <tr class='titulo_coluna'>
                        <td colspan='<?echo $colspan;?>' align='center'>
                            <b><?php echo traduz("Frete"); ?></b>
                        </td>
                        <td colspan='1' align='right'>
                            <b><? echo number_format($valor_frete, 2, ",", "."); ?> </b>
                        </td>
                       </tr>
                    <?
                }

                if(in_array($login_fabrica, [167, 203]) && $tipo_pedido == "VENDA"){
                    $pedido_valores_adicionais = json_decode($pedido_valores_adicionais,true);

                    echo "<tr class='menu_top'>
                        <td colspan='8'>" . traduz("Desconto Fabricante") . "</td>
                        <td align='right' >{$pedido_valores_adicionais['valor_desconto_fabricante']}%</td>
                    </tr>";
                    echo "<tr class='menu_top'>
                        <td colspan='8'>" . traduz("Adicional Fabricante") . "</td>
                        <td align='right' >{$pedido_valores_adicionais['adicional_fabricante']}</td>
                    </tr>";
                }

                if ($login_fabrica == 15) {
                    echo $cabecalho_c.$lista_c."</table><br><table width='700' border='0' cellspacing='1' cellpadding='2' align='center' class='Tabela'>".$cabecalho_r.$lista_r;
                } ?>

                <?php

                if($login_fabrica == 168){

                    if(strlen($valor_frete) > 0){

                        $sql_t = "SELECT valores_adicionais FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$login_fabrica}";
                        $res_t = pg_query($con, $sql_t);

                        $transportadora_frete = pg_fetch_result($res_t, 0, "valores_adicionais");

                        $valor_frete_desc = number_format($valor_frete, 2, ",", ".");

                        echo "
                        <tr>
                            <td colspan='10' class='menu_top'>" . traduz("FRETE") . "({$transportadora_frete}) </td>
                            <td class='menu_top'>{$valor_frete_desc}</td>
                        </tr>";

                    }

                }

                if ($login_fabrica == 175){ ?>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("VALOR DO FRETE"); ?></td>
                        <td><?=(!empty($pedidoValorFrete)) ? number_format($pedidoValorFrete,2,",",".") : "0,00"?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("VALOR DA DESPESA"); ?></td>
                        <td><?= (!empty($pedidoValorDespesa)) ? number_format($pedidoValorDespesa,2,",",".") : "0,00"?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("VALOR DO SEGURO"); ?></td>
                        <td><?= (!empty($pedidoValorSeguro)) ? number_format($pedidoValorSeguro,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("VALOR DO ICMS RETIDO"); ?></td>
                        <td><?= (!empty($total_icms_item)) ? number_format($total_icms_item,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("VALOR TOTAL DO IPI"); ?></td>
                        <td><?= (!empty($total_ipi_item)) ? number_format($total_ipi_item,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("VALOR TOTAL DOS IMPOSTOS"); ?></td>
                        <td><?= (!empty($total_geral_impostos_item)) ? number_format($total_geral_impostos_item,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="16"><?php echo traduz("TOTAL DESCONTO"); ?></td>
                        <td><?= (!empty($pedidoValorDesconto)) ? number_format($pedidoValorDesconto,2,",",".") : "0,00" ?></td>
                    </tr>

                <?php } ?>
                <tr class='titulo_coluna'>

                    <?php
                    if ($login_fabrica == 1 || $login_fabrica == 104) {?>
                        <td colspan='7'><b><?php echo traduz("TOTAL"); ?></b></td>
                        <td align='right' nowrap><b><? echo number_format($total_pedido_sem_ipi,2,",","."); ?></b></td>
                        <td align='right' nowrap><b><? echo number_format($total_pedido,2,",","."); ?></b></td>
                        <td align='right' nowrap></td><?php
                    } else {

                        if (!in_array($login_fabrica, array(11,138,142,143,172)) && !isset($telaPedido0315)) {

                            $coluna = (in_array($login_fabrica, array(6,10,24,46,51,52,81,94,95,98,99,101,106,108,111,114,115,116,117,120,201,121,122))  or ($login_fabrica >= 123 && $login_fabrica != 172) ) ? '8' : '7'; //HD 404932 - Adicionar Suggar

            			    if($telecontrol_distrib){
                                if(visualiza_estoque_distrib($login_admin)){
                                    $coluna = 10;
                                }else{
                                    $coluna = 9;
                                }
                            } ;

                            $coluna = (in_array($login_fabrica, array(15,87, 101))) ? '9' : $coluna; //HD 404932 - Adicionar Suggar

                            //if($login_fabrica == 15) $coluna = "7"; //14-04-2011 - Inclui a 15 na condição acima
                            if(in_array($login_fabrica,array(88,94))){ ?>
                            <td colspan='<?echo $coluna;?>' align='center'>
                                <b><?php echo traduz("Valor total do saldo com IPI, em R$, com desconto de"); ?><? echo $pedido_desconto; ?><?php echo traduz("% e valor do frete"); ?></b>
                            </td>
                            <?
                            } else {
                                if (isFabrica(87)) {
                                    $coluna = 8;
                                }else if($login_fabrica == 120 or $login_fabrica == 201){
                                    $coluna = 7;
                                }else if($login_fabrica == 125){
                                    $coluna = 10;
                                } else if ($login_fabrica == 42) {
                                    $coluna = 6;
                                }
                            ?>
                                <td colspan='<?echo $coluna;?>' align='center'><b><?php echo traduz("TOTAL"); ?></b></td><?php
                            }
                        } else {

                            $colspan = (in_array($login_fabrica, array(11,119,135,172))) ? 6 : 7;
                            if(in_array($login_fabrica, array(149,153,156,157,161,165,168)) || ($usa_calculo_ipi)){
                                $colspan =  (regiao_suframa() == true && in_array($login_fabrica, array(161))) ? 7 :8;
                            }
                            $colspan = (in_array($login_fabrica, array(142,138))) ? 7 : $colspan;

                            $colspan = (in_array($login_fabrica, array(165))) ? 9 : $colspan;
                            $colspan = (in_array($login_fabrica, array(174))) ? 12 : $colspan;

                            if(in_array($login_fabrica, array(147))){
                                if(visualiza_estoque_distrib($login_admin)){
                                    $colspan = 10;
                                }else{
                                    $colspan = 9;
                                }
                            }


                            $colspan = (in_array($login_fabrica, array(160,11,172)) or $replica_einhell) ? 10 : $colspan;
                            $colspan = (in_array($login_fabrica, array(11,172)) and visualiza_estoque_distrib($login_admin)) ? 10 : 8;

                            if ($login_fabrica == 175){ $colspan = "16"; }
                            $label_sub_total = traduz("SUBTOTAL");
                            if(isset($telaPedido0315) and !in_array($login_fabrica,array(138,142,143))){

                                if (in_array($login_fabrica,[144,151])) {
                                    $colspan = 7;
                                }

                                $label_sub_total = "TOTAL";
                            }
                            if ($login_fabrica == 160 or $replica_einhell) {
                                $label_sub_total = traduz("TOTAL S/ DESCONTO");
                            }

                            if ($login_fabrica == 168) {
                                $colspan = 8;
                                ?>
                                    <td align='center'><b><?php echo traduz("TOTAL PEÇAS"); ?></b></td>
                                    <td align='center'><b><?= $total_qtde ?></b></td>
                                <?
                            }

                            if ($login_fabrica == 147) {
                                if (visualiza_estoque_distrib($login_admin)) {
                                    $colspan = '10';
                                } else {
                                    $colspan = '8';
                                }
                            }

                            if ($login_fabrica == 143) {
                                $colspan = '8';
                            }

                            if(in_array($login_fabrica, [169,170])){
                                $colspan = '8';   
                            }

                              if ($login_fabrica == 11 OR  $login_fabrica == 172) {
                                if (visualiza_estoque_distrib($login_admin)) {
                                    $colspanlenoxx = '10';
                                } else {
                                    $colspanlenoxx = '8';
                                }
                            }

                            if ($login_fabrica == 164) {
                            	$colspan = '7';
                            }

                            if ($login_fabrica == 187 && $tipo_pedido == "Garantia") {
                                if (visualiza_estoque_distrib($login_admin)) {
                                    $colspan = 9;
                                } else {
                                    $colspan = 7;
                                }
                            }
?>
                            <td colspan='<?php echo $colspan; ?>' align='center'><b><?=$label_sub_total?></b></td>
<?php
                        }

                        if ($login_fabrica <> 14) {

                            if ($login_fabrica == 87) {

                                $total_pedido = $total_pedido_jacto;
                            }

                            if (in_array($login_fabrica, [88,126,175])) {
                                $sql = "SELECT sum((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco) as total FROM tbl_pedido_item WHERE pedido = $pedido";
                                $res = pg_query($con,$sql);
                                $total_pedido = pg_fetch_result($res,0,0);

                                if ($login_fabrica != 175) {
                                    if($login_fabrica == 88){
                                        $total_pedido = $total_pedido + $valor_frete;
                                    }else{
                                        $total_pedido = $total_pedido + $valor_frete + (($total_pedido / 100) * $ipi);
                                    }
                                }

                            }

                            if($login_fabrica == 168 && strlen($valor_frete) > 0){
                                $tbl_pedido_total = $subtotal + $valor_frete;
                            }

                            if (isset($telaPedido0315)) {

                                $usa_frete = \Posvenda\Regras::get("usa_frete", "pedido_venda", $login_fabrica);

                                if ($login_fabrica == 160 or $replica_einhell) {
                                    $tbl_pedido_total = $total_pedido;
                                }

                                if ($login_fabrica == 175) {
                                    $tbl_pedido_total =  $total_geral_impostos_item + $total_pedido;
                                }

                                if(in_array($login_fabrica, [167, 203])){
                                    $valor_desconto = $pedido_valores_adicionais['valor_desconto_fabricante'];
                                    $valor_adicionais_pedido = $pedido_valores_adicionais['adicional_fabricante'];

                                    $valor_desconto = str_replace(",", ".", $valor_desconto);
                                    $valor_adicionais_pedido = str_replace(",", ".", $valor_adicionais_pedido);

                                    $total_com_desconto = $tbl_pedido_total - ($tbl_pedido_total * $valor_desconto / 100);
                                    #$tbl_pedido_total = $total_com_desconto + $valor_adicionais_pedido;
                                    $tbl_pedido_total = $total_com_desconto;

?>
                                    <td align='right' nowrap><b><?=number_format ($tbl_pedido_total,2,',','.')?></b></td>
<?php
                                } else {
                                    if($login_fabrica == 147) {
                                        $tbl_pedido_total = $total_geral_ipi;
                                    } elseif ($login_fabrica == 35) {
										$tbl_pedido_total = $total_tela;
									} else if ($login_fabrica == 139) {
                                        $tbl_pedido_total = $total_pedido;
                                    }

                                    if ($login_fabrica == 138) {
?>
                                        <td align='right' nowrap><b><?=number_format ($subtotal,2,',','.')?></b></td>
<?php
                                    } else {
?>
                                        <td align='right' nowrap><b><?=number_format ($tbl_pedido_total,2,',','.')?></b></td>
<?php
                                    }

                                    if ($login_fabrica == 147) {
?>
                                    <td>&nbsp;</td>
                                    <td><?=number_format($valorTotalDesconto,2,',','.')?></td>
<?php
                                    }
                                }
                                // if(!empty($usa_frete)){
                                //     $subtotal += $valor_frete;
                                // }

                                // if($login_fabrica == 153 or $login_fabrica == 160 or $replica_einhell){
                                //     echo "<td align='right' nowrap><b>".number_format ($total_pedido,2,',','.')."</b></td>";
                                // }else{
                                //     echo "<td align='right' nowrap><b>".number_format ($subtotal,2,',','.')."</b></td>";
                                // }
                            } else {

                                if($login_fabrica == 94){
                                    $total_pedido += $valor_frete;
                                }
?>

                                <?php if ($login_fabrica == 87): ?>
                                   <td align='right' nowrap><b><? echo number_format ($total_pedido_jacto_sem_st,2,",",".");?></b></td>
                                <?php endif ?>

                                <td align='right' nowrap><b><? echo number_format ($total_pedido,2,",",".");?></b></td>
                        <?php
                            }

                        } else {?>
                            <td align='right' nowrap><b><? echo str_replace (".",",",$total_pedido); ?></b></td><?php
                        }

                        if ($login_fabrica == 87) {
                            echo "<td>&nbsp;</td>";
                        }

                        if (($login_fabrica == 11 and strtoupper($tipo_pedido) == "VENDA") && !isset($telaPedido0315)) {
                            $colspan_venda = 10;
                            echo "<tr>";
                                echo "<td colspan='{$colspanlenoxx}' align='center'><b>".traduz("Desconto sobre pedido de venda")." ($pedido_desconto%)</b></td>";
                                echo "<td align='right' nowrap><b>";
                                echo str_replace ('.',',',$total_pedido * $pedido_desconto / 100)."</b></td>";
                            echo "</tr>";

                            echo "<tr>";
                                echo "<td colspan='$colspanlenoxx' align='center'><b>".traduz("TOTAL")."</b></td>";
                                echo "<td align='right' nowrap><b>";
                                $total_geral = $total_pedido - ($total_pedido * $pedido_desconto / 100);
                                echo str_replace ('.',',',number_format($total_geral,2,",","."))."</b></td>";
                            echo "</tr>";

                        }

                        if (in_array($login_fabrica,array(160,191)) or $replica_einhell) { ?>
                            <tr class='titulo_coluna'>
                                <td colspan='<?=$colspan?>' align='center'><b><?php echo traduz("TOTAL C/ DESCONTO"); ?></b></td>
                                <td align='right' nowrap><b>
                                <?php
                                $total_geral = $total_pedido - ($total_pedido * $pedido_desconto / 100);
                                echo str_replace ('.',',',number_format($total_geral,2,",","."));
                                ?>
                                </b></td>
                            </tr>
<?php
                        }
                    }
?>
                </tr>
            </table>
<?php

            if ($login_fabrica == 143 && $status_pedido == 18) {
?>
                <br />
                <div style="width: 700px; background-color: #59B259; color: #FFFFFF; font-weight: bold; text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 14px; margin: 0 auto;" >
                    <?php echo traduz("Pedido calculado aguardando aprovação"); ?>
                </div>
                <table class="tabela" style="margin: 0 auto; width: 100%;">
                    <tr>
                        <th class="titulo_coluna" colspan="100%"><?php echo traduz("ATUALIZAÇÃO DE VALORES"); ?></th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th><?php echo traduz("Componente"); ?></th>
                        <th><?php echo traduz("Qtde"); ?></th>
                        <th><?php echo traduz("Valor Unitário"); ?></th>
                        <th><?php echo traduz("Valor Total"); ?></th>
                        <th><?php echo traduz("Novo Valor Unitário"); ?></th>
                        <th><?php echo traduz("Novo Valor Total"); ?></th>
                    </tr>
                    <?php
                    $sqlItensAtualizados = "
                        SELECT
                            (tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS componente,
                            tbl_pedido_item.qtde,
                            tbl_pedido_item.preco AS valor_unitario,
                            tbl_pedido_item.total_item AS valor_total,
                            tbl_pedido_item.acrescimo_financeiro AS novo_valor_unitario,
                            tbl_pedido_item.acrescimo_tabela_base AS novo_valor_total
                        FROM tbl_pedido_item
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                        WHERE tbl_pedido_item.pedido = {$pedido};
                    ";
                    $resItensAtualizados = pg_query($con, $sqlItensAtualizados);

                    $novo_total_pedido = 0;

                    if (pg_num_rows($resItensAtualizados)) {
                        while ($item = pg_fetch_object($resItensAtualizados)) {
                            $novo_total_pedido += $item->novo_valor_total;

                            echo "
                                <tr>
                                    <td>{$item->componente}</td>
                                    <td style='text-align: center;' >{$item->qtde}</td>
                                    <td style='text-align: right;' >".number_format($item->valor_unitario, 2, ",", ".")."</td>
                                    <td style='text-align: right;' >".number_format($item->valor_total, 2, ",", ".")."</td>
                                    <td style='text-align: right; font-weight: bold; color: #FF6600;' >".number_format($item->novo_valor_unitario, 2, ",", ".")."</td>
                                    <td style='text-align: right; font-weight: bold; color: #FF6600;' >".number_format($item->novo_valor_total, 2, ",", ".")."</td>
                                </tr>
                            ";
                        }
                    }
                    ?>
                    <tr>
                        <th colspan="3" class="titulo_coluna" style="text-align: right;" ><?php echo traduz("Total"); ?></th>
                        <td style='text-align: right;' ><?=number_format($total_pedido, 2, ",", ".")?></td>
                        <th class="titulo_coluna" style="text-align: right;" ><?php echo traduz("Novo Total"); ?></th>
                        <td style='text-align: right; font-weight: bold; color: #FF6600;' ><?=number_format($novo_total_pedido, 2, ",", ".")?></td>
                    </tr>
                    <tr>
                        <td colspan="100%" style="text-align: center;" >
                            <form method="post" >
                                <input type="submit" name="aprova_atualizacao_pedido" value="<?php echo traduz("Aprovar Pedido"); ?>" />
                            </form>
                        </td>
                    </tr>
                </table>
<?php
            }

            if ($login_fabrica == 168 && $status_pedido != 27 && strlen($valor_frete) == 0) {
?>
                <form method="POST" name="frm_pedido_cotacao_frete" action="<?=$PHP_SELF?>">
                    <input type="hidden" name="posto_cep"       id="posto_cep"      value="<?=$posto_cep?>" />
                    <input type="hidden" name="total_pedido"    id="total_pedido"   value="<?=$tbl_pedido_total?>" />
                    <table border="0"  class="formulario" align="center" width="700">
                        <thead>
                            <tr class="titulo_tabela"><th><?php echo traduz("Cotação de Frete"); ?></th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <table border="0" width='700' align="center">
                                        <tr>
                                            <td style="width:20px;">&nbsp;</td>
                                            <td>
                                                <?php echo traduz("Tamanho"); ?>
                                                <br />
                                                <select name="caixa" id="caixa" >
                                                    <option value="18,13,6"><?php echo traduz("Tipo"); ?> 1 (18x13x6)</option>
                                                    <option value="32,13,10"><?php echo traduz("Tipo"); ?> 2 (32x13x10)</option>
                                                    <option value="30,20,13"><?php echo traduz("Tipo"); ?> 3 (30x20x13)</option>
                                                    <option value="30,20,23"><?php echo traduz("Tipo"); ?> 4 (30x20x23)</option>
                                                    <option value="35,35,20"><?php echo traduz("Tipo"); ?> 5 (35x35x20)</option>
                                                    <option value="48,22,43"><?php echo traduz("Tipo"); ?> 6 (48x22x43)</option>
                                                    <option value="60,35,35"><?php echo traduz("Tipo"); ?> 7 (60x35x35)</option>
                                                    <option value="30,30,25"><?php echo traduz("Tipo"); ?> 8 (30x30x25)</option>
                                                    <option value="45,30,10"><?php echo traduz("Tipo"); ?> 9 (45x30x10)</option>
                                                    <option value="53,53,45"><?php echo traduz("Tipo"); ?> 10 (53x53x45)</option>
                                                    <option value="61,52,40"><?php echo traduz("Tipo"); ?> 11 (61x52x40)</option>
                                                    <option value="49,26,26"><?php echo traduz("MPBX 100"); ?> (49x26x26)</option>
                                                    <option value="tamanho_personalizado"><?php echo traduz("Personalizado"); ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <?php echo traduz("Volume"); ?><br/>
                                                <input type="text" name="volume" id="volume" size="10" value=""/>
                                            </td>
                                            <td>
                                                <?php echo traduz("Peso Real"); ?><br/>
                                                <input type="text" name="peso_real" id="peso_real" size="10" value=""/>
                                            </td>
                                            <td style="width:20px;">&nbsp;</td>
                                        </tr>
                                        <tr class="box_tamanho_personalizado" style="visibility:hidden;">
                                            <td style="width:20px;">&nbsp;</td>
                                            <td>
                                                <select name="tipo_tamanho_personalizado" id="tipo_tamanho_personalizado" style="margin-bottom: 4px; margin-top: 4px;" >
                                                    <option value="1"><?php echo traduz("Pacotes e Caixas"); ?></option>
                                                    <option value="2"><?php echo traduz("Envelopes"); ?></option>
                                                    <option value="3"><?php echo traduz("Rolos e Cilindros"); ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <table border="0" colspan="2">
                                                    <tr>
                                                        <td>C:<input type="text" name="comp_pers" id="comp_pers" size="2" maxlength="2" value=""/></td>
                                                        <td><span id="larg_diam">L</span>:<input type="text" name="larg_pers" id="larg_pers" size="2" maxlength="2" value="" /></td>
                                                        <td>A:<input type="text" name="alt_pers"  id="alt_pers"  size="2" maxlength="2" value="" /></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td style="width:20px;">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td style="width:20px;">&nbsp;</td>
                                            <td >
                                                <?php echo traduz("Valor Frete"); ?><br/>
                                                <div id="valor_frete_c_input"></div>
                                                <input type="hidden" name="valor_frete_c" id="valor_frete_c" value="" />
                                            </td>
                                            <td colspan="2">
                                                <?php echo traduz("Valor Transportadora"); ?><br/>
                                                <input type="text" name="valor_frete_t" id="valor_frete_t"  size="10" value="" />
                                            </td>
                                            <td style="width:20px;">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" align="center" style="height: 22px; line-height: 22px;" class="carregando-frete">
                                                <!-- Carregando... -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:20px;">&nbsp;</td>
                                            <td colspan="3" style="text-align:center;">
                                                <button id="cotar_frete" name="cotar_frete"><?php echo traduz("Cotar Frete"); ?></button>
                                                <button id="envio_posto" name="envio_posto"><?php echo traduz("Enviar Frete para o posto"); ?></button>
                                            </td>
                                            <td style="width:20px;">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
<?php
            }

            if ($login_fabrica == 138) {
                $sql = "SELECT posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
                $res = pg_query($con, $sql);

                $posto = pg_fetch_result($res, 0, "posto");

                $comprovante_pagamento = $s3->getObjectList("{$login_fabrica}_{$posto}_{$pedido}");
                $comprovante_pagamento = basename($comprovante_pagamento[0]);

                $ext = strtolower(preg_replace("/.+\./", "", basename($comprovante_pagamento)));

                if (!in_array($ext, array("pdf", "doc", "docx"))) {
                    $comprovante_pagamento_thumb = $s3->getObjectList("thumb_{$login_fabrica}_{$posto}_{$pedido}");
                    $comprovante_pagamento_thumb = basename($comprovante_pagamento_thumb[0]);

                    $comprovante_pagamento_thumb = $s3->getLink($comprovante_pagamento_thumb);
                }

                $comprovante_pagamento = $s3->getLink($comprovante_pagamento);

                if (!empty($comprovante_pagamento)) {
                    if (!in_array($ext, array("pdf", "doc", "docx"))) {
                       echo "
                        <tr>
                            <td>
                                <script src='../js/FancyZoom.js'></script>
                                <script src='../js/FancyZoomHTML.js'></script>
                                <script>
                                    $(function(){
                                        setupZoom();
                                    });
                                </script>
                                <table style='margin: 0 auto; width: 700px;'>
                                    <tbody>
                                        <tr>
                                            <td class='titulo_tabela'>" . traduz("Comprovante de Pagamento") . "</td>
                                        </tr>
                                        <tr>
                                            <td style='text-align: center;' >
                                                <a href='{$comprovante_pagamento}' ><img src='$comprovante_pagamento_thumb' /></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        ";
                    }

                    echo "
                    <br />
                    <table border='0' align='center' width='700'>
                        <tr>
                            <td>
                                <a href='$comprovante_pagamento' target='_blank'>" . traduz("Clique aqui para fazer o download do comprovante de pagamento") . "</a>
                            </td>
                        </tr>
                    </table>
                    ";
                }

                $copia_pedido = $s3->getObjectList("copia_{$login_fabrica}_{$posto}_{$pedido}");
                $copia_pedido = basename($copia_pedido[0]);

                $ext = strtolower(preg_replace("/.+\./", "", basename($copia_pedido)));

                if (!in_array($ext, array("pdf", "doc", "docx"))) {
                    $copia_pedido_thumb = $s3->getObjectList("thumb_copia_{$login_fabrica}_{$posto}_{$pedido}");
                    $copia_pedido_thumb = basename($copia_pedido_thumb[0]);

                    $copia_pedido_thumb = $s3->getLink($copia_pedido_thumb);
                }

                $copia_pedido = $s3->getLink($copia_pedido);

                if (!empty($copia_pedido)) {
                    if (!in_array($ext, array("pdf", "doc", "docx"))) {
                       echo "
                        <tr>
                            <td>
                                <script src='../js/FancyZoom.js'></script>
                                <script src='../js/FancyZoomHTML.js'></script>
                                <script>
                                    $(function(){
                                        setupZoom();
                                    });
                                </script>
                                <table style='margin: 0 auto; width: 700px;'>
                                    <tbody>
                                        <tr>
                                            <td class='titulo_tabela'>" . traduz("Cópia do pedido") . "</td>
                                        </tr>
                                        <tr>
                                            <td style='text-align: center;' >
                                                <a href='{$copia_pedido}' ><img src='$copia_pedido_thumb' /></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        ";
                    }

                    echo "
                    <br />
                    <table border='0' align='center' width='700'>
                        <tr>
                            <td align='center'>
                                <a href='$copia_pedido' target='_blank'>" . traduz("Clique aqui para fazer o download da cópia do comprovante de pagamento") . "</a>
                            </td>
                        </tr>
                    </table>
                    ";
                }
            }

            if (isset($telaPedido0315)) {
                $anexo = \Posvenda\Regras::get("anexo", "pedido_venda", $login_fabrica);
                $anexo_qtde = \Posvenda\Regras::get("anexo", "pedido_venda", $login_fabrica);

                if ($anexo == true) {
?>
                    <tr>
                        <td colspan="100%" >
                            <script src="plugins/jquery.form.js" ></script>
                            <script>

                            $(function() {
                                $("button[name=submitForm]").click(function() {
                                    var form   = $(this).parent("form");
                                    var acaoS3 = $(form).find("input[name=acaoS3]").val();

                                    if (acaoS3 == "Excluir") {
                                        $(form).find("img.anexoS3").hide();
                                        $(form).find("img.anexoLoading").show();
                                        $(form).find("button").hide();
                                        $(form).submit();
                                    } else if (acaoS3 == "Anexar") {
                                        $(form).find("input[name=arquivoS3]").click();
                                    }
                                });

                                $("input[name=arquivoS3]").change(function() {
                                    if ($(this).val().length > 0) {
                                        var form = $(this).parent("form");

                                        $(form).find("img.anexoS3").hide();
                                        $(form).find("img.anexoLoading").show();
                                        $(form).find("button").hide();
                                        $(form).submit();
                                    }
                                });

                                $("form.anexoS3Form").each(function() {
                                    $(this).ajaxForm({
                                        complete: function(data) {
                                            data = $.parseJSON(data.responseText);

                                            var form = $("form[name=anexo_pedido_"+data.posicao+"]");

                                            if (data.error) {
                                                alert(data.error);
                                            } else {
                                                if (data.acaoS3 == "Excluir") {
                                                    $(form).find("input[name=anexoS3]").val("");
                                                    $(form).find("input[name=acaoS3]").val("Anexar");
                                                    $(form).find("button[name=submitForm]").text("Anexar");
                                                    $(form).find("img.anexoS3").attr({ src: "imagens/imagem_upload.png" });
                                                    $(form).find("a.linkAnexoS3").attr({ href: "#" });
                                                } else if (data.acaoS3 == "Anexar") {
                                                    $(form).find("input[name=anexoS3]").val(data.anexoS3);
                                                    $(form).find("input[name=acaoS3]").val("Excluir");
                                                    $(form).find("button[name=submitForm]").text("Excluir");
                                                    $(form).find("img.anexoS3").attr({ src: data.thumbImagemS3 });
                                                    $(form).find("a.linkAnexoS3").attr({ href: data.imagemS3 });
                                                }
                                            }

                                            $(form).find("button").show();
                                            $(form).find("img.anexoLoading").hide();
                                            $(form).find("img.anexoS3").show();
                                        }
                                    });
                                });
                            });

                            </script>
                            <table class="tabela" style="margin: 0 auto; width: 100%;" >
                                <tr>
                                    <th class="titulo_coluna" colspan="100%"><?php echo traduz("Comprovante de pagamento"); ?></th>
                                </tr>
                                <tr>
                                    <?php
                                    for ($i = 0; $i < $anexo_qtde; $i++) {
                                        unset($anexoS3);

                                        $anexo = $s3->getObjectList("{$pedido}_{$i}.");

                                        if (count($anexo) > 0) {
                                            $acaoS3   = "Excluir";
                                            $anexoS3  = basename($anexo[0]);
                                            $imagemS3 = $s3->getLink($anexoS3);
                                            $ext      = strtolower(preg_replace("/.+\./", "", $anexoS3));

                                            switch ($ext) {
                                                case "pdf":
                                                    $thumbImagemS3 = "imagens/pdf_icone.png";
                                                    break;

                                                case "doc":
                                                case "docx":
                                                    $thumbImagemS3 = "imagens/docx_icone.png";
                                                    break;

                                                default:
                                                    $thumbImagemS3 = $s3->getLink("thumb_".$anexoS3);
                                                    break;
                                            }
                                        } else {
                                            $acaoS3        = "Anexar";
                                            $imagemS3      = "#";
                                            $thumbImagemS3 = "imagens/imagem_upload.png";
                                        }
                                        ?>
                                        <td>
                                            <form name="anexo_pedido_<?=$i?>" class="anexoS3Form" method="post" enctype="multipart/form-data" >
                                                <a class="linkAnexoS3" href="<?=$imagemS3?>" target="_blank" >
                                                    <img class="anexoS3" src="<?=$thumbImagemS3?>" style="width: 100px; height: 90px;" />
                                                </a>

                                                <img class="anexoLoading" src="imagens/loading_img.gif" style="width: 64px; height: 64px; display: none;" />

                                                <br />

                                                <input type="file" name="arquivoS3" value="" style="display: none;" />
                                                <button type="button" name="submitForm" ><?=$acaoS3?></button>
                                                <input type="hidden" name="acaoS3" value="<?=$acaoS3?>" />
                                                <input type="hidden" name="anexoS3" value="<?=$anexoS3?>" />
                                                <input type="hidden" name="posicao" value="<?=$i?>" />
                                            </form>
                                        </td>
                                    <?php
                                    }
                                    ?>
                                </tr>
                            </table>
                            <br />
                        </td>
                    </tr>
                <?php
                }
            }

            if (in_array($login_fabrica, array(1))) {
                $sql= "SELECT observacao FROM tbl_pedido_status where pedido = $pedido and status = 18 and length(observacao) > 0 and admin notnull";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0 ){?>

                    <table class="tabela" align="center" width="700">
                        <tr class="titulo_coluna"><td><?php echo traduz("Justificativa"); ?></td></tr>
                        <tr>
                            <td>
                                <?
                                    echo pg_fetch_result($res,0,0);
                                ?>
                                &nbsp;
                                </font>
                            </td>
                        </tr>
                    </table>
                <?php
                }
            }

            if ($login_fabrica == 7) {

                $sql = "SELECT  os                                           AS os,
                                sua_os                                       AS sua_os,
                                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
                            FROM    tbl_os
                            JOIN    tbl_pedido ON tbl_pedido.pedido = tbl_os.pedido_cliente
                            WHERE   tbl_pedido.pedido  = $pedido
                            AND     tbl_pedido.fabrica = $login_fabrica
                            AND     tbl_os.fabrica     = $login_fabrica
                            ORDER BY sua_os ;";

                $res2 = pg_query ($con,$sql);

                if (pg_num_rows($res2) > 0) {
                    echo "<br>";
                    echo "<table width='400' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
                    echo "<thead>";
                        echo "<tr bgcolor='#C0C0C0'>";
                            echo "<td align='center' colspan='3'><b>" . traduz("Ordens de Serviço que geraram o pedido acima") . "</b></td>";
                        echo "</tr>";
                        echo "<tr bgcolor='#C0C0C0'>";
                            echo "<td align='center'><b>" . traduz("OS") . "</b></td>";
                            echo "<td align='center'><b>" . traduz("Abertura") . "</b></td>";
                            echo "<td align='center'><b>" . traduz("Fechamento") . "</b></td>";
                        echo "</tr>";
                    echo "</thead>";

                    for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {

                        $os              = pg_fetch_result($res2, $i, 'os');
                        $sua_os          = pg_fetch_result($res2, $i, 'sua_os');
                        $data_abertura   = pg_fetch_result($res2, $i, 'data_abertura');
                        $data_fechamento = pg_fetch_result($res2, $i, 'data_fechamento');

                        if ($i % 2 == 0) $cor = '#F1F4FA';

                        echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal' >";
                            echo "<td align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
                            echo "<td align='center'>$data_abertura</td>";
                            echo "<td align='center'>$data_fechamento</td>";
                        echo "</tr>";

                    }

                    echo "</table>";

                }

            }

            if ($login_fabrica == 15) { // HD 115459
                echo "<br>";
                echo "<a href='pedido_admin_consulta_impressao.php?pedido=$pedido' target='_blank'><img src='imagens/btn_imprimir.gif'></a>";
            }

        if ($detalhar == "ok") {

            echo "<br>";
            #Mostar somente para pedidos de OS - Fabrica 1 - HD  14831
            #Nao mostrar as OS do tipo de pedido LOCADOR -  HD 15114

            if (($tipo_pedido_id <> 94 and (strpos(strtoupper($condicao),"GARANTIA") !== false or $login_fabrica <> 1)) OR (strlen($pedido_cliente)> 0 AND $login_fabrica == 7) ) {

                if (!in_array($login_fabrica,$vet_fabrica_cancela)) {

                $sql = "SELECT  distinct
                                LPAD(tbl_os.sua_os::TEXT,10,'0'),
                                tbl_peca.peca           ,
                                tbl_peca.referencia     ,
                                tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                tbl_peca.descricao      ,
                                tbl_os.os               ,
                                tbl_os.sua_os           ,
                                tbl_pedido.posto
                        FROM    tbl_pedido
                        JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                        JOIN    tbl_peca        ON  tbl_peca.peca             = tbl_pedido_item.peca
                        LEFT JOIN tbl_os_item   ON  tbl_os_item.peca          = tbl_pedido_item.peca
                                                AND tbl_os_item.pedido_item      = tbl_pedido_item.pedido_item
                        LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
                        LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
                        WHERE   tbl_pedido_item.pedido = $pedido
                        AND     tbl_pedido.fabrica     = $login_fabrica
                        ORDER BY tbl_peca.descricao;";

                $sql_item = '';

                if ($login_fabrica <> 14  and $login_fabrica <> 43 and $login_fabrica <> 50) {
                    $sql_item = " ,tbl_pedido_item.pedido_item ";
                }
                if($login_fabrica == 30){
                    $sql_item .= " , tbl_pedido_item.pedido_item_atendido ";
                }
                $extra_cond_join_os_item = '';
                if (in_array($login_fabrica, $vet_fabrica_pedido_item)) {
                    $extra_cond_join_os_item = ' AND tbl_os_item.pedido_item    = tbl_pedido_item.pedido_item ';
                }


                $sql = "SELECT  distinct
                                lpad(tbl_os.sua_os::TEXT,10,'0'),
                                tbl_peca.peca      ,
                                tbl_peca.referencia,
                                tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                tbl_peca.descricao ,
                                tbl_os.os          ,
                                tbl_os.sua_os      ,
                                tbl_os_item_nf.nota_fiscal,
                                TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf,
                                tbl_pedido.posto   ,
                                tbl_pedido.exportado
                                $sql_item
                        FROM    tbl_pedido
                        JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
                        JOIN    tbl_peca        ON  tbl_peca.peca              = tbl_pedido_item.peca
                        LEFT JOIN tbl_os_item   ON  tbl_os_item.peca           = tbl_pedido_item.peca
				AND tbl_os_item.pedido         = tbl_pedido.pedido and (tbl_os_item.pedido_item = tbl_pedido_item.pedido_item or tbl_os_item.pedido_item isnull)
				$extra_cond_join_os_item
                        LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
                        LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item       = tbl_os_item_nf.os_item
                        WHERE   tbl_pedido_item.pedido = $pedido
                        ORDER BY tbl_peca.descricao;";

                if ($login_fabrica == 7) {

                    $sql = "SELECT  distinct
                                    tbl_pedido_item.pedido_item,
                                    lpad(tbl_os.sua_os::TEXT,10,'0'),
                                    tbl_peca.peca      ,
                                    tbl_peca.referencia,
                                    tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                    tbl_peca.descricao ,
                                    tbl_os.os          ,
                                    tbl_os.sua_os      ,
                                    tbl_os_item_nf.nota_fiscal,
                                    tbl_pedido.posto
                            FROM    tbl_pedido
                            JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
                            JOIN    tbl_peca        ON  tbl_peca.peca              = tbl_pedido_item.peca
                            LEFT JOIN tbl_os_item   ON  tbl_os_item.peca           = tbl_pedido_item.peca
                                                    AND (tbl_os_item.pedido_cliente = tbl_pedido.pedido
                                                    OR tbl_os_item.pedido = tbl_pedido.pedido)
                            LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
                            LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item       = tbl_os_item_nf.os_item
                            WHERE   tbl_pedido_item.pedido = $pedido
                            ORDER BY lpad(tbl_os.sua_os::TEXT,10,'0');";

                } else {

                    if ($login_fabrica == 5) {

                        $sql = "SELECT  distinct
                                    lpad(tbl_os.sua_os::TEXT,10,'0') ,
                                    tbl_peca.peca      ,
                                    tbl_peca.referencia,
                                    tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                    tbl_peca.descricao ,
                                    tbl_os.os          ,
                                    tbl_os.sua_os      ,
                                    tbl_os.revenda_nome,
                                    tbl_os_item_nf.nota_fiscal,
                                    tbl_pedido.posto,
                                    tbl_os_item.os_item
                                    FROM    tbl_pedido
                                    JOIN    tbl_pedido_item     ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
                                    JOIN    tbl_peca            ON  tbl_peca.peca              = tbl_pedido_item.peca
                                    JOIN    tbl_os_item         ON  tbl_os_item.peca           = tbl_pedido_item.peca
                                    AND tbl_os_item.pedido         = tbl_pedido.pedido
                                    LEFT JOIN tbl_os_produto    ON  tbl_os_produto.os_produto  = tbl_os_item.os_produto
                                    LEFT JOIN tbl_os            ON  tbl_os.os                  = tbl_os_produto.os
                                    LEFT JOIN tbl_os_item_nf    ON  tbl_os_item.os_item        = tbl_os_item_nf.os_item
                                    WHERE   tbl_pedido_item.pedido = $pedido
                                    and tbl_pedido.fabrica = $login_fabrica
                                    ORDER BY tbl_peca.descricao";

                    }

                }

                $res = pg_query ($con,$sql);

            } else {

		if (isset($novaTelaOs)) {
            if(in_array($login_fabrica, [169,170])){
                        $campo_faturamento = "tbl_faturamento_item.faturamento_item, ";
                        $join_faturamento = " left join tbl_faturamento_item on tbl_faturamento_item.pedido = tbl_pedido.pedido and tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item left join tbl_faturamento on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento  ";
                    }

		    $sql = "SELECT  DISTINCT
                        tbl_pedido_item.pedido_item,
                        tbl_peca.peca           ,
                        tbl_peca.referencia     ,
                        tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                        tbl_peca.descricao      ,
                        tbl_os.os               ,
                        tbl_os.sua_os           ,
                        tbl_pedido.posto        ,
                        tbl_pedido.exportado    ,
                        --tbl_os_item.oid         ,
                        tbl_pedido_item.peca_alternativa,
						$campo_faturamento
                        tbl_os_item.os_item
                    FROM    tbl_pedido
                    JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                    JOIN    tbl_peca          ON  tbl_peca.peca             = tbl_pedido_item.peca
                    LEFT JOIN tbl_os_item     ON  tbl_os_item.pedido         = tbl_pedido.pedido
                                             AND tbl_pedido_item.pedido_item= tbl_os_item.pedido_item
                    LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
                    LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
                    $join_faturamento
                    WHERE   tbl_pedido_item.pedido = $pedido
                    AND     tbl_pedido.fabrica     = $login_fabrica
                    ORDER BY tbl_peca.descricao";
		} else if (in_array($login_fabrica,$vet_fabrica_cancela)) {
                    if($telecontrol_distrib){
                        $join_faturamento = " LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_faturamento.fabrica = $login_fabrica  ";
                    }


                    $sql = "SELECT  DISTINCT
                        tbl_pedido_item.pedido_item,
                        $campo_faturamento
                        tbl_peca.peca           ,
                        tbl_peca.referencia     ,
                        tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                        tbl_peca.descricao      ,
                        tbl_os.os               ,
                        tbl_os.sua_os           ,
                        tbl_os.nota_fiscal_saida AS nf_saida_b,
                        TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida_b,
                        tbl_os.consumidor_revenda AS consumidor_revenda_b,
                        tbl_pedido.posto        ,
                        tbl_pedido.exportado    ,
                        tbl_os_item.oid         ,
                        tbl_os_item.os_item
                    FROM    tbl_pedido
                    JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                    JOIN    tbl_peca          ON  tbl_peca.peca             = tbl_pedido_item.peca
                    LEFT JOIN tbl_os_item     ON tbl_os_item.pedido         = tbl_pedido.pedido
                                             AND (tbl_pedido_item.pedido_item= tbl_os_item.pedido_item or (tbl_pedido_item.peca = tbl_os_item.peca and tbl_os_item.pedido_item isnull))
                    LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
                    LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
                    $join_faturamento
                    WHERE   tbl_pedido_item.pedido = $pedido
                    AND     tbl_pedido.fabrica     = $login_fabrica
                    ORDER BY tbl_peca.descricao";

                } else if ($login_fabrica == 51) {

                    $sql = "SELECT  DISTINCT
                                    tbl_pedido_item.pedido_item,
                                    tbl_peca.peca           ,
                                    tbl_peca.referencia     ,
                                    tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                    tbl_peca.descricao      ,
                                    tbl_os.os               ,
                                    tbl_os.sua_os           ,
                                    tbl_pedido.posto        ,
                                    tbl_pedido.exportado    ,
                                    tbl_os_item.oid         ,
                                    tbl_os_item.os_item
                            FROM    tbl_pedido
                            JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                            JOIN    tbl_peca          ON  tbl_peca.peca             = tbl_pedido_item.peca
                            JOIN    tbl_os_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
                            JOIN    tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN    tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
                            WHERE   tbl_pedido_item.pedido = $pedido
                            AND     tbl_pedido.fabrica     = $login_fabrica
                            ORDER BY tbl_peca.descricao";

                } else {

                    $sql = "SELECT  DISTINCT
                                    '' as pedido_item,
                                    tbl_peca.peca           ,
                                    tbl_peca.referencia     ,
                                    tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                    tbl_peca.descricao      ,
                                    tbl_os.os               ,
                                    tbl_os.sua_os           ,
                                    tbl_pedido.posto        ,
                                    tbl_pedido.exportado    ,
                                    tbl_os_item.oid         ,
                                    tbl_os_item.os_item
                    FROM    tbl_pedido
                    JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                    JOIN    tbl_peca          ON  tbl_peca.peca             = tbl_pedido_item.peca
                    LEFT JOIN tbl_os_item     ON  tbl_os_item.peca          = tbl_pedido_item.peca AND tbl_os_item.pedido  = tbl_pedido.pedido
                    LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
                    LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
                    WHERE   tbl_pedido_item.pedido = $pedido
                    AND     tbl_pedido.fabrica     = $login_fabrica
                    AND     tbl_os.os NOTNULL

                    UNION

                        SELECT  distinct
                                '' as pedido_item,
                                tbl_peca.peca           ,
                                tbl_peca.referencia_fabrica     AS peca_referencia_fabrica,
                                tbl_peca.referencia     ,
                                tbl_peca.descricao      ,
                                tbl_os.os               ,
                                tbl_os.sua_os           ,
                                tbl_pedido.posto        ,
                                tbl_pedido.exportado    ,
                                tbl_pedido_cancelado.oid,
                                tbl_pedido_item.pedido_item as os_item
                        FROM    tbl_pedido
                        JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
                        JOIN    tbl_peca        ON  tbl_peca.peca             = tbl_pedido_item.peca
                        JOIN    tbl_pedido_cancelado ON  tbl_pedido_cancelado.peca = tbl_pedido_item.peca
                                    AND tbl_pedido_cancelado.pedido    = tbl_pedido_item.pedido
                        LEFT JOIN tbl_os ON  tbl_os.os = tbl_pedido_cancelado.os

                        WHERE   tbl_pedido_item.pedido = $pedido
                        AND     tbl_pedido.fabrica     = $login_fabrica
                        AND     tbl_os.os notnull

                    ORDER BY 5";

                }

                $res = pg_query ($con,$sql);
            }
            if ( (pg_num_rows($res) > 0 AND in_array($login_fabrica,array(86,94,98,99,101))) OR (pg_num_rows($res) > 0 AND !in_array($login_fabrica,array(86,94,98,99,101))) && !($login_fabrica == 143 && $status_pedido == 19)) {

                $qtdeItens = pg_num_rows($res);
                if (in_array($login_fabrica, [11,172,178])) {
                    $arrOs = pg_fetch_all($res);
                    $temOs = false;

                    foreach ($arrOs as $ky => $vl) {
                        if (!empty($vl['os'])) {
                            $temOs = true;
                            break;
                        }
                    }
                }

                for ($i = 0 ; $i < pg_num_rows($res); $i++) {

                    $nf_saida_b             = pg_fetch_result($res, $i, 'nf_saida_b');//hd_chamado=2788473
                    $data_nf_saida_b        = pg_fetch_result($res, $i, 'data_nf_saida_b');//hd_chamado=2788473
                    $consumidor_revenda_b   = pg_fetch_result($res, $i, 'consumidor_revenda_b');//hd_chamado=2788473
                    if($telecontrol_distrib OR in_array($login_fabrica, [169,170]) ){
                        $faturamento_item = pg_fetch_result($res, $i, 'faturamento_item');
                    }

                    $peca = pg_fetch_result($res, $i, 'peca');
                    $peca_alternativa = pg_fetch_result($res, $i, 'peca_alternativa');
                    $os   = pg_fetch_result($res, $i, 'os');
                    $sua_os   = pg_fetch_result($res, $i, 'sua_os');

                    if ($login_fabrica == 171) {
                        $peca_descricao = pg_fetch_result($res, $i, 'referencia') . " / " . pg_fetch_result($res, $i, 'peca_referencia_fabrica') . " - " . pg_fetch_result($res, $i, 'descricao');
                    } else {
                        $peca_descricao = pg_fetch_result($res, $i, 'referencia') . " - " . pg_fetch_result($res, $i, 'descricao');
                    }
                    $posto          = pg_fetch_result($res, $i, 'posto');

                    if (in_array($login_fabrica, array(94,98,99,101))) {
                        $exportado  = pg_fetch_result($res, $i, 'exportado');
                    }

                    if ($login_fabrica == 51 or $login_fabrica == 81 or $login_fabrica == 10 or $login_fabrica == 104 or $login_fabrica == 101) {
                        $os_item    = pg_fetch_result($res, $i, 'os_item');
                    }

                    $pedido_item = pg_fetch_result ($res, $i, 'pedido_item');
                    if($login_fabrica == 30){
                        $pedido_item_atendido = pg_fetch_result ($res, $i, 'pedido_item_atendido');
                    }

                    $cor = ($i % 2 ) ? '#F1F4FA' : "#F7F5F0";
                    if (!empty($pedido_item)) {
                        /*$sqlItem = "SELECT (qtde - (qtde_faturada + qtde_cancelada)) AS qtde_pendente
                                         FROM tbl_pedido_item
                                        WHERE pedido_item = $pedido_item";*/
                        $sqlItem = "SELECT qtde, qtde_faturada, qtde_faturada_distribuidor, qtde_cancelada FROM tbl_pedido_item WHERE pedido_item = $pedido_item";

                        if($login_fabrica == 99 AND stripos($tipo_pedido,"GARANTIA") !== false){
                            $sqlItem = "SELECT  tbl_os_item.qtde,
                                                tbl_faturamento_item.qtde AS qtde_faturada,
                                                tbl_pedido_cancelado.qtde AS qtde_cancelada,
                                                tbl_pedido_item.qtde_faturada_distribuidor
                                        FROM tbl_os_item
                                        JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = tbl_pedido_item.peca
                                        LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_item.pedido = tbl_pedido_cancelado.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca AND tbl_pedido_cancelado.os = $os
                                        LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.os = $os
                                        WHERE tbl_os_item.pedido_item = $pedido_item";
                        }

                        $resItem = pg_query($con, $sqlItem);

                        $qtde = pg_fetch_result($resItem,0,'qtde');
                        $qtde_faturada_distribuidor = pg_fetch_result($resItem,0,'qtde_faturada_distribuidor');
                        $qtde_faturada = pg_fetch_result($resItem,0,'qtde_faturada');
                        if ($distribuidor == 4311) $qtde_faturada = $qtde_faturada_distribuidor;
                        $qtde_cancelada = pg_fetch_result($resItem,0,'qtde_cancelada');

                        $itemPendente = $qtde - ($qtde_faturada + $qtde_cancelada);

                    }

                    if ($login_fabrica != 1 and !empty($peca)) {

                        if (in_array($login_fabrica,array(3,5,6,7,10,14,24,30,35,40,42,43,45,46,50,72,74,80,81,85,90,91,94,95,88,99,101,108,111,114,115,116,117,120,201,121,122))  or $login_fabrica >= 123 )  {
                            $sql_adicional = " AND tbl_faturamento_item.pedido = $pedido ";
                        } else {
                            $sql_adicional = " AND tbl_faturamento.pedido      = $pedido ";
                        }

						if(!empty($pedido_item)) {
                            $sql_adicional .= " AND (tbl_faturamento_item.pedido_item      = $pedido_item or tbl_faturamento_item.pedido_item isnull)  ";
						}
                        if(in_array($login_fabrica,array(99,101)) AND stripos($tipo_pedido,"GARANTIA") !== false){
                            $sql_adicional .= " AND (tbl_faturamento_item.os = $os or tbl_faturamento_item.os ISNULL)";
                        }

                        if($telecontrol_distrib != "t"){
                            $cond_posto = " AND tbl_faturamento.posto = $posto ";
                        }

			            $leftAlternativa = "";
                    	$condPeca = "";
			$distinct = "";
                    	if (in_array($login_fabrica, array(169,170))) {
			    $distinct = "DISTINCT";
                            $leftAlternativa = "
				LEFT JOIN tbl_peca_alternativa pa_de ON pa_de.peca_de = {$peca} AND pa_de.fabrica = {$login_fabrica}
				LEFT JOIN tbl_peca_alternativa pa_para ON pa_para.peca_para = {$peca} AND pa_para.fabrica = {$login_fabrica}
			    ";
                            $condPeca = "AND (tbl_faturamento_item.peca = {$peca} OR tbl_faturamento_item.peca = pa_de.peca_para OR tbl_faturamento_item.peca = pa_para.peca_de OR tbl_faturamento_item.peca IN (SELECT DISTINCT peca_para FROM tbl_peca_alternativa WHERE fabrica = {$login_fabrica} AND peca_de = pa_para.peca_de OR peca_de = pa_de.peca_para))";
			            } else {
                            $condPeca = "AND tbl_faturamento_item.peca = {$peca}";
                    	}

                        $sql  = "SELECT {$distinct}
					trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                        tbl_faturamento.faturamento                      ,
                                        tbl_faturamento.conhecimento                     ,
                                        TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao    ,
                                        CASE
                                            WHEN $login_fabrica in (125,122) THEN
                                                tbl_pedido_item.qtde_faturada_distribuidor
                                            ELSE
                                                tbl_faturamento_item.qtde
                                        END as qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                JOIN    tbl_pedido_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND ((tbl_faturamento_item.peca = $peca  and tbl_faturamento_item.pedido_item isnull) or (tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item))
				{$leftAlternativa}
                                WHERE tbl_faturamento.fabrica = {$login_fabrica}
                                and tbl_faturamento_item.faturamento_item = $faturamento_item 
                                {$condPeca}
                                $cond_posto
                                $sql_adicional;";
                        if ($login_fabrica == 2) {

                            $sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                            tbl_faturamento.faturamento                     ,
                                            tbl_faturamento.conhecimento
                                    FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                                    JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
                                    JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
                                    JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                        AND tbl_faturamento.fabrica = $login_fabrica
                                    JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                                    WHERE   tbl_faturamento.posto     = $posto
                                    AND     tbl_faturamento_item.peca = $peca";

                        }

                        $resx = pg_query($con, $sql);

						if(($login_fabrica == 160 or $replica_einhell) and pg_num_rows($resx)==0){
							$cond_peca = " tbl_faturamento_item.peca = $peca  ";
							if(!empty($peca_alternativa)) {
								$cond_peca = " tbl_faturamento_item.peca = $peca_alternativa  ";
							}
                            $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                        tbl_faturamento.faturamento                      ,
                                        tbl_faturamento.conhecimento                     ,
                                        TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao    ,
                                        tbl_pedido_item.qtde_faturada_distribuidor as qtde
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                JOIN    tbl_pedido_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = $peca
								WHERE $cond_peca
								$cond_posto
                                $sql_adicional;";
                                $resx = pg_query($con, $sql);
                        }

                        $nf            = '';
                        $data_nf_saida = '';
                        $faturamento   = '';
                        $emissao       = '';
                        $qtde_volume   = '';
                        $conhecimento  = '';

                        if (pg_num_rows($resx) > 0) {

                            $nf            = trim(pg_fetch_result($resx, 0, 'nota_fiscal'));
                            $data_nf_saida = trim(pg_fetch_result($resx, 0, 'emissao'));
                            $emissao       = trim(pg_fetch_result($resx, 0, 'emissao'));
                            $faturamento   = trim(pg_fetch_result($resx, 0, 'faturamento'));

                            if ($login_fabrica >= 99) {
                                $qtde_volume = trim(pg_fetch_result($resx, 0, 'qtde'));
                            }


                            //Gustavo 12/12/2007 HD 9590
                            if (in_array($login_fabrica, array(35,147))) $conhecimento = trim(pg_fetch_result($resx, 0, 'conhecimento'));

                        } else {

                            if (in_array($login_fabrica, array(3,6,11,46,94,95,98,99,88,74,106,108,111,115,116,117,120,201,121,122))  or $login_fabrica >= 123) {
                                $and_fat = "AND tbl_faturamento.fabrica     = $login_fabrica";
                                if (in_array($login_fabrica, [11,172])) {
                                    $and_fat = "AND (tbl_faturamento.fabrica = $login_fabrica OR tbl_faturamento.fabrica = 10)";
                                }

                                $sql = "SELECT  trim(tbl_faturamento.nota_fiscal) AS nota_fiscal            ,
                                                tbl_faturamento.faturamento                                 ,
                                                tbl_faturamento.conhecimento                                ,
                                                TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao    ,
                                                CASE
                                                    WHEN $login_fabrica = 125 THEN
                                                        tbl_pedido_item.qtde_faturada_distribuidor
                                                    ELSE
                                                        tbl_pedido_item.qtde_faturada
                                                END as qtde
                                        FROM    tbl_faturamento_item
                                        JOIN    tbl_faturamento ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                           $and_fat
                                        JOIN    tbl_peca        USING (peca)
					                    JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido      = tbl_faturamento_item.pedido
						                AND (tbl_pedido_item.peca        = tbl_faturamento_item.peca or tbl_pedido_item.peca_alternativa = tbl_faturamento_item.peca)
                                                AND tbl_pedido_item.peca        = $peca
                                        WHERE   tbl_faturamento_item.pedido = $pedido";
                                $resY = pg_query($con,$sql);

                                if (pg_num_rows($resY) == 0) {

                                    $sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal , TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                                    tbl_faturamento.conhecimento
                                            FROM tbl_faturamento
                                            JOIN tbl_faturamento_item USING (faturamento)
                                            WHERE tbl_faturamento.posto = 4311
                                            AND   tbl_faturamento_item.pedido = $pedido
                                            AND   tbl_faturamento_item.peca   = $peca";
                                    $resY = pg_query($con,$sql);

                                }
                            } else if (in_array($login_fabrica, array(10,51,81,87,114)) or $telecontrol_distrib) {

                                $sql = "SELECT  trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
                                                tbl_faturamento.faturamento                      ,
                                                tbl_faturamento.conhecimento                     ,
                                                TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao    ,
                                                tbl_faturamento.total_nota                       ,
                                                tbl_faturamento.cfop
                                        FROM    tbl_faturamento
                                        JOIN    tbl_faturamento_item USING (faturamento)
                                        WHERE   tbl_faturamento.fabrica in( $login_fabrica,10)
                                        AND     (tbl_faturamento.pedido    = $pedido OR tbl_faturamento_item.pedido=$pedido)
                                        AND     tbl_faturamento_item.peca = $peca ";

                                if ($login_fabrica <> 10 and !empty($os_item)) {
                                    $sql .= " AND     (tbl_faturamento_item.os_item = $os_item) ";
                                }

                                $sql .= "ORDER BY lpad(tbl_faturamento.nota_fiscal::TEXT,20,'0') ASC;";

                                $resY = pg_query($con,$sql);

                                if (pg_num_rows($resY) == 0) {

                                    $sql = "SELECT tbl_faturamento.faturamento,
                                                    tbl_faturamento.nota_fiscal ,
                                                    TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                                                    tbl_faturamento.conhecimento
                                            FROM    tbl_faturamento
                                            JOIN    tbl_faturamento_item USING (faturamento)
                                            WHERE   tbl_faturamento.posto = 4311
                                            AND     tbl_faturamento_item.pedido = $pedido";

                                    if ($login_fabrica <> 10 and !empty($os_item)) {
                                        $sql .= " AND     (tbl_faturamento_item.os_item = $os_item ) ";
                                    }

                                    $sql .= "AND   tbl_faturamento_item.peca = $peca";
                                    $resY = pg_query ($con,$sql);

                                    if(pg_num_rows($resY) == 0){
                                        if(in_array($login_fabrica,array(81,114)) AND stripos($tipo_pedido,"GARANTIA") !== false){
                                            $sql = "SELECT tbl_faturamento.faturamento,
                                                            tbl_faturamento.nota_fiscal ,
                                                            TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                                                            tbl_faturamento.conhecimento
                                                    FROM    tbl_faturamento
                                                    JOIN    tbl_faturamento_item USING (faturamento)
                                                    WHERE   tbl_faturamento_item.peca = $peca
                                                    AND     tbl_faturamento.fabrica in( $login_fabrica,10)
                                                    AND     tbl_faturamento_item.os   = $os";
                                            $resY = pg_query ($con,$sql);

                                        }
                                    }
                                }

                            } else {
                                $extra_cond_join = '';
                                if ($login_fabrica == 35) {
                                    $extra_cond_join = ' AND tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item ';
                                }

                                $sql = "SELECT tbl_faturamento.faturamento ,
                                                tbl_faturamento.nota_fiscal ,
                                                TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                                                tbl_faturamento.conhecimento,
                                                tbl_faturamento.envio_frete
                                            FROM tbl_pedido_item
                                            JOIN tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido
                                                AND tbl_pedido_item.peca = tbl_faturamento_item.peca
                                                $extra_cond_join
                                            JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                AND tbl_faturamento.fabrica = $login_fabrica
                                            JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                                            WHERE tbl_faturamento_item.peca = $peca
											AND     (tbl_faturamento_item.pedido_item = $pedido_item or tbl_faturamento_item.pedido_item isnull)
                                            AND tbl_faturamento_item.pedido = $pedido";

                                $resY = pg_query($con, $sql);

                            }


                            if (pg_num_rows($resY) > 0) {

                                $nf = pg_fetch_result($resY, 0, 'nota_fiscal');
                                if(in_array($login_fabrica,array(81,114)) AND stripos($tipo_pedido,"GARANTIA") !== false){
                                    $data_nf_saida = pg_fetch_result($resY, 0, 'emissao');
                                }
                                $faturamento = pg_fetch_result($resY, 0, 'faturamento');
								$emissao    = trim(pg_fetch_result($resY, 0, 'emissao'));

                                if ($login_fabrica == 86 || $login_fabrica == 101){
                                    $conhecimento = pg_fetch_result($resY, 0, 'conhecimento');
                                    $envio_frete = pg_fetch_result($resY, 0, 'envio_frete');
                                }

                                if (in_array($login_fabrica, [11,172])) {
                                    $conhecimento = pg_fetch_result($resY, 0, 'conhecimento');
                                }

                                if ($login_fabrica == 87 || $login_fabrica == 104) {
                                    $total_nota = trim(pg_fetch_result($resY, 0, 'total_nota'));
                                    $cfop       = trim(pg_fetch_result($resY, 0, 'cfop'));
                                }
                                if ($login_fabrica >= 99 and $login_fabrica <> 104 AND pg_num_rows($resx) > 0) {
                                    $emissao     = pg_fetch_result($resx, 0, 'emissao');
                                    $qtde_volume = pg_fetch_result($resx, 0, 'qtde');
                                }
                                //Gustavo 12/12/2007 HD 9590
                                if ($login_fabrica == 35) $conhecimento = trim(pg_fetch_result($resY, 0, 'conhecimento'));

                            } else {

                                if($qtde_faturada == 0 and $qtde_cancelada == 0) {
                                    $nf = "Pendente";
                                }
                                if($qtde_cancelada > 0 and $qtde_faturada == 0) {
                                    $nf = "Cancelado";
                                }
                                if($qtde_faturada > 0) {
                                    $nf = "Embarcado";
                                }

                                if ($login_fabrica == 52) {
                					if (strlen($os) > 0){

                						$sqlQtdeCancelada = "SELECT (tbl_os_item.qtde - tbl_pedido_cancelado.qtde) AS qtde
                							FROM tbl_pedido_cancelado
                							JOIN tbl_os_produto ON tbl_os_produto.os = tbl_pedido_cancelado.os
                							JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                							WHERE tbl_pedido_cancelado.os = $os
                							AND tbl_pedido_cancelado.peca = $peca
                							AND tbl_pedido_cancelado.fabrica = $login_fabrica";
                						$resQtdeCancelada = pg_query($con, $sqlQtdeCancelada);

                						if (pg_num_rows($resQtdeCancelada) > 0 && pg_fetch_result($resQtdeCancelada, 0, "qtde") == 0) {
                							$nf = "Cancelado";
                						}else{
                							$nf = "Pendente";
                						}
                					}
                				}
                            }

                        }

                    } else {

                        #HD 13653
                        if (in_array($login_fabrica,[1])) {
                            $emissao = pg_fetch_result ($res, $i, 'data_nf');
                        }

                        $nf = pg_fetch_result ($res, $i, 'nota_fiscal');

                        if (strlen($nf) == 0) {

                            $sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
                                            TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
                                    FROM    tbl_os
                                    JOIN    tbl_os_produto USING (os)
                                    JOIN    tbl_os_item USING (os_produto)
                                    WHERE   tbl_os_item.pedido = $pedido
                                    AND     tbl_os_item.peca   = $peca";

                            $resnf = pg_query ($con,$sql);

                            if (pg_num_rows($resnf) > 0) {
                                $nf            = trim(pg_fetch_result($resnf, 0, 'nota_fiscal_saida'));
                                $data_nf_saida = trim(pg_fetch_result($resnf, 0, 'data_nf_saida'    ));
                            } else if ($itemPendente > 0) {
                                $nf = "Pendente";
                            } else {
                                $nf = "Cancelado";
                            }

                        }

                    }

                    if (strlen($sua_os) == 0) $sua_os = $os;

                    # Chamado 10028
                    if ($login_fabrica == 1 AND $tipo_pedido_id != 86) {

                        if ($nf == "pendente" OR $nf == "Pendente") {
                            $nf = "pendente";
                        }

                    }

                    if ($i == 0) {
                        // HD 22962
                        if (in_array($login_fabrica, array(52))) {
                            echo '<form name="frm_cancelar_selecao" method="POST" action="'.$PHP_SELF.'">';
                            echo '<input type="hidden" name="qtde_itens" value="'.$qtdeItens.'" />';
                            echo '<input type="hidden" name="cancelar_selecao" value="" />';
                            echo '<input type="hidden" name="pedido" value="'.$pedido.'" />';
                        }
                        echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='tabela' >";
                        echo "<thead>";

                        if (in_array($login_fabrica, [35])) {

                            echo "<div style='float:right; margin-left: 5px'>";

                            /**
                             * @author William Castro <william.castro@telecontrol.com.br>
                             *
                             * hd-6517984
                             *
                             * Adição dos botoes de Histórico HelpDesk e Abrir HelpDesk
                             *
                             */

                            if ((isset($tipo_pedido)) && ($tipo_pedido == "Embarcado")) {

                                /*
                                    Verificar a possibilidade de já carregar o posto do pedido
                                */

                                // redireciona para a tela de abertura de HelpDesk

                                echo "<a target='_blank' href='../admin/helpdesk_posto_autorizado_novo_atendimento.php?posto_num={$codigo_posto}' value='Abrir'><button>Abrir HelpDesk</button></a>";

                            }

                            echo "<a style='margin-left: 5px' rel='shadowbox' href='shadowbox_lista_helpdesk.php?pedido={$pedido}' value='Historico'><button>Histórico Reclamação</button></a>";

                            echo "</div>";
                        }

                        if (strlen($os) > 0) {
                            echo "<caption class='titulo_tabela'>";
                            echo traduz("Ordens de Serviço que geraram o pedido acima");
                            echo "</caption>";
                        }
                        echo "<tr class='titulo_coluna'>";

                        if (in_array($login_fabrica, array(52))) {
                            echo '<td><input type="checkbox" class="frm main" name="marcar" value="tudo" title=' . "Selecione ou desmarque todos" . ' onClick="SelecionaTodos(this.form.ativo);" style="cursor: hand;" /></td>';
                        }

                        //if ($condicao == "Garantia") {
                            //strpos($condicao,"GARANTIA") !== false or coloquei 11/12/07 hd 9460

                        if ( ((in_array($login_fabrica, [11,172,178]) && $temOs) || strlen($os) > 0) &&  ((strpos($condicao,"GARANTIA") !== false or strpos($condicao,"Garantia") !== false or strpos($condicao, "Livre de débito") !== false or strpos($condicao,"Reposição Antecipada") !== false or strpos(strtoupper($condicao), "LIVRE DE D") !== false  or (strlen($pedido_cliente) > 0 AND $login_fabrica==7)) or strtoupper($tipo_pedido) == 'GARANTIA') and !in_array($login_fabrica,array(95)) OR ( in_array($login_fabrica, array(163)) AND strpos($condicao,"BONIFICAÇÃO") !== false) OR (in_array($login_fabrica, array(186)) AND strpos($condicao,"BOLETO") !== false)) {
                            echo "<td align='center'><b>" . traduz("Sua OS") . "</b></td>";
                        }

                        if (!in_array($login_fabrica,array(11,24,40,94,95,98,172))) {#HD 347649 - INICIO
                            echo "<td align='center'><b>" . traduz("Nota Fiscal") . "</b></td>";
                        } else if (!in_array($login_fabrica,array(24,40,94,95,98,99,101,88))) {
                            echo "<td align='center'><b>" . traduz("Situação") . "</b></td>";
                        }#HD 347649 - FIM
                        if($login_fabrica == 157){
                            echo "<td align='center'><b>" . traduz("Transportadora") . "</b></td>";
                        }

                        if($login_fabrica == 30) {
                            echo "<td align='center'><b>" . traduz("Data prevista de entrega") . "</b></td>";
                            echo "<td align='center'><b>" . traduz("Status de entrega") . "</b></td>";
                        }

                        if (in_array($login_fabrica, array(11,35,45,74,80,86,147,151,168,172)) OR $telecontrol_distrib) {
                            echo "<td align='center'><b>" . traduz("Conhecimento") . "</b></td>";
                        }

                        if (in_array($login_fabrica, array(175))) {
                            echo "<td align='center'><b>" . traduz("Código de Rastreio") . "</b></td>";
                        }

                        if(!in_array($login_fabrica, array(40))){
                            echo "<td align='center'><b>" . traduz("Emissão") . "</b></td>";
                        }

                        echo "<td align='center'><b>" . traduz("Peça") . "</b></td>";
                        if($login_fabrica == 30){
                            echo "<td align='center'><b>" . traduz("Peça Substituída") . "</b></td>";
                        }
                        if ($login_fabrica >= 99 AND strtoupper($tipo_pedido) != "GARANTIA"){
                            echo "<td align='center'><b>" . traduz("Qtde") . "<br>" . traduz("Faturada") . "</b></td>";
                        }

                        if ($login_fabrica == 86) {
                            echo "<td align='center'><b>" . traduz("Rastreio Transporte") . "</b></td>";
                            echo "<td align='center'><b>" . traduz("Tipo Frete") . "</b></td>";
                        }

                        if ($login_fabrica == 101) {
                            echo "<td align='center'><b>" . traduz("Rastreio Transporte") . "</b></td>";
                        }

                        if ($login_fabrica == 87) {
                            echo "<td align='center'><b>" . traduz("Emissão") . "</b></td>";
                            echo "<td align='center'><b>" . traduz("CFOP") . "</b></td>";
                            echo "<td align='center'><b>" . traduz("Total Nota") . "</b></td>";
                        }

                        if (in_array($login_fabrica, array(2,6, 10, 45, 51, 80, 81,88,114))){
                            echo "<td align='center'><b>" . traduz("Qtde Pendente") . "</b></td>";
                        }

                        if (in_array($login_fabrica, array_merge($vet_fabrica_cancela,$vet_gama_salton_tele,array(45,80))) && !in_array($status_pedido, array(4,14)) && !in_array($login_fabrica, [52, 175])) {
                            echo "<td align='center'><b>" . traduz("Ação") . "</b></td>";
                        }
                        if (in_array($login_fabrica, array_merge($vet_fabrica_cancela,$vet_gama_salton_tele)) AND $login_fabrica == 52) {
                            echo "<td align='center'><b>" . traduz("Qtde a Cancelar") . "</b></td>";
                        }
                        if(in_array($login_fabrica, array(40))){
                            echo "<td>" . traduz("Qtde") . "</td>";
                            echo "<td>" . traduz("Faturada") . "</td>";
                            echo "<td>" . traduz("Cancelada") . "</td>";
                            echo "<td>" . traduz("Pendente") . "</td>";
                            echo "<td>" . traduz("Nota Fiscal") . "</td>";
                        }
                        if($login_fabrica == 3){
                            echo "<td>" . traduz("Qtde") . "</td>";
                        }
                        echo "</tr>";
                        echo "</thead>";
                    }

                    echo "<tr bgcolor='$cor'>";

                    if (in_array($login_fabrica, array(52))) {
                        echo '<td align="center"><input type="hidden" name="pedido_item_'.$i.'" value="'.$pedido_item.'"><input type="hidden" name="os_'.$i.'" value="'.$os.'"><input type="checkbox" class="frm peca" name="marcar_'.$i.'" value="sim" title="Selecione para Cancelar itens desta peça" /></td>';
                    }

                    if ( ((in_array($login_fabrica, [11,172,178]) && $temOs) || strlen($os) > 0) && ((strpos($condicao,"GARANTIA") !== false or strpos($condicao,"Garantia") !== false or
                         strpos($condicao,"Livre de débito") !== false or strpos($condicao,"Reposição Antecipada") !== false or (strlen($pedido_cliente)>0 AND $login_fabrica==7)) or stripos($tipo_pedido,"GARANTIA") !== false ) AND !in_array($login_fabrica,array(95)) OR ( in_array($login_fabrica, array(163)) AND strpos($condicao,"BONIFICAÇÃO") !== false) OR (in_array($login_fabrica, array(186)) AND strpos($condicao,"BOLETO") !== false)) {

                            $mostraOs = (empty($sua_os)) ? "&nbsp;" : $sua_os;

                        echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$mostraOs</a></font></td>";
                    }else {
                        if (empty($sua_os) AND empty($os)){
                            #echo "<td></td>";
                        }else {
                          echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
                        }
                    }

                    if (!in_array($login_fabrica,array(24,40,94,95,98))) { //HD 404932
						$disabled_marcar = false;
                        echo "<td align='center'>";

                        $sql  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido = $pedido AND peca = $peca and (pedido_item = $pedido_item or pedido_item isnull)";

                        if (in_array($login_fabrica,array(2,51,81,74,99,137)) AND stripos($tipo_pedido,"GARANTIA") !== false AND strlen($os) > 0) {
                            $sql .= " AND os = $os ";
                        }
                        if($login_fabrica == 15){
                            $sql .= "AND pedido_item = $pedido_item";
                        }

                        $resY = pg_query ($con,$sql);

                        if($login_fabrica == 52){
                            if(strlen(trim($nf))>0 AND strtoupper($nf) <> strtoupper("Pendente")){
								$disabled_marcar = true;
                                echo "<script>$('input[name=marcar_".$i."]').attr('disabled', true);</script>";
                            }
                        }

                        if ((pg_num_rows ($resY) > 0 and !in_array($login_fabrica, array(3,101,106,160))) and !$replica_einhell) {
                            if((($qtde - ($qtde_cancelada + $qtde_faturada) == 0) AND $qtde_faturada > 0) OR ($login_fabrica == 74 and $qtde_faturada > 0)){
                                $nf_desc = $nf;
                            }else if ($qtde == $qtde_cancelada){
                                $nf_desc = "Cancelado";
                                if (in_array($login_fabrica, array(52)) and !$disabled_marcar) {
									$disabled_marcar = true;
                                    echo "<script>$('input[name=marcar_".$i."]').attr('disabled', true);</script>";
                                }
                            }elseif($qtde > $qtde_cancelada){
                                $nf_desc = "Pendente";
                            }elseif ($qtde_faturada > 0) {
                                $nf_desc = "Faturado Parcial";
                            }

                            if ($login_fabrica == 52) {
                                $sqlQtdeCancelada = "SELECT (tbl_os_item.qtde - tbl_pedido_cancelado.qtde) AS qtde
                                        FROM tbl_pedido_cancelado
                                        JOIN tbl_os_produto ON tbl_os_produto.os = tbl_pedido_cancelado.os
                                        JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                        WHERE tbl_pedido_cancelado.os = $os
                                        AND tbl_pedido_cancelado.peca = $peca
                                        AND tbl_os_item.pedido_item = $pedido_item
                                        AND tbl_pedido_cancelado.fabrica = $login_fabrica";
                                $resQtdeCancelada = pg_query($con, $sqlQtdeCancelada);
                                if (pg_num_rows($resQtdeCancelada) > 0 && pg_fetch_result($resQtdeCancelada, 0, "qtde") == 0) {
                                    $nf_desc = "Cancelado";
									if (!$disabled_marcar) {
                                        echo "<script>$('input[name=marcar_".$i."]').attr('disabled', true);</script>";
									}
									echo ($nf) ? $nf : "<acronym title='".pg_fetch_result($resY,0,motivo)."'>$nf_desc</acronym>";
                                }
                            }else{
	                            echo "<acronym title='".pg_fetch_result($resY,0,motivo)."'>$nf_desc</acronym>";
							}
                        } else {
                            if (1==1) {

								if (in_array($login_fabrica,array(6,35,46,74,81,88,95,98,99,101,115,116,117,120,201,121,122))  or ($login_fabrica >= 123 && $login_fabrica != 172)) {
									$sqlF = "SELECT pedido_item, qtde_faturada ,qtde, qtde_cancelada FROM tbl_pedido_item WHERE pedido = $pedido AND peca = $peca and pedido_item = $pedido_item";
									$resF = pg_query($con,$sqlF);
									#echo nl2br($sqlF);
									if (pg_num_rows($resF) > 0) {

										if((in_array($login_fabrica,array(81,88,99,101,121)) or $telecontrol_distrib) and !empty($os) anD stripos($tipo_pedido,"GARANTIA") !== false){
											$sql_os = " AND (tbl_faturamento_item.os = $os or tbl_faturamento_item.os ISNULL)";
										}

										if ((in_array($login_fabrica,array(74,35,101)) or $login_fabrica >=123) && stripos($tipo_pedido,"GARANTIA") !== false) {
											$sql_os_item = " AND (tbl_faturamento_item.pedido_item = $pedido_item or tbl_faturamento_item.pedido_item isnull)";
										}

										$fat            = pg_fetch_result($resF, 0, 'qtde_faturada');
										$qtde           = pg_fetch_result($resF, 0, 'qtde');
										$qtde_cancelada = pg_fetch_result($resF, 0, 'qtde_cancelada');
										//$pedido_item  = pg_fetch_result($resF, 0, 'pedido_item');

										$whereCancelamento = "";
										if (in_array($login_fabrica, array(151))) {
											$whereCancelamento = " AND tbl_faturamento.cancelada IS NULL";
										}

										$leftAlternativa = "";
										$condPeca = "";
										$distinct = "";
										if (in_array($login_fabrica, array(169,170))) {
											$distinct = "DISTINCT";
											$leftAlternativa = "
												LEFT JOIN tbl_peca_alternativa pa_de ON pa_de.peca_de = {$peca} AND pa_de.fabrica = {$login_fabrica}
												LEFT JOIN tbl_peca_alternativa pa_para ON pa_para.peca_para = {$peca} AND pa_para.fabrica = {$login_fabrica}
											";
											$condPeca = "AND (tbl_faturamento_item.peca = {$peca} OR tbl_faturamento_item.peca = pa_de.peca_para OR tbl_faturamento_item.peca = pa_para.peca_de OR tbl_faturamento_item.peca IN (SELECT DISTINCT peca_para FROM tbl_peca_alternativa WHERE fabrica = {$login_fabrica} AND peca_de = pa_para.peca_de OR peca_de = pa_de.peca_para))";
                                            $whereFaturamento_item = " and tbl_faturamento_item.faturamento_item = $faturamento_item ";

										}elseif($telecontrol_distrib){
											$leftAlternativa = " JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido and tbl_pedido_item.pedido_item = $pedido_item";
											if(!empty($faturamento_item)) {
												$whereFaturamento_item = " and tbl_faturamento_item.faturamento_item = $faturamento_item ";
											}
											$condPeca = " and ((tbl_faturamento_item.peca = $peca and tbl_pedido_item.peca = tbl_faturamento_item.peca) or tbl_faturamento_item.peca = tbl_pedido_item.peca_alternativa) ";
										} else {
											$condPeca = "AND tbl_faturamento_item.peca = {$peca}";
										}

										$sqlN = "SELECT {$distinct}
											tbl_faturamento.nota_fiscal,
											tbl_transportadora.nome AS transp,
											tbl_faturamento.conhecimento,
											TO_CHAR(emissao,'DD/MM/YYYY') as emissao, faturamento
											FROM tbl_faturamento_item
											JOIN tbl_faturamento USING(faturamento)
											LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_faturamento.transportadora
										{$leftAlternativa}
										WHERE tbl_faturamento_item.pedido = $pedido
										{$condPeca}
										$sql_os
										$sql_os_item
                                        $whereFaturamento_item
										$whereCancelamento";
										$resN = pg_query($con, $sqlN);

										unset($emissao);

                                        $nf = "";

										if (pg_num_rows($resN) > 0) {
                                            for($cont = 0; $cont<pg_num_rows($resN); $cont++){
    											$nf =  pg_fetch_result($resN,$cont,'nota_fiscal');
    											$transp = pg_fetch_result($resN,0,'transp');
    											$emissao = pg_fetch_result($resN,0,'emissao');
    											$conhecimento = pg_fetch_result($resN, 0, 'conhecimento');
    											$faturamento = pg_fetch_result($resN, 0, 'faturamento');
                                            }
											if($telecontrol_distrib == true and $nf == '000000'){
												$nf = "";
												$emissao = "";
											}
										} else {
											if((in_array($login_fabrica,array(81)) or $distrib_telecontrol) and !empty($os)){
												$sqlN = "SELECT tbl_faturamento.nota_fiscal,
													TO_CHAR(emissao,'DD/MM/YYYY') as emissao
													FROM tbl_faturamento_item
													JOIN tbl_faturamento USING(faturamento)
													WHERE  tbl_faturamento_item.peca     = $peca
													AND tbl_faturamento.fabrica in (10,$login_fabrica)
													and (tbl_faturamento_item.pedido = $pedido or tbl_faturamento_item.pedido isnull)
													AND tbl_faturamento_item.os = $os";

												$resN = pg_query($con, $sqlN);

												if (pg_num_rows($resN) > 0) {
													$nf = pg_fetch_result($resN,0,nota_fiscal);
													$emissao = pg_fetch_result($resN,0,'emissao');
												}elseif($qtde > ($fat+$qtde_cancelada)){
													$nf = "Pendente";
												}

												if($telecontrol_distrib == true and $nf == '000000'){
													$nf = "";
													$emissao = "";
												}

											}elseif(($login_fabrica == 160 or $replica_einhell)){

												if(!empty($peca_alternativa) > 0) {
												$sqlN = "SELECT distinct tbl_faturamento.nota_fiscal,
													tbl_faturamento.transp,
													tbl_faturamento.conhecimento,
													TO_CHAR(emissao,'DD/MM/YYYY') as emissao, faturamento
													FROM tbl_faturamento_item
													JOIN tbl_faturamento USING(faturamento)
													WHERE tbl_faturamento_item.pedido = $pedido
													AND tbl_faturamento_item.peca     = $peca_alternativa
															$sql_os
															$sql_os_item";
												}else{
													$sqlN = "SELECT distinct tbl_faturamento.nota_fiscal,
													tbl_faturamento.transp,
													tbl_faturamento.conhecimento,
													TO_CHAR(emissao,'DD/MM/YYYY') as emissao, faturamento
													FROM tbl_faturamento_item
													JOIN tbl_faturamento USING(faturamento)
													WHERE tbl_faturamento_item.pedido = $pedido
													AND tbl_faturamento_item.peca     = $peca
															$sql_os
															$sql_os_item";
												}
												$resN = pg_query($con, $sqlN);
												if (pg_num_rows($resN) > 0) {
													$result = pg_fetch_all($resN);
													foreach($result as $n => $nfs){
														$nf[] = $nfs['nota_fiscal'];
														$emissao[] = $nfs['emissao'];
														$conhecimento[] = $nfs['conhecimento'];
														$fats[] = $nfs['faturamento'];

													}
													$nf = implode(",", $nf);
													$emissao = implode(",",$emissao);
													$conhecimento = implode(",",$conhecimento);
													$faturamento = implode(",",$fats);
												}
											}elseif($qtde > ($fat + $qtde_cancelada)){
												$nf = "Pendente";
											}

											if ($login_fabrica == 101) {
												if (strlen($os) > 0){
													$sqlQtdeCancelada = "SELECT (tbl_os_item.qtde - tbl_pedido_cancelado.qtde) AS qtde
														FROM tbl_pedido_cancelado
														JOIN tbl_os_produto ON tbl_os_produto.os = tbl_pedido_cancelado.os
														JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
														WHERE tbl_pedido_cancelado.os = $os
														AND tbl_pedido_cancelado.peca = $peca
														AND tbl_os_item.pedido_item = $pedido_item
														AND tbl_pedido_cancelado.pedido_item = $pedido_item
														AND tbl_pedido_cancelado.fabrica = $login_fabrica";
													$resQtdeCancelada = pg_query($con, $sqlQtdeCancelada);

													if (pg_num_rows($resQtdeCancelada) > 0 && pg_fetch_result($resQtdeCancelada, 0, "qtde") == 0) {
														$nf = "Cancelado";
													}
												}
											}

                                            if (!empty($os) && $telecontrol_distrib && $nf == 'Pendente') {
                                                $sqlNT = "SELECT nota_fiscal_saida, 
                                                                 TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
                                                          FROM tbl_os
                                                          WHERE os = $os 
                                                          AND fabrica = $login_fabrica
                                                          AND nota_fiscal_saida NOTNULL
                                                          AND data_nf_saida NOTNULL ";
                                                $resNT = pg_query($con, $sqlNT);
                                                if (pg_num_rows($resNT) > 0) {
                                                    $nf = pg_fetch_result($resNT, 0, 'nota_fiscal_saida');
                                                    $emissao = pg_fetch_result($resNT, 0, 'data_nf_saida');
                                                }
                                            }
										}

					if($login_fabrica == 81 AND $posto == 20682 AND $consumidor_revenda_b == "R" AND strlen($nf_saida_b) > 0 AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
						echo "$nf_saida_b &nbsp;";
					}else{
						echo $nf;
					}

					// echo (!empty($emissao)) ? " - ".$emissao :"";

									}

								} else if($login_fabrica == 42){
                                    echo "<span style='cursor:pointer' onClick='linkLupeon(\"$nf\", \"$cnpj\")'>" . $nf . "</a>";                       
                                } else {

									echo (strtolower($nf) <> 'pendente') ? "$nf " : "$nf &nbsp;";

								}

                            } else if ($login_fabrica == 24 or $login_fabrica == 35) {
                                echo (strlen($nf) > 0) ? $nf : "pendente";
                            }

                        }

                        echo "</td>";

                    }

                    if($login_fabrica==157){
                        echo "<td align='center'>$transp</td>";
                    }

                    if($login_fabrica == 30) {

                        $sqlO = "SELECT obs
                                FROM tbl_pedido_item
                                WHERE pedido = $pedido
                                AND   pedido_item = $pedido_item";

                        $resO = pg_query($con, $sqlO);

                        $obs = explode(";", pg_fetch_result($resO, 0, 0));

                        echo "<td align='center'>$obs[0]</td>";
                        echo "<td align='center'>$obs[1]</td>";
                    }
                    //Gustavo 12/12/2007 HD 9590

                    if (in_array($login_fabrica, array(11,35,45,74,80,86,147,151,168,172,175)) OR $telecontrol_distrib) {
                        echo "<TD style='text-align:CENTER;'";

                        if (!empty($faturamento)) {

                            $fab_cond = " AND fabrica = $login_fabrica";
                            if (in_array($login_fabrica, [11,172]) or $telecontrol_distrib) {
                                    $fab_cond = "AND (fabrica = $login_fabrica OR fabrica = 10)";
                            }

                            $sql_verifica_conhecimento = "SELECT conhecimento AS conhecimento FROM tbl_faturamento
                                                                            WHERE faturamento in ( $faturamento ) $fab_cond";
                            $res_verifica_conhecimento = pg_query($con, $sql_verifica_conhecimento);

                            if (in_array($login_fabrica, [11,172]) && pg_num_rows($res_verifica_conhecimento) > 0 && empty($conhecimento)) {
                                $conhecimento = pg_fetch_result($res_verifica_conhecimento, 0, 'conhecimento');
                            }

                            if (in_array($login_fabrica, array(35,45,74,80,86))) {
                                echo " class='conteudo'>";

				if(strpos($conhecimento,"http") !== false){
					echo "<A HREF='$conhecimento' target = '_blank'>" . traduz("Rastreio Pedido") . "</A>";
				}else if(pg_num_rows($res_verifica_conhecimento)>0){
                                    echo "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>{$conhecimento}</A>";
                                }else{
                                    echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>{$conhecimento}</A>";
                                }
                            }

                            if (in_array($login_fabrica, array(147,151,175)) OR $telecontrol_distrib) {
                                echo " nowrap>";

                                if (preg_match("/^\[.+\]$/", $conhecimento)) {
                                    $conhecimento = json_decode($conhecimento, true);

                                    $codigos_rastreio = array();

                                    foreach ($conhecimento as $key => $codigo_rastreio) {
                                        if(pg_num_rows($res_verifica_conhecimento)>0){
                                            $codigos_rastreio[] = "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$codigo_rastreio' rel='shadowbox'>$codigo_rastreio</A>";
                                        }else{
                                            $codigos_rastreio[] = "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$codigo_rastreio' target = '_blank'>$codigo_rastreio</A>";
                                        }
                                    }

                                    echo implode(", ", $codigos_rastreio);
                                } else {
                                    if(pg_num_rows($res_verifica_conhecimento)>0){
                                        echo "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>";
                                    }else{
                                        echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
                                    }
                                    echo $conhecimento;
                                    echo "</A>";
                                }

                            }

                        } else {
                            echo ">";
                        }
                        echo "</TD>";
                    }

                    if (!in_array($login_fabrica, array(99, 101, 104)) && empty($telecontrol_distrib)){
                        $emissao = (!in_array(strtolower($nf_desc),array('cancelado','cancelada'))) ? $emissao : "";
                        unset($nf_desc);
                    }

                    if($login_fabrica != 40){

                        if($login_fabrica == 81 AND $posto == 20682 AND $consumidor_revenda_b == "R" AND strlen($nf_saida_b) > 0 AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
                            echo "<td align='center'>".$data_nf_saida_b."</td>";
                        }else{
                            echo "<td align='center'>$emissao</td>";
                        }

                    }

                    $nowrap_style = ($login_fabrica == 160 or $replica_einhell) ? "" : "";
                    echo "<td align='left' $nowrap_style>$peca_descricao</td>";
                    if($login_fabrica == 30){
                        if(strlen($pedido_item_atendido) > 0){
                            $sqlAtendido = "
                                SELECT  referencia || ' - ' || descricao AS peca_substituida
                                FROM    tbl_peca
                                JOIN    tbl_pedido_item ON  tbl_pedido_item.peca        = tbl_peca.peca
                                                        AND tbl_peca.fabrica            = $login_fabrica
                                                        AND tbl_pedido_item.pedido_item = $pedido_item_atendido
                            ";
                            $resAtendido = pg_query($con,$sqlAtendido);
                            $peca_substituida = pg_fetch_result($resAtendido,0,peca_substituida);
                        }else{
                            $peca_substituida = "&nbsp;";
                        }
                        echo "<td align='left' nowrap>$peca_substituida</td>";
                    }
                    if ($login_fabrica >= 99 AND strtoupper($tipo_pedido) != "GARANTIA") {
                        echo "<td align='center'>$qtde_volume</td>";
                    }

                    if ($login_fabrica == 86) {
                        echo "<td align='center'>$conhecimento</td>";
                        echo "<td align='center'>$envio_frete</td>";
                    }

                    if ($login_fabrica == 101) {
                        if(strpos($conhecimento,"http") !== false){
                            $rastreio_longhi = "<A HREF='' target = '_blank'>Rastreio Pedido</A>";
                        }else if(pg_num_rows($res_verifica_conhecimento)>0){
                            $rastreio_longhi = "<A HREF='./relatorio_faturamento_correios?conhecimento=$conhecimento' rel='shadowbox'>{$conhecimento}</A>";
                            }else{
                                $rastreio_longhi = "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>{$conhecimento}</A>";
                            }
                        echo "<td align='center'>$rastreio_longhi</td>";
                    }

                    if ($login_fabrica == 87) {
                        echo "<td align='left'>$emissao</td>";
                        echo "<td align='left'>$cfop</td>";
                        echo "<td align='right'>".number_format($total_nota,2,',','.')."</td>";
                    }

                    if ($login_fabrica == 45 and 1==2) {

                        echo "<td align='center'>";

                        if (strtolower($nf)=='pendente' AND pg_num_rows ($resY) == 0) {
                            echo "<form name='acao_$i'>";
                            echo traduz("Motivo:") . "<input type='text' name='motivo' class='frm'>";
                            echo "<a href='javascript: if(confirm(\"". traduz('Deseja cancelar este item do pedido: $peca_descricao?') . "\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&os=$os&motivo=\"+document.acao_$i.motivo.value'>";
                            echo " <img src='imagens/btn_x.gif'><font size='1'>" . traduz('Cancelar') . "</font></a>";
                            echo "</form>";
                        }

                        echo "</td>";

                    }



                    if (in_array($login_fabrica, array_merge($vet_fabrica_cancela,$vet_gama_salton_tele)) && !in_array($status_pedido, array(4,14,52))) {

                        if ($login_fabrica == 2) {

                            $qtde_cancelada = 0;

                            $sqli = "SELECT tbl_pedido_item.qtde,
                                            tbl_pedido_item.qtde_faturada,
                                            tbl_pedido_item.qtde_faturada_distribuidor,
                                            tbl_pedido_cancelado.qtde as qtde_cancelada
                                    FROM tbl_pedido_item
                                    LEFT JOIN tbl_pedido_cancelado on tbl_pedido_item.pedido    =tbl_pedido_cancelado.pedido and tbl_pedido_item.peca =tbl_pedido_cancelado.peca
                                    WHERE tbl_pedido_item.pedido = $pedido
                                    AND tbl_pedido_item.peca     = $peca
                                    AND tbl_pedido_cancelado.pedido_item = $pedido_item ";

                            if (strlen($os) > 0) {
                                $sqli .= " AND (tbl_pedido_cancelado.os = $os or tbl_pedido_cancelado.os is null)";
                            }

                        }elseif(in_array($login_fabrica,array(52,99)) AND (stripos($tipo_pedido,"GARANTIA") !== false OR strtoupper($tipo_pedido) == "CONSIGNADO") and !empty($os)){
                            $sqli = "SELECT tbl_os_item.qtde,
                                            tbl_faturamento_item.qtde AS qtde_faturada,
                                            tbl_pedido_cancelado.qtde AS qtde_cancelada,
                                            tbl_pedido_cancelado.os,
                                            tbl_pedido_item.qtde_faturada_distribuidor
                                    FROM tbl_pedido
                                    JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
                                    LEFT JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.peca = tbl_pedido_item.peca
                                    LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                    LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                                    LEFT JOIN tbl_faturamento_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca AND tbl_faturamento_item.os = $os
                                    LEFT JOIN tbl_pedido_cancelado ON tbl_os.os = tbl_pedido_cancelado.os AND tbl_pedido_cancelado.pedido = $pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
                                    WHERE tbl_os_item.pedido = $pedido
                                    AND tbl_pedido_item.peca = $peca
                                    AND tbl_os.os = $os";

                        }elseif (in_array($login_fabrica, $vet_fabrica_cancela)) {

                            $sqli = "SELECT tbl_pedido_item.qtde,
                                            tbl_pedido_item.qtde_faturada,
                                            tbl_pedido_item.qtde_faturada_distribuidor,
                                            tbl_pedido_item.qtde_cancelada,
                                            tbl_pedido_cancelado.os
                                    FROM tbl_pedido_item
                                    LEFT JOIN tbl_pedido_cancelado on tbl_pedido_item.pedido =tbl_pedido_cancelado.pedido and tbl_pedido_item.peca =tbl_pedido_cancelado.peca
                                    LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_faturamento_item.peca = tbl_pedido_item.peca  
                                    WHERE tbl_pedido_item.pedido_item = $pedido_item ";
                        } else {

                            $sqli = "SELECT tbl_pedido_item.qtde,
                                    tbl_pedido_item.qtde_faturada,
                                    tbl_pedido_item.qtde_faturada_distribuidor,
                                    SUM (tbl_pedido_cancelado.qtde) as qtde_cancelada
                                    FROM tbl_pedido_item
                                    LEFT JOIN tbl_pedido_cancelado on tbl_pedido_item.pedido =tbl_pedido_cancelado.pedido and tbl_pedido_item.peca =tbl_pedido_cancelado.peca
                                    WHERE tbl_pedido_item.pedido = $pedido
                                    AND tbl_pedido_item.peca = $peca
                                    GROUP BY tbl_pedido_item.qtde, qtde_faturada, qtde_faturada_distribuidor";
                        }
                        //echo nl2br($sqli)."<br><br>";
                        $resi = pg_query($con, $sqli);
                        $total_pendente = 0 ;
                        if (pg_num_rows($resi) > 0) {

                            $qtde           = pg_fetch_result($resi, 0, 'qtde');
                            $qtde_cancelada = (strlen(pg_fetch_result($resi, 0, 'qtde_cancelada')) > 0) ? pg_fetch_result($resi, 0, 'qtde_cancelada') : 0;
                            $qtde_faturada  = (pg_fetch_result($resi, 0, 'qtde_faturada_distribuidor') > 0) ? pg_fetch_result($resi, 0, 'qtde_faturada_distribuidor') : pg_fetch_result($resi, 0, 'qtde_faturada');

                            if ((in_array($login_fabrica, array(3,6,45,46,51,52,81,88,94,95,98,99,101,108,111,114,115,116,117,120,201,121,122))  or $login_fabrica >= 123) AND (stripos($tipo_pedido,"GARANTIA") !== false or strtoupper($tipo_pedido) == "CONSIGNADO")){

                                $os_cancelada           = pg_fetch_result($resi, 0, 'os');
                            }

                            if (in_array($login_fabrica, $vet_fabrica_cancela) or $login_fabrica == 81) {
                                $total_pendente = $qtde - ($qtde_faturada + $qtde_cancelada);
                                $total_qtde_final = $qtde - $qtde_cancelada;
                            }

                        }

			if($login_fabrica == 52){
				$qtde_pendente = $qtde - ($qtde_cancelada + $qtde_faturada);

				if($qtde_pendente > 0){
					echo "<script>$('input[name=marcar_".$i."]').attr('disabled', false);</script>";
				}
			}

                        if ((strtolower($nf) == 'pendente' && pg_num_rows($resY) == 0 && !in_array($login_fabrica,array(51,81,114))) || ($qtde > ($qtde_faturada + $qtde_cancelada))) {
                            if (in_array($login_fabrica, array(6,45,88,81,114))) {
                                echo "<td align='center'>$total_pendente</td>";
                            }

                            if ($nf_desc != "Cancelado" && (in_array($login_fabrica, array_merge($vet_fabrica_cancela,array(81))) and $total_pendente > 0) or (pg_num_rows($resY) == 0 and $login_fabrica <> 3 and (in_array($login_fabrica, $vet_fabrica_cancela)))) {

                                if ((in_array($login_fabrica,array(101)) and strlen($exportado)==0) OR $login_fabrica <> 101) {
                                    if (in_array($login_fabrica, [175]))
                                        continue;

                                    $mosta_form = "";

                                    if(strlen($os) > 0) {
                                        #if($os_cancelada != $os && $total_pendente > 0){
                                        if($total_pendente > 0){
                                            $mosta_form = "sim";
                                        }
                                    } else {
                                        $mosta_form = "sim";
                                    }

                                    $peca_descricao = str_replace('"','',$peca_descricao);
                                    $peca_descricao = str_replace("'","",$peca_descricao);
                                    $total_qtde_final = $qtde - $qtde_cancelada;

                                    if(!empty($total_qtde_final) AND !empty($mosta_form)){
                                            $mostra_acoes = true;
                                            if ($login_fabrica == 175){
                                                $mostra_acoes = false;
                                                if ($status_pedido == 1 AND empty($aprovado_cliente)){
                                                    $mostra_acoes = true;
                                                }
                                            }
                                            echo "<td align='left' nowrap>";
                                            if ($mostra_acoes === true){
                                                if(in_array($login_fabrica, array(52,151))){

                                                    $readOnly = ($login_fabrica != 52) ? "readOnly" : "";
                                                    $total_pendente = ($login_fabrica == 52) ? $qtde_pendente : $total_pendente;

                                                    echo "Qtde: <input type='text' size='2' name='qtde_a_cancelar_$i' class='frm' $readOnly value='".$total_pendente."'>&nbsp;&nbsp;";
                                                }else{
                                                    echo "Qtde: <input type='text' size='2' name='qtde_a_cancelar_$i' class='frm'>&nbsp;&nbsp;";
                                                }

                                                if(!in_array($login_fabrica, array(52))){
                                                    echo traduz("Motivo:")." <input type='text' name='motivo_$i' class='frm'>";
                                                    echo "<a style='cursor:pointer' type='button' onclick='if(confirm(\"".traduz("Deseja cancelar este item do pedido:")." ".$peca_descricao."?\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&os=$os&motivo=\"+$(\"input[name=motivo_$i]\").val()+\"&qtde_cancelar=\"+$(\"input[name=qtde_a_cancelar_$i]\").val()'>";
                                                    echo " <img src='../imagens/icone_deletar.png' style='padding: 0; margin: 0;width: 10px;'>".traduz("Cancelar")."</a>";
                                                }
                                            }
                                            echo "</td>";
                                    }else{
                                        echo "<td>&nbsp;</td>";
                                    }
                                }

                            } else if (((in_array($login_fabrica,$vet_fabrica_cancela)) and $itemPendente == 0)) {
                                echo "<td>&nbsp;</td>";
                            }
                        }else{
                            $total_pendente = ($total_pendente > 0) ? $total_pendente : "&nbsp;";
                            echo "<td style='text-align:center'>$total_pendente</td>";
                        }
                    } elseif (in_array($login_fabrica, array_merge($vet_fabrica_cancela,$vet_gama_salton_tele,array(52)))) {

                        $sqli = "SELECT tbl_pedido_item.qtde,
                                            tbl_pedido_item.qtde_faturada,
                                            tbl_pedido_item.qtde_faturada_distribuidor,
                                            tbl_pedido_item.qtde_cancelada,
                                            tbl_pedido_cancelado.os
                                    FROM tbl_pedido_item
                                    LEFT JOIN tbl_pedido_cancelado on tbl_pedido_item.pedido =tbl_pedido_cancelado.pedido and tbl_pedido_item.peca =tbl_pedido_cancelado.peca
                                    WHERE tbl_pedido_item.pedido_item = $pedido_item ";
                        $resi = pg_query($con, $sqli);
                        $total_pendente = 0 ;

                        if (pg_num_rows($resi) > 0) {

                            $qtde           = pg_fetch_result($resi, 0, 'qtde');
                            $qtde_cancelada = (strlen(pg_fetch_result($resi, 0, 'qtde_cancelada')) > 0) ? pg_fetch_result($resi, 0, 'qtde_cancelada') : 0;
                            $qtde_faturada  = (pg_fetch_result($resi, 0, 'qtde_faturada_distribuidor') > 0) ? pg_fetch_result($resi, 0, 'qtde_faturada_distribuidor') : pg_fetch_result($resi, 0, 'qtde_faturada');

                            if (stripos($tipo_pedido,"GARANTIA") !== false or strtoupper($tipo_pedido) == "CONSIGNADO"){
                                $os_cancelada           = pg_fetch_result($resi, 0, 'os');
                            }

                            if (in_array($login_fabrica, $vet_fabrica_cancela)) {
                                $total_pendente = $qtde - ($qtde_faturada + $qtde_cancelada);
                                $total_qtde_final = $qtde - $qtde_cancelada;
                            }
                        }
                        if ((strtolower($nf) == 'pendente' && pg_num_rows($resY) == 0 ) || ($qtde > ($qtde_faturada + $qtde_cancelada))) {
                            if ($nf_desc != "Cancelado" && (in_array($login_fabrica, array_merge($vet_fabrica_cancela)) && $total_pendente > 0) || (pg_num_rows($resY) == 0 && (in_array($login_fabrica, $vet_fabrica_cancela)))) {
                                if ( (in_array($login_fabrica,array(52)) and strlen($exportado)==0) ) {
                                    $mosta_form = "";
                                    if(strlen($os) > 0){
                                        if($os_cancelada != $os || $total_pendente > 0 ){
                                            $mosta_form = "sim";
                                        }
                                    } else {
                                        $mosta_form = "sim";
                                    }

                                    $peca_descricao = str_replace('"','',$peca_descricao);
                                    $peca_descricao = str_replace("'","",$peca_descricao);
                                    $total_qtde_final = $qtde - $qtde_cancelada;
                                    if(!empty($total_qtde_final) && !empty($mosta_form)){
                                            echo "<td align='left' nowrap>";

                                                echo traduz("Qtde") . "<input type='text' size='5' name='qtde_a_cancelar_$i' class='frm'>&nbsp;&nbsp;";
                                                echo traduz("Motivo:") . "<input type='text' name='motivo_$i' class='frm'>";
                                                echo "<a href='javascript: if(confirm(\"" . traduz("Deseja cancelar este item do pedido: ") . $peca_descricao ."?\")) window.location = \"$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&os=$os&motivo=\"+$(\"input[name=motivo_$i]\").val()+\"&qtde_cancelar=\"+$(\"input[name=qtde_a_cancelar_$i]\").val()'>";
                                                
                                                //echo " <img src='imagens/btn_x.gif'><font size='1'>Cancelar</font></a>";
                                                echo " <img src='../imagens/icone_deletar.png' style='padding: 0; margin: 0;width: 10px;'>".traduz("Cancelar")."</a>";
                                            echo "</td>";
                                    }else{
                                        echo "<td>&nbsp;</td>";
                                    }
                                }

                            } else if (((in_array($login_fabrica,$vet_fabrica_cancela)) and $itemPendente == 0)) {
                                echo "<td>&nbsp;</td>";
                            }
                        } else {
                            if($login_fabrica == 3){
                                echo "<td align='center'>$qtde</td>";
                            }
                            // if($login_fabrica <> 160){
                            //         echo "<td>&nbsp;</td>";
                            // }
                        }
                    } else {
                        if($login_fabrica == 81 and ($itemPendente != 0 or $total_pendente != 0)){
                            echo "<td>&nbsp;</td>";
                        }
                    }

                    if($login_fabrica == 81 and $itemPendente == 0 and $total_pendente == 0 and $condicao!="GARANTIA" and $condicao!="Garantia" and in_array($status_pedido,array(4,14))){
                        echo "<td align='center'>0</td>";
                    }

                    if(in_array($login_fabrica, array(40))){

                        $nota_fiscal = array();
                        $cond = "";
                        if (strlen($os) > 0) {
                            $cond = " AND tbl_os_produto.os = {$os}";
                        }
                        $sql_nota_fiscal = "SELECT
                                                DISTINCT tbl_faturamento.nota_fiscal,
                                                TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao
                                            FROM tbl_faturamento
                                            INNER JOIN tbl_faturamento_item  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item
                                            LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                            WHERE
                                                tbl_faturamento_item.pedido_item = {$pedido_item}
                                                AND tbl_faturamento.fabrica = {$login_fabrica}
                                                {$cond}
                                                AND tbl_faturamento_item.peca = {$peca}
                                                AND tbl_faturamento_item.pedido_item = {$pedido_item}";
                        //echo nl2br($sql_nota_fiscal); exit;
                        $res_nota_fiscal = pg_query($con, $sql_nota_fiscal);

                        if(pg_num_rows($res_nota_fiscal) > 0){
                            for($n = 0; $n < pg_num_rows($res_nota_fiscal); $n++){
                                $nota_fiscal[] = pg_fetch_result($res_nota_fiscal, $n, "nota_fiscal")."&nbsp; / &nbsp;".pg_fetch_result($res_nota_fiscal, $n, "emissao");
                            }
                            $nota_fiscal = implode("<br /> ", $nota_fiscal);
                        }else{
                            $nota_fiscal = "";
                        }
                        $condOs = "";
                        if (strlen($os) > 0) {
                            $condOs = " os = {$os} AND ";
                        }

                        /* Total de peças */
                        if (strlen($os) > 0) {
                            $sql_qtde = "SELECT SUM(qtde) AS qtde FROM tbl_os_item WHERE pedido_item = {$pedido_item} AND peca = {$peca} AND os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
                        } else {
                            $sql_qtde = "SELECT SUM(qtde) AS qtde FROM tbl_pedido_item WHERE pedido_item = {$pedido_item} AND peca = {$peca}";
                        }
                        $res_qtde = pg_query($sql_qtde);

                        if(pg_num_rows($res_qtde) > 0){
                            $qtde_total = (strlen(pg_fetch_result($res_qtde, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde, 0, "qtde") : 0;
                        }else{
                            $qtde_total = 0;
                        }

                        /* Faturadas */
                        $sql_qtde_faturada = "SELECT SUM(qtde) AS qtde FROM tbl_faturamento_item WHERE {$condOs} pedido_item = {$pedido_item} AND peca = {$peca}";
                        $res_qtde_faturada = pg_query($con, $sql_qtde_faturada);

                        if(pg_num_rows($res_qtde_faturada) > 0){
                            $qtde_faturada =  (strlen(pg_fetch_result($res_qtde_faturada, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde_faturada, 0, "qtde") : 0;
                        }else{
                            $qtde_faturada = 0;
                        }

                        /* Canceladas */
                        $sql_qtde_cancelada = "SELECT SUM(qtde) AS qtde FROM tbl_pedido_cancelado WHERE {$condOs} fabrica = {$login_fabrica} AND pedido_item = {$pedido_item} AND peca = {$peca}";
                        $res_qtde_cancelada = pg_query($con, $sql_qtde_cancelada);

                        if(pg_num_rows($res_qtde_cancelada) > 0){
                            $qtde_cancelada = (strlen(pg_fetch_result($res_qtde_cancelada, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde_cancelada, 0, "qtde") : 0;
                        }else{
                            $qtde_cancelada = 0;
                        }

                        $pendente = $qtde_total;

                        if($qtde_faturada > 0){
                            $pendente = $qtde_total - $qtde_faturada;
                        }else{
                            $nota_fiscal = "";
                        }

                        if($qtde_cancelada > 0){
                            $pendente = $qtde_total - $qtde_cancelada;
                        }

                        if($qtde_faturada > 0 && $qtde_cancelada > 0){
                            $pendente = $qtde_total - ($qtde_faturada + $qtde_cancelada);
                        }

                         //echo "Total: $qtde_total Faturadas: $qtde_faturada Canceladas: $qtde_cancelada Pendentes: $pendente Faturamento: $faturamento Faturamento Item: $faturamento_item Pedido: $pedido Pedido Item: $pedido_item OS: $os<br />";

                        echo "<td align='center'><strong>{$qtde_total}</strong></td>";
                        echo "<td align='center'>{$qtde_faturada}</td>";
                        echo "<td align='center'>{$qtde_cancelada}</td>";
                        echo "<td align='center'>{$pendente}</td>";
                        echo "<td align='center'>{$nota_fiscal}</td>";
                    }
                    if (in_array($login_fabrica, [35])) {
                        $sqlValida = "  SELECT  nota_fiscal,
                                                conhecimento,
                                                TO_CHAR(emissao,'DD/MM/YYYY') as emissao,
                                                peca,
                                                referencia,
                                                descricao
                                        FROM tbl_faturamento_item
                                        JOIN tbl_faturamento USING (faturamento)
                                        JOIN tbl_peca USING (peca)
                                        WHERE pedido_item = {$pedido_item}
                                            AND peca != {$peca}";
                        $resValida = pg_query($con, $sqlValida);
                        if (pg_num_rows($resValida) > 0) {
                            if (strlen($os) > 0) {
                                foreach (pg_fetch_all($resValida) as $peca) {
                                    echo "<tr style='background-color: #FAFF73'>";
                                    echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os={$os}' target='_new'>{$os}</a></font></td>";
                                    echo "<td align='center'>{$peca['nota_fiscal']}</td>";
                                    echo "<td style='text-align:center;'><a href='{$peca['conhecimento']}'>Rastreio Pedido</a></td>";
                                    echo "<td align='center'>{$peca['emissao']}</td>";
                                    echo "<td>{$peca['referencia']}-{$peca['descricao']}</td>";
                                    echo "</tr>";
                                }
                            }else{
                                foreach (pg_fetch_all($resValida) as $peca) {
                                    echo "<tr style='background-color: #FAFF73'>";
                                    echo "<td>{$peca['nota_fiscal']}</td>";
                                    echo "<td style='text-align:center;'><a href='{$peca['conhecimento']}'>Rastreio Pedido</a></td>";
                                    echo "<td>{$peca['emissao']}</td>";
                                    echo "<td>{$peca['referencia']}-{$peca['descricao']}</td>";
                                    echo "<td><b>Peça Original: {$peca_descricao}<b></td>";
                                    echo "</tr>";
                                }
                            }
                        }
                    }
                }

                echo "</tr>";
                echo "</table>";
            }
        }
        if (in_array($login_fabrica, [35])) {
            echo "<br><font class='detalheFeturamento' data-shadowPedido='{$pedido}'>" . traduz("Ver Faturamento Detalhado") ." </font>";
        }
        echo "<br />";

             if($login_fabrica == 101){
            echo "<input type='button' value='" . traduz("Imprimir") . "' onclick='window.print();'> <br /> <br />";
        }
        if (in_array($login_fabrica, array(52))) {
            $mostrou_motivo = false;
        }
        if (((in_array($login_fabrica, array(6,11,45,46,52,74,81,88,95,98,99,101,106,108,111,114,115,116,117,120,201,121,122))  || ($login_fabrica >= 123 && $login_fabrica != 143) || ($login_fabrica == 104 && $tipo_pedido != "GARANTIA") ) && !in_array($status_pedido, array(4,13, 14,31))) || ($login_fabrica == 143 && in_array($status_pedido, array(24,18)))) {
            $mostrou_motivo = true;

            if ($login_fabrica == 175){
                if ($status_pedido == 1){
                    echo "<span style='font-size:12px;'>" . traduz("Motivo:") . "</span>&nbsp;<input type='text' name='motivo_tudo' id='motivo_tudo' size='90' class='frm'> <br /><br>";
                    echo "<input type='button' value='Cancelar Tudo' onclick='cancelaTudo($pedido, \"\")'>";
                }
            }else{
                echo "<span style='font-size:12px;'>Motivo:</span>&nbsp;<input type='text' name='motivo_tudo' id='motivo_tudo' size='90' class='frm'> <br /><br>";
                echo "<input type='button' value='" . traduz("Cancelar Tudo") . "' onclick='cancelaTudo($pedido, \"\")'>";
            }
   
            if(in_array($login_fabrica, array(11,172))){
                echo "<input type='button' value='" . traduz("Cancelado para Troca") . "' onclick='cancelaTudo($pedido,\"enviado para troca\")'>";
        } else if (in_array($login_fabrica, array(6,11,45,46,88,95,98,99,101,106,108,111,115,116,117,120,201,121,122))  or $login_fabrica >= 123) {
            echo '&nbsp;';
        }  
        if (in_array($login_fabrica, array(52))) {
            if (!in_array($status_pedido, array(4, 14))) {
                if (!$mostrou_motivo) {
                    echo "<span style='font-size:12px;'>" . traduz("Motivo:") . "</span>&nbsp;<input type='text' name='motivo_tudo' id='motivo_tudo' size='90' class='frm'> <br /><br>";
                }
                echo '&nbsp;&nbsp;<input type="button" value="' . traduz("Cancelar Selecionados") .'" onclick="if ($(\'input[name=cancelar_selecao]\').val() == \'\') { $(\'input[name=cancelar_selecao]\').val(\'sim\'); $(\'form[name=frm_cancelar_selecao]\').submit(); } else { alert(\'' . traduz("Aguarde o envio...") . '\');}" />';
            }
            echo "</form>";
        }

        if($login_fabrica == 74){
            $sqlObs = "SELECT obs FROM tbl_pedido_item WHERE pedido = $pedido AND obs notnull";
            $resObs = pg_query($con,$sqlObs);
            if(pg_num_rows($resObs) > 0){
                echo "<br><br><table align='center' class='tabela' width='700'>";
                echo "<caption class='titulo_tabela'>" . traduz("Notas Fiscais canceladas") . "</caption>";
                echo "<tr class='titulo_coluna'>";
                echo "<th>" . traduz("Nota Fiscal") . "</th>";
                echo "<th>" . traduz("Peça") . "</th>";
                echo "<th>" . traduz("Data Cancelamento") . "</th>";
                echo "</tr>";

                for($z = 0; $z < pg_num_rows($resObs); $z++){
                    $obs = pg_fetch_result($resObs, $z, 'obs');
                    list($nf_obs,$peca_obs,$data_obs) = explode('|',$obs);
                    $cor = ($z % 2 ) ? '#F1F4FA' : "#F7F5F0";

                    echo "<tr bgcolor='$cor'>";
                    echo "<td align='center'>$nf_obs</td>";
                    echo "<td>$peca_obs</td>";
                    echo "<td align='center'>$data_obs</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }

        /* ------------ Posição do Pedidos ------------------- */
        $mostrar_pendencia = 0;

        #Chamado 10028
        #Retirado HD-6283623, anexo Interação 73
        if ($login_fabrica == 1 && 1==2) {

            $sql = "SELECT  tbl_pedido_item.qtde         ,
                            tbl_pedido_item.qtde_faturada,
                            tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada) AS qtde_pendente,
                            tbl_peca.peca                ,
                            tbl_peca.referencia          ,
                            tbl_peca.descricao           ,
                            tbl_peca.parametros_adicionais
                    FROM    tbl_pedido
                    JOIN    tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_pedido.pedido
                    JOIN    tbl_peca             ON tbl_peca.peca          = tbl_pedido_item.peca
                    WHERE   tbl_pedido.pedido = $pedido
                    ORDER   BY  qtde_pendente DESC, tbl_pedido_item.pedido_item";

            $res = pg_query ($con,$sql);

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                if ($i == 0) {

                    if($login_fabrica == 1){
                        $cols = "5";
                    }else{
                        $cols = "4";
                    }

                    echo "<br>";
                    echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='tabela' >";
                    echo "<thead>";
                        echo "<tr class='titulo_tabela'>";
                            echo "<td align='center' colspan='$cols'><b>" . traduz("Posição deste pedido") . "</b></td>";
                        echo "</tr>";
                        echo "<tr class='titulo_coluna'>";
                            echo "<td align='left'>" . traduz("Componente") . "</td>";
                            echo "<td>" . traduz("Qtde") . "<br>" . traduz("Pedida") . "</td>";
                            echo "<td>" . traduz("Qtde") . "<br>" . traduz("Faturada") . "</td>";
                            echo "<td>" . traduz("Qtde") . "<br>" . traduz("Pendente") . "</td>";

                            if($login_fabrica == 1){
                                echo "<td>" . traduz("Previsão") . "</td>";
                            }

                        echo "</tr>";
                    echo "</thead>";

                }

                if($login_fabrica == 1){

                    $parametros_adicionais = pg_fetch_result($res,$i,parametros_adicionais);
                    $parametros_adicionais = json_decode($parametros_adicionais,true);

                    $qtde_faturada = pg_result($res,$i,qtde_faturada);
                    $qtde          = pg_result($res,$i,qtde);

                    $estoque    = $parametros_adicionais['estoque'];
                    $previsao   = $parametros_adicionais['previsao'];

                    $estoque    = ucfirst($parametros_adicionais["estoque"]);
                    $previsao   = mostra_data($parametros_adicionais["previsao"]);
					$estoque = strtoupper($estoque);
					if($estoque == "DISPONIVEL" or $estoque == "DISPONÍVEL"){
                        if($qtde_faturada == $qtde){
                            $previsao = "Faturada";
                        }else{
                            $previsao = "<font face='arial' size='-2'>$estoque </font>";
                        }
                    }
                }

                $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

                echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal' >";
                echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
                echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
                echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde_faturada) . "</td>";
                //if ($mostrar_pendencia == 1){
                    echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde_pendente) . "</td>";
                    if($login_fabrica == 1){
                        echo "<td align='right'>$previsao</td>";
                    }
                //}
                echo "</tr>";
            }
            echo "</table>";

            echo "<br>";

            # Chamado 10028
            /* EMBARQUES */
            $sql = "SELECT      tbl_pendencia_bd_novo_nf.pedido,
                                tbl_pendencia_bd_novo_nf.referencia_peca,
                                TO_CHAR(tbl_pendencia_bd_novo_nf.data,'DD/MM/YYYY') as data,
                                tbl_pendencia_bd_novo_nf.qtde_embarcada,
                                tbl_pendencia_bd_novo_nf.nota_fiscal,
                                tbl_pendencia_bd_novo_nf.transportadora_nome,
                                tbl_pendencia_bd_novo_nf.conhecimento
                            FROM tbl_pendencia_bd_novo_nf
                            WHERE posto  = '$posto'
                            AND   pedido = '$pedido'
                            ORDER BY pedido,tbl_pendencia_bd_novo_nf.data DESC
                        ";
            $res = pg_query($con,$sql);
            $resultado = pg_num_rows($res);

            for ($i = 0 ; $i < $resultado ; $i++) {

                if ($i == 0) {

                    echo "<table width='700' cellpadding='2' cellspacing='1'   align='center' class='tabela' >";
                    echo "<tr class='titulo_tabela >";
                    echo "<td colspan='7'>" . traduz("Embarques") . "</td>";
                    echo "</tr>";
                    echo "<tr class='titulo_coluna'>";
                    echo "<td align='left'>" . traduz("Componente") . "</td>";
                    echo "<td>" . traduz("Data") . "</td>";
                    echo "<td>" . traduz("Qtde") . "<br>" . traduz("Embarcada") . "</td>";
                    echo "<td>" . traduz("Nota") . "<br>" . traduz("Fiscal") . "</td>";
                    echo "<td>" . traduz("Transportadora") . "</td>";
                    echo "<td>" . traduz("Nº Objeto") . "</td>";
                    echo "</tr>";
                }

                $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

                $peca                   =  pg_fetch_result($res,$i,referencia_peca);
                $data                   =  pg_fetch_result($res,$i,data);
                $qtde_embarcada         =  pg_fetch_result($res,$i,qtde_embarcada);
                $nota_fiscal            =  pg_fetch_result($res,$i,nota_fiscal);
                $transportadora_nome    =  pg_fetch_result($res,$i,transportadora_nome);
                $conhecimento           =  pg_fetch_result($res,$i,conhecimento);

                $conhecimento = strtoupper($conhecimento);
                $conhecimento = str_replace("-","",$conhecimento);
                $conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

                echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
                echo "<td nowrap>$peca</td>";
                echo "<td align='center'>$data</td>";
                echo "<td align='center'>$qtde_embarcada</td>";
                echo "<td align='center'>$nota_fiscal</td>";
                echo "<td align='left'>$transportadora_nome</td>";
                echo "<td align='right'>$conhecimento</td>";
                echo "</tr>";

            }

            echo "</table>";
            /*echo "<br><br>";
            if($login_fabrica == 1){
            echo "<table width='700' cellpadding='2' cellspacing='1' align='center'>";
                echo "<tr>";
                    echo "<td align='center' bgcolor='#f4f4f4'><p align='center'>
                        <font size='1'><b> A previsão informada refere-se a disponibilidade da peça na fábrica. Para entrega é necessário considerar o prazo de envio de acordo com sua região. <Br> Previsão sujeita a alteração.</b></font></p>
                    </td>";
                echo "</tr>";
            echo "</table>";
            }*/

            //hd 14024 25/2/2008
            /*MOSTRAR AS NOTAS FISCAIS DAS ORDENS PROGRAMADAS*/

            $sql = "SELECT      tbl_ordem_programada_pedido_black.pedido,
                                tbl_ordem_programada_pedido_black.peca_referencia,
                                tbl_peca.descricao,
                                tbl_ordem_programada_pedido_black.qtde_faturada_ped,
                                tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada AS qtde_pendente,
                                tbl_ordem_programada_pedido_black.nota_fiscal,
                                TO_CHAR(tbl_ordem_programada_pedido_black.data_nota,'DD/MM/YYYY') as data_nota,
                                tbl_ordem_programada_pedido_black.transportadora_nome,
                                tbl_ordem_programada_pedido_black.ar as conhecimento
                            FROM tbl_ordem_programada_pedido_black
                            JOIN tbl_peca using(peca)
                            JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_ordem_programada_pedido_black.pedido
                            AND tbl_pedido_item.peca = tbl_ordem_programada_pedido_black.peca
                            WHERE tbl_ordem_programada_pedido_black.pedido = '$pedido'
                            ORDER BY tbl_ordem_programada_pedido_black.pedido,tbl_ordem_programada_pedido_black.pedido_data, qtde_pendente DESC";

            $res       = pg_query($con,$sql);
            $resultado = pg_num_rows($res);

            for ($i = 0; $i < $resultado; $i++) {

                if ($i == 0) {

                    echo "<table width='700' align='center' border='0' cellspacing='3'>";
                        echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
                            echo "<td colspan='8'>" . traduz("Embarques") . "</td>";
                        echo "</tr>";
                        echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
                            echo "<td align='left'>" . traduz("Componente") . "</td>";
                            echo "<td align='left'>" . traduz("Descricao") . "</td>";
                            echo "<td>" . traduz("Qtde") . "<br>" .  traduz("Embarcada") . "</td>";
                            echo "<td>" . traduz("Nota Fiscal") . "</td>";
                            echo "<td>" . traduz("Data Nota") . "</td>";
                            echo "<td>" . traduz("Transportadora") . "</td>";
                            echo "<td>" . traduz("Nº Objeto") . "</td>";
                        echo "</tr>";

                }

                $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

                $peca                   =  pg_fetch_result($res, $i, 'peca_referencia');
                $peca_descricao         =  pg_fetch_result($res, $i, 'descricao');
                $qtde_faturada_ped      =  pg_fetch_result($res, $i, 'qtde_faturada_ped');
                $qtde_pendente          =  pg_fetch_result($res, $i, 'qtde_pendente');
                $nota_fiscal            =  pg_fetch_result($res, $i, 'nota_fiscal');
                $data_nota              =  pg_fetch_result($res, $i, 'data_nota');
                $transportadora_nome    =  pg_fetch_result($res, $i, 'transportadora_nome');
                $conhecimento           =  pg_fetch_result($res, $i, 'conhecimento');

                $conhecimento = strtoupper($conhecimento);
                $conhecimento = str_replace("-","",$conhecimento);
                $conhecimento = "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=".$conhecimento."BR' target='_blank'>$conhecimento</a>";

                echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
                    echo "<td nowrap>$peca</td>";
                    echo "<td nowrap>$peca_descricao</td>";
                    echo "<td align='center'>$qtde_faturada_ped</td>";
                    echo "<td align='center'>$nota_fiscal</td>";
                    echo "<td align='center'>$data_nota</td>";
                    echo "<td align='left'>$transportadora_nome</td>";
                    echo "<td align='right'>$conhecimento</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "<br>";

        }

    } ?>
    </td>

    <td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr><?php
echo "<tr><td ><br>";
    if (strlen($data_exportado) == 0 AND ($login_admin == 232 OR $login_admin == 112) AND $pedido_sedex <> 't') {
        $chave = md5($pedido);
        echo "<INPUT TYPE='submit' onclick=\"if (confirm('" . traduz("Deseja realmente transformar o Pedido nº") . $pedido_blackedecker . traduz("em Pedido Sedex ?") . "') == true) { window.location='$PHP_SELF?sedex=$pedido&pedido=$pedido&key=$chave'; }\" value='" . traduz("Transformar em Pedido Sedex") ."'>";
    }

echo "</td></tr>";?>

</form>

</table>

<?php

if(in_array($login_fabrica, array(138))){

    include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
    $tDocs   = new TDocs($con, $login_fabrica);

?>
    <br />

    <!-- ANexo -->
    <table class="tabela" align="center" width="700" cellspacing="1" cellpadding="6">
        <tr class="titulo_coluna">
            <td><?php echo traduz("Boleto Bancário"); ?> </td>
        </tr>
        <tr>
            <td align="center">
                <?php

                $fabrica_qtde_anexos = 1;

                if ($fabrica_qtde_anexos > 0) {

                    echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

                    $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;

                    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                        unset($anexo_link);

                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3          = false;
                        $anexo             = "";

                        if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {
                                    if ($countDocs != $i) {
                                        continue;
                                    }

                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);
									$ext_item = strtolower($ext_item);
                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }

                                    $anexo_item_link = $anexo['link'];
                                    $countDocs++;

                                }

                                $anexo        = basename($anexos[0]);
                                $anexo_s3     = true;
                            }
                        }
                        ?>

                        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                            <?php if (isset($anexo_item_link)) { ?>
                                <a href="<?=$anexo_item_link?>" target="_blank" >
                            <?php } ?>

                            <img src="<?=$anexo_item_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                            <br />

                            <?php if (isset($anexo_item_link)) { ?>
                                </a>
                                <script>setupZoom();</script>
                            <?php } ?>

                            <?php
                            if ($anexo_s3 === false) {
                            ?>
                                <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" ><?php echo traduz("Anexar"); ?></button>
                            <?php
                            }
                            ?>

                            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                            <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                            <?php
                            if ($anexo_s3 === true) {?>
                                <!--<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button> -->
                                <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')"><?php echo traduz("Visualizar boleto"); ?></button>

                            <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                }
                ?>
            </td>
        </tr>
    </table>
    <!-- Fim anexo-->

<?php
}
?>

<?php
if(in_array($login_fabrica, array(163))){

    include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
    $tDocs   = new TDocs($con, $login_fabrica);

?>
    <!-- ANexo -->
    <table align="center">
        <tr>
            <td align="center">
                <?php

                $fabrica_qtde_anexos = 1;

                if ($fabrica_qtde_anexos > 0) {

                    echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

                    $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;

                    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                        unset($anexo_link);

                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3          = false;
                        $anexo             = "";

                        if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {
                                    if ($countDocs != $i) {
                                        continue;
                                    }

                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }

                                    $anexo_item_link = $anexo['link'];
                                    $countDocs++;

                                }

                                $anexo        = basename($anexos[0]);
                                $anexo_s3     = true;
                            }
                        }
                        ?>

                        <br />

                        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                            <?php if (isset($anexo_item_link)) { ?>
                                <a href="<?=$anexo_item_link?>" target="_blank" >
                            <?php } ?>

                            <img src="<?=$anexo_item_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                            <br />

                            <?php if (isset($anexo_item_link)) { ?>
                                </a>
                                <script>setupZoom();</script>
                            <?php } ?>

                            <?php
                            if ($anexo_s3 === false) {
                            ?>
                                <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" ><?php echo traduz("Anexar"); ?></button>
                            <?php
                            }
                            ?>

                            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                            <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                            <?php
                            if ($anexo_s3 === true) {?>
                                <!--<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button> -->
                                <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')"><?php echo traduz("Baixar"); ?></button>

                            <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                }
                ?>
            </td>
        </tr>
    </table>
    <!-- Fim anexo-->

<?php
}
?>

<?php

if ($login_fabrica == 51 OR $login_fabrica == 10) { //  HD 46988

    $sql = "SELECT tbl_peca.referencia         ,
                tbl_peca.descricao             ,
                tbl_pedido_item.qtde           ,
                tbl_pedido_item.qtde_faturada  ,
                tbl_pedido_item.qtde_cancelada ,
                tbl_pedido_item.pedido_item    ,
                tbl_pedido_item.peca           ,
                tbl_pedido_item.qtde_faturada_distribuidor
            FROM  tbl_pedido
            JOIN  tbl_pedido_item USING (pedido)
            JOIN  tbl_peca        USING (peca)
            JOIN  tbl_tipo_pedido USING (tipo_pedido)
            WHERE tbl_pedido_item.pedido = $pedido
            AND   tbl_tipo_pedido.codigo = 'FAT'
            AND   tbl_pedido.fabrica     = $login_fabrica
            AND   (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor) < tbl_pedido_item.qtde
            ORDER BY tbl_peca.descricao;";

        $res = pg_query ($con,$sql);

        if (pg_num_rows($res) > 0){
            echo "<br>";
            echo "<table width='450' border='0' cellspacing='1' cellpadding='3' align='center'>";
            echo "<caption>". traduz("Baixar Pendências") . "</caption>";
            echo "<tr height='20' class='menu_top'>";
                echo "<td>". traduz("Componente") . "</td>";
                echo "<td align='center'>". traduz("Qtde") . "</td>";
                echo "<td align='center'>". traduz("Qtde Faturada") . "</td>";
                echo "<td align='center' style='font-size:9px'>". traduz("Pendência<br>do Pedido") . "</td>";
                echo "<td align='center' style='font-size:9px'>". traduz("Qtde a cancelar") . "</td>";
                echo "<td>". traduz("Ação") . "</td>";
            echo "</tr>";

            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

                $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

                $peca_descricao             = pg_fetch_result($res, $i, 'referencia') . " - " . pg_fetch_result ($res, $i, 'descricao');
                $qtde                       = pg_fetch_result($res, $i, 'qtde');
                $qtde_faturada              = pg_fetch_result($res, $i, 'qtde_faturada');
                $qtde_cancelada             = pg_fetch_result($res, $i, 'qtde_cancelada');
                $pedido_item                = pg_fetch_result($res, $i, 'pedido_item');
                $peca                       = pg_fetch_result($res, $i, 'peca');
                $qtde_faturada_distribuidor = pg_fetch_result($res, $i, 'qtde_faturada_distribuidor');

                if ($distribuidor == 4311) {
                    $qtde_faturada = $qtde_faturada_distribuidor;
                }

                $total_faturada += $qtde_faturada;

                echo "<tr bgcolor='$cor' class='table_line1'>";
                echo "<td align='left'>{$peca_descricao}</td>";
                echo "<td align='right'>{$qtde}</td>";
                echo "<td align='right'>{$qtde_faturada}</td>";
                if($login_fabrica == 101){
                    echo "<td align='right'>$localizacao_estoque</td>";
                }
                $qtde_pendente = $qtde - $qtde_faturada - $qtde_cancelada;
                echo "<td class='table_line1_pendencia' align='right'>";
                echo ($qtde_pendente == 0 OR strlen($qtde_pendente) == 0) ? "&nbsp;" : $qtde_pendente;
                echo "</td>";
                echo "<td align='right'>";
                echo "<input type=text name='qtde_a_cancelar' size =3 value='' id='qtde_cancelar_$i'>";
                echo "</td>";
                echo "<td align='center'>";

                if ($qtde > $qtde_faturada AND in_array($login_admin, $lista_de_admin_altera_pedido)) {
                    echo "Motivo:<br> <input type='text' id='motivo_cancelamento_item_$i' class='frm' size='10'>";
                    echo "<a href=\"javascript: if(confirm('" . traduz("Deseja cancelar este item do pedido:") ." $peca_descricao?')) window.location = '$PHP_SELF?cancelar=$pedido_item&pedido=$pedido&peca=$peca&motivo='+document.getElementById('motivo_cancelamento_item_$i').value+'&qtde_cancelar='+document.getElementById('qtde_cancelar_$i').value\">";
                    echo " <img src='imagens/btn_x.gif'><font size='1'>" . traduz("Cancelar") . "</font></a>";
                }

                echo "</td>";
                echo "</tr>";

            }

            echo "<tr>";
                echo "<td></td>";
                echo "<td bgcolor='#cccccc' align='right' colspan='6' nowrap></td>";
            echo "</tr>";
            echo "</table><br><br>";
        }

    if($condicao!="GARANTIA" and $condicao!="Garantia"){
        $sql = "SELECT distinct tbl_faturamento.faturamento ,
                        tbl_faturamento.nota_fiscal ,
                        tbl_faturamento.transp ,
                        TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        tbl_faturamento.conhecimento,
                        tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.peca ,
                        tbl_faturamento_item.qtde ,
                        tbl_peca.peca ,
                        tbl_peca.referencia ,
                        tbl_peca.descricao
                FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
                JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica in ( $login_fabrica, 10)
                JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
                ORDER   BY tbl_peca.descricao";

        $res = pg_query ($con,$sql);

        if (pg_num_rows($res) > 0) {

            //echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Notas Fiscais que atenderam a este pedido</h2>";
            echo "<table width='700' align='center' border='0' cellspacing='3' class='tabela'>";
            echo "<tr class='titulo_tabela'><td colspan='100%'>" . traduz("Notas Fiscais que atenderam a este pedido") . "<td></tr>";
            echo "<tr class='titulo_coluna' >";
                echo "<td>" . traduz("Nota Fiscal") . "</td>";
                if($login_fabrica==157){
                    echo "<td>" . traduz("Transportadora") . "</td>";
                }
                echo "<td>" . traduz("Data") . "</td>";
                echo "<td>" . traduz("Peça") . "</td>";
                echo "<td>" . traduz("Qtde") . "</td>";
            echo "</tr>";

            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

                echo "<tr bgcolor='$cor'>";
                    echo "<td>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
                  if($login_fabrica==157){
                    echo "<td>" . pg_fetch_result ($res,$i,transp) . "</td>";
                  }
                    echo "<td>" . pg_fetch_result ($res,$i,emissao) . "</td>";
                    echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
                    echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
                echo "</tr>";

            }

            echo "</table>";

        }
    }

}


if (in_array($login_fabrica,array(3,6,11,14,15,24,43,94,95,98,101,172))) {//HD 404932

    if (in_array($login_fabrica,array(6,11,46,94,95,98,99,101,104,105,108,111,115,116,117,120,201,121,122))  or $login_fabrica >= 123) {

        $cond_os = " LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os ";

        if($login_fabrica == 99 OR $login_fabrica == 101){

            $cond_left = " LEFT JOIN tbl_os_item ON (tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item and tbl_os_item.peca = tbl_faturamento_item.peca) $cond_faturamento";

            if($login_fabrica == 99){
                $cond_faturamento = " AND tbl_faturamento_item.os = tbl_os.os";
                if(strtolower($tipo_pedido) == "garantia"){
                    $cond_os = " JOIN tbl_os ON tbl_os.os = tbl_os_produto.os $cond_faturamento";
                }
            }

        }else{
            $cond_left = " LEFT JOIN tbl_os_item ON (tbl_os_item.pedido = tbl_pedido.pedido and (tbl_os_item.peca = tbl_faturamento_item.peca or tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item)) ";
        }



        $mostra_os_campo = "tbl_os.sua_os,tbl_os.os, tbl_faturamento_item.qtde as qtde,";
        $mostra_os_join  = "JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido AND tbl_pedido.fabrica = $login_fabrica
                            $cond_left
                            LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            $cond_os";

    } else {

        $mostra_os_campo = "tbl_faturamento_item.qtde,";
        $mostra_os_join  = "";

    }



    if ($login_fabrica == 101) {
        $sql = "SELECT
                    tbl_os.sua_os,
                    tbl_os.os,
                    tbl_faturamento.nota_fiscal,
                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    sum(tbl_faturamento_item.qtde) as qtde
                FROM tbl_faturamento_item
                JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                AND tbl_faturamento.fabrica = {$login_fabrica}
                JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
                JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os
                WHERE tbl_faturamento_item.pedido = {$pedido}
                GROUP BY tbl_peca.descricao,
                tbl_os.sua_os,
                tbl_os.os,
                tbl_faturamento.nota_fiscal,
                tbl_faturamento.emissao,
                tbl_peca.referencia
                ORDER BY tbl_peca.descricao ";
    } else {
        $sql = "SELECT DISTINCT tbl_faturamento.nota_fiscal ,
                    $mostra_os_campo
                    TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                    tbl_peca.referencia ,
                    tbl_peca.descricao
                FROM tbl_faturamento_item
                JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
                JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
                $mostra_os_join
                WHERE tbl_faturamento_item.pedido = $pedido
                ORDER BY tbl_peca.descricao";
    }

    $res = pg_query ($con,$sql);
    //echo pg_last_error();
    //echo nl2br($sql);

    if (pg_num_rows ($res) > 0 AND $login_fabrica <> 3) { //HD-2890050 em conversa com ronaldo o mesmo solicitou para remover essa tabela para Britânia.

        //echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Notas Fiscais 1que atenderam a este pedido</h2>";
        echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>";
        echo "<caption class='titulo_tabela'>" . traduz("Notas Fiscais que atenderam a este pedido") . "</caption>";
        echo "<tr class='titulo_coluna'>";

            if (in_array($login_fabrica,array(6,11,46,94,95,98,99,101,104,105,108,111,115,116,117,120,201,121,122))  or $login_fabrica >= 123) {

                echo "<td>Sua OS</td>";
            }
            echo "<td>" . traduz("Nota Fiscal") . "</td>";
            echo "<td>" . traduz("Data") . "</td>";
            echo "<td>" . traduz("Peça") . "</td>";
            echo "<td>" . traduz("Qtde") . "</td>";
        echo "</tr>";

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

            echo "<tr bgcolor='$cor'>";

                if (in_array($login_fabrica,array(6,11,46,94,95,98,99,101,104,105,108,111,115,116,117,120,201,121,122))  or $login_fabrica >= 123) {

                    echo "<td><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=" . pg_fetch_result ($res,$i,os) . "' target='_new'>" . pg_fetch_result ($res,$i,sua_os) . "</a></td>";
                }
                echo "<td>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
                echo "<td>" . pg_fetch_result ($res,$i,emissao) . "</td>";
                echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
                echo "<td align='center'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
            echo "</tr>";

        }

        echo "</table>";
        echo "<br><br>";

    }

    $sql = "SELECT  distinct
                    TO_CHAR(data_log,'DD/MM/YYYY') AS data,
                    TO_CHAR(data_exportacao,'DD/MM/YYYY') AS data_exportado,
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    mensagem          ,
                    tbl_pedido_log_exportacao.peca
            FROM    tbl_pedido_log_exportacao
            LEFT JOIN    tbl_peca ON tbl_peca.peca = tbl_pedido_log_exportacao.peca
            WHERE pedido = $pedido
            ORDER   BY data_exportado";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {

        $peca = pg_fetch_result($res,0,peca);

        if (strlen($peca) > 0 AND strlen($pedido) > 0) {

            $sqlx = "SELECT tbl_os.os, sua_os
                     FROM tbl_os
                     JOIN tbl_os_produto USING(os)
                     JOIN tbl_os_item    USING(os_produto)
                     WHERE tbl_os_item.pedido = $pedido
                     AND   tbl_os_item.peca   = $peca";

            $resx  = pg_query($con, $sqlx);

            if (pg_num_rows($resx) > 0) {

                $os     = pg_fetch_result($resx, 0, 'os');
                $sua_os = pg_fetch_result($resx, 0, 'sua_os');

            }

        }

        echo "<h2 style='font-size:12px ; color:#000000 ; text-align:center ' >Recusas da Fabrica</h2>";
        echo "<table width='600' align='center' border='0' cellspacing='1' class='tabela'>";
        echo "<tr class='titulo_coluna' >";
            echo "<td>" . traduz("Data Log") . "</td>";
            echo "<td nowrap>" . traduz("Envio p/ Fábrica") . "</td>";
            echo "<td nowrap><acronym title='OSs que geraram a solicitação'>" . traduz("OS") . "</acronym></td>";
            echo "<td>" . traduz("Peça") . "</td>";
            echo "<td>" . traduz("Motivo") . "</td>";
        echo "</tr>";

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

            echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
                echo "<td>" . pg_fetch_result ($res,$i,data) . "</td>";
                echo "<td>" . pg_fetch_result ($res,$i,data_exportado) . "</td>";
                echo "<td><A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
                echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) ." - ". pg_fetch_result ($res,$i,descricao) . "</td>";
                echo "<td nowrap>" . pg_fetch_result ($res,$i,mensagem) . "</td>";
            echo "</tr>";

        }

        echo "</table>";

    }

}

//echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Pedidos cancelados que pertencem a este pedido</h2>";
echo "<br>";

// Historico de Intervenção de Pedido HD-4402121
if ((in_array($login_fabrica,[160, 186]) or $replica_einhell or $telecontrol_distrib or $interno_telecontrol)) {

    $sqlAudit = "SELECT  tbl_pedido_status.data,
                         tbl_pedido_status.status,
                         tbl_pedido_status.observacao,
                         tbl_status_pedido.descricao,
                         tbl_pedido_status.admin
                FROM     tbl_pedido
                JOIN     tbl_pedido_status ON tbl_pedido.pedido        = tbl_pedido_status.pedido
                JOIN     tbl_status_pedido ON tbl_pedido_status.status = tbl_status_pedido.status_pedido
                WHERE    tbl_pedido.pedido  = $pedido
                AND      tbl_pedido.fabrica = $login_fabrica
                ORDER BY tbl_pedido_status.data DESC";
    $resAudit = pg_query($con, $sqlAudit);

    if(pg_num_rows($resAudit) > 0){

        echo "<table width='700' cellpadding='2' cellspacing='1' align='center' class='tabela' >";
        echo "<thead>";
        echo "<caption class='titulo_tabela'>";
        echo "Histórico de Status Pedido";
        echo "</caption>";
        echo "</thead>";
        echo "<tr class='titulo_coluna'>";
        echo "<td align='center'><b>" . traduz("Data") . "</b></td>";
        echo "<td align='center'><b>" . traduz("Observação") . "</b></td>";
        echo "<td align='center'><b>" . traduz("Status") . "</b></td>";
        echo "<td align='center'><b>" . traduz("Admin") . "</b></td>";
        echo "</tr>";   

        for ($a=0; $a < pg_num_rows($resAudit); $a++) {
            $status_data    = pg_fetch_result($resAudit, $a, 'data');
            $status_obs     = mb_check_encoding(pg_fetch_result($resAudit, $a, 'observacao'), 'UTF-8')
            				? utf8_decode(pg_fetch_result($resAudit, $a, 'observacao')) : pg_fetch_result($resAudit, $a, 'observacao');

            $status_desc    = pg_fetch_result($resAudit, $a, 'descricao');
            $admin          = pg_fetch_result($resAudit, $a, 'admin');
            $status_admin   = "";

            $newDate = date("d-m-Y", strtotime($status_data));
            $status_data = str_replace("-", "/", $newDate);

            if (!empty($admin)){
                $sql_adm = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
                $res_adm      = pg_query($con, $sql_adm);
                $status_admin = pg_fetch_result($res_adm,0,'nome_completo');
            }

            echo "<tr>";
                echo "<td align='center'>$status_data</td>";
                echo "<td align='center'>$status_obs</td>";
                echo "<td align='center'>$status_desc</td>";
                echo "<td align='center'>$status_admin</td>";
            echo "</tr>";

        }
    }
    echo "</table>";
    echo "<br>";
}



//if ($login_fabrica <> 81) {

    if ($login_fabrica == 104) {
        $join_os = "
        LEFT JOIN tbl_os_item on tbl_pedido_cancelado.pedido_item = tbl_os_item.pedido_item
        LEFT JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
        LEFT JOIN tbl_os on tbl_os_produto.os = tbl_os.os
        ";
    }else{
        $join_os = "
        LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
        ";
    }

    $sql =  "SELECT tbl_peca.referencia         ,
                    tbl_peca.descricao          ,
                    tbl_pedido_cancelado.qtde   ,
                    tbl_pedido_cancelado.motivo ,
                    TO_CHAR(tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data ,
                    tbl_admin.login ,
                    tbl_os.sua_os
            FROM tbl_pedido_cancelado
            JOIN tbl_peca USING (peca)

            $join_os
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_pedido_cancelado.admin
            WHERE tbl_pedido_cancelado.pedido  = $pedido
            AND   tbl_pedido_cancelado.fabrica = $login_fabrica
            ORDER BY tbl_peca.descricao";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
		$colspan = 4;
        for ($i = 0; $i < pg_num_rows($res); $i++) {

            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

            if ($i == 0) {
                echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>";
                echo "<caption class='titulo_tabela'>" . traduz("Itens cancelados que pertencem a este pedido") . "</caption>";
                echo "<tr class='titulo_coluna'>";
                    if(strtoupper($condicao)=="GARANTIA"){
                        echo "<td>OS</td>";
                    }
                    echo "<td>" . traduz("Data") . "</td>";
                    echo "<td>" . traduz("Peça") . "</td>";
                    echo "<td>" . traduz("Qtde") . "</td>";
                    echo "<td>" . traduz("Admin") . "</td>";
                echo "</tr>";
                echo "<tr class='titulo_coluna'></tr>";

            }

            echo "<tr bgcolor='$cor'>";
                if(strtoupper($condicao)=="GARANTIA"){
                    echo "<td Znowrap align='center'>".pg_fetch_result($res,$i,sua_os)."</td>";
                }
                echo "<td nowrap align='center'>".pg_fetch_result($res,$i,data)."</td>";
                echo "<td nowrap align='center'>".pg_fetch_result($res,$i,referencia)." - ".pg_fetch_result($res,$i,descricao)."</td>";
                echo "<td nowrap align='center'>".pg_fetch_result($res,$i,qtde)."</td>";
                echo "<td nowrap>".pg_fetch_result($res,$i,login)."</td>";
            echo "</tr>";
            echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'></tr>";
            echo "<tr bgcolor='$cor'>";
                echo "<td class='titulo_coluna'>" . traduz("Motivo:") . "</td>";
                echo "<td colspan='$colspan' nowrap align='left'>".pg_fetch_result($res,$i,motivo)."</td>";
            echo "</tr>";

        }

        echo "</table>";

    } else {
        echo "<p align='center'>" . traduz("Não há nenhum pedido cancelado.") . "</p>";
    }

    if ($imprime == 'sim') {
        echo "<div>";
        echo "<a href='pedido_finalizado.php?pedido=".$pedido."' target='_blank'><button type='button'>" . traduz("Imprimir") . "</button></a>";
        echo "&nbsp;";
        if ($login_fabrica == 1) {
            echo "<a href='pedido_cadastro_blackedecker.php' target='_blank'><button type='button'>" . traduz("Lançar Novo Pedido") . "</button></a>";
        }
        echo "</div>";
    }

/*} else {
    $sql = "SELECT
                tbl_os.sua_os, tbl_os_item.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_pedido_item.pedido, sum(tbl_pedido_item.qtde_cancelada) AS qtde_cancelada
            FROM tbl_os
            JOIN tbl_os_produto
                ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_os_item
                ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            JOIN tbl_pedido_item
                ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
            JOIN tbl_peca
                ON tbl_peca.peca = tbl_os_item.peca
            WHERE
                tbl_pedido_item.pedido = $pedido
                AND tbl_os_item.qtde = tbl_pedido_item.qtde_cancelada
            GROUP BY
                tbl_os.sua_os, tbl_os_item.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_pedido_item.pedido";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0 ) {
        echo "<table style='margin: 0 auto; width: 700px; border: 0;' cellspacing='1' class='tabela'>
            <caption class='titulo_tabela'>
                Itens cancelados que pertencem a este pedido
            </caption>
            <tr class='titulo_coluna'>";
                if($condicao=="GARANTIA" or $condicao=="Garantia"){
                    echo "<td>OS</td>";
                }
                    echo "<td>Data</td>
                          <td>Peça</td>
                          <td>Qtde</td>
            </tr>
            <tr class='titulo_coluna'></tr>";

        for ($i = 0; $i < pg_num_rows($res); $i++){
            $xos             = pg_fetch_result($res, $i, "sua_os");
            $xreferencia     = pg_fetch_result($res, $i, "referencia");
            $xdescricao      = pg_fetch_result($res, $i, "descricao");
            $xqtde_cancelada = pg_fetch_result($res, $i, "qtde_cancelada");
            $xpeca           = pg_fetch_result($res, $i, "peca");

            $sqlP = "SELECT data, motivo FROM tbl_pedido_cancelado WHERE fabrica = $login_fabrica AND pedido = $pedido AND os = $xos AND peca = $xpeca";
            $resP = pg_query($con, $sqlP);

            $xdata   = pg_fetch_result($resP, 0, "data");
            $xmotivo = pg_fetch_result($resP, 0, "motivo");

            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

            echo "<tr style='background-color: {$cor}' >";

                    if (strtolower($condicao) == "garantia") {
                        echo "<td style='text-align: center' nowrap > {$xos} </td>";
                    }

                    echo "<td style='text-align: center' nowrap > {$xdata} </td>
                          <td nowrap > {$xreferencia} - {$xdescricao} </td>
                          <td style='text-align: right' nowrap > {$xqtde_cancelada} </td>
                  </tr>
                  <tr style='background-color: {$cor}' >
                    <td class='titulo_coluna' >
                        Motivo
                    </td>
                    <td colspan='3' nowrap > {$xmotivo} </td>
                  </tr>";
        }

        echo "</table>";
    }
}*/

if ($login_fabrica == 7 or $login_fabrica == 43) {

    $sql = "SELECT pedido
            FROM        tbl_pedido
            WHERE       tbl_pedido.fabrica          = $login_fabrica
                AND         tbl_pedido.pedido       = $pedido
                AND         tbl_pedido.finalizado       IS NOT NULL
                AND         tbl_pedido.exportado        IS NULL
                AND         tbl_pedido.troca            IS NOT TRUE
                AND         tbl_pedido.recebido_fabrica IS NULL
                AND         (tbl_pedido.status_pedido <> 14 OR tbl_pedido.status_pedido IS NULL )
                AND         tbl_pedido.data            > '2008-08-01'
                AND         tbl_pedido.data_aprovacao   IS NULL ";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        echo "<form name='aprova'>";
        echo "<a href='javascript: if(confirm(\"" . traduz("Deseja aprovar este pedido?") ."\")) window.location = \"$PHP_SELF?aprovar=sim&pedido=$pedido\"'>";
        echo " <font size='1'>" . traduz("Clique aqui para aprovar esse pedido.") . "</font></a>";
        echo "</form>";
    }

}

if ($login_fabrica == 10 and ($login_admin == 586 or $login_admin == 432)) {

        echo "<form name='retirar_finalizado'>";
        echo "<a href='javascript: if(confirm(\"" . traduz("Deseja tirar a finalização deste pedido?") ."\")) window.location = \"$PHP_SELF?retirar_finalizado=sim&pedido=$pedido\"'>";
        echo " <font size='1'>" . traduz("Clique aqui para TIRAR A FINALIZAÇÃO deste pedido para conseguir alterar o pedido LOGADO como POSTO na LOJA VIRTUAL.") , "</font></a>";
        echo "</form>";

}

/* HD 332453
if($login_fabrica==24){
    ?>
<center><a href='pedido_admin_consulta_txt.php?pedido=<?=$pedido?>&exportar=true"'>EXPORTAR PEDIDO</a></center>
<?}
*/
if (in_array($login_fabrica, array(151))) {
    $sql =  "SELECT tbl_pedido.obs
            FROM  tbl_pedido
            JOIN  tbl_pedido_item USING (pedido)
            JOIN  tbl_peca        USING (peca)
            WHERE tbl_pedido_item.pedido = $pedido
            AND   tbl_pedido.fabrica     = $login_fabrica
            AND tbl_pedido.obs IS NOT NULL";
    $res = pg_query($con,$sql);

    $tabela_obs = '';
    if (pg_num_rows($res) > 0) {
        $tabela_obs .= "<table width='700' cellpadding='2' cellspacing='1' align='center' class='tabela' >";
        $tabela_obs .= "<thead>";
        $tabela_obs .= "<caption class='titulo_tabela'>";
        $tabela_obs .= traduz("Observação Cancelamento");
        $tabela_obs .= "</caption>";
        $tabela_obs .= "</thead>";
        $tabela_obs .= "<tbody>";
        $tabela_obs .= "<tr>";
        $tabela_obs .= "<td>";
        $tabela_obs .= pg_fetch_result($res, 0, "obs");
        $tabela_obs .= "</td>";
        $tabela_obs .= "</tr>";
        $tabela_obs .= "</tbody>";
        $tabela_obs .= "</table>";

        echo $tabela_obs;
    }
}

if(strlen($pedido)> 0) { ?>

    <br>
    <script>
        $(function() {
            $("button[name=btn_auditor]").click(function() {
                var programa = $("#auditor_programa").val();
                var id_auditor = $("#id_auditor").val();

                if(programa == "pedido_cancelado")
                {
                    var url = 'relatorio_log_alteracao_new.php?' +
                        'parametro=tbl_' + programa +
                        '&id=' + id_auditor + '&esconder_coluna=antes';
                } else {
                    var url = 'relatorio_log_alteracao_new.php?' +
                        'parametro=tbl_' + programa +
                        '&id=' + id_auditor;
                }

                Shadowbox.init();

                Shadowbox.open({
                    content: url,
                    player: "iframe",
                });
            });

            $("#venda_direta").click(function(){
                var pedido = $(this).data("pedido");
                $.ajax({
                    url:"<?=$PHP_SELF?>",
                    type:"POST",
                    dataType:"JSON",
                    data:{
                        ajax_venda_direta:true,
                        tipo:"pedido_admin_consulta.php",
                        pedido:pedido
                    },
                    beforeSend: function(){
                        $("#loading_venda_direta").show();
                        $("#venda_direta").hide();
                    }
                })
                .done(function(data){
                    if (data.embarque.length > 0) {
                        alert("Embarque relizado com sucesso "+data.embarque);
                    }else{
                        alert("Não foi possível gerar o embarque.");
                    }
                    $("#loading_venda_direta").hide();
                    //$("#venda_direta").show();
                    $("#conferencia_embarque").show();
                })
                .fail(function(){
                    alert("Falha ao gerar o embarque");
                    $("#loading_venda_direta").hide();
                    $("#venda_direta").show();
                })
            });

            $("#conferencia_embarque").click(function(){
                var pedido = $(this).data("pedido");
                $.ajax({
                    url:"<?=$PHP_SELF?>",
                    type:"POST",
                    dataType:"JSON",
                    data:{
                        ajax_conferencia_embarque:true,
                        tipo:"pedido_admin_consulta.php",
                        pedido:pedido
                    },
                    beforeSend: function(){
                        $("#loading_venda_direta").show();
                        $("#conferencia_embarque").hide();
                    }
                })
                .done(function(data){
                    if (data.retorno == 'sim') {
                        alert("Conferência relizada com sucesso ");
                    }else{
                        alert("Não foi possível gerar a conferência.");
                    }
                    $("#loading_venda_direta").hide();
                    //$("#conferencia_embarque").show();
                })
                .fail(function(){
                    alert("Falha ao gerar a conferência");
                    $("#loading_venda_direta").hide();
                    $("#conferencia_embarque").show();
                })
            });
        });
    </script>
    <?php if ($login_fabrica == 24) {

        if (isset($tipo_pedido) && $tipo_pedido == "Venda Funcionario") {

            echo "<a href='pedido_admin_impressao_dados.php?pedido=" . $_GET['pedido'] . "'><img border='0' width='95' heigth='95' src='imagens/btn_imprimir_azul.gif' alt='" . traduz("Imprimir") . "'></a>";
            echo "<br><br>";
        }

     } ?>
    <button type="button" name="btn_auditor">
        <?php echo traduz("Ver Log de Alteração"); ?>
    </button>
    <input type="hidden" id="id_auditor" name="id_auditor" value="<?php echo $login_fabrica.'*'.$pedido; ?>">
    <select name="auditor_programa" id="auditor_programa">
        <option value="pedido"><?php echo traduz("Pedido"); ?></option>
        <option value="pedido_cancelado"><?php echo traduz("Itens Cancelados"); ?></option>
        <option value="pedido_item"><?php echo traduz("Itens do Pedido"); ?></option>
    </select>
    <br>
<?php }
if ($telecontrol_distrib OR $login_fabrica == 174) { ?>
    <br />
    <center>
        <iframe id="iframe_interacao" src="interacoes.php?tipo=pedido&reference_id=<?=$pedido?>&posto=<?=$posto?>" style="width: 700px;" frameborder="0" scrolling="no"></iframe>
        <br />
            <table class="titulo_tabela">
                <tr>
                    <td width="50%" style="cursor: pointer;">
                        <a href="gerar_embarque_os_press.php?os=<?=$_GET['os']?>" target="_blank" style="color: #FFFFFF;">&nbsp; <?= traduz("Embarcar"); ?> &nbsp;</a>
                    </td>                    
                </tr>
            </table>
    </center>
    <br />
<?php } 
    }
include "rodape.php"; ?>
