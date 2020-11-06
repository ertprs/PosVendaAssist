<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
$hoje = date('Y-m-d');

$atendente = $cook_admin;

$ajaxCache = new Posvenda\AjaxCache($login_fabrica, $login_admin, __FILE__);
$cache = $ajaxCache->getFromCache();

if (!empty($_POST['atualiza_atendimentos']) and $_POST['atualiza_atendimentos'] == 'true') {
	$cache = $ajaxCache->cleanCache();
}

$sql = "
        SELECT  tbl_hd_chamado.hd_chamado,
                tbl_hd_chamado.data_providencia::DATE AS data_providencia,
                tbl_hd_chamado_extra.array_campos_adicionais
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra    USING(hd_chamado)
        WHERE   tbl_hd_chamado.fabrica                  = {$login_fabrica}
        AND     tbl_hd_chamado.fabrica_responsavel      = {$login_fabrica}
        AND     tbl_hd_chamado.status                   NOT IN('Cancelado','Resolvido','Finalizado')
        AND     tbl_hd_chamado.atendente                = {$login_admin}
        AND     tbl_hd_chamado.data_providencia         IS NOT NULL
        AND     tbl_hd_chamado.data_providencia::date <= CURRENT_DATE
        --AND     tbl_hd_chamado.posto isnull
        --AND     tbl_hd_chamado_extra.leitura_pendente   IS TRUE
        --AND     tbl_hd_chamado.cliente_admin IS NOT NULL
  ORDER BY      tbl_hd_chamado.data_providencia
";
//

$rows = 0;

if (empty($cache)) {
	$res = pg_query($con, $sql);

	$rows = pg_num_rows($res);
} else {
	die($cache);
}

$retorno = array(
    "qtde"         => $rows,
    "atendimentos" => array()
);

$k = 0;
if ($rows > 0) {
    for ($i = 0; $i < $rows; $i++) {
        $atendimento         = pg_fetch_result($res,$i,hd_chamado);
        $data_providencia    = pg_fetch_result($res,$i,data_providencia);

        $array_campos_adicionais = pg_fetch_result($res, $i, "array_campos_adicionais");

        if(strlen(trim($array_campos_adicionais)) > 0){
            $array_campos_adicionais    = json_decode($array_campos_adicionais, true);
            $data_limite                = $array_campos_adicionais["data_limite"];

            if(!empty($data_limite)){
                list($d, $m, $a) = explode("/", $data_limite);
                $data_limite = "{$a}-{$m}-{$d}";
            } else {
                $data_limite = "0000-00-00";
            }

            if( strtotime('today') == strtotime($data_providencia."- 1 day")) {
                $perto_prazo = 'prazo24';
            } else if (strtotime('today') == strtotime($data_providencia."- 2 day")) {
                $perto_prazo = 'prazo48';
            } else if (strtotime('today') == strtotime($data_providencia."- 3 day")) {
                $perto_prazo = 'prazo72';
            } else {
                $perto_prazo = '';
            }

            $k ++;
        }

        $retorno["atendimentos"][] = array(
            "atendimento"       => $atendimento,
            "data_providencia"  => $data_providencia,
            "perto_prazo"       => $perto_prazo,
            "data_programada"	=> $data_limite
        );

        if($k == 5) $i = $rows;
    }
}

function sortFunction( $a, $b ) {
    return strtotime($a["data_providencia"]) - strtotime($b["data_providencia"]);
}

usort($retorno["atendimentos"], "sortFunction");

$ret = json_encode($retorno);

$ajaxCache->writeCache($ret);

echo json_encode($retorno);

?>
