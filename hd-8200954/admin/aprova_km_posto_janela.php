<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

$posto          = $_GET['posto'];
$os             = (int)$_GET['os'];
$data_inicial   = $_GET['data_inicial'];
$data_final     = $_GET['data_final'];
$aprova         = $_GET['aprova'];
$select_acao    = $_POST['select_acao'];

if(strlen($aprova) == 0){
    $aprovacao = "98, 99, 100, 101";
}elseif($aprova=="aprovacao"){
    $aprovacao = "98";
}elseif($aprova=="aprovadas"){
    $aprovacao = "99, 100";
}elseif($aprova=="reprovadas"){
    $aprovacao = "101";
}

$array_reincidencias = array(98,99,100,101,161,162);

$sql = "SELECT  interv.os
   INTO TEMP    tmp_interv_$login_admin
        FROM    (
                    SELECT  ultima.os,
                            (
                                SELECT  status_os
                                FROM    tbl_os_status
                                WHERE   status_os IN (" . implode(',', $array_reincidencias) . ")
                                AND     tbl_os_status.os = ultima.os AND tbl_os_status.fabrica_status = $login_fabrica
                          ORDER BY      data DESC
                                LIMIT   1
                            ) AS ultimo_status
                    FROM    (
                                SELECT  DISTINCT
                                        os
                                FROM    tbl_os_status
                                WHERE   status_os IN (" . implode(',', $array_reincidencias) . ")
                                AND     tbl_os_status.fabrica_status = $login_fabrica
                            ) ultima
                ) interv
        WHERE   interv.ultimo_status IN ($aprovacao);

        CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

            /* select os from  tmp_interv_$login_admin; */

        SELECT  tbl_os.os                                                               ,
                tbl_os.hd_chamado                                                       ,
                tbl_os.posto                                                            ,
                tbl_os.data_abertura                                                    ,
                tbl_os.sua_os                                                           ,
                tbl_os.consumidor_nome                                                  ,
                tbl_os.qtde_km                                                          ,
                tbl_os.autorizacao_domicilio                                            ,
                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura            ,
                TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao           ,
                tbl_os.fabrica                                                          ,
                tbl_os.consumidor_nome                                                  ,
                tbl_os.consumidor_cidade                                                ,
                tbl_os.consumidor_estado                                                ,
                tbl_os.nota_fiscal_saida                                                ,
                tbl_os.tipo_atendimento                                                 ,
                to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')  AS data_nf_saida            ,
                tbl_posto.nome                              AS posto_nome               ,
                tbl_posto_fabrica.codigo_posto                                          ,
                tbl_posto_fabrica.contato_estado                                        ,
                tbl_posto_fabrica.contato_cidade                                        ,
                tbl_produto.referencia                      AS produto_referencia       ,
                tbl_produto.descricao                       AS produto_descricao        ,
                tbl_defeito_constatado.descricao            AS defeito_constatado       ,
                tbl_defeito_constatado_grupo.descricao      AS defeito_constatado_grupo ,
                tbl_produto.voltagem                                                    ,
                (
                    SELECT  status_os
                    FROM    tbl_os_status
                    WHERE   tbl_os.os = tbl_os_status.os
                    AND     status_os IN (" . implode(',', $array_reincidencias) . ")
                    AND     tbl_os_status.fabrica_status = $login_fabrica
              ORDER BY      data DESC
                    LIMIT   1
                )                                           AS status_os                ,
                (
                    SELECT  observacao
                    FROM    tbl_os_status
                    WHERE   tbl_os.os = tbl_os_status.os
                    AND     status_os IN (" . implode(',', $array_reincidencias) . ")
                    AND     tbl_os_status.fabrica_status = $login_fabrica
              ORDER BY      data DESC
                    LIMIT   1
                )                                           AS status_observacao        ,
                (
                    SELECT  tbl_status_os.descricao
                    FROM    tbl_os_status
                    JOIN    tbl_status_os USING(status_os)
                    WHERE   tbl_os.os = tbl_os_status.os
                    AND     status_os IN (" . implode(',', $array_reincidencias) . ")
                    AND     tbl_os_status.fabrica_status = $login_fabrica
              ORDER BY      data DESC
                    LIMIT   1
                )                                           AS status_descricao
        FROM    tmp_interv_$login_admin X
        JOIN    tbl_os                          ON  tbl_os.os                                               = X.os
        JOIN    tbl_produto                     ON  tbl_produto.produto                                     = tbl_os.produto
        JOIN    tbl_posto                       ON  tbl_os.posto                                            = tbl_posto.posto
        JOIN    tbl_posto_fabrica               ON  tbl_posto.posto                                         = tbl_posto_fabrica.posto
                                                AND tbl_posto_fabrica.fabrica                               = $login_fabrica
   LEFT JOIN    tbl_defeito_constatado          ON  tbl_defeito_constatado.defeito_constatado               = tbl_os.defeito_constatado
   LEFT JOIN    tbl_defeito_constatado_grupo    ON  tbl_defeito_constatado_grupo.defeito_constatado_grupo   = tbl_defeito_constatado.defeito_constatado_grupo
        WHERE   tbl_os.fabrica = $login_fabrica
		AND tbl_posto_fabrica.fabrica   = $login_fabrica
    ";
    if($os != 0){
        $sql .= "
        AND     tbl_os.os = $os
        ";
    }else{
        if(strlen($data_inicial) && strlen($data_final)){
            $sql .= "
        AND     tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
            ";
        }
	}
	if(!empty($posto)) {
		$sql .= " AND  tbl_posto_fabrica.posto     = $posto "; 
	}
