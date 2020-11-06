<?PHP

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="call_center";
include "autentica_admin.php";

# Fábricas que tem permissão para esta tela
if(!in_array($login_fabrica, array(1))) {
	header("Location: menu_callcenter.php");
	exit;
}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ADVERTÊNCIA / BOLETIM DE OCORRÊNCIA";

if ($_POST['gerar_excel'] == 'true'){

		$_POST['data_inicial'] = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $_POST['data_inicial']);
		$_POST['data_final']   = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $_POST['data_final']);

		$dt_ini = $_POST['data_inicial'];
		$dt_fin = $_POST['data_final'];

		$nao_possui_data = empty($_POST['data_inicial']) && empty($_POST['data_final']);


		if($_POST['admin_sap'] != ""){
			$where_admin = " and tbl_posto_fabrica.admin_sap = ".$_POST['admin_sap'];
		}else{
			$where_admin = "";
		}

		if($_POST['posto_sap'] != ""){
			$where_posto = " and tbl_posto_fabrica.posto = ".$_POST['posto_sap'];

		}else{
			$where_posto = "";
		}

		 $sql = "SELECT posto
		 		FROM tbl_posto_fabrica
		 		WHERE codigo_posto = '$post->codigo_posto'
		 		AND fabrica = 1";

		$res = pg_query($con, $sql);
		$id_posto = pg_fetch_result($res, 0, 0);
		$post = (object)$_POST;

		$sql = "SELECT advertencia,
					   to_char (tbl_advertencia.data_input, 'DD/MM/YYYY') as data_input,
					   to_char (tbl_advertencia.data_concluido, 'DD/MM/YYYY') as data_concluido,
					   tbl_posto_fabrica.codigo_posto,
					   tbl_posto.nome as posto_nome,
					   tbl_tipo_posto.descricao AS tipo_posto_desc,
					   tbl_tipo_ocorrencia.descricao,
					   tbl_admin.nome_completo,
					   tbl_advertencia.numero_sac,
					   tbl_advertencia.numero_advertencia,
					   tbl_advertencia.tipo_ocorrencia,
					   tbl_advertencia.contato_posto,
					   tbl_produto.referencia ||'-'|| tbl_produto.descricao AS produto_descricao,
					   tbl_os.os,
					   tbl_os.sua_os,
					   posto_os.codigo_posto AS codigo_posto_os,
					   tbl_advertencia.parametros_adicionais->>'nivel_falha' AS nivel_falha,
					   tbl_advertencia.parametros_adicionais->>'tipo_falha' AS tipo_falha,
					   tbl_advertencia.parametros_adicionais->>'tratativa_atendimento' AS tratativa_atendimento,
					   tbl_advertencia.parametros_adicionais->>'acao_bo' AS acao_bo,
					   tbl_advertencia.parametros_adicionais->>'outros_explicacao' AS outros_explicacao
				FROM tbl_advertencia
				INNER JOIN tbl_posto_fabrica
					ON (tbl_advertencia.posto = tbl_posto_fabrica.posto AND tbl_advertencia.fabrica = tbl_posto_fabrica.fabrica)
				INNER JOIN tbl_posto
					ON (tbl_posto_fabrica.posto = tbl_posto.posto)
				LEFT JOIN tbl_os
					ON(tbl_advertencia.os = tbl_os.os)
				LEFT JOIN tbl_posto_fabrica AS posto_os
					ON(tbl_os.posto = posto_os.posto AND tbl_os.fabrica = posto_os.fabrica)
				LEFT JOIN tbl_tipo_posto 
					ON (tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto)
				LEFT JOIN tbl_produto
					ON(tbl_advertencia.produto = tbl_produto.produto)
				INNER JOIN tbl_admin
					ON (tbl_admin.admin = tbl_advertencia.admin)
				LEFT JOIN tbl_tipo_ocorrencia
					ON (tbl_tipo_ocorrencia.tipo_ocorrencia = tbl_advertencia.tipo_ocorrencia)
				WHERE " . (!$nao_possui_data ? "tbl_advertencia.data_input BETWEEN '$dt_ini 00:00:00' AND '$dt_fin 23:59:59'   ".$where_admin. $where_posto  :  "1=1");

		if(trim($_POST['advertencia']) 		!= "") $sql .= "AND tbl_advertencia.advertencia  	= ".$_POST['adverterncia'];
		if(trim($_POST['codigo_posto']) 	!= "") $sql .= "AND tbl_advertencia.posto  			= $id_posto";
		if(trim($_POST['tipo_ocorrencia'])  != "") $sql .= "AND tbl_advertencia.tipo_ocorrencia = ".$_POST['tipo_ocorrencia'];
		if(trim($_POST['atendente']) 		!= "") $sql .= "AND tbl_advertencia.admin 			= ".$_POST['atendente'];
		if(trim($_POST['statuss']) 			!= "") $sql .= "AND tbl_advertencia.data_concluido	IS " . ($_POST['statuss'] ? "NOT NULL " : "NULL ");

		if(trim($_POST['tipo_relatorio']) == "advertencia") :
			$sql .= "AND tbl_advertencia.tipo_ocorrencia IS NULL ";
		elseif(trim($_POST['tipo_relatorio']) == "boletim") :
			$sql .= "AND tbl_advertencia.tipo_ocorrencia IS NOT NULL ";
		endif;
		$sql.= " AND tbl_advertencia.fabrica = $login_fabrica ";

		if ($_POST['nivel_falha'] != "") {
			$sql.= " AND tbl_advertencia.parametros_adicionais->>'nivel_falha' = '$post->nivel_falha' ";
		}

		if ($_POST['tipo_falha'] != "") {
			$sql.= " AND tbl_advertencia.parametros_adicionais->>'tipo_falha' = '$post->tipo_falha' ";
		}

		if ($_POST['tratativa_atendimento'] != "") {
			$sql.= " AND tbl_advertencia.parametros_adicionais->>'tratativa_atendimento' = '$post->tratativa_atendimento' ";
		}

		$sql .= " ORDER BY advertencia DESC,tbl_posto.nome, data_input";

		$res = pg_query($con, $sql);

		while($advertencia = pg_fetch_object($res)) {
			$advertencias[$advertencia->codigo_posto][] = $advertencia;
		}

		if(count($advertencias)) {

		$dateTime = new DateTime('now');
		$data = $dateTime->format(DateTime::ISO8601);
		$fileName = "relatorio_advertencia_bo-{$data}.xls";

		$file = fopen("/tmp/{$fileName}", "w");

		$thead = "
				<table border='1'>
				<thead>
					<tr>
						<th colspan='18' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
							RELATÓRIO DE ADVERTÊNCIA / BOLETIM DE OCORRÊNCIA
						</th>
					</tr>
					<tr>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>B.O / Advertência</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>SAC/Suporte</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo do Posto</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Posto</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Contato do Posto</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fechamento</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Ocorrência</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Admin Responsável HD</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nível da Falha</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tratativa do Atendimento</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Falha</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ação Tomada</th>
						</thead>
						";
		fwrite($file, $thead);

				$arrayNivelFalha = ["leve"=>"Leve", "medio"=>"Médio", "alto"=>"Alto"];
	            $arrayTratativa  = ["devolucao"=>"Devolução de Valor", "reparo"=>"Reparo", "troca"=>"Troca do Produto"];
	            $arrayFalha      = ["duvida_tecnica"=>"Falta de Comunicação C/ o Suporte Ref. à Dúvidas Técnicas", 
	            					"pendencia_peca"=>"Falta de Comunicação C/ o Suporte Ref. à Pendência de Peça",
	            					"telecontrol"=>"Falta de Comunicação C/ o Suporte Ref. à Dúvida na Utilização do Sistema Telecontrol",
	            					"demora_analise"=>"Demora na Análise do Produto (Sem Pedido de Peças)",
	            					"demora_realizar"=>"Demora em Realizar Pedido de Peças",
	            					"procedimentos_incorretos"=>"Realização de Procedimentos Incorretos"
	            				   ];
	            $arrayBO 		 = ["acompanhamento"=>"Acompanhamento", 
									"orientacao_verbal"=>"Orientação Verbal", 
									"orientacao_escrita"=>"Orientação Escrita",
									"advertencia"=>"Advertência",
									"descredenciamento"=>"Descredenciamento",
									"outros"=>"Outros"
								   ];

				foreach ($advertencias as $posto => $advertencias) :

				foreach($advertencias as $advertencia):

					$nivel_falha = "";
					$tipo_falha = "";
					$tratativa_atendimento = "";
					$acao_bo = "";
					$bo_ad = "";

					$os = $advertencia->codigo_posto_os.$advertencia->sua_os;

					$status =  (empty($advertencia->data_concluido)) ? "Pendente" : "Finalizado";
					if(!empty($advertencia->tipo_ocorrencia)) {
						$bo_ad = "Boletim de Ocorrência";
					}

					$numero = $advertencia->advertencia;
					if(empty($advertencia->tipo_ocorrencia)) {
						$numero.= '-'.$advertencia->numero_advertencia;
						$bo_ad = "Advertência";
					}

					if (!empty($advertencia->nivel_falha)) {
						foreach ($arrayNivelFalha as $key => $value) {
							if ($advertencia->nivel_falha == $key) {
								$nivel_falha = $value;
								break;
							}
						}
					}

					if (!empty($advertencia->tipo_falha)) {
						foreach ($arrayFalha as $key => $value) {
							if ($advertencia->tipo_falha == $key) {
								$tipo_falha = $value;
								break;
							}	
						}
					}

					if (!empty($advertencia->tratativa_atendimento)) {
						foreach ($arrayTratativa as $key => $value) {
							if ($advertencia->tratativa_atendimento == $key) {
								$tratativa_atendimento = $value;
								break;
							}	
						}
					}

					if (!empty($advertencia->acao_bo)) {
						foreach ($arrayBO as $key => $value) {
							if ($advertencia->acao_bo == $key) {
								$acao_bo = $value;
								break;
							}	
						}
					}

					if ($advertencia->acao_bo == 'outros') {
						$outros_explicacao = $advertencia->outros_explicacao;
						$str       = str_replace('\u','u',$outros_explicacao);
						$outros_explicacao = preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $str);
						$outros_explicacao = (mb_check_encoding($outros_explicacao, "UTF-8")) ? utf8_decode($outros_explicacao) : $outros_explicacao;
						$acao_bo = $acao_bo ." - ".$outros_explicacao;
					}

					// condição de tipo_ocorrencia para pegar só b.o pois somente eles tem hd chamado
					$admin_resp = '';
					if (!empty($advertencia->numero_sac) && !empty($advertencia->tipo_ocorrencia)) {
						$hdId = explode("-", $advertencia->numero_sac);
						
						$sCondicoes = " AND tbl_hd_chamado.hd_chamado = ".$hdId[0];

						if (count($hdId) > 1) {
							$sCondicoes .=  " AND (tbl_hd_chamado.protocolo_cliente = '".$hdId[0]."' OR tbl_hd_chamado.hd_chamado = ".$hdId[0]." OR tbl_hd_chamado.hd_chamado_anterior = ".$hdId[0].")";
						}

						$sql_resp = "	SELECT nome_completo 
										FROM tbl_hd_chamado 
										JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente 
										WHERE fabrica_responsavel = $login_fabrica
										$sCondicoes";
						$res_resp = pg_query($con, $sql_resp);
						if (pg_num_rows($res_resp) > 0) {
							$admin_resp = pg_fetch_result($res_resp, 0, 'nome_completo');
						}
					}

					$xcontato_posto = (mb_check_encoding($advertencia->contato_posto, "UTF-8")) ? utf8_decode($advertencia->contato_posto) : $advertencia->contato_posto;

					$body =  "<tbody>
								<tr id='$advertencia->advertencia'>
								<td>".$os."</td>
								<td class='tal'>".$bo_ad."</td>
								<td>".$advertencia->numero_sac."</td>
								<td>".$advertencia->produto_descricao."</td>
								<td>".$advertencia->tipo_posto_desc."</td>
								<td class='tal'>".$advertencias[0]->codigo_posto ."-". $advertencias[0]->posto_nome ."</td>
								<td class='tal'>". $xcontato_posto ."</td>
								<td class='tac'>$advertencia->data_input</td>
								<td class='tac' name='advertencia'>".$numero."</td>
								<td class='tac' name='data_fechamento'>$advertencia->data_concluido</td>
								<td class='tac'>" . (empty($advertencia->descricao) ? "Advertência" : $advertencia->descricao). "</td>
								<td class='tac'>".$advertencia->nome_completo."</td>
								<td class='tac'>".$admin_resp."</td>
								<td class='tac' name='statuss'>" .$status. "</td>
								<td class='tac' name='statuss'>" .$nivel_falha. "</td>
								<td class='tac' name='statuss'>" .$tratativa_atendimento. "</td>
								<td class='tac' name='statuss'>" .$tipo_falha. "</td>
								<td class='tac' name='statuss'>" .$acao_bo. "</td>
							</tr>
							</tdbody>
							";
					fwrite($file, $body);

				endforeach;

			endforeach;

		fclose($file);

		if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");

			echo "xls/{$fileName}";
		}
	}


		exit;
}

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

