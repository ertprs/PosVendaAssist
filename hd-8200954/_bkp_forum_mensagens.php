<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$title = " F O R U M ";

$layout_menu = 'tecnica';

include "cabecalho.php";
include "javascript_pesquisas.php"
?>

<style type='text/css'>

.forum_cabecalho {
	padding: 5px;
	background-color: #FFCC00;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	text-align: center;
	}

.texto {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	font-weight: bold;
	color: #596D9B;
	text-align: justify;
	}

.corpo {
	padding: 5px;
	font-family: arial;
	font-size: 12px;
	color: #596D9B;
	text-align: justify;
	}

.forum_claro {
	padding: 3px;
	background-color: #CED7E7;
	color: #596D9B;
	text-align: center;
	}


.forum_escuro {
	padding: 3px;
	background-color: #D9E2EF;
	color: #596D9B;
	text-align: center;
	}

a:link.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:visited.menu {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.menu {
	color: #FFCC00;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
}

a:link.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.forum {
	color: #63798D;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.forum {
	color: #0000FF;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:link.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

a:visited.botao {
	padding: 20px,20px,20px,20px;
	background-color: #ffcc00;
	color: #000000;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	text-decoration: none;
}

a:hover.botao {
	padding: 20px,20px,20px,20px;
	background-color: #596d9b;
	color: #ffffff;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}

</style>
<br />
<table width='700px' border='0' cellpadding='0' cellspacing='3'>
<tr>
	<td>
		<img src='imagens/forum_logo.gif'>
	</td>
	<td class='texto'>
		<a href='#' class='nav'><<</a>&nbsp;<a href='#' class='nav'><</a> &nbsp;<a href='#' class='nav'>&nbsp;01</a>| <a href='#' class='nav'>02</a>| <a href='#' class='nav'>03</a>|&nbsp; <a href='#' class='nav'>></a>&nbsp;<a href='#' class='nav'>>></a>
	</td>
</tr>

<tr>
	<td valign='top'>
		<table width='150px' border='0' cellpadding='0' cellspacing='3' valign='top'>
		<tr>
			<td>
				<img src='imagens/forum_home.gif'>
			</td>
			<td>
				<a href='#' class='menu'>PÁGINA INICIAL</a>
			</td>
		</tr>
		<tr>
			<td>
				<img src='imagens/forum_carta.gif'>
			</td>
			<td>
				<a href='#' class='menu'>ENVIAR MENSAGEM</a>
			</td>
		</tr>
		</table>
	</td>
	<td>
		<table width='550px' border='0' cellpadding='0' cellspacing='3'>
		<tr class='forum_cabecalho'>
			<td>
				TOPICO: título do tópico vai aqui
			</td>
		</tr>
		<tr class='forum_claro'>
			<td style='text-align: left;'>
				<table width='100%' border='0' cellpadding='0' cellspacing='3'>
				<tr class='texto'>
					<td>
						Marcos Teruo
					</td>
					<td>
						Defeito no Joystick
					</td>
					<td>
						04.08.2004 - 19:30 h
					</td>
				</tr>
				<tr class='corpo'>
					<td colspan='3'>
						nononononononononon nononnonon non nononono nnononononnoono nonononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.<br />
						nononononononononon nononnonon non nononono nonono nonon ononon noononononononnononon nononononononon nonononn onon nonon on ono nononnon onon oono non ononono nononono no non ononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr class='forum_escuro'>
			<td style='text-align: left;'>
				<table width='100%' border='0' cellpadding='0' cellspacing='3'>
				<tr class='texto'>
					<td>
						Marcos Teruo
					</td>
					<td>
						Defeito no Joystick
					</td>
					<td>
						04.08.2004 - 19:30 h
					</td>
				</tr>
				<tr class='corpo'>
					<td colspan='3'>
						nononononononononon nononnonon non nononono nnononononnoono nonononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.<br />
						nononononononononon nononnonon non nononono nonono nonon ononon noononononononnononon nononononononon nonononn onon nonon on ono nononnon onon oono non ononono nononono no non ononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr class='forum_claro'>
			<td style='text-align: left;'>
				<table width='100%' border='0' cellpadding='0' cellspacing='3'>
				<tr class='texto'>
					<td>
						Marcos Teruo
					</td>
					<td>
						Defeito no Joystick
					</td>
					<td>
						04.08.2004 - 19:30 h
					</td>
				</tr>
				<tr class='corpo'>
					<td colspan='3'>
						nononononononononon nononnonon non nononono nnononononnoono nonononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.<br />
						nononononononononon nononnonon non nononono nonono nonon ononon noononononononnononon nononononononon nonononn onon nonon on ono nononnon onon oono non ononono nononono no non ononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr class='forum_escuro'>
			<td style='text-align: left;'>
				<table width='100%' border='0' cellpadding='0' cellspacing='3'>
				<tr class='texto'>
					<td>
						Marcos Teruo
					</td>
					<td>
						Defeito no Joystick
					</td>
					<td>
						04.08.2004 - 19:30 h
					</td>
				</tr>
				<tr class='corpo'>
					<td colspan='3'>
						nononononononononon nononnonon non nononono nnononononnoono nonononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.<br />
						nononononononononon nononnonon non nononono nonono nonon ononon noononononononnononon nononononononon nonononn onon nonon on ono nononnon onon oono non ononono nononono no non ononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr class='forum_claro'>
			<td style='text-align: left;'>
				<table width='100%' border='0' cellpadding='0' cellspacing='3'>
				<tr class='texto'>
					<td>
						Marcos Teruo
					</td>
					<td>
						Defeito no Joystick
					</td>
					<td>
						04.08.2004 - 19:30 h
					</td>
				</tr>
				<tr class='corpo'>
					<td colspan='3'>
						nononononononononon nononnonon non nononono nnononononnoono nonononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.<br />
						nononononononononon nononnonon non nononono nonono nonon ononon noononononononnononon nononononononon nonononn onon nonon on ono nononnon onon oono non ononono nononono no non ononon o nonono no non ononono non o on ononononono o onononon on ono  no non ononono o on on onon on onononon onon ononononon.
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align='right'>
				<a href='#' class='botao'>Responder a Este Tópico</a>
			</td>
		</tr>
	</table>
	</td>
</tr>

</table>

<? include "rodape.php"; ?>