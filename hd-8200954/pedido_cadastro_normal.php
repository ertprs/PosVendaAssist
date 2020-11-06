<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if ($login_fabrica == 24 and 1==2) {
	if (strlen($pedido)>0) {
		header("Location: pedido_cadastro_suggar.php?pedido=$pedido");
	}
	else {
		header("Location: pedido_cadastro_suggar.php");
	}
}

$login_bloqueio_pedido = $cookie_login['cook_bloqueio_pedido'];
if($login_fabrica == 5 and $login_posto == 6359){
	header("Location: pedido_cadastro_new.php");
	exit;
}

if($login_fabrica == 1){
	header("Location: pedido_blackedecker_cadastro.php");
	exit;
}
/**INICIO - Funções para o HD-2017979 da Esmaltec */
require_once('includes2/pedidosEsmaltec_verificaOrigem.php');
/**FIM - Funções para o HD-2017979 da Esmaltec */

if($login_fabrica == 30){

	if($_POST['seleciona_condicao'] == 'true'){
		$id_tipo_pedido = $_POST['id_tipo_pedido'];

		$sql_tipo_posto = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
		$res_tipo_posto = pg_query($con, $sql_tipo_posto);

		$tipo_posto = pg_fetch_result($res_tipo_posto, 0, "tipo_posto");

		$sqlCondicao = "SELECT 
							tbl_tipo_pedido_condicao.condicao,
							tbl_tipo_pedido_condicao.tipo_pedido,
							tbl_condicao.descricao
						FROM tbl_condicao
						INNER JOIN tbl_tipo_pedido_condicao ON tbl_condicao.condicao = tbl_tipo_pedido_condicao.condicao AND tbl_tipo_pedido_condicao.fabrica = $login_fabrica 
						INNER JOIN tbl_posto_condicao ON tbl_condicao.condicao = tbl_posto_condicao.condicao AND tbl_posto_condicao.posto = {$login_posto} 
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_condicao.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
						WHERE 
							tbl_tipo_pedido_condicao.tipo_pedido = $id_tipo_pedido 
							AND tbl_posto_fabrica.tipo_posto = {$tipo_posto} 
							AND tbl_posto_condicao.visivel IS TRUE";

		$resCondicao = pg_query($con, $sqlCondicao);

		if(pg_num_rows($resCondicao) > 0){
			$cont = pg_num_rows($resCondicao);
			$select = "<select size='1' name='condicao' class='frm'>";
			for ($i=0; $i < $cont; $i++) {

				$select .= "<option value='".pg_fetch_result($resCondicao, $i, "condicao")."'";
				$select .= ">".pg_fetch_result($resCondicao, $i, "descricao")."</option>";
			}
			$select .="</select>";
		}else{
			$error = "erro";
		}

		if(strlen($error) > 0){
			echo "error|Não existe condição para esse tipo de pedido";
		}else{
			echo $select;
		}
		exit;
	}


	if (file_exists("bloqueio_pedidos/bloqueia_pedido_esmaltec.txt")) {
		$abrir = fopen("bloqueio_pedidos/bloqueia_pedido_esmaltec.txt", "r");
		$ler = fread($abrir, filesize("bloqueio_pedidos/bloqueia_pedido_esmaltec.txt"));
		fclose($abrir);
		$conteudo = explode(";;", $ler);
		$data_inicio = $conteudo[0];
		$data_fim    = $conteudo[1];
		$comentario  = $conteudo[2];

		if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim"))) { // DATA DA VOLTA
			if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio"))) { // DATA DO BLOQUEIO
				$title     = "Pedido de Peças";
				$cabecalho = "Pedido de Peças";
				$layout_menu = 'pedido';

				if($login_posto <> 6359 ) {
					include "cabecalho.php";

					echo "<br><br>\n";
					echo "<table width='700' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
					echo "<TR align='center' bgcolor='#336699'>";
					echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
					echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO ESMALTEC</B></TD>";
					echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
					echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>$comentario</p></TD>";

					echo "</form>";
					echo "</TR>";
					echo "</table>";
					include "rodape.php";
					exit;
				}
			}
		}
	}
}

/*
if($login_fabrica==3 AND $login_posto<>6359){
	echo "Em manutenção - Retorno previsto para às 12 horas";
	exit;
}
*/
/* HD 102825
if($login_fabrica == 5 and $login_posto<>6359 and $login_posto<>
055) {
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDO TEMPORARIAMENTE SUSPENSO.</H4>";
	include "rodape.php";
	echo "";
	exit;
}*/


if($login_fabrica=="15" and $login_posto<>6359){
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	/*Desativado conforme solicitacao Rodrigo latina hd 5086 takashi 28/09/07*/
	echo "<BR><BR><center>Desativado Temporariamente</center><BR><BR>";
	include "rodape.php";
	exit;
}


if($login_fabrica=="50" and $login_posto<>6359 and 1==2){
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	// HD  36995
	echo "<BR><BR><center><b>Pedidos faturados bloqueado, favor pedir peças para compra , através do e-mail:</b> <u>carina@colormaq.com.br</u></center><BR><BR>";
	include "rodape.php";
	exit;
}

if($login_fabrica == 14){
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDO INDISPONÍVEL.</H4>";
	include "rodape.php";
	exit;
}


$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_result ($res,0,0) == 'f') {

	//hd 17625 - Suggar faz pedido em garantia manual
	if (pg_result ($res,0,0) == 'f' and in_array($login_fabrica,array(24,30))) {
		$sql = "SELECT pedido_em_garantia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
	}

	if (pg_result ($res,0,0) == 'f') {
		include "cabecalho.php";
		echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
		include "rodape.php";
		exit;
	}

}

// BLOQUEIO DE PEDIDO FATURADO PARA O GM TOSCAN
if($login_fabrica == 3 and $login_posto == 970){
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}

if(($login_fabrica == 51 or $login_fabrica == 81) and $login_bloqueio_pedido == 't') {
    $layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	include "rodape.php";
	exit;

/*	$sql = "SELECT tbl_posto_linha.posto
			FROM   tbl_posto_linha
			WHERE  tbl_posto_linha.posto        = $login_posto
			AND    tbl_posto_linha.linha NOT IN (2,4);";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 0) {
		$layout_menu = 'pedido';
		$title       = "Cadastro de Pedidos de Peças";
		include "cabecalho.php";
		include "rodape.php";
		exit;
	}*/
}

#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	$distribuidor_digita = pg_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}

$limit_pedidos = 2;

/* Suggar liberou até 4 pedido em garantia - HD 22397 23862*/ // HD 33373 // HD 60077
$limite_posto = array(720,20235,476);
if($login_fabrica==24 AND in_array($login_posto,$limite_posto)){
	$limit_pedidos = 4;
}

if($login_posto==2474){
	$limit_pedidos = 4;
}

if($login_posto==19566){
	$limit_pedidos = 99;
}

#Redireciona para a Loja Virtual - Desabilitado pois ainda vai utilizar este cadastro
if ($login_fabrica == 3) {
	$sql = "SELECT estado FROM tbl_posto WHERE posto = $login_posto";
	$res = pg_exec ($con,$sql);
	$estado = pg_result ($res,0,0);
	if ($estado == 'SP'){
		//header("Location: loja_completa.php");
		//exit;
	}
}

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";
$qtde_item = 40;

if($login_posto==2474){
	$qtde_item = 70;
}

if($login_fabrica==11){
	$qtde_item = 30;
}
/*HD:22543 - IGOR*/
if($login_fabrica==50){
	$qtde_item = 18;
}

/*HD 70768 - Esmaltec 50 ítens  */
if($login_fabrica==30) {
	$qtde_item=50;
}

