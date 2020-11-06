<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

$qtd_mes_ano = 12;
$ano_inicio_atividades = 2002;
$btn_acao = $_POST['btn_gerar_relatorio'];

if(isset($btn_acao)){
	$produto_linha = intval($_POST['produto_linha']);
	
	if ($produto_linha > 0) {
		$where_produto_linha = "AND tbl_produto.linha = {$produto_linha}"; 
	}
	
	$mes_inicial = intval($_POST['mes_inicial']);
	$ano_inicial = intval($_POST['ano_inicial']);
	$data_inicial = "01/".$mes_inicial."/"."$ano_inicial";
	//strlen($mes_inicial) == 1 ? $mes_inicial = "0".$mes_inicial : $mes_inicial;
	$mes_final   = intval($_POST['mes_final']);
	$ano_final   = intval($_POST['ano_final']);
	$data_final = "01/".$mes_final."/"."$ano_final";
	//strlen($mes_final) == 1 ? $mes_final = "0".$mes_final : $mes_final;
	
	//VALIDAÇÃO DATA
	if(empty($data_inicial) and empty($data_final)){
				$msg_erro = "Data Inválida";
			}

			if(!empty($data_inicial) and empty($data_final)){
				$msg_erro = "Data Inválida";
			}

			if(empty($data_inicial) and !empty($data_final)){
				$msg_erro = "Data Inválida";
			}
		if(strlen($msg_erro) == 0){
			if(!empty($data_inicial) and !empty($data_final)){
				if(strlen($msg_erro)==0){
					list($di, $mi, $yi) = explode("/", $data_inicial);
					if(!checkdate($mi,$di,$yi)) 
						$msg_erro = "Data Inválida";
				}
				
				if(strlen($msg_erro)==0){
					list($df, $mf, $yf) = explode("/", $data_final);
					if(!checkdate($mf,$df,$yf)) 
						$msg_erro = "Data Inválida";
				}

				if(strlen($msg_erro)==0){
					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final = "$yf-$mf-$df";
				}
				
				if(strlen($msg_erro)==0){
				    if (strtotime($aux_data_inicial.'+12 month') < strtotime($aux_data_final) ) {
				            $msg_erro = 'O intervalo entre as datas não pode ser maior que 12 mês';
				        }
				}
				
				if(strlen($msg_erro)==0){
			        if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
			        or strtotime($aux_data_final) > strtotime('today')){
			            $msg_erro = "Data Inválida.";
			        }
					
				}

			}
		}
	
	//FIM VALIDAÇÃO DATA
	
	
	$diferenca_ano = ($ano_final - $ano_inicial) * $qtd_mes_ano;
	$qtde_meses =  (($diferenca_mes + $diferenca_ano) - $mes_inicial)+ $mes_final +1;
}
if(isset($btn_acao) && strlen($msg_erro)==0){	
	
	$sql ="	SELECT
				tbl_defeito_constatado.defeito_constatado,
				tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
				TO_CHAR(tbl_os.data_fechamento, 'YYYY-MM') AS ano_mes,
				COUNT(tbl_os.os) AS qtde_os
	
			FROM

			tbl_os
			JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			
			WHERE
			tbl_os.fabrica={$login_fabrica}
			AND tbl_os.data_fechamento BETWEEN '{$ano_inicial}-{$mes_inicial}-01 00:00:00' AND '{$ano_final}-{$mes_final}-01 00:00:00'::timestamp + INTERVAL '1 MONTH' - INTERVAL '1 SECOND'
			AND tbl_os.posto <> 6359
			{$where_produto_linha}
			
			GROUP BY
			tbl_defeito_constatado.defeito_constatado,
			tbl_defeito_constatado.descricao,
			TO_CHAR(tbl_os.data_fechamento, 'YYYY-MM')
			
			ORDER BY
			
			tbl_defeito_constatado.descricao,
			TO_CHAR(tbl_os.data_fechamento, 'YYYY-MM')
			";
	
	$res = pg_query($con, $sql);	
	$dados = array();	
	while ($linha = pg_fetch_assoc($res)) {
		if (is_array($linha)) extract($linha);
		$dados[$defeito_constatado][$defeito_constatado_descricao][$ano_mes] = $qtde_os;
	}	
	if (empty($dados)){
		$msg_erro = "Não foram encontrados registros para essa pesquisa";
	}
	
	$meses = array();
	?>

	<?
	$resultado.= "<table align='center' cellspacing='1' class='tabela'>";
	
	$xlsthead = "<table align='center' cellspacing='1' class='tabela'>";
	
	$resultado.= "<tr class='titulo_coluna'>
						<td><input type=\"checkbox\" name=\"todas\" id=\"todas\"></td>
						<td align='left'>Defeito Constatado</td>";	
	$xlsthead .= "<tr class='titulo_coluna'>
					<td align='left'>Defeito Constatado</td>";	    
	$ano = $ano_inicial;
	$mudou_ano = false;
	$reducao_mes = 0;
	
	for ($i = 0; $i < $qtde_meses; $i++) {
		if($mudou_ano == false && $mes_inicial + $i > $qtd_mes_ano){
			$reducao_mes = 12;
			$ano++;
			$mudou_ano = true;
		}
		$mes = $mes_inicial + $i - $reducao_mes;
		$mes = str_pad($mes, 2, '0', STR_PAD_LEFT);
		
		$meses[$i] = "{$ano}-{$mes}";
		$resultado.= "<td>{$mes}/{$ano}</td>";
		$xlsthead .= "<td>{$mes}/{$ano}</td>";
		$select_option .="<option value='{$meses[$i]}'>{$mes}/{$ano}</option>";
	}
	
	$btn_gerar_grafico .="<select name='grafico_mes_ano' id='grafico_mes_ano' class='grafico_mes_ano'>";
	$btn_gerar_grafico .= $select_option;
	$btn_gerar_grafico .="</select>";
	$btn_gerar_grafico .="<input type='button' id='btn_gerar_grafico' class='btn_gerar_grafico' value='Gerar gráfico'>";
	
	$resultado.= "</tr>";
	$xlsthead .= "</tr>";
	$cont = 0;
	
	foreach($dados as $defeito_constatado => $defeito) {
		foreach ($defeito as $defeito_constatado_descricao => $qtdes_meses) {
			# code...
			$cor = ($cont % 2) ? "#F7F5F0" : "#F1F4FA";
			$resultado.= "	<tr bgcolor='$cor'>
								<td><input type='checkbox' name='' class='select_defeito' value='{$defeito_constatado_descricao}' ></td>
								<td align='left' class='defeito'>{$defeito_constatado_descricao}</td>";	
			$xlsthead .= "	<tr bgcolor='$cor'>
								<td align='left' class='defeito'>{$defeito_constatado_descricao}</td>";		
			foreach($meses as $i => $ano_mes) {
				$qtde = isset($qtdes_meses[$ano_mes]) ? $qtdes_meses[$ano_mes] : 0;
				$resultado.= "<td class='{$ano_mes}'>{$qtde}</td>";
				$xlsthead .= "<td class='{$ano_mes}'>{$qtde}</td>";
			}
			$resultado.= "</tr>";
			$xlsthead .= "</tr>";
			$cont++;
		}
	}
$resultado.= "</table>";
$xlsthead .= "</table>";
}

