<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

$admin_privilegios = "cadastros";


if (isset($_POST['carrega_defeitos'])) {

    $peca = $_POST['peca'];

     $sql = "SELECT tbl_peca_defeito_constatado.defeito_constatado
             FROM tbl_peca_defeito_constatado
             WHERE tbl_peca_defeito_constatado.peca = {$peca}";
    $res = pg_query($con, $sql);

    $defeitosPecas = [];
    while ($dadosDefeito = pg_fetch_object($res)) {

        $defeitosPecas[] = $dadosDefeito->defeito_constatado;

    }

    exit(json_encode($defeitosPecas));

}  

if($login_fabrica == 134){
    $tema = "Serviço Realizado";
    $temaPlural = "Serviços Realizados";
    $temaMPlural = "SERVIÇOS REALIZADOS";
    $temaMaiusculo = "SERVIÇO REALIZADO";
}else{
    $tema = "Defeito Constatado";
    $temaPlural = "Defeitos Constatados";
    $temaMPlural = "DEFEITOS CONSTATADOS";
    $temaMaiusculo = "DEFEITO CONSTATADO";
}

$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

$sqlPesquisa = "SELECT tbl_peca.descricao as peca,
               tbl_peca.referencia AS ref_peca,
               tbl_defeito_constatado.descricao as defeito,
               tbl_defeito_constatado.codigo,
               tbl_peca_defeito_constatado.peca_defeito_constatado
          FROM tbl_peca_defeito_constatado
          JOIN tbl_peca USING(peca)
          JOIN tbl_defeito_constatado USING(defeito_constatado)
         WHERE tbl_defeito_constatado.fabrica = $login_fabrica
      ORDER BY tbl_defeito_constatado.descricao
      {$limit}";
$resPesquisa = pg_query($con,$sqlPesquisa);

$btn_acao = $_POST['btn_acao'];

if ($_POST["gerar_excel"]) {
    if (pg_num_rows($resPesquisa) > 0) {
        $data = date("d-m-Y-H:i");

        $fileName = "relatorio_defeito_constatado-{$data}.csv";

        $file = fopen("/tmp/{$fileName}", "w");
        $thead = "referencia;descricao;defeito_constatado \n";
        fwrite($file, $thead);

        while ($dados = pg_fetch_object($resPesquisa)) {

            $body .= "{$dados->ref_peca};{$dados->peca};{$dados->defeito}\n";

        }

        fwrite($file, $body);

        fclose($file);

        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");

            echo "xls/{$fileName}";
        }
    }

    exit;
}

