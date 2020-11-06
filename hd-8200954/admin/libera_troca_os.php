<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "../class/email/PHPMailer/class.phpmailer.php";
include "../class/email/PHPMailer/PHPMailerAutoload.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include 'funcoes.php';

$sql = "SELECT  aprova_laudo
        FROM    tbl_admin
        WHERE   fabrica = $login_fabrica
        AND     admin = $login_admin
";
$res = pg_query($con,$sql);
$aprova_laudo = pg_fetch_result($res,0,aprova_laudo);
// echo nl2br($sql);exit;

$btn_acao    = trim($_POST["btn_acao"]);
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
        if ($tipo_busca == "codigo"){
            $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
        }else{
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cnpj         = trim(pg_fetch_result($res,$i,cnpj));
                $nome         = trim(pg_fetch_result($res,$i,nome));
                $codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
                echo "$cnpj|$nome|$codigo_posto";
                echo "\n";
            }
        }
    }
    exit;
}



$layout_menu = "callcenter";
$title = "LIBERAÇÃO DE ORDEM DE SERVIÇO";

include 'cabecalho_new.php';


$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("#classificacao_atendimento").multiselect({
           selectedText: "selecionados # de #"
        });

        var table = new Object();
        table['table'] = '#resultado_os';
        table['type'] = 'full';
        $.dataTableLoad(table);
    });
</script>

<?
if($btn_acao == 'submit'){
    $data_inicial           = $_POST['data_inicial'];
    $data_final             = $_POST['data_final'];
    $laudo                  = $_POST['laudo'];
    $os_troca_especifica    = $_POST['os_troca_especifica'];
    $posto_codigo           = $_POST['posto_codigo'];

    if((strlen($data_inicial) == 0 or strlen($data_final) == 0) and strlen($os_troca_especifica) == 0) {
        $msg_erro["msg"][]    = "É necessário a inclusão das datas ou a busca direta por OS";
        $msg_erro["campos"][] = "data";
    }

    if(strlen($data_inicial) > 0 and strlen($data_final) > 0 and strlen($os_troca_especifica) == 0) {

        if(!count($msg_erro["msg"])){
            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi)) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";
            }
        }

        if(!count($msg_erro["msg"])){
            list($df, $mf, $yf) = explode("/", $data_final);
            if(!checkdate($mf,$df,$yf)) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";
            }
        }

        if(!count($msg_erro["msg"])){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";
        }
        if(!count($msg_erro["msg"])){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
            or strtotime($aux_data_final) > strtotime('today')){
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Atual";
                $msg_erro["campos"][] = "data";
            }
        }

        if(!count($msg_erro["msg"])){
            if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final) ) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }

        $xdata_inicial = formata_data ($data_inicial);
        $xdata_inicial = $xdata_inicial." 00:00:00";

        $xdata_final = formata_data ($data_final);
        $xdata_final = $xdata_final." 23:59:59";
    }

    if(strlen($posto_codigo) > 0){
        $sqlPosto = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   tbl_posto_fabrica.codigo_posto  = '$posto_codigo'
                AND     tbl_posto_fabrica.fabrica       = $login_fabrica ";
        $resPosto = pg_query($con,$sqlPosto);
        if(pg_numrows($resPosto) > 0) {
            $posto = pg_fetch_result($resPosto,0,'posto');
        }else{
            $msg_erro["msg"][] = "Posto informado nao encontrado";
        }
    }
}

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }

if (strlen($msg_sucesso) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=$msg_sucesso;?></h4>
    </div>
<?php } ?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
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
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <?php if ($login_fabrica == 30) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='classificacao_atendimento'>Classificação do Atendimento</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <select id="classificacao_atendimento" name="classificacao_atendimento[]" multiple="multiple" size="1" class="frm">
                                <?php
                                    $aux_sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND hd_classificacao IN (50, 51, 52) ORDER BY descricao";
                                    $aux_res = pg_query($con, $aux_sql);
                                    $aux_row = pg_num_rows($aux_res);

                                    for ($wx = 0; $wx < $aux_row; $wx++) { 
                                        $hd_classificacao = pg_fetch_result($aux_res, $wx, 'hd_classificacao');
                                        $hd_descricao     = pg_fetch_result($aux_res, $wx, 'descricao');

                                        if ($_POST["classificacao_atendimento"] == $hd_classificacao) {
                                            $selected = "SELECTED";
                                        } else {
                                            $selected = "";
                                        }

                                        ?> <option <?=$selected;?> value="<?=$hd_classificacao;?>"><?=$hd_descricao;?></option> <?
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='tipo_laudo'>Tipo de Laudo</label>
                    <div class='controls controls-row'>
                        <div class='span7'>
                            <label class="span2" for="fat_fat">
                                <input type="radio" name="laudo" id="fat_fat" value='fat' <? if(trim($laudo) == 'fat') echo "checked='checked'"; ?>>&nbsp;FAT
                            </label>
                            <label class="span2" for="fat_far">
                                <input type="radio" name="laudo" id="fat_far" value='far' <? if(trim($laudo) == 'far') echo "checked='checked'"; ?>>&nbsp;FAR
                            </label>
                            <label class="spa4" for="fat_fats">
                                <input type="radio" name="laudo" id="fat_fats" value='fats' <? if(trim($laudo) == 'fats') echo "checked='checked'"; ?>>&nbsp;FAT SINISTRO
                            </label>
                            <label class="span4" for="fat_fatrev">
                                <input type="radio" name="laudo" id="fat_fatrev"value='fatrev' <? if(trim($laudo) == 'fatrev') echo "checked='checked'"; ?>>&nbsp;FAT REVENDA
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <?php } ?>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<?

