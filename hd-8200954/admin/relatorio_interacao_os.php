<?php

if (isset($_GET["interagir"])) {
	include "interacao_os.php";
	exit;
}

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";
include "funcoes.php";

if (isset($_POST["interacao_massa"]) && $_POST["interacao_massa"] == "sim") {

	$msg_erro_upload = array();
	$arquivo         = $_FILES["arquivo"];
	$tipo_arquivo    = explode(".", $arquivo["name"]);
	$tipo_arquivo    = $tipo_arquivo[count($tipo_arquivo) - 1];

	if ((int)$arquivo["size"] == 0) {

		$msg_erro_upload[] = traduz("Por favor, insira o arquivo!");

	} else if (!in_array($tipo_arquivo, array("txt", "csv"))) {

		$msg_erro_upload[] = traduz("Por favor, insira o arquivo com a extensão TXT ou CSV!");

	} else {
		$dados = file_get_contents($arquivo["tmp_name"]);
		$linhas = explode("\n", $dados);
		$linha_cont = 0;

		foreach ($linhas as $linha) {
			$linha_cont++;
			$dados_interacao = explode(";", $linha);

			if ((int)count($dados_interacao) == 1 and strlen($dados_interacao[1]) > 0) {
				$msg_erro_upload[] = traduz("A formatação do arquivo na linha ")."{$linha_cont}".traduz(" está errada");

			} else if (!in_array((int)count($dados_interacao), array(2, 3)) and strlen($dados_interacao[1]) > 0) {
				$msg_erro_upload[] = traduz("Quantidade de itens diferentes de 2, na linha ")."{$linha_cont}";

			} else if (strlen($dados_interacao[1]) > 0) {
				$os = $dados_interacao[0];

				if (strlen($os) < 12) {
					$os = str_pad($os, 12, "0", STR_PAD_LEFT);
				}
				$interacao = $dados_interacao[1];

				if (strlen(trim($os)) == 0) {
					$msg_erro_upload[] = traduz("Número de OS não informado, na linha ")."{$linha_cont}";

				} else {
					$sql_os = "SELECT os FROM tbl_os WHERE sua_os = '{$os}' AND fabrica = {$login_fabrica}";
					$res_os = pg_query($con, $sql_os);

					if(pg_num_rows($res_os) == 0){
						$msg_erro_upload[] = traduz("Número de OS ")."{$os}".traduz(" inválida, na linha ")."{$linha_cont}";

					}

				}

				if(strlen(trim($interacao)) == 0){

					$msg_erro_upload[] = traduz("Interação não informada, na linha ")."{$linha_cont}";

				}

			}

		}

		/* Arquivo validado sem erros */
		if(count($msg_erro_upload) == 0){

			foreach ($linhas as $linha) {
				$dados_interacao = explode(";", $linha);
				$os = $dados_interacao[0];

				if (strlen($os) < 12) {
					$os = str_pad($os, 12, "0", STR_PAD_LEFT);
				}
				$interacao = $dados_interacao[1];

				$sql = "SELECT os, posto FROM tbl_os WHERE sua_os = '{$os}' AND fabrica = {$login_fabrica}";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){

					$os    = pg_fetch_result($res, 0, "os");
					$posto = pg_fetch_result($res, 0, "posto");

					$sql = "INSERT INTO tbl_os_interacao
						(os, comentario, fabrica, posto, admin, programa)
						VALUES
						({$os}, '{$interacao}', {$login_fabrica}, {$posto}, {$login_admin}, 'admin/relatorio_interacao_os.php')";
					$res = pg_query($con, $sql);

				}

			}

			$msg_sucesso_upload = true;

		}

	}

}

