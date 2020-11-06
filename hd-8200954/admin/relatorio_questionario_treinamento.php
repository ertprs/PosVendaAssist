<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";

$array_estados = array(
    'AC' => 'Acre','AL' => 'Alagoas','AM' => 'Amazonas','AP' => 'Amapá','BA' => 'Bahia',
    'CE' => 'Ceara','DF' => 'Distrito Federal','ES' => 'Espírito Santo','GO' => 'Goiás',
    'MA' => 'Maranhão','MG' => 'Minas Gerais','MS' => 'Mato Grosso do Sul','MT' => 'Mato Grosso',
    'PA' => 'Pará','PB' => 'Paraíba','PE' => 'Pernambuco','PI' => 'Piauí­','PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro','RN' => 'Rio Grande do Norte','RO' => 'Rondônia','RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul','SC' => 'Santa Catarina','SE' => 'Sergipe','SP' => 'São Paulo',
    'TO' => 'Tocantins'
);

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "
            SELECT DISTINCT *
            FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                UNION (
                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                )
            ) AS cidade
            ORDER BY cidade ASC;
        ";
        $res = pg_query($con,$sql);

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
        $retorno = array("error" => utf8_encode("Estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {

    $estado = $_POST["estado"];
    $cidade = $_POST["cidade"];
    $treinamento = $_POST["treinamento"];

    if (!empty($cidade)){
        $sqlCidade = "
            SELECT DISTINCT *
            FROM (
                    SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('$cidade')
                UNION (
                    SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER('$cidade')
                )) AS cidade
            ORDER BY cidade ASC;
        ";
        $resCidade = pg_query($con, $sqlCidade);
       
        if (pg_num_rows($resCidade) == 0 || strlen(pg_last_error()) > 0) {
            $msg_erro['msg'][] = "Ocorreu um problema na seleção da cidade.";
            $msg_erro['campos'][] = "cidade";
        } else {
            $idCidade = pg_fetch_result($resCidade, 0, cidade);
        }
    }

    if (!empty($treinamento)){
        $sql = "SELECT treinamento FROM tbl_treinamento WHERE fabrica = {$login_fabrica} AND treinamento = {$treinamento}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0){
            $msg_erro['msg'][] = "Treinamento não encontrado.";
            $msg_erro['campos'][] = "treinamento";
        }else{
            $cond_treinamento = " AND tbl_treinamento.treinamento = $treinamento ";
        }
    }
   
    if (count($msg_erro['msg']) == 0) {

        if (strlen(trim($cidade)) > 0){
            $cond_cidade = " AND tbl_treinamento_cidade.cidade = {$idCidade} OR tbl_treinamento_cidade.estado = '{$estado}' ";
        }

        if(!empty($estado) AND empty($cidade)){
            $cond_estado = " AND tbl_treinamento_cidade.estado = '{$estado}' AND tbl_treinamento_cidade.cidade IS NULL ";
        }

        $sql = "SELECT tbl_treinamento.treinamento,
                tbl_treinamento.titulo,
                tbl_treinamento.descricao,
                tbl_treinamento.local,
                TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
                tbl_linha.nome                                        AS linha_nome,
                tbl_familia.descricao                                 AS familia_descricao,
                tbl_cidade.nome                                       AS treinamento_cidade,
                tbl_cidade.estado                                     AS treinamento_estado,
                tbl_tecnico.nome                                      AS nome_tecnico,
                tbl_tecnico.tecnico                                   AS id_tecnico
            FROM tbl_treinamento
            JOIN      tbl_admin   USING(admin)
            JOIN      tbl_linha   USING(linha)
            JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                AND tbl_treinamento.fabrica = $login_fabrica
            JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico AND tbl_tecnico.fabrica = 169
            JOIN tbl_cidade ON tbl_cidade.cidade = tbl_treinamento.cidade
            LEFT JOIN tbl_familia USING(familia)
            LEFT JOIN tbl_treinamento_cidade ON tbl_treinamento_cidade.treinamento = tbl_treinamento.treinamento
                AND tbl_treinamento.fabrica = $login_fabrica
            WHERE tbl_treinamento.fabrica = $login_fabrica
            AND tbl_treinamento.data_finalizado IS NOT NULL
            $cond_cidade
            $cond_estado
            $cond_treinamento
            GROUP BY tbl_treinamento.treinamento, tbl_treinamento.titulo, tbl_treinamento.descricao,
                tbl_treinamento.local, tbl_treinamento.data_inicio, tbl_linha.nome, tbl_familia.descricao,
                tbl_cidade.cidade, tbl_cidade.estado, tbl_tecnico.nome, tbl_tecnico.tecnico
            ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo" ;
        $res_dados = pg_query($con,$sql);
    }
}

$title = "CADASTRO DE JORNADAS";

include "cabecalho_new.php";

unset($form);

$plugins = array(
    "tooltip",
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "select2",
    "dataTable"
);

