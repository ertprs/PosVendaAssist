<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../plugins/fileuploader/TdocsMirror.php';
include '../../class/ComunicatorMirror.php';

$comunicatorMirror = new ComunicatorMirror();
$login_fabrica     = 188;

if (isset($_GET['departamento']) && $_GET['departamento'] == 'Assistência Técnica') {
    $selected_asstec = "selected='selected'";
    $cep             = trim($_GET['cep']);
}

function retira_especiais($texto){
    return str_replace(['-',' ','.','(',')','/'], "", $texto);
}

function verifica_revenda() {
    global $con, $login_fabrica, $cnpj, $telefone_revenda, $email_revenda, $razao_social;

    if (!empty($cnpj)) {
        $sql = "SELECT revenda
                FROM tbl_revenda
                WHERE cnpj = '{$cnpj}'";
        $res = pg_query($con, $sql);
    }

    if (pg_num_rows($res) > 0) {
        $revenda = pg_fetch_result($res, 0, "revenda");

        $sql = "UPDATE tbl_revenda SET
                    nome = '{$razao_social}',
                    fone  = '{$telefone_revenda}',
                    email = '{$email_revenda}'
                WHERE revenda = {$revenda};";
        $res = pg_query($con, $sql);
    } else {
        $sql = "INSERT INTO tbl_revenda
                (nome, cnpj, fone, email)
                VALUES
                ('{$razao_social}', '{$cnpj}', '{$telefone_revenda}', '{$email_revenda}')
                RETURNING revenda;";
        $res = pg_query($con, $sql);
        $revenda = pg_fetch_result($res, 0, "revenda");
    }

    return (empty($revenda)) ? "null" : $revenda;
}

/***** LISTANDO DADOS ********/
$array_estado = array(
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AM' => 'Amazonas',
    'AP' => 'Amapá',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MG' => 'Minas Gerais',
    'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí­',
    'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul',
    'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',
    'SP' => 'São Paulo',
    'TO' => 'Tocantins'
);
/******* AJAX ********/
if ($_GET["ajax_carrega_cidades"]) {
    $estado = strtoupper(trim($_GET["estado"]));

    if (empty($estado)) {
        $retorno = array("erro" => utf8_encode("Estado não informado"));
    } else {
        $sql = "SELECT DISTINCT nome, cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' ORDER BY nome ASC";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => "Erro ao carregar cidades");
        } else {
            $retorno = array("cidades" => array());

            while ($cidade = pg_fetch_object($res)) {
                $retorno["cidades"][] = utf8_encode(strtoupper($cidade->nome));
            }
        }
    }

    exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_produto"]) {
    $familia = strtoupper(trim($_GET["familia"]));

    if (empty($familia)) {
        $retorno = array("erro" => utf8_encode("Família não informada"));
    } else {
        $sql = "SELECT 
                     produto, 
                     referencia, 
                     descricao
                FROM tbl_produto 
                WHERE fabrica_i = {$login_fabrica} 
                    AND ativo IS TRUE 
                    AND familia = {$familia}";
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => "Erro ao carregar produtos");
        } else {
            $retorno = array("produtos" => array());

            while ($produto = pg_fetch_object($res)) {
                $retorno["produtos"][] = array(utf8_encode(strtoupper($produto->descricao)), $produto->produto);
            }
        }
    }

    exit(json_encode($retorno));
}

