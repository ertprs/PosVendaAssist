<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

$btn_acao = "";

if(isset($_POST["btn_acao"])){
	$btn_acao = $_POST["btn_acao"];
}

if($btn_acao == "pesquisar"){
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];
	$posto_codigo = $_POST["posto"]["codigo"];
	$posto_nome   = $_POST["posto"]["nome"];
	$condicao     = "";

	if (empty($os_pesquisa) && $btn_listar_auditoria == false && (empty($data_inicial) || empty($data_final))) {
		$msg_erro['msg']["obg"] = "Preencha os campos obrigatórios";
		$msg_erro['campos'][]   = "data_inicial";
		$msg_erro['campos'][]   = "data_final";
	}

	try {
		validaData($data_inicial, $data_final, 3);

		list($dia, $mes, $ano) = explode("/", $data_inicial);
        $aux_data_inicial      = $ano."-".$mes."-".$dia;

        list($dia, $mes, $ano) = explode("/", $data_final);
        $aux_data_final        = $ano."-".$mes."-".$dia;

        $condicao = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	} catch (Exception $e) {
		$msg_erro["msg"][] = $e->getMessage();
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	}

	if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}')) OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$posto_nome}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]   .= "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
			$condicao .= " AND tbl_pedido.posto = $posto";
		}
	}

	if(count($msg_erro["msg"]) == 0){
		$sql = "SELECT 
				tbl_pedido.pedido,
				to_char(tbl_pedido.data, 'DD/MM/YYYY') AS data_pedido,
				tbl_posto.posto,
				tbl_posto.nome AS nome_posto,
				tbl_posto_fabrica.codigo_posto,
				tbl_peca.referencia AS peca_referencia,
				tbl_peca.descricao AS peca_descricao,
				tbl_pedido_item.qtde_cancelada
			FROM tbl_pedido
				INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_faturado IS TRUE AND tbl_tipo_pedido.fabrica = {$login_fabrica}
				INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}				
				INNER JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.pedido = tbl_pedido.pedido AND tbl_pedido_cancelado.fabrica = {$login_fabrica} AND tbl_pedido_cancelado.posto = tbl_pedido.posto AND tbl_pedido_cancelado.peca = tbl_pedido_item.peca
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			WHERE tbl_pedido_item.qtde_cancelada > 0 AND tbl_pedido.fabrica = $login_fabrica $condicao
			ORDER BY tbl_posto.posto, tbl_pedido.data";
		$resPedido = pg_query($con,$sql);
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO PEÇA CANCELADA DE PEDIDO FATURADO";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "price_format",
   "select2"
);

include __DIR__."/plugin_loader.php";

?>
<script type="text/javascript">
$(function() {
	$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	Shadowbox.init();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("#btn_pesquisa").on("click",function(){
		$("#btn_acao").val("pesquisar");
	});

	$("button[id^=btn_peca_]").on("click",function(){
		var linha = this.id.replace(/\D/g, "");
		var status_tabela_peca = $("#status_tabela_peca_"+linha).val();

		if(status_tabela_peca == "hidden"){
			$("#tabela_peca_"+linha).show();
			$("#status_tabela_peca_"+linha).val("show");
			$("#btn_peca_"+linha).removeClass("btn-primary");
			$("#btn_peca_"+linha).addClass("btn-danger");
			$("#btn_peca_"+linha).text("Fechar");
		}else{
			$("#tabela_peca_"+linha).hide();
			$("#status_tabela_peca_"+linha).val("hidden");
			$("#btn_peca_"+linha).removeClass("btn-danger");
			$("#btn_peca_"+linha).addClass("btn-primary");
			$("#btn_peca_"+linha).text("Peça");
		}
	});
});

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
	$("#div_trocar_posto").show();
}

</script>
<?php
if (count($msg_erro['msg']) > 0) { 
	?>
	<br/>
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
	<br/>
<?php 
}
?>

