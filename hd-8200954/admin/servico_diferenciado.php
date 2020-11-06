<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include '../class/AuditorLog.php';

$title       = "BONIFICAÇÃO POR SERVIÇO DIFERENCIADO";
$layout_menu = 'cadastro';


if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $tipo = filter_input(INPUT_POST,'tipo');


    if ($tipo == "alteraValor") {
        $valor = filter_input(INPUT_POST,'valor');

        $auditorLog = new AuditorLog('update');

        $valor = str_replace(",",".",$valor);
        $sql = "
            SELECT  tbl_fabrica.parametros_adicionais
            FROM    tbl_fabrica
            WHERE   fabrica = $login_fabrica
        ";
        $res = pg_query($con,$sql);

        $auditorLog->retornaDadosSelect("SELECT JSON_FIELD('servicoDiferenciado',parametros_adicionais) AS servicoDiferenciado FROM tbl_fabrica WHERE fabrica = ".$login_fabrica);
        $valores = pg_fetch_result($res,0,parametros_adicionais);
        $verifica = json_decode($valores,TRUE);

        $verifica['servicoDiferenciado'] = (float)$valor;
        pg_query($con,"BEGIN TRANSACTION");

        $sqlUp = "
            UPDATE  tbl_fabrica
            SET     parametros_adicionais = E'".json_encode($verifica)."'
            WHERE   fabrica = $login_fabrica
        ";
        $resUp = pg_query($con,$sqlUp);

        $auditorLog->retornaDadosSelect()->enviarLog('update','tbl_fabrica_servico_diferenciado',$login_fabrica.'*'.$login_fabrica,'servico_diferenciado.php');
        if (!pg_last_error($con)) {
            pg_query($con,"COMMIT TRANSACTION");
            echo json_encode(array("ok"=>true));
        } else {
            pg_query($con,"ROLLBACK TRANSACTION");
            echo "erro";
        }
    }

    exit;
}

$sql = "
    SELECT  JSON_FIELD('servicoDiferenciado',tbl_fabrica.parametros_adicionais) AS valor_diferenciado
    FROM    tbl_fabrica
    WHERE   fabrica = $login_fabrica
";
$res = pg_query($con,$sql);

$valor_diferenciado = pg_fetch_result($res,0,valor_diferenciado);


include 'cabecalho_new.php';

$plugins = array(
    "price_format",
    "shadowbox"
);
include("plugin_loader.php");
?>
<script type="text/javascript" src="js/jquery.maskMoney.min.js"></script>
<script type="text/javascript">
$(function(){
    Shadowbox.init();

    $("#valor").css("text-align","right");
    $("#valor").priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.'
    });

    $("#gravar").click(function(e){
        e.preventDefault();

        var valor = $("#valor").val();

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"alteraValor",
                valor:valor
            }

        })
        .done(function(data){
            if (data.ok) {
                $("#valor_atual").text(valor);
                $("#valor").val("");
                alert("Bonificação Atualizada com sucesso!")
            }
        })
        .fail(function(){
            alert("Não foi possível alterar o valor de Bonificação.");
        });
    });
});
</script>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_causa_troca" method="post" action="<?=$PHP_SELF?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Cadastro do valor de bonificação</div>
    <div class='row-fluid'>
        <div class='span4'></div>
        <div class='span8'>
            <div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='valor'>Valor da Bonificação</label>
                <div class='controls controls-row'>
                    <div class='span7'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="valor" name="valor" maxlength='6' class='span12' value="" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'></div>
    </div>
    <p>
        <br />
        <button type="button" id="gravar" class="btn btn-success" value="gravar">Gravar</button>
        <br />
    </p>
    <br />
    <center>
    <div class='tac'>
        <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_fabrica_servico_diferenciado&id=<?=$login_fabrica?>'>Visualizar Log Auditor</a>
    </div>
    </center>
    <br>
</form>
<br />
<table border="0" cellspacing="0" cellspadding="0" style="width:850px;">
    <tr>
        <td class="titulo_tabela">Valor Atual</td>
        <td style="font-size:16px;text-align:right;border: 1px solid #596d9b"><span id="valor_atual"><?=number_format($valor_diferenciado,2,',','.')?></span></td>
    </tr>
</table>

<?php
include "rodape.php";
?>
