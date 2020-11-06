<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$informatizado = $_POST['informatizado'];
	if(strlen($informatizado)==0) $msg_erro = "Por favor informar se é informatizado";
	
	$branca        = $_POST['branca'];
	if(strlen($branca)==0) $branca = "f";
	
	$portateis     = $_POST['portateis'];
	if(strlen($portateis)==0) $portateis = "f";
	
	$refrigeracao  = $_POST['refrigeracao'];
	if(strlen($refrigeracao)==0) $refrigeracao = "f";

	if(strlen($msg_erro)==0){
		$sql = "INSERT into tbl_pesquisa_suggar(posto,informatizado,branca,portateis,refrigeracao)
					values($login_posto,'$informatizado','$branca','$portateis','$refrigeracao')";
		$res = pg_exec($con,$sql);
		header ("Location: menu_inicial.php");
	}

}


include_once 'funcoes.php';
$sql = "SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto
		from tbl_posto
		join tbl_posto_fabrica on tbl_posto_fabrica.posto=tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		where tbl_posto.posto = $login_posto ";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$nome         = pg_result($res,0,nome);
	$codigo_posto = pg_result($res,0,codigo_posto);
echo "<BR><BR><BR><BR>";
if(strlen($msg_erro)>0){
echo "<table width='100%' align='center' border='0' cellpadding='5' cellspacing='0' style='font-family: verdana; font-size: 12px; color:#FFFFFF' bgcolor='#dc8282'><tr><td align='center'><b>$msg_erro</B></td></tr></table><BR>";
}

	echo "<form name='frm_pesquisa' method='post' action='$PHP_SELF'>";
	echo "<table width='300' align='center' border='0' bgcolor='#e8ad82' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 10px; color:#f3eae2'>";
	echo "<tr>";
	echo "<td align='center' bgcolor='#f3eae2'>";
		echo "<table width='300' align='center' border='0' cellpadding='5' cellspacing='0' style='font-family: verdana; font-size: 10px; color:#330000'>";
		echo "<tr>";
		echo "<td align='center'>";
		echo "<font size='2'>Caro posto autorizado $codigo_posto $nome</font>";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left'>";
		echo "<B>Seu posto  é informatizado?</b> &nbsp;";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left'>";
		echo "<input class='frm' type='radio' name='informatizado' value='t'> Sim";
		echo "&nbsp;&nbsp;<input class='frm' type='radio' name='informatizado' value='f'> Não";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left'>";
		echo "<B>Quais as linhas de produtos o posto atende:</b> &nbsp;";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left'>";
		echo "<input type='checkbox' name='branca' value='t'> Linha Branca<BR>";
		echo "<input type='checkbox' name='portateis' value='t'> Portáteis<BR>";
		echo "<input type='checkbox' name='refrigeracao' value='t'> Refrigeração";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'>";
		echo "<img src='imagens/btn_continuar.gif' onclick=\"javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='continuar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }\" ALT=\"Continuar com Ordem de Serviço\" border='0' style='cursor: pointer'>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<input type='hidden' name='btn_acao' value=''>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "</form>";
}
?>
