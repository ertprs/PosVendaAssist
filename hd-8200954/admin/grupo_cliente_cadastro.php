<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_REQUEST["btn_acao"];

if ($btn_acao == "gravar") {
	$descricao = $_REQUEST['descricao'];
	$ativo = $_REQUEST['ativo'];
	
	if (strlen($descricao) == 0) {
		$msg_erro['msg'][] = "O campo descrição não pode ser vazio";
		$msg_erro['campos'][] = "descricao";
	} else if (strlen($descricao) > 60) {
		$msg_erro['msg'][] = "O campo descrição não pode ultrapassar 60 caracteres";
		$msg_erro['campos'][] = "descricao";
	}

	if (empty($ativo)) {
		$ativo = 'f';
	}

	if (count($msg_erro['msg']) == 0) {
		$sql = "INSERT INTO tbl_grupo_cliente
					(fabrica, descricao, ativo)
				VALUES
					({$login_fabrica}, '{$descricao}', '{$ativo}');";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) == 0) {
			$msg_sucesso = "Grupo de cliente cadastrado com sucesso";
			unset($btn_acao, $descricao, $ativo);
		} else {
			$msg_erro['msg'][] = "Ocorreu um erro no cadastro do grupo de cliente";
		}
	}

} else if ($btn_acao == "excluir") {

	$grupo_cliente = $_REQUEST['grupo_cliente'];

	$sql = "SELECT COUNT(*) FROM tbl_cliente WHERE grupo_cliente = {$grupo_cliente};";
	$res = pg_query($con, $sql);
	$qtdeClientesGrupo = pg_fetch_result($res, 0, 0);

	if ($qtdeClientesGrupo == 0) {

		$sql = "SELECT COUNT(*) FROM tbl_posto_grupo_cliente WHERE grupo_cliente = {$grupo_cliente};";
		$res = pg_query($con, $sql);
		$qtdePostosGrupo = pg_fetch_result($res, 0, 0);

		if ($qtdePostosGrupo == 0) {

			if(!empty($grupo_cliente)) {

				$sql = "DELETE FROM tbl_grupo_cliente WHERE grupo_cliente = {$grupo_cliente} AND fabrica = {$login_fabrica};";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0)
					$retorno = array("erro" => utf8_encode("Ocorreu um erro na exclusão do grupo de cliente"));
				else
					$retorno = array("sucesso" => utf8_encode("Grupo de cliente excluido com sucesso"));
			} else {
				$retorno = array("erro" => utf8_encode("Grupo de cliente não encontrado"));
			}
		} else {
			$retorno = array("erro" => utf8_encode("Este grupo tem {$qtdePostosGrupo} postos(s) agregado(s) e não pode ser excluído"));
		}

	} else {
		$retorno = array("erro" => utf8_encode("Este grupo tem {$qtdeClientesGrupo} cliente(s) e não pode ser excluído"));
	}

	unset($btn_acao, $grupo_cliente);

	echo json_encode($retorno);
	exit;

} else if (in_array($btn_acao, array('ativar','inativar'))) {

    $grupo_cliente = $_REQUEST["grupo_cliente"];
    $status = ($btn_acao == 'ativar') ? "true" : "false";

    $sql = "UPDATE tbl_grupo_cliente SET ativo = {$status} WHERE grupo_cliente = {$grupo_cliente}";
    $res = pg_query($con, $sql);

    $status = (pg_affected_rows($res) > 0) ? true : false;

    if($status == true){
        $descricao = "";
    }else{
        $descricao = "Erro ao alterar Status do Grupo de Cliente";
    }

    $descricao = utf8_encode($descricao);

    echo json_encode(array("status" => $status, "descricao" => $descricao));
    exit;

}

$layout_menu = "cadastro";
$title = "CADASTRO DE GRUPO DE CLIENTES";
include 'cabecalho_new.php';

