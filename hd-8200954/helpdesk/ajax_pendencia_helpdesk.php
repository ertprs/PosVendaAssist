<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
$hoje = date('Y-m-d');
$analista_hd = "";
$atendente   = $cook_admin;
$cond = "";
if (!empty($_GET['analista'])) {
    $analista_hd = $_GET['analista'];
}

if ($_POST['atualiza_atendimentos'] && $_POST['sla']) {
    if (in_array($atendente, array(5205,4789,8527)) || ($analista_hd == 'sim' && $atendente != 586)) {

        $sql = "
             SELECT  tbl_hd_chamado.atendente, tbl_hd_chamado.fabrica, tbl_hd_chamado.hd_chamado, to_char(tbl_hd_chamado.previsao_termino, 'DD/MM/YYYY')  previsao_termino, fn_retira_especiais(tbl_hd_chamado.status) AS status
               FROM  tbl_hd_chamado
              WHERE  tbl_hd_chamado.previsao_termino <= '".date('Y-m-d')." 23:59:59'
                AND  tbl_hd_chamado.status NOT IN ('Resolvido', 'Cancelado')
                {$cond}
           ORDER BY  tbl_hd_chamado.previsao_termino 
        ";

        $res  = pg_query($con, $sql);
        $rows = pg_num_rows($res);

        $retorno = array(
          "qtde_sla"         => array(),
          "atendimentos_sla" => array(),
          "qtde"             => array(),
          "atendimentos"     => array(),
        );

        if ($rows > 0) {
            for ($i = 0; $i < $rows; $i++) {
                $atendimento      = pg_fetch_result($res,$i,hd_chamado);
                $previsao_termino = pg_fetch_result($res,$i,previsao_termino);
                $xAtendente       = pg_fetch_result($res,$i,atendente);
		$fabrica          = pg_fetch_result($res,$i,fabrica);
		$status		  = pg_fetch_result($res,$i,status);

                $sqlFab = "
                     SELECT  tbl_fabrica.fabrica, tbl_fabrica.nome
                       FROM  tbl_fabrica
                      WHERE tbl_fabrica.fabrica = {$fabrica} 
                ";
                $resFab  = pg_query($con, $sqlFab);
                if (pg_num_rows($resFab) > 0) { 
                    $xfabricaNome = pg_fetch_result($resFab,0,nome);
                    $xfabrica = "$xfabricaNome";
                } else {
                    $xfabrica = "";
                }
                if (in_array($fabrica, array(1,159))) {
					$retorno['qtde_sla'][] = 1;
					$retorno["atendimentos_sla"][] = array(
						"atendimento"       => $atendimento,
						"previsao_termino"  => $previsao_termino,
						"fabrica"  => $xfabrica,
						"status"   => $status,
					);
                } else {
					$retorno['qtde'][] = 1;
					$retorno["atendimentos"][] = array(
						"atendimento"       => $atendimento,
						"previsao_termino"  => $previsao_termino,
						"fabrica"  => $xfabrica,
						"status"   => $status,
					);
                }
            }
        }

        exit(json_encode($retorno));
    } 
}

if ($_POST['atualiza_atendimentos'] && !in_array($atendente, array(5205,4789,8527))) {
    $sql = "
         SELECT  tbl_hd_chamado.fabrica, tbl_hd_chamado.hd_chamado, to_char(tbl_hd_chamado.previsao_termino, 'DD/MM/YYYY')  previsao_termino, fn_retira_especiais(tbl_hd_chamado.status) AS status
           FROM  tbl_hd_chamado
          WHERE  tbl_hd_chamado.previsao_termino <= '".date('Y-m-d')." 23:59:59'
            AND  tbl_hd_chamado.status NOT IN ('Resolvido', 'Cancelado')
            AND  tbl_hd_chamado.atendente={$atendente}
       ORDER BY  tbl_hd_chamado.previsao_termino 
    ";

    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);

    $retorno = array(
      "qtde"         => $rows,
      "atendimentos" => array()
    );

    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $atendimento      = pg_fetch_result($res,$i,hd_chamado);
            $previsao_termino = pg_fetch_result($res,$i,previsao_termino);
	    $fabrica = pg_fetch_result($res,$i,fabrica);
	    $status  = pg_fetch_result($res,$i,status);

            $sqlFab = "
                 SELECT  tbl_fabrica.fabrica, tbl_fabrica.nome
                   FROM  tbl_fabrica
                  WHERE tbl_fabrica.fabrica = {$fabrica} 
            ";
            $resFab  = pg_query($con, $sqlFab);
            if (pg_num_rows($resFab) > 0) { 
                $xfabricaNome = pg_fetch_result($resFab,0,nome);
                $xfabrica = "$xfabricaNome";
            } else {
                $xfabrica = "";
            }
            $retorno["atendimentos"][] = array(
              "atendimento"       => $atendimento,
              "previsao_termino"  => $previsao_termino,
	      "fabrica"  => $xfabrica,
	      "status"   => $status,
            );
        }
    }

    echo json_encode($retorno);
}
