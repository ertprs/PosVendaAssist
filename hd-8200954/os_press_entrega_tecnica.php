<?php
error_reporting(E_ALL);

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include __DIR__.'/dbconfig.php';
    include __DIR__.'/includes/dbconnect-inc.php';
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/dbconfig.php';
    include __DIR__.'/includes/dbconnect-inc.php';
    include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';
include_once S3CLASS;
 $s3 = new AmazonTC("os", $login_fabrica);


if ($_POST["excluir_anexo"]) {
    $anexo = $_POST["anexo"];
    $ano   = $_POST["ano"];
    $mes   = $_POST["mes"];

    if (!empty($anexo) && !empty($ano) && !empty($mes)) {

        $retorno = $s3->deleteObject($anexo, null, $ano, $mes);
        $retorno = array("ok" => true);
    } else {
        $retorno = array("error" => utf8_encode("Anexo não informado"));
    }

    exit(json_encode($retorno));
}

if ($_POST["ajax_anexo_upload"]) {
    $s3 = new AmazonTC("os", $login_fabrica);

    $os      = $_POST['os'];
    $posicao = $_POST['posicao'];
    $ano     = $_POST['ano'];
    $mes     = $_POST['mes'];
    $file    = $_FILES["anexo_upload_".$posicao];

    $ext = strtolower(preg_replace('/.+\./', '', $file['name']));

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($file['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, pdf, doc, docx'));
        } else {
            $arquivo_nome = "{$os}_{$posicao}";

            $s3->upload($arquivo_nome, $file, $ano, $mes);

            if($ext == "pdf"){
                $thumb = "imagens/pdf_icone.png";
            }else if(in_array($ext, array("doc", "docx"))){
                $thumb = "imagens/docx_icone.png";
            }else{
                $thumb = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", false, $ano, $mes);
            }

            $full  = $s3->getLink("{$arquivo_nome}.{$ext}", false, $ano, $mes);

            if (!strlen($full) && !strlen($thumb)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("full" => $full, "thumb" => $thumb, "posicao" => $posicao);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    exit(json_encode($retorno));
}



$layout_menu = "callcenter";
$title       = "CONFIRMAÇÃO DE ORDEM DE SERVIÇO - ENTREGA TÉCNICA";

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

// 'nome' único de arquivo para trabalhar com anexos
if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}

$os = $_GET['os'];