if($login_fabrica == 30){
    $postoTipoAtende = "SELECT tipo_atende from tbl_posto_fabrica where posto = $login_posto AND fabrica = $login_fabrica";
    $resTipoAtende = pg_query($con, $postoTipoAtende);
    if(pg_num_rows($resTipoAtende) > 0){
        $sql = "SELECT pedido
            FROM tbl_pedido
            WHERE fabrica = $login_fabrica
            AND posto = $login_posto
            AND exportado IS NULL
            AND pedido_os is not true
            AND (status_pedido <> 14 OR status_pedido IS NULL)
            AND finalizado is null
            ORDER BY pedido DESC
            LIMIT 1";
    }else{
        $sql = "SELECT pedido
            FROM tbl_pedido
            WHERE fabrica = $login_fabrica
            AND posto = $login_posto
            AND exportado IS NULL
            AND pedido_os is not true
            AND (status_pedido <> 14 OR status_pedido IS NULL)
            ORDER BY pedido DESC
            LIMIT 1";
    }
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){

        $cook_pedido = pg_result($res,0,pedido);
        $cookie_login['cook_pedido'] = $cook_pedido;
    }else{
        unset($cookie_login['cook_pedido']);
        unset($cook_pedido);
    }
}
if ($btn_acao == "gravar"){
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];

	if ($login_fabrica == 30) {
		$aux_condicao = $condicao;

		if (empty($aux_condicao)) {
			$msg_erro = "Selecione a condição de pagamento";
		}
	} else {
		$aux_condicao = (strlen($condicao) == 0) ? "null" : $condicao ;
	}
	$aux_pedido_cliente = (strlen($pedido_cliente) == 0) ? "null" : "'". $pedido_cliente ."'";
	$aux_transportadora = (strlen($transportadora) == 0) ? "null" : $transportadora ;
	$aux_observacao_pedido = (strlen($observacao_pedido) == 0) ? "null" : "'$observacao_pedido'" ;


	if($login_fabrica <> 30){
		if (strlen($tipo_pedido) <> 0) {
			$aux_tipo_pedido = "'". $tipo_pedido ."'";
		}else{
			$sql = "SELECT	tipo_pedido
					FROM	tbl_tipo_pedido
					WHERE	descricao IN ('Faturado','Venda')
					AND		fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$aux_tipo_pedido = "'". pg_result($res,0,tipo_pedido) ."'";
		}
	}

	if($login_fabrica == 30){ //hd_chamado=2480632
		if (strlen($tipo_pedido) == '') {
			$msg_erro="Por favor, informar tipo de pedido";
		}else{
			$aux_tipo_pedido = $tipo_pedido;
		}
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}

	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";

	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_exec($con,$sql);
			if (pg_numrows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if($login_fabrica==24 and $tipo_pedido==104 and $login_posto<>6359){
		$sql = "SELECT 	to_char(current_date,'MM')::INTEGER as mes,
						to_char(current_date,'YYYY') AS ano";
		$res = pg_exec($con,$sql);
		$mes = pg_result($res,0,mes);
		$ano = pg_result($res,0,ano);

		if(strlen($mes)>0){
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			/*HD: 108583 - RETIRADO PEDIDO DO ADMIN E COM STATUS CANCELADO (14)*/
			$sql = "SELECT 	count(pedido) as qtde
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto
					AND admin is NULL
					AND status_pedido <> 14
					AND data BETWEEN '$data_inicial' AND '$data_final'
					AND tipo_pedido = 104";
			$res = pg_exec($con,$sql);
			$qtde = pg_result($res,0,qtde);
			if($qtde >= $limit_pedidos){
				$msg_erro = "Seu PA já fez $limit_pedidos pedidos de garantia este mês, por favor entre em contato com o fabricante";
			}
		}
	}

if(strlen($msg_erro)==0){
	if (strlen ($pedido) == 0 ) {
		// HD  80338
		if($login_fabrica == 24) {
			$sql_campo = " ,tipo_frete ";
			$sql_valor = " ,'CIF' ";
		}

		if($login_fabrica == 30){

			if(isset($cook_pedido)){
				$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.exportado IS NULL
					AND    tbl_pedido.pedido = $cook_pedido";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res) == 0) {
					$msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
				}else{
					$sql_campo .= ", pedido ";
					$sql_valor .= ", $cook_pedido ";
				}
			}
		}

		#-------------- insere pedido ------------

		if(empty($msg_erro)){
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						fabrica        ,
						condicao       ,
						pedido_cliente ,
						transportadora ,
						linha          ,
						tipo_pedido    ,
						digitacao_distribuidor,
						obs
						$sql_campo
					) VALUES (
						$posto              ,
						$login_fabrica      ,
						$aux_condicao       ,
						$aux_pedido_cliente ,
						$aux_transportadora ,
						$aux_linha          ,
						$aux_tipo_pedido    ,
						$digitacao_distribuidor,
						$aux_observacao_pedido
						$sql_valor
					)";

			if(!empty($aux_tipo_pedido)){
				$res = @pg_exec ($con,$sql);
			}
			
			$msg_erro = pg_errormessage($con);
			if (strlen($msg_erro) == 0){
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
				$pedido  = @pg_result ($res,0,0);
			}
		}
	}else{
		$sql = "UPDATE tbl_pedido SET
					condicao       = $aux_condicao       ,
					pedido_cliente = $aux_pedido_cliente ,
					transportadora = $aux_transportadora ,
					linha          = $aux_linha          ,
					tipo_pedido    = $aux_tipo_pedido
				WHERE pedido  = $pedido
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}

	if (strlen ($msg_erro) == 0) {

		$nacional  = 0;
		$importado = 0;
		$remessa_gar = 0;
		$remessa_gar_comp = 0;
		$total_de_pecas = 50;
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$pedido_item     = trim($_POST['pedido_item_'     . $i]);
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			$preco           = trim($_POST['preco_'           . $i]);
			$xkit_peca       = $_POST["kit_kit_peca_" . $i];

			$preco = (strlen($preco) > 0) ? $preco : 0;
			$preco = str_replace(",",".",str_replace(".","",$preco));

			$qtde = str_replace(",",".",$qtde);
			if (strlen ($peca_referencia) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}

			if (empty($peca_referencia)){
				$total_de_pecas--;
			}

			//hd-3301317
			if (in_array($login_fabrica,array(3)) and ($qtde > 50)){
				$msg_erro = "Quantidade máxima de 50 unidades de cada peça por pedido! <br />";
			}

			if(in_array($login_fabrica, array(3))){

				$mes = date('m');
				$ano = date('Y');
				$ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));

				$dinicio = "$ano-$mes-01";
				$dfim = "$ano-$mes-$ultimo_dia";				
				
				if(strlen(trim($peca_referencia))> 0){
					// tbl_pedido_item.qtde, tbl_pedido.pedido, tbl_pedido.data
					$sqlqtdePeca = "SELECT sum(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) as qtde_peca 
						from tbl_peca 
						join tbl_pedido_item on tbl_pedido_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
						join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
					WHERE tbl_peca.referencia = '$peca_referencia' 
					AND tbl_pedido.data BETWEEN '$dinicio 00:00:00' and '$dfim 23:59:59'
					AND tbl_pedido.posto = $login_posto 
					AND tbl_pedido.fabrica = $login_fabrica ";

					$resqtdePeca = pg_query($con, $sqlqtdePeca);
					if(pg_num_rows($resqtdePeca)>0){
						$qtde_pecas_pedidos = pg_fetch_result($resqtdePeca, 0, qtde_peca);
						if(empty($qtde_pecas_pedidos)){
							$qtde_pecas_pedidos = 0;
						}

						if($qtde_pecas_pedidos+$qtde > 50 ){
							$restante = ((50-$qtde_pecas_pedidos)< 0)? "0" : 50-$qtde_pecas_pedidos; 
							$msg_limite50 .= "Peça $peca_referencia - Pedidos Anteriores $qtde_pecas_pedidos - Saldo  $restante <Br>" ;
						}
					}
				}
			}

			if (in_array($login_fabrica,array(30)) && strlen($xkit_peca) > 0) {

                $xsql_peca = "
                    SELECT  tbl_peca.peca                           AS peca_kit                             ,
                            tbl_peca.referencia                     AS peca_kit_referencia                  ,
                            tbl_peca.remessa_garantia               AS peca_kit_remessa_garantia            ,
                            tbl_peca.remessa_garantia_compressor    AS peca_kit_remessa_garantia_compressor ,
                            tbl_kit_peca_peca.qtde                  AS peca_kit_qtde
                    FROM    tbl_peca
                    JOIN    tbl_kit_peca_peca   USING(peca)
                    JOIN    tbl_kit_peca        USING (kit_peca)
                    WHERE   tbl_kit_peca_peca.kit_peca  = $xkit_peca
                    AND     tbl_kit_peca.fabrica        = $login_fabrica
                ";
//                     echo nl2br($xsql_peca);exit;
                $xres_peca = pg_query($con, $xsql_peca);

                $sqlTab = "
                    SELECT  tabela
                    FROM    tbl_posto_linha
                    JOIN    tbl_linha   ON  tbl_linha.linha     = tbl_posto_linha.linha
                                        AND tbl_linha.fabrica   = $login_fabrica
                    WHERE   tbl_posto_linha.posto = $login_posto
                    LIMIT   1;
                ";
                $resTab = pg_query($con,$sqlTab);
                $tabela = pg_fetch_result($resTab,0,tabela);

                if(pg_numrows($xres_peca) > 0){
                    for($px = 0; $px < pg_numrows($xres_peca); $px++){
                        $peca_kit                               = pg_fetch_result($xres_peca,$px,peca_kit);
                        $peca_kit_referencia                    = pg_fetch_result($xres_peca,$px,peca_kit_referencia);
                        $peca_kit_remessa_garantia              = pg_fetch_result($xres_peca,$px,peca_kit_remessa_garantia);
                        $peca_kit_remessa_garantia_compressor   = pg_fetch_result($xres_peca,$px,peca_kit_remessa_garantia_compressor);
                        $peca_kit_qtde                          = pg_fetch_result($xres_peca,$px,peca_kit_qtde);

                        /**
                            * Pesquisa de preço por peça do Kit
                            */

                        $sql_preco = "
                            SELECT  coalesce(preco,0) AS preco
                            FROM    tbl_tabela_item
                            WHERE   peca    = $peca_kit
                            AND     tabela  = $tabela
                        ";
//                             echo nl2br($sql_preco);exit;
                        $res_preco = @pg_query($con, $sql_preco);
                        $preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));

                        if(strlen($preco) == 0){
                            $msg_erro = "A peça $peca_kit_referencia está sem preço!";
                            break 2;
                        }else{
                            if (strlen($peca_kit_remessa_garantia) > 0 && strlen($peca_kit_remessa_garantia_compressor) > 0 ) {
                                $msg_erro .= " Não é permitido no mesmo pedido peça remessa em garantia e peça remessa em garantia compressor";
                                $linha_erro = $i;
                                break 2;
                            }

                            if (strlen($peca_kit_remessa_garantia) > 0 and ($tipo_pedido <> 231 and $tipo_pedido <> 127)){
                                $msg_erro .= "Peça: $peca_kit_referencia, não pode ser lançada para este tipo de pedido";
                                break 2;
                            }

                            if (strlen($peca_kit_remessa_garantia_compressor) > 0 and ($tipo_pedido <> 233 and $tipo_pedido <> 127)){
                                $msg_erro .= "Peça: $peca_kit_referencia, não pode ser lançada para este tipo de pedido";
                                break 2;
                            }

                            $sqlX = "SELECT pedido_item
                                    FROM    tbl_pedido_item
                                    WHERE   pedido = $pedido
                                    AND     peca = $peca_kit";
//                                 echo nl2br($sqlX);exit;
                            $resX = pg_exec($con,$sqlX);
                            if (pg_numrows($resX) > 0) {
                                $msg_erro = "Peça $peca_kit_referencia já selecionada. Não é permitido duas peças iguais no mesmo pedido. Retire o Kit ou a peça.";
                                break;
                            }else{

                                $qtde_kit = $qtde * $peca_kit_qtde;

                                $sqlKitItem = "INSERT INTO tbl_pedido_item (
                                                pedido  ,
                                                peca    ,
                                                qtde    ,
                                                precos
                                            ) VALUES (
                                                $pedido     ,
                                                $peca_kit   ,
                                                $qtde_kit   ,
                                                $preco
                                            )";
                                $resKitItem = pg_query($con,$sqlKitItem);
                                if(pg_last_error($con)){
                                    $msg_erro = "Não foi possível realizar a gravação";
                                    break 2;
                                }else{
                                    $sql = "SELECT fn_valida_pedido_item ($pedido,$peca_kit,$login_fabrica)";
                                    $res = @pg_exec ($con,$sql);
                                    $msg_erro = pg_errormessage($con);
                                }
                            }
                        }
                    }
                }
            }else{

                # hd 142245
                if (strlen ($peca_referencia) > 0 AND strlen($preco) == 0 AND ($login_fabrica == 30 OR $login_fabrica == 5)) {
                    $msg_erro = "A peça $peca_referencia está sem preço!";
                    $linha_erro = $i;
                    break;
                }

                //verifica se a peça tem o valor da peca caso nao tenha exibe a msg
                //só verifica os precos dos campos que tenha a referencia da peça.
                if ($login_fabrica == '15' AND strlen($peca_referencia) > 0 )
                {
                    if($tipo_pedido <> '90')
                    {
                        if(strlen($preco) == 0)
                        {
                            $msg_erro = 'Existem peças sem preço.<br>';
                            $linha_erro = $i;
                            break;
                        }
                    }
                }
                //Adicionado a Gama Italy HD20369
                if (($login_fabrica==6 OR ($login_fabrica==51 AND $login_posto <> 4311)) and strlen($peca_referencia) > 0 and strlen($preco)==0) {
                    $msg_erro = 'Existem peças sem preço.<br>';
                    $linha_erro = $i;
                    break;
                }

                if ($login_fabrica==45 and strlen($peca_referencia) > 0 and strlen($preco)==0) {
                    $msg_erro = 'Existem peças sem preço.<br>';
                    $linha_erro = $i;
                    break;
                }

                $qtde_anterior = 0;
                $peca_anterior = "";
                if (strlen($pedido_item) > 0 AND $login_fabrica==3){
                    $sql = "SELECT peca,qtde
                            FROM tbl_pedido_item
                            WHERE pedido_item = $pedido_item";
                    $res = @pg_exec ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                    if (pg_numrows ($res) > 0){
                        $peca_anterior = pg_result($res,0,peca);
                        $qtde_anterior = pg_result($res,0,qtde);
                    }
                }

                if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0) {
					$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
                    $res = pg_exec ($con,$sql);

                    /* Tira do estoque disponivel */
                    if ($login_fabrica==3){
                        $sql = "UPDATE tbl_peca
                                SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior
                                WHERE peca     = $peca_anterior
                                AND   fabrica  = $login_fabrica
                                AND   promocao_site IS TRUE
                                AND qtde_disponivel_site IS NOT NULL";
                        $res = pg_exec ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                }

                if (strlen ($peca_referencia) > 0) {
                    $peca_referencia = trim (strtoupper ($peca_referencia));
                    $peca_referencia = str_replace ("-","",$peca_referencia);
                    $peca_referencia = str_replace (".","",$peca_referencia);
                    $peca_referencia = str_replace ("/","",$peca_referencia);
                    $peca_referencia = str_replace (" ","",$peca_referencia);

                    $sql = "SELECT  tbl_peca.peca   ,
                                    tbl_peca.origem ,
                                    tbl_peca.qtde_disponivel_site ,
                                    tbl_peca.qtde_max_site,
                                    tbl_peca.multiplo_site,
                                    tbl_peca.remessa_garantia,
                                    tbl_peca.remessa_garantia_compressor
                            FROM    tbl_peca
                            WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
                            AND     tbl_peca.fabrica             = $login_fabrica";
                    $res = pg_query($con,$sql);

                    $peca          = pg_fetch_result($res,0,peca);
                    $promocao_site = pg_fetch_result($res,0,promocao_site);
                    $qtde_disp     = pg_fetch_result($res,0,qtde_disponivel_site);
                    $qtde_max      = pg_fetch_result($res,0,qtde_max_site);
                    $qtde_multi    = pg_fetch_result($res,0,multiplo_site);

                    if(pg_num_rows($res) == 0){
                    	   $msg_erro = "Peça $peca_referencia não cadastrada";
                        $linha_erro = $i;
                        break;
                    }else{
                        $peca   = pg_result ($res,0,peca);
                        $origem = trim(pg_result ($res,0,origem));
                        if ($login_fabrica == 30) {
                            $remessa_garantia = trim(pg_result ($res,0,remessa_garantia));
                            $remessa_garantia_compressor = trim(pg_result ($res,0,remessa_garantia_compressor));
                        }
                    }

                    if ($login_fabrica == 30) {

                        if ($remessa_garantia == 't') {
                            $remessa_gar += 1;
                        }

                        if ($remessa_garantia_compressor == 't') {
                            $remessa_gar_comp += 1;
                        }

                        if ($remessa_gar>0 and $remessa_gar_comp>0) {
                            $msg_erro .= " Não é permitido no mesmo pedido peça remessa em garantia e peça remessa em garantia compressor";
                            $linha_erro = $i;
                            break;

                        }

                        if ($remessa_gar > 0 and ($tipo_pedido <> 231 and $tipo_pedido <> 127)){
                            $msg_erro .= "Peça: $peca_referencia, não pode ser lançada para este tipo de pedido";
                        }

                        if ($remessa_gar_comp > 0 and ($tipo_pedido <> 233 and $tipo_pedido <> 127)){
                            $msg_erro .= "Peça: $peca_referencia, não pode ser lançada para este tipo de pedido";
                        }

                    }

                    if ($origem == "NAC" or $origem == "1") {
                        $nacional = $nacional + 1;
                    }

                    if ($origem == "IMP" or $origem == "2") {
                        $importado = $importado + 1;
                    }

                    #hd 16782
                    if ($nacional > 0 and $importado > 0 and $login_fabrica <> 3 and $login_fabrica <> 5 and $login_fabrica <> 8 and $login_fabrica <> 24 and $login_fabrica <> 6) {
                        $msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
                        $linha_erro = $i;
                        break;
                    }

                    /*
                    if ($login_fabrica == 3 && strlen($peca_referencia) > 0) {
                        $sqlX =	"SELECT referencia
                                FROM tbl_peca
                                WHERE referencia_pesquisa = UPPER('$peca_referencia')
                                AND   fabrica = $login_fabrica
                                AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
                        $resX = pg_exec($con,$sqlX);
                        if (pg_numrows($resX) > 0) {
                            $peca_previsao = pg_result($resX,0,0);
                            $msg_erro = "Não há previsão de chegada da Peça $peca_previsao. Favor encaminhar e-mail para <a href='mailto:leila.beatriz@britania.com.br'>leila.beatriz@britania.com.br</a>, informando o número da ordem de serviço. Somente serão aceitas requisições via email! Não utilizar o 0800.";
                        }
                    }
                    */

                    /* HD 27857 - Não permitir duas peças iguais no mesmo pedido */
                    if ($login_fabrica == 3 && strlen($peca) > 0 AND strlen($pedido_item)==0) {
                        $sqlX =	"SELECT pedido_item
                                FROM tbl_pedido_item
                                WHERE pedido = $pedido
                                AND   peca = $peca";
                        $resX = pg_exec($con,$sqlX);
                        if (pg_numrows($resX) > 0) {
                            $msg_erro = "Peça $peca_referencia  já selecionada. Não é permitido duas peças iguais no mesmo pedido. Altere sua quantidade.";
                        }
                    }

                    /* HD 219410 - Não permitir duas peças iguais no mesmo pedido */
                    if ($login_fabrica == 30 && strlen($peca) > 0 AND strlen($pedido_item)==0) {
                        $sqlX =	"SELECT pedido_item
                                FROM tbl_pedido_item
                                WHERE pedido = $pedido
                                AND   peca = $peca";
                        $resX = pg_exec($con,$sqlX);
                        if (pg_numrows($resX) > 0) {
                            $msg_erro = "Peça $peca_referencia  já selecionada. Não é permitido duas peças iguais no mesmo pedido. Altere sua quantidade.";
                        }
                    }


                    if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
                        if (strlen($pedido_item) == 0){
                            $sql = "INSERT INTO tbl_pedido_item (
                                        pedido ,
                                        peca   ,
                                        qtde   ,
                                        preco
                                    ) VALUES (
                                        $pedido ,
                                        $peca   ,
                                        $qtde   ,
                                        $preco
                                    )";
                        }else{
                            $sql = "UPDATE tbl_pedido_item SET
                                        peca = $peca,
                                        qtde = $qtde,
                                        preco = $preco
                                    WHERE pedido_item = $pedido_item";
                        }

                        $res = @pg_exec ($con,$sql);
                        $msg_erro = pg_errormessage($con);


                        #HD 15017
                        #HD 16686
                        if ($login_fabrica==3 AND $promocao_site=='t'){
                            ########## Validação de Quantidade #########
                            $sql = "SELECT SUM(tbl_pedido_item.qtde) AS qtde
                                    FROM tbl_pedido
                                    JOIN tbl_pedido_item USING(pedido)
                                    WHERE  tbl_pedido.fabrica     = $login_fabrica
                                    AND    tbl_pedido.posto       = $login_posto
                                    AND    tbl_pedido.pedido      = $pedido
                                    AND    tbl_pedido_item.peca   = $peca";
                            $res = pg_exec ($con,$sql);
                            $pedido_item = "";
                            if (pg_numrows ($res) > 0) {
                                $qtde_pedido = pg_result ($res,0,qtde);

                                if (strlen($msg_erro)==0 AND strlen($qtde_max)>0 AND $qtde_pedido > $qtde_max){
                                    $msg_erro .= "Quantidade máxima permitida para a peça $peca_referencia é de $qtde_max.";
                                }
                                if (strlen($msg_erro)==0 AND strlen($qtde_disp)>0 AND $qtde_pedido > $qtde_disp){
                                    $msg_erro .= "A peça $peca_referencia tem $qtde_disp unidades disponíveis.";
                                }
                                if (strlen($msg_erro)==0 AND strlen($qtde_multi)>0 AND $qtde_pedido % $qtde_multi <> 0){
                                    $msg_erro .= "Para a peça $peca_referencia a quantidade deve ser múltiplo de $qtde_multi.";
                                }
                            }
                        }

                        /* Tira do estoque disponivel */
                        if ($login_fabrica==3 AND $promocao_site=='t' AND strlen($pedido_item) > 0 AND $peca_anterior <> $peca){
                            $sql = "UPDATE tbl_peca
                                    SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior
                                    WHERE peca     = $peca_anterior
                                    AND   fabrica  = $login_fabrica
                                    AND   promocao_site IS TRUE
                                    AND qtde_disponivel_site IS NOT NULL";
                            $res = pg_exec ($con,$sql);
                            $msg_erro .= pg_errormessage($con);
                            $qtde_anterior = 0;
                        }

                        if ($login_fabrica==3 AND $promocao_site=='t'){
                            $sql = "UPDATE tbl_peca
                                    SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior -$qtde
                                    WHERE peca     = $peca
                                    AND   fabrica  = $login_fabrica
                                    AND   promocao_site IS TRUE
                                    AND qtde_disponivel_site IS NOT NULL";
                            $res = pg_exec ($con,$sql);
                            $msg_erro .= pg_errormessage($con);
                        }

                        if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
                            $res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
                            $pedido_item = pg_result ($res,0,0);
                            $msg_erro = pg_errormessage($con);
                        }

                        if (strlen($msg_erro) == 0) {
                            $sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
                            $res = @pg_exec ($con,$sql);
                            $msg_erro = pg_errormessage($con);
                        }

                        if (strlen ($msg_erro) > 0) {
                            break ;
                        }
                    }//faz a somatoria dos valores das peças para verificar o total das pecas pedidas
                    //Apenas para Latina.

                    if($login_fabrica == 30) {
                        if( strlen($preco) > 0 AND strlen($qtde) > 0){
                            $total_valor_esmaltec = $total_valor_esmaltec + ($preco * $qtde);
                        }
                    }

                    if($login_fabrica == 15) {
                        if( strlen($preco) > 0 AND strlen($qtde) > 0){
                            $total_valor = (($total_valor) + ( str_replace( "," , "." ,$preco) * $qtde));
                        }
                    }
                }
            }
		}

		if($login_fabrica == 3 AND strlen(trim($msg_limite50))> 0){
			$msg_erro .= "Peças acima do limite de 50 peças<Br> ".$msg_limite50;
		}

				
		if ($total_de_pecas == 0 and $login_fabrica == 30){
			$msg_erro = "Não é possivel gravar o pedido sem nenhuma peça. Informe alguma peça";
		}
		//modificado para a Latina pois o valor nao pode ser menor do que 80,00 reias.

		if($login_fabrica == 15){
			if($tipo_pedido <> '90')
				{
				if($total_valor < 80){
					$msg_erro .= 'O valor mínimo não foi atingido ';
				}else{
					//condicoes de pagamento depedendo do valor não se pode escolher a forma de pagamento
					if($condicao == 75 AND $total_valor < 200){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
					if($condicao == 98 AND $total_valor < 350){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
					if($condicao == 99 AND $total_valor < 600){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0 and !in_array($login_fabrica,array(30))) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}


	//hd-3301317
	if ( ($login_fabrica == 3) and strlen($msg_erro) == 0 ){
		$sql="SELECT total from tbl_pedido where pedido=$pedido";
		$res=@pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$total=pg_result($res,0,total);
			if($total < 50){
				$msg_erro="Valor Mínimo de pedidos é de R$ 50,00";
			}
		}
	}

	//HD 15482 //HD 27679 23/7/2008 GAMA  //HD 34765
	if((/*$login_fabrica==3 or */$login_fabrica==51) and strlen($msg_erro)==0){
		$sql="SELECT total from tbl_pedido where pedido=$pedido";
		$res=@pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$total=pg_result($res,0,total);
			if($total < 30){
				$msg_erro="O pedido faturado deve ser maior que R$ 30,00";
			}
		}
	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		if(in_array($login_fabrica,array(30))){
			header("Location: $PHP_SELF?listar=$pedido");
		} else {
			header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1");
		}
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}


}

$btn_acao = $_GET['btn_acao'];
if($btn_acao == "finalizar"){
	$pedido = $_GET['pedido'];

	pg_query($con, "BEGIN TRANSACTION");
    if($login_fabrica == 30){
        $pedidos = array($pedido);
        try{
            //função definida em: includes2/pedidosEsmaltec_verificaOrigem.php
            $novoPedido = verificaOrigemPecas($pedido);
            if($novoPedido != false){
                $pedidos[] = $novoPedido;
            }
            $valorTotalPedidos = 0;
            $pedidoParam = array();
            foreach($pedidos as $key => $item){

                $sql = "SELECT fn_pedido_finaliza ($item,$login_fabrica)";
                $res = pg_exec($con,$sql);
                $msg_erro = pg_errormessage($con);

                $sql = "SELECT total from tbl_pedido where pedido=$item";

                $res = pg_exec($con,$sql);
                if(pg_numrows($res) > 0){
                    $total = pg_result($res,0,total);
                    $valorTotalPedidos += $total;
                }
                $pedidoParam[] = "pedido[$key]=$item";
            }

            if(strlen($msg_erro) == 0){
                $pedidoParam = implode("&",$pedidoParam);
                pg_query($con, "COMMIT TRANSACTION");
                unset($cook_pedido);
                unset($cookie_login["cook_pedido"]);
                header ("Location: pedido_relacao.php?$pedidoParam&btn_acao_pesquisa=continuar&loc=1");

            }else{

                pg_query($con, "ROLLBACK TRANSACTION");
            }

        }catch(Exception $ex){
            pg_query($con, "ROLLBACK TRANSACTION");
            $msg_erro = $ex->getMessage();
        }
    }else{
        $sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
        $res = pg_exec($con,$sql);
        $msg_erro = pg_errormessage($con);
        if (empty($msg_erro)) {

            pg_query($con, "COMMIT");
            header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1");

        } else {

            pg_query($con, "ROLLBACK");

        }
    }
}

if($btn_acao == "deletar"){
	$pedido = $_GET['pedido'];
	$pedido_item = $_GET['pedido_item'];

	$sql_verifica = "SELECT finalizado from tbl_pedido where tbl_pedido.pedido = $pedido";
	$res_verifica = pg_query($con,$sql_verifica);

	if (pg_num_rows($res_verifica) > 0){

		$finalizado = pg_fetch_result($res_verifica, 0, 0);

		$finalizado = ($login_fabrica == 30) ? "" : $finalizado;

		if (!empty($finalizado)){

			$msg_erro = "Pedido já finalizado";

		}else{

			$res = pg_query($con,"BEGIN TRANSACTION");

			$sqlD = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
			$resD = pg_query($con,$sqlD);
			$msg_erro = pg_errormessage($con);

			if(empty($msg_erro)){

				$sql = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
				$res = pg_query($con,$sql);

				if(pg_numrows($res) == 0){

					$sqlT = "SELECT fnc_pedido_delete_esmaltec($pedido,$login_fabrica,null)";
					$resT = pg_query($con,$sqlT);
					$msg_erro = pg_errormessage($con);

					if(empty($msg_erro)){

						$sqlP = "UPDATE tbl_pedido SET fabrica = 0 WHERE pedido = $pedido AND fabrica = $login_fabrica";
						$resP = pg_query($con,$sqlP);
						$msg_erro = pg_errormessage($con);

					}

				}

			}

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$msg_erro = pg_errormessage ($con) ;
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");
				header ("Location: $PHP_SELF");
				exit;
			}

		}

	}


}
#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if($login_fabrica == 30){
	if(strlen($pedido) == 0 && isset($_GET['listar'])){
		$pedido = $_GET['listar'];
	}
}
setcookie("cook_pedido", $pedido, time()+(3600*120));
$cook_pedido = $cookie_login['cook_pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT	TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.obs                                                    ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.total_original                                         ,
					tbl_pedido.permite_alteracao
			FROM	tbl_pedido
			LEFT JOIN tbl_transportadora USING (transportadora)
			left JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto        USING (produto)
			WHERE	tbl_pedido.pedido   = $pedido
			AND		tbl_pedido.posto    = $login_posto
			AND		tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$data                  = trim(pg_result ($res,0,data));
		$transportadora        = trim(pg_result ($res,0,transportadora));
		$transportadora_cnpj   = trim(pg_result ($res,0,transportadora_cnpj));
		$transportadora_codigo = trim(pg_result ($res,0,transportadora_codigo));
		$transportadora_nome   = trim(pg_result ($res,0,transportadora_nome));
		$pedido_cliente        = trim(pg_result ($res,0,pedido_cliente));
		$tipo_pedido           = trim(pg_result ($res,0,tipo_pedido));
		$produto               = trim(pg_result ($res,0,produto));
		$produto_referencia    = trim(pg_result ($res,0,produto_referencia));
		$produto_descricao     = trim(pg_result ($res,0,produto_descricao));
		$linha                 = trim(pg_result ($res,0,linha));
		$condicao              = trim(pg_result ($res,0,condicao));
		$exportado             = trim(pg_result ($res,0,exportado));
		$total_original        = trim(pg_result ($res,0,total_original));
		$permite_alteracao     = trim(pg_result ($res,0,permite_alteracao));
		$observacao_pedido     = @pg_result ($res,0,obs);
	}
}



