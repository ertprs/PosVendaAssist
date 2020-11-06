<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
} else {
	include "autentica_usuario.php";
	include_once 'class/tdocs.class.php';
}

include 'plugins/fileuploader/TdocsMirror.php';
include_once __DIR__.'/funcoes.php';

$plugins = array(
   "shadowbox"
);

$os = addslashes($_REQUEST["os"]);
$tr = addslashes($_REQUEST["os"]);
?>
<!DOCTYPE HTML/>
<html>
	<head>
	    <meta http-equiv=pragma content=no-cache />
	    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
	    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
	    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
	    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
	    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
	    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
	    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
	    <style>
	    	.buttons {
	    		margin: 10px 10px 10px 10px;
	    	}

	    	.flex-box {
			  display: flex;
			  align-items: center;
			  justify-content: center;
			}

			.container-box {
			  margin-top: 20px;
			}

			.content-box {
			  color: white;
			  text-align: center;
			  width: 200px;
			}
	    </style>

	    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
	    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	    <script src="bootstrap/js/bootstrap.js" ></script>
	    <script src="plugins/jquery.mask.js"></script>
	    <?php 
	    	if ($areaAdmin === true) {
	    		include __DIR__.'/admin/plugin_loader.php';	
	    	} else { 
	    		include __DIR__.'/plugin_loader.php'; 
	    	}
    	?>

		<script type="text/javascript" charset="UTF-8">
			$(function(){
				Shadowbox.init();

				$('#btn-close').on('click', function(){
					window.parent.hideLine(true);
				});

				$('#btn-back').on('click', function(){
					window.parent.closeModal();
				});
			});
		</script>
	</head>
	<body>
		<?php 
			if ($fabricaFileUploadOS) {
			    if (!empty($os)) {
			        $tempUniqueId = $os;
			        $anexoNoHash  = null;

			    } else if (strlen(getValue("anexo_chave")) > 0) {
			        $tempUniqueId = getValue("anexo_chave");
			        $anexoNoHash  = true;
			    
			    } else {
			    	$tempUniqueId = ($areaAdmin === true) ? $login_fabrica.$login_admin.date("dmYHis") : $login_fabrica.$login_posto.date("dmYHis");
			        $anexoNoHash  = true;
			    }

		        $boxUploader = array(
		            "div_id"       => "div_anexos",
		            "prepend"      => $anexo_prepend,
		            "context"      => "os",
		            "unique_id"    => $tempUniqueId,
		            "hash_temp"    => $anexoNoHash,
		            "reference_id" => $tempUniqueId
		        );

        		include "box_uploader.php";
			}
		?>
	</body>
</html>