<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';

$acao = $_GET["acao"];

if ($acao == "atualizar"){
	$id_usuario = $_POST["id_usuario"]; 
	$nome = $_POST["nome"]; 
	$login = $_POST["login"]; 
	$senha = $_POST["senha"]; 
	$nivel = $_POST["nivel"]; 


$sql = "UPDATE tbl_cobranca_usuario SET nome='$nome', login='$login', senha='$senha', nivel='$nivel' where id_usuario = $id_usuario";
$res = pg_exec($con,$sql);
$texto="<b><br><br>&nbsp;&nbsp;&nbsp;Altera��o efetuada com Sucesso</b>";
}


$sql = "SELECT nivel FROM tbl_cobranca_usuario where id_usuario='$logado'";
$res = pg_exec($con,$sql);					

if(pg_numrows($res)> 0){
	$nivel_logado=pg_result($res,0,nivel);
}

If ($nivel_logado == "5"){
	$sql = "SELECT id_usuario, nome, login, senha, nivel FROM tbl_cobranca_usuario";
	$res = pg_exec($con,$sql);
	$row = 0;
echo "<br><table border='1' bordercolor='#D9E2EF' style='font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;' align='center' cellpadding='5' cellspacing='0'>";
echo "<tr bgcolor='#D9E2EF' align='center'><td>NOME</td><td>USU�RIO</td><td>N�VEL</td></tr>";
while($row=pg_fetch_array($res)) {
					
					$id_usuario = $row["id_usuario"];
					$nome = $row["nome"];
					$login = $row["login"];
					$senha = $row["senha"];
					$nivel = $row["nivel"];
					echo "<tr><td><a href=adm_usuarios.php?id_usuario=".$id_usuario."&alterar=sim>". $nome ."</a></td><td><a href=adm_usuarios.php?id_usuario=".$id_usuario."&alterar=sim>". $login ."</a></td><td><a href=adm_usuarios.php?id_usuario=".$id_usuario."&alterar=sim>". $nivel ."</a></td></td></tr>";
					}

echo "</table><br><br><center><a href='incluir_usuario.php'>Incluir usu�rio</a></center>$texto<br><br>";

}

	$alterar = $_GET["alterar"]; 
if ($alterar=="sim" and $nivel_logado == "5"){
	$id_usuario = $_GET["id_usuario"]; 

			$sql = "SELECT id_usuario, nome, login, senha, nivel FROM tbl_cobranca_usuario where id_usuario = $id_usuario";
			$res = pg_exec($con,$sql);					

				if(pg_numrows($res)> 0){
					$id_usuario=pg_result($res,0,id_usuario);
					$nome=pg_result($res,0,nome);
					$login=pg_result($res,0,login);
					$senha=pg_result($res,0,senha);
					$nivel=pg_result($res,0,nivel);
				}
?>

<FORM METHOD=POST ACTION="adm_usuarios.php?acao=atualizar">
<INPUT TYPE="hidden" NAME="id_usuario" value="<?= $id_usuario ?>">
<table border="0" cellpadding="5" cellpadding="0" style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;" >
    <tr>
    	<td width="20">Nome:</td>
        <td width="300"><INPUT TYPE="text" NAME="nome" value="<?= $nome ?>"></td>
    </tr>
    <tr>
    	<td width="20">Login:</td>
        <td width="300"><INPUT TYPE="text" NAME="login" value="<?= $login ?>"></td>
    </tr>
    <tr>
    	<td width="20">Senha:</td>
        <td width="300"><INPUT TYPE="text" NAME="senha" value="<?= $senha ?>"></td>
    </tr>
    <tr>
        <td width="20">N�vel:</td>
        <td width="300"><SELECT NAME="nivel">
                <OPTION VALUE="1" <? if ($nivel == "1"){echo " SELECTED";}?>>n�vel 1</option>
                <OPTION VALUE="2" <? if ($nivel == "2"){echo " SELECTED";}?>>n�vel 2</option>
                <OPTION VALUE="3" <? if ($nivel == "3"){echo " SELECTED";}?>>nivel 3</option>
                <OPTION VALUE="4" <? if ($nivel == "4"){echo " SELECTED";}?>>n�vel 4</option>
                <OPTION VALUE="5" <? if ($nivel == "5"){echo " SELECTED";}?>>Administrador</option>
            </SELECT></td>
    </tr>
    <tr>
    	<td colspan="2">N�vel 1 = apenas incluir hist�rico, finalizar nota e relat�rios<br>
N�vel 2 = n�vel 1 + incluir arquivo no banco de dados<br>
N�vel 3 = n�vel 1 + n�vel 2 + abrir nota j� fechada<br>
N�vel 4 = apenas relat�rios<br>
Administrador = todas as permi��es + gerenciar usuarios<br><br></td>
    </tr>
    <tr>
    	<td colspan="2"><INPUT TYPE="submit" value="ALTERAR DADOS DO USU�RIO"></td>
    </tr>
</table>
</FORM>


<?
}
else
{
	if ($nivel_logado <> "5"){
			$sql = "SELECT id_usuario, nome, login, senha, nivel FROM tbl_cobranca_usuario where id_usuario = $id_usuario";
			$res = pg_exec($con,$sql);					
		
				if(pg_numrows($res)> 0){
					$id_usuario=pg_result($res,0,id_usuario);
					$nome=pg_result($res,0,nome);
					$login=pg_result($res,0,login);
					$senha=pg_result($res,0,senha);
					$nivel=pg_result($res,0,nivel);
				}
?>
<br><br>
<FORM METHOD=POST ACTION="adm_usuarios.php?acao=atualizar">
<INPUT TYPE="hidden" NAME="id_usuario" value="<?= $id_usuario ?>">
<table border="0" cellpadding="5" cellpadding="0" style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;" >
    <tr>
    	<td width="20">Nome:</td>
        <td width="300"><INPUT TYPE="text" NAME="nome" value="<?= $nome ?>" <? if ($nivel <> "5"){echo " disabled='disabled'";}?>></td>
    </tr>
    <tr>
    	<td width="20">Login:</td>
        <td width="300"><INPUT TYPE="text" NAME="login" value="<?= $login ?>"></td>
    </tr>
    <tr>
    	<td width="20">Senha:</td>
        <td width="300"><INPUT TYPE="text" NAME="senha" value="<?= $senha ?>"></td>
    </tr>
    <tr>
        <td width="20">N�vel:</td>
        <td width="300"><SELECT NAME="nivel" <? if ($nivel <> "5"){echo " disabled='disabled'";}?>>
                <OPTION VALUE="1" <? if ($nivel == "1"){echo " SELECTED";}?>>n�vel 1</option>
                <OPTION VALUE="2" <? if ($nivel == "2"){echo " SELECTED";}?>>n�vel 2</option>
                <OPTION VALUE="3" <? if ($nivel == "3"){echo " SELECTED";}?>>nivel 3</option>
                <OPTION VALUE="4" <? if ($nivel == "4"){echo " SELECTED";}?>>n�vel 4</option>
                <OPTION VALUE="5" <? if ($nivel == "5"){echo " SELECTED";}?>>Administrador</option>
            </SELECT></td>
    </tr>
    <tr>
    	<td colspan="2"><INPUT TYPE="submit" value="ALTERAR DADOS DO USU�RIO"></td>
    </tr>
</table>
</FORM>
<?
	}
}	

include 'rodape.php';
?>