<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3 = new anexaS3('ve', (int) $login_fabrica);
}

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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="pt-br">
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
		//$('#sb-nav-close').attr('style','visibility:hidden');		
			$(".close").click(function(){
				window.parent.Shadowbox.close();
			});

		});
	</script>

	<style>
	body {
		margin: 0;
		height:100%;
		font-family: Arial, Verdana, Times, Sans;
		background: #eeeeee; /* Old browsers */
		background: #DCDCDC; /* Old browsers */
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#DCDCDC), color-stop(100%,#DCDCDC)); /* Chrome,Safari4+ */
		background: linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* W3C */
		background: -o-linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* Opera 11.10+ */
		background: -ms-linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* IE10+ */
		background: -moz-linear-gradient(top, #DCDCDC , #DCDCDC ); /* FF3.6+ */
		background: -webkit-linear-gradient(top, #DCDCDC 0%,#DCDCDC 100%); /* Chrome10+,Safari5.1+ */
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#DCDCDC', endColorstr='#DCDCDC',GradientType=0 ); /* IE6-9 */
	}
	
	#conteudo{
		margin: auto;
		width: 100%;
	}

	#conteudo img{
		float:left;
	}
	
	#conteudo table{
		font: bold 11px 'Arial';
		text-align:justify;
		margin-left:40px;
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
						window.parent.document.getElementById('leitura_comunicado').value = '1';
						window.parent.Shadowbox.close();
					}
				}
			}
		});
	}
</script>
</head>
<body>

<?php
##### COMUNICADOS - INÍCIO #####
		$referencia = $_REQUEST['referencia'];

		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND (referencia_pesquisa = '$referencia' OR referencia = '$referencia')";
		$res_comun = pg_exec($con,$sql);

		if (pg_numrows($res_comun) > 0){
			$produto = pg_result($res_comun,0,0);
			$sql =	"SELECT tbl_comunicado.comunicado                                       ,
							tbl_comunicado.descricao                                        ,
							tbl_comunicado.mensagem                                         ,
							tbl_comunicado.extensao                                         ,
							tbl_comunicado.tipo                                             ,
							TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
							tbl_comunicado.produto                                          ,
							tbl_produto.referencia                    AS produto_referencia ,
							tbl_produto.descricao                     AS produto_descricao
					FROM tbl_comunicado
					JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
					WHERE tbl_comunicado.fabrica = $login_fabrica
					AND   tbl_comunicado.produto = $produto
					AND   tbl_comunicado.obrigatorio_os_produto IS TRUE
					AND   tbl_comunicado.comunicado NOT IN(SELECT comunicado FROM tbl_comunicado_posto_blackedecker WHERE comunicado = tbl_comunicado.comunicado AND posto = $login_posto)
					ORDER BY tbl_comunicado.data DESC;";

			$res_comun = pg_exec($con,$sql);

			if (pg_num_rows($res_comun) > 0) {
				extract(pg_fetch_assoc($res_comun, 0), EXTR_PREFIX_ALL, 'com');
			?>
			
			<div id="conteudo">
				<img src="imagens/blackedecker2.png"> <br><br><br>
				<table align="center" id="tbl_comunicados" border='0' width="80%">
					<caption>Comunicado referente ao produto :</caption>
					<tr class='produto'>
						<td align="center" colspan="3">
							<b>
							<?php echo $com_produto_descricao;?>
							</b>
						</td>
					</tr>
					
					<?php
						for($k = 0; $k < pg_numrows($res_comun); $k++){
							extract(pg_fetch_assoc($res_comun, $k), EXTR_PREFIX_ALL, 'com');
					?>
						<tr id="<?=$com_comunicado?>">
							<td align="left">
								<table width="100%" border="0" style="margin-left:0px;">
									<tr class='titulo'>
										<td>
											Título: <?php echo $com_descricao;?>
											
										</td>
									</tr>
									<tr>
										<td>
											<?php echo $com_mensagem;?>
										</td>
									</tr>

									<tr>
										<td align="center">
											<?php
												if (strlen($com_extensao) > 0){ 
													$display = "none";
													if ($S3_online) {
														$tipo_s3 = in_array($com_tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
														if ($s3->tipo_anexo != $tipo_s3)
															$s3->set_tipo_anexoS3($tipo_s3);

														$s3 = new anexaS3($tipo_s3, (int) $login_fabrica, $com_comunicado);
													}
													$fileLink = ($S3_online and $s3->temAnexo) ? $s3->url : "comunicados/$com_comunicado.$com_extensao";
											?>
													<input type="button" value="Abrir arquivo" onclick="abreAnexoComunicado('<?php echo "$fileLink, $com_comunicado"; ?>)">
													
											<?php } else {
													$display = "table-cell";
													}
											?>
											<input type="button" value="Li e confirmo" class="close" id="li_confirmo_<?php echo $com_comunicado; ?>')" style="display:<?=$display?>;">
										</td>
									</tr>
								</table>
								<br><hr>
							</td>
						</tr>
					<?php
						}
					?>
						
				</table>
			<?php
				
			} else {
				echo "<script>window.parent.Shadowbox.close();</script>";
			}
		} else {
			echo "<script>window.parent.Shadowbox.close();</script>";
		}
		##### COMUNICADOS - FIM #####
?>
		</div>
	</body>
</html>
