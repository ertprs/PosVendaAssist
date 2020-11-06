<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$sql = "SELECT 	extrato, 
				data_geracao,  
				avulso,
				mao_de_obra, 
				pecas, 
				total 
		FROM tbl_extrato 
		WHERE fabrica=$login_fabrica 
		ORDER BY data_geracao desc 
		LIMIT 1500;";
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){
echo "<table border='1' width='100%' style='font-size:11px';>";
	echo "<tr>";
		echo "<td colspan='6' align='center'>Valores do Extrato</td>";
		echo "<td align='center'>Valor peças</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td>Extrato</td>";
		echo "<td>Data Geração</td>";
		echo "<td>Total<BR><font size=1>(tbl_extrato.total)</font></td>";
		echo "<td>Avulso<BR><font size=1>(tbl_extrato.avulso)</font></td>";
		echo "<td>Mão de Obra<BR><font size=1>(tbl_extrato.mao_de_obra)</font></td>";
		echo "<td>Peças<BR><font size=1>(tbl_extrato.pecas)</font></td>";
		echo "<td>Peças Hoje<BR><font size=1>(tbl_os_item.qtde * tbl_os_item.custo_peca)</font></td>";
	echo "</tr>";
	for($i=0; pg_numrows($res)>$i; $i++){
	$extrato      = pg_result($res,$i,extrato);
	$data_geracao = pg_result($res,$i,data_geracao);
	$mao_de_obra  = pg_result($res,$i,mao_de_obra);
	$pecas        = pg_result($res,$i,pecas);
	$total        = pg_result($res,$i,total);
	$avulso       = pg_result($res,$i,avulso);
			
		$xsql = "SELECT SUM((SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.servico_realizado = 90)) AS pecas  
				FROM    tbl_os_extra
				JOIN    tbl_os USING (os)
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_os.fabrica = $login_fabrica";
//				AND     ( tbl_os.satisfacao IS NULL OR tbl_os.satisfacao IS FALSE )
		$xres = pg_exec ($con,$xsql);
		$calculo_peca      = pg_result($xres,0,pecas);	
		if(strlen($calculo_peca)==0)$calculo_peca="0";
	if($calculo_peca<>$pecas){$cor="#d9a6a6"; $cont++;}else{$cor="#ffffff";}
	echo "<tr>";
		echo "<td bgcolor='$cor'>$extrato</td>";
		echo "<td bgcolor='$cor'>$data_geracao</td>";
		echo "<td bgcolor='$cor'>$total</td>";
		echo "<td bgcolor='$cor'>$avulso</td>";
		echo "<td bgcolor='$cor'>$mao_de_obra</td>";
		echo "<td bgcolor='$cor'>$pecas</td>";
		echo "<td bgcolor='$cor'>$calculo_peca</td>";
	echo "</tr>";
	}
echo "</table>";
echo "$cont valores desiguais";
}



?>
