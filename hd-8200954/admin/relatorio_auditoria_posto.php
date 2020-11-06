<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios="Auditoria";

if ($_POST["btn_acao"] == "ok") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $os_finalizadas     = $_POST['os_finalizadas'];
    $cento_e_oitenta = date("Y-m-d",strtotime("-180 days"));

    if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);

        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }else{
            $mostra_data_inicial = $d."/".$m."/".$y;
        }
        if($xdata_inicial < $cento_e_oitenta){
            $msg_erro["msg"][]    ="Data Inicial está fora da margem de 180 dias da busca";
            $msg_erro["campos"][] = "data_final";
        }
    }else{
        $xdata_inicial = $cento_e_oitenta;
        $mostra_data_inicial = date("d/m/Y",strtotime("-180 days"));
    }

    if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);

        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }else{
            $mostra_data_final = $d."/".$m."/".$y;
        }
    }else{
        $xdata_final        = date("Y-m-d");
        $mostra_data_final  = date("d/m/Y");
    }

    if($xdata_inicial > $xdata_final){
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data";
    }



    if(strlen($codigo_posto) == 0 || strlen($descricao_posto) == 0){
        $msg_erro["msg"][]    ="Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "posto";
    }

    if(!isset($os_finalizadas)){
      $todas_os = " AND     tbl_os.finalizada IS NULL";
    }

    /**
    * - Antes das buscas, obter o ID do posto
    */
    $sql = "SELECT  posto
            FROM    tbl_posto_fabrica
            WHERE   fabrica      = $login_fabrica
            AND     codigo_posto = '$codigo_posto';
    ";
    $res = pg_query($con,$sql);
    $posto = pg_fetch_result($res,0,posto);

    /**
    * - Criação de TEMP para todas as OS
    * do intervalo de 180 dias ou da data pedida
    */
    $sqlTemp = "
        SELECT  tbl_os.os                   ,
                tbl_os.data_abertura        ,
                tbl_os.data_digitacao       ,
                tbl_os.os_reincidente       ,
                tbl_os_item.os_item         ,
                tbl_os_item.digitacao_item
   INTO TEMP    os_busca
        FROM    tbl_os
   LEFT JOIN    tbl_os_produto USING(os)
   LEFT JOIN    tbl_os_item USING(os_produto)
        WHERE   tbl_os.fabrica = $login_fabrica
        AND     tbl_os.posto = $posto
        $todas_os
        AND     tbl_os.excluida IS NOT TRUE
        AND     tbl_os.data_digitacao::DATE BETWEEN '$xdata_inicial' AND '$xdata_final'
    ";
