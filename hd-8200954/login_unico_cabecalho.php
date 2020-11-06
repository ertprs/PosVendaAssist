<?$login_posto = $cook_posto;
echo $login_posto;
?>
<HTML>
<HEAD>
<TITLE>Telecontrol - <?=$title;?></TITLE>
<link type="text/css" rel="stylesheet" href="css/tc_lu.css">
<link type="text/css" rel="stylesheet" href="css/estilo.css">
<link href="/assist/imagens/tc_2009.ico" rel="shortcut icon">
<script type="text/javascript" language="JavaScript">
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}
</script>
<style>
body {color:black}
.Destaque {font-size: 12pt;font-weight: bold;color: black;}
.Label {font-family: Arial;font-size: 10px;color: black;}
.Menu_Conteudo {font-family: Arial;font-size: 8pt;font-weight: normal;color:white}
.Conteudo {font-family: Arial;font-size: 8pt;font-weight: normal;}
.Espaco{background-color: white; width: 25px;text-align:center;}
.Erro{border: 1px solid #900;font: 10pt Arial ;color: white;background-color: red;}
.d{font-family:arial;font-size:10px;font-weight:normal;color:#777;}
#Menu{border-bottom:#485989 1px solid;}
acronym {color:#F90;cursor: help;}
img{border:0px;}
.Caixa{
	border:           1px solid #69c;
	background-color: white;
	font-size:        10px;
	font-family:      Verdana;
}
.aviso{
	color:#6C6C6C;
	font-size:12px;
}
H2.aviso_dados{
	color: #F22;
	border-bottom: 1px dotted #F22;
	margin-bottom: 5px;
    text-decoration: blink;
    font-weight: bold;
    font-size: 1.2em;
    background-color: #cae0ff;
    padding: 0 0.5ex;
}
H2.aviso_dados IMG,
DIV.atualiza_dados IMG {cursor:pointer}
DIV.atualiza_dados {
	border: 1px solid #242946;
	border-right-width: 2px;
	border-bottom-width: 2px;
    margin-bottom: 1em;
    margin-right:  2em;
	padding-bottom: 1ex;
	background-color: #e6eef7;
    background-image: -webkit-gradient(linear, left top, left bottom, from(#9ac0ff), to(#e6eef7);
		filter: progid:DXImageTransform.Microsoft.Gradient(gradientType=1),"1",startColorStr=#9ac0ff,endColorStr=#e6eef7);
	padding: 0.5ex;
}

IMG.btn_gravar {
	cursor: pointer;
	position: relative;
	top: 4px;
}

.botao {
	cursor: pointer;
	background-color: #DDD;
	color: #333;
	padding: 0 0.7ex;
	/* Bordes redondeados, *SOLO CSS3* */
    border-radius: 6px;
    	-moz-border-radius: 6px;
    	-webkit-border-radius: 6px;
	border: 2px outset #999;
	height: 0.9em;
	font-size: 0.9em;
}
.botao:hover {	/* Para navegadores no-IE	*/
	border: 2px inset #666;
}


.titulo2{
	font-size:14px;
	font-weight:bold;
	color:#3B3B3B;
}
.TabelaRevenda{
	border:#485989 1px solid;
	background-color: #e6eef7;
	font-size:12px;
}
.TabelaRevenda caption{
	font-weight:bold;
	text-align: left;
	color: #009;
}
.TabelaRevenda th {
	text-align: left;
}

Table#cabecalho {
	width: 100%;
	border:0;
    border-collapse: collapse;
	border-spacing:2px;
	background-image:url('helpdesk/imagem/fundo_dh5.jpg');
}
Table#cabecalho tr {
	height:83;
    text-align:left;
	vertical-align: middle;
}
Table#cabecalho td {
    white-space: nowrap;
    text-align:right;
    padding-right: 6px;
}
@import url('./css/tooltips.css');
Table#cabecalho a.lu {text-decoration:none;color:white;border-bottom: 1px dotted white;}
Table#cabecalho a.lu:hover {border-bottom-style:solid}
img#logotv {width:44px; height:45px; border:0}
</style>
<script src="js/jquery-1.1.2.js" type="text/javascript"></script>
<!-- <script type="text/javascript">
	$(function() {
		$("a[@rel='televendas']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			fixPNG: true,
			showBody: " - ",
			extraClass: "televendas"
		});
		//$('div.top').corner();
//		$("div[@rel='box_content']").corner("20px tr bl");
	});
</script> -->
</head>
<body>
<table id='cabecalho'>
	<tr>
	<td width='75%'>
<?
/*	14/11/2008 - Confere se está na loja virtual usando um método simples:
	se a URL contém 'lv_'. Isto limita os nomes das páginas relacionadas à loja
	virtual, pois os arquivos principais devem conter essa string. Por enquanto
	já é assim (lv_completa, lv_detalhe, lv_carrinho, lv_menu, lv_menu_lateral...)  */
	if (strpos($PHP_SELF,"lv_")>0) {
	$urltelevendas="'./lv_televendas.html','Atendimento',".
			 "'toolbar=no,location=no,status=no,menubar=no,".
			 "resizable=no,scrollbars=no,width=400,height=550'";  ?>
		<a href="javascript:window.open(<? echo $urltelevendas; ?>);void(0);">
			<IMG id='logotv' SRC="./imagens/televendas_am_pq.png" ALT="Televendas">
		</a>
	</td>
	<td>
		<A href="javascript:window.open(<? echo $urltelevendas; ?>);void(0);"
			style= 'text-align: left;
					text-decoration: none;
					color: #FADB05;
					a:link {text-decoration: none;
							 color: #FADB05;}'
			title="<? echo $televendas; ?>">
				<span><IMG SRC="./imagens/televendas_am_sm.png" WIDTH="92" HEIGHT="14" BORDER="0" ALT="" /></span>
			<BR><span style='font: 0.8em;'>Central de Atendimento
			<BR><span style='font: 0.8em; text-decoration: underline;'>Veja Mais</span>
		</A>
	<?// } else { ?>
	<!--
	<TD>
	    <A HREF='./lv_completa.php' TITLE='Acesse a Loja Virtual Telecontrol'>
	        <IMG SRC='./imagens/lv_titulo.png'>
	 	</A>
	 </TD>
	 -->


	 <?}  ?>
	</td>
	<TD>
		&nbsp; <?	// Aqui o código para a lista de aniversariantes do mês ou da semana... ?>

	</TD>
	<TD  style='height: 1.5em; line-height: 1.5em;color:black;'>
<? 	if (strpos($PHP_SELF,"lv_")>0) {  ?>
		<a href='lv_pedido.php' class='lu'>Minhas Compras</a>&nbsp;|&nbsp;
		<? }	if (strlen($cook_login_simples)==0) {?>
			<a href='login_unico.php' class='lu'>In&iacute;cio</a>&nbsp;|&nbsp;
		<?}?>
		<a href='login_unico_logout.php' class='lu'>Sair</a>&nbsp;
	</TD>
	</tr>
</table>
<!--
<table width='100%' border='0' cellspacing='0' align='center' cellpadding='2' id='Menu'>
	<tr height='50' >
		<td bgcolor='#005f9d' align='center' width='250' height='50'><img src='logos/telecontrolmini.gif'></td>
		<td bgcolor='#005f9d' align='left' class='Menu_Conteudo'>&nbsp;<?echo "<b>$login_nome</b> <br> &nbsp;$login_email";?>
		</td>
		<td bgcolor='#005f9d' align='right' nowrap>
		<?if (strlen($cook_login_simples)==0) {?>
			<a href='login_unico.php' style='color:white'>Início</font></a>&nbsp;|&nbsp;
		<?}?>
		<a href='lv_pedido.php' style='color:white'>Minhas Compras</font></a>&nbsp;|&nbsp;
			<a href='http://www.telecontrol.com.br' style='color:white'>Sair</font></a>&nbsp;
		</td>
	</tr>
</table>
-->

<div	id='div_carregando'
	 style='position: absolute;opacity:.90;z-index:1; overflow: auto;
	 		top:0px;right: 5px;width: 18ex;line-height: 1.4em;text-align: center;
			color: #8C663F;font-weight:bold;border: 1px solid #D3BE96;
			background-color: #FCF0D8;'>
			Carregando...
</div>
<?
if($login_posto==4311 OR $login_posto==6359 OR $login_posto==28157){
?>
<center>
<br clear=both>
<div id="tabs">
	<ul>
	<li class="tab spacer">&nbsp;</li>
	<li class="tab selectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="<?if($aba=="1") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='login_unico.php'">
		<span id="<?if($aba=="1") echo "tab0_view_title";else echo "tab1_view_title";?>">Início</span>
	</li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>


	<li id="tab1_view" class="<?if($aba=="2") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='fluxo-r/rg_recebimento.php'">
		<span id="<?if($aba=="1") echo "tab0_view_title";else echo "tab1_view_title";?>">Lote de Revenda</span>
	</li>

	<?  if($login_posto==4311){ ?>

		<li class="tab selectedtab_r">&nbsp;</li>
		<li class="tab unselectedtab_l">&nbsp;</li>

		<li id="tab1_view" class="<?if($aba=="3") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='estoque_consulta.php'">
			<span id="<?if($aba=="1") echo "tab0_view_title";else echo "tab1_view_title";?>">Estoque</span>
		</li>

		<li class="tab selectedtab_r">&nbsp;</li>
		<li class="tab unselectedtab_l">&nbsp;</li>

		<li id="tab1_view" class="<?if($aba=="4") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='distrib/'">
			<span id="<?if($aba=="4") echo "tab0_view_title";else echo "tab1_view_title";?>">Distrib</span>
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
	if($aba==1){
	echo "<div align='left'>&nbsp;&nbsp;";
	echo "<a href='login_unico_alterar_senha.php'>Alterar Senha</a>&nbsp;&nbsp;";
	echo "<a href='login_unico_alterar_email.php'>Alterar Email</a>&nbsp;&nbsp;";
	if($login_master =='t') echo "<a href='login_unico_cadastro.php?t=lu'>Cadastro de Usuários</a>&nbsp;&nbsp;";
// 	echo "<a href='lv_completa.php'>Loja Virtual</a>";
	echo "</div>";
	}
	if($aba==2) {
		/*
	echo "<div align='left'>&nbsp;&nbsp;";
	echo "<a href='login_unico_rg.php'>Status</a>&nbsp;&nbsp;";
	echo "<a href='login_unico_rg_recebimento.php'>Recebimento</a>&nbsp;&nbsp;";
	echo "<a href='login_unico_rg_conferencia.php'>Validar RG</a>&nbsp;&nbsp;";

	echo "<a href='login_unico_rg_planilha.php'>Planilha</a>&nbsp;&nbsp;";
	echo "<a href='login_unico_rg_planilha_retorno.php' >Retorno Planilha</a>&nbsp;&nbsp;";
	echo "<a href='login_unico_rg_retorno.php'>Retorno para Revenda</a>&nbsp;&nbsp;";
	echo "</div>";
	*/
	}
?>

<br>
<?}?>
