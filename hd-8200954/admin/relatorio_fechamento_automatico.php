<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
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
				for ($i=0; $i<pg_numrows ($res); $i++ ){
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

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$ArrayEstados = array('', 'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO');

$sinalizador_pesquisa = array(1, 18, 19, 20);

$periodo = trim($_POST["periodo"]);
if (strlen($periodo) == 0) $periodo = $_GET["periodo"];

$codigo_posto = trim($_POST["codigo_posto"]);
if (strlen($codigo_posto) == 0) $codigo_posto = $_GET["codigo_posto"];

$sua_os = trim($_POST["sua_os"]);
if (strlen($sua_os) == 0) $sua_os = $_GET["sua_os"];

$sinalizador = trim($_POST["sinalizador"]);
if (strlen($sinalizador) == 0) $sinalizador = $_GET["sinalizador"];

if ($codigo_posto) {
	$sql = "
	SELECT
	posto

	FROM
	tbl_posto_fabrica

	WHERE
	fabrica=$login_fabrica
	AND codigo_posto='$codigo_posto'
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$posto = pg_result($res, 0, posto);
	}
	else {
		$codigo_posto = "";
		$posto = "";
	}
}

if(strlen($posto)) {
	$sql="SELECT posto FROM tbl_posto_fabrica WHERE posto='$posto' AND fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Posto $codigo_posto não encontrado";
	}
	elseif ($acao == "PESQUISAR") {
		header("location:os_auditar_posto_test.php?posto=$posto&status=aprovacao");
		die;
	}
}

if (strlen($sua_os)) {
	$sql = "SELECT os FROM tbl_os WHERE sua_os='$sua_os' AND fabrica=$login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$os = pg_result($res, 0, os);
		
		$posto = "";
		$codigo_posto = "";
		$periodo = "";
	}
	else {
		$msg_erro = "OS $sua_os não encontrada no sistema";
	}
}

if (strlen($sinalizador)) {
	if (in_array($sinalizador, $sinalizador_pesquisa)) {
	}
	else {
		$sinalizador = "";
	}
}

$title = "RELATÓRIO DE OS FECHADAS AUTOMATICAMENTE";

include "cabecalho.php";

$estilo_principal = "font-size: 8pt;";

$estilo_os_cabecalho = "background-color: #333333; color: #FFFFFF; font-weight: bold;";
$estilo_os_linha0 = "background-color: #555555; color: #FFFFFF; white-space: nowrap;";
$estilo_os_linha1 = "background-color: #DDDDDD; color: #000000; white-space: nowrap;";
$estilo_os_item = "background-color: #FFFFFF; border-bottom: 1px solid #999999; white-space: nowrap;";

$estilo_data_cabecalho = "background-color: #AA4433; color: #FFFFFF; font-weight: bold;";
$estilo_data_linha0 = "background-color: #CC8888; white-space: nowrap;";
$estilo_data_linha1 = "background-color: #FFDDCC; white-space: nowrap;";

$estilo_produto_cabecalho = "background-color: #448833; color: #FFFFFF; font-weight: bold;";
$estilo_produto_linha0 = "background-color: #88DD88; white-space: nowrap;";
$estilo_produto_linha1 = "background-color: #DDFFCC; white-space: nowrap;";

$estilo_consumidor_cabecalho = "background-color: #4433AA; color: #FFFFFF; font-weight: bold;";
$estilo_consumidor_linha0 = "background-color: #8888DD; white-space: nowrap;";
$estilo_consumidor_linha1 = "background-color: #DDCCFF; white-space: nowrap;";

$estilo_comunicado_cabecalho = "background-color: #AA5500; color: #FFFFFF; font-weight: bold;";
$estilo_comunicado_linha0 = "background-color: #DD8855; white-space: nowrap;";
$estilo_comunicado_linha1 = "background-color: #FFDDAA; white-space: nowrap;";

?>

<style type="text/css">

.Mensagem {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #007700;
}
.Total {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #DDEEEE;
}

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

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />


<? include "javascript_calendario.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>

<script language="JavaScript">

<?php

echo "
estilo_aprovar = \"$estilo_aprovar\";
estilo_reprovar = \"$estilo_reprovar\";
";

?>

$(function()
{
/*	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");*/
});

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});
});

