<?php
//	Limpa todas as cookies do Pós-Venda, na hora que é carregado o site inicial
	setcookie ("cook_login");
	setcookie ('cook_login_posto');
	setcookie ('cook_login_codigo_posto');
	setcookie ('cook_login_fabrica'); // retirado para não perder a fabrica pois temos regras de redirecionamento!
	setcookie ('cook_login_cnpj');
	setcookie ('cook_login_nome');
	setcookie ('cook_login_fabrica_nome');
	setcookie ('cook_login_pede_peca_garantia');
	setcookie ('cook_login_tipo_posto');
	setcookie ("cook_posto_fabrica");
	setcookie ("cook_fabrica");
	setcookie ("cook_posto");
	setcookie ("cook_login_unico");

	setcookie ('cook_login','');
	setcookie ('cook_admin','');
	setcookie ('cook_master','');
	setcookie ('cook_fabrica','');
	setcookie ('cook_posto','');
	setcookie ('cook_posto_fabrica','');
?>
