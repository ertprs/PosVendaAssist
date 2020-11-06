<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";


$distrib_lote = $_POST['distrib_lote'];


echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);

echo "<select name='distrib_lote' size='1'>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>";
}
echo "</select>";

echo "<input type='submit' name='btn_acao' value='Imprimir Lote'>";

echo "</form>";



if (strlen ($distrib_lote) > 0) {

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome                 ,
					tbl_banco.nome AS banco        ,
					tbl_posto_fabrica.agencia      ,
					tbl_posto_fabrica.conta        ,
					lote.mobra_os                  ,
					tbl_distrib_lote_posto.mao_de_obra_conferida
			FROM    tbl_distrib_lote_posto
			JOIN    tbl_distrib_lote  USING (distrib_lote)
			JOIN    tbl_posto         ON tbl_distrib_lote_posto.posto = tbl_posto.posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
			JOIN   (SELECT tbl_os.posto, SUM (tbl_produto.mao_de_obra) AS mobra_os
					FROM tbl_os
					JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote
					GROUP BY tbl_os.posto
			) lote ON tbl_posto.posto = lote.posto
			LEFT JOIN tbl_banco ON tbl_posto_fabrica.banco = tbl_banco.codigo
			WHERE tbl_distrib_lote_posto.distrib_lote = $distrib_lote
			ORDER BY tbl_posto_fabrica.banco , tbl_posto.nome";

	$res = pg_exec ($con,$sql);

	#echo $sql;

	$sql = "SELECT LPAD (lote::text,6,'0') AS lote , TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento FROM tbl_distrib_lote WHERE distrib_lote = $distrib_lote";
	$resX = pg_exec ($con,$sql);

	echo "<center><h1>Lote " . pg_result ($resX,0,lote) . " de " . pg_result ($resX,0,fechamento) . "</h1></center>";

	echo "<table border='1' cellspacing='0' cellpadding='2'>";
	echo "<tr align='center' bgcolor='#6666FF'>";
	echo "<td nowrap><b>Código</b></td>";
	echo "<td nowrap><b>Nome</b></td>";
	echo "<td nowrap><b>Banco</b></td>";
	echo "<td nowrap><b>Agência</b></td>";
	echo "<td nowrap><b>Conta</b></td>";
	echo "<td nowrap><b>Valor</b></td>";
	echo "</tr>";


	for ($i = 0 ; $i < pg_numrows ($res); $i++) {
		echo "<tr style='font-size:10px'>";

		echo "<td nowrap>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";

		echo "<td nowrap>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td nowrap>";
		echo pg_result ($res,$i,banco);
		echo "</td>";

		echo "<td nowrap>";
		echo pg_result ($res,$i,agencia);
		echo "</td>";

		echo "<td nowrap>";
		echo pg_result ($res,$i,conta);
		echo "</td>";

		$valor_conf = pg_result ($res,$i,mao_de_obra_conferida);
		$valor_os   = pg_result ($res,$i,mobra_os);
		
		$valor = $valor_conf ;
		if ($valor_os < $valor_conf) $valor = $valor_os ;
		if ($valor == 0) $valor = $valor_os ;
		if (strlen ($valor) == 0 ) $valor = $valor_os ;

		echo "<td nowrap align='right'>";
		echo number_format ($valor,2,",",".");
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";
}

?>

<? #include "rodape.php"; ?>

</body>
</html>
