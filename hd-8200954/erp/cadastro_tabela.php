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

	$tabela           = trim($_POST['tabela']);
	$sigla_tabela     = trim($_POST['sigla_tabela']);
	$descricao        = trim($_POST['descricao']);
	$ativa            = trim($_POST['ativa']);
	$internet         = trim($_POST['internet']);
	$tabela_principal = trim($_POST['tabela_principal']);


	if(strlen($sigla_tabela)     == 0 ) $msg_erro .= "Digite a Sigla<br>";
	if(strlen($descricao) == 0 ) $msg_erro .= "Digite a descrição<br>";

	//INFORMAÇÕES GERAIS
	if(strlen($sigla_tabela)     > 0) $xsigla_tabela      = "'".$sigla_tabela."'";
	else                       $xsigla_tabela      = "null";
	if(strlen($descricao) > 0) $xdescricao  = "'".$descricao."'";
	else                       $xdescricao  = "null";

	if(strlen($ativa) == 0){
		$ativa = "'f'";
	}else{
		$ativa = "'t'";
	}

	if(strlen($internet) == 0){
		$internet = "'f'";
	}else{
		$internet = "'t'";
	}

	if(strlen($tabela_principal) == 0){
		$tabela_principal = "'f'";
	}else{
		$tabela_principal = "'t'";
	}


	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//--=== Cadastro de Principal ============================================================================
		if (strlen($tabela)==0){

			$sql = "INSERT INTO tbl_tabela (
					sigla_tabela            ,
					descricao        ,
					fabrica          ,
					ativa            ,
					internet         ,
					data_inclusao    ,
					tabela_principal
				)VALUES (
					$xsigla_tabela   ,
					$xdescricao      ,
					$login_empresa   ,
					$ativa           ,
					$internet        ,
					current_date     ,
					$tabela_principal
				)";
		}else{
			$sql = "UPDATE tbl_tabela SET
					sigla_tabela     = $xsigla_tabela   ,
					descricao        = $xdescricao      ,
					ativa            = $ativa           ,
					internet         = $internet        ,
					tabela_principal = $tabela_principal
				WHERE tabela         = $tabela ";
		}
//echo $sql;
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);