$title = "RELATÓRIO DE ÍNDICE DE OCORRÊNCIA MENSAL";

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

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.div_resultado_conteudo{
	border:1px solid #596D9B;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.btn_gerar_relatorio{
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
</style>
<!-- 1. Add these JavaScript inclusions in the head of your page -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<!-- 2. Add the JavaScript to initialize the chart on document ready -->
<script type="text/javascript">
window.defeitos;
window.qtdes;
$(document).ready(function(){
	$("#btn_gerar_relatorio").click(function(){
		if (typeof $(this).attr("submeteu") == "undefined") {
			$(this).val("Aguarde, gerando relatório...");
			$(this).attr("submeteu", "sim");
		}
		else {
			alert("Aguarde, gerando relatório...")
			return false;
		}
	});

	$("#todas").click(function(){
		if ($(this).is(":checked")){
			$('.select_defeito').attr('checked',true);
		}else{
			$('.select_defeito').attr('checked',false);
		}
	});	

	$(".btn_gerar_grafico").click(function(){
		var ano_mes = $("#grafico_mes_ano").val();
		window.defeitos = new Array();
		window.qtdes = new Array();
		var i = 0;
		var count = 0;
		$(".select_defeito").each(function(){
			
			if ($(this).is(":checked")){
				count += 1;
			}else{
				count = count;
			}

		});	

		if (count == 0){
			alert('Selecione algum defeito para gerar o relatório');
			return false;
		}	

		$(".select_defeito:checked").each(function(){
			
			var defeito = $(this).val();
			
			window.qtdes[i] = parseInt($(this).parent().parent().find('.'+ano_mes).html());
			
			window.defeitos[i] = defeito;
			i++;

		});

		window.open('indice_ocorrencia_mensal_popup.php',"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=1800,height=600,top=30,left=0" );
		
	})

});

</script>
<div class="texto_avulso" style="width:700px;">
	Este Relatório considera a Data de Fechamento das Ordens de Serviço.
</div>
<br/>
<table width='700px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
<?php
	if($msg_erro!=""){
		echo "<tr class='msg_erro' ><td colspan='5'>{$msg_erro}</td></tr>";
	}
?>
	<tr>
		<td class='titulo_tabela' colspan="5">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
	<td width='140px'></td>
		<td>
		<form name='frm_relatorio' method='POST' action="" align='center'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
				<tr>
<?php 
						$pesquisa .="<td>";
							$pesquisa .="Linha <br />";
							$pesquisa .="<select name='produto_linha' id='produto_linha'  class='frm'>";
							$pesquisa .="<option value=''>Selecione</option>";
							$sql ="SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica}";
							$res = pg_query($con, $sql);
							while($linha = pg_fetch_array($res)){
								$selected = $produto_linha == $linha['linha'] ? "selected" : "";
								$pesquisa .= "<option nome='{$linha['nome']}' value='{$linha['linha']}' {$selected}>{$linha['nome']}</option>"; 
							}
							$pesquisa .="</select>";
						$pesquisa .="</td>";
						
						$pesquisa .="<td>";
							$pesquisa .="Mês Inicial <br />";
							$pesquisa .="<select name='mes_inicial' id='mes_inicial'  class='frm'>";
							for($num_mes = 1; $num_mes <= $qtd_mes_ano; $num_mes++ ){
								strlen($num_mes) == 1 ? $num_mes = "0".$num_mes : $num_mes ;
								$pesquisa .= "<option value='$num_mes'";
								$mes_inicial == $num_mes ? $pesquisa .="selected>$num_mes</option>" : $pesquisa .=">$num_mes</option>"; 
							}
							$pesquisa .="</select>";
						$pesquisa .="</td>";

						$pesquisa .="<td>";
							$pesquisa .="Ano Inicial <br />";
							$pesquisa .="<select  name='ano_inicial' id='ano_inicial'  class='frm'>";
							for($num_ano = date("Y"); $num_ano >= $ano_inicio_atividades; $num_ano-- ){
								$pesquisa .= "<option value='{$num_ano}'";
								$selected = ($ano_inicial == $num_ano) ? 'selected' : '' ;
								$pesquisa .="$selected>{$num_ano}</option>"; 
							}
							$pesquisa .="</select>";
						$pesquisa .="</td>";
						
						$pesquisa .="<td>";
							$pesquisa .="Mês Final <br />";
							$pesquisa .="<select name='mes_final' id='mes_final'  class='frm'>";
							for($num_mes = 01; $num_mes <= $qtd_mes_ano; $num_mes++ ){
								strlen($num_mes) == 1 ? $num_mes = "0".$num_mes : $num_mes ;
								$pesquisa .= "<option value='{$num_mes}'";
								$mes_final == $num_mes ? $pesquisa .="selected>{$num_mes}</option>" : $pesquisa .=">{$num_mes}</option>"; 
							}
							$pesquisa .="</select>";
						$pesquisa .="</td>";
						
						$pesquisa .="<td>";
							$pesquisa .="Ano Final <br />";
							$pesquisa .="<select name='ano_final' id='ano_final''  class='frm'>";
							for($num_ano = date("Y"); $num_ano >= $ano_inicio_atividades; $num_ano-- ){
								$pesquisa .= "<option value='{$num_ano}'";
								$ano_final == $num_ano ? $pesquisa .="selected>{$num_ano}</option>" : $pesquisa .=">{$num_ano}</option>"; 
							}
							$pesquisa .="</select>";
						$pesquisa .="</td>";
						
						echo $pesquisa;

?>
				</tr>
			</table>

	<td width='100px'></td>
	</tr>
	
	<tr>
		<td colspan="5" align="center"><input type='submit' name='btn_gerar_relatorio' id='btn_gerar_relatorio' class='btn_gerar_relatorio' value='Gerar relatório' /></td>
	</tr>
	</form>
</table>
<?php
if(strlen($msg_erro) == 0){
	echo $btn_gerar_grafico;
	echo $resultado;
	$data = date("Y-m-d").".".date("H-i-s");
	$arquivo_nome     = "indice-ocorrencia-mensal-$data.$login_admin.xls";
	$path             = "xls/";
    $path_tmp         = "/tmp/";
    $arquivo_completo     = $path.$arquivo_nome;
    $arquivo_completo_tmp = $path_tmp.$arquivo_nome;
	$fp = fopen ($arquivo_completo_tmp,"w");
	fputs ($fp,$xlsthead);
	fclose ($fp);
	echo ` cp $arquivo_completo_tmp $path `;
	echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome' target='_blank'><img src='imagens/excel.gif'><br />Baixar Em XLS</a></p>";
    echo "<script language='javascript'>";
    echo "document.getElementById('id_download').style.display='block';";
    echo "</script>";
    echo "<br>";


	flush();
}
	include "rodape.php";
?>
