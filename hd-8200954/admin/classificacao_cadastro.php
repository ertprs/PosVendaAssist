<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_REQUEST["btn_acao"];

if ($btn_acao == "gravar") {
	$descricao = $_REQUEST['descricao'];
	
	if (strlen($descricao) == 0) {
		$msg_erro['msg'][] = "O campo descrição não pode ser vazio";
		$msg_erro['campos'][] = "descricao";
	}

	if (count($msg_erro['msg']) == 0) {
		$sql = "INSERT INTO tbl_classificacao
					(fabrica, descricao)
				VALUES
					({$login_fabrica}, '{$descricao}');";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) == 0) {
			$msg_sucesso = "Classificação cadastrada com sucesso";
			unset($btn_acao, $descricao);
		} else {
			$msg_erro['msg'][] = "Ocorreu um erro no cadastro da classificação";
		}
	}

} else if ($btn_acao == "excluir") {

	$classificacao = $_REQUEST['classificacao'];

	if(!empty($classificacao)) {

		$sql = "DELETE FROM tbl_classificacao WHERE classificacao = {$classificacao} AND fabrica = {$login_fabrica};";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0)
			$retorno = array("erro" => utf8_encode("Ocorreu um erro na exclusão da classificação"));
		else
			$retorno = array("sucesso" => utf8_encode("Classificação excluida com sucesso"));
	} else {
		$retorno = array("erro" => utf8_encode("Classificação não encontrada"));
	}

	unset($btn_acao, $classificacao);

	echo json_encode($retorno);
	exit;

}

$layout_menu = "cadastro";
$title = "CADASTRO DE CLASSIFICAÇÕES";
include 'cabecalho_new.php';

if (count($msg_erro['msg']) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
	</div>
<? } else if (strlen($msg_sucesso) > 0) {?>
	<div class="alert alert-success">
		<h4><?= $msg_sucesso; ?></h4>
	</div>
<? } ?>

<script type="text/javascript">
	$(function() {
		$('.excluir').click(function() {
			var i = $(this).attr('rel');
			var classificacao = $('#classificacao_'+i).val();
			
			$.ajax({
				url: "<?= $PHP_SELF; ?>",
				type: "POST",
				data: { btn_acao: 'excluir', classificacao: classificacao },
				complete: function (data) {
					data = JSON.parse(data.responseText);
					if (data.sucesso) {
						alert(data.sucesso);
						$(".linha_classificacao_"+i).remove();
						if ($('#tbl_classificacao tr').length <= 2) {
							$('#tbl_classificacao').append('<tr><td colspan="2" class="tac">Nenhuma classificação cadastrada</td></tr>');
						}
					} else {
						alert(data.erro);
					}
				}
			});
		});
	});
</script>

<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_classificacao' method='POST' action='<?= $PHP_SELF; ?>' class='form-inline tc_formulario'>
	<input type='hidden' name='classificacao' value='<?= $classificacao; ?>' />
	<div class="titulo_tabela">Cadastro de Classificação</div>
	<br />
	<div class='row-fluid'>
	    <div class='span2'></div>
	    <div class='span5'>
			<label class="control-label" for="descricao">Descrição</label>
			<div class="controls controls-row">
	            <div class="span12">
	                <h5 class="asteristico">*</h5>
	                <input type="text" name="descricao" value="<?= $descricao; ?>" class="span12" maxlength="50" />
	            </div>
	        </div>   		
	    </div>
	    <div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span12 tac'>
			<p>
				<input type='hidden' name='btn_acao' id='btn_acao' value='' />
				<input type="button" value="Gravar" class='btn btn-default' onclick="if ($('#btn_acao').val() == '' ) { $('#btn_acao').val('gravar'); $(this).parents('form').submit(); } else { alert ('Aguarde submissão') }" alt="Gravar formulário" />
				<input type="reset" value="Limpar" class='btn btn-primary' alt="Limpar campos" />
			</p>
		</div>
	</div>
</form>
<div class='container'>
	<table id='tbl_classificacao' class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class="titulo_coluna">
				<th colspan="2">Relação de Classificações Cadastradas</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Descrição</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?
			$sql = "SELECT * FROM tbl_classificacao WHERE fabrica = {$login_fabrica} ORDER BY descricao;";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				$classificacoes_cadastradas = pg_fetch_all($res);
				foreach ($classificacoes_cadastradas as $key => $value) {
					$classificacao = $value['classificacao'];
					$descricao = $value['descricao']; ?>
					<tr class="linha_classificacao_<?= $key; ?>">
						<td><?= $descricao; ?></td>
						<td class="tac">
							<input type="hidden" name="classificacao_<?= $key; ?>" id="classificacao_<?= $key; ?>" value="<?= $classificacao; ?>" />
							<button type="button" class="btn btn-small btn-danger excluir" rel="<?= $key; ?>">Excluir</button>
						</td>
					</tr>
				<? }
			} else { ?>
				<tr>
					<td colspan="2" class="tac">Nenhuma classificação cadastrada</td>
				</tr>
			<? } ?>
		</tbody>
	</table>
</div>
<? include "rodape.php"; ?>
