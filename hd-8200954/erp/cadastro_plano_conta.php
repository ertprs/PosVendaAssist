<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

if(strlen($_GET["tipo"]) > 0) $tipo = $_GET["tipo"];
else                          $tipo = $_POST["tipo"];

$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}


if ($btn_acao == "Gravar") {

	$id_plano_conta   = trim($_POST['plano_conta']);
	$conta            = trim($_POST['conta']);
	$descricao        = trim($_POST['descricao']);
	$debito_credito   = trim($_POST['debito_credito']);//hd 3387, não gravava se era debito_credito, estranho
	$despesa_administrativa = trim($_POST['despesa_administrativa']);


	if(strlen($conta)     == 0 ) $msg_erro .= "Digite a Conta<br>";
	if(strlen($descricao) == 0 ) $msg_erro .= "Digite a descrição<br>";

	//INFORMAÇÕES GERAIS
	if(strlen($conta)     > 0) $xconta      = "'".$conta."'";
	else                       $xconta      = "null";
	if(strlen($descricao) > 0) $xdescricao  = "'".$descricao."'";
	else                       $xdescricao  = "null";

	if(strlen($despesa_administrativa) == 0){
		$despesa_administrativa = "'f'";
	}else{
		$despesa_administrativa = "'t'";
	}


	$debito_credito = "'".$debito_credito."'";

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//--=== Cadastro de Principal ============================================================================
		if (strlen($id_plano_conta)==0){

			$sql = "INSERT INTO tbl_plano_conta (
					conta            ,
					descricao        ,
					empresa          ,
					debito_credito   ,
					despesa_administrativa
				)VALUES (
					$xconta           ,
					$xdescricao       ,
					$login_empresa    ,
					$debito_credito   ,
					$despesa_administrativa
				)";
		}else{
			$sql = "UPDATE tbl_plano_conta SET
					conta     = $xconta    ,
					descricao = $xdescricao,
					debito_credito = $debito_credito,
					despesa_administrativa = $despesa_administrativa
				WHERE plano_conta = $id_plano_conta ";

		}

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Plano de Contas gravado com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "alterar") {
	if(strlen($_GET["plano_conta"])>0)$plano_conta = trim($_GET['plano_conta']);

	$sql = "SELECT  plano_conta          ,
			conta                        ,
			descricao                    ,
			debito_credito               ,
			despesa_administrativa
		FROM tbl_plano_conta
		WHERE empresa     = $login_empresa
		AND   plano_conta = $plano_conta";
	$res = pg_exec ($con,$sql) ;

	$plano_conta      = trim(pg_result($res,0,plano_conta));
	$conta            = trim(pg_result($res,0,conta));
	$descricao        = trim(pg_result($res,0,descricao));
	$debito_credito   = trim(pg_result($res,0,debito_credito));
	$despesa_administrativa = trim(pg_result($res,0,despesa_administrativa));
}

include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'cadastros') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>



<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function limpar_form(formu){
	for( var i = 0 ; i < formu.length; i++ ){
		if (formu.elements[i].type !='button' && formu.elements[i].type !='submit'){
			if(formu.elements[i].type=='checkbox'){
				formu.elements[i].checked=false;
			}else{
				formu.elements[i].value='';
			}
		}
	}
}


</script>
<script type="text/javascript">
	$(function() {

		$('#container-1').tabs( {fxAutoHeight: true, fxSpeed: 'fast'} );
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});
	$(document).ready(
	function()
	{
		//$("#conta").focus();
	}
);
</script>
<!--========================= AJAX ==================================.-->
<? include "javascript_pesquisas.php" ?>

<style>

.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 12px;
	
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#FFF;
}
.Titulo_Colunas{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}



caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}

</style>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='../ajax_cep.js'></script>



<? if (strlen($msg_erro)>0) {?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?}?>

