<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "auditoria";
$titulo = "Postos que NÃO lançaram OS pelo SAC";
$title = "Postos que NÃO lançaram OS pelo SAC";

include 'cabecalho.php';
?>


<?
	$sql ="SELECT
		tbl_posto_fabrica.codigo_posto            ,
		tbl_posto.nome                            ,
		tbl_posto.cnpj                            ,
		tbl_posto.cidade                          ,
		tbl_posto.estado                                   
		FROM tbl_posto
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto 
		AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_posto_fabrica.fabrica = $login_fabrica
		AND tbl_posto.posto NOT IN (
			SELECT tbl_posto.posto
			FROM tbl_os
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
			WHERE tbl_os.fabrica = $login_fabrica
                        AND tbl_os.admin notnull
			GROUP BY tbl_posto.posto
		)
		GROUP BY
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_posto.cnpj                            ,
		tbl_posto.cidade                          ,
		tbl_posto.estado                           
		ORDER BY
		tbl_posto.nome";
	
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
	$total = pg_numrows($res);
	echo "<BR><BR><center>Listagem de Postos que <b>não</b> abriram O.S no <b>SAC</b></center><BR>
			Total de $total postos";
		echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' bgcolor='#9FB5CC' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td><B>CNPJ</B>";
		echo "</td>";
		echo "<td><B>Código Posto</B>";
		echo "</td>";
		echo "<td><B>Nome</B>";
		echo "</td>";
		echo "<td><B>Cidade</B>";
		echo "</td>";
		echo "<td><B>Estado</B>";
		echo "</td>";
		echo "</tr>";
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$cnpj           = trim(pg_result($res,$i,cnpj));
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$nome           = trim(pg_result($res,$i,nome));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			/*$posto          = trim(pg_result($res,$i,posto));*/

		$cor = "#FFFFFF"; 
		if ($i % 2 == 0) $cor = '#D2E6FF';
		echo "<tr bgcolor='$cor'>";
		echo "<td>$cnpj";
		echo "</td>";
		echo "<td>$codigo_posto";
		echo "</td>";
		echo "<td>$nome";
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
