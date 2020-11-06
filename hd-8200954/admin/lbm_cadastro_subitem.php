<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';

use model\ModelHolder;

if ($login_fabrica == 158) {
    if ($_serverEnvironment == "production") {
        $chave_persys = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
    }else{
        $chave_persys = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
    }

    require_once "../class/importa_arquivos/ImportaArquivo.php";
}

if($_GET['produto']){
    $produto = $_GET['produto'];
}

function verificaPeca($referencia){
    global $con;
    global $login_fabrica;
    $sql = "SELECT peca FROM tbl_peca where referencia = '$referencia' AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) == 0){
        return false;
    }
    return pg_fetch_result($res, 0, "peca");
}

function delete_dba($table, $condicao){
    global $con;

    if($table != "" && $condicao != ""){
        pg_query($con, "BEGIN TRANSACTION");

        $sql = "DELETE FROM ".$table." WHERE ".$condicao."";
        pg_query($con, $sql);

        if(pg_last_error($con)){
            pg_query($con, "ROLLBACK");
            return false;

        } else {
            pg_query($con, "COMMIT");
            return true;
        }
    }
    return false;
}

if ($_POST["btn_acao"] == "importar_txt") {
    try {
        $arquivo     = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
        $produto     = $_POST['produto'];
        $tmpPathInfo = pathinfo($arquivo['tmp_name']);
        $pathInfo    = pathinfo($arquivo['name']);

        if (!in_array($pathInfo["extension"], array('csv', "txt" ))) {
            throw new Exception("Extensão do arquivo deve ser CSV ou TXT");
        }

        $maxFileSize = 2048000;

        if ($arquivo["size"] > $maxFileSize) {
            throw new Exception("Arquivo maior do que o permitido (2MB)");
        }

        $path = $tmpPathInfo["dirname"]."/".$tmpPathInfo["basename"];

        $fileColumns = array("produto","peca","qtde_peca","acao","subitem","descricao_subitem","qtde_subitem","acao_subitem");

        $hashTableFields = array("tbl_lista_basica" => $fileColumns );

        $separator = ";";

        $importaArquivo = new ImportaArquivo($path, $separator, $fileColumns, $hashTableFields);

        $importaArquivo->readFile();

        $rows = $importaArquivo->getDataRows();
        
        $auditorLog = new AuditorLog();    
        $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto" => $produto, "fabrica" => $login_fabrica) );
        $lista_peca_gravada = array();
        $count_peca         = 0;
        $verificar_array    = array(
            "produto"           => array("código do produto", "codigo do produto"),
            "peca"              => array("código da peça", "codigo da peca"),
            "qtde_peca"         => array("Quantidade da peça", "Quantidade da peca"),
            "acao"              => array("ação", "acao"),
            "subitem"           => array("código subitem", "codigo subitem"),
            "descricao_subitem" => array("descrição subitem", "descricao subitem"),
            "qtde_subitem"      => array("quantidade subitem"),
            "acao_subitem"      => array("ação subitem", "acao subitem"));
        
        for($i = 0; $i < count($rows); $i++){
            $table = array_keys($rows[$i]);
            $table = $table[0];

            if($i == 0){
                foreach ($rows[0][$table] as $key => $value) {
                    if(in_array(strtolower($value), $verificar_array[$key])){
                        $i++;
                        break;
                    }
                }
            }
            
            $currentRow               = $rows[$i][$table];
            $currentRow["referencia"] = $currentRow["peca"];
            $peca_pai                 = verificaPeca($currentRow["peca"]);
            
            if ($peca_pai) {
                $referencia_produto = $currentRow['produto'];

                $sqlProd = "SELECT produto FROM tbl_produto
                    WHERE fabrica_i    = {$login_fabrica}
                        AND referencia = '{$referencia_produto}'";
                $resProd = pg_query($con,$sqlProd);
                $produto                  = pg_fetch_result($resProd,0,produto);
                
                $lista_peca_gravada[$count_peca] = array(
                    "produto"           => "",
                    "peca"              => "",
                    "qtde_peca"         => "",
                    "acao"              => "",
                    "subitem"           => "",
                    "descricao_subitem" => "",
                    "qtde_subitem"      => "",
                    "acao_subitem"      => ""
                );
                $acaoGravacao = strtoupper($currentRow["acao"]);

                if(trim($acaoGravacao) != ""){
                    $lista_peca_gravada[$count_peca]["produto"]   = $referencia_produto;
                    $lista_peca_gravada[$count_peca]["peca"]      = $currentRow["peca"];
                    $lista_peca_gravada[$count_peca]["qtde_peca"] = $currentRow["qtde_peca"];

                    if (trim($acaoGravacao) == "INCLUIR") {
                        pg_query($con, "BEGIN TRANSACTION");
                        $array_subitem = array("subitem" => true);

                        $sql = "SELECT lista_basica, 
                                parametros_adicionais 
                            FROM tbl_lista_basica 
                            WHERE produto   = {$produto}
                                AND fabrica = {$login_fabrica}
                                AND peca    = {$peca_pai}
                                AND parametros_adicionais like '%subitem%'";
                        $res = pg_query($con, $sql);

                        if(pg_num_rows($res) == 0){
                            $sql = "INSERT INTO tbl_lista_basica (
                                    fabrica,
                                    produto,
                                    peca,
                                    qtde,
                                    admin,
                                    data_alteracao,
                                    parametros_adicionais,
                                    ativo
                                ) VALUES (
                                    {$login_fabrica},
                                    {$produto},
                                    {$peca_pai},
                                    ".$currentRow['qtde_peca'].",
                                    {$login_admin},
                                    CURRENT_TIMESTAMP,
                                    '".json_encode($array_subitem)."',
                                    TRUE
                                )";

                        } else {
                            $lista_basica          = json_decode(pg_fetch_result($res, 0, 'lista_basica'));
                            $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'));
                            strlen($parametros_adicionais) > 0 ? $parametros_adicionais->subitem = true : $parametros_adicionais = $array_subitem;

                            $sql = "UPDATE tbl_lista_basica SET 
                                    parametros_adicionais = '".json_encode($parametros_adicionais)."',
                                    qtde                  = ".$currentRow['qtde_peca'].",
                                    data_alteracao        = CURRENT_TIMESTAMP
                                WHERE tbl_lista_basica.fabrica        = {$login_fabrica}
                                    AND tbl_lista_basica.lista_basica = {$lista_basica};";
                        }
                        pg_query($con,$sql);
                        
                        if(pg_last_error($con)){
                            $msg['success'] = false;
                            $msg['msg'][]   = pg_last_error($con) . " - Erro ao salvar peça. Peça: " . $currentRow["referencia"];
                            pg_query($con, "ROLLBACK");
                            continue;

                        } else if($currentRow['subitem'] != ""){
                            pg_query($con, "COMMIT");

                            $lista_peca_gravada[$count_peca]["acao"] = "Incluído";
                            $peca_subitem                            = verificaPeca($currentRow['subitem']);

                            if($peca_subitem){
                                if($currentRow['acao_subitem'] != ""){
                                    $lista_peca_gravada[$count_peca]["subitem"]           = $currentRow["subitem"];
                                    $lista_peca_gravada[$count_peca]["descricao_subitem"] = $currentRow["descricao_subitem"];
                                    $lista_peca_gravada[$count_peca]["qtde_subitem"]      = $currentRow["qtde_subitem"];

                                    if(trim(strtoupper($currentRow['acao_subitem'])) == "INCLUIR"){
                                        pg_query($con, "BEGIN TRANSACTION");

                                        $sql = "SELECT peca_container FROM tbl_peca_container 
                                            WHERE fabrica      = {$login_fabrica}
                                                AND peca_mae   = {$peca_pai}
                                                AND peca_filha = {$peca_subitem}
                                                AND produto    = {$produto}";
                                        $res_peca_container = pg_query($con, $sql);

                                        if(pg_num_rows($res_peca_container) == 0){
                                            $sql = "INSERT INTO tbl_peca_container (
                                                    fabrica,
                                                    peca_mae,
                                                    peca_filha,
                                                    produto,
                                                    qtde
                                                ) VALUES (
                                                    {$login_fabrica},
                                                    {$peca_pai},
                                                    {$peca_subitem},
                                                    {$produto},
                                                    ".$currentRow['qtde_subitem']."
                                                )";

                                        } else {
                                            $sql = "UPDATE tbl_peca_container SET qtde = ".$currentRow['qtde_subitem']."
                                                WHERE fabrica      = {$login_fabrica}
                                                    AND peca_mae   = {$peca_pai}
                                                    AND peca_filha = {$peca_subitem}
                                                    AND produto    = {$produto}";
                                        }
                                        pg_query($con, $sql);

                                        if(pg_last_error($con)){
                                            $msg['success'] = false;
                                            $msg['msg'][]   = pg_last_error($con);
                                            pg_query($con, "ROLLBACK");
                                            continue;

                                        } else {
                                            $lista_peca_gravada[$count_peca]["acao_subitem"] = "Incluído";
                                            pg_query($con, "COMMIT TRANSACTION");
                                        }

                                    } else if(trim(strtoupper($currentRow['acao_subitem'])) == "EXCLUIR"){
                                        $condicao = " peca_mae = {$peca_pai}
                                                AND fabrica    = {$login_fabrica}
                                                AND peca_filha = {$peca_subitem}";
                                        
                                        if(delete_dba("tbl_peca_container",$condicao)){
                                            $lista_peca_gravada[$count_peca]["acao_subitem"] = "Excluído";

                                        } else {
                                            $msg['msg'][] = pg_last_error($con);
                                        }
                                    } else {
                                        $msg_erro["msg"]["acao"][] = $currentRow["subitem"];
                                        continue;
                                    }
                                } else {
                                    $msg_erro["msg"]["acao"][] = $currentRow["subitem"];
                                    continue;
                                }
                            } else {
                                if(strlen($currentRow["subitem"]) == 0){
                                    $msg_erro["msg"]["vazio"] = "Peça(s) de subitem não informada(s)";
                                }  else {
                                    $erro[] = "Subitem - ".$currentRow["subitem"];
                                }
                                continue;
                            }
                        } else {
                            $lista_peca_gravada[$count_peca]["acao"] = "Incluído";
                            pg_query($con, "COMMIT TRANSACTION");
                        }

                    } else if (trim($acaoGravacao) == "EXCLUIR") {
                        $condicao = "peca_mae = {$peca_pai}
                                  AND fabrica = {$login_fabrica}";

                        if(delete_dba("tbl_peca_container", $condicao)){
                            $condicao = "peca   = {$peca_pai}
                                    AND produto = {$produto}
                                    AND fabrica = {$login_fabrica}";

                            if(delete_dba("tbl_lista_basica", $condicao)){
                                $lista_peca_gravada[$count_peca]["acao"] = "Excluído";

                                if($currentRow["subitem"] != ""){
                                    $lista_peca_gravada[$count_peca]["subitem"] = "Todos os subitens cadastrado nesta peça, foram excluídos.";
                                }

                            } else {
                                $msg_erro["msg"][] = "Erro ao excluir a peça - ".$currentRow["peca"]."!";
                            }
                        } else {
                            $msg_erro["msg"][] = "Erro ao excluir todos os subitens da peça - ".$currentRow["peca"]." para poder proceder na exclusão da peça mensionada!";
                        }
                    }
                }
            } else {
                if(strlen($currentRow["peca"]) == 0){
                    $msg_erro["msg"]["vazio"] = "Peça(s) não informada(s)";
                }  else {
                    $erro[] = $currentRow["peca"];
                }
                continue;
            }
            $count_peca++;
        }

        if(count($erro) > 0){
            $pecas_erradas     = implode(", ",$erro);
            $msg_erro["msg"][] = "Peça(s) não encontada(s): ".$pecas_erradas;
        }

        if(count($msg_erro["msg"]["acao"]) > 0){
            $pecas_erradas = implode(", ", $msg_erro["msg"]["acao"]);
            $pecas_erradas = "Subitem - Peça(s) ".$pecas_erradas." sem ação declarada";
        }

        $auditorLog->retornaDadosTabela()
                   ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

    } catch(Exception $ex) {
        $msg_erro["msg"][] = $ex->getMessage();
        $msg_erro["msg"][] = "Tente Novamente.";
    }
}

