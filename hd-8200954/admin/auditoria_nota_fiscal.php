<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
require_once AWS_SDK;
include_once '../anexaNF_inc.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];
	$status          = $_POST['status'];

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
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

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			//$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		$condStatus = " 189,190,191 ";
		$sql = "SELECT interv_reinc.os INTO temp tmp_aud_nf_$login_admin
			FROM (
				  SELECT
					  ultima_reinc.os,
						(SELECT status_os
							 FROM tbl_os_status
							 WHERE fabrica_status = $login_fabrica
							 AND tbl_os_status.os = ultima_reinc.os AND status_os IN ($condStatus) order by data desc LIMIT 1) AS ultimo_reinc_status
					  FROM (SELECT DISTINCT os
					   FROM tbl_os_status
					JOIN tbl_os USING(os)
					WHERE fabrica_status = $login_fabrica
					AND status_os IN ($condStatus) ) ultima_reinc
				) interv_reinc
			WHERE interv_reinc.ultimo_reinc_status IN ($status);

		SELECT DISTINCT
					tbl_os.os,
					tbl_os.sua_os,
					TO_CHAR((select data from tbl_os_status where status_os = $status and tbl_os_status.os = tbl_os.os order by data desc limit 1), 'DD/MM/YYYY') as data_auditoria,
					
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_os.serie,
					tbl_os.nota_fiscal,
					TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nota_fiscal
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				JOIN tbl_os_status USING (os)
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
				{$cond_posto}
				AND tbl_os.os in (select os from tmp_aud_nf_$login_admin);
		";
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "auditoria-nf-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			switch ($status) {
				case 189:
					$status_text = "PENDENTE DE AUDITORIA";
					break;

				case 191:
					$status_text = "APROVADA";
					break;

				case 190:
					$status_text = "RECUSADA";
					break;
			}

			if($login_fabrica == 6){
				if($status == 189){
					$coluna_tectoy = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Inclusão</th>";
				}
				if($status == 190){
					$coluna_tectoy = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Recusada</th>";
				}
				if($status == 191){
					$coluna_tectoy = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Aprovada</th>";
				}				
			}

			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='8' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÒRIO DA AUDITORIA DE NOTA FISCAL: {$status_text}
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>NF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data NF</th>
							$coluna_tectoy							
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$sua_os           = pg_fetch_result($resSubmit, $i, 'sua_os');
				$data             = pg_fetch_result($resSubmit, $i, 'data');
				$posto            = pg_fetch_result($resSubmit, $i, 'posto');
				$produto          = pg_fetch_result($resSubmit, $i, 'produto');
				$serie            = pg_fetch_result($resSubmit, $i, 'serie');
				$nota_fiscal      = pg_fetch_result($resSubmit, $i, 'nota_fiscal');
				$data_nota_fiscal = pg_fetch_result($resSubmit, $i, 'data_nota_fiscal');
				$data_auditoria = pg_fetch_result($resSubmit, $i, 'data_auditoria');

				if($login_fabrica == 6){
					$dados_coluna_data ="<td nowrap align='center' valign='top'>{$data_auditoria}</td>";
				}

				$body .="
					<tr>
						<td nowrap align='center' valign='top'>{$sua_os}</td>
						<td nowrap align='center' valign='top'>{$data}</td>
						<td nowrap align='center' valign='top'>{$posto}</td>
						<td nowrap align='center' valign='top'>{$produto}</td>
						<td nowrap align='center' valign='top'>{$serie}</td>
						<td nowrap align='center' valign='top'>{$nota_fiscal}</td>
						<td nowrap align='center' valign='top'>{$data_nota_fiscal}</td>
						$dados_coluna_data
					</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='8' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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

$layout_menu = "auditoria";
$title = "AUDITORIA DE NOTA FISCAL";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask"
);

include("plugin_loader.php");
?>

<style>
	span.loading {
		display: none;
	}

	span.loading > img {
		height: 30px;
		width: 30px;
	}

	div.motivo {
		display: none;
	}
</style>

