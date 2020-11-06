<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title = "Consulta Expressa de Pedidos e OS";
$body_onload = "javascript: document.frm_consulta.os.focus()";

include "cabecalho.php";

?>

<? include "javascript_pesquisas.php" ?>


<br>

<?
$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) == 0) {
?>

<FORM name="frm_consulta" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<TABLE width="450" align="center" border="0" cellspacing="2" cellpadding="2" bgcolor="#66AABB">
<TR>
	<TD>Pelo Número da OS</TD>
	<TD><input type='text' name='sua_os' size='10' maxlength='10'></TD>
	<TD><input type='submit' name='btn_acao' value='Pesquisar'></TD>
</TR>
</TABLE>
</FORM>
<?
	#---- Fim do BTN_ACAO
}else{

	$sua_os = $_POST['sua_os'];
	if (strlen ($sua_os) > 0) {
		$sua_os = str_replace (".","",trim ($sua_os));
		$sql = "SELECT	tbl_os.os,
						tbl_os.sua_os,
						tbl_os.serie,
						to_char (tbl_os.data_abertura , 'DD/MM/YYYY')          AS abertura  ,
						to_char (tbl_os.data_nf , 'DD/MM/YYYY')                AS data_nf   ,
						to_char (tbl_os.data_digitacao , 'DD/MM/YYYY HH24:MI') AS digitacao ,
						to_char (tbl_extrato.data_geracao , 'DD/MM/YYYY')      AS geracao   ,
						tbl_os.serie ,
						tbl_produto.referencia , 
						tbl_produto.descricao AS produto_descricao ,
						tbl_produto.garantia ,
						tbl_posto.nome AS posto_nome ,
						tbl_posto_fabrica.codigo_posto AS posto_codigo ,
						tbl_os.consumidor_nome ,
						tbl_os.consumidor_fone ,
						tbl_os.consumidor_cidade ,
						distrib.distrib_codigo ,
						distrib.nome ,
						tbl_defeito_reclamado.descricao  AS defeito_reclamado  ,
						tbl_defeito_constatado.descricao AS defeito_constatado ,
				FROM    tbl_os
				JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
				";
	}
}

?>


<?
include "rodape.php";
?>
