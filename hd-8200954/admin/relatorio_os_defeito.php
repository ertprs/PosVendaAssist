<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO OS - DEFEITOS";


if($_GET['peca']){
    
    $os = $_GET['os'];
    if($os == ""){
        echo json_encode(array("exception" => "Informe a Ordem de Serviço"));
        exit;
    }

    $sql = "SELECT  tbl_os_item.qtde, tbl_os_item.digitacao_item,
                    tbl_peca.referencia || ' - '|| tbl_peca.descricao AS peca
            FROM tbl_os 
            LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
            LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
            LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.os = $os AND tbl_os_item.os_item IS NOT NULL;";
            
    $res = pg_query($con,$sql);

    if(pg_last_error()){
        echo json_encode(array("exception" => pg_last_error()));   
        exit;
    }


    $resultado = pg_fetch_all($res);

    if(count($resultado) == 0 || $resultado == false){
        echo json_encode(array("exception" => utf8_encode("Nenhuma peça lançada para essa OS")));
        exit;   
    }

    foreach ($resultado as $key => $value) {
        $resultado[$key]['digitacao_item'] = date("d-m-Y H:i:s",strtotime($value['digitacao_item']));
        $resultado[$key]['peca'] = utf8_encode($value['peca']);
    }

    echo json_encode($resultado);    
    exit;
}

if(count($_POST)>0){

    $data_val = explode("/",$data_inicial);
    if(!checkdate($data_val[1],$data_val[0],$data_val[2])){
        $msg_erro['msg'][] = "Informe uma data inicial válida";
    }

    $data_val = explode("/",$data_final);
    if(!checkdate($data_val[1],$data_val[0],$data_val[2])){
        $msg_erro['msg'][] = "Informe uma data final válida";
    }



    // if($data_inicial_validate == false ){
    //     $msg_erro['msg'][] = "Informe uma data inicial válida";
    // }
    // if($data_final_validate == false){
    //     $msg_erro['msg'][] = "Informe uma data final válida";
    // }



    $sql = "SELECT '$data_inicial'::date + interval '3 months' > '$data_final'";
    
    $res = pg_query($con,$sql);
    $periodo_6meses = pg_fetch_result($res,0,0);
    if($periodo_6meses == 'f'){
        $msg_erro['msg'][] = "As datas devem ter um intervalo de no máximo 3 meses";
    }


    

    if(strlen(trim($serie))>0){
        $where .= " AND tbl_os.serie = '$serie' ";
    }
    if(strlen(trim($produto_referencia))>0){
        $where .= " AND tbl_produto.referencia = '$produto_referencia' ";
    }
    if(strlen(trim($peca_referencia))>0){
        $where .= " AND tbl_peca.referencia = '$peca_referencia' ";
    }
    if(strlen(trim($defeito_constatado))>0){
        $where .= " AND tbl_os.defeito_constatado = $defeito_constatado ";
    }
    if(strlen(trim($defeito_reclamado))>0){
        $where .= " AND tbl_os.defeito_reclamado = $defeito_reclamado ";
    }

    switch ($tipo_data) {
        case 'data_abertura':
            $data_campo = 'tbl_os.data_abertura';
            break;
        case 'data_fechamento':
            $data_campo = 'tbl_os.data_fechamento';
            break;
        case 'data_lancamento_peca':
            $data_campo = 'tbl_os_item.digitacao_item';
            break;
    }
   
    $sql = "SELECT DISTINCT tbl_os.os, tbl_os.data_abertura, tbl_os.data_fechamento, tbl_os.serie, tbl_os.produto, 
                   tbl_produto.referencia||' - '||tbl_produto.descricao as produto,
                   tbl_defeito_constatado.descricao as defeito_constatado,
                   tbl_defeito_reclamado.descricao as defeito_reclamado
            FROM tbl_os 
            LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
            LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
            LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
            LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
            WHERE tbl_os.fabrica = $login_fabrica
            $where  
            AND $data_campo BETWEEN '2016-01-01 00:00:00' AND '2016-03-29 23:59:59';";


    if(count($msg_erro['msg']) == 0){
        $res = pg_query($con,$sql);
        if(pg_last_error()){
            $msg_erro['msg'] = "Ocorreu um erro ao executar a consulta";
        }
        $relatorio = pg_fetch_all($res);
        if($relatorio == false){
            $relatorio = array();
        }
    }else{
        $relatorio = array();
    }
}else{
    $relatorio = null;
}


