<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$atendente = $cook_admin;
$usa_data_providencia = in_array($login_fabrica, array(156));

if($moduloProvidencia){
	$usa_data_providencia = array($login_fabrica);
}

$ajaxCache = new Posvenda\AjaxCache($login_fabrica, $login_admin, __FILE__);
$cache = $ajaxCache->getFromCache();

if (!empty($_POST['atualiza_atendimentos']) and $_POST['atualiza_atendimentos'] == 'true') {
	$cache = $ajaxCache->cleanCache();
}

$limit = "";
if(in_array($login_fabrica, [90,174])) {
  $limit = " limit 5 ";
} elseif ($login_fabrica == 35) {
  $limit = " limit 10 ";
}
$campo_prioridade = "";
if(in_array($login_fabrica, [90,125,164,174])) {
	$cond  = "    AND     tbl_hd_chamado.data_providencia::date <= CURRENT_DATE "; 
  $order_by = " ORDER BY tbl_hd_chamado.data_providencia ASC ";
}elseif($login_fabrica == 35) {
  $order_by = " ORDER BY tbl_hd_chamado.hd_chamado ASC ";
}elseif($login_fabrica == 189) {
#  $campo_prioridade  = ", tbl_hd_chamado_extra.array_campos_adicionais::jsonb->'prioridade' AS prioridade";
  $order_by = " ORDER BY tbl_hd_chamado.data_providencia ASC ";
}else{
  $order_by = " ORDER BY tbl_hd_chamado.data ASC ";
}

$joinAdmin = '';
$whereAdmin = '';
if ($login_fabrica == 183) {
	$joinAdmin = "
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = {$login_fabrica}
		JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
	";
	$whereAdmin = "
		AND (tbl_admin.admin_sap IS TRUE
		AND ((tbl_hd_motivo_ligacao.descricao = 'ACOMPANHAR PROCESSO'
		AND data::date <= CURRENT_DATE - INTERVAL '20 DAYS')
		OR (fn_retira_especiais(tbl_hd_motivo_ligacao.descricao) IN ('SEM POSTO', 'AUTORIZACAO DE TROCA')))
		OR tbl_admin.admin_sap IS NOT TRUE)";
}

if (!$usa_data_providencia) {
    $sql = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado_extra.array_campos_adicionais          
              FROM tbl_hd_chamado
              JOIN tbl_hd_chamado_extra USING(hd_chamado)              
             WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
               AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
               AND tbl_hd_chamado.status <> 'Resolvido'
               AND tbl_hd_chamado_extra.leitura_pendente IS TRUE
			   AND tbl_hd_chamado.posto isnull
               AND tbl_hd_chamado_extra.array_campos_adicionais ~E'\"admin_agendamento\":\"?$atendente\"? *[,}]';";
} else if($login_fabrica == 35){
    $sql = "SELECT tbl_hd_chamado.hd_chamado,
                   TO_CHAR(tbl_hd_chamado.data_providencia,'YYYY-MM-DD') AS data_programada,
                   (select to_char(tbl_hd_chamado_item.data, 'YYYY-MM-DD') from tbl_hd_chamado_item where tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado order by hd_chamado_item desc limit 1) as ultima_interacao 
              FROM tbl_hd_chamado
             WHERE tbl_hd_chamado.fabrica = $login_fabrica
               AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
               AND tbl_hd_chamado.atendente = $atendente
               AND tbl_hd_chamado.status NOT IN('Resolvido','Cancelado')
			   AND tbl_hd_chamado.posto isnull
               AND data_providencia IS NOT NULL
	       $cond
	       GROUP BY tbl_hd_chamado.hd_chamado, tbl_hd_chamado.data_providencia
             $order_by
          $limit ";


}else{
 $sql = "SELECT tbl_hd_chamado.hd_chamado,
                TO_CHAR(tbl_hd_chamado.data_providencia,'YYYY-MM-DD') AS data_programada {$campo_prioridade}   
              FROM tbl_hd_chamado
              JOIN tbl_hd_chamado_extra USING(hd_chamado)
		{$joinAdmin}
             WHERE tbl_hd_chamado.fabrica = $login_fabrica
               AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
               AND tbl_hd_chamado.atendente = $atendente
               AND tbl_hd_chamado.status NOT IN('Resolvido','Cancelado')
	       AND tbl_hd_chamado.posto isnull
               AND data_providencia IS NOT NULL
		{$whereAdmin}
	       $cond
             $order_by
          $limit ";
}

$rows = 0;

if (empty($cache)) {
	$res = pg_query($con, $sql);
	$rows = pg_num_rows($res);
} else {
	die($cache);
}

$retorno = array(
    'qtde'         => $rows,
    'atendimentos' => array()
);


if ($rows > 0) {

    function sortFunction( $a, $b ) {
        return strtotime($a['data_programada']) - strtotime($b['data_programada']);
    }

    for ($i = 0; $i < $rows; $i++) {
        $atendimento = pg_fetch_result($res, $i, 'hd_chamado');

        if ($usa_data_providencia) {
            $data_programada         = pg_fetch_result($res, $i, 'data_programada');
            $ultima_interacao        = pg_fetch_result($res, $i, 'ultima_interacao');
        } else {
            $array_campos_adicionais = json_decode(pg_fetch_result($res, $i, 'array_campos_adicionais'), true);

            if (!is_null($array_campos_adicionais)) {
                $data_programada = $array_campos_adicionais['data_programada'];
                if ($data_programada) {
                    list($d, $m, $a) = explode('/', $data_programada);
                    $data_programada = "{$a}-{$m}-{$d}";
                }
            }
        }

        $retorno['atendimentos'][] = array(
            'atendimento'     => $atendimento,
            'data_programada' => $data_programada,
            'ultima_interacao' => $ultima_interacao            
        );
    }

    usort($retorno['atendimentos'], 'sortFunction');

	$ret = json_encode($retorno);

	$ajaxCache->writeCache($ret);

    die($ret);
}

echo '[]';

