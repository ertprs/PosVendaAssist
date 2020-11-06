<?php

header("Content-Type:text/html; charset=utf-8");

$caminho_imagem = dirname(__FILE__) . '/../autocredenciamento/fotos/';
$caminho_path	= dirname($_SERVER['PHP_SELF']) . '/../autocredenciamento/fotos/';

include dirname(__FILE__) . '/../dbconfig.php';
include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../class_resize.php';
include dirname(__FILE__) . '/../helpdesk/mlg_funciones.php';
include dirname(__FILE__) . '/../trad_site/fn_ttext.php';

$not_in_fabricas = '108, 93, 47, 89, 63, 92, 8, 14, 66, 5, 43, 61, 77, 76, 110, 78, 107, 112, 113, 75, 111, 109, 10';
$not_in_marcas = '131, 189, 178, 177, 184';

$debug = ($_COOKIE['debug'][0] == 't');

/* 10/12/2009 MLG - Não funcionava a gravação de informação porque agora o $msg_erro é um array,
					portanto a conferência tem que ser feita com count($msg_erro) e não com strlen()
*/

/*  Tradução inicial, depois, no 'else' para mudar de formulário, tem outro array para ele  */
$a_labels = array(
	"autocredenciamento"	=> array(
		"pt-br" => "Verificação de Cadastro",
		"es"	=> "Comprobación de Registro",
		"en"	=> "Account Verification"
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
		"pt-br" => "A Telecontrol agradece o seu cadastro! <br> Em breve estaremos enviando o seu banner no endereço cadastrado",
		"es"	=> "¡Telecontrol agradece su alta!<br />En breve enviaremos el cartel a la dirección indicada.",
		"en"	=> "Telecontrol thanks you for signing up!<br />You will receive your banner in a few days."
	),
	"tc_ja_cadastrado"=>array(
		"pt-br" => "Já recebemos o pedido do seu banner. Aguarde que em breve estaremos efetuando o envio.",
		"es"	=> "Hemos recibido su solicitud del cartel, en unos días haremos el envío.",
		"en"	=> "We already received your banner order. You will receive your order soon!"
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
		"pt-br" => '<p class="texto_informativo_informacao_font_cabecalho"><b>O que é e para que serve esta verificação de cadastro?</b></p><br>
		<p class="texto_informativo_informacao_font_conteudo">É um novo recurso que desenvolvemos para auxiliar nossos parceiros. Tem por finalidade, contribuir para que os postos tenham em seu estabelecimento um banner que exiba para seus clientes as marcas que ele atende.</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>1º Passo -</b> O Posto Autorizado faz o cadastro no site da <b>Telecontrol</b> detalhando as informações importantes para que a confecção do banner seja feita.</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>2º Passo -</b> A <b>Telecontrol</b> irá coletar os dados preenchidos pelos postos e irá produzir os banners.</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>3º Passo -</b> O posto irá receber GRATUITAMENTE o banner em seu estabelecimento conforme o endereço cadastrado.</p><br>
		<p class="texto_informativo_informacao_font_cabecalho"><strong style="font-size: 17px;"> Não perca tempo, cadastre-se já!</strong></p><br>',
		"es" => '<p class="texto_informativo_informacao_font_cabecalho"><b>¿Qué es y para qué sirve esta comprobación de Registro?</b></p><br>
		<p class="texto_informativo_informacao_font_conteudo">Es un nuevo recurso que hemos desarrollado para ayudar a nuestros socios. Su finalidad es que todos los Servicios Técnicos tengan en su establecimiento un cartel, o <i>banner</i>, que exhiba a sus clietnes las marcas a las que atiende.</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>1º Paso -</b> O Posto Autorizado faz o cadastro no site da <b>Telecontrol</b> detalhando as informações importantes para que a confecção do banner seja feita.</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>2º Paso -</b> A <b>Telecontrol</b> irá coletar os dados preenchidos pelos postos e irá produzir os banners.</p><br>
		<p class="texto_informativo_informacao_font_conteudo"><b>3º Paso -</b> O posto irá receber GRATUITAMENTE o banner em seu estabelecimento conforme o endereço cadastrado.</p><br>
		<p class="texto_informativo_informacao_font_cabecalho"><strong style="font-size: 17px;"> Não perca tempo, cadastre-se já!</strong></p><br>',
	),
);