if (strlen ($os) > 0) {

    // HD31887
    $sql = "SELECT  tbl_os.sua_os                                                               ,
                    tbl_os.sua_os_offline                                                       ,
                    tbl_admin.login                              AS admin                       ,
                    troca_admin.login                            AS troca_admin       ,
                    to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao              ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura               ,
                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento             ,
                    to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS data_finalizada                  ,
                    to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida               ,
                    tbl_os.tipo_atendimento                                                     ,
                    tbl_tipo_atendimento.descricao                 AS nome_atendimento          ,
                    tbl_tipo_atendimento.codigo                    AS codigo_atendimento        ,
                    tbl_os.consumidor_nome                                                      ,
                    tbl_os.consumidor_fone                                                      ,
                    tbl_os.consumidor_celular                                                   ,
                    tbl_os.consumidor_fone_comercial                                            ,
                    tbl_os.consumidor_fone_recado                                               ,
                    tbl_os.consumidor_endereco                                                  ,
                    tbl_os.consumidor_numero                                                    ,
                    tbl_os.consumidor_complemento                                               ,
                    tbl_os.consumidor_bairro                                                    ,
                    tbl_os.consumidor_cep                                                       ,
                    tbl_os.consumidor_cidade                                                    ,
                    tbl_os.consumidor_estado                                                    ,
                    tbl_os.consumidor_cpf                                                       ,
                    tbl_os.consumidor_email                                                     ,
                    tbl_os.nota_fiscal                                                          ,
                    tbl_os.nota_fiscal_saida                                                    ,
                    tbl_os.cliente                                                              ,
                    tbl_os.revenda                                      ,
            tbl_os.revenda_nome                             ,
            tbl_os.revenda_cnpj                                 ,
            tbl_os.revenda_fone                                 ,
                    tbl_os.rg_produto                                                           ,
                    tbl_os.defeito_reclamado_descricao       AS defeito_reclamado_descricao_os  ,
                    tbl_marca.marca                                                             ,
                    tbl_marca.nome as marca_nome                                                ,
                    tbl_os.qtde_produtos as qtde                                                ,
                    tbl_os.tipo_os                                                              ,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf                     ,
                    tbl_defeito_reclamado.defeito_reclamado      AS defeito_reclamado           ,
                    tbl_defeito_reclamado.descricao              AS defeito_reclamado_descricao ,
                    tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
                    tbl_defeito_constatado.defeito_constatado    AS defeito_constatado          ,
                    tbl_defeito_constatado.descricao             AS defeito_constatado_descricao,
                    tbl_defeito_constatado.codigo                AS defeito_constatado_codigo   ,
                    tbl_causa_defeito.causa_defeito              AS causa_defeito               ,
                    tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
                    tbl_causa_defeito.codigo                     AS causa_defeito_codigo        ,
                    tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo,
                    tbl_motivo_reincidencia.descricao            AS motivo_reincidencia_desc    ,
                    tbl_os.obs_reincidencia                                                     ,
                    tbl_os.aparencia_produto                                                    ,
                    tbl_os.acessorios                                                           ,
                    tbl_os.consumidor_revenda                                                   ,
                    tbl_os.obs                                                                  ,
                    tbl_os.qtde_diaria,
                    tbl_os.observacao                                                           ,
                    tbl_os.excluida                                                             ,
                    tbl_produto.produto                                                         ,
                    tbl_produto.referencia                                                      ,
                    tbl_produto.referencia_fabrica               AS modelo                      ,
                    tbl_produto.descricao                                                       ,
                    tbl_produto.voltagem                                                        ,
                    tbl_produto.valor_troca                                                     ,
                    tbl_produto.troca_obrigatoria                                               ,
                    tbl_os.qtde_produtos                                                        ,
                    tbl_os.serie                                                                ,
                    tbl_os.codigo_fabricacao                                                    ,
                    tbl_posto_fabrica.codigo_posto               AS codigo_posto                ,
                    tbl_posto.nome                               AS nome_posto                  ,
                    tbl_os.ressarcimento                                                        ,
                    tbl_os.certificado_garantia                                                 ,
                    tbl_os_extra.os_reincidente                                                 ,
                    tbl_os_extra.recolhimento,
                    tbl_os_extra.orientacao_sac                                                 ,
                    tbl_os_extra.reoperacao_gas                                                 ,
                    tbl_os_extra.obs_nf                                                         ,
                    tbl_os_extra.recomendacoes                                                          ,
                    tbl_os.solucao_os                                                           ,
                    tbl_os.posto                                                                ,
                    tbl_os.promotor_treinamento                                                 ,
                    tbl_os.fisica_juridica                                                      ,
                    tbl_os.troca_garantia                                                       ,
                    tbl_os.troca_garantia_admin                                                 ,
                    tbl_os.troca_faturada                                                       ,
                    tbl_os_extra.tipo_troca                                                     ,
                    tbl_os_extra.serie_justificativa                                            ,
                    tbl_os_extra.qtde_horas                                                     ,
                    tbl_os_extra.obs_adicionais                                                 ,
                    tbl_os_extra.pac AS codigo_rastreio                                                 ,
                    tbl_os.os_posto                                                             ,
                    to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI') as data_ressarcimento       ,
                    serie_reoperado                                                             ,
                    tbl_extrato.extrato                                                         ,
                    to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_previsao,
                    to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_pagamento,
                    tbl_os.fabricacao_produto                                                   ,
                    tbl_os.qtde_km                                                              ,
                    tbl_os.valores_adicionais                                               ,
                    tbl_os.os_numero,
                    tbl_os.cortesia                                                             ,
                    tbl_linha.nome AS nome_linha,
                    tbl_os.nf_os,
                    tbl_os.qtde_hora,
                    tbl_os.hora_tecnica as hora_tecnica
            FROM       tbl_os
            JOIN       tbl_posto              ON tbl_posto.posto                       = tbl_os.posto
            JOIN       tbl_posto_fabrica      ON tbl_posto_fabrica.posto               = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN       tbl_motivo_reincidencia ON tbl_os.motivo_reincidencia           = tbl_motivo_reincidencia.motivo_reincidencia
            LEFT JOIN  tbl_os_extra           ON tbl_os.os                             = tbl_os_extra.os
            LEFT JOIN  tbl_extrato            ON tbl_extrato.extrato                   = tbl_os_extra.extrato AND tbl_extrato.fabrica = {$login_fabrica}
            LEFT JOIN  tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato        = tbl_extrato.extrato
            LEFT JOIN  tbl_admin              ON tbl_os.admin                          = tbl_admin.admin
            LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
            LEFT JOIN  tbl_defeito_reclamado  ON tbl_os.defeito_reclamado              = tbl_defeito_reclamado.defeito_reclamado
            LEFT JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado             = tbl_defeito_constatado.defeito_constatado
            LEFT JOIN  tbl_causa_defeito      ON tbl_os.causa_defeito                  = tbl_causa_defeito.causa_defeito
            LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
            LEFT JOIN  tbl_produto            ON tbl_os.produto                        = tbl_produto.produto
            LEFT JOIN  tbl_tipo_atendimento   ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
            LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
            LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
            WHERE   tbl_os.os = {$os}
             AND tbl_os.fabrica = {$login_fabrica}";
    $res = pg_query($con,$sql);
}

