<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../plugins/fileuploader/TdocsMirror.php';
include_once '../../class/communicator.class.php';
include_once "../../class/aws/s3_config.php";
include_once S3CLASS;
include_once "../../class/tdocs.class.php";

if ($_serverEnvironment == 'development'){
    $URL_BASE = "http://novodevel.telecontrol.com.br/~felipe/posvenda/externos/viapol/";
    $admin = 12002;//devel
}else{
    $URL_BASE = "https://posvenda.telecontrol.com.br/assist/externos/viapol/";
    $admin = 12103;//prod
}

$login_fabrica = 189;
$array_tipos = [
            "R" => "Representante",
            "V" => "Viapol",
            "C" => "Clientes",
            "T" => "Transportadora"
        ];
$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
$tDocs       = new TDocs($con, $login_fabrica);


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

if (!function_exists('converte_data')) {
    function converte_data($date)
    {
        $date = explode("-", preg_replace('/\//', '-', $date));
        $date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
        if (sizeof($date)==3)
            return $date2;
        else return false;
    }
}

if (!function_exists('valida_consumidor_cpf')) {
    function valida_consumidor_cpf($cpf) {
        global $con;

        $cpf = preg_replace("/\D/", "", $cpf);

        if (strlen($cpf) > 0) {
            $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                return false;
            }else{
                return true;
            }
        }
    }
}

if (!function_exists('Valida_Data')) {
    function Valida_Data($dt){
        $data = explode("/","$dt");
        $d = $data[0];
        $m = $data[1];
        $y = $data[2];

        $res = checkdate($m,$d,$y);
        if ($res == 1){
           return "ok";
        } else {
           return "erro";
        }
    }
}

function calcula_horas_uteis($ini_exp, $fim_exp, $data_prov, $prazo) {
    global $con;

    if (strtotime($data_prov) > strtotime($fim_exp)) {

        $sqlCal         = pg_query($con, "SELECT '$data_prov'::timestamp - '{$fim_exp}'::timestamp  AS prazoNaoUsado");
        $prazoNaoUsado = pg_fetch_result($sqlCal, 0, 'prazoNaoUsado');

        $ini_exp   = date('Y-m-d H:i:s', strtotime(date("$ini_exp")." +1 day"));
        $fim_exp   = date('Y-m-d H:i:s', strtotime(date("$fim_exp")." +1 day"));

        $sqlCal   = pg_query($con, "SELECT '$ini_exp'::timestamp + INTERVAL '{$prazoNaoUsado} hours' AS data_px");
        $data_px  = pg_fetch_result($sqlCal, 0, 'data_px');

        return calcula_horas_uteis($ini_exp, $fim_exp, $data_px, $prazoNaoUsado);

    } else {
        return $data_prov;
    }
}

