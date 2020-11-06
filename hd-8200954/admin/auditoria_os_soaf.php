<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao = trim($_POST['btn_acao']);

unset($msg_erro);
$msg_erro = array();

$styles = ' <style type="text/css">
		
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
		
		.sucesso{
			background-color:#008000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_tabela{
			background-color:#596d9b;
			font: bold 14px "Arial" !important;
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
		
	</style>

';

$ajax = (isset($_GET['ajax'])) ? $_GET['ajax'] : null ;
$os = (isset($_GET['os'])) ? $_GET['os'] : null;
if ($ajax == true and $os){
	echo $styles;
	
	$sql = "SELECT 
			tbl_tipo_soaf.descricao,
			tbl_tipo_soaf.tipo_soaf,
			tbl_soaf.soaf,
			TO_CHAR(tbl_soaf.data_abertura, 'DD/MM/YYYY') as data_abertura,
			tbl_soaf.status
			
			FROM tbl_os 

			JOIN tbl_os_produto using (os)
			JOIN tbl_os_item    using (os_produto)
			JOIN tbl_soaf       on tbl_os_item.soaf = tbl_soaf.soaf  
			JOIN tbl_tipo_soaf  on tbl_soaf.tipo_soaf = tbl_tipo_soaf.tipo_soaf 

			WHERE tbl_os.os=$os 
			AND tbl_os.fabrica=$login_fabrica 
			AND tbl_soaf.data_abertura is not null

			order by data_abertura
			
	";
	$res = pg_query($con,$sql);
	
	echo "<table class='tabela' width='100%' align='center'>"; 
		echo "<tr class='titulo_coluna'>";
			echo "<td>Tipo de SOAF</td>";
			echo "<td>Data de Abertura SOAF</td>";
			echo "<td>Status</td>";
			echo "<td>Ações</td>";
		echo "</tr>";
	for ($i = 0; $i < pg_numrows($res); $i++){
	
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$tipo_soaf_descricao = pg_result($res,$i,'descricao');
		$tipo_soaf 			 = pg_result($res,$i,'tipo_soaf');
		$soaf 			     = pg_result($res,$i,'soaf');
		$soaf_data_abertura  = pg_result($res,$i,'data_abertura');
		$status 		 	 = pg_result($res,$i,'status');
		if ($status == 'Aprovado' or $status=='Reprovado'){
			$display = "display:none";
		}
		
		echo "<tr bgcolor='$cor'>";
			echo "<td>";
				echo $tipo_soaf_descricao;
			echo"</td>";
			
			echo "<td>";
				echo $soaf_data_abertura;
			echo"</td>";
			
			echo "<td id='label_status_$soaf'>";
				echo $status;
			echo"</td>";
			
			echo "<td>";
				echo "<input type='button' id='editar_$soaf' name='editar_$soaf' value='Editar' onclick='mostraFormSoaf($soaf,$os)' style='cursor:pointer;font:11px Arial'   />";
				echo "<input type='button' id='aprovar_$soaf' name='aprovar_$soaf' value='Aprovar' onclick='aprovaSoaf($soaf)' style='cursor:pointer;font:11px Arial;$display'  />";
				echo "<input type='button' id='reprovar_$soaf' name='reprovar_$soaf' value='Reprovar' onclick='reprovaSoaf($soaf)' style='cursor:pointer;font:11px Arial; $display '/>";
				echo "<input type='button' id='email_$soaf' name='email_$soaf'value='Enviar e-Mail' onclick='mostraFormEmail($soaf,$os)' style='cursor:pointer;font:11px Arial' />";
			echo"</td>";
		echo"</tr>";
		
	}
	
	echo "</table>";
	exit;
}

$aprovar = (isset($_GET['aprova'])) ? $_GET['aprova'] : null ;
if ($aprovar){
	$soaf_aprovar = $_GET['soaf'];
	$res = pg_query($con,'BEGIN TRANSACTION');
	$sql_os = "
		UPDATE tbl_soaf 
		SET 
			status = 'Aprovado',
			data_aprovacao = current_date
			
		WHERE soaf = $soaf_aprovar 
	";
	$res_os   = pg_query($con,$sql_os);
	$msg_erro = pg_errormessage($con);
	
	if ($msg_erro){
		echo "erro";
		$res = pg_query($con,'ROLLBACK TRANSACTION');
	}else{
		echo "ok;Aprovado";
		$res = pg_query($con,'COMMIT TRANSACTION');
	}
	exit;
}

$reprovar = (isset($_GET['reprova'])) ? $_GET['reprova'] : null ;
if ($reprovar){
	$soaf_aprovar = $_GET['soaf'];
	$res = pg_query($con,'BEGIN TRANSACTION');
	$sql_os = "
		UPDATE tbl_soaf 
		SET 
			status = 'Reprovado',
			data_reprovacao = current_date
			
		WHERE soaf = $soaf_aprovar 
	";
	$res_os   = pg_query($con,$sql_os);
	$msg_erro = pg_errormessage($con);
	
	if ($msg_erro){
		echo "erro";
		$res = pg_query($con,'ROLLBACK TRANSACTION');
	}else{
		echo "ok;Reprovado";
		$res = pg_query($con,'COMMIT TRANSACTION');
	}
	exit;
}


if ($btn_acao == 'pesquisar'){
	
	//VALIDAÇÃO DE DATA INICIO
	$data_inicial = $_POST["filtro_data_inicial"];
	$data_final = $_POST["filtro_data_final"];
	
	if (!$data_inicial or !$data_final){
		$msg_erro[] = 'Informe a data';
	}
	
	if ($data_inicial and $data_final){
	
		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) 
				$msg_erro[] = "Data Inicial Inválida";
			
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)) 
				$msg_erro[] = 'Data Final Inválida';
			
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";
			
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){ 
				$msg_erro[] = 'Intervalo de Data Inválido';
			}

			if (strtotime($aux_data_final) > strtotime('today')){
				$msg_erro[] = 'Intervalo de Data Inválido';
			}
			
		}
		
		if (empty($msg_erro) and $aux_data_inicial and $aux_data_final){
			$cond_data = " 
				AND tbl_soaf.data_abertura between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' 
			";
		}
		
	}
	//VALIDAÇÃO DE DATA FIM
	
	//RECEBE DADOS DO POST 
	$posto_id 			= trim($_POST['posto_id']);
	$filtro_posto_ref 	= trim($_POST['filtro_posto_ref']);
	$filtro_posto_desc	= trim($_POST['filtro_posto_desc']);
	$filtro_os 			= trim($_POST['filtro_os']);
	$filtro_status 		= trim($_POST['filtro_status']);
	$filtro_tipo_soaf 	= trim($_POST['filtro_tipo_soaf']);
	
	$cond_os 	 	= (strlen($filtro_os)>0) ? " AND (tbl_os.sua_os = '$filtro_os' or tbl_os.os = $filtro_os) " : "" ;
	$cond_posto  	= (strlen($posto_id)>0 and strlen($filtro_posto_ref)>0) ? " AND tbl_os.posto = $posto_id " : "" ;
	$cond_status 	= (strlen($filtro_status)>0) ? " AND tbl_soaf.status = '$filtro_status' " : "" ;
	$cond_tipo_soaf = (strlen($filtro_tipo_soaf)>0) ? " AND tbl_soaf.tipo_soaf = '$filtro_tipo_soaf' " : "" ;
	if (!$posto_id){
		$msg_erro[] = 'Informe um posto para a pesquisa';
	}
	
}

