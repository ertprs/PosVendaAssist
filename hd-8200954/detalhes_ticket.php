<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_usuario.php';
include 'funcoes.php';

$apiUrl = "https://api2.telecontrol.com.br";
$companyHash = $parametros_adicionais_posto['company_hash'];

$ticket = filter_input(INPUT_GET, 'ticket', FILTER_VALIDATE_INT);
$os = filter_input(INPUT_GET, 'os', FILTER_VALIDATE_INT);

// Verifica se a OS ja foi integrada e redireciona para a tela de OS_PRESS
$sql = "SELECT campos_adicionais::JSON->>'data_integrado' AS data_integrado FROM tbl_os_campo_extra WHERE os = {$os}";
$pgResource = pg_query($con, $sql);
$dataIntegrado = pg_fetch_result($pgResource, 0, 'data_integrado');

if( $dataIntegrado ){
	header("Location: os_press.php?os={$os}");
	exit;
}

// Monta o Ticket
function montarTicket($formData){
	$ticket = json_decode($formData['ticket'], true);

	$horimetro = [
		'name' => 'horimetro',
		'content' => [
			['name' => 'horimetro', 'value' => $formData['horimetro']]
		]
	];
	$ticket['response_modificado'][] = $horimetro;

	$defeitos = [
		'name' => 'defeitos',
		'content' => [
			['name' => 'defeito_reclamado', 'value' => $formData['defeitoReclamado']]
		]
	];
	$ticket['response_modificado'][] = $defeitos;

	$adicionais = [
		'name' => 'adicionais',
		'content' => [
			['name' => 'pedagio', 'value' => str_replace(',', '.', str_replace('.', '', $formData['pedagio']))],
			['name' => 'alimentacao', 'value' => str_replace(',', '.', str_replace('.', '', $formData['alimentacao']))]
		]
	];
	$ticket['response_modificado'][] = $adicionais;

	$observacao = [
		'name' => 'observacao',
		'content' => [
			['name' => 'observacao', 'value' => $formData['observacao']]
		]
	];
	$ticket['response_modificado'][] = $observacao;

	// Produtos
	$produto = [
		'name' => 'produto',
		'content' => []
	];

	foreach($formData['listaDeProdutos'] as $item){
		$arr = [];
		$arr['name'] = 'produto';
		$arr['valueArray'] = json_encode([
			'defeito_constatado' => $item['defeito_constatado'],
			'solucao' => $item['solucao']
		]);

		$produto['content'][] = $arr;
	}
	$ticket['response_modificado'][] = $produto;

	// Lista básica
	$listaBasica = [
		'name' => 'lista_basica',
		'content' => []
	];

	foreach($formData['listaBasica'] as $item){
		$arr = [];
		$arr['name'] = 'lista_basica';
		$arr['valueArray'] = json_encode([
			'peca_referencia' => $item['peca_referencia'],
			'quantidade' => $item['quantidade'],
			'servico_realizado' => $item['servico_realizado']
		]);

		$listaBasica['content'][] = $arr;
	}

	// Deletados
	$listaBasica['deleted'] = [
		'dateTime' => $ticket['lista_basica']['dateTime']
	];

	foreach( $formData['listaDeletados'] as $item ){
		$arr = [];
		$arr = json_encode([
			'peca_referencia' => $item['peca_referencia'],
			'quantidade' => $item['quantidade'],
			'servico_realizado' => $item['servico_realizado']
		]);

		$listaBasica['deleted']['inputs'][] = $arr;
	}

	$ticket['response_modificado'][] = $listaBasica;
	
	$response = json_decode($ticket[0]['response'], true);
	
	foreach($response as $item){
		if(in_array($item['name'], ['checklist', 'resumo', 'anexos', 'assinatura', 'assinatura_tecnico', 'status'])){
			$ticket['response_modificado'][] = $item;
		}
	}

	$ticket['response_modificado'] = json_encode($ticket['response_modificado']);
	$ticket['referenceId'] = $ticket[0]['reference_id'];
	$ticket['ticket'] = $ticket[0]['ticket']; 
	$ticket['contexto'] = $ticket[0]['contexto']; 
	$ticket['response'] = $ticket[0]['response'];
	unset($ticket[0]);

	return json_encode($ticket);
}

