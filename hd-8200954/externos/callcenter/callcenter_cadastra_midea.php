<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';

include 'class/aws/s3_config.php';
include_once S3CLASS;


$login_fabrica = 169;

$page = $_REQUEST["page"];

if($page == "springer"){
	$legend = "<h1>Olá.</h1>
				Bem-vindo ao Atendimento on-line Springer.<br/>
				Para falar conosco, preencha seus dados abaixo.";
}else if($page == "mideacarrier"){
	$legend = "ENVIE UMA <span class='msg_l'>MENSAGEM</span>";
}else if($page == "mideadobrasil"){
	$legend = "<h1>Olá.</h1>
				Bem-vindo ao Atendimento on-line Midea.<br/>
				Para falar conosco, preencha seus dados abaixo.";
}

$array_estado = array(
	'AC' => 'Acre',
	'AL' => 'Alagoas',
	'AM' => 'Amazonas',
	'AP' => 'Amapá',
	'BA' => 'Bahia',
	'CE' => 'Ceara',
	'DF' => 'Distrito Federal',
	'ES' => 'Espírito Santo',
	'GO' => 'Goiás',
	'MA' => 'Maranhão',
	'MG' => 'Minas Gerais',
	'MS' => 'Mato Grosso do Sul',
	'MT' => 'Mato Grosso',
	'PA' => 'Pará',
	'PB' => 'Paraíba',
	'PE' => 'Pernambuco',
	'PI' => 'Piauí­',
	'PR' => 'Paraná',
	'RJ' => 'Rio de Janeiro',
	'RN' => 'Rio Grande do Norte',
	'RO' => 'Rondônia',
	'RR' => 'Roraima',
	'RS' => 'Rio Grande do Sul',
	'SC' => 'Santa Catarina',
	'SE' => 'Sergipe',
	'SP' => 'São Paulo',
	'TO' => 'Tocantins'
);

function validaCep() {
	global $_POST;

	$cep = $_POST["cep"];

	if (!empty($cep)) {
		try {
			$endereco = CEP::consulta($cep);

			if (!is_array($endereco)) {
				throw new Exception("CEP inválido");
			}
		} catch (Exception $e) {
			throw new Exception("CEP inválido");
		}
	}
}

function validaEstado() {
	global $array_estado, $_POST;

	$estado = strtoupper($_POST["estado"]);

	if (!empty($estado) && !in_array($estado, array_keys($array_estado))) {
		throw new Exception("Estado inválido");
	}
}

function validaCidade() {
	global $con, $_POST;

	$cidade = utf8_decode($_POST["cidade"]);
	$estado = strtoupper($_POST["estado"]);

	if (!empty($cidade) && !empty($estado)) {
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' AND UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Cidade não encontrada".$sql);
		}
	}
}

function validaEmail() {
	global $_POST;

	$email = $_POST["email"];

	if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Email inválido");
	}
}

function checaCPF(){
    global $_POST, $con, $login_fabrica;	// Para conectar com o banco...

    $cpf = $_POST['cpf'];
    $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

	if(strlen($cpf) > 0){
		$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
		if ($res_cpf === false) {
			$cpf_erro = pg_last_error($con);
			if ($use_savepoint) $n = @pg_query($con,"ROLLBACK TO SAVEPOINT checa_CPF");
			throw new Exception("CPF informado inválido");
		}
	}
}


