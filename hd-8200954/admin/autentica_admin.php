<?
/**
 * HD 156726 - Augusto - 2009 10 01
 * Quando este include é utilizado por uma página que deve retornar um
 * resultado via Ajax, ele não deve dar exit e parar a execução.
 * Como identificamos uma página Ajax?
 * Este include verifica se existe uma constante "ADMIN_INCLUDE_AJAX",
 * se a constante existir, ele não dará exit.
 * Repare que a página do Ajax deverá tratar se o usuário possui ou não
 * permissao de acesso através do output buffer.
 * Exemplo de uso: admin/email_admin.php
 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include_once '../fn_traducao.php';

date_default_timezone_set('Etc/GMT+3');
$cookie_login = get_cookie_login($_COOKIE['sess']);

global $admin_consulta_os;

$cook_fabrica = $cookie_login['cook_fabrica'];
$cook_admin   = $cookie_login['cook_admin'];
$cook_master  = $cookie_login['cook_master'];
$cook_empresa = $cookie_login['cook_empresa'];
$cook_avatar  = $cookie_login['cook_avatar'];

/**
 *  Registra IP do usuario para posterior pesquisa WHOIS e PING
 */
$hostname = trim(`hostname`);
$login_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
	$_SERVER['HTTP_X_FORWARDED_FOR'] :
	($_SERVER['REMOTE_ADDR'] ? :
	'LOCALHOST');

if ($cook_fabrica == 158) {
    if (check_session_duration($_COOKIE['sess'], '15 minutes')) {
        update_session_timestamp($_COOKIE['sess']);
    } else {
		header ("Location: logout.php");
		exit;
    }
}

// $cook_fabrica = $_COOKIE['cook_fabrica'];
// $cook_admin   = $_COOKIE['cook_admin'];
// $cook_master  = $_COOKIE['cook_master'];
// $cook_empresa = $_COOKIE['cook_empresa'];

