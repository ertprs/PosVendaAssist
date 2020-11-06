<?php
// Retirado a pedido do Túlio HD 10291

$sql="SELECT SUM(data_expira_senha - CURRENT_DATE) AS data
        FROM tbl_posto_fabrica
       WHERE posto   = $login_posto
         AND fabrica = $login_fabrica";
$res = pg_query($con, $sql);
$data_expira_senha = pg_fetch_result($res, 0, 'data');
if ($data_expira_senha < 0) {
	header("Location: alterar_senha.php");
	exit;
}else{
	if (strlen($msg_validade_cadastro) == 0) {
		$msg_validade_cadastro="<a href='alterar_senha.php'><font size='1' face='arial,verdana'>Sua senha irá expirar em $data_expira_senha dias. Clique aqui para cadastrar uma senha nova.</font></a>";
	}
}

