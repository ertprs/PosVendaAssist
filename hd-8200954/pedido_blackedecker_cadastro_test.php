<?php
//raphael giovanini - inclusao de pedido garantia atraves de funcao

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if (strlen(trim($_GET["msg"])) > 0) $msg = trim($_GET["msg"]);

$abrir = fopen("/www/assist/www/bloqueia_pedido_black.txt", "r");
$ler = fread($abrir, filesize("/www/assist/www/bloqueia_pedido_black.txt"));
fclose($abrir);

$conteudo = explode(";;", $ler);

$data_inicio = $conteudo[0];
$data_fim    = $conteudo[1];
$comentario  = $conteudo[3];

//PROMOÇÃO
# Obs.: no HD 59222 foi coloca uma validação na fn_finaliza_pedido_blackedecker
#   para não aceitar pedidos promocionais, uma vez que a Black não aceitará
#   este tipo de pedido em 2009. A razão de ter colocado na função é que no
#   envio de pedido via arquivo (pedido_upload.php) permitia-se o cadastro de
#   pedido promocional mesmo não estando de fato liberado
$abrir = fopen("/www/assist/www/libera_promocao_black.txt", "r");
$ler = fread($abrir, filesize("/www/assist/www/libera_promocao_black.txt"));
fclose($abrir);

$conteudo_p = explode(";;", $ler);

$data_inicio_p = $conteudo_p[0];
$data_fim_p    = $conteudo_p[1];
$comentario_p  = $conteudo_p[2];
$promocao = "f";

if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) { // DATA DO BLOQUEIO
		$promocao = "t";
	}
}
//$promocao = "f";
//takashi alterar aqui
$cond2 = $_POST['condicao'];

if($login_fabrica == 3) setcookie ("cook_pedido",$_GET["pedido"],time()+(3600*48));


if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio"))) { // DATA DO BLOQUEIO
		$title     = "PEDIDO DE PEÇAS";
		$cabecalho = "PEDIDO DE PEÇAS";
		$layout_menu = 'pedido';

		if($login_posto <>6359 ) {
			include "cabecalho.php";

			echo "<br><br>\n";
			echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
			echo "<TR align='center' bgcolor='#336699'>";
			echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
			echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
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

$sql = "SELECT  tbl_posto_fabrica.codigo_posto      ,
				tbl_posto_fabrica.tipo_posto        ,
				tbl_posto_fabrica.pedido_faturado   ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_posto_fabrica.escolhe_condicao  ,
				tbl_posto_fabrica.condicao_escolhida,
				tbl_posto.cnpj                      ,
				tbl_posto.ie                        ,
				tbl_posto.nome                      ,
				tbl_posto.estado
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto USING(posto)
		WHERE   tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = @pg_query ($con,$sql);

if (@pg_num_rows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$codigo_posto       = trim(pg_fetch_result ($res_posto,0,codigo_posto));
$tipo_posto         = trim(pg_fetch_result ($res_posto,0,tipo_posto));
$nome_posto         = trim(pg_fetch_result ($res_posto,0,nome));
$cnpj               = trim(pg_fetch_result ($res_posto,0,cnpj));
$ie                 = trim(pg_fetch_result ($res_posto,0,ie));
$estado             = trim(pg_fetch_result ($res_posto,0,estado));
$pedido_faturado    = trim(pg_fetch_result ($res_posto,0,pedido_faturado));
$pedido_em_garantia = trim(pg_fetch_result ($res_posto,0,pedido_em_garantia));
$escolhe_condicao   = trim(pg_fetch_result ($res_posto,0,escolhe_condicao));
$condicao_escolhida = trim(pg_fetch_result ($res_posto,0,condicao_escolhida));

/* HD 23738 */
if ($escolhe_condicao == 't' AND $condicao_escolhida == "") {
	header ("Location: pedido_condicao.php");
	exit;
}

/* HD 23738 */
if ($escolhe_condicao == 't' AND $condicao_escolhida == "f") {
		$title     = "PEDIDO DE PEÇAS";
		$cabecalho = "PEDIDO DE PEÇAS";
		$layout_menu = 'pedido';

		include "cabecalho.php";

		echo "<br><br>\n";
		echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>CONDIÇÃO DE PAGAMENTO</B></TD>";
		echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>Cadastraremos a sua condição de pagamento em nosso sistema. A tela do site para a digitação dos pedidos de peças será liberada em até 24 horas.<BR><BR><BR> Black & Decker do Brasil</p></TD>";
		echo "</TR>";
		echo "</table>";
		include "rodape.php";
		exit;
}

if ($pedido_faturado == 'f') {
	$title     = "Pedido de Peças";
	$cabecalho = "Pedido de Peças";
	$layout_menu = "pedido";

	include "cabecalho.php";

	echo "
	<style type=\"text/css\">
	.menu_top { text-align: center; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: bold; border: 0px solid; color:'#ffffff'; background-color: '#596D9B'; }
	.table_line1 { font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: normal; border: 0px solid; }
	</style>";

	echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='100%' align='left' class='table_line1'>\n";
	echo "<b>Caro $nome_posto</b>, seu pedido de peças deve ser efetuado através de um distribuidor de sua região.\n";
	echo "<br><br>\n";
	echo "Abaixo relação de distribuidores por região:\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	$sql = "SELECT  tbl_posto.nome                                                                                 ,
					tbl_posto_fabrica.codigo_posto                                                                 ,
					tbl_posto.fone                                                                                 ,
					tbl_posto.contato                                                                              ,
					tbl_posto.email                                                                                ,
					(tbl_posto.endereco || ', ' || tbl_posto.numero || ' ' || tbl_posto.complemento) AS endereco   ,
					tbl_posto.bairro                                                                               ,
					tbl_posto.cidade                                                                               ,
					tbl_posto.estado                                                                               ,
					(substr(tbl_posto.cep,1,5) || '-' || substr(tbl_posto.cep,6,3))                     AS cep     ,
					(case tbl_posto.estado when '$estado' then '1' else '2' end )                       AS ordem
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 1
			WHERE   tbl_posto_fabrica.codigo_posto in ('22530','51167','40979','40399','56198','42308','10674','43743')
			ORDER BY ordem, tbl_posto.cidade";
	$res = @pg_query ($con,$sql);

/*
##Antes de 09/10/2007 eram essas condições##

FROM    tbl_posto
JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 1
WHERE   tbl_posto_fabrica.codigo_posto not in ('1122', '23513', '20741', '21957')
AND     ( tbl_posto_fabrica.tipo_posto IN (39, 79, 80, 81) OR tbl_posto_fabrica.tipo_posto = 40 )
ORDER BY ordem, tbl_posto.cidade";


*/


	for ($x=0; $x < pg_num_rows($res); $x++) {
		$nome     = trim(pg_fetch_result($res,$x,nome));
		$posto    = trim(pg_fetch_result($res,$x,codigo_posto));
		$estado   = trim(pg_fetch_result($res,$x,estado));
		$fone     = trim(pg_fetch_result($res,$x,fone));
		$contato  = trim(pg_fetch_result($res,$x,contato));
		$email    = trim(pg_fetch_result($res,$x,email));
		$endereco = trim(pg_fetch_result($res,$x,endereco));
		$bairro   = trim(pg_fetch_result($res,$x,bairro));
		$cidade   = trim(pg_fetch_result($res,$x,cidade));
		$cep      = trim(pg_fetch_result($res,$x,cep));

		if ($codigo_posto <> "21957" and $codigo_posto <> "20741") {
			echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='menu_top'><b>Distribuidor</b></td>\n";
			echo "<td align='center' class='menu_top'><b>Código</b></td>\n";
			echo "<td align='center' class='menu_top'><b>UF</b></td>\n";
			echo "<td align='center' class='menu_top'><b>Telefone</b></td>\n";
			echo "<td align='left'   class='menu_top'><b>Contato</b></td>\n";
			echo "<td align='center' class='menu_top'><b>E-mail</b></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='table_line1'>$posto - $nome</td>\n";
			echo "<td align='center' class='table_line1'>$posto</td>\n";
			echo "<td align='center' class='table_line1'>$estado</td>\n";
			echo "<td align='center' class='table_line1'>$fone</td>\n";
			echo "<td align='left'   class='table_line1'>$contato</td>\n";
			echo "<td align='left'   class='table_line1'>$email</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' align='center'>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='menu_top'><b>Endereço</b></td>\n";
			echo "<td align='center' class='menu_top'><b>Bairro</b></td>\n";
			echo "<td align='center' class='menu_top'><b>Cidade</b></td>\n";
			echo "<td align='center' class='menu_top'><b>CEP</b></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='table_line1'>$endereco</td>\n";
			echo "<td align='center' class='table_line1'>$bairro</td>\n";
			echo "<td align='center' class='table_line1'>$cidade</td>\n";
			echo "<td align='center' class='table_line1'>$cep</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<br>\n";
		}
	}

	echo "<br><br>\n";

	include "rodape.php";
	exit;
}

setcookie ("cook_pedido");
$cook_pedido = "";

//alterado HD 7325
//lpad(tbl_pedido.pedido_blackedecker,5,0) AS pedido_blackedecker trocado por tbl_pedido.pedido_blackedecker

$sql = "SELECT  tbl_pedido.pedido                                              ,
				tbl_pedido.pedido_blackedecker                                 ,
				tbl_pedido.seu_pedido
		FROM    tbl_pedido
		WHERE   tbl_pedido.exportado           ISNULL
		AND     tbl_pedido.controle_exportacao ISNULL
		AND     tbl_pedido.admin               ISNULL
		AND     (
			tbl_pedido.natureza_operacao ISNULL        OR
			tbl_pedido.natureza_operacao <> 'SN-GART' AND
			tbl_pedido.natureza_operacao <> 'VN-REV'
			)
		AND     tbl_pedido.pedido_os         IS NOT TRUE
		AND     tbl_pedido.pedido_acessorio  IS NOT TRUE
		AND     tbl_pedido.pedido_sedex      IS NOT TRUE
		 ";
		 //AND     tbl_pedido.condicao          NOT IN (62)

if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
	$sql .= "AND     tbl_pedido.tabela = 31 ";
}else{
	$sql .= "AND     tbl_pedido.tabela = 108 ";
}
// HD 22895
$sql .= "AND     tbl_pedido.posto             = $login_posto
		 AND     tbl_pedido.fabrica           = $login_fabrica
		 AND (tbl_pedido.status_pedido is null or tbl_pedido.status_pedido <>14);";
//if ($ip == '201.13.179.89') echo $sql;
//if ($ip == '201.71.54.144') echo $sql;
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
	$cook_pedido         = trim(pg_fetch_result($res,0,pedido));
	$pedido_blackedecker = trim(pg_fetch_result($res,0,pedido_blackedecker));
	$seu_pedido          = trim(pg_fetch_result($res,0,seu_pedido));
	//alterado HD 7325
	$pedido_blackedecker = "00000" . $pedido_blackedecker;
	$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));
	//---------------

	#HD 34403
	if (strlen($seu_pedido)>0){
		$pedido_blackedecker = fnc_so_numeros($seu_pedido);
	}

	$pedido_suframa      = "";
	$sql_suframa = "SELECT pedido_suframa FROM tbl_pedido WHERE pedido_suframa = $cook_pedido";
	$res_suframa = pg_query ($con,$sql_suframa);
	if (pg_num_rows ($res_suframa) > 0) {
	    $pedido_suframa = pg_fetch_result ($res_suframa,0,0);
	}

	setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
}

