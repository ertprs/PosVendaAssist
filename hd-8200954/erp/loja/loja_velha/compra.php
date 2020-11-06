<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>COMPRAS</TITLE>
</HEAD>
<?		$liq1=$_POST['liquidificador1'];
		$liq2=$_POST['liquidificador2'];
		$liq3=$_POST['liquidificador3'];
		$caf1=$_POST['cafeteira1'];
		$caf2=$_POST['cafeteira2'];
		$caf3=$_POST['cafeteira3'];



?>
<BODY bgcolor="#FFFFFF">
<TABLE width="100%" border="0" cellspacing="0" cellpadding='0'>
<TR>
	<TD colspan="2" bgcolor="eeeeee"><FONT SIZE="5" ><CENTER>Akacia</CENTER></FONT></TD>
</TR>
<TR>
	<TD colspan="2" bgcolor="eeeeee">&nbsp;<br></TD>
</TR>
<TR nowrap align="top" >
	<TD valign='top'>
		<table width='150' border = '0'cellspacing='0'cellpadding='0'>
		<TR>
			<TD bgcolor="#808080"><B><CENTER>MENU</CENTER></B></TD>
		</TR>
		<TR>
			<TD bgcolor="eeeeee"><a href='index.php'><B>Início</B></A></TD>
		</TR>
		<TR>
			<TD bgcolor="eeeeee"><a href='cafeteiras.php'><B>Cafeteiras</B></A></TD>
		</TR>
		<TR>
			<TD bgcolor="eeeeee"><a href='liquidificadores.php'><B>Liquidificadores</B></A></TD>
		</TR>
		<TR>
			<TD bgcolor="eeeeee"><a href='contato.php'><B>Contato</B></TD>
		</TR>
		</table>
	</TD>
	<TD >
		<table width='100'>
		<FORM METHOD=POST ACTION="" >
		<TR>
			<TD colspan="4"><B><FONT COLOR="#6633FF"><CENTER>DADOS PESSOAIS</CENTER></FONT></B></TD>
		</TR>
		<TR>
			<TD>Nome:</TD>
			<TD colspan="3"><INPUT TYPE="text" NAME="nome" size="50"></TD>
		</TR>
		<TR>
			<TD>Endereço:</TD>
			<TD colspan="3"><INPUT TYPE="text" NAME="endereco" size="50"></TD>
		</TR>
		<TR align="left">
			<TD>Bairro:</TD>
			<TD><INPUT TYPE="text" NAME="bairro"></TD>
			<TD>Tel:<INPUT TYPE="text" NAME="telefone" size="10"></TD>
			<TD>&nbsp;</TD>
		</TR>
		<TR>
			<TD>E-mail:</TD>
			<TD colspan="3"><INPUT TYPE="text" NAME="email" size="50"></TD>
		</TR>
		<TR>
			<TD>Obs:</TD>
			<TD colspan="3"><TEXTAREA NAME="obs" ROWS="5" COLS="40"></TEXTAREA></TD>
		</TR>
		<TR>
			<TD colspan="3"><?	if($liq1>0){echo"LIQUIDIFICADOR BRITANIA SILENCIUM III - 2V+PULSAR<br>  Qtde.: $liq1<br>";}
								if($liq2>0){echo"LIQUIDIFICADOR BRITANIA DIAMANTE<br>  Qtde.: $liq2<br>";}
								if($liq3>0){echo"LIQUIDIFICADOR BRITANIA - BELLAGIO 03 VEL<br>  Qtde.: $liq3<br>";}
								if($caf1>0){echo"CAFETEIRA BRITANIA 14 CAFES - CP14 PRETA - 2V+PULSAR<br>  Qtde.: $caf1<br>";}
								if($caf2>0){echo"CAFETEIRA BRITANIA 36 CAFES - CP36 PRETA<br>  Qtde.: $caf2<br>";}
								if($caf3>0){echo"CAFETEIRA BRITANIA CB27<br>  Qtde.: $caf3<br>";}
				?>
			</TD>
		</TR>
		<TR>
			<TD align="center" colspan="3"><INPUT TYPE="submit" name="Próximo"></TD>
		</TR>
		</FORM></TD>
		</table>
	</TD>
</TR>
</TABLE>
</BODY>
</HTML>
