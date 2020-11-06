<? /* Tanto c�digo para uma linha s�... */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
 /* include 'autentica_usuario.php';

if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico) > 0) {
		include 'login_unico_autentica_usuario.php';
		$login_fabrica = 10;
	}elseif (strlen($cook_fabrica)==0 AND strlen($cook_login_simples) > 0) {
		include 'login_simples_autentica_usuario.php';
	}else{
		include 'autentica_usuario.php';
}
*/
$sql = "SELECT regra_loja_virtual
		FROM tbl_configuracao
		WHERE fabrica = 10";
$res_conf = pg_exec($con, $sql);
$resultado = pg_numrows($res_conf);
if ($resultado > 0){
	$regra_loja_virtual = trim(pg_result($res_conf,0,regra_loja_virtual));
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<!--
/*
	@link:		http://www.telecontrol.com.br/lv_forma_pago.php
	@access:	public
	@author:	Telecontrol
				Manuel L�pez
	@copyright:	2008 - Telecontrol Networking
	@internal:	Televendas Loja Virtual - Informa��o de Formas de Pagamento
	@name:		Formas de Pagamento
	@version:	1.0�

	2008/10/28	Inicio, inclus�o de texto
	2008/10/29	Formata��o de texto
	...

*/ -->
<html>
<head>
<title>Telecontrol - Formas de Pagamento</title>

<script type="text/javascript"  src="js/gradient.js"></script>
<script type="text/javascript"  src="js/niftycube.js"></script>

<script type="text/javascript">
	window.onload=function(){
		Nifty("DIV.bradesco","all transparent");
		Nifty("td.cajatexto","tl br transparent");
	}
</script>

<style type="text/css">
/*	Definir estilo do t&iacute;tulo e os textos	*/
	body {
		border:			none;
		margin:			0px;
		padding:		0px;
		color:			#666;
		background:		white;
		font-family:	arial,freesans,garuda,helvetica,verdana,sans-serif;
		font-weight:	normal;
		font-size:		11px;
		vertical-align:	middle;
	}

	H1 {
		position:	fixed;
		width:		400px;
		top:		0px;
		left:		0px;
		margin:		0px;
		padding-top:12px;
		color:		#f5d011;	/* Amarelo-laranja Telecontrol */
		height:		30px;
		text-align:	center;
		font-size:	18px;
		font-weight:bold;
		background-position:left top;
		background-image: url('./imagens/barra_dg_azul_tc.jpg');
		background-repeat: repeat-x;
		z-index:	11;
	}

	td.cajatexto {
		position:		relative;
		margin-left:	10px;
		margin-right:	10px;
		margin-top:		4px;
		padding:		5px;
		background:		#dbe4ec;
		font-size:		12px;
		font-weight:	bold;
		text-align:		left;
		width:			370px;
		max-height:		2.5em;
		vertical-align:	middle;
		z-index:		0;
	}

	.texto {
		background:		white;
		color:			#666;
		margin-right:	20px;
		padding:		5px 25px 5px 15px;
		font-family:	arial, freesans, garuda, helvetica, verdana, sans-serif;
		font-size:		11px;
		text-align:		left;
	}

	TABLE {
		margin-left:	10px;
		table-layout:	fixed;
		width:			380px;
	}

	td {
		width:			360px;
		min-height:		3em;
		vertical-align:	middle;
	}

/*	Definir estilo dos links	*/
	a:link, a:visited {
		text-decoration:none;
		color:			#00A;
		font-weight:	bold;
		border:			none;
	}

	a:hover {
		border: 1px #AAA;
		text-decoration:underline;
		color: #6b7290;
	}

/*	Outros	*/
	B {
		color: #333;
	}

	IMG {
		display:		inline;
		top:			-5px;
		padding-right:	 5px;
		vertical-align:	middle;
	}

	.aviso {
		border: 2px solid #6b7290;
		padding: 3px 10px 3px 10px;
		background-color: white;
		color: red;
	}

	DIV.bradesco {
        position:		relative;
	    padding-left:	14px;
	    padding-top:    5px;
	    padding-bottom:	3px;
		margin-left:	25px;
		width:          260px;
		background:		#FFA6A6;
		z-index:        0;
	}
	DIV.bradesco P {
	text-align:center;
	font-size:15px;
	font-weight:bold;
	color:#B00;
	height: 1.5em;
	margin:0;
	}
	DIV.bradesco B {color:white;}
	DIV.bradesco STRONG {
		display:		inline-block;
		width:			15ex;
		font-weight:	normal;
		color:          white; /* #222; */
}

</style>
</HEAD>

<BODY>
<!-- T&iacute;tulo -->
<H1>FORMAS DE PAGAMENTO</H1>

<!-- <P class='cajatexto'>
	P�gina tempor�riamente fora de servi�o...</p> -->

<DIV style='height: 45px;'>&nbsp;</DIV>

<TABLE  border="0" cellpadding="0" cellspacing="1">
	<TR>
		<TD style='height: 32px;'>
			<DIV class='aviso'
				 style='text-align: center; font-weight: bold;
						margin:2px 20px 6px 20px;'>
			<? echo $regra_loja_virtual ?></DIV>
		</TD>
	</TR>

	<TR>
		<TD class='cajatexto'>
		<DIV class='logo'>
		<IMG SRC="imagens/boleto_fp.jpg" ALT="BOLETO">
		Boleto Banc&aacute;rio</DIV>
		</TD>
	</TR>

	<TR>
		<TD class='texto'>
		<!-- Imprima o Boleto e pague em qualquer banco ou no <B>Internet Banking</B> de sua prefer&ecirc;ncia. --><p>
		O pagamento de suas compras realizadas na <B>Telecontrol</B>, pode ser efetuado utilizando <U>Boleto Banc�rio</U>, que pode ser pago diretamente pela Internet em seu <B>Bankline</B> ou <!-- impresso e pago--> em qualquer ag�ncia banc�ria ou nos equipamentos de auto-atendimento.<p>
		<B>Para esta op��o ser� acrescido o valor de R$ 1,46 referente a taxa de cobran�a efetuada pelo banco.</B> <p>
<!--
		<B>Obs.: Seu pedido somente ser� expedido ap�s a compensa��o do pagamento do boleto que ocorre, normalmente, de um dia para outro. Pagamento em finais de semana e/ou feriados, a data do pagamento � o pr�ximo dia �til.</B>
-->
	</TD>
	</TR>

<!--
	 <TR>
		<TD class='cajatexto'>
			<DIV class='bdn'>
			<IMG SRC="./imagens/logo_bdn_pq.png" ALT="Bradesco">
			Pagamento F&aacute;cil Bradesco
		</DIV>
		</TD>
	</TR>
	<TR>
		<TD class='texto'>
		Op&ccedil;&atilde;o apenas para clientes do Bradesco com acesso ao <SPAN CLASS="achtung">Internet Banking</SPAN>. D&eacute;bito em conta corrente.
		</TD>
	</TR>
-->
	<TR>
		<TD class='cajatexto'>
		<DIV class='bdn'>
<!--		<IMG SRC="./imagens/logo_bdn_pq.png" ALT="Bradesco"> -->
		Dep&oacute;sito banc�rio ou Transfer�ncia</DIV>
	</TD>
	</TR>

	<TR>
		<TD class='texto'>
		<B>&nbsp;Aten&ccedil;&atilde;o: </B>
		Nos casos de dep&oacute;sito/transfer&ecirc;ncia, para libera&ccedil;&atilde;o do pedido, envie o comprovante de dep&oacute;sito ou transfer&ecirc;ncia de acordo com uma das op&ccedil;&otilde;es abaixo:<br>

		<UL>
			<LI>Print da tela de transfer&ecirc;ncia, via e-mail <B>lojavirtual@telecontrol.com.br</B>.
			<LI>C&oacute;pia via scanner do comprovante de dep&oacute;sito ou transfer&ecirc;ncia, via e-mail <B>lojavirtual@telecontrol.com.br</B>.
			<LI>Fax do comprovante de dep&oacute;sito ou transfer&ecirc;ncia atrav&eacute;s do n� <B>(14) 3413-6588</B>, em HOR&Aacute;RIO COMERCIAL.
		</UL>

		<B>Obs.: </B> No caso de dep&oacute;sitos em cheque ou em caixa eletr&ocirc;nico, obrigatoriamente ser&aacute; aguardada a compensa&ccedil;&atilde;o para procedermos com a expedi&ccedil;&atilde;o.<br>
		O prazo para DEP&Oacute;SITO &eacute; de 1 (um) dia &uacute;til. Passado este prazo a reserva &eacute; cancelada autom&aacute;ticamente.
		<br><br>
		<DIV CLASS='gradient FFA6A6 BE0000 vertical bradesco'>
			<P>Bradesco</P>
			<B><strong>Ag&ecirc;ncia:</strong>3054<br>
			<strong>Conta Corrente:</strong>10257-1<br>
			<strong>Favorecido:</strong>Telecontrol Networking Ltda.</B>
		</DIV>
		</TD>
	</TR>
</TABLE>
</BODY>
</HTML>