if($_POST['excel']){
    

    if(count($relatorio) == 0){
        echo "NODATA";
        exit;
    }

    $data = date("d-m-Y-H:i");
    $arquivo_nome = "relatorio-os-defeito-$data.xls";
    $path = "xls/";
    $path_tmp = "/tmp/";
    $arquivo_completo = $path.$arquivo_nome;
    $arquivo_completo_tmp = $path_tmp.$arquivo_nome;
    $fp = fopen($arquivo_completo_tmp,"w");


    $thead = "<table border='1'>
            <thead>
            <tr>                
                <th><b> OS  </b></th>
                <th><b> Data de Abertura </b></th>
                <th><b> Data de Encerramento </b> </th>
                <th><b> Número de Série </b> </th>
                <th><b> Produto </b> </th>
                <th><b> Defeito Reclamado </b> </th>
                <th><b> Defeito Constatado </b> </th>                                
            </tr>
            </thead>
            <tbody>";

    fwrite($fp,$thead);



    foreach ($relatorio as $key => $value) {
        if($value['data_fechamento'] != ""){ 
            $data_aux = date("d-m-Y",strtotime($value['data_fechamento'])); 
        }
        $tr ='<tr>                
                <td class="tac os-data" style="vertical-align: middle;">'.$value['os'].'</td>
                <td class="tac" style="vertical-align: middle;">'.date("d-m-Y",strtotime($value['data_abertura'])).'</td>
                <td class="tac" style="vertical-align: middle;">'. $data_aux.'</td>
                <td class="tac" style="vertical-align: middle;">'.$value['serie'].'</td>
                <td class="tac" style="vertical-align: middle;">'.$value['produto'].'</td>
                <td style="vertical-align: middle;">'.$value['defeito_reclamado'].'</td>
                <td style="vertical-align: middle;">'.$value['defeito_constatado'].'</td>
            </tr>';
        fwrite($fp,$tr);
//------- ITEM            
        $sql = "SELECT  tbl_os_item.qtde, tbl_os_item.digitacao_item,
                tbl_peca.referencia || ' - '|| tbl_peca.descricao AS peca
        FROM tbl_os 
        LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
        LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
        LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
        LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
        WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.os = ".$value['os']." AND tbl_os_item.os_item IS NOT NULL;";
        $res = pg_query($con,$sql);

        if(pg_last_error()){
            continue;
        }

         $resultado = pg_fetch_all($res);

        if(count($resultado) == 0 || $resultado == false){
            continue;
        }
        if(count($resultado) > 0){
            foreach ($resultado as $key => $value) {
                $resultado[$key]['digitacao_item'] = date("d-m-Y H:i:s",strtotime($value['digitacao_item']));
                $resultado[$key]['peca'] = utf8_encode($value['peca']);

                $tr = '<tr><td colspan="5">'.$resultado[$key]['peca'].'</td><td>'.$resultado[$key]['qtde'].'</td><td>'.$resultado[$key]['digitacao_item'].'</td></tr>';
                fwrite($fp,$tr);
            }
        }
//---------
    }


    $rodape = '</tbody>
    </table>';

    fwrite($fp,$rodape);




    fclose($fp);
    if (file_exists($arquivo_completo_tmp)) {
        system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
        echo $arquivo_completo;
    }

    exit;
}




//--------------- Configurações --------------------
require_once("cabecalho_new.php");

$plugins = array(
    "mask",
    "datepicker",    
    "autocomplete",
    "shadowbox",
    "multiselect"
);

include "plugin_loader.php";
?>

<style type="text/css">
    
    .subline td{
        background: #A9C4FF !important;
    }

    .subline:hover td{
        background-color: #A9C4FF !important;   
        background: #A9C4FF !important;
    }

    .table-hover tbody .subline:hover td, .table-hover tbody .subline:hover th{
        background-color: #A9C4FF !important;   
        background: #A9C4FF !important;   
    }
</style>

