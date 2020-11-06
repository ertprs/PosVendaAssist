<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if (strlen(trim($_GET["linha"])) > 0) $linha = trim($_GET["linha"]);

if (strlen($linha) > 0) {
	$sql =	"SELECT DISTINCT
					tbl_produto.familia,
					tbl_familia.descricao
			FROM tbl_produto
			JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
			WHERE tbl_produto.linha = $linha
			AND tbl_familia.fabrica = $login_fabrica
			ORDER BY tbl_familia.descricao;";
	$res_familia = @pg_exec($con,$sql);

	echo "<script language='JavaScript'>";
	if (pg_numrows($res_familia) > 0) {
		for ($i = 0 ; $i < pg_numrows($res_familia) ; $i++) {
			$aux_familia           = trim(pg_result($res_familia,$i,familia));
			$aux_familia_descricao = substr(trim(pg_result($res_familia,$i,descricao)), 0, 23);
			print "window.parent.AdicionaFamilia ('$aux_familia_descricao', '$aux_familia', 'familia');\n";
		}
	}else{
		print "window.parent.AdicionaFamilia ('Nenhuma Família Cadastrada', '0', 'familia');\n";
	}
	echo "</script>";
}
?>