// AJAX SALVAR TICKET
$salvarTicket = filter_input(INPUT_POST, 'salvarTicket', FILTER_VALIDATE_BOOLEAN);
if( $salvarTicket ){
	$formData = $_POST;

	unset($formData['salvarTicket']);

	$ticket = montarTicket($formData);

	$curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "$apiUrl/ticket-checkin/ticket-reagendar",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => $ticket,
      CURLOPT_HTTPHEADER => array(
        "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
        "access-env: PRODUCTION",
        "cache-control: no-cache",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

	curl_close($curl);
	
	if($err){
		$response = [
			'error' => true,
			'message' => 'Não foi possível realizar a operação. Tente novamente em instantes.'
		];
		exit(json_encode($response));
	}

	$retorno = json_decode($response, true);

	if( !empty($retorno['erro']) ){
		$res = [
			'error' => true,
			'message' => $retorno['erro']
		];
		exit(json_encode($res));
	}

	if( !empty($retorno['exception']) ){
		$res = [
			'error' => true,
			'message' => $retorno['exception']
		];
		exit(json_encode($res));
	}

	if( !empty($retorno['message']) ){
		$res = [
			'error' => false,
			'message' => $retorno['message']
		];
		exit(json_encode($res));
	}

	exit;
}

// AJAX SALVAR E APROVAR TICKET
$salvarAprovarTicket = filter_input(INPUT_POST, 'salvarAprovarTicket', FILTER_VALIDATE_BOOLEAN);
if( $salvarAprovarTicket ){
	$formData = $_POST;
	unset($formData['salvarAprovarTicket']);

	$ticket = montarTicket($formData);

	$curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "$apiUrl/ticket-checkin/ticket-reagendar",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => $ticket,
      CURLOPT_HTTPHEADER => array(
        "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
        "access-env: PRODUCTION",
        "cache-control: no-cache",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

	curl_close($curl);
	
	if($err){
		$response = [
			'error' => true,
			'message' => 'Não foi possível realizar a operação. Tente novamente em instantes'
		];
		exit(json_encode($response));
	}

	$retorno = json_decode($response, true);

	if( !empty($retorno['erro']) ){
		$res = [
			'error' => true,
			'message' => $retorno['erro']
		];
		exit(json_encode($res));
	}

	if( !empty($retorno['exception']) ){
		$res = [
			'error' => true,
			'message' => $retorno['exception']
		];
		exit(json_encode($res));
	}

	$referenceId = filter_input(INPUT_GET, 'os', FILTER_VALIDATE_INT);
	$ticket = filter_input(INPUT_GET, 'ticket', FILTER_VALIDATE_INT);

    $resRotina = exec('php rotinas/telecontrol/retorna_dados_tickets.php '.$referenceId. ' ' . $login_fabrica);
	$resRotina = json_decode($resRotina, true);

	if( !empty($resRotina['erro']) ){
		$res = [
			'error' => true,
			'message' => $resRotina['erro']
		];
		exit(json_encode($res));
	}

	if( !empty($resRotina['exception']) ){
		$res = [
			'error' => true,
			'message' => $resRotina['exception']
		];
		exit(json_encode($res));
	}

	$dados = [];
	$dados['reference_id'] = $referenceId;
	$dados['ticket_id'] = $ticket;
	$dados['admin_aprova'] = $login_posto;
	$dados = json_encode($dados);

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "$apiUrl/ticket-checkin/ticket-aprovacao",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => $dados,
		CURLOPT_HTTPHEADER => array(
		"access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
		"access-env: PRODUCTION",
		"cache-control: no-cache",
		"content-type: application/json"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	if($err){
		$res = [
			'error' => true,
			'message' => 'Não foi possível realizar a operação. Tente novamente em instantes.'
		];
		exit(json_encode($res));
	}

	$retorno = json_decode($response, true);

	if( !empty($retorno['erro']) ){
		$res = [
			'error' => true,
			'message' => $retorno['erro']
		];
		exit(json_encode($res));
	}

	if( !empty($retorno['exception']) ){
		$res = [
			'error' => true,
			'message' => $retorno['exception']
		];
		exit(json_encode($res));
	}
    
    if( !empty($retorno['message']) ){
		$res = [
			'error' => false,
			'message' => $retorno['message']
		];
		exit(json_encode($res));
	}

	exit;
}

$layout_menu = "callcenter";
$title = "APROVAÇÃO DE TICKET";

include "cabecalho_new.php";

$plugins = ["shadowbox", "mask"];
include("plugin_loader.php");

$curl = curl_init();
curl_setopt_array($curl, array(
	CURLOPT_URL => "{$apiUrl}/ticket-checkin/ticket-finalizado/companyHash/{$companyHash}/ticket/{$ticket}",
    CURLOPT_RETURNTRANSFER => true,
  	CURLOPT_ENCODING => "",
  	CURLOPT_MAXREDIRS => 10,
  	CURLOPT_TIMEOUT => 30,
  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  	CURLOPT_CUSTOMREQUEST => "GET",
  	CURLOPT_POSTFIELDS => "",
  	CURLOPT_HTTPHEADER => array(
  		"access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
    	"access-env: PRODUCTION",
    	"cache-control: no-cache",
    	"content-type: application/json"
  	),
));

$resCurl = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
	exit;
} else {
    $ticket = json_decode($resCurl, true);
	$response = json_decode($ticket[0]['response'], true);
	$responseModificado = json_decode($ticket[0]['response_modificado'], true);

	if( !empty($responseModificado) ){
		$response = $responseModificado;
	}

    $responseTratado = [];
    foreach($response as $item){
		$responseTratado[$item['name']] = $item['content'];
		if( isset($item['deleted']) ){
			$responseTratado[$item['name']]['deleted'] = $item['deleted'];
		}
	}
}

function getOs($os, $fabrica){
	global $con;

	$sql ="SELECT tbl_os.os,
			   tbl_tipo_atendimento.descricao as tipo_atendimento,
			   consumidor_nome,
			   consumidor_cidade,
			   tbl_os.fabrica,
			   (SELECT tbl_tecnico.nome FROM tbl_tecnico_agenda
				  JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico
                 WHERE tbl_tecnico_agenda.os = tbl_os.os
                   AND tbl_tecnico_agenda.fabrica = {$fabrica}
		      ORDER BY tecnico_agenda
		    DESC LIMIT 1) AS nome_tecnico,
               (SELECT TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY HH24:MI')
                  FROM tbl_tecnico_agenda
                  JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico
                 WHERE tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$fabrica}
              ORDER BY tecnico_agenda
            DESC LIMIT 1) AS data_agendamento
        FROM tbl_os
        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
       WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$fabrica}";

    $resource = pg_query($con, $sql);
	return pg_affected_rows($resource) ? pg_fetch_assoc($resource) : null;
}

