<?php
ini_set('memory_limit', '1024M');
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_admin.php';

if ($login_fabrica != 10) {
	http_response_code(418);
	exit;
}

function getMimeType($ext) {
	switch ($ext) {
	case 'doc':
		$mimeType = 'application|msword';
		break;

	case 'docx':
		$mimeType = 'application|vnd.openxmlformats-officedocument.wordprocessingml.document';
		break;

	case 'csv':
		$mimeType = 'text|csv';
		break;

	case 'jpeg':
	case 'jpg':
		$mimeType = 'image|jpeg';
		break;

	case 'ods':
		$mimeType = 'application|vnd.oasis.opendocument.spreadsheet';
		break;

	case 'odt':
		$mimeType = 'application|vnd.oasis.opendocument.text';
		break;

	case 'png':
		$mimeType = 'image|png';
		break;

	case 'pdf':
		$mimeType = 'application|pdf';
		break;

	case 'xls':
		$mimeType = 'application|vnd.ms-excel';
		break;

	case 'xlsx':
		$mimeType = 'application|vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		break;

	default:
		$mimeType = 'application|octet-stream';
		break;
	}

	return $mimeType;
}

if ($_REQUEST['ajax_listar_anexos']) {
	try {
		#include '/aws-amazon/sdk/sdk.class.php';

		$os      = $_REQUEST['os'];
		$fabrica = $_REQUEST['fabrica'];
		$data    = $_REQUEST['data'];

		list($ano, $mes, $dia) = explode('-', $data);

		$fabrica = str_pad($fabrica, 4, 0, STR_PAD_LEFT);
		$mes     = str_pad($mes, 2, 0, STR_PAD_LEFT);

		include_once S3CLASS;
    	$s3 = new AmazonTC("extrato", (int) 152);
    	
		$anexos = $s3->getObjectList($os."-nota_fiscal_servico");

		if (empty($anexos)) {
			$res = [
				'sem_anexos' => true
			];
		} else {
			$res = [
				'sem_anexos' => false,
				'anexos'   => []
			];

			foreach ($anexos as $anexo) {
				$nome     = preg_replace('/.+\//', '', $anexo);
				$extensao = preg_replace('/.+\./', '', $nome);
				$nome     = str_replace(".{$extensao}", '', $nome);
				$extensao = strtolower($extensao);
				$mimeType = getMimeType($extensao);
				$tamanho  = 0;


				$sql = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = {$fabrica} AND referencia_id = {$os} AND obs::jsonb->0->>'filename' = '{$file}';";
				$resB = pg_query($con,$sql);

				if(pg_num_rows($resB) > 0){
					continue;
				}

				array_push($res['anexos'], array(
					'nome' => $nome,
					'extensao' => $extensao,
					'mimeType' => $mimeType,
					'tamanho' => $tamanho
				));
			}
		}

		http_response_code(200);
		exit(json_encode($res));
	} catch(\Exception $e) {
		http_response_code(400);
		exit;
	}
}

if ($_REQUEST['ajax_os_processada']) {
	try {
		$os      = $_REQUEST['os'];
		$fabrica = $_REQUEST['fabrica'];
		$anexo_retorno = $_REQUEST['anexo_retorno'];

		if ($anexo_retorno == "Sem Anexo") {
			http_response_code(200);
			exit(json_encode([
				'sucesso' => true
			]));			
		}

		$sql = "
			INSERT INTO tbl_extrato_nota_avulsa (fabrica, extrato, data_lancamento, nota_fiscal, data_emissao, valor_original, observacao) VALUES ($fabrica, $os, now(), '0', now(), 0, '$anexo_retorno')
		";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
			throw new \Exception('Erro ao remover OS da fila');
		}

		http_response_code(200);
		exit(json_encode([
			'sucesso' => true
		]));
	} catch(\Exception $e) {
		http_response_code(400);
		exit;
	}
}