include ("plugin_loader.php");
?>
<script type="text/javascript">
    $(function() {
        Shadowbox.init();
        $("#treinamento").select2();
        $('#estado').select2();
        $('#cidade').select2();
        /**
         * Evento para quando alterar o estado carregar as cidades do estado
         */
        $("#estado").change(function() {
            busca_cidade($(this).val());
        });


        $(".button_questionario").click(function(){

            var treinamento  = $(this).data("treinamento");
            var tecnico  = $(this).data("tecnico");
            
            Shadowbox.open({
                content: "visualiza_questionario.php?treinamento="+treinamento+"&tecnico="+tecnico,
                player: 'iframe',
                width: 1024,
                height: 600
            });
        });

    });

    /**
     * Função que busca as cidades do estado e popula o select cidade
     */
    function busca_cidade(estado, cidade) {
        $("#cidade").find("option").first().nextAll().remove();

        if (estado.length > 0) {
            $.ajax({
                async: false,
                url: "jornada_cadastro.php",
                type: "POST",
                timeout: 60000,
                data: { ajax_busca_cidade: true, estado: estado },
                beforeSend: function() {
                    if ($("#cidade").next("img").length == 0) {
                        $("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                    }
                },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);

                    if (data.error) {
                        alert(data.error);
                    } else {
                        $.each(data.cidades, function(key, value) {
                            var option = $("<option></option>", { value: value, text: value });
                            $("#cidade").append(option);
                        });
                    }

                    $("#cidade").show().next().remove();
                }
            });
        }

        if(typeof cidade != "undefined" && cidade.length > 0){
            $("#cidade option[value='"+cidade+"']").attr('selected','selected');
        }
    }

</script>

<?php if (count($msg_erro['msg']) > 0) { ?>
    <div class="alert alert-error">
        <h4><?= implode(',', $msg_erro['msg']); ?></h4>
    </div>
<? } ?>

<br/>
<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_relatorio" method="post" action="<?= $PHP_SELF; ?>">
    <div class="titulo_tabela">Pesquisa Questionário Treinamento</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("treinamento", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='treinamento'>Tipo do Treinamento (Título)</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="treinamento" id="treinamento">
                            <option value="">Selecione</option>
                            <?php
                            $sql = "
                                SELECT titulo, treinamento
                                FROM tbl_treinamento
                                WHERE fabrica = $login_fabrica
                                AND ativo
                                AND data_finalizado IS NOT NULL ";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_treinamento = ( isset($treinamento) and ($treinamento == $key['treinamento']) ) ? "SELECTED" : '' ;
                            ?>
                                <option value="<?php echo $key['treinamento']?>" <?php echo $selected_treinamento ?> >
                                    <?php echo $key['titulo']?>
                                </option>
                            <?php
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
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?= (in_array('estado', $msg_erro['campos'])) ? "error" : ""; ?>">
                <label class="control-label" for="estado">Estado</label>
                <div class="controls controls-row">
                    <div class="span11">
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <? foreach ($array_estados as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : ""; ?>
                                <option value="<?= $sigla; ?>" <?= $selected; ?> ><?= $nome_estado; ?></option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="span4">
            <div class="control-group <?= (in_array('cidade', $msg_erro['campos'])) ? "error" : ""; ?>">
                <label class="control-label" for="cidade">Cidade</label>
                <div class="controls controls-row">
                    <div class="span11">
                        <select id="cidade" name="cidade" class="span12" >
                            <option value="" >Selecione</option>
                            <? if (strlen($estado) > 0) {
                                $sql = "
                                    SELECT DISTINCT * FROM (
                                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC;
                                ";

                                $res = pg_query($con,$sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected = (trim($result->cidade) == trim($cidade)) ? "SELECTED" : ""; ?>
                                        <option value="<?= $result->cidade; ?>" <?= $selected; ?> ><?= $result->cidade; ?></option>
                                    <? }
                                }
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <br/>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
    <br/>
</form>

<?php
    if (pg_num_rows($res_dados) > 0){
?>
        <table id="resultado_questionario" class='table table-striped table-bordered table-hover table-fixed' >
                <thead>
                    <tr class='titulo_coluna' >
                        <th>Data</th>
                        <th>Titulo</th>
                        <th>Linha</th>
                        <th>Local</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Nome Técnico</th>
                        <th>Resultado Questionário</th>
                    </tr>
                </thead>
                <tbody>
        <?php
        for ($i=0; $i < pg_num_rows($res_dados); $i++) { 
            $treinamento        = pg_fetch_result($res_dados, $i, 'treinamento');
            $titulo             = pg_fetch_result($res_dados, $i, 'titulo');
            $descricao          = pg_fetch_result($res_dados, $i, 'descricao');
            $local              = pg_fetch_result($res_dados, $i, 'local');
            $data_inicio        = pg_fetch_result($res_dados, $i, 'data_inicio');
            $linha_nome         = pg_fetch_result($res_dados, $i, 'linha_nome');
            $familia_descricao  = pg_fetch_result($res_dados, $i, 'familia_descricao');
            $treinamento_cidade = pg_fetch_result($res_dados, $i, 'treinamento_cidade');
            $treinamento_estado = pg_fetch_result($res_dados, $i, 'treinamento_estado');  
            $nome_tecnico       = pg_fetch_result($res_dados, $i, 'nome_tecnico');
            $id_tecnico         = pg_fetch_result($res_dados, $i, 'id_tecnico');
        ?>
            <tr>
                <td><?=$data_inicio?></td>
                <td><?=$titulo?></td>
                <td><?=$linha_nome?></td>
                <td><?=$local?></td>
                <td><?=$treinamento_cidade?></td>
                <td><?=$treinamento_estado?></td>
                <td><?=$nome_tecnico?></td>
                <td class="tac"><button type="button" class="btn-primary btn button_questionario" data-treinamento="<?=$treinamento?>" data-tecnico="<?=$id_tecnico?>" class="btn btn-small btn-primary">Visualizar</button></td>
            </tr>
        <?php    
        }
        ?>  
            </tbody>
        </table>
        <script>
            $.dataTableLoad({ table: "#resultado_questionario" });
        </script>
        <?php
    }  

?>
<? include "rodape.php"; ?>