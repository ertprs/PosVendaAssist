<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

if (strlen($cookie_login["cook_login_posto"]) > 0) {
	include 'autentica_usuario.php';
} else {
	include 'autentica_admin.php';
}
//Nome do arquivo que contem a tabela de motivos
if ($_SERVER['HTTP_HOST'] != 'devel.telecontrol.com.br') {
    $fileName = "../bloqueio_pedidos/outros_motivos_bd.txt";
}else{
    $fileName = "outros_motivos_bd.txt"; //Para funcionar no Devel
}



if ($_POST['alterar'] == 'Alterar') {
	//echo "Alterar";
	$cod_outros_motivos = "";
	$desc_outros_motivos = "";
	$cod_motivo = $_POST['cod_outros_motivos'];
	$desc_motivo = $_POST['desc_outros_motivos'];
	$arq_jason = file_get_contents($fileName);
	$arq_jason = explode("\n", $arq_jason);

	foreach ($arq_jason as $key => $value) {
		//echo "foreach";
		 $json = json_decode($value,true);

		 if (array_key_exists($cod_motivo, $json)) {
		 	$json[$cod_motivo] = utf8_encode($desc_motivo);
		 	$json = json_encode($json);
		 	$arq_jason[$key] = $json;
		 	$arq_jason = implode("\n", $arq_jason);

		 	$file = fopen($fileName, "w");
			if ($file == false){
				$msg_erro = 'Não foi possível criar o arquivo!';
			}
			if (!isset($msg_erro)) {
				if (!fwrite($file, $arq_jason)){
					$msg_erro = 'Não foi possível atualizar o arquivo!';
				}else{
					$msg = 'Arquivo atualizado com sucesso.';
				}
			}
			fclose($file);
		}
	}
	if (!isset($msg)) {
		$msg_erro = 'Código não encontrado!';
	}
}

if ($_POST['cadastrar'] == 'Cadastrar') {

	$cod_motivo = trim($_POST['cod_outros_motivos']);
	$desc_motivo = trim($_POST['desc_outros_motivos']);

	if (!strlen($cod_motivo) ){
		$msg_erro["campos"][] = "cod_outros_motivos";
	}
	if (!strlen($desc_motivo) ) {
		$msg_erro["campos"][] = "desc_outros_motivos";
	}

	if (count($msg_erro["campos"]) > 0) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios!";
	}

	if (!isset($msg_erro)) {

		$arq_jason = file_get_contents($fileName);

		//é json
		if ( !empty($arq_jason) && is_string($arq_jason) && json_last_error() == 0 ) {
			$arq_jason = explode("\n", $arq_jason);
		}else{
			unset($arq_jason);
			$arq_jason = array();
		}

		foreach ($arq_jason as $key => $value) {
			$json_a = json_decode($value,true);

		 	foreach ($json_a as $key_json => $value_json) {
		 		$json[trim($key_json)] = trim($value_json);
		 	}

			if (array_key_exists($cod_motivo, $json)) {
				$msg_erro["msg"][] = "Código já cadastrado!";
		 		$msg_erro["campos"][] = "cod_outros_motivos";

		 		break;
		 	}
		}

		if (!isset($msg_erro)) {
			$novo_motivo = array($cod_motivo => utf8_encode($desc_motivo));
			$novo_motivo = json_encode($novo_motivo);
			$arq_jason[] = $novo_motivo;
			$arq_jason = implode("\n", $arq_jason);

			$file = fopen($fileName, "w");
			if ($file == false){
				$msg_erro["msg"][] = 'Não foi possível criar o arquivo!';
			}
			if (!isset($msg_erro)) {
				if (!fwrite($file, $arq_jason)){
					$msg_erro["msg"][] = 'Não foi possível atualizar o arquivo!';
				}else{
					$msg = 'Motivo cadastrado com sucesso.';
				}
			}
			fclose($file);
		}
	}
}

if ($_GET['outros_motivos']) {
	$cod_motivo = $_GET['outros_motivos'];
	$cod_outros_motivos = $cod_motivo;
	$arq_jason = file_get_contents($fileName);
	$arq_jason = explode("\n", $arq_jason);

	foreach ($arq_jason as $key => $value) {
		 $json_a = json_decode($value,true);
		 foreach ($json_a as $key_json => $value_json) {
		 	$json[trim($key_json)] = trim($value_json);
		 }

		if (array_key_exists($cod_motivo, $json)) {
			$desc_outros_motivos = utf8_decode($json[$cod_motivo]);
		}
	}

}

