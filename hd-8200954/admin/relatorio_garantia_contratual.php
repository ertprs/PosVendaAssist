<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

function cliente_contratual() {
	global $con, $login_fabrica;

	$sql = "SELECT grupo_cliente FROM tbl_grupo_cliente
			WHERE fabrica = $login_fabrica AND descricao = 'Garantia Contratual'
			AND ativo IS TRUE";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 0, 'grupo_cliente');
	}

}

if (isset($_POST['btn_pesquisa'])) {
	$codigo_cliente = $_POST['cliente_codigo'];
	$nome_cliente   = pg_escape_string($_POST['cliente_nome']);
	$data_inicial   = $_POST['data_inicial'];
	$data_final     = $_POST['data_final'];
	$status         = pg_escape_string($_POST['status_chamado']);

	$grupo_cliente  = cliente_contratual();

	if (!empty($codigo_cliente)) {
		$sql = "SELECT tbl_cliente.cliente 
				FROM tbl_cliente
				JOIN tbl_grupo_cliente 
				ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente
				WHERE tbl_cliente.grupo_cliente = $grupo_cliente
				AND UPPER(tbl_cliente.codigo_cliente) = UPPER('$codigo_cliente')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$cliente = pg_fetch_result($res, 0, 'cliente');

			$condCliente = "AND tbl_hd_chamado.cliente = $cliente";
		} else {
			$msg_erro['msg'][]    = "Cliente não encontrado";
			$msg_erro['campos'][] = "cliente";
		}
	}

	$xdata_inicial = formata_data($data_inicial);
	$xdata_final   = formata_data($data_final);

	if (empty($xdata_inicial) || empty($xdata_final)) {
		$msg_erro['msg'][]    = "Preencha as datas";
		$msg_erro['campos'][] = "data";
	} else {
		$cond_periodo = " AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
	}

	if (!empty($status)) {
		$condStatus = " AND tbl_hd_chamado.status = '$status'";
	}

	if (count($msg_erro) == 0) {

		$sqlPesquisa = "SELECT 
							tbl_hd_chamado.hd_chamado,
							tbl_hd_chamado.cliente,
							tbl_hd_chamado_extra.os,
							tbl_hd_chamado.data as data_chamado,
							(
								SELECT tbl_hd_chamado_item.data
								FROM tbl_hd_chamado_item
								WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
								AND tbl_hd_chamado_item.status_item = 'Resolvido'
								ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC
								LIMIT 1
							) as data_encerramento,
							tbl_produto.referencia as referencia_produto,
							tbl_produto.descricao as descricao_produto,
							tbl_hd_chamado_extra.serie,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome as nome_posto,
							tbl_cidade.estado,
							tbl_cidade.nome as cidade_nome,
							tbl_cliente.nome as nome_cliente,
							tbl_cliente.codigo_cliente as codigo_cliente,
							tbl_solucao.descricao as descricao_solucao
						FROM tbl_hd_chamado
							JOIN tbl_cliente ON tbl_cliente.cliente = tbl_hd_chamado.cliente AND tbl_cliente.grupo_cliente = $grupo_cliente AND tbl_hd_chamado.fabrica = $login_fabrica 
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
							LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
							LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
							JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
							LEFT JOIN tbl_os ON tbl_hd_chamado_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
							LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os AND tbl_solucao.fabrica = $login_fabrica
						WHERE tbl_hd_chamado.fabrica = $login_fabrica
							  {$cond_periodo}
							  {$condStatus}
							  {$condCliente}
							";

		$resPesquisa = pg_query($con, $sqlPesquisa);

		if ($_POST["gerar_excel"]) {

			$sqlPesquisa = "SELECT 
							tbl_hd_chamado.hd_chamado,
							tbl_hd_chamado.cliente,
							tbl_hd_chamado_extra.os,
							tbl_hd_chamado.data as data_chamado,
							(
								SELECT tbl_hd_chamado_item.data
								FROM tbl_hd_chamado_item
								WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
								AND tbl_hd_chamado_item.status_item = 'Resolvido'
								ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC
								LIMIT 1
							) as data_encerramento,
							tbl_produto.referencia as referencia_produto,
							tbl_produto.descricao as descricao_produto,
							tbl_hd_chamado_extra.serie,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome as nome_posto,
							tbl_cidade.estado,
							tbl_cidade.nome as cidade_nome,
							tbl_cliente.nome as nome_cliente,
							tbl_cliente.codigo_cliente as codigo_cliente,
							tbl_solucao.descricao as descricao_solucao,
							tbl_defeito.descricao AS descricao_defeito,
							tbl_servico_realizado.descricao as descricao_servico,
							tbl_peca.referencia || ' - ' || tbl_peca.descricao as peca
						FROM tbl_hd_chamado
							JOIN tbl_cliente ON tbl_cliente.cliente = tbl_hd_chamado.cliente AND tbl_cliente.grupo_cliente = $grupo_cliente AND tbl_hd_chamado.fabrica = $login_fabrica 
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
							LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
							LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
							JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
							LEFT JOIN tbl_os ON tbl_hd_chamado_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
							LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os AND tbl_solucao.fabrica = $login_fabrica
							LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
							LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
							LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
							LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = {$login_fabrica}
							LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
							AND tbl_servico_realizado.fabrica = {$login_fabrica}
						WHERE tbl_hd_chamado.fabrica = $login_fabrica
							  {$cond_periodo}
							  {$condStatus}
							  {$condCliente}
							";
			$resPesquisa = pg_query($con, $sqlPesquisa);

			if (pg_num_rows($resPesquisa) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_garantia_contratual-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");
				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='16' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO DE ATENDIMENTOS GARANTIA CONTRATUAL
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Cliente</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Cliente</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Protoco</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ordem de Serviço</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura Protocolo</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Encerramento</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência Produto</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição Produto</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Posto</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Solução</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peça</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Serviço Realizado</th>
							</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);

				for ($x=0;$x < pg_num_rows($resPesquisa);$x++) { 
					$cliente_codigo = pg_fetch_result($resPesquisa, $x, 'codigo_cliente');
					$cliente_nome   = pg_fetch_result($resPesquisa, $x, 'nome_cliente');
					$hd_chamado     = pg_fetch_result($resPesquisa, $x, 'hd_chamado');
					$os             = pg_fetch_result($resPesquisa, $x, 'os');
					$data_chamado   = pg_fetch_result($resPesquisa, $x, 'data_chamado');
					$data_encerramento  = pg_fetch_result($resPesquisa, $x, 'data_encerramento');
					$referencia_produto = pg_fetch_result($resPesquisa, $x, 'referencia_produto');
					$descricao_produto  = pg_fetch_result($resPesquisa, $x, 'descricao_produto');
					$serie           = pg_fetch_result($resPesquisa, $x, 'serie');
					$codigo_posto    = pg_fetch_result($resPesquisa, $x, 'codigo_posto');
					$nome_posto      = pg_fetch_result($resPesquisa, $x, 'nome_posto');
					$cidade_nome          = pg_fetch_result($resPesquisa, $x, 'cidade_nome');
					$estado          = pg_fetch_result($resPesquisa, $x, 'estado');
					$descricao_solucao = pg_fetch_result($resPesquisa, $x, 'descricao_solucao');
					$tem_pecas = pg_fetch_result($resPesquisa, $x, 'tem_pecas');
					$peca = pg_fetch_result($resPesquisa, $x, 'peca');
					$defeito = pg_fetch_result($resPesquisa, $x, 'descricao_defeito');
					$servico = pg_fetch_result($resPesquisa, $x, 'descricao_servico');

						$body .="
								<tr>
									<td nowrap valign='top'>{$cliente_codigo}</td>
									<td nowrap valign='top'>{$cliente_nome}</td>
									<td nowrap align='center' valign='top'>{$hd_chamado}</td>
									<td nowrap align='center' valign='top'>$os</td>
									<td nowrap align='center' valign='top'>".mostra_data($data_chamado)."</td>
									<td nowrap align='center' valign='top'>".mostra_data($data_encerramento)."</td>
									<td nowrap valign='top'>{$referencia_produto}</td>
									<td nowrap valign='top'>{$descricao_produto}</td>
									<td nowrap align='center' valign='top'>{$serie}</td>
									<td nowrap valign='top'>$codigo_posto</td>
									<td nowrap valign='top'>{$cidade_nome}</td>
									<td nowrap valign='top'>{$estado}</td>
									<td nowrap valign='top'>{$descricao_solucao}</td>
									<td nowrap align='center' valign='top'>{$peca}</td>
									<td nowrap align='center' valign='top'>{$defeito}</td>
									<td nowrap valign='top'>{$servico}</td>
								</tr>";
				}


				fwrite($file, $body);
				fwrite($file, "
							<tr>
								<th colspan='16' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
							</tr>
						</tbody>
					</table>
				");

				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
			}

			exit;
		}

	}
}

