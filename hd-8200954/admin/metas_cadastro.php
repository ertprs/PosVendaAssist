<?php
require_once "dbconfig.php";
require_once "includes/dbconnect-inc.php";
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
if(isset($_POST['btn_gravar_meta']) || isset($_POST['btn_pesquisar_meta'])){
	if($_POST['familia'] != "" && strlen($_POST['produto_referencia'])>0){
		$msg_erro = "Selecione Produto ou Família";
	}
	if(isset($_POST['btn_gravar_meta'])&& intval($_POST['mes_referente'])<1){
		$produto = $_POST['produto_codigo'];
		$msg_erro = "Data inválida";
	}
	else{
		
		if($_POST['familia'] == "" && strlen($_POST['produto_referencia']) == 0){
			$msg_erro = "Selecione Produto ou Família";
		}else{
			$produto = $_POST['produto_codigo'];
			$mes_referente = intval($_POST['mes_referente']);
			$ano_referente = intval($_POST['ano_referente']);
			
			if($_POST['mes_referente'] != ""){
				$condicao ="AND tbl_producao_defeito.mes_producao={$mes_referente}";
				$condicao = "AND mes_producao ={$mes_referente}"; 
			}
			
			if(isset($_POST['btn_gravar_meta']) && strlen($_POST['valor_meta'])==0){
				$msg_erro="Favor informar valor da meta";
			}else{
				$meta  = $_POST['valor_meta'];
				if(!strpos($meta,".")&&(strpos($meta,","))){
					$meta=substr_replace($meta, '.', strpos($meta, ","), 1);
				}
			}
		}
		
		if(strlen($msg_erro) == 0){
			

			if (isset($_POST['btn_pesquisar_meta'])){
								
				if(strlen($_POST['produto_referencia'])>0){
					if (isset($_POST['produto_codigo'])){
						if($_POST['mes_referente'] != ""){
							$condicao = "
								AND mes_producao ={$mes_referente}
								"; 
						}
						$produto_codigo = intval($_POST['produto_codigo']);
						$sql = "
							SELECT * FROM tbl_producao_defeito 
							WHERE fabrica = {$login_fabrica} 
							AND produto = {$produto_codigo} {$condicao} 
							AND ano_producao ={$ano_referente} 
							AND meta_produto IS NOT NULL
						";
						
						$res = pg_query($con, $sql);
						extract(pg_fetch_assoc($res));
						if(pg_num_rows($res)>0){
							$sql= "SELECT descricao AS descricao_produto from tbl_produto WHERE produto = {$produto}";
							extract(pg_fetch_assoc(pg_query($con, $sql)));
							$meta_produto = number_format($meta_produto, 2, '.', '.');
							$result_pesquisa = "
								<tr class='titulo_coluna'>
									<td>Produto</td>
									<td>Mês</td>
									<td>Ano</td>
									<td>Meta Produto</td>
									<td colspan='2'>Ações</td>
								</tr>
								<tr bgcolor='#F1F4FA'>
									<td>{$descricao_produto}</td>
									<td>{$meses[$mes_producao]}</td>
									<td>{$ano_producao}</td>
									<td>{$meta_produto}</td>
									<td><a href='?acao=alterar_meta_produto&produto={$produto}&mes={$mes_producao}&ano={$ano_producao}'>Alterar</a></td>
									<td><a href='?acao=excluir_meta_produto&produto={$produto}&mes={$mes_producao}&ano={$ano_producao}'>Excluir</a></td>
								</tr>
							";
						}else{
							$msg_erro ="Não foram encontrados registros para esta consulta";
						}
					}
					unset($_POST['produto_referencia']);
				}
				if(strlen($_POST['familia'])>0){
					if($_POST['mes_referente'] != ""){
						$condicao ="
							AND tbl_producao_defeito.mes_producao={$mes_referente}
							";
					}

					$familia = intval($_POST['familia']);
					$sql ="
					SELECT DISTINCT
					tbl_familia.familia,
					tbl_familia.descricao AS descricao_familia,
					tbl_producao_defeito.mes_producao,
					tbl_producao_defeito.ano_producao,
					tbl_producao_defeito.meta_familia
					
					FROM
					tbl_producao_defeito
					JOIN tbl_produto ON tbl_producao_defeito.produto=tbl_produto.produto
					JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia
					
					WHERE
					tbl_producao_defeito.fabrica={$login_fabrica}
					AND tbl_familia.familia ={$familia}
					AND tbl_producao_defeito.ano_producao={$ano_referente}
					{$condicao}
					AND tbl_producao_defeito.meta_familia IS NOT NULL
					";
					$res = pg_query($con, $sql);
					if(pg_num_rows($res)>0){
						$i =0;
						$result_pesquisa .= "
						<tr class='titulo_coluna'>
							<td>Família</td>
							<td>Mês</td>
							<td>Ano</td>
							<td>Meta Família</td>
							<td colspan='2'>Ações</td>
						</tr>";
						while($linha = pg_fetch_array($res)){
							extract($linha);
							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							$meta_familia = number_format($meta_familia, 2, '.', '.');
							$result_pesquisa .= "
							<tr bgcolor='{$cor}'>
								<td>{$descricao_familia}</td>
								<td>{$meses[$mes_producao]}</td>
								<td>{$ano_producao}</td>
								<td>{$meta_familia}</td>
								<td><a href='?acao=alterar_meta_familia&familia={$familia}&mes={$mes_producao}&ano={$ano_producao}&meta={$meta_familia}'>Alterar</a></td>
								<td><a href='?acao=excluir_meta_familia&familia={$familia}&mes={$mes_producao}&ano={$ano_producao}'>Excluir</a></td>
							<tr>";
							$i++;
						}
						unset($_POST['familia']);
					}else{
						$msg_erro ="Não foram encontrados registros para esta consulta";
					}

				}
				unset($_POST['btn_pesquisar_meta']);
			}	
			
			
			if (strlen($_POST['btn_gravar_meta'])>0){
				
				if(strlen($_POST['produto_referencia'])>0){
					if (strlen($_POST['produto_codigo'])>0){
						$produto = intval($_POST['produto_codigo']);
						$sql = "SELECT * from tbl_producao_defeito WHERE fabrica = {$login_fabrica} AND produto ={$produto} AND mes_producao ={$mes_referente} AND ano_producao ={$ano_referente}"; 
						$res = pg_query($con, $sql);
						if(pg_num_rows($res) > 0){
							$sql = "UPDATE tbl_producao_defeito SET meta_produto = {$meta} WHERE fabrica = {$login_fabrica} AND produto ={$produto} AND mes_producao ={$mes_referente} AND ano_producao ={$ano_referente}";
						}else{
							$sql = "INSERT INTO tbl_producao_defeito(fabrica, produto, qtde_producao, ano_producao, mes_producao, meta_produto) VALUES({$login_fabrica},{$produto}, 0, {$ano_referente},{$mes_referente},{$meta})";
						}
						$res = pg_query($con, $sql);
						if(pg_affected_rows($res)>0){
							$msg_sucesso = "Gravado com sucesso";
						}
					}else{
						$msg_erro = "O Produto Informado não Existe.";
					}
					unset($_POST['produto_referencia']);
				}
				
				
				if(strlen($_POST['familia'])>0){
					$sucesso =0;
					$familia = intval($_POST['familia']);
					
					//Consulta os produtos da familia
				 $sql = "
					SELECT 
					produto 
					
					FROM 
					tbl_produto 
					JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia 
					
					WHERE tbl_familia.fabrica={$login_fabrica} 
					AND tbl_familia.familia={$familia}
					";
					$res = pg_query($con, $sql);
					while($linha = pg_fetch_array($res)){
						extract($linha);
						$sql = "
						SELECT 
						produto
						
						FROM 
						tbl_producao_defeito 
						
						WHERE 
						fabrica = {$login_fabrica} 
						AND produto = {$produto} 
						LIMIT 1
						";
						
						$result = pg_query($con, $sql);
						extract(pg_fetch_assoc($result));
						if(pg_num_rows($result) > 0){
							
							$sql = "
								UPDATE 
								tbl_producao_defeito 
								SET meta_familia = {$meta} 
								WHERE fabrica = {$login_fabrica} 
								AND produto ={$produto} 
								AND mes_producao ={$mes_referente} 
								AND ano_producao ={$ano_referente}";
						}
						$result = pg_query($con, $sql);
						if(pg_affected_rows($result)>0){
							$sucesso++;
						}
						
					}
					if($sucesso == 0){
						$sql = "
							INSERT INTO
							tbl_producao_defeito(
							fabrica,
							produto,
							qtde_producao,
							mes_producao,
							ano_producao,
							meta_familia)
							
							VALUES(
							{$login_fabrica},
							{$produto},
							0,
							{$mes_referente},
							{$ano_referente},
							{$meta} )
							";
							
						$result = pg_query($con, $sql);
						if(pg_affected_rows($result)>0){
							$sucesso++;
						}
							
					}
					
					if($sucesso>0){
						$msg_sucesso = "Garavado com sucesso";
					}
					unset($_POST['familia']);
					unset($produto);
				}
				unset($_POST['btn_gravar_meta']);
				
			}

		
			
		}
			
	}
}
if(strlen($_GET['acao'])>0 && !isset($_POST['btn_gravar_meta']) && !isset($_POST['btn_pesquisar_meta'])){
	
	switch ($_GET['acao']){
		case "alterar_meta_familia":
			$familia = $_GET['familia'];
			$mes_referente = $_GET['mes'];
			$ano_referente = $_GET['ano'];
			$valor_meta = $_GET['meta'];
//			$sql="SELECT meta_familia from tbl_producao_defeito WHERE fabrica={$login_fabrica} AND mes_producao = {$mes_referente} AND ano_producao={$ano_referente}";
//			$result = pg_query($con, $sql);
//			$valor_meta = pg_result($result, 0, 0);
		break;
		
		case "excluir_meta_familia":
			$familia = $_GET['familia'];
			$mes_referente = $_GET['mes'];
			$ano_referente = $_GET['ano'];
			$sql = "
			SELECT produto FROM tbl_produto JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia 
			WHERE tbl_familia.fabrica={$login_fabrica} AND tbl_familia.familia={$familia}";
			$res = pg_query($con, $sql);
			while($linha = pg_fetch_array($res)){
				extract($linha);
				$sql = "SELECT produto FROM tbl_producao_defeito 
				WHERE fabrica = {$login_fabrica} AND produto = {$produto} LIMIT 1";
				$result = pg_query($con, $sql);
				extract(pg_fetch_assoc($result));
				if(pg_num_rows($result) > 0){
					$sql = "UPDATE tbl_producao_defeito SET meta_familia = NULL 
					WHERE fabrica = {$login_fabrica} AND produto ={$produto} AND mes_producao ={$mes_referente} 
					AND ano_producao ={$ano_referente}";
					$update = pg_query($con, $sql);
					if(pg_affected_rows($update)>0){
						$msg_sucesso = "Meta excluída com sucesso!";
					}
				}
			}
			unset($produto);
		break;
		
		case "alterar_meta_produto":
			$produto = $_GET['produto'];
			$mes_referente = $_GET['mes'];
			$ano_referente = $_GET['ano'];
			$sql="SELECT meta_produto from tbl_producao_defeito WHERE fabrica={$login_fabrica} AND produto = {$produto} AND mes_producao = {$mes_referente} AND ano_producao={$ano_referente}";
			$result = pg_query($con, $sql);
			$valor_meta = pg_result($result, 0, 0);
			$valor_meta = number_format($valor_meta, 2, '.', '.');
			$sql=" SELECT tbl_produto.produto,  tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto_referencia
			FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha 
			WHERE tbl_linha.fabrica = {$login_fabrica} 
			AND tbl_produto.produto={$produto}";
			extract(pg_fetch_assoc(pg_query($con, $sql)));
		break;
		
		case "excluir_meta_produto":
			$produto = $_GET['produto'];
			$mes_referente = $_GET['mes'];
			$ano_referente = $_GET['ano'];
			$sql="UPDATE tbl_producao_defeito SET meta_produto = NULL 
			WHERE fabrica = {$login_fabrica} AND produto ={$produto} 
			AND mes_producao ={$mes_referente} AND ano_producao ={$ano_referente}";
			pg_query($con, $sql);
			if(pg_affected_rows($update)>0){
				$msg_sucesso = "Meta excluída com sucesso!";
			}
		break;	
		
	}
}
$title = "CADASTRO DE METAS";
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
.btn_gravar_meta, .btn_pesquisar_meta{
	margin-top: 20px;
	margin-bottom: 10px;
}
#familia, #produto_referencia, #valor_meta{
	width: 100%;
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
.result{
	margin-top: 14px;
}
</style>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script language='javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.meio.mask.js"></script>
<link type="text/css" rel="stylesheet" href="js/jquery.autocomplete.css">
<script type="text/javascript">
$().ready(function(){
	$('.familia').change(function(){
		$('.produto_referencia').val("");
		$('.produto_codigo').val("");
	});

	$('.produto_referencia').blur(function(){
		$('.familia').val("");
	});
	$(".valor_meta").setMask({
		mask		: '99.999999999999999',
		type		: 'reverse',
		defaultValue: '000'
	});
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
});
</script>

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
		<td class='titulo_tabela' colspan="3">Cadastro e Pesquisa de Metas</td>
	</tr>
	<tr>
	<td width='160px'></td>
		<td>
		<form name='frm_relatorio' method='POST' action='metas_cadastro.php' enctype='multipart/form-data'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0' class='formulario'>
