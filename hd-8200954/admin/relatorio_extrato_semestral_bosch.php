<?
//CHAMADO:		109308
//PROGRAMADOR:	EBANO LOPES
//SOLICITANTE:	20 - BOSCH

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';

$admin_privilegios="financeiro";
$layout_menu = "financeiro";
$title = "CONTROLE DE GARANTIA SEMESTRAL";

include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario.php";

?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#mes_ano_inicial").maskedinput("99/9999");
	});
</script>


<!-- ******************************** FIM JAVASCRIPT ******************************** -->

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<style>
.contitulo {
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.conlinha0 {
	background-color: #CCCCCC;
}

.conlinha1 {
	background-color: #FFFFFF;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>


<?php

	$sql_opcoes = "SELECT pais, nome FROM tbl_pais ORDER BY nome";
	$res_opcoes = pg_exec ($con,$sql_opcoes);
	$n_opcoes = pg_numrows($res_opcoes);
	$opcoes_pais = "";
	if ($_POST["pais"]) $pais = $_POST["pais"];
	else $pais = "BR";

	for($j = 0; $j < $n_opcoes; $j++)
	{
		$pais_valor = pg_result ($res_opcoes,$j,0);
		$nome_valor = pg_result ($res_opcoes,$j,1);
		if ($pais_valor == $pais) $selected_pais = " selected "; else $selected_pais = "";

		$opcoes_pais .= "<option $selected_pais value=$pais_valor>$nome_valor</option>";
	}

?>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div>
Informe o mês e o ano do início da consulta
</div>
<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' align='center' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'><?=$title?></td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Mês/Ano Inicial - Ex: 01/2009</td>
					<td align='left'>
						<input type="text" name="mes_ano_inicial" size="12" maxlength="7" class="Caixa" value="<?=$mes_ano_inicial?>">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>País</td>
					<td align='left'>
						<select id="pais" name="pais">
							<?=$opcoes_pais?>
						</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='submit' name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>





<?

$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) > 0) {

	$meses = array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
	$pais = $_POST["pais"];

	$mes_ano_inicial = $_POST['mes_ano_inicial'];
	$data_inicial    = explode("/", $mes_ano_inicial);
	$data_inicial[0] = intval($data_inicial[0]);
	$data_inicial    = $data_inicial[1] . "-" . $data_inicial[0] . "-01";

	$sql = "SELECT '$data_inicial'::date + INTERVAL '6 month'";
	$res = pg_exec ($con,$sql);
	$data_final = pg_result ($res,0,0);

	$sql = "
	SELECT
	liberado
	
	FROM
	tbl_extrato
	
	WHERE
	fabrica=$login_fabrica
	AND liberado>'$data_inicial'
	AND liberado<'$data_final'
	
	GROUP BY
	liberado
	
	ORDER BY
	liberado
	";
	$res_periodos = pg_exec($con, $sql);

	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++)
	{
		$data_final = pg_result($res_periodos, $i, 0);

		$sql = "
		SELECT
		COUNT(*)                AS qtde_os     ,
		SUM(tbl_os.pecas)       AS pecas       ,
		SUM(tbl_os.mao_de_obra) AS mao_de_obra

		FROM tbl_extrato 
		JOIN tbl_os_extra USING (extrato)
		JOIN tbl_os       ON tbl_os.os = tbl_os_extra.os and tbl_os.fabrica =$login_fabrica
		JOIN tbl_posto    ON tbl_os.posto = tbl_posto.posto

		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_posto.pais = '$pais'
		AND   tbl_extrato.liberado BETWEEN '$data_inicial' AND '$data_final'
		";

//		echo $sql . "<br>-------------------------------------------------------------------<br>";

		$res = pg_exec($con, $sql);

		$qtde_os     = pg_result ($res,0,qtde_os);
		$pecas       = pg_result ($res,0,pecas);
		$mao_de_obra = pg_result ($res,0,mao_de_obra);

		$total = $pecas + $mao_de_obra;
		if ($qtde_os > 0) {
			$media = number_format($total / $qtde_os, 2, ",", ".");
		}else{
			$media = "-";
		}

		$mao_de_obra = number_format($mao_de_obra, 2, ",", ".");
		$pecas       = number_format($pecas, 2, ",", ".");
		$total       = number_format($total, 2, ",", ".");

		$sql = "SELECT EXTRACT (MONTH FROM '$data_inicial'::date)";
		$res = pg_exec ($con,$sql);
		$mes = pg_result ($res,0,0);

		$sql = "SELECT EXTRACT (YEAR FROM '$data_inicial'::date)";
		$res = pg_exec ($con,$sql);
		$ano = pg_result ($res,0,0);

		$inicio_periodo = implode("/", array_reverse(explode("-", $data_inicial)));
		$fim_periodo = implode("/", array_reverse(explode("-", $data_final)));

		$resposta_array[0][$i] = "<b>Data do Extrato:</b> $fim_periodo<br><b>Processo Telecontrol:</b>$inicio_periodo a $fim_periodo<br>";
		$resposta_array[1][$i] = $qtde_os;
		$resposta_array[2][$i] = $pecas;
		$resposta_array[3][$i] = $mao_de_obra;
		$resposta_array[4][$i] = $total;
		$resposta_array[5][$i] = $media;

		$sql = "SELECT TO_CHAR('$data_final'::date + INTERVAL '1 day', 'YYYY-MM-DD')";
		$res = pg_exec ($con,$sql);
		$data_inicial = pg_result ($res, 0, 0);
	}

	echo "<table align=center class=Conteudo>";

	echo "<tr class=conlinha0>";
	echo "<td width=100 class=contitulo> Período </td>";
	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
		echo "<td width='120'>";
		echo $resposta_array[0][$i];
		echo "</td>";
	}
	echo "</tr>";

	echo "<tr class=conlinha1>";
	echo "<td width=100 class=contitulo> Qtde OS </td>";
	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
		echo "<td width='120'>";
		echo $resposta_array[1][$i];
		echo "</td>";
	}
	echo "</tr>";

	echo "<tr class=conlinha0>";
	echo "<td width=100 class=contitulo> Peças R$ </td>";
	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
		echo "<td width='120'>";
		echo $resposta_array[2][$i];
		echo "</td>";
	}
	echo "</tr>";

	echo "<tr class=conlinha1>";
	echo "<td width=100 class=contitulo> Mão-de-Obra R$ </td>";
	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
		echo "<td width='120'>";
		echo $resposta_array[3][$i];
		echo "</td>";
	}
	echo "</tr>";

	echo "<tr class=conlinha0>";
	echo "<td width=100 class=contitulo> Total Pago R$ </td>";
	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
		echo "<td width='120'>";
		echo $resposta_array[4][$i];
		echo "</td>";
	}
	echo "</tr>";

	echo "<tr class=conlinha1>";
	echo "<td width=100 class=contitulo> Média por OS R$ </td>";
	for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
		echo "<td width='120'>";
		echo $resposta_array[5][$i];
		echo "</td>";
	}

	echo "</tr>";
	echo "</table>";
	echo "<br><br><br>";

}
?>




<? include "rodape.php" ?>
