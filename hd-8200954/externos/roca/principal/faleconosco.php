<?php
include '../../../dbconfig.php';
include '../../../includes/dbconnect-inc.php';
include '../../../funcoes.php';
include '../../plugins/fileuploader/TdocsMirror.php';

$site = $_GET["site"];
if (strlen($site) == 0) {
    exit("Fale conosco não encontrado");
}

if ($_serverEnvironment == 'development'){
    #$URL_BASE = "http://roca.novodevel.telecontrol.com.br/externos/roca/";
    $URL_BASE = "http://novodevel.telecontrol.com.br/~bicalleto/PosVenda/externos/roca/";
}else{
    $URL_BASE = "https://posvenda.telecontrol.com.br/assist/externos/roca/";
}

$login_fabrica = 178;

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

if (filter_input(INPUT_POST,'btn_submit')) {

    $valida_campos = false;

    if (!filter_input(INPUT_POST,"assunto") && $site != "roca") {
        $msg_erro["msg"][] = "Preencha o campo Assunto";
        $msg_erro['campos'][] = "assunto";
    }else{
        $assunto = trim(filter_input(INPUT_POST,"assunto",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
    
        $sql = "SELECT hd_classificacao, obriga_campos, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND hd_classificacao = $assunto";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $assunto          = pg_fetch_result($res, 0, "descricao");
            $hd_classificacao = pg_fetch_result($res, 0, "hd_classificacao");
            $obriga_campos    = pg_fetch_result($res, 0, "obriga_campos");

            if ($obriga_campos == "t"){
                $valida_campos = true;
            }
        }
    }

    if ($valida_campos === true){
        if (!filter_input(INPUT_POST,"cpf")) {
            $msg_erro["msg"][] = "Preencha o campo CPF";
            $msg_erro['campos'][] = "cpf";
        }

        if (!filter_input(INPUT_POST,"data_nascimento")) {
            $msg_erro["msg"][] = "Preencha o campo Data Nascimento";
            $msg_erro['campos'][] = "data_nascimento";
        }

        if (!filter_input(INPUT_POST,"consumidor_email",FILTER_VALIDATE_EMAIL)) {
            $msg_erro["msg"][] = "Preencha o campo E-mail";
            $msg_erro['campos'][] = "consumidor_email";
        }

        if (!filter_input(INPUT_POST,"consumidor_cep")) {
            $msg_erro["msg"][] = "Preencha o campo CEP";
            $msg_erro['campos'][] = "consumidor_cep";
        }

        if (!filter_input(INPUT_POST,"consumidor_endereco")) {
            $msg_erro["msg"][] = "Preencha o campo Endereço";
            $msg_erro['campos'][] = "consumidor_endereco";
        }

        if (!filter_input(INPUT_POST,"consumidor_numero")) {
            $msg_erro["msg"][] = "Preencha o campo Número";
            $msg_erro['campos'][] = "consumidor_numero";
        }

        if (!filter_input(INPUT_POST,"consumidor_bairro")) {
            $msg_erro["msg"][] = "Preencha o campo Bairro";
            $msg_erro['campos'][] = "consumidor_bairro";
        }

        if (!filter_input(INPUT_POST,"consumidor_cidade")) {
            $msg_erro["msg"][] = "Preencha o campo Cidade";
            $msg_erro['campos'][] = "consumidor_cidade";
        }
        if (!filter_input(INPUT_POST,"consumidor_estado")) {
            $msg_erro["msg"][] = "Preencha o campo UF";
            $msg_erro['campos'][] = "consumidor_estado";
        }

        if (!filter_input(INPUT_POST,"aceito") && $site == "roca") {
            $msg_erro["msg"][] = "Preencha o campo Aceito";
            $msg_erro['campos'][] = "aceito";
        }

        if (!filter_input(INPUT_POST,"fone") && $site == "laufen") {
            $msg_erro["msg"][] = "Preencha o campo Telefone";
            $msg_erro['campos'][] = "fone";
        }
    }

    if (!filter_input(INPUT_POST,"consumidor_nome")) {
        $msg_erro["msg"][] = "Preencha o campo Nome Completo";
        $msg_erro['campos'][] = "consumidor_nome";
    }

    if (!filter_input(INPUT_POST,"consumidor_celular")) {
        $msg_erro["msg"][] = "Preencha o campo Celular";
        $msg_erro['campos'][] = "consumidor_celular";
    }

    if (filter_input(INPUT_POST,"cpf")) {
        $valida_cpf_cnpj = valida_consumidor_cpf(filter_input(INPUT_POST,"cpf"));

        if ($valida_cpf_cnpj === false){
            $msg_erro["msg"][] = "CPF informado inválido";
            $msg_erro['campos'][] = "cpf";
        }
    }
 
    if (filter_input(INPUT_POST,"data_nascimento")) {
        $ok = Valida_Data(filter_input(INPUT_POST,"data_nascimento"));
        if ($ok == 'erro') {
            $msg_erro["msg"][] = "Data Nascimento inválida";
            $msg_erro['campos'][] = "data_nascimento";
        }
    }

    if (!filter_input(INPUT_POST,"mensagem")) {
        $msg_erro["msg"][] = "Preencha o campo Mensagem";
        $msg_erro['campos'][] = "mensagem";
    }

    if (count($msg_erro["msg"]) == 0) {
       
        $consumidor_nome        = trim(filter_input(INPUT_POST,"consumidor_nome",FILTER_SANITIZE_SPECIAL_CHARS));
        $data_nascimento        = trim(filter_input(INPUT_POST, "data_nascimento", FILTER_SANITIZE_SPECIAL_CHARS));
        $data_nascimento        = converte_data($data_nascimento);
        
        $cpf                    = trim(filter_input(INPUT_POST, "cpf", FILTER_SANITIZE_SPECIAL_CHARS));
        $cpf                    = preg_replace("/\D/", "", $cpf);

        $consumidor_email       = trim(filter_input(INPUT_POST,"consumidor_email",FILTER_SANITIZE_EMAIL));
        $consumidor_celular     = trim(filter_input(INPUT_POST,"consumidor_celular",FILTER_SANITIZE_NUMBER_INT));
        $consumidor_cep         = trim(filter_input(INPUT_POST,"consumidor_cep",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_endereco    = trim(filter_input(INPUT_POST,"consumidor_endereco"));
        $consumidor_numero      = trim(filter_input(INPUT_POST,"consumidor_numero"));
        $consumidor_bairro      = trim(filter_input(INPUT_POST,"consumidor_bairro",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_complemento = trim(filter_input(INPUT_POST,"consumidor_complemento",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_cidade      = trim(filter_input(INPUT_POST,"consumidor_cidade",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $consumidor_estado      = trim(filter_input(INPUT_POST,"consumidor_estado",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $aceito                 = trim(filter_input(INPUT_POST,"aceito",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        $mensagem               = trim(filter_input(INPUT_POST,"mensagem",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            
        if (empty($consumidor_cidade)){
            $cidade = "null";
        }else{
            $consumidor_cidade = getCidades($con, $consumidor_cidade, $consumidor_estado);
            $cidade = $consumidor_cidade["cidade_id"];
        }

        if (empty($data_nascimento)){
            $data_nascimento = "null";
        }else{
            $data_nascimento = "'$data_nascimento'";
        }

        $consumidor_celular     = str_replace("-","",$consumidor_celular);
        $consumidor_cep     = str_replace(["-", " ", "."],"",$consumidor_cep);
       
        if ($site != "roca") {
            $mensagem = "Assunto: " . $assunto . "<br >".$mensagem;
        }
        $fone  = "";
        $fone2 = "";
        if ($site == "laufen") {
            $fone  = trim(filter_input(INPUT_POST,"fone",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $fone2 = trim(filter_input(INPUT_POST,"fone2",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
        }

        if (strlen($consumidor_celular) == 10){
            $fone = $consumidor_celular;
            unset($consumidor_celular);
        }
        
        $hd_chamado_origem = null;
        $sqlOrigem = "SELECT hd_chamado_origem
                        FROM tbl_hd_chamado_origem 
                       WHERE  ativo IS TRUE
                         AND descricao = 'Fale Conosco'
                         AND fabrica = {$login_fabrica}";
        $resOrigem = pg_query($con, $sqlOrigem);
        if (pg_num_rows($resOrigem) > 0) {

            $hd_chamado_origem = pg_fetch_result($resOrigem, 0, 'hd_chamado_origem');

	}

	$sqlHdO = "SELECT tbl_admin.admin as atendente
		  FROM tbl_hd_origem_admin
		  JOIN tbl_admin USING(admin,fabrica)
		  WHERE tbl_hd_origem_admin.fabrica = {$login_fabrica}
		  AND tbl_hd_origem_admin.hd_chamado_origem = {$hd_chamado_origem}
		  AND tbl_admin.ativo IS TRUE";
        $resHdO = pg_query($con, $sqlHdO);
	$atDoDia = pg_fetch_all($resHdO);
	
	foreach ($atDoDia as $key => $value) {
		$sqlCont = "	SELECT  COUNT(1) AS chamados_hoje
				FROM    tbl_hd_chamado
				JOIN    tbl_hd_chamado_extra USING(hd_chamado)
				WHERE   tbl_hd_chamado.atendente = {$value['atendente']}
				AND     tbl_hd_chamado.posto isnull
				AND     tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				AND     tbl_hd_chamado_extra.hd_chamado_origem = {$hd_chamado_origem}
				AND     tbl_hd_chamado.data > '".date("Y-m-d 00:00:00")."'";
		$resCont = pg_query($con,$sqlCont);
		$contaChamados = pg_fetch_result($resCont,0,'chamados_hoje');
		$qtdeChamados[$value['atendente']] = $contaChamados;
	}

	asort($qtdeChamados);
	$atendentesOrdenados = array_keys($qtdeChamados);
	$primeiroAtendente = array_shift($atendentesOrdenados);
	$callcenter_supervisor[] = array("atendente" => $primeiroAtendente);

	foreach ($callcenter_supervisor as $key => $value) {
		$atendentes[] = $value['atendente'];
	}

        $atendentes = array_filter($atendentes);

	if(count($atendentes) > 0){
		$sql = "SELECT  tbl_hd_origem_admin.admin,
				tbl_admin.login
			FROM tbl_hd_origem_admin
			JOIN tbl_admin USING(admin,fabrica)
			WHERE tbl_hd_origem_admin.fabrica = {$login_fabrica}
			AND tbl_hd_origem_admin.admin IN(".implode(",",$atendentes).")
			AND tbl_admin.ativo IS TRUE
			AND tbl_hd_origem_admin.hd_chamado_origem = {$hd_chamado_origem}
			LIMIT 1";
    		$resP = pg_query($con,$sql);
    		$xadmin = pg_fetch_result($resP, 0, 'admin');
	}
        $res = pg_query($con,"BEGIN TRANSACTION");

        $sqlInsHd = "
            INSERT INTO tbl_hd_chamado (
                fabrica,
                atendente,
                fabrica_responsavel,
                status,
                titulo,
                hd_classificacao
            ) VALUES (
                $login_fabrica,
                $xadmin,
                $login_fabrica,
                'Aberto',
                'Atendimento Fale Conosco - Site {$site}',
                $hd_classificacao
            ) RETURNING hd_chamado;
        ";

        $resInsHd = pg_query($con,$sqlInsHd);
        $erro .= pg_last_error($con);
        $hd_chamado = pg_fetch_result($resInsHd,0,'hd_chamado');

        $sqlInsEx = "
            INSERT INTO tbl_hd_chamado_extra (
                fone,
                fone2,
                hd_chamado,
                hd_chamado_origem,
                origem,
                consumidor_revenda,
                nome,
                email,
                celular,
                endereco,
                numero,
                bairro,
                cep,
                complemento,
                cidade,
                data_nascimento,
                cpf,
                reclamado
            ) VALUES (
                '$fone',
                '$fone2',
                $hd_chamado,
                $hd_chamado_origem,
                'Fale Conosco',
                'C',
                '$consumidor_nome',
                '$consumidor_email',
                '$consumidor_celular',
                '$consumidor_endereco',
                '$consumidor_numero',
                '$consumidor_bairro',
                '$consumidor_cep',
                '$consumidor_complemento',
                $cidade,
                $data_nascimento,
                '$cpf',
                '$mensagem'
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
                'Abertura de chamado via Fale Conosco - Site {$site}',
                'Aberto'
            )
        ";

        $resInsItem = pg_query($con,$sqlInsItem);
        $erro .= pg_last_error($con);

        /* upload de nota fiscal */

        if (isset($_FILES['anexo_nf']) AND !empty($_FILES['anexo_nf']['name'])) {
            $data_hora = date("Y-m-d\TH:i:s");
            $destino   = '/tmp/';
            $tamanho   = 1024 * 1024 * 2;

            $extensoes = array('jpg', 'png', 'gif', 'pdf');

            $anx_nf    = $_FILES["anexo_nf"];
            $extensao  = strtolower(end(explode('.', $_FILES['anexo_nf']['name'])));
            
            if (array_search($extensao, $extensoes) === false) {
                $erro .= "Por favor, envie arquivos com as seguintes extensões: jpg, png, pdf ou gif";
            }

            if ($tamanho < $_FILES['anexo_nf']['size']) {
                $erro .= "O arquivo enviado é muito grande, envie arquivos de até 2Mb";
            }

            $nome_final = $login_fabrica.'_'.$hd_chamado.'.jpg';
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
                        "page"     => "externos/roca/principal/faleconosco.php",
                        "typeId"   => "notafiscal",
                        "descricao"=> ""
                    ));

                    $sql = "
                        INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
                        VALUES('$unique_id', $login_fabrica, 'callcenter', 'ativo', '[$obs]', 'callcenter', $hd_chamado);";  
                    $res = pg_query($con, $sql);
                    $erro .= pg_last_error($con);

                }
            } else {
                $msg_erro["msg"][] = "Não foi possível enviar o arquivo, tente novamente";
            }
        }

        if (!empty($erro)) {

            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $msg_erro["msg"][] = "Erro ao fazer o registro do atendimento.";
        } else {
            $res = pg_query($con,"COMMIT TRANSACTION");

            $_POST["consumidor_nome"] = "";
            $_POST["consumidor_email"] = "";
            $_POST["consumidor_celular"] = "";
            $_POST["consumidor_cep"] = "";
            $_POST["consumidor_endereco"] = "";
            $_POST["consumidor_numero"] = "";
            $_POST["consumidor_bairro"] = "";
            $_POST["consumidor_complemento"] = "";
            $_POST["consumidor_cidade"] = "";
            $_POST["consumidor_estado"] = "";
            $_POST["cpf"] = "";
            $_POST["data_nascimento"] = "";
            $_POST["aceito"] = "";
            $_POST["mensagem"] = "";

            $msg = "Atendimento aberto com sucesso!<br> <b>Nº do protocolo:  {$hd_chamado}</b><br> Em breve nossa equipe entrará em contato.";
        }
    }
}
header('Content-type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<title>Fale Conosco </title>

<!-- CSS Files -->
<link rel="stylesheet" href="../../bootstrap3/css/bootstrap.min.css" />
<?php if ($site == "roca") {?>
<link rel="stylesheet" href="<?php echo $URL_BASE;?>principal/roca-web-theme/css/normalize.min.css?v=<?php echo date("YmdHis");?>" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?>principal/roca-web-theme/css/main_roca.css?v=<?php echo date("YmdHis");?>" />
<?php }?>
<?php if ($site == "logasa") {?>
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/slick.css?v=<?php echo date("YmdHis");?>" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/style.min.css?v=<?php echo date("YmdHis");?>" />
<?php }?>
<?php if ($site == "celite") {?>
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/reset.css?v=<?php echo date("YmdHis");?>" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/geral.css?v=<?php echo date("YmdHis");?>" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/contato.css?v=<?php echo date("YmdHis");?>" />
<?php }?>

<?php if ($site == "incepa") {?>
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/main.css?v=<?php echo date("YmdHis");?>" />
<?php }?>

<?php if ($site == "laufen") {?>
<link rel="stylesheet" href="<?php echo $URL_BASE;?><?php echo $site;?>/css/laufen-local-style.css?v=<?php echo date("YmdHis");?>" />
<?php }?>


<style type="text/css">
    body{
        margin: 0;
        padding:0;
        background: #f4f5f5 !important;

    }
    .control-label{
        font-weight: 300;
    }
    .txt_normal{
        font-weight: 300;
    }
    select{
        background-color: white;
        font-family: inherit;
        border: 1px solid #cccccc;
        -webkit-border-radius: 1px;
        -moz-border-radius: 1px;
        -ms-border-radius: 1px;
        -o-border-radius: 1px;
        border-radius: 1px;
        -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        -moz-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        -ms-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        -o-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        color: rgba(0, 0, 0, 0.75);
        display: block;
        font-size: 13px;
        margin: 0 0 12px 0;
        padding: 6px;
        height: 32px;
        width: 100%;
        -webkit-transition: all 0.15s linear;
        -moz-transition: all 0.15s linear;
        -ms-transition: all 0.15s linear;
        -o-transition: all 0.15s linear;
        transition: all 0.15s linear;
    }

    <?php if ($site == "roca") {?>
    .titulo{
        font-family: "HelveticaNeueW02-47LtCn_694048", "Helvetica Neue LT W06_47 Lt Cn", "HelveticaNeueW15-47LtCn_777348", "HelveticaNeueW10-47LtCn_777246", "Swiss721BT-LightCondensed", Arial, Helvetica, sans-serif;
        font-weight: normal;
        font-style: normal;
        font-size: 1.5em;
        color: #000000;

    }
    a.tooltips {
        position: relative;
        display: inline;
    }
    a.tooltips span.tips {
        position: absolute;
        width:440px;
        color: #FFFFFF;
        background: #404040;
        height: 34px;
        line-height: 34px;
        text-align: center;
        visibility: hidden;
        border-radius: 7px;
    }
    a.tooltips span.tips:after {
        content: '';
        position: absolute;
        top: 100%;
        left: 17%;
        margin-left: -8px;
        width: 0; height: 0;
        border-top: 8px solid #404040;
        border-right: 8px solid transparent;
        border-left: 8px solid transparent;
    }
    a:hover.tooltips span.tips {
        visibility: visible;
        opacity: 0.8;
        bottom: 30px;
        left: 50%;
        margin-left: -76px;
        z-index: 999;
    }
    <?php }?>
    .campos_obg{
        color: #d90000;
        font-size: 0.7em;
    }
    <?php if ($site == "logasa") {?>

        .contactArea .formArea {
             width: 100%;
        }
        .contactArea .formArea .field {
             width: 100%;
        }

        a.tooltips {
            position: relative;
            display: inline;
        }
        a.tooltips span.tips {
            position: absolute;
            width:460px;
            color: #FFFFFF;
            background: #404040;
            height: 34px;
            line-height: 34px;
            text-align: center;
            visibility: hidden;
            border-radius: 7px;
            font-size:0.60em;
        }
        .glyphicon-question-sign{
            color: #f19837;
        }
        a.tooltips span.tips:after {
            content: '';
            position: absolute;
            top: 100%;
            left: 17%;
            margin-left: -8px;
            width: 0; height: 0;
            border-top: 8px solid #404040;
            border-right: 8px solid transparent;
            border-left: 8px solid transparent;
        }
        a:hover.tooltips span.tips {
            visibility: visible;
            opacity: 0.8;
            bottom: 30px;
            left: 50%;
            margin-left: -76px;
            z-index: 999;
        }

        ::-webkit-input-placeholder {
           color: #a9a9a9 !important;
        }
         
        :-moz-placeholder { /* Firefox 18- */
           color: #a9a9a9 !important;  
        }
         
        ::-moz-placeholder {  /* Firefox 19+ */
           color: #a9a9a9 !important;  
        }
         
        :-ms-input-placeholder {  
           color: #a9a9a9 !important;  
        }
    <?php }?> 
    <?php if ($site == "celite" || $site == "laufen") {?>

        .frm-default .input-text, .frm-default .input-textarea {
             width: 100%;
        }
        .frm-default .input-textarea {
            max-width: 100%;
        }
        .frm-default .input-submit {
                float: inherit;
        }

        a.tooltips {
            position: relative;
            display: inline;
        }
        a.tooltips span.tips {
            position: absolute;
            width:440px;
            color: #FFFFFF;
            background: #404040;
            height: 34px;
            line-height: 34px;
            text-align: center;
            visibility: hidden;
            border-radius: 7px;
        }
        a.tooltips span.tips:after {
            content: '';
            position: absolute;
            top: 100%;
            left: 17%;
            margin-left: -8px;
            width: 0; height: 0;
            border-top: 8px solid #404040;
            border-right: 8px solid transparent;
            border-left: 8px solid transparent;
        }
        .glyphicon-question-sign{
            color: #232322;
        }
        a:hover.tooltips span.tips {
            visibility: visible;
            opacity: 0.8;
            bottom: 30px;
            left: 50%;
            margin-left: -76px;
            z-index: 999;
        }

    <?php }?>    
    <?php if ($site == "laufen") {?>
        label {
            width: 100%;
        }
    <?php }?>
    <?php if ($site == "incepa") {?>

        .contato .formulario_contato, .contato .content_main, .empresa .formulario_contato, .empresa .content_main {
            width: 100%;
            max-width: 100%;
            margin: none;
        }
        .formulario_contato {
            padding: 0;
            background: #ffffff;
            position: relative;
        }

        .contato .formulario_contato label, .contato .content_main label, .empresa .formulario_contato label, .empresa .content_main label {
            font-family: 'bebas_neuebold';
            color: #696969;
            display: block;
            margin-bottom: 5px;
            font-size: 18px;
            text-transform: uppercase;
        }
        .contato{
            padding: 0px; 
        }
        .contato .formulario_contato select, .contato .content_main select, .empresa .formulario_contato select, .empresa .content_main select{
            padding-left:0px;
        }

        a.tooltips {
            position: relative;
            display: inline;
        }
        a.tooltips span.tips {
            position: absolute;
            width:440px;
            color: #FFFFFF;
            background: #404040;
            height: 34px;
            line-height: 34px;
            text-align: center;
            visibility: hidden;
            border-radius: 7px;
            font-size:0.60em;
        }
        .glyphicon-question-sign{
            color: #696969;
        }
        a.tooltips span.tips:after {
            content: '';
            position: absolute;
            top: 100%;
            left: 17%;
            margin-left: -8px;
            width: 0; height: 0;
            border-top: 8px solid #404040;
            border-right: 8px solid transparent;
            border-left: 8px solid transparent;
        }
        a:hover.tooltips span.tips {
            visibility: visible;
            opacity: 0.8;
            bottom: 30px;
            left: 50%;
            margin-left: -76px;
            z-index: 999;
        }
    <?php }?>

    .texto_anexo{
        font-weight: bold;
        color: red;
        font-size: 0.80em;
    }
/*
    .row {
        width: 500px !important;
        max-width: 500px !important; 
        min-width: 500px !important; 
    }*/
</style>
<?php
    
    $sql_classificacao = "SELECT hd_classificacao, descricao, obriga_campos FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao ASC";
    $res_classificacao = pg_query($con, $sql_classificacao);

    $array_assunto = pg_fetch_all($res_classificacao);

    switch ($site) {
        case 'logasa':
            $class_input    = "input-text";
            $class_select   = "customSelect";
            $class_submit   = "input-submit";
            $class_text     = "input-textarea";
            // $array_assunto = [
            //                 "Serviço de atendimento ao consumidor - SAC" => "Serviço de atendimento ao consumidor - SAC",
            //                 "Consulta de Produtos" => "Consulta de Produtos",
            //                 "Informação Técnica" => "Informação Técnica",
            //                 "Sugestão" => "Sugestão",
            //                 "Reclamação" => "Reclamação",
            //                 "Revendas" => "Revendas"
            //             ];

        break;
        case 'celite':
            $class_label    = "";
            $class_form    = "frm-default";
            $class_input    = "input-text";
            $class_select   = "customSelect";
            $class_submit   = "input-submit";
            $class_text   = "input-textarea";
            // $array_assunto = [
            //                 "Serviço de atendimento ao consumidor - SAC" => "Serviço de atendimento ao consumidor - SAC",
            //                 "Consulta de Produtos" => "Consulta de Produtos",
            //                 "Informação Técnica" => "Informação Técnica",
            //                 "Sugestão" => "Sugestão",
            //                 "Reclamação" => "Reclamação",
            //                 "Revendas" => "Revendas"
            //             ];

        break;
        case 'incepa':
            $class_label    = "";
            $class_form    = "frm-default";
            $class_input    = "input-text";
            $class_select   = "customSelect";
            $class_submit   = "input-submit go bebas_neuebold";
            $class_text   = "input-textarea";
            // $array_assunto = [
            //                 "Serviço de atendimento ao consumidor - SAC" => "Serviço de atendimento ao consumidor - SAC",
            //                 "Consulta de Produtos" => "Consulta de Produtos",
            //                 "Informação Técnica" => "Informação Técnica",
            //                 "Sugestão" => "Sugestão",
            //                 "Reclamação" => "Reclamação",
            //                 "Revendas" => "Revendas"
            //             ];

            break;

        case 'roca':
            $class_label    = "control-label";
            $class_input    = "";
            $class_select   = "";
            $class_submit   = "button";
            $class_text   = "";
            //$array_assunto = [];

        break;
        case 'laufen':
            $class_label    = "";
            $class_form    = "frm-default";
            $class_input    = "input-text";
            $class_select   = "customSelect";
            $class_submit   = "input-submit go bebas_neuebold";
            $class_text   = "input-textarea";
            // $array_assunto = [
            //                 "Comentários" => "Comentários",
            //                 "Inquérito de produtos" => "Inquérito de produtos",
            //                 "Reclamação" => "Reclamação",
            //                 "Encomenda de folhetos" => "Encomenda de folhetos",
            //                 "Relações públicas" => "Relações públicas",
            //                 "Recursos humanos" => "Recursos humanos",
            //                 "Conteúdo do site" => "Conteúdo do site",
            //             ];

        break;
        default:
            $class_label    = "control-label";
            $class_input    = "";
            $class_select   = "";
            $class_submit   = "";
            $class_text   = "";
            //$array_assunto = [];

        break;
    }
?>

</head>
<body>

<div class="container">
    <?php if ($site == "roca") {?>
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-8">
            <h4 class="titulo">Contato Consumidor </h4>
            <p>Telefone: 0800 701 1300</p>
            
        </div>
        <div class="col-sm-2"></div>
    </div>
    <?php }?>
    <?php if ($site == "laufen") {?>
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-8">
            <h4 class="titulo">CONTACTE-NOS</h4>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <?php }?>

    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-8">
            <?php if (count($msg_erro["msg"]) > 0) {?>
                <br />
                <div class="alert alert-danger">
                    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
                </div>
            <?php }?>
            <?php if (!empty($msg)) {?>
                <br />
                <div class="alert alert-success">
                    <h4><?=$msg?></h4>
                </div>
            <?php }?>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <div class="row">
        <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
    <div class="contactArea contato">
        <div class="formArea formulario_contato">
            <form id="frmFaleConosco" class="<?php echo $class_form;?>" enctype="multipart/form-data" method="POST">
<!--  <a class="tooltips" href="#">CSS Tooltips
<span>Tooltip</span></a>
 -->                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                            <div class="pull-right campos_obg">Campos (*) são obrigatórios</div>
                            
                            <div class="form-group <?=(in_array('assunto', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                <label class='<?php echo $class_label;?>' for='assunto'><span class="span_assunto">*</span> Assunto</label>
                                <select name="assunto" class="<?php echo $class_select;?>" id="assunto">
                                    <option value="">Selecione o assunto da sua mensagem</option>
                                    <?php 
                                        foreach ($array_assunto as $key => $value) {
                                           $selected = ($_POST["assunto"] == $value["hd_classificacao"]) ? "selected" : "";
                                           echo '<option '.$selected .' data-obriga_campos="'.$value["obriga_campos"].'" value="'.$value["hd_classificacao"].'">'.utf8_encode($value["descricao"]).'</option>';
                                        }
                                    ?>
                                </select> 
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                            <div class="form-group <?=(in_array('consumidor_nome', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                <label class="<?php echo $class_label;?>" for="consumidor_nome"><span class="span_nome">*</span> Nome Completo</label>
                                <input type='text' class="<?php echo $class_input;?>" name='consumidor_nome' id='consumidor_nome' value="<?php echo (isset($_POST["consumidor_nome"]) && strlen($_POST["consumidor_nome"]) > 0) ? $_POST["consumidor_nome"]  : "";?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>

                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('cpf', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="cpf"><span class="span_cpf">*</span> CPF/CNPJ</label>
                            <input type='text' class="<?php echo $class_input;?>" name='cpf' id='cpf' value="<?php echo (isset($_POST["cpf"]) && strlen($_POST["cpf"]) > 0) ? $_POST["cpf"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>

                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('data_nascimento', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="data_nascimento"><span class="span_nascimento">*</span> Data Nascimento</label>
                            <input type='text' class="<?php echo $class_input;?>" placeholder='dd/mm/aaaa' name='data_nascimento' id='data_nascimento' value="<?php echo (isset($_POST["data_nascimento"]) && strlen($_POST["data_nascimento"]) > 0) ? $_POST["data_nascimento"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_email', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_email"><span class="span_email">*</span> E-mail</label>
                            <input type='email' class="<?php echo $class_input;?>" name='consumidor_email' id='consumidor_email' value="<?php echo (isset($_POST["consumidor_email"]) && strlen($_POST["consumidor_email"]) > 0) ? $_POST["consumidor_email"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_celular', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_celular"><span class="span_celular">*</span> Celular</label>
                            <input type='text' class="<?php echo $class_input;?> telefone" placeholder='(00) 00000-0000' name='consumidor_celular' id='consumidor_celular' value="<?php echo (isset($_POST["consumidor_celular"]) && strlen($_POST["consumidor_celular"]) > 0) ? $_POST["consumidor_celular"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>

                <?php if ($site == "laufen") {?>
                    <div class='row'>
                        <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                        <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                            <div class="field">
                                <div class="form-group <?=(in_array('fone', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                    <label class="<?php echo $class_label;?>" for="fone"><span class="span_fone">*</span> Telefone</label>
                                    <input type='text' class="<?php echo $class_input;?>" name='fone' id='fone' value="<?php echo (isset($_POST["fone"]) && strlen($_POST["consumidor_celular"]) > 0) ? $_POST["consumidor_celular"] : "";?>"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2">
                    </div>
                    <div class='row'>
                        <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                        <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                            <div class="field">
                                <div class="form-group <?=(in_array('fone2', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                    <label class="<?php echo $class_label;?>" for="fone2">Fax</label>
                                    <input type='text' class="<?php echo $class_input;?>" name='fone2' id='fone2' value="<?php echo (isset($_POST["fone2"]) && strlen($_POST["consumidor_celular"]) > 0) ? $_POST["consumidor_celular"] : "";?>"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    </div>
                <?php } ?>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_cep', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_cep"><span class="span_cep">*</span> CEP  <a class="tooltips" href="#"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span><span class="tips">Ao digitar o CEP o endereço será preenchido automaticamente</span></a></label>
                            <input type='text' class="<?php echo $class_input;?>" name='consumidor_cep' id='consumidor_cep' value="<?php echo (isset($_POST["consumidor_cep"]) && strlen($_POST["consumidor_cep"]) > 0) ? $_POST["consumidor_cep"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_endereco', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_endereco"><span class="span_endereco">*</span> Endereço</label>
                            <input type='text' class="<?php echo $class_input;?>" name='consumidor_endereco' id='consumidor_endereco' value="<?php echo (isset($_POST["consumidor_endereco"]) && strlen($_POST["consumidor_endereco"]) > 0) ? $_POST["consumidor_endereco"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_numero', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_numero"><span class="span_numero">*</span> Número</label>
                            <input type='text' class="<?php echo $class_input;?>" name='consumidor_numero' id='consumidor_numero' value="<?php echo (isset($_POST["consumidor_numero"]) && strlen($_POST["consumidor_numero"]) > 0) ? $_POST["consumidor_numero"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class="row">
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_complemento', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_complemento"><span class="span_bairro">*</span> Complemento</label>
                            <input type='text' class="<?php echo $class_input;?>" name='consumidor_complemento' id='consumidor_complemento' value="<?php echo (isset($_POST["consumidor_complemento"]) && strlen($_POST["consumidor_complemento"]) > 0) ? $_POST["consumidor_complemento"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_bairro', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_bairro"><span class="span_bairro">*</span> Bairro</label>
                            <input type='text' class="<?php echo $class_input;?>" name='consumidor_bairro' id='consumidor_bairro' value="<?php echo (isset($_POST["consumidor_bairro"]) && strlen($_POST["consumidor_bairro"]) > 0) ? $_POST["consumidor_bairro"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_cidade', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_cidade"><span class="span_cidade">*</span> Cidade</label>
                            <input type='text' class="<?php echo $class_input;?>" name='consumidor_cidade' id='consumidor_cidade' value="<?php echo (isset($_POST["consumidor_cidade"]) && strlen($_POST["consumidor_cidade"]) > 0) ? $_POST["consumidor_cidade"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_estado', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="<?php echo $class_label;?>" for="consumidor_estado"><span class="span_uf">*</span> UF</label>
                            <select id="consumidor_estado" class="<?php echo $class_select;?>" name="consumidor_estado">
                                <option value="">--</option>
                                <?php foreach ($array_estados() as $uf=>$estado) {
                                        $selected = ($_POST["consumidor_estado"] == $uf) ? "selected" : "";
                                    ?>
                                    <option <?=$selected?> value="<?=$uf?>"><?=$uf;?></option>
                                <?php }?>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('mensagem', $msg_erro['campos'])) ? "has-error" : "" ?>">
                        <label class='<?php echo $class_label;?>' for='mensagem'><span class="span_mensagem">*</span> Mensagem</label>
                        <textarea rows="5" class="<?php echo $class_text;?>" id="mensagem" name="mensagem"><?php echo (isset($_POST["mensagem"]) && strlen($_POST["mensagem"]) > 0) ? $_POST["mensagem"] : "";?></textarea>
                    </div>
                    </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <div class="row">
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8" id="div_anexo_nf">
                        <label for="anexo_nf" >Para agilizar o atendimento recomendamos que anexe a foto da NF e do produto</label>
                        <input type="file" class="" name="anexo_nf" id="anexo_nf" />
                        <span class='texto_anexo'>Anexar arquivos nas extensões: (JPG, PNG, PDF ou GIF) com tamanho de até 2Mb</span>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div><br/><br/>
                
                <?php if ($site == "roca") {?>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8">
                        <div class="form-group <?=(in_array('aceito', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <?php $checked = ($_POST["aceito"] == $uf) ? "checked" : "";?>
                            <input type='checkbox' <?php echo $checked;?> name='aceito' id='aceito' value="t" />
                            <span class="txt_normal" for="aceito">Aceito os termos e condições</span>
                            <p  class="txt_normal">Nos termos da Lei de Proteção de Dados de Caráter Pessoal - a Lei Orgânica 15/1999, de 13 de dezembro, os dados serão integrados num arquivo cujo responsável é a ROCA, S.A. Esta informação será tratada com a máxima privacidade, confidencialidade e segurança de acordo com a legislação vigente. Como tal, informamos que os seus dados ficam também integrados no ficheiro de clientes e utilizadores ROCA. Pode exercer os seus direitos de acesso, retificação, cancelamento ou oposição enviando uma comunicação por escrito para: Roca Sanitario, S.A. Av. Diagonal, 513, 08029 Barcelona, España.</p>
                        </div>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
                <?php }?>
                <div class='row'>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xl-8" align="center" >
                        <p class="tac">
                            <input type="submit" class="<?php echo $class_submit;?>" value="Enviar" name="btn_submit" >
                        </p>
                    </div>
                    <div class="col-sm-2 col-md-2 col-lg-2 col-xl-2"></div>
                </div>
            </div>
            </form>
        </div>
    </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo $URL_BASE;?>principal/roca-web-theme/js/vendor/jquery-1.8.3.min.js"></script>
<!-- <script type="text/javascript" src="../../../plugins/jquery.maskedinput_new.js"></script> -->
<script type="text/javascript" src="../../../admin/js/jquery.mask.js"></script>
<?php if ($site == "logasa") {?>
    <script type="text/javascript" src="<?php echo $URL_BASE;?><?php echo $site;?>/js/slick.min.js"></script>
<?php }?>
<script type="text/javascript">
$(function(){
    $("#fone").mask("(00) 0000-0000");
    $("#fone2").mask("(00) 0000-0000");
    
    $("#consumidor_cep").mask("00000-000");
    $("#consumidor_nome,#produto_serie").keyup(function(e){
        $(this).val($(this).val().toUpperCase());
    });

    $("#consumidor_cep").blur(function() {
        busca_cep($(this).val(),"");
    });
        
    $("#assunto").change(function(){
        valida_campos();
    });
    
    valida_campos();
    $("#data_nascimento").mask("00/00/0000");
    
    var options = {
        onKeyPress : function(cpfcnpj, e, field, options) {
            var masks = ['000.000.000-000', '00.000.000/0000-00'];
            var mask = (cpfcnpj.length > 14) ? masks[1] : masks[0];
            $('#cpf').mask(mask, options);
        }
    };

    $('#cpf').mask('000.000.000-000', options);

    $("#consumidor_estado").change(function(){
        var options = "";
        $.ajax({
            url:"faleconosco.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                ajaxType:"buscaCidades",
                estado:$(this).val()
            }
        })
        .done(function(data){
            if (data.ok) {
                $.each(data.cidades,function(k,v){
                    options += "<option value='"+v.cidade_id+"'>"+v.cidade_nome+"</option>";
                });
                $("#consumidor_cidade").html(options);
            }
        });
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
    $('.telefone').keyup(phoneMask);
});

function valida_campos(){
    var obriga_campos = $("#assunto > option:selected").data("obriga_campos");

    if (obriga_campos == "f"){
        $(".span_cpf, .span_nascimento, .span_email, .span_cep, .span_endereco, .span_numero, .span_bairro, .span_cidade, .span_uf").hide();
    }else{
        $(".span_nome, .span_cpf, .span_nascimento, .span_email, .span_celular, .span_cep, .span_endereco, .span_numero, .span_bairro, .span_cidade, .span_uf, .span_mensagem").show();
    }
}

function busca_cep(cep,method){
    var img = $("<img />", { src: "../../../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });
    if (typeof method == "undefined" || method.length == 0) {
        method = "webservice";
        $.ajaxSetup({
            timeout: 10000
        });
    } else {
        $.ajaxSetup({
            timeout: 10000
        });
    }
    $.ajax({
        async: true,
        url: "../../ajax_cep.php",
        type: "GET",
        data: {
            cep: cep,
            method: method
        },
        beforeSend: function() {
            $("#consumidor_estado").prop("disabled","disabled");
            $("#consumidor_cidade").prop("disabled","disabled");
        },
        success: function(data) {
            results = data.split(";");

            if (results[0] != "ok") {
                alert(results[0]);
            } else {
                $("#consumidor_estado").data("callback", "selectCidade").data("callback-param", results[3]);
                $("#consumidor_estado").val(results[4]);
                $("#consumidor_endereco").val(results[1]);
                $("#consumidor_bairro").val(results[2]);
                $("#consumidor_cidade").val(results[3]);
                $("#consumidor_numero").focus();
                $("#consumidor_estado").removeAttr("disabled");
                $("#consumidor_cidade").removeAttr("disabled");

            }
            $.ajaxSetup({
                timeout: 0
            });
        },
        error: function(xhr, status, error) {
            busca_cep(cep, "database");
        }
    });
}
</script>
</body>
</html>