## Aqui começa o PROCESSO DE PESQUISA
if ($btn_acao == 'submit' and !count($msg_erro["msg"])) {
    $codigo_posto = $_POST['posto_codigo'];

    if ($login_fabrica == 30 && !empty($_POST["classificacao_atendimento"])) {
        $classificacao_atendimento = $_POST["classificacao_atendimento"];

        for ($wx=0; $wx < count($classificacao_atendimento); $wx++) { 
            $classificacao_atendimento[$wx] = "'" . $classificacao_atendimento[$wx] . "'";
        }

        $join_os_campo_extra  = " LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica ";
        $where_os_campo_extra .= "\nAND JSON_FIELD('hd_classificacao',tbl_os_campo_extra.campos_adicionais) IN (". implode(",", $classificacao_atendimento) .")";
    } else {
        $join_os_campo_extra  = "";
        $where_os_campo_extra = "";
    }

    $sql="  SELECT  tbl_os_status.os,
                    tbl_os_status.status_os,
                    tbl_os_status.admin
       INTO TEMP    tmp_os_aprovada_antes
            FROM    tbl_os_status
            WHERE   tbl_os_status.fabrica_status = $login_fabrica
            AND     tbl_os_status.status_os = 193;

            CREATE INDEX idx_os_aprovada_antes ON tmp_os_aprovada_antes(os);

            SELECT  DISTINCT
                    tbl_os.os                                                                   ,
                    tbl_os.sua_os                                                               ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')          AS data_abertura_os     ,
                    TO_CHAR(tbl_laudo_tecnico_os.data,'DD/MM/YYYY')     AS data_laudo           ,
                    tbl_posto_fabrica.codigo_posto                                              ,
                    tbl_posto.nome                                      AS nome_posto           ,
                    AGE(CURRENT_DATE,tbl_laudo_tecnico_os.data::DATE)   AS dias_decorrentes     ,
                    tbl_produto.referencia                              AS produto_referencia   ,
                    tbl_produto.descricao                               AS produto_descricao    ,
                    tbl_admin.nome_completo                             AS responsavel          ,
                    CASE WHEN tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"fat\"%'
                         THEN 'fat'
                         WHEN tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"far\"%'
                         THEN 'far'
                         WHEN tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"fatrev\"%'
                         THEN 'fatrev'
                         ELSE 'fats'
                    END                                                 AS laudo
            FROM    tbl_os
            JOIN    tbl_laudo_tecnico_os    ON  tbl_laudo_tecnico_os.os     = tbl_os.os
            JOIN    tbl_os_troca            ON  tbl_os_troca.os             = tbl_os.os
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_posto_fabrica.posto
            LEFT JOIN    tbl_os_produto          ON  tbl_os_produto.os           = tbl_os.os
            JOIN    tbl_produto             ON  tbl_produto.produto         = tbl_os.produto
            JOIN    tmp_os_aprovada_antes   ON  tmp_os_aprovada_antes.os    = tbl_os.os
            JOIN    tbl_admin               ON  tbl_admin.admin             = tmp_os_aprovada_antes.admin
            $join_os_campo_extra
            WHERE   
            tbl_os.fabrica = $login_fabrica
            AND tbl_os_troca.status_os = 193 
            AND tbl_laudo_tecnico_os.afirmativa IS TRUE 
            AND tbl_os_troca.ressarcimento IS NOT TRUE
            AND tbl_os_troca.gerar_pedido IS NOT TRUE
            $where_os_campo_extra
    ";

    if(strlen($posto) > 0){
        $sql .= "\nAND tbl_posto.posto = $posto ";
    }

    if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
        $sql .= "\nAND tbl_laudo_tecnico_os.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
    }
    if(strlen($laudo) > 0){
        switch($laudo){
            case "fat":
                $sql .= "\nAND tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"fat\"%'";
            break;
            case "far":
                $sql .= "\nAND tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"far\"%'";
            break;
            case "fats":
                $sql .= "\nAND tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"fats\"%'";
            break;
            case "fatrev":
                $sql .= "\nAND tbl_laudo_tecnico_os.observacao LIKE '%\"laudo\":\"fatrev\"%'";
            break;
        }
    }

    if(strlen($os_troca_especifica) > 0){
        $sql .= "\nAND tbl_os.os = $os_troca_especifica";
    }
