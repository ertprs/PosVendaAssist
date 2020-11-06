<?php

header("Location: autocredenciamento_new.php");
exit;

//externos/autocredenciamento.php
header("Content-Type:text/html; charset=utf-8");

$caminho_imagem = dirname(__FILE__) . '/../autocredenciamento_teste/';
$caminho_path	= dirname($_SERVER['PHP_SELF']) . '/../autocredenciamento_teste/';

include dirname(__FILE__) . '/../dbconfig.php';
include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../class_resize.php';
include dirname(__FILE__) . '/../mlg/mlg_funciones.php';
include dirname(__FILE__) . '/../trad_site/fn_ttext.php';

$not_in_fabricas = '108, 93, 47, 89, 63, 92, 8, 14, 66, 5, 43, 61, 77, 76, 110, 78, 107, 112, 113, 75, 111, 109, 10,119,46,133';
$not_in_marcas = '131, 189, 178, 177, 184,137,136,199';

$debug = ($_COOKIE['debug'][0] == 't');

/* 10/12/2009 MLG - Não funcionava a gravação de informação porque agora o $msg_erro é um array,
					portanto a conferência tem que ser feita com count($msg_erro) e não com strlen()
*/

/*  Tradução inicial, depois, no 'else' para mudar de formulário, tem outro array para ele  */

if(!empty($_GET['fabrica'])){
	if((int)$_GET['fabrica']){
		$sql = "SELECT lower(replace(nome,' ','')) from tbl_fabrica where fabrica = ".$_GET['fabrica'];
		$res = pg_query($con,$sql);
		$fabrica_nome = pg_fetch_result($res,0,0);
	}


}
$a_labels = array(
	"autocredenciamento"	=> array(
		"pt-br" => "Autocredenciamento",
		"es"	=> "Auto-Regristro",
		"en"	=> "Self-Register"
	),
	"digite_CNPJ"=> array(
		"pt-br" => "Por favor, digite o CNPJ da sua Autorizada.",
		"es"	=> "Por favor, escriba su Nº de Identificación Fiscal",
		"en"	=> "Please, type your Tax ID"
	),
	"Informe_CNPJ"=>array(
		"pt-br" => "CNPJ do Posto Autorizado:",
		"es"	=> "Escriba su ID fiscal:",
		"en"	=> "Enter your Tax ID:"
	),
	"erro_CNPJ" => array (
		"pt-br" => "CNPJ digitado inválido",
		"es"    => "La ID Fiscal no es válida",
		"en"    => "TaxID is invalid",
		"de"    => "TaxID ist ungültig",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"Consultar"	=> array(
		"pt-br" => "",
		"es"	=> "",
		"en"	=> "Search"
	),
	"gravar"	=> array(
		"pt-br" => "Gravar Formulário",
		"es"	=> "Enviar Formulario",
		"en"	=> "Submit Form"
	),
	"tc_agradece"=>array(
		"pt-br" => "A Telecontrol agradece o seu cadastro!",
		"es"	=> "¡Telecontrol agradece su alta!",
		"en"	=> "Telecontrol thanks you for signing up!"
	),
	"homepage"	=> array(
		"pt-br" => "Página inicial",
		"es"	=> "",
		"en"	=> "Home Page"
	),
	"termos_de _uso"	=> array(
		"pt-br" => "Li e concordo",
		"es"	=> "He leído y estoy de acuerdo",
		"en"	=> "I have read and agree"
	),
	"label_texto_informacao"=> array(
	"pt-br" => '<p class="texto_informativo_informacao_font_cabecalho"><b>O que é e para que serve o Autocredenciamento Telecontrol?</b></p><br>
	<p class="texto_informativo_informacao_font_conteudo">É um novo recurso que desenvolvemos para auxiliar nossos parceiros. Tem por finalidade, contribuir para que as indústrias ampliem sua Rede Autorizada e, por outro lado, possibilitar ao seu Posto Autorizado acesso a um canal rápido e eficaz para oferecer seus serviços.</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>1º Passo -</b> O Posto Autorizado faz o cadastro no site da <b>Telecontrol</b> detalhando as informações importantes para que as Industrias possam analisar seu perfil (linhas de atendimento, cidades que atende, fotos da sua empresa, preferência por marcas, recursos a disposição - carro, estacionamento, etc).</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>2º Passo -</b> A <b>Telecontrol</b> disponibilizará estas informações para as indústrias que utilizam nosso Sistema.</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>3º Passo -</b> A indústria interessada entrará em contato para iniciar o processo de credenciamento do Posto Autorizado.</p><br>
	<p class="texto_informativo_informacao_font_cabecalho"><strong style="font-size: 17px;"> Não perca tempo, cadastre-se já!</strong></p><br>',
	"es"	=> '<p class="texto_informativo_informacao_font_cabecalho"><b>¿Qué es y para qué sirve la Auto Acreditación Telecontrol?</b></p><br>
	<p class="texto_informativo_informacao_font_conteudo">Es una nueva herramienta que hemos desarrollado para ayudar a nuestros socios. Su propósito es contribuir a que nuestros clientes aumenten su Red Autorizada, y por otro lado permitir el acceso a los Servicios de Asistencia Técnica a un canal rápido y efectivo para ofrecer sus servicios a empresas de su preferencia.</p></center><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>Paso 1 -</b> El Servicio de Asistencia Técnica se da de alta en nuestra web, facilitando informaciones para que las empresas puedan analizar su perfil (líneas telefónicas, ciudades cercanas que atiende, fotos de su negocio, su preferencia por algunas marcas, recursos disponibles (Estacionamiento, etc.)</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>Paso 2 -</b><b> Telecontrol</b> proporcionará esta información a las empresas que utilizan nuestro sistema.</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>Paso 3 -</b> La empresa en cuestión se comunicará con usted para iniciar el proceso de acreditación.</p><br>
	<p class="texto_informativo_informacao_font_conteudo">Es muy simple. ¡Dese prisa y regístrese ahora!</p><br>',
	"en"	=> '<p class="texto_informativo_informacao_font_cabecalho"><b>What is and what is the Auto Accreditation Telecontrol?</b></p><br>
	<p class="texto_informativo_informacao_font_conteudo">It is a new tool that we have developed to help our partners. Its purpose is to help our clients increase their authorized network and on the other hand, allow Technical Support Services the access to a fast and effective channel to offer their services to companies of your choice.</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>Step 1 -</b> Technical Assistance Service signs up on our website, providing information for companies to assess their profile (telephone lines, attending nearby cities, photos of your business, your preference for some brands, available resources (Parking , etc.).</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>Step 2 -</b> <b>Telecontrol</b> will provide this information to companies that use our system.</p><br>
	<p class="texto_informativo_informacao_font_conteudo"><b>Step 3 -</b> A company that have an interest n your bussiness will contact you to start the accreditation process.</p><br>
	<p class="texto_informativo_informacao_font_conteudo">It is very simple. Hurry and sign up now!</p><br>'
	),
);



function pg_array_quote($arr, $valType = 'string') {
	if (!is_array($arr)) return 'NULL';

	if (count($arr) == 0) return '\'{}\'';
	$ret = '{';
	switch ($valType) {
		case 'str':
		case 'string':
		case 'text':
			foreach($arr as $item) {
				if		(is_bool($item)) $item = ($item) ? 'TRUE' : 'FALSE';
				elseif	(is_null($item) or strtoupper($item) == 'NULL') $item = 'NULL';
				elseif	(is_string($item) and strpos($item, ',') !== false) $item = "\"$item\"";
				$quoted[] = $item;
			}
			$ret .= implode(',',$quoted) . '}';
			return $ret;
		break;
		case 'numeric':
		case 'int':
		case 'integer':
		case 'float':
		case 'boolean':
		case 'bool':
			foreach($arr as $item) {
				if (is_string($item) and
					($item == 't' or $item == 'f') and
					$valType == 'bool') $item = ($item == 't');
				if	(is_bool($item)) $item = ($item) ? 'TRUE' : 'FALSE';
				$quoted[] = $item;
			}
			$ret .= implode(',',$quoted) . '}';
			return $ret;
		break;
	}
	return 'NULL';
}


$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "Search") $btn_acao = "Cadastrar"; //  Para a versão em inglês do formulário...

$outros_sistema = '';

$html_titulo = ttext ($a_labels, "autocredenciamento", $cook_idioma);

function checaCPF ($cpf,$return_str = true) {
	global $con;	// Para conectar com o banco...
	$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

	$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
	if ($res_cpf === false) {
		return ($return_str) ? pg_last_error($con) : false;
	}
	return $cpf;
}

	if(strtolower($btn_acao) == 'cadastrar'){
		$verifica_cnpj  = preg_replace("/\D/","",$cnpj);
		$verifica_email = trim($_POST['verifica_email']);

	if (is_numeric($verifica_cnpj)) {
		if (checaCPF($verifica_cnpj,false)===false) $msg_erro = ttext($a_labels, "erro_CNPJ");
	} else $msg_erro = $cnpj;

	if(strlen($msg_erro) == 0){


		$sql = "SELECT *
				FROM tbl_posto_alteracao
				where cnpj = '$verifica_cnpj' and auto_credenciamento is true";
				$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$posto                 = utf8_encode(pg_fetch_result($res,0,posto));
			$nome                  = utf8_encode(pg_fetch_result($res,0,razao_social));
			$nome_fantasia         = utf8_encode(pg_fetch_result($res,0,nome_fantasia));
			$cnpj                  = utf8_encode(pg_fetch_result($res,0,cnpj));
			$endereco              = utf8_encode(pg_fetch_result($res,0,endereco));
			$numero                = utf8_encode(pg_fetch_result($res,0,numero));
			$complemento           = utf8_encode(pg_fetch_result($res,0,complemento));
			$bairro                = utf8_encode(pg_fetch_result($res,0,bairro));
			$cidade                = utf8_encode(pg_fetch_result($res,0,cidade));
			$estado                = utf8_encode(pg_fetch_result($res,0,estado));
			$cep                   = utf8_encode(pg_fetch_result($res,0,cep));
			$email                 = utf8_encode(pg_fetch_result($res,0,email));
			$telefone              = utf8_encode(pg_fetch_result($res,0,fone));
			$fax                   = utf8_encode(pg_fetch_result($res,0,fax));
			$contato               = utf8_encode(pg_fetch_result($res,0,contato));
			$ie                    = utf8_encode(pg_fetch_result($res,0,ie));
			$linhas                = utf8_encode(pg_fetch_result($res,0,linhas));
			$funcionarios          = utf8_encode(pg_fetch_result($res,0,funcionario_qtde));
			$oss                   = utf8_encode(pg_fetch_result($res,0,os_qtde));
			$atende_cidade_proxima = utf8_encode(pg_fetch_result($res,0,atende_cidade_proxima));
			$marca_nao_autorizada  = utf8_encode(pg_fetch_result($res,0,marca_nao_autorizada));
			$marca_ser_autorizada  = utf8_encode(pg_fetch_result($res,0,marca_ser_autorizada));
			$melhor_sistema        = utf8_encode(pg_fetch_result($res,0,melhor_sistema));
			$fabrica_credenciadas  = utf8_encode(pg_fetch_result($res,0,fabrica_credenciada));
			$marcas_credenciadas   = utf8_encode(pg_fetch_result($res,0,marca_credenciada));
			$observacao			   = utf8_encode(pg_fetch_result($res,0,observacao));
			$fabricantes           = utf8_encode(pg_fetch_result($res,0,outras_fabricas));

			$informacao_sistema    = utf8_encode(pg_fetch_result($res,0,informacao_sistema));
			$informacao_marca      = utf8_encode(pg_fetch_result($res,0,informacao_marca));
			$informacao_vantagem   = utf8_encode(pg_fetch_result($res,0,informacao_vantagem));
			$informacao_comentario = utf8_encode(pg_fetch_result($res,0,informacao_comentario));

			$visita_tecnica = utf8_encode(pg_fetch_result($res,0,visita_tecnica));
			$atende_consumidor_balcao = utf8_encode(pg_fetch_result($res,0,atende_consumidor_balcao));
			$atende_revendas = utf8_encode(pg_fetch_result($res,0,atende_revendas));

			$aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento

			list($info_sistema_1,$info_sistema_2,$info_sistema_3)  = explode('|', $informacao_sistema);
			list($info_marca_1,$info_marca_2,$info_marca_3)  = explode('|', $informacao_marca);
			list($info_vantagem_1,$info_vantagem_2,$info_vantagem_3)  = explode('|', $informacao_vantagem);

			$fabrica_credenciadas_2 = $fabrica_credenciadas;
			$fabrica_credenciadas_2 = str_replace("{", "", $fabrica_credenciadas_2);
			$fabrica_credenciadas_2 = str_replace("}", "", $fabrica_credenciadas_2);

			$sql_fabrica_ja_credenciada = "SELECT fabrica FROM tbl_posto_fabrica WHERE fabrica not in ($fabrica_credenciadas_2) and posto = $posto and credenciamento = 'CREDENCIADO'";
			$res_fabrica_cre = pg_query($con, $sql_fabrica_ja_credenciada);

			$array_fab = explode(",", $fabrica_credenciadas_2);

			while($data_fab = pg_fetch_object($res_fabrica_cre)){

				array_push($array_fab, $data_fab->fabrica);

			}

			$fabrica_credenciadas =  implode(",", $array_fab);

		} else {
			$sql = "SELECT *
					FROM tbl_posto
					LEFT JOIN tbl_posto_extra using(posto)
					WHERE cnpj = '$verifica_cnpj'
					ORDER BY posto DESC LIMIT 1";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$posto                 = utf8_encode(pg_fetch_result($res,0,posto));
				$nome                  = utf8_encode(pg_fetch_result($res,0,nome));
				$nome_fantasia         = utf8_encode(pg_fetch_result($res,0,nome_fantasia));
				$cnpj                  = utf8_encode(pg_fetch_result($res,0,cnpj));
				$endereco              = utf8_encode(pg_fetch_result($res,0,endereco));
				$numero                = utf8_encode(pg_fetch_result($res,0,numero));
				$complemento           = utf8_encode(pg_fetch_result($res,0,complemento));
				$bairro                = utf8_encode(pg_fetch_result($res,0,bairro));
				$cidade                = utf8_encode(pg_fetch_result($res,0,cidade));
				$estado                = utf8_encode(pg_fetch_result($res,0,estado));
				$cep                   = utf8_encode(pg_fetch_result($res,0,cep));
				$email                 = utf8_encode(pg_fetch_result($res,0,email));
				$telefone              = utf8_encode(pg_fetch_result($res,0,fone));
				$fax                   = utf8_encode(pg_fetch_result($res,0,fax));
				$contato               = utf8_encode(pg_fetch_result($res,0,contato));
				$ie                    = utf8_encode(pg_fetch_result($res,0,ie));
				$pais                  = utf8_encode(pg_fetch_result($res,0,pais));
				$descricao             = utf8_encode(pg_fetch_result($res,0,descricao));

				$aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento
			}else{
				$aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento
			}
		}
	} else {
		$msg = '<label class="erro_campos_obrigatorios">' . $msg_erro . '</label>';
		$msg_erro = '';
	}
}

