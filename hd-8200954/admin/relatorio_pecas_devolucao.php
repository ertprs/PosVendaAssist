<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Relatório de Peças para Devolução Obrigatória";
$layout_menu = "auditoria";

/**
 * listarPecas($peca_referencia,$peca_descricao,$pendente,$codigo_posto,$estado,$cidade)
 * - Função para listar as peças já marcadas para retorno obrigatório à Fábrica
 *
 * @param Int $peca_referencia Referência da peça
 * @param String $peca_descricao Descrição da peça
 * @param Bool $pendente Busca por pedido de peças já concluídas ou não
 * @param String|Null $codigo_posto Código do posto
 * @param String|Null $estado Unidade da Federação que ocorrerá a verificação das peças cadastradas na OS
 * @param Int|Null $cidade Município que ocorrerá a verificação das peças cadastradas na OS
 *
 * @return Lista com as peças já registradas em OS
 */
function listarPecas($peca_referencia,$peca_descricao,$pendente,$codigo_posto,$estado,$cidade){
    global $con,$login_fabrica,$login_admin;

    if(empty($peca_referencia) || empty($peca_descricao)){
        $msg = "Favor, preencher os campos obrigat&oacute;rios.";
        $campos[] = "peca";
    }else{
        $sqlPeca = "
            SELECT  tbl_peca.peca
            FROM    tbl_peca
            WHERE   tbl_peca.fabrica    = $login_fabrica
            AND     tbl_peca.referencia = '$peca_referencia'
        ";
        $resPeca = pg_query($con,$sqlPeca);
        $peca = pg_fetch_result($resPeca,0,peca);
    }

    if(strlen($codigo_posto) > 0){
        $sqlPosto = "
            SELECT  tbl_posto_fabrica.posto
            FROM    tbl_posto_fabrica
            WHERE   tbl_posto_fabrica.fabrica       = $login_fabrica
            AND     tbl_posto_fabrica.codigo_posto  = '$codigo_posto'
        ";
        $resPosto = pg_query($con,$sqlPosto);
        $posto = pg_fetch_result($resPosto,0,posto);

        $wherePosto = "AND tbl_lgr_peca_solicitacao.posto = $posto\n";
    }

    if(strlen($estado) > 0){
        $whereEstado = "AND tbl_lgr_peca_solicitacao.estado = '$estado'\n";
    }

    if(strlen($cidade) > 0){
        $whereCidade = "
            AND tbl_lgr_peca_solicitacao.cod_ibge = (
                SELECT cod_ibge FROM tbl_ibge WHERE cidade_pesquisa = '$cidade'
            )
        ";
    }

    if($pendente == 't'){
        $wherePendente = "AND tbl_lgr_peca_devolucao.data_baixa IS NULL";
    }else{
        $wherePendente = "AND tbl_lgr_peca_devolucao.data_baixa IS NOT NULL";
    }

    if(strlen($msg) > 0){
        return json_encode(array("erro"=>$msg,"campos"=>$campos));
    } else {
        $sql = "
            SELECT  tbl_lgr_peca_devolucao.lgr_peca_devolucao                           AS peca_devolucao   ,
                    tbl_lgr_peca_devolucao.os                                                               ,
                    TO_CHAR(tbl_lgr_peca_devolucao.data_input,'DD/MM/YYYY')             AS data_abertura    ,
                    tbl_posto_fabrica.codigo_posto                                      AS posto_codigo     ,
                    tbl_posto.nome                                                      AS posto_nome       ,
                    tbl_peca.referencia                                                 AS peca_referencia  ,
                    tbl_peca.descricao                                                  AS peca_descricao   ,
                    tbl_lgr_peca_devolucao.qtde                                         AS peca_qtde        ,
                    TO_CHAR(tbl_lgr_peca_devolucao.data_baixa,'DD/MM/YYYY HH24:MI:SS')  AS peca_pendente    ,
                    tbl_admin.nome_completo
            FROM    tbl_lgr_peca_solicitacao
            JOIN    tbl_lgr_peca_devolucao  USING (lgr_peca_solicitacao)
            JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_lgr_peca_devolucao.posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_lgr_peca_devolucao.peca
       LEFT JOIN    tbl_admin               ON  tbl_admin.admin             = tbl_lgr_peca_devolucao.admin
            WHERE   tbl_lgr_peca_solicitacao.fabrica    = $login_fabrica
            AND     tbl_lgr_peca_devolucao.peca         = $peca
            $wherePosto
            $whereEstado
            $whereCidade
            $wherePendente
        ";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            return json_encode(pg_fetch_all($res));
        }else{
            return json_encode(array("erro"=>"Sem pe&ccedil;as ativas"));
        }
    }
}

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados())) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT cod_ibge,UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}') AND cod_ibge IS NOT NULL
                    UNION (
                        SELECT cod_ibge,UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[$result->cod_ibge] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("erro" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("erro" => utf8_encode("estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if(filter_input(INPUT_POST,'baixar')){
    $peca_devolucao = filter_input(INPUT_POST,'peca_devolucao',FILTER_VALIDATE_INT);

    $res = pg_query($con,"BEGIN TRANSACTION");

    $sql = "
        UPDATE  tbl_lgr_peca_devolucao
        SET     data_baixa = CURRENT_TIMESTAMP,
                admin = $login_admin
        WHERE   lgr_peca_devolucao = $peca_devolucao
    ";
    $res = pg_query($con,$sql);
    if(!pg_last_error($con)){
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("ok"=>"sim"));
    }else{
        $msg = pg_last_error($con);
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo $msg;
    }
    exit;
}

if(filter_input(INPUT_POST,'pesquisar')){

    $peca_referencia    = filter_input(INPUT_POST,'peca_referencia');
    $peca_descricao     = filter_input(INPUT_POST,'peca_descricao',FILTER_SANITIZE_STRING);
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto',FILTER_SANITIZE_STRING);
    $estado             = filter_input(INPUT_POST,'estado');
    $cidade             = filter_input(INPUT_POST,'cidade');
    $pendente           = filter_input(INPUT_POST,'pendente');

    $retorno = listarPecas($peca_referencia,$peca_descricao,$pendente,$codigo_posto,$estado,$cidade);

}

include "cabecalho_new.php";

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$plugins = array(
    "autocomplete",
    "shadowbox",
    "dataTable"
);

include("plugin_loader.php");
?>
<script type="text/javascript">
$(function() {
    $.autocompleteLoad(Array("peca", "posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
    $("#estado").change(function() {
        busca_cidade($(this).val(), "");
    });

    $("#pesquisar").click(function(){
        $("input[name=pesquisar]").val("sim");
        $(this).parents("form").submit();
    });

    $("button[id^=baixa_]").click(function(){
        var dados = $(this).attr("id");
        var todo = dados.split("_");
        var peca_devolucao = todo[1];

        if(confirm("Deseja realmente dar baixa nesta peça?")){
            $.ajax({
                url:"relatorio_pecas_devolucao.php",
                type:"POST",
                dataType: "JSON",
                data:{
                    baixar:"sim",
                    peca_devolucao:peca_devolucao,
                },
                beforeSend:function(){
                    $(this).attr("disabled","disabled");
                }
            })
            .done(function(data){
                if(data.ok == "sim"){
                    $("#"+peca_devolucao).detach();
                    alert("Peça com baixa realizada.");
                }
            })
            .fail(function(){
                alert("Não foi possível realizar a baixa na peça");
            });
        }
    });
});

function busca_cidade(estado, cidade) {
    $("#cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "relatorio_pecas_devolucao.php",
            type: "POST",
            dataType:"JSON",
            data: {
                ajax_busca_cidade: true,
                estado: estado
            }
        })
        .done(function(data) {
            if (data.error) {
                alert(data.error);
            } else {
                $.each(data.cidades, function(key, value) {
                    var option = $("<option></option>", { value: key, text: value});

                    $("#cidade").append(option);
                });
            }

            $("#cidade").show().next().remove();
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){
        $('#cidade option[value='+cidade+']').attr('selected','selected');
    }
}

function retorna_peca(retorno){
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
    $("#estado").val(retorno.estado);
    busca_cidade(retorno.estado,retorno.cod_ibge);
}
</script>
<?php
$retornoDados = json_decode($retorno,TRUE);

if(isset($retornoDados['erro'])){
?>
<div class="alert alert-error"><h4><?=$retornoDados['erro']?></h4></div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("peca", $retornoDados["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_referencia'>Peça</label>
                <div class='controls controls-row'>
                    <div class='span4 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="peca_referencia" id="peca_referencia" size="12" maxlength="10" class='span12' value= "<?=$peca_referencia?>">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("peca", $retornoDados["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_descricao'>Descricao</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="peca_descricao" id="peca_descricao" size="12" maxlength="10" class='span12' value= "<?=$peca_descricao?>">
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
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
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
        <div class="span4">
            <div class="control-group ">
                <label class="control-label" for="estado">Estado</label>
                <div class="controls controls-row">
                    <div class="span10">
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
<?php
                                #O $array_estados() está no arquivo funcoes.php
                                foreach ($array_estados() as $sigla => $nome_estado) {
                                    $selected = ($sigla == $estado) ? "selected" : "";

                                    echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                                }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group ">
                <label class="control-label" for="cidade">Cidade</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select id="cidade" name="cidade" class="span12">
                            <option value="" >Selecione</option>
<?php
                                if (strlen($estado) > 0) {
                                    $sql = "SELECT DISTINCT * FROM (
                                                SELECT cod_ibge,UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}') AND cod_ibge IS NOT NULL
                                                UNION (
                                                    SELECT cod_ibge,UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                                                )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cod_ibge) == trim($cidade)) ? "SELECTED" : "";

                                            echo "<option value='{$result->cod_ibge}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
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
                <label class='control-label' for='pendente'>Pendente</label>
                <div class='controls controls-row'>
                    <label class="radio-inline">
                        <input type="radio" name="pendente" id="pendente_sim" value="t" <?=($pendente == 't' || $pendente == '') ? "checked" : ""?>>&nbsp;Sim
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="pendente" id="pendente_nao"  value="f" <?=($pendente == 'f') ? "checked" : ""?>>&nbsp;Não
                    </label>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p class="tac">
        <input type="hidden" name="pesquisar" value="" />
        <button type='button' class='btn btn-success' id="pesquisar">Pesquisar</button>
    </p>
</form>
<?PHP

if(is_array($retornoDados) && !array_key_exists('erro',$retornoDados)){
?>
<table class="table table-bordered table-striped">
    <thead>
        <tr class="titulo_tabela">
            <th>OS</th>
            <th>Data Abertura</th>
            <th>Posto</th>
            <th>Peça</th>
            <th>Qtde</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($retornoDados as $dadosPecas){
?>
        <tr id="<?=$dadosPecas['peca_devolucao']?>">
            <td class="tac"><?=$dadosPecas['os']?></td>
            <td class="tac"><?=$dadosPecas['data_abertura']?></td>
            <td class="tac"><?=$dadosPecas['posto_codigo']. "-" .$dadosPecas['posto_nome']?></td>
            <td class="tac"><?=$dadosPecas['peca_referencia']. "-" .$dadosPecas['peca_descricao']?></td>
            <td class="tar"><?=$dadosPecas['peca_qtde']?></td>
            <td class="tac">
<?php
        if($dadosPecas['peca_pendente'] == ""){
?>
                <button type='button' class='btn btn-success' id="baixa_<?=$dadosPecas['peca_devolucao']?>">Baixa</button>
<?php
        }else{
            echo "Baixa: ".$dadosPecas['peca_pendente']."<br /> por ".$dadosPecas['nome_completo'];
        }
?>
            </td>
        </tr>
<?php
    }
?>
    </tbody>
</table>
<?php
}
include "rodape.php";
?>