#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
}

$title       = "Cadastro de Pedidos de Peças";
$layout_menu = 'pedido';

include "cabecalho.php";

/* if ($login_fabrica == 30) {
	$sql = "SELECT tbl_posto_fabrica.tipo_posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND tbl_posto_fabrica.posto = $login_posto
			AND tbl_posto_fabrica.tipo_posto = 368
			AND posto <> 6359";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		echo "<BR><BR><center><b>Pedido Faturado não Autorizado SAE PSE</b></center><BR><BR>";
		include "rodape.php";
		exit;
	}
} */

if(($login_fabrica == 81 or $login_fabrica == 51) and $login_bloqueio_pedido == 't'){
	echo "<p>";
	echo "<table border=1 align='center'><tr><td align='center'>";
	echo "<font face='verdana' size='2' color='FF0000'><b>Existem títulos pendentes de seu posto autorizado junto ao Distribuidor.
	<br>";
	if($login_fabrica == 3){
		echo "Não será possível efetuar novo pedido faturado das linhas de eletro e branca.
	<br>";
	}else{
		echo "Não será possível efetuar novo pedido faturado !.
	<br>";
	}
	echo "<br>
	Para regularizar a situação solicitamos um contato urgente com a TELECONTROL:
	<br>
	(14) 3413-6588 / distribuidor@telecontrol.com.br
	<br>
	Entrar em contato com o departamento de cobranças ou <br>
	efetue o depósito em conta corrente no <br><BR>
	Banco Bradesco<BR>
	Agência 2155-5<br>
	C/C 17427-0<br><br>
	e encaminhe um fax (14 3413-6588) com o comprovante.</b>
	<br><br>
	<b>Para visualizar os títulos <a href='posicao_financeira_telecontrol.php'>clique aqui</a></b>
	</font>";
	echo "</td></tr></table>";
	echo "<p>";
}

