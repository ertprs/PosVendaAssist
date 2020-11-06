<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include_once(__DIR__.'/class/tdocs.class.php');
$tdocs = new TDocs($con, $login_fabrica, 'ferramenta');

if ($_POST["btn_acao"]) {
    $posto_ferramenta      = $_POST["posto_ferramenta"];
    $descricao             = trim($_POST["descricao"]);
    $grupo_ferramenta      = $_POST["grupo_ferramenta"];
    $fabricante            = trim($_POST["fabricante"]);
    $modelo                = trim($_POST["modelo"]);
    $numero_serie          = trim($_POST["numero_serie"]);
    $certificado           = trim($_POST["certificado"]);
    $validade_certificado  = $_POST["validade_certificado"];
    $boxuploader_unique_id = $_POST["boxuploader_unique_id"];
    $ativo                 = $_POST["ativo"];
    
    if (empty($descricao) || !strlen($grupo_ferramenta) || empty($fabricante) || empty($modelo) || empty($numero_serie) || empty($certificado) || empty($validade_certificado)) {
        $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
        
        if (empty($descricao)) {
            $msg_erro["campos"][] = "descricao";
        }
        
        if (!strlen($grupo_ferramenta)) {
            $msg_erro["campos"][] = "grupo_ferramenta";
        }
        
        if (empty($fabricante)) {
            $msg_erro["campos"][] = "fabricante";
        }
        
        if (empty($modelo)) {
            $msg_erro["campos"][] = "modelo";
        }
        
        if (empty($numero_serie)) {
            $msg_erro["campos"][] = "numero_serie";
        }
        
        if (empty($certificado)) {
            $msg_erro["campos"][] = "certificado";
        }
        
        if (empty($validade_certificado)) {
            $msg_erro["campos"][] = "validade_certificado";
        }
    }
    
    if (!empty($validade_certificado)) {
        list($dia, $mes, $ano) = explode("/", $validade_certificado);
        
        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"][] = traduz("Validade do certificado inválida");
            $msg_erro["campos"][] = "validade_certificado";
        } else if (strtotime("$ano-$mes-$dia") < strtotime(date("Y-m-d"))) {
            $msg_erro["msg"][] = traduz("A validade do certificado não pode ser inferior ao dia atual");
            $msg_erro["campos"][] = "validade_certificado";
        } else {
            $validade_certificado = "$ano-$mes-$dia";
        }
    }
    
    if ($boxuploader_unique_id != $posto_ferramenta) {
        $anexos = $tdocs->getByHashTemp($boxuploader_unique_id);
    } else {
        $anexos = $tdocs->getdocumentsByRef($posto_ferramenta);
    }
    
    if (!count($anexos)) {
        $msg_erro["msg"][] = traduz("É obrigatório anexar um arquivo");
    }
    
    if (empty($msg_erro["msg"])) {
        pg_query($con, "BEGIN");
        
        $ativo = (empty($ativo)) ? "f" : $ativo;
        
        if (!strlen($posto_ferramenta)) {
            $sql = "
                INSERT INTO tbl_posto_ferramenta
                (
                    fabrica, 
                    posto, 
                    grupo_ferramenta, 
                    descricao, 
                    fabricante, 
                    modelo, 
                    numero_serie, 
                    certificado, 
                    validade_certificado, 
                    ativo
                ) VALUES (
                    {$login_fabrica},
                    {$login_posto},
                    {$grupo_ferramenta},
                    '{$descricao}',
                    '{$fabricante}',
                    '{$modelo}',
                    '{$numero_serie}',
                    '{$certificado}',
                    '{$validade_certificado}',
                    '{$ativo}'
                ) RETURNING posto_ferramenta
            ";
        } else {
            $sql = "
                SELECT * 
                FROM tbl_posto_ferramenta 
                WHERE fabrica = {$login_fabrica} 
                AND posto = {$login_posto} 
                AND posto_ferramenta = {$posto_ferramenta}
            ";
            $res = pg_query($con, $sql);
            $antigo = pg_fetch_assoc($res);
            
            $colunas = array();
            if (
                ($antigo["ativo"] == "f" && $ativo == "t")
                || ($antigo["certificado"] != $certificado)
                || ($antigo["validade_certificado"] != $validade_certificado)
             ) {
                $colunas[] = "aprovado = null";
                $colunas[] = "reprovado = null";
            }
            
            if (count($colunas) > 0) {
                $colunas = ", ".implode(", ", $colunas);
            } else {
                $colunas = null;
            }
            
            $sql = "
                UPDATE tbl_posto_ferramenta SET
                    grupo_ferramenta     = {$grupo_ferramenta},
                    descricao            = '{$descricao}',
                    fabricante           = '{$fabricante}',
                    modelo               = '{$modelo}',
                    numero_serie         = '{$numero_serie}',
                    certificado          = '{$certificado}',
                    validade_certificado = '{$validade_certificado}',
                    ativo                = '{$ativo}'
                    {$colunas}
                WHERE fabrica = {$login_fabrica}
                AND posto = {$login_posto}
                AND posto_ferramenta = {$posto_ferramenta}
            ";
        }
        $res = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0) {
            $msg_erro["msg"][] = traduz("Erro ao gravar ferramenta");
        } else {
            if (!strlen($posto_ferramenta)) {
                $res = pg_fetch_assoc($res);
                
                if (!$tdocs->updateHashTemp($boxuploader_unique_id, $res["posto_ferramenta"])) {
                    $msg_erro["msg"][] = traduz("Erro ao gravar ferramenta");
                }
            }
        }
        
        if (empty($msg_erro["msg"])) {
            pg_query($con, "COMMIT");
            $msg_success = true;
            
            if (!strlen($posto_ferramenta)) {
                unset($_POST);
            }
        } else {
            pg_query($con, "ROLLBACK");
        }
    }
}

