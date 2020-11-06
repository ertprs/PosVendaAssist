<?php
require_once "dbconfig.php";
require_once "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
require_once 'autentica_admin.php';

$meses = array();
$meses[1] ="Janeiro";
$meses[2] ="Fevereiro";
$meses[3] ="Março";
$meses[4] ="Abril";
$meses[5] ="Maio";
$meses[6] ="Junho";
$meses[7] ="Julho";
$meses[8] ="Agosto";
$meses[9] ="Setembro";
$meses[10] ="Outubro";
$meses[11] ="Novembro";
$meses[12] ="Dezembro";


// Autocomplete ajax
if (isset($_GET["q"])){
	$q = utf8_decode(strtoupper($_GET["q"]));
	if (strlen($q)>2){
		if ($_GET["busca"] == "produto_referencia"){
			$sql="
			SELECT tbl_produto.produto,  tbl_produto.referencia || ' - ' || tbl_produto.descricao AS descricao
			FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha 
			WHERE tbl_linha.fabrica = {$login_fabrica} 
			AND (tbl_produto.referencia_pesquisa LIKE '{$q}%' OR tbl_produto.descricao LIKE '%{$q}%')";
		}
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$produto_codigo 	= trim(pg_fetch_result($res,$i,produto));
					$produto_descricao	= trim(pg_fetch_result($res,$i,descricao));
					echo "$produto_descricao|$produto_codigo";
					echo "\n";
				}
			}
		
	}
	exit;
}
if(isset($_POST['btn_gerar_idc']) || isset($_POST['btn_gerar_idc_mensal'])){
	if($_POST['familia'] != "" && strlen($_POST['produto_referencia'])>0){
		$msg_erro = "Selecione Produto ou Família";
	}
	if(($_POST['familia'] == "" && strlen($_POST['produto_referencia']) == 0) && isset($_POST['btn_gerar_idc_mensal'])){
		$msg_erro = "Informe a Família ou o Produto";
	}
	else{
		
		$mes_referente = intval($_POST['mes_referente']);
		$ano_referente = intval($_POST['ano_referente']);
		
		if($ano_referente > date("Y")){
			$msg_erro = "Data inválida";
		}
		
		if($ano_referente == date("Y")){
			if($mes_referente >= date("m")){	
				$msg_erro = "Data inválida";
			}
		}

		if(strlen($msg_erro) == 0){
			
			$ano_final =$ano_referente + floor(($mes_referente + 11)/13);
			$mes_final = ($mes_referente + 11)%12;
			$mes_final = $mes_final == 0 ? 12 : $mes_final;
			
			//echo "{$mes_referente}/{$ano_referente} => {$mes_final}/{$ano_final}";
			
			if ($_POST['familia'] == "" && strlen($_POST['produto_referencia']) == 0){
				$sql = "
					SELECT
					familia AS descricao,
					volume_garantia,
					qtde_defeito,
					(qtde_defeito*1000/volume_garantia) AS idc,
					meta_familia AS meta,
					((qtde_defeito*1000/volume_garantia) - meta_familia) AS desvio
					
					FROM (
					SELECT
					tbl_familia.descricao AS familia,
					meta_familia,
					SUM(qtde_producao) AS volume_garantia,
					SUM(indice) AS qtde_defeito
					
					FROM
					tbl_producao_defeito JOIN tbl_produto ON tbl_producao_defeito.produto=tbl_produto.produto
					JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia
					
					WHERE
					tbl_familia.fabrica={$login_fabrica}
					AND mes_producao={$mes_referente}
					AND ano_producao={$ano_referente}
					
					GROUP BY
					tbl_familia.descricao,
					meta_familia
					
					HAVING
					SUM(qtde_producao) > 0
					) AS dados
				";

			}
			
			if(strlen($_POST['produto_referencia'])>0 || strlen($_POST['familia'])>0){
				
				if(strlen($_POST['produto_referencia'])>0){
					$produto_codigo = intval($_POST['produto_codigo']);
					$descricao_campo = "Produto";
					$condicao = "AND tbl_produto.produto = {$produto_codigo}";
					$select = "produto";
					$sub_select = "tbl_produto.descricao AS produto";
					$campo_meta = "meta_produto";
					$group_by = "tbl_produto.descricao";
					$campo = "meta_produto";
					
				}
				
				if(strlen($_POST['familia'])>0 ){
					$familia = intval($_POST['familia']);
					$condicao = "AND tbl_familia.familia = {$familia}";
					$descricao_campo = "Família";
					$select = "familia";
					$sub_select = "tbl_familia.descricao AS familia";
					$campo_meta = "meta_familia";
					$group_by ="tbl_familia.descricao";
					$campo ="meta_familia";
					
				}
				$sql = "
					SELECT
					{$select} AS descricao,
					volume_garantia,
					qtde_defeito,
					mes_producao,
					ano_producao,
					(qtde_defeito*1000/volume_garantia) AS idc,
					meta,
					((qtde_defeito*1000/volume_garantia) - meta) AS desvio
					
					FROM (
					SELECT
					{$sub_select},
					{$campo_meta} AS meta,
					mes_producao,
					ano_producao,
					SUM(qtde_producao) AS volume_garantia,
					SUM(indice) AS qtde_defeito
					
					FROM
					tbl_producao_defeito JOIN tbl_produto ON tbl_producao_defeito.produto=tbl_produto.produto
					JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia
					
					WHERE
					tbl_familia.fabrica={$login_fabrica}
					{$condicao}
					AND (mes_producao >= {$mes_referente} AND ano_producao = {$ano_referente} OR mes_producao <= {$mes_final} AND ano_producao = {$ano_final})
					
					GROUP BY
					{$group_by},
					{$campo},
					mes_producao,
					ano_producao
					
					HAVING
					SUM(qtde_producao) > 0
					) AS dados
					
					ORDER BY
					ano_producao,
					mes_producao
					";
				
			}
			
				$res = pg_query($con, $sql);
				if(pg_num_rows($res)>0){
					$i = 0;
					while ($linha = pg_fetch_array($res)){
						extract($linha);
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						$result_pesquisa .= "
						<tr bgcolor='{$cor}' align='center'>
							<td class='result'>{$descricao}</td>
							<td>{$volume_garantia}</td>
							<td class='qtde_defeito'>{$qtde_defeito}</td>";
						
						if(strlen($_POST['produto_referencia'])>0 || strlen($_POST['familia'])>0){
							$btn_grafico ="liberar";
							$result_pesquisa .= "
							<td class='mes'>{$meses[$mes_producao]}</td>
							<td>{$ano_producao}</td>";
						}
						$idc = round($idc, 2);
						$meta = round($meta, 2);
						$desvio = round($desvio, 2);
						$result_pesquisa .= "
							<td class='idc'>{$idc}</td>
							<td class='meta'>{$meta}</td>
							<td>{$desvio}</td>
						<tr>";
						$i++;
						$idc_acumulada += $idc;
						$meta_acumulada += $meta;
					}
				}else{
					$msg_erro ="Não foram encontrados registros para esta consulta";
				}

		}
	}
}
$title = "RELATÓRIO DE ÍNDICE DE DEFEITO DE CAMPO";
$layout_menu = "gerencia";
include 'cabecalho.php';
?>
<style type='text/css'>
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.titulo_tabela{
	background-color:#596d9b;
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
.msg_sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    margin-botton:2px;
}
.btn_enviar_relatorio{
	margin-top: 25px;
	margin-bottom: 10px;
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
.btn_gerar_idc{
	margin-top: 20px;
	margin-bottom: 10px;
}
#familia{
	width: 250px;
}
#produto_referencia{
	width: 250px;
}
#mes_referente, #ano_referente{
	width: 80px;
}
.titulo_coluna{
	background-color: #596D9B;
	font: bold 11px "Arial";
	color: white;
	text-align: center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.subtitulo{
	text-align: center;
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
}
.result{
	margin-top: 14px;
}
</style>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script language='javascript' src='js/jquery.autocomplete.js'></script>
<link type="text/css" rel="stylesheet" href="js/jquery.autocomplete.css">
<script type="text/javascript">
window.meses;
window.qtdes;
window.idc;
window.meta;
window.familia;
$().ready(function(){
	//$('.produto_referencia').val("");
	var currentTime = new Date().getTime();
	function formatItem(row) {
		return row[0];
	}   
	$(".produto_referencia").autocomplete("<?echo $PHP_SELF.'?busca=produto_referencia&nocache='; ?>"+currentTime, {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});
	$(".produto_referencia").result(function(event, data, formatted) {
		$(".produto_codigo").val(data[1]) ;
	});	

	$("#btn_gerar_idc").click(function(){
		if (typeof $(this).attr("submeteu") == "undefined") {
			$(this).val("Aguarde, gerando relatório...");
			$(this).attr("submeteu", "sim");
		}
		else {
			alert("Aguarde, gerando relatório...")
			return false;
		}
	});
		

	$(".btn_gerar_grafico").click(function(){
		var familia = $(".result").html();
		window.meses = new Array();
		window.qtdes = new Array();
		window.idc = new Array();
		window.meta = new Array();
		var i = 0;

		$(".result").each(function(){
			window.meses[i] = $(this).parent().find(".mes").html();
			window.qtdes[i] = $(this).parent().find(".qtde_defeito").html();
			window.idc[i] = $(this).parent().find(".idc").html();
			window.meta[i] = $(this).parent().find(".meta").html();
			i++;

		});

	window.open('relatorio_indice_defeito_campo_popup.php',"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=1800,height=600,top=30,left=0" );
			
	});
	$('.familia').change(function(){
		$('.produto_referencia').val("");
		$('.produto_codigo').val("");
	});

	$('.produto_referencia').blur(function(){
		$('.familia').val("");
	});

	
});
</script>

<div class="texto_avulso" style="width:700px;">
	Esta tela possui duas modalidades de relatório: Anual e Mensal<br/>
	Para retirar o relatório Mensal de todas famílias informe o "Mês" e "Ano".<br/>
	Para retirar o relatório Anual, informe uma família ou produto e o mês e ano iniciais.<br/>
</div>
<br/>
<table width='700px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
<?php
	if($msg_erro!=""){
		echo "<tr class='msg_erro' ><td colspan='3'>{$msg_erro}</td></tr>";
	}
	if(strlen($msg_sucesso) > 0){
		echo "<tr class='msg_sucesso' ><td colspan='3'>{$msg_sucesso}</td></tr>";
	}
?>
	<tr>
		<td class='titulo_tabela' colspan="3">Parâmetros de Pesquisa</td>
	</tr>
	
	<tr class="subtitulo" >
	    <td colspan="4">
			Mensal das Famílias/Cálculo IDC
	    </td>
	</tr>
	
	<tr>
	<td width='210px'></td>
		<td>
		
		
		<form name='frm_relatorio' method='POST' action='<?php $PHP_SELF ?>' enctype='multipart/form-data'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>
<?php


				$form .="<tr><td width='150px'></td><td align='center'>";
					$form .="Mês Inicial<br />";
					$form .="<select name='mes_referente' id='mes_referente'  class='frm'>";
					for($num_mes = 1; $num_mes <= 12; $num_mes++ ){
						$form .= "<option value='$num_mes'";
						$mes_referente == $num_mes ? $form .="selected>{$meses[$num_mes]}</option>" : $form .=">{$meses[$num_mes]}</option>"; 
					}
					$form .="</select>";
				$form .="</td>";
		
				$form .="<td align='center'>";
					$form .="Ano Inicial<br />";
					$form .="<select  name='ano_referente' id='ano_referente'  class='frm'>";
					for($num_ano = date("Y"); $num_ano >= 2002; $num_ano-- ){
						$form .= "<option value='{$num_ano}'";
						$ano_referente == $num_ano ? $form .="selected>{$num_ano}</option>" : $form .=">{$num_ano}</option>"; 
					}
					$form .="</select>";
				$form .="</td><td width='150px'></td></tr>";
				$form .="</table>";
				
				echo $form;

?>
	</td>
	
	<td width='200px'></td>
	</tr>
	
	<tr>
		<td colspan='3' align='center'><input type='submit' name='btn_gerar_idc' id='btn_gerar_idc' class='btn_gerar_idc' value='Gerar IDC Mensal' /></td>
	</tr>
	</form>
	
	



	<tr class="subtitulo" >
	    <td colspan="4">
			Anual da Família / Cálculo IDC 
	    </td>
	</tr>
	
	
	
	<tr>
	
	
	
	
	<td width='150px'></td>
		<td>
		
		<form name='frm_relatorio' method='POST' action='<?php $PHP_SELF ?>' enctype='multipart/form-data'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>
<?php

				$form_mensal .="<tr><td>";
					$form_mensal .="Família<br />";
					$form_mensal .="<select  name='familia' id='familia'  class='familia frm familia_titulo'>";
					$form_mensal .="<option value=''>Selecione</option>";
					$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica={$login_fabrica}";
					$res = pg_query($con, $sql);
					while ($linha = pg_fetch_array($res)){
						$selected = $familia == $linha['familia'] ? "selected" : "";
						$form_mensal .="<option value='{$linha['familia']}' {$selected}>{$linha['descricao']}</option>";
					}
				$form_mensal .="</select></td>";
				
				$form_mensal .="<td align='center'><br/>&nbsp;OU&nbsp;</td>";
				
				$form_mensal .="<td>Produto<br/>
					<input type='text' name='produto_referencia' id='produto_referencia' class='produto_referencia frm' value='{$produto_referencia}'>
					<input type='hidden' name='produto_codigo' id='produto_codigo' class='produto_codigo frm' value='{$produto}'></td></tr>";

				$form_mensal .="<tr><table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td width='150px'></td><td align='center'>";
					$form_mensal .="Mês Inicial<br />";
					$form_mensal .="<select name='mes_referente' id='mes_referente'  class='frm'>";
					for($num_mes = 1; $num_mes <= 12; $num_mes++ ){
						$form_mensal .= "<option value='$num_mes'";
						$mes_referente == $num_mes ? $form_mensal .="selected>{$meses[$num_mes]}</option>" : $form_mensal .=">{$meses[$num_mes]}</option>"; 
					}
					$form_mensal .="</select>";
				$form_mensal .="</td>";
		
				$form_mensal .="<td align='center'>";
					$form_mensal .="Ano Inicial<br />";
					$form_mensal .="<select  name='ano_referente' id='ano_referente'  class='frm'>";
					for($num_ano = date("Y"); $num_ano >= 2002; $num_ano-- ){
						$form_mensal .= "<option value='{$num_ano}'";
						$ano_referente == $num_ano ? $form_mensal .="selected>{$num_ano}</option>" : $form_mensal .=">{$num_ano}</option>"; 
					}
					$form_mensal .="</select>";
				$form_mensal .="</td><td width='150px'></td></tr></table></tr>";
				//$form_mensal .="</table>";
				
				echo $form_mensal;
		
?>		
		
		</td>
	
	<td width='150px'></td>
	
	
	
	
	
	
	
	
	
	
	
	</tr>
	<tr>
		<td colspan='3' align='center'><input type='submit' name='btn_gerar_idc_mensal' id='btn_gerar_idc' class='btn_gerar_idc' value='Gerar IDC Anual' />
		<?php if(strlen($btn_grafico)>0){ echo "<input type='button' name='btn_gerar_grafico' id='btn_gerar_grafico' class='btn_gerar_grafico' value='Gerar gráfico' />";}?>
		</td>
	</tr>
	</form>
	</table>
	
	
	
	
	
	
	
<?php
				if(strlen($result_pesquisa)>0){
					$idc_acumulada = $idc_acumulada/$i;
					$meta_acumulada = round($meta_acumulada/$i, 2);
					$table .="<table align='center' width='700' cellspacing='1' class='tabela result'>
							<tr class='titulo_coluna'>
								<th>Indicador</th>
								<th>Volume Garantia</th>
								<th>Qtde Defeito</th>";
					if(strlen($_POST['produto_referencia'])>0 || strlen($_POST['familia'])>0){
						$table .= "	<th>Mês</th>
									<th>Ano</th>";
					}
					$table .= "	<th>IDC</th>
								<th>Meta</th>
								<th>Desvio</th>
							</tr>
								{$result_pesquisa}
								
							<tr>
							</table>
							<table align='center' width='700' cellspacing='1' class='tabela'>
							<tr class='titulo_tabela'>
								<th colspan='2'>IDC Acumulado</th>
								<th colspan='2'>Meta Acumulado</th>
							</tr>
							<tr>

								<td colspan='2'>{$idc_acumulada}</td>
								<td colspan='2'>{$meta_acumulada}</td>
							</tr>
							</table>";
				
				
					echo $table;
				}
				
				
				
				
?>

	
</table>
<?php
	include "rodape.php";
?>