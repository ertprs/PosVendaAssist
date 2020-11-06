<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

#Lista de todas as siglas de estagos Brasileiros
$estadosBrasil = array("AC"=>"Acre", "AL"=>"Alagoas", "AM"=>"Amazonas", "AP"=>"Amapá","BA"=>"Bahia","CE"=>"Ceará","DF"=>"Distrito Federal","ES"=>"Espírito Santo","GO"=>"Goiás","MA"=>"Maranhão","MT"=>"Mato Grosso","MS"=>"Mato Grosso do Sul","MG"=>"Minas Gerais","PA"=>"Pará","PB"=>"Paraíba","PR"=>"Paraná","PE"=>"Pernambuco","PI"=>"Piauí","RJ"=>"Rio de Janeiro","RN"=>"Rio Grande do Norte","RO"=>"Rondônia","RS"=>"Rio Grande do Sul","RR"=>"Roraima","SC"=>"Santa Catarina","SE"=>"Sergipe","SP"=>"São Paulo","TO"=>"Tocantins");


#Recebe as variáveis 
if (strlen($_GET["estoque_minimo_parametro"]) > 0)  $estoque_minimo_parametro = trim($_GET["estoque_minimo_parametro"]);
if (strlen($_POST["estoque_minimo_parametro"]) > 0) $estoque_minimo_parametro = trim($_POST["estoque_minimo_parametro"]);

if (strlen($_POST["coeficiente"]) > 0) $coeficiente = trim($_POST["coeficiente"]);
if (strlen($_POST["estado"]) > 0) $estado = trim($_POST["estado"]);

