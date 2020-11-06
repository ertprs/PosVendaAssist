<?php
$admin_privilegios = "info_tecnica";
$layout_menu 	   = "tecnica";
$title 			   = "Cadastro de Cliente ao Treinamento";
$plugins 		   = array("datepicker", "mask", "shadowbox");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';

if(isset($_GET['atualizar'])){
	$treinamento_posto_id = $_GET['treinamento_posto'];
	$cliente_id = $_GET['cliente'];
	$treinamento_id = $_GET['id'];

    $sql = "
        SELECT
           tbl_treinamento_posto.treinamento_posto AS treinamento_posto_id,
           tbl_treinamento_posto.treinamento AS treinamento_id,
           tbl_treinamento_posto.cliente AS cliente_id,
           tbl_treinamento_posto.tecnico_cpf AS cliente_cpf,
           tbl_treinamento_posto.tecnico_nome AS cliente_nome,
           tbl_treinamento_posto.tecnico_rg AS cliente_rg,
           TO_CHAR(tbl_treinamento_posto.tecnico_data_nascimento,'DD/MM/YYYY') AS cliente_data_nascimento,
           tbl_treinamento_posto.tecnico_celular AS cliente_celular,
           tbl_treinamento_posto.hotel AS cliente_agenda_hotel,
           tbl_treinamento_posto.tecnico_email  AS cliente_email 
        FROM
           tbl_treinamento_posto
        WHERE
           tbl_treinamento_posto.treinamento_posto = {$treinamento_posto_id}
           AND tbl_treinamento_posto.treinamento = {$treinamento_id}
           AND tbl_treinamento_posto.cliente = {$cliente_id}";
    $res = pg_query($con, $sql);
    $treinamento_posto_id    = pg_fetch_result($res, 0, 'treinamento_posto_id');
    $treinamento_id          = pg_fetch_result($res, 0, 'treinamento_id');
    $cliente_id              = pg_fetch_result($res, 0, 'cliente_id');
    $cliente_nome            = pg_fetch_result($res, 0, 'cliente_nome');
    $cliente_cpf             = pg_fetch_result($res, 0, 'cliente_cpf');
    $cliente_rg              = pg_fetch_result($res, 0, 'cliente_rg');
    $treinamento_data_fim    = pg_fetch_result($res, 0, 'treinamento_data_fim');
    $cliente_data_nascimento = pg_fetch_result($res, 0, 'cliente_data_nascimento');
    $cliente_celular         = pg_fetch_result($res, 0, 'cliente_celular');
    $cliente_agenda_hotel    = pg_fetch_result($res, 0, 'cliente_agenda_hotel');
    $cliente_email           = pg_fetch_result($res, 0, 'cliente_email');
}

