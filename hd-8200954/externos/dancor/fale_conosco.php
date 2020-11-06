<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';


$login_fabrica    = 193;
$admin            = 12109;
$atendente        = 12109;
$origem           = 182; //"Fale Conosco" do devel
$site             = 'www.dancor.com.br/dancor-site-novo/public/';
$hd_classificacao = 419;

function validaEmail() {
    global $_POST;

    $email = $_POST["email"];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inv·lido");
    }
}

if ($_POST["buscaCidade"] == true) {
    $estado = strtoupper($_POST["estado"]);

    if (strlen($estado) > 0) {
        $sql = "SELECT DISTINCT * FROM (
                SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
                UNION (
                    SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
                )
            ) AS cidade ORDER BY cidade ASC";
        $res  = pg_query($con, $sql);
        $rows = pg_num_rows($res);

        if ($rows > 0) {
            $cidades = array();

            for ($i = 0; $i < $rows; $i++) {
                $cidades[$i] = array(
                    "cidade"          => utf8_encode(pg_fetch_result($res, $i, "cidade")),
                    "cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade"))),
                );
            }

            $retorno = array("cidades" => $cidades);
        } else {
            $retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
        }
    } else {
        $retorno = array("erro" => "Nenhum estado selecionado");
    }

    exit(json_encode($retorno));
}


if($_POST['query']){
    $param = addslashes($_POST['query']);
    $sql   = "SELECT referencia, descricao, produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND (referencia ~* '{$param}' OR descricao ~* '{$param}');";
    $res   = pg_query($con,$sql);
    $dados = pg_fetch_all($res);

    if (pg_num_rows($res) > 0) {
        foreach ($dados as $key => $value) {
        $value['descricao'] = (mb_detect_encoding($value['descricao'], 'UTF-8', true)) ? $value['descricao'] : utf8_encode($value['descricao']);
            $retorno[] = array("label" => $value['referencia']." : ".$value['descricao'], "value" => $value['referencia']." : ".$value['descricao'], "produto" => $value['produto']);
        }
    }
    
    echo json_encode($retorno);
    exit;
}