</script>
<? include "javascript_pesquisas.php"; ?>
<? if (strlen($msg_erro) > 0) { ?>
	
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" >
		<tr class="msg_erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center">
		<tr class="Mensagem">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? } ?>


	<form name="frm_busca" method="post" action="<?=$PHP_SELF?>">
	<input type="hidden" name="acao">
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" class='formulario'>
		<tr class="titulo_tabela">
			<td colspan="4" height='25'>Parâmetros de Pesquisa</td>
		</tr>
		<tr >
			<td colspan="4">&nbsp;</td>
		</tr>

		<tr  align='left'>
			<td width="130">&nbsp;</td>
			<td>OS</td>
			<td>Mês</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="sua_os" id="sua_os" class="frm" value="<? echo $sua_os; ?>" maxlength="20" />
			</td>
			<td>
				<?
				echo "
				<select id='periodo' name='periodo' class='frm'>";

					$ano = intval(date("Y"));
					$mes = intval(date("n"));
					
					//Mêses a partir de julho/2010
					for($a = $ano; $a >= 2010; $a--) {
						if ($a == 2010) {
							$mes_inicial = 7;
						}
						else {
							$mes_inicial = 1;
						}

						if ($a == $ano) {
							$mes_final = $mes;
						}
						else {
							$mes_final = 12;
						}

						for($m = $mes_final; $m >= $mes_inicial; $m--) {
							$valor = "$m/$a";
							$label = substr("0".$m, -2) . "/$a";

							if ($valor == "$periodo_mes/$periodo_ano") {
								$selected = "selected";
							}
							else {
								$selected = "";
							}

							echo "
							<option value='$valor' $selected>$label</option>";
						}
					}
				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>Posto</td>
			<td>Nome do Posto</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="codigo_posto" id="codigo_posto" size="15"  value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto(document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto(document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'nome')">
			</td>
			<td width="10">&nbsp;</td>
		</tr>
				
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td colspan='2'>Status</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td colspan='2'>
				<select id="sinalizador" name="sinalizador" class="frm">
					<option value=''>TODOS</option>";
				<?
				$sql = "
				SELECT
				sinalizador,
				acao

				FROM
				tbl_sinalizador_os

				WHERE
				sinalizador IN (" . implode(",", $sinalizador_pesquisa) . ")
				";
				$res = pg_query($con, $sql);

				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$sinalizador_select = pg_result($res, $i, sinalizador);
					$acao_select = pg_result($res, $i, acao);

					if ($sinalizador == $sinalizador_select) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value='$sinalizador_select' $selected>$acao_select</option>";
				}

				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr >
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr >
			<td colspan="4" align='center' style='padding:20px 0 20px 0;'><input type='button' value='Pesquisar' onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