?>

<script language="javascript">
function exibeTipo(){
	f = document.frm_pedido;
	if(f.linha.value == 3){
		f.tipo_pedido.disabled = false;
	}else{
		f.tipo_pedido.selectedIndex = 0;
		f.tipo_pedido.disabled = true;
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim";
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}
</script>

<style type="text/css">
#layout{
	width: 650px;
	margin:0 auto;
}

ul#split, ul#split li{
	margin:50px;
	margin:0 auto;
	padding:0;
	width:600px;
	list-style:none
}

ul#split li{
	float:left;
	width:600px;
	margin:0 10px 10px 0
}

ul#split h3{
	font-size:14px;
	margin:0px;
	padding: 5px 0 0;
	text-align:center;
	font-weight:bold;
	color:white;
}
ul#split h4{
	font-size:90%
	margin:0px;
	padding-top: 1px;
	padding-bottom: 1px;
	text-align:center;
	font-weight:bold;
	color:white;
}

ul#split p{
	margin:0;
	padding:5px 8px 2px
}

ul#split div{
	background: #E6EEF7
}

li#one{
    text-align:left;
    width: 800px !important;
    position: relative;
    left: -100px;
}

li#one div{
	border:1px solid #596D9B
}
li#one h3{
	background: #7392BF;
}

li#one h4{
	background: #7392BF;
}