if (isset($_POST["pesquisar"])) {
	$status       = $_POST["status"];
	$posto_id     = $_POST["posto_id"];
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];
	$tipo_data    = $_POST["tipo_data"];

	if (empty($data_inicial) || empty($data_final)) {
		$msg_erro["msg"][] = traduz("Preencha os Campos obrigatórios");
	}

	if ($login_fabrica == 183 AND $status == "novas_interacoes_posto_autorizado"){
		unset($msg_erro["msg"]);
	}
	
	try {
		if (!empty($data_inicial) AND !empty($data_final)){
			validaData($data_inicial, $data_final, 1);

			list($dia, $mes, $ano) = explode("/", $data_inicial);
			$data_inicial = "$ano-$mes-$dia";

			list($dia, $mes, $ano) = explode("/", $data_final);
			$data_final = "$ano-$mes-$dia";
		}
	} catch(Exception $e) {
		$msg_erro["msg"][] = $e->getMessage();
	}

	if (!count($msg_erro["msg"])) {
		switch ($status) {
			case "novas_interacoes_posto_autorizado":
				$whereStatus = "x.confirmacao_ultima_interacao IS NULL AND x.admin_ultima_interacao IS NULL";
				break;

			case "os_ultima_interacao_posto_autorizado":
				$whereStatus = "x.admin_ultima_interacao IS NULL ";
				break;

			case "os_ultima_interacao_fabrica":
				$whereStatus = "x.admin_ultima_interacao IS NOT NULL";
				break;
			
			// exibe TODOS os status inicialmente permitidos na tela
			case "todos":
				$whereStatus = "((x.confirmacao_ultima_interacao IS NULL AND x.admin_ultima_interacao IS NULL) 
								OR x.admin_ultima_interacao IS NULL 
								OR x.admin_ultima_interacao IS NOT NULL)";
				break;	
		}

		if (!empty($posto_id)) {
			$wherePosto = "AND o.posto = {$posto_id}";
		}

		switch ($tipo_data) {
			case 'digitacao':
				$tipo_data = "data_digitacao";
				break;

			case 'finalizada':
				$tipo_data = "finalizada";
				break;

			case 'interacao':
				$tipo_data = "interacao";
				break;				
		}

		if($tipo_data == "interacao"){
			if (!empty($data_inicial) AND !empty($data_final)){
				$cond_data_interacao = " AND (tbl_os_interacao.data BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59') ";
			}
			$subquery = "SELECT							
							o.os,
							o.sua_os,
							p.nome AS posto_autorizado,
							p.estado,
							o.data_digitacao,
							TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS finalizada,
							pf.codigo_posto as codigo_posto,
							(SELECT oi.data FROM tbl_os_interacao oi WHERE oi.fabrica = $login_fabrica AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS ultima_interacao,
							(SELECT oi.admin FROM tbl_os_interacao oi WHERE oi.fabrica = $login_fabrica AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS admin_ultima_interacao,
							(SELECT oi.confirmacao_leitura FROM tbl_os_interacao oi WHERE oi.fabrica = $login_fabrica AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS confirmacao_ultima_interacao
							FROM tbl_os_interacao
							INNER JOIN tbl_os o ON o.os = tbl_os_interacao.os AND o.fabrica = $login_fabrica
							INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $login_fabrica
							INNER JOIN tbl_posto p ON p.posto = pf.posto
							WHERE tbl_os_interacao.fabrica = $login_fabrica
							{$cond_data_interacao}
							{$wherePosto}
							ORDER BY tbl_os_interacao.data ASC";
			} else {
				if (!empty($tipo_data) AND !empty($data_inicial)){
					$cond_data = " AND (o.{$tipo_data} BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59') ";
				}
				$subquery = "SELECT								
								o.os,
								o.sua_os,
								p.nome AS posto_autorizado,
								p.estado,
								data_digitacao,
								TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS finalizada,
								(SELECT oi.data FROM tbl_os_interacao oi WHERE oi.fabrica = $login_fabrica AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS ultima_interacao,
								(SELECT oi.admin FROM tbl_os_interacao oi WHERE oi.fabrica = $login_fabrica AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS admin_ultima_interacao,
								(SELECT oi.confirmacao_leitura FROM tbl_os_interacao oi WHERE oi.fabrica = $login_fabrica AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS confirmacao_ultima_interacao,
								pf.codigo_posto as codigo_posto
								FROM tbl_os o
								INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $login_fabrica
								INNER JOIN tbl_posto p ON p.posto = pf.posto
								WHERE o.fabrica = $login_fabrica
								{$cond_data}
								{$wherePosto}
								ORDER BY o.data_digitacao ASC";
			}

		if($tipo_data == "interacao") {
			$sql = "
				SELECT
					DISTINCT
					x.os,
					x.sua_os,
					x.posto_autorizado,
					to_char(x.data_digitacao,'DD/MM/YYYY') as data_digitacao,
					x.finalizada,
					to_char(x.ultima_interacao,'DD/MM/YYYY') as ultima_interacao,
					x.estado,
					x.codigo_posto,
					x.confirmacao_ultima_interacao,
					x.admin_ultima_interacao
				FROM ({$subquery}) x
				WHERE {$whereStatus}
				AND ultima_interacao >= data_digitacao";
		} else {
			if (!empty($tipo_data) AND !empty($data_inicial)){
				$cond_data = " AND (o.{$tipo_data} BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59') ";
			}
			$sql = "
				SELECT
					x.os,
					x.sua_os,
					x.posto_autorizado,
					to_char(x.data_digitacao,'DD/MM/YYYY') as data_digitacao,
					x.finalizada,
					to_char(x.ultima_interacao,'DD/MM/YYYY') as ultima_interacao,
					x.estado,
					x.codigo_posto,
					x.confirmacao_ultima_interacao,
					x.admin_ultima_interacao
				FROM (SELECT
						o.os,
						o.sua_os,
						p.nome AS posto_autorizado,
						p.estado,
						data_digitacao,
						TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS finalizada,
						(SELECT oi.data FROM tbl_os_interacao oi WHERE oi.fabrica = {$login_fabrica} AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS ultima_interacao,
						(SELECT oi.admin FROM tbl_os_interacao oi WHERE oi.fabrica = {$login_fabrica} AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS admin_ultima_interacao,
						(SELECT oi.confirmacao_leitura FROM tbl_os_interacao oi WHERE oi.fabrica = {$login_fabrica} AND oi.os = o.os ORDER BY oi.data DESC LIMIT 1) AS confirmacao_ultima_interacao,
						pf.codigo_posto as codigo_posto					
					FROM tbl_os o
					INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto p ON p.posto = pf.posto
					WHERE o.fabrica = {$login_fabrica}
					{$cond_data}
					{$wherePosto}
					ORDER BY o.{$tipo_data} ASC
				) x
				WHERE {$whereStatus}
				AND ultima_interacao >= data_digitacao";
		}
		
		$resPesquisa = pg_query($con, $sql);
	}
}

$layout_menu = "callcenter";
$title = traduz("INTERAÇÕES EM ORDEM DE SERVIÇO");

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
		var os     = $(this).data("os");
		var sua_os = $(this).data("sua-os");

		Shadowbox.open({
			content: "relatorio_interacao_os.php?interagir=true&os="+os,
			player: "iframe",
			width: 850,
			height: 600,
			title: "<?=traduz("Ordem de Serviço &nbsp;")?>" +sua_os
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
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?></b>
</div>

<form method="POST" role="form" class="tc_formulario" >
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>

	<br />

	<div class="row-fluid">
		<div class="span1" ></div>
		<div class="span2">
			<div class="control-group">
				<div class="controls controls-row">
					<div class="span12">
						<label class="radio" >
							<input type="radio" name="tipo_data" checked value= "digitacao" />
							<?=traduz('Data Digitação')?>
						</label>
						<label class="radio" >
							<input type="radio" name="tipo_data" <?=($_POST["tipo_data"] == "finalizada") ? "checked" : ""?> value= "finalizada" />
							<?=traduz('Data Finalização')?>
						</label>
						<label class="radio" >
							<input type="radio" name="tipo_data" <?=($_POST["tipo_data"] == "interacao") ? "checked" : ""?> value= "interacao" />
							<?=traduz('Data Interação')?>
						</label>						
					</div>
				</div>
			</div>
		</div><br>
		<div class="span2">
			<div class="control-group <?=(in_array('data', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class="control-label" for="data_inicial"><?=traduz('Data Inicial')?></label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class="asteristico">*</h5>
						<input type="text" name="data_inicial" id="data_inicial" class="span12" value= "<?=$_POST['data_inicial']?>" autocomplete="off" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group <?=(in_array('data', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class="control-label" for="data_final"><?=traduz('Data Final')?></label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class="asteristico">*</h5>
						<input type="text" name="data_final" id="data_final" class="span12" value="<?=$_POST['data_final']?>" autocomplete="off" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4" >
			<div class="control-group <?=(in_array('status', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="status" ><?=traduz('Status')?></label>
				<div class="controls controls-row" >
					<?php
				 	 if ($login_fabrica == 72) {
					 	$status_array["todos"] = "Todos";
					 }
					$status_array["novas_interacoes_posto_autorizado"] = "Novas interações do Posto Autorizado";
					$status_array["os_ultima_interacao_posto_autorizado"] = "OSs com última interação do Posto Autorizado";
					$status_array["os_ultima_interacao_fabrica"] = "OSs com última interação da Fábrica";
					if ($login_fabrica == 165) {
						$status_array = array(
							"os_ultima_interacao_posto_autorizado" => "OSs com última interação do Posto Autorizado",
							"os_ultima_interacao_fabrica"          => "OSs com última interação da Fábrica"
						);
					}
					?>

					<select id="status" name="status" class="span12" >
						<?php
						foreach ($status_array as $value => $text) {
							$selected = (getValue("status") == $value) ? "selected" : "";

							echo "<option value='{$value}' {$selected} >{$text}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1" ></div>
		<?php
		if (strlen(getValue("posto_id")) > 0) {
			$input_readonly = "readonly='readonly'";
			$i_class = "icon-remove";
		} else {
			$i_class = "icon-search";
		}
		?>
		<div class="span4" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="posto_codigo" ><?=traduz('Código do Posto')?></label>
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
		<div class="span4" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="posto_nome" ><?=traduz('Nome do Posto')?></label>
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
		<div class="span1" ></div>
		<? if(isFabrica(72)) { ?>
		<div class="span4">
			<label class="checkbox">
                <div  style="display:inline-block; position:relative;">
				    <input type="checkbox"  name="gerar_excel" value="t" rel="4"  <?= ($_POST["gerar_excel"] == "t") ? "checked" : ""; ?>><?=traduz('Download de Arquivo em CSV')?>
                    <div class='' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                </div>
			</label>
		</div>
		<? } ?>
	</div>
	<br />

	<button type="submit" name="pesquisar" class="btn" data-loading-text="<?=traduz("Pesquisando...")?>" ><?=traduz('Pesquisar')?></button>

	<br /><br />
</form>

<?php

if(!isset($resPesquisa) && in_array($login_fabrica, array(3))){

	if (count($msg_erro_upload) > 0) {

		echo "<div class='alert alert-error'><h4>".implode("<br />", $msg_erro_upload)."</h4></div>";

	}

	if($msg_sucesso_upload === true){

		echo "<div class='alert alert-success'> <h4> Arquivo enviado com Sucesso! </h4> </div>";

	}

?>

<form method="POST" role="form" class="tc_formulario" enctype="multipart/form-data">

	<input type="hidden" name="interacao_massa" value="sim">

	<div class='titulo_tabela '><?=traduz('Interação em Massa')?></div>

	<br />


	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10">
			<div class="alert alert-warning tal"><?=traduz('
				Extensões permitidas: TXT e CSV <br />
				Layout no corpo do arquivo, sempre separados por ; (ponto e virgula): Número da OS; Descrição da Interação;')?>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span6">
			<div class="control-group">
				<div class="controls controls-row">
					<div class="span12">
						<label><?=traduz('Arquivo:')?></label>
						<input type="file" name="arquivo" class="span10" required>
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<label> &nbsp; </label>
			<button type="submit" class="btn btn-default"><?=traduz(' Enviar Arquivo ')?></button>
		</div>
	</div>

	<br />

</form>

<?php
}

if (isset($resPesquisa)) {
	if (pg_num_rows($resPesquisa) > 0) {
	?>
		<table class="table table-striped table-bordered resultado">
			<thead>
				<tr class="titulo_coluna">
					<th><?=traduz('OS')?></th>
					<?php if(isFabrica(72)){?>
						<th><?=traduz('Codigo Posto')?></th>
					<?php } ?>
					<th><?=traduz('Posto Autorizado')?></th>
					<?php if (isFabrica(35, 72,24)) { ?>
						<th><?=traduz('Estado')?></th>
					<?php } ?>
					<?php if(isFabrica(72) && $status=="todos"){?>
						<th><?=traduz('Status')?></th>
					<?php } ?>
					<th><?=traduz('Data Digitação')?></th>
					<th><?=traduz('Data Finalizada')?></th>
					<th><?=traduz('Última Interação')?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
					$dadosExportacao = array();
				
				while ($row = pg_fetch_object($resPesquisa)) {
				?>
					<tr>
						<td><a href="os_press.php?os=<?=$row->os?>" target="_blank" ><?=$row->sua_os?></a></td>
						<?php if (isFabrica(72)) { ?>
							<td class="tac" ><?=$row->codigo_posto?></td>
						<?php } ?>
						<td><?=$row->posto_autorizado?></td>
						<?php if (isFabrica(35, 72,24)) { ?>
							<td class="tac" ><?=$row->estado?></td>
						<?php } 
						if (isFabrica(72) && $status=="todos"){
								if($row->confirmacao_ultima_interacao == NULL && $row->admin_ultima_interacao == NULL){
									$indexStatus = 'novas_interacoes_posto_autorizado';
								}elseif($row->admin_ultima_interacao == NULL){
									$indexStatus = 'os_ultima_interacao_posto_autorizado';
								}else{
									$indexStatus = 'os_ultima_interacao_fabrica';
								}
					  			$row->status = $status_array[$indexStatus];
					  			echo "<td class='tac'>$status_array[$indexStatus]</td>";
						} ?>
						<td class="tac" ><?=$row->data_digitacao?></td>
						<td class="tac" ><?=$row->finalizada?></td>
						<td class="tac" ><?=$row->ultima_interacao?></td>
						<td class="tac" >
							<button type="button" class="btn btn-small btn-info btn-ver-comentarios" data-os="<?=$row->os?>" data-sua-os="<?=$row->sua_os?>" ><i class="icon-comment icon-white" ></i></button>
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
		</script>
	<?php
	} else {
	?>
		<div class="alert alert-error" >
			<button type="button" class="close" data-dismiss="alert" >&times;</button>
			<strong><?=traduz('Nenhum resultado foi encontrado')?></strong>
		</div>
	<?php
	}

	if(isFabrica(72) && $_POST["gerar_excel"] == "t" && sizeof($dadosExportacao) > 0){

		// geração do arquivo
		flush();
		$arquivo_nome = "relatorio_interacao_os-$login_admin.csv";
		$path         = "xls/"; // Para teste remover comentário
		$path_tmp     = "/tmp/";
		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;
		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;


		// Cria arquivo e cabecalho
        $fp = fopen ($arquivo_completo,"w+");

        $titulos = array(
        	'OS',
        	'Codigo Posto',
        	'Posto Autorizado',
        	'Estado',
        	'Status',
        	'Data Digitacao',
        	'Data Finalizada',
        	'Ultima Interacao'
    	);
        fputs ($fp,implode(";",$titulos)."\n");

		foreach ($dadosExportacao as $row) {
			$campos = array(
				$row->sua_os,
				$row->codigo_posto,
				$row->posto_autorizado,
				$row->estado,
				$row->status,
				$row->data_digitacao,
				$row->finalizada,
				$row->ultima_interacao
			);
			fputs ($fp,implode(";",$campos)."\n");
		}
		fclose($fp);
		
		// botao Download CSV
	 	echo"
	        <br />
	        <table width='300' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
	            <tr>
	                <td align='left' valign='absmiddle'>
	                    <a href='$arquivo_completo' target='_blank'>
	                        <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>".traduz("Download do Arquivo CSV")."
	                    </a>
	                </td>
	            </tr>
	        </table>
	    ";
    }
}

include "rodape.php";
?>