//     echo nl2br($sql);
    $res = pg_query($con,$sql);
    $qtde = pg_num_rows($res);

    if($qtde > 0){

?>
    </div>
    <form name='frm_pesquisa2' method='post' action='<?=$PHP_SELF?>'>
        <table id="resultado_os" class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_coluna'>
                    <th>OS</th>
                    <th>Data Abertura</th>
                    <th>Data Laudo</th>
                    <th>Dias decorridos</th>
                    <th>Posto</th>
                    <th>Produto</th>
                    <th>Defeito Constatado</th>
                    <th>Visualização</th>
                    <th>Laudo</th>
                    <th>Responsável</th>
                </tr>
            </thead>
            <tbody>
<?
        for($i=0;$i<$qtde;$i++){
            $auditoria_os                   = pg_fetch_result($res,$i,os);
            $auditoria_sua_os               = pg_fetch_result($res,$i,sua_os);
            $auditoria_data_abertura_os     = pg_fetch_result($res,$i,data_abertura_os);
            $auditoria_data_laudo           = pg_fetch_result($res,$i,data_laudo);
            $auditoria_codigo_posto         = pg_fetch_result($res,$i,codigo_posto);
            $auditoria_nome_posto           = pg_fetch_result($res,$i,nome_posto);
            $auditoria_dias_decorrentes     = pg_fetch_result($res,$i,dias_decorrentes);
            $auditoria_produto_referencia   = pg_fetch_result($res,$i,produto_referencia);
            $auditoria_produto_descricao    = pg_fetch_result($res,$i,produto_descricao);
            $auditoria_responsavel          = pg_fetch_result($res,$i,responsavel);
            $auditoria_status_laudo         = pg_fetch_result($res,$i,status_laudo);
            $auditoria_laudo                = pg_fetch_result($res,$i,laudo);

            $auditoria_dias_decorrentes = substr($auditoria_dias_decorrentes,0,2);
            $cor = ($i%2) ? "#F7F5F0": '#F1F4FA';

            $sqlD = "SELECT DISTINCT
                            tbl_defeito_constatado.codigo,
                            tbl_defeito_constatado.descricao
                    FROM    tbl_os_defeito_reclamado_constatado
                    JOIN    tbl_defeito_constatado USING(defeito_constatado)
                    WHERE   os = $auditoria_os";
            $resD = pg_query($con,$sqlD);
            $array_integridade = array();

            for ($j=0;$j<pg_num_rows($resD);$j++){
                $aux_defeito_constatado = pg_fetch_result($resD,$j,0).'-'.pg_fetch_result($resD,$j,1);
                array_push($array_integridade,$aux_defeito_constatado);
            }

            $lista_defeitos = implode($array_integridade,", ");
?>
                <tr>
                    <td class="tal">
                        <a href='os_press.php?os=<?=$auditoria_os?>' target='_blank'><?=$auditoria_sua_os?></a>
                    </td>
                    <td class="tac">
                        <?=$auditoria_data_abertura_os?>
                    </td>
                    <td class="tac">
                        <?=$auditoria_data_laudo?>
                    </td>
                    <td class="tal">
                        <?=$auditoria_dias_decorrentes?>        
                    </td>
                    <td  class="tal">
                        <acronym title="Posto: <?=$auditoria_codigo_posto." - ".$auditoria_nome_posto?>" style="cursor:help;"><?=substr($auditoria_codigo_posto." - ".$auditoria_nome_posto,0,30)?></acronym>
                    </td>
                    <td class="tal">
                        <acronym title="Produto: <?=$auditoria_produto_referencia." - ".$auditoria_produto_descricao?>" style="cursor:help;"><?=substr($auditoria_produto_referencia." - ".$auditoria_produto_descricao,0,30)?></acronym>
                    </td>
                    <td class="tal">
                        <?=$lista_defeitos?>
                    </td>
                    <td class="tac">
                        <a href="cadastro_laudo_troca.php?imprimir=<?=$auditoria_os?>&admin=sim&laudo=<?=$auditoria_laudo?>" target="_blank">Ver</a>
                    </td>
                    <td class="tac">
                        <a href="cadastro_laudo_troca.php?liberar=<?=$auditoria_os?>&admin=sim&laudo=<?=$auditoria_laudo?>" target="_blank">Liberar OS</a>
                    </td>
                    <td class="tal">
                        <?=$auditoria_responsavel?>
                    </td>
                </tr>
<? } ?>
            </tbody>
            <tfoot>
                <input type='hidden' name='qtde_os' value='<?=$i?>'>
                <input type='hidden' name='btn_acao' value='Pesquisar'>
                <tr style='background-color:#485989;color:white;font-weight:bold;text-align:left'>
                    <td colspan = "11">&nbsp;</td>
                </tr>
            </tfoot>
        </table>
    </form>
<? } else { ?>
        <div class='container'>
            <div class="alert">
                <h4>Não foi encontrada OS de Troca.<</h4>
            </div>  
        </div>
<? } 
}

include 'rodape.php';

?>
