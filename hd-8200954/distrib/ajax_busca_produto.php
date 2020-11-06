<?
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';


$digito = $_GET["typing"];

$sql= "SELECT posto, 
			nome,
			cidade 	
		FROM tbl_posto where nome ilike '%$digito%'
		ORDER BY nome ;";
//echo "sql: $sql";
$res = pg_exec ($con,$sql);

if(pg_numrows($res)>0) {
	if(pg_numrows($res)>20){
		echo "<div style=' filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome';$('fornID').value = '$posto'\">";
		echo "<table width='380' >";
		echo "<tr>";
		echo "<td nowrap style='font-size:10px'>".pg_numrows($res)." reg. encontrados!Especifique mais..";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
	}else{
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$posto	= trim(pg_result($res,$i,posto));	
			$nome	= trim(pg_result($res,$i,nome));	

			if(strlen($nome) > 31){
				$nome_red= substr($nome, 0,30) . "...";
			}else{
				$nome_red= $nome;
			}

			$cidade	= trim(pg_result($res,$i,cidade));	
			if(strlen($cidade)>22)
				$cidade	= substr($cidade, 0,20) . "...";
			else
				$cidade	= $cidade;

			echo "<div style='border-width: 1px; border-style: solid; border-color: #cccccc; filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome';$('fornID').value = '$posto'\">";
			//echo "<span width='100' class='informal' style='font-size:10px'></span>";
			echo "<table width='375' >";
			echo "<tr>";
			echo "<td nowrap width='245' style='font-size:9px'>".strtoupper($nome_red);
			echo "</td>";
			echo "<td nowrap width='140' align='right' style='font-size:9px'>".strtoupper($cidade);
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</div>";
		}
	}
}else{
	echo "<div style=' filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome';$('fornID').value = '$posto'\">";
	echo "<table width='380' >";
	echo "<tr>";
	echo "<td nowrap style='font-size:10px'>Nada encontrado com: <b>$digito</b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
}