if (isset($_POST["ajax_enviar"])) {

    $regras = array(
        "notEmpty" => array(
            "assunto",
            "nome",
            "area_atuacao",
            "email",
            "estado",
            "cidade",
            "bairro",
            "mensagem"
        ),
        "validaEmail"  => "email"
    );

    $msg_erro = array(
        "msg"    => array(),
        "campos" => array()
    );

    foreach ($regras as $regra => $campo) {
        switch ($regra) {
            case "notEmpty":
                foreach($campo as $input) {
                    $valor = trim($_POST[$input]);

                    if (empty($valor)) {
                        $msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatÛrios");
                        $msg_erro["campos"][]   = $input;
                    }
                }
                break;

            default:
                $valor = trim($_POST[$campo]);
                if (!empty($valor)) {
                    try {
                        call_user_func($regra);
                    } catch(Exception $e) {
                        $msg_erro["msg"][]    = utf8_encode($e->getMessage());
                        $msg_erro["campos"][] = $campo;
                    }
                }
                break;
        }
    }

    if (count($msg_erro["msg"]) > 0) {
        $retorno = array("erro" => $msg_erro);
    } else {
        $assunto            = trim($_POST["assunto"]);
        $nome               = utf8_decode(trim($_POST["nome"]));
        $area_atuacao       = trim($_POST["area_atuacao"]);
        $email              = trim($_POST["email"]);
        $telefone           = trim($_POST["telefone"]);
        $estado             = trim($_POST["estado"]);
        $cidade             = trim($_POST["cidade"]);
        $bairro             = trim($_POST["bairro"]);
        $produto            = (isset($_POST['produto'])) ? trim($_POST["produto"]) : null;
        $mensagem           = utf8_decode(trim($_POST["mensagem"]));
        $informativo_dancor = (isset($_POST['informativo_dancor'])) ? $_POST['informativo_dancor'] : null;
        $mensagem          .= " <br /> Deseja receber Informativo Dancor: <b>{$informativo_dancor}</b> <br />";

        if (!in_array($assunto, ['assistencia', 'reclamacao'])) {
            $corpoEmail = "
                <p><b>Nome:</b> {$nome}</p>
                <p><b>Email:</b> {$email}</p>
                <p><b>¡rea de AtuaÁ„o:</b> {$area_atuacao}</p>
                <p><b>Telefone:</b> {$telefone}</p>
                <p><b>Cidade:</b> {$cidade}</p>
                <p><b>Estado:</b> {$estado}</p>
                <p><b>Bairro:</b> {$bairro}</p>
                <p><b>Assunto:</b> ".strtoupper($assunto)." </p>
                <p><b>Mensagem:</b> {$mensagem} </p>";

            $mailTc = new TcComm('smtp@posvenda');
            $res    =  $mailTc->sendMail(
                        'lucas.bicaleto@telecontrol.com.br',
                        "Fale Conosco via site Dancor",
                        $corpoEmail,
                        'noreply@telecontrol.com.br');
            if ($res) {
                $retorno = array("sucesso" => true);
            }
        }else{
            $sql = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND lower(descricao) = 'fale conosco'";
            $res = pg_query($con,$sql);
            $hd_chamado_origem = pg_fetch_result($res, 0, 'hd_chamado_origem');

            try {

                pg_query($con, "BEGIN");


                //
                 /*
                * - Verifica TODOS os atendentes
                * Respons·veis pela regi„o E classificaÁ„o
                * do chamado
                 */
                $sql = "SELECT  DISTINCT
                            tbl_admin.admin as atendente
                    FROM    tbl_admin_atendente_estado
                    JOIN    tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
                    WHERE   tbl_admin_atendente_estado.fabrica  = {$login_fabrica}
                    AND     tbl_admin.fabrica                           = {$login_fabrica}
                    AND     tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                    AND     tbl_admin.ativo             IS TRUE
                    AND     tbl_admin.nao_disponivel    IS NULL  ";
                $resP = pg_query($con,$sql);
                $atDoDia = pg_fetch_all($resP);

                /*
                 * - Faz a contagem di·ria de chamados
                 * direcionados a este atendente
                 */
                foreach ($atDoDia as $key => $value) {
                    $sqlCont = "
                        SELECT  COUNT(1) AS chamados_hoje
                        FROM    tbl_hd_chamado
                        WHERE   tbl_hd_chamado.atendente = ".$value['atendente']."
                        AND     tbl_hd_chamado.posto isnull
                        AND     tbl_hd_chamado.data::DATE = CURRENT_DATE";
                    $resCont       = pg_query($con,$sqlCont);
                    $contaChamados = pg_fetch_result($resCont,0,'chamados_hoje');
                    $qtdeChamados[$value['atendente']] = $contaChamados;
                }

                /*
                 * - Retira o atendente
                 * com menor n˙mero de chamados
                 * atendidos no dia para gravaÁ„o do prÛximo chamado
                 */
                asort($qtdeChamados);
                $atendentesOrdenados     = array_keys($qtdeChamados);
                $primeiroAtendente       = array_shift($atendentesOrdenados);
                $callcenter_supervisor[] = array("atendente" => $primeiroAtendente);

                foreach ($callcenter_supervisor as $key => $value) {
                    $atendentes[] = $value['atendente'];
                }
                $atendentes = array_filter($atendentes);

                if(count($atendentes) > 0){
                    $sql = "SELECT  tbl_admin_atendente_estado.admin,
                                tbl_admin.login
                            FROM tbl_admin_atendente_estado
                                JOIN tbl_admin USING(admin)
                            WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
                                AND tbl_admin_atendente_estado.admin IN(".implode(",",$atendentes).")
                                AND tbl_admin.fabrica = {$login_fabrica}
                                AND tbl_admin.ativo IS TRUE
                                AND tbl_admin.nao_disponivel is NULL
                                AND tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                            LIMIT 1";

                    $resP           = pg_query($con,$sql);
                    $novo_atendente = pg_fetch_result($resP, 0, 'admin');
                    $nome_atendente = pg_fetch_result($resP, 0, 'login');
                }

                $atendente  = (!empty($novo_atendente)) ? $novo_atendente : $atendente;
                $admin      = (!empty($novo_atendente)) ? $novo_atendente : $admin;

                $busca_prod = "SELECT produto FROM tbl_produto WHERE produto = ".addslashes($produto);
                $res_prod   = pg_query($con, $busca_prod);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Produto informado, n„o foi encontrado." . pg_last_error());
                }

                if (!empty($estado) && !empty($cidade)) {
                    $estado    = strtoupper($estado);
                    $cidade    = strtoupper($cidade);
                    $sqlCidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(nome) = '{$cidade}' AND UPPER(estado) = '{$estado}'";
                    $resCidade = pg_query($con, $sqlCidade);

                    if (pg_num_rows($resCidade) > 0) {
                        $cidade = pg_fetch_result($resCidade, 0, 'cidade');
                    }
                }

                $sql = "INSERT INTO tbl_hd_chamado (
                                            admin,
                                            data,
                                            fabrica_responsavel,
                                            fabrica,
                                            titulo,
                                            status,
                                            atendente
                                        ) VALUES (
                                            $admin,
                                            CURRENT_TIMESTAMP,
                                            $login_fabrica,
                                            $login_fabrica,
                                            'Atendimento Fale Conosco',
                                            'Aberto',
                                            $atendente
                                            
                                        )RETURNING hd_chamado";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #1" . pg_last_error());
                }

                $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

                $sql = "INSERT INTO tbl_hd_chamado_extra
                            (
                                hd_chamado        ,
                                produto,
                                nome              ,
                                email             ,
                                reclamado, 
                                origem,
                                hd_chamado_origem,
                                consumidor_revenda,
                                cidade,
                                bairro
                            )
                            VALUES
                            (
                                $hd_chamado,
                                $produto,
                                '$nome',
                                '$email',
                                '$mensagem',
                                'Fale Conosco',
                                $hd_chamado_origem,
                                'C',
                                $cidade,
                                '$bairro'
                            )";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #2" . pg_last_error());
                }

                $sql = "INSERT INTO tbl_hd_chamado_item (
                    admin,
                    status_item,
                    hd_chamado ,
                    comentario
                ) VALUES (
                    $admin,
                    'Aberto',
                    $hd_chamado,
                    'Abertura de chamado via Fale Conosco - Site $site'
                )";
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #3");
                }

                if (count($msg_erro["msg"]) > 0) {
                    $retorno = array("erro" => $msg_erro);
                }else{
                    pg_query($con, "COMMIT");
                    $retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
                }

            } catch (Exception $e) {
                $msg_erro["msg"][]      = $e->getMessage();
                $retorno = array("erro" => $msg_erro);
                pg_query($con, "ROLLBACK");
            }
        }
    }

    exit(json_encode($retorno));
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
    "AP"=>"AP - Amap·", "BA"=>"BA - Bahia", "CE"=>"CE - Cear·","DF"=>"DF - Distrito Federal",
    "ES"=>"ES - EspÌrito Santo", "GO"=>"GO - Goi·s","MA"=>"MA - Maranh„o","MG"=>"MG - Minas Gerais",
    "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Par·","PB"=>"PB - ParaÌba",
    "PE"=>"PE - Pernambuco","PI"=>"PI - PiauÌ","PR"=>"PR - Paran·","RJ"=>"RJ - Rio de Janeiro",
    "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - RondÙnia","RR"=>"RR - Roraima",
    "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
    "SP"=>"SP - S„o Paulo","TO"=>"TO - Tocantins");
