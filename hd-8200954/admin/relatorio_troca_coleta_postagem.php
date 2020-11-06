<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$fabricas_geram_excel = array(51);


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

//HD 405156 - ATUALIZA COLETA_POSTAGEM AJAX
if ( isset($_GET['coleta_postagem']) ){
	
	
	$new_coleta_postagem = $_GET['coleta_postagem'];
	$sua_os = $_GET['os'];
	
	$res = pg_query($con,"BEGIN TRANSACTION");
	
	$sql_os = "select os from tbl_os where sua_os='$sua_os' and fabrica=$login_fabrica; ";
	
	
	$res_os = pg_query($con,$sql_os);
	
	$os_coleta_troca = pg_result($res_os,0,'os');
	
	$sql 		= "UPDATE tbl_os_troca set coleta_postagem='$new_coleta_postagem' where os=$os_coleta_troca;  ";
	$res 		= pg_query($con,$sql);
	$msg_erro 	= pg_errormessage($con);
	
	if (empty($msg_erro)){
		
		$res = pg_query($con,"COMMIT TRANSACTION");
		
		$sql_pega_coleta_postagem = "SELECT coleta_postagem FROM tbl_os_troca WHERE os=$os_coleta_troca; ";
		$res_pega_coleta_postagem = pg_query($con,$sql_pega_coleta_postagem);
		
		$coleta_postagem_atualizado =  pg_result($res_pega_coleta_postagem,0,0);
		
		echo "$coleta_postagem_atualizado";
	
	}else{

		$res = pg_query($con,"ROLLBACK TRANSACTION");
		
	}
	exit;
}

if (isset($_GET['coletado'])){

	$coletado = 't';
	$sua_os = $_GET['os'];
	
	$sql_os = "select os from tbl_os where sua_os='$sua_os' and fabrica=$login_fabrica; ";
	
	
	$res_os = pg_query($con,$sql_os);
	
	$os_coleta_troca = pg_result($res_os,0,'os');
	
	$res = pg_query($con,"BEGIN TRANSACTION");
	
	$sql 		= "UPDATE tbl_os_troca set coletado='$coletado' where os=$os_coleta_troca;  ";
	$res 		= pg_query($con,$sql);
	$msg_erro 	= pg_errormessage($con);
	
	
	if (empty($msg_erro)){
		
		$res = pg_query($con,"COMMIT TRANSACTION");
		
		echo "Produto Coletado";
		
	}else{

		$res = pg_query($con,"ROLLBACK TRANSACTION");
		
	}
	exit;
}

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
else                                   $data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)   $data_final = $_GET['data_final'];
else                                   $data_final = $_POST['data_final'];

if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];
else                                   $codigo_posto = $_POST['codigo_posto'];

if($_POST)
{
	if(empty($data_inicial) OR empty($data_final)){
		$msg_erro = "Data Inválida";
	}

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
		$aux_data_inicial = $yi."-".$mi."-".$di;
		$aux_data_final = "$yf-$mf-$df";
	}

	if(strlen($msg_erro)==0){
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
			$msg_erro = "Data Inválida";
		}
	}

	if(strlen($msg_erro)==0 and $login_fabrica <> 15){
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month')) {
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
		}
	 }
}

if(strlen($codigo_posto)>0){
	$sql = "SELECT posto 
			FROM tbl_posto_fabrica 
			WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	if(pg_num_rows($res)<1){
		$msg_erro = " Selecione o Posto! ";
	}else{
		$posto = pg_result($res,0,0);
		if(strlen($posto)==0){
			$msg_erro = " Selecione o Posto! ";
		}else{
			$cond_posto = " AND   tbl_os.posto   = $posto ";
		}
	}
}

$title = "RELATÓRIO DE OS'S TROCA DE PRODUTO";
include "cabecalho.php";

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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
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

<?php 
	include "javascript_pesquisas.php" ;
	include "javascript_calendario.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}

	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
	});

});

//HD 405156
function fnc_altera_coleta (linha) {
	
	$("#btn_alterar_postagem_"+linha).hide();
	$("#btn_salvar_postagem_"+linha).show();
	
	$("#lbl_coleta_postagem_"+linha).hide();
	$("#coleta_postagem_"+linha).show();
	
}