<?php


				$form .="<tr><td colspan='3'>";
					$form .="Família<br />";
					$form .="<select  name='familia' id='familia'  class='familia frm'>";
					$form .="<option value=''>Selecione</option>";
					$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica={$login_fabrica}";
					$res = pg_query($con, $sql);
					while ($linha = pg_fetch_array($res)){
						$selected = $familia == $linha['familia'] ? "selected" : "";
						$form .="<option value='{$linha['familia']}' {$selected}>{$linha['descricao']}</option>";
					}
				$form .="</select></td></tr>";
				
				$form .="<tr><td colspan='3'>Produto<br/>
					<input type='text' name='produto_referencia' id='produto_referencia' class='produto_referencia frm' value='{$produto_referencia}' size='45'>
					<input type='hidden' name='produto_codigo' id='produto_codigo' class='produto_codigo frm' value='{$produto}'></td></tr>";

				$form .="<tr><td>";
					$form .="Mês<br />";
					$form .="<select name='mes_referente' id='mes_referente'  class='frm'>";
					$form .="<option value=''>Selecione</option>";
					for($num_mes = 1; $num_mes <= 12; $num_mes++ ){
						$form .= "<option value='$num_mes'";
						$mes_referente == $num_mes ? $form .="selected>{$meses[$num_mes]}</option>" : $form .=">{$meses[$num_mes]}</option>"; 
					}
					$form .="</select>";
				$form .="</td>";
		
				$form .="<td>";
					$form .="Ano<br />";
					$form .="<select  name='ano_referente' id='ano_referente'  class='frm'>";
					for($num_ano = date("Y"); $num_ano >= 2002; $num_ano-- ){
						$form .= "<option value='{$num_ano}'";
						$ano_referente == $num_ano ? $form .="selected>{$num_ano}</option>" : $form .=">{$num_ano}</option>"; 
					}
					$form .="</select>";
				$form .="</td>";
				
				$form .="<td>Meta<br/><input type='text' name='valor_meta' id='valor_meta' class='valor_meta frm' value='{$valor_meta}' size='18'></td></tr>";
				
				$form .="</table>";
				
				echo $form;

?>
	</td>
	<td width='150px'></td>
	</tr>
	
	<tr>
		<td colspan='3' align='center'><input type='submit' name='btn_gravar_meta' id='btn_gravar_meta' class='btn_gravar_meta' value='Gravar' />
		<input type='submit' name='btn_pesquisar_meta' id='btn_pesquisar_meta' class='btn_pesquisar_meta' value='Pesquisar' /></td>
	</tr>
	</form>
<?php

			if(strlen($result_pesquisa)>0){
				echo $pesquisa .= "<table align='center' width='700' cellspacing='1' class='tabela result'>{$result_pesquisa}</table>";
			}
?>
	
</table>
<?php
	include "rodape.php";
?>