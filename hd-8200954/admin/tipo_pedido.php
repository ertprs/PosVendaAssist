<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';

if ($_POST['btn_acao'] == 'submit') {
    $msg_erro = array();

    $tipo_pedido         = (int)$_POST['tipo_pedido'];
    $descricao           = trim($_POST['descricao']);
    $codigo              = trim($_POST['codigo']);
    $garantia_antecipada = ($_POST['garantia_antecipada'] == 't') ? 'TRUE' : 'FALSE';
    $uso_consumo         = ($_POST['uso_consumo'] == 't')         ? 'TRUE' : 'FALSE';

    if ($login_fabrica == 156) {
        $ativo   = ($_POST['ativo'])   ? 'TRUE' : 'FALSE';
        $visivel = ($_POST['visivel']) ? 'TRUE' : 'FALSE';
    } else {
        $ativo = $visivel = 'TRUE'; // valor DEFAULT da tabela para o resto de fábricas
    }
    if (!strlen($descricao)) {
        $msg_erro['msg']['obg'] = traduz('Preencha os campos obrigatórios');
        $msg_erro['campos'][]   = 'descricao';
    }

    if (!strlen($codigo)) {
        $msg_erro['msg']['obg'] = traduz('Preencha os campos obrigatórios');
        $msg_erro['campos'][]   = 'codigo';
    }

    if (!count($msg_erro)) {
        if (empty($tipo_pedido)) {
            $sql = "INSERT INTO tbl_tipo_pedido (
                        fabrica, 
                        codigo, 
                        descricao, 
                        garantia_antecipada,
                        uso_consumo,
                        ativo,
                        visivel
                    ) VALUES (
                        $login_fabrica, 
                        '$codigo', 
                        '$descricao',
                        $garantia_antecipada,
                        $uso_consumo,
                        $ativo,
                        $visivel
                    )";
        } else {
            $sql = "UPDATE tbl_tipo_pedido 
                       SET codigo              = '$codigo',
                           descricao           = '$descricao',
                           garantia_antecipada = $garantia_antecipada,
                           visivel             = $visivel,
                           ativo               = $ativo,
                           uso_consumo         = $uso_consumo
                     WHERE fabrica     = $login_fabrica
                       AND tipo_pedido = $tipo_pedido";
        }

        $res = pg_query($con, $sql);
        
        if (!pg_last_error()) {
            $msg_success = true;
            // unset($tipo_pedido, $descricao, $codigo, $garantia_antecipada, $uso_consumo, $visivel, $ativo);
        } else {
            $msg_erro["msg"] = traduz("Erro ao gravar tipo de pedido");
        }
    }
}

if (!empty($_GET["tipo_pedido"])) {
    $tipo_pedido = $_GET["tipo_pedido"];

    $sql = "SELECT codigo, descricao, garantia_antecipada, uso_consumo, visivel, ativo
              FROM tbl_tipo_pedido
             WHERE fabrica = $login_fabrica
               AND tipo_pedido = $tipo_pedido;";

    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0) {
        $codigo              = pg_fetch_result($res, 0, 'codigo');
        $descricao           = pg_fetch_result($res, 0, 'descricao');
        $garantia_antecipada = pg_fetch_result($res, 0, 'garantia_antecipada');
        $uso_consumo         = pg_fetch_result($res, 0, 'uso_consumo');
        $visivel             = pg_fetch_result($res, 0, 'visivel');
        $ativo               = pg_fetch_result($res, 0, 'ativo');
    } else {
        $msg_erro["msg"][] = traduz("Tipo de posto não encontrado");
    }
}

$layout_menu = "cadastro";
$title       = traduz("CADASTRO DE TIPO DE PEDIDO");
$title_page  = traduz("Cadastro");

if ($_GET["tipo_pedido"] || strlen($tipo_pedido) > 0) {
    $title_page = traduz("Alteração de Cadastro");
}

include 'cabecalho_new.php'; 

