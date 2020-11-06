<?
$cook_posto_fabrica = $cookie_login['cook_posto_fabrica'];
$cook_fabrica       = $cookie_login['cook_fabrica'];
$cook_posto         = $cookie_login['cook_posto'];

if ($cook_posto_fabrica == 'deleted') {
	echo "<center><b>Seu computador está possivelmente infectado por vírus que atrabalha o correto funcionamento deste site. É um vírus que deleta os <i>cookies</i> que o site precisa para trabalhar.<p>Por favor, atualize seu anti-vírus ou entre em contato com o suporte técnico que lhe vendeu este computador.<p>Qualquer dúvida, peça para que seu técnico entre em contato com a TELECONTROL. (14) 3413-6588 ou suporte@telecontrol.com.br </b></center>";
	exit;
}

if (strlen ($cook_posto_fabrica) == 0) {
	header ("Location: index.php");
	exit;
}


$sql = "SELECT  tbl_posto_fabrica.posto,
			tbl_posto_fabrica.fabrica
	FROM    tbl_posto_fabrica
	WHERE	tbl_posto_fabrica.fabrica = $cook_fabrica
	AND     tbl_posto_fabrica.posto   = $cook_posto";
$res = pg_exec ($con,$sql);

$login_fabrica = pg_result ($res,0,fabrica);
$login_posto   = pg_result ($res,0,posto);

$sql = "SELECT	tbl_posto.posto                           ,
			tbl_posto.nome                                ,
			tbl_posto.cnpj                                ,
			tbl_fabrica.nome as fabrica_nome              ,
			tbl_posto_fabrica.pedido_em_garantia          ,
			tbl_posto_fabrica.tipo_posto                  ,
			tbl_posto_fabrica.distribuidor                ,
			tbl_posto_fabrica.reembolso_peca_estoque      ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_fabrica.fabrica                           ,
			tbl_tipo_posto.distribuidor AS e_distribuidor ,
			tbl_posto_fabrica.pedido_via_distribuidor     ,
			tbl_posto_fabrica.credenciamento              ,
			tbl_fabrica.pedir_causa_defeito_os_item       ,
			tbl_fabrica.pedir_defeito_constatado_os_item  ,
			tbl_fabrica.pedir_solucao_os_item
	FROM	tbl_posto
	JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
	JOIN	tbl_fabrica       ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
	JOIN    tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
	WHERE	tbl_posto_fabrica.fabrica = $cook_fabrica
	AND     tbl_posto_fabrica.posto   = $cook_posto";
$res = @pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
header ("Location: index.php");
exit;
}

$login_posto                       = trim (pg_result ($res,0,posto));
$login_nome                        = trim (pg_result ($res,0,nome));
$login_codigo_posto                = trim (pg_result ($res,0,codigo_posto));
$login_cnpj                        = trim (pg_result ($res,0,cnpj));
$login_fabrica                     = trim (pg_result ($res,0,fabrica));
$login_fabrica_nome                = trim (pg_result ($res,0,fabrica_nome));
$login_pede_peca_garantia          = trim (pg_result ($res,0,pedido_em_garantia));
$login_tipo_posto                  = trim (pg_result ($res,0,tipo_posto));
$login_e_distribuidor              = trim (pg_result ($res,0,e_distribuidor));
$login_distribuidor                = trim (pg_result ($res,0,distribuidor));
$pedido_via_distribuidor           = trim (pg_result ($res,0,pedido_via_distribuidor));
$login_reembolso_peca_estoque      = trim (pg_result ($res,0,reembolso_peca_estoque));
$login_credenciamento              = trim (pg_result ($res,0,credenciamento));
$pedir_causa_defeito_os_item       = trim (pg_result ($res,0,pedir_causa_defeito_os_item));
$pedir_defeito_constatado_os_item  = trim (pg_result ($res,0,pedir_defeito_constatado_os_item));
$pedir_solucao_os_item             = trim (pg_result ($res,0,pedir_solucao_os_item));

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
$login_bloqueio_pedido = $cookie_login["cook_bloqueio_pedido"];

if (strlen ($login_bloqueio_pedido) == 0) {
	$sql = "SELECT * FROM tbl_distribuidor_lista_negra
			WHERE  tbl_distribuidor_lista_negra.posto        = $login_posto
			AND    tbl_distribuidor_lista_negra.distribuidor = $login_distribuidor;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$login_bloqueio_pedido = "t";
	}


	if (strlen ($login_bloqueio_pedido) == 0) {
		$sql = "SELECT  distinct tbl_posto.posto
				FROM    tbl_contas_receber
				JOIN    tbl_faturamento_fatura   ON tbl_faturamento_fatura.faturamento_fatura = tbl_contas_receber.faturamento_fatura
				JOIN    tbl_faturamento          ON tbl_faturamento.faturamento_fatura        = tbl_faturamento_fatura.faturamento_fatura
				JOIN    tbl_posto_fabrica        ON tbl_posto_fabrica.posto                   = tbl_faturamento.posto
												AND tbl_posto_fabrica.fabrica                 = tbl_faturamento.fabrica
												AND tbl_posto_fabrica.fabrica                 = $login_fabrica
												AND tbl_posto_fabrica.posto                   = $login_posto
				JOIN    tbl_posto                ON tbl_posto.posto                           = tbl_faturamento.posto
				WHERE   (tbl_contas_receber.vencimento + 7)::date <= current_date
				AND     tbl_contas_receber.recebimento ISNULL
				AND     tbl_contas_receber.status      ISNULL
				ORDER BY    tbl_posto.posto;";
		//if ($ip == "201.0.9.216") echo $sql;
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$login_bloqueio_pedido = "t";
		}else{
			$login_bloqueio_pedido = "f";
		}
	}
	// setcookie ('cook_bloqueio_pedido',$login_bloqueio_pedido);
	add_cookie($cookie_login,'cook_bloqueio_pedido',$login_bloqueio_pedido);	
	set_cookie_login($token_cookie,$cookie_login);
}

?>