if ($_POST["btn_acao"] == "submit") {
    $treinamento_posto_id    = $_POST['treinamento_posto_id'];
    $treinamento_id          = $_POST['treinamento_id'];
    $cliente_id              = $_POST['cliente_id'];
    $cliente_cpf             = $_POST['cliente_cpf'];
    $cliente_nome            = $_POST['cliente_nome'];
    $cliente_rg              = $_POST['cliente_rg'];
    $cliente_data_nascimento = $_POST['cliente_data_nascimento'];
    $cliente_celular         = $_POST['cliente_celular'];
    $cliente_email           = $_POST['cliente_email'];
    $cliente_agenda_hotel    = (!isset($_POST['cliente_agenda_hotel']) || empty($_POST['cliente_agenda_hotel'])) ? 'f' : $_POST['cliente_agenda_hotel'];

    if (empty($cliente_email)) {
    	$cliente_email = 'null';
    }else{
    	$cliente_email = "'$cliente_email'";
    }

    if (empty($cliente_celular)) {
    	$cliente_celular = 'null';
    }else{
    	$cliente_celular = "'$cliente_celular'";
    }

    $msg_erro = array();
    if($treinamento_id == ""){
        $msg_erro["msg"] = "Erro ao identicar o treinamento";
    }else{
        if(strlen($cliente_cpf) > 0){
            $auxiliar_cpf = str_replace(array(".", ",", "-", "/", " "), "", $cliente_cpf);
        }else{
        	$msg_erro["campos"][] = 'cpf';
        }

        if(strlen($cliente_rg) > 0){
            $auxiliar_rg = str_replace(array(".", ",", "-", "/", " "), "", $cliente_rg);
        }else{
        	$msg_erro["campos"][] = 'rg';
        }

        if(strlen($cliente_celular) > 0){
            $auxiliar_celular = str_replace(array("(",")",".", ",", "-", "/", " "), "", $cliente_celular);
        }

        if(strlen($cliente_data_nascimento) > 0){
        	$auxliar_data_nascimento = implode('-', array_reverse(explode('/', $cliente_data_nascimento)));
        }else{
        	$msg_erro["campos"][] = 'dt_nascimento';
        }

        if (strlen($cliente_id) == 0 || strlen($cliente_cpf) == 0 || strlen($cliente_nome) == 0) {
        	if (strlen($cliente_id) == 0 && !count($msg_erro["campos"])) {
        		$msg_erro["msg"] = 'Cliente não selecionado';
        	}
        	$msg_erro["campos"][] = 'cpf';
        	$msg_erro["campos"][] = 'nome';
        }

        if (count($msg_erro["campos"]) && empty($msg_erro["msg"])) {
        	$msg_erro["msg"] = 'Preencha os campos obrigatórios';
        }

        if (empty($msg_erro["msg"]) && empty($treinamento_posto_id)) {
		$sql = "SELECT treinamento_posto FROM tbl_treinamento_posto WHERE treinamento = $treinamento_id AND cliente = $cliente_id";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$msg_erro["msg"] = "Cliente já cadastrado para este treinamento";
		}
	}

        if (empty($msg_erro["msg"])) {
        	pg_query($con, "BEGIN");
        	if($treinamento_posto_id != "" && $treinamento_id != "" && $cliente_id != ""){
        		$opcao = 'alterar';
	            $sql = "
	                UPDATE tbl_treinamento_posto
	                SET
	                   tecnico_cpf             = '{$auxiliar_cpf}',
	                   tecnico_nome            = '{$cliente_nome}',
	                   tecnico_rg              = '{$auxiliar_rg}',
	                   tecnico_data_nascimento = '{$auxliar_data_nascimento}',
	                   tecnico_celular         = {$auxiliar_celular},
	                   hotel                   = '{$cliente_agenda_hotel}',
	                   tecnico_email           = {$cliente_email}
	                WHERE
	                   tbl_treinamento_posto.treinamento_posto = {$treinamento_posto_id}
	                   AND tbl_treinamento_posto.treinamento = {$treinamento_id}
	                   AND tbl_treinamento_posto.cliente = {$cliente_id}";
			}else{
				$opcao = 'inserir';
                $sql = "
                    INSERT INTO tbl_treinamento_posto(
                    	treinamento,
                    	cliente,
                    	tecnico_cpf,
                    	tecnico_nome,
                    	tecnico_rg,
                    	tecnico_data_nascimento,
                    	tecnico_celular,
                    	hotel,
                    	tecnico_email,
                    	confirma_inscricao
                    ) VALUES (
                    	{$treinamento_id},
                    	{$cliente_id},
                    	'{$auxiliar_cpf}',
                    	'{$cliente_nome}',
                    	'{$auxiliar_rg}',
                    	'{$auxliar_data_nascimento}',
                    	{$auxiliar_celular},
                    	'{$cliente_agenda_hotel}',
                    	{$cliente_email},
                    	TRUE
                   	);";
			}

			pg_query($con, $sql);
			if (strlen(pg_last_error()) == 0) {
				pg_query($con, "COMMIT");
				$msg_sucesso = "Registro cadastrado/atualizado com sucesso";

                $treinamento_posto_id    = "";
                $cliente_id              = "";
                $cliente_nome            = "";
                $cliente_cpf             = "";
                $cliente_rg              = "";
                $treinamento_data_fim    = "";
                $cliente_data_nascimento = "";
                $cliente_celular         = "";
                $cliente_agenda_hotel    = "";
                $cliente_email           = "";
			}else{
				pg_query($con, "ROLLBACK");
				$msg_erro["msg"] = "Ocorreu um erro ao tentar $opcao o registro";
			}
        }
    }
}

