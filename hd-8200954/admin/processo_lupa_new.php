<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}
if (count($_REQUEST["parametro"])>0) {

    $tipo_pesquisa      = $_REQUEST["parametro"];
    $tipo               = trim($tipo_pesquisa);
    $descricao_pesquisa = utf8_decode(trim($_REQUEST["valor"]));
}
if (count($_REQUEST['tipo_pesquisa'])) {
    $tipo_pesquisa      = $_REQUEST['tipo_pesquisa'];
    $tipo               = trim($tipo_pesquisa);
    $descricao_pesquisa = $_REQUEST['descricao_pesquisa'];
    $data_inicial       = $_REQUEST["data_inicial"];
    $data_final         = $_REQUEST["data_final"];
}

    if(empty($descricao_pesquisa)){
        $descricao_pesquisa = $_GET['q'];
    }


    if($tipo_pesquisa == "cpf"){
        $tipo_pesquisa = strlen(preg_replace("/[^0-9]/", "", $descricao_pesquisa)) == 14 ? "cnpj" : "cpf" ;
    }

    if ($_REQUEST["exata"] == 'sim') {
        $busca_exata = true;
    } else {
        $busca_exata = false;
    }

    if ($_REQUEST["ajax"]) {
        $localizar = trim($_GET["q"]);
        $localizar_numeros = preg_replace( '/[^0-9]/', '', $localizar);
        $resultados = "LIMIT 5";
        $busca_produtos = false;
        $ajax = true;
    } else {
        $ajax = false;
        $localizar = trim($_GET["localizar"]);
        $localizar_numeros = preg_replace( '/[^0-9]/', '', $localizar);
        $busca_produtos = true;
    }

    function verificaValorCampo($campo){
        return strlen($campo) > 0 ? $campo : "&nbsp;";
    }

    function verificaDataValida($data){
        if(!empty($data)){
            list($di, $mi, $yi) = explode("/", $data);

            return checkdate($mi,$di,$yi) ? true : false;
        }

        return false;
    }

    function subtraiData($data, $dias = 0, $meses = 0, $ano = 0){
        $data = explode("/", $data);
        $newData = date("d/m/Y", mktime(0, 0, 0, $data[1] - $meses,
        $data[0] - $dias, $data[2] - $ano) );

        return $newData;
    }

    function somaData($data, $dias = 0, $meses = 0, $ano = 0){
        $data = explode("/", $data);
        $newData = date("d/m/Y", mktime(0, 0, 0, $data[1] + $meses,
        $data[0] + $dias, $data[2] + $ano) );

        return $newData;
    }

    $lista_tipo_pesquisa = array(
        "cpf"           => "CPF",
        "cnpj"          => "CNPJ",
        "nome"          => "Nome",
        "os"            => "OS",
        "atendimento"   => "Atendimento",
        "serie"         => "Nº de Série",
        "cep"           => "CEP",
        "telefone"      => "Telefone"
    );

    if(empty($data_inicial) OR !verificaDataValida($data_inicial)){
        $data_inicial       = subtraiData(Date('d/m/Y'),0,0,1);
        $data_final         = Date('d/m/Y');
    }
    $aux_data_inicial   = implode("-", array_reverse(explode("/", $data_inicial)));

    if(empty($data_final) OR !verificaDataValida($data_final)){
        $data_final = somaData($data_inicial,0,0,1);
    }
    $aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));

    if(strtotime($aux_data_inicial) > strtotime($aux_data_final)){
        $data_inicial       = subtraiData(Date('d/m/Y'),0,0,2);
        $data_final         = Date('d/m/Y');
    }

    if (strtotime($aux_data_inicial.' - 24 month') < strtotime($aux_data_final) ) {
        $data_final         =   somaData($data_inicial,0,0,2);
        $aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));
    }

    $aux_data_inicial   = implode("-", array_reverse(explode("/", $data_inicial)));
    $aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));

    if(!$ajax){?>
        <!DOCTYPE html>
        <html>
            <head>
            <meta charset="iso-8859-1">
             <link rel="stylesheet" href="../plugins/jquery/datepick/telecontrol.datepick.css" media="screen, projection, print">
             <link rel="stylesheet" href="../css/lupas/lupas.css" media="screen, projection, print">

            <style media="screen, projection, print">

                body {
                    margin: 0;
                    font-family: Arial, Verdana, Times, Sans;
                    background: #fff;
                }

                .sematendimento {
                    font-size: 7pt;
                    font-weight: bold;
                    background-color: #CC5555;
                    color: #FFFFFF;
                    text-align: center;
                    padding: 0;
                    margin: 0;
                }

                .semos {
                    font-size: 7pt;
                    font-weight: bold;
                    background-color: #CC5555;
                    color: #FFFFFF;
                    text-align: center;
                    padding: 0;
                    margin: 0;
                }

                .right{
                    float: right;
                }

                .lp_tabela td{
                    cursor: default;
                }
            </style>

            <script type="text/javascript" src="../js/jquery-1.8.3.min.js"></script>
            <script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
            <script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
            <script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
            <script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
            <script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
            <script src="plugins/shadowbox_lupa/lupa.js"></script>
            <script type='text/javascript'>
                //função para fechar a janela caso a telca ESC seja pressionada!
                $(window).keypress(function(e) {
                    if(e.keyCode == 27) {
                         window.parent.Shadowbox.close();
                    }
                });

                $(document).ready(function() {
                    $("#gridRelatorio").tablesorter();

                    $("#tipo_pesquisa").change(function(){
                        var pesquisa = $("#tipo_pesquisa").val();
                        $("#descricao_pesquisa").val('');
                        $("#descricao_pesquisa").removeClass();
                        $("#descricao_pesquisa").unmask();
                        tipo_pesquisa(pesquisa);

                        if ($(this).val() == "atendimento") {
                            $(".date").hide().prev("label").hide();
                        } else {
                            $(".date").show().prev("label").show();
                        }
                    });

                    $(".sematendimento").parent().addClass('sematendimento');
                    $(".semos").parent().addClass('semos');

                    $(".aviso").click(function(){
                        $(this).fadeOut(500);
                    });

                    <?php
                    if ($tipo_pesquisa == "atendimento") {
                    ?>
                        $(".date").hide().prev("label").hide();
                    <?php
                    }
                    ?>
                });

                function maskara(){
                    $('.date').datepick({startDate:'01/01/2000'});
                    $(".date").maskedinput("99/99/9999");

                    $(".cep").maskedinput("99999-999");
                    $(".telefone").maskedinput("(99) 9999-9999");
                    $(".cpf").maskedinput("999.999.999-99");
                    $(".cnpj").maskedinput("99.999.999/9999-99");
                    $('.atendimento').numeric();

                    $(".nome").unmask();
                    $(".nome").unbind("keypress");
                }

                function tipo_pesquisa(pesquisa){
                    switch(pesquisa){
                        case 'cpf':
                            $("#descricao_pesquisa").addClass("cpf");
                            $("#label_descricao_pesquisa span").html(" CPF");
                        break;

                        case 'cnpj':
                            $("#descricao_pesquisa").addClass("cnpj");
                            $("#label_descricao_pesquisa span").html(" CNPJ");
                        break;

                        case 'nome':
                            $("#descricao_pesquisa").addClass("nome");
                            $("#label_descricao_pesquisa span").html(" Nome");
                        break;

                        case 'atendimento':
                            $("#descricao_pesquisa").addClass("atendimento");
                            $("#label_descricao_pesquisa span").html(" Atendimento");
                        break;

                        case 'serie':
                            $("#descricao_pesquisa").addClass("serie");
                            $("#label_descricao_pesquisa span").html(" Numero de Série");
                        break;

                        case 'cep':
                            $("#descricao_pesquisa").addClass("cep");
                            $("#label_descricao_pesquisa span").html(" CEP");
                        break;

                        case 'telefone':
                            $("#descricao_pesquisa").addClass("telefone");
                            $("#label_descricao_pesquisa span").html(" Telefone");
                        break;

                        default:
                            $("#descricao_pesquisa").addClass("");
                            $("#label_descricao_pesquisa span").html("");
                    }
                    maskara();
                }

                $(window).load(function () {
                    tipo_pesquisa($("#tipo_pesquisa").val());
                });

                //Esta função busca os dados da matriz de array consumidores e retorna para a janela principal
                //Este array Ã© alimentado por um código gerado em PHP neste mesmo programa
                function retorna_dados_cliente(cliente) {
                    var formulario = window.parent.document.frm_pesquisa_cadastro;
                    
                    formulario.cli_nome.value               = consumidores[cliente]['nome'];
                    formulario.consumidor_cpf.value         = consumidores[cliente]['cpf_cnpj'];
                    formulario.cli_email.value              = consumidores[cliente]['email'];
                    formulario.cli_tel_fix.value            = consumidores[cliente]['fone'];
                    formulario.cli_cep.value                = consumidores[cliente]['cep'];
                    formulario.cli_endereco.value           = consumidores[cliente]['endereco'];
                    formulario.cli_numero.value             = consumidores[cliente]['numero'];
                    formulario.cli_end_complemento.value    = consumidores[cliente]['complemento'];
                    formulario.cli_bairro.value             = consumidores[cliente]['bairro'];
                    
                    formulario.cli_estado.value             = consumidores[cliente]['estado'];
                    formulario.cli_cidade.value             = consumidores[cliente]['nome_cidade'];
                    formulario.cli_tel_cel.value            = consumidores[cliente]['fone2'];
                    formulario.produto_referencia.value     = consumidores[cliente]['produto_referencia'];
                    formulario.produto_descricao.value      = consumidores[cliente]['produto_descricao'];
                    
                    formulario.status_chamado.value         = consumidores[cliente]['status'];
                    
                    formulario.produto_nota_fiscal.value    = consumidores[cliente]['nota_fiscal'];
                    formulario.data_nota_fiscal.value       = consumidores[cliente]['data_nf'];
                    formulario.garantia.value               = consumidores[cliente]['garantia'];
                        
                    formulario.chamado_referencia.value     = consumidores[cliente]['atendimento'];;
                    
                    if (formulario.os_posto.value != "undefined") {
                       formulario.os_posto.value = consumidores[cliente]['sua_os'];
                    }
                    
                    if (formulario.chamado_atendente.value  != "undefined") {
                        formulario.chamado_atendente.value  = consumidores[cliente]['nome_completo'];
                    }
                    
                    $(formulario.cli_cep).focus();
                    $(formulario.cli_numero).focus();

                }               

                function retorna_dados_posto(cliente) {

                    $.ajax({
                        type    : "GET",
                        url     : "pesquisa_consumidor_callcenter_new_ajax.php",
                        async: false,
                        data    : "acao=sql&sql=SELECT codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.fabrica=<? echo $login_fabrica; ?> AND tbl_posto.posto="+consumidores[cliente]['posto'],
                        complete : function(data) {                            
                            trata_retorna_dados_posto(data.responseText, cliente);
                        }
                    });
                }

                function trata_retorna_dados_posto(retorno, cliente) {
                    var formulario = window.parent.document.frm_pesquisa_cadastro;

                    if (retorno.indexOf("|") != -1) {
                        dados = retorno.split('|');

                        if (typeof formulario.codigo_posto != "undefined") {
                            formulario.codigo_posto.value = dados[0];
                        }else{
                            formulario.codigo_posto.value = null;
                        }

                        if (formulario.descricao_posto.value != "undefined") {
                            formulario.descricao_posto.value = dados[1];
                        }else{
                            formulario.descricao_posto.value = null;
                        }

                    }else{
                        formulario.codigo_posto.value = null;
                        formulario.descricao_posto.value = null;
                    }
                    window.parent.Shadowbox.close();
                }

                function fnc_pesquisa_produto (campo, campo2, tipo) {
                    if (tipo == "referencia" ) {
                        var xcampo = campo;
                    }

                    if (tipo == "descricao" ) {
                        var xcampo = campo2;
                    }

                    if (xcampo.value != "") {
                        var url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;

                        if (typeof janela != "undefined") {
                            if (janela != null && !janela.closed) {
                                janela.location = url;
                                janela.focus();
                            }
                            else if (janela != null && janela.closed) {
                                janela = null;
                            }
                        }
                        else {
                            janela = null;
                        }

                        if (janela == null) {
                            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
                            janela = window.janela;
                            janela.referencia   = campo;
                            janela.descricao    = campo2;
                            janela.focus();
                        }
                    }
                }

                function funcao_continuar_busca() {
                    if (window.parent.document.frm_pesquisa_cadastro.produto_referencia.value == '') {
                        return(confirm("Continuar a busca sem informar o produto?\n\nATENÇÃO: desta forma o sistema não buscará o consumidor nas Ordens de Serviço"));
                    }
                    else {
                        return true;
                    }
                }
            </script>
            </head>

            <body>
                <div id="container_lupa" style="overflow-y:auto;">
                <div class="lp_header">
                    <a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
                        <img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
                    </a>
                </div>
            <?
                echo "<div class='lp_nova_pesquisa'>";
                    echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
                        echo "<input type='hidden' name='forma' value='$forma' />";
                        echo "<table cellspacing='1' cellpadding='2' border='0'>";
                            echo "<tr>";
                                echo "<td>";
                                    echo "<label for='tipo_pesquisa' >Tipo de Pesquisa</label>";
                                    echo "<select name='tipo_pesquisa' id='tipo_pesquisa' class='frm' style='width: 110px;'>";
                                        foreach ($lista_tipo_pesquisa as $item => $tipo) {
                                            $selected = ($tipo_pesquisa == $item) ? " selected = 'selected' " : "";

                                            echo "<option value='{$item}' {$selected}>{$tipo}</option>";
                                        }
                                    echo "</select>";
                                echo "</td>";

                                echo "<td>";
                                    echo "<label for='descricao_pesquisa' id='label_descricao_pesquisa'>Descrição da Pesquisa <span style='color: #F00'></span></label>";
                                    echo "<input type='text' name='descricao_pesquisa' id='descricao_pesquisa' value='$descricao_pesquisa' style='width: 200px' maxlength='50' />";
                                echo "</td>";

                                echo "<td>";
                                    echo "<label for='data_inicial'>Data Inicial</label>";
                                    echo "<input type='text' name='data_inicial' class='date' value='{$data_inicial}' style='width: 80px' maxlength='10' />";
                                echo "</td>";

                                echo "<td>";
                                    echo "<label for='data_final'>Data Final</label>";
                                    echo "<input type='text' name='data_final' class='date' value='{$data_final}' style='width: 80px' maxlength='10' />";
                                echo "</td>";

                            echo "</tr>";

                            echo "<tr>";

                                echo "<td colspan='4' class='btn_acao' valign='bottom' align='left' width='*'>
                                        <input type='submit' name='btn_acao' value='Pesquisar ' />
                                    </td>";

                            echo "</tr>";

                        echo "</table>";
                    echo "</form>";
                echo "</div>";
    }//fim do ajax!!!

        if(!empty($descricao_pesquisa)){
            if(!$ajax){
                echo "<div class='lp_pesquisando_por'>Pesquisando pelo ";
                    echo $lista_tipo_pesquisa[$tipo_pesquisa].": ".$descricao_pesquisa;
                echo "</div>";
            }

            //Este array define em quais tabelas o sistema irá buscar consumidores
            //  O: tlb_os
            //  C: tbl_hd_chamado
            //  R: tbl_revenda
            //  A: tbl_posto
            $buscarem = array("O", "C", "A");

            //Array que armazena os parametros da clÃ¡usula WHERE para filtrar a busca
            $busca = array();

            switch($tipo_pesquisa) {
                case "cpf":
                    $cpf_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
                    $cpf        = $descricao_pesquisa;

                    if((strlen($cpf_number) == 11)) {
                        $busca["O"][] = "AND (tbl_os.consumidor_cpf = '$cpf_number' OR tbl_os.consumidor_cpf = '$cpf')";
                        $busca["C"][] = "AND (tbl_hd_chamado_extra.cpf = '$cpf_number' OR tbl_hd_chamado_extra.cpf = '$cpf')";
                    } else{
                        $msg_erro = "Valor de busca digitado incorreto ou em branco. O CPF deve ser digitado com 11 dígitos";
                    }
                break;

                case "cnpj":
                    $cnpj_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
                    $cnpj        = $descricao_pesquisa;

                    if((strlen($cnpj_number) == 14)) {
                        $busca["R"][] = "AND (tbl_revenda.cnpj = '$cnpj_number' OR tbl_revenda.cnpj = '$cnpj')";
                        $busca["A"][] = "AND (tbl_posto.cnpj = '$cnpj_number' OR tbl_posto.cnpj = '$cnpj')";
                        $busca["C"][] = "AND tbl_hd_chamado_extra.cpf = '$cnpj_number'";
                    }else {
                        $msg_erro = "Valor de busca digitado incorreto ou em branco. O CNPJ deve ser digitado com 14 dígitos";
                    }
                break;

                case "nome":
                    //nome - não pode conter números
                    if(strlen($descricao_pesquisa) >= 5) {
                        $busca["O"][] = "AND tbl_os.consumidor_nome ILIKE '$descricao_pesquisa%'";
                        $busca["C"][] = "AND tbl_hd_chamado_extra.nome ILIKE '$descricao_pesquisa%'";
                        $busca["R"][] = "AND tbl_revenda.nome ILIKE '$descricao_pesquisa%'";
                        $busca["A"][] = "AND tbl_posto.nome ILIKE '$descricao_pesquisa%'";
                    }else {
                        $msg_erro = "Valor de busca digitado incorreto ou em branco. O nome do consumidor deve no mínimo 6 letras";
                    }
                break;

                case "atendimento":
                    $atendimento = intval($descricao_pesquisa);
                    //atendimento - somente números
                    if (strlen($atendimento) > 2) {
                        $buscarem = array("C");
                        $busca["C"][] = "AND tbl_hd_chamado.hd_chamado = $atendimento";
                    }
                    else {
                        $msg_erro = "Valor de busca digitado incorreto ou em branco.";
                    }
                break;

                case "os":
                    //os - busca OSs com tamanho de no mínimo 5 números e com no mÃ¡ximo 3 separadores não numÃ©ricos
                    if (strlen($descricao_pesquisa) > 4) {
                        $buscarem = array("O");
                        $busca["O"][] = "AND tbl_os.sua_os='$descricao_pesquisa'";
                    }
                    else {
                        $msg_erro = "O número da OS deve ser composto apenas por números, contendo separador ou não";
                    }
                break;

                case "serie":
                    $buscarem = array("O");
                    $busca["O"][] = "AND tbl_os.serie='" . strtoupper($descricao_pesquisa) . "'";
                    if($login_fabrica == 52) {
                        $buscarem = array("C");
                        $busca["C"][] = "AND tbl_hd_chamado.hd_chamado in (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado JOIN tbl_hd_chamado_item USING(hd_chamado) WHERE produto notnull AND serie ='" . strtoupper($descricao_pesquisa) . "')";

                    }
                break;

                case "cep":
                    $cep_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
                    $cep        = $descricao_pesquisa;

                    if (strlen($descricao_pesquisa) >= 8) {
                        $busca["O"][] = "AND (tbl_os.consumidor_cep = '$cep' OR tbl_os.consumidor_cep = '$cep_number')";
                        $busca["C"][] = "AND (tbl_hd_chamado_extra.cep = '$cep' OR tbl_hd_chamado_extra.cep = '$cep_number')";
                        $busca["R"][] = "AND (tbl_revenda.cep = '$cep' OR tbl_revenda.cep = '$cep_number')";
                        $busca["A"][] = "AND (tbl_posto.cep = '$cep' OR tbl_posto.cep = '$cep_number')";
                    }else {
                        $msg_erro = "O CEP deve ser digitado com 8 dígitos";
                    }
                break;

                case "telefone":
                    $telefone_number = preg_replace("/[^0-9]/", '', $descricao_pesquisa);
                    $telefone        = $descricao_pesquisa;

                    if (strlen($telefone_number) >= 8) {
                        $busca["O"][] = "AND (tbl_os.consumidor_fone = '$telefone_number' OR  tbl_os.consumidor_fone = '$telefone')";
                        $busca["C"][] = "AND (tbl_hd_chamado_extra.fone = '$telefone_number' OR  tbl_hd_chamado_extra.fone = '$telefone')";
                        $busca["R"][] = "AND (tbl_revenda.fone = '$telefone_number' OR  tbl_revenda.fone = '$telefone')";
                        $busca["A"][] = "AND (tbl_posto.fone = '$telefone_number' OR  tbl_posto.fone = '$telefone')";
                    }else {
                        $msg_erro = "Digite pelo menos 8 digitos!";
                    }
                break;

                case "todos":
                    $separador_implode = " OR ";
                    $separador_clausulas_where = " AND ";

                    //cpf/cnpj - busca somente CPF/CNPJ completos, separados ou não por pontos ou traÃ§os
                    if((strlen($localizar_numeros) == 11 && strlen($localizar) <= 14) || (strlen($localizar_numeros) == 14 && strlen($localizar) <= 18)) {
                        $busca["O"][] = "(tbl_os.consumidor_cpf = '$localizar_numeros')";
                        $busca["C"][] = "(tbl_hd_chamado_extra.cpf = '$localizar_numeros')";
                        $busca["R"][] = "(tbl_revenda.cnpj = '$localizar_numeros')";
                        $busca["A"][] = "(tbl_posto.cnpj = '$localizar_numeros')";
                    }

                    //nome - não pode conter números
                    if(($localizar_numeros != $localizar) && (strlen($localizar_numeros) == 0) && ((strlen($localizar) - strlen($localizar_numeros)) >= 5)) {
                        if ($busca_exata) {
            //              $busca["O"][] = "tbl_os.consumidor_nome = '$localizar'";
                            $busca["C"][] = "tbl_hd_chamado_extra.nome = '$localizar'";
                            $busca["R"][] = "tbl_revenda.nome = '$localizar'";
                            $busca["A"][] = "tbl_posto.nome = '$localizar'";
                        }
                        else {
            //              $busca["O"][] = "tbl_os.consumidor_nome ILIKE '%$localizar%'";
                            $busca["C"][] = "tbl_hd_chamado_extra.nome ILIKE '%$localizar%'";
                            $busca["R"][] = "tbl_revenda.nome ILIKE '%$localizar%'";
                            $busca["A"][] = "tbl_posto.nome ILIKE '%$localizar%'";
                        }
                    }

                    //atendimento - somente números
                    if ($localizar_numeros == $localizar) {
                        $busca["C"][] = "tbl_hd_chamado.hd_chamado=$localizar";
                    }

                    //os - busca OSs com tamanho de no mínimo 5 números e com no mÃ¡ximo 3 separadores não numÃ©ricos
                    if ($localizar_numeros && (strlen($localizar_numeros)+3 >= strlen($localizar)) && strlen($localizar_numeros) > 5) {
                        $busca["O"][] = "AND tbl_os.sua_os='$localizar'";
                    }

                    //serie
                    $busca["O"][] = "tbl_os.serie='" . strtoupper($localizar) . "'";

                    //cep - busca CEPs com tamanho de 8 números e com no mÃ¡ximo 2 separadores não numÃ©ricos
                    if (strlen($localizar_numeros == 8) && (strlen($localizar_numeros)+2 >= strlen($localizar))) {
                        $busca["O"][] = "(tbl_os.consumidor_cep = '$localizar_numeros')";
                        $busca["C"][] = "(tbl_hd_chamado_extra.cep = '$localizar_numeros')";
                        $busca["R"][] = "(tbl_revenda.cep = '$localizar_numeros')";
                        $busca["A"][] = "(tbl_posto.cep = '$localizar_numeros')";
                    }

                    //telefone
                    if (strlen($localizar_numeros) > 8) {
                        if ($busca_exata) {
                            $busca["O"][] = "(regexp_replace(tbl_os.consumidor_fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
                            $busca["C"][] = "(regexp_replace(tbl_hd_chamado_extra.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
                            $busca["R"][] = "(regexp_replace(tbl_revenda.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
                            $busca["A"][] = "(regexp_replace(tbl_posto.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
                        }
                        else {
                            $busca["O"][] = "(regexp_replace(tbl_os.consumidor_fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
                            $busca["C"][] = "(regexp_replace(tbl_hd_chamado_extra.fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
                            $busca["R"][] = "(regexp_replace(tbl_revenda.fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
                            $busca["A"][] = "(regexp_replace(tbl_posto.fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
                        }
                    }
                break;

                default:
                    $msg_erro = "Nenhum parametro válido foi informado para a busca";
            }

            if (strlen($estado) == 2) {
                $busca["O"][] = " AND tbl_os.consumidor_estado = '$estado'";
                $busca["C"][] = " AND tbl_hd_chamado_extra.cidade IN (SELECT cidade FROM tbl_cidade WHERE estado='$estado')";
                $busca["R"][] = " AND tbl_revenda.cidade IN (SELECT cidade FROM tbl_cidade WHERE estado='$estado')";
                $busca["A"][] = " AND tbl_posto.estado = '$estado'";
            }

            if (strlen($produto) > 0) {
                $busca["O"][] = " AND tbl_os.produto = $produto";
                $busca["C"][] = " AND tbl_hd_chamado_extra.produto = $produto";
            }

            if (in_array("O", $buscarem)) {
                if (is_array($busca["O"])) {
                    if (in_array("C", $buscarem)) {
                        $exclui_os_com_atendimento = "AND tbl_hd_chamado_extra.os IS NULL";
                    }

                    $busca_O = implode("$separador_implode", $busca["O"]);
                }
                else {
                    $indice = array_search("O", $buscarem);
                    unset($buscarem[$indice]);
                }
            }

            if (in_array("C", $buscarem)) {
                if (is_array($busca["C"])) {
                    $busca_C = implode("$separador_implode", $busca["C"]);
                }
                else {
                    $indice = array_search("C", $buscarem);
                    unset($buscarem[$indice]);
                }
            }

            if (in_array("R", $buscarem)) {
                if (is_array($busca["R"])) {
                    $busca_R = implode("$separador_implode", $busca["R"]);
                }
                else {
                    $indice = array_search("R", $buscarem);
                    unset($buscarem[$indice]);
                }
            }

            if (in_array("A", $buscarem)) {
                if (is_array($busca["A"])) {
                    $busca_A = implode("$separador_implode", $busca["A"]);
                }
                else {
                    $indice = array_search("A", $buscarem);
                    unset($buscarem[$indice]);
                }
            }

            if ($_GET["tipo"] == "todos") {
                $busca_O = "(" . $busca_O . ")";
                $busca_C = "(" . $busca_C . ")";
                $busca_R = "(" . $busca_R . ")";
                $busca_A = "(" . $busca_A . ")";
            }

            if ($busca_produtos) {
                $busca_produtos_select_O = "
                    tbl_produto.produto AS produto_id,
                    tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
                    tbl_produto.referencia AS produto_referencia,
                    tbl_produto.linha AS produto_linha,
                    tbl_produto.descricao AS produto_descricao,
                    tbl_produto.voltagem AS produto_voltagem,
                    tbl_produto.garantia AS garantia,";

                $busca_produtos_select_C = "
                    tbl_produto.produto AS produto_id,
                    tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
                    tbl_produto.referencia AS produto_referencia,
                    tbl_produto.linha AS produto_linha,
                    tbl_produto.descricao AS produto_descricao,
                    tbl_produto.voltagem AS produto_voltagem,
                    tbl_produto.garantia AS garantia,";

                $busca_produtos_select = "
                    0 as produto_id,
                    '' AS produto,
                    '' AS produto_referencia,
                    null AS produto_linha,
                    '' AS produto_descricao,
                    '' AS produto_voltagem,";

                $busca_produtos_from_O = "
                    JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}";

                $busca_produtos_from_C = "
                    LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto  AND tbl_produto.fabrica_i = {$login_fabrica}";
            }

            $sql_busca["O"] = "
                (
                    SELECT
                        tbl_os.os as id,
                        tbl_os.consumidor_nome AS nome,
                        tbl_os.consumidor_endereco AS endereco,
                        tbl_os.consumidor_numero AS numero,
                        tbl_os.consumidor_complemento AS complemento,
                        tbl_os.consumidor_bairro AS bairro,
                        tbl_os.consumidor_cep AS cep,
                        0 AS cidade,
                        tbl_os.consumidor_fone AS fone,
                        tbl_os.consumidor_fone_comercial AS fone2,
                        tbl_os.consumidor_celular AS fone3,
                        tbl_os.consumidor_cpf as cpf_cnpj,
                        ''::text AS rg,
                        ''::text AS nome_completo,
                        tbl_os.consumidor_email AS email,
                        tbl_os.consumidor_cidade AS nome_cidade,
                        tbl_os.consumidor_estado AS estado,
                        tbl_os.sua_os AS sua_os,
                        $busca_produtos_select_O
                        tbl_os.serie,
                        TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
                        tbl_os.nota_fiscal,
                        '' AS status,
                        '' AS categoria,
                        tbl_hd_chamado_extra.hd_chamado AS referencia,
                        tbl_os.posto,
                        'O'::text AS tipo,
                        TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_atendimento,
                        tbl_os.defeito_reclamado,
                        tbl_posto.nome as nome_posto
                    FROM tbl_os
                        JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto AND tbl_os.fabrica=tbl_posto_fabrica.fabrica
                        JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
                        $busca_produtos_from_O
                        LEFT JOIN tbl_hd_chamado_extra ON tbl_os.os=tbl_hd_chamado_extra.os
                    WHERE
                        tbl_os.fabrica = $login_fabrica
                        AND (tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')
                        AND tbl_os.excluida IS NOT TRUE
                        $separador_clausulas_where $busca_O
                        $resultados
                )";

            $sql_busca["C"] = "
                (
                    SELECT
                        tbl_hd_chamado_extra.hd_chamado as id,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.endereco,
                        tbl_hd_chamado_extra.numero,
                        tbl_hd_chamado_extra.complemento,
                        tbl_hd_chamado_extra.bairro,
                        tbl_hd_chamado_extra.cep,
                        tbl_hd_chamado_extra.cidade,
                        tbl_hd_chamado_extra.fone,
                        tbl_hd_chamado_extra.fone2,
                        tbl_hd_chamado_extra.celular AS fone3,
                        tbl_hd_chamado_extra.cpf as cpf_cnpj,
                        tbl_hd_chamado_extra.rg,
                        tbl_admin.nome_completo,
                        tbl_hd_chamado_extra.email,
                        tbl_cidade.nome AS nome_cidade,
                        tbl_cidade.estado,
                        (SELECT tbl_os.sua_os FROM tbl_os WHERE tbl_os.os=tbl_hd_chamado_extra.os AND tbl_os.fabrica = {$login_fabrica}) AS sua_os,
                        $busca_produtos_select_C
                        tbl_hd_chamado_extra.serie,
                        TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_nf,
                        tbl_hd_chamado_extra.nota_fiscal,
                        tbl_hd_chamado.status AS status,
                        tbl_hd_chamado.categoria AS categoria,
                        tbl_hd_chamado_extra.os AS referencia,
                        tbl_hd_chamado_extra.posto,
                        'C'::text as tipo,
                        TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_atendimento,
                        tbl_hd_chamado_extra.defeito_reclamado,
                        tbl_posto.nome as nome_posto
                    FROM tbl_hd_chamado_extra
                        JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado=tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                        JOIN tbl_admin ON tbl_hd_chamado.atendente=tbl_admin.admin AND tbl_admin.fabrica = {$login_fabrica}
                        LEFT JOIN tbl_os ON tbl_hd_chamado_extra.os=tbl_os.os AND tbl_os.fabrica={$login_fabrica}
                        LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        LEFT JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto
                        LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
                        $busca_produtos_from_C
                        
                    WHERE
                        fabrica_responsavel = $login_fabrica
                        ".(($tipo_pesquisa != "atendimento") ? "AND (tbl_hd_chamado.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')" : "")."
                        $separador_clausulas_where $busca_C
                        $resultados
                )
            ";

            $sql_busca["R"] = "
                (
                    SELECT
                        tbl_revenda.revenda as id,
                        tbl_revenda.nome,
                        tbl_revenda.endereco,
                        tbl_revenda.numero,
                        tbl_revenda.complemento,
                        tbl_revenda.bairro,
                        tbl_revenda.cep,
                        tbl_revenda.cidade,
                        tbl_revenda.fone,
                        tbl_revenda.fax AS fone2,
                        '' AS fone3,
                        tbl_revenda.cnpj as cpf_cnpj,
                        ''::text AS rg,
                        ''::text AS nome_completo,
                        tbl_revenda.email,
                        tbl_cidade.nome AS nome_cidade,
                        tbl_cidade.estado,
                        '' AS sua_os,
                        $busca_produtos_select
                        '' AS serie,
                        '' AS data_nf,
                        '' AS nota_fiscal,
                        '' AS status,
                        '' AS categoria,
                        0 AS referencia,
                        0 AS posto,
                        'R'::text AS tipo,
                        '' AS data_atendimento,
                        null as defeito_reclamado,
                        '' as nome_posto
                    FROM tbl_revenda
                        LEFT JOIN tbl_cidade USING (cidade)
                    WHERE
                        1=1
                        $separador_clausulas_where $busca_R
                        $resultados
                )
            ";

            $sql_busca["A"] = "
                (
                    SELECT
                        tbl_posto.posto AS id,
                        tbl_posto.nome AS nome,
                        tbl_posto.endereco AS endereco,
                        tbl_posto.numero AS numero,
                        tbl_posto.complemento AS complemento,
                        tbl_posto.bairro AS bairro,
                        tbl_posto.cep AS cep,
                        0 AS cidade,
                        tbl_posto.fone AS fone,
                        tbl_posto.fax AS fone2,
                        '' AS fone3,
                        tbl_posto.cnpj AS cpf_cnpj,
                        ''::text AS rg,
                        ''::text AS nome_completo,
                        tbl_posto.email AS email,
                        tbl_posto.cidade AS nome_cidade,
                        tbl_posto.estado AS estado,
                        '' AS sua_os,
                        $busca_produtos_select
                        '' AS serie,
                        '' AS data_nf,
                        '' AS nota_fiscal,
                        '' AS status,
                        '' AS categoria,
                        0 AS referencia,
                        0 AS posto,
                        'A'::text AS tipo,
                        TO_CHAR(tbl_posto_fabrica.data_alteracao, 'DD/MM/YYYY')  AS data_atendimento,
                        null as defeito_reclamado,
                        tbl_posto.nome as nome_posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE
                        tbl_posto_fabrica.fabrica = $login_fabrica
                        $separador_clausulas_where $busca_A
                        $resultados
                )
            ";

            //print nl2br(($sql_busca["C"])); exit;


            if(empty($msg_erro)){
                $busca_sql_final = array();

                //Este bloco de código verifica o array $buscarem para verificar quais opcoes
                //de busca foram selecionadas. Para cada item do array $buscarem a rotina
                //inserte no array $busca_sql_final a sql correspondente do array $sql_busca

                foreach($buscarem AS $indice => $opcao) {
                    $busca_sql_final[] = $sql_busca[$opcao];
                }

                if (count($sql_busca)) {
                     $busca_sql_final = implode(" UNION ", $busca_sql_final);
                }else {
                    $msg_erro = "A busca não retornou resultados";
                }

                $sql = "SELECT * FROM (
                            $busca_sql_final
                        ) AS Dados
                        ORDER BY 
                        tipo, id DESC
                        $resultados;";

                //exit(nl2br($sql));
                //echo nl2br($sql);
                //exit;

                $res = pg_query($sql);
                if(pg_last_error($con)){
                    $msg_erro = "Erro ao pesquisar dados!";
                }
            }

            if(!empty($msg_erro)){
                if ($ajax)
                    echo "erro|$msg_erro";
                else
                    echo "<div class='lp_msg_erro'>{$msg_erro}</div>";
                exit;
            }else{
                if(pg_num_rows($res)){
                    if(!$ajax){
                        echo "
                            <script language=javascript>
                                consumidores = new Array();
                                formulario = window.parent.frm_pesquisa_cadastro;
                            </script>";

                            $coluna = ($login_fabrica == 74) ? "<th>Telefone</th>" : "<th>Produto</th>";

                        echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
                            echo "<thead>";
                                echo "<tr>";
                                    echo "<th>Atendimento</th>";
                                    echo "<th>Data</th>";
                                    echo "<th>Cliente</th>";
                                    echo "<th>CPF</th>";
                                    echo "<th>Ordem Serviço </th>";
                                    echo $coluna;
                                    echo "<th>Status</th>";
                                    echo "<th>Tipo Atendimento </th>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                    }
                            $dados_javascript = "";
                            $nomes_repetidos = array();
                            for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                                $dados = pg_fetch_array($res,$i,PGSQL_ASSOC);
                                extract($dados);
                                
                                if(!$ajax){
                                    for($f = 0; $f < pg_num_fields($res); $f++) {
                                        $campo = pg_field_name($res, $f);
                                        $valor = pg_fetch_result($res, $i, $f);
                                        $$campo = $valor;

                                        if ($login_fabrica == 183 AND $campo == "garantia"){
                                            list($dia, $mes, $ano) = explode("/", $data_nf);
                                            if (strtotime("{$ano}-{$mes}-{$dia} +{$valor} months") < strtotime("today")) {
                                                $valor = "nao";
                                            }else{
                                                $valor = "sim";
                                            }
                                            $$campo = $valor;
                                        }

                                        //Este código gera os dados dos clientes em uma matriz de arrays javascript para que as funções
                                        //retorna_dados_cliente() e retorna_dados_produto() possam buscar e retornar os valores
                                        if ($f == 0) {
                                            echo "
                                                <script type='text/javascript'>
                                                    consumidores[".$i."] = new Array();
                                                </script>";
                                        }


                                        echo "
                                            <script type='text/javascript'>
                                                consumidores[".$i."]['".$campo."'] = '" . addslashes($valor) . "';
                                            </script>";


                                        switch($tipo) {
                                            case "O":
                                                    echo "
                                                    <script type='text/javascript'>                                                
                                                        consumidores[".$i."]['atendimento'] = '$referencia';
                                                    </script>";
                                            break;

                                            case "C":
                                                echo "
                                                    <script type='text/javascript'>                                                
                                                        consumidores[".$i."]['atendimento'] = '$id';
                                                    </script>";
                                            break;

                                            case "R":
                                                echo "
                                                    <script type='text/javascript'>                                                
                                                        consumidores[".$i."]['atendimento'] = '';
                                                    </script>";
                                            break;

                                            case "A":
                                                echo "
                                                    <script type='text/javascript'>                                                
                                                        consumidores[".$i."]['atendimento'] = '';
                                                    </script>";
                                            break;
                                        }
                                    }

                                }

                                if(array_search($id,$nomes_repetidos) === false){
                                    $nomes_repetidos[] = $id;
                                }else{
                                    continue;
                                }
                                switch($tipo) {
                                    case "O":
                                        $os = $sua_os;
                                        $atendimento = $referencia;

                                        $linkos = "";

                                        if ($atendimento) {
                                            $sql = "
                                            SELECT
                                                status,
                                                categoria

                                            FROM tbl_hd_chamado
                                            WHERE
                                                hd_chamado=$atendimento;";
                                            $res_hd = pg_query($con, $sql);

                                            $status = pg_fetch_result($res_hd, 0, status);
                                            $categoria = pg_fetch_result($res_hd, 0, categoria);
                                        }
                                    break;

                                    case "C":
                                        $os = $sua_os;
                                        $atendimento = $id;
                                        $linkos = "";
                                    break;

                                    case "R":
                                        $os = 0;
                                        $atendimento = 0;
                                    break;

                                    case "A":
                                        $os = 0;
                                        $atendimento = 0;
                                    break;
                                }

                                if ($atendimento) {
                                    $atendimento_link = $atendimento;

                                    if ($status == "Resolvido" || $status == "Cancelado") {
                                        if ($os) {
                                            $os_link = $sua_os;
                                        }else {
                                            $os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
                                            $linkos = "";
                                        }
                                    }else {
                                        if ($os) {
                                            $os_link = $os;
                                        }else {
                                            $os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
                                            $linkos = "";
                                        }
                                    }
                                }else{
                                    $atendimento_link = "<p class='sematendimento'>SEM ATENDIMENTO</p>";
                                    $os_link = $os;

                                    if ($os) {
                                        $os_link = $sua_os;
                                    }else {
                                        $os_link = "<p class='semos'>SEM ORDEM SERVIÇO</p>";
                                        $linkos = "";
                                    }
                                }

                                if(!empty($nome)){
                                    $funcao_produto = ($login_fabrica == 74) ? " retorna_dados_produto($i); " : "";
                                    
                                    $nome = "<a href=\"javascript: retorna_dados_cliente($i); retorna_dados_posto($i); $funcao_produto window.parent.Shadowbox.close();\"  >".verificaValorCampo(substr($nome, 0, 30))."</a>";
                                }
                                else
                                    $nome = "&nbsp;";

                                if(!empty($produto))
                                    $produto = verificaValorCampo(substr($produto, 0, 20));
                                else
                                    $produto = "&nbsp;";

                                if(!$ajax){
                                    $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

                                    $coluna = ($login_fabrica == 74) ? "<td>$fone</td>" : "<td>$produto</td>";

                                    echo "<tr style='background: $cor'>";
                                        echo "<td style='text-align: center'>".verificaValorCampo($atendimento_link)."</td>";
                                        echo "<td style='text-align: center'>".verificaValorCampo($data_atendimento)."</td>";
                                        echo "<td>$nome</td>";
                                        echo "<td>$cpf_cnpj</td>";
                                        echo "<td style='text-align: center'>".verificaValorCampo($os_link)."<span class='right'>$linkos</span></td>";
                                        echo $coluna;
                                        echo "<td style='text-align: center'>".verificaValorCampo($status)."</td>";
                                        echo "<td>".verificaValorCampo($categoria)."</td>";
                                    echo "</tr>";
                                }else{
                                    $valores = array();

                                    for($f = 0; $f < pg_num_fields($res); $f++) {
                                        $valores[] = pg_fetch_result($res, $i, $f);
                                    }

                                    $valores = implode("|", $valores);
                                    echo $valores . "\n";
                                }
                            }
                    if(!$ajax){
                            echo "</tbody>";

                        echo "</table>";
                    }
                }else{
                    if(!$ajax)
                        ?>
                        <div class='lp_msg_erro'>Nenhum resultado encontrado.</div>
                        <br>
                        <center><button onclick="javascript:window.print()">Imprimir</button></center>
                        <?
                }
            }
        }else{
            if(!$ajax)
                echo "<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>";
        }
    if(!$ajax){
    ?>
    </body>
</html>
<?php }?>