.coluna1{
	width:250px;
	font-weight:bold;
	display: inline;
	float:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 12px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.btn-boleto{
	background-image: linear-gradient(#f38542, #e65d0a);
	border: none;
    padding: 6px 25px;
    text-align: center;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
    font-weight: bold;
    color: #fff;
}

.btn-boleto:hover{
	text-decoration:none !important;
    font-weight:bold !important;
    color: #fff !important;
    background-image: linear-gradient(#fb995e, #ff680c) !important;
}

</style>

<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript">
<?if ($login_fabrica == 30){?>
	$(document).ready(function(){

			if ($('input[name=tipo_pedido]').val() != "") {
				condicaoPedido($('input[name=tipo_pedido]').val());
			}

			var qtde_nao_brancos = 50;
			qtde_nao_brancos = $('.ref_esmaltec').filter(function(){return ($(this).val() != '');}).length;

			var msg_error = "<?=$msg_erro?>";

			if(msg_error == '' || msg_error == undefined){ //hd_chamado=2480632
				$("#tipo_pedido").attr('disabled', (qtde_nao_brancos > 0));
			}

			$('input[name=tipo_pedido]').val($("#tipo_pedido").val());
			var qtde_tipo_pedido = $('#tipo_pedido option').size();

			if (qtde_tipo_pedido == 1) {
				//hd_chamado=2480632
					var id_tipo_pedido = $('input[name=tipo_pedido]').val();
					// condicaoPedido(id_tipo_pedido);
			}

			$("#tipo_pedido").change(function(){
				$('input[name=tipo_pedido]').val($(this).val());
				//hd_chamado=2480632
					var id_tipo_pedido = $('input[name=tipo_pedido]').val();
					condicaoPedido(id_tipo_pedido);
				//hd_chamado=2480632 - fim
			});

			$('.ref_esmaltec').blur(function(){
				qtde_nao_brancos = $('.ref_esmaltec').filter(function(){return ($(this).val() != '');}).length;
				$("#tipo_pedido").attr('disabled', (qtde_nao_brancos > 0));
			});

	});
<?}?>

	function condicaoPedido(id_tipo_pedido) {

		$.ajax({
			url : "<?php echo $_SERVER['PHP_SELF']; ?>",
				type: "POST",
				data: {
				seleciona_condicao : "true",
				id_tipo_pedido : id_tipo_pedido,
			 },
			complete: function(data){
				data = data.responseText;
				retorno = data.split("|");
				if(retorno[0] == "error"){
					$("#condicao_select_smaltec").html('');
					$("#condicao_input_smaltec").hide();
				}else{
					$("#condicao_select_smaltec").html(retorno[0]);
						exibirPopUpAviso();
                    $("select[name=condicao]").change(function() { /*HD - 4397677*/
                    	exibirPopUpAviso();
                    });
                    if ($("#condicao_selecionada").length) {
                        $("select[name=condicao]").val($("#condicao_selecionada").val());
                    }
				}
			}
		});
	}

	function exibirPopUpAviso() {
		var aux_tipo_pedido = $("#tipo_pedido").val();
    	var aux_condicao    = $("select[name=condicao]").val();
    	
    	if (aux_tipo_pedido == "127" && aux_condicao == "1486") {
    		janela = window.open("peca_pesquisa_lista.php?popup=aviso_condicao", "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.focus();
    	}
	}
</script>

<!-- Bordas Arredondadas para a NIFTY -->
<!--<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>-->



<?php include "javascript_pesquisas.php" ?>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript">
function fnc_makita_preco (linha_form) {
	condicao = window.document.frm_pedido.condicao.value ;
	posto    = <?= $login_posto ?>;

	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = "0,00";

	peca_referencia = 'peca_referencia_' + linha_form;
	peca_referencia = document.getElementById(peca_referencia).value;

	url = 'makita_valida_regras.php?linha_form=' + linha_form + '&posto=<?= $login_posto ?>&produto_referencia=' + peca_referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';
	requisicaoHTTP ('GET', url , true , 'fnc_makita_responde_preco');

}

function fnc_makita_responde_preco (campos) {
	campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
	campos = campos.substring (0,campos.indexOf('</preco>'));
	campos_array = campos.split("|");

	preco      = campos_array[0] ;
	linha_form = campos_array[1] ;

	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = preco;

}
</script>


<!--  Mensagem de Erro-->
<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	?>
	<div id="layout">
	<div class="error">
	<? echo $erro . $msg_erro; ?>
	</div>
	</div>
<? } ?>

<?


$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$frase = "PREENCHA SEU PEDIDO DE COMPRA/GARANTIA";
}else{
	$frase = "PREENCHA SEU PEDIDO DE COMPRA";
}
?>

<br>

<div id="layout">
	<div class="titulo"><h1>Cadastro de Pedido</h1></div>
</div>

<div id="layout">
	<div class="subtitulo">
	<?
	if ($login_fabrica == 51) {
		echo "
		<font size='4' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>*** Atenção esta tela é somente para pedidos fora de garantia ***</b></font>
		<br><br>
		<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
		<br><br>";
	}
	if ($login_fabrica == 3) { ?>
		<!--<b>Atenção Linha Áudio e Vídeo:</b> Pedidos de peças para linha de áudio e vídeo feitos nesta tela devem ser para uso em consertos fora da garantia, e gerarão fatura e duplicata.<br>Pedidos para conserto em garantia serão gerados automaticamente pela Ordem de Serviço.<br>Leia o Manual e a Circular na primeira página.
		<br><br>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
		<br><br>
		//hd3301317
		Não há Valor Mínimo de Pedido de Compra de Peças. <br>
		-->
		Valor mínimo de pedido de compra R$ 50,00. <br>
		A restrição será no faturamento e envio de peças pelo depósito da Britânia.<br>
		<b>Valor mínimo de faturamento R$  50,00.</b> <br>
		Quando houver pedido de peças em garantia será utilizado o mesmo frete.<br>
		Pedidos pendentes de compra superiores a 60 dias serão avaliados e poderão ser excluídos.<br>
	</td>
	<? }elseif ($login_fabrica == 15) { ?>
		<b>AVISO</b> <br>Peças <b>plásticas</b> em garantia, somente para produtos com até <b>90 dias</b> da compra.
		<br>
		<br>
		<b>Condições de Pagamento:</b> <br> Até R$ 200,00 30 dias ; Até R$ 350,00 30-45 dias <br> Até R$ 600,00 , 30-60 dias ; Acima de R$ 600,00 , 30-60-90 dias
		<br>
		<br>
		<b>*** Pedidos abaixo de R$80,00 não serão faturados ***</b>
		<br>
		<br>
		<b>Despesas de frete de peças faturadas serão por conta do Posto Autorizado.</b>
		<br>
			Sudeste/Sul: R$ 28,36<br>
			Centroeste: R$ 30,00<br>
			Norte/Nordeste: R$ 33.80<br>
		<br>
		<b>Despesas de frete de peças em garantia serão por conta da LATINATEC.</b>
		<br>
		<br>
	<? }else{ ?>
		<b>Atenção:</b> Pedidos a prazo dependerão de análise do departamento de crédito.
	<?

	 } ?>

<?
/*
if($login_fabrica ==45){
echo "		<br><br><b>Atenção:</b> <font color ='red'><br>Pedido suspenso até terça-feira (10/02/2009).
<br></font>
Ass. NKS.";
exit;
}
*/
?>
	</div>
</div>

<!-- OBSERVAÇÕES -->
<div id="layout">
<? if ($login_fabrica<>30) {   //HD 70768-1 - Retirar mensagem na Esmaltec  ?>

	<div class="content">
	<? if($login_fabrica<>24){ ?>
		Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.
	<? 	
		}else {

		echo "O fabricante limita em $limit_pedidos pedidos de garantia por mês.<br>";

		$sql = "SELECT 	to_char(current_date,'MM')::INTEGER as mes,
						to_char(current_date,'YYYY') AS ano";
		$res = pg_exec($con,$sql);
		$mes = pg_result($res,0,mes);
		$ano = pg_result($res,0,ano);

		if(strlen($mes)>0){
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			$sql = "SELECT 	count(pedido) as qtde
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto
					AND data BETWEEN '$data_inicial' AND '$data_final'
					AND tipo_pedido = 104";
			$res = pg_exec($con,$sql);
			$qtde = pg_result($res,0,qtde);
			if($qtde < 2){
				//echo "<br>";
				echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês</b>";
				//echo "<br>";
			}else{
				//echo "<br>";
				echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês, caso necessite de outro pedido em garantia por favor entre em contato com o fabricante.</b>";
				//echo "<br>";
			}
		}
	}
   }    // fim HD 70768-1
	//alterado por Wellington 13-10-2006 chamado 575
	if ($login_fabrica == "11") { ?>
			<span> Somente Pedidos de Venda </span>
			<? echo "Nesta tela devem ser digitados somente pedidos de <B>VENDA</B>. Pedidos de peça na <B>GARANTIA</B> devem ser feitos somente através da abertura da Ordem de Serviço.";
				?>
	<? } ?>

		<!-- PERMITIR ALTERAÇÕES  -->
		<? if($login_fabrica == 7 AND $total_original > 0 AND $permite_alteracao == 't'){ ?>
			<br><br><b>Atenção:</b> o pedido deve ser superior a R$ <?=number_format($total_original,2,",",".")?>
		<?}?>
	</div>

	<?
	/*hd-3301317*/
	if (in_array($login_fabrica,array(3))) {
		Echo '	<div class="content">
					Quantidade máxima de 50 unidades de cada peça por pedido.
				</div>';
	}?>

</div>
<?php if (in_array($login_fabrica,array(3))) {?>
<div>
	<a href="https://britania.portaldocliente.online" target="_blank"class="btn-boleto">Emissão de Boletos</a>
</div>
<?php }?>
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? if(!empty($pedido)) echo $pedido;else echo $cook_pedido; ?>">
<input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">

<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
	<center>
	<p>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr valign='top' style='font-size:12px'>
		<td nowrap align='center'>
		Distribuidor pode digitar pedidos para seus postos.
		<br>
		Digite o código do posto
		<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
		ou deixe em branco para seus próprios pedidos.
		</td>
	</tr>
	</table>
<? } ?>
<br>


<!-- INICIA DIVISÃO -->
<ul id="split">
<li id="one">
<h3><? echo $frase; ?></h3>
<div>

	<? if ($login_fabrica <> "24" and $login_fabrica <> "30") { //HD 70768-2 Retirar campo 'pedido do cliente' ?>
		<p><span class='coluna1'>Pedido do Cliente</span>
			<input class="frm" type="text" name="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
		</p>
	<?}?>

	<?
	$res = pg_exec ("SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

	#permite_alteracao - HD 47695
	if (pg_result ($res,0,0) == 'f' OR $permite_alteracao == 't') {
		echo "<input type='hidden' name='condicao' value=''>";
	}else{?>

	<?php if($login_fabrica <> 30){ //hd_chamado=2480632 ?>
	<p><span class='coluna1'>Condição Pagamento</span>
		<?
			//hd 17625
			if ($login_fabrica == 24 or $login_fabrica == 30) {
				$sql = "SELECT pedido_em_garantia, pedido_faturado
						FROM tbl_posto_fabrica
						WHERE fabrica = $login_fabrica
						AND   posto   = $login_posto;";
				$res = pg_exec($con,$sql);

				$pede_em_garantia = pg_result($res,0,pedido_em_garantia);
				$pede_faturado    = pg_result($res,0,pedido_faturado);
			}
			if($login_posto == 4311){
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_condicao.fabrica     = $login_fabrica ";
			}else{
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_condicao.fabrica     = $login_fabrica
						AND      tbl_condicao.visivel       IS TRUE
						AND      tbl_posto_condicao.visivel IS TRUE ";
			}

			//hd 17625
			if ($login_fabrica == 24 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
				$sql .= " AND tbl_condicao.condicao = 928 ";
			}

			if ($login_fabrica == 30 and $pede_em_garantia == "t") {
				$sql .= " AND tbl_condicao.condicao = 1825 ";
			}

			$sql .= "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
			$xxx  = $sql;

			$res = pg_exec ($con,$sql);

			if(pg_numrows ($res) == 0 or $login_fabrica==2 ) {

					$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						WHERE    tbl_condicao.fabrica = $login_fabrica
						AND      tbl_condicao.visivel IS TRUE ";
					//hd 17625
				if ($login_fabrica == 24 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
					$sql .= " AND tbl_condicao.condicao = 928 ";
				}

				if ($login_fabrica == 30 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
					$sql .= " AND tbl_condicao.condicao = 1825 ";
				}

				if($login_fabrica == 30){

					$sql = "SELECT tbl_posto_condicao.condicao, tbl_condicao.descricao, tbl_posto_condicao.visivel
						FROM 			tbl_posto_condicao
						JOIN 			tbl_condicao ON tbl_condicao.condicao = tbl_posto_condicao.condicao
						WHERE 			tbl_condicao.fabrica = $login_fabrica
						AND   			tbl_posto_condicao.posto   = $login_posto
						AND     		tbl_condicao.visivel IS TRUE
						AND 				tbl_posto_condicao.visivel IS TRUE
					";
				}

				$sql .= "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
				$res = pg_exec ($con,$sql);
			}

			if($login_fabrica == 30){
					$sql = "SELECT tbl_posto_condicao.condicao, tbl_condicao.descricao, tbl_posto_condicao.visivel
						FROM 			tbl_posto_condicao
						JOIN 			tbl_condicao ON tbl_condicao.condicao = tbl_posto_condicao.condicao
						WHERE 			tbl_condicao.fabrica = $login_fabrica
						AND   			tbl_posto_condicao.posto   = $login_posto
						AND     		tbl_condicao.visivel IS TRUE
						AND 				tbl_posto_condicao.visivel IS TRUE
						ORDER BY tbl_condicao.descricao
					";
					$res = pg_exec ($con,$sql);
			}
			 //echo $sql;exit;
			?>

			<select size='1' name='condicao' class='frm'>

			<?

			if ($login_fabrica == 30 and $pede_em_garantia == "t") {
				$sqlGar = "SELECT * from tbl_condicao WHERE fabrica = $login_fabrica AND codigo_condicao = '4'";
				$resGar = pg_query($con, $sqlGar);

				if (pg_num_rows($resGar) > 0) {
					echo "<option value='".pg_fetch_result($resGar, 0, "condicao")."'";
					if (pg_fetch_result($resGar, 0, "condicao") == $condicao) echo " selected";
					echo ">".pg_fetch_result($resGar, 0, "descricao")."</option>";
				}
			}

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				#HD 107982
				if($login_fabrica==24){
					if(($login_posto==13051 OR $login_posto==39159 OR $login_posto==19566 OR $login_posto==1053) AND pg_result ($res,$i,condicao) <> 928){
						echo "<option value='" . pg_result ($res,$i,condicao) . "'";
						if (pg_result ($res,$i,condicao) == $condicao) echo " selected";
						echo ">" . pg_result ($res,$i,descricao) . "</option>";
					}else if($login_posto<>13051 AND $login_posto<>39159 AND $login_posto<>19566 AND $login_posto<>1053){
						echo "<option value='" . pg_result ($res,$i,condicao) . "'";
						if (pg_result ($res,$i,condicao) == $condicao) echo " selected";
						echo ">" . pg_result ($res,$i,descricao) . "</option>";
					}
				}else{
					echo "<option value='" . pg_result ($res,$i,condicao) . "'";
					if (pg_result ($res,$i,condicao) == $condicao) echo " selected";
					echo ">" . pg_result ($res,$i,descricao) . "</option>";
				}
			}
		?>
		</select>

	<?php } ?>

	</p>
<?
}
?>

		<?
		//VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
		$sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica
				WHERE fabrica=$login_fabrica AND posto=$login_posto";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$garantia_antecipada = pg_result($res,0,0);
			if($garantia_antecipada <> "t") {
				$garantia_antecipada ="f";
			}
		}
		?>


			<p><span class='coluna1'>Tipo de Pedido</span>
			<?
			// se posto pode escolher tipo_pedido

			$sql = "SELECT   *
					FROM     tbl_posto_fabrica
					WHERE    tbl_posto_fabrica.posto   = $login_posto
					AND      tbl_posto_fabrica.fabrica = $login_fabrica";
			if($login_fabrica<>24) {
				$sql .= " AND      tbl_posto_fabrica.pedido_em_garantia IS TRUE;";
			}

			$res = pg_exec ($con,$sql);

			$readonly = "";

			if ($login_fabrica == 30) {
				if(!empty($cook_pedido)){
					$auxpedido = $cook_pedido;
					$sql = "select tipo_pedido,descricao from tbl_pedido join tbl_tipo_pedido using(tipo_pedido) where pedido = $auxpedido";
					$res = pg_query($sql);
					$tipo_pedido = pg_result($res,0,tipo_pedido);
					$descricao   = pg_result($res,0,descricao);
					$readonly = "readonly";
				}
			}

			if (pg_numrows($res) > 0) {

				if ($readonly) {
					echo "<input type='text' name='tipo_pedido_descricao' id='tipo_pedido_descricao' value='$descricao' readonly size='40'>";
					echo "<input type='hidden' name='tipo_pedido' value='$tipo_pedido' >";

				} else {
					if ($login_fabrica == 30){
						$name_tipo_pedido = "";
						echo "<input type='hidden' name='tipo_pedido' value='$tipo_pedido' >";
					}else{
						$name_tipo_pedido = 'tipo_pedido';
					}
					echo "<select size='1' name='$name_tipo_pedido' id='tipo_pedido' class='frm' $readonly>";
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica ";
					if($login_fabrica==24) {
						$sql .= " AND tipo_pedido not in(107,104)";

						#HD 17625
						if ($pede_faturado == '0') {
							$sql .= " AND tipo_pedido <> 103 ";
						}
					}

					if($login_fabrica == 30) {
						if($pedido_faturado == '0') {
							$sql .= " AND pedido_faturado is not true ";
						}
					}
					$sql .= " ORDER BY tipo_pedido ";
					$res = pg_exec ($con,$sql);
					$xxx = $sql;

					# AND      (garantia_antecipada is false or garantia_antecipada is null)
					# takashi -  coloquei -> AND      (garantia_antecipada is false or garantia_antecipada is null)
					# efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

					if($login_fabrica == 30){ //hd_chamado=2480632
						echo "<option value=''></option>";
					}
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						#HD 107982
						if($login_fabrica==24){
							if(($login_posto==13051 OR $login_posto==39159 OR $login_posto==19566 OR $login_posto==1053) AND pg_result ($res,$i,tipo_pedido) <> 104){
								echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
								if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
									echo " selected";
								}
								echo ">" . pg_result($res,$i,descricao) . "</option>";
							}else if($login_posto<>13051 AND $login_posto<>39159 AND $login_posto<>19566 AND $login_posto<>1053){
								echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
								if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
									echo " selected";
								}
								echo ">" . pg_result($res,$i,descricao) . "</option>";
							}
						}else{
							echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
							if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
								echo " selected";
							}
							echo ">" . pg_result($res,$i,descricao) . "</option>";
						}
					}

					if($garantia_antecipada=="t"){
						//takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
						$sql = "SELECT   *
								FROM     tbl_tipo_pedido
								WHERE    fabrica = $login_fabrica
								AND garantia_antecipada is true ";
						if($login_fabrica==24) {
							$sql .= " and tipo_pedido <> 107";
						}
						 $sql .= " ORDER BY tipo_pedido ";
						 $xxl =  $sql;
						$res = pg_exec ($con,$sql);

						for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							#HD 107982
							if($login_fabrica==24){
								if(($login_posto==13051 OR $login_posto==39159 OR $login_posto==19566 OR $login_posto==1053) AND pg_result ($res,$i,tipo_pedido) <> 104){
									echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
									if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
										echo " selected";
									}
									echo ">" . pg_result($res,$i,descricao) . "</option>";
								}else if($login_posto<>13051 AND $login_posto<>39159 AND $login_posto<>19566 AND $login_posto<>1053){
									echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
									if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
										echo " selected";
									}
									echo ">" . pg_result($res,$i,descricao) . "</option>";
								}
							}else{
								echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
								if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) {
									echo " selected";
								}
								echo ">" . pg_result($res,$i,descricao) . "</option>";
							}
						}
					}
					echo "</select>";
				}
			}else{
				if ($login_fabrica == 30){
					$name_tipo_pedido = "";
					echo "<input type='hidden' name='tipo_pedido' value='$tipo_pedido' >";
				}else{
					$name_tipo_pedido = 'tipo_pedido';
				}

				echo "<select size='1' id='tipo_pedido' name='$name_tipo_pedido' class='frm' ";
				if ($login_fabrica == 3) {
					echo "disabled";
				}
				echo ">";
				$sql = "SELECT   *
						FROM    tbl_tipo_pedido
						WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
					       OR	tbl_tipo_pedido.descricao ILIKE '%Venda%')
						AND     tbl_tipo_pedido.fabrica = $login_fabrica
						AND     (garantia_antecipada is false or garantia_antecipada is null)
						ORDER BY tipo_pedido;";

				#HD 47695
				if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica ";
					if (strlen($tipo_pedido)>0){
						$sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
					}
					$sql .= " ORDER BY tipo_pedido;";
				}

				$res = pg_exec ($con,$sql);

				# takashi -  coloquei : AND      (garantia_antecipada is false or garantia_antecipada is null)
				# efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

				if($login_fabrica == 30){
					echo "<option value=''>Selecione</option>";
				}

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
					if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
					echo ">" . pg_result($res,$i,descricao) . "</option>";
				}
				if($garantia_antecipada=="t"){
					#takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica
							AND garantia_antecipada is true
							ORDER BY tipo_pedido ";
					$res = pg_exec ($con,$sql);

					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
						if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
							echo " selected";
						}
						echo ">" . pg_result($res,$i,descricao) . "</option>";
					}
				}
				echo "</select>";
			}
			?>
			</p>

			<?php if($login_fabrica == 30){ //hd_chamado=2480632 ?>
				<br>
				<p><span class='coluna1'>Condição Pagamento</span></p>
				<input type="text" name="condicao_input_smaltec" id="condicao_input_smaltec" style="display: none;">
				<span style="border:none;" id='condicao_select_smaltec'>
					<?php
						if(strlen($condicao) > 0){
							$where_cond_pagamento = " AND tbl_condicao.condicao = {$condicao} ";
						}

						/* $sqlCondicao = "SELECT tbl_tipo_pedido_condicao.condicao,
												tbl_tipo_pedido_condicao.tipo_pedido,
												tbl_condicao.descricao
											FROM tbl_condicao
											JOIN tbl_tipo_pedido_condicao ON tbl_condicao.condicao = tbl_tipo_pedido_condicao.condicao AND tbl_tipo_pedido_condicao.fabrica = $login_fabrica
											WHERE tbl_tipo_pedido_condicao.tipo_pedido = $tipo_pedido"; */
						/*$sqlCondicao = "SELECT   tbl_condicao.*
										FROM     tbl_condicao
										JOIN     tbl_posto_condicao USING (condicao)
										WHERE    tbl_posto_condicao.posto = $login_posto
										AND      tbl_condicao.fabrica     = $login_fabrica
									        AND 	 tbl_condicao.visivel IS TRUE
										$where_cond_pagamento";
						$resCondicao = pg_query($con, $sqlCondicao);

						if(pg_num_rows($resCondicao) > 0){
							$cont = pg_num_rows($resCondicao);
							$select = "<select size='1' name='condicao' class='frm'>";
							for ($i=0; $i < $cont; $i++) {

								$select .= "<option value='".pg_fetch_result($resCondicao, $i, "condicao")."' ";
								$select .= ">".pg_fetch_result($resCondicao, $i, "descricao")."</option>";
							}
							$select .="</select>";
						}

						echo $select;*/

					?>

				</span>
			<?php } ?>

		<?#-------------------- Transportadora -------------------

		#HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
		if ($permite_alteracao != 't'){
			$sql = "SELECT	tbl_transportadora.transportadora        ,
							tbl_transportadora.cnpj                  ,
							tbl_transportadora.nome                  ,
							tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING(transportadora)
					JOIN	tbl_fabrica USING(fabrica)
					WHERE	tbl_transportadora_fabrica.fabrica        = $login_fabrica
					AND		tbl_transportadora_fabrica.ativo          = 't'
					AND		tbl_fabrica.pedido_escolhe_transportadora = 't'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
			?>
				<p><span class='coluna1'>Transportadora</span>
				<?
					if (pg_numrows ($res) <= 20) {
						echo "<select name='transportadora' class='frm'>";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='".pg_result($res,$i,transportadora)."' ";
							if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
							echo ">";
							echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
					}else{
						echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
						echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

						#echo "<input type='text' name='transportadora_cnpj' size='20' maxlength='18' value='$transportadora_cnpj' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_cnpj,'cnpj')\" style='cursor:pointer;'>";

						echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

						//echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' onblur='javascript: lupa_transportadora_nome.click()'>&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
						echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
					}
					?>
				</p>
		<? }
		}?>

		<?#-------------------- Linha do pedido -------------------

		#HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
		if ($permite_alteracao == 't' and strlen($linha)>0 or in_array($login_fabrica, [3])){
			?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
		}else{
			$sql = "SELECT	tbl_linha.linha            ,
							tbl_linha.nome
					FROM	tbl_linha
					JOIN	tbl_fabrica USING(fabrica)
					JOIN	tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
											AND tbl_posto_linha.linha = tbl_linha.linha
					WHERE	tbl_fabrica.linha_pedido is true
					AND     tbl_linha.fabrica = $login_fabrica ";

			// BLOQUEIO DE PEDIDOS PARA A LINHA ELETRO E BRANCA EM
			// CASO DE INADIMPLÊNCIA
			// Não bloqueia pedidos do JANGADA - CARMEM LUCIA
// HD 221194 estava bloqueando porque o posto está com pendência na TELECONTROL
//			if ($login_fabrica == 3 and $login_bloqueio_pedido == 't' and $login_posto <> 1053) //			{
//				$sql .= "AND tbl_linha.linha NOT IN (2,4)";
//			}
			if($login_fabrica == 51){
				$sql .= " AND tbl_linha.ativo IS TRUE ";
			}
			#permite_alteracao - HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
				$sql .= " AND tbl_linha.linha = $linha ";
			}
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
			?>
				<p><span class='coluna1'>Linha</span>
						<?
						echo "<select name='linha' class='frm' ";
						if ($login_fabrica == 3) echo " onChange='exibeTipo()'";
						echo ">";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='".pg_result($res,$i,linha)."' ";
							if ($linha == pg_result($res,$i,linha) ) echo " selected";
							echo ">";
							echo pg_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
						?>
				</p>
			<?
			}
		}
		?>
		<h4>Peças</h4>
		<div style="text-align: right;">Valor Total do Pedido: <input disabled type="text" name="valor_total" id="valor_total" /></div>

		<? if(!in_array($login_fabrica, [3,24,30,42])) { //HD 70768-Retirar estes campos para a Esmaltec ?>
			<p><span class='coluna1'>Referência Produto</span><input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia,document.frm_pedido.produto_descricao,'referencia',document.frm_pedido.produto_voltagem)">
			</p>

			<p><span class='coluna1'>Descrição Produto</span><input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' align='absmiddle' alt="Clique para pesquisar pela descrição do produto" onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia,document.frm_pedido.produto_descricao,'descricao',document.frm_pedido.produto_voltagem)"><input type="hidden" name="produto_voltagem">
			</p>
			<br>
		<? } else { ?>
			<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
		<? } ?>

		<!-- Peças -->
		<p>
		<table border="0" cellspacing="0" cellpadding="2" align="center" class='xTabela' style='width: 95%'>
			<tr height="20" bgcolor="#CDDBF1">
				<td align='center'><?
				if($login_fabrica<>6){?>Referência Componente<? }else{?> Código Componente<? }?></td>
				<td align='center'>Descrição Componente</font></td>
				<td align='center'>Qtde</td>
				<? if (!in_array($login_fabrica, [3,14,24])) { ?>
				<td align='center'>Preço</td>
				<? } ?>
				<? if (in_array($login_fabrica, [3])) { ?>
				<td align='center'>Valor Peça</td>
				<td align='center'>Sub Total</td>
				<? } ?>
			</tr>

			<?
			if($login_fabrica == 30 AND $_GET['pecas']){
				$pecas_sem_estoque = str_replace("//", "", $_GET['pecas']);
				$pecas_sem_estoque = json_decode($pecas_sem_estoque,true);
			}

			for ($i = 0 ; $i < $qtde_item ; $i++) {

				if (strlen($pedido) > 0 and $login_fabrica <> 30){	// AND strlen ($msg_erro) == 0
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";

					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						$pedido_item     = trim(@pg_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_result($res,$i,descricao));
						$qtde            = trim(@pg_result($res,$i,qtde));
						$preco           = trim(@pg_result($res,$i,preco));
						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');
					}else{
						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}else{

					if($login_fabrica == 30 AND !empty($pecas_sem_estoque['p_'.$i])){
						$sqlE = "SELECT referencia,descricao,round(CAST(preco AS numeric),2) AS preco
									FROM tbl_peca
									JOIN tbl_tabela_item ON tbl_peca.peca = tbl_tabela_item.peca
									JOIN tbl_posto_linha ON tbl_tabela_item.tabela = tbl_posto_linha.tabela
									WHERE tbl_peca.peca = ".$pecas_sem_estoque['p_'.$i]."
									AND tbl_posto_linha.posto = $login_posto";
						$resE = pg_query($con,$sqlE);
						if(pg_num_rows($resE) > 0){
							$peca_referencia = pg_fetch_result($resE, 0, 'referencia');
							$peca_descricao  = pg_fetch_result($resE, 0, 'descricao');
							$preco 			 = pg_fetch_result($resE, 0, 'preco');
						}
					}else{

						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}

				$peca_referencia = trim ($peca_referencia);

				#--------------- Valida Peças em DE-PARA -----------------#
				$tem_obs = false;
				$linha_obs = "";
				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_exec ($con,$sql);

					if (pg_numrows ($resX) > 0) {
						$linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
						$peca_referencia = pg_result ($resX,0,0);
						$tem_obs = true;
					}
				}

				#--------------- Valida Peças Fora de Linha -----------------#
				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

					$resX = pg_exec ($con,$sql);
					if (pg_numrows ($resX) > 0) {
						$libera_garantia = pg_result ($resX,0,libera_garantia);
						#17624
						if ($login_fabrica==3 AND $libera_garantia=='t'){
							$linha_obs .= "Peça acima está fora de linha. Disponível somente para garantia. Caso necessário, favor contatar a Assistência Técnica Britânia <br>&nbsp;";
						}else{
							$linha_obs .= "Peça acima está fora de linha <br>&nbsp;";
						}
						$tem_obs = true;
					}
				}

				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_exec ($con,$sql);
					if (pg_numrows ($resX) > 0) {
						$peca_descricao = pg_result ($resX,0,0);
					}
				}

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($tem_obs) $cor='#FFCC33';

				$class_referencia_esmaltec = ($login_fabrica == 30) ? "ref_esmaltec" : '' ;
			?>
				<tr bgcolor="<? echo $cor ?>">
					<td align='left'>
						<input type="hidden" name="pedido_item_<? echo $i ?>" value="<? echo $pedido_item; ?>">