if ($_POST["ajax_enviar"]) {

	$regras = array(
		"notEmpty" => array(
			"nome",
			"email",
			"cpf",
			"estado",
			"cidade",
			"endereco",
			"celular",
			"telefone",
			"motivo_contato",
			"mensagem"
		),
		"validaCep"    => "cep",
		"validaEstado" => "estado",
		"validaCidade" => "cidade",
		"validaEmail"  => "email",
		"checaCPF" 	   => "cpf"
	);

	if(strlen(trim($_POST['telefone'])) > 0){
		unset($regras['notEmpty'][6]);
	}else if(strlen(trim($_POST['celular'])) > 0){
		unset($regras['notEmpty'][7]);
	}

	$msg_erro = array(
		"msg"    => array(),
		"campos" => array()
	);

	foreach ($regras as $regra => $campo) {
		switch ($regra) {
			case "notEmpty":
				foreach($campo as $input) {
					$valor = trim($_POST[$input]);

					if (empty($valor)) {
						$msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
						$msg_erro["campos"][]   = $input;
					}
				}
				break;

			default:
				$valor = trim($_POST[$campo]);
				if (!empty($valor)) {
					try {
						call_user_func($regra);
					} catch(Exception $e) {
						$msg_erro["msg"][]    = utf8_encode($e->getMessage());
						$msg_erro["campos"][] = $campo;
					}
				}
				break;
		}
	}

	if (count($msg_erro["msg"]) > 0) {
		$retorno = array("erro" => $msg_erro);
	} else {
		$nome         	= utf8_decode(trim($_POST["nome"]));
		$email        	= trim($_POST["email"]);
		$telefone     	= trim($_POST["telefone"]);
		$celular 		= trim($_POST['celular']);
		$cpf 			= trim($_POST['cpf']);
		$cep          	= trim($_POST["cep"]);
		$estado       	= $_POST["estado"];
		$cidade       	= utf8_decode($_POST["cidade"]);
		$bairro       	= utf8_decode(trim($_POST["bairro"]));
		$endereco     	= utf8_decode(trim($_POST["endereco"]));
		$numero       	= trim($_POST["numero"]);
		$complemento  	= utf8_decode(trim($_POST["complemento"]));
		$motivo_contato = utf8_decode($_POST["motivo_contato"]);
		$mensagem     	= utf8_decode(trim($_POST["mensagem"]));



		if ($page == "springer"){
			$xmensagem = "Aberto via Springer. <br/></br>".$mensagem;
			$login_fale_conosco = "springer_fale_conosco";
		}elseif($page == "mideacarrier"){
			$xmensagem = "Aberto via MideaCarrier. <br/></br>".$mensagem;
			$login_fale_conosco = "midea_carrier_fale_conosco";
		}elseif($page == "mideadobrasil"){
			$xmensagem = "Aberto via Midea do Brasil. <br/></br>".$mensagem;
			$login_fale_conosco = "midea_fale_conosco";
		}elseif($page == "carrier"){
			$xmensagem = "Aberto via Carrier do Brasil. <br/></br>".$mensagem;
			$login_fale_conosco = "carrier_fale_conosco";
		}

		$anexo_arquivo_1 = $_FILES['anexo_arquivo_1'];
		$anexo_arquivo_2 = $_FILES['anexo_arquivo_2'];
		$anexo_arquivo_3 = $_FILES['anexo_arquivo_3'];

		try {
			$sql = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica AND login = '".$login_fale_conosco."' ";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro pesquisar Admin");
			}

			if (pg_num_rows($res) > 0){
				$admin_fale_conosco = pg_fetch_result($res, 0, 'admin');
			}

			$sql = "SELECT tbl_hd_origem_admin.admin AS atendente,
							tbl_admin.email,
							tbl_admin.nome_completo AS atendente_nome,
							COUNT(tbl_hd_chamado.hd_chamado) AS qtde_chamado
					FROM tbl_hd_origem_admin
					JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_origem_admin.hd_chamado_origem
						AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica = {$login_fabrica}
					LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_hd_origem_admin.admin
						AND tbl_hd_chamado.fabrica = {$login_fabrica}
						AND lower(tbl_hd_chamado.status) not in ('resolvido', 'cancelado')
						/*AND tbl_hd_chamado.data::date = current_date*/
					WHERE tbl_hd_origem_admin.fabrica = {$login_fabrica}
					AND tbl_admin.atendente_callcenter IS TRUE
					AND tbl_hd_chamado_origem.descricao = 'Fale Conosco'
					GROUP BY tbl_hd_origem_admin.admin, tbl_admin.email, tbl_admin.nome_completo
					ORDER BY qtde_chamado LIMIT 1 ";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro pesquisar Atendente");
			}

			if(pg_num_rows($res) > 0){
				$atendente = pg_fetch_result($res, 0, 'atendente');
				$atendente_nome = pg_fetch_result($res, 0, 'atendente_nome');
				$atendente_email = pg_fetch_result($res, 0, 'email');
			}

			pg_query($con, "BEGIN");

			$sql = "INSERT INTO tbl_hd_chamado (
									admin,
									data,
									atendente,
									fabrica_responsavel,
									fabrica,
									titulo,
									status,
									categoria
								) VALUES (
									$admin_fale_conosco,
									CURRENT_TIMESTAMP,
									$atendente,
									$login_fabrica,
									$login_fabrica,
									'Atendimento Fale Conosco',
									'Aberto',
									'$motivo_contato'
								)RETURNING hd_chamado";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao abrir o atendimento");
			}

			$hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

			$cidade = retira_acentos($cidade);

			$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('{$cidade}') AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade_id = pg_fetch_result($res, 0, "cidade");
			} else {
				$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER('{$cidade}') AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
					$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

					$sql = "INSERT INTO tbl_cidade (
								nome, estado
							) VALUES (
								'{$cidade_ibge}', '{$cidade_estado_ibge}'
							) RETURNING cidade";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao abrir o atendimento");
					}

					$cidade_id = pg_fetch_result($res, 0, "cidade");
				}
			}

			$cep = preg_replace("/\D/", "", $cep);
			$cpf = preg_replace("/\D/", "", $cpf);
			$telefone = preg_replace("/\D/","", $telefone);
			$celular = preg_replace("/\D/","", $celular);

			$sql = "INSERT INTO tbl_hd_chamado_extra (
									hd_chamado,
									nome,
									cpf,
									email,
									celular,
									fone,
									cep,
									cidade,
									bairro,
									endereco,
									numero,
									complemento,
									origem,
									reclamado
								) VALUES (
									$hd_chamado,
									'$nome',
									'$cpf',
									'$email',
									'$celular',
									'$telefone',
									'$cep',
									$cidade_id,
									'$bairro',
									'$endereco',
									'$numero',
									'$complemento',
									'Fale Conosco',
									'$xmensagem'
							)";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception(utf8_encode("Erro ao abrir o atendimento"));
			}

			$sql = "INSERT INTO tbl_hd_chamado_item (
								hd_chamado,
								admin,
								comentario,
								status_item
							) VALUES (
								$hd_chamado,
								$admin_fale_conosco,
								'$xmensagem',
								'Aberto'
							)";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao abrir o atendimento");
			}

			$s3 = new AmazonTC("callcenter", $login_fabrica);

			if(strlen($anexo_arquivo_1['name']) > 0 && $anexo_arquivo_1['size'] > 0){
				$ext1 = strtolower(preg_replace("/.+\./", "", $anexo_arquivo_1["name"]));

				if (!in_array($ext1, array('png', 'jpg', 'jpeg', 'pdf'))) {
					$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
				}else{
					$pathinfo 	= pathinfo($anexo_arquivo_1['name']);
					$type  		= $pathinfo['extension'];
					$s3->upload("{$hd_chamado}-1", $anexo_arquivo_1, null, null);
				}
			}

			if(strlen($anexo_arquivo_2['name']) > 0 && $anexo_arquivo_2['size'] > 0){
				$ext2 = strtolower(preg_replace("/.+\./", "", $anexo_arquivo_2["name"]));
				if (!in_array($ext2, array('png', 'jpg', 'jpeg', 'pdf'))) {
					$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
				}else{
					$pathinfo 	= pathinfo($anexo_arquivo_2['name']);
					$type  		= $pathinfo['extension'];
					$s3->upload("{$hd_chamado}-2", $anexo_arquivo_2, null, null);
				}
			}

			if(strlen($anexo_arquivo_3['name']) > 0 && $anexo_arquivo_3['size'] > 0){
				$ext3 = strtolower(preg_replace("/.+\./", "", $anexo_arquivo_3["name"]));
				if (!in_array($ext3, array('png', 'jpg', 'jpeg', 'pdf'))) {
					$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
				}else{
					$pathinfo 	= pathinfo($anexo_arquivo_3['name']);
					$type  		= $pathinfo['extension'];
					$s3->upload("{$hd_chamado}-3", $anexo_arquivo_3, null, null);
				}
			}

			if (count($msg_erro["msg"]) > 0) {
				$retorno = array("erro" => $msg_erro);
			}else{
				$assunto  		= $atendente_nome.' - Atendimento '.$hd_chamado;
        		$mensagem 		= "<strong>Foi aberto o Atendimento: $hd_chamado via Fale Conosco.</strong><br><br>";
        		$externalId    	= 'smtp@posvenda';
				$externalEmail 	= 'naorespondablueservice@carrier.com.br';

				$mailTc = new TcComm($externalId);
				$res = $mailTc->sendMail(
					$atendente_email,
					$assunto,
					$mensagem,
					$externalEmail
				);

				pg_query($con, "COMMIT");
				$retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
			}
		} catch (Exception $e) {
			$msg_erro["msg"][] = $e->getMessage();
			$retorno = array("erro" => $msg_erro);
			pg_query($con, "ROLLBACK");
		}
	}
	exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_cidades"]) {
	$estado = strtoupper(trim($_GET["estado"]));

	if (empty($estado)) {
		$retorno = array("erro" => utf8_encode("Estado não informado"));
	} else {
		$sql = "SELECT DISTINCT nome FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' ORDER BY nome ASC";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => "Erro ao carregar cidades");
		} else {
			$retorno = array("cidades" => array());

			while ($cidade = pg_fetch_object($res)) {
				$retorno["cidades"][] = utf8_encode(strtoupper($cidade->nome));
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST['deleta_imagens']) {
	$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
	$file = $_POST['file'];
	$callcenter = $_POST['callcenter'];

	$s3->deleteObject($file);

    if ($s3->result == false) {
        echo json_encode(array('erro' => 'Erro ao deletar arquivo'));
    	exit;
    }else{
    	$s3->getObjectList("{$callcenter}-", false);
		$qtde_img = count($s3->files);

    	echo json_encode(array('sucess' => 'Imagem deletada com sucesso', 'qtde_img' => $qtde_img));
    	exit;
    }
}

if ($_POST["ajax_pesquisar"]) {

	$regras = array(
		"notEmpty" => array(
			"hd_chamado",
			"cpf_pesquisa"
		),
	);

	$msg_erro = array(
		"msg"    => array(),
		"campos" => array()
	);

	foreach ($regras as $regra => $campo) {
		switch ($regra) {
			case "notEmpty":
				foreach($campo as $input) {
					$valor = trim($_POST[$input]);

					if (empty($valor)) {
						$msg_erro["msg"]["obg"] = "Preencha todos os campos obrigatórios";
						$msg_erro["campos"][]   = $input;
					}
				}
				break;

			default:
				$valor = trim($_POST[$campo]);
				if (!empty($valor)) {
					try {
						call_user_func($regra);
					} catch(Exception $e) {
						$msg_erro["msg"][]    = utf8_encode($e->getMessage());
						$msg_erro["campos"][] = $campo;
					}
				}
				break;
		}
	}

	if (!count($msg_erro["msg"]) > 0) {
		$hd_chamado 	= utf8_decode(trim($_POST["hd_chamado"]));
		$cpf_pesquisa 			= trim($_POST['cpf_pesquisa']);
		$cpf_pesquisa = preg_replace("/\D/", "", $cpf_pesquisa);

		$sql_pesquisa = "SELECT
					tbl_hd_chamado.hd_chamado 			AS protocolo,
					tbl_hd_chamado_extra.nome 			AS nome_consumidor,
					tbl_hd_chamado_extra.cpf 			AS cpf_consumidor,
					tbl_hd_chamado_extra.email 			AS email_consumidor,
					tbl_hd_chamado_extra.fone 			AS fone_consumidor,
					tbl_hd_chamado_extra.celular 		AS celular_consumidor,
					tbl_hd_chamado_extra.cep			AS cep_consumidor,
					tbl_hd_chamado_extra.bairro 		AS bairro_consumidor,
					tbl_hd_chamado_extra.endereco 		AS endereco_consumidor,
					tbl_hd_chamado_extra.numero 		AS numero_consumidor,
					tbl_hd_chamado_extra.complemento	AS complemento_consumidor,
					tbl_hd_chamado_extra.nota_fiscal 	AS nota_fiscal,
					tbl_hd_chamado_extra.data_nf 		AS data_nf,
					tbl_hd_chamado.categoria 			AS categoria,
					tbl_cidade.nome 					AS cidade_consumidor,
					tbl_cidade.estado 					AS estado_consumidor,
					tbl_produto.referencia              AS referencia_produto,
					tbl_produto.descricao 				AS descricao_produto,
					tbl_hd_chamado_extra.reclamado 		AS info_complementares,
					tbl_status_checkpoint.descricao 	AS status_os,
					tbl_hd_chamado_extra.os 			AS numero_os
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_cidade ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
				LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os AND tbl_os.fabrica = {$login_fabrica}
				LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado_extra.cpf = '{$cpf_pesquisa}'
				AND tbl_hd_chamado.hd_chamado = {$hd_chamado}";
		$res_pesquisa = pg_query($con, $sql_pesquisa);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro["msg"][] = "Erro ao pesquisar atendimento";
		}

		// if(strlen($anexo_pesquisa_1['name']) > 0 && $anexo_pesquisa_1['size'] > 0){
		// 	$ext1 = strtolower(preg_replace("/.+\./", "", $anexo_pesquisa_1["name"]));

		// 	if (!in_array($ext1, array('png', 'jpg', 'jpeg', 'pdf'))) {
		// 		$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
		// 	}else{
		// 		$pathinfo 	= pathinfo($anexo_pesquisa_1['name']);
		// 		$type  		= $pathinfo['extension'];
		// 		$s3->upload("{$hd_chamado}-1", $anexo_pesquisa_1, null, null);
		// 	}
		// }

		// if(strlen($anexo_pesquisa_2['name']) > 0 && $anexo_pesquisa_2['size'] > 0){
		// 	$ext2 = strtolower(preg_replace("/.+\./", "", $anexo_pesquisa_2["name"]));
		// 	if (!in_array($ext2, array('png', 'jpg', 'jpeg', 'pdf'))) {
		// 		$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
		// 	}else{
		// 		$pathinfo 	= pathinfo($anexo_pesquisa_2['name']);
		// 		$type  		= $pathinfo['extension'];
		// 		$s3->upload("{$hd_chamado}-2", $anexo_pesquisa_2, null, null);
		// 	}
		// }

		// if(strlen($anexo_pesquisa_3['name']) > 0 && $anexo_pesquisa_3['size'] > 0){
		// 	$ext3 = strtolower(preg_replace("/.+\./", "", $anexo_pesquisa_3["name"]));
		// 	if (!in_array($ext3, array('png', 'jpg', 'jpeg', 'pdf'))) {
		// 		$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
		// 	}else{
		// 		$pathinfo 	= pathinfo($anexo_pesquisa_3['name']);
		// 		$type  		= $pathinfo['extension'];
		// 		$s3->upload("{$hd_chamado}-3", $anexo_pesquisa_3, null, null);
		// 	}
		// }

		// if (count($msg_erro["msg"]) > 0) {
		// 	$retorno = array("erro" => $msg_erro);
		// }else{
		// 	$retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
		// }

	}
}