<script>
    function geraExcel(){
        var data = new Object()
        $.each($(document.frm_defeito).find("input"),function(idx, elem){

            $(data).attr($(elem).attr("name"),$(elem).val());            
        });

        data.tipo_data = $("input[name=tipo_data]:checked").val();
        data.excel = true;

        console.log(data);
        $.ajax("relatorio_os_defeito.php",{
            method: "POST",
            data: data
        }).done(function(response){
            if(response == 'NODATA'){
                alert("Nenhuma pesquisa foi respondida ainda");
            }else{
                //console.log(response);
                window.location = "./"+response;
            }
        });

        
    }

    function retorna_peca(retorno){        
        $("#peca_referencia_0").val(retorno.referencia);
        $("#peca_descricao_0").val(retorno.descricao);
    }

    $(function() {
        $.datepickerLoad(["data_ini", "data_fim"]);

        Shadowbox.init();
        
        $(document).on("click", "span[rel=lupa]", function () {
            $.lupa($(this),Array('posicao'), "");
        });

        $(".expand-info").click(function(){
            var icon = $(this);

            var tr = $(this).parents("tr");
            var os = $(tr).find(".os-data").html();

            if($(tr).next().hasClass("subline") == true){
                $(icon).addClass('icon-plus');
                $(icon).removeClass('icon-minus');

                $("."+os).remove();

            }else{
                $(icon).addClass('icon-refresh');
                $(icon).removeClass('icon-plus');       

                $.ajax("relatorio_os_defeito.php",{
                    method: "GET",
                    data:{
                        os: os,
                        peca: true
                    }
                }).done(function(response){
                    response = JSON.parse(response);
                    
                    if(response.exception == undefined){
                        $.each(response,function(idx,elem){
                            var traux = $('<tr class="subline-info '+os+'">');
                            var td = $("<td colspan='6'>"+elem.peca+"</td>");
                            $(traux).append(td);
                            var td = $("<td>"+elem.qtde+"</td>");
                            $(traux).append(td);
                            var td = $("<td>"+elem.digitacao_item+"</td>");
                            $(traux).append(td);

                            $(tr).after($(traux));             
                        });
                        
                        $(tr).after('<tr class="subline '+os+'"><td class="tac" colspan="6">Peça</td><td>Quantidade</td><td>Data de Lançamento</td></tr>');                                       
                    }else{
                        $(tr).after('<tr class="subline '+os+'"><td class="tac" colspan="8">'+response.exception+'</td></tr>');            
                    }

                    $(icon).addClass('icon-minus');
                    $(icon).removeClass('icon-refresh');       
                });
            }
               /*

               <tr class="subline">
                    <td colspan="6" >Peça</td>                    
                    <td>Quantidade</td>                    
                    <td>Data de Lançamento</td>                    
                </tr>
                <tr>
                    <td colspan="6">Perçasd </td>
                    <td>123</td>
                    <td>10-10-1000</td>
                </tr>

                */ 

        });
    });

    function retorna_produto (retorno) {
        $("#produto").val(retorno.produto);
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }


    function submitForm(form){
        console.log(form);

        var keep = true;
        $.each($(".required"),function(idx,elem){
            if($(elem).val() == ""){

                $(elem).parents(".control-group").addClass("error");
                keep = false;
            }
        });        
        if(keep == false){
            return false;
        }

        $("form[name='frm_defeito']").submit();
    }


</script>

<!-- FORM NOVO -->

