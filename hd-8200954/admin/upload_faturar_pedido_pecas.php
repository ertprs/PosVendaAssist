<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

function busca_info_posto($valor){
    global $con, $login_fabrica;

    if($login_fabrica == 152){
        $campo = "codigo_posto";
    }else{
        $campo = "cnpj";
    }

    if(strstr($valor, "'") != false){
        $valor = str_replace("'", "", $valor);
    }

    if(strstr($valor, '"') != false){
        $valor = str_replace('"', "", $valor);
    }

    $sql = "SELECT nome, tbl_posto.posto
            FROM tbl_posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            WHERE $campo = '$valor'
            AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $posto_nome = pg_fetch_result($res, 0, "nome");
        $id         = pg_fetch_result($res, 0, "posto");
    }else{
        $posto_nome   = "";
        $id           = "";
    }

    return array(
        "nome" => $posto_nome,
        "id"   => $id
    );
}

if(isset($_FILES['arquivo_faturar_pedido'])){
    $arquivo = $_FILES['arquivo_faturar_pedido'];

    $types = array("csv","text/csv");
    $type  = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["size"] > 0) {
        if (!in_array($type, $types)) {
            $msg_erro = "Formato inválido, é aceito apenas o formato csv";
        } else {
            $file = fopen($arquivo['tmp_name'],"r");

            if($file){
                $file_content = explode("\n", file_get_contents($arquivo["tmp_name"]));

                $file_content = array_map(function($i) {
                    if(strripos($i, ";") !== false){
                        return explode(";", $i);
                    }else if(strripos($i, "\t")){
                        return explode("\t", $i);
                    }
                }, $file_content);

                $cabecalho = $file_content[0];
                unset($file_content[0]);

                $linhas = $file_content;
                unset($file_content);

                $linhas = array_map(function($l) use ($cabecalho) {
                    $arr = array();

                    foreach ($l as $key => $value) {
                        $arr[trim($cabecalho[$key])] = trim($value);
                    }

                    return $arr;
                }, $linhas);

                $count = count($linhas);

                for($i=1; $i<=$count; $i++){
                    if (empty($linhas[$i]['pedido'])) {
                        continue;
                    }

                    $sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$linhas[$i]['pedido']}";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        $msg_erro["msg"][] = "Pedido {$linhas[$i]['pedido']} não encontrado";
                        continue;
                    }

                    $sql = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$linhas[$i]['pedido']} AND pedido_item = {$linhas[$i]['pedido_item']}";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        $msg_erro["msg"][] = "Peça {$linhas[$i]['referencia_peca']} não encontrada para o pedido {$linhas[$i]['pedido']}";
                        continue;
                    }

                    $linhas[$i]['cnpj'] = str_replace(array('.','-','/'),"",$linhas[$i]['cnpj']);
                    
                    $xprevisao_entrega = $linhas[$i]['previsao_entrega'];
                    if (!empty($xprevisao_entrega)){
                        $xprevisao_entrega = fnc_formata_data_pg($xprevisao_entrega);
                        $xprevisao_entrega = str_replace("'", "", $xprevisao_entrega);

                        if (strpos($xprevisao_entrega, "-")) {
                            list($ano, $mes, $dia) = explode("-", $xprevisao_entrega);
                            $xprevisao_entrega = "$dia/$mes/$ano";
                        }
                        $linhas[$i]['previsao_entrega'] = $xprevisao_entrega;
                    }
                    
                    if(strlen($linhas[$i]['previsao_entrega']) > 0){
                        $previsao_entrega = $linhas[$i]['previsao_entrega'];
                        $codigo_posto      = $linhas[$i]['codigo_posto'];
                        $cnpj              = $linhas[$i]['cnpj'];
                        $xpedido           = $linhas[$i]['pedido'];

                        $previsao_entrega = fnc_formata_data_pg($previsao_entrega);
                        $previsao_entrega = str_replace("'", "", $previsao_entrega);

                        if (strpos($previsao_entrega, "-")) {
                            list($ano, $mes, $dia) = explode("-", $previsao_entrega);
                            $previsao_entrega = "$dia/$mes/$ano";
                        }
                        
                        $pedido_nf[$xpedido]['codigo_posto']        = $codigo_posto;
                        $pedido_nf[$xpedido]["cnpj"]                = $cnpj;
                        $pedido_nf[$xpedido]['previsao_entrega']    = $previsao_entrega;

                        $info_posto = busca_info_posto($cnpj);
                        $pedido_nf[$xpedido]["nome_posto"] = $info_posto["nome"];
                        $pedido_nf[$xpedido]["id_posto"]   = $info_posto["id"];
                        
                        $array_pedido = $linhas[$i];

                        unset(
                            $array_pedido["codigo_posto"],
                            $array_pedido["cnpj"],
                            $array_pedido["nome_posto"]
                        );

                        $pedido_nf[$xpedido]["pedidos"][] = $array_pedido;
                    } else if (!empty($linhas[$i]["previsao_faturamento"])) {
                        $pedido[$linhas[$i]['pedido']][] = $linhas[$i];
                    }
                }
            }
        }
    } else {
        $msg_erro["msg"][] = "Erro ao fazer o upload do arquivo";
    }
}