//  Funções para o Banco de Dados
function pg_begin() {
	global $con;
	$pg_res = pg_query($con,"BEGIN TRANSACTION");
	return (is_resource($pg_res)) ? $pg_res : pg_last_error($pg_res);
}
function pg_commit() {
	global $con;
	$pg_res = pg_query($con,"COMMIT TRANSACTION");
	return (is_resource($pg_res)) ? $pg_res : pg_last_error($pg_res);
}
function pg_rollback($loop = '') {
	global $con;
	$pg_res = pg_query($con,"ROLLBACK $loop TRANSACTION");
	return (is_resource($pg_res)) ? $pg_res : pg_last_error($pg_res);
}

$estados = array("AC" => "Acre",		"AL" => "Alagoas",	"AM" => "Amazonas",			"AP" => "Amapá",
				 "BA" => "Bahia",		"CE" => "Ceará",	"DF" => "Distrito Federal",	"ES" => "Espírito Santo",
				 "GO" => "Goiás",		"MA" => "Maranhão",	"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
				 "MT" => "Mato Grosso", "PA" => "Pará",		"PB" => "Paraíba",			"PE" => "Pernambuco",
				 "PI" => "Piauí",		"PR" => "Paraná",	"RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte",
				 "RO" => "Rondônia",	"RR" => "Roraima",	"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
				 "SE" => "Sergipe",		"SP" => "São Paulo","TO" => "Tocantins");

