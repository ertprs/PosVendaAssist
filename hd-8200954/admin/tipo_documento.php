<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';

if ($_POST['btn_acao'] == 'submit') {
    $msg_erro = array();

    $tipo_documento         = (int)$_POST['tipo_documento'];
    $descricao           = trim($_POST['descricao']);
    $ativo = ($_POST['ativo'] == 't') ? 'TRUE' : 'FALSE';

    if (!strlen($descricao)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'descricao';
    }

    if (!count($msg_erro)) {
        if (empty($tipo_documento)) {
            $sql = "INSERT INTO tbl_tipo_documento (
                        fabrica, 
                        descricao, 
                        ativo
                    ) VALUES (
                        $login_fabrica, 
                        '$descricao',
                        $ativo
                    )";
        } else {
            $sql = "UPDATE tbl_tipo_documento 
                       SET descricao           = '$descricao',
                           ativo               = $ativo
                     WHERE fabrica     = $login_fabrica
                       AND tipo_documento = $tipo_documento";
        }

        $res = pg_query($con, $sql);
        
        if (!pg_last_error()) {
            $msg_success = true;
            $tipo_documento = "";
            $descricao = "";
            $ativo = "";
        } else {
            $msg_erro["msg"] = "Erro ao gravar tipo de documento";
        }
    }
}

if (!empty($_GET["tipo_documento"])) {
    $tipo_documento = $_GET["tipo_documento"];

    $sql = "SELECT descricao, ativo
              FROM tbl_tipo_documento
             WHERE fabrica = $login_fabrica
               AND tipo_documento = $tipo_documento;";

    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0) {
        $descricao           = pg_fetch_result($res, 0, 'descricao');
        $ativo               = pg_fetch_result($res, 0, 'ativo');
    } else {
        $msg_erro["msg"][] = "Tipo de documento não encontrado";
    }
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE TIPO DE DOCUMENTOS";
$title_page  = "Cadastro";

if ($_GET["tipo_documento"] || strlen($tipo_documento) > 0) {
    $title_page = "Alteração de Cadastro";
}

include 'cabecalho_new.php'; 

if ($msg_success) {
?>
    <div class="alert alert-success">
        <h4>Tipo de pedido, gravado com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php 
} 

?>
    <div class="row">
        <b class="obrigatorio pull-right">* Campos obrigatórios </b>
    </div>

    <form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" action="tipo_documento.php" >
        <legend class='titulo_tabela'><?=$title_page?></legend>
        <input type="hidden" name="tipo_documento" value="<?=$tipo_documento?>" />
        <div class='row-fluid'>
        <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='descricao'>Descrição</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" name="descricao" id="descricao" size="12" class='span12' maxlength="50" value= "<?=$descricao?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group tac <?=(in_array("ativo", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label tac' for='ativo'>Ativo</label>
                        <div class='controls controls-row'>
                            <div class='span12 tac'>
                                <input type="checkbox" name="ativo" id="ativo" value="t" <?=($ativo == "t") ? "CHECKED" : ""?> />
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span2'></div>
        </div>
        <p><br/>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <button class='btn' type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
        </p><br/>
    </form>

<?php

$sql = "SELECT tipo_documento, descricao, ativo
          FROM tbl_tipo_documento
         WHERE fabrica = $login_fabrica
         ORDER BY descricao";
$res = pg_query($con, $sql);

$rowcount = pg_num_rows($res);

if ($rowcount) {
    $rows = pg_fetch_all($res);
    $trueImg  = '<div style="text-align:center"><img src="imagens/status_verde.png" title="Sim" /></div>';
    $falseImg = '<div style="text-align:center"><img src="imagens/status_vermelho.png" title="Não" /></div>';

    $tableData = array(
        'attrs' => array(
            'tableAttrs' => ' id="condicoes_cadastradas" class="table table-striped table-bordered table-hover"',
            'headerAttrs' => 'class="titulo_coluna"',
        )
    );

    foreach($rows as $i=>$row) {
        $tipo_documento = $row['tipo_documento'];
        $descricao   = $row['descricao'];
        
        $rowData = array(
            'Descrição' => "<a title='Editar tipo de documento' href='{$_SERVER['PHP_SELF']}?tipo_documento={$tipo_documento}' >{$descricao}</a>",
            'Ativo' => ($row['ativo'] == 't') ? "Sim" : "Não"
        );
        
        $tableData[] = $rowData;
    }
    echo array2table($tableData);
}

include "rodape.php"; 
?>
