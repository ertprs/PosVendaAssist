<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro,gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';


if ($_POST["btn_acao"] == "gravar") {
    $msg_erro = [];

    $roteiro_assunto    = $_POST["roteiro_assunto"];
    $assunto_desc  = utf8_encode(trim($_POST["assunto_desc"]));

    if (empty($assunto_desc)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "assunto_desc";
    } else {
        if (empty($roteiro_assunto)){
            $sqlver = "
                SELECT  COUNT(1) AS igual
                FROM    tbl_roteiro_assunto
                WHERE   fabrica = $login_fabrica
                AND     UPPER(assunto) = UPPER('$assunto_desc')
            ";
            $resver = pg_query($con,$sqlver);

            if (pg_fetch_result($resver,0,'igual') > 0) {
                $msg_erro['msg']['obg'] = "Assunto já existente";
                $msg_erro["campos"][]   = "assunto_desc";
            }
        }
    }
    
    $lbl_msg = "";
    $lbl_msg_erro = "";

    if (!count($msg_erro)) {
        if (empty($roteiro_assunto)) {
            $sql = "INSERT INTO tbl_roteiro_assunto (
                        fabrica,
                        assunto
                    ) VALUES (
                        $login_fabrica,
                        '$assunto_desc'
                    )";

            $lbl_msg = "gravado";
            $lbl_msg_erro = "gravar";
        } else {
            $sql = "UPDATE tbl_roteiro_assunto
                    SET
                        assunto = '$assunto_desc'
                    WHERE fabrica = $login_fabrica
                    AND roteiro_assunto = $roteiro_assunto";
            
            $lbl_msg = "alterado";
            $lbl_msg_erro = "alterar";
        }

        $res = pg_query($con, $sql);

        if (!pg_last_error()) {
            $msg_success = true;
            header("Location: cadastro_assunto_roteiro.php?msg=$lbl_msg");

        } else {
            $msg_erro["msg"] = "Erro ao $lbl_msg_erro o Assunto";
        }
    }
}

if ($_POST["btn_acao"] == "excluir" && 1==2) {
    $msg_erro = [];

    $roteiro_assunto    = $_POST["roteiro_assunto"];
    $assunto_desc  = utf8_encode(trim($_POST["assunto_desc"]));

    $lbl_msg = "";
    $lbl_msg_erro = "";
    
    if (!empty($roteiro_assunto)) {
        $sql = " DELETE FROM tbl_roteiro_assunto WHERE roteiro_assunto = $roteiro_assunto AND fabrica = $login_fabrica ";
        $res = pg_query($con, $sql);

        $lbl_msg = "excluido";
        $lbl_msg_erro = "excluir";

        if (!pg_last_error()) {
            $msg_success = true;
            header("Location: cadastro_assunto_roteiro.php?msg=$lbl_msg");
        } else {
            $msg_erro["msg"] = "Erro ao $lbl_msg_erro o Assunto";
        }
    } else {
        $msg_erro["msg"] = "Erro ao excluir o Assunto";
    }
}

if ($_POST["btn_acao"] == "ativar") {
    $roteiro_assunto = $_POST["roteiro_assunto"];

    $sql = "SELECT roteiro_assunto FROM tbl_roteiro_assunto WHERE fabrica = {$login_fabrica} AND roteiro_assunto = {$roteiro_assunto}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        pg_query($con, "BEGIN");

        $sql = "UPDATE tbl_roteiro_assunto SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND roteiro_assunto = {$roteiro_assunto}";
        $res = pg_query($con, $sql);

        if (!pg_last_error()) {
            pg_query($con, "COMMIT");
            echo "success";
        } else {
            pg_query($con, "ROLLBACK");
            echo "error";
        }
    }

    exit;
}

if ($_POST["btn_acao"] == "inativar") {
    $roteiro_assunto = $_POST["roteiro_assunto"];

    $sql = "SELECT roteiro_assunto FROM tbl_roteiro_assunto WHERE fabrica = {$login_fabrica} AND roteiro_assunto = {$roteiro_assunto}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        pg_query($con, "BEGIN");

        $sql = "UPDATE tbl_roteiro_assunto SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND roteiro_assunto = {$roteiro_assunto}";
        $res = pg_query($con, $sql);

        if (!pg_last_error()) {
            pg_query($con, "COMMIT");
            echo "success";
        } else {
            pg_query($con, "ROLLBACK");
            echo "error";
        }
    }

    exit;
}

if (!empty($_GET["roteiro_assunto"])) {
    $roteiro_assunto = $_GET["roteiro_assunto"];

    $sql = "SELECT assunto
            FROM tbl_roteiro_assunto
            WHERE fabrica = $login_fabrica
            AND roteiro_assunto = $roteiro_assunto;";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $assunto_desc = utf8_decode(pg_fetch_result($res, 0, 'assunto'));
    } else {
        $msg_erro["msg"][] = " Assunto não encontrado";
    }
}