//     exit(nl2br($sql));
$res = pg_query($con,$sql);

if(strlen($select_acao)>0){

    $qtde_os     = trim($_POST["qtde_os"]);
    $observacao  = trim($_POST["observacao"]);

    if($select_acao == "101" AND strlen($observacao) == 0){
        $msg_erro .= "Informe o motivo da reprovação da OS.<br>";
    }

    $observacao = (strlen($observacao) > 0) ? " Observação: $observacao " : "";

    if (strlen($qtde_os)==0){
        $qtde_os = 0;
    }

    for ($x=0;$x<$qtde_os;$x++){

        $xxos         = trim($_POST["check_".$x]);
        $xxqtde_km_os = trim($_POST["qtde_km_os_".$x]);
        $xxqtde_km    = trim($_POST["qtde_km_".$x]);

        $xxqtde_km    = str_replace (".","",$xxqtde_km);
        $xxqtde_km    = str_replace (",",".",$xxqtde_km);

        if(($xxqtde_km_os <> $xxqtde_km) AND $observacao == "Observação:" ){
            $msg_erro .= "Informe o motivo da alteração do km da OS: $xxos.";
        }else{
            // ALTERARA O STATUS DE APROVADA, PARA APROVADA COM ALTERAÇÃO
            if($xxqtde_km_os <> $xxqtde_km){
                if (empty($xxqtde_km_os) && $xxqtde_km == 0) {
                    // BUG quando KM da OS é nulo, e a qtde de km aprovada é 0, o status estava indo para 100.
                } else {
                    $select_acao = "100" ;
                }
            }
        }

        if (strlen($xxos) > 0 AND strlen($msg_erro) == 0) {

            $res_os = pg_query($con,"BEGIN TRANSACTION");

            $sql = "SELECT contato_email,tbl_os.sua_os, tbl_os.posto
                    FROM tbl_posto_fabrica
                    JOIN tbl_os          ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE tbl_os.os      = $xxos
                    AND   tbl_os.fabrica = $login_fabrica";
            $res_x = pg_query($con,$sql);
            $posto_email = pg_fetch_result($res_x,0,contato_email);
            $sua_os      = pg_fetch_result($res_x,0,sua_os);
            $posto       = pg_fetch_result($res_x,0,posto);

            $sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
            $res_x = pg_query($con,$sql);
            $promotor = pg_fetch_result($res_x,0,nome_completo);

            $sql = "SELECT status_os
                    FROM tbl_os_status
                    WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
                    AND tbl_os_status.fabrica_status = $login_fabrica
                    AND os = $xxos
                    ORDER BY data DESC
                    LIMIT 1";

            $res_os = pg_query($con,$sql);

            if (pg_num_rows($res_os) > 0) {

                $status_da_os = trim(pg_fetch_result($res_os, 0, status_os));

                if ($status_da_os == 98) {
                    if ($select_acao == "99") {

                        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin) VALUES ($xxos,99,current_timestamp,'$observacao',$login_admin)";
                        $res       = pg_query($con, $sql);
                        $msg_erro .= pg_errormessage($con);
                    }

                    //ALTERADO O KM
                    if ($select_acao == "100") {
                        $observacao = trim($_POST["observacao"])." - O KM foi alterado de $xxqtde_km_os para $xxqtde_km " ;

                        $sql = "INSERT INTO tbl_os_status(os,status_os,data,observacao,admin) VALUES ($xxos, 100, current_timestamp, '$observacao', $login_admin)";

                        $res       = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);


                        $sql       = "UPDATE tbl_os SET qtde_km = $xxqtde_km, qtde_km_calculada=0 WHERE os = $xxos AND fabrica = $login_fabrica";
                        $res       = pg_query($con, $sql);
                        $msg_erro .= pg_errormessage($con);


                        // HD 149799
                        $sql       = "SELECT fn_calcula_extrato($login_fabrica, extrato) FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $xxos AND extrato IS NOT NULL and fabrica = $login_fabrica";
                        $res       = @pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                    }

                    //RECUSADA
                    if ($select_acao == "101") {

                        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin) VALUES ($xxos,101,current_timestamp,'$observacao',$login_admin)";

                        $res       = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        $sql       = "UPDATE tbl_os SET qtde_km = 0, qtde_km_calculada = 0 WHERE os = $xxos AND fabrica = $login_fabrica;";
                        $res       = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        $sql       = " SELECT fn_calcula_extrato($login_fabrica, extrato) FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $xxos AND extrato IS NOT NULL and fabrica = $login_fabrica ";
                        $res       = @pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                }
            }

            $sql = "SELECT extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $xxos and fabrica=$login_fabrica";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {

                $extrato_recalcular = pg_result($res, 0, 'extrato');

                if (strlen($extrato_recalcular) > 0) {
                    $sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato_recalcular)";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);
                }

            }

            if (strlen($msg_erro) == 0) {
                $res = pg_query($con,"COMMIT TRANSACTION");
            } else {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                $msg_erro = "Problemas na auditoria";
            }
        }
    }
    if (strlen($msg_erro) == 0) {
        echo "<script>window.parent.Shadowbox.close();</script>";
    }
}
?>
<script type="text/javascript">
function ver(os) {
    var url = "aprova_km.php?ver=endereco&os="+os;
    janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=550, height=300, top=18, left=0");
    janela_aut.focus();
}

