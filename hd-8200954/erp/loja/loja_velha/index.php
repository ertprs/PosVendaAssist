<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<? 

if($acao=='enviar'){
//===================BUSCA CAMPOS=============================================//

		$liq1=$_POST['liquidificador1'];
		$liq1=$_POST['liquidificador1'];
		$liq2=$_POST['liquidificador2'];
		$liq3=$_POST['liquidificador3'];
		$caf1=$_POST['cafeteira1'];
		$caf2=$_POST['cafeteira2'];
		$caf3=$_POST['cafeteira3'];
		$nome=$_POST['nome'];
		$endereco=$_POST['endereco'];
		$bairro=$_POST['bairro'];
		$telefone=$_POST['telefone'];
		$cidade=$_POST['cidade'];
		$cep=$_POST['cep'];
		$email=$_POST['email'];
		$obs=$_POST['obs'];


//-=============================FUNÇÃO VALIDA EMAIL==============================-//

function validatemail($email=""){ 
    if (preg_match("/^[a-z]+([\._\-]?[a-z0-9]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
//validacao anterior [a-z0-9\._-]
		$valida = "1"; 
    } 
    else { 
        $valida = "0"; 
    } 
    return $valida; 
} 


$email = $_POST['email'];
if(strlen($email)>0){

	if (validatemail($email)) { 


		//ENVIA EMAIL PARA TULIO

		$email_origem  = "$email";
		$email_destino = "tulio@telecontrol.com.br";
		$assunto       = "Compra de Produtos";
		$corpo        .="<br>Nome: $nome \n";
		$corpo        .="<br>Endereco: $endereco \n";
		$corpo        .="<br>Bairro: $bairro \n";
		$corpo        .="<br>Cidade: $cidade \n";
		$corpo        .="<br>CEP: $cep \n";
		$corpo        .="<br>Email: $email \n\n";
		$corpo        .="<br>Telefone: $telefone \n";
		$corpo        .="<br>LIQUIDIFICADORES:<br> Liquidificador 1= $liq1<br> Liquidificador 2= $liq2<br> Liquidificador 3= $liq3 \n";
		$corpo        .="<br>CAFETEIRAS:<br> Cafeteira 1= $caf1<br> Cafeteira 2= $caf2<br>  Cafeteira 3= $caf3 \n";
		$corpo        .="<br>OBS.:<br> $obs \n";
		$corpo        .="<br>_______________________________________________\n";


		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

//$corpo = $body_top.$corpo;

		if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
			$msg = "<br>Foi enviado um email para: ".$email.", e nele há um link para confirmar a validade do email.<br>Logo após a confirmação o sistema estará liberado!<br>";
		}else{
			$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";

		}

	echo "<script>alert('"._("Pedido enviado com sucesso")."');</script>\n";
	
	}else{
		echo "<script>alert('"._("E-mail inválido")."');</script>\n";
	}
}
}

?>

<HTML>
<HEAD>
<TITLE>--((AKACIA))--</TITLE>
</HEAD>

<BODY>
<TABLE width="100%" border="0" cellspacing="0" cellpadding='0' >
<TR>
	<TD><img src="logo.gif" border="0"></TD>
	<td width="100%" background="fundo_cabecalho.gif" nowrap align="right">
