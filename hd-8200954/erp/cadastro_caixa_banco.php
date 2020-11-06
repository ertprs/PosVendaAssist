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

	$id_caixa_banco   = trim($_POST['caixa_banco']);
	$banco            = trim($_POST['banco']);
	$agencia          = trim($_POST['agencia']);
	$conta            = trim($_POST['conta']);
	$descricao        = trim($_POST['descricao']);

	if(strlen($banco)     == 0 ) $msg_erro .= "Digite o banco<br>";
	if(strlen($agencia)   == 0 ) $msg_erro .= "Digite a agencia<br>";
	if(strlen($conta)     == 0 ) $msg_erro .= "Digite a conta<br>";
	if(strlen($descricao) == 0 ) $msg_erro .= "Digite a descrição<br>";


	//INFORMAÇÕES GERAIS
	if(strlen($banco)     > 0) $xbanco      = "'".$banco."'";
	else                       $xbanco      = "null";
	if(strlen($agencia)   > 0) $xagencia    = "'".$agencia."'";
	else                       $xagencia    = "null";
	if(strlen($conta)     > 0) $xconta      = "'".$conta."'";
	else                       $xconta      = "null";
	if(strlen($descricao) > 0) $xdescricao  = "'".$descricao."'";
	else                       $xdescricao  = "null";



	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//--=== Cadastro de Principal ============================================================================
		if (strlen($id_caixa_banco)==0){

			$sql = "INSERT INTO tbl_caixa_banco (
					banco            ,
					agencia          ,
					conta            ,
					descricao        ,
					empresa
				)VALUES (
					$xbanco           ,
					$xagencia         ,
					$xconta           ,
					$xdescricao       ,
					$login_empresa
				)";
					
		}else{
			$sql = "UPDATE tbl_caixa_banco SET
					banco     = $xbanco     ,
					agencia   = $xagencia   ,
					conta     = $xconta     ,
					descricao = $xdescricao
				WHERE caixa_banco = $id_caixa_banco ";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Caixa/Banco gravado com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "alterar") {
	if(strlen($_GET["caixa_banco"])>0)$caixa_banco = trim($_GET['caixa_banco']);

	$sql = "SELECT  caixa_banco                ,
			banco                      ,
			agencia                    ,
			conta                      ,
			descricao
		FROM tbl_caixa_banco
		WHERE empresa     = $login_empresa
		AND   caixa_banco = $caixa_banco";
	$res = pg_exec ($con,$sql) ;

	$caixa_banco      = trim(pg_result($res,0,caixa_banco));
	$banco            = trim(pg_result($res,0,banco));
	$agencia          = trim(pg_result($res,0,agencia));
	$conta            = trim(pg_result($res,0,conta));
	$descricao        = trim(pg_result($res,0,descricao));
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
a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
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
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}
.ok{
	font-family: Verdana;
	font-size: 12px;
	color:blue;
	border:#39AED5 1px solid; background-color: #B0DFEE;
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
			<td class='Titulo_Tabela' align='center' colspan='6'>CAIXA BANCO</td>
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
										<td align='left' ><input class="Caixa" type="text" name="conta" id="conta" size="10" maxlength="10" value="<? echo $conta ?>" ></td>
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
						<input  type="hidden" name="caixa_banco" value="<? echo $caixa_banco ?>">

						<table  align='center' width='400' border='0' >
						<tr>
							<td class='Label'>Banco</td>
							<td colspan='4'><?
								$sql = "SELECT codigo, nome
										FROM tbl_banco
										Order by nome";	
								$res = pg_exec($con,$sql);
								if(pg_numrows($res)>0){
									echo "<SELECT name='banco' size='1'>";
									echo "<option></option>";
									for($x=0;pg_numrows($res)>$x;$x++){
										$codigo = pg_result($res,$x,codigo);
										$nome = pg_result($res,$x,nome);
										echo "<option value='$codigo'>$codigo - $nome</option>";
									}
									echo "</select>";
								}
								?>
							</td>
		
		</tr>
		<tr>
			<td class='Label'>Agencia</td>
			<td align='left' >
				<input class="Caixa" type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>" ></td>
		</tr>
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

	$sql = "SELECT  caixa_banco  ,
			banco        ,
			agencia      ,
			conta        ,
			descricao
		FROM tbl_caixa_banco
		WHERE empresa = $login_empresa
		$cond2
		ORDER BY conta ASC";

	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' class='tabela'>";
		echo "<caption>";
		echo "Relação dos serviços cadastrados";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#95ACCE'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Banco</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Agencia</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Conta</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Descrição</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Ações</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {

			$caixa_banco  = trim(pg_result($res,$k,caixa_banco));
			$banco        = trim(pg_result($res,$k,banco));
			$agencia      = trim(pg_result($res,$k,agencia));
			$conta        = trim(pg_result($res,$k,conta));
			$descricao    = trim(pg_result($res,$k,descricao));
			/*$valor         = trim(pg_result($res,$k,valor));
			$ativo         = trim(pg_result($res,$k,ativo));
			*/
			if($k%2==0)$cor = '#ECF3FF';
			else               $cor = '#FFFFFF';

			echo "<tr bgcolor='$cor' class='linha'>";

			echo "<td align='left'  >$banco</td>";
			echo "<td align='left'  >$agencia</td>";
			echo "<td align='center'>$conta</td>";
			echo "<td align='left'  >$descricao</td>";
			echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&caixa_banco=$caixa_banco#tab2Cadastrar'>Alterar</a>";
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