<?php
						if ($login_fabrica == 30) {
?>

                        <input type='hidden' name='kit_kit_peca_<?=$i?>' value='<?=$xkit_peca[$i]?>'>
<?
                        }
?>
						<input type="text" data-lista='<? echo $i ?>' id="peca_referencia_<? echo $i ?>"  class="frm <?=$class_referencia_esmaltec?> ui-autocomplete-input" name="peca_referencia_<? echo $i ?>" size="10" value="<? echo $peca_referencia; ?>">
						<img src='imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' tipo="peca_classe" align='absmiddle'
						<? if ($login_fabrica == 14 ) { ?> onclick="javascript: fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia.value , document.frm_pedido.peca_referencia_<?echo $i?> , document.frm_pedido.peca_descricao_<?echo $i?> , document.frm_pedido.posicao, 'referencia')" <?
						}else{ ?> onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<? echo $i ?>,window.document.frm_pedido.preco_<? echo $i ?>,window.document.frm_pedido.voltagem,'referencia',window.document.frm_pedido.qtde_<? echo $i ?>,'','','',<? echo $i ?>)" <? } ?>>
					</td>
					<td align='left'>
						<input type="hidden" name="posicao">
						<input class="frm ui-autocomplete-input" data-lista='<? echo $i ?>' type="text" name="peca_descricao_<? echo $i ?>" id="peca_descricao_<? echo $i ?>" size="25" value="<? echo $peca_descricao ?>"><img tipo="peca_classe" src='imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' <? 
						if ($login_fabrica == 14 ) { 
							?> onclick="javascript: fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia.value , document.frm_pedido.peca_referencia_<?echo $i?> , document.frm_pedido.peca_descricao_<?echo $i?> , document.frm_pedido.posicao, 'descricao')" <? 
						}else{ 
							?> onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<? echo $i ?>,window.document.frm_pedido.preco_<? echo $i ?>,window.document.frm_pedido.voltagem,'descricao',window.document.frm_pedido.qtde_<? echo $i ?>,'','','',<? echo $i ?>)" <? } ?>>
					</td>
					<td align='center'>
						<input class="frm" data_qtde="0" data_qtde_id="<? echo $i ?>" id="qtde_<? echo $i ?>" type="text" name="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>"
						<?
						if ($login_fabrica == 42) {
							echo " onblur='javascript: fnc_makita_preco ($i) ' ";
						}

						if (in_array($login_fabrica, array(3))) {
							echo ' placeholder="Max. 50"';
						}
						
						?>
						>
					</td>

					<? if ($login_fabrica != 14 AND $login_fabrica!=24) { ?>
					<td align='center'>
						<input class="frm" id="preco_<? echo $i ?>" type="text" name="preco_<? echo $i ?>" size="10"  value="<? echo $preco ?>" readonly style='text-align:right'>
					</td>
					<? } ?>
					<? if (in_array($login_fabrica, [3])) { ?>
					<td align='center'>
						<input data_sub_total="<? echo $i ?>" class="frm" id="sub_total_<? echo $i ?>" type="text" name="sub_total_<? echo $i ?>" size="10"  value="<?php echo $preco * $qtde ?>" readonly style='text-align:right'>
					</td>
					<td align='center'>
						<img style="display:none;" data_linha_id='<?php echo $i; ?>' src="image/icon-error.gif" width="20px">
					</td>
					<? } ?>
					<? if ($login_fabrica==24){ ?>
					<input type="hidden" name="preco_<? echo $i ?>" value="<? echo $preco ?>">
					 <? } ?>
				</tr>

				<?
				if ($tem_obs) {
					$colsp = ($login_fabrica == 3) ? 5 : 4;
					echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
					echo "<td colspan='".$colsp."'>$linha_obs</td>";
					echo "</tr>";
				}
                if (in_array($login_fabrica,array(30))) {
                    echo "<tr>
                            <td colspan='100%'>

                                <div id='kit_peca_$i'><input type='hidden' name='kit_peca_$i' value='kit_peca_$i'>";

                    if (!empty($xkit_peca[$i])) {
                    $sql = " SELECT tbl_peca.peca      ,
                                        tbl_peca.referencia,
                                        tbl_peca.descricao,
                                        tbl_kit_peca_peca.qtde
                                FROM    tbl_kit_peca_peca
                                JOIN    tbl_peca USING(peca)
                                WHERE   fabrica = $login_fabrica
                                AND     kit_peca = $xkit_peca[$i]
                                ORDER BY tbl_peca.peca";
                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res) > 0) {
                                echo "<table>";
                                    if ($login_fabrica == 91 || $login_fabrica <> 3) {
                                        echo "<tr>
                                                <td colspan='100%'>
                                                    <input type='hidden' name='kit_kit_peca_$i' value='$xkit_peca[$i]'>
                                                </td>
                                            </tr>";
                                    }
                            for ($k = 0; $k < pg_num_rows($res); $k++) {
                                $kit_peca_peca = pg_fetch_result($res,$k,'peca');
                                $kit_peca_qtde = pg_fetch_result($res,$k,'qtde');

                                if ($_POST["kit_peca_$kit_peca_peca"]) {
                                    $checked = "checked";
                                } else {
                                    $checked = "";
                                }

                                echo "<tr style='font-size: 11px'>";
                                    echo "<td>";
                                    echo "<input type='".((in_array($login_fabrica,array(3,15,30,91))) ? 'hidden' : 'checkbox')."' name='kit_peca_$kit_peca_peca' $checked value='$kit_peca_peca'>";
                                        echo "<input type='text' name='kit_peca_qtde_$kit_peca_peca' id='kit_peca_qtde_$kit_peca_peca' value='" . $_POST["kit_peca_qtde_$kit_peca_peca"] . "' size='5' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\" readonly='readonly'> x ";
                                    echo "</td>";
                                    echo "<td> - ";
                                    echo pg_fetch_result($res,$k,'descricao');
                                    echo "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                    }
                        echo "</div>
                        </td>
                    </tr>";
                }
			}
			if($login_fabrica == 15){
				echo "<tr style='font-size:12px' align='center'>";
				echo "<td colspan='4'><b>Observação</b>: <INPUT TYPE='text' size='60' NAME='observacao_pedido'";
					if(strlen($observacao_pedido) > 0) echo " value='$observacao_pedido'";
				echo "></td>";
				echo "</tr>";
			}

			?>
			</table>
		</p>
		<p><center>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
		</center>
		</p>
