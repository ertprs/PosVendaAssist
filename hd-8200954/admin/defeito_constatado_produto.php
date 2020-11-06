<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros"; // callcenter financeiro gerencia infotecnica auditoria

require_once 'autentica_admin.php';
require_once 'funcoes.php';

$btn_acao = $_POST['btn_acao'];

if (isset($_POST['carrega_defeitos'])) {

    $produto = $_POST['produto'];

    $sql = "SELECT tbl_defeito_constatado.defeito_constatado
            FROM tbl_diagnostico
            JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico AND tbl_diagnostico_produto.fabrica = $login_fabrica
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_diagnostico_produto.fabrica = $login_fabrica
            WHERE tbl_diagnostico.fabrica = $login_fabrica
            AND tbl_diagnostico_produto.produto = $produto";
    $res = pg_query($con, $sql);

    $defeitosProduto = [];
    while ($dadosDefeito = pg_fetch_object($res)) {

        $defeitosProduto[] = $dadosDefeito->defeito_constatado;

    }

    exit(json_encode($defeitosProduto));

}  

if ($_GET['acao'] == "delete" && strlen($_GET['diagnostico_produto']) > 0) {

    $diagnostico_produto    = $_GET['diagnostico_produto'];


    $sqlValida = "SELECT tbl_diagnostico.diagnostico
                    FROM tbl_diagnostico
                    JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico AND tbl_diagnostico_produto.fabrica = $login_fabrica
                   WHERE tbl_diagnostico.fabrica = $login_fabrica
                     AND tbl_diagnostico_produto.diagnostico_produto = $diagnostico_produto";
    $resValida = pg_query($con, $sqlValida);
    if (pg_num_rows($resValida) > 0) {

        $diagnostico  = pg_fetch_result($resValida, 0, 'diagnostico');

        $res = pg_query($con,"BEGIN");    

        $sqlRemove = "DELETE FROM tbl_diagnostico_produto WHERE diagnostico_produto = {$diagnostico_produto}";
        $resRemove = pg_query($con, $sqlRemove);
        if (pg_last_error()) {
            $erro["msg"][]    = "Erro ao gravar";
        }

        $sqlRemove = "DELETE FROM tbl_diagnostico WHERE diagnostico = {$diagnostico}";
        $resRemove = pg_query($con, $sqlRemove);
        if (pg_last_error()) {
            $erro["msg"][]    = "Erro ao gravar";
        }

        if (count($erro["msg"]) > 0) {
            $msg_erro["msg"][]    = "Erro ao excluir";
            $res = pg_query($con,"ROLLBACK");
        } else {
            $res = pg_query($con,"COMMIT");
            $msg = "Excluido com Sucesso!";
        }

    } else {
        $msg_erro["msg"][] = "Defeito não encontrado";
    }
    echo "<meta http-equiv=refresh content=\"1;URL=defeito_constatado_produto.php?lista_tudo=true\">";
}


