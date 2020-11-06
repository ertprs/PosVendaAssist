<?php
header("Expires: {$gmtDate} GMT");
header("Cache-Control: no-store, no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

extract($_GET);
if ($ajax == "consulta") {
	$anterior = ($dias == 5) ? 0 : $dias - 9;
	if ($dias < 35) {
		$sql = "SELECT DISTINCT os,
						CASE WHEN sua_os IS NULL THEN os::varchar ELSE sua_os END AS sua_os,
						(SELECT count(os_produto) AS qtde_itens FROM tbl_os_produto WHERE os = tbl_os.os)
                    FROM tbl_os
                  WHERE fabrica = $login_fabrica
                    AND posto   = $login_posto
                    AND data_fechamento IS NULL
                    AND tbl_os.excluida IS NOT TRUE
                    AND data_abertura::date BETWEEN current_date - INTERVAL '$dias days' AND current_date - INTERVAL '$anterior days'
				ORDER BY os
		";
	} else {
		$sql = "SELECT DISTINCT os,
						CASE WHEN sua_os IS NULL THEN os::varchar ELSE sua_os END AS sua_os,
						(SELECT count(os_produto) AS qtde_itens FROM tbl_os_produto WHERE os = tbl_os.os)
                    FROM tbl_os
                  WHERE fabrica	= $login_fabrica
					    AND posto	= $login_posto
					    AND data_fechamento IS NULL
					    AND tbl_os.excluida IS NOT TRUE
					    AND data_abertura::date < current_date-INTERVAL '25 days'
				ORDER BY os
		";
	}
	$res = @pg_query($con, $sql);
	if (!is_resource($res)) {echo "ko";exit;}
	$numrows = pg_num_rows($res);
	if ($numrows === false) {echo "ko";exit;}
	$os_sem = Array();
	$os_com = Array();
    for ($i; $i < $numrows; $i++):
    	list ($os, $sua_os, $qtde_pecas) = pg_fetch_row($res,$i);
    	if ($mostra == 'true') echo "$os<br>";
    	$os = "<a target='_blank' href='os_item_new.php?os=$os'>$sua_os</a>";
    	if ($mostra == 'true') echo "$os<br>";
    	if ($qtde_pecas):
    	    $os_com[] = $os;
		else:
		    $os_sem[] = $os;
		endif;
	endfor;
	if (!count($os_sem) and !count($os_com)) {echo "NO RESULTS";exit;}
	if (count($os_sem)) {
		echo "<p>OS em aberto <b>SEM</b> peças entre $anterior e $dias dias:<br>\n".
	         implode(", ", $os_sem).
	         "</p>";
	}
	if (count($os_com)) {
		echo "<p>OS em aberto <b>COM</b> peças:<br>\n".
	         implode(", ", $os_com).
	         "</p>";
	}
	exit;
}
?>
