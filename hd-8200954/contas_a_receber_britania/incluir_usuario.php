<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';

$acao = $_GET["acao"];

if ($acao == "incluir"){
	$nome = $_POST["nome"]; 
	$login = $_POST["login"]; 
	$senha = $_POST["senha"]; 
	$nivel = $_POST["nivel"]; 

if ($nome == ""){
	echo "<b><br><br>&nbsp;&nbsp;&nbsp;O campo nome deve ser preenchido";
	$erro="sim";
}
if ($login == ""){
	echo "<b><br>&nbsp;&nbsp;&nbsp;O campo usus�rio deve ser preenchido";
	$erro="sim";
}
if ($senha == ""){
	echo "<b><br>&nbsp;&nbsp;&nbsp;O campo senha deve ser preenchido";
	$erro="sim";
}

if ($erro <> "sim"){
$sql = "insert into tbl_cobranca_usuario (nome, login, senha, nivel) values('$nome','$login','$senha','$nivel')";
$res = pg_exec($con,$sql);
echo "<b><br><br>&nbsp;&nbsp;&nbsp;Us�ario inclu�do com sucesso.<br><br><b>";
}
					
}

?>

<FORM METHOD=POST ACTION="incluir_usuario.php?acao=incluir">
<INPUT TYPE="hidden" NAME="id_usuario">
<table border="0" cellpadding="5" cellpadding="0" style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;" >
    <tr>
    	<td width="20">Nome:</td>
        <td width="300"><INPUT TYPE="text" NAME="nome"></td>
    </tr>
    <tr>
    	<td width="20">Login:</td>
        <td width="300"><INPUT TYPE="text" NAME="login" ></td>
    </tr>
    <tr>
    	<td width="20">Senha:</td>
        <td width="300"><INPUT TYPE="text" NAME="senha"></td>
    </tr>
    <tr>
        <td width="20">N�vel:</td>
        <td width="300"><SELECT NAME="nivel">
                <OPTION VALUE="1">n�vel 1</option>
                <OPTION VALUE="2">n�vel 2</option>
                <OPTION VALUE="3">nivel 3</option>
                <OPTION VALUE="4">n�vel 4</option>
                <OPTION VALUE="5">Administrador</option>
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
    	<td colspan="2"><INPUT TYPE="submit" value="INCLUIR USU�RIO"></td>
    </tr>
</table>
</FORM>


<?
include 'rodape.php';
?>