?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />
    <title>Fale Conosco</title>

    <!-- jQuery -->
    <script type="text/javascript" src="../callcenter/plugins/jquery-1.11.3.min.js" ></script>

    <!-- Plugins Adicionais -->
    <script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
    <script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.form.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js?v=<?php echo date("YmHis");?>"></script>

    <!-- Bootstrap -->
    <script type="text/javascript" src="../callcenter/plugins/bootstrap/js/bootstrap.min.js" ></script>
    <link rel="stylesheet" type="text/css" href="../callcenter/plugins/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

    <script type="text/javascript">
        $(function(){

            var btn;
            $("#form_fale_conosco").ajaxForm({
                complete:function(data){
                    data = $.parseJSON(data.responseText);

                    //data = JSON.parse(data);
                    if (data.erro) {
                        var msg_erro = [];

                        $.each(data.erro.msg, function(key, value) {
                            msg_erro.push(value);
                        });

                        $("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

                        data.erro.campos.forEach(function(input) {
                            $("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").parents("div.form-group").addClass("has-error");
                        });

                        $("#msg_erro").show();
                    } else {
                        if (typeof data.hd_chamado != "undefined") {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
                        } else {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
                        }

                        $("input, textarea, select").val("");
                    }

                    $(document).scrollTop(0);
                    $("#enviar").button("reset");
                }
            });

            $("#enviar").click(function() {
                btn      = $(this);

                $("div.input.erro").removeClass("erro");
                $("#msg_erro").html("").hide();
                $("#msg_sucesso").hide();
                $(btn).button("loading");

                $("#form_fale_conosco").submit();
            });

            $("#estado").change(function () {
                if ($(this).val().length > 0) {
                    buscaCidade($(this).val());
                } else {
                    $("#cidade > option[rel!=default]").remove();
                }
            });

            function buscaCidade (estado, cidade, tipo = 'C') {
                $.ajax({
                    async: false,
                    url: "fale_conosco.php",
                    type: "POST",
                    data: { buscaCidade: true, estado: estado },
                    cache: false,
                    complete: function (data) {
                        data = $.parseJSON(data.responseText);

                        if (data.cidades) {
                            if (tipo == "C") {
                                $("#cidade > option[rel!=default]").remove();
                            }

                            if (tipo == "R") {
                                $("#cidade_revenda > option[rel!=default]").remove();
                            }

                            var cidades = data.cidades;

                            $.each(cidades, function (key, value) {
                                var option = $("<option></option>");
                                $(option).attr({ value: value.cidade });
                                $(option).text(value.cidade);

                                if (cidade == undefined) { cidade = value.cidade; }

                                var cid = retiraAcentos(cidade);

                                if (cidade != undefined && value.cidade.toUpperCase() == cid.toUpperCase()) {
                                    $(option).attr({ selected: "selected" });
                                }

                                if (tipo == "C") {
                                    $("#cidade").append(option);
                                }

                                if (tipo == "R") {
                                    $("#cidade_revenda").append(option);
                                }
                            });
                        } else {
                            if (tipo == "C") {
                                $("#cidade > option[rel!=default]").remove();
                            }

                            if (tipo == "R") {
                                $("#cidade_revenda > option[rel!=default]").remove();
                            }

                        }
                    }
                });
            }

            function retiraAcentos(obj) {

                com_acento = '.·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
                sem_acento = '.aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';

                resultado = '';

                for (i = 0; i < obj.length; i++) {

                    if (com_acento.search(obj.substr(i,1)) >= 0) {
                        resultado += sem_acento.substr(com_acento.search(obj.substr(i,1)),1);
                    } else {
                        resultado += obj.substr(i,1);
                    }

                }
                return resultado;

            }

            $("#produto_descricao").autocomplete({
                source: function( request, response ) {
                    $.ajax({
                        url: "<?=$_SERVER['PHP_SELF']?>",
                        type: 'POST',
                        dataType: "JSON",
                        data: {
                            query: request.term
                        },
                        success: function( data ) {
                            console.log(data);
                            response( data );
                        }
                    });
                },
                select: function (event, ui) {
                    var produto           = ui.item.produto;
                    var descricao_produto = ui.item.label;
                    $('#produto_descricao').val(descricao_produto);
                    $('#produto').val(produto);
                    return false;
                }
            });

            $("#assunto").change(function(){
                var selectedCountry = $(this). children("option:selected"). val();

                if ($.inArray(selectedCountry, ['assistencia','reclamacao']) == -1) {
                    $("#div_produto").hide();
                } else {
                    $("#div_produto").show();
                }
            });

            $("#div_produto").hide();
        });       
    </script>
    <style>
        label {
            color: #8f8f8f;
        }

        .campo_obrigatorio {
            color: darkred;
        }
    </style>
</head>
<body style="background-color: #ffffff;">

<div class="container">
    <h3 style="color:#00adf0;">Fale Conosco:</h3>
    <div id="msg_erro" class="alert alert-danger alert-dismissible" style="display: none;">

    </div>
    <div id="msg_sucesso" class="alert alert-success alert-dismissible" style="display: none;">

    </div>
    <form id="form_fale_conosco" action='fale_conosco.php' enctype="multipart/form-data" method="post">
        <label style="color: red;"><span class="campo_obrigatorio"> *</span> Itens de preenchimento obrigatÛrio</label>
        <input type="hidden" name="ajax_enviar" value='true' />
        <br />
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="assunto" >Assunto<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="assunto" name="assunto" >
                <option value='' class="selecione">Selecione</option>
                <option value="marketing">Marketing</option>
                <option value="vendas">Vendas</option>
                <option value="assistencia">AssistÍncia TÈcnica</option>
                <option value="financeiro">Financeiro</option>
                <option value="trienamento">Treinamento</option>
                <option value="reclamacao">ReclamaÁıes, CrÌticas e Sugestıes</option>
                <option value="trabalhe_conosco">Trabalhe Conosco</option>
                <option value="outros">Outros</option>
            </select>
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <label for="nome" >Nome<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="nome" name="nome" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="area_atuacao" >¡rea de AtuaÁ„o<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="area_atuacao" name="area_atuacao" >
                <option value='' class="selecione">Selecione</option>
                <option value="">Escolha</option>
                <option value="Arquiteto">Arquiteto</option>  
                <option value="Balconista">Balconista</option>
                <option value="Comprador">Comprador</option>
                <option value="Bombeiros Hidra˙lico">Bombeiros Hidra˙lico</option>
                <option value="Comerciante Varejista">Comerciante Varejista</option>
                <option value="Engenheiro">Engenheiro</option>
                <option value="Vendas e Marketing">Vendas e Marketing</option>
                <option value="Perfurador de PoÁos">Perfurador de PoÁos</option>
                <option value="SÌndico">SÌndico</option>
                <option value="Estudante">Estudante</option>
                <option value="Projetisca">Projetisca</option>
            </select>
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <label for="email" >Seu E-mail<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="email" name="email" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <label for="telefone" >Telefone </label>
            <input type="text" class="form-control" id="telefone" name="telefone" />
        </div>

        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="estado" >Estado<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="estado" name="estado" >
                <option value='' class="selecione">Selecione</option>
                <?php
                    foreach ($array_estado as $k => $v) {
                        echo '<option value="'.$k.'"'.($aux_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                    }
                ?>
            </select>
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="cidade" >Cidade<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="cidade" name="cidade" >
                <option value='' class="selecione">Selecione</option>
                <option value='assistencia' class="selecione">Assist√™ncia T√©cnica</option>
                <option value='vendas' class="selecione">Vendas</option>
                <option value='rh' class="selecione">RH</option>
                <option value='financeiro' class="selecione">Financeiro</option>
                <option value='compras' class="selecione">Compras</option>
                <option value='marketing' class="selecione">Marketing</option>
                <option value='outros' class="selecione">Outros</option>
            </select>
        </div>
        <div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
            <label for="bairro" >Bairro<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="bairro" name="bairro" />
        </div>
        <div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" id="div_produto" style="display: none;">
            <label for="produto_descricao" >Produto</label>
            <input type='hidden' name='produto' id='produto' value="<?php echo $produto?>">
            <input type="text" class="form-control" id="produto_descricao" name="produto_descricao" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="mensagem" >Mensagem<span class="campo_obrigatorio"> *</span></label>
            <textarea class="form-control" name="mensagem" rows="6" ></textarea>
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="informativo_dancor" >Deseja receber Informativo Dancor?</label> <br />
            <input type="radio" name="informativo_dancor" id="informativo_dancor" class="" value="sim" /> Sim &nbsp;&nbsp;
            <input type="radio" name="informativo_dancor" id="informativo_dancor" class="" value="nao" /> N„o
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" align="center">
            <button style="background-color: #428bca;color: white;width: 100px;" type="button" id="enviar" class="btn btn-md" data-loading-text="ENVIANDO..." >Enviar</button>
        </div> <br />
    </form>
</div>