if(isset($_POST['btn_acao'])){
    $btn_acao = $_POST['btn_acao'];
}

if ($btn_acao == "gravar_faturamento") {
    try {
        $transaction = false;



        $nota_fiscal       = $_POST["nota_fiscal"];
        $id_posto          = $_POST["id_posto"];
        $pedidos           = $_POST["pedidos"];


        if (!count($pedidos)) {
            throw new Exception("Nota Fiscal sem Itens para faturar");
        } else {
            $erros = array();

            foreach ($pedidos as $key => $pedido) {
                if (empty($pedido["pedido"])) {
                    $erros[] = "Pedido {$pedido['pedido']} não encontrado";
                } else {
                    
                    if (empty($pedido["previsao_entrega"])) {
                        throw new Exception("Previsão de Chegada não informada");
                    } else {
                        unset($previsao_entrega);
                        $previsao_entrega = $pedido["previsao_entrega"];
                        list($dia, $mes, $ano) = explode("/", $previsao_entrega);
                        $previsao_entrega = "$ano-$mes-$dia";
                        if (!strtotime($previsao_entrega)) {
                            throw new Exception("Previsão de Chegada inválida");
                        } else if (strtotime($previsao_entrega) < strtotime("today")) {
                            throw new Exception("Previsão de Chegada não pode ser inferior ao o dia atual");
                        }

                        $pedido["previsao_entrega"] = $previsao_entrega;
                    }

                    pg_query($con, "BEGIN");

                    $transaction = true;

                    $array_comunicado[] = array(
                        "pedido"           => $pedido["pedido"],
                        "peca"             => $pedido["referencia_peca"],
                        "previsao_entrega" => $pedido["previsao_entrega"],
                        "os"               => $pedido["os"]
                    );

                    if (empty($previsao_entrega)){
                        continue;
                    }

                    $sql_up = "UPDATE tbl_pedido SET previsao_entrega = '{$previsao_entrega}' WHERE fabrica = {$login_fabrica} AND pedido = ".$pedido["pedido"];
                    $res_up = pg_query($con, $sql_up);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao atualizar ordem/previsão de faturamento do pedido");
                    }
                }
            }
            if (count($array_comunicado)) {
                $html = "<ul>";

                foreach ($array_comunicado as $pedido) {
                    list($ano, $mes, $dia) = explode("-", $previsao_entrega);

                    $previsao_entrega = "$dia/$mes/$ano";
                    $html .= "
                        <li>OS: {$pedido['os']}, Pedido: {$pedido['pedido']}, Peça: {$pedido['peca']}, Previsão de Chegada: {$previsao_entrega}</li>
                    ";
                }

                $html .= "</ul>";
                $sql = "
                    INSERT INTO tbl_comunicado
                        (fabrica, posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
                    VALUES
                        ({$login_fabrica}, {$id_posto}, true, 'Com. Unico Posto', true, 'Previsão de chegada', '{$html}')";
                $res = pg_query($con, $sql);

                if(pg_last_error() > 0){
                    throw new Exception("Erro ao enviar comunicado de pedidos faturados para o posto autorizado");
                }
            }

            pg_query($con, "COMMIT");
            $array_sucesso["sucesso"] = true;
            exit(json_encode($array_sucesso));
        }
    } catch(Exception $e) {
        if ($transaction === true) {
            pg_query($con, "ROLLBACK");
        }
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

function formatar_data($data){
    if(strripos($data,"-") == true){
        list($ano, $mes, $dia) = explode("-", $data);
        $data = $dia."/".$mes."/".$ano;
    }else{
        list($dia, $mes, $ano) = explode("/", $data);
        $data = $ano."-".$mes."-".$dia;
    }
    return($data);
}

$layout_menu = "gerencia";
$title = "ATUALIZAR PREVISÃO DE ENTREGA";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput"
);

include __DIR__."/plugin_loader.php";
?>

<script type="text/javascript">
    $(function(){
        
        $("button.salvar_arquivo").on("click",function(){
            $(this).button("loading");

            setTimeout(function() {
                var arquivo = $('#arquivo_faturar_pedido').val();

                if(arquivo.length > 0){
                    $("button.salvar_arquivo").parents("#frm_upload_faturar_pedido").submit();
                }else{
                    $("button.salvar_arquivo").button("reset");
                    alert("Selecione o arquivo da nota fiscal de serviço");
                }
            }, 1);
        });

        $("button.btn_grava_nota_fiscal").on("click", function() {
            $(this).button("loading");

            $("table.nota_fiscal_faturar").each(function() {
                $(this).find("td.status").html("<span class='label label-info' >Aguardando...</span>");
            });

            var processo_erro = false;
            var parcial = 0;

            $("table.nota_fiscal_faturar").each(function() {
                var td_status = $(this).find("td.status");
                if ($(td_status).find("span.label-success").length > 0) {
                    return;
                }

                var erro = [];
                var id_posto          = $(this).find("input.id_posto").val();
                if (typeof id_posto == "undefined" || id_posto.length == 0) {
                    erro.push("Posto Autorizado não encontrado");
                }

                var pedidos = [];
                $(this).find("table.pedidos > tbody > tr").each(function() {
                    var pedido = $(this).find("input.pedido").val();
                    var previsao_entrega = $(this).find("input.previsao_entrega").val();
                    var pedido_item = $(this).find("input.pedido_item").val();
                    var os = $(this).find("input.os").val();
                    var referencia_peca = $(this).find("input.referencia_peca").val();
                    var qtde_pendente = parseInt($(this).find("input.quantidade_pendente").val());
                    var erro_linha = false;

                    if (typeof referencia_peca == "undefined" || referencia_peca.length == 0) {
                        erro.push("Peça do Pedido "+pedido+" não informada");
                        erro_linha = true;
                    } else {
                        if (typeof pedido == "undefined" || pedido.length == 0) {
                            erro.push("Número de Pedido da Peça "+referencia_peca+" não informado");
                            erro_linha = true;
                        }

                        if (typeof pedido_item == "undefined" || pedido_item.length == 0) {
                            erro.push("Número de Pedido Item da Peça "+referencia_peca+" não informado");
                            erro_linha = true;
                        }

                        if (isNaN(qtde_pendente)) {
                            erro.push("Peça "+referencia_peca+" Qtde Pendente inválida (deve ser um número inteiro) ou não informada");
                            erro_linha = true;
                        }
                    }
                    if (!erro_linha) {
                        var dados_pedido = {
                            pedido: pedido,
                            pedido_item: pedido_item,
                            os: os,
                            referencia_peca: referencia_peca,
                            qtde_pendente: qtde_pendente,
                            previsao_entrega: previsao_entrega
                        };
                        pedidos.push(dados_pedido);
                    }
                });
                
                if (erro.length > 0) {
                    processo_erro = true;
                    $(this).find("td.status").html("<span class='label label-important' >"+erro.join("<br />")+"</span>");
                    return;
                } else {
                    var dados_nota_fiscal = {
                        btn_acao: "gravar_faturamento",
                        id_posto: id_posto,
                        pedidos: pedidos
                    };

                    $.ajax({
                        async: false,
                        url: "upload_faturar_pedido_pecas.php",
                        type: "post",
                        dataType:"JSON",
                        data: dados_nota_fiscal,
                        beforeSend: function() {
                            $(td_status).html("<span class='label label-warning' >Processando...</span>");
                        },
                        timeout: 5000
                    }).fail(function(response) {
                        processo_erro = true;
                        $(td_status).html("<span class='label label-important' >Tempo limite esgotado tente novamente</span>");
                    }).done(function(response) {
                        if (response.sucesso) {
                            $(td_status).html("<span class='label label-success' >Atualizado</span>");
                            if (response.parcial) {
                                parcial = 1;
                            }
                        } else {
                            processo_erro = true;
                            $(td_status).html("<span class='label label-important' >"+response.erro+"</span>");
                        }
                    });
                }
            });

            if (processo_erro === true) {
                alert("Processamento concluído com erros");
            } else {
                var msg = "Processamento concluído com sucesso";
                if (parcial == 1) {
                    msg += "\nHá itens que foram processados parcialmente";
                }
                alert(msg);
            }

            $(this).button("reset");
        });
    });
</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <div class="alert alert-error">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
<? } ?>

