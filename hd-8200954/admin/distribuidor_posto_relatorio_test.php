<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";

$visual_black = "manutencao-admin";

$title       = "RELAÇÃO DE DISTRIBUIDORES E SEUS POSTOS AUTORIZADOS";
$cabecalho   = "Relação de Distribuidores e seus Postos Autorizados";
$layout_menu = "cadastro";

include 'cabecalho.php';?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}


.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
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
	font: bold 16px "Arial";
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
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='msg_error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} 
//echo $msg_debug;
?> 
<p>


<center>

<form name='frm_distribuidor_posto' method='post' action='<? echo $PHP_SELF ?>'>
<table align="center" class='formulario' width='700'>
	<tr class='titulo_tabela'>
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td style="padding-left:200px;">
			Selecione o Distribuidor: <br />
			<?
			$sql = "SELECT	tbl_posto.posto                ,
							tbl_posto.nome                 ,
							tbl_posto.nome_fantasia        ,
							tbl_posto_fabrica.codigo_posto
					FROM	tbl_posto
					JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					JOIN	tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
					WHERE	tbl_posto_fabrica.fabrica   = $login_fabrica
					AND		tbl_tipo_posto.distribuidor is true	
					ORDER BY tbl_posto.nome_fantasia, tbl_posto.nome";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res) > 0){
				echo "<select name='distribuidor' class='frm'>\n";
				echo "<option selected></option>\n";
				for($i = 0; $i < pg_numrows($res); $i++){
					$fantasia = pg_result ($res,$i,nome_fantasia);
					if (strlen ($fantasia) == 0) $fantasia = pg_result ($res,$i,nome);
					echo "<option value='".pg_result($res,$i,posto)."'>".pg_result($res,$i,codigo_posto)." - ".$fantasia."</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</td>
	</tr>

	<tr>
		<td align='center'>OU</td>
	</tr>

	<tr>
		<td style="padding-left:200px;">
			Selecione o Estado: <br />
			<?
			$sql = "SELECT * FROM tbl_estado WHERE pais='BR' AND pais <> 'EX' ORDER BY estado";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res) > 0){
				echo "<select name='estado' class='frm'>\n";
				echo "<option selected></option>\n";
				for($i = 0; $i < pg_numrows($res); $i++){
					echo "<option value='".pg_result($res,$i,estado)."'>".pg_result($res,$i,estado)." - ". pg_result ($res,$i,nome)."</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td align='center'>
			<input type='submit' name='btn_acao' value='Listar'>
		</td>
	</tr>
</table>

</form>
</center>
<br>

<?
$distribuidor = $_POST['distribuidor'];
$estado       = $_POST['estado'];

if (strlen($distribuidor) > 0){

	echo "<center>";
	echo "<b>Legenda:</b> D=Atendido pelo Distribuidor &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; F=Atendido pela Fábrica";
	echo "</center>";

	echo "<p>";

	echo "<table width='700' align='center' cellspacing='1' class='tabela'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td colspan='100%' style='font-size:14px;'>Listando os postos do distribuidor</td>";
	echo "</tr>";
	echo "<tr class='subtitulo'>";
	echo "<td colspan='100%' style='font-size:12px;'>";
	$sql = "SELECT nome, nome_fantasia, cidade, estado, tbl_posto_fabrica.codigo_posto FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto.posto = $distribuidor";
	$res = pg_exec ($con,$sql);
	echo pg_result ($res,0,codigo_posto) ;
	echo " - " ;
	echo pg_result ($res,0,nome) ;
	echo "<br />";
	echo pg_result ($res,0,cidade) ;
	echo " - " ;
	echo pg_result ($res,0,estado) ;
	$estado_distribuidor = pg_result ($res,0,estado) ;
	echo "</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td align='center'>Código</td>";
	echo "<td align='center'>Nome</td>";
	echo "<td align='center'>Cidade</td>";
	echo "<td align='center'>Estado</td>";

	$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<td nowrap>";
		echo pg_result ($res,$i,nome);
		echo "</td>";
	}
	echo "</tr>";


	$sql = "SELECT DISTINCT tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.cidade, tbl_posto.estado, tbl_posto_fabrica.credenciamento 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto
				WHERE (tbl_posto_linha.distribuidor = $distribuidor OR ((tbl_posto_linha.distribuidor IS NULL OR tbl_posto_linha.distribuidor <> $distribuidor) AND tbl_posto.estado = '$estado_distribuidor'))
				ORDER BY tbl_posto.estado, tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$posto = pg_result ($res,$i,posto) ;

		$cor = "#F7F5F0";
		if ($i % 2 == 0) $cor = '#F1F4FA';

		if (pg_result ($res,$i,credenciamento) <> "CREDENCIADO") {
			$cor = "#FF6666";
		}

		echo "<tr bgcolor='$cor'>";
		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,cidade);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,estado);
		echo "</td>";

		$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
		$resX = pg_exec ($con,$sql);

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			$linha = pg_result ($resX,$x,linha) ;

			$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
			$resZ = pg_exec ($con,$sql);
			if (pg_numrows ($resZ) == 0) {
				echo "<td></td>";
			}else{
				$x_distribuidor = pg_result ($resZ,0,distribuidor) ;
				if ($distribuidor == $x_distribuidor) {
					echo "<td>D</td>";
				}else{
					echo "<td><b><font color='#CC0033' size='+1'>F</font></b></td>";
				}
			}
		}



		echo "</tr>";
	}



	echo "</table>";

}







if (strlen($estado) > 0){

	
	echo "<center>";
	echo "<b>Legenda:</b> D=Atendido pelo Distribuidor &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; F=Atendido pela Fábrica";
	echo "</center>";

	echo "<p>";

	echo "<table width='700' align='center' class='tabela'>";
	echo "<tr class='titulo_tabela'>";
	echo "<td colspan='100%' style='font-size:14px;'>Listando os postos do Estado $estado</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td align='center'>Código</td>";
	echo "<td align='center'>Nome</td>";
	echo "<td align='center'>Cidade</td>";
	echo "<td align='center'>Estado</td>";

	$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<td nowrap>";
		echo pg_result ($res,$i,nome);
		echo "</td>";
	}
	echo "</tr>";


	$sql = "SELECT DISTINCT tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.cidade, tbl_posto.estado 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto
				WHERE tbl_posto.estado = '$estado' 
				ORDER BY tbl_posto.estado, tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$posto = pg_result ($res,$i,posto) ;

		$cor = "#F7F5F0";
		if ($i % 2 == 0) $cor = '#F1F4FA';

		echo "<tr bgcolor='$cor'>";
		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,cidade);
		echo "</td>";

		echo "<td nowrap align='left'>";
		echo pg_result ($res,$i,estado);
		echo "</td>";

		$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
		$resX = pg_exec ($con,$sql);
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			$linha = pg_result ($resX,$x,linha) ;

			$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha ";
			$resZ = pg_exec ($con,$sql);
			if (pg_numrows ($resZ) == 0) {
				echo "<td></td>";
			}else{
				$x_distribuidor = pg_result ($resZ,0,distribuidor) ;
				if (strlen ($x_distribuidor) > 0) {
					echo "<td>D</td>";
				}else{
					echo "<td><b><font color='#CC0033' size='2'>F</font></b></td>";
				}
			}
		}



		echo "</tr>";
	}



	echo "</table>";

}

?>

<p>

<? include "rodape.php"; ?>