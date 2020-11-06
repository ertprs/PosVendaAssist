<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';


if (isset($_POST['ajax'])) {
	if (isset($_POST['estado'])) {
		echo $_POST['estado'];
		exit;
	} 
	if (isset($_POST['posto'])) {
		echo $_POST['posto'];
		exit;
	} 
} else if ($_POST) {
	$sqlEstado = "
		SELECT	contato_estado,
				nome
		FROM tbl_posto_fabrica 
		JOIN tbl_estado ON tbl_estado.estado = tbl_posto_fabrica.contato_estado
		WHERE fabrica = $login_fabrica
				AND credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO') 
		GROUP BY contato_estado, nome";
	$resEstado = pg_query($con,$sqlEstado);
}


$layout_menu = "auditoria";
$title = "AUDITORIA TOP 10 PEÇAS POR POSTO";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>
<style type="text/css">
	.table{
		min-width: 850px;
	}
</style>
<script type="text/javascript">
	$(function()
	{
		$.datepickerLoad(["data_final", "data_inicial"]);
		$('[data-estado]').on('click', function(){
			if ($('[data_estado=' + $(this).data('estado') + ']').length){
				$('[data_estado=' + $(this).data('estado') + ']').closest('tr').remove();
			} else {
				var linha = $(this);
				$.ajax({
			        type: 'POST',
			        url: 'auditoria_top_posto_pecas_ajax.php',
			        data: {
			            ajax:true,
			            estado:$(this).data('estado'),
			            inicio:$('#data_inicial').val(),
			            fim:$('#data_final').val(),
			        },
			        success: function(retorno){
						$(linha).after(retorno);
						$('[data-posto]').on('click', function(){
							if ($('[data_posto=' + $(this).data('posto') + ']').length) {
								$('[data_posto=' + $(this).data('posto') + ']').closest('tr').remove();
							} else {
								var linha = $(this);
								$.ajax({
							        type: 'POST',
							        url: 'auditoria_top_posto_pecas_ajax.php',
							        data: {
							            ajax:true,
							            posto:$(this).data('posto'),
							            estado:$(this).attr('data_estado'),
							            inicio:$('#data_inicial').val(),
							            fim:$('#data_final').val(),
							        },
							        success: function(retorno){
										$(linha).after(retorno);
									},
								});
							}
						});
					},
				});
			}
		});
		$('#btn_submit').on('click', function(){
			msg = "";
			if ($("#data_inicial").val() == '' || $("#data_final").val() == '') {
				var msg = "Preencha os campos obrigatórios";
				$("#data_inicial").addClass("error");
			    $("#data_final").addClass("error");				
			}
			if (msg != ""){
				$("#div_erro").html("<h4 align='center'>"+msg+"</h4>");
				$("#div_erro").show();
			}else{
				$("form[name=frm_relatorio]").submit();
			}
		});	
	});
</script>
<!DOCTYPE html>
<html>
<head>
	<title>AUDITORIA TOP 10 PEÇAS POR POSTO</title>
</head>
<body>
	<div id="div_erro" class="msg_erro alert alert-danger" style="display: none;"></div>
	<form method="POST" action="<?echo $PHP_SELF?>" name="frm_relatorio" id='frm_relatorio' align='center' class='form-search form-inline tc_formulario'>
			<div class="titulo_tabela">Parâmetros de Pesquisa</div>
			<br>
			<div class='row-fluid'>
				<div class='span4'></div>
					<div class='span2'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='data_inicial'>Data Inicial</label>
							<div class='controls controls-row'>
								<div class='span10'>
									<h5 class='asteristico'>*</h5>
										<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value="<?=$data_inicial?>">
								</div>
							</div>
						</div>
					</div>
				<div class='span2'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span10'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
							</div>
						</div>
					</div>
				</div>
				<div class='span4'></div>
			</div>
			<br>
			<button type="button" id='btn_submit' class="btn btn-primary">Consultar</button>
			<br>
			<br>
		</form>	
	<table align='center' border='1' cellspacing='1' cellpadding='1' class='tabela' id='lista_top_pecas' style="width: 850px;">
		<?php 
		foreach (pg_fetch_all($resEstado) as $estado) {
			echo "<tr data-estado='{$estado['contato_estado']}' style='cursor: pointer;'><td colspan='3' style='background-color: #2A78EC;'><b>{$estado['nome']}</b></td></tr>";
		}
		?>
	</table>
	</body>
</html>
<?php
if ($_POST) {
	$icon_excel  = "imagens/icon_csv.png";
	$label_excel = "Gerar Arquivo CSV";
	$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	$resposta .="<tr>";
	$resposta .= "<td id='excel_print' align='center' style='cursor: pointer; border: 0; font: bold 14px Arial;'><a style='text-decoration: none; '><img src='$icon_excel' height='40px' width='40px' align='absmiddle' >&nbsp;&nbsp;&nbsp;<span class='txt'>$label_excel</span></a></td>";
	$resposta .= "</tr>";
	$resposta .= "</table>";
	$resposta .= "<input type='hidden' id='post_data_inicial' value='{$_POST['data_inicial']}'/>";
	$resposta .= "<input type='hidden' id='post_data_final' value='{$_POST['data_final']}'/>";
	echo $resposta;
}
?>
<script type="text/javascript">
	$('#excel_print').on('click', function(){
	        var url = 'auditoria_top_posto_pecas_ajax.php';
	        var params = '?relatorio=true';
	        params += '&inicio=' + $('#post_data_inicial').val();
	        params += '&fim=' + $('#data_final').val();
	        
	        window.open(url + params);
	});
</script>

<?php include "rodape.php"; ?>