<script type="text/javascript">
	$(function() {
		Shadowbox.init();

		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("button.acao").click(function () {
			var td   = $(this).parents("td");
			var os   = $(this).attr("rel");
			var acao = $(this).attr("name");

			if (acao == "recusa") {
				$(td).find("button[name=aprova], button[name=recusa]").hide();
				$(td).find("div.motivo").show();
			} else {
				switch (acao) {
					case "aprova":
						var data          = { aprova: true, os: os };
						var html_complete = "<div class='alert alert-success' style='width: 180px; margin-bottom: 0px;' ><h4>Aprovada</h4></div>";
						break;

					case "prosseguir_recusa":
						var motivo        = $.trim($(td).find("input[name=motivo]").val());
						var data          = { recusa: true, motivo: motivo, os: os };
						var html_complete = "<div class='alert alert-error' style='width: 180px; margin-bottom: 0px;' ><h4>Recusada</h4></div>";
						break;
				}

				if (acao == "prosseguir_recusa" && motivo.length == 0) {
					alert("Informe um motivo");
				} else {
					$.ajax({
						url: "auditoria_nota_fiscal_ajax.php",
						type: "POST",
						data: data,
						beforeSend: function () {
							$(td).find("button[name=aprova], button[name=recusa]").hide();
							$(td).find("div.motivo").hide();
							$(td).find("span.loading").show();
						},
						complete: function (data) {
							data = $.parseJSON(data.responseText);

							if (data.erro) {
								$(td).find("span.loading").hide();
								$(td).find("button[name=aprova], button[name=recusa]").show();

								alert(data.erro);
							} else {
								$(td).html(html_complete);
							}
						}
					});
				}
			}
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

<form name='frm_auditoria_nf' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
			<div class='span8'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Status</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<label class="radio">
								<input type="radio" name="status" value="189" checked />Pendente de Auditoria
							</label>

							<label class="radio">
								<input type="radio" name="status" value="191" <?=($status == 191) ? "checked" : ""?> />Aprovada
							</label>

							<label class="radio">
								<input type="radio" name="status" value="190" <?=($status == 190) ? "checked" : ""?> />Recusada
							</label>
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
			echo "<br />";

			$count = pg_num_rows($resSubmit);
		?>
			<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' style="margin: 0 auto;" >
				<thead>
					<tr class='titulo_coluna' >
						<th>OS</th>
<?
        if($login_fabrica == 6){
?>
                        <th>Anexo</th>
<?
        }
?>
						<th>Data Abertura</th>
						<th>Posto</th>
						<th>Produto</th>
						<th>Série</th>
						<th>NF</th>
						<th>Data NF</th>
						<?php
						if ($status == 189) {
						?>
							<th>Ações</th>
						<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$os               = pg_fetch_result($resSubmit, $i, 'os');
						$sua_os           = pg_fetch_result($resSubmit, $i, 'sua_os');
						$data             = pg_fetch_result($resSubmit, $i, 'data');
						$posto            = pg_fetch_result($resSubmit, $i, 'posto');
						$produto          = pg_fetch_result($resSubmit, $i, 'produto');
						$serie            = pg_fetch_result($resSubmit, $i, 'serie');
						$nota_fiscal      = pg_fetch_result($resSubmit, $i, 'nota_fiscal');
						$data_nota_fiscal = pg_fetch_result($resSubmit, $i, 'data_nota_fiscal');
						?>

						<tr>
							<td class='tac'><a href='os_press.php?os=<?=$os?>' target='_blank' ><?=$sua_os?></a></td>
					<?
					    if($login_fabrica == 6){
					        $temImg = temNF($os, 'url');
					        echo "<td  class='tac'>";
						        foreach($temImg as $linha){
						        	echo "<a href='$linha' ><img src='../imagens/clips.gif'/> </a>";
						        }
						    echo "</td>";
					    }
?>
							<td class='tac'><?=$data?></td>
							<td><?=$posto?></td>
							<td><?=$produto?></td>
							<td class='tac'><?=$serie?></td>
							<td class='tac'><?=$nota_fiscal?></td>
							<td class='tac'><?=$data_nota_fiscal?></td>
							<?php
							if ($status == 189) {
							?>
								<td class="tac" style="vertical-align: middle; min-width: 230px;" nowrap >
									<button type="button" class="btn btn-success acao" name="aprova" rel="<?=$os?>" >Aprovar</button>
									<button type="button" class="btn btn-danger acao" name="recusa" >Recusar</button>
									<span class="loading" >
										<img src="imagens/loading_img.gif" />
									</span>
									<div class="tal motivo" >
										<input type="text" name="motivo" placeholder="Informe o motivo" style="margin-bottom: 0px; width: 115px;" />
										<button type"button" class="btn acao" name="prosseguir_recusa" rel="<?=$os?>" >Prosseguir</button>
									</div>
								</td>
							<?php
							}
							?>
						</tr>
					<?php
					}
					?>
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
<script type='text/javascript' src='js/FancyZoom.js'></script>
<script type='text/javascript' src='js/FancyZoomHTML.js'></script>
<script type="text/javascript">
    setupZoom();
</script>
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