function fnc_salva_coleta(linha,sua_os){
	
	var valor_coleta_postagem_anterior = "";
	var valor_coleta_postagem_novo = "";
	
	valor_coleta_postagem_anterior = $("#lbl_coleta_postagem_"+linha).html();
	
	if ( $.trim($("#coleta_postagem_"+linha).val()).length > 0 ){
		
		valor_coleta_postagem_novo = $("#coleta_postagem_"+linha).val();
		
		//Veririfica se o valor antigo é o mesmo do valor atual, se não for, não processa nada e altera o campo e botão 
		if ( $.trim(valor_coleta_postagem_novo) == $.trim(valor_coleta_postagem_anterior) ){

			$("#btn_salvar_postagem_"+linha).hide();
			$("#btn_alterar_postagem_"+linha).show();
			$("#coleta_postagem_"+linha).hide();
			$("#lbl_coleta_postagem_"+linha).show();
			$("#imagem_"+linha).hide();
		}else{
		
			$.ajax({
				type: "GET",
				url: "<?=$PHP_SELF?>?"+'os='+ sua_os +"&coleta_postagem=" + valor_coleta_postagem_novo,
				/* data: 'os='+ sua_os +"&coleta_postagem=" + valor_coleta_postagem_novo , */
				beforeSend: function(){
					$("#btn_salvar_postagem_"+linha).hide();
					if (!$("#imagem_"+linha).length>0){
						$('#acao1_'+linha).append("<img src='js/loadingAnimation.gif' id='imagem_"+linha+"' style='width:165px;height:13px;padding:2px'>");
					}else{
						$("#imagem_"+linha).show();
					}
				} ,
				complete: function(http) {
					
					results = http.responseText;
					$("#imagem_"+linha).hide();
					$("#btn_alterar_postagem_"+linha).show();
					$("#lbl_coleta_postagem_"+linha).html(results);
					$("#lbl_coleta_postagem_"+linha).show();
					$("#coleta_postagem_"+linha).hide();
										
				}
			});
			
		}

	}else{
		alert ("Preencha algum valor no campo \"Nº Coleta/Postagem\" ")
	}
}

function fnc_coleta(linha,sua_os){

	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>?"+'os='+ sua_os +"&coletado=ok",
		/* data: 'os='+ sua_os +"&coleta_postagem=" + valor_coleta_postagem_novo , */
		beforeSend: function(){
			$("#btn_coleta_postagem_"+linha).hide();
			if (!$("#imagem2_"+linha).length>0){
				$('#acao2_'+linha).append("<img src='js/loadingAnimation.gif' id='imagem2_"+linha+"' style='width:95px;height:13px;padding:2px'>");
			}else{
				$("#imagem2_"+linha).show();
			}
		} ,
		complete: function(http) {
			
			results = http.responseText;
			$("#imagem2_"+linha).hide();
			$("#produto_coletado_"+linha).show();
			$("#tr_"+linha).css({backgroundColor:'#99FF99'});
		
		}
	});

}

</script>

<form name='frm_relatorio' method='post' action="<?=$PHP_SELF?>" align='center'>
	<table width='700px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
		<?php
			if($msg_erro!=""){ ?>
			<tr>
				<td align="center" class='msg_erro'><? echo $msg_erro; ?></td>
			</tr>
		<?php
			}
		?>
		<tr>
			<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
					<tr>
									<td width='130px'>&nbsp;</td>
									<td align="left" nowrap width='130px'>
										Data Inicial <br />
										<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"> 
									</td>
									<td nowrap colspan='2' align="left">
										Data Final <br />
										<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"> 
									</td>
								</tr>
					<tr>
						<td width='130px'>&nbsp;</td>
						<td  align='left'>
							Cod Posto <br />
							<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="10" value="<? echo $codigo_posto ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
						</td>	
						<td  align='left' nowrap>
							Razão Social <br />
							<input class="frm" type="text" name="posto_nome" id="posto_nome" size="40" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')"></A>
						</td>
					</tr>
				<?if (in_array($login_fabrica,$fabricas_geram_excel)){
					$checked = ($_POST['excel']=='sim')? 'CHECKED':null;
				?>
					<tr>
						<td width='130px'>&nbsp;</td>
						<td colspan='2' align='left'><input type='checkbox' <?=$checked?> value='sim' name='excel' id='excel'> 
							<label for="excel" style='cursor:pointer'>Gerar Excel</label>
						</td>
						
					</tr>
				<?}?>
					<tr align='center' width='100%'>
						<td colspan='5' align='center'>
							<input type='submit' name='btn_gravar' value='Pesquisar' />
							<input type='hidden' name='acao' value="<? echo $acao; ?>" />
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<?