<script type="text/javascript" src="js/relatorio_advertencia_bo.js"></script>


<script language="javascript">

    // From http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

	$(function() {

		$("#consultar").click(function(){
			objExcel = new Object();

			objExcel.gerar_excel = "true";

			if($("#data_inicial").val() != ""){
				objExcel.data_inicial = $("#data_inicial").val();
			}else{
				objExcel.data_inicial = "";
			}

			if($("#data_final").val() != ""){
				objExcel.data_final = $("#data_final").val();
			}else{
				objExcel.data_final = "";
			}

			if($("#nivel_falha").val() != ""){
				objExcel.nivel_falha = $("#nivel_falha").val();
			}else{
				objExcel.nivel_falha = "";
			}

			if($("#tratativa_atendimento").val() != ""){
				objExcel.tratativa_atendimento = $("#tratativa_atendimento").val();
			}else{
				objExcel.tratativa_atendimento = "";
			}

			if($("#tipo_falha").val() != ""){
				objExcel.tipo_falha = $("#tipo_falha").val();
			}else{
				objExcel.tipo_falha = "";
			}

			if($("#codigo_posto").val() != ""){
				objExcel.codigo_posto = $("#codigo_posto").val();
			}else{
				objExcel.codigo_posto = "";
			}

			if($("#descricao_posto").val() != ""){
				objExcel.descricao_posto = $("#descricao_posto").val();
			}else{
				objExcel.descricao_posto = "";
			}

			if($("#advertencia").val() != ""){
				objExcel.advertencia = $("#advertencia").val();
			}else{
				objExcel.advertencia = "";
			}

			if($("#statuss").val() != ""){
				objExcel.statuss = $("#statuss").val();
			}else{
				objExcel.statuss = "";
			}

			if($("#tipo_ocorrencia").val() != ""){
				objExcel.tipo_ocorrencia = $("#tipo_ocorrencia").val();
			}else{
				objExcel.tipo_ocorrencia = "";
			}

			if($("#atendente").val() != ""){
				objExcel.atendente = $("#atendente").val();
			}else{
				objExcel.atendente = "";
			}

			if($("#admin_sap").val() != ""){
				objExcel.admin_sap = $("#admin_sap").val();
			}else{
				objExcel.admin_sap = "";
			}

			if($("#posto_sap").val() != ""){
				objExcel.posto_sap = $("#posto_sap").val();
			}else{
				objExcel.posto_sap = "";
			}


			$("#jsonPOST").val(JSON.stringify(objExcel));
		});

        var bo = getParameterByName('bo');

        if (bo) {
            $("#advertencia").val(bo);
            $("#consultar").click();
        }

	});
