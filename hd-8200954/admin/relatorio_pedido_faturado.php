<?php
/*HD - 3594930*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];

	/*Buscando o posto*/
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

	/*Validações do formulário*/
	if (!strlen($data_inicial) && !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios.";
		$msg_erro["campos"][] = "data";
	}else{
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
			$aux_data_final   = "{$yf}-{$mf}-{$df} 23:59:59";


			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}

			if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -6 month')) { 
                $msg_erro["msg"][]    = "Período não pode ser maior que 6 meses";
				$msg_erro["campos"][] = "data";
            }
		}
	} /*Fim das validações do formulário*/

	if (!empty($posto)) {
		$cond_posto = " AND tbl_posto_fabrica.posto = {$posto} ";
	}
	
	$sql = "	SELECT tbl_posto.posto as posto_id,
				tbl_posto.cnpj as posto_cnpj,
				tbl_posto.nome as posto_razao,
				0 AS pedido_qtde_pedidos,
				0 AS pedido_qtde_pecas,
				0 as pedido_soma_total_ipi
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_pedido ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_pedido.fabrica = $login_fabrica
			AND tbl_pedido.data BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			$cond_posto
			AND tbl_pedido.pedido IS NULL
			UNION
			SELECT tbl_posto.posto as posto_id,
			  tbl_posto.cnpj as posto_cnpj,
			  tbl_posto.nome as posto_razao,
			  COUNT(DISTINCT p.pedido) AS pedido_qtde_pedidos,
			  SUM(tbl_pedido_item.qtde) AS pedido_qtde_pecas,
			  SUM(((tbl_pedido_item.qtde * tbl_pedido_item.preco) * (1 + (tbl_faturamento_item.aliq_ipi / 100)))) as pedido_soma_total_ipi
			FROM
			  tbl_pedido p
			  JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = p.tipo_pedido
			  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = p.posto AND tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			  JOIN tbl_pedido_item ON tbl_pedido_item.pedido = p.pedido
			  JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
			WHERE
			  p.data BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
			  AND p.fabrica = $login_fabrica
			  AND tbl_tipo_pedido.pedido_faturado IS TRUE
			  AND p.status_pedido <> 14
			  {$cond_posto}
			GROUP BY
			  tbl_posto.posto,
			  tbl_posto.cnpj,
			  tbl_posto.nome,
			  p.posto;
	";
	$resSubmit = pg_query($con, $sql);

	/*Gerar Excel*/
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");
	        $fileName = "relatorio_pedidos_faturados-{$data}.xls";
	        $file = fopen("/tmp/{$fileName}", "w");

	        $thead = "
	        <table border='1'>
			<thead>
				<tr>
					<th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
						RELATÓRIO DE PEDIDOS FATURADOS
					</th>
				</tr>
				<tr>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Código</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Posto</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Qtde Pedidos</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Qtde Peças</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Valor Total Pedidos R$</th>
				</tr>
			</thead>
	        ";

	        fwrite($file, $thead);
	        $tbody = "";

	        $total_pedido_soma_total_ipi = 0;
			$total_pedido_qtde_pedidos = 0;
			$total_pedido_qtde_pecas = 0;

	        for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
	        	$posto_cnpj                  = pg_fetch_result($resSubmit, $i, 'posto_cnpj');
				$posto_razao                 = pg_fetch_result($resSubmit, $i, 'posto_razao');
				$pedido_soma_total_ipi       = pg_fetch_result($resSubmit, $i, 'pedido_soma_total_ipi');
				$pedido_qtde_pedidos         = pg_fetch_result($resSubmit, $i, 'pedido_qtde_pedidos');
				$pedido_qtde_pecas = pg_fetch_result($resSubmit, $i, 'pedido_qtde_pecas');

				$total_pedido_soma_total_ipi += $pedido_soma_total_ipi;
				$total_pedido_qtde_pedidos += $pedido_qtde_pedidos;
				$total_pedido_qtde_pecas += $pedido_qtde_pecas;

				$tbody .= "
					<tr>
					<td nowrap align='left'>{$posto_cnpj}</td>
					<td nowrap align='left'>{$posto_razao}</td>
					<td nowrap align='center'>{$pedido_qtde_pedidos}</td>
					<td nowrap align='center'>{$pedido_qtde_pecas}</td>
					<td nowrap align='right'>". number_format($pedido_soma_total_ipi,2,",",".") . "</td>
					</tr>
				";
	        }

	        $tbody .= "
	        	<tr>
					<th colspan='2' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' align='right'>Total</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>{$total_pedido_qtde_pedidos}</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>{$total_pedido_qtde_pecas}</th>
					<th olspan='1' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>R$ ". number_format($total_pedido_soma_total_ipi,2,",",".") . "</th>
				</tr>
	        ";

	        fwrite($file, $tbody);
	        fwrite($file, "
					<tr>
						<th colspan='5' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
					</tr>
				</tbody>
			</table>
			");

			fclose($file);

			if(file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}
		}

		exit;
	}
}

