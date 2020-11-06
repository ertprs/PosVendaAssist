<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$os = $_GET['os'];
if(isset($_GET["acesso_admin"]) && $_GET["acesso_admin"] == "true" ){
	$acesso_admin = "true";
}else{
	include 'autentica_usuario.php';
	$layout_menu = "os";

	$title = "Anexos de OS";
	include 'cabecalho.php';
}

if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3ve = new anexaS3('ve', (int) $login_fabrica);
    $S3_online = is_object($s3ve);
}

$s3 = new AmazonTC("os", $login_fabrica, true);
if(($login_fabrica == 11) && ($_POST["btn_acao"]=='Gravar') && (!isset($acesso_admin)) ){
	$msg_success = "";
	foreach ($_FILES as $key => $file) {
		
		if($key == "img_os_1" || $key == "img_os_2"){
			if(strlen($file["name"]) > 0 ){
				$type  = strtolower(preg_replace("/.+\//", "", $file["type"]));
				//verifica anexos os_cadastro
				if(isset($_POST["img_os_1"]) && $_POST["img_os_1"] == "true"){
					$finalNomeOs = "img_os_2";
				}else if(isset($_POST["img_os_2"]) && $_POST["img_os_2"] == "true"){
					$finalNomeOs = "img_os_1";
				}else{
					$finalNomeOs = $key;
				}

				$fileName = "anexo_os_{$login_fabrica}_{$os}_{$finalNomeOs}";
				
				$s3->upload($fileName, $file, "","");
				$msg_success = "Gravado com Sucesso";
			}

		}

		if($key == "img_os_item_1" || $key == "img_os_item_2"){
			if(strlen($file["name"]) > 0){
				$type  = strtolower(preg_replace("/.+\//", "", $file["type"]));
				//verifica anexos os_item
				if(isset($_POST["img_os_item_1"]) && $_POST["img_os_item_1"] == "true" ){
					$finalNomeOsItem = "img_os_item_2";
				}else if(isset($_POST["img_os_item_2"]) && $_POST["img_os_item_2"] == "true"){
					$finalNomeOsItem = "img_os_item_1";
				}else{
					$finalNomeOsItem = $key;
				}

				$fileName = "anexo_os_item_{$login_fabrica}_{$os}_{$finalNomeOsItem}";

				$s3->upload($fileName, $file, "","");
				if(strlen($msg_success) == 0){
					$msg_success = "Gravado com Sucesso";
				}
			}
		}

		if($key == "img_os_revenda_1"){
			if(strlen($file["name"]) > 0 && strlen($_POST["os_revenda"]) > 0){
				$os_revenda = $_POST["os_revenda"];
				$type  = strtolower(preg_replace("/.+\//", "", $file["type"]));

				//verifica anexos os_item
				$fileName = "anexo_os_revenda_{$login_fabrica}_{$os_revenda}_{$key}";

				$s3->upload($fileName, $file, "","");
				
				if(strlen($msg_success) == 0){
					$msg_success = "Gravado com Sucesso";
				}
			}
		}
	}
}

$arrAnexosOS = array("os_cadastro" => array(), "os_item" => array(), "os_revenda" => array());
$arrNamesInputs = array();
//verifica se tem anexo de OS Revenda
$sqlSuaOS = "   SELECT sua_os
                FROM tbl_os
                WHERE tbl_os.os = {$os} AND
                      fabrica = {$login_fabrica}";
$resSuaOs = pg_query($con,$sqlSuaOS);
$suaOs = pg_fetch_result($resSuaOs, 0, "sua_os");
list($suaOs,$digito) = explode("-", $suaOs);

$sqlOsRevenda = "   SELECT os_revenda
                    FROM tbl_os_revenda
                    WHERE sua_os = '{$suaOs}' AND
                          fabrica = {$login_fabrica}"; 

$resOsRevenda = pg_query($con,$sqlOsRevenda);
if(pg_num_rows($resOsRevenda)> 0 ){
	$osRevenda = pg_fetch_result($resOsRevenda, 0, "os_revenda");
	$s3->getObjectList("anexo_os_revenda_{$login_fabrica}_{$osRevenda}_img_os_revenda_");
	$anexo_os_revenda = basename($s3->files[0]);
}
//verifica anexos em os_item anexo_os_item
$prefix_os_item = "anexo_os_item_{$login_fabrica}_{$os}_img_os_item";

