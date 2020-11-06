<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";
include "funcoes.php";

if (isset($_POST["pesquisar"])) {
	$status       = $_POST["status"];
	$tipo         = $_POST["tipo_interacao"];
	$posto_id     = $_POST["posto_id"];
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];

	if (empty($data_inicial) || empty($data_final)) {
		$msg_erro["msg"][] = "Preencha os Campos obrigatórios";
	}

	try {
		validaData($data_inicial, $data_final, 3);

		list($dia, $mes, $ano) = explode("/", $data_inicial);
		$data_inicial = "$ano-$mes-$dia";

		list($dia, $mes, $ano) = explode("/", $data_final);
		$data_final = "$ano-$mes-$dia";
	} catch(Exception $e) {
		$msg_erro["msg"][] = $e->getMessage();
	}
}


$layout_menu = "callcenter";
$title = "INTERAÇÕES";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "price_format",
   "dataTable"
);

include __DIR__."/plugin_loader.php";
?>
<script>

$(function() {

	$("#data_inicial, #data_final").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	Shadowbox.init();

	$("span.add-on").on("click", function() {
		if ($(this).find("i").hasClass("icon-remove")) {
			$("#posto_codigo").val("").prop({ readonly: false }).next().find("i").removeClass("icon-remove").addClass("icon-search");
			$("#posto_nome").val("").prop({ readonly: false }).next().find("i").removeClass("icon-remove").addClass("icon-search");
			$("#posto_id").val("");
		} else {
			$.lupa($(this));
		}
	});

	$("button[type=submit]").on("click", function() {
		$(this).button("loading");
	});

	$("button.btn-ver-comentarios").on("click", function() {
		var tipo     = $(this).data("tipo");
		var id = $(this).data("id");
		var posto = $(this).data("posto");

		var link = "interacao_os.php?os="+id+"&iframe=true";
		if (tipo == 'PEDIDO') {
			link = "interacoes.php?tipo="+tipo+"&posto="+posto+"&reference_id="+id;
		}
		
		Shadowbox.open({
			content: link,
			player: "iframe",
			width: 850,
			height: 600,
			title: "Interaçoes"
		});
	});
});

function retorna_posto(retorno) {
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo).prop({ readonly: true }).next().find("i").removeClass("icon-search").addClass("icon-remove");
	$("#posto_nome").val(retorno.nome).prop({ readonly: true }).next().find("i").removeClass("icon-search").addClass("icon-remove");
}
</script>

<?php 