if ($msg_success) {
?>
    <div class="alert alert-success">
        <h4><?=traduz('Tipo de pedido, gravado com sucesso')?></h4>
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

if ($login_fabrica != 158) {
?>
    <div class="row">
        <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
    </div>

    <form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" action="tipo_pedido.php" >
        <legend class='titulo_tabela'><?=$title_page?></legend>
        <input type="hidden" name="tipo_pedido" value="<?=$tipo_pedido?>" />
        <div class='row-fluid'>
        <div class='<?=($login_fabrica==156)?'span1':'span2'?>'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='descricao'><?=traduz('Descrição')?></label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" name="descricao" id="descricao" size="12" class='span12' maxlength="50" value= "<?=$descricao?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='codigo'><?=traduz('Código')?></label>
                        <div class='controls controls-row'>
                            <div class='span7'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" name="codigo" id="codigo" size="12" class='span12' maxlength="10" value="<?=$codigo?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group <?=(in_array("garantia_antecipada", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='garantia_antecipada'><?=traduz('Garantia Antecipada')?></label>
                        <div class='controls controls-row'>
                            <div class='span12 tac'>
                                <input type="checkbox" name="garantia_antecipada" id="garantia_antecipada" value="t" <?=($garantia_antecipada == "t") ? "CHECKED" : ""?> />
                            </div>
                        </div>
                    </div>
                </div>
            <?php if ($login_fabrica == 156): ?>
                <div class='span1'>
                    <div class='control-group <?=(in_array("visivel", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='visivel'><?=traduz('Visível')?></label>
                        <div class='controls controls-row'>
                            <div class='span12 tac'>
                                <input type="checkbox" name="visivel" id="visivel" value="t" <?=($visivel == "t") ? "CHECKED" : ""?> />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span1'>
                    <div class='control-group <?=(in_array("ativo", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='ativo'><?=traduz('Ativo')?></label>
                        <div class='controls controls-row'>
                            <div class='span12 tac'>
                                <input type="checkbox" name="ativo" id="ativo" value="t" <?=($ativo == "t") ? "CHECKED" : ""?> />
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class='span2'></div>
        </div>
        <?php if (in_array($login_fabrica, array(138))) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span2'>
                    <div class='control-group <?=(in_array("uso_consumo", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='uso_consumo'><?=traduz('Uso Consumo')?></label>
                        <div class='controls controls-row'>
                            <div class='span12 tac'>
                                <input type="checkbox" name="uso_consumo" id="uso_consumo" value="t" <?=($uso_consumo == "t") ? "CHECKED" : ""?> />
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span2'></div>
        </div>
        <?php } ?>
        <p><br/>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <button class='btn' type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Gravar')?></button>
            <?php
            if (strlen($_GET["condicao"]) > 0) {
            ?>
                <button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';"><?=traduz('Limpar')?></button>
            <?php
            }
            ?>
        </p><br/>
    </form>

<?php
}

$sql = "SELECT tipo_pedido, descricao, codigo, visivel, ativo
          FROM tbl_tipo_pedido
         WHERE fabrica = $login_fabrica
         ORDER BY descricao";
$res = pg_query($con, $sql);

$rowcount = pg_num_rows($res);

if ($rowcount) {
    $rows = pg_fetch_all($res);
    $trueImg  = '<div style="text-align:center"><img src="imagens/status_verde.png" title="'.traduz("Sim").'" /></div>';
    $falseImg = '<div style="text-align:center"><img src="imagens/status_vermelho.png" title="'.traduz("Não").'" /></div>';

    $tableData = array(
        'attrs' => array(
            'tableAttrs' => ' id="condicoes_cadastradas" class="table table-striped table-bordered table-hover"',
            'headerAttrs' => 'class="titulo_coluna"',
        )
    );

    foreach($rows as $i=>$row) {
        $tipo_pedido = $row['tipo_pedido'];
        $descricao   = $row['descricao'];

        if ($login_fabrica == 158) {
            $rowData = array(
                'Descrição' => $descricao,
                'Código' => $row['codigo']
            );
        } else {
            $rowData = array(
                'Descrição' => "<a title='".traduz("Editar tipo de pedido")."' href='{$_SERVER['PHP_SELF']}?tipo_pedido={$tipo_pedido}' >{$descricao}</a>",
                'Código' => $row['codigo']
            );
        }

        if ($login_fabrica == 156) {
            // Tipos ENTRADA e SAIDA não podem ser alterados pelo admin
            if ($row['codigo'] == 'ENTRADA' or $row['codigo'] == 'SAIDA') {
               $rowData['Descrição'] = $descricao;
            }
            $rowData['Visível'] = ($row['visivel'] == 't') ? $trueImg : $falseImg;
            $rowData['Ativo']   = ($row['ativo'] == 't')   ? $trueImg : $falseImg;
        }
        $tableData[] = $rowData;
    }
    echo array2table($tableData);
}

include "rodape.php"; 
?>
