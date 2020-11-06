<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE TEMPO DE ATENDIMENTO DE OS";

if(isset($_POST["btn_acao"])){

    if(strlen($data_inicial) > 0 && $data_inicial != "dd/mm/aaaa"){
        $xdata_inicial = fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'", "", $xdata_inicial);
    }else{
        $msg_erro["msg"][]    = "Data Inicial Inválida";
        $msg_erro["campos"][] = "data_inicial";
    }

    if(strlen($data_final) > 0 && $data_final != "dd/mm/aaaa"){
        $xdata_final = fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'", "", $xdata_final);
    }else{
        $msg_erro["msg"][]    ="Data Final Inválida";
        $msg_erro["campos"][] = "data_final";
    }

    if (!empty($_POST["tipo_posto"])) {
        $cond_tipo_posto = "AND tbl_posto_fabrica.tipo_posto = {$tipo_posto}";
		$link = "&tipo_posto=$tipo_posto";
    }

    if(count($msg_erro) == 0){

        $dat = explode("/", $data_inicial); //tira a barra

        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    = "Data Inicial Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }

    }

    if(count($msg_erro) == 0){

        $dat = explode("/", $data_final); //tira a barra

        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    = "Data Final Inválida";
            $msg_erro["campos"][] = "data_final";
        }

    }

    if(count($msg_erro) == 0){

        list($dia, $mes, $ano) = explode("/", $data_inicial);
        $xdata_inicial = $ano."-".$mes."-".$dia;

        list($dia, $mes, $ano) = explode("/", $data_final);
        $xdata_final = $ano."-".$mes."-".$dia;

        $cond_data = " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";

        if(strlen($_POST["estado"]) > 0){

            $estado = $_POST["estado"];

            switch ($estado) {
                case 'norte':
                    $arr_cond_estado[$estado] = " AND tbl_os.consumidor_estado IN ('AC', 'AP', 'AM', 'PA', 'RR', 'RO', 'TO') ";
                    break;
                case 'nordeste':
                    $arr_cond_estado[$estado] = " AND tbl_os.consumidor_estado IN ('AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE') ";
                    break;
                case 'centro_oeste':
                    $arr_cond_estado[$estado] = " AND tbl_os.consumidor_estado IN ('DF', 'GO', 'MT', 'MS') ";
                    break;
                case 'sudeste':
                    $arr_cond_estado[$estado] = " AND tbl_os.consumidor_estado IN ('ES', 'MG', 'RJ', 'SP') ";
                    break;
                case 'sul':
                    $arr_cond_estado[$estado] = " AND tbl_os.consumidor_estado IN ('PR', 'RS', 'SC') ";
                    break;
                default:
                    $arr_cond_estado[$estado] = " AND tbl_os.consumidor_estado = '{$estado}' ";
            }
        
        } else {
            $arr_cond_estado = array(
                'centro_oeste' => " AND tbl_os.consumidor_estado IN ('DF', 'GO', 'MT', 'MS') ",
                'nordeste' => " AND tbl_os.consumidor_estado IN ('AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE') ",
                'norte' => " AND tbl_os.consumidor_estado IN ('AC', 'AP', 'AM', 'PA', 'RR', 'RO', 'TO') ",
                'sudeste' => " AND tbl_os.consumidor_estado IN ('ES', 'MG', 'RJ', 'SP') ",
                'sul' => " AND tbl_os.consumidor_estado IN ('PR', 'RS', 'SC') ",
                'Total Geral' => " AND tbl_os.consumidor_estado IN ('DF', 'GO', 'MT', 'MS','AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE','AC', 'AP', 'AM', 'PA', 'RR', 'RO', 'TO', 'ES', 'MG', 'RJ', 'SP','PR', 'RS', 'SC') "
            );
        }

        if(strlen($_POST["posto_id"]) > 0 && strlen($_POST["codigo_posto"]) > 0 && strlen($_POST["descricao_posto"]) > 0){
            $posto_id = $_POST["posto_id"];
            $cond_posto = " AND tbl_os.posto = {$posto_id} ";
			$link .="&posto=$posto_id";
        }

        $qtde_intervalo = array();
        $intervalo      = array(3, 7, 14, 21, 30, 31);
        
        $arr_result = array();

        foreach ($arr_cond_estado as $k => $cond_estado) {

            $dia_ant        = 0;

            foreach ($intervalo as $dia) {

                if($dia == 31){
                    $cond_intervalo = " >= '{$dia}' ";
                }else{
                    $cond_intervalo = " BETWEEN '{$dia_ant}' AND '{$dia}' ";
                }

                $sql = "SELECT 
                            tbl_os.os,
                            (EXTRACT(EPOCH FROM data_conserto - data_abertura::TIMESTAMP)/3600)::INT as tempo_conserto
                            INTO TEMP temp_tempo_atendimento_os
                        FROM tbl_os  
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        WHERE 
                            tbl_os.fabrica = {$login_fabrica} 
                            AND tbl_os.finalizada NOTNULL 
                            {$cond_data}
                            {$cond_estado} 
                            {$cond_posto} 
                            {$cond_tipo_posto}
                            AND tbl_os.data_conserto NOTNULL 
                            AND tbl_os.data_conserto::date - tbl_os.data_abertura::date $cond_intervalo;

                        SELECT COUNT(os) AS qtde_os, SUM(tempo_conserto) AS tempo_conserto FROM temp_tempo_atendimento_os";
                $result = pg_query($con, $sql);

                $qtde_os = pg_fetch_result($result, 0, "qtde_os");
                $str_tempo_conserto = pg_fetch_result($result, 0, "tempo_conserto");

                $qtde_intervalo[$dia] = (int) $qtde_os;
                $tempo_conserto[$dia] = (int) $str_tempo_conserto;

                $dia_ant = $dia + 1;

                $drop_temp = pg_query($con, "DROP TABLE temp_tempo_atendimento_os");
            }

            $arr_result[$k] = array(
                "qtde_os" => $qtde_intervalo,
                "tempo_conserto" => $tempo_conserto
            );

            $sql_num = pg_num_rows($result);
        }

        if (!empty($_POST["gerar_xls"])) {

            $ultimo = array_slice($arr_result, -1);
            $arr_result_csv= $ultimo + $arr_result;

            foreach ($arr_result_csv as $key => $value) {

                foreach ($value['qtde_os'] as $chave => $valor) {

                    $valor_intervalo['total_qtde_os'][$chave][$key] = $value["qtde_os"][$chave];
                    $valor_intervalo['total_tempo_conserto'][$chave][$key] = $value["tempo_conserto"][$chave];

                    // calculo do TAT                    
                    if ($value["qtde_os"][$chave] == 0) {
                        $valor_intervalo['total_tat'][$chave][$key] = 0;
                    } else {
                        $valor_intervalo['total_tat'][$chave][$key] = round(($value["tempo_conserto"][$chave] / $value["qtde_os"][$chave]), 2);
                    }

                    // Calculo %
                    if ($key === "Total Geral") {
                        $valor_intervalo['percentual'][$chave][$key] = "";
                    } else {
                        if ($value["qtde_os"][$chave] == 0) {
                            $valor_intervalo['percentual'][$chave][$key] = "0%";
                        } else {                            
                            $valor_intervalo['percentual'][$chave][$key] = round((($value["qtde_os"][$chave]/$arr_result_csv['Total Geral']['qtde_os'][$chave]) * 100),2)."%" ;
                        }
                    }
                }

            }

            $header = "Conteúdo;";
            foreach ($valor_intervalo['total_qtde_os'] as $dias => $regioes) {
                $header .= "$dias dias;";
                foreach ($regioes as $estado => $qtde) {
                    if ($estado !== 'Total Geral') {
                        $header .= "".strtoupper(str_replace('_', '-', $estado)).";";
                    }
                }
            }
            $header .= "\n";

            foreach ($valor_intervalo as $desc_calc => $arr_dias) {
                if ($desc_calc == 'total_qtde_os') {
                    $body .= "Qtde OS;";
                } elseif ($desc_calc == 'total_tempo_conserto') {
                    $body .= "Tempo Conserto;";
                } elseif ($desc_calc == 'total_tat') {
                    $body .= "TAT;";
                } elseif ($desc_calc == 'percentual') {
                    $body .= "Percentual;";
                }

                foreach ($arr_dias as $dia => $arr_qtde) {
                    foreach ($arr_qtde as $qtde) {
                        $body .= "$qtde;";
                    }                    
                }
                $body .= "\n";
            }

            $conteudo = $header;
            $conteudo .= $body;

            $csv_name = 'xls/relatorio_tempo_atendimento_os_' . substr(sha1($login_admin), 0, 6) . date('Ymd') . '.csv';
            file_put_contents($csv_name, utf8_encode($conteudo));

            die("$csv_name");
        }
    }

}

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">

