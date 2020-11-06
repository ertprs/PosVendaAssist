<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $tipo_relatorio     = $_POST['tipo_relatorio'];
    $status_atendimento = $_POST['status_atendimento'];

    if(strlen(trim($status_atendimento))>0){
        $cond_2 = " AND tbl_hd_chamado.status = '$status_atendimento' ";
    }

    if(strlen(trim($tipo_relatorio))==0){
        $msg_erro["msg"][]    ="Por favor informar o Tipo de Relatório. <bR>";
    }
    
    $status             = $_POST['status_atendimento'];
    
    if(strlen(trim($data_inicial))==0 AND strlen(trim($data_final))==0){
        $msg_erro["msg"][]    ="Os campos Data Inicial e Data Final são obrigatórios. <bR>";
    }else{

    
        if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
            $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
            $xdata_inicial = str_replace("'","",$xdata_inicial);
        }else{
            $msg_erro["msg"][]    ="Data inicial inválida";
            //$msg_erro["campos"][] = "data_inicial";
        }

        if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
            $xdata_final =  fnc_formata_data_pg(trim($data_final));
            $xdata_final = str_replace("'","",$xdata_final);
        }else{
            $msg_erro["msg"][]    ="Data final inválida";
            //$msg_erro["campos"][] = "data_final";
        }

        if(strlen($msg_erro)==0){
            $dat = explode ("/", $data_inicial );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    ="Data inicial inválida";
                //$msg_erro["campos"][] = "data_inicial";
            }
        }
        if(strlen($msg_erro)==0){
            $dat = explode ("/", $data_final );//tira a barra
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    ="Data final inválida";
                //$msg_erro["campos"][] = "data_final";
            }
        }

        if($xdata_inicial > $xdata_final and strlen($msg_erro)==0)
            $msg_erro["msg"][]    ="Data inicial maior que data final.";
            //$msg_erro["campos"][] = "data_inicial";
            //$msg_erro["campos"][] = "data_final";

    }

    if(strlen($produto_referencia)>0){
        $sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $produto = pg_result($res,0,0);
            $cond_1 = "AND tbl_hd_chamado_extra.produto = $produto ";
        }
    }
  
    if(strlen(trim($msg_erro))==0){

        if($tipo_relatorio == "atendete_perfil_solicitacao_realizacao"){
            $sql = "SELECT tbl_hd_chamado.hd_chamado,
                    tbl_hd_chamado_extra.nome,
                    tbl_produto.referencia,
                    tbl_produto.descricao,
                    tbl_hd_chamado_extra.array_campos_adicionais AS perfil_cliente,
                    json_field('acordo_realizado',tbl_hd_chamado_extra.array_campos_adicionais) AS acordo_realizado
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra USING(hd_chamado)
                    JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
                    WHERE tbl_hd_chamado.fabrica = $login_fabrica
                    $cond_1
                    $cond_2 
                    AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";

        

        $theader .= "<th>Número do atendimento </th>";
        $theader .= "<th>Nome do Cliente</th>";
        $theader .= "<th>Produto</th>";
        $theader .= "<th>Perfil do Cliente</th>";
        $theader .= "<th>Pedido do cliente</th>";
        $theader .= "<th>Realização do Acordo (Sim ou Não)</th>";
        


        }elseif($tipo_relatorio == "produto_classificacao"){

            $sql = "SELECT count(tbl_hd_chamado.hd_chamado) AS qtde,
                    tbl_produto.referencia,
                    tbl_produto.descricao,
                    tbl_hd_classificacao.descricao AS classificacao
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra USING(hd_chamado)
                    JOIN tbl_hd_classificacao USING(hd_classificacao, fabrica)
                    JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
                    WHERE tbl_hd_chamado.fabrica = $login_fabrica
                    $cond_1
                    $cond_2 
                    AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
                    GROUP BY tbl_hd_classificacao.descricao,
                    tbl_produto.referencia,
                    tbl_produto.descricao
                    ORDER BY qtde DESC ";


            $theader .= "<th>Classificação</th>";
            $theader .= "<th>Produto</th>";
            $theader .= "<th>Quantidade de atendimentos</th>";



        }elseif($tipo_relatorio == "classificacao_solicitacao"){
            $sql = "SELECT count(tbl_hd_chamado.hd_chamado) AS qtde,
                    tbl_hd_chamado_extra.origem,
                    tbl_hd_classificacao.descricao AS classificacao,
                    json_field('pedido_cliente',tbl_hd_chamado_extra.array_campos_adicionais) AS pedido_cliente
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra USING(hd_chamado)
                    JOIN tbl_hd_classificacao USING(hd_classificacao, fabrica)
                    WHERE tbl_hd_chamado.fabrica = $login_fabrica
                    $cond_1
                    $cond_2 
                    AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
                    GROUP BY tbl_hd_classificacao.descricao,
                    tbl_hd_chamado_extra.origem,
                    pedido_cliente
                    ORDER BY qtde DESC";


            $theader .= "<th>Classificação</th>";
            $theader .= "<th>Pedido do Cliente</th>";
            $theader .= "<th>Origem</th>";
            $theader .= "<th>Quantidade de atendimentos</th>";

        }
    }

    $resSubmit = pg_exec($con,$sql);
    if ($_POST["gerar_excel"]) {
        if (pg_num_rows($resSubmit) > 0) {

            $data = date("d-m-Y-H:i");
            $fileName = "novos_relatorios_callcenter-{$data}.csv";

            $file = fopen("/tmp/{$fileName}", "w");

            for($y=0; $y < pg_num_rows($resSubmit); $y++){

                if($tipo_relatorio == "atendete_perfil_solicitacao_realizacao"){
                    
                    if($y == 0){
                        $thead = "Número do atendimento;Nome do Cliente;Produto;Perfil do Cliente;Pedido do cliente;Realização do Acordo;\n\r";
                        fwrite($file, $thead);    
                    }                    

                    $numero_atendimento = pg_fetch_result($resSubmit, $y, "hd_chamado");
                    $referencia         = pg_fetch_result($resSubmit, $y, "referencia");
                    $nome               = pg_fetch_result($resSubmit, $y, "nome");
                    $descricao          = pg_fetch_result($resSubmit, $y, "descricao");
                    $perfil_cliente     = pg_fetch_result($resSubmit, $y, "perfil_cliente");
            $acordo_realizado   = pg_fetch_result($resSubmit, $y, "acordo_realizado");
            $perfil_cliente = json_decode(str_replace("\\",$prefil_cliente),true);
                    
                    $tbody .= "$numero_atendimento;$nome;$referencia - $descricao;{$perfil_cliente['perfil_cliente']};{$pedido_cliente['pedido_cliente']};$acordo_realizado; \n\r";
                    
 
                }elseif($tipo_relatorio == "produto_classificacao"){
                    
                    if($y == 0){
                        $thead = "Classificação;Produto;Qtde de Atendimentos;\n\r";
                        fwrite($file, $thead);    
                    } 

                    $referencia     = pg_fetch_result($resSubmit, $y, "referencia");
                    $descricao      = pg_fetch_result($resSubmit, $y, "descricao");
                    $qtde           = pg_fetch_result($resSubmit, $y, "qtde");
                    $classificacao  = pg_fetch_result($resSubmit, $y, "classificacao");

                    $tbody .= "$classificacao;$referencia - $descricao;$qtde;\n\r";


                }elseif($tipo_relatorio == "classificacao_solicitacao"){
                    
                    if($y == 0){
                        $thead = "Classificação;Pedido Cliente;Origem;Qtde de Atendimentos;\n\r";
                        fwrite($file, $thead);
                    }

                    $classificacao  = pg_fetch_result($resSubmit, $y, "classificacao");
                    $qtde           = pg_fetch_result($resSubmit, $y, "qtde");
                    $origem           = pg_fetch_result($resSubmit, $y, "origem");
                    $pedido_cliente           = pg_fetch_result($resSubmit, $y, "pedido_cliente");
                    if(strpos($pedido_cliente, "{") !==false) {
                        $valores_adicionais = json_decode($pedido_cliente,true);
                        $pedido_cliente = $valores_adicionais['pedido_cliente'];
                    }

                    $tbody .= "$classificacao;$pedido_cliente;$origem;$qtde;\n\r";
                 
                }
            }
            fwrite($file, $tbody);
             if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
        }
        exit;
    }
}