// Defeito constatado
$sql = "SELECT DISTINCT tbl_defeito_constatado.defeito_constatado, tbl_defeito_constatado.descricao FROM tbl_diagnostico
JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado  AND tbl_defeito_constatado.fabrica = {$login_fabrica}
JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
WHERE tbl_diagnostico.fabrica = {$login_fabrica} 
AND tbl_os.os = {$os} 
AND tbl_diagnostico.ativo IS TRUE 
and tbl_diagnostico.familia = tbl_produto.familia 
ORDER BY tbl_defeito_constatado.descricao ASC";

$pgResource = pg_query($con, $sql);
$listaDefeitosConstatados = pg_fetch_all($pgResource);

// Solução
$sql = "SELECT solucao, descricao FROM tbl_solucao WHERE fabrica = {$login_fabrica}";
$pgResource = pg_query($con, $sql);
$listaSolucoes = pg_fetch_all($pgResource);

// Serviço realizado
$sql = "SELECT servico_realizado, descricao FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica}";
$pgResource = pg_query($con, $sql);
$listaServicoRealizado = pg_fetch_all($pgResource);

// Informações da OS
$infoOs = getOs($os, $login_fabrica);
?>

<style>
	.container{ width: 900px; padding: 10px; }

	.j-row{ display: flex }

	.j-row-anexos {
		display: flex;
		align-items: flex-start;
	}

	.j-col-1 { flex-basis:  8.3333% }
	.j-col-2 { flex-basis: 16.6666% }
	.j-col-3 { flex-basis: 25% }
	.j-col-4 { flex-basis: 33.3333% }
	.j-col-5 { flex-basis: 41.6666% }
	.j-col-6 { flex-basis: 50% }
	.j-col-7 { flex-basis: 58.3333% }
	.j-col-8 { flex-basis: 66.6666% }
	.j-col-9 { flex-basis: 75% }
	.j-col-10 { flex-basis: 83.3333% }
	.j-col-11 { flex-basis: 91.6666% }
	.j-col-12 { flex-basis: 100% }

	.j-mr-1 { margin-right: 5px }

	.j-mt-1 { margin-top: 5px }
	.j-mt-2 { margin-top: 10px }

	.j-bg-error { background-color: #ff000070 !important}
	.j-bg-light { background-color: #f9f9f9 }
</style>

<div class="informacoes-os">
	<div>
		<h5 style="text-align: center;">Informações OS</h5>
		<hr style="margin: 5px; padding: 5px;">
	</div>

	<div class="j-row">
		<div class="j-col-2 j-mr-1">
			<label>
				<strong>OS</strong>
			</label>
			<input type="text" value="<?=$infoOs['os']?>" disabled style="width: 100%">
		</div>
		<div class="j-col-3 j-mr-1">
			<label>
				<strong>Tipo Atendimento</strong>
			</label>
			<input type="text" value="<?=$infoOs['tipo_atendimento']?>" disabled style="width: 100%">
		</div>
		<div class="j-col-5 j-mr-1">
			<label>
				<strong>Cliente</strong>
			</label>
			<input type="text" value="<?=$infoOs['consumidor_nome']?>" disabled style="width: 100%">
		</div>
		<div class="j-col-2 j-mr-1">
			<label>
				<strong>Cidade</strong>
			</label>
			<input type="text" value="<?=$infoOs['consumidor_cidade']?>" disabled style="width: 100%">
		</div>
	</div>
	<div class="j-row">
		<div class="j-col-6 j-mr-1">
			<label>
				<strong>Técnico</strong>
			</label>
			<input type="text" value="<?=$infoOs['nome_tecnico']?>" disabled style="width: 100%">
		</div>
		<div class="j-col-3 j-mr-1">
			<label>
				<strong>Agendamento</strong>
			</label>
			<input type="text" value="<?=$infoOs['data_agendamento']?>" disabled style="width: 100%">
		</div>
		<div class="j-col-3 j-mr-1">
			<label>
				<strong>Finalizado</strong>
			</label>
			<input type="text" value="<?= (new DateTIme($ticket[0]['data_finalizado']))->format('d/m/Y H:i') ?>" disabled style="width: 100%">
		</div>
	</div>
</div>

<div class="informacoes-gerais">
	<div class="">
		<h5 style="text-align: center;">Informações gerais</h5>
		<hr style="margin: 5px; padding: 5px;">
	</div>

	<div class="j-row">

		<?php if(isset($responseTratado['horimetro'])): ?>
			<div class="j-col-4 j-mr-1">
			    <label>
			    	<strong>Horimetro</strong>
			    </label>
				<input type="text" class="apenasLetras" value="<?= $responseTratado['horimetro'][0]['value'] ?>" style="width: 100%" name="horimetro">
			</div>
		<?php endif; ?>

		<?php if(isset($responseTratado['adicionais'])): ?>
			<?php
				$pedagio = array_filter($responseTratado['adicionais'], function($el){
					if( $el['name'] === 'pedagio' ){
						return true;
					}
				});

				$pedagio = array_values($pedagio);
			?>
			<div class="j-col-4 j-mr-1">
			    <label>
			    	<strong>Pedágio</strong>
			    </label>
				<input type="text" class="money apenasLetras" value="<?= number_format($pedagio[0]['value'], 2, '.', ''); ?>" style="width: 100%" name="pedagio">
			</div>
		<?php endif; ?>

		<?php if(isset($responseTratado['adicionais'])): ?>
			<?php
				$alimentacao = array_filter($responseTratado['adicionais'], function($el){
					if( $el['name'] === 'alimentacao' ){
						return true;
					}
				});

				$alimentacao = array_values($alimentacao);
			?>
			<div class="j-col-4 j-mr-1">
			    <label>
			    	<strong>Alimentação</strong>
			    </label>
				<input type="text" class="money apenasLetras" value="<?= number_format($alimentacao[0]['value'], 2, '.', '') ?>" style="width: 100%" name="alimentacao">
			</div>
		<?php endif; ?>
	</div>

	<?php if(isset($responseTratado['defeitos'])): ?>
		<div class="j-row" style="justify-content: center">
			<div class="j-col-12 j-mr-1">
				<label>
					<strong>Defeito reclamado</strong>
		    	</label>
				<input type="text" value="<?= utf8_decode($responseTratado['defeitos'][0]['value']) ?>" style="width: 100%" name="defeito_reclamado">
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- PRODUTOS -->
<div class="informacoes-produto">
	<h5 style="text-align: center;">
		Produto
		<button class="btn btn-success" onclick="addProdutoForm()">
			<i class="icon-plus icon-white"></i>
		</button>
	</h5>
	<hr style="margin: 0; padding: 0;">

	<div class="produto-content" style="text-align: center;">
		<?php if(isset($responseTratado['produto'])): ?>
			<?php foreach($responseTratado['produto'] as $item): ?>
				<?php
					$produto = json_decode($item['valueArray'], true);
					$defeitoConstatado = explode('|', $produto['defeito_constatado']);
					$solucao = explode('|', $produto['solucao']);
				?>
				<div class="j-row produto-item j-mt-1" style="background-color: #f9f9f9; padding: 5px; justify-content: center;">
	    			<div class="j-col-6 j-mr-1">
	    				<label>
				    		<strong>Defeito constatado</strong>
					    </label>

						<select style="width: 100%" name="defeito_constatado">
							<?php foreach($listaDefeitosConstatados as $item): ?>
								<option value="<?= $item['defeito_constatado'] ?>"
								<?=$item['defeito_constatado'] == $defeitoConstatado[0] ? 'selected' : null?>
								data-defeito_constatado="<?=$item['defeito_constatado']?>"
								data-descricao="<?=$item['descricao']?>">

									<?= $item['descricao'] ?> 
								</option>
							<?php endforeach; ?>
						</select>

	    			</div>
	    			<div class="j-col-5 j-mr-1">
	    				<label>
				    		<strong>Solução</strong>
					    </label>

					    <select style="width: 100%" name="solucao">
							<?php foreach($listaSolucoes as $item): ?>
								<option value="<?=$item['solucao'] ?>" 
								<?=$item['solucao'] == $solucao[0] ? 'selected' : null?>
								data-solucao="<?=$item['solucao']?>"
								data-descricao="<?=$item['descricao']?>">
								 
									<?= $item['descricao'] ?>
								</option>
							<?php endforeach; ?>
						</select>

	    			</div>
	    			<div class="j-col-1 j-mr-1" style="margin-top: 20px; text-align: center">
	    				<button class="btn btn-danger" onclick="removerProdutoForm(this)">
	    					<i class="icon-remove icon-white"></i>
	    				</button>
	    			</div>
	    		</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<!-- LISTA BÁSICA -->
<div class="informacoes-lista-basica">
	<h5 style="text-align: center;">
		Lista básica
		<button class="btn btn-success" onclick="addPecaForm()">
			<i class="icon-plus icon-white"></i>
		</button>
	</h5>
	<hr style="margin: 0; padding: 0;">

	<div class="lista-basica" data-contador="<?= count($responseTratado['lista_basica']) ?>" style="margin-top: 5px;">
		<?php if(isset($responseTratado['lista_basica'])): 
			$pecasExcluidas = $responseTratado['lista_basica']['deleted']['inputs'];

				foreach($responseTratado['lista_basica'] as $key => $item): ?>
				<?php
					if( $key === 'deleted' ){
						continue;
					}

					static $contador = 1;
					$listaBasica = json_decode($item['valueArray'], true);

					$referencia = explode('|', $listaBasica['peca_referencia']);
					$servicoRealizado = explode('|', $listaBasica['servico_realizado']);
					$quantidade = $listaBasica['quantidade'];

					$isDeleted = false;
					foreach( $pecasExcluidas as $peca ){
						$tmp = json_decode($peca, true);
						$ref = explode('|', $tmp['peca_referencia']);

						$isDeleted = ($ref[0] == $referencia[0]);
						
						if($isDeleted){
							break;
						}
					}
				?>
				<div class="lista-basica-item j-row j-bg-light" 
					 data-item="<?=$contador?>" 
					 data-api="true"
					 <?= $isDeleted ? 'data-deleted="true"' : '' ?>
					 style="padding: 5px; justify-content: center;">

					<div class="j-col-5 j-mr-1">
						<label for="">
							<strong> Peça referência </strong>
						</label>
						<input  type="text"
							    name="referencia"
								value="<?= $referencia[0] .' - '. utf8_decode($referencia[1]) ?>"
								data-referencia="<?= $referencia[0] ?>"
								data-descricao="<?=$referencia[1]?>"
								style="width: 100%"
								<?= $isDeleted ? 'disabled' : '' ?>>
					</div>
					<div class="j-col-4 j-mr-1">
						<label for="">
							<strong> Serviço realizado </strong>
						</label>

						<select style="width: 100%" name="servico" <?= $isDeleted ? 'disabled' : '' ?>>
							<?php foreach($listaServicoRealizado as $item): ?>
								<option value="<?= $item['servico_realizado'] ?>"
								<?=$item['servico_realizado'] == $servicoRealizado[0] ? 'selected' : null?>
								data-servico="<?=$item['servico_realizado']?>"
								data-descricao="<?=$item['descricao']?>">

									<?= $item['descricao'] ?> 
								</option>
							<?php endforeach; ?>
						</select>

					</div>
					<div class="j-col-1 j-mr-1">
						<label for="">
							<strong> Quantidade </strong>
						</label>
						<input type="text" class="apenasLetras" value="<?= $quantidade ?>" name="quantidade" style="width: 100%" <?= $isDeleted ? 'disabled' : '' ?>>
					</div>
					<?php if(!$isDeleted): ?>
						<div class="j-col-2 btn-group-action" style="margin-top: 20px; text-align: center;">
							
							<button class="btn btn-danger" onclick="removerPecaForm(this)">
								<i class="icon-remove icon-white"></i>
							</button>
							<button class="btn btn-info" onclick="openShadowPesquisa(this)">
								<i class="icon-search icon-white"></i>
							</button>
						</div>
					<?php else: ?>
						<div class="j-col-2" style="margin-top: 20px; text-align: center;">
							<span style="color: red; font-weight: bold"> Esta peça será excluída </span>	
						</div>
					<?php endif; ?>
				</div>
				<?php $contador++; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<!-- OBSERVAÇÃO -->
<div class="">
	<h5 style="text-align: center;"> Observação </h5>
	<hr style="margin: 0; padding: 0;">
</div>

<div class="j-row">
	<div class="j-col-12">
		<?php if(isset($responseTratado['observacao'])): ?>
			<textarea name="observacao" cols="30" rows="5" style="width: 100%"> <?= utf8_decode($responseTratado['observacao'][0]['value']) ?> </textarea>
		<?php endif; ?>
	</div>
</div>

<!-- ANEXOS -->
<div class="">
	<h5 style="text-align: center;"> Anexos </h5>
	<hr style="margin: 0; padding: 0;">
</div>

<div class="j-mt-2" style="width: 879px">
	<?php if(isset($responseTratado['anexos'])): ?>
		<?php foreach($responseTratado['anexos'] as $anexo): ?>
			<div class="j-row" style="width: 439px;margin-bottom: 10px; float: left;">
				<img width="200" src="http://api2.telecontrol.com.br/tdocs/document/id/<?=$anexo['uniqueId']?>"
					class="j-mr-1 img-polaroid"
					style="cursor: pointer; min-width: 110px"
					onclick="openImage(this)">

				<div>
					<?php foreach($anexo['info'] as $n) { ?>
						<?php if($n['name'] == 'tipo') { ?>
							<p> <strong> Tipo </strong>: <?= utf8_decode($n['value']) ?> </p>
						<?php } ?>
					<?php } ?>

					</br>

					<?php foreach($anexo['info'] as $n) { ?>
						<?php if($n['name'] == 'descricao') { ?>
							<p> <strong> Descrição </strong>: <?= utf8_decode($n['value']) ?> </p>
						<?php } ?>
					<?php } ?>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
<div style="clear:both;"></div>

<!-- ASSINATURAS -->
<div class="">
	<h5 style="text-align: center;"> Assinaturas </h5>
	<hr style="margin: 0; padding: 0;">
</div>

<div class="j-row">
	<?php if(isset($responseTratado['assinatura'])): ?>
		<?php 
			$arrTratadoAssinatura = [];
			foreach($responseTratado['assinatura'][0]['info'] as $info){
				$arrTratadoAssinatura[$info['name']] = $info['value'];
			}
		?>
		<div class="j-col-6 j-mr-1">
			<div class="" style="text-align: center">
				<h5>Consumidor</h5>

				<img src="http://api2.telecontrol.com.br/tdocs/document/id/<?=$responseTratado['assinatura'][0]['uniqueId']?>">
				
				<div style="text-align: center"> 
					<strong>Nome:</strong> 
					<?= utf8_decode($arrTratadoAssinatura['nome']) ?> 
				</div>

				<div style="text-align: center"> 
					<strong>Cargo:</strong> 
					<?= utf8_decode($arrTratadoAssinatura['cargo']) ?> 
				</div>

				<div style="text-align: center"> 
					<strong>Tipo Documento:</strong> 
					<?=$arrTratadoAssinatura['tipo_documento']?> 
				</div>

				<div style="text-align: center"> 
					<strong>Documento:</strong> 
					<?=$arrTratadoAssinatura['documento']?> 
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?php if(isset($responseTratado['assinatura_tecnico'])): ?>
		<?php 
			$arrTratadoTecnico = [];
			foreach($responseTratado['assinatura_tecnico'][0]['info'] as $info){
				$arrTratadoTecnico[$info['name']] = $info['value'];
			}
		?>
		<div class="j-col-6 j-mr-1" style="display: flex; justify-content: space-around">
			<div class="" style="text-align: center">
				<h5>Técnico</h5>

				<img src="http://api2.telecontrol.com.br/tdocs/document/id/<?=$responseTratado['assinatura_tecnico'][0]['uniqueId']?>">

				<div style="text-align: center"> 
					<strong>Nome:</strong> 
					<?= utf8_decode($arrTratadoTecnico['nome']) ?> 
				</div>

				<div style="text-align: center"> 
					<strong>Cargo:</strong> 
					<?= utf8_decode($arrTratadoTecnico['cargo']) ?> 
				</div>
				<div style="text-align: center"> 
					<strong>Tipo Documento:</strong> 
					<?=$arrTratadoTecnico['tipo_documento']?> 
				</div>

				<div style="text-align: center"> 
					<strong>Documento:</strong> 
					<?=$arrTratadoTecnico['documento']?> 
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

<hr>

<div class="j-row">
	<div class="j-col-12" style="text-align: center">
		<!-- <button class="btn btn-success" onclick="onSalvar()"> Salvar </button> -->

		<a href="#myModal" role="button" class="btn btn-success" data-toggle="modal">Salvar</a>
	</div>
</div>
<br>
<br>

<!-- Modal -->
<div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-body" style="text-align: center">
    <p>Clique na ação desejada!</p>
  </div>
  <div class="modal-footer">
    <div class="group-btn-modal">
		<button class="btn btn-danger" data-dismiss="modal" aria-hidden="true" style="float: right">Fechar</button>
		<button class="btn btn-primary" style="float: left" onclick="onSalvar()"> Salvar Alterações </button>
		<button class="btn btn-success" style="float: left" onclick="onSalvarAprovar()"> Salvar Alterações e Aprovar Ticket </button>
	</div>
	<img src="imagens/ajax-loader_2.gif" class="loader-modal" style="display: none; margin: 0 auto">
  </div>
</div>

<script>
	$(function(){
		Shadowbox.init();

		$('.money').mask('000.000,00', {reverse: true});
	});

	$(document).on('keyup blue', ".apenasLetras", function(){
		var valor = $(this).val();
		valor = valor.replace(/[a-zA-z]/g,'');
		$(this).val(valor);
	});

	let cacheUltimaPeca = '';

	function addProdutoForm(){
		const produtoForm = `
			<div class="j-row produto-item j-mt-1" style="background-color: #f9f9f9; padding: 5px; justify-content: center;">
    			<div class="j-col-6 j-mr-1">
    				<label>
			    		<strong>Defeito constatado</strong>
				    </label>
					<select style="width: 100%" name="defeito_constatado">
						<option value=""> Selecione </option>

						<?php foreach($listaDefeitosConstatados as $defeito): ?>
							<option value="<?=$defeito['defeito_constatado']?>"
							data-defeito_constatado="<?=$defeito['defeito_constatado']?>"
							data-descricao="<?=$defeito['descricao']?>">

								<?=$defeito['descricao']?>
							</option>
						<?php endforeach; ?>

					</select>
    			</div>
    			<div class="j-col-5 j-mr-1">
    				<label>
			    		<strong>Solução</strong>
				    </label>
				    <select style="width: 100%" name="solucao">
				    	<option value=""> Selecione </option>

							<?php foreach($listaSolucoes as $solucao): ?>
								<option value="<?=$solucao['solucao']?>"
								data-solucao="<?=$solucao['solucao']?>"
								data-descricao="<?=$solucao['descricao']?>">

									<?=$solucao['descricao']?>
								</option>
							<?php endforeach; ?>

				    </select>
    			</div>
    			<div class="j-col-1 j-mr-1" style="margin-top: 20px; text-align: center">
    				<button class="btn btn-danger" onclick="removerProdutoForm(this)">
    					<i class="icon-remove icon-white"></i>
    				</button>
    			</div>
    		</div>
		`

		$(".produto-content").append(produtoForm);
	}

	function removerProdutoForm(refButton){
		$(refButton).parents('.produto-item').remove();
	}

	function addPecaForm(){
		const containerListaBasica = document.querySelector('.lista-basica');
		let contador = document.querySelector('.lista-basica').dataset.contador;

		const pecaForm = `
			<div class="lista-basica-item j-row" data-item="${++contador}" style="background-color: #f9f9f9; padding: 5px; justify-content: center;">
				<div class="j-col-5 j-mr-1">
					<label for="">
						<strong> Peça referência </strong>
					</label>
					<input type="text" value="" name="referencia" style="width: 100%">
				</div>
				<div class="j-col-4 j-mr-1">
					<label for="">
						<strong> Serviço realizado </strong>
					</label>
					<select style="width: 100%" name="servico">
	    				<option value=""> Selecione um serviço </option>
						
						<?php foreach($listaServicoRealizado as $servico): ?>
							<option value="<?=$servico['servico_realizado']?>"
							data-servico="<?=$servico['servico_realizado']?>"
							data-descricao="<?=$servico['descricao']?>">

								<?= $servico['descricao'] ?>
							</option>
						<?php endforeach; ?>

					</select>
				</div>
				<div class="j-col-1 j-mr-1">
					<label for="">
						<strong> Quantidade </strong>
					</label>
					<input type="text" class="apenasLetras" value="1" name="quantidade" style="width: 100%">
				</div>
				<div class="j-col-2" style="margin-top: 20px; text-align: center;">
					<button class="btn btn-info" onclick="openShadowPesquisa(this)">
						<i class="icon-search icon-white"></i>
					</button>
					<button class="btn btn-danger" onclick="removerPecaForm(this)">
						<i class="icon-remove icon-white"></i>
					</button>
				</div>
			</div>
		`;

		document.querySelector('.lista-basica').dataset.contador = contador;
		$('.lista-basica').append(pecaForm);
	}

	function removerPecaForm(refButton){
		const item = $(refButton).parents('.lista-basica-item'); //.remove();
		const api = item.data('api');

		if( api == null || api == 'undefined' || api == '' ){
			item.remove();
			return;
		}

		if( !confirm("Deseja realmente excluir essa peça?") ){
			return;	
		}

		item.attr("data-deleted", "true");

		const inputPeca = item.find('input[name=referencia]');
		const inputServico = item.find('select[name=servico]');
		const inputQuantidade = item.find('input[name=quantidade]');

		inputPeca.attr('disabled', true);
		inputServico.attr('disabled', true);
		inputQuantidade.attr('disabled', true);

		const content = item.find('.btn-group-action');
		content.html('');
		content.html(`
			<div class="j-col-2" style="text-align: center;">
				<span style="color: red; font-weight: bold"> Esta peça será excluída </span>	
			</div>
		`);
	}

	function openShadowPesquisa(refButton){
		const containerListaBasica = $(refButton).parents('.lista-basica-item');
		const inputReferencia = containerListaBasica.find("input[name=referencia]").first();

		let ref = inputReferencia.data('referencia');
		if(ref != null && ref != 'undefined'){
			var urlForSearch = "admin/peca_lupa_new.php?parametro=referencia&valor=" + ref;
		}else{
			var urlForSearch = "admin/peca_lupa_new.php?parametro=descricao&valor=" + inputReferencia.val();
		}

		cacheUltimaPeca = containerListaBasica.data('item');

		Shadowbox.open({
    		content: urlForSearch,
      		player: "iframe",
    		width:  1000,
      		height: 500
  		});
	}

	function retorna_peca(infoPeca){
		const inputReferencia = $('.lista-basica').find(`[data-item=${cacheUltimaPeca}]`);
		const input = inputReferencia.find('input[name=referencia]');

		input.val(`${infoPeca.referencia} - ${infoPeca.descricao}`);
		input.attr("data-referencia", infoPeca.referencia);
		input.attr("data-descricao", infoPeca.descricao);
	}

	function openImage(refElement){
		const imageWidth = refElement.width, imageHeight = refElement.height;

		let height = 1024, width = 1024;
		if( imageWidth > imageHeight ){
			width = 1024; height = 1024;
		}

		const content = `<img src="${refElement.src}" style="width: 100%; height: 100%; ">`;

		Shadowbox.open({content, player: "html", width, height});
	}

	function montarResponse(){
		const horimetro = document.querySelector('input[name=horimetro]').value;
		const pedagio = document.querySelector('input[name=pedagio]').value;
		const alimentacao = document.querySelector('input[name=alimentacao]').value;
		const defeitoReclamado = document.querySelector('input[name=defeito_reclamado]').value;
		const observacao = document.querySelector('textarea[name=observacao]').value;

		const listaDeProdutos = [];
		const produtoItem = document.querySelector(".produto-content").querySelectorAll('.produto-item');
		produtoItem.forEach(function(item){
			const defeitoConstatadoSelect = item.querySelector("select[name=defeito_constatado]");
			const defeitoConstatadoOption = defeitoConstatadoSelect.options[defeitoConstatadoSelect.selectedIndex];

			const solucaoSelect = item.querySelector("select[name=solucao]");
			const solucaoOption = solucaoSelect.options[solucaoSelect.selectedIndex];

			listaDeProdutos.push({
				defeito_constatado: `${defeitoConstatadoOption.dataset.defeito_constatado}|${defeitoConstatadoOption.dataset.descricao}`,
				solucao: `${solucaoOption.dataset.solucao}|${solucaoOption.dataset.descricao}`
			})
		});

		const listaBasica = [];
		const listaBasicaItem = document.querySelector(".lista-basica").querySelectorAll('.lista-basica-item');
		listaBasicaItem.forEach(function(item){
			const inputReferencia = item.querySelector("input[name=referencia]"); 
			const selectServico = item.querySelector("select[name=servico]");
			const selectOption = selectServico.options[selectServico.selectedIndex];
			const inputQuantidade = item.querySelector("input[name=quantidade]");

			listaBasica.push({
				peca_referencia: `${inputReferencia.dataset.referencia}|${inputReferencia.dataset.descricao}`,
				quantidade: inputQuantidade.value,
				servico_realizado: `${selectOption.dataset.servico}|${selectOption.dataset.descricao}`
			});
		});

		const listaDeletados = [];
		listaBasicaItem.forEach(function(item){
			if( item.dataset.deleted ){
				const inputReferencia = item.querySelector("input[name=referencia]"); 
				const selectServico = item.querySelector("select[name=servico]");
				const selectOption = selectServico.options[selectServico.selectedIndex];
				const inputQuantidade = item.querySelector("input[name=quantidade]");

				listaDeletados.push({
					peca_referencia: `${inputReferencia.dataset.referencia}|${inputReferencia.dataset.descricao}`,
					quantidade: inputQuantidade.value,
					servico_realizado: `${selectOption.dataset.servico}|${selectOption.dataset.descricao}`
				})
			}
		});

		const formData = {
			horimetro, 
			pedagio, 
			alimentacao, 
			defeitoReclamado,
			observacao,
			listaDeProdutos,
			listaBasica,
			listaDeletados,
			ticket: JSON.stringify(<?=json_encode($ticket)?>)
		};

		return formData;
	}

	function removerErros(){
		$('.j-bg-error').removeClass('j-bg-error');
	}

	async function onSalvar(){
		$(".group-btn-modal").css('display', 'none');
		$(".loader-modal").css('display', 'block');

		removerErros();

		const formData = montarResponse();
		formData.salvarTicket = true;

		let errors = [];
		formData.listaDeProdutos.forEach(function(produto, index){
			const infoDefeito = produto.defeito_constatado.split('|');
			const infoSolucao = produto.solucao.split('|');

			if(
				(infoDefeito[0] === 'undefined' || infoDefeito[0] === '') ||
				(infoSolucao[0] === 'undefined' || infoSolucao[1] === '')
			){
				errors.push({
					pos: index
				});
			}
		});

		const produtoItem = document.querySelector(".produto-content").querySelectorAll('.produto-item');
		if( errors.length > 0 ){
			$(".group-btn-modal").css('display', 'block');
			$(".loader-modal").css('display', 'none');
			$('#myModal').modal('hide');

			errors.forEach(function(peca){
				$(produtoItem[peca.pos]).addClass('j-bg-error');
			});

			alert('Existem campos com erro, favor corrigir.');
			return;
		}

		errors = [];
		
		formData.listaBasica.forEach(function(peca, index){
			const infoPeca = peca.peca_referencia.split('|');
			const infoServico = peca.servico_realizado.split('|');

			if( 
				(infoPeca[0] === 'undefined' || infoPeca[0] === '' ) ||
				(infoPeca[1] === 'undefined' || infoPeca[1] === '') ||
				(infoServico[0] === 'undefined' || infoServico[0] === '') ||
				(infoServico[1] === 'undefined' || infoServico[1] === '') ||
				(peca.quantidade === '' || peca.quantidade === 'undefined' || peca.quantidade === '0') 
			){
				errors.push({
					pos: index,
					referencia: infoPeca[0],
					descricao: infoPeca[1]
				});				
			}
		});

		const listaBasicaItem = $(".lista-basica").find('.lista-basica-item');

		// Verifica se existe algum erro no formData
		if( errors.length > 0 ){	
			$(".group-btn-modal").css('display', 'block');
			$(".loader-modal").css('display', 'none');
			$('#myModal').modal('hide');

			errors.forEach(function(peca){
				$(listaBasicaItem[peca.pos]).addClass('j-bg-error');
			});

			alert('Existem campos com erro, favor corrigir.');
			return;
		}

		try {
			let response = await $.post(window.location.href, formData);
			response = JSON.parse(response);

			$(".group-btn-modal").css('display', 'block');
			$(".loader-modal").css('display', 'none');
			$('#myModal').modal('hide');

			if( response['error'] === true ){
				alert(response['message']);
				return;
			}

			alert(response['message']);
		}catch(e){
			alert('Erro ao processar solicitação. Tente novamente em instantes.');
		}
	}

	async function onSalvarAprovar(){
		$(".group-btn-modal").css('display', 'none');
		$(".loader-modal").css('display', 'block');

		removerErros();

		const formData = montarResponse();
		formData.salvarAprovarTicket = true;

		let errors = [];
		formData.listaDeProdutos.forEach(function(produto, index){
			const infoDefeito = produto.defeito_constatado.split('|');
			const infoSolucao = produto.solucao.split('|');

			if(
				(infoDefeito[0] === 'undefined' || infoDefeito[0] === '') ||
				(infoSolucao[0] === 'undefined' || infoSolucao[1] === '')
			){
				errors.push({
					pos: index
				});
			}
		});

		const produtoItem = document.querySelector(".produto-content").querySelectorAll('.produto-item');
		if( errors.length > 0 ){
			$(".group-btn-modal").css('display', 'block');
			$(".loader-modal").css('display', 'none');
			$('#myModal').modal('hide');

			errors.forEach(function(peca){
				$(produtoItem[peca.pos]).addClass('j-bg-error');
			});

			alert('Existem campos com erro, favor corrigir.');
			return;
		}

		errors = [];
		
		formData.listaBasica.forEach(function(peca, index){
			const infoPeca = peca.peca_referencia.split('|');
			const infoServico = peca.servico_realizado.split('|');

			if( 
				(infoPeca[0] === 'undefined' || infoPeca[0] === '' ) ||
				(infoPeca[1] === 'undefined' || infoPeca[1] === '') ||
				(infoServico[0] === 'undefined' || infoServico[0] === '') ||
				(infoServico[1] === 'undefined' || infoServico[1] === '') ||
				(peca.quantidade === '' || peca.quantidade === 'undefined' || peca.quantidade === '0') 
			){
				errors.push({
					pos: index,
					referencia: infoPeca[0],
					descricao: infoPeca[1]
				});				
			}
		});

		const listaBasicaItem = $(".lista-basica").find('.lista-basica-item');

		// Verifica se existe algum erro no formData
		if( errors.length > 0 ){	
			$(".group-btn-modal").css('display', 'block');
			$(".loader-modal").css('display', 'none');
			$('#myModal').modal('hide');

			errors.forEach(function(peca){
				$(listaBasicaItem[peca.pos]).addClass('j-bg-error');
			});

			alert('Existem campos com erro, favor corrigir.');
			return;
		}

		try {
			let response = await $.post(window.location.href, formData);
			response = JSON.parse(response);

			$(".group-btn-modal").css('display', 'block');
			$(".loader-modal").css('display', 'none');
			$('#myModal').modal('hide');

			if( response['error'] === true ){
				alert(response['message']);
				return;
			}

			alert(response['message']);
			window.location.href = "os_press.php?os=" + <?=$os?>
		}catch(e){
			console.log(e);
			alert('Erro ao processar solicitação. Tente novamente em instantes.');
		}
	}
</script>