//     echo nl2br($sqlTemp);exit;
    $resTemp = pg_query($con,$sqlTemp);

    /**
    * - Busca de OS abertas
    */
    $sqlAberta = "
        SELECT  DISTINCT
                os
        FROM    os_busca
    ";
    $resAberta  = pg_query($con,$sqlAberta);
    $total_os   = pg_num_rows($resAberta);

    /*
    * - Busca de OS Abertas
    * Sem Peças
    */

    $sqlSemP = "
        SELECT  COUNT(tbl_os_item.os_item) AS qtde_itens
        FROM    tbl_os
   LEFT JOIN    tbl_os_produto  USING (os)
   LEFT JOIN    tbl_os_item     USING (os_produto)
        WHERE   tbl_os.fabrica      = $login_fabrica
        AND     tbl_os.posto        = $posto
        AND     tbl_os.excluida     IS NOT TRUE
        AND     tbl_os.finalizada   IS NULL
        AND     tbl_os.data_digitacao::DATE BETWEEN '$xdata_inicial' AND '$xdata_final'
  GROUP BY      os
    ";
    $resSemP = pg_query($con,$sqlSemP);
    $item = pg_fetch_all_columns($resSemP);
    $contaZero = 0;

    for($i = 0;$i<count($item);$i++){
        if($item[$i] == 0){
            $contaZero++;
        }
    }

    /**
    * - Busca de OS abertas por mais de 30 dias
    */
    $sqlTrinta = "
        SELECT  DISTINCT
                os
        FROM    os_busca
        WHERE   data_digitacao::DATE < CURRENT_DATE - INTERVAL '30 days'
    ";
    $resTrinta      = pg_query($con,$sqlTrinta);
    $totalTrinta    = pg_num_rows($resTrinta);

    /**
    * - Tempo médio de digitação da OS em relação
    * à sua abertura
    */

    $sqlMediaAbertura = "
        SELECT  AVG(AGE(data_digitacao::DATE,data_abertura)) AS diferenca_abertura
        FROM    os_busca;
    ";
    $resMediaAbertura   = pg_query($con,$sqlMediaAbertura);
    $mediaAbertura      = pg_fetch_result($resMediaAbertura,0,diferenca_abertura);
    if(strstr($mediaAbertura,"day")){
        $mediaAbertura     = explode("day",$mediaAbertura);
        $mediaAbertura     = $mediaAbertura[0]." dia(s)";
    }else{
        $mediaAbertura = explode(".",$mediaAbertura);
        $aux = explode(":",$mediaAbertura[0]);
        if($aux[0] != "00"){
            $mediaAbertura = $aux[0]."hrs".$aux[1]." minutos";
        }else{
            $mediaAbertura = $aux[1]." minutos";
        }
    }

    /**
    * - Tempo médio de digitação
    * das peças da OS
    */

    $sqlMediaPecas = "
        SELECT  AVG(AGE(digitacao_item,data_digitacao::DATE)) AS diferenca_digitacao_item
        FROM    os_busca
    ";
    $resMediaPecas  = pg_query($con,$sqlMediaPecas);
    $mediaPecas     = pg_fetch_result($resMediaPecas,0,diferenca_digitacao_item);
    if(strstr($mediaPecas,"day")){
        $mediaPecas     = explode("day",$mediaPecas);
        $mediaPecas     = $mediaPecas[0]." dia(s)";
    }else{
        $mediaPecas = explode(".",$mediaPecas);
        $aux = explode(":",$mediaPecas[0]);
        if($aux[0] != "00"){
            $mediaPecas = $aux[0]."hrs".$aux[1];
        }else{
            $mediaPecas = $aux[1]." minutos";
        }
    }

    /**
    * - Porcentagem de
    * reincidências
    */

    $sqlReincidencia = "
        SELECT  DISTINCT
                os
        FROM    os_busca
        WHERE   os_reincidente IS TRUE
    ";
    $resReincidencia        = pg_query($con,$sqlReincidencia);
    $aux_reincidencia       = pg_num_rows($resReincidencia);
    $porcentoReincidencia   = $aux_reincidencia / $total_os * 100;

    /**
    * - Porcentagem de OS
    * com menos de trinta dias de abertura
    */

    $sqlMenosTrinta = "
        SELECT  DISTINCT
                os
        FROM    os_busca
        WHERE   data_digitacao > CURRENT_DATE - INTERVAL '30 days'
    ";
    $resMenosTrinta         = pg_query($con,$sqlMenosTrinta);
    $aux_menosTrinta        = pg_num_rows($resMenosTrinta);
    $porcentoMenosTrinta    = $aux_menosTrinta / $total_os * 100;

    /**
    * - Faz a média de peças das OS
    */

    $sqlAuxMedia = "
        SELECT  os,
                COUNT(os_item) AS qtde_peca
   INTO TEMP    media_os
        FROM    os_busca
  GROUP BY      os;

        SELECT  AVG(qtde_peca) AS media_pecas
        FROM    media_os;
    ";

    $resMedia   = pg_query($con,$sqlAuxMedia);
    $auxPecas   = pg_fetch_result($resMedia,0,media_pecas);
    $pecaPorOs  = number_format($auxPecas,2,',','.');

}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE AUDITORIA DE POSTOS";