if (strlen($cook_pedido) > 0) {
	$sql = "SELECT  tbl_condicao.condicao,
					tbl_pedido.bloco_os
			FROM    tbl_pedido
			JOIN    tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE   tbl_pedido.pedido = $cook_pedido";
	$res = @pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$condicao = trim(pg_fetch_result($res,0,condicao));
		$bloco_os = trim(pg_fetch_result($res,0,bloco_os));;
	}
}

if (strlen($cook_pedido) > 0 and strlen($btngravar) == 0 and strlen($finalizar) == 0) {
	$res = pg_query($con, "BEGIN TRANSACTION");

	if (strlen($pedido_suframa) > 0) {
		$sql = "INSERT INTO tbl_pedido_item (
					pedido,
					peca  ,
					qtde  ,
					preco
			)
			SELECT  $cook_pedido          ,
					tbl_pedido_item.peca  ,
					tbl_pedido_item.qtde  ,
					tbl_pedido_item.preco
			FROM    tbl_pedido_item
			JOIN    tbl_pedido using (pedido)
			WHERE   tbl_pedido.pedido_suframa = $cook_pedido;";
		$res = pg_query ($con,$sql);

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro .= pg_errormessage ($con) ;
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fnc_pedido_delete ($cook_pedido, $login_fabrica, null)";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con);
			}
		}

		// HD 32120
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido, $login_fabrica)";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_GET["ignorar"]) > 0) {
	$ignorar = trim($_GET["ignorar"]);

	$sql = "SELECT fnc_pedido_delete ($ignorar, $login_fabrica, null)";
	$res = pg_query ($con,$sql);

	setcookie ("cook_pedido");
	$cook_pedido = "";

	header ("Location: $PHP_SELF");
	exit;
}

//HD 21009 - 27/5/2008
#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	$pedido = trim($_GET["pedido"]);

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_query ($con,$sql);

	$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
	$resP = @pg_query ($con,$sqlP);
	if(pg_num_rows($resP)==0){
		$sql = "DELETE FROM tbl_pedido
		WHERE  tbl_pedido.pedido = $pedido";
		$res = @pg_query ($con,$sql);
	}

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}else{
		header ("Location: $PHP_SELF");
		exit;
	}
}

//PROMOÇÂO BLACK
/*IGOR - HD 15890 - 18/03/08 - estava com errno pois o cook pedido está em branco quando vai gravar um pedido novo
então adicionei: strlen($cook_pedido) > 0
*/
if($promocao == 'f' AND ($btngravar == "Gravar" OR $finalizar == 1) AND strlen($cook_pedido) > 0 ) {
	$sql = "SELECT condicao FROM tbl_pedido WHERE pedido = $cook_pedido AND condicao in(957,958); ";
	$sql = "SELECT tbl_pedido.condicao
			FROM tbl_pedido
			JOIN tbl_condicao using(condicao)
			WHERE tbl_pedido.pedido = $cook_pedido AND tbl_condicao.promocao is true ";
	$res = @pg_query ($con,$sql);
	if(pg_num_rows($res) > 0){
		if(strlen($cond) == 0){
			$sql2 = "UPDATE tbl_pedido set condicao = null WHERE pedido = $cook_pedido AND fabrica = $login_fabrica; ";
			$res2 = pg_query($con,$sql2);
			$msg_erro = "O prazo para cadastro de promoção acabou. Por favor escolha outra condição de pagamento e clique em Gravar." ;
		}
	}
}


#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				WHERE  tbl_pedido.exportado isnull
				AND    tbl_pedido.pedido = $cook_pedido;";
		$res = @pg_query ($con,$sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
			setcookie ("cook_pedido");
			$cook_pedido = "";
		}


		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_pedido SET
						unificar_pedido = '$unificar'
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.unificar_pedido isnull;";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen (trim ($msg_erro)) == 0) {
			$sql = "INSERT INTO tbl_pedido_alteracao (
						pedido
					)VALUES(
						$cook_pedido
					)";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido,$login_fabrica)";
		$res = @pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($cook_pedido,$login_fabrica);";
		$res = @pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