if($_POST['anexo_pesquisa']){

	$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
	$anexo_pesquisa_1 	= $_FILES['anexo_pesquisa_1'];
	$anexo_pesquisa_2 	= $_FILES['anexo_pesquisa_2'];
	$anexo_pesquisa_3 	= $_FILES['anexo_pesquisa_3'];
	$hd_chamado 		= $_POST['callcenter'];

	if(strlen($anexo_pesquisa_1['name']) > 0 && $anexo_pesquisa_1['size'] > 0){
		$ext1 = strtolower(preg_replace("/.+\./", "", $anexo_pesquisa_1["name"]));

		if (!in_array($ext1, array('png', 'jpg', 'jpeg', 'pdf'))) {
			$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
		}else{
			$pathinfo 	= pathinfo($anexo_pesquisa_1['name']);
			$type  		= $pathinfo['extension'];
			$s3->upload("{$hd_chamado}-1", $anexo_pesquisa_1, null, null);
		}
	}

	if(strlen($anexo_pesquisa_2['name']) > 0 && $anexo_pesquisa_2['size'] > 0){
		$ext2 = strtolower(preg_replace("/.+\./", "", $anexo_pesquisa_2["name"]));
		if (!in_array($ext2, array('png', 'jpg', 'jpeg', 'pdf'))) {
			$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
		}else{
			$pathinfo 	= pathinfo($anexo_pesquisa_2['name']);
			$type  		= $pathinfo['extension'];
			$s3->upload("{$hd_chamado}-2", $anexo_pesquisa_2, null, null);
		}
	}

	if(strlen($anexo_pesquisa_3['name']) > 0 && $anexo_pesquisa_3['size'] > 0){
		$ext3 = strtolower(preg_replace("/.+\./", "", $anexo_pesquisa_3["name"]));
		if (!in_array($ext3, array('png', 'jpg', 'jpeg', 'pdf'))) {
			$msg_erro["campos"][]   = "Favor anexar arquivos no formato (png, jpg, jpeg ou pdf)";
		}else{
			$pathinfo 	= pathinfo($anexo_pesquisa_3['name']);
			$type  		= $pathinfo['extension'];
			$s3->upload("{$hd_chamado}-3", $anexo_pesquisa_3, null, null);
		}
	}
}
?>