if ($btn_acao == "gravar") {
    $msg                = "";
    $msg_erro           = [];
    $defeito_constatado = $_POST['defeito_constatado'];
    $referencia_peca    = $_POST['peca_referencia'];
    $descricao_peca     = $_POST['peca_descricao'];
    $peca_defeito       = $_POST['peca_defeito'];

    $sql = "SELECT tbl_peca.peca
              FROM tbl_peca
             WHERE tbl_peca.fabrica = $login_fabrica
               AND tbl_peca.referencia = '$referencia_peca'";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) == 0) {
        $msg_erro["msg"][] = 'Peça não encontrada';
        $msg_erro["campos"][] = 'peca_descricao';
        $msg_erro["campos"][] = 'peca_referencia';
    } else {
        $peca = pg_fetch_result($res, 0, 'peca');
    }

    if (count($defeito_constatado) == 0 && count($msg_erro["msg"]) == 0) {
        $msg_erro["msg"][]    = "Informe o ".$tema;
        $msg_erro["campos"][] = 'defeito_constatado';
    }

    if (strlen($referencia_peca) == 0 && count($msg_erro["msg"]) == 0) {
        $msg_erro["msg"][]    = 'Informe a Referência da Peça';
        $msg_erro["campos"][] = 'peca_referencia';
    }

    if (strlen($descricao_peca) == 0 && count($msg_erro["msg"]) == 0) {
        $msg_erro["msg"][]    = 'Informe o Nome da Peça';
        $msg_erro["campos"][] = 'peca_descricao';
    }

    if (count($msg_erro["msg"]) == 0) {

        pg_query($con, "BEGIN");

        $defeitosSelect = implode(",", $defeito_constatado);

        $sqlDiagExcluir = "DELETE FROM tbl_peca_defeito_constatado WHERE peca = {$peca} AND defeito_constatado NOT IN ({$defeitosSelect})";
        $resDiagExcluir = pg_query($con, $sqlDiagExcluir);

        for ($i=0; $i < count($defeito_constatado); $i++) { 

            $sqlValida = "SELECT peca_defeito_constatado
                          FROM tbl_peca_defeito_constatado
                          WHERE peca = {$peca}
                          AND defeito_constatado = {$defeito_constatado[$i]}";
            $resValida = pg_query($con, $sqlValida);

            if (pg_num_rows($resValida) > 0) {
                continue;
            }

            $sql = "INSERT INTO tbl_peca_defeito_constatado
                                (
                                    fabrica,
                                    peca,
                                    defeito_constatado
                                )
                                VALUES(
                                    $login_fabrica,
                                    $peca,
                                    $defeito_constatado[$i]
                                )";
            $res = pg_query($con,$sql);

        }

        if (strlen(pg_last_error($con)) == 0) {
            $msg = "Gravado com Sucesso!";
            pg_query($con, "COMMIT");
        } else {
            $msg_erro["msg"][]    = 'Erro ao gravar';
            pg_query($con, "ROLLBACK");
        }

    }
}

if ($btn_acao == "excluir") {

    $peca_defeito = $_POST['peca_defeito'];

    $sql = "DELETE 
              FROM tbl_peca_defeito_constatado 
             WHERE peca_defeito_constatado = $peca_defeito";
    $res = pg_query($con,$sql);

    if (strlen(pg_last_error($con)) == 0) {
        $msg = "Excluído com Sucesso!";
    }

}

if ($_GET['peca_defeito']) {
    $peca_defeito = $_GET['peca_defeito'];
    $sql = "SELECT tbl_peca.referencia,
                   tbl_peca.descricao,
                   tbl_defeito_constatado.defeito_constatado
                 FROM tbl_peca_defeito_constatado
                 JOIN tbl_peca USING(peca)
                 JOIN tbl_defeito_constatado ON tbl_peca_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                WHERE peca_defeito_constatado = $peca_defeito";
    $res = pg_query($con,$sql);

    $referencia_peca     = pg_result($res,0,referencia);
    $descricao_peca      = pg_result($res,0,descricao);
    $defeito_constatado  = pg_result($res,0,defeito_constatado);
}

$layout_menu = 'cadastro';

$title = "CADASTRO $temaMaiusculo PEÇA";

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

