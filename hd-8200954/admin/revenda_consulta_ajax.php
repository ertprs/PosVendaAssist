<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$busca_revenda = $_GET["busca_revenda"];	

	if (strlen($q)>2){

		if(strlen($busca_revenda)>0){
			$sql = "SELECT  tbl_revenda.revenda,
					tbl_revenda.nome,
					tbl_revenda.cnpj
				FROM tbl_revenda
				JOIN   tbl_revenda_fabrica USING (revenda)
				WHERE  tbl_revenda_fabrica.fabrica = $login_fabrica
				";
			if ($busca_revenda == "codigo") $sql .= " AND tbl_revenda.cnpj = '$q' ";
			else                            $sql .= " AND UPPER(tbl_revenda.nome) LIKE UPPER('%$q%') ";
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$revenda = trim(pg_result($res,$i,revenda));
					echo "$cnpj|$nome|$revenda\n";
				}
			}

		}else{

			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_busca == "codigo") $sql .= " AND tbl_posto_fabrica.codigo_posto LIKE '%$q%' ";
			else                         $sql .= " AND UPPER(tbl_posto.nome)          LIKE UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$codigo_posto|$nome|$codigo_posto\n";
				}
			}
		}
	}
	exit;
}
?>