</script>

<?
	# Busca e armazena os tipos de ocorrência
	$sql = "SELECT tipo_ocorrencia,
				   descricao
			FROM tbl_tipo_ocorrencia
			WHERE fabrica = $login_fabrica
			ORDER BY tipo_ocorrencia";

	$res = pg_query($con, $sql);

	while($ocorrencia = pg_fetch_object($res)) {
		$tipos_ocorrencia[] = $ocorrencia;
	}

	# Busca e armazena os atendentes ATIVOS
	$sql = "SELECT nome_completo,
				   admin
			FROM tbl_admin
			WHERE (ativo IS TRUE OR (admin_sap IS TRUE AND ativo))
			AND   fabrica = $login_fabrica
			ORDER BY nome_completo";

	$res = pg_query($con, $sql);

	while($admin = pg_fetch_object($res)) {
		$atendentes[] = $admin;
	}

	# Busca e armazena os atendentes ativos com privilégio SAP

	$sql = "SELECT nome_completo,
				   admin
			FROM tbl_admin
			WHERE (admin_sap IS TRUE AND ativo)
			AND   fabrica = $login_fabrica
			ORDER BY nome_completo";

	$res = pg_query($con, $sql);

	while($admin_sap = pg_fetch_object($res)) {
		$atendentes_sap[] = $admin_sap;
	}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='consultar' align='center' class='form-search form-inline tc_formulario'>

	<?
		$msg_erro = $_GET["msg_erro"];

			if(trim($msg_erro) != "") {
		?>
	<? } ?>

	<div class="titulo_tabela">Relatório de advertência / Boletim de ocorrência</div>
	<br />

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='data_final'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span2'>
			<div class='control-group'>
				<label class='control-label' for='nivel_falha'>Nível de Falha</label>
				<div class='controls'>
					<div class='span12'>
						<select name="nivel_falha" id="nivel_falha" class="span12">
							<option></option>
							<option value="leve">Leve</option>
							<option value="medio">Médio</option>
							<option value="alto">Alto</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group'>
				<label class='control-label' for='tratativa_atendimento'>Tratativa do Atendimento</label>
				<div class='controls'>
					<div class='span12'>
						<select name="tratativa_atendimento" id="tratativa_atendimento" class="span12">
							<option></option>
							<option value="devolucao">Devolução de Valor</option>
							<option value="reparo">Reparo</option>
							<option value="troca">Troca do Produto</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group'>
				<label class='control-label' for='tipo_falha'>Tipo de Falha</label>
				<div class='controls'>
					<div class='span12'>
						<select name="tipo_falha" id="tipo_falha" class="span12">
							<option></option>
							<option value="duvida_tecnica">Falta de Comunicação C/ o Suporte Ref. à Dúvidas Técnicas</option>
							<option value="pendencia_peca">Falta de Comunicação C/ o Suporte Ref. à Pendência de Peça</option>
							<option value="telecontrol">Falta de Comunicação C/ o Suporte Ref. à Dúvida na Utilização do Sistema Telecontrol</option>
							<option value="demora_analise">Demora na Análise do Produto (Sem Pedido de Peças)</option>
							<option value="demora_realizar">Demora em Realizar Pedido de Peças</option>
							<option value="procedimentos_incorretos">Realização de Procedimentos Incorretos</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Código do posto</label>
				<div class='controls controls-row'>
					<div class='span8 input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto"class='span10'>
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='descricao_posto'>Descrição do posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" value="" class='span11'>
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='adverterncia'>Numero do B.O / Advertência</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<input type="number" name="advertencia" id="advertencia" min="0" class="span12" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='statuss'>Status</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name="statuss" id="statuss" class="span12">
							<option></option>
							<option value="1">Finalizado</option>
							<option value="0">Pendente</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for="tipo_ocorrencia">Tipo de ocorrência</label>
				<div class="control control-row">
					<div class="span8">
						<select name="tipo_ocorrencia" id="tipo_ocorrencia" class="span12">
							<option></option>
						<?
							foreach ($tipos_ocorrencia as $ocorrencia) {
								echo "<option value='$ocorrencia->tipo_ocorrencia'>" . $ocorrencia->descricao . "</option>";
							}
						?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for="atendente">Atendente</label>
				<div class="control control-row">
					<div class="span12">
						<select name="atendente" id="atendente" class="span12">
							<option></option>
						<?
							foreach ($atendentes as $admin) {
								echo "<option value='$admin->admin'>" . $admin->nome_completo . "</option>";
							}
						?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for="tipo_ocorrencia">Admin SAP</label>
				<div class="control control-row">
					<div class="span8">
						<select name="admin_sap" id="admin_sap" class="span12">
							<option></option>
						<?
							foreach ($atendentes_sap as $admin_sap) {
								echo "<option value='".$admin_sap->admin."'>" . $admin_sap->nome_completo . "</option>";
							}
						?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="span4">
			<div class="control-group">
				<label class="control-label" for="tipo_ocorrencia">Postos Admin SAP</label>
				<div class="control control-row">
					<div class="span12">
						<select name="posto_sap" id="posto_sap" class="span12">
							<option></option>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>

		<div class="span3">
			<label>
				<input type="radio" name="tipo_relatorio" checked>
				Todos
			</label>
		</div>
		<div class="span3">
			<label>
				<input type="radio" name="tipo_relatorio" value="advertencia">
				Advertência
			</label>
		</div>
		<div class="span3">
			<label>
				<input type="radio" name="tipo_relatorio" value="boletim">
				Boletim de ocorrência
			</label>
		</div>
		<div class="span1"></div>
	</div>

	<p>
		<input type="submit" class="btn" id="consultar" value="Gerar">
	</p><br/>

