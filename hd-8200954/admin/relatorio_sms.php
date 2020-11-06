<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
$title = "RELATÓRIO DE SMS";
$layout_menu = "callcenter";

include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$admin_privilegios="call_center";
include_once 'autentica_admin.php';
include_once 'cabecalho_new.php';

$sms_callcenter = in_array($login_fabrica, array(3,80,104,151,169,170));

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){

	if($data_inicial != null && $data_final != null){

		// Usa a função criada e pega o timestamp das duas datas:
		$time_inicial = (int)is_date($data_inicial, 'EUR', '@');
		$time_final   = (int)is_date($data_final,   'EUR', '@');

		// Calcula a diferença de segundos entre as duas datas:
		$diferenca = $time_final - $time_inicial; // 19522800 segundos

		// Converte os segundos em dias
		$dias = (int)floor($diferenca / (60 * 60 * 24));

		if ($dias<= 45) {
			$data_inicialf = is_date("$data_inicial 00:00:00");
			$data_finalf   = is_date("$data_final 23:59:59");


			$sql_chave = "SELECT api_secret_key_sms FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$qry_chave = pg_query($con, $sql_chave);

			if (pg_num_rows($qry_chave) > 0) {

				$chave = pg_fetch_result($qry_chave, 0, 'api_secret_key_sms');

				$url = 'https://sms.comtele.com.br' .
					"/api/{$chave}/detailedreport"  .
					str_replace(' ', '%20', "?startDate={$data_inicialf}&"."endDate={$data_finalf}");

				// Para testes! Grava a última requisição e a reutiliza. Evita erros de temp < 30s e também
				// não sobrecarrega o web server
				// if (file_exists('/home/manuel/test/sms_response.json') and filesize('/home/manuel/test/sms_response.json')>3)
				// 	$request = file_get_contents('/home/manuel/test/sms_response.json');
				// else
				$request = file_get_contents($url);

				if ($request == '[]' or !$request) {
					$request = ($request == "[]") ? "" : $request;
					$msg_erro = 'Nenhum resultado encontrado. '.$request;
					// file_put_contents('/home/manuel/test/sms_response.json', $request);
				} else {
					$tabela = json_decode($request, true);

					// file_put_contents('/home/manuel/test/sms_response.json', $request);

					if (empty($tabela)) {
						$msg_erro = str_replace('método da API', 'relatório', utf8_decode($request));
					}
				}
			}
		}else{
			$msg_erro = "A diferença entre as datas não pode ser maior que 45 dias";
		}

	}else{
		$msg_erro = "Por favor, informe o intervalo para a pesquisa";
	}
}

?>

