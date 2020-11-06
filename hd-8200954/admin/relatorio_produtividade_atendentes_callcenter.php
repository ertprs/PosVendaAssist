<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

function converterParaSegundos($tempo) {

	if (!empty($tempo) && $tempo != "0" && !preg_match('/[a-zA-Z]/',$tempo)) {

		$dt = new DateTime("1970-01-01 $tempo", new DateTimeZone('UTC'));
		$seconds = (int)$dt->getTimestamp();

		return $seconds;

	} else {

		return 0;

	}
	
}

function retornaTempoFormatado($segundos) {

	$horas = floor($segundos / 3600);
	$minutos = floor(($segundos - ($horas * 3600)) / 60);
	$segundos = floor($segundos % 60);

	return str_pad($horas , 2 , '0' , STR_PAD_LEFT) . ":" . str_pad($minutos , 2 , '0' , STR_PAD_LEFT) . ":" . str_pad($segundos , 2 , '0' , STR_PAD_LEFT);

}

function retornaMeses($data_inicial, $data_final)
{
    $begin = new DateTime( $data_inicial );
    $end = new DateTime( $data_final );
    $end = $end->modify( '+1 month' );

    $interval = DateInterval::createFromDateString('1 month');

    $period = new DatePeriod($begin, $interval, $end);
    $counter = 0;
    foreach($period as $dt) {
        $counter++;
    }

    return $counter;
}

function getDadosTelefonia($data_inicial, $data_final = null) {
	global $filasTelefonia, $login_fabrica;

	$filasTelefonia = array_map(function ($r) {
		return "'$r'";
	}, $filasTelefonia);

	if (empty($data_final)) {
		$totalLoop = 3;
	} else {
		$totalLoop = retornaMeses(formata_data($data_inicial), formata_data($data_final));
	}

	for ($x = 1;$x <= $totalLoop;$x++) {

		$dataInicial = implode("-", array_reverse(explode("/", $data_inicial)));
		$dataFinal = date('Y-m-d', strtotime("+1 months", strtotime(formata_data($data_inicial))));

		$queryString = "/inicio/{$dataInicial}/final/{$dataFinal}/companhia/10/setor/sac/filas/" . urlencode(implode(",", $filasTelefonia)) . "/fabrica/" . $login_fabrica;
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

		$responseData[date(("m/Y"),strtotime($dataInicial))] = json_decode(curl_exec($curlData), true);

		if (strlen(curl_error($curl) > 0) OR $responseData['exception']) {
			$responseData = ['error' => strlen($responseData['exception']) ? $responseData['exception'] : curl_error($curlData)];
		}

		$data_inicial = date('d/m/Y', strtotime($dataFinal));

	}
	
	return $responseData;
}

function getIdExternoAtendente($id) {
	global $con, $login_fabrica;

	$sql = "SELECT tbl_admin.external_id
			FROM tbl_admin
			WHERE tbl_admin.admin = {$id}
			AND tbl_admin.fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 0, 'external_id');
	} else {
		return false;
	}

}

function getNomeAtendente($id) {
	global $con, $login_fabrica;

	$sql = "SELECT tbl_admin.nome_completo
			FROM tbl_admin
			WHERE tbl_admin.admin = {$id}
			AND tbl_admin.fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 0, 'nome_completo');
	} else {
		return false;
	}
}

function getInteracao($id, $data_inicial, $data_final = null){
	global $con, $login_fabrica;

	$datainicial = explode('/', $data_inicial);
	$datafinal2 = explode('/', $data_final);

	$data1 = $datainicial[2].'-'.$datainicial[1].'-'.$datainicial[0].' 00:00:00';
	$data2 = $datafinal2[2].'-'.$datafinal2[1].'-'.$datafinal2[0].' 23:59:59';
	$data2 = str_replace('--', '', $data2);

	$sql = "SELECT count(*) as interacao
			FROM tbl_interacao
			WHERE tbl_interacao.fabrica = {$login_fabrica}
			AND tbl_interacao.contexto = 2
			AND tbl_interacao.admin = {$id}
			AND tbl_interacao.data BETWEEN '$data1' AND '$data2'";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 'interacao');
	} else {
		return false;
	}
}

function getInteracaoPedido($id, $data_inicial, $data_final = null){
	global $con, $login_fabrica;

	$datainicial = explode('/', $data_inicial);
	$datafinal3 = explode('/', $data_final);

	$data1 = $datainicial[2].'-'.$datainicial[1].'-'.$datainicial[0].' 00:00:00';
	$data2 = $datafinal3[2].'-'.$datafinal3[1].'-'.$datafinal3[0].' 23:59:59';
	$data2 = str_replace('--', '', $data2);

	$sql = "SELECT COUNT(*) as interacao_pedido
			FROM tbl_os_interacao
			WHERE tbl_os_interacao.fabrica = {$login_fabrica}
			and tbl_os_interacao.admin = {$id}
			AND tbl_os_interacao.data BETWEEN '$data1' AND '$data2'";
	
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 'interacao_pedido');
	} else {
		return 	false;
	}
}