if ($btn_acao == "gravar") {

    $msg                   = "";
    $msg_erro              = [];
    $defeito_constatado    = $_POST['defeito_constatado'];
    $produto_referencia    = $_POST['produto_referencia'];
    $produto_descricao     = $_POST['produto_descricao'];
        
    $sql = "SELECT tbl_produto.produto
              FROM tbl_produto
             WHERE tbl_produto.fabrica_i = $login_fabrica
               AND tbl_produto.referencia = '$produto_referencia'";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) == 0) {
        $msg_erro["msg"][] = 'Produto não encontrado';
        $msg_erro["campos"][] = 'produto_referencia';
        $msg_erro["campos"][] = 'produto_descricao';
    } else {
        $produto = pg_fetch_result($res, 0, 'produto');
    }

    if (strlen($produto_referencia) == 0) {
        $msg_erro["msg"][]    = 'Informe a Referência do Produto';
        $msg_erro["campos"][] = 'produto_referencia';
    }

    if (strlen($produto_descricao) == 0) {
        $msg_erro["msg"][]    = 'Informe a Descrição do Produto';
        $msg_erro["campos"][] = 'produto_descricao';
    }

    if (count($defeito_constatado) == 0) {
        $msg_erro["msg"][]    = "Informe o Defeito Constatado";
        $msg_erro["campos"][] = 'defeito_constatado';
    }

    if (count($msg_erro["msg"]) == 0) {
        $res = pg_query($con,"BEGIN");

        $defeitosSelect = implode(",", $defeito_constatado);

        $sqlDiagExcluir = "SELECT tbl_diagnostico.diagnostico
                FROM tbl_diagnostico
                JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico 
                AND tbl_diagnostico_produto.fabrica = $login_fabrica
                WHERE tbl_diagnostico.defeito_constatado NOT IN ({$defeitosSelect})
                AND tbl_diagnostico_produto.produto = {$produto}";
        $resDiagExcluir = pg_query($con, $sqlDiagExcluir);

        if (pg_num_rows($resDiagExcluir) > 0) {

            while ($dadosDiag = pg_fetch_object($resDiagExcluir)) {

                $sqlDelete = "DELETE FROM tbl_diagnostico_produto WHERE diagnostico = {$dadosDiag->diagnostico};
                              DELETE FROM tbl_diagnostico WHERE diagnostico = {$dadosDiag->diagnostico}";
                pg_query($con, $sqlDelete);

            }

        }

        for ($i=0; $i < count($defeito_constatado); $i++) {

            $sqlValida = "SELECT tbl_diagnostico.diagnostico,
                                 tbl_defeito_constatado.codigo || ' - ' || tbl_defeito_constatado.descricao as defeito
                            FROM tbl_diagnostico
                            JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico AND tbl_diagnostico_produto.fabrica = $login_fabrica
                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_diagnostico_produto.fabrica = $login_fabrica
                           WHERE tbl_diagnostico.fabrica = $login_fabrica
                             AND tbl_diagnostico.defeito_constatado = $defeito_constatado[$i]
                             AND tbl_diagnostico_produto.produto = $produto";
            $resValida = pg_query($con, $sqlValida);

            if (pg_num_rows($resValida) > 0) {
                continue;
            }
           
            $sqlInsertDiag = "INSERT INTO tbl_diagnostico (
                                            fabrica, 
                                            defeito_constatado
                                        ) VALUES (
                                            $login_fabrica,
                                            $defeito_constatado[$i]
                                        ) RETURNING diagnostico";
            $resInsertDiag = pg_query($con, $sqlInsertDiag);
            $idDiagnostico  = pg_fetch_result($resInsertDiag, 0, 0);

            if (pg_last_error()) {
                $msg_erro["msg"][]    = "Erro ao gravar";
            }

            if ($idDiagnostico && count($msg_erro["msg"]) == 0) {

                $sqlInsertDiagPD = "INSERT INTO tbl_diagnostico_produto (
                                                diagnostico,
                                                fabrica, 
                                                produto
                                            ) VALUES (
                                                $idDiagnostico,
                                                $login_fabrica,
                                                $produto
                                            )";
                $resInsertDiagPD = pg_query($con, $sqlInsertDiagPD);
                if (pg_last_error()) {
                    $msg_erro["msg"][]    = "Erro ao gravar";
                }
            }
        }

        if (count($msg_erro["msg"]) > 0) {
            $res = pg_query($con,"ROLLBACK");
        } else {
            $res = pg_query($con,"COMMIT");
            $msg = "Gravado com Sucesso!";
        }

    }
}

$title = 'Relação de possíveis defeitos constatados para cada produto';
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "autocomplete",
    "ajaxform",
    "fancyzoom",
    "multiselect"
);

include("plugin_loader.php");
?>
<script>

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);

        $.ajax({
            url : window.location,
            type: "POST",
            data: {
                carrega_defeitos : true,
                produto : retorno.produto
            },
            timeout: 7000,
            dataType: 'json'
        }).done(function(data){

            if (data.length > 0) {

                $("input[name=multiselect_defeito_constatado]").prop("checked", false);

                $("input[name=multiselect_defeito_constatado]").filter(function(){
                    return $.inArray($(this).val(), data) !== -1;
                }).click();

            }

        });

    }

    function processa_form(tipo) {

        if (tipo == 'gravar' ) {
            $("input[name=btn_acao]").val("gravar");
            document.frm_cadastro.submit()
        } else if (tipo == 'limpar' ) {
            window.location='<?php echo $PHP_SELF;?>';
        } else if (tipo == 'lista' ) {
            window.location='<?php echo $PHP_SELF;?>?lista_tudo=true';
        }

    }
    
    $(function() {

        $.dataTableLoad("#tabela");
        Shadowbox.init();
        
        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
        $(".multiple").multiselect({
            selectedText: "selecionados # de #",
           minWidth: 400
        });

        $(".btn-remove").on("click",function() {
            var defeito = $(this).data("defeito")
            if (confirm("Deseja remover esse registro?") == true) {
                window.location.href='<?php echo $PHP_SELF;?>?acao=delete&diagnostico_produto='+defeito;
            } else {
                return false;
            }
            
        });
    });
