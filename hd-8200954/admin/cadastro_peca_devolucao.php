<?php
/**
 * cadastro_peca_devolucao.php
 * - Tela para cadastro de peças para
 * uso de devoluções obrigatórias
 *
 * @author William Ap. Brandino
 * @since 2015-12-21
 */
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Peças para Devolução Obrigatória";
$layout_menu = "cadastro";

/**
 * listarTodas()
 * - Função para listagem de todas as peças ATIVAS
 * para uso de devolução obrigatória
 *
 * @return Array com os dados
 */

function listarTodas(){
    global $con,$login_fabrica;

    $sql = "
        SELECT  tbl_lgr_peca_solicitacao.lgr_peca_solicitacao   AS peca_devolucao   ,
                tbl_peca.peca                                   AS peca             ,
                tbl_peca.referencia                             AS peca_referencia  ,
                tbl_peca.descricao                              AS peca_descricao   ,
                tbl_posto_fabrica.codigo_posto                  AS posto_codigo     ,
                UPPER(fn_retira_especiais(tbl_posto.nome))      AS posto_nome       ,
                tbl_lgr_peca_solicitacao.estado                 AS peca_estado      ,
                UPPER(fn_retira_especiais(tbl_ibge.cidade))     AS peca_cidade      ,
                tbl_lgr_peca_solicitacao.qtde                   AS peca_qtde
        FROM    tbl_lgr_peca_solicitacao
        JOIN    tbl_peca            USING (fabrica,peca)
   LEFT JOIN    tbl_posto           USING (posto)
   LEFT JOIN    tbl_posto_fabrica   USING (posto,fabrica)
   LEFT JOIN    tbl_ibge            ON tbl_ibge.cod_ibge = tbl_lgr_peca_solicitacao.cod_ibge
        WHERE   tbl_lgr_peca_solicitacao.fabrica    = $login_fabrica
        AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
    ";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        return json_encode(pg_fetch_all($res));
    }else{
        return json_encode(array("erro"=>"Sem pe&ccedil;as ativas"));
    }
}

/**
 * gravarPeca($peca_referencia,$peca_descricao,$qtde,$codigo_posto,$descricao_posto,$estado,$cidade)
 * - Função para gravação das peças para uso
 * nas OS, caso seja necessária a devolução obrigatória
 *
 * @param String $peca_referencia Referência da peça
 * @param String $peca_descricao Nome da peça
 * @param Int $qtde Quantidade de vezes que a peça entrará em OSs
 * @param String|Null $codigo_posto Código do posto
 * @param String|Null $estado Unidade da Federação que ocorrerá a verificação das peças cadastradas na OS
 * @param Int|Null $cidade Município que ocorrerá a verificação das peças cadastradas na OS
 *
 * @return Lista Atualizada com a peça cadastrada
 */
