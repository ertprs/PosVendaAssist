<?
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
<div id='status' style='width:700px;margin:auto;'></div>
<div id='carregando' align='center' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<div class="titulo_tabela" style="width:700px;margin:auto;">Parâmetros de Pesquisa</div>
<table width='700' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="20%">&nbsp;</td>
					<td align='right'>Mês</td>
					<td align='left'>
						<select name="mes" size="1" class="frm">
							<option value=''></option>
							<?
							$meses = array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
							for ($i = 1 ; $i <= count($meses) ; $i++) {
								echo "<option value='$i'";
								if ($mes == $i) echo " selected";
								echo ">" . $meses[$i] . "</option>";
							}
							?>
						</select>
						&nbsp;&nbsp;Ano</font>
						<select name="ano" size="1" class="frm">
							<option value=''></option>
							<?
							for ($i = 2003 ; $i <= date("Y") ; $i++) {
								echo "<option value='$i'";
								if ($ano == $i) echo " selected";
								echo ">$i</option>";
							}
							?>
						</select>
						&nbsp;&nbsp;País
						<select id="pais" name="pais" class="frm">
							<?=$opcoes_pais?>
						</select>
					</td>
				</tr>
			</table><br>

			<input type='submit' name='btn_acao' value='Consultar' style='margin-left:300px;' />

		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
</table>
</FORM>

<?

$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) > 0 && !empty($_POST['mes']) && !empty($_POST['ano']) ) {

	$meses = array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

	$pais = $_POST["pais"];
	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	$mes_ano_inicial = $mes . '/' . $ano; //hd #292017
	
	$data_inicial    = explode("/", $mes_ano_inicial);
	$data_inicial[0] = intval($data_inicial[0]);
	if($data_inicial[0] < 10)
		$data_inicial[0] = '0' . $data_inicial[0];
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
	if(pg_numrows($res_periodos) == 0)
		echo 'Nenhum Resultado Encontrado.';
	else
	{
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
			JOIN tbl_os       ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_posto    ON tbl_os.posto = tbl_posto.posto

			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_posto.pais = '$pais'
			AND   tbl_extrato.liberado BETWEEN '$data_inicial' AND '$data_final'
			";

			echo $sql . "<br>-------------------------------------------------------------------<br>";

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
		
		echo '<br /><br /><table align="center" class="tabela" width="700">';

		echo "<tr class='titulo_coluna'>";
		echo "<td width=100 class=contitulo> Período </td>";
		for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
			echo "<td width='120'>";
			echo $resposta_array[0][$i];
			echo "</td>";
		}
		echo "</tr>";

		echo "<tr bgcolor='#F7F5F0'>";
		echo "<td width=100> Qtde OS </td>";
		for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
			echo "<td width='120'>";
			echo $resposta_array[1][$i];
			echo "</td>";
		}
		echo "</tr>";

		echo "<tr bgcolor='#F1F4FA'>";
		echo "<td width=100 class=contitulo> Peças R$ </td>";
		for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
			echo "<td width='120'>";
			echo $resposta_array[2][$i];
			echo "</td>";
		}
		echo "</tr>";

		echo "<tr bgcolor='#F7F5F0'>";
		echo "<td width=100 class=contitulo> Mão-de-Obra R$ </td>";
		for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
			echo "<td width='120'>";
			echo $resposta_array[3][$i];
			echo "</td>";
		}
		echo "</tr>";

		echo "<tr bgcolor='#F1F4FA'>";
		echo "<td width=100 class=contitulo> Total Pago R$ </td>";
		for ($i = 0 ; $i < pg_numrows($res_periodos) ; $i++) {
			echo "<td width='120'>";
			echo $resposta_array[4][$i];
			echo "</td>";
		}
		echo "</tr>";

		echo "<tr bgcolor='#F7F5F0'>";
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
}
else if(strlen ($btn_acao) > 0 && ( empty($_POST['mes']) || empty($_POST['ano']) ) )
	echo '<div id="msg" class="msg_erro">Preencha todos os campos</div>';
?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	$("#msg").appendTo("#status");
</script>


<? include "rodape.php" ?>
