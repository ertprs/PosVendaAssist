<?

$cook_fabrica = $_COOKIE['cook_fabrica'];
$cook_admin   = $_COOKIE['cook_admin'];
$cook_master  = $_COOKIE['cook_master'];

$cook_empresa = $_COOKIE['cook_empresa'];
$cook_loja    = $_COOKIE['cook_loja'];
$cook_empregado	= $_COOKIE['cook_empregado'];
$cook_posto_empregado	= $_COOKIE['cook_posto_empregado'];


if (strlen ($cook_admin) == 0 and strlen($cook_posto_empregado) == 0)  {
	header ("Location: ../index.php");
	exit;
}


//if ($ip <> "201.0.9.216" and $cook_fabrica == 3) {
//	header ("Location: ../index.php");
//	exit;
//}

global $login_admin;
global $login_login;
global $login_fabrica;
global $login_privilegios;
global $login_fabrica_nome;
global $login_fabrica_logo;
global $login_fabrica_site;
global $multimarca;
global $acrescimo_tabela_base;
global $acrescimo_financeiro;
global $pedir_causa_defeito_os_item;
global $pedir_defeito_constatado_os_item;
global $pedir_solucao_os_item;



//para acesso ao ERP
global $login_empresa        ;
global $login_loja           ;
global $login_posto_empregado;
global $login_empregado;
global $login_empregado_nome ;
global $login_filial_nome    ;
global $login_empresa_nome   ;
//ERP
if(strlen($cook_empresa) > 0){


	
	$sql = "SELECT nome 
			FROM tbl_posto 
			WHERE posto = $login_posto_empregado;";
	$res = @pg_exec ($con,$sql);
	$l_empregado_nome = trim (@pg_result ($res,0,nome));

	$login_empresa         = $cook_empresa;
	$login_loja            = $cook_loja;
	$login_empregado	   = $cook_empregado;
	$login_posto_empregado = $cook_posto_empregado;
	$login_admin           = $cook_admin;
	$login_empregado_nome  = $l_empregado_nome;
	

}else{
//USADO PARA LOGAR COMO FABRICA SEM PRECISAR USAR LOGIN DOS FABRICANTES
if(strlen($cook_master)>0) {
	$sql = "SELECT  
				tbl_fabrica.nome as fabrica_nome            ,
				tbl_fabrica.logo AS fabrica_logo            ,
				tbl_fabrica.site AS fabrica_site            ,
				tbl_fabrica.multimarca                      ,
				tbl_fabrica.acrescimo_tabela_base           ,
				tbl_fabrica.acrescimo_financeiro            ,
				tbl_fabrica.pedir_causa_defeito_os_item     ,
				tbl_fabrica.pedir_defeito_constatado_os_item,
				tbl_fabrica.pedir_solucao_os_item
		FROM    tbl_fabrica 
		WHERE   tbl_fabrica.fabrica = $cook_fabrica";

	$res = @pg_exec ($con,$sql);

	$login_fabrica_nome                = trim (pg_result ($res,0,fabrica_nome));
	$login_fabrica_logo                = trim (pg_result ($res,0,fabrica_logo));
	$login_fabrica_site                = trim (pg_result ($res,0,fabrica_site));
	$multimarca                        = trim (pg_result ($res,0,multimarca));
	$acrescimo_tabela_base             = trim (pg_result ($res,0,acrescimo_tabela_base));
	$acrescimo_financeiro              = trim (pg_result ($res,0,acrescimo_financeiro));
	$pedir_causa_defeito_os_item       = trim (pg_result ($res,0,pedir_causa_defeito_os_item));
	$pedir_defeito_constatado_os_item  = trim (pg_result ($res,0,pedir_defeito_constatado_os_item));
	$pedir_solucao_os_item             = trim (pg_result ($res,0,pedir_solucao_os_item));


	$sql = "SELECT  tbl_admin.admin                             ,
					tbl_admin.fabrica                           ,
					tbl_admin.login                             ,
					tbl_admin.senha                             ,
					tbl_admin.privilegios                       
			FROM    tbl_admin
			WHERE   tbl_admin.admin   = $cook_admin";
//echo "sql aut_admin: $sql";
	$res = @pg_exec ($con,$sql);

	$login_admin                       = trim (pg_result ($res,0,admin));
	$login_login                       = trim (pg_result ($res,0,login));
	$login_fabrica                     = $cook_fabrica;
	$login_privilegios                 = "*";

}else{
	$sql = "SELECT  tbl_admin.admin                             ,
					tbl_admin.fabrica                           ,
					tbl_admin.login                             ,
					tbl_admin.senha                             ,
					tbl_admin.privilegios                       ,
					tbl_fabrica.nome as fabrica_nome            ,
					tbl_fabrica.logo AS fabrica_logo            ,
					tbl_fabrica.site AS fabrica_site            ,
					tbl_fabrica.multimarca                      ,
					tbl_fabrica.acrescimo_tabela_base           ,
					tbl_fabrica.acrescimo_financeiro            ,
					tbl_fabrica.pedir_causa_defeito_os_item     ,
					tbl_fabrica.pedir_defeito_constatado_os_item,
					tbl_fabrica.pedir_solucao_os_item
			FROM    tbl_admin
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			WHERE   tbl_admin.admin   = $cook_admin
			AND     tbl_admin.fabrica = $cook_fabrica";
	$res = @pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		header ("Location: ../index.php");
		exit;
	}
	$login_admin                       = trim (pg_result ($res,0,admin));
	$login_login                       = trim (pg_result ($res,0,login));
	$login_fabrica                     = trim (pg_result ($res,0,fabrica));
	$login_privilegios                 = trim (pg_result ($res,0,privilegios));
	$login_fabrica_nome                = trim (pg_result ($res,0,fabrica_nome));
	$login_fabrica_logo                = trim (pg_result ($res,0,fabrica_logo));
	$login_fabrica_site                = trim (pg_result ($res,0,fabrica_site));
	$multimarca                        = trim (pg_result ($res,0,multimarca));
	$acrescimo_tabela_base             = trim (pg_result ($res,0,acrescimo_tabela_base));
	$acrescimo_financeiro              = trim (pg_result ($res,0,acrescimo_financeiro));
	$pedir_causa_defeito_os_item       = trim (pg_result ($res,0,pedir_causa_defeito_os_item));
	$pedir_defeito_constatado_os_item  = trim (pg_result ($res,0,pedir_defeito_constatado_os_item));
	$pedir_solucao_os_item             = trim (pg_result ($res,0,pedir_solucao_os_item));

}



if (strlen ($admin_privilegios) > 0) {
	$admin_autorizado = 0;
	$array_privilegios = split (",",$admin_privilegios);
	for ($i = 0 ; $i < count($array_privilegios) ; $i++) {
		$cabecalho_privilegio = $array_privilegios[$i];
		if (strpos ($login_privilegios , trim($cabecalho_privilegio)) !== false) {
			$admin_autorizado = 1;
		}
	}

	if (strpos ($login_privilegios,"*") !== false) {
		$admin_autorizado = 1;
	}

	if ($admin_autorizado == 0) {
		$title = "MENU GERÊNCIA";
		$layout_menu = "gerencia";
		include 'cabecalho.php';
		echo "<p><hr><center><h1>Sem permissão para acessar este programa</h1></center><p><hr>";
		exit;
	}
}


}
$sql = "/* PROGRAMA $PHP_SELF  #   FABRICA $login_fabrica   #  ADMIN $login_admin */";
$resX = @pg_exec ($con,$sql);

// Define e verifica a presença do SDK do Amazon Web Services.
// Também define o ambiente de trabalho, desenvolvimento (DEV_ENV==TRUE) ou produção (DEV_ENV==FALSE)
include_once 'class/aws/s3_config.php';
?>