if ($telecontrol_distrib && $_POST["gerar_excel"] == "t") {

	$filename = "relatorio-interacoes-". $login_admin . ".csv";

	$fileCsv  = "xls/" . $filename;

	$csv = fopen($fileCsv, "w+");

	fwrite($csv, "Interação em; OS / Pedido Faturado; Posto Autorizado; Abertura da OS / Pedido; Tipo da Interação; Data da Interação; Conteúdo de Interação; Admin; Quantidade de Interações OS's / Faturados \n");

	if ($telecontrol_distrib) {

		$camposOs     = " (select count(1) from tbl_os_interacao where os = e.os and fabrica = {$login_fabrica} AND tbl_os_interacao.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59') as qtde_interacao, ";;

		$camposPedido = " (select count(1) from tbl_interacao where registro_id = e.registro_id and fabrica = $login_fabrica AND tbl_interacao.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59') as qtde_interacao, ";

		if ($_POST["gerar_excel"] == "t") { 

			$camposOs     = " TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY HH24:mm') AS data_abertura, comentario, (select count(1) from tbl_os_interacao where os = e.os and fabrica = {$login_fabrica} AND tbl_os_interacao.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59') as qtde_interacao , interno AS tipo_interacao, tbl_admin.nome_completo AS nome_admin, ";

			$joinCamposOs = " JOIN tbl_os ON (tbl_os.os = e.os) 
							  LEFT JOIN tbl_admin ON (tbl_admin.admin = e.admin) ";

			$camposPedido = " TO_CHAR(tbl_pedido.data,'DD/MM/YYYY HH24:mm') AS data_abertura, comentario, (select count(1) from tbl_interacao where registro_id = e.registro_id and fabrica = $login_fabrica AND tbl_interacao.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59') as qtde_interacao , interno AS tipo_interacao, tbl_admin.nome_completo AS nome_admin, ";

			$joinCamposPedido = " JOIN tbl_pedido ON (tbl_pedido.pedido = e.registro_id)
								  LEFT JOIN tbl_admin ON (tbl_admin.admin = e.admin)  ";
			$groupBy = " , data_abertura, comentario, nome_admin, interno ";
		}
	}

	$campo_sua_os_union = "";
	$campo_sua_os       = "";

	if (in_array($login_fabrica, [11,172])) {
		$campo_sua_os_union = ", (SELECT sua_os FROM tbl_os WHERE tbl_os.os = e.os AND fabrica = $login_fabrica) AS sua_os";
		$campo_sua_os       = ", (SELECT sua_os FROM tbl_os WHERE tbl_os.os = e.registro_id AND fabrica = $login_fabrica) AS sua_os";
	}

	if (empty($_POST['tipo_interacao']) || $_POST['tipo_interacao'] == '1') {

		$unionInteracaoOs = "UNION
							 SELECT 'OS' as descricao,
									e.os as registro_id,
									tbl_posto.nome,
									tbl_posto.posto,
									{$camposOs}
									(
										SELECT to_char(data,'DD/MM/YYYY HH24:mm') 
										FROM tbl_os_interacao i 
										WHERE i.os_interacao = e.os_interacao 
										ORDER BY data DESC limit 1
									) AS data,
									'OS' as contexto_descricao,
									to_char(e.data,'DD/MM/YYYY HH24:mm') as data_interacao
									$campo_sua_os_union
							 FROM tbl_os_interacao e
							 LEFT JOIN tbl_posto ON tbl_posto.posto = e.posto
							 {$joinCamposOs}
							 WHERE e.data BETWEEN '{$data_inicial} 00:00:00' 
							 AND '{$data_final} 23:59:59'
							 AND e.fabrica = {$login_fabrica}
							 {$wherePosto}
							 {$whereStatus}
							 {$whereInterno}
							 ";

	}

	$sqlInteracao = "SELECT DISTINCT
						descricao,
						registro_id,
						tbl_posto.nome,
						tbl_posto.posto,
						{$camposPedido}
						(SELECT to_char(data,'DD/MM/YYYY HH24:mm') FROM tbl_interacao i WHERE i.registro_id = e.registro_id ORDER BY data DESC limit 1 ) AS data,
						tbl_contexto.descricao as contexto_descricao,
						to_char(e.data,'DD/MM/YYYY HH24:mm') as data_interacao
						$campo_sua_os
					FROM tbl_interacao e
						JOIN tbl_contexto USING (contexto)
						LEFT JOIN tbl_posto ON tbl_posto.posto = e.posto
						{$joinCamposPedido}
					WHERE e.data BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' 
						AND e.fabrica = {$login_fabrica}
						$whereInterno
						$wherePosto
						$whereStatus	
						$whereTipo				
					GROUP BY registro_id, nome, descricao, tbl_posto.posto, e.data {$groupBy}
	{$unionInteracaoOs}
					ORDER BY registro_id";
#echo nl2br($sqlInteracao); exit;
	$resInteracao = pg_query($con, $sqlInteracao);

	while ($row = pg_fetch_object($resInteracao)) {

		$corpo = "";

		$interno = 'Interação Externa';

		if ($row->tipo_interacao) {

			$interno = 'Interação Interna';
	
			$com = explode(" ", $comentario);

			if ($com[0] == 'Transferido') {

				$interno = 'Transferência';
			}
		}

		if(strlen($row->nome_admin) == 0){
			$row->nome_admin = 'Posto Autorizado';
		}

		$corpo .= str_replace(',', '', $row->contexto_descricao);
		$corpo .= ";" . str_replace(',', '', $row->registro_id);
		$corpo .= ";" . str_replace(',', '', $row->nome);
		$corpo .= ";" . str_replace(',', '', $row->data_abertura);
		$corpo .= ";" . str_replace(',', '', $interno);
		$corpo .= ";" . str_replace(',', '', $row->data_interacao);
		$corpo .= ";" . str_replace([';',',',"\n", "\r", "      "], '', $row->comentario);
		$corpo .= ";" . str_replace(',', '', $row->nome_admin);
		$corpo .= ";" . str_replace(',', '', $row->qtde_interacao) . "\n";

		fwrite($csv, $corpo);
	}

	fclose($csv);
} 
?>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
	<div class="alert alert-error" >
		<button type="button" class="close" data-dismiss="alert" >&times;</button>
		<strong><?=implode("<br />", $msg_erro['msg'])?></strong>
	</div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form method="POST" role="form" class="tc_formulario" >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>

	<br />
	<div class="row-fluid">
		<div class="span2" ></div>
		<div class="span4">
			<div class="control-group <?=(in_array('data', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class="control-label" for="data_inicial">Data Inicial</label>
				<div class="controls controls-row">
					<div class="span6">
						<h5 class="asteristico">*</h5>
						<input type="text" name="data_inicial" id="data_inicial" class="span12" value= "<?=$_POST['data_inicial']?>" autocomplete="off" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array('data', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class="control-label" for="data_final">Data Final</label>
				<div class="controls controls-row">
					<div class="span6">
						<h5 class="asteristico">*</h5>
						<input type="text" name="data_final" id="data_final" class="span12" value="<?=$_POST['data_final']?>" autocomplete="off" />
					</div>
				</div>
			</div>
		</div>		
	</div>
	<div class="row-fluid">
		<div class="span2" ></div>
		<div class="span4" >
			<div class="control-group <?=(in_array('status', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="status" >Status</label>
				<div class="controls controls-row" >
					<select id="status" name="status" class="span12" >
						<option value=''>Selecione um status</option>
						<option value='pendente' <?= (getValue("status") == 'pendente') ? 'selected' : '' ?>>Leitura Pendente</option>
						<option value='confirmada' <?= (getValue("status") == 'confirmada') ? 'selected' : '' ?>>Leitura confirmada</option>
					</select>
				</div>
			</div>
		</div>
		<div class="span4" >
			<div class="control-group <?=(in_array('status', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="tipo" >Tipo Interação</label>
				<div class="controls controls-row" >
					<?php
						$sqlContexto = "SELECT DISTINCT ON (tbl_interacao.contexto) 
											tbl_contexto.contexto,
											tbl_contexto.descricao
										FROM tbl_interacao
										JOIN tbl_contexto USING(contexto)
										WHERE tbl_contexto.campo IS NOT NULL
										AND tbl_interacao.fabrica = {$login_fabrica}
										ORDER BY tbl_interacao.contexto DESC";
						$resContexto = pg_query($con, $sqlContexto);
					?>
					<select id="status" name="tipo_interacao" class="span12" >
						<option value='' selected >Selecione um tipo de interação</option>
						<option value='1' <?= (getValue("tipo_interacao") == '1') ? "selected" : "" ?>>OS</option>
						<?php
						foreach (pg_fetch_all($resContexto) as $contexto) {
							$selected = (getValue("tipo_interacao") == $contexto['contexto']) ? "selected" : "";

							echo "<option value='{$contexto['contexto']}' {$selected} >{$contexto['descricao']}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span2" ></div>
		<?php
		if (strlen(getValue("posto_id")) > 0) {
			$input_readonly = "readonly='readonly'";
			$i_class = "icon-remove";
		} else {
			$i_class = "icon-search";
		}
		?>
		<div class="span3" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="posto_codigo" >Código do Posto</label>
				<div class="controls controls-row" >
					<div class="input-append">
						<input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />
						<input type="text" id="posto_codigo" name="posto_codigo" class="span10" value="<?=getValue('posto_codigo')?>" <?=$input_readonly?> />
						<span class="add-on" rel="lupa" ><i class="<?=$i_class?>"></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class="span6" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="posto_nome" >Nome do Posto</label>
				<div class="controls controls-row" >
					<div class="input-append">
						<input type="text" id="posto_nome" name="posto_nome" class="span10" value="<?=getValue('posto_nome')?>" <?=$input_readonly?> />
						<span class="add-on" rel="lupa" ><i class="<?=$i_class?>"></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span2" ></div>
		<? if(isFabrica(72) || $telecontrol_distrib) { ?>
		<div class="span4">
			<label class="checkbox">
                <div  style="display:inline-block; position:relative;">
				    <input type="checkbox"  name="gerar_excel" value="t" rel="4"  <?= ($_POST["gerar_excel"] == "t") ? "checked" : ""; ?>>Download de Arquivo em CSV
                    <div class='' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                </div>
			</label>
		</div>
		<? } ?>
	</div>

	<button type="submit" name="pesquisar" class="btn" data-loading-text="Pesquisando..." >Pesquisar</button>

	<br /><br />
</form>
<?php 
if (isset($_POST["pesquisar"]) && empty($msg_erro)) {
	$wherePosto = "";
	$whereStatus = "";
	$whereTipo = "";
	if ($_POST['posto_id'] > 0 ) {
		$wherePosto = " AND tbl_posto.posto = '{$_POST['posto_id']}'";
	}

	
	if (!empty($_POST['status'])) {

		$whereInterno = "AND e.interno IS NOT TRUE";

		if ($_POST['status'] == 'pendente') {
			$whereStatus = " AND e.confirmacao_leitura is null ";
		} else {
			$whereStatus = " AND e.confirmacao_leitura is not null ";
		}

	}

	if (!empty($_POST['tipo_interacao'])) {

		$whereTipo = " AND e.contexto = {$_POST['tipo_interacao']} ";

	}

	$camposOs     = "";
	$joinCamposOs = "";
	$camposPedido = "";
	$joinCamposPedido = "";

	if ($telecontrol_distrib) {

		$camposOs     = " (select count(1) from tbl_os_interacao where os = e.os and fabrica = {$login_fabrica} AND tbl_os_interacao.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59') as qtde_interacao, ";;

		$camposPedido = " (select count(1) from tbl_interacao where registro_id = e.registro_id and fabrica = $login_fabrica AND tbl_interacao.data BETWEEN '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59') as qtde_interacao, ";

	}

	$campo_sua_os_union = "";
	$campo_sua_os       = "";

	if (in_array($login_fabrica, [11,172])) {
		$campo_sua_os_union = ", (SELECT sua_os FROM tbl_os WHERE tbl_os.os = e.os AND fabrica = $login_fabrica) AS sua_os";
		$campo_sua_os       = ", (SELECT sua_os FROM tbl_os WHERE tbl_os.os = e.registro_id AND fabrica = $login_fabrica) AS sua_os";
	}

	if (empty($_POST['tipo_interacao']) || $_POST['tipo_interacao'] == '1') {

		$unionInteracaoOs = "UNION
							 SELECT DISTINCT ON (e.os) 'OS' as descricao,
									e.os as registro_id,
									tbl_posto.nome,
									tbl_posto.posto,
									{$camposOs}
									(
										SELECT to_char(data,'DD/MM/YYYY HH24:mm') 
										FROM tbl_os_interacao i 
										WHERE i.os_interacao = e.os_interacao 
										ORDER BY data DESC limit 1
									) AS data,
									'OS' as contexto_descricao
									$campo_sua_os_union
							 FROM tbl_os_interacao e
							 LEFT JOIN tbl_posto ON tbl_posto.posto = e.posto
							 WHERE e.data BETWEEN '{$data_inicial} 00:00:00' 
							 AND '{$data_final} 23:59:59'
							 AND e.fabrica = {$login_fabrica}
							 {$wherePosto}
							 {$whereStatus}
							 {$whereInterno}
							 ";

	}

	$sqlInteracao = "SELECT DISTINCT
						descricao,
						registro_id,
						tbl_posto.nome,
						tbl_posto.posto,
						{$camposPedido}
						(SELECT to_char(data,'DD/MM/YYYY HH24:mm') FROM tbl_interacao i WHERE i.registro_id = e.registro_id ORDER BY data DESC limit 1 ) AS data,
						tbl_contexto.descricao as contexto_descricao
						$campo_sua_os
					FROM tbl_interacao e
						JOIN tbl_contexto USING (contexto)
						LEFT JOIN tbl_posto ON tbl_posto.posto = e.posto
						{$joinCamposPedido}		
					WHERE e.data BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' 
						AND e.fabrica = {$login_fabrica}
						$whereInterno
						$wherePosto
						$whereStatus	
						$whereTipo				
					GROUP BY registro_id, nome, descricao, tbl_posto.posto
					{$unionInteracaoOs}";
	$resInteracao = pg_query($con, $sqlInteracao);
#echo nl2br($sqlInteracao);
}
if (pg_num_rows($resInteracao) > 0) {
?>
<table style="width: 100%;" class="table table-striped table-bordered resultado">
	<thead>
		<tr class="titulo_coluna">
			<th>Tipo Interação</th>
			<th>OS / Pedido</th>
			<th>Posto Autorizado</th>
			<th>Última Interação</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php 

		$totalInteracoes = 0;
		
		while ($row = pg_fetch_object($resInteracao)) { 

			switch ($row->contexto_descricao) {
				case 'OS':

					$url = "os_press.php?os={$row->registro_id}";

					break;
				case 'PEDIDO':

					$url = "pedido_admin_consulta.php?pedido={$row->registro_id}";

					break;
				default:

					$url = "#";

					break;
			}

			$totalInteracoes += $row->qtde_interacao;
		?>
			<tr>
				<td class="tac"><?=$row->descricao?></td>
				<td class="tac">
				<?php if (in_array($login_fabrica, [11,172]) && !empty($row->sua_os)) { ?>
						<a href="<?= $url ?>" target="_blank">
							<?=$row->sua_os?>
						</a>	
				<?php } else { ?>
					<a href="<?= $url ?>" target="_blank">
						<?=$row->registro_id?>
					</a>
				<?php } ?>
				</td>
				<td class="tac"><?=$row->nome?></td>
				<td class="tac"><?=$row->data?></td>
				<td class="tac" >
					<button 
						type="button" 
						class="btn btn-small btn-info btn-ver-comentarios" 
						data-tipo="<?=$row->descricao?>" 
						data-posto="<?=$row->posto?>" 
						data-id="<?=$row->registro_id?>" >
						<i class="icon-comment icon-white" ></i>
					</button>
				</td>
			</tr>
		<?php
			if($_POST["gerar_excel"] == "t"){
				array_push($dadosExportacao, $row);
			}
		}
		?>
	</tbody>
</table>
<script>
	$.dataTableLoad({
		table: ".resultado"
	});

	<?php if ($telecontrol_distrib) { ?>

       let qtde_interacoes  = <?= $totalInteracoes ?>;
       let conteudo_dt_info = $('.dataTables_info').html() + ' <span style="color:red">com ' + qtde_interacoes + ' interações registradas</span>';
       $('.dataTables_info').html(conteudo_dt_info);

	<?php } ?>
</script>
<?php 
	
	$jsonPOST = excelPostToJson($_POST);
	
	if ($_POST['gerar_excel'] == 't') { ?>

		<br>
		<div id='btn_gerar_csv' class="btn_excel">
			<a href="<?= $fileCsv ?>" download>
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</a>
		</div>

	<?php } ?>	

<? } else { ?>
	<div class="alert alert-error" >
		<button type="button" class="close" data-dismiss="alert" >&times;</button>
		<strong>Nenhum resultado foi encontrado</strong>
	</div>
<?php }
include "rodape.php";
?>
