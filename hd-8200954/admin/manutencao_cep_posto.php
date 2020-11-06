<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, call_center";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["ajax_importa_csv"] == true) {

    $posto   = $_POST["codigo_posto_0"];
    $arquivo = $_FILES["upload"];

    if (empty($posto)) {

        $msg_erro["msg"][]    = "Selecione um Posto";
        $msg_erro["campos"][] = "posto";

    } elseif ($arquivo["size"] == 0) {

        $msg_erro["msg"][]    = "Insira o arquivo CSV";
        $msg_erro["campos"][] = "upload";

    } else {

        $arquivo = file_get_contents($arquivo["tmp_name"]);
        $trata_arquivo = str_replace("\r\n", "\n", $arquivo);
        $trata_arquivo = str_replace("\r", "\n", $arquivo);
        $arquivo = explode("\n", $trata_arquivo);
        $arquivo = array_filter($arquivo);

        $sqlPosto = "SELECT tbl_posto_fabrica.posto
                       FROM tbl_posto
                 INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                        AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                      WHERE (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto}'))";
        $resPosto = pg_query($con, $sqlPosto);

        if (!pg_num_rows($resPosto)) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "codigo_posto_0";
        } else {
            $posto = pg_fetch_result($resPosto, 0, "posto");
        }
        $ceps_carregados = array();
        foreach ($arquivo as $key => $value) {
            if ($key == 0) {
                continue;
            }
            $limpa_cep = str_replace(';', '', trim($value));
            $cep = str_replace('-', '', trim($limpa_cep));
            if (strlen($cep) <> 8) {
                continue;
            } else {
                $ceps_carregados[] = $cep;
            }
        }

        if (!empty($ceps_carregados)) {

            $ceps_cadastrados = array();
            $sql = "SELECT cep_inicial FROM tbl_posto_cep_atendimento WHERE tbl_posto_cep_atendimento.posto={$posto} AND tbl_posto_cep_atendimento.fabrica={$login_fabrica} AND blacklist is false";
            $res = pg_query($con, $sql);
            $row_cep = pg_fetch_all($res);
            foreach ($row_cep as $keyCadastrados => $valueCadastrados) {
                $ceps_cadastrados[] = $valueCadastrados['cep_inicial'];
            }

            $ceps_novos = array_diff($ceps_carregados, $ceps_cadastrados);

            if ($login_fabrica == 183){
                $campo_183 = ",blacklist";
                $valor_183 = ",'t'";
            }

            if (!empty($ceps_novos)) {
                foreach ($ceps_novos as $cep_novo) {
                    $sqlInsert = "INSERT INTO tbl_posto_cep_atendimento (fabrica, posto, cep_inicial, cep_final $campo_183) VALUES ({$login_fabrica}, {$posto}, '{$cep_novo}','{$cep_novo}' {$valor_183})";
                    $resInsert = pg_query($con, $sqlInsert);
                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][]    = "Erro: CEP {$cep_novo}, não cadastrado";
                        $msg_erro["campos"][] = "";
                    } else {
                        $msg_ok = "ok" ;
                    }
                }
            } else {
                $msg_erro["msg"][]    = "CEP(s) já cadastrado(s)";
                $msg_erro["campos"][] = "";
            }
        } else {
            $msg_erro["msg"][]    = "Erro ao carregar o arquivo, verifique.";
            $msg_erro["campos"][] = "";
        }
    }
}