$s3->getObjectList($prefix_os_item, "false","","");
$anexos_os_item = $s3->files;

foreach ($anexos_os_item as $file) {
	$pathinfo = pathinfo($file);
	$name = strstr($pathinfo["filename"], "img_os_item_1");
	if($name !== false){
		$arrNamesInputs[] = $name;	
	}

	$name = strstr($pathinfo["filename"], "img_os_item_2");
	if($name !== false){
		$arrNamesInputs[] = $name;	
	}
	$arrAnexosOS["os_item"][] = $pathinfo["basename"];


}

$qtde_anexos_os_item = count($arrAnexosOS["os_item"]);

//verifica anexos de cadastro de os
$prefix_os_cadastro = "anexo_os_{$login_fabrica}_{$os}_img_os_";
$s3->getObjectList($prefix_os_cadastro, "false","","");
$anexos_os = $s3->files;

foreach ($anexos_os as $file) {
	$pathinfo = pathinfo($file);
	$name = strstr($pathinfo["filename"], "img_os_1");
	if($name !== false){
		$arrNamesInputs[] = $name;	
	}
	
	$name = strstr($pathinfo["filename"], "img_os_2");
	if($name !== false){
		$arrNamesInputs[] = $name;	
	}
	$arrAnexosOS["os_cadastro"][] = $pathinfo["basename"];

}
$qtde_anexos_os_cadastro = count($arrAnexosOS["os_cadastro"]);


if($qtde_anexos_os_item < 2){
	$qtd_inputs_os_item = 2-$qtde_anexos_os_item;
}

if($qtde_anexos_os_cadastro < 2 ){
	$qtd_inputs_os_cadastro = 2-$qtde_anexos_os_cadastro;	
}

?>

<style type="text/css">
.sucesso{
	background-color: #238E23;
    color: #FFFFFF;
    font: bold 14px "Arial";
    text-align: center;
}
.formulario {
    background-color: #D9E2EF;
    font: 11px Arial;
    text-align: left;
}


.titulo_tabela {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 14px "Arial";
    text-align: center;
}

.subtitulo_tabela {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 12px "Arial";
    text-align: center;
}
</style>
<script type='text/javascript' src='js/jquery-1.7.2.js'></script>
<script type='text/javascript' src='plugins/jquery.form.js'></script>
<link rel="stylesheet" type="text/css" href="admin/fancybox/jquery.fancybox-1.3.4.css" />
<script type='text/javascript' src='js/FancyZoom.js'></script>
<script type='text/javascript' src='js/FancyZoomHTML.js'></script>
<script>
$(function(){
	setupZoom();
	var os = $("#os").val();
	// $("form[name=frm_upload_img_os]").ajaxForm({
 //        complete: function(data) {
 //            // data = $.parseJSON(data.responseText);

 //            // if (data.erro) {
 //            //     alert(data.erro);
 //            // } else {
 //            //     $("#img_"+data.i+"_"+data.k).attr("src", data.file);
 //            //     $("input[name=temp_uploaded_"+data.i+"_"+data.k+"]").val("t");
 //            //     $("input[name=temp_uploaded_ext_"+data.i+"_"+data.k+"]").val(data.ext);

 //            // }

 //            // $("img.loadImg:visible").hide();
 //            // ajax_process = false;
 //        }
 //    });
});

