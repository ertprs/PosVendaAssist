 <?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

### HD-6781686 Validar acesso a tela por e-mail
### INICIO
$sqlAdmin = "SELECT email FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin}";
$resAdmin = pg_query($con, $sqlAdmin);

$emailAdmin = pg_fetch_result($resAdmin, 0, 'email');

if(!empty($emailAdmin)){
	$pos_emailAdmin = strpos($emailAdmin, '@');
	$dominioAdmin = substr($emailAdmin, $pos_emailAdmin);
	if($dominioAdmin == "@telecontrol.com.br"){

if ($_POST['valida_data']) {
	$m_i = $_POST['mes_ini'];
	$a_i = $_POST['ano_ini'];
	$d_ini = date('Y-m-01',mktime(0,0,0,$m_i,1,$a_i));
	$dd_ini = new DateTime($d_ini);

	$m_f = $_POST['mes_fin'];
	$a_f = $_POST['ano_fin'];
	$d_fin = date('Y-m-t',mktime(0,0,0,$m_f,1,$a_f));
	$dd_fin = new DateTime($d_fin);

	$diff = $dd_ini->diff($dd_fin);

	if ($diff->y > 1 || ($diff->y == 1 && $diff->m > 1)) {
		exit("erro");
	} else {
		exit("ok");
	}
}

function retorna_fila_telefone2($data_inicial, $data_final) {
	global $filasTelefonia, $login_fabrica;

	if (empty($data_inicial) || empty($data_final)) {
		$msg_erro["msg"][]    = "Data informada inválida";
		$msg_erro["campos"][] = "data";
	}

	if (count($msg_erro) == 0) {
		$resultadoPesquisa = [];
		$responseData = [];

		$filasTelefonia = str_replace("'", "", $filasTelefonia);

		$filasTelefonia = array_map(function ($r) {
			return "'$r'";
		}, $filasTelefonia);

		$queryString = "/inicio/{$data_inicial}/final/{$data_final}/companhia/10/setor/sac/filas/" . urlencode(implode(",", $filasTelefonia)) . "/fabrica/" . $login_fabrica;
		$curlData = curl_init();

		curl_setopt_array($curlData, array(
			CURLOPT_URL => 'https://api2.telecontrol.com.br/telefonia/relatorio-atendentes' . $queryString,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_HTTPHEADER => array(
				"Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
		        "Access-Env: PRODUCTION",
		        "Cache-Control: no-cache",
		        "Content-Type: application/json"
			),
		));

		$responseData[] = json_decode(curl_exec($curlData), true);
		
		if (!empty(curl_error($curl)) OR $responseData['exception']) {

			$msg_erro["msg"][]    = strlen($responseData['exception']) ? $responseData['exception'] : curl_error($curlData);

		} else {
			foreach ($responseData as $key => $value) {
				foreach ($value as $at => $vl) {
					foreach ($vl as $k => $v) {
						$resultadoPesquisa[] = $v['recebidas']['total_ligacoes'];
					}
				}
			}
			foreach ($resultadoPesquisa as $key => $value) {
				if (empty($value)) {
					unset($resultadoPesquisa[$key]);
				}
			}
		}	
		
		curl_close($curlData);
		return $resultadoPesquisa;
	} else {
		return $msg_erro;
	}
}

if ($_POST["gerar_excel"]) {

	$d_i = $_POST['data_inicial'];
	$d_i_array = explode("/", $d_i);
	$m_i = $d_i_array[0];
	$a_i = $d_i_array[1]; 
	$data_inicial = date('Y-m-01',mktime(0,0,0,$m_i,1,$a_i));

	
	$d_f = $_POST['data_final'];
	$d_f_array = explode("/", $d_f);
	$m_f = $d_f_array[0];
	$a_f = $d_f_array[1]; 
	$data_final = date('Y-m-t',mktime(0,0,0,$m_f,1,$a_f));

	$data_hoje = date('d-m-Y');

	$sql_nome_fab = "SELECT nome 
					 FROM tbl_fabrica 
					 WHERE fabrica = $login_fabrica";
	$res_nome_fab = pg_query($con, $sql_nome_fab);
	$nome_fab = pg_fetch_result($res_nome_fab, 0, 'nome');

	$sql_postos_credenciados = "SELECT COUNT(tbl_posto_fabrica.posto) AS postos_credenciados 
								FROM tbl_posto_fabrica 
								WHERE fabrica = $login_fabrica 
								AND credenciamento <> 'DESCREDENCIADO'";
	$res_postos_credenciados = pg_query($con, $sql_postos_credenciados);
	if (pg_num_rows($res_postos_credenciados) > 0) {
		$postos_credenciados = pg_fetch_result($res_postos_credenciados, 0, 'postos_credenciados');
	} else {
		$postos_credenciados = 0;
	}

	$sql_qtde_sku = "SELECT COUNT(1) AS qtde_sku
					 FROM tbl_peca
					 WHERE fabrica = $login_fabrica
					 AND ativo";
	$res_qtde_sku = pg_query($con, $sql_qtde_sku);
	if (pg_num_rows($res_qtde_sku) > 0) {
		$qtde_sku = pg_fetch_result($res_qtde_sku, 0, 'qtde_sku');
	} else {
		$qtde_sku = 0;
	}

	$sql_qtde_peca = "SELECT SUM(tbl_posto_estoque.qtde) AS qtde_peca
					  FROM tbl_peca
					  JOIN tbl_posto_estoque USING(peca)
					  WHERE fabrica = $login_fabrica
					  AND ativo IS TRUE";
	$res_qtde_peca = pg_query($con, $sql_qtde_peca);
	if (pg_num_rows($res_qtde_peca) > 0) {
		$qtde_peca = pg_fetch_result($res_qtde_peca, 0, 'qtde_peca');
	} else {
		$qtde_peca = 0;
	}

	$sql_qtde_atendente = "SELECT COUNT(tbl_admin.admin) AS qtde_atendente
						   FROM tbl_admin
						   WHERE fabrica = $login_fabrica
						   AND ativo IS TRUE
						   AND atendente_callcenter IS TRUE";
	$res_qtde_atendente = pg_query($con, $sql_qtde_atendente);
	if (pg_num_rows($res_qtde_atendente) > 0) {
		$qtde_atendente = pg_fetch_result($res_qtde_atendente, 0, 'qtde_atendente');
	} else {
		$qtde_atendente = 0;
	}

	$sql_data = "	SELECT 	data_digitacao
					FROM tbl_os 
					WHERE fabrica = $login_fabrica
					AND data_digitacao BETWEEN '$data_inicial 00:00' AND '$data_final 00:00'
					ORDER BY data_digitacao ASC limit 1";
	 $res_data = pg_query($con, $sql_data);
	 $data_digitacao = pg_fetch_result($res_data, 0, 'data_digitacao');

	 $sql_mes = "SELECT setup::date as inicial,  to_char(setup::date,'mm/yyyy') as mes_ano
		 FROM generate_series( '$data_digitacao',current_date, INTERVAL '1 month') AS setup  order by 1 ";
	$res_meses = pg_query($con, $sql_mes);
	
	if (pg_num_rows($res_meses) > 0) {
		$total_os_mes_geral = 0;
		$total_pedido_geral = 0;
		$total_atendimento_geral = 0;
		$total_interacao_geral = 0;
		$total_telefonia_geral = 0;
		$total_nfe_garantia_geral = 0;
		$total_nfe_faturado_geral = 0;
		$total_nfe_geral = 0;
		$total_qtde_peca_garantia_geral = 0;
		$total_qtde_peca_faturado_geral = 0;
		$total_pecas_geral = 0;
		
		for ($i = 0; $i < pg_num_rows($res_meses); $i++) {
			$mes_ano = pg_fetch_result($res_meses, $i, 'mes_ano');
			list($mes_final, $ano_final) = explode('/',$mes_ano);
			$inicial = date('Y-m-01',mktime(0,0,0,$mes_final,1,$ano_final));
			$final = date('Y-m-t',mktime(0,0,0,$mes_final,1,$ano_final));
			$total_os_mes = 0;
			$total_pedido = 0;
			$total_atendimento = 0;
			$total_interacao = 0;
			$total_telefonia = 0;
			$total_nfe_garantia = 0;
			$total_nfe_faturado = 0;
			$total_nfe = 0;
			$total_qtde_peca_garantia = 0;
			$total_qtde_peca_faturado = 0;
			$total_pecas = 0;
			
			$sql_total_os_mes = "	SELECT COUNT(os) AS total_os
									FROM tbl_os
									WHERE fabrica = $login_fabrica
									AND data_digitacao between '$inicial 00:00' and '$final 23:59'
									AND excluida IS NOT TRUE";
			$res_total_os_mes = pg_query($con, $sql_total_os_mes);
			if (pg_num_rows($res_total_os_mes) > 0) {
				$total_os_mes = pg_fetch_result($res_total_os_mes, 0, 'total_os');
				$total_os_mes_geral += $total_os_mes;
			}

			$sql_total_pedido = "	SELECT COUNT(tbl_pedido.pedido) AS total_pedido
									FROM tbl_pedido
									JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
									AND (tbl_tipo_pedido.codigo = 'FAT' or pedido_faturado)
									WHERE tbl_pedido.fabrica = $login_fabrica
									AND tbl_pedido.data between '$inicial 00:00' and '$final 23:59'";
			$res_total_pedido = pg_query($con, $sql_total_pedido);
			if (pg_num_rows($res_total_pedido) > 0) {
				$total_pedido = pg_fetch_result($res_total_pedido, 0, 'total_pedido');
				$total_pedido_geral += $total_pedido;
			}

			$sql_total_atendimento = "	SELECT COUNT(hd_chamado) AS total_atendimento
										FROM tbl_hd_chamado
										WHERE fabrica_responsavel = $login_fabrica
										AND data between '$inicial 00:00' and '$final 23:59'
										AND posto IS NULL";
			$res_total_atendimento = pg_query($con, $sql_total_atendimento);
			if (pg_num_rows($res_total_atendimento) > 0) {
				$total_atendimento = pg_fetch_result($res_total_atendimento, 0, 'total_atendimento');
				$total_atendimento_geral += $total_atendimento;
			}

			// Consulta igual da tela admin/callcenter_relatorio_atendimento_atendente.php
			$sql_total_interacao = " 	WITH inte AS (
													SELECT
													COUNT(1) AS interacoes
													FROM tbl_hd_chamado hc
													INNER JOIN tbl_hd_chamado_item hci ON hci.hd_chamado = hc.hd_chamado
													INNER JOIN tbl_admin a ON a.admin = hci.admin AND a.fabrica = $login_fabrica
													WHERE hc.fabrica_responsavel = $login_fabrica
													AND hc.fabrica = $login_fabrica
													AND hc.posto IS NULL
													AND hci.data BETWEEN '$inicial 00:00:00' AND '$final 23:59:59'
													AND hci.admin IS NOT NULL
													AND hci.status_item IS NOT NULL
												   )
										SELECT SUM(interacoes) AS total_interacao FROM inte";
			$res_total_interacao = pg_query($con, $sql_total_interacao);
			if (pg_num_rows($res_total_interacao) > 0) {
				$total_interacao = pg_fetch_result($res_total_interacao, 0, 'total_interacao');				
				if (!empty($total_interacao)) {
					$total_interacao_geral += $total_interacao;
				}
			}

			$dt = explode("/", $mes_ano);

			$data_inicial_tel = $dt[1].'-'.$dt[0].'-01';
			$data_final_tel = date("Y-m-t", strtotime($data_inicial_tel));

			$retorno_tel = retorna_fila_telefone2($data_inicial_tel, $data_final_tel);

			foreach ($retorno_tel as $p => $ttl) {
				$total_telefonia += $ttl;
			}

			$total_telefonia_geral += $total_telefonia;
	
			$sql_total_nfe_garantia = "	SELECT COUNT(distinct tbl_faturamento.faturamento) AS total_faturamento_garantia,
											   SUM(tbl_faturamento_item.qtde) AS total_pecas_garantia
										FROM tbl_faturamento
										JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
										JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
										WHERE tbl_peca.fabrica = $login_fabrica
										AND emissao between '$inicial 00:00' and '$final 23:59'
										AND tbl_faturamento_item.pedido notnull
										AND (tbl_faturamento.distribuidor = 4311 or tbl_faturamento.distribuidor isnull)
										AND (left(tbl_faturamento.cfop, 2) IN ('59','69'))";
			$res_total_nfe_garantia = pg_query($con, $sql_total_nfe_garantia);
			if (pg_num_rows($res_total_nfe_garantia) > 0) {
				$total_nfe_garantia = pg_fetch_result($res_total_nfe_garantia, 0, 'total_faturamento_garantia');
				$total_nfe_garantia_geral += $total_nfe_garantia;
				$total_qtde_peca_garantia = pg_fetch_result($res_total_nfe_garantia, 0, 'total_pecas_garantia');
				$total_qtde_peca_garantia = (empty($total_qtde_peca_garantia)) ? 0 : $total_qtde_peca_garantia;
				$total_qtde_peca_garantia_geral += $total_qtde_peca_garantia;
			}			

			$sql_total_nfe_faturada = "	SELECT COUNT(distinct tbl_faturamento.faturamento) AS total_faturamento_faturado,
											   SUM(tbl_faturamento_item.qtde) AS total_pecas_faturada	
										FROM tbl_faturamento
										JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
										JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
										WHERE tbl_peca.fabrica = $login_fabrica
										AND emissao between '$inicial 00:00' and '$final 23:59'
										AND tbl_faturamento_item.pedido notnull
										AND (tbl_faturamento.distribuidor = 4311 or tbl_faturamento.distribuidor isnull)
										AND (left(tbl_faturamento.cfop, 2) NOT IN ('59','69'))";
			$res_total_nfe_faturada = pg_query($con, $sql_total_nfe_faturada);
			if (pg_num_rows($res_total_nfe_faturada) > 0) {
				$total_nfe_faturado = pg_fetch_result($res_total_nfe_faturada, 0, 'total_faturamento_faturado');
				$total_nfe_faturado_geral += $total_nfe_faturado;
				$total_qtde_peca_faturado = pg_fetch_result($res_total_nfe_faturada, 0, 'total_pecas_faturada');
				$total_qtde_peca_faturado = (empty($total_qtde_peca_faturado)) ? 0 : $total_qtde_peca_faturado;
				$total_qtde_peca_faturado_geral += $total_qtde_peca_faturado;
			}

			$total_nfe = $total_nfe_garantia + $total_nfe_faturado;
			$total_nfe_geral += $total_nfe;
			$total_pecas = $total_qtde_peca_garantia + $total_qtde_peca_faturado;
			$total_pecas_geral += $total_pecas;

			if ($telecontrol_distrib) { 

				$queryInteracao = "SELECT count(*) AS total FROM tbl_interacao 
								  WHERE fabrica = {$login_fabrica}
								  AND data between '$inicial 00:00' and '$final 23:59'";

				$resInteracao = pg_query($con, $queryInteracao);

				$interacaoPedido = pg_fetch_result($resInteracao, 0, 'total');

				$queryInteracao = "SELECT count(*) as total FROM tbl_os_interacao 
								  WHERE fabrica = {$login_fabrica}
								  AND data between '$inicial 00:00' and '$final 23:59'";

	  			$resInteracao = pg_query($con, $queryInteracao);

				$interacaoOs = pg_fetch_result($resInteracao, 0, 'total');

				$qtde_interacoes = $interacaoOs + $interacaoPedido;
			}

			$body .="
						<tr>
							<td nowrap align='center' valign='top'>$mes_ano</td>
							<td nowrap align='center' valign='top'>$total_os_mes</td>
							<td nowrap align='center' valign='top'>$total_pedido</td>
							<td nowrap align='center' valign='top'>$total_atendimento</td>
							<td nowrap align='center' valign='top'>$total_interacao</td>
							<td nowrap align='center' valign='top'>$total_telefonia</td>
							<td nowrap align='center' valign='top'>$total_nfe_garantia</td>
							<td nowrap align='center' valign='top'>$total_nfe_faturado</td>
							<td nowrap align='center' valign='top'>$total_nfe</td>
							<td nowrap align='center' valign='top'>$total_qtde_peca_garantia</td>
							<td nowrap align='center' valign='top'>$total_qtde_peca_faturado</td>
							<td nowrap align='center' valign='top'>$total_pecas</td>";
			
			if ($telecontrol_distrib) {
				
				$body .= "<td nowrap align='center' valign='top'>$qtde_interacoes</td>";
			}
			
			$body .="</tr>";
		}
			
		$body .= "</tbody>
				</table>";

		$fileName = "relatorio_evolucao_operacional-{$data_hoje}.xls";

		$file = fopen("xls/{$fileName}", "w");
		
		$thead = "
			<table border='1'>
				<thead>
					<tr>
						<th rowspan='2'>$data_hoje</th>
						<th>Postos Credenciados</th>
						<th>Qtde SKU's</th>
						<th>Qtde Peças em Estoque</th>
						<th>Qtde de Atendentes</th>
					</tr>
					<tr>
						<th>$postos_credenciados</th>
						<th>$qtde_sku</th>
						<th>$qtde_peca</th>
						<th>$qtde_atendente</th>
					</tr>
					<tr>
						<th>Subtotais</th>
						<th nowrap align='center' valign='top'>$total_os_mes_geral</th>
						<th nowrap align='center' valign='top'>$total_pedido_geral</th>
						<th nowrap align='center' valign='top'>$total_atendimento_geral</th>
						<th nowrap align='center' valign='top'>$total_interacao_geral</th>
						<th nowrap align='center' valign='top'>$total_telefonia_geral</th>
						<th nowrap align='center' valign='top'>$total_nfe_garantia_geral</th>
						<th nowrap align='center' valign='top'>$total_nfe_faturado_geral</th>
						<th nowrap align='center' valign='top'>$total_nfe_geral</th>
						<th nowrap align='center' valign='top'>$total_qtde_peca_garantia_geral</th>
						<th nowrap align='center' valign='top'>$total_qtde_peca_faturado_geral</th>
						<th nowrap align='center' valign='top'>$total_pecas_geral</th>
					</tr>
					<tr>
						<th>Totais</th>
						<th nowrap align='center' valign='top'>$total_os_mes_geral</th>
						<th nowrap align='center' valign='top'>$total_pedido_geral</th>
						<th nowrap align='center' valign='top'>$total_atendimento_geral</th>
						<th nowrap align='center' valign='top'>$total_interacao_geral</th>
						<th nowrap align='center' valign='top'>$total_telefonia_geral</th>
						<th nowrap align='center' valign='top'>$total_nfe_garantia_geral</th>
						<th nowrap align='center' valign='top'>$total_nfe_faturado_geral</th>
						<th nowrap align='center' valign='top'>$total_nfe_geral</th>
						<th nowrap align='center' valign='top'>$total_qtde_peca_garantia_geral</th>
						<th nowrap align='center' valign='top'>$total_qtde_peca_faturado_geral</th>
						<th nowrap align='center' valign='top'>$total_pecas_geral</th>
					</tr>
					<tr>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>$nome_fab</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>OS's</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Pedidos Faturados</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Atendimentos</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Interações em Atendimentos</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Telefonia - Chamadas Atendidas</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Nfe's Emitidas para EMBARQUE(Garantia)</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Nfe's Emitidas para EMBARQUE(Faturado)</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Total de Nfe's Emitidas</th>	
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Qtde da Peças Despachadas(Garantia)</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Qtde da Peças Despachadas(Faturado)</th>
						<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Total de Peças Despachadas</th>";

			if ($telecontrol_distrib) {
	
				$thead .= "<th bgcolor='#596D9B' style='color: #FFFFFF !important;'>Interações em OS's e Faturados</th>";
			}
			
			$thead .= "</tr>
						</thead>
						<tbody>
		";

		fwrite($file, $thead);
		fwrite($file, $body);
		fclose($file);

		if (file_exists("xls/{$fileName}")) {
			echo "xls/{$fileName}";
		}
	}
	exit;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE EVOLUÇÃO OPERACIONAL";
include 'cabecalho_new.php';

$plugins = array(
	"mask"
);

include("plugin_loader.php");

?>

<script type="text/javascript">

	$(function(){ 

		$("#data_inicial").mask("99/9999");
		$("#data_final").mask("99/9999");

		$("#btn-excel").click(function(){

			let json_text    = "";
			let data_inicial = $("#data_inicial").val();
			let data_final   = $("#data_final").val();
			let msg = '';


			if (data_final == "" || data_final == "") {
				$("#div_erro").html("<h4 align='center'>Preencha as datas para gerar o arquivo</h4>");
			} else if (!valida_data(data_inicial, data_final)) {
				$("#div_erro").html("<h4 align='center'>Datas Inválidas</h4>");
				$("#div_erro").show();
			} else {
				if($("#gestao").prop("checked")) {
					json_text = '{"data_inicial":"'+data_inicial.replace("/","\\/")+'","data_final":"'+data_final.replace("/","\\/")+'","gerar_excel":true, "gestao":true}';
				} else {
					json_text = '{"data_inicial":"'+data_inicial.replace("/","\\/")+'","data_final":"'+data_final.replace("/","\\/")+'","gerar_excel":true}';
				}
				
				
				$("#jsonPOST").val(json_text);
				$("#gerar_excel").click();

			}

		});

	});

	function valida_data(data_inicial, data_final) {
		
		var dt_array_i = data_inicial.split('/');
		var m_i = dt_array_i[0];
		var a_i = dt_array_i[1];

		var dt_array_f = data_final.split('/');
		var m_f = dt_array_f[0];
		var a_f = dt_array_f[1];
		var ok = false;

		if ((m_i < 1 || m_i > 12 || a_i.length != 4) || (m_f < 1 || m_f > 12 || a_f.length != 4)) {
			return false;
		} else {
			$.ajax({
				url: 'relatorio_evolucao_operacional.php',
				async: false,
				type: 'POST',
				data: {valida_data: true, mes_ini: m_i, ano_ini: a_i, mes_fin: m_f, ano_fin: a_f},
			})
			.always(function(data) {
				if (data.trim() == "ok") {
					ok = true;
				} else {
					ok = false;
				}
			});
		}
		return ok;
	}

</script>

<div class="container">
	<div id="div_erro" class="msg_erro alert alert-danger" style="display: none;"></div>
	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>
	<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
		<div class='titulo_tabela'>
			Parâmetros de Pesquisa
		</div>
		<br />
		<div class="row-fluid">
			<div class="span3"></div>
			<div class='span3'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : "" ?>'>
					<label class='control-label' for='data_inicial'>Mês/Ano Inicial</label>
						<div class='controls controls-row'>
							<h5 class="asteristico">*</h5>
							<input class="span8" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<?=$data_inicial?>">
						</div>
				</div>
			</div>
			<div class="span1"></div>
			<div class='span3'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : "" ?>'>
					<label class='control-label' for='data_final'>Mês/Ano Final</label>
						<div class='controls controls-row'>
							<h5 class="asteristico">*</h5>
							<input class="span8" type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<?=$data_final?>">
						</div>
				</div>
			</div>
		</div>
		<br /><br />

		<div class="row-fluid tac">
			<br />
			<button type="button" class="btn btn-success" id="btn-excel">Download do Excel</button>
			<input type="button" id="gerar_excel" style="display: none;" value="" />
			<input type="hidden" id="jsonPOST" value="" />
		</div>	
		<!-- <input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='pesquisar'> -->
		<br />
	</FORM>
</div>
	<div id="div_warning" class="alert alert-warning"><h4>Intervalo máximo permitido entre datas é de 13 meses</h4></div>
<?php
	} else {
		$layout_menu = "gerencia";
		$title = "RELATÓRIO DE EVOLUÇÃO OPERACIONAL";
		include 'cabecalho_new.php';
		?>
		<div id="div_danger" class="alert alert-danger">
		<?
			echo "Acesso negado para o ADMIN logado";
		?></div><?
	}
} else {
		?>
		<div id="div_danger" class="alert alert-danger">
		<?
			echo "Acesso negado para o ADMIN logado";
		?></div><?
}
include 'rodape.php';?>
