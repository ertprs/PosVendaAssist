<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

?>

<style>
	body {
		margin: 0;
		font: 10px Arial;
	}
</style>

<?php

$sql = "SELECT
		tbl_posto_fabrica.codigo_posto																	 ,
		tbl_posto_fabrica.reembolso_peca_estoque                              AS posto_pedido_em_garantia,
		TO_CHAR(tmp_os_duplicaca_black.data_digitacao, 'DD/MM/YYYY')          AS data_digitacao          ,

		tmp_os_duplicaca_black.os_com_troca                                                              ,
		tmp_os_duplicaca_black.sua_os_com_troca                                                          ,
		TO_CHAR(tmp_os_duplicaca_black.os_com_troca_finalizada, 'DD/MM/YYYY') AS os_com_troca_finalizada ,
		tmp_os_duplicaca_black.os_com_troca_extrato                                                      ,
		tmp_os_duplicaca_black.os_com_troca_pedido                                                       ,
		tmp_os_duplicaca_black.os_com_troca_peca                                                         ,

		tmp_os_duplicaca_black.os_duplicada                                                              ,
		tmp_os_duplicaca_black.sua_os_duplicada                                                          ,
		TO_CHAR(tmp_os_duplicaca_black.os_duplicada_finalizada, 'DD/MM/YYYY') AS os_duplicada_finalizada ,
		tmp_os_duplicaca_black.os_duplicada_extrato                                                      ,
		tmp_os_duplicaca_black.os_duplicada_pedido                                                       ,
		tmp_os_duplicaca_black.os_duplicada_peca                                                         

		FROM tmp_os_duplicaca_black
		JOIN tbl_os            ON tbl_os.os               = tmp_os_duplicaca_black.os_duplicada AND tbl_os.fabrica = 1
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_os_duplicaca_black.posto        AND tbl_posto_fabrica.fabrica = 1;";

$res   = pg_exec($con, $sql);
$total = pg_numrows($res);

if ($total > 0) {

    echo "<br />";

    echo "<table border='1' cellpadding='5' cellspacing='0' align='center' rules='all'>";

		echo "<caption><h1>Registros não Excluídos</h1></caption>";

		for ($i = 0; $i < $total; $i++) {

			$row = pg_fetch_assoc($res);
			$cor = $i % 2 == 0 ? '#EEEEEE' : '#FFFFFF';

			if ($i == 0) {
				echo "<tr>";
				foreach ($row as $k => $v) {
					if ($k != "os_com_troca" && $k != "os_duplicada") {
						$key = str_replace('_',' ',$k);
						echo '<th>'.ucwords($key).'</th>';
					}
				}
				echo "</tr>";
			}

			$x = 0;

			echo '<tr bgcolor="'.$cor.'" valign="middle">';

			foreach ($row as $k => $v) {

				if ($v == 't') $v = 'Sim';
				if ($v == 'f') $v = 'Não';

/*				if ($x == 4) {
					$os = $row["os_com_troca"];
					echo "<td align='center'><a href='http://www.telecontrol.com.br/assist/admin/os_press.php?os=$os' target='_blank'>$v</a></td>";
				}
				else if ($x == 10) {
					$os = $row["os_duplicada"];
					echo "<td align='center'><a href='http://www.telecontrol.com.br/assist/admin/os_press.php?os=$os' target='_blank'>$v</a></td>";
				}
				else */ if ($x == 7 OR $x == 13) {
					echo "<td align='center'><a href='http://www.telecontrol.com.br/assist/admin/pedido_admin_consulta.php?pedido=$v' target='_blank'>$v</a></td>";
				}
				else if ($x == 3 || $x == 9) {
				}
				else {
					echo "<td align='center'>$v</td>";
				}
				$x++;

			}

			echo "</tr>";

		}

		echo '<tr bgcolor="#CCCCCC"><th colspan="'.$total.'">Total: '.$i.'</th></tr>';

    echo "</table>";

}