$layout_menu = "callcenter";
$title = "NOVOS RELATÓRIOS CALLCENTER";

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
    $.autocompleteLoad(Array("produto"));
    Shadowbox.init();
    $.dataTableLoad();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

});


function retorna_produto(retorno){
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);



}

function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,defeito_reclamado){
janela = window.open("callcenter_relatorio_defeito_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&defeito_reclamado="+defeito_reclamado, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
    janela.focus();
}

/* POP-UP IMPRIMIR */
    function abrir(URL) {
        var width = 700;
        var height = 600;
        var left = 90;
        var top = 90;

        window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
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
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

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
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
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
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status_atendimento'>Status do atendimento</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status_atendimento" id="status_atendimento">
                            <option value=""></option>
                            <option value="Cancelado"<? if($status_atendimento == "Cancelado"){echo " selected "; }?>>Cancelado</option>
                            <option value="Aberto" <? if($status_atendimento == "Aberto"){echo " selected "; }?>>Aberto</option>
                            <option value="Resolvido" <? if($status_atendimento == "Resolvido"){echo " selected "; }?>>Resolvido</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
   

    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group '>
            <h5 class='asteristico'>*</h5>
                <label class='control-label' for='status_atendimento'>Tipo Relatório</label>
                <div class='controls controls-row'>
                    <div class='span12'>

                        <div class='row-fluid'> 
                            <input type="radio" name="tipo_relatorio" value="atendete_perfil_solicitacao_realizacao" <? if($tipo_relatorio == "atendete_perfil_solicitacao_realizacao"){echo " checked "; }?>>
                            Atendente x Perfil do Cliente x Solicitação do cliente x Realização do Acordo <br>
                            
                            <input type="radio" name="tipo_relatorio" value="produto_classificacao"  <? if($tipo_relatorio == "produto_classificacao"){echo " checked "; }?>>
                            Produto x Classificação do atendimento <br>
                            
                            <input type="radio" name="tipo_relatorio" value="classificacao_solicitacao" <? if($tipo_relatorio == "classificacao_solicitacao"){echo " checked "; }?>>
                            Classificação do Atendimento x Solicitação do cliente x Origem <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<br />

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
<table id="callcenter_relatorio" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <TR class='titulo_coluna'>
            <?=$theader?>
        </TR >
    </thead>
    <tbody>
<?
        $grafico_conteudo = "";
        $count = pg_num_rows($resSubmit);
        $total_soma = 0;

        for($y=0; $y < $count; $y++){

            if($tipo_relatorio == "atendete_perfil_solicitacao_realizacao"){
                $numero_atendimento = pg_fetch_result($resSubmit, $y, "hd_chamado");
                $referencia         = pg_fetch_result($resSubmit, $y, "referencia");
                $nome               = pg_fetch_result($resSubmit, $y, "nome");
                $descricao          = pg_fetch_result($resSubmit, $y, "descricao");
                $perfil_cliente     = pg_fetch_result($resSubmit, $y, "perfil_cliente");
        $acordo_realizado   = pg_fetch_result($resSubmit, $y, "acordo_realizado");
        $perfil_cliente     = json_decode(str_replace("\\","",$perfil_cliente),true);
    ?>
            <TR bgcolor='$cor'>
                <TD class="tal"> <a href="callcenter_interativo_new.php?callcenter=<?=$numero_atendimento?>" target="_blank"> <?=$numero_atendimento ?> </a> </TD>
                <TD class="tal"><?=$nome ?></TD>
                <TD class="tal"><?=$referencia ." - ". $descricao?></TD>
                <TD class="tal"><?=$perfil_cliente['perfil_cliente'] ?></TD>
                <TD class="tal"><?=$perfil_cliente['pedido_cliente'] ?></TD>
                <TD class="tac"><?=$perfil_cliente['acordo_realizado']?></TD>
            </TR >
    <?
            }elseif($tipo_relatorio == "produto_classificacao"){
                $referencia     = pg_fetch_result($resSubmit, $y, "referencia");
                $descricao      = pg_fetch_result($resSubmit, $y, "descricao");
                $qtde           = pg_fetch_result($resSubmit, $y, "qtde");
                $classificacao  = pg_fetch_result($resSubmit, $y, "classificacao");

                ?>
             <TR bgcolor='$cor'>
                <TD class="tal"><?=$classificacao ?></TD>
                <TD class="tal"><?=$referencia ." - ". $descricao?></TD>
                <TD class="tal"><?=$qtde?></TD>
            </TR >
                <?


            }elseif($tipo_relatorio == "classificacao_solicitacao"){
                $classificacao  = pg_fetch_result($resSubmit, $y, "classificacao");
                $qtde           = pg_fetch_result($resSubmit, $y, "qtde");
                $origem           = pg_fetch_result($resSubmit, $y, "origem");
                $pedido_cliente           = pg_fetch_result($resSubmit, $y, "pedido_cliente");
                if(strpos($pedido_cliente, "{") !==false) {
                    $valores_adicionais = json_decode($pedido_cliente,true);
                    $pedido_cliente = $valores_adicionais['pedido_cliente'];
                }

                 ?>
                <TR bgcolor='$cor'>
                    <TD class="tal"><?=$classificacao ?></TD>
                    <TD class="tal"><?=$pedido_cliente?></TD>
                    <TD class="tal"><?=$origem?></TD>
                    <TD class="tal"><?=$qtde?></TD>
                </TR >
                    <?

            }

        }
?>
    

    </tbody>
</table>
<br>
<br>
<br>
        <?php
        if($count > 0){
            $jsonPOST = excelPostToJson($_POST);
        
        ?>

        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>
        <?php } ?>


<br /> <br />

<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

</div>

    

<?php
           
        }else{
            echo "<div class='container'>
            <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>";
        }
    }
?>

<p>

<? include "rodape.php" ?>