/*		if (strlen (trim ($msg_erro)) == 0 AND strlen($pedido_em_garantia)>0) {
			$sql = "SELECT fn_black_pedido_garantia($cook_pedido)";
#			echo $sql.'<br>';
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
	}
*/
//echo "$cond2";

	$sql2 = "SELECT total, condicao FROM tbl_pedido WHERE pedido = $cook_pedido AND fabrica = $login_fabrica; ";
	$res2 = pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		$condicao_pag = pg_fetch_result($res2,0,condicao);
	}

	if($promocao == 't'){
		$sql = "SELECT tbl_black_posto_condicao.id_condicao  ,
						tbl_black_posto_condicao.condicao    ,
						tbl_condicao.limite_minimo           ,
						tbl_condicao.promocao
				FROM tbl_black_posto_condicao
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_condicao      ON tbl_condicao.condicao   = tbl_black_posto_condicao.id_condicao
				WHERE tbl_black_posto_condicao.posto = $login_posto
				AND   tbl_condicao.condicao = $condicao_pag ";
		if($promocao == 't'){
			$sql .= "UNION SELECT tbl_condicao.condicao as id_condicao, tbl_condicao.descricao as condicao, tbl_condicao.limite_minimo, tbl_condicao.promocao
			FROM tbl_condicao
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $login_posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_condicao.fabrica = $login_fabrica
			AND tbl_condicao.promocao is true
			AND   tbl_condicao.condicao = $condicao_pag";
		}
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res) > 0){
			$limite_minimo = pg_fetch_result($res,0,limite_minimo);
			$promocao2 = pg_fetch_result($res,0,promocao);

			$sql2 = "SELECT sum(preco*qtde) as total
						FROM tbl_pedido_item
						WHERE pedido = $cook_pedido; ";
			$res2 = pg_query ($con,$sql2);
			$total_pag = pg_fetch_result($res2,0,0);
			if($total_pag < $limite_minimo AND $promocao2 == 't'){
				$msg_erro = "O valor mínimo para esta condição de pagamento é de R$ ". number_format($limite_minimo, 2, ',', '.') .", por gentileza acrescente mais peças neste pedido, grave e finalize.";
				$sql3 = "UPDATE tbl_pedido set finalizado = null where pedido = $cook_pedido AND fabrica = $login_fabrica ; ";
				$res3 = pg_query($con,$sql3);

				# HD 225737
				if($login_fabrica == 1 and $login_posto == 5252 and $condicao_pag == 972) {
					$msg_erro = "";
					$sql3 = "SELECT fn_pedido_finaliza ($cook_pedido,$login_fabrica);";
					$res3 = pg_query($con,$sql3);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
//		$msg_erro = $total_pag;
//		$msg_erro = "$sql";
	}

	if(strlen($pedido_em_garantia)==0){
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
				WHERE  tbl_pedido.pedido = $cook_pedido
				AND    tbl_pedido.total <= 200
				AND    trim(tbl_condicao.codigo_condicao) <> '15'
				AND    trim(tbl_condicao.codigo_condicao) <> '30'
				AND    trim(tbl_condicao.codigo_condicao) <> '60'
				AND    trim(tbl_condicao.codigo_condicao) <> '90';";
		$res = @pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) $msg_erro = "
		<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
		<tr>
		<td align='left'>
		<font face='Verdana, Arial' size='2' color='#FFFFFF'>
		<b>Pedidos de valor até R$ 200,00 gerarão parcela única, sendo disponível estas opções</b>:<br>
		<UL>
			<LI>À VISTA ou 30 dias direto (sem taxa financeira);
			<LI>60 dias direto (3%);
			<LI>90 dias direto (6,10%)
		</UL>
		<br><center>Favor alterar a condição de pagamento e clicar em gravar.</center><br><br>
		</font>
		</td>
		</tr>
		</table>";

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.total >  200
					AND    tbl_pedido.total <= 400
					AND    trim(tbl_condicao.codigo_condicao) <> '15'
					AND    trim(tbl_condicao.codigo_condicao) <> '30'
					AND    trim(tbl_condicao.codigo_condicao) <> '47'
					AND    trim(tbl_condicao.codigo_condicao) <> '60'
					AND    trim(tbl_condicao.codigo_condicao) <> '76'
					AND    trim(tbl_condicao.codigo_condicao) <> '90';";
			$res = @pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) $msg_erro .= "
			<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
			<tr>
			<td align='left'>
			<font face='Verdana, Arial' size='2' color='#FFFFFF'>
			<b>Pedidos acima de R$ 200,00 e até R$ 400,00 gerarão duas parcelas, sendo disponível estas opções</b>:<br>
			<UL>
				<LI>30/60 dias (1,5%);
				<LI>60/90 dias (4,5%);
			</UL>
			e/ou
			<br>
			<UL>
				<LI>À VISTA ou 30 dias direto (sem taxa financeira);
				<LI>60 dias direto (3%);
				<LI>90 dias direto (6,10%)
			</UL>
			<br><center>Favor alterar a condição de pagamento e clicar em gravar.</center><br><br>
			</font>
			</td>
			</tr>
			</table>";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.total > 400
					AND    trim(tbl_condicao.codigo_condicao) <> '15'
					AND    trim(tbl_condicao.codigo_condicao) <> '30'
					AND    trim(tbl_condicao.codigo_condicao) <> '47'
					AND    trim(tbl_condicao.codigo_condicao) <> '60'

					AND    trim(tbl_condicao.codigo_condicao) <> '76'
					AND    trim(tbl_condicao.codigo_condicao) <> '90'
					AND    trim(tbl_condicao.codigo_condicao) <> '191';";
					//AND    trim(tbl_condicao.codigo_condicao) <> '62'
			$res = @pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) $msg_erro .= "Pedidos acima de R$ 400,00 gerarão três parcelas, sendo disponível estas opções:<br>
			<UL>
				<LI>30/60/90 dias (3%);
				<LI> 60/90/120 dias (6,10%);
			</UL>
			e/ou
			<br>
			<UL>
				<LI>À VISTA ou 30 dias direto (sem taxa financeira);
				<LI>60 dias direto (3%);
				<LI>90 dias direto (6,10%)
			</UL>
			e/ou
			<UL>
				<LI>30/60 dias (1,5%);
				<LI>60/90 dias (4,5%);
			</UL>
			<br>
			<br>Favor alterar a condição de pagamento e clicar em gravar.<br><br>";
		}

	}

	if (strlen($msg_erro) == 0 and strlen($cook_pedido)>0) {
		$msg = $_GET['msg'];
		header ("Location: pedido_blackedecker_finalizado_new.php?msg=$msg&pedido=$cook_pedido");
		exit;
	}

}

#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar") {
	$condicao = trim($_POST['condicao']);
