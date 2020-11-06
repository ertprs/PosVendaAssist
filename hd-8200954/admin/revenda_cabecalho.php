<link type="text/css" rel="stylesheet" href="../revend/css/tc.css">
<div id="tabs">
	<ul>
	<li class="tab spacer">&nbsp;</li>
	<li class="tab selectedtab_l">&nbsp;</li>

	<li id="tab0_view" class="<?if($aba == "1") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_inicial.php'">
		<span id="<?if($aba=="1") echo "tab0_view_title";else echo "tab1_view_title";?>">Página inicial</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba == "2") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_lote_cadastro.php'" alt='Gerenciamento de AT'>
		<span id="<?if($aba == "2") echo "tab0_view_title";else echo "tab1_view_title";?>" alt='Cadastro de Lote'>Cadastro de Lotes</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="3") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_lote_consulta.php'">
		<span id="<?if($aba=="3") echo "tab0_view_title";else echo "tab1_view_title";?>">Consulta Lotes</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="4") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_cadastro_revenda.php'">
		<span id="<?if($aba=="4") echo "tab0_view_title";else echo "tab1_view_title";?>">Revendas</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="5") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_produto.php'">
		<span id="<?if($aba=="5") echo "tab0_view_title";else echo "tab1_view_title";?>">Produtos</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="6") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_lote_posto.php'">
		<span id="<?if($aba=="6") echo "tab0_view_title";else echo "tab1_view_title";?>">Postos</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="7") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_lote_consulta_macro.php'">
		<span id="<?if($aba=="7") echo "tab0_view_title";else echo "tab1_view_title";?>">Relatório Macro</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>


	<li class="tab unselectedtab_r">&nbsp;</li>
	<li class="tab addtab">&nbsp;&nbsp;</li>
	<li class="tab" id="addstuff"></li>
</ul>
</div>