<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DE FALHAS EM POTENCIAL";

include "cabecalho_new.php";

$plugins = array( 
                "tooltip"
                );

include ("plugin_loader.php");

if (strlen($_GET["falha"]) > 0) {
    $falha = trim($_GET["falha"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
    $btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {
    $descricao       = trim($_POST["descricao"]);
    $ativo           = trim($_POST["ativo"]);
    $falha           = trim($_POST["falha"]);

    if (strlen($descricao) == 0) {
        $msg_erro = "Favor informar a Descrição da Falha.";
    }

    if (strlen($ativo) == 0) {
        $aux_ativo = "f";
    } else {
        $aux_ativo = "t";
    }


    $sql = "SELECT  tbl_servico.descricao
            FROM    tbl_servico
            WHERE   tbl_servico.fabrica = $login_fabrica
            AND     tbl_servico.descricao = '$descricao';";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $msg_erro = "Falha já cadastrada.";
    }


    $res = pg_query($con, "BEGIN TRANSACTION");
    if (strlen($msg_erro) == 0 && strlen($falha) == 0) {

        ###INSERE REGISTRO
        $sql = "INSERT INTO tbl_servico (
                    fabrica   ,
                    descricao ,
                    ativo 
                ) VALUES (
                    $login_fabrica ,
                    '$descricao'   ,
                    '$aux_ativo'
                )";

        $res      = pg_query($con,$sql);
        $msg_erro = pg_last_error($con);

    } elseif (strlen($msg_erro) == 0 && strlen($falha) > 0) {

        ###ALTERA REGISTRO
        $sql = "UPDATE tbl_servico  SET
                                    descricao = '$descricao',
                                    ativo     = '$aux_ativo'
                                    WHERE  tbl_servico.fabrica = $login_fabrica
                                    AND tbl_servico.servico    = $falha";

        $res      = pg_query($con,$sql);
        $msg_erro = pg_last_error($con);
    }

    if (strlen ($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg = "Gravado com Sucesso!";
        
    } else {
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $descricao         = $POST["descricao"];
        $ativo             = $POST["ativo"];
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

###CARREGA REGISTRO
if (strlen($_GET["falha"]) > 0) {
    $falha_alteracao = $_GET["falha"];
    $sql = "SELECT  tbl_servico.servico,
                    tbl_servico.descricao,
                    tbl_servico.ativo
            FROM    tbl_servico
            WHERE   tbl_servico.fabrica = $login_fabrica
            AND     tbl_servico.servico = $falha_alteracao;";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $falha_alteracao   = trim(pg_result($res, 0, servico));
        $descricao         = trim(pg_result($res, 0, descricao));
        $ativo             = trim(pg_result($res, 0, ativo));
    }
}
?>
    <style>
        table tr>td:first-of-type {
            text-align: left;
            padding-right: 1em;
        }
    </style>

    <?php if (strlen($msg_erro) > 0) { ?>
    <div class="alert alert-error">
        <h4><?php echo $msg_erro;?></h4>
    </div>
    <?php } ?>
    <?php 
        if (strlen($msg) > 0) { 
        $descricao = "";
        $ativo     = "f";
    ?>
    <div class="alert alert-success">
        <h4><?php echo $msg;$msg="";?></h4>
    </div>
    <?php } ?>
<br/>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_falha" method="post" action="<?php echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
    <input type="hidden" name="falha" value="<?php echo $falha_alteracao ?>" />

    <div class="titulo_tabela">Cadastro de Falha em Potencial</div>
    <br/>
    <div class="row-fluid">
        <div class="span3"></div>
        <div class="span5">
            <div class='control-group <?php echo (strpos($msg_erro,"nome da familia") !== false) ? "error" : "" ?>'>
                <label class="control-label" for="">Descrição da Falha</label>
                <div class="controls controls-row">
                    <h5 class="asteristico">*</h5>
                    <input type="text" class="span12" id="descricao" name="descricao" value="<?php echo $descricao ?>" maxlength="50" />
                </div>
            </div>
        </div>
        <div class="span1">
            <div class="control-group tac">
                <label class="control-label" for="">Ativo</label>
                <div class="controls controls-row tac">
                    <input type='checkbox' name='ativo' id='ativo' value='TRUE' <?php if ($ativo == 't') {echo "CHECKED";}?> />
                </div>
            </div>
        </div>
        <div class="span3"></div>
    </div>
    <br/>
    <div class="row-fluid">
        <div class="span4"></div>

        <div class="span4 tac">
            <button type="button" class="btn" onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />     
        </div>
        <div class="span4"></div>
    </div>
    <br/>
</form>
<!-- AQUI COMEÇA A LISTAGEM -->
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class='titulo_tabela'>
            <th colspan='4'>Relação das Falhas em Potenciais Cadastradas</th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Descrição</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
        $sql = "SELECT  tbl_servico.servico,
                        tbl_servico.descricao,
                        tbl_servico.ativo
                FROM    tbl_servico
                WHERE   tbl_servico.fabrica = $login_fabrica
                ORDER BY tbl_servico.descricao;";
        $res = pg_query($con, $sql);

        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $falha     = trim(pg_result($res, $x, servico));
            $descricao = trim(pg_result($res, $x, descricao));
            $ativo     = trim(pg_result($res, $x, ativo));

            $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

            if ($ativo == 't') {
                $ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
            } else {
                $ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
            }
    ?>  
    <tr>
        <td align="left">
            <a href="<? echo $PHP_SELF.'?falha='.$falha;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $descricao;?></a>
        </td>
        <td class="tac"><?php echo $ativo;?></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php 
    if (!isset($semcab)) {
        include "rodape.php";
    }
?>
