<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';


$login_fabrica    = 198;
$admin            = 12124;
$atendente        = 12124;
$origem           = 199; //"Fale Conosco" do devel
$site             = 'https://www.frigelar.com.br';
$hd_classificacao = 438;

function getCidades($con, $consumidor_cidade, $consumidor_estado) {
    $sql = "
        SELECT  tbl_cidade.cidade,
                tbl_cidade.nome AS cidade_nome
        FROM    tbl_cidade
        WHERE   tbl_cidade.cod_ibge IS NOT NULL
        AND     tbl_cidade.estado = '$consumidor_estado'
        AND     tbl_cidade.nome = '$consumidor_cidade'
  ORDER BY      cidade_nome
    ";
    $res = pg_query($con,$sql);

    $resultado = pg_fetch_object($res);
    $cidades = array("cidade_id" => $resultado->cidade, "cidade_nome" => $resultado->cidade_nome);

    return $cidades;
}

function validaEmail() {
    global $_POST;

    $email = $_POST["email"];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
}

if (isset($_POST["ajax_enviar"])) {

    $regras = array(
        "notEmpty" => array(
            "nome",
            "cpf_cnpj",
            "mensagem",
            "email",
            "celular",
            "cep",
            "endereco",
            "numero"
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
                        $msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
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

    if (strlen(trim($_POST["celular"])) > 0) {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $celularValida    = trim($_POST["celular"]);
        $celularValida    = $phoneUtil->parse("+55".$celularValida, "BR");
        $isValid          = $phoneUtil->isValidNumber($celularValida);
        $numberType       = $phoneUtil->getNumberType($celularValida);
        $mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

        if (!$isValid || $numberType != $mobileNumberType) {
            $msg_erro["msg"]["obg"] = utf8_encode("Número de celular inválido");
            $msg_erro["campos"][]   = 'celular';
            $retorno = array("erro" => $msg_erro);
        }
    }

    if (count($msg_erro["msg"]) > 0) {
        $retorno = array("erro" => $msg_erro);
    } else {
        $cidade          = trim($_POST["cidade"]);
        $estado          = trim($_POST["estado"]);
        $cpf_cnpj        = trim($_POST["cpf_cnpj"]);
        $data_nascimento = trim($_POST["data_nascimento"]);
        $email           = trim($_POST["email"]);
        $celular         = trim($_POST["celular"]);
        $cep             = trim($_POST["cep"]);
        $endereco        = utf8_decode(trim($_POST["endereco"]));
        $mensagem        = utf8_decode(trim($_POST["mensagem"]));
        $nome            = utf8_decode(trim($_POST["nome"]));
        $numero          = addslashes(trim($_POST["numero"]));
        $endereco        = addslashes($endereco);
        $mensagem        = addslashes($mensagem);
        $nome            = addslashes($nome);
        $cep             = str_replace('-', '', $cep);
        $cpf_cnpj        = str_replace(['-', '/', '.'], '', $cpf_cnpj);

        $sql = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND lower(descricao) = 'fale conosco'";
        $res = pg_query($con,$sql);
        $hd_chamado_origem = pg_fetch_result($res, 0, 'hd_chamado_origem');
        $hd_chamado_origem = (!empty($hd_chamado_origem)) ? $hd_chamado_origem : $origem;

        try {

            pg_query($con, "BEGIN");

            /*
             * - Verifica TODOS os atendentes
             * Responsáveis pela região E classificação
             * do chamado
             */
            $sql = "SELECT  DISTINCT
                        tbl_admin.admin as atendente
                    FROM    tbl_admin_atendente_estado
                    JOIN    tbl_admin ON tbl_admin.admin                = tbl_admin_atendente_estado.admin
                    WHERE   tbl_admin_atendente_estado.fabrica          = {$login_fabrica}
                    AND     tbl_admin.fabrica                           = {$login_fabrica}
                    AND     tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                    AND     tbl_admin.ativo          IS TRUE
                    AND     tbl_admin.nao_disponivel IS NULL";
            $resP    = pg_query($con,$sql);
            $atDoDia = pg_fetch_all($resP);

            /*
             * - Faz a contagem diária de chamados
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
             * com menor número de chamados
             * atendidos no dia para gravação do próximo chamado
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

            $hd_chamado        = pg_fetch_result($res, 0, "hd_chamado");
            $consumidor_cidade = getCidades($con, $cidade, $estado);
            $cidade            = $consumidor_cidade["cidade_id"];

            $sql = "INSERT INTO tbl_hd_chamado_extra
                        (
                            hd_chamado        ,
                            nome              ,
                            cpf,
                            email             ,
                            celular,
                            cidade,
                            cep,
                            endereco,
                            numero,
                            reclamado, 
                            origem,
                            hd_chamado_origem,
                            consumidor_revenda
                        )
                        VALUES
                        (
                            $hd_chamado,
                            '$nome',
                            '$cpf_cnpj',
                            '$email',
                            '$celular',
                             $cidade,
                            '$cep',
                            '$endereco',
                            '$numero',
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
?>

<!DOCTYPE HTML/>
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

            $("#data_nascimento").mask("99/99/9999");
            $("#celular").mask("(99) 99999-9999");
            $("#cep").mask("99999-999");

            $(document).on('keydown', '#cpf_cnpj', function (e) {
                var digit = e.key.replace(/\D/g, '');

                var value = $(this).val().replace(/\D/g, '');

                var size = value.concat(digit).length;

                $(this).mask((size <= 11) ? '000.000.000-00' : '00.000.000/0000-00');
            });
        });   

        function retiraAcentos(obj) {
            com_acento = '.áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
            sem_acento = '.aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
            resultado  = '';

            for (i = 0; i < obj.length; i++) {

                if (com_acento.search(obj.substr(i,1)) >= 0) {
                    resultado += sem_acento.substr(com_acento.search(obj.substr(i,1)),1);
                } else {
                    resultado += obj.substr(i,1);
                }

            }

            return resultado;
        }

        function buscaCEP(cep) {
            var cep = cep.replace(/[^\d]+/g,'');
            
            $.ajax({
                type: "GET",
                url:  "../../admin/ajax_cep.php",
                data: "cep="+escape(cep)+"method=database",
                cache: false,
                complete: function(resposta){
                    results = resposta.responseText.split(";");
                    console.log(results);

                    if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
                    if (typeof (results[1]) != 'undefined') $('#cidade').val(results[3]);
                    if (typeof (results[1]) != 'undefined') $('#estado').val(results[4]);

                    $('#endereco').prop('readonly', true);
                }
            });
        }    
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
    <h3 style="font-weight: bold;">CENTRAL DE ATENDIMENTO</h3>
    <div id="msg_erro" class="alert alert-danger alert-dismissible" style="display: none;">

    </div>
    <div id="msg_sucesso" class="alert alert-success alert-dismissible" style="display: none;">

    </div>
    <form id="form_fale_conosco" action='fale_conosco.php' enctype="multipart/form-data" method="post">
        <label style="color: black; margin-left: 3px;">FALE CONOSCO</label>
        <input type="hidden" name="ajax_enviar" value='true' />
        <br />
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="nome" >Nome Completo<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="nome" name="nome" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="cpf_cnpj" >CPF/CNPJ<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" placeholder="000.000.000-00 / 00.000.000/0000-00" />    
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="data_nascimento" >Data de Nascimento</label>
            <input type="text" class="form-control" id="data_nascimento" name="data_nascimento" placeholder="00/00/0000" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="email" >E-mail<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="email" name="email" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="celular" >Celular<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="celular" name="celular" placeholder="(00) 00000-0000"/>
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="cep" >CEP<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="cep" name="cep" onblur="buscaCEP(this.value)" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="endereco" >Endereço<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="endereco" name="endereco" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="numero" >Número<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="numero" name="numero" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="mensagem" >Mensagem<span class="campo_obrigatorio"> *</span></label>
            <textarea class="form-control" name="mensagem" rows="6" ></textarea>
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <input type="hidden" class="form-control" id="estado" name="estado" value="" />
            <input type="hidden" class="form-control" id="cidade" name="cidade" value="" />
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" align="center">
            <button style="background-color: #428bca;color: white;width: 100px;" type="button" id="enviar" class="btn btn-md" data-loading-text="ENVIANDO..." >Enviar</button>
        </div> <br />
    </form>
</div>