$plugins = array(
   "shadowbox",
   "ajaxform"
);


include __DIR__."/admin/plugin_loader.php";

?>
<script>

    // function showHideGMap() {
    //     var gMapDiv = $('#gmaps');
    //     var newh    = (gMapDiv.css('height')=='5px') ? '486px' : '5px';
    //     gMapDiv.animate({height: newh}, 400);
    //     if (newh=='5px') gMapDiv.parent('td').css('height', '2em');
    //     if (newh!='5px') gMapDiv.parent('td').css('height', 'auto');
    // }

    // function excluirComentario(os,os_status){

    //     if (confirm('Deseja alterar este comentário?')){
    //         var justificativa = prompt('Informe a nova justificativo. É Opcional.', '');
    //         if (justificativa==null){
    //             return;
    //         }else{
    //             window.location = "<?=$PHP_SELF?>?os="+os+"&apagarJustificativa="+os_status+"&justificativa="+justificativa;
    //         }
    //     }
    // }


    $(function() {

        /**
        * Eventos para anexar imagem
        */
         $("form[name=form_anexo]").ajaxForm({
                complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });

                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo_"+data.posicao).prepend(link);

                    if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                        setupZoom();
                    }

                    $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            }
         });

        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");
            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });



        $("button[name=excluir_anexo]").click(function() {
            if (confirm("Deseja realmente excluir o anexo?")) {
                var div  = $(this).parent("div");

                var anexo = $(div).find("input[name=anexo]").val();
                var ano   = $(div).find("input[name=ano]").val();
                var mes   = $(div).find("input[name=mes]").val();
               // console.log(anexo,ano,mes);return;
                $.ajax({
                    url: "os_press_entrega_tecnica.php",
                    type: "post",
                    data: { excluir_anexo: true, anexo: anexo, ano: ano, mes: mes },
                    complete: function(data) {
                        data = $.parseJSON(data.responseText);

                        if (data.error) {
                            alert(data.error);
                        } else {
                            $(div).remove();
                        }
                    }
                });
            }
        });

    });

</script>
<?

