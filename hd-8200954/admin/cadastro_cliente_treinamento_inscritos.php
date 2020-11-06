<?php
$admin_privilegios = "info_tecnica";
$layout_menu 	   = "tecnica";
$title 			   = "Cadastro de Cliente ao Treinamento";
$plugins 		   = array("dataTable");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
	if ($_POST['action'] == 'ativa_inativa') {
		$treinamento  = $_POST['treinamento'];
		$cliente 	  = $_POST['cliente'];
		$ativo 	 	  = $_POST['ativo'];
		$treinamentop = $_POST['treinamentop'];

        $sql = "UPDATE tbl_treinamento_posto SET
        			ativo = {$ativo}
            	WHERE tbl_treinamento_posto.treinamento_posto = $treinamentop
               		AND tbl_treinamento_posto.treinamento = $treinamento
               		AND tbl_treinamento_posto.cliente = $cliente";

        pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
			exit(json_encode(array("error" => utf8_encode('Erro ao tentar alterar o estado do cadastro desse usuário'))));
        }
        exit(json_encode(array("ok" => utf8_encode('ok'))));
	}
}

include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';

$treinamento_id	= $_GET['id'];
$sql = "SELECT
			tbl_treinamento_posto.treinamento_posto,
           	tbl_treinamento.titulo AS treinamento_titulo,
           	TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS treinamento_data_incio,
           	TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY') AS treinamento_data_fim,
           	tbl_linha.nome AS linha_nome,
           	tbl_treinamento.vagas AS treinamento_vagas,
           	tbl_treinamento.local AS treinamento_local,
           	(
           		SELECT count(*)
           		FROM tbl_treinamento_posto
           		WHERE ativo IS TRUE AND treinamento = {$treinamento_id}
           	)  AS vagas_preenchidas,
           	tbl_treinamento_posto.cliente,
           	tbl_treinamento_posto.tecnico_nome,
           	tbl_treinamento_posto.tecnico_cpf,
           	tbl_treinamento_posto.tecnico_rg,
           	tbl_treinamento_posto.tecnico_celular,
           	tbl_treinamento_posto.tecnico_email,
           	TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS tecnico_data_inscricao,
           	tbl_treinamento_posto.ativo AS cliente_ativo
        FROM tbl_treinamento
            LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_treinamento.linha
            LEFT JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento AND tbl_treinamento_posto.cliente IS NOT NULL
        WHERE tbl_treinamento.fabrica = $login_fabrica AND tbl_treinamento.ativo IS TRUE AND tbl_treinamento.treinamento = {$treinamento_id}";

$res  = pg_query($con, $sql);
$rows = pg_num_rows($res);
?>
<form name='frm_inscreve_cliente' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Treinamento</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='titulo'>Titulo</label>
                <div class='controls controls-row'>
                	<label><b><?=pg_fetch_result($res, 0, 'treinamento_titulo')?></b></label>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='data_inicio'>Data inicio</label>
                <div class='controls controls-row'>
                	<label><b><?=pg_fetch_result($res, 0, 'treinamento_data_incio')?></b></label>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='data_fim'>Data fim</label>
                <div class='controls controls-row'>
                	<label><b><?=pg_fetch_result($res, 0, 'treinamento_data_fim')?></b></label>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='linha'>Linha</label>
                <div class='controls controls-row'>
                	<label><b><?=pg_fetch_result($res, 0, 'linha_nome')?></b></label>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='vagas'>Total de Vagas</label>
                <div class='controls controls-row'>
                	<label><b><?=pg_fetch_result($res, 0, 'treinamento_vagas')?></b></label>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='vagas_preenchidas'>Vagas preenchidas (Técnico + Cliente)</label>
                <div class='controls controls-row'>
                	<label id="vagas_preenchidas"><b><?=pg_fetch_result($res, 0, 'vagas_preenchidas')?></b></label>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='local'>Local</label>
                <div class='controls controls-row'>
                	<label><b><?=pg_fetch_result($res, 0, 'treinamento_local')?></b></label>
                </div>
            </div>
        </div>
    </div>