if ($_REQUEST['ajax_transferir_arquivo']) {
	include '/aws-amazon/sdk/sdk.class.php';
	$s3 = new AmazonS3();

	$anexos  = $_REQUEST['arquivos'];
	$os      = $_REQUEST['os'];
	$fabrica = $_REQUEST['fabrica'];

	$sql_da = "SELECT data_geracao FROM tbl_extrato WHERE extrato = $os";
	$res_da = pg_query($con, $sql_da);

	list($ano, $mes, $dia) = explode('-', pg_fetch_result($res_da, 0, 'data_geracao'));
	$s3_fabrica = str_pad($fabrica, 4, '0', STR_PAD_LEFT);
	#$s3_caminho = $s3_fabrica . '/' . $ano . '/' . $mes;

	$response = [];

	foreach ($anexos as $anexo) {
		try {
			$nome     = $anexo['nome'];
			$extensao = $anexo['extensao'];
			$mimeType = $anexo['mimeType'];
			$tamanho  = $anexo['tamanho'];

			$response[$nome] = [];
			$file = $nome.$extensao;

			$sql = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = {$fabrica} AND referencia_id = {$os} AND obs::jsonb->0->>'filename' = '{$file}';";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				continue;
			}

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/s3/name/{$nome}/extension/{$extensao}/mime/{$mimeType}/size/{$tamanho}",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_HTTPHEADER => array(
					'access-application-key: 32e1ea7c54c0d7c144bc3d3045d8309a5b137af9',
					'access-env: PRODUCTION',
					'content-type: application/json'
				)
			));

			$curl_response = curl_exec($curl);
			$curl_error = curl_error($curl);

			curl_close($curl);

			if ($curl_error) {
				throw new Exception($curl_error);
			} else {
				$curl_response = json_decode($curl_response, true);
			}

			$tdocs_id = $curl_response['id'];

			if (empty($tdocs_id)) {
				throw new \Exception('Erro ao gerar tdocs_id');
			}

			$response[$nome]['tdocs_id'] = $tdocs_id;

			$s3->copy_object(
				['bucket' => 'br.com.telecontrol.webuploads', 'filename' => 'extrato_nota_fiscal_servico/'.$s3_fabrica . '/' . $nome.'.'.$extensao],
				['bucket' => 'br.com.telecontrol.tdocs-devel', 'filename' => $tdocs_id],
				['acl' => $s3::ACL_PRIVATE, 'storage' => $s3::STORAGE_STANDARD]
			);

			$response[$nome]['copiado'] = true;

			$obs = json_encode([
			0 => [
				'acao'     => 'anexar',
				'filename' => $nome.'.'.$extensao,
				'filesize' => $tamanho,
				'date'     => date('c'),
				'fabrica'  => $fabrica,
				'page'     => $_SERVER['PHP_SELF'],
				'source'   => 'moved-manually',
				'usuario'  => [],
				'typeId'   => 'nfe_servico'
			]]);

			$insert = "
					INSERT INTO tbl_tdocs
					(tdocs_id, fabrica, contexto, situacao, obs, referencia, referencia_id)
					VALUES
					('{$tdocs_id}', {$fabrica}, 'extrato', 'ativo', '{$obs}', 'extrato', {$os})
				";
			$res = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				throw new \Exception('Erro ao gravar associar anexo a OS');
			}
		} catch (\Exception $e) {
			$response[$nome]['erro'] = utf8_encode($e->getMessage());
		}
	}

	http_response_code(200);
	exit(json_encode($response));
}

$sqlFabricas = '
SELECT fabrica, nome
FROM tbl_fabrica
WHERE ativo_fabrica IS TRUE
ORDER BY nome ASC
';
$resFabricas = pg_query($con, $sqlFabricas);
$fabricas = pg_fetch_all($resFabricas);

$layout_menu = 'gerencia';
$title = 'S3 to Box Uploader';

include 'cabecalho_new.php';

$plugins = [
	'select2',
	'font_awesome'
];
include 'plugin_loader.php';
?>

<form class='form-search form-inline tc_formulario' method='post'>
<div class='titulo_tabela'>Selecione a Fábrica</div>
<br />
<div class='row-fluid'>
<div class='span4'></div>
<div class='span4'>
<div class='control-group'>
<div class='controls controls-row'>
<select id='fabrica' name='fabrica' class='span12'>
<option value='' >Selecione</option>
<?php
foreach ($fabricas as $fabrica) {
	$selected = (getValue('fabrica') == $fabrica['fabrica']) ? 'selected' : null;
?>
	<option value='<?=$fabrica['fabrica']?>' <?=$selected?> ><?=$fabrica['fabrica']?> - <?=$fabrica['nome']?></option>
<?php
}
?>
</select>
</div>
</div>
</div>
</div>

