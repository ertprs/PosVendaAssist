<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include '../ajax_cabecalho.php';
    include 'funcoes.php';
    $admin_privilegios="info_tecnica,call_center";
    include 'autentica_admin.php';


    $treinamentoAjax = $_GET["treinamento"];

    if(filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)){
        $treinamento = filter_input(INPUT_POST,'treinamento_id');
        $justificativa = filter_input(INPUT_POST,'notificacao_treinamento');
        //pegar todos os postos que estão cadastrados neste treinamento e enviar e-mail.
        $sql_p = "
            SELECT  DISTINCT
                    tbl_posto_fabrica.posto,
                    tbl_treinamento.titulo
            FROM    tbl_treinamento
            JOIN    tbl_treinamento_posto USING(treinamento)
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto             = tbl_treinamento_posto.posto
                                        AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE   tbl_treinamento.treinamento = $treinamento";
//             echo $sql_p;exit;
        $res_p = pg_query($con,$sql_p);

        if (pg_num_rows($res_p) > 0) {

            $sql = "INSERT INTO tbl_comunicado (
                        mensagem,
                        tipo,
                        fabrica,
                        descricao,
                        ativo,
                        posto,
                        obrigatorio_site
                    ) VALUES ";
            $tl = pg_num_rows($res_p);

            for ($i = 0; $i < $tl; $i++) {
                $titulo = pg_fetch_result($res_p,$i,titulo);
                $posto  = pg_fetch_result($res_p,$i,posto);

                $sql .= "(
                    '".utf8_encode("Treinamento sobre $titulo foi excluído pela fábrica. Motivo: $justificativa.<br /> Entre em contato para maiores informações.")."',
                    'Com. Unico Posto',
                    $login_fabrica,
                    '".utf8_encode("Informações sobre o treinamento $titulo")."',
                    true,
                    $posto,
                    true
                )";
                if (($i + 1) != $tl) {
                    $sql .= ",";
                }
            }
// exit($sql);
            pg_query($con,"BEGIN TRANSACTION");
            pg_query($con,$sql);

            $sqlPosto = "
                DELETE FROM tbl_treinamento_posto
                WHERE   treinamento = $treinamento
            ";
            $resPosto = pg_query($con,$sqlPosto);

            $sqlTreina = "
                DELETE FROM tbl_treinamento
                WHERE   treinamento = $treinamento
            ";
            $resTreina = pg_query($con,$sqlTreina);

            if (pg_last_error($con)) {
                $erro = pg_last_error($con);
                pg_query($con,"ROLLBACK TRANSACTION");

                echo $erro;
            }

            pg_query($con,"COMMIT TRANSACTION");
            echo json_encode(array("ok"=>true,"msg"=>utf8_encode("Treinamento excluído.")));
        }

        exit;
    }

?>
<!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script src="plugins/resize.js"></script>
        <script src="plugins/shadowbox_lupa/lupa.js"></script>
        <script>

            function envia_notificacao(){

                var treinamento_id = $("#treinamento").val();
                var notificacao_treinamento = $("#notificacao_treinamento").val();
                $(".cadastra-notificao").text('Aguarde...');
                $.ajax({
                    url: "treinamento_justificativa.php",
                    type: "POST",
                    dataType:"JSON",
                    data: {
                        ajax: true,
                        treinamento_id: treinamento_id,
                        notificacao_treinamento:notificacao_treinamento
                    }
                }).done(function(data) {
                    if (data.ok) {
                        alert(data.msg);
                        window.parent.setTimeout('location.reload()', 500);
                    }
                    $(".cadastra-notificao").text('Enviar Justificativa');
                })
                .fail(function(){
                    $("#msg_envia_notificacao").html('<div class="alert alert-error" ><h4>Erro ao excluir</h4></div>');
                });
            }
        </script>
    </head>
    <body>
        <div id="msg_envia_notificacao">

        </div>
        <div class="row">
            <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
        </div>
        <form name="frm_treinamento_notificacao" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
            <div class="container-fluid form_tc" style="overflow: auto;">
                <input type="hidden" id="treinamento" name="treinamento" value="<?=$treinamentoAjax?>">
                <div class="titulo_tabela">Justificativa da Exclusão do Treinamento</div>
                <br>
                <div class='row-fluid'>
                    <div class='span2'></div>
                    <div class="span8">
                        <div class="control-group" id="descricao_campo">
                            <h5 class='asteristico'>*</h5>
                            <label class='control-label'>Texto para envio de comunicado aos postos</label>
                            <textarea style="resize: none" name="notificacao_treinamento" rows="3" cols="30" id="notificacao_treinamento" class="span12" ></textarea>
                        </div>
                    </div>
                    <div class='span2'></div>
                </div>
                <br />
                <p class="tac">
                    <button type='button' class='btn cadastra-notificao' onclick="javascript:envia_notificacao()">Enviar Justificativa</button>
                </p>
                <br />
            </div>
        </form>
    </body>
</html>
