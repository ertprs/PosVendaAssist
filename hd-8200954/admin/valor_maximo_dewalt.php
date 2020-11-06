<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

if (isset($_POST["btn_cadastro"])) {
	$valor_maximo = str_replace(".","",$_POST['valor_maximo']);
	$valor_maximo = str_replace(",",".",$valor_maximo);

	if (!empty($valor_maximo)) {
		$AuditorLog = new AuditorLog;

		$sql = "SELECT parametros_adicionais
				FROM tbl_fabrica
				WHERE fabrica = $login_fabrica";

		$res = pg_query($con, $sql);

		$AuditorLog->RetornaDadosSelect($sql);

		$parametros_adicionais = JSON_DECODE(pg_fetch_result($res, 0, 'parametros_adicionais'));
		$parametros_adicionais->valorMaximoPedidoDewalt = $valor_maximo;
		$parametros_adicionais_pedido = JSON_ENCODE($parametros_adicionais);

		$sql = "UPDATE tbl_fabrica 
				SET parametros_adicionais = '$parametros_adicionais_pedido'
				WHERE fabrica = $login_fabrica";
		pg_query($con, $sql);

		if (pg_last_error($con)) {
			$msg_erro = 'Erro no cadastro';
		} else {
			$AuditorLog->RetornaDadosSelect()->EnviarLog('update', 'tbl_fabrica',"$login_fabrica*$login_fabrica");

			$msg_sucesso = 'Cadastro realizado com sucesso!';
		}
	} else {
		$msg_erro = "Informe um valor máximo para os pedidos Dewalt Rental";
	}

} else {
	$sql = "SELECT JSON_FIELD('valorMaximoPedidoDewalt',parametros_adicionais) as valor_maximo
			FROM tbl_fabrica
			WHERE fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	$valor_maximo = number_format(pg_fetch_result($res,0,'valor_maximo'),2,',','.');
}
?>
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
<script type="text/javascript" src="js/jquery.price_format.1.7.min.js"></script>
<script>
	$(function(){
		$("#valor_maximo").priceFormat({
	        prefix: '',
	        centsSeparator: ',',
	        thousandsSeparator: '.'
		});

		$("#auditor_log").click(function(){
			$.ajax({
                url: 'relatorio_log_alteracao_new.php?parametro=tbl_fabrica&id=<?= $login_fabrica ?>',
                success: function(data){
                    $("#lista_auditor").html(data);
                }
            });
		});
	});
</script>
	<?php 
	if (isset($_POST["btn_cadastro"])) {
		if (empty($msg_erro)) { ?>
			<div class="alert alert-success">
				<h4><?= $msg_sucesso ?></h4>
		    </div>
		<?php
		} else { ?>
			<div class="alert alert-danger">
				<h4><?= $msg_erro ?></h4>
		    </div>
	<?php
		} 
	} ?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_cadastro' method='post' id='condicoes_cadastradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario" style="height: 70%;">
	<div class="titulo_tabela">Cadastro de Valor Máximo</div>
	<br />
	<div class='row-fluid'>
		<div class='span4'></div>
		<div class='span8 tac'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>
					<b>Valor Pedidos Dewalt Rental</b>
				</label>
				<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<span class='add-on'><div style="color: black;font-weight: bolder;">R$</div></span>
							<input style="text-align: center;" type="text" name="valor_maximo" id="valor_maximo" value="<?= $valor_maximo ?>" class="span7" />
						</div>
					</div>
			</div>
		</div>
	</div>
	<br />
	<div class='row-fluid tac'>
		<input type="submit" class="btn btn-primary" name="btn_cadastro" value="Cadastrar" />
	</div>
</form>
<div id="lista_auditor">

</div>