function getPeca($id, $data_inicial, $data_final = null){
	global $con, $login_fabrica;

	$datainicial = explode('/', $data_inicial);
	$datafinal3 = explode('/', $data_final);

	$data1 = $datainicial[2].'-'.$datainicial[1].'-'.$datainicial[0].' 00:00:00';
	$data2 = $datafinal3[2].'-'.$datafinal3[1].'-'.$datafinal3[0].' 23:59:59';
	$data2 = str_replace('--', '', $data2);

	$sql = "SELECT count(*) as qtde_peca 
			from tbl_peca 
			where fabrica = {$login_fabrica}
			AND data_input BETWEEN '$data1' AND '$data2'
			AND admin = $id";
	
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 'qtde_peca');
	} else {
		return $sql;
	}
}

function getProduto($id, $data_inicial, $data_final = null){
	global $con, $login_fabrica;

	$datainicial = explode('/', $data_inicial);
	$datafinal3 = explode('/', $data_final);

	$data1 = $datainicial[2].'-'.$datainicial[1].'-'.$datainicial[0].' 00:00:00';
	$data2 = $datafinal3[2].'-'.$datafinal3[1].'-'.$datafinal3[0].' 23:59:59';
	$data2 = str_replace('--', '', $data2);

	$sql = "SELECT count(*) as qtde_produto 
			from tbl_produto 
			where fabrica_i = {$login_fabrica}
			AND data_input BETWEEN '$data1' AND '$data2'
			AND admin = $id";
	
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 'qtde_produto');
	} else {
		return false;
	}
}


