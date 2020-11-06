<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {

	if (strlen(trim($_POST['nome'])) > 0) $valor = trim($_POST['nome']);
	else $msg_erro = "Digite o nome do fabricante";

	if (strlen(trim($_POST['logo'])) > 0) $valor = trim($_POST['logo']);
	else $msg_erro = "Digite o nome do logo do fabricante";

	if (strlen(trim($_POST['site'])) > 0) $valor = trim($_POST['site']);
	else $msg_erro = "Digite o site do fabricante";


	if(strlen($msg_erro) == 0){
		$sql = "INSERT INTO tbl_fabrica (
							nome                                   ,
							logo                                   ,
							site                                   ,
							inibe_revenda                          ,
							linha_pedido                           ,
							os_item_subconjunto                    ,
							pedir_sua_os                           ,
							qtde_item_os                           ,
							pedido_escolhe_condicao                ,
							os_item_aparencia                      ,
							defeito_constatado_por_familia         ,
							multimarca                             ,
							acrescimo_tabela_base                  ,
							acrescimo_financeiro                   ,
							pedido_via_distribuidor                ,
							os_defeito                             ,
							pedir_defeito_reclamado_descricao      ,
							pedir_causa_defeito_os_item            ,
							in_out                                 ,
							posicao_pagamento_extrato_automatico   ,
							defeito_constatado_por_linha           ,
							codigo_fabricacao                      ,
							type                                   ,
							satisfacao                             ,
							laudo_tecnico                          ,
							data_abertura_os_automatica            ,
							postos_credenciado                     ,
							sistema_offline)
					(SELECT 
							'$nome' as nome                           ,
							'$logo' as logo                           ,
							'$site' as site                           ,
							inibe_revenda                             ,
							linha_pedido                              ,
							os_item_subconjunto                       ,
							pedir_sua_os                              ,
							qtde_item_os                              ,
							pedido_escolhe_condicao                   ,
							os_item_aparencia                         ,
							defeito_constatado_por_familia            ,
							multimarca                                ,
							acrescimo_tabela_base                     ,
							acrescimo_financeiro                      ,
							pedido_via_distribuidor                   ,
							os_defeito                                ,
							pedir_defeito_reclamado_descricao         ,
							pedir_causa_defeito_os_item               ,
							in_out                                    ,
							posicao_pagamento_extrato_automatico      ,
							defeito_constatado_por_linha              ,
							codigo_fabricacao                         ,
							type                                      ,
							satisfacao                                ,
							laudo_tecnico                             ,
							data_abertura_os_automatica               ,
							postos_credenciado                        ,
							sistema_offline
						FROM tbl_fabrica where fabrica = 35);";
		$res = pg_exec($con, $sql);
		$msg_erro = pg_errormessage($con);
	}
	if(strlen($msg_erro) == 0){
		header ("Location: fabricante_cadastro");
	}
}

$title       = "Cadastro de Fabricantes";
$cabecalho   = "Cadastro de Fabricantes";
$layout_menu = "gerencia";
include 'cabecalho.php';


?>
<script language="JavaScript">
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>
<style>
.Conteudo{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	color:#fff;
	font-weight: bold;
}
</style>

<?
	if(strlen($msg_erro) > 0){
		echo "<BR>";
		echo "<center><div style='background-color:#FCDB8F;width:300px;margin:0 auto;text-align:center;padding:3px'><p align='center' style='font-size: 12px'><b>$msg_erro</b></p></div></center>";
		echo "<br>";
	}
?>


<BR>
<FORM METHOD="POST"  NAME="frm_fabrica" ACTION="<? echo $PHP_SELF; ?>">
		<table width='300' align='center' border='0' cellspacing='0' bgcolor='#ffffff'>
		<TR>
			<td align='center'  bgcolor="#330099" class="Conteudo" colspan='3'>Cadastro da Fábrica</td>
		</TR>
		<TR>
			<TD bgcolor='#d9e2ef' style='font-size: 12px'>Nome</TD>
			<TD bgcolor='#d9e2ef' style='font-size: 12px'>Logo</TD>
			<TD bgcolor='#d9e2ef' style='font-size: 12px'>Site</TD>
		</TR>
		<TR>
			<TD><INPUT TYPE="text" NAME="nome" value="<? echo $nome; ?>" size="20" maxlength="30"></TD>
			<TD><INPUT TYPE="text" NAME="logo" value="<? echo $logo; ?>" size="20" maxlength="30"></TD>
			<TD><INPUT TYPE="text" NAME="site" value="<? echo $site; ?>" size="20" maxlength="30"></TD>
		</TR>
		<TR>
			<TD bgcolor='#d9e2ef' colspan='3'><input type='hidden' name='btn_acao' value=''>
			<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_fabrica.btn_acao.value == '' ) { document.frm_fabrica.btn_acao.value='gravar' ; document.frm_fabrica.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
			</TD>
		</TR>
	</TABLE>
</FORM>
<BR>
<BR>

<TABLE align='center' style='font-size: 12px' cellspacing='0' cellpadding='0' border='1'>
<TR>
	<TD><b>Fabrica</b></TD>
	<TD><b>Nome</b></TD>
	<TD><b>Logo</b></TD>
	<TD><b>Site</b></TD>
</TR>
<? 
$sql = "SELECT fabrica, nome, logo, site FROM tbl_fabrica order by nome;";
$res = pg_exec($con,$sql);

for($i=0;$i<pg_numrows($res);$i++){
	$fabrica_cod  = pg_result($res,$i,fabrica);
	$fabrica_nome = pg_result($res,$i,nome);
	$fabrica_logo = pg_result($res,$i,logo);
	$fabrica_site = pg_result($res,$i,site);

?>
	<TR>
		<TD><?echo $fabrica_cod;?></TD>
		<TD><?echo $fabrica_nome;?></TD>
		<TD><?echo $fabrica_logo;?></TD>
		<TD><?echo $fabrica_site;?></TD>
	</TR>
<?
}
?>
</TABLE>

<? include "rodape.php"; ?>