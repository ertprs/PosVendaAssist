<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$array_pais_estado = $array_pais_estado();

$msg_erro = "";
if($_GET['buscaCidade']){
    $uf = $_GET['estado'];

    if($uf == "BR-CO"){
        $estado = "'GO','MS','MT','DF'";
    } else if($uf == "BR-NE"){
        $estado = "'SE','AL','RN','MA','PE','PB','CE','PI','BA'";
    } else if($uf == "BR-N"){
        $estado = "'TO','PA','AP','RR','AM','AC','RO'";
    } else {
        $estado = "'$uf'";
    }
    $sql = "SELECT DISTINCT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and contato_estado in($estado) ORDER BY contato_estado,contato_cidade";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        $retorno = "<option value=''>Todos</option>";
        for($i = 0; $i < pg_numrows($res); $i++){
            $cidade = pg_result($res,$i,'contato_cidade');
            $estado = pg_result($res,$i,'contato_estado');

            $nome_cidade = in_array($uf,array('BR-CO','BR-NE','BR-N')) ? "$cidade - $estado" : $cidade;
            $retorno .= "<option value='$cidade'>$nome_cidade</option>";
        }
    } else {
        $retorno .= "<option value=''>".traduz("Cidade não encontrada")."</option>";
    }

    echo $retorno;
    exit;
}

/**
 * Area para colocar os AJAX
 */
if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {

    $estado = strtoupper($_POST["estado"]);
    $pais   = strtoupper($_POST["pais"]);

    if (!empty($pais) && $pais != "BR") {
        
        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                FROM tbl_cidade 
                WHERE UPPER(estado_exterior) = UPPER('{$estado}')
                AND UPPER(pais) = UPPER('{$pais}')
                ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }

    } else {

        if (array_key_exists($estado, $array_estados())) {
			$cond_pais = !empty($pais) ? " and pais ='$pais' " : "";
            $sql = "SELECT DISTINCT * FROM (
                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}') $cond_pais
                        UNION (
                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                        )
                    ) AS cidade
                    ORDER BY cidade ASC";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $array_cidades = array();

                while ($result = pg_fetch_object($res)) {
                    $array_cidades[] = $result->cidade;
                }

                $retorno = array("cidades" => $array_cidades);
            } else {
                $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
            }
        } else {
            $retorno = array("error" => utf8_encode("Estado não encontrado123"));
        }

    }

    exit(json_encode($retorno));
}

if (strlen($_POST["btnacao"]) > 0) {
    $btnacao = trim(strtolower($_POST["btnacao"]));
}

if (strlen($_POST["posto"]) > 0) {
    $posto = $_POST["posto"];
}


$array_estados_login = $array_estados($login_pais);

 // INICIO DA SQL
$codigo_posto_codigo = $_POST['codigo_posto_codigo'];
$posto_codigo = $_POST['posto_codigo'];
$posto_nome   = $_POST['posto_nome'];
$posto_cidade = $_POST['posto_cidade'];
$posto_estado = $_POST['posto_estado'];
$posto_bairro = $_POST['posto_bairro'];
$linha        = $_POST['linha'];
$credenciamento= $_POST['credenciamento'];
$tipo_Posto    = $_POST['tipo_posto'];
$pais          = $_POST['pais'];
$pais_posto    = $_POST['pais_posto'];

if($login_fabrica==11 or $login_fabrica == 172){
    $atendimento_lenoxx = $_POST['atendimento_lenoxx'];
}

if($login_fabrica==117){
    $campo_tp = " , tbl_tipo_posto.descricao AS descricao_tipo_posto ";
}