<? if (strlen($ok)>0 OR strlen($msg)>0) {?>
<div class='ok'>
	<? echo $msg; ?>
</div>
<?}?>
<? if (strlen($peca)==0  && $btn_acao!='cadastrar') { ?>
<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>PLANO DE CONTA</td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab1Procurar"><span><img src='imagens/lupa.png' align=absmiddle> Busca</span></a></li>
						<li><a href="#tab2Cadastrar"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle> Cadastro</span></a></li>
					</ul>
					<div id="tab1Procurar">

							<form name="frm_procura" method="post" action="<? echo $PHP_SELF ?>">
							<table align='left' width='100%' border='0' class='tabela'>
									<tr>
										<td class='Label'>Conta</td>
										<td align='left' ><input class="Caixa" type="text" name="conta" id='conta' size="10" maxlength="10" value="<? echo $conta ?>" ></td>
									</tr>
									<tr>
										<td class='Label'>Descrição</td>
										<td colspan='4'><input class="Caixa" type="text" name="descricao" size="50" maxlength="50" value="<? echo $descricao ?>"></td>
									</tr>

								<tr>
										<td colspan='6' align='center'>
											<br>
											<input name='btn_acao' type='hidden'>
											<input name='pesquisar' type='button' class='botao' onclick="this.form.btn_acao.value='pesquisar';this.form.submit();" value='Pesquisar'>
										</td>
									</tr>
							</table>
							</form>
					</div>
					<div id="tab2Cadastrar">
						<p>
							<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF ?>">
							<input  type="hidden" name="plano_conta" value="<? echo $plano_conta ?>">
							
							<table  align='center' width='500' border='0' >
									<tr>
										<td class='Label'>Conta</td>
										<td colspan='4'><input class="Caixa" type="text" name="conta" size="10" maxlength="10" value="<? echo $conta ?>"></td>
										
									</tr>
									<tr>
										<td class='Label'>Descricao</td>
										<td align='left' >
											<input class="Caixa" type="text" name="descricao" size="50" maxlength="80" value="<? echo $descricao ?>" ></td>
									</tr>
									<tr>
										<td class='Label'>Débito/Crédito</td>
										<td align='left' >
										<select name="debito_credito" class="Caixa">
											<option value="D" <? if ($debito_credito == "D") echo " SELECTED "; ?>>Débito</option>
											<option value="C" <? if ($debito_credito == "C") echo " SELECTED "; ?>>Crédito</option>
										</select>
										</td>
									</tr>
									<tr>
										<td class='Label'>Despesa Administrativa</td>
										<td align='left' >
										<INPUT TYPE="checkbox" NAME="despesa_administrativa" value='t' <?if($despesa_administrativa=="t")echo "checked";?>> <font size='1'>Sim (Para formação de preço)</font>
										</td>
									</tr>
									<tr>
										<td class='Label' colspan='5' align='center'>
											<input class="botao" type="submit" name="btn_acao"  value='Gravar'>
											<input class="botao" type="button" name="btn_limpar" onclick='limpar_form(this.form)'  value='Limpar' >
										</td>
									</tr>
							</table>
							</form>

						</p>
					</div>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'></td>
		</tr>
</table>
<? } ?>



<?
$btn_acao = $_POST['btn_acao'];
	
if(strlen ($msg_erro) == 0 AND strlen($btn_acao)>0){

	$conta     = $_POST['conta'];
	$descricao = $_POST['descricao'];
	
	if(strlen($conta)>0)     $cond2 .= " AND conta     LIKE  '%$conta%' ";
	if(strlen($descricao)>0) $cond2 .= " AND descricao ILIKE '%$descricao%' ";

	
	$sql = "SELECT  plano_conta      ,
			conta                    ,
			descricao                ,
			debito_credito           ,
			despesa_administrativa
		FROM tbl_plano_conta
		WHERE empresa = $login_empresa
		$cond2
		ORDER BY conta ASC";
	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' class='tabela'>";
		echo "<caption>";
		echo "Relação de plano de conta";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#95ACCE'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Descrição</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Débito/Crédito</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><acronym title='Para formação de preço' style='cursor:help;'><b>Despesa Administrativa</b></a></td>";

		
		echo "<td align='center' class='Titulo_Colunas'><b>Ações</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {

			$plano_conta  = trim(pg_result($res,$k,plano_conta));
			$conta        = trim(pg_result($res,$k,conta));
			$descricao    = trim(pg_result($res,$k,descricao));
			$debito_credito = trim(pg_result($res,$k,debito_credito));
			$despesa_administrativa = trim(pg_result($res,$k,despesa_administrativa));
			if($despesa_administrativa=="t"){
				$despesa_administrativa = "<font color='#009900'>Sim</font>";
			}else{
				$despesa_administrativa = "<font color='#990000'>Não</font>";
			}
			if($debito_credito == 'C') $debito_credito = "<font color='#009900'>Crédito</font>";
			if($debito_credito == 'D') $debito_credito = "<font color='#990000'>Débito</font>";

			if($k%2==0)$cor = '#ECF3FF';
			else               $cor = '#FFFFFF';

			echo "<tr bgcolor='$cor' class='linha'>";
			echo "<td align='center'>$conta</td>";
			echo "<td align='left'  >$descricao</td>";
			echo "<td align='left'  >$debito_credito</td>";
			echo "<td align='center'>$despesa_administrativa</td>";
			echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&plano_conta=$plano_conta#tab2Cadastrar'>Alterar</a>";
			echo "</td>";

			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "Nenhum $tipo cadastrado.";
	}
}


//--===== FIM - Lançamento de Peças =====================================================================

?>

<?
 include "rodape.php";
 ?>