#	$bloco_os = intval(trim($_POST['bloco_os']));
	$bloco_os = 0;

	if (strlen($bloco_os) == 0) {
		$aux_bloco_os = 0;
	}else{
		if (is_int($bloco_os) == false) {
			$aux_bloco_os = 0;
		}else{
			$fnc          = pg_query($con,"SELECT fnc_so_numeros('$bloco_os')");
			$aux_bloco_os = pg_fetch_result ($fnc,0,0);
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen ($condicao) == 0 AND strlen ($cook_pedido)== 0) {
			$msg_erro .= "Escolha a condição de pagamento";
		}
	}

	if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.sigla_tabela = 'BASE1'";
	}else{
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.sigla_tabela = 'BASE2'";
	}
	$res = @pg_query ($con,$sql);
	if (pg_num_rows ($res) > 0) $tabela = pg_fetch_result ($res,0,0);

	if (strlen($msg_erro) == 0){
		$res = pg_query($con, "BEGIN TRANSACTION");

		if (strlen ($cook_pedido) == 0) {

			// IGOR - ATENÇÃO: FOI ADICIONADO PARA OS POSTOS QUE TEM PEDIDO EM GARANTIA
			if($condicao=="62"){
				$des_tipo_pedido = "GARANTIA";
			}else{
				$des_tipo_pedido = "FATURADO";
			}

			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						condicao       ,
						tabela         ,
						bloco_os       ,
						fabrica        ,
						tipo_pedido    ,
						unificar_pedido
					)VALUES(
						$login_posto  ,
						'$condicao'   ,
						'$tabela'     ,
						$aux_bloco_os ,
						$login_fabrica,
						(SELECT tipo_pedido
						 FROM tbl_tipo_pedido
						 WHERE fabrica = $login_fabrica
							AND UPPER(trim(descricao)) = '$des_tipo_pedido' ),
						't'
					)";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}else{
				$res = pg_query ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido = pg_fetch_result ($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
			}
		}else{
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.exportado isnull
					AND    tbl_pedido.pedido = $cook_pedido;";
			$res = @pg_query ($con,$sql);

			if (pg_num_rows($res) == 0) {
				$msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
				setcookie ("cook_pedido");
				$cook_pedido = "";
			}

			if (strlen($msg_erro) == 0) {
				// IGOR - ATENÇÃO: FOI ADICIONADO PARA OS POSTOS QUE TEM PEDIDO EM GARANTIA
				if($condicao=="62"){
					$des_tipo_pedido = "GARANTIA";
				}else{
					$des_tipo_pedido = "FATURADO";
				}

				$sql = "UPDATE tbl_pedido SET
							tabela     = '$tabela'       ,
							condicao   = '$condicao'     ,
							tipo_pedido = 	(SELECT tipo_pedido
											 FROM tbl_tipo_pedido
											 WHERE fabrica = $login_fabrica
												AND UPPER(trim(descricao)) = '$des_tipo_pedido' ),
							bloco_os   = '$aux_bloco_os' ,
							total      = null            ,
							finalizado = null
						WHERE tbl_pedido.pedido = $cook_pedido;";
				$res = @pg_query ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro .= pg_errormessage ($con) ;
				}
			}

			if (strlen (trim ($msg_erro)) == 0) {
				$sql = "INSERT INTO tbl_pedido_alteracao (
							pedido
						)VALUES(
							$cook_pedido
						)";
				$res = @pg_query ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro .= pg_errormessage ($con) ;
				}
			}
		}

		/* hd 19525 - retirei em 08/05/2008, se ocorrer algum problema voltar e retirar a vlaidação de itens da função
		if (strlen($cook_pedido) > 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
		*/

		if (strlen (trim ($msg_erro)) == 0) {
			$msg = "";
			for ($i = 0 ; $i < 15 ; $i++) {
				$referencia = trim($_POST["referencia" . $i]);
				$qtde       = trim($_POST["qtde"       . $i]);

				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);

				$xreferencia = strtoupper($xreferencia);
//takashi 26-01 HD1115
/*				if($xreferencia=="56224401"){
					$msg_erro .= "ITEM 562244-01 EM ANÁLISE, ENTRE EM CONTATO COM A FÁBRICA.<BR>";
				}
				if($xreferencia=="64789600"){
					$msg_erro .= "ITEM 647896-00 EM ANÁLISE, ENTRE EM CONTATO COM A FÁBRICA.<BR>";
				}
				if($xreferencia=="18544100"){
					$msg_erro .= "ITEM 185441-00 EM ANÁLISE, ENTRE EM CONTATO COM A FÁBRICA.<BR>";
				}
*/
				if ((strlen($referencia) > 0) AND(strlen($msg_erro)==0)) {
					$sql = "SELECT tbl_peca.peca
							FROM   tbl_peca
							WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'
							AND    tbl_peca.fabrica             = $login_fabrica";
					$resX = pg_query ($con,$sql);

					if (pg_num_rows($resX) > 0 AND strlen (trim ($qtde)) > 0 AND $qtde > 0) {
						$peca = pg_fetch_result($resX,0,0);

						$sqlY = "SELECT	a.oid    ,
										a.*      ,
										tbl_peca.peca,
										referencia,
										descricao
								FROM	tbl_peca
								JOIN	(
											SELECT	oid,*
											FROM	tbl_pedido_item
											WHERE	pedido = $cook_pedido
										)
										a ON tbl_peca.peca = a.peca
										ORDER BY a.pedido_item";
						$resY = pg_query ($con,$sqlY);
						if(pg_num_rows($resY) > 0){
							for ($j = 0 ; $j < @pg_num_rows ($resY) ; $j++) {
								$aux_peca = @pg_fetch_result ($resY,$j,peca);
								if($aux_peca == $peca){
									$msg_erro = "Peça $referencia em destaque em duplicidade, favor retirar!";
									$linha_erro = $i;
								}
							}
						}

						if(strlen($msg_erro) == 0){

							$sql = "INSERT INTO tbl_pedido_item (
									pedido,
									peca  ,
									qtde
								)VALUES(
									$cook_pedido,
									$peca       ,
									$qtde
								)";
							$res = @pg_query ($con,$sql);
							$msg_erro .= @pg_errormessage($con);



							if (strlen($msg_erro) == 0) {
								$res         = @pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
								$pedido_item = @pg_fetch_result ($res,0,0);
								$msg_erro .= pg_errormessage($con);
							}

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT fn_valida_pedido_item ($cook_pedido,$peca,$login_fabrica)";
								$res = @pg_query ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}

							if (strlen ($msg_erro) > 0) {
								$linha_erro = $i;
								$erro_linha = "erro_linha" . $i;
								$$erro_linha = 1 ;
								break ;
							}
						}
						// HD 32120
						if (strlen($msg_erro) == 0) {
							$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido, $login_fabrica)";
							$res = @pg_query ($con,$sql);

							if (strlen ( pg_errormessage ($con) ) > 0) {
								$msg_erro .= pg_errormessage ($con);
							}
						}
					}else{
						if (strlen (trim ($qtde)) > 0 AND $qtde > 0) {
							$msg_erro = "Item $referencia não existe, Consulte a vista explodida atualizada e verifique o código correto.";
						}else{
							$msg_erro = "Favor informar a quantidade para o item $referencia.";
						}

						if (strlen ($msg_erro) > 0) {
							$erro_linha = "erro_linha" . $i;
							$$erro_linha = 1 ;
							$linha_erro = $i;
							break ;
						}
					}
				}

				##### VERIFICA A QTDE DA PEÇA NO DO MÊS ANTERIOR #####
/*				if (strlen($msg_erro) == 0 && strlen($peca) > 0 && strlen($xreferencia) > 0) {
					$data_i = date("Y-m-d", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
					$data_f = date("Y-m-t", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
					$sql =	"SELECT SUM(tbl_pedido_item.qtde)
							FROM tbl_pedido_item
							JOIN tbl_pedido      ON tbl_pedido.pedido           = tbl_pedido_item.pedido
							JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
							JOIN tbl_peca        ON tbl_peca.peca               = tbl_pedido_item.peca
							WHERE tbl_pedido_item.peca = $peca
							AND   tbl_pedido.data BETWEEN '$data_i 00:00:00' AND '$data_f 23:59:59'
							AND   tbl_pedido.pedido NOT IN ($cook_pedido)
							AND   UPPER(TRIM(tbl_tipo_pedido.descricao)) = 'FATURADO';";
					$res = pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$qtde_total_mes = pg_fetch_result($res,0,0);
						if (strlen($qtde_total_mes) > 0 && $qtde > $qtde_total_mes) {
							$msg .= " A quantidade $qtde pedida para a peça $referencia está acima da média de $qtde_total_mes pedida normalmente. Você tem certeza que a quantidade pedida está correta? ";
						}
					}
				}*/
			}
		}


		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=".$msg);
			exit;
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title     = "PEDIDO DE PEÇAS";
$cabecalho = "PEDIDO DE PEÇAS";