$camposAdc = array();
$parametrosAdicionais = '';
if($login_fabrica ==50){
    $camposAdc[] = ',tbl_posto_fabrica.parametros_adicionais';
}
if (!empty($pais_posto) OR (strlen ($posto_codigo) > 0) OR (strlen($posto_nome) > 0) OR (strlen($posto_cidade) > 0) OR (strlen($posto_estado) > 0) OR (strlen($linha) > 0) OR (strlen($credenciamento) > 0) OR (strlen($tipo_posto) > 0) OR (strlen($codigo_posto_codigo) > 0 ) || !empty($pais)) {

    $sql_postos = "SELECT DISTINCT
                    tbl_posto.posto                 ,
                    tbl_posto.nome                  ,
                    tbl_posto_fabrica.contato_nome  ,
                    tbl_posto.cnpj                  ,
                    tbl_posto_fabrica.contato_fone_comercial AS fone,
                    tbl_posto_fabrica.contato_cidade AS cidade,
                    tbl_posto_fabrica.contato_estado AS estado,
                    tbl_posto_fabrica.contato_bairro AS bairro,
                    tbl_posto_fabrica.codigo_posto  ,
                    tbl_posto_fabrica.digita_os     ,
                    tbl_posto_fabrica.credenciamento ,
                    tbl_posto_fabrica.latitude,
                    tbl_posto_fabrica.longitude,
                    tbl_posto_fabrica.atendimento,
                    tbl_posto_fabrica.contato_email
                    ". implode(',', $camposAdc);

    if (strlen($linha) > 0) {
        $sql_postos .= ", tbl_linha.nome ";
    }

    $sql_postos .= "$campo_tp
            FROM   tbl_posto
            JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
            JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica ";

    if (strlen($linha) > 0) {
        $sql_postos .= "JOIN   tbl_posto_linha      ON tbl_posto_linha.posto     = tbl_posto.posto
                JOIN    tbl_linha            ON tbl_linha.linha           = tbl_posto_linha.linha ";
    }

    if (strlen($tipo_posto) > 0 OR $login_fabrica == 117) {
        $sql_postos .= "JOIN   tbl_tipo_posto      ON tbl_tipo_posto.tipo_posto  = tbl_posto_fabrica.tipo_posto and tbl_tipo_posto.fabrica=tbl_posto_fabrica.fabrica ";
    }


    $sql_postos .= "WHERE   tbl_posto_fabrica.fabrica = $login_fabrica";

    $xposto_codigo = str_replace (" " , "" , $posto_codigo);
    $xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
    $xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
    $xposto_codigo = str_replace ("." , "" , $xposto_codigo);

    if (!empty($pais_posto)) {
        $sql_postos .= " AND tbl_posto.pais = '{$pais_posto}'";
    }

    //HD 110541
    if (strlen ($atendimento_lenoxx) > 0 and $atendimento_lenoxx <> " ") {
        $sql_postos .= " AND tbl_posto_fabrica.atendimento ='$atendimento_lenoxx'";
    }

    if (strlen ($posto_codigo) > 0 ) {
       $sql_postos .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
    }

    if (strlen ($codigo_posto_codigo) > 0 ) {
        $sql_postos .= " AND tbl_posto_fabrica.codigo_posto ='$codigo_posto_codigo'";
    }

    if (strlen ($posto_nome) > 0 and (strlen($codigo_posto_codigo) == 0 and empty($posto_codigo))) {
        $sql_postos .= " AND (tbl_posto.nome  ILIKE '%$posto_nome%' OR tbl_posto.nome_fantasia ILIKE '%$posto_nome%')";
    }

    if (strlen ($posto_cidade) > 0 ) {
        $sql_postos .= " AND TRIM(tbl_posto_fabrica.contato_cidade) = '$posto_cidade'";
    }

    if (strlen ($posto_estado) > 0 ) {
        $sql_postos .= " AND tbl_posto_fabrica.contato_estado = '$posto_estado'";
    }

    if (strlen ($posto_bairro) > 0 ) {
        $sql_postos .= " AND tbl_posto_fabrica.contato_bairro ILIKE '%$posto_bairro%'";
    }

    if (!empty($pais)) {
        $sql_postos .= " AND tbl_posto.pais = '$pais'";
    }

    if (strlen ($linha) > 0 ) {
        $sql_postos .= " AND tbl_linha.linha = '$linha' ";
    }

    if (strlen ($credenciamento) > 0 ) {
        $sql_postos .= " AND tbl_posto_fabrica.credenciamento = '$credenciamento' ";
    }

    if (strlen ($tipo_posto) > 0 ) {
        $sql_postos .= " AND tbl_tipo_posto.tipo_posto = '$tipo_posto' ";
    }

    if ($login_fabrica == 11 or $login_fabrica == 172) {
        $sql_postos .= " AND tbl_posto_fabrica.credenciamento <> 'REPROVADO' ";
    }

    $sql_postos .= " ORDER BY cidade, tbl_posto.nome";
    
    $res_postos = pg_query($con,$sql_postos);


} else if (!empty($btnacao)) {
    $msg_erro["msg"][]    = traduz("Preencha algum parâmetro para pesquisa");
    $msg_erro["campos"][] = traduz("todos");
}

$layout_menu = "callcenter";
$title = traduz("CONSULTA DE POSTOS");
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