$sql = "SELECT
		tbl_posto_fabrica.codigo_posto																	 ,
		tbl_posto_fabrica.reembolso_peca_estoque                              AS posto_pedido_em_garantia,
		TO_CHAR(tmp_os_duplicaca_black.data_digitacao, 'DD/MM/YYYY')          AS data_digitacao          ,

		tmp_os_duplicaca_black.os_com_troca                                                              ,
		tmp_os_duplicaca_black.sua_os_com_troca                                                          ,
		TO_CHAR(tmp_os_duplicaca_black.os_com_troca_finalizada, 'DD/MM/YYYY') AS os_com_troca_finalizada ,
		tmp_os_duplicaca_black.os_com_troca_extrato                                                      ,
		tmp_os_duplicaca_black.os_com_troca_pedido                                                       ,
		tmp_os_duplicaca_black.os_com_troca_peca                                                         ,

		tmp_os_duplicaca_black.os_duplicada                                                              ,
		tmp_os_duplicaca_black.sua_os_duplicada                                                          ,
		TO_CHAR(tmp_os_duplicaca_black.os_duplicada_finalizada, 'DD/MM/YYYY') AS os_duplicada_finalizada ,
		tmp_os_duplicaca_black.os_duplicada_extrato                                                      ,
		tmp_os_duplicaca_black.os_duplicada_pedido                                                       ,
		tmp_os_duplicaca_black.os_duplicada_peca                                                         

		FROM tmp_os_duplicaca_black
		JOIN tbl_os            ON tbl_os.os               = tmp_os_duplicaca_black.os_duplicada AND tbl_os.fabrica = 0
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_os_duplicaca_black.posto        AND tbl_posto_fabrica.fabrica = 1;";

$res   = pg_exec($con, $sql);
$total = pg_numrows($res);

if ($total > 0) {

    echo "<br />";
    echo "<br />";
    echo "<br />";
    echo "<br />";

    echo "<table border='1' cellpadding='5' cellspacing='0' align='center' rules='all'>";

		echo "<caption><h1>Registros Duplicados e Excluídos</h1></caption>";

		for ($i = 0; $i < $total; $i++) {

			$row = pg_fetch_assoc($res);
			$cor = $i % 2 == 0 ? '#EEEEEE' : '#FFFFFF';

			if ($i == 0) {
				echo "<tr>";
				foreach ($row as $k => $v) {
					if ($k != "os_com_troca" && $k != "os_duplicada") {
						$key = str_replace('_',' ',$k);
						echo '<th>'.ucwords($key).'</th>';
					}
				}
				echo "</tr>";
			}

			$x = 0;

			echo '<tr bgcolor="'.$cor.'" valign="middle">';

			foreach ($row as $k => $v) {

				if ($v == 't') $v = 'Sim';
				if ($v == 'f') $v = 'Não';

/*				if ($x == 4) {
					$os = $row["os_com_troca"];
					echo "<td align='center'><a href='http://www.telecontrol.com.br/assist/admin/os_press.php?os=$os' target='_blank'>$v</a></td>";
				}
				else if ($x == 10) {
					$os = $row["os_duplicada"];
					echo "<td align='center'><a href='http://www.telecontrol.com.br/assist/admin/os_press.php?os=$os' target='_blank'>$v</a></td>";
				}
				else */if ($x == 7 OR $x == 13) {
					echo "<td align='center'><a href='http://www.telecontrol.com.br/assist/admin/pedido_admin_consulta.php?pedido=$v' target='_blank'>$v</a></td>";
				}
				else if ($x == 3 || $x == 9) {
				}
				else {
					echo "<td align='center'>$v</td>";
				}
				$x++;

			}

			echo "</tr>";

		}

		echo '<tr bgcolor="#CCCCCC"><th colspan="'.$total.'">Total: '.$i.'</th></tr>';

    echo "</table>";


}?>