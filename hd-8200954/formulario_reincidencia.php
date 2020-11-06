<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$os_reincidente = $_GET['os'];

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	include "funcoes.php";
	$area_admin = true;
} else {
	include 'autentica_usuario.php';
	include "funcoes.php";
}
?>
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
		<style>
			body {
				background-color: #D9E2EF;
				margin-top: 30px;
				margin-left: 15px;
				margin-right: 15px;
			}
			label {
				font-size: 13px;
				margin-left: 10px;
			}
			h5 {
				margin-bottom: 10px;
				text-align: center;
			}
			#obrigatorio {
				color: darkred;
				margin: 20px;
				font-size: 13px !important;
			}
			input[name=resposta] {
				height: 30px;
			}
			textarea[name=justificativa] {
				width: 70%;
			}
			label:hover {
				font-weight: bolder;
			}
		</style>
		<script>
			$(function(){

				$("#gravar_os").click(function(){

					var resposta_opcao = $(".resposta:checked").val();
					var justificativa  = $.trim($("textarea[name=justificativa]").val());

					if (resposta_opcao == undefined || resposta_opcao == "") {

						alert("Selecione uma das opções");

					} else if (justificativa == "" || justificativa.length < 20) {

						alert("Informe uma justificativa com mais de 20 caracteres");

					} else {

						$("#formulario_reincidencia_opcao", window.parent.document).val(resposta_opcao);
						$("#formulario_reincidencia_justificativa", window.parent.document).val(justificativa);

						window.parent.submit_form_os();

						window.parent.Shadowbox.close();

					}

				});

			})
		</script>
	</head>
	<body>
		<div class="alert alert-warning">
			<h5><?= traduz("Ordem de Serviço Reincidente de") ?>: <a href="os_press.php?os=<?= $os_reincidente ?>"><?= $os_reincidente ?></a></h5>
		</div>
		<h5><?= traduz("Para dar continuidade ao cadastro da OS preencha com a justificativa") ?></h5>
		<br />
		<p>
			<label>
				<input type="radio" class="resposta" name="resposta" value="<?= traduz("Produto retornou à assistência depois de retirado") ?>" /> &nbsp;&nbsp;<?= traduz("Produto retornou à assistência depois de retirado") ?>
			</label>
			<label>
				<input type="radio" class="resposta" name="resposta" value="<?= traduz("Produto retornou à assistência depois de retirado") ?>" /> &nbsp;&nbsp;<?= traduz("Produto apresentou defeito ao realizar teste na entrega ao consumidor") ?>
			</label>
			<label>
				<input type="radio" class="resposta" name="resposta" value="<?= traduz("Produto retornou à assistência depois de retirado") ?>" /> &nbsp;&nbsp;<?= traduz("Mais de um produto na mesma Nota Fiscal") ?>
			</label>
			<label>
				<input type="radio" class="resposta" name="resposta" value="<?= traduz("Produto retornou à assistência depois de retirado") ?>" /> &nbsp;&nbsp;<?= traduz("Outros") ?>
			</label>
		</p>
		<span id="obrigatorio">*(<?= traduz("OBRIGATÓRIO PREENCHIMENTO DE UMA DAS OPÇÕES ACIMA")  ?>)</span>
		<br /><br />
		<p style="text-align: center;">
			<?= traduz("Descreva um breve relato para reabertura da Ordem de serviço") ?>:<br />
			<span id="obrigatorio">*(<?= traduz("CAMPO OBRIGATÓRIO, COM MÍNIMO DE 20 CARACTERES")  ?>)</span>
			<textarea rows="3" name="justificativa"></textarea>
		</p>
		<p style="text-align: center;">
			<button id="gravar_os" class="btn btn-large"><?= traduz("Gravar") ?></button>
		</p>
	</body>
</html>