if (strlen($_GET["posto_ferramenta"]) > 0 && !$_POST) {
    $posto_ferramenta = $_GET["posto_ferramenta"];
    
    $sql = "
        SELECT *
        FROM tbl_posto_ferramenta
        WHERE fabrica = {$login_fabrica}
        AND posto = {$login_posto}
        AND posto_ferramenta = {$posto_ferramenta}
    ";
    $res = pg_query($con, $sql);
    
    if (!pg_num_rows($res)) {
        $msg_erro["msg"][] = traduz("Ferramenta não encontrada");
    } else {
        $_RESULT = array(
            "posto_ferramenta"     => $posto_ferramenta,
            "descricao"            => pg_fetch_result($res, 0, "descricao"),
            "grupo_ferramenta"     => pg_fetch_result($res, 0, "grupo_ferramenta"),
            "fabricante"           => pg_fetch_result($res, 0, "fabricante"),
            "modelo"               => pg_fetch_result($res, 0, "modelo"),
            "numero_serie"         => pg_fetch_result($res, 0, "numero_serie"),
            "certificado"          => pg_fetch_result($res, 0, "certificado"),
            "validade_certificado" => date("d/m/Y", strtotime(pg_fetch_result($res, 0, "validade_certificado"))),
            "ativo"                => pg_fetch_result($res, 0, "ativo")
        );
    }
}

$layout_menu = 'cadastro';
$title       = traduz('Cadastro de Ferramentas');
$title_page  = traduz('Cadastro');

include 'cabecalho_new.php';

$plugins = array(
    'datepicker',
    'mask',
    'shadowbox'
);
include 'plugin_loader.php';

if ($msg_success) {
?>
    <div class='alert alert-success' >
        <h4><?=traduz("Ferramenta, gravada com sucesso")?></h4>
    </div>
<?php
}

if (count($msg_erro['msg']) > 0) {
?>
    <div class='alert alert-error' >
        <h4><?=implode('<br />', $msg_erro['msg'])?></h4>
    </div>
<?php
}
?>

<div class='row' >
    <b class='obrigatorio pull-right' >  * <?=traduz("Campos Obrigatórios")?> </b>
</div>

