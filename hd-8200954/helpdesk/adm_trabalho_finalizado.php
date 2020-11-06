<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
$sql =" UPDATE tbl_hd_chamado_item
           SET termino = CURRENT_TIMESTAMP
         WHERE hd_chamado_item IN(
            SELECT hd_chamado_item
					 FROM tbl_hd_chamado_item
             WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
						AND termino IS NULL
					 ORDER BY hd_chamado_item desc
					 LIMIT 1 );";
$res      = pg_query($con, $sql);
$msg_erro = pg_last_error($con);

$sql = "UPDATE tbl_hd_chamado_atendente
				SET data_termino = CURRENT_TIMESTAMP
				WHERE admin               =  $login_admin
				AND   data_termino IS NULL";
$res      = pg_query($con, $sql);
$msg_erro = pg_last_error($con);

if($login_admin=='432'){
	header ("Location: http://posvenda.telecontrol.com.br/assist/helpdesk/adm_chamado_lista_novo");
	exit;
}else{
	remove_login_cookie($_COOKIE['sess']);
	header ("Location: http://www.telecontrol.com.br");
	exit;
}

