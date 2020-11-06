<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = trim($_POST["btn_acao"]);
$defeito_constatado = trim($_GET["defeito_constatado"]);
if(strlen($defeito_constatado)>0){
	$sql = "SELECT  tbl_defeito_constatado.codigo   ,
					tbl_defeito_constatado.ativo   ,
					tbl_defeito_constatado.descricao
			FROM    tbl_defeito_constatado
			WHERE   tbl_defeito_constatado.fabrica            = $login_fabrica
			AND     tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));
		$ativo     = trim(pg_result($res,0,ativo));
	}
}

if(strlen($btn_acao)>0){
	$defeito_constatado= $_POST["defeito_constatado"];
	$codigo = trim($_POST["codigo"]);
	if(strlen($codigo)==0) {
		$codigo = 'null';//{ $msg_erro ="Por favor insira o código do defeito constatado<BR>";}
	} else {
		$codigo = "'".$codigo."'";
	}
	$descricao = trim($_POST["descricao"]);
	if(strlen($descricao)==0){ $msg_erro ="Por favor insira a descrição do defeito constatado<BR>";}
	$ativo = trim($_POST["ativo"]);
	if(strlen($ativo)==0){$ativo='f';}	
	if(($btn_acao=="gravar") AND (strlen($defeito_constatado)==0)){
		if(strlen($msg_erro)==0){
			$sql = "INSERT INTO tbl_defeito_constatado (
								descricao,
								codigo, 
								ativo,
								fabrica
							) VALUES (
								'$descricao',
								$codigo,
								'$ativo',
								$login_fabrica
							);";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
// 			if(strlen($msg_erro)==0){$msg_erro="Adicionado com sucesso!";}
		}
	}
	if(($btn_acao=="gravar") AND (strlen($defeito_constatado)>0)){
		$sql = "UPDATE tbl_defeito_constatado SET
				descricao= '$descricao',
				codigo= '$codigo',
				ativo= '$ativo'	 
		WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
		AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		header ("Location: $PHP_SELF");
// 		if(strlen($msg_erro)==0){$msg_erro="Alterado com sucesso!";}
	}
	if(($btn_acao=="deletar") AND (strlen($defeito_constatado)>0)){
		$sql = "DELETE FROM tbl_defeito_constatado
				WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
				AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		
		if (strpos ($msg_erro,'tbl_defeito_constatado') > 0) $msg_erro = "Este defeito constatado não pode ser excluido";
		if (strpos ($msg_erro,'defeito_constatado_fk') > 0) $msg_erro = "Este defeito constatado não pode ser excluido";
		
// 		if(strlen($msg_erro)==0){$msg_erro="Apagado com sucesso!";}
	}
}

$layout_menu = "cadastro";
$title = "Cadastramento de Defeitos Constatados";
include 'cabecalho.php';
?>

<style type="text/css">

input { 
background-color: #ededed; 
font: 12px verdana;
color:#363738;
border:1px solid #969696;
}


</style>
<?
echo "<form name='frm_defeito_constatado' method='post' action='$PHP_SELF'><BR>";
echo "<input type='hidden' name='defeito_constatado' value='$defeito_constatado'>";
echo "<table width='650' border='0' bgcolor='#D9E2EF'  align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
if (strlen($msg_erro) > 0) { 
	echo "<div class='error'>";
	echo $msg_erro; 
	echo "</div>";
} 
echo "<tr>";
echo "<td align='left' colspan='3' bgcolor='#596D9B'><font color='#FFFFFF'><B>Cadastro de DEFEITOS CONSTATADOS</B></font></td>";
echo "</tr>";

echo "<tr>";
echo "<td align='left'>Código (*)</td>";
echo "<td align='left'>Descrição (*)</td>";
echo "<td align='left'>&nbsp;</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='left'><input type='text' name='codigo' value='$codigo' size='12' maxlength='20'></td>";
echo "<td align='left'><input type='text' name='descricao' value='$descricao' size='30' maxlength='100'></td>";
echo "<td align='left'><input type='checkbox' name='ativo'"; if ($ativo == 't' ) echo " checked "; echo " value='t'> Ativo</td>";
echo "</tr>";

echo "<TR>";
?>
<TD align='center' colspan='3'>
<br><font size='1'>Os campos com esta marcação (*) não podem ser nulos. </font><BR>
<input type='hidden' name='btn_acao' value=''>
<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_defeito_constatado.btn_acao.value == '' ) { document.frm_defeito_constatado.btn_acao.value='gravar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_defeito_constatado.btn_acao.value == '' ) { document.frm_defeito_constatado.btn_acao.value='deletar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Linha" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_defeito_constatado.btn_acao.value == '' ) { document.frm_defeito_constatado.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
</center> 
</td>
<?
echo "</TR>";
echo "</TABLE>";
echo "</form>";
echo "<br>";
echo "<h2>Para efetuar alterações, clique na descrição do defeito constatado.</h2>";

$sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
				tbl_defeito_constatado.codigo           ,
				tbl_defeito_constatado.descricao        ,
				tbl_defeito_constatado.ativo        
		FROM    tbl_defeito_constatado
		LEFT JOIN tbl_linha USING (linha)
		LEFT JOIN tbl_familia USING (familia)
		WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
		ORDER BY tbl_defeito_constatado.linha, tbl_defeito_constatado.familia, tbl_defeito_constatado.descricao;";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table  align='center' width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
	echo "<tr bgcolor='#D9E2EF'>";
	echo "<td nowrap colspan='3'>RELAÇÃO DE DEFEITOS CONSTATADOS</td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E2EF'>";
	echo "<td nowrap>Ativo</td>";
	echo "<td nowrap>Código</td>";
	echo "<td nowrap>Descrição</td>";
	echo "</tr>";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$defeito_constatado   = trim(pg_result($res,$x,defeito_constatado));
		$descricao            = trim(pg_result($res,$x,descricao));
		$codigo               = trim(pg_result($res,$x,codigo));
		$ativo                = trim(pg_result($res,$x,ativo));
		if($ativo=='t'){ $ativo="Sim"; }else{$ativo="<font color='#660000'>Não</font>";}
		$cor = ($x % 2 == 0) ? "#FFFFFF" : "#F1F4FA";
		echo "<tr bgcolor='$cor'>";
		echo "<td nowrap>$ativo</td>";
		echo "<td nowrap align='left'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$codigo</a></td>";
		echo "<td nowrap align='left'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$descricao</a></td>";
		echo "</tr>";
	}
	echo "</table>";
}

include "rodape.php";
?>
