<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

#$admin_privilegios="info_tecnica";
$admin_privilegios="auditoria";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "auditoria";
$titulo = "Consulta Comunicados de Supervisores";
$title = "Consulta Comunicados de Supervisores";

include 'cabecalho.php';


$msg_erro = "";
$comunicado_supervisor = 0;
if (trim($_POST['comunicado_supervisor']) > 0) $comunicado_supervisor = trim($_POST['comunicado_supervisor']);
if (trim($_GET['comunicado_supervisor']) > 0)  $comunicado_supervisor = trim($_GET['comunicado_supervisor']);

if ($comunicado_supervisor > 0){
	$sql = "SELECT	comunicado_supervisor      ,
					titulo                     ,
					mensagem                   ,
					arquivo                    ,
					TO_CHAR(data_envio,'DD/MM/YYYY') AS data_envio
			FROM	tbl_comunicado_supervisor
			WHERE   fabrica=$login_fabrica
			AND comunicado_supervisor = $comunicado_supervisor
			ORDER BY comunicado_supervisor DESC";
	$res = pg_exec ($con,$sql);

	if (pg_result($res,0,comunicado_supervisor)>0){
		$titulo   = trim(pg_result($res,0,titulo));
		$mensagem = trim(pg_result($res,0,mensagem));
		$data     = trim(pg_result($res,0,data_envio));
		$arquivo =  trim(pg_result($res,0,arquivo));
		echo "<br><table width='700' align='center' border='0'>";
		echo "<tr>";
		echo "<td align='center' colspan ='3'class='menu_top'>$titulo</td>";
		echo"<tr>";
		echo"	<td class='table_line'>Mensagem</td>";
		echo"	<td class='table_line2' colspan='2'>$mensagem</textarea></td>";
		echo "</tr>";
		echo "<tr>";
		echo "	<td class='table_line'>Data</td>";
		echo "	<td class='table_line2' colspan='2'>$data</td>";
		echo "</tr>";

//SE EXISTE ARQUIVO NO COMUNICADO DE SUPERVISOR ELE SEPARAR AS IMAGENS DOS DOCS

		if($arquivo > 0){
			echo "<tr>";
			echo "	<td class='table_line'>Arquivo</td>";
			$ext = explode(".",$arquivo);
			$ext = $ext[1];
			if ($ext == "jpg" || $ext == "gif" || $ext == "bmp" || $ext == "png"){
				echo "<td class='table_line2' colspan='2'><img src='../comunicados_supervisor/$arquivo'</td>";
			}
			else{
				echo "<td class='table_line2' colspan='2'><a href='../comunicados_supervisor/$arquivo' target='_blank'>Abrir arquivo</a></td>";
			}
			echo "</tr>";
		}
	}
}


?>



<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #f5f5f5
}
.table_line2 {
	text-align: left;
	background-color: #fcfcfc
}
.ERRO{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ff0000;
}
</style>

<body>




<?


#--------------------------------------------------------
#  Mostra todos os informativos cadastrados
#--------------------------------------------------------

if($comunicado_supervisor==0){

	$sql = "SELECT	comunicado_supervisor      ,
					titulo                     ,
					mensagem                   ,
					arquivo                    ,
					TO_CHAR(data_envio,'DD/MM/YYYY') AS data_envio
			FROM	tbl_comunicado_supervisor
			WHERE   fabrica = $login_fabrica
			ORDER BY comunicado_supervisor DESC";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0'>";
		echo "<tr>";
		echo "<td align='center' class='menu_top'>Titulo</td>";
		echo "<td align='center' class='menu_top'>Mensagem</td>";
		echo "<td align='center' class='menu_top'>Data</td>";
		echo "<td align='center' class='menu_top' width='85'>&nbsp;</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$arquivo = trim(pg_result ($res,$i,arquivo));

			echo "<tr>";
			echo "<td align='left' class='table_line'>";
			echo "<a href='$PHP_SELF?comunicado_supervisor=".pg_result ($res,$i,comunicado_supervisor)."'>";
			echo pg_result ($res,$i,titulo);
			echo "</a>";
			echo "</td>";

			echo "<td align='left' class='table_line'width='300'>";
			echo pg_result ($res,$i,mensagem);
			echo "</td>";

			echo "<td align='left' class='table_line'width='50'>";
			echo pg_result ($res,$i,data_envio);
			echo "</td>";

			echo "<td class='table_line' align='center'><center>";
			echo "<a href='$PHP_SELF?comunicado_supervisor=".pg_result ($res,$i,comunicado_supervisor)."'>";
			if($arquivo) echo "Arquivo";else echo"Texto";
			echo "</center></a>";
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}else{
	 echo '<span class="ERRO">NENHUM DOCUMENTO CADASTRADO</span>';
	}
}

include "rodape.php";
?>