</script>
<br/>
<?if(strlen($msg_success) > 0){?>
<table class="sucesso" width='650' align='center'>
<tr><td><?=$msg_success?></td></tr>
</table>
<?}?>
<form name="frm_upload_img_os" id="frm_upload_img_os" method="POST" action="<?=$PHP_SELF.'?os='.$os?>" enctype="multipart/form-data">
	<input type="hidden" id="os" name="os" value="<?=$_GET['os']?>">
	<input type="hidden" name="qtde_inputs_os_cadastro" value="<?=$qtd_inputs_os_cadastro?>">
	<input type="hidden" name="qtde_inputs_os_item" value="<?=$qtd_inputs_os_item?>">
	<? foreach ($arrNamesInputs as $names) { ?>
		<input type="hidden" name="<?=$names?>" value="true">
	<?} ?>
	<table width='650' align='center' class="formulario" border='0' cellspacing='0' cellpadding='2'>
		<thead>
			<tr >
				<th colspan="4" class="titulo_tabela">Inserir / Visualizar Anexos à OS</th>
			</tr>

		</thead>
		<tbody>

			<tr>
				<td colspan="2"  >&nbsp</td>
			</tr>

			
			<?	if($qtd_inputs_os_cadastro > 0 && (!isset($acesso_admin))){ 
					for ($i=1; $i <= $qtd_inputs_os_cadastro; $i++) { ?>
						<tr class="table_line">
							<td align="center" width="50%">
								Anexo : <input type="file" name="img_os_<?=$i?>"/>
							</td>
						</tr>
						<tr><td>&nbsp</td></tr> <?
					}
					
				}
				if(count($arrAnexosOS["os_cadastro"]) > 0){ ?>
					<tr class="table_line"><?
					for ($i=0; $i < count($arrAnexosOS["os_cadastro"]); $i++) { 
						$link = $s3->getLink($arrAnexosOS["os_cadastro"][$i]);
						$thumb = $s3->getLink("thumb_".$arrAnexosOS["os_cadastro"][$i]);?>

						
							<td align="center" width='50%'><a href="<?=$link?>"><img id="img_anexo_os_<?=$i?>" src="<?=$thumb?>" alt="Anexo da OS" ></a></td>
						

								
				<?	} ?>
					</tr>

					
			 <? } ?>
				<tr>
					<td colspan="2"  >&nbsp</td>
				</tr>


			<?

				if($qtd_inputs_os_item > 0 && (!isset($acesso_admin))){
					for ($i=1; $i<=$qtd_inputs_os_item; $i++) {?>
						<tr class="table_line">
							<td align="center" width="50%">
							Anexo: <input type="file" name="img_os_item_<?=$i?>" />
							</td>
						</tr>
						<tr><td>&nbsp</td></tr>
						<?

					}
				}

				if(count($arrAnexosOS["os_item"]) > 0){ ?>
					<tr class="table_line"><?
					for ($i=0; $i < count($arrAnexosOS["os_item"]); $i++) { 
						$link = $s3->getLink($arrAnexosOS["os_item"][$i]);
						$thumb = $s3->getLink("thumb_".$arrAnexosOS["os_item"][$i]);?>

						
							<td align="center" width="50%"><a href="<?=$link?>"><img id="thumb_img_anexo_os_<?=$i?>" src="<?=$thumb?>" alt="Anexo da OS" ></a></td>								
				<?	} ?>
					</tr>
					
				<?} ?>
				<tr><td>&nbsp</td></tr> <?
				if(strlen($anexo_os_revenda) > 0){?>
					<tr class="table_line"><?
						$link = $s3->getLink($anexo_os_revenda);
						$thumb = $s3->getLink("thumb_".$anexo_os_revenda);?>

						<td align="center" width="50%" style="text-align: center !important;"><a href="<?=$link?>"><img id="thumb_img_anexo_os_<?=$i?>" src="<?=$thumb?>" alt="Anexo da OS" ></a></td>
					</tr>
<?				}else if(pg_num_rows($resOsRevenda)> 0 ){ ?>
					<tr class="table_line">
							<td align="center" width="50%">
							<input type="hidden" name="os_revenda" value="<?=$osRevenda?>" />
							Anexo: <input type="file" name="img_os_revenda_1" />
							</td>
					</tr>
					<tr><td>&nbsp</td></tr>
			<?	}

				if( isset($acesso_admin) && ((count($arrAnexosOS["os_item"]) == 0) && (count($arrAnexosOS["os_cadastro"]) == 0)  && (strlen($anexo_os_revenda) == 0) )){ ?>
					<tr><td align="center">Não Possui Anexos</td></tr>
				<? } ?>
			<br/><br/>

			<?if( ($qtd_inputs_os_item > 0 || $qtd_inputs_os_cadastro > 0 || ((pg_num_rows($resOsRevenda) > 0) && (strlen($anexo_os_revenda) == 0) )) && (!isset($acesso_admin)) ){?>
			<tr>
				<td align="center" colspan="2" >
					<input type="submit" value="Gravar" name="btn_acao">
				</td>
			</tr>
			<?}?>
		</tbody>
	</table>
</form>
<? include "rodape.php";

if(strlen($msg_success)>0){?>
	<script type="text/javascript">window.location="os_press.php?os=<?=$os?>";</script>	
<?}