// pre_echo($_POST);
if($btn_acao == 'gravar') {
//  Cada erro vai num item do array. Depois, na hora de mostrar, faz um 'implode'

	$msg_erro = array();
	$posto	  = trim($_POST['posto']);

	if (!function_exists('anti_injection')) {
		function anti_injection($string) {
			$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
			return strtr(strip_tags(trim($string)), $a_limpa);
		}
	}

	if (!function_exists('is_email')) {
	function is_email($email=""){   // False se não bate...
		return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
	}
}
	//  Função para conferir cada campo do $_POST, devolve 'false' ou o que colocar como último argumento
	if (!function_exists('check_post_field')) {
		function check_post_field($fieldname, $returns = false) {
			if (!isset($_POST[$fieldname])) return $returns;
			$data = anti_injection($_POST[$fieldname]);
		// 	echo "<p><b>$fieldname</b>: $data</p>\n";
			return (strlen($data)==0) ? $returns : $data;
		}
	}
	//  Coloca aspas nos campo QUE PRECISAR. Usar só para campos BOOL ou string (char,varchar,text, etc.)
	//  Serve para, p.e., evitar que um valor 'null' seja NULL e não string 'null'
	if (!function_exists('pg_quote')) {
		function pg_quote($str, $type_numeric = false) {
		    if (is_bool($str)) return ($str) ? 'true' : 'false';
			if (is_null($str)) return 'null';
			if (is_numeric($str) and $type_numeric) return $str;
			if (in_array($str,array('null','true','false','t','f'))) return $str;
			return "'".pg_escape_string($str)."'";
		}
	}

	$aux_cod_posto	= utf8_decode(check_post_field('melhor_sistema'));
//  Campos não obrigatórios
	$aux_fax					= utf8_decode(check_post_field("fax",""));
	$fabricantes				= utf8_decode(check_post_field('fabricantes', ''));
	$aux_atende_cidade_proxima	= utf8_decode(check_post_field('atende_cidade_proxima',''));
	$aux_marca_nao_autorizada	= utf8_decode(check_post_field('marca_nao_autorizada', ''));
	$aux_marca_ser_autorizada	= utf8_decode(check_post_field('marca_ser_autorizada', ''));
	$aux_melhor_sistema			= utf8_decode(check_post_field('melhor_sistema', ''));
	$aux_opcao_outras_fabricas	= utf8_decode(check_post_field('fabricantes', ''));
	$aux_observacao				= utf8_decode(check_post_field('observacao', ''));
	$opcao_outras_fabricas		= utf8_decode(check_post_field('opcao_outras_fabricas', ''));
	$aux_inf_comentario			= utf8_decode(check_post_field('inf_comentario', ''));

// 	Campos obrigatórios... quando é para dar um INSERT!

	if (checaCPF(anti_injection($_POST['cnpj']),false)	=== false) $msg_erro[] = "Preencha/Verifique o campo CNPJ";
	if(($aux_nome			= check_post_field("nome")) === false) $msg_erro[] = "Preencha o campo Razão Social";
	if(($aux_cep			= check_post_field('cep')) === false) $msg_erro[] = "Preencha o campo CEP";
	if(($aux_endereco		= check_post_field('endereco')) === false) $msg_erro[] = "Preencha o campo Endereço";
	if(($aux_numero			= check_post_field('numero')) === false) $msg_erro[] = "Preencha o campo Número";
	if(($aux_bairro			= check_post_field('bairro')) === false) $msg_erro[] = "Preencha o campo Bairro";
	if(($aux_cidade			= check_post_field('cidade')) === false) $msg_erro[] = "Preencha o campo Cidade";
	if(($aux_estado			= check_post_field('estado')) === false) $msg_erro[] = "Preencha o campo Estado";
	if(($aux_complemento	= check_post_field("complemento"))	=== false) {}; //$msg_erro[] = "Preencha o campo Complemento"; }
	if(($aux_email			= check_post_field('email')) === false) $msg_erro[] = "Preencha o campo E-Mail";
	if(($aux_telefone		= check_post_field('telefone')) === false) $msg_erro[] = "Preencha o campo Telefone";
	if(($aux_contato		= check_post_field("contato")) === false) $msg_erro[] = "Preencha o campo Contato";
	if(($aux_nome_fantasia	= check_post_field("nome_fantasia")) === false) $msg_erro[] = "Preencha o campo Nome Fantasia";
	// if(($aux_ie				= check_post_field("ie")) === false) $msg_erro[] = "Preencha o campo I.E";



	//Validações
	if(!is_email($aux_email)) $msg_erro[] = "O e-mail digitado ($aux_email) não é válido.";

	$descricao = check_post_field('descricao');

	if(($aux_funcionarios	= check_post_field('funcionarios','null')) != 'null') {
		if (!is_numeric($aux_funcionarios)) {
			$msg_erro[] = "Apenas números no campo Qtde. de funcionários ($aux_funcionarios).";
		}
	}
	if(($aux_oss = check_post_field("oss","null")) != "null") {
		if(!is_numeric($aux_oss)){
			$msg_erro[] = "Apenas números no campo Qtde. de Ordem de Serviço mensal.";
		}
	}

	if (checaCPF(anti_injection($_POST['cnpj']),false)!==false) $aux_cnpj = preg_replace('/\D/','',$_REQUEST['cnpj']);

	$aux_nome = utf8_decode($aux_nome);
	$aux_cep = utf8_decode($aux_cep);
	$aux_endereco = utf8_decode($aux_endereco);
	$aux_numero = utf8_decode($aux_numero);
	$aux_bairro = utf8_decode($aux_bairro);
	$aux_cidade = utf8_decode($aux_cidade);
	$aux_estado = utf8_decode($aux_estado);
	$aux_complemento = utf8_decode($aux_complemento);
	$aux_email = utf8_decode($aux_email);
	$aux_telefone = utf8_decode($aux_telefone);
	$aux_contato = utf8_decode($aux_contato);
	$aux_nome_fantasia = utf8_decode($aux_nome_fantasia);
	$aux_ie = utf8_decode($aux_ie);
	$descricao = utf8_decode($descricao);


//  Atende linhas...
	$a_linhas[]= ($_POST['linha_1']) ? $linha_1 = utf8_decode($_POST['linha_1']) : "";
	$a_linhas[]= ($_POST['linha_2']) ? $linha_2 = utf8_decode($_POST['linha_2']) : "";
	$a_linhas[]= ($_POST['linha_3']) ? $linha_3 = utf8_decode($_POST['linha_3']) : "";
	$a_linhas[]= ($_POST['linha_4']) ? $linha_4 = utf8_decode($_POST['linha_4']) : "";
	$a_linhas[]= ($_POST['linha_5']) ? $linha_5 = utf8_decode($_POST['linha_5']) : "";
	$a_linhas[]= ($_POST['linha_6']) ? $linha_6 = utf8_decode($_POST['linha_6']) : "";
	$a_linhas[]= ($_POST['linha_7']) ? $linha_7 = utf8_decode($_POST['linha_7']) : "";
	$a_linhas[]= ($_POST['linha_6_obs']) ? $linha_6_obs = utf8_decode($_POST['linha_6_obs']) : "";
	if (count($a_linhas) == 0) $msg_erro[] = "Escolha ao menos uma LINHA de atuação.";
	$linhas = implode(",", array_filter($a_linhas));
	unset($a_linhas);

	$info_sistema_1 = $_POST['inf_sistema1'];
	$info_sistema_1 = str_replace("|","", $info_sistema_1);
	$info_sistema_2 = $_POST['inf_sistema2'];
	$info_sistema_2 = str_replace("|","", $info_sistema_2);
	$info_sistema_3 = $_POST['inf_sistema3'];
	$info_sistema_3 = str_replace("|","", $info_sistema_3);
	$info_sistema = utf8_decode($info_sistema_1."|".$info_sistema_2."|".$info_sistema_3);
	$info_sistema_verifica = $info_sistema_1.$info_sistema_2.$info_sistema_3;


	$info_marca_1 = $_POST['inf_marca1'];
	$info_marca_1 = str_replace("|","", $info_marca_1);
	$info_marca_2 = $_POST['inf_marca2'];
	$info_marca_2 = str_replace("|","", $info_marca_2);
	$info_marca_3 = $_POST['inf_marca3'];
	$info_marca_3 = str_replace("|","", $info_marca_3);
	$info_marca	  = utf8_decode($info_marca_1."|".$info_marca_2."|".$info_marca_3);
	$info_marca_verifica = $info_marca_1.$info_marca_2.$info_marca_3;

	$info_vantagem_1 = $_POST['inf_vantagem1'];
	$info_vantagem_1 = str_replace("|","", $info_vantagem_1);
	$info_vantagem_2 = $_POST['inf_vantagem2'];
	$info_vantagem_2 = str_replace("|","", $info_vantagem_2);
	$info_vantagem_3 = $_POST['inf_vantagem3'];
	$info_vantagem_3 = str_replace("|","", $info_vantagem_3);
	$info_vantagem	 = utf8_decode($info_vantagem_1."|".$info_vantagem_2."|".$info_vantagem_3);
	$info_vantagem_verifica = $info_vantagem_1.$info_vantagem_2.$info_vantagem_3;

	if (empty($_POST['outros_sistema']) or $_POST['outros_sistema'] == "N") {
		$info_sistema = '';
		$info_marca = '';
		$info_vantagem = '';
	}

	$sql = "SELECT posto
				FROM tbl_posto
				LEFT JOIN tbl_posto_extra using(posto)
				WHERE cnpj = '$aux_cnpj'
				ORDER BY posto DESC LIMIT 1";
	$res = pg_query($con,$sql);

	$posto = (is_resource($res) and @pg_numrows($res)==1) ? pg_fetch_result($res,0,posto) : '';

	$aux_cnpj          = preg_replace('/\D/','', $aux_cnpj);
	$aux_cep           = preg_replace('/\D/','', $aux_cep);
	$aux_telefone      = preg_replace('/(\d\d)(\d{4})(\d{4})/','($1) $2-$3', $aux_telefone);
	$aux_fax		   = preg_replace('/(\d\d)(\d{4})(\d{4})/','($1) $2-$3', $aux_fax);


//  Prepara os valores a serem inseridos ou atualizados:
	$aux_nome					= pg_quote($aux_nome);
	$aux_cnpj					= pg_quote($aux_cnpj);
	$aux_ie						= pg_quote($aux_ie);
	$aux_endereco				= pg_quote($aux_endereco);
	$aux_numero					= pg_quote($aux_numero);
	$aux_complemento			= pg_quote($aux_complemento);
	$aux_bairro					= pg_quote($aux_bairro);
	$aux_cidade					= pg_quote($aux_cidade);
	$aux_cep					= pg_quote($aux_cep);
	$aux_telefone				= pg_quote($aux_telefone);
	$aux_contato				= pg_quote($aux_contato);
	$aux_estado					= pg_quote($aux_estado);
	$aux_email					= pg_quote($aux_email);
	$aux_fax					= pg_quote($aux_fax);
	$aux_nome_fantasia			= pg_quote($aux_nome_fantasia);
	$aux_descricao				= pg_quote($descricao);
	$aux_opcao_outras_fabricas	= pg_quote($aux_opcao_outras_fabricas);
	$opcao_outras_fabricas		= pg_quote($opcao_outras_fabricas);
	$aux_inf_comentario			= pg_quote($aux_inf_comentario);

	if (!empty($_POST['total_fab'])) {
		$total_fab_post = (int) $_POST['total_fab'];
		$aux_fabrica = array();
		$aux_marca = array();

		for ($i = 0; $i <  $total_fab_post; $i++) {
			if (!empty($_POST['fabrica_' . $i])) {
				$tmp = explode(':', $_POST['fabrica_' . $i]);

				switch ($tmp[0]) {
					case 'f':
						$aux_fabrica[] = $tmp[1];
						break;
					case 'm':
						$aux_marca[] = $tmp[1];
						break;
				}
			}
		}

		if (empty($aux_fabrica) and empty($aux_marca)) {
			if ($opcao_outras_fabricas == "''") {
				$msg_erro[] = 'Selecione uma fabrica que gostaria de ser credenciado.';
			} else {
				$and_fabrica_credenciadas = "'{}'";
				$and_marcas_credenciadas = "'{}'";
			}
		} else {
			if (!empty($aux_fabrica)) {
				$and_fabrica_credenciadas = "'{" . implode(', ', $aux_fabrica) . "}'";
			} else {
				$and_fabrica_credenciadas = "'{}'";
			}

			if (!empty($aux_marca)) {
				$and_marcas_credenciadas = "'{" . implode(', ', $aux_marca) . "}'";
			} else {
				$and_marcas_credenciadas = "'{}'";
			}
		}

	}

	$verifica_posto = 0;

	$sql_posto = "SELECT posto FROM tbl_posto WHERE cnpj = $aux_cnpj";
	$res_posto = pg_query($con, $sql_posto);
	if (pg_num_rows($res_posto) == 0) {
		$posto = '0';
	} else {
		$posto = pg_fetch_result($res_posto, 0, 'posto');
	}

	$sql_cpnj = "SELECT posto FROM tbl_posto_alteracao WHERE cnpj = $aux_cnpj";
	$res_cnpj = pg_query($con, $sql_cpnj);
	if (pg_num_rows($res_cnpj) > 0) {
		$verifica_posto = 1;
	}

	$condicao_1 = $_POST['condicao_1'];
	$condicao_2 = $_POST['condicao_2'];
	$condicao_3 = $_POST['condicao_3'];

	if(strlen($condicao_1) == 0 && strlen($condicao_2) == 0 && strlen($condicao_3) == 0){
		$msg_erro[] = "Escolha pelo menos uma opção em que o POSTO TEM CONDICÕES DE ATENDER";
	}

	$condicao_1 = (strlen($condicao_1) > 0) ? "t" : "f";
	$condicao_2 = (strlen($condicao_2) > 0) ? "t" : "f";
	$condicao_3 = (strlen($condicao_3) > 0) ? "t" : "f";

	if(count($msg_erro) == 0 AND $verifica_posto == 0) {

		#-------------- INSERT ---------------
		$sql = "INSERT INTO tbl_posto_alteracao (
					posto			,
					fabrica			,
					razao_social    ,
					cnpj            ,
					ie              ,
					endereco        ,
					numero          ,
					complemento     ,
					bairro          ,
					cep             ,
					cidade          ,
					estado          ,
					email           ,
					fone            ,
					fax             ,
					contato         ,
					nome_fantasia	,
					linhas			,
					funcionario_qtde,
					os_qtde			,
					atende_cidade_proxima ,
					marca_ser_autorizada ,
					marca_nao_autorizada ,
					melhor_sistema ,
					outras_fabricas ,
					fabrica_credenciada ,
					marca_credenciada ,
					observacao,
					informacao_sistema,
					informacao_marca,
					informacao_vantagem,
					informacao_comentario,
					auto_credenciamento,
					banner,
					visita_tecnica,
					atende_consumidor_balcao,
					atende_revendas
				) VALUES (
					$posto  ,
					10				  ,
					$aux_nome		  ,
					$aux_cnpj         ,
					$aux_ie           ,
					$aux_endereco     ,
					$aux_numero       ,
					$aux_complemento  ,
					$aux_bairro       ,
					$aux_cep          ,
					$aux_cidade       ,
					$aux_estado       ,
					$aux_email        ,
					$aux_telefone     ,
					$aux_fax          ,
					$aux_contato      ,
					$aux_nome_fantasia,
					'$linhas'		  ,
					$aux_funcionarios ,
					$aux_oss		  ,
					'$aux_atende_cidade_proxima' ,
					 $opcao_outras_fabricas		,
					'$aux_marca_nao_autorizada' ,
					'$aux_cod_posto' ,
					$aux_opcao_outras_fabricas ,
					$and_fabrica_credenciadas,
					$and_marcas_credenciadas,
					$aux_descricao ,
					'$info_sistema',
					'$info_marca',
					'$info_vantagem',
					$aux_inf_comentario,
					true,
					false,
					'$condicao_1',
					'$condicao_2',
					'$condicao_3'
				)";
		$res = pg_query ($con,$sql);
		$msg_erro_insert = pg_errormessage($con);
		if(strlen($msg_erro_insert) > 0){
			$msg_erro[] = "Erro ao gravar os dados no sistema.<br>Tente novamente."; // $sql - $msg_erro_insert";
			pg_rollback();
		}

	} else {

		if (count($msg_erro) == 0) {
			$sql = "UPDATE tbl_posto_alteracao SET
							razao_social		= $aux_nome			,
							ie					= $aux_ie			,
							endereco			= $aux_endereco		,
							numero				= $aux_numero		,
							complemento			= $aux_complemento	,
							bairro				= $aux_bairro		,
							cep					= $aux_cep			,
							cidade				= $aux_cidade		,
							estado				= $aux_estado		,
							email				= $aux_email		,
							fone				= $aux_telefone		,
							fax					= $aux_fax			,
							contato				= $aux_contato		,
							nome_fantasia		= $aux_nome_fantasia,
							linhas				= '$linhas'			,
							funcionario_qtde	= $aux_funcionarios,
							os_qtde				= $aux_oss			,
							atende_cidade_proxima = '$aux_atende_cidade_proxima' ,
							marca_nao_autorizada = '$aux_marca_nao_autorizada' ,
							marca_ser_autorizada = $opcao_outras_fabricas ,
							melhor_sistema = '$aux_melhor_sistema' ,
							informacao_sistema = '$info_sistema',
							informacao_marca = '$info_marca',
							informacao_vantagem = '$info_vantagem' ,
							informacao_comentario = $aux_inf_comentario,
							outras_fabricas = $aux_opcao_outras_fabricas ,
							fabrica_credenciada = $and_fabrica_credenciadas,
							marca_credenciada = $and_marcas_credenciadas,
							observacao = $aux_descricao,
							auto_credenciamento = 't',
							visita_tecnica = '$condicao_1',
							atende_consumidor_balcao = '$condicao_2',
							atende_revendas = '$condicao_3'
						WHERE cnpj = $aux_cnpj";
			$res = pg_query($con, $sql);

			if (!is_resource($res)) {
				$msg_erro[] = pg_last_error($con);
				pg_rollback();
			}

			$sql = "UPDATE tbl_posto SET
							endereco			= $aux_endereco		,
							numero				= $aux_numero		,
							complemento			= $aux_complemento	,
							bairro				= $aux_bairro		,
							cep					= $aux_cep			,
							cidade				= $aux_cidade		,
							estado				= $aux_estado		,
							email				= $aux_email		,
							fone				= $aux_telefone		,
							fax					= $aux_fax			,
							contato				= $aux_contato
						WHERE cnpj = $aux_cnpj";

			$res = pg_query($con, $sql);

			if (!is_resource($res)) {
				$msg_erro[] = pg_last_error($con);
				pg_rollback();
			}
		}

	}
	//echo nl2br($aux_descricao);
	//echo nl2br($sql);exit;
	if(count($msg_erro) == 0){
		$config["tamanho"] = 2*1024*1024;

		$nome_foto__cnpj	= preg_replace('/\D/','',utf8_decode($aux_cnpj));

		for($i = 1; $i < 4; $i++){
			if ($_FILES["arquivo$i"]['name']=='') continue; //  Próxima iteração se não há arquivo definido
			$arquivo	= $_FILES["arquivo$i"];
			if ($debug) {echo "<p>Imagem para o posto $posto, Erros: ".count($msg_erro)."<br><pre>".var_dump($arquivo)."</pre></p>";}

			// Formulário postado... executa as ações
			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
				// Verifica o MIME-TYPE do arquivo
				if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
					$msg_erro[] = "Arquivo em formato inválido!";
				}
				// Verifica tamanho do arquivo
				if ($arquivo["size"] > $config["tamanho"])
					$msg_erro[] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";

				if (count($msg_erro) == 0) {
					// Pega extensão do arquivo
					preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "." . $ext[1];
					$aux_extensao = strtolower($aux_extensao);
					// Gera um nome único para a imagem
					$nome_anexo = $nome_foto__cnpj . "_" .$i . $aux_extensao;

				if ($debug) echo "<p>Gravando a imagem $i como $nome_anexo...</p>";
					// Exclui anteriores, qquer extensao
					@unlink($imagem_dir);

					// Faz o upload da imagem
					if (count($msg_erro) == 0) {
						$thumbail = new resize( "arquivo$i", 600, 400 );
						$thumbail -> saveTo($nome_anexo,$caminho_imagem);
					}
				}
			}
		}
	}

	if(count($msg_erro) == 0){
		pg_commit();
		$msg_ok = "OK";

		$fabricas_repl = preg_replace("/[\{\}']/", "", $and_fabrica_credenciadas);
		$marcas_repl = preg_replace("/[\{\}']/", "", $and_marcas_credenciadas);

		$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica IN ($fabricas_repl)";
		if (!empty($marcas_repl)) {
			$sql.= " UNION  SELECT nome FROM tbl_marca WHERE marca IN ($marcas_repl) ";
		}
		$sql.= " ORDER BY nome";
		$qry = pg_query($con, $sql);
		$fabricas_interesse = array();
		while ($fetch = pg_fetch_assoc($qry)) {
			$fabricas_interesse[] = $fetch['nome'];
		}

		if(!empty($_REQUEST['fabrica']) and ($_REQUEST['fabrica'] == 124 or $_REQUEST['fabrica'] == 126)) {
			$sql = "  select  array_to_string(array_agg(email),',') from tbl_admin where fabrica = ".$_REQUEST['fabrica']. " and privilegios='*' and help_desk_supervisor;";
			$res1 = pg_query($con,$sql);
			$email_destino = pg_fetch_result($res1,0,0);
		}else{
			$email_destino = "rodrigo.perina@telecontrol.com.br, ronaldo@telecontrol.com.br, jader.abdo@telecontrol.com.br";
		}
		$email_origem  = "suporte.fabricantes@telecontrol.com.br";
		$assunto       = "AUTO CREDENCIAMENTO - " . $nome ;
		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=utf-8\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";
		$corpo = "Foi feito um auto cadastramento no Telecontrol, segue os dados:";
		$corpo.= "<br><br>Posto: <b>$nome</b>";
		$corpo.= "<br>CNPJ: <b>$cnpj</b>";
		$corpo.= "<br>Cidade: <b>$cidade</b>";
		$corpo.= "<br>Estado: <b>$estado</b>";
		$corpo.= "<br>Linhas: <b>$linhas</b>";
		$corpo.= '<br>Fabricas de interesse: <b>' . implode(', ', $fabricas_interesse);
		if ($opcao_outras_fabricas != "''") {
			if (!empty($fabricas_interesse)) {
				$corpo.= ', ';
			}
			$corpo.= str_replace("'", "", $opcao_outras_fabricas);
		}
		$corpo.= '</b>';
		$corpo.= "<br>Qtde. Funcionários: <b>$aux_funcionarios</b>";
		$corpo.= "<br>Qtde. OS / mês: <b>" . str_replace("null", "", $aux_oss) . "</b>";
		$corpo.= "<br><br>_______________________________________________\n";
		$corpo.= "<br><br>Telecontrol\n";
		$corpo.= "<br>www.telecontrol.com.br\n";
		mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " );

	} else {
		pg_rollback();
	}
}
if (is_array($msg_erro)) {
	$a_msg_erro = implode("\n<br>", $msg_erro);
    unset($msg_erro);
    $msg_erro = $a_msg_erro;
    unset($a_msg_erro);
}


include 'topo_wordpress.php';

echo '<div class="titulo_tela">
			<br />
			<h1><a href="javascript:void(0)" style="cursor:point;">Autocredenciamento</a></h1>
		</div>';