if (isset($_POST['submit']) || isset($_POST['submit_diversos'])) {
	
	$atendente    = $_POST['atendente'];
	$data_inicial = $_POST['data_inicial'];

	if (isset($_POST['submit'])) {
		$data_final   = date('Y-m-d', strtotime("+3 months", strtotime(formata_data($data_inicial))));
	} else {
		$data_final = formata_data($_POST['data_final']);
	}

	if (isset($_POST['submit'])) {
		if (empty($data_inicial)) {
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		}
	} else {
		if (empty($data_inicial) || empty($data_final)) {
			$msg_erro["campos"][] = "data";
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		}
	}

	if (count($msg_erro) == 0) {

		$condPeriodo = " AND tbl_hd_chamado.data BETWEEN '".formata_data($data_inicial)."' AND '{$data_final}'";

		if (isset($_POST['submit_diversos'])) {

			$camposAdmin 	   		= ", tbl_admin.nome_completo, tbl_admin.admin";
			$camposAdminResultado   = ", resultado.admin, resultado.nome_completo";
			$joinAdmin              = " LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
									    AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
									    AND tbl_admin.fabrica = {$login_fabrica}
									  ";
			$adminChamado      		= " resultado.admin";
			$countAbertosAtendente	= " COUNT(*)";
			$countAbertosEquipe     = " COUNT(*)";

			if (count($_POST['atendentes']) > 0) {

				$atendentes 	= implode(",", $_POST['atendentes']);
				$condAtendentes = "AND tbl_admin.admin IN ({$atendentes})";

			}
					

		} else {
			$adminChamado      			= $atendente;
			$countAbertosAtendente    	= " COUNT(1)  FILTER (WHERE tbl_hd_chamado.admin = {$atendente})";
			$countAbertosEquipe         = " COUNT(1)  FILTER (WHERE tbl_hd_chamado.admin <> {$atendente})";
			$countResolvidosEquipe   	= " tbl_hd_chamado.admin <> {$atendente} AND";
			$countResolvidosAtendente 	= " tbl_hd_chamado.admin = {$atendente} AND";

			$subQueryTotalAdmin = "SELECT 
							 		COALESCE(NULLIF(COUNT(*), 0), 1) AS total
							   FROM tbl_admin
							   WHERE tbl_admin.fabrica = {$login_fabrica}
							   AND tbl_admin.admin <> {$adminChamado}
							   AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
							   AND tbl_admin.ativo IS TRUE
							   AND (
							 	  SELECT COUNT(*)
							 	  FROM tbl_hd_chamado
							 	  WHERE tbl_hd_chamado.admin = tbl_admin.admin
							 	  AND tbl_hd_chamado.fabrica = {$login_fabrica}
							 	  AND TO_CHAR(tbl_hd_chamado.data, 'mm/yyyy') = resultado.mes
							 	  LIMIT 1
							   ) > 0
							   {$condAtendentes}";

			$subQuerysEquipe = ",(
									SELECT COUNT(*) as total
									FROM tbl_hd_chamado_item
									LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
									LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
									AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
									WHERE tbl_hd_chamado_item.admin <> {$adminChamado}
									AND tbl_hd_chamado_item.status_item != 'Resolvido'
									AND TO_CHAR(tbl_hd_chamado_item.data, 'mm/yyyy') = resultado.mes
									AND tbl_hd_chamado.fabrica = {$login_fabrica}
									AND tbl_admin.fabrica = {$login_fabrica}
									{$condAtendentes}
								) / ({$subQueryTotalAdmin}) as media_interacoes_equipe,
								(
									SELECT COUNT(*) as total
									FROM tbl_hd_chamado_item
									LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
									LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
									AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
									WHERE tbl_hd_chamado_item.admin <> {$adminChamado}
									AND tbl_hd_chamado_item.status_item = 'Resolvido'
									AND TO_CHAR(tbl_hd_chamado_item.data, 'mm/yyyy') = resultado.mes
									AND tbl_hd_chamado.fabrica = {$login_fabrica}
									AND tbl_admin.fabrica = {$login_fabrica}
									{$condAtendentes}
								) / ({$subQueryTotalAdmin}) as media_interacoes_resolvidas_equipe,
								resultado.abertos_equipe / ({$subQueryTotalAdmin}) as media_abertos_equipe_atendente";

		}

		$sqlProdutividade = "
							SELECT 
								resultado.mes,
								resultado.abertos_atendente,
								resultado.abertos_equipe,
								(resultado.resolvidos_mesmo_dia_atendente * 100) / COALESCE(NULLIF(resultado.abertos_atendente,0),1)
								AS porcentagem_resolvidos_mesmo_dia_atendente,
								(resultado.resolvidos_mesmo_dia_equipe * 100) / COALESCE(NULLIF(resultado.abertos_equipe,0),1)
								AS porcentagem_resolvidos_mesmo_dia_equipe,
								(
									SELECT COUNT(*) as total 
									FROM tbl_hd_chamado_item 
									LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
									{$joinAdmin}
									WHERE tbl_hd_chamado_item.admin = {$adminChamado} 
									AND tbl_hd_chamado_item.status_item != 'Resolvido' 
									AND TO_CHAR(tbl_hd_chamado_item.data, 'mm/yyyy') = resultado.mes 
									AND tbl_hd_chamado.fabrica = {$login_fabrica} 
									{$condAtendentes} 
								) as total_interacoes_atendente, 
								(
									SELECT COUNT(*) as total 
									FROM tbl_hd_chamado_item 
									LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
									{$joinAdmin} 
									WHERE tbl_hd_chamado_item.admin = {$adminChamado} 
									AND tbl_hd_chamado_item.status_item = 'Resolvido' 
									AND TO_CHAR(tbl_hd_chamado_item.data, 'mm/yyyy') = resultado.mes 
									AND tbl_hd_chamado.fabrica = {$login_fabrica} 
									{$condAtendentes} 
								) as total_interacoes_resolvidas_atendente 
								{$subQuerysEquipe} 
								{$camposAdminResultado} 
						    FROM (
								SELECT  to_char(tbl_hd_chamado.data, 'mm/yyyy') AS mes, 
										{$countAbertosAtendente}      AS abertos_atendente, 
										{$countAbertosEquipe}         AS abertos_equipe, 
										COUNT(1)  FILTER(WHERE {$countResolvidosAtendente} tbl_hd_chamado.data::date = ( 
											SELECT tbl_hd_chamado_item.data::date 
											FROM tbl_hd_chamado_item 
											{$joinAdmin} 
											WHERE tbl_hd_chamado_item.status_item = 'Resolvido' 
											AND tbl_hd_chamado_item.hd_chamado 	  = tbl_hd_chamado.hd_chamado 
											ORDER BY tbl_hd_chamado_item.data DESC 
											LIMIT 1 
										)) AS resolvidos_mesmo_dia_atendente,
										COUNT(1)  FILTER(WHERE {$countResolvidosEquipe} tbl_hd_chamado.data::date = (
											SELECT tbl_hd_chamado_item.data::date 
											FROM tbl_hd_chamado_item
											{$joinAdmin}
											WHERE tbl_hd_chamado_item.status_item = 'Resolvido' 
											AND tbl_hd_chamado_item.hd_chamado 	  = tbl_hd_chamado.hd_chamado 
											ORDER BY tbl_hd_chamado_item.data DESC 
											LIMIT 1 
										)) AS resolvidos_mesmo_dia_equipe 
										{$camposAdmin} 
								FROM tbl_hd_chamado 
								{$joinAdmin} 
								WHERE tbl_hd_chamado.fabrica = {$login_fabrica} 
								{$condPeriodo} 
								{$condAtendentes} 
								GROUP BY mes {$camposAdmin} 
							) AS resultado 
							ORDER BY resultado.mes ASC
							";
		$resProdutividade = pg_query($con, $sqlProdutividade);

		if (isset($_POST['gerar_excel'])) {

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio-produtividade-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			$thead = "<table>
						<thead>
							<tr>
								<th bgcolor='#7ba6ed'>Mês/Ano</th>
								<th bgcolor='#7ba6ed'>Atendente</th>
								<th bgcolor='#7ba6ed'>Atendimentos Abertos</th>
								<th bgcolor='#7ba6ed'>% Resolvidos Mesmo Dia</th>
								<th bgcolor='#7ba6ed'>Interações em Atendimentos</th>
								<th bgcolor='#7ba6ed;>Chamadas Atendidas</th>
								<th bgcolor='#7ba6ed'>Tempo Médio Espera</th>
								<th bgcolor='#7ba6ed'>Tempo Médio Atendimento</th>
								<th bgcolor='#7ba6ed'>Ligações Realizadas</th>
								<th bgcolor='#7ba6ed'>Tempo Médio Ligações</th>
								<th bgcolor='#7ba6ed'>Interações em OS's</th>
								<th bgcolor='#7ba6ed'>Interações pedidos faturados</th>
								<th bgcolor='#7ba6ed'>Inclusão de Peça</th>
								<th bgcolor='#7ba6ed'>Inclusão de Produto</th>
							</tr>
						</thead>
						<tbody>";

			fwrite($file, $thead);

			$totalEquipeAbertos  = 0;
			$resolvidosEquipe    = 0;
			$interacoesAtendente = 0;
			$contadorLinhas      = 1;

			$dadosTelefonia = getDadosTelefonia($_POST['data_inicial'], $_POST['data_final']);

			$contador 			= 0;
			$contadorTelefonia	= 0;
			while ($dadosPesquisa = pg_fetch_object($resProdutividade)) {

				$dadosTelefoniaMes = $dadosTelefonia[$dadosPesquisa->mes]['atendentes'];

				if (($mesAnterior != $dadosPesquisa->mes && !empty($mesAnterior)) || (pg_num_rows($resProdutividade) == $contadorLinhas)) {

					$tbodyTotal =	'<tr>
										<td align="center" bgcolor="#7ba6ed" colspan="2"><strong>Média Equipe '.$mesAnterior.'</strong></td>
										<td align="center" bgcolor="#e0e2e5"><strong>'.number_format(($totalEquipeAbertos / $contador),2).'</strong></td>
										<td align="center" bgcolor="#e0e2e5"><strong>'.number_format(($resolvidosEquipe / $contador),2).'%</strong></td>
										<td align="center" bgcolor="#e0e2e5"><strong>'.number_format(($interacoesAtendente / $contador),2).'</strong></td>
										<td align="center" bgcolor="#e0e2e5">'.retornaTempoFormatado(number_format(($totalEspera / $contadorTelefonia),0)).'</td>
										<td align="center" bgcolor="#e0e2e5">'.retornaTempoFormatado(number_format(($totalDuracao / $contadorTelefonia),0)).'</td>
										<td align="center" bgcolor="#e0e2e5">'.number_format(($totalLigacoesRealizadas / $contadorTelefonia),2).'</td>
										<td align="center" bgcolor="#e0e2e5">'.retornaTempoFormatado(number_format(($duracaoMediaLigacaoRealizadas / $contadorTelefonia),0)).'</td>
										<td align="center" bgcolor="#e0e2e5">'.number_format(($totalinteracao / $contador),0).'</td>
										<td align="center" bgcolor="#e0e2e5">'.number_format(($totalinteracaopedido / $contador),0).'</td>
										<td align="center" bgcolor="#e0e2e5">'.number_format(($totalpeca / $contador),0).'</td>
										<td align="center" bgcolor="#e0e2e5">'.number_format(($totalproduto / $contador),0).'</td> 
									</tr>';

					if (pg_num_rows($resProdutividade) == $contadorLinhas) {
						$fim    = $tbodyTotal;
						$inicio = "";
					} else {
						$inicio = $tbodyTotal;
						$fim    = "";
					}

					$totalEquipeAbertos  	 		= 0;
					$resolvidosEquipe    	 		= 0;
					$interacoesAtendente 	 		= 0;
					$contador 			 	 		= 0;
					$totalEspera         	 		= 0;
					$totalDuracao        	 		= 0;
					$totalLigacoesRealizadas 		= 0;
					$duracaoMediaLigacaoRealizadas  = 0;
					$contadorTelefonia              = 0;
					$totalinteracao					= 0;
					$totalinteracaopedido			= 0;
					$totalpeca						= 0;
					$totalproduto					= 0;
				}

				$totalEquipeAbertos   += $dadosPesquisa->abertos_atendente;
				$resolvidosEquipe     += $dadosPesquisa->porcentagem_resolvidos_mesmo_dia_atendente;
				$interacoesAtendente  += $dadosPesquisa->total_interacoes_atendente;
				$totalinteracao       += getInteracao($dadosPesquisa->admin, $data_inicial, $data_final);
				$totalinteracaopedido += getInteracaoPedido($dadosPesquisa->admin, $data_inicial, $data_final);
				$totalpeca            += getPeca($dadosPesquisa->admin, $data_inicial, $data_final);
				$totalproduto         += getProduto($dadosPesquisa->admin, $data_inicial, $data_final);

				$esperaMedia         = '00:00:00';
				$duracaoMedia        = '00:00:00';
				$totalLigacoes 		 = 0;
				$duracaoMediaLigacao = 0;

				foreach ($dadosTelefoniaMes as $chave => $dadosAtendente) {

					if (getIdExternoAtendente($dadosPesquisa->admin) == $dadosAtendente['external_id']) {

						$esperaMedia    	  = $dadosAtendente['recebidas']['espera_media'];
						$duracaoMedia   	  = $dadosAtendente['recebidas']['duracao_media'];
						$totalLigacoes  	  = $dadosAtendente['realizadas']['total_ligacoes'];
						$duracaoMediaLigacao  = $dadosAtendente['realizadas']['duracao_media'];

					}

				}

				if (!empty(getIdExternoAtendente($dadosPesquisa->admin))) {

					$totalEspera  					+= converterParaSegundos($esperaMedia);
					$totalDuracao 					+= converterParaSegundos($duracaoMedia);
					$totalLigacoesRealizadas    	+= $totalLigacoes;
					$duracaoMediaLigacaoRealizadas  += converterParaSegundos($duracaoMediaLigacao);

					$colunasTelefonia = '<td align="center">'.$esperaMedia.'</td>
										 <td align="center">'.$duracaoMedia.'</td>
										 <td align="center">'.$totalLigacoes.'</td>
										 <td align="center">'.$duracaoMediaLigacao .'</td>';

					$contadorTelefonia++;

				} else {

					$colunasTelefonia = '<td align="center" colspan="4" bgcolor="#f4a97a">Atendente sem cadastro na telefonia</td>';

				}

				$tbody .=	$inicio.'<tr>
								<td align="center"><strong>'.$dadosPesquisa->mes.'</strong</td>
								<td>'.$dadosPesquisa->nome_completo.'</td>
								<td align="center">'.$dadosPesquisa->abertos_atendente.'</td>
								<td align="center">'.$dadosPesquisa->porcentagem_resolvidos_mesmo_dia_atendente.'%</td>
								<td align="center">'.$dadosPesquisa->total_interacoes_atendente.'</td>
								'.$colunasTelefonia.'
								<td align="center">'.getInteracao($dadosPesquisa->admin, $data_inicial, $data_final).'</td>
								<td align="center">'.getInteracaoPedido($dadosPesquisa->admin, $data_inicial, $data_final).'</td>
								<td align="center">'.getPeca($dadosPesquisa->admin, $data_inicial, $data_final).'</td>
								<td align="center">'.getProduto($dadosPesquisa->admin, $data_inicial, $data_final).'</td> 
							 </tr>
							 '.$fim;

				$inicio     = "";
				$fim        = "";

				$mesAnterior = $dadosPesquisa->mes;
				$contador++;
				$contadorLinhas++;

			}

			$tbody .= '
				</tbody>
			</table>';

			fwrite($file, $tbody);
			fclose($file);
			
			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

			exit;

		}
	}

}