$layout_menu = 'pedido';

if(!empty($cook_pedido)) {
	$sql = "SELECT pedido
			FROM tbl_pedido
			WHERE pedido = $cook_pedido
			AND   fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) == 0){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
}

include "cabecalho.php";

/*Entra so se for posto atendido pela black*/
$sql2 = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('10068', '10086', '10097', '10120', '10177', '10240', '10345', '10358', '10378', '10678', '10844', '11147', '11228', '11290', '12004', '12008', '12009', '12010', '12011', '12012', '12012', '12016', '12017', '12019', '12027', '12030', '12058', '12059', '12115', '12120', '12124', '12129', '12138', '13008', '13014', '13031', '13035', '13053', '13072', '13074', '13076', '13077', '13088', '13109', '13114', '13128', '13136', '13150', '13155', '13161', '13201', '13270', '13515', '13516', '13632', '13635', '13695', '13715', '13786', '13812', '14048', '14049', '14055', '14135', '14162', '14166', '14187', '14189', '14228', '14246', '14426', '14675', '14975', '15007', '15024', '15026', '15031', '15034', '15036', '15037', '15046', '15047', '15097', '15111', '15113', '15199', '16001', '20034', '20165', '20223', '20274', '20285', '20312', '20322', '20333', '20336', '20403', '20439', '20621', '20653', '20670', '20763', '20920', '20998', '21061', '21139', '21150', '21163', '21215', '21217', '21272', '21292', '21350', '21362', '21436', '21464', '21661', '21815', '21870', '21914', '21957', '22002', '22010', '22086', '22116', '22426', '22439', '22457', '22612', '22624', '22625', '22626', '22632', '22689', '22720', '22941', '23019', '23098', '23111', '23135', '23150', '23155', '23158', '23160', '23163', '23163', '23193', '23194', '23195', '23215', '23225', '23227', '23241', '23284', '23290', '23292', '23293', '23331', '23340', '23345', '23350', '23354', '23355', '23358', '23359', '23360', '23361', '23364', '23368', '23369', '23373', '23510', '23511', '23513', '23642', '23915', '23921', '23925', '24142', '24212', '24327', '24367', '24394', '24567', '26934', '26942', '26943', '26946', '26948', '26949', '26954', '26955', '26956', '26957', '26958', '29067', '29078', '29079', '30003', '30048', '30052', '31057', '31351', '32007', '32022', '32027', '32057', '32058', '32074', '32085', '32855', '33009', '33018', '33027', '33029', '33034', '34022', '34087', '35021', '35053', '35066', '35143', '36033', '36039', '36086', '36107', '36307', '36778', '36888', '37040', '37043', '38021', '38034', '38036', '38039', '38774', '39075', '39512', '39600', '39730', '39870', '39874', '40031', '40077', '40092', '40113', '40305', '40399', '40563', '40979', '41086', '41272', '41665', '41683', '42076', '42124', '42308', '42362', '42375', '42376', '42408', '43380', '43743', '45007', '45127', '45889', '48838', '50061', '50087', '50139', '51092', '51097', '51167', '51168', '51199', '51734', '51735', '51738', '51771', '51774', '51782', '51787', '52006', '52021', '52028', '52043', '52047', '52051', '52055', '52200', '52208', '53129', '53130', '53155', '53175', '53463', '54006', '54020', '54025', '54029', '54032', '54037', '54038', '54049', '54050', '54089', '55036', '55122', '55142', '55143', '55153', '55155', '55159', '55170', '55200', '55241', '55270', '56016', '56089', '56153', '56176', '56198', '56267', '56305', '56368', '56463', '56875', '56876', '56977', '57215', '57432', '57582', '57719', '57873', '57874', '57972', '58022', '58036', '58142', '58219', '58329', '58558', '58774', '10121', '10341', '10454', '10665', '58414', '10674', '10843', '11399', '12137', '43745', '43244', '10698', '11529', '12015', '12031', '58876', '42450', '12045', '12124', '12127', '23154', '12129', '14017', '14300', '14717', '14975', '15022', '15035', '15040', '15045', '34069', '15048', '15101', '20072', '20331', '20341', '43144', '42300', '20370', '26966', '54062', '13513', '41689', '40142', '42307', '20398', '20417', '20741', '21149', '21302', '21351', '21480', '21551', '21565', '21972', '22007', '22497', '22530', '22585', '22588', '20490', '22627', '22631', '42385', '22670', '22710', '22893', '23156', '23167', '23183', '23184', '58589', '23413', '23189', '23197', '23283', '23298', '23316', '23371', '23372', '23374', '23380', '23381', '23382', '23387', '23388', '23440', '23554', '23980', '24348', '26950', '26953', '26955', '26960', '26961', '26962', '26963', '27907', '27908', '27909', '29060', '29062', '29083', '30002', '31021', '31129', '31141', '31290', '31310', '31395', '32008', '32024', '32029', '34135', '36788', '37583', '38038', '39545', '39600', '39692', '39791', '39874', '40007', '40049', '40052', '40053', '40082', '40092', '40097', '40143', '40444', '40598', '40885', '40907', '40921', '41066', '41176', '41221', '41280', '41309', '41472', '41694', '41979', '42031', '42125', '42270', '42280', '42297', '42404', '42409', '43027', '43051', '43201', '43779', '43818', '44003', '45010', '45126', '50004', '51782', '52198', '53440', '53441', '54024', '54056', '55026', '55049', '55146', '55874', '56047', '56097', '56146', '56376', '56480', '57299', '57602', '58108', '58423', '58879', '84481', '99065', '01122')";
$res2 = pg_query ($con,$sql2);