</TR>
<TR nowrap align="top" bgcolor="">
	<TD  colspan="2">
		<FORM METHOD=POST ACTION="index.php?acao=enviar">
		<table width='100' align="center">
		<TR>
			<TD colspan="2" ><B><FONT COLOR="#3333CC"><CENTER>PRODUTOS</CENTER></FONT></B></TD>
		</TR>
		<TR>
			<TD colspan="2"><CENTER><B>LIQUIDIFICADOR BRITANIA SILENCIUM III - 2V+PULSAR</B></CENTER></TD>
		</TR>
		<TR>
			<TD align="left"><img src="liquidificador1.jpg"></TD>
			<TD nowrap><FONT SIZE="" COLOR="#FF6600">	<B>VALOR</B><br>
														À vista R$ 	59,58<br>
														2x sem juros R$ 29,79<br>
													Qtde.: <INPUT TYPE="text" NAME="liquidificador1" size="3" value="0">
			</FONT></TD>
		</TR>
		<TR>
			<TD>&nbsp;</TD>
		</TR>
		<TR>
			<TD colspan="2"><CENTER><B>LIQUIDIFICADOR BRITANIA DIAMANTE</B></CENTER></TD>
		</TR>
		<TR >
			<TD align="left"><img src="liquidificador2.jpg"></TD>
			<TD nowrap><FONT SIZE="" COLOR="#FF6600">	<B>VALOR</B><br>
														À vista R$ 	66,63<br>
														2x sem juros R$ 33,31<br>
														3x sem juros R$ 22,21<br>
													Qtde.: <INPUT TYPE="text" NAME="liquidificador2" size="3" value="0">
			</FONT></TD>
		</TR>
		<TR>
			<TD>&nbsp;</TD>
		</TR>
		<TR>
			<TD colspan="2"><CENTER><B>LIQUIDIFICADOR BRITANIA - BELLAGIO 03 VEL</B></CENTER></TD>
		</TR>
		<TR >
			<TD align="left"><img src="liquidificador3.jpg"></TD>
			<TD nowrap><FONT SIZE="" COLOR="#FF6600">	<B>VALOR</B><br>
														À vista R$ 	86,93<br>
														2x sem juros R$ 43,47<br>
														3x sem juros R$ 28,98<br>
														4x sem juros R$ 21,73<br>
													Qtde.: <INPUT TYPE="text" NAME="liquidificador3" size="3" value="0">
			</FONT></TD>
		</TR>
		<TR>
			<TD colspan="2"><CENTER><B>CAFETEIRA BRITANIA 14 CAFES - CP14 PRETA</B></CENTER></TD>
		</TR>
		<TR>
			<TD align="left"><img src="cafeteira1.jpg"></TD>
			<TD nowrap><FONT SIZE="" COLOR="#FF6600">	<B>VALOR</B><br>
														À vista R$ 	50,66<br>
														2x sem juros R$ 25,33<br>
													Qtde.: <INPUT TYPE="text" NAME="cafeteira1" size="3" value="0">
			</FONT></TD>
		</TR>
		<TR>
			<TD>&nbsp;</TD>
		</TR>
		<TR>
			<TD colspan="2"><CENTER><B>CAFETEIRA BRITANIA 36 CAFES - CP36 PRETA</B></CENTER></TD>
		</TR>
		<TR >
			<TD align="left"><img src="cafeteira2.jpg"></TD>
			<TD nowrap><FONT SIZE="" COLOR="#FF6600">	<B>VALOR</B><br>
														À vista R$ 	93,00<br>
														2x sem juros R$ 	46,50<br>
														3x sem juros R$ 	31,00<br>
														4x sem juros R$ 	23,25<br>
													Qtde.: <INPUT TYPE="text" NAME="cafeteira2" size="3" value="0">
			</FONT></TD>
		</TR>
		<TR>
			<TD>&nbsp;</TD>
		</TR>
		<TR>
			<TD colspan="2"><CENTER><B>CAFETEIRA BRITANIA CB27</B></CENTER></TD>
		</TR>
		<TR >
			<TD align="left"><img src="cafeteira3.jpg"></TD>
			<TD nowrap><FONT SIZE="" COLOR="#FF6600">	<B>VALOR</B><br>
														À vista R$ 	72,67<br>
														2x sem juros R$ 36,34<br>
														3x sem juros R$ 24,22<br>
													Qtde.: <INPUT TYPE="text" NAME="cafeteira3" size="3" value="0">
			</FONT></TD>
		</TR>
		<TR>
			<table width='100' align="center">
		<TR>
			<TD colspan="4"><B><FONT COLOR="#6633FF"><CENTER>DADOS PESSOAIS</CENTER></FONT></B></TD>
		</TR>
<!--===============FORMULARIO================================================ -->
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
			<TD>Tel:<INPUT TYPE="text" NAME="telefone" size="21"></TD>
			<TD>&nbsp;</TD>
		</TR>
				<TR align="left">
			<TD>Cidade:</TD>
			<TD><INPUT TYPE="text" NAME="cidade"></TD>
			<TD>CEP:<INPUT TYPE="text" NAME="cep" size="19"></TD>
			<TD>&nbsp;</TD>
		</TR>
		<TR>
			<TD>E-mail:</TD>
			<TD colspan="3"><INPUT TYPE="text" NAME="email" size="50"></TD>
		</TR>
		<TR>
			<TD>Obs:</TD>
			<TD colspan="3"><TEXTAREA NAME="obs" ROWS="5" COLS="38"></TEXTAREA></TD>
		</TR>
		<TR>
			<TD align="center" colspan="3"><INPUT TYPE="submit" name="compra"></TD>
		</TR>
		</TD>
		</table>
		</TR>
		</table>
		</FORM>
	</TD>
</TR>
</TABLE>
</BODY>
</HTML>