if ($_GET["ajax_transferir"] == true) {
    $posto_de   = $_POST['codigo_posto_0'];
    $posto_para = $_POST['codigo_posto_1'];
    $cep        = $_POST['cep'];

    if (empty($posto_de)) {
        $retorno = array("erro" => utf8_encode("Posto não informado"));
    } elseif (empty($posto_para)) {
        $retorno = array("erro" => utf8_encode("Transferir para, não informado"));
    } elseif (empty($cep)) {
        $retorno = array("erro" => utf8_encode("CEP não selecionado"));
    }

    if (empty($retorno)) {
        $ceps = array();
        foreach ($cep as $keyCep => $valueCep) {
            $valueCep = str_replace("-", "", $valueCep);
            $ceps[] = "'$valueCep'";
        }

        $sqlPosto = "SELECT tbl_posto_fabrica.posto
                       FROM tbl_posto
                 INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                        AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                      WHERE (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_de}'))";
        $resPosto = pg_query($con, $sqlPosto);

        if (!pg_num_rows($resPosto)) {
            $retorno = array("erro" => "Posto não encontrado");
        } else {
            $postoDe = pg_fetch_result($resPosto, 0, "posto");
        }

        if (empty($retorno)) {
            $sqlPostoTransfere = "SELECT tbl_posto_fabrica.posto
                                    FROM tbl_posto
                              INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                                     AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                   WHERE (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_para}'))";
            $resPostoTransfere = pg_query($con, $sqlPostoTransfere);

            if (!pg_num_rows($resPostoTransfere)) {
                $retorno = array("erro" => utf8_encode("Posto Transferir para, não encontrado"));
            } else {
                $postoPara = pg_fetch_result($resPostoTransfere, 0, "posto");
            }
        }
        if (empty($retorno)) {

            if ($login_fabrica == 183){
                $cond_black = "";

                $sql = "SELECT cep_inicial FROM tbl_posto_cep_atendimento WHERE tbl_posto_cep_atendimento.posto={$postoPara} AND blacklist IS TRUE AND tbl_posto_cep_atendimento.fabrica={$login_fabrica}";
                $res = pg_query($con, $sql);
                $row_cep = pg_fetch_all($res);
                foreach ($row_cep as $keyCadastrados => $valueCadastrados) {
                    $ceps_cadastrados[] = "'".$valueCadastrados['cep_inicial']."'";
                }
                $ceps = array_diff($ceps, $ceps_cadastrados);
            }else{
                $cond_black = " AND blacklist is false ";
            }
            pg_query($con,"BEGIN TRANSACTION");

            $sql = "UPDATE tbl_posto_cep_atendimento SET posto = {$postoPara} WHERE tbl_posto_cep_atendimento.posto={$postoDe} {$cond_black} AND tbl_posto_cep_atendimento.fabrica={$login_fabrica} AND tbl_posto_cep_atendimento.cep_inicial IN(".implode(',', $ceps).")";
            pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con,"ROLLBACK");
                $retorno = array("erro" => utf8_encode("Erro ao Transferir"));
            } else {
                pg_query($con,"COMMIT");
                $retorno = array("ok" => true);
            }
        }
    }

    exit(json_encode($retorno));
}

if ($_GET["ajax_copiar"] == true) {
    $posto_de         = $_POST['codigo_posto_0'];
    $posto_para       = $_POST['codigo_posto_1'];
    $cep              = $_POST['cep'];
    $ceps_cadastrados = array();

    if (empty($posto_de)) {
        $retorno = array("erro" => utf8_encode("Posto não informado"));
    } elseif (empty($posto_para)) {
        $retorno = array("erro" => utf8_encode("Transferir para, não informado"));
    } elseif (empty($cep)) {
        $retorno = array("erro" => utf8_encode("CEP não selecionado"));
    }

    $sqlPosto = "SELECT tbl_posto_fabrica.posto
                   FROM tbl_posto
             INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                  WHERE (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_de}'))";
    $resPosto = pg_query($con, $sqlPosto);

    if (!pg_num_rows($resPosto)) {
        $retorno = array("erro" => utf8_encode("Posto não encontrado"));
    } else {
        $postoDe = pg_fetch_result($resPosto, 0, "posto");
    }

    $sqlPostoTransfere = "SELECT tbl_posto_fabrica.posto
                            FROM tbl_posto
                      INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                             AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                           WHERE (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_para}'))";
    $resPostoTransfere = pg_query($con, $sqlPostoTransfere);

    if (!pg_num_rows($resPostoTransfere)) {
        $retorno = array("erro" => utf8_encode("Posto Transferir para, não encontrado"));
    } else {
        $postoPara = pg_fetch_result($resPostoTransfere, 0, "posto");
    }

    if (empty($retorno)) {
        if ($login_fabrica == 183){
            $cond_blacklist = " AND blacklist IS TRUE ";
            $campo_blacklist = " , blacklist ";
        }else{
            $cond_blacklist = " AND blacklist IS FALSE ";
        }

        $sql = "SELECT cep_inicial FROM tbl_posto_cep_atendimento WHERE tbl_posto_cep_atendimento.posto={$postoPara} {$cond_blacklist} AND tbl_posto_cep_atendimento.fabrica={$login_fabrica}";
        $res = pg_query($con, $sql);
        $row_cep = pg_fetch_all($res);
        foreach ($row_cep as $keyCadastrados => $valueCadastrados) {
            $ceps_cadastrados[] = $valueCadastrados['cep_inicial'];
        }
        $msg = array();
        foreach ($cep as $keyCep => $valueCep) {
            $ceps_post[] = str_replace("-", "", $valueCep);
        }

        $ceps_novos = array_diff($ceps_post, $ceps_cadastrados);
        
        foreach ($ceps_novos as $cep_novo) {
            $sqlInsert = "INSERT INTO tbl_posto_cep_atendimento (fabrica, posto, cep_inicial, cep_final {$campo_blacklist}) SELECT fabrica, {$postoPara}, cep_inicial, cep_final {$campo_blacklist} FROM tbl_posto_cep_atendimento WHERE tbl_posto_cep_atendimento.posto={$postoDe} AND tbl_posto_cep_atendimento.cep_inicial='{$cep_novo}'";
            $resInsert = pg_query($con, $sqlInsert);
            if (strlen(pg_last_error()) > 0) {
                $msg["erro"][] = utf8_encode("Erro: CEP {$cep_novo}, não copiado");
            } else {
                $msg["ok"][] = true;
            }
        }

    }
    if (empty($retorno)) {
        if (!empty($msg["erro"])) {
            $retorno["erro"] = implode("\n", $msg["erro"]);
        } elseif (!empty($msg["ok"])) {
            $retorno = array("ok" => true);
        } else {
            $retorno = array("erro" => utf8_encode("CEP(s) já copiado(s)"));
        }
    }

    exit(json_encode($retorno));
}

