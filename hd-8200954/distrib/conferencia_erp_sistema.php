<?php
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'cabecalho.php';
    include 'autentica_usuario.php';

    /* ====== Autencicação API ====== */
    $auth_token = "5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9";
    $headers = array(
        "Authorization-Token: $auth_token",
        "User: valeria@acaciaeletro.com.br",
        "App: AcaciaEletro",
        "Content-Type: application/json; charset=utf-8"
    );        

    $ip_maquina = include ("../nosso_ip.php");

    if (strlen($login_posto) == 0 && !in_array($login_posto,array("4311", "595", "20321","376542"))) {
        header ("Location: http://www.telecontrol.com.br");
        exit;        
    }

    if (isset($_GET['ajax']) && $_GET['ajax'] == 'sim') {

        if ($_GET['acao'] == 'consulta') {
            if (isset($_GET['excel']) && $_GET['excel'] == 'sim') {
                $excel = true;

                $data = date("d-m-Y-H:i");
                $fileName = "conferencia_erp-{$data}.csv";
                $file = fopen("/tmp/{$fileName}", "w");
                fwrite($file, utf8_encode("Referência_Distrib;Descrição_Distrib;NCM_Distrib;Unidade_Distrib;Estoque_Distrib;;Referência_ERP;Descrição_Distrib;NCM_ERP;Unidade_Distrib;Estoque_ERP \n"));
            }            

            if ($_GET['estoque'] == 1) {
                $where = ' AND estoque > 0';
            }

            $sql = "SELECT referencia, descricao, ncm, unidade, estoque
                    FROM tbl_peca
                    WHERE fabrica = ".$_GET['fabrica'].$where." ORDER BY descricao ";

            $res = pg_exec ($con,$sql);
            if(pg_numrows($res)==0) {
                exit(json_encode(array("nenhum" => utf8_encode("Não foi localizado nenhum produto em estoque!"))));
            }else{
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                $tabela  = "<h4>Distrib.</h4><table class='table table-bordered table-striped table-hover'>";
                $tabela .= "<thead>";
                $tabela .= "<th>Referência</th>";
                $tabela .= "<th>Descrição</th>";                
                $tabela .= "<th>NCM</th>";
                $tabela .= "<th>Unidade</th>";
                $tabela .= "<th>Estoque</th>";
                $tabela .= "</thead>";
                $tabela .= "<tbody>";

                $tabela2  = "<h4>ERP</h4><table class='table table-bordered table-striped table-hover'>";
                $tabela2 .= "<thead>";
                $tabela2 .= "<th>Referência</th>";
                $tabela2 .= "<th>Descrição</th>";                
                $tabela2 .= "<th>NCM</th>";
                $tabela2 .= "<th>Unidade</th>";
                $tabela2 .= "<th>Estoque</th>";
                $tabela2 .= "</thead>";
                $tabela2 .= "<tbody>";                    

                $contador = 0;
                for ($x = 0; $x < pg_numrows($res); $x++) {
                    
                    $referencia = pg_fetch_result($res, $x, "referencia");
                    $descricao = pg_fetch_result($res, $x, "descricao");
                    $ncm = pg_fetch_result($res, $x, "NCM");
                    $unidade = pg_fetch_result($res, $x, "unidade");
                    if (pg_fetch_result($res, $x, "estoque") == "") {
                        $estoque = 0;
                    }else{
                        $estoque = pg_fetch_result($res, $x, "estoque");
                    }                        

                    $tabela_aux  = "<tr>";
                    $tabela_aux .= "<td>".$referencia."</td>";
                    $tabela_aux .= "<td style='line-height: 14px;'>".strtoupper($descricao)."</td>";
                    $tabela_aux .= "<td>".$ncm."</td>";
                    $tabela_aux .= "<td>".$unidade."</td>";
                    $tabela_aux .= "<td>".$estoque."</td>";
                    $tabela_aux .= "</tr>";

                    $uri = 'http://api.sigecloud.com.br/request/produtos/get?codigo='.$referencia;

                    curl_setopt($ch, CURLOPT_URL, $uri);
                    $response_json = curl_exec($ch);
                    $result = json_decode($response_json, true);

                    if (is_array($result)){
                        $tabela2_aux  = "<tr>";
                        $tabela2_aux .= "<td>".$result['Codigo']."</td>";
                        $tabela2_aux .= "<td style='line-height: 14px;'>".$result['Nome']."</td>";

                        if ($result['Estoque'] == "") {
                            $estoque_erp = 0;
                        }else{
                            $estoque_erp = $result['Estoque'];
                        }

                        $alteracao = 0;
                        if($ncm !== $result['NCM']){
                            $tabela2_aux .= "<td>".$result['NCM']."</td>";
                            $alteracao = 1;
                        }else{
                            $tabela2_aux .= "<td>".$result['NCM']."</td>";
                        }
                        $tabela2_aux .= "<td>".$result['UnidadeTributavel']."</td>";

                        if ($estoque !== 0 && $estoque_erp !== 0) {
                            if ($estoque !== $estoque_erp) {
                                $tabela2_aux .= "<td>".$estoque_erp."</td>";
                                $alteracao = 1;
                            }
                        }else{
                            $tabela2_aux .= "<td>".$estoque_erp."</td>";
                        }
                        $tabela2_aux .= "</tr>";
                        if ($alteracao == 0) {
                            $tabela2_aux = "";
                        }
                    }else{
                        $lineHeight = 28;
                        if (strlen($descricao) <= 19) {
                            $lineHeight = 20;
                        }
                        $tabela2_aux  = "<tr>";
                        $tabela2_aux .= "<td>".$referencia."</td>";
                        $tabela2_aux .= "<td style='line-height: {$lineHeight}px;'>Não encontrado</td>";
                        $tabela2_aux .= "<td></td>";
                        $tabela2_aux .= "<td></td>";
                        $tabela2_aux .= "<td></td>";
                        $tabela2_aux .= "</tr>";
                    }
                    if ($tabela2_aux !== "") {
                        if ($excel) {
                            fwrite($file,"$referencia;$descricao;$ncm;$unidade;$estoque;;".$result['Codigo'].";".$result['Nome'].";".$result['NCM'].";".$result['UnidadeTributavel'].";".$result['Estoque']." \n");
                        }else{
                            $contador = $contador + 1;
                            $tabela .= $tabela_aux;
                            $tabela2 .= $tabela2_aux;
                        }                            
                    }
                }
                if ($excel) {
                    fclose($file);
                    if (file_exists("/tmp/{$fileName}")) {
                        system("mv /tmp/{$fileName} ../admin/xls/{$fileName}");
                        exit(json_encode(array("ok" => utf8_encode("../admin/xls/{$fileName}"))));
                    }else{
                        exit(json_encode(array("erro" => utf8_encode("Erro: Arquivo CSV não gerado!"))));
                    }
                }else{
                    $tabela  .= "</tbody></table>";
                    $tabela2 .= "</tbody></table>";
                    exit(json_encode(array("ok" => utf8_encode($tabela), "tab" => utf8_encode($tabela2), "ref" => utf8_encode(substr($referencia,1)), "qtd" => $contador)));
                }
            }            
        }

        if ($_GET['acao'] == "lista-fabrica") {
            $sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica IN (".implode(",", $fabricas).") ORDER BY nome";

            $res = pg_exec ($con,$sql);
            if(pg_numrows($res) !== 0) {
                $option = "<option value='0'></option>";
                for ($x = 0; $x < pg_numrows($res); $x++) {
                    $option .= "<option value='".pg_fetch_result($res, $x, "fabrica")."'>".pg_fetch_result($res, $x, "nome")."</option>";
                }
                exit(json_encode(array("ok" => utf8_encode($option))));
            }
        }
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
        <title>Conferência ERP</title>
        <link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    </head>
    <body>
        <? include 'menu.php'; ?>
        <h1> Conferência Estoque x ERP </h1>
        <div id="qtdTotal" class="alert alert-success" style="display: none;"><h4></h4></div>
        <div id="noRegistro" class="alert alert-warning" style="display: none;"><h4></h4></div>
        <div class="container">
            <div class="row">
                <div class="well span8 offset2">
                    <div class="row">
                        <div class="span2">
                            <label>Fabrica:</label>
                        </div>
                        <div class="span3">
                            <select id="lista-fabrica">
                                <option value="0"></option>
                            </select>
                        </div>
                        <div class="span3">
                            <label>
                                <input type="checkbox" name="estoque" id="estoque"> Produtos em estoque?<br>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="span6 offset3">
                    <button type="button" class="btn btn-primary" name="pesquisa" id="btn-pesquisar">Consultar</button>
                    <button type="button" class="btn btn-success" name="excel" id="btn-excel"><img src='../imagens/excell.gif'>  Excel (CSV)</button>
                    <img id="loader" src="35.gif" style="width: 30px; display: none;">                   
                </div>
            </div>
            <br>
            <div class="row">
                <div class="span6">
                    <div id="tabela"></div>
                </div>
                <div class="span6">
                    <div id="tabela2"></div>
                </div>                
            </div>
        </div>
        <br>
    </body>
    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="../bootstrap/js/bootstrap.js"></script>
    <script type="text/javascript">
        $(function(){
            $.ajax({
                method: "GET",
                url: "conferencia_erp_sistema.php",
                data: {ajax: "sim", acao: "lista-fabrica"},
                timeout: 5000
            }).fail(function(){
                $('#noRegistro').show().find('h4').html("Não foi possível listar as fabricas, tempo esgotado! Recarregue a pagina...");
            }).done(function(data){
                data = JSON.parse(data);
                if (data.ok !== undefined) {
                    $('#lista-fabrica').html(data.ok);
                }                                
            });
            $('#estoque').prop("checked", false);                        
        });

        $('#btn-pesquisar').on('click', function(){
            if ($('#lista-fabrica').val() !== '0') {
                $('#btn-pesquisar').prop("disabled", true);
                $('#btn-excel').prop("disabled", true);
                $('#loader').show();
                CleanResult();

                if($('#estoque').is(":checked") == true){
                    var estoque = 1;    
                }else{
                    var estoque = 0;
                }

                $.ajax({
                    method: "GET",
                    url: "conferencia_erp_sistema.php",
                    data: {ajax: "sim", acao: "consulta", fabrica: $('#lista-fabrica').val(), estoque: estoque}
                }).fail(function(){
                    $('#noRegistro').show().find('h4').html("Não foi possível listar os produtos, tempo esgotado!");
                    $('#btn-pesquisar').prop("disabled", false);
                    $('#btn-excel').prop("disabled", false);
                    $('#loader').hide();
                }).done(function(data){
                    data = JSON.parse(data);
                    if (data.ok !== undefined) {
                        $('#tabela').html(data.ok);
                        $('#tabela2').html(data.tab);
                        $('#btn-pesquisar').prop("disabled", false);
                        $('#btn-excel').prop("disabled", false);
                        $('#qtdTotal').show().find('h4').html("Foram identificados "+data.qtd+" registros que precisam ser atualizados...");
                    }
                    if (data.nenhum !== undefined) {
                        CleanResult();
                        $('#noRegistro').show().find('h4').html("Não foi encontrado nenhum registro!");
                        $('#btn-pesquisar').prop("disabled", false);
                        $('#btn-excel').prop("disabled", false);                        
                    }
                    $('#loader').hide();
                });
            }else{
                CleanResult();
                alert("Selecione a fabrica para consulta!");
            }
        });

        $('#btn-excel').on('click', function(){            
            if ($('#lista-fabrica').val() !== '0') {
                $('#loader').show();
                $('#btn-pesquisar').prop("disabled", true);
                $('#btn-excel').prop("disabled", true);            
                if($('#estoque').is(":checked") == true){
                    var estoque = 1;    
                }else{
                    var estoque = 0;
                }

                $.ajax({
                    method: "GET",
                    url: "conferencia_erp_sistema.php",
                    data: {ajax: "sim", acao: "consulta", excel: 'sim', fabrica: $('#lista-fabrica').val(), estoque: estoque}
                }).fail(function(){
                    $('#noRegistro').show().find('h4').html("Não foi possível gerar o arquivo CSV, tempo esgotado!");
                    $('#loader').hide();
                    $('#btn-pesquisar').prop("disabled", false);
                    $('#btn-excel').prop("disabled", false);
                }).done(function(data){
                    data = JSON.parse(data);                
                    if (data.ok !== undefined) {
                        window.open(data.ok);
                    }else{
                        $('#noRegistro').show().find('h4').html(data.erro);
                    }
                    $('#btn-pesquisar').prop("disabled", false);
                    $('#btn-excel').prop("disabled", false);                
                    $('#loader').hide();
                });
            }else{
                alert("Selecione a fabrica para consulta!");
            }
        });

        function CleanResult(){
            $('#tabela').html("");
            $('#tabela2').html("");
            $('#qtdTotal').hide().find('h4').html("");
            $('#noRegistro').hide().find('h4').html("");
        }

    </script>
</html>
<? include'rodape.php'; ?>