/************* SUBMIT DO FORMULÁRIO ************/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $departamento    = pg_escape_string(trim($_POST['departamento']));
    $nome            = pg_escape_string(trim($_POST['nome']));
    $email           = pg_escape_string(trim($_POST['email']));
    $email_revenda   = pg_escape_string(trim($_POST['email_revenda']));
    $telefone        = pg_escape_string(trim($_POST['telefone']));
    $telefone_revenda= pg_escape_string(trim($_POST['telefone_revenda']));
    $celular         = pg_escape_string(trim($_POST['celular']));
    $cpf             = pg_escape_string(trim($_POST['cpf']));
    $cnpj            = pg_escape_string(trim($_POST['cnpj']));
    $pais            = pg_escape_string(trim($_POST['pais']));
    $cep             = pg_escape_string(trim($_POST['cep']));
    $estado          = pg_escape_string(trim($_POST['estado']));
    $cidade          = pg_escape_string(trim($_POST['cidade']));
    $endereco        = pg_escape_string(trim($_POST['endereco']));
    $numero          = pg_escape_string(trim($_POST['numero']));
    $complemento     = pg_escape_string(trim($_POST['complemento']));
    $bairro          = pg_escape_string(trim($_POST['bairro']));
    $produto_id      = pg_escape_string(trim($_POST['produto']));
    $data_nf         = pg_escape_string(trim(formata_data($_POST['data_compra'])));
    $nota_fiscal     = pg_escape_string(trim($_POST['nota_fiscal']));
    $mensagem        = pg_escape_string(trim($_POST['mensagem']));
    $familia         = pg_escape_string(trim($_POST['familia']));
    $razao_social    = pg_escape_string(trim($_POST['razao_social']));
    $consumidor_revenda = pg_escape_string(trim($_POST['consumidor_revenda']));

	$familia = $familia ?$familia : "null";
	$produto_id = $produto_id ?$produto_id:  "null";
	$data_nf = $data_nf ?"'$data_nf'":  "null";
    $telefone = retira_especiais($telefone);
    $telefone_revenda = retira_especiais($telefone_revenda);
    $celular  = retira_especiais($celular);
    $cpf      = retira_especiais($cpf);
    $cnpj     = retira_especiais($cnpj);
    $cep      = retira_especiais($cep);

    if ($consumidor_revenda == "R") {
        $revenda_id = verifica_revenda();
    } else {
        $revenda_id = "null";
    }

    if (empty($msg_erro)) {
        /* OBTENDO A CIDADE */
        if (!empty($estado) && !empty($cidade)) {
            $sql_cidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' AND nome = '{$cidade}'";
            $res_cidade = pg_query($con, $sql_cidade);
            if (pg_num_rows($res_cidade) > 0) {
                $cidade_id = pg_fetch_result($res_cidade, 0, 'cidade');
            } else {
                $msg_erro = "Ocorreu um erro ao selecionar a cidade <br />";
            }    
        }      
        
        $sql_busca_origem = "SELECT 
                                hd_chamado_origem AS origem_id
                            FROM  tbl_hd_chamado_origem
                            WHERE fabrica       = {$login_fabrica}
                                  AND descricao = 'Fale Conosco'
                            LIMIT 1";
        $res_busca_origem = pg_query($con, $sql_busca_origem);
        if (pg_num_rows($res_busca_origem) > 0) {

            $origem_id        = pg_fetch_result($res_busca_origem, 0, 'origem_id');

            $sql_busca_admin  = "SELECT COUNT(hc.hd_chamado) AS qtde,
                                    a.admin,
                                    a.email
                                FROM tbl_admin a
                                JOIN tbl_hd_origem_admin hoa ON hoa.admin    = a.admin 
                                    AND hoa.fabrica           = {$login_fabrica}
                                    AND hoa.hd_chamado_origem = {$origem_id}
                                LEFT JOIN tbl_hd_chamado hc  ON hc.atendente = a.admin 
                                    AND hc.fabrica = {$login_fabrica}
                                    AND hc.status  = 'Aberto'
                                WHERE a.fabrica    = {$login_fabrica}
                                    AND a.ativo IS TRUE
                                    AND a.atendente_callcenter IS TRUE
                                GROUP BY a.admin
                                ORDER BY qtde ASC
                                    LIMIT 1";

            $res_busca_admin  = pg_query($con, $sql_busca_admin);

            if (pg_num_rows($res_busca_admin) > 0) {
                $id_atendente    = pg_fetch_result($res_busca_admin, 0, 'admin');
                $email_atendente = pg_fetch_result($res_busca_admin, 0, 'email');

                /* BEGIN */
                pg_query($con, "begin");

                /* abre chamado */
                $sql_chamado  = "INSERT INTO tbl_hd_chamado (
                                    admin,
                                    data,
                                    atendente,
                                    fabrica_responsavel,
                                    fabrica,
                                    titulo,
                                    status,
                                    categoria,
									hd_classificacao
                                ) VALUES (
                                    {$id_atendente},
                                    CURRENT_TIMESTAMP,
                                    {$id_atendente},
                                    {$login_fabrica},
                                    {$login_fabrica},
                                    'Fale Conosco',
                                    'Aberto',
									'reclamacao_produto',
									{$departamento}
                                ) RETURNING hd_chamado";

                $res_chamado  = pg_query($con, $sql_chamado);
                $msg_erro    .= pg_last_error($con);
                
                if ( (pg_num_rows($res_chamado) > 0) && !strlen($msg_erro) > 0) {
                    $hd_chamado_id     = pg_fetch_result($res_chamado, 0, 'hd_chamado');
                    $campos_add        = json_encode(array("pais" => $pais));
                    $sql_chamado_extra = "INSERT INTO tbl_hd_chamado_extra (
                                            hd_chamado,
                                            nome,
                                            email,
                                            fone,
                                            celular,
                                            origem,
                                            hd_chamado_origem,
                                            cpf,
                                            array_campos_adicionais,
                                            cep,
                                            cidade,
                                            endereco,
                                            numero,
                                            complemento,
                                            bairro,
                                            produto,
                                            data_nf,
                                            nota_fiscal,
                                            revenda,
                                            consumidor_revenda,
                                            revenda_nome,
                                            revenda_cnpj,
											reclamado
                                        ) VALUES (
                                             {$hd_chamado_id},
                                            '{$nome}',
                                            '{$email}',
                                            '{$telefone}',
                                            '{$celular}',
                                            'Fale Conosco',
                                             {$origem_id},
                                            '{$cpf}',
                                            '{$campos_add}',
                                            '{$cep}',
                                             {$cidade_id},
                                            '{$endereco}',
                                            '{$numero}',
                                            '{$complemento}',
                                            '{$bairro}',
                                             {$produto_id},
                                            {$data_nf},
                                            '{$nota_fiscal}',
                                            {$revenda_id},
                                            '{$consumidor_revenda}',
                                            '{$razao_social}',
                                            '{$cnpj}',
											'{$mensagem}'
                                        )";
                    $res_chamado_extra = pg_query($con, $sql_chamado_extra);
                    $msg_erro         .= pg_last_error($con);

                }

                /* upload de nota fiscal */
                if (isset($_FILES['anexo_nf']) && empty($msg_erro) and !empty($_FILES['anexo_nf']['name'])) {
                    $data_hora = date("Y-m-d\TH:i:s");
                    $destino   = '/tmp/';
                    $tamanho   = 1024 * 1024 * 2;
                    $extensoes = array('jpg', 'png', 'gif', 'pdf');

                    $anx_nf    = $_FILES["anexo_nf"];
                    $extensao  = strtolower(end(explode('.', $_FILES['anexo_nf']['name'])));
                    
                    if (array_search($extensao, $extensoes) === false) {
                        $msg_erro .= "Por favor, envie arquivos com as seguintes extensões: jpg, png, pdf ou gif <br />";
                    }

                    if ($tamanho < $_FILES['anexo_nf']['size']) {
                        $msg_erro .= "O arquivo enviado é muito grande, envie arquivos de até 2Mb. <br />";
                    }

                    $nome_final = $login_fabrica.'_'.$hd_chamado_id.'.jpg';
                    $caminho    = $destino.$nome_final;

                    if (move_uploaded_file($_FILES['anexo_nf']['tmp_name'], $caminho)) {
                        $tdocsMirror = new TdocsMirror();
                        $response    = $tdocsMirror->post($caminho);

                        if(array_key_exists("exception", $response)){
                            header('Content-Type: application/json');
                            echo json_encode(array("exception" => "Ocorreu um erro ao realizar o upload: ".$response['message']));
                            exit;
                        }
                        $file = $response[0];

                        foreach ($file as $filename => $data) {
                            $unique_id = $data['unique_id'];
                        }

                        $sql_verifica = "SELECT * 
                                    FROM tbl_tdocs
                                    WHERE fabrica = {$login_fabrica} 
                                    AND contexto  = 'callcenter'
                                    AND tdocs_id  = '$unique_id'";
                        $res_verifica = pg_query($con,$sql_verifica);
                        if (pg_num_rows($res_verifica) == 0){
                            $obs = json_encode(array(
                                "acao"     => "anexar",
                                "filename" => "{$nome_final}",
                                "filesize" => "".$_FILES['anexo_nf']['size']."",
                                "data"     => "{$data_hora}",
                                "fabrica"  => "{$login_fabrica}",
                                "page"     => "externos/callcenter/callcenter_cadastra_ingco.php",
                                "typeId"   => "notafiscal",
                                "descricao"=> ""
                            ));

                            $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
                                values('$unique_id', $login_fabrica, 'callcenter', 'ativo', '[$obs]', 'callcenter', $hd_chamado_id);";  
                            $res       = pg_query($con, $sql);
                            $msg_erro .= pg_last_error($con);

                        }
                    } else {
                      $msg_erro .= "Não foi possível enviar o arquivo, tente novamente <br />";
                    }
                }

                /* gravando mensagem */
                $sql_mensagem =  "INSERT INTO tbl_hd_chamado_item (comentario,hd_chamado) VALUES ('{$mensagem}', {$hd_chamado_id});";
                $res_mensagem = pg_query($con, $sql_mensagem);
                $msg_erro    .= pg_last_error($con);

                /* ROLLBACK */
                if (strlen($msg_erro) > 0) {

                    if (pg_last_error()) {
                        $msg_erro = "Erro ao cadastrar atendimento, favor entrar em contato com o fabricante ";
                    }

                    pg_query($con, "rollback");
                } else {

                    pg_query($con, "commit"); 

                    $titulo_email = "Atendimento Fale Conosco Ingco - $hd_chamado_id"; 
                    $msg_email    = "<h3>Novo atendimento recebido através do site:</h3> <br /><br />
                                    <b>Nome............:</b>   {$nome} <br />
                                    <b>E-mail...........:</b>  {$email} <br />
                                    <b>CPF/CNPJ.....:</b>      {$cpf_cnpj} <br />
                                    <b>Telefone........:</b>   {$telefone} <br />
                                    <b>Celular..........:</b>  {$celular} <br />
                                    <b>País..............:</b> {$pais} <br />
                                    <b>CEP...............:</b> {$cep} <br />
                                    <b>Estado...........:</b>  {$estado} <br />
                                    <b>Cidade...........:</b>  {$cidade} <br />
                                    <b>Endereço.......:</b>    {$endereco} <br />
                                    <b>Número.........:</b>    {$numero} <br />
                                    <b>Complemento:</b>        {$complemento} <br />
                                    <b>Mensagem.....:</b>      {$mensagem} <br />";   

                    $array_emails = array(
                        "ely@ingco.com.br", 
                        "sac@ingco.com.br", 
                        "bombas@ingco.com.br",
                        $email_atendente
                    );
                    
                    /* envia e-mail */
                    foreach ($array_emails AS $email) {
                        try {
                            $comunicatorMirror->post($email, utf8_encode("$titulo_email"), utf8_encode("$msg_email"), "smtp@posvenda");
                        } catch (\Exception $e) {
                            $msg_erro .= $e;
                        }
                    }

                    $protocolo = $hd_chamado_id;

                    $msg_sucesso_procotolo = "<h5>Nº de Protocolo: <i><b>{$protocolo}</b></i> <br /></h5>";
                    header("Location: $PHP_SELF?hd_chamado=$hd_chamado_id");
                }
            } else {
		$msg_erro = "Ocorreu um erro ao gravar o atendimento, favor entrar em contato com a fábrica.";
	    }
        }
        
    }
}

