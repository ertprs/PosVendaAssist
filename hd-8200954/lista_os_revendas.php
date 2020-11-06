
<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";
	include "funcoes.php";

	if(isset($_POST["gravar"])){
		include_once "class/aws/s3_config.php";
		include_once S3CLASS;

		$s3 = new AmazonTC("os", $login_fabrica);

		$arquivo = $_FILES["nota_fiscal_saida"];
		$os = $_POST["os_upload"];

		if($arquivo["size"] == 0){
			$erro["erro"] = "Por favor insira um arquivo para upload";
		}

		if(empty($os)){
			$erro["erro"] = "Por favor insira um arquivo para upload";
		}

		if(count($erro) == 0){

			/*
			* Anexo
			*/
			$ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

		    if ($ext == "jpeg") {
		        $ext = "jpg";
		    }

			$os = explode(",", $os);

			for($i = 0; $i < count($os); $i++){

				$os_atual = $os[$i];

				$sql = "UPDATE tbl_os SET status_checkpoint = 12 WHERE os = {$os_atual}";
				$res = pg_query($con, $sql);

				$s3->upload("nota-fiscal-saida-{$os_atual}", $arquivo);

			}

		}

		if(count($erro) > 0){
			echo json_encode($erro);
		} else {
			echo json_encode(array("anexado" => true));
		}

		exit;

	}

	$os_revenda = $_REQUEST["os_revenda"];
	$tipo = $_REQUEST["tipo"];

	if(!empty($tipo) && $tipo == "comum"){

		$sql = "SELECT 
			tbl_os.sua_os AS sua_os,
			tbl_os.os AS os,
			tbl_produto.descricao AS produto,
			tbl_os.serie,
			tbl_os.nota_fiscal,
			tbl_os.data_nf 
		FROM tbl_os 
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
		WHERE tbl_os.os = {$os_revenda} 
		ORDER BY tbl_os.os ASC";

	}else{

		$sql = "SELECT 
			tbl_os.sua_os AS sua_os,
			tbl_os.os AS os,
			tbl_produto.descricao AS produto,
			tbl_os_revenda_item.serie,
			tbl_os_revenda_item.nota_fiscal,
			tbl_os_revenda_item.data_nf 
		FROM tbl_os_revenda_item 
		JOIN tbl_produto ON tbl_os_revenda_item.produto = tbl_produto.produto 
		JOIN tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote 
		WHERE os_revenda = {$os_revenda} 
		ORDER BY tbl_os.os ASC";

	}

	$res = pg_query($con, $sql);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Lista de OSs</title>

		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/jquery.form.js"></script>

		<style>
			.box{
				padding: 10px;
				overflow-y: auto;
				overflow-x: auto;
				height: 600px;
			}
			.fixed{
				position: absolute;
				bottom: -30px !important;
				z-index: 10;
				width: 750px !important;
			}
		</style>

		<script>

			$("form[name=form_upload]").ajaxForm({
				uploadProgress: function(){	
					$('.box').hide();
					$(".anexo_erro").text("").hide();
					$(".anexado").hide();
					$('.anexo_loading').show();
				},
		        complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.erro) {
						$('.anexo_erro').text(data.erro).show();
					}else{
						$('.anexado').show();

						setTimeout(function() {
							$('.anexado').hide('500');
							window.parent.Shadowbox.close();
						}, 5000);
					}

					$('.box').show();
					$('.anexo_loading').hide();
		    	}
		    });

		</script>

	</head>
	<body>

		<div class="alert alert-block alert-error anexo_erro" style="display: none; margin: 10px;"></div>

		<div class="alert alert-block alert-info anexo_loading" style="display: none; margin: 10px;">
			Por favor aguarde o envio da Nota Fiscal de Saída, isso pode levar algum tempo. <br />
			<img src="imagens/ajax-loader.gif" style="margin-top: 40px; position: absolute; z-index: 5;" />
		</div>

		<div class="alert alert-block alert-success anexado" style="display: none; margin: 10px;">
			Upload Realizado com sucesso.
		</div>

		<div class="box">

			<h3>Lista de OSs</h3>

			<?php
			if(pg_num_rows($res) > 0){

				$rows = pg_num_rows($res);
				$os_array = array();

				echo "<table class='table table-bordered table-striped'>";
					echo "<thead>";
						echo "
							<tr class='titulo_coluna'>
								<th>OS</th>
								<th>Produto</th>
								<th>Série</th>
								<th nowrap>Nota Fiscal</th>
								<th nowrap>Data da NF</th>
							</tr>";
					echo "</thead>";
					echo "<tbody>";

					for($i = 0; $i < $rows; $i++){

						$sua_os 		= pg_fetch_result($res, $i, 'sua_os');
						$os 			= pg_fetch_result($res, $i, 'os');
						$produto 		= pg_fetch_result($res, $i, 'produto');
						$serie 			= pg_fetch_result($res, $i, 'serie');
						$nota_fiscal 	= pg_fetch_result($res, $i, 'nota_fiscal');
						$data_nf 		= pg_fetch_result($res, $i, 'data_nf');

						list($ano, $mes, $dia) = explode("-", $data_nf);
						$data_nf = $dia."/".$mes."/".$ano;

						$os_array[] = $os;

						echo "
							<tr>
								<td>{$sua_os}</td>
								<td>{$produto}</td>
								<td>{$serie}</td>
								<td>{$nota_fiscal}</td>
								<td>{$data_nf}</td>
							</tr>
						";

					}

					echo "</tbody>";
				echo "</table>";

				$os_array = implode(",", $os_array);

				?>

				<br /> <br />

				<div class='breadcrumb fixed'>

					<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" name="form_upload">

						<label for="nota_fiscal_saida" style="display: inline;">Nota Fiscal de Saída</label> &nbsp; 
						<input type="file" name="nota_fiscal_saida" id="nota_fiscal_saida" style="width: 350px;" /> 
						<input type="submit" class="btn btn-primary" value="Realizar Upload" />
						<button type="button" class="btn btn-danger" onclick="window.parent.Shadowbox.close();" >Cancelar</button>
						
						<input type="hidden" name="os_upload" value="<?php echo $os_array; ?>">
						<input type="hidden" name="gravar" value="ok" />
						<input type="hidden" name="os_revenda" value="<?php echo $os_revenda; ?>" />
						
					</form>

				</div>

				<br />

				<?php

			}else{
				echo "
				<div class='alert alert-block alert-danger text-center'>
					<strong>Nenhuma OS encontrada / A OS de Revenda ainda não foi Explodida!</strong>
				</div>
				<button type='button' class='btn btn-danger' onclick='window.parent.Shadowbox.close();' >Cancelar</button>";
			}
			?>
		</div>

	</body>
</html>