$layout_menu = "gerencia";
$title = "Relatorio de Pedidos Faturados";

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

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		var table = new Object();
        table['table'] = '#resultado_pedidos_faturados';
        table['type'] = 'full';
        $.dataTableLoad(table);
	});

	function pedido_faturado_detalhe(data_inicial,data_final,posto){
		Shadowbox.open({
            content : "relatorio_pedido_faturado_detalhe.php?data_inicial="+data_inicial+"&data_final="+data_final+"&posto="+posto,
            player: 'iframe'
        });
	}

	function retorna_posto(retorno){
	    $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>
<?
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

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

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
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
		?>
		<br>
		<div class='container'>
	        <div class="alert">
	            <h4>Período de <?echo"$data_inicial";?> até <?echo"$data_final";?></h4>
	        </div>  
    	</div>
		<br>
		<?
		if (pg_num_rows($resSubmit) > 500) {
			$count = 500;
			?>
			<div id='registro_max'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<?php
		} else {
			$count = pg_num_rows($resSubmit);
		} ?>

	<table id="resultado_pedidos_faturados" class="table table-striped table-bordered table-large">
		<thead>
			<tr class="titulo_coluna">
				<th class="tac">Código</th>
				<th class="tac">Posto</th>
				<th class="tac">Qtde Pedidos</th>
				<th class="tac">Qtde Peças</th>
				<th class="tac">Valor Total Pedidos R$</th>
			</tr>
		</thead>
		<tbody>
			<?
				$total_pedido_soma_total_ipi = 0;
				$total_pedido_qtde_pedidos = 0;
				$total_pedido_qtde_pecas = 0;

				for ($i = 0; $i < $count; $i++) {
					$posto_cnpj                  = pg_fetch_result($resSubmit, $i, 'posto_cnpj');
					$posto_id                    = pg_fetch_result($resSubmit, $i, 'posto_id');
					$posto_razao                 = pg_fetch_result($resSubmit, $i, 'posto_razao');
					$pedido_soma_total_ipi       = pg_fetch_result($resSubmit, $i, 'pedido_soma_total_ipi');
					$pedido_qtde_pedidos         = pg_fetch_result($resSubmit, $i, 'pedido_qtde_pedidos');
					$pedido_qtde_pecas = pg_fetch_result($resSubmit, $i, 'pedido_qtde_pecas');

					$total_pedido_soma_total_ipi += $pedido_soma_total_ipi;
					$total_pedido_qtde_pedidos += $pedido_qtde_pedidos;
					$total_pedido_qtde_pecas += $pedido_qtde_pecas;
					?>
					<tr>
						<td class='tac'>
							<a href="javascript: pedido_faturado_detalhe('<?=$data_inicial;?>','<?=$data_final;?>',<?=$posto_id;?>);" ><?=$posto_cnpj;?></a>
						</td>
						<td class='tal'><?=$posto_razao;?></td>
						<td class='tac'><?=$pedido_qtde_pedidos;?></td>
						<td class='tac'><?=$pedido_qtde_pecas;?></td>
						<td class='tar'>R$ <?=number_format($pedido_soma_total_ipi,2,",",".");?></td>
					</tr>
				<?php
				} ?>
		</tbody>
		<tfoot>
			<tr class="titulo_coluna">
				<td colspan="2" class="tar">Total</td>
				<td class="tac"><?=$total_pedido_qtde_pedidos?></td>
				<td class="tac"><?=$total_pedido_qtde_pecas?></td>
				<td class="tar">R$ <?=number_format($total_pedido_soma_total_ipi,2,",",".")?></td>
			</tr>
		</tfoot>
	</table>
<? 
	$achou = "sim";

	$jsonPOST = excelPostToJson($_POST);
?>
	<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    <div id='gerar_excel' class="btn_excel">
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>

<?	} else {
		$achou = "nao";
	}

	if($achou == "nao"){ ?>
		<div class='container'>
	        <div class="alert">
	            <h4>Nenhum resultado encontrado.</h4>
	        </div>  
    	</div>
	<?
	}
}

include "rodape.php"; 
?>
