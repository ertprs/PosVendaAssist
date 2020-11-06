<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

$tipo = $_GET["tipo"];

if ($tipo == "constatado") {
	$constatado_familia = $_GET["constatado_familia"];

	$sql =	"SELECT  tbl_defeito_constatado.defeito_constatado ,
					 tbl_defeito_constatado.codigo             ,
					 tbl_defeito_constatado.descricao          
			FROM     tbl_defeito_constatado
			JOIN     tbl_familia_defeito_constatado USING (defeito_constatado)
			JOIN     tbl_familia ON tbl_familia_defeito_constatado.familia = tbl_familia.familia
			WHERE    tbl_defeito_constatado.fabrica = $login_fabrica
			AND      tbl_familia.familia = $constatado_familia
			ORDER BY tbl_defeito_constatado.descricao;";
	$res_constatado2 = pg_exec($con,$sql);

	echo "<script language='JavaScript'>";
	if (@pg_numrows($res_constatado2) > 0) {
		for ($i = 0 ; $i < @pg_numrows($res_constatado2) ; $i++) {
			$defeito_constatado = pg_result($res_constatado2,$i,defeito_constatado);
			$descricao          = substr(pg_result($res_constatado2,$i,descricao), 0, 23);
			print "window.parent.AdicionaDefeito ('$descricao', '$defeito_constatado', 'defeito_constatado');\n";
		}
	}else{
		print "window.parent.AdicionaDefeito ('Nenhum Defeito Constatado Cadastrado', '0', 'defeito_constatado');\n";
	}
	echo "</script>";
}

if ($tipo == "reclamado") {
	$reclamado_familia = $_GET["reclamado_familia"];

	$sql =	"SELECT  tbl_defeito_reclamado.defeito_reclamado ,
					 tbl_defeito_reclamado.descricao         
			FROM     tbl_defeito_reclamado
			JOIN     tbl_familia USING (familia)
			WHERE    tbl_familia.fabrica = $login_fabrica
			AND      tbl_familia.familia = $reclamado_familia
			ORDER BY tbl_defeito_reclamado.descricao;";
	$res_reclamado2 = pg_exec($con,$sql);

	echo "<script language='JavaScript'>";
	if (@pg_numrows($res_reclamado2) > 0) {
		for ($i = 0 ; $i < @pg_numrows($res_reclamado2) ; $i++) {
			$defeito_reclamado = pg_result($res_reclamado2,$i,defeito_reclamado);
			$descricao         = substr(pg_result($res_reclamado2,$i,descricao), 0, 23);
			print "window.parent.AdicionaDefeito ('$descricao', '$defeito_reclamado', 'defeito_reclamado');\n";
		}
	}else{
		print "window.parent.AdicionaDefeito ('Nenhum Defeito Reclamado Cadastrado', '0', 'defeito_reclamado');\n";
	}
	echo "</script>";
}
?>