if(strlen($msg_ok) > 0){?>

	<script language="JavaScript" type="text/javascript">
		var contador = 30;
		function conta() {
			if(contador == 0) {
				window.location = "http://www.telecontrol.com.br";
 			}

 			if (contador != 0){
	  			contador = contador-1;
  				setTimeout("conta()", 1000);
 			}
		}

	</script>

<br/>
<table width="962px" class="barra_topo">
	<tr>
		<td>&nbsp;</td>
	<tr>
</table>
<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
	<tr>
		<td>
			<div id="conteiner">
				<div id="conteudo">
					<br>
					<div class="email_sucesso" style="text-align: center;"><?=ttext ($a_labels, "tc_agradece", $cook_idioma)?>&nbsp;</div>
					<?php

					echo '<div style="float: left; width: 800px; margin: 20px 70px;">';

					echo '<div style="float: left; width: 500px; margin-left: 240px;">';
						echo '<strong>Razão Social:</strong> ' , $nome;
					echo '</div>';

					echo '<div style="float: left; width: 500px; margin-left: 240px;">';
						echo '<div style="float: left; width: 250px;">';
							echo '<strong>CNPJ:</strong> ' , $cnpj;
						echo '</div>';
						echo '<div style="float: left; width: 250px;">';
							echo '<strong>IE:</strong> ' , $ie;
						echo '</div>';
					echo '</div>';

					echo '<div style="float: left; width: 500px; margin-left: 240px;">';
						echo '<div style="float: left; width: 250px;">';
							echo '<strong>Cidade:</strong> ' , $cidade;
						echo '</div>';
						echo '<div style="float: left; width: 250px;">';
							echo '<strong>Estado:</strong> ' , $estado;
						echo '</div>';
					echo '</div>';

					echo '<div style="float: left; width: 500px; margin-left: 240px;">';
						echo '<strong>Email:</strong> ' , $email;
					echo '</div>';

					echo '</div>';


					echo '<div style="float: left; width: 900px; margin: 30px 0 20px 30px;">';

					$cnpj = str_replace("'", "", $aux_cnpj);

					$img_path = $caminho_path.$cnpj;
					$img_caminho = $caminho_imagem.$cnpj;

					if (file_exists($img_caminho."_1.jpg")) $img_ext = "jpg";
					if (file_exists($img_caminho."_1.png")) $img_ext = "png";
					if (file_exists($img_caminho."_1.gif")) $img_ext = "gif";

					if ($img_ext) {
						$img_src = $img_path . '_1.' . $img_ext;
						echo '<div style="float: left; width: 270px;">';
							echo '<img width="260" height="163" src="' , $img_src , '" />';
						echo '</div>';
					}

					if (file_exists($img_caminho."_2.jpg")) $img_ext = "jpg";
					if (file_exists($img_caminho."_2.png")) $img_ext = "png";
					if (file_exists($img_caminho."_2.gif")) $img_ext = "gif";

					if ($img_ext) {
						$img_src = $img_path . '_2.' . $img_ext;
						echo '<div style="float: left; width: 270px; margin-left: 40px;">';
							echo '<img width="260" height="163" src="' , $img_src , '" />';
						echo '</div>';
					}

					if (file_exists($img_caminho."_3.jpg")) $img_ext = "jpg";
					if (file_exists($img_caminho."_3.png")) $img_ext = "png";
					if (file_exists($img_caminho."_3.gif")) $img_ext = "gif";

					if ($img_ext) {
						$img_src = $img_path . '_3.' . $img_ext;
						echo '<div style="float: left; width: 270px; margin-left: 40px;">';
							echo '<img width="260" height="163" src="' , $img_src , '" />';
						echo '</div>';
					}

					echo '</div>';

					echo '</div>';
					?>
				</div>
			</div>
		</td>
	</tr>
</table><br/>
	<script>window.onload = conta();</script>

<?php
	include 'rodape_wordpress.php';
	exit;
}
?>

<script src="../js/jquery.js" type="text/javascript"></script>
<script src="../js/jquery.autocomplete.js" type="text/javascript"></script>
<script src="../js/file/jquery.MultiFile_novo.js" type="text/javascript"></script>
<script type='text/javascript' src='../js/jquery.maskedinput.js'></script>
<script type="text/javascript" src="../js/jquery.numeric.js"></script>
<script language="JavaScript">

function verifica_submit() {

	var verifica = 0;

	var nome = $(".nome").val();
	var nome_fantasia = $(".nome_fantasia").val();
	var cpnj = $("#cnpj").val();
	var ie = $("#ie").val();
	var contato = $(".contato").val();
	var telefone = $(".telefone").val();
	var email = $(".email").val();
	var cep = $(".cep").val();
	var endereco = $(".endereco").val();
	var numero = $(".numero").val();
	var bairro = $(".bairro").val();
	var cidade = $(".cidade").val();
	var estado = $(".estado").val();
	var funcionarios = $(".funcionarios").val();
	var fabricantes = $("#fabricantes").val();
	var atende_cidade_proxima = $("#atende_cidade_proxima").val();

	var linha_1 = $("#linha_1").attr("checked");
	var linha_2 = $("#linha_2").attr("checked");
	var linha_3 = $("#linha_3").attr("checked");
	var linha_4 = $("#linha_4").attr("checked");
	var linha_5 = $("#linha_5").attr("checked");
	var linha_6 = $("#linha_6").attr("checked");
	var linha_7 = $("#linha_7").attr("checked");

	var condicao_1 = $("#condicao_1").attr("checked");
	var condicao_2 = $("#condicao_2").attr("checked");
	var condicao_3 = $("#condicao_3").attr("checked");

	var arquivo1 = $("#arquivo1").val();
	var arquivo2 = $("#arquivo2").val();
	var arquivo3 = $("#arquivo3").val();

	var total_fab = $("#total_fab").val();
	var outras_fabricas = $("#outras_fabricas").attr("checked");
	var outras_fabricas_txt = $("#opcao_outras_fabricas").val();

	var s_outro_sis = $("#s_outro_sis").attr("checked");

	var melhor_sistema = $("#melhor_sistema_txt").val();

	if (!nome) {
		$(".nome").css('border-color','#C6322B');
		$(".nome").css('border-width','1px');
		$(".razaosocial").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".nome").css('border-color','#CCC');
		$(".nome").css('border-width','1px');
		$(".razaosocial").css('color','#535252');
	}

	if (!nome_fantasia) {
		$(".nome_fantasia").css('border-color','#C6322B');
		$(".nome_fantasia").css('border-width','1px');
		$(".lnome_fantasia").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".nome_fantasia").css('border-color','#CCC');
		$(".nome_fantasia").css('border-width','1px');
		$(".lnome_fantasia").css('color','#535252');
	}

	if (!cpnj) {
		$("#cnpj").css('border-color','#C6322B');
		$("#cnpj").css('border-width','1px');
		$(".lcnpj").css('color','#C6322B');
		verifica ='1';
	} else {
		$("#cnpj").css('border-color','#CCC');
		$("#cnpj").css('border-width','1px');
		$(".lcnpj").css('color','#535252');
	}

	if (!contato) {
		$(".contato").css('border-color','#C6322B');
		$(".contato").css('border-width','1px');
		$(".lcontato").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".contato").css('border-color','#CCC');
		$(".contato").css('border-width','1px');
		$(".lcontato").css('color','#535252');
	}

	if (!telefone) {
		$(".telefone").css('border-color','#C6322B');
		$(".telefone").css('border-width','1px');
		$(".ltelefone").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".telefone").css('border-color','#CCC');
		$(".telefone").css('border-width','1px');
		$(".ltelefone").css('color','#535252');
	}

	if (!email) {
		$(".email").css('border-color','#C6322B');
		$(".email").css('border-width','1px');
		$(".lemail").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".email").css('border-color','#CCC');
		$(".email").css('border-width','1px');
		$(".lemail").css('color','#535252');
	}

	if (!cep) {
		$(".cep").css('border-color','#C6322B');
		$(".cep").css('border-width','1px');
		$(".lcep").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".cep").css('border-color','#CCC');
		$(".cep").css('border-width','1px');
		$(".lcep").css('color','#535252');
	}

	if (!endereco) {
		$(".endereco").css('border-color','#C6322B');
		$(".endereco").css('border-width','1px');
		$(".lendereco").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".endereco").css('border-color','#CCC');
		$(".endereco").css('border-width','1px');
		$(".lendereco").css('color','#535252');
	}

	if (!numero) {
		$(".numero").css('border-color','#C6322B');
		$(".numero").css('border-width','1px');
		$(".lnumero").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".numero").css('border-color','#CCC');
		$(".numero").css('border-width','1px');
		$(".lnumero").css('color','#535252');
	}

	if (!bairro) {
		$(".bairro").css('border-color','#C6322B');
		$(".bairro").css('border-width','1px');
		$(".lbairro").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".bairro").css('border-color','#CCC');
		$(".bairro").css('border-width','1px');
		$(".lbairro").css('color','#535252');
	}

	if (!cidade) {
		$(".cidade").css('border-color','#C6322B');
		$(".cidade").css('border-width','1px');
		$(".lcidade").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".cidade").css('border-color','#CCC');
		$(".cidade").css('border-width','1px');
		$(".lcidade").css('color','#535252');
	}

	if (!estado) {
		$(".estado").css('border-color','#C6322B');
		$(".estado").css('border-width','1px');
		$(".lestado").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".estado").css('border-color','#CCC');
		$(".estado").css('border-width','1px');
		$(".lestado").css('color','#535252');
	}

	if (!funcionarios) {
		$(".funcionarios").css('border-color','#C6322B');
		$(".funcionarios").css('border-width','1px');
		$(".lfuncionarios").css('color','#C6322B');
		verifica ='1';
	} else {
		$(".funcionarios").css('border-color','#CCC');
		$(".funcionarios").css('border-width','1px');
		$(".lfuncionarios").css('color','#535252');
	}

	if (!fabricantes) {
		$("#fabricantes").css('border-color','#C6322B');
		$("#fabricantes").css('border-width','1px');
		$(".lfabricantes").css('color','#C6322B');
		verifica ='1';
	} else {
		$("#fabricantes").css('border-color','#CCC');
		$("#fabricantes").css('border-width','1px');
		$(".lfabricantes").css('color','#535252');
	}

	if (!atende_cidade_proxima) {
		$("#atende_cidade_proxima").css('border-color','#C6322B');
		$("#atende_cidade_proxima").css('border-width','1px');
		$(".latende_cidade_proxima").css('color','#C6322B');
		verifica ='1';
	} else {
		$("#atende_cidade_proxima").css('border-color','#CCC');
		$("#atende_cidade_proxima").css('border-width','1px');
		$(".latende_cidade_proxima").css('color','#535252');
	}

	if (linha_1 == false && linha_2 == false && linha_3 == false && linha_4 == false && linha_5 == false && linha_6 == false && linha_7 == false) {
		$(".llinhas").css("color", "#C6322B");
		$("#info_ad_linhas").css("border-color", "#C6322B");
		verifica ='1';
	} else {
		$(".llinhas").css("color", "#535252");
		$("#info_ad_linhas").css("border-color", "#CCCCCC");
	}

    if (condicao_1 == false && condicao_2 == false && condicao_3 == false) {
		$(".latender").css("color", "#C6322B");
		$("#info_atender").css("border-color", "#C6322B");
		verifica ='1';
	} else {
		$(".latender").css("color", "#535252");
		$("#info_atender").css("border-color", "#CCCCCC");
	}

	var testVal = '';
	var totErr = 0;
	var fabrica_ok = 1;



	for (var i = 0; i < total_fab; i++) {
		testVal = $("input[name=fabrica_" + i + "]").attr("checked");

		<? // HD-1867132

		if($_GET['fabrica'] == 114 AND $_GET['linha'] == 811 OR $_GET['fabrica'] == 114 AND $_GET['linha'] == 710){
		?>
				testVal = $("input[name='fabrica_8']").val();
		<?
			}
			// FIM HD-1867132
		?>
		if (!testVal) {
			totErr++;
		}
	}


	if (totErr < total_fab) {
		fabrica_ok = 0;
	} else {
		if (outras_fabricas && outras_fabricas_txt) {
			fabrica_ok = 0;
		}
	}


	if (fabrica_ok == 1) {
		$("#fabrica_marcas_label_topo").css("color", "#C6322B");
		$("#info_ad_fabricas").css("border-color", "#C6322B");
		verifica ='1';
	} else {
		$("#fabrica_marcas_label_topo").css("color", "#535252");
		$("#info_ad_fabricas").css("border-color", "#CCCCCC");
	}

	if (!arquivo1) {
		var old_foto1 = $("#old_foto1").val();

		if (old_foto1 != "1") {
			$("#arquivo1").css('border-color','#C6322B');
			$("#arquivo1").css('border-width','1px');
			$(".lfachada").css('color','#C6322B');
			verifica ='1';
		}
	} else {
		$("#arquivo1").css('border-color','#CCC');
		$("#arquivo1").css('border-width','1px');
		$(".lfachada").css('color','#535252');
	}

	if (!arquivo2) {
		var old_foto2 = $("#old_foto2").val();

		if (old_foto2 != "1") {
			$("#arquivo2").css('border-color','#C6322B');
			$("#arquivo2").css('border-width','1px');
			$(".lrecepcao").css('color','#C6322B');
			verifica ='1';
		}
	} else {
		$("#arquivo2").css('border-color','#CCC');
		$("#arquivo2").css('border-width','1px');
		$(".lrecepcao").css('color','#535252');
	}

	if (!arquivo3) {
		var old_foto3 = $("#old_foto3").val();

		if (old_foto3 != "1") {
			$("#arquivo3").css('border-color','#C6322B');
			$("#arquivo3").css('border-width','1px');
			$(".loficina").css('color','#C6322B');
			verifica ='1';
		}
	} else {
		$("#arquivo3").css('border-color','#CCC');
		$("#arquivo3").css('border-width','1px');
		$(".loficina").css('color','#535252');
	}

	if (s_outro_sis) {
		var inf = 0;

		var if1 = $('#inf_sistema1').val();
		var im1 = $('#inf_marca1').val();
		var iv1 = $('#inf_vantagem1').val();

		if (if1 && im1 && iv1) {
			inf = 0;
		} else {
			inf++;
		}

		var if2 = $('#inf_sistema2').val();
		var im2 = $('#inf_marca2').val();
		var iv2 = $('#inf_vantagem2').val();

		if (if2 && im2 && iv2) {
			inf = 0;
		} else {
			inf++;
		}

		var if3 = $('#inf_sistema3').val();
		var im3 = $('#inf_marca3').val();
		var iv3 = $('#inf_vantagem3').val();

		if (if3 && im3 && iv3) {
			inf = 0;
		} else {
			inf++;
		}

		if (inf == 3) {
			$("#inf_sistema1").css('border-color','#C6322B');
			$("#inf_sistema1").css('border-width','1px');

			$("#inf_marca1").css('border-color','#C6322B');
			$("#inf_marca1").css('border-width','#1px');

			$("#inf_vantagem1").css('border-color','#C6322B');
			$("#inf_vantagem1").css('border-width','1px');

			$("#label_informacoes_sistem_extra").css('color','#C6322B');
			$("#label_sistema").css('color','#C6322B');
			$("#label_marca").css('color','#C6322B');
			$("#label_vantagem").css('color','#C6322B');

			verifica ='1';
		} else {
			$("#inf_sistema1").css('border-color','#CCC');
			$("#inf_sistema1").css('border-width','1px');

			$("#inf_marca1").css('border-color','#CCC');
			$("#inf_marca1").css('border-width','1px');

			$("#inf_vantagem1").css('border-color','#CCC');
			$("#inf_vantagem1").css('border-width','1px');

			$("#label_informacoes_sistem_extra").css('color','#535252');
			$("#label_sistema").css('color','#535252');
			$("#label_marca").css('color','#535252');
			$("#label_vantagem").css('color','#535252');
		}
	} else {
		$("#inf_sistema1").css('border-color','#CCC');
		$("#inf_sistema1").css('border-width','1px');

		$("#inf_marca1").css('border-color','#CCC');
		$("#inf_marca1").css('border-width','1px');

		$("#inf_vantagem1").css('border-color','#CCC');
		$("#inf_vantagem1").css('border-width','1px');

		$("#label_informacoes_sistem_extra").css('color','#535252');
		$("#label_sistema").css('color','#535252');
		$("#label_marca").css('color','#535252');
		$("#label_vantagem").css('color','#535252');
	}

	if (!melhor_sistema) {
		$("#melhor_sistema_txt").css('border-color','#C6322B');
		$("#melhor_sistema_txt").css('border-width','1px');
		$("#melhor_sis").css('color','#C6322B');
		verifica ='1';
	} else {
		$("#melhor_sistema_txt").css('border-color','#CCC');
		$("#melhor_sistema_txt").css('border-width','1px');
		$("#melhor_sis").css('color','#535252');
	}

	if (verifica =='1') {
		//alert("ERRO");
		$("#mensagem_envio").html('');
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* Por favor, verifique os campos marcados em vermelho.</label>');
		window.location.hash = '#mensagem_envio';
		return false;
	} else {
		//alert("SUCESSO");
		$("#mensagem_envio").html('');
		$('#frm_posto').submit();//EXECUTA O SUBMIT
		return true;
	}

}