if ($_GET["ajax_deleta_cep"] == true) {

    $posto = $_POST['codigo_posto_0'];
    $cep   = $_POST['cep'];

    if (empty($posto)) {
        $retorno = array("erro" => utf8_encode("Posto não informado"));
    } elseif (empty($cep)) {
        $retorno = array("erro" => utf8_encode("CEP não selecionado"));
    }
    if (empty($retorno)) {
        $sqlPosto = "SELECT tbl_posto_fabrica.posto
                       FROM tbl_posto
                 INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                        AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                      WHERE (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto}'))";
        $resPosto = pg_query($con, $sqlPosto);

        if (!pg_num_rows($resPosto)) {
            $retorno = array("erro" => utf8_encode("Posto não encontrado"));
        } else {
            $posto = pg_fetch_result($resPosto, 0, "posto");
        }
        if (empty($retorno)) {
            $ceps = array();
            foreach ($cep as $keyCep => $valueCep) {
                $valueCep = str_replace("-", "", $valueCep);
                $ceps[] = "'$valueCep'";
            }
            pg_query($con,"BEGIN TRANSACTION");

            if ($login_fabrica == 183){
                $cond_blacklist_cep = "";
            }else{
                $cond_blacklist_cep = " AND blacklist is false ";
            }

            $sql = "DELETE  FROM tbl_posto_cep_atendimento WHERE tbl_posto_cep_atendimento.posto={$posto} {$cond_blacklist_cep} AND tbl_posto_cep_atendimento.fabrica={$login_fabrica} AND tbl_posto_cep_atendimento.cep_inicial IN(".implode(',', $ceps).")";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con,"ROLLBACK");
                $retorno = array("erro" => utf8_encode("Erro ao deletar CEPs"));
            } else {
                pg_query($con,"COMMIT");
                $retorno = array("ok" => true);
            }
        }
    }
    exit(json_encode($retorno));
}

if ($_GET["ajax_exporta_cep"] == true) {

    $cep  = $_POST['cep'];
    $primeiraRequisicao = $_POST['primeiraRequisicao'];
    $ultimaRequisicao   = $_POST['ultimaRequisicao'];
    $data = $_POST['dataHora'];

    $fileName = "listagem_cep_posto-{$data}.csv";

    $file = fopen("xls/{$fileName}", "a");

    if ($primeiraRequisicao == "true") {

        $head = "CEP;\n";
        fwrite($file, $head);

    }

    foreach ($cep as $val) {
        fwrite($file, $val.";\n");
    }

    if (file_exists("xls/{$fileName}") && $ultimaRequisicao == "true") {

        echo "xls/{$fileName}";

    }

    exit;
}

