<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';

$b64_fabrica      = base64_decode($_GET['fab']);

if ($b64_fabrica == 'lith') {
    $titulo = 'Fale Conosco - Lith Ferramentas';
    $site   = 'https://lithferramentas.com.br';

} elseif ($b64_fabrica == 'fortg') {
    $titulo = 'Fale Conosco - Fortg Ferramentas';
    $site   = 'https://fortg.com.br';
}

$login_fabrica    = 194;
$admin            = 12031;
$atendente        = 12031;
$origem           = 182; //"Fale Conosco" do devel
$hd_classificacao = 399;

function validaEmail() {
    global $_POST;

    $email = $_POST["email"];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inv�lido");
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


if (isset($_POST["ajax_enviar"])) {

    $regras = array(
        "notEmpty" => array(
            "nome",
            "email",
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
                        $msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigat�rios");
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
        $nome               = utf8_decode(trim($_POST["nome"]));
        $email              = trim($_POST["email"]);
        $telefone           = trim($_POST["telefone"]);
        $mensagem           = utf8_decode(trim($_POST["mensagem"]));
        
        /*
            $estado             = trim($_POST["estado"]);
            $cidade             = trim($_POST["cidade"]);
            $bairro             = trim($_POST["bairro"]);
        */

        $sql = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND lower(descricao) = 'fale conosco'"; 
        $res = pg_query($con,$sql);
        $hd_chamado_origem = pg_fetch_result($res, 0, 'hd_chamado_origem');

        try {

            pg_query($con, "BEGIN");

            /*
             * Verifica TODOS os atendentes
             * Respons�veis pela regi�o E classifica��o
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
             * - Faz a contagem di�ria de chamados
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
             * com menor n�mero de chamados
             * atendidos no dia para grava��o do pr�ximo chamado
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

            /*if (!empty($estado) && !empty($cidade)) {
                $estado    = strtoupper($estado);
                $cidade    = strtoupper($cidade);
                $sqlCidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(nome) = '{$cidade}' AND UPPER(estado) = '{$estado}'";
                $resCidade = pg_query($con, $sqlCidade);

                if (pg_num_rows($resCidade) > 0) {
                    $cidade = pg_fetch_result($resCidade, 0, 'cidade');
                }
            }*/

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
                            nome              ,
                            email             ,
                            reclamado, 
                            origem,
                            hd_chamado_origem,
                            consumidor_revenda
                        )
                        VALUES
                        (
                            $hd_chamado,
                            '$nome',
                            '$email',
                            '$mensagem',
                            'Fale Conosco',
                            $hd_chamado_origem,
                            'C'
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

    exit(json_encode($retorno));
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
    "AP"=>"AP - Amap�", "BA"=>"BA - Bahia", "CE"=>"CE - Cear�","DF"=>"DF - Distrito Federal",
    "ES"=>"ES - Esp�rito Santo", "GO"=>"GO - Goi�s","MA"=>"MA - Maranh�o","MG"=>"MG - Minas Gerais",
    "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Par�","PB"=>"PB - Para�ba",
    "PE"=>"PE - Pernambuco","PI"=>"PI - Piau�","PR"=>"PR - Paran�","RJ"=>"RJ - Rio de Janeiro",
    "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rond�nia","RR"=>"RR - Roraima",
    "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
    "SP"=>"SP - S�o Paulo","TO"=>"TO - Tocantins");
?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />
    <title><?= $titulo; ?></title>

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

                com_acento = '.����������������������������������������������';
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
        });       
    </script>
    <style>
        #form_fale_conosco {
            margin-top: 35px;
        }

        .btn-action {
            padding: 10px 20px;
            margin-left: 50%;
            font-size: 1em;
            font-weight: 700;
            text-decoration: none;
            color: #fff;
            background-color: #e6b800;
            border: none;
            border-radius: 3px;
            float: left;
            text-align: center;

        }

        label {
            color: #6c757d;
        }

        .campo_obrigatorio {
            color: darkred;
        }
    </style>
</head>
<body style="background-color: #ffffff;">

<div class="container">
    <div id="msg_erro" class="alert alert-danger alert-dismissible" style="display: none;">

    </div>
    <div id="msg_sucesso" class="alert alert-success alert-dismissible" style="display: none;">

    </div>

    <form id="form_fale_conosco" action="fale_conosco.php" method="POST" class="cor">
    <input type="hidden" name="ajax_enviar" value='true' />
        <div class="form-group">
            <label for="nome" >Nome <span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="nome" name="nome" />
        </div>
        <div class="form-group">
            <label for="email" >E-mail <span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="email" name="email" />
        </div>
        <div class="form-group">
            <label for="telefone" >Telefone </label>
            <input type="text" class="form-control" id="telefone" name="telefone" />
        </div>
        <div class="form-group">
            <label for="mensagem" >Mensagem <span class="campo_obrigatorio"> *</span></label>
            <textarea class="form-control" name="mensagem" rows="6" ></textarea>
        </div>
        <div class="form-group" align="center">
            <button class="btn-action btn-action--center contact-form__btn-submit" type="button" id="enviar" class="btn btn-md" data-loading-text="ENVIANDO...">
                <i class="fas fa-circle-notch fa-spin" style="display: none;"></i> <span>Enviar</span>
            </button>
        </div>
    </form>


<!-- TALVEZ SER� USADO 
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
                <option value='assistencia' class="selecione">Assistência Técnica</option>
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
-->
</div>

