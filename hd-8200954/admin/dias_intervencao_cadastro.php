<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$title = "CADASTRO DE QUANTIDADE DE DIAS PARA INTERVENÇÃO"; 
$layout_menu = 'cadastro';
include 'funcoes.php';
$admin_privilegios="cadastro";


$acao      = $_POST['acao'];
$qtde_dias = $_POST['qtde_dias'];
if(strlen($acao)>0 and strlen($qtde_dias) > 0 ){
	$sql = "UPDATE tbl_fabrica
			SET qtde_dias_intervencao_sap = $qtde_dias
			WHERE fabrica =  $login_fabrica";
	$res = pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if(strlen($msg_erro)==0){
		$msg = "Alterado com Sucesso!";
	}

}elseif(strlen(trim($qtde_dias)) == 0 ){
	$msg_erro="Por favor, colocar quantidade de dias para entrar na intervenção.";
}



include 'cabecalho.php';
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.linha{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	color:#393940 ;
}
a.linha:link, a.linha:visited, a.linha:active{
	text-decoration: none;
	font-weight: normal;
	color: #393940;
}

a.linha:hover {
	text-decoration: underline overline; 
	color: #393940;
  }

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>


<?
echo "<FORM name='frm_pesquisa' METHOD='POST' ACTION='$PHP_SELF'>";

if(strlen($msg_erro)>0 and strlen($acao) >0){
echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' class='msg_erro'>";
	echo "<TR >\n";
	echo "<TD align='center'>$msg_erro</TD >";
	echo "</TR >\n";
	echo "</TABLE >\n";

}
if(strlen($msg)>0 and strlen($acao) >0){
echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' class='sucesso'>";
	echo "<TR >\n";
	echo "<TD align='center'>$msg</TD >";
	echo "</TR >\n";
	echo "</TABLE >\n";

}
		$sql="  SELECT qtde_dias_intervencao_sap
				FROM tbl_fabrica
				WHERE fabrica=$login_fabrica";
		$res=pg_exec($con,$sql);
		
		if(pg_numrows($res) > 0 ){
			$qtde_dias = pg_result($res,0,qtde_dias_intervencao_sap);
		}
		echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='0' class='formulario'>";
		echo "<TR >\n";
		echo "<td class='titulo_tabela' colspan='2'>Cadastro</TD>\n";
		echo "</TR>\n";
		echo "<TR >\n";
		echo "<td align='left' style='padding:20px 0 20px 100px;' width='350'>
				Alterar a quantidade de dias para intervenção para &nbsp;
				<input type='text' name='qtde_dias' value='$qtde_dias' size='5' style='TEXT-ALIGN: right' maxlength='20' class='frm'> Dias";
		echo "</TD>\n";
		echo "<td align='left'>";
?>
		<input class="botao" type="hidden" name="acao"  value=''>
		<input  class="input"  type="button" name="bt" value='Gravar' onclick="javascript:if (document.frm_pesquisa.acao.value!='') alert('Aguarde Submissão'); else{document.frm_pesquisa.acao.value='Gravar';document.frm_pesquisa.submit();}">
<?
		echo "</TD>\n";
		echo "</TR>\n";
		echo "</table>";

echo "</form>";

echo "<BR><BR>";

include "rodape.php"; 

?>
