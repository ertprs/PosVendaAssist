<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "OS(s) Reincidêntes";
$layout_menu = "callcenter";
$admin_privilegios="callcenter";

if ($_POST['btn_pesquisar'] == 'Pesquisar') {
    
    $data_inicio        = $_POST['data_inicio'];
    $data_fim           = $_POST['data_fim'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $nome_consumidor    = $_POST['nome_consumidor'];
    $numero_serie       = $_POST['numero_serie'];
    $mesmo_produto      = $_POST['mesmo_produto'];
    
    try {
        if( validaData($data_inicio,$data_fim,3) ){

            $xdata_inicio =  fnc_formata_data_pg(trim($data_inicio));
            $xdata_inicio = str_replace("'","",$xdata_inicio);

            $xdata_fim =  fnc_formata_data_pg(trim($data_fim));
            $xdata_fim = str_replace("'","",$xdata_fim);

            $joinOS2 = " JOIN tbl_os os2 USING(fabrica,posto,nota_fiscal,data_nf,revenda) ";
            $whereReincidente = " AND os1.os_reincidente is TRUE ";
        }

    } catch (Exception $e) {
        $msg_erro["campos"][] = "data_consulta";
        $msg_erro["msg"][] = $e->getMessage();      
    }

    if(!empty($codigo_posto)){
        $sql = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   fabrica      = $login_fabrica
                AND     codigo_posto = '$codigo_posto';";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $posto = trim(pg_fetch_result($res,0,posto));
            $wherePosto = " AND os1.posto = '$posto' AND os2.posto = {$posto}";
        } else {
            $msg_erro["campos"][] = "posto";
            $msg_erro["msg"][] = "Posto não encontrado!";
        }
    }

    if(!empty($produto_referencia)){
        $sql = "SELECT  produto
                FROM    tbl_produto
                WHERE   fabrica_i      = $login_fabrica
                AND     referencia = '$produto_referencia';";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $produto = trim(pg_fetch_result($res,0,produto));
            $whereProduto = " AND os1.produto = '$produto' AND os2.produto = {$produto}";
        } else {
            $msg_erro["campos"][] = "produto";
            $msg_erro["msg"][] = "Produto não encontrado!";
        }
    }

    if(!empty($nome_consumidor)){
        $whereConsumidor = " AND os1.consumidor_nome ilike '%{$nome_consumidor}%' 
                             AND os2.consumidor_nome ilike '%{$nome_consumidor}%'";
    }
 
    if(!empty($numero_serie)){
        $joinOS2 = " JOIN tbl_os os2 USING(fabrica,posto,nota_fiscal,data_nf,revenda,serie) ";
        $whereReincidente = "";
    }

    if(!empty($mesmo_produto)){
        $joinOS2 = " JOIN tbl_os os2 USING(fabrica,posto,nota_fiscal,data_nf,revenda,produto) ";
        $whereReincidente = "";
    }

    if(!empty($mesmo_produto) AND !empty($numero_serie)){
        $joinOS2 = " JOIN tbl_os os2 USING(fabrica,posto,nota_fiscal,data_nf,revenda,serie,produto) ";
        $whereReincidente = "";
    }

    if(count($msg_erro)==0){

        $sql = "SELECT  DISTINCT os1.os                 AS os,
                        tbl_tipo_atendimento.descricao  AS tipo_atendimento,
                        tbl_posto_fabrica.codigo_posto  AS codigo_posto,
                        tbl_posto.nome                  AS nome_posto,
                        tbl_produto.referencia||' - '||tbl_produto.descricao AS produto_referencia,
                        os1.consumidor_nome             AS nome_consumidor,
                        TO_CHAR(os1.data_abertura,'DD/MM/YYYY') AS data_abertura,
                        TO_CHAR(os1.finalizada,'DD/MM/YYYY') AS data_finalizacao,
                        os1.nota_fiscal                 AS numero_nf,
                        TO_CHAR(os1.data_nf,'DD/MM/YYYY') AS data_nf,
                        tbl_status_checkpoint.descricao AS status,
                        tbl_os_extra.extrato            AS extrato
                    FROM tbl_os os1
                        $joinOS2
                        JOIN tbl_posto ON tbl_posto.posto = os1.posto
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        JOIN tbl_produto ON tbl_produto.produto = os1.produto
                        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = os1.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                        JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = os1.status_checkpoint
                        JOIN tbl_os_extra ON tbl_os_extra.os = os1.os
                    WHERE os1.data_abertura between '$xdata_inicio 00:00:00' and '$xdata_fim 23:59:59'
                        AND os1.fabrica = {$login_fabrica}
                        AND os2.fabrica = {$login_fabrica}
                        AND os2.excluida is not TRUE
                        AND os1.excluida is not TRUE
                        $wherePosto
                        $whereProduto
                        $whereConsumidor
                        $whereReincidente
                        AND os1.os > os2.os
                        AND os2.data_abertura + interval '90 days' >= os1.data_abertura;";

        $resSubmit = pg_query($con,$sql);
        // echo nl2br($sql);exit;

        if(strlen(trim(pg_last_error($con)))>0){
            $msg_erro["msg"][] = "Erro ao pesquisar. ";
            unset($resSubmit);
        }

        /* Gera arquivo CSV */
        if ($_POST["gerar_excel"] && pg_num_rows($resSubmit) > 0) {

            $data = date("d-m-Y-H:i");
            $arquivo_nome       = "os_reincidente-{$data}.csv";            
            $path               = "xls/";
            $path_tmp           = "/tmp/";

            $arquivo_completo       = $path.$arquivo_nome;
            $arquivo_completo_tmp   = $path_tmp.$arquivo_nome;            

            $fp = fopen($arquivo_completo_tmp,"w");

            $cabecalho = array(
                "OS",
                "Tipo de Atendimento",
                "Código do Posto",
                "Nome do posto",
                "Produto Referência",
                "Nome do Consumidor",
                "Data de abertura",
                "Data finalização",
                "Número de nota fiscal",
                "Data da nota fiscal",
                "Status",
                "Extrato"
            );

            $thead = implode(';',$cabecalho)."\r\n".$linha;
            fwrite($fp, $thead);

            $tbody = "";
            while($resultSubmit = pg_fetch_object($resSubmit)){

                $total_os = $resultSubmit->total_mo + $resultSubmit->total_pecas;
                
                $tbody .= "$resultSubmit->os;$resultSubmit->tipo_atendimento;$resultSubmit->codigo_posto;$resultSubmit->nome_posto;$resultSubmit->produto_referencia;$resultSubmit->nome_consumidor;$resultSubmit->data_abertura;$resultSubmit->data_finalizacao;$resultSubmit->numero_nf;$resultSubmit->data_nf;$resultSubmit->status;$resultSubmit->extrato\r\n";            
            }            

            fwrite($fp, $tbody);
            fclose($fp);

            if (file_exists($arquivo_completo_tmp)) {
                system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
                echo $arquivo_completo;
            }
            exit;
        }
    }
}

