<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if (filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN)) {
    $osExportar = filter_input(INPUT_POST,"exportar",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
    $os = implode(",",$osExportar);

    pg_query($con,"BEGIN TRANSACTION");
/*45557770*/
    $sql = "UPDATE  tbl_os
            SET     exportado = NULL
            WHERE   fabrica = $login_fabrica
            AND     os IN ($os)
    ";
    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
        exit;
    }

    pg_query($con,"COMMIT TRANSACTION");
    echo json_encode(array("ok"=>true,"msg"=>"Os com Reenvio agendado"));
    exit;
}

if (filter_input(INPUT_POST,'arquivo_kof')) {
    $arquivo_kof = filter_input(INPUT_POST,"arquivo_kof");

    if (empty($arquivo_kof)) {
        $msg_erro["msg"][] = "Preencher os campos obrigatórios";
    } else {

        $sqlOs = "SELECT  tbl_os.os,
                        tbl_os.sua_os,
                        JSON_FIELD('arquivo_saida_kof',tbl_os_campo_extra.campos_adicionais)    AS arquivo_saida_kof
                FROM    tbl_os
                JOIN    tbl_os_campo_extra USING(fabrica,os)
                WHERE   fabrica = $login_fabrica
                AND     exportado IS NOT NULL
                AND     JSON_FIELD('arquivo_saida_kof',tbl_os_campo_extra.campos_adicionais) = '$arquivo_kof'
          ORDER BY      tbl_os.os
        ";
        $resOs = pg_query($con,$sqlOs);

        if (!pg_num_rows($resOs)) {
            $msg_erro["alerta"][] = "Nenhum resultado encontrado";
        }
    }
}

$title = "REENVIO OS PARA KOF";
$layout_menu = "gerencia";

include "cabecalho_new.php";
?>
<script type="text/javascript">

$(function(){
    $("#todas").click(function(){
        if ($(this).is(":checked")) {
            $("input[id^=exp_]").prop("checked",true);
        } else {
            $("input[id^=exp_]").prop("checked",false);
        }
    });

    $("#exportar").click(function(e){
        e.preventDefault();

        var osExportar = [];

        $.each($("input[id^=exp_]:checked"),function(k,v){
            osExportar.push($(this).val());
        });

        $.ajax({
            url:"upload_os_kof.php",
            dataType:"JSON",
            type:"POST",
            data:{
                ajax:true,
                tipo:"upload_kof",
                exportar:osExportar
            },
            beforeSend:function(){
                $("input[id^=exp_]").prop("disabled",true);
                $("#exportar").prop("disabled",true);
            }
        })
        .done(function(data){
            if (data.ok) {
                alert(data.msg);
                location.href = "<?=$PHP_SELF?>";
            }
        });
    });
});

</script>
<style type="text/css">
.resultado {
    width:400px;
    margin:0 auto;
}
</style>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div id='alertError' class="alert alert-error no-print" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (count($msg_erro["alerta"]) > 0) {
?>
    <div id='Alert' class="alert no-print" >
        <h4><?=implode("<br />", $msg_erro["alerta"])?></h4>
    </div>
<?php
}

?>
<div class="row" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
    <br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class="control-group">
                <label class="control-label" for="arquivo_kof">Nome Arquivo</label>
                <div class="controls controls-row">
                    <div class="span4">
                        <h5 class="asteristico">*</h5>
                        <input id="arquivo_kof" name="arquivo_kof" class="span12 " value="<?=$arquivo_kof?>" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <br />
    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>
    <br />
</form>

<?php

if ($_POST['arquivo_kof'] && empty($msg_erro["msg"]) && empty($msg_erro["alerta"])) {
?>
<table class="table table-bordered resultado" >
    <thead>
        <tr class="titulo_coluna" >
            <th style="width:30%;">Marcar Todas<br /><input type="checkbox" name="todas" id="todas" value="" /></th>
            <th>OS</th>
        </tr>
    </thead>
    <tbody>
<?php
    while ($result = pg_fetch_object($resOs)) {
?>
        <tr>
            <td style="text-align:center;"><input type="checkbox" name="exp[]" id="exp_<?=$result->os?>" value="<?=$result->os?>"> </td>
            <td style="text-align:center;"><a href="os_press.php?os=<?=$result->os?>" target="_blank"><?=$result->sua_os?></a></td>
        </tr>
<?php
    }
?>
    </tbody>
</table>
<br />
    <p class="tac" >
        <button name="exportar" id="exportar" class="btn" >Adicionar para a fila de exportação</button>
    </p>
<br />
<?php
}

include 'rodape.php';
?>
