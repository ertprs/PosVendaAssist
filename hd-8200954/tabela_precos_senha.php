<?
// Tela onde o usuario podera trocar a senha ou liberar o acesso a tabela de preço

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
//include 'autentica_usuario_financeiro.php';


if (strlen($_GET['acao'])) $acao       = $_GET['acao'];
if (strlen($_POST['acao'])) $acao       = $_POST['acao'];
$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_nova= $_POST['senha_nova'];
	$senha_nova2= $_POST['senha_nova2'];

	if($senha_nova == $senha_nova2){
		$sql = "UPDATE tbl_posto SET
				senha_tabela_preco = '$senha_nova'
				WHERE posto = $login_posto";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro)==0) 
			if($acao=='inserir') $info = traduz("a.senha.foi.cadastrada.com.sucesso",$con,$cook_idioma);
			else                 $info = traduz("a.senha.foi.alterada.com.sucesso",$con,$cook_idioma);
	}else{
		$msg_erro =  traduz("senhas.nao.conferem",$con,$cook_idioma);
	}
}
if (strlen($_GET['aceita'])) $aceita        = $_GET['aceita'];
if (strlen($_POST['aceita'])) $aceita       = $_POST['aceita'];

if($acao=='libera' and $aceita=='s'){
	$sql = "UPDATE tbl_posto SET
			senha_tabela_preco = null
			WHERE posto = $login_posto";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	$msg_erro = substr($msg_erro,6);
	if(strlen($msg_erro)==0){
		$info =  traduz("acesso.liberado",$con,$cook_idioma);
	}
}


$layout_menu = 'preco';
$title = traduz("senha.da.tabela.de.preco",$con,$cook_idioma);
include "cabecalho.php";
?>


<style type="text/css">

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
.Tabela img{
	padding:5px;
	padding-left:15px;
	}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.TituloConsulta {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #D9E2EF;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<? 

if($acao<>'inserir' AND $acao<>'libera'){ ?>
<table style='font-family: verdana; font-size: 10px; color:#A8A7AD' width='200' align='center'>
<tr>
<td><? echo"<a href='$PHP_SELF?acao=alterar'>".traduz("alterar.senha",$con,$cook_idioma)."</a>"; ?></td>
<td><? echo"<a href='$PHP_SELF?acao=libera'>".traduz("liberar.tela",$con,$cook_idioma)."</a>"; ?></td>
</tr>
</table>

<?
} 

if($acao=='inserir' OR strlen($info)>0 OR $acao=='libera'){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF'  width='60'><img src='imagens/info.jpg' align='middle'></td><td  class='Mensagem' bgcolor='FFFFFF'>";
	
	if(strlen($info)>0) {
		echo $info;
	}elseif($acao=='inserir') {
		fecho("ao.inserir.uma.senha.voce.ira.bloquear.o.acesso.a.visualizacao.da.tabela.de.precos,.e.conseguira.acessa-lo.somente.atraves.da.senha.que.voce.ira.preencher.nos.campos.abaixo",$con,$cook_idioma);
		echo "<br>";
		fecho("cadastre.sua.senha",$con,$cook_idioma);

	}elseif($acao=='libera') {
		fecho("ao.clicar.no.botao.abaixo.voce.vai.estar.concordando.e.liberando.o.acesso.a.tabela.de.precos.sem.que.seja.digitada.a.senha.para.o.acesso",$con,$cook_idioma);
	}
	echo"</td>";
	echo "</tr>";
	echo "</table><br>";
}

	
if(strlen($info)==0){
	if(strlen($msg_erro)>0){
		echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
		echo "<tr >";
		echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF'> $msg_erro</td>";
		echo "</tr>";
		echo "</table><br>";
	}

	if($acao=='alterar' OR $acao=='inserir'){
		echo "<FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
		echo "<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>";
		echo "<tr >";
		echo "<td class='Titulo'>".traduz("senha.do.usuario",$con,$cook_idioma)."</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#F3F8FE'>";
		echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='2' CLASS='table_line' bgcolor='#F3F8FE'>";
		echo "<tr class='Conteudo' >";
		echo "<TD colspan='4' style='text-align: center;'>";
		echo "<br>".traduz("por.favor.digite.a.nova.senha",$con,$cook_idioma);
		echo "</TD>";
		echo "</tr>";
		echo "<TR width='100%'  >";
		echo "<td colspan='2'  align='right' height='40'>".traduz("senha",$con,$cook_idioma).":&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' ></td>";
		echo "</tr>";
		echo "<TR width='100%'  >";
		echo "<td colspan='2'  align='right' height='40'>".traduz("repetir.senha",$con,$cook_idioma).":&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' ></td>";
		echo "</tr>";
		echo "<tr class='Conteudo' >";
		echo "<TD colspan='4' style='text-align: center;'>";
		echo "<br><input type='submit' name='btn_gravar' value='".traduz("gravar",$con,$cook_idioma)."'><input type='hidden' name='acao' value=$acao>";
		echo "</TD>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	//alterar senha
	}elseif($acao=='libera' ){
		echo "<a href='$PHP_SELF?aceita=s&acao=libera'>Eu quero liberar o acesso</a>";
	}
}


	//alterar senha
	
	//fazer update setando senha financeiro com null

