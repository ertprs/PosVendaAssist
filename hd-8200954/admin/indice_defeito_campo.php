<?php
require_once "dbconfig.php";
require_once "includes/dbconnect-inc.php";
require_once 'autentica_admin.php';
require_once dirname(__FILE__) . '/../rotinas/funcoes.php';

$vet['fabrica'] = '{$login_fabrica}';
$vet['tipo']    = 'importa_csv';
$vet['dest']    = 'helpdesk@telecontrol.com.br';
$vet['log']     = 2;			

if(isset($_POST['btn_enviar_relatorio'])){
		if(strlen($_FILES['file_csv']['name'])>0){
			$_UP['extensoes'] = array('csv');
			$extensao = strtolower(end(explode('.', $_FILES['file_csv']['name'])));
			if (array_search($extensao, $_UP['extensoes']) === false) {
				$msg_erro = "O arquivo deve possuir uma extensão do tipo CSV";
			}else{
				if ($_FILES["file_csv"]["error"] > 0){
					$_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
					$_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especificado no HTML';
					$_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
					$_UP['erros'][4] = 'Nenhum arquivo foi selecionado.';
				  	$msg_erro = $_UP['erros'][$_FILES['file_csv']['error']];
				}
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
			}
		}else{
			$msg_erro ="Selecione um Arquivo para Envio.";
		}
		if(strlen($msg_erro) == 0){

			if (($handle = fopen($_FILES['file_csv']['tmp_name'], "r")) !== FALSE) {
				
				$sql = "SELECT * FROM tbl_producao_defeito WHERE fabrica = {$login_fabrica} AND mes_producao = {$mes_referente} AND ano_producao = {$ano_referente} LIMIT 1";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res) > 0){
					$sql = "UPDATE tbl_producao_defeito SET qtde_producao=0, indice=NULL WHERE fabrica={$login_fabrica} AND mes_producao={$mes_referente} AND ano_producao={$ano_referente}";
					$result = pg_query($con, $sql);
				}
					
				$sql ="
				SELECT DISTINCT
				tbl_familia.familia,
				tbl_producao_defeito.meta_familia
				
				FROM
				tbl_producao_defeito
				JOIN tbl_produto ON tbl_producao_defeito.produto=tbl_produto.produto
				JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia
				
				WHERE
				tbl_producao_defeito.fabrica={$login_fabrica}
				AND tbl_producao_defeito.mes_producao={$mes_referente}
				AND tbl_producao_defeito.ano_producao={$ano_referente}
				";
				$res = pg_query($con, $sql);
				$metas = array();
				
				for($i = 0; $i < pg_num_rows($res); $i++) {
					extract(pg_fetch_assoc($res));
					
					$metas[$familia] = $meta_familia;
				}
				
				while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			    	$sql = "SELECT tbl_produto.produto, tbl_produto.familia FROM tbl_produto JOIN tbl_linha USING(linha) WHERE tbl_linha.fabrica={$login_fabrica} AND tbl_produto.referencia='{$data[0]}'";
			        $res = pg_query($con, $sql);
			        if(pg_num_rows($res) > 0){
			        	extract(pg_fetch_assoc($res));
			        	
			        	$meta_familia = isset($metas[$familia]) ? $metas[$familia] : "NULL";

			        	$sql = "SELECT * FROM tbl_producao_defeito WHERE fabrica={$login_fabrica} AND produto={$produto} AND mes_producao={$mes_referente} AND ano_producao={$ano_referente}";
			        	$res = pg_query($con, $sql);
			        	if (pg_num_rows($res) > 0) {
			        		$sql = "UPDATE tbl_producao_defeito SET qtde_producao={$data[1]} WHERE fabrica={$login_fabrica} AND produto={$produto} AND mes_producao={$mes_referente} AND ano_producao={$ano_referente}";
			        		$res = pg_query($con, $sql);
			        	}
			        	else {
			        		$sql ="INSERT INTO tbl_producao_defeito(fabrica, produto, qtde_producao, mes_producao, ano_producao, meta_familia) VALUES({$login_fabrica}, {$produto}, {$data[1]}, {$mes_referente}, {$ano_referente}, {$meta_familia})";
							$result = pg_query($con, $sql);
			        	}
					}else {
						$log_erro .="<tr><td>Referência {$data[0]} inexistente</td></tr>";
						Log::log2($vet, "Referência {$data[0]} inexistente");
					}
			        	
			    }
			}
			fclose($handle);
			
			unset($vet);
			
			require_once '../rotinas/esmaltec/calculo_idc.php';
			
			$msg_sucesso = "Gravado com Sucesso!";
		}
}