//HD 4746 atualizar as tabelas como falso, se tabela é tabela principal
		$sqltab1="SELECT *
					FROM tbl_tabela
					WHERE tabela = $tabela
					AND   fabrica = $login_empresa";

		$restab1=pg_exec($con,$sqltab1);
		$msg_erro = pg_errormessage($con);

		$tabela_principal = trim(pg_result($restab1,0,tabela_principal));

		if($tabela_principal=='t') {
			$sqltab2="UPDATE tbl_tabela set
						tabela_principal = 'f'
						WHERE tabela <> $tabela
						AND   fabrica=$login_empresa";

			$restab2=pg_exec($con,$sqltab2);
			$msg_erro = pg_errormessage($con);
		}
		
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Tabela de preço gravada com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "alterar") {
	if(strlen($_GET["tabela"])>0)$tabela = trim($_GET['tabela']);

	$sql = "SELECT  tabela    ,
			sigla_tabela      ,
			descricao         ,
			ativa             ,
			internet          ,
			tabela_principal
		FROM tbl_tabela
		WHERE fabrica     = $login_empresa
		AND   tabela = $tabela";
	$res = pg_exec ($con,$sql) ;

	$tabela           = trim(pg_result($res,0,tabela));
	$sigla_tabela     = trim(pg_result($res,0,sigla_tabela));
	$descricao        = trim(pg_result($res,0,descricao));
	$ativa            = trim(pg_result($res,0,ativa));
	$internet         = trim(pg_result($res,0,internet));
	$tabela_principal = trim(pg_result($res,0,tabela_principal));
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
		//$("#sigla_tabela").focus();
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
			<td class='Titulo_Tabela' align='center' colspan='6'>TABELA DE PREÇO</td>
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
										<td class='Label'>Sigla</td>
										<td align='left' ><input class="Caixa" type="text" name="sigla_tabela" id="sigla_tabela" size="10" maxlength="15" value="<? echo $conta ?>" ></td>
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
							<input  type="hidden" name="tabela" value="<? echo $tabela ?>">
							
							<table  align='center' width='500' border='0' >
									<tr>
										<td class='Label'>Sigla</td>
										<td colspan='4'><input class="Caixa" type="text" name="sigla_tabela" size="10" maxlength="10" value="<? echo $sigla_tabela ?>"></td>
										
									</tr>
									<tr>
										<td class='Label'>Descrição</td>
										<td align='left' >
											<input class="Caixa" type="text" name="descricao" size="50" maxlength="80" value="<? echo $descricao ?>" ></td>
									</tr>
									<tr>
										<td class='Label'>Ativo</td>
										<td align='left' >
										<INPUT TYPE="checkbox" NAME="ativa" value='t' <?if($ativa=="t")echo "checked";?>> <font size='1'>Sim</font>
										</td>
									</tr>
									<tr>
										<td class='Label'>Internet</td>
										<td align='left' >
										<INPUT TYPE="checkbox" NAME="internet" value='t' <?if($internet=="t")echo "checked";?>> <font size='1'>Sim</font>
										</td>
									</tr>
									<tr>
										<td class='Label'>Tabela principal</td>
										<td align='left' >
										<INPUT TYPE="checkbox" NAME="tabela_principal" value='t' <?if($tabela_principal=="t")echo "checked";?>> <font size='1'>Sim</font>
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

	
	$sql = "SELECT  tabela     ,
			sigla_tabela       ,
			descricao          ,
			ativa              ,
			internet           ,
			tabela_principal   ,
			to_char(data_inclusao,'dd/mm/yyyy') as data_inclusao
		FROM tbl_tabela
		WHERE fabrica = $login_empresa
		$cond2
		ORDER BY sigla_tabela ASC";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<br>";
		##### LEGENDAS - INÍCIO #####
		echo "<div align='left' style='position: relative; left: 150'>";
		echo "<table border='0' cellspacing='0' cellpadding='0'>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FFB0B0'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Tabela principal</b></font></td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
		echo "<BR>";
			##### LEGENDAS - FIM #####
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' class='tabela'>";
		echo "<caption>";
		echo "Relação de tabela(s) de preço ativa(s)";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#95ACCE'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Sigla</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Descrição</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Data Cadastro</b></td>";
		echo "<td align='center'   class='Titulo_Colunas'><b>Ativo</b></td>";
		echo "<td align='center'   class='Titulo_Colunas'><b>Internet</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Ações</b></td>";
		echo "</tr>";

		for ($k = 0; $k <pg_numrows($res) ; $k++) {

			$tabela       = trim(pg_result($res,$k,tabela));
			$sigla_tabela        = trim(pg_result($res,$k,sigla_tabela));
			$descricao    = trim(pg_result($res,$k,descricao));
			$ativa        = trim(pg_result($res,$k,ativa));
			$internet        = trim(pg_result($res,$k,internet));
			$data_inclusao= trim(pg_result($res,$k,data_inclusao));
			if($ativa=="t"){
				$ativa = "<font color='#009900'>Sim</font>";
			}else{
				$ativa = "<font color='#990000'>Não</font>";
			}

			if($internet=="t"){
				$internet = "<font color='#009900'>Sim</font>";
			}else{
				$internet = "<font color='#990000'>Não</font>";
			}

			if($debito_credito == 'C') $debito_credito = "<font color='#009900'>Crédito</font>";
			if($debito_credito == 'D') $debito_credito = "<font color='#990000'>Débito</font>";

			if($k%2==0)$cor = '#ECF3FF';
			else               $cor = '#FFFFFF';
	
			$sqlcor="SELECT * 
					FROM tbl_tabela 
					WHERE tabela_principal='t' 
					AND   tabela=$tabela
					AND fabrica=$login_empresa";
			$rescor=pg_exec($con,$sqlcor);
			if(pg_numrows($rescor) > 0) {
				$cor='#FFB0B0';
			}


			echo "<tr bgcolor='$cor' class='linha'>";
			echo "<td align='center'>$sigla_tabela</td>";
			echo "<td align='left'  ><a href='$PHP_SELF?btn_acao=alterar&tabela=$tabela#tab2Cadastrar'>$descricao</a></td>";
			echo "<td align='center'>$data_inclusao</td>";
			echo "<td align='center'  >$ativa</td>";
			echo "<td align='center'  >$internet</td>";
			echo "<td align='center'  ><a href='$PHP_SELF?btn_acao=alterar&tabela=$tabela#tab2Cadastrar'><img src='imagens/pencil.png'></a></td>";
			
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
