<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$ajax = $_GET['ajax'];
unset($msg_erro);

//SQL PARA VERIFICAR A DESCRICAO DO TIPO DE SOAF CADASTRADO PARA O FORMULARIO
//INICIO {
if ($_GET['soaf'] && $_GET['ok']){
	$soaf = $_GET['soaf'];
	$sql = "
		SELECT tbl_tipo_soaf.descricao
		from tbl_soaf
		JOIN tbl_tipo_soaf on tbl_soaf.tipo_soaf = tbl_tipo_soaf.tipo_soaf
		where soaf = $soaf
	";
	$res = pg_query($con,$sql);

	$tipo_soaf_descricao = (pg_num_rows($res)>0) ? strtoupper(trim(pg_result($res,0,'descricao'))) : "";
}
	//FIM }

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

if ($ajax == true){
	echo $styles;
	$os = $_GET['os'];
	
	$sql = "SELECT count(tbl_os_item.soaf) as qtde_soaf,
			tbl_tipo_soaf.descricao,
			tbl_tipo_soaf.tipo_soaf,
			tbl_soaf.soaf
			

			FROM tbl_os 

			JOIN tbl_os_produto using (os)
			JOIN tbl_os_item    using (os_produto)
			JOIN tbl_soaf       on tbl_os_item.soaf = tbl_soaf.soaf  
			JOIN tbl_tipo_soaf  on tbl_soaf.tipo_soaf = tbl_tipo_soaf.tipo_soaf 

			WHERE tbl_os.os=$os AND tbl_soaf.data_abertura is null

			GROUP BY tbl_tipo_soaf.descricao,tbl_tipo_soaf.tipo_soaf,tbl_soaf.soaf

			order by qtde_soaf
			
	";
	$res = pg_query($con,$sql);
	
	echo "<table class='tabela' width='100%' align='center'>"; 
		echo "<tr class='titulo_coluna'>";
			echo "<td>Tipo de SOAF</td>";
			echo "<td>QTDE de Itens</td>";
			echo "<td>Ações</td>";
		echo "</tr>";
	for ($i = 0; $i < pg_numrows($res); $i++){
	
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$qtde_soaf 			 = pg_result($res,$i,'qtde_soaf');
		$tipo_soaf_descricao = pg_result($res,$i,'descricao');
		$tipo_soaf 			 = pg_result($res,$i,'tipo_soaf');
		$soaf 			     = pg_result($res,$i,'soaf');
		
		echo "<tr bgcolor='$cor'>";
			echo "<td>";
				echo $tipo_soaf_descricao;
			echo"</td>";
			
			echo "<td>";
				echo $qtde_soaf;
			echo"</td>";
			
			echo "<td>";
				echo "<a href='os_soaf_formulario.php?os=$os&tipo_soaf=$tipo_soaf&soaf=$soaf' target='_blank'>Preencher Formulário</a>";
			echo"</td>";
		echo"</tr>";
		
	}
	
	echo "</table>";
	exit;
}


$title = "Listagem de OS SOAF";
include "cabecalho.php";
?>
<!DOCTYPE HTML>
<html>

<head>
	<meta charset="ISO 8859-1">
	<?
	echo $styles;
	?>
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript">
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
<?
if ($_GET['ok']=='ok' and $_GET['os']){
?>
<table class="sucesso" align="center" width="700px">
	<tr>
		<td>
			O Formulário de SOAF da OS <label style="color:#FFCC33;font:bold 14px Arial"> <?=$_GET['os']?> </label> para o tipo de SOAF "<label style="color:#FFCC33;font:bold 14px Arial"> <?=$tipo_soaf_descricao?> </label>" foi preenchido com sucesso.
		</td>
	</tr>
</table>
<?
}
$sql = "SELECT 
			
			distinct(tbl_os.os),
			tbl_os.sua_os
			
		from tbl_os 
		
		JOIN tbl_os_produto on (tbl_os.os = tbl_os_produto.os) 
		JOIN tbl_os_item    on (tbl_os_produto.os_produto = tbl_os_item.os_produto) 
		JOIN tbl_soaf       on (tbl_os_item.soaf = tbl_soaf.soaf)
		
		WHERE tbl_os.fabrica = $login_fabrica 
		AND tbl_os.posto =  $login_posto 
		and tbl_os_item.soaf is not null 
		and tbl_soaf.data_abertura is null";
$res = pg_query($con,$sql);

if (pg_num_rows($res)>0){
?>
	
	<table class="tabela" align="center" width="700px" cellspacing="0" cellpadding="2">
		<tr>
			<th class="titulo_coluna" colspan="2">OS</th>
		</tr>
		<?php
		for ($i = 0; $i < pg_num_rows($res); $i++){
		
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$os_soaf = pg_result($res,$i,0);
			$sua_os_soaf = pg_result($res,$i,1);
		?>
			<tr bgcolor="<?echo $cor?>">
			
				<td>
					<a href="javascript:mostraSOAF(<?=$os_soaf?>);"><?echo $sua_os_soaf?></a>
				</td>
				<td>
					<a href="os_press.php?os=<?=$os_soaf?>" target="_blank">
						<img src="imagens/btn_consulta.gif" border="0" />
					</a>
				</td>
			</tr>
			
		<?php
		
			echo "<tr>";
				echo "<td colspan='100%' id='linha_$os_soaf' rel='' style='display:none;'>";
					echo "<div id='dados_$os_soaf' style='display:none;border: 1px solid #949494;'></div>";
				echo "</td>";
			echo "</tr>";
			
		}
		?>
		
	</table>

<?
}
?>

	
</body>

</html>

<?

include "rodape.php";

?>