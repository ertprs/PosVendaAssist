<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

include_once "../class/tdocs.class.php";
$s3 = new TDocs($con, $login_fabrica);

// if(!in_array($login_admin, array(4960,6497))){
//     $permissao = "false";
//     $msg_erro["msg"][] = "Você não tem permissão para acessar essa tela";
// }

if(strlen($_GET['fabrica']) > 0){
    $fabrica = $_GET['fabrica'];
    if($fabrica <> $login_fabrica){
        $msg_erro["msg"][] = "Nenhuma documentação encontrada";
    }
}else{
   $msg_erro["msg"][] = "Nenhuma documentação encontrada";
}

if (!count($msg_erro["msg"])) {
    $sql = "SELECT tbl_change_log.hd_chamado,
                    tbl_change_log.change_log,
                    tbl_change_log.titulo,
                    tbl_change_log.change_log_fabrica,
                    tbl_change_log.ativo,
                    tbl_change_log.change_log_interno AS rash,
                    TO_CHAR(tbl_change_log.data, 'DD/MM/YYYY') AS data,
                    tbl_fabrica.nome,
                    tbl_change_log.fabrica,
                    tbl_admin.nome_completo,
                    tbl_change_log.data_atualizacao
                FROM tbl_change_log
                JOIN tbl_admin ON tbl_admin.admin = tbl_change_log.admin
                JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_change_log.fabrica
                WHERE tbl_fabrica.fabrica = $login_fabrica
                ORDER BY tbl_change_log.data_atualizacao desc";
    $res = pg_query($con, $sql);
    $erro = pg_last_error($con);
    $resSubmit = pg_query($con, $sql);
}

$layout_menu = "gerencia";
$title = "Relatório Documentação";
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

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $("#versao").mask('9.99.99');
        $('#uploadBtn').change(function(){
            var upload = $(this).val();
            $("#uploadFile").val(upload);
        });

        // $.dataTableLoad({
        //     table: "#relatorio_documentacao",
        //     type: "custom",
        //     config: [ "pesquisa" ]
        // });

    });

    function visualizar(linha){
        $('#'+linha).show();
        $("#visualizar_"+linha).hide();
        $("#ocultar_"+linha).show();
    }
    function ocultar(linha){
        $('#'+linha).hide();
        $("#ocultar_"+linha).hide();
        $("#visualizar_"+linha).show();
    }
</script>

<?php

if(strlen($_GET['msg']) > 0){
    $erro = $_GET['msg'];
    $erro = explode(',', $erro);
    $msg_erro["msg"] = array_merge($erro);
}

if (count($msg_erro["msg"]) > 0 OR $_GET['msg'] == 'error') {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
    if($permissao == "false"){
        exit;
    }
}
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
    ?>
        <!-- </div> -->
        <table id="relatorio_documentacao" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela" role="row">
                    <th colspan="9" rowspan="1">Documentações</th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Fábrica</th>
                    <th>Versão</th>
                    <th>Data</th>
                    <th>Número Chamado</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ação</ht>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $i < $count; $i++) {
                    $fabrica            = pg_fetch_result($resSubmit, $i, 'fabrica');
                    $change_log         = pg_fetch_result($resSubmit, $i, 'change_log');
                    $hd_chamado         = pg_fetch_result($resSubmit, $i, 'hd_chamado');
                    $versao             = pg_fetch_result($resSubmit, $i, 'titulo');
                    $descricao          = pg_fetch_result($resSubmit, $i, 'change_log_fabrica');
                    $rash               = pg_fetch_result($resSubmit, $i, 'rash');
                    $tipo               = pg_fetch_result($resSubmit, $i, 'ativo');
                    $data               = pg_fetch_result($resSubmit, $i, 'data');
                    $nome               = pg_fetch_result($resSubmit, $i, 'nome');
                    $nome_completo      = pg_fetch_result($resSubmit, $i, 'nome_completo');

                    $sql_docs = "SELECT tdocs
                                    FROM tbl_tdocs
                                    WHERE fabrica = $fabrica
                                    AND tdocs_id = '$rash'";
                    $res_docs = pg_query($con, $sql_docs);
                    if(pg_num_rows($res_docs) > 0){
                        $doc_id = pg_fetch_result($res_docs, 0, 'tdocs');
                        $link_doc_tdocs = $s3->getDocumentLocation($doc_id);
                    }


                ?>
                    <tr>
                        <td class='tal' style='vertical-align: middle;'><?=$nome;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$versao;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$data;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$hd_chamado;?></td>
                        <td class='tac' style='vertical-align: middle;'>
                            <button class='btn btn-small' id='visualizar_<?=$change_log?>' onclick='visualizar(<?=$change_log?>);' >Visualizar descrição</button>
                            <button style='display:none;' class='btn btn-small' id='ocultar_<?=$change_log?>' onclick='ocultar(<?=$change_log?>);' >Ocultar descrição</button>
                        </td>
                        <td class='tac' style='vertical-align: middle;'>
                            <input type="hidden" name="change_log" value="<?=$change_log?>" />
                            <input type="hidden" name="id_fabrica" value="<?=$fabrica?>" />
                            <?php
                            if ($tipo == "f") {
                                echo "<span class='label label-important'>Inativo</span>";
                            } else {
                                echo "<span class='label label-info'>Ativo</span>";
                            }
                            ?>
                        </td>
                        <td class='tac' style='vertical-align: middle;'>
                            <a href='<?=$link_doc_tdocs?>' download>
                                <button class='btn btn-small'>Download</button>
                            </a>
                        </td>
                    </tr>
                    <tr id='<?=$change_log?>' style='display:none;'>
                        <td class='tac' colspan='8' style='vertical-align: middle;'>
                            <textarea readOnly rows='4' class='span12' ><?=$descricao;?></textarea>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        <br />
    <?php
    }else{
        echo '
        <div class="container">
        <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
        </div>
        </div>';
    }
}



include 'rodape.php';?>