function vericaSubmitCNPJ() {
	var cnpj = $("#cnpj").val();

	if (!cnpj) {
		$("#cnpj").css('border-color','#C6322B');
		$("#cnpj").css('border-width','1px');
		$(".informe_cnpj").css('color', '#C6322B');

		$("#mensagem_envio").html('');
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* Por favor, informe seu CNPJ</label>');
		return false;
	} else {
		$("#mensagem_envio").html('');
		$('#verificaa').submit();
		return true;
	}
}

function mostraOutrosSis(p) {
	var el = document.getElementById(p + '_outro_sis');

	if (el.value == "S") {
		document.getElementById('outros_sistemas').style.display = "block";
	} else {
		document.getElementById('outros_sistemas').style.display = "none";
	}
}

function txtBoxFormat(objeto, sMask, evtKeyPress) {
	var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

	if(document.all) { // Internet Explorer
		nTecla = evtKeyPress.keyCode;
	} else if(document.layers) { // Nestcape
		nTecla = evtKeyPress.which;
	} else {
		nTecla = evtKeyPress.which;
		if (nTecla == 8) {
			return true;
		}
	}

	sValue = objeto.value;

	// Limpa todos os caracteres de formatação que
	// já estiverem no campo.
	sValue = sValue.toString().replace( /[\-\.\/:\(\)\s]/g, "");
	fldLen = sValue.length;
	mskLen = sMask.length;

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
		bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/") || (sMask.charAt(i) == ":"))
		bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " "))

	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++; }
	else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}

	  i++;
	}

	objeto.value = sCod;

	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
			return ((nTecla > 47) && (nTecla < 58)); }
		else { // qualquer caracter...
			return true;
	}
	}
	else {
		return true;
	}
}

function verificaNumero(e) {
	if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
		return false;
	}
}

function vretirar_caracter(e) {
	if (e.which == 124) {
		return false;
	}
}

$(document).ready(function() {
	$(".funcionarios").keypress(verificaNumero);
});


$(document).ready(function() {
	$(".inf_sistemas").keypress(vretirar_caracter);
});


$(document).ready(function() {
	$("input[@name=cnpj]").maskedinput("99.999.999/9999-99");
	$("input[@name=telefone]").maskedinput("(99) 9999-9999");
	$("input[@name=fax]").maskedinput("(99) 9999-9999");
	$("input[@name=cep]").maskedinput("99999-999");

	$("#ie").numeric();
	$("#numero").numeric();
	$(".funcionarios").numeric();
	$(".oss").numeric();
});

</script>

<script type="text/javascript">
jQuery().ready(function() {
//  Busca CEP
	jQuery('form input[name=cep]').blur(function() {
		var cep		= escape(jQuery(this).val());
		var endereco= jQuery('form input[name=endereco]');
		var bairro	= jQuery('form input[name=bairro]');
		var cidade	= jQuery('form input[name=cidade]');
		var estado	= jQuery('form select[name=estado]');

		if (cep.length >= 8) {
			jQuery.get('ajax_cep.php',
						{'cep':cep},
						function(data) {
				results = data.split(";");
// 				alert(data);
				if (results[0] != 'ok'){
					jQuery('#endereco').val('');
					jQuery('#bairro').val('');
					jQuery('#cidade').val('');
					jQuery('#estado').val('');
					jQuery('#numero').val('');
					jQuery('#complemento').val('');
					return false;
				}

				if (typeof (results[1]) != 'undefined') endereco.val(results[1]);
				if (typeof (results[2]) != 'undefined') bairro.val(results[2]);
				if (typeof (results[3]) != 'undefined') cidade.val(results[3]);
				if (typeof (results[4]) != 'undefined') estado.val(results[4]);

				if (data.length <= 2) {
					jQuery('#endereco').focus();
				} else {
					jQuery('#numero').val('');
					jQuery('#complemento').val('');
					jQuery('#numero').focus();
				}
			});
		} else {
			jQuery('#endereco').val('');
			jQuery('#bairro').val('');
			jQuery('#cidade').val('');
			jQuery('#estado').val('');
			jQuery('#numero').val('');
			return false;
		}
	});


<?php
	/*  Define o array para o autocomplete dos fabricantes cadastrados na Telecontrol. Talvez seja possível adicionar
	também marcas de outros cadastros... Veremos    */

	$temp_fabricas = pg_fetch_all(pg_query($con, "SELECT nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE and fabrica NOT IN ($not_in_fabricas)"));
	foreach ($temp_fabricas as $fabrica_temp) {
		$fabricas_tc[] = '"'.$fabrica_temp['nome'].'"';
	}
	echo "	var fabricas = [".implode(",",$fabricas_tc)."];\n";
?>


	$('#linha_6').click(function(){
		if($('#linha_6').is(':checked')){
			$('#linha_6_obs').attr('disabled',false);
		}else{
			$('#linha_6_obs').attr('disabled',true);
		}
	});


	$('#marca_todas_fabricas').click(function(){
		if($('#marca_todas_fabricas').is(':checked')){
			$('.todas_fabricas').attr('checked','checked');
		}else{
			$('.todas_fabricas').removeAttr('checked');
		}
	});


	$('#outras_fabricas').click(function(){
		if($('#outras_fabricas').is(':checked')){
			$('#opcao_outras_fabricas').attr('disabled',false);
		}else{
			$('#opcao_outras_fabricas').attr('disabled',true);
		}
	});
});
</script>

<style>
#fullcontent {
	font: 16px Arial,Helvetica,sans-serif;
}
#entry {font-size: 11px;}
#entry fieldset label {
	display: inline-block;
	zoom:1;
	width: 120px;
}
A#manual:visited,A#manual:link {
	color:#F00;
	text-decoration:none;
    font-weight: bold;
}

A#manual:hover {text-decoration:underline;}
/* Fim  */

.texto_informativo{
	font: 12px Arial,Helvetica,sans-serif;
	color:#596D9B;
}

.texto_informativo_informacao{
	font: 12px Arial,Helvetica,sans-serif;
	color:#596D9B;
	width: 100%;
	text-align:left;
}

.texto_informativo_informacao_font_cabecalho{
	text-align: center;
	font: 15px Arial,Helvetica,sans-serif;
}

.texto_informativo_informacao_font_conteudo{
	text-align:justify;
	font: 13px Arial,Helvetica,sans-serif;
	width: 900px;
	margin: auto;
}

.concordo { border: none; }
.todas_fabricas { border: none; }
.fieldset_informacao { width: 950px; margin-left: 20px; border: 1px solid #CCCCCC; padding-top: 20px; }

fieldset label {
    display: inline-block;
    margin-right: 0.5ex;
    text-align: right;
    width: 8em;
	font: 12px Arial,Helvetica,sans-serif;
    font-weight: bold;
}


fieldset span {
	font: 12px Arial,Helvetica,sans-serif;
}


#linhas_label{ font: 12px Arial,Helvetica,sans-serif; font-weight: bold; }


#info_ad label{ font: 12px Arial,Helvetica,sans-serif; font-weight: bold; }

fieldset#info_ad label, label#descricao_posto {
	font: 12px Arial,Helvetica,sans-serif;
	display: block;
	width: 98%;
	text-transform: none;
	margin-top: 1em;
	text-align: left;
	font-weight: bold;
}

#fabrica_marcas_label{ font: 12px Arial,Helvetica,sans-serif; font-weight: bold; }

#fabrica_marcas_label_topo{ font: 16px; }

#fotos_label{ font: 16px; }


h3 {
	display: block;
	font-size: 1.17em;
	-webkit-margin-before: 1em;
	-webkit-margin-after: 1em;
	-webkit-margin-start: 0px;
	-webkit-margin-end: 0px;
	font-weight: bold;
	font: 12px Arial,Helvetica,sans-serif;
}

form fieldset img {
	border-radius: 4px;
	-moz-border-radius: 4px;
	height: 48px;
	left: 10px;
	max-width: 100%;
	margin-bottom: 2px;
	border: 2px solid grey;
	position: relative;
	vertical-align: middle;
}

form fieldset img:hover {
	border: 7px solid white;
	box-shadow:2px 2px 4px black;
}


td {
	border-top: 0px solid whiteSmoke;
}


.css_termo_de_uso{
	 font-weight: bold;
	 margin-right: 0.5ex;
	 text-align: right;
	 text-transform: capitalize;
	 width: 10em;
	 font: 12px Arial,Helvetica,sans-serif;
}

.informe_cnpj { font-weight: bold; text-align: center; width: 180px; }
.btn_acao { cursor: pointer; display: inline-block; height: 24px; line-height: 18px; padding: 0 5px 1px; }

.colunas {
	border: 1px solid #DDDDDD;
    border-radius: 8px 8px 8px 8px;
    line-height: 2em;
    margin: 2em;
    padding: 1em;
	width: 860px;
}

.inf_sistemas { width: 260px; }
#inf_comentario { width: 835px; }
#fabrica_marcas_label_topo { color: #222222; }

</style>

