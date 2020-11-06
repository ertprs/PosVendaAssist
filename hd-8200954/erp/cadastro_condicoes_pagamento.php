<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}


if ($btn_acao == "Gravar") {

	$condicao = trim($_POST['condicao']);
//	if (strlen($txt_condicao)==0) $txt_condicao = trim($_POST['txt_condicao']);

	$codigo = trim($_POST['txt_codigo']);

	$descricao = trim($_POST['txt_descricao']);
//	if (strlen($descricao)==0) $descricao = trim($_POST['txt_descricao']);
	
	$tabela_preco = trim($_POST['tabela_preco']);
	if(strlen($tabela_preco)==0)$msg_erro = "Por favor informe a tabela de preço";	
	if (strlen($descricao)==0){
//		$msg_erro .= "Informe a descrição do serviço!";	
	}

	$parcelas_array= array();
	for ($i=0; $i<30; $i++){
		$par = trim($_POST["txt_parcela_$i"]);
		if (strlen($par)==0) continue;
		array_push($parcelas_array,$par);
	}
	if (count($parcelas_array)>0){
		$descricao = count($parcelas_array)." vezes";
		$parcelas = implode("|",$parcelas_array);

		if ($parcelas_array[0]==0 AND count($parcelas_array)==1){
			$descricao = "Á Vista";
		}
	}
	else{
		$msg_erro .= "Informe ao menos 1 parcela. Pagamentos à vista é 0";	
	}

	$visivel = trim($_POST['txt_visivel']);

	if ($visivel=='t')	$visivel="'t'";
	else				$visivel="'f'";

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($condicao)==0){
			$sql = "INSERT INTO tbl_condicao (
							descricao           ,
							codigo_condicao     ,
							parcelas            ,
							visivel             ,
							fabrica             ,
							tabela
							)
							VALUES (
							'$descricao',
							'$codigo',
							'$parcelas',
							$visivel,
							$login_empresa,
							$tabela_preco
							)";
		}else{
			$sql = "UPDATE tbl_condicao 
					SET
							descricao       = '$descricao',
							codigo_condicao = '$codigo',
							parcelas        = '$parcelas',
							visivel         = $visivel,
							tabela          = $tabela_preco
					WHERE condicao = $condicao";
		}
//echo $sql;
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "Condição de pagamento gravado com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	$condicao = trim($_GET['condicao']);
	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		 $sql = "DELETE FROM tbl_condicao
				WHERE condicao = $condicao
				AND fabrica    = $login_empresa";
				/*08/08/06 conforme conversa com Fábio, colocar apenas como nao visivel*/
		$sql = "UPDATE tbl_condicao set visivel = 'f'
				WHERE condicao = $condicao
				AND fabrica    = $login_empresa";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "Condição excluída com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


