<?

	$titulo_menu_1 = "<A class='menu_item' href='menu_cadastro_n.php'>CADASTROS</A>";
	$titulo_menu_2 = "<A class='menu_item' href='menu_gerencia_n.php'>GERÊNCIA</A>";
	$titulo_menu_3 = "<A class='menu_item' href='menu_callcenter_n.php'>CALL-CENTER</A>";
	$titulo_menu_4 = "<A class='menu_item' href='menu_infotecnica_n.php'>INFO TÉCNICA</A>";
	$titulo_menu_5 = "<A class='menu_item' href='menu_financeiro_n.php'>FINANCEIRO</A>";
	$titulo_menu_6 = "<A class='menu_item' href='menu_auditoria_n.php'>AUDITORIA</A>";
	
	switch ($cook_submenu) {
		// CADASTROS
		case 1:
			$titulo_submenu_1 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 01</A>";
			$titulo_submenu_2 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 02</A>";
			$titulo_submenu_3 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 03</A>";
			$titulo_submenu_4 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 04</A>";
			$titulo_submenu_5 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 05</A>";
			$titulo_submenu_6 = " &nbsp;<A class='sub_item' href='index.php'>|&nbsp; VOLTAR PARA MENU</A>";
			$titulo_submenu_7 = "<A class='sub_item' href='../logout.php'>|&nbsp;SAIR&nbsp;|&nbsp;</A>";
		break;

		// GERÊNCIA
		case 2:
			$titulo_submenu_1 = " &nbsp;<A class='sub_item' href='os_parametros.php'>|&nbsp; CONSULTA OS</A>";
			$titulo_submenu_2 = " &nbsp;<A class='sub_item' href='os_extrato.php'>|&nbsp; PRÉ-FECH. EXTRATO</A>";
			$titulo_submenu_3 = " &nbsp;<A class='sub_item' href='extrato_detalhe.php'>|&nbsp; EXTRATOS PRÉ-FECHADOS</A>";
			$titulo_submenu_4 = " &nbsp;<A class='sub_item' href='relatorio_quebra_ano.php'>|&nbsp; QUEBRA POR ANO</A>";
			//$titulo_submenu_5 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 05</A>";
			$titulo_submenu_6 = " &nbsp;<A class='sub_item' href='index.php'>|&nbsp; VOLTAR PARA MENU</A>";
			$titulo_submenu_7 = "<A class='sub_item' href='../logout.php'>|&nbsp;SAIR&nbsp;|&nbsp;</A>";
		break;

		// CALL-CENTER
		case 3:
			$titulo_submenu_1 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 01</A>";
			$titulo_submenu_2 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 02</A>";
			$titulo_submenu_3 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 03</A>";
			$titulo_submenu_4 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 04</A>";
			$titulo_submenu_5 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 05</A>";
			$titulo_submenu_6 = " &nbsp;<A class='sub_item' href='index.php'>|&nbsp; VOLTAR PARA MENU</A>";
			$titulo_submenu_7 = "<A class='sub_item' href='../logout.php'>|&nbsp;SAIR&nbsp;|&nbsp;</A>";
		break;

		// INFO TÉCNICA
		case 4:
			$titulo_submenu_1 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 01</A>";
			$titulo_submenu_2 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 02</A>";
			$titulo_submenu_3 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 03</A>";
			$titulo_submenu_4 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 04</A>";
			$titulo_submenu_5 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 05</A>";
			$titulo_submenu_6 = " &nbsp;<A class='sub_item' href='index.php'>|&nbsp; VOLTAR PARA MENU</A>";
			$titulo_submenu_7 = "<A class='sub_item' href='../logout.php'>|&nbsp;SAIR&nbsp;|&nbsp;</A>";
		break;

		// FINANCEIRO
		case 5:
			$titulo_submenu_1 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 01</A>";
			$titulo_submenu_2 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 02</A>";
			$titulo_submenu_3 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 03</A>";
			$titulo_submenu_4 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 04</A>";
			$titulo_submenu_5 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 05</A>";
			$titulo_submenu_6 = " &nbsp;<A class='sub_item' href='index.php'>|&nbsp; VOLTAR PARA MENU</A>";
			$titulo_submenu_7 = "<A class='sub_item' href='../logout.php'>|&nbsp;SAIR&nbsp;|&nbsp;</A>";
		break;

		// AUDITORIA
		case 6:
			$titulo_submenu_1 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 01</A>";
			$titulo_submenu_2 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 02</A>";
			$titulo_submenu_3 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 03</A>";
			$titulo_submenu_4 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 04</A>";
			$titulo_submenu_5 = " &nbsp;<A class='sub_item' href='#'>|&nbsp; OPÇÃO 05</A>";
			$titulo_submenu_6 = " &nbsp;<A class='sub_item' href='index.php'>|&nbsp; VOLTAR PARA MENU</A>";
			$titulo_submenu_7 = "<A class='sub_item' href='../logout.php'>|&nbsp;SAIR&nbsp;|&nbsp;</A>";
		break;
	}
?>