$layout_menu = "tecnica";
$title       = "Cadastro Dos Assuntos Dos Roteiros";
$title_page  = "Cadastro";

if ($_GET["roteiro_assunto"] || !empty($roteiro_assunto)) {
    $title_page = "Alteração de Cadastro";
}

include 'cabecalho_new.php';

if ($_GET['msg']) {
?>
    <div class="alert alert-success">
        <h4>Assunto <?=$_GET['msg']?> com sucesso</h4>
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


$sql = "SELECT roteiro_assunto, 
               assunto, 
               ativo
        FROM tbl_roteiro_assunto
        WHERE fabrica = $login_fabrica
        ORDER BY assunto ASC";
$res = pg_query($con, $sql);
$rows = pg_fetch_all($res);

?>

<script type="text/javascript">
    $(function () {
        
        $(document).on("click", "button[name=ativar]", function () {
            if (ajaxAction()) {
                var that     = $(this);
                var roteiro_assunto = $(this).attr("rel");

                $.ajax({
                    async: false,
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    dataType: "JSON",
                    data: { btn_acao: "ativar", roteiro_assunto: roteiro_assunto },
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        data = data.responseText;

                        if (data == "success") {
                            $(that).removeClass("btn-success").addClass("btn-danger");
                            $(that).attr({ "name": "ativar", "title": "Inativar Assunto" });
                            $(that).text("Inativar");
                            $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
                        }

                        loading("hide");
                    }
                });
            }
        });

        $(document).on("click", "button[name=inativar]", function () {
            if (ajaxAction()) {
                var that     = $(this);
                var roteiro_assunto = $(this).attr("rel");

                $.ajax({
                    async: false,
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    dataType: "JSON",
                    data: { btn_acao: "inativar", roteiro_assunto: roteiro_assunto },
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        data = data.responseText;

                        if (data == "success") {
                            $(that).removeClass("btn-danger").addClass("btn-success");
                            $(that).attr({ "name": "ativar", "title": "Ativar Assunto" });
                            $(that).text("Ativar");
                            $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
                        }

                        if(data == "fixo") {
                            alert('Assunto não pode ser Inativado!');
                        }

                        loading("hide");
                    }
                });
            }
        });

        $(document).on("click", "button[name=gravar]", function () {
            console.log('ddddd')
            $("input[name=btn_acao]").val('gravar');
            $("#frm_assunto").submit();
        });

        $(document).on("click", "button[name=excluir]", function () {
            //$("input[name=btn_acao]").val('excluir');
            //$("#frm_assunto").submit();
        });
    });

</script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_assunto" id="frm_assunto" method="POST" class="form-search form-inline tc_formulario" action="cadastro_assunto_roteiro.php" >
    <div class='titulo_tabela '><?=$title_page?></div>
    <br/>
    <input type="hidden" name="roteiro_assunto" value="<?=$roteiro_assunto?>" />
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span8'>
                <div class='control-group <?=(in_array("assunto_desc", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='assunto_desc'>Assunto</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="assunto_desc" id="assunto_desc" size="12" class='span12' maxlength="50" value= "<?=$assunto_desc?>"/>
                        </div>
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <?php 
            $txt_btn = "Gravar";
            if (!empty($_GET['roteiro_assunto']) || !empty($roteiro_assunto)) {
                $txt_btn = "Alterar";
            }
        ?>
        <button class='btn' type="button" name="gravar" ><?=$txt_btn?></button>
        <?php
        if ((!empty($_GET["roteiro_assunto"]) || !empty($roteiro_assunto)) && 1==2) {
        ?>
            <button class='btn btn-danger' type="button" name="excluir" >Excluir</button>
        <?php
        }
        ?>
    </p><br/>
</form>

<?php if (count($rows) > 0) { ?>

    <div class='alert'>Para efetuar alterações, clique na descrição do Assunto.</div>
    <table id="assuntos_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class="titulo_coluna" >
                <th>Assuntos</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($rows as $key => $value) {

                    $assunto_desc      = utf8_decode($value['assunto']);
                    $roteiro_assunto   = $value['roteiro_assunto'];
                    $ativo             = $value['ativo'];

                    echo "<tr>
                        <td><a href='{$_SERVER['PHP_SELF']}?roteiro_assunto={$roteiro_assunto}' >{$assunto_desc}</a></td>";
                    echo "<td class='tac'>";
                    if ($ativo != "t") {
                        echo "<button type='button' rel='{$roteiro_assunto}' name='ativar' class='btn btn-small btn-success' title='Ativar Assunto' >Ativar</button>";
                    } else {
                        echo "<button type='button' rel='{$roteiro_assunto}' name='inativar' class='btn btn-small btn-danger' title='Inativar Assunto' >Inativar</button>";
                    }
                echo "
                        </td>
                    </tr>";
                }
            ?>
        </tbody>
    </table>
<?php
    }

include "rodape.php";
?>
