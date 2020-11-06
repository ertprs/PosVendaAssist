<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro";
include 'autentica_admin.php';
include 'funcoes.php';


use Posvenda\Modalidade;
$objModalidade = new Modalidade($login_fabrica, $con);

if (isset($_GET["modalidade"]) && strlen($_GET["modalidade"]) > 0) {
	$modalidade = $_GET["modalidade"];
}

if ($_POST["btn_acao"] && empty($_POST["inativarAtivar"])) {
	$nome       = $_POST["nome_modalidade"];
	$ativo      = ($_POST["ativo"] == 't') ? "t" : "f";

	if (strlen($nome_modalidade) == 0) {
		$msg_erro["campos"][] = "modalidade";
		$msg_erro["msg"][]    = "Informe o nome da modalidade";
	}

	if (count($msg_erro["msg"]) == 0) {
		if (strlen($modalidade) == 0) {
			$insert = $objModalidade->Insert($nome, $ativo, $login_fabrica);
			if ($insert === true) {
				$msg_success["msg"][] = "Modalidade cadastrada com sucesso";
			} else {
				$msg_erro["msg"][] = "Erro ao cadastrar a modalidade \"$nome\"";
			}
		} else {
			$update = $objModalidade->Update($modalidade, $nome, $ativo, $login_fabrica);
			if ($update === true) {
				$msg_success["msg"][] = "Modalidade atualizada com sucesso";
			} else {
				$msg_erro["msg"][] = "Erro ao atualizar a modalidade \"$nome\"";
			}
		}

		unset($nome, $ativo);
	}
}

if ($_POST["inativarAtivar"]) {
	$id_modalidade = $_POST["modalidade"];
	$nome          = utf8_decode($_POST["nome"]);
	$status        = $_POST["status"];

	$update = $objModalidade->Update($id_modalidade, $nome, $status, $login_fabrica);
	if ($update === true) {
		if ($status == 't') {
			$aux_label = "ativada";
		} else {
			$aux_label = "inativada";
		}
		echo "Modalidade \"$nome\" $aux_label com sucesso!";
	} else {
		if ($status == 't') {
			$aux_label = "ativar";
		} else {
			$aux_label = "inativar";
		}
		echo "Erro ao $aux_label a modalidade \"$nome\"";
	}

	exit;
}

$layout_menu = "cadastro";
$title = "CADASTRO DE MODALIDADES";
include 'cabecalho_new.php';

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		var table = new Object();
        table['table'] = '#resultado_modalidade';
        table['type'] = 'full';
        $.dataTableLoad(table);
	});

	function inativarAtivar(status, modalidade, nome) {
		$.ajax({
	        type: 'POST',
	        url: 'modalidade_cadastro.php',
	        data: {
	            inativarAtivar: true,
	            nome: nome,
	            status: status,
	            modalidade: modalidade
	        },
	    }).done(function(data) {
	    	alert(data);
	    	window.location.href = "modalidade_cadastro.php";
	    });
	}

	function editar(status, modalidade, nome) {
		if (status == 't') {
			$("#ativo").prop('checked', true);
		} else {
			$("#ativo").prop('checked', false);
		}

		$("#nome_modalidade").val(nome);
		$("#modalidade").val(modalidade);
	}
</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<?php if (count($msg_success["msg"]) > 0) { ?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_modalidade' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<input type="hidden" name="modalidade" id="modalidade" value="<?=$modalidade;?>">
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("modalidade", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='nome_modalidade'>Nome Modalidade</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="nome_modalidade" id="nome_modalidade" class='span12' value= "<?=$nome?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='ativo'>Ativo</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<input type="checkbox" name="ativo" id="ativo" class='span2' value="t" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<p><br/>
		<button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Salvar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php
	$modalidades = $objModalidade->getAll();

	if (!empty($modalidades)) { ?>
		<table id="resultado_modalidade" class='table table-striped table-bordered table-hover table-fixed'>			<thead>
				<tr class='titulo_tabela' >
	                <th colspan="3" >Relação das Modalidades Cadastradas</th>
	            </tr>
	            <tr class='titulo_coluna'>
	            	<th>Nome</th>
	            	<th>Status</th>
	            	<th>Ação</th>
	            </tr>
			</thead>
			<tbody>
				<?php
					foreach ($modalidades as $modalidade_atual) {
						$aux_ativo    = ($modalidade_atual["ativo"] === true) ? "t"          : "f";
						$novo_status  = ($modalidade_atual["ativo"] === true) ? "f"          : "t";
						$label_ativo  = ($modalidade_atual["ativo"] === true) ? "Ativo"      : "Inativo";
						$label_button = ($modalidade_atual["ativo"] === true) ? "Inativar"   : "Ativar";
						$class_button = ($modalidade_atual["ativo"] === true) ? "btn-danger" : "btn-success";

						$parametros = "'$novo_status'," . $modalidade_atual["modalidade"] . ",'" . $modalidade_atual["nome"] . "'";
						$editar    = "'$aux_ativo'," . $modalidade_atual["modalidade"] . ",'" . $modalidade_atual["nome"] . "'";
						?>
						<tr>
							<td class='tal'><?=$modalidade_atual["nome"];?></td>
							<td class='tac'><?=$label_ativo;?></td>
							<td class='tac'>
								<button class="btn <?=$class_button;?>" id="btn_inativarAtivar_<?=$modalidade_atual['modalidade'];?>" onclick="inativarAtivar(<?=$parametros;?>)" ><?=$label_button;?></button>
								<button class="btn btn-warning" onclick="editar(<?=$editar;?>)">Editar</button>
							</td>
						</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php }

include 'rodape.php';
?>