<style type="text/css">
    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_tabela{
        background-color:#596d9b;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_coluna{
        background-color:#596d9b;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
   
</style>
<script language="javascript">
    $(function() {
        $.dataTableLoad("#tabela");
        Shadowbox.init();
        
        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
        $(".multiple").multiselect({
           selectedText: "# de # Selecionado",
           minWidth: 400
        });
    });
    function processa_form(tipo) {

        if (tipo == 'gravar' ) {
            $("input[name=btn_acao]").val("gravar");
            document.frm_cadastro.submit()
        } else if (tipo == 'excluir' ) {
            $("input[name=btn_acao]").val("excluir");
            document.frm_cadastro.submit()
        } else if (tipo == 'limpar' ) {
            window.location='<?php echo $PHP_SELF;?>';
        }

    }
    function retorna_peca(retorno){
        $("input[name=peca_referencia]").val(retorno.referencia);
        $("input[name=peca_descricao]").val(retorno.descricao);

        $.ajax({
            url : window.location,
            type: "POST",
            data: {
                carrega_defeitos : true,
                peca : retorno.peca
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
    <form name='frm_cadastro' METHOD='POST' ACTION='defeito_constatado_peca_cadastro.php' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela'>Cadastro</div>
        <br/>
        <input type='hidden' name='peca' id='peca' value=''>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_referencia'>Ref. Peças</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<?php echo $referencia_peca ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<?php echo $descricao_peca ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
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
                            <select name="defeito_constatado[]" multiple="multiple" class='span12 multiple' id="defeito_constatado">
                                <option value=''>Selecione <?=$tema?></option>
                                <?php
                                    $sql = "SELECT DISTINCT defeito_constatado,descricao
                                                 FROM tbl_defeito_constatado
                                                WHERE fabrica = $login_fabrica
                                                AND ativo
                                                ORDER BY descricao";
                                    $res = pg_query($con, $sql);

                                    for($i = 0; $i < pg_num_rows($res); $i++){
                                        $defeito   = pg_fetch_result($res, $i, 'defeito_constatado');
                                        $descricao = pg_fetch_result($res, $i, 'descricao');
                                        
                                        $selected = (in_array($defeito, $defeito_constatado)) ? "SELECTED" : "";

                                        echo "<option value='$defeito' {$selected}>{$descricao}</option>";
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
            <button class='btn btn-primary' onclick="processa_form('gravar')" type="button">Gravar</button>
            <button class='btn btn-danger' onclick="processa_form('excluir')" type="button">Excluir</button>
            <button class='btn' id="btn_acao" onclick="processa_form('limpar')" type="button">Limpar</button>
            <input type='hidden' name='btn_acao' value=''>
            <input type='hidden' name='peca_defeito' value='<?php echo $peca_defeito; ?>'>

        </p><br/>
    </form> <br />
<?php  if (pg_num_rows($resPesquisa) > 0) { 

    if (pg_num_rows($resPesquisa) >= 500) {
        $count = 500;
        ?>
        <div id='registro_max'>
            <h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
        </div>
    <?php
    }

    ?>
    <table class='table table-bordered table-striped table-hover table-fixed' id="tabela">
        <thead>
            <tr class='titulo_coluna'>
                <th class="tal">Referência Peça</th>
                <th class="tal">Descrição Peça</th>
                <?php if ($login_fabrica == 19) { ?>
                    <th>Código</th>
                <?php } ?>
                <th class="tal"><?php echo $tema;?></th>
            </tr>
        </thead>
        <tbody>
        <?php 
            $total_geral  = 0;
            $total_receber = 0;
            $valor_pagar   = 0;

            for ($i = 0; $i < pg_num_rows($resPesquisa); $i++) {
                $peca           = pg_fetch_result($resPesquisa,$i,"peca");
                $ref_peca       = pg_fetch_result($resPesquisa,$i,"ref_peca");
                $defeito        = pg_fetch_result($resPesquisa,$i,"defeito");
                $peca_defeito   = pg_fetch_result($resPesquisa,$i,"peca_defeito_constatado");
                $defeito_codigo = pg_fetch_result($resPesquisa,$i, "codigo");
        ?>
            <tr id='<?php echo $os; ?>'>
                <td class='tal'><?php echo $ref_peca; ?></td>
                <td class='tal' nowrap><?php echo $peca; ?></td>
                <?php if ($login_fabrica == 19) { ?>
                    <td><?php echo $defeito_codigo; ?></td>
                <?php } ?>
                <td class='tal' nowrap><?php echo $defeito; ?></td>
            </tr>
        <?php }?>
        
        </tbody>
    </table>
    <?php
        $jsonPOST = excelPostToJson($_POST);
    ?>
    <br />
    <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo CSV</span>
    </div>
<?php 
    } else {
        echo "<div class='alert alert-warning'>Nenhum Defeito de Peça Cadastrado</div>";
    }
?>
<?php include "rodape.php";
