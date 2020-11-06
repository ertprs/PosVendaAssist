<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

require_once( '../classes/Posvenda/GoogleAuthenticator.php' );


if(in_array($login_fabrica, [189])) {
	$label_origem = "Depto. Gerador da RRC";
} else {
	$label_origem = "Origem";
}
if ($_POST["btn_acao"] == "excluir") {

	$xorigem_admin = $_POST["origem_admin"];
	$sql = "SELECT hd_origem_admin FROM tbl_hd_origem_admin WHERE hd_origem_admin = {$xorigem_admin} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$sql = "DELETE FROM tbl_hd_origem_admin WHERE fabrica = {$login_fabrica} AND hd_origem_admin = {$xorigem_admin}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo json_encode(array("retorno" => utf8_encode("success"),"id_origem" => utf8_encode($xorigem_admin)));
		} else {
			echo json_encode(array("retorno" => utf8_encode("Erro ao deletar registro.")));
		}
	}else{
		echo json_encode(array("retorno" => utf8_encode("Registro não encontrado.")));
	}
	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$atendente	= $_POST['atendente'];

	$autenticador = new GoogleAuthenticator();

	$codigo_secreto = $autenticador->createSecret();


	if(!empty($codigo_secreto)) {
		$sql = "update tbl_admin set responsabilidade = '$codigo_secreto' where fabrica = 183 and admin = $atendente";
		$res = pg_query($con,$sql);

		$msg_success = "Token gravada com sucesso";
	}



}


$sql = "SELECT nome_completo,login, responsabilidade from tbl_admin where responsabilidade is not null and fabrica = $login_fabrica";
$resSubmit = pg_query($con, $sql);

$layout_menu = "cadastro";
$title = "Token Google Supervisor";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$("select").select2();
	});


	$(document).on('click','button.excluir', function(){
		if (confirm('Deseja excluir o registro ?')) {
			var btn = $(this);
	        var text = $(this).text();
	        var origem_admin = $(btn).data('origem_admin');
	        var obj_datatable = $("#resultado").dataTable()

	        $(btn).prop({disabled: true}).text("Excluindo...");
	        $.ajax({
	            method: "POST",
	            url: "<?=$_SERVER['PHP_SELF']?>",
	            data: { btn_acao: 'excluir', origem_admin: origem_admin},
	            timeout: 8000
	        }).fail(function(){
	        	//$(".btn-small").parent('.tac').html("dsadasdasdas");
	        	alert("Não foi possível excluir o registro, tempo limite esgotado!");
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.retorno == "success") {
	                $(btn).text("Excluido");
	                setTimeout(function(){
	                	$(obj_datatable.fnGetData()).each(function(idx,elem){
	                		if($(elem[2]).data('origem_admin') == origem_admin){
	                			obj_datatable.fnDeleteRow(idx);
	                			return;
	                		}
	                	});
	                }, 1000);
	            }else{
	                $(btn).prop({disabled: false}).text(text);
				}
	        });
	    }else{
	    	return false;
	    }
    });

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if(strlen($msg_success) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='atendente'>Supervidor</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name="atendente" id="atendente">
								<option value=""></option>
								<?php
								$sql = "SELECT admin, nome_completo
										FROM tbl_admin
										WHERE fabrica = $login_fabrica
										AND (callcenter_supervisor IS TRUE)
										AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_atendente = ( isset($atendente) and ($atendente == $key['admin']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
										<?php echo $key['nome_completo']?>
									</option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			</div>
		<?php if (in_array($login_fabrica, [174, 186])) { ?>
		<div class="row-fluid ref-ml" style="display:none;">
			<div class="span4"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="referencia">Referência</label>
					<div class="controls controls-row">
						<div class="span12 alert alert-warning" style="text-align:center">
							<h5 class="asteristico">*</h5>
							<input
								style="margin:0 10px"
								type="checkbox"
								<?= ($_POST['referencia_pre'] == "PRE") ? "checked" : "" ?>
								value="PRE"
								name="referencia_pre"
							> Pré-Venda
							<input
								style="margin:0 10px"
								type="checkbox"
								<?= ($_POST['referencia_pos'] == "POS") ? "checked" : "" ?>
								value="POS"
								name="referencia_pos"
							> Pós-Venda
						</div>
					</div>
				</div>
			</div>
			<div class="span4"></div>
		</div>
		<script type="text/javascript">
			$(function () {
				if ($("#origem option:selected").html().match(/.Mercado Livre.*/)) {
					$(".ref-ml").fadeIn(500);
				}

				$("#origem").on("change", function () {
					if ($("#origem option:selected").html().match(/.Mercado Livre.*/)) {
						$(".ref-ml").fadeIn(500);
					}
				});
			});
		</script>
		<?php } ?> 
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		</p><br/>
</form>

<?php
if (isset($resSubmit)) {

		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			$autenticador = new GoogleAuthenticator();
			$count = pg_num_rows($resSubmit);
		?>
			<table id="resultado" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Supervidor</th>
						<th>Login</th>
						<th>Token</th>
						<th>Ação</th>
                    </tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$atendente      = pg_fetch_result($resSubmit, $i, 'nome_completo');
						$login  	= pg_fetch_result($resSubmit, $i, 'login');
						$token      = pg_fetch_result($resSubmit, $i, 'responsabilidade');
						$id_admin  		= pg_fetch_result($resSubmit, $i, 'admin');


						$website = "https://www.telecontrol.global";
						$titulo = "$login - $atendente";
						$url_qr_code = $autenticador->getQRCodeGoogleUrl( $titulo, $token, $website );



					?>
						<tr id='<?=$origem_admin?>'>
								<td style='vertical-align: middle;'><?=$atendente?></td>
								<td style='vertical-align: middle;'><?=$login?></td>
								<td style='vertical-align: middle;' class='tac'><img src="<?=$url_qr_code?>"/></td>
								<td style='vertical-align: middle;' class='tac'>
									<button class='btn btn-danger btn-small excluir' data-origem_admin='<?=$origem_admin?>'>Excluir</button>
								</td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>

			<script>
				$.dataTableLoad({ table: "#resultado" });
			</script>
			<br />
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}



include 'rodape.php';?>