function setCheck(theCheckbox,mudarcor,cor){
    if (document.getElementById(theCheckbox)) {
//      document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
    }
    if (document.getElementById(mudarcor)) {
        document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
    }
}
</script>
<style type="text/css">
#container_relatorio{
    overflow-y:auto;
    height:500px;
}

#relatorio_os_auditoria {
    width: 98%;
    margin: 0 auto;
    font-family:Helvetica,Arial,sans-serif;
    font-size:13px;
    background-color: #485989;
    border: 1px solid #D2E4FC;
}

#relatorio_os_auditoria > thead > tr > th {
    background-color: #485989;
    color: #FFFFFF;
    font-weight: bold;
}

body {
    margin: 0px;
}

.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    color: #000000;
    background: #ced7e7;
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
    font-size: 7pt;
    text-align: left;
    color: #000000;
    background: #ced7e7;
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

.conteudo2 {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #FFDCDC;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }
.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
.inpu{
    border:1px solid #666;
}
.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

table.bordasimples {
    border-collapse: collapse;
}

table.bordasimples tr td {
    border:1px solid #000000;
}
</style>
<form name="frm_pesquisa2" method="POST" action="<?=$_SERVER['PHP_SELF']?>" >
<input type="hidden" name="data_inicial" value="<?=$data_inicial?>" />
<input type="hidden" name="data_final" value="<?=$data_final?>" />
<input type="hidden" name="aprova" value="<?=$aprova?>" />
<input type="hidden" name="posto" value="<?=$posto?>" />
<div id="container_relatorio">
<table id="relatorio_os_auditoria" width='800px' border='0' cellspacing='1' cellpadding='0' align='center' >
    <thead>
        <tr>
            <th><img src="imagens_admin/selecione_todas.gif" onclick="javascript: checkaTodos();" alt="Selecionar todos" style="cursor: hand; margin: 0 auto;" /></th>
            <th>OS</th>
            <th>KM</th>
            <th>OBSERVAÇÃO</th>
            <th>DATA DIGITAÇÃO</th>
            <th>PRODUTO</th>
            <th>CIDADE</th>
            <th>ESTADO</th>
            <th>DESCRIÇÃO</th>
            <th>STATUS</th>
        </tr>
    </thead>
    <tbody>
<?
        for ($x = 0; $x < pg_num_rows($res); $x++) {
            $os                       = pg_fetch_result($res, $x, "os");
            $posto                    = pg_fetch_result($res, $x, "posto");
            $data_abertura            = pg_fetch_result($res, $x, "data_abertura");
            $hd_chamado               = pg_fetch_result($res, $x, "hd_chamado");
            $sua_os                   = pg_fetch_result($res, $x, "sua_os");
            $codigo_posto             = pg_fetch_result($res, $x, "codigo_posto");
            $posto_nome               = pg_fetch_result($res, $x, "posto_nome");
            $qtde_km                  = pg_fetch_result($res, $x, "qtde_km");
            $autorizacao_domicilio    = pg_fetch_result($res, $x, "autorizacao_domicilio");
            $consumidor_nome          = pg_fetch_result($res, $x, "consumidor_nome");
            $consumidor_cidade        = pg_fetch_result($res, $x, "consumidor_cidade");
            $consumidor_estado        = pg_fetch_result($res, $x, "consumidor_estado");
            $produto_referencia       = pg_fetch_result($res, $x, "produto_referencia");
            $produto_descricao        = pg_fetch_result($res, $x, "produto_descricao");
            $produto_voltagem         = pg_fetch_result($res, $x, "voltagem");
            $defeito_constatado       = pg_fetch_result($res, $x, "defeito_constatado");
            $defeito_constatado_grupo = pg_fetch_result($res, $x, "defeito_constatado_grupo");
            $data_digitacao           = pg_fetch_result($res, $x, "data_digitacao");
            $data_abertura            = pg_fetch_result($res, $x, "data_abertura");
            $status_os                = pg_fetch_result($res, $x, "status_os");
            $status_observacao        = pg_fetch_result($res, $x, "status_observacao");
            $status_descricao         = pg_fetch_result($res, $x, "status_descricao");
            $contato_estado           = pg_fetch_result($res, $x, "contato_estado");
            $contato_cidade           = pg_fetch_result($res, $x, "contato_cidade");
            $tipo_atendimento         = pg_fetch_result($res, $x, "tipo_atendimento");

            $qtde_kmx = number_format($qtde_km,3,',','.');
            $cor = ($x % 2 == 0) ? "#FEFEFE": '#E8EBEE';
?>
        <tr id="linha_<?=$x?>" style="background-color: <?=$cor?>;" >
            <td>
<?php
            if ($status_os == 98 || ($login_fabrica == 52 && ($status_os == 99 || $status_os == 100)) || ($login_fabrica == 74 && ($status_os == 161 || $status_os == 162))) {
?>
                <input type="checkbox" id="check_<?=$x?>" name="check_<?=$x?>" onclick="javascript: setCheck('check_<?=$x?>', 'linha_<?=$x?>', '<?=$cor?>');" value="<?=$os?>" <?=$disabled_check?> <?=(strlen($msg_erro) > 0 && strlen($_POST["check_{$x}"]) > 0) ? "CHECKED" : ""?> />
<?php
            }
?>
            </td>
<?php
            $sql_extrato = "SELECT extrato
                            FROM tbl_os_extra
                            JOIN tbl_extrato USING (extrato)
                            WHERE os = {$os}
                            and fabrica = {$login_fabrica}";
            $res_extrato = pg_query($con, $sql_extrato);

            unset($title_extrato, $title_extrato2);

            if (pg_num_rows($res_extrato) > 0 && strlen(pg_fetch_result($res_extrato, 0, "extrato")) > 0) {
                $title_extrato  = "<br />".pg_fetch_result($res_extrato, 0, "extrato");
                $title_extrato2 = "Esta Ordem de Serviço já consta em um extrato e será recalculado! Se você não tem certeza da alteração, não a faça! A impressão deste extrato pelo Posto ou por outro setor da administração pode ter sido realizada!";
            }
?>

            <td style="font-size: 9px; font-family: verdana" nowrap >
                <a href="os_press.php?os=<?=$os?>" title="<?=$title_extrato2?>" target="_blank" >
                    <?=$sua_os?> <?=$title_extrato?>
                </a>
            </td>
            <td>
                <input type="hidden" name="qtde_km_os_<?=$x?>" value="<?=$qtde_km?>" />
                <?php
                if ($status_os == 98) {
                ?>
                    <input type="text" size="5" name="qtde_km_<?=$x?>" class="qtde_km" value="<?=$qtde_kmx?>" />
                <?php
                } else {
                ?>
                    <?=$qtde_kmx?>
                <?php
                }
                ?>
                <a href="javascript: ver(<?=$os?>);" >Ver Endereços</a>
            </td>
            <td style="font-size: 9px; font-family: verdana;" >
                <?=$status_observacao?>
            </td>
            <td style="font-size: 9px; font-family: verdana;" >
                <?=$data_digitacao?>
            </td>
            <td style="font-size: 9px; font-family: verdana;" >
                <?=$consumidor_cidade?>
            </td>
            <td style="font-size: 9px; font-family: verdana;" >
                <?=$consumidor_estado?>
            </td>
            <td style="font-size: 9px; font-family: verdana" nowrap >
                <acronym title="Produto: <?=$produto_referencia?> - " style="cursor: help;" >
                    <?=$produto_referencia?>
                </acronym>
            </td>
            <td style="font-size: 9px; font-family: verdana;" nowrap >
                <acronym title="Produto: <?=$produto_referencia?> - <?=$produto_descricao?>" style="cursor: help;" >
                    <?=$produto_descricao?>
                </acronym>
            </td>
            <td style="font-size: 9px; font-family: verdana;" nowrap >
                <acronym title="Observação do Promotor: <?=$status_observacao?>" >
                    <?=$status_descricao?>
                </acronym>
            </td>
        </tr>
<?php
        }
?>
        <input type="hidden" name="qtde_os" value="<?=$x?>" />
    </tbody>
    <tfoot>
        <tr>
            <th style="height: 20px; background-color: #485989; text-align: left;" colspan="100%" >
                <?php
                $enable_option =  null;
                ?>
                    &nbsp;&nbsp;
                <img src="imagens/seta_checkbox.gif" align="absmiddle" /> <b style="color: #FFFFFF;" >COM MARCADOS:</b>
                <select name="select_acao" size="1" class="frm" >
                    <option></option>
                    <option value="99" <?=($_POST["select_acao"] == "99") ? "selected" : ""?> <?=$enable_option?> >APROVADO</option>
                    <option value="101" <?=($_POST["select_acao"] == "101") ? "selected" : ""?> <?=$enable_option?> >RECUSADO</option>
                </select>
                <b style="color: #FFFFFF;">Motivo:</b> <input class="frm" type="text" id="observacao" name="observacao" size="30" maxlength="250" value="" <?=($_POST["select_acao"] == "19") ? "disabled" : ""?> />
                <img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: document.frm_pesquisa2.submit();" style="cursor: hand;" align="absmiddle" />
            </th>
        </tr>
    </tfoot>
</table>
</div>
</form>
<?php

?>
</table>
