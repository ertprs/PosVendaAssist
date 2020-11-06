<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}
$marca = trim($_GET['modelo']);
if (strlen($marca)==0) $marca = trim($_POST['modelo']);

if ($btn_acao=='pesquisar'){
	//campos da tabela peça
	$referencia            = trim($_POST['referencia']);
	$nome                  = trim($_POST['nome']);

	$sql_adicional = "";
	if (strlen($referencia)>0) $sql_adicional  = "AND marca  = '$referencia' ";
	if (strlen($nome)>0)       $sql_adicional .= "AND nome ilike '%$nome%'";
}


if ($btn_acao == "Gravar") {

	$txt_marca = trim($_GET['txt_marca']);
	if (strlen($txt_marca)==0) $txt_marca = trim($_POST['txt_marca']);

	$nome = trim($_GET['txt_nome']);
	if (strlen($nome)==0) $nome = trim($_POST['txt_nome']);

	if (strlen($nome)==0){
		$msg_erro = "Informe o nome da marca!";	
	}

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($txt_marca)==0){
			$sql = "INSERT INTO tbl_marca (nome,fabrica,empresa)
							VALUES ('$nome',0,$login_empresa)";
		}else{
			$sql = "UPDATE tbl_marca 
					SET		nome='$nome'
					WHERE marca=$txt_marca
					AND fabrica=0
					AND empresa=$login_empresa";
		}
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$btn_acao  ="";
			$txt_marca ="";
			$nome      ="";
			$msg = " Marca alterada com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	$marca = trim($_GET['marca']);
	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "DELETE FROM tbl_marca
				WHERE marca=$marca
				WHERE empresa=$login_empresa";

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


if ($btn_acao == "alterar") {
	$marca = trim($_GET['marca']);

	$sql = "SELECT marca,nome
			FROM tbl_marca
			WHERE marca=$marca";
	$res = pg_exec ($con,$sql) ;

	$marca    = trim(pg_result($res,0,marca));
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
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});
	$(document).ready(
	function()
	{

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
table.tabela tr{
	height:20px;
}
table.tabela tr:hover {
	background: #C2E8FE;
}
tr.linha td {
	border-bottom: 1px solid #c0c0c0; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
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
	color:#FFFFFF;
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
			<td class='Titulo_Tabela' align='center' colspan='6'>Cadastro de Marca</td>
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
							<input  type="hidden" name="txt_marca" value="<? echo $marca ?>">

							<table  align='center' width='450' border='0' class='tabela'>
									<tr height='3'>
										<td  colspan='5'>&nbsp;</td>
									</tr>
									<tr>
										<td class='Label'>Nome:</td>
										<td colspan='4'><input class="Caixa" type="text" name="txt_nome" size="60" maxlength="60" value="<? echo $nome ?>"></td>
										
									</tr>
									<?
										if (strlen($marca)>0)   $btn_msg="Gravar Alterações";
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

	$sql = "SELECT marca,nome
			FROM tbl_marca
			WHERE empresa=$login_empresa
			$sql_adicional 
			ORDER BY nome ASC";
	//if($ip=='200.228.76.7'){ echo $sql;}

	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' cellspacing='0' cellpadding='0' class='tabela'>";
		echo "<caption>";
		echo "Marcas";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#7392BF'>";
		echo "<th align='center' class='Titulo_Tabela'><b>Código</b></th>";
		echo "<th align='left'   class='Titulo_Tabela'><b>Nome</b></th>";
		echo "<th align='center' class='Titulo_Tabela'><b>Ações</b></th>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$marca    = trim(pg_result($res,$k,marca));
			$nome     = trim(pg_result($res,$k,nome));
			
			echo "<tr class='linha'>";
			echo "<td align='center'><input type='hidden' name='marca' value='$marca'>$marca</td>";
			echo "<td align='left'  >$nome</td>";
			echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&marca=$marca#tab2Cadastrar'>Alterar</a>";
			echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
			echo "<a href=\"javascript:if (confirm('Deseja excluir?')) window.location='$PHP_SELF?btn_acao=excluir&marca=$marca'\">Excluir</a>";
			echo "</td>";

			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "<br><br><p>Nenhuma marca encontrado</p>";
	}
}

//--===== FIM - Lançamento de Peças =====================================================================

?>

<?
 //include "rodape.php";
 ?>