$estados = array("AC" => "Acre",		"AL" => "Alagoas",	"AM" => "Amazonas",			"AP" => "Amapá",
				 "BA" => "Bahia",		"CE" => "Ceará",	"DF" => "Distrito Federal",	"ES" => "Espírito Santo",
				 "GO" => "Goiás",		"MA" => "Maranhão",	"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
				 "MT" => "Mato Grosso", "PA" => "Pará",		"PB" => "Paraíba",			"PE" => "Pernambuco",
				 "PI" => "Piauí",		"PR" => "Paraná",	"RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte",
				 "RO" => "Rondônia",	"RR" => "Roraima",	"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
				 "SE" => "Sergipe",		"SP" => "São Paulo","TO" => "Tocantins");

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

//  Função para conferir cada campo do $_POST, devolve 'false' ou o que colocar como último argumento
if (!function_exists('check_post_field')) {
	function check_post_field($fieldname, $returns = false) {
		if (!isset($_POST[$fieldname])) return $returns;
		$data = anti_injection($_POST[$fieldname]);
	// 	echo "<p><b>$fieldname</b>: $data</p>\n";
		return (strlen($data)==0) ? $returns : $data;
	}
}

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

/**
 * Ação de cadastrar, ou seja, quando o usuário preenche o CNPJ na primeira tela.
 * Se o posto existe, recebe os dados do posto.
 */
if(strtolower($btn_acao) == 'cadastrar') {

	$verifica_cnpj  = preg_replace("/\D/", '', $cnpj);

	if (checaCPF($verifica_cnpj,false)===false) $msg_erro = ttext($a_labels, "erro_CNPJ");

	if(strlen($msg_erro) == 0) {

		$sql = "SELECT *,
						(LENGTH(marca_ser_autorizada) > 3 AND 
						 LENGTH(outras_fabricas)>3) AS preencheu_fabricas
				  FROM tbl_posto_alteracao
				 WHERE cnpj = '$verifica_cnpj' ";
				$resA = pg_query($con,$sql);
		if(pg_num_rows($resA) > 0){
	
			$posto_ja_cadastrado   = (pg_fetch_result($resA, 0, 'preencheu_fabricas') == 't');
			$posto                 = utf8_encode(pg_fetch_result($resA,0,posto));
			$nome                  = utf8_encode(pg_fetch_result($resA,0,razao_social));
			$nome_fantasia         = utf8_encode(pg_fetch_result($resA,0,nome_fantasia));
			$cnpj                  = utf8_encode(pg_fetch_result($resA,0,cnpj));
			$endereco              = utf8_encode(pg_fetch_result($resA,0,endereco));
			$numero                = utf8_encode(pg_fetch_result($resA,0,numero));
			$complemento           = utf8_encode(pg_fetch_result($resA,0,complemento));
			$bairro                = utf8_encode(pg_fetch_result($resA,0,bairro));
			$cidade                = utf8_encode(pg_fetch_result($resA,0,cidade));
			$estado                = utf8_encode(pg_fetch_result($resA,0,estado));
			$cep                   = utf8_encode(pg_fetch_result($resA,0,cep));
			$email                 = utf8_encode(pg_fetch_result($resA,0,email));
			$telefone              = utf8_encode(pg_fetch_result($resA,0,fone));
			$fax                   = utf8_encode(pg_fetch_result($resA,0,fax));
			$contato               = utf8_encode(pg_fetch_result($resA,0,contato));
			$ie                    = utf8_encode(pg_fetch_result($resA,0,ie));

			$aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento

			list($info_sistema_1, $info_sistema_2, $info_sistema_3)  = explode('|', $informacao_sistema);
			list($info_marca_1,   $info_marca_2,   $info_marca_3)    = explode('|', $informacao_marca);
			list($info_vantagem_1,$info_vantagem_2,$info_vantagem_3) = explode('|', $informacao_vantagem);

		} else {

			$sql = "SELECT *
					FROM tbl_posto
					LEFT JOIN tbl_posto_extra USING(posto)
					WHERE cnpj = '$verifica_cnpj'
					ORDER BY posto DESC LIMIT 1";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$posto                 = utf8_encode(pg_fetch_result($res,0,'posto'));
				$nome                  = utf8_encode(pg_fetch_result($res,0,'nome'));
				$nome_fantasia         = utf8_encode(pg_fetch_result($res,0,'nome_fantasia'));
				$cnpj                  = utf8_encode(pg_fetch_result($res,0,'cnpj'));
				$endereco              = utf8_encode(pg_fetch_result($res,0,'endereco'));
				$numero                = utf8_encode(pg_fetch_result($res,0,'numero'));
				$complemento           = utf8_encode(pg_fetch_result($res,0,'complemento'));
				$bairro                = utf8_encode(pg_fetch_result($res,0,'bairro'));
				$cidade                = utf8_encode(pg_fetch_result($res,0,'cidade'));
				$estado                = utf8_encode(pg_fetch_result($res,0,'estado'));
				$cep                   = utf8_encode(pg_fetch_result($res,0,'cep'));
				$email                 = utf8_encode(pg_fetch_result($res,0,'email'));
				$telefone              = utf8_encode(pg_fetch_result($res,0,'fone'));
				$fax                   = utf8_encode(pg_fetch_result($res,0,'fax'));
				$contato               = utf8_encode(pg_fetch_result($res,0,'contato'));
				$ie                    = utf8_encode(pg_fetch_result($res,0,'ie'));
				$pais                  = utf8_encode(pg_fetch_result($res,0,'pais'));
				$descricao             = utf8_encode(pg_fetch_result($res,0,'descricao'));

				$aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento

			} else {

				$aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento

			}
		}
	} else {
		$msg = '<label class="erro_campos_obrigatorios">' . $msg_erro . '</label>';
		$msg_erro = '';
	}
}