<div class="container">
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

    <form name='frm_defeito' MEthOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>

        <br />

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='serie'>Número de Série</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>                            
                            <input type="text" id="serie" name="serie" class='span12' value="<?=$serie?>">
                        </div>
                    </div>
                </div>
            </div>            
            <div class='span2'></div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="data_ini" name="data_inicial" class='span12 required' maxlength="20" value="<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="data_fim" name="data_final" class='span12 required' value="<?=$data_final?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <div class='row-fluid' style="min-height: 30px !important">
            <div class='span2'></div>
            <div class='span8'>
                Data de Referência
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span2'>
                <label class="radio">
                    <input type="radio" name="tipo_data" id="optionsRadios1" value="data_abertura" <?if($tipo_data=="data_abertura" or $tipo_data=="") echo "checked";?>>
                    Abertura da OS
                </label>
            </div>
            <div class='span3'>
                <label class="radio">
                    <input type="radio" name="tipo_data" id="optionsRadios1" value="data_fechamento" <?if($tipo_data=="data_fechamento") echo "checked";?>>
                    Fechamento da OS
                </label>
            </div>
            <div class='span3'>
                    <label class="radio">
                    <input type="radio" name="tipo_data" id="optionsRadios1" value="data_lancamento_peca" <?if($tipo_data=="data_lancamento_peca") echo "checked";?> >
                    Lançamento da Peça
                </label>
            </div>
            <div class='span2'></div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='produto_referencia'>Referência do Produto</label>
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
                <div class='control-group'>
                    <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
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
                <div class='control-group'>
                    <label class='control-label' for='peca_referencia'>Referência da Peça</label>
                    <div class='controls controls-row'>                        
                        <div class='span10 input-append'>                            
                            <input type="text" id="peca_referencia_0" name="peca_referencia" class='span12' value="<?=$peca_referencia_0?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="0" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class="control-group">
                    <label class='control-label' for='peca_descricao'>Descrição da Peça</label>
                    <div class='controls controls-row'>                        
                        <div class='span10 input-append'>                            
                            <input type="text" id="peca_descricao_0" name="peca_descricao" class='span12' value="<?=$peca_descricao_0?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="0" parametro="descricao"/>                            
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
                    <label class='control-label' for='defeito_constatado'>Defeito Constatado</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <select class="span12" name="defeito_constatado" id="defeito_constatado">                                                              
                                <option value=""   <? if (strlen($defeito_constatado) == 0)    echo " selected "; ?>></option>
                                <?php

                                $sql = "select defeito_constatado,descricao from tbl_defeito_constatado where fabrica = $login_fabrica;";
                                $res = pg_query($con,$sql);
                                
                                if(pg_num_rows($res)>0){
                                    foreach (pg_fetch_all($res) as $key => $value) {
                                        if($value['defeito_constatado'] == $defeito_constatado){
                                            $selected = "selected";
                                        }else{
                                            $selected = "";
                                        }
                                        ?><option <?=$selected?> value="<?=$value['defeito_constatado']?>"><?=$value['descricao']?></option><?php
                                        
                                    }
                                }
                                ?>                                
                                <!-- <option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option> -->                                                                
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='defeito_reclamado'>Defeito Reclamado</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <select class="span12" name="defeito_reclamado" id="defeito_reclamado">
                                <option value=""   <? if (strlen($defeito_reclamado) == 0)    echo " selected "; ?>></option>
                                <?php

                                $sql = "select defeito_reclamado,descricao from tbl_defeito_reclamado where fabrica = $login_fabrica;";
                                $res = pg_query($con,$sql);
                                
                                if(pg_num_rows($res)>0){
                                    foreach (pg_fetch_all($res) as $key => $value) {
                                         if($value['defeito_reclamado'] == $defeito_reclamado){
                                            $selected = "selected";
                                        }else{
                                            $selected = "";
                                        }
                                        ?><option <?=$selected?> value="<?=$value['defeito_reclamado']?>"><?=$value['descricao']?></option><?php
                                        
                                    }
                                }                                

                                ?>                                
                                <!-- <option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option> -->                                                                
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>        

        <p>
            <br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'pesquisar');">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>

        <br/>

    </form>


    <?php
    if($relatorio != null){
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed'name='relatorio' id='relatorio'>
        <thead>
            <tr class='titulo_tabela'>
            <td colspan='8'>
                <center>
                    <h4>Resultado de pesquisa entre os dias <?=$data_inicial?> e <?=$data_final?></h4>
                </center>
            </td>
            </tr>
            <tr class='titulo_coluna'>
                <th height='15' style="vertical-align: middle;"><b> <i class="icon-zoom-in icon-white"></i> </b></th>
                <th height='15' style="vertical-align: middle;"><b> OS  </b></th>
                <th width="50" height='15' style="vertical-align: middle;"><b> Data de Abertura </b></th>
                <th width="50" height='15' style="vertical-align: middle;"><b> Data de Encerramento </b> </th>
                <th width="50" height='15' style="vertical-align: middle;"><b> Número de Série </b> </th>
                <th width="200" height='15' style="vertical-align: middle;"><b> Produto </b> </th>
                <th width="100" height='15' style="vertical-align: middle;"><b> Defeito Reclamado </b> </th>
                <th width="100" height='15' style="vertical-align: middle;"><b> Defeito Constatado </b> </th>                                
            </tr>
        </thead>
        <tbody>
            <?php
            $a = 1;
            $qtdRegistros = 0;
            foreach ($relatorio as $key => $value) {
                $qtdRegistros += 1;
            ?>
                <tr>
                    <td class="tac" style="vertical-align: middle;"><i class="icon-plus expand-info" style="cursor: pointer;"></i></td>
                    <td class="tac" style="vertical-align: middle;"><a class="os-data" target="_BLANK" href="os_press.php?os=<?=$value['os']?>"><?=$value['os']?></a></td>
                    <td class="tac" style="vertical-align: middle;"><?=date("d-m-Y",strtotime($value['data_abertura']))?></td>
                    <td class="tac" style="vertical-align: middle;"><?php if($value['data_fechamento'] != ""){ echo date("d-m-Y",strtotime($value['data_fechamento'])); }?></td>
                    <td class="tac" style="vertical-align: middle;"><?=$value['serie']?></td>
                    <td class="tac" style="vertical-align: middle;"><?=$value['produto']?></td>
                    <td style="vertical-align: middle;"><?=$value['defeito_reclamado']?></td>
                    <td style="vertical-align: middle;"><?=$value['defeito_constatado']?></td>
                </tr>           
            <?php
            }
            ?>
            <tr>
                <td colspan="8" style="text-align: right"><b><?=$qtdRegistros?></b> Registro(s)</td>
            </tr>            
        </tbody>
    </table>    

    <div class='btn_excel'>
        <span><img src='imagens/excel.png' /></span>
        <span class='txt' onclick='javascript: geraExcel()'; >Gerar Arquivo Excel</span>
    </div>

    <?php
    }
    ?>
</div>

<? include "rodape.php" ?>