if (!empty($msg_erro["msg"])) {
?>
	<div class="alert alert-danger"><h4><?=$msg_erro["msg"]?></h4></div>
<?php
}
if (!empty($msg_sucesso)) {
?>
	<div class="alert alert-success"><h4><?=$msg_sucesso?></h4></div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_inscreve_cliente' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Novo Cliente</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("cpf", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='cliente_cpf'>CPF</label>
                <div class='controls controls-row'>
                    <div class='input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="cliente_cpf" name="cliente_cpf" class='span12' maxlength="20" value="<?=$cliente_cpf?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="cliente" parametro="cpf" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='cliente_nome'>Nome</label>
                <div class='controls controls-row'>
                    <div class='input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="cliente_nome" name="cliente_nome" class='span12' value="<?=$cliente_nome?>" >&nbsp;
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="cliente" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("rg", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='cliente_rg'>RG</label>
                <div class="controls controls-row">
                	<h5 class='asteristico'>*</h5>
                	<input type="text" id="cliente_rg" name="cliente_rg" value="<?=$cliente_rg?>" >
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("dt_nascimento", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='cliente_data_nascimento'>Data de Nascimento</label>
                <div class="controls controls-row">
                	<h5 class='asteristico'>*</h5>
                	<input type="text" id="cliente_data_nascimento" name="cliente_data_nascimento" value="<?=$cliente_data_nascimento?>">
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='cliente_celular'>Telefone de Contato</label>
                <div class='controls controls-row'>
                    <input type="text" id="cliente_celular" name="cliente_celular" value="<?=$cliente_celular?>">
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='cliente_email'>E-mail</label>
                <div class='controls controls-row'>
                    <input type="email" id="cliente_email" name="cliente_email" value="<?=$cliente_email?>">
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='cliente_agenda_hotel'>Agenda Hotel</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="checkbox" name="cliente_agenda_hotel" value='t' <?=($_POST['cliente_agenda_hotel'] == 't' || $cliente_agenda_hotel == 't') ? 'checked' : ''?>>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="treinamento_posto_id" id="treinamento_posto_id" value="<?=$treinamento_posto_id?>"/>
    <input type="hidden" name="treinamento_id" id="treinamento_id" value="<?=$treinamento_id?>"/>
    <input type="hidden" name="cliente_id" id="cliente_id" value="<?=$cliente_id?>"/>
    <input type='hidden' id="btn_click" name='btn_acao' value=''/>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4 tac">
    		<button class='btn btn-primary' id="btn_acao" type="button">Gravar</button>
    		<button class='btn btn-voltar' type="button">Voltar</button>
    	</div>
    </div>
</form>
<script type="text/javascript">
	$(function(){
		$.datepickerLoad(Array("cliente_data_nascimento"));
        $("#cliente_celular").mask("(99) 99999-9999");
        $("#cliente_cpf").mask("999.999.999-99");
        $("#cliente_rg").mask("99.999.999-9");

        Shadowbox.init();
        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $('#btn_acao').on('click', function(){
        	submitForm($(this).parents('form'));
        });

        $('.btn-voltar').on('click', function(){
        	<?php if(isset($_GET['atualizar'])){ ?>
        		var id = <?=$_GET['id']?>;
        		window.open('cadastro_cliente_treinamento_inscritos.php?id='+id, '_self');
        	<?php }else{ ?>
        		window.open('cadastro_cliente_treinamento.php', '_self');
        	<?php } ?>
        });
	});

    function retorna_cliente(retorno){
        $("#cliente_id").val(retorno.cliente);
        $("#cliente_cpf").val(retorno.cpf);
        $("#cliente_nome").val(retorno.nome);
        $("#cliente_celular").val(retorno.telefone);
        $("#cliente_email").val(retorno.email);
    }
</script>
<? include 'rodape.php'; ?>
