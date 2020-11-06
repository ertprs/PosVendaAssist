	<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "auditoria";
$titulo = "Postos que já lançaram OS no sistema";
$title = "Postos que já lançaram OS no sistema";

include 'cabecalho.php';
?>


<?
	$sql ="SELECT tbl_posto.nome,tbl_posto_fabrica.codigo_posto, tbl_posto.cidade, tbl_posto.estado, count(tbl_os.os) as qtde_os
	from tbl_os
	join tbl_posto on tbl_os.posto = tbl_posto.posto
	join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
	where tbl_os.fabrica = $login_fabrica and tbl_os.posto<>6359
	GROUP BY tbl_posto.nome,tbl_posto_fabrica.codigo_posto, tbl_posto.cidade, tbl_posto.estado
	ORDER BY tbl_posto_fabrica.codigo_posto";
	
	$res = pg_exec ($con,$sql);
//echo $sql; exit;
	if (pg_numrows($res) > 0) {
	$total = pg_numrows($res);
	echo "<BR><BR><center>Postos que já lançaram OS no sistema</center><BR>
			Total de $total postos";
		echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td><B>Código Posto</B>";
		echo "</td>";
		echo "<td><B>Nome</B>";
		echo "</td>";
		echo "<td><B>Cidade</B>";
		echo "</td>";
		echo "<td><B>Estado</B>";
		echo "</td>";
		echo "<td><B>Qtde</B>";
		echo "</td>";
		echo "</tr>";
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$nome           = trim(pg_result($res,$i,nome));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$qtde_os          = trim(pg_result($res,$i,qtde_os));

		$cor = "#FFFFFF"; 
		if ($i % 2 == 0) $cor = '#eeeeff';
		echo "<tr bgcolor='$cor'>";
		echo "<td>$codigo_posto";
		echo "</td>";
		echo "<td align='left'>$nome";
		echo "</td>";
		echo "<td align='left'>$cidade";
		echo "</td>";
		echo "<td>$estado";
		echo "</td>";
		echo "<td>$qtde_os";
		echo "</td>";
		echo "</tr>";
		}
		echo "</table><BR><BR>";

	}
	

include "rodape.php";


?>
