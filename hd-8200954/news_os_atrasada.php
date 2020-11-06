<?
##### OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA #####
$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $login_posto
		AND   (tbl_os.data_abertura + INTERVAL '20 days') <= current_date
		AND   (tbl_os.data_abertura + INTERVAL '30 days') > current_date
		AND   tbl_os.data_fechamento IS NULL
		ORDER BY os_ordem";
$res = pg_exec($con,$sql);
//echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
if (pg_numrows($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' height='15' bgcolor='#91C8FF'>";
	echo "<td colspan='3'>OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA</td>";
	echo "</tr>";
	echo "<tr class='Titulo' height='15' bgcolor='#91C8FF'>";
	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>PRODUTO</td>";
	echo "</tr>";
	for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
		$os               = trim(pg_result($res,$a,os));
		$sua_os           = $login_codigo_posto . trim(pg_result($res,$a,sua_os));
		$abertura         = trim(pg_result($res,$a,abertura));
		$referencia       = trim(pg_result($res,$a,referencia));
		$descricao        = trim(pg_result($res,$a,descricao));
		$voltagem         = trim(pg_result($res,$a,voltagem));
		$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

		$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		echo "<td align='center'>" . $sua_os . "</td>";
		echo "<td align='center'>" . $abertura . "</td>";
		echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
##### OS SEM DATA DE FECHAMENTO HÁ 20 DIAS OU MAIS DA DATA DE ABERTURA #####
#*/                                                                                             
##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####
$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_os.posto   = $login_posto
		AND   (tbl_os.data_abertura + INTERVAL '30 days') <= current_date
		AND   tbl_os.data_fechamento IS NULL
		AND  tbl_os.excluida is not true
		ORDER BY os_ordem";
$res = pg_exec($con,$sql);
//echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
if (pg_numrows($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
	echo "<td colspan='3'>OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO<br><font color='#FFFF00'>Clique na OS para informar o Motivo</font></td>";
	echo "</tr>";
	echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>PRODUTO</td>";
	echo "</tr>";
	for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
		$os               = trim(pg_result($res,$a,os));
		$sua_os           = $login_codigo_posto . trim(pg_result($res,$a,sua_os));
		$abertura         = trim(pg_result($res,$a,abertura));
		$referencia       = trim(pg_result($res,$a,referencia));
		$descricao        = trim(pg_result($res,$a,descricao));
		$voltagem         = trim(pg_result($res,$a,voltagem));
		$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;
		
		$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
		echo "<td align='center'><a href='os_motivo_atraso.php?os=$os' target='_blank'>" . $sua_os . "</a></td>";
		echo "<td align='center'>" . $abertura . "</td>";
		echo "<td><acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
##### OS QUE EXCEDERAM O PRAZO LIMITE DE 30 DIAS PARA FECHAMENTO #####

?>