include 'cabecalho_new.php';

$plugins = array(
    "datepicker",
    "shadowbox",
    "maskedinput",
    "ajaxform",
    "dataTable",
    "multiselect",
    "autocomplete"
);

include 'plugin_loader.php';
?>
<script type="text/javascript">

$(function() {
    $("#data_inicio").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("#data_fim").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    $.autocompleteLoad(Array("posto","produto"));

    /**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();

    /**
     * Evento que chama a função de lupa para a lupa clicada
     */
    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    $.dataTableLoad({ table: "#tabela_os_reincidencia" });
});

function retorna_posto(retorno){
    $("#posto_id").val(retorno.posto);
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

</script>

<?php 
if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php 
} ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_lancamentos_os_prestacao_servico' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
    <div id="div_consulta_lancamento_os" class="tc_formulario">
        <div class="titulo_tabela">OS(s) Reincidêntes</div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span2">
                <div class='control-group <?=(in_array("data_consulta", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="data_inicio">Data Início</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>  
                            <input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?=$data_inicio;?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
            <div class="span2">
                <div class='control-group <?=(in_array("data_consulta", $msg_erro["campos"])) ? "error" : ""?>' >
                    <label class="control-label" for="data_fim">Data Fim</label>                    
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>  
                            <input id="data_fim" name="data_fim" class="span12" type="text" value="<?=$data_fim;?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>        
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
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
                        <div class='span11 input-append'>
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>  
            <div class="span2"></div>
        </div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='nome_consumidor'>Nome do Consumidor</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" id="nome_consumidor" name="nome_consumidor" class='span12' maxlength="50" value="<?=$nome_consumidor;?>" >                            
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
            </div>
            <div class='span2'></div>
        </div>
	<? if (!in_array($login_fabrica, array(169,170))) { ?>
            <br />
            <div class="titulo_tabela"><B>Selecione Tipo de Reincidência</B></div>
            <br />
            <div class='row-fluid'>
            	<div class='span3'></div>
            	<div class='span3'>
                    <div class='control-group'>
                   	<label class="checkbox" for="">Mesmo número de série
                        	<input type='checkbox' name='numero_serie' value='numero_serie' <?=(!empty($numero_serie)) ? 'checked' : ''?> >
                        </label>
                    </div>
            	</div>
            	<div class='span3'>
                    <div class='control-group'>
                    	<label class="checkbox" for="">Mesmo produto
                            <input type='checkbox' name='mesmo_produto' value='mesmo_produto' <?=(!empty($mesmo_produto)) ? 'checked' : ''?>>
                    	</label>
                    </div>
            	</div>
            	<div class='span3'></div>
            </div>
	<? } ?>
        <br />
        <div class="row-fluid">
            <div class="span4"></div>
            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">                     
                        <input type="submit" class="btn" id="btn_pesquisar" name="btn_pesquisar" value="Pesquisar" />
                    </div>
                </div>
            </div>
            <div class="span4"></div>
        </div>
    </div>