<script type="text/javascript" charset="utf-8">
	$(function() {
		$.dataTableLoad();
		// $.datepickerLoad(["data_inicial", "data_final"]);
		// $.datepickerLoad("data_inicial");
		$("#data_inicial").datepicker({ maxDate: 0, minDate: "-45d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_final").datepicker({ maxDate: 0, minDate: "-45d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	});
	function conteudo_sms(conteudo){

		Shadowbox.init();

		Shadowbox.open({
            content : "<div style='background-color: #fff; padding: 20px; padding-bottom: 40px; text-transform: uppercase; font-family: arial;'> <h3 style='text-align: center;'>Conteúdo do SMS</h3> <div style='border-bottom: 1px solid #ccc; margin-bottom: 20px;'></div> "+conteudo+" </div>",
            player: 'html',
            title : "Conteúdo do SMS",
            width : 600,
            height: 400
        });

	}
</script>

<? if(strlen($msg_erro)>0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<?
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<div class="alert alert-warning">
	<strong>
		Para realizar uma nova consulta, espere um intervalo de 30 segundos. <br />
		O período de intervalo de consulta é de 45 dias.
	</strong>
</div>

<form name='frm_pesquisa' method='POST' action='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Relatório de SMS Enviados</div>
	<br />
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Data Inicial</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Data Final</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$data_final?>">
				</div>
			</div>
   		</div>
   		<div class="span3">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Status</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select name='status' id='status' class='span12'>
					<option value='0' <?if($status=='0') echo " SELECTED ";?>>Todos</option>
					<option value='1' <?if($status=='1') echo " SELECTED ";?>>Enviada com Sucesso</option>
					<option value='2' <?if($status=='2') echo " SELECTED ";?>>Erro no Envio</option>
				</select>
				</div>
			</div>
   		</div>
   		<?php?>
   		<div class="span3">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Origem do Envio</label>
				<div class='controls controls-row'>
					<select name='origem_envio' id='origem_envio' class='span12'>
					<option value='0' <?php if($origem_envio == '0') echo " SELECTED "; ?>> Todos </option>
					<option value='1' <?php if($origem_envio == '1') echo " SELECTED "; ?>> Enviado pela Ordem de Serviço </option>
					<?php if(!in_array($login_fabrica, array(3,11))){ ?>
						<option value='2' <?php if($origem_envio == '2') echo " SELECTED "; ?>> Enviado pelo Call-Center </option>
						<?php if(!in_array($login_fabrica, array(3,80,101,104))){ ?>
							<option value='3' <?php if($origem_envio == '3') echo " SELECTED "; ?>> Enviado pela Providência </option>
						<?php } ?>
					<?php } ?>
				</select>
				</div>
			</div>
   		</div>
   		<div class="span1"></div>
   	</div>
	<br />
	<div class="row-fluid">
        <!-- margem -->
        <div class="span1"></div>

        <div class="span10">
            <div class="control-group">
                <div class="controls controls-row tac">
                	<span class="msg-btn-pesquisar"></span>
                    <input type="hidden" name="btn_acao"  value=''>
                    <button class="btn btn-pesquisar" name="bt" value='Listar' onclick="javascript:if (document.frm_pesquisa.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_pesquisa.btn_acao.value='Listar';document.frm_pesquisa.submit();}" >Pesquisar</button>
                </div>
            </div>
        </div>

        <!-- margem -->
        <div class="span4"> </div>
    </div>
</form>

<?php  

if (strlen($btn_acao) > 0 && $msg_erro == null) { 

?>

<style>
	.btn-pesquisar{
		display: none;
	}
</style>

<script>

	function habilita_btn_pesquisar(){

		var segundos = 30;
		setInterval(function(){
		  	$(".msg-btn-pesquisar").html("O botão para pesquisar irá aparecer dentro de <strong class='text-error	'>"+segundos+" segundos</strong>... por favor aguarde!");
		  	if(parseInt(segundos) == 0){
		  		$(".msg-btn-pesquisar").html("");
				$(".btn-pesquisar").show();
		  		return false;
		  	}
		  	segundos--;
		}, 500);

	}

	window.setTimeout(function(){
		habilita_btn_pesquisar();
	}, 500);

</script>

<?php

	// Nome do arquivo
	$fileName = "relatorio_sms_{$login_admin}.csv";

	$valor_unitario_sms = (in_array($login_fabrica, array(151))) ? 0.09 : 0.15;
	$valor_unitario_sms_desc = "R$ ".number_format($valor_unitario_sms, 2, ",", ".");

?>

<br />

<div class="alert alert-warning">
	<strong>
		A cada 160 caracteres é consumido 1 crédito.
	</strong>
</div>

<br />

<div class="btn_excel" onclick="javascript: window.location='xls/<?php echo $fileName; ?>';">		    
    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
</div>

<br />

<div class="alert alert-info">
	Valor unitário por SMS: <strong>R$ <?php echo $valor_unitario_sms_desc; ?></strong>
</div>

</div> <!-- Container -->

<br />

<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" style="width: 1180px;">
	<table class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class='titulo_coluna'>
				<!-- <td>Nome do Posto</td> -->
				<td class="tac"><?=(in_array($login_fabrica, array(169,170))) ? "Atendimento/OS" : "OS"?></td>
				<?php if (!in_array($login_fabrica, array(169,170)) && $sms_callcenter): ?>
				<td class="tac">Call-Center &nbsp; </td>
				<?php endif ?>
				<td class="tac">Data Envio</td>
				<td class="tac">Destinatário</td>
				<td class="tac">Origem do Envio</td>
				<td class="tac">Status Mensagem</td>
				<td class="tac">Conteúdo do SMS</td>
				<td class="tac">Créditos Consumidos</td>
				<td class="tac">Valor SMS &nbsp; </td>
			</tr>
		</thead>
		<tbody>
<?php
	// Padrão de relatório, como a tela virou multifábrica, dvemos parametrizar o padrão...

	$tipo_relatorio = in_array($login_fabrica, array(151)) ? 'OS' : null;
	$prot = null;
	$tableCount = count($tabela);

	/* $tr = '<tr>'.
		'<td>%s</td>' .
		str_repeat('<td>%s</td>', 5+($sms_callcenter)) .
		'</tr>'; */

	$status = $_REQUEST['status'];

	/**
	 * Chaves nos ítens do retorno:
		Receiver: <fone do destinatário>
		Content: <texto da mensagem>
		Status:  <Status simples: 0 erro, 1 enviado>
		ScheduleDate: <data de agendamento, ou null>
		RequestDate: <data ed solicitação:
		SystemMessage: <mensagem de status do sistema SMS>
		DlrStatus: <status, padrão DLR>
		Sender: <tel. remetente (callerID)>
	 */

	/**
	 * Ao invés de processar linha por linha e realizar n*n consultas no banco,
	 * percorrer o JSON, separar posto por OS e CallCenter conforme as regras,
	 * recuperar as informações dos postos de uma só vez e só depois montar a
	 * tabela de resultados.
	 */

	$PostoOS = array();
	$PostoCC = array();
	$listaIDX = array();

	if (isset($tabela) and count($tabela)) {

		// $csv_header = 'Nome do Posto;OS;';
		if (in_array($login_fabrica, array(169,170))) {
			$csv_header = 'Atendimento/OS;';
		} else {
			$csv_header = 'OS;';
		}

		if (!in_array($login_fabrica, array(169, 170)) && $sms_callcenter) {
			$csv_header .= 'Call-Center;';
		}

		$csv_header.= 'Data Envio;Destinatário;Origem de Envio;Status Mensagem;Conteúdo do SMS;Créditos Consumidos;Valor SMS;';
		$csv = '';

		//$reTipoAt = "(?P<tipo>Protocolo|protocolo\sde\satendimento|OS|O\.S|ordem\sde\serv)\D+(?P<tipoAtID>\d+)";
		//if ($login_fabrica != 3)
			//$reTipoAt .= '\.';
		$reTipoAt = "(Protocolo|protocolo\sde\satendimento|OS|O\.S|ordem\sde\sserv)\D+(\d+)";

		foreach ($tabela as $i => $sms) {
			preg_match("/$reTipoAt/", str_replace(array("!", ":", ",", "."), "", retira_acentos(utf8_decode($sms['Content']))), $tipoAtInfo);

			if(in_array($login_fabrica, array(104))){
				
				list($conteudo1, $conteudo2) = explode(" OS ", $sms["Content"]);

				if(strlen($conteudo2) > 0){

					$conteudo2_arr = explode(" ", $conteudo2);

					$os_sms = str_replace(array(",", "."), "", $conteudo2_arr[0]);

					if(is_numeric($os_sms)){
						$prot = $os_sms;
						$tipo_relatorio = "OS";
					}

				}else{

					list($conteudo1, $conteudo2) = explode("Protocolo de Atendimento OVD ", $sms["Content"]);

					if(strlen($conteudo2) > 0){

						$conteudo2_arr = explode(" ", $conteudo2);

						$protocolo_sms = str_replace(array(",", "."), "", $conteudo2_arr[0]);

						if(is_numeric($protocolo_sms)){
							$prot = $protocolo_sms;
							$tipo_relatorio = "Protocolo";
						}

					}else{

						$prot = "Pedido de Peças";
						$tipo_relatorio = "OS";

					}

				}

			}else{

				list( ,$tipo_relatorio, $prot) = $tipoAtInfo;

			}

			if(!in_array($prot, $listaIDX)){
				$listaIDX[] = $prot;
			}

			$tipo_relatorio = (in_array($tipo_relatorio, array("OS", "O.S", "ordem de serv"))) ? 'OS' : $tipo_relatorio;


			/* pecho("$tipo_relatorio ID $prot:"); */

			$data_envio_sms = $sms["RequestDate"];

			list($data_envio, $hora_envio) = explode("T", $data_envio_sms);
			list($ano, $mes, $dia) = explode("-", $data_envio);

			$data_envio_valor = $data_envio;
			$data_envio = $dia."/".$mes."/".$ano;

			list($hora, $m) = explode(".", $hora_envio);

			$data_envio_sms = $data_envio." ".$hora;

			if (in_array(strtolower($tipo_relatorio), array("protocolo", "protocolo de atendimento"))) {
				$Postos['CC'.$prot][] = array(
					'',
					'',
					$prot,
					$data_envio_sms,
					$sms['Receiver'],
					$sms['Status'],
					$sms['Content'],
					$data_envio_valor
				);
				$PostoCC[] = $prot;
			}

			if ($tipo_relatorio == 'OS') {

				$Postos['OS'.$prot][] = array(
					'Sem dados da OS',
					$prot,
					'',
					$data_envio_sms,
					$sms['Receiver'],
					$sms['Status'],
					$sms['Content'],
					$data_envio_valor
				);

				$PostoOS[] = $prot;

			}
		}

		/**
		 * Consulta os dados dos postos
		 */
		if (count($PostoOS) > 0) {

			$postos_arr = array();

			foreach ($PostoOS as $os) {
				if(is_numeric($os)){
					$postos_arr[] = $os;
				}
			}

			$sql = "SELECT tbl_os.sua_os, tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS nomeposto
				      FROM tbl_os
				      JOIN tbl_posto
				       	ON tbl_posto.posto           = tbl_os.posto
				      JOIN tbl_posto_fabrica
				       	ON tbl_posto_fabrica.posto   = tbl_posto.posto
				          AND tbl_posto_fabrica.fabrica = $login_fabrica
					 WHERE ". sql_where(array('tbl_os.sua_os' => $PostoOS));

			$dadosPostosOS = pg_fetch_pairs($con, $sql);

			if (!is_array($dadosPostosOS))
				$msg_erro = 'Erro ao consultar os dados dos Postos!';
			else {
				// pre_echo($dadosPostosOS, 'INFO POSTOS');
				foreach ($dadosPostosOS as $idx => $nomePosto) {
					// echo "IDX OS$idx : ".array_key_exists("OS$idx", $Postos)
					$Postos['OS'.$idx][$idx][0] = $nomePosto;
				}
			}
		}

		if (count($PostoCC)) {
			$sql = "SELECT tbl_hd_chamado_extra.hd_chamado,
						   tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS nomeposto
					  FROM tbl_hd_chamado_extra
					  JOIN tbl_posto
						   ON tbl_posto.posto           = tbl_hd_chamado_extra.posto
					  JOIN tbl_posto_fabrica
						   ON tbl_posto_fabrica.posto   = tbl_posto.posto
						  AND tbl_posto_fabrica.fabrica = $login_fabrica
					 WHERE " . sql_where(array('hd_chamado' => $PostoCC));

			$dadosPostosCC = pg_fetch_pairs($con, $sql);

			if (!is_array($dadosPostosCC))
				$msg_erro = 'Erro ao consultar os dados dos Postos Autorizados!';
			else {
				foreach ($dadosPostosCC as $idx => $nomePosto) {
					$Postos['CC'.$idx][$idx][0] = $nomePosto;
				}
			}
		}

		$valor_total_sms = 0;
		$valor_total_creditos = 0;

		/* pg_prepare($con, "verifica_os_fabrica_os_sms", "SELECT os FROM tbl_os WHERE os = $1 AND fabrica = {$login_fabrica}");
		pg_prepare($con, "verifica_os_fabrica_callcenter_sms", "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado = $1 AND fabrica = {$login_fabrica}"); */

		foreach($listaIDX as $IDX) {

			$row = null;

			if (array_key_exists('OS'.$IDX, $Postos)) {
				$row = $Postos['OS'.$IDX];
			}

			if (array_key_exists('CC'.$IDX, $Postos)) {
				$row = $Postos['CC'.$IDX];
			}

			if (is_null($row))
				continue;

			if(count($row) > 0){
				foreach ($row as $r) {

					list($posto, $os, $protocolo, $envio, $dest, $statusId, $conteudo_sms, $data_envio_valor) = $r;

					if ($status > 0 and $status != $statusId)
						continue;

					// Se pediu mensagens enviadas, pula SMS com erro...
					if ($status == 1 and $statusId == 2)
						continue;

					// Se pediu mensagens não enviadas (erro), pula as que não tem erro
					if ($status == 2 and $statusId == 1)
						continue;

					if(strlen($envio) == 0 && strlen($dest) == 0){
						continue; 
					}

					/* if(strlen($os) > 0){
						$res_sms = pg_execute($con, "verifica_os_fabrica_os_sms", array($os));
						if(pg_num_rows($res_sms) == 0){
							$os = "Não Informado";
						}
					}
					
					if(strlen($protocolo) > 0){
						$res_sms = pg_execute($con, "verifica_os_fabrica_callcenter_sms", array($protocolo));
						if(pg_num_rows($res_sms) == 0){
							$protocolo = "Não Informado";
						}
					} */

					$statusSms = (int)$statusId === 2 ? 'Erro no Envio' : 'Enviada com Sucesso';
					$dest = phone_format(substr($dest, 2));

					$conteudo_sms = utf8_decode($conteudo_sms);

					if(in_array($login_fabrica, array(11))){

						$origem_envio_desc = "Ordem de Serviço";

					}else{

						$origem_envio_desc = "Enviado pela Providência";

						if(strstr($conteudo_sms, "Protocolo de Atendimento", true) || strlen($protocolo) > 0){
							
							$origem_envio_desc = "Enviado pelo Call-Center";
						
						}else if(strstr($conteudo_sms, "retirada com brevidade", true) || strlen($os) > 0){
							
							$origem_envio_desc = (in_array($login_fabrica, array(80,101,104,151))) ? "Enviado pela Ordem de Serviço" : "Enviado pelo Posto";
						
						}

						if(in_array($login_fabrica, array(151))){

							if($origem_envio_desc == "Enviado pelo Call-Center"){

								if(strstr($conteudo_sms, "Protocolo de Atendimento")){
									$origem_envio_desc = "Enviado pelo Call-Center";
								}else{
									$origem_envio_desc = "Enviado pela Providência";	
								}

							}

						}

						$origem_envio = $_REQUEST["origem_envio"];

						if($origem_envio == '1'){
							if($origem_envio_desc != "Enviado pelo Posto" && $origem_envio_desc != "Enviado pela Ordem de Serviço"){
								continue;
							}
						}

						if($origem_envio == '2'){
							if($origem_envio_desc != "Enviado pelo Call-Center"){
								continue;
							}
						}

						if($origem_envio == '3'){
							if($origem_envio_desc != "Enviado pela Providência"){
								continue;
							}
						}

					}

					if(in_array($login_fabrica, array(151))){

						$valor_unitario_sms = (strtotime($data_envio_valor) >= strtotime("2017-04-01")) ? 0.09 : 0.13;

					}

					$unidade_credito = 160;
					$qtde_caracteres = strlen($conteudo_sms);
					$qtde_creditos_consumidos = ceil((int)$qtde_caracteres / $unidade_credito);

					$valor_sms = $qtde_creditos_consumidos * $valor_unitario_sms;
					$valor_total_sms += $valor_sms;

					$valor_total_creditos += $qtde_creditos_consumidos;

					if (in_array($login_fabrica, array(169,170))) {
						$os_call_center = "Atendimento/OS: <strong>{$os}</strong>";
					} else {
						$os_call_center = (strlen(trim($os)) > 0) ? "OS: <strong>{$os}</strong>" : "Call-Center: <strong>{$protocolo}</strong>";
					}

					$conteudo_sms_header = "
						{$os_call_center} <br /> 
						Envio: <strong>{$envio}</strong> <br /> 
						Origem de Envio: <strong>{$origem_envio_desc}</strong> <br />
						Status Mensagem: <strong>{$statusSms}</strong> <br /> <br />
						<strong>Mensagem:</strong> <br />
					";

					$visualizar_conteudo_sms = str_replace("\n", "", $conteudo_sms_header).$conteudo_sms;

					$valor_sms = "R$ ".number_format($valor_sms, 2, ",", ".");

					echo "<tr>";
						if (!in_array($login_fabrica, array(169,170))) {
							echo "<td>{$os}</td>";
							echo ($sms_callcenter) ? "<td>{$protocolo}</td>" : "";
						} else {
							echo "<td>".((empty($os)) ? $protocolo : $os)."</td>";
						}
						echo "<td>{$envio}</td>";
						echo "<td>{$dest}</td>";
						echo "<td>{$origem_envio_desc}</td>";
						echo "<td>{$statusSms}</td>";
						echo "<td class='tac'> <button type='button' onclick='conteudo_sms(\"{$visualizar_conteudo_sms}\");' class='btn btn-success'> Ver Mensagem </button> </td>";
						echo "<td class='tac'>{$qtde_creditos_consumidos}</td>";
						echo "<td class='tac'>{$valor_sms}</td>";
					echo "</tr>";

					$protocolo_callcenter = (!in_array($login_fabrica, array(169, 170)) && $sms_callcenter) ? $protocolo.";" : "";
					if (in_array($login_fabrica, array(169,170))) {
						$os = (empty($os)) ? $protocolo : $os;
					}

					$conteudo_sms = html_entity_decode($conteudo_sms);

					$csv .= PHP_EOL . "{$os};{$protocolo_callcenter}{$envio};{$dest};{$origem_envio_desc};{$statusSms};{$conteudo_sms};{$qtde_creditos_consumidos};{$valor_sms};";
					
				}

			}

		}

	}

	$temCSV = false;

	if (strlen($csv)) {

		$file = fopen("/tmp/{$fileName}", "w");
		fwrite($file, $csv_header . $csv);
		fclose($file);

		if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");

			$temCSV = true;

		}

	}

?>
		</tbody>

		<tfoot>
			<tr>
				<th colspan="<?php echo (!in_array($login_fabrica, array(169,170)) && $sms_callcenter) ? 7 : 6; ?>" style='text-align: right !important;'> Total: </th>
				<th><?php echo $valor_total_creditos; ?></td>
				<th>R$ <?php echo number_format($valor_total_sms, 2, ",", "."); ?></th>
			</tr>
		</tfoot>

	</table>

	<?php if ($temCSV){ ?>

	<br />

	<div class="btn_excel" onclick="javascript: window.location='xls/<?php echo $fileName; ?>';">		    
	    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
	    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
	</div>

	<br />

<?php
	} // CSV
} ?>

<? include_once "rodape.php";