<p class='tac'>
<button type='submit' class='btn'><i class='fa fa-search'></i> Pesquisar</button>
</p>

<br />
</form>

<script>

$('#fabrica').select2();

$('button[type=submit]').on('click', e => {
e.preventDefault();

$(e.target).prop({ disabled: true });

const i = $(e.target).find('i');
$(i).removeClass('fa-check').addClass('fa-spinner fa-pulse');

$(e.target).parents('form').submit();
});

</script>

<?php
if ($_POST) {
	$fabrica = getValue('fabrica');

	$sqlFila = "
SELECT fabrica, extrato AS os, data_geracao AS data
FROM tbl_extrato
WHERE fabrica = {$fabrica}
AND tbl_extrato.data_geracao BETWEEN '2019-01-01 00:00' AND '2019-12-30 23:59:59'
AND tbl_extrato.extrato NOT IN (SELECT extrato FROM tbl_extrato_nota_avulsa WHERE fabrica = $fabrica)
ORDER BY extrato DESC
";
	
$resFila = pg_query($con, $sqlFila);

$array_os = pg_fetch_all($resFila);
?>
<hr />

<div class='row-fluid'>
<div class='span4'>
<div class='alert alert-info os-fila'>
<h4>0</h4> OSs na fila
</div>
</div>
<div class='span4'>
<div class='alert alert-success os-processada'>
<h4>0</h4> OSs processadas
</div>
</div>
<div class='span4'>
<div class='alert alert-error os-erro'>
<h4>0</h4> Erros
</div>
</div>
</div>

<div class='row-fluid'>
<div class='span6'>
<div class='panel panel-info'>
<div class='panel-heading'>
<h4 class='panel-title'>
&nbsp;&nbsp;&nbsp;
<i class='fa fa-cloud-upload-alt'></i>
OS <span class='os-atual'>N/A</span>
<small>Próxima OS <span class='proxima-os'>N/A</span></small>
<button type='button' id='btn-acao' class='btn btn-success btn-mini pull-right' data-acao='iniciar' style='margin-right: 10px;' ><i class='fa fa-play'></i></button>
</h4>
</div>
<div class='panel-body tac acao-atual'>
<br />
<h4>Aguardando Início</h4>
<br />
</div>
</div>
</div>
<div class='span6'>
<div class='panel panel-danger'>
<div class='panel-heading'>
<h4 class='panel-title'>
&nbsp;&nbsp;&nbsp;
<i class='fa fa-exclamation-triangle'></i>
Anexos com erro
</h4>
</div>
<div class='panel-body tac lista-erros' style='height: 100%;'>
<br />
<h4>Aguardando Início</h4>
<br />
</div>
</div>
</div>
</div>

	<script>
	/*fila*/
	const array_os         = <?=json_encode($array_os)?>;

	/*elementos*/
	const elemOsFila       = $('.os-fila').find('h4');
	const elemOsProcessada = $('.os-processada').find('h4');
	const elemOsErro       = $('.os-erro').find('h4');
	const elemOsAtual      = $('.os-atual');
	const elemProximaOs    = $('.proxima-os');
	const elemAcaoAtual    = $('.acao-atual');
	const elemListaErros   = $('.lista-erros');

	/*contadores*/
	let fila        = array_os.length;
	let processadas = 0;
	let erros       = 0;

	/*controladores*/
	let indice  = 0;
	let os      = null;
	let proxima = null;
	let ajax    = false;
	let anexo_retorno = null;

	/*define a próxima ordem de serviço*/
	const proximaOs = () => {
	if (os === null) {
		proxima = array_os[indice];
	}

	os = proxima;
	indice++;
	proxima = array_os[indice];

	$(elemOsAtual).text(os.os);
	$(elemProximaOs).text(proxima.os);
	$(elemOsProcessada).text(processadas);
	$(elemOsFila).text(fila);
	$(elemOsErro).text(erros);
	}

	/*lista os anexos de uma ordem de serviço*/
	const listarAnexos = () => new Promise((resolve, reject) => {
	$(elemAcaoAtual).html('\
		<br />\
			<h4><i class="fa fa-spinner fa-pulse"></i> Buscando Anexos</h4>\
				<br />\
					');

	$.ajax({
	url: window.location,
		type: 'get',
		data: {
		ajax_listar_anexos: true,
			os: os.os,
			fabrica: os.fabrica,
			data: os.data
					},
					async: true,
					timeout: 60000
					}).fail(() => {
					reject({
					os: os,
						acao: 'listarAnexos'
					});
					}).done((res, req) => {
					if (req === 'success') {
						anexo_retorno = JSON.parse(res);
						if (anexo_retorno['anexos'] != undefined && anexo_retorno['anexos'] != null) {
							anexo_retorno = anexo_retorno['anexos']['0']['nome'];
						} else if (anexo_retorno['sem_anexos'] == true) {
							anexo_retorno = 'Sem Anexo';
						} 
						resolve(JSON.parse(res));
					} else {
						reject({
						os: os,
							acao: 'listarAnexos'
					}); 
					}
					});
	});

	/*marca uma ordem de serviço como processada*/
	const osProcessada = () => new Promise((resolve, reject) => {
	$(elemAcaoAtual).find('h4').html('<i class="fa fa-spinner fa-pulse"></i> Removendo da fila</h4>');

	$.ajax({
	url: window.location,
		type: 'get',
		data: {
		ajax_os_processada: true,
			os: os.os,
			fabrica: os.fabrica,
			anexo_retorno: anexo_retorno
	},
		async: true,
		timeout: 60000
	}).fail(() => {
	reject({
	os: os,
		acao: 'osProcessada'
	});
	}).done((res, req) => {
	if (req === 'success') {
		resolve(JSON.parse(res));
	} else {
		reject({
		os: os,
			acao: 'osProcessada'
	}); 
	}
	});
	});

	/*adiciona um erro*/
	const adicionarErro = err => {
	let table = null;

	if ($(elemListaErros).find('i.fa-frown').length == 0) {
		$(elemListaErros).html('\
			<br />\
				<h4><i class="fa fa-frown"></i></h4>\
					<br />\
						');

		table = $('<table></table>', {
		class: 'table table-bordered table-striped',
			html: '\
				<thead>\
					<tr>\
						<th>OS</th>\
						<th>Anexo</th>\
						<th>Ação</th>\
					</tr>\
				</thead>\
				<tbody></tbody>\
				',
			css: {
			marginBottom: '0px'
		}
						});

		$(elemListaErros).append(table);
	}

	if (table === null) {
		table = $(elemListaErros).find('table');
	}

	let data = {};

	if (err.os) {
		data.os = err.os;
	}

	if (err.anexo) {
		data.anexo = err.anexo;
	} else {
		err.anexo = { nome: '' };
	}

	if (err.acao) {
		data.acao = err.acao;
	}

	let tr = $('<tr></tr>', {
	data: data,
		html: `
	<td class='tac'>${err.os.os}</td>
		<td>${err.anexo.nome}</td>
		<td class='tac'>${err.acao}</td>
		`
	});

	$(table).find('tbody').append(tr);

	erros++;
	}

	/*efetua a transferência de um arquivo do S3 (antigo) para o TDocs (novo)*/
	const transferirArquivo = async arquivos => {
	return new Promise((resolve, reject) => {
	$.ajax({
	url: window.location,
		type: 'get',
		data: {
		ajax_transferir_arquivo: true,
			arquivos: arquivos,
			os: os.os,
			fabrica: os.fabrica
	},
		async: true,
		timeout: 60000
	}).always((res, req) => {
	if (req === 'success') {
		res = JSON.parse(res);

		arquivos = arquivos.map(arquivo => {
		arquivo.response = res[arquivo.nome]
			return arquivo;
		});

		resolve(arquivos);
	} else {
		reject({
		os: os,
			response: JSON.parse(res.responseText),
acao: 'transferirArquivo'
	});
	}
	});
	});
	}

	/*processa uma ordem de serviço*/
	const processar = () => {
	(new Promise((resolve, reject) => {
	listarAnexos().then(async res => {
	/*se possuir anexo lista os anexos*/
	if (res.sem_anexos !== true) {
		let arquivos = [];
		let controle = [];

		let table = $('<table></table>', {
		class: 'table table-bordered table-striped',
			html: '\
				<thead>\
					<tr>\
						<th>Arquivo</th>\
							<th>Status</th>\
								</tr>\
									</thead>\
										<tbody></tbody>\
',
css: {
marginBottom: '0px'
}
		});
		let tr;

		res.anexos.forEach((anexo, i) => {
		tr = $('<tr></tr>', {
		id: anexo.nome,
			data: anexo,
			html: `
		<td>${anexo.nome}.${anexo.extensao}</td>
			<td class='tac'><i class='fa fa-spinner fa-pulse'></i></td>
			`
		});
		$(table).find('tbody').append(tr);

		arquivos.push(anexo);
		});

		$(elemAcaoAtual).html('\
			<br />\
				<h4><i class="fa fa-spinner fa-pulse"></i> Enviando arquivos</h4>\
					<br />\
						');
		$(elemAcaoAtual).append(table);

		/*aguardando terminar todas as transferência para prosseguir*/
		await transferirArquivo(arquivos).then(res => {
		res.forEach(anexo => {
		if (typeof anexo.response.erro == 'undefined') {
			$(`tr[id='${anexo.nome}']`).find('td').last().html('<i class="fa fa-check"></i>');
		} else {
			$(`tr[id='${anexo.nome}']`).find('td').last().html('<i class="fa fa-exclamation-triangle"></i>');
			adicionarErro({
			os: os,
				acao: 'transferirArquivo',
				anexo: anexo
			});
		}
		});
		})
			.catch(err => {
			$(elemAcaoAtual).find('table > tbody > tr').each(function() {
				$(this).find('td:last').html('<i class="fa fa-exclamation-triangle"></i>');
			});
			adicionarErro(err);
		});
	}

	osProcessada().then(res => {
	processadas++;
	resolve();
	}).catch(err => {
	adicionarErro(err);
	reject();
	});
	}).catch(err => {
	adicionarErro(err);
	reject();
	});
	})).then(() => {
	fila--;
	proximaOs();

	if (ajax === true) {
		processar();
	} else {
		$('#btn-acao').removeClass('btn-info').addClass('btn-success');
		$('#btn-acao').find('i').removeClass('fa-spinner fa-pulse').addClass('fa-play');
		$('#btn-acao').data({ acao: 'iniciar' }).prop({ disabled: false });

		$(elemAcaoAtual).html('\
			<br />\
			<h4>Aguardando Início</h4>\
			<br />\
		');
	}
	}).catch(() => {
	fila--;
	proximaOs();

	if (ajax === true) {
		processar();
	} else {
		$('#btn-acao').removeClass('btn-info').addClass('btn-success');
		$('#btn-acao').find('i').removeClass('fa-spinner fa-pulse').addClass('fa-play');
		$('#btn-acao').data({ acao: 'iniciar' }).prop({ disabled: false });

		$(elemAcaoAtual).html('\
			<br />\
				<h4>Aguardando Início</h4>\
					<br />\
						');
	}
	});
	}

	$('#btn-acao').on('click', e => {
	const btn   = $(e.delegateTarget);
	const icone = $(btn).find('i');
	const acao  = $(btn).data('acao');

	if (acao == 'iniciar') {
		$(btn).removeClass('btn-success').addClass('btn-warning');
		$(icone).removeClass('fa-play').addClass('fa-pause');
		$(btn).data({ acao: 'pausar' });
		$(elemListaErros).html('\
			<br />\
				<h4><i class="fa fa-smile"></i></h4>\
					<br />\
						');

		ajax = true;

		processar(os);
	} else if (acao == 'pausar') {
		$(btn).removeClass('btn-warning').addClass('btn-info').prop({ disabled: true });
		$(icone).removeClass('fa-pause').addClass('fa-spinner fa-pulse');

		ajax = false;
	}
	});

	$(elemOsFila).text(fila);
	proximaOs();
	</script>
<?php
}

include 'rodape.php';