<form method='POST' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <input type='hidden' name='posto_ferramenta' value='<?=getValue("posto_ferramenta")?>' />
    <br />

    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span3' >
            <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='descricao' ><?=traduz("Descrição")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='descricao' name='descricao' class='span12' value='<?=getValue("descricao")?>' maxlength='50' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2' >
            <div class='control-group <?=(in_array("grupo_ferramenta", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='grupo_ferramenta' ><?=traduz("Grupo da Ferramenta")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <select id='grupo_ferramenta' name='grupo_ferramenta' class='span12' />
                            <option value='' ><?=traduz("Selecione")?></option>
                            <?php
                            $sql = "
                                SELECT grupo_ferramenta, descricao
                                FROM tbl_grupo_ferramenta
                                WHERE fabrica = {$login_fabrica}
                                AND ativo IS TRUE
                            ";
                            $res = pg_query($con, $sql);
                            if (pg_num_rows($res) > 0) {
                                while ($row = pg_fetch_object($res)) {
                                    $selected = (getValue('grupo_ferramenta') == $row->grupo_ferramenta) ? 'selected' : '';
                                    echo "<option value='{$row->grupo_ferramenta}' {$selected} >{$row->descricao}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span3' >
            <div class='control-group <?=(in_array("fabricante", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='fabricante' ><?=traduz("Fabricante")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='fabricante' name='fabricante' class='span12' value='<?=getValue("fabricante")?>' maxlength='50' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span3' >
            <div class='control-group <?=(in_array("modelo", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='modelo' ><?=traduz("Modelo")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='modelo' name='modelo' class='span12' value='<?=getValue("modelo")?>' maxlength='30' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span3' >
            <div class='control-group <?=(in_array("numero_serie", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='numero_serie' ><?=traduz("Número de Série")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='numero_serie' name='numero_serie' class='span12' value='<?=getValue("numero_serie")?>' maxlength='30' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2' >
            <div class='control-group <?=(in_array("certificado", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='certificado' ><?=traduz("Certificado")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='certificado' name='certificado' class='span12' value='<?=getValue("certificado")?>' maxlength='30' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span3' >
            <div class='control-group <?=(in_array("validade_certificado", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='validade_certificado' ><?=traduz("Validade do Certificado")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='validade_certificado' name='validade_certificado' class='span12' value='<?=getValue("validade_certificado")?>' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2' >
            <div class='control-group' >
                <label class='control-label' for='ativo' ><?=traduz("Ativo")?></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <label class='checkbox' >
                            <input type='checkbox' id='ativo' name='ativo' value='t' <?=(getValue("ativo") == "t") ? "checked" : ""?> /> 
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    if (strlen(getValue("boxuploader_unique_id")) > 0) {
        $unique_id = getValue("boxuploader_unique_id");
    } else {
        if (!strlen(getValue("posto_ferramenta"))) {
            $unique_id = $login_fabrica.$login_posto.date("dmYHisu");
        } else {
            $unique_id = getValue("posto_ferramenta");
        }
    }
    
    $boxUploader = array(
        'div_id' => 'div_anexos',
        'context' => 'ferramenta',
        'unique_id' => $unique_id,
        'hash_temp' => (!strlen(getValue('posto_ferramenta'))) ? true : false
    );
    include 'box_uploader.php';
    ?>
    
    <input type="hidden" name="boxuploader_unique_id" value="<?=$unique_id?>" />

    <p>
        <br />
        <input type='hidden' id='btn_click' name='btn_acao' />
        <button class='btn' type='button' onclick='submitForm($(this).parents("form"));' ><?=traduz("Gravar")?></button>
        <?php
        if (strlen(getValue('posto_ferramenta')) > 0) {
        ?>
            <button class='btn btn-warning' type='button' onclick='window.location = "<?=$_SERVER["PHP_SELF"]?>";' ><?=traduz("Limpar")?></button>
        <?php
        }
        ?>
    </p>
    <br />
</form>
</div>

<?php
$sqlVencimento = "
SELECT pf.*, gf.descricao AS grupo_ferramenta_descricao, (pf.validade_certificado - CURRENT_DATE) AS dias_vencimento
FROM tbl_posto_ferramenta pf
INNER JOIN tbl_grupo_ferramenta gf ON gf.grupo_ferramenta = pf.grupo_ferramenta AND gf.fabrica = {$login_fabrica}
WHERE pf.fabrica = {$login_fabrica}
AND pf.posto = {$login_posto}
AND pf.ativo IS TRUE
AND pf.aprovado IS NOT NULL
AND ((pf.validade_certificado - CURRENT_DATE) <= 60)
ORDER BY dias_vencimento ASC, pf.data_input ASC
";
$resVencimento = pg_query($con, $sqlVencimento);

if (pg_num_rows($resVencimento) > 0) {
?>
    <table class='table table-striped table-bordered table-hover table-normal table-center' >
        <thead>
            <tr class='error' >
                <th colspan='10' ><?=traduz("Ferramentas com o certificado próximo do vencimento")?></th>
            </tr>
            <tr class='titulo_coluna' >
                <th><?=traduz("Descrição")?></th>
                <th><?=traduz("Grupo da Ferramenta")?></th>
                <th><?=traduz("Fabricante")?></th>
                <th><?=traduz("Modelo")?></th>
                <th><?=traduz("Número de Série")?></th>
                <th><?=traduz("Certificado")?></th>
                <th><?=traduz("Validade do Certificado")?></th>
                <th><?=traduz("Dias para Vencer")?></th>
                <th><?=traduz("Anexo(s)")?></th>
                <th><?=traduz("Ação")?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($resVencimento)) {
            ?>
                <tr>
                    <td><?=$row->descricao?></td>
                    <td><?=$row->grupo_ferramenta_descricao?></td>
                    <td><?=$row->fabricante?></td>
                    <td><?=$row->modelo?></td>
                    <td><?=$row->numero_serie?></td>
                    <td><?=$row->certificado?></td>
                    <td><?=date("d/m/Y", strtotime($row->validade_certificado))?></td>
                    <td class='tac' ><?=($row->dias_vencimento > 0) ? $row->dias_vencimento : "<span class='label label-important'>".traduz("Vencido")."</span>"?></td>
                    <td class='tac' nowrap >
                    <?php
                    $boxUploader = array(
                        'context' => 'ferramenta',
                        'titulo' => traduz('Ferramenta')." {$row->descricao} - ".traduz('Anexo(s)'),
                        'unique_id' => $row->posto_ferramenta,
                        'div_id' => 'vencimento'.$row->posto_ferramenta
                    );
                    include 'box_uploader_viewer.php';
                    ?>
                    </td>
                    <td>
                        <button type='button' class='btn btn-info' onclick='window.location = "<?="{$_SERVER["PHP_SELF"]}?posto_ferramenta={$row->posto_ferramenta}"?>";' ><?=traduz("Alterar")?></button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <br />
<?php
}

$sql = "
    SELECT
        pf.*,
        gf.descricao AS grupo_ferramenta_descricao
    FROM tbl_posto_ferramenta pf 
    INNER JOIN tbl_grupo_ferramenta gf ON gf.grupo_ferramenta = pf.grupo_ferramenta AND gf.fabrica = {$login_fabrica}
    WHERE pf.fabrica = {$login_fabrica} 
    AND pf.posto = {$login_posto}
";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
?>
    <table class='table table-striped table-bordered table-hover table-large table-center' >
        <thead>
            <tr class='titulo_coluna' >
                <th colspan='11' ><?=traduz("Ferramentas Cadastradas")?></th>
            </tr>
            <tr class='titulo_coluna' >
                <th><?=traduz("Descrição")?></th>
                <th><?=traduz("Grupo da Ferramenta")?></th>
                <th><?=traduz("Fabricante")?></th>
                <th><?=traduz("Modelo")?></th>
                <th><?=traduz("Número de Série")?></th>
                <th><?=traduz("Certificado")?></th>
                <th><?=traduz("Validade do Certificado")?></th>
                <th><?=traduz("Ativo")?></th>
                <th><?=traduz("Status")?></th>
                <th><?=traduz("Anexo(s)")?></th>
                <th><?=traduz("Ação")?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($res)) {
                if (!empty($row->aprovado)) {
                    $status = '<span class="label label-success">'.traduz('Aprovado').'</span>';
                } else if (!empty($row->reprovado)) {
                    $status = '<span class="label label-important">'.traduz('Reprovado').'</span>';
                } else {
                    $status = '<span class="label label-warning">'.traduz('Aguardando Aprovação').'</span>';
                }
                ?>
                <tr>
                    <td><?=$row->descricao?></td>
                    <td><?=$row->grupo_ferramenta_descricao?></td>
                    <td><?=$row->fabricante?></td>
                    <td><?=$row->modelo?></td>
                    <td><?=$row->numero_serie?></td>
                    <td><?=$row->certificado?></td>
                    <td><?=date("d/m/Y", strtotime($row->validade_certificado))?></td>
                    <td class='tac' ><?=($row->ativo == "t") ? "<img title='".traduz('Ativo')."' src='imagens/status_verde.png' >" : "<img title='".traduz('Inativo')."' src='imagens/status_vermelho.png' >" ?></td>
                    <td class='tac' ><?=$status?></td>
                    <td class='tac' nowrap >
                    <?php
                    $boxUploader = array(
                        'context' => 'ferramenta',
                        'titulo' => traduz('Ferramenta')." {$row->descricao} - ".traduz('Anexo(s)'),
                        'unique_id' => $row->posto_ferramenta,
                        'div_id' => $row->posto_ferramenta
                    );
                    include 'box_uploader_viewer.php';
                    ?>
                    </td>
                    <td>
			<?php
			if(empty($row->aprovado) && empty($row->reprovado)){
			?>
                        <button type='button' class='btn btn-info' onclick='window.location = "<?="{$_SERVER["PHP_SELF"]}?posto_ferramenta={$row->posto_ferramenta}"?>";' ><?=traduz("Alterar")?></button>
			<?php
			}
			?>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
}
?>

<script>
    
$(function(){
    Shadowbox.init();
    $('#validade_certificado').mask('99/99/9999').datepicker();
})    
    
</script>

<?php
include 'rodape.php';
