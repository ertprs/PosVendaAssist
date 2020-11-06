<?php
$admin_privilegios = "info_tecnica";
$layout_menu 	   = "tecnica";
$title 			   = "Cadastro de Cliente ao Treinamento";
$plugins 		   = array("dataTable");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';

$sql = "SELECT
           tbl_treinamento.treinamento AS treinamento_id,
           tbl_treinamento.titulo AS treinamento_titulo,
           TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS treinamento_data_incio,
           TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY') AS treinamento_data_fim,
           tbl_linha.nome AS linha_nome,
           tbl_treinamento.vagas AS treinamento_vagas,
           tbl_treinamento.local AS treinamento_local,
           tbl_treinamento.vagas,
           (SELECT count(*) FROM tbl_treinamento_posto WHERE ativo IS TRUE AND treinamento = tbl_treinamento.treinamento) AS vagas_preenchidas
        FROM tbl_treinamento
            LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_treinamento.linha
        WHERE
            tbl_treinamento.fabrica = {$login_fabrica}
            AND tbl_treinamento.ativo IS TRUE
        ORDER BY
           tbl_treinamento.data_inicio DESC";

$res  = pg_query($con, $sql);
$rows = pg_num_rows($res);

if ($rows == 0) {
	echo "<div class='alert alert-warning'><h4>Nenhum registro encontrado</h4></div>";
	include 'rodape.php';
	exit;
}
?>
<table id="resultado_cliente_treinamento" class='table table-striped table-bordered table-hover table-large table-fixed'>
	<thead>
        <tr class='titulo_tabela' >
            <th colspan="7">Treinamentos Ativos</th>
        </tr>
        <tr class="titulo_coluna">
            <th>Título</th>
            <th>Data de Início</th>
            <th>Data de Fim</th>
            <th>Linha</th>
            <th>Total de Vagas / Preenchidas(Técnico e Cliente)</th>
            <th>Local</th>
            <th>Inscrições</th>
        </tr>
    </thead>
    <tbody>
    	<?php
    	for ($i = 0; $i < $rows; $i++) {
            $treinamento_id         = pg_fetch_result($res, $i, 'treinamento_id');
            $treinamento_titulo     = pg_fetch_result($res, $i, 'treinamento_titulo');
            $treinamento_data_incio = pg_fetch_result($res, $i, 'treinamento_data_incio');
            $treinamento_data_fim   = pg_fetch_result($res, $i, 'treinamento_data_fim');
            $linha_nome             = pg_fetch_result($res, $i, 'linha_nome');
            $treinamento_vagas      = pg_fetch_result($res, $i, 'vagas');
            $vagas_preenchidas      = pg_fetch_result($res, $i, 'vagas_preenchidas');
            $treinamento_local      = pg_fetch_result($res, $i, 'treinamento_local');
            $treinamento_situacao   = pg_fetch_result($res, $i, 'treinamento_situacao');
		?>
			<tr>
	            <td>
	            	<?=$treinamento_titulo?>
	            </td>
	            <td class='tac'><?=$treinamento_data_incio?></td>
	            <td class='tac'><?=$treinamento_data_fim?></td>
	            <td><?=$linha_nome?></td>
	            <td class='tac'>
	            	<?=str_pad($treinamento_vagas, 3, '0', STR_PAD_LEFT)."/".str_pad($vagas_preenchidas, 3, '0', STR_PAD_LEFT)?>
	            	</td>
	            <td><?=$treinamento_local?></td>
	            <td class="tac" width="20%">
	            	<?php
	            		$disabled  = ($treinamento_vagas - $vagas_preenchidas <= 0) ? 'disabled' : '';
	            		$disabled2 = ($vagas_preenchidas <= 0) ? 'disabled' : '';
	            	?>
	            	<button class="btn btn-success btn-small btn-adiciona" data-id='<?=$treinamento_id?>' <?=$disabled?>>+</button>
	            	<button class="btn btn-primary btn-small btn-visualiza" data-id='<?=$treinamento_id?>' <?=$disabled2?>>Visualizar</button>
	            </td>
        	</tr>
		<?php } ?>
    </tbody>
</table>
<script type="text/javascript">
	$(function(){
		$.dataTableLoad({ table: '#resultado_cliente_treinamento' });

		$('.btn-visualiza').on('click', function(){
			var id = $(this).data('id');
			window.open('cadastro_cliente_treinamento_inscritos.php?id='+id, '_self');
		});
		$('.btn-adiciona').on('click', function(){
			var id = $(this).data('id');
			window.open('cadastro_cliente_treinamento_form.php?treinamento_id='+id, '_self');
		});
	});
</script>
<?php include 'rodape.php'; ?>
