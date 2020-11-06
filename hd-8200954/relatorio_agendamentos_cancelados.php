<?
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

$layout_menu = ($areaAdmin) ? 'callcenter' : 'os';

$title = traduz("CADASTRO DE ORDEM DE SERVIÇO DE REVENDA");

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];

	if (!empty($data_inicial) OR !empty($data_final)){
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
	if ($areaAdmin === true){
		if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
			$sql = "
				SELECT tbl_posto_fabrica.posto
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
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($aux_data_inicial) AND !empty($data_final)){
			$cond_data_abertura = "AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
		}	

		if ($areaAdmin === true AND !empty($posto)){
			$cond_posto = "AND tbl_os.posto = $posto";
		}
	}
}

$sql = "
	SELECT
		os,
		sua_os,
		data_abertura,
		referencia_produto,
		descricao_produto,
		nome_tecnico,
		data_cancelado,
		nome_posto,
		consumidor_nome
	FROM (
		SELECT DISTINCT ON (tbl_os.os)
			tbl_os.os,
			tbl_os.sua_os,
			TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
			tbl_os.consumidor_nome,
			tbl_posto.nome AS nome_posto,
			tbl_produto.referencia AS referencia_produto,
			tbl_produto.descricao AS descricao_produto,
			x.tecnico_agenda,
			x.data_cancelado,
			x.nome_tecnico
		FROM tbl_os
		JOIN (
			SELECT
				tbl_tecnico_agenda.tecnico_agenda,
				tbl_tecnico_agenda.os,
				tbl_tecnico.nome AS nome_tecnico,
				TO_CHAR(tbl_tecnico_agenda.data_cancelado, 'DD/MM/YYYY') AS data_cancelado
			FROM tbl_tecnico_agenda
			JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico
			WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
			ORDER BY tbl_tecnico_agenda.tecnico_agenda DESC
		) x ON x.os = tbl_os.os
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
		WHERE tbl_os.fabrica = $login_fabrica
		$cond_data_abertura
		$cond_posto
		ORDER BY tbl_os.os, x.tecnico_agenda DESC
	) xx
	WHERE data_cancelado IS NOT NULL";
$res = pg_query($con, $sql);

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet"
);

include __DIR__.'/admin/plugin_loader.php'; 

?>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<form name="frm_os_revenda" id="frm_os_revenda" method="POST" class="form-search form-inline tc_formulario" enctype="multipart/form-data" >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
    <div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span4'>
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
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php if ($areaAdmin === true){ ?>
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
	<?php } ?>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>
<?php
if (pg_num_rows($res) > 0) {
	echo "<br />";
?>
<div class="container-fluid">
	<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr>
				<th colspan="8" class='titulo_tabela'>OSs com agendamento cancelados</th>
			</tr>
			<tr class='titulo_coluna' >
				<th>OS</th>
				<th>Data abertura</th>
				<th>Produto</th>
                <th>Técnico</th>
				<th>Data cancelado</th>
				<th>Posto</th>
				<th>Consumidor</th>
			</tr>
		</thead>
		<tbody>
			<?php
			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$os                 = pg_fetch_result($res, $i, 'os');
				$sua_os             = pg_fetch_result($res, $i, 'sua_os');
				$data_abertura      = pg_fetch_result($res, $i, 'data_abertura');
				$referencia_produto = pg_fetch_result($res, $i, 'referencia_produto');
				$descricao_produto  = pg_fetch_result($res, $i, 'descricao_produto');
				$nome_tecnico      	= pg_fetch_result($res, $i, 'nome_tecnico');
				$data_cancelado     = pg_fetch_result($res, $i, 'data_cancelado');
				$nome_posto    		= pg_fetch_result($res, $i, 'nome_posto');
				$consumidor_nome    = pg_fetch_result($res, $i, 'consumidor_nome');
			?>
			<tr>
				<td class='tac'><a target="_blank" href="os_press.php?os=<?=$os?>"><?=$sua_os?></a></td>
				<td class='tac'><?=$data_abertura?></td>
				<td><?=$referencia_produto?> - <?=$descricao_produto?></td>
				<td><?=$nome_tecnico?></td>
				<td class='tac'><?=$data_cancelado?></td>
				<td><?=$nome_posto?></td>
				<td><?=$consumidor_nome?></td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>
	<script>
		$.dataTableLoad({ table: "#resultado_os_atendimento" });
	</script>
	<br />
<?php
}else{
	echo '
	<div class="container">
	<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
	</div>
	</div>';
}

?>

<script type="text/javascript">
$(function() {
    /**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
});
function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}
</script>

<? include "rodape.php"; ?>
