<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "../helpdesk.inc.php";

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $tipo   = filter_input(INPUT_POST,'tipo');
    $os     = filter_input(INPUT_POST,'os');
    $motivo = filter_input(INPUT_POST,'motivo');

    $sqlAud = "SELECT   tbl_auditoria_os.auditoria_os
                FROM    tbl_auditoria_os
                WHERE   tbl_auditoria_os.os = $os
                AND     auditoria_status = 4
                AND     tbl_auditoria_os.observacao ILIKE 'auditoria de devolu%o de pe%as'
                AND     liberada    IS NULL
                AND     reprovada   IS NULL
          ORDER BY      tbl_auditoria_os.data_input DESC
                LIMIT   1";
    $resAud = pg_query($con, $sqlAud);
    $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

    pg_query($con,"BEGIN TRANSACTION");
    if ($tipo == "aprovar") {
        $sqlUpAud = "
            UPDATE  tbl_auditoria_os
            SET     liberada        = CURRENT_TIMESTAMP,
                    bloqueio_pedido = FALSE,
                    paga_mao_obra   = FALSE,
                    justificativa   = '".pg_escape_string(utf8_decode($motivo))."',
                    admin           = $login_admin
            WHERE   auditoria_os = $aud_os
        ";
        $msg = json_encode(array("ok" => true, "msg" => utf8_encode("Devolução de Peças aprovada.")));
    } else if ($tipo == "recusar") {
        $sqlUpAud = "
            UPDATE  tbl_auditoria_os
            SET     reprovada        = CURRENT_TIMESTAMP,
                    paga_mao_obra   = FALSE,
                    justificativa   = '".pg_escape_string(utf8_decode($motivo))."',
                    admin           = $login_admin
            WHERE   auditoria_os = $aud_os
        ";
        $msg = json_encode(array("ok" => true, "msg" => utf8_encode("Devolução de Peças recusada.")));

    }
    $resUpAud = pg_query($con,$sqlUpAud);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
        exit;
    }

    pg_query($con,"COMMIT TRANSACTION");
    echo $msg;
    exit;
}