function gravarPeca($peca_referencia,$peca_descricao,$qtde,$codigo_posto,$estado,$cidade){
    global $con,$login_fabrica,$login_admin;
    if(empty($peca_referencia) || empty($peca_descricao) || empty($qtde)){
        $msg = "Favor, preencher os campos obrigat&oacute;rios.";
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

    $postoInsert = "";
    $postoResult = "";
    if(strlen($codigo_posto) > 0){
        $sqlPosto = "
            SELECT  tbl_posto_fabrica.posto
            FROM    tbl_posto_fabrica
            WHERE   tbl_posto_fabrica.fabrica       = $login_fabrica
            AND     tbl_posto_fabrica.codigo_posto  = '$codigo_posto'
        ";
        $resPosto = pg_query($con,$sqlPosto);
        $posto = pg_fetch_result($resPosto,0,posto);

        $estadoInsert = ", estado";
        $estadoResult = ",\n'".$estado."'";
        $cidadeInsert = ", cod_ibge";
        $cidadeResult = ",\n".$cidade;
        $postoInsert = ", posto";
        $postoResult = ",\n".$posto;
        $sqlVerificaPosto = "
            SELECT  DISTINCT
                    tbl_lgr_peca_solicitacao.peca
                    FROM    tbl_lgr_peca_solicitacao
                    WHERE   tbl_lgr_peca_solicitacao.concluida IS NOT TRUE
                    AND     (
                                tbl_lgr_peca_solicitacao.posto = $posto
                            OR  tbl_lgr_peca_solicitacao.peca IN (
                                    SELECT  tbl_lgr_peca_solicitacao.peca
                                    FROM    tbl_lgr_peca_solicitacao
                                    WHERE   tbl_lgr_peca_solicitacao.peca = $peca
                                    AND     tbl_lgr_peca_solicitacao.posto <> $posto
                                    AND     (
                                                tbl_lgr_peca_solicitacao.cod_ibge = (
                                                    SELECT  tbl_posto_fabrica.cod_ibge
                                                    FROM    tbl_posto_fabrica
                                                    WHERE   tbl_posto_fabrica.posto = $posto
                                                    AND     tbl_posto_fabrica.fabrica = $login_fabrica
                                                )
                                            OR  tbl_lgr_peca_solicitacao.estado = (
                                                    SELECT  tbl_posto.estado
                                                    FROM    tbl_posto
                                                    WHERE   tbl_posto.posto = $posto
                                                )
                                            )
                                    AND     tbl_lgr_peca_solicitacao.concluida IS NOT TRUE
                                )
                            )
                    AND     tbl_lgr_peca_solicitacao.peca = $peca
        ";
//         exit(nl2br($sqlVerificaPosto));
        $resVerificaPosto = pg_query($con,$sqlVerificaPosto);
        $msgPosto = (pg_num_rows($resVerificaPosto) > 0) ? "O posto j&aacute; &eacute; encontrado em uma regi&atilde;o cadastrada" : "";
    }

    $estadoInsert = "";
    $estadoResult = "";
    if(strlen($estado) > 0 && strlen($cidade) == 0 ){
        $estadoInsert = ", estado";
        $estadoResult = ",\n'".$estado."'";
        $sqlVerificaEstado = "
            SELECT  DISTINCT
                    tbl_lgr_peca_solicitacao.peca
                    FROM    tbl_lgr_peca_solicitacao
                    WHERE   tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                    AND     (
                                tbl_lgr_peca_solicitacao.estado     = '$estado'
                            OR  tbl_lgr_peca_solicitacao.peca IN (
                                    SELECT  tbl_lgr_peca_solicitacao.peca
                                    FROM    tbl_lgr_peca_solicitacao
                                    WHERE   tbl_lgr_peca_solicitacao.peca = $peca
                                    AND     (
                                                tbl_lgr_peca_solicitacao.cod_ibge IN (
                                                    SELECT  tbl_ibge.cod_ibge
                                                    FROM    tbl_ibge
                                                    WHERE   tbl_ibge.estado = '$estado'
                                                )
                                            OR  tbl_lgr_peca_solicitacao.posto = (
                                                    SELECT  tbl_lgr_peca_solicitacao.posto
                                                    FROM    tbl_lgr_peca_solicitacao
                                                    JOIN    tbl_posto   ON  tbl_posto.posto     = tbl_lgr_peca_solicitacao.posto
                                                                        AND tbl_posto.estado    = '$estado'
                                                    WHERE   tbl_lgr_peca_solicitacao.concluida IS NOT TRUE
                                                )
                                            )
                                    AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                                )
                            )
                    AND     tbl_lgr_peca_solicitacao.peca = $peca
        ";
        $resVerificaEstado = pg_query($con,$sqlVerificaEstado);
        $msgEstado = (pg_num_rows($resVerificaEstado) > 0) ? "O estado j&aacute; &eacute; encontrado em uma regi&atilde;o cadastrada" : "";
    }

    $cidadeInsert = "";
    $cidadeResult = "";
    if(strlen($cidade) > 0){
        $estadoInsert = ", estado";
        $estadoResult = ",\n'".$estado."'";
        $cidadeInsert = ", cod_ibge";
        $cidadeResult = ",\n".$cidade;
        $sqlVerificaCidade = "
            SELECT  DISTINCT
                    tbl_lgr_peca_solicitacao.peca
                    FROM    tbl_lgr_peca_solicitacao
                    WHERE   tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                    AND     (
                                tbl_lgr_peca_solicitacao.cod_ibge = $cidade
                            OR  tbl_lgr_peca_solicitacao.peca IN (
                                    SELECT  tbl_lgr_peca_solicitacao.peca
                                    FROM    tbl_lgr_peca_solicitacao
                                    WHERE   tbl_lgr_peca_solicitacao.peca = $peca
                                    AND     tbl_lgr_peca_solicitacao.posto IN (
                                                SELECT  tbl_posto_fabrica.posto
                                                FROM    tbl_posto_fabrica
                                                JOIN    tbl_ibge USING (cod_ibge)
                                                JOIN    tbl_posto USING(posto,estado)
                                                WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
                                                AND     tbl_ibge.cod_ibge = $cidade
                                            )
                                    AND     tbl_lgr_peca_solicitacao.concluida  IS NOT TRUE
                                )
                            )
                    AND     tbl_lgr_peca_solicitacao.peca = $peca
        ";
        $resVerificaCidade = pg_query($con,$sqlVerificaCidade);
        $msgCidade = (pg_num_rows($resVerificaCidade) > 0) ? "A cidade j&aacute; &eacute; encontrada em uma regi&atilde;o cadastrada" : "";
    }

    $msgArray = array($msgPosto,$msgEstado,$msgCidade);
    foreach($msgArray as $frase){
        if(strlen($frase) > 0){
            $msg .= "<br>".$frase;
        }
    }

    if(strlen($msg) > 0){
        return json_encode(array("erro"=>$msg));
    } else {
        /**
         * - Por regra, não se pode duplicar a peça, tendo a mesma cadastrada
         * para a mesma, ou região pertinente, obedecendo a condição de,
         * se existir, estar com a quantidade
         * zerada.
         *
         */

        $res = pg_query($con,"BEGIN TRANSACTION");
        $sql = "
            INSERT INTO tbl_lgr_peca_solicitacao (
                peca,
                fabrica,
                qtde,
                admin
                $postoInsert
                $estadoInsert
                $cidadeInsert
            ) VALUES (
                $peca,
                $login_fabrica,
                $qtde,
                $login_admin
                $postoResult
                $estadoResult
                $cidadeResult
            )
        ";
        $res = pg_query($con,$sql);

        if(!pg_last_error($con)){
            $res = pg_query($con,"COMMIT TRANSACTION");
            $listar = listarTodas();
            return $listar;
        } else {
            $gravaErro = pg_last_error($con);
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            return json_encode(array("erro" => $gravaErro));
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

if(filter_input(INPUT_POST,'gravar') || filter_input(INPUT_POST,'listar')){

    $peca_referencia    = filter_input(INPUT_POST,'peca_referencia');
    $peca_descricao     = filter_input(INPUT_POST,'peca_descricao',FILTER_SANITIZE_STRING);
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto',FILTER_SANITIZE_STRING);
    $estado             = filter_input(INPUT_POST,'estado');
    $cidade             = filter_input(INPUT_POST,'cidade');
    $qtde               = filter_input(INPUT_POST,'qtde');

    if(filter_input(INPUT_POST,'listar')){
        $retorno = listarTodas();
    } else {
        $retorno = gravarPeca($peca_referencia,$peca_descricao,$qtde,$codigo_posto,$estado,$cidade);
    }
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
    "dataTable",
    "alphanumeric"
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

    $("#qtde").numeric();

    $("#listar").click(function(){
        $("input[name=listar]").val("sim");
        $(this).parents("form").submit();
    });

    $("#gravar").click(function(){
        $("input[name=gravar]").val("sim");
        $(this).parents("form").submit();
    });
});

function busca_cidade(estado, cidade) {
    $("#cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "cadastro_peca_devolucao.php",
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
//     console.debug(retorno);
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
            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
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
            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
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
            <div class="control-group <?=(in_array('estado', $msg_erro['campos'])) ? "error" : "" ?>">
                <label class="control-label" for="estado">Estado</label>
                <div class="controls controls-row">
                    <div class="span10">
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
<?php
                                #O $array_estados() está no arquivo funcoes.php
                                foreach ($array_estado as $sigla => $nome_estado) {
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
            <div class="control-group <?=(in_array('cidade', $msg_erro['campos'])) ? "error" : "" ?>">
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
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='qtde'>Qtde</label>
                <div class='controls controls-row'>
                    <div class='span4 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="qtde" id="qtde" size="12" maxlength="10" class='span12' value="<?=$qtde?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p class="tac">
        <input type="hidden" name="gravar" value="" />
        <input type="hidden" name="listar" value="" />
        <button type='button' class='btn btn-success' id="gravar">Gravar</button>
        <button type='button' class='btn btn-info' id="listar">Listar Ativas</button>
    </p>
</form>
<?PHP

if(is_array($retornoDados) && !array_key_exists('erro',$retornoDados)){
?>
<table class="table table-bordered table-striped">
    <thead>
        <tr class="titulo_tabela">
            <th>Peça</th>
            <th>Posto</th>
            <th>Cidade</th>
            <th>UF</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($retornoDados as $dadosPecas){
?>
        <tr id="<?=$dadosPecas['peca_devolucao']?>">
            <td class="tac"><?=$dadosPecas['peca_referencia']. "-" .$dadosPecas['peca_descricao']?></td>
            <td class="tac"><?=$dadosPecas['posto_codigo']. "-" .$dadosPecas['posto_nome']?></td>
            <td class="tac"><?=$dadosPecas['peca_cidade']?></td>
            <td class="tac"><?=$dadosPecas['peca_estado']?></td>
            <td class="tar"><?=$dadosPecas['peca_qtde']?></td>
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