<div class="mensagem"></div>
<form name="frm_upload_faturar_pedido" id="frm_upload_faturar_pedido" method="POST" action="<?echo $PHP_SELF?>"  class="form-search form-inline tc_formulario" enctype="multipart/form-data">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
     <span class="label label-important">Layout do arquivo: A planilha deverá ser no formato CSV (.csv), Os campos devem ser separados por ponto e virgula(;)</span>

    <div class='row-fluid'>
        <div class="span2"></div>
        <div class="span2">
            <div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
                <div class="controls controls-row">
                    <div class="span12"><h5 class='asteristico'>*</h5>
                        <label>Upload de arquivo</label>
                        <input type='file' name='arquivo_faturar_pedido' id="arquivo_faturar_pedido" size='18' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p>
        <button type="button" data-loading-text="Realizando Upload..." class="btn salvar_arquivo">Upload de Arquivo</button>
    </p>
    <br />
</form>
<div class="campo_obrigatorio"></div>
<?php
if(count($linhas) > 0){
    if(count($pedido_nf) > 0){
    ?>
        <table class="table table-striped table-bordered table-large" style="margin: 0 auto;" >
            <td class="tac" >
                <button type="button" data-loading-text="Processando..." class="btn btn-success btn_grava_nota_fiscal" >Gravar Previsão de Entrega</button>
            </td>
        </table>

        <br />

        <?php
        foreach ($pedido_nf as $pedido => $dados) {
        ?>
            <table class="table table-striped table-bordered table-large nota_fiscal_faturar" style="margin: 0 auto;" >
                <tr class="info" >
                    <td class="tac" colspan="4" style="font-weight: bold;" >
                        Pedido: <?=$pedido?>
                        <input type="hidden" class="pedido" value="<?=$pedido?>" />
                        <input type="hidden" class="id_posto" value="<?=$dados['id_posto']?>" />
                        
                    </td>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Posto</th>
                    <th>Previsão entrega</th>
                </tr>
                <tr>
                    <td nowrap><?=$dados["nome_posto"]?></td>
                    <td nowrap class="tac"><?=$dados["previsao_entrega"]?></td>
                </tr>
                <tr class="titulo_coluna" >
                    <th colspan="4" >Itens</th>
                </tr>
                <tr>
                    <td colspan="4" style="padding: 0px;" >
                        <table class="table table-striped table-bordered pedidos" style="margin: 0 auto; width: 100%;" >
                            <thead>
                                <tr class="titulo_coluna" >
                                    <th>Pedido</th>
                                    <th>Peça</th>
                                    <th>Quantidade pendente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($dados["pedidos"] as $pedido) {
                                    ?>
                                    <tr>
                                        <td>
                                            <?=$pedido["pedido"]?>
                                            <input type="hidden" class="pedido" value="<?=$pedido['pedido']?>" />
                                            <input type="hidden" class="pedido_item" value="<?=$pedido['pedido_item']?>" />
                                            <input type="hidden" class="os" value="<?=$pedido['os']?>" />
                                            <input type="hidden" class="referencia_peca" value="<?=$pedido['referencia_peca']?>" />
                                            <input type="hidden" class="quantidade_pendente" value="<?=$pedido['quantidade_pendente']?>" />
                                            <input type="hidden" class="previsao_entrega" value="<?=$pedido['previsao_entrega']?>" />
                                        </td>
                                        <td><?=$pedido["referencia_peca"]?> - <?=$pedido["descricao_peca"]?></td>
                                        <td class="tac"><?=$pedido["quantidade_pendente"]?></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
            <br />
        <?php
        }
        ?>

        <hr />

    <?php
    }else{
        ?>
        <div class="alert alert-warning"><h4>No arquivo não foram encontrados pedidos para faturar</h4></div>
        <?php
    }
}

include "rodape.php";
?>