$title = "UPLOAD DO RELATÓRIO DE DEFEITO DE CAMPO";

$layout_menu = "cadastro";
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
.msg_sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
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
.titulo_coluna{
    background-color:#FF0000;
    font: bold 11px "Arial";
	color: white;
	text-align: center;
}
.upload_erro{
    background-color:#FF0000;
    font: bold 14px "Arial";
	color: white;
	text-align: center;
}
.msg_sucesso_parcial{
    background-color:#FFA500;
    font: bold 14px "Arial";
	color: white;
	text-align: center;

}
</style>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript">
$().ready(function(){
	$(".btn_enviar_relatorio").click(function(){
		if (typeof $(this).attr("submeteu") == "undefined") {
			$(this).val("Aguarde, carregando relatório...");
			$(this).attr("submeteu", "sim");
		}
		else {
			alert("Aguarde, carregando relatório...")
			return false;
		}
	});
});
</script>
<div class="texto_avulso" style="width:700px;">
	O Arquivo deve conter duas colunas separadas por "ponto e vírgula", ambas sem cabeçalho. A primeira deve conter a referência do produto e a segunda deve conter a quantidade
</div>
<br/>
<table width='700px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
<?php
	if($msg_erro!=""){
		echo "<tr class='msg_erro' ><td colspan='3'>{$msg_erro}</td></tr>";
	}
	
	if(strlen($msg_sucesso) > 0){
		if(strlen($log_erro) > 0){
			echo "<tr class='msg_sucesso_parcial' ><td colspan='3'>Alguns registros não foram gravados, verifique o Log de Erros</td></tr>";
		}else{
			echo "<tr class='msg_sucesso' ><td colspan='3'>{$msg_sucesso}</td></tr>";	
		}
	}
	
?>
	<tr>
		<td class='titulo_tabela' colspan="3">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
	<td width='140px'></td>
		<td>
		<form name='frm_relatorio' method='POST' action='<?php $PHP_SELF ?>' enctype='multipart/form-data'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
				<tr>
<?php

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

				$pesquisa .="<td>";
					$pesquisa .="Mês<br />";
					$pesquisa .="<select name='mes_referente' id='mes_referente'  class='frm'>";
					for($num_mes = 1; $num_mes <= 12; $num_mes++ ){
						$pesquisa .= "<option value='$num_mes'";
						$mes_referente == $num_mes ? $pesquisa .="selected>{$meses[$num_mes]}</option>" : $pesquisa .=">{$meses[$num_mes]}</option>"; 
					}
					$pesquisa .="</select>";
				$pesquisa .="</td>";
		
				$pesquisa .="<td>";
					$pesquisa .="Ano<br />";
					$pesquisa .="<select  name='ano_referente' id='ano_referente'  class='frm'>";
					for($num_ano = date("Y"); $num_ano >= 2002; $num_ano-- ){
						$pesquisa .= "<option value='{$num_ano}'";
						$ano_referente == $num_ano ? $pesquisa .="selected>{$num_ano}</option>" : $pesquisa .=">{$num_ano}</option>"; 
					}
					$pesquisa .="</select>";
				$pesquisa .="</td>";
				
				$pesquisa .="<td><br/><input type='file' name='file_csv' id='file_csv' class='file_csv' value=''></td>";
				
				echo $pesquisa;
?>
				</tr>
			</table>
	<td width='100px'></td>
	</tr>
	
	<tr>
		<td colspan='3' align='center'><input type='submit' name='btn_enviar_relatorio' id='btn_enviar_relatorio' class='btn_enviar_relatorio' value='Enviar' /></td>
	</tr>
	</form>
</table>
<?php
	if(strlen($log_erro) > 0){
		echo "
		<table align='center' width='700' cellspacing='1' class='tabela'>
			<tr class='upload_erro'><th>Log de Erros</th></tr>
				{$log_erro}
			</table>";
	}

	include "rodape.php";
?>