$layout_menu = "auditoria";
$title = "Auditoria de SOAF";
include "cabecalho.php";

?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="ISO 8859-1">
	<?
	echo $styles;
	?>
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
	<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
	<?php include "javascript_calendario.php";?>
	<script type="text/javascript">
		
		$().ready(function(){
			$( "#filtro_data_inicial" ).datePicker({startDate : "01/01/2000"});
			$( "#filtro_data_inicial" ).maskedinput("99/99/9999");
			
			$( "#filtro_data_final" ).datePicker({startDate : "01/01/2000"});
			$( "#filtro_data_final" ).maskedinput("99/99/9999");
			
			Shadowbox.init();
		});
		
		function gravaDados(name, valor){
			try{
				$("input[name="+name+"]").val(valor);
			} catch(err){
				return false;
			}
		}
		
		function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,nome,credenciamento){
			gravaDados('filtro_posto_ref',codigo_posto);
			gravaDados('filtro_posto_desc',nome);
			gravaDados('posto_id',posto);
		}
		
		function pesquisaPosto(campo,tipo){
			var campo = campo.value;

			if (jQuery.trim(campo).length > 2){
				Shadowbox.open({
					content:    "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
					player: "iframe",
					title:      "Pesquisa Posto",
					width:  800,
					height: 500
				});
			}else
				alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}
		
		function mostraFormSoaf(soaf,os){

				Shadowbox.open({
					content:    "auditoria_os_soaf_formulario.php?soaf="+soaf+"&os="+os,
					player: "iframe",
					title:      "Formulário de SOAF",
					width:  900,
					height: 500
				});
			
		}
		
		function mostraFormEmail(soaf,os){

				Shadowbox.open({
					content:    "auditoria_os_soaf_email.php?soaf="+soaf+"&os="+os,
					player: "iframe",
					title:      "Formulário de SOAF - Envio de e-Mail",
					width:  900,
					height: 500
				});
			
		}
		
		function aprovaSoaf(soaf){
			if (confirm('Deseja realmente aprovar este SOAF')== true){
				
				var curDateTime = new Date();
				$.ajax({
					type: "GET",
					url: "<?=$PHP_SELF?>",
					data: 'ajax=true&aprova=sim&soaf='+soaf,
					error: function (){
						alert('Erro ao aprovar soaf, tente novamente');
					},
					complete: function(http) {
						results = http.responseText.split(";");
						if (results[0] != 'erro'){
							alert('O SOAF aprovada com sucesso');
							$('#aprovar_'+soaf).hide();
							$('#reprovar_'+soaf).hide();
							$('#label_status_'+soaf).html(results[1]);
						}
					}
				});
				
			}
		}
		
		function reprovaSoaf(soaf){
			if (confirm('Deseja realmente reprovar este SOAF')== true){
				
				var curDateTime = new Date();
				$.ajax({
					type: "GET",
					url: "<?=$PHP_SELF?>",
					data: 'ajax=true&reprova=sim&soaf='+soaf,
					error: function (){
						alert('Erro ao reprovar soaf, tente novamente');
					},
					complete: function(http) {
						results = http.responseText.split(";");
						if (results[0] != 'erro'){
							alert('O SOAF reprovada com sucesso');
							$('#aprovar_'+soaf).hide();
							$('#reprovar_'+soaf).hide();
							$('#label_status_'+soaf).html(results[1]);
						}
					}
				});
				
			}
		}
		
		function mostraSOAF(os){
			if (document.getElementById('dados_' + os)){
				var style2 = document.getElementById('dados_' + os); 
				
				if (style2==false) return; 
				if (style2.style.display=="block"){
					$('#dados_'+os).slideUp("slow");
					$('#linha_'+os).attr('colspan','100%');
					$('#linha_'+os).hide();
				}else{
					$('#linha_'+os).show();
					$('#dados_'+os).slideDown("slow");
					style2.style.display = "block";
					if ($('#dados_'+os).attr('rel')!='1'){
						retornaSoaf(os);
					}
					$('#dados_'+os).attr('rel','1');
				}
			}
		}

		function retornaSoaf(os){

			var curDateTime = new Date();
			$.ajax({
				type: "GET",
				url: "<?=$PHP_SELF?>",
				data: 'ajax=true&os='+ os ,
				beforeSend: function(){
					$('#dados_'+os).html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
				},
				error: function (){
					$('#dados_'+os).html("erro");
				},
				complete: function(http) {
					results = http.responseText;
					$('#dados_'+os).html(results).addClass('z-index','2');
				}
			});
		}
		
	</script>
	
