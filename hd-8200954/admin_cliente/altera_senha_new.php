<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="contrato";
require_once 'autentica_admin.php';
if (!$moduloGestaoContrato) {
	include_once 'funcoes.php';
}
include_once '../helpdesk/mlg_funciones.php';
if ( isset($_POST['btn_acao']) ) { 
		
			$antiga 	= $_POST['antiga'];
			$nova		= $_POST['senha_nova'];
			$confirma 	= $_POST['senha_confirma'];
		
			if (empty($antiga))
				$msg_erro = 'Preencha a Senha Atual';
			if (empty($nova) && !isset($msg_erro) )
				$msg_erro = 'Preencha a Nova Senha';
			if( ( $nova != $confirma ) && !isset($msg_erro) )
				$msg_erro = 'Os campos Nova Senha e Confirmação de Senha devem ser iguais';
			if( !isset($msg_erro) && ( strlen($nova) < 6 || strlen($nova) > 10 ) )
				$msg_erro = 'A senha deve conter no mínimo 6 e no máximo 10 caracteres';
				
			if( !isset($msg_erro)) {
				$count_letras = preg_match_all('/[a-z]/i', $nova, $a_letras);
				$count_nums = preg_match_all('/\d/', $nova, $a_nums);
				if ( ($count_nums + $count_letras ) != strlen($nova) )
					$msg_erro = 'A senha deve conter apenas letras e números';
				if( $count_nums < 2 || $count_letras < 2 )
					$msg_erro = 'A senha deve conter no mínimo 2 números e 2 caracteres';
			}
			if(!isset($msg_erro)) {
			
				$sql = "SELECT admin FROM tbl_admin WHERE admin = $login_admin AND senha = '$antiga'";
				$res = pg_query($con,$sql);
				if(!pg_num_rows($res))
					$msg_erro = 'Senha Atual Inválida';
				else {
				
					$sql = "UPDATE tbl_admin SET senha = '$nova' WHERE admin = $login_admin";
					$res = pg_query($con,$sql);
					if(strlen(pg_last_error()) == 0) 
						$msg_sucesso = 'Senha Atualizada com Sucesso';
				}
			
			}
		}
$layout_menu = "contrato";
$title = "ALTERAR SENHA";
if ($moduloGestaoContrato) {
	include_once 'cabecalho_novo.php';
} else {
	include_once 'cabecalho_new.php';
}
$plugins = array(
    'autocomplete',
	'datepicker',
	'shadowbox',
	'mask',
	'dataTable',
	'multiselect'
);
include("plugin_loader.php");
?>

<style>
	#msg { text-align:center; display:none;}
</style>

<div id="msg" class="alert alert-danger">Os campos Nova Senha e Confirmação de Senha estão diferentes</div>
<?php if (strlen($msg_erro) > 0) { ?>
    <div class='alert alert-danger'>
        <strong><? echo $msg_erro; ?></strong>
    </div>
<?php } ?>
<?php if (strlen($msg_success) > 0) { ?>
    <div class='alert alert-success'>
        <strong><? echo $msg_success; ?></strong>
    </div>
<?php } ?>

<div class="row ">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_revenda" method="POST" class="form-search form-inline tc_formulario">
    <div class='titulo_tabela '>Alterar Senha</div>

    <p>&nbsp;</p>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Senha Atual</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <input class="span10" type="password" name="antiga" id="antiga" class="frm" value="<?=$antiga?>" />
                </div>
            </div>
        </div>
        <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Nova Senha</label>
                <div class='controls controls-row'>
                	<h5 class='asteristico'>*</h5>
                    <input class="span10" type="password" name="senha_nova" id="senha_nova" class="frm" value="<?=$nova?>" />
                </div>
            </div>
        </div>
        <div class="span3">
            <div class='control-group'>
                <label class='control-label' for=''>Confirmar Nova Senha</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                        <input class="span10" type="password" name="senha_confirma" id="senha_confirma" class="frm" value="<?=$confirma?>" />
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span12 text-center">
        	<br>
           <center> <button id="btn_gravar"  class="btn btn-primary" type="submit" name="btn_acao" value="gravar">Gravar</button></center>
        </div>
    </div>
</form>

<script type="text/javascript">
	$('#senha_confirma').change(function(){
		if( $(this).val() !== '' && ( $(this).val() !== $("#senha_nova").val() ) )
			$("#msg").show();
		else if($(this).val() === $("#senha_nova").val() ) 
				$("#msg").hide();
		
	});
</script>

<?php include 'rodape.php'; ?>