<?php
set_time_limit(0);
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

if (!$moduloGestaoContrato) {
    echo "<meta http-equiv=refresh content=\"0;URL=menu_gerencia.php\">";
}

use GestaoContrato\TabelaPreco;
$objTabelaPreco   = new TabelaPreco($login_fabrica, $con);
$url_redir        = "<meta http-equiv=refresh content=\"0;URL=cadastro_tabela_preco.php\">";

if ($_GET["acao"] == "edit" && isset($_GET["contrato_tabela"]) && strlen($_GET["contrato_tabela"]) > 0) {
    $retorno     = $objTabelaPreco->get($_GET["contrato_tabela"]);
} elseif ($_GET["upload_arquivo"] == "true" && isset($_GET["contrato_tabela"]) && strlen($_GET["contrato_tabela"]) > 0) {
    $retorno     = $objTabelaPreco->get($_GET["contrato_tabela"]);
} 

$dadosTabela = $objTabelaPreco->get();

if ($_POST["tipo_acao"] == "add") {
    $retorno        = $_POST;
    $codigo         = $_POST["codigo"];
    $descricao      = $_POST["descricao"];
    $ativo          = ($_POST["ativo"] == "t") ? "t" : "f";

    if (strlen($codigo) == 0) {
        $msg_erro["msg"][]    = "Campo Código é obrigatório";
        $msg_erro["campos"][] = "codigo";
    }

    if (strlen($descricao) == 0) {
        $msg_erro["msg"][]   = "Campo Descrição é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    if (count($msg_erro["msg"]) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");

        $dadosSave = [
                        "codigo"        => $codigo,
                        "descricao"     => $descricao,
                        "ativo"         => $ativo,
        ];
        $result   = $objTabelaPreco->add($dadosSave);
        
        if (isset($result["erro"]) && $result["erro"] == true) {
            $msg_erro["msg"][] = $result["msn"];
        } else {
            $msg_sucesso["msg"][] = "Gravado com sucesso";
        }

        if (count($msg_erro["msg"]) == 0 ) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            echo $url_redir;
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

if ($_POST["tipo_acao"] == "edit") {

    $contrato_tabela  = $_POST["contrato_tabela"];
    $codigo           = $_POST["codigo"];
    $descricao        = $_POST["descricao"];
    $ativo            = ($_POST["ativo"] == "t")          ? "t" : "f";

    if (strlen($codigo) == 0) {
        $msg_erro["msg"][] = "Campo Código é obrigatório";
        $msg_erro["campos"][] = "codigo";
    }

    if (strlen($descricao) == 0) {
        $msg_erro["msg"][] = "Campo Descrição é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    if (count($msg_erro["msg"]) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");
        $dadosSave = [
                        "codigo"        => $codigo,
                        "descricao"     => $descricao,
                        "ativo"         => $ativo,
        ];
        $result   = $objTabelaPreco->edit($contrato_tabela, $dadosSave);
        if (isset($result["erro"]) && $result["erro"] == true) {
            $msg_erro["msg"][] = $result["msn"];
        } else {
            $msg_sucesso["msg"][] = "Gravado com sucesso";
        }

        if (count($msg_erro["msg"]) == 0 ) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            echo $url_redir;
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}


if ($_POST["tipo_acao"] == "upload") {
    $contrato_tabela  = $_POST["contrato_tabela"];
    $upload_arquivo   = $_FILES["upload_tabela"]["name"];


    if (strlen($contrato_tabela) == 0) {
        $msg_erro["msg"][]   = "Tabela não encontrada";
        $msg_erro["campos"][] = "contrato_tabela";
    }

    if (strlen($upload_arquivo) == 0) {
        $msg_erro["msg"][]    = "Uploado de Arquivo é obrigatório";
        $msg_erro["campos"][] = "upload_tabela";
    }

    if (count($msg_erro["msg"]) == 0) {

        $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["upload_tabela"]["name"]));

        if (!in_array(strtolower($extensao), array("csv", "txt"))) {
            $msg_erro["msg"][]  = "Formado de arquivo inválido <br />";
            $msg_erro["campos"][] = "upload_tabela";
        }
        $arquivo        = file_get_contents($_FILES['upload_tabela']['tmp_name']);
        $trata_arquivo  = str_replace("\r\n", "\n", $arquivo);
        $trata_arquivo  = str_replace("\r", "\n", $arquivo);
        $arquivo        = explode("\n", $trata_arquivo);
        $itens_tabela   = array_filter($arquivo);

        if (count($itens_tabela) > 0 && count($msg_erro["msg"]) == 0) {
            $total_registros = count($itens_tabela);
            $array_itens = [];
            $contador_success = [];
            $contador_erro = [];
            foreach ($itens_tabela as $key => $rows) {
                
                list($codigo_tabela, $referencia, $valor) = explode(";", $rows);

                $xproduto = $objTabelaPreco->getProduto(trim($referencia));
                $xtabela  = $objTabelaPreco->get(null,trim($codigo_tabela));

                if (isset($xproduto["produto"]) && strlen($xproduto["produto"]) > 0 && isset($xtabela["contrato_tabela"])  && strlen($xtabela["contrato_tabela"]) > 0) {
                   
                    $res = pg_query($con,"BEGIN TRANSACTION");

                    $dadosSave = [
                                    "produto"           => $xproduto["produto"],
                                    "contrato_tabela"   => $xtabela["contrato_tabela"],
                                    "preco"             => $valor,
                    ];


                    $auditorLog = new AuditorLog();
                    $auditorLog->retornaDadosTabela('tbl_contrato_tabela_item', array('contrato_tabela'=>$xtabela["contrato_tabela"]));
                    $result   = $objTabelaPreco->addItem($dadosSave);

                    if (isset($result["erro"]) && $result["erro"] == true) {
                        $contador_erro[] =  $result["msn"];
                        $res = pg_query($con,"ROLLBACK TRANSACTION");
                    } else {
                        $sqlLog   = "SELECT preco FROM tbl_contrato_tabela_item WHERE contrato_tabela = ".$xtabela["contrato_tabela"];
                        $auditorLog->retornaDadosSelect($sqlLog)->enviarLog('insert', 'tbl_contrato_tabela_item', $login_fabrica.'*'.$xtabela["contrato_tabela"]);
                        $res = pg_query($con,"COMMIT TRANSACTION");
                        $contador_success[] = "Gravado com sucesso";
                    }
                    unset($auditorLog);
                } else {
                    $contador_erro[] = 'Produto Referencia: <b>'.$referencia .'</b>  não encontrado.';
                }
            }
        }
    }
}


