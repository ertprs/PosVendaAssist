<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include "autentica_admin.php";
if( in_array($login_fabrica, array(11,172)) ){
	$admin_privilegios="auditoria";
}else{
	$admin_privilegios="gerencia";
}
include "funcoes.php";

$msg = "";

if($_GET['buscaCidade']){
	$uf = $_GET['estado'];

	if($uf == "BR-CO"){
		$estado = "'GO','MS','MT','DF'";
	} else if($uf == "BR-NE"){
		$estado = "'SE','AL','RN','MA','PE','PB','CE','PI','BA'";
	} else if($uf == "BR-N"){
		$estado = "'TO','PA','AP','RR','AM','AC','RO'";
	} else {
		$estado = "'$uf'";
	}
	$sql = "SELECT DISTINCT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and contato_estado in($estado) ORDER BY contato_estado,contato_cidade";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$retorno = "<option value=''>Todos</option>";
		for($i = 0; $i < pg_numrows($res); $i++){
			$cidade = pg_result($res,$i,'contato_cidade');
			$estado = pg_result($res,$i,'contato_estado');

			$nome_cidade = in_array($uf,array('BR-CO','BR-NE','BR-N')) ? "$cidade - $estado" : $cidade;
			$retorno .= "<option value='$cidade'>$nome_cidade</option>";
		}
	} else {
		$retorno .= "<option value=''>Cidade não encontrada</option>";
	}

	echo $retorno;
	exit;
}