<?
/* Refiz usando jQuery...
<script language='javascript' src='/ajax.js'></script>
<script language='javascript' src='/ajax_cep.js'></script>
*/
	if(strlen($msg_erro) > 0){	?>
	<center>
		<div style='width:800px;margin:0 auto;text-align:center;padding:3px'>
			<p style='text-align: center;font-size: 12px;font-weight:bold; color: #FF0000;'><?=$msg_erro?>
			</p>
		</div>
	</center>
	<br>
<?	}

 if(strlen($btn_acao) == 0 or $aux_cnpj == ""){ /* PREECHIMENTO DO CNPJ PARA VERIFICACAO NO BANCO  or !$aux_cnpj */?>
	<br/><br/>
	<form method='post' id='verificaa' name='frm_verifica' action="<?$PHP_SELF?>">
 		<div style="float: left; text-align: center; width: 950px; margin-left: 20px;">
			<div style="float: left; margin-left: 220px; width: 180px;">
				<label class="informe_cnpj"><?=ttext($a_labels, "Informe_CNPJ", $cook_idioma)?></label>
			</div>
			<div style="float: left;">
				<input style="width: 140px;" type="text" name="cnpj" id='cnpj' maxlength="18" value="<?=trim($cnpj)?>">
			</div>
			<div style="float: left; margin-left: 20px;">
				<input type="hidden" name="btn_acao" value="Cadastrar" />
				<?php if ($_GET['wurth'] == 's'): ?>
					<input type="hidden" name="wurth" value="ok">
				<?php endif ?>
				<?php if ($_GET['cobimex'] == 's'): ?>
					<input type="hidden" name="cobimex" value="ok">
				<?php endif ?>
				<?php if ($_GET['positec'] == 's'): ?>
					<input type="hidden" name="positec" value="ok">
				<?php endif ?>
				<?php if (!empty($_GET['fabrica'])): ?>
				<input type="hidden" name="<?=$fabrica_nome?>" value="ok">
				<?php endif ?>


				<input type="button"  id='btn_acao' class='input_gravar' style="cursor: default;" value='<?=ttext ($a_labels, "Cadastrar", $cook_idioma)?>' onClick="vericaSubmitCNPJ()" />
			</div>
		</div>

		<div style="float: left; text-align: center; width: 950px; margin: 30px 0 40px 0;">
			<div class="barra_topo" style="width: 950px; margin-left: 20px;">
				<div id="mensagem_envio">&nbsp;<?php echo $msg;?></div>
			</div>
			<div class="fieldset_informacao">
				<?=ttext ($a_labels, "label_texto_informacao", $cook_idioma)?>
			</div>
		</div>

	</form>
	<br />
<?

}else{
/*  define os textos em vários idiomas num array asociado com o formulário...    */
$a_labels = array(
//  Dados cadastrais
	"cadastro"		=> array(
		"pt-br" => "Informações Cadastrais",
		"es"	=> "Datos Fiscales",
		"en"	=> "Fiscal Identification"
	),
	"razaosocial"	=> array(
		"pt-br" => "Razão Social",
		"es"	=> "Razón Social",
		"en"	=> "Name"
	),
	"fantasia"		=> array(
		"pt-br" => "Nome Fantasia",
		"es"	=> "Nombre",
		"en"	=> "Trade Name"
	),
	"cnpj"		=> array(
		"pt-br" => "CNPJ/CPF",
		"es"	=> "ID Fiscal",
		"en"	=> "Tax ID"
	),
	"insc_est"	=> array(
		"pt-br" => "I. E.",
		"es"	=> "IE (solo brasil)",
		"en"	=> "IE (Brazil only)"
	),
//  Dados de contato
	"dadoscont"	=> array(
		"pt-br" => "informações de contato",
		"es"	=> "datos de contacto",
		"en"	=> "contact data"
	),
	"contato"	=> array(
		"pt-br" => "Contato",
		"es"	=> "Contacto",
		"en"	=> "Contact"
	),
	"telefone"	=> array(
		"pt-br" => "Telefone",
		"es"	=> "Teléfono",
		"en"	=> "Phone number"
	),
	"email"		=> array(
		"pt-br" => "Email",
		"es"	=> "correo-e",
		"en"	=> ""
	),
	"fax"		=> array(
		"pt-br" => "Fax",
		"es"	=> "",
		"en"	=> ""
	),
	"endereco"	=> array(
		"pt-br" => "Endereço",
		"es"	=> "Dirección",
		"en"	=> "Address"
	),
	"rua"		=> array(
		"pt-br" => "Logradouro",
		"es"	=> "Vía",
		"en"	=> "Address"
	),
	"numero"	=> array(
		"pt-br" => "Número",
		"es"	=> "",
		"en"	=> "Number"
	),
	"complemento"=>array(
		"pt-br" => "Complemento",
		"es"	=> "",
		"en"	=> "Extra"
	),
	"bairro"	=> array(
		"pt-br" => "Bairro",
		"es"	=> "Barrio",
		"en"	=> "Neighborhood"
	),
	"cep"		=> array(
		"pt-br" => "Cep",
		"es"	=> "CP",
		"en"	=> "ZIP Code"
	),
	"cep_busca" => array(
		"pt-br" => "Digite seu CEP para agilizar o preenchimento",
		"es"	=> "(Solo Brasil) escriba su CEP para localizar su dirección",
		"en"	=> "(Brazil only) Type your CEP to speed up the form fill"
	),
	"cidade"	=> array(
		"pt-br" => "Cidade",
		"es"	=> "Ciudad",
		"en"	=> "City"
	),
	"estado"	=> array(
		"pt-br" => "Estado",
		"es"	=> "Estado/Región",
		"en"	=> "State"
	),
//  Linhas que trabalha
	"linhas"	=> array(
		"pt-br" => "linhas que trabalha",
		"es"	=> "líneas que atiende",
		"en"	=> "lines you serve"
	),
	"branca"	=> array(
		"pt-br" => "BRANCA - adega, refrigeração, ar-condicionado (split, janela,..)",
		"es"	=> "BLANCA - bodegas, refrigeración, aire acondicionado, etc.",
		"en"	=> "WHITE - freezers, air conditioners, etc."
	),
	"marron"	=> array(
		"pt-br" => "MARROM - áudio e video (TV, DVD, MP3, MP4, ...)",
		"es"	=> "MARRON - audio y vídeo (TV, DVD, MP3, MP4, ...)",
		"en"	=> "BROWN - Audio & video (TV, DVD, MP3, MP4, ...)"
	),
	"eletro"	=> array(
		"pt-br" => "ELETROPORTÁTEIS - liquidificadores, ventiladores, ...",
		"es"	=> "BLANCA PORTÁTIL - licuadoras, ventiladores, ...",
		"en"	=> "ELETROPORTABLES - blenders, fans, ..."
	),
	"informatica"=>array(
		"pt-br" => "INFORMÁTICA - notebook, monitores, ...",
		"es"	=> "INFORMÁTICA - portátiles, monitores, ...",
		"en"	=> "COMPUTERS - notebooks, monitors, ..."
	),
	"ferramentas"=>array(
		"pt-br" => "FERRAMENTAS - furadeiras, serras, motosserras ...",
		"es"	=> "HERRAMIENTAS - taladros, sierras de corte, sierras mecánicas, ...",
		"en"	=> "TOOLS - drills, chainsaws, ..."
	),
	"lavadoras" => array (
		"pt-br" => "LAVADORAS DE ALTA PRESSÃO",
		"es" => "",
		"en" => ""
	),
	"outras"	=> array(
		"pt-br" => "OUTRAS",
		"es"	=> "OTRAS",
		"en"	=> "OTHER"
	),
	"quais"		=> array(
		"pt-br" => "QUAIS?",
		"es"	=> "¿CUÁLES?",
		"en"	=> "WHICH?"
	),
//  Informações adicionais
	"moreinfo"	=> array(
		"pt-br" => "informações adicionais",
		"es"	=> "información adicional",
		"en"	=> "additional information"
	),
	"qtdefunc"	=> array(
		"pt-br" => "Quantidade de funcionários",
		"es"	=> "Número de empleados",
		"en"	=> "Number of employees"
	),
	"qtdeos"	=> array(
		"pt-br" => "Quantidade de ordens de serviço mensal",
		"es"	=> "Cantidad de órdenes de servicio por mes",
		"en"	=> "Number of service orders per month"
	),
	"marcasauth"=> array(
		"pt-br" => "Quais marcas sua empresa é autorizada atualmente?",
		"es"	=> "¿De qué marcas es servicio autorizado actualmente?",
		"en"	=> "What brands are you currently authorized service?"
	),
	"melhor_sis"=> array(
		"pt-br" => "Na sua opinião, qual o melhor sistema informatizado de ordens de serviço? Por quê?",
		"es"	=> "En su opinión, ¿cuál es el mejor sistema informatizado de órdenes de servicio? ¿Por qué?",
		"en"	=> "In you opinion, what is the best service order's omputer program? Why?"
	),
	"atendeperto"=>array(
		"pt-br" => "Sua empresa atende cidades próximas? Quais?",
		"es"	=> "¿Su empresa atiende ciudades cercanas? ¿Cuáles?",
		"en"	=> "Does your company attend cities nearby? Which ones?"
	),
	"marcasquer"=> array(
		"pt-br" => "Quais marcas sua empresa gostaria de ser autorizada?",
		"es"	=> "¿De qué marcas querría ser servicio autorizado?",
		"en"	=> "For which brands would you like to be an authorized service?"
	),
	"marcas_nao"=> array(
		"pt-br" => "Quais marcas sua empresa não gostaria de ser autorizada?",
		"es"	=> "¿De qué marcas <b>no</b> querría ser servicio autorizado?",
		"en"	=> "For which brands wouldn't you like to be an authorized service?"
	),
	"porque"=> array(
		"pt-br" => "Por quê?",
		"es"	=> "¿Por qué?",
		"en"	=> "Why?"
	),
//  Fotos e descrição
	"fotos&desc"=> array(
		"pt-br" => "Fotos / Descrição",
		"es"	=> "Fotos / Descripción",
		"en"	=> "Pictures / Description"
	),
	"3fotos"	=> array(
		"pt-br" => "Três fotos da sua loja (fachada, recepção e laboratório) com as extensões JPG e tamanho máximo de 2MB.",
		"es"	=> "Tres fotos de su establecimiento (fachada, recepción y taller o laboratorio), en formato JPG.",
		"en"	=> "Three photos of your store (front, reception and workshop or laboratory), JPG format.."
	),
	"fachada"	=> array(
		"pt-br" => "Fachada",
		"es"	=> "Fachada",
		"en"	=> "Facade"
	),
	"recepção"	=> array(
		"pt-br" => "Recepção",
		"es"	=> "Recepción",
		"en"	=> "Foyer"
	),
	"oficina"	=> array(
		"pt-br" => "Laboratório",
		"es"	=> "Laboratorio",
		"en"	=> "Laboratory"
	),
	"DescAuth"	=> array(
		"pt-br" => "Descrição de sua Autorizada",
		"es"	=> "Descipción de su Servicio Autorizado",
		"en"	=> "Describe your store"
	),
	"gravar"	=> array(
		"pt-br" => "Credenciar",
		"es"	=> "Enviar Formulario",
		"en"	=> "Submit Form"
	),
	"opcoes_fabricas"	=> array(
		"pt-br" => "Fábricas de Interesse",
		"es"	=> "",
		"en"	=> "Accredit the Factories"
	),
	"marca_todos"=> array(
		"pt-br" => "MARCAR TODOS",
		"es"	=> "",
		"en"	=> "CHECK ALL"
	),
	"mensagem_topo"=> array(
		"pt-br" => "Campos em que o conteúdo foi alterado",
		"es"	=> "Campos cuyo valor ha sido alterado",
		"en"	=> "Fields whose value have been altered"
	),
	"mensagem_obrigatorio"=> array(
		"pt-br" => "* Campos Obrigatórios",
		"es"	=> "* Campos Obligatorios",
		"en"	=> "* Required Fields"
	),
	"informacao"=> array(
		"pt-br" => "Informações",
		"es"	=> "informacións",
		"en"	=> "Information"
	),
	"informacoes_sistema"=> array(
		"pt-br" => "Você usa outros sistemas além do Telecontrol?", /* Para quais marcas? Quais são as vantagens?",*/
		"es"	=> "",
		"en"	=> ""
	),
	"informacoes_sistema_extra" => array (
		"pt-br" => "Para quais marcas? Quais são as vantagens?",
		"es" => "",
		"en" => ""
	),
	"label_sistema"=> array(
		"pt-br" => "Nome do Sistema",
		"es"	=> "Sistema",
		"en"	=> "System"
	),
	"label_marca"=> array(
		"pt-br" => "Marca",
		"es"	=> "Marca",
		"en"	=> "Mark"
	),
	 "label_vantagem"=> array(
		"pt-br" => "Vantagens",
		"es"	=> "ventajas",
		"en"	=> "Advantages"
	),
	 "label_comentario"=> array(
		"pt-br" => "Comentário livre",
		"es"	=> "Evaluación gratis",
		"en"	=> "Free Appraisal"
	),
 )
?>

	<div class="div_top_principal">
		<table width="100%" style="text-align: right;">
			<tr>
				<td>
					*Campos obrigat&oacute;rios.
				</td>
			<tr>
		</table>
	</div>

	<table width="962px" class="barra_topo">
		<tr>
			<td><div id="mensagem_envio">&nbsp;<?php echo $msg;?></div></td>
		<tr>
	</table>

	<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
		<tr>
			<td>
				<div id="conteiner">
					<div id="conteudo">
						<br>

		<form name="frm_posto" id="frm_posto" method="post" action="<?=$PHP_SELF?>" enctype="multipart/form-data">
		<input type="hidden" name="posto" value="<?=$posto?>" />
		<input type="hidden" name="fabrica" value="<?=$_GET['fabrica']?>" />
			<?php
			/*if(strlen($posto) > 0){
			?>
				<div style="background:#FFCCCC; width: 370px; color:white;font-size:18px; margin-left:30px; margin-bottom: 20px;">
					* <label style="width: 350px; text-align: left;"><?=ttext($a_labels, 'mensagem_topo', $cook_idioma);?></label>
				</div>
			<?php
			}*/
			?>

		<fieldset for='dados_cadastrais' class='colunas'>
		    <legend><?=ttext ($a_labels, "cadastro", $cook_idioma)?></legend>
            <p>
			    <label class="razaosocial"><?=ttext ($a_labels, "razaosocial", $cook_idioma)?> * </label>
				<input value="<?=$nome?>" type="text" name="nome" class="nome" rel="<?php echo $nome;?>" size="35" maxlength="150">
				<br />
                <label class="lnome_fantasia"><?=ttext($a_labels, 'fantasia', $cook_idioma);?> * </label>
				<input value="<?=$nome_fantasia?>" type="text" name="nome_fantasia" size="35" maxlength="50" rel="<?=$nome_fantasia?>" class="nome_fantasia">
			</p>
            <p>
				<label class="lcnpj"><?=ttext($a_labels, 'cnpj', $cook_idioma);?> * </label>
				<input value="<?=$cnpj?>" type="text" align="right" name="cnpj" id="cnpj" size="16" maxlength="14" readonly />
				<br />
				<label class="lie"><?=ttext($a_labels, 'insc_est', $cook_idioma);?> </label>
				<input value="<?=$ie?>" type="text" name="ie" id="ie" size="20" maxlength="14" rel="<?=$ie?>" class="ie"/>
			</p>
		</fieldset>

		<fieldset for='info_contato' class='colunas'>
		    <legend><?=ttext ($a_labels, "dadoscont", $cook_idioma)?></legend>
            <p>
				<label class="lcontato"><?=ttext ($a_labels, "contato", $cook_idioma)?> * </label>
				<input value="<?=$contato?>" type="text" name="contato" class="contato" size="35" maxlength="30" rel="<?php echo $contato;?>">
				<br />
				<label class="ltelefone"><?=ttext ($a_labels, "telefone", $cook_idioma)?> * </label>
				<input value="<?=$telefone?>" type="text" name="telefone" class="telefone" rel="<?php echo $telefone;?>" size="15" maxlength="14" />
			</p>
            <p>
				<label class="lemail"><?=ttext ($a_labels, "email", $cook_idioma)?> * </label>
				<input value="<?=$email?>" type="text" name="email" id="email" class="email" size="35" maxlength="50" rel="<?php echo $email;?>" <?	echo $readonly;//Adicionada regra para o posto alterar o e-mail caso esteja errado na tbl_posto	?> />
				<input type="hidden" name="email_antigo" id="email_antigo" value="<?=$email?>">
                <br />
				<label><?=ttext ($a_labels, "fax", $cook_idioma)?></label>
				<input value="<?=$fax?>" type="text" name="fax" id="fax" class="fax" size="15" maxlength="14" rel="<?php echo $fax;?>" />
			</p>
		</fieldset>

        <fieldset for="endereco" class='colunas'>
			<legend><?=ttext ($a_labels, "endereco", $cook_idioma)?></legend>
            <p style="width: 840px;">
				<label class="lcep" style='text-transform:uppercase!important'><?=ttext ($a_labels, "cep", $cook_idioma)?> * </label>
				<input value="<?=$cep?>" type="text" name="cep" id="cep" class="cep" size="15" maxlength="10" rel="<?php echo $cep;?>" /><br/>
                <span style="padding-left: 110px; color: #A9A8A8;"><?=ttext ($a_labels, "cep_busca", $cook_idioma)?></span>
            </p>
			<p>
				<label class="lendereco"><?=ttext ($a_labels, "rua", $cook_idioma)?> * </label>
				<input value="<?=$endereco?>" type="text" name="endereco" id="endereco" class="endereco" size="35" maxlength="50" rel="<?php echo $endereco;?>" />
                <br />
				<label class="lnumero"><?=ttext ($a_labels, "numero", $cook_idioma)?> * </label>
				<input value="<?=$numero?>" type="text" name="numero" id="numero" class="numero" size="35" maxlength="10" rel="<?php echo $numero;?>" align="right" <?=$readonly?> />
                <br />
				<label><?=ttext ($a_labels, "complemento", $cook_idioma)?></label>
				<input value="<?=$complemento?>" type="text" name="complemento" id="complemento" class="complemento" size="35" maxlength="20" rel="<?php echo $complemento;?>" />
			</p>

			<p>
				<label class="lbairro"><?=ttext ($a_labels, "bairro", $cook_idioma)?> * </label>
				<input value="<?=$bairro?>" type="text" name="bairro" id="bairro" class="bairro" size="35" maxlength="40" rel="<?php echo $bairro;?>" />
                <br />
				<label class="lcidade"><?=ttext ($a_labels, "cidade", $cook_idioma)?> * </label>
				<input value="<?=$cidade?>" type="text" name="cidade" id="cidade" class="cidade" size="25" maxlength="30" rel="<?php echo $cidade;?>" readonly="readonly" />
						<input type="hidden" name="cidade_antigo" id="cidade_antigo" value="<?=$cidade?>">
				<br />
				<label class="lestado"><?=ttext ($a_labels, "estado", $cook_idioma)?> * </label>
				<select name="estado" id="estado" class="estado" rel="<?php echo $estado;?>">
					<option value=""></option>
<?
    foreach ($estados as $sigla=>$nome_estado) {
    	echo "\t\t\t\t\t<option value='$sigla'";
    	if ($sigla == $estado) echo " selected";
    	echo ">$nome_estado</option>\n";
    }
?>				</select>
			</p>
        </fieldset>

		<?php
		$outros_sistema = 'N';

		if (!empty($info_sistema_1) && !empty($info_marca_1) && !empty($info_vantagem_1)) {
			$outros_sistema = 'S';
		}

		if (!empty($info_sistema_2) && !empty($info_marca_2) && !empty($info_vantagem_2)) {
			$outros_sistema = 'S';
		}

		if (!empty($info_sistema_3) && !empty($info_marca_3) && !empty($info_vantagem_3)) {
			$outros_sistema = 'S';
		}

		?>

		 <fieldset for="endereco" class='colunas'>
			<legend><?=ttext ($a_labels, "informacao", $cook_idioma)?></legend>
			<div style="margin-left: 10px;">
				<table border='0' width='820px' style="margin-bottom: 10px;">
					<tr>
						<td width="600px" id="fabrica_marcas_label" style="color: #535252;">
							<?=ttext ($a_labels, "informacoes_sistema", $cook_idioma)?>
							&nbsp;&nbsp;
							<input type="radio" id="s_outro_sis" name="outros_sistema" value="S" onChange="mostraOutrosSis('s')"
							<?php
							$display = 'none';
							if ($outros_sistema == "S") {
								echo ' checked="checked" ';
								$display = 'block';
							}
							?>
							/> Sim
							<input type="radio" id="n_outro_sis" name="outros_sistema" value="N" onChange="mostraOutrosSis('n')"
							<?php
							if ($outros_sistema == "N") {
								echo ' checked="checked" ';
								$display = 'none';
							}
							?>
							/> Não
						</td>
					</tr>
				</table>
				<div id="outros_sistemas" style="display: <?php echo $display ?>;">
				<span id="label_informacoes_sistem_extra" style="font: bold 12px Arial,Helvetica,sans-serif; color: #535252; margin-left: -9px;">
					<?=ttext ($a_labels, "informacoes_sistema_extra", $cook_idioma)?>
				</span><br/>
				<table border='0' width='820px'>
					<tr>
						<td width="200px" id="fabrica_marcas_label">
							<label style="width: 220px; text-align: left; margin-left: -6px;" id="label_sistema"><?=ttext ($a_labels, "label_sistema", $cook_idioma)?></label><br>
							<input type="text" name="inf_sistema1" id="inf_sistema1" size="35" value="<?php echo $info_sistema_1;?>"  class="inf_sistemas">
						</td>
						<td width="200px" id="fabrica_marcas_label" style="padding-left: 20px;">
							<label style="width: 220px; text-align: left; margin-left: -6px;" id="label_marca"><?=ttext ($a_labels, "label_marca", $cook_idioma)?></label><br>
							<input type="text" name="inf_marca1" id="inf_marca1" size="35" value="<?php echo $info_marca_1;?>" class="inf_sistemas">
						</td>
						<td width="200px" id="fabrica_marcas_label" style="padding-left: 20px;">
							<label style="width: 220px; text-align: left; margin-left: -6px;" id="label_vantagem"><?=ttext ($a_labels, "label_vantagem", $cook_idioma)?></label><br>
							<input type="text" name="inf_vantagem1" id="inf_vantagem1" size="35" maxlength="250" value="<?php echo $info_vantagem_1;?>" class="inf_sistemas">
						</td>
					</tr>
				</table>

				<table border='0' width='820px'>
					<tr>
						<td width="200px" id="fabrica_marcas_label">
							<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_sistema", $cook_idioma)?></label><br>
							<input type="text" name="inf_sistema2" id="inf_sistema2" size="35" value="<?php echo $info_sistema_2;?>" class="inf_sistemas">
						</td>

						<td width="200px" id="fabrica_marcas_label" style="padding-left: 20px;">
							<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_marca", $cook_idioma)?></label><br>
							<input type="text" name="inf_marca2" id="inf_marca2" size="35" value="<?php echo $info_marca_2;?>" class="inf_sistemas">
						</td>
						<td width="200px" id="fabrica_marcas_label" style="padding-left: 20px;">
							<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_vantagem", $cook_idioma)?></label><br>
							<input type="text" name="inf_vantagem2" id="inf_vantagem2" size="35" maxlength="250" value="<?php echo $info_vantagem_2;?>" class="inf_sistemas">
						</td>
					</tr>
				</table>

				<table border='0' width='820px'>
					<tr>
						<td width="200px" id="fabrica_marcas_label">
							<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_sistema", $cook_idioma)?></label><br>
							<input type="text" name="inf_sistema3" id="inf_sistema3" size="35" value="<?php echo $info_sistema_3;?>" class="inf_sistemas">
						</td>
						<td width="200px" id="fabrica_marcas_label" style="padding-left: 20px;">
							<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_marca", $cook_idioma)?></label><br>
							<input type="text" name="inf_marca3" id="inf_marca3" size="35" value="<?php echo $info_marca_3;?>" class="inf_sistemas">
						</td>
						<td width="200px" id="fabrica_marcas_label" style="padding-left: 20px;">
							<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_vantagem", $cook_idioma)?></label><br>
							<input type="text" name="inf_vantagem3" id="inf_vantagem3" size="35" maxlength="250" value="<?php echo $info_vantagem_3;?>" class="inf_sistemas">
						</td>
					</tr>
				</table>
				</div>

				<table border='0' width='820px'>
					<tr>
						<td width="597px" id="fabrica_marcas_label">
							<label style="width: 580px; text-align: left; margin-left: -6px;" id="melhor_sis"><?=ttext ($a_labels, "melhor_sis", $cook_idioma)?> * </label><br/>
							<textarea name="melhor_sistema" id="melhor_sistema_txt" style="width: 835px; height: 50px;"><?=$melhor_sistema?></textarea>
						<?php/*	<label style="width: 220px; text-align: left; margin-left: -6px;"><?=ttext ($a_labels, "label_comentario", $cook_idioma)?></label><br>
							<input type="text" name="inf_comentario" id="inf_comentario" size="115" value="<?php echo $informacao_comentario;?>"> */ ?>
						</td>
					</tr>
				</table>
			</div>
		 </fieldset>
		<?php
			if(strpos($linhas, 'OUTRAS') !== false){
				$linha_6_obs = substr($linhas, strrpos($linhas,',')+1);
				$bloqueia_campo_6 = "";
			}else{
				$linha_6_obs = "";
				$bloqueia_campo_6 = "readonly='true'";
			}

			// HD-1867132
			if($_GET['fabrica'] == 114 AND $_GET['linha'] == 811){

			 	$bloqueia_checkbox = " onclick='return false;'";

			 	if(strlen($linha_6_obs) > 0){
					$linha_6 .= "checado";
		 			$linha_6_obs .= ", MASTER CHEF";
		 		}else{
		 			$linha_6 .= "checado";
		 			$linha_6_obs .= "MASTER CHEF";
		 		}
			}

			if($_GET['fabrica'] == 114 AND $_GET['linha'] == 710){

				$bloqueia_checkbox = " onclick='return false;'";

				if(strlen($linha_6_obs) > 0){
					$linha_6 .= "checado";
		 			$linha_6_obs .= ", COMPRESSORES MICHELIN";
		 		}else{
		 			$linha_6 .= "checado";
		 			$linha_6_obs .= "COMPRESSORES MICHELIN";
		 		}
			}
			// fim HD-1867132

		?>

		<fieldset for="linhas" id="info_ad_linhas" class="colunas">
			<legend class="llinhas"><?=ttext ($a_labels, "linhas", $cook_idioma)?> * </legend>
			<div style="margin-left: 10px; float: left;">
				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo" name="linha_1" id="linha_1" value='BRANCA' <?if(strlen($linha_1) > 0 or strpos($linhas, 'BRANCA') !== false) echo "checked";?> />
					</div>
					<div id="linha_label_1" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "branca", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo"  name="linha_2" id="linha_2" value='MARROM' <?if(strlen($linha_2) > 0 or strpos($linhas, 'MARROM') !== false) echo "checked";?> />
					</div>
					<div id="linha_label_2" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "marron", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo"  name="linha_3" id="linha_3" value='ELETROPORTATEIS' <?if(strlen($linha_3) > 0 or strpos($linhas, 'ELETRO') !== false) echo "checked";?> />
					</div>
					<div id="linha_label_3" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "eletro", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo"  name="linha_4" id="linha_4" value='INFORMATICA' <?if(strlen($linha_4) > 0 or strpos($linhas, 'INFORM') !== false) echo "checked";?> />
					</div>
					<div id="linha_label_4" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "informatica", $cook_idioma)?>.
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo"  name="linha_5" id="linha_5" value='FERRAMENTAS' <?if(strlen($linha_5) > 0 or strpos($linhas, 'FERRAM') !== false) echo "checked";?> />
					</div>
					<div id="linha_label_5" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "ferramentas", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo"  name="linha_7" id="linha_7" value="LAVADORAS DE ALTA PRESSAO"
						<?php
						if (strlen($linha_7) > 0 or strpos($linhas, "LAVADORAS DE ALTA PRESSAO") !== false) {
							echo ' checked ';
						}
						?> />
					</div>
					<div id="linha_label_7" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "lavadoras", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="concordo"  name="linha_6" class="linha_6" id="linha_6" value='OUTRAS' <?if(strlen($linha_6) > 0 or strpos($linhas, 'OUTRAS') !== false) echo "checked".$bloqueia_checkbox; ?>/>
					</div>
					<div id="linha_label_6" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "outras", $cook_idioma)?> &nbsp;&mdash;&nbsp; <?=ttext ($a_labels, "quais", $cook_idioma)?> &nbsp;&nbsp;
						<INPUT TYPE="text" NAME="linha_6_obs" class="linha_6_obs" id="linha_6_obs" <?php echo $bloqueia_campo_6;?> size='70' value='<?php echo $linha_6_obs;?>'>
					</div>
				</div>
			</div>
		</fieldset>

        <fieldset for='info_adicionais' id="info_ad" class='colunas'>
			<legend><?=ttext ($a_labels, "informacao", $cook_idioma)?></legend>
			<div style="margin-left: 10px;">
			<p>
				<label class="lfuncionarios"><?=ttext ($a_labels, "qtdefunc", $cook_idioma)?> * </label>
				<input value="<?=$funcionarios?>" align="right" type="text" name="funcionarios" class="funcionarios" size='10' maxlength="3" style="margin-left: 7px;" />

				<label><?=ttext ($a_labels, "qtdeos", $cook_idioma)?></label>
				<input value="<?=$oss?>" align="right" type="text" name="oss" size='10' class="oss" maxlength="5" style="margin-left: 7px;" />
				<br />

			</p>

			<p>
				<label class="lfabricantes"><?=ttext ($a_labels, "marcasauth", $cook_idioma)?> * </label>
				<textarea name="fabricantes" size='50' class='marcas' id="fabricantes" style="margin-left: 7px;"><?=$fabricantes?></textarea>
				<label class="latende_cidade_proxima"><?=ttext ($a_labels, "atendeperto", $cook_idioma)?> * </label>
				<textarea name="atende_cidade_proxima" size='50' id="atende_cidade_proxima" style="margin-left: 7px;"><?=$atende_cidade_proxima?></textarea>
				<?php /*<label><?=ttext ($a_labels, "marcas_nao", $cook_idioma)?>
				<?=ttext ($a_labels, "porque", $cook_idioma)?></label>
				<textarea name="marca_nao_autorizada" size='50' class='marcas' style="margin-left: 7px;"><?=$marca_nao_autorizada?></textarea>
				<br>
				<label><?=ttext ($a_labels, "melhor_sis", $cook_idioma)?></label>
				<textarea name="melhor_sistema" size='50' style="margin-left: 7px;"><?=$melhor_sistema?></textarea>*/ ?>
			<br />&nbsp;
			</p>
			</div>
		</fieldset>

        <fieldset for='info_adicionais' id="info_ad_fabricas" class='colunas'>
			<legend id="fabrica_marcas_label_topo"><?=ttext ($a_labels, "opcoes_fabricas", $cook_idioma)?> * </legend>
			<div style="margin-left: 10px; width: 820px;">
			<table border='0' width='820px'>
				<tr>
					<td width='850px'>
						<div style="float: left;">
							<div style="float: left;">
								<input type="checkbox" class="marca_todas_fabricas" id="marca_todas_fabricas" name="marca_todas_fabricas" style="border: none;">
							</div>
							<div style="float: left; margin-left: 5px; line-height: 22px; color: #535252; font-weight: bold;">
								<?=ttext ($a_labels, "Marcar todas", $cook_idioma);?></b></label>
							</div>
						</div>
					</td>
				</tr>
			</table>

			 <table border='0' width='820px'>
				<tr>
					<?php
					//BUSCA AS FABRICAS ATIVAS
					//$sql_fabrica = "SELECT fabrica,nome,ativo_fabrica FROM tbl_fabrica where ativo_fabrica = 't' order by nome";
					//$res_fabrica = pg_query($con,$sql_fabrica);

					$sql = "
						SELECT
						fabrica,
						ativo_fabrica,
						nome,
						'f' AS fabrica_marca
						FROM tbl_fabrica
						where ativo_fabrica = 't'
						AND fabrica NOT IN ($not_in_fabricas)

						UNION

						SELECT
						tbl_marca.marca,
						tbl_marca.ativo,
						tbl_marca.nome,
						'm' AS fabrica_marca
						FROM tbl_fabrica
						JOIN tbl_marca
						ON tbl_fabrica.fabrica = tbl_marca.fabrica
						AND tbl_marca.ativo = 't'
						where tbl_fabrica.ativo_fabrica = 't'
						AND marca NOT IN ($not_in_marcas)

						ORDER BY nome
						";

					$res = pg_query($con, $sql);
					$rows = pg_num_rows($res);
					if ($rows > 0){
						$b = '0';
?>
                    <input type="hidden" name="total_fab" id="total_fab" value="<?php echo $rows; ?>" />
<?
						for ($a=0; $a < $rows; $a++){
							$id_fabrica   = pg_fetch_result($res, $a, 'fabrica');
							$nome_fabrica = ucwords(strtolower(trim(pg_fetch_result($res, $a, 'nome'))));
							$fabrica_marca = pg_fetch_result($res, $a, 'fabrica_marca');

							$$fabrica_nome = false;
							if ($id_fabrica == 114){
								 if (isset($_POST['cobimex']) and $_POST['cobimex'] == 'ok'){
								 	$checked_cobimex = "CHECKED";
								 	$cobimex = true;
								}
							}elseif ($id_fabrica == 122){
								if (isset($_POST['wurth']) and $_POST['wurth'] == 'ok'){
									$checked_wurth = "CHECKED";
									$wurth = true;
								}
							}elseif ($id_fabrica == 123){
								if (isset($_POST['positec']) and $_POST['positec'] == 'ok'){
									$checked_positec = "CHECKED";
									$positec = true;
								}
							}elseif ($id_fabrica == $_GET['fabrica']){
								if (isset($_POST[$fabrica_nome]) and $_POST[$fabrica_nome] == 'ok'){
									$checked_saintgobain = "CHECKED";
									$$fabrica_nome = true;
								}
							}else{
								$checked_cobimex = '';
								$cobimex = false;
							}

							$literals = array (
												"Delonghi" => "DeLonghi",
												"Dwt" => "DWT",
												"Ibbl" => "IBBL",
												"Nks" => "NKS"
											);

							if (array_key_exists($nome_fabrica, $literals)) {
								$nome_fabrica = $literals["$nome_fabrica"];
							}

							if($b == 4){
								$b = '0';
							?>
								<tr>
							<?php
							}
							$b++;
							$fabrica_credenciadas = str_replace('{','',$fabrica_credenciadas);
							$fabrica_credenciadas = str_replace('}','',$fabrica_credenciadas);
							$cod_fabrica = explode(",",$fabrica_credenciadas);

							foreach($cod_fabrica as $variavel_fabrica) {

								if($variavel_fabrica == $id_fabrica){
									$check_fabrica = "checked onclick='return false;'";
								}
							}

							?>
							<td width="25%" id="fabrica_marcas_label" style="padding-right: 5px; padding-bottom: 5px;">
								<div style="float: left; width: 205px;">
									<div style="float: left;">
										<?php if (($cobimex and $id_fabrica == 114) or ($wurth and $id_fabrica == 122) or ($positec and $id_fabrica == 123) or ($$fabrica_nome and !empty($_GET['fabrica']))): ?>
											<input type="checkbox" class="todas_fabricas" checked onclick='return false;' >
											<input type="hidden" name="fabrica_<?php echo $a;?>" value="<?php echo $fabrica_marca . ':' . $id_fabrica;?>" >
										<?php else: ?>
											<input type="checkbox" class="todas_fabricas" <?php echo $check_fabrica; ?> name="fabrica_<?php echo $a;?>" id="todas_fabricas" value="<?php echo $fabrica_marca . ':' . $id_fabrica;?>">
										<?php endif ?>
									</div>
									<div style="float: left; margin-left: 5px; line-height: 22px; color: #535252; font-weight: bold;">
										<?php echo  $nome_fabrica; ?>
									</div>
								</div>
							</td>
							<?php
							if($b == 4){
							?>
								</tr>
							<?php
							}
							$check_fabrica = '';
							$id_fabrica ="";
						}
					}

					//BUSCA AS MARCAS ATIVAS
					$sql_marcas = "SELECT
										tbl_marca.marca,
										tbl_marca.ativo,
										tbl_marca.nome
									FROM tbl_fabrica
									JOIN tbl_marca
									ON tbl_fabrica.fabrica = tbl_marca.fabrica
									AND tbl_marca.ativo = 't'
									where tbl_fabrica.ativo_fabrica = 't'
									order by tbl_marca.nome";
					//$res_marca = pg_query($con,$sql_marcas);
					$ttl_fabrica = pg_num_rows($res_marca);
					if(pg_num_rows($res_marca) > 0){
						$b = '0';
						for($a=0;$a < $ttl_fabrica;$a++){
							$id_marca	 = pg_fetch_result($res_marca,$a,'marca');
							$nome_marca	 = pg_fetch_result($res_marca,$a,'nome');

							if($b == 4){
								$b = '0';
							?>
								<tr>
							<?php
							}
							$b++;

							$marcas_credenciadas = str_replace('{','',$marcas_credenciadas);
							$marcas_credenciadas = str_replace('}','',$marcas_credenciadas);
							$cod_marca = explode(",",$marcas_credenciadas);
							foreach($cod_marca as $variavel_marca) {
								if($variavel_marca == $id_marca){
									$check_marca = "checked";
								}
							}
							?>
							<td width="25%" id="fabrica_marcas_label">
								<input type="checkbox" class="todas_fabricas" <?php echo $check_marca;?> name="fabrica_<?php echo $id_marca;?>" id="todas_marcas" value="<?php echo $id_marca;?>">&nbsp;<?php echo '<strong>' , $nome_marca , '</strong>';?>
							</td>
							<?php
							if($b == 4){
							?>
								</tr>
							<?php
							}
							$check_marca	= '';
							$id_marca		="";
						}
					}
					?>
				</tr>
			</table>
			<?php
				$check_outras_fabricas = '';
				$disabled_outras_fabricas = '';
				if(strlen($marca_ser_autorizada) > 0){
					$check_outras_fabricas = "checked";
					$disabled_outras_fabricas = '';
				}else{
					$disabled_outras_fabricas = 'disabled="true"';
				}
			?>
			<table border='0' cellspacing='0' cellpadding='0' width='100%;'>
				<tr>
					<td>
						<div style="float: left; width: 840px; margin-top: 20px;">
							<div style="float: left;">
								<input type="checkbox" name="outras_fabricas" <?php echo $check_outras_fabricas;?> id="outras_fabricas" value="outras" style="border: none;">
							</div>
							<div style="float: left; margin-left: 5px; line-height: 22px; color: #535252; font-weight: bold;">
								<?=ttext ($a_labels, "outras", $cook_idioma)?></label>
							</div>
						</div>
						<textarea name="opcao_outras_fabricas" id="opcao_outras_fabricas" style="width: 800px;" <?php echo $disabled_outras_fabricas;?>><?php echo $marca_ser_autorizada;?></textarea>
					</td>
				</tr>
			</table>
			</div>
		</fieldset>

		<fieldset for="atender" id="info_atender" class="colunas">
			<legend class="latender"><?=ttext ($a_labels, "posto_tem_condicões_de_atender", $cook_idioma)?> * </legend>

			<div style="margin-left: 10px; float: left;">
				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="atender" name="condicao_1" id="condicao_1" value='VISITA TECNICA' <?if($visita_tecnica == 't') echo "checked";?> />
					</div>
					<div id="linha_label_1" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "Visita_tecnica", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="atender"  name="condicao_2" id="condicao_2" value='ATENDE CONSUMIDOR - BALCÃO' <?if($atende_consumidor_balcao == 't') echo "checked";?> />
					</div>
					<div id="linha_label_2" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "Atende_consumidor_-_balcão", $cook_idioma)?>
					</div>
				</div>

				<div style="float: left; width: 800px;">
					<div style="float: left;">
						<input type="checkbox" class="atender"  name="condicao_3" id="condicao_3" value='ATENDE REVENDAS' <?if($atende_revendas == 't') echo "checked";?> />
					</div>
					<div id="linha_label_3" style="float: left; margin-left: 10px; line-height: 22px; font-size: 12px; color: #535252; font-weight: bold;">
						<?=ttext ($a_labels, "Atende_revendas", $cook_idioma)?>
					</div>
				</div>


			</div>
		</fieldset>

		<fieldset for='info_adicionais' class='colunas'>
			<legend><?=ttext ($a_labels, "fotos&desc", $cook_idioma)?></legend>
			<p>
				<label style="width: 680px; margin-left: -65px;"><?=ttext ($a_labels, "3fotos", $cook_idioma)?></label>
				<div style="width: 840px; float: left; margin: 30px 0; ">
				<div style="width: 800px; float: left;">
					<label class="lfachada" style="margin-left: -16px;"><?=ttext ($a_labels, "fachada", $cook_idioma)?> * </label>
					<input type='file' name='arquivo1' id='arquivo1' class="arquivo1" accept="jpeg|jpg" size='1' />
					<?
					if (is_numeric($posto))
					$img_path = $caminho_path.$cnpj;
					$img_caminho = $caminho_imagem.$cnpj;
					//echo  dirname(preg_replace("&admin(_cliente)?/&", '', $_SERVER['PHP_SELF'])) ."/nf_digitalizada");
					if (file_exists($img_caminho."_1.jpg")) $img_ext = "jpg";
					if (file_exists($img_caminho."_1.png")) $img_ext = "png";
					if (file_exists($img_caminho."_1.gif")) $img_ext = "gif";
					if ($img_ext) {
						$img_src = $img_path."_1.$img_ext";
						echo '<input type="hidden" id="old_foto1" value="1" />';
					?>
									<img src="<?php echo $img_src;?>" />
					<?}
					unset($img_ext);