if (strlen($cook_empresa) > 0) {
	include 'autentica_admin2.php';
	//exit;
} else {

	$cache_bypass=md5(time());

	//SE FOR CLIENTE O&M - SISTEMA "http://www.telecontrol.com.br/assist/admin_cliente/"
	//PARA NENHUM ENTRAR NA AREA DO OUTRO
	if (!empty($cookie_login['cook_cliente_admin'])) {
		foreach ($cookie_login as $k => $v) {
			$cookie_login = remove_cookie($cookie_login,$k);
			// setcookie($k, '');
		}
		set_cookie_login($token_cookie,$cookie_login);

		setcookie('sess', '');
		unset($_COOKIE['sess']);

		header("Location: http://www.telecontrol.com.br/");
		echo 'Redirecionando para http://www.telecontrol.com.br/';
		die;
	}

	/*
	$cook_empresa = $_COOKIE['cook_empresa'];
	if ((strlen ($cook_admin) == 0 )or (strlen($cook_empresa) == 0)) {
		header ("Location: ../index.php");
		exit;
	}

	if ((strlen($cook_master) > 0) or (strlen($cook_empresa) > 0)) {
		include 'autentica_admin2.php';
		//exit;
	}else{
	*/
	if (strlen ($cook_admin) == 0) {
		header ("Location: logout.php");
		exit;
	}

	if (strlen ($cook_fabrica) == 0) {
		header ("Location: logout.php");
		exit;
	}

	if (strlen($cook_master) > 0) {
		include 'autentica_admin2.php';
		//exit;
	}else{

		//if ($ip <> "201.0.9.216" and $cook_fabrica == 3) {
		//	header ("Location: ../index.php");
		//	exit;
		//}


		$sql_verifica_consulta_os = "SELECT admin FROM tbl_admin WHERE consulta_os IS TRUE AND admin = $cook_admin AND tbl_admin.fabrica = $cook_fabrica;";
		$res_verifica_consulta_os = pg_query($sql_verifica_consulta_os);
		if (pg_num_rows($res_verifica_consulta_os)) {
			$admin_consulta_os = true;

			$pagina_habilitada = false;
			$paginas_habilitadas = array('os_press','os_consulta_lite','menu_callcenter');

			foreach($paginas_habilitadas as $pag_habilitadas){
				if (strpos($PHP_SELF,$pag_habilitadas) !== false){
					$pagina_habilitada = true;
				}
			}

			if ($pagina_habilitada == false) {
				header ("Location: menu_callcenter.php");
			}

		}

	    $sql = "SELECT
						tbl_admin.admin                             ,
						tbl_admin.nome_completo                     ,
						tbl_admin.fabrica                           ,
						tbl_admin.login                             ,
						tbl_admin.senha                             ,
						tbl_admin.privilegios                       ,
						tbl_admin.admin_sap                         ,
						tbl_admin.live_help,
						tbl_admin.email,
						tbl_admin.callcenter_supervisor,
						tbl_admin.help_desk_supervisor              ,
						tbl_admin.cliente_admin                     ,
						tbl_admin.intervensor						,
						tbl_admin.responsavel_postos				,
						tbl_admin.parametros_adicionais AS admin_pa ,
						tbl_fabrica.nome as fabrica_nome            ,
						tbl_fabrica.logo AS fabrica_logo            ,
						tbl_fabrica.site AS fabrica_site            ,
						tbl_fabrica.multimarca                      ,
						tbl_fabrica.acrescimo_tabela_base           ,
						tbl_fabrica.acrescimo_financeiro            ,
						tbl_fabrica.pedir_causa_defeito_os_item     ,
						tbl_fabrica.pedir_defeito_constatado_os_item,
						tbl_fabrica.pedir_solucao_os_item			,
						tbl_admin.pais 								,
						tbl_l10n.nome                               ,
						tbl_fabrica.parametros_adicionais AS fabrica_pa
				FROM    tbl_admin
				JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
				LEFT JOIN tbl_l10n ON tbl_l10n.l10n = tbl_admin.l10n
				WHERE   tbl_admin.admin   = $cook_admin
				AND     tbl_admin.fabrica = $cook_fabrica 
				AND		tbl_admin.ativo
				AND		tbl_fabrica.ativo_fabrica";
		$res = @pg_exec ($con,$sql);

		if (@pg_numrows ($res) == 0) {
			header ("Location: ../index.php");
			exit;
		}


		global $login_admin;
		global $login_cliente_admin;
		global $login_login;
		global $login_nome_completo;
		global $login_fabrica;
		global $login_privilegios;
		global $login_supervisor;
		global $login_fabrica_nome;
		global $login_fabrica_logo;
		global $login_fabrica_site;
		global $login_live_help;
		global $login_email;
		global $login_pais;
		global $multimarca;
		global $acrescimo_tabela_base;
		global $admin_sap_login;
		global $acrescimo_financeiro;
		global $pedir_causa_defeito_os_item;
		global $pedir_defeito_constatado_os_item;
		global $pedir_solucao_os_item;
		global $admin_e_promotor_wanke; //HD 685194
		global $admin_interventor;
		global $admin_parametros_adicionais;
		global $login_callcenter_supervisor;
		global $login_sac_telecontrol;
		global $login_responsavel_postos;
		global $cook_idioma;
		global $fabrica_pa;
		global $login_responsavel_ressarcimento;

		$login_admin                       = trim (pg_result ($res,0,admin));
		$admin_interventor		           = trim (pg_result ($res,0,intervensor));
		$login_cliente_admin               = trim (pg_result ($res,0,cliente_admin));
		$login_login                       = trim (pg_result ($res,0,login));
		$login_nome_completo               = trim (pg_result ($res,0,nome_completo));
		$login_fabrica                     = trim (pg_result ($res,0,fabrica));
		$login_privilegios                 = trim (pg_result ($res,0,privilegios));
		$login_callcenter_supervisor       = pg_fetch_result($res,0,'callcenter_supervisor');
		$login_supervisor                  = (pg_result ($res,0,help_desk_supervisor) == 't');
		$login_fabrica_nome                = trim (pg_result ($res,0,fabrica_nome));
		$login_fabrica_logo                = trim (pg_result ($res,0,fabrica_logo));
		$login_fabrica_site                = trim (pg_result ($res,0,fabrica_site));
		$login_pais                		   = trim (pg_result ($res,0,pais));
		$login_responsavel_postos  		   = trim (pg_result ($res,0,'responsavel_postos'));
		$multimarca                        = trim (pg_result ($res,0,multimarca));
		$acrescimo_tabela_base             = trim (pg_result ($res,0,acrescimo_tabela_base));
		$acrescimo_financeiro              = trim (pg_result ($res,0,acrescimo_financeiro));
		$pedir_causa_defeito_os_item       = trim (pg_result ($res,0,pedir_causa_defeito_os_item));
		$admin_sap_login				   = pg_fetch_result($res,0,admin_sap);
		$pedir_defeito_constatado_os_item  = trim (pg_result ($res,0,pedir_defeito_constatado_os_item));
		$pedir_solucao_os_item             = trim (pg_result ($res,0,pedir_solucao_os_item));
		$admin_e_promotor_wanke			   = (strpos($login_privilegios,'promotor')!==false); //HD 685194
		$login_live_help 				   = pg_fetch_result($res,0,'live_help');
		$login_email 					   = pg_fetch_result($res, 0, 'email');
		$admin_parametros_adicionais	   = json_decode(pg_fetch_result($res,0,admin_pa), true);
		$cook_idioma		               = trim (pg_result ($res,0,nome));
		$login_responsavel_ressarcimento   = $admin_parametros_adicionais['libera_ressarcimento'];
		$fabrica_parametros_adicionais	   = json_decode(trim (pg_result ($res, 0, fabrica_pa)), true);

		$real = 'R$ ';
		$pais = (empty($fabrica_parametros_adicionais['pais'])) ? "BR" : $fabrica_parametros_adicionais['pais'];
		
		if ($pais == 'AR') {
			$real = 'ARS$ ';
		} else if ($pais == 'CO') {
			$real = 'COP$ ';
		} else if ($pais == 'PE') {
			$real = 'PEN$ ';
		}

		$login_sac_telecontrol = false;
		if ($admin_parametros_adicionais['sacTelecontrol'] == true) {
			$login_sac_telecontrol = true;
		}

		if (strlen ($admin_privilegios) > 0) {

			$admin_autorizado = 0;
			$array_privilegios = explode (",",$login_privilegios);

			foreach($array_privilegios as $privilegios){
				if(strpos($admin_privilegios,$privilegios) !== false){
					$admin_autorizado = 1;
				}
			}

			if (strpos ($login_privilegios,"*") !== false) {
				$admin_autorizado = 1;
			}

			if ($admin_autorizado == 0) {				
				define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

				if (count($array_privilegios) == 0) { /*Sem nenhum privilégio*/
					$aux_url_shadowbox = BI_BACK . "logout.php";
				} else {
					$auxiliar = str_replace("_", "", $array_privilegios[0]);

					switch ($auxiliar) {
						case "gerencia":
							$aux_url_shadowbox = BI_BACK . "menu_gerencia.php";
						break;

						case "callcenter":
							$aux_url_shadowbox = BI_BACK . "menu_callcenter.php";
						break;

						case "cadastro":
							$aux_url_shadowbox = BI_BACK . "menu_cadastro.php";
						break;

						case "cadastros":
							$aux_url_shadowbox = BI_BACK . "menu_cadastro.php";
						break;

						case "infotecnica":
							$aux_url_shadowbox = BI_BACK . "menu_tecnica.php";
						break;

						case "financeiro":
							$aux_url_shadowbox = BI_BACK . "menu_financeiro.php";
						break;

						case "auditoria":
							$aux_url_shadowbox = BI_BACK . "menu_auditoria.php";
						break;

						default:
							$aux_url_shadowbox = BI_BACK . "logout.php";
						break;
					}
				}
				include_once "shadowbox_usuario_sem_acesso.php";
			}
		}

		if (array_key_exists('restricao_ip', $admin_parametros_adicionais)) {
			// Falta conferir se o fabricantre tem faixas de IP cadastradas.
			$IPsWhiteList = pg_num_rows(
				pg_query(
					$con,
					"SELECT ip_lista
					   FROM tbl_ip_lista
					  WHERE fabrica = $login_fabrica
					    AND tipo_ip = 'whitelist'
						AND ativo"
				)
			);

			// Confere se o IP do admin está na "whitelist" do fabricante
			$IPvalida = pg_num_rows(
				pg_query(
					$con,
					"SELECT ip_address
					   FROM tbl_ip_lista
					  WHERE fabrica = $login_fabrica
					    AND tipo_ip = 'whitelist'
					    AND ativo
					    AND ip_address >> '$login_ip'"
				)
			);

			// echo pg_last_error($con) . "<br>WhiteList IPs #: $IPsWhiteList<br>IP $login_ip válida: ";var_dump($IPvalida);
			if ($IPsWhiteList > 0 and !$IPvalida) {
				$title = 'ACESSO BLOQUEADO';
				$layout_menu = 'callcenter';
				include 'cabecalho_new.php';
				echo "<p><div class='alert'><h4>ACESSO BLOQUEADO, POR FAVOR, ACESSE DESDE UM LOCAL HABILITADO.</h4></div><p>";
				include 'rodape.php';
				die;
			}
		}

		$sql = "/* PROGRAMA $PHP_SELF  #   FABRICA $login_fabrica   #  ADMIN $login_admin */";
		$resX = @pg_exec ($con,$sql);

	}

}

