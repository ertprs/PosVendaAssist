<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


$btn_acao = trim($_POST['btn_acao']);
$defeito=$_GET["defeito"];
// $defeito=$_POST["defeito"];
// echo "def: $defeito";
if(strlen($defeito)>0){
	$sql = "SELECT  tbl_defeito_reclamado.linha            ,
					tbl_defeito_reclamado.familia          ,
					tbl_defeito_reclamado.duvida_reclamacao,
					tbl_defeito_reclamado.descricao        ,
					tbl_defeito_reclamado.ativo
			FROM    tbl_defeito_reclamado
			WHERE   tbl_defeito_reclamado.defeito_reclamado = $defeito
			AND     tbl_defeito_reclamado.fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$linha               = trim(pg_result($res,0,linha));
		$familia             = trim(pg_result($res,0,familia));
		$duvida_reclamacao   = trim(pg_result($res,0,duvida_reclamacao));
		$descricao_defeito   = trim(pg_result($res,0,descricao));
		$ativo               = trim(pg_result($res,0,ativo));
	}
}


if(strlen($btn_acao)>0){
	if($btn_acao=="gravar"){
	$defeito=$_POST["defeito"];
	$descricao_defeito = trim($_POST['descricao_defeito']);
	if(strlen($descricao_defeito)==0){ $msg_erro ="Por favor insira a descrição do defeito reclamado<BR>";}
	$duvida_reclamacao = trim($_POST['duvida_reclamacao']);
	$ativo = trim($_POST['ativo']);
	if(strlen($ativo)==0){$ativo='f';}
		if((strlen($msg_erro)==0)and(strlen($defeito)==0)) {
				$sql = "INSERT INTO tbl_defeito_reclamado (
									descricao, 
									ativo,
									duvida_reclamacao,
									fabrica
								) VALUES (
									'$descricao_defeito',
									'$ativo',
									'$duvida_reclamacao',
									$login_fabrica
								);";
 				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
// 				echo "$sql";
			if(strlen($msg_erro)==0){$msg_erro="Adicionado com sucesso!";}
		}

	
if((strlen($msg_erro)==0)and(strlen($defeito)>0)){
	$sql = "UPDATE tbl_defeito_reclamado set
					descricao= '$descricao_defeito',
					ativo='$ativo',
					duvida_reclamacao='$duvida_reclamacao',
					fabrica=$login_fabrica
				where defeito_reclamado=$defeito and fabrica=$login_fabrica";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
// 				echo "$sql";
			if(strlen($msg_erro)==0){$msg_erro="Alterado com sucesso!";}
}
				
				
	}//gravar
	$defeito = trim($_POST['defeito']);
	if(($btn_acao=="deletar") AND (strlen($defeito)>0)){ 
// 	echo "apagando $defeito";
	$sql = "DELETE FROM tbl_defeito_reclamado
							WHERE  defeito_reclamado = $defeito 
							AND    fabrica=$login_fabrica";
 					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
//     echo "$sql";
 	if(strlen($msg_erro)==0){$msg_erro="Apagado com sucesso!";}
	
	}//DELETAR
 
 
 
}//btn_acao

$layout_menu = "cadastro";
$title = "Cadastramento de Defeitos Reclamados";
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
echo "<form name='frm_defeito' method='post' action='$PHP_SELF'>";
echo "<input type='hidden' name='defeito' value='$defeito'>";
echo "<BR>";
echo "<table width='600' border='0' bgcolor='#D9E2EF'  align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
if (strlen($msg_erro) > 0) { 
echo "<div class='error'>";
echo $msg_erro; 
echo "</div>";
} 
// echo "<tr>";
// echo "<td align='left' colspan='3'><font color='#A22A26'><u><B>Cadastro de DEFEITOS RECLAMADOS</B></u></font></td>";
// echo "</tr>";
echo "<tr>";
echo "<td align='left' colspan='3' bgcolor='#596D9B'><font color='#FFFFFF'><B>Cadastro de DEFEITOS RECLAMADOS</B></font></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='left'>Descrição (*)<BR><input type='text' name='descricao_defeito' value='$descricao_defeito' size='40' maxlength='50'></td>";
echo "<td align='left'>Tipo<BR>";
echo "<select name='duvida_reclamacao' style='width: 150px;'>";
	echo "<option value=''>ESCOLHA</option>";
		if ($login_fabrica <> 6) { 
	echo "<option value='DV'";  if ($duvida_reclamacao == 'DV') echo " SELECTED "; echo ">Dúvida</option>";
	echo "<option value='RC'";  if ($duvida_reclamacao == 'RC') echo " SELECTED "; echo ">Reclamação</option>";
	echo "<option value='IS'";  if ($duvida_reclamacao == 'IS') echo " SELECTED "; echo ">Insatisfação</option>";
	} else { 
	echo "<option value='RC'";  if ($duvida_reclamacao == 'RC') echo " SELECTED "; echo ">Reclamação</option>";
	echo "<option value='IN'";  if ($duvida_reclamacao == 'IN') echo " SELECTED "; echo ">Informação</option>";
	echo "<option value='IS'";  if ($duvida_reclamacao == 'IS') echo " SELECTED "; echo ">Insatisfação</option>";
	echo "<option value='TP'";  if ($duvida_reclamacao == 'TP') echo " SELECTED "; echo ">Troca de Produto</option>";
	echo "<option value='EN'";  if ($duvida_reclamacao == 'EN') echo " SELECTED "; echo ">Engano</option>";
	echo "<option value='OA'";  if ($duvida_reclamacao == 'OA') echo " SELECTED "; echo ">Outras Áreas</option>";
	} 
