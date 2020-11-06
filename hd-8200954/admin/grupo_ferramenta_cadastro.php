<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "cadastros";

include "autentica_admin.php";

if ($_POST["btn_acao"] == "submit") {
    $descricao = trim($_POST["descricao"]);
    $ativo = $_POST["ativo"];
    
    if (empty($descricao)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
    }
    
    if (empty($ativo)) {
        $ativo = "f";
    }
    
    if (empty($msg_erro["msg"])) {
        $grupo_ferramenta = $_POST["grupo_ferramenta"];
        
        if (!strlen($grupo_ferramenta)) {
            $sql = "
                INSERT INTO tbl_grupo_ferramenta
                (fabrica, descricao, ativo)
                VALUES
                ({$login_fabrica}, '{$descricao}', '{$ativo}')
            ";
        } else {
            $sql = "
                UPDATE tbl_grupo_ferramenta SET
                    descricao = '{$descricao}',
                    ativo = '{$ativo}'
                WHERE fabrica = {$login_fabrica}
                AND grupo_ferramenta = {$grupo_ferramenta}
            ";
        }
        $res = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0) {
            $msg_erro["msg"][] = "Erro ao gravar grupo de ferremantas";
        } else {
            $msg_success = true;
            if (!strlen($grupo_ferramenta)) {
                unset($_POST);
            }
        }
    }
}

if (!empty($_GET["grupo_ferramenta"] && !$_POST)) {
    $grupo_ferramenta = $_GET["grupo_ferramenta"];
    
    $sql = "
        SELECT descricao, ativo
        FROM tbl_grupo_ferramenta
        WHERE fabrica = {$login_fabrica}
        AND grupo_ferramenta = {$grupo_ferramenta}
    ";
    $res = pg_query($con, $sql);
    
    if (!pg_num_rows($res)) {
        $msg_erro["msg"][] = "Grupo de Ferramentas não encontrado";
    } else {
        $_RESULT["grupo_ferramenta"] = $grupo_ferramenta;
        $_RESULT["descricao"]        = pg_fetch_result($res, 0, "descricao");
        $_RESULT["ativo"]            = pg_fetch_result($res, 0, "ativo");
    }
}

$layout_menu = "cadastro";
$title       = "Grupo de Ferramentas";
$title_page  = "Cadastro";

include "cabecalho_new.php";

if ($msg_success) {
?>
    <div class="alert alert-success" >
        <h4>Grupo de Ferramenta, gravado com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class='row' >
    <b class='obrigatorio pull-right' >  * Campos obrigatórios </b>
</div>

<form method='POST' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <input type='hidden' name='grupo_ferramenta' value='<?=getValue("grupo_ferramenta")?>' />
    <br />

    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span4' >
            <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='descricao' >Descrição</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='descricao' name='descricao' class='span12' value='<?=getValue("descricao")?>' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2' >
            <div class='control-group' >
                <label class='control-label' for='' ></label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <label class='checkbox' >
                            <input type='checkbox' id='ativo' name='ativo' value='t' <?=(getValue("ativo") == "t") ? "checked" : ""?> /> Ativo
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p>
        <br />
        <input type='hidden' id='btn_click' name='btn_acao' />
        <button class='btn' type='button' onclick='submitForm($(this).parents("form"));' >Gravar</button>
        <?php
        if (strlen(getValue('grupo_ferramenta')) > 0) {
        ?>
            <button class='btn btn-warning' type='button' onclick='window.location = "<?=$_SERVER["PHP_SELF"]?>";' >Limpar</button>
        <?php
        }
        ?>
    </p>
    <br />
</form>

<?php
$sql = "
    SELECT grupo_ferramenta, descricao, ativo
    FROM tbl_grupo_ferramenta
    WHERE fabrica = {$login_fabrica}
";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
?>
    <table id='result' class='table table-striped table-bordered table-hover table-normal' >
        <thead>
            <tr class='titulo_coluna' >
                <th colspan='3' >Grupos de Ferramentas cadastrados</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Descrição</th>
                <th>Ativo</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($res)) {
            ?>
                <tr>
                    <td><?=$row->descricao?></td>
                    <td class="tac" ><?=($row->ativo == "t") ? "<img title='Ativo' src='imagens/status_verde.png' >" : "<img title='Inativo' src='imagens/status_vermelho.png' >" ?></td>
                    <td class="tac" >
                        <button type='button' class='btn btn-info' onclick='window.location = "<?="{$_SERVER["PHP_SELF"]}?grupo_ferramenta={$row->grupo_ferramenta}"?>";' >Alterar</button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
}

include "rodape.php";
?>