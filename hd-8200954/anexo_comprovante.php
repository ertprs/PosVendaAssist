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

function getAnexo($os) {

	global $con, $login_fabrica;

	$sql = "SELECT tdocs_id
	    	FROM tbl_tdocs 
		    WHERE fabrica = $login_fabrica 
		    AND referencia_id = $os
		    AND contexto = 'comprovante_retirada' 
		    AND situacao ='ativo'"; 

	$res = pg_query($con, $sql); 

	if (pg_num_rows($res) > 0) {

		return True;
	}

	return False;
}

if ($_POST['btn_acao'] ==  "Enviar") {

	$anexoComprovante = $_FILES['file_comprovante'];
	$os = $_REQUEST['os'];

	if (strlen(trim($anexoComprovante['name'])) == 0) {
        if (array_search($extensao, $extensoes) == false) {
			$msg_erro["msg"][] = "Por favor, insira um arquivos antes de realizar o upload<br />";
        }
		
	}else{
		$extensoes = array('jpg', 'png', 'jpeg', 'pdf');
        $extensao  = strtolower(end(explode('.', $anexoComprovante['name'])));
        if (!in_array($extensao, $extensoes)) {
			$msg_erro["msg"][] = "Por favor, envie arquivos com as seguintes extensões: jpg, png, jpeg, pdf<br /> $extensao";
        }
	}

    if (count($msg_erro["msg"]) == 0) {

		$temAnexo = getAnexo($os);

        if (!$temAnexo) {

        	$dataAtual = date('Y-m-d');

            $obs = json_encode(array(
                "acao"     => "anexar",
                "filename" => "{$anexoComprovante['name']}",
                "filesize" => "{$anexoComprovante['size']}",
                "data"     => "{$dataAtual}",
                "fabrica"  => "{$login_fabrica}",
                "page"     => "anexo_comprovante.php",
                "typeId"   => "comprovante_retirada_produto",
                "descricao"=> "Comprovante de Retirada do Produto"
            ));
            
			$s3Tdocs = new TdocsMirror();
			$tdocsId = $s3Tdocs->post($anexoComprovante['tmp_name']);
			$tdocsId = array_values($tdocsId[0]);
        	$tdocsId = $tdocsId[0]['unique_id'];

            try {
				$sql_auditoria = "SELECT * from tbl_auditoria_os 
								where os = $os 
								AND auditoria_status = 3 
								AND liberada IS NULL 
								AND reprovada IS NULL 
								AND observacao = 'PRODUTOS TROCADOS NA OS'";
				$res_auditoria = pg_query($con, $sql_auditoria);

				if(pg_num_rows($res_auditoria) == 0){
					$insert_auditoria = "INSERT INTO tbl_auditoria_os(os,auditoria_status,observacao) VALUES($os,3,'PRODUTOS TROCADOS NA OS')";
					$res = pg_query($con, $insert_auditoria);
				}

	            $sql = "INSERT INTO tbl_tdocs(tdocs_id, fabrica,contexto, situacao, obs, referencia, referencia_id) 
	                    VALUES('$tdocsId', $login_fabrica, 'comprovante_retirada', 'ativo', '[$obs]', 'os', $os)";

	            $res = pg_query($con, $sql);

				if (strlen(pg_last_error()) == 0) {
					
	        	} 

        	} catch (Exception $e) {
				if (preg_match("/\\u/", $e->getMessage())) {
					$erro = utf8_decode($e->getMessage());
				} else {
					$erro = $e->getMessage();
				}

				$msg_erro["msg"][] = $erro;
			}
		}
	}
}

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
		    margin: 11px 7px;
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
		.subtitulo {
			background: #FCF0D8;
			text-align: center;
			padding: 2px 2px;
			margin: 10px auto;
			color: #392804;
		}
	</style>

    <script type="text/javascript">

    	function confirma2(){
			$("#btn_acao").val("Enviar");
			$("#form_laudo").submit();			    
    	}

		function confirma() {
			anexo = $("#uploadFile").val();
			Swal.fire({
				title: 'Deseja realmente gravar os dados ? Após a gravação não será possível alterar',
				icon: 'warning',
				showCancelButton: true,
				cancelButtonText: 'Cancelar',
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Sim'
			}).then((result) => {
				if (result.value) {
					$.ajax({
						type: 'POST',
						url: "<?=$_SERVER['PHP_SELF']?>",
						data: confirma2()
					}).done(function(msg){
						Swal.fire("Registro gravado com sucesso!", '', "success")
					})
				}
			});
		}

    	$(function() {			

			$('#uploadBtn').change(function(){
	            var upload = $(this).val();
	            $("#uploadFile").val(upload);
	        });
		});
    </script>