if (filter_input(INPUT_POST,'btn_acao') == "submit") {

    $sua_os         = filter_input(INPUT_POST,'sua_os');
    $codigo_posto   = filter_input(INPUT_POST,'codigo_posto');
    $nome_posto     = filter_input(INPUT_POST,'nome_posto');
    $data_inicial   = filter_input(INPUT_POST,'data_inicial');
    $data_final     = filter_input(INPUT_POST,'data_final');
    $status_os      = filter_input(INPUT_POST,'status_os');

    if (!empty($sua_os) && strlen($sua_os) > 4 && strlen($sua_os) < 20) {

        $pos = strpos($sua_os, "-");
        if ($pos === false) {
            if (strlen ($sua_os) > 12) {
                $pos = strlen($sua_os) - (strlen($sua_os)-6);
            } else if(strlen ($sua_os) > 11){
                $pos = strlen($sua_os) - (strlen($sua_os)-5);
            } else if(strlen ($sua_os) > 10) {
                $pos = strlen($sua_os) - (strlen($sua_os)-6);
            } else if(strlen ($sua_os) > 9) {
                $pos = strlen($sua_os) - (strlen($sua_os)-5);
            } else {
                $pos = strlen($sua_os);
            }
        } else {
            //hd 47506
            if (strlen (substr($sua_os,0,$pos)) > 11) {#47506
                $pos = $pos - 7;
            } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                $pos = $pos - 6;
            } else if(strlen ($sua_os) > 9) {
                $pos = $pos - 5;
            }
        }
        $xsua_os        = substr($sua_os, $pos,strlen($sua_os));
        $condicao_sua_os .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os = '$xsua_os' OR tbl_os.os = $sua_os) ";
    }
    if (strlen ($sua_os) > 9 || !empty($codigo_posto)) {
        if (empty($codigo_posto)) {
            $codigo_posto   = substr($sua_os,0,$pos);
        }

        $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
//         echo $sqlPosto;
        $res = pg_exec($con,$sqlPosto);
        $xposto =  pg_result($res,0,posto) ;
        if(!empty($xposto)) {
            $condicao_sua_os .= " AND tbl_os.posto = $xposto ";
        }
    }

    if(strlen($data_inicial) > 0 OR strlen($data_final) > 0){
        if (!$aux_data_inicial = dateFormat($data_inicial, 'dmy')) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else if (!$aux_data_final = dateFormat($data_final, 'dmy')) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0) {
        $sql = "SELECT tbl_posto_fabrica.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND (
                        UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')
                    OR
                        TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9')
                )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro['msg'][]    = 'Posto não encontrado';
            $msg_erro['campos'][] = 'posto';
        } else {
            $posto = pg_fetch_result($res, 0, 'posto');
        }
    }

    if (strlen($data_inicial) > 0) {
        $cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'";
    } else {
        $cond_data = " AND tbl_os.data_abertura BETWEEN current_timestamp - interval '1 year' and current_timestamp";
    }

    switch ($status_os) {
        case "aprovacao":
            $cond_auditado = "  AND tbl_auditoria_os.liberada   IS NULL
                                AND tbl_auditoria_os.reprovada  IS NULL";
            break;
        case "aprovadas":
            $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL";
            break;
        case "reprovadas":
            $cond_auditado = " AND tbl_auditoria_os.reprovada IS NOT NULL";
            break;
    }

    $sql = "SELECT  DISTINCT
                    tbl_os.os                                                                   ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')              AS data_abertura    ,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')            AS data_fechamento  ,
                    tbl_os.posto                                                                ,
                    tbl_os.sua_os                                                               ,
                    tbl_os.os_reincidente                                                               ,
                    TO_CHAR(tbl_auditoria_os.data_input,'DD/MM/YYYY')       AS data_auditoria   ,
                    tbl_posto.nome AS posto_nome ,
                    tbl_posto_fabrica.codigo_posto AS codigo_posto
            FROM    tbl_auditoria_os
            JOIN    tbl_os              USING(os)
            JOIN    tbl_posto           USING(posto)
            JOIN    tbl_posto_fabrica   USING(posto,fabrica)
            WHERE   tbl_os.fabrica = $login_fabrica
            AND     tbl_auditoria_os.auditoria_status = 4
            AND     tbl_auditoria_os.observacao ILIKE 'auditoria de devolu%o de pe%as'
            $condicao_sua_os
            $cond_data
            $cond_auditado
    ";
    $resSubmit = pg_query($con,$sql);
}

$layout_menu = "auditoria";
$title = "AUDITORIA DEVOLUÇÃO DE PEÇAS";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>
<style type="text/css">
    .reinc {
        background-color:##FFC0CB;
    }
</style>
<script type="text/javascript">
var hora = new Date();
var table = new Object();

$(function() {
    table['table'] = '#resultado_auditoria_garantia_peca';
    table['type'] = 'full';
    $.dataTableLoad(table);

    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("button[name$=_os]").click(function(e){
        e.preventDefault();

        var os = $(this).attr("rel");
        var tipo = $(this).attr("name").split("_");
        var osAcao = os+"|"+tipo[0];

        if (os != undefined && os.length > 0) {
            Shadowbox.open({
                content: $("#DivMotivo").html().replace(/__OsAcao__/, osAcao),
                player: "html",
                height: 135,
                width: 400,
                options: {
                    enableKeys: false
                }
            });
        }
    });
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

$(document).on("click","#button_motivo",function(e){

    var obj         = $(this).attr("rel");
    var dados       = obj.split("|");
    var os          = dados[0];
    var intervencao = dados[1];
    var motivo      = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());
    var gravar = true;

    if (motivo.length == 0 && intervencao == 'recusar') {
        alert("Digite o motivo da recusa.");
        gravar = false;
        Shadowbox.close();
    }
    if (gravar) {
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:intervencao,
                os:os,
                motivo:motivo
            },
            beforeSend:function(){
                $("#sb-container").find("div.conteudo").hide();
                $("#sb-container").find("div.loading").show();
            }
        })
        .done(function(data){
            if (data.ok) {
                $("#linha_"+os).hide();
                alert(data.msg);

                $("#sb-container").find("div.loading").hide();
                Shadowbox.close();
            }
        })
        .fail(function(){
            alert("Erro ao gravar ação da auditoria de devolução de peças");
            $("#sb-container").find("div.conteudo").hide();
            $("#sb-container").find("div.loading").hide();
            Shadowbox.close();
        });
    }
});