if ($_POST) {
    $posto           = $_POST["codigo_posto_0"];
    $descricao_posto = $_POST["descricao_posto_0"];
}
$layout_menu = "callcenter";
$title = "Manutenção de CEP - Blacklist";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "maskedinput",
    "shadowbox",
    "alphanumeric"
);

include "plugin_loader.php";

?>

<script>

$(function() {
    Shadowbox.init();
    $.autocompleteLoad(["posto"]);

    $("#codigo_cep").mask("99999-999",{placeholder:""});

    $(document).on("click", "span[rel=lupa]", function () {
        $.lupa($(this),Array('posicao'));
    });

     $("body").on('click', "input[type='checkbox']",function(key,index) {
        var posicao = $(this).data('posicao');
        var labels  = document.getElementsByClassName("label_text");
        if ($(this).is(':checked')) {
            $(labels[posicao]).css('background-color', '#d6e9c6');
        } else {
            $(labels[posicao]).css('background-color', '#ffffff');
        }

    });

    /* DELETA CEPS SELECIONADOS */
    $('.btn-remove-selecionados').click(function(){
        var checado =  $('#frm1').find("input[type='checkbox']:checked").length > 0;
        if (checado) {
            if (confirm('Tem certeza que deseja remover o(s) CEP(s) selecionado(s)?')) {
               var dados = $('#frm1').serialize();
                $.ajax({
                    url: "manutencao_cep_posto.php?ajax_deleta_cep=true",
                    type: "POST",
                    data: dados,
                    beforeSend: function() {
                        $("button.btn-remove-selecionados").button("loading");
                    }
                }).done(function(data) {
                    $("button.btn-remove-selecionados").button("reset");
                    data = $.parseJSON(data);
                    if (data.erro != '' && !data.ok) {
                        alert(data.erro);
                        return false;
                    } else {
                        alert('CEP(s) removido(s) com sucesso');
                        location.reload();
                    }
                });
            }
        } else {
            alert('Selecione pelo menos um CEP');
            return false;
        }

    });

    /* EXPORTA CEPS SELECIONADOS */
    $('.btn-exporta-selecionados').click(function(){

        var checado =  $('#frm1').find("input[type='checkbox']:checked").length > 0;

        var today = new Date();

        var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
        var dateTime = date+' '+time;

        var dataHoraArquivo = dateTime;

        if (checado) {

            var arrCeps = $("#frm1 input[type='checkbox']:checked").map(function(){
                return $(this).val();
            }).get();

            let x = 0;
            while (arrCeps.length) {

                var arrDividido = arrCeps.splice(0, 100);

                $.ajax({
                    async: false,
                    url: "manutencao_cep_posto.php?ajax_exporta_cep=true",
                    type: "POST",
                    data: {
                      cep: arrDividido,
                      ultimaRequisicao: false,
                      primeiraRequisicao: (x == 0),
                      dataHora: dateTime
                    },
                    beforeSend: function() {
                        $("button.btn-exporta-selecionados").button("loading");
                    }
                }).done(function(retorno) {
                    $("button.btn-exporta-selecionados").button("reset");
                });

                x++;
            }

            $.ajax({
                async: false,
                url: "manutencao_cep_posto.php?ajax_exporta_cep=true",
                type: "POST",
                data: {
                  cep: [],
                  ultimaRequisicao: true,
                  primeiraRequisicao: false,
                  dataHora: dateTime
                },
                beforeSend: function() {
                    $("button.btn-exporta-selecionados").button("loading");
                }
            }).done(function(retorno) {
                $("button.btn-exporta-selecionados").button("reset");
                if (retorno != '') {
                    window.open(retorno);
                    location.reload();
                } else {
                    alert('Houve um erro ao exportar');
                    return false;
                }
            });

        } else {
            alert('Selecione pelo menos um CEP');
            return false;
        }
    });

    /* TRANSFERE CEPS */
    $('#btn_acao_transferir').click(function(){
        var checado =  $('#frm1').find("input[type='checkbox']:checked").length > 0;
        if ($('#codigo_posto_0').val() == '' || $('#descricao_posto_0').val() == '') {
            alert("Selecione um Posto");
            $('#codigo_posto_0').focus();
            return false;
        }

        if (!checado) {
            alert("Selecione os menos um CEP");
            return false;
        }

        if ($('#codigo_posto_1').val() == '' || $('#descricao_posto_1').val() == '') {
            alert("Selecione um Posto a transferir");
            $('#codigo_posto_1').focus();
            return false;
        }

        var dados = $('#frm1').serialize();
        $.ajax({
            url: "manutencao_cep_posto.php?ajax_transferir=true",
            type: "POST",
            data: dados,
            beforeSend: function() {
                $("#btn_acao_transferir").button("loading");
            }
        }).done(function(data) {
            $("#btn_acao_transferir").button("reset");
            data = $.parseJSON(data);
            if (data.erro != '' && !data.ok) {
                alert(data.erro);
                return false;
            } else {
                alert('CEP(s) transferido(s) com sucesso');
                location.reload();
            }
        });
    });

    /* COPIA CEPS */
    $('#btn_acao_copiar').click(function(){
        var checado =  $('#frm1').find("input[type='checkbox']:checked").length > 0;
        if ($('#codigo_posto_0').val() == '' || $('#descricao_posto_0').val() == '') {
            alert("Selecione um Posto");
            $('#codigo_posto_0').focus();
            return false;
        }

        if (!checado) {
            alert("Selecione os menos um CEP");
            return false;
        }

        if ($('#codigo_posto_1').val() == '' || $('#descricao_posto_1').val() == '') {
            alert("Selecione um Posto a transferir");
            $('#codigo_posto_1').focus();
            return false;
        }

        var dados = $('#frm1').serialize();
        $.ajax({
            url: "manutencao_cep_posto.php?ajax_copiar=true",
            type: "POST",
            data: dados,
            beforeSend: function() {
                $("#btn_acao_copiar").button("loading");
            }
        }).done(function(data) {
            $("#btn_acao_copiar").button("reset");
            console.log(data)
            data = $.parseJSON(data);
            if (data.erro != '' && !data.ok) {
                alert(data.erro);
                return false;
            } else {
                alert('CEP(s) copiado(s) com sucesso');
                location.reload();
            }
        });
    });
});

