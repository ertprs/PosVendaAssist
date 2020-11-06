<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$diagnostico 		= trim($_GET['diagnostico']);
if(strlen($diagnostico)>0){
$sql ="UPDATE tbl_diagnostico set ativo='f' where diagnostico=$diagnostico";
$res = @pg_exec($con,$sql);
$msg_erro = pg_errormessage($con);
if(strlen($msg_erro)==0){$msg_erro="Apagado com sucesso!";}
}

$linha 		 		= trim($_POST['linha']);
if($linha=='0') $msg_erro .="Escolha a linha<BR>";
$familia	 		= trim($_POST['familia']);
if($familia=='0') $msg_erro .="Escolha a familia<BR>";
$defeito_constatado	= trim($_POST['defeito_constatado']);
if($defeito_constatado=='0') $msg_erro .="Escolha o defeito constatado<BR>";
$solucao			= trim($_POST['solucao']);
if($solucao=='0') $msg_erro .="Escolha a solução<BR>";
$btn_acao			= trim($_POST['btn_acao']);
if(($btn_acao=="gravar") and (strlen($msg_erro)==0)){
	$sql = "SELECT diagnostico 
			from tbl_diagnostico 
			where fabrica = $login_fabrica 
				and linha = $linha
				and familia = $familia
				and defeito_constatado = $defeito_constatado
				and solucao = $solucao";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$diagnostico = pg_result($res,0,0);
		$sql = "UPDATE tbl_diagnostico set ativo='t' where diagnostico = $diagnostico and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)==0){$msg_erro="Adicionado com sucesso!";}

	}else{
	$sql = "INSERT INTO tbl_diagnostico (
						fabrica,
						linha,
						familia,
						defeito_constatado,
						solucao, ativo
					) VALUES (
						$login_fabrica,
						$linha,
						$familia,
						$defeito_constatado,
						$solucao, 't'
					);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	//echo "$sql";
	if(strlen($msg_erro)==0){$msg_erro="Adicionado com sucesso!";}
	}
}

$aux_linha 		 		= trim($_POST['linha']);
$aux_familia	 		= trim($_POST['familia']);
$aux_defeito_constatado	= trim($_POST['defeito_constatado']);
$aux_solucao			= trim($_POST['solucao']);

$layout_menu = "cadastro";
$title = "Cadastramento de diagnósticos";
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
echo "<BR><BR><table width='600' border='0' align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td align='center' colspan='2'><font color='#000000'>Visando melhorar o cadastro e manutenção da integridade a Telecontrol disponibiliza um novo programa de manutenção. <BR><a href='relacionamento_diagnostico_new.php'>Clique aqui para acessa-lo.</a></font></td>";
echo "</tr>";
echo "</table>";
echo "<BR><BR><form name='frm_diagnostico' method='post' action='$PHP_SELF'>";
echo "<table width='600' border='0' bgcolor='#D9E2EF' align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
if (strlen($msg_erro) > 0) { 
	echo "<div class='error'>";
	echo $msg_erro; 
	echo "</div>";
} 
echo "<tr>";
echo "<td align='left' colspan='2' bgcolor='#596D9B'><font color='#FFFFFF'><B>Relacionamento de Diagnósticos</B></font></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='center' >Linha*<BR>";
$sql ="SELECT linha, nome from tbl_linha where fabrica=$login_fabrica order by nome";
$res = pg_exec ($con,$sql);
echo "<select name='linha' style='width: 150px;'>";
echo "<option value='0'>Linha</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$nome = trim(pg_result($res,$y,nome));
	echo "<option value='$linha'";  if ($linha == $aux_linha) echo " SELECTED "; echo ">$nome</option>";
}
echo "</select>";
echo "</td>";
echo "<td align='center' >Família*<BR>";
$sql ="SELECT familia, descricao from tbl_familia where fabrica=$login_fabrica order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='familia' style='width: 150px;'>";
echo "<option value='0'>Familia</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$familia           = trim(pg_result($res,$y,familia));
	$descricao = trim(pg_result($res,$y,descricao));
	echo "<option value='$familia'";  if ($familia == $aux_familia) echo " SELECTED "; echo
