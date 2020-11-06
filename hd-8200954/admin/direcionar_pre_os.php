<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include 'autentica_admin.php';

$hd_chamado = $_GET["hd_chamado"];

if(isset($_POST["hd_chamado"])){

	$hd_chamado 	= $_POST["hd_chamado"];
	$codigo_posto 	= $_POST["codigo_posto"];
	$nome_posto 	= $_POST["nome_posto"];

	$sql = "SELECT tbl_posto.posto 
			FROM tbl_posto_fabrica 
			JOIN tbl_posto USING(posto) 
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} 
			AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}' 
			AND tbl_posto.nome = '{$nome_posto}'";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$posto = pg_fetch_result($res, 0, "posto");

		$sql_ant = "SELECT posto FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
		$res_ant = pg_query($con, $sql_ant);

		if(pg_num_rows($res_ant) > 0){

			$posto_anterior = pg_fetch_result($res_ant, 0, "posto");

			$motivo = "Redirecionamento de pré-OS";
			$msg = "Por motivo de atraso, a pré-OS {$hd_chamado} foi redirecionada para outro Posto";

			$sql = "INSERT INTO tbl_comunicado( mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, pais, ativo) 
					VALUES ('$motivo', '$msg', 'Comunicado', $login_fabrica, 't', $posto_anterior, 'BR', 't');";
			$res = pg_query($con, $sql);

			$sql_hd = "UPDATE tbl_hd_chamado_extra SET posto = {$posto} WHERE hd_chamado = {$hd_chamado}";
			$res_hd = pg_query($con, $sql_hd);

			echo "Pré-OS {$hd_chamado} redirecionada com Sucesso";

		}

	}

	exit;

}

?>

<!DOCTYPE html />
<html>
	<head>

		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
	    <link href="css/tooltips.css" type="text/css" rel="stylesheet" />
	    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
	    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

	    <!--[if lt IE 10]>
	  	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
		<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
		<![endif]-->

	    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
	    <script src="bootstrap/js/bootstrap.js"></script>

		<?php

		$plugins = array(
			"autocomplete"
		);

		include("plugin_loader.php");

		?>

		<script>

			$(function() {
				$.autocompleteLoad(Array("posto"));
			});

			function direcionar(hd_chamado){
			    var codigo_posto = $("#codigo_posto").val();
			    var nome_posto = $("#descricao_posto").val();

			    $.ajax({
			    	url : "<?php echo $_SERVER['PHP_SERVER']; ?>",
			    	type : "POST",
			    	data: {
			    		hd_chamado : hd_chamado,
			    		codigo_posto : codigo_posto,
			    		nome_posto : nome_posto
			    	},
			    	complete: function(data){
			    		$(".alert-success").show();
			    		$(".alert-success").text(data.responseText);
			    	}
			    });

			}

		</script>
	</head>

	<body>

		<div id="topo">
			<img class="espaco" src="imagens/logo_new_telecontrol.png">
		</div>

		<br />

		<hr />

		<div style="padding: 20px;">

			<h4>Escolha um Posto Autorizado para direcionar a Pré-OS</h4> <br />

			<div class="alert alert-success" style="display: none;"></div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='codigo_posto'>Código Posto</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" name="codigo_posto" id="codigo_posto" class='span12' >
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='descricao_posto'>Nome Posto</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" name="descricao_posto" id="descricao_posto" class='span12' >
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>

			<br />

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<button class="btn" onclick="direcionar(<?php echo $hd_chamado; ?>)">Direcionar para o Posto</button>
				</div>
				<div class='span2'></div>
			</div>

		</div>

	</body>
</html>
