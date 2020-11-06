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

include_once __DIR__.'/funcoes.php';
include_once('plugins/fileuploader/TdocsMirror.php');
$tDocs = new TdocsMirror();
?>

<!DOCTYPE html />
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

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

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
	</style>
</head>
<body>
	<div class='container' style="overflow-y: <?=$overflow_y?>;" >
	<?php
				$link = $_GET['anexo'];
				$tipo = $_GET['tipo'];
				$name = $_GET['name'];
				$name = str_replace(array('/','_', ' '), '', $name);
				$os = $_GET['os'];				
				if(!empty($os)) {
					$sql = "select obs, tdocs_id from tbl_tdocs where fabrica = $login_fabrica and referencia_id = $os and contexto='comprovante_retirada'";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)  > 0) {
						$obs = pg_fetch_result($res, 0, 'obs');
						$tdocs_id = pg_fetch_result($res, 0, 'tdocs_id');
						$obs = json_decode($obs,true);
						$obs = $obs[0];
					}
				}
                if (strlen($link) > 0) { ?>

					<div class='titulo_tabela'>Anexo do comprovante da OS</div>
						<br>
						<div class='row-fluid'>
							<div class='span3'></div>
							<div class='span8'>
								<div class='control-group'>
		                    		<div class='controls controls-row'>
		                        		<div class='span14'>
											<?php
											$link = $tDocs->get($link)['link'];
											if($tipo == 'pdf' or strpos($obs['filename'],'pdf') !== false) {
												if(!empty($tdocs_id)) {
													echo "<a href='https://api2.telecontrol.com.br/tdocs/document/id/$tdocs_id/file/".preg_replace("/\s|,/",'_',$obs['filename'])."' target='_blank'><img src='../imagens/icone_PDF.png'></a>";
												}else{
													echo "<a href='$PHP_SELF?link=$link&nome=$name'><img src='../imagens/icone_PDF.png'></a>";
												}
											}else{
												echo "<img src='$link'>";
											}
											?>
		                        		</div>
		                    		</div>
		                		</div>
							</div>
							<div class='span3'></div>
						</div>
					</div>
				<?php }
   ?>

	</div>
</body>
</html>

<?php
	if ($_GET['link']) {
		$file = $_GET['link'];
		$nome = $_GET['nome'];

		header("Content-Description: File Transfer");
		header("Content-Type: application/octet-stream");
		header('Content-Disposition: attachment; filename="' . ($nome) . '";');	
		header('Content-Length: ' . strlen(file_get_contents($file)));
		readfile($file);
	}
?>
