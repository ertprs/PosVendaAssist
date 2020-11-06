<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}

$modelo = trim($_GET['modelo']);
if (strlen($modelo)==0) $modelo = trim($_POST['modelo']);

if ($btn_acao=='pesquisar'){
	//campos da tabela peça
	$referencia            = trim($_POST['referencia']);
	$nome                  = trim($_POST['nome']);

	$sql_adicional = "";
	if (strlen($referencia)>0) $sql_adicional  = "AND modelo  = '$referencia' ";
	if (strlen($nome)>0)  $sql_adicional .= "AND nome like '%$nome%'";
}


if ($btn_acao == "Gravar") {

	$nome = trim($_GET['txt_nome']);
	if (strlen($nome)==0) $nome = trim($_POST['txt_nome']);

	if (strlen($nome)==0){
		$msg_erro = "Informe o nome do modelo!";	
	}

//	if (strlen($modelo)==0) $msg_erro="Selecione o modelo!";

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($modelo)==0){
			$sql = "INSERT INTO tbl_modelo (nome,fabrica)
							VALUES ('$nome',$login_empresa)";
		}else{
			$sql = "UPDATE tbl_modelo 
					SET nome = '$nome'
					WHERE modelo  = $modelo
					AND   fabrica = $login_empresa";
		}
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$btn_acao  = "";
			$nome      = "";
			$modelo    = "";
			$msg = " Modelo gravado com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	$modelo = trim($_GET['modelo']);
	if (strlen($modelo)==0) $modelo = trim($_POST['modelo']);

	if (strlen($modelo)==0) $msg_erro = "Selecione o modelo a ser excluido!";

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "DELETE FROM tbl_modelo
				WHERE modelo  = $modelo
				WHERE fabrica = $login_empresa";

		//$res = pg_exec ($con,$sql);
		//$msg_erro = pg_errormessage($con);
		$msg_erro .= "Sem permissão para excluir";
		if (strlen ($msg_erro) == 0) {
			//$res = pg_exec ($con,"COMMIT TRANSACTION");
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


if (strlen($modelo)>0) {
	$sql = "SELECT modelo,nome
			FROM tbl_modelo
			WHERE modelo = $modelo";
	$res = pg_exec ($con,$sql) ;

	$modelo    = trim(pg_result($res,0,modelo));
	$nome     = trim(pg_result($res,0,nome));
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
		$('#container-Principal').tabs();
	});
	$(document).ready(
	function()
	{
		$('#container-Principal').tabsSelected();
		//$("#referencia").focus();
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
tr.linha td {
	border-bottom: 1px solid #c0c0c0; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}

.linha tr:hover {
	background-color:#D2ECFF;
}
</style>

<?$data_abertura = date("d/m/Y");?>

<? if (strlen($msg_erro)>0) {?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?}?>

<? if (strlen($msg)>0) {?>
<div class='ok'>
	<? echo $msg; ?>
</div>
<?}?>

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Cadastro de Modelo</td>
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
										<td class='Label'>Código</td>
										<td align='left' ><input class="Caixa" type="text" name="referencia" id="referencia" size="10" maxlength="10" value="<? echo $referencia ?>" ></td>
									</tr>
									<tr>
										<td class='Label'>Nome</td>
										<td colspan='4'><input class="Caixa" type="text" name="nome" size="50" maxlength="50" value="<? echo $nome ?>"></td>
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
						<!--<a href='<? echo $PHP_SELF ?>?btn_acao=cadastrar'><img src='imagens/edit2.png' align='absmiddle'> Cadastar um novo serviço / mão de obra</a> -->

						</p>
						<form name="frm_cad_ser" method="post" action="<? echo $PHP_SELF ?>#tab2Cadastrar">
						<input  type="hidden" name="modelo" value="<? echo $modelo ?>">

						<table  align='center' width='500' border='0' class='tabela'>
								<tr height='3'>
									<td  colspan='5'>&nbsp;</td>
								</tr>
								<tr>
									<td class='Label'>Nome:</td>
									<td colspan='4'><input class="Caixa" type="text" name="txt_nome" id="txt_nome" size="60" maxlength="60" value="<? echo $nome ?>"></td>
								</tr>
									<?
										if (strlen($modelo)>0)  $btn_msg="Gravar Alterações";
										else                    $btn_msg="Gravar";
									?>
								<tr>
									<td class='Label' colspan='5' align='center'>
										<input class="botao" type="hidden" name="btn_acao"  value=''>
										<input class="botao" type="button" name="bt"        value='<? echo $btn_msg ?>' onclick="javascript:if (this.form.btn_acao.value!='') alert('Aguarde Submissão'); else if (confirm('Deseja continuar?')){
											this.form.btn_acao.value='Gravar';this.form.submit();
											}">
										<input class="botao" type="button" name="btn_cancelar" onclick='javascript:window.location="cadastro_modelo.php"'  value='Cancelar' >
										<input class="botao" type="button" name="btn_limpar" onclick='limpar_form(this.form)'  value='Limpar' >
										<input class="botao" type="button" name="bt"        value='Excluir' onclick="javascript:if (this.form.btn_acao.value!='') alert('Aguarde Submissão'); else{this.form.btn_acao.value='excluir';this.form.submit();}">
									</td>
								</tr>
						</table>
						</form>

					</div>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'></td>
		</tr>
</table>

<?
if(strlen ($msg_erro) == 0 AND $btn_acao=='pesquisar'){

	$sql = "SELECT modelo,nome
			FROM tbl_modelo
			WHERE fabrica =$login_empresa
			$sql_adicional
			ORDER BY nome ASC";

	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' cellspacing='0' cellpadding='0' class='tabela'>";
		echo "<caption>";
		echo "Modelos Cadastrados";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#7392BF'>";
		echo "<td align='center' class='Titulo_Tabela'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Tabela'><b>Nome</b></td>";
		echo "<td align='center' class='Titulo_Tabela'><b>Ações</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$modelo    = trim(pg_result($res,$k,modelo));
			$nome     = trim(pg_result($res,$k,nome));

			if($k%2==0)$cor = '#ECF3FF';
			else               $cor = '#FFFFFF';

			echo "<tr bgcolor='$cor' class='linha'>";
			echo "<td align='center'><input type='hidden' name='modelo' value='$modelo'>$modelo</td>";
			echo "<td align='left'  >$nome</td>";
			echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&modelo=$modelo#tab2Cadastrar'>Alterar</a>";
			echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
			echo "<a href=\"javascript:if (confirm('Deseja excluir?')) window.location='$PHP_SELF?btn_acao=excluir&modelo=$modelo'\">Excluir</a>";
			echo "</td>";

			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "<br><br><p>Nenhum modelo encontrado</p>";
	}

}
//--===== FIM - Lançamento de Peças =====================================================================

?>

<?
 //include "rodape.php";
 ?>