if (count($msg_erro) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro); ?></h4>
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
			var grupo_cliente = $('#grupo_cliente_'+i).val();

			if (confirm("Tem certeza que deseja excluir esse Grupo de Clientes")) {			
				$.ajax({
					url: "<?= $PHP_SELF; ?>",
					type: "POST",
					data: { btn_acao: 'excluir', grupo_cliente: grupo_cliente },
					complete: function (data) {
						data = JSON.parse(data.responseText);
						if (data.sucesso) {
							alert(data.sucesso);
							$(".linha_grupo_"+i).remove();
							if ($('#tbl_grupo_cliente tr').length <= 2) {
								$('#tbl_grupo_cliente').append('<tr><td colspan="3" class="tac">Nenhum grupo de cliente cadastrado</td></tr>');
							}
						} else {
							alert(data.erro);
						}
					}
				});
			}
		});

		$(".status").on("click", function (){
			var i = $(this).attr('rel');
	        var grupo_cliente = $('#grupo_cliente_'+i).val();
	        var that = $(this);

	        var btn_acao = $(this).text().toLowerCase();
	        var r = confirm("Deseja realmente "+$(this).text()+" esse registro?");

	        if(r == false) {
                return false;
	        }

	        $.ajax({
	            url : "<?= $PHP_SELF; ?>",
	            type : "POST",
	            data : {
	                btn_acao : btn_acao,
	                grupo_cliente : grupo_cliente
	            },
	            complete: function(data){
	                data = $.parseJSON(data.responseText);
	                if(data.status == true){
	                    if (btn_acao == 'ativar') {
		                    $(that).removeClass("btn-success").addClass("btn-danger");
		                    $(that).text("Inativar");
		                    $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
		                } else {
		                    $(that).removeClass("btn-danger").addClass("btn-success");
		                    $(that).text("Ativar");
		                    $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
		                }
	                }else{
	                    alert(data.descricao);
	                }
	            }
	        });
	    });
	});
</script>

<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_grupo_cliente' method='POST' action='<?= $PHP_SELF; ?>' class='form-inline tc_formulario'>
	<div class="titulo_tabela">Cadastro de Grupo de Clientes</div>
	<br />
	<div class='row-fluid'>
	    <div class='span2'></div>
	    <div class='span5'>
			<label class="control-label" for="descricao">Descrição</label>
			<div class="controls controls-row">
	            <div class="span12">
	                <h5 class="asteristico">*</h5>
	                <input type="text" name="descricao" value="<?= $descricao; ?>" class="span12" maxlength="60" />
	            </div>
	        </div>   		
	    </div>
	    <div class="span3">
	    	<div class="checkbox">
				<label></label>
				<div class="constrols controls-row">
					<div class="span12">
						<input type="checkbox" name="ativo" id="ativo" value="t" /> Ativo
					</div>
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
	<table id='tbl_grupo_cliente' class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class="titulo_coluna">
				<th colspan="3">Relação de Grupos de Clientes Cadastrados</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Descrição</th>
				<th>Status</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?
			$sql = "SELECT * FROM tbl_grupo_cliente WHERE fabrica = {$login_fabrica} ORDER BY descricao;";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				$grupos_cadastrados = pg_fetch_all($res);
				foreach ($grupos_cadastrados as $key => $value) {
					$grupo_cliente = $value['grupo_cliente'];
					$descricao = $value['descricao'];
					$ativo = $value['ativo'];
					$img_status = ($ativo == "t") ? 'status_verde.png' : 'status_vermelho.png';
					$class_btn_ativo = ($ativo == "t") ? 'btn-danger' : 'btn-success';
					$botao_ativo = "<button type='button' class='btn btn-small {$class_btn_ativo} status' rel='{$key}'>";
                    $botao_ativo .= ($ativo == "t") ? "Inativar" : "Ativar";
                    $botao_ativo .="</button>"; ?>
					<tr class="linha_grupo_<?= $key; ?>">
						<td><?= $descricao; ?></td>
						<td class="tac"><img name="visivel" src="imagens/<?= $img_status; ?>" /></td>
						<td class="tac">
							<input type="hidden" name="grupo_cliente_<?= $key; ?>" id="grupo_cliente_<?= $key; ?>" value="<?= $grupo_cliente; ?>" />
							<?= $botao_ativo; ?>
							<button type="button" class="btn btn-small btn-danger excluir" rel="<?= $key; ?>">Excluir</button>
						</td>
					</tr>
				<? }
			} else { ?>
				<tr>
					<td colspan="3" class="tac">Nenhum grupo de cliente cadastrado</td>
				</tr>
			<? } ?>
		</tbody>
	</table>
</div>
<? include "rodape.php"; ?>