</script>
<br />
<?php if(count($msg_erro["msg"]) > 0){?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br>", $msg_erro["msg"]); ?> </h4>
    </div>
<?php } ?>
<?php if(strlen($msg) > 0){?>
    <div class="alert alert-success">
        <h4><?php echo $msg;?> </h4>
    </div>
<?php } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_cadastro' METHOD='POST' ACTION='defeito_constatado_produto.php' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela'>Cadastro</div>
    <br/>
    <input type='hidden' name='peca' id='peca' value=''>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?php echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group <?=(in_array("defeito_constatado", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Defeito Constatado</label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <h5 class='asteristico'>*</h5>
                        <select name="defeito_constatado[]" multiple="multiple" class='span12 multiple' id="defeito_constatado">
                            <?php
                                $sql = "SELECT DISTINCT defeito_constatado,descricao,codigo
                                             FROM tbl_defeito_constatado
                                            WHERE fabrica = $login_fabrica
                                            AND ativo
                                            ORDER BY descricao";
                                $res = pg_query($con, $sql);

                                for($i = 0; $i < pg_num_rows($res); $i++){
                                    $defeito   = pg_fetch_result($res, $i, 'defeito_constatado');
                                    $descricao = pg_fetch_result($res, $i, 'descricao');
                                    $codigo    = pg_fetch_result($res, $i, 'codigo');

                                    $selected = (in_array($defeito, $defeito_constatado)) ? "SELECTED" : "";
                                    echo "<option value='$defeito' {$selected}>{$codigo} - {$descricao}</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>   
    <p>
      <br/>
        <button class='btn btn-success' onclick="processa_form('gravar')" type="button">Gravar</button>
        <button class='btn' onclick="processa_form('limpar')" type="button">Limpar</button>
        <button class='btn btn-info' id="btn_acao" onclick="processa_form('lista')" type="button">Listar Defeitos</button>
        <input type='hidden' name='btn_acao' value=''>

    </p><br/>
</form> <br />
<?php
if (isset($_GET["lista_tudo"]) && $_GET["lista_tudo"] == true) {

    $sql = "SELECT tbl_produto.descricao AS descricao_produto,
                   tbl_produto.referencia AS ref_produto,
                   tbl_diagnostico_produto.diagnostico_produto,
                   tbl_defeito_constatado.descricao as defeito,
                   tbl_defeito_constatado.codigo
              FROM tbl_diagnostico
              JOIN tbl_diagnostico_produto ON tbl_diagnostico.diagnostico = tbl_diagnostico_produto.diagnostico AND tbl_diagnostico_produto.fabrica = $login_fabrica
              JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
              JOIN tbl_produto ON tbl_diagnostico_produto.produto = tbl_produto.produto  AND fabrica_i = $login_fabrica
             WHERE tbl_diagnostico.fabrica = $login_fabrica
          ORDER BY tbl_produto.descricao";
    $res = pg_query($con,$sql);
?>
    <table class='table table-bordered table-striped table-hover table-fixed' id="tabela">
        <thead>
            <tr class='titulo_coluna'>
                <th class="tal">Referência Produto</th>
                <th class="tal">Descrição Produto</th>
                <th class="tal">Defeito Constatado</th>
                <th class="tac">Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if (pg_num_rows($res) > 0) {
            for ($i = 0; $i < pg_num_rows($res); $i++) {
                $descricao_produto       = pg_fetch_result($res,$i,"descricao_produto");
                $ref_produto             = pg_fetch_result($res,$i,"ref_produto");
                $defeito                 = pg_fetch_result($res,$i,"defeito");
                $defeito_codigo          = pg_fetch_result($res,$i, "codigo");
                $diagnostico_produto     = pg_fetch_result($res,$i, "diagnostico_produto");
        ?>
            <tr id='<?php echo $diagnostico_produto; ?>'>
                <td class='tal'><?php echo $ref_produto; ?></td>
                <td class='tal' nowrap><?php echo $descricao_produto; ?></td>
                <td class='tal' nowrap><?php echo $defeito_codigo . ' - ' . $defeito; ?></td>
                <td class='tac' nowrap>
                    <button data-defeito="<?php echo $diagnostico_produto; ?>" type="button" class="btn btn-danger btn-remove btn-small">Excluir</button>
                </td>
            </tr>
        <?php }
        } 
        ?>
        </tbody>
    </table>
<?php 
}
include "rodape.php";