$(document).on("click", "div[name=mostrar_pecas]", function() {
    var linha = $(this);
    linha.next("#m_peca").css({"width":"360px"});
    linha.next("#m_peca").show();
    linha.attr('name', 'esconder_pecas');
    linha.html('<span class="label label-info">Esconder peças</span>');
    $(".acoes").css({"width":"380px"});
});

$(document).on("click", "div[name=esconder_pecas]", function() {
    var linha = $(this);
    linha.next("#m_peca").hide();
    linha.attr('name', 'mostrar_pecas');
    linha.html('<span class="label label-info">Mostrar peças</span>');
    $(".acoes").css({"width":"280px"});
});

$(document).on("keyup","#text_motivo",function () {
    $(this).next().html(200 - $(this).val().length);
});
</script>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <!-- DATA -->
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <!-- FIM - DATA -->

    <!-- POSTO -->
    <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
        <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='codigo_posto'>Código Posto</label>
        <div class='controls controls-row'>
            <div class='span7 input-append'>
            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=$codigo_posto?>" >
            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
            </div>
        </div>
        </div>
    </div>
    <div class='span4'>
        <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='descricao_posto'>Nome Posto</label>
        <div class='controls controls-row'>
            <div class='span12 input-append'>
            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" >&nbsp;
            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
            </div>
        </div>
        </div>
    </div>
    <div class='span2'></div>
    </div>
    <!-- FIM POSTO -->

    <!-- OS -->
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group <?=(in_array("sua_os", $msg_erro["campos"])) ? "error" : ""?>'>
            <label class='control-label' for='os'>Número OS</label>
            <div class='controls controls-row'>
                <input type="text" name="sua_os" id="sua_os" class='span4' value="<?=$sua_os?>" >
            </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group'>
            <label class='radio'>
                <input type="radio" name="status_os" value="aprovacao" <?=($status_os == 'aprovacao' OR $filtro_auditoria == '') ? "checked " : ""?> >
                Em aprovação
            </label>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group'>
            <label class='radio'>
                <input type="radio" name="status_os" value="aprovadas" <?=($status_os == 'aprovadas') ? "checked " : ""?> >
                Aprovadas
            </label>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group'>
            <label class='radio'>
                <input type="radio" name="status_os" value="reprovadas" <?=($status_os == 'reprovadas') ? "checked " : ""?> >
                Reprovadas
            </label>
            </div>
        </div>
        <div class='span1'></div>
    </div>
    <p>
        <br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p>
    <br/>
</form>
<!-- </div> -->
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
?>
<div align='left' style='position: relative; left: 25'>
    <table border='0' cellspacing='0' cellpadding='0'>
        <tr height='3'><td colspan='2'></td></tr>
        <tr height='18'>
            <td width='18' bgcolor='#FFC0CB'>&nbsp;</td>
            <td align='left'><font size='1'><b>&nbsp;
            Reincidências de Devolução de Peças
            </b></font></td>
        </tr>
        <tr height='3'><td colspan='2'></td></tr>
    </table>
