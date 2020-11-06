<?php
header('Content-type: text/html; charset=iso-8859-1');

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'helpdesk/mlg_funciones.php';

$msg_erro = "";

if (strlen($_POST['senha_financeiro']) > 0) {
    $senha_financeiro = anti_injection($_POST['senha_financeiro']);
    if (strlen($senha_financeiro)==0) {
        $msg_erro = traduz('digite.sua.senha', $con);
    }

    if (strlen($msg_erro)==0) {
        $sql = "SELECT senha_financeiro
                  FROM tbl_posto_fabrica
                 WHERE posto = $login_posto
                   AND fabrica = $login_fabrica
                   AND trim(senha_financeiro) = '$senha_financeiro'";
        // echo nl2br($sql);
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $token_cookie = $_COOKIE['sess'];
            add_cookie($cookie_login,"acessa_extrato","SIM");

           set_cookie_login($token_cookie,$cookie_login);

            //se for black vai pra tela de extrato da b&d
            if ($login_fabrica == 1) {
                header ("Location: os_extrato_blackedecker.php");
                exit;
            }
            //se for britania e distribuidor
            elseif ($login_fabrica == 3) {

                $sql = "SELECT   extrato
                        FROM  tbl_extrato
                        WHERE tbl_extrato.fabrica = $login_fabrica
                        AND   tbl_extrato.posto = $login_posto
                        ORDER BY  tbl_extrato.extrato DESC LIMIT 1";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {

                    $ultimo_extrato = pg_fetch_result($res,0,extrato);

                    $sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                JOIN    tbl_peca             USING (peca)
                                WHERE   tbl_faturamento.extrato_devolucao <= $ultimo_extrato
                                AND     tbl_faturamento.fabrica = $login_fabrica
                                AND     tbl_faturamento.posto             = $login_posto
                                AND     tbl_faturamento.distribuidor IS NULL
                                AND     (tbl_peca.devolucao_obrigatoria IS TRUE or tbl_peca.produto_acabado       IS TRUE)
                                AND     tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
                                AND     tbl_faturamento.extrato_devolucao NOT IN (
                                            SELECT  distinct
                                        extrato_devolucao
                                        FROM tbl_faturamento
                                        WHERE posto IN (13996,4311)
                                        AND distribuidor=$login_posto
                                        AND fabrica=$login_fabrica
                                        AND extrato_devolucao <= $ultimo_extrato
                                )
                                ORDER BY  tbl_faturamento.extrato_devolucao DESC";
                    $ress = pg_query ($con,$sqls);
                    $res_qtdes = pg_num_rows ($ress);

                    if ($res_qtdes> 0) {

                        $extrato_aux = pg_fetch_result($ress,0,extrato_devolucao);

                        $sqlD="SELECT extrato_devolucao
                            FROM   tbl_faturamento
                            WHERE  distribuidor = $login_posto
                            AND    extrato_devolucao = $extrato_aux;";
                        $resD = pg_query($con,$sqlD);

                        if (pg_num_rows($resD) == 0) {
                            $sqld = " SELECT tbl_extrato.extrato,to_char(data_geracao,'DD/MM/YYYY') as data_extrato
                                    FROM tbl_extrato
                                    WHERE extrato = $extrato_aux
                                    AND   fabrica = $login_fabrica
                                    AND   posto   = $login_posto
                                    AND   data_geracao > '2010-01-01 00:00:00'
                                    ORDER BY extrato DESC limit 1;";
                            $resd = pg_query($con,$sqld);
                            if (pg_num_rows($resd) > 0) {
                                header("Location:extratos_pendentes_britania.php");
                                exit;
                            }
                        }
                    }
                }

                if ($login_e_distribuidor == 't') {
                    header ("Location: new_extrato_distribuidor.php");
                    exit;
                }

                $sql = "SELECT codigo
                        FROM tbl_extrato
                        JOIN tbl_extrato_agrupado USING(extrato)
                        WHERE fabrica = $login_fabrica
                        AND   posto   = $login_posto ";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    header ("Location: extrato_agrupado.php");
                    exit;
                }

                $sqln = "SELECT extrato
                    FROM tbl_extrato
                    JOIN tbl_extrato_nota_avulsa USING(extrato)
                    WHERE tbl_extrato.fabrica = $login_fabrica
                    AND   tbl_extrato.posto   = $login_posto ";
                $resn = pg_query($con,$sqln);
                if (pg_num_rows($resn) > 0) {
                    header ("Location: extrato_agrupado.php");
                    exit;
                }
                header ("Location: extrato_posto_novo.php");
                exit;
            } else {
                //echo "aqiu nao";exit;
                header ("Location: os_extrato.php");
            }
        } else {
            $msg_erro = traduz('senha.invalida', $con);
        }
    }
}

if (count($_POST) and !strlen($_POST['senha_financeiro'])) {
    $msg_erro = traduz('digite.uma.senha');
}

$error_alert = true;
$layout_menu = "os";
$title = traduz('senha.do.financeiro');

include_once 'cabecalho_new.php';

if (!count(array_filter($_POST))) {
    echo $cabecalho->alert(traduz('area.restrita.para.pessoal.autorizado'), 'info', 'lock');
}
?>
<div class="container">
    <div class="tc_formulario">
        <form name="frm_pesquisa" method="POST" action="<? echo $php_self ?>" class="form-horizontal">
            <legend class="titulo_tabela"><?fecho('validacao.de.senha.do.financeiro', $con);?></legend>
            <?fecho('a.area.financeira.e.restrita.precisada.senha.do.financeiro', $con);?>
            <div class="control-group offset2">
                <label class="control-label" for="senhaf"><?=traduz('senha')?></label>
                <div class="controls">
                    <input id="senhaf" type="password" name="senha_financeiro">
                </div>
            </div>
            <div class="control-group offset2">
                <div class="controls"><button class="btn" name="btn_acao"><?=traduz('acessar')?></button></div>
            </div>
            <p></p>
        </form>
    </div>
</div>
<?php
include 'rodape.php';