if($btn_acao == 'gravar') {

//  Cada erro vai num item do array. Depois, na hora de mostrar, faz um 'implode'

	$msg_erro = array();
	$posto	  = trim($_POST['posto']);

	$fabricas_credenciadas = pg_array_quote(getPost('fabricantes'));

//  Campos não obrigatórios
	$aux_fax                            = utf8_decode(check_post_field("fax",""));
	$aux_observacao                     = utf8_decode(check_post_field('observacoes', ''));
	// Agora são obrigatórios
	// $aux_outras_fabricas_credenciadas   = utf8_decode(check_post_field('outras_fabricas_credenciadas', ''));
	// $aux_outras_fabricas_especializadas = utf8_decode(check_post_field('outras_fabricas_especializadas', ''));
	
// 	Campos obrigatórios... quando é para dar um INSERT!

	if (checaCPF(anti_injection($_POST['cnpj']),false)	        === false) $msg_erro[] = "Preencha/Verifique o campo CNPJ";
	if(($aux_nome          = check_post_field("nome"))          === false) $msg_erro[] = "Preencha o campo Razão Social";
	if(($aux_cep           = check_post_field('cep'))           === false) $msg_erro[] = "Preencha o campo CEP";
	if(($aux_endereco      = check_post_field('endereco'))      === false) $msg_erro[] = "Preencha o campo Endereço";
	if(($aux_numero        = check_post_field('numero'))        === false) $msg_erro[] = "Preencha o campo Número";
	if(($aux_bairro        = check_post_field('bairro'))        === false) $msg_erro[] = "Preencha o campo Bairro";
	if(($aux_cidade        = check_post_field('cidade'))        === false) $msg_erro[] = "Preencha o campo Cidade";
	if(($aux_estado        = check_post_field('estado'))        === false) $msg_erro[] = "Preencha o campo Estado";
	if(($aux_email         = check_post_field('email'))         === false) $msg_erro[] = "Preencha o campo E-Mail";
	if(($aux_telefone      = check_post_field('telefone'))      === false) $msg_erro[] = "Preencha o campo Telefone";
	if(($aux_contato       = check_post_field("contato"))       === false) $msg_erro[] = "Preencha o campo Contato";
	if(($aux_nome_fantasia = check_post_field("nome_fantasia")) === false) $msg_erro[] = "Preencha o campo Nome Fantasia";
	if(($aux_ie            = check_post_field("ie"))            === false) $msg_erro[] = "Preencha o campo IE";
	if(($aux_outras_fabricas_credenciadas   = check_post_field("outras_fabricas_credenciadas"))   === false) $msg_erro[] = "Preencha o campo 'Outras Marcas Credenciadas'";
	if(($aux_outras_fabricas_especializadas = check_post_field("outras_fabricas_especializadas")) === false) $msg_erro[] = "Preencha o campo 'Especializado nas Marcas'";

	//Validações
	if(!is_email($aux_email)) $msg_erro[] = "O e-mail digitado ($aux_email) não é válido.";

	if (checaCPF(anti_injection($_POST['cnpj']),false)!==false)
		$aux_cnpj = preg_replace('/\D/','',$_POST['cnpj']);

	$aux_nome          = utf8_decode($aux_nome);
	$aux_cep           = utf8_decode($aux_cep);
	$aux_endereco      = utf8_decode($aux_endereco);
	$aux_numero        = utf8_decode($aux_numero);
	$aux_bairro        = utf8_decode($aux_bairro);
	$aux_cidade        = utf8_decode($aux_cidade);
	$aux_estado        = utf8_decode($aux_estado);
	$aux_complemento   = utf8_decode($aux_complemento);
	$aux_email         = utf8_decode($aux_email);
	$aux_telefone      = utf8_decode($aux_telefone);
	$aux_contato       = utf8_decode($aux_contato);
	$aux_nome_fantasia = utf8_decode($aux_nome_fantasia);
	$aux_ie            = utf8_decode($aux_ie);
	$descricao         = utf8_decode($descricao);

//  Prepara os valores a serem inseridos ou atualizados:
	$aux_nome          = pg_quote($aux_nome);
	$aux_cnpj          = pg_quote($aux_cnpj, true);
	$aux_ie            = pg_quote($aux_ie);
	$aux_endereco      = pg_quote($aux_endereco);
	$aux_numero        = pg_quote($aux_numero);
	$aux_complemento   = pg_quote($aux_complemento);
	$aux_bairro        = pg_quote($aux_bairro);
	$aux_cidade        = pg_quote($aux_cidade);
	$aux_cep           = pg_quote($aux_cep);
	$aux_telefone      = pg_quote($aux_telefone);
	$aux_contato       = pg_quote($aux_contato);
	$aux_estado        = pg_quote($aux_estado);
	$aux_email         = pg_quote($aux_email);
	$aux_fax           = pg_quote($aux_fax);
	$aux_nome_fantasia = pg_quote($aux_nome_fantasia);
	
	$verifica_posto = 0;

	$aux_cep = str_replace('-', '', $aux_cep);

	$sql_posto = "SELECT posto FROM tbl_posto WHERE cnpj = $aux_cnpj";
	$res_posto = pg_query($con, $sql_posto);
	if (pg_num_rows($res_posto) == 0) {
		$posto = '0';
	} else {
		$posto = pg_fetch_result($res_posto, 0, 'posto');
	}

	$sql_cpnj = "SELECT posto FROM tbl_posto_alteracao WHERE cnpj = $aux_cnpj ";
	$res_cnpj = pg_query($con, $sql_cpnj);
	if (pg_num_rows($res_cnpj) > 0) {
		$verifica_posto = 1;
	}
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
					observacao 	    ,
					fabrica_credenciada,
					marca_ser_autorizada,
					outras_fabricas,
					banner,
					valida_banner
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
					'$aux_observacao',
					'$fabricas_credenciadas',
					'$outras_fabricas_credenciadas',
					'$outras_fabricas_especializadas',
					true,
					'I'
				)";
		$res = pg_query ($con,$sql);
		$msg_erro_insert = pg_last_error($con);
		
		if(strlen($msg_erro_insert) > 0){
			$msg_erro[] = "Erro ao gravar os dados no sistema.<br>Tente novamente."; // $sql - $msg_erro_insert";
			pg_rollback();
		}

	} else {

		if (count($msg_erro) == 0) {
			$sql = "UPDATE tbl_posto_alteracao SET
							razao_social         = $aux_nome,
							ie                   = $aux_ie,
							endereco             = $aux_endereco,
							numero               = $aux_numero,
							complemento          = $aux_complemento,
							bairro               = $aux_bairro,
							cep                  = $aux_cep,
							cidade               = $aux_cidade,
							estado               = $aux_estado,
							email                = $aux_email,
							fone                 = $aux_telefone,
							fax                  = $aux_fax,
							contato              = $aux_contato,
							nome_fantasia        = $aux_nome_fantasia,
							observacao           = '$aux_observacao',
							fabrica_credenciada  = '$fabricas_credenciadas',
							marca_ser_autorizada = '$outras_fabricas_credenciadas',
							outras_fabricas      = '$outras_fabricas_especializadas',
							banner 		     = true, 
							valida_banner        ='U',
							data_alterado        = current_timestamp
						WHERE cnpj = $aux_cnpj";
			$res = pg_query($con, $sql);

			if (!is_resource($res)) {
				$msg_erro[] = pg_last_error($con);
				pg_rollback();
			}
		}

	}

	if(count($msg_erro) == 0){
		pg_commit();
		$msg_ok = "OK";

		$email_origem     =  "suporte.fabricantes@telecontrol.com.br";
		// $email_destino =  "gabriel.silveira@telecontrol.com.br, rodrigo.perina@telecontrol.com.br, ronaldo@telecontrol.com.br, gabriel.rolon@telecontrol.com.br";
		$email_destino    =  "ronaldo@telecontrol.com.br";
		$assunto          =  "PEDIDO DE BANNER - " . $nome ;
		$body_top         =  "--Message-Boundary\n";
		$body_top        .=  "Content-type: text/html; charset                                                                  = utf-8\n";
		$body_top        .=  "Content-transfer-encoding: 7BIT\n";
		$body_top        .=  "Content-description: Mail message body\n\n";
		$corpo            =  "Foi feito um pedido para recebimento de banner por um posto autorizado:";
		$corpo           .=  "<br><br>Posto: <b>$nome</b>";
		$corpo           .=  "<br>CNPJ: <b>$cnpj</b>";
		$corpo           .=  "<br>Cidade: <b>$cidade</b>";
		$corpo           .=  "<br>Estado: <b>$estado</b>";
		$corpo           .=  '</b>';
		$corpo           .=  "<br><br>_______________________________________________\n";
		$corpo           .=  "<br><br>Telecontrol\n";
		$corpo           .=  "<br>www.telecontrol.com.br\n";
		// 27-02-2013 - MLG - Ronaldo pediu por chat deshabilitar o envio de email.
		//mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " );	

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

if ($btn_acao and !empty($aux_cnpj)) {
	$texto_titulo_pagina = "Dados Cadastrais";
}else{
	$texto_titulo_pagina = "Verificação de Cadastro";
}

echo '<div class="titulo_tela">
			<br />
			<h1><a href="javascript:void(0)" style="cursor:point;">'.$texto_titulo_pagina.'</a></h1>
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
<table width="948" class="barra_topo">
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

/**
 * Se o posto ja tiver cadastrado, vai exibir esta imagem para ele.
 */
if ($posto_ja_cadastrado) {
	?>
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

	<table width="948" class="barra_topo">
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
						<div class="email_sucesso" style="text-align: center;">
							<?=ttext ($a_labels, "tc_ja_cadastrado", $cook_idioma)?>&nbsp;
						</div>
						
					</div>
				</div>
			</td>
		</tr>
	</table><br/>
		<script>window.onload = conta();</script>

	<?

	include 'rodape_wordpress.php';
	exit;	
}

?>

<script src="../js/jquery.js" type="text/javascript"></script>
<script src="../js/jquery.autocomplete.js" type="text/javascript"></script>
<script type='text/javascript' src='../js/jquery.maskedinput.js'></script>
<script type="text/javascript" src="../js/jquery.numeric.js"></script>
<script type="text/javascript">

function verifica_submit() {

	var verifica      = 0;

	var nome          = $(".nome").val();
	var nome_fantasia = $(".nome_fantasia").val();
	var cpnj          = $("#cnpj").val();
	var ie            = $("#ie").val();
	var contato       = $(".contato").val();
	var telefone      = $(".telefone").val();
	var email         = $(".email").val();
	var cep           = $(".cep").val();
	var endereco      = $(".endereco").val();
	var numero        = $(".numero").val();
	var bairro        = $(".bairro").val();
	var cidade        = $(".cidade").val();
	var estado        = $(".estado").val();
	var funcionarios  = $(".funcionarios").val();
	var fabricantes   = $("#fabricantes").val();
	var atende_cidade_proxima = $("#atende_cidade_proxima").val();

	var linha_1 = $("#linha_1").attr("checked");
	var linha_2 = $("#linha_2").attr("checked");
	var linha_3 = $("#linha_3").attr("checked");
	var linha_4 = $("#linha_4").attr("checked");
	var linha_5 = $("#linha_5").attr("checked");
	var linha_6 = $("#linha_6").attr("checked");
	var linha_7 = $("#linha_7").attr("checked");

	var arquivo1 = $("#arquivo1").val();
	var arquivo2 = $("#arquivo2").val();
	var arquivo3 = $("#arquivo3").val();

	var total_fab = $("#total_fab").val();
	var outras_fabricas      = $("#outras_fabricas").attr("checked");
	var outras_fabricas_txt  = $("#opcao_outras_fabricas").val();
	var outras_fabricas_cred = $('#outras_fabricas_credenciadas').val();
	var outras_fabricas_esp  = $('#outras_fabricas_especializadas').val();

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

	if (outras_fabricas_cred.length < 3) {
		$("#outras_fabricas_credenciadas").css('border-color','#C6322B');
		$("#outras_fabricas_credenciadas").css('border-width','1px');
		$("#loutras_fabricas_credenciadas").css('color','#C6322B');
		verifica ='1';
	} else {
		$("#outras_fabricas_credenciadas").css('border-color','#CCC');
		$("#outras_fabricas_credenciadas").css('border-width','1px');
		$("#loutras_fabricas_credenciadas").css('color','#535252');
	}

	if (outras_fabricas_esp.length < 3) {
		$("#outras_fabricas_especializadas").css('border-color','#C6322B');
		$("#outras_fabricas_especializadas").css('border-width','1px');
		$("#loutras_fabricas_especializadas").css('color','#C6322B');
		verifica ='1';
	} else {
		$("#outras_fabricas_especializadas").css('border-color','#CCC');
		$("#outras_fabricas_especializadas").css('border-width','1px');
		$("#loutras_fabricas_especializadas").css('color','#535252');
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
	$("input[@name=cnpj]").maskedinput("99.999.999/9999-99");
	$("input[@name=telefone]").maskedinput("(99) 9999-9999");
	$("input[@name=fax]").maskedinput("(99) 9999-9999");
	$("input[@name=cep]").maskedinput("99999-999");

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
	position: relative;
	vertical-align: middle;
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

div#company_logos{
	position:relative;
	width:800px;
	display:table;
	margin:auto;
}

div#company_logos ul {
	margin:auto;
}

div#company_logos ul li {
	float: left;
	border: 1px solid lightgray;
	width: 170px;
	height: 75px;
	line-height: 75px; /* Para fazer funcionar o vertical align... */
	list-style-type: none;
	margin: 1px 1ex 1ex auto;
	padding:         10px;
	text-align: center;
}