echo "</select>";
echo "</td>";
echo "<td align='left'><input type='checkbox' name='ativo'"; if ($ativo == 't' ) echo " checked "; echo " value='t'> Ativo</td>";
echo "</tr>";


echo "<TR>";
?>
<TD align='center' colspan='3'>
<br><font size='1'>Os campos com esta marcação (*) não podem ser nulos. </font><BR>
<input type='hidden' name='btn_acao' value=''>
<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_defeito.btn_acao.value == '' ) { document.frm_defeito.btn_acao.value='gravar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_defeito.btn_acao.value == '' ) { document.frm_defeito.btn_acao.value='deletar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Linha" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_defeito.btn_acao.value == '' ) { document.frm_defeito.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
</center> 
</td>
<?
/*

echo "<TD align='center' colspan='3'><br><font size='1'>Os campos com esta marcação (*) não podem ser nulos. </font><BR><input type='submit' name='btn_acao' value='gravar'>&nbsp;&nbsp;<input type='submit' name='btn_acao' value='deletar'>&nbsp;&nbsp;<input type='reset' name='btn_acao' value='limpar'>";
echo "</TD>";*/
echo "</TR>";
echo "</TABLE>";
//botoes acao
//botoes acao



 
 
echo "<br><br><center><font size='2'><b>Relação de Defeitos Reclamados</b><BR>
	<I>Para efetuar alterações, clique na descrição do defeito reclamado.</i></font>
	</center>";


		$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado, 
						tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao ,
						tbl_defeito_reclamado.duvida_reclamacao,
						tbl_defeito_reclamado.ativo
				FROM tbl_defeito_reclamado 
				WHERE tbl_defeito_reclamado.fabrica=$login_fabrica
				ORDER BY tbl_defeito_reclamado.descricao";
		$res = pg_exec ($con,$sql);
// 		echo "$sql";
		echo "<table width='500' border='0' align='center' class='conteudo' cellpadding='2' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
			echo "<tr bgcolor='#D9E2EF'>";
			echo "<td align='left'>Ativo?</td>";
			echo "<td align='left'>Tipo</td>";
			echo "<td align='left'>Defeito Reclamado</td>";
			echo "</tr>";
		for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			$defeito_reclamado           = trim(pg_result($res,$y,defeito_reclamado));
			$defeito_reclamado_descricao = trim(pg_result($res,$y,defeito_reclamado_descricao));
			$duvida_reclamacao = trim(pg_result($res,$y,duvida_reclamacao));
			$ativo  = trim(pg_result($res,$y,ativo));
			if($ativo=='t'){ $ativo="Sim"; }else{$ativo="<font color='#660000'>Não</font>";}
			if ($login_fabrica <> 6) { 
				if ($duvida_reclamacao == 'DV') $duvida_reclamacao ="Dúvida";
				if ($duvida_reclamacao == 'RC') $duvida_reclamacao ="Reclamação";
				if ($duvida_reclamacao == 'IS') $duvida_reclamacao ="Insatisfação";
			} else { 
				if ($duvida_reclamacao == 'RC') $duvida_reclamacao ="Reclamação";
				if ($duvida_reclamacao == 'IN') $duvida_reclamacao ="Informação";
				if ($duvida_reclamacao == 'IS') $duvida_reclamacao ="Insatisfação";
				if ($duvida_reclamacao == 'TP') $duvida_reclamacao ="Troca de Produto";
				if ($duvida_reclamacao == 'EN') $duvida_reclamacao ="Engano";
				if ($duvida_reclamacao == 'OA') $duvida_reclamacao ="Outras Áreas";
			}

			$cor = ($y % 2 == 0) ? "#FFFFFF": '#F1F4FA';
			
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'>$ativo</td>";
			echo "<td align='left'>$duvida_reclamacao</td>";
			echo "<td align='left'><a href='$PHP_SELF?defeito=$defeito_reclamado'>$defeito_reclamado_descricao</a></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";

echo "</form>";

echo "<BR><BR><BR>";
echo "<table width='500' border='0' cellspacing='2' cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
echo "<TR>";
echo "<TD align='center'>
<a href='linha_cadastro-tk.php'>Linha</a><BR>
<a href='familia_cadastro-tk.php'>Familia</a><BR>
<a href='defeito_reclamado_cadastro-tk.php'>Defeito Reclamado</a><BR>
<a href='defeito_constatado_cadastro-tk.php'>Defeito Constatado</a><BR>
<a href='solucao-tk.php'>Solução</a><BR>
<a href='relacionamento_diagnostico-tk.php'>Diagnostico</a><BR>";
echo "</TD>";
echo "</TR>";
echo "</TABLE>";



include "rodape.php";
?>
</body>
</html>