<!DOCTYPE html />
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="language" content="pt-br" />

	<!-- jQuery -->
	<script type="text/javascript" src="plugins/jquery-1.11.3.min.js" ></script>

	<!-- Bootstrap -->
	<script type="text/javascript" src="plugins/bootstrap/js/bootstrap.min.js" ></script>
	<link rel="stylesheet" type="text/css" href="plugins/bootstrap/css/bootstrap.min.css" />

	<!-- Plugins Adicionais -->
	<script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
	<script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
	<script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
	<script type="text/javascript" src="../../plugins/jquery.form.js"></script>
	<link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

	<style type="text/css">
		<?php
			if($page == "springer"){
				include 'midea_files/springer.css';
			}else if($page == "mideacarrier"){
				include 'midea_files/mideacarrier.css';
			}else if ($page == 'mideadobrasil'){
				include 'midea_files/mideadobrasil.css';
			}else if ($page == "carrier"){
				include 'midea_files/carrier.css';
			}
		?>
	</style>
	<script>

	$(function() {

		/* FALE CONOSCO */
		$(document).on('click','#deleteAnexo1', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_arquivo_1]").val('');
			$("#desc1").html('');
			$('#desc1').hide();
		});
		$(document).on('click', '#deleteAnexo2', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_arquivo_2]").val('');
			$("#desc2").html('');
			$('#desc2').hide();
		});
		$(document).on('click', '#deleteAnexo3', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_arquivo_3]").val('');
			$("#desc3").html('');
			$('#desc3').hide();
		});

		$(document).on('click', '#alterarAnexo1', function(){
			$("input[name=anexo_arquivo_1]").val('');
			$("input[name=anexo_arquivo_1]").click();
		});
		$(document).on('click', '#alterarAnexo2', function(){
			$("input[name=anexo_arquivo_2]").val('');
			$("input[name=anexo_arquivo_2]").click();
		});
		$(document).on('click', '#alterarAnexo3', function(){
			$("input[name=anexo_arquivo_3]").val('');
			$("input[name=anexo_arquivo_3]").click();
		});
		/* FIM FALE CONOSCO */

		/* PESQUISA */
		$(document).on('click','#deleteAnexoPesquisa1', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_pesquisa_1]").val('');
			$("#desc_pesquisa1").html('');
			$('#desc_pesquisa1').hide();
		});
		$(document).on('click', '#deleteAnexoPesquisa2', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_pesquisa_2]").val('');
			$("#desc_pesquisa2").html('');
			$('#desc_pesquisa2').hide();
		});
		$(document).on('click', '#deleteAnexoPesquisa3', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_pesquisa_3]").val('');
			$("#desc_pesquisa3").html('');
			$('#desc_pesquisa3').hide();
		});

		$(document).on('click', '#alterarAnexoPesquisa1', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_pesquisa_1]").val('');
			$("input[name=anexo_pesquisa_1]").click();
		});
		$(document).on('click', '#alterarAnexoPesquisa2', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_pesquisa_2]").val('');
			$("input[name=anexo_pesquisa_2]").click();
		});
		$(document).on('click', '#alterarAnexoPesquisa3', function(){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) - 1;
			$("input[name=qtde_img]").val(valida_qtde);

			$("input[name=anexo_pesquisa_3]").val('');
			$("input[name=anexo_pesquisa_3]").click();
		});

		/* FIM PESQUISA */

		$("#cpf").mask("999.999.999-99");

		$("#cpf_pesquisa").mask("999.999.999-99");

		$("select").fancySelect();

		$("#telefone").each(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000", $(this).val());
            } else {
                $(this).mask("(00) 0000-0000", $(this).val());
            }
        });

        $("#telefone").keypress(function() {
        	if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000");
            } else {
               $(this).mask("(00) 0000-0000");
            }
        });

        $("#telefone").blur(function() {
    		if ($(this).val().length > 0){
        		$("#celular").parent().find('.campo_obrigatorio').html('');
            }else{
            	$("#celular").parent().find('.campo_obrigatorio').html('*');
            }
        });

        $("#celular").blur(function() {
        	var telefone = $("#telefone").val();

        	if ($(this).val().length > 0 && telefone.length == ""){
        		$("#telefone").parent().find('.campo_obrigatorio').html('');
            }else{
            	$("#telefone").parent().find('.campo_obrigatorio').html('*');
            }
        });

        $("#celular").each(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000", $(this).val());
            } else {
                $(this).mask("(00) 0000-0000", $(this).val());
            }
        });

        // $("#celular").keypress(function() {
        //     if ($(this).val().match(/^\(1\d\) 9/i)) {
        //         $(this).mask("(00) 00000-0000");
        //     } else {
        //        $(this).mask("(00) 0000-0000");
        //     }
        // });

        	var phoneMask = function(){
		if($(this).val().match(/^\(0/)){
        			$(this).val('(');
        			return;
	        	}

        		if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
        			$(this).mask('(00) 0000-0000');
        		}else{
			$(this).mask('(00) 00000-0000');
	        	}
        		$(this).keyup(phoneMask);
        	};

        	$('#celular').keyup(phoneMask);
        	$("#cep").mask("99999-999");

        	$("#cep").on("blur", function(){
	        	$("#mensagem_erro").removeClass("alert alert-erro");
	        	var cep = $(this).val();
	        	var method = "webservice";

			if (cep.length > 0) {

				$.ajax({
					async: true,
					url: "../../admin/ajax_cep.php",
					type: "GET",
					data: { cep: cep, method: method },
					error: function(xhr, status, error) {
                        $("#mensagem_erro").addClass("alert alert-erro");
                        $("#mensagem_erro").html("<h4>CEP errado.</h4>");

                    },
					success: function(data) {
						results = data.split(";");

						if (results[0] != "ok") {
							alert(results[0]);
						} else {
							var indexEstado = $("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').index();

							$('#estado option:eq('+indexEstado+')').prop('selected', true).trigger('change');

							carregaCidades(results[4],results[3]);

							// $("#cidade").val(results[3]);

							if (results[2].length > 0) {
								$("#bairro").val(results[2]);
							}

							if (results[1].length > 0) {
								$("#endereco").val(results[1]);
							}
						}

						if ($("#bairro").val().length == 0) {
							$("#bairro").focus();

						} else if ($("#endereco").val().length == 0) {
							$("#endereco").focus();

						} else if ($("#numero").val().length == 0) {
							$("#numero").focus();

						}
					}
				});
			}
        });

        $("#numero").numeric();
        $("#hd_chamado").numeric();

        $("input, textarea, select").blur(function() {
        	var valor = $.trim($(this).val());

        	if (valor.length > 0) {
        		if ($(this).parents("div.form-group").hasClass("has-error")) {
        			$(this).parents("div.form-group").removeClass("has-error");
        		}
        	}
        });

        $("#estado").on("change.fs", function() {
        	$(this).trigger("change.$");
        });

		$("#estado").change(function() {
			var value = $(this).val();

			if (value.length > 0) {
				carregaCidades(value);
			} else {
				$("#cidade").find("option:first").nextAll().remove();
				$("#cidade").trigger("update");
			}
		});

		$("#motivo_contato").on("change.fs", function() {
        	$(this).trigger("change.$");
        });

		$("#motivo_contato").change(function() {
			var value = $(this).val();

			if (value == "Assistência Técnica") {
				$("#div_produto, #div_familia").show();
			} else {
				$("#div_produto, #div_familia").hide();
			}
		});

		var btn;
		$("#form_fale_conosco").ajaxForm({
			complete:function(data){
				data = $.parseJSON(data.responseText);
				//data = JSON.parse(data);
				if (data.erro) {
					var msg_erro = [];

					$.each(data.erro.msg, function(key, value) {
						msg_erro.push(value);
					});

					$("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

					data.erro.campos.forEach(function(input) {
						$("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").parents("div.form-group").addClass("has-error");
					});

					$("#msg_erro").show();
				} else {
					if (typeof data.hd_chamado != "undefined") {
						$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
					} else {
						$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
					}

					$("div.form-group").find("input, textarea, select").val("");
					$("#estado, #cidade, #motivo_contato, #familia, #produto").trigger("update");

					$("input[name=anexo_arquivo_1]").val('');
					$("#desc1").html('');
					$('#desc1').hide();
					$("input[name=anexo_arquivo_2]").val('');
					$("#desc2").html('');
					$('#desc2').hide();
					$("input[name=anexo_arquivo_3]").val('');
					$("#desc3").html('');
					$('#desc3').hide();
				}

				$(document).scrollTop(0);
				$(btn).button("reset");
			}
		});

		$("#enviar").click(function() {
			btn      = $(this);

			$("div.input.erro").removeClass("erro");
			$("#msg_erro").html("").hide();
			$("#msg_sucesso").hide();
			$(btn).button("loading");

			$("#form_fale_conosco").submit();
		});
	});

	function addImg(box, param){

		var img_arr 	= new Array();
		var ext 		= "";

		if(param == 'faleconosco'){
			var img = $("input[name=anexo_arquivo_"+box+"]").val();
		}else{
			var img = $("input[name=anexo_pesquisa_"+box+"]").val();
		}


		if(param == 'pesquisar'){
			var qtde_img = $("input[name=qtde_img]").val();
			var valida_qtde = parseInt(qtde_img) + 1;
			if(valida_qtde >3){
				alert('Permitido somente 3 anexos');
				return;
			}
			$("input[name=qtde_img]").val(valida_qtde);
		}

		img_arr = img.split("\\");
		img = img_arr[img_arr.length - 1];

		img_arr = (img.split("."));

		if(img_arr[1].toLowerCase() != "png" && img_arr[1].toLowerCase() != "jpg" && img_arr[1].toLowerCase() != "jpeg" && img_arr[1].toLowerCase() != "bmp" && img_arr[1].toLowerCase() != "pdf" && img_arr[1] != "doc" && img_arr[1] != "txt"){
			alert("A extensão do Anexo não é Permitida, Anexar nos seguintes formatos (jpg, jpeg ou pdf");
			$("input[name=anexo_arquivo_"+box+"]").val('');
			return;
		}

		if(param == 'faleconosco'){
			$('#desc'+box).show();
			$("#desc"+box).html("<img src='../img/delete.png' style='height: 15px; margin-bottom: -2px; cursor: pointer;' title='Excluir Anexo' id='deleteAnexo"+box+"' /> <img src='../img/edit.png' style='height: 17px; margin-bottom: -3px; cursor: pointer;' title='Alterar Anexo' id='alterarAnexo"+box+"') /> Imagem <strong>"+img+"</strong> adicionada com Sucesso");
		}else{
			$('#desc_pesquisa'+box).show();
			$("#desc_pesquisa"+box).html("<img src='../img/delete.png' style='height: 15px; margin-bottom: -2px; cursor: pointer;' title='Excluir Anexo' id='deleteAnexoPesquisa"+box+"' /> <img src='../img/edit.png' style='height: 17px; margin-bottom: -3px; cursor: pointer;' title='Alterar Anexo' id='alterarAnexoPesquisa"+box+"') /> Imagem <strong>"+img+"</strong> adicionada com Sucesso");
		}
	}

	function addAnexos(param){

		if(param == 'faleconosco'){
			var anexo1 = $("input[name=anexo_arquivo_1]").val();
			var anexo2 = $("input[name=anexo_arquivo_2]").val();
			var anexo3 = $("input[name=anexo_arquivo_3]").val();
		}else{
			var anexo1 = $("input[name=anexo_pesquisa_1]").val();
			var anexo2 = $("input[name=anexo_pesquisa_2]").val();
			var anexo3 = $("input[name=anexo_pesquisa_3]").val();

			var anexada1 = 0;
			var anexada2 = 0;
			var anexada3 = 0;
			if($("input[name=img_anexada_nome1]").val() != undefined && $("input[name=img_anexada_nome1]").val() != ""){
				var anexada1 = 1;
			}

			if($("input[name=img_anexada_nome2]").val() != undefined && $("input[name=img_anexada_nome2]").val() != ""){
				var anexada2 = 1;
			}

			if($("input[name=img_anexada_nome3]").val() != undefined && $("input[name=img_anexada_nome3]").val() != ""){
				var anexada3 = 1;
			}
		}

		/*monteiro ..  testar ..
			esta subistituindo a img do primeiro anexo.
			verificar a quantidade valida_qtde não esta somando certo
			fazer layou outras telas

		*/

		if(anexo1.length > 0 && anexo2.length > 0 && anexo3.length > 0){
			alert("Permitido somente 3 anexos");
			return false;
		}else{
			if(param == 'faleconosco'){
				if(anexo1.length == 0){
					$("input[name=anexo_arquivo_1]").click();
				}
				if(anexo1.length > 0 && anexo2.length == 0){
					$("input[name=anexo_arquivo_2]").click();
				}
				if(anexo2.length > 0 && anexo3.length == 0){
					$("input[name=anexo_arquivo_3]").click();
				}
			}else{

				if(anexo1.length == 0 && anexada1 == 0){
					$("input[name=anexo_pesquisa_1]").click();
				}
				if(anexo1.length > 0 && anexo2.length == 0 || anexada2 == 0){
					$("input[name=anexo_pesquisa_2]").click();
				}
				if(anexo2.length > 0 && anexo3.length == 0 || anexada3 == 0){
					$("input[name=anexo_pesquisa_3]").click();
				}
			}
		}
	}

	function carregaCidades(estado,cidade) {
		var select_cidade = $("#cidade");

		$.ajax({
			url: "callcenter_cadastra_midea.php",
			type: "get",
			data: { ajax_carrega_cidades: true, estado: estado },
			beforeSend: function() {
				$(select_cidade).find("option:first").nextAll().remove();
				//$("#cidade_label").append("<span class='loading' >carregando...</span>")
			}
		}).done(function(data) {
			data = JSON.parse(data);
			if (data.erro) {
				alert(data.erro);
			} else {
				data.cidades.forEach(function(cidade) {
					var option = $("<option></option>", {
						value: cidade,
						text: cidade
					});

					$(select_cidade).append(option);
				});

				if(cidade != undefined){
					var indexCidade = $("#cidade option").removeAttr('selected').filter('[value="'+cidade+'"]').index();
					$('#cidade option:eq('+indexCidade+')').prop('selected', true).trigger('change');
				}
				$("#cidade_label span.loading").remove();
			}
			$(select_cidade).trigger("update");
		});
	}

	function carregaProdutos(familia) {
		var select_produto = $("#produto");

		$.ajax({
			url: "callcenter_cadastra_midea.php",
			type: "get",
			data: { ajax_carrega_produtos: true, familia: familia },
			beforeSend: function() {
				$(select_produto).find("option:first").nextAll().remove();
				$("#produto_label").append("<span class='loading' >carregando...</span>")
			}
		}).done(function(data) {
			data = JSON.parse(data);

			if (data.erro) {
				alert(data.erro);
			} else {
				data.produtos.forEach(function(produto) {
					var option = $("<option></option>", {
						value: produto.id,
						text: produto.descricao
					});

					$(select_produto).append(option);
				});

				$("#produto_label span.loading").remove();
			}

			$(select_produto).trigger("update");
		});
	}

	function buscar(){
		$("div.form-group").find("input, textarea, select").val("");
		$("#form_fale_conosco").hide();
		$("#pesquisar_atendimento").show();
		$("#table_result").remove();
		$("#anexos_callcenter").remove();
	}

	function voltar(){
		$("div.form-group").find("input, textarea, select").val("");
		$("#form_fale_conosco").show();
		$("#pesquisar_atendimento").hide();

		$("div.form-group").removeClass("has-error");
		$(".alert-danger").hide();
		$(".alert-info").hide();
	}

	function excluir_anexo(file,posicao,callcenter){
		$("#deletando"+posicao).show();
		$("#deleteAnexo"+posicao).hide();
		$.ajax({
			url: "callcenter_cadastra_midea.php",
			type: "POST",
			data: { file: file, deleta_imagens: true, callcenter: callcenter},
			complete: function (data) {

				data = $.parseJSON(data.responseText);
				if (data.erro != undefined) {
                    alert(data.erro);
                } else {
                	$("input[name=img_anexada_nome"+posicao+"][value='"+file+"']").parents("div.img_anexada").remove();
                	if(data.qtde_img < 3){
                		$("#form_anexo_pesquisa").show();
                	}
                }
                $("#deletando"+posicao).hide();
            }
		});
	}
	</script>