">$descricao</option>";
}
echo "</select>";
echo "</td>";
echo "</tr>";
/*
echo "<tr>";
echo "<td colspan='2' align='center' >Defeito Reclamado*<BR>";
$sql ="SELECT defeito_reclamado, descricao, duvida_reclamacao from tbl_defeito_reclamado where fabrica=$login_fabrica and ativo='t'";
if($login_fabrica==6){ $sql .=" AND duvida_reclamacao='RC' ";}
$sql .=" order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='defeito_reclamado' style='width: 300px;'>";
echo "<option value='0'>Defeito Reclamado</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$defeito_reclamado          = trim(pg_result($res,$y,defeito_reclamado));
	$descricao = trim(pg_result($res,$y,descricao));
	$duvida_reclamacao = trim(pg_result($res,$y, duvida_reclamacao));
	echo "<option value='$defeito_reclamado'";  if ($defeito_reclamado == $aux_defeito_reclamado) echo " SELECTED ";
	echo ">$descricao</option>";
}
echo "</select>";
echo "</td>";
echo "</tr>";
*/
echo "<tr>";
echo "<td colspan='2' align='center' >Defeito Constatado*<BR>";
$sql ="SELECT defeito_constatado, descricao from tbl_defeito_constatado where fabrica=$login_fabrica and ativo='t' order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='defeito_constatado' style='width: 300px;'>";
echo "<option value='0'>Defeito Constatado</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$defeito_constatado          = trim(pg_result($res,$y,defeito_constatado));
	$descricao = trim(pg_result($res,$y,descricao));
	echo "<option value='$defeito_constatado'";  if ($defeito_constatado == $aux_defeito_constatado) echo " SELECTED ";
echo ">$descricao</option>";
}
echo "</select>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='2' align='center' >Solução*<BR>";
$sql ="SELECT solucao, descricao from tbl_solucao where fabrica=$login_fabrica and ativo='t' order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='solucao' style='width: 300px;'>";
echo "<option value='0'>Solução</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$solucao         = trim(pg_result($res,$y,solucao));
	$descricao = trim(pg_result($res,$y,descricao));
	echo "<option value='$solucao'";  if ($solucao == $aux_solucao) echo " SELECTED "; echo
">$descricao</option>";
}
echo "</select>";
echo "</td>";
echo "</tr>";
echo "<TR>";
?>
<TD align='center' colspan='3'>
<br><font size='1'>Os campos com esta marcação (*) não podem ser nulos. </font><BR>
<input type='hidden' name='btn_acao' value=''>
<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_diagnostico.btn_acao.value == '' ) { document.frm_diagnostico.btn_acao.value='gravar' ; document.frm_diagnostico.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">&nbsp;&nbsp;&nbsp;
<img border="0" src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_diagnostico.btn_acao.value == '' ) { document.frm_diagnostico.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
</center> 
</td>
<?
echo "</TR>";
echo "</TABLE>";
echo "</form>";


echo "<table width='500' border='0' cellspacing='1'  cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
$sql ="SELECT 
			linha, 
			nome 
		FROM tbl_linha 
		WHERE linha IN (
						SELECT distinct(linha) 
							FROM tbl_diagnostico 
							WHERE fabrica=$login_fabrica and ativo='t') 
		ORDER BY NOME";
$num=pg_numrows($res);
echo "<TR>";
echo "<td align='center' colspan='$num'><A name='inicio'>Escolha a LINHA que você deseja análisar</A></td>";
echo "</TR>";
echo "<TR>";
$res = pg_exec ($con,$sql);
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$linha_descricao = trim(pg_result($res,$y,nome));
		$a= "#"."$linha_descricao";

	echo "<td align='center'><font color='#000000'> <A href='$PHP_SELF?linha_abre=$linha'>$linha_descricao</A></td>";
#LINHA
}

echo "</tr>";
echo "</table>";

//echo "<BR><BR><center><a href='relacionamento_diagnostico_xls.php'><font color='#000000' face='verdana' size='1'>Clique aqui para fazer o download da tabela de relacionamento de integridade</font></a></center>";