if ($btn_acao == "alterar") {
	$condicao = trim($_GET['condicao']);

	$sql = "SELECT	tbl_condicao.condicao         ,
					tbl_condicao.codigo_condicao  ,
					tbl_condicao.descricao        ,
					tbl_condicao.parcelas         ,
					tbl_condicao.visivel          ,
					tbl_condicao.tabela           
			FROM tbl_condicao
			WHERE tbl_condicao.fabrica =$login_empresa
			AND   tbl_condicao.condicao=$condicao";
	$res = pg_exec ($con,$sql) ;

	$condicao      = trim(pg_result($res,0,condicao));
	$codigo        = trim(pg_result($res,0,codigo_condicao));
	$descricao     = trim(pg_result($res,0,descricao));
	$parcelas      = trim(pg_result($res,0,parcelas));
	$visivel       = trim(pg_result($res,0,visivel));
	$tabela_preco  = trim(pg_result($res,0,tabela));

	$parcelas = explode("|",$parcelas);

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

function checarSoNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
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
	$(document).ready(
	function()
	{
		//$("#txt_codigo").focus();
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
.tabela tr{
	height:20px;
}
.tabela tr:hover {
	background: #C2E8FE;
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
.ok{
	font-family: Verdana;
	font-size: 12px;
	color:blue;
	border:#39AED5 1px solid; background-color: #B0DFEE;
}
tr.linha td {
	border-bottom: 1px solid #c0c0c0; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}





/*---------- Exemplo Completo: Lista select ----------*/

form.listaValores label {
	background: #F0FFF0;
	border-bottom: 2px solid green;
	font-weight: bold;
}
form.listaValores option {
	background: #FFFFF0;
}
form.listaValores option:nth-child(even) {
	background: #F0FFFF;
}
form.listaValores option.even {
	background: #F0FFFF;
}
form.listaValores option:hover {
	background: #CCF0F0;
}


</style>

<?$data_abertura = date("d/m/Y");?>

<? if (strlen($msg_erro)>0) {
	if (strpos ($msg_erro,"duplicate key violates unique constraint") > 0) $msg_erro = "Cadastro duplicado";
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	
	?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?}?>

<? if (strlen($msg)>0) {?>
<div class='ok'>
	<? echo $msg; ?>
</div>
<?}?>

<form name="frm_cad_cond" method="post" action="<? echo $PHP_SELF ?>">
<input  type="hidden" name="condicao" value="<? echo $condicao ?>">

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='500' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' colspan='5'>Cadastro de Condições de Pagamento</td>
		</tr>
		<tr height='3'>
			<td  colspan='5'>&nbsp;</td>
		</tr>
		<tr>
			<td class='Label'>Código</td>
			<td colspan='4'><input class="Caixa" type="text" name="txt_codigo" id="txt_codigo" size="60" maxlength="60" value="<? echo $codigo ?>"></td>
			
		</tr>
		<tr>
			<td class='Label' valign='top'>Parcelas</td>
			<td align='left'>
			<?
			$cont = count($parcelas);
			for ($i=0;$i<$cont;$i++){
				$tmp_par = $parcelas[$i];
				if ($i%4==0 AND $i<>0)echo "<br>";
				$X=$i+1;
				if (strlen($X)==1) $X = "0$X";
				echo "$X ª <input class='Caixa' type='text' name='txt_parcela_$i' size='2' maxlength='4' value='$tmp_par' onblur='javascript:checarSoNumero(this)'> &nbsp;&nbsp;&nbsp;&nbsp;";
			}
			for ($i=$cont;$i<24;$i++){
				if ($i>24) break;
				if ($i%4==0 AND $i<>0)echo "<br>";
				$X=$i+1;
				if (strlen($X)==1) $X = "0$X";
				echo "$X ª <input class='Caixa' type='text' name='txt_parcela_$i' size='2' maxlength='4' value='' onblur='javascript:checarSoNumero(this)'> &nbsp;&nbsp;&nbsp;&nbsp;";
			}
			?>
			</td>
			
		</tr>
		<tr>
			<td class='Label'>Visível</td>
			<td align='left' colspan='4'>
				<input type="checkbox" name="txt_visivel" value='t' <? if ($visivel=='t')echo "CHECKED"; ?>>
				</td>
		</tr>
		<tr>
			<td class='Label'>Tabela</td>
			<td colspan='4'>
			<select name="tabela_preco" class="Caixa">
<?			$sql = "SELECT	tabela      ,
							sigla_tabela,
							descricao
					FROM  tbl_tabela
					WHERE fabrica = $login_empresa
					AND   ativa is true
					ORDER by descricao";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($i=0;$i<pg_numrows($res);$i++){
					$tabela       = pg_result($res,$i,tabela);
					$sigla_tabela = pg_result($res,$i,sigla_tabela);
					$tabela_nome  = pg_result($res,$i,descricao);
					echo "<option value='$tabela' ";
						if ($tabela == $tabela_preco) echo " SELECTED ";
					echo ">$sigla_tabela $tabela_nome</option>";
				}
			}else{
				echo "<option value=''>Nenhum resultado encontrado</option>";
			}
?>
			</select>
			</td>
		</tr>
		<tr>
			<td class='Label' colspan='5' align='center'>
				<input class="Caixa" type="submit" name="btn_acao"  value='Gravar'>
				<input class="Caixa" type="button" name="btn_limpar" onclick='limpar_form(this.form)'  value='Limpar' >
			</td>
		</tr>
</table>
</form>

<?

	$sql = "SELECT	tbl_condicao.condicao           ,
					tbl_condicao.codigo_condicao    ,
					tbl_condicao.descricao          ,
					tbl_condicao.parcelas           ,
					tbl_condicao.visivel            , 
					tbl_condicao.tabela             ,
					tbl_tabela.descricao as tabela_descricao
			FROM tbl_condicao
			left JOIN tbl_tabela on tbl_condicao.tabela = tbl_tabela.tabela
			WHERE tbl_condicao.fabrica = $login_empresa
			ORDER BY tbl_condicao.condicao ASC";
	
	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' cellspacing='0' cellpadding='0' class='tabela'>";
		echo "<caption>";
		echo "Relações das Condições de Pagamento Cadastradas";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#7392BF'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Descrição</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Tab. Preço</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Visível</b></td>";
		echo "<td align='center' class='Titulo_Colunas'><b>Ações</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$condicao      = trim(pg_result($res,$k,condicao));
			$codigo        = trim(pg_result($res,$k,codigo_condicao));
			$descricao     = trim(pg_result($res,$k,descricao));
			$parcelas      = trim(pg_result($res,$k,parcelas));
			$visivel       = trim(pg_result($res,$k,visivel));
			$tabela        = trim(pg_result($res,$k,tabela));
			$parcelas = str_replace("|","/",$parcelas);
			$tabela_descricao = trim(pg_result($res,$k,tabela_descricao));


			echo "<tr class='linha'>";
			echo "<td align='center'><input type='hidden' name='condicao' value='$condicao'>$codigo</td>";
			echo "<td align='left'>$parcelas dias</td>";
			echo "<td align='left'>$tabela_descricao</td>";
			if ($visivel=='t') $visivel="<img src='imagens/status_verde.gif'>";
			else             $visivel="<img src='imagens/status_vermelho.gif'>";

			echo "<td align='center'>$visivel</td>";
			echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&condicao=$condicao'>Alterar</a>";
			echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
			echo "<a href=\"javascript:if (confirm('Deseja excluir?')) window.location='$PHP_SELF?btn_acao=excluir&condicao=$condicao'\">Excluir</a>";
			echo "</td>";

			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "Nenhuma condição de pagamento cadastrada";
	}

//--===== FIM - Lançamento de Peças =====================================================================

?>

<?
 //include "rodape.php";
 ?>