checked = false;

function selecionaTudo(form) {
    var aa     = document.getElementById(form);
    var labels = document.getElementsByClassName("label_text");
    if (checked == false) {
        checked = true;
        bg_label = '#d6e9c6';
    } else {
        checked = false
        bg_label = '#ffffff';
    }

    for (var i =0; i < aa.elements.length; i++) {
        aa.elements[i].checked = checked;
        $(labels[i]).css('background-color', bg_label);
    }
}

function retorna_posto(retorno) {
    var posicao = retorno.posicao;
    $("#codigo_posto_"+posicao).val(retorno.codigo);
    $("#descricao_posto_"+posicao).val(retorno.nome);
    if (retorno.codigo != '' && posicao == 0) {
        $("#acoes_cep").show("slow");
        carrega_cep_posto(retorno.codigo);
    }
}

function carrega_cep_posto(posto) {
    $.ajax({
        url: "ajax_carrega_cep_posto.php",
        type: "POST",
        data: { ajax_carrega_cep_posto: true, posto: posto },
        beforeSend: function() {
            $("#cep_posto").html("<div class='alert alert-info'><p>Aguarde, carregando CEP(s)...</p></div>");
        }
    }).always(function(data) {
        data = $.parseJSON(data);
        if (data.erro) {
            $("#cep_posto").html("<div class='alert alert-warning'><p>CEP(s) não encontrado para este Posto.</p></div>");
        } else {
            $("#cep_posto").html("");
            $.each(data.ceps_posto, function(key, cep) {
                var cep_final = cep.substring(0, 5) +'-'+ cep.substring(5, 8);
                $("#cep_posto").append("<label class='label_text'><input type='checkbox' data-posicao='"+key+"' name='cep[]' value='"+cep_final+"'> "+cep_final+"</label>");
            });
        }
    });
}

</script>
<style>
    .linha{
        border:solid 1px #eeeeee;
        background: #ffffff;
        overflow: auto;
        max-height: 250px;
        padding: 5px;
    }
    .label_text{
        border-bottom: solid 1px #eeeeee;padding:5px;margin: 0px;display: block !important;
    }
</style>
<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<?php if (count($msg_erro["msg"]) == 0 && strlen($msg_ok) > 0) { ?>
    <div class="alert alert-success" >
        <h4>Arquivo Enviado com Sucesso</h4>
    </div>
<?php } ?>
<div class="row" >
    <b class="obrigatorio pull-right" >  * Campos obrigatórios </b>