<script type='text/javascript'>
    $(function(){
        $("#posto_codigo").mask("99.999.999/9999-99");
        $.autocompleteLoad(Array("posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        var jsonPaisEstado = JSON.parse('<?= json_encode(array_map_recursive('utf8_encode', $array_pais_estado)) ?>');

        $("#pais_posto").change(function(){

            let sigla = $(this).val();

            $("#posto_estado > option:not(:first)").remove("");
            $("#posto_cidade > option:not(:first)").remove("");
            
            if (jsonPaisEstado[sigla] != undefined) {

                $.each(jsonPaisEstado[sigla], function(key, objEstado) {

                    $.each(objEstado, function(sigla, nome) {

                        var option = $("<option></option>", { value: sigla, text: nome});

                        $("#posto_estado").append(option);
                    });

                });

            }

        });

        $("#posto_estado").change(function() {

            if ($("#pais_posto").length > 0) {
                var paisSelecionado = $("#pais_posto option:selected").val();
            } else {
                var paisSelecionado = "BR";
            }

            busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "consumidor", undefined, paisSelecionado);

        });

    });

    /**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, consumidor_revenda, cidade, pais = "BR") {
    $("#posto_cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "posto_consulta.php",
            type: "POST",
            data: { ajax_busca_cidade: true, estado: estado , pais: pais},
            beforeSend: function() {
                if ($("#posto_cidade").next("img").length == 0) {
                    $("#posto_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $.each(data.cidades, function(key, value) {
                        var option = $("<option></option>", { value: value, text: value});

                        $("#posto_cidade").append(option);
                    });
                }


                $("#posto_cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){

        $("#posto_cidade option[value='"+cidade+"']").attr('selected','selected');

    }

}

function retorna_posto(posto,codigo_posto,nome,cnpj,pais,cidade,estado,nome_fantasia){
    gravaDados('codigo_posto_codigo',codigo_posto);
    gravaDados('posto_nome',nome);
    gravaDados('posto_codigo',cnpj);
}

function gravaDados(name, valor){
        try {
                $("input[name="+name+"]").val(valor);
        } catch(err){
                return false;
        }
}


function montaComboCidade(estado){

    $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
            cache: false,
            success: function(data) {
                $('#cidade').html(data);
            }

        });

}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
    $("#posto_codigo").val(retorno.cnpj);
}

<?php if ($login_fabrica == 52) { ?>
    $(document).ready(function() {
        $('select[name=pais]').on('change',function () {
            if ($('select[name=pais]').val() != 'BR') {
                $('select[name=estado]').prop('disabled', 'disabled');
                $('select[name=estado]').val('');
                $('select[name=cidade]').prop('disabled', 'disabled');
                $('select[name=cidade]').val('');
            } else {
                $('select[name=estado]').removeAttr('disabled');
                $('select[name=cidade]').removeAttr('disabled');
            }
        });
    });
<?php } ?>

</script>
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?
$codigo_posto_codigo = $_POST['codigo_posto_codigo'];//14011 19/2/2008
$posto_codigo   = $_POST['posto_codigo'];
$posto_nome     = $_POST['posto_nome'];
$posto_cidade   = $_POST['posto_cidade'];
$posto_estado   = $_POST['posto_estado'];
$posto_bairro   = $_POST['posto_bairro'];
$linha          = $_POST['linha'];
$credenciamento = $_POST['credenciamento'];
$tipo_posto     = $_POST['tipo_posto'];

/*IGOR HD 8177 - Criar busca no google maps*/
?>
<?php
if (!empty($msg_erro["msg"])) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name='frm_mapa' class="form-search form-inline tc_formulario" method='post' action='mapa_rede_new.php' target='_blank'>
    <div class="titulo_tabela"><?=traduz('Consulte o Mapa da Rede')?></div>
    <br />
    <div class='row-fluid'>
        <div class="span1"></div>
        <div class="span4">
            <div class='control-group'>
                <label class='control-label' for='País'><?=traduz('País')?></label><br />
                <div class='controls controls-row input-append'>
                    <select name='pais' class='controls'>
                        <?php   
                        if ($login_fabrica == 52) { ?>
                            <option value=""><?=traduz('Selecione')?></option>
                        <?php
                                $aux_sql = "SELECT pais, nome FROM tbl_pais ORDER BY nome";
                                $aux_res = pg_query($con, $aux_sql);
                                $aux_row = pg_num_rows($aux_res);
                                
                                for ($wz=0; $wz < $aux_row; $wz++) { 
                                    $aux_pais = pg_fetch_result($aux_res, $wz, 'pais');
                                    $aux_nome = pg_fetch_result($aux_res, $wz, 'nome');

                                    if (strlen($consumidor_pais) > 0) {
                                        if ($consumidor_pais == $aux_pais) {
                                            $selected = "selected";
                                        }
                                    }

                                    ?> <option <?=$selected;?> value="<?=$aux_pais;?>"><?=$aux_nome;?></option> <?
                                    unset($selected);
                                }
                        } else { ?>
                            <option value='BR' selected><?=traduz('Brasil')?></option>
						<? } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class='control-group'>
                <label class='control-label' for='Estado'><?=traduz('Estado')?></label>
                 <div class='controls controls-row input-append'>
                    <select name='estado' class='span12 controls inptc6' onchange="montaComboCidade(this.value)">
                        <option value='00' selected><?=traduz('Todos')?></option>
                        <option value='BR-CO'      >Centro-Oeste</option>
                        <option value='BR-NE'      >Nordeste</option>
                        <option value='BR-N'       >Norte</option>
                        <option value='AC'>Acre</option>
                        <option value='AL'>Alagoas</option>
                        <option value='AM'>Amazonas</option>
                        <option value='AP'>Amapá</option>
                        <option value='BA'>Bahia</option>
                        <option value='CE'>Ceará</option>
                        <option value='DF'>Distrito Federal</option>
                        <option value='ES'>Espírito Santo</option>
                        <option value='GO'>Goiás</option>
                        <option value='MA'>Maranhão</option>
                        <option value='MG'>Minas Gerais</option>
                        <option value='MS'>Mato Grosso do Sul</option>
                        <option value='MT'>Mato Grosso</option>
                        <option value='PA'>Pará</option>
                        <option value='PB'>Paraíba</option>
                        <option value='PE'>Pernambuco</option>
                        <option value='PI'>Piauí</option>
                        <option value='PR'>Paraná</option>
                        <option value='RJ'>Rio de Janeiro</option>
                        <option value='RN'>Rio Grande do Norte</option>
                        <option value='RO'>Rondônia</option>
                        <option value='RR'>Roraima</option>
                        <option value='RS'>Rio Grande do Sul</option>
                        <option value='SC'>Santa Catarina</option>
                        <option value='SE'>Sergipe</option>
                        <option value='SP'>São Paulo</option>
                        <option value='TO'>Tocantins</option>

                    </select>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group'>
                 <label class='control-label' for='Cidade'><?=traduz('Cidade')?></label><br />
                 <div class='controls controls-row input-append'>
                    <select name='cidade' id='cidade' class='frm'>
                    <?
                        $sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_cidade) AS contato_cidade
                                FROM tbl_posto_fabrica
                                WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                                ORDER BY contato_cidade";
                        $res = pg_query($con, $sql);
                        if(pg_num_rows($res)>0){
                            ?>
                            <option value='' selected><?=traduz('Todos')?></option> <?php
                            for($x=0; $x<pg_num_rows($res); $x++){
                                $nome_cidade = pg_fetch_result($res, $x, contato_cidade);
                                ?> <option value='<?= $nome_cidade ?>'>
                                        <?= $nome_cidade; ?>
                                   </option>
                    <?
                            }
                        }
                    ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid">
        <div class="span5"></div>
            <div class="span2">
                <input class="btn" type='submit' name='btn_mapa' value='<?=traduz("Pesquisar")?>'>
            </div>
        <div class="span5"></div>
    </div>