</FORM>
</div>
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) { ?>
        <br />
        <table id="tabela_os_reincidencia" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_coluna">
                    <th>OS</th>
                    <th>Tipo de Atendimento</th>
                    <th>Código do Posto</th>
                    <th>Nome do posto</th>
                    <th>Produto Referência</th>
                    <th>Nome do Consumidor</th>
                    <th>Data de abertura</th>
                    <th>Data finalização</th>
                    <th>Número de nota fiscal</th>
                    <th>Data da nota fiscal</th>
                    <th>Status</th>
                    <th>Extrato</th>
                </tr>
            </thead>
            <tbody>
            <?php
            while($resultSubmit = pg_fetch_object($resSubmit)){?>
                <tr>
                    <td class="tac">
                        <a href="os_press.php?os=<?=$resultSubmit->os?>" target="_blanck"><?=$resultSubmit->os?></a>
                    </td>
                    <td class="tac"><?=$resultSubmit->tipo_atendimento?></td>
                    <td class="tac"><?=$resultSubmit->codigo_posto?></td>
                    <td class="tac"><?=$resultSubmit->nome_posto?></td>
                    <td class="tac"><?=$resultSubmit->produto_referencia?></td>
                    <td class="tac"><?=$resultSubmit->nome_consumidor?></td>
                    <td class="tac"><?=$resultSubmit->data_abertura?></td>
                    <td class="tac"><?=$resultSubmit->data_finalizacao?></td>
                    <td class="tac"><?=$resultSubmit->numero_nf?></td>
                    <td class="tac"><?=$resultSubmit->data_nf?></td>
                    <td class="tac"><?=$resultSubmit->status?></td>
                    <td class="tac"><?=$resultSubmit->extrato?></td>
                </tr>
            <?php                
            } ?>
            </tbody>            
        </table>
        <br />
        <?php
        $jsonPOST = excelPostToJson($_POST); ?>
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
            <span class="txt">Gerar Arquivo CSV</span>
        </div>
    <?php
    } else { ?>
        <div class='container'>
            <div class='alert'>
                <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
    <?php
    }
}
include "rodape.php";
