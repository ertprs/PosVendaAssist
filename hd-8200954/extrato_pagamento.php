<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Dados do Banco para pagamento de Postos";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>


<?

$sql = "SELECT  tbl_posto.nome                                                      ,
		tbl_posto_fabrica.codigo_posto                                      ,
		TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY')    AS data_geracao   ,
		tbl_extrato.extrato                                                 ,
		tbl_extrato.mao_de_obra                                             ,
		tbl_extrato.pecas                                                   ,
		tbl_extrato.avulso                                                  ,
		tbl_extrato.total                                                   ,
		(
		SELECT count(tbl_os.os) 
		FROM tbl_os
		JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		WHERE tbl_os_extra.extrato = tbl_extrato.extrato
		)                                                 AS total_os
	FROM tbl_extrato	
	JOIN tbl_posto         ON tbl_posto.posto         = tbl_extrato.posto
	JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
	WHERE tbl_extrato.fabrica = $login_fabrica
	ORDER BY tbl_posto.nome;";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='750'>";
	echo "<tr class='Titulo'>";
	echo "<td >CÓDIGO POSTO</td>";
	echo "<td >NOME POSTO</td>";
	echo "<td >EXTRATO</td>";
	echo "<td >GERAÇÃO</td>";
	echo "<td >M.O</td>";
	echo "<td >PEÇAS</td>";
	echo "<td >AVULSO</td>";
	echo "<td >TOTAL</td>";
	echo "<td >TOTAL OS</td>";
	echo "</tr>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$nome                    = trim(pg_result($res,$i,nome))          ;
		$codigo_posto            = trim(pg_result($res,$i,codigo_posto))  ;
		$extrato                 = trim(pg_result($res,$i,extrato))       ;
		$data_geracao            = trim(pg_result($res,$i,data_geracao))  ;
		$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra))   ;
		$pecas                   = trim(pg_result($res,$i,pecas))         ;
		$avulso                  = trim(pg_result($res,$i,avulso))        ;
		$total                   = trim(pg_result($res,$i,total))         ;
		$total_os               = trim(pg_result($res,$i,total_os))      ;

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		$pecas       = number_format ($pecas,2,",",".")      ;
		$mao_de_obra = number_format ($mao_de_obra,2,",",".");
		$avulso      = number_format ($avulso,2,",",".")     ;
		$total       = number_format ($total,2,",",".")      ;
		
		echo "<tr class='Conteudo'align='center'>";
		echo "<td bgcolor='$cor' >$codigo_posto</td>";
		echo "<td bgcolor='$cor' align='left'>$nome_posto</td>";
		echo "<td bgcolor='$cor' align='left'>$extrato</td>";
		echo "<td bgcolor='$cor' >$data_geracao</td>";
		echo "<td bgcolor='$cor' align='rigth'>R$ $mao_de_obra</td>";
		echo "<td bgcolor='$cor' align='rigth'>R$ $pecas</td>";
		echo "<td bgcolor='$cor' align='rigth'>R$ $avulso</td>";
		echo "<td bgcolor='$cor' align='rigth'>R$ $total</td>";
		echo "<td bgcolor='$cor' align='rigth'>R$ $total_os</td>";
		echo "</tr>";
	}
	echo "</table>";
}

?>
