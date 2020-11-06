<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

include_once "../class/tdocs.class.php";

$tDocs       = new TDocs($con, $login_fabrica);

if ($_POST['reprova_os_termo'] && $_POST['os'] != "") {
    $os            = $_POST['os'];
    $justificativa = $_POST['justificativa'];
    $msg_erro      = "";

    $sql_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
    $res_campos_adicionais = pg_query($con, $sql_campos_adicionais);
    if (pg_num_rows($res_campos_adicionais) > 0) {
        $campos_adicionais = json_decode(pg_fetch_result($res_campos_adicionais, 0, 'campos_adicionais'), true);
        unset($campos_adicionais['termo_retirada_produto']);
        unset($campos_adicionais['termo_entrega_produto']);

        $campos_adicionais = "'".json_encode($campos_adicionais)."'";

        $sql_abre_os = "UPDATE tbl_os SET data_fechamento = null, finalizada = null WHERE os = $os AND fabrica = $login_fabrica";
        $res_abre_os = pg_query($con, $sql_abre_os);
        if (pg_last_error()) {
            $msg_erro = 'erro';
        }

        $sql_limpa_termo = "UPDATE tbl_os_campo_extra SET campos_adicionais = $campos_adicionais WHERE os = $os AND fabrica = $login_fabrica";
        $res_limpa_termo = pg_query($con, $sql_limpa_termo);
        if (pg_last_error()) {
            $msg_erro = 'erro';
        }

        $sql_limpa_anexo = "UPDATE tbl_tdocs 
                            SET situacao = 'inativo' 
                            WHERE referencia_id = $os 
                            AND fabrica = $login_fabrica 
                            AND situacao = 'ativo' 
                            AND (
                                 JSON_FIELD('typeId', obs) = 'termo_entrega' 
                                 OR 
                                 JSON_FIELD('termo_devolucao', obs) = 'ok'
                                 OR 
                                 JSON_FIELD('termo_entrega', obs) = 'ok'
                                )";
        $res_limpa_anexo = pg_query($con, $sql_limpa_anexo);
        if (pg_last_error()) {
            $msg_erro = 'erro';
        }

        $sql_reprova = "
                UPDATE tbl_auditoria_os SET
                    reprovada = current_timestamp,
                    justificativa = '$justificativa',
                    admin = $login_admin
                WHERE os = $os
            ";
        $res_reprova = pg_query($con, $sql_reprova);
        if (pg_last_error()) {
            $msg_erro = 'erro';
        }

        if (empty($msg_erro)) {
            $mensagem = "Prezada AT, a OS $os, foi auditada com relação aos TERMOS de Entrega e Retirada e no\rmomento encontra-se inconsistente para aprovação. Favor regularizar. Em caso de dúvidas entrar\rem contato 0800.718.7825.";

            $os_termo = array("os_termo" => $os);
            $os_termo = "'".json_encode($os_termo)."'";

            $sql_cod_posto = "SELECT posto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
            $res_cod_posto = pg_query($con, $sql_cod_posto);
            $posto = pg_fetch_result($res_cod_posto, 0, 'posto');

            $sqlComunicado = "INSERT INTO tbl_comunicado (
                                                            ativo,
                                                            mensagem,
                                                            descricao,
                                                            tipo,
                                                            fabrica,
                                                            posto,
                                                            obrigatorio_site, 
                                                            data,
                                                            parametros_adicionais
                                                            ) VALUES (
                                                            't',
                                                            '$mensagem',
                                                            'Os Reprovada em Auditoria de Termo',
                                                            'Comunicado',
                                                            $login_fabrica,
                                                            $posto,
                                                            't',
                                                            now(),
                                                            $os_termo
                                                         )";
            $resComunicado = pg_query($con, $sqlComunicado);
        }
        if (empty($msg_erro)) {
            exit('ok');
            
        } else {
            exit('erro');
        }
    }
    exit('ok');
}


if ($_POST["ajax_anexo_upload"] == true) {

    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

                $anexoID      = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
                $arquivo_nome = json_encode($tDocs->sentData);

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 

            }

            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }

            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $tdocs_id = $anexoID;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {
                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao','tdocs_id');
            }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