$layout_menu = "cadastro";
$title       = "CADASTRAMENTO DE SUBITEM NA LISTA BÁSICA";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete"
);

include("plugin_loader.php");
?>

</style>
<link type="text/css" href="../js/pikachoose/css/css3.css" rel="stylesheet" />
<script type="text/javascript" src="../js/pikachoose/js/jquery.jcarousel.min.js"></script>
<script type="text/javascript" src="../js/pikachoose/js/jquery.touchwipe.min.js"></script>
<script type="text/javascript" src="../js/pikachoose/js/jquery.pikachoose.js"></script>
<link href="../js/imgareaselect/css/imgareaselect-default.css" rel="stylesheet" type="text/css"/>
<link href="../js/imgareaselect/css/imgareaselect-animated.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="../js/imgareaselect/js/jquery.imgareaselect.js"></script>
<script type="text/javascript" src="../js/ExplodeView.js"></script>
<script type="text/javascript" src="../js/jquery.form.js"></script>

<script type="text/javascript" src="plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>

<style type="text/css">
    .icon-edit,.icon-remove-sign{
        display: none;
        float:left;
        padding: 3px;
    }

    table > tbody > tr > td {
        text-align: center !important;
    }

    .message_upload {
        margin-right: 1%;
        margin-left: 1%;
        width: 90%;
        white-space: normal;
    }
