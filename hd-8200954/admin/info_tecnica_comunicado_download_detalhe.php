<?
$title = "Downloads por mês Manual de Serviço";
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
$posto      = $_GET ['posto'];

?>

<style>


.mesano {
	font-family: Arial;
	FONT-SIZE: 11pt; 
}

img{
	border: 0px;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
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
</style>

<table width = '700' class='formulario' align = 'center' cellpadding='5' cellspacing='0' border='0' >
<form name='frm_relatorio' method='get'>
<tr>
	<td align='center'>
		Mês Inicial 
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
				<option value='$i' $selected>" . ucwords($mesString[$i]) . "</option>";
			}
		?>
		</select>
		Ano Inicial
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
		Período
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
		<input type='hidden' name='posto' value='<?php echo $posto; ?>'>
		<input type='submit' name='btn_acao' value='Gerar Relatório'>
	</td>
</tr>
</form>
</table>
<br>
<?

if (!empty($tipo)) {
	$tipo = urldecode ($tipo);


	$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));

	if(strlen($periodo) > 0 and $periodo > 1) {
		$xmes = $mes + $periodo-1;
	}else{
		$xmes = $mes;
	}
	$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $xmes, 1, $ano));
	
	list($yi, $mi, $di) = explode("-", $xdata_final);
	if(substr($mi,0,-1) == '0'){
		$mi = substr($mi,1);
	}

	if ($tipo) $tipo_cond = "AND tbl_comunicado.tipo = '$tipo'";

	$sql = "SELECT  count(tbl_comunicado_download_log.comunicado_download_log) as downloads,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM tbl_comunicado
			JOIN tbl_produto ON tbl_comunicado.produto = tbl_produto.produto
			JOIN tbl_comunicado_download_log ON tbl_comunicado.comunicado = tbl_comunicado_download_log.comunicado and tbl_comunicado_download_log.posto = $posto and tbl_comunicado_download_log.data between '$xdata_inicial' and '$xdata_final'
			WHERE tbl_comunicado.fabrica=$login_fabrica
			AND   tbl_comunicado.ativo IS TRUE
			$tipo_cond
			GROUP BY tbl_produto.referencia,
					 tbl_produto.descricao
			ORDER BY downloads DESC,
					 tbl_produto.descricao ASC ";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0)
	{
		if(strlen($familia)>0)
		{
			$sql = "SELECT descricao FROM tbl_familia WHERE familia=$familia";
			$res_familia = pg_query ($con, $sql);
			$descricao_familia = trim(pg_result($res_familia, 0, descricao));
		}

		echo "<table width='700' align='center' class='tabela' cellspacing='0' cellpadding='0' border='0'>";
		echo "<tr class='titulo_coluna'>";
		if($periodo > 1 and $ano != $yi){
			echo "<tr class='titulo_coluna'><td colspan='4' style='font-size:14px;'>De ". ucwords($mesString[$mes])." de ".$ano." a ".ucwords($mesString[$mi])." de ".$yi."</td></tr>";
		}
		else{
			if($ano == $yi and $periodo > 1){
				echo "<tr class='titulo_coluna'><td colspan='4' style='font-size:14px;'>De ". ucwords($mesString[$mes])." a ".ucwords($mesString[$mi])." de ".$yi."</td></tr>";
			}
			else{
				echo "<tr class='titulo_coluna'><td colspan='4' style='font-size:14px;'>". ucwords($mesString[$mes])." de ".$ano."</td></tr>";
			}
		}
		echo "</tr>";
		echo "</b></td>";
		echo "</tr>";
		
		echo "<tr class='titulo_coluna' >";
		echo "<td  width='100'>Downloads</td>";
		echo "<td  width='150'>Referência</td>";
		echo "<td  width='450'>Produto</td>";
		echo "</tr>";
		
		$total = pg_num_rows ($res);
	
		for ($i=0; $i<$total; $i++) {
			$referencia            = trim(pg_result ($res,$i,referencia));
			$descricao             = trim(pg_result ($res,$i,descricao));
			$downloads             = trim(pg_result ($res,$i,downloads));
			if ($downloads == 0) $downloads = "-";
	
			$cor = ($i % 2 == 0) ? '#F7F5F0' : "#F1F4FA";
	
			echo "<tr bgcolor='$cor' class='conteudo'>";
			echo "<td align='center'>$downloads </td>";
			echo "<td align='center'>$referencia </td>";
			echo "<td nowrap >$descricao</td>";
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