//identação do diagnostico INICIO
//identação do diagnostico INICIO
//identação do diagnostico INICIO
$linha_abre = $_GET['linha_abre'];
if(strlen($linha_abre)>0){
echo "<BR><BR>";
echo "<table width='700' border='0' cellspacing='1' bgcolor='#485989' cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
echo "<TR>";
echo "<TD align='center' colspan='4'><font color='#FFFFFF'><b>Diagnósticos Cadastrados</b></font></td>";
echo "</TR>";
echo "<TR  bgcolor='#f4f7fb'>";
echo "<TD align='center' width='120'>Linha</td>";
echo "<TD align='center' width='120' >Familia</td>";
//echo "<TD align='center' width='120'>Defeito Reclamado</td>";
echo "<TD align='center' width='120'>Defeito Constatado</td>";
echo "<TD align='center' width='200'>Solução</td>";
echo "<TD align='center'>Apagar?</td>";
echo "</TR>";
#LINHA

$sql ="SELECT 
			linha, 
			nome 
		FROM tbl_linha 
		WHERE linha =$linha_abre
		ORDER BY NOME";
$res = pg_exec ($con,$sql);
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$linha_descricao = trim(pg_result($res,$y,nome));

	echo "<tr>";
	echo "<td align='left' bgcolor='#819CB4'>
	<font color='#ffffff'><B><A name='$linha_descricao'>$linha_descricao</A></B></td>";
	echo "<td align='right' bgcolor='#819CB4' colspan='4'><A href='#inicio'><font color='#ffffff' size='1'>Voltar ao topo</font></a></td>";
	echo "</tr>";
#LINHA
#FAMILIA	
	$sqlfamilia ="SELECT 
						familia, 
						descricao 
					FROM tbl_familia 
					WHERE familia IN (
										SELECT DISTINCT(familia) 
										FROM tbl_diagnostico 
										WHERE fabrica=$login_fabrica AND linha=$linha and ativo='t'
										)
					ORDER BY descricao";
	$resfamilia = @pg_exec ($con,$sqlfamilia);
	for ($x = 0 ; $x < pg_numrows($resfamilia) ; $x++){
		$familia           = trim(pg_result($resfamilia,$x,familia));
		$descricao_familia = trim(pg_result($resfamilia,$x,descricao));
		echo "<tr>";
		echo "<td  bgcolor='#ced7e7'>&nbsp;</td>";
		echo "<td align='left' bgcolor='#819CB4' colspan='4'><font color='#ffffff'>
		<B><A name='$descricao_familia'>$descricao_familia</B></A></td>";
/*		echo "<td  bgcolor='#819CB4' colspan='3'>&nbsp;</td>";
		echo "<td  bgcolor='#819CB4'><A href='#inicio'><font color='#ffffff'>Voltar ao topo</font></a></td>";*/
		echo "</tr>";
#DEFEITO_RECLAMADO
				$sqldefeito_constatado ="SELECT defeito_constatado, 
												descricao 
											FROM tbl_defeito_constatado 
											WHERE defeito_constatado IN (
																		SELECT DISTINCT(defeito_constatado) 
																		FROM tbl_diagnostico 
																		WHERE fabrica=$login_fabrica 
																		AND linha=$linha 
																		AND familia=$familia and ativo='t')
											ORDER BY descricao";
				$resdefeito_constatado = pg_exec ($con,$sqldefeito_constatado);
						
				for ($z = 0 ; $z < pg_numrows($resdefeito_constatado) ; $z++){
					$defeito_constatado           = trim(pg_result($resdefeito_constatado,$z,defeito_constatado));
					$descricao_defeito_constatado = trim(pg_result($resdefeito_constatado,$z,descricao));
					echo "<tr>";
					echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
					echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
//					echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
					echo "<td align='left' bgcolor='#819CB4' colspan='3'><font color='#ffffff'><B>$descricao_defeito_constatado</B></td>";
					//echo "<td bgcolor='#819CB4'> &nbsp;</td>";
					echo "</tr>";
#SOLUCAO
					$sqlsolucao ="SELECT solucao, 
										descricao 
									FROM tbl_solucao 
									WHERE solucao IN (
													SELECT DISTINCT(solucao) 
													FROM tbl_diagnostico 
													WHERE fabrica=$login_fabrica 
													AND linha=$linha 
													AND familia=$familia 
													AND defeito_constatado=$defeito_constatado and ativo='t')
									ORDER BY descricao";
					$ressolucao = pg_exec ($con,$sqlsolucao);
					for ($k = 0 ; $k < pg_numrows($ressolucao) ; $k++){
						$solucao          = trim(pg_result($ressolucao,$k,solucao));
						$descricao_solucao = trim(pg_result($ressolucao,$k,descricao));
						$sqldiagnostico="SELECT diagnostico from tbl_diagnostico where fabrica=$login_fabrica and linha=$linha and familia=$familia and defeito_constatado=$defeito_constatado and solucao=$solucao";
						$resdiagnostico=@pg_exec($con,$sqldiagnostico);
						$diagnostico          = trim(pg_result($resdiagnostico,0,diagnostico));
						echo "<tr>";
						echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
						echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
						echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
//						echo "<td bgcolor='#ced7e7'> &nbsp;</td>";
						echo "<td align='left' bgcolor='#D6DFF0'><font
color='#000000'><B>$descricao_solucao</B></td>";
						echo "<td bgcolor='#D6DFF0'><a href='$PHP_SELF?diagnostico=$diagnostico'><img border='0' src='imagens_admin/btn_apagar.gif' alt='Apagar Diagóstico'></A></td>";
						echo "</tr>";
					}
#SOLUCAO
				}
#DEFEITO_CONSTATADO
	}
#FAMILIA

}
echo "</TABLE>";
}
//identação do diagnostico FIM
//identação do diagnostico FIM
//identação do diagnostico FIM


include "rodape.php";
?>