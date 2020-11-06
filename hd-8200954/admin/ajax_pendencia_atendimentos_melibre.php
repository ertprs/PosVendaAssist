<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$atendente = $cook_admin;

$limit = "";
if(in_array($login_fabrica, [174])) {
  $limit = " limit 5 ";
}

$query_countfb = "
SELECT xx.*
FROM (
    SELECT x.*
    FROM (
        SELECT DISTINCT ON(tbl_hd_chamado_item.hd_chamado) tbl_hd_chamado.hd_chamado, tbl_hd_chamado_item.status_item, tbl_hd_chamado_item.data
        FROM tbl_hd_chamado 
        INNER JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
        WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
        AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
        AND tbl_hd_chamado.status NOT IN('Cancelado', 'Resolvido') 
        AND tbl_hd_chamado.atendente = {$login_admin} 
        ORDER BY tbl_hd_chamado_item.hd_chamado, tbl_hd_chamado_item.data DESC
    ) x
    WHERE x.status_item ~ 'Mercado Livre'
) xx
";

$res = pg_query($con, $query_countfb);
$rows = pg_num_rows($res);

$retorno = array(
    'qtde'         => $rows
);

if ($rows > 0) {
    for ($i = 0; $i < $rows; $i++) {
        $atendimento = pg_fetch_result($res, $i, 'hd_chamado');
        $data = pg_fetch_result($res, $i, 'data');

        $retorno['atendimentos'][] = array(
            'atendimento'     => $atendimento,
            'data' => implode("/", array_reverse(explode("-", explode(" ", $data)[0])))
        );
    }

    usort($retorno['atendimentos'], 'sortFunction');

    die(json_encode($retorno));
}

echo '[]';

?>
