<?$login_posto = $cook_posto;?>
<html><head>
<title>Telecontrol - <?=$title;?></title>
<link type="text/css" rel="stylesheet" href="css/tc_lu.css">
<link type="text/css" rel="stylesheet" href="css/estilo.css">
<style>
.Destaque {font-size: 12pt;font-weight: bold;color: #000000;}
.Label {font-family: Arial;font-size: 10px;color: #000000;}
.Menu_Conteudo {font-family: Arial;font-size: 8pt;font-weight: normal;color:#FFF}
.Conteudo {font-family: Arial;font-size: 8pt;font-weight: normal;}
.Espaco{BACKGROUND-COLOR: #FFFFFF; width: 25px;text-align:center;}
.Erro{BORDER-RIGHT: #990000 1px solid;BORDER-TOP: #990000 1px solid;FONT: 10pt Arial ;COLOR: #ffffff;BORDER-LEFT: #990000 1px solid;BORDER-BOTTOM: #990000 1px solid;BACKGROUND-COLOR: #FF0000;}
.Caixa{BORDER-RIGHT: #6699CC 1px solid;BORDER-TOP: #6699CC 1px solid;FONT: 8pt Arial ;BORDER-LEFT: #6699CC 1px solid;BORDER-BOTTOM: #6699CC 1px solid;BACKGROUND-COLOR: #FFFFFF;}
.D{FONT-FAMILY:Arial;FONT-SIZE:10px;FONT-WEIGHT:normal;COLOR:#777777;}
#Menu{border-bottom:#485989 1px solid;}
acronym {color:#FF9900;cursor: help;}
img{border:0px;}
.Caixa{
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BORDER:           #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	FONT-SIZE:        10px;
	FONT-FAMILY:      Verdana;
}
.aviso{
	color:#6C6C6C;
	font-size:12px;
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
	color: #000099;
}
.TabelaRevenda th{
	text-align: left;
}
#dest {
	background-color: #464e73;
	color: #FFFFFF;
	padding: 3px 0px 3px 0px;
	font-size: 16px;
	margin: 3px 0px 3px 0px;
	text-align: center;
	width: 100%;
	font-weight:bold;
}
</style>
<script src="js/jquery-1.1.2.js"          type="text/javascript"></script>
<script type="text/javascript">
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
</script>
</head>
<body>
<table width='100%' border='0' cellspacing='0' align='center' cellpadding='2' background='helpdesk/imagem/fundo_dh5.jpg' >
<tr height='83'>
	<td width='100%' align='left' >&nbsp;</td>
		<td align='left' nowrap >
		<?if (strlen($cook_login_simples)==0) {?>
			<a href='login_unico.php' style='color:#FFF'>Início</font></a>&nbsp;|&nbsp;
		<?}?>
		<?
$televendas  = "<B>CENTRAL DE ATENDIMENTO</B><BR><BR>
<B><I>São Paulo</I></B><BR>
(11) 4063-4230<BR><BR>

<B><I>Rio de Janeiro</I></B><BR>
(21) 4063-4180<BR><BR>

<B><I>Curitiba</I></B><BR>
(41) 4063-9872<BR><BR>

<B><I>Florianópolis</I></B><BR>
(48) 4052-8762<BR><BR>

<B><I>Belo Horizonte</I></B><BR>
(31) 4062-7401<BR><BR>

<B><I>Caxias do Sul</I></B><BR>
(54) 4062-9112<BR><BR>

<B>Email:</B> distribuidor@telecontrol.com.br";
		?>
		<a href='#' rel="televendas" title="<? echo $televendas; ?>" style='color:#FFF'>Televendas</font></a>&nbsp;|&nbsp;

		<a href='lv_pedido.php' style='color:#FFF'>Minhas Compras</font></a>&nbsp;|&nbsp;
			<a href='http://www.telecontrol.com.br' style='color:#FFF'>Sair</font></a>&nbsp;
		</td>
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
			<a href='login_unico.php' style='color:#FFF'>Início</font></a>&nbsp;|&nbsp;
		<?}?>
		<a href='lv_pedido.php' style='color:#FFF'>Minhas Compras</font></a>&nbsp;|&nbsp;
			<a href='http://www.telecontrol.com.br' style='color:#FFF'>Sair</font></a>&nbsp;
		</td>
	</tr>
</table>
-->

<div  style="position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;"><table id='div_carregando'  style="border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);" ><tbody><tr><td><b>Carregando...</b></td></tr></tbody></table></div>
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
		<span id="<?if($aba=="2") echo "tab0_view_title";else echo "tab1_view_title";?>">Lote de Revenda</span>
	</li>
	
	<?  if($login_posto==4311){ ?>

		<li class="tab selectedtab_r">&nbsp;</li>
		<li class="tab unselectedtab_l">&nbsp;</li>

		<li id="tab1_view" class="<?if($aba=="3") echo "tab selectedtab";else echo "tab unselectedtab";?>" style="display:block" onclick="document.location='estoque_consulta.php'">
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
	if($aba==1){
	echo "<div align='left'>&nbsp;&nbsp;";
	echo "<a href='login_unico_alterar_senha.php'>Alterar Senha</a>&nbsp;&nbsp;";
	echo "<a href='login_unico_alterar_email.php'>Alterar Email</a>&nbsp;&nbsp;";
	if($login_master =='t') echo "<a href='login_unico_cadastro.php?t=lu'>Cadastro de Usuários</a>&nbsp;&nbsp;";
	echo "<a href='lv_completa.php'>Loja Virtual</a>";
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
	if($aba==3){
	echo "<div align='left'>&nbsp;&nbsp;";
	echo "<a href='estoque_consulta.php'>Consulta de Estoque</a>&nbsp;&nbsp;";
	echo "<a href='peca_localizacao.php'>Localização de Peças</a>&nbsp;&nbsp;";
	echo "<a href='nf_entrada.php'>NF de Entrada</a>&nbsp;&nbsp;";
	//echo "<a href='login_unico_alterar_email.php'>Alterar Email</a>&nbsp;&nbsp;";
	//if($login_master =='t') echo "<a href='login_unico_cadastro.php?t=lu'>Cadastro de Usuários</a>&nbsp;&nbsp;";
	//echo "<a href='lv_completa.php'>Loja Virtual</a>";
	echo "</div>";
	}
?>

<br>
<?}?>
