<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "TEMPO MÉDIO DE ATENDIMENTO";
$layout_menu = "callcenter";
$admin_privilegios="call_center";

function geraTimestamp($data) {
	$partes = explode('/', $data);
	return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
}

function mascara($val, $mascara){
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mascara)-1; $i++){
		if($mascara[$i] == '#'){
			if(isset($val[$k]))
				$maskared .= $val[$k++];
		}else{
			if(isset($mascara[$i]))
				$maskared .= $mascara[$i];
		}
	}
	return $maskared;
}

if ($_POST["pesquisar"] == "Pesquisar") {
	$data_inicio  = $_POST['data_inicio'];
    $data_fim     = $_POST['data_fim'];
    $estado       = $_POST['estado'];
    $atendente    = $_POST['atendente'];

    if (empty($data_inicio) || empty($data_fim)) {
		$msg_erro["msg"][] = "Preencha os Campos obrigatórios";
		$msg_erro["campos"][] = "data_inicio";
		$msg_erro["campos"][] = "data_fim";
	} else {
		try {
	    	validaData($data_inicio,$data_fim,6);
	    	list($dia, $mes, $ano) = explode("/", $data_inicio);
			$xdata_inicio = "$ano-$mes-$dia";

			list($dia, $mes, $ano) = explode("/", $data_fim);
			$xdata_fim = "$ano-$mes-$dia";


		} catch (Exception $e) {    	
	    	$msg_erro["msg"][]    = $e->getMessage();
			$msg_erro["campos"][] = "data_inicio";
			$msg_erro["campos"][] = "data_fim";
		}
	}

	if (!empty($atendente)) {
		$cond_atendente = " AND tbl_hd_chamado.atendente = {$atendente} ";		
	}

	if (!empty($estado)) {
		$cond_estado = " AND tbl_cidade.estado = '{$estado}' ";
	}

	if (!count($msg_erro["msg"]) > 0) {
		
		$sql_pesq = "	SELECT 
				DISTINCT tbl_hd_chamado_item.hd_chamado
				into temp temp_thd
			        FROM tbl_hd_chamado_item
			        JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
			        WHERE tbl_hd_chamado_item.data BETWEEN '{$xdata_inicio} 00:00:00' AND '{$xdata_fim} 23:59:59'
			        AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND UPPER(tbl_hd_chamado_item.status_item) = 'RESOLVIDO';

				SELECT 
							tbl_hd_chamado.data AS data_abertura,
							tbl_hd_chamado.hd_chamado ,
							tbl_posto.nome AS posto_nome,
							tbl_posto.cnpj AS posto_cnpj,
							tbl_cidade.estado AS cliente_estado,
							tbl_posto.estado AS posto_estado,
							tbl_admin.nome_completo AS admin_ab
						INTO TEMP temp_tma
						FROM tbl_hd_chamado
						JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
						JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
						JOIN temp_thd ON temp_thd.hd_chamado = tbl_hd_chamado.hd_chamado
						LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
						WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
							AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
							AND upper(tbl_hd_chamado.titulo) <> trim('HELP-DESK POSTO')
							AND upper(tbl_hd_chamado.status) =  'RESOLVIDO'
							$cond_atendente
							$cond_estado
						ORDER BY tbl_hd_chamado.hd_chamado DESC;

						SELECT DISTINCT
							temp_tma.hd_chamado ,
							to_char(temp_tma.data_abertura, 'DD/MM/YYYY') AS data_abertura,							
							temp_tma.posto_nome,
							temp_tma.posto_cnpj,
							temp_tma.cliente_estado,
							temp_tma.posto_estado ,
							temp_tma.admin_ab,
							to_char(tbl_hd_chamado_item.data, 'DD/MM/YYYY') AS data_fechamento,
							tbl_admin.nome_completo AS admin_fc
							FROM temp_tma 
							JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = temp_tma.hd_chamado
							AND tbl_hd_chamado_item.data BETWEEN '{$xdata_inicio} 00:00:00' AND '{$xdata_fim} 23:59:59'
							AND UPPER(tbl_hd_chamado_item.status_item) = 'RESOLVIDO'
							JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
							ORDER BY temp_tma.hd_chamado ;";
		$res_pesq = pg_query($con,$sql_pesq);
		#echo nl2br($sql_pesq);exit;


		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($res_pesq) > 0) {
				$fileName = "relatorio_tma_".date("d-m-Y-H:i").".xls";

				$file = fopen("/tmp/{$fileName}", "w");

				$thead = "PROTOCOLO;AGENTE (AB);AGENTE (FC);CNPJ - POSTO;RAZÃO SOCIAL - POSTO;UF - CLIENTE;DATA ABERTURA;DATA FECHAMENTO;TMA\n";

				fwrite($file, $thead);
				$media_tma=0;

				for($i = 0; $i < pg_num_rows($res_pesq); $i++){
					$hd_chamado 		= pg_fetch_result($res_pesq, $i, 'hd_chamado');
					$admin_ab           = pg_fetch_result($res_pesq, $i, 'admin_ab');
					$admin_fc       	= pg_fetch_result($res_pesq, $i, 'admin_fc');
					$data_abertura     	= pg_fetch_result($res_pesq, $i, 'data_abertura');
					$data_fechamento   	= pg_fetch_result($res_pesq, $i, 'data_fechamento');
					$posto_nome     	= pg_fetch_result($res_pesq, $i, 'posto_nome');
					$posto_cnpj         = pg_fetch_result($res_pesq, $i, 'posto_cnpj');
					$posto_estado     	= pg_fetch_result($res_pesq, $i, 'posto_estado');
					$cliente_estado 	= pg_fetch_result($res_pesq, $i, 'cliente_estado');
					if (!empty($posto_cnpj)) {
						$posto_cnpj = mascara($posto_cnpj,'##.###.###/####-##');
					}
					$tma = (int)floor( (geraTimestamp($data_fechamento) - geraTimestamp($data_abertura) ) / (60 * 60 * 24));
					$media_tma = $media_tma + $tma;

					$tbody = "$hd_chamado;$admin_ab;$admin_fc;$posto_cnpj;$posto_nome;$cliente_estado;$data_abertura;$data_fechamento;$tma";

					$tbody .= "\n";

					fwrite($file, $tbody);
				}
				$media_tma =  number_format(($media_tma / pg_num_rows($res_pesq)), 2, ',', ' ');

				fwrite($file, ";;;;;;;MEDIA FINAL (dias);$media_tma\n");

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

include 'cabecalho_new.php';

$plugins = array("datepicker"
	,"dataTable"
	,"maskedinput"
	);

include 'plugin_loader.php';
?>

<script type="text/javascript">

$(function() {	
	$.datepickerLoad(Array("data_fim", "data_inicio"));
	$.dataTableLoad({ table: "#table_callcenter_tempo_medio_atendimento" });
});


</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='relatorio_callcenter_tempo_medio_atendimento' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_inicio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicio'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicio" id="data_inicio" size="12" maxlength="10" class='span12' value= "<?=$data_inicio?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_fim", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_fim'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_fim" id="data_fim" size="12" maxlength="10" class='span12' value="<?=$data_fim?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class="span4">
			<div class="control-group">
				<label class="control-label" for="estado">Estado</label>
				<div class="controls controls-row">
					<div class="span10">
							<select id="estado" name="estado" class="span12">
								<option value="" >Selecione</option>
								<?php
								#O $array_estados() está no arquivo funcoes.php
								foreach ($array_estados() as $sigla => $nome_estado) {
									$selected = ($sigla == getValue('estado')) ? "selected" : "";

									echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
								}
								?>
							</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='atendente'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <select name="atendente" id="atendente">
                            <option value=""></option>
							<?php
                            $sql = "SELECT admin, login
									from tbl_admin
									where fabrica = $login_fabrica
									and ativo is true
									and (privilegios like '%call_center%' or privilegios like '*')
									AND tbl_admin.admin NOT IN (6437)
                                    order by login";
							$res = pg_exec($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_atendente = ( isset($atendente) and ($atendente == $key['admin']) ) ? "SELECTED" : '' ;?>

                            	<option value="<?=$key['admin']?>" <?=$selected_atendente ?> ><?=$key['login']?></option>
                            <?php
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>        
        <div class='span2'></div>
    </div>
	<br />
	<p class="tac">
		<input type="submit" class="btn" name="pesquisar" value="Pesquisar" />
	</p>
	<br />
</FORM>
<br /> 

<!-- Tabela -->
<?
if (isset($res_pesq)) {
	if(pg_num_rows($res_pesq) > 0){
	                    
	?>
	<form name="frm_tab" method="GET" class="form-search form-inline" enctype="multipart/form-data" >
		<table id="table_callcenter_tempo_medio_atendimento" class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_coluna'>
					<td>Protocolo</td>
					<td>Agente (AB)</td>
					<td>Agente (FC)</td>
					<td>CNPJ - Posto</td>
					<td>Razão Social - Posto</td>
					<td>UF - Cliente</td>
					<td>Data Abertura</td>
					<td>Data Fechamento</td>
					<td>TMA</td>
				</tr>
			</thead>
			<tbody>
				<?
				$media_tma = 0;
				for ($i = 0 ; $i < pg_num_rows($res_pesq) ; $i++) {

					$hd_chamado 		= pg_fetch_result($res_pesq, $i, 'hd_chamado');
					$admin_ab           = pg_fetch_result($res_pesq, $i, 'admin_ab');
					$admin_fc       	= pg_fetch_result($res_pesq, $i, 'admin_fc');
					$data_abertura     	= pg_fetch_result($res_pesq, $i, 'data_abertura');
					$data_fechamento   	= pg_fetch_result($res_pesq, $i, 'data_fechamento');
					$posto_nome     	= pg_fetch_result($res_pesq, $i, 'posto_nome');
					$posto_cnpj         = pg_fetch_result($res_pesq, $i, 'posto_cnpj');
					$posto_estado     	= pg_fetch_result($res_pesq, $i, 'posto_estado');
					$cliente_estado 	= pg_fetch_result($res_pesq, $i, 'cliente_estado');
					if (!empty($posto_cnpj)) {
						$posto_cnpj = mascara($posto_cnpj,'##.###.###/####-##');
					}
					$tma = (int)floor( (geraTimestamp($data_fechamento) - geraTimestamp($data_abertura) ) / (60 * 60 * 24));
					$media_tma = $media_tma + $tma;
					 ?>	
					<tr>
						<td>						
							<a href="callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>" target="_blank">
							<?=$hd_chamado?></a>
						</td>
						<td><?=$admin_ab?></td>
						<td><?=$admin_fc?></td>
						<td><?=$posto_cnpj?></td>
						<td><?=$posto_nome?></td>
						<td><?=$cliente_estado?></td>
						<td><?=$data_abertura?></td>
						<td><?=$data_fechamento?></td>
						<td><?=$tma?></td>
					</tr>
				<?
				}
				?>
			</tbody>
		</table>
	</form>	
	<br />
	<?php
	$media_tma =  number_format(($media_tma / pg_num_rows($res_pesq)), 2, ',', ' ');?>
	<div class="alert alert-warning">
		<h4>MÉDIA FINAL: <?=$media_tma?> dias</h4>
	</div>
	<br />
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Excel</span>
		</div>
	<?
	}else{?>
	<div class="container">
		<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
	<?
	}
}
?>

<?php

include "rodape.php";

?>
