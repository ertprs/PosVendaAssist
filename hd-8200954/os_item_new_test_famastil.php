<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include 'ajax_cabecalho.php';

//HD 56418 Candece
if ($login_fabrica == 50 OR $login_fabrica == 35) {
    $os = $_GET['os'];
    if (strlen($os) > 0){
        $sql = "SELECT os, status_os from tbl_os_status WHERE os = $os LIMIT 1";
        $res = pg_query($con,$sql) ;
        if (pg_num_rows($res)>0){
            $testandostatus = pg_fetch_result($res,0,status_os);
            if ($testandostatus == "118" OR $testandostatus == "127"){
                header("Location: os_em_auditoria.php?os=$os");
                exit;
            }
        }
    }
}


// 14/01/2010 MLG - HD 189523
if ($login_fabrica == 15) {

    if (!function_exists("is_between")) {
        function is_between($valor,$min,$max) {   // BEGIN function is_between
            // Devolve 'true' se o valor está entre ("between") o $min e o $max
            return ($valor >= $min AND $valor <= $max);
        }
    }

    function valida_serie_latinatec($serie,$prod_min_ver,$prod_max_ver,$lbm_min_ver,$lbm_max_ver) {
        if (strlen(trim($serie)) < 3) return true;
        $serie_ok = false;
        $usar_serie_produto    = ($prod_min_ver != "" or $prod_max_ver != "");
        $usar_serie_lbm        = ($lbm_min_ver != "" or $lbm_max_ver != "");
        if (!$usar_serie_lbm and !$usar_serie_produto) $serie_ok = true;
        if (!$serie_ok) {
            $min_serie_prod = (trim($prod_min_ver) == "") ? " " : strtoupper($prod_min_ver);
            $max_serie_prod = (trim($prod_max_ver) == "") ? "z" : strtoupper($prod_max_ver);
            $min_serie_lbm  = (trim($lbm_min_ver)  == "") ? " " : strtoupper($lbm_min_ver);
            $max_serie_lbm  = (trim($lbm_max_ver)  == "") ? "z" : strtoupper($lbm_max_ver); // O minúsculo é proposital...
            if (is_between(strtoupper($serie[1]),$min_serie_prod, $max_serie_prod) or !$usar_serie_produto) {
                if (is_between(strtoupper($serie[1]),$min_serie_lbm, $max_serie_lbm)) {
                    $serie_ok = true;
                }
            }
        }
        return $serie_ok;
    }

}

/*IGOR HD: 44202 - 16/10/2008*/
if ($login_fabrica == 3) {

    $xos = $_GET['os'];

    if (strlen($xos) == 0) {
        $xos = $_POST['os'];
    }

    if (strlen($xos) > 0) {

        $status_os = "";

        $sql = "SELECT status_os
                FROM  tbl_os_status
                WHERE os = $xos
                AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
                ORDER BY data DESC LIMIT 1";
        $res_intervencao = pg_query($con, $sql);
        $msg_erro        = pg_errormessage($con);

        if (pg_num_rows($res_intervencao) > 0) {

            $status_os = pg_fetch_result($res_intervencao,0,status_os);

            if ($status_os == "120" OR $status_os == "122" OR $status_os == "126" OR $status_os == "140" OR $status_os == "141" OR $status_os == "143") {
                header("Location: os_press.php?os=$xos");
                exit;
            }

        }

    }

}

if ($login_fabrica == 5) {
    $os=$_GET['os'];
    header("Location: os_item_new_mondial.php?os=$os&reabrir=$reabrir");
    exit;
}

if ($ajax == 'peca') {

    if (strlen(trim($referencia)) > 4) {

        $sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql) ;
        $produto = pg_fetch_result($res,0,produto);

        $sql = "SELECT peca,referencia,descricao
                FROM tbl_peca
                JOIN tbl_lista_basica USING (peca)
                WHERE referencia               = '$referencia'
                AND   tbl_peca.fabrica         = $login_fabrica
                AND   tbl_lista_basica.produto = $produto
                AND   tbl_peca.ativo  IS     TRUE
                AND   produto_acabado IS NOT TRUE;";

#        $sql = "SELECT peca,referencia,descricao
#                FROM tbl_peca
#                WHERE referencia               = '$referencia'
#                AND   tbl_peca.fabrica         = $login_fabrica
#                AND   tbl_peca.ativo  IS     TRUE
#                AND   produto_acabado IS NOT TRUE;";

        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $descricao = pg_fetch_result($res,0,descricao);
            echo "ok|$descricao";
        } else echo "NO|NO";
    }else echo "NO|NO";
    exit;
}

if ($ajax == 'defeito_constatado') {

    if (strlen(trim($defeito_constatado)) > 2) {

        $sql = "SELECT  tbl_linha.linha    ,
                        tbl_familia.familia
                FROM tbl_os
                JOIN tbl_produto USING(produto)
                JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
                JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
                WHERE tbl_os.fabrica = $login_fabrica
                AND   tbl_os.os      = $os";

        $res = pg_query($con,$sql) ;
        $produto_linha   = pg_fetch_result($res,0,'linha');
        $produto_familia = pg_fetch_result($res,0,'familia');

        $sql = "SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
                       tbl_defeito_constatado.descricao,
                       tbl_defeito_constatado.codigo
                  FROM tbl_diagnostico
                  JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado and tbl_defeito_constatado.ativo <> 'f'
                 WHERE tbl_diagnostico.linha   = $produto_linha
                   AND tbl_diagnostico.familia = $produto_familia
                   AND tbl_diagnostico.ativo   = 't'
                   AND tbl_defeito_constatado.codigo = '$defeito_constatado'
                 ORDER BY tbl_defeito_constatado.descricao";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $defeito_constatado = pg_fetch_result($res,0,'defeito_constatado');
            $codigo             = pg_fetch_result($res,0,'codigo');
            $descricao          = pg_fetch_result($res,0,'descricao');
            echo "ok|$defeito_constatado|$codigo|$descricao";

        } else {

            echo "NO|NO $sql";

        }

    } else {

        echo "NO|NO";

    }

    exit;

}

if ($ajax == 'defeito_constatado_solucao') {

    if (strlen(trim($defeito_constatado)) > 2) {

        $sql = "SELECT  tbl_linha.linha    ,
                        tbl_familia.familia
                FROM tbl_os
                JOIN tbl_produto USING(produto)
                JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
                JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
                WHERE tbl_os.fabrica = $login_fabrica
                AND   tbl_os.os      = $os";

        $res = pg_query($con,$sql);

        $produto_linha   = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,'linha')   : '';
        $produto_familia = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,'familia') : '';

        $sql = "SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
                       tbl_defeito_constatado.descricao,
                      tbl_defeito_constatado.codigo,
                      tbl_solucao.solucao,
                      tbl_solucao.descricao as solucao_descricao
                 FROM tbl_diagnostico
                 JOIN tbl_defeito_constatado  ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado and tbl_defeito_constatado.ativo <> 'f'
                 JOIN tbl_solucao             ON tbl_diagnostico.solucao            = tbl_solucao.solucao
                WHERE tbl_diagnostico.linha   = $produto_linha
                  AND tbl_diagnostico.familia = $produto_familia
                  AND tbl_diagnostico.ativo   = 't'
                  AND tbl_defeito_constatado.codigo = '$defeito_constatado'
                ORDER BY tbl_defeito_constatado.descricao";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $defeito_constatado = pg_fetch_result($res, 0, 'defeito_constatado');
            $codigo             = pg_fetch_result($res, 0, 'codigo');
            $descricao          = pg_fetch_result($res, 0, 'descricao');
            echo "ok|$defeito_constatado|$codigo|$descricao";

        } else {

            echo "NO|NO $sql";

        }

    } else {

        echo "NO|NO";

    }

    exit;

}

if (strlen($_GET["peca_referencia"]) > 0 AND $_GET["peca_troca"] == "sim") {

    $referencia = trim($_GET["peca_referencia"]);

    $sql  = "SELECT peca
            FROM tbl_peca
            WHERE fabrica = $login_fabrica
            AND   referencia ='$referencia'
            AND   troca_obrigatoria IS TRUE";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        echo "sim";
    }

    exit;

}

$atualiza_serie_trocada = $_GET['atualiza_serie_trocada'];

if (strlen($atualiza_serie_trocada) == 0) {
    $atualiza_serie_trocada = $_POST['atualiza_serie_trocada'];
}

if (strlen($atualiza_serie_trocada) > 0) {

    $os_item        = $_GET["os_item"];
    $serie_trocada  = $_GET["serie_trocada"];

    if (strlen($os_item) > 0 and strlen($serie_trocada) > 0) {

        $sql = "UPDATE tbl_os_item SET peca_serie_trocada ='$serie_trocada'
                WHERE os_item = $os_item";

        $res      = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        if (strlen($msg_erro) == 0) {
            echo "Atualizado com Sucesso!";
        } else {
            echo "Ocorreu o seguinte erro $msg_erro";
        }

    }

    exit;

}

if (strlen($os) > 0 AND strlen($defeito_reclamado) > 0 AND $login_fabrica == 19) {
	$tipo_atend = $_POST['osg'];
	$cond = "";
	if($tipo_atend)
		$cond = ",tipo_atendimento = $tipo_atend";
    $sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado".$cond." WHERE os = $os AND fabrica = $login_fabrica";
	
    $res = pg_query($con,$sql);
}

if (strlen($os) > 0 and $defeito == 'defeito') {

    echo "<h2 style='font-family:Verdana'>Defeito Constatado</h2>";

    if (strlen(trim($defeito_constatado_codigo)) > 1 OR strlen(trim($defeito_constatado_descricao)) > 2 OR $login_fabrica == 43) {

        $sql = "SELECT tbl_linha.linha    ,
                       tbl_familia.familia
                  FROM tbl_os
                  JOIN tbl_produto    USING(produto)
                  JOIN tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
                  JOIN tbl_familia    ON tbl_familia.familia = tbl_produto.familia
                 WHERE tbl_os.fabrica = $login_fabrica
                   AND tbl_os.os      = $os";

        $res = pg_query($con,$sql);
        $produto_linha   = pg_fetch_result($res,0,'linha');
        $produto_familia = pg_fetch_result($res,0,'familia');

        if (strlen($defeito_constatado_codigo) > 0)
            $sql_a1 = " AND  tbl_defeito_constatado.codigo like '%$defeito_constatado_codigo%' ";
        if (strlen($defeito_constatado_descricao) > 0)
            $sql_a1 = " AND  upper(tbl_defeito_constatado.descricao) like upper('%$defeito_constatado_descricao%') ";

        $sql = "SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
                       tbl_defeito_constatado.descricao,
                       tbl_defeito_constatado.codigo
                  FROM tbl_diagnostico
                  JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado and tbl_defeito_constatado.ativo <> 'f'
                 WHERE tbl_diagnostico.linha   = $produto_linha
                   AND tbl_diagnostico.familia = $produto_familia
                   AND tbl_diagnostico.ativo   = 't'
                   AND tbl_defeito_constatado.ativo IS TRUE
                   $sql_a1
                 ORDER BY tbl_defeito_constatado.descricao";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            echo "<table style='font-family:verdana;font-size:12px;'>";
            echo "<tr bgcolor='#336699' style='color:#FFFFFF'>";

            if ($login_fabrica<>43) echo "<Th>Código</th>";

            echo "<th>Descrição</th>";
            echo "</tr>";

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $defeito_constatado = pg_fetch_result($res,$i,'defeito_constatado');
                $codigo             = pg_fetch_result($res,$i,'codigo');
                $descricao          = pg_fetch_result($res,$i,'descricao');

                echo "<tr>";

                if ($login_fabrica<>43) echo "<td><a href=\"javascript: defeito_constatado.value='$defeito_constatado';defeito_constatado_codigo.value='$codigo';defeito_constatado_descricao.value='$descricao';this.close();\">$codigo</a></td>";

                echo "<td><a href=\"javascript: defeito_constatado.value='$defeito_constatado';defeito_constatado_codigo.value='$codigo';defeito_constatado_descricao.value='$descricao';this.close();\">$descricao</a></td>";
                echo "</tr>";

            }

            echo "</table>";

        } else if ($login_fabrica <> 43) {

            echo "<h4 style='color:#FF0000'>Nenhum defeito com o código: $defeito_constatado_codigo</h4>";

        } else {

            echo "<h4 style='color:#FF0000'>Nenhum defeito com a descrição: $defeito_constatado_descricao</h4>";

        }

    } else if ($login_fabrica <> 43) {

        echo "<h4 style='color:#FF0000'>Nenhum defeito com o código: $defeito_constatado_codigo</h4>";

    } else {

        echo "<h4 style='color:#FF0000'>Nenhum defeito com a descrição: $defeito_constatado_descricao</h4>";

    }

    echo "<br><center><a href='javascript:this.close();'>[Fechar]</a></center>";

    exit;

}

$msg_erro     = "";
$msg_previsao = "";

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con,$sql);

$pedir_causa_defeito_os_item       = pg_fetch_result($res, 0, 'pedir_causa_defeito_os_item');
$pedir_defeito_constatado_os_item  = pg_fetch_result($res, 0, 'pedir_defeito_constatado_os_item');
$pedir_defeito_reclamado_descricao = pg_fetch_result($res, 0, 'pedir_defeito_reclamado_descricao');
$ip_fabricante                     = trim (pg_fetch_result($res, 0, 'ip_fabricante'));
$ip_acesso                         = $_SERVER['REMOTE_ADDR'];
$os_item_admin                     = "null";

# AJAX PARA PEGAR SERVICO REALIZADO
if ($login_fabrica == 3 AND isset($_POST['buscaServicoRealizado'])) {
    header('Content-Type: text/html; charset=ISO-8859-1');
    $buscaServicoRealizado = trim($_POST['buscaServicoRealizado']);
    $os                    = trim($_POST['os']);

    if (strlen($buscaServicoRealizado) > 0) {

        if (strlen($os) > 0) {

            if ($buscaServicoRealizado == "073894" OR $buscaServicoRealizado == "073897") {
                $regarca_gas = 1;
            }

        } else {

            $sql = "SELECT  bloqueada_garantia
                    FROM    tbl_peca
                    WHERE   fabrica = $login_fabrica
                    AND referencia  = '$buscaServicoRealizado'
                    AND bloqueada_garantia IS TRUE";

            $res = pg_query($con,$sql) ;

            if (pg_num_rows($res)>0){
                $bloqueada_garantia = pg_fetch_result($res,0,'bloqueada_garantia');
            }

        }

        if (strlen($os) > 0) {

            $sql = "SELECT *
                    FROM   tbl_servico_realizado
                    WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

            if ($regarca_gas == 1) {
                $sql .= "AND tbl_servico_realizado.servico_realizado = 692";
            } else {
                $sql .= "AND tbl_servico_realizado.servico_realizado <> 692";
                $sql .= "AND tbl_servico_realizado.ativo IS TRUE ";
            }

        } else {

            $sql = "SELECT *
                    FROM   tbl_servico_realizado
                    WHERE  tbl_servico_realizado.fabrica = $login_fabrica
                    AND   (tbl_servico_realizado.ativo IS TRUE OR (tbl_servico_realizado.servico_realizado=643 or tbl_servico_realizado.servico_realizado=644) )";

            if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15 and $login_fabrica <> 52) {
                $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
            }

            if ($bloqueada_garantia != 't') {
                $sql .= " AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%' AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%'";
                $bloqueada_garantia = "f";
            }

        }

        $sql .= " ORDER BY descricao ";
        $res = pg_query($con,$sql) ;
        echo $bloqueada_garantia."||";

        for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
            $serv = pg_fetch_result($res,$i,'servico_realizado');
            $desc = pg_fetch_result($res,$i,'descricao');
            echo "<option value='$serv'>";
            echo $desc;
            echo "</option>";
        }

    } else {

        echo "<option value=''>Selecione a peça</option>";

    }

    exit;

}

if (in_array($login_fabrica,array(2,15,30,59,))) {

    $btn_altera         = $_POST['btn_altera'];
    $os_altera          = $_POST['os_altera'];
    $referencia_produto = trim($_POST['referencia_produto']);

    if (strlen($btn_altera) > 0 AND strlen($os_altera) > 0 AND strlen($referencia_produto) > 0) {

        $res = pg_query($con,"BEGIN TRANSACTION");

        $sql = "UPDATE tbl_os SET produto =
                    (
                        SELECT produto
                        FROM tbl_produto
                        JOIN tbl_linha USING (linha)
                        WHERE  tbl_linha.fabrica  = $login_fabrica
                        AND  referencia = '$referencia_produto'
                    )
                WHERE tbl_os.os = $os_altera
                AND   tbl_os.fabrica = $login_fabrica
                AND   tbl_os.defeito_constatado IS NULL
                AND   tbl_os.solucao_os IS NULL ";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (strlen($msg_erro) == 0) {
            $sqlx = "SELECT fn_valida_os($os_altera,$login_fabrica)";
            $res = pg_query($con,$sqlx);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            header("Location: $PHP_SELF?os=$os_altera");
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }

    }

}

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para mostrar a imagem: echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb'></a>
	Para saber se tem anexo: temNF($os, 'bool');
*/
include_once('anexaNF_inc.php');

if (strlen($_GET['reabrir']) > 0) $reabrir = $_GET['reabrir'];
if (strlen($_GET['os']) > 0)      $os      = $_GET['os'];
if (strlen($_POST['os']) > 0)     $os      = $_POST['os'];
if (strlen($_POST['os_int']) > 0) $os      = $_POST['os_int'];

$defeito_reclamado = $_POST['xxdefeito_reclamado'];

$sql = "SELECT  tbl_os.sua_os,
                tbl_os.fabrica,
                tipo_atendimento
        FROM    tbl_os
        WHERE   tbl_os.os = $os";
$res = pg_query($con,$sql) ;

if (pg_num_rows($res) > 0) {
    if (pg_fetch_result($res,0,'fabrica') <> $login_fabrica ) {
        header("Location: os_cadastro.php");
        exit;
    }
}

$tipo_atendimento = pg_fetch_result($res,0,'tipo_atendimento');
$sua_os = trim(pg_fetch_result($res,0,sua_os));

if ($login_fabrica == 14) {

    $imprimir       = $_GET['imprimir'];
    $qtde_etiquetas = $_GET['qtde_etiq'];

    if (strlen($imprimir) > 0 AND strlen($os) > 0) {
        header("Location: os_item.php?os=$os&imprimir=1&qtde_etiq=$qtde_estiquetas");
    } else {
        header("Location: os_item.php?os=$os");
    }

    exit;

}

if ($login_fabrica == 1 AND strlen($os) > 0) {
    header("Location: os_item.php?os=$os");
    exit;
}

if ($login_fabrica == 45) {

    $troca = trim($_GET['troca']);

    if ($troca == "1") {

        $sql = "SELECT tbl_produto.troca_obrigatoria
                  FROM tbl_produto
                  JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
                 WHERE tbl_os.os  = $os
                   AND tbl_os.posto = $login_posto;";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $troca_obrigatoria = trim(pg_fetch_result($res,0,troca_obrigatoria));

            if ($troca_obrigatoria == 't') {

                $sql_intervencao = "SELECT status_os
                                      FROM tbl_os_status
                                     WHERE os = $os
                                       AND status_os IN (62,64,65)
                                     ORDER BY data DESC
                                     LIMIT 1";

                $res_intervencao = pg_query($con, $sql_intervencao);
                $status_os = "";

                if (pg_num_rows($res_intervencao) > 0) {
                    $status_os = trim(pg_fetch_result($res_intervencao,0,status_os));
                }

                if (pg_num_rows($res_intervencao) == 0 or $status_os == "65"){
                    $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
                    $res = pg_query($con,$sql);
                    $msg_intervencao .= "<br>A produto $produto_referencia precisa de Intervenção da Assistência Técnica da Fábrica. Aguarde o contato da fábrica";
                }

                $assunto = "TROCA OBRIGATORIA - OS ITEM NEW - $os - $login_fabrica - $login_posto";
                $corpo   = "OS: $os \n";

            }

        }

    }

}

if ($login_fabrica == 24) {

    $sql = "SELECT current_timestamp-data_digitacao < '00:02:00'
              FROM tbl_os
             WHERE os =  $os ";
    $res = pg_query($con,$sql);
    $verifica = pg_fetch_result($res,0,0);

}

if ($login_fabrica <> 56) {

    if ($verifica == 't' or $login_fabrica <> 24) {
        $sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
        $res1 = pg_query($con,$sql);
    }

}

if (strlen($_GET['os']) > 0) {

    $os = $_GET['os'];
    $sql = "SELECT motivo_atraso ,
                   observacao    ,
                   os_reincidente,
                   obs_reincidencia
              FROM tbl_os
             WHERE os = $os
               AND fabrica = $login_fabrica";

    $res = pg_query($con,$sql);

    $motivo_atraso    = pg_fetch_result($res, 0, 'motivo_atraso');
    $observacao       = pg_fetch_result($res, 0, 'observacao');
    $os_reincidente   = pg_fetch_result($res, 0, 'os_reincidente');
    $obs_reincidencia = pg_fetch_result($res, 0, 'obs_reincidencia');

    if ($login_fabrica == 2) {
        if ($os_reincidente == 't' AND (strlen($obs_reincidencia) == 0))
            header("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
    } else {
        if ($os_reincidente == 't' AND strlen($obs_reincidencia ) == 0)
            header("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
    }

}

if (strlen($reabrir) > 0) {

    $sql = "SELECT count(*)
              FROM tbl_os_item
              JOIN tbl_os_produto USING(os_produto)
              JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
             WHERE os = $os
               AND fabrica = $login_fabrica
               AND tbl_servico_realizado.troca_produto IS TRUE;";

    $res = pg_query($con,$sql);

    if (pg_fetch_result($res,0,0) == 0) {

        $sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
                 WHERE tbl_os.os      = $os
                   AND tbl_os.fabrica = $login_fabrica
                   AND tbl_os.posto   = $login_posto;";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

    } else {

        $msg_erro .= "Esta OS não pode ser reaberta pois a solução foi a troca do produto.";
        echo "<script language='javascript'>alert('Esta os não pode ser reaberta pois o produto foi trocado pela fábrica'); history.go(-1);</script>";
        exit();

    }

}

if ($login_fabrica == 11) {

    $interacao_msg = $_POST['interacao_msg'];

    if (strlen($interacao_msg) > 0) {

        $interacao_exigir_resposta = $_POST['interacao_exigir_resposta'];

        if (strlen($interacao_msg) == 0) {
            $msg_erro.= "Por favor, insira algum comentário.";
        }

        if ($interacao_exigir_resposta <> 't') {
            $interacao_exigir_resposta = 'f';
        }

        if (strlen($msg_erro) == 0) {

            $sql = "INSERT INTO tbl_os_interacao(
                                    os             ,
                                    comentario     ,
                                    exigir_resposta
                                )VALUES(
                                    $os              ,
                                    '$interacao_msg' ,
                                    '$interacao_exigir_resposta'
                                )";

            $res = pg_query($con,$sql);

        }

    }

}

# Fabio 17/01/2007 - verifica o status das OS da britania
# HD 14830 - Fabrica 25
# HD 13618 - Fabrica 45
# HD 12657 - Fabrica 2
if (in_array($login_fabrica,array(2,3,6,11,25,45,50,51))) {

    $sql = "SELECT status_os,observacao
              FROM tbl_os_status
             WHERE os = $os
               AND status_os IN (62,64,65,72,73,87,88,116,117,102,68,70,115,98,99,100,101,102,103,104)
             ORDER BY data DESC LIMIT 1";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

        $status     = pg_fetch_result($res, 0, 'status_os');
        $observacao = pg_fetch_result($res, 0, 'observacao');

        if ($status == '62') {

            if (strpos($observacao, "troca") > 0) {

                $msg_intervencao .= "<b style='color:#FF3333'>OS com intervenção da assistência técnica da Fábrica</b><br><b style='color:#000;font-size:12px'>O produto selecionado deve ser trocado.<br> Selecione o Defeito Constatado e a Solução para continuar</b>";

                header("Location: os_finalizada.php?os=$os");

                exit;

            } else {

                header("Location: os_finalizada.php?os=$os");

                exit;

                //adicionado para digitar constatado e solucao

            }

        }

        if ($status == '65') {

            header("Location: os_press.php?os=$os");
            exit;

        }

        if ($status == '72' or $status == '87' or $status == '116') {

            header("Location: os_finalizada.php?os=$os");
            exit;

        }

        if ($login_fabrica == 50 AND 1==2) { //HD 36253 3/9/2008

            if (in_array($status,array('68','70','115','98','99','100','101','102','103','104'))) {

                $layout_menu = 'os';
                $title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
                include "cabecalho.php";

                $sqlI = "SELECT descricao FROM tbl_status_os WHERE status_os = $status";
                $resI = pg_query($con, $sqlI);
                if (pg_num_rows($resI)>0) $descricao = pg_fetch_result($resI, 0, descricao);
                echo "<br />";
                echo "<FONT SIZE='4' COLOR='#FF3333'>";
                    echo "OS $os em intervenção ( $descricao )<BR> Tela bloqueada para lançamentos de peças.";
                echo "</FONT>";
                exit;

            }

        }

    }

}

if (strlen($_POST['qtde_itens_mostrar']) > 0) $qtde_itens_mostrar = $_POST['qtde_itens_mostrar'];

if ($qtde_itens_mostrar == '' and $login_fabrica == 30) $qtde_itens_mostrar = 20;
//adicionado por Fabio 02/01/2007- numero de itens na OS
if ($login_fabrica <> 30) $qtde_itens_mostrar="";

if (isset($_GET['n_itens']) AND strlen($_GET['n_itens']) > 0) {

    $qtde_itens_mostrar = $_GET['n_itens'];

    if ($login_fabrica <> 15) {

        if ($qtde_itens_mostrar > 10) $qtde_itens_mostrar = 10;
        if ($qtde_itens_mostrar < 0)  $qtde_itens_mostrar = 3;

    } else {

        if (strlen($qtde_itens_mostrar) == 0) {
            $qtde_itens_mostrar = -1;
        }

        switch ($qtde_itens_mostrar) {

            case $qtde_itens_mostrar < 0: 
                $qtde_itens_mostrar = 10;
            break;
            case 20: 
                $qtde_itens_mostrar= 20;
            break;
            case 30: 
                $qtde_itens_mostrar=30;
            break;
            case 40: 
                $qtde_itens_mostrar=40;
            break;

        }

        //echo $qtde_itens_mostrar;

    }

} else if($login_fabrica <> 30) {

    $qtde_itens_mostrar = 3;

    /*  HD 13498 9/2/2008
    A pedido da Dalila foi aumentada a quantidade de linhas para este posto*/
    /* Adicionado posto 13979 - HD 14278 */
    if ($login_fabrica == 45) {
        $sql= "SELECT qtde_os_item
                 FROM tbl_posto_fabrica
                WHERE posto   = $login_posto
                  AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $qtde_itens_mostrar = pg_fetch_result($res, 0, 'qtde_os_item');
    }

    if ($login_fabrica == 15 ) {
        $qtde_itens_mostrar = 40;
    }

}

$numero_pecas_faturadas = 0;
// fim do numero de linhas - Fabio 02/01/2007

//modificado por Fernando 02/08/2006 - Exclusao do item na OS qdo o mesmo estiver abaixo dos 30%.
//verifica se tem os_item amarrado na os_produto se nao tiver ele apaga os_produto.

$os_item = trim($_GET ['os_item']);

if ($os_item > 0) {

    if ($os_item_old != $os_item) {

        $os_item_old = $os_item;
        //seleciona a os_produto que contem a os_item quem não geraam pedido
        $sql = "SELECT os_produto FROM tbl_os_item WHERE os_item = $os_item AND pedido IS NULL";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 1) {

            $os_produto = pg_fetch_result($res, 0, 'os_produto');

            #HD 15489
            $sql = "UPDATE tbl_os_produto SET
                        os = 4836000
                    WHERE os_produto = $os_produto";
            $res = pg_query($con,$sql);

        } else {

            $msg_erro_item .= "Não foi encontrado o item.";

        }

    } else {

        $msg_erro_item .= "Não foi encontrado o item.";

    }

}

$btn_acao     = strtolower ($_POST['btn_acao']);
$btn_imprimir = strtolower ($_POST['btn_imprimir']);

if ((($login_fabrica==3 or $login_fabrica == 45 or $login_fabrica == 24) AND $login_posto==6359) or ($login_fabrica == 46 AND $login_posto==6359)){
    $query = "    SELECT orcamento
                FROM tbl_orcamento
                WHERE empresa = $login_fabrica
                AND   os      = $os";
    $orca = pg_query($con, $query);
    if(pg_num_rows($orca)>0){
        $orcamento = pg_fetch_result($orca,0,orcamento);
    }
}

if ($login_fabrica==3 AND $login_posto==6359){
    $query = "    SELECT os_revenda_item, os_revenda
                FROM tbl_os_revenda_item
                WHERE os_lote = $os";
    $orca = pg_query($con, $query);
    if(pg_num_rows($orca)>0){
        $os_revenda_item = pg_fetch_result($orca,0,os_revenda_item);
        $os_revenda      = pg_fetch_result($orca,0,os_revenda);
    }
}

if ($btn_acao == "gravar") {

	if ($anexaNotaFiscal and !$msg_erro) {
		if (is_array($_FILES['foto_nf']) and $_FILES['foto_nf']['name'] != '') {
			$anexou = anexaNF($os, $_FILES['foto_nf']);
			if ($anexou !== 0 and $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria']==true) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
			if ($anexou == 0 AND $login_fabrica == 72){
				$sql = "SELECT status_os, admin FROM tbl_os_status WHERE tbl_os_status.os = $os AND status_os = 154 ORDER BY data DESC LIMIT 1";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res) == 1){
					$status_os = pg_fetch_result($res,0,'status_os');
					$admin = pg_fetch_result($res,0,'admin');

					if($status_os == 154){
						$sql = "SELECT email, nome_completo FROM tbl_admin WHERE admin = {$admin};";
						$res = pg_query($con, $sql);
						$email_admin = pg_fetch_result($res,0,'email');
						$nome_completo_admin = pg_fetch_result($res,0,'nome_completo');

						if($email_admin != ""){
							$remetente    = "Suporte <helpdesk@telecontrol.com.br>";
							$destinatario = $email_admin;
							$assunto      = "Anexada Nota Fiscal à OS {$os}\n";
							$mensagem     = "Prezado(a) {$nome_completo_admin}\n";
							$mensagem    .="<br /><br />Foi anexada uma imagem de nota fiscal referente à OS {$os}\n";
							$mensagem    .="<br /><br />----------\n";
							$mensagem    .="<br />Atenciosamente,\n";
							$mensagem    .="<br />Suporte Telecontrol\n";
							$mensagem    .="<br />www.telecontrol.com.br\n";
							$mensagem    .="<br /><b>Esta é uma mensagem automática, não responda este e-mail.</b>\n";
							$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
							mail($destinatario,$assunto,$mensagem,$headers);
						}
					}

				}
			}
		}
	}
	//  FIM Anexa imagem NF

    $res = pg_query($con,"BEGIN TRANSACTION");

    $defeito_constatado       = $_POST ['defeito_constatado'];
    $data_fechamento          = $_POST['data_fechamento'];
    $defeito_constatado_grupo = $_POST['defeito_constatado_grupo'];

    if (strlen($data_fechamento) > 0) {
        $xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
        if ($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro.= "Data de fechamento maior que a data de hoje.";
    }

    if (strlen($data_fechamento) > 0) {
        $xdata_fechamento = fnc_formata_data_pg($data_fechamento);
        if ($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro.= "Data de fechamento maior que a data de hoje.";
    }

    $sql = "SELECT consumidor_revenda from tbl_os where os = $os";
    $res = pg_query($con,$sql);
    $consumidor_revenda = pg_fetch_result($res, 0, 'consumidor_revenda');

// HD 350051 - Obrigatoriedade para as que exigem imagem da NF.
    if ($anexaNotaFiscal and !temNF($os, 'bool') and 
        (($login_fabrica == 43 and $consumidor_revenda == 'C') or // HD 354997 - ImgNF obrig. para 43 só OS Consumidor
         ($login_fabrica != 43 and $login_fabrica <> 72 and $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] == true))) {
	 	$msg_erro .= "Não pode ser gravada a OS sem que haja uma imagem da Nota Fiscal.";
	}

    //Samuel 18-08 a pedido do Fabricio da Britania o campo Defeito constatado e solucao passam a ser obrigatorios
    //HD 206869: Exigir digitação de defeito constatado
    if (($login_fabrica == 3) or ($login_fabrica == 6) or ($login_fabrica == 24) or ($login_fabrica == 45) or ($login_fabrica == 81) or ($login_fabrica == 42)) {

        if (strlen($defeito_constatado)==0) {
            $msg_erro .= "Por favor preencher o campo defeito constatado.<br />";
        }

        if ($login_fabrica <> 45 and $login_fabrica <> 81 and $login_fabrica <> 42 and $login_fabrica <> 74) {

            if (strlen($solucao_os) == 0) {
                $msg_erro .= "Por favor preencher o campo solução.<BR>";
            }

        }

    }

    if ($login_fabrica == 30) {

        $aux_defeito_constatado_codigo    = $_POST['defeito_constatado_codigo'];
        $aux_defeito_constatado_descricao = $_POST['defeito_constatado_descricao'];
        $qtde_integridade                 = $_POST['qtde_integridade'];

        for ($f = 1; $f <= $qtde_integridade; $f++) {
            $integridade_defeito_constatado .= $_POST['integridade_defeito_constatado_'.$f] . ';';
            $integridade_defeito_descricao  .= $_POST['integridade_defeito_descricao_'.$f] . ';';
        }

    }

    if ($login_fabrica == 15 OR $login_fabrica == 24 OR $login_fabrica == 30 OR $login_fabrica == 52 OR $login_fabrica == 85) {

        if (isset($_POST['produto_serie'])) {
            $produto_serie = trim($_POST['produto_serie']);
        }
		
		if ($login_fabrica == 15){
			$sql = "SELECT tbl_produto.numero_serie_obrigatorio FROM tbl_os JOIN tbl_produto using(produto) WHERE os = $os";
			$res = pg_query($con,$sql);
			$numero_serie_obrigatorio = pg_fetch_result($res, 0, 'numero_serie_obrigatorio');
			
			if(strlen($produto_serie) == 0 && $numero_serie_obrigatorio == 't'){
				$msg_erro .= 'Número de Série Inválido';
			}
			
		}

        if ($login_fabrica == 30) {
            $xproduto_serie = $produto_serie;
            //if (strlen($produto_serie)==0){
            //    $msg_erro .='Por Favor digite o Número de Série';
            //}

			#HD 276459 visita_agendada

			$visita_agendada = $_POST['visita_agendada'];
			$cobrar_visita = $_POST['cobrar_visita'];
			
			if ($cobrar_visita=='sim') {
				if (strlen($visita_agendada)>0) {
					 $sql = "UPDATE tbl_os
								SET visita_agendada = '$visita_agendada'
							 WHERE os     = $os
							   AND posto  = $login_posto";

					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				} else {
					$msg_erro .= "<br>Erro ao gravar agendamento verifique data";
				}
			}
		}
        /* HD 21977 */
        /* retirado igor 14/07/2008*/
        /*
        if ($login_fabrica==30 and isset($_POST['produto_serie']) and strlen($produto_serie)==0){
            $msg_erro .= "O número de série é obrigatório.";
        }
        */
		
        if (strlen($msg_erro) == 0 AND isset($_POST['produto_serie'])) {

            $sql = "UPDATE tbl_os
                        SET serie = '$produto_serie'
                     WHERE os     = $os
                       AND posto  = $login_posto";

            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT current_timestamp - data_digitacao < '00:05:00'
                      FROM tbl_os
                     WHERE os= $os ";

            $res      = pg_query($con,$sql);
            $verifica = pg_fetch_result($res,0,0);

            if ($verifica == 't') {
                $sql  = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
                $res1 = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }

        }

    }

    if ($login_fabrica == 58 or $login_fabrica == 46 or $login_fabrica == 19) {

        $sql = "SELECT produto, familia, linha FROM tbl_produto JOIN tbl_os using(produto) WHERE os = $os";
        $res = pg_query($con,$sql);

        $laudo_produto = pg_fetch_result($res, 0, 'produto');
        $laudo_familia = pg_fetch_result($res, 0, 'familia');
        $laudo_linha   = pg_fetch_result($res, 0, 'linha');

        if ($login_fabrica <> 19) {

            $sql = "SELECT *
                      FROM tbl_laudo_tecnico
                     WHERE tbl_laudo_tecnico.produto = $laudo_produto";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res) == 0) {

                $sql = "SELECT *
                          FROM tbl_laudo_tecnico
                         WHERE tbl_laudo_tecnico.familia = $laudo_familia";

                $res = pg_query($con,$sql);

            }

        } else {

            $sql = "SELECT *
                    FROM tbl_laudo_tecnico
                    WHERE tbl_laudo_tecnico.linha = $laudo_linha";

            $res = pg_query($con,$sql);

        }

        if ($login_fabrica == 19) {
            $digitou_laudo = $_POST['digitou_laudo'];
        }

        if (pg_num_rows($res) > 0 and $digitou_laudo == 'n') {

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $laudo      = pg_fetch_result($res, $i, 'laudo_tecnico');
                $titulo     = pg_fetch_result($res, $i, 'titulo');
                $afirmativa = pg_fetch_result($res, $i, 'afirmativa');
                $observacao = pg_fetch_result($res, $i, 'observacao');

                if (strlen($msg_erro) == 0) {

                    $laudo_ordem = trim($_POST["ordem_$laudo"]);

                    if ($afirmativa == 't') {

                        $laudo_afirmativa = trim($_POST["afirmativa_$laudo"]);

                        if (strlen($laudo_afirmativa) == 0) {
                            $msg_erro.= "Por favor, complete o Laudo Técnico.";
                        } else {
                            $laudo_afirmativa = "'".trim($_POST["afirmativa_$laudo"])."'";
                        }

                    } else {

                        $laudo_afirmativa = 'null';

                    }

                    if ($observacao == 't') {

                        $laudo_observacao = trim($_POST["observacao_$laudo"]);

                        if (strlen($laudo_observacao) == 0) {

                            $msg_erro.= "Por favor, complete o Laudo Técnico.";

                        }

                    } else {

                        $laudo_observacao = '';

                    }

                    if (strlen($msg_erro) == 0) {

                        $sql2 = "INSERT INTO tbl_laudo_tecnico_os (titulo        ,
                                                                   os          ,
                                                                   afirmativa  ,
                                                                   observacao  ,
                                                                   ordem
                                                                  ) VALUES (
                                                                   '$titulo'          ,
                                                                   '$os'              ,
                                                                   $laudo_afirmativa,
                                                                   '$laudo_observacao',
                                                                   $laudo_ordem
                                                                  )";

                        $res2 = pg_query($con,$sql2);
                        $msg_erro .= pg_errormessage($con);

                    }

                }

            }

        }

    }

    //para a fabrica 11 é obrigatório aparencia_produto e acessorios, para as outras é mostrado na tela /os_cadastro.php
    if ($login_fabrica == 11) {
        //APARENCIA
        if (strlen(trim($aparencia_produto)) == 0) {
            $aparencia_produto = 'null';
            $msg_erro .= "Informar a Aparência do Produto.<BR>";
        } else {
            $aparencia_produto= "'".trim($aparencia_produto)."'";
            $sql = "UPDATE tbl_os SET aparencia_produto = $aparencia_produto
                WHERE  tbl_os.os    = $os
                AND    tbl_os.posto = $login_posto;";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }

        //ACESSORIOS
        if (strlen(trim($acessorios)) == 0) {

            $acessorios = 'null';
            $msg_erro .= "Informar os Acessórios do produto.<BR>";

        } else {

            $acessorios= "'".trim($acessorios)."'";

            $sql = "UPDATE tbl_os
                       SET acessorios   = $acessorios
                     WHERE tbl_os.os    = $os
                       AND tbl_os.posto = $login_posto;";

            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

        }

    }

    if (strlen($msg_erro) == 0) {

        if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

        //Rotina de vários defeitos para uma única OS.
        if (in_array($login_fabrica,array(30,43,59,2,85))) {

            $numero_vezes      = 100;
            $array_integridade = array();

            for ($i = 0; $i < $numero_vezes; $i++) {

                $int_constatado = trim($_POST["integridade_defeito_constatado_$i"]);
                $int_solucao    = trim($_POST["integridade_solucao_$i"]);
                $principal      = trim($_POST["principal"]);

                if (!isset($_POST["integridade_defeito_constatado_$i"])) continue;
                if (strlen($int_constatado) == 0) continue;

                $aux_defeito_constatado = $int_constatado;
                $aux_solucao            = $int_solucao;

                array_push($array_integridade, $aux_defeito_constatado);

                if (in_array($login_fabrica,array(43,59,2,85))) {
					# HD 309680
                    if (strlen($aux_solucao) > 0) {//HD 79185

                        $sql = "SELECT defeito_constatado_reclamado
                                FROM   tbl_os_defeito_reclamado_constatado
                                WHERE  os=$os
                                AND    defeito_constatado = $aux_defeito_constatado
                                AND    solucao            = $aux_solucao";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                    }

                    if (pg_num_rows($res) == 0) {

                        if (strlen($msg_erro) == 0) {

                            if (strlen($aux_solucao) > 0) {

                                $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                            os,
                                            defeito_constatado,
                                            solucao
                                        ) VALUES (
                                            $os,
                                            $aux_defeito_constatado,
                                            $aux_solucao
                                        )";

                                $res = pg_query($con,$sql);
                                $msg_erro .= pg_errormessage($con);

                            }

                        }

                    }

                } else {

                    $sql = "SELECT defeito_constatado_reclamado
                              FROM tbl_os_defeito_reclamado_constatado
                             WHERE os = $os
                               AND defeito_constatado = $aux_defeito_constatado";

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if (pg_num_rows($res) == 0) {

                        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                    os,
                                    defeito_constatado
                                ) VALUES (
                                    $os,
                                    $aux_defeito_constatado
                                )";

                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                    }

                }

            }

            if (strlen($msg_erro) == 0) {//HD 79185

                $lista_defeitos = implode($array_integridade,",");

                if (strlen($lista_defeitos) > 0) {

                    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
                             WHERE os = $os
                               AND defeito_constatado NOT IN ($lista_defeitos)";

                    $res = pg_query($con,$sql);

                    if (strlen(pg_errormessage($con)) > 0) {
                        $msg_erro .= "<br>É necessário clicar no botão Adicionar Defeito!<br>";
                    }

                } else {

                    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
                            WHERE os = $os";

                    $res = pg_query($con,$sql);
                    $msg_erro.="É necessário clicar no botão Adicionar Defeito!";

                }

            }

            //o defeito constatado recebe o primeiro defeito constatado.
            $defeito_constatado = $aux_defeito_constatado;
            // HD 41052
            if ($login_fabrica == 43 and strlen($principal) > 0) $defeito_constatado = $principal;


        }

        if ($login_fabrica == 19) {

            $numero_vezes_i = 100;
            $numero_vezes_j = 100;

            #Apaga todos os defeitos reclamados e constatados
            $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE os=$os";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            for ($i = 0; $i < $numero_vezes_i; $i++) {

                $int_reclamado = trim($_POST["defeito_reclamado_$i"]);

                if (!isset($_POST["defeito_reclamado_$i"])) continue;
                if (strlen($int_reclamado) == 0)            continue;

                $aux_defeito_reclamado = $int_reclamado;

                if ($aux_defeito_reclamado <> 0) {

                    #Insere todos os defeitos reclamados
                    $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                os                    ,
                                defeito_reclamado
                            ) VALUES (
                                $os                   ,
                                $aux_defeito_reclamado
                            )";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                }

                for ($j = 0; $j < $numero_vezes_j; $j++) {

                    $int_constatado = trim($_POST["i_defeito_constatado_".$i."_".$j]);

                    if (!isset($_POST["i_defeito_constatado_".$i."_".$j])) continue;
                    if (strlen($int_constatado) == 0)                      continue;

                    $aux_defeito_constatado = $int_constatado;
                    $defeito_constatado     = $int_defeito_constatado;

                    if ($aux_defeito_reclamado == 0) $aux_defeito_reclamado = "NULL";

                    $sql = "SELECT defeito_constatado_reclamado
                              FROM tbl_os_defeito_reclamado_constatado
                             WHERE os                 = $os
                               AND defeito_reclamado  = $aux_defeito_reclamado
                               AND defeito_constatado = $aux_defeito_constatado";

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if (pg_num_rows($res) == 0) {

                        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                    os,
                                    defeito_constatado,
                                    defeito_reclamado
                                )VALUES(
                                    $os,
                                    $aux_defeito_constatado,
                                    $aux_defeito_reclamado

                                )";

                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
                                 WHERE os                 = $os
                                   AND defeito_reclamado  = $aux_defeito_reclamado
                                   AND defeito_constatado IS NULL";

                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                        
                    }

                }

            }

        }

        if ($login_fabrica == 19) {

            # HD 28155
            if ($tipo_atendimento <> 6) {

                $sql = "SELECT defeito_constatado
                          FROM tbl_os_defeito_reclamado_constatado
                         WHERE os = $os LIMIT 1";

                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $defeito_constatado = pg_fetch_result($res,0,0);
                } else {
                    $msg_erro.= "É necessário informar o defeito constatado";
                }

            }
        }

        if ($login_fabrica == 52) {

            if (strlen($msg_erro) == 0) {

                if (strlen($defeito_constatado_grupo) > 0) {

                    $sql = "UPDATE tbl_os
                               SET defeito_constatado_grupo = $defeito_constatado_grupo
                             WHERE tbl_os.os                = $os
                               AND tbl_os.posto             = $login_posto;";

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                }

            }

        }

        if (strlen($msg_erro) == 0) {

            if (strlen($defeito_constatado) > 0) {

                $sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
                        WHERE  tbl_os.os    = $os
                        AND    tbl_os.posto = $login_posto;";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            }

        }
        //CASO DEFEITO RECLAMADO ESTEJA VAZIO

        $defeito_reclamado = $_POST['defeito_reclamado'];
        if ($pedir_defeito_reclamado_descricao == 't') {
            if (strlen($defeito_reclamado) == 0) {
                $defeito_reclamado = 'null';
            }
        }

        if (strlen($defeito_reclamado) == 0 ) $msg_erro.= "Informe o defeito reclamado.";

        if (strlen($msg_erro) == 0) {

            if (strlen($defeito_reclamado) > 0) {

                $sql = "UPDATE tbl_os
                           SET defeito_reclamado = $defeito_reclamado
                         WHERE tbl_os.os         = $os
                           AND tbl_os.posto      = $login_posto;";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            }

        }//CASO DEFEITO RECLAMADO ESTEJA VAZIO

    }

	//hd 351696 Início
	if($login_fabrica == 42 || $login_fabrica == 74){
		$xdefeito_raclamado = $_POST['xxdefeito_reclamado'];
		$defeito_reclamado_descricao_os = $_POST['defeito_reclamado_descricao_os'];

		if(strlen($xdefeito_raclamado) == 0 and strlen($defeito_reclamado_descricao_os) == 0){
			$msg_erro = 'Informe o Defeito Reclamado pelo Cliente <br />';
		}
	}
	//hd 351696 Fim

	if($login_fabrica == 96) { 	# HD 390996
		$total_orcamento = $_POST['total_orcamento'];
		$total_horas     = $_POST['total_horas'];
		$total_orcamento = str_replace (",",".",$total_orcamento);
		$total_horas     = str_replace (",",".",$total_horas);
		
		if(!empty($total_orcamento) and empty($total_horas)) {
			$msg_erro = "Por favor, informe total de horas ";
		}

		if(empty($total_orcamento) and !empty($total_horas)) {
			$msg_erro = "Por favor, informe total do orçamento";
		}

		if(!empty($total_orcamento) and !empty($total_horas)) {
			$sql = " SELECT os
					FROM tbl_orcamento_os_fabrica
					WHERE os = $os";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$sql = " UPDATE tbl_orcamento_os_fabrica SET	
							total           = $total_orcamento,
							total_horas     = $total_horas
						WHERE os = $os";
			}else{
				$sql = " INSERT INTO tbl_orcamento_os_fabrica(
								os,
								fabrica,
								total,
								total_horas			
							)VALUES(
								$os,
								$login_fabrica,
								$total_orcamento,
								$total_horas
							)";
			}
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			
		}
	}

    if (strlen($msg_erro) == 0) {
        $xcausa_defeito = $_POST ['causa_defeito'];
        if (strlen($xcausa_defeito) == 0) $xcausa_defeito = "null";
        if (strlen($xcausa_defeito) > 0) {
            $sql = "UPDATE tbl_os SET causa_defeito = $xcausa_defeito
                    WHERE  tbl_os.os    = $os
                    AND    tbl_os.posto = $login_posto;";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }
    }

    if (strlen($msg_erro) == 0) {
        $x_solucao_os = $_POST['solucao_os'];
        if (strlen($x_solucao_os) == 0) $x_solucao_os = 'null';
        else                            $x_solucao_os = "'".$x_solucao_os."'";
        $sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
                WHERE  tbl_os.os    = $os
                AND    tbl_os.posto = $login_posto;";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }

    $obs = trim($_POST["obs"]);
    if (strlen($obs) > 0) $obs = "'".$obs."'";
    else                   $obs = "null";
    //takashi 07-08 a pedido do andre da tectoy o campo observação passa a ser obrigatorio
    if ($login_fabrica == 6) {
        if (strlen($obs) == 0) {
            $msg_erro .= "Por favor preencher o campo Observação<BR>";
        }
    }
    //takashi 07-08 a pedido do andre da tectoy o campo observação passa a ser obrigatorio

    $tecnico_nome = trim($_POST["tecnico_nome"]);
    if (strlen($tecnico_nome) > 0) {
        $tecnico_nome = "'".$tecnico_nome."'";
    } else {
        if ($login_fabrica == 30) { # 136812
            $msg_erro.= "Por favor, informe o nome do técnico";
        } else {
            $tecnico_nome = "null";
        }
    }

    $tecnico = trim($_POST["tecnico"]);

    if (strlen($tecnico)) {

        $sql = "SELECT tecnico FROM tbl_tecnico WHERE tecnico = $tecnico AND fabrica = $login_fabrica AND posto = $login_posto";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            $msg_erro.= "O técnico informado não está cadastrado";
        }

    } else {

        $tecnico = "null";

    }

    if ($login_fabrica == 19) {

        $sql = "SELECT linha
                  FROM tbl_produto
                  JOIN tbl_os USING(produto)
                 WHERE os = $os";
        $res    = pg_query($con,$sql);
        $linhax = pg_fetch_result($res,0,0);

    }

    if (($login_fabrica == 19) and (/* HD 51526 - liberado para todos os postos
                                        (($login_posto == 14068) or ($login_posto == 6359)) and */
        $linhax == 260)) {

        $fabricacao_produto = trim($_POST["fabricacao_produto"]);

        if (strlen($fabricacao_produto) == 0) {

            $msg_erro .= "Por favor, preencha o campo Mês e Ano de Fabricação do Produto<br/>";

        } else {

            if (strlen($fabricacao_produto) <> 7) {
                $msg_erro .= "Mês e ano inválidos! Verifique os dados digitados.<br/>";
            }

            if (!is_numeric(substr($fabricacao_produto,0,2)) or substr($fabricacao_produto,0,2) < 1 or substr($fabricacao_produto,0,2) > 12) {
                $msg_erro .= "Mês e ano inválidos! Verifique os dados digitados.<br/>";
            }

            if (substr($fabricacao_produto,2,1) <> "/") {
                $msg_erro .= "Mês e ano inválidos! Verifique os dados digitados.<br/>";
            }

            $sql = "SELECT TO_CHAR (current_date,'YYYY')";
            $res = pg_query($con,$sql);
            $ano_atual = pg_fetch_result($res,0,0);

            if (!is_numeric(substr($fabricacao_produto,3,4)) or substr($fabricacao_produto,3,4) > $ano_atual) {
                $msg_erro .= "Mês e ano inválidos! Verifique os dados digitados.<br/>";
            }

        }

        //hd 47311
    }

    //DEFEITO RECLAMADO COMO CAMPO TEXTO
    if (($pedir_defeito_reclamado_descricao == 't') AND $defeito_reclamado == 'null') {

        $defeito_reclamado_descricao_os = $_POST["defeito_reclamado_descricao_os"];

        if (strlen($defeito_reclamado_descricao_os) == 0) {
            $msg_erro.= "Por favor, preencha o campo do defeito reclamado.";
        }

    } else if($login_fabrica <> 42 and $login_fabrica <> 96) {//PARA OUTRAS FÁBRICAS SETA NULL
        $defeito_reclamado_descricao_os = 'null';
    }

    $valores_adicionais = trim($_POST["valores_adicionais"]);
    $valores_adicionais = str_replace (",",".",$valores_adicionais);

    if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

    $justificativa_adicionais = trim($_POST["justificativa_adicionais"]);

    if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
    else                                       $justificativa_adicionais = "null";

    if (strlen($type) > 0) $type = "'".trim($_POST['type'])."'";
    else                   $type = 'null';

    $qtde_km = trim($_POST["qtde_km"]);
    $qtde_km = str_replace (",",".",$qtde_km);

    if ($login_fabrica == 3) {
        if (strlen($qtde_km) == 0) $qtde_km = "0";
    } else {
        if (strlen($qtde_km) == 0) $qtde_km = " qtde_km ";
    }

    //HD 20862 20/6/2008
    if ($login_fabrica == 3) {

        if (strlen($_POST['atendimento_domicilio']) == 0) {
            $tipo_atendimento = ' NULL ';
        } else {
            $tipo_atendimento = '37';
        }

    }

    //HD 20682 20/6/2008
    if ($login_fabrica == 3 AND $tipo_atendimento == 37) {

        $status_os = trim($_POST["status_os"]);
        //if (strlen($status_os)==0 or $status_os=="101"){

        $sql_atendimento = " tipo_atendimento = $tipo_atendimento, ";

        //hd 24288
        //if (strlen($_POST["autorizacao_domicilio"])>0) $autorizacao_domicilio = trim($_POST["autorizacao_domicilio"]);
        //else                                          $autorizacao_domicilio = "NULL";

        if (strlen(trim($_POST["justificativa_autorizacao"]))>0) $justificativa_autorizacao = trim($_POST["justificativa_autorizacao"]);
        else                                                     $justificativa_autorizacao = "NULL";

        if ($qtde_km == "0") {
            $msg_erro .= "Informe o campo Kilometragem";
        }

        //hd 24288
        //if($autorizacao_domicilio == "NULL"){
        //    $msg_erro .= "Informe o campo Número de Autorização";
        //}

        if ($justificativa_autorizacao == "NULL") {
            $msg_erro .= "Informe o campo Justificativa da Kilometragem";
        }
        //}

    } else {
        //hd 24288
        //$autorizacao_domicilio     = "NULL";
        $justificativa_autorizacao = "NULL";
    }

    if ($login_fabrica == 3 AND $status_os == "101" AND $tipo_atendimento != 37) {
        $sql_atendimento = " tipo_atendimento = $tipo_atendimento, ";
    }

    if (strlen($msg_erro) == 0) {

        if ($login_fabrica == 3 AND $status_os <> "99" AND (strlen($status_os) == 0 or $status_os == "101")) {//HD 20682 20/6/2008
            $sql_dom_britania = " /*autorizacao_domicilio = $autorizacao_domicilio,*/
                                  qtde_km               = $qtde_km              ,";
        } else if($login_fabrica <> 3) {
            $sql_dom_britania = " qtde_km               = $qtde_km                ,";
        }

        $sql = "UPDATE  tbl_os SET obs              = $obs                             ,
                        tecnico_nome                = $tecnico_nome                    ,
                        tecnico                     = $tecnico                         ,
                        codigo_fabricacao           = '$codigo_fabricacao'             ,
                        fabricacao_produto          = '$fabricacao_produto'            ,
                        valores_adicionais          = $valores_adicionais              ,
                        justificativa_adicionais    = $justificativa_adicionais        ,";
					if($login_fabrica <> 96){
						$sql .= "defeito_reclamado_descricao = '$defeito_reclamado_descricao_os',";
					}
					$sql .="
                        $sql_atendimento
                        $sql_dom_britania
                        type                        = $type
                WHERE  tbl_os.os    = $os
                AND    tbl_os.posto = $login_posto;";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if ($login_fabrica == 3  AND $tipo_atendimento == 37 AND (strlen($status_os) == 0 or $status_os == "101")) {//HD 20682 20/6/2008

            $sql = "INSERT INTO tbl_os_status(
                        os,
                        status_os,
                        data,
                        observacao
                        )VALUES(
                        $os,
                        98 ,
                        current_timestamp,
                        '$justificativa_autorizacao');";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

        }

    }

    if (strlen($msg_erro) == 0) { //HD 79185
        #HD 13618
        $sqlT = "SELECT tbl_produto.troca_obrigatoria
                FROM   tbl_produto
                JOIN   tbl_os ON tbl_os.produto = tbl_produto.produto
                WHERE  tbl_os.os    = $os
                AND    tbl_os.posto = $login_posto;";
        $resT = pg_query($con,$sqlT);
        if (pg_num_rows($resT) > 0) {
            $troca_obrigatoria = pg_fetch_result($resT,0,troca_obrigatoria);
        }
    }

    $informatica = trim($_POST["informatica"]);

    #HD 101357
    if($login_fabrica == 3){
        $linha_p = $_POST[linha_envia];
        if($linha_p==528){
            $informatica = 't';
        }
    }

    $orcamento_garantia = "";

    if (strlen($msg_erro) == 0) {

        $qtde_item = $_POST['qtde_item'];

        for ($i = 0 ; $i < $qtde_item ; $i++) {
            $xos_item        = $_POST['os_item_'        . $i];
            $xorcamento_item = $_POST['orcamento_item_' . $i];
            $xos_produto     = $_POST['os_produto_'     . $i];
            $xproduto        = $_POST['produto_'        . $i];
            $xserie          = $_POST['serie_'          . $i];
            $xposicao        = $_POST['posicao_'        . $i];
            $xpeca           = $_POST['peca_'           . $i];
            $xqtde           = $_POST['qtde_'           . $i];
            $xdefeito        = $_POST['defeito_'        . $i];
            $xservico        = $_POST['servico_'        . $i];
            $xpeca_serie     = $_POST['peca_serie_'     . $i];
            $xpeca_serie_trocada = $_POST['peca_serie_trocada_' . $i];
            $xpcausa_defeito = $_POST['pcausa_defeito_' . $i];
            $xreparo_estoque = $_POST['reparo_estoque_' . $i];
            $xkit_peca       = $_POST['kit_kit_peca_' . $i];
            $descricao       = $_POST['descricao_' . $i];

            /* HD COLORMAQ 153132*/
            if ($login_fabrica == 50 and strlen($xproduto) > 0 and strlen($xpeca) > 0) {
                $sqlqtdemultipla = "SELECT qtde FROM tbl_lista_basica
                JOIN    tbl_peca using(peca)
                WHERE    peca = (SELECT peca FROM tbl_peca WHERE referencia = '$xpeca')
                AND produto = (SELECT produto FROM tbl_produto WHERE referencia = '$xproduto') AND multiplo = qtde;";
                $resqtdemultipla = pg_query($con,$sqlqtdemultipla);
                if (pg_num_rows($resqtdemultipla) > 0) {
                    $xqtde = pg_fetch_result($resqtdemultipla,0,qtde);
                } else {
                    $xqtde = "1";
                }

            }
            /* */
            /* HD 202030 - dupla verificação javascript e php -Samuel 2010-02-03*/
            if ($login_fabrica == 50 and strlen($xdefeito) > 0 and strlen($xpeca) > 0) {

                $sqlD = "SELECT     tbl_defeito.descricao                   , 
                                tbl_defeito.defeito                     , 
                                tbl_defeito.codigo_defeito              ,
                                tbl_peca_defeito.ativo
                        FROM  tbl_peca_defeito 
                        JOIN  tbl_defeito using(defeito)
                        JOIN  tbl_peca on tbl_peca.peca = tbl_peca_defeito.peca 
                        AND   tbl_peca.fabrica = $login_fabrica
                        AND   tbl_peca.referencia = '$xpeca'
                        WHERE tbl_peca_defeito.ativo = 't' 
                        AND   tbl_defeito.ativo = 't' 
                        AND   tbl_defeito.defeito = $xdefeito";

                $resD = pg_exec($con,$sqlD);

                if (pg_num_rows($resD) <> 1) {
                    if($login_fabrica == 50){
						$msg_erro .= "Sem Defeitos Cadastrados, Contate o Fabricante!";
					}else{
						$msg_erro .= "Você deve escolher o defeito da Peça novamente!";
					}
                }

            }

            //HD 107982 2009-07-20
            if ($login_fabrica == 24) {

                if ($xreparo_estoque=="peca_reposicao_estoque") {
                    $xpeca_reposicao_estoque = "'t'";
                } else {
                    $xpeca_reposicao_estoque = "'f'";
                }

                if ($xreparo_estoque == "aguardando_peca_reparo") {
                    $xaguardando_peca_reparo = "'t'";
                } else {
                    $xaguardando_peca_reparo = "'f'";
                }

                // HD 170216
                if ($xservico == '504' and $xaguardando_peca_reparo == "'f'" and $xpeca_reposicao_estoque == "'f'") {
                    $msg_erro.= "Para este serviço, é obrigado selecionar se é Repor Estoque ou Aguardando peça para reparo";
                }

            } else {

                $xpeca_reposicao_estoque = "'f'";
                $xaguardando_peca_reparo = "'f'";

            }

            $xpreco_orcamento = $_POST['preco_orcamento_' . $i];
            $xpreco_orcamento = $xpreco_orcamento;

            $xpreco_venda_orcamento = $_POST['preco_venda_orcamento_' . $i];
            $xpreco_venda_orcamento = $xpreco_venda_orcamento;

            $xpreco_orcamento = str_replace ("," , "." ,$xpreco_orcamento);

            $xpreco_venda_orcamento = str_replace ("," , "." ,$xpreco_venda_orcamento);

            if ($xservico <> 643 AND $xservico<> 644 AND $xservico <> 4247 AND $xservico <> 4289){
                $xpreco_orcamento = "";
            }

            $xproduto = $xproduto;

            /*IGOR - hd: 45924 13/10/2008*/
            if ($login_fabrica <> 50) {
                $xpeca    = $xpeca;
            }

            $xserie = (strlen($xserie) == 0) ? 'null' : "'".$xserie."'";

            // HD 101357 - desbloqueada
            if ($informatica == 't' and strlen($xpeca)> 0) {

                $sql_x = "SELECT numero_serie_peca from tbl_peca where referencia = '$xpeca' and fabrica = $login_fabrica;";

                $res_x = pg_query($con, $sql_x);
                $testa_numero_serie_peca = pg_fetch_result($res_x, $i, 'numero_serie_peca');

                if ($testa_numero_serie_peca <> 't') {

                    if (strlen($xpeca_serie) == 0) $msg_erro .= "";
                    else                           $xpeca_serie = "'" . $xpeca_serie . "'";

                } else {
                    if (strlen($xpeca_serie) == 0) $msg_erro .= "É necessário informar a Série da Peça para linha de Informática.";
                    else                           $xpeca_serie = "'" . $xpeca_serie . "'";

                    if($login_fabrica == 3 and strlen($msg_erro ) == 0 ){
                        $sql = "SELECT numero_serie_peca
                                FROM tbl_numero_serie_peca
                                WHERE fabrica = $login_fabrica and
                                    serie_peca = $xpeca_serie ;";
                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res) > 0) {
                            /* echo "correto";*/
                        }else{
                            $msg_erro .= "É necessário informar a Série da Peça Válido para linha de Informática.";
                        }
                    }
                }
            }else{
                $xpeca_serie = (strlen($xpeca_serie) == 0)?'null':"'".$xpeca_serie."'";
            }
	    if ($xpeca_serie == '') $xpeca_serie = 'NULL';
            $xpeca_serie_trocada= (strlen($xpeca_serie_trocada) == 0)?'null':"'".$xpeca_serie_trocada."'";
            $xposicao            = (strlen($xposicao) == 0) ? 'null' : "'".$xposicao."'";

            $xadmin_peca      = $_POST["admin_peca_"     . $i]; //aqui
            if (strlen($xadmin_peca)==0) $xadmin_peca ="null"; //aqui
            if($xadmin_peca=="P") $xadmin_peca ="null"; //aqui

/*            if ($login_fabrica == 5 and strlen($causa_defeito) == 0)
                $msg_erro = "Selecione a causa do defeito";
            elseif ($login_fabrica <> 5 and strlen($causa_defeito) == 0)
                $causa_defeito = 'null';*/

            if ((strlen($xos_produto) > 0 or strlen($xorcamento_item)>0) AND strlen($xpeca) == 0) {
                if (strlen($xos_produto) > 0){
                    $sql = "DELETE FROM tbl_os_produto
                            WHERE  tbl_os_produto.os         = $os
                            AND    tbl_os_produto.os_produto = $xos_produto";
                    #HD 15489
                    $sql = "UPDATE tbl_os_produto SET
                                os = 4836000
                            WHERE os         = $os
                            AND   os_produto = $xos_produto";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
                if (strlen($orcamento)>0 AND strlen($xorcamento_item) > 0){
                    $sql = "DELETE FROM tbl_orcamento_item
                            WHERE  orcamento_item = $xorcamento_item
                            AND orcamento = $orcamento";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                    $query = "UPDATE tbl_orcamento SET
                                total_pecas     = (SELECT SUM(preco*qtde)
                                                FROM tbl_orcamento_item
                                                WHERE orcamento = $orcamento),
                                total_pecas_venda = (SELECT SUM(preco_venda*qtde)
                                                FROM tbl_orcamento_item
                                                WHERE orcamento = $orcamento)
                            WHERE orcamento = $orcamento
                            AND empresa = $login_fabrica";
                    $orca = pg_query($con, $query);
                    $msg_erro .= pg_errormessage($con);
                }
            }else{
                if ($login_fabrica == 3 && strlen($xpeca) > 0) {
                    $sqlX = "SELECT referencia, TO_CHAR (previsao_entrega,'DD/MM/YYYY') AS previsao
                             FROM tbl_peca
                             WHERE UPPER(referencia_pesquisa) = UPPER('$xpeca')
                             AND   fabrica = $login_fabrica
                             AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
                    $resX = pg_query($con,$sqlX);
                    if (pg_num_rows($resX) > 0) {
                        $peca_previsao = pg_fetch_result($resX,0,referencia);
                        $previsao      = pg_fetch_result($resX,0,previsao);

                        $msg_previsao  = "O pedido da peça $peca_previsao foi efetivado. A previsão de disponibilidade desta peça será em $previsao. A fábrica tomará as medidas necessárias par o atendimento ao consumidor.";
                    }
                }

                if (strlen($xpeca) > 0 and strlen($msg_erro) == 0) {
                    $xpeca    = strtoupper ($xpeca);

                    if (strlen($msg_erro) > 0) {
                    }

                    if (strlen($xqtde) == 0) $xqtde = "1";

                    if ($login_fabrica == 1 && intval($xqtde) == 0) $msg_erro .= " O item $xpeca está sem quantidade, por gentileza informe a quantidade para este item. ";

                    #HD 13618
                    if ($login_fabrica==45 and $troca_obrigatoria == 't'){
                        $msg_erro .= " Este produto é de Troca e não é fornecido peças para reparo.";
                        break;
                    }

                    if (strlen($xproduto) == 0) {
                        $sql = "SELECT tbl_os.produto
                                FROM   tbl_os
                                WHERE  tbl_os.os      = $os
                                AND    tbl_os.fabrica = $login_fabrica;";
                        $res = pg_query($con,$sql);

                        if (pg_num_rows($res) > 0) {
                            $xproduto = pg_fetch_result($res,0,0);
                        }
                    }else{
                            $sql = "SELECT tbl_produto.produto
                                FROM   tbl_produto
                                JOIN   tbl_linha USING (linha)
                                WHERE  (tbl_produto.referencia_pesquisa = '$xproduto' or tbl_produto.referencia = '$xproduto')
                                AND    tbl_linha.fabrica = $login_fabrica
                                ";

                                /*HD: 79762 03/03/2009 DEIXAR APENAS A BUSCA PELA LISTA BÁSICA MESMO QUANDO O PRODUTOS ESTIVER INATIVO*/
                                if($login_fabrica <> 3 and $login_fabrica <> 81 ) $sql .= " AND tbl_produto.ativo IS TRUE " ;

                        $res = pg_query($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #$msg_erro .= "Produto $xproduto não cadastrado";
                            $linha_erro = $i;
                        }else{
                            $xproduto = pg_fetch_result($res,0,produto);
                        }
                    }

                    if (strlen($msg_erro) == 0) {

                        #peças para Orçamento
                        # $os_revenda_item > 0 quando a OS for de revenda
                        if (($login_fabrica==3 or $login_fabrica == 45 or $login_fabrica == 24) AND ($xservico == 643 OR $xservico == 644 OR $xservico == 4247 or $xservico == 4289)) {

                            $gravou_pecas_orcamento = "sim";

                            # Apaga caso exista algum item que não é orçamento
                            if (strlen($xos_produto) > 0){
                                $sql = "DELETE FROM tbl_os_produto
                                        WHERE  tbl_os_produto.os         = $os
                                        AND    tbl_os_produto.os_produto = $xos_produto";
                                #HD 15489
                                $sql = "UPDATE tbl_os_produto SET
                                            os = 4836000
                                        WHERE os         = $os
                                        AND   os_produto = $xos_produto";
                                $res = pg_query($con,$sql);
                                $msg_erro .= pg_errormessage($con);
                            }

                            if (strlen($orcamento)==0){
                                $query = "INSERT INTO tbl_orcamento (empresa,os) VALUES ($login_fabrica,$os)";
                                $orca = pg_query($con, $query);
                                $msg_erro .= pg_errormessage($con);
                                $query = "SELECT currval('tbl_orcamento_orcamento_seq') AS orcamento";
                                $orca = pg_query($con, $query);
                                $orcamento = pg_fetch_result($orca,0,orcamento);
                            }

                            if (strlen($orcamento)==0){
                                $msg_erro .= "Erro ao criar o orçamento. Tente novamente ou contate o Suporte Telecontrol através do e-mail: suportetelecontrol.com.br";
                            }
                            //HD 21425 não pode gravar produto acabado
                            $sql = "SELECT tbl_peca.*
                                    FROM   tbl_peca
                                    WHERE  (UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
                                            OR UPPER(tbl_peca.referencia) = UPPER('$xpeca'))
                                    AND    tbl_peca.fabrica = $login_fabrica
                                    AND    tbl_peca.produto_acabado IS NOT TRUE;";
                            $res = pg_query($con,$sql);

                            if (pg_num_rows($res) == 0) {
                                $msg_erro .= "Peça $xpeca não cadastrada";
                                $linha_erro = $i;
                            }else{
                                $xpeca                    = pg_fetch_result($res,0,peca);
                            }

                            if (strlen($xdefeito) == 0) {
                                $msg_erro .= "Favor informar o defeito da peça"; #$defeito = "null";
                            }

                            if (strlen($xservico) == 0) {
                                $msg_erro .= "Favor informar o serviço realizado"; #$servico = "null";
                            }

                            if (strlen($xpcausa_defeito) == 0) {
                                $xpcausa_defeito = 'null';
                            }
                            #echo '*'.$xorcamento_item;
                            if (strlen($msg_erro)== 0){
                                if (strlen($xorcamento_item) == 0){
                                    $query = "INSERT INTO tbl_orcamento_item
                                    (orcamento,peca,defeito,servico_realizado,preco,qtde,preco_venda)
                                    VALUES ($orcamento,$xpeca,$xdefeito,$xservico,$xpreco_orcamento,$xqtde,$xpreco_venda_orcamento)";
                                    $orca = pg_query($con, $query);
                                    $msg_erro .= pg_errormessage($con);
                                }else{
                                    $query = "UPDATE tbl_orcamento_item SET
                                                peca              = $xpeca,
                                                defeito           = $xdefeito,
                                                servico_realizado = $xservico,
                                                preco             = $xpreco_orcamento,
                                                preco_venda       = $xpreco_venda_orcamento,
                                                qtde              = $xqtde
                                            WHERE orcamento = $orcamento
                                            AND orcamento_item = $xorcamento_item";
                                    $orca = pg_query($con, $query);
                                    $msg_erro .= pg_errormessage($con);
                                }
                                $query = "UPDATE tbl_orcamento SET
                                            total_pecas     = (    SELECT
                                                        SUM(preco*qtde)
                                                        FROM tbl_orcamento_item
                                                        WHERE orcamento = $orcamento
                                                    )
                                        WHERE orcamento = $orcamento
                                        AND empresa = $login_fabrica";
                                $orca = pg_query($con, $query);
                                $msg_erro .= pg_errormessage($con);
                            }

                        } else {

                            if(($login_fabrica == 15 || $login_fabrica == 24) and strlen($xkit_peca) > 0) {//HD 258901
                                if (strlen($xdefeito) == 0) $msg_erro .= "Favor informar o defeito da peça";
                                if (strlen($xservico) == 0) $msg_erro .= "Favor informar o serviço realizado";

                                if (strlen($xos_produto) >  0){
                                    $sql = "UPDATE tbl_os_produto SET
                                                os = 4836000
                                            WHERE os         = $os
                                            AND   os_produto = $xos_produto";
                                    $res =pg_query($con,$sql);
                                    $msg_erro .= pg_errormessage($con);
                                }

                                if (strlen($msg_erro) == 0) {
                                    $sql = "  SELECT tbl_peca.peca
                                                FROM    tbl_kit_peca_peca
                                                JOIN    tbl_peca USING(peca)
                                                WHERE   fabrica = $login_fabrica
                                                AND     kit_peca = $xkit_peca
                                                ORDER BY tbl_peca.peca";
                                    $res = pg_query($con,$sql);

                                    if(pg_num_rows($res) > 0){
                                        $sqlx = "INSERT INTO tbl_os_produto (
                                                        os     ,
                                                        produto,
                                                        serie
                                                    )VALUES(
                                                        $os     ,
                                                        $xproduto,
                                                        $xserie
                                                );";
                                        $resx = pg_query($con,$sqlx);
                                        $msg_erro .= pg_errormessage($con);
                                        $resx = pg_query($con,"SELECT CURRVAL ('seq_os_produto')");
                                        $xos_produto  = pg_fetch_result($resx,0,0);

                                        for($xx =0;$xx<pg_num_rows($res);$xx++) {
                                            $xxpeca = pg_fetch_result($res,$xx,'peca');
                                            $kit_peca_peca = $_POST['kit_peca_'.$xxpeca];
                                            $kit_peca_qtde = $_POST['kit_peca_qtde_'.$xxpeca];

                                            if (strlen($kit_peca_peca) > 0) {
                                                for($kit_n = 0; $kit_n < $kit_peca_qtde; $kit_n++) {
                                                    //echo "$xxpeca - $kit_peca_qtde : ";
                                                    $sqlx = "INSERT INTO tbl_os_item (
                                                                    os_produto            ,
                                                                    peca                  ,
                                                                    qtde                  ,
                                                                    defeito               ,
                                                                    servico_realizado
                                                                )VALUES(
                                                                    $xos_produto          ,
                                                                    $xxpeca               ,
                                                                    1                     ,
                                                                    $xdefeito             ,
                                                                    $xservico
                                                            );";
                                                    $resx = pg_query($con,$sqlx);
                                                    $msg_erro .= pg_errormessage($con);
                                                }
                                            }
                                        }
                                    }
                                }

                            }else{
                                if (strlen($xos_produto) == 0){
                                    $sql = "INSERT INTO tbl_os_produto (
                                                os     ,
                                                produto,
                                                serie
                                            )VALUES(
                                                $os     ,
                                                $xproduto,
                                                $xserie
                                        );";
                                    //echo '1-'.$sql.'<br>';
                                    $res = pg_query($con,$sql);
                                    $msg_erro .= pg_errormessage($con);

                                    $res = pg_query($con,"SELECT CURRVAL ('seq_os_produto')");
                                    $xos_produto  = pg_fetch_result($res,0,0);
                                }else{
                                    $sql = "UPDATE tbl_os_produto SET
                                                os      = $os      ,
                                                produto = $xproduto,
                                                serie   = $xserie
                                            WHERE os_produto = $xos_produto;";
                                    //echo '1-'.$sql.'<br>';
                                    $res = pg_query($con,$sql);
                                    $msg_erro .= pg_errormessage($con);
                                }

                                // Delete Orçamento caso exista
                                if (strlen($xorcamento_item)>0){
                                    $sql = "DELETE FROM tbl_orcamento_item
                                            WHERE orcamento = $orcamento
                                            AND orcamento_item = $xorcamento_item";
                                    $res = pg_query($con,$sql);
                                    $msg_erro .= pg_errormessage($con);//echo $msg_erro;
                                }

                                if (strlen($msg_erro) > 0) {

                                    break ;

                                } else {

                                    $xpeca = strtoupper ($xpeca);

                                    //HD 21425 não pode gravar produto acabado
                                    if (strlen($xpeca) > 0) {
                                        $sql = "SELECT tbl_peca.*
                                                  FROM tbl_peca
                                                 WHERE (UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
                                                        OR UPPER(tbl_peca.referencia) = UPPER('$xpeca'))
                                                   AND tbl_peca.fabrica = $login_fabrica
                                                   AND tbl_peca.produto_acabado IS NOT TRUE;";

                                        if ($login_fabrica == 50 or $login_fabrica == 5) {

                                            /* hd: 45924 13/10/2008 */
                                            $sql = "SELECT tbl_peca.*
                                                      FROM tbl_peca
                                                     WHERE UPPER(tbl_peca.referencia) = UPPER('$xpeca')
                                                       AND tbl_peca.fabrica = $login_fabrica
                                                       AND tbl_peca.produto_acabado IS NOT TRUE;";

                                        }

                                        $res = pg_query($con,$sql);
										unset($aux_xpeca);

                                        if (pg_num_rows($res) == 0) {

                                            $msg_erro .= "Peça $xpeca não cadastrada";
                                            $linha_erro = $i;

                                        } else {

                                            $aux_xpeca = $xpeca;
                                            $xpeca                    = pg_fetch_result($res, 0, 'peca');
                                            $intervencao_fabrica_peca = pg_fetch_result($res, 0, 'retorna_conserto');
                                            $troca_obrigatoria_peca   = pg_fetch_result($res, 0, 'troca_obrigatoria');
                                            $bloqueada_garantia_peca  = pg_fetch_result($res, 0, 'bloqueada_garantia');
                                            $bloqueada_peca_critica   = pg_fetch_result($res, 0, 'peca_critica');
                                            $intervencao_carteira     = pg_fetch_result($res, 0, 'intervencao_carteira');
                                            $previsao_entrega_peca    = pg_fetch_result($res, 0, 'previsao_entrega');
                                            $gera_troca_produto       = pg_fetch_result($res, 0, 'gera_troca_produto');

                                            if ($gera_troca_produto == 't' AND $login_fabrica == 45) {

                                                $sql_intervencao = "SELECT status_os
                                                                      FROM tbl_os_status
                                                                     WHERE os = $os
                                                                       AND status_os IN (62,64,65)
                                                                     ORDER BY data DESC
                                                                     LIMIT 1";

                                                $res_intervencao = pg_query($con, $sql_intervencao);
                                                $status_os = "";

                                                if (pg_num_rows($res_intervencao) > 0){
                                                    $status_os = trim(pg_fetch_result($res_intervencao, 0, 'status_os'));
                                                }

                                                if (pg_num_rows($res_intervencao) == 0 or $status_os == "64") {

                                                    if ($gera_troca_produto == 't') {
                                                        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'Foi lançada uma  peça nesta O.S., e é necessário que o produto seja trocado.')";
                                                        $res = pg_query($con,$sql);
                                                    }

                                                }

                                            }

                                        }

                                        if ($login_fabrica == 6) {//HD 3475

                                            if ($intervencao_fabrica_peca != 't' AND $xservico != "485") {

                                                $ssql = "SELECT (current_date - data_abertura) as dias from tbl_os where os=$os";
                                                $ress = pg_query($con, $ssql);

                                                if (pg_num_rows($ress) > 0) {

                                                    if (pg_fetch_result($ress,0,0) > 40) {
                                                        $msg_erro .= "PARA SOLICITAÇÃO DE PEÇA DESTA ORDEM DE SERVIÇO, FAVOR ENTRAR EM CONTATO COM O DEPTO. TÉCNICO - TEC TOY";
                                                    }

                                                }

                                            }

                                        }

                                        if (strlen($xdefeito) == 0) $msg_erro .= "Favor informar o defeito da peça"; #$defeito = "null";
                                        if (strlen($xservico) == 0) $msg_erro .= "Favor informar o serviço realizado"; #$servico = "null";

                                        //if ($login_fabrica == 5 and strlen($xcausa_defeito) == 0) $msg_erro = "Selecione a causa do defeito.";
                                        //elseif (strlen($xcausa_defeito) == 0)                    $xcausa_defeito = 'null';

                                        if (strlen($xpcausa_defeito) == 0) $xpcausa_defeito = 'null';


                                        //echo $msg_erro;

                                        if (strlen($msg_erro) == 0) {

                                            if ($login_fabrica == 11) {

                                                $sql = "SELECT CURRENT_DATE - date(data_abertura) as dias
                                                          FROM tbl_os
                                                         WHERE tbl_os.os = $os";

                                                $res  = pg_query($con,$sql);
                                                $dias = pg_fetch_result($res,0,0);
                                                $existe_item = $_POST['existe_item'];

                                                if ($dias > 15 and $existe_item == 0 and $xservico == 61) {

                                                    echo "<script type='text/javascript'>
                                                    alert('ATENÇÃO \\nEste pedido de peças está sendo realizado a mais de 15 (quinze) dias após a data da abertura da OS.\\nEsclarecemos que os pedidos de peças em atraso acarretam o atraso no conserto do aparelho, e que pode acarretar prejuízo para a empresa decorrentes de reclamações no SAC ou PROCON.\\nPor fim, informamos que este pedido de peça em atraso foi cadastrado em nosso banco de dados e, caso a empresa venha a sofrer prejuízo por conta desta OS, em decorrência deste ato, o valor do  prejuízo poderá ser descontado de Vossa Senhoria.');
                                                    </script>";

                                                }

                                            }
											unset($makita_preco_bruto);
											if($login_fabrica == 42) {
											
													$preco = "select referencia from tbl_peca where peca = $xpeca";
													$preco_res = pg_query($con,$preco);
													$ref_peca = pg_result($preco_res,0,0);
													$ins_makita = ', preco';													
													$_GET['linha_form'] = $xx; 
													$_GET['condicao'] = '1159'; //condicao de pagamento a vista
													$_GET['produto_referencia'] = $ref_peca;		
													$_GET['posto'] = $login_posto;
													unset($makita_preco);
													ob_start();
													include('makita_valida_regras.php');
													ob_get_clean();
													$makita_preco_bruto = ', ' . number_format($makita_preco,2,".",".");
											}

                                            if (strlen($xos_item) == 0) {

                                                #HD 101357 inserir nul no lugar do numero de serie da peca para não dar erro no insert
                                                if ($login_fabrica == 3 AND strlen($xpeca_serie) == 0) {
                                                    $xpeca_serie = "null";
                                                }

                                                $sql = "INSERT INTO tbl_os_item (os_produto            ,
                                                                                 posicao               ,
                                                                                 peca                  ,
                                                                                 qtde                  ,
                                                                                 defeito               ,
                                                                                 causa_defeito         ,
                                                                                 servico_realizado     ,
                                                                                 admin                 ,
                                                                                 peca_serie            ,
                                                                                 peca_serie_trocada    ,
                                                                                 peca_reposicao_estoque,
                                                                                 aguardando_peca_reparo
																				 $ins_makita
                                                                       ) VALUES (
                                                                                 $xos_produto            ,
                                                                                 $xposicao               ,
                                                                                 $xpeca                  ,
                                                                                 $xqtde                  ,
                                                                                 $xdefeito               ,
                                                                                 $xpcausa_defeito        ,
                                                                                 $xservico               ,
                                                                                 $xadmin_peca            ,
                                                                                 $xpeca_serie            ,
                                                                                 $xpeca_serie_trocada    ,
                                                                                 $xpeca_reposicao_estoque,
                                                                                 $xaguardando_peca_reparo
																				 $makita_preco_bruto);";

                                                $res = pg_query($con,$sql);
                                                $msg_erro .= pg_errormessage($con);

                                                //echo $msg_erro;

                                            } else {

                                                #HD 101357 inserir nul no lugar do numero de serie da peca para não dar erro no insert
                                                if ($login_fabrica == 3 AND strlen($xpeca_serie) == 0) {
                                                    $xpeca_serie = "null";
                                                }
												if($login_fabrica == 42)
													$upd_preco = ', preco = ' . number_format($makita_preco,2,".",".");

                                                $sql = "UPDATE tbl_os_item SET
                                                            os_produto              = $xos_produto            ,
                                                            posicao                 = $xposicao               ,
                                                            peca                    = $xpeca                  ,
                                                            qtde                    = $xqtde                  ,
                                                            defeito                 = $xdefeito               ,
                                                            causa_defeito           = $xpcausa_defeito        ,
                                                            servico_realizado       = $xservico               ,
                                                            admin                   = $xadmin_peca            ,
                                                            peca_serie              = $xpeca_serie            ,
                                                            peca_serie_trocada      = $xpeca_serie_trocada    ,
                                                            peca_reposicao_estoque  = $xpeca_reposicao_estoque,
                                                            aguardando_peca_reparo  = $xaguardando_peca_reparo
															$upd_preco
                                                        WHERE os_item = $xos_item;";
                                                //echo '1-'.$sql.'<br>';
                                                $res = pg_query($con,$sql);
                                                $msg_erro .= pg_errormessage($con);

                                            }

                                            if (strlen($msg_erro) > 0) {
                                                break ;
                                            }

                                            // Se a peça estiver como bloqueada garantia, a os é cadastrada, a peça tbm, mas o pedido da peça nao eh feito. Somente após a autorizacao o pedido da peça eh feito. -- Fabio 07/03/2007
                                            if (($login_fabrica == 3  AND $xservico == "20" AND $bloqueada_garantia_peca == 't') OR
                                                ($login_fabrica == 11 AND $xservico == "61" AND $bloqueada_garantia_peca == 't')) {
                                                // envia email teste para avisar
                                                $os_bloqueada_garantia ='t';
                                                $msg_intervencao .= "<br />O pedido da peça $xpeca precisa de análise antes do envio. Aguarde o contato da fábrica";
                                                $gravou_peca = "sim";
                                            }
                                            // Se a PEÇA tiver intervencao da fabrica e for troca de peca gerando pedido, alterar status da OS para Intervenção da Assistência Técnica da Fábrica PENDENTE ( tbl_status_os -> 62)
                                            //Retirado intervençao tecnica para Lenoxx - HD 11374
                                            // ($login_fabrica==11  AND $xservico=="61")
                                            //voltei a rotina para Lenoxx hd 15230
                                            elseif ((($login_fabrica == 2 AND $xservico == "7" AND $login_posto == 6359) OR ($login_fabrica == 3  AND $xservico == "20") OR ($login_fabrica == 6  AND $xservico=="485") OR ($login_fabrica == 11  AND $xservico == "61") OR ($login_fabrica == 51  AND $xservico == "673"))
                                                AND ($intervencao_fabrica_peca == 't' OR $troca_obrigatoria_peca == 't') AND $xqtde > 0) {
                                                $os_com_intervencao = 't';
                                                $gravou_peca = "sim";
                                            }
                                            /* INTERVENÇÃO DE CARTEIRA */
                                            else if (($login_fabrica == 3 AND $xservico == "20" AND $intervencao_carteira == 't')) {
                                                // envia email teste para avisar
                                                $os_com_intervencao_carteira ='t';
                                                $gravou_peca="sim";
                                            }
                                            # se for peça critica, entra em OS com intervenção de suprimentos
                                            else if ($login_fabrica == 11 AND $bloqueada_peca_critica == 't' AND $xservico == "61") {

                                                $os_com_intervencao_suprimentos = 't';
                                                $gravou_peca = "sim";

                                            } else if ($login_fabrica == 3 AND $xservico == "20") {

                                                $gravou_peca = "sim";

                                            } else if($login_fabrica == 3) { // hd 49873

                                                $sqlp = "SELECT gera_pedido 
												FROM tbl_servico_realizado 
												WHERE fabrica = $login_fabrica
												AND ativo IS TRUE 
												AND servico_realizado = $xservico";

												$resp = pg_query($con,$sqlp);
												$gera_pedido = pg_fetch_result($resp,0,0);

												if ($gera_pedido == 't') {
													$os_com_intervencao = 't';
													$gravou_peca = "sim";
												}
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($login_fabrica == 80) {
            $rastreamento_envio = $_POST['rastreamento_envio'];
            $data_envio = $_POST['data_envio'];

                $sql = "SELECT hd_chamado from tbl_hd_chamado_extra where os = $os";

                $res = pg_exec($con,$sql);

                if (pg_num_rows($res)>0) {

                    $hd_chamado = pg_result($res,0,0);
                    $sqlinf = "INSERT INTO tbl_hd_chamado_item(
                        hd_chamado   ,
                        data         ,
                        comentario   ,
                        interno      ,
                        admin
                        )values(
                        $hd_chamado       ,
                        current_timestamp ,
                        'Envio do Produto ao consumidor em $data_envio, número do rastreamento ==><a href=\"http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_envio"."BR target=\"_blank\"\"> $rastreamento_envio </a>',
                        't',
                        (SELECT admin FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado limit 1)
                        )";
                    //echo nl2br($sqlinf);
                    $resinf = pg_query($con,$sqlinf);

                    $sql = "UPDATE tbl_os set obs = 'Envio do Produto ao consumidor em $data_envio, número do rastreamento ==><a href=\"http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_envio"."BR target=\"_blank\"\"> $rastreamento_envio </a>' where os = $os";
                    $res = pg_query($con,$sql);
                }
        }

        if ($login_fabrica == 6 or
            ($login_fabrica == 15 and
             in_array($login_posto,array(6359,10950,10952,20235,2405,5551,12008,11946,11467,10806,11958,11946,11471,11732,118825)))           ) { //HD 2599, 15180, 11/12/09-HD 183336
            $pre_total = $_POST['pre_total'];
			
            for ($i = 0 ; $i < $pre_total ; $i++) {
                $pre_peca = $_POST['pre_peca_'.$i];
                if (strlen($pre_peca)>0){
					
                    //echo "<BR>$pre_peca";
                    $pre_defeito = $_POST['pre_defeito_'.$i];
                    $pre_servico = $_POST['pre_servico_'.$i];
                    $pre_qtde    = $_POST['pre_qtde_'   .$i];
                    //echo "<BR>$pre_defeito";
                    //echo "<BR>$pre_servico";
                    if (strlen($pre_defeito)== 0)$msg_erro .= "Favor informar o defeito da peça<BR>";
                    if (strlen($pre_servico)== 0)$msg_erro .= "Favor informar o serviço realizado<BR>";

                    $sql = "select produto from tbl_os where os=$os and fabrica = $login_fabrica";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)>0){
                        $pre_produto = pg_fetch_result($res,0,0);
                    }
					
					$sql_peca = "SELECT tbl_peca.referencia
								FROM tbl_peca
								WHERE peca = $pre_peca";
					$res_peca = pg_query($con,$sql_peca);
					
					$referencia = pg_fetch_result($res_peca,0,'referencia');
					#HD 335128 VALIDACAO DO NRO DE SERIE COM A PECA DA LISTA BASICA - INICIO
					if ($login_fabrica == 15){
						
						if (isset($_POST['produto_serie'])) {
							$produto_serie_letra = substr($_POST['produto_serie'],1,1);
						}
						
						
						
						if (strlen($produto_serie)>0){
						$sql_serie_in_out = "SELECT 
											tbl_lista_basica.peca         ,
											tbl_lista_basica.serie_inicial,
											tbl_lista_basica.serie_final
											froM tbl_lista_basica
											where produto=$pre_produto
											AND tbl_lista_basica.peca = $pre_peca
											ORDER BY tbl_lista_basica.peca";
							
							$res_serie_in_out = pg_query($con,$sql_serie_in_out);
							
							$serie_inicial = pg_result($res_serie_in_out,0, serie_inicial);
							$serie_final = pg_result($res_serie_in_out,0, serie_final);
							
							if (strlen($serie_inicial)>0 and strlen($serie_final)>0){
						
								$sql_serie= "SELECT 
											tbl_lista_basica.peca,
											tbl_lista_basica.serie_inicial,
											tbl_lista_basica.serie_final
											froM tbl_lista_basica
											where produto=$pre_produto
											AND tbl_lista_basica.peca = $pre_peca
											AND '$produto_serie_letra' between tbl_lista_basica.serie_inicial and tbl_lista_basica.serie_final
											ORDER BY tbl_lista_basica.peca";
								$res_serie = pg_query($con,$sql_serie);
								
								$linha = pg_num_rows($res_serie);
								if ($linha == 0){
									$msg_erro .= " A peça $referencia não pertence à esta versão do produto<BR>";
								}
							
							}
						}
						
					}#HD 335128 VALIDACAO DO NRO DE SERIE COM A PECA DA LISTA BASICA - FIM
					
                    if (strlen($msg_erro)==0){
                            $sql = "INSERT INTO tbl_os_produto (
                                            os     ,
                                            produto
											
                                        )VALUES(
                                            $os     ,
                                            $pre_produto
                                    );";
                                $res = pg_query($con,$sql);
                                $msg_erro .= pg_errormessage($con);
                                $res = pg_query($con,"SELECT CURRVAL ('seq_os_produto')");
                                $xos_produto  = pg_fetch_result($res,0,0);
                    }
                    if (strlen($msg_erro) == 0) {
                            $sql = "INSERT INTO tbl_os_item (
                                        os_produto        ,
                                        peca              ,
                                        qtde              ,
                                        defeito           ,
                                        servico_realizado
                                    )VALUES(
                                        $xos_produto    ,
                                        $pre_peca       ,
                                        $pre_qtde       ,
                                        $pre_defeito    ,
                                        $pre_servico
                                );";
                            $res = pg_query($con,$sql);
                            $msg_erro .= pg_errormessage($con);
                            //echo "2- ".$sql;
                    }
                }
            }
        }
    }

    # Caso gravou peças de orçamento, registrar...
    if (strlen($orcamento) > 0 AND $gravou_pecas_orcamento == "sim") {

        $gravou_pecas_orcamento = "";

        $valor_mo_orcamento = trim($_POST["valor_mo_orcamento"]);
        $orcamento_aprovado = trim($_POST["orcamento_aprovado"]);

        $valor_mo_orcamento = str_replace ("," , "." ,$valor_mo_orcamento);
        $valor_mo_orcamento = str_replace ("-" , "" , $valor_mo_orcamento);
        $valor_mo_orcamento = str_replace ("/" , "" , $valor_mo_orcamento);
        $valor_mo_orcamento = str_replace (" " , "" , $valor_mo_orcamento);

        if (strlen($valor_mo_orcamento) == 0) {
            $valor_mo_orcamento = " NULL ";
        }

        if (strlen($orcamento_aprovado) > 0) {
            $orcamento_aprovado = "'t'";
        } else {
            $orcamento_aprovado = "NULL";
        }

        if (strlen($orcamento) == 0) {
            $query = "INSERT INTO tbl_orcamento (empresa,os,total_mao_de_obra,aprovado) VALUES ($login_fabrica,$os,$valor_mo_orcamento,$orcamento_aprovado)";
            $orca = pg_query($con, $query);
            $msg_erro .= pg_errormessage($con);
            $query = "SELECT currval('tbl_orcamento_orcamento_seq') AS orcamento";
            $orca = pg_query($con, $query);
            $orcamento = pg_fetch_result($orca,0,orcamento);
        }else{
            $query = "UPDATE tbl_orcamento SET
                        total_mao_de_obra= $valor_mo_orcamento,
                        total_pecas     = (SELECT
                                    SUM(preco*qtde)
                                    FROM tbl_orcamento_item
                                    WHERE orcamento=$orcamento
                                    ),
                        aprovado = $orcamento_aprovado
                    WHERE orcamento = $orcamento
                    AND empresa = $login_fabrica";
            $orca = pg_query($con, $query);
            $msg_erro .= pg_errormessage($con);
        }

        #Criar Help-Desk
        $sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento = $orcamento";
        $res_chamado = pg_query($con, $sql);
        if(pg_num_rows($res_chamado)>0){
            $hd_chamado = pg_fetch_result($res_chamado,0,hd_chamado);
        }else{
            $sql = "INSERT INTO tbl_hd_chamado (posto,titulo,orcamento) VALUES ($login_posto,'Orçamento da OS Nº $sua_os',$orcamento)";
            $res_chamado = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT currval('seq_hd_chamado')";
            $res_chamado = pg_query($con, $sql);
            $hd_chamado = pg_fetch_result($res_chamado,0,0);
        }

        $sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,comentario) VALUES ($hd_chamado,'Ordem de Serviço alterada. Aguardando aprovação')";
        $res_chamado = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

        if (strlen($orcamento)>0){
            $query = "UPDATE tbl_orcamento SET
                        aprovado = NULL,
                        data_aprovacao = NULL,
                        data_reprovacao = NULL,
                        motivo_reprovacao = NULL
                    WHERE orcamento = $orcamento
                    AND empresa = $login_fabrica";
            $orca = pg_query($con, $query);
            $msg_erro .= pg_errormessage($con);
        }

        # Teste se tem mais itens em Orçamento. Se não tive apaga.
        /*( RETIREI PARA TESTES )*/
        if (strlen($orcamento)>0 AND 1==2){
            $query = "SELECT count(*)
                    FROM tbl_orcamento_item
                    WHERE orcamento = $orcamento";
            $orca = pg_query($con, $query);
            $msg_erro .= pg_errormessage($con);
            $qtde_pecas_orcando = pg_fetch_result($orca,0,0);
            if ($qtde_pecas_orcando==0){
                if (strlen($hd_chamado)>0){
                    $query = "DELETE FROM tbl_hd_chamado_item
                                WHERE hd_chamado = $hd_chamado";
                    $orca = pg_query($con, $query);
                    $msg_erro .= pg_errormessage($con);
                    $query = "DELETE FROM tbl_hd_chamado
                                WHERE hd_chamado = $hd_chamado";
                    $orca = pg_query($con, $query);
                    $msg_erro .= pg_errormessage($con);
                }
                $query = "DELETE FROM tbl_orcamento
                            WHERE orcamento = $orcamento
                            AND empresa = $login_fabrica";
                $orca = pg_query($con, $query);
                $msg_erro .= pg_errormessage($con);
            }
        }
    }

//     Para a latinatec sempre que a solução for para trocar peça (troca_peca is true)
//     deve ser especificado a peças a ser trocada.HD3549
//     Adicionado Gama Italy HD20369
//     Adicionado Socinter HD29352
//     MLG 25/8/2009 - Tectoy HD 7489 - Passei de rotina extra embaixo para ésta, que é exatamente igual, inclusive o texto de erro

//HD 208462: 1. Se a solução selecionada exigir troca de peça, exigir os_item com serviço que gere troca de peça
//             Totas as fábricas, autorizado por Samuel
//             2. Se a solução selecionada não exigir troca de peça, não deixar cadastrar os_item com serviço
//             que gere troca de peça. Todas as fábricas, autorizado por Samuel

//HD 232039: Para a SightGPS cadastra varios defeitos e solucoes, a validação é diferente. Ver mais abaixo
if (strlen($x_solucao_os) > 0 && $x_solucao_os <> 'null' && $login_fabrica != 47 && $login_fabrica != 59){
    if ($login_fabrica == 15 && ($solucao_os == 328 || $solucao_os == 330 || $solucao_os == 331)) {
        //HD 227536: Para as soluções 328 330 331 não deve validar a questão de lançamento de itens
    }
    else {
        $sql_t = "SELECT troca_peca FROM tbl_solucao
                   WHERE solucao = $x_solucao_os AND fabrica = $login_fabrica AND troca_peca IS TRUE; ";
        $res_t = pg_query($con,$sql_t);
        if(pg_num_rows($res_t) > 0){
            $sql_t = "SELECT COUNT(*)
                            FROM tbl_os_item
                            JOIN tbl_os_produto USING(os_produto)
                            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                        WHERE os = $os
                        AND tbl_servico_realizado.troca_de_peca IS TRUE";
            $res_t = pg_query($con,$sql_t);
            if(pg_fetch_result($res_t,0,0) == 0){
                $msg_erro .= "Para a solução escolhida, é necessário especificar a peça a ser trocada.";
            }
        }
        else {
            $sql_t = "SELECT COUNT(*)
                            FROM tbl_os_item
                            JOIN tbl_os_produto USING(os_produto)
                            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                        WHERE os = $os
                        AND tbl_servico_realizado.troca_de_peca IS TRUE";
            $res_t = pg_query($con,$sql_t);
            if(pg_fetch_result($res_t,0,0) > 0){
                $msg_erro .= "Para a solução escolhida, não pode existir serviço com troca de peça.";
            }
        }
    }
}
//HD 232039: Validar defeito constatado e solução para fábricas que lançam mais de um defeito x solucao
else if ($login_fabrica == 59) {
    $sql = "
    SELECT
    DISTINCT
    tbl_solucao.troca_peca

    FROM
    tbl_os_defeito_reclamado_constatado
    JOIN tbl_solucao ON tbl_os_defeito_reclamado_constatado.solucao=tbl_solucao.solucao

    WHERE
    os=$os
    ";
    $res = pg_query($con, $sql);

    //Contando quantos itens lançados possuem serviço com troca de peça
    $sql_t = "
    SELECT
    COUNT(*)

    FROM
    tbl_os_item
    JOIN tbl_os_produto USING(os_produto)
    JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado

    WHERE
    os = $os
    AND tbl_servico_realizado.troca_de_peca IS TRUE
    ";
    $res_t = pg_query($con, $sql_t);
    //Se tiver dois registros no result, quer dizer que um deles é 't' e outro 'f' já que o campo é boolean
    //Se tiver um registro e este for 't'
    //Nestes dois casos tem que digitar peça com serviço com troca de peça
    if (pg_num_rows($res) == 2 || (pg_num_rows($res) == 1 && pg_result($res, 0, troca_peca) == 't')) {
        if(pg_fetch_result($res_t,0,0) == 0){
            $msg_erro .= "Uma das soluções escolhidas exige troca de peça.<br>É necessário especificar a peça a ser trocada.";
        }
    }
    //Se tiver um registro e este for 'f' não pode digitar peça com serviço de troca de peça
    elseif (pg_num_rows($res) == 1 && pg_result($res, 0, troca_peca) == 'f') {
        if(pg_fetch_result($res_t,0,0) > 0){
            $msg_erro .= "Nenhuma das soluções escolhidas exige troca de peça.<br>Não pode existir peça que tenha serviço com troca de peça.";
        }
    }
}

if($login_fabrica == 90){ //HD 311795

	$recolhimento = $_POST['recolhimento'] == 'sim' ? 't' : 'f';
	$sql_r = "UPDATE tbl_os_extra SET recolhimento = '" . $recolhimento . "' WHERE os = " . $os;
	//var_dump($sql_r);
	$res_r = pg_query($con, $sql_r);
	$msg_erro = pg_errormessage($con);
	echo !empty($msg_erro) ? $msg_erro : '';
	
	//gravar aqui o campo reoperacao de gas HD 320946
	$reoperacao = $_POST['reop_gas'] == 'sim' ? 't' : 'f';
	$sql_r = "UPDATE tbl_os_extra SET reoperacao_gas = '" . $reoperacao . "' WHERE os = ". $os;
	$res_r = pg_query($con, $sql_r);
	$msg_erro = pg_errormessage($con);
	echo !empty($msg_erro) ? $msg_erro : '';
}

//HD 196225: Excluí algumas linhas abaixo que estavam comentadas. Caso precise, verificar arquivo em não_sync com diff

    if (strlen($msg_erro) == 0) {
    //echo "FAZZ-validacao ";
        $sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
        $res      = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        if($login_fabrica == 19 and strpos(pg_errormessage($con),"ERRO_VALOR_PECA_SUPERIOR") > 0) {
            $msg_alerta_peca = pg_errormessage($con);
        }

        //$msg_erro .= "SELECT fn_valida_os_item($os, $login_fabrica)";
        if (strlen($data_fechamento) > 0){
            if (strlen($msg_erro) == 0) {
                    $sql = "UPDATE tbl_os SET data_fechamento   = $xdata_fechamento
                            WHERE  tbl_os.os    = $os
                            AND    tbl_os.posto = $login_posto;";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);
            }
        }
    }
//exit;

    #Isso desabilita a intervencao
    /*
    if ($login_fabrica == 6 AND $login_posto <> 6359){
        $os_com_intervencao = "";
        $gravou_peca="";
    }
    */

    if (strlen($msg_erro) == 0 and $gravou_peca == "sim" and (in_array($login_fabrica, array(2,3,6,11,51)))) {

        // quando a peça estiver sob intervenção da assistencia técnica, $os_com_intervencao==t
        // então inseri um status 62 para bloquear a OS
        if ($os_com_intervencao == 't') {

            // envia email teste para avisar
            $sql_intervencao = "SELECT sua_os,
                                       to_char(data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                                       nome
                                  FROM tbl_os
                                  JOIN tbl_posto USING(posto)
                                 WHERE tbl_os.os = $os";

            $res_Y = pg_query($con,$sql_intervencao);

            $y_sua_os = pg_fetch_result($res_Y, 0, 'sua_os');
            $y_data   = pg_fetch_result($res_Y, 0, 'data_digitacao');
            $y_nome   = pg_fetch_result($res_Y, 0, 'nome');

            $sql_intervencao = "SELECT *
                                  FROM tbl_os_status
                                 WHERE os = $os
                                 ORDER BY data DESC LIMIT 1";

            $res_intervencao = pg_query($con, $sql_intervencao);
            $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'Peça da O.S. com intervenção da fábrica.')";

            if (pg_num_rows($res_intervencao) == 0) {

                $res = pg_query($con,$sql);

            } else {

                $status_os = pg_fetch_result($res_intervencao, 0, 'status_os');

                if ($status_os != 62) {
                    $res = pg_query($con,$sql);
                }

            }

        } else if ($os_bloqueada_garantia == 't') {

            // se a peça cadastrada estiver bloqueada para garantia, $os_bloqueada_garantia==t
            // então ele inseri o status 72 para bloquear a OS para o SAP
            //no caso se tiver peça bloqueada para garantia, deve justificar.

            $sql_intervencao = "SELECT *
                                  FROM tbl_os_status
                                 WHERE os = $os
                                 ORDER BY data DESC LIMIT 1";

            $res_intervencao = pg_query($con, $sql_intervencao);

            $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,72,current_timestamp,'Peça da OS bloqueada para garantia')";

            if (pg_num_rows($res_intervencao)== 0) {

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            } else {

                $status_os = pg_fetch_result($res_intervencao,0,'status_os');

                if ($status_os != 72) {

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                }

            }

        } else if ($login_fabrica == 11 AND $intervencao_previsao == 't') {

            $sql_intervencao = "SELECT *
                                  FROM tbl_os_status
                                 WHERE os = $os
                                 ORDER BY data DESC LIMIT 1";

            $res_intervencao = pg_query($con,$sql_intervencao);
            $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,72,current_timestamp,'Peça da OS com previsão para entrega superior a 15 dias')";

            if (pg_num_rows($res_intervencao)== 0) {

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            } else {

                $status_os = pg_fetch_result($res_intervencao, 0, 'status_os');

                if ($status_os != "72") {

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                }

            }

        } else if ($login_fabrica == 11 AND $os_com_intervencao_suprimentos == 't') {

            if ($login_posto == 6359 OR $login_posto == 6945 OR $login_posto == 1967) {

                $sql_intervencao = "SELECT *
                                      FROM tbl_os_status
                                     WHERE os = $os
                                       AND status_os IN (87,88)
                                     ORDER BY data DESC LIMIT 1";

                $res_intervencao = pg_query($con,$sql_intervencao);

                $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,87,current_timestamp,'OS com intervenção de suprimentos')";

                if (pg_num_rows($res_intervencao)== 0) {

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                } else {

                    $status_os = pg_fetch_result($res_intervencao, 0, 'status_os');

                    if ($status_os != 87) {

                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                    }

                }

            }

        } else if ($login_fabrica == 3 AND $os_com_intervencao_carteira == 't') { /* 35521 */

            $sql_intervencao = "SELECT *
                                  FROM tbl_os_status
                                 WHERE os=$os
                                   AND status_os IN (116,117)
                                 ORDER BY data DESC LIMIT 1";

            $res_intervencao = pg_query($con, $sql_intervencao);

            $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,116,current_timestamp,'OS com intervenção de Carteira')";

            if (pg_num_rows($res_intervencao) == 0) {

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            } else {

                $status_os = pg_fetch_result($res_intervencao,0,'status_os');

                if ($status_os!=116){
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }

            }

        } else if ($login_fabrica == 3) {

            $sqld = "SELECT current_date - data_abertura,
                            qtde_dias_intervencao_sap
                       FROM tbl_os
                  LEFT JOIN tbl_fabrica USING (fabrica)
                      WHERE os = $os";

            $resd = pg_query($con, $sqld);
            $data_aberturax = pg_fetch_result($resd,0,0);
            $qtde_dias_intervencao_sap = pg_fetch_result($resd, 0, 'qtde_dias_intervencao_sap');

            // HD 34210
            if ($data_aberturax >= $qtde_dias_intervencao_sap) {

                $sql_intervencao = "SELECT *
                                      FROM tbl_os_status
                                     WHERE os = $os
                                     ORDER BY data DESC LIMIT 1";

                $res_intervencao = pg_query($con,$sql_intervencao);

                $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,72,current_timestamp,'Pedido de Peças a mais de $qtde_dias_intervencao_sap dias.')";

                if (pg_num_rows($res_intervencao) == 0) {

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                    $peca_mais_30_dias = 't';

                } else {

                    $status_os = pg_fetch_result($res_intervencao, 0, 'status_os');

                    if ($status_os != 72) {

                        $res = pg_query($con, $sql);
                        $msg_erro .= pg_errormessage($con);
                        $peca_mais_30_dias = 't';

                    }

                }

            }

        }

    }

    ############# MARCA ESMALTEC HD 27561 #################
    if ($login_fabrica == 30) {

        $fogao              = strtoupper(trim($_POST['fogao']));
        $marca_fogao        = strtoupper(trim($_POST['marca_fogao']));

        $refrigerador       = strtoupper(trim($_POST['refrigerador']));
        $marca_refrigerador = strtoupper(trim($_POST['marca_refrigerador']));

        $bebedouro          = strtoupper(trim($_POST['bebedouro']));
        $marca_bebedouro    = strtoupper(trim($_POST['marca_bebedouro']));

        $microondas         = strtoupper(trim($_POST['microondas']));
        $marca_microondas   = strtoupper(trim($_POST['marca_microondas']));

        $lavadoura          = strtoupper(trim($_POST['lavadoura']));
        $marca_lavadoura    = strtoupper(trim($_POST['marca_lavadoura']));

        $escolheu = 0;

        if (strlen($fogao) > 0 AND strlen($marca_fogao) == 0) {
            $msg_erro .= "Escolha a marca do fogão";
        }
        if (strlen($fogao) > 0 AND strlen($marca_fogao) > 0) {$escolheu++;}

        if (strlen($refrigerador) > 0 AND strlen($marca_refrigerador) == 0) {
            $msg_erro .= "Escolha a marca do refrigerador";
        }
        if (strlen($refrigerador) > 0 AND strlen($marca_refrigerador) > 0) {$escolheu++;}

        if (strlen($bebedouro) > 0 AND strlen($marca_bebedouro) == 0) {
            $msg_erro .= "Escolha a marca do bebedouro";
        }
        if (strlen($bebedouro) > 0 AND strlen($marca_bebedouro) > 0) {$escolheu++;}

        if (strlen($microondas) > 0 AND strlen($marca_microondas) == 0) {
            $msg_erro .= "Escolha a marca do microondas";
        }
        if (strlen($microondas) > 0 AND strlen($marca_microondas) > 0) {$escolheu++;}

        if (strlen($lavadoura) > 0 AND strlen($marca_lavadoura) == 0){
            $msg_erro .= "Escolha a marca da lavadoura";
        }
        if (strlen($lavadoura) > 0 AND strlen($marca_lavadoura) > 0) {$escolheu++;}

        if (strlen($msg_erro) == 0 AND $escolheu > 0) {

            $marcas = $fogao . ";" . $marca_fogao . ";" . $refrigerador . ";" . $marca_refrigerador . ";" . $bebedouro . ";" . $marca_bebedouro . ";" . $microondas . ";" . $marca_microondas . ";" . $lavadoura . ";" . $marca_lavadoura;

            $sqlm = " UPDATE tbl_os_extra SET
                             obs_adicionais = '$marcas'
                       WHERE os = $os";

            $resm = pg_query($con,$sqlm);
            $msg_erro .= pg_errormessage($con);

        }

    }
    #######################################################


    //HD 4291-Paulo
    if ($login_posto == 4311) {

        $prateleira_box = strtoupper(trim($_POST['prateleira_box']));

        if (strlen($prateleira_box) == 0) $prateleira_box = " ";
        if (strlen($msg_erro) == 0 and strlen($prateleira_box) > 0) {

            $sql= "UPDATE tbl_os
                      SET prateleira_box = '$prateleira_box'
                    WHERE os = $os
                        AND posto = $login_posto";

            $res = pg_query($con,$sql);

            $msg_erro .= pg_errormessage($con);

        }

    }

	//HD 255530: Colocar OS em intervenção para a Nova Computadores
	if ($login_fabrica == 43 && strlen($msg_erro) == 0) {
		//Verifica se a OS já está em intervenção
		//Seleciona o último status dentre os status de invervenção e verifica se
		//é diferente de 64 - Aprovado

		//HD 283312: Arrumei a query, estava errado na cláusula WHERE "AND os_status" estava "AND status_os" e na subquery também.
		$sql = "
		SELECT
		status_os

		FROM
		tbl_os_status

		WHERE
		os=$os
		AND os_status=(
			SELECT MAX(os_status)
			FROM tbl_os_status
			WHERE status_os IN (62,64,65,127)
			AND os=$os
		)
		AND status_os<>64
		";
		//HD 283312: FIM
		$res = pg_query($con, $sql);

		//Se veio resultado, quer dizer que o último status de intervenção não é
		//64 - Aprovado, então já está em intervenção
		if (pg_num_rows($res)) {
		}
		else {
			//OS com mais de 3 peças
			$sql = "
			SELECT
			tbl_os_item.os_item

			FROM
			tbl_os_produto
			JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
			JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado

			WHERE
			tbl_os_produto.os=$os
			AND tbl_servico_realizado.gera_pedido IS TRUE
			";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) >= 3) {
				$sql = "
				INSERT INTO
				tbl_os_status (
				os,
				status_os,
				observacao
				)

				VALUES (
				$os,
				62,
				'OS com mais de 3 peças'
				)
				";
				$res = pg_query($con, $sql);
			}
			//Se não é intervenção por conta de ser com mais de 3 peças verifica
			//OS Reincidente com pedido da mesma peça da OS anterior
			else {
				$sql = "
				SELECT
				tbl_os_extra.os_reincidente

				FROM
				tbl_os
				JOIN tbl_os_extra ON tbl_os.os=tbl_os_extra.os

				WHERE
				tbl_os.os=$os
				AND tbl_os.os_reincidente IS TRUE
				AND tbl_os_extra.os_reincidente IS NOT NULL
				";
				$res = pg_query($con, $sql);

				//É reincidente
				if (pg_num_rows($res) > 0) {
					$os_reincidente = pg_result($res, 0, os_reincidente);
					
					$sql = "
					SELECT
					tbl_os_item.os_item

					FROM
					tbl_os_item
					JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
					JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado=tbl_os_item.servico_realizado
						 AND tbl_servico_realizado.gera_pedido IS TRUE
					JOIN tbl_os_item AS os_item_reincidente ON tbl_os_item.peca=os_item_reincidente.peca
					JOIN tbl_os_produto AS os_produto_reincidente ON os_item_reincidente.os_produto=os_produto_reincidente.os_produto
					JOIN tbl_servico_realizado AS servico_realizado_reincidente ON os_item_reincidente.servico_realizado=servico_realizado_reincidente.servico_realizado
						 AND servico_realizado_reincidente.gera_pedido IS TRUE

					WHERE
					tbl_os_produto.os=$os
					AND os_produto_reincidente.os=$os_reincidente
					";
					$res = pg_query($con, $sql);

					//A OS atual e a reincidente tem peças em comum
					if (pg_num_rows($res) > 0) {
						$sql = "
						INSERT INTO
						tbl_os_status (
						os,
						status_os,
						observacao
						)

						VALUES (
						$os,
						62,
						'OS reincidente e com pedido da mesma peça'
						)
						";
						$res = pg_query($con, $sql);
					}
				}
			}
		}

		//Como está dentro de uma transaction, se der um erro o sistema vai retornar erros
		//para todos os demais comandos SQL
		if (pg_errormessage($con)) {
			$msg_erro = "Falha na rotina de intervenção de OS. Contate o fabricante.";
		}
	}
	//HD 255530 FIM

	if (strlen($msg_erro) == 0) {

        $res = pg_query($con,"COMMIT TRANSACTION");

        if ($login_fabrica <> 2 AND $login_fabrica <> 51 AND ($os_bloqueada_garantia == 't' OR $peca_mais_30_dias == 't' OR $os_com_intervencao == 't' OR $intervencao_previsao == 't' OR $os_com_intervencao_carteira == 't')) {

            if ($login_fabrica <> 11) {
                header("Location: os_justificativa_garantia.php?os=$os");
            } else {
                echo "<script>window.location = 'os_justificativa_garantia.php?os=$os'</script>";
            }

        } else {

            if ($login_fabrica == 3 AND $btn_imprimir == 'imprimir') {

                header("Location: os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar");

            } else {

                if ($login_fabrica <> 11) {
                    header("Location: os_finalizada.php?os=$os&");
                } else {
                    echo "<script>window.location = 'os_finalizada.php?os=$os'</script>";
                }

            }

        }

        exit;

    } else {

        $res = pg_query($con,"ROLLBACK TRANSACTION");

    }

}

#HD 276459 visita_agendada
if (strlen($os) > 0) {
    #----------------- Le dados da OS --------------
    $sql = "SELECT  tbl_os.*                       ,
                    tbl_produto.produto            ,
                    tbl_produto.referencia         ,
                    tbl_produto.descricao          ,
                    tbl_produto.voltagem           ,
                    tbl_produto.linha              ,
                    tbl_produto.familia            ,
                    tbl_produto.troca_obrigatoria  ,
                    tbl_linha.nome AS linha_nome   ,
                    tbl_posto_fabrica.codigo_posto ,
                    tbl_os_extra.orientacao_sac    ,
                    tbl_os_extra.os_reincidente AS reincidente_os,
                    tbl_os.prateleira_box                        ,
					to_char(tbl_os.visita_agendada::date,'dd/mm/yyyy') as visita_agendada2,
                    tbl_os_extra.obs_adicionais,
                    tbl_defeito_constatado.descricao as defeito_descricao,
                    tbl_linha.informatica
            FROM    tbl_os
            JOIN    tbl_os_extra USING (os)
            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
            JOIN    tbl_posto USING (posto)
            JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                                      AND tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN    tbl_produto USING (produto)
            LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
            WHERE   tbl_os.os = $os";
    $res = pg_query($con,$sql) ;

    $defeito_constatado = pg_fetch_result($res,0,defeito_constatado);
    $defeito_descricao  = pg_fetch_result($res,0,defeito_descricao);
    $aparencia_produto    = pg_fetch_result($res,0,aparencia_produto);
    $acessorios            = pg_fetch_result($res,0,acessorios);
    $causa_defeito      = pg_fetch_result($res,0,causa_defeito);
    $linha              = pg_fetch_result($res,0,linha);
    $informatica        = pg_fetch_result($res,0,informatica);
    $linha_nome         = pg_fetch_result($res,0,linha_nome);
    $consumidor_nome    = pg_fetch_result($res,0,consumidor_nome);
    $sua_os             = pg_fetch_result($res,0,sua_os);
    $type               = pg_fetch_result($res,0,type);
    $produto_os         = pg_fetch_result($res,0,produto);
    $produto_referencia = pg_fetch_result($res,0,referencia);
    $produto_descricao  = pg_fetch_result($res,0,descricao);
    $produto_voltagem   = pg_fetch_result($res,0,voltagem);
    if ($login_fabrica == 15) {
        $produto_serie      = strtoupper(pg_fetch_result($res,0,serie));
    } else {
        $produto_serie      = pg_fetch_result($res,0,serie);
    }
    $produto_serie_db   = pg_fetch_result($res,0,serie);
    $qtde_produtos      = pg_fetch_result($res,0,qtde_produtos);
    $obs                = pg_fetch_result($res,0,obs);
    $codigo_posto       = pg_fetch_result($res,0,codigo_posto);
    $defeito_reclamado  = pg_fetch_result($res,0,defeito_reclamado);
    $defeito_reclamado_descricao_os = pg_fetch_result($res,0,defeito_reclamado_descricao);
    $os_reincidente     = pg_fetch_result($res,0,reincidente_os);
    $consumidor_revenda = pg_fetch_result($res,0,consumidor_revenda);
    $solucao_os         = pg_fetch_result($res,0,solucao_os);
    $tecnico_nome       = pg_fetch_result($res,0,tecnico_nome);
    $tecnico            = pg_fetch_result($res,0,tecnico);
    $codigo_fabricacao  = pg_fetch_result($res,0,codigo_fabricacao);
    $valores_adicionais = pg_fetch_result($res,0,valores_adicionais);
    $justificativa_adicionais = pg_fetch_result($res,0,justificativa_adicionais);
    $qtde_km            = pg_fetch_result($res,0,qtde_km);
    $produto_familia    = pg_fetch_result($res,0,familia);
    $produto_linha      = pg_fetch_result($res,0,linha);
    $troca_obrigatoria  = pg_fetch_result($res,0,troca_obrigatoria);
    $fabricacao_produto = pg_fetch_result($res,0,fabricacao_produto);
    $defeito_constatado_grupo =  pg_fetch_result($res,0,defeito_constatado_grupo);
    //hd 24288
    //$autorizacao_domicilio = pg_fetch_result($res,0,autorizacao_domicilio);

    $orientacao_sac    = pg_fetch_result($res,0,orientacao_sac);
#    $orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
#    $orientacao_sac = str_replace ("<br />","",$orientacao_sac);

//HD 4291 Paulo
    if($login_posto==4311){
        $prateleira_box = pg_fetch_result($res,0, prateleira_box);
    }
	#HD 276459 visita_agendada
    if($login_fabrica==30){//HD 27561
        $obs_adicionais = pg_fetch_result($res,0, obs_adicionais);
        $visita_agendada = pg_fetch_result($res,0, visita_agendada2);
        if (strlen($produto_serie)==0) {
            $produto_serie = $xproduto_serie;
        }

        $obs_adicionais = explode(";", $obs_adicionais);

        $fogao               = $obs_adicionais[0];
        $marca_fogao         = $obs_adicionais[1];
        $refrigerador        = $obs_adicionais[2];
        $marca_refrigerador  = $obs_adicionais[3];
        $bebedouro           = $obs_adicionais[4];
        $marca_bebedouro     = $obs_adicionais[5];
        $microondas          = $obs_adicionais[6];
        $marca_microondas    = $obs_adicionais[7];
        $lavadoura           = $obs_adicionais[8];
        $marca_lavadoura     = $obs_adicionais[9];
    }

    if (($login_fabrica==3 or $login_fabrica == 45 or $login_fabrica == 24) AND $login_posto==6359){
        $sql = "SELECT orcamento,
                total_mao_de_obra,
                aprovado,
                TO_CHAR(data_aprovacao,'DD/MM/YYYY HH24:MI')  AS data_aprovacao,
                TO_CHAR(data_reprovacao,'DD/MM/YYYY HH24:MI') AS data_reprovacao,
                motivo_reprovacao
            FROM tbl_orcamento
            WHERE os = $os
            AND empresa = $login_fabrica";
        $res = pg_query($con,$sql) ;
        if (pg_num_rows($res)>0){
            $orcamento            = pg_fetch_result($res,0,orcamento);
            $valor_mo_orcamento = pg_fetch_result($res,0,total_mao_de_obra);
            $aprovado            = pg_fetch_result($res,0,aprovado);
            $data_aprovacao        = pg_fetch_result($res,0,data_aprovacao);
            $data_reprovacao    = pg_fetch_result($res,0,data_reprovacao);
            $motivo_reprovacao    = pg_fetch_result($res,0,motivo_reprovacao);
            $valor_mo_orcamento = number_format($valor_mo_orcamento,2,',','.');
        }
    }


    if (strlen($os_reincidente) > 0) {
        $sql = "SELECT tbl_os.sua_os
                FROM   tbl_os
                WHERE  tbl_os.os      = $os_reincidente
                AND    tbl_os.fabrica = $login_fabrica
                AND    tbl_os.posto   = $login_posto;";
        $res = pg_query($con,$sql) ;

        if (pg_num_rows($res) > 0) $sua_os_reincidente = trim(pg_fetch_result($res,0,sua_os));
    }
}

#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto  ,
                tbl_fabrica.pergunta_qtde_os_item,
                tbl_fabrica.os_item_serie        ,
                tbl_fabrica.os_item_aparencia    ,
                tbl_fabrica.qtde_item_os
        FROM    tbl_fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_query($con,$sql);
/*takashi hd 1874 -  nao sei por que mas estava pg_num_rows($res), chegava em alguns campos como vazio, nao sei como nunca ninguem reclamou... vamosver o que acontece..*/
if (pg_num_rows($resX) > 0) {
    $os_item_subconjunto = pg_fetch_result($resX,0,os_item_subconjunto);
    if (strlen($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

    $pergunta_qtde_os_item = pg_fetch_result($resX,0,pergunta_qtde_os_item);
    if (strlen($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';

    $os_item_serie = pg_fetch_result($resX,0,os_item_serie);
    if (strlen($os_item_serie) == 0) $os_item_serie = 'f';

    $os_item_aparencia = pg_fetch_result($resX,0,os_item_aparencia);
    if (strlen($os_item_aparencia) == 0) $os_item_aparencia = 'f';

    $qtde_item = pg_fetch_result($resX,0,qtde_item_os);
    if (strlen($qtde_item) == 0) $qtde_item = 5;

    if($login_fabrica == 45) {
        $sql="SELECT qtde_os_item
                FROM tbl_posto_fabrica
                where posto   = $login_posto
                and   fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $qtde_item    = pg_fetch_result($res,0,qtde_os_item);
    }

}

$resX = pg_query($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica");
$posto_item_aparencia = pg_fetch_result($resX,0,0);

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus(); listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value); ";

$layout_menu = 'os';
include "cabecalho.php";

if($login_fabrica==3){
    if (strlen($_POST['os'])>0) $os = $_POST['os'];
    else                       $os = $_GET['os'];

    $sql ="select
            sua_os
        from tbl_os
        join tbl_os_troca using(os)
        where os = $os
        AND   data_conserto IS NOT NULL";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)>0){
        $sua_os = pg_fetch_result($res,0,sua_os);
        echo "<P>";
            echo "<B>OS<FONT SIZE='3' COLOR='#FF0033'>&nbsp;$sua_os&nbsp;</FONT>com troca de produto não pode ser alterada.</B>";
        echo "</P>";
        exit;
    }
}

if($login_fabrica==35){ // HD 112977
    if (strlen($_POST['os'])>0) $os = $_POST['os'];
    else                       $os = $_GET['os'];
    $sql ="SELECT
            sua_os
        FROM tbl_os
        JOIN tbl_os_troca using(os)
        WHERE os = $os";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)>0){
        $sua_os = pg_fetch_result($res,0,sua_os);
        echo "<P>";
            echo "<b>OS<FONT SIZE='3' COLOR='#FF0033'>&nbsp;$sua_os&nbsp;</FONT>com troca de produto não pode ser alterada.</B>";
        echo "</P>";
        exit;
    }
}

$imprimir        = $_GET['imprimir'];
$qtde_etiquetas  = $_GET['qtde_etiq'];

if (strlen($os) == 0) $os = $_GET['os'];

if (strlen($imprimir) > 0 AND strlen($os) > 0 ) {
    echo "<script language='javascript'>";
    echo "window.open ('os_print.php?os=$os&qtde_etiquetas=$qtde_etiquetas','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
    echo "</script>";
}

//  03/02/2010 MLG HD - 201678 (voltando alteração feita 13/11/09)
$gambiara_esmaltec = 'waldir/samuel';
include "javascript_pesquisas.php";
include "javascript_calendario_new.php";

?>


<?
# Se a OS for uma OS de revenda, entra
if (strlen($os_revenda)>0 or $login_fabrica==3){
?>
<script type="text/javascript">

    function EscondeDiv(x){
        var campo = document.getElementById('retorno_serie_'+x);
        campo.style.display = "none";
    }
var http3 = new Array();
function atualizaserietrocada(os_item, serietrocada,x){

    os_item      = document.getElementById(os_item).value;
    serietrocada = document.getElementById(serietrocada).value;

    var curDateTime = new Date();
    http3[curDateTime] = createRequestObject();
    var campo = document.getElementById('retorno_serie_'+x);

    if (!campo) {
        return;
    }
/*
    if (campo.style.display=="block"){
        campo.style.display = "none";
    }else{
        campo.style.display = "block";
    }
*/


    url = "<?$PHP_SELF;?>?atualiza_serie_trocada=true&os_item="+os_item+"&serie_trocada="+serietrocada;
    http3[curDateTime].open('get',url);
    http3[curDateTime].onreadystatechange = function(){
        if(http3[curDateTime].readyState == 1) {
            campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
        }
        if (http3[curDateTime].readyState == 4){
            if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
                var results = http3[curDateTime].responseText;
                campo.innerHTML = results;
                campo.style.display = "block";
                window.setTimeout('EscondeDiv('+x+')',2000);
            }else {
                alert('Ocorreu um erro');
            }
        }
    }
    http3[curDateTime].send(null);

}

function mostraDomicilio(campo,destino){
    if(campo.checked){
        document.getElementById(destino).style.display = "block";
    }else{
        document.getElementById(destino).style.display = "none";
    }
}

function checarNumero(campo){
    var num = campo.value.replace(",",".");
    campo.value = parseFloat(num).toFixed(2);
    if (campo.value=='NaN') {
        campo.value='';
    }
}

$().ready(function() {
    $("select[rel=servicos_realizados]").focus(function(){
        var campo = $(this);
        if  ( $("input[name='peca_"+campo.attr("alt")+"']").val() !='' ){
            campo.html('<option value="">Aguarde . . . . . . . .</option>');
            $.post('<? echo $PHP_SELF; ?>',
                { buscaServicoRealizado : $("input[name='peca_"+campo.attr("alt")+"']").val()
                    <? if (strlen($os_revenda)==0){ echo ", os : '$os'";}?>
                },
                function(resposta){
                    retorno = resposta.split("||");
                    campo.html(retorno[1]);
                }
            );
        }
    });
    $("select[rel=servicos_realizados]").change(function(){
        var campo = $(this);
        if (campo.val()=='643' || campo.val()=='644'){
            $("#orcamento_mostra_"+campo.attr("alt")).show();
            $("#orcamento_mao_obra").show();
        }else{
            $("#orcamento_mostra_"+campo.attr("alt")).hide();
        }
    });
    
});

</script>

<?}?>

<? if ($login_fabrica==3 and 1==2){ ?>
<script type="text/javascript">
    $().ready(function() {
        $("select[rel=servicos_realizados]").change(function(){
            var campo = $(this);
            if ( $("input[name='peca_"+campo.attr("alt")+"']").val() == '073894' || $("input[name='peca_"+campo.attr("alt")+"']").val() == '073894' ) {
                if  ( campo.val() != '692' ){ // Recarga de Gás
                    alert('Para esta peça, o serviço deve ser Troca de Gás.');
                    // selectIndex()
                    //selectIndeOf()
                }
            }
        });
    });
</script>
<?}?>


<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_produto.js'></script>
<script language='javascript' src='js/bibliotecaAJAX.js'></script>

<script language="JavaScript">

//HD 276459 visita_agendada
function gravaVisita(os,data) {
	
	if (data.length > 0 && data != '__/__/____' ) {
		var url = 'ajax_grava_visita.php?os='+os+'&data='+data;
		requisicaoHTTP('GET',url,true,'retorno_visita');
	} else {
		alert('Digite uma data válida');
	}
}

function retorno_visita(dados) {
	var tratar_dados = dados.split('|');
	alert(tratar_dados[1]);
}


$().ready(function() {

    <? if ($login_fabrica == 15) { ?>
        ocultaLinhas();
    
    <?}?>

})


<?
if ($login_fabrica == 15) {

    if (strlen($os) > 0) {
        $sql = "SELECT count(os_item) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os=$os";
        $res_count = pg_query($con, $sql);
        $linhas_inicial = pg_result($res_count, 0, 0);
        
        $linhas_inicial = $linhas_inicial + 5;
        if ($linhas_inicial < 10) {
            $linhas_inicial = 10;
        }
    }
    else {
        $linhas_inicial = 10;
    }
?>
    function ocultaLinhas() {
        var qtde = $('#qtde_mostrar').val();

        for (i=<?echo $linhas_inicial;?>;i<=40;i++) {
            $('#mostrar_'+i).css('display', 'none');
        }
    }


    function mostrarLinhas(qtde) {
        navegador = navigator.appName;
        versao = parseInt(navigator.appVersion);
        if (navegador == 'Microsoft Internet Explorer') {
            var strBlock = 'block';
        }else {
            var strBlock = 'table-row';
        }
        var qtde = qtde;
        for (i=10;i<qtde;i++) {
            $('#mostrar_'+i).css('display', strBlock);
        }
    }
<?}?>


$().ready(function() {
    $('#produto_serie').blur(function () {
                         var serie = $(this).val();
                         $(this).val(serie.toUpperCase());
    });
});
//	#HD 276459 visita_agendada
$(function(){
        $("#fabricacao_produto").maskedinput("99/9999");
		$("#visita_agendada").maskedinput("99/99/9999");
        $("#data_envio").maskedinput("99/99/9999");
    });


function trim(str){
    while(str.charAt(0) == (" ") ){
        str = str.substring(1);
    }
    while(str.charAt(str.length-1) == " " ){
        str = str.substring(0,str.length-1);
    }
    return str;
}

function abreComunicadoPeca(i){
    var referencia = document.getElementById('peca_'+i).value;
    url = "pesquisa_comunicado_peca.php?referencia=" + referencia;
    window.open(url,"Comunicado","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
}

var http5 = new Array();

function checarComunicado(i){
    var imagem     = document.getElementById('imagem_comunicado_'+i);
    var referencia = document.getElementById('peca_'+i).value;
    imagem.style.visibility = "hidden";
    imagem.title = "Não há comunicados para esta peça.";
    if (referencia.length > 0){
        var curDateTime = new Date();
        http5[curDateTime] = createRequestObject();
        url = "os_item_comunicado_ajax.php?referencia="+escape(referencia);
        http5[curDateTime].open('get',url);
        http5[curDateTime].onreadystatechange = function(){
            if (http5[curDateTime].readyState == 4)
            {
                if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304)
                {
                    var response = http5[curDateTime].responseText;
                    if (response=="ok"){
                        imagem.title = "Há comunicados para esta peça. Clique aqui para ler.";
                        imagem.style.visibility = "visible";
                    }
                    else {
                        imagem.title = "Não há comunicados para esta peça.";
                        imagem.style.visibility = "hidden";
                    }
                }
            }
        }
        http5[curDateTime].send(null);
    }
}

<?php
#HD 307418
?>
function limpaDefeito(i){
	$('#defeito_'+i).html('<option id="op_'+i+'"></option>');
}

    function atualizaQtde(campo,campo2){
        if(campo && campo2){
            if ( campo.value.length == 0){
                campo2.value = '';
            }
            if ( campo.value.length > 0 && campo2.value.length == 0 ){
                campo2.value = 1;
            }
        }
    }


    function fnc_troca(os){
        alert('A troca de produto deve ser feita somente quando o reparo do produto necessita de troca de peças.');
        if (confirm('A Fábrica irá fazer a troca do produto. Confirmar a troca?')){
            window.location='<?=$PHP_SELF?>?os='+os+'&troca=1';
        }
    }


//funcao lista basica tectoy, posicao, serie inicial, serie final
function fnc_pesquisa_lista_basica2 (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {

        var url = "";

        if (tipo == "tudo") {
            url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
            url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
            url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");

        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.preco      = peca_preco;
        janela.qtde       = peca_qtde;
        janela.focus();

}

function fnc_pesquisa_lista_basica_suggar(produto_referencia, peca_referencia, peca_descricao, peca_posicao, peca_preco, voltagem, tipo, kit_peca) {

        var url = "";

        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value+"&kit_peca="+kit_peca.value+"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value +"&kit_peca="+kit_peca.value+"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");

        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.posicao    = peca_posicao;
        janela.preco      = peca_preco;
        janela.qtde       = '';//HD 258901
        janela.kit_peca   = kit_peca;
        janela.focus();

}

function fnc_pesquisa_lista_basica_latina (produto_referencia, peca_referencia, peca_descricao,  peca_preco, voltagem, tipo, serie, kit_peca) {

        var url = "";

        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&serie=" + serie.value + "&kit_peca="+kit_peca.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&serie=" + serie.value + "&kit_peca="+ kit_peca.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");

        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.preco      = peca_preco;
        janela.focus();

}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn = new Array();

function pega_peca(os,referencia,descricao) {
    var ref = document.getElementById(referencia).value;
    if(document.getElementById(referencia).value.length > 0){
        url = "<?=PHP_SELF?>?ajax=peca&referencia="+ref+"&os="+os;
        var curDateTime = new Date();
        http_forn[curDateTime] = createRequestObject();
        http_forn[curDateTime].open('GET',url,true);
        http_forn[curDateTime].onreadystatechange = function(){
            if (http_forn[curDateTime].readyState == 4)
            {
                if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                {
                    var response = http_forn[curDateTime].responseText.split("|");
                    if (response[0]=="ok"){
                        document.getElementById(descricao).value = response[1];
                    }
                }
            }
        }
        http_forn[curDateTime].send(null);
    }
}
function pega_dc(os,id,referencia,descricao){
    var ref = document.getElementById(referencia).value;
    if(document.getElementById(referencia).value.length > 0){
        url = "<?=$PHP_SELF?>?ajax=defeito_constatado&defeito_constatado="+ref+"&os="+os;

        var curDateTime = new Date();
        http_forn[curDateTime] = createRequestObject();
        http_forn[curDateTime].open('GET',url,true);
        http_forn[curDateTime].onreadystatechange = function(){
            if (http_forn[curDateTime].readyState == 4)
            {
                if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                {
                    var response = http_forn[curDateTime].responseText.split("|");
                    if (response[0]=="ok"){
                        document.getElementById(id).value = response[1];
                        document.getElementById(referencia).value = response[2];
                        document.getElementById(descricao).value = response[3];
                    }
                }
            }
        }
        http_forn[curDateTime].send(null);
    }
}
function fnc_pesquisa_dc (os, defeito_constatado, defeito_constatado_codigo, defeito_constatado_descricao) {
    var url = "";
    if (defeito_constatado != '') {
        url = "<?$PHP_SELF?>?defeito=defeito&os=" + os+"&defeito_constatado_codigo="+defeito_constatado_codigo.value+"&defeito_constatado_descricao="+defeito_constatado_descricao.value;

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.defeito_constatado           = defeito_constatado          ;
        janela.defeito_constatado_codigo    = defeito_constatado_codigo   ;
        janela.defeito_constatado_descricao = defeito_constatado_descricao;
        janela.focus();
    }
}

function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.preco      = peca_preco;
        janela.qtde       = peca_qtde;
        janela.focus();

}

function fnc_pesquisa_peca_lista_sub (produto_referencia, peca_posicao, peca_referencia, peca_descricao) {
    var url = "";
    if (produto_referencia != '') {
        url = "peca_pesquisa_lista_subconjunto.php?produto=" + produto_referencia;
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto    = produto_referencia;
        janela.posicao    = peca_posicao;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.focus();
    }
}

/* FUNÇÃO PARA INTELBRAS E SUGGAR - POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {

    var url = "";

    if (tipo == "tudo") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
    }

    if (tipo == "referencia") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
    }

    if (tipo == "descricao") {
        url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim";
    }

    if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");

        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.posicao    = peca_posicao;
        janela.preco      = '';//HD 258901
        janela.focus();

    } else {

        alert("Digite pelo menos 4 caracteres!");

    }

}

function fnc_pesquisa_lista_serie (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista_serie.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista_serie.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista_serie.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.focus();
}

/* FUNÇÃO PARA buscar número de série e referencia do produto*/
function fnc_pesquisa_peca_serie (serie,peca_referencia,peca_descricao) {

    var url = "peca_pesquisa_serie.php?serie=" + serie;

    if (serie.length > 0 ) {

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.focus();

    } else {

        alert("Digite o número de série!");

    }

}

function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {

    try {
        ajax = new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {

        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) {
            try {
                ajax = new XMLHttpRequest();
            } catch(exc) {
                alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
            }
        }

    }

    if (ajax) {
        document.forms[0].solucao_os.options.length = 1;
        idOpcao  = document.getElementById("opcoes");
        ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
        ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        ajax.onreadystatechange = function() {
            if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
            if(ajax.readyState == 4 ) {
                if(ajax.responseXML) {
                    montaComboSolucao(ajax.responseXML);
                } else {
                    idOpcao.innerHTML = "Selecione a solucao";
                }
            }
        }
        var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
        ajax.send(null);
    }
}

function listaSolucaoGrupo(defeito_constatado_grupo) {
    try {
        ajax = new ActiveXObject("Microsoft.XMLHTTP");
    }
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) {
            try {
                ajax = new XMLHttpRequest();
            }
            catch(exc) {
                alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
            }
        }
    }
    if(ajax) {
        var idOpcao = document.getElementById('defeito_constatado');
//        alert("ajax_defeito_constatado_grupo.php?defeito_constatado_grupo="+defeito_constatado_grupo+"&os="+os);
        ajax.open("GET", "ajax_defeito_constatado_grupo.php?defeito_constatado_grupo="+defeito_constatado_grupo);
        ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        ajax.onreadystatechange = function() {
            if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
            if(ajax.readyState == 4 ) {
                if(ajax.responseXML) {
                    montaComboConstatado(ajax.responseXML,idOpcao);
                }
            }
        }
        var params = "defeito_constatado_grupo="+defeito_constatado_grupo;
        ajax.send(null);
    }
}

function montaComboSolucao(obj){
    var dataArray   = obj.getElementsByTagName("produto");
    if(dataArray.length > 0) {
        for(var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
            idOpcao.innerHTML = "";
            var novo = document.createElement("option");
            novo.setAttribute("id", "opcoes");
            novo.value = codigo;
            novo.text  = nome;
            document.forms[0].solucao_os.options.add(novo);
        }
    } else {
        idOpcao.innerHTML = "Nenhuma solução encontrada";
    }
}

// Defeito Constatado - Combo
function listaConstatado(linha,familia, defeito_reclamado,defeito_constatado) {
	
    try {
        ajax = new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {
        try {
            ajax = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(ex) {
            try {
                ajax = new XMLHttpRequest();
            } catch(exc) {
                alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
            }
        }
    }

    if (ajax) {
        defeito_constatado.options.length = 1;
        idOpcao  = document.getElementById("opcoes2");
        ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);
        ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        ajax.onreadystatechange = function() {
            if(ajax.readyState == 1) {
                idOpcao.innerHTML = "Carregando...!";
            }
            if (ajax.readyState == 4) {
                if (ajax.responseXML) {
                    montaComboConstatado(ajax.responseXML,defeito_constatado);
                } else {
                    idOpcao.innerHTML = "Selecione o defeito constatado";
                }
            }
        }
        ajax.send(null);
    }

}

function montaComboConstatado(obj,defeito_constatado) {

    var dataArray = obj.getElementsByTagName("produto");

    if (dataArray.length > 0) {

        var novo = document.createElement("option");
        novo.setAttribute("id", "opcoes2");
        var defeito = "<? echo $defeito_descricao;?>";

        if (defeito.length > 0) {;
            novo.text  = defeito;
            defeito_constatado.options.add(novo);
            defeito_constatado.options[0].value="<? echo $defeito_constatado;?>";
        } else {
            novo.text  = "Selecione o Defeito";
            defeito_constatado.options.add(novo);
        }

        for (var i = 0 ; i < dataArray.length ; i++) {
            //percorre o arquivo XML paara extrair os dados
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
            var novo = document.createElement("option");
            novo.setAttribute("id", "opcoes2");
            novo.value = codigo;
            novo.text  = nome;
            defeito_constatado.options.add(novo);//adiciona
        }

    } else {
        defeito_constatado.innerHTML = "Selecione o defeito";
    }

}

function listaDefeitos(valor) {
    //verifica se o browser tem suporte a ajax
    try {
        ajax = new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {
        try {
            ajax = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(ex) {
            try {
                ajax = new XMLHttpRequest();
            } catch(exc) {
                alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
            }
        }
    }
    //se tiver suporte ajax
    if (ajax) {
        //deixa apenas o elemento 1 no option, os outros são excluídos
        document.forms[0].defeito_reclamado.options.length = 1;
        //opcoes é o nome do campo combo
        idOpcao = document.getElementById("opcoes");
        //ajax.open("POST", "ajax_produto.php", true);
        ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
        ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        ajax.onreadystatechange = function() {
            if (ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
            if (ajax.readyState == 4 ) {
                if(ajax.responseXML) {
                    montaCombo(ajax.responseXML);//após ser processado-chama fun
                } else {
                    idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
                }
            }
        }
        //passa o código do produto escolhido
        var params = "produto_referencia="+valor;
        ajax.send(null);
    }
}

function defeitoLista(peca,linha,os) {
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }
    if(peca.length > 0) {
        if(ajax) {
            var defeito = "defeito_"+linha;
            var op = "op_"+linha;
            eval("document.forms[0]."+defeito+".options.length = 1;");
            idOpcao  = document.getElementById(op);
            ajax.open("GET","ajax_defeito2.php?peca="+peca+"&os="+os);
            ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            ajax.onreadystatechange = function() {
                if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
                if(ajax.readyState == 4 ) {
                    if(ajax.responseXML) {
                        montaComboDefeito(ajax.responseXML,linha);
                    }
                    else {
                        idOpcao.innerHTML = "Selecione a peça";
                    }
                }
            }
            ajax.send(null);
        }
    }
}

function montaComboDefeito(obj,linha){
    var defeito = "defeito_"+linha;
    var op = "op_"+linha;
    var dataArray   = obj.getElementsByTagName("produto");

    if(dataArray.length > 0) {
        for(var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
            idOpcao.innerHTML = "Selecione o defeito";
            var novo = document.createElement("option");
            novo.setAttribute("id", op);//atribui um ID a esse elemento
            novo.value = codigo;        //atribui um valor
            novo.text  = nome;//atribui um texto
            eval("document.forms[0]."+defeito+".options.add(novo);");//adiciona
        }
    } else {
        idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
    }
}
function servicoLista(peca,linha) {
    try {ajax2 = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) { try {ajax2 = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax2 = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax2 = null;}
        }
    }

    if(peca.length > 0) {

        if(ajax2) {
            var servico = "servico_"+linha;
             var op_servico = "op_"+linha;
            eval("document.forms[0]."+servico+".options.length = 1;");

            idOpcao_servico  = document.getElementById(op_servico);

            ajax2.open("GET","ajax_servico.php?peca="+peca);
            ajax2.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            ajax2.onreadystatechange = function() {
                if(ajax2.readyState == 1) {idOpcao_servico.innerHTML = "Carregando...!";}
                if(ajax2.readyState == 4 ) {
                    if(ajax2.responseXML) {
                        montaComboServico(ajax2.responseXML,linha);
                    }else {
                        idOpcao_servico.innerHTML = "Selecione a peça";
                    }
                }
            }
            ajax2.send(null);
        }
    }
}
function montaComboServico(obj,linha){
    var servico = "servico_"+linha;
    var op_servico = "op_"+linha;
    var dataArray   = obj.getElementsByTagName("produto");
    if(dataArray.length > 0) {
        for(var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;

            idOpcao_servico.innerHTML = "Selecione o defeito";

            var novo = document.createElement("option");

            novo.setAttribute("id_servico", op_servico);
            novo.value = codigo;
            novo.text  = nome;
            eval("document.forms[0]."+servico+".options.add(novo);");
        }
    } else {
        idOpcao_servico.innerHTML = "Selecione o defeito";
    }
}

function adicionaIntegridade() {
    if(document.getElementById('defeito_constatado_codigo').value =="" && document.getElementById('defeito_constatado_descricao').value== "") {
        alert('Selecione o defeito constatado');
        return false;
    }
    <? if($login_fabrica == 43 or $login_fabrica == 59 or $login_fabrica == 2 or $login_fabrica == 85 ) { ?>
            if(document.getElementById('solucao_os').options[document.getElementById('solucao_os').selectedIndex].text==""){
                alert('Selecione a solução');
                return false
            }
    <? } ?>

    var tbl = document.getElementById('tbl_integridade');
    var lastRow = tbl.rows.length;
    var iteration = lastRow;

        if (iteration>0){
            document.getElementById('tbl_integridade').style.display = "inline";
        }

        var linha = document.createElement('tr');
        linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

        // COLUNA 1 - LINHA
        <? if ($login_fabrica <> 59 AND $login_fabrica <> 2) { ?>
        var celula =
        criaCelula(document.getElementById('defeito_constatado_codigo').value + '-'+document.getElementById('defeito_constatado_descricao').value);
        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

    <? } else { ?>

        var celula =
        criaCelula('-'+document.getElementById('defeito_constatado_descricao').value);
        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
    <? } ?>
        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
        el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
        el.setAttribute('value',document.getElementById('defeito_constatado').value);
        celula.appendChild(el);
        linha.appendChild(celula);
    <? if($login_fabrica == 30) { ?>
        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'integridade_defeito_descricao_' + iteration);
        el.setAttribute('id', 'integridade_defeito_descricao_' + iteration);
        el.setAttribute('value',document.getElementById('defeito_constatado_descricao').value);
        celula.appendChild(el);
        linha.appendChild(celula);

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'qtde_integridade');
        el.setAttribute('id', 'qtde_integridade');
        el.setAttribute('value',iteration);
        celula.appendChild(el);
        linha.appendChild(celula);
    <? } ?>
        <? if($login_fabrica == 43 or $login_fabrica == 59 or $login_fabrica == 2 or $login_fabrica == 85 ) { ?>
            // COLUNA 2 - HD:  67783 ADICIONADO A SOLUÇÃO NA TABELA DE DEFEITOS: DEFEITO + SOLUCAO
            var celula = criaCelula(document.getElementById('solucao_os').options[document.getElementById('solucao_os').selectedIndex].text );
            celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'integridade_solucao_' + iteration);
            el.setAttribute('id', 'integridade_solucao_' + iteration);
            el.setAttribute('value',document.getElementById('solucao_os').value);
            celula.appendChild(el);

            linha.appendChild(celula);
        <?}?>


        // coluna 6 - botacao
        var celula = document.createElement('td');
        celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

        var el = document.createElement('input');
        el.setAttribute('type', 'button');
        el.setAttribute('value','Excluir');
        el.onclick=function(){removerIntegridade(this,'nao');};
        celula.appendChild(el);
        linha.appendChild(celula);

        <? if($login_fabrica == 43 ) { ?>
                var agt = navigator.userAgent.toLowerCase();
                var is_ie = (agt.indexOf("msie")!=-1 && document.all);
                 var celula = document.createElement('td');
                celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
            if(is_ie){
                radio = document.createElement('<input name="principal" id="principal" type="radio" value="'+document.getElementById('defeito_constatado').value+'"/>');
                celula.appendChild(radio);
                linha.appendChild(celula);
            }else{
                var el = document.createElement('input');
                el.setAttribute('type', 'radio');
                el.setAttribute('name', 'principal');
                el.setAttribute('value',document.getElementById('defeito_constatado').value);
                celula.appendChild(el);
                linha.appendChild(celula);
            }
        <? } ?>

        // finaliza linha da tabela
        var tbody =tbl.getElementsByTagName("tbody")[0];
        tbody.appendChild(linha);
        /*linha.style.cssText = 'color: #404e2a;';*/
        tbl.appendChild(tbody);

        //document.getElementById('solucao').selectedIndex=0;

        if(document.getElementById('defeito_constatado_descricao').value != ""){
            document.getElementById('defeito_constatado_descricao').value = "";
            document.getElementById('defeito_constatado_codigo').value = "";
        }
    }

function adicionaIntegridade2(indice,tabela,defeito_reclamado,defeito_reclamado_desc,defeito_constatado) {

        var parar = 0;
        $("input[rel='defeito_constatado_"+indice+"']").each(function (){
            if ($(this).val() == defeito_constatado.value){
                parar++;
            }
        });

        if (parar>0){
            alert('Defeito constatado '+defeito_constatado.options[defeito_constatado.selectedIndex].text+' já inserido')
            return false;
        }

        var tbl       = document.getElementById(tabela);
        var lastRow   = tbl.rows.length;
        var iteration = lastRow;

        if (iteration>0){
            document.getElementById(tabela).style.display = "inline";
        }
        //Cria Linha
        var linha = document.createElement('tr');
        linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

        // Cria Coluna/
        var celula = document.createElement('td');
        var celula = criaCelula(defeito_constatado.options[defeito_constatado.selectedIndex].text);
        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000';
        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'i_defeito_constatado_' +indice+'_'+ iteration);
        el.setAttribute('rel', 'defeito_constatado_' +indice);
        el.setAttribute('id', 'i_defeito_constatado_' +indice+'_'+ iteration);
        el.setAttribute('value',defeito_constatado.value);
        celula.appendChild(el);
        linha.appendChild(celula);


        var celula = document.createElement('td');
        celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';
        var el = document.createElement('input');
        el.setAttribute('type', 'button');
        el.setAttribute('value','Excluir');
        el.onclick=function(){removerIntegridade2(this,tabela);};
        celula.appendChild(el);
        linha.appendChild(celula);



        // finaliza linha da tabela
        var tbody = document.createElement('TBODY');
        tbody.appendChild(linha);
        /*linha.style.cssText = 'color: #404e2a;';*/
        tbl.appendChild(tbody);

    }


    function removerIntegridade(iidd,id_defeito_constatado_reclamado) {
        <? if ($login_fabrica == 43 or $login_fabrica == 85 ) {?>
            if (id_defeito_constatado_reclamado != 'nao') {
                var url = 'excluir_defeito_ajax.php?id='+id_defeito_constatado_reclamado;
                requisicaoHTTP('GET',url,true,'retorno');
                var tbl = document.getElementById('tbl_integridade');
                tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
            } else {
                var tbl = document.getElementById('tbl_integridade');
                tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
            }
        <?} else { ?>
            var tbl = document.getElementById('tbl_integridade');
            tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
        <?}?>
    }

    function retorno(campos) {

    }

    function removerIntegridade2(iidd,tabela){
        var tbl = document.getElementById(tabela);
        tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

    }

    function criaCelula(texto) {
        var celula = document.createElement('td');
        var textoNode = document.createTextNode(texto);
        celula.appendChild(textoNode);
        return celula;
    }

    function abreComunicado(){
        var ref = document.frm_os.produto_referencia.value;
        if (document.frm_os.produto_referencia.value!=""){
            url = "pesquisa_comunicado.php?produto=" + ref;
            window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
        }
    }

    function verificaServico(servico,peca){
        var data = new Date();
        if (servico.value == '673' && peca.value.length > 0){
            $.ajax({
                type: "GET",
                url: "<?=$PHP_SELF?>",
                data: 'peca_referencia='+peca.value+'&peca_troca=sim&data='+data.getTime(),
                complete: function(http) {
                    results = http.responseText;
                    if (results =='sim'){
                        if(!confirm('Caso seja necessário esta peça para consertar o produto, o produto será trocado e a mão de obra, será de R$ 2,00. Caso consiga consertar o produto sem necessidade de troca desta peça, anote o serviço como ajuste, ou limpeza, ou ressoldagem, e a mão-de-obra será paga integral.')){
                            servico.value="";
                        }
                    }
                }
            });
        }
    }

function fnc_pesquisa_peca_lista_latina (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, serie,kit_peca) {

    var url = "";

    if (tipo == "tudo") {
        url = "peca_pesquisa_lista.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&serie="+serie.value+"&verifica_serie=sim"+"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
    }

    if (tipo == "referencia") {
        url = "peca_pesquisa_lista.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&serie="+serie.value+"&kit_peca="+kit_peca.value+"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
    }

    if (tipo == "descricao") {
        url = "peca_pesquisa_lista.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&serie="+serie.value+"&kit_peca="+kit_peca.value+"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
    }

    if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");

        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.preco      = peca_preco;
        janela.kit_peca   = kit_peca;
        janela.focus();

    } else {

        alert("<? if($sistema_lingua == "ES"){
                    echo "Digite al minus 3 caracters";
                }else{
                    echo "Digite pelo menos 3 caracteres!";
                } ?>");

    }

}
</script>

<script language='javascript'><?php
if ($login_fabrica == 52) {?>
	$(document).ready(function() {
		listaSolucaoGrupo(document.frm_os.defeito_constatado_grupo.value);
	});<?php
}?>


// servico troca faturada
// NKS ==> servico = 4247
// SUGAGR ==> servico = 4289

function mostraOrcamento(servico,linha) {
    var servico = servico;
    var linha = linha;
    var preco = document.getElementById('preco_'+linha).value;
    var preco_orcamento = document.getElementById('preco_orcamento_'+linha);
    var div_preco = document.getElementById("orcamento_mostra_"+linha);
    var div_mo = document.getElementById('orcamento_mao_obra')
    if (servico == 4247 || servico == 4289) {
        div_preco.style.display = 'block';
        div_mo.style.display = 'block';
        preco_orcamento.value = preco;
    }
    else {
        div_preco.style.display = 'none';
    }
}

function validaSerieEsmaltec(serie,produto,fabrica) {
    var serie = serie;
    var produto = produto;
    var fabrica = fabrica;
    var url = 'ajax_pesquisa_serie_esmaltec.php?serie='+serie+'&produto='+produto+'&fabrica='+fabrica;

    if (serie.length == 0)
    {
        return false;
    }
    else{
        requisicaoHTTP('GET',url,true,'tratadados');
    }
}

function tratadados(campos){
    var arraycampos=campos.split('|');

    if (arraycampos[0]=='ok')
    {
        return false;
    }
    if (arraycampos[0]=='erro 1'){
        alert ('Número de Série '+arraycampos[1]+' incorreto para o produto informado');
        $('#produto_serie').val ('');
        $('#produto_serie').focus();
    }
    if (arraycampos[0]=='erro 2'){
        if (confirm('Número de Série '+arraycampos[1]+' Fora do Padrão, Deseja colocar a OS para auditoria do inspetor?')==true)
        {
            return true;
        }
        else
        {
            $('#produto_serie').val ('');
            $('#produto_serie').focus();
        }
    }
}

</script>
<script>
  

function info(obj,conteudo){
    var divPop = document.getElementById("pop");        
	divPop.style.display = ""; 
	//divPop.childNodes[0].firstChild.nodeValue = conteudo;
	$("#pop").html(conteudo);
	//obj.appendChild(divPop);
	$("#pop").appendTo(obj);
	
}

function fechar(){        
	$("#pop").hide();
}

window.onload = function(){
	$("input[rel='numero']").keypress(function(e) {   
		var c = String.fromCharCode(e.which);   
		var allowed = '1234567890,.'; 
		if (e.which != 8 && e.which !=0 && allowed.indexOf(c) < 0) {
				return false;
		}
	});
}
				
</script>

<style>

  
 #tab td{     
 cursor:help;
 }
 
 #pop{    
 position:absolute;
 background-color:#FF4040;
 font: bold 12px Arial;
 color: #FFFFFF;
 }

a.lnk:link{
    font-size: 10px;
    font-weight: bold;
    text-decoration: underline;
    color:#FFFF33;
}
a.lnk:visited{
    font-size: 10px;
    font-weight: bold;
    text-decoration: underline;
    color:#FFFF33;
}
/*    Para link da Lenoxx [Manuel]    */
p.c1{
    color:white;
    font:bold 80% Arial,Helvetica,sans-serif;
}
a#lnx:link{
    font-weight: bold;
    text-decoration:none;
    color:white;
	
}
a#lnx:hover{
    font-weight: bold;
    text-decoration:underline;
    color:#FFFFAA;
}
a#lnx:visited{
    font-weight: bold;
    text-decoration:none;
    color:#FFFFAA;
}

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}
.btn_altera{
    font:bold 11px tahoma,verdana,helvetica;
    padding-left:3px;
    padding-right:3px;
    cursor:pointer;
    overflow:visible;
    outline:0 none;
    background-position: center;
    background-repeat: no-repeat;
}

.texto_avulso{
    font: 14px Arial;
	color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: justify;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;
}

</style>

<?php
#HD 311411 - Tectoy
if($login_fabrica==6){
	$sqlConserto="SELECT data_conserto
					FROM tbl_os
					WHERE os = $os";
	$resConserto = pg_query($con,$sqlConserto);
	$numConserto    = pg_num_rows($resConserto);
	
	if($numConserto){
		$data_conserto = pg_fetch_result($resConserto,0,'data_conserto');
		if(strlen($data_conserto) > 0){
			?>
			<table border="0" width="700" align="center" class="tabela">
				<tr class="msg_erro">
					<td>OS com data de conserto informada, para alterações, entrar em contato com o fabricante</td>
				</tr>
			</table>
			<?
			include 'rodape.php';
			exit;
		}
	}
}
#HD 311411 - Fim
?>

<p>

<?
$os_item = trim($_GET['os_item']);
if($os_item > 0){
    echo "<FONT COLOR=\"#FF0033\"><B>$msg_erro_item</B></FONT>";
    $msg_erro_item = 0;
}
?>


<?

if (strlen($msg_erro) > 0) {
    ##### Recarrega Form em caso de erro #####
    $os                       = $_POST["os"];
    $defeito_reclamado        = $_POST["defeito_reclamado"];
    $causa_defeito            = $_POST["causa_defeito"];
    $obs                      = $_POST["obs"];
    $aparencia_produto          = $_POST["aparencia_produto"];
    $acessorios                  = $_POST["acessorios"];
    $defeito_constatado       = $_POST["defeito_constatado"];
    $solucao_os               = $_POST["solucao_os"];
    $type                     = $_POST["type"];
    $tecnico_nome             = $_POST["tecnico_nome"];
    $tecnico                  = $_POST["tecnico_nome"];
    $valores_adicionais       = $_POST["valores_adicionais"];
    $justificativa_adicionais = $_POST["justificativa_adicionais"];
    $qtde_km                  = $_POST["qtde_km"];
    $peca_serie               = $_POST["peca_serie"];
    $peca_serie_trocada       = $_POST["peca_serie_trocada"];
    $peca_reposicao_estoque   = $_POST["peca_reposicao_estoque"];
    $aguardando_peca_reparo   = $_POST["aguardando_peca_reparo"];
//  HD 196473
    $produto_serie            = $_POST["produto_serie"];

    //hd 24288
    //$autorizacao_domicilio    = $_POST["autorizacao_domicilio"];
    $justificativa_autorizacao = $_POST["justificativa_autorizacao"];
    $fabricacao_produto        = $_POST["fabricacao_produto"];
    $codigo_fabricacao         = $_POST["codigo_fabricacao"];
    $defeito_constatado_grupo  =  $_POST["defeito_constatado_grupo"];


    if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

    echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center' class='msg_erro'>";
    echo "<tr>";
    echo "<td height='27' valign='middle' align='center'>";


    //hd 162508
    if($login_fabrica == 19 and strpos($msg_erro,"ERRO_VALOR_PECA_SUPERIOR") > 0) {
        $sql = "INSERT INTO tbl_os_status (
                    os,
                    status_os,
                    observacao,
                    fabrica_status
                ) VALUES (
                    $os,
                    62,
                    'Peça ".$aux_xpeca." com valor maior ou igual a 80% do valor do produto.',
                    $login_fabrica
                )";
        $res      = pg_query($con,$sql);
    }


    // retira palavra ERROR:
    if (strpos($msg_erro,"ERROR: ") !== false) {
        $erro = "Foi detectada a seguinte divergência: <br>";
        #$msg_erro .= substr($msg_erro, 6);
        #Tirei a concatenação porque estava duplicando a msg de erro HD 171532
        $msg_erro = substr($msg_erro, 6);
    }
    // retira CONTEXT:
    if (strpos($msg_erro,"CONTEXT:")) {
        $x = explode('CONTEXT:',$msg_erro);
        $msg_erro = $x[0];
    }
    echo $erro . $msg_erro;

    echo "</td>";
    echo "</tr>";
    echo "</table>";
}

##### COMUNICADOS - INÍCIO #####
#-----------CHAMADO 342629 INICIO-------------
$sql =    "
SELECT * FROM (

SELECT tbl_comunicado.comunicado                                       ,
                tbl_comunicado.descricao                                        ,
                tbl_comunicado.mensagem                                         ,
                tbl_comunicado.extensao                                         ,
                tbl_comunicado.data                                               ,
                tbl_comunicado.produto                                          ,
                tbl_produto.referencia                    AS produto_referencia ,
                tbl_produto.descricao                     AS produto_descricao
        FROM tbl_comunicado
        JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
        JOIN tbl_os      ON tbl_os.produto = tbl_produto.produto
        WHERE tbl_comunicado.fabrica = $login_fabrica
        AND   tbl_os.os = $os
        AND   tbl_comunicado.obrigatorio_os_produto IS TRUE


UNION

SELECT tbl_comunicado.comunicado ,
             tbl_comunicado.descricao ,
             tbl_comunicado.mensagem ,
             tbl_comunicado.extensao ,
                tbl_comunicado.data                                               ,
             tbl_comunicado.produto ,
             tbl_produto.referencia AS produto_referencia ,
             tbl_produto.descricao AS produto_descricao
        FROM tbl_comunicado
        JOIN tbl_comunicado_produto ON tbl_comunicado.comunicado=tbl_comunicado_produto.comunicado
        JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
        JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
        WHERE tbl_comunicado.fabrica = $login_fabrica
        AND tbl_os.os = $os
        AND tbl_comunicado.obrigatorio_os_produto IS TRUE
        ) AS dados
        ORDER BY data DESC;";
#-----------CHAMADO 342629 FIM-------------
$res_comun = pg_exec($con,$sql);

#-----------CHAMADO 342629 INICIO-------------
if (pg_numrows($res_comun) > 0){

    echo "<table border='0' width='700' align='center' style='background-color:#FFCC00;'>";
		echo"<tr align='center' valing='middle'>";
			echo"<td align='center' valign='center'>";
					echo "<table width='650'>";
					echo "<tr><td align='center'><img src='imagens/esclamachion1.gif'></td>";
					echo "<td align='left' style='text-align:center;'><b>Comunicado referente ao produto<br>";
					echo pg_result($res_comun,0,produto_referencia) . " - " . pg_result($res_comun,0,produto_descricao) . "</b></td></tr>";
					echo "</table>";

					echo "<br>";

					echo "<table width='650' cellspadding='0' cellpadding='0' class='tabela'>";
						echo "<tr class='titulo_coluna' style='font-weight:bold'>";
						echo "<td>Data</td>";
						echo "<td>Título</td>";
						echo "<td>Arquivo</td>";
						echo "</tr>";
						for ($k = 0 ; $k < pg_numrows($res_comun) ; $k++) {
							$cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
							echo "<tr class='Conteudo' style='background-color:$cor;'>";
							#--------CHAMADO 342629-------
							list($yi, $mi, $di) = explode("-", pg_result($res_comun,$k,data));
							$data_comunicado = substr($di,0,2)."/".$mi."/".$yi;

							echo "<td  align='center' >".$data_comunicado."</td>";
							#--------------------------------------
							echo "<td><a href='comunicado_mostra.php?comunicado=" . pg_result($res_comun,$k,comunicado) . "' target='_blank'>" . pg_result($res_comun,$k,descricao) . "</a></td>";
							echo "<td align='center'>";
							if (strlen(pg_result($res_comun,$k,comunicado)) > 0 && strlen(pg_result($res_comun,$k,extensao)) > 0) echo "<a href='comunicados/" . pg_result($res_comun,$k,comunicado) . "." . pg_result($res_comun,$k,extensao) . "' target='_blank'>Abrir arquivo</a>";
							else "&nbsp;";
							echo "</td>";
							echo "</tr>";
					}
					echo "</table>";

			echo "<br>";
			echo"<td>";
		echo "<tr>";
	echo "</table>";
#-----------CHAMADO 342629 FIM-------------
    echo "<br>";
}
##### COMUNICADOS - FIM #####


    if($login_fabrica==24){
        $sql = "SELECT tbl_peca.garantia_diferenciada from tbl_lista_basica join tbl_produto using(produto) join tbl_peca using(peca) where tbl_produto.produto='$produto_os' order by tbl_peca.garantia_diferenciada asc";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)>0){
            $garantia_diferenciada = pg_fetch_result($res,0,garantia_diferenciada);
        }
        if (strlen($garantia_diferenciada) > 0){
            $sql = "SELECT (data_nf + (('$garantia_diferenciada months')::interval))::date as dt_vencimento_garantia_peca,
                            data_abertura,
                            ((data_nf + (('$garantia_diferenciada months')::interval))::date < data_abertura)as venceu
                    FROM tbl_os
                    WHERE os = $os
                    AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $dt_vencimento_garantia_peca = pg_fetch_result($res,0,dt_vencimento_garantia_peca);
            $data_aber   = pg_fetch_result($res,0,data_abertura);
            $venceu  = pg_fetch_result($res,0,venceu);
            if($venceu=='t'){
                echo "<table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#ecc3c3'>";
                echo "<tr>";
                echo "<td valign='middle' align='center'>";
                echo "<font face='Arial, Helvetica, sans-serif' color='#d03838' size='1'><B>Atenção:</B> Produto comprado a mais de $garantia_diferenciada meses, algumas peças estão fora da garantia</font>";
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        }
        //echo "data do vencimento da garantia de 6 meses($dt_vencimento_garantia_peca), data abertura($data_aber), venceu a garantia de 6 meses e irá aparecer a mensagem? ($venceu)";
    }


    echo "<table width='700' align='center' class='texto_avulso'>";
    echo "<tr>";
    echo "<td >";
    if($login_fabrica==19){
        echo "Caso algum tipo de defeito Constatado não esteja relacionado nas opções, favor informar o Depto de Assistência Técnica através do e - mail osglorenzetti.com.br, informando qual o número da OS, o produto e qual o defeito que não consta na lista";
    }
    if($login_fabrica==6){
        #Retirado (011) 3823-1713 a pedido - Fabio - 7499
        echo "Caso algum tipo de defeito Constatado não esteja relacionado nas opções, favor informar o Depto de Assistência Técnica através do E-mail : duvidastecnicastectoy.com.br";
    }
    if($login_fabrica==20){
        echo "<b>Caso algum tipo de defeito Constatado não esteja relacionado nas opções, favor informar o Depto de Assistência Técnica através do telefone (019) 3745-2208 ou mesmo através do E-mail : Gustavo.Guerreirobr.bosch.com";
    }
    if($login_fabrica==3){
        echo "Caso algum tipo de defeito Constatado não esteja relacionado nas opções, favor informar através do E-mail : suporte.tecnicobritania.com.br, código Posto, descrição do Produto, defeito reclamado, defeito constatado que não consta na lista, e a solução";
    }
    if($login_fabrica==15){
        echo "Caso não encontre algum tipo de informação nos campos abaixo, favor nos informar através do e-mail telecontrollatinatec.com.br</font></b>";
    }
    /*if($login_fabrica==11){
        echo "<b><font face='Arial, Helvetica, sans-serif' color='#465357' size='1'>Caso algum tipo de defeito Constatado n&atilde;o esteja relacionado nas op&ccedil;&otilde;es, ou alguma pe&ccedil;a n&atilde;o seja encontrada na LISTA B&Aacute;SICA do produto, favor informar o Depto de Assist&ecirc;ncia T&eacute;cnica atrav&eacute;s do email suportedatlenoxxsound.com.br informando o modelo do produto e a pe&ccedil;a n&atilde;o encontrada.</font></b>";
    }    Exclusão segundo HD 49350, Erasmo  [Manuel]*/
    if($login_fabrica==24){
        echo "<b><font face='Arial, Helvetica, sans-serif' color='#465357' size='1'>Caso algum tipo de defeito Constatado não esteja relacionado nas opções, favor informar o Depto de Assistência Técnica através do telefone (031) 3280-1300 ou mesmo através do E-mail : suggatsuggar.com.br </font></b>";
    }
    echo "</td>";
    echo "</tr>";
    echo "</table>";

    if ($login_fabrica==19) {
        //ULTIMO STATUS
        $sql_intervencao = "SELECT interv.os
                            FROM (
                                SELECT
                                    ultima.os,
                                    (SELECT status_os FROM tbl_os_status WHERE status_os IN (62,64) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
                                FROM (
                                    SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (62,64) AND tbl_os_status.fabrica_status=$login_fabrica
                                    and tbl_os_status.os = $os
                                ) ultima
                            ) interv
                            WHERE interv.ultimo_status IN (62)";
        $res_intervencao = pg_query($con, $sql_intervencao);

        if(pg_num_rows($res_intervencao)>0){
            //PEGA O ALERTA
            $res = pg_query($con,"SELECT fn_valida_os($os, $login_fabrica)");
            $msg_alerta = pg_last_notice($con);
            $msg_alerta = trim(substr($msg_alerta,7,strpos($msg_alerta,"CONTEXT:")-7));

            //QUANDO A TELA É CHAMADA E A OS JÁ ESTÁ EM INTERVENÇÃO POR VALOR DE PEÇA SETA A MENSAGEM, POIS A MENSAGEM DO ERRO QUANDO LANÇA A PEÇA VEM DA FUNÇÃO
            if(!isset($msg_alerta{0})) {
                $msg_alerta = "ATENÇÃO: Não fazer reparo neste produto! A Lorenzetti vai analisar a possibilidade de troca deste produto! Aguarde instruções.";
            }

            echo "<script type='text/javascript'>alert(\"$msg_alerta\"); window.location='os_finalizada.php?os=$os';</script>";
        }
    }

    //
    if($login_fabrica==11){
        echo "<table width='700' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#0000FF'>";
        echo "<tr>";
        echo "<td valign='middle' align='center'>";
            echo "<P class='c1'>Caso algum tipo de <I>Defeito Constatado</I> n&atilde;o esteja relacionado nas op&ccedil;&otilde;es, ou alguma pe&ccedil;a n&atilde;o seja encontrada na <I>LISTA B&Aacute;SICA</I> do produto, favor informar o Dpto. de Assist&ecirc;ncia T&eacute;cnica atrav&eacute;s do email <a id='lnx' href='mailto:suportedatlenoxxsound.com.br'>suportedatlenoxxsound.com.br</A> informando o modelo do produto e a pe&ccedil;a n&atilde;o encontrada.</P>"; //Texto alterado segundo HD 49350, Erasmo [Manuel]
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "<br>";
        if (strlen($os) > 0) { // HD 68996
            $sql = " SELECT comunicado
                     FROM tbl_comunicado
                     JOIN tbl_os USING(produto)
                     WHERE tbl_os.os = $os
                     AND   tbl_os.fabrica = $login_fabrica
                     AND   tbl_comunicado.fabrica = $login_fabrica
                     AND tbl_comunicado.ativo IS TRUE ";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $titulo = " HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER ";
            }else{
                $titulo = " NÃO HÁ COMUNICADOS PARA ESTE PRODUTO ";
            }
        }
        echo "<img src='imagens/botoes/vista.jpg' height='22px' id='img_comunicado' target='_blank' name='img_comunicado' border='0' align='absmiddle'  title='$titulo' onclick=\"javascript:abreComunicado()\" style='cursor: pointer;'><font color='#FF0000' SIZE='4'>&nbsp;&nbsp;DICAS DO PRODUTO</FONT>";
    }

if (strlen($msg_previsao) > 0) {
    echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
    echo "<tr>";
    echo "<td height='27' valign='middle' align='center'>";
    echo "<b><font face='Arial, Helvetica, sans-serif' color='#3333FF'>";
    echo $msg_previsao ;
    echo "</font></b>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "<br>";

}

#HD 13618
if ( $login_fabrica==45 AND $troca_obrigatoria == 't'){
    echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:5px'>";
    #echo "<p style='font-size:14px;font-weight:bold;margin:0px;'>ESTE PRODUTO É TROCA OBRIGATÓRIA</p>";
    echo "<ul style='font-size:12px;margin:0px;'>";
    echo "    <li>Não é fornecido peças para este produto.";
    echo "    <li>Se houver necessidade de troca de peça, clique no botão abaixo: ";
    echo "</ul>";
    echo "<input type='button' onClick='fnc_troca($os)' value='Clique aqui para Troca de Produto'>";
    echo "</div>";
}

#------------ Pedidos via Distribuidor -----------#
$resX = pg_query($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
if (pg_fetch_result($resX,0,0) == 't') {
    $resX = pg_query($con,"SELECT tbl_posto.nome FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto_linha.distribuidor = tbl_posto.posto WHERE tbl_posto_linha.posto = $login_posto AND tbl_posto_linha.linha = $linha");
//------------HD 341319 INICIO------------
    echo "<table class='texto_avulso' align='center' width='700' border ='0'>";
		echo "<tr>";
	if (pg_num_rows($resX) > 0) {
        echo "<td align='center'>Atenção! Peças da linha <b>$linha_nome</b> serão atendidas pelo distribuidor.<br><font size='+1'>" . pg_fetch_result($resX,0,nome) . "</font></td><p>";
    }else{
        echo "<td align='center'>Peças da linha <b>$linha_nome</b> serão atendidas pelo fabricante.</td><p>";
    }
	echo "</tr>";
	echo "</table>";
//------------HD 341319 FIM------------
}


?>
    <!-- ------------- Formulário | LINHA FIM: 7959 ----------------- -->
<form name="frm_os" method="post" action="<? echo $PHP_SELF.'?'.$_SERVER['QUERY_STRING']?>" enctype="multipart/form-data">
 <?if($login_fabrica == 30 or $login_fabrica == 59 or $login_fabrica == 2) { // HD 46493
        echo "<br />";

        $sql = "SELECT tbl_os.os
                FROM tbl_os
                LEFT JOIN tbl_os_produto USING (os)
                LEFT JOIN tbl_os_item USING (os_produto)
                WHERE tbl_os.os      = $os
                AND   tbl_os.fabrica = $login_fabrica
                AND   tbl_os.defeito_constatado IS NULL
                AND   tbl_os.solucao_os IS NULL
                GROUP BY tbl_os.os
                HAVING count(os_item) = 0 ";
        $res = pg_query($con,$sql);
        echo "<table width='400' border='0' cellpadding='0' cellspacing='1' align='center' bgcolor='#ffffff'>";

        if(pg_num_rows($res)  > 0 ) {
            $os_altera      = pg_fetch_result($res,0,os);
            echo "<tr><td nowrap colspan='3' align='center'> Para alterar produto desta OS, selecione e clique em <b>'Confirmar alteração do Produto'</b></td></td> ";
            echo "<tr>";
            echo "<td nowrap>";
            echo "<input type='hidden' name='os_altera' value = '$os_altera'>";
            echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font><br>";
            echo "<input class='frm' type='text' name='referencia_produto'  id='referencia_produto' size='15' maxlength='20' value='$produto_referencia' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on';\" displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');><img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto (document.frm_os.referencia_produto,document.frm_os.descricao_produto,'referencia',document.frm_os.produto_voltagem)\" style='cursor: hand'>";
            echo "</td>";
            echo "<td nowrap>";
            echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font><br>";
            echo "<input class='frm' type='text' name='descricao_produto' id='descricao_produto' size='30' value='$produto_descricao' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on';\" displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.'); ><img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto (document.frm_os.referencia_produto,document.frm_os.descricao_produto,'descricao',document.frm_os.produto_voltagem)\"  style='cursor: pointer'></A>";
            echo "<input type='hidden' name='produto_voltagem' value=''>";
            echo "</td>";
            echo "<td><br>";
            echo "<input type='submit' name = 'btn_altera' value='Confirmar alteração do Produto' class='btn_altera'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "<hr></hr>";
        }else{
            echo "<tr>";
            echo "<td nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font><br>";
            echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>";
            echo "$produto_referencia - $produto_descricao";
            echo "</b></font>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "<br>";
        }

     }
    #HD 101357
    if($login_fabrica==3){
        echo "<input name='linha_envia' type='hidden' value='".$linha."'>";
    }
     ?>

<!----------HD 341319 INICIO---------->
<table width="90%" border="0" cellpadding="0" cellspacing="0" align="center" class='formulario'>
<tr>
	<td class='titulo_tabela'>
		Lançamento de Itens
	</td>
</tr>
<tr>
<!----------HD 341319 FIM---------->
    <td valign="top" align="center">

        <input type="hidden" name="os"        value="<?echo $os?>">
        <input type="hidden" name="voltagem"  value="<?echo $produto_voltagem?>">
        <input type='hidden' name='produto_referencia' value='<? echo $produto_referencia ?>'>
        <p>

<? if ($login_fabrica == 1 or $login_fabrica == 35) { ?>
        <table border="0" cellspacing="0" cellpadding="0" align="center">
        <tr>
            <td nowrap><a href="os_print.php?os=<? echo $os ?>" target="_blank" alt="Imprimir OS"><img src="imagens/btn_imprimir.gif"></a></td>
        </tr>
        </table>
<? } ?>

        <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
                <br>
                <font size="2" face="Geneva, Arial, Helvetica, san-serif">
                <b>
<?
        if ($login_fabrica == 1) echo $codigo_posto;
        echo $sua_os;
?>
                </b>
                </font>
            </td>
		<? if($login_fabrica == 90){ ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				Tipo Atendimento</font><br />
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>
				<?php
					if($tipo_atendimento == 88)
						echo 'ATENDIMENTO BALCÃO';
					else if($tipo_atendimento == 89)
						echo 'ATENDIMENTO COM DESLOCAMENTO';
				?></b>
				</font>
			</td>
		<? } ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
                <br>
                <font size="2" face="Geneva, Arial, Helvetica, san-serif">
                <b><? echo $consumidor_nome ?></b>
                </font>
            </td>

            <? if ($login_fabrica == 19) { ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Produtos</font>
                <br>
                <font size="2" face="Geneva, Arial, Helvetica, san-serif">
                <b>
                <?
                echo $qtde_produtos;
                ?>
                </b>
                </font>
            </td>
            <? } ?>


            <? if($login_fabrica <> 30) { ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
                <br>
                <font size="2" face="Geneva, Arial, Helvetica, san-serif">
                <b><?
#------------HD 341319 INICIO------------
                echo "$produto_descricao"; ?></b>
<!-----------HD 341319 FIM---------->
                </font>
            </td>
            <? } ?>

            <td nowrap>
            <?
            if ($login_fabrica == 1 OR $login_fabrica == 51) {
                echo "<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\">Versão/Type</font>";
                echo "<br>";
                echo "<select name='type' class ='frm'>\n";
                echo "<option value=''></option>\n";
                echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo " >Tipo 1</option>\n";
                echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo " >Tipo 2</option>\n";
                echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo " >Tipo 3</option>\n";
                echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo " >Tipo 4</option>\n";
                echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo " >Tipo 5</option>\n";
                echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo " >Tipo 6</option>\n";
                echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo " >Tipo 7</option>\n";
                echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo " >Tipo 8</option>\n";
                echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo " >Tipo 9</option>\n";
                echo "<option value='Tipo 10'"; if($type == 'Tipo 10') echo " selected"; echo " >Tipo 10</option>\n";
                echo "<\select>&nbsp;";
            }
            ?>
            </td>
            <? if ($login_fabrica==24 OR (($login_fabrica==30 OR $login_fabrica==52) AND strlen($produto_serie_db)==0) or $login_fabrica ==15 or $login_fabrica ==85){ ?>
                <td nowrap  valign='top'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
                <br>
                <input class="frm" type="text" name="produto_serie" id="produto_serie"
                        size="18" maxlength="20" value="<?=$produto_serie ?>"
                      onblur="this.className='frm';<?if ($login_fabrica==30 or $login_fabrica==85){?>validaSerieEsmaltec(this.value,<?=$produto_os?>,<?=$login_fabrica?>); <?}?>displayText('&nbsp;');"
                     onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');">
                <br><font face='arial' size='1'></font>
                <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
                </div>
            </td>
            <? }else{ ?>
            
            <?php
                if (in_array($login_fabrica, array(14, 43, 66, 80,42))) {
                    echo "<td><a style='font-size:9pt' href='os_upload_nf_produto.php?os=$os'target=_blank>";
					echo ($login_fabrica == 42) ? "Upload de imagem":"imagem da nota fiscal";
					 echo "</a></td>";
				}
            ?>
            <? if ($login_fabrica == 45) { // MLG 05/11/2009 - Um visual diferente para não assustar nignuém da Intelbras... ?>
			<td>
            <a style='font-size:9pt' href='os_upload_nf_produto.php?os=<?=$sua_os?>'
               title='Clique para anexar a imagem da Nota Fiscal'
              target='_blank'>
                <img src="imagens/add_nf.gif">
            </a>
			</td>
            <?}?>            

            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                    <?
                    if($login_fabrica=="35"){
                        echo "PO#";
                    }else{
                        echo "N. Série";
                    }
                    ?>
                </font>
                <br>
                <font size="2" face="Geneva, Arial, Helvetica, san-serif">
                <b><? echo $produto_serie ?></b>
                </font>
            </td>
            <? } ?>
        </tr>
        </table>
<?
//relacionamento de integridade comeca aqui....
echo "<INPUT TYPE='hidden' name='xxproduto_linha' value='$produto_linha'>";
echo "<INPUT TYPE='hidden' name='xxproduto_familia' value='$produto_familia'>";

//WELLINGTON 20/12/2006 -
//SE FOR LENOXX E TIVER UM DOS DEFEITOS INATIVOS, PREENCHE COM ESPAÇO EM BRANCO PARA PA PREENCHER NOVAMENTE
if ($login_fabrica==11 AND ($defeito_reclamado==3708 or $defeito_reclamado==3710 )) $defeito_reclamado = "";

if ($login_fabrica==15){
    if($os < '2998725') $defeito_reclamado = "";
}

if(($login_fabrica==6 or $login_fabrica==3 OR $login_fabrica==24) and strlen($defeito_reclamado)>0){
//verifica se o defeito reclamado esta ativo, senao ele pede pra escolher de novo...acontece pq houve a mudança de tela.
    $sql = "SELECT ativo from tbl_defeito_reclamado where defeito_reclamado=$defeito_reclamado";
    $res = pg_query($con,$sql);
    $xativo = pg_fetch_result($res,0, ativo);

    if($xativo=='f'){
        $defeito_reclamado= "";
    }
    $sql = "SELECT defeito_reclamado
            FROM tbl_diagnostico
            WHERE fabrica=$login_fabrica
            AND linha = $produto_linha
            AND defeito_reclamado = $defeito_reclamado
            AND familia = $produto_familia
            AND tbl_diagnostico.ativo='t'";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res)) {
        $xativo = pg_fetch_result($res,0, defeito_reclamado);
    }
    else {
        $xativo = "";
    }
    if (strlen($xativo)==0){
        $defeito_reclamado= "";
    }
}

//se tiver o defeito reclamado ativo
//HD 351696 Início
if($login_fabrica == 42 || $login_fabrica == 74){
	$sqlFamilia = "SELECT familia from tbl_diagnostico WHERE familia = ".$produto_familia;
	$resFamilia = pg_exec($con,$sqlFamilia);
	if(pg_numrows($resFamilia) > 0){
		$condFamilia = " AND tbl_diagnostico.familia = $produto_familia";
	}
?>
	<table width='100%' class='formulario'>
		<tr>
			<td align='left'>
				Defeito Reclamado <br />
				<select name='defeito_reclamado' id='defeito_reclamado' class='frm'>
					<?php			
						if(!empty($defeito_reclamado)){
							$sql = "SELECT defeito_reclamado, descricao 
									FROM tbl_defeito_reclamado 
									WHERE defeito_reclamado = $defeito_reclamado 
									AND fabrica = $login_fabrica";
							$res = pg_exec($con,$sql);
							$def_reclamado = pg_result($res,0, defeito_reclamado);
							$desc_defeito_reclamado  = pg_result($res,0,descricao);

							echo "<option value='$def_reclamado'>$desc_defeito_reclamado</option>";
						}
						else{
							$sql = "SELECT tbl_diagnostico.defeito_reclamado       , 
										   tbl_defeito_reclamado.defeito_reclamado , 
										   tbl_defeito_reclamado.descricao
									FROM tbl_diagnostico
									JOIN tbl_defeito_reclamado USING(defeito_reclamado)
									WHERE tbl_diagnostico.fabrica = $login_fabrica
									$condFamilia";
							$res = pg_exec($con,$sql);
							$totalDefRec = pg_numrows($res);
							for($i = 0; $i < $totalDefRec; $i++){
								$def_reclamado = pg_result($res,$i, defeito_reclamado);
								$desc_defeito_reclamado  = pg_result($res,$i,descricao);

								echo "<option value='$def_reclamado'>$desc_defeito_reclamado</option>";
							}
						}
					?>
				</select>
			</td>
			<td>
				<? if(!empty($defeito_reclamado_descricao_os)){
						//echo nl2br($sql);
						$sqlDesc = "SELECT defeito_reclamado,
										   descricao
										FROM tbl_defeito_reclamado
										WHERE tbl_defeito_reclamado.descricao = '$defeito_reclamado_descricao_os'
										AND fabrica = $login_fabrica";	
					
						$resDesc = pg_exec($con,$sqlDesc);
						echo 'Defeito Reclamado Cliente <br />';
						if(pg_numrows($resDesc) > 0){
							$xdefeito_reclamado = pg_fetch_result($resDesc,0,defeito_reclamado);
							$xdefeito_reclamado_descricao = pg_fetch_result($resDesc,0,descricao);
							echo "<INPUT TYPE='text' name='defeito_reclamado_descricao_os' size='30' value='$xdefeito_reclamado_descricao' readonly class='frm'>";
							echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$xdefeito_reclamado' class='frm'>";
						}
						else{
							echo "<INPUT TYPE='text' name='defeito_reclamado_descricao_os' size='30' value='$defeito_reclamado_descricao_os'>";
						}
					//echo nl2br($sqlDesc);
				 }?>
			</td>
			<td>
				Defeito Constatado <br />
				<select name='defeito_constatado' id='defeito_constatado' class='frm'>
					<?php
														
							$sql = "SELECT tbl_diagnostico.defeito_constatado       , 
										   tbl_defeito_constatado.defeito_constatado , 
										   tbl_defeito_constatado.descricao
									FROM tbl_diagnostico
									JOIN tbl_defeito_constatado USING(defeito_constatado)
									WHERE tbl_diagnostico.fabrica = $login_fabrica
									$condFamilia";
							$res = pg_exec($con,$sql);
							$totalDefRec = pg_numrows($res);
							for($i = 0; $i < $totalDefRec; $i++){
								$def_constatado = pg_result($res,$i, defeito_constatado);
								$desc_defeito_constatado  = pg_result($res,$i,descricao);

								echo "<option value='$def_constatado'";
								if($def_constatado == $defeito_constatado){
									echo 'selected';
								}
								echo " >$desc_defeito_constatado</option>";
							}
						
					?>
				</select>
			</td>
		</tr>
	</table>
<? } 
//HD 351696 Fim

if ( ($login_fabrica <> 42 && $login_fabrica <> 74) && (strlen($defeito_reclamado)>0) and (($login_fabrica==3) or ($login_fabrica==6) or ($login_fabrica==15) or ($login_fabrica==11) or ($login_fabrica==2) or ($login_fabrica==19) or ($login_fabrica==24) or ($login_fabrica==5) or ($login_fabrica==26) or ($login_fabrica==25) or ($login_fabrica>28) ) ) {
    echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
//validações da Lennox
    if($login_fabrica==11){
        //aparencia do produto
        echo "<tr>";
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Aparência do Produto<br></font><input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </font></td>";

        //acessórios
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Acessórios<br></font><input class='frm' type='text' name='acessorios' size='30' value='$acessorios' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </td>";
        echo "</tr>";
    }
//validações da Lennox

    echo "<tr>";
    echo "<td>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><BR>";

    $sql = "SELECT defeito_reclamado,
                    descricao as defeito_reclamado_descricao
            FROM tbl_defeito_reclamado
            WHERE defeito_reclamado= $defeito_reclamado";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        $xdefeito_reclamado = pg_fetch_result($res,0,defeito_reclamado);
        $xdefeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
    }
    echo "<INPUT TYPE='text' name='xxdefeito_reclamado' size='30' value='$xdefeito_reclamado - $xdefeito_reclamado_descricao' disabled>";
    echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$xdefeito_reclamado'>";
    echo "</td>";
    if ($login_fabrica == 52) {
        echo "<td>";
        echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Grupo Defeito Constatado</font><BR>";

        $sql = "SELECT    defeito_constatado_grupo,
                        grupo_codigo,
                        descricao
                FROM tbl_defeito_constatado_grupo";
        $res = pg_query($con,$sql);

        echo "<select name='defeito_constatado_grupo' id='defeito_constatado_grupo' size='1' class='frm' onchange='listaSolucaoGrupo(this.value);'>";
        echo "<option value=''>selecione</option>";

        if(pg_num_rows($res)>0){

        for ($y = 0 ; $y < pg_num_rows($res) ; $y++ ) {

            $xdefeito_constatado_grupo = pg_fetch_result($res,$y,defeito_constatado_grupo);
            $defeito_constatado_grupo_descricao = pg_fetch_result($res,$y,descricao);
            $grupo_codigo = pg_fetch_result($res,$y,grupo_codigo);

            echo "<option value='$xdefeito_constatado_grupo'"; if ($defeito_constatado_grupo == $xdefeito_constatado_grupo) { echo "SELECTED"; } echo ">$grupo_codigo - $defeito_constatado_grupo_descricao</option>";
        }
        echo "</select>";


        echo "</td>";
        }
    }
    if($login_fabrica<>19){
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'> Constatado</font><BR>";

        if($pedir_defeito_reclamado_descricao == 'f'){
            $sql = "SELECT     distinct(tbl_diagnostico.defeito_constatado),
                            tbl_defeito_constatado.descricao
                    FROM tbl_diagnostico
                    JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
                    WHERE tbl_diagnostico.fabrica = $login_fabrica
					AND tbl_diagnostico.ativo='t' ";
				if($login_fabrica != 95) {
					$sql .= " AND tbl_diagnostico.linha = $produto_linha
                    AND tbl_diagnostico.defeito_reclamado=$defeito_reclamado" ;
				}
					
            if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
            if ($login_fabrica == 19) {
                //hd 3347
                $sql .= " AND tbl_defeito_constatado.defeito_constatado <> 10820 ";

                //hd 3470
                if ($linha <> 261) $sql .= " AND tbl_defeito_constatado.defeito_constatado <> 10823 ";
            }
            if($login_fabrica == 24){
                $sql.="AND tbl_defeito_constatado.ativo ='t'";
            }
            $sql.=" ORDER BY tbl_defeito_constatado.descricao";
            if ($login_fabrica == 19 and $tipo_atendimento==3){
                $sql = "SELECT tbl_defeito_constatado.*
                        FROM tbl_defeito_constatado
                        WHERE fabrica = $login_fabrica and defeito_constatado in (10021,10546,10547,10548,10549,10550,10551,10552,10545)";
            }//hd1414 takashi 07-03-07
        }else{
			if($login_fabrica <> 42 && $login_fabrica <> 86 and $login_fabrica <> 74)
				$cond = "tbl_diagnostico.linha = $produto_linha";
			else
				$cond = '1=1';
            $sql = "SELECT     distinct(tbl_diagnostico.defeito_constatado),
                            tbl_defeito_constatado.descricao
                    FROM tbl_diagnostico
                    JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
                    WHERE $cond
                    AND tbl_diagnostico.ativo='t' ";
            if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
            if($login_fabrica == 24){
                $sql.="AND tbl_defeito_constatado.ativo ='t'";
            }
            $sql.=" ORDER BY tbl_defeito_constatado.descricao";
        }

        $res = pg_query($con,$sql);

        echo "<select name='defeito_constatado' id='defeito_constatado' size='1' class='frm'";
        if($login_fabrica<>19 and $login_fabrica <> 59 and $login_fabrica <> 95){
            echo "onchange='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);'";
        } else {
            if ($login_fabrica == 59) {
            echo " onchange=\"javascript: getElementById('defeito_constatado_codigo').value = this.value;getElementById('defeito_constatado_descricao').value = this.options[this.selectedIndex].text;\" ";
            }
			if($login_fabrica == 95) {
				echo " onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado.value,this);' ";
			}
        }
        echo ">";

        echo "<option value=''></option>";
        for ($y = 0 ; $y < pg_num_rows($res) ; $y++ ) {
            $xxdefeito_constatado = pg_fetch_result($res,$y,defeito_constatado) ;
            $defeito_constatado_descricao = pg_fetch_result($res,$y,descricao) ;

            echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_descricao</option>";
        }
        echo "</select>";
        echo "</td>";
    }

    if (!in_array($login_fabrica,array(52,42,86,74,95))) {
        echo "<td>";
        if($login_fabrica==19){
            echo "<INPUT TYPE='hidden' name='solucao_os' value='367'>";
        }else{
        echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";

        echo "<select name='solucao_os' id='solucao_os' class='frm'  style='width:200px;' onfocus='javascript: if (document.frm_os.defeito_constatado.value != \"\") { listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);}' >";

        $solucao_descricao='';
        if (strlen($solucao_os) > 0){
            $sql = "SELECT     solucao,
                            descricao
                    FROM tbl_solucao
                    WHERE fabrica=$login_fabrica
                    AND solucao=$solucao_os";
            $res = pg_query($con, $sql);
            $solucao_descricao = pg_fetch_result($res,0,descricao);
        }

        echo "<option id='opcoes' value='$solucao_os'>$solucao_descricao</option>";
        echo "</select>";

        }
        echo "</td>";
    }
    echo "</tr>";
    //HD 4291 Paulo
    if($login_posto== 4311 ) {
        echo "<tr><td>";
        echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Box/Prateleira</font><BR>";
        echo "<input type='text' name='prateleira_box' class='frm' value='$prateleira_box' size='8' maxlength='20'>";
        echo "</td>";
        echo "</tr>";
    }
    //Fim
    echo "</table>";
    echo "<BR><BR>";



}

//Verifica OSG-Avaliacao Lorenzetti
if($login_fabrica== 19){
	
	$sql = "select tbl_os.tipo_atendimento
                    from tbl_os
                    where tbl_os.os = $os";
        $res = pg_query($con,$sql);
		$tipo = pg_fetch_result($res,0,tipo_atendimento);
		
	if($tipo==77){
		echo "<font style='font-size: 14px;'><b>OS de GARANTIA<b></font><br>";
		echo "Sim&nbsp; <input type='radio' name='osg' value='4'>&nbsp;&nbsp;&nbsp;Não&nbsp; <input type='radio' name='osg' value='77' checked>";
		echo "<br><br>";
	}
}
//Fim Verifica OSG-Avaliacao Lorenzetti


//FIM se tiver o defeito reclamado ativo
//caso nao achar defeito reclamado
if (strlen($defeito_reclamado)==0 && $login_fabrica <> 42 && $login_fabrica <> 74){
    echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";

//validacoes lennox
    if($login_fabrica==11){
        //aparencia do produto
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Aparência do Produto<br></font><input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </font></td>";

        //acessórios
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Acessórios<br></font><input class='frm' type='text' name='acessorios' size='30' value='$acessorios' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </td>";
        echo "</tr>";
    }
//validacoes lennox
    if($pedir_defeito_reclamado_descricao == 't'){
        echo "<tr>";
        echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
        if(strpos($sua_os,'-') == FALSE){//SE FOR DE CONSUMIDOR
            if (strlen($defeito_reclamado_descricao_os) > 0){
                echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
                echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
                echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
            }else{
                echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
                echo "<INPUT TYPE='text' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
                echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
            }
        }else{//SE FOR DE REVENDA
            if (strlen($defeito_reclamado_descricao_os) == 0 ){
                echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
                echo "<INPUT TYPE='text' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
                echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
            }else{
                echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
                echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
                echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
            }
        }
        echo "</td>";
    }else{
        echo "<tr>";
        echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
        echo "<select name='defeito_reclamado'  class='frm' style='width:220px;' ";
        echo"onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' ";
        if($login_fabrica==19) echo "onchange='window.location=\"$PHP_SELF?os=$os&defeito_reclamado=\"+this.value'";
        echo ">";

        echo "<option id='opcoes' value=''></option>";
        echo "</select>";
        echo "</td>";
    }
    if($tipo_atendimento == 22){
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR><font color=green size=1><b>Instalação de Purificador</b></font></td>";
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR><font color=green size=1><b>Instalação de Purificador</b></font></td>";
        echo "<input type='hidden' name='defeito_constatado' id='defeito_constatado' value='11261'>";
        echo "<input type='hidden' name='solucao_os' id='solucao_os' value='459'>";
    } else {
        //CONSTATADO
        if ($login_fabrica <> 19) {
            if ($pedir_defeito_constatado_os_item <> 'f' OR $login_fabrica<>5) {
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR>";
                    if ($login_fabrica<>30 AND $login_fabrica <> 43 and $login_fabrica <> 85 ) {

                    if ($login_fabrica == 59 or $login_fabrica == 2 or $login_fabrica ==45 ) {
                        echo "<input type='hidden' name='defeito_constatado_codigo' id='defeito_constatado_codigo' value=''>";

                        echo "<input type='hidden' name='defeito_constatado_descricao' id='defeito_constatado_descricao' value=''>";
                    }?>
                    <select name='defeito_constatado' id='defeito_constatado' class='frm'  onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado.value,this);' onchange="javascript: getElementById('defeito_constatado_codigo').value = this.value;getElementById('defeito_constatado_descricao').value = this.options[this.selectedIndex].text;"><?php
                    if ($pedir_defeito_reclamado_descricao == 't' AND strlen($defeito_constatado) AND ($login_fabrica == 45 OR $login_fabrica == 15 OR $login_fabrica == 35 OR $login_fabrica == 2 OR $login_fabrica == 43 OR $login_fabrica == 46 OR $login_fabrica == 51 OR $login_fabrica == 30 OR $login_fabrica == 56 OR $login_fabrica == 40 OR $login_fabrica == 50 OR $login_fabrica > 56)) {

                        $sql_cons = "SELECT defeito_constatado, descricao
                                        FROM tbl_defeito_constatado
                                        WHERE defeito_constatado = $defeito_constatado
                                        AND fabrica = $login_fabrica; ";
                        $res_cons = pg_query($con, $sql_cons);

                        if (pg_num_rows($res_cons) > 0) {
                            $defeito_constatado_desc = pg_fetch_result($res_cons,0,descricao);
                            echo "<option id='opcoes2' value='$defeito_constatado'>$defeito_constatado_desc</option>";
                        } else {
                            echo "<option id='opcoes2' value=''></option>";
                        }

                    } else {
                        echo "<option id='opcoes2' value=''></option>";
                    }

                    echo "</select>";

                } else {
                    if (strlen($defeito_constatado) > 0) {
                        $sql_cons = "SELECT defeito_constatado, descricao ,codigo
                                        FROM tbl_defeito_constatado
                                        WHERE defeito_constatado = $defeito_constatado
                                        AND fabrica = $login_fabrica; ";
                        $res_cons = pg_query($con, $sql_cons);
                        if(pg_num_rows($res_cons) > 0){
                            $defeito_constatado_descricao = pg_fetch_result($res_cons,0,descricao);
                            $defeito_constatado_codigo    = pg_fetch_result($res_cons,0,codigo);
                            $defeito_constatado_id        = pg_fetch_result($res_cons,0,defeito_constatado);
                        }
                    }
                    echo "<input type='hidden' name='defeito_constatado' id='defeito_constatado' value='$defeito_constatado_id'>";

                    //hd 46589
                    echo "<input ";if ($login_fabrica<>43) echo "type='text'"; else echo "type='hidden'"; echo " name='defeito_constatado_codigo' id='defeito_constatado_codigo' size='5' value='$aux_defeito_constatado_codigo' onblur=\" pega_dc('$os','defeito_constatado','defeito_constatado_codigo','defeito_constatado_descricao'); \">";if ($login_fabrica<>43) echo "<img src='imagens/lupa.png' onclick='fnc_pesquisa_dc(\"$os\",document.frm_os.defeito_constatado,document.frm_os.defeito_constatado_codigo,document.frm_os.defeito_constatado_descricao)'>&nbsp;";

                    echo "<input type='text' name='defeito_constatado_descricao' id='defeito_constatado_descricao'";if ($login_fabrica==43) echo " size='30'";echo " value='$aux_defeito_constatado_descricao'><img src='imagens/lupa.png' onclick='fnc_pesquisa_dc(\"$os\",document.frm_os.defeito_constatado,document.frm_os.defeito_constatado_codigo,document.frm_os.defeito_constatado_descricao)'>&nbsp;";
                }
                echo "</td>";
            }
        }
        //CONSTATADO
        //SOLUCAO
        echo "<td>";
        if($login_fabrica==19){
            echo "<INPUT TYPE='hidden' name='solucao_os' value='367'>";
        }else{

            if ($pedir_solucao_os_item <> 'f' or (!in_array($login_fabrica,array(5,42,95)))) {
                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
                echo "<select name='solucao_os' id='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
                if($pedir_defeito_reclamado_descricao == 't' AND strlen($solucao_os) > 0 AND ($login_fabrica == 45 OR $login_fabrica == 43 OR $login_fabrica == 15 OR $login_fabrica == 35 OR $login_fabrica == 2 OR $login_fabrica == 46 OR $login_fabrica == 51 OR $login_fabrica==30 OR $login_fabrica==50 OR $login_fabrica == 40 OR $login_fabrica==56 OR $login_fabrica>56)){
                    $sql_cons = "SELECT solucao, descricao
                                    FROM tbl_solucao
                                    WHERE solucao = $solucao_os
                                    AND fabrica = $login_fabrica; ";
                    $res_cons = pg_query($con, $sql_cons);
                    if(pg_num_rows($res_cons) > 0){
                        $solucao_os_desc = pg_fetch_result($res_cons,0,descricao);
                        echo "<option id='opcoes' value='$solucao_os'>$solucao_os_desc</option>";
                    }else{
                        echo "<option id='opcoes' value=''></option>";
                    }
                }else{
                    echo "<option id='opcoes' value=''></option>";
                }
                echo "</select>";
            }
        }
        echo "</td>";
        //SOLUCAO
		if($login_fabrica == 90) { // HD 311795
			$sql_r = "SELECT recolhimento FROM tbl_os_extra WHERE os = $os";
			$query = pg_query($con,$sql_r);
			$check = pg_fetch_result($query,0,recolhimento);
			$found = FALSE;
			
			echo '<td>',
				 '	<font size="1"face="Geneva, Arial, Helvetica, san-serif">',
				 '		Recolhimento<br />';

			$opc = 't';
			if($opc == $check){	$checked = 'checked="checked"'; $found = TRUE; }
			else
				$checked = '';

			echo '		<input type="radio" name="recolhimento" value="sim" '. $checked . ' />Sim&nbsp;';

			$opc = 'f';
			if($opc == $check || $found == FALSE)
				$checked = 'checked';
			else
				$checked = '';

			echo	 '	<input type="radio" name="recolhimento" value="nao" '.$checked.' /> N&atilde;o&nbsp;',
				 '	</font>',
				 '</td>';
// HD 321628
			$sql_r = "SELECT 
					  CASE WHEN reoperacao_gas IS NULL THEN 'f' ELSE reoperacao_gas END as reop
					  FROM tbl_os_extra 
					  WHERE os = $os";
			$query = pg_query($con,$sql_r);
			$check = pg_fetch_result($query,0,reop);

			echo '<td>
					<font size="1"face="Geneva, Arial, Helvetica, san-serif">
						Reoperação de Gás<br />';

			$checked = $check == 't' ? 'checked' : '';
			echo '		<input type="radio" name="reop_gas" value="sim" '.$checked.' /> Sim&nbsp;';
			$checked = $check == 'f' ? 'checked' : '';
			echo	 '	<input type="radio" name="reop_gas" value="nao" '.$checked.' /> N&atilde;o&nbsp;',
				 '	</font>',
				 '</td>';

		}
    }
    echo "</tr>";
    echo "</table>";
}else {
    if ($login_fabrica == 59) {
        echo "<input type='hidden' name='defeito_constatado_codigo' id='defeito_constatado_codigo' value=''>";
        echo "<input type='hidden' name='defeito_constatado_descricao' id='defeito_constatado_descricao' value=''>";
    }
}
if($login_fabrica==30 or $login_fabrica ==43 or $login_fabrica == 59 or $login_fabrica == 2 or $login_fabrica == 85){
    echo "<input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'><br>";
    echo "
    <table style='border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='700' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
    <thead>
    <tr bgcolor='#596D9B' style='color:#FFFFFF;'>
    <td align='center'><b>Defeito Constatado</b></td>";
    if($login_fabrica == 43 or $login_fabrica == 59 or $login_fabrica == 2 or $login_fabrica == 85){
        echo "<td align='center'><b>Solução</b></td>";
    }
    echo "<td align='center'><b>Ações</b></td>";

    if($login_fabrica == 43){
        echo "<td align='center'><b>Principal</b></td>";
    }
    echo "</tr></thead><tbody>";
    //HD 232039: Achei este problema enquanto fazia o chamado e resolvi
    //Não pode ter este código aqui, senão não mostra os defeitos sempre que der erro:
    //if (strlen($msg_erro)==0 and strlen($erro)==0) {
    /*HD:  67783 -  ADICIONADO SOLUÇÃO PARA A NOVA COMPUTADORES*/
        $sql_cons = "SELECT
                    defeito_constatado_reclamado,
                    tbl_defeito_constatado.defeito_constatado,
                    tbl_defeito_constatado.descricao         ,
                    tbl_defeito_constatado.codigo,
                    tbl_solucao.solucao,
                    tbl_solucao.descricao as solucao_descricao
            FROM tbl_os_defeito_reclamado_constatado
            JOIN tbl_defeito_constatado USING(defeito_constatado)
            LEFT JOIN tbl_solucao USING(solucao)
            WHERE os = $os";
    $res_dc = pg_query($con, $sql_cons);
    if(pg_num_rows($res_dc) > 0){
        for($x=0;$x<pg_num_rows($res_dc);$x++){
            $id_defeito_constatado_reclamado = pg_fetch_result($res_dc,$x,defeito_constatado_reclamado);
            $dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
            $dc_solucao = pg_fetch_result($res_dc,$x,solucao);
            
            $sqlx = "SELECT defeito_constatado
                    FROM tbl_os
                    WHERE os  = $os
                    AND defeito_constatado = $dc_defeito_constatado";
            $resx = pg_query($con,$sqlx);

            $defeito_principal = 'f';
            if (pg_num_rows($resx) > 0) {
                $defeito_principal = 't';
            }

            $dc_descricao = pg_fetch_result($res_dc,$x,descricao);
            $dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
            $dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

            $aa = $x+1;
            echo "<tr>";
            echo "<td><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";
            if($login_fabrica == 43 or $login_fabrica == 59 or $login_fabrica == 2 or $login_fabrica == 85) {
                echo "<td><font size='1'><input type='hidden' name='integridade_solucao_$aa' value='$dc_solucao'>$dc_solucao_descricao</font></td>";
            }

            echo "<td align='right'><input type='button' onclick='removerIntegridade(this,$id_defeito_constatado_reclamado);' value='Excluir' ></td>";
            if($login_fabrica == 43) {
                echo "<td align='center'><input type='radio' name='principal' value='$dc_defeito_constatado'"; if ($defeito_principal == 't') echo " checked ";echo "></td>";
            }
            echo "</tr>";
        }
        echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
    }
    else {
        echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
        $itens_integridade = explode(';',$integridade_defeito_constatado);
        $itens_integridade_descricao = explode(';',$integridade_defeito_descricao);
        for ($f=0;$f<$qtde_integridade;$f++) {
            $inc = $f+1;
            echo "<tr>";
            echo "<td><font size='1'>$itens_integridade[$f]-$itens_integridade_descricao[$f]</font>
            <input type='hidden' name='integridade_defeito_constatado_$inc' id='integridade_defeito_constatado_$inc' value='$itens_integridade[$f]'>
            </td>";
            echo "<td align='right'>aaa<input type='button' onclick='removerIntegridade(this,\"amem\");' value='Excluir' ></td>";
            echo "</tr>";
        }
    }
echo "</tbody></table>";
}
if($login_fabrica==19){
    $sql = "SELECT defeito_reclamado
                FROM tbl_os_defeito_reclamado_constatado
                WHERE os                 = $os LIMIT 1";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)==0){
            $sql = "SELECT defeito_reclamado FROM tbl_os WHERE os=$os";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)>0){
                $aux_defeito_reclamado = pg_fetch_result($res,0,0);
                if (strlen(trim($aux_defeito_reclamado))> 0) {
                    $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                        os,
                        defeito_reclamado
                    )VALUES(
                        $os,
                        $aux_defeito_reclamado
                    )";
                    $res = pg_query($con,$sql);
                }
            }
        }

    echo "<table style=' border:#485989 1px solid; font-size:12px;' align='center' width='700' border='0' cellspacing='3' cellpadding='3' bgcolor='#e6eef7'>";
    echo "<thead>";
    echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
    echo "<td align='center'><b>Defeito Reclamado</b></td>";
    echo "<td align='center'><b>Defeito Constatado</b></td>";
    echo "<td align='center'><b>Adicionar</b></td>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    $sql_cons = "SELECT DISTINCT
                    DR.defeito_reclamado                  ,
                    DR.descricao           AS dr_descricao
            FROM tbl_os_defeito_reclamado_constatado RC
            LEFT JOIN tbl_defeito_reclamado          DR ON DR.defeito_reclamado  = RC.defeito_reclamado
            WHERE RC.os = $os
            AND   RC.defeito_reclamado IS NOT NULL";
    $res_dr = pg_query($con, $sql_cons);
    if(pg_num_rows($res_dr) > 0){
        for($x=0;$x<pg_num_rows($res_dr);$x++){
            $dr_defeito_reclamado  = pg_fetch_result($res_dr,$x,defeito_reclamado);
            $dr_descricao          = pg_fetch_result($res_dr,$x,dr_descricao);

            $aa = $x+1;

            $cor = ($x%2) ? "#e6eef7" : "#FFFFFF";

            echo "<tr bgcolor='$cor'>";
            echo "<td valign='top'>";
            echo "<input type='hidden' name='defeito_reclamado_$aa' id='defeito_reclamado_$aa' value='$dr_defeito_reclamado'>";
            echo "<input type='hidden' name='defeito_reclamado_descricao_$aa' id='defeito_reclamado_descricao_$aa' value='$dr_descricao'>";
            echo "$dr_descricao";
            echo "</td>";

            echo "<td>";
            echo "<select name='defeito_constatado_$aa' id='defeito_constatado_$aa' class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado_$aa.value,this);' >";
            echo "<option id='opcoes2' value=''></option>";
            echo "</select>";
                echo "<br><table id='tab_defeitos_$aa' name='tab_defeitos_$aa' style='font-size:12px;display:none' width='100%'>";
                echo "<thead><tr><td></td></tr></thead>";
                echo "<tbody>";
                $sql_cons = "SELECT DISTINCT
                                DC.defeito_constatado                 ,
                                DC.descricao           AS dc_descricao
                        FROM tbl_os_defeito_reclamado_constatado RC
                        JOIN tbl_defeito_constatado              DC ON DC.defeito_constatado = RC.defeito_constatado
                        WHERE RC.os = $os
                        AND   RC.defeito_reclamado = $dr_defeito_reclamado
                        AND   RC.defeito_constatado IS NOT NULL";

                $res_dc = pg_query($con, $sql_cons);
                if(pg_num_rows($res_dc) > 0){
                    for($y=0;$y<pg_num_rows($res_dc);$y++){
                        $dc_defeito_constatado = pg_fetch_result($res_dc,$y,defeito_constatado);
                        $dc_descricao          = pg_fetch_result($res_dc,$y,dc_descricao);
                        $bb = $y+1;
                        echo "<tr>";
                        echo "<td style='text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000'><font size='1'><input type='hidden' name=\"i_defeito_constatado_".$aa."_".$bb."\" id=\"i_defeito_constatado_".$aa."_".$bb."\" rel=\"defeito_constatado_".$aa."\" value='$dc_defeito_constatado'>$dc_descricao</font></td>";
                        echo "<td align='right'><input type='button' onclick='removerIntegridade2(this,\"tab_defeitos_$aa\");' value='Excluir'></td>";
                        echo "</tr>";
                    }
                    echo "<script>document.getElementById('tab_defeitos_$aa').style.display = \"inline\";</script>";
                }
                echo "</tbody>";
                echo "</table>";
            echo "</td>";
            echo "<td valign='top'>";
            echo "<input type='button' onclick=\"javascript: adicionaIntegridade2('$aa','tab_defeitos_$aa',document.frm_os.defeito_reclamado_$aa,document.frm_os.defeito_reclamado_descricao_$aa,document.frm_os.defeito_constatado_$aa)\" value='Adicionar Defeito' name='btn_adicionar'>";
            echo "</td>";

            echo "</tr>";
        }
        $aa++;
        /*
        echo "<tr bgcolor='#FFFFCC'>";
        echo "<td valign='top'>";
        echo "<input type='hidden' name='defeito_reclamado_$aa' id='defeito_reclamado_$aa' value='0'>";
        echo "<input type='hidden' name='defeito_reclamado_descricao_$aa' id='defeito_reclamado_descricao_$aa' value='0'>";
        echo "Não Informado";
        echo "</td>";

        echo "<td>";
        echo "<select name='defeito_constatado_$aa' id='defeito_constatado_$aa' class='frm' style='width: 220px;'>";
        $sql_cons = "SELECT DISTINCT
                    tbl_diagnostico.defeito_constatado,
                    tbl_defeito_constatado.descricao,
                    tbl_defeito_constatado.codigo
                FROM tbl_diagnostico
                JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado=tbl_defeito_constatado.defeito_constatado
                WHERE tbl_diagnostico.linha  = $produto_linha
                AND tbl_diagnostico.familia = $produto_familia
                AND tbl_diagnostico.ativo = 't'
                ORDER BY tbl_defeito_constatado.descricao";
        $res_cons = pg_query($con, $sql_cons);
        if(pg_num_rows($res_cons) > 0){
            echo "<option id='opcoes2' value=''></option>";
            for($j;$j<pg_num_rows($res_cons);$j++){
                $defeito_constatado      = pg_fetch_result($res_cons,$j,defeito_constatado);
                $defeito_constatado_desc = pg_fetch_result($res_cons,$j,descricao);
                echo "<option id='opcoes2' value='$defeito_constatado'>$defeito_constatado_desc</option>";
            }
        }else{
            echo "<option id='opcoes2' value=''></option>";
        }

        echo "</select>";
            echo "<br><table id='tab_defeitos_$aa' name='tab_defeitos_$aa' style='font-size:12px;display:none' width='100%'>";
            echo "<thead><tr><td></td></tr></thead>";
            echo "<tbody>";
                $sql_cons = "SELECT DISTINCT
                                DC.defeito_constatado                 ,
                                DC.descricao           AS dc_descricao
                        FROM tbl_os_defeito_reclamado_constatado RC
                        JOIN tbl_defeito_constatado              DC ON DC.defeito_constatado = RC.defeito_constatado
                        WHERE RC.os = $os
                        AND   RC.defeito_reclamado  IS     NULL
                        AND   RC.defeito_constatado IS NOT NULL";

                $res_dc = pg_query($con, $sql_cons);
                if(pg_num_rows($res_dc) > 0){
                    for($y=0;$y<pg_num_rows($res_dc);$y++){
                        $dc_defeito_constatado = pg_fetch_result($res_dc,$y,defeito_constatado);
                        $dc_descricao          = pg_fetch_result($res_dc,$y,dc_descricao);
                        $bb = $y+1;
                        echo "<tr>";
                        echo "<td style='text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000'><font size='1'><input type='hidden' name=\"i_defeito_constatado_".$aa."_".$bb."\" id=\"i_defeito_constatado_".$aa."_".$bb."\" rel=\"defeito_constatado_".$aa."\" value='$dc_defeito_constatado'>$dc_descricao</font></td>";
                        echo "<td align='right'><input type='button' onclick='removerIntegridade2(this,\"tab_defeitos_$aa\");' value='Excluir'></td>";
                        echo "</tr>";
                    }
                    echo "<script>document.getElementById('tab_defeitos_$aa').style.display = \"inline\";</script>";
                }
            echo "</tbody>";
            echo "</table>";
        echo "</td>";
        echo "<td valign='top'>";
        echo "<input type='button' onclick=\"javascript: adicionaIntegridade2('$aa','tab_defeitos_$aa',document.frm_os.defeito_reclamado_$aa,document.frm_os.defeito_reclamado_descricao_$aa,document.frm_os.defeito_constatado_$aa)\" value='Adicionar Defeito' name='btn_adicionar'>";
        echo "</td>";
        echo "</tr>";
        */
    }
    echo "</tbody></table>";
/*
    echo "
    <table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;' align='center' width='700' border='0' cellspacing='3' cellpadding='3' id='tbl_integridade'>
    <thead>
    <tr bgcolor='#596D9B' style='color:#FFFFFF;'>
    <td align='center'><b>Defeito Reclamado</b></td>
    <td align='center'><b>Defeito Constatado</b></td>
    <td align='center'><b>Ações</b></td>
    </tr>
    </thead>
    <tbody>";
    $sql_cons = "SELECT
                    DR.defeito_reclamado                  ,
                    DR.descricao           AS dr_descricao,
                    DC.defeito_constatado                 ,
                    DC.descricao           AS dc_descricao,
                    DC.codigo              AS dc_codigo
            FROM tbl_os_defeito_reclamado_constatado RC
            LEFT JOIN tbl_defeito_constatado         DC ON DC.defeito_constatado = RC.defeito_constatado
            LEFT JOIN tbl_defeito_reclamado          DR ON DR.defeito_reclamado  = RC.defeito_reclamado
            WHERE os = $os";
    $res_dc = pg_query($con, $sql_cons);
    if(pg_num_rows($res_dc) > 0){
        for($x=0;$x<pg_num_rows($res_dc);$x++){
            $dr_defeito_reclamado  = pg_fetch_result($res_dc,$x,defeito_reclamado);
            $dr_descricao          = pg_fetch_result($res_dc,$x,dr_descricao);
            $dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
            $dc_descricao          = pg_fetch_result($res_dc,$x,dc_descricao);
            $dc_codigo             = pg_fetch_result($res_dc,$x,dc_codigo);
            $aa = $x+1;
            echo "<tr>";
            echo "<td><font size='1'><input type='hidden' name='integridade_defeito_reclamado_$aa' id='integridade_defeito_reclamado_$aa' value='$dr_defeito_reclamado'>$dr_descricao</font></td>";
            echo "<td><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";
            echo "<td align='right'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
            echo "</tr>";


            echo "</tr>";
        }
        echo "";
    }
    echo "</tbody></table>";
*/



}
//fim caso nao achar defeito reclamado

//relacionamento de integridade termina aqui....

if(1==2){
        //if (($consumidor_revenda=="R") or (strlen($defeito_reclamado)==0) ){
     ?>
        <table width="100%" border="0" cellspacing="5" cellpadding="0">

        <?
/*         echo "<tr>";
        echo "<td>$defeito_reclamado</td>";
        echo "</tr>";*/
        ?>
        <tr>
            <?
                if ($pedir_defeito_constatado_os_item <> 'f') {
            ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if($login_fabrica=='20'){echo "Reparo";}else { echo "Defeito Constatado";} ?></font>
                <br>
                <select name="defeito_constatado" size="1" class="frm" style='width: 220px;'>
                    <option selected></option><?php
                $sql = "SELECT defeito_constatado_por_familia, defeito_constatado_por_linha FROM tbl_fabrica WHERE fabrica = $login_fabrica";
                 $res = pg_query($con,$sql);
                $defeito_constatado_por_familia = pg_fetch_result($res,0,0) ;
                $defeito_constatado_por_linha   = pg_fetch_result($res,0,1) ;

                if ($defeito_constatado_por_familia == 't') {
                    $sql = "SELECT familia FROM tbl_produto WHERE produto = $produto_os";
                    $res = pg_query($con,$sql);
                    $familia = pg_fetch_result($res,0,0) ;

                    if ($login_fabrica == 1){

                        $sql = "SELECT tbl_defeito_constatado.* FROM tbl_familia  JOIN   tbl_familia_defeito_constatado USING(familia) JOIN   tbl_defeito_constatado USING(defeito_constatado) ";
                        if ($linha == 198) $sql .= "LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado =  tbl_defeito_constatado.defeito_constatado AND tbl_produto_defeito_constatado.produto = $produto_os ";
                        $sql .= " WHERE  tbl_defeito_constatado.fabrica = $login_fabrica AND tbl_familia_defeito_constatado.familia = $familia";
                        if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
                        $sql .= " ORDER BY tbl_defeito_constatado.descricao";
                    }else{
                        $sql = "SELECT tbl_defeito_constatado.*
                                FROM   tbl_familia
                                JOIN   tbl_familia_defeito_constatado USING(familia)
                                JOIN   tbl_defeito_constatado         USING(defeito_constatado)
                                WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
                                AND    tbl_familia_defeito_constatado.familia = $familia";
                        if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
                        $sql .= " ORDER BY tbl_defeito_constatado.descricao";
                    }
                }else{

                    if ($defeito_constatado_por_linha == 't') {
                        $sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
                        $res   = pg_query($con,$sql);
                        $linha = pg_fetch_result($res,0,0) ;

                        $sql = "SELECT tbl_defeito_constatado.*
                                FROM   tbl_defeito_constatado
                                JOIN   tbl_linha USING(linha)
                                WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
                                AND    tbl_linha.linha = $linha";
                        if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
                        $sql .= " ORDER BY tbl_defeito_constatado.descricao";
                    }else{
                        $sql = "SELECT tbl_defeito_constatado.*
                            FROM   tbl_defeito_constatado
                            WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
                        if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
                        if ($login_fabrica ==11){$sql .= " ORDER BY tbl_defeito_constatado.codigo";
                        }else{$sql .= " ORDER BY tbl_defeito_constatado.descricao";}
                    }
                }

                #--------- Bosch ----------
                if ($login_fabrica == "20") {
                    $sql = "SELECT tbl_defeito_constatado.*
                            FROM tbl_defeito_constatado
                            JOIN tbl_produto_defeito_constatado
                                ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
                                AND tbl_produto_defeito_constatado.produto = $produto_os
                            ORDER BY tbl_defeito_constatado.descricao";
                }

                $res = pg_query($con,$sql) ;
                for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
                    echo "<option ";
                    if ($defeito_constatado == pg_fetch_result($res,$i,defeito_constatado) ) echo " selected ";
                    echo " value='" . pg_fetch_result($res,$i,defeito_constatado) . "'>" ;
                    echo pg_fetch_result($res,$i,codigo) ." - ". pg_fetch_result($res,$i,descricao) ;
                    echo "</option>";
                }
                ?>
                </select>

            </td>
            <? } ?>

            <?if ($pedir_causa_defeito_os_item <> 'f' and $login_fabrica <> 5 ) { ?>
            <td nowrap>
                <?
                if ($login_fabrica == 1 OR $login_fabrica == 51){
                    echo "<INPUT TYPE='hidden' name='name='causa_defeito' value='149'>";
                }else{
                ?>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito</font>
                <br>
                <select name="causa_defeito" size="1" class="frm"  style='width: 220px;'>
                    <option selected></option>
<?
                    $sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
                    $res = pg_query($con,$sql) ;

                    for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
                        echo "<option ";
                        if ($causa_defeito == pg_fetch_result($res,$i,causa_defeito) ) echo " selected ";
                        echo " value='" . pg_fetch_result($res,$i,causa_defeito) . "'>" ;
                        echo pg_fetch_result($res,$i,codigo) . " - " . pg_fetch_result($res,$i,descricao) ;
                        echo "</option>\n";
                    }
?>
                </select>
                <? } ?>
            </td>
            <? } ?>
        </tr>
        </table>
<?//identificacao?>
        <?if ($pedir_solucao_os_item <> 'f') { ?>
        <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td align="left" nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                <?
                    if($login_fabrica<>20) {echo "Solução";}
                    else echo "Identificação";
                ?>
                </font>
                <br>
                <select width="650" name="solucao_os" size="1" class="frm"  style='width: 220px;'>
                    <option value=""></option>
                <?

                    $sql = "SELECT *
                            FROM   tbl_servico_realizado
                            WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

                    if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15) {
                        $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                    }

                    if ($login_fabrica == 1) {
                        if ($login_reembolso_peca_estoque == 't') {
                            $sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'Troca de pe%' ";
                            $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
                            if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
                        }else{
                            $sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
                            $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
                            if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
                        }
                    }
                    if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS NOT TRUE ";

                    if (strlen($os_revenda)==0){
                        $sql .= " AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%' ";
                        $sql .= " AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%' ";
                    }

                    $sql .= " AND tbl_servico_realizado.ativo IS TRUE ";
                    $sql .= " ORDER BY descricao ";
                    $res = pg_query($con,$sql) ;

                    if (pg_num_rows($res) == 0) {
                        $sql = "SELECT *
                                FROM   tbl_servico_realizado
                                WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

                        if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15) {
                            $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                        }

                        if ($login_fabrica == 1) {
                            if ($login_reembolso_peca_estoque == 't') {
                                $sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'Troca de pe%' ";
                                $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
                            }else{
                                $sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
                                $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
                            }
                        }

                        if (strlen($os_revenda)==0){
                            $sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%' ";
                            $sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%' ";
                        }

                        $sql .=    " AND tbl_servico_realizado.linha IS NULL
                                AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
                        $res = pg_query($con,$sql) ;
                    }

                    for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
                        echo "<option ";
                        if ($solucao_os == pg_fetch_result($res,$x,servico_realizado)) echo " selected ";
                        echo " value='" . pg_fetch_result($res,$x,servico_realizado) . "'>" ;
                        echo pg_fetch_result($res,$x,descricao) ;
                        if (pg_fetch_result($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
                        echo "</option>";
                    } ?>
                </select>
            </td>
        </tr>
        </table>
        <? } ?>

<?
}//fim fabrica<>6    ?>

<?
    ##############################
    if($login_fabrica==30){ //HD 54672 ?>
        <BR><BR>
        <table border='0' cellpadding='0' cellspacing='0' align='center'>
            <tr>
                <td align='center' style='font-size: 12px;'>
                    <B>Qtde. Itens:</B>&nbsp;<!-- (<?=$qtde_itens_mostrar." / ".$qtde_item?>)-->
                    <select size='1' class="frm" name='qtde_itens_mostrar' onChange="javascript: document.frm_os.submit(); ">
                        <option value='5'  <? if ($qtde_itens_mostrar <= 5) echo 'selected'; ?>>05</option>
<?                    for ($val = 10; $val <= $qtde_item; $val+=10) { // o 5 é padrão, o resto vai de 10 em 10
                        echo "\t\t\t\t\t\t<option value='$val'";
                        if ($qtde_itens_mostrar == $val) echo " SELECTED";
                        echo ">$val</option>\n";
                    }
?>                    </select>
                </td>
            </tr>
        </table>
    <?}
    ##############################
?>
<?if ($login_fabrica==3){?>
    <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td align="right" nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Itens:</font><br>
                <select name="n_itens" size="1" class="frm" onchange='javascript:document.location.href="<? echo $PHP_SELF ?>?os=<? echo $os ?>&n_itens="+this.value'>
                <option value='3' <? if ($qtde_itens_mostrar==3)echo " selected"; ?>>3</option>
                <option value='5' <? if ($qtde_itens_mostrar==5)echo " selected"; ?>>5</option>
                </select>
            </td>
        </tr>
        </table>
<? }

if($tipo_atendimento==22 ) {
    echo "<center><font color=red size='1'>Não é possível lançar peças numa OS de Instalção</font></center>";
}else{
if ($login_fabrica == 15){
    $qtde_item = 40;
    ?>
    <table width="100%" border="0" cellspacing="1" cellpadding="0">
        <tr>
            <td align="right" nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Itens:</font><br>
                <select name="n_itens" size="1" class="frm" onchange='javascript:mostrarLinhas(this.value);'>
<?                    for ($val = 10; $val <= $qtde_item; $val+=10) {
                        echo "<option value='$val'";
                        if ($qtde_itens_mostrar == 10) echo " SELECTED";
                        echo ">$val</option>\n";
                    }
?>                    </select>
            </td>
        </tr>
    </table>
<? } ?>
<?
    if ($login_fabrica == 45 or $login_fabrica == 51 or $login_fabrica==3) {
        $sqlv = "select comunicado,extensao
                from tbl_comunicado
                where fabrica=$login_fabrica
                and tipo='Vista Explodida'
                and produto=$produto_os";

        $resv = pg_query($con,$sqlv) ;
        if (pg_num_rows($resv) > 0) {
            $vcomunicado      = pg_fetch_result($resv,0,comunicado);
            $vextensao        = pg_fetch_result($resv,0,extensao);
            echo "<br>";
                echo "<a href='comunicados/$vcomunicado.$vextensao' target='_blank'>Clique aqui</a> para ver a vista-explodida";
            echo "<br>";
        }
    }

if ($login_fabrica == 19 or $login_fabrica == 30 or $login_fabrica == 59) {?>
        <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td align="left" nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Técnico</font>
                <br>
                <?
                if ($login_fabrica == 59 && strlen($tecnico_nome) == 0) {
                    $sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica=$login_fabrica AND posto=$login_posto";
                    $res_tecnico = pg_query($con, $sql);

                    if (pg_num_rows($res_tecnico) == 0) {
                        echo "<a href='tecnico_cadastro.php' titule='É necessário cadastrar o técnico do Cadastro de Técnicos para continuar' style='font-size:9pt;'>Cadastre um técnico para continuar</a>";
                    }
                    else {
                        echo "
                        <select name='tecnico' id='tecnico' class='frm'>
                            <option value='null'>SELECIONE UM TÉCNICO</option>";

                        for ($t = 0; $t < pg_num_rows($res_tecnico); $t++) {
                            $tecnico_select = pg_result($res_tecnico, $t, tecnico);
                            $nome_select = pg_result($res_tecnico, $t, nome);

                            if ($tecnico_select == $tecnico) {
                                $selected = "selected";
                            }
                            else {
                                $selected = "";
                            }

                            echo "
                            <option value='$tecnico_select' $selected>$nome_select</option>";
                        }

                        $help = "Para cadastrar um novo técnico acesse aba Cadastro >> Cadastro de Técnicos";

                        echo "
                        </select> <img src='imagens/help.png' style='cursor:pointer' title='$help' onclick=\"alert('$help')\">";
                    }
                }
                else {
                ?>
                <input type='text' name='tecnico_nome' size='20' maxlength='20' value='<? echo $tecnico_nome ?>'>
                <?
					#HD 276459 visita_agendada
					if ($login_fabrica == 30) {
						$sql_om = "SELECT substr(tbl_marca.nome,0,6) as marca from tbl_os join tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_os.cliente_admin join tbl_marca ON tbl_marca.marca = tbl_cliente_admin.marca where tbl_os.os = $os";

						$res_om = pg_exec($con,$sql_om);

						if (pg_num_rows($res_om)>0) {
							$marca_os = pg_result($res_om,0,0);
							if ($marca_os == 'AMBEV') {
								echo "<input type='hidden' name='cobrar_visita' value='sim'>";
								echo "<td> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Agendar Visita</font><br><input type='text' value='$visita_agendada' name='visita_agendada' id='visita_agendada' onchange='gravaVisita($os,this.value)'></td>";
							}
						}
					}
				}
                ?>
            </td>
            <?php # HD 44487
            if ( /* HD 51526 - liberado para todos os postos
            ($login_posto == 14068 or $login_posto == 6359) and */
                $produto_linha == 260){ ?>
                <td align="left" nowrap>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Mês e Ano de Fabricação do Produto</font>
                    <br>
                    <input type='text' name='fabricacao_produto' id='fabricacao_produto' size='16' maxlength='20' value='<? echo $fabricacao_produto ?>'>
                </td>

                <? //hd 47311
                    if (1==2) {?>
                        <td align="left" nowrap>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Sequência (Ex. 01, 02... 99)</font>
                            <br>
                            <input type='text' name='codigo_fabricacao' size='10' maxlength='2' value='<? echo $codigo_fabricacao ?>'>
                        </td>
                <?}?>
            <?php } ?>
        </tr>
        </table>
<?
}

        ### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
        if (strlen($os) > 0){
            $sql = "SELECT  tbl_os_item.os_item                                 ,
                            tbl_os_item.pedido                                  ,
                            tbl_pedido.pedido_blackedecker  AS pedido_blackedecker,
                            tbl_os_item.qtde                                    ,
                            tbl_os_item.causa_defeito                           ,
                            tbl_peca.referencia                                 ,
                            tbl_peca.descricao                                  ,
                            tbl_peca.devolucao_obrigatoria                      ,
                            tbl_defeito.defeito                                 ,
                            tbl_defeito.descricao AS defeito_descricao          ,
                            tbl_causa_defeito.descricao AS causa_defeito_descricao,
                            tbl_produto.referencia AS subconjunto               ,
                            tbl_os_produto.produto                              ,
                            tbl_os_produto.serie                                ,
                            tbl_servico_realizado.servico_realizado             ,
                            tbl_servico_realizado.descricao AS servico_descricao,
                            tbl_os_item.peca_serie_trocada
                    FROM    tbl_os
                    JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
                    JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
                    JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
                    JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
                    JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
                    LEFT JOIN    tbl_defeito           USING (defeito)
                    LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
                    LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
                    WHERE   tbl_os.os      = $os
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_os_item.pedido NOTNULL
                    ORDER BY tbl_os_item.os_item ASC;";
            $res = pg_query($con,$sql) ;

            if(pg_num_rows($res) > 0) {
                echo "<table width='100%' border='0' cellspacing='2' cellpadding='0' class='tabela'>";
                echo "<tr height='20'>";
                $colspan = 4;
                if($informatica == 't'){
                    $colspan = 5;
                    echo "<INPUT TYPE='hidden' NAME='informatica' VALUE='t'>";
                }
                if ($login_fabrica == 51){
                    $colspan = 5;
                }
                echo "<td align='center' colspan='$colspan' class='titulo_coluna'>Pedidos enviados ao fabricante</td>";

                echo "</tr>";
                echo "<tr height='20' bgcolor='#666666'>";

                echo "<td align='center' class='titulo_coluna'>Pedido</td>";
                echo "<td align='center' class='titulo_coluna'>Referência</td>";
                echo "<td align='center' class='titulo_coluna'>Descrição</td>";
                echo "<td align='center' class='titulo_coluna'>Qtde</td>";
                if ($login_fabrica == 51){
                    echo "<td align='center' class='titulo_coluna'>Retornável</td>";
                }
                if($informatica == 't'){
                    echo "<td align='center' class='titulo_coluna'>N. Série Peça Trocada</td>";
                }

                echo "</tr>";

                for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                        $faturado      = pg_num_rows($res);
                        $fat_pedido    = pg_fetch_result($res,$i,pedido);
                        $fat_pedido_blackedecker = pg_fetch_result($res,$i,pedido_blackedecker);
                        $fat_peca      = pg_fetch_result($res,$i,referencia);
                        $fat_descricao = pg_fetch_result($res,$i,descricao);
                        $fat_qtde      = pg_fetch_result($res,$i,qtde);

                        $peca_serie_trocadax = pg_fetch_result($res,$i,peca_serie_trocada);
                        $os_item       = pg_fetch_result($res,$i,os_item);

                        $devolucao_obrigatoria = pg_fetch_result($res,$i,devolucao_obrigatoria);

                        if ($devolucao_obrigatoria == "t"){
                            $devolucao_obrigatoria = "Sim";
                        }else{
                            $devolucao_obrigatoria = "Não";
                        }

                        echo "<tr height='20' bgcolor='$cor' style='text-align:justify;'>";

                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";
                        if ($login_fabrica == 1) {
                            echo $fat_pedido_blackedecker;
                        } else {
                            echo $fat_pedido;
                        }
                        echo "</font></td>";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>";
                        echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>";

                        if ($login_fabrica == 51){
                            echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$devolucao_obrigatoria</font></td>";
                        }

                        //HD 38818 4/9/2008 ----------------------------
                        if($informatica == 't'){
                            echo "</td>";
                            echo "<td align='center'>";
                            echo "<INPUT TYPE='hidden' NAME='os_item_$i' id='os_item_$i' VALUE='$os_item'>";
                            ?>
                            <INPUT TYPE='text' NAME='peca_serie_trocada_<?=$i?>' id='peca_serie_trocada_<?=$i?>' value='<? echo $peca_serie_trocadax; ?>' size='13' maxlength='20' <? echo "onblur=\"javascript:atualizaserietrocada('os_item_$i','peca_serie_trocada_$i', $i)\""; ?> >
                            <div id='retorno_serie_<?=$i?>' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:150px;'>
                            </div>
                            <?
                            echo "</td>";
                        }

                        echo "</tr>";
                }
                echo "</table>";
            }
        }

        ### LISTA ITENS DA OS QUE ESTÃO COMO NÃO LIBERADAS PARA PEDIDO EM GARANTIA
        if (strlen($os) > 0){
            $sql = "SELECT  tbl_os_item.os_item                                 ,
                            tbl_os_item.obs                                     ,
                            tbl_os_item.qtde                                    ,
                            tbl_peca.referencia                                 ,
                            tbl_peca.descricao                                  ,
                            tbl_defeito.defeito                                 ,
                            tbl_defeito.descricao AS defeito_descricao          ,
                            tbl_produto.referencia AS subconjunto               ,
                            tbl_os_produto.produto                              ,
                            tbl_os_produto.serie                                ,
                            tbl_servico_realizado.servico_realizado             ,
                            tbl_servico_realizado.descricao AS servico_descricao
                    FROM    tbl_os
                    JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
                    JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
                    JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
                    JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
                    LEFT JOIN    tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
                    LEFT JOIN    tbl_defeito           USING (defeito)
                    LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
                    WHERE   tbl_os.os      = $os
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_os_item.liberacao_pedido           IS FALSE
                    AND     tbl_os_item.liberacao_pedido_analisado IS TRUE
                    ORDER BY tbl_os_item.os_item ASC;";
            $res = pg_query($con,$sql) ;

            $sqlOrcamento = "SELECT tbl_orcamento_item.orcamento_item           ,
                            tbl_orcamento_item.qtde                             ,
                            tbl_peca.referencia                                 ,
                            tbl_peca.descricao                                  ,
                            tbl_produto.referencia AS subconjunto               ,
                            tbl_os.produto                                      ,
                            tbl_os.serie                                        ,
                            tbl_orcamento_item.pedido                           ,
                            tbl_servico_realizado.servico_realizado             ,
                            tbl_servico_realizado.descricao AS servico_descricao
                    FROM    tbl_os
                    JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
                    JOIN    tbl_orcamento              ON tbl_orcamento.os = tbl_os.os
                    JOIN    tbl_orcamento_item         ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
                    JOIN    tbl_produto                ON tbl_os.produto = tbl_produto.produto
                    JOIN    tbl_peca                   ON tbl_orcamento_item.peca   = tbl_peca.peca
                    LEFT JOIN    tbl_pedido            ON tbl_orcamento_item.pedido = tbl_pedido.pedido
                    LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
                    WHERE   tbl_os.os      = $os
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_orcamento_item.pedido           NOTNULL
                    ORDER BY tbl_orcamento_item.orcamento_item ASC;";
            $resOrca = pg_query($con,$sqlOrcamento) ;

            if(pg_num_rows($res) > 0 OR pg_num_rows($resOrca) > 0) {
                $col = 4;
                if($login_fabrica == 14){ $col = 5; }
                echo "<table width='100%' border='3' cellspacing='2' cellpadding='0'>\n";
                echo "<tr height='20' bgcolor='#666666'>\n";

                if ($login_fabrica <> 6) {
                    echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia </b></font></td>\n";
                }else{
                    echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças pendentes</b></font></td>\n";
                }

                echo "</tr>\n";
                echo "<tr height='20' bgcolor='#666666'>\n";

                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";
                if($login_fabrica == 14){ echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Excluir</b></font></td>\n";    }
                echo "</tr>\n";

                for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                    $recusado      = pg_num_rows($res);
                    $rec_item      = pg_fetch_result($res,$i,os_item);
                    $rec_obs       = pg_fetch_result($res,$i,obs);
                    $rec_peca      = pg_fetch_result($res,$i,referencia);
                    $rec_descricao = pg_fetch_result($res,$i,descricao);
                    $rec_qtde      = pg_fetch_result($res,$i,qtde);

                    echo "<tr height='20' bgcolor='#FFFFFF'>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
                    echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";
                    if($login_fabrica == 14){
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='$PHP_SELF?os=$os&os_item=$rec_item'><IMG SRC=\"imagens/btn_excluir.gif\" ALT=\"Excluir\"></font></a></td>";
                    }
                    echo "</tr>\n";
                }

                for ($i = 0 ; $i < pg_num_rows($resOrca) ; $i++) {
                        $recusado      = pg_num_rows($resOrca);
                        $rec_item      = pg_fetch_result($resOrca,$i,orcamento_item);
                        $rec_peca      = pg_fetch_result($resOrca,$i,referencia);
                        $rec_pedido    = pg_fetch_result($resOrca,$i,pedido);
                        $rec_descricao = pg_fetch_result($resOrca,$i,descricao);
                        $rec_qtde      = pg_fetch_result($resOrca,$i,qtde);

                        echo "<tr height='20' bgcolor='#FFFFFF'>";

                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_pedido</font></td>\n";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
                        echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

                        echo "</tr>\n";
                }
                echo "</table>\n";
            }
        }

        ### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSEM PEDIDO
        if (strlen($os) > 0){
            $sql = "SELECT  tbl_os_item.os_item                                 ,
                            tbl_os_item.obs                                     ,
                            tbl_os_item.qtde                                    ,
                            tbl_peca.referencia                                 ,
                            tbl_peca.descricao                                  ,
                            tbl_defeito.defeito                                 ,
                            tbl_defeito.descricao AS defeito_descricao          ,
                            tbl_produto.referencia AS subconjunto               ,
                            tbl_os_produto.produto                              ,
                            tbl_os_produto.serie                                ,
                            tbl_servico_realizado.servico_realizado             ,
                            tbl_servico_realizado.descricao AS servico_descricao
                            ,peca_reposicao_estoque,
                            aguardando_peca_reparo
                    FROM    tbl_os
                    JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
                    JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
                    JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
                    JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
                    LEFT JOIN    tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
                    LEFT JOIN    tbl_defeito           USING (defeito)
                    LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
                    WHERE   tbl_os.os      = $os
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_os_item.pedido           ISNULL
                    AND     tbl_os_item.liberacao_pedido IS TRUE
                    ORDER BY tbl_os_item.os_item ASC;";
            $res = pg_query($con,$sql) ;

            if(pg_num_rows($res) > 0) {
                echo "<table width='70%' border='0' cellspacing='2' cellpadding='0'>\n";
                echo "<tr height='20' bgcolor='#666666'>\n";

                echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";

                echo "</tr>\n";
                echo "<tr height='20' bgcolor='#666666'>\n";

                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
                echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

                echo "</tr>\n";

                for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                        $recusado      = pg_num_rows($res);
                        $rec_item      = pg_fetch_result($res,$i,os_item);
                        $rec_peca      = pg_fetch_result($res,$i,referencia);
                        $rec_descricao = pg_fetch_result($res,$i,descricao);
                        $rec_qtde      = pg_fetch_result($res,$i,qtde);

                        echo "<tr height='20' bgcolor='#FFFFFF'>";

                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
                        echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

                        echo "</tr>\n";
                }
                echo "</table>\n";
            }
        }

        if (strlen($os) > 0 AND strlen($msg_erro) == 0){
            if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
                $sql = "SELECT  tbl_peca.peca
                        FROM    tbl_peca
                        JOIN    tbl_lista_basica USING (peca)
                        JOIN    tbl_produto      USING (produto)
                        WHERE   tbl_produto.produto     = $produto_os
                        AND     tbl_peca.fabrica        = $login_fabrica
                        AND     tbl_peca.item_aparencia = 't'
                        ORDER BY tbl_peca.referencia;";
                $resX = pg_query($con,$sql);
                $inicio_itens = pg_num_rows($resX);
            }else{
                $inicio_itens = 0;
            }

            $sql = "SELECT  tbl_os_item.os_item                                                ,
                            tbl_os_item.pedido                                                 ,
                            tbl_os_item.qtde                                                   ,
                            tbl_os_item.causa_defeito                                          ,
                            tbl_os_item.posicao                                                ,
                            tbl_os_item.admin              as admin_peca                       ,
                            tbl_os_item.peca_serie                                             ,
                            tbl_os_item.peca_serie_trocada                                     ,
                            tbl_peca.referencia                                                ,
                            tbl_peca.descricao                                                 ,
                            tbl_defeito.defeito                                                ,
                            tbl_defeito.descricao                   AS defeito_descricao       ,
                            tbl_causa_defeito.descricao             AS causa_defeito_descricao ,
                            tbl_produto.referencia                  AS subconjunto             ,
                            tbl_os_produto.os_produto                                          ,
                            tbl_os_produto.produto                                             ,
                            tbl_os_produto.serie                                               ,
                            tbl_servico_realizado.servico_realizado                            ,
                            tbl_servico_realizado.descricao         AS servico_descricao,
                            tbl_os_item.peca_reposicao_estoque                                 ,
                            tbl_os_item.aguardando_peca_reparo
                    FROM    tbl_os
                    JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
                    JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
                    JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
                    JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
                    LEFT JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
                    LEFT JOIN    tbl_defeito           USING (defeito)
                    LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
                    LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
                    WHERE   tbl_os.os      = $os
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_os_item.pedido           ISNULL
                    AND     tbl_os_item.liberacao_pedido IS FALSE
                    ORDER BY tbl_os_item.os_item;";
            $res = pg_query($con,$sql) ;
            $fim_itens = 0;
            if (pg_num_rows($res) > 0) {
                $fim_itens = $inicio_itens + pg_num_rows($res);
                //$qtde_item = $qtde_item + $inicio_itens ;

                $i = 0;

                //hd 44118 - tem que zerar a variável, senão o php não entende que é um array, pois já foi usada anteriormente variável com este nome
                // MLG - apaga a variável da memória
                unset($os_item);
                for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
                    $os_item[$k]                 = pg_fetch_result($res,$i,os_item);
                    $os_produto[$k]              = pg_fetch_result($res,$i,os_produto);
                    $pedido[$k]                  = pg_fetch_result($res,$i,pedido);
                    $peca[$k]                    = pg_fetch_result($res,$i,referencia);
                    $qtde[$k]                    = pg_fetch_result($res,$i,qtde);
                    $produto[$k]                 = pg_fetch_result($res,$i,subconjunto);
                    $serie[$k]                   = pg_fetch_result($res,$i,serie);
                    $posicao[$k]                 = pg_fetch_result($res,$i,posicao);
                    $descricao[$k]               = pg_fetch_result($res,$i,descricao);
                    $defeito[$k]                 = pg_fetch_result($res,$i,defeito);
                    $pcausa_defeito[$k]          = pg_fetch_result($res,$i,causa_defeito);
                    $causa_defeito_descricao[$k] = pg_fetch_result($res,$i,causa_defeito_descricao);
                    $defeito_descricao[$k]       = pg_fetch_result($res,$i,defeito_descricao);
                    $servico[$k]                 = pg_fetch_result($res,$i,servico_realizado);
                    $peca_serie[$k]              = pg_fetch_result($res,$i,peca_serie);
                    $peca_serie_trocada[$k]      = pg_fetch_result($res,$i,peca_serie_trocada);
                    $servico_descricao[$k]       = pg_fetch_result($res,$i,servico_descricao);
                    $admin_peca[$k]              = pg_fetch_result($res,$i,admin_peca);//aqui
                    if (strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }
                    $peca_reposicao_estoque[$k]  = pg_fetch_result($res,$i,peca_reposicao_estoque);//aqui
                    $aguardando_peca_reparo[$k]  = pg_fetch_result($res,$i,aguardando_peca_reparo);//aqui
                    $i++;
                }
            }else{
                // HD 73196 - MLG - A variável tem que ser apagada também para esta iteração
                unset($os_item);
                for ($i = 0 ; $i < $qtde_item ; $i++) {
                    $os_item[$i]        = $_POST["os_item_"        . $i];
                    $orcamento_item[$i] = $_POST["orcamento_item_" . $i];
                    $os_produto[$i]     = $_POST["os_produto_"     . $i];
                    $produto[$i]        = $_POST["produto_"        . $i];
                    $serie[$i]          = $_POST["serie_"          . $i];
                    $posicao[$i]        = $_POST["posicao_"        . $i];
                    $peca[$i]           = $_POST["peca_"           . $i];
                    $descricao[$i]           = $_POST["descricao_"      . $i];
                    $qtde[$i]           = $_POST["qtde_"           . $i];
                    $defeito[$i]        = $_POST["defeito_"        . $i];
                    $pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
                    $servico[$i]        = $_POST["servico_"        . $i];
                    $peca_serie[$i]     = $_POST["peca_serie_"     . $i];
                    $peca_serie_trocada[$i] = $_POST["peca_serie_trocada_" . $i];
                    $admin_peca[$i]     = $_POST["admin_peca_"     . $i]; //aqui
                    $peca_reposicao_estoque[$i] = $_POST["peca_reposicao_estoque_" . $i]; //aqui
                    $aguardando_peca_reparo[$i] = $_POST["aguardando_peca_reparo_" . $i]; //aqui

                    if (strlen($peca[$i]) > 0) {
                        $sql = "SELECT  tbl_peca.referencia,
                                        tbl_peca.descricao
                                FROM    tbl_peca
                                WHERE   tbl_peca.fabrica    = $login_fabrica
                                AND     tbl_peca.referencia = $peca[$i];";
                        $resX = pg_query($con,$sql) ;

                        if (pg_num_rows($resX) > 0) {
                            $descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
                        }
                    }
                }
            }

            # Pega itens do Orçamento
            $sql = "SELECT  tbl_orcamento_item.orcamento_item                                  ,
                            tbl_orcamento_item.peca                                            ,
                            tbl_orcamento_item.qtde                                            ,
                            tbl_orcamento_item.preco                                           ,
                            tbl_orcamento_item.preco_venda                                     ,
                            tbl_orcamento_item.defeito                                         ,
                            tbl_orcamento_item.servico_realizado                               ,
                            tbl_peca.referencia                                                ,
                            tbl_peca.descricao                                                 ,
                            tbl_defeito.descricao                   AS defeito_descricao
                    FROM    tbl_orcamento
                    JOIN    tbl_orcamento_item         ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
                    JOIN    tbl_peca                   ON tbl_peca.peca                = tbl_orcamento_item.peca
                    LEFT JOIN    tbl_defeito           ON tbl_defeito.defeito          = tbl_orcamento_item.defeito
                    WHERE   tbl_orcamento.os      = $os
                    AND     tbl_orcamento.empresa = $login_fabrica
                    AND     tbl_orcamento_item.pedido IS NULL
                    ORDER BY tbl_orcamento_item.orcamento_item;";
            $res = pg_query($con,$sql) ;

            if (pg_num_rows($res) > 0) {
                $qtde_itens_orcado = pg_num_rows($res);
                $i = 0;
                // HD 354997 - MLG - A variável tem que ser apagada também para esta iteração
                unset($os_item);
                for ($j = $fim_itens; $j < $fim_itens + $qtde_itens_orcado ; $j++) {

                    $orcamento_item[$j]          = trim(pg_fetch_result($res,$i,orcamento_item));
                    $os_item[$j]                 = "";
                    $os_produto[$j]              = "";
                    $pedido[$j]                  = "";
                    $peca[$j]                    = trim(pg_fetch_result($res,$i,referencia));
                    $qtde[$j]                    = trim(pg_fetch_result($res,$i,qtde));
                    $preco[$j]                   = trim(pg_fetch_result($res,$i,preco));
                    $preco_venda[$j]             = trim(pg_fetch_result($res,$i,preco_venda));
                    $produto[$j]                 = "";
                    $serie[$j]                   = "";
                    $posicao[$j]                 = "";
                    $descricao[$j]               = trim(pg_fetch_result($res,$i,descricao));
                    $defeito[$j]                 = trim(pg_fetch_result($res,$i,defeito));
                    $pcausa_defeito[$j]          = "";
                    $causa_defeito_descricao[$j] = "";
                    $defeito_descricao[$j]       = "";
                    $servico[$j]                 = trim(pg_fetch_result($res,$i,servico_realizado));
                    $servico_descricao[$j]       = "";
                    $admin_peca[$j]              = "";
                    if (strlen($admin_peca[$j])==0) { $admin_peca[$j]="P"; }
                    $i++;
                $preco[$j] = number_format($preco[$j],2,',','.');
                $preco_venda[$j] = number_format($preco_venda[$j],2,',','.');
                }
            }
        }else{
            // HD 354997 - MLG - A variável tem que ser apagada também para esta iteração
	    unset($os_item);
            for ($i = 0 ; $i < $qtde_item ; $i++) {
                $os_item[$i]        = $_POST["os_item_"        . $i];
                $orcamento_item[$i] = $_POST["orcamento_item_" . $i];
                $os_produto[$i]     = $_POST["os_produto_"     . $i];
                $produto[$i]        = $_POST["produto_"        . $i];
                $serie[$i]          = $_POST["serie_"          . $i];
                $posicao[$i]        = $_POST["posicao_"        . $i];
                $peca[$i]           = $_POST["peca_"           . $i];
                $qtde[$i]           = $_POST["qtde_"           . $i];
                $defeito[$i]        = $_POST["defeito_"        . $i];
                $pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
                $servico[$i]        = $_POST["servico_"        . $i];
                $peca_serie[$i]     = $_POST["peca_serie_"     . $i];
                $descricao[$i]      = $_POST["descricao_"     . $i];
                $xkit_peca[$i]      = $_POST["kit_kit_peca_"     . $i];
                $peca_serie_trocada[$i] = $_POST["peca_serie_trocada_" . $i];
                $admin_peca[$i]     = $_POST["admin_peca_"     . $i];//aqui
                if (strlen($peca[$i]) > 0) {
                    $sql = "SELECT  tbl_peca.referencia,
                                    tbl_peca.descricao
                            FROM    tbl_peca
                            WHERE   tbl_peca.fabrica    = $login_fabrica
                            AND     tbl_peca.referencia = '$peca[$i]';";
                    $resX = pg_query($con,$sql) ;
                    if (pg_num_rows($resX) > 0) {
                        $descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
                    }
                }
            }
        }
        if ($login_fabrica == 3 || $login_fabrica == 43) {//HD 255541

            echo "<table width='800px' border='0' cellspacing='1' cellpadding='2'>";
            echo "<tr height='10' bgcolor='#17203E' style='font: normal bold 13px Geneva, Arial, Helvetica, san-serif;'>";
            echo "<td align='center' style='color:#1ebb1e'>Apenas uma peça.<br>OS com pedido aprovado</td>";
            echo "<td align='center' style='color:#ffff00'>Duas peças.<br>OS e pedidos sujeitos a análise</td>";
            echo "<td align='center' style='color:#ff0000'>Três ou mais peças.<br>OS e pedidos sujeitos a auditoria</td>";
        }
        echo "<table width='100%' border='0' cellspacing='2' cellpadding='0' id='tablemostrar'>";

        echo "<tr height='30' class='titulo_coluna'>";

        if ($os_item_subconjunto == 't') {
            echo "<td align='center'><b>Subconjunto</b></td>";
        }

        if($login_fabrica == 56){
            echo "<td align='center'><b>N. Série</b></td>";
        }

        if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
            echo "<td align='center'><b>";
            if($login_fabrica==35){
                echo "PO#";
            }else{
                echo "N. Série";
            }
            echo "</b></font></td>";
        }

        #HD 132467
        if($login_fabrica==19){
            $sql ="select tipo_atendimento
            from tbl_os
            where os = $os";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res)>0){
                $tipo_atendimento_x = pg_fetch_result($res,0,tipo_atendimento);
            }
        }

        if($login_fabrica==19 AND ($tipo_atendimento_x==34 OR $tipo_atendimento_x==19 OR $tipo_atendimento_x==33)){
            #HD 132467
            $block_item='DISABLED';
        }else{
            $block_item='';
        }

        if ($login_fabrica == 14) echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";

        if ($login_fabrica == 15 || $login_fabrica == 24) {
            echo "<td align='center' style='width:30%;'><b>&nbsp; Descrição &nbsp;</b></td>";
        }else{
            echo "<td align='center'><b>&nbsp; Código &nbsp;</b></td>";
        }
                /*
                echo "<acronym title=\"Clique para abrir a lista básica do produto.\"><a class='lnk' href='peca_consulta_por_produto";
        if ($login_fabrica == 14) echo "_subconjunto";
        echo ".php?produto=$produto_os' target='_blank'>LISTA BÁSICA<img src='imagens/btn_lista.gif'></a></acronym>";*/
        //echo "</td>";
             /*   echo "<td width='60' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>LISTA BÁSICA</FONT></TD>";*/

        if ($login_fabrica ==14) $link_suffix = "_subconjunto";
        $link_param = "?produto=$produto_os";
        if ($login_fabrica == 6) $link_param .= "&os=$os";

        echo "<td align='center'>\n";
        if ($login_fabrica == 11) { // 19/01/2010 HD 192641 - Lenoxx não mostra lista básica do produto
            echo "<span style='color:white;font-size:10px'>Lista Básica</span>\n";
        } else {
            echo "<a href='peca_consulta_por_produto".$link_suffix.".php".$link_param."' ".
                 "title='Clique para abrir a lista básica do produto' ".
                 "class='lnk' target='_blank'>".
                 "LISTA BÁSICA</a>\n";
        }
        echo "</td>\n";

        if ($login_fabrica == 15 || $login_fabrica == 24) {
            echo "<td align='center'><b>Código</b></td>";
        }else{
            echo "<td align='center' width='45'><b>Descrição</b></td>";
        }
        //HD 38818 4/9/2008 ----------------------------
        if($informatica == 't'){
            echo "<td align='center'><b>N. Série Peça</b></td>";
        }
        if ($pergunta_qtde_os_item == 't') {
            echo "<td align='center'><b>Qtde</b></td>";
        }

        if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
            echo "<td align='center'><b>Causa</b></td>";
        }
        echo "<td align='center'><b>Defeito</b></td>";
        echo "<td align='center'><b>";
		echo ($login_fabrica == 96)?"Free of charge":"Serviço"; # HD 390996
		echo "</b></td>";

        //HD 38818 4/9/2008 ----------------------------
        if($informatica == 't'){
            echo "<td align='center' colspan='2'><b>N. Série Peça Trocada</b></td>";
        }
        //---------------------

        echo "</tr>";

if ($login_fabrica==6 or ($login_fabrica == 15 and in_array($login_posto, array(6359,10950,10952,20235,2405,5551,12008,11946,11467,10806,11958,11946,11471,11732,118825)) and $consumidor_revenda == 'R')){
/*HD 2599, 15180, 11/12/2009-HD 183336*/
    if($login_fabrica==6){
    $sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_lista_basica.qtde
            , tbl_lista_basica.serie_inicial, tbl_lista_basica.serie_final, ' ' as produto_serie_inicial, ' ' as  produto_serie_final
            FROM  tbl_lista_basica
            JOIN  tbl_peca using(peca)
            WHERE tbl_lista_basica.fabrica = $login_fabrica
            AND   tbl_lista_basica.produto = $produto_os
            AND   tbl_peca.item_aparencia  = 'f'
            AND   tbl_peca.pre_selecionada = 't'
            ORDER by tbl_peca.referencia; ";
    }else{
//  14/01/2010 HD 189523 - Lista básica não filtra por $serie[1]
    //HD 196225: Mudar ordenação da lista básica para OS de revenda
    $sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_lista_basica.qtde,
            tbl_lista_basica.serie_inicial, tbl_lista_basica.serie_final,
            tbl_produto.serie_inicial AS produto_serie_inicial, tbl_produto.serie_final AS produto_serie_final
            FROM tbl_lista_basica
            JOIN tbl_peca     USING(peca)
            JOIN tbl_produto USING (produto)
            WHERE tbl_lista_basica.fabrica = $login_fabrica
            AND   tbl_lista_basica.produto = $produto_os
            ORDER by tbl_peca.descricao; ";
    }
    $res = pg_query($con,$sql);
    $lbm_itens = pg_num_rows($res);
    if($lbm_itens>0){

        for($x=0; $lbm_itens>$x; $x++){
            $ypeca_referencia        = pg_fetch_result($res,$x,referencia);
            $ypeca_descricao        = pg_fetch_result($res,$x,descricao);
            $yqtde                    = pg_fetch_result($res,$x,qtde);
            $ypeca                    = pg_fetch_result($res,$x,peca);
            $l_serie_inicial        = pg_fetch_result($res,$x,serie_inicial);
            $l_serie_final            = pg_fetch_result($res,$x,serie_final);
            $p_serie_inicial        = pg_fetch_result($res,$x,produto_serie_inicial);
            $p_serie_final            = pg_fetch_result($res,$x,produto_serie_final);
			
			$sql_depara = "SELECT para FROM tbl_depara WHERE de='$ypeca_referencia' AND fabrica=15";
			// echo $ypeca."<br>";
			
			// echo nl2br($sql_depara)."<br>";

			$res_depara = pg_query($con,$sql_depara);
			$para_itens = pg_num_rows($res_depara);
			if ($para_itens > 0 ){
				$para = pg_result($res_depara,0, para);
			}
//  14/01/2010 HD 189523 - Para a Latinatec -Não mostra as peças que não correspondem com a série/versão do produto
            if ($login_fabrica == 15) {
//             echo "$produto_serie,$p_serie_inicial,$p_serie_final,$l_serie_inicial,$l_serie_final";
				unset($cor_fundo); #HD 335128 Cor de fundo caso a peça nao esteja na versao
                if (!valida_serie_latinatec($produto_serie,$p_serie_inicial,$p_serie_final,$l_serie_inicial,$l_serie_final))  $cor_fundo = '#FCC';#HD 335128 Cor de fundo caso a peça nao esteja na versao
            }
            $pre_peca_x_checked    = ($_POST["pre_peca_$x"]==$ypeca)?" CHECKED":"";
            $valor_pre_defeito_x= (isset($_POST["pre_defeito_$x"]))?$_POST["pre_defeito_$x"]:"";
            $valor_pre_servico_x= (isset($_POST["pre_servico_$x"]))?$_POST["pre_servico_$x"]:"";
			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
			
			$cor = ($cor_fundo) ? $cor_fundo : $cor; #HD 335128 Cor de fundo caso a peça nao esteja na versao
            if ($cor == '#FCC'){
				echo " <tr style='background-color:$cor; cursor:help;'>" ;
			}else{
				echo "<tr style='background-color:$cor'>";
            }
            if($login_fabrica <> 15){
                echo "<td align='center' width='30%'><input class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'>&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> ";
				echo "</td>";
            }else{
				
				if ($cor == '#FCC'){
					echo "<td onmouseover=\"info(this,'Esta peça não pertence a versão do produto com o número de série $produto_serie')\"  onmouseout='fechar()'>
						<font face='arial' size='-2' color='#000000'>$ypeca_descricao</font>";
				}else{
                echo "<td align='left'><font face='arial' size='-2' color='#000000'>$ypeca_descricao</font>";
				}
				if (strlen($para)>0) #HD 335128 CONDICAO DE - PARA
				{
					echo "&nbsp;<font style='font: bold 12px Arial; color:#FF0000'>Mudou para $para</font>";
					
				}#HD 335128
				echo "</td>";
            }
			
            echo "<td width='60' align='center'></TD>";
            if($login_fabrica <> 15){
                echo "<td align='center'><font face='arial' size='-2' color='#000000'>$ypeca_descricao</font></td>\n";
            }else{
				if (strlen($para)>0) #HD 335128 CONDICAO DE - PARA
				{
					if ($cor == '#FCC'){
						echo "<td align='center' onmouseover=\"info(this,'Esta peça não pertence a versão do produto com o número de série $produto_serie')\"  onmouseout='fechar()' ><input DISABLED class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'$pre_peca_x_checked>";
						echo "&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> </td>";
						
					}else{
					
					echo "<td align='center' align='center'><input DISABLED class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'$pre_peca_x_checked>";
					echo "&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> </td>";
					}
				}else{#HD 335128
					if ($cor == '#FCC'){
						echo "<td align='center' onmouseover=\"info(this,'Esta peça não pertence a versão do produto com o número de série $produto_serie')\"  onmouseout='fechar()'><input class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'$pre_peca_x_checked>";
						echo "&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> </td>";
						
					}else{
					
					echo "<td align='center'><input class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'$pre_peca_x_checked>";
					echo "&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> </td>";
					}
				}
            }
			

            if($login_fabrica <> 15){
                echo "<td align='center'><font face='arial' size='-2' color='#000000'>$yqtde</font><input type='hidden' name='pre_qtde_$x' value='$yqtde'></td>\n";
            }else{
                echo "<input type='hidden' name='pre_qtde_$x' value='$yqtde'>\n";
            }
			
			if ($cor == '#FCC'){
						echo "<td align='center' onmouseover=\"info(this,'Esta peça não pertence a versão do produto com o número de série $produto_serie')\"  onmouseout='fechar()'>";
			}else{
            echo "<td align='center'>";
			}
			if (strlen($para)>0) #HD 335128 CONDICAO DE - PARA
			{
				echo "<select DISABLED name='pre_defeito_$x' class='frm' style='width:170px;'>";
				echo "</select>";
			}else{ #HD 335128
            echo "<select name='pre_defeito_$x' class='frm' style='width:170px;'>";
            echo "<option></option>";
            if($login_fabrica <> 15){
                $sql = "SELECT     tbl_defeito.defeito,
                                tbl_defeito.descricao
                        FROM tbl_peca_defeito
                        JOIN tbl_defeito using(defeito)
                        WHERE peca = $ypeca
                        AND tbl_peca_defeito.ativo = 't'
                        ORDER BY tbl_defeito.descricao";
            }else{
                $sql = "SELECT *
                        FROM   tbl_defeito
                        WHERE  tbl_defeito.fabrica = $login_fabrica
                        AND    tbl_defeito.ativo IS TRUE
                        ORDER BY descricao ";
            }
            $zres = pg_query($con,$sql);
            if(pg_num_rows($zres)>0){
                for($z=0;pg_num_rows($zres)>$z;$z++){
                    $zdefeito   = pg_fetch_result($zres,$z,defeito);
                    $zdescricao = pg_fetch_result($zres,$z,descricao);
                    $opt_sel = ($valor_pre_defeito_x == $zdefeito)?" SELECTED":"";
                    echo "<option value='$zdefeito'$opt_sel>$zdescricao</option>";
                }
            }
            echo "</select>";
            echo "</td>";
			}
			if (strlen($para)>0 and $login_fabrica == 15){ #HD 335128 CONDICAO DE - PARA
				if ($cor == '#FCC'){
						echo "<td align='center' onmouseover=\"info(this,'Esta peça não pertence a versão do produto com o número de série $produto_serie')\"  onmouseout='fechar()'>";
						echo "<select DISABLED class='frm' size='1' name='pre_servico_$x'  style='width:200px;'>";
						echo "</select>";
						echo "</td>";
				}else{
				echo "<td align='center'>";
				echo "<select DISABLED class='frm' size='1' name='pre_servico_$x'  style='width:200px;'>";
				echo "</select>";
				echo "</td>";
				}
				
			}else{
				if ($cor == '#FCC'){
						echo "<td align='center' onmouseover=\"info(this,'Esta peça não pertence a versão do produto com o número de série $produto_serie')\"  onmouseout='fechar()'>";
				}else{
				echo "<td align='center'>";
				}
				if($login_fabrica <> 15){
					echo "<select class='frm' size='1' name='pre_servico_$x'  style='width:150px;'>";
				}else{
					echo "<select class='frm' size='1' name='pre_servico_$x'  style='width:200px;'>";
				}
				echo "<option></option>";
				if($login_fabrica <> 15){
					$sql = "SELECT tbl_servico_realizado.servico_realizado,
									tbl_servico_realizado.descricao
							FROM tbl_peca_servico
							JOIN tbl_servico_realizado using(servico_realizado)
							WHERE tbl_peca_servico.ativo = 't'
							AND tbl_peca_servico.peca = $ypeca";

					if (strlen($os_revenda)==0){
						$sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%' ";
						$sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%' ";
					}

					$sql .= "ORDER BY tbl_servico_realizado.descricao";

				}else{
					$sql = "SELECT *
							FROM tbl_servico_realizado
							WHERE tbl_servico_realizado.fabrica = $login_fabrica
							AND tbl_servico_realizado.ativo IS TRUE
							ORDER BY descricao";
				}
				$zres = pg_query($con,$sql);
				if(pg_num_rows($zres)>0){
					for($z=0;pg_num_rows($zres)>$z;$z++){
						$zservico_realizado   = pg_fetch_result($zres,$z,servico_realizado);
						$zdescricao = pg_fetch_result($zres,$z,descricao);
						$ztrocapeca = pg_fetch_result($zres,$z,troca_de_peca);
						if($ztrocapeca == 't' AND $login_fabrica == 15){
							echo "<option value='$zservico_realizado' selected> $zdescricao</option>";
						}else{
							$opt_sel = ($valor_pre_servico_x == $zservico_realizado)?" SELECTED":"";
							echo "<option value='$zservico_realizado'$opt_sel>$zdescricao</option>";
						}
					}
				}
				echo "</select>";
				echo "</td>";
				echo "</tr>";
			}
			$para = '';
        }
        echo "<input type='hidden' name='pre_total' value='$x'>\n";
    }
/*HD 2599*/
}
        $loop = $qtde_item;
        if ($login_fabrica==3){
            $sql = "SELECT  count(*) as contador
                    FROM    tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    JOIN tbl_os_item      ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    WHERE   tbl_os.os  = $os
                    AND     tbl_os.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $num = pg_fetch_result($res,0,contador) - $numero_pecas_faturadas;
            $loop = $qtde_itens_mostrar - $numero_pecas_faturadas;
            if ($loop<$num)
                $loop = $num;
        }
        if($login_fabrica== 45){
            $sql="SELECT qtde_os_item
                    FROM tbl_posto_fabrica
                    WHERE posto   = $login_posto
                    AND   fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $loop = pg_fetch_result($res,0,qtde_os_item);
        }
        if($login_fabrica == 15 ) {
            $loop = $qtde_itens_mostrar;
        }

        if($login_fabrica == 30) { //HD 54672
            if (strlen($qtde_itens_mostrar)==0) $qtde_itens_mostrar = 5;

            $sql = "SELECT  count(*) as contador
                    FROM    tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    JOIN tbl_os_item      ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    WHERE   tbl_os.os  = $os
                    AND     tbl_os.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $num = pg_fetch_result($res,0,contador);
//    Qtde de linhas para mostrar sempre múltiplo de 10... desde que não seja menor q 5
            $num = ($num>5) ? round($num/10)*10 : 5;
            $loop = ($qtde_itens_mostrar <= $num)?$num:$qtde_itens_mostrar;
        }

        if($login_fabrica == 6 AND $posto_item_aparencia == 't') {
            $loop = $loop+7;
        }

		if( in_array( $login_fabrica, array( 90 , 91 ) ) ) {
			$loop = 10;
		}

		if($login_fabrica == 42)
			$loop= 15;

		//USADO PARA FAZER O LAÇO QUANDO EXISTIR UM ITEM DE APARENCIA E INCREMENTAR EM QTDE_ITENS
		$qtde_item_aparencia="0";

		$offset = 0;

        for ($i = 0 ; $i < $loop ; $i++) {
            $cor="";
            if ($login_fabrica == 3 || $login_fabrica == 43) {//HD 255541
                $cor=" bgcolor='#FF6666'";
                if ($i==0) {
                    $cor=" bgcolor='#99FF99'";
                    if ($numero_pecas_faturadas==1) $cor=" bgcolor='#FFFF99'";
                }
                if ($i==1) {
                     $cor=" bgcolor='#FFFF99'";
                    if ($numero_pecas_faturadas==1) $cor=" bgcolor='#FF6666'";
                }
                if ($numero_pecas_faturadas>=2) $cor=" bgcolor='#FF6666'";
            }


            echo "<tr $cor id='mostrar_$i' style='text-align:justify;'>";
                echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>\n";
                echo "<input type='hidden' name='os_item_$i'    value='$os_item[$i]'>\n";
                echo "<input type='hidden' name='orcamento_item_$i' value='$orcamento_item[$i]'>\n";
                echo "<input type='hidden' name='descricao'>";
                echo "<input type='hidden' name='preco'>";
                echo "<input type='hidden' name='preco_$i' value='$preco[$i]' id='preco_$i'>";
                echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui

            if ($os_item_subconjunto == 'f') {
                echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
            }else{
                echo "<td align='left' nowrap>";
                echo "<select class='frm' size='1' name='produto_$i'>";
                #echo "<option></option>";

                $sql = "SELECT  tbl_produto.produto   ,
                                tbl_produto.referencia,
                                tbl_produto.descricao
                        FROM    tbl_subproduto
                        JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
                        WHERE   tbl_subproduto.produto_pai = $produto_os
                        ORDER BY tbl_produto.referencia;";
                $resX = pg_query($con,$sql) ;

                echo "<option value='$produto_referencia' ";
                if ($produto[$i] == $produto_referencia) echo " selected ";
                echo " >$produto_descricao</option>";

                for ($x = 0 ; $x < pg_num_rows($resX) ; $x++ ) {
                    $sub_produto    = trim (pg_fetch_result($resX,$x,produto));
                    $sub_referencia = trim (pg_fetch_result($resX,$x,referencia));
                    $sub_descricao  = trim (pg_fetch_result($resX,$x,descricao));

                    if ($login_fabrica == 14 AND substr ($sub_referencia,0,3) == "499" ){
                        $sql = "SELECT  tbl_produto.produto   ,
                                        tbl_produto.referencia,
                                        tbl_produto.descricao
                                FROM    tbl_subproduto
                                JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
                                WHERE   tbl_subproduto.produto_pai = $sub_produto
                                ORDER BY tbl_produto.referencia;";
                        $resY = pg_query($con,$sql) ;
                        echo "<optgroup label='" . $sub_referencia . " - " . substr($sub_descricao,0,25) . "'>" ;
                        for ($y = 0 ; $y < pg_num_rows($resY) ; $y++ ) {
                            $sub_produto    = trim (pg_fetch_result($resY,$y,produto));
                            $sub_referencia = trim (pg_fetch_result($resY,$y,referencia));
                            $sub_descricao  = trim (pg_fetch_result($resY,$y,descricao));

                            echo "<option ";
                            if (trim ($produto[$i]) == $sub_referencia) echo " selected ";
                            echo " value='" . $sub_referencia . "'>" ;
                            echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
                            echo "</option>";
                        }
                        echo "</optgroup>";
                    }else{
                        echo "<option ";
                        if (trim ($produto[$i]) == $sub_referencia) echo " selected ";
                        echo " value='" . $sub_referencia . "'>" ;
                        echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
                        echo "</option>";
                    }
                }

                echo "</select>";
                if ($login_fabrica == 14) {
                    echo " <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, document.frm_os.posicao_$i, document.frm_os.peca_$i, document.frm_os.descricao_$i)' alt='Clique para abrir a lista básica do produto selecionado' style='cursor:pointer;'>";
                }
                echo "</td>\n";
            }

            if ($os_item_subconjunto == 'f') {
                $xproduto = $produto[$i];
                echo "<input type='hidden' name='serie_$i'>\n";
            }else{
                if ($os_item_serie == 't') {
                    echo "<td align='left'><input class='frm' type='text' name='serie_$' size='9' value='$serie[$i]'></td>\n";
                }
            }

            if($login_fabrica == 56){
                    echo "<td align='left'><input class='frm' type='text' name='serie_$i' size='9' value='$serie[$i]'></td>\n";
            }

            /* Rotina para verificação de comunicados por Peça - HD 19052 */
            if (strlen($peca[$i])>0){
                $sql ="SELECT count(*)
                        FROM  tbl_comunicado
                        LEFT JOIN tbl_comunicado_peca USING(comunicado)
                        LEFT JOIN tbl_peca PC_1  ON PC_1.peca = tbl_comunicado_peca.peca
                        LEFT JOIN tbl_peca PC_2  ON PC_2.peca = tbl_comunicado.peca
                        WHERE tbl_comunicado.fabrica = $login_fabrica
                        AND   tbl_comunicado.ativo  IS TRUE
                        AND ( tbl_comunicado.posto = $login_posto OR tbl_comunicado.posto IS NULL)
                        AND (PC_1.referencia = '$peca[$i]' OR PC_2.referencia = '$peca[$i]')";
                $resComunicado = pg_query($con,$sql) ;
                $tem_comunicado = trim(pg_fetch_result($resComunicado,0,0));
            }else{
                $tem_comunicado = 0;
            }


            if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {    // HD 7033 16/11/2007
                $sql = "SELECT  tbl_peca.peca      ,
                                tbl_peca.referencia,
                                tbl_peca.descricao ,
                                tbl_lista_basica.qtde
                        FROM    tbl_peca
                        JOIN    tbl_lista_basica USING (peca)
                        JOIN    tbl_produto      USING (produto)
                        WHERE   tbl_produto.produto     = $produto_os
                        AND     tbl_peca.fabrica        = $login_fabrica";
                        if ($login_fabrica==6 and strlen($produto_serie)>0) {
                        $sql .= " AND     tbl_lista_basica.serie_inicial < '$produto_serie'
                                  AND     tbl_lista_basica.serie_final > '$produto_serie'";
                        }
                        $sql .= " AND     tbl_peca.item_aparencia = 't'
                        ORDER BY tbl_peca.referencia
                        LIMIT 1 OFFSET $offset;";
                $resX = pg_query($con,$sql) ;
                if (pg_num_rows($resX) > 0) {

                    $qtde_item_aparencia++;
                    $xpeca       = trim(pg_fetch_result($resX,0,peca));
                    $xreferencia = trim(pg_fetch_result($resX,0,referencia));
                    $xdescricao  = trim(pg_fetch_result($resX,0,descricao));
                    $xqtde       = trim(pg_fetch_result($resX,0,qtde));

                    if ($peca[$i] == $xreferencia)
                        $check = " checked ";
                    else
                        $check = "";

                    if ($login_posto == 427) $check = " checked ";

                    echo "<td align='left'><input class='frm' type='checkbox' name='peca_$i' value='$xreferencia' $check>&nbsp;<font face='arial' size='-2' color='#000000'>$xreferencia</font></td>\n";

                    echo "<td width='60' align='left'>";
                    //echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'>";
                    echo "</TD>";
                    echo "<td align='left'><font face='arial' size='-2' color='#000000'>$xdescricao</font></td>\n";
                    echo "<td align='left'><font face='arial' size='-2' color='#000000'>$xqtde</font><input type='hidden' name='qtde_$i' value='$xqtde'></td>\n";

                    if ($login_fabrica == 6) {
                        if (strlen($defeito[$i]) == 0) $defeito[$i] = 78 ;
                        if (strlen($servico[$i]) == 0) $servico[$i] = 1 ;
                    }
                }else{

                        echo "<td align='left' nowrap>&nbsp;&nbsp;<input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' alt='LISTA BÁSICA'><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
    //takashi chamado 300 12-07
                        echo "<td width='60' align='left'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\",document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
    //takashi chamado 300 12-07
                        echo "<td align='left' nowrap>&nbsp;&nbsp;<input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
                        if ($pergunta_qtde_os_item == 't') {
                            echo "<td align='left'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>\n";
                        }
                    }
            }else{
                if ($login_fabrica == 14) {
                    echo "<td align='left'><input class='frm' type='text' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'></td>\n";
                }if ($login_fabrica == 24) { //hd 12634 29/1/2008
                    echo "<input class='frm' type='hidden' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'>";
                }else{
                    echo "<input type='hidden' name='posicao_$i'>\n";
                }
//takashi 04-04-07 hd 1819

                // LATINA codigo e descrição são invertidos - HD 4981
                // alterar aki tem que altera mais a baixo tbm!!! é o mesmo código
				$cor_coluna = ($i % 2) ? "#F1F4FA" : "#F7F5F0";
                if ($login_fabrica == 15 || $login_fabrica == 24) {//HD 258901
                    echo "<td align='left' bgcolor='$cor_coluna'><input class='frm' type='text' name='descricao_$i' size='45' value='$descricao[$i]'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'";
                     echo " onclick='javascript: fnc_pesquisa_peca_lista_latina (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\",document.frm_os.produto_serie ,document.frm_os.kit_peca_$i)'";
                    echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
                } elseif($login_fabrica == 95){
					echo "<td width='200' align='left' bgcolor='$cor_coluna' nowrap> <input class='frm' type='text' name='peca_$i' $block_item id='peca_$i'size='15' value='$peca[$i]'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'
					onclick='javascript: fnc_pesquisa_lista_serie(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\", document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				}else {
                    echo "<td align='left' nowrap>
                    <input class='frm' type='text' name='peca_$i' $block_item id='peca_$i'size='15' value='$peca[$i]'"; echo " "; if($login_fabrica==30 or $login_fabrica ==43) echo " onblur=\"javascript: pega_peca('$os','peca_$i','descricao_$i'); atualizaQtde(document.frm_os.peca_$i,document.frm_os.qtde_$i);\" ";
                    if ($login_fabrica==50) echo " onBlur='checarComunicado($i);' onkeyup=\"limpaDefeito($i);\"  ";
                    echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'  ";
                    //hd 12634 29/1/2008
                    if ($login_fabrica == 14 ) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
                    else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"referencia\", document.frm_os.qtde_$i)'";
                    echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
                    //takashi 11-07 chamado 300
                    /* echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle'                         onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'>";*/

                    /* Rotina para verificação de comunicados por Peça - HD 19052 */
                    if($tem_comunicado==0){
                        $style_img_comunicado = "visibility:hidden;";
                    }else{
                        $style_img_comunicado = "";
                    }
                    echo "<img id='imagem_comunicado_$i' src='imagens/warning.png' style='$style_img_comunicado cursor: pointer;' aling='absmiddle' onclick='javascript:abreComunicadoPeca($i)'>";
                    echo "</td>\n";
                }

                if ($login_fabrica == 6) {
                    echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica2(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                } else {
                    //hd 12634 29/1/2008
                    if ($login_fabrica == 24) {//HD 258901
                        echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica_suggar(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"referencia\",document.frm_os.kit_peca_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                    } else if($login_fabrica == 15) {
                        echo "<td width='60' align='center' bgcolor='$cor_coluna'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica_latina (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\", document.frm_os.produto_serie, document.frm_os.kit_peca_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                        //takashi 11-07 chamado 300
                    } elseif($login_fabrica == 95) { //HD 383844
						echo "<td width='60' align='center' bgcolor='$cor_coluna'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_serie(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\", document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
					}elseif (strlen($block_item) > 0) {
                        echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                    } else {
                        echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"referencia\",document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                        //takashi 11-07 chamado 300
                    }
                }

                // LATINA codigo e descrição são invertidos - HD 4981
                // alterar aki tem que altera mais a acima tbm!!! é o mesmo código
                if ($login_fabrica == 15 || $login_fabrica == 24) {//HD 258901
                    echo "<td align='center' nowrap bgcolor='$cor_coluna'>
                    <input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'"; echo " >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'";
                    echo " onclick='javascript: fnc_pesquisa_peca_lista_latina (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\",document.frm_os.produto_serie ,document.frm_os.kit_peca_$i)'";
                    echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
                    echo "</td>\n";
                }elseif($login_fabrica == 95){
					echo "<td  align='center' bgcolor='$cor_coluna' nowrap> <input class='frm' type='text' name='descricao$i' $block_item id='descricao_$i'size='25' value='$peca[$i]'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'
					onclick='javascript: fnc_pesquisa_lista_serie(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\", document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				}else{
                    echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' id='descricao_$i' $block_item size='25' value='$descricao[$i]' ";
                    if ($login_fabrica==50) echo " onBlur='javascript:checarComunicado($i);'  ";
                    echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'";
                    //hd 12634 29/1/2008
                    if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
                    else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco_$i , document.frm_os.voltagem, \"descricao\", document.frm_os.qtde_$i )'";
                    echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
                }
            //HD 38818 4/9/2008 ----------------------------
            if($informatica == 't'){
                echo "</td>";
                    echo "<td align='center' nowrap bgcolor='$cor_coluna'>";
                    echo "<input class='frm' type='text' name='peca_serie_$i' id='peca_serie_$i' size='13' $block_item maxlength='20' value='$peca_serie[$i]' ";
                    echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle'";
                    echo " onclick='javascript: fnc_pesquisa_peca_serie (document.frm_os.peca_serie_$i.value, document.frm_os.peca_$i,document.frm_os.descricao_$i  )'";
                    echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";


                    /*
                    echo "<INPUT TYPE='text' NAME='peca_serie_$i' value='$peca_serie[$i]' size='13' maxlength='20' >";
                    echo "<img id='imagem_serie_$i' src='imagens/lupa.png' style='$style_img_comunicado cursor: pointer;' aling='absmiddle' onclick='javascript:abreSeriePeca($i)'>";
                echo "</td>";*/
            }
            //----------------------------------------------


                if ($pergunta_qtde_os_item == 't') {
                    echo "<td align='center' bgcolor='$cor_coluna'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]' $block_item></td>\n";
                }
            }

            #------------------- Causa do Defeito no Item --------------------
            if ($pedir_causa_defeito_os_item == 't' and $login_fabrica<>20) {
                echo "<td align='center'>";
                echo "<select class='frm' size='1' name='pcausa_defeito_$i'>";
                echo "<option selected></option>";

                # HD 44571
                if ($login_fabrica == 5){
                    $cond_mondialcausa = "AND causa_defeito = 1";
                }

                $sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica $cond_mondialcausa ORDER BY codigo, descricao";
                $res = pg_query($con,$sql) ;

                for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
                    echo "<option ";
                    if ($pcausa_defeito[$i] == pg_fetch_result($res,$x,causa_defeito)) echo " selected ";
                    echo " value='" . pg_fetch_result($res,$x,causa_defeito) . "'>" ;
                    echo pg_fetch_result($res,$x,codigo) ;
                    echo " - ";
                    echo pg_fetch_result($res,$x,descricao) ;
                    echo "</option>";
                }

                echo "</select>";
                echo "</td>\n";
            }

            #------------------- Defeito no Item --------------------
            echo "<td align='center' bgcolor='$cor_coluna'>";
            /*INTEGRIDADE DE PEÇAS COMECA AQUI - TAKASHI HD 1950*/
            if ($login_fabrica == 24 or $login_fabrica == 6 or $login_fabrica == 50) {
                # HD 51163 - Colormaq
                echo "<select name='defeito_$i' id='defeito_$i' class='frm' style='width:";
                if ($login_fabrica == 6) {
                    echo "170px;";
                } else {
                    echo "150px;";
                }
                echo "' onfocus='defeitoLista(document.frm_os.peca_$i.value,$i,$os);'";
                if ($login_fabrica == 50){
                    echo " onClick='javascript:checarComunicado($i);'  ";
                }
                echo " >";
                echo "<option id='op_$i' value=''></option>";

                if (strlen($defeito[$i]) > 0) {

                    $sql = "SELECT tbl_defeito.defeito, tbl_defeito.descricao,
                                tbl_defeito.codigo_defeito
                            FROM   tbl_defeito
                            WHERE  tbl_defeito.fabrica = $login_fabrica
                            AND    tbl_defeito.defeito = $defeito[$i]
                            AND    tbl_defeito.ativo IS TRUE
                            ORDER BY descricao";
                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res) > 0) {
                        echo "<option value='" . pg_fetch_result($res,0,defeito) . "' SELECTED>";
                        if (($login_fabrica == 50) and (strlen(trim (pg_fetch_result($res,0,codigo_defeito))) > 0)) {
                            echo pg_fetch_result($res,0,codigo_defeito) ;
                            echo " - " ;
                        }
                        echo pg_fetch_result($res,0,descricao)."</option>";
                    }

                }
                echo "</select>";
            } else {

                echo "<select class='frm' size='1' name='defeito_$i' id='defeito_$i' $block_item>";
                echo "<option selected></option>";

                $sql = "SELECT *
                        FROM   tbl_defeito
                        WHERE  tbl_defeito.fabrica = $login_fabrica
                        AND    tbl_defeito.ativo IS TRUE
                        ORDER BY descricao";
                $res = pg_query($con,$sql) ;


                for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
                    echo "<option ";
                    if ($defeito[$i] == pg_fetch_result($res,$x,defeito)) echo " selected ";
                    echo " value='" . pg_fetch_result($res,$x,defeito) . "'>" ;

                    if (strlen(trim (pg_fetch_result($res,$x,codigo_defeito))) > 0) {
                        echo pg_fetch_result($res,$x,codigo_defeito) ;
                        echo " - " ;
                    }
                    echo pg_fetch_result($res,$x,descricao) ;
                    echo "</option>";
                }

                echo "</select>";
            }
            /*INTEGRIDADE DE PEÇAS TERMINA AQUI - TAKASHI HD 1950*/
            echo "</td>\n";

            echo "<td align='center' bgcolor='$cor_coluna'>";
            /*INTEGRIDADE DE PEÇAS x SOLUÇÃO COMECA AQUI - TAKASHI HD 2504*/
            if ($login_fabrica == 6) {
                echo "<select class='frm' size='1' name='servico_$i'  style='width:150px;' onfocus='servicoLista(document.frm_os.peca_$i.value,$i);'  rel='servicos_realizados' alt='$i'>";
                echo "<option id_servico='op_$i' value=''></option>";
                echo "<option selected></option>";
                $sql = "SELECT     tbl_servico_realizado.descricao,
                                tbl_servico_realizado.servico_realizado
                        FROM tbl_servico_realizado
                        WHERE tbl_servico_realizado.fabrica = $login_fabrica
                        AND tbl_servico_realizado.ativo IS TRUE
                        AND tbl_servico_realizado.servico_realizado = $servico[$i]
                        ORDER BY descricao";
                $res = pg_query($con,$sql) ;
                if(pg_num_rows($res)>0){
                    echo "<option value='" . pg_fetch_result($res,0,servico_realizado) . "' SELECTED>".pg_fetch_result($res,0,descricao)."</option>";

                }

                echo "</select>";

            } else {

                echo "<select class='frm' size='1' name='servico_$i' rel='servicos_realizados' $block_item alt='$i'";
                if ($login_fabrica == 3) echo "style='width: 290px";
                /*if ($login_fabrica ==51) { 83858 Retirar
                    echo " onChange='verificaServico(this,document.frm_os.peca_$i)'";
                }*/
                if ($login_fabrica == 45 or $login_fabrica ==24) {
                    echo " onchange='mostraOrcamento(this.value,$i);' ";
                }
                echo ">";
                echo "<option selected></option>";

                $sql = "SELECT *
                        FROM   tbl_servico_realizado
                        WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

                if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15 and $login_fabrica <> 52 and $login_fabrica <> 85 and $login_fabrica <> 42 and $login_fabrica <> 91 and $login_fabrica <> 72) {
                    $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                }

                if ($login_fabrica == 1) {

                    if ($login_reembolso_peca_estoque == 't') {
                        $sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                        $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
                        if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
                    } else {
                        $sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
                        $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
                        if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
                    }

                }

                if ($login_fabrica == 20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";

                if (strlen($os_revenda) == 0) {
                    $sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%' ";
                    $sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%' ";
                }

                if ($login_fabrica == 40) {
                    $sqlf = " SELECT familia 
                            FROM tbl_os
                            JOIN tbl_produto USING(produto)
                            WHERE os = $os";

                    $resf    = pg_query($con,$sqlf);
                    $familia = pg_fetch_result($res,0,familia);

                    if (!in_array($familia,array(810,2462,2463))) {
                        $sql.= " AND tbl_servico_realizado.descricao not ilike '%recarga de g%' ";
                    }
                    
                }

                // Samuel 26/12/2008 - nao deixar o servico realizado 733 usado para troca de produto.
                // Quando for trocar o produto, trocar o servico realizado 673 para 733, assim não vai
                // embarcar novamente peças canceladas.
                $sql .= " AND tbl_servico_realizado.ativo IS TRUE
                            AND tbl_servico_realizado.servico_realizado <> 733";
				
				if($login_fabrica == 91){ //HD 367935
						$sqlServico = "SELECT tbl_posto_fabrica.tipo_posto ,
									   tbl_tipo_posto.posto_interno
									FROM tbl_posto_fabrica
									JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
									WHERE tbl_posto_fabrica.posto = $login_posto
									AND tbl_tipo_posto.posto_interno = 't'";
						$resServico = pg_exec($con,$sqlServico);
						if(pg_numrows($resServico) == 0){
							$sql .=" AND tbl_servico_realizado.servico_realizado <> 10398 ";
						}
				}

                if ($login_posto <> 6359) {
                    $sql .= " AND tbl_servico_realizado.servico_realizado not in (4247,4289)";
                }

                $sql .= " ORDER BY descricao ";

                $res = pg_query($con,$sql) ;

                if (pg_num_rows($res) == 0) {

                    $sql = "SELECT *
                            FROM   tbl_servico_realizado
                            WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

                    if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15) {
                        $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                    }

                    if ($login_fabrica == 1) {

                        if ($login_reembolso_peca_estoque == 't') {
                            $sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                            $sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
                        } else {
                            $sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
                            $sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
                        }

                    }

                    if ($login_fabrica == 20) $sql .= " tbl_servico_realizado.solucao IS TRUE ";

                    if (strlen($os_revenda) == 0) {
                        $sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%' ";
                        $sql .=" AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%' ";
                    }

                    $sql .=    " AND tbl_servico_realizado.linha IS NULL";
                    $sql .= " ORDER BY descricao ";

                    $res = pg_query($con,$sql);

                }

                for ($x = 0; $x < pg_num_rows($res); $x++) {
                    echo "<option ";
                    if ($servico[$i] == pg_fetch_result($res,$x,servico_realizado) /*OR ($login_fabrica==5 AND pg_fetch_result($res,$x,gera_pedido)=='t')*/) echo " selected ";
                    echo " value='" . pg_fetch_result($res,$x,servico_realizado) . "'>" ;
                    echo pg_fetch_result($res,$x,descricao) ;
                    if (pg_fetch_result($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
                    echo "</option>";
                }

                if ($login_fabrica == 3 and $login_posto == 6359) {
                    echo "<option value='43' ";
                    if ($servico[$i] == 43) echo " selected ";
                    echo " >Troca de peça do estoque interno</option>";
                }

				# HD 289283
				if($login_fabrica == 91 and $login_tipo_posto == 270) {
					echo "<option value='10129' ";
                    if ($servico[$i] == 10129) echo " selected ";
                    echo " >Troca de peça NÃO gerando pedido</option>";
				}

                echo "</select>";
                echo "</td>\n";

            }

            if ($login_fabrica == 3 or $login_fabrica == 45 or $login_fabrica == 24) {

                echo "<td align='center' nowrap>";

                if (($login_fabrica == 3 or $login_fabrica == 45 or $login_fabrica == 24) AND ($servico[$i] == 643 OR $servico[$i] == 644 or $servico[$i] == 4247 or $servico[$i] == 4289)) {
                    echo " <div id='orcamento_mostra_$i'>";
                } else {
                    echo " <div id='orcamento_mostra_$i' style='font-size:10px; display: none'>";
                }
                echo "R$<input type='text' readonly size='5' name='preco_orcamento_$i' id='preco_orcamento_$i' value='".$preco[$i]."' onBlur='checarNumero(this)' title='Preço de Tabela da Fabrica'> - R$<input type='text' size='5' name='preco_venda_orcamento_$i' id='preco_venda_orcamento_$i' value='".$preco_venda[$i]."' onBlur='checarNumero(this)' title='Preço de venda ao Consumidor'>";
                //echo "<img  id='imagem_ajuda_$i' onMouseOut=\"HideHelp('ajuda_$i')\" onMouseOver=\"ShowHelp('ajuda_$i', 'Preço', 'Preencha este campo se esta peça será cobrada do consumidor.')\" src='imagens/help1.gif' width='24' height='16' border='0' style='display:none'><div style='display:none' id='ajuda_$i'></div>";
                echo "<a href=\"javascript:alert('Preencha este campo com o valor da peça que será utilizada para venda pelo cliente/revenda')\"><img  id='imagem_ajuda_$i' src='imagens/help1.gif' width='24' height='16' align='absmiddle' border='0' ></a>";

                echo "</div>";

                echo "</TD>";

            }

            //HD 107982 2009-07-20 ---------------------------
            if ($login_fabrica == 24) {
                echo "<td nowrap align='center'>";
                    echo "<input type='radio' name='reparo_estoque_$i' value='peca_reposicao_estoque'";
                    if($peca_reposicao_estoque[$i]=="t") echo "checked";
                    echo " title='Repor Estoque'>";
                    echo "<font size='-1'>Repor Estoque</font>";
                    /*------------*/
                    echo "&nbsp;";
                    echo "<input type='radio' name='reparo_estoque_$i' value='aguardando_peca_reparo'";
                    if($aguardando_peca_reparo[$i]=="t") echo "checked";
                    echo " title='Aguardando peça para reparo'>";
                    echo "<font size='-1'>Aguardando peça para reparo</font>";
                echo "</td>";
            }
            //----------------------------------------------

            //HD 38818 4/9/2008 ----------------------------
            if ($informatica == 't') {
                echo "</td>";
                echo "<td align='center'>";
                    echo "<INPUT TYPE='text' NAME='peca_serie_trocada_$i' value='$peca_serie_trocada[$i]' size='13' maxlength='20'>";
                echo "</td>";
            }
            //----------------------------------------------
            echo "</tr>\n";

            if ($login_fabrica == 15 || $login_fabrica == 24) {
                echo "<tr>
                        <td colspan='100%'>
                            <div id='kit_peca_$i'><input type='hidden' name='kit_peca_$i' value='kit_peca_$i'>";
                if(!empty($xkit_peca[$i])) {
                    $sql = " SELECT tbl_peca.peca      ,
                                    tbl_peca.referencia,
                                    tbl_peca.descricao,
                                    tbl_kit_peca_peca.qtde
                            FROM    tbl_kit_peca_peca
                            JOIN    tbl_peca USING(peca)
                            WHERE   fabrica = $login_fabrica
                            AND     kit_peca = $xkit_peca[$i]
                            ORDER BY tbl_peca.peca";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                            echo "<table>";
                                echo "<tr>
                                        <td colspan='100%'>
                                            <input type='hidden' name='kit_kit_peca_$i' value='$xkit_peca[$i]'>
                                        </td>
                                    </tr>";
                        for ($k = 0; $k < pg_num_rows($res); $k++) {
                            $kit_peca_peca = pg_fetch_result($res,$k,'peca');
                            $kit_peca_qtde = pg_fetch_result($res,$k,'qtde');

                            if ($_POST["kit_peca_$kit_peca_peca"]) {
                                $checked = "checked";
                            } else {
                                $checked = "";
                            }

                            echo "<tr style='font-size: 11px'>";
                                echo "<td>";
                                    echo "<input type='checkbox' name='kit_peca_$kit_peca_peca' $checked> ";
                                    echo "<input type='text' name='kit_peca_qtde_$kit_peca_peca' id='kit_peca_qtde_$kit_peca_peca' value='" . $_POST["kit_peca_qtde_$kit_peca_peca"] . "' size='5' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\"> x ";
                                    echo pg_fetch_result($res,$k,'referencia');
                                echo "</td>";
                                echo "<td> - ";
                                echo pg_fetch_result($res,$k,'descricao');
                                echo "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
                    echo "</div>
                    </td>
                </tr>";
            }
            $offset = $offset + 1;
        }
        echo "</table>";?>
    </td>
</tr>

</table>
<!-- FIM DO FORMULÁRIO -->

<?php
}//tipo atendimento de instalação termina aqui?>

<table>
    <tr>
        <td><BR></td>
    </tr>
</table><?php

if ($login_fabrica == 3 AND $linha == 335 AND $login_posto == 6359) { //HD 20682 20/6/2008

    $sql = "SELECT tbl_os.os             ,
                tbl_os.posto             ,
                tbl_os.tipo_atendimento  ,
                (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (98,99,101) ORDER BY data DESC LIMIT 1) AS status_os
            FROM tbl_os
            WHERE tbl_os.os               = $os
            AND   tbl_os.posto            = $login_posto
            ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $status_os         = pg_fetch_result($res, 0, 'status_os');
        $tipo_atendimento  = pg_fetch_result($res, 0, 'tipo_atendimento');

        if ($tipo_atendimento == 37) {

            $domicilio = "checked";

            if ($status_os == 98) {
                $campos_domicilio = " readonly='readonly' ";
                $check_domicilio  = " disabled ";
                $alerta_domicilio = " onClick=\"alert('A OS foi para aprovação. Aguarde ser analisada.')\" ";
            }

            if ($status_os == 99) {
                $campos_domicilio = " readonly='readonly' ";
                $alerta_domicilio = " onClick=\"alert('Não é possível alterar essa informação. A Kilometragem já foi aprovada!')\" ";
            }

        } else {
            $mostra_dados_domicilio = " display:none; ";
        }

        if ($status_os == 99) {
            echo "<input type='hidden' name='atendimento_domicilio' value='t'>";
        } else {?>
            <INPUT TYPE='checkbox' <?=$check_domicilio?> NAME='atendimento_domicilio' value='t' <?=$domicilio?> onClick="mostraDomicilio(this,'tbl_domicilio')" >
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Atendimento Domicilio</font><?php
        }
        echo "<INPUT TYPE='hidden' NAME='status_os' VALUE='$status_os'>";?>
            <table width='800' align='center' border='1' bordercolor='#666666' cellspacing='0' cellpadding='2' style='<?=$mostra_dados_domicilio?>' id='tbl_domicilio'>
            <tr>
                <td width='100%' align='center'  bgcolor='#666666' colspan='4'>
                    <font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'>Atendimento Domicilio</font>
                </td>
            </tr>
            <tr>
                <td>
                    <table width='795' align='center' border='0' cellspacing='0' cellpadding='2'>
                        <tr>
                            <td width='25%'>
                                <FONT SIZE="1">Kilometragem:</FONT>
                                <INPUT TYPE="text" NAME="qtde_km" value="<?=$qtde_km ?>" size="5" maxlength="10" class="frm" <?=$campos_domicilio?> <?=$alerta_domicilio?> />
                            </td><?php
                            //hd 24288
                            //<td >
                            //    <FONT SIZE="1">Número de Autorização:</FONT>
                            //    <INPUT TYPE="text" NAME="autorizacao_domicilio" value="<? echo $autorizacao_domicilio ?><?//" size="10" maxlength="15" class="frm" <?=$campos_domicilio?> <?//=$alerta_domicilio?><?//>
                            //</td>?>
                            <td nowrap>
                                <FONT SIZE="1">Justificativa da Kilometragem:</FONT>
                                <INPUT TYPE="text"  NAME="justificativa_autorizacao"  value="<? echo $justificativa_autorizacao ?>" size="70" maxlength="255" class="frm" <?=$campos_domicilio?> <?=$alerta_domicilio?>>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table><?php

        echo '<table>';
            echo '<tr>';
                echo '<td><BR></td>';
            echo '</tr>';
        echo '</table>';

        $sql = "SELECT  tbl_os.os                            ,
                        (SELECT tbl_status_os.descricao FROM tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
                        tbl_os_status.observacao              ,
                        to_char(tbl_os_status.data, 'dd/mm/yyy') AS data
                        FROM tbl_os
                LEFT JOIN tbl_os_status USING(os)
                WHERE tbl_os.os    = $os
                AND   tbl_os.posto = $login_posto
                AND   tbl_os_status.status_os IN(
                        SELECT status_os
                        FROM tbl_os_status
                        WHERE tbl_os.os = tbl_os_status.os
                        AND status_os IN (98,99,101) ORDER BY data DESC
                )";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            echo "<table width='650' align='center' border='0' bordercolor='#666666' cellspacing='2' cellpadding='1'>";
                echo "<tr>";
                    echo "<td align='center'  bgcolor='#666666' colspan='5'>";
                        echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'>Historico Atendimento Domicilio</font>";
                    echo "</td>";
                echo "</tr>";
                echo "<tr>";
                    echo "<td align='center'  bgcolor='#666666'>";
                        echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'>Status</font>";
                    echo "</td>";
                    echo "<td align='center'  bgcolor='#666666'>";
                        echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'>Justificativa Autorização</font>";
                    echo "</td>";
                    echo "<td align='center'  bgcolor='#666666'>";
                        echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'>Data</font>";
                    echo "</td>";
                echo "</tr>";
                for ($x = 0; $x < pg_num_rows($res); $x++) {

                    $status_os  = pg_fetch_result($res, $x, 'status_os');
                    $observacao = pg_fetch_result($res, $x, 'observacao');
                    $data       = pg_fetch_result($res, $x, 'data');

                    if ($x % 2 == 0) $cor = "#B4D6E1";
                    else             $cor = "#D7D7D7";

                    echo "<tr bgcolor='$cor'>";
                        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_os</font></td>";
                        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$observacao</font></td>";
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
                    echo "</tr>";

                }
            echo "</table>";

        }
    }
}?>

<table width='700' align='center' border='0' cellspacing='0' cellpadding='5'><?php
if ($login_fabrica == 19) { ?>
    <tr><?php
        //retirado por Wellington - chamado 1572 (Natanael)
        /*
        <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
            <br>
            <FONT SIZE="1">Valores Adicionais:</FONT>
            <br>
            <FONT SIZE="1">R$ </FONT>
            <INPUT TYPE="text" NAME="valores_adicionais" value="<? echo $valores_adicionais ?>" size="10" maxlength="10" class="frm">
            <br><br>
        </td>
        <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
            <br>
            <FONT SIZE="1">Justificativa dos Valores Adicionais:</FONT>
            <br>
            <INPUT TYPE="text" NAME="justificativa_adicionais" value="<? echo $justificativa_adicionais ?>" size="30" maxlength="100" class="frm">
            <br><br>
        </td>
        */?>
        <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
            <br>
            <FONT SIZE="1">Quilometragem:</FONT>
            <br>
            <INPUT TYPE="text" NAME="qtde_km" value="<? echo $qtde_km ?>" size="5" maxlength="10" class="frm">
            <br><br>
        </td>
    </tr><?php
}

if (($ip == "201.27.214.156") or ($ip == "200.228.76.116")) {

    if ($login_fabrica == 15) {?>
        <tr>
            <td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
                <table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#B63434">
                    <tr>
                        <td valign="middle" align="RIGHT">
                            <FONT SIZE="1" color='#FFFFFF'>***Data Fechamento:   </FONT>
                        </td>
                        <td valign="middle" align="LEFT">
                            <INPUT TYPE="text" NAME="data_fechamento" value="<?=$data_fechamento;?>" size="12" maxlength="10" class="frm">
                            <br />
                            <font size='1' color='#FFFFFF'>dd/mm/aaaa</font>
                        </td>
                    </tr>
                </table>
            </td>
        </tr><?php
    }

}

if (($login_fabrica == 3 or $login_fabrica == 45 or $login_fabrica == 24) AND $login_posto == 6359) {?>
    <tr>
        <td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
            <div id='orcamento_mao_obra' <? if (strlen($orcamento)==0){ echo "style='display:none'";} ?>>
            <table width='55%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#244D8A">
                <tr>
                    <td valign="middle" align="RIGHT">
                        <FONT SIZE="1" color='#FFFFFF'>Valor da Mão de Obra do Orçamento: </FONT>
                    </td>
                    <td valign="middle" align="LEFT">
                        <INPUT TYPE="text" NAME="valor_mo_orcamento" value="<? echo $valor_mo_orcamento; ?>" size="12" maxlength="10" class="frm" onBlur='checarNumero(this)' />
                        <a href="javascript:alert('Valor de Mão de Obra do Orçamento somente para O.S. que não utilizarem peças em garantia.')"><img  id='imagem_ajuda_$i' src='imagens/help1.gif' width='24' height='16' align='absmiddle' border='0' /></a>
                    </td>
                </tr>
                <tr>
                    <td colspan='2' align='center'>
                        <FONT SIZE="1" color='#FFFFFF'>* O Valor de mão de obra do orçamento é apenas para controle do posto. Este valor não será lançado em extrato.
                        </FONT>
                    </td>
                </tr><?php
                if ($login_fabrica != 45 and $login_fabrica != 24){ ?>
                    <tr>
                        <td valign="middle" align="RIGHT">
                            <FONT SIZE="1" color='#FFFFFF'>Aprovado: </FONT>
                        </td>
                        <td valign="middle" align="LEFT">
                            <FONT SIZE="1" color='#FFFFFF'>
                                Sim
                                <INPUT TYPE="radio" NAME="orcamento_aprovado" value='' <? if ($aprovado=='t'){echo " CHECKED ";}?> />
                                &nbsp;&nbsp;
                                Não
                                <INPUT TYPE="radio" NAME="orcamento_aprovado" value='t' <? if ($aprovado=='f'){echo " CHECKED ";}?> />
                            </FONT>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2' align='center'>
                            <b style='color:#FFFFFF;font-size:12px'><?php
                                if ($aprovado == 't') {
                                    echo "Orçamento aprovado em $data_aprovacao";
                                } else if ($aprovado == 'f') {
                                    echo "Orçamento <b style='color:red'>REPROVADO</b> em $data_reprovacao pelo motivo: '$motivo_reprovacao' ";
                                } else {
                                    echo "Orçamento não aprovado/reprovado";
                                }?>
                            </b>
                        </td>
                    </tr><?php
                }?>
            </table>
        </td>
    </tr><?php
}

if ($login_fabrica == 96 and $tipo_atendimento == 93) {
			$sql = " SELECT total,
							total_horas
					FROM tbl_orcamento_os_fabrica
					WHERE os = $os
					AND   fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$total_orcamento = pg_fetch_result($res,0,'total');
				$total_horas = pg_fetch_result($res,0,'total_horas');
				$total_orcamento = number_format($total_orcamento,2,",",".");
			}
			?>
			<table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' >
				<tr>
					<td valign="middle" align="RIGHT">
						<FONT SIZE="1" >Total do Orçamento</FONT>
					</td>
					<td valign="middle" align="LEFT">
						<INPUT TYPE="text" NAME="total_orcamento" rel='numero' value="<?=$total_orcamento;?>" size="12" maxlength="10" class="frm">
					</td>
				</tr>
				<tr>
					<td valign="middle" align="RIGHT">
						<FONT SIZE="1" >Total Horas</FONT>
					</td>
					<td valign="middle" align="LEFT">
						<INPUT TYPE="text" NAME="total_horas" rel='numero' value="<?=$total_horas;?>" size="12" maxlength="10" class="frm">
					</td>
				</tr>
			</table>
			<br/>
	<?php
}

//HD 81926
if ($login_fabrica == 58 or $login_fabrica == 46 or $login_fabrica == 1 or ($login_fabrica == 19 and $tipo_atendimento == 2) or ($login_fabrica == 19 and $tipo_atendimento == 20)) {

    $sql = "SELECT tbl_laudo_tecnico_os.*
                FROM tbl_laudo_tecnico_os
                WHERE os = $os
                ORDER BY ordem, laudo_tecnico_os;";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

        echo "<br />";

        echo "<table align='center' width='700' style=' border:#485989 1px solid; font-size:12px;' border='0' borderstyle='collapse' cellspacing='3' cellpadding='3' bgcolor='#e6eef7'>";
            echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
                echo "<td colspan='3' style='font-size: 12px' align='center'><b>LAUDO TÉCNICO</b></td>";
            echo "</tr>";
            echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
                echo "<td>QUESTÃO</td>";
                echo "<td>AFIRMAÇÃO</td>";
                echo "<td>RESPOSTA</td>";
            echo "</tr>";

        for ($i = 0; $i < pg_num_rows($res); $i++) {

            $laudo      = pg_fetch_result($res,$i,'laudo_tecnico_os');
            $titulo     = pg_fetch_result($res,$i,'titulo');
            $afirmativa = pg_fetch_result($res,$i,'afirmativa');
            $observacao = pg_fetch_result($res,$i,'observacao');

            $cor = "#F7F5F0";

            if ($i % 2 == 0) {
                $cor = '#F1F4FA';
            }

            echo "<tr bgcolor='$cor'>";
                echo "<td align='left'><font size='-2' color='#939393'><b>$titulo</b></font></td>";
                if (strlen($afirmativa) > 0) {
                    echo "<td align='center' width='10' nowrap><font size='-3'>";
                    echo ($afirmativa == 't') ? 'Sim' : 'Não';
                    echo "</font></td>";
                } else {
                    echo "<td align='center' width='10'>&nbsp;</td>";
                }
                if (strlen($observacao) > 0) {
                    echo "<td align='left' size='350'><font size='-3'>$observacao</font></td>";
                } else {
                    echo "<td align='left' width='350'>&nbsp;</td>";
                }

            echo "</tr>";

        }

        echo "</table>";
        echo "<input type='hidden' name='digitou_laudo' id='digitou_laudo' value='s'>";

    } else {

        echo "<br />";
        echo "<table align='center' width='700' style=' border:#485989 1px solid; font-size:12px;' border='0' borderstyle='collapse' cellspacing='3' cellpadding='3' bgcolor='#e6eef7'>";
            echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
                echo "<td colspan='3' style='font-size: 12px' align='center'><b>LAUDO TÉCNICO</b></td>";
            echo "</tr>";
            echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
                echo "<td>QUESTÃO</td>";
                echo "<td>AFIRMAÇÃO</td>";
                echo "<td>RESPOSTA</td>";
            echo "</tr>";

        if ($login_fabrica == 19) {

            $sql = "SELECT tbl_laudo_tecnico.*
                        FROM tbl_laudo_tecnico
                        WHERE linha = $produto_linha
                        ORDER BY ordem;";

            $res = pg_query($con,$sql);

        } else {

            $sql = "SELECT tbl_laudo_tecnico.*
                        FROM tbl_laudo_tecnico
                        WHERE produto = $produto_os
                        ORDER BY laudo_tecnico;";

            $res = pg_query($con,$sql);

            if (pg_num_rows($res) == 0) {

                $sql = "SELECT tbl_laudo_tecnico.*
                            FROM tbl_laudo_tecnico
                            WHERE familia = $produto_familia
                            ORDER BY laudo_tecnico;";

                $res = pg_query($con,$sql);

            }

        }

        if (pg_num_rows($res) > 0) {

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $laudo      = pg_fetch_result($res,$i,'laudo_tecnico');
                $titulo     = pg_fetch_result($res,$i,'titulo');
                $afirmativa = pg_fetch_result($res,$i,'afirmativa');
                $observacao = pg_fetch_result($res,$i,'observacao');
                $ordem      = pg_fetch_result($res,$i,'ordem');

                #Recarrega o form
                $afirmativa_laudo = $_POST["afirmativa_$laudo"];
                $observacao_laudo = $_POST["observacao_$laudo"];
                $ordem_laudo      = $_POST["ordem_$laudo"];

                echo "<tr bgcolor='#FFFFFF'>";
                    echo "<td align='left' nowrap><font size='-2'><b>$titulo<INPUT TYPE='hidden' value='$ordem' NAME='ordem_$laudo'></b></font></td>";
                    if ($afirmativa == 't') {
                        echo "<td align='left' nowrap><font size='-2'><INPUT TYPE='radio' NAME='afirmativa_$laudo'";
                        if ($afirmativa_laudo == 't') echo "checked='checked'";
                        echo " value='t'>Sim <INPUT TYPE='radio' NAME='afirmativa_$laudo'";
                        if ($afirmativa_laudo == 'f') echo " checked='checked'";
                        echo " value='f'> Não</font></td>";
                    } else {
                        echo "<td align='left'>&nbsp;</td>";
                    }

                    if ($observacao == 't') {
                        echo "<td align='left'><font size='-2'><INPUT TYPE='text' size='50' maxlength='256' value='$observacao_laudo' NAME='observacao_$laudo'></font></td>";
                    } else {
                        echo "<td align='left'>&nbsp;</td>";
                    }

                echo "</tr>";

            }

        }

        echo "</table>";
        echo "<input type='hidden' name='digitou_laudo' id='digitou_laudo' value='n' />";

    }

}

if ($login_fabrica == 30) {//HD 27561

    $fogao              = strtoupper(trim($_POST['fogao']));
    $marca_fogao        = strtoupper(trim($_POST['marca_fogao']));

    $refrigerador       = strtoupper(trim($_POST['refrigerador']));
    $marca_refrigerador = strtoupper(trim($_POST['marca_refrigerador']));

    $bebedouro          = strtoupper(trim($_POST['bebedouro']));
    $marca_bebedouro    = strtoupper(trim($_POST['marca_bebedouro']));

    $microondas         = strtoupper(trim($_POST['microondas']));
    $marca_microondas   = strtoupper(trim($_POST['marca_microondas']));

    $lavadoura          = strtoupper(trim($_POST['lavadoura']));
    $marca_lavadoura    = strtoupper(trim($_POST['marca_lavadoura']));?>

    <table width='650' align='center' border='0' cellspacing='0' cellpadding='5'>
        <TR>
            <TD style='font-size: 12px;'>Fogão</TD>
            <TD style='font-size: 12px;'>Marca</TD>
            <TD style='font-size: 12px;'>Refrigerador</TD>
            <TD style='font-size: 12px;'>Marca</TD>
            <TD style='font-size: 12px;'>Bebedouro</TD>
            <TD style='font-size: 12px;'>Marca</TD>
        </TR>
        <TR>
            <TD>
                <SELECT NAME='fogao'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='2Q' <? if($fogao == '2Q') echo 'SELECTED'; ?>>2Q</OPTION>
                    <OPTION VALUE='4Q' <? if($fogao == '4Q') echo 'SELECTED'; ?>>4Q</OPTION>
                    <OPTION VALUE='5Q' <? if($fogao == '5Q') echo 'SELECTED'; ?>>5Q</OPTION>
                    <OPTION VALUE='6Q' <? if($fogao == '6Q') echo 'SELECTED'; ?>>6Q</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='marca_fogao'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='ATLAS' <? if($marca_fogao == 'ATLAS') echo 'SELECTED'; ?>>ATLAS</OPTION>
                    <OPTION VALUE='BOSCH' <? if($marca_fogao == 'BOSCH') echo 'SELECTED'; ?>>BOSCH</OPTION>
                    <OPTION VALUE='BRASTEMP' <? if($marca_fogao == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
                    <OPTION VALUE='CONTINENTAL' <? if($marca_fogao == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
                    <OPTION VALUE='CONSUL' <? if($marca_fogao == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
                    <OPTION VALUE='DAKO' <? if($marca_fogao == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
                    <OPTION VALUE='ELETROLUX' <? if($marca_fogao == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
                    <OPTION VALUE='ESMALTEC' <? if($marca_fogao == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
                    <OPTION VALUE='OUTROS' <? if($marca_fogao == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='refrigerador'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='1 Porta' <? if($refrigerador == '1 PORTA') echo 'SELECTED'; ?>>1 Porta</OPTION>
                    <OPTION VALUE='2 Portas' <? if($refrigerador == '2 PORTAS') echo 'SELECTED'; ?>>2 Portas</OPTION>
                    <OPTION VALUE='Frost Free' <? if($refrigerador == 'FROST FREE') echo 'SELECTED'; ?>>Frost Free</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='marca_refrigerador'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='BRASTEMP' <? if($marca_refrigerador == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
                    <OPTION VALUE='CONSUL' <? if($marca_refrigerador == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
                    <OPTION VALUE='CONTINENTAL' <? if($marca_refrigerador == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
                    <OPTION VALUE='DAKO' <? if($marca_refrigerador == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
                    <OPTION VALUE='ELETROLUX' <? if($marca_refrigerador == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
                    <OPTION VALUE='ESMALTEC' <? if($marca_refrigerador == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
                    <OPTION VALUE='OUTROS' <? if($marca_refrigerador == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='bebedouro'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='Coluna' <? if($bebedouro == 'COLUNA') echo 'SELECTED'; ?>>Coluna</OPTION>
                    <OPTION VALUE='Mesa' <? if($bebedouro == 'MESA') echo 'SELECTED'; ?>>Mesa</OPTION>
                    <OPTION VALUE='Suporte' <? if($bebedouro == 'SUPORTE') echo 'SELECTED'; ?>>Suporte</OPTION>
                    <OPTION VALUE='Filtro' <? if($bebedouro == 'FILTRO') echo 'SELECTED'; ?>>Filtro</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='marca_bebedouro'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='ESMALTEC' <? if($marca_bebedouro == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
                    <OPTION VALUE='OUTROS' <? if($marca_bebedouro == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
                </SELECT>
            </TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;'>Microondas</TD>
            <TD style='font-size: 12px;'>Marca</TD>
            <TD style='font-size: 12px;'>Lavadoura</TD>
            <TD style='font-size: 12px;'>Marca</TD>
        </TR>
        <TR>
            <TD>
                <SELECT NAME='microondas'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='Pequeno' <? if($microondas == 'PEQUENO') echo 'SELECTED'; ?>>Pequeno</OPTION>
                    <OPTION VALUE='Medio'  <? if($microondas == 'MEDIO') echo 'SELECTED'; ?>>Médio</OPTION>
                    <OPTION VALUE='Grande'  <? if($microondas == 'GRANDE') echo 'SELECTED'; ?>>Grande</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='marca_microondas'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='BOSCH' <? if($marca_microondas == 'BOSCH') echo 'SELECTED'; ?>>BOSCH</OPTION>
                    <OPTION VALUE='BRASTEMP' <? if($marca_microondas == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
                    <OPTION VALUE='CCE' <? if($marca_microondas == 'CCE') echo 'SELECTED'; ?>>CCE</OPTION>
                    <OPTION VALUE='CONSUL' <? if($marca_microondas == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
                    <OPTION VALUE='CONTINENTAL' <? if($marca_microondas == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
                    <OPTION VALUE='ELETROLUX' <? if($marca_microondas == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
                    <OPTION VALUE='ESMALTEC' <? if($marca_microondas == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
                    <OPTION VALUE='PANASONIC' <? if($marca_microondas == 'PANASONIC') echo 'SELECTED'; ?>>PANASONIC</OPTION>
                    <OPTION VALUE='OUTROS' <? if($marca_microondas == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='lavadoura'>
                    <OPTION VALUE='' ></OPTION>
                    <OPTION VALUE='Sim' <? if($lavadoura == 'SIM') echo 'SELECTED'; ?>>Sim</OPTION>
                    <OPTION VALUE='Nao' <? if($lavadoura == 'NAO') echo 'SELECTED'; ?>>Não</OPTION>
                </SELECT>
            </TD>
            <TD>
                <SELECT NAME='marca_lavadoura'>
                    <OPTION VALUE=''></OPTION>
                    <OPTION VALUE='BRASTEMP' <? if($marca_lavadoura == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
                    <OPTION VALUE='CONSUL' <? if($marca_lavadoura == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
                    <OPTION VALUE='CONTINENTAL' <? if($marca_lavadoura == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
                    <OPTION VALUE='DAKO' <? if($marca_lavadoura == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
                    <OPTION VALUE='ELETROLUX' <? if($marca_lavadoura == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
                    <OPTION VALUE='ESMALTEC' <? if($marca_lavadoura == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
                    <OPTION VALUE='OUTROS' <? if($marca_lavadoura == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
                </SELECT>
            </TD>
        </TR>
    </TABLE><?php

}?>

<tr>
    <td align="center" style='height=27px;width:380px;background-color:white' valign="middle" colspan="5">
        <font size="1">Observação:</font><br>
	<textarea name="obs" cols="64" rows='3' maxlength="255" class="frm"><?=$obs?></textarea><?php
        if ($login_fabrica <> 19) {//hd3335?>
            <br /><br />
            <font size="1" color="#ff0000">O campo "Observação" é somente para o controle do posto autorizado. <br>O fabricante não se responsabilizará pelos dados aqui digitados.</FONT>
            <br /><br /><?php
        }?>
    </td>
    <td>
    <?php
        if ($anexaNotaFiscal) {
			if (!temNF($os, 'bool') and !temNF($os, 'bool', 2)) {
				echo $inputNotaFiscal;
			} else {
				echo '<p style="text-align:center;font-weight:bold">Imagem em anexo</p>' . temNF($os) . temNF($os, 'link', 2) . $include_imgZoom;
			}
?>	</td>

<?				}?>
</tr><?php

if (strlen($orientacao_sac) > 0) {?>
    <tr>
        <td valign="middle" align="center" <?php echo ($anexaNotaFiscal) ?"colspan='4'":"colspan='3'"?> bgcolor="#eeeeee">
            <FONT SIZE="1"><b>Orientação do SAC ao Posto Autorizado</b></FONT>
            <p>
                <?=$orientacao_sac?>
                <br /><br />
            </p>
        </td>
    </tr><?php
}

if ($login_fabrica == 6) {
    $qtde_item = $qtde_item +$qtde_item_aparencia;
}

if (($login_fabrica == 11 OR $login_fabrica == 10) AND $login_posto == 6359) {?>

    <style type="text/css">
        body {
            margin: 0px;
        }
		
		.msg_erro{
			background-color:#FF0000;
			font: bold 16px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
        .titulo {
            font-family: Arial;
            font-size: 7pt;
            text-align: right;
            color: #000000;
            background: #D6CBA9;
        }
        .titulo2 {
            font-family: Arial;
            font-size: 7pt;
            text-align: center;
            color: #000000;
            background: #ced7e7;
        }
        .titulo3 {
            font-family: Arial;
            font-size: 10px;
            text-align: right;
            color: #000000;
            background: #E9DACD;
            height:16px;
            padding-left:5px
        }

        .inicio {
            font-family: Arial;
            FONT-SIZE: 8pt;
            font-weight: bold;
            text-align: left;
            color: #FFFFFF;
        }

        .conteudo {
            font-family: Arial;
            FONT-SIZE: 8pt;
            font-weight: bold;
            text-align: left;
            background: #F4F7FB;
        }

        .justificativa{
            font-family: Arial;
            FONT-SIZE: 10px;
            background: #F4F7FB;
        }

        .Tabela{
            border:1px solid #d2e4fc;
            background-color:#666666;
            }

        .subtitulo {
            font-family: Verdana;
            FONT-SIZE: 9px;
            text-align: left;
            background: #F4F7FB;
            padding-left:5px
        }
        .inpu{
            border:1px solid #666666;
        }
    </style>

    <TABLE width='700' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
        <TR>
            <TD><font size='2' color='#FFFFFF'><center><b>INTERAGIR NA OS</b></center></font></TD>
        </TR>
        <TR>
            <TD class='conteudo'><br />
                <TABLE align='center' border='0' cellspacing='0' cellpadding='0'>
                    <TR>
                        <TD><INPUT TYPE="text" NAME="interacao_msg" size='70'>&nbsp;<INPUT TYPE="checkbox" NAME="interacao_exigir_resposta" value='t'>&nbsp;<font size='1'>Enviar p/ o fabricante.</font></TD>
                    </TR>
                </TABLE>
                <br /><?php

                $sql = "SELECT os_interacao,
                                to_char(data,'DD/MM/YYYY HH24:MI') as data,
                                comentario,
                                tbl_admin.nome_completo
                            FROM tbl_os_interacao
                            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
                            WHERE os = $os
                            AND interno IS FALSE
                            ORDER BY os_interacao DESC;";

                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {

                    for ($i = 0; $i < pg_num_rows($res); $i++) {
                        $os_interacao     = pg_fetch_result($res,$i,os_interacao);
                        $interacao_msg    = pg_fetch_result($res,$i,comentario);
                        $interacao_data   = pg_fetch_result($res,$i,data);
                        $interacao_nome   = pg_fetch_result($res,$i,nome_completo);

                        if ($i == 0) {
                            echo "<br />";
                            echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
                                echo "<tr height='18'>";
                                    echo "<td width='18' bgcolor='#F5E8CF'>&nbsp;</td>";
                                    echo "<td align='left'><font size='1'><b>&nbsp; Interação do fabricante.</b></font></td>";
                                echo "</tr>";
                            echo "</table>";

                            echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela' >";
                                echo "<tr align='center'>";
                                    echo "<td class='titulo'><CENTER>Nº</CENTER></td>";
                                    echo "<td class='titulo'><CENTER>Data</CENTER></td>";
                                    echo "<td class='titulo'><CENTER>Mensagem</CENTER></td>";
                                    echo "<td class='titulo'><CENTER>Fabrica</CENTER></td>";
                                echo "</tr>";
                        }

                        if (strlen($interacao_nome) > 0) {
                            $cor = "style='font-family: Arial; FONT-SIZE: 8pt; font-weight: bold; text-align: left; background: #F5E8CF;'";
                        } else {
                            $cor = "class='conteudo'";
                        }

                        echo "<tr>";
                            echo "<td width='25' $cor>"; echo pg_num_rows($res) - $i; echo "</td>";
                            echo "<td width='90' $cor nowrap>$interacao_data</td>";
                            echo "<td $cor>$interacao_msg</td>";
                            echo "<td $cor nowrap>$interacao_nome</td>";
                        echo "</tr>";
                    }

                    echo "</TABLE>";

                }

                echo '<br />';
            echo '</TD>';
        echo '</TR>';
    echo '</TABLE>';
    echo '<br />';

}

    if ($login_fabrica == 80) {?>
        <table>
            <tr>
                <td align='center'>Data de Envio</td>
            </tr>
            <tr>
                <td class='table_line2' align='center'>
                    <input type='text' name='data_envio' id='data_envio' size='12' value='<?=$rastreamento_envio;?>'>
                </td>
            </tr>
            <tr>
                <td  class='conteudo'>Número do Rastreamento do Sedex</td>
            </tr>
            <tr>
                <td class='table_line2' align='center'>
                    <input type='text' name='rastreamento_envio' size='30' value='<?=$rastreamento_envio;?>'>
                </td>
            </tr>
        </table><?php
    }

    if ($login_fabrica == 11 and strlen($os) > 0) {
        $sql = "select tbl_os.os
                    from tbl_os
                    join tbl_os_produto using(os)
                    join tbl_os_item using(os_produto)
                    where tbl_os.os = $os;";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $existe_item = pg_fetch_result($res,0,os);
            echo "<input type='hidden' value='$existe_item' name='existe_item'>";
        } else {
            echo "<input type='hidden' value='0' name='existe_item'>";
        }
    }?>
    <tr>
        <td height="27" valign="middle" align="center"  <?php echo ($anexaNotaFiscal) ?"colspan='4'":"colspan='3'"?> bgcolor="#FFFFFF">
            <input type='hidden' name='qtde_item' id='qtde_item' value='<? echo $qtde_item ?>'>
            <input type='hidden' name='qtde_mostrar' id='qtde_mostrar' value='<? echo $qtde_itens_mostrar ?>'>
            <input type="hidden" name="btn_acao" value="">
            <input type="hidden" name="btn_imprimir" value="">

			<!-- HD 342629 -->
			<input type='button' value='GRAVAR' style='cursos:pointer;'  ALT="Gravar itens da Ordem de Serviço" title='Gravar itens da Ordem de Serviço' ONCLICK="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" >
			<?php
            if ($login_fabrica == 3) {?> &nbsp;&nbsp;&nbsp

			<!-- HD 342629 -->
			<input type='button' value='FECHAR' style='cursos:pointer;'  ALT="Gravar itens da Ordem de Serviço" title='Gravar itens da Ordem de Serviço' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.btn_imprimir.value='imprimir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }">

			<? } ?>
        </td>
    </tr>
</table>
</form>
 <div id="pop" style="display:none; width:auto;">   
 
	<div>&nbsp;</div>     
              
 
 </div>
<p>

<? include "rodape.php";?>