$msg_erro = [];
$msg      = "";
if ($_POST) {
    if (!filter_input(INPUT_POST,"tipo")) {
        $msg_erro["msg"][] = "Preencha o campo <b>Identifique-se</b>";
        $msg_erro['campos'][] = "tipo";
    }

    if (!filter_input(INPUT_POST,"nome")) {
        $msg_erro["msg"][] = "Preencha o campo <b>Titular da Nota</b>";
        $msg_erro['campos'][] = "nome";
    }

    if (!filter_input(INPUT_POST,"email",FILTER_VALIDATE_EMAIL)) {
        $msg_erro["msg"][] = "Preencha o campo <b>E-mail</b>";
        $msg_erro['campos'][] = "email";
    }


    if (!filter_input(INPUT_POST,"nf")) {
        $msg_erro["msg"][] = "Preencha o campo <b>N° NF</b>";
        $msg_erro['campos'][] = "nf";
    }

    if (count($_POST["produto_referencia"]) == 0) {
        $msg_erro["msg"][] = "Preencha o campo <b>Produto</b>";
        $msg_erro['campos'][] = "produto";
    }

    if (!filter_input(INPUT_POST,"produto_aplicado")) {
        $msg_erro["msg"][] = "Preencha o campo <b>Produto foi aplicado</b>";
        $msg_erro['campos'][] = "produto_aplicado";
    }

    if (!filter_input(INPUT_POST,"mensagem")) {
        $msg_erro["msg"][] = "Preencha o campo <b>Descrição ocorrência</b>";
        $msg_erro['campos'][] = "mensagem";
    }

    if (count($msg_erro["msg"]) == 0) {
     
        $tipo               = trim(filter_input(INPUT_POST,"tipo",FILTER_SANITIZE_EMAIL));
	$consumidor_nome    = trim(filter_input(INPUT_POST,"nome",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
	$consumidor_nome    = pg_escape_string($consumidor_nome);
        $empresa            = trim(filter_input(INPUT_POST,"empresa",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_email   = trim(filter_input(INPUT_POST,"email",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_celular = trim(filter_input(INPUT_POST,"celular",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $produto            = trim(filter_input(INPUT_POST,"produto",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $nf                 = trim(filter_input(INPUT_POST,"nf",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $qtde_produto       = trim(filter_input(INPUT_POST,"qtde_produto",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $produto_aplicado   = trim(filter_input(INPUT_POST,"produto_aplicado",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $lote               = trim(filter_input(INPUT_POST,"lote"));
        $mensagem           = trim(filter_input(INPUT_POST,"mensagem"));
        $pedido             = trim(filter_input(INPUT_POST,"pedido"));
        $codigo_posto             = trim(filter_input(INPUT_POST,"codigo_posto"));
        $anexos             = $_POST["anexo"];

        $endereco   = trim(filter_input(INPUT_POST,"endereco",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $numero   = trim(filter_input(INPUT_POST,"numero",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $complemento   = trim(filter_input(INPUT_POST,"complemento",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $bairro   = trim(filter_input(INPUT_POST,"bairro",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $cep   = trim(filter_input(INPUT_POST,"cep",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $cpf_cnpj   = trim(filter_input(INPUT_POST,"cpf_cnpj",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $nome_cidade   = trim(filter_input(INPUT_POST,"nome_cidade",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $estado   = trim(filter_input(INPUT_POST,"estado",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_revenda   = trim(filter_input(INPUT_POST,"consumidor_revenda",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));

        $cidade = "";

        if (strlen($nome_cidade) > 0 && strlen($estado) > 0) {
            $cidade = getCidades($con, $nome_cidade,$estado);
        }

        if (strlen(str_replace(["(",")","-"," "], "", $consumidor_celular)) == 10) {
            $campoFone = "fone,";
            $valorFone = "'{$consumidor_celular}',";
        } else {
            $campoFone = "celular,";
            $valorFone = "'{$consumidor_celular}',";
        }


        $campoPedido = "";
        $valorPedido = "";

        if (strlen($pedido) > 0) {
            $campoPedido .= "pedido,";
            $valorPedido .= "$pedido,";
        }

        if (strlen($endereco) > 0) {
            $campoPedido .= "endereco,";
            $valorPedido .= "'$endereco',";
        }

        if (strlen($numero) > 0) {
            $campoPedido .= "numero,";
            $valorPedido .= "'$numero',";
        }

        if (strlen($complemento) > 0) {
            $campoPedido .= "complemento,";
            $valorPedido .= "'$complemento',";
        }

        if (strlen($bairro) > 0) {
            $campoPedido .= "bairro,";
            $valorPedido .= "'$bairro',";
        }

        if (strlen($cep) > 0) {
            $campoPedido .= "cep,";
            $valorPedido .= "'$cep',";
        }

        if (strlen($cpf_cnpj) > 0) {
            $campoPedido .= "cpf,";
            $valorPedido .= "'$cpf_cnpj',";
        }

        if (isset($cidade["cidade_id"]) && strlen($cidade["cidade_id"]) > 0) {
            $campoPedido .= "cidade,";
            $valorPedido .= "".$cidade["cidade_id"].",";
        }

        $campoADD = "";
        $valorADD = "";
        $array_campos_adicionais = [];
        if (strlen($empresa) > 0) {
            $array_campos_adicionais['nome_empresa'] = utf8_encode($empresa); 
        }
        if (strlen($produto_aplicado) > 0) {
            $array_campos_adicionais['produto_aplicado'] = $produto_aplicado; 
        }
        if (strlen($codigo_posto) > 0) {
            $array_campos_adicionais['codigo_cliente_revenda'] = $codigo_posto; 
        }
        if (strlen($lote) > 0) {
            $xlote = $lote; 
        }
        if (!empty($array_campos_adicionais)) {
            $array_campos_adicionais = json_encode($array_campos_adicionais);
            $campoADD = ",array_campos_adicionais";
            $valorADD = ",'$array_campos_adicionais'";

        }

        $sql_busca_origem = "SELECT 
                                hd_chamado_origem AS origem_id
                            FROM  tbl_hd_chamado_origem
                            WHERE fabrica       = {$login_fabrica}
                                  AND descricao = 'FALE CONOSCO'
                            LIMIT 1";
        $res_busca_origem = pg_query($con, $sql_busca_origem);
        if (pg_num_rows($res_busca_origem) > 0) {

            $origem_id        = pg_fetch_result($res_busca_origem, 0, 'origem_id');

            $sql_busca_admin  = "SELECT COUNT(hc.hd_chamado) AS qtde,
                                    a.admin,
                                    a.email
7                                FROM tbl_admin a
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

            if (1==1 || pg_num_rows($res_busca_admin) > 0) {
//                $id_atendente    = pg_fetch_result($res_busca_admin, 0, 'admin');
                $id_atendente    = $admin;
                $email_atendente = pg_fetch_result($res_busca_admin, 0, 'email');
                //$data_providencia = date('Y-m-d H:i:s', strtotime('+2 hours', strtotime(date("Y-m-d H:i:s"))));

                $sqlAcao = "SELECT hd_motivo_ligacao,prazo_horas
                              FROM tbl_hd_motivo_ligacao
                             WHERE fabrica = {$login_fabrica}
                               AND descricao = 'AGUARDANDO CLASSIFICACAO'
                            LIMIT 1";
                $resAcao = pg_query($con, $sqlAcao);
                if (pg_num_rows($resAcao) > 0) {
                    $hd_motivo_ligacao = pg_fetch_result($resAcao, 0, 'hd_motivo_ligacao');
                    $prazo_horas       = pg_fetch_result($resAcao, 0, 'prazo_horas');
                    $data_prov_comp    = date("Y-m-d H:i:s");
                    $data_pr           = date('Y-m-d H:i:s', strtotime("$data_prov_comp +$prazo_horas hour"));
                    $data_providencia  = calcula_horas_uteis(date("Y-m-d 08:00:00"), date("Y-m-d 17:30:00"), $data_pr, $prazo);
                }

                $res = pg_query($con,"BEGIN TRANSACTION");

                $sqlInsHd = "
                    INSERT INTO tbl_hd_chamado (
                        admin,
                        fabrica,
                        atendente,
                        fabrica_responsavel,
                        status,
                        titulo,
                        data_providencia
                    ) VALUES (
                        $admin,
                        $login_fabrica,
                        $id_atendente,
                        $login_fabrica,
                        'Aberto',
                        'Abertura de ocorrência via Site',
                        '$data_providencia'
                    ) RETURNING hd_chamado;
                ";

                $resInsHd = pg_query($con,$sqlInsHd);
                $erro .= pg_last_error($con);
                $hd_chamado = pg_fetch_result($resInsHd,0,'hd_chamado');

                $sqlInsEx = "
                    INSERT INTO tbl_hd_chamado_extra (
                        nome,
                        {$campoFone}
                        hd_chamado,
                        hd_motivo_ligacao,
                        hd_chamado_origem,
                        origem,
                        consumidor_revenda,
                        email,
                        $campoPedido
                        reclamado
                        {$campoADD}
                        
                    ) VALUES (
                        '$consumidor_nome',
                        {$valorFone}
                        $hd_chamado,
                        $hd_motivo_ligacao,
                        $origem_id,
                        'FALE CONOSCO',
                        '$tipo',
                        '$consumidor_email',
                        $valorPedido
                        '$mensagem'
                        {$valorADD}
                        
                    )
";
                $resInsEx = pg_query($con,$sqlInsEx);
                $erro = pg_last_error($con);

                $sqlInsItem = "
                    INSERT INTO tbl_hd_chamado_item (
                        hd_chamado,
                        comentario,
                        status_item
                    ) VALUES (
                        $hd_chamado,
                        'Abertura de ocorrência via Site',
                        'Aberto'
                    )
                ";

                $resInsItem = pg_query($con,$sqlInsItem);
                $erro .= pg_last_error($con);
                $produto      = "";
                $qtde_produto = "";

                for ($i=0; $i < count($_POST["produto_referencia"]); $i++) { 
                    $produto      = $_POST["produto"][$i];
                    $emissao      = $_POST["emissao"][$i];
                    $qtde_produto = $_POST["qtde_produto"][$i];
                    $preco_produto = $_POST["preco_produto"][$i];
                    $campos_add["preco"] = number_format($preco_produto,2,',','.');
                    $xcamposAdd = json_encode($campos_add);
                    $sqlInsItem2 = "
                        INSERT INTO tbl_hd_chamado_item (
                            hd_chamado,
                            produto,
                            data_nf,
                            nota_fiscal,
                            qtde,
                            tincaso,
                            campos_adicionais
                        ) VALUES (
                            $hd_chamado,
                            $produto,
                            '$emissao',
                            '$nf',
                            $qtde_produto,
                            '$xlote',
                            '$xcamposAdd'
                        )
                    ";
                    $resInsItem2 = pg_query($con,$sqlInsItem2);
                    $erro .= pg_last_error($con);
                }

                /* upload do produto */

                if (empty($erro) && !empty($anexos)) {

                    foreach ($anexos as $anexo) {
                        if (empty($anexo)) {
                            continue;
                        }               

                        $dadosAnexo = json_decode($anexo, 1);
                        if (empty($dadosAnexo)) {
                            continue;
                        }
           
                        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $hd_chamado, "anexar", false, "callcenter", "produto");
                        if (!$anexoID) {
                            $msg_erro["msg"][] = 'Erro ao fazer upload do anexo!';
                        }
                    }

                }

                $msg = "";
                if (!empty($erro)) {
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    $msg_erro["msg"][] = "Erro ao fazer o registro da ocorrência." . $erro;
                } else {
                    $res = pg_query($con,"COMMIT TRANSACTION");
                      $corpoEmail = "
                        <p><b>Identifique-se:</b> {$array_tipos[$tipo]}</p>
                        <p><b>Titular da Nota:</b> {$consumidor_nome}</p>
                        <p><b>Nota Fiscal:</b> {$nf}</p>
                        <p><b>Registrado Por:</b> {$empresa}</p>
                        <p><b>Email:</b> {$consumidor_email}</p>
                        <p><b>Telefone:</b> {$telefone} - <b>Celular:</b> {$consumidor_celular}</p>
                        
                        <p><b>Produto foi aplicado:</b> {$produto_aplicado} - <b>Lote:</b> {$lote}</p>
                        <p><b>Mensagem da ocorrência:</b> {$mensagem} </p>
                    ";

                    $mailTc = new TcComm('smtp@posvenda');
 

                    $mailTc->setEmailSubject("Abertura de ocorrência via site Viapol Nº ".$hd_chamado);
                    $mailTc->addToEmailBody($corpoEmail);
                    $mailTc->setEmailFrom('sac@viapol.com.br');

                    //$mailTc->addEmailDest($email_atendente);
                    $mailTc->addEmailDest('carolina.mantovani@viapol.com.br');
                    if (strlen($pedido) > 0) {
                        $sqlPedido = "SELECT cliente_email 
                                        FROM tbl_pedido 
                                       WHERE pedido={$pedido} 
                                         AND fabrica=189 
                                         AND cliente_email IS NOT NULL";
                        $resPedido = pg_query($con, $sqlPedido);
                        if (pg_num_rows($resPedido) > 0) {
                            $email_repre = pg_fetch_result($resPedido, 0, 'cliente_email');
                            $mailTc->addEmailDest($email_repre);
                        }
                    }

                    $res = $mailTc->sendMail();

                    unset($_POST);
                    $anexos = [];
                    $msg = "Ocorrência aberta com sucesso!<br> <b>Nº do protocolo:  {$hd_chamado}</b><br> Em breve nossa equipe entrará em contato.";
                }
            } else {
                $msg_erro["msg"][] = "Ocorreu um erro ao gravar o atendimento, favor entrar em contato com a fábrica.";
            }
        }
    }
}

if ($_POST["ajax_anexo_upload"] == true) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif, pdf'),'posicao' => $posicao);

        } else {

            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

                $anexoID      = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
                $arquivo_nome = json_encode($tDocs->sentData);

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 

            }

            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }

            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $tdocs_id = $anexoID;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {
                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao','tdocs_id');
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}
if ($_POST["ajax_remove_anexo"] == true) {

    $posicao    = $_POST["posicao"];
    $tdocs_id   = $_POST["tdocsid"];

    $tDocs->setContext('callcenter');

    $anexoID = $tDocs->deleteFileById($tdocs_id);

    if (!$anexoID) {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Erro ao remover arquivo'),'posicao' => $posicao);
    }  else {

        $retorno = array('sucesso' => true, 'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}
header('Content-type: text/html; charset=iso-8859-1');

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="iso-8859-1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<title>Registro de Ocorrência </title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
<!-- CSS Files -->
<link rel="stylesheet" href="../bootstrap3/css/bootstrap.min.css" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?>css/style.css?v=<?php echo date("YmdHis");?>" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?>css/style-new.css?v=<?php echo date("YmdHis");?>" />

<style type="text/css">
    body{
        margin: 0;
        padding:0;
        background: #ffffff !important;
    }
    .control-label{
        font-weight: 300;
    }
    .txt_normal{
        font-weight: 300;
    }

    .campos_obg{
        color: #d90000;
        font-size: 0.7em;
    }
    label{color: #004fa2;font-weight: normal;padding-top: 8px;}
    select{
        height: 38px;
    font-size: 16px;
    margin-bottom: 10px;
    padding-left:10px;
    width: 100%;
    }
    h2 {
            font-weight: 700;
    margin-top: 0px;
    text-transform: uppercase;
    font-size: 40px;
    color: #01508E;
    }
    hr{
            border-top: 1px solid #eee !important;
    }
    .label_anexo{
        text-align: center;
        color: #01508E;
        font-weight: bold;
    }
    .btn-anexar{
        background: #81AD19;
        display: block;
        color: #000000;
        padding: 5px;
        width: 100%;
        border: solid 1px #668816;
        border-radius: 5px;
        margin-top: 10px;

    }
    .btn-anexar:hover{
        background: #668816;
        display: block;
        color: #ffffff;
        padding: 5px;
        width: 100%;
        border: solid 1px #81AD19;
        border-radius: 5px;
        margin-top: 10px;
    }
    .btn-delanexar{
        background: #d90000;
        display: block;
        color: #ffffff;
        padding: 5px;
        width: 100%;
        border: solid 1px #ff0000;
        border-radius: 5px;
        margin-top: 10px;
    }
    .btn-delanexar:hover{
        background: #ff0000;
        display: block;
        color: #ffffff;
        padding: 5px;
        width: 100%;
        border: solid 1px #d90000;
        border-radius: 5px;
        margin-top: 10px;

    }
</style>
</head>
<body>

<div class="container">
<?php
$agora = date("Y-m-d H:m");
$corte = '2020-02-01 00:00';

if(strtotime($agora) < strtotime($corte)){
?>
<br><br>
<div class="alert alert-danger">
	<h4><b>Os registros de ocorrências serão liberados a partir do dia 01/02/2020 às 00:00 horas</b></h4>
</div>
<?php
	exit;
}
?>
<div class="mindBody"> 
        
    <img class="img960">
    
    <div class="max-width">
        <div class="unit size1of1">
            <div class="pageInfo">
                <div class="row">
                    <div class="col-sm-12">
                        <h2>Anotação de Ocorrências</h2>
                        <p>O departamento responsável irá analisar o registro e em breve entrará em contato.</p>
                    </div>
                </div><br><br>
                <?php if (isset($msg_erro["msg"]) && count($msg_erro["msg"]) > 0) {?>
                    <div class="alert alert-danger">
                        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
                    </div>
                <?php }?>
                <?php if (isset($msg) && strlen($msg) > 0) {?>
                    <div class="alert alert-success">
                        <h4><?php echo $msg;?></h4>
                    </div>
                <?php }?>
                <div class="msg_valida"></div>
                <form action="" method="post" name="form_ocorrencia" enctype="multipart/form-data" id="contactForm">
                    <input type="hidden" name="codigo_posto" id="codigo_posto" value="<?php echo (isset($_POST["codigo_posto"]) && strlen($_POST["codigo_posto"]) > 0) ? $_POST["codigo_posto"] : '';?>">
                    <input type="hidden" name="endereco" id="endereco" value="<?php echo (isset($_POST["endereco"]) && strlen($_POST["endereco"]) > 0) ? $_POST["endereco"] : '';?>">
                    <input type="hidden" name="numero" id="numero" value="<?php echo (isset($_POST["numero"]) && strlen($_POST["numero"]) > 0) ? $_POST["numero"] : '';?>">
                    <input type="hidden" name="complemento" id="complemento" value="<?php echo (isset($_POST["complemento"]) && strlen($_POST["complemento"]) > 0) ? $_POST["complemento"] : '';?>">
                    <input type="hidden" name="bairro" id="bairro" value="<?php echo (isset($_POST["bairro"]) && strlen($_POST["bairro"]) > 0) ? $_POST["bairro"] : '';?>">
                    <input type="hidden" name="cep" id="cep" value="<?php echo (isset($_POST["cep"]) && strlen($_POST["cep"]) > 0) ? $_POST["cep"] : '';?>">
                    <input type="hidden" name="cpf_cnpj" id="cpf_cnpj" value="<?php echo (isset($_POST["cpf_cnpj"]) && strlen($_POST["cpf_cnpj"]) > 0) ? $_POST["cpf_cnpj"] : '';?>">
                    <input type="hidden" name="nome_cidade" id="nome_cidade" value="<?php echo (isset($_POST["nome_cidade"]) && strlen($_POST["nome_cidade"]) > 0) ? $_POST["nome_cidade"] : '';?>">
                    <input type="hidden" name="estado" id="estado" value="<?php echo (isset($_POST["estado"]) && strlen($_POST["estado"]) > 0) ? $_POST["estado"] : '';?>">
                    <input type="hidden" name="consumidor_revenda" id="consumidor_revenda" value="<?php echo (isset($_POST["consumidor_revenda"]) && strlen($_POST["consumidor_revenda"]) > 0) ? $_POST["consumidor_revenda"] : '';?>">
                    <input type="hidden" name="pedido" id="pedido" value="<?php echo (isset($_POST["pedido"]) && strlen($_POST["pedido"]) > 0) ? $_POST["pedido"] : '';?>">
                    <input type="hidden" name="qtde_total_produtos" id="qtde_total_produtos" value="<?php echo (isset($_POST["qtde_total_produtos"]) && strlen($_POST["qtde_total_produtos"]) > 0) ? $_POST["qtde_total_produtos"] : '';?>">
                    <div class="row">
                        <div class="col-sm-2"><label>N° NF*:</label></div>
                        <div class="col-sm-3">
                            <div class="input-group">
                              <input type="text"  maxlength="6" name="nf" value="<?php echo (isset($_POST["nf"]) && strlen($_POST["nf"]) > 0) ? $_POST["nf"] : '';?>">
                              <span class="input-group-btn">
                                <button class="btn btn-default btn-lupa-nf" style="border-radius: 0px;border-left: 0px;margin-top: -10px;padding-top: 11px;padding-bottom: 5px;border-color: #afaaaa;" type="button"><i class="glyphicon glyphicon-search"></i></button>
                              </span>
                              <span class="input-group-btn">
                                <button class="btn btn-default"  data-toggle="popover" data-placement="right"  data-content="Preencha o número da nota fiscal e clique na lupa." style="margin-top: -10px;padding-top: 11px;padding-bottom: 5px;border-color: #afaaaa;" type="button"><i class="glyphicon glyphicon-question-sign"></i></button>
                              </span>
                            </div>
                        </div>
                        <div class="col-sm-2"><label>Identifique-se*:</label></div>
                        <div class="col-sm-5">
                            <select name="tipo">
                                <option value="">Selecione ...</option>
                                <?php foreach ($array_tipos as $sigla => $tipo) {?>
                                    <option value="<?php echo $sigla;?>" <?php echo ($_POST["tipo"] == $sigla) ? 'selected' : '';?>><?php echo $tipo;?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Titular da Nota*:</label></div>
                        <div class="col-sm-10"><input name="nome" id="nome" value="<?php echo (isset($_POST["nome"]) && strlen($_POST["nome"]) > 0) ? $_POST["nome"] : '';?>" type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Registrado Por:</label></div>
                        <div class="col-sm-8">
                            <div class="input-group">
                              <input name="empresa" value="<?php echo (isset($_POST["empresa"]) && strlen($_POST["empresa"]) > 0) ? $_POST["empresa"] : '';?>" type="text">
                              <span class="input-group-btn">
                                <button class="btn btn-default"  data-container="body" data-toggle="popover" data-placement="right"  data-content="Informe seu Nome Completo para receber o retorno da Viapol" style="margin-top: -10px;padding-top: 11px;padding-bottom: 5px;border-color: #afaaaa;" type="button"><i class="glyphicon glyphicon-question-sign"></i></button>
                              </span>
                            </div>
                        </div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Email*:</label></div>
                        <div class="col-sm-4"><input name="email" id="email" value="<?php echo (isset($_POST["email"]) && strlen($_POST["email"]) > 0) ? $_POST["email"] : '';?>" type="text"></div>
                        <div class="col-sm-1"><label>Telefone*:</label></div>
                        <div class="col-sm-2"><input name="celular" id="celular" value="<?php echo (isset($_POST["celular"]) && strlen($_POST["celular"]) > 0) ? $_POST["celular"] : '';?>" class="celular" type="text"></div>
                        
                    </div><br>
                    <?php if (isset($_POST["produto_referencia"]) && count($_POST["produto_referencia"]) > 0) {?>
                        <?php 
                        for ($i=0; $i < count($_POST["produto_referencia"]); $i++) {
                        ?>
                        <div class="row item-<?php echo $i;?>">
                            <div class="col-sm-2"><label>Referência <?php echo $i+1;?>*:</label></div>
                            <div class="col-sm-2">
                                <div class="input-group">
                                  <input type="text" class="produto_referencia_<?php echo $i;?>" name="produto_referencia[<?php echo $i;?>]" value="<?php echo (isset($_POST["produto_referencia"][$i]) && strlen($_POST["produto_referencia"][$i]) > 0) ? $_POST["produto_referencia"][$i] : '';?>">
                                </div>
                            </div>
                            <div class="col-sm-2"><label>Produto <?php echo $i+1;?>*:</label></div>
                            <div class="col-sm-3">
                                  <input type="text" class="produto_descricao_<?php echo $i;?>" name="produto_descricao[<?php echo $i;?>]" value="<?php echo (isset($_POST["produto_descricao"][$i]) && strlen($_POST["produto_descricao"][$i]) > 0) ? $_POST["produto_descricao"][$i] : '';?>">
                                  <input type="hidden" class="produto_<?php echo $i;?>" name="produto[<?php echo $i;?>]" value="<?php echo (isset($_POST["produto"][$i]) && strlen($_POST["produto"][$i]) > 0) ? $_POST["produto"][$i] : '';?>">

                                  <input type="hidden" class="emissao_<?php echo $i;?>" name="emissao[<?php echo $i;?>]" value="<?php echo (isset($_POST["emissao"][$i]) && strlen($_POST["emissao"][$i]) > 0) ? $_POST["emissao"][$i] : '';?>">
                            </div>
                            <div class="col-sm-1"><label>Qtd <?php echo $i+1;?>*:</label></div>
                            <div class="col-sm-1"><input name="qtde_produto[<?php echo $i;?>]" class="qtde_produto_<?php echo $i;?>" type="text" value="<?php echo (isset($_POST["qtde_produto"][$i]) && strlen($_POST["qtde_produto"][$i]) > 0) ? $_POST["qtde_produto"][$i]: '';?>"></div>
                            <div class="col-sm-1"><input name="preco_produto[<?php echo $i;?>]" class="preco_produto_<?php echo $i;?>" type="hidden" value="<?php echo (isset($_POST["preco_produto"][$i]) && strlen($_POST["preco_produto"][$i]) > 0) ? $_POST["preco_produto"][$i]: '';?>"></div>
                            <div class="col-sm-1"><button class="btn btn-danger" onclick="remove_linha(<?php echo $i;?>);" type="button"><i class="glyphicon glyphicon-remove"></i></div>
                        </div><br>
                        <?php }?>
                    <?php }?>
                    <div id="listas_produtos">

                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Produto foi aplicado*:</label></div>
                        <div class="col-sm-1">
                            <input name="produto_aplicado" value="Sim"  <?php echo (isset($_POST["produto_aplicado"]) && $_POST["produto_aplicado"] == "Sim") ? 'checked' : '';?> type="radio"> <label style="float: right;margin-top: -26px;margin-left: 10px;"> Sim</label>
                        </div>
                        <div class="col-sm-1">
                             <input name="produto_aplicado" value="Nao" <?php echo (isset($_POST["produto_aplicado"]) && $_POST["produto_aplicado"] == "Nao") ? 'checked' : '';?>  type="radio"> <label style="float: right;margin-top: -26px;margin-left: 10px;">Não</label>
                        </div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>N° Lote <span class="txt_ast_lote"></span>:</label></div>
                        <div class="col-sm-2"><input name="lote" value='<?php echo (isset($_POST["lote"]) && strlen($_POST["lote"]) > 0) ? $_POST["lote"] : '';?>' type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Descrição ocorrência*:</label></div>
                        <div class="col-sm-10"><textarea name="mensagem" rows="5" cols="20"><?php echo (isset($_POST["mensagem"]) && strlen($_POST["mensagem"]) > 0) ? $_POST["mensagem"] : '';?></textarea></div>
                    </div><br><hr />
                    <h5 class="label_anexo">Anexo(s)</h5><br />
                        <div style="text-align: center;" align="center">
                        <?php
                        for ($i=1; $i <= 5 ; $i++) {

                        $imagemAnexo = "../../admin/imagens/imagem_upload.png";
                        $linkAnexo   = "#";
                        $tdocs_id   = "";

                        ?>
                            <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                                <?php if ($linkAnexo != "#") { ?>
                                <a href="<?=$linkAnexo?>" target="_blank" >
                                <?php } ?>
                                    <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                                <?php if ($linkAnexo != "#") { ?>
                                </a>
                                <?php } ?>
                                <button type="button" style="display: none;" class="btn-delanexar btn-remover-anexo" data-tdocsid="<?=$tdocs_id?>" data-posicao="<?=$i?>" >Remover</button>
                                <button type="button" class="btn-anexar" name="anexar" rel="<?=$i?>" >Anexo <?php echo $i?></button>
                                <img src="../../admin/imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                                <input type="hidden" rel="anexo" id="anexo_cancela" name="anexo[<?=$i?>]" value="<?=$anexo[$i]?>" />
                            </div>
                   
                        <?php } ?>
                    </div><hr />

                    <div class="row">
                        <div class="col-sm-12"><button name="btn_submit" type="button" class="contactButton newPurpBtn">Enviar</button></div>
                    </div><br>

                </form>
                <?php for ($i = 1; $i <=  5; $i++) {?>
                    <form name="form_anexo" method="post" action="ocorrencia.php" enctype="multipart/form-data" style="display: none !important;" >
                        <input type="file" name="anexo_upload_<?=$i?>" value="" />
                        <input type="hidden" name="ajax_anexo_upload" value="t" />
                        <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
                        <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
                    </form>

                <?php }?>                     
            </div><!--pageInfo-->   
        <br clear="all">
        </div>  
        <br clear="all">
    </div>  
 
    </div>

</div>


<div data-backdrop="static" class="modal fade" tabindex="-1" role="dialog" id="myModal" aria-labelledby="gridSystemModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header tac">
	   <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>    
	   <h4 class="modal-title tac" id="gridSystemModalLabel">Comunicado</h4>
      
	</div>
      <div class="modal-body tac">

<h3 style="color:#222">
Aten&#231;&#227;o<br><br>


Para registrar a ocorr&#234;ncia, entrar em contato com o nosso SAC atrav&#233;s do telefone
<b style="color:#81AD19">08000 494 0777</b>


<br><br>

</h3>



      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../bootstrap3/js/bootstrap.min.js"></script>
<script src="../../admin/js/jquery.mask.js"></script>
<script type="text/javascript" src="../../js/jquery.form.js"></script>

<script src="../../plugins/shadowbox/shadowbox.js" type="text/javascript" ></script>
<link rel="stylesheet" type="text/css" href="../../plugins/shadowbox/shadowbox.css" media="all" >

<script type="text/javascript">
    function retorna_dados(nome = '', email = '', fone ='', fone2='', pedido='',endereco='',numero='', complemento='',bairro='',cep='', cpf_cnpj = '', nome_cidade='',estado='',codigo_posto='') {

        $("input[name=codigo_posto]").val(codigo_posto)
        $("input[name=nome]").val(nome)
        $("input[name=endereco]").val(endereco)
        $("input[name=numero]").val(numero)
        $("input[name=complemento]").val(complemento)
        $("input[name=bairro]").val(bairro)
        $("input[name=cep]").val(cep)
        $("input[name=cpf_cnpj]").val(cpf_cnpj)
        $("input[name=nome_cidade]").val(nome_cidade)
        $("input[name=estado]").val(estado)
        if(cpf_cnpj.length > 10) {
            $("input[name=consumidor_revenda]").val('R')
        } else {
            $("input[name=consumidor_revenda]").val('C')
        }
        $("input[name=pedido]").val(pedido)
    }
    
    function retorna_produto (retorno, posicao) {
        $("#produto_"+posicao).val(retorno.produto);
        $("#produto_descricao_"+posicao).val(retorno.descricao);
    }
    function limpa_produtos(){
        $("#listas_produtos").html('');

    }
    function add_linha(posicao) {

        var conteudo = '\
                        <div class="row item-'+posicao+'">\
                            <div class="col-sm-2"><label>Referência '+posicao+'*:</label></div>\
                            <div class="col-sm-2">\
                                <div class="input-group">\
                                  <input type="text" class="produto_referencia_'+posicao+'"  name="produto_referencia[]" >\
                                </div>\
                            </div>\
                            <div class="col-sm-2"><label>Produto '+posicao+'*:</label></div>\
                            <div class="col-sm-3">\
                                  <input type="text"  class="produto_descricao_'+posicao+'"name="produto_descricao[]" >\
                                  <input type="hidden"  class="produto_'+posicao+'" name="produto[]" >\
                                  <input type="hidden"  class="emissao_'+posicao+'" name="emissao[]" >\
                            </div>\
                            <div class="col-sm-1"><label>Qtd '+posicao+'*:</label></div>\
                            <div class="col-sm-1"><input class="qtde_produto_'+posicao+'" name="qtde_produto[]" type="text"><input class="preco_produto_'+posicao+'" name="preco_produto[]" type="hidden"></div>\
                            <div class="col-sm-1"><button class="btn btn-danger" onclick="remove_linha('+posicao+');" type="button"><i class="glyphicon glyphicon-remove"></i></div>\
                        </div>\
                            ';
        $("#listas_produtos").append(conteudo);
    
       
    }

    function remove_linha(posicao) {

        var total = $("#qtde_total_produtos").val();
        $(".item-"+posicao).remove();
        $("#qtde_total_produtos").val(total-1);
       
    }

    $(function(){
        Shadowbox.init();
        $(".fone").mask("(00) 0000-0000");
        $(".celular").mask("(00) 00000-0000");
        $('[data-toggle="popover"]').popover();
 /*       
        $("input[name=produto_aplicado]").change(function(){
            if ($(this).val() == "Nao") {
                $('.txt_ast_lote').text("*") 
            } else {
                $('.txt_ast_lote').text("") 
            }
        });
*/

//	$('#myModal').modal('show');
        $(".btn-lupa-produto").click(function(){
            var produto = $("input[name=produto_descricao]").val();
            if (produto == "") {
                alert("Digite o Produto");
                $("input[name=produto_descricao]").focus();
                return false;
            }

            Shadowbox.init();
            Shadowbox.open({
                content: 'lupa_produto.php?parametro=descricao&valor='+produto ,
                player: 'iframe',
                width: 800,
                height: 500
            });
        });

        $(".btn-lupa-nf").click(function(){
            var nf = $("input[name=nf]").val();
            if (nf == "") {
                alert("Digite o N° NF");
                $("input[name=nf]").focus();
                return false;
            }

            Shadowbox.init();
            Shadowbox.open({
                content: 'lupa_nf.php?nf=' + nf,
                player: 'iframe',
                width: 800,
                height: 500
            });
        });

        $(document).on("click",".contactButton", function(){
            $(this).attr("disabled", true);
            var msg_campos      = "";
            var tipo            = $("select[name=tipo]").val();
            var nome            = $("input[name=nome]").val();
            var codigo_posto    = $("input[name=codigo_posto]").val();
            var empresa         = $("input[name=empresa]").val();
            var email           = $("input[name=email]").val();
            var telefone        = $("input[name=telefone]").val();
            var celular         = $("input[name=celular]").val();
            var produto         = $("input[name=produto_1]").val();
            var nf              = $("input[name=nf]").val();
            var qtde_produto    = $("input[name=qtde_produto_1]").val();
            var preco_produto    = $("input[name=preco_produto_1]").val();
            var produto_aplicado = $("input[name=produto_aplicado]:checked").val();
            var lote            = $("input[name=lote]").val();
            var imagem          = $("input[name=imagem]").val();
            var mensagem        = $("input[name=mensagem]").val();


            if (tipo == "") {
                msg_campos += "Selecione o campo <b>Identifique-se</b><br>";
            }

            if (nome == "") {
                msg_campos += "Preencha o campo <b>Titular da Nota</b><br>";
            }

            if (email == "") {
                msg_campos += "Preencha o campo <b>E-mail</b><br>";
            }

            if (produto == "") {
                msg_campos += "Preencha o campo <b>Produto</b><br>";
            }

            if (nf == "") {
                msg_campos += "Preencha o campo <b>N° NF</b><br>";
            }

            if (qtde_produto == "") {
                msg_campos += "Preencha o campo <b>Qtd produto</b><br>";
            }

            if (produto_aplicado == "") {
                msg_campos += "Preencha o campo <b>Produto foi aplicado</b><br>";
            }
/*

            if ((produto_aplicado == "Nao" || produto_aplicado == undefined)  && lote == "") {
                msg_campos += "Preencha o campo <b>N° Lote</b><br>";
            }

            if (imagem == "") {
                msg_campos += "Preencha o campo <b>Imagem do Produto</b><br>";
            }*/

            if (mensagem == "") {
                msg_campos += "Preencha o campo <b>Descrição ocorrência</b><br>";
            }
            
            $(".msg_valida").html('');

            if (msg_campos != "") {
                $(".msg_valida").html('<div class="alert alert-danger">'+msg_campos+'</div>');
                $(this).attr("disabled", false);
                return false;
            }
            $("form[name=form_ocorrencia]").submit();

        });

        var phoneMask = function() {
            if($(this).val().match(/^\(0/)) {
                $(this).val('(');
                return;
            }
            if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
                $(this).mask('(00) 0000-0000'); /* Máscara default */
            } else {
                $(this).mask('(00) 00000-0000');  // 9º Dígito
            }
            $(this).keyup(phoneMask);
        };
        $('.fone').keyup(phoneMask);
        $('.celular').keyup(phoneMask);

        $("div[id^=div_anexo_]").each(function(i) {
            var tdocs_id = $("#div_anexo_"+i).find(".btn-remover-anexo").data("tdocsid");
            if (tdocs_id != '' && tdocs_id != null && tdocs_id != undefined) {
                $("#div_anexo_"+i).find("button[name=anexar]").hide();
                $("#div_anexo_"+i).find(".btn-remover-anexo").show();
            } else {
                $("#div_anexo_"+i).find(".btn-remover-anexo").hide();
            }
        });

        /* REMOVE DE FOTOS */
        $(document).on("click", ".btn-remover-anexo", function () {
            var tdocsid = $(this).data("tdocsid");
            var posicao = $(this).data("posicao");

            if (tdocsid != '' && tdocsid != null && tdocsid != undefined) {
                $("#div_anexo_"+posicao).find(".btn-remover-anexo").hide();
                $.ajax({
                    url: window.location,
                    type: "POST",
                    dataType:"JSON",
                    data: { 
                        ajax_remove_anexo: true,
                        tdocsid: tdocsid,
                        posicao: posicao
                    }
                }).done(function(data) {
                    if (data.erro == true) {
                        alert(data.msg);
                        return false;
                    } else {
                        alert("Removido com sucesso.");
                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").hide();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", "");
                        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val("");
                        $("#div_anexo_"+data.posicao).find(".link_anexo_"+data.posicao).removeAttr("href");
                        $("#div_anexo_"+data.posicao).find(".link_anexo_"+data.posicao).removeAttr("target");
                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").attr("src", "../../admin/imagens/imagem_upload.png");
                        $("input[name^=anexo_upload_]").val("");
                    }
                });

            } else {
                $("#div_anexo_"+posicao).find(".btn-remover-anexo").show();
            }

        });

        /* ANEXO DE FOTOS */
        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button[name=anexar]").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");
            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);
                
                if (data.error) {
                    alert(data.error);
                    $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                    $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
                } else {
                    var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();

                    if (data.ext == 'pdf') {
                        $(imagem).attr({ src: "../../admin/imagens/pdf_icone.png" });
                    } else if (data.ext == "doc" || data.ext == "docx") {
                        $(imagem).attr({ src: "../../admin/imagens/docx_icone.png" });
                    } else {
                        $(imagem).attr({ src: data.link });
                    }
                    
                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        class: "link_anexo_"+data.posicao,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo_"+data.posicao).prepend(link);


                    $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button[name=anexar]").hide();
                $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").show();
                $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", data.tdocs_id);
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            }
        /* FIM ANEXO DE FOTOS */
        });

    });
</script>
</body>
</html>
