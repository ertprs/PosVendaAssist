<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}

$servico = trim($_GET['servico']);
if (strlen($servico)==0) $servico = trim($_POST['servico']);

if ($btn_acao=='pesquisar'){
	//campos da tabela peça
	$referencia            = trim($_POST['referencia']);
	$descricao             = trim($_POST['descricao']);
	$ativo                 = trim($_POST['ativo']);

	$sql_adicional = "";
	if (strlen($referencia)>0) $sql_adicional  = "AND servico  = '$referencia' ";
	if (strlen($descricao)>0)  $sql_adicional .= "AND descricao like '%$descricao%'";
	if (strlen($ativo)>0)      $sql_adicional .= "AND ativo = 't'";
}

if ($btn_acao == "Gravar") {

	$descricao = trim($_GET['txt_descricao']);
	if (strlen($descricao)==0) $descricao = trim($_POST['txt_descricao']);

	if (strlen($descricao)==0){
		$msg_erro = "Informe a descrição do serviço!";	
	}

	$valor = trim($_GET['txt_valor']);
	if (strlen($valor)==0) $valor = trim($_POST['txt_valor']);

	$valor = str_replace(",",".",$valor);

	if (strlen($valor)==0){
		$msg_erro = "Informe o valor do serviço!";	
	}


	$ativo = trim($_GET['txt_ativo']);
	if (strlen($ativo)==0) $ativo = $_POST['txt_ativo'];

	if ($ativo=='t'){
		$ativo="'t'";
	}
	else{
		$ativo="'f'";
	}


	if (strlen($valor)==0){
		$msg_erro = "Informe o valor!";	
	}

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($servico)==0){
			$sql = "INSERT INTO tbl_servico (
							descricao,
							valor,
							ativo,
							fabrica,
							posto)
							VALUES (
							'$descricao',
							$valor,
							$ativo,
							$login_empresa,
							$login_loja
							)";
		}else{
			$sql = "UPDATE tbl_servico 
					SET
							descricao='$descricao',
							valor=$valor,
							ativo=$ativo
					WHERE servico=$servico";
		}
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "Serviço gravado com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	if (strlen($servico)==0) $msg_erro="Selecione um serviço / mão de obra.";
	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "DELETE FROM tbl_servico
				WHERE servico=$servico
				AND fabrica=$login_empresa
				AND posto=$login_loja";

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "Serviço excluído com sucesso!";
			$servico="";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if (strlen($servico)>0) {
	$sql = "SELECT servico,descricao,valor,ativo
			FROM tbl_servico
			WHERE fabrica=$login_empresa
			AND posto=$login_loja
			AND servico=$servico";
	$res = pg_exec ($con,$sql) ;
	$servico       = trim(pg_result($res,0,servico));
	$descricao     = trim(pg_result($res,0,descricao));
	$valor         = trim(pg_result($res,0,valor));
	$ativo         = trim(pg_result($res,0,ativo));
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
.tabela tr{
	height:20px;
}
.tabela tr:hover {
	background: #C2E8FE;
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
<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});
		$(document).ready(
	function()
	{
		//$("#referencia").focus();
	}
);
</script>

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
			<td class='Titulo_Tabela' align='center' colspan='6'>Cadastro de Serviços / Mão de Obra</td>
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
										<td align='left' ><input class="Caixa" type="text" name="referencia"  id="referencia" size="10" maxlength="10" value="<? echo $referencia ?>" ></td>
									</tr>
									<tr>
										<td class='Label'>Descrição</td>
										<td colspan='4'><input class="Caixa" type="text" name="descricao" size="50" maxlength="50" value="<? echo $descricao ?>"></td>
									</tr>
									<tr>
										<td class='Label'>Ativo</td>
										<td colspan='4'><input class="Caixa" type="checkbox" name="ativo" <? if ($ativo=='t') echo " CHECKED " ?>></td>
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
							<form name="frm_cad_ser" method="post" action="<? echo $PHP_SELF ?>">
							<input  type="hidden" name="servico" value="<? echo $servico ?>">

							<table align='center' width='450' border='0'  >
									<tr height='3'>
										<td  colspan='5'>&nbsp;</td>
									</tr>
									<tr>
										<td class='Label'>Descrição:</td>
										<td colspan='4'><input class="Caixa" type="text" name="txt_descricao" size="60" maxlength="60" value="<? echo $descricao ?>"></td>
										
									</tr>
									<tr>
										<td class='Label'>Valor</td>
										<td align='left' colspan='4'>
											<input class="Caixa" type="text" name="txt_valor"   size="10" maxlength="10" value="<? echo $valor ?>" onblur="javascript:checarNumero(this)"></td>
									</tr>
									<tr>
										<td class='Label'>Ativo</td>
										<td align='left' colspan='4'>
											<input type="checkbox" name="txt_ativo" value='t' <? if ($ativo=='t')echo "CHECKED"; ?>>
											</td>
									</tr>
									<?
										if (strlen($servico)>0) $btn_msg="Gravar Alterações";
										else                    $btn_msg="Gravar";
									?>
									<tr>
										<td class='Label' colspan='5' align='center'>
											<input class="botao" type="hidden" name="btn_acao"  value=''>
											<input class="botao" type="button" name="bt"        value='<? echo $btn_msg ?>' onclick="javascript:if (this.form.btn_acao.value!='') alert('Aguarde Submissão'); else{this.form.btn_acao.value='Gravar';this.form.submit();}">
											<input class="botao" type="button" name="btn_cancelar" onclick='javascript:window.location="cadastro_servico.php"'  value='Cancelar' >
											<input class="botao" type="button" name="btn_limpar" onclick='limpar_form(this.form)'  value='Limpar' >
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
	$sql = "SELECT	servico,
					descricao,
					valor,
					ativo
			FROM tbl_servico
			WHERE fabrica=$login_empresa
			AND posto=$login_loja
			$sql_adicional
			ORDER BY servico ASC";

	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' cellspacing='0' cellpadding='0' class='tabela'>";
		echo "<caption>";
		echo "Serviços / Mão de Obra encontrados";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#95ACCE'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Descrição</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Valor</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Ativo</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Ações</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$servico       = trim(pg_result($res,$k,servico));
			$descricao     = trim(pg_result($res,$k,descricao));
			$valor         = trim(pg_result($res,$k,valor));
			$ativo         = trim(pg_result($res,$k,ativo));
			
			$valor = number_format($valor, 2,',', '');

			if($k%2==0)$cor = '#ECF3FF';
			else               $cor = '#FFFFFF';

			echo "<tr bgcolor='$cor' class='linha'>";
			echo "<td align='center'><input type='hidden' name='servico' value='$servico'>$servico</td>";
			echo "<td align='left'  >$descricao</td>";
			echo "<td align='center'>$valor</td>";

			if ($ativo=='t') $ativo="<img src='imagens/status_verde.gif'>";
			else             $ativo="<img src='imagens/status_vermelho.gif'>";

			echo "<td align='center'>$ativo</td>";
			echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&servico=$servico#tab2Cadastrar'>Alterar</a>";
			echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
			echo "<a href=\"javascript:if (confirm('Deseja excluir?')) window.location='$PHP_SELF?btn_acao=excluir&servico=$servico'\">Excluir</a>";
			echo "</td>";

			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "<br><br><p>Nenhum serviço / mão de obra encotrado</p>";
	}
}


//--===== FIM - Lançamento de Peças =====================================================================

?>

<?
 //include "rodape.php";
 ?>
