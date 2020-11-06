<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "RELATÓRIO OS(S) REPROVADAS";
$layout_menu = "gerencia";
$admin_privilegios="gerencia";

if ($_POST['btn_pesquisar'] == 'Pesquisar') {
    $data_inicio    = $_POST['data_inicio'];
    $data_fim       = $_POST['data_fim'];
    $codigo_posto   = $_POST['codigo_posto'];
    $produto_referencia   = $_POST['produto_referencia'];
    $os = $_POST['os'];
    $nome_cliente      = $_POST['nome_cliente'];
    $admin_resp      = $_POST['admin_resp'];

    if (!empty($data_inicio) AND !empty($data_fim)) {
        try {
            if( validaData($data_inicio,$data_fim,12) ){

                $xdata_inicio =  fnc_formata_data_pg(trim($data_inicio));
                $xdata_inicio = str_replace("'","",$xdata_inicio);

                $xdata_fim =  fnc_formata_data_pg(trim($data_fim));
                $xdata_fim = str_replace("'","",$xdata_fim);
            }

        } catch (Exception $e) {
            $msg_erro["campos"][] = "data_consulta";
            $msg_erro["msg"][] = $e->getMessage();      
        }
        
    } else {
        $data_inicio = date('d/m/Y', strtotime("-12 months"));
        $data_fim = date('d/m/Y');

        $xdata_inicio =  fnc_formata_data_pg(trim($data_inicio));
        $xdata_inicio = str_replace("'","",$xdata_inicio);

        $xdata_fim =  fnc_formata_data_pg(trim($data_fim));
        $xdata_fim = str_replace("'","",$xdata_fim);
    }   
    

    if(!empty($codigo_posto)){
        $sql = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   fabrica      = $login_fabrica
                AND     codigo_posto = '$codigo_posto';";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $posto = trim(pg_fetch_result($res,0,posto));
            $wherePosto = " AND tbl_posto_fabrica.posto = '$posto' AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
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
            $whereProduto = " AND tbl_produto.produto = '$produto' AND tbl_produto.fabrica_i = {$login_fabrica}";
        } else {
            $msg_erro["campos"][] = "produto";
            $msg_erro["msg"][] = "Produto não encontrado!";
        }
    }

    if(!empty($os)){
        $sql = "SELECT  os
                FROM    tbl_os
                WHERE   fabrica      = $login_fabrica
                AND     os = '$os';";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $os = trim(pg_fetch_result($res,0,os));
            $whereOs = " AND tbl_os.os = '$os' AND tbl_os.fabrica = {$login_fabrica}";
        } else {
            $msg_erro["campos"][] = "os";
            $msg_erro["msg"][] = "OS não encontrada!";
        }
    }

    if (!empty($nome_cliente)) {
        $whereNomeCliente = " AND tbl_os.consumidor_nome ilike '%".trim($nome_cliente)."%' ";
    }

    if (!empty($admin_resp)) {
        $whereAdminResp = " AND tmp_os_intervencao.admin = {$admin_resp} ";
    }


    if(count($msg_erro)==0){
        $sql = "SELECT  tbl_auditoria_os.os,
                        tbl_auditoria_os.admin,
                        tbl_auditoria_os.reprovada,
                        tbl_auditoria_os.data_input AS data_auditoria
                    INTO TEMP tmp_os_intervencao
                    FROM tbl_auditoria_os
                    WHERE tbl_auditoria_os.reprovada IS NOT NULL
                    ORDER BY tbl_auditoria_os.data_input DESC";
        $res = pg_query($con,$sql);

        $sql = "SELECT  tbl_os.os ,
                        TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                        TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                        tbl_os.posto ,
                        tbl_os.sua_os,
                        tbl_os.consumidor_nome,
                        TO_CHAR(tmp_os_intervencao.data_auditoria,'DD/MM/YYYY') AS data_auditoria,
                        tmp_os_intervencao.admin,
                        TO_CHAR(tmp_os_intervencao.reprovada,'DD/MM/YYYY') AS reprovada,
                        tbl_admin.nome_completo,
                        tbl_posto.nome AS nome_posto ,
                        tbl_posto.estado,
                        tbl_posto_fabrica.codigo_posto AS codigo_posto,
                        tbl_posto_fabrica.contato_email AS posto_email ,
                        tbl_produto.referencia AS produto_referencia ,
                        tbl_produto.descricao AS produto_descricao ,
                        tbl_os_extra.os_reincidente
                    FROM tbl_os
                        JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
                        JOIN tmp_os_intervencao ON tmp_os_intervencao.os = tbl_os.os
                        JOIN tbl_admin ON tbl_admin.admin = tmp_os_intervencao.admin
                        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto 
                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
                        LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
                    WHERE tbl_os.fabrica = {$login_fabrica}
                        AND tbl_os.data_abertura between '$xdata_inicio 00:00:00' and '$xdata_fim 23:59:59'
                        $wherePosto
                        $whereProduto
                        $whereOs
                        $whereNomeCliente
                GROUP BY    tbl_os.os,tbl_os.data_abertura,
                            tbl_os.data_fechamento,
                            tmp_os_intervencao.data_auditoria,
                            tmp_os_intervencao.admin,
                            tmp_os_intervencao.reprovada,
                            tbl_admin.nome_completo,
                            tbl_os.posto,
                            tbl_os.sua_os,
                            tbl_os.consumidor_nome,
                            tbl_posto.nome,
                            tbl_posto.estado,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_posto_fabrica.contato_email,
                            tbl_produto.referencia,
                            tbl_produto.descricao,
                            tbl_os_extra.os_reincidente
                ORDER BY tbl_os.os;";

        $resSubmit = pg_query($con,$sql);

        // echo nl2br($sql);

        if(strlen(trim(pg_last_error($con)))>0){
            $msg_erro["msg"][] = "Erro ao pesquisar. ";
            unset($resSubmit);
        }

        /* Gera arquivo CSV */
        if ($_POST["gerar_excel"] && pg_num_rows($resSubmit) > 0) {

            $data = date("d-m-Y-H:i");           

            $arquivo_nome       = "csv_os_reprovada-{$data}.csv";
            $path               = "xls/";
            $path_tmp           = "/tmp/";

            $arquivo_completo       = $path.$arquivo_nome;
            $arquivo_completo_tmp   = $path_tmp.$arquivo_nome;

            $fp = fopen($arquivo_completo_tmp,"w");

            $cabecalho = array(
                "Código do Posto",
                "Nome do Posto",
                "UF",
                "OS",
                "Nome Consumidor",
                "Data Abertura",
                "Data Fechamento",
                "Admin Responsável",
                "Data Reprovação",
                "Referência do Produto",
                "Descrição do Produto"
            );

            $thead = implode(';',$cabecalho)."\r\n".$linha;
            fwrite($fp, $thead);

            $tbody = "";
            
            while($resultSubmit = pg_fetch_object($resSubmit)){

                $tbody .= "{$resultSubmit->codigo_posto};{$resultSubmit->nome_posto};{$resultSubmit->estado};{$resultSubmit->os};{$resultSubmit->consumidor_nome};{$resultSubmit->data_abertura};{$resultSubmit->data_fechamento};{$resultSubmit->nome_completo};{$resultSubmit->reprovada};{$resultSubmit->produto_referencia};{$resultSubmit->produto_descricao}\r\n";
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
    "autocomplete",
    "datepicker",
    "shadowbox",
    "maskedinput",
    "dataTable"
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

    $.dataTableLoad({ table: "#tabela_os_reprovada" });
});

function retorna_posto(retorno){
    $("#posto_id").val(retorno.posto);
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function retorna_produto(retorno){    
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
<FORM name='frm_os_reprovada' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
    <div id="div_os_reprovada" class="tc_formulario">
        <div class="titulo_tabela">Consulta OS(s) Reprovadas</div>
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
                        <div class='span12 input-append'>
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
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>' >
                    <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
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
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='os'>Número OS</label>
                    <div class='controls controls-row'>
                        <input type="text" name="os" id="os" class='span8' value="<? echo $os ?>" >
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class="control-label" for="nome_cliente">Nome do Cliente</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input id="nome_cliente" name="nome_cliente" class="span12" type="text" value="<?=$nome_cliente;?>" />
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
                <div class='control-group' >
                    <label class="control-label" for="admin_resp">Admin Responsável Pela Reprovação</label>
                    <div class="controls controls-row">
                        <div class="span11">
                            <select id="admin_resp" name="admin_resp" class="span12">
                                <option value="">Selecione</option>
                                <?php
                                $sql = "SELECT nome_completo,admin FROM tbl_admin WHERE ativo is true AND fabrica = 104;";
                                $res = pg_query($con,$sql);
                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected  = (trim($result->admin) == trim($admin_resp)) ? "SELECTED" : "";
                                        echo "<option value='{$result->admin}' {$selected} >".strtoupper($result->nome_completo)."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>              
            </div>
            <div class="span6"></div>
        </div>
        <br />
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
        <table id="tabela_os_reprovada" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_coluna">                 
                    <th>Código do Posto</th>
                    <th>Nome do Posto</th>
                    <th>UF</th>
                    <th>OS</th>
                    <th>Nome Consumidor</th>
                    <th>Data Abertura</th>
                    <th>Data Fechamento</th>
                    <th>Admin Responsável</th>
                    <th>Data Reprovação</th>
                    <th>Referência do Produto</th>
                    <th>Descrição do Produto</th>
                </tr>
            </thead>
            <tbody>
            <?php
            while($resultSubmit = pg_fetch_object($resSubmit)){
                $total_os = $resultSubmit->total_mo + $resultSubmit->total_pecas;
                ?>
                <tr>
                    <td class="tac"><?=$resultSubmit->codigo_posto?></td>
                    <td class="tac"><?=$resultSubmit->nome_posto?></td>
                    <td class="tac"><?=$resultSubmit->estado?></td>
                    <td class="tac">
                        <a href="os_press.php?os=<?=$resultSubmit->os?>" target="_blanck"><?=$resultSubmit->os?></a>
                    </td>
                    <td class="tac"><?=$resultSubmit->consumidor_nome?></td>
                    <td class="tac"><?=$resultSubmit->data_abertura?></td>
                    <td class="tac"><?=$resultSubmit->data_fechamento?></td>
                    <td class="tac"><?=$resultSubmit->nome_completo?></td>
                    <td class="tac"><?=$resultSubmit->reprovada?></td>
                    <td class="tac"><?=$resultSubmit->produto_referencia?></td>
                    <td class="tac"><?=$resultSubmit->produto_descricao?></td>
                </tr>
            <?php
            }?>
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