div#company_logos img {
	
	width: auto;
	height: auto;
	position: static;
	margin:auto;

}

/* div#company_logos li p {color:#fff;margin:0.2em 0;} */

</style>


<!--[if IE]>
	<script type='text/javascript' src='js/FancyZoom.js'></script>
    <script type='text/javascript' src='js/FancyZoomHTML.js'></script>

	<style type="text/css">
	#fullcontent {
		font: 12px Arial,Helvetica,sans-serif;
	}
	label.linhas{
		font: normal normal 0.9em arial, helvetica, sans-serif;
		color: #3D3D3D;
		display: inline;
		width: auto;
		padding-left: 1.5em;
		text-transform: none;
	}
	</style>
<![endif]-->

<?
/* Refiz usando jQuery...
<script language='javascript' src='/ajax.js'></script>
<script language='javascript' src='/ajax_cep.js'></script>
*/
	if(strlen($msg_erro) > 0){	?>
		<center>
			<div style='width:300px;margin:0 auto;text-align:center;padding:3px'>
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
				<?php if ($_GET['cobimex'] == 's'): ?>
					<input type="hidden" name="cobimex" value="ok">
				<?php endif ?>
				<input type="button"  id='btn_acao' class='input_gravar' style="cursor: default;" value='<?=ttext ($a_labels, "Cadastrar", $cook_idioma)?>' onClick="vericaSubmitCNPJ()" />
			</div>
		</div>

	</form>

	<div style="float: left; text-align: center; width: 950px; margin: 30px 0 40px 0;">
		<div class="barra_topo" style="width: 950px; margin-left: 20px;">
			<div id="mensagem_envio">&nbsp;<?php echo $msg;?></div>
		</div>
		<div class="fieldset_informacao">
			<?=ttext ($a_labels, "label_texto_informacao", $cook_idioma)?>
		</div>
	</div>

	<br />
<?

}else{

/**
 * Verifica se o CNPJ passado existe no sistema. se existir. vai liberar para cadastro.
 * Caso não exista, vai avisar ao posto que ele pode fazer o auto-credenciamento e enviar para a tela do auto-credenciamento
 */
$sql = "SELECT tbl_posto.posto, tbl_posto.nome as posto_razao_social,tbl_posto_fabrica.nome_fantasia as posto_nome_fantasia FROM tbl_posto JOIN tbl_posto_fabrica using(posto) WHERE tbl_posto.cnpj = '{$aux_cnpj}' ";
$res = pg_query($con,$sql);

if (pg_num_rows($res)==0) {
	?>
	<table style="border:solid 1px #CCCCCC;width: 948px!important;height:300px;" class="caixa_conteudo">
		<tr>
			<td>

					
				<div class="fieldset_informacao">
					
					<p class="texto_informativo_informacao_font_cabecalho">
						<b>
							Cadastro não encontrado
						</b>
					</p>					
					<br>
					<p class="texto_informativo_informacao_font_conteudo">
						A pesquisa do seu CNPJ não retornou um cadastro já existente no nosso sistema.
					</p>
					<p class="texto_informativo_informacao_font_conteudo">
						Faça já seu auto-credenciamento e faça parte do grande grupo de usuários do sistema TELECONTROL.
					</p>
					<p class="texto_informativo_informacao_font_conteudo">
						Após o auto-credenciamento, volte nesta tela para fazer o cadastro do seu pedido do banner exclusivo
					</p>
					<p class="texto_informativo_informacao_font_conteudo" style="margin:auto;text-align:center;">
						<input type="button" value="Fazer Auto-Credenciamento" onclick="window.open('autocredenciamento.php')">
					</p>
				</div>


			</td>
		</tr>
	</table>

	<?
	include 'rodape_wordpress.php';
	exit;
}

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
		"pt-br" => "Enviar Formulario",
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
		<table style="text-align: right;width:948px;">
			<tr>
				<td>
					*Campos obrigat&oacute;rios.
				</td>
			<tr>
		</table>
	</div>

	<table style="width:948px!important" class="barra_topo">
		<tr>
			<td><div id="mensagem_envio" style="width: 90%!important;text-align: center">&nbsp;<?php echo $msg;?></div></td>
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
				<label class="lie"><?=ttext($a_labels, 'insc_est', $cook_idioma);?> * </label>
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
				<input value="<?=$email?>" type="text" name="email" id="email" class="email" size="35" maxlength="50" rel="<?php echo $email;?>" />
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
                <span class='hint_campo'><?=ttext ($a_labels, "cep_busca", $cook_idioma)?></span>
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

		<fieldset class="colunas" for="fabricas_credenciadas">
			<legend>Fabricantes Credenciados</legend>
			<p>&nbsp;</p>
				<?
				
				$sql = "SELECT tbl_posto.posto, tbl_posto.nome as posto_razao_social,tbl_posto_fabrica.nome_fantasia as posto_nome_fantasia FROM tbl_posto JOIN tbl_posto_fabrica using(posto) WHERE tbl_posto.cnpj = '{$aux_cnpj}' ";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0) {

					$posto = pg_fetch_result($res, 0, 0);

					$sql = "SELECT 	tbl_posto_fabrica.fabrica,
									tbl_fabrica.logo,
									tbl_fabrica.nome 
							FROM tbl_posto_fabrica 
							JOIN tbl_fabrica USING(fabrica) 
							JOIN tbl_posto USING(posto) 
							WHERE tbl_posto_fabrica.posto=$posto 
							AND tbl_fabrica.ativo_fabrica IS TRUE 
							AND tbl_fabrica.fabrica NOT IN (10,46) 
							AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO') 
							ORDER BY tbl_fabrica.nome ";

					$resFabricas = pg_query($con,$sql);
					?>
			<div id="company_logos">
				<ul>

					<?php
					include '../fn_logoResize.php';
					foreach (pg_fetch_all($resFabricas) as $key => $value) {

						$attrImg = logoSetSize('../logos/' . $value['logo'], 150, 70, 'css'); ?>
					<li>
						<img src="../logos/<?php echo $value['logo']?>" style="<?=$attrImg?>" alt="">
						<input type="hidden" name="fabricantes[]" value='<?php echo $value["fabrica"]?>'>
					</li>
						
			<?php } ?>
				</ul>
			</div>
					
					<?php
				}
			?>
		</fieldset>

		<?php
			if (isset($posto_ja_cadastrado) and count($_POST)<4) { // Posto já cadastrou, falta algum estes campos obrigatórios
				$ofc = $_POST['outras_fabricas_credenciadas'];
				$ome = $_POST['outras_fabricas_especializadas'];
				$ofc = pg_fetch_result($resA, 0, 'marca_ser_autorizada');
				$ome = pg_fetch_result($resA, 0, 'outras_fabricas');
			}
		?>

		<!-- Outras marcas credenciadas -->
		<fieldset class="colunas">
			<legend id='loutras_fabricas_credenciadas'>Outras Marcas Credenciadas *</legend>
			<div style="float: left; width: 800px;">
				<table border='0' cellspacing='0' cellpadding='0' width='100%;'>
					<tr>
						<td style='margin:auto;text-align:center'>
							<textarea name="outras_fabricas_credenciadas" id="outras_fabricas_credenciadas" style="width: 700px;height:50px"><?php echo $ofc?></textarea>
						</td>
					</tr>
				</table>
				<div>
					<ul class='hint_campo'>
						<li>Separe com vírgulas " , "</li>
						<li>Caso não seja credenciado de outras marcas, simplesmente digite <strong>'NÃO'</strong> ou <strong>'NÃO SOU CREDENCIADO'</strong></li>
					</ul>
				</div>
			</div>
		</fieldset>

		<!-- Marcas Especializadas -->
		<fieldset class="colunas">
			<legend id='loutras_fabricas_especializadas'>Especializado nas Marcas *</legend>
			<div style="float: left; width: 800px;">
				<table border='0' cellspacing='0' cellpadding='0' width='100%;'>
					<tr>
						<td style='margin:auto;text-align:center'>
							<textarea name="outras_fabricas_especializadas" id="outras_fabricas_especializadas" style="width: 700px;height:50px"><?php echo $ome;?></textarea>
						</td>
					</tr>
				</table>
				<div>
					<ul class='hint_campo'>
						<li>Separe com vírgulas " , "</li>
						<li>Caso não seja especializado em outras marcas, simplesmente digite <strong>'NÃO'</strong> ou <strong>'NÃO SOU ESPECIALIZADO'</strong></li>
					</ul>
				</div>
			</div>
		</fieldset>

		<!-- Marcas Especializadas -->
		<fieldset class="colunas">
			<legend>Observações</legend>
			<div style="float: left; width: 800px;">
				<table border='0' cellspacing='0' cellpadding='0' width='100%;'>
					<tr>
						<td style='margin:auto;text-align:center'>
							<textarea name="observacoes" id="observacoes" style="width: 700px;height:50px"><?php echo $observacoes;?></textarea>
						</td>
					</tr>
				</table>
			</div>
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
<?}?>
	</div>
	<div class="blank_footer">&nbsp;</div>
</body>
</html>