$layout_menu = "gerencia";
$title = "Relatório de Produtividade Callcenter";
include 'cabecalho_new.php';

if ($privilegios != "*") {
	exit("Apenas usuários MASTER podem acessar este relatório");
}

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect",
	"maskedinput"
);

include("plugin_loader.php");

?>
<style>
	.tipos-header {
		background-color: #53a3b9;
		color: white;
		height: 40px;
		font-family: sans-serif;
		margin-bottom: 15px;
		font-size: 17px;
		border-radius: 7px;
		text-align: center;
		padding-top: 12px;
		display: block;
		cursor: pointer;
	}

	.tipos-header:hover {
		background-color: #297083;
		transition: 0.25s ease-in;
	}

</style>
<script>
	$(function(){

		$("#data_inicial1").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_inicial2").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#atendente_multiplo").multiselect({
            selectedText: "selecionados # de #"
        });

		$(".tipos-header").click(function(){
			$(this).next("div").slideToggle();
		});

		$("#btn-excel").click(function(){

			let data_inicial = $("#data_inicial2").val();
			let data_final   = $("#data_final").val();

			let atendentes   = JSON.stringify($(".ui-multiselect-checkboxes input[type=checkbox]:checked").map(function(){
									return $(this).val();
							   }).toArray());

			if (data_final == "" || data_final == "") {
				alert("Preencha as datas para gerar o arquivo");
			} else {
				
				let json_text = '{"data_inicial":"'+data_inicial.replace("/","\\/")+'","data_final":"'+data_final.replace("/","\\/")+'","gerar_excel":true,"submit_diversos":true, "atendentes" : '+atendentes+'}';
				
				$("#jsonPOST").val(json_text);
				$("#gerar_excel").click();

			}

		});

	});