</form>

</div>

<div class="container" id="legenda" style="display:none">
    <table>
        <tbody>
            <tr>
                <td width="18">
                    <div style="background-color: #FFC176">&nbsp;</div>
                </td>
                <td align="left">
                    &nbsp;<b>Postos: 5SC, 5SB e 5SA</b>
                </td>
            </tr>
            <tr>
                <td width="18">
                    <div style="background-color: #FF9E9E">&nbsp;</div>
                </td>
                <td align="left">
                    &nbsp;<b>Postos com Reincidências de Registros</b>
                </td>
            </tr>
            <tr>
                <td width="18">
                    <div style="background-color: #FFF18D">&nbsp;</div>
                </td>
                <td align="left">
                    &nbsp;<b>Postos 5 Estrelas e com Reincidências de Registros</b>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<br />
<br />
	<table id="tbl_result" class="table table-striped table-bordered table-hover table-large" style="display:none">
		<thead>
			<tr class='titulo_tabela'>
				<th colspan='19'>Relatório de advertência / Boletim de ocorrência</th>
			</tr>
			<tr class='titulo_coluna'>
				<th>OS</th>
				<th>B.O / Advertência</th>
				<th>SAC/Suporte</th>
				<th>Produto</th>
				<th>Tipo do Posto</th>
				<th>Nome Posto</th>
				<th>Contato do Posto</th>
				<th>Data de abertura</th>
				<th>Número da Ocorrência</th>
				<th>Data de fechamento</th>
				<th>Tipo de ocorrência</th>
				<th>Atendente</th>
				<th>Admin Responsável HD</th>
				<th>Status</th>
				<th>Nível da Falha</th>
				<th>Tratativa do Atendimento</th>
				<th>Tipo de Falha</th>
				<th>Ação Tomada</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody id="resultado">

		</tbody>
	</table>

	<div class="container" id="erro_result">
	</div>
	<br />

	<?php
		$jsonPOST = excelPostToJson($_POST);
	?>

	<div id='gerar_excel' class="btn_excel" style="display:none">
		<input type="hidden" id="jsonPOST" value='' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>

<? include "rodape.php"; ?>
