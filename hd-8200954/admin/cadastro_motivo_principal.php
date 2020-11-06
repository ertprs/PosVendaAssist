<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Cadastra Motivo Principal";
$layout_menu = "gerencia";
$admin_privilegios="gerencia";

 // echo "<pre>";
 // print_r($_POST);
 // echo "</pre>";

if ($_POST["gravar"] == "Gravar") {
	$descricao = $_POST['descricao'];
	

	if (!strlen($descricao)) {
        $msg_erro["campos"][] = "descricao";
    }
     if (count($msg_erro["campos"]) > 0) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
	}

	
	if (count($msg_erro["msg"]) == 0) {
		//Update
		if($_POST['motivo_processo'] > 0){
			$motivo_processo = $_POST['motivo_processo'];

			$sql_up = "UPDATE  tbl_motivo_processo SET descricao = '$descricao', ativo = true WHERE motivo_processo = $motivo_processo;";
			
			$res_up = pg_query($con,$sql_up);

			if(pg_last_error($con)){
		        $msg_erro["msg"][] = "Erro ao atualizar funcionário.";
		        $msg_erro["campos"][] = "update";
		    }else{
					//header("Location: cadastro_funcionario.php?msg=ok");
		    	header("Location: cadastro_motivo_principal.php?msg=ok");
			}
		}else{
			$sql_m = "SELECT descricao
			              FROM tbl_motivo_processo
			             WHERE UPPER(descricao) = UPPER('$descricao')
			             AND fabrica = $login_fabrica";

			$res_m = pg_query($con,$sql_m);

			if(pg_num_rows($res_m) == 0){
				$sql_ins = "INSERT INTO tbl_motivo_processo(descricao,fabrica)VALUES('$descricao',$login_fabrica);";
				$res_m = pg_query($con,$sql_ins);


				if(pg_last_error($con)){
					$msg_erro["msg"][] = pg_last_error($con);
				}else{
					header("Location: cadastro_motivo_principal.php?msg=ok");
				}
			}
		}
	}    
}


if ($_GET["motivo_processo"] <> "") {
	$motivo_processo_get = $_GET["motivo_processo"];
	//echo "aki";

	$sql_ed = "SELECT ativo,motivo_processo,descricao
				FROM tbl_motivo_processo
				WHERE motivo_processo = $motivo_processo_get";

	$res_ed = pg_query($con,$sql_ed);

	$_RESULT["motivo_processo"]			= pg_fetch_result($res_ed,0, 'motivo_processo');
	$_RESULT["descricao"]   			= pg_fetch_result($res_ed,0, 'descricao');
	
	//unset($_GET);


}




include 'cabecalho_new.php';

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform"
);

include 'plugin_loader.php';
?>
<script type="text/javascript">

$(function () {
		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var motivo_processo = $(this).parent().find("input[name=motivo_processo]").val();
				var that     = $(this);
				
				$.ajax({
					async: false,
					url: "cadastro_motivo_processo_ajax.php",
					type: "POST",
					dataType: "JSON",
					data: { motivo_processo: motivo_processo },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == " success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": "Alterar a condição de pagamento para não visível" });
							$(that).text("Inativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
						}

						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var motivo_processo = $(this).parent().find("input[name=motivo_processo]").val();
				var that     = $(this);
				
				$.ajax({
					async: false,
					url: "cadastro_motivo_processo_ajax.php",
					type: "POST",
					dataType: "JSON",
					data: { motivo_processo: motivo_processo },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						
						data = data.responseText;
						if(data == " success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": "Alterar a condição de pagamento para visível" });
							$(that).text("Ativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
						}

						loading("hide");
					}
				});
			}
		});
});

</script>



<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>

<?php
}
?>

<?php
if (strlen($_GET['msg']) > 0) {
	$msg = "Motivo cadastrado com sucesso";

?>
<br />
    <div class="alert alert-success">
		<h4> <? echo $msg;?></h4>
    </div>
<?php
}

?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_pesquisa_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
	<input type="hidden" id="motivo_processo" name="motivo_processo" value="<?=getValue('motivo_processo')?>"/>
	<div class="titulo_tabela">Cadastra Motivo Principal</div>
	<br>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span7'>
			<div class='control-group <?=(in_array('descricao', $msg_erro['campos'])) ? "error" : "" ?>'>
				<label class='control-label'>Motivo Principal</label>
				<div class='controls controls-row'>					
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="descricao" id="descricao" size="12"  class='span12' value="<?=getValue('descricao')?>" >
					</div>
				</div>
			</div>
		</div>  
		<div class="span1"></div>
	</div> 
	   	
	<br />
	<p class="tac">
		<input type="submit" class="btn" name="gravar" value="Gravar" />
	</p>
	<br />
</FORM>
<br /> 

<!-- Tabela -->
<?
//Lista todos os Processos Cadastrados
	$sql_func = "SELECT descricao, motivo_processo, ativo
		              FROM tbl_motivo_processo
		             WHERE fabrica = $login_fabrica
		             ORDER BY ativo DESC";
	
	$res_func = pg_query($con,$sql_func);

if(pg_num_rows($res_func) > 0){
                    
?>
<form name="frm_tab" method="GET" class="form-search form-inline" enctype="multipart/form-data" >
	<table class='table table-striped table-bordered table-hover table-fixed'>
		<col width="350">
  		<col width="30">
		<thead>
			<tr class='titulo_coluna'>
				<td>Descrição</td>
				<td>Ações</td>
			</tr>
		</thead>
		<tbody>
	<?
			for ($i = 0 ; $i < pg_num_rows($res_func) ; $i++) {

				$descricao_tabela		= pg_fetch_result($res_func, $i, 'descricao');
				$motivo_processo		= pg_fetch_result($res_func, $i, 'motivo_processo');
				$ativo					= pg_fetch_result($res_func, $i, 'ativo');
	?>	
			<tr id="<?php echo $motivo_processo?>">
				<td><a href="<?=$PHP_SELF?>?motivo_processo=<?=$motivo_processo?>">
						<?echo $descricao_tabela?>
					</a>
				</td>
				<td class='tac' nowrap>
					

					<input type="hidden" name="motivo_processo" value="<?=$motivo_processo?>" />
					<?
					if ($ativo === 't') {?>
						<button type='button' name='inativar' class='btn btn-small btn-danger' title='Alterar o motivo principal para Inativo' >Inativar</button>
					<?}else {?>	
						<button type='button' name='ativar' class='btn btn-small btn-success' title='Alterar o motivo principal para Ativo' >Ativar</button>						
					<?
					}
					?>
				</td>
			</tr>
	<?
	}
	?>
		</tbody>
	</table>
</form>
<br />
<?
}
?>

<?php

include "rodape.php";

?>