if (pg_num_rows($res) > 0) {

    $sua_os                 = pg_fetch_result($res, 0, "sua_os");
    $data_abertura          = pg_fetch_result($res, 0, "data_abertura");
    $data_digitacao         = pg_fetch_result($res, 0, "data_digitacao");
    $data_fechamento        = pg_fetch_result($res, 0, "data_fechamento");
    $data_finalizada        = pg_fetch_result($res, 0, "data_finalizada");
    $consumidor_nome        = pg_fetch_result($res, 0, "consumidor_nome");
    $consumidor_fone        = pg_fetch_result($res, 0, "consumidor_fone");
    $consumidor_endereco    = pg_fetch_result($res, 0, "consumidor_endereco");
    $consumidor_numero      = pg_fetch_result($res, 0, "consumidor_numero");
    $consumidor_complemento = pg_fetch_result($res, 0, "consumidor_complemento");
    $consumidor_bairro      = pg_fetch_result($res, 0, "consumidor_bairro");
    $consumidor_cep         = pg_fetch_result($res, 0, "consumidor_cep");
    $consumidor_cidade      = pg_fetch_result($res, 0, "consumidor_cidade");
    $consumidor_estado      = pg_fetch_result($res, 0, "consumidor_estado");
    $consumidor_cpf         = pg_fetch_result($res, 0, "consumidor_cpf");
    $consumidor_email       = pg_fetch_result($res, 0, "consumidor_email");
    $codigo_posto           = pg_fetch_result($res, 0, "codigo_posto");
    $nome_posto             = pg_fetch_result($res, 0, "nome_posto");
    $nota_fiscal            = pg_fetch_result($res, 0, "nota_fiscal");
    $qtde_km                = pg_fetch_result($res, 0, "qtde_km");
    $data_nf                = pg_fetch_result($res, 0, "data_nf");
    $hora_tecnica           = pg_fetch_result($res, 0, "hora_tecnica");
    $qtde_deslocamento      = pg_fetch_result($res, 0, "qtde_hora");
    $observacao             = pg_fetch_result($res, 0, "observacao");
    if (!isset($observacao)) {
        $observacao = pg_fetch_result($res, 0, "obs");
    }


    //--==== INFORMACOES DA REVENDA ====================================================
    $revenda                     = pg_fetch_result ($res,0,"revenda");

        $sql = "SELECT      tbl_revenda.nome              ,
                            tbl_revenda.cnpj              ,
                            tbl_revenda.cidade            ,
                            tbl_revenda.fone              ,
                            tbl_revenda.endereco          ,
                            tbl_revenda.numero            ,
                            tbl_revenda.complemento       ,
                            tbl_revenda.bairro            ,
                            tbl_revenda.cep               ,
                            tbl_revenda.email             ,
                            tbl_cidade.nome AS nome_cidade,
                            tbl_cidade.estado
                FROM        tbl_revenda
                LEFT JOIN   tbl_cidade USING (cidade)
                LEFT JOIN   tbl_estado using(estado)
                WHERE       tbl_revenda.revenda = {$revenda}";

        $res_revenda = pg_query ($con,$sql);

        if (pg_num_rows ($res_revenda) > 0) {
            $revenda_nome       = trim(pg_fetch_result($res_revenda,0,"nome"));
            $revenda_cnpj       = trim(pg_fetch_result($res_revenda,0,"cnpj"));
            $revenda_bairro     = trim(pg_fetch_result($res_revenda,0,"bairro"));
            $revenda_cidade     = trim(pg_fetch_result($res_revenda,0,"nome_cidade"));
            $revenda_estado     = trim(pg_fetch_result($res_revenda,0,"estado"));
            $revenda_cep        = trim(pg_fetch_result($res_revenda,0,"cep"));
            $revenda_email      = trim(pg_fetch_result($res_revenda,0,"email"));
            $revenda_endereco       = trim(pg_fetch_result($res_revenda,0,"endereco"));
            $revenda_numero     = trim(pg_fetch_result($res_revenda,0,"numero"));
            $revenda_complemento        = trim(pg_fetch_result($res_revenda,0,"complemento"));
            $revenda_fone       = trim(pg_fetch_result($res_revenda,0,"fone"));
        }



    $sql_qtde_dias = "SELECT data_fechamento - data_abertura AS dias FROM tbl_os WHERE os = $os";
    $res_qtde_dias = pg_query ($con, $sql_qtde_dias);

    if(pg_num_rows($res_qtde_dias)){
        $qtde_dias = pg_fetch_result($res_qtde_dias, 0, "dias");
    }

    if (strlen($data_fechamento) > 0) {
        if($qtde_dias == 0) {
            $fechamento_em = "No mesmo dia";
        }else if($qtde_dias == 1){
            $fechamento_em = $qtde_dias." dia";
        }else if($qtde_dias > 1){
            $fechamento_em = $qtde_dias." dias";
        }else{
            $fechamento_em = "OS Aberta";
        }
    }
    ?>

    <br />

    <table align="center" id="resultado_os" class='table table-bordered table-large' >
        <tr>
            <td class='titulo_tabela tac' colspan='100%'>Informações da OS</td>
        </tr>
        <tr>
            <td class="tac" style="color: orange; vertical-align: middle;" rowspan="6"><h2><?=$sua_os?></h2></td>
            <td class='titulo_coluna' width="100">Data Abertura</td>
            <td><?=$data_abertura?></td>
            <td class='titulo_coluna' width="100">Data Digitação</td>
            <td><?=$data_digitacao?></td>
        </tr>
        <tr>
            <td class='titulo_coluna' width="100">Data Fechamento</td>
            <td><?=$data_fechamento?></td>
            <td class='titulo_coluna' width="100">Data Finalizada</td>
            <td><?=$data_finalizada?></td>
        </tr>
        <tr>
            <td class='titulo_coluna' width="100">Fechado em</td>
            <td colspan="3" ><?=$fechamento_em?></td>
        </tr>
        <tr>
            <td class='titulo_coluna' width="100">Nota Fiscal</td>
            <td><?=$nota_fiscal?></td>
            <td class='titulo_coluna' width="100">Data da NF</td>
            <td><?=$data_nf?></td>
        </tr>
        <tr>
            <td class='titulo_coluna' width="100"> Qtde KM</td>
            <td><?=$qtde_km?></td>
            <td class='titulo_coluna' width="100"> Hora técnica em minutos</td>
            <td colspan="3" ><?=$hora_tecnica?></td>
        </tr>
        <tr>
            <td class='titulo_coluna' width="100"> Tempo de Deslocamento em horas</td>
            <td><?=$qtde_deslocamento?></td>
        </tr>
    </table>
            <?php
            if($areaAdmin === true){
            ?>

            <table align="center" id="resultado_os" class='table table-bordered table-large' >

                <tr>
                    <td class='titulo_tabela tac' colspan='100%'>Informações do Posto</td>
                </tr>

                <tr>
                    <td class='titulo_coluna'>Código</td>
                    <td nowrap><?=$codigo_posto?></td>
                    <td class='titulo_coluna'>Nome</td>
                    <td nowrap><?=$nome_posto?></td>
                </tr>
            </table>

            <?php } ?>
    <table align="center" id="resultado_os" class='table table-bordered table-large' >

        <tr>
            <td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Nome</td>
            <td nowrap><?=$consumidor_nome?></td>
            <td class='titulo_coluna'>CPF</td>
            <td><?=$consumidor_cpf?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Telefone</td>
            <td nowrap><?=$consumidor_fone?></td>
            <td class='titulo_coluna'>Email</td>
            <td><?=$consumidor_email?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Endereço</td>
            <td nowrap><?=$consumidor_endereco?></td>
            <td class='titulo_coluna'>Número</td>
            <td><?=$consumidor_numero?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Complemento</td>
            <td nowrap><?=$consumidor_complemento?></td>
            <td class='titulo_coluna'>Bairro</td>
            <td><?=$consumidor_bairro?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Cidade</td>
            <td nowrap><?=$consumidor_cidade?></td>
            <td class='titulo_coluna'>Estado</td>
            <td><?=$consumidor_estado?></td>
        </tr>

    </table>

    <table align="center" id="resultado_os" class='table table-bordered table-large' >
        <tr>
            <td class='titulo_tabela tac' colspan='100%'>Informações da Revenda</td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Nome</td>
            <td nowrap><?=$revenda_nome?></td>
            <td class='titulo_coluna'>CNPJ</td>
            <td><?=$revenda_cnpj?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>CEP</td>
            <td nowrap><?=$revenda_cep?></td>
            <td class='titulo_coluna'>Estado</td>
            <td><?=$revenda_estado?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Cidade</td>
            <td nowrap><?=$revenda_cidade?></td>
            <td class='titulo_coluna'>Bairro</td>
            <td><?=$revenda_bairro?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Endereço</td>
            <td nowrap><?=$revenda_endereco?></td>
            <td class='titulo_coluna'>Número</td>
            <td><?=$revenda_numero?></td>
        </tr>

        <tr>
            <td class='titulo_coluna'>Complemento</td>
            <td nowrap><?=$revenda_complemento?></td>
            <td class='titulo_coluna'>Telefone</td>
            <td><?=$revenda_fone?></td>
        </tr>

    </table>



    <table align="center" id="resultado_os" class='table table-bordered table-large' >

        <tr>
            <td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
        </tr>

        <?php

        $sql_produto = "SELECT  tbl_produto.referencia,
                            tbl_produto.descricao,
                            tbl_os_produto.capacidade AS qtde_produto
                        FROM tbl_os_produto
                        JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
                        WHERE tbl_os_produto.os = {$os}";
        $res_produto = pg_query($con, $sql_produto);

        if(pg_num_rows($res_produto)){

            $total_produtos = pg_num_rows($res_produto);

            $total_quantidade = 0;

            for($i = 0; $i < $total_produtos; $i++){

                $referencia     = pg_fetch_result($res_produto, $i, "referencia");
                $descricao      = pg_fetch_result($res_produto, $i, "descricao");
                $qtde_produto   = pg_fetch_result($res_produto, $i, "qtde_produto");

                $total_quantidade += $qtde_produto;

                ?>

                <tr>
                    <td class='titulo_coluna'>Produto</td>
                    <td ><?=$referencia." - ".$descricao?></td>
                    <td class='titulo_coluna' nowrap>Quantidade</td>
                    <td width="100" class="tac"><?=$qtde_produto?></td>
                </tr>

                <?php

            }

        }

        ?>

    </table>


                <?php
                        $sqlCustoAdicional = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND valores_adicionais notnull";
                        $resCustoAdicional = pg_query($con,$sqlCustoAdicional);

                        if(pg_num_rows($resCustoAdicional) > 0){

                            $custos_adicionais = pg_fetch_result($resCustoAdicional,0,'valores_adicionais');
                            $custos_adicionais = json_decode($custos_adicionais,true);
                    ?>
                            <br />
                            <table align="center" id="resultado_os" class='table table-bordered table-large' >
                               <tr>
                                    <td class='titulo_tabela tac' colspan='100%'>Valores Adicionais</td>
                                </tr>

                    <?php
                            $i = 0;
                            foreach ($custos_adicionais as $key => $value) {
                                foreach ($value as $chave => $valor) {
                                    $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                    ?>
                                    <tr >
                                        <td class='titulo_coluna' width="25%"> <?=utf8_decode($chave)?> </td>
                                        <?php
                                            if($login_fabrica <> 125){
                                        ?>
                                            <td width="25%"> R$ <?=$valor?> </td>
                                        <?php
                                            }
                                        ?>
                                    </tr>
                    <?php
                                    $i++;
                                }
                            }
                    ?>
                            </table>
            <?php
            }
           if($auditoria_unica == true){
             ?>
                <br/>
                <TABLE align="center" id="resultado_os" class='table table-bordered table-large' >
                    <tr>
                        <td class='titulo_tabela tac' colspan='6' style="font-size:11pt; text-align: center;">Histórico de Intervenção</td>
                    </tr>
                    <tr>
                        <TD class="titulo_coluna">Data</TD>
                        <TD class="titulo_coluna">Descrição</TD>
                        <TD class="titulo_coluna">Status</TD>
                        <TD class="titulo_coluna">Paga MO</TD>
                        <TD class="titulo_coluna">Justificativa</TD>
                        <TD class="titulo_coluna">Admin</TD>
                    </tr>
                    <?php
                        $sqlAuditoria = "SELECT tbl_auditoria_status.descricao,
                                tbl_auditoria_os.observacao,
                                to_char(tbl_auditoria_os.data_input,'DD/MM/YYYY') AS data_input,
                                tbl_auditoria_os.liberada,
                                tbl_auditoria_os.cancelada,
                                tbl_auditoria_os.justificativa,
                                tbl_auditoria_os.paga_mao_obra,
                                tbl_admin.nome_completo
                            FROM tbl_auditoria_os
                                JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                                LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
                                JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                            WHERE tbl_auditoria_os.os = $os ORDER BY data_input DESC";
                        $resAuditoria = pg_query($con,$sqlAuditoria);

                        if(pg_num_rows($resAuditoria) > 0){
                            $count = pg_num_rows($resAuditoria);
                            for($i=0; $i < $count; $i++){
                                unset($liberada,$cancelada,$status_auditoria);
                                $descricao_auditoria     = (pg_fetch_result($resAuditoria, $i, "descricao"));
                                $observacao_auditoria    = (pg_fetch_result($resAuditoria, $i, "observacao"));
                                $data_auditoria          = pg_fetch_result($resAuditoria, $i, "data_input");
                                $liberada                = pg_fetch_result($resAuditoria, $i, "liberada");
                                $cancelada_auditoria     = pg_fetch_result($resAuditoria, $i, "cancelada");
                                $justificativa_auditoria = utf8_decode(pg_fetch_result($resAuditoria, $i, "justificativa"));
                                $paga_mao_obra           = pg_fetch_result($resAuditoria, $i, "paga_mao_obra");
                                $nome_auditoria          = utf8_decode(pg_fetch_result($resAuditoria, $i, "nome_completo"));

                                if($paga_mao_obra == 't'){
                                    $paga_mao_obra = "Sim";
                                }else{
                                    $paga_mao_obra = "Não";
                                }

                                if($liberada != ""){
                                    $status_auditoria = "Liberado";
                                }else if($cancelada != ""){
                                    $status_auditoria = "Cancelado";
                                }
                    ?>
                    <tr>
                        <td class="conteudo"><?=$data_auditoria?></td>
                        <td class="conteudo"><?=$descricao_auditoria?> - <?=$observacao_auditoria?></td>
                        <td class="conteudo"><?=$status_auditoria?></td>
                        <td class="conteudo"><?=$paga_mao_obra?></td>
                        <td class="conteudo"><?=$justificativa_auditoria?></td>
                        <td class="conteudo"><?=$nome_auditoria?></td>
                    </tr>
                    <?php }
                    } ?>
                </TABLE>
            <?php }
            ?>

    <br />
        <iframe id="iframe_interacao_os" src="interacao_os.php?os=<?=$os?>&iframe=true" style="width: 700px;" frameborder="0" scrolling="no" ></iframe>
    <br />

    <table align="center" id="resultado_os" class='table table-bordered table-large' >
        <tr>
            <td class='titulo_tabela tac' colspan='100%'>Observações</td>
        </tr>
        <tr>
            <td width="25%"><?=$observacao?></td>
        </tr>
    </table>


    <div id="div_anexos" class="table div-bordered table-large">
        <div class="titulo_tabela">
            Anexo(s)
        </div>
        <br />

        <div class="tac" >
        <?php

        if ($fabrica_qtde_anexos > 0) {
            if (strlen($os) > 0) {
                list($dia,$mes,$ano) = explode("/", $data_abertura);
            }

            echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

            for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                unset($anexo_link);
                $anexo_imagem = "imagens/imagem_upload.png";
                $anexo_s3     = false;
                $anexo        = "";

                if(strlen($os) > 0) {
                    $anexos = $s3->getObjectList("{$os}_{$i}.", false, $ano, $mes);
                    if (count($anexos) > 0) {

                        $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

                        if ($ext == "pdf") {
                            $anexo_imagem = "imagens/pdf_icone.png";
                        } else if (in_array($ext, array("doc", "docx"))) {
                            $anexo_imagem = "imagens/docx_icone.png";
                        } else {
                            $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
                        }

                        $anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

                        $anexo        = basename($anexos[0]);
                        $anexo_s3     = true;
                    }
                }
                ?>
                <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">

                <?php   if (isset($anexo_link)) { ?>
                        <a href="<?=$anexo_link?>" target="_blank" >
                    <?php } ?>
                            <input type='hidden' name='ano' value='<?=$ano?>' />
                            <input type='hidden' name='mes' value='<?=$mes?>' />
                            <input type='hidden' name='anexo' value='<?=$anexo?>' />
                            <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                    <?php if (isset($anexo_link) and $areaAdmin === true) { ?>
                        </a>
                        <button type="button" class="btn btn-mini btn-danger btn-block" name="excluir_anexo" >Excluir</button>
                        <script>setupZoom();</script>
                    <?php } ?>

                    <?php
                    if ($anexo_s3 === false) {
                    ?>
                        <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
                    <?php
                    }
                    ?>

                    <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                    <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                    <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                </div>
            <?php
            }
        }
        ?>
        </div>
        <br />
    </div>



    <br />

    <p class="tac">
        <a href="cadastro_os_entrega_tecnica.php">
            <button type="button" class="btn btn-primary">Lançar uma nova Ordem de Serviço - Entrega Técnica</button>
        </a>
        &nbsp; &nbsp;
        <a href="os_print_entrega_tecnica.php?os=<?=$os?>" target="_blank">
            <button type="button" class="btn">Imprimir</button>
        </a>
    </p>

    <br />

    <hr />
    <?php

    if ($fabrica_qtde_anexos > 0) {
        list($dia,$mes,$ano) = explode("/", $data_abertura);
        for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {

        ?>
            <form name="form_anexo" method="post" action="os_press_entrega_tecnica.php" enctype="multipart/form-data" style="display: none;" >
                <input type="file" name="anexo_upload_<?=$i?>" value="" />

                <input type="hidden" name="ajax_anexo_upload" value="t" />
                <input type='hidden' name='os' value='<?=$os?>' />
                <input type='hidden' name='posicao' value='<?=$i?>' />
                <input type='hidden' name='ano' value='<?=$ano?>' />
                <input type='hidden' name='mes' value='<?=$mes?>' />
            </form>
        <?php
        }
    }

}

/* Rodapé */
include 'rodape.php';

?>