</form>
<br>
<form method='POST' class='form-search form-inline tc_formulario' name='frm_posto' action="<?= $PHP_SELF ?>">
    <input type='hidden' name='btnacao' value=''>
    <div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div>
    <br />
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span3">
            <div class='control-group <?=(in_array('consumidor[pais]', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label">
                    País
                    <select id="pais_posto" name="pais_posto" class="span12" style="width: 200px;">
                        <?php
                        foreach ($array_paises() as $sigla => $nome_pais) {
                            
                            if (!empty($pais_posto)) {
                                $selected = ($sigla == $pais_posto) ? "selected" : "";
                            } else {
                                $selected = ($sigla == "BR") ? "selected" : "";
                            }

                            echo "<option value='{$sigla}' {$selected}>{$nome_pais}</option>";
                        } ?>
                    </select>
                </label>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
            <div class="span4">
                <div class='control-group <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='Código Posto'><?=traduz('Código Posto')?></label><br />
                    <div class='controls-row input-append'>
                            <input type='text' name='codigo_posto_codigo' value='<?= $codigo_posto_codigo ?>' class='span6' id="codigo_posto">
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                            <?
                                $sql = "SELECT  *
                                        FROM    tbl_tipo_posto
                                        WHERE   tbl_tipo_posto.fabrica = $login_fabrica AND ativo
                                        ORDER BY tbl_tipo_posto.descricao;";
                                $res = pg_query($con,$sql);

                                if (pg_num_rows($res) > 0) {
                               ?>
                           <label class='control-label' for='Tipo Posto'> <?=traduz('Tipo Posto')?></label>
                                <div class='controls controls-row'>
                                        <select class='span12' style='width: 150px;' name='tipo_posto' class='controls'>
                                            <option value=''>TODOS</option>
                                <?
                                        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                            $aux_tipo_posto = trim(pg_fetch_result($res,$x,tipo_posto));
                                            $aux_descricao  = trim(pg_fetch_result($res,$x,descricao));
                                ?>
                                            <option value='<?= $aux_tipo_posto ?>';
                                <?
                                            if ($tipo_posto == $aux_tipo_posto) echo " SELECTED "; echo ">$aux_descricao</option>\n";
                                        }
                                ?>
                                        </select>
                                    </option>
                            <?
                                }
                            ?>
                                </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
            <div class="span4">
                <div class='control-group <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='CNPJ'><?=traduz('CNPJ')?></label><br />
                    <div class='controls controls-row input-append'>
                        <input type='text' class="span10" name='posto_codigo' id='posto_codigo' maxlength='18' value='<?= $posto_codigo ?>' class='frm'>
                         <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" />
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='Nome do Posto'><?=traduz('Nome do Posto')?></label>
                    <div class='controls controls-row input-append'>
                        <input type='text' name='posto_nome' value='<?= $posto_nome ?>' class='frm' id="descricao_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
            <div class="span4">
                <div class='control-group <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='Estado'><?=traduz('Estado')?></label>
                    <div class='controls controls-row'>
                        <select name='posto_estado' class='frm' id='posto_estado'>";
                                    <option value='' selected><?=traduz('Selecione')?></option>";
                                <?
									if(count($_POST) == 0 and empty($pais_posto)) {
										$pais_posto = $login_pais;
									}

                                    if (!empty($pais_posto)) {
                                        $array_estados = $array_estados($pais_posto);
                                    }

                                    foreach ($array_estados as $k => $v) {
                                        echo '<option value="'.$k.'"'.($posto_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                                    }
                                ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='Cidade'><?=traduz('Cidade')?></label>
                    <div class='controls controls-row'>
                        <select id="posto_cidade" name="posto_cidade" class="frm" style="width:200px">
                            <option value="" ><?=traduz('Selecione')?></option>
                             <?php

                                if (!empty($posto_estado)) {

                                    if (!empty($pais_posto) && $pais_posto != "BR") {
        
                                        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                                                FROM tbl_cidade 
                                                WHERE UPPER(estado_exterior) = UPPER('".$posto_estado."')
                                                AND UPPER(pais) = UPPER('".$pais_posto."')
                                                ";
                                        $res = pg_query($con, $sql);
                                       
                                    } else {

                                        $sql = "SELECT DISTINCT * FROM (
                                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$posto_estado."')
                                                    UNION (
                                                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$posto_estado."')
                                                    )
                                                ) AS cidade
                                                ORDER BY cidade ASC";

                                    }

                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cidade) == trim($posto_cidade)) ? "SELECTED" : "";

                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>
            </div>
            <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
            <div class='span4'>
                <label class='control-label' for='Bairro'><?=traduz('Bairro')?></label>
                <div class='controls controls-row'>
                    <input type='text' name='posto_bairro' size='20' value='<?= $posto_bairro ?>' class='frm'>
                </div>
            </div>

            <div class='span4'>
        <?
        if($login_fabrica==11 or $login_fabrica == 172){
        ?>
                <label class='control-label' for='Atendimento'><?=traduz('Atendimento')?></label>
                    <div class='controls controls-row <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                        <select class='frm' name='atendimento_lenoxx' >
                            <option value=' '> </option>
                            <option value='b'><?=traduz('BALCÃO')?></option>
                            <option value='r'><?=traduz('REVENDA')?></option>
                            <option value='t'><?=traduz('BALCÃO/REVENDA')?></option>
                        </select>
                    </div>
        <?
        }
        ?>
            </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
            <div class='span4'>
                <label class='control-label' for='Linha'><?=($login_fabrica == 117) ? traduz('Macro-Família') : traduz('Linha'); ?></label>
                <div class='controls controls-row <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                    <?

                        if ($login_fabrica == 117) {
                            $joinElgin = 'JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                        JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha';
                        }

                        $sql = "SELECT DISTINCT tbl_linha.linha, tbl_linha.nome
                                FROM    tbl_linha
                                $joinElgin
                                WHERE   tbl_linha.fabrica = $login_fabrica
                                AND     tbl_linha.ativo
                                ORDER BY tbl_linha.nome;";
                        $res = pg_query($con,$sql);

                        if (pg_numrows($res) > 0) {
                            echo "<select class='frm' style='width: 150px;' name='linha'>\n";
                            echo "<option value=''>".traduz('TODAS')."</option>\n";

                            for ($x = 0 ; $x < pg_numrows($res) ; $x++){
                                $aux_linha = trim(pg_result($res,$x,linha));
                                $aux_nome  = trim(pg_result($res,$x,nome));

                                echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
                            }
                            echo "</select>\n";
                        }
                    ?>
                </div>
            </div>
            <div class='span4'>
                <label class='control-label' for='Status'><?=traduz('Status')?></label>
                <div class='controls controls-row <?=(in_array("todos", $msg_erro["campos"])) ? "error" : ""?>'>
                    <select class='frm' style='width: 150px;' name='credenciamento' >
                        <option value=''><?=traduz('TODOS')?></option>
                        <option value='CREDENCIADO'
                            <?
                                 if ($credenciamento== "CREDENCIADO") echo " SELECTED "; echo ">".traduz('CREDENCIADO')."</option>\n";
                            ?>
                                <option value='DESCREDENCIADO'
                            <?
                                if ($credenciamento== "DESCREDENCIADO") echo " SELECTED "; echo ">".traduz('DESCREDENCIADO')."</option>\n";
                            ?>
                                <option value='EM CREDENCIAMENTO'
                            <?
                                 if ($credenciamento== "EM CREDENCIAMENTO") echo " SELECTED "; echo ">".traduz('EM CREDENCIAMENTO')."</option>\n";
                            ?>
                                <option value='EM DESCREDENCIAMENTO'
                            <?
                                 if ($credenciamento== "EM DESCREDENCIAMENTO") echo " SELECTED "; echo ">".traduz('EM DESCREDENCIAMENTO')."</option>\n";

                                if ($login_fabrica == 11 or $login_fabrica == 172) {
                                    echo "<option value='REPROVADO'"; if ($credenciamento== "REPROVADO") echo " SELECTED "; echo ">".traduz('REPROVADO')."</option>\n";
                                }
                            ?>

                    </select>
                </div>
            </div>
            <div class='span2'></div>
       </div>
     <?

    if($login_fabrica == 50){ ?>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class="span4">
                    <b><?=traduz('Posto Isento:')?></b>
                        <div class='row-fluid'>
                                <div class="span3">
                                    <label class='radio'>
                                        Sim<input type="radio" name="posto_isento" value="t" <?= ($posto_isento == 't') ? 'checked' : '' ?> />
                                    </label>
                                </div>
                                <div class="span6">
                                    <label class='radio'>
                                        Não<input type="radio" name="posto_isento" value="f" <?= ($posto_isento == 'f') ? 'checked' : '' ?> />
                                    </label>
                                </div>
                        </div>
                </div>
                <div class="span4">
                    <b><?=traduz('Devolver Peças')?></b>
                        <div class='row-fluid'>
                            <div class="span3">
                                <label class='radio'>
                                    <?=traduz('Sim')?> <input type="radio" name="devolver_pecas" value="t" <?= ($devolver_pecas == 't') ? 'checked' : '' ?> />
                                </label>
                            </div>
                            <div class="span6">
                                <label class='radio'>
                                    <?=traduz('Não')?> <input type="radio" name="devolver_pecas" value="f" <?= ($devolver_pecas == 'f') ? 'checked' : '' ?> />
                                </label>
                            </div>
                        </div>
                </div>
            <div class='span2'></div>
        </div>

        <?php } ?>
        <div class='row-fluid'>
            <div class='span3'></div>
                <div class='span6 tac'>
                    <?
                    echo "<input class='btn' type='button' style='cursor:pointer;' value='Filtrar' onclick=\"javascript: document.frm_posto.btnacao.value='filtrar' ; document.frm_posto.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";
                    ?>
                    <button type="button" class="btn btn-primary" onclick="window.open('<?=$PHP_SELF?>');"><?=traduz('Limpar dados')?></button>
                    <br /><br />
                </div>
            <div class='span3'></div>
        </div>