if(strlen($_GET["os_aberta"])>0)    $os_aberta    = trim($_GET["os_aberta"]);   else  $os_aberta   = trim($_POST["os_aberta"]);

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ) {
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_produto.referencia like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

##### GERAR ARQUIVO EXCEL #####
if ($acao == "RELATORIO" && $login_fabrica != 72) {
	$x_data_inicial     = trim($_GET["data_inicial"]);
	$x_data_final       = trim($_GET["data_final"]);
	$produto            = trim($_GET["produto"]);
	$posto              = trim($_GET["posto"]);
	$os_aberta          = trim($_GET["os_aberta"]);
	$os_pedido_n_faturado = trim($_GET["os_pedido_n_faturado"]);

	if(strlen($produto) > 0) {
		$cond_produto = " AND tbl_os.produto = $produto";
	}

	if(strlen($posto) > 0) {
		$cond_posto = " AND tbl_os.posto = $posto";
	}

	//HD 229110: Filtro por OS Aberta
	if(strlen($os_aberta) > 0) {
		$cond_os_aberta = " AND tbl_os.finalizada IS NULL";
	}

	$tipo_os   = trim($_GET['tipo_os']);

	//HD 211825: Filtro de DEVOLUCAO DE VENDA para a Salton
	switch($tipo_os) {
		case "troca":
			$cond_tipo= "
			AND tbl_os.troca_garantia IS TRUE
			AND tbl_os.ressarcimento IS NOT TRUE
			AND tbl_os_troca.troca_revenda IS NOT TRUE ";
		break;

		case "ressarcimento":
			$cond_tipo= " AND tbl_os.ressarcimento IS TRUE ";
		break;

		case "revenda":
			$cond_tipo= " AND tbl_os_troca.troca_revenda IS TRUE ";
		break;

		case "troca_diferente":
			$campos_peca = " , tbl_peca.referencia AS peca_referencia, tbl_peca.descricao AS peca_descricao";
			$join_peca = " JOIN tbl_peca ON tbl_peca.peca = tbl_os_troca.peca";
			$cond_tipo= " AND tbl_produto.referencia <> tbl_peca.referencia ";
		break;

		default:
			$cond_tipo =" AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE )";
	}

	//HD 211825: Filtro por tipo de OS: Consumidor/Revenda
	if (strlen(trim($_GET["tipo_os_cr"]))) {
		$tipo_os_cr = $_GET["tipo_os_cr"];
		$cond_cr = " AND tbl_os.consumidor_revenda='$tipo_os_cr'";
	}

	//HD 211825: Filtro por CNPJ da Revenda
	if (strlen(trim($_GET["revenda_cnpj"]))) {
		$revenda_cnpj = preg_replace('/[^0-9]+/', '', $_GET["revenda_cnpj"]);
		if(strlen($revenda_cnpj) == 8) {
			$cond_revenda_cnpj = " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%'";
		}
	}

	#HD 260902
	$causa_troca = $_GET["causa_troca"];
	if(strlen($_GET["causa_troca"])==0) {
		$cond_causa_troca = "";
	} else {
		$cond_causa_troca = " AND tbl_os_troca.causa_troca = $causa_troca ";

	}

	$sql =	"SELECT tbl_posto_fabrica.posto                          AS posto               ,
				tbl_posto_fabrica.codigo_posto                       AS posto_codigo        ,
				tbl_posto.nome                                       AS posto_nome          ,
				tbl_os.sua_os                                                               ,
				tbl_os.os                                                                   ,
				tbl_os.ressarcimento                                                        ,
				tbl_os.serie                                                                ,
				tbl_produto.referencia                               AS produto_referencia  ,
				tbl_produto.descricao                                AS produto_descricao   ,
				(
					SELECT referencia
					FROM tbl_peca
					JOIN tbl_os_item    USING (peca)
					JOIN tbl_os_produto USING (os_produto)
					WHERE tbl_peca.produto_acabado
					AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS troca_por_referencia ,
				(
					SELECT descricao
					FROM tbl_peca
					JOIN tbl_os_item    USING (peca)
					JOIN tbl_os_produto USING (os_produto)
					WHERE tbl_peca.produto_acabado
					AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS troca_por_descricao ,
				(
					SELECT pedido
					FROM tbl_peca
					JOIN tbl_os_item    USING (peca)
					JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS pedido ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')           AS data_abertura        ,
				TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY HH24:MI')        AS data_troca           ,
				tbl_admin.login                                                              ,
				tbl_os_troca.ri                                                              ,
				tbl_os_troca.setor                                                           ,
				tbl_os_troca.situacao_atendimento                                            ,
				tbl_causa_troca.descricao                            AS causa_troca			 ,
				tbl_os_troca.troca_revenda
				$campos_peca
		FROM tbl_os
		JOIN tbl_admin            ON tbl_admin.admin            = tbl_os.troca_garantia_admin
		JOIN tbl_posto            ON tbl_posto.posto            = tbl_os.posto
		JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto    = tbl_posto.posto AND tbl_posto_fabrica.fabrica=$login_fabrica
		JOIN tbl_produto          ON tbl_produto.produto        = tbl_os.produto and tbl_produto.fabrica_i=$login_fabrica
		JOIN tbl_os_troca         ON tbl_os_troca.os            = tbl_os.os and tbl_os_troca.fabric=$login_fabrica
		$join_peca
		LEFT JOIN tbl_causa_troca ON tbl_causa_troca.causa_troca=tbl_os_troca.causa_troca and tbl_causa_troca.fabrica=$login_fabrica
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os_troca.data BETWEEN '$x_data_inicial' AND '$x_data_final'
		$cond_produto
		$cond_posto
		$cond_tipo
		$cond_revenda_cnpj
		$cond_cr
		$cond_os_aberta
		$cond_causa_troca
		ORDER BY tbl_os_troca.data;";
	#echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		$data = date("Y_m_d-H_i_s");
		$arq = fopen("/tmp/assist/relatorio-troca-produto-$login_fabrica-$data.html","w");

		fputs($arq,"<html>");
		fputs($arq,"<head>");
		fputs($arq,"<title>RELATÓRIO DE TROCA DE PRODUTO - ".date("d/m/Y H:i:s"));
		fputs($arq,"</title>");
		fputs($arq,"</head>");
		fputs($arq,"<body>");


		fputs($arq,"<table border='0' cellspacing='0' cellpadding='0' >");
		fputs($arq,"<tr height='18'>");
		fputs($arq,"<td width='18' bgcolor='#99d888'>&nbsp;</td>");
		fputs($arq,"<td align='left'><font size='1'><b>&nbsp; Ressarcimento Financeiro </b></font></td>");
		fputs($arq,"</tr>");

		if (in_array($login_fabrica, array(81, 114))) {
		fputs($arq,"<tr height='18'>");
		fputs($arq,"<td width='18' bgcolor='#d89988'>&nbsp;</td>");
		fputs($arq,"<td align='left'><font size='1'><b>&nbsp; Autorização de Troca pela Revenda </b></font></td>");
		fputs($arq,"</tr>");
		}

		fputs($arq,"</table>");

		fputs($arq,"<br>");
		fputs($arq,"<table width='750' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#596D9B' align='center' style='border-style: solid; border-color: #596D9B; border-width:1px;
font-family: Verdana;
font-size: 10px;'>");
		fputs($arq,"<tr height='15' class='Titulo'>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>OS</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Posto</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Produto</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Série</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Produto troca</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Abertura</td>");
		if( !in_array($login_fabrica, array(11,172)) ){
			fputs($arq,"<td background='imagens_admin/azul.gif'>Troca</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Pedido</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Responsável</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif' nowrap>Setor responsável</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif' nowrap>Situação do atendimento</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>RI</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Causa da Troca</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Ressarcimento</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Peças Originou a Troca</td>");
		}else{
			fputs($arq,"<td background='imagens_admin/azul.gif'>Data Troca</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Responsável</td>");
			fputs($arq,"<td background='imagens_admin/azul.gif'>Causa</td>");
		}
		fputs($arq,"</tr>");

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$posto                = trim(pg_result($res,$i,posto));
			$posto_codigo         = trim(pg_result($res,$i,posto_codigo));
			$posto_nome           = trim(pg_result($res,$i,posto_nome));
			$posto_completo       = $posto_codigo . " - " . $posto_nome;
			$sua_os               = trim(pg_result($res,$i,sua_os));
			$os                   = trim(pg_result($res,$i,os));
			$produto_referencia   = trim(pg_result($res,$i,produto_referencia));
			$serie                = trim(pg_result($res,$i,serie));
			$produto_descricao    = trim(pg_result($res,$i,produto_descricao));
			$produto_completo     = $produto_referencia . " - " . $produto_descricao;
			$troca_por_referencia = trim(pg_result($res,$i,troca_por_referencia));
			$troca_por_descricao  = trim(pg_result($res,$i,troca_por_descricao));
			$troca_por_completo   = $troca_por_referencia . " - " . $troca_por_descricao;
			$data_abertura        = trim(pg_result($res,$i,data_abertura));
			$data_troca           = trim(pg_result($res,$i,data_troca));
			$pedido               = trim(pg_result($res,$i,pedido));
			$login                = trim(pg_result($res,$i,login));
			$ressarcimento        = trim(pg_result($res,$i,ressarcimento));
			$ri                   = trim(pg_result($res,$i,ri));
			$setor                = trim(pg_result($res,$i,setor));
			$situacao_atendimento = trim(pg_result($res,$i,situacao_atendimento));
			$causa_troca          = trim(pg_result($res,$i,causa_troca));
			$troca_revenda        = trim(pg_result($res,$i,troca_revenda));

			//HD 211177: Quando é ressarcimento não há produto troca
			if ($ressarcimento == "t") {
				$troca_por_referencia = "";
				$troca_por_descricao  = "";
				$troca_por_completo   = "";
			}

			#HD 13502
			$pecas_originou_troca = array();

			$sql = "SELECT referencia
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					WHERE tbl_os_produto.os = $os
					AND tbl_os_item.originou_troca IS TRUE";
			$res2 = pg_exec($con,$sql);
			if (pg_numrows($res2) > 0) {
				for ($j=0; $j<pg_numrows($res2); $j++){
					array_push($pecas_originou_troca ,trim(pg_result($res2,$j,referencia)));
				}
			}

			if($situacao_atendimento == 0 AND strlen($situacao_atendimento)>0) $situacao_atendimento = "Garantia";
			elseif(strlen($situacao_atendimento)>0)                            $situacao_atendimento .= "%";

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if ($ressarcimento == "t") $cor = "#99d888";
			if ($troca_revenda == "t") $cor = "#d89988";

			if($ressarcimento == "t") $ressarcimento = "SIM";
			else                      $ressarcimento = "NÃO";
			fputs($arq,"<tr class='Conteudo' height='15' bgcolor='$cor'>");
			fputs($arq,"<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>");
			fputs($arq,"<td nowrap align='left'>$posto_codigo - $posto_nome</td>");
			if( in_array($login_fabrica, array(11,172)) ) {
				fputs($arq,"<td nowrap align='left'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>$produto_referencia</acronym></td>");
				fputs($arq,"<td nowrap align='left'><acronym style='cursor: hand;'>$serie</acronym></td>");
				fputs($arq,"<td nowrap align='left'><acronym title='REFERÊNCIA: $troca_por_referencia \n DESCRIÇÃO: $troca_por_descricao' style='cursor: hand;'>$troca_por_referencia</acronym></td>");
			} else {
				fputs($arq,"<td nowrap align='left'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>$produto_referencia - $produto_descricao</acronym></td>");
				fputs($arq,"<td nowrap align='left'><acronym style='cursor: hand;'>$serie</acronym></td>");
				fputs($arq,"<td nowrap align='left'><acronym title='REFERÊNCIA: $troca_por_referencia \n DESCRIÇÃO: $troca_por_descricao' style='cursor: hand;'>$troca_por_descricao</acronym></td>");
			}

			fputs($arq,"<td nowrap>$data_abertura</td>");
			if( !in_array($login_fabrica, array(11,172)) ){
			fputs($arq,"<td nowrap>$data_troca</td>");
			fputs($arq,"<td nowrap>$pedido</td>");
			fputs($arq,"<td nowrap align='left'>$login</td>");
			fputs($arq,"<td nowrap align='center'>$setor</td>");
			fputs($arq,"<td nowrap align='center'>$situacao_atendimento</td>");
			fputs($arq,"<td nowrap align='center'>$ri</td>");
			fputs($arq,"<td nowrap align='center'>$causa_troca</td>");
			fputs($arq,"<td nowrap align='center'>$ressarcimento</td>");
			fputs($arq,"<td nowrap align='center'>".implode(", ",$pecas_originou_troca)."</td>");
			}else{
				fputs($arq,"<td nowrap>$data_troca</td>");
				fputs($arq,"<td nowrap align='left'>$login</td>");
				fputs($arq,"<td nowrap align='center'>$causa_troca</td>");
			}
			fputs($arq,"</tr>");

			$posto_anterior  = $posto;
			$nota_fiscal     = null;
			$login           = null;
		}

		fputs($arq,"</table>");
		fputs($arq,"</body>");
		fputs($arq,"</html>");
		fclose($arq);

		rename("/tmp/assist/relatorio-troca-produto-$login_fabrica-$data.html", "/www/assist/www/admin/xls/relatorio-troca-produto-$login_fabrica-$data.xls");
//		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-troca-produto-$login_fabrica-$data.xls /tmp/assist/relatorio-troca-produto-$login_fabrica-$data.html`;
		echo "<br>";
		echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Relatório gerado com sucesso!<br><a href='xls/relatorio-troca-produto-$login_fabrica-$data.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá ver, imprimir e salvar a tabela para consultas off-line.</b></font></p>";
		exit;
	}
}

if (strlen($acao) > 0) {
	if($acao == "RELATORIO")
		$_POST = $_GET;
	//var_dump($_POST);
	if (!in_array($login_fabrica, array(11, 72, 80, 81,101, 114, 172))) {
		$mes = trim (strtoupper ($_POST['mes']));
		$ano = trim (strtoupper ($_POST['ano']));

		if(strlen($ano) == 0){
			$msg = "Escolha o Ano.";
		}
	}else{
		$x_data_inicial = trim($_POST["data_inicial"]);
		$x_data_final   = trim($_POST["data_final"]);
		if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

			if (strlen($x_data_inicial) > 0) {
				list($di, $mi, $yi) = explode("/", $x_data_inicial);
				if(!checkdate($mi,$di,$yi))
					$msg= "Data Inválida";
				$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
				$x_data_inicial = str_replace("'", "", $x_data_inicial);
				$dia_inicial    = substr($x_data_inicial, 8, 2);
				$mes_inicial    = substr($x_data_inicial, 5, 2);
				$ano_inicial    = substr($x_data_inicial, 0, 4);
				$xdata_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
			}else{
				$msg = "Data Inválida";
			}

			if (strlen($x_data_final) > 0) {
				list($df, $mf, $yf) = explode("/", $x_data_final);
				if(!checkdate($mf,$df,$yf))
					$msg = "Data Inválida";
				$x_data_final = fnc_formata_data_pg($x_data_final);
				$x_data_final = str_replace("'", "", $x_data_final);
				$dia_final    = substr($x_data_final, 8, 2);
				$mes_final    = substr($x_data_final, 5, 2);
				$ano_final    = substr($x_data_final, 0, 4);
				$xdata_final  = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
			}else if(strlen($msg) == 0){
				$msg = "Data Inválida";
			}

			if(strlen($x_data_final) > 0 && strlen($x_data_inicial) > 0)
				if(	$x_data_inicial > $x_data_final )
					$msg = "Data Inválida";

			if($acao == "RELATORIO" && strlen($_POST["data_inicial"]) && strlen($_POST["data_final"])){
				$x_data_inicial = implode("-", array_reverse(explode("/", $_POST['data_inicial'])));
				$x_data_final	= implode("-", array_reverse(explode("/", $_POST['data_final'])));
			}
			//die($x_data_inicial);

		}else{
			$msg = " Informe as datas corretas para realizar a pesquisa. ";
		}
	}

	if(in_array($login_fabrica, array(3, 80))) {
		$causa_troca = $_POST["causa_troca"];

		if(strlen($_POST["causa_troca"]) == 0) {
			$cond_causa_troca = "";

		} else{
			$cond_causa_troca = " AND tbl_os_troca.causa_troca = $causa_troca ";
		}
	}

	//HD 211825: Filtro por CNPJ da Revenda
	if (strlen(trim($_POST["revenda_cnpj"]))) {
		$revenda_cnpj = preg_replace('/[^0-9]+/', '', $_POST["revenda_cnpj"]);
		if (strlen($revenda_cnpj) != 8) {
			$msg = "Digite os 8 primeiros números do CNPJ da REVENDA";
		}
	}

	##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$os_pedido_n_faturado = trim($_POST["os_pedido_n_faturado"]);


	##### Filtro Cidade Estado ######
  $estado = trim($_POST["estado"]);
  $cidade = trim($_POST["cidade"]);

  ##### Filtro Consumidor Revenda ######
  $os_consumidor = trim($_POST["consumidor"]);
  $os_revenda = trim($_POST["revenda"]);

}

if( in_array($login_fabrica, array(11,172)) ){
	$layout_menu = "auditoria";
}else{
	$layout_menu = "gerencia";
}
$title = "RELATÓRIO DE TROCA DE PRODUTO";
if ($acao == "RELATORIO" && $login_fabrica == 72){
	ob_start();
}
else {
	include "cabecalho_new.php";
}
?>
<?php
if ($acao == "RELATORIO" && $login_fabrica == 72){
}
else {
?>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.espaco td{
	padding:10px 0 10px;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
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
}
</style>

<?php
$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric",
    "multiselect"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

$(function()
{
    Shadowbox.init();

    $('#data_inicial').datepicker({startDate:'01/01/2000'});
    $('#data_final').datepicker({startDate:'01/01/2000'});
    $("#data_inicial").mask("99/99/9999");
    $("#data_final").mask("99/99/9999");
    $("#revenda_cnpj").numeric();

    function formatItem(row) {
        return row[2] + " - " + row[1];
    }

    $(document).on("click", "span[rel=lupa]", function () {
        $.lupa($(this));
    });

});

function retorna_posto (retorno) {

    $("#codigo_posto").val(retorno.codigo);
    $("#nome_posto").val(retorno.nome);

}

function retorna_produto (retorno) {

    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);

}

function GerarRelatorio (produto, posto, tipo_os, data_inicial, data_final, tipo_os_cr, revenda_cnpj,os_aberta,causa_troca) {
	//HD 211825: Filtro por tipo de OS: Consumidor/Revenda
	if (typeof tipo_os_cr == "undefined") {
		tipo_os_cr = "";
	}
	//HD 211825: Filtro por CNPJ da Revenda
	if (typeof revenda_cnpj == "undefined") {
		revenda_cnpj = "";
	}
	//HD 229110: Filtro por OS Aberta
	if (typeof os_aberta == "undefined") {
		os_aberta = "";
	}
	//HD 260902: Filtro por causa de troca
	if (typeof causa_troca == "undefined") {
		causa_troca = "";
	}
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto=' + produto + '&posto=' + posto +  '&tipo_os=' + tipo_os + '&data_inicial=' + data_inicial + '&data_final=' + data_final + "&tipo_os_cr=" + tipo_os_cr + "&revenda_cnpj=" + revenda_cnpj + "&os_aberta=" + os_aberta + "&causa_troca=" + causa_troca;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}

function montaComboCidade(estado){

	$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
			cache: false,
			success: function(data) {
				$('#cidade').html(data);
			}
		});

}


</script>

<?
//Variavel escolha serve para selecionar entre 'data_digitacao' ou 'finalizada' na pesquisa.
$escolha = trim($_POST['data_filtro']);

?>
<div id="msg" style="width:700px;margin:auto;"></div>
<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios </b>
</div>
<form name="frm_relatorio" method="post" action="<?=$PHP_SELF?>" class='tc_formulario'>
    <input type="hidden" name="acao" value="" />
<?php
if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
    <div class="titulo_tabela">Paramêtros de Pesquisa</div>
    <br />
<?php
if(in_array($login_fabrica, array(11, 72, 80, 81, 101, 114, 172))) {
?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_inicial" id="data_inicial" size="12" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_final" id="data_final" size="12" class='span12' value= "<?=$data_final?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='consumidor_revenda_pesquisa'>Cons/Rev</label>
                <div class='controls controls-row'>
                    <div class='span8'>
<?php
    switch ($consumidor_revenda_pesquisa) {
        case "C":
            $selected_c = "SELECTED";
        break;

        case "R":
            $selected_r = "SELECTED";
        break;
    }
?>
                        <select id="consumidor_revenda_pesquisa" name="consumidor_revenda_pesquisa" class='frm' style='width:112px'>
                            <option value="">Todas</option>
                            <option value="C" <?=$selected_c?>>Consumidor</option>
                            <option value="R" <?=$selected_r?>>Revenda</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='tipo_os'>Tipo OS Troca</label>
                <div class='controls controls-row'>
                    <div class='span8'>
<?php
    switch($tipo_os) {
        case "troca":
            $tipo_os_troca_selected = "selected";
        break;

        case "ressarcimento":
            $tipo_os_ressarcimento_selected = "selected";
        break;

        case "revenda":
            $tipo_os_revenda_selected = "selected";
        break;

        case "troca_diferente":
            $tipo_os_troca_diferente = "selected";
        break;
    }
?>
                        <select name='tipo_os' class='frm' style='width:204px'>
                            <option value=''>TODOS</option>
                            <option value='troca' <?=$tipo_os_troca_selected?>>TROCA</option>
                            <option value='ressarcimento' <?=$tipo_os_ressarcimento_selected?>>RESSARCIMENTO</option>

<?php
    if (in_array($login_fabrica, array(81, 114))) {
?>
                            <option value='revenda' <?=$tipo_os_revenda_selected?>>DEVOLUÇÃO DE VENDA</option>
                            <option value='troca_diferente' <?=$tipo_os_troca_diferente?>>Troca Produto Diferente</option>
<?php
    }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='codigo_posto'>Posto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" id="codigo_posto" name="codigo_posto" class='span12' maxlength="20" value="<?=$codigo_posto?>" />
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='nome_posto'>Nome do Posto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" id="nome_posto" name="nome_posto" class='span12' maxlength="20" value="<?=$nome_posto?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?=$produto_referencia?>" />
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' maxlength="20" value="<?=$produto_descricao?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
<!-- novo -->
<?php
    if ( in_array($login_fabrica, array(11,172)) ) {
?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Estado</td>
			<td>Cidade</td>
			<td width="10">&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td width="10">&nbsp;</td>
		<td>
			<select name='estado' class='frm' onchange="montaComboCidade(this.value)">
				<option value='' selected>Todos</option>
				<option value='BR-CO'>Centro-Oeste</option>
				<option value='BR-NE'>Nordeste</option>
				<option value='BR-N'>Norte</option>
		    <option value='AC'>Acre</option>
		    <option value='AL'>Alagoas</option>
		    <option value='AM'>Amazonas</option>
		    <option value='AP'>Amapá</option>
		    <option value='BA'>Bahia</option>
		    <option value='CE'>Ceará</option>
		    <option value='DF'>Distrito Federal</option>
		    <option value='ES'>Espírito Santo</option>
		    <option value='GO'>Goiás</option>
		    <option value='MA'>Maranhão</option>
		    <option value='MG'>Minas Gerais</option>
		    <option value='MS'>Mato Grosso do Sul</option>
		    <option value='MT'>Mato Grosso</option>
		    <option value='PA'>Pará</option>
		    <option value='PB'>Paraíba</option>
		    <option value='PE'>Pernambuco</option>
		    <option value='PI'>Piauí</option>
		    <option value='PR'>Paraná</option>
		    <option value='RJ'>Rio de Janeiro</option>
		    <option value='RN'>Rio Grande do Norte</option>
		    <option value='RO'>Rondônia</option>
		    <option value='RR'>Roraima</option>
		    <option value='RS'>Rio Grande do Sul</option>
		    <option value='SC'>Santa Catarina</option>
		    <option value='SE'>Sergipe</option>
		    <option value='SP'>São Paulo</option>
		    <option value='TO'>Tocantins</option>
			</select>
		</td>
		<td>
		<?php
			echo "<select name='cidade' id='cidade' class='frm'>";
				$sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_cidade) AS contato_cidade
						FROM tbl_posto_fabrica
						WHERE tbl_posto_fabrica.fabrica = $login_fabrica
						ORDER BY contato_cidade";
				$res = pg_exec($con, $sql);
				if(pg_numrows($res)>0){
					echo "<option value='' selected>Todos</option>";
					for($x=0; $x<pg_numrows($res); $x++){
						$nome_cidade = pg_result($res, $x, contato_cidade);
						echo "<option value='$nome_cidade'>";
						echo $nome_cidade;
						echo "</option>";
					}
				}
			echo "</select>&nbsp;&nbsp;";
		?>
		</td>
		<td width="10">&nbsp;</td>
	</tr>

<!-- //// -->
<?php
    }
?>

<?php
    if ($login_fabrica != 101) {
?>


    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='revenda_cnpj'>CNPJ da Revenda</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <input type="text" id="revenda_cnpj" name="revenda_cnpj" class='span12' maxlength="20" value="<?=$revenda_cnpj?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
<?php
    }
	    if ( in_array($login_fabrica, array(11,172)) ) {
			echo "<div class='row-fluid'>
				    <div class='span2'></div>";
	    	if($os_aberta=="t") $checked1 = "checked";
	    	echo "<div class='span4'>
	    			<INPUT class='frm' TYPE='checkbox' {$checked1} NAME='os_aberta' VALUE='t'> Apenas OSs não finalizadas
	    		 </div>";
	    
	    	if($os_pedido_n_faturado == "t") $checked =  "checked";
	        echo "
		        <div class='span4'>
	                <INPUT class='frm' {$checked} TYPE='checkbox' NAME='os_pedido_n_faturado' VALUE='t'/> Apenas OSs com Pedido Não Faturado
		        </div>";
			echo "<div class='span2'></div>
				</div>";
	    }

} else {
?>
	<style type="text/css">
		select {
			width: 100% !important;
		}
	</style>
	<div class="row-fluid">
        <div class="span2"></div>
        <div class='span4'>
            <div class='control-group <?php echo $showAsError ?>'>
                <label class="control-label" for="mes">Mês</label>
                <div class="controls controls-row">
                    <div class="span6">
                        <h5 class="asteristico">*</h5>
                        <?
						$meses = array(
							1 => 'Janeiro',
							'Fevereiro',
							'Março',
							'Abril',
							'Maio',
							'Junho',
							'Julho',
							'Agosto',
							'Setembro',
							'Outubro',
							'Novembro',
							'Dezembro');
						if (strlen ($mes) == 0) $mes = date('m');
						?>
						<select name="mes">
							<option value="anual">ANUAL</option>
							<?php
							for ($i = 1 ; $i <= 12 ; $i++) {
								?>
								<option value='<?=$i?>' <?php echo $mes == $i ? "selected" : ""; ?> ><?=$meses[$i]?></option>
								<?php
							}
							?>
						</select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='ano'>Ano</label>
                <div class='controls controls-row'>
                    <div class='span6 input-append'>
                        <select name="ano">
						<?
						for($i = date("Y"); $i > 2003; $i--){
							?>
							<option value='<?=$i?>' <?php echo $mes == $i ? "selected" : ""; ?> ><?=$i?></option>
							<?php
						}
						?>
						</select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
 <? } 

