<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
$hoje = date('Y-m-d');

$atendente = $cook_admin;

if (isset($_POST['atualiza_chamado_interno'])) {
    $sql = "
            SELECT
                tbl_hd_chamado.hd_chamado,
                tbl_hd_chamado.data_providencia::DATE AS data_providencia
            FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra USING(hd_chamado)
                JOIN tbl_tipo_solicitacao USING(tipo_solicitacao)
            WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
                AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
                AND tbl_hd_chamado.atendente = {$login_admin}
                AND tbl_tipo_solicitacao.codigo = 'I' AND tbl_tipo_solicitacao.fabrica = {$login_fabrica}
                AND tbl_hd_chamado.status NOT IN ('Finalizado', 'Cancelado')
            ORDER BY tbl_hd_chamado.hd_chamado";
    
    $res = pg_query($con, $sql);

    $rows = pg_num_rows($res);

    $retorno = array(
        "qtde"         => $rows,
        "atendimentos" => array()
    );

    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $atendimento       = pg_fetch_result($res, $i, "hd_chamado");
            $data_providencia  = pg_fetch_result($res, $i, "data_providencia");

            $retorno["atendimentos"][] = array(
                "atendimento"       => $atendimento,
                "data_programada"   => implode('/', array_reverse(explode('-', $data_providencia)))
            );
        }
    }

    exit(json_encode($retorno));
}

$cond_status = "tbl_hd_chamado.status IN('Ag. Finalização', 'Ag. Fábrica')";

if ($login_fabrica == 30) {
    $cond_status = "(
        tbl_hd_chamado.status IN('Ag. Finalização', 'Ag. Fábrica') OR (
            titulo = 'Help-Desk Posto' AND status NOT IN ('Finalizado', 'Cancelado') AND data_providencia IS NOT NULL
        )
    )";
}

if (in_array($login_fabrica, [169,170])) {

    $cond_status = "tbl_hd_chamado.status NOT IN ('Finalizado', 'Cancelado') 
                    AND tbl_hd_chamado.titulo = 'Help-Desk Admin'";

}

$tela_nova = "";
if (in_array($login_fabrica, [11,172])) {
    $tela_nova = " AND tbl_hd_chamado.tipo_solicitacao NOTNULL";
}

if (isset($_POST['getCount']) && $_POST['getCount'] == "true") {
    $campos = "COUNT(1) as total";
} else {
    $campos = "tbl_hd_chamado.hd_chamado";
}

$sql = "
        SELECT  {$campos}
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra    USING(hd_chamado)
        WHERE   tbl_hd_chamado.fabrica                  = {$login_fabrica}
        AND     tbl_hd_chamado.fabrica_responsavel      = {$login_fabrica}
        AND     $cond_status
        AND     tbl_hd_chamado.atendente                = {$login_admin}
        AND     (tbl_hd_chamado.data_providencia::date <= CURRENT_DATE OR tbl_hd_chamado.data_providencia IS NULL)
        $tela_nova
";
//AND tbl_hd_chamado.cliente_admin IS NOT NULL
//

$res = pg_query($con, $sql);

if ($_POST['getCount'] =='true') {

    $retorno = array(
        "qtde"         => (int) pg_fetch_result($res, 0, 'total'),
        "atendimentos" => array()
    );

} else {

    $rows = pg_num_rows($res);

    $retorno = array(
        "qtde"         => $rows,
        "atendimentos" => array()
    );

    $k = 0;
    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $atendimento         = pg_fetch_result($res,$i,hd_chamado);

            $retorno["atendimentos"][] = array(
                "atendimento"       => $atendimento,
            );

            if($k == 5) $i = $rows;
        }
    }
}
echo json_encode($retorno);

?>
