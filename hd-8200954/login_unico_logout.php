<?php
include 'logout_2.php';
exit;

//  Apaga as informações setadas pelo login_unico_cabecalho
	setcookie ("cook_posto_fabrica");
	setcookie ("cook_posto");
	setcookie ("cook_fabrica");
	setcookie ("cook_login_posto");
	setcookie ("cook_login_unico");
	setcookie ("cook_login_nome");
	setcookie ("cook_login_cnpj");
	setcookie ("cook_login_fabrica");
	setcookie ("cook_login_fabrica_nome");
	setcookie ("cook_login_pede_peca_garantia");
	setcookie ("cook_login_tipo_posto");
	setcookie ("cook_login_e_distribuidor");
	setcookie ("cook_login_distribuidor");
	setcookie ("cook_pedido_via_distribuidor");
header('Location: index.php');
?>