if(in_array($login_fabrica, array(3,80))){
 ?>
	<style type="text/css">
		select {
			width: 100% !important;
		}
	</style>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span7">
            <div class='control-group'>
                <label class='control-label' for='causa_troca'>Causa da Troca</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <select name="causa_troca">
						<option value=""></option>
						<?
						$sql = "SELECT causa_troca, descricao FROM tbl_causa_troca 
							WHERE fabrica = $login_fabrica 
								AND ativo IS TRUE
							ORDER BY descricao";
						$resCausaTroca = pg_query($con,$sql);
						$total_causa   = pg_num_rows($resCausaTroca);

						if(isset($_POST["causa_troca"])){
							$post_causa_troca = $_POST["causa_troca"];
						}

						for($i=0; $i<$total_causa; $i++){
							$causa_troca = pg_fetch_result($resCausaTroca, $i, causa_troca);
							$descricao   = pg_fetch_result($resCausaTroca, $i, descricao);
							$selected    = $post_causa_troca == $causa_troca ? "selected" : "";
							?>
							<option value="<?=$causa_troca?>" <?=$selected?>><?=$descricao?></option>
							<?php
						}
						?>
						</select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
<?php } ?>
    <p>
        <input type="button" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer;" value="Pesquisar" />
        <br/>
    </p>
    <br />
