<html>
<head>
<title>Telecontrol - <?=$title;?></title>
<link type="text/css" rel="stylesheet" href="css/tc.css">
<link type="text/css" rel="stylesheet" href="css/estilo.css">

<script type="text/javascript" src="js/jquery-1.1.2.pack.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" language="javascript" src="js/jquery.history_remote.pack.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.tabs.pack.js"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->
<script type="text/javascript" language="javascript" src="js/jquery.MultiFile.js" ></script>        
<script type="text/javascript" language="javascript" src="js/jquery.MetaData.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.maskedinput.js" ></script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script type='text/javascript'                       src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript'                       src='js/dimensions.js'></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />
<script type="text/javascript"                       src="js/jquery.bgiframe.js"></script>
<script type="text/javascript"                       src="js/jquery.dimensions.tootip.js"></script>
<script type="text/javascript"                       src="js/chili-1.7.pack.js"></script>
<script type="text/javascript"                       src="js/jquery.tooltip.js"></script>


<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs({fxSpeed: 'fast'} );
		
	});
</script>
<style>
.Conteudo{font-family: Arial;font-size: 10px;color: #333333;}
.D{FONT-FAMILY:Arial;FONT-SIZE:10px;FONT-WEIGHT:normal;COLOR:#777777;}
</style>

</head>
<body>
<?if($mostra_menu <> "FALSE"){?>
<table width='100%' border='0' cellspacing='0' align='center' cellpadding='2' id='Menu'>
	<tr height='50' >
		<td bgcolor='#ffeac0' align='center' width='250' height='50'><img src='../logos/telecontrolmini.gif'></td>
		<td bgcolor='#ffeac0' align='left' class='Conteudo'>&nbsp;<?echo "<b>$login_nome</b> <br> &nbsp;$login_email";?> 
		</td>
		<td bgcolor='#ffeac0' align='right' nowrap>
			<a href='index.php'>Inicial</a> | 
			<a href="ajuda.php?TB_iframe=true&height=400&width=600" class="thickbox" id='LinkAjuda' title='Ajuda'> Ajuda</a> | 
			<a href='http://www.telecontrol.com.br'>Sair</font></a>
		</td>
	</tr>
</table>
<?
if(strlen($login_logo)>0) echo "<br><img src='logos/$login_logo'><p>&nbsp;</p>";
else                      echo "<br><b>Logomarca da Empresa - $login_nome</b><p>&nbsp;</p>";
?>

<div id="tabs">
	<ul>
	<li class="tab spacer">&nbsp;</li>
	<li class="tab selectedtab_l">&nbsp;</li>

	<li id="tab0_view" class="<?if($aba == "1") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='index.php'">
		<span id="<?if($aba=="1") echo "tab0_view_title";else echo "tab1_view_title";?>">Página inicial</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba == "2") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='lote_cadastro.php'" alt='Gerenciamento de AT'>
		<span id="<?if($aba == "2") echo "tab0_view_title";else echo "tab1_view_title";?>" alt='Cadastro de Lote'>Cadastro de Lotes</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="3") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='lote_consulta.php'">
		<span id="<?if($aba=="3") echo "tab0_view_title";else echo "tab1_view_title";?>">Consulta Notas</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="4") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='nf_entrada.php'">
		<span id="<?if($aba=="4") echo "tab0_view_title";else echo "tab1_view_title";?>">Recebimento de Produto</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="5") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='produto_lista.php'">
		<span id="<?if($aba=="5") echo "tab0_view_title";else echo "tab1_view_title";?>">Produtos</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="6") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='revenda_cadastro.php'">
		<span id="<?if($aba=="6") echo "tab0_view_title";else echo "tab1_view_title";?>">Dados da Empresa</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="7") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='lote_consulta_macro.php'">
		<span id="<?if($aba=="7") echo "tab0_view_title";else echo "tab1_view_title";?>">Relatório Macro</span></li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li class="tab unselectedtab_r">&nbsp;</li>
	<li class="tab addtab">&nbsp;&nbsp;</li>
	<li class="tab" id="addstuff"></li>
</ul>
</div>
<center>
<?}?>