<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include 'includes/funcoes.php';

include 'funcoes.php';

$garantia1 = $_GET['garantia1'];
$garantia2 = $_GET['garantia2'];
$garantia3 = $_GET['garantia3'];

if ((empty($garantia3) || $garantia3 == "null") && !empty($garantia2)) {
	$meses_garantia = $garantia2;
	$exibe_pergunta_finalizar = true;
} else if (!(empty($garantia3))) {
	$exibe_pergunta_finalizar = false;
}

?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
		<link type="text/css" href="plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
		<style>

			* {
				text-align: center;
			}

			.btn-pergunta {
				width: 90%;
			}
			
		</style>
		<script>
			$(function(){

				var contexto = window.parent.document;

				$("#sim_finalizar").click(function(){

					let garantia_lorenzetti = $("#x_meses").text();
					$("#garantia_lorenzetti", contexto).val(garantia_lorenzetti);

					window.parent.page_loading();

					$("input[name=btn_acao]", contexto).val("continuar");
					$("form[name=frm_os]", contexto).submit();
					
					window.parent.Shadowbox.close();

				});

				$("#nao_finalizar").click(function(){
					window.parent.Shadowbox.close();
				});

				$(".btn-pergunta").click(function(){

					let garantia_lorenzetti = ($(this).data("tipo") == "recirculacao")
											  ? $("#garantia2").val()
											  : $("#garantia3").val();

					$("#x_meses").text(garantia_lorenzetti);
					
					$(".pergunta").slideToggle("slow");

				});

			});
		</script>
	</head>
	<body class="tc_formulario">

		<input type="hidden" id="garantia1" value="<?= $garantia1 ?>" />
		<input type="hidden" id="garantia2" value="<?= $garantia2 ?>" />
		<input type="hidden" id="garantia3" value="<?= $garantia3 ?>" />

		<br /><br />
		<div class="pergunta" <?= ($exibe_pergunta_finalizar) ? "" : "hidden" ?>>
			<div class="row-fluid">
				<h4>Cadastro Valida <span id="x_meses"><?= $meses_garantia ?></span> Meses de Garantia</h4>
				<h5 style="color: darkred;">Deseja Finalizar o cadastro?</h5>
				<br />
			</div>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<label>
						<button id="sim_finalizar" class="btn btn-success btn-large">Sim</button>
					</label>
				</div>
				<div class="span4">
					<label>
						<button id="nao_finalizar" class="btn btn-danger btn-large">Não</button>
					</label>
				</div>
				<div class="span2"></div>
			</div>
		</div>
		<div class="pergunta" <?= ($exibe_pergunta_finalizar) ? "hidden" : "" ?>>
			<div class="row-fluid">
				<h4>Selecione uma das opções referentes ao produto</h4>
			</div>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<label>
						<button class="btn btn-info btn-large btn-pergunta" data-tipo="recirculacao">Produto é de recirculação de água</button>
					</label>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<label>
						<button class="btn btn-info btn-large btn-pergunta" data-tipo="aquecimento">Produto é de aquecimento de água fria</button>
					</label>
				</div>
			</div>
		</div>
	</body>
</html>