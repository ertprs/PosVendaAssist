<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$codigo_posto = $_POST['codigo_posto'];
$senha        = $_POST['senha'];

#$codigo_posto = $_GET['codigo_posto'];
#$senha        = $_GET['senha'];


$sql = "SELECT  tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_peca.unidade, tbl_tabela_item.preco , to_char (tbl_tabela_item.preco * (1 + (tbl_peca.ipi::float/100)),'99999990.00') AS total
	FROM tbl_tabela_item 
	JOIN tbl_tabela ON tbl_tabela_item.tabela = tbl_tabela.tabela
	JOIN tbl_peca   ON tbl_tabela_item.peca = tbl_peca.peca
	JOIN tbl_posto_linha ON tbl_posto_linha.tabela = tbl_tabela.tabela
	JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
	WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto' AND tbl_posto_fabrica.senha = '$senha'
	ORDER BY tbl_peca.referencia";
$res = pg_exec($con,$sql);

for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	echo pg_result ($res,$i,referencia);
	echo "\t";
	echo pg_result ($res,$i,descricao);
	echo "\t";
	echo pg_result ($res,$i,ipi);
	echo "\t";
	echo pg_result ($res,$i,unidade);
	echo "\t";
	echo number_format (pg_result ($res,$i,preco),2,",","");
	echo "\t";
	echo number_format (pg_result ($res,$i,total),2,",","");
	echo "\n";
	flush();
}


?>