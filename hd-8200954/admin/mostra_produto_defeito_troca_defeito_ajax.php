<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$linha         = $_GET['linha'];
$data_inicial  = $_GET['data_inicial'];
$data_final    = $_GET['data_final'];
$codigo_linha  = $_GET['codigo_linha'];
$produto       = $_GET['produto'];

$sql= "
	SELECT
		tbl_defeito_constatado.descricao   AS descricao,
		tbl_os.defeito_constatado          AS defeito_constatado,
		COUNT(tbl_os.os)                   AS qtde
	FROM tbl_os
		JOIN tbl_defeito_constatado    ON tbl_defeito_constatado.defeito_constatado   = tbl_os.defeito_constatado
		JOIN tbl_produto               ON tbl_produto.produto                         = tbl_os.produto
	WHERE tbl_os.fabrica       = $login_fabrica
		AND tbl_produto.linha  = $codigo_linha
		AND tbl_os.produto     = $produto
		AND tbl_os.data_abertura between '$data_inicial 00:00:00' and '$data_final 23:59:59' 
		AND tbl_os.defeito_constatado IS NOT NULL
	GROUP BY tbl_defeito_constatado.descricao, tbl_os.defeito_constatado
	ORDER BY descricao, qtde desc";
$res = pg_exec ($con,$sql);
//echo nl2br($sql);
//die;
// Monta listagem de MÊS

if (pg_num_rows($res)>0) {
	echo "$linha|";
	echo "<table border=1 cellpadding=1 cellspacing=0 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=699>";
		echo "<tr class=Titulo>";
			echo "<td align=center>Código do Defeito</td>";
			echo "<td align=center>Descrição do Defeito</td>";
			echo "<td align=center>Qtde</td>";
		echo "</tr>";
		for ($i=0; $i < pg_numrows($res); $i++){
			$codigo_defeito   = trim(pg_result($res,$i,defeito_constatado));
			$descricao        = trim(pg_result($res,$i,descricao));
			$qtde             = trim(pg_result($res,$i,qtde));
			if($cor=="#F1F4FA")
				$cor = "#F7F5F0";
			else
				$cor = "#F1F4FA";
		echo "<tr>";
			echo "<td bgcolor=$cor align=center nowrap>$codigo_defeito</td>";
			echo "<td bgcolor=$cor align=center nowrap>$descricao</td>";
			echo "<td bgcolor=$cor align=center nowrap>$qtde</td>";
		echo "</tr>";
		}
	echo "</table>";
}
?>