if($btn_acao == "excluirMotivo"){

	$arq_jason = file_get_contents($fileName);
	$arq_jason = explode("\n", $arq_jason);

	foreach ($arq_jason as $key => $value) {
		$json = json_decode($value,true);

		if (array_key_exists($idMotivo, $json)) {
			$json_excluir = array($key=>$value);
			$arq_jason = array_diff_key($arq_jason, $json_excluir);
	 	}
	}

	if (count($arq_jason) > 0) {
		$arq_jason = implode("\n", $arq_jason);
	}else{
		$arq_jason ="\n";
	}

	//$arq_jason = implode("\n", $arq_jason);

	$file = fopen($fileName, "w");
	if ($file == false){
		$msg_erro = 'Não foi possível criar o arquivo!';
	}
	if (!isset($msg_erro)) {
		if (!fwrite($file, $arq_jason)){
			$msg_erro = 'Não foi possível atualizar o arquivo!';
		}
	}
	fclose($file);

	if (!isset($msg_erro)) {
		$retorno = array("ok" => utf8_encode("Motivo excluído com sucesso!"));
	}else{
		$retorno = array("error" => utf8_encode($msg_erro));
	}
	exit(json_encode($retorno));
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

		<script>
			$(function () {
				$.dataTableLupa();
			});

			function excluirMotivo(idMotivo){
				$.ajax({
			        url: "outros_motivos_bd_ajaxx.php",
			        type: "POST",
			        data: {btn_acao: "excluirMotivo", idMotivo: idMotivo},
			        complete: function(data) {
						data = $.parseJSON(data.responseText);
						if (data.error) {
							alert(data.error);
						} else {
							alert(data.ok);
							$("#id_"+idMotivo).remove();

						}
					}

			    });
			}

		</script>
	</head>

	<body>

		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
			</div>
			<br />
			<hr />
		<div class="row-fluid">
			<?php
			if (count($msg_erro["msg"]) > 0) {
			?>
			    <div class="alert alert-error">
			        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
			    </div>
			<?php
			}?>
			<div class="row">
				<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
			</div>
			<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
				<div class="span1"></div>
				<div class="span2">
					<div class="control-group <?=(in_array('cod_outros_motivos', $msg_erro['campos'])) ? 'error' : ''?>">
						<label class='control-label'>Cod. Motivo</label>
						<div class='controls controls-row'>
							<div class='span12'>
								<?php
								if (!isset($_GET['outros_motivos'])){
								?>
									<h5 class='asteristico'>*</h5>
								<?php
								}
								?>
								<input type="text" name="cod_outros_motivos" id="cod_outros_motivos" class='span12' value= "<?=$cod_outros_motivos?>">
							</div>
						</div>
		   			</div>
		   		</div>
		   		<div class="span6">
					<div class="control-group <?=(in_array('desc_outros_motivos', $msg_erro['campos'])) ? 'error' : ''?>">
						<label class='control-label'>Descrição Motivo</label>
						<div class='controls controls-row'>
							<div class='span12'>
								<h5 class='asteristico'>*</h5>
								<input type="text" name="desc_outros_motivos" id="desc_outros_motivos" class='span12' value= "<?=$desc_outros_motivos?>">
							</div>
						</div>
		   			</div>
		   		</div>
				<div class="span2">
					<div>
						<br>
						<div class='controls controls-row'>
							<?php
							if ($_GET['outros_motivos']) {
							?>
								<input type="submit" class="btn" id="btn_alterar" name="alterar" value="Alterar">
							<?php
							}else{
							?>
								<input type="submit" class="btn" id="btn_cadastrar" name="cadastrar" value="Cadastrar">
							<?php
							}
							?>


						</div>
					</div>
				</div>
				<div class="span1"></div>
			</form>
		</div>
			<?
			$outros_motivos_arq = file_get_contents($fileName);

			if (strlen(trim($outros_motivos_arq[0]) ) > 0) {
				$outros_motivos_arq = explode("\n", $outros_motivos_arq);

			?>
				<div id="border_table">
					<table class="table table-striped table-bordered table-hover table-lupa" >
						<thead>
							<tr class='titulo_coluna'>
								<th style='width:150px;' >Código Outros Motivos</th>
								<th>Descrição Outros Motivos</th>
								<th style='width:150px;' >Ações</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($outros_motivos_arq as $cod => $desc) {
								$decod_motivos = json_decode($desc,true);
								$outros_motivos_cod = array_keys($decod_motivos);
								$outros_motivos_cod = $outros_motivos_cod[0];
								$outros_motivos_desc = utf8_decode($decod_motivos[$outros_motivos_cod]);
								if (empty($outros_motivos_desc)) {
									continue;
								}
							?>
								<tr id="id_<?=$outros_motivos_cod?>">
									<td><?=$outros_motivos_cod?></td>
									<td><?=$outros_motivos_desc?></td>
									<td class='tac'>
										<input type="button" id="btn_alterar_<?=$outros_motivos_cod?>" name="btn_alterar_<?=$outros_motivos_cod?>" class="btn btn-info" value="Alterar" onclick="window.location='<?php echo $PHP_SELF.'?outros_motivos='.$outros_motivos_cod; ?>'">
										<input type="button" id="btn_excluir_<?=$outros_motivos_cod?>" class="btn btn-danger" value="Excluir" onclick="excluirMotivo('<?=$outros_motivos_cod?>')">
                            		</td>
								</tr>
			                <?php
			                }
			                ?>

						</tbody>
					</table>
				</div>
            <?php
			} else {
				echo '
					<div class="alert alert_shadobox">
					    <h4>Não Foram Encontrado(s) Outros Motivos!</h4>
					</div>';
			}
			?>
	</div>
	</body>
</html>