if (strlen($_GET["del"]) == 1) $remover = $_GET["del"];

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}
if ($remover == 1 && $estoque_minimo_parametro > 0) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE 	tbl_estoque_minimo_parametro
			SET  	data_final = NOW(),
					admin = ".$login_admin."
			WHERE 	estoque_minimo_parametro = ".$estoque_minimo_parametro." 
			AND 	fabrica = ".$login_fabrica.";";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header('Location: '.$PHP_SELF);
	} else {
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	
	$coeficiente = str_replace('.','',$coeficiente);
	$coeficiente = str_replace(",",".",$coeficiente);
	
	if(strlen($coeficiente) == 0 || $coeficiente == 0) {
		$msg_erro = "Favor informar o valor do Coeficiente de Multiplicação";
	}

	if (strlen($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		$sql = "UPDATE 	tbl_estoque_minimo_parametro
				SET  	data_final = NOW(),
						admin = ".$login_admin."
				WHERE 	estado = '".$estado."'
				AND 	fabrica = ".$login_fabrica."
				AND 	data_final IS NULL;";

		$result = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		###INSERE NOVO REGISTRO
		$sql = "INSERT INTO tbl_estoque_minimo_parametro
				(estado,fabrica,coeficiente,data_inicio,data_final,admin)
				VALUES
				('$estado',$login_fabrica,$coeficiente,now(),null,$login_admin);";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_success = 'Gravado com sucesso!';

	}

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header('Location: '.$PHP_SELF);

	} else {
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

if($estoque_minimo_parametro > 0){

	$sql = 'SELECT coeficiente, estado FROM tbl_estoque_minimo_parametro WHERE estoque_minimo_parametro='.$estoque_minimo_parametro.'AND fabrica = '.$login_fabrica;
	$res = pg_exec ($con,$sql);
	$total = pg_numrows($res);

	if($total>0){
		$coeficiente  = pg_result($res,0,'coeficiente');
		$estado  = pg_result($res,0,'estado');
	}
	
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE COEFICIENTE DE ESTOQUE";
include 'cabecalho.php';?>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script language="JavaScript">
$(function(){
	$(".msk_valor").numeric({allow: ',' });
});
</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	background-color: #596D9B;
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}
p{
margin-left: 10%;
}
.sucesso {
  color: white;
  text-align: center;
  font: bold 16px Verdana, Arial, Helvetica, sans-serif;
  background-color: green;
}

.subtitulo{
background-color:#7092BE;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center; 
}
table.bordasimples {border-collapse: collapse;}
	table.bordasimples tr td {
		border:1px solid #D9E2EF;
		font-size: 11px;
	}
	.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:#008000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.informacao{
	font: 14px Arial; color:rgb(89, 109, 155);
	background-color: #C7FBB5;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{padding:0 0 0 120px; }
</style>

<br /><?php

if (strlen($msg_erro) > 0) {?>
	<TABLE  width='700px' align='center' border='0' cellspacing="1" cellpadding="0" class='Titulo'>
		<TR align='center'>
			<TD class='error'><? echo $msg_erro; ?></TD>
		</TR>
	</TABLE><?php
} else if (strlen($msg_success) > 0) {?>
	<TABLE  width='700px' align='center' border='0' cellspacing="1" cellpadding="0" class='Titulo'>
		<TR align='center'>
			<TD class='sucesso'>Gravado com sucesso!</TD>
		</TR>
	</TABLE><?php
}?>

<form name="frm_acrescimo" method="post" action="<?=$PHP_SELF?>">
	
	<input type="hidden" name="estoque_minimo_parametro" value="<?=$estoque_minimo_parametro?>" />
	<table width='700px' align='center' cellspacing="0" cellpadding="3" class="formulario">
		<tr class="titulo_tabela">
			<td colspan='14'>
				Cadastro de Coeficiente de Estoque
			</td>
		</tr>
		<tr class='table_line' align='left'>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr class='table_line' align='left'>
			<td class="espaco" width="300">
				Estado
			</td>
			<td>
				Coeficiente
			</td>
		</tr>
		<tr class='table_line'>
			<td class="espaco">
				<select name="estado" id="estado" class="frm">
					<?php
					foreach($estadosBrasil as $sigla => $est){
						?>
						<option value="<?php echo $sigla;?>" <?php echo ($sigla == $estado) ? 'selected="selected"' : null;?>>
							<?php echo $est;?>
						</option>
						<?
					}
					?>
				</select>
			</td>
			<td>
				<input class='frm msk_valor' type="text" name="coeficiente" value="<?=number_format($coeficiente,1,',','')?>" size="12" maxlength="8" />
			</td>
		</tr>
		<tr align='center'><td>&nbsp;</td></tr>
		<tr align='center'>
			<td colspan='2'>
				<input type='hidden' name='btnacao' value=''>
				<input type="button" value="Gravar" onclick="if(document.frm_acrescimo.btnacao.value == ''){ document.frm_acrescimo.btnacao.value='gravar';document.frm_acrescimo.submit()}else{alert('Aguarde submissão') }" alt="Gravar formulário" border='0' style='cursor:pointer'> &nbsp;
				<input type="button" value="Limpar" onclick="window.location='<? echo $PHP_SELF ?>';return false;" alt="Limpar campos" border='0' style='cursor:pointer'>
			</td>
		</tr>
		<tr align='center'><td>&nbsp;</td></table>
	</table>
		
	<br>
	<br>

<table class="tabela" width='700px' align='center' border='0' cellspacing="1" cellpadding="3">
	<tr align='center' class="titulo_tabela">
		<th colspan='3'>
			Relação dos Coeficiente de Estoque por Estado
		</th>
	</tr>
	
	<tr align='center' class="titulo_tabela">
		<th width="50%">
			Estado
		</th>
		<th>
			Coeficiente de Multiplicação
		</th>
		<th>
			Ação
		</th>
	</tr>
	
	<?php
	$sql = "SELECT  estoque_minimo_parametro    ,
					estado     ,
					coeficiente
			FROM    tbl_estoque_minimo_parametro
			WHERE   fabrica = $login_fabrica
			AND 	data_final IS NULL
			ORDER BY estado ASC";

	$res = pg_exec ($con,$sql);
	$total = pg_numrows($res);

	for ($i=0;$i<$total;$i++) {

		$estoque_minimo_parametro  = pg_result($res, $i, 'estoque_minimo_parametro');
		$estado            = pg_result($res, $i, 'estado');
		$coeficiente         = pg_result($res, $i, 'coeficiente');

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo '<tr align="center" class="table_line" style="background-color: '.$cor.';">';
		echo '	<td><a href="'.$PHP_SELF.'?estoque_minimo_parametro='.$estoque_minimo_parametro.'">'.$estadosBrasil[$estado].'</a></td>';
		echo '	<td><a href="'.$PHP_SELF.'?estoque_minimo_parametro='.$estoque_minimo_parametro.'">'.number_format($coeficiente,1,',','.').'</a></td>';
		echo '	<td align="center"><input type="button" value="Excluir" onclick="window.location.href=\''.$PHP_SELF.'?estoque_minimo_parametro='.$estoque_minimo_parametro.'&del=1\'"></td>';
		echo '</tr>';
	}?>
</table>
</form>

<? include "rodape.php"; ?>
