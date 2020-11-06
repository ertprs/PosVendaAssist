<?
$title = "DOWNLOADS POR MÊS MANUAL DE SERVIÇO";
$layout_menu = 'tecnica';

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "cabecalho.php";

if($_GET["mes"]) $mes = $_GET["mes"];
else $mes = date("n");

if($_GET["ano"]) $ano = $_GET["ano"];
else $ano = date("Y");

if($_GET["periodo"]) $periodo = $_GET["periodo"];
else $periodo = 1;

$tipo       = $_GET ['tipo'];
$familia    = $_GET ['familia'];
$linha      = $_GET ['linha'];

?>

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

.mesano {
	font-family: Arial;
	FONT-SIZE: 11pt; 
}

.Tabela{
	border:1px solid #485989;
	
}
img{
	border: 0px;
}
</style>

<table width = '700' class='formulario' align = 'center' cellpadding='5' cellspacing='0' border='0' >
<form name='frm_relatorio' method='get'>
<tr class='titulo_tabela'><td colspan='3'>Parâmetros de Pesquisa</td></tr>
<tr><td colspan='3'>&nbsp;</td></tr>
<tr >
	<td align='center'>
		Mês Inicial: 
		<select name='mes' size='1'>
		<?php
			$mesString[1] = "janeiro";
			$mesString[2] = "fevereiro";
			$mesString[3] = "março";
			$mesString[4] = "abril";
			$mesString[5] = "maio";
			$mesString[6] = "junho";
			$mesString[7] = "julho";
			$mesString[8] = "agosto";
			$mesString[9] = "setembro";
			$mesString[10] = "outubro";
			$mesString[11] = "novembro";
			$mesString[12] = "dezembro";

			for($i=1; $i<=12; $i++)
			{
				if ($i == intval($mes))
					$selected = "SELECTED";
				else
					$selected = "";

				echo "
				<option value='$i' $selected>" . $mesString[$i] . "</option>";
			}
		?>
		</select>
		Ano Inicial:
		<select name='ano' size='1'>
		<?php
			$ano_comeco_relatorio = 2009;
			$ano_final = date("Y");
			$ano_inicial = $ano_final - 5;
			if($ano_inicial < $ano_comeco_relatorio) $ano_inicial = $ano_comeco_relatorio;

			for($i=$ano_inicial; $i<=$ano_final; $i++)
			{
				if ($i == intval($ano))
					$selected = "SELECTED";
				else
					$selected = "";

				echo"
				<option value='$i' $selected>$i</option>";
			}
		?>
		</select>
		Período:
		<select name="periodo">
		<?php
			for($i=1; $i<=6; $i++)
			{
				if ($i == intval($periodo))
					$selected = "SELECTED";
				else
					$selected = "";

				if ($i == 1) $label = "Mês";
				else $label = "Meses";

				echo"
				<option value='$i' $selected>$i $label</option>";
			}
		?>
		</select>
		<input type='hidden' name='tipo' value='<?php echo $tipo; ?>'>
		<input type='hidden' name='linha' value='<?php echo $linha; ?>'>
		<input type='hidden' name='familia' value='<?php echo $familia; ?>'>
		<input type='submit' name='btn_acao' value='Gerar Relatório'>
	</td>
</tr>
</form>
<tr><td colspan='3'>&nbsp;</td></tr>
</table>
<br />
<?

if (strlen($tipo))
{
	$tipo = urldecode ($tipo);
	$mes_ano = array();

	for($i = 0; $i < $periodo; $i++)
	{
		$mes_atual = $mes + $i;
		if($mes_atual > 12)
		{
			$mes_atual = $mes_atual - 12;
			$ano_atual = $ano + 1;
		}
		else
		{
			$ano_atual = $ano;
		}

		$mes_ano[] = "tbl_comunicado_download.mes = $mes_atual AND tbl_comunicado_download.ano = $ano_atual";
	}

	$mes_ano = "(" . implode(") OR (", $mes_ano) . ")";

	if ($tipo) $tipo_cond = "AND tbl_comunicado.tipo = '$tipo'";
	if ($linha) $linha_cond = "AND tbl_produto.linha = '$linha'";
	if ($familia) $familia_cond = "AND tbl_produto.familia = '$familia'";

	$sql = "
	SELECT
	SUM(CASE WHEN tbl_comunicado_download.qtde IS NULL THEN 0 ELSE tbl_comunicado_download.qtde END) AS downloads,
	tbl_produto.referencia,
	tbl_produto.descricao

	FROM
	tbl_comunicado
	JOIN tbl_produto ON tbl_comunicado.produto = tbl_produto.produto
	LEFT JOIN tbl_comunicado_download ON tbl_comunicado.comunicado=tbl_comunicado_download.comunicado AND ($mes_ano)

	WHERE
	tbl_comunicado.fabrica=$login_fabrica
	AND tbl_comunicado.ativo IS TRUE
	$tipo_cond
	$linha_cond
	$familia_cond

	GROUP BY
	tbl_comunicado.comunicado,
	tbl_produto.referencia,
	tbl_produto.descricao

	ORDER BY
	downloads DESC,
	tbl_produto.descricao ASC
	";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0)
	{
		if(strlen($familia)>0)
		{
			$sql = "SELECT descricao FROM tbl_familia WHERE familia=$familia";
			$res_familia = pg_exec ($con, $sql);
			$descricao_familia = trim(pg_result($res_familia, 0, descricao));
		}

		echo "<table width='700' align='center' cellspacing='1' cellpadding='0' border='0' class='tabela'>";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='4' height='25'>$title - $descricao_familia</td>";
		echo "</tr>";
		echo "</b></td>";
		echo "</tr>";
		
		echo "<tr class='titulo_coluna' >";
		echo "<td width='100'>Downloads</td>";
		echo "<td width='150'>Referência</td>";
		echo "<td width='450'>Produto</td>";
		echo "</tr>";
		
		$total = pg_numrows ($res);
	
		for ($i=0; $i<$total; $i++) {
			$referencia            = trim(pg_result ($res,$i,referencia));
			$descricao             = trim(pg_result ($res,$i,descricao));
			$downloads             = trim(pg_result ($res,$i,downloads));
			if ($downloads == 0) $downloads = "-";
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			echo "<tr bgcolor='$cor'>";
			echo "<td align='center'>$downloads </td>";
			echo "<td align='center'>$referencia </td>";
			echo "<td nowrap align='left'>";
			
			if (strlen ($descricao) > 0)
			{
				echo $descricao;
				if($login_fabrica==14) echo " - ".$comunicado_descricao;
			}
			else
			{
				echo $comunicado_descricao;
			}

			echo "</td>";
			echo "</tr>";
		}
		echo "</form>";
		echo "</table>";
	
		echo "<hr>";
	}else{
		echo "<center>Nenhum $tipo cadastrado</center>";
	}
}

?>