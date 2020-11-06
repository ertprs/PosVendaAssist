<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">

<? include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php"; ?>

<html>
<head>
<title> <?echo strtoupper(traduz("configuracoes",$con,$cook_idioma));?> </title>
<meta http-equiv=pragma content=no-cache>

	<!-- LINK PARA O CSS -->
	<link href="css/basico.css" rel="stylesheet" type="text/css" />

<style type='text/css'>
body {
	padding: 0px,0px,0px0px;
	text-align: left;
}

h6 {
	font: 12px Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
	background: #FFFFFF;
}
</style>

</head>

<body>
<img src='imagens/explorer.gif'>
<table width='400px' border='0'>
	<tr>
		<td><h6><br>
		<? 
				fecho ("para.configurar.o.ie",$con,$cook_idioma).":";
				echo "<br>";
				fecho ("1.va.em.ferramentas.opcoes.da.internet",$con,$cook_idioma);
				?>
				</i></h6>
		</td>
	</tr>
	<tr>
		<td><img src='imagens/help_tela01.gif'></td>
	</tr>
	<tr>
		<td>
			<h6>
				<br />
				<?
				fecho ("2.na.sub.divisao.arquivos.de.internet.temporarios.clique.no.botao.configuracoes",$con,$cook_idioma);
				?>
			</h6>
		</td>
	</tr>
	<tr>
		<td>
			<img src='imagens/help_tela02.gif'>
		</td>
	</tr>
	<tr>
		<td>
			<h6>
				<br />
				<?
				fecho ("e.em.verificar.se.ha.versoes.mais.atualizadas.das.paginas.armazenadas.escolha.a.cada.visita.a.pagina",$con,$cook_idioma);
				?>
		</td>
	</tr>
	<tr>
		<td>
			<h6>
				<br />
				<?
				fecho ("3.no.espaco.em.disco.a.ser.usado.configure.em.1mb.clique.em.ok",$con,$cook_idioma);
				?>
			</h6>
		</td>
	</tr>
	<tr>
		<td><img src='imagens/help_tela03.gif'></td>
	</tr>
	<tr>
		<td>
			<h6>
				<br>
<?
				fecho ("4.ainda.em.opcoes.da.internet.clique.na.aba..avancadas.e.certifique.se.de.que.a.opcao.nao.salvar.paginas.criptografadas.esta.selecionada.clique.em.ok.e.esta.pronto",$con,$cook_idioma);
?>
			</h6>
		</td>
	</tr>
	<tr>
		<td><img src='imagens/help_tela04.gif'></td>
	</tr>
</table>
<hr />
<?
//<center><a href='configuracao_ns.php'>Configuração para o Netscape: <img src='imagens/netscape.gif'></a></center>
?>
</body>
</html>