</style>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? }

if ($_GET['msg']) {
    $msg = array(  
        "gravar"    => "Itens gravados com sucesso",
        "exclui"    => "Lista básica excluída com sucesso",
        "importar"  => "Itens importados com sucesso",
        "type"      => "Type duplicado com sucesso",
        "duplicar"  => "Lista básica duplicada com sucesso"
    ); ?>
    <div class="alert alert-success">
        <h4><?=$msg[$_GET['msg']]?></h4>
    </div>
<? } ?>

    <div class="row">
        <b class="obrigatorio pull-right">* Campos obrigatórios</b>
    </div>

    <? if ($login_fabrica == 158) { ?>
        <form name='frm_lbm_excel' METHOD='POST' action='<?=$PHP_SELF?>' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <div class="titulo_tabela" >Realizar Upload de Arquivo Lista Básica com Subitem</div>
            <input type='hidden' name='btn_lista' value='listar'>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <br />
            <span class="label label-important message_upload">Layout do arquivo: Código do Produto, Código da Peça, Quantidade da Peça, Ação (INCLUIR ou EXCLUIR), Código Subitem, Descrição Subitem, Quantidade Subitem e Ação Subitem (INCLUIR ou EXCLUIR), separados por ponto e vírgula (;)</span>
            <br /><br />

            <div class="row-fluid" >
                <div class="span2" ></div>

                <div class="span5" >
                    <div class="control-group <?=(in_array('arquivo', $msg_erro['campos'])) ? 'error' : ''?>" >
                        <label class="control-label" for="arquivo" >Arquivo CSV / TXT</label>

                        <div class="controls controls-row" >
                            <div class="span12" >
                                <h5 class='asteristico'>*</h5>
                                <input type="file" name="arquivo" id="arquivo" class="span12" />
                            </div>
                        </div>

                    </div>
                </div>

                <div class="span2">
                    <div class="controls controls-row" >
                        <div class="span8" >
                            <br />
                            <input type="button" class="btn btn-default" id="btn_acao" onclick="submitForm($(this).parents('form'),'importar_txt');" value="Realizar Upload" />
                        </div>
                    </div>

                </div>

            </div>

        </form>
    <? } 
    if($lista_peca_gravada != ""){
        ?>
        <form name='frm_lbm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline'>
            <table class='table table-striped table-bordered table-hover table-large pecas'>
                <thead>
                    <tr class="titulo_coluna">
                        <th colspan="8" class="titulo_tabela ">Peças Processadas Corretamente</th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th>Produto</th>
                        <th>Peça</th>
                        <th>Quantidade</th>
                        <th>Ação</th>
                        <th>Subitem</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for($i = 0; $i < count($lista_peca_gravada); $i++){
                        if($lista_peca_gravada[$i]["produto"] != ""){
                        ?>
                        <tr>
                            <td><?=$lista_peca_gravada[$i]["produto"]?></td>
                            <td><?=$lista_peca_gravada[$i]["peca"]?></td>
                            <td><?=$lista_peca_gravada[$i]["qtde_peca"]?></td>
                            <td class="alert alert-success"><b><?=$lista_peca_gravada[$i]["acao"]?></b></td>
                            <?php
                            if($lista_peca_gravada[$i]["subitem"] != "" && $lista_peca_gravada[$i]["descricao_subitem"] == ""){
                                ?>
                                <td colspan="4"><?=$lista_peca_gravada[$i]["subitem"]?></td>
                                <?php
                            } else {
                                ?>
                                <td><?=$lista_peca_gravada[$i]["subitem"]?></td>
                                <td><?=$lista_peca_gravada[$i]["descricao_subitem"]?></td>
                                <td><?=$lista_peca_gravada[$i]["qtde_subitem"]?></td>
                                <td class="alert alert-success"><b><?=$lista_peca_gravada[$i]["acao_subitem"]?></b></td>
                                <?php
                            }
                            ?>
                            
                        </tr>
                        <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </form>
        <?php
    }
    ?>
    
<?php
include "rodape.php";
?>