?>

<!DOCTYPE HTML />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />

    <!-- jQuery -->
    <script type="text/javascript" src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script type="text/javascript" src="../../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.min.js" ></script>
    <script type="text/javascript" src="../../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script type="text/javascript" src="../../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script type="text/javascript" src="../../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script type="text/javascript" src="../../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <!-- Bootstrap -->
    <script type="text/javascript" src="../../bootstrap/js/bootstrap.min.js" ></script>
    <link rel="stylesheet" type="text/css" href="../../bootstrap/css/bootstrap_ ajuste.css" />
    <link rel="stylesheet" type="text/css" href="../../bootstrap/css/bootstrap.min.css" />
    <link rel='stylesheet' type='text/css' href='../../plugins/select2/select2.css' />
    <link rel='stylesheet' type='text/css' href='../../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.min.css' />
    
    <!-- Plugins Adicionais -->    
    <script src='../../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
    <script src='../../plugins/select2/select2.js'></script>    
    <script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
    <script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
    <link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

    <script type="text/javascript" src='../../plugins/shadowbox_lupa/shadowbox.js'></script>
    <link rel='stylesheet' type='text/css' href='../../plugins/shadowbox_lupa/shadowbox.css' />

    <style>
    body {
        font-size: 13px;
        line-height: 22px;
        font-family:Arial, Helvetica, sans-serif;
        color: #777777;
    }

    div.container {
        max-width: 595px;
    }

    legend {
        font-size: 13px;
        line-height: 22px;
        font-family:Arial, Helvetica, sans-serif;
        color: #777777;
        margin-bottom: 40px;
    }

    .campo_obrigatorio {
        color: #777777;
    }

    div.has-error label {
        color: #ED333A !important;
    }

    div.has-error input, div.has-error textarea, div.has-error select, div.has-error div.trigger {
        border-color: #ED333A !important;
    }

    #enviar {
        padding: 10px 16px;
        font-family: Open Sans;
        font-weight: 700;
        padding: 14px 34px;
    }

    span.loading {
        color: #58b847;
        margin-left: 20px;
    }

    .alert {
        border: medium none;
        font-weight: 300;
        padding: 15px 20px;
        border-radius: 3px;
    }

    .alert-danger {
        background-color: #EE6057;
        color: #FFF;
    }

    .alert-success {
        background-color: #58b847 !important;
        color: #FFF;
    }

    fieldset {border: none;}

    textarea,
    select[size],
    select[multiple] {
      height: auto;
    }

    textarea {
      min-height: 100px;
      overflow: auto;
      resize: vertical;
      width: 100%;
    }

    optgroup {
      font-style: normal;
      font-weight: normal;
    }

    textarea,
    select,
    input[type="date"],
    input[type="datetime"],
    input[type="datetime-local"],
    input[type="email"],
    input[type="month"],
    input[type="number"],
    input[type="password"],
    input[type="search"],
    input[type="tel"],
    input[type="text"]:not(.lupa),
    input[type="time"],
    input[type="url"],
    input[type="week"] {
        display: block;
        outline: 0;
        margin: 0 0 25px 0;
        text-align: left;
        vertical-align: top;
        height:40px;
        max-width: 100%;
        width: 100%;
        padding:0px 20px;
        font-size: 14px;
        border-width: 1px;
        border-style: solid;
        border-color: #e0e0e0;
        background-color:#ffffff;
        position: relative;
        font-weight: 400;
        -webkit-backface-visibility: hidden;
        -webkit-transition: all 300ms;
        transition: all 300ms;
        border-radius: 0px !important;
        outline: none !important;
    }

    textarea {
        padding:10px 20px;
    }

    label {
        font-weight: 400;
        font-size: 12px;
        color: #777777;
    }

    input[type="radio"],
    input[type="checkbox"] {
        margin: 5px 0;
        display: inline-block;
    }

    #div_legenda {
        font-size: 32px;
        color: #0451a1;
        text-align: center;
        font-family: Oxygen;
        font-weight: 400;
        font-style: normal;
    }
    #barra {
        background-color: red; height: 1px; border: 0;
    }
    #infos {
        padding-bottom: 25px;
    }
    #div_legenda {
        text-align: center !important;
    }
    #div_legenda #barra {
        /*display: inline;*/
        width: 20%;
        margin: 0 auto;
    }
    </style>

    <!-- SCRIPTS -->
    <script>
        $(function() {

            Shadowbox.init();

            $("span[rel=lupa]").click(function () {
                $.lupa($(this));
            });

            $("body").scrollTop();

            // Mascaras, function()
            $("#cpf").mask("999.999.999-99");
            $("#cnpj").mask("99.999.999/9999-99");
            $("#telefone").mask("(99) 9999-9999");
            $("#telefone_revenda").mask("(99) 99999-9999");
            $("#celular").mask("(99) 99999-9999");
            $("#data_compra").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
            
            $("#familia").filter(function(){
                return $(this).find("option:checked").val() != "";
            }).change();

            // select2
            $("#pais").select2();
            $("#cidade").select2();
            $("#estado").select2();
            $("#familia").select2();
            $("#produto").select2();

            // CEP
            $("#cep").on("blur", function(){
                var cep = $(this).val();
                busca_cep(cep);
            });

            $("#cep").blur();
            $("#pais").change();
            $("#departamento").change();
                    $(".consumidor").show();
                    $(".revenda").hide();


        });

        function retorna_produto (retorno) {
            $("#produto_referencia").val(retorno.referencia);
            $("#produto_descricao").val(retorno.descricao);
        }

        // Estado, funtion()
        $(document).on('change', '#estado', function(){
            var value = $(this).val();

            if (value.length > 0) {
                carregaCidades(value);
            } else {
                $("#cidade").find("option:first").nextAll().remove();
                $("#cidade").trigger("update");
            }
        });

        // Família - Produto, function()
        $(document).on('change', '#familia', function(){
            var familia        = $(this).val();
            var select_produto = $("#produto");

            $.ajax({
                url: "callcenter_cadastra_ingco.php",
                type: "get",
                data: { ajax_carrega_produto: true, familia: familia },
                beforeSend: function() {
                    $(select_produto).find("option:first").nextAll().remove();
                }
            }).done(function(data) {
                data = JSON.parse(data);

                if (data.erro) {
                    alert(data.erro);
                } else {

                    var option = $("<option></option>", {
                        value: "",
                        text: "Selecione",
                        selected : "selected"
                    });

                    $(select_produto).append(option);

                    data.produtos.forEach(function(produto) {
                        var option = $("<option></option>", {
                            value: produto[1],
                            text: produto[0]
                        });

                        $(select_produto).append(option);
                    });

                    $("#produto_label span.loading").remove();
                }

                $(select_produto).trigger("update");
            });
        });

        $(document).on('change', '#pais', function(){
            var selected = $("#pais").val();
            if (selected == 'BRL' || selected == 'BR') {
                $("#div_cep, #div_cidade, #div_estado").show();
                $("#cep, #cidade, #estado").find('span').attr("style", "width:100% !important;");
            } else {
                $("#div_cep, #div_cidade, #div_estado").hide();
            }
        }); 

        function carregaCidades(estado,cidade) {
            var select_cidade = $("#cidade");

            $.ajax({
                url: "callcenter_cadastra_ingco.php",
                type: "get",
                data: { ajax_carrega_cidades: true, estado: estado },
                beforeSend: function() {
                    $(select_cidade).find("option:first").nextAll().remove();
                }
            }).done(function(data) {
                data = JSON.parse(data);

                if (data.erro) {
                    alert(data.erro);
                } else {
                    data.cidades.forEach(function(cidade) {
                        var option = $("<option></option>", {
                            value: cidade,
                            text: cidade
                        });

                        $(select_cidade).append(option);
                    });
                    if(cidade != undefined){
                        var indexCidade = $("#cidade option").removeAttr('selected').filter('[value="'+cidade+'"]').index();
                        $('#cidade option:eq('+indexCidade+')').prop('selected', true).trigger('change');
                    }
                    $("#cidade_label span.loading").remove();
                }

                $(select_cidade).trigger("update");
            });
        }

        function valida_campos() {
            var msg_erro = false;
            



					if (!$("#nome").val()){
                        $("#div_nome").addClass("error");
                        msg_erro = true;
					}


					if (!$("#cep").val()){
                        $("#div_cep").addClass("error");
                        msg_erro = true;
					}

					if (!$("#estado").val()){
                        $("#div_estado").addClass("error");
                        msg_erro = true;
					}

					if (!$("#cidade").val()){
                        $("#div_cidade").addClass("error");
                        msg_erro = true;
					}

					if (!$("#mensagem").val()){
                        $("#div_mensagem").addClass("error");
                        msg_erro = true;
                    }

					if (!$("#celular").val()){
                        $("#div_celular").addClass("error");
                        msg_erro = true;
					}

					if (!$("#endereco").val()){
                        $("#div_endereco").addClass("error");
                        msg_erro = true;
					}

					if (!$("#bairro").val()){
                        $("#div_bairro").addClass("error");
                        msg_erro = true;
					}

					if (!$("#numero").val()){
                        $("#div_numero").addClass("error");
                        msg_erro = true;
					}

                if (msg_erro == true) {
                    $("#msg_erro").html("Preencha os campos obrigatórios");
                    $("#msg_erro").show();
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                    return false;    
                }

            frm.submit();
            $("#msg_erro").hide();
        }

        function busca_cep(cep) {
            $("#msg_erro").removeClass("alert alert-erro");
            var cep = cep;
            var method = "webservice";

            if (cep.length > 0) {

                $.ajax({
                    async: true,
                    url: "../../admin/ajax_cep.php",
                    type: "GET",
                    data: { cep: cep, method: method },
                    beforeSend: function() {
                    },
                    error: function(xhr, status, error) {
                        $("#msg_erro").addClass("alert alert-erro");
                        $("#msg_erro").html("<h4>CEP errado.</h4>");

                    },
                    success: function(data) {
                        results = data.split(";");

                        if (results[0] != "ok") {
                            alert(results[0]);
                        } else {
                            var indexEstado = $("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').index();
                            $("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').attr('selected', true);
                            $('#estado option:eq('+indexEstado+')').prop('selected', true).trigger('change');

                            $("#bairro").val(results[2]);

                            carregaCidades(results[4],results[3]);

                            // $("#cidade").val(results[3]);

                            if (results[1].length > 0) {
                                $("#endereco").val(results[1]);
                            }
                        }

                        if ($("#endereco").val().length == 0) {
                            $("#endereco").focus();

                        }

                        $("#estado_label span.loading").remove();
                        $("#endereco_label span.loading").remove();
                        $("#cidade_label span.loading").remove();
                    }
                });
            }
        }
    </script>
</head>
<body>
<?php
$msg_sucesso_procotolo = $_REQUEST['hd_chamado'];
if($msg_sucesso_procotolo) $msg_sucesso_procotolo = "Atendimento $msg_sucesso_procotolo aberto com sucesso";
?>
<div class="container" >
    <form name="form_fale_conosco" id="form_fale_conosco" method="post" action='<?=$PHP_SELF?>'  enctype="multipart/form-data" onsubmit="valida_campos(); return false;">
        <h3 id='div_legenda'>
            <span>Contato</span>
            <hr id="barra" />
        </h3>

        <div id="msg_erro" class="alert alert-danger" <?php echo (!empty($msg_erro) ? 'style="display: block !important;"' : 'style="display: none !important;"') ?>>
            <?php echo (!empty($msg_erro) ? $msg_erro : '') ?>
        </div>
        <div id="msg_sucesso" class="alert alert-success" <?php echo (!empty($msg_sucesso_procotolo) || !empty($msg_sucesso) ? 'style="display: block !important;"' : 'style="display: none !important;"') ?> >
            <?php
                if (!empty($msg_sucesso_procotolo)) {
                    echo $msg_sucesso_procotolo;
                } else if (!empty($msg_sucesso)) {
                    echo $msg_sucesso;
                }
            ?>
        </div>

        <div class="control-group span12" id='infos'>
            Preencha o formulário abaixo para entrar em contato com nosso time de consultores. <br />
            <span style='font-style: italic;'>* campos obrigatórios</span>
        </div>

        <!-- row -->
        <div class='row-fluid'>
             <div class="control-group span4" id='div_departamento'>
                <label for="departamento">Escolha um departamento</label>
                    <select name='departamento' id='departamento' class='form-control'>
						<?
							$sql = "select hd_classificacao, descricao
									from tbl_hd_classificacao
									where fabrica  = $login_fabrica";
							$res = pg_query($con, $sql);
							for($i=0;$i<pg_num_rows($res);$i++) {
								$hd_classificacao = pg_fetch_result($res,$i, 'hd_classificacao');
								$descricao = pg_fetch_result($res,$i, 'descricao');
								echo "<option value='$hd_classificacao'>$descricao</option>";
							}
	
						?>
                </select>
            </div>
        </div>
        <div id="dados_formulario">
            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6 revenda" id="div_cnpj">
                    <label for="cpf_cnpj" >CNPJ</label>
                    <input type="text" class="form-control" id="cnpj" name="cnpj" />
                </div>
                <div class="control-group span6 revenda" id="div_razao">
                    <label for="razao_social" >Razão Social </label>
                    <input type="text" class="form-control" id="razao_social" name="razao_social" />
                </div>
            </div>
            <div class="row-fluid">
                <div class="control-group span6 revenda" id="div_razao">
                    <label for="razao_social">Telefone </label>
                    <input type="text" class="form-control" id="telefone_revenda" name="telefone_revenda" />
                </div>
                <div class="control-group span6 revenda" id="div_email_revenda">
                    <label for="nome" >E-mail(*)</label>
                    <input type="text" class="form-control" id="email_revenda" name="email_revenda" />
                </div>
            </div>
            <div class="row-fluid">
                <div class="control-group span12" id="div_nome">
                    <label for="nome" >Nome Consumidor*</label>
					<input type="text" class="form-control" id="nome" name="nome" value='<?=$nome?>' />
                </div>
            </div>
            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" id="div_email">
                    <label for="email" >E-mail Consumidor</label>
                    <input type="text" class="form-control" id="email" name="email" />
                </div>
                <div class="control-group span6" id="div_cpf">
                    <label for="cpf_cnpj" >CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" />
                </div>
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" id="div_telefone">
                    <label for="telefone" >Telefone </label>
                    <input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
                </div>

                <div class="control-group span6" id="div_celular">
                    <label for="telefone" >Celular* </label>
                    <input type="text" class="form-control " id="celular" class="celular" name="celular" />
                </div>
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" id="div_pais">
                    <label for="pais" >País </label>
                    <select class="form-control" id="pais" name="pais" >
                        <?php
                        $sql_pais     = "SELECT 
                                                pais, nome
                                        FROM    tbl_pais 
                                        WHERE   nome IS NOT NULL
                                            AND pais IS NOT NULL;";
                        $res_pais          = pg_query($con, $sql_pais);
                        while ($array_pais = pg_fetch_array($res_pais)) {
                            if ($array_pais['pais'] == "BR" || $array_pais['pais'] == "BRL") {
                                $selected_pais = "selected='selected'";
                            } else {
                                $selected_pais = "";
                            }
                            echo "<option value='{$array_pais['pais']}' ".$selected_pais." >{$array_pais['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="control-group span6" style="display: none;" id='div_cep'>
                    <label for="cep" >CEP* </label>
                    <input type="text" class="form-control" id="cep" name="cep" value="<?=$cep;?>" />
                </div>
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" style="display: none;" id="div_estado" >
                    <label for="estado" id="estado_label">Estado* </label>
                    <select class="form-control" id="estado" name="estado" >
                        <option value='' disabled="disabled" selected="selected">---</option>
                        <?php
                        foreach ($array_estado as $sigla => $nome) {
                            echo "<option value='{$sigla}' >{$nome}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="control-group span6" style="display: none;" id="div_cidade">
                    <label id="cidade_label" for="cidade" >Cidade* </label>
                    <select class="form-control" id="cidade" name="cidade" >
                        <option value='' disabled="disabled" selected="selected">---</option>
                    </select>
                </div>
            </div>
            <!--// row -->

             <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" id="div_endereco">
                    <label for="endereco" id="endereco_label">Endereço* </label>
                    <input type="text" class="form-control" id="endereco" name="endereco" maxlength="70" />
                </div>
                <div class="control-group span6" id="div_bairro">
                    <label for="bairro" id="bairro_label">Bairro* </label>
                    <input type="text" class="form-control" id="bairro" name="bairro" maxlength="70" />
                </div>
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" id="div_numero">
                    <label for="numero" >Número* </label>
                    <input type="text" class="form-control col-lg-2" id="numero" name="numero" maxlength="20" />
                </div>
                
                <div class="control-group span6" id="div_complemento" >
                    <label for="complemento" >Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" maxlength="40" />
                </div>
            </div>
            <!--// row -->   

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span4" id="div_familia">
                    <label for="familia">Família </label>
                        <select name='familia' id='familia' class='form-control'>
                        <option value='' disabled="disabled" selected="selected">---</option>
                        <?php
                            $sql_familia = "SELECT 
                                                familia, descricao 
                                            FROM  tbl_familia 
                                            WHERE fabrica = {$login_fabrica}
                                                  AND      ativo IS TRUE 
                                                  ORDER BY descricao ASC";
                            $res_familia = pg_query($con, $sql_familia);
                            if (pg_num_rows($res_familia) > 0) {
                                while ($array_familia = pg_fetch_array($res_familia)) {

                                    $selected = ($_GET['familia'] == $array_familia['familia']) ? 'selected' : '';

                                    echo "<option value='{$array_familia['familia']}' {$selected} >{$array_familia['descricao']}</option>";
                                }
                            }
                        ?>
                    </select>
                </div>   
                <div class="control-group span8" id="div_produto">
                    <label for="produto" id="produto_label">Produto </label>
                        <select name='produto' id='produto' class='form-control'>
                            <option value='' disabled="disabled" selected="selected">---</option>
                    </select>
                </div> 
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span6" id="div_data_compra">
                    <label for="data_compra" >Data da Compra </label>
                    <input type="text" class="form-control" id="data_compra" name="data_compra" />
                </div>
                <div class="control-group span6" id="div_nota_fiscal">
                    <label for="nota_fiscal" >Nota Fiscal </label>
                    <input type="text" class="form-control" id="nota_fiscal" name="nota_fiscal" />
                </div>
            </div>

            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span12" id="div_mensagem">
                    <label for="mensagem" >Mensagem* </label>
                    <textarea class="form-control" name="mensagem" id="mensagem" rows="6" ></textarea>
                </div>
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="control-group span12" id="div_anexo_nf">
                    <label for="anexo_nf" >Anexo de Nota Fiscal </label>
                    <input type="file" class="form-control" name="anexo_nf" id="anexo_nf" />
                </div>
            </div>
            <!--// row -->

            <!-- row -->
            <div class="row-fluid">
                <div class="span4" >
                    <button type="submit" id="enviar" name="btnacao" class="btn btn-lg btn-danger btnacao" >Enviar</button>
                </div>    
            </div>
        </div>
        <!--// row -->
    </form>
</div>

<br /><br />

</body>
<style type="text/css">
    .select2-hidden-accessible {
        border: 0 !important;
        clip: rect(0 0 0 0) !important;
        height: 1px !important;
        margin: -1px !important;
        overflow: hidden !important;
        padding: 0 !important;
        position: relative !important;
        width: 1px !important;
    }
    span.select2.select2-container.select2-container--default {
        width: 100% !important;   
    }
    span.select2-selection.select2-selection--single {
        margin: 0 0 25px 0 !important; 
        height: 41px !important;
    }
    div.error span.select2-selection.select2-selection--single {
        height: 41px !important;
        border-color: #b94a48;
    }
    span.select2-selection__rendered {
        padding-top: 7px !important;
    }


</style>
</html>
