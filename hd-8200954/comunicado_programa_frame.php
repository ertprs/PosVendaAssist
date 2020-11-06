<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

if($_GET['comunicado']){
	$comunicado_lido = $_GET['comunicado'];
	$sql = "SELECT comunicado 
			FROM tbl_comunicado_posto_blackedecker 
			WHERE comunicado = $comunicado_lido
			AND   posto      = $login_posto";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) == 0){
		$sql = "INSERT INTO tbl_comunicado_posto_blackedecker (comunicado, posto, data_confirmacao) VALUES ($comunicado_lido, $login_posto, CURRENT_TIMESTAMP)";
	}else{
		$sql = "UPDATE tbl_comunicado_posto_blackedecker SET 
					data_confirmacao = CURRENT_TIMESTAMP 
				WHERE  comunicado = $comunicado_lido
				AND    posto      = $login_posto";
	}
	$res = @pg_exec ($con,$sql);

	$erro = pg_last_error($con);

	if(empty($erro)){
		echo "OK";
	} else {
		echo $erro;
	}
	exit;
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Comunicados Produtos </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<script>
$(function(){
$('#sb-nav-close').attr('style','visibility:hidden');		
});
</script>
</head>

<style>
	body {
		margin: 0;
		height:100%;
		font-family: Arial, Verdana, Times, Sans;
		background: #eeeeee; /* Old browsers */
		background: #DCDCDC; /* Old browsers */
		background: -moz-linear-gradient(top, #DCDCDC , #DCDCDC ); /* FF3.6+ */
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#DCDCDC), color-stop(100%,#DCDCDC)); /* Chrome,Safari4+ */
		background: -webkit-linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* Chrome10+,Safari5.1+ */
		background: -o-linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* Opera 11.10+ */
		background: -ms-linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* IE10+ */
		background: linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* W3C */
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#DCDCDC', endColorstr='#DCDCDC',GradientType=0 ); /* IE6-9 */


	}
	
	#conteudo{
		margin: auto;
		width: 98%;
	}

	#conteudo img{
		float:left;
	}
	
	#conteudo table{
		font: bold 11px 'Arial';
		text-align:justify;
		page-break-before: always;
	}

	#conteudo table caption{
		font:bold 16px 'Arial';		
	}
	
	.titulo {
		font: bold 12px 'Arial';
		color:#696969;
	}

	.produto {
		font: bold 14px 'Arial';
		color:#FF4500;
	}


</style>

<script type="text/javascript">


	function abreAnexoComunicado(caminho,comunicado){
		window.open(caminho);
		$('#li_confirmo_'+comunicado).attr('style','display:table-cell')
	}
	
	function removeLinhas(comunicado){
		$('#' + comunicado).remove();
	}

	function leituraComunicado(comunicado){
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data: 'comunicado='+comunicado,
			success: function(data){
				if (data == 'OK')
				{
					removeLinhas(comunicado);

					var num_linhas = $("#tbl_comunicados>tbody >tr").length;
					if( num_linhas < 2 ){
						window.parent.Shadowbox.close();
					}
				}
				else
				{
					alert(data);
				}
			}
		});
	}
</script>

<body>

<?php
##### COMUNICADOS - INÍCIO #####
		$link_programa = $_REQUEST['link_programa'];

		 $link_url = basename($_SERVER["PHP_SELF"]);
    
	    if($_serverEnvironment !== "development") {
	        $link_programa2 = "/assist/".$link_url;
	        $link_programa3 = "http://posvenda.telecontrol.com.br/assist/".$link_url;
	        $link_programa4 = "https://posvenda.telecontrol.com.br/assist/".$link_url;
	    } else {
	        $link_programa2 = "/PosVendaAssist/".$link_url;
	        $link_programa3 = "http://novodevel.telecontrol.com.br/~gaspar/PosVendaAssist/".$link_url;
	        $link_programa4 = "https://novodevel.telecontrol.com.br/~gaspar/PosVendaAssist/".$link_url;
	    }

		$sql =	"SELECT tbl_comunicado.comunicado                        ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.extensao                          ,
						tbl_comunicado.tipo								 ,
						TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data,
						tbl_comunicado.programa                                          
				   FROM tbl_comunicado
				  WHERE tbl_comunicado.fabrica = $login_fabrica
					AND tbl_comunicado.tipo = 'Comunicado por tela'
					AND (tbl_comunicado.programa = '$link_programa' OR tbl_comunicado.programa = '$link_programa2' OR tbl_comunicado.programa = '$link_programa3' OR tbl_comunicado.programa = '$link_programa4')
					AND tbl_comunicado.comunicado NOT IN(
							SELECT comunicado
							  FROM tbl_comunicado_posto_blackedecker
							 WHERE comunicado = tbl_comunicado.comunicado 
							   AND posto      = $login_posto)
			   ORDER BY tbl_comunicado.data DESC;";

		$res_comun = pg_exec($con,$sql);

		if (pg_numrows($res_comun) > 0){
		?>
		
		<div id="conteudo">
			<img src="imagens/blackedecker2.png"> <br><br><br>
			<table align="center" id="tbl_comunicados" border='0'>
				<caption>Comunicado</caption>
				<?php
					for($k = 0; $k < pg_numrows($res_comun); $k++){
				?>
					<tr id="<?=pg_result($res_comun,$k,comunicado)?>">
						<td align="left">
							<table width="100%" border="0" style="margin-left:0px;">
								<tr class='titulo'>
									<td>
										<?php echo pg_result($res_comun,$k,descricao);?>
										
									</td>
								</tr>
								<tr>
									<td>
										<?php echo pg_result($res_comun,$k,mensagem);?>
									</td>
								</tr>

								<tr>
									<td align="center">
										<?php
											if (strlen(pg_result($res_comun,$k,comunicado)) > 0 && strlen(pg_result($res_comun,$k,extensao)) > 0){ 
												$display = "none";
												if ($S3_online) {
													$tipo_s3 = in_array(pg_result($res_comun,$k,tipo), explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
													if ($s3->tipo_anexo != $tipo_s3)
														$s3->set_tipo_anexoS3($tipo_s3);

													$s3 = new anexaS3($tipo_s3, (int) $login_fabrica, pg_result($res_comun,$k,comunicado));
												}
												$fileLink = ($S3_online and $s3->temAnexo) ? $s3->url :
															"comunicados/pg_result($res_comun,$k,comunicado).pg_result($res_comun,$k,extensao)";
										?>
												<input type="button" value="Abrir arquivo" onclick="abreAnexoComunicado('<?php echo $fileLink; ?>',<?php echo pg_result($res_comun,$k,comunicado); ?>)">
												
										<?php } else {
												$display = "table-cel";
												}
										?>
										<input type="button" value="Li e confirmo" id="li_confirmo_<?php echo pg_result($res_comun,$k,comunicado); ?>" onclick="leituraComunicado('<?php echo pg_result($res_comun,$k,comunicado); ?>')" style="display:<?=$display?>;">
									</td>
								</tr>
							</table>
							<br><hr>
						</td>
					</tr>
				<?php
					}
				?>
					<tr>
						<td align="center">
							<input type="button" value="Leio Depois" onclick="window.parent.Shadowbox.close();">
						</td>
					</tr>
			</table>
		<?php
			
		} else {
			echo "<script>window.parent.Shadowbox.close();</script>";
		}
		
		##### COMUNICADOS - FIM #####
?>
		</div>
	</body>
</html>
