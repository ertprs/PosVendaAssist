<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$acao = $_GET["acao"];

switch($acao) {
	case "arquivo":
		$sql = "SELECT arquivo, descricao FROM tbl_arquivo WHERE status='ativo' AND descricao ILIKE '%" . $_GET["q"] . "%' ORDER BY descricao LIMIT 10";
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$dados = pg_fetch_row($res);
			$dados = implode("|", $dados);
			echo $dados . "\n";
		}
	break;

	default:
		echo "falha";
}

?>