</script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario tc_formulario1'>
	<div class='titulo_tabela '>Relatório produtividade X média da equipe</div>
	<br />
	<div class="alert alert-warning" style="width: 40%;margin-left: 27%;text-align: center;">
		<h6>Os dados gerados neste relatório, consideram os 3 meses posteriores à data inicial informada</h6>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial1" size="12" maxlength="10" class='span12' value= "<?= $data_inicial ?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Atendente</label>
					<div class='controls controls-row'>
						<div class='span5'>
							<h5 class='asteristico'>*</h5>
							<select name="atendente">
								<option value=""></option>
								<?php
								$sqlAtendentes = "SELECT admin, nome_completo
												  FROM tbl_admin
												  WHERE fabrica = {$login_fabrica}
												  AND ativo IS TRUE
												  AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
												  ORDER BY nome_completo";
								$resAtendentes = pg_query($con, $sqlAtendentes);

								while ($dadosAtendentes = pg_fetch_object($resAtendentes)) {
								?>

									<option value="<?= $dadosAtendentes->admin ?>" <?= $selected ?>><?= $dadosAtendentes->nome_completo ?></option>

								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class="row-fluid tac">
		<br />
		<button class="btn btn-primary" name="submit">Gerar OnePage</button>
		
	</div>
	<br />
</form>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario tc_formulario2'>
	<div class='titulo_tabela '>Relatório excel de diversos atendentes</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial2" size="12" maxlength="10" class='span12' value= "<?= $data_inicial ?>">
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
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 data_final' value= "<?= $_POST['data_final'] ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='data_inicial'>Atendentes</label>
					<div class='controls controls-row'>
						<div class='span5'>
							<select name="atendente_multiplo[]" id="atendente_multiplo" multiple="multiple">
								<?php
								$sqlAtendentes = "SELECT admin, nome_completo
												  FROM tbl_admin
												  WHERE fabrica = {$login_fabrica}
												  AND ativo IS TRUE
												  AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
												  ORDER BY nome_completo";
								$resAtendentes = pg_query($con, $sqlAtendentes);

								while ($dadosAtendentes = pg_fetch_object($resAtendentes)) {
									?>

									<option value="<?= $dadosAtendentes->admin ?>"><?= $dadosAtendentes->nome_completo ?></option>

								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class="row-fluid tac">
		<br />
		<button type="button" class="btn btn-success" id="btn-excel">Download do Excel</button>
		<input type="button" id="gerar_excel" style="display: none;" value="" />
		<input type="hidden" id="jsonPOST" value="" />
	</div>
	<br />
</form>
<?php
	if (pg_num_rows($resProdutividade) > 0 && count($msg_erro) == 0 && isset($_POST['submit'])) {

		$totalAbertosAtendente = 0;
		$totalAbertosEquipe    = 0;

		$mediaInteracoesPorAtendimento[] = ["name" => utf8_encode("Resolvidas"), "data" => []];
		$mediaInteracoesPorAtendimento[] = ["name" => utf8_encode("Abertas"), "data" => []];

		while ($dados = pg_fetch_object($resProdutividade)) {

			$abertosAtendente[] = ["name" => $dados->mes,
								   "data" => [(int) $dados->abertos_atendente, 
								   			  (int) $dados->media_abertos_equipe_atendente]];

			$totalAbertosAtendente += $dados->abertos_atendente;
			$totalAbertosEquipe    += $dados->media_abertos_equipe_atendente;

			$resolvidosMesmoDia[] = ["name" => $dados->mes,
								     "data" => [(int) $dados->porcentagem_resolvidos_mesmo_dia_atendente, 
								     			(int) $dados->porcentagem_resolvidos_mesmo_dia_equipe]];

			$totalResolvidosAtendente += $dados->porcentagem_resolvidos_mesmo_dia_atendente;
			$totalResolvidosEquipe    += $dados->porcentagem_resolvidos_mesmo_dia_equipe;

			$mesesArray[] = $dados->mes." Atendente";
			$mesesArray[] = $dados->mes." Equipe" ;

			$mediaInteracoesPorAtendimento[0]["data"][] = (int) $dados->total_interacoes_resolvidas_atendente;
			$mediaInteracoesPorAtendimento[0]["data"][] = (int) $dados->total_interacoes_atendente;
			$mediaInteracoesPorAtendimento[1]["data"][] = (int) $dados->media_interacoes_resolvidas_equipe;
			$mediaInteracoesPorAtendimento[1]["data"][] = (int) $dados->media_interacoes_equipe;

			$totalMediaInteracoesAtendentes  += $dados->porcentagem_resolvidos_mesmo_dia_atendente;
			$totalMediaInteracoesEquipe      += $dados->porcentagem_resolvidos_mesmo_dia_equipe;

		}

		$qtdeLinhas = pg_num_rows($resProdutividade);

		$abertosAtendente[] = ["name" => utf8_encode("Média Geral"),
							   "data" => [(int) $totalAbertosAtendente / $qtdeLinhas,
							   			  (int) $totalAbertosEquipe / $qtdeLinhas]];

		$abertosAtendenteGrafico = json_encode($abertosAtendente);

		$resolvidosMesmoDia[] = ["name" => utf8_encode("Média Geral"),
							     "data" => [(int) $totalResolvidosAtendente / $qtdeLinhas,
							   			    (int) $totalResolvidosEquipe / $qtdeLinhas]];

		$resolvidosMesmoDiaGrafico = json_encode($resolvidosMesmoDia);

        $mesesArray[] = utf8_encode("Média Geral");

		$mediaInteracoesPorAtendimentoGrafico = json_encode($mediaInteracoesPorAtendimento);
		$mesesArrayGrafico                    = json_encode($mesesArray);

		$totalAtendidasEquipe = 0;

		$dadosTelefonia = getDadosTelefonia($_POST['data_inicial']);
				
		foreach ($dadosTelefonia as $mes => $arrayDados) {

			$totalEquipeMes[$mes]['total_recebidas_equipe']  = 0;
			$totalEquipeMes[$mes]['total_atendentes_equipe'] = 0;

			foreach ($arrayDados as $dadosAtendentes) {
				foreach($dadosAtendentes as $valor) {

					if ($valor['external_id'] == getIdExternoAtendente($atendente)) {

						$totalAtendidasTelefoniaAtendente[] = ["name" => $mes,
								   			   		  		   "data" => [(int) $valor['recebidas']['total_ligacoes']]];

						$tempoMedioEsperaAtendente[]		= ["name" => $mes,
								   			   		  		   "data" => [(int) converterParaSegundos($valor['recebidas']['espera_media'])]];

						$duracaoMediaLigacaoAtendente[]		= ["name" => $mes,
								   			   		  		   "data" => [(int) converterParaSegundos($valor['recebidas']['duracao_media'])]];	

						$totalLigacoesRealizadasAtendente[] = ["name" => $mes,
								   			   		  		   "data" => [(int) $valor['realizadas']['total_ligacoes']]];	   			   		  		   	

					} else if (!empty($valor['nome_completo'])) {

						$totalEquipeMes[$mes]['total_recebidas_equipe']  += $valor['recebidas']['total_ligacoes'];
						$totalEquipeMes[$mes]['total_espera_media']      += converterParaSegundos($valor['recebidas']['espera_media']);
						$totalEquipeMes[$mes]['total_duracao_media']     += converterParaSegundos($valor['recebidas']['duracao_media']);
						$totalEquipeMes[$mes]['total_ligacoes_media']    += $valor['realizadas']['total_ligacoes'];
						$totalEquipeMes[$mes]['total_atendentes_equipe'] += 1;

					}

				}
			}
		}

		$contadorPosicao = 0;
		foreach ($totalEquipeMes as $mes => $totalMes) {

			$total_atendentes_equipe = $totalMes['total_atendentes_equipe'];

			$mediaAtendidasEquipe    = ($totalMes['total_recebidas_equipe']  / $total_atendentes_equipe);
			$mediaTempoEspera        = ($totalMes['total_espera_media']      / $total_atendentes_equipe);
			$mediaDuracao            = ($totalMes['total_duracao_media']     / $total_atendentes_equipe);
			$mediaLigacoesRealizadas = ($totalMes['total_ligacoes_media']    / $total_atendentes_equipe);

			$totalAtendidasTelefoniaAtendente[$contadorPosicao]["data"][] = (int) $mediaAtendidasEquipe;
			$tempoMedioEsperaAtendente[$contadorPosicao]["data"][]        = (int) $mediaTempoEspera;
			$duracaoMediaLigacaoAtendente[$contadorPosicao]["data"][]     = (int) $mediaDuracao;
			$totalLigacoesRealizadasAtendente[$contadorPosicao]["data"][] = (int) $mediaLigacoesRealizadas;

			$contadorPosicao++;

		}

		$totalAtendimentosTelefonia 		 = json_encode($totalAtendidasTelefoniaAtendente);
		$tempoMedioEsperaTelefonia  		 = json_encode($tempoMedioEsperaAtendente);
		$duracaoMediaTelefonia  		     = json_encode($duracaoMediaLigacaoAtendente);
		$totalLigacoesRealizadasTelefonia  	 = json_encode($totalLigacoesRealizadasAtendente);

		?>
		<div class="tipos-header">
			Atendimentos Originados
		</div>
		<div id="atendimentos_abertos" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>

			Highcharts.chart('atendimentos_abertos', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Atendimentos Originados'
			    },
			    subtitle: {
			        text: 'atendente x média equipe'
			    },
			    xAxis: {
			        categories: [
			            '<?= getNomeAtendente($atendente) ?>',
			            'Equipe',
			        ],
			        crosshair: true
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: 'Qtde. Chamados'
			        }
			    },
			    tooltip: {
			        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
			        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
			            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
			        footerFormat: '</table>',
			        shared: true,
			        useHTML: true
			    },
			    plotOptions: {
			        column: {
			            pointPadding: 0.2,
			            borderWidth: 0
			        }
			    },
			    series: <?= $abertosAtendenteGrafico ?>
			});

		</script>
		<div class="tipos-header">
			% Resolvidos Mesmo Dia
		</div>
		<div id="resolvidos_mesmo_dia" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>

			Highcharts.chart('resolvidos_mesmo_dia', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Porcentagem de chamados resolvidos no mesmo dia'
			    },
			    subtitle: {
			        text: 'atendente x média equipe'
			    },
			    xAxis: {
			        categories: [
			            '<?= getNomeAtendente($atendente) ?>',
			            'Equipe',
			        ],
			        crosshair: true
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: '% Chamados'
			        }
			    },
			    tooltip: {
			        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
			        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
			            '<td style="padding:0"><b>{point.y:.0f}%</b></td></tr>',
			        footerFormat: '</table>',
			        shared: true,
			        useHTML: true
			    },
			    plotOptions: {
			        column: {
			            pointPadding: 0.2,
			            borderWidth: 0
			        }
			    },
			    series: <?= $resolvidosMesmoDiaGrafico ?>
			});

		</script>
		<div class="tipos-header">
			Interações em atendimentos
		</div>
		<div id="interacoes_por_atendimento" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>
		Highcharts.chart('interacoes_por_atendimento', {
		    chart: {
		        type: 'column'
		    },
		    title: {
		        text: 'Interações em atendimentos'
		    },
		    subtitle: {
			    text: 'atendente (<?= getNomeAtendente($atendente) ?>) x média equipe'
			},
		    xAxis: {
		        categories: <?= $mesesArrayGrafico ?>,
		    },
		    yAxis: {
		        min: 0,
		        title: {
		            text: ''
		        },
		        stackLabels: {
		            enabled: true,
		            style: {
		                fontWeight: 'bold',
		                color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
		            }
		        }
		    },
		    legend: {
		        align: 'right',
		        x: -30,
		        verticalAlign: 'top',
		        y: 25,
		        floating: true,
		        backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
		        borderColor: '#CCC',
		        borderWidth: 1,
		        shadow: false
		    },
		    tooltip: {
		        headerFormat: '<b>{point.x}</b><br/>',
		        pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
		    },
		    plotOptions: {
		        column: {
		            stacking: 'normal',
		            dataLabels: {
		                enabled: true,
		                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
		            }
		        }
		    },
		    series: <?= $mediaInteracoesPorAtendimentoGrafico ?>
		});
		</script>
		<div class="tipos-header">
			Quantidade de chamadas atendidas
		</div>
		<div id="chamadas_atendidas" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>

			Highcharts.chart('chamadas_atendidas', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Quantidade de chamadas atendidas'
			    },
			    subtitle: {
			        text: 'atendente x média equipe'
			    },
			    xAxis: {
			        categories: [
			            '<?= getNomeAtendente($atendente) ?>',
			            'Equipe',
			        ],
			        crosshair: true
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: 'Qtde. Ligações Atendidas'
			        }
			    },
			    tooltip: {
			        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
			        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
			            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
			        footerFormat: '</table>',
			        shared: true,
			        useHTML: true
			    },
			    plotOptions: {
			        column: {
			            pointPadding: 0.2,
			            borderWidth: 0
			        }
			    },
			    series: <?= $totalAtendimentosTelefonia ?>
			});

		</script>
		<div class="tipos-header">
			Tempo Médio de Espera (TME)
		</div>
		<div id="media_espera" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>

			Highcharts.chart('media_espera', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Tempo Médio de Espera (TME)'
			    },
			    subtitle: {
			        text: 'atendente x média equipe'
			    },
			    xAxis: {
			        categories: [
			            '<?= getNomeAtendente($atendente) ?>',
			            'Equipe',
			        ],
			        crosshair: true
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: 'Segundos'
			        }
			    },
			    tooltip: {
			        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
			        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
			            '<td style="padding:0"><b>{point.y:.0f} Segundos</b></td></tr>',
			        footerFormat: '</table>',
			        shared: true,
			        useHTML: true
			    },
			    plotOptions: {
			        column: {
			            pointPadding: 0.2,
			            borderWidth: 0
			        }
			    },
			    series: <?= $tempoMedioEsperaTelefonia ?>
			});

		</script>
		<div class="tipos-header">
			Tempo / Duração Média de Atendimento (TMA)
		</div>
		<div id="duracao_media" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>

			Highcharts.chart('duracao_media', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Tempo / Duração Média de Atendimento (TMA)'
			    },
			    subtitle: {
			        text: 'atendente x média equipe'
			    },
			    xAxis: {
			        categories: [
			            '<?= getNomeAtendente($atendente) ?>',
			            'Equipe',
			        ],
			        crosshair: true
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: 'Segundos'
			        }
			    },
			    tooltip: {
			        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
			        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
			            '<td style="padding:0"><b>{point.y:.0f} Segundos</b></td></tr>',
			        footerFormat: '</table>',
			        shared: true,
			        useHTML: true
			    },
			    plotOptions: {
			        column: {
			            pointPadding: 0.2,
			            borderWidth: 0
			        }
			    },
			    series: <?= $duracaoMediaTelefonia ?>
			});

		</script>
		<div class="tipos-header">
			Ligações Realizadas
		</div>
		<div id="ligacoes_realizadas" style="min-width: 400px; height: 400px; margin: 0 auto" hidden></div>
		<script>

			Highcharts.chart('ligacoes_realizadas', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Ligações Realizadas'
			    },
			    subtitle: {
			        text: 'atendente x média equipe'
			    },
			    xAxis: {
			        categories: [
			            '<?= getNomeAtendente($atendente) ?>',
			            'Equipe',
			        ],
			        crosshair: true
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: 'Qtde. Total'
			        }
			    },
			    tooltip: {
			        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
			        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
			            '<td style="padding:0"><b>{point.y:.0f} Segundos</b></td></tr>',
			        footerFormat: '</table>',
			        shared: true,
			        useHTML: true
			    },
			    plotOptions: {
			        column: {
			            pointPadding: 0.2,
			            borderWidth: 0
			        }
			    },
			    series: <?= $totalLigacoesRealizadasTelefonia ?>
			});

		</script>
<?php
	}

include "rodape.php";
?>
