<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<HTML>
 <HEAD>
  <TITLE>Cobrança</TITLE>

 <BODY style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px; margin:0;">
 <TABLE WIDTH="100%" border="1" bordercolor='#D9E2EF' cellpadding='0' cellspacing='0' align='center' style=' font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;'>
 <TR>
	<TD>
		<TABLE WIDTH="100%" border="0" style=' font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px;'>
		<TR>
			<TD WIDTH="268" HEIGHT="56" align="center"><IMG SRC="img/logo.jpg" WIDTH="268" HEIGHT="56" BORDER="0"></TD>
			<TD align="center">SISTEMA DE COBRANÇA</TD>
		</TR>
		</TABLE>
	</TD>
 </TR>
 <TR>
	<TD>
 <? 
include 'banco.php';


$logar = $_GET["logar"];
if ($logar=="sair"){
setcookie ("logado", "", time()+3600);
$logado = "";
}else{
$logado = $_COOKIE ["logado"];
}

if ($logar=="logar"){
	$login = $_POST["login"];
	$senha = $_POST["senha"];
		if ($login==""){echo "<br><b>USUÁRIO INVÁLIDO</b><br>";}
		if ($senha==""){echo "<br><b>SENHA INVÁLIDA</b><br>";}

			$sql = "SELECT id_usuario, nome, login, senha, nivel FROM tbl_cobranca_usuario where login='$login' and senha='$senha'";
			$res = pg_exec($con,$sql);					

				if(pg_numrows($res)> 0){
					$id_usuario=pg_result($res,0,id_usuario);
					$nome=pg_result($res,0,nome);
					$login=pg_result($res,0,login);
					$senha=pg_result($res,0,senha);
					$nivel=pg_result($res,0,nivel);
					
					setcookie ("logado", $id_usuario, time()+3600);
					$logado = $id_usuario;

				}else{
				echo "<br><b>USUÁRIO OU SENHA INVÁLIDO</b><br>";
				}

}

if ($logado==""){
					?>
						<FORM METHOD=POST ACTION="index.php?logar=logar">
							Usuário:<INPUT TYPE="text" NAME="login"><br>
							Senha:<INPUT TYPE="text" NAME="senha"><br>
							<INPUT TYPE="submit" value="enviar">
						</FORM>
					<?
}else{
				$sql = "SELECT id_usuario, nome, login, senha, nivel FROM tbl_cobranca_usuario where id_usuario='$logado'";
				$res = pg_exec($con,$sql);					

				if(pg_numrows($res)> 0){
					$id_usuario=pg_result($res,0,id_usuario);
					$nome=pg_result($res,0,nome);
					$login=pg_result($res,0,login);
					$senha=pg_result($res,0,senha);
					$nivel=pg_result($res,0,nivel);
				}
						
						setcookie ("logado", $id_usuario, time()+3600);

	if ($nivel=="1"){	
		echo "<TABLE WIDTH='100%' bgcolor='#D9E2EF' border='0' cellpadding='5' cellspacing='0' align='center' style=' font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;'><TR><TD  align='center' valign='middle'><a href='busca.php'>COBRANÇA</a></TD><TD  align='center' valign='middle'><a href='relatorio.php'>RELATÓRIOS</a></TD><TD  align='center' valign='middle'><a href='adm_usuarios.php'>USUÁRIOS</a></TD><TD  align='center' valign='middle'><a href='index.php?logar=sair'>SAIR</a></TD></TR></TABLE>";
	}else{
			if ($nivel=="2" ){
				echo "<TABLE WIDTH='100%' bgcolor='#D9E2EF' border='0' cellpadding='5' cellspacing='0' align='center' style=' font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;'><TR><TD  align='center' valign='middle'><a href='busca.php'>COBRANÇA</a></TD><TD  align='center' valign='middle'><a href='relatorio.php'>RELATÓRIOS</a></TD><TD  align='center' valign='middle'><a href='envia_arquivo.php'>INCLUIR DADOS</a></TD><TD  align='center' valign='middle'><a href='adm_usuarios.php'>USUÁRIOS</a></TD><TD  align='center' valign='middle'><a href='index.php?logar=sair'>SAIR</a></TD></TR></TABLE>";
			}else{
				if ( $nivel=="3" or $nivel=="5"){
				echo "<TABLE WIDTH='100%' bgcolor='#D9E2EF' border='0' cellpadding='5' cellspacing='0' align='center' style=' font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;'><TR><TD  align='center' valign='middle'><a href='busca.php'>COBRANÇA</a></TD><TD  align='center' valign='middle'><a href='relatorio.php'>RELATÓRIOS</a></TD><TD  align='center' valign='middle'><a href='abrir_nota.php'>ABRIR NOTA</a></TD><TD  align='center' valign='middle'><a href='envia_arquivo.php'>INCLUIR DADOS</a></TD><TD  align='center' valign='middle'><a href='adm_usuarios.php'>USUÁRIOS</a></TD><TD  align='center' valign='middle'><a href='index.php?logar=sair'>SAIR</a></TD></TR></TABLE>";
				}else{
					if ($nivel=="4"){
						echo "<TABLE WIDTH='100%' bgcolor='#D9E2EF' border='0' cellpadding='5' cellspacing='0' align='center' style=' font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px;'><TR><TD  align='center' valign='middle'><a href='relatorio.php'>RELATÓRIOS</a></TD  align='center' valign='middle'><a href='adm_usuarios.php'>USUÁRIOS</a><TD  align='center' valign='middle'><a href='index.php?logar=sair'>SAIR</a></TD></TR></TABLE>";
					}
				}
			}
	}
}
?>
</TD>
 </TR>
 <TR>
	<TD>
