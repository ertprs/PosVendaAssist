<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$interacao_pedido = false;

$ajaxCache = new Posvenda\AjaxCache($login_fabrica, $login_admin, __FILE__);
$cache = $ajaxCache->getFromCache();

if (!empty($_POST['limpaCache']) and $_POST['limpaCache'] == 'true') {
  $cache = $ajaxCache->cleanCache();
}

if ($_POST["busca_os_ag_interacao"]) {
    if ($fabrica_interacao_admin_sap) { // os_cadastro_unico/interacao/regras.php
        $admin_sap = "AND tbl_posto_fabrica.admin_sap = $login_admin";
    } else if ($login_fabrica == 72) {
 	$admin_sap = "AND tbl_posto_fabrica.admin_sap = $login_admin";
    }
    if (in_array($login_fabrica,[72])){
      $finalizada = '';
    } else {
      $finalizada = 'AND tbl_os.finalizada IS NULL';
    }

    if($telecontrol_distrib and $$login_fabrica != 10){
      $cond_tranferido_para = " and tbl_os_interacao.transferido_para = $login_admin ";
      $cond_tranfer = " and comentario not ilike 'Transferido para%' ";
      $confirmacao_leitura = " and tbl_os_interacao.confirmacao_leitura is null ";
      $interacao_pedido = true;
    }else{
       $cond_admin = " AND ( SELECT admin
                        FROM tbl_os_interacao oi
                       WHERE oi.os = tbl_os.os
                       ORDER BY oi.os_interacao DESC LIMIT 1) IS NULL ";

      $confirmacao_leitura = " AND ( SELECT confirmacao_leitura
                        FROM tbl_os_interacao oi
                       WHERE oi.os = tbl_os.os
                       ORDER BY oi.os_interacao DESC LIMIT 1) IS NULL ";
    }

    $sql = " SELECT DISTINCT tbl_os.os, tbl_os.sua_os, (
                     SELECT data
                     FROM tbl_os_interacao
                     WHERE os = tbl_os.os
                     ORDER BY os_interacao DESC LIMIT 1) AS data_ultima_interacao
               FROM tbl_os
               JOIN tbl_os_interacao  USING(os, posto, fabrica)
               JOIN tbl_posto_fabrica USING(posto, fabrica)
              WHERE tbl_os.fabrica = $login_fabrica
                $cond_admin
                $cond_tranfer 
                $cond_tranferido_para
                $confirmacao_leitura 
                $finalizada
                $admin_sap
              ORDER BY 3 ";

if (empty($cache)) {
    $res = pg_query($con, $sql);
}else{
  die($cache);
}
    $retorno = array(
        'qtde' => pg_num_rows($res),
        'oss'  => array()
    );

    for ($i=0; $i<pg_num_rows($res); $i++) {
        $os                    = pg_fetch_result($res, $i, 'os');
        $data_ultima_interacao = substr(pg_fetch_result($res, $i, 'data_ultima_interacao'), 0, 10);
        $data_ultima_interacao = explode("-", $data_ultima_interacao);
        $data = $data_ultima_interacao[2]."/".$data_ultima_interacao[1]."/".$data_ultima_interacao[0];

        $retorno['oss'][] = array(
            'os' => "$os",
            'pedido' => '',
            'data_programada' => "$data"
        );
    }

    if($interacao_pedido == true){ 

        $sql_interacaoPedido = "SELECT distinct pedido, (select data from tbl_interacao where registro_id = tbl_pedido.pedido $cond_tranfer order by interacao desc limit 1) as data_ultima_interacao FROM tbl_pedido join tbl_interacao on tbl_interacao.registro_id = tbl_pedido.pedido and tbl_interacao.contexto = 2 
        WHERE tbl_pedido.fabrica = $login_fabrica and confirmacao_leitura is null and tbl_interacao.transferido_para = $login_admin $cond_tranfer";
        $res_interacaoPedido = pg_query($con, $sql_interacaoPedido);
      

        for($a=0; $a<pg_num_rows($res_interacaoPedido); $a++){
          $pedido = pg_fetch_result($res_interacaoPedido, $a, 'pedido');
          $data_ultima_interacao = substr(pg_fetch_result($res_interacaoPedido, $a, 'data_ultima_interacao'), 0, 10);

          $data_ultima_interacao = explode("-", $data_ultima_interacao);
          $data = $data_ultima_interacao[2]."/".$data_ultima_interacao[1]."/".$data_ultima_interacao[0];

          $retorno['oss'][] = array(
              'os' => "",
              'pedido' => $pedido,
              'data_programada' => "$data"
          );
        }

        $retorno['qtde'] = $retorno['qtde'] + pg_num_rows($res_interacaoPedido);
    }

    $ret = json_encode($retorno);

    $ajaxCache->writeCache($ret);

    die($ret);
}