if ($_POST["ajax_remove_anexo"] == true) {

    $posicao    = $_POST["posicao"];
    $tdocs_id   = $_POST["tdocsid"];

    $tDocs->setContext('os','oscancela');

    $anexoID = $tDocs->deleteFileById($tdocs_id);

    if (!$anexoID) {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Erro ao remover arquivo'),'posicao' => $posicao);
    }  else {

        $retorno = array('sucesso' => true, 'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}

?>
<html>
    <head>

        <meta http-equiv=pragma content=no-cache>

        <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
        <link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script src="plugins/dataTable.js"></script>
        <script src="plugins/resize.js"></script>
        <script src='plugins/shadowbox_lupa/shadowbox.js'></script>
        <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src='plugins/jquery.form.js'></script>
        <script src='plugins/FancyZoom/FancyZoom.js'></script>
        <script src='plugins/FancyZoom/FancyZoomHTML.js'></script>
        <style>
            .esconder_anexos {
                display: none;
            }

            .danexo > .box-uploader-anexos {
                display: none;
            }

        </style>

        <script>
            $(function(){

                $("#btn-close-modal-cancela-os").click(function(){

                    window.parent.Shadowbox.close();

                });

                $("#btn-cancelar-os").click(function(){

                    let fab = '<?=$login_fabrica?>';
                    let justificativa = $("#input-cancelar-os-justificativa").val();
                    let os            = $("#os_cancela").val();
                    let erro          = false;
                    let anexo_cancela = $("#anexo_cancela").val();
                    let zerar_mo      = "nao";

                    if (os === undefined || os == "") {
                        erro = true;
                        alert("OS não encontrada");
                    }

                    if ((fab == 131) && (anexo_cancela === undefined || anexo_cancela =="")) {
                        erro = true;
                        alert("Favor, anexar o arquivo");
                    }

                    if ((fab != 131) && (justificativa === undefined || justificativa == "")) {
                        erro = true;
                        alert("Favor, informe o motivo");       
                    }

                    if (!erro) {

                        if ($("#zerar-mo").is(":checked")) {
                            zerar_mo = "sim";
                        } 

                        if (fab != 131) {
                            $.ajax({
                                    url: "cancela_os_auditoria.php",
                                    type: "POST",
                                    data: {
                                        reprova_os_termo: true,
                                        os: os,
                                        justificativa: justificativa
                                    },
                                    beforeSend: function() {
                                        $("#btn-cancelar-os").prop({ disabled: true }).text("Cancelando...");
                                    },
                                    async: false,
                                    timeout: 10000
                                }).fail(function(res) {
                                    alert("ocorreu um erro ao cancelar a OS");
                                    window.parent.Shadowbox.close();
                                }).done(function(res) {
                                    if (res == 'ok') {
                                        $("#"+os, window.parent.document).hide();
                                        alert("OS "+os+" reprovada com sucesso!");
                                        window.parent.Shadowbox.close();
                                    } else {
                                        alert("ocorreu um erro ao cancelar a OS");
                                        $("#btn-cancelar-os").prop({ disabled: false }).text("Cancelar");
                                    }
                                });

                        } else {

                            var data_ajax = {
                                ajax_reprova_auditoria: true,
                                os: os,
                                justificativa: justificativa,
                                anexo_cancela: anexo_cancela,
                                zerar_mo: zerar_mo
                            };

                            $.ajax({
                                url: "os_auditoria_unica.php",
                                type: "POST",
                                data: data_ajax,
                                beforeSend: function() {
                                    $("#btn-cancelar-os").prop({ disabled: true }).text("Cancelando...");
                                },
                                async: false,
                                timeout: 10000
                            }).fail(function(res) {
                                alert("ocorreu um erro ao cancelar a OS");
                                window.parent.Shadowbox.close();
                            }).done(function(res) {

                                res = JSON.parse(res);

                                if (res.sucesso) {
                                    $("#"+os, window.parent.document).hide();
                                    alert("OS "+os+" reprovada com sucesso!");
                                    window.parent.Shadowbox.close();
                                } else {
                                    alert("ocorreu um erro ao cancelar a OS");
                                    $("#btn-cancelar-os").prop({ disabled: false }).text("Cancelar");
                                }

                            });
                        }
                    }
                });

                $("div[id^=div_anexo_]").each(function(i) {
                    var tdocs_id = $("#div_anexo_"+i).find(".btn-remover-anexo").data("tdocsid");
                    if (tdocs_id != '' && tdocs_id != null && tdocs_id != undefined) {
                        $("#div_anexo_"+i).find("button[name=anexar]").hide();
                        $("#div_anexo_"+i).find(".btn-remover-anexo").show();
                    } else {
                        $("#div_anexo_"+i).find(".btn-remover-anexo").hide();
                    }
                });

                /* REMOVE DE FOTOS */
                $(document).on("click", ".btn-remover-anexo", function () {
                    var tdocsid = $(this).data("tdocsid");
                    var posicao = $(this).data("posicao");

                    if (tdocsid != '' && tdocsid != null && tdocsid != undefined) {

                        $.ajax({
                            url: window.location,
                            type: "POST",
                            dataType:"JSON",
                            data: { 
                                ajax_remove_anexo: true,
                                tdocsid: tdocsid,
                                posicao: posicao
                            }
                        }).done(function(data) {
                            if (data.erro == true) {
                                alert(data.msg);
                                return false;
                            } else {
                                alert("Removido com sucesso.");
                                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                                $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                                $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").hide();
                                $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", "");
                                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val("");
                                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").attr("src", "imagens/imagem_upload.png");
                                $("input[name^=anexo_upload_]").val("");
                            }
                        });

                    }

                });

                /* ANEXO DE FOTOS */
                $("input[name^=anexo_upload_]").change(function() {
                    var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

                    $("#div_anexo_"+i).find("button[name=anexar]").hide();
                    $("#div_anexo_"+i).find("img.anexo_thumb").hide();
                    $("#div_anexo_"+i).find("img.anexo_loading").show();

                    $(this).parent("form").submit();
                });

                $("button[name=anexar]").click(function() {
                    var posicao = $(this).attr("rel");
                    $("input[name=anexo_upload_"+posicao+"]").click();
                });

                $("form[name=form_anexo]").ajaxForm({
                    complete: function(data) {
                        data = $.parseJSON(data.responseText);
                        
                        if (data.error) {
                            alert(data.error);
                            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                            $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
                        } else {
                            var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();

                            if (data.ext == 'pdf') {
                                $(imagem).attr({ src: "imagens/pdf_icone.png" });
                            } else if (data.ext == "doc" || data.ext == "docx") {
                                $(imagem).attr({ src: "imagens/docx_icone.png" });
                            } else {
                                $(imagem).attr({ src: data.link });
                            }
                            
                            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                            var link = $("<a></a>", {
                                href: data.href,
                                target: "_blank"
                            });

                            $(link).html(imagem);

                            $("#div_anexo_"+data.posicao).prepend(link);

                            setupZoom();

                            $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                        }

                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+data.posicao).find("button[name=anexar]").hide();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").show();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", data.tdocs_id);
                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
                    }
                /* FIM ANEXO DE FOTOS */
                });

            });

        </script>
    </head>
    <body>
        <div class="modal-header">
        </div>
        <div class="modal-body" style="height: 70%;">
            <?php if (in_array($login_fabrica, [123,160]) or $replica_einhell) { ?>
                    <div class="alert alert-info"><strong>Para cancelar a OS é obrigatório informar o motivo do cancelamento</strong></div>
            <?php } else { ?>
                    <div class="alert alert-info"><strong>Para reprovar a OS é obrigatório informar o motivo e anexar o Laudo de recusa</strong></div>
            <?php } ?>

            <form>
                <fieldset>
                    <label>Motivo</label>
                    <input type="text" id="input-cancelar-os-justificativa" maxlength="200" style="width: 98%;" value="" /> 
                    <input type="hidden" value="<?= $_GET['os'] ?>" id="os_cancela" />
                    <?php if (!in_array($login_fabrica, [123,160]) and !$replica_einhell) { ?>
                            <br />
                            <label>
                                <input type="checkbox" id="zerar-mo" value="t" /> Zerar mão de obra
                            </label>
                    <?php } ?>
                </fieldset>
            </form>
                <?php
                if (!in_array($login_fabrica, [123,160]) and !$replica_einhell) {
                    for ($i=1; $i <= 1 ; $i++) {

                        $imagemAnexo = "imagens/imagem_upload.png";
                        $linkAnexo   = "#";
                        $tdocs_id   = "";

                        ?>
                        <center>
                            <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                                <?php if ($linkAnexo != "#") { ?>
                                <a href="<?=$linkAnexo?>" target="_blank" >
                                <?php } ?>
                                    <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                                <?php if ($linkAnexo != "#") { ?>
                                </a>

                                <script>setupZoom();</script>
                                <?php } ?>
                                <button type="button" style="display: none;" class="btn btn-mini btn-remover-anexo btn-danger btn-block" data-tdocsid="<?=$tdocs_id?>" data-posicao="<?=$i?>" >Remover</button>
                                <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar Laudo</button>
                                <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                                <input type="hidden" rel="anexo" id="anexo_cancela" name="anexo[<?=$i?>]" value="<?=$anexo[$i]?>" />
                            </div>
                        </center>
                        <br />
                    <?php 
                    }
                } ?>
        </div>
        <div class="modal-footer" style="height: 10%;">
            <button type="button" id="btn-close-modal-cancela-os" class="btn">Fechar</button>
            <button type="button" id="btn-cancelar-os" class="btn btn-danger">Reprovar OS</button>
        </div>
        <?php
        for ($i = 1; $i <=  1; $i++) { ?>
            <form name="form_anexo" method="post" action="cancela_os_auditoria.php" enctype="multipart/form-data" style="display: none !important;" >
                <input type="file" name="anexo_upload_<?=$i?>" value="" />
                <input type="hidden" name="ajax_anexo_upload" value="t" />
                <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
                <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
            </form>
        <?php 
        } ?>
    </body>
</html>