</head>
<body>
	
	<?php if ($msg_erro){ 
			$msg_erro =  implode ("<br>",array_filter($msg_erro));
	?>
		<table class="msg_erro" width="700px" align="center" cellpadding="2" border="0">
			<tr>
				<td><?php echo $msg_erro?></td>
			</tr>
		</table>
	<?php }?>
	
	<form name="frm_pesquisa" method="post" action="<?php echo $PHP_SELF?>">
		<table align="center" class='formulario' width="700px" cellpadding='0' cellspacing='0' border='0'>
			<tr>
				<td class="titulo_tabela">Parâmetros de Pesquisa</td>
			</tr>
			<tr>
			
				<td>
					
					<!-- HIDDEN BUTTONS - INICIO -->
					<input type="hidden" name="posto_id" value="<?php echo $posto_id?>" />
					<input type="hidden" name="btn_acao" value="" />
					<!-- HIDDEN BUTTONS - FIM	 -->
					
					<!-- TABELA DE FORMULÁRIO DE PESQUISA - INICIO -->
					<table width="600px" align="center" border='0' class='formulario' cellpadding='5' cellspacing='0'>
						
						<tr>
							<td colspan="2">
								&nbsp;
							</td>
						</tr>
						
						<tr>
							<td width="30%">Data Inícial</td>
							<td>Data Final</td>
						</tr>
						
						<tr>
							<td>
								<input type="text" name="filtro_data_inicial" id="filtro_data_inicial" class='frm' style="width:121px" value="<?php echo $data_inicial?>" />
							</td>
							<td>
								<input type="text" name="filtro_data_final" id="filtro_data_final" class='frm' style="width:121px" value="<?php echo $data_final?>" />
							</td>
						</tr>
						
						<tr>
							<td colspan="2">Os</td>
						</tr>
						
						<tr>
							<td>
								<input type="text" name="filtro_os" id="filtro_os" class='frm' value="<?php echo $filtro_os?>" />
							</td>
							<td>&nbsp;</td>
						</tr>
						
						
						<tr>
							<td>Posto Referência</td>
							<td>Posto Descrição</td>
						</tr>
						
						<tr>
							<td>
								<input type="text" name="filtro_posto_ref" id="filtro_posto_ref" class='frm' value="<?php echo $filtro_posto_ref?>" />
								<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle"  onclick=" pesquisaPosto (document.frm_pesquisa.filtro_posto_ref, 'codigo');">
							</td>
							<td>
								<input type="text" name="filtro_posto_desc" id="filtro_posto_desc" class='frm' style="width:341px" value="<?php echo $filtro_posto_desc?>" />
								<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle"  onclick=" pesquisaPosto (document.frm_pesquisa.filtro_posto_desc, 'nome');">
							</td>
						</tr>
						
						<tr>
							<td>Status SOAF</td>
							<td>Tipo de SOAF</td>
						</tr>
						<tr>
						
							<td>
								<select class='frm' name="filtro_status" id="filtro_status" style="width:141px">
									
									<option value="">Todos</option>
									
									<option value="Em Aprovação" <?php echo ($filtro_status == "Em Aprovação") ? "CHECKED" : ""; ?> >
										Em Aprovação
									</option>
									
									<option value="Aprovado" <?php echo ($filtro_status == "Aprovado") ? "CHECKED" : ""; ?> >
										Aprovados
									</option>
									
									<option value="Reprovado" <?php echo ($filtro_status == "Reprovado") ? "CHECKED" : ""; ?> >
										Reprovados
									</option>
								</select>
							</td>
							
							<td>
								<select class='frm' name="filtro_tipo_soaf" id="filtro_tipo_soaf" style="width:141px">
									
									<option value="">Todos</option>
									<?
									$sql_i = "select * from tbl_tipo_soaf where fabrica=$login_fabrica and ativo is true order by descricao ";
									$res_i = pg_query($con,$sql_i);
									if (pg_num_rows($res_i)>0){
										for ($i=0; $i < pg_num_rows($res_i); $i++){
											$tipo_soaf = pg_result($res_i,$i,'tipo_soaf');
											$tipo_soaf_descricao = pg_result($res_i,$i,'descricao');
											
											$selected = ($tipo_soaf == $filtro_tipo_soaf) ? "SELECTED" : "";
											?>
											<option value="<?=$tipo_soaf?>" <?=$selected?>> <?=$tipo_soaf_descricao?> </option>
											<?
										}
									}
									
									?>
									
								</select>
							</td>
							
						</tr>
						
						<tr>
							<td>&nbsp;</td>
						</tr>
						
						<tr>
							<td colspan="2" align="center">
								<input type="button" value="Pesquisar" name="btn_pesquisa" onclick="if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde') }" />
							</td>
						</tr>
						
					</table>
					<!-- TABELA DE FORMULÁRIO DE PESQUISA - FIM -->
					
				</td>
			
			</tr>
			
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
	</form>

	</body>
