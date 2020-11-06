<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$numero_os 			= $_POST['numero_os'];

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

	if (!count($msg_erro["msg"])) {
		
		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}

		if (!empty($aux_data_inicial)){
			$cond_data_abertura = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
		}

		if (!empty($numero_os)){
			$cond_os = " AND tbl_os.os = $numero_os ";
		}
	}
}

if (empty($login_privilegios) AND $admin_sap_login != "t"){
	header("LOCATION: menu_callcenter.php");
}else{
	if ($admin_sap_login == "t"){
		$cond_admin = " AND tbl_posto_fabrica.admin_sap = $login_admin ";
	}

	if ($login_privilegios == "*"){
		unset($cond_admin);
	}
}

$sql_os_agendamento = "
    SELECT 
        tbl_os.os,
        tbl_os.sua_os,
        TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
        tbl_os.consumidor_nome,
        tbl_os.hd_chamado,
        tbl_admin.nome_completo AS nome_admin,
        tbl_posto.nome AS nome_posto,
        tbl_posto_fabrica.contato_fone_comercial
    FROM tbl_os
    JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap AND tbl_admin.fabrica = $login_fabrica
    WHERE tbl_os.fabrica = $login_fabrica
    AND tbl_os.off_line_reservada IS TRUE
    AND tbl_os.data_abertura + interval '2 days' < current_date
    AND tbl_os.excluida IS NULL
    AND tbl_os.finalizada IS NULL
    $cond_posto
	$cond_data_abertura
	$cond_os
	$cond_admin
    UNION
    SELECT 
    	tbl_os.os,
    	tbl_os.sua_os,
    	TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
    	tbl_os.consumidor_nome,
    	tbl_os.hd_chamado,
    	tbl_admin.nome_completo AS nome_admin,
        tbl_posto.nome AS nome_posto,
        tbl_posto_fabrica.contato_fone_comercial
    FROM tbl_tecnico_agenda
    JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica = $login_fabrica
    JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap AND tbl_admin.fabrica = $login_fabrica
    WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
    AND tbl_tecnico_agenda.confirmado IS NULL
    AND tbl_os.excluida IS NULL
    AND tbl_os.finalizada IS NULL
    AND tbl_tecnico_agenda.data_cancelado IS NULL
    $cond_postoi
	$cond_data_abertura
	$cond_os
	$cond_admin";
$res_os_agendamento = pg_query($con, $sql_os_agendamento);

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ORDEM DE SERVIÇO PENDENTE DE AGENDAMENTO";
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
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

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

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("numero_os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='numero_os'>Número da OS</label>
				<div class='controls controls-row'>
					<input type="text" name="numero_os" id="numero_os" class='span7' value="<? echo $numero_os ?>" >
				</div>
			</div>
		</div>
		<div class='span6'></div>
	</div>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>

<?php
if (isset($res_os_agendamento)) {
	if (pg_num_rows($res_os_agendamento) > 0) {
		echo "<div class='container-fluid'>";
	?>
		<table id="resultado_agendamentos" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>OS</th>
					<th>Consumidor</th>
					<th>Atendimento</th>
					<th>Abertura</th>
					<th>Inspetor</th>
                    <th>Posto</th>
                    <th>Fone Posto</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < pg_num_rows($res_os_agendamento); $i++) {
					$os 					= pg_fetch_result($res_os_agendamento, $i, "os");
					$sua_os 				= pg_fetch_result($res_os_agendamento, $i, "sua_os");
					$data_abertura 			= pg_fetch_result($res_os_agendamento, $i, "data_abertura");
					$consumidor_nome 		= pg_fetch_result($res_os_agendamento, $i, "consumidor_nome");
					$hd_chamado 			= pg_fetch_result($res_os_agendamento, $i, "hd_chamado");
					$nome_admin 			= pg_fetch_result($res_os_agendamento, $i, "nome_admin");
					$nome_posto 			= pg_fetch_result($res_os_agendamento, $i, "nome_posto");
					$contato_fone_comercial = pg_fetch_result($res_os_agendamento, $i, "contato_fone_comercial");
				?>
				<tr>
					<td class="tac"><a href="os_press.php?os=<?=$os?>"><?=$sua_os?></a></td>
					<td><?=$consumidor_nome?></td>
					<td class='tac'><?=$data_abertura?></td>
					<td class='tac'><?=$hd_chamado?></td>
					<td><?=$nome_admin?></td>
					<td><?=$nome_posto?></td>
					<td class='tac'><?=$contato_fone_comercial?></td>
				</tr>
				<?php	
				}
				?>
			</tbody>
		</table>
		<script>
			$.dataTableLoad({ table: "#resultado_agendamentos" });
		</script>
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