if($btn_gravar=="Pesquisar" and strlen($msg_erro)==0){
	
	
	$sql = "SELECT   tbl_os.os                                                  ,
					tbl_os.sua_os                                               ,
					to_char(tbl_os.data_abertura, 'dd/mm/yyyy') AS data_abertura,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto.nome         AS nome_posto                        ,
					tbl_produto.referencia AS produto_referencia                ,
					tbl_produto.descricao  AS produto_descricao                 ,
					tbl_os_troca.coleta_postagem                                ,
					tbl_os_troca.coletado		                                ,
					to_char(tbl_os_troca.data_postagem, 'dd/mm/yyyy') AS data_postagem
			FROM tbl_os
			JOIN tbl_os_troca USING(os)
			JOIN tbl_posto    USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto       ON tbl_produto.produto     = tbl_os.produto  AND tbl_produto.fabrica_i = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os_troca.coleta_postagem IS NOT NULL
			AND   tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' 
			$cond_posto";
	#echo nl2br($sql); exit;
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		echo "<br>";
		
		
		#HD 405156 - inicio
		if ($login_fabrica==51){
			?>
			
			<table border='0' cellpadding='2' cellspacing='2' align='center' width='700px'>
				<tr>
					<td colspan='2' align='left'> Legenda: </td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<td align='left' style='width:15px;background-color:#99FF99' >&nbsp;</td>
					<td align='left'>&nbsp; Produto coletado </td>
				</tr>
			</table>
			<br />
			<?
		}
		#HD 405156 - fim
		
		
		#HD 405156 - inicio
		if (in_array($login_fabrica,$fabricas_geram_excel) && $_POST["excel"] == "sim" ){
			ob_start();
		}
		#HD 405156 - fim
		
		
		echo "<table border='0' cellpadding='1' cellspacing='1' align='center' class='tabela' width='750px'>";
		echo "<tr class='titulo_coluna'>";
			echo "<td>OS</td>";
			echo "<td>Data Abertura</td>";
			echo "<td>Posto</td>";
			echo "<td>Produto</td>";
			echo "<td>Nº Coleta/Postagem</td>";
			echo "<td>Data Solicitação</td>";
			if ($login_fabrica == 51){
				echo "<td colspan='2'>Ação</td>";
			}
		echo "</tr>";
		for($i=0; $i<pg_numrows($res); $i++){
			$os                 = pg_result($res,$i,os);
			$sua_os             = pg_result($res,$i,sua_os);
			$data_abertura      = pg_result($res,$i,data_abertura);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$nome_posto         = pg_result($res,$i,nome_posto);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$coleta_postagem    = pg_result($res,$i,coleta_postagem);
			$data_postagem      = pg_result($res,$i,data_postagem);
			$coletado           = pg_result($res,$i,coletado);
			
			#HD 405156 - inicio
			if ($coletado == 't'){
				$cor = '#99FF99';
			}else{
				$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';
			}
			#HD 405156 - fim
			
			echo "<tr style='background-color:$cor' id='tr_$i'>";
				echo "<td>";
					echo"<a href='os_press.php?os=$os' target='_blank'>";
						echo "$sua_os";
					echo"</a>";
				echo "</td>";
				
				echo "<td>$data_abertura</td>";
				echo "<td nowrap>$codigo_posto - $nome_posto</td>";
				echo "<td nowrap>$produto_referencia - $produto_descricao</td>";
				
				#HD 405156 - inicio
				if($login_fabrica==51){
					echo "<td align='center'>";
						echo "<label id='lbl_coleta_postagem_$i'>$coleta_postagem</label>";
						echo "<input type=\"text\" id='coleta_postagem_$i' name='coleta_postagem_$i' value='$coleta_postagem' style='display:none;width:100px' class='frm' maxlength='20' />";
					echo "</td>";
				}else{
					echo "<td align='center'>
						$coleta_postagem
					</td>";
				}
				#HD 405156 - fim
				
				echo "<td>$data_postagem</td>";
				
				#HD 405156 - inicio
				if ($login_fabrica==51){
					
					echo "<td id='acao1_$i'>
							<input type=\"button\"  id='btn_alterar_postagem_$i'  name='btn_alterar_postagem_$i' value=\" Alterar Coleta/Postagem \" style=\"cursor:pointer\" onclick=\"fnc_altera_coleta($i)\"/>
							<input type=\"button\"  id='btn_salvar_postagem_$i' name='btn_salvar_postagem_$i' value=\"Salvar Coleta/Postagem \" style=\"display:none;cursor:pointer\" onclick=\"fnc_salva_coleta($i,$sua_os)\" />
					</td>";
					
					echo "<td id='acao2_$i' nowrap>";
						if ($coletado == 't'){
							echo "<label id=\"produto_coletado_$i\">Produto Coletado</label>";
						}else{
							echo "<label id=\"produto_coletado_$i\" style=\"display:none\">Produto Coletado</label>";
							echo "	<input type=\"button\"  id='btn_coleta_postagem_$i' name='btn_coleta_postagem_$i' value=\"Coletado\" style=\"cursor:pointer\" onclick=\"fnc_coleta($i,$sua_os)\" />";
						}
					echo "</td>";
					
				}
				#HD 405156 - fim
				
			echo "</tr>";
		}
		echo "</table>";
		
		
		#HD 405156 - inicio - geração de excel
		if (in_array($login_fabrica,$fabricas_geram_excel) && $_POST["excel"] == "sim"  ){
			
			
			ob_start();
			
			echo "<table border='1' cellpadding='1' cellspacing='1' align='center' width='750px'>";
				echo "<tr>";
					echo "<td style='background-color:#596d9b;font: bold 14px \"Arial\";color:#FFFFFF;'>OS</td>";
					echo "<td style='background-color:#596d9b;font: bold 14px \"Arial\";color:#FFFFFF;'>Data Abertura</td>";
					echo "<td style='background-color:#596d9b;font: bold 14px \"Arial\";color:#FFFFFF;'>Posto</td>";
					echo "<td style='background-color:#596d9b;font: bold 14px \"Arial\";color:#FFFFFF;'>Produto</td>";
					echo "<td style='background-color:#596d9b;font: bold 14px \"Arial\";color:#FFFFFF;'>Nº Coleta/Postagem</td>";
					echo "<td style='background-color:#596d9b;font: bold 14px \"Arial\";color:#FFFFFF;'>Data Solicitação</td>";
				echo "</tr>";
				
				for($i=0; $i<pg_numrows($res); $i++){
					$os                 = pg_result($res,$i,os);
					$sua_os             = pg_result($res,$i,sua_os);
					$data_abertura      = pg_result($res,$i,data_abertura);
					$codigo_posto       = pg_result($res,$i,codigo_posto);
					$nome_posto         = pg_result($res,$i,nome_posto);
					$produto_referencia = pg_result($res,$i,produto_referencia);
					$produto_descricao  = pg_result($res,$i,produto_descricao);
					$coleta_postagem    = pg_result($res,$i,coleta_postagem);
					$data_postagem      = pg_result($res,$i,data_postagem);
					$coletado           = pg_result($res,$i,coletado);
					
					#HD 405156 - inicio
					if ($coletado == 't'){
						$cor = '#99FF99';
					}else{
						$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';
					}
					#HD 405156 - fim
					
					echo "<tr>";
						
						echo "<td style='background-color:$cor'>";
							echo "$sua_os";
						echo "</td style='background-color:$cor'>";
						echo "<td style='background-color:$cor'>$data_abertura</td>";
						echo "<td nowrap style='background-color:$cor'>$codigo_posto - $nome_posto</td>";
						echo "<td nowrap style='background-color:$cor'>$produto_referencia - $produto_descricao</td>";
						echo "<td align='center' style='background-color:$cor'>$coleta_postagem &nbsp;</td>";
						echo "<td style='background-color:$cor'>$data_postagem</td>";
						
					echo "</tr>";
				}
			
			echo "</table>";
			//Redireciona a saida da tela, que estava em buffer, para a variÃ¡vel
			$hora = time();
			$xls = "xls/relatorio_troca_coleta_postagem_".$hora.".xls";
			
			$saida = ob_get_clean();
			$arquivo = fopen($xls, "w");
			fwrite($arquivo, $saida);
			fclose($arquivo);
		
			echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
					echo "<td align='center'><input type='button' value='Download em Excel' onclick=\"window.location='$xls'\"></td>";
				echo "</tr>";
			echo "</table>";
				
		}
		#HD 405156 - fim
		
	}else{
		echo "<p>Nenhum resultado encontrado!</p>";
	}
}

?>

<? include "rodape.php" ?>