</div>
</li>
</ul>
<!-- Fecha Divisão-->

</form>
<br clear='both'>
<p>

<?php
	if($login_fabrica == 30){
?>
			<table width="700" align="center">
				<tr>
					<td align='center' bgcolor='#f4f4f4'>
						<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
					</td>
				</tr>
				<tr>
					<td align='center' bgcolor='#f4f4f4'>
						<p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR</b></font></p>
					</td>
				</tr>
			</table> <br />
<?php
		if(!empty($cook_pedido)){
		$pedido = $cook_pedido;

		$sqlT="SELECT tbl_posto_linha.tabela
			FROM tbl_posto_linha
			JOIN tbl_tabela using (tabela)
			WHERE fabrica = $login_fabrica and posto = $login_posto";
		$resT = pg_query($con,$sqlT);

		if(pg_numrows($resT) > 0){
			$tabela = pg_result($resT,0,tabela);
		}


		if ($login_fabrica == 30) {
			$sql_descobre_tipo = "SELECT upper(descricao) as descricao from tbl_tipo_pedido join tbl_pedido using(tipo_pedido) where pedido = $pedido";

			$res_descobre_tipo = pg_query($con,$sql_descobre_tipo);

			if (pg_num_rows($res_descobre_tipo)>0) {
				$descricao_tipo = pg_result($res_descobre_tipo,0,descricao);
			}

			if ($descricao_tipo == 'REMESSA EM GARANTIA' or $descricao_tipo == 'REMESSA EM GAR DE COMPRESSOR') {
				$sqlT="SELECT tbl_posto_linha.tabela_posto
					FROM tbl_posto_linha
					JOIN tbl_tabela using (tabela)
					WHERE fabrica = $login_fabrica and posto = $login_posto";
				$resT = pg_query($con,$sqlT);

				if(pg_numrows($resT) > 0){
					$tabela = pg_result($resT,0,tabela_posto);
				}
			}
		}


        $sql = "SELECT tbl_peca.referencia,
        tbl_peca.descricao,
        tbl_pedido_item.pedido_item,
        tbl_pedido_item.qtde,
        ROUND(tbl_tabela_item.preco::NUMERIC, 2) AS preco,
        tbl_pedido.condicao
        FROM tbl_pedido_item
        JOIN tbl_pedido using(pedido)
        JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = $login_fabrica
        JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_pedido_item.peca AND tbl_tabela_item.tabela = $tabela
        WHERE tbl_pedido_item.pedido = $pedido";

		$res = pg_query($con,$sql);
		if(pg_numrows($res) > 0){
			$total_itens = pg_numrows($res);
		?>

			<input type="text" name="condicao_selecionada" id="condicao_selecionada" style="display: none;" value="<?=pg_result($res,0,condicao); ?>">
			<table width="700" align="center" cellspacing="1" class="tabela">
			<caption class="titulo_tabela">Itens do Pedido</caption>
			<tr class="titulo_coluna">
				<td>Ação</td>
				<td>Referencia</td>
				<td>Descrição</td>
				<td>Qtde</td>
				<td>Valor</td>
			</tr>
			<?php
				$total = 0;
				for($i=0; $i < $total_itens; $i++){
					$pedido_item = pg_result($res,$i,pedido_item);
					$qtde 		 = pg_result($res,$i,qtde);
					$preco 		 = pg_result($res,$i,preco);
					$referencia  = pg_result($res,$i,referencia);
					$descricao	 = pg_result($res,$i,descricao);

					$valor = $preco * $qtde;
					$total += $valor;

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			?>
					<tr bgcolor="<?php echo $cor; ?>">
						<td align="center"> <input type="button" value="Excluir" onclick="window.location='<?php echo $PHP_SELF;?>?btn_acao=deletar&pedido=<?php echo $pedido;?>&pedido_item=<?php echo $pedido_item;?>'"> </td>
						<td> <?php echo $referencia;?> </td>
						<td> <?php echo $descricao;?> </td>
						<td align="center"> <?php echo $qtde;?> </td>
						<td align="right"> <?php echo number_format($valor,2,',','.');?> </td>
					</tr>
			<?php
				}
			?>
			<tr class="titulo_coluna">
				<td colspan="4" align="right"> Total </td>
				<td align="right"> <?php echo number_format($total,2,',','.');?> </td>
			</tr>
			<tr>
				<td colspan="5" align="center">
					<input type="button" value="Finalizar" onclick="window.location='<?php echo $PHP_SELF;?>?btn_acao=finalizar&pedido=<?php echo $pedido;?>'">
				</td>
			</tr>
		</table>
		<?php
		}
	}
}
?>
<? include "rodape.php"; ?>
<?php if (in_array($login_fabrica, [3])) { ?>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<script type="text/javascript">
	window.qtdechange = function(posicao){
		$('#qtde_' + posicao).trigger("blur");
	};
	 
	$(function(){
		$('#valor_total').val(0);
		$("[data_qtde_id]").each(function() {
			$(this).trigger("blur");
		});
	});
	$("[data_qtde_id]").blur(function(){
		var id = $(this).attr('data_qtde_id');
		if ($('#qtde_' + id).val() > 50) {
			alert('Limite de 50 quantidade por peça.');
			$('#qtde_' + id).val(50);
		}
		var preco_antes = $('#preco_' + id).val().replace(",", ".");
		var preco = parseFloat(preco_antes);
		var qtde = parseFloat(this.value);
		var calculo = parseFloat(qtde * preco);
		if (isNaN(calculo)) {
			calculo = 0;
		}
		$('#sub_total_' + id).val(calculo.toFixed(2));
		$('#sub_total_' + id).attr('data_sub_total', calculo.toFixed(2));
		var total = 0;
		$('[data_sub_total]').each(function(){
			if (parseFloat($(this).val()) > 0){
				total += parseFloat($(this).val());
				$('#valor_total').val(total.toFixed(2));
			}	
		});
		if (total == 0) {
			$('#valor_total').val(0);
		}
		if (calculo > 0){
			$("[data_linha_id=" + id + "]").removeAttr('style');
			$("[data_linha_id=" + id + "]").attr('style', 'cursor: pointer;');			
		}
	
	});
	$("[data_linha_id]").click(function() {
		var id = $(this).attr('data_linha_id');
		$('#peca_referencia_'+ id).val('');
		$('#peca_descricao_'+ id).val('');
		$('#qtde_'+ id).val(0);
		$('#preco_'+ id).val('');
		$('#sub_total_'+ id).val(0);
		$("[data_linha_id=" + id + "]").removeAttr('style');
		$("[data_linha_id=" + id + "]").attr('style', 'display: none;');
		$("[data_qtde_id]").each(function() {
			$(this).trigger("blur");
		});
	});
	$("[id^=peca_referencia]").keyup(function(){
		$(this).autocomplete({
			source: "ajax_busca_peca.php",
			extraParams: { campo: 'referencia', limit: 10, valor: $(this).val() },
			select: function (event, ui) {
				setCampos($(this), ui.item['referencia'], ui.item['descricao'], ui.item['preco']);
				return false;			
			}
		}).data("uiAutocomplete")._renderItem = function (ul, item) {
			var text = item["referencia"] + " - " + item["descricao"];

			return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
		};
	});
	$("[id^=peca_descricao]").keyup(function(){
		$(this).autocomplete({
			source: "ajax_busca_peca.php",
			extraParams: { campo: 'descricao', limit: 10, valor: $(this).val() },
			select: function (event, ui) {
				setCampos($(this), ui.item['referencia'], ui.item['descricao'], ui.item['preco']);
				return false;			
			}
		}).data("uiAutocomplete")._renderItem = function (ul, item) {
			var text = item["referencia"] + " - " + item["descricao"];

			return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
		};
	})
	function setCampos(campo, ref, desc, preco){
		$('#peca_referencia_' + $(campo).data("lista")).val(ref);
		$('#peca_descricao_' + $(campo).data("lista")).val(desc);
		$('#preco_' + $(campo).data("lista")).val(preco);
		$('#qtde_' + $(campo).data("lista")).val(1);
		$('#qtde_' + $(campo).data("lista")).trigger('blur');
	}
</script>
<?php }?>