$layout_menu       = "gerencia";
$admin_privilegios = "gerencia";
$title             = "Tabela de Preço -  Contrato";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });

        Shadowbox.init();

        $(".ver_pecas").click(function(){
            var tabela = $(this).data('tabela');

                Shadowbox.open({
                    content :   "contrato_tabela_preco_item.php?tabela="+tabela,
                    player  :   "iframe",
                    title   :   "Gerenciamento de Produtos",
                    width   :   800,
                    height  :   500
                });
        });


    });
</script>
    <?php if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>
    <?php if (count($msg_sucesso["msg"]) > 0) {?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($contador_success) > 0){?>
        <div class="alert alert-success">
            <h4>Foram inserido(s) <?php echo count($contador_success);?> de um total de <?php echo $total_registros;?></h4>
        </div>
    <?php }?>
    <?php if (count($contador_erro) > 0){?>
        <div class="alert alert-error">
            <h4>Ocorreu erro em <?php echo count($contador_erro);?> de um total de <?php echo $total_registros;?></h4>
        </div>
        <div class="alert alert-error" style="text-align: left;">
            <p><?php echo implode("<br>", $contador_erro);?></p>
        </div>
    <?php }?>
    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <?php 
    //UPLOAD DE ITENS
    if (isset($_GET["upload_arquivo"]) && $_GET["upload_arquivo"] == true && strlen($_GET["contrato_tabela"]) > 0) {
    ?>
        <div class="row-fluid>">
            <div class="alert">
            
                Layout do anexo com delimitador 'ponto e vírgula': Código da tabela; Referência do produto; Preço<br />
                <strong>Exemplo: VENDA;<?php echo date('mYs')?>;500.55</strong>
            </div>
        </div>
        <form name='frm_tabela' METHOD='POST' ACTION='cadastro_tabela_preco.php?upload_arquivo=true&contrato_tabela=<?php echo $retorno["contrato_tabela"] ?>' align='center'  enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <input type="hidden" name="contrato_tabela" value="<?php echo (isset($retorno["contrato_tabela"]) && strlen($retorno["contrato_tabela"]) > 0) ? $retorno["contrato_tabela"] : "";?>">
            <input type="hidden" name="tipo_acao" value="upload">

            <div class="titulo_tabela">Upload de Arquivo da tabela de preço</div><br />

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label' for='codigo'>Código</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" id="codigo" disabled name="codigo" class='span12' value="<?php echo (isset($retorno["codigo"]) && strlen($retorno["codigo"]) > 0) ? $retorno["codigo"] : "";?>"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='descricao'>Descrição</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" id="descricao" disabled name="descricao" class='span12' value="<?php echo (isset($retorno["descricao"]) && strlen($retorno["descricao"]) > 0) ? $retorno["descricao"] : "";?>" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span6">
                    <div class='control-group <?=(in_array("upload_tabela", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='upload_tabela'>Upload de arquivo(somente arquivo com extensão txt)</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="file" id="upload_tabela" name="upload_tabela" class='span12'>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p><br/>
                <input type="submit" class="btn btn-success" name="btn_acao" value='Gravar' />
                <a href="cadastro_tabela_preco.php?listar_todos=true" class="btn">Listar Todos</a>
                
            </p><br/><br/>
        </form><br />
    <?php 
    } else {
        //CADASTRA TABELA
    ?>
        <form name='frm_tabela' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'  enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <?php if (isset($_GET["acao"]) && $_GET["acao"] == "edit") {?>
                <input type="hidden" name="tipo_acao" value="edit">
                <input type="hidden" name="contrato_tabela" value="<?php echo (isset($retorno["contrato_tabela"]) && strlen($retorno["contrato_tabela"]) > 0) ? $retorno["contrato_tabela"] : "";?>">
            <?php } else {?> 
                <input type="hidden" name="tipo_acao" value="add">
            <?php }?> 


            <div class="titulo_tabela">Cadastro da tabela de preço</div><br />

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span2'>
                    <div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='codigo'>Código</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" id="codigo" name="codigo" class='span12'  value="<?php echo (isset($retorno["codigo"]) && strlen($retorno["codigo"]) > 0) ? $retorno["codigo"] : "";?>"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='descricao'>Descrição</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" id="descricao" name="descricao" class='span12'  value="<?php echo (isset($retorno["descricao"]) && strlen($retorno["descricao"]) > 0) ? $retorno["descricao"] : "";?>" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span1'>
                    <div class='control-group'>
                        <label class='control-label' for='ativo'>Ativo</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="checkbox" name="ativo" <?php echo (isset($retorno["ativo"]) && $retorno["ativo"] == 't') ? "checked" : "";?> id="ativo" value="t">
                                <div><strong></strong></div>
                            </div>  
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            
            <p><br/>
                <input type="submit" class="btn btn-success" name="btn_acao" value='Gravar' />
                <a href="cadastro_tabela_preco.php?listar_todos=true" class="btn">Listar Todos</a>
            </p><br/><br/>
        </form><br />
    <?php }?>

    <?php
        if (!$dadosTabela["erro"] && isset($_GET["listar_todos"]) && $_GET["listar_todos"] == true) {
            echo "
                <table class='table table-striped table-bordered table-hover table-fixed'>
                    <thead>
                        <tr class='titulo_tabela'>
                            <th colspan='4'>Relação das Tabelas Cadastradas</th>
                        </tr>
                        <tr class='titulo_coluna'>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>";
                    foreach ($dadosTabela as $k => $rows) {

                        $ativo = ($rows["ativo"] == 't') ? "<img title='Ativo' src='imagens/status_verde.png'>" : "<img title='Inativo' src='imagens/status_vermelho.png'>";

                        echo "
                            <tr>
                                <td class='tal'>".$rows["codigo"]."</td>
                                <td class='tal'>".$rows["descricao"]."</td>
                                <td class='tac'>".$ativo."</td>
                                <td class='tac'>
                                    <a href='cadastro_tabela_preco.php?upload_arquivo=true&contrato_tabela=".$rows["contrato_tabela"]."' class='btn btn-warning btn-mini'>Upload de Arquivo</a>
                                    <a href='cadastro_tabela_preco.php?acao=edit&contrato_tabela=".$rows["contrato_tabela"]."' class='btn btn-info btn-mini'>Alterar</a>

                                    <input type='button' class='btn btn-success  btn-mini ver_pecas' value='Ver Produtos'  data-tabela='".$rows["contrato_tabela"]."'/>&nbsp;
                                    <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_contrato_tabela_item&id=".$login_fabrica."*".$rows["contrato_tabela"]."'><button class='btn btn-mini btn-primary'>Log de Produtos</button></a>
                                </td>
                            </tr>";
                    }
        echo "</table>";
    }
    echo "<br /><br />";
    include "rodape.php"; 
?>