</form>
<table id="resultado_cliente_treinamento" class='table table-striped table-bordered table-hover table-large table-fixed' >
    <thead>
        <tr class='titulo_tabela' >
                <th colspan="16">Clientes cadastrados no treinamento</th>
        </tr>
        <tr class="titulo_coluna">
            <th>Nome</th>
            <th>CPF</th>
            <th>RG</th>
            <th>Telefone</th>
            <th>E-mail</th>
            <th>Data da Inscrição</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
    	<?php
    	for ($i = 0; $i < $rows; $i++) {
    		$cliente 				= pg_fetch_result($res, $i, 'cliente');
    		$tecnico_nome 			= pg_fetch_result($res, $i, 'tecnico_nome');
    		$tecnico_cpf 			= pg_fetch_result($res, $i, 'tecnico_cpf');
    		$tecnico_rg 			= pg_fetch_result($res, $i, 'tecnico_rg');
    		$tecnico_celular 		= pg_fetch_result($res, $i, 'tecnico_celular');
    		$tecnico_email 			= pg_fetch_result($res, $i, 'tecnico_email');
    		$tecnico_data_inscricao = pg_fetch_result($res, $i, 'tecnico_data_inscricao');
    		$cliente_ativo 			= pg_fetch_result($res, $i, 'cliente_ativo');
    		$treinamento_posto 		= pg_fetch_result($res, $i, 'treinamento_posto');
    	?>
		<tr>
			<td><?=$tecnico_nome?></td>
			<td class="tac"><?=$tecnico_cpf?></td>
			<td class="tac"><?=$tecnico_rg?></td>
			<td class="tac"><?=$tecnico_celular?></td>
			<td><?=$tecnico_email?></td>
			<td class="tac"><?=$tecnico_data_inscricao?></td>
			<td class="tac" width="20%">
				<button class="btn btn-primary btn-small btn-alterar" data-treinamento="<?=$treinamento_id?>" data-cliente="<?=$cliente?>" data-treinamentop="<?=$treinamento_posto?>">Alterar</button>
				<?=($cliente_ativo == 't') ?
					'<button class="btn btn-danger btn-small btn-inativar" data-treinamento="'.$treinamento_id.'" data-cliente="'.$cliente.'" data-treinamentop="'.$treinamento_posto.'">Inativar</button>' :
					'<button class="btn btn-success btn-small btn-confirmar" data-treinamento="'.$treinamento_id.'" data-cliente="'.$cliente.'" data-treinamentop="'.$treinamento_posto.'">Confirmar</button>' ?>
			</td>
		</tr>
    	<?php
    	}
    	?>
    </tbody>
</table>
<div class="row-fluid">
	<div class="span4"></div>
	<div class="span4 tac">
		<button class='btn btn-voltar' type="button">Voltar</button>
	</div>
</div>
<script type="text/javascript">
	$(function(){
		$.dataTableLoad({ table: '#resultado_cliente_treinamento' });

        $('.btn-voltar').on('click', function(){
        	window.open('cadastro_cliente_treinamento.php', '_self');
        });

        $('.btn-alterar').on('click', function(){
        	var treinamento  = $(this).data('treinamento');
        	var cliente 	 = $(this).data('cliente');
        	var treinamentop = $(this).data('treinamentop');

        	window.open('cadastro_cliente_treinamento_form.php?cliente='+cliente+'&id='+treinamento+'&treinamento_posto='+treinamentop+'&atualizar', '_self');
        });

        $(document).on('click', '.btn-inativar, .btn-confirmar', function(){
        	var treinamento  = $(this).data('treinamento');
        	var cliente 	 = $(this).data('cliente');
        	var treinamentop = $(this).data('treinamentop');
        	var btn 		 = $(this);
        	var condicao 	 = (btn.hasClass('btn-inativar')) ? 'false': 'true';

        	$.ajax({
        		url: window.open.href,
        		type: 'POST',
        		data: { ajax: 'sim', action: 'ativa_inativa', treinamento: treinamento, cliente: cliente, treinamentop: treinamentop, ativo: condicao },
        		timeout: 8000
        	}).fail(function(){
        	}).done(function(data){
        		data = JSON.parse(data);
        		if (data.ok !== undefined) {
        			if (btn.hasClass('btn-inativar')){
        				btn.toggleClass('btn-inativar btn-confirmar').toggleClass('btn-danger btn-success').text('Confirmar');
        				$('#vagas_preenchidas').find('b').text(parseInt($('#vagas_preenchidas').find('b').text()) - 1);
        			}else{
        				btn.toggleClass('btn-confirmar btn-inativar').toggleClass('btn-success btn-danger').text('Inativar');
        				$('#vagas_preenchidas').find('b').text(parseInt($('#vagas_preenchidas').find('b').text()) + 1);
        			}
        		}else{
        			alert(data.error);
        		}
        	});
        });
	});
</script>
<?php include 'rodape.php';?>