<form name="frm_relatorio_peca_cancelada_pedido"  class='form-search form-inline tc_formulario' method="POST" action="<?=$PHP_SELF?>" align='center'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_inicial">Data Inicial</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=$data_inicial?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_final">Data Final</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_final" name="data_final" class="span12" type="text" value="<?=$data_final?>" />
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
			<div class="span2">
				<div class='control-group' >
						<label class="control-label" for="posto_codigo">Código do Posto</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
			</div>

			<div class="span4">
				<div class='control-group' >
						<label class="control-label" for="posto_nome">Nome do Posto</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
			</div>
			<div class='span1'></div>
		</div>
		<p>
			<button class='btn' id="btn_pesquisa" type="submit">Pesquisar</button>
			<input type="hidden" id="btn_acao" name="btn_acao" value=""/>
		</p>
		<br />
</form>
</br>
<?php
if(count($msg_erro["msg"]) == 0 && $btn_acao == "pesquisar"){
	if(pg_num_rows($resPedido) > 0){
		include_once 'relatorio_peca_cancelada_pedido_excel.php';
	?>
	<table id="tabela_pedido" class="table table-bordered table-striped table-fixed">
		<thead>
			<tr class="titulo_coluna" >
				<th colspan="7" >Peças Canceladas</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Pedido</th>
				<th>Data do Pedido</th>
				<th>Código Posto</th>
				<th>Nome Posto</th>
				<th>Peça</th>
			</tr>
		</thead>
		<tbody>
			<?
			$count = pg_num_rows($resPedido);
			$pedido_antigo = 0;

			for($i=0; $i<$count; $i++){
				$posto        = pg_fetch_result($resPedido, $i, "posto");
				$codigo_posto = pg_fetch_result($resPedido, $i, "codigo_posto");
				$nome_posto   = pg_fetch_result($resPedido, $i, "nome_posto");
				$pedido       = pg_fetch_result($resPedido, $i, "pedido");
				$data_pedido  = pg_fetch_result($resPedido, $i, "data_pedido");

				// if($pedido_antigo != $pedido){
					$pedido_antigo = $pedido;
					?>
					<tr>
						<td class="tac"><?=$pedido?></td>
						<td class="tac"><?=$data_pedido?></td>
						<td class="tac"><?=$codigo_posto?></td>
						<td class="tac"><?=$nome_posto?></td>
						<td class="tac">
							<input type="hidden" id="pedido_<?=$i?>" value="<?=$pedido?>"/>
							<input type="hidden" id="status_tabela_peca_<?=$i?>" value="hidden"/>
							<button type="button" class="btn btn-primary btn-mini" id="btn_peca_<?=$i?>">Peça</button>
						</td>
					</tr>
					<tr id="tabela_peca_<?=$i?>" style="display:none">
						<td colspan="5">
						<table id="tabela_peca_cancelada_<?=$i?>" class="table table-bordered table-striped">
							<thead>
								<tr class="titulo_coluna">
									<th>Referência Peça</th>
									<th>Descrição Peça</th>
									<th>Quantidade Cancelada</th>
								</tr>
							</thead>
							<tbody>
					<?php
					while($pedido_antigo == pg_fetch_result($resPedido, $i, "pedido")){
						$peca_referencia = pg_fetch_result($resPedido, $i, "peca_referencia");
						$peca_descricao  = pg_fetch_result($resPedido, $i, "peca_descricao");
						$qtde_cancelada  = pg_fetch_result($resPedido, $i, "qtde_cancelada");
						?>
								<tr>
									<td class="tac"><?=$peca_referencia?></td>
									<td class="tac"><?=$peca_descricao?></td>
									<td class="tac"><?=$qtde_cancelada?></td>
								</tr>
						<?php
						if($pedido_antigo == pg_fetch_result($resPedido, $i+1, "pedido")){
							$i++;
						}else{
							$pedido_antigo = 0;
						}
					}
				// }
				?>
							</tbody>
						</table>
						</td>
					</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
	}
}
include "rodape.php";
?>