<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$status_os          = $_POST['status_os'];

	if (count($status_os) > 0) {

		$status_os_pesquisa = implode(", ",$status_os);

		$condStatus = "AND tbl_os.status_checkpoint IN ({$status_os_pesquisa})";

	}

	$cond_pesquisa_produto = "AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";

	$cond_pesquisa_peca = "AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				$cond_pesquisa_produto
				";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE fabrica = {$login_fabrica}
				$cond_pesquisa_peca";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	}

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (!count($msg_erro["msg"])) {

		if (!empty($produto)){
			$cond_produto = " AND tbl_produto.produto = {$produto} ";
		}

		if (!empty($peca)){
			$cond_peca = " AND tbl_pedido_item.peca = {$peca} ";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_pedido.posto = {$posto} ";
		}

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "SELECT DISTINCT ON (tbl_os_produto.os, tbl_pedido.pedido)
					tbl_pedido.pedido,
					tbl_os_produto.os,
					tbl_status_checkpoint.descricao as status_checkpoint_os,
					TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') as dt_abertura,
					tbl_produto.referencia as referencia_produto,
					tbl_produto.descricao as descricao_produto,
					CASE
						WHEN tbl_os.os IS NOT NULL
						THEN coalesce(tbl_os.finalizada::date, current_date) - tbl_os.data_abertura
						ELSE coalesce(tbl_faturamento_correio.data::date, current_date) - tbl_pedido.data::date
					END as dias_estatico
				FROM tbl_pedido
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido
				LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = {$login_fabrica}
				LEFT JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
				LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
				LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_faturamento_correio ON tbl_faturamento_item.faturamento = tbl_faturamento_correio.faturamento 
				AND LOWER(tbl_faturamento_correio.situacao) LIKE 'objeto entregue%' 
				AND tbl_faturamento_correio.fabrica = {$login_fabrica} AND tbl_faturamento_correio.data_input > tbl_faturamento_item.data_input
				WHERE tbl_pedido.fabrica = {$login_fabrica}
				AND tbl_pedido.data::date BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
				AND tbl_pedido.finalizado IS NOT NULL
				AND tbl_pedido.status_pedido != 14
				{$condStatus}
				{$cond_posto}
				{$cond_produto}
				{$cond_peca}
				{$limit}
				";

		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_os_pedido-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS (garantia)</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido (Fora Garantia)</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data do Pedido</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tempo/Dias Estático</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição Produto</th>
						
			";
			
			$cont = 0;
			while ($dadosOs = pg_fetch_object($resSubmit)) {

				if (empty($dadosOs->os)) {
					$pedido_faturado = $dadosOs->pedido;
				} else {
					$pedido_faturado = "";
				}

				$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$dadosOs->os}</td>
							<td nowrap align='center' valign='top'>{$dadosOs->status_checkpoint_os}</td>
							<td nowrap align='center' valign='top'>{$pedido_faturado}</td>
							<td nowrap align='center' valign='top'>{$dadosOs->pedido}</td>
							<td nowrap align='center' valign='top'>{$dadosOs->dt_abertura}</td>
							<td nowrap align='center' valign='top'>{$dadosOs->dias_estatico}</td>
							<td nowrap align='center' valign='top'>{$dadosOs->referencia_produto}</td>
							<td nowrap align='center' valign='top'>{$dadosOs->descricao_produto}</td>";

					$sqlPecasPedido = "SELECT tbl_peca.referencia, tbl_peca.descricao
									   FROM tbl_pedido_item
									   JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
									   AND tbl_peca.fabrica = {$login_fabrica}
									   AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
									   WHERE tbl_pedido_item.pedido = {$dadosOs->pedido}";
					$resPecasPedido = pg_query($con, $sqlPecasPedido);

					while ($dadosPecas = pg_fetch_object($resPecasPedido)) {

						if (pg_num_rows($resPecasPedido) > $cont) {
							$thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peça {$cont}</th>";
							$cont++;
						}
						
						$body .= "<td>{$dadosPecas->referencia} - {$dadosPecas->descricao}</td>";
					}

				$body .="
						</tr>";
			}

			$thead .= "</tr>
					</thead>
					<tbody>";

			fwrite($file, $thead);

			fwrite($file, $body);
			fwrite($file, "
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

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS x Peças";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#status_os").multiselect({
            selectedText: "selecionados # de #"
        });

	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
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

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial (pedido)</label>
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
					<label class='control-label' for='data_final'>Data Final (pedido)</label>
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
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'>Ref. Peças</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'>Descrição Peça</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<?php
					$condicao_status = "0,1,2,3,4,9";

					if ($telecontrol_distrib && !isset($novaTelaOs)) {
					    $condicao_status .= ",35, 36, 37, 39";
					}

					if (in_array($login_fabrica, [81])) {
						$condicao_status .= ",8";
					}

					$sql_status = "SELECT status_checkpoint,descricao FROM tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.")";
                    $res_status = pg_query($con,$sql_status);

					?>
					<label class='control-label' for='codigo_posto'>Status da OS</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<select name="status_os[]" id="status_os" multiple="multiple">
								<?php
								while ($dadosStatus = pg_fetch_object($res_status)) {
									$selected = (in_array($dadosStatus->status_checkpoint, $_POST["status_os"])) ? "selected" : "";
								?>
									<option value="<?= $dadosStatus->status_checkpoint ?>" <?= $selected ?>>
										<?= $dadosStatus->descricao ?>
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
		
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
			<table id="resultado_os" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Garantia (OS)</th>
						<th>Status OS</th>
						<th>Fora Garantia</th>
						<th>Pedido</th>
						<th>Data de Abertura</th>
						<th>Tempo/Dias Estático</th>
						<th>Referência Produto</th>
						<th>Descrição Produto</th>
					</tr>
				</thead>
				<tbody>
				<?php
				while ($dadosOs = pg_fetch_object($resSubmit)) { 

					if (empty($dadosOs->os)) {
						$pedido_faturado = $dadosOs->pedido;
					} else {
						$pedido_faturado = "";
					}

					?>
					<tr>
						<td class="tac">
							<a href="os_press.php?os=<?= $dadosOs->os ?>" target="_blank"><?= $dadosOs->os ?></a>
						</td>
						<td>
							<?= $dadosOs->status_checkpoint_os ?>
						</td>
						<td class="tac"><?= $pedido_faturado ?></td>
						<td class="tac">
							<a href="pedido_admin_consulta.php?pedido=<?= $dadosOs->pedido ?>" target="_blank"><?= $dadosOs->pedido ?></a>
						</td>
						<td class="tac"><?= $dadosOs->dt_abertura ?></td>
						<td class="tac"><?= $dadosOs->dias_estatico ?></td>
						<td><?= $dadosOs->referencia_produto ?></td>
						<td><?= $dadosOs->descricao_produto ?></td>
					</tr>
				<?php
				} ?>
				</tbody>
			</table>

			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os" });
				</script>
			<?php
			}
			?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}



include 'rodape.php';?>