?>
				</div>
				<div style="width: 800px; float:left; margin-top: 25px;">
					<label class="lrecepcao" style="margin-left: -16px;"><?=ttext ($a_labels, "recepção", $cook_idioma)?> * </label>
					<input type='file' name='arquivo2' id='arquivo2' class="arquivo2" accept="jpeg|jpg" size='1' />
					<?
					if (file_exists($img_caminho."_2.jpg")) $img_ext = "jpg";
					if (file_exists($img_caminho."_2.png")) $img_ext = "png";
					if (file_exists($img_caminho."_2.gif")) $img_ext = "gif";
					if ($img_ext) {
						$img_src = $img_path."_2.$img_ext";
						echo '<input type="hidden" id="old_foto2" value="1" />';
					?>
									<img src="<?php echo $img_src;?>" />
					<?}
					unset($img_ext);
					?>
                </div>
				<div style="width: 800px; float:left; margin-top: 25px;">
					<label class="loficina" style="margin-left: -16px;"><?=ttext ($a_labels, "oficina", $cook_idioma)?> * </label>
					<input type='file' name='arquivo3' id='arquivo3' class="arquivo3" size='1' accept="jpeg|jpg" />
					<?
					if (file_exists($img_caminho."_3.jpg")) $img_ext = "jpg";
					if (file_exists($img_caminho."_3.png")) $img_ext = "png";
					if (file_exists($img_caminho."_3.gif")) $img_ext = "gif";
					if ($img_ext) {
						$img_src = $img_path."_3.$img_ext";
						echo '<input type="hidden" id="old_foto3" value="1" />';
					?>
									<img src="<?php echo $img_src;?>" />
					<?}?>
                </div>
				</div>
				<div style="background-color:;width:800px;float:left; margin-top: 20px; margin-left: 13px;">
					<h3 style="color: #535252; font-weight: bold;"><?=ttext ($a_labels, "DescAuth", $cook_idioma)?></h3>
					<textarea name="descricao" id="campo_descricao" style="width: 825px; height: 150px;"><?=$observacao?></textarea>
				</div>
			</p>
        </fieldset>
        <p class='center'>
			<center>
				<input type="hidden" name='btn_acao' value='gravar'>
				<input type="button" class="input_gravar" value="<?=ttext ($a_labels, "gravar", $cook_idioma)?>" onClick="verifica_submit();" />
			</center>
		</p>
	</form>
<!--[if IE]>
<script type='text/javascript'>setupZoom();</script>
<![endif]-->
	</div>
</div>
</td></tr></table>
<?}

include 'rodape_wordpress.php';