if (pg_num_rows ($res2) > 0) {
$btn_condicao = $_POST['btn_condicao'];
	if ($btn_condicao == 'Confirmar') {
		$condicao = $_POST['condicao'];
/*takashi -  nao estava gravando id_condicao de pagamento - 11-01-07*/
		if($condicao == "30/60/90DD (financeiro de 3%)"){ $id_condicao = "55"; }
		if($condicao == "30/60DD (financeiro de 1,5%)"){  $id_condicao = "53"; }
		if($condicao == "30DD (sem financeiro)"){         $id_condicao = "51"; }
		if($condicao == "45DD (financeiro 1,5%)"){        $id_condicao = "52"; }
		if($condicao == "60/90/120DD (financeiro 6,1%)"){ $id_condicao = "57"; }
		if($condicao == "60/90DD (financeiro 4,5%)"){     $id_condicao = "73"; }
		if($condicao == "60DD (financeiro 3%)"){          $id_condicao = "54"; }
		if($condicao == "90DD (financeiro 6,1%)"){        $id_condicao = "56"; }
/*takashi -  nao estava gravando id_condicao de pagamento - 11-01-07*/

		$sql = "INSERT INTO tbl_black_posto_condicao (posto, condicao, id_condicao) VALUES ($login_posto, '$condicao', $id_condicao)";
			$resX = pg_query ($con,$sql);
#----------- Enviar email de Confirmação de Leitura -----------#
		$sql = "SELECT codigo_posto from tbl_posto_fabrica where fabrica=$login_fabrica and posto=$login_posto";
		$res = pg_query($con, $sql);
		$codigo_postoo = pg_fetch_result($res,0, codigo_posto);

		$remetente_email = "takashi@telecontrol.com.br";
		$assunto      = "Condição de Pagamento de pedido Telecontrol";
		$corpo        = "O Posto <B>$codigo_postoo</b> escolheu uma condição de pagamento <B>( $condicao )</B> do pedido TELECONTROL";

		$email_origem = "Telecontrol Assist <helpdesk@telecontrol.com.br>";

		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		@mail($remetente_email, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " );
		/*envia email pra rubia*/
			echo "<script language='javascript'> location.href=\"$PHP_SELF\" ; </script>";
			exit;
	}
	$sql2 = "SELECT id_condicao
			FROM tbl_black_posto_condicao
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
			WHERE tbl_black_posto_condicao.posto = $login_posto
				AND tbl_posto_fabrica.pedido_faturado = 't'";
	$res2 = pg_query ($con,$sql2);
//	if($ip=="200.232.184.76") echo "$sql2";
	if (pg_num_rows ($res2) == 0) {
		echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
		echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "	<TD>Para dar início a digitação de pedidos de peça,  por gentileza leia o comunicado abaixo relativo ao faturamento de peças.<BR><BR>
		Por exigência da Corporação a Black & Decker do Brasil estará mudando seu software operacional e essa alteração implicará em nossa sistemática de faturamento. No entanto, a única mudança que irá interferir para o posto de serviço é relativa às condições de pagamento dos pedidos. Explicando melhor, com o novo sistema, o posto de serviço poderá determinar sua condição de pagamento, porém, após essa escolha a condição será padronizada e fixa para todos seus pedidos sem opção de alterá-la.<BR><BR>
		IMPORTANTE: A condição escolhida agora será permanente e única para todos os pedidos.<BR><BR>Condições a escolher:<BR><BR>";

		echo "<select name='condicao' size='1'>";
		echo "<option value='30DD (sem financeiro)'        >30DD (sem financeiro)        </option>";
		echo "<option value='30/60DD (financeiro de 1,5%)' >30/60DD (financeiro de 1,5%) </option>";
		echo "<option value='30/60/90DD (financeiro de 3%)'>30/60/90DD (financeiro de 3%)</option>";
		echo "<option value='45DD (financeiro 1,5%)'       >45DD (financeiro 1,5%)       </option>";
		echo "<option value='60DD (financeiro 3%)'         >60DD (financeiro 3%)         </option>";
		echo "<option value='60/90DD (financeiro 4,5%)'    >60/90DD (financeiro 4,5%)    </option>";
		echo "<option value='60/90/120DD (financeiro 6,1%)'>60/90/120DD (financeiro 6,1%)</option>";
		echo "<option value='90DD (financeiro 6,1%)'       >90DD (financeiro 6,1%)       </option>";
		echo "</select><BR>";
		echo "Desde já agradecemos à compreensão.<BR><BR>
				Departamento de Assistência Técnica<BR><BR>
				Black & Decker do Brasil<BR>
	<center><input type='submit' name='btn_condicao' value='Confirmar'></center></TD>";
		echo "</form>";
		echo "</TR>";
		echo "</table>";
		include "rodape.php";
		exit;
	}
/*VERIFICA SE ELE JA ESCOLHEU A CONDICAO DE PAGAMENTO*/
}
?>

<!-- ---------------------- Inicio do HTML -------------------- -->

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px !important;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
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

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}


table.tabela tr td{
    font-family: Arial;
	font-weight: bold;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold "Arial";
	font-weight: bold;
	font-size: 14px !important;
    color:#FFFFFF;
    text-align:center;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
</style>

<?
if ($alterar == 1) {
?>



<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="table_line1">
		<b>Antes de lançar Pedido ou OS´s, por favor, <a href='cad_posto.php'>clique aqui</a> <br>e complete seu CNPJ e Inscrição Estadual</b>
	</td>
</tr>
</table>

<?}else{?>

<script LANGUAGE="JavaScript">
<!--
/*
function FuncPesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_blackedecker.php?peca=" + peca_referencia.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=50,left=100");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.qtde			= peca_qtde;
		janela.focus();
	}else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }
}
*/

 function fnc_pesquisa_peca (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }
	
    if (xcampo.value != "") {
        var url = "";
        url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
        janela.retorno = "<? echo $PHP_SELF ?>";
        janela.referencia= campo;
        janela.descricao= campo2;
        janela.focus();
    }else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }
}

function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;

   if((tecla > 47 && tecla < 58)) return true;
    else{
    if (tecla != 8 && tecla != 0) return false;
    else return true;
    }
}

function fnc_fora_linha (nome, seq) {
	var url = "";
	if (nome != "") {
		url = "pesquisa_fora_linha.php?nome=" + nome + "&seq=" + seq;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}
//-->

<!-- Início
nextfield = "condpgto"; // coloque o nome do primeiro campo do form
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frmpedido.' + nextfield + '.focus()');
			return false;
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP);
// Fim -->

</script>

<? include "javascript_pesquisas.php" ;?>
<script type='text/javascript'>
<!--
 function fnc_pesquisa_peca (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }
	
    if (xcampo.value != "") {
        var url = "";
        url = "peca_pesquisa_lista_blackedecker_369636.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
        janela.retorno = "<? echo $PHP_SELF ?>";
        janela.referencia= campo;
        janela.descricao= campo2;
        janela.focus();
    }else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }
}
//-->
</script>
	<br>
	<table width="700" border="0" align="center"  class="texto_avulso">
<?
//if (strlen($cook_pedido) > 0) {
//	echo "<br>";
//	echo "<div class='contentBlockMiddle' style='width: 600px'>";
//	echo "<table border='0' cellpadding='0' cellspacing='0'>";
//	echo "<tr>";
//	echo "<td><img border='0' src='imagens/esclamachion1.gif'></td>";
//	echo "<td align='center'><b>Para que o pedido $pedido_blackedecker seja enviado para a fábrica, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.</b></td>";
//Para que o pedido 62301 seja enviado para a fábrica às 13h30, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.
//Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.
//	echo "<td align='center'><b>Para que o pedido $pedido_blackedecker seja enviado para a fábrica às 13h30, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.  Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.</b></td>";
//	echo "<td align='center'><b>Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente. </b></td>";
//	echo "</tr>";
//	echo "</table>";
//	echo "</div>";
//}
if (strlen($cook_pedido) > 0) {
?>
	<br>
	<table width="700" border="0" align="center"  class="texto_avulso">
		<tr class='titulo_tabela'>
			<td>Informações sobre Finalização dos Pedidos</td>
		</tr>
	
		<tr>
			<? //hd 21119
			//<td align='center' bgcolor='#f4f4f4'>
			//<p align='justify'><font size='1'><b> Para que o pedido <? echo $pedido_blackedecker ?>
			<?//seja enviado para a fábrica às 11:45h, grave e finalize o pedido novamente, antes de sair da tela //de digitação de pedidos.</b></font></p>
			//</td>
			?>
			<td align='center' style='text-align:justify'>
			Para que o pedido <? echo $pedido_blackedecker ?> seja enviado para a fábrica às 11h45, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.
			</td>
		</tr>

		<tr>
			<td align='center' style='text-align:justify; padding: 5px'>
				Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.
			</td>
		</tr>

			
		<?
		}?>

		<?
		//Chamado: 1757
		$fiscal = fopen("/www/assist/www/periodo_fiscal.txt", "r");
		$ler_fiscal = fread($fiscal, filesize("/www/assist/www/periodo_fiscal.txt"));
		fclose($fiscal);
		if($login_fabrica <>3){
		?>
		<tr>
			<td align='center' style='text-align:justify; padding: 5px' class='texto_avulso'>Data limite para colocação de pedidos neste mês: <font color='#ff0000'><? echo $ler_fiscal; ?></font></td>
		</tr>
	</table>
	
	<?
	if (strlen($msg) > 0) $msg_erro .= $msg;

		if (strlen ($msg_erro) > 0) {
			echo "<br>";
			if (strpos ($msg_erro,"Fail to add null value in not null attribute peca") > 0)
				$msg_erro .= "Peça não existe";

			if (strpos ($msg_erro,'update or delete on "tbl_pedido" violates foreign key constraint "$3" on "tbl_pedido') > 0)
				$msg_erro .= "Não foi possível gerar pedido SUFRAMA. Por favor, entre em contato com o administrador.";
			?>
		
		<? if(strlen(trim($msg_erro)) > 0){?>
			<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
				<tr>
					<td valign="middle" align="center" class='msg_erro'>
				<?
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

					echo $erro . $msg_erro;
				?>
					</td>
				</tr>
			</table>
		<?php }?>
		<br>
		<? } ?>
	
<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<br>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="formulario">
<tr>
	<td class='titulo_tabela' >Envio de Pedidos Via Arquivos</td>
</tr>
<?
// Não haverá mais pedido unificado para blackedecker
	if (($tipo_posto == 39 or $tipo_posto == 79 or $tipo_posto == 80 or $tipo_posto == 81) and 1==2) { ?>
<tr >
	<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (DISTRIBUIDOR)</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_distribuidor.doc' target='_blank'>Clique aqui</a></b></td>
</tr>
<? } ?>

<? if ($pedido_em_garantia == "t" and 1==2) { ?>
<tr>
	<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (GARANTIA)</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_garantia.doc' target='_blank'>Clique aqui</a></b></td>
</tr>
<? }

?>
<?
/*
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** CALENDÁRIO FISCAL</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>Clique aqui</a></b></td>
</tr>
*/

?>
<tr >
	<td align='center' style='font:13px Arial'><b><a href='pedido_upload.php' target='blank'><input type="button" value="Enviar Arquivo" /></a></b></td>
</tr>
</table>

<?}?>