if( in_array($login_fabrica, array(24, 85)) ){
	$sql = "INSERT INTO log_programa (programa, admin) VALUES ('$PHP_SELF',$login_admin)";
	$resX = @pg_exec ($con,$sql);
}

include 'valida_campos_obrigatorios.php';

#if ($login_admin <> 189 and strlen ($login_admin) > 0 ) {
#
#echo "<CENTER><h1>ATENÇÃO</h1>";
#echo "<h3>O sistema passará por manutenção técnica</h3";
#echo "<h3>Dentro de 10 minutos será restabelecido</h3";
#echo "<h3> </h3";
#echo "<p><h3>Agradecemos a compreensão!</h3>";
#exit;
#}

$sql = "INSERT INTO tbl_log_conexao(programa, admin, pid,fabrica) VALUES('$PHP_SELF', $login_admin, pg_backend_pid(),$login_fabrica)";
$res = pg_query($con, $sql);


/**
 * @since 2011.12.19 - tbl_ip_acesso não existe no Urano
 * @since 2012.09.06 - não usamos mais o Urano e sim o Netuno, agora temos a tabela
 *    mas ainda assim podemos viver sem isto.
 * Francisco Ambrozio
 */
if ($_serverEnvironment !== 'development') {

	$sql = "SELECT * FROM tbl_ip_acesso WHERE admin = $login_admin AND data::DATE = CURRENT_DATE and ip = '$login_ip'";
	$res = pg_exec($con,$sql);

	if( pg_numrows($res) == 0) {
		$login_ip = pg_escape_literal($con, $login_ip);
		$sql      = "INSERT INTO tbl_ip_acesso (admin, ip) VALUES ($login_admin, $login_ip)";
		$res      = pg_exec($con, $sql);
	}
}
// Pinsard/Ronald - 2015-06-22
// Em resposta à solicitação da Marisa sobre proteger o banco contra inteiros espúrios,
// o Ronald deu a ideia de filtrar o conteúdo de URLs que contenham o parâmetro "os"
// Pode não cobrir todos os casos, especialmente se o parâmetro não for nomeado "os".
// A solução definitiva acontecerá quando o banco for atualizado para versões maiores que 9.0
// Depois da atualização do banco, o bloco abaixo poderá ser removido do código.