</div>
<br />
<table id="resultado_auditoria_geral" class='table table-bordered table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th>OS</th>
            <th>Abertura</th>
            <th>Auditoria</th>
<?php
    if ($status_os <> 'aprovacao') {
?>
            <th>Fechamento</th>
<?php
    }
?>
            <th>Posto</th>
            <th>Peças</th>
<?php
    if ($status_os == "aprovacao") {
?>
            <th class='acoes'>Ações</th>
<?php
    }
?>
        </tr>
    </thead>
    <tbody>
<?php
        $count = 0;
        while ($results = pg_fetch_object($resSubmit)) {

?>
        <tr id='linha_<?=$results->os?>' class='_linha_<?=$results->os?>' <?=($results->os_reincidente == 't') ? "style='background-color:#FFC0CB;'" : ""?>>
            <td class="tac"><a href="os_press.php?os=<?=$results->os?>" target="_blank"><?=$results->codigo_posto.$results->sua_os?></a></td>
            <td class="tac"><?=$results->data_abertura?></td>
            <td class="tac"><?=$results->data_auditoria?></td>
<?php
            if ($status_os <> 'aprovacao') {
?>
            <td class="tar"><?=$results->data_fechamento?></td>

<?php
            }
?>
            <td class="tac"><?=$results->codigo_posto." - ".$results->posto_nome?></td>
            <td>
<?php
            $sql_peca = "
                SELECT  tbl_os_item.os_item                 ,
                        tbl_os_item.qtde                   ,
                        tbl_peca.referencia AS referencia   ,
                        tbl_peca.descricao  AS descricao    ,
                        tbl_peca.peca       AS peca
                FROM    tbl_os_produto
                JOIN    tbl_os_item USING (os_produto)
                JOIN    tbl_peca    USING (peca)
                WHERE   tbl_os_produto.os = ".$results->os;
            $res_peca = pg_query($con, $sql_peca);
?>
                <div name='mostrar_pecas' rel='<?=$results->os?>' style='width: 100%; text-align: center; cursor: pointer;'><span class='label label-info'> Mostrar peças</span></div>
                <table style='display:none;' style='width:385px;' id='m_peca' class='table table-bordered'>
                    <thead>
                        <tr class='titulo_coluna'>
                            <th>Nome</th>
                            <th>Qtde</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
            while($pecas = pg_fetch_object($res_peca)) {
?>
                        <tr>
                            <td class='peca'><?=$pecas->referencia." - ".$pecas->descricao?></td>
                            <td class='peca'> <?=$pecas->qtde?></td>
                        </tr>
<?php
            }
?>
                    </tbody>
                </table>
            </td>
<?php
            if ($status_os == 'aprovacao') {
?>
            <td class="tac">
                <button type='button' name='aprovar_os' class='btn btn-small btn-success' rel='<?=$results->os?>' title='Aprovar OS' >Aprovar</button>
                <button type='button' name='recusar_os' class='btn btn-small btn-danger'  rel='<?=$results->os?>'  >Recusar</button>
            </td>
<?php
            }
?>
        </tr>
<?php
            $count++;
        }
?>
    </tbody>
</table>
<div id="DivMotivo" style="display: none;" >
    <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
    <div class="conteudo" >
        <div class="titulo_tabela" >Informe o Motivo</div>
            <div class="row-fluid">
                <div class="span12">
                    <div class="controls controls-row">
                        <textarea name="text_motivo" id="text_motivo" class="span12" maxlength="200"></textarea>
                        <label style="margin-top: -9px;margin-bottom: -21px;color: darkgrey" id="contador">200</label>
                    </div>
                </div>
            </div>
            <p><br />
            <button type="button" id = "button_motivo" name="button_motivo" class="btn btn-block btn-success" rel="__OsAcao__" >Gravar</button>
            <p><br />
        </div>
    </div>
</div>
<?php
    } else{
?>
<div class="container">
    <div class="alert">
        <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?php
    }
}

include "rodape.php";
?>
