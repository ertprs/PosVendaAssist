<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DE DEFEITO CONSTATADO COM FALHAS EM POTENCIAL";

include "cabecalho_new.php";

$plugins = array( 
                "select2"
                );

include ("plugin_loader.php");

if (strlen($_GET["diagnostico"]) > 0) {
    $diagnostico = trim($_GET["diagnostico"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
    $btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {
    $defeito_constatado = trim($_POST["defeito_constatado"]);
    $falha              = trim($_POST["falha"]);
    $diagnostico        = trim($_POST["diagnostico"]);

    if (strlen($defeito_constatado) == 0) {
        $msg_erro = "Favor escolher um Defeito Constatado.";
    }

    if (strlen($falha) == 0) {
        $msg_erro = "Favor escolher uma Falha em Potencial.";
    }

    $res = pg_query($con, "BEGIN TRANSACTION");
    if (strlen($msg_erro) == 0 && strlen($diagnostico) == 0) {

        ###INSERE REGISTRO
        $sql = "INSERT INTO tbl_diagnostico (
                    fabrica,
                    defeito_constatado,
                    servico,
                    ativo 
                ) VALUES (
                    $login_fabrica,
                    $defeito_constatado,
                    $falha,
                    't'
                )";

        $res      = pg_query($con,$sql);
        $msg_erro = pg_last_error($con);

    } elseif (strlen($msg_erro) == 0 && strlen($diagnostico) > 0) {

        ###ALTERA REGISTRO
        $sql = "UPDATE tbl_diagnostico  SET
                                    defeito_constatado = $defeito_constatado,
                                    servico = $falha
                                    WHERE  tbl_diagnostico.fabrica = $login_fabrica
                                    AND tbl_diagnostico.diagnostico = $diagnostico";

        $res      = pg_query($con,$sql);
        $msg_erro = pg_last_error($con);
    }

    if (strlen ($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg = "Gravado com Sucesso!";
        
    } else {
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $defeito_constatado = $POST["defeito_constatado"];
        $falha              = $POST["falha"];
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

###CARREGA REGISTRO
if (strlen($_GET["diagnostico"]) > 0) {
    $diagnostico_alteracao = $_GET["diagnostico"];
    $sql = "SELECT  tbl_diagnostico.diagnostico,
                    tbl_diagnostico.defeito_constatado,
                    tbl_diagnostico.servico
            FROM    tbl_diagnostico
            WHERE   tbl_diagnostico.fabrica = $login_fabrica
            AND     tbl_diagnostico.diagnostico = $diagnostico_alteracao;";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $diagnostico_alteracao  = trim(pg_result($res, 0, diagnostico));
        $defeito_constatado     = trim(pg_result($res, 0, defeito_constatado));
        $falha                  = trim(pg_result($res, 0, servico));
    }
}
?>
    <style>
        table th {
            text-align: left !important;
        }
        table tr>td:first-of-type {
            text-align: left;
            padding-right: 1em;
        }
    </style>
    <script>
        $(function(){
            $('.select2').select2();
        });
    </script>
    <?php if (strlen($msg_erro) > 0) { ?>
    <div class="alert alert-error">
        <h4><?php echo $msg_erro;?></h4>
    </div>
    <?php } ?>
    <?php 
        if (strlen($msg) > 0) { 
        $defeito_constatado = "";
        $falha     = "";
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
    <input type="hidden" name="diagnostico" value="<?php echo $diagnostico_alteracao ?>" />

    <div class="titulo_tabela">Cadastro de Defeito Constatado com Falha em Potencial</div>
    <br/>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span5">
            <div class='control-group <?php echo (strpos($msg_erro, "Defeito Constatado") !== false) ? "error" : "" ?>'>
                <label class="control-label" for="">Defeito Constatado</label>
                <div class="controls controls-row">
                    <h5 class="asteristico">*</h5>
                    <select name="defeito_constatado" class="select2 span12" id="defeito_constatado">
                        <option value="">Escolha um Defeito Constatado</option>
                        <?php
                            $sqlDefeito = "SELECT 
                                                descricao,
                                                defeito_constatado
                                            FROM tbl_defeito_constatado
                                            WHERE tbl_defeito_constatado.fabrica=$login_fabrica
                                            ORDER BY tbl_defeito_constatado.descricao";
                            $resDefeito = pg_query($con, $sqlDefeito);
                            while ($rowsDefeito = pg_fetch_array($resDefeito)) {
                                $selectedDefeito = ($rowsDefeito['defeito_constatado'] == $defeito_constatado) ? "selected='selected'" : "";
                                echo '<option value="'.$rowsDefeito['defeito_constatado'].'" '.$selectedDefeito.'>'.$rowsDefeito['descricao'].'</option>';
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span5">
            <div class='control-group <?php echo (strpos($msg_erro, "Falha em Potencial") !== false) ? "error" : "" ?>'>
                <label class="control-label" for="">Falha em Potencial</label>
                <div class="controls controls-row">
                    <h5 class="asteristico">*</h5>
                    <select name="falha" class="select2 span12" id="falha">
                        <option value="">Escolha uma Falha em Potencial</option>
                        <?php
                            $sqlFalha = "SELECT 
                                                descricao,
                                                servico
                                            FROM tbl_servico
                                            WHERE tbl_servico.fabrica=$login_fabrica
                                            ORDER BY tbl_servico.descricao";
                            $resFalha = pg_query($con, $sqlFalha);
                            while ($rowsFalha = pg_fetch_array($resFalha)) {
                                $selectedFalha = ($rowsFalha['servico'] == $falha) ? "selected='selected'" : "";
                                echo '<option value="'.$rowsFalha['servico'].'" '.$selectedFalha.'>'.$rowsFalha['descricao'].'</option>';
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span1"></div>
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
            <th colspan='4' class="tac">Relação dos Defeitos Constatados com Falha em Potenciais Cadastrados</th>
        </tr>
        <tr class='titulo_coluna'>
            <th>Defeito Constatado</th>
            <th>Falha em Potencial</th>
        </tr>
    </thead>
    <tbody>
    <?php
        $sql = "SELECT  
                    tbl_servico.descricao AS nome_falha,
                    tbl_defeito_constatado.descricao AS nome_defeito,
                    tbl_diagnostico.diagnostico,
                    tbl_diagnostico.defeito_constatado,
                    tbl_diagnostico.servico
            FROM    tbl_diagnostico
            JOIN tbl_servico ON tbl_servico.servico=tbl_diagnostico.servico
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_diagnostico.defeito_constatado
            WHERE tbl_diagnostico.fabrica = $login_fabrica
            ORDER BY tbl_diagnostico.diagnostico DESC;";
        $res = pg_query($con, $sql);

        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $diagnostico  = trim(pg_result($res, $x, diagnostico));
            $nome_falha   = trim(pg_result($res, $x, nome_falha));
            $nome_defeito = trim(pg_result($res, $x, nome_defeito));

            $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
    ?>  
    <tr>
        <td align="left">
            <a href="<? echo $PHP_SELF.'?diagnostico='.$diagnostico;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $nome_defeito;?></a>
        </td>
        <td align="left">
            <a href="<? echo $PHP_SELF.'?diagnostico='.$diagnostico;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $nome_falha;?></a>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php 
    if (!isset($semcab)) {
        include "rodape.php";
    }
?>