</div>
<form name="frm1" id="frm1" method="post" class="form-search form-inline tc_formulario" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">

    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
    <br />

    <div class="row-fluid" >
        <div class="span2" ></div>

        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="codigo_posto_0" >Código Posto</label>

                <div class="controls controls-row" >
                    <div class="span7 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="codigo_posto_0" id="codigo_posto_0" class="span12" value="<?= $posto ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" posicao="0" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="descricao_posto_0" >Nome Posto</label>

                <div class="controls controls-row" >
                    <div class="span12 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="descricao_posto_0" id="descricao_posto_0" class="span12" value="<?= $descricao_posto ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" posicao="0" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>

        <div class="span4" >
            <div class="control-group <?=(in_array('cep', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="codigo_cep" >CEP</label>

                <div class="controls controls-row" >
                    <div class="span7 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="codigo_cep" id="codigo_cep" class="span12" value="<?= $codigo_cep ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" posicao="0" parametro="cep" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span4" >
        </div>

        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8">
            <br />

            <div class="alert">
               <h4> Layout do arquivo: </h4>
                <b>"CEP;<br />
                    11111-111;<br />
                99999-999;"</b> <br /> separados por ponto e virgula (;)
            </div>

            <br />
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span5" >
            <div class="control-group" >
                <label class="control-label" for="upload" >Arquivo CSV/TXT</label>
                <input type="hidden" name="ajax_importa_csv" id="ajax_importa_csv" value="true" />


                <div class="controls controls-row" >
                    <div class="span12" >
                        <input type="file" name="upload" id="upload" class="span12" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="controls controls-row" >
                <div class="span8" >
                    <br />
                    <input type="submit" class="btn btn-primary" data-loading-text="Realizando upload..." value="Realizar Upload" />
                </div>
            </div>
        </div>
    </div>
    <br />


    <div id="acoes_cep" style="display: none;">
        <div class="titulo_tabela" >CEP(s) Atendido(s)</div>
        <input type="hidden" name="arquivo_csv" value="sim" />
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="row-fluid" style="min-height:0px !important;">
                    <div class="span3"></div>
                    <div class="span6" align="center">
                        <span><a href="javascript:selecionaTudo('frm1');" id="seleciona">Marcar todos</a></span>
                    </div>
                    <div class="span3"></div>
                </div>
                <div class="row-fluid"  style="min-height:0px !important;">
                    <div class="span3"></div>
                    <div class="span6 linha" id="cep_posto">
                        <p align="center">Selecione um posto.</p>
                    </div>
                    <div class="span3"></div>
                </div>
                <div class="row-fluid" style="min-height:0px !important;">
                    <div class="span3"></div>
                    <div class="span6" style="padding-top:10px !important;" align="center">
                        <button type="button" data-loading-text="Deletando..." class="btn btn-mini btn-danger btn-remove-selecionados">Deletar selecionados</button>
                        <button type="button" data-loading-text="Exportando..." class="btn btn-mini btn-exporta-selecionados">Exportar selecionados</button>
                    </div>
                    <div class="span3"></div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <br /><br />
        <div class="titulo_tabela" >Transferir / Copiar para:</div><br />
          <div class="row-fluid" >
            <div class="span2" ></div>

            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" for="codigo_posto" >Código Posto</label>
                    <div class="controls controls-row" >
                        <div class="span7 input-append" >
                            <input type="text" name="codigo_posto_1" id="codigo_posto_1" class="span12" value="<?= $codigo_posto ?>" />
                            <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" posicao="1" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span4" >
                <div class="control-group">
                    <label class="control-label" for="descricao_posto_1" >Nome Posto</label>

                    <div class="controls controls-row" >
                        <div class="span12 input-append" >
                            <input type="text" name="descricao_posto_1" id="descricao_posto_1" class="span12" />
                            <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" posicao="1" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span2" ></div>
        </div>
        <br /><br />
        <p align="center">
            <button class="btn btn-primary" data-loading-text="Transferindo..." id="btn_acao_transferir" type="button">Transferir</button> ou
            <button class="btn btn-success" data-loading-text="Copiando..." id="btn_acao_copiar" type="button">Copiar</button>
        </p>
        <br /><br />
    </div>
</form>
<?php
include "rodape.php";

?>
