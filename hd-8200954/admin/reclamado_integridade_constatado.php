<?php
/*
@author Maicon Antonio
@description Integrar Defeito Reclamado com Constatado. HD 2804217
*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';

$layout_menu = "cadastro";

$btn_acao = $_REQUEST['btn_acao'];

// Área Ajax

if ($btn_acao == 'inativar' || $btn_acao == 'ativar') {

	$diagnostico = $_REQUEST['diagnostico'];
	if(!empty($diagnostico)) {

		$status = ($btn_acao == 'ativar') ? "true" : "false";

		$sql = "UPDATE tbl_diagnostico SET ativo = {$status} WHERE diagnostico = {$diagnostico} AND fabrica = {$login_fabrica};";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0)
			$retorno = array("erro" => utf8_encode("Ocorreu um erro na alteração de status"));
		else
			$retorno = array("sucesso" => utf8_encode("Status alterado com sucesso"));
	} else {
		$retorno = array("erro" => utf8_encode("Integridade não encontrada"));
	}

	echo json_encode($retorno);
	exit;

}

// Fim - Área Ajax

if ($btn_acao == 'gravar') {
	$defeito_reclamado = $_REQUEST['defeito_reclamado'];
	$defeito_constatado = $_REQUEST['defeito_constatado'];
	$garantia = $_REQUEST['garantia'];
	$tipo     = $_REQUEST['tipo'];

	if (empty($defeito_reclamado)) {
		$msg_erro['msg'][] = "É necessário selecionar um Defeito Reclamado";
		$msg_erro['campos'][] = "defeito_reclamado";
	}

	if (empty($defeito_constatado)) {
		$msg_erro['msg'][] = "É necessário selecionar um Defeito Constatado";
		$msg_erro['campos'][] = "defeito_constatado";
	}

    if ($tipo == 272) {
        $garantia = 'f';
    }

	if (empty($garantia)) {
		$msg_erro['msg'][] = "É necessário selecionar Garantia (Sim/Não)";
		$msg_erro['campos'][] = "garantia";
	}

	if (count($msg_erro['msg']) == 0) {
		$sql = "SELECT * FROM tbl_diagnostico WHERE defeito_reclamado = {$defeito_reclamado} AND defeito_constatado = {$defeito_constatado} AND garantia = '{$garantia}' AND fabrica = {$login_fabrica};";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$msg_erro['msg'][] = "Esse relacionamento já está cadastrado";
			$msg_erro['campos'][] = "defeito_reclamado";
			$msg_erro['campos'][] = "defeito_constatado";
			$msg_erro['campos'][] = "garantia";
		}
	}

	if (count($msg_erro['msg']) == 0) {
        if (in_array($login_fabrica, array(158))) {
            $sql = "INSERT INTO tbl_diagnostico
                        (fabrica, defeito_reclamado, defeito_constatado, ativo, garantia, tipo_atendimento)
                    VALUES
                        ({$login_fabrica}, {$defeito_reclamado}, {$defeito_constatado}, true, '{$garantia}', $tipo)";
        } else {
		$sql = "INSERT INTO tbl_diagnostico (fabrica, defeito_reclamado, defeito_constatado, ativo, garantia)
			VALUES ({$login_fabrica}, {$defeito_reclamado}, {$defeito_constatado}, true, '{$garantia}');";
		}

		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro['msg'][] = "Ocorreu um erro na inserção dos dados";
		} else {
			$msg_sucesso = "Relacionamento gravado com sucesso";
			unset($defeito_reclamado);
			unset($defeito_constatado);
			unset($garantia);
		}
	}
}

$title = "DEFEITO RECLAMADO X DEFEITO CONSTATADO";
include 'cabecalho_new.php';

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");

$title_page = "Cadastro";

if (count($msg_erro['msg']) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
	</div>
<? }
if (isset($msg_sucesso)) { ?>
	<div class="alert alert-success">
		<h4><?= $msg_sucesso; ?></h4>
	</div>
<? } ?>

<script type="text/javascript">
	$(function() {

		$.dataTableLoad({
			table: "#reclamado_x_constatado",
			type: "custom",
			config: [ "pesquisa" ]
		});

	    $('input[name=tipo]').on('change', function(){
	        if ($(this).val() == '272') {
	            $('#garantia').hide();
	        }else{
	            $('#garantia').show();
	        }
	    });

		$(document).on("click", ".status", function () {
			var diagnostico = $(this).attr('rel');
			var that = $(this);
			var btn_val = $(this).val();
			var btn_acao = $(this).val().toLowerCase();
			if (confirm('Tem certeza que deseja '+btn_val+' o registro?')) {
				$.ajax({
					url: "<?= $PHP_SELF; ?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: btn_acao, diagnostico: diagnostico },
					complete: function (data) {
						data = $.parseJSON(data.responseText);
						if (data.sucesso) {
							if (btn_acao == 'ativar') {
								$(that).removeClass("btn-success").addClass("btn-danger");
								$(that).val("Inativar");
								$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
							} else {
								$(that).removeClass("btn-danger").addClass("btn-success");
								$(that).val("Ativar");
								$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
							}
						} else {
							alert(data.erro);
						}
					}
				});
			}
		});

	});
</script>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name="frm_reclamado_constatado" method="POST" action="<?= $PHP_SELF; ?>" class="form-search form-inline tc_formulario" border="0">
	<div class="titulo_tabela"><?= $title_page; ?></div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?= (in_array("defeito_reclamado", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='defeito_reclamado'>Defeito Reclamado</label>
				<div class='controls controls-row'>
					<select name="defeito_reclamado" id="defeito_reclamado" class='span12'>
						<option value=""></option>
						<?
						$sql ="SELECT defeito_reclamado, descricao, codigo FROM tbl_defeito_reclamado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao;";
						$res = pg_query($con, $sql);

						$defeitos_reclamados = pg_fetch_all($res);

						foreach($defeitos_reclamados as $res_defeito_reclamado) {
							$selReclamado = ($res_defeito_reclamado['defeito_reclamado'] == $defeito_reclamado) ? "SELECTED" : ""; ?>
							<option value="<?= $res_defeito_reclamado['defeito_reclamado']; ?>" <?= $selReclamado; ?>><?= $res_defeito_reclamado['codigo']." - ".$res_defeito_reclamado['descricao']; ?></option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?= (in_array("defeito_constatado", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='defeito_constatado'>Defeito Constatado</label>
				<div class='controls controls-row'>
					<select name="defeito_constatado" id="defeito_constatado" class='span12'>
						<option value=""></option>
						<?php
						$sql ="SELECT defeito_constatado, descricao, codigo FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao;";
						$res = pg_query($con, $sql);

						$defeitos_constatados = pg_fetch_all($res);

						foreach($defeitos_constatados as $res_defeito_constatado) {
							$selConstatado = ($res_defeito_constatado['defeito_constatado'] == $defeito_constatado) ? "SELECTED" : ""; ?>
							<option value="<?= $res_defeito_constatado['defeito_constatado']; ?>" <?= $selConstatado; ?>><?= $res_defeito_constatado['codigo']." - ".$res_defeito_constatado['descricao']; ?></option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<!-- Garantia/Fora Garantia -->
    <? if (in_array($login_fabrica, array(158))) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <div class='control-label'>Tipo</div>
                    <label class="radio inline" for="tipoGar">
                        <input id="tipoGar" type="radio" name="tipo" value="273" <?=(empty($tipo) || $tipo == 273) ? 'checked' : ''?>>
                        Garantia</label>
                    <label class="radio inline" for="tipoPiso">
                        <input id="tipoPiso" type="radio" name="tipo" value="272" <?=($tipo == 272) ? 'checked' : ''?>>
                        Atendimento Tipo Piso</label>
                </div>
            </div>
            <div class="span5">
                <div class="control-group">
                    <label class="control-label" for="garantia">Garantia</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?php $hidden = ($tipo == 272) ? "style='display: none;'" : '' ?>
                            <select name='garantia' id='garantia' class='span5' <?=$hidden?>>
                                <option value="">Selecione</option>
                                <option value="t">Sim</option>
                                <option value="f">Não</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <? } ?>
	<div class="row-fluid tac">
		<input type="hidden" name="btn_acao" value="" />
		<input type="button" value="Gravar" class="btn" name="gravar" onclick="if ($('input[name=btn_acao]').val() == '') { $('input[name=btn_acao]').val('gravar'); $(this).parents('form').submit();  } else { alert('Aguarde... O cadastro esta em processamento.'); }" />
	</div>
</form>
<?
if ($_GET['listar']=='piso') {
    $filtro = 'AND tbl_diagnostico.tipo_atendimento = 272';
}
$sql = "SELECT  tbl_diagnostico.diagnostico,
                tbl_diagnostico.ativo,
                tbl_diagnostico.garantia ,
                tbl_defeito_reclamado.descricao  AS defeito_reclamado_descricao,
                tbl_defeito_reclamado.codigo     AS defeito_reclamado_codigo,
                tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
                tbl_defeito_constatado.codigo    AS defeito_constatado_codigo,
                tbl_diagnostico.tipo_atendimento
        FROM    tbl_diagnostico
        JOIN    tbl_defeito_reclamado  USING (defeito_reclamado, fabrica)
        JOIN    tbl_defeito_constatado USING (defeito_constatado, fabrica)
        WHERE   tbl_diagnostico.fabrica = {$login_fabrica}
        $filtro
  ORDER BY      defeito_reclamado_descricao,
                defeito_constatado_descricao ASC;";

$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
    $captionText = isFabrica(158)
        ? "<th colspan='4'>Defeitos Cadastrados</th>".
          ($_GET['listar'] == 'piso'
            ? "<th colspan='2'><a class='btn btn-mini btn-default' href='{$_SERVER['PHP_SELF']}'>Listar Todos</a></th>"
            : "<th colspan='2'><a class='btn btn-mini btn-default' href='?listar=piso'>Listar Piso</a></th>"
          )
        : "<th colspan='100%'>Defeitos Cadastrados</th>"; // Este é para o resto
?>
	<br />
	<table id="reclamado_x_constatado" class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
            <tr class="titulo_coluna"><?=$captionText?></tr>
			<tr class="titulo_coluna">
				<th>Defeito Reclamado</th>
				<th>Defeito Constatado</th>
				<th>Ativo</th>
				<? if ($login_fabrica == 158) { ?>
					<th>Garantia</th>
					<th>Piso</th>
				<? } ?>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?
			for ($i = 0; $i < pg_num_rows($res); $i++) {
                $defeito_reclamado_descricao  = pg_fetch_result($res, $i, 'defeito_reclamado_descricao');
                $defeito_reclamado_codigo     = pg_fetch_result($res, $i, 'defeito_reclamado_codigo');
                $defeito_constatado_descricao = pg_fetch_result($res, $i, 'defeito_constatado_descricao');
                $defeito_constatado_codigo    = pg_fetch_result($res, $i, 'defeito_constatado_codigo');
                $diagnostico                  = pg_fetch_result($res, $i, 'diagnostico');
                $ativo                        = pg_fetch_result($res, $i, 'ativo');
                $tipo_atendimento             = pg_fetch_result($res, $i, 'tipo_atendimento');
                $garantia                     = pg_fetch_result($res, $i, 'garantia');
                $imagem_ativo                 = ($ativo            == "t") ? 'status_verde.png' : 'status_vermelho.png';
                $imagem_piso                  = ($tipo_atendimento == 272) ? 'status_verde.png' : 'status_vermelho.png';
                $imagem_garantia              = ($garantia         == "t") ? 'status_verde.png' : 'status_vermelho.png';
                $btn_value                    = ($ativo            == "t") ? 'Inativar'         : 'Ativar';
                $class_btn                    = ($ativo            == "t") ? 'btn-danger'       : 'btn-success'; ?>
				<tr>
					<td class="tac"><?= $defeito_reclamado_codigo." - ".$defeito_reclamado_descricao; ?></td>
					<td class="tac"><?= $defeito_constatado_codigo." - ".$defeito_constatado_descricao; ?></td>
					<td class="tac"><img name="visivel" src="imagens/<?= $imagem_ativo; ?>" /></td>
					<? if ($login_fabrica == 158) { ?>
						<td class="tac"><img src="imagens/<?= $imagem_garantia; ?>" /></td>
						<td class="tac"><img src="imagens/<?= $imagem_piso; ?>" /></td>
					<? } ?>
					<td class="tac"><input type="button" class="btn <?= $class_btn; ?> status" rel="<?= $diagnostico; ?>" value="<?= $btn_value; ?>" /></td>
				</tr>
			<? } ?>
		</tbody>
	</table>
<? } else { ?>
	<div class="alert">
		<h5>Nenhum resultado encontrado.
    <?php if ($_GET['listar'] == 'piso'): ?>
        <a class='btn btn-mini btn-default' href='<?=$_SERVER['PHP_SELF']?>'>Listar Todos</a>
    <?php endif; ?>
	</h5></div>
<? }
include 'rodape.php';