<?

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	if (strlen($os)) {
		$osWhere = "AND tbl_os.os=$os";
	}

	if (strlen($posto)) {
		$postoWhere = "AND tbl_os.posto=$posto";
	}

	if (strlen($periodo)) {
		$periodo = explode("/", $periodo);
		$periodo_mes_final = substr("0" . (intval($periodo[0])+1), -2);
		$periodo_mes = substr("0" . intval($periodo[0]), -2);
		$periodo_ano = intval($periodo[1]);
		$periodoWhere = "AND tbl_os.data_fechamento BETWEEN '$periodo_ano-$periodo_mes-01'::date AND ('$periodo_ano-$periodo_mes_final-01'::timestamp - INTERVAL '1 DAY')::date
		AND tbl_os_status.data BETWEEN '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp AND '$periodo_ano-$periodo_mes_final-01 00:00:00'::timestamp - INTERVAL '1 SECOND'";
	}

	if (strlen($sinalizador)) {
		$sinalizadorWhere = "AND tbl_os.sinalizador=$sinalizador";
	}

	$sql = "
	SELECT
	tbl_os.os,
	tbl_os.sua_os,
	TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
	TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
	TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
	TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS finalizada,
	TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
	TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto,
	tbl_os.serie,
	tbl_os.consumidor_nome,
	tbl_os.consumidor_fone,
	tbl_os.consumidor_celular,
	tbl_os.consumidor_email,
	tbl_os.consumidor_cidade,
	tbl_os.consumidor_estado

	FROM
	tbl_os
	JOIN tbl_os_status ON tbl_os.os=tbl_os_status.os AND status_os=145

	WHERE
	tbl_os.fabrica=$login_fabrica
	$periodoWhere
	$osWhere
	$postoWhere
	$sinalizadorWhere
	";
	$res = pg_query($con, $sql);
	$numero_registros = pg_num_rows($res);

	$colunas = 13;

	$cabecalho = "
		<tr>
			<td rowspan='3' style='$estilo_os_cabecalho'>OS</td>
			<td colspan='3' style='$estilo_data_cabecalho'>Datas</td>
			<td colspan='3' style='$estilo_produto_cabecalho'>Produto</td>
			<td colspan='4' style='$estilo_consumidor_cabecalho'>Consumidor</td>
			<td colspan='2' style='$estilo_comunicado_cabecalho'>Comunicado</td>
		</tr>
		<tr class='titulo_coluna'>
			<td style='$estilo_data_cabecalho'>Abertura</td>
			<td style='$estilo_data_cabecalho'>Digitação</td>
			<td style='$estilo_data_cabecalho'>Fechamento</td>

			<td colspan='3' style='$estilo_produto_cabecalho'>Produto (Série)</td>

			<td style='$estilo_consumidor_cabecalho'>Nome</td>
			<td style='$estilo_consumidor_cabecalho'>Residencial</td>
			<td style='$estilo_consumidor_cabecalho'>Celular</td>
			<td style='$estilo_consumidor_cabecalho'>Comercial</td>

			<td style='$estilo_comunicado_cabecalho'>Data</td>
			<td style='$estilo_comunicado_cabecalho'>Leitura</td>
		</tr>
		<tr>
			<td style='$estilo_data_cabecalho'>Finalização</td>
			<td style='$estilo_data_cabecalho'>Nota Fiscal</td>
			<td style='$estilo_data_cabecalho'>Conserto</td>

			<td style='$estilo_produto_cabecalho'>Reclamado</td>
			<td style='$estilo_produto_cabecalho'>Constatado</td>
			<td style='$estilo_produto_cabecalho'>Solução</td>

			<td style='$estilo_consumidor_cabecalho'>e-mail</td>
			<td colspan='2' style='$estilo_consumidor_cabecalho'>Cidade</td>
			<td style='$estilo_consumidor_cabecalho'>Estado</td>

			<td colspan='2' style='$estilo_comunicado_cabecalho'>Leitor</td>
		</tr>
		";
	
	echo "
	<table id='tabela_principal' style='$estilo_principal' align='center'>
	$cabecalho
	<tbody>";

	$total = 0;
	for ($i = 0; $i < pg_num_rows($res); $i++) {
		//Recupera os valores do resultado da consulta
		$valores_linha = pg_fetch_array($res, $i);

		//Transforma os resultados recuperados de array para variáveis
		extract($valores_linha);

		$sql = "
		SELECT
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome AS posto_nome,
		tbl_produto.referencia AS produto_referencia,
		tbl_produto.descricao AS produto_descricao,
		tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
		tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
		tbl_solucao.descricao AS solucao_descricao,
		TO_CHAR(tbl_comunicado.data, 'DD/MM/YYYY') AS comunicado_data,
		TO_CHAR(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS comunicado_data_confirmacao,
		tbl_comunicado_posto_blackedecker.leitor AS comunicado_leitor

		FROM
		tbl_os
		JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto
			 AND tbl_os.fabrica=tbl_posto_fabrica.fabrica
		JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
		LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado=tbl_defeito_reclamado.defeito_reclamado
		LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
		LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
		LEFT JOIN tbl_os_comunicado ON tbl_os.os=tbl_os_comunicado.os
		LEFT JOIN tbl_comunicado ON tbl_os_comunicado.comunicado=tbl_comunicado.comunicado
				  AND tbl_comunicado.tipo='F AUT'
		LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado.comunicado=tbl_comunicado_posto_blackedecker.comunicado

		WHERE
		tbl_os.os=$os
		";
		$res_adicionais = pg_query($con, $sql);

		//Recupera os valores do resultado da consulta
		$valores_linha = pg_fetch_array($res_adicionais, 0);

		//Transforma os resultados recuperados de array para variáveis
		extract($valores_linha);

		if ($i % 10 == 0 && $i != 0) {
			echo $cabecalho;
		}

		if ($i % 2){
			$estilo_os = $estilo_os_linha0;
			$estilo_data = $estilo_data_linha0;
			$estilo_produto = $estilo_produto_linha0;
			$estilo_consumidor = $estilo_consumidor_linha0;
			$estilo_comunicado = $estilo_comunicado_linha0;
		}
		else {
			$estilo_os = $estilo_os_linha1;
			$estilo_data = $estilo_data_linha1;
			$estilo_produto = $estilo_produto_linha1;
			$estilo_consumidor = $estilo_consumidor_linha1;
			$estilo_comunicado = $estilo_comunicado_linha1;
		}

		echo "
		<tr><td style='$estilo_os; height:5px;' colspan='$colunas'></td></tr>
		<tr>
			<td style='$estilo_os' rowspan='2'><a href='os_press.php?os=$os' style='$estilo_os; text-decoration:underline;' title='Clique aqui para consultar a OS em uma nova janela' target='_blank'>$sua_os</a></td>

			<td style='$estilo_data'>$data_abertura</td>
			<td style='$estilo_data'>$data_digitacao</td>
			<td style='$estilo_data'>$data_fechamento</td>

			<td colspan='3' style='$estilo_produto'>$produto_referencia - $produto_descricao ($serie)</td>

			<td style='$estilo_consumidor'>$consumidor_nome</td>
			<td style='$estilo_consumidor'>$consumidor_telefone</td>
			<td style='$estilo_consumidor'>$consumidor_celular</td>
			<td style='$estilo_consumidor'>$consumidor_comercial</td>

			<td style='$estilo_comunicado'>$comunicado_data</td>
			<td style='$estilo_comunicado'>$comunicado_data_confirmacao</td>
		</tr>
		<tr>
			<td style='$estilo_data'>$finalizada</td>
			<td style='$estilo_data'>$data_nf</td>
			<td style='$estilo_data'>$data_conserto</td>

			<td style='$estilo_produto'>$defeito_reclamado_descricao</td>
			<td style='$estilo_produto'>$defeito_constatado_descricao</td>
			<td style='$estilo_produto'>$solucao_descricao</td>

			<td style='$estilo_consumidor'>$consumidor_email</td>
			<td colspan='2' style='$estilo_consumidor'>$consumidor_cidade</td>
			<td style='$estilo_consumidor'>$consumidor_estado</td>

			<td colspan='2' style='$estilo_comunicado'>$comunicado_leitor</td>
		</tr>
		";

		$sql = "
		SELECT
		tbl_os_item.os_item,
		tbl_peca.peca,
		tbl_peca.referencia AS peca_referencia,
		tbl_peca.descricao AS peca_descricao,
		tbl_os_item.qtde,
		TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
		tbl_defeito.descricao AS defeito_descricao,
		tbl_servico_realizado.descricao AS servico_realizado_descricao,
		tbl_os_item.pedido,
		tbl_os_item_nf.nota_fiscal,
		TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf

		FROM
		tbl_os_produto
		JOIN tbl_os_item USING (os_produto)
		JOIN tbl_produto USING (produto)
		JOIN tbl_peca USING (peca)
		LEFT JOIN tbl_defeito USING (defeito)
		LEFT JOIN tbl_servico_realizado USING (servico_realizado)
		LEFT JOIN tbl_admin ON tbl_os_item.admin = tbl_admin.admin
		LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
		LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
		LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido

		WHERE
		tbl_os_produto.os = $os

		ORDER BY
		tbl_peca.descricao 
		";
		$res_itens = pg_query($con, $sql);

		if (pg_num_rows($res_itens)) {
			//Recupera os valores do resultado da consulta
			$valores_linha_itens = pg_fetch_array($res_itens, $j);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha_itens);

			$sql = "
			SELECT
			tbl_extrato_lancamento.extrato,
			tbl_extrato_lancamento.posto,
			tbl_extrato_lancamento.valor

			FROM
			tbl_extrato_lancamento_os
			JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento_os.extrato_lancamento=tbl_extrato_lancamento.extrato_lancamento

			WHERE
			tbl_extrato_lancamento_os.os=$os
			";
			$res_extrato = pg_query($con, $sql);
			$valor = "";
			
			if (pg_num_rows($res_extrato)) {
				$extrato_posto = pg_result($res_extrato, 0, posto);
				$extrato = pg_result($res_extrato, 0, extrato);
				$valor = pg_result($res_extrato, 0, valor);

				if (strlen($extrato)) {
					$extrato_link = "<a href='extrato_posto_mao_obra_novo_britania.php?extrato=$extrato&posto=$extrato_posto' target='blank' title='Clique aqui para consultar o extrato em uma nova janela' style='$estilo_os; text-decoration:underline;'>$extrato</a>";
				}
				else {
					$extrato_link = "Próximo";
				}

				$debito_pecas = "<br>Extrato Débito: $extrato_link<br>Valor: " . number_format($valor, 2, ",", ".");
			}
			else {
				$debito_pecas = "";
			}

			echo "
		<tr>
			<td style='$estilo_os' rowspan='" . (pg_num_rows($res_itens)+1) . "'>Peças$debito_pecas</td>
			<td colspan=3 style='$estilo_os'>Componente</td>
			<td style='$estilo_os' >Qtde</td>
			<td style='$estilo_os'>Digitação</td>
			<td style='$estilo_os'>Defeito</td>
			<td style='$estilo_os'>Serviço</td>
			<td style='$estilo_os'>Pedido</td>
			<td style='$estilo_os'>Nota</td>
			<td style='$estilo_os'>Emissão</td>
			<td style='$estilo_os' colspan='2'>Valor</td>
		</tr>
			";

			for($p = 0; $p < pg_num_rows($res_itens); $p++) {
				$preco = false;
				$preco_formatado = false;
				$sql = "
				SELECT
				tbl_os_item.preco

				FROM
				tbl_os_item
				JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item

				WHERE
				os_item=$os_item
				";
				$res_preco = pg_query($con, $sql);

				if (pg_num_rows($res_preco)) {
					$preco = pg_result($res_preco, 0, preco);
				}
				elseif (strlen($pedido)) {
					$sql = "
					SELECT
					AVG(tbl_pedido_item.preco) AS preco

					FROM
					tbl_pedido_item

					WHERE
					pedido=$pedido
					AND peca=$peca
					";
					$res_preco = pg_query($con, $sql);

					if (pg_num_rows($res_preco)) {
						$preco = pg_result($res_preco, 0, preco);
					}
				}
				else {
					$sql = "
					SELECT
					preco

					FROM
					tbl_os_item

					WHERE
					os_item=$os_item
					";
					$res_preco = pg_query($con, $sql);

					if (pg_num_rows($res_preco)) {
						$preco = pg_result($res_preco, 0, preco);
					}
				}
				
				if ($preco) {
					$preco_formatado = number_format($preco, 2, ",", ".");
				}
				else {
					$preco_formatado = "";
				}

				if (strlen($pedido)) {
					$pedido_link = "<a href='pedido_admin_consulta.php?pedido=$pedido' title='Clique aqui para consultar o pedido em uma nova janela' target='_blank' style='color:#000000; text-decoration:underline;'>$pedido</a>";
				}
				else {
					$pedido_link = "";
				}

				echo "
		<tr>
			<td colspan='3' style='$estilo_os_item'>$peca_referencia - $peca_descricao</td>
			<td style='$estilo_os_item'>$qtde</td>
			<td style='$estilo_os_item'>$digitacao_item</td>
			<td style='$estilo_os_item'>$defeito_descricao</td>
			<td style='$estilo_os_item'>$servico_realizado_descricao</td>
			<td style='$estilo_os_item'>$pedido_link</td>
			<td style='$estilo_os_item'>$nota_fiscal</td>
			<td style='$estilo_os_item'>$data_nf</td>
			<td style='$estilo_os_item' colspan='2'>$preco_formatado</td>
		</tr>
				";
			}
		}

		echo "
		<tr><td style='$estilo_os; height:5px;' colspan='$colunas'></td></tr>";
	}

	if ($numero_registros) {
		echo "
		<tr>
			<td colspan=$colunas align='center'>
				<b>Quantidade de registros listados: $numero_registros</b>
			</td>
		</tr>
	</tbody>
	</table>";
	}
	
	else {
		echo "<br><FONT size='2' COLOR=\"#FF3333\"><B>Não encontrado!</B></FONT><br><br>";
	}
}
echo "<br>";


include "rodape.php";
?>