</form>

<br>
</div>
<?

if($mes == 'ANUAL'){?>
	<FORM METHOD=POST ACTION="<?$PHP_SELF?>">
	<TABLE border='0' align='center' style='font-family: verdana; font-size: 12px' cellspacing='0' cellpadding='0'>
	<TR class="Titulo">
		<TD  align='center'>Pedido de Relatório de Ano</TD>
	</TR>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<TR class="Conteudo" bgcolor="#D9E2EF">
		<TD align='center'>O relatório não pode ser executado no momento.<br>Você deseja enviar um e-mail para o Suporte tirar o relatório e enviar num praza mínimo de 24 horas?<br><b>Digite corretamente seu e-mail para que possa ser enviado o relatório!</b><br></TD>
	</TR>
		<INPUT TYPE="hidden" NAME="produto_1" value='<? echo $produto_referencia ?>'>
		<INPUT TYPE="hidden" NAME="produto_2" value='<? echo $produto_descricao ?>'>
		<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<TR >
		<TD class="Conteudo" bgcolor="#D9E2EF" align='left'>Sim <INPUT TYPE='radio' value='sim' NAME='pedido_relatorio'> Não <INPUT TYPE='radio' value='nao' NAME='pedido_relatorio'><br>E-mail: <INPUT TYPE="text" size='80' NAME="email"></TD>
	</TR>
	<TR >
	<INPUT TYPE="hidden" name='anual' value='<? echo $ano; ?>'>
		<TD class="Conteudo" bgcolor="#D9E2EF" align='center'><INPUT TYPE='submit' name='relatorio' value='Enviar'></TD>
	</TR>
	</TABLE>
	</FORM>
<?}
}
// echo "AQUI->".$acao."-->".$msg;exit;
if ( (strlen($acao) > 0 && strlen($msg) == 0 AND $mes <> "ANUAL") || ($acao == "RELATORIO" && $login_fabrica == 72) ) {
	if(!in_array($login_fabrica, array(11, 72, 80, 81,101, 114, 172))) {
		$x_data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$x_data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

		$mostra_data_inicial = mostra_data($x_data_inicial);
		$mostra_data_final   = mostra_data($x_data_final);
		$cond_data=" AND   tbl_os_troca.data BETWEEN '$x_data_inicial' AND '$x_data_final' ";
	} else {
		if($acao != "RELATORIO") {
			$x_data_inicial =$x_data_inicial. " 00:00:00";
			$x_data_final   =$x_data_final .  " 23:59:59";
		}
		$cond_data=" AND   tbl_os_troca.data BETWEEN '$x_data_inicial' AND '$x_data_final' ";
	}
	$tipo_os = trim($_POST['tipo_os']); // hd 38342

	//HD 211825: Filtro de TROCA REVENDA para a Salton
	switch($tipo_os) {
		case "troca":
			$cond_tipo= "
			AND tbl_os.troca_garantia IS TRUE
			AND tbl_os.ressarcimento IS NOT TRUE
			AND tbl_os_troca.troca_revenda IS NOT TRUE ";
		break;

		case "ressarcimento":
			$cond_tipo= " AND tbl_os.ressarcimento IS TRUE ";
		break;

		case "revenda":
			$cond_tipo= " AND tbl_os_troca.troca_revenda IS TRUE ";
		break;

		case "troca_diferente":
			$campos_peca = " , tbl_peca.referencia AS peca_referencia, tbl_peca.descricao AS peca_descricao";
			$join_peca = " JOIN tbl_peca ON tbl_peca.peca = tbl_os_troca.peca";
			$cond_tipo= " AND tbl_produto.referencia <> tbl_peca.referencia ";
		break;

		default:
			$cond_tipo =" AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE )";
	}
	if(strlen($_POST['produto_referencia']) == 0 && $acao == 'RELATORIO')
		$_POST['produto_referencia'] = $_GET['produto'];

	if(strlen($_POST['produto_referencia']) > 0) {
		$produto_referencia = trim($_POST['produto_referencia']);
		if($acao == 'RELATORIO')
			$sql="SELECT produto FROM tbl_produto JOIN tbl_linha using (linha) WHERE produto = '$produto_referencia' and fabrica=$login_fabrica";
		else
		$sql="SELECT produto FROM tbl_produto JOIN tbl_linha using (linha) WHERE referencia = '$produto_referencia' and fabrica=$login_fabrica";

		$res=pg_exec($con,$sql);
		if(pg_numrows($res) > 0) {
			$produto = pg_result($res,0,produto);
			$cond_produto= " AND tbl_os.produto = $produto ";
		}
		else
			$msg_erro = 'Produto não Encontrado';
	}
	if(strlen(trim($_POST['codigo_posto'])) == 0 && $acao == "RELATORIO")
		$_POST['codigo_posto'] = $_GET['posto'];

	if(strlen(trim($_POST['codigo_posto'])) > 0) {
		$codigo_posto = trim($_POST['codigo_posto']);

		if($acao == "RELATORIO")

			$sql="  SELECT posto
					FROM tbl_posto_fabrica
					WHERE tbl_posto_fabrica.posto='$codigo_posto'";
		else
			$sql="SELECT posto FROM tbl_posto_fabrica WHERE tbl_posto_fabrica.codigo_posto='$codigo_posto' and fabrica = $login_fabrica";

		$res=pg_exec($con,$sql);
		if(pg_numrows($res)>0) {
			$posto = pg_result($res,0,posto);
			$cond_posto= " AND tbl_os.posto= $posto";
		}
		else
			$msg_erro = 'Posto não Encontrado';
	}
	if($login_fabrica == 72){
		$cond_posto .= " AND tbl_posto_fabrica.posto <> 6359 ";
	}

	//HD 211825: Filtro por tipo de OS: Consumidor/Revenda
	if($acao == 'RELATORIO')
		$_POST["consumidor_revenda_pesquisa"] = $_GET['tipo_os_cr'];
	if (strlen(trim($_POST["consumidor_revenda_pesquisa"]))) {
		$consumidor_revenda_pesquisa = $_POST["consumidor_revenda_pesquisa"];
		$cond_cr = " AND tbl_os.consumidor_revenda='$consumidor_revenda_pesquisa'";
	}

	//HD 211825: Filtro por CNPJ da Revenda
	if (strlen(trim($_POST["revenda_cnpj"]))) {
		$revenda_cnpj = preg_replace('/[^0-9]+/', '', $_POST["revenda_cnpj"]);
		if(strlen($revenda_cnpj) == 8) {
			$cond_revenda_cnpj = " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%'";
		}
	}

	//HD 229110: Filtro por OS Aberta
	$os_aberta = $_POST["os_aberta"];
	if ($os_aberta=="t") {
		$cond_os_aberta = " AND tbl_os.finalizada IS NULL ";
	}
    if(strlen($msg_erro) == 0) {
        if($login_fabrica != 72) { // hd 308334
            $campos = "
                    ,tbl_os.consumidor_fone AS consumidor_fone,
                    tbl_os.consumidor_cpf AS consumidor_cpf,
                    tbl_os.consumidor_email AS consumidor_email,
                    tbl_admin.login,
                    TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY HH24:MI') AS data_troca,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
                    ";
        } else {
            $campos = ",tbl_admin.login, TO_CHAR((SELECT data
                                FROM tbl_pedido
                                WHERE tbl_pedido.pedido = tbl_os_troca.pedido),'DD/MM/YYYY') AS data_pedido ";
            $cond_72 = ' AND tbl_os.excluida IS NOT TRUE';
        }

        if ( in_array($login_fabrica, array(11,172)) ) {

            //echo $os_consumidor;exit;

            if($os_consumidor == "C"){
                $cond_os_consumidor = " AND tbl_os.consumidor_revenda = 'C'";
            } else {
                $cond_os_consumidor = "";
            }

            if($os_revenda == "R"){
                $cond_os_revenda = " AND tbl_os.consumidor_revenda = 'R'";
            }else{
                $cond_os_revenda = "";
            }

            if($os_consumidor == "C" AND $os_revenda == "R"){
                $cond_os_revenda = "";
                $cond_os_consumidor = "";
            }

            if(strlen(trim($estado)) > 0){
                $cond_estado = "AND tbl_posto_fabrica.contato_estado ILIKE '%$estado%'";
            }

            if(strlen(trim($cidade)) > 0){
                $cond_cidade = "AND tbl_posto_fabrica.contato_cidade ILIKE '%$cidade%'";
            }
        }
		
		$joinPedido = " LEFT JOIN";
		$joinFat    = "";

        if (in_array($login_fabrica,[11,172])) {
			$cond_troca_cancelada = " AND (tbl_pedido.status_pedido <> 14 or tbl_pedido.status_pedido isnull)";
		
			if ($os_pedido_n_faturado == "t" ) {
				$joinFat = "LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido";
				$joinCondFat = "AND (tbl_faturamento_item.faturamento_item IS NULL OR tbl_faturamento_item.faturamento IS NULL)";
			}

		}

        $sql =	"SELECT tbl_posto_fabrica.posto AS posto,
                    tbl_os.ressarcimento,
                    tbl_posto_fabrica.codigo_posto AS posto_codigo,
                    tbl_posto.nome AS posto_nome,
                    tbl_posto_fabrica.contato_cidade AS cidade,
                    tbl_posto_fabrica.contato_estado AS estado,
                    tbl_os.sua_os,
                    tbl_os.os,
                    tbl_os.serie,
                    tbl_produto.referencia AS produto_referencia,
                    tbl_produto.descricao AS produto_descricao,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                    tbl_os_troca.ri,
                    TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
                    tbl_os_troca.setor,
                    tbl_os_troca.situacao_atendimento,
                    tbl_causa_troca.descricao AS causa_troca,
                    tbl_os.consumidor_revenda,
                    tbl_os.consumidor_nome AS consumidor_nome,
                    tbl_os.defeito_reclamado_descricao AS defeito_reclamado,
                    tbl_os.data_nf_saida AS data_nf_saida,
                    tbl_os_troca.troca_revenda,
                    (select min(preco) from tbl_peca join tbl_tabela_item using(peca) where tbl_peca.referencia = tbl_produto.referencia and fabrica = $login_fabrica) as preco
                    $campos
                    $campos_peca
            FROM    tbl_os
            JOIN    tbl_admin           ON  tbl_admin.admin             = tbl_os.troca_garantia_admin
                                        AND tbl_admin.fabrica           = tbl_os.fabrica
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                        AND tbl_posto_fabrica.fabrica   = tbl_os.fabrica
            JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_posto_fabrica.posto
            JOIN    tbl_produto         ON  tbl_produto.produto         = tbl_os.produto
            JOIN    tbl_os_troca        ON  tbl_os_troca.os             = tbl_os.os and tbl_os_troca.fabric=$login_fabrica
            $joinPedido tbl_pedido        ON tbl_pedido.pedido            = tbl_os_troca.pedido
                                        AND tbl_os_troca.fabric         = tbl_os.fabrica
            $join_peca
            $joinFat
            LEFT JOIN tbl_causa_troca ON tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca
				AND tbl_causa_troca.fabrica = $login_fabrica
            WHERE   tbl_os.fabrica  = $login_fabrica
            AND     tbl_os.excluida IS NOT TRUE
            $cond_72
            $cond_data
            $cond_tipo
            $cond_produto
            $cond_posto
            $cond_cr
            $cond_revenda_cnpj
            $cond_os_aberta
            $cond_causa_troca
            $cond_estado
            $cond_cidade
            $cond_os_revenda
            $cond_os_consumidor
            $cond_troca_cancelada
            $joinCondFat
            ORDER BY tbl_os.consumidor_revenda, tbl_os_troca.data
            ;";
    // exit(nl2br($sql));
        #HD 52120 - Fiz a seguinte alteracao no SLQ: o relatório fazia a busca por data do fechamento da OS
        #           Alterei para a data da troca com JOIN tbl_os_troca (antes estava com LEF JOIN)
        #           Fiz um teste para a Britania e Lenoxx de todas as OS de troca tem registro na tbl_os_troca
        #           Os testes retornaram as mesmas qtdes de OS com JOIN e com LEFT
        #           Coloquei um ORDER tbm, para nw ficar misturado sem ordenacao nenhuma
        //echo nl2br($sql);exit;
        $res = pg_exec($con,$sql);
        $numero_registros = pg_numrows($res);

        if (pg_numrows($res) > 0) {
            if($acao != "RELATORIO") {

                $data = date("Y_m_d-H_i_s");
                $file = "xls/relatorio-troca-produto-$login_fabrica-$data.csv";
                $fp = fopen($file,'w');
//                 echo $fp;
?>

            <br /> <br />
            <div class="btn_excel" onclick="javascript: window.location='<?=$file?>';">
                <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
                <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
            </div>
            <br /> <br />
    <!--			echo "<br><input type='button' value='Download em Excel' onclick=\"javascript: GerarRelatorio ('$produto', '$posto', '$tipo_os','$x_data_inicial', '$x_data_final', '$consumidor_revenda_pesquisa', '$revenda_cnpj','$os_aberta','$causa_troca');\"><br>";-->
                <table border='0' cellspacing='0' cellpadding='0' style='margin-left: 107px; margin-bottom: 3px;'>
                    <tr height='18'>
                        <td width='18' bgcolor='#99d888'>&nbsp;</td>
                        <td align='left'><font size='1'><b>&nbsp; Ressarcimento Financeiro</b></font></td>
                    </tr>
<?php
                if (in_array($login_fabrica, array(81, 114))) {
?>
                    <tr height='5'><td colspan=2></td></tr>
                        <tr height='18'>
                        <td width='18' bgcolor='#d89988'>&nbsp;</td>
                        <td align='left'><font size='1'><b>&nbsp; Autorização de Troca pela Revenda </b></font></td>
                    </tr>
<?php
                }
?>
                </table>
<?php
            }
?>
        <table class='table table-bordered' id='relatorio' style='margin: 0 auto;'>
            <thead>
                <tr class='titulo_coluna'>
<?php
            if (in_array($login_fabrica, array(81, 114))) {
                fwrite($fp, "OS;ABERTURA;POSTO;");

?>
                <th>OS</th>
                <th>Abertura</th>
                <th>Posto </th>
<?php
                if($tipo_os == "troca_diferente"){
                fwrite($fp, "PRODUTO ORIGEM;PRODUTO TROCADO;");
?>
                <th>Produto de Origem</th>
                <th>Produto Trocado</th>
<?php
                }else{
                    fwrite($fp, "PRODUTO;");
?>
                <th>Produto</th>
<?php
                }
                    fwrite($fp, "CONSUMIDOR;FONE;CPF;E-MAIL;ATENDENTE;TROCA/REEMBOLSO;PEDIDO;RESPONSÁVEL;MOTIVO;PAGAMENTO;");
?>
                <th>Consumidor</th>
                <th>Fone</th>
                <th>CPF</th>
                <th>e-mail</th>
                <th>Atendimento</th>
                <th>Troca/Reembolso</th>
                <th>Pedido</th>
                <th>Responsável</th>
                <th>Motivo</th>
                <th>Pagamento</th>
<?php
                if($telecontrol_distrib) {
                    fwrite($fp, "PRECO TABELA;");
?>
                <th>Preço Tabela</th>
<?php
                }
                if($_POST['tipo_os'] == 'ressarcimento'){
                    fwrite($fp, "VALOR;");
?>
                <th>Valor</td>
<?php
                }

            } else {
                if($login_fabrica == 72) { // hd 308334
                    fwrite($fp, "OS;ABERTURA;POSTO;REFERÊNCIA;DESCRIÇÃO;SÉRIE;DATA COMPRA;CONS/REV;DATA PEDIDO;OPERADOR RESPONSÁVEL;MOTIVO TROCA;VALOR;");
?>

                    <th>OS</th>
                    <th>Abertura</th>
                    <th>Posto</th>
                    <th>Referência</th>
                    <th>Descrição</th>
                    <th>Nº de Série</th>
                    <th>Data Compra</th>
                    <th>C/R</th>
                    <th>Data Pedido</th>
                    <th>Operador<br> Responsável</th>
                    <th>Motivo da Troca</th>
                    <th width="100px">Valor</th>
<?php
                } else {
                    fwrite($fp, "OS;CONS/REV;POSTO;");
?>
                    <th>OS</th>
                    <th>C/R</th>
                    <th>Posto</th>
<?php
                    if ( in_array($login_fabrica, array(11,172)) ) {
                        fwrite($fp, "CIDADE;ESTADO;");
?>
                    <th>Cidade</th>
                    <th>Estado</th>
<?php
                    }
                    fwrite($fp, "PRODUTO;SÉRIE;PRODUTO TROCA;");
?>
                    <th>Produto</th>
                    <th>Nº de Série</th>
                    <th>Produto troca</th>
<?php
                    if ($login_fabrica == 101) {
                        fwrite($fp, "CAUSA TROCA;PEÇAS ORIGINOU TROCA;");
?>
                    <th>Causa da Troca</th>
                    <th>Peças Originou a Troca</th>
<?php
                        }
                        fwrite($fp, "ABERTURA;");
?>
                    <th>Abertura</th>
<?php
                    if ( in_array($login_fabrica, array(11,172)) ) {
                        fwrite($fp, "PEDIDO;NF;");
?>
                    <th>Pedido</th>
                    <th>NF</th>
<?php
                    }
                    if( !in_array($login_fabrica, array(11,172)) ){
                        fwrite($fp, "TROCA;PEDIDO;RESPONSÁVEL;SETOR;SITUAÇÃO;RI;");
?>
                    <th>Troca</th>
                    <th>Pedido</th>
                    <th>Responsável</th>
                    <th nowrap>Setor responsável</th>
                    <th nowrap>Situação do atendimento</th>
                    <th>RI</th>
<?php
                        if ($login_fabrica != 101) {
                        fwrite($fp, "CAUSA TROCA;PEÇAS ORIGINOU TROCA;");
?>
                    <th>Causa da Troca</th>
                    <th>Peças Originou a Troca</th>
<?php
                        }
                    } else {
                        fwrite($fp, "DATA TROCA;RESPONSÁVEL;CAUSA;");
?>
                    <th>Data Troca</th>
                    <th>Responsável</th>
                    <th>Causa</th>
<?php
                    }
                }
            }
            fwrite($fp, "\n");
?>
                </tr>
            </thead>
            <tbody>
<?php
            $posto_anterior = "*";
            $cont = 1;
            for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

            	$cor = "";

                if( $login_fabrica != 72 ) { // hd 308334
                    $consumidor_fone      = trim(pg_result($res,$i,consumidor_fone));
                    $consumidor_cpf       = trim(pg_result($res,$i,consumidor_cpf));
                    $consumidor_email     = trim(pg_result($res,$i,consumidor_email));
                    $data_troca           = trim(pg_result($res,$i,data_troca));
                    $data_fechamento      = trim(pg_result($res,$i,data_fechamento));
                    $login                = trim(pg_result($res,$i,login));
                } else {
                    $data_pedido           = trim(pg_result($res,$i,data_pedido));
                }
                $login                = trim(pg_result($res,$i,login));
                $ressarcimento        = trim(pg_result($res,$i,ressarcimento));
                $posto                = trim(pg_result($res,$i,posto));
                $posto_codigo         = trim(pg_result($res,$i,posto_codigo));
                $posto_nome           = trim(pg_result($res,$i,posto_nome));
                if( in_array($login_fabrica, array(11,172)) ){
                    $cidade = trim(pg_result($res,$i,cidade));
                    $estado = trim(pg_result($res,$i,estado));
                }

                $posto_completo       = $posto_codigo . " - " . $posto_nome;
                $sua_os               = trim(pg_result($res,$i,sua_os));
                $os                   = trim(pg_result($res,$i,os));
                $serie                = trim(pg_result($res,$i,serie));
                $produto_referencia   = trim(pg_result($res,$i,produto_referencia));
                $produto_descricao    = trim(pg_result($res,$i,produto_descricao));
                $produto_completo     = $produto_referencia . " - " . $produto_descricao;
                if($tipo_os == "troca_diferente"){
                    $peca_referencia = trim(pg_result($res,$i,peca_referencia));
                    $peca_descricao  = trim(pg_result($res,$i,peca_descricao));
                }
                #$troca_por_referencia = trim(pg_result($res,$i,troca_por_referencia));
                #$troca_por_descricao  = trim(pg_result($res,$i,troca_por_descricao));
                $troca_por_completo   = $troca_por_referencia . " - " . $troca_por_descricao;
                $data_abertura        = trim(pg_result($res,$i,data_abertura));
                $data_nf              = trim(pg_result($res,$i,data_nf));
                $preco                = trim(pg_result($res,$i,'preco'));
                $ri                   = trim(pg_result($res,$i,ri));
                $setor                = trim(pg_result($res,$i,setor));
                $situacao_atendimento = trim(pg_result($res,$i,situacao_atendimento));
                $causa_troca          = trim(pg_result($res,$i,causa_troca));
                $consumidor_revenda   = trim(pg_result($res,$i,consumidor_revenda));
                $consumidor_nome      = trim(pg_result($res,$i,consumidor_nome));
                $troca_revenda        = trim(pg_result($res,$i,troca_revenda));
                $defeito_reclamado    = trim(pg_result($res,$i,defeito_reclamado));
                $data_nf_saida        = trim(pg_result($res,$i,data_nf_saida));

                if($situacao_atendimento == 0 AND strlen($situacao_atendimento)>0) $situacao_atendimento = "Garantia";
                elseif(strlen($situacao_atendimento)>0)                            $situacao_atendimento .= "%";


                #HD 13502
                $pecas_originou_troca = array();

                $sql = "SELECT referencia
                        FROM tbl_os_produto
                        JOIN tbl_os_item USING(os_produto)
                        JOIN tbl_peca USING(peca)
                        WHERE tbl_os_produto.os = $os
                        AND tbl_os_item.originou_troca IS TRUE";
                $res2 = pg_exec($con,$sql);
                if (pg_numrows($res2) > 0) {
                    for ($j=0; $j<pg_numrows($res2); $j++){
                        array_push($pecas_originou_troca ,trim(pg_result($res2,$j,referencia)));
                    }
                }

                $sql = "SELECT referencia   AS troca_por_referencia ,
                            descricao    AS troca_por_descricao  ,
                            pedido       AS pedido
                        FROM tbl_peca
                        JOIN tbl_os_item    USING (peca)
                        JOIN tbl_os_produto USING (os_produto)
                        WHERE tbl_peca.produto_acabado
                        AND tbl_os_produto.os = $os LIMIT 1";
                $res2 = pg_exec($con,$sql);

                if (pg_numrows($res2) > 0 && $ressarcimento != "t") {
                    $troca_por_referencia = trim(pg_result($res2,0,troca_por_referencia));
                    $troca_por_descricao  = trim(pg_result($res2,0,troca_por_descricao));
                    $pedido               = trim(pg_result($res2,0,pedido));
                    $troca_por_completo   = $troca_por_referencia . " - " . $troca_por_descricao;
                } else {
                    $troca_por_referencia = "";
                    $troca_por_descricao  = "";
                    $pedido               = "";
                }

                if ($ressarcimento == "t") { 
                	$cor = "#99d888";
                }

                if ($troca_revenda == "t") { 
                	$cor = "#d89988";
                }

                # Separando consumidor Revenda #
                // if($login_fabrica == 11){

                // 	if($consumidor_revenda == "R"){
                // 		if($cont == 1){
                // 			$cont = 0;
                // 			echo "<tr height='15' class='titulo_coluna'><td colspan='14'>OS REVENDA</td></tr>";
                // 		}
                // 	}
                // }
?>
                <tr bgcolor='<?=$cor?>'>
<?php
                if (in_array($login_fabrica, array(81, 114))){
                    fwrite($fp, "$sua_os;$data_abertura;$posto_nome;");
?>
	<td nowrap><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a></td>
					<td nowrap><?=$data_abertura?></td>
                    <td nowrap align='left'><?=$posto_nome?></td>
<?php
                    if ($tipo_os == "troca_diferente") {
                        fwrite($fp, "$produto_referencia - $produto_descricao;$peca_referencia - $peca_descricao;");
?>
                        <td nowrap align='left'><?=$produto_referencia." - ".$produto_descricao?></td>
                        <td nowrap align='left'><?=$peca_referencia." - ".$peca_descricao?></td>
<?php
                    } else {
                        fwrite($fp, "$produto_referencia - $produto_descricao;");
?>
                        <td nowrap align='left'><?=$produto_referencia." - ".$produto_descricao?></td>
<?php
                    }
                    fwrite($fp, "$consumidor_nome;$consumidor_fone;$consumidor_cpf;$consumidor_email;$situacao_atendimento;$data_troca;$pedido;$login;$defeito_reclamado;$data_fechamento;");
?>
                    <td nowrap align='left'><?=$consumidor_nome?></td>
                    <td nowrap align='left'><?=$consumidor_fone?></td>
                    <td nowrap align='left'><?=$consumidor_cpf?></td>
                    <td nowrap align='left'><?=$consumidor_email?></td>
                    <td nowrap align='left'><?=$situacao_atendimento?></td>
                    <td nowrap align='left'><?=$data_troca?></td>
                    <td nowrap align='left'><?=$pedido?></td>
                    <td nowrap align='left'><?=$login?></td>
                    <td nowrap align='left'><?=$defeito_reclamado?></td>
                    <td nowrap align='left'><?=$data_fechamento?></td>
<?php
                    if($telecontrol_distrib) {
                        fwrite($fp, number_format($preco,2,",",".").";");
?>
                    <td nowrap align='left'><?=number_format($preco,2,",",".")?></td>
<?
                    }
                    if (filter_input(INPUT_POST,'tipo_os') == 'ressarcimento') {
                        $sql2 = pg_query($con, "SELECT tbl_hd_chamado_troca.valor_produto from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os");
                        if (pg_numrows($sql2)>0) {
                            $valor_ress = trim(pg_result($sql2,0,valor_produto));
                        }
                        fwrite($fp, number_format($valor_ress,2,',','').";");
?>
                    <td><?=number_format($valor_ress,2,',','')?></td>
<?php
                    }
                } else if($login_fabrica == 72) {
                    fwrite($fp, "$sua_os;$data_abertura;$posto_nome;$produto_referencia;$produto_descricao;$serie;$data_nf;");
?>
                    <td nowrap><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a></td>
                    <td nowrap><?=$data_abertura?></td>
                    <td nowrap align='left'><?=$posto_nome?></td>
                    <td nowrap align='left'><?=$produto_referencia?></td>
                    <td nowrap align='left'><?=$produto_descricao?></td>
                    <td nowrap align='left'><?=$serie?></td>
                    <td nowrap align='left'><?=$data_nf?></td>
<?php
                    switch ($consumidor_revenda) {
                        case "C":
                            fwrite($fp, "CONS;");
?>
                    <td nowrap><acronym title='Consumidor' style='cursor: help;'>CONS</acronym></td>
<?php
                            break;

                        case "R":
                            fwrite($fp, "REV;");
?>
                    <td nowrap><acronym title='Revenda' style='cursor: help;'>REV</acronym></td>
<?php
                            break;

                    }
                    fwrite($fp, "$data_pedido;$login;$causa_troca;");
?>
                    <td nowrap align='left'><?=$data_pedido?></td>
                    <td nowrap align='left'><?=$login?></td>
                    <td nowrap align='left'><?=$causa_troca?></td>
<?php
                    $sql_troca = "SELECT tbl_pedido_item.preco from tbl_pedido_item join tbl_os_troca USING (pedido_item) where tbl_os_troca.os=$os;";

                    $res_troca = pg_query($con,$sql_troca);

                    if (pg_num_rows($res_troca)>0){
                        $valor_troca_os = trim( pg_result($res_troca,0,"preco") );
                    }
                    fwrite($fp, "$valor_troca_os;");
?>
                    <td><?=number_format($valor_troca_os,2,',','')?></td>
<?php
                } else {
                    fwrite($fp, "$sua_os;");
?>
                    <td nowrap><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a></td>
<?php
                    //HD 211825: Filtrar por tipo de OS: Consumidor/Revenda
                    switch ($consumidor_revenda) {
                        case "C":
                            fwrite($fp, "CONS;")
?>
                    <td nowrap><acronym title='Consumidor' style='cursor: help;'>CONS</acronym></td>
<?php
                            break;

                        case "R":
                            fwrite($fp, "REV;");
?>
                    <td nowrap><acronym title='Revenda' style='cursor: help;'>REV</acronym></td>
<?php
                            break;
                    }
                    if( in_array($login_fabrica, array(11,172)) ){
                        $posto_t = $posto_nome;
                        $posto_nome = substr($posto_nome,0,10);

                        fwrite($fp, "$posto_codigo - $posto_nome;$cidade;$estado;");
?>
                    <td nowrap align='left' alt='$posto_t' title='$posto_t'><?=$posto_codigo." - ".$posto_nome?></td>
                    <td nowrap align='left'><?=$cidade?></td>
                    <td nowrap align='left'><?=$estado?></td>
<?php
                    } else {
                        fwrite($fp, "$posto_codigo - $posto_nome;");
?>
                    <td nowrap align='left'><?=$posto_codigo." - ".$posto_nome?></td>
<?php
                    }

                    $prod = ( in_array($login_fabrica, array(11,172)) ) ? $produto_referencia : $produto_descricao;
                    $trocaProd = ( in_array($login_fabrica, array(11,172)) ) ? $troca_por_referencia : $troca_por_descricao;
                    fwrite($fp, "$prod;$serie;$trocaProd;");
?>
                    <td nowrap align='left'>
                        <acronym title='REFERÊNCIA: <?=$produto_referencia?>\nDESCRIÇÃO: <?=$produto_descricao?>' style='cursor: hand;'>
                        <?=$prod?>
                        </acronym>
                    </td>
                    <td nowrap><?=$serie?></td>
                    <td nowrap align='left'>
                        <acronym title='REFERÊNCIA: <?=$troca_por_referencia?> \n DESCRIÇÃO: <?=$troca_por_descricao?>' style='cursor: hand;'>
                            <?=$trocaProd?>
                        </acronym>
                    </td>
<?php
                    if ($login_fabrica == 101) {
                        $pecas = implode(", ",$pecas_originou_troca);
                        fwrite($fp, "$causa_troca;$pecas;");
?>
                    <td nowrap align='left'><?=$causa_troca?></td>
                    <td nowrap align='left'><?=$pecas?></td>
<?php
                    }
                    fwrite($fp, "$data_abertura;");
?>
                    <td nowrap><?=$data_abertura?></td>
<?php
                    if ( in_array($login_fabrica, array(11,172)) ) {
                        fwrite($fp, "$pedido;");
?>
                    <td nowrap><?=$pedido?></td>
<?php
                        if(strlen($pedido)>0){
                            $sql_x = "
                                SELECT  DISTINCT
                                        nota_fiscal
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING(faturamento)
                                WHERE   tbl_faturamento_item.pedido='$pedido';
                            ";
                            $res_x        = pg_exec($con,$sql_x);
                            if(pg_numrows($res_x) > 0) {
                                $nota_fiscal  = trim(pg_result($res_x,0,nota_fiscal));
                            }
                        }
                        fwrite($fp, "$nota_fiscal;");
?>
                    <td nowrap><?=$nota_fiscal?></td>
<?php
                    }

                    if( !in_array($login_fabrica, array(11,172)) ){
                        fwrite($fp, "$data_troca;$pedido;$login;$setor;$situacao_atendimento;$ri;");
?>
                    <td nowrap><?=$data_troca?></td>
                    <td nowrap><?=$pedido?></td>
                    <td nowrap align='left'><?=$login?></td>
                    <td nowrap align='center'><?=$setor?></td>
                    <td nowrap align='center'><?=$situacao_atendimento?></td>
                    <td nowrap align='center'><?=$ri?></td>
<?php
                        if ($login_fabrica != 101) {
                            $pecas = implode(", ",$pecas_originou_troca);
                            fwrite($fp, "$causa_troca;$pecas;");
?>
                    <td nowrap align='left'><?=$causa_troca?></td>
                    <td nowrap align='left'><?=$pecas?></td>
<?php
                        }
                    } else {
                        fwrite($fp, "$data_troca;$login;$causa_troca;");
?>
                    <td nowrap><?=$data_troca?></td>
                    <td nowrap align='left'><?=$login?></td>
                    <td nowrap align='left'><?=$causa_troca?></td>
<?php
                    }
                }
?>
                </tr>


<?php
                $posto_anterior  = $posto;
                $nota_fiscal     = null;
                $login           = null;
                fwrite($fp, "\n");
            }
            fclose($fp);
?>
            </tbody>
        </table>
        <br><FONT size='2' COLOR="#000000"><B>Total de <?=$numero_registros?> registros</B></FONT><br><br>

<?php
        } else {
?>
		<div class="container">
	        <div class="alert alert-warning">
	        	<h4>Não foram encontrados resultados para esta pesquisa!</h4>
	        </div>
	    </div>
<?php
        }
    } else { // produto ou posto nao encontrado
?>
	<div class="msg_erro" id="msg_erro" style="display:hidden;"><?=$msg_erro?></div>

	<script>
		$("#msg_erro").appendTo("#msg").fadeIn("slow");
	</script>
<?
    }
}
if ($acao == "RELATORIO" && $login_fabrica == 72) {
			$xls = "xls/troca_produto_total".$login_admin.".xls";

			$saida = ob_get_contents();
			ob_end_clean();
			//echo 'Saida: '. $saida; die;
			$arquivo = fopen($xls, "w");
			fwrite($arquivo, $saida);
			fclose($arquivo);

			header("Location:$xls");
			die;
} else {
	echo "<br>";
	include "rodape.php";
}
?>