$(function() {

    $.datepickerLoad(Array("data_final", "data_inicial"));
    // $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

});

function retorna_posto(retorno){
    $("#posto_id").val(retorno.posto);
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function gera_csv() {
    var data_inicial = $("#data_inicial").val();
    var data_final = $("#data_final").val();
    var estado = $("#estado").val();
    var codigo_posto = $("#codigo_posto").val();
    var descricao_posto = $("#descricao_posto").val();
    var tipo_posto = $("#tipo_posto").val();

    $.ajax({
        type: 'POST',
        url: 'tempo_atendimento_os.php',
        data: {
            btn_acao: "sim",
            data_inicial: data_inicial,
            data_final: data_final,
            estado: estado,
            codigo_posto: codigo_posto,
            descricao_posto: descricao_posto,
            tipo_posto: tipo_posto,
            gerar_xls: "t"
        },
    }).done(function(data) {
        location = data;
    });
}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <strong class="obrigatorio pull-right">  * Campos obrigatórios </strong>
</div>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'>
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='data_final'>Regiões / Estados</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <option value="norte" <?php echo ($estado == "norte") ? "selected" : ""; ?> >Região Norte</option>
                            <option value="nordeste" <?php echo ($estado == "nordeste") ? "selected" : ""; ?> >Região Nordeste</option>
                            <option value="centro_oeste" <?php echo ($estado == "centro_oeste") ? "selected" : ""; ?> >Região Centro-Oeste</option>
                            <option value="sudeste" <?php echo ($estado == "sudeste") ? "selected" : ""; ?> >Região Sudeste</option>
                            <option value="sul" <?php echo ($estado == "sul") ? "selected" : ""; ?> >Região Sul</option>
                            <?php
                            #O $array_estados está no arquivo funcoes.php
                            foreach ($array_estados as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : "";
                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class="row-fluid">

        <div class='span2'></div>
        <div class='span3'>
            <input type="hidden" name="posto_id" id="posto_id" value="">
            <div class='control-group'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=$codigo_posto?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span5'>
            <div class='control-group'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>

    </div>

    <?php
    if ($login_fabrica == 164) {
    ?>
        <div class="row-fluid" >
            <div class='span2'></div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label' for='tipo_posto'>Tipo do Posto</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="tipo_posto" id="tipo_posto" >
                                <option value="" >Selecione</option>
                                <?php
                                $sqlTipoPosto = "
                                    SELECT tipo_posto, descricao
                                    FROM tbl_tipo_posto
                                    WHERE fabrica = {$login_fabrica}
                                    ORDER BY descricao ASC
                                ";
                                $resTipoPosto = pg_query($con, $sqlTipoPosto);

                                while ($tp = pg_fetch_object($resTipoPosto)) {
                                    $selected = ($tp->tipo_posto == $_POST["tipo_posto"]) ? "selected" : "";

                                    echo "<option value='{$tp->tipo_posto}' {$selected} >{$tp->descricao}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>

    <br />

    <button class='btn' id="btn_acao">Pesquisar</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='sim' />

    <br /> <br />

</form>

<?php

if(isset($arr_result)){

    if(!empty($arr_result)){

        $regioes = false;

        if (count($arr_result) > 1) {
            $regioes = true;
        }

        ?>

        </div>

        <div class="container" style="margin-top: 6px;">
            <div class="alert alert-warning">
                O tempo de atendimento é contabilizado entre a data de abertura e data de conserto.
            </div>
        </div>

        <?php foreach ($arr_result as $key => $value): ?>

            <?php
            $total_intervalo = array_sum($value["qtde_os"]);
            $total_conserto = array_sum($value["tempo_conserto"]);
            ?>

        <div style="margin: 5px !important;">

            <table class="table table-bordered table-striped" style="width: 850px;">
                <thead>
                    <?php if (true === $regioes): ?>
                        <tr class="titulo_coluna">
                            <th colspan="8">
                                <?php echo strtoupper(str_replace('_', '-', $key)) ?>
                            </th>
                        </tr>
                    <?php endif ?>
                    <tr class="titulo_coluna">
                        <th>Conteúdo</th>
                        <th>3 dias</th>
                        <th>7 dias</th>
                        <th>14 dias</th>
                        <th>21 dias</th>
                        <th>30 dias</th>
                        <th>Acima de 30 dias</th>
                        <th>Total Geral</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>

                        <!--
        
                        BETWEEN '0' AND '3' 
                        BETWEEN '4' AND '7' 
                        BETWEEN '8' AND '14' 
                        BETWEEN '15' AND '21' 
                        BETWEEN '22' AND '30' 
                        > '30' 

                        -->

                        <td class='tac'>Qtde OS</td>
                        <td class='tac'>
						<a href="os_consulta_lite.php?btn_acao=true&data_inicial=<?php echo $_POST["data_inicial"] ?>&data_final=<?php echo $_POST["data_final"]; ?>&periodo=0-3&regiao=<?=$key,$link?>" target="_blank" >
                                <?php echo $value["qtde_os"][3]; ?>
                            </a>
                        </td>
                        <td class='tac'>
                            <a href="os_consulta_lite.php?btn_acao=true&data_inicial=<?php echo $_POST["data_inicial"] ?>&data_final=<?php echo $_POST["data_final"]; ?>&periodo=4-7&regiao=<?=$key,$link?>" target="_blank" >
                                <?php echo $value["qtde_os"][7]; ?>
                            </a>
                        </td>
                        <td class='tac'>
                            <a href="os_consulta_lite.php?btn_acao=true&data_inicial=<?php echo $_POST["data_inicial"] ?>&data_final=<?php echo $_POST["data_final"]; ?>&periodo=8-14&regiao=<?=$key,$link?>" target="_blank" >
                                <?php echo $value["qtde_os"][14]; ?>
                            </a>
                        </td>
                        <td class='tac'>
                            <a href="os_consulta_lite.php?btn_acao=true&data_inicial=<?php echo $_POST["data_inicial"] ?>&data_final=<?php echo $_POST["data_final"]; ?>&periodo=15-21&regiao=<?=$key,$link?>" target="_blank" >
                                <?php echo $value["qtde_os"][21]; ?>
                            </a>
                        </td>
                        <td class='tac'>
                            <a href="os_consulta_lite.php?btn_acao=true&data_inicial=<?php echo $_POST["data_inicial"] ?>&data_final=<?php echo $_POST["data_final"]; ?>&periodo=22-30&regiao=<?=$key,$link?>" target="_blank" > 
                                <?php echo $value["qtde_os"][30]; ?>
                            </a>
                        </td>
                        <td class='tac'>
						<a href="os_consulta_lite.php?btn_acao=true&data_inicial=<?php echo $_POST["data_inicial"] ?>&data_final=<?php echo $_POST["data_final"]; ?>&periodo=31-31&regiao=<?=$key,$link?>" target="_blank" >
                                <?php echo $value["qtde_os"][31]; ?>
                            </a> 
                        </td>
                        <td class='tac'> <strong><?php echo $total_intervalo; ?></strong> </td>
                    </tr>

                    <tr>
                        <td class="tac">Tempo Conserto</td>
                        <td class="tac"><strong><?php echo $value["tempo_conserto"][3] ?></strong></td>
                        <td class="tac"><strong><?php echo $value["tempo_conserto"][7] ?></strong></td>
                        <td class="tac"><strong><?php echo $value["tempo_conserto"][14] ?></strong></td>
                        <td class="tac"><strong><?php echo $value["tempo_conserto"][21] ?></strong></td>
                        <td class="tac"><strong><?php echo $value["tempo_conserto"][30] ?></strong></td>
                        <td class="tac"><strong><?php echo $value["tempo_conserto"][31] ?></strong></td>
                        <td class="tac"><strong><?php echo $total_conserto; ?></strong></td>
                    </tr>

                    <tr>
                        <td class="tac">TAT</td>
                        <td class="tac">
                            <strong>
                                <?php
                                $total_tat = 0;

                                if (empty($value["qtde_os"][3])) {
                                    $tat = 0;
                                } else {
                                    $tat = round(($value["tempo_conserto"][3] / $value["qtde_os"][3]), 2);
                                }

                                echo $tat;
                                $total_tat += $tat;
                                ?>
                            </strong>
                        </td>
                        <td class="tac">
                            <strong>
                                <?php
                                if (empty($value["qtde_os"][7])) {
                                    $tat = 0;
                                } else {
                                    $tat = round(($value["tempo_conserto"][7] / $value["qtde_os"][7]), 2);
                                }

                                echo $tat;
                                $total_tat += $tat;
                                ?>
                            </strong>
                        </td>
                        <td class="tac">
                            <strong>
                                <?php
                                if (empty($value["qtde_os"][14])) {
                                    $tat = 0;
                                } else {
                                    $tat = round(($value["tempo_conserto"][14] / $value["qtde_os"][14]), 2);
                                }

                                echo $tat;
                                $total_tat += $tat;
                                ?>
                            </strong>
                        </td>
                        <td class="tac">
                            <strong>
                                <?php
                                if (empty($value["qtde_os"][21])) {
                                    $tat = 0;
                                } else {
                                    $tat = round(($value["tempo_conserto"][21] / $value["qtde_os"][21]), 2);
                                }

                                echo $tat;
                                $total_tat += $tat;
                                ?>
                            </strong>
                        </td>
                        <td class="tac">
                            <strong>
                                <?php
                                if (empty($value["qtde_os"][30])) {
                                    $tat = 0;
                                } else {
                                    $tat = round(($value["tempo_conserto"][30] / $value["qtde_os"][30]), 2);
                                }

                                echo $tat;
                                $total_tat += $tat;
                                ?>
                            </strong>
                        </td>
                        <td class="tac">
                            <strong>
                                <?php
                                if (empty($value["qtde_os"][31])) {
                                    $tat = 0;
                                } else {
                                    $tat = round(($value["tempo_conserto"][31] / $value["qtde_os"][31]), 2);
                                }

                                echo $tat;
                                $total_tat += $tat;
                                ?>
                            </strong>
                        </td>
                        <td class="tac"><strong><?php echo $total_tat ?></strong></td>
                    </tr>

                    <tr>
                        <td class='tac'>Percentual</td>
                        <td class='tac'><strong><?php echo round((($value["qtde_os"][3] / $total_intervalo) * 100), 2) ?>%</strong></td>
                        <td class='tac'><strong><?php echo round((($value["qtde_os"][7] / $total_intervalo) * 100), 2) ?>%</strong></td>
                        <td class='tac'><strong><?php echo round((($value["qtde_os"][14] / $total_intervalo) * 100), 2) ?>%</strong></td>
                        <td class='tac'><strong><?php echo round((($value["qtde_os"][21] / $total_intervalo) * 100), 2) ?>%</strong></td>
                        <td class='tac'><strong><?php echo round((($value["qtde_os"][30] / $total_intervalo) * 100), 2) ?>%</strong></td>
                        <td class='tac'><strong><?php echo round((($value["qtde_os"][31] / $total_intervalo) * 100), 2) ?>%</strong></td>
                        <td class='tac'></td>
                    </tr>
                </tbody>
            </table>

        </div>

        <?php endforeach ?>

<br/>
        <div class="btn_excel"  onClick="gera_csv()">
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>

        <div class="container">

        <?php

    }else{

        echo "
            <div class='alert'>
                <h4>Nenhum resultado encontrado</h4>
            </div>
        ";

    }
}

?>

<br /> <br />

<?php include "rodape.php"; ?>
