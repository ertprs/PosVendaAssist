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
</style>
</head>
<body>
<table width='100%'  border='0'height='83' cellpadding='0' cellspacing='0' border='2' background='helpdesk/imagem/fundo_dh5.jpg'>
<tr bgcolor='29304D' valign='middle'>
		<td nowrap  align='right'>
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

<center>
<br>
