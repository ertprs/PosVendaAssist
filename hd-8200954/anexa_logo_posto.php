<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include_once "class/tdocs.class.php";

$posto = $_GET['posto'];

if ($_POST["btn_gravar"] == "gravar") {
    $img_posto  = $_FILES['img_posto'];
    $posto 		= $_POST['posto'];

    $msg_erro = array();

    $amazonTC = new TDocs($con, 10);
    $amazonTC->setContext('logomarca_posto');
	$types = array("png", "jpg", "jpeg");
	
	if ((strlen(trim($img_posto["name"])) > 0) && ($img_posto["size"] > 0)) {
		$type  = strtolower(preg_replace("/.+\//", "", $img_posto["type"]));

		if (!in_array($type, $types)) {
			$msg_erro["msg"][] = "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg<br />";
		} else {
			$img_posto['name'] = "logo_posto_".$posto.".".$type;
			
			if($amazonTC->uploadFileS3($img_posto, $posto, false, "logomarca_posto")){
	            $documents = $amazonTC->getdocumentsByRef($posto, 'logomarca_posto')->attachListInfo;
	            
	           	foreach ($documents as $key => $value) {
	           		$link_logo = $value['link'];
	           	}
	            $msg_success["msg"][] = "Imagem anexada com sucesso.";
	        }else{
	        	$msg_erro["msg"][] = "Erro ao anexar logomarca, tente novamente.";
	        }
		}
	}
}
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
	</head>
	<style type="text/css">
		.fileUpload {
		    position: relative;
		    overflow: hidden;
		    margin: 10px;
		}
		.fileUpload input.upload {
			position: absolute;
		    top: 0;
		    right: 0;
		    margin: 0;
		    padding: 0;
		    font-size: 20px;
		    cursor: pointer;
		    opacity: 0;
		    filter: alpha(opacity=0);
		}
		#uploadFile{
			background: #ffffff;
		}
	</style>
	<script type="text/javascript">
		$(function(){
			$('#uploadBtn').change(function(){
	            var upload = $(this).val();
	            $("#uploadFile").val(upload);
	        });
		});
	</script>
	<body style='background-color: #eeeeee'>
		<div id="container_lupa" style="overflow-y:auto;">
			<?php
			if (count($msg_erro["msg"]) > 0) {
			?>
			    <div class="alert alert-error">
					<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
			    </div>
			<?php
			}else if (count($msg_success["msg"]) > 0){
			?>
				<div class="alert alert-success">
					<h4><?=implode("<br />", $msg_success["msg"])?></h4>
			    </div>
			<?php	
			}
			?>

			<form name='frm_relatorio' id='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' enctype='multipart/form-data'>
				<input type="hidden" name="posto" value="<?=$posto?>">
				<div class="row-fluid">
					<div class='span12'>
						<div class="hero-unit">
							<h3>Anexar logomarca do Posto autorizado.</h3>
						  	<p>A logomarca será refletida na impressão da Ordem de serviço</p>
							<input id="uploadFile"  placeholder="" disabled="disabled" />
				            <div class="fileUpload btn btn-primary">
				                <span>Upload</span>
				                <input id="uploadBtn" name='img_posto' type="file" class="upload" />
				            </div>
				            <br/><br/>
				            <button type="submit" name="btn_gravar" value='gravar' class='btn btn-primary'>Gravar</button>
				            <button type='button' onclick="window.parent.retorna_anexo_logo('<?=$link_logo?>','<?=$posto?>')" class="btn">Fechar</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</body>
</html>