</html>

<br />

<?php 

if ($btn_acao == "pesquisar" and empty($msg_erro)){
	
	$sql = "
		SELECT 
			
			distinct(tbl_os.os) as os,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura
			
		FROM tbl_os 
		
		JOIN tbl_os_produto on (tbl_os.os = tbl_os_produto.os)
		JOIN tbl_os_item    on (tbl_os_produto.os_produto = tbl_os_item.os_produto) 
		JOIN tbl_soaf       on (tbl_os_item.soaf = tbl_soaf.soaf) 
		
		WHERE tbl_os.fabrica = $login_fabrica 
		AND tbl_os_item.soaf is not null 
		AND tbl_soaf.data_abertura is not null 
		AND tbl_os_item.pedido is null 
		$cond_os 
		$cond_data 
		$cond_posto 
		$cond_status 
		$cond_tipo_soaf 
		
		
		
	";
	
	$res = pg_query($con,$sql);
	
	if (pg_num_rows($res)>0){
	?>
		<table class="tabela" width="700px" align="center" cellpadding="0" cellspacing="2">
			
			<tr >
				<td colspan="5" class="titulo_tabela">Resultado da Pesquisa</td>
			</tr>
			<tr>
				<th class="titulo_coluna">OS</th>
				<th class="titulo_coluna">Data Abertura OS</th>
			</tr>
			<?php
			for ($i = 0;$i < pg_num_rows($res); $i++){
				
				$os		 		= pg_result($res,$i,'os');
				$data_abertura	= pg_result($res,$i,'data_abertura');
			?>
				<tr>
					<td><a href="javascript:mostraSOAF(<?=$os?>);" "><?echo $os?></a></td>
					<td><?echo $data_abertura?></td>
				</tr>
				<?php
					echo "<tr>";
						echo "<td colspan='100%' id='linha_$os' rel='' style='display:none;'>";
							echo "<div id='dados_$os' style='display:none;border: 1px solid #949494;'></div>";
						echo "</td>";
					echo "</tr>";
				
			}
			
			?>
		</table>
	<?
	}else{
	?>
		<table class="msg_erro" width="700px" align="center">
			<tr>
				<td>Nenhum Resultado Encontrado</td>
			</tr>
		</table>
	<?
	}
	
}

include "rodape.php";
?>