include "cabecalho_new.php";
$plugins = array(   "datepicker",
                    "mask",
                    "dataTable",
                    "autocomplete",
                    "shadowbox"
);
include "plugin_loader.php";
?>
<script type="text/javascript" charset="utf-8">
$(function(){
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}
</script>

<?
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
<form name='frm_auditoria_posto' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela ' style="margin-bottom:15px;">Parâmetros de Pesquisa</div>
    <?php
        if ($login_fabrica == 3) {
    ?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8 alert alert-warning" style="text-align:center;padding:0px;">
                <h5>AVISO: A data inicial não deve ser anterior a 180 dias da data atual.</h5>
            </div>
            <div class="span2"></div>
        </div>
    <?php
        }
    ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='os_finalizadas'>Listar Todas Os</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="checkbox" name="os_finalizadas"  <?php if (isset($os_finalizadas)) echo "checked"; ?> value= "true">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
        </div>
        <div class='span2'></div>
    </div>
    <input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
    <div class="row-fluid">
        <!-- margem -->
        <div class="span4"></div>

        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button type="button" class="btn" value="Pesquisar" alt="Pesquisar Postos" onclick="submitForm($(this).parents('form'),'ok');" > Pesquisar</button>
                </div>
            </div>
        </div>

        <!-- margem -->
        <div class="span4"></div>
    </div>
</form>
<?php
if (isset($resTemp) && count($msg_erro["msg"]) == 0) {
    if ($total_os > 0) {
        echo "<br />";
?>
<table id="relatorio_auditoria_posto" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_tabela'>
           <th colspan="2">
                RESULTADOS PARA O POSTO <?=$$descricao_posto?> NO PERÍODO DE <?=$mostra_data_inicial?> À <?=$mostra_data_final?>
           </th>
        <tr>
        <tr class='titulo_tabela'>
            <th>Descrição</th>
            <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class='tal'><?php if(isset($os_finalizadas)) echo "TODAS AS OS"; else echo "OS EM ABERTO"; ?></td>
            <td class='tac'><?=$total_os?></td>
        </tr>
        <tr>
            <td class='tal '><?php if(isset($os_finalizadas)) echo "TODAS AS OS ACIMA DE TRINTA DIAS"; else echo "OS ABERTAS ACIMA DE TRINTA DIAS"; ?></td>
            <td class='tac'><?=$totalTrinta?></td>
        </tr>

        <tr>
            <td class='tal '>OS EM ABERTO SEM PEÇAS</td>
            <td class='tac'><?=$contaZero?></td>
        </tr>

        <tr>
            <td class='tal '>TEMPO MÉDIO DE DIGITAÇÃO DA OS</td>
            <td class='tac'><?=$mediaAbertura?></td>
        </tr>
        <tr>
            <td class='tal '>TEMPO MÉDIO DE DIGITAÇÃO DA PEÇA</td>
            <td class='tac'><?=$mediaPecas?></td>
        </tr>
        <tr>
            <td class='tal '>REINCIDÊNCIAS (%)</td>
            <td class='tac'><? echo number_format($porcentoReincidencia,2,',',''); ?></td>
        </tr>
        <tr>
            <td class='tal '>OS ABERTAS ABAIXO DE TRINTA DIAS (%)</td>
            <td class='tac'><? echo number_format($porcentoMenosTrinta,2,',',''); ?></td>
        </tr>
        <tr>
            <td class='tal '>MÉDIA DE PEÇAS POR OS</td>
            <td class='tac'><?=$pecaPorOs?></td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="titulo_tabela">
            <td colspan="2">
                &nbsp;
            </td>
        </tr>
    </tfoot>
</table>
<?
    }else{
?>
<div class="alert alert-error">
    <h4>Nenhuma OS encontrada no período</h4>
</div>
<?
    }
}
include "rodape.php";
?>