<!--
<? if ($cook_pedido > 0) { ?>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#f4f4f4'>
		<p align='justify'><font size=1>
		<font color='#FF0000'><b>O SEU PEDIDO NÚMERO</b>: <b><? echo $pedido_blackedecker ?> SERÁ EXPORTADO ÀS 13h30</font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE. SE O PEDIDO NÃO FOR FINALIZADO APÓS A INCLUSÃO DE NOVOS ITENS, SERÁ EXPORTADO PARA A BLACK & DECKER APENAS O PEDIDO FINALIZADO INICIALMENTE</b>.<br>
		</font></p>
	</td>
</tr>
</table>
<? } ?>
-->

<br>
<br>
<?
if($promocao=="t"){
		echo "<table width='700' border='0' align='center' cellpadding='1' cellspacing='1' class='formulario'>";
		echo "<TR>";
		//echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
		echo "	<TD colspan='3' class='titulo_tabela'>Comunicado Promoção</TD>";
		echo "</TR>";
		echo "<TR class='texto_avulso' >";
		echo "<TD style='text-align:justify'>$comentario_p</TD>";

	//	echo "</form>";
		echo "</TR>";
		echo "</table>";
		echo "<br><br>\n";
}

?>


<? //hd 50052 59207
/*<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>
<TR align='center' bgcolor='#336699'>
<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO</B></TD>
<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">
<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>
	<CENTER><B><U>"ATENÇÃO:  INVENTÁRIO 2008</U></B></CENTER>
	<BR><BR>
	Devido ao nosso inventário anual, informamos que o fechamento correspondente ao <B>mês de DEZEMBRO/2008 será no dia 12/12/08.</B> Receberemos pedidos de peças e acessórios somente até esta data, dentro do horário padrão às 11h45.
	<BR><BR>
	Retornaremos com o faturamento somente dia 05/01/2009. Desta forma, de 12/12/08 (após as 11h45) até 05/01/09, não disponibilizaremos o site para a digitação de pedidos.
	<BR><BR>
	Neste período a Black & Decker não receberá pedidos e não emitirá nota fiscal, ou seja, não haverá nenhuma movimentação no estoque, por se tratar do nosso período de inventário na fábrica. Por isso é de suma importância  que seja feita uma programação de peças com base neste período que a fábrica ficará sem atender, principalmente para as peças de maior giro, pois não podemos abrir exceções.
	<BR><BR>
	Qualquer dúvida, por gentileza entre em contato com Rúbia pelo telefone 34-3318-3024 ou com o suporte da sua região.
	<BR><BR>
	<U>Obs: DISTRIBUIDORES, FAVOR INFORMAR OS SEUS CLIENTES.</U>
	<BR><BR>
	Obrigada
	<BR><BR>
	Rúbia Lane Fernandes<BR>
	Analista de Faturamento<BR>
	Black & Decker do Brasil"<BR>
</p></TD>
</TR>
</table>
<br><br>
*/?>



<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="formulario">
<tr class="titulo_coluna">
	<td align='left'>
		Posto
	</td>

	<td align='left'>
		Razão Social
	</td>

	<td align='left'>
		Condição de Pagamento
	</td>

</tr>
<tr>
	<td align='left' class="table_line1" valign='top'>
		<b><? echo $codigo_posto; ?></b>
	</td>

	<td align='left' class="table_line1" valign='top'>
		<b><? echo $nome_posto; ?></b>
	</td>

	<td align='left' nowrap class="table_line1" valign='top'>


<? if(1==1) { ?>
	<select name="condicao" class="frm">
	<option value=''></option>
<?
//RAPHAEL
	$sql = "SELECT   tbl_condicao.*
			FROM     tbl_condicao
			JOIN     tbl_posto_condicao USING (condicao)
			WHERE    tbl_posto_condicao.posto = $login_posto
			AND      tbl_condicao.fabrica     = $login_fabrica
			AND      (tbl_posto_condicao.visivel IS TRUE)
			ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10, '0') ";
	//echo $sql;

	//nao deixar mostrar EM GARANTIA, pois existe uma tela para pedido em garantia que é habilitada no menu.

	$sql = "SELECT	tbl_black_posto_condicao.id_condicao,
					tbl_black_posto_condicao.condicao
			FROM tbl_black_posto_condicao
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_condicao on tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao and tbl_condicao.fabrica = $login_fabrica
			WHERE tbl_black_posto_condicao.posto = $login_posto
			AND  tbl_posto_fabrica.pedido_faturado is true
			AND tbl_posto_fabrica.fabrica = $login_fabrica ";
	if($promocao == 'f'){
		$sql .=" AND tbl_condicao.promocao is not true ";
	}else{
		//hd 100300 - QUER QUE APAREÇA AUTOMATICAMENTE QUANDO FOR PROMOÇÃO
		$sql .= "UNION SELECT tbl_condicao.condicao as id_condicao, tbl_condicao.descricao as condicao
				FROM tbl_condicao
				WHERE tbl_condicao.fabrica = $login_fabrica
				AND tbl_condicao.promocao is true order by condicao
				";
	}
	if($ip=='187.39.213.156') echo nl2br($sql);
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		for ($x=0; $x < pg_num_rows($res); $x++) {
			echo "<option "; if ($condicao == pg_fetch_result($res,$x,id_condicao)) echo " SELECTED "; echo " value='" . pg_fetch_result($res,$x,id_condicao) . "'>" . pg_fetch_result($res,$x,condicao) . "</option>\n";
		}
	}
	else {
		echo "<option value='51'>30DD (sem financeiro)</option>";
	}
}
?>
	</select>
	<br>
	</td>
