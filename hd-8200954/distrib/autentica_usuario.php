<?
include_once '../token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_posto         = $cookie_login['cook_posto'];
$cook_login_unico   = $cookie_login['cook_login_unico'];


add_cookie($cookie_login,"cook_posto"        ,$cook_posto);
add_cookie($cookie_login,"cook_login_unico"  ,$cook_login_unico);
set_cookie_login($token_cookie,$cookie_login);

// setcookie ("cook_posto"        ,$cook_posto);
// setcookie ("cook_login_unico"  ,$cook_login_unico);

if ($cook_posto_fabrica == 'deleted') {
	echo "<center><b>Seu computador está possivelmente infectado por vírus que atrabalha o correto funcionamento deste site. É um vírus que deleta os <i>cookies</i> que o site precisa para trabalhar.<p>Por favor, atualize seu anti-vírus ou entre em contato com o suporte técnico que lhe vendeu este computador.<p>Qualquer dúvida, peça para que seu técnico entre em contato com a TELECONTROL. (14) 3413-6588 ou suporte@telecontrol.com.br </b></center>";
	exit;
}

if (strlen ($cook_posto_fabrica) == 0 AND strlen($cook_login_unico) == 0 ) {
	header ("Location: http://www.telecontrol.com.br/");
	exit;
}

$sql = "SELECT fabrica, parametros_adicionais FROM tbl_fabrica WHERE parametros_adicionais ilike '%telecontrol_distrib%'";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	while($data = pg_fetch_object($res)){
		$json = json_decode($data->parametros_adicionais);
		if($json->telecontrol_distrib == "t"){
			$telecontrol_distrib .= $data->fabrica.",";
		}
	}
}

$telecontrol_distrib = substr($telecontrol_distrib, 0, strlen($telecontrol_distrib) - 1);

/*HD 48162*/
$fabricas = array($telecontrol_distrib);

#if (strlen ($cook_posto_fabrica) == 0 AND strlen($cook_login_unico) >  0 ) {
#	header ("Location: ..login_unico.php");
#	exit;
#}


if(strlen($cook_login_unico) > 0 AND $cook_login_unico <> 'temporario' and $cook_login_unico <> 'deleted'){
	$sql = "SELECT  login_unico,
					posto      ,
					nome       ,
					email      ,
					abre_os    ,
					item_os    ,
					fecha_os   ,
					compra_peca,
					extrato    ,
					master ,
					distrib_total
			FROM tbl_login_unico
			WHERE login_unico = $cook_login_unico;";
		$res = pg_exec ($con,$sql);
		$login_unico             = pg_result($res,0,login_unico);
		$login_unico_posto       = pg_result($res,0,posto);
		$login_unico_abre_os     = pg_result($res,0,abre_os);
		$login_unico_item_os     = pg_result($res,0,item_os);
		$login_unico_fecha_os    = pg_result($res,0,fecha_os);
		$login_unico_compra_peca = pg_result($res,0,compra_peca);
		$login_unico_extrato     = pg_result($res,0,extrato);
		$login_unico_master      = pg_result($res,0,master);
		$login_unico_nome        = pg_result($res,0,nome);
		$login_unico_email       = pg_result($res,0,email);
		$login_unico_distrib_total = pg_result($res,0,distrib_total);

		
		add_cookie($cookie_login,'cook_login_unico',$login_unico);
		add_cookie($cookie_login,'cook_posto',$login_unico_posto);
		set_cookie_login($token_cookie,$cookie_login);
		// setcookie(cook_login_unico,$login_unico);
		// setcookie(cook_posto,$login_unico_posto);
}


$sql = "SELECT	tbl_posto.posto                      ,
				tbl_posto.nome                       ,
				tbl_posto.cnpj                       ,
				tbl_posto.pais
	FROM  tbl_posto
	WHERE posto = $cook_posto";
$res = @pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
	header ("Location: index.php");
	exit;
}

$login_posto                       = trim (pg_result ($res,0,posto));
$login_nome                        = trim (pg_result ($res,0,nome));
$login_cnpj                        = trim (pg_result ($res,0,cnpj));
$login_pais                        = trim (pg_result ($res,0,pais));


setcookie('cook_sistema_lingua',"BR");
if($login_pais <> 'BR' and strlen($login_pais) == 2 and ( $login_fabrica == 20 or $login_fabrica ==14)) {
	$sistema_lingua = 'ES';
	$cook_idioma    = "es";
	setcookie ('cook_sistema_lingua',"ES");
	setcookie ('cook_idioma',"ES");
}else{
	$cook_idioma    = "pt-br";
	setcookie ('cook_idioma',"pt-br");
}
if($login_fabrica<>'20' and $login_fabrica <>'14'){
	$sistema_lingua = '';
	$login_pais     = '';
	$cook_idioma    = "pt-br";
	setcookie ('cook_idioma',"pt-br");
	setcookie ('cook_sistema_lingua',"");
}


#Adicionado pois o idioma estava vindo vazio e pegava o idioma com maior prioridade
if(strlen($cook_idioma) == 0){
	$cook_idioma    = "pt-br";
	setcookie ('cook_idioma',"pt-br");
}

