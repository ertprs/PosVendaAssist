	<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

?>


<?
//listagem de os pelo posto
$sql="SELECT tbl_os.sua_os,
		tbl_os.data_digitacao,
		tbl_os.consumidor_nome,
		tbl_os.consumidor_fone,
		tbl_os.consumidor_cep,
		tbl_os.consumidor_cidade,
		tbl_os.consumidor_estado
		from tbl_os
		where tbl_os.posto='5388'
		ORDER BY tbl_os.data_digitacao";

	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
	$total = pg_numrows($res);
	echo "<BR><BR><center>Listagem de Postos que ainda não lançaram O.S no Sistema</center><BR>
			Total de $total postos";
		echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' bgcolor='#9FB5CC' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td><B>data digitacao</B>";
		echo "</td>";
		echo "<td><B>os</B>";
		echo "</td>";
		echo "<td><B>nome</B>";
		echo "</td>";
		echo "<td><B>fone</B>";
		echo "</td>";
		echo "<td><B>cep</B>";
		echo "</td>";
		echo "<td><B>cidade</B>";
		echo "</td>";
		echo "<td><B>Estado</B>";
		echo "</td>";
		echo "</tr>";
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$data_digitacao          = trim(pg_result($res,$i,data_digitacao));
			$sua_os                  = trim(pg_result($res,$i,sua_os));
			$cidade         = trim(pg_result($res,$i,consumidor_cidade));
			$estado         = trim(pg_result($res,$i,consumidor_estado));
			$nome           = trim(pg_result($res,$i,consumidor_nome));
			$fone                 = trim(pg_result($res,$i,consumidor_fone));
			$cep   = trim(pg_result($res,$i,consumidor_cep));
			/*$posto          = trim(pg_result($res,$i,posto));*/

		$cor = "#FFFFFF"; 
		if ($i % 2 == 0) $cor = '#D2E6FF';
		echo "<tr bgcolor='$cor'>";
		echo "<td>$data_digitacao";
		echo "</td>";
		echo "<td>$sua_os";
		echo "</td>";
		echo "<td>$nome";
		echo "</td>";
		echo "<td>$fone";
		echo "</td>";
		echo "<td>$cep";
		echo "</td>";
		echo "<td>$cidade";
		echo "</td>";
		echo "<td>$estado";
		echo "</td>";
		echo "</tr>";
		}
		echo "</table><BR><BR>";
	}else{
		echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td>Todos os Postos estão utilizando o Sistema.";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	

include "rodape.php";


?>