</head>
<body>

<?php

	if (isset($_GET["iframe"])) {

		$overflow_y = "none";
		$iframe = "&iframe=true";

	} else {

		$overflow_y = "auto";
	} ?>

	<div class='container' style="overflow-y: <?=$overflow_y?>;" >
		<?php if (strlen(trim($os)) == 0) { ?>

			<div class="alert alert-error" >Ordem de Serviço <?=$os?> não encontrada</div>

		<?php } else {					

			if (strlen(trim($os)) > 0) {
				
				if (count($msg_erro["msg"]) > 0) { ?>

				    <div class="alert alert-error">
						<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
				    </div>
				
				<?php }

				if (strlen(trim($msg_success)) > 0) { ?>
					<div class='alert alert-success'>
						<h4><?=$msg_success?></h4>
					</div>
				<?php
				}

				$temAnexo = getAnexo($os);

				if ($temAnexo) { ?>

					<div class='titulo_tabela'>Anexo do comprovante da OS</div>
						<br>
						<div class='row-fluid'>
							<div class='span3'></div>
							<div class='span8'>
								<div class='control-group'>
		                    		
		                    		<div class='controls controls-row'>
		                        		<div class='span14'>
		                            		<h3 class='asteristico'>Aguardando conferência do fabricante</h3>
		                            		<br><br><br>
		                        		</div>
		                    		</div>
		                		</div>
							</div>
							<div class='span3'></div>
						</div>
					</div>

				<?php } else { ?>

					<form name='anexo_data_conserto' enctype="multipart/form-data" id="form_laudo" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
						<input type="hidden" name="os" id="os" value="<?=$os?>">
						<div class='titulo_tabela'>Favor anexar o comprovante da OS</div>
						<br>
						<div class='row-fluid'>
							<div class='span2'></div>
							<div class='span2'></div>
							<div class='span5'>
								<div class='control-group <?=(in_array("upload", $msg_erro["campos"])) ? "error" : ""?>'>
		                    		<label class='control-label' for='upload'>Anexo</label>
		                    		<div class='controls controls-row'>
		                        		<div class='span12'>
		                            		<h5 class='asteristico'>*</h5>
		                            		<input id="uploadFile"  placeholder="" disabled="disabled" />
				                            <div class="fileUpload btn">
				                                <span>Buscar arquivo</span>
				                                <input id="uploadBtn" name='file_comprovante' type="file" class="upload" />
				                            </div>
		                        		</div>
		                    		</div>
		                		</div>
							</div>
							<div class='span2'></div>
						</div>
						<input type="hidden" id="btn_acao" name="btn_acao" value="">
					</form> 
						<div class="subtitulo">
							<span>Extensões de arquivos permitidos:</span><br>
							<span>PNG, JPEG, JPG, PDF</span><br>
						</div>
					<div class="row-fluid">
							
						<div class="span2">
						</div>
						<div class="span8">
							<div class="tac">
								<button <?=$display?> class='btn btn-info' name='btn_acao' <?=$btn_display?> onclick="confirma();"><?="Upload"?></button>
								<button type='button' onclick="window.parent.retorna_data_conserto('<?=$os?>','<?=$data?>','<?=$gravado?>')" class="btn">Sair</button>
							</div>
						</div>
						<div class="span2"></div>
					</div>
				<?php }
			} 
		} ?>

	</div>
</body>
</html>