if(!empty($login_unico)) {
		$sql= "SELECT postos
				FROM tbl_distrib_unico
				WHERE login_unico = $login_unico";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
				$login_distrib_postos = pg_fetch_result($res,0,'postos');
				$login_distrib_postos = preg_replace("/[{}]/","",$login_distrib_postos);
		}
		$login_distrib_postos = (!empty($login_distrib_postos)) ? $login_distrib_postos : $login_posto;
}

if (strlen ($login_distribuidor) == 0) $login_distribuidor = "null";
	// setcookie ('cook_login_posto'                      ,$login_posto);
	// setcookie ('cook_login_codigo_posto'               ,$login_codigo_posto);
	// setcookie ('cook_login_fabrica'                    ,$login_fabrica);

	add_cookie($cookie_login,'cook_login_posto'                      ,$login_posto);
	add_cookie($cookie_login,'cook_login_codigo_posto'               ,$login_codigo_posto);
	add_cookie($cookie_login,'cook_login_fabrica'                    ,$login_fabrica);	
	set_cookie_login($token_cookie,$cookie_login);
#	setcookie ('cook_login_cnpj'                       ,$login_cnpj);
#	setcookie ('cook_login_nome'                       ,$login_nome);
#	setcookie ('cook_login_fabrica_nome'               ,$login_fabrica_nome);
#	setcookie ('cook_login_pede_peca_garantia'         ,$login_pede_peca_garantia);
#	setcookie ('cook_login_tipo_posto'                 ,$login_tipo_posto);
#	setcookie ('cook_login_e_distribuidor'             ,$login_e_distribuidor);
#	setcookie ('cook_login_distribuidor'               ,$login_distribuidor);
#	setcookie ('cook_pedido_via_distribuidor'          ,$pedido_via_distribuidor);
#	setcookie ('cook_reembolso_peca_estoque'           ,$login_reembolso_peca_estoque);
#	setcookie ('cook_login_credenciamento'             ,$login_credenciamento);
#	setcookie ('cook_pedir_causa_defeito_os_item'      ,$pedir_causa_defeito_os_item);
#	setcookie ('cook_pedir_defeito_constatado_os_item' ,$pedir_defeito_constatado_os_item);
#	setcookie ('cook_pedir_solucao_os_item'            ,$pedir_solucao_os_item);

###########################################
### Monta variáveis para ajudar LOG     ###
###########################################
$var_post = "";
foreach($_POST as $key => $val) {
    $var_post .= "[" . $key . "]=" . $val . "; ";
}
foreach($_GET as $key => $val) {
    $var_get .= "[" . $key . "]=" . $val . "; ";
}


$sql = "/* PROGRAMA $PHP_SELF  # FABRICA $login_fabrica  #  POSTO $login_posto  # POST-FORM $var_post # GET-DATA $var_get  */";
$resX = @pg_exec ($con,$sql);


###########################################
### AVISO E BLOQUEIO DE PEDIDO FATURADO ###
###########################################
$login_bloqueio_pedido = $_COOKIE["cook_bloqueio_pedido"];

$login_bloqueio_pedido = "";


//include "/var/www/assist/www/log_inicio.php";
include "../log_inicio.php";


/* Criado por Fabio - 04/09/2008 */
include_once "../includes/traducao.php";

if (!function_exists("traduz")) {
	function traduz($inputText,$con,$cook_idioma_pesquisa,$x_parametros = null){

		global $msg_traducao;
		global $PHP_SELF;

		$cook_idioma_pesquisa = strtolower($cook_idioma_pesquisa);

		if (strlen($cook_idioma_pesquisa)==0){
			$cook_idioma_pesquisa = 'pt-br';
		}

		$mensagem = $msg_traducao[$cook_idioma_pesquisa][$inputText];

		if (strlen($mensagem)==0){
			$mensagem = $msg_traducao['pt-br'][$inputText];
		}

		if (strlen($mensagem)==0){
			$mensagem = $msg_traducao['es'][$inputText];
		}

		if (strlen($mensagem)==0){
			$mensagem = $msg_traducao['en-us'][$inputText];
		}

		if (strlen($mensagem)==0){
			$mensagem = $inputText;

			$sql = "INSERT INTO tmp_traducao_falha (msg_id,idioma,programa)
					VALUES ('$inputText', '$cook_idioma_pesquisa','$PHP_SELF')";
			$x_res = @pg_exec($con,$sql);
		}

		if ($x_parametros){
			if (!is_array($x_parametros)){
				$x_parametros = explode(",",$x_parametros);
			}
			while ( list($x_variavel,$x_valor) = each($x_parametros)){
				$mensagem = preg_replace('/%/',$x_valor,$mensagem,1);
			}
		}

		return $mensagem;
	}
}


/* Criado por Paulo e alterado por Fabio (abaixo) - 03-09-2008 */
if (!function_exists("fecho")) {
	function fecho($inputText,$con,$cook_idioma,$x_parametros = null){
		echo traduz($inputText,$con,$cook_idioma,$x_parametros);
	};
}

?>
