<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';

if ($_POST['btn_acao'] == 'submit') {
    $msg_erro = array();

    $status_processo         = (int)$_POST['status_processo'];
    $descricao           = trim($_POST['descricao']);
    $ativo = ($_POST['ativo'] == 't') ? 'TRUE' : 'FALSE';
    $finaliza_processo = ($_POST['finaliza_processo'] == 't') ? 'TRUE' : 'FALSE';

    if (!strlen($descricao)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'descricao';
    }

    if (!count($msg_erro)) {
        if (empty($status_processo)) {
            $sql = "INSERT INTO tbl_status_processo (
                        fabrica, 
                        descricao, 
                        ativo,
                        finaliza_processo
                    ) VALUES (
                        $login_fabrica, 
                        '$descricao',
                        $ativo,
                        $finaliza_processo
                    )";
        } else {
            $sql = "UPDATE tbl_status_processo 
                       SET descricao           = '$descricao',
                           ativo               = $ativo,
                           finaliza_processo   = $finaliza_processo
                     WHERE fabrica     = $login_fabrica
                       AND status_processo = $status_processo";
        }

        $res = pg_query($con, $sql);
        
        if (!pg_last_error()) {
            $msg_success = true;
            $status_processo = "";
            $descricao = "";
            $ativo = "";
            $finaliza_processo = "";
        } else {
            $msg_erro["msg"] = "Erro ao gravar status processo.";
        }
    }
}

if (!empty($_GET["status_processo"])) {
    $status_processo = $_GET["status_processo"];

    $sql = "SELECT descricao, ativo, finaliza_processo
              FROM tbl_status_processo
             WHERE fabrica = $login_fabrica
               AND status_processo = $status_processo;";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0) {
        $descricao           = pg_fetch_result($res, 0, 'descricao');
        $ativo               = pg_fetch_result($res, 0, 'ativo');
        $finaliza_processo   = pg_fetch_result($res, 0, 'finaliza_processo');
    } else {
        $msg_erro["msg"][] = "Status processo não encontrado";
    }
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE STATUS PROCESSO";
$title_page  = "Cadastro";

if ($_GET["status_processo"] || strlen($status_processo) > 0) {
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

    <form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" action="status_processo.php" >
        <legend class='titulo_tabela'><?=$title_page?></legend>
        <input type="hidden" name="status_processo" value="<?=$status_processo?>" />
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
            
            <?php if ($login_fabrica == 183){ ?>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label' for='finaliza_processo'>Finaliza Processo</label>
                    <div class='controls controls-row'>
                        <select class="span12" name="finaliza_processo" id="finaliza_processo">
                            <option <?=($finaliza_processo == "t")?"selected":""?> value="t">SIM</option>
                            <option <?=(empty($finaliza_processo) OR $finaliza_processo == "f")?"selected":""?> value="f">NÃO</option>
                        </select>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class='span2'>
                <div class='control-group tac<?=(in_array("ativo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='ativo'>Ativo</label>
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

$sql = "SELECT status_processo, descricao, ativo, finaliza_processo
          FROM tbl_status_processo
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
        $status_processo = $row['status_processo'];
        $descricao   = $row['descricao'];
        
        if ($login_fabrica == 183){
            $rowData = array(
                'Descrição' => "<a title='Editar Status Processo' href='{$_SERVER['PHP_SELF']}?status_processo={$status_processo}' >{$descricao}</a>",
                'Finaliza Processo' => ($row['finaliza_processo'] == 't') ? "Sim" : "Não",
                'Ativo' => ($row['ativo'] == 't') ? "Sim" : "Não"
            );
        }else{
            $rowData = array(
                'Descrição' => "<a title='Editar Status Processo' href='{$_SERVER['PHP_SELF']}?status_processo={$status_processo}' >{$descricao}</a>",
                'Ativo' => ($row['ativo'] == 't') ? "Sim" : "Não"
            );
        }
        $tableData[] = $rowData;
    }
    echo array2table($tableData);
}

include "rodape.php"; 
?>