</tr>
</table>

<br>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class="tabela">
<tr>
	<td width="25%" class="titulo_coluna">
		Referência
	</td>
	<td width="60%"  class="titulo_coluna">
		Descrição
	</td>
	<td width="15%" class="titulo_coluna">
		Quantidade
	</td>
</tr>
<?
for ($i = 0 ; $i < 15 ; $i ++) {
	$referencia = "referencia" . $i;
	$descricao  = "descricao" . $i;
	$qtde       = "qtde" . $i;
	$erro_linha = "erro_linha" . $i;

	$referencia = $$referencia;
	$descricao  = $$descricao;
	$qtde       = $$qtde;
	$erro_linha .= $$erro_linha;

	$prox = $i + 1;
	$done = 14;

	$cor_erro = "#ffffff";
	if ($erro_linha == 1) $cor_erro = "#FFCCCC";

	if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor_erro = "#FFCCCC";
        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>


<tr bgcolor='<?php echo $cor; ?>' height='25px'>
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" size="15" maxlength="15" value="<? echo $referencia ?>"  onFocus="nextfield ='qtde<?echo $i?>'" >
		<img src='imagens/lupa.png' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,'referencia');">
	</td>
	<td align='center'">
		<input type="text" name="descricao<? echo $i ?>" style='width: 370px;'  maxlength="30" value="<? echo $descricao ?>"  onFocus="nextfield ='qtde<?echo $i?>'">
		<img src='imagens/lupa.png' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,'descricao');">
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="7" maxlength="4" onkeypress='return SomenteNumero(event);' value="<? echo $qtde ?>"  <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>

<? } ?>

<tr>
	<td align='center' colspan='3'>
		
		<input type="hidden" name="btngravar" value="">

<? /*HD 1102*/  ?>
		<input type="button" value='Gravar' onclick="javascript: if (document.frmpedido.btngravar.value == '' ) { document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>

	</td>
</tr>
</table>

<br>

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center" class='texto_avulso'>
<tr>
	<td style="text-align:justify;" >
		Para continuar a digitar itens neste pedido, basta gravar e em seguida continuar digitando.
	</td>
</tr>

<tr>
	<td style="text-align:justify;">---</td>
</tr>

<tr>
	<td style="text-align:justify;" >
		Aviso: após gravar o seu pedido, irá aparecer o resumo dos itens lançados e abaixo deste resumo, terá o botão de finalização que somente será usado quando não existirem mais itens a serem lançados neste pedido.
	</td>
</tr>

<tr>
	<td style="text-align:justify;">---</td>
</tr>

<tr>
	<td  style='text-align:justify;'>
		Caso já tenha terminado de digitar os itens e queira passar para a próxima tela, clique em finalizar abaixo.
	</td>
</tr>

</table>
</form>


<?
# RESUMO DO PEDIDO!
if (strlen ($cook_pedido) > 0 OR strlen($pedido)>0) {
?>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class='tabela'>

<tr>
	<td colspan="5" class='titulo_tabela' >
		Resumo do Pedido
	</td>
</tr>

<tr>
	<td class="titulo_coluna">
		Referência
	</td>
	<td width="80%" align='center' class="titulo_coluna">
		Descrição
	</td>
	<td width="10%" align='center' class="titulo_coluna">
		Quantidade
	</td>
	<td width="2.5%" align='center' class="titulo_coluna">
		Preço
	</td>
	<td width="10%" align='center' class="titulo_coluna">
		Ações
	</td>
</tr>

<?
	$pedido = $cook_pedido;
	if($login_fabrica == 3) $pedido = $_GET["pedido"];
	$sql = "SELECT	a.oid    ,
					a.*      ,
					referencia,
					descricao
			FROM	tbl_peca
			JOIN	(
						SELECT	tbl_pedido_item.oid,tbl_pedido_item.*
						FROM	tbl_pedido_item
						JOIN    tbl_pedido USING(pedido)
						WHERE	pedido = $pedido
						AND     fabrica = $login_fabrica
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = pg_query ($con,$sql);
	$total = 0;

	for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F7F5F0' : "#F1F4FA";

		echo "<tr bgcolor='$cor'>";
		echo "<td width='20%' align='center' nowrap>";

//		echo "<a href='$PHP_SELF?delete=" . pg_fetch_result ($res,$i,pedido_item) . "&pedido=$pedido'>";
		
//		echo "<img src='imagens/btn_excluir.gif' align='absmiddle' hspace='5' border='0'>";
		

//		echo "</a>";
		echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='70%' align='left'>";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='10%' align='right'>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right'>";
		echo number_format (pg_fetch_result ($res,$i,preco),2,",",".");
		echo "</td>";
		
		echo "<td>";
			echo "<input type='button' value='EXCLUIR' onclick=\"window.location='$PHP_SELF?delete=" . pg_fetch_result ($res,$i,'pedido_item') . "&pedido=$pedido'\">";
		echo "</td>";


		echo "</tr>";

		$total = $total + (pg_fetch_result ($res,$i,preco) * pg_fetch_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="4" class="menu_top">
		<b>T O T A L</b>
	</td>
	<td align='right' class="menu_top" style='text-align:right'>
		<b>
		<? echo number_format ($total,2,",",".") ?>
		</b>
	</td>
</tr>
</table>



<!-- ============================ Botoes de Acao ========================= -->

<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">

<tr>
	<td align='center'>
<?
/*
$sql = "SELECT	tbl_posicao_pedido.pedido_mfg ,
				to_char(tbl_posicao_pedido.data_pedido, 'DD/MM/YYYY') AS data_pedido,
				(
					SELECT	tbl_posicao_status.status
					FROM	tbl_posicao_status
					WHERE	tbl_posicao_pedido.pedido_mfg = tbl_posicao_status.pedido_mfg
					ORDER BY tbl_posicao_status.data_status DESC LIMIT 1
				) AS status
		FROM tbl_posicao_pedido
		WHERE	trim(tbl_posicao_pedido.codigo_posto) = '$posto'
		ORDER BY tbl_posicao_pedido.data_pedido DESC LIMIT 1";
*/
$sql = "SELECT	*
		FROM	tbl_status_pedido
		LEFT JOIN tbl_pedido USING (status_pedido)
		LEFT JOIN tbl_faturamento USING(pedido)
		WHERE	tbl_pedido.posto = $login_posto
		AND		tbl_pedido.fabrica = $login_fabrica
		AND		tbl_pedido.pedido = $cook_pedido
		AND		tbl_status_pedido.status_pedido IN (4,5)";
$res = @pg_query ($con,$sql);

if (@pg_num_rows($res) > 0){
	$link  = "javascript:PedidoPendente();";
	echo "
			<script>
			function PedidoPendente(){
				if(confirm('UNIFICAÇÃO DOS PEDIDOS.\\n\\nDeseja somar as pendências do pedido ".trim(pg_fetch_result($res,0,pedido_mfg))." neste novo pedido ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
					window.location = '$PHP_SELF?finalizar=1&unificar=t&msg=1';
				}else{
					if(confirm('A pendência, após a finalização do seu novo pedido, será cancelada.\\n\\nConfirma a exclusão da pendência ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
						window.location = '$PHP_SELF?finalizar=1&unificar=f&msg=2';
					}
				}
			}
			</script>
			<br>\n";
			
}else{
	$link = "$PHP_SELF?finalizar=1&linha=$linha&unificar=t";
}

?>

		<INPUT TYPE="button" VALUE="Finalizar" style='cursor: pointer' ONCLICK="window.location='<? echo $link; ?>'">
	</td>
</tr>

</table>

<?
}
?>


<? } # Final do IF do CNPJ e IE ?>

<p>

<?include "rodape.php";?>