$layout_menu = "callcenter";
$title = "Relatório Garantia Contratual";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>
<script>
	$(function() {

		Shadowbox.init();

		$.datepickerLoad(Array("data_final", "data_inicial"));

		$("span[rel=lupa]").click(function () {
			var attrAdicionais = ["contratual"];

			$.lupa($(this), attrAdicionais);
		});

		$(".exibe_pecas").click(function(){
			var os = $(this).data("os");

			Shadowbox.open({
                content: "exibe_pecas_os_contratual.php?os="+os,
                player: "iframe",
                width: 900,
                height: 450
            });
		});

	});

	function retorna_cliente(retorno){
		$("#cliente_codigo").val(retorno.codigo_cliente);
		$("#cliente_nome").val(retorno.nome);
    }

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<?php
if (count($msg_success["msg"]) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Relatório de Clientes Contratuais</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("cliente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_codigo'>Código Cliente</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="cliente_codigo" name="cliente_codigo" class='span12' maxlength="20" value="<? echo $cliente_codigo ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="cliente" parametro="codigo" contratual="t" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("cliente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_nome'>Nome Cliente</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="cliente_nome" name="cliente_nome" class='span12' value="<? echo $cliente_nome ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="cliente" parametro="nome" contratual="t" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("status_chamado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='status_chamado'>Status</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="status_chamado" id="status_chamado">
							<option value=""></option>
							<?php
							$sql = "SELECT status
									FROM tbl_hd_status
									WHERE fabrica = $login_fabrica";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_status = ( isset($status_chamado) and ($status_chamado == $key['status']) ) ? "SELECTED" : '' ;

							?>
								<option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >

									<?php echo $key['status']?>

								</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<br />
	<button class="btn" name="btn_pesquisa">Pesquisar</button>
	<br /><br />
</form>
</div>
<?php
	if (isset($_POST['btn_pesquisa'])) {

		if (pg_num_rows($resPesquisa) > 0) {
		?>
		<br />
		<table id="resultado_cliente" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr>
					<th class="titulo_tabela" colspan="15">Lista de Clientes Garantia Contratual</th>
				</tr>
				<tr class='titulo_coluna' >
					<th>Código Cliente</th>
					<th>Nome Cliente</th>
					<th>Protocolo</th>
		            <th>Ordem de Serviço</th>
		            <th>Abertura Protocolo</th>
		            <th>Encerramento</th>
		            <th>Referência Produto</th>
		            <th>Descrição Produto</th>
		            <th>Série</th>
		            <th>Código Posto</th>
		            <th>Nome Posto</th>
		            <th>Cidade</th>
		            <th>Estado</th>
		            <th>Solução</th>
		            <th>Peças</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($x=0;$x < pg_num_rows($resPesquisa);$x++) { 
					$cliente_codigo = pg_fetch_result($resPesquisa, $x, 'codigo_cliente');
					$cliente_nome   = pg_fetch_result($resPesquisa, $x, 'nome_cliente');
					$hd_chamado     = pg_fetch_result($resPesquisa, $x, 'hd_chamado');
					$os             = pg_fetch_result($resPesquisa, $x, 'os');
					$data_chamado   = pg_fetch_result($resPesquisa, $x, 'data_chamado');
					$data_encerramento  = pg_fetch_result($resPesquisa, $x, 'data_encerramento');
					$referencia_produto = pg_fetch_result($resPesquisa, $x, 'referencia_produto');
					$descricao_produto  = pg_fetch_result($resPesquisa, $x, 'descricao_produto');
					$serie           = pg_fetch_result($resPesquisa, $x, 'serie');
					$codigo_posto    = pg_fetch_result($resPesquisa, $x, 'codigo_posto');
					$nome_posto      = pg_fetch_result($resPesquisa, $x, 'nome_posto');
					$cidade_nome          = pg_fetch_result($resPesquisa, $x, 'cidade_nome');
					$estado          = pg_fetch_result($resPesquisa, $x, 'estado');
					$descricao_solucao = pg_fetch_result($resPesquisa, $x, 'descricao_solucao');
					$tem_pecas = pg_fetch_result($resPesquisa, $x, 'tem_pecas');
					$descricao_solucao = pg_fetch_result($resPesquisa, $x, 'descricao_solucao');
				?>
					<tr>
						<td class="tac">
							<?= $cliente_codigo ?>
						</td>
						<td>
							<?= $cliente_nome ?>
						</td>
						<td class="tac"><a href="callcenter_interativo_new.php?callcenter=<?= $hd_chamado ?>" target="_blank"><?= $hd_chamado ?></a></td>
						<td class="tac"><a href="os_press.php?os=<?= $os ?>" target="_blank"><?= $os ?></a></td>
						<td class="tac"><?= mostra_data_hora($data_chamado) ?></td>
						<td class="tac"><?= mostra_data_hora($data_encerramento) ?></td>
						<td>
							<?= $referencia_produto ?>
						</td>
						<td>
							<?= $descricao_produto ?>
						</td>
						<td class="tac">
							<?= $serie ?>
						</td>
						<td>
							<?= $codigo_posto ?>
						</td>
						<td>
							<?= $nome_posto ?>
						</td>
						<td>
							<?= $cidade_nome ?>
						</td>
						<td class="tac">
							<?= $estado ?>
						</td>
						<td class="tac">
							<?= $descricao_solucao ?>
						</td>
						<td class="tac">
							<?php

							$sqlPecas = "SELECT tbl_os_item.os_item 
										FROM tbl_os
										JOIN tbl_os_produto ON tbl_os_produto.os = $os
										JOIN tbl_os_item USING(os_produto)
										WHERE tbl_os.os = $os";
							$resPecas = pg_query($con, $sqlPecas);

							if (pg_num_rows($resPecas) > 0) { ?>
								<button class="exibe_pecas btn btn-primary" data-os="<?= $os ?>">Peças</button>
							<?php 
							} ?>
						</td>
					</tr>
				<?php
				} ?>
			</tbody>
		</table>
		<br />

		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>

		<script>
			$.dataTableLoad({ table: "#resultado_cliente" });
		</script>

	<?php
	} else { ?>
		<div class="alert alert-warning">
			<h4>Nenhum resultado encontrado</h4>
		</div>
	<?php
	}
}

include "rodape.php";
?>
                              