</form>
<br>
<?
    if (pg_num_rows($res_postos) > 0 && !empty($btnacao)) {
        ?>
        <table class='table' style="width: 100%;">
            <tr>
                <td bgcolor='#D9E2EF'>
                    <table width='100%' cellpadding='1' cellspacing='1'>
                        <tr>
                            <td colspan='8' class='titulo_tabela'><?=traduz('LEGENDA STATUS')?></td>
                        </tr>
                        <tr>
                            <td width='5%' bgcolor = '#FFFFFF'>C</td>
                            <td align='left' width='20%' ><?=traduz('Credenciado')?></td>
                            <td bgcolor='#BC053D' width='5%'><font color='#FFFFFF'>D</font></td>
                            <td align='left' width='20%' ><?=traduz('Descredenciado')?></td>
                            <td bgcolor='#F27900' width='5%'><font color='#FFFFFF'>EC</font></td>
                            <td align='left' width='20%' ><?=traduz('Em Credenciamento')?></td>
                            <td bgcolor='gray' width='5%'><font color='#FFFFFF'>ED</font></td>
                            <td align='left' width='20%' ><?=traduz('Em Descredenciamento')?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <br><br>

</div>
    <?php
        if (in_array($login_fabrica, array(30,117,165,167,203))) {
            ob_start();
            $arquivo = "xls/informacoes-posto-$login_fabrica-".date('Y-m-d').".xls";
        ?>
            <div id='gerar_excel' class="btn_excel" onclick="window.open('<?= $arquivo ?>')">
                <span><img src='imagens/excel.png' /></span>
                <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
            </div>
        <?
        }
    ?>
        <table id='tabela_postos' class='table table-striped table-bordered table-large' style="margin: 0 auto;">
            <thead class='titulo_coluna'>
                <th align='center' nowrap><?=traduz('CNPJ')?></th>
                <th align='center'><?=traduz('Nome do Posto')?></th>
                <?
                if ($login_fabrica != 117) {
                ?>
                    <th align='center'><?=traduz('Nome')?></th>
                <?
                }
                if ($login_fabrica == 15) {
                ?>
                    <th align='center' nowrap>e-mail</th>
                <?
                }
                if (in_array($login_fabrica, array(45,80,117))) {
                    ?>
                    <th align='center' nowrap><?=traduz('Fone')?></th>
                <?
                }

                if ($login_fabrica == 80) { ?>
                    <td alin='center'><?=traduz('Linha')?></td>
                <?
                }
                ?>
                <th align='center' ><?=traduz('Bairro')?></th>
                <th align='center' ><?=traduz('Cidade')?></th>
                <th align='center' ><?=traduz('UF')?></th>
                <?
                if($login_fabrica==117) { ?>
                    <th align='center' nowrap><?=traduz('Tipo Posto')?></th>
                    <th align='center' ><?=traduz('Macro-Família')?></th>
                <?php
                }
                if($login_fabrica==45) { ?>
                    <th align='center' ><?=traduz('Linhas')?></th>
                <?php
                }
                ?>
                <th align='center' nowrap><?=traduz('Status')?></th>
                <?php
                if($login_fabrica== 151) { ?>
                    <th align='center' ><?=traduz('Data Geração')?></th>
                <?php
                }
                ?>

                <?php
                if($login_fabrica==50) {
                    ?> <th align='center' ><?=traduz('Posto Isento')?></th>
                       <th align='center' ><?=traduz('Devolver Peças')?></th>
                <?php
                }
                ?>
                <th align='center'>Ações</th>
                <th ><?=traduz('Geolocalização')?></th>
                <? if ($login_fabrica == 35) { ?>
                    <th align='center'><?=traduz('Obrigatoriedade de NF na OS')?></th>
                <? } ?>
                <? if($login_fabrica != 30) { ?>
                    </thead>
                <? }

            if (in_array($login_fabrica, array(30,117,165,167,203))) {
                $excel = ob_get_contents();
            }

            if($login_fabrica == 30){
                    $excel .= "<th align='center' >Latitude</th>";
                    $excel .= "<th align='center' >Longitude</th>";
                    $excel .= "</thead>";
                    ?> </thead> <?
                }
            for ($i = 0 ; $i < pg_numrows ($res_postos) ; $i++) {

                $posto          = trim(pg_result($res_postos,$i,posto));
                $cnpj           = trim(pg_result($res_postos,$i,cnpj));
                $fone           = trim(pg_result($res_postos,$i,fone));
                $nome           = trim(pg_result($res_postos,$i,nome));
                $contato_nome   = trim(pg_result($res_postos,$i,contato_nome));
                $cidade         = trim(pg_result($res_postos,$i,cidade));
                $estado         = trim(pg_result($res_postos,$i,estado));
                $credenciamento = trim(pg_result($res_postos,$i,credenciamento));
                $bairro         = trim(pg_result($res_postos,$i,bairro));
                $digita_os      = trim(pg_result($res_postos,$i,digita_os));
                $email         = trim(pg_result($res_postos,$i,contato_email));

                if($login_fabrica == 30){
                    $latitude = trim(pg_result($res_postos,$i,latitude));
                    $longitude = trim(pg_result($res_postos,$i,longitude));
                    $latitude = explode(".", $latitude);
                    $longitude = explode(".", $longitude);
                    $latitude = $latitude[0] . "," . "$latitude[1]$latitude[2]";
                    $longitude = $longitude[0] . "," . "$longitude[1]$longitude[2]";
                }

                if($login_fabrica == 50){
                    $mostrarDados = 0;
                    $posto_parametros_adicionais = array();
                    $json_parametros_adicionais = pg_fetch_result($res_postos,$i,'parametros_adicionais');
                    if(!empty($json_parametros_adicionais) ){
                        $posto_parametros_adicionais = json_decode($json_parametros_adicionais,true);
                        if(isset($_POST['posto_isento']) && isset($_POST['devolver_pecas'])){
                            if(array_key_exists('posto_isento', $posto_parametros_adicionais) && $posto_parametros_adicionais['posto_isento'] == $_POST['posto_isento']){
                                $mostrarDados ++;
                            }else{
                                $mostrarDados--;
                            }

                            if(array_key_exists('devolver_pecas', $posto_parametros_adicionais) && $posto_parametros_adicionais['devolver_pecas'] == $_POST['devolver_pecas']){
                                $mostrarDados ++;
                            }else{
                                $mostrarDados--;
                            }
                            if($mostrarDados != 2){
                                $mostrarDados = 0;
                                continue;
                            }
                        }
                    }
                }

                if($login_fabrica == 117) {
                    $descricao_tipo_posto   = trim(pg_result($res_postos,$i,descricao_tipo_posto));
                    $sql_linha = "SELECT DISTINCT
                                    tl.linha as macro_linha,
                                    tl.nome as descricao,
                                    tl.ativo as ativo
                                FROM tbl_macro_linha_fabrica AS tmlf
                                    JOIN tbl_macro_linha AS tml ON tmlf.macro_linha = tml.macro_linha
                                    JOIN tbl_linha AS tl ON tmlf.linha = tl.linha
                                    JOIN tbl_posto_linha tpl ON tpl.linha = tl.linha
                                WHERE tmlf.fabrica = {$login_fabrica}
                                    AND tml.ativo IS TRUE
                                    AND tl.ativo IS TRUE
                                    AND   tpl.posto = $posto
                                ORDER BY descricao;";

                    $resl = pg_query($con, $sql_linha);
                    if(pg_numrows($resl)>0){
                        $linhas = "";
                        for($z=0; $z<pg_numrows($resl);$z++){
                            $nome_linha = pg_result($resl, $z, descricao);
                            $linhas .= $nome_linha.",";
                            $xlinhas = substr($linhas,0, -1);
                        }
                    }
                }

                if($login_fabrica == 151){
                    $sql_X = "SELECT TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data_geracao
                            FROM tbl_credenciamento
                           WHERE tbl_credenciamento.fabrica = $login_fabrica
                             AND tbl_credenciamento.posto   = $posto
                        ORDER BY tbl_credenciamento.data DESC
                           LIMIT 1";
                    $res_X = pg_query ($con,$sql_X);

                    if (pg_num_rows ($res_X) > 0) {
                        $data_geracao   = trim(pg_fetch_result($res_X,0,'data_geracao'));
                    }
                }

                if($login_fabrica==45){// HD 19498 13/5/2008
                    $sql_linha = "SELECT DISTINCT nome AS nome_linha
                                        from tbl_linha
                                JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
                                WHERE tbl_linha.fabrica = $login_fabrica
                                AND   tbl_posto_linha.posto = $posto";
                    $resl = pg_query($con, $sql_linha);

                    if(pg_numrows($resl)>0){
                        $linhas = "";
                        for($z=0; $z<pg_numrows($resl);$z++){
                            $nome_linha = pg_result($resl, $z, nome_linha);
                            $linhas .= $nome_linha.",";
                            $xlinhas = substr($linhas,0, -1);
                        }
                    }
                }


                //HD 12220 18/1/2008
                if ($credenciamento == 'CREDENCIADO'){
                    $xcredenciamento = 'C';
                    $cor_fundo = '#FFFFFF';
                    $cor_texto = '#000000';
                    #HD 110541
                    if($digita_os<>"t" AND ($login_fabrica==11 or $login_fabrica == 172)){
                        $xcredenciamento .= '/B';
                    }
                }else if ($credenciamento == 'DESCREDENCIADO') {
                    $xcredenciamento = 'D';
                    $cor_fundo = '#BC053D';
                    $cor_texto = '#FFFFFF';
                }else if ($credenciamento == 'EM CREDENCIAMENTO'){
                    $xcredenciamento = 'EC';
                    $cor_fundo = '#F27900';
                    $cor_texto = '#FFFFFF';
                }else if ($credenciamento == 'EM DESCREDENCIAMENTO'){
                    $xcredenciamento = 'ED';
                    $cor_fundo = 'gray';
                    $cor_texto = '#FFFFFF';
                }

                if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";
                ob_start();
                ?>
                <tr bgcolor='<?= $cor ?>'>
                    <td align='left'><?= $cnpj ?></td>
    		        <td align='left'><a href="posto_login.php?posto=<?=$posto?>" target="_blank" ><?= $nome ?></a></td>
                    <?
                    if($login_fabrica != 117){
                    ?>
                        <td align='left'><?= $contato_nome ?></td>
                    <?
                    }
                    if ($login_fabrica == 15) {
                    ?>
                        <td align='left' nowrap><?= $email ?></td>
                    <?
                    }
                    if ($login_fabrica==45 or $login_fabrica==80 or $login_fabrica==117)echo "<td class=table_line align='left' nowrap>$fone</td>";

                    if ($login_fabrica == 80){

                        $sqlqtde = "SELECT count(*) from tbl_linha where fabrica = $login_fabrica and ativo is true";
                        $resqtde = pg_query($sqlqtde);

                        $num_linha = pg_result($resqtde,0,0);

                        $sql_linha = "SELECT DISTINCT nome AS nome_linha
                                    FROM tbl_linha
                                JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
                                WHERE    tbl_linha.fabrica = $login_fabrica
                                    AND  tbl_posto_linha.posto = $posto
                                    AND  tbl_linha.ativo is true
                                ORDER BY nome_linha;";
                        $res_linha = pg_query($con,$sql_linha);

                        if (pg_numrows($res_linha)>0){
                            $linha_cont = pg_numrows($res_linha);
                            $linha_nome = "";
                            $linha_escreve = "";
                            for ($x=0; $x<pg_numrows($res_linha);$x++){
                                $linha_nome[] = pg_result($res_linha, $x, nome_linha);
                            }
                            if ($num_linha == $linha_cont) {
                                $linha_escreve = 'Todas as Linhas';
                            } else {
                                $linha_escreve = implode($linha_nome,'/');
                            }
                        }
                        ?>
                        <td class=table_line align='left'><?= $linha_escreve ?></td>
                        <?
                    }
                    ?>
                   <td align='left' ><?= $bairro ?></td>
                   <td align='left' ><?= $cidade ?></a></td>
                   <td align='left' class="tac"><?= $estado ?></td>
                    <?

                    if($login_fabrica==117){ ?>
                        <td class=table_line align='left' nowrap><?= $descricao_tipo_posto ?></td>
                    <?php
                    }

                    if($login_fabrica==45 OR $login_fabrica==117){ ?>
                        <td class=table_line align='left' ><?= $xlinhas ?></td>
                    <?php
                    }
                    ?>
                    <td class="tac" style='background-color:<?= $cor_fundo ?>; color:<?= $cor_texto ?>;'><?= $xcredenciamento ?></td>
                    <?
                    if($login_fabrica == 151){
                        echo "<td class=table_line align='left'> $data_geracao</td>";
                    }
                    $posto_isento = ('t' == $posto_parametros_adicionais['posto_isento'])  ? 'SIM' : 'NÃO';
                    $devolver_pecas = ('t' == $posto_parametros_adicionais['devolver_pecas'])? 'SIM' : 'NÃO';

                    if($login_fabrica==50) { ?>
                        <td class=table_line align='left'><?= $posto_isento ?></td>
                        <td class=table_line align='left'><?= $devolver_pecas ?></td>
                    <?
                    }

                    if($login_fabrica != 30){
                        if (in_array($login_fabrica, array(117,165,167,203))) {
                            $excel .= ob_get_contents() . "</tr>";
                        }
                    } else {
                        $excel .= ob_get_contents();
                        $excel .= "<td align='left'>$latitude</td>";
                        $excel .= "<td align='left'>$longitude</td>";
                        $excel .= "</tr>";
                    }
                    ?>
                    <TD>
                        <a class="btn btn-primary btn-small" role="button" href = 'posto_consulta_detalhe.php?posto=<?=$posto ?>' target="_blank"><?=traduz('Consultar')?></a>
                    </TD>
                    <TD>
                        <a rel="shadowbox" href="atualiza_localizacao_posto.php?posto=<?= $posto ?>" name="btnAtualizaMapa"><?=traduz('Atualizar Geolocalização')?></a>
                    </TD>
                    <? if ($login_fabrica == 35) { 
                        $aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto LIMIT 1";
                        $aux_res = pg_query($con, $aux_sql);
                        $aux_par_ad = (array) json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'));

                        if (!empty($aux_par_ad["anexar_nf_os"]) && $aux_par_ad["anexar_nf_os"] == "nao") {
                            $obrigado_anexar_nf = traduz("Isento de Obrigatoriedade");
                        }
                        ?> <td> <?=$obrigado_anexar_nf;?> </td> <?
                    } ?>
                </tr>
            <?
            }
            if($login_fabrica == 30){
                $excel = str_replace("<th align='center'>".traduz('Ações')."</th>", "", $excel);
                $excel = str_replace("<th align='center'>".traduz('Geolocalização')."</th>", "", $excel);
            }
            ?>
        </table>
        <?
        if(in_array($login_fabrica, array(30,117,165,167,203))) {
            $excel .= "</table>";
            $excel = str_replace("<th align='center'>".traduz('Ações')."</th>", "", $excel);
            $excel = str_replace("<th align='center'>".traduz('Geolocalização')."</th>", "", $excel);
            $fp = fopen($arquivo,"w");
            fwrite($fp, $excel);
            fclose($fp);
        }

    } else if (!empty($btnacao)) {
        ?>
        <div class="alert alert-warning">
            <h4><?=traduz('Nenhum resultado encontrado')?></h4>
        </div>
    <?php
    }
?>
<p>
<p>
<script language='javascript' src='address_components.js'></script>
<script>
    $.dataTableLoad({ table: "#tabela_postos" });
</script>

<?php if (in_array($login_fabrica, [180,181,182])) { ?>
        <script type="text/javascript">
            $(window).load(function() {
                $("form[name=frm_mapa]").hide();
            });
        </script>
<?php } ?>

<? include "rodape.php"; ?>
