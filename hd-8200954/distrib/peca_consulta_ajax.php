<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca  = $_GET["busca"];
	$busca_posto = $_GET["busca_posto"];

	if (strlen($q)>2){

		if(strlen($busca_posto)>0){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto LIKE '%$q%' ";
			}else{
				$sql .= " AND tbl_posto.nome          ILIKE '%$q%' ";
			}
			$sql .= " LIMIT 15 ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$codigo_posto|$nome|$codigo_posto\n";
				}
			}

		}else{
			$sql = "SELECT  tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.peca,
					tbl_fabrica.nome
				FROM   tbl_peca
				JOIN   tbl_posto_estoque USING(peca)
				JOIN   tbl_fabrica       USING(fabrica)
				WHERE  tbl_posto_estoque.posto = $login_posto 
				";
			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_peca.referencia ILIKE '%$q%' ";
			}else{
				$sql .= " AND tbl_peca.descricao  ILIKE '%$q%' ";
			}
			$sql .= " LIMIT 15";
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$referencia  = trim(pg_result($res,$i,referencia));
					$descricao   = trim(pg_result($res,$i,descricao));
					$peca        = trim(pg_result($res,$i,peca));
					$nome        = trim(pg_result($res,$i,nome));
					echo "$nome|$referencia|$descricao |$peca\n";
				}
			}
		}
	}
	exit;
}
?>
