<?
$login_posto = $cook_posto;

$gmtDate = gmdate("D, d M Y H:i:s");
header ("Expires: {$gmtDate} GMT");
header ("Last-Modified: {$gmtDate} GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");

?>
<html><head>
<title>Telecontrol - <?=$title;?></title>
<link type="text/css" rel="stylesheet" href="http://www.telecontrol.com.br/tc.css">
<link type="text/css" rel="stylesheet" href="css/estilo.css">
<style>

.Erro{BORDER-RIGHT: #990000 1px solid;BORDER-TOP: #990000 1px solid;FONT: 10pt Arial ;COLOR: #ffffff;BORDER-LEFT: #990000 1px solid;BORDER-BOTTOM: #990000 1px solid;BACKGROUND-COLOR: #FF0000;}
#Menu{border-bottom:#485989 1px solid;}
img{border:0px;}
.Caixa{
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BORDER:           #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	FONT-SIZE:        14px;
	FONT-FAMILY:      Verdana;
}


.TabelaRevenda{
	font-family:Verdana,sans;
	font-size:12px;
	border:#485989 1px solid;
	background-color: #e6eef7;

}
.TabelaRevenda caption{
	font-weight:bold;
	text-align: left;
	color: #000099;
}
.TabelaRevenda thead{
	background-color:#596D9B;
	color:#FFFFFF;
}
.TabelaRevenda tfoot td{
	text-align: center;
}
.TabelaRevenda th{
	text-align: left;
}




.b1h, .b2h, .b3h, .b4h, .b2bh, .b3bh, .b4bh{font-size:1px; overflow:hidden; display:block;}
.b1h {height:1px; background:#aaa; margin:0 5px;}
.b2h, .b2bh {height:1px; background:#aaa; border-right:2px solid #aaa; border-left:2px solid #aaa; margin:0 3px;}
.b3h, .b3bh {height:1px; background:#aaa; border-right:1px solid #aaa; border-left:1px solid #aaa; margin:0 2px;}
.b4h, .b4bh {height:2px; background:#aaa; border-right:1px solid #aaa; border-left:1px solid #aaa; margin:0 1px;}
.b2bh, .b3bh, .b4bh {background: #ddd;}
.headh {background: #aaa; border-right:1px solid #aaa; border-left:1px solid #aaa;}
.headh h3 {margin: 0px 10px 0px 10px; padding-bottom: 3px;}
.corpo {background: #ddd; border-right:1px solid #aaa; border-left:1px solid #aaa;}
.corpo div {margin-left: 12px; padding-top: 5px;}


</style>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="JS/thickbox.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/thickbox-compressed.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>

</head>
<body>
<table width='100%' border='0' cellspacing='0' align='center' cellpadding='2' background='../helpdesk/imagem/fundo_dh5.jpg' >
<tr height='83'>
	<td width='100%' align='left' >&nbsp;</td>
		<td align='left' nowrap >
		<?if (strlen($cook_login_simples)==0) {?>
			<a href='../login_unico.php' style='color:#FFF'>Início</a>&nbsp;|&nbsp;
		<?}?>
		<!--<a href='lv_pedido.php' style='color:#FFF'>Minhas Compras</font></a>&nbsp;|&nbsp;-->
			<a href='http://www.telecontrol.com.br' style='color:#FFF'>Sair</a>&nbsp;
		</td>
	</tr>
</table>

<div  style="position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;"><table id='div_carregando'  style="border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);" ><tbody><tr><td><b>Carregando...</b></td></tr></tbody></table></div>
<?
if($login_posto==4311 OR $login_posto==6359 OR $login_posto==28157){
?>
<br>
<div id="tabs">
	<ul>
	<li class="tab spacer">&nbsp;</li>
	<li class="tab selectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="1") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='../login_unico.php'">
		<span id="<?if($aba=="1") echo "tab0_view_title";else echo "tab1_view_title";?>">Início</span>
	</li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>


	<li id="tab1_view" class="<?if($aba=="2") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='rg_recebimento.php'">
		<span id="<?if($aba=="2") echo "tab0_view_title";else echo "tab1_view_title";?>">Lote de Revenda</span>
	</li>

	<?  if($login_posto==4311){ ?>

		<li class="tab selectedtab_r">&nbsp;</li>
		<li class="tab unselectedtab_l">&nbsp;</li>

		<li id="tab1_view" class="<?if($aba=="3") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='../estoque_consulta.php'">
			<span id="<?if($aba=="3") echo "tab0_view_title";else echo "tab1_view_title";?>">Estoque</span>
		</li>

	<? }?>
	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li class="tab unselectedtab_r">&nbsp;</li>
	<li class="tab addtab">&nbsp;&nbsp;</li>
	<li class="tab" id="addstuff"></li>
</ul>
</div>
<?
if($aba==2) {
	echo "<div align='left'>&nbsp;&nbsp;";
	echo "<a href='rg_recebimento.php'><font color='blue'>Recebimento</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<a href='rg_conferencia.php'><font color='blue'>Validar RG</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<a href='lote_consulta.php'><font color='blue'>Consulta Lotes</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<a href='planilha.php'><font color='blue'>Montar Planilha</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<a href='planilha_retorno.php'><font color='blue'>Digitar Planilha</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<a href='rg_retorno.php'><font color='blue'>Saída Produtos</font></a>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "</div>";
}
?>



<br>
<?}?>