</head>
<body>

<div class="container" >

	<?php if($page != "carrier"){
			if(isset($_POST['ajax_pesquisar'])){
				$display_pesquisa = "";
				$display_fale_conosco = "style='display:none;'";
			}else{
				$display_pesquisa = "style='display:none;'";
				$display_fale_conosco = "";
			}
		?>

		<div id="pesquisar_atendimento" <?=$display_pesquisa?> >
			<legend>
				<h1>Pesquisar.</h1>
					Pesquise um atendimento já aberto.
			</legend>
			<form id="form_pesquisa_conosco" action='callcenter_cadastra_midea.php' enctype="multipart/form-data" method="post">
				<input type="hidden" name="ajax_pesquisar" value='true'>
				<input type="hidden" name="page" value="<?=$page?>">

				<?php
					if (count($msg_erro["msg"]) > 0) {
					?>
					    <div class="alert alert-danger">
							Desculpe! <?=implode("<br />", $msg_erro["msg"])?>
					    </div>
					<?php
					}

					if (isset($_POST['ajax_pesquisar']) AND pg_num_rows($res_pesquisa) == 0 AND !count($msg_erro["msg"])){
				?>
						<div class="alert alert-info">
							Desculpe! Nenhum resultado encontrado.
					    </div>
				<?php
					}
				?>
				<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6 <?=(in_array("hd_chamado", $msg_erro["campos"])) ? "has-error" : ""?>" >
					<label for="hd_chamado" >Protocolo<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control " id="hd_chamado" name="hd_chamado" />
				</div>

				<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6 <?=(in_array("cpf_pesquisa", $msg_erro["campos"])) ? "has-error" : ""?> ">
					<label for="nome" >CPF<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="cpf_pesquisa" name="cpf_pesquisa" />
				</div>

					<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
						<button type="button" onclick="voltar();" id="btn_voltar" class="btn btn-lg pull-left" >voltar</button>
					</div>
					<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
						<button type="submit" id="pesquisar" class="btn btn-lg pull-right">Pesquisar</button>
					</div>
			</form>

			<?php if(pg_num_rows($res_pesquisa) > 0){
					$xcategoria = pg_fetch_result($res_pesquisa, 0, 'categoria');
					switch ($xcategoria) {
						case 'indicacao_at':
							$categoria = "Indicação de Assistência Técnica";
							break;
						case 'informacao':
							$categoria = "Informação";
							break;
						case 'onde_comprar':
							$categoria = "Onde Comprar";
							break;
						case 'reclamacao_at':
							$categoria = "Reclamação de Assistência Técnica";
							break;
						case 'reclamacao_empresa':
							$categoria = "Reclamação de Empresa";
							break;
						case 'sugestao':
							$categoria = "Sugestão";
							break;
						case 'duvida_produto':
							$categoria = "Dúvida sobre o Produto";
							break;
						case 'reclamacao_produto':
							$categoria = "Reclamação de produto";
							break;
					}
				?>

				<table id="table_result" class="table table-bordered">
					<thead>
						<tr><th>Protocolo</th>			<td id='result_protocolo'><?=pg_fetch_result($res_pesquisa, 0, 'protocolo') ?></td></tr>
						<tr><th>Nome</th>				<td id='result_nome'><?=pg_fetch_result($res_pesquisa, 0, 'nome_consumidor') ?></td></tr>
						<tr><th>CPF</th>				<td id='result_cpf'><?=pg_fetch_result($res_pesquisa, 0, 'cpf_consumidor') ?></td></tr>
						<tr><th>Email</th>				<td id='result_email'><?=pg_fetch_result($res_pesquisa, 0, 'email_consumidor') ?></td></tr>
						<tr><th>Fone</th>				<td id='result_fone'><?=pg_fetch_result($res_pesquisa, 0, 'fone_consumidor') ?></td></tr>
						<tr><th>Celular</th>			<td id='result_celular'><?=pg_fetch_result($res_pesquisa, 0, 'celular_consumidor') ?></td></tr>
						<tr><th>Cep</th>				<td id='result_cep'><?=pg_fetch_result($res_pesquisa, 0, 'cep_consumidor') ?></td></tr>
						<tr><th>Bairro</th>				<td id='result_bairro'><?=pg_fetch_result($res_pesquisa, 0, 'bairro_consumidor') ?></td></tr>
						<tr><th>Endereço</th>			<td id='result_endereco'><?=pg_fetch_result($res_pesquisa, 0, 'endereco_consumidor').' N.'.pg_fetch_result($res_pesquisa, 0, 'numero_consumidor').' - '.pg_fetch_result($res_pesquisa, 0, 'complemento_consumidor') ?></td></tr>
						<tr><th>Cidade</th>				<td id='result_cidade'><?=pg_fetch_result($res_pesquisa, 0, 'cidade_consumidor') ?></td></tr>
						<tr><th>Estado</th>				<td id='result_estado'><?=pg_fetch_result($res_pesquisa, 0, 'estado_consumidor') ?></td></tr>
						<tr><th>Nota Fiscal</th>		<td id='result_nf'><?=pg_fetch_result($res_pesquisa, 0, 'nota_fiscal') ?></td></tr>
						<tr><th>Data Nota Fiscal</th>	<td id='result_data_nf'><?=pg_fetch_result($res_pesquisa, 0, 'data_nf') ?></td></tr>
						<tr><th>Categoria</th>			<td id='result_categoria'><?=$categoria?></td></tr>
						<tr><th>Ref. Produto</th>		<td id='result_ref_produto'><?=pg_fetch_result($res_pesquisa, 0, 'referencia_produto') ?></td></tr>
						<tr><th>Desc. Produto</th>		<td id='result_desc_produto'><?=pg_fetch_result($res_pesquisa, 0, 'descricao_produto') ?></td></tr>
						<tr><th>Número OS</th>			<td id='result_numero_os'><?=pg_fetch_result($res_pesquisa, 0, 'numero_os') ?></td></tr>
						<tr><th>Status OS</th>			<td id='result_status_os'><?=pg_fetch_result($res_pesquisa, 0, 'status_os') ?></td></tr>
						<tr><th>Comentario</th>			<td id='result_comentario'><?=pg_fetch_result($res_pesquisa, 0, 'info_complementares') ?></td></tr>
					</thead>
				</table>
				<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" id='anexos_callcenter' >
					<div class='row'>

					<?php
						$callcenter = pg_fetch_result($res_pesquisa, 0, 'protocolo');
						$s3 = new AmazonTC("callcenter", $login_fabrica);
						$s3->getObjectList("{$callcenter}-", false);
						$qtde_img = count($s3->files);
						if (count($s3->files) > 0) {
							$file_links = $s3->getLinkList($s3->files);

							$x['link'] = $file_links;
							$y['img'] = $s3->files;

							foreach ($s3->files as $key => $file) {
								$img_i = preg_replace("/.*.\//", "", $file);
								$img_i = preg_replace("/\..*./", "", $img_i);
								$img_i = explode("-", $img_i);
								$img_i = $img_i[1];

								$file_name = preg_replace("/.*.\//", "", $file);

								$type  = trim(strtolower(preg_replace("/.+\./", "", $file_name)));

								if ($type != "pdf" and $type != "zip" and $type != 'wav') {
									$file_thumb = $s3->getLink("thumb_".$file_name);

									if (!strlen($file_thumb)) {
										$file_thumb = $file_name;
									}
								} elseif($type == "pdf") {
									$file_thumb = "imagens/icone_pdf.jpg";
								}elseif($type == "zip"){
									$file_thumb = "imagens/icone_zip.jpg";
								}elseif($type == "wav"){
									$file_thumb = "imagens/icone_wav.png";
								}


								?>
								<div class="form-group col-xs-3 col-sm-3 col-md-3 col-lg-3 img_anexada img_border" rel="<?=$img_i?>">
									<a href="<?=$file_links[$key]?>" target="_blank" >
										<img style='margin-top: 5px;' src="<?=$file_thumb?>" />
									</a>
									<br />
									<img id="deletando<?=$img_i?>" class="loadImg"  src="../../imagens/loading_indicator_big.gif" style="display: none; width: 25px; margin-top: 10px;" />
									<img src='../img/delete.png' style='height: 15px; margin-bottom: 8px; margin-top: 8px;  cursor: pointer;' title='Excluir Anexo' onclick="excluir_anexo('<?=$file_name?>','<?=$img_i?>', '<?=$callcenter?>');" id='deleteAnexo<?=$img_i?>' />
									<input type="hidden" name="img_anexada_nome<?=$img_i?>" value="<?=$file_name?>" />
								</div>
							<?php
							}
						}
						if(count($s3->files) < 3){
							$display_anexo = "";
						}else{
							$display_anexo = "style=display:none;";
						}
						?>
						<form id="form_anexo_pesquisa" action='callcenter_cadastra_midea.php' <?=$display_anexo?> enctype="multipart/form-data" method="post">
							<input type="hidden" name="anexo_pesquisa" value='true'>
							<input type="hidden" name="page" value="<?=$page?>">
							<input type="hidden" name="callcenter" value="<?=$callcenter?>">
							<input type="hidden" name="qtde_img" value="<?=$qtde_img?>">
							<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
								<p>
									<div style='margin-top: 17px; width: 100%;'>
										<label style='width: 100%;'>Anexar Arquivo? <img src="../img/plus.gif" style="margin-bottom: -5px; cursor: pointer;" onclick="addAnexos('pesquisa')" /> <em style='color: #999; font-size: 14px;'>clique para adicionar</em> </label> <br /> <br />
										<div id="desc_pesquisa1" style="margin-bottom: 15px; width: 100%; display: none;"></div>
										<div id="desc_pesquisa2" style="margin-bottom: 15px; width: 100%; display: none;"></div>
										<div id="desc_pesquisa3" style="margin-bottom: 15px; width: 100%; display: none;"></div>
									</div>
								</p>

								<input type="file" name="anexo_pesquisa_1" style="display: none;" onchange="addImg('1','pesquisar')" />
								<input type="file" name="anexo_pesquisa_2" style="display: none;" onchange="addImg('2', 'pesquisar')" />
								<input type="file" name="anexo_pesquisa_3" style="display: none;" onchange="addImg('3', 'pesquisar')" />

								<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
									<button type="submit" id="anexo_pesquisa" class="btn btn-lg pull-right">Anexar</button>
								</div>
							</div>
						</form>

					</div>
				</div>

				<!-- <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
					<p>
						<div style='margin-top: 17px; width: 100%;'>
							<label style='width: 100%;'>Anexar Arquivo? <img src="../img/plus.gif" style="margin-bottom: -5px; cursor: pointer;" onclick="addAnexos('pesquisa')" /> <em style='color: #999; font-size: 14px;'>clique para adicionar</em> </label> <br /> <br />
							<div id="desc_pesquisa1" style="margin-bottom: 15px; width: 100%; display: none;"></div>
							<div id="desc_pesquisa2" style="margin-bottom: 15px; width: 100%; display: none;"></div>
							<div id="desc_pesquisa3" style="margin-bottom: 15px; width: 100%; display: none;"></div>
						</div>
					</p>

					<input type="file" name="anexo_pesquisa_1" style="display: none;" onchange="addImg('1','pesquisar')" />
					<input type="file" name="anexo_pesquisa_2" style="display: none;" onchange="addImg('2', 'pesquisar')" />
					<input type="file" name="anexo_pesquisa_3" style="display: none;" onchange="addImg('3', 'pesquisar')" />
				</div> -->
			<?php } ?>
		</div>
		<form id="form_fale_conosco" action='callcenter_cadastra_midea.php' enctype="multipart/form-data" method="post" <?=$display_fale_conosco?> >
			<input type="hidden" name="ajax_enviar" value='true'>
			<input type="hidden" name="page" value="<?=$page?>">

			<div id="mensagem_erro" style="display:none"></div>
			<legend>
				<button class='btn pull-right' id='btn_pesquisa' onclick="buscar();" type='button'>buscar</button>
				<?=$legend?>
			</legend>

			<div id="msg_erro" class="alert alert-danger" ></div>

			<div id="msg_sucesso" class="alert alert-success" ></div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="nome" >Nome<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="nome" name="nome" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="nome" >CPF<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="cpf" name="cpf" />
			</div>


			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="email" >E-mail<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="email" name="email" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="telefone" >Telefone<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="celular" >Celular<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="celular" class="celular" name="celular" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="cep" >CEP</label>
				<input type="text" class="form-control" id="cep" name="cep" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="estado" >Estado<span class="campo_obrigatorio">*</span></label>
				<select class="form-control" id="estado" name="estado" >
					<option value="" >Selecione</option>
					<?php
					foreach ($array_estado as $sigla => $nome) {
						echo "<option value='{$sigla}' >{$nome}</option>";
					}
					?>
				</select>
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label id="cidade_label" for="cidade" >Cidade<span class="campo_obrigatorio">*</span></label>
				<select class="form-control" id="cidade" name="cidade" >
					<option value="" >Selecione</option>
				</select>
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="bairro" >Bairro</label>
				<input type="text" class="form-control" id="bairro" name="bairro" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="endereco" >Endereço<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="endereco" name="endereco" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="numero" >Número</label>
				<input type="text" class="form-control col-lg-2" id="numero" name="numero" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="complemento" >Complemento</label>
				<input type="text" class="form-control" id="complemento" name="complemento" />
			</div>

			<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
				<label for="motivo_contato" >Motivo Contato<span class="campo_obrigatorio">*</span></label>
				<select class="form-control" id="motivo_contato" name="motivo_contato" >
					<option value=''>Selecione</option>
					<?php
						$sqlNatureza = "SELECT nome, descricao
										FROM tbl_natureza
										WHERE fabrica = {$login_fabrica}
										AND ativo IS TRUE
										ORDER BY descricao ASC";
						$resNatureza = pg_query($con, $sqlNatureza);

						while ($natureza = pg_fetch_object($resNatureza)) {
							echo "<option value='{$natureza->nome}' >{$natureza->descricao}</option>";
						}
					?>
				</select>
			</div>

			<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
				<label for="mensagem" >Mensagem<span class="campo_obrigatorio">*</span></label>
				<textarea class="form-control" name="mensagem" rows="6" ></textarea>
			</div>

			<!-- teste -->
			<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
				<p>
					<div style='margin-top: 17px; width: 100%;'>
						<label style='width: 100%;'>Anexar Arquivo? <img src="../img/plus.gif" style="margin-bottom: -5px; cursor: pointer;" onclick="addAnexos('faleconosco')" /> <em style='color: #999; font-size: 14px;'>clique para adicionar</em> </label> <br /> <br />
						<div id="desc1" style="margin-bottom: 15px; width: 100%; display: none;"></div>
						<div id="desc2" style="margin-bottom: 15px; width: 100%; display: none;"></div>
						<div id="desc3" style="margin-bottom: 15px; width: 100%; display: none;"></div>
					</div>
				</p>

				<input type="file" name="anexo_arquivo_1" style="display: none;" onchange="addImg('1','faleconosco')" />
				<input type="file" name="anexo_arquivo_2" style="display: none;" onchange="addImg('2','faleconosco')" />
				<input type="file" name="anexo_arquivo_3" style="display: none;" onchange="addImg('3','faleconosco')" />
			</div>
			<!-- fim teste -->

			<div class="col-xs-6 col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-4 col-lg-offset-8" ></div>
			<div class="col-xs-6 col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-4 col-lg-offset-8" >
				<button type="button" id="enviar" class="btn btn-lg pull-right" data-loading-text="ENVIANDO..." >Enviar</button>
			</div>
		</form>
	<?php }else{
			if(isset($_POST['ajax_pesquisar'])){
				$display_pesquisa = "";
				$display_fale_conosco = "style='display:none;'";
			}else{
				$display_pesquisa = "style='display:none;'";
				$display_fale_conosco = "";
			}
	?>
		<div id="pesquisar_atendimento" <?=$display_pesquisa?> >
			<legend>
				<h1>Pesquisar.</h1>
					Pesquise um atendimento já aberto.
			</legend>
			<form id="form_pesquisa_conosco" action='callcenter_cadastra_midea.php' enctype="multipart/form-data" method="post">
				<input type="hidden" name="ajax_pesquisar" value='true'>
				<input type="hidden" name="page" value="<?=$page?>">

				<?php
					if (count($msg_erro["msg"]) > 0) {
					?>
					    <div class="alert alert-danger">
							Desculpe! <?=implode("<br />", $msg_erro["msg"])?>
					    </div>
					<?php
					}

					if (isset($_POST['ajax_pesquisar']) AND pg_num_rows($res_pesquisa) == 0 AND !count($msg_erro["msg"])){
				?>
						<div class="alert alert-info">
							Desculpe! Nenhum resultado encontrado.
					    </div>
				<?php
					}
				?>
				<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6 <?=(in_array("hd_chamado", $msg_erro["campos"])) ? "has-error" : ""?>" >
					<label for="hd_chamado" >Protocolo<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control " id="hd_chamado" name="hd_chamado" />
				</div>

				<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6 <?=(in_array("cpf_pesquisa", $msg_erro["campos"])) ? "has-error" : ""?> ">
					<label for="nome" >CPF<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="cpf_pesquisa" name="cpf_pesquisa" />
				</div>

					<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
						<button type="button" onclick="voltar();" id="btn_voltar" class="btn btn-lg pull-left" >voltar</button>
					</div>
					<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
						<button type="submit" id="pesquisar" class="btn btn-lg pull-right">Pesquisar</button>
					</div>
			</form>

			<?php if(pg_num_rows($res_pesquisa) > 0){
					$xcategoria = pg_fetch_result($res_pesquisa, 0, 'categoria');
					switch ($xcategoria) {
						case 'indicacao_at':
							$categoria = "Indicação de Assistência Técnica";
							break;
						case 'informacao':
							$categoria = "Informação";
							break;
						case 'onde_comprar':
							$categoria = "Onde Comprar";
							break;
						case 'reclamacao_at':
							$categoria = "Reclamação de Assistência Técnica";
							break;
						case 'reclamacao_empresa':
							$categoria = "Reclamação de Empresa";
							break;
						case 'sugestao':
							$categoria = "Sugestão";
							break;
						case 'duvida_produto':
							$categoria = "Dúvida sobre o Produto";
							break;
						case 'reclamacao_produto':
							$categoria = "Reclamação de produto";
							break;
					}
				?>

				<table id="table_result" class="table table-bordered">
					<thead>
						<tr><th>Protocolo</th>			<td id='result_protocolo'><?=pg_fetch_result($res_pesquisa, 0, 'protocolo') ?></td></tr>
						<tr><th>Nome</th>				<td id='result_nome'><?=pg_fetch_result($res_pesquisa, 0, 'nome_consumidor') ?></td></tr>
						<tr><th>CPF</th>				<td id='result_cpf'><?=pg_fetch_result($res_pesquisa, 0, 'cpf_consumidor') ?></td></tr>
						<tr><th>Email</th>				<td id='result_email'><?=pg_fetch_result($res_pesquisa, 0, 'email_consumidor') ?></td></tr>
						<tr><th>Fone</th>				<td id='result_fone'><?=pg_fetch_result($res_pesquisa, 0, 'fone_consumidor') ?></td></tr>
						<tr><th>Celular</th>			<td id='result_celular'><?=pg_fetch_result($res_pesquisa, 0, 'celular_consumidor') ?></td></tr>
						<tr><th>Cep</th>				<td id='result_cep'><?=pg_fetch_result($res_pesquisa, 0, 'cep_consumidor') ?></td></tr>
						<tr><th>Bairro</th>				<td id='result_bairro'><?=pg_fetch_result($res_pesquisa, 0, 'bairro_consumidor') ?></td></tr>
						<tr><th>Endereço</th>			<td id='result_endereco'><?=pg_fetch_result($res_pesquisa, 0, 'endereco_consumidor').' N.'.pg_fetch_result($res_pesquisa, 0, 'numero_consumidor').' - '.pg_fetch_result($res_pesquisa, 0, 'complemento_consumidor') ?></td></tr>
						<tr><th>Cidade</th>				<td id='result_cidade'><?=pg_fetch_result($res_pesquisa, 0, 'cidade_consumidor') ?></td></tr>
						<tr><th>Estado</th>				<td id='result_estado'><?=pg_fetch_result($res_pesquisa, 0, 'estado_consumidor') ?></td></tr>
						<tr><th>Nota Fiscal</th>		<td id='result_nf'><?=pg_fetch_result($res_pesquisa, 0, 'nota_fiscal') ?></td></tr>
						<tr><th>Data Nota Fiscal</th>	<td id='result_data_nf'><?=pg_fetch_result($res_pesquisa, 0, 'data_nf') ?></td></tr>
						<tr><th>Categoria</th>			<td id='result_categoria'><?=$categoria?></td></tr>
						<tr><th>Ref. Produto</th>		<td id='result_ref_produto'><?=pg_fetch_result($res_pesquisa, 0, 'referencia_produto') ?></td></tr>
						<tr><th>Desc. Produto</th>		<td id='result_desc_produto'><?=pg_fetch_result($res_pesquisa, 0, 'descricao_produto') ?></td></tr>
						<tr><th>Número OS</th>			<td id='result_numero_os'><?=pg_fetch_result($res_pesquisa, 0, 'numero_os') ?></td></tr>
						<tr><th>Status OS</th>			<td id='result_status_os'><?=pg_fetch_result($res_pesquisa, 0, 'status_os') ?></td></tr>
						<tr><th>Comentario</th>			<td id='result_comentario'><?=pg_fetch_result($res_pesquisa, 0, 'info_complementares') ?></td></tr>
					</thead>
				</table>
				<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" id='anexos_callcenter' >
					<div class='row'>

					<?php
						$callcenter = pg_fetch_result($res_pesquisa, 0, 'protocolo');
						$s3 = new AmazonTC("callcenter", $login_fabrica);
						$s3->getObjectList("{$callcenter}-", false);
						$qtde_img = count($s3->files);
						if (count($s3->files) > 0) {
							$file_links = $s3->getLinkList($s3->files);

							$x['link'] = $file_links;
							$y['img'] = $s3->files;

							foreach ($s3->files as $key => $file) {
								$img_i = preg_replace("/.*.\//", "", $file);
								$img_i = preg_replace("/\..*./", "", $img_i);
								$img_i = explode("-", $img_i);
								$img_i = $img_i[1];

								$file_name = preg_replace("/.*.\//", "", $file);

								$type  = trim(strtolower(preg_replace("/.+\./", "", $file_name)));

								if ($type != "pdf" and $type != "zip" and $type != 'wav') {
									$file_thumb = $s3->getLink("thumb_".$file_name);

									if (!strlen($file_thumb)) {
										$file_thumb = $file_name;
									}
								} elseif($type == "pdf") {
									$file_thumb = "imagens/icone_pdf.jpg";
								}elseif($type == "zip"){
									$file_thumb = "imagens/icone_zip.jpg";
								}elseif($type == "wav"){
									$file_thumb = "imagens/icone_wav.png";
								}


								?>
								<div class="form-group col-xs-3 col-sm-3 col-md-3 col-lg-3 img_anexada img_border" rel="<?=$img_i?>">
									<a href="<?=$file_links[$key]?>" target="_blank" >
										<img style='margin-top: 5px;' src="<?=$file_thumb?>" />
									</a>
									<br />
									<img id="deletando<?=$img_i?>" class="loadImg"  src="../../imagens/loading_indicator_big.gif" style="display: none; width: 25px; margin-top: 10px;" />
									<img src='../img/delete.png' style='height: 15px; margin-bottom: 8px; margin-top: 8px;  cursor: pointer;' title='Excluir Anexo' onclick="excluir_anexo('<?=$file_name?>','<?=$img_i?>', '<?=$callcenter?>');" id='deleteAnexo<?=$img_i?>' />
									<input type="hidden" name="img_anexada_nome<?=$img_i?>" value="<?=$file_name?>" />
								</div>
							<?php
							}
						}
						if(count($s3->files) < 3){
							$display_anexo = "";
						}else{
							$display_anexo = "style=display:none;";
						}
						?>
						<form id="form_anexo_pesquisa" action='callcenter_cadastra_midea.php' <?=$display_anexo?> enctype="multipart/form-data" method="post">
							<input type="hidden" name="anexo_pesquisa" value='true'>
							<input type="hidden" name="page" value="<?=$page?>">
							<input type="hidden" name="callcenter" value="<?=$callcenter?>">
							<input type="hidden" name="qtde_img" value="<?=$qtde_img?>">
							<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
								<p>
									<div style='margin-top: 17px; width: 100%;'>
										<label style='width: 100%;'>Anexar Arquivo? <img src="../img/plus.gif" style="margin-bottom: -5px; cursor: pointer;" onclick="addAnexos('pesquisa')" /> <em style='color: #999; font-size: 14px;'>clique para adicionar</em> </label> <br /> <br />
										<div id="desc_pesquisa1" style="margin-bottom: 15px; width: 100%; display: none;"></div>
										<div id="desc_pesquisa2" style="margin-bottom: 15px; width: 100%; display: none;"></div>
										<div id="desc_pesquisa3" style="margin-bottom: 15px; width: 100%; display: none;"></div>
									</div>
								</p>

								<input type="file" name="anexo_pesquisa_1" style="display: none;" onchange="addImg('1','pesquisar')" />
								<input type="file" name="anexo_pesquisa_2" style="display: none;" onchange="addImg('2', 'pesquisar')" />
								<input type="file" name="anexo_pesquisa_3" style="display: none;" onchange="addImg('3', 'pesquisar')" />

								<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
									<button type="submit" id="anexo_pesquisa" class="btn btn-lg pull-right">Anexar</button>
								</div>
							</div>
						</form>

					</div>
				</div>
			<?php } ?>
		</div>
		<form id="form_fale_conosco" action='callcenter_cadastra_midea.php' enctype="multipart/form-data" method="post" <?=$display_fale_conosco?> >
			<input type="hidden" name="ajax_enviar" value='true'>
			<input type="hidden" name="page" value="<?=$page?>">

			<div id="mensagem_erro" style="display:none"></div>
			<div class='row'>
				<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
					<button class='btn pull-right' id='btn_pesquisa' onclick="buscar();" type='button'>BUSCAR</button>
				</div>
			</div>
			<div id="msg_erro" class="alert alert-danger" ></div>

			<div id="msg_sucesso" class="alert alert-success" ></div>

			<div class='row'>
				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="nome" >Nome<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="nome" name="nome" />
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="email" >E-mail<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="email" name="email" />
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="telefone" >Telefone</label>
					<input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
				</div>
			</div>

			<div class='row'>
				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="celular" >Celular<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="celular" class="celular" name="celular" />
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="nome" >CPF<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="cpf" name="cpf" />
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="cep" >CEP</label>
					<input type="text" class="form-control" id="cep" name="cep" />
				</div>
			</div>

			<div class='row'>
				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="estado" >Estado</label>
					<select class="form-control" id="estado" name="estado" >
						<option value="" >Selecione</option>
						<?php
						foreach ($array_estado as $sigla => $nome) {
							echo "<option value='{$sigla}' >{$nome}</option>";
						}
						?>
					</select>
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label id="cidade_label" for="cidade" >Cidade</label>
					<select class="form-control" id="cidade" name="cidade" >
						<option value="" >Selecione</option>
					</select>
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="bairro" >Bairro</label>
					<input type="text" class="form-control" id="bairro" name="bairro" />
				</div>
			</div>

			<div class='row'>
				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="endereco" >Endereço</label>
					<input type="text" class="form-control" id="endereco" name="endereco" />
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="numero" >Número</label>
					<input type="text" class="form-control" id="numero" name="numero" />
				</div>

				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="complemento" >Complemento</label>
					<input type="text" class="form-control" id="complemento" name="complemento" />
				</div>
			</div>

			<div class='row'>
				<div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
					<label for="motivo_contato" >Motivo Contato<span class="campo_obrigatorio">*</span></label>
					<select class="form-control" id="motivo_contato" name="motivo_contato" >
						<option value=''>Selecione</option>
						<?php
							$sqlNatureza = "SELECT nome, descricao
											FROM tbl_natureza
											WHERE fabrica = {$login_fabrica}
											AND ativo IS TRUE
											ORDER BY descricao ASC";
							$resNatureza = pg_query($con, $sqlNatureza);

							while ($natureza = pg_fetch_object($resNatureza)) {
								echo "<option value='{$natureza->nome}' >{$natureza->descricao}</option>";
							}
						?>
					</select>
				</div>
			</div>
			<div class='row'>

				<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
					<label for="mensagem" >Mensagem<span class="campo_obrigatorio">*</span></label>
					<textarea class="form-control" name="mensagem" rows="3" ></textarea>
				</div>
			</div>
			<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
				<p>
					<div style='margin-top: 17px; width: 100%;'>
						<label style='width: 100%;'>Anexar Arquivo? <img src="../img/plus.gif" style="margin-bottom: -5px; cursor: pointer;" onclick="addAnexos('faleconosco')" /> <em style='color: #999; font-size: 14px;'>clique para adicionar</em> </label> <br /> <br />
						<div id="desc1" style="margin-bottom: 15px; width: 100%; display: none;"></div>
						<div id="desc2" style="margin-bottom: 15px; width: 100%; display: none;"></div>
						<div id="desc3" style="margin-bottom: 15px; width: 100%; display: none;"></div>
					</div>
				</p>

				<input type="file" name="anexo_arquivo_1" style="display: none;" onchange="addImg('1','faleconosco')" />
				<input type="file" name="anexo_arquivo_2" style="display: none;" onchange="addImg('2','faleconosco')" />
				<input type="file" name="anexo_arquivo_3" style="display: none;" onchange="addImg('3','faleconosco')" />
			</div>
			<div class='row'>
				<div class="col-xs-6 col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-4 col-lg-offset-8" ></div>
				<div class="col-xs-6 col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-4 col-lg-offset-8" >
					<button type="button" id="enviar" class="btn btn-lg pull-right" data-loading-text="ENVIANDO..." >Enviar</button>
				</div>
			</div>
		</form>
	<?php } ?>
</div>

<br /><br />

</body>
</html>
<!-- fica no meu iframe {
	window.onmessage = function(event) {
			    event.source.postMessage($(document).height()+100, event.origin);
			};
}

passar{
	window.parent.postMessage($(document).height()+100, "*");
}

colocar no site deles {
	window.onmessage = function(event) {
        $("#idIframe").css({ height: event.data+"px" });
    };

} -->
