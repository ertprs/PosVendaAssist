<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios="cadastros";

include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $servico   = $_POST["servico"];
    $descricao = $_POST["descricao"];
    $ativo     = $_POST["ativo"][0];

    if (!strlen($descricao)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
    }

    if ($ativo != "true") {
        $ativo = 'false';
    }

    if (!count($msg_erro["msg"])) {
        $res = pg_query ($con,"BEGIN");

        if (strlen($servico) == 0) {
            $sql = "INSERT INTO tbl_servico  (
                        fabrica,
                        descricao,
                        ativo
                    ) VALUES (
                        $login_fabrica,
                        '$descricao',
                        $ativo
                    );";
        }else{
            $sql = "UPDATE  tbl_servico
                    SET 
                        descricao = '$descricao',
                        ativo     = $ativo
                    WHERE tbl_servico.fabrica = $login_fabrica
                    AND tbl_servico.servico = $servico";
        }

        $res = pg_query($con, $sql);

       
        if (!pg_last_error()) {
            pg_query($con, "COMMIT");
            $msg_success = true;
            unset($_POST);
            unset($servico);
        } else {
            $msg_erro["msg"][] = "Erro na inclusão/alteração de serviço";
            pg_query($con, "ROLLBACK");
        }
    }
}


if (!empty($_GET['servico'])){
    $servico = $_GET['servico'];

    $sql = "SELECT tbl_servico.servico, tbl_servico.descricao, tbl_servico.ativo
            FROM tbl_servico
            WHERE tbl_servico.fabrica = $login_fabrica
            AND tbl_servico.servico = $servico";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        $_RESULT['servico']   = trim(pg_result($res,0,servico));
        $_RESULT['descricao'] = trim(pg_result($res,0,descricao));
        $_RESULT['ativo']     = pg_result($res,0,ativo) == "t" ? true : false;
    }
}

$layout_menu = "cadastro";
$title = "CADASTRO DE SERVIÇOS";

if(strlen($servico) == 0){
    $title_page = "Cadastro";
}else{
    $title_page = "Alteração de Serviço";
}

include "cabecalho_new.php";

if ($msg_success) {
?>
    <div class="alert alert-success">
        <h4>Serviço cadastrado com sucesso</h4>
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
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<?
$hiddens = array(
    "servico"
);

$inputs = array(
    "descricao" => array(
        "span"      => 3,
        "label"     => "Descrição",
        "type"      => "input/text",
        "width"     => 12,
        "maxlength" => 40,
        "required"  => true
    ),
    "ativo" => array(
        "span"  => 1,
        "type"  => "checkbox",
        "width" => 1,
        "checks" => array(
            "true" => "Ativo"
        )
    )
);
?>
<form name="frm_servico" method="post" class="form-search form-inline tc_formulario" action="servico_cadastro.php">
    <div class='titulo_tabela '><?=$title_page?></div>
    <br/>

    <?php
     echo montaForm($inputs, $hiddens);
    ?>

    <p><br/>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <button class='btn' type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
        <?php
        if (strlen($_GET["servico"]) > 0) {
        ?>
            <button class='btn btn-warning' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
        <?php
        }
        ?>
    </p><br/>
</form>

<table id="servicos_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class="titulo_tabela" >
            <th colspan="2">Relação de Serviços</th>
        </tr>
        <tr class="titulo_coluna" >
            <th>Descrição</th>
            <th>Ativo</th>
        </tr>
    </thead>
    <tbody>
        <?
        $sql = "SELECT  
                    tbl_servico.servico,
                    tbl_servico.descricao,
                    CASE WHEN tbl_servico.ativo IS TRUE
                        THEN 'sim'
                        ELSE 'não'
                    END as ativo
                FROM tbl_servico
                WHERE tbl_servico.fabrica  = $login_fabrica
                ORDER BY tbl_servico.descricao";
        $res = pg_query ($con,$sql);

        while($result = pg_fetch_object($res)){
        ?>
            <tr>
                <td class="tac">
                    <a href="servico_cadastro.php?servico=<?=$result->servico?>"><?=$result->descricao?></a>
                </td>
                <td class="tac">
                    <img src="imagens/<?=($result->ativo == 'sim') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($result->ativo == 'sim') ? 'Serviço ativo' : 'Serviço inativo'?>"/>
                </td>
            </tr>
        <?
        }
        ?>
    </tbody>
</table>
<br />
<?
include "rodape.php";
?>