if (!empty( $_REQUEST['os'] )) {
    if ( strlen( $_REQUEST[ 'os' ] ) > 12 and is_numeric($_REQUEST['os']) ) {
        include 'cabecalho.php';
        echo '<center><br />N&uacute;mero de ordem de servi&ccedil;o informado &eacute; inv&aacute;lido.<br />&nbsp;</center>';
        include 'rodape.php';
        exit ;
    }
}


$fabricas_contrato_lite = array(95,98,99,101);

//Fábricas que acessam o Môdulo de Revenda
$usa_sistema_de_revenda = in_array($login_fabrica, array(1,3,6,11,35,45,46,60,172));

include_once '../class/aws/s3_config.php';

// Código que verifica o campo parametros_adicionais na tbl_fábrica e que monta as variáveis de verificação
$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica AND parametros_adicionais IS NOT NULL ";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true); // true para retornar ARRAY e não OBJETO
    extract($parametros_adicionais); // igual o foreach, mais eficiente (processo interno do PHP)

    if($login_fabrica == 35){
	    $novaTelaOs = true;
	    $fabrica_qtde_anexos = 3;
	    $fabrica_usa_valor_adicional = true;
	    $telaPedido0315 = true;
	    $auditoria_unica = true;
	    $auditorias = array('peca' => true, 'produto' => true, 
	    	'fabricante' => true, 'reincidente' => true, 'numero_serie' => true, 'km' => true);
	}

    if (!$externalId) {
        $externalId    = 'smtp@posvenda';
        if (in_array($login_fabrica, array(169,170))){
            $externalEmail = 'naorespondablueservice@carrier.com.br';
        }else{
            $externalEmail = 'noreply@telecontrol.com.br';
        }

    }
}

/**
 * 31/08/2015 MLG
 * Fábricas que têm habilitado o HD Posto
 * Movido aos autentica_*, pois é usando em pelo menos 6 scripts.
 * Se alterar, tem que alterar no autentica_usuario e no autentica_admin
 **/
$fabrica_hd_posto = array(1,3,11,30,42,72,151,153,172);
if ($helpdeskPostoAutorizado == true) {
    $fabrica_hd_posto[] = $login_fabrica;
}
$fabrica_at_regiao = array(1, 151); // Fábricas que têm atendentes para diferentes regiões (cidade ou UF)

include_once('../class/tdocs.class.php');

$fabrica_com_token_form = array();

//Função para validação de token nos formulários
if(in_array($login_fabrica, $fabrica_com_token_form)){
    require_once __DIR__.DIRECTORY_SEPARATOR.'../token_form.php';
}


