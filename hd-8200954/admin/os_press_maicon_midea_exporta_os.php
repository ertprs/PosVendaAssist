<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$programa_insert = $_SERVER['PHP_SELF'];
include_once '../helpdesk/mlg_funciones.php';

require('../class/email/mailer/class.phpmailer.php');

use model\ModelHolder;
use html\HtmlBuilder;
use html\HtmlHelper;

/*  HD 135436(+Mondial))
    Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
    na admin/os_press e nas os_fechamento, sempre nesta função
*/
#HD 311411 - Adicionado Fábrica 6 (TecToy)
$usaDataConserto = ($posto == '4311' or (( !in_array($login_fabrica, array(1,11,172)) ) and $posto==6359) or
                    in_array($login_fabrica, array(2,3,5,6,7,11,14,15,20,30,35,43,45,50)) or $login_fabrica >50);

///30/08/2010 MLG HD 283928  Fábricas que mostram o status de Intervenção e o histórico. Adicionar 43 (Nova Comp.)
$historico_intervencao = (in_array($login_fabrica, array(1,2,3,6,11,14,24,25,35,43,45,50,72,74,81)) or $login_fabrica > 84);

/*
* - Auditoria de OS para determinadas linhas
* marcadas por posto
*/
$historico_auditoria_24hrs = in_array($login_fabrica,array(50));

//28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item
$mostrar_valor_pecas       = in_array($login_fabrica, array(1,42,166));

$fabricas_ped_canc_sem_os  = array(35,94,80,91,104,88);
$fabrica_exclui_anexo_os   = in_array($login_fabrica, array(138));
$fabrica_exclui_anexo_peca = in_array($login_fabrica, array(138,148));
$fabrica_anexo_garantia_estendida = in_array($login_fabrica, array(117));

if(in_array($login_fabrica, array(158)) && $_GET['exportar'] == true){
    //"Até meia noite isso tinha que ficar pronto, por favor, não me julguem ;)"
    $exportado = false;
    if(isset($_GET['os']) && strlen($_GET['os'])){
        $os = $_GET['os'];
    }
    $sql = "SELECT os as codigo,
                    data_abertura as \"dataAgendamento\",
                    data_abertura as \"dataAgendamentoInicio\",
                    data_abertura as \"dataAgendamentoFim\",

                   defeito_reclamado_descricao as assunto,
                   'Telecontrol' as fonte,
                   'PS2' as \"situacaoOrdem\",
                   consumidor_cep as cep,
                   consumidor_endereco as logradouro,
                   consumidor_numero as numero,
                   consumidor_bairro as bairro,
                   consumidor_cidade as cidade,
                   consumidor_estado as estado,
                   CASE WHEN tipo_atendimento = 252 THEN 'OST1602011577636d4c'
                        WHEN tipo_atendimento = 253 THEN 'OST16010711c17a24b0'
                   END as \"ordemTipo\",
                   consumidor_nome as \"razaoNome\",
                   consumidor_cpf as \"cnpjCpf\",
                   'AG160106101abc5ede' as \"agenteCodigo\",
                   referencia as \"equipamentoCodigo\"
                   FROM tbl_os
            JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
            where os = " . $os;

    $res = pg_query($con, $sql);
    $dados = pg_fetch_assoc($res);
    $dados['cliente']['razaoNome'] = $dados['razaoNome'];
    $dados['cliente']['cnpjCpf'] = $dados['cnpjCpf'];
    $dados['endereco']['logradouro'] = utf8_encode($dados['logradouro']);
    $dados['endereco']['cep'] = $dados['cep'];
    $dados['endereco']['numero'] = $dados['numero'];
    $dados['endereco']['bairro'] = utf8_encode($dados['bairro']);
    $dados['endereco']['cidade'] = utf8_encode($dados['cidade']);
    $dados['endereco']['estado'] = utf8_encode($dados['estado']);
    $dados['assunto'] = utf8_encode($dados['assunto']);
    $dados['agendada'] = true;
    $dados['atribuida'] = true;
    $dados['agendaOrdemAgente'][] = array('agente'=> array('codigo'=>$dados['agenteCodigo']));
    $dados['recursoEquipamento'][] = array('equipamento'=> array('codigo'=>$dados['equipamentoCodigo']));
    unset($dados['agenteCodigo']);
    $dados['dataAgendamento'] = DateTime::createFromFormat('Y-m-d',$dados['dataAgendamento']);
    $dados['dataAgendamento'] = mktime($dados['dataAgendamento']->format('H'), $dados['dataAgendamento']->format('i'),$dados['dataAgendamento']->format('s'), $dados['dataAgendamento']->format('m'),$dados['dataAgendamento']->format('d'),$dados['dataAgendamento']->format('Y')) * 1000;

    $dados['dataAgendamentoInicio'] = $dados['dataAgendamento'];
    $dados['dataAgendamentoFim'] = $dados['dataAgendamentoInicio'] + 1000;

    $situacaoOrdem = $dados['situacaoOrdem'];

    unset($dados['situacaoOrdem']);

    $ordemTipo = $dados['ordemTipo'];
    unset($dados['ordemTipo']);
    try{
        $dados['ordemTipo']['codigo'] = $ordemTipo;
        $dados['situacaoOrdem']['codigo'] = $situacaoOrdem;
        unset($dados['nome']);
        unset($dados['cnpjCpf']);
        unset($dados['cep']);
        unset($dados['logradouro']);
        unset($dados['numero']);
        unset($dados['bairro']);
        unset($dados['cidade']);
        unset($dados['estado']);

        exportaEProdutiva($dados);

    $exportado = true;
    }catch(Exception $ex){
        $msg_erro = $ex->getMessage();
        $exportado = false;
    }
}

function exportaEProdutiva($osData){
    global $_serverEnvironment;

    if ($_serverEnvironment == "production") {
        $authorizationKey = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';
    } else {
        $authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447';
    }

    $url = 'http://telecontrol.eprodutiva.com.br/api/ordem';
    $json = json_encode($osData);
    $json = utf8_encode($json);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorizationv2: ".$authorizationKey,
    ));

    $result = curl_exec($ch);
    if(!$result){
        throw new Exception('Não foi possível obter os Dados');
        //echo '>>>>>>>>>> CURL ERROR: (' .$url . " -> ". $result . ' -> ' . $json. ')' . ' *produto: '.$listaBasicaData['produto_referencia'] . ' *peca: ' . $listaBasicaData['peca_referencia']. "\n";
    }
    curl_close($ch);
    return validateResponseReturningArray($result, $json);
}

function validateResponseReturningArray($curlResult, $requestParams = null){
    $arrResult = json_decode($curlResult, true);
    if(array_key_exists('error', $arrResult)){
        throw new Exception('Erro ao exportar Ordem de Serviço: ' . utf8_decode($arrResult['error']['message']));
        //throw new Exception('>>>>>>>>>> Response: (' . $curlResult . ' -> ' . $requestParams . ')' . "\n");

    }
    return $arrResult;
}

if(in_array($login_fabrica, array(151)) && isset($_GET['troca'])){
    $os    = trim($_GET['os']);
    $troca = trim($_GET['troca']);
    $hdChamado = trim($_GET['hdChamado']);

    echo '<script>window.open("formulario_troca_recompra.php?os='.$os.'&acao='.$troca.'&hdChamado='.$hdChamado.'");</script>';
}


if(in_array($login_fabrica,array(152))){
    $os = trim($_GET["os"]);

        $sql_tipo_os = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_tipo_atendimento
                INNER JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
            WHERE tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_os.os = {$os}
                AND tbl_tipo_atendimento.entrega_tecnica IS TRUE
                and tbl_os.excluida is not true";
    $res_tipo_os = pg_query($con, $sql_tipo_os);

    if(pg_num_rows($res_tipo_os) > 0){

            header("Location: os_press_entrega_tecnica.php?os={$os}");
            exit;
    }

}

if(in_array($login_fabrica,array(145))){
    $os = trim($_GET["os"]);

        $sql_tipo_os = "SELECT tbl_tipo_atendimento.grupo_atendimento
                        FROM tbl_os
                        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                        WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}";
    $res_tipo_os = pg_query($con, $sql_tipo_os);

    if(pg_num_rows($res_tipo_os) > 0){

            $grupo_atendimento = strtoupper(pg_fetch_result($res_tipo_os, 0, "grupo_atendimento"));

        if($grupo_atendimento == "R"){
            header("Location: os_press_revisao.php?os={$os}");
            exit;
        }
    }

}


$admin_privilegios="call_center, gerencia";
if($login_fabrica == 74){
    include "../classes/FechamentoOS.php";
    $fechamentoOS = new FechamentoOS();
}

if ($login_fabrica == 7) {
    header ("Location: os_press_filizola.php?os=$os");
    exit;
}

if($login_fabrica == 134){
    $tema          = 'Serviço Realizado';
    $temaPlural    = 'Serviços Realizados';
    $temaMPlural   = 'SERVIÇOS REALIZADOS';
    $temaMaiusculo = 'SERVIÇO REALIZADO';
    $tema_titulo   = 'DEFEITO / SERVIÇO';
    $tema_coluna   = 'REALIZADO';
}else{
    $tema          = 'Defeito Constatado';
    $temaPlural    = 'Defeitos Constatados';
    $temaMPlural   = 'DEFEITOS CONSTATADOS';
    $temaMaiusculo = 'DEFEITO CONSTATADO';
    $tema_titulo   = 'DEFEITOS';
    $tema_coluna   = 'DEFEITO';
    if(isset($novaTelaOs)){
        $tema_coluna = 'CONSTATADO';
    }
}

if ($S3_sdk_OK) {
    include_once S3CLASS;
    if ($fabrica_anexo_garantia_estendida)
        $s3_ge = new anexaS3('ge', (int) $login_fabrica); //Anexo garantia estendida para Elgin
    $S3_online = is_object($s3_ge);

    $s3ve = new anexaS3('ve', (int) $login_fabrica);
    $S3_online = is_object($s3ve);
}

if (in_array($login_fabrica,array(3,11,42,125,126,137,151,172))){

    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $amazonTC = new AmazonTC("os", $login_fabrica);
}
if( in_array($login_fabrica, array(3,11,126,137,151,172)) && $_POST["deletar_imagem_os"] == 'true'){
    if(strlen($_POST["file"]) > 0){
        if($amazonTC->deleteObject($_POST["file"], false,"","")){
            $resp = json_encode(array('apagado' => "true" ));
            echo $resp;
            exit;
        }
    }
}

if ($_POST["excluir_anexo"]) {
    $anexo = $_POST["anexo"];
    $ano   = $_POST["ano"];
    $mes   = $_POST["mes"];

    if (!empty($anexo) && !empty($ano) && !empty($mes)) {
        $amazonTC = new AmazonTC("os", $login_fabrica);
        $amazonTC->deleteObject($anexo, null, $ano, $mes);

        $retorno = array("ok" => true);
    } else {
        $retorno = array("error" => utf8_encode("Anexo não informado"));
    }

    exit(json_encode($retorno));
}

if ($_POST["excluir_anexo_peca"]) {
    $anexo = $_POST["anexo"];

    if (!empty($anexo)) {
        $amazonTC = new AmazonTC("os_item", $login_fabrica);
        $amazonTC->deleteObject($anexo);

        $retorno = array("ok" => true);
    } else {
        $retorno = array("error" => utf8_encode("Anexo não informado"));
    }

    exit(json_encode($retorno));
}

if(in_array($login_fabrica,array(129,161)) AND $_POST["disparar_pesquisa"]){
	$os = $_POST['os'];

    $sql = "SELECT pesquisa
            FROM tbl_pesquisa
            WHERE fabrica = {$login_fabrica} AND categoria = 'ordem_de_servico_email' AND ativo IS TRUE";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) == 0) {
        echo "Erro: Não foi identificado nenhuma pesquisa da categoria 'Ordem de Serviço - E-mail' cadastrada!";
        exit;
    }

	$sql = "SELECT  tbl_os.consumidor_email,
			tbl_os.consumidor_nome,
			tbl_produto.descricao,
			tbl_produto.referencia
		FROM tbl_os
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
		AND tbl_produto.fabrica_i = $login_fabrica
		WHERE os = $os";
	$res = pg_query($con,$sql);

	$email = pg_fetch_result($res,0,'consumidor_email');
	$produto_referencia = pg_fetch_result($res,0,'referencia');
	$produto_nome = pg_fetch_result($res,0,'descricao');
	$consumidor_nome = pg_fetch_result($res,0,'consumidor_nome');
	$link_temp = explode("admin/",$HTTP_REFERER);

	if($login_fabrica == 129){
		$from_fabrica           = "no_reply@telecontrol.com.br";
        	$from_fabrica_descricao = "Pós-Venda Rinnai";
        	$link_pesquisa = $link_temp[0]."externos/rinnai/callcenter_pesquisa_satisfacao2.php?os=$os";
        	$assunto  = "Pesquisa de Satisfação - Rinnai";
	}else{
		$from_fabrica           = "no_reply@telecontrol.com.br";
		$from_fabrica_descricao = "Pós-Venda Cristófoli";
		$link_pesquisa = $link_temp[0]."externos/cristofoli/callcenter_pesquisa_satisfacao2.php?os=$os";
		$assunto  = "Pesquisa de Satisfação - Cristófoli";
	}
    #$email = "guilherme.monteiro@telecontrol.com.br";
	if(strlen($email) == 0){
		$msg_erro = "Email não informado";
	}else{
		$valida_email = filter_var($email,FILTER_VALIDATE_EMAIL);
                if($valida_email === false){
                    $msg_erro = "O email informado não é válido para envio de pesquisa de satisfação ";
                }else{

		            $mensagem = "Produto: $produto_referencia - $produto_nome <br>";
                    $mensagem .= "Ordem de Serviço: $os, <br>";
                    $mensagem .= "Prezado(a) $consumidor_nome, <br>";
                    $mensagem .= "Sua opinião é muito importante para melhorarmos nossos serviços<br>";
                    $mensagem .= "Por favor, faça uma avaliação sobre nossos produtos e atendimento através do link abaixo: <br />";
                    $mensagem .= "Pesquisa de Satisfação: <a href='$link_pesquisa' target='_blank'>Acesso Aqui</a> <br><br>Att <br>Equipe ".$login_fabrica_nome;

                    $headers  = "MIME-Version: 1.0 \r\n";
                    $headers .= "Content-type: text/html \r\n";
                    $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";

		   if(!mail($consumidor_nome .'<'.$email.'>', $assunto, utf8_encode($mensagem), $headers)){
			$msg_erro = "Erro ao enviar email";
		   }else{
			$msg_erro = "ok";
		   }
		}
	}

	echo $msg_erro;
	exit;
}

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
    Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
    Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
    Para saber se tem anexo:temNF($os, 'bool');
    Para mostrar a imagem:  echo temNF($os); // Devolve os links dentro de um <TABLE> (table>tr>td>a>img)
                            echo temNF($os, implode('<br/>','img')); // Devolve a imagem (<img src='imagem'>), se tiver outros tipos, usar 'url'
*/
include_once('../anexaNF_inc.php');

/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = excluirNF($img_nf);

    if (!$excluiu)
        die('ko|Não foi possível excluir o arquivo solicitado. ');

    $nome_anexo = preg_replace("/^.*?([xeros]_)?(\d+)(-\d)?\..*$/", "$1$2", $img_nf);
    $param_chklst = false;
    $param_admin = false;

    if ($login_fabrica == '1') {
        $arq_info = pathinfo($img_nf);

        if (!empty($arq_info)) {
            $arr_fn = explode('_', $arq_info['filename']);

            if (array_key_exists(1, $arr_fn) and $arr_fn[1] == 'admin') {
                $param_chklst = true;
                $param_admin = true;
            }
        }
    }

    if ($excluiu)
        die("ok|" . temNF($nome_anexo, 'linkEx', '', $param_admin, $param_chklst) . "|$img_nf|$nome_anexo");
    exit($ret);

}// FIM Excluir imagem

/**
 * - Ajax de Cancelamento de OS
 *
 * @params String ajax Int os String acao
 * @author William Ap. Brandino
 */

if ($_POST['ajax'] == "cancelar_os") {
    $os = $_POST['os'];
    $acao = $_POST['acao'];
    $motivo = utf8_decode($_POST['motivo']);

    $res = pg_query($con,"BEGIN TRANSACTION");
    if($acao == "liberar"){
        $text = "Liberação";
        $sql = "
            UPDATE  tbl_os
            SET     excluida = FALSE
            WHERE   os = $os
        ";
        $res = pg_query($con,$sql);
    }else{
         $sql = "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  ,
                    admin
                ) VALUES (
                    $os,
                    '$motivo' ,
                    15       ,
                    $login_admin
                );";
// echo $sql;
        $res = pg_query ($con,$sql);

        $text = "Cancelamento";
        $sql = "
            UPDATE  tbl_os
            SET     excluida = TRUE
            WHERE   os = $os
        ";
        $res = pg_query($con,$sql);
    }

    if(pg_last_error($con)){
//     echo pg_last_error($con);
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
    }else{
        if($motivo != ""){
            $sql = "INSERT INTO tbl_os_interacao
                    (programa,fabrica, os, admin, comentario, interno, exigir_resposta)
                    VALUES
                    ('$programa_insert',$login_fabrica, $os, $login_admin, '$text de OS. Motivo: $motivo', TRUE, FALSE)";
            $res = pg_query($con,$sql);
        }
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("result"=>"ok"));
    }
    exit;
}

##Testes
#if ($login_admin == 449 OR $login_admin == 398 OR $login_admin == 805) {
#   header ("Location: os_press_20080515.php?os=$os");
#   exit;
#}

$sql = "SELECT  tbl_fabrica.os_item_subconjunto
        FROM    tbl_fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
    $os_item_subconjunto = pg_fetch_result ($res,0,os_item_subconjunto);
    if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

$navegador = $_SERVER['HTTP_USER_AGENT'];
$mozilla = "Firefox";
$pos = strpos($navegador, $mozilla);

$btn_acao              = $_POST['btn_acao'];

$os                    = (int)trim($_GET['os']);
if(strlen($os) > 10) {
	include_once "cabecalho.php";
	echo "<h1>Os $os não encontrada</h1>";
	include_once "rodape.php";
	exit;
}

if (!empty($_GET['sua_os']) and $login_fabrica == 1) {
    $get_sua_os = $_GET['sua_os'];

    $len = strlen($get_sua_os) - 7;
    $num_sua_os = substr($get_sua_os, -7);
    $posto_sua_os = substr($get_sua_os, 0, $len);

    $sqlSuaOS = "SELECT os FROM tbl_os
                 JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
                   AND tbl_posto_fabrica.fabrica = $login_fabrica
                 WHERE tbl_os.sua_os = '$num_sua_os'
                 AND codigo_posto = '$posto_sua_os'
                 AND tbl_os.fabrica = $login_fabrica";

    $qryOs = pg_query($con, $sqlSuaOS);

    if (pg_num_rows($qryOs) == 1) {
        $os = pg_fetch_result($qryOs, 0, 'os');
    }
}

$os                    = preg_replace('/\D/','', $os);
$mostra_valor_faturada = trim($_GET['mostra_valor_faturada']);

#HD 367226 - Gabriel Silveira
if ($login_fabrica==24 and $_GET['checa_reinc']=='s' and strlen($os)>0 ){

    $sql      = "SELECT fn_valida_os_reincidente($os, $login_fabrica)";//HD 256659
    $res      = @pg_query($con,$sql);

}

if ($mostra_valor_faturada == 'sim' and !empty($os)) { // HD 181964
    echo "<script>window.open('produto_valor_faturada.php?os=$os','','height=300, width=650, top=20, left=20, scrollbars=yes')</script>";
}

if ($btn_acao == 'gravar_orientacao') { # HD 68629 para Colormaq

    $orientacao_sac = trim($_POST['orientacao_sac']);
    $orientacao_sac = htmlentities($orientacao_sac, ENT_QUOTES);
    $orientacao_sac = nl2br($orientacao_sac);

    if (strlen ($orientacao_sac) == 0) {
        $orientacao_sac  = "null";
    }


    $sql = "UPDATE tbl_os_extra SET orientacao_sac = trim('$orientacao_sac') WHERE tbl_os_extra.os = $os;";
    $res = pg_query($con, $sql);

    $msg_erro = pg_last_error($con);

    if (strlen($msg_erro) == 0) {
        echo "<script language='javascript'>\n";
        echo "  alert('Orientação gravada com sucesso.');\n";
        echo "</script>\n";
    } else {
        echo "<script language='javascript' >\n";
        echo "  alert('Erro. Não foi possível gravar a Orientação.');\n";
        echo "</script>\n";
    }

}

$apagarJustificativa = trim($_GET['apagarJustificativa']);
$justificativa       = trim($_GET['justificativa']);
$bloqueioint         = trim($_GET['bloqueioint']);  // MLG 14-12-2010 - HD 326633

#Adicionado por Fábio - 19/10/2007 - HD 6107
if (strlen($os) > 0 AND strlen($apagarJustificativa) > 0) {

    $sql = "SELECT observacao FROM tbl_os_status WHERE os = $os AND fabrica_status = $login_fabrica AND os_status = $apagarJustificativa";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $observacao = pg_fetch_result($res, 0, 'observacao');
        $tmp        = substr($observacao, 0, strpos($observacao, 'Justificativa:'));

        $justificativa = "'".$tmp." Justificativa: ".$justificativa."'";

        $sql = "UPDATE tbl_os_status SET observacao = $justificativa WHERE os_status = $apagarJustificativa";
        $res = pg_query($con, $sql);

        header("Location: $PHP_SELF?os=$os");
        exit;

    }

}


if(in_array($login_fabrica, [167, 203]) && isset($enviar_orcamento)){
    $dados_email = $_POST['dados_email'];
    $dados_email = json_decode($dados_email,true);

    $mensagem = "Prezado cliente <br/>";
    $mensagem .= "Informamos que o equipamento da <br/>";
    $mensagem .= "Ordem de serviço:".$dados_email["geral"]["os"]."<br/>";
    $mensagem .= "Modelo: ".$dados_email["geral"]["produto_referencia"]." - ".utf8_decode($dados_email["geral"]["produto_descricao"])."<br/>";
    $mensagem .= "Série:".$dados_email["geral"]["numero_serie"]."<br/>";
    $mensagem .= "Está com o orçamento pronto, Segue o valor e a prescrição do que deve ser feito no equipamento,<br/> Peço por gentileza que verifique a aprovação para darmos continuidade do processo, <br/> Com aprovação ou não do orçamento, o valor de R$120.00 Será cobrado referente à mão de Obra.";
    $mensagem .= "Serviços <br/><br/>";

    $count = 0;
    foreach ($dados_email as $key) {

        if(empty($dados_email[$count]["descricao_peca"])){
            continue;
        }

        $mensagem .= "Peça: ".utf8_decode($dados_email[$count]["descricao_peca"])."<br>";
        $mensagem .= "Quantidade: ".$dados_email[$count]["qtde_pecas"]."<br>";
        $mensagem .= "Valor unitário: ".$dados_email[$count]["preco_unitario"]."<br><br>";
        $count++;
    }

    $mensagem .= "Valor adicional: ".$dados_email["geral"]["valor_adicional"]."<br>";
    $mensagem .= "Valor total das peças: ".$dados_email["geral"]["valor_total_pecas"]."<br>";
    $mensagem .= "Valor total geral: ".$dados_email["geral"]["total_geral"]."<br>";
    $email = $dados_email["geral"]["email_consumidor"];
    $assunto = "ORÇAMENTO - ".$dados_email["geral"]["posto_nome"];

    $headers  = "MIME-Version: 1.0 \r\n";
    $headers .= "Content-type: text/html \r\n";
    $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";

    if(!mail($consumidor_nome .'<'.$email.'>', $assunto, $mensagem, $headers)){
        $msg_erro = "Erro ao enviar email";
    }else{
        $msg_erro = "ok";
    }
    echo $msg_erro;
    exit;
}

# HD 44202 - Ultimo Status para as Aprovações de OS aberta a mais de 90 dias
if (strlen($os) > 0 AND in_array($login_fabrica,array(3,15))){
    $sql = "SELECT status_os, observacao FROM tbl_os_status WHERE os = $os AND status_os IN(120,122,123,126,140,141,142,143) AND fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1";

    $res_status = @pg_query($con,$sql);

    if (@pg_num_rows($res_status) >0) {
        $status_os_aberta     = trim(pg_fetch_result($res_status,0,status_os));
        $status_os_aberta_obs = trim(pg_fetch_result($res_status,0,observacao));
    }
}


#------------ Detecta OS para Auditoria -----------#
$auditoria = $_GET['auditoria'];
$auditoria_motivo = '';
if ($auditoria == 't') {

    $btn_acao                 = $_POST['btn_acao'];
    $os                       = $_POST['os'];
    $sua_os                   = $_POST['sua_os'];
    $posto                    = $_POST['posto'];
    $justificativa_reprova    = $_POST['justificativa_reprova'];

    if(strlen($posto)==0)$posto    = $_GET['posto'];

    //--=== As ações de cada botão ===========================================================
    if ($btn_acao == 'Reprovar') {
        $sql = "UPDATE tbl_os_extra SET status_os = 13 WHERE os = $os";
        $res = pg_query ($con,$sql);

        $sql = "UPDATE tbl_os_item SET
                    admin_liberacao = $login_admin,
                    liberacao_pedido           = 'f',
                    liberacao_pedido_analisado = TRUE
                WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
        $sql = "SELECT fn_auditoria_previa_admin($os,$login_admin,'f','0')";
        $res = pg_query ($con,$sql);

        /* EXCLUI A OS */
        $justificativa_exclusao = " OS CANCELADA. Após ser auditada, a OS foi cancelada. <br>Justificativa da Fábrica: $justificativa_reprova";
        $sql =  "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  ,
                    admin
                ) VALUES (
                    $os    ,
                    '$justificativa_exclusao',
                    15,
                    $login_admin
                )";
        $res = pg_query($con,$sql);

        $sql = "UPDATE tbl_os SET
                    excluida = true,
                    data_fechamento = CURRENT_DATE,
                    finalizada      = CURRENT_TIMESTAMP
                WHERE  tbl_os.os           = $os
                AND    tbl_os.fabrica      = $login_fabrica;";
        $res = pg_query($con,$sql);


        /* INSERE COMUNICADO PARA O POSTO */
        $sql = "INSERT INTO tbl_comunicado (
            descricao              ,
            mensagem               ,
            tipo                   ,
            fabrica                ,
            obrigatorio_os_produto ,
            obrigatorio_site       ,
            posto                  ,
            ativo
        ) VALUES (
            'OS $sua_os foi CANCELADA',
            'Após ser auditada, a OS $sua_os foi cancela. <br><br>Justificativa da Fábrica: $justificativa_reprova',
            'Pedido de Peças' ,
            $login_fabrica    ,
            'f'               ,
            't'               ,
            $posto            ,
            't'
        );";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);

    }elseif ($btn_acao == 'Analisar') {
        //Analisar: os não retorna mais no dia atual para auditoria apenas no dia seguinte
        $sql = "UPDATE tbl_os_extra SET
                    status_os = 20,
                    data_status = current_date
                WHERE os = $os";
        $res = pg_query ($con,$sql);

        $sql = "UPDATE tbl_os_item SET
                    admin_liberacao = $login_admin,
                    liberacao_pedido = 'f',
,                   liberacao_pedido_analisado = TRUE
                WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
        $sql = "SELECT fn_auditoria_previa_admin ($os,$login_admin,'f','0')";
        $res = pg_query ($con,$sql);

        //seta cancelada = false pq na função cancela toda OS nãi liberada.
        $sql = "UPDATE tbl_os_auditar set cancelada = false where os = $os";
        $res = pg_query ($con,$sql);

    }elseif ($btn_acao == 'Aprovar') {
        $sql = "UPDATE tbl_os_extra SET status_os = 19 WHERE os = $os";
        $res = pg_query ($con,$sql);

        $sql = "UPDATE tbl_os_item SET
                    admin_liberacao = $login_admin,
                    liberacao_pedido = 't' ,
,                   liberacao_pedido_analisado = TRUE,
                    data_liberacao_pedido = CURRENT_TIMESTAMP
                WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
        $sql = "SELECT fn_auditoria_previa_admin ($os,$login_admin,'t','0')";
        $res = pg_query ($con,$sql);
    }elseif ($btn_acao == 'Aprovar sem Mão de Obra'){
        $sql = "UPDATE tbl_os_extra SET status_os = 19 WHERE os = $os";
        $res = pg_query ($con,$sql);


        $sql = "SELECT fn_auditoria_previa_admin ($os,$login_admin,'t',CASE WHEN tbl_os.mao_de_obra NOTNULL THEN tbl_os.mao_de_obra ELSE tbl_produto.mao_de_obra END) FROM tbl_os JOIN tbl_produto USING(produto) WHERE os = $os AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);


    }
    $os = "";
    //--======================================================================================


    //hd 7118 - dependendo do dia da semana não deve contar sábado e domingo
    $sql_dia_semana = "SELECT  EXTRACT(dow FROM now()) + 1 AS dia_da_semana";
    $res_dia_semana = pg_query($con,$sql_dia_semana);

    switch(pg_fetch_result($res_dia_semana, 0, 0)) {
        case 1:
            $intervalo = '4 DAYS'; break;
        case 5:
        case 6:
        case 7:
            $intervalo = '3 DAYS'; break;
        default:
            $intervalo = '5 DAYS'; break;
    }

    $sql = "
        SELECT (
            SELECT  count(tbl_os.os) AS total_reincidente
            FROM tbl_os ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
    }
    $sql .= " JOIN tbl_os_auditar  USING(os)
            JOIN tbl_posto         USING(posto)
            JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            WHERE tbl_os.posto           = $posto
            AND   tbl_os.fabrica         = $login_fabrica
            AND   tbl_os_auditar.auditar = 1
            AND   tbl_os.auditar          IS TRUE
            AND   tbl_os_auditar.liberado IS NOT TRUE
            AND   tbl_os_auditar.cancelada IS NOT TRUE
            AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
            AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL) ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " AND tbl_produto.linha = " . abs($_GET['linha']);
    }
    $sql .= " ) AS total_reincidente,
        (
            SELECT  count(tbl_os.os) AS total_reincidente
            FROM tbl_os ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
    }
    $sql .= " JOIN tbl_os_auditar  USING(os)
            JOIN tbl_posto         USING(posto)
            JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            WHERE tbl_os.posto           = $posto
            AND   tbl_os.fabrica         = $login_fabrica
            AND   tbl_os_auditar.auditar = 2
            AND   tbl_os.auditar          IS TRUE
            AND   tbl_os_auditar.liberado IS NOT TRUE
            AND   tbl_os_auditar.cancelada IS NOT TRUE
            AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
            AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL) ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " AND tbl_produto.linha = " . abs($_GET['linha']);
    }
    $sql .= " ) AS total_tres_pecas,
        (
            SELECT  count(tbl_os.os) AS total_reincidente
            FROM tbl_os ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
    }
    $sql .= " JOIN tbl_os_auditar  USING(os)
            JOIN tbl_posto         USING(posto)
            JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            WHERE tbl_os.posto           = $posto
            AND   tbl_os.fabrica         = $login_fabrica
            AND   tbl_os_auditar.auditar = 3
            AND   tbl_os.auditar          IS TRUE
            AND   tbl_os_auditar.liberado IS NOT TRUE
            AND   tbl_os_auditar.cancelada IS NOT TRUE
            AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
            AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL) ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " AND tbl_produto.linha = " . abs($_GET['linha']);
    }
    $sql .= " ) AS total_datas_diferentes";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) == 1){
        $total_reincidente      = pg_fetch_result ($res,0,total_reincidente)     ;
        $total_tres_pecas       = pg_fetch_result ($res,0,total_tres_pecas)      ;
        $total_datas_diferentes = pg_fetch_result ($res,0,total_datas_diferentes);
    }

    $sql = "SELECT  tbl_os.os                     ,
                    tbl_os_auditar.auditar        ,
                    tbl_os_auditar.descricao      ,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome
              FROM  tbl_os ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
    }
    $sql .= " JOIN  tbl_os_auditar ON tbl_os.os = tbl_os_auditar.os
            JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            WHERE tbl_os.posto   = $posto
            AND   tbl_os.fabrica = $login_fabrica
            AND   tbl_os.auditar          IS TRUE
            AND   tbl_os_auditar.liberado IS NOT TRUE
            AND   tbl_os_auditar.cancelada IS NOT TRUE
            AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
            AND (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL) ";
    if (is_numeric($_GET['linha'])) {
        $sql .= " AND tbl_produto.linha = " . abs($_GET['linha']);
    }
    $sql .= " LIMIT 1";

    $res = pg_query ($con,$sql);

    if(pg_num_rows($res) == 1){
        $os           = pg_fetch_result ($res,0,os)          ;
        $auditar      = pg_fetch_result ($res,0,auditar)     ;
        $descricao    = pg_fetch_result ($res,0,descricao)   ;
        $nome         = pg_fetch_result ($res,0,nome)        ;
        $codigo_posto = pg_fetch_result ($res,0,codigo_posto);

        echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#003399'  align='center' width='700'>";
        echo "<tr>";
        echo "<td colspan='2'><b><font color='#000099'>AUDITORIA DE OS</font></b></td>";
        echo "<td rowspan='3'><font size='1'>OS Reincidentes: <b>$total_reincidente</b><br>OS com 3 ou mais peças: <b>$total_tres_pecas</b><br>OS com peças lançadas em datas diferentes: <b>$total_datas_diferentes</b></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='subtitulo'>Posto</td>";
        echo "<td class='Conteudo'>$codigo_posto - $nome</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='subtitulo'>Motivo</td>";
        echo "<td class='Conteudo'><font color='#990000'>$descricao</font></td>";
        echo "</tr>";
        echo "</table>";
        echo "<br>";
        echo "</div>";
    }else{
        echo "<center><h1>Todas OS desse posto foram auditadas</h1>";
        echo "<a href=\"javascript:window.close();\">Fechar esta janela</a></center>";
        exit;
    }
}

if ($auditar === false) {
    echo "<p><h1>Todas as OS auditadas </h1><p>";
    exit;
}

if($login_fabrica==19){//hd 19833 3/6/2008
    $sql_revendas = "tbl_revenda.cnpj AS revenda_cnpj                                  ,
                     tbl_revenda.nome AS revenda_nome                                  ,";

    $join_revenda = "LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda";
}else{//lpad 25/8/2008 HD 34515
    $sql_revendas = "tbl_os.revenda_nome                                               ,
                     lpad(tbl_os.revenda_cnpj, 14, '0') AS revenda_cnpj                ,";
}

if(isset($novaTelaOs)){
    $left_join_produto = "LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                          LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto ";
    $campo_serie = "tbl_os_produto.serie";
}else{
    $left_join_produto = " LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
    $campo_serie = "tbl_os.serie";
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) == 0) $os = $_GET['os'];
if (strlen ($os) > 0) {

    $sql_extra  = '';
    $join_extra = '';

    if ($login_fabrica == 52) {
        $sql_extra .= 'tbl_motivo_reincidencia.descricao as motivo_reincidencia_desc,';
    }

    if ($login_fabrica == 85) {
        $sql_extra  .= "tbl_hd_chamado_extra.array_campos_adicionais,\n";
        $join_extra .= "LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os\n";
    }

    if ($login_fabrica == 153) {
        $sql_extra .= "tbl_os.codigo_fabricacao,\n";
    }

    if (in_array($login_fabrica, array(30,59,87,94,156,158,162))) {
        $joinTblTec = "LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}";
        $col_tec = 'tbl_tecnico.nome AS tecnico_nome,';
    } else {
        $col_tec = 'tbl_os.tecnico_nome,';
    }

    if($login_fabrica == 151){
        $os_bloqueada = " tbl_os_campo_extra.os_bloqueada, ";
    }

    $sql = "SELECT  tbl_os.posto,
                    tbl_os.sua_os,
                    tbl_os.sua_os_offline,
                    tbl_admin.login                               AS admin,
		    tbl_admin.nome_completo                       AS admin_nome_completo,
                    troca_admin.login                             AS troca_admin,
                    TO_CHAR(tbl_os.data_digitacao,  'DD/MM/YYYY HH24:MI:SS') AS data_digitacao,
                    TO_CHAR(tbl_os.data_hora_abertura,'DD/MM/YYYY HH24:MI:SS')  AS data_hora_abertura,
                    TO_CHAR(tbl_os.data_abertura,   'DD/MM/YYYY') AS data_abertura,
                    TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
                    TO_CHAR(tbl_os.finalizada,      'DD/MM/YYYY') AS finalizada,
                    tbl_os.tipo_atendimento,
                    tbl_os.tecnico,
                    $col_tec
                    $sql_extra
                    tbl_tipo_atendimento.codigo                   AS codigo_atendimento,
                    tbl_tipo_atendimento.descricao                AS nome_atendimento,
                    tbl_os.consumidor_nome,
                    tbl_os.consumidor_fone,
                    tbl_os.consumidor_celular,
                    tbl_os.consumidor_fone_comercial,
                    tbl_os.consumidor_endereco,
                    tbl_os.consumidor_numero,
                    tbl_os.consumidor_complemento,
                    tbl_os.consumidor_bairro,
                    tbl_os.consumidor_cep,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_estado,
                    tbl_os.consumidor_cpf,
                    tbl_os.consumidor_email,
                    tbl_os.consumidor_fone_recado,
                    $sql_revendas
                    tbl_os.nota_fiscal,
                    tbl_os.cliente,
                    tbl_os.revenda,
                    tbl_os.os_reincidente                         AS reincidencia,
                    tbl_os.motivo_atraso,
                    TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY')         AS data_nf,
                    tbl_defeito_reclamado.descricao               AS defeito_reclamado,
                    tbl_os.defeito_reclamado_descricao,
                    tbl_os.marca,
                    tbl_defeito_constatado.descricao              AS defeito_constatado,
                    tbl_defeito_constatado_grupo.descricao        AS defeito_constatado_grupo,
                    tbl_defeito_constatado.codigo                 AS defeito_constatado_codigo,
                    tbl_causa_defeito.descricao                   AS causa_defeito,
                    tbl_causa_defeito.codigo                      AS causa_defeito_codigo,
                    tbl_os.aparencia_produto,
                    tbl_os.acessorios,
                    tbl_os.consumidor_revenda,
                    tbl_os.hd_chamado,
                    CASE WHEN tbl_os.fabrica IN(52) AND tbl_os.hd_chamado IS NOT NULL
                         THEN tbl_os.obs || '\nMotivo: ' || tbl_os.observacao
                         WHEN tbl_os.fabrica IN(169,170) AND tbl_os.hd_chamado IS NOT NULL
                         THEN tbl_os.obs || '\n\n<b>Observações do Callcenter:</b> <br/>' || tbl_os.observacao
                         ELSE tbl_os.obs
                    END AS obs,
                    tbl_os.observacao AS obs_callcenter,
                    tbl_os.rg_produto,
                    tbl_os.excluida,
                    tbl_os.promotor_treinamento,
                    tbl_os.embalagem_original,
                    tbl_os.autorizacao_cortesia                                       ,
                    tbl_os.certificado_garantia                                       ,
                    tbl_produto.produto,
                    tbl_produto.referencia                                            ,
                    tbl_produto.referencia_fabrica                                    ,
                    tbl_produto.descricao                                             ,
                    tbl_produto.voltagem                                              ,
                    tbl_produto.valor_troca                                           ,
                    tbl_produto.parametros_adicionais AS produto_parametros_adicionais ,
                    tbl_os.qtde_produtos                                              ,
                    $campo_serie                                                      ,
                    tbl_os.type                                                       ,
                    tbl_os.serie_reoperado                                            ,
                    tbl_os.posto                                                      ,
                    tbl_os.codigo_fabricacao                                          ,
                    tbl_os.troca_garantia                                             ,
                    tbl_os.troca_via_distribuidor                                     ,
                    tbl_os.troca_garantia_admin                                       ,
                    tbl_os.justificativa_adicionais                                   ,
                    tbl_os.contrato                                                   ,
                    to_char(tbl_os.troca_garantia_data,'DD/MM/YYYY') AS troca_garantia_data ,
                    tbl_posto_fabrica.codigo_posto               AS posto_codigo      ,
                    tbl_posto_fabrica.reembolso_peca_estoque                          ,
                    tbl_posto.nome                               AS posto_nome        ,
                    tbl_posto.posto                              AS codigo_posto      ,
                    tbl_posto_fabrica.contato_endereco                           AS posto_endereco    ,
                    tbl_posto_fabrica.contato_numero                             AS posto_num         ,
                    tbl_posto_fabrica.contato_complemento                        AS posto_complemento ,
                    tbl_posto_fabrica.contato_cep                                AS posto_cep         ,
                    tbl_posto_fabrica.contato_cidade                             AS posto_cidade      ,
                    tbl_posto_fabrica.contato_estado                             AS posto_estado      ,
                    tbl_posto_fabrica.contato_fone_comercial                               AS posto_fone        ,
                    tbl_posto_fabrica.credenciamento                             AS situacao_posto ,
                    tbl_os_extra.os_reincidente                                       ,
                    tbl_os_extra.natureza_servico,
                    tbl_os_extra.recolhimento                                         ,
                    tbl_os_extra.orientacao_sac                                       ,
                    tbl_os_extra.serie_justificativa                                  ,
                    tbl_os_extra.reoperacao_gas                                       ,
                    tbl_os_extra.obs_nf                                               ,
                    tbl_os_extra.hora_tecnica,
                    tbl_os_extra.qtde_horas,
                    tbl_os_extra.recomendacoes,
                    tbl_os_extra.obs_adicionais,
                    tbl_os_extra.pac AS numero_pac,
                    tbl_os_extra.pac,
                    tbl_os_extra.data_fabricacao,
                    CASE WHEN tbl_os.fabrica IN (30)
                        THEN tbl_os_troca.ressarcimento
                        ELSE tbl_os.ressarcimento
                    END AS ressarcimento,
                    tbl_os.obs_reincidencia,
                    {$motivo_reincidencia}
                    tbl_os.solucao_os,
                    tbl_os.fisica_juridica,
                    tbl_marca.marca,
                    tbl_marca.nome as marca_nome,
                    tbl_os.tipo_os,
                    tbl_tipo_os.descricao AS tipo_os_descricao,
                    (SELECT observacao
                       FROM tbl_os_status
                      WHERE os        = tbl_os.os
                        AND   fabrica_status = $login_fabrica
                        AND   status_os = 15
                      ORDER BY data DESC LIMIT 1)                               AS motivo_exclusao,
                    tbl_os.nota_fiscal_saida,
                    TO_CHAR(tbl_os.data_nf_saida, 'DD/MM/YYYY')                 AS data_nf_saida,
                    TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI')         AS data_conserto,
                    tbl_os.troca_faturada,
                    tbl_os_extra.tipo_troca,
                    tbl_os.os_posto,
                    TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY HH24:MI')            AS data_ressarcimento,
                    tbl_extrato.extrato,
                    TO_CHAR(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS data_previsao,
                    TO_CHAR(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS data_pagamento,
                    TO_CHAR(tbl_extrato.liberado, 'DD/MM/YYYY')                 AS liberado,
                    tbl_extrato.protocolo,
                    tbl_os.fabricacao_produto,
                    tbl_os.qtde_km,
                    tbl_os.quem_abriu_chamado,
                    tbl_os.os_numero,
                    tbl_os_interacao.comentario AS observacao_log_usuarios,
                    tbl_os_troca.observacao   AS observacao_troca,
                    tbl_os.valores_adicionais,
                    tbl_os_troca.gerar_pedido   AS gerar_pedido,
                    tbl_os_troca.admin_autoriza,
                    tbl_os.consumidor_nome_assinatura AS contato_consumidor,
                    tbl_os.condicao AS contador,
                    tbl_os.cortesia,
                    tbl_os.key_code,
                    tbl_linha.nome AS nome_linha,
                    tbl_familia.descricao AS descricao_familia,
                    tbl_os.nf_os,
                    tbl_os.qtde_diaria,
                    tbl_os.qtde_hora,
                    tbl_os.hora_tecnica as os_hora_tecnica,
                    $os_bloqueada
                    tbl_os_campo_extra.campos_adicionais AS os_campos_adicionais,
                    tbl_os_campo_extra.valores_adicionais AS os_valores_adicionais,
                    TO_CHAR(tbl_os_extra.inicio_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS inicio_atendimento,
                    TO_CHAR(tbl_os_extra.termino_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS termino_atendimento,
		    tbl_os_extra.regulagem_peso_padrao,
		    tbl_status_checkpoint.descricao AS status_checkpoint
            FROM tbl_os
            JOIN tbl_posto                   ON tbl_posto.posto               = tbl_os.posto
            LEFT JOIN tbl_tipo_os ON tbl_tipo_os.tipo_os = tbl_os.tipo_os
            JOIN tbl_posto_fabrica           ON tbl_posto_fabrica.posto       = tbl_os.posto
                                           AND tbl_posto_fabrica.fabrica     = $login_fabrica
            LEFT JOIN tbl_os_extra           ON tbl_os.os                     = tbl_os_extra.os
            LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.fabrica = $login_fabrica
            LEFT JOIN tbl_os_troca           ON tbl_os.os                     = tbl_os_troca.os
            LEFT JOIN tbl_extrato            ON tbl_extrato.extrato           = tbl_os_extra.extrato
                                           AND tbl_extrato.fabrica           = $login_fabrica
            LEFT JOIN tbl_extrato_pagamento  ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
            LEFT JOIN tbl_admin              ON (tbl_os.admin                 = tbl_admin.admin)
            LEFT JOIN tbl_admin troca_admin  ON tbl_os.troca_garantia_admin   = troca_admin.admin
            LEFT JOIN tbl_defeito_reclamado  ON tbl_os.defeito_reclamado      = tbl_defeito_reclamado.defeito_reclamado
            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado     = tbl_defeito_constatado.defeito_constatado
            LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
            LEFT JOIN tbl_motivo_reincidencia USING (motivo_reincidencia)
            LEFT JOIN tbl_causa_defeito          ON tbl_os.causa_defeito = tbl_causa_defeito.causa_defeito
            $left_join_produto
            $join_extra
            $joinTblTec
            LEFT JOIN tbl_tipo_atendimento       ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
            LEFT JOIN tbl_marca                  ON tbl_produto.marca    = tbl_marca.marca
            LEFT JOIN tbl_os_interacao           ON tbl_os.os            = tbl_os_interacao.os
            LEFT JOIN tbl_linha                  ON tbl_produto.linha    = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
            LEFT JOIN tbl_familia                ON tbl_produto.familia  = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
		    $join_revenda
            LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            WHERE   tbl_os.os = $os
            AND     tbl_os.fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);
    #echo nl2br($sql);

#   echo $sql . "<br>- ". pg_num_rows ($res);

    if (pg_num_rows ($res) > 0) {
        $hd_chamado                  = pg_fetch_result($res, 0, "hd_chamado");
        $posto                       = pg_fetch_result($res, 0, 'posto');
        $sua_os                      = pg_fetch_result($res, 0, 'sua_os');
        $admin                       = pg_fetch_result($res, 0, 'admin');
	$admin_nome_completo         = pg_fetch_result($res, 0, 'admin_nome_completo');
        $data_digitacao              = pg_fetch_result($res, 0, 'data_digitacao');
        if ($login_fabrica == 171) {
            $os_projeto              = pg_fetch_result($res, 0, 'contrato');
        }
        if ($login_fabrica != 158) {
            $data_digitacao = explode(" ", $data_digitacao);
            $data_digitacao = $data_digitacao[0];
        }
        $data_abertura               = pg_fetch_result($res, 0, 'data_abertura');
        $data_fechamento             = pg_fetch_result($res, 0, 'data_fechamento');
        if ($login_fabrica == 153) {
            $codigo_lacre            = pg_fetch_result($res, 0, 'codigo_fabricacao');
        }
        $data_finalizada             = pg_fetch_result($res, 0, 'finalizada');
        $consumidor_nome             = pg_fetch_result($res, 0, 'consumidor_nome');
        if ($login_fabrica == 85) {
            $array_campos_adicionais = pg_fetch_result($res, 0, 'array_campos_adicionais');
            if (!empty($array_campos_adicionais)) {
                $campos_adicionais = json_decode($array_campos_adicionais);
                if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                    $consumidor_nome = $campos_adicionais->nome_fantasia;
                }
            }
        }

        if(in_array($login_fabrica, [167, 203])){
            $contato_consumidor = pg_fetch_result($res, 0, 'contato_consumidor');
            $contador = pg_fetch_result($res, 0, 'contador');

            $produto_parametros_adicionais = pg_fetch_result($res, 0, 'produto_parametros_adicionais');
            $produto_parametros_adicionais = json_decode($produto_parametros_adicionais,true);
            $suprimento = $produto_parametros_adicionais['suprimento'];
        }

        if( in_array($login_fabrica, array(11,172)) ){
            $situacao_posto = pg_fetch_result($res, 0, situacao_posto);
        }
        if($login_fabrica == 151){
            $os_campos_adicionais = pg_fetch_result($res, 0, 'os_campos_adicionais');
            $os_campos_adicionais = json_decode($os_campos_adicionais, true);
            $os_bloqueada = pg_fetch_result($res, 0, 'os_bloqueada');
        }

        if($login_fabrica == 161){
            $sem_ns = $os_campos_adicionais['sem_ns'];
        }

        if ($login_fabrica == 158) {
            $data_hora_abertura = pg_fetch_result($res, 0, 'data_hora_abertura');
            $inicio_atendimento = pg_fetch_result($res, 0, "inicio_atendimento");
            $fim_atendimento    = pg_fetch_result($res, 0, "termino_atendimento");
            $amperagem          = pg_fetch_result($res, 0, "regulagem_peso_padrao");
        }else{
            $regulagem_peso_padrao   = pg_fetch_result($res, 0, "regulagem_peso_padrao");
        }

        $consumidor_endereco         = pg_fetch_result ($res,0,consumidor_endereco);
        $consumidor_numero           = pg_fetch_result ($res,0,consumidor_numero);
        $consumidor_complemento      = pg_fetch_result ($res,0,consumidor_complemento);
        $consumidor_bairro           = pg_fetch_result ($res,0,consumidor_bairro);
        $consumidor_cidade           = pg_fetch_result ($res,0,consumidor_cidade);
        $consumidor_estado           = pg_fetch_result ($res,0,consumidor_estado);
        $consumidor_cep              = pg_fetch_result ($res,0,consumidor_cep);
        $consumidor_fone             = pg_fetch_result ($res,0,consumidor_fone);
        $consumidor_celular          = pg_fetch_result ($res,0,consumidor_celular);
        $consumidor_fone_comercial   = pg_fetch_result ($res,0,consumidor_fone_comercial);
        $consumidor_cpf              = pg_fetch_result ($res,0,consumidor_cpf);
        $consumidor_email            = pg_fetch_result ($res,0,consumidor_email);
        $revenda_cnpj                = pg_fetch_result ($res,0,revenda_cnpj);
        $revenda_nome                = pg_fetch_result ($res,0,revenda_nome);
        $motivo_atraso               = pg_fetch_result ($res,0,motivo_atraso);
        $nota_fiscal                 = pg_fetch_result ($res,0,nota_fiscal);
        $data_nf                     = pg_fetch_result ($res,0,data_nf);
        $cliente                     = pg_fetch_result ($res,0,cliente);
        $revenda                     = pg_fetch_result ($res,0,revenda);
        $rg_produto                  = pg_fetch_result ($res,0,rg_produto);
        $defeito_reclamado           = pg_fetch_result ($res,0,defeito_reclamado);
        $aparencia_produto           = pg_fetch_result ($res,0,aparencia_produto);
        $acessorios                  = pg_fetch_result ($res,0,acessorios);
        $defeito_reclamado_descricao = pg_fetch_result ($res,0,defeito_reclamado_descricao);
        $marca_fricon                = pg_fetch_result ($res,0,marca);
        $produto                     = pg_fetch_result($res, 0, produto);
        $produto_referencia_fabrica  = pg_fetch_result ($res,0,referencia_fabrica);
        $produto_referencia          = pg_fetch_result ($res,0,referencia);
        $produto_modelo              = pg_fetch_result ($res,0,referencia_fabrica);
        $produto_descricao           = pg_fetch_result ($res,0,descricao);
        $produto_voltagem            = pg_fetch_result ($res,0,voltagem);
        $recomendacoes               = pg_fetch_result ($res,0,recomendacoes);
        $serie                       = pg_fetch_result ($res,0,serie);
        $type                        = pg_fetch_result ($res,0,type);
        $serie_reoperado             = pg_fetch_result ($res,0,serie_reoperado);
        if($login_fabrica==14) $numero_controle = $serie_reoperado; //HD 56740
            $embalagem_original      = pg_fetch_result($res, 0, 'embalagem_original');
        if (in_array($login_fabrica, array(156))) {
            $void = $serie_reoperado;
            $sem_ns = $embalagem_original;
        }
        $codigo_fabricacao         = pg_fetch_result($res, 0, 'codigo_fabricacao');
        $consumidor_revenda        = pg_fetch_result($res, 0, 'consumidor_revenda');
        $defeito_constatado        = pg_fetch_result($res, 0, 'defeito_constatado');
        $defeito_constatado_grupo  = pg_fetch_result($res, 0, 'defeito_constatado_grupo');
        $defeito_constatado_codigo = pg_fetch_result($res, 0, 'defeito_constatado_codigo');
        $causa_defeito_codigo      = pg_fetch_result($res, 0, 'causa_defeito_codigo');
        $causa_defeito             = pg_fetch_result($res, 0, 'causa_defeito');
        $posto_codigo              = pg_fetch_result($res, 0, 'posto_codigo');
        $posto_nome                = pg_fetch_result($res, 0, 'posto_nome');
        $posto_endereco            = pg_fetch_result($res, 0, 'posto_endereco');
        $posto_num                 = pg_fetch_result($res, 0, 'posto_num');
        $posto_complemento         = pg_fetch_result($res, 0, 'posto_complemento');
        $posto_cep                 = pg_fetch_result($res, 0, 'posto_cep');
        $posto_cidade              = pg_fetch_result($res, 0, 'posto_cidade');
        $posto_estado              = pg_fetch_result($res, 0, 'posto_estado');
        $posto_fone                = pg_fetch_result($res, 0, 'posto_fone');
        $obs_os                    = pg_fetch_result($res, 0, 'obs');
        $obs_os_log                = pg_fetch_result($res, 0, 'observacao_log_usuarios'); # HD 925803 para Fricon
        $qtde_produtos             = pg_fetch_result($res, 0, 'qtde_produtos');
        $excluida                  = pg_fetch_result($res, 0, 'excluida');
        $orientacao_sac            = pg_fetch_result($res, 0, 'orientacao_sac');
        $serie_justificativa       = pg_fetch_result($res, 0, 'serie_justificativa');
        $data_conserto             = pg_fetch_result($res, 0, 'data_conserto');
        $troca_faturada            = pg_fetch_result($res, 0, 'troca_faturada');
        $tipo_troca                = pg_fetch_result($res, 0, 'tipo_troca'); //HD 51792
        $consumidor_fone_recado    = pg_fetch_result($res, 0, 'consumidor_fone_recado');
        $os_posto                  = pg_fetch_result($res, 0, 'os_posto');
        $data_ressarcimento        = pg_fetch_result($res, 0, 'data_ressarcimento');
        $valor_troca               = pg_fetch_result($res, 0, 'valor_troca');
        $recolhimento              = (pg_fetch_result($res, 0, 'recolhimento'));
        $reoperacao_gas            = pg_fetch_result($res, 0, 'reoperacao_gas');
        $hora_tecnica              = pg_fetch_result($res, 0, 'hora_tecnica');
        $qtde_horas                = pg_fetch_result($res, 0, 'qtde_horas');
        $gerar_pedido              = pg_fetch_result($res, 0, 'gerar_pedido');
        $cortesia                  = pg_fetch_result($res, 0, 'cortesia');
        $obs_adicionais            = pg_fetch_result($res, 0, 'obs_adicionais');
        $numero_pac                = pg_fetch_result($res, 0, 'numero_pac');
        $nome_linha                = pg_fetch_result($res, 0, 'nome_linha');
        $descricao_familia         = pg_fetch_result($res, 0, 'descricao_familia');
        $key_code                = pg_fetch_result($res, 0, 'key_code');
        $qtde_diaria               = pg_fetch_result($res, 0, 'qtde_diaria');
        $os_reincidente            = trim(pg_fetch_result($res, 0, 'os_reincidente'));
        $reincidencia              = trim(pg_fetch_result($res, 0, 'reincidencia'));
        $solucao_os                = trim(pg_fetch_result($res, 0, 'solucao_os'));
        $troca_garantia            = trim(pg_fetch_result($res, 0, 'troca_garantia'));
        $troca_garantia_data       = trim(pg_fetch_result($res, 0, 'troca_garantia_data'));
        $troca_garantia_admin      = trim(pg_fetch_result($res, 0, 'troca_garantia_admin'));
        $motivo_exclusao           = trim(pg_fetch_result($res, 0, 'motivo_exclusao'));
        $certificado_garantia      = trim(pg_fetch_result($res, 0, 'certificado_garantia'));
        $autorizacao_cortesia      = trim(pg_fetch_result($res, 0, 'autorizacao_cortesia'));
        $promotor_treinamento      = trim(pg_fetch_result($res, 0, 'promotor_treinamento'));
        $fisica_juridica           = trim(pg_fetch_result($res, 0, 'fisica_juridica'));
        $tipo_atendimento          = trim(pg_fetch_result($res, 0, 'tipo_atendimento'));
        $observacao_troca          = trim(pg_fetch_result($res, 0, 'observacao_troca'));
        $data_fabricacao          = trim(pg_fetch_result($res, 0, 'data_fabricacao'));
        $os_campos_adicionais       = json_decode(pg_fetch_result($res, 0, "os_campos_adicionais"), true);
	$status_checkpoint          = trim(pg_fetch_result($res, 0, 'status_checkpoint'));
        $os_valores_adicionais    = json_decode(pg_fetch_result($res, 0, "os_valores_adicionais"), true);

        if($login_fabrica == 20){
            $motivo_ordem = $os_campos_adicionais["motivo_ordem"];
        }

        if ($login_fabrica == 162) {
            $data_saida = $os_campos_adicionais["data_saida"];
            $rastreio   = $os_campos_adicionais["rastreio"];
        }

        if(!empty($data_fabricacao)){
            $data_fabricacao = DateTime::createFromFormat('Y-m-d',$data_fabricacao);
            $data_fabricacao = $data_fabricacao->format('d/m/Y');
        }else{
            $data_fabricacao = '';
        }
        if ($login_fabrica == 152) {
            $tempo_deslocamento = pg_fetch_result($res, 0, "qtde_hora");
        }

        if( in_array($login_fabrica, array(11,172)) ){
            $consumidor_fone_comercial = $consumidor_fone_recado;
        }

        if ($login_fabrica == 148) {
            $os_horimetro = pg_fetch_result($res, 0, "qtde_hora");
            $os_revisao = pg_fetch_result($res, 0, "os_hora_tecnica");

            $obs_adicionais_json = json_decode($obs_adicionais);

            $serie_motor       = $obs_adicionais_json->serie_motor;
            $serie_transmissao = $obs_adicionais_json->serie_transmissao;
        }

        if ($login_fabrica == 141 || $login_fabrica == 165) {
            $cod_rastreio = pg_fetch_result($res, 0, "pac");
        }

        if ($login_fabrica == 145) {
            $os_construtora = (pg_fetch_result($res, 0, "nf_os") == "t")  ? "Sim" : "Não";
        }

        if ($login_fabrica == 122) {
            extract(json_decode($obs_adicionais, true));
        }

        if($login_fabrica == 96){
            $motivo             = pg_fetch_result($res,0,obs_nf);
        }

        if ($login_fabrica == 156) {
            $natureza_operacao = pg_fetch_result($res, 0, "natureza_servico");
        }

        if ($login_fabrica == 1 && $tipo_atendimento == 18) {
            $sql = "SELECT total_troca FROM tbl_os_troca WHERE os = $os";

            $res_valor_troca = pg_query($con,$sql);

            if(pg_num_rows($res_valor_troca) > 0){
                $valor_troca = pg_fetch_result($res_valor_troca,0,'total_troca');
            }
        }

        if (in_array($login_fabrica, array(169,170))) {
            $produto_emprestimo = pg_fetch_result($res, 0, contrato);
            $revenda_contato = pg_fetch_result($res, 0, contato_consumidor);
            $justificativa_adicionais = pg_fetch_result($res, 0, justificativa_adicionais);
            $justificativa_adicionais = json_decode($justificativa_adicionais, true);

            if (isset($justificativa_adicionais["motivo_visita"])) {
                $motivo_visita = utf8_decode($justificativa_adicionais["motivo_visita"]);
            }
        }

        if($fisica_juridica=="F"){
            $fisica_juridica = "Pessoa Física";
        }
        if($fisica_juridica=="J"){
            $fisica_juridica = "Pessoa Jurídica";
        }

        $tecnico       = trim(pg_fetch_result($res,0,tecnico));
        $tecnico_nome       = trim(pg_fetch_result($res,0,'tecnico_nome'));
        $codigo_atendimento = trim(pg_fetch_result($res,0,'codigo_atendimento'));
        $sua_os_offline     = trim(pg_fetch_result($res,0,'sua_os_offline'));
        $marca_nome         = trim(pg_fetch_result($res,0,'marca_nome'));
        $marca              = trim(pg_fetch_result($res,0,'marca'));
        $ressarcimento      = trim(pg_fetch_result($res,0,'ressarcimento'));
        $admin_autoriza     = trim(pg_fetch_result($res,0,'admin_autoriza'));
        $troca_admin        = trim(pg_fetch_result($res,0,'troca_admin'));
        $codigo_posto       = trim(pg_fetch_result($res,0,'posto'));
        $reembolso_peca_estoque  = pg_fetch_result($res, 0, reembolso_peca_estoque);
        $obs_reincidencia   = trim(pg_fetch_result($res,0,'obs_reincidencia'));
        $troca_admin        = (strlen($troca_admin) == 0) ? $admin : $troca_admin;
        if ($login_fabrica == 52) {
            $motivo_reincidencia_desc   = pg_fetch_result($res,0,'motivo_reincidencia_desc');
        }
        $tipo_os           =  trim(pg_fetch_result($res,0,'tipo_os'));
        $tipo_os_descricao =  trim(pg_fetch_result($res,0,'tipo_os_descricao'));
        $nota_fiscal_saida =  trim(pg_fetch_result($res,0,'nota_fiscal_saida'));
        $data_nf_saida     =  trim(pg_fetch_result($res,0,'data_nf_saida'));

        $nome_atendimento  =  trim(pg_fetch_result($res,0,'nome_atendimento'));

        //--==== Dados Extrato HD 61132 ====================================
        $extrato           =  trim(pg_fetch_result($res,0,'extrato'));
        $data_previsao     =  trim(pg_fetch_result($res,0,'data_previsao'));
        $data_pagamento    =  trim(pg_fetch_result($res,0,'data_pagamento'));
        $liberado          =  trim(pg_fetch_result($res,0,'liberado'));
        $protocolo         =  trim(pg_fetch_result($res,0,'protocolo'));
        $extrato_link      =  ($login_fabrica == 1) ? $protocolo : $extrato;

        // HD 64152
        $fabricacao_produto = trim(pg_fetch_result($res,0,'fabricacao_produto'));
        $qtde_km            = trim(pg_fetch_result($res,0,'qtde_km'));
        $os_numero          = trim(pg_fetch_result($res,0,'os_numero'));
        $quem_abriu_chamado = trim(pg_fetch_result($res,0,'quem_abriu_chamado'));
        if(strlen($qtde_km) == 0) $qtde_km = 0;

        if(strlen($promotor_treinamento)>0){
            $sql = "SELECT nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento";
            $res_pt = pg_query($con,$sql);
            if (@pg_num_rows($res_pt) >0) {
                $promotor_treinamento  = trim(@pg_fetch_result($res_pt,0,'nome'));
            }
        }

        if($login_fabrica == 15){
            $preco_produto             = pg_fetch_result($res,0,'valores_adicionais');
        }

        # HD 13940 - Ultimo Status para as Aprovações de OS
        $sql = "SELECT status_os, observacao
                FROM tbl_os_status
                WHERE os = $os
                AND status_os IN (92,93,94)
                ORDER BY data DESC
                LIMIT 1";
        $res_status = @pg_query($con,$sql);
        if (@pg_num_rows($res_status) >0) {
            $status_recusa_status_os  = trim(pg_fetch_result($res_status,0,'status_os'));
            $status_recusa_observacao = trim(pg_fetch_result($res_status,0,'observacao'));
            if($status_recusa_status_os == 94){
                $os_recusada = 't';
            }
        }

        if($login_fabrica == 145){
            $complemento_where = "LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade";
            $complemento_campos = "tbl_cidade.nome as nome_cidade_revenda,
                            tbl_cidade.estado as sigla_estado_revenda,";
        }

        if (strlen($revenda) > 0) {
            $sql = "SELECT  tbl_revenda.endereco   ,
                            tbl_revenda.numero     ,
                            tbl_revenda.complemento,
                            tbl_revenda.bairro     ,
                            tbl_revenda.cep        ,
                            $complemento_campos
                            tbl_revenda.fone       ,
                            tbl_revenda.email
                    FROM    tbl_revenda
                    $complemento_where
                    WHERE   tbl_revenda.revenda = $revenda;";
            $res1 = pg_query ($con,$sql);

            if (pg_num_rows($res1) > 0) {
                $revenda_endereco    = strtoupper(trim(pg_fetch_result ($res1,0,endereco)));
                $revenda_numero      = trim(pg_fetch_result ($res1,0,numero));
                $revenda_complemento = strtoupper(trim(pg_fetch_result ($res1,0,complemento)));
                $revenda_bairro      = strtoupper(trim(pg_fetch_result ($res1,0,bairro)));
                $revenda_email       = trim(pg_fetch_result ($res1,0,email));
                $revenda_fone        = strtoupper(trim(pg_fetch_result ($res1,0,fone)));
                $revenda_cep         = trim(pg_fetch_result ($res1,0,cep));
                $revenda_email       = trim(pg_fetch_result ($res1,0,email));
                $revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);

                $sigla_estado_revenda         = trim(pg_fetch_result ($res1,0,sigla_estado_revenda));
                $nome_cidade_revenda         = trim(pg_fetch_result ($res1,0,nome_cidade_revenda));

            }
        }
        if (strlen($revenda_cnpj) == 14){
            $revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
        }elseif(strlen($consumidor_cpf) == 11){
            $revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
        }

            if($aparencia_produto=='NEW')$aparencia_produto= $aparencia_produto.' - Bom Estado';
            if($aparencia_produto=='USL')$aparencia_produto= $aparencia_produto.' - Uso intenso';
            if($aparencia_produto=='USN')$aparencia_produto= $aparencia_produto.' - Uso Normal';
            if($aparencia_produto=='USH')$aparencia_produto= $aparencia_produto.' - Uso Pesado';
            if($aparencia_produto=='ABU')$aparencia_produto= $aparencia_produto.' - Uso Abusivo';
            if($aparencia_produto=='ORI')$aparencia_produto= $aparencia_produto.' - Original, sem uso';
            if($aparencia_produto=='PCK')$aparencia_produto= $aparencia_produto.' - Embalagem';

    }
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = "CONFIRMAÇÃO DE ORDEM DE SERVIÇO";
$layout_menu = 'callcenter';
include "cabecalho.php";
?>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<script src="plugins/shadowbox/shadowbox.js"    type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="../js/anexaNF_excluiAnexo.js"></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
<script type='text/javascript' src='js/jquery.zoombie.js'></script>

<?php if ($login_fabrica == 74) { ?>
<script type="text/javascript">
    $(document).ready(function() {
        $("input[name=data_contato]").mask("99/99/9999");

    });

</script>
<?}?>

<script type="text/javascript">

	SetIFrameHeight = function(height) {
	    $("#pesquisa_satisfacao_iframe").height(height);
	}

$(function () {

    $('.visualizar_check').click(function(){
        $('#checklist_preenchido').show();
        $('.esconder_check').show();
        $('.visualizar_check').hide();
    });

    $('.esconder_check').click(function(){
        $('#checklist_preenchido').hide();
        $('.esconder_check').hide();
        $('.visualizar_check').show();
    });

    // <input type='button' class='visualizar_check' value='Visualizar Checklist'></br>
    // <input type='button' style='display:none;' class='esconder_check' value='Esconder Checklist'></br>

    setupZoom();
    Shadowbox.init();

    $('#pesquisa_satisfacao').click( function(e){
        Shadowbox.open({
            content:    "pesquisa_satisfacao_new.php?local=posto&os=<?=$os?>",
            player: "iframe",
            title:      "Pesquisa de Satisfação",
            width:  800,
            height: 500
        });

    });

    $("#auditorias").click(function(e){
        Shadowbox.open({
            content:"auditoria_os_press.php?os=<?=$os?>",
            player:"iframe",
            title:"Auditorias da OS",
            width:  800,
            height: 500
        });
    });
<?php
if ($login_fabrica == 30) {
?>
    $("#histHelp").click(function(){
        Shadowbox.open({
            content :   "helpdesk_posto_autorizado_historico.php?os=<?=$os?>",
            player  :   "iframe",
            title   :   "Histórico de Help-Desk",
            width   :   800,
            height  :   500
        });
    });

    $("#cadastra_laudo").click(function(e){
        e.preventDefault;

        var tipo_laudo = $("input[name=laudo]:checked").val();

        if (typeof tipo_laudo === 'undefined') {
            alert("Selecione um tipo de laudo para prosseguir o cadastro");
        } else {
            window.open("cadastro_laudo_troca.php?os=<?=$os?>&laudo="+tipo_laudo,"_self");
        }
    });
<?php
}
?>
	$("#disparar_pesquisa").click(function(){

		var os = $(this).attr("rel");

		$.ajax({
			url: "os_press.php",
			type: "POST",
			data: {
				disparar_pesquisa: "true",
				os: os
			},
			complete: function(data){
				if(data.responseText == "ok"){
					alert("Email enviado com sucesso");
				}else{
					alert(data.responseText);
				}
			}
		});

	});

});

    <?php if(in_array($login_fabrica, [167, 203])){
     ?>
        function enviar_orcamento(){
            var dados_email = $("#dados_email").val();

            $.ajax({
                url:"<?=$PHP_SELF?>",
                type: "POST",
                data: {
                    enviar_orcamento: "true",
                    dados_email: dados_email
                },
                complete: function(data){
                    if(data.responseText == "ok"){
                        alert("Email enviado com sucesso");
                    }else{
                        alert(data.responseText);
                    }
                }
            });
        }

    <?php } ?>
    function deletarImagemOS(el, fileName, idx){
        var elemento = $(el);
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            data:{
                deletar_imagem_os: "true",
                file:fileName
            },
            complete:function(data){
                var resp = $.parseJSON(data.responseText);

                if(resp.apagado == "true"){

                    var table = elemento.parents("table");
                    var length = table.find("td").length
                    if(length > 1){
                        elemento.parent().remove();
                    }else{
                        table.remove();
                    }

                    if (idx) {
                        $('#' + idx).remove();
                    }
                }

            }
        });
    }

    function verAnexo(tipo, idx) {
        var div = tipo + '_' + idx;
        $('.anexo').css({ display: "none" });
        $('#' + div).css({ display: "block" });
        $('#' + div).zoombie({ on: 'click' });
    }

    <?php if ($login_fabrica == '1'): ?>
    $().ready(function() {
        var program_self = window.location.pathname;
        var blocoNF;

        $('[id^=anexo]').on('click', 'img.excluir_NF', function() {

            var blocoNF = $(this).parents('div')[0];

            var nota = $(this).data('name');
            var notaId = $(this).data('id');

            nota = nota.replace(/^http:\/\/[a-z0-9.-]+\//, '')
                if (nota.indexOf('?')>-1) nota = nota.substr(0, nota.indexOf('?'));

            var excluir_str = 'Confirma a exclusão do arquivo "' + nota + '" desta OS?';
            if (confirm(excluir_str) ==    false) return false;

            $.post(program_self, {
                'excluir_nf': notaId,
                'ajax':       'excluir_nf'
            },
            function(data) {
                var r = data.split('|');
                if (r[0] ==    'ok') {
                    alert('Imagem excluída com êxito');
                    if (r[1].indexOf('<tr')>0) blocoNF.html(r[1]); // Só se vier uma outra tabela!
                    if (r[1] == '')            blocoNF.remove();
                } else {
                    alert('Erro ao excluir o arquivo. '+r[1]);
                }
            });

        });

    });
    <?php endif; ?>
<?  if ($login_fabrica == 30) { ?>

function cancelarOs(os,acao) {
    var motivo;
    if(acao == "cancelar"){
        motivo = prompt("Digite o motivo do cancelamento da OS");
    }else{
        motivo = prompt("Digite o motivo da reabertura da OS");
    }
    if(motivo != null){
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"cancelar_os",
                os:os,
                acao:acao,
                motivo:motivo
            },
            beforeSend:function(){
                $("#cancelar_os").text("Aguardando...").attr("disabled","true");
            }
        })
        .done(function(data){
            if(data.result == "ok"){
                location.reload();
            }
        })
        .fail(function(){
            alert("Não foi possível realizar a operação.");
            if(acao == "liberar"){
                $("#cancelar_os").text("Reabrir OS").prop("disabled","");
            }else{
                $("#cancelar_os").text("Cancelar OS").prop("disabled","");
            }
        });
    }
}

<? } ?>
</script>

<style type="text/css">

body {
    margin: 0px;
}

#DIVanexos table{
    width: 700px;
    text-align: center;
    margin: 0 auto;
    margin-top: 20px;
}

table.anexos {margin: 1ex auto}
table.anexos thead {text-transform:capitalize;text-align:center}
table.anexos tr td {vertical-align:middle;text-align:center}
table.anexos tr td a>img {max-height: 150px;min-height: 96px;}
.excluir_NF {cursor: pointer}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    padding-right: 5px;
    padding-left: 5px;
    color: #000000;
    background: #ced7e7;
}
.titulo2 {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
}
.titulo3 {
    font-family: Arial;
    font-size: 7pt;
    text-align: left;
    color: #000000;
    background: #ced7e7;
}
.inicio {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
}

.conteudo {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    padding-left: 5px;
    padding-right: 5px;
    background: #F4F7FB;
}

.conteudo2 {
    font-family: Arial;
    font-size: 8pt;
    font-weight: bold;
    text-align: left;
    background: #FFDCDC;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }
table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
}
table.tabela2 tr td{
    font-family: verdana;
    font-size: 11px;
    border:1px solid #ACACAC;
    border-collapse: collapse;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
.inpu{
    border:1px solid #666;
}
.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

span.excluirNF {
    color:red;
    font-weight: bold;
    cursor: pointer;
    _cursor: hand;
}
table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {border:1px solid #000000;}

.anexo { display: none }

#gmaps {
    width:606px;
    height: 5px;
    /*display:none;*/
    margin:1ex auto;
    background-color:#CED7E7;
    border:6px solid #CED7E7;
    border-top-width:24px;
    border-radius:12px;
    -moz-border-radius:12px; /* FF < 4.00 */
    cursor:help;
    overflow: hidden;
    z-index:100;
    /*transition: height 0.5s ease-in;
    -o-transition: height 0.5s ease-in;
    -ms-transition: height 0.5s ease-in;
    -moz-transition: height 0.5s ease-in;
    -webkit-transition: height 0.5s ease-in;*/
}

.previsao_entrega{
    display: inline;
    position: relative;
    /*font-size: 13px;
    vertical-align: middle;*/
}

.previsao_entrega:hover:after{
    padding: 5px 15px;
    width: 220px;
    border-radius: 5px;
    background: #333;
    background: rgba(0,0,0,.8);
    content: attr(data-title);
    position: absolute;
    right: 20%;
    bottom: 26px;
    z-index: 98;
    color: #fff;
}

.previsao_entrega:hover:before{
    border: solid;
    border-color: #333 transparent;
    border-width: 6px 6px 0px 6px;
    content: "";
    position: absolute;
    /*left: 50%;*/
    right: 20%;
    bottom: 20px;
    z-index: 99;
}

/* //HD 664673 - @media ADICIONADA PARA RETIRAR ESTA LINHA NA IMPRESSÃO DA TELA */

@media print {
    .mapa_gmaps {
        display:none;
    }
}
</style>

<!--[if lt IE 8]>
<style>
table.tabela2{
    empty-cells:show;
    border-collapse:collapse;
    border-spacing: 2px;
}
</style>
<![endif]-->

<script>
    function showHideGMap() {
        var gMapDiv = $('#gmaps');
        var newh    = (gMapDiv.css('height')=='5px') ? '486px' : '5px';
        gMapDiv.animate({height: newh}, 400);
        if (newh=='5px') gMapDiv.parent('td').css('height', '2em');
        if (newh!='5px') gMapDiv.parent('td').css('height', 'auto');
    }

    function excluirComentario(os,os_status){

        if (confirm('Deseja alterar este comentário?')){
            var justificativa = prompt('Informe a nova justificativo. É Opcional.', '');
            if (justificativa==null){
                return;
            }else{
                window.location = "<?=$PHP_SELF?>?os="+os+"&apagarJustificativa="+os_status+"&justificativa="+justificativa;
            }
        }
    }

    <?php
    if (isset($novaTelaOs) OR $login_fabrica == 52) {
    ?>
        $(function() {
            $("button[name=excluir_anexo]").click(function() {
                if (confirm("Deseja realmente excluir o anexo?")) {
                    var span  = $(this).parent("span");

                    var anexo = $(span).find("input[name=anexo]").val();
                    var ano   = $(span).find("input[name=ano]").val();
                    var mes   = $(span).find("input[name=mes]").val();

                    $.ajax({
                        url: "os_press.php",
                        type: "post",
                        data: { excluir_anexo: true, anexo: anexo, ano: ano, mes: mes },
                        complete: function(data) {
                            data = $.parseJSON(data.responseText);

                            if (data.error) {
                                alert(data.error);
                            } else {
                                $(span).remove();
                            }
                        }
                    });
                }
            });

            $("button[name=excluir_anexo_peca]").click(function() {
                if (confirm("Deseja realmente excluir o anexo da peça?")) {
                    var span  = $(this).parent("span");
                    var anexo = $(span).find("input[name=anexo]").val();

                    $.ajax({
                        url: "os_press.php",
                        type: "post",
                        data: {excluir_anexo_peca: true, anexo: anexo},
                        complete: function(data) {
                            data = $.parseJSON(data.responseText);

                            if (data.error) {
                                alert(data.error);
                            } else {
                                $(span).remove();
                            }
                        }
                    });
                }
            });
        });
    <?php
    }
    ?>
</script>
<?

//Verifica se OS existe -- HD318754
$sql = "SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
$res = pg_query($con,$sql);

if (pg_num_rows($res) == 0) {

    $sql_exc = "SELECT * FROM tbl_os_excluida WHERE os = $os";
    $res_exc = pg_exec($con, $sql_exc);

    if (pg_numrows($res_exc) > 0) {

        if ($login_fabrica == 24) {
            $link = "os_consulta_excluida.php?sua_os=$os&chk_opt13=1";
        } else {
            if (isset($novaTelaOs)) {
                $link = "relatorio_os_excluida.php?os=$os";
            } else {
                $link = "os_consulta_excluida.php?os=$os";
            }
        }

        echo "Existe um registro de exclusão para esta OS, <a href='$link'>clique aqui para visualizar</a>";

    } else {
        echo '<center>OS não Encontrada</center>';
    }

    include 'rodape.php';
    exit;

}

if ($auditoria == 't') {?>
<style type="text/css">
div.banner {
  margin:       0;
  padding:      0;
  font-size:    10px;
  position:     fixed;
  _position:    absolute;
  *position:    absolute;
  top:          0em;
  left:         auto;
  width:        100%;
  right:        0em;
  background:   #F7F5F0;
  border-bottom:1px solid #FF9900;
  cursor:       pointer;
}
</style>
<!--[if IE]>
<script>
function janela(a , b , c , d) {
    var arquivo = a;
    var janela = b;
    var largura = c;
    var altura = d;
    posx = (screen.width/2)-(largura/2);
    posy = (screen.height/2)-(altura/2);
    features="width=" + largura + " height=" + altura + " status=yes scrollbars=yes";
    newin = window.open(arquivo,janela,features);
    newin.focus();
}


window.onscroll = function(){
    var p = document.getElementById("janela") || document.all["janela"];
    var y1 = y2 = y3 = 0, x1 = x2 = x3 = 0;

    if (document.documentElement) y1 = document.documentElement.scrollTop || 0;
    if (document.body) y2 = document.body.scrollTop || 0;
    y3 = window.scrollY || 0;
    var y = Math.max(y1, Math.max(y2, y3));

    if (document.documentElement) x1 = document.documentElement.scrollLeft || 0;
    if (document.body) x2 = document.body.scrollLeft || 0;
    x3 = window.scrollX || 0;
    var x = Math.max(x1, Math.max(x2, x3));

    p.style.top = (parseInt(p.initTop) + y) + "px";
    p.style.left = (parseInt(p.initLeft) + x) + "px";
    p.style.marginLeft = (0) + "px";
    p.style.marginTop = (0) + "px";
}

window.onload = function(){
    var p = document.getElementById("janela") || document.all["janela"];
    p.initTop = p.offsetTop; p.initLeft = p.offsetLeft;
    window.onscroll();
}
</script>
<![endif]-->
<script type="text/javascript">
$().ready(function() {
    $('#janela').click(function () {
        $(this).removeClass('banner')
               .removeAttr('title');
    });
});
</script>
<?
}
echo "<div class='banner' id='janela'"; //HD 332254
echo ($auditoria=='t') ? " title='Clique para desbloquear o pop-up'>" : '>';
?>
<p>

<?

if(in_array($login_fabrica,array(42,74))){

    $sql_os_cancelada = "SELECT cancelada FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
    $res_os_cancelada = pg_query($con, $sql_os_cancelada);

    $os_cancelada = pg_fetch_result($res_os_cancelada, 0, "cancelada");
    if($os_cancelada == "t"){
        echo "<div style='width: 700px; margin: 0 auto; border-radius: 4px; padding: 10px 0px; text-transform: uppercase; font-weight: bold; font-size: 16px; margin-bottom: 10px; margin-top: 0px; background-color: #ffcccc; color: #ff4d4d;'>";
            echo "Esta Os está cancelada";
        echo "</div>";
    }

}

//HD 307124 - inicio - OS CANCELADA
if (in_array($login_fabrica, array(151))) {
    $sql_serie_controle = "SELECT motivo FROM tbl_serie_controle WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND lower(serie) = lower('{$serie}')";
    $res_serie_controle = pg_query($con, $sql_serie_controle);
    if (pg_num_rows($res_serie_controle)) {
        echo "<table class='msg_erro' align='center' width='700'>
                <tr>
                    <td align='center'>
                        Número de Série bloqueado, motivo: ".pg_fetch_result($res_serie_controle, 0, 'motivo')."
                    </td>
                </tr>
            </table>";
    }
}
if ($login_fabrica == 81){

    $sql = "SELECT cancelada from tbl_os where fabrica=$login_fabrica and os=$os";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res)>0){

        $cancelada = pg_result($res,0,0);

        if ($cancelada == 't')
        {
        ?>
            <table class='msg_erro' align='center' width='700'>
                <tr>
                    <td align='center'>
                        OS CANCELADA
                    </td>
                </tr>
            </table>
        <?
        }

    }

}

//HD 307124 - fim - OS CANCELADA

if (strlen($status_os_aberta) > 0 AND $login_fabrica == 3) {
    $status_os_aberta_inter = "";
    if ($status_os_aberta == 122 || $status_os_aberta == 141) {//HD 268613
        $status_os_aberta_inter = "<br><b style='font-size:11px'>OS com intervenção da fábrica. Aguardando liberacão </b>";
        echo "<br />
            <center>
            <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                <b style='font-size:14px;color:red;width:100%'>Status OS </b>
                 $status_os_aberta_inter <br />
                <b style='font-size:11px'>$status_os_aberta_obs </b>
            </div>
            </center><br />";
    }
}


if (strlen($os_reincidente) > 0 OR $reincidencia == 't') {

    //verifica se OS faz parte de extrato. HD7622
    $sql = "SELECT tbl_extrato.extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os AND tbl_extrato.aprovado IS NOT NULL ; ";
    $res2 = pg_query($con,$sql);
    $reic_extrato = @pg_fetch_result($res2,0,0);

//  16/11/2009 HD 171349 - Waldir
//  if(strlen($reic_extrato) == 0){
//      echo "passou para verificar a reincidencia.";
//      $sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
//      $res1 = pg_query ($con,$sql);
//  }
    $sql = "SELECT  tbl_os_status.status_os,tbl_os_status.observacao
        FROM tbl_os_extra JOIN tbl_os_status USING(os)
        WHERE tbl_os_extra.os = $os
        AND tbl_os_status.status_os IN (67,68,70,95,132,239)";
    //HD: 53642
    if ($login_fabrica == 3 and $os > 8082706) $sql .= " ORDER BY tbl_os_status.status_os ";
    if ($login_fabrica == 1) $sql .= " ORDER BY tbl_os_status.data desc limit 1 ";

    $res1 = pg_query ($con,$sql);

    if (pg_num_rows ($res1) > 0) {
        $status_os  = trim(pg_fetch_result($res1,0,'status_os'));
        $observacao = trim(pg_fetch_result($res1,0,'observacao'));
    }

    $sql  = "SELECT os_reincidente FROM tbl_os_extra WHERE os = $os";
    $resr = pg_query($con,$sql);
    if (pg_num_rows($resr) > 0) {
        $xos_reincidente = trim(pg_fetch_result($resr,0,os_reincidente));
    }

    echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
    echo "<tr>";
    echo "<td style='font-weight:bold;font-size:10px;text-align:center;'>ATENÇÃO</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td align='center'><font size='1'>";

    if(isset($novaTelaOs)) {
        $sql = "SELECT osr.os, osr.sua_os FROM tbl_os_extra INNER JOIN tbl_os AS osr ON osr.os = tbl_os_extra.os_reincidente WHERE tbl_os_extra.os = {$os}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $osr        = pg_fetch_result($res, 0, "os");
            $osr_sua_os = pg_fetch_result($res, 0, "sua_os");

            echo "ORDEM DE SERVIÇO REINCIDENTE DA ORDEM DE SERVIÇO: <a href='os_press.php?os={$osr}' target='_blank'>{$osr_sua_os}</a>";
        }
    }

    if (strlen($xos_reincidente) > 0 && !isset($novaTelaOs)) {

        $sql = "SELECT  tbl_os.sua_os,
                tbl_os.serie,
		tbl_posto_fabrica.codigo_posto
                FROM    tbl_os
		JOIN tbl_posto_fabrica using(posto, fabrica)
                WHERE   tbl_os.os = $xos_reincidente;";
        $res1 = pg_query ($con,$sql);
        if (pg_num_rows ($res1) > 0) {
            $sos     = trim(pg_fetch_result($res1,0,'sua_os'));
            $serie_r = trim(pg_fetch_result($res1,0,'serie'));
            $codigo_posto_r = trim(pg_fetch_result($res1,0,'codigo_posto'));
        	if ($login_fabrica == 1) $sos = $codigo_posto_r.$sos;
        }


    } else if (!isset($novaTelaOs) && $login_fabrica != 30 and strlen($serie) > 3) {

        //CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
        $sql = "SELECT os,sua_os
            FROM tbl_os
            JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
            WHERE   serie   = '$serie'
            AND     os     <> $os
            AND     fabrica = $login_fabrica
            AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
            //echo $sql;

        $res2 = pg_query ($con,$sql);

        echo "ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: <u>$serie_r</u> REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR:<br>";

        if (pg_num_rows($res2) > 0) {
            for ($i = 0; $i < pg_num_rows($res2); $i++) {
                $sos_reinc = trim(pg_fetch_result($res2,$i,'sua_os'));
                $os_reinc  = trim(pg_fetch_result($res2,$i,'os'));
                echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
                $mostrou = 1;
            }
        }

    }
    if ($status_os == 67 and $mostrou <> 1 && !isset($novaTelaOs) && $login_fabrica != 30) {

        echo "ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: <u>$serie</u> REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR:<br>";

        if ( in_array($login_fabrica, array(11,172)) ) {
            $sql = "SELECT os_reincidente
                    FROM tbl_os_extra
                    WHERE os= $os";
            $res2 = pg_query($con,$sql);

            $osrein = pg_fetch_result($res2,0,os_reincidente);

            if (strlen($osrein) > 0) {
                $sql = "SELECT os,sua_os
                        FROM tbl_os
                        WHERE   serie   = '$serie_r'
                        AND     os      = $osrein
                        AND     fabrica = $login_fabrica";
                $res2 = pg_query($con,$sql);
                if (pg_num_rows($res2) > 0) {
                    $sua_osrein = pg_fetch_result($res2,0,sua_os);
                    echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
                }
            }

        } else {
            if ($login_fabrica == 74  or $login_fabrica == 96 or $login_fabrica == 94 or $login_fabrica == 52) { // HD 708057

                $sql = "SELECT os_reincidente
                        FROM tbl_os_extra
                        WHERE os = $os";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res)) {


                    $os_reinc = pg_result($res,0,0);

                    $sql = "SELECT os,sua_os
                            FROM tbl_os
                            WHERE   serie   = '$serie'
                            AND     os      = $os_reinc
                            AND     fabrica = $login_fabrica";

                    $res2 = pg_query($con,$sql);
                    if (pg_num_rows($res2) > 0) {

                        $sua_osrein = pg_fetch_result($res2,0,sua_os);
                        if ($login_fabrica == 96){
                            echo "<a href='os_press.php?os=$os_reinc' target='_blank'>» $os_reinc</a>";

                        }else{
                            echo "<a href='os_press.php?os=$os_reinc' target='_blank'>» $sua_osrein</a>";

                        }
                    }

                }

            }
            else {
                if ($login_fabrica != 122) {
                    $reincidencia_ns_obrigatorio = " AND tbl_produto.numero_serie_obrigatorio IS TRUE  ";
                }

                $sql = "SELECT os,sua_os
                        FROM tbl_os
                        JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
                        WHERE   serie   = '$serie'
                        AND     os     <> $os
                        AND     fabrica = $login_fabrica
                        $reincidencia_ns_obrigatorio
                        ORDER BY tbl_os.os DESC LIMIT 5";


                $res2 = pg_query ($con,$sql);

                if (pg_num_rows($res2) > 0) {
                    for ($i = 0; $i < pg_num_rows($res2); $i++) {
                        $sos_reinc = trim(pg_fetch_result($res2,$i,'sua_os'));
                        $os_reinc  = trim(pg_fetch_result($res2,$i,'os'));
                        echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";

                    }
                }
            }

            if ($login_fabrica == 3 and $os > 8082706) echo "<br />$observacao";

        }

    } else if ($status_os == 68) {
        echo "ORDEM DE SERVIÇO COM MESMA REVENDA E NOTA FISCAL REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
    } else if ($status_os == 70) {

            echo "ORDEM DE SERVIÇO COM MESMA REVENDA, NOTA FISCAL E PRODUTO REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";

            if($login_fabrica == 3 and $os > 8082706) echo "<br>$observacao";
    } else if ($status_os == 95) {
        echo "ORDEM DE SERVIÇO COM MESMA NOTA FISCAL E PRODUTO REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
    } else if ($status_os == 132) {
        echo "ORDEM DE SERVIÇO COM MESMA NOTA FISCAL E MESMA DATA DA NF. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
    } else if ($status_os == 239) {
        echo "ORDEM DE SERVIÇO REINCIDENTE COM OUTRO POSTO AUTORIZADO. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";

    } else if (!isset($novaTelaOs)) {
        if ($mostrou <> 1) echo "OS Reincidente:<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";
    }

    echo "";
    echo "</font></td>";
    echo "</tr>";
    echo "</table>";
}

// MLG 14-12-2010 - HD 326633 - Início 2
    if ($bloqueioint=='intervencao') {
        echo "<div style=' border: #D3BE96 1px solid;width:696px;margin:1ex auto;background-color:#FCF0D8'><br>\n<p style='color:red;background-color:yellow;margin:0.5ex 1em;font-size:11px;text-align:center;font-weight:bold'>OS em intervenção técnica, <u>não é permitido alterar as peças</u></p><br>\n</div>\n";
    }
// MLG 14-12-2010 - HD 326633 - Fim 2

if ($consumidor_revenda == 'R')
    $consumidor_revenda = 'REVENDA';
else
    if ($consumidor_revenda == 'C')
        $consumidor_revenda = 'CONSUMIDOR';

 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################

if (strlen($tipo_atendimento) > 0 and (in_array($login_fabrica,array(1,15,91,94,96,129)))) {?>
    <TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' align="center"><?php
        if ($tipo_os == 13 OR in_array($login_fabrica,array(15,91,94,96,129))) {
            echo "<TR>";
            echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
            echo "Tipo Atendimento:";
            echo "</TD>";
            if(in_array($login_fabrica,array(91,94,96,129)))
                echo "<TD class='conteudo' height='20' colspan='3'>&nbsp;&nbsp;$nome_atendimento</TD>";
            else if ($login_fabrica == 15){

                echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$nome_atendimento</TD>";

                if ($tipo_atendimento == 21){

                    echo "<TD class='inicio' height='20' width='75px'>&nbsp;&nbsp;";
                    echo "Qtde. KM:";
                    echo "</TD>";
                    echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$qtde_km</TD>";

                }

            } else {

                echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$nome_atendimento</TD>";
                echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
                echo "Solicitante:";
                echo "</TD>";
                echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$quem_abriu_chamado</TD>";
            }
            echo "</TR>";
        } else {
            echo "<TR>";
                echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
                    echo "Troca de Produto:";
                echo "</TD>";
                echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$nome_atendimento</TD>";
            echo "</TR>";

            if($login_fabrica == 1){
                $sql_os_interna = "SELECT tbl_os.os_posto
                                    FROM tbl_os
                                    WHERE tbl_os.os = $os
                                    AND tbl_os.fabrica = $login_fabrica";
                $res_os_interna = pg_query($con, $sql_os_interna);

                if(pg_num_rows($res_os_interna) > 0){
                    $os_interna_posto = pg_fetch_result($res_os_interna, 0, 'os_posto');
                    echo "<TR>";
                        echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
                            echo "OS interna posto:";
                        echo "</TD>";
                        echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$os_interna_posto</TD>";
                    echo "</TR>";
                }
            }

            if ($login_fabrica == 1 AND strlen($observacao_troca) > 0) { #HD 274932 e 303195
                echo "<TR>";
                    echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;OBS.:</TD>";
                    echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$observacao_troca</TD>";
                echo "</TR>";
            }
        }?>
    </TABLE><?php
}

if ($login_fabrica == 94 ) {

    $sql = "SELECT tbl_os.sua_os
                FROM tbl_os_campo_extra
                JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os_troca_origem AND tbl_os.fabrica = tbl_os_campo_extra.fabrica
                WHERE tbl_os_campo_extra.os = $os
                AND tbl_os_campo_extra.fabrica = $login_fabrica";

    $res = pg_query($con,$sql);

    if ( pg_num_rows($res) ) {

        echo '<table style="border: #D3BE96 1px solid; background-color: #FCF0D8" align="center" width="700">
                    <tr>
                        <td align="center"><font size="1">OS de Origem: &nbsp;'.pg_result($res,0,0).'</font></td>
                    </tr>
                </table>';

    }

}

if ($login_fabrica == 1 AND strlen($os) > 0) { // HD 17284

    $sql2 = "SELECT  TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data,
                    descricao,
                    tbl_os_status.observacao,
                    tbl_admin.admin_sap,
                    tbl_os_status.status_os
            FROM    tbl_os_status
            JOIN    tbl_status_os using(status_os)
            JOIN    tbl_os_troca    ON  tbl_os_status.os    = tbl_os_troca.os
       LEFT JOIN    tbl_admin       ON  tbl_os_troca.admin  = tbl_admin.admin
                                    AND tbl_admin.fabrica   = $login_fabrica
            WHERE   tbl_os_status.os        = $os";

    $res2=pg_query($con,$sql2);

    if(pg_num_rows($res2) > 0){
        if(in_array(pg_fetch_result($res2,0,'status_os'),array(13,19))){
                echo "<TABLE width='700' border='0' cellspacing='0' cellpadding='0' class='Tabela' align='center'>";
                echo "<TR>";
                echo "<TD class='inicio' colspan='2' align='center'>HISTÓRICO</TD>";
                echo "</TR>";
                for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
                        $data             = pg_fetch_result($res2,$i,data);
                        $descricao_status = pg_fetch_result($res2,$i,descricao);
                        $observacao_status = pg_fetch_result($res2,$i,observacao);
                        echo "<TR>";
                        echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
                        echo "</tr>";
                        echo "<TR>";
                        echo "<TD class='conteudo2' colspan='2' align='center'>Motivo: $observacao_status</TD>";
                        echo "</TR>";
                }
            echo "</TABLE></center>";
        }
    }
}

 if ($excluida == "t") {
     if (strlen($motivo_exclusao) > 0) $motivo_exclusao = "Motivo: ".$motivo_exclusao;
?>
<CENTER>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
<TR>
    <TD  bgcolor="#FFE1E1" height='20' style="text-align:center;">
    <?
    if ($login_fabrica==20 AND $os_recusada =='t'){
        #HD 13940
        echo "OS RECUSADA - ".$status_recusa_observacao;
    }else{
        echo ($login_fabrica != 30 && !$cancelaOS) ? "<h1>ORDEM DE SERVIÇO EXCLUÍDA</h1>" : "<h1>ORDEM DE SERVIÇO CANCELADA</h1>";
        echo $motivo_exclusao;
    }
    ?>
    </TD>
    <?
        if($login_fabrica==3 AND strlen($os)>0){
            $sqlE = "SELECT tbl_admin.login
                     FROM tbl_os
                     JOIN tbl_admin on tbl_admin.admin = tbl_os.admin_excluida
                     WHERE tbl_os.os = $os";
            $resE = pg_exec($con,$sqlE);

            if(pg_numrows($resE)>0){
                $admin_nome = pg_result($resE,0,login);
                echo "<TD bgcolor='#FFE1E1' height='20'>";
                    echo "<h1>Admin exclusão: $admin_nome</h1>";
                echo "</TD>";
            }
        }
    ?>
</TR>
</TABLE>
</CENTER>
<?
}

if($login_fabrica == 151 and $os_bloqueada == 't'){
?>
    <CENTER>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
<TR>
    <TD  bgcolor="#FFE1E1" height='20' colspan='2' style="text-align:center;">
    <h1>ORDEM DE SERVIÇO CONGELADA</h1>
    </TD>
</TR>
<tr>
    <td class='conteudo' >Admin:<?=$os_campos_adicionais['admin'] ?></td>
    <td class='conteudo' >Data: <?=$os_campos_adicionais['data'] ?></td>
</tr>
</TABLE>
</CENTER>
<?php
}elseif($login_fabrica == 50 and $os_bloqueada == 't'){ ?>
<CENTER>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
<TR>
    <TD  bgcolor="#FFE1E1" height='20' colspan='2' style="text-align:center;">
    <h1>ORDEM DE SERVIÇO BLOQUEADA</h1>
    </TD>
</TR>
<tr>
    <td class='conteudo' >Observação:<?=utf8_decode($os_campos_adicionais['obs']) ?></td>
    <td class='conteudo' >Data: <?=mostra_data($os_campos_adicionais['data']) ?></td>
</tr>
</TABLE>
</CENTER>
<?php }
?>
<?
if (strlen ($auditoria_motivo) > 0) {
    echo "<center><h2><font size='+2'> $auditoria_motivo </font></h2></center>";
}
?>

<?
if ($ressarcimento == "t") {
    echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
    echo "<TR height='30'>";
    echo "<TD align='left' colspan='3'>";
    echo "<font family='arial' size='2' color='#ffffff'><b>";
    echo "Ressarcimento Financeiro";
    echo "</b></font>";
    echo "</TD>";
    echo "</TR>";

    //4/1/2008 HD 11068
    if(in_array($login_fabrica,array(11,45,101,172))){
        $sql = "SELECT
                    observacao,
                    descricao
                FROM tbl_os_troca
                LEFT JOIN tbl_causa_troca USING (causa_troca)
                WHERE tbl_os_troca.os = $os";
        $resY = pg_query ($con,$sql);

        if (pg_num_rows ($resY) > 0) {
            $troca_observacao = pg_fetch_result ($resY,0,observacao);
            $troca_causa = pg_fetch_result ($resY,0,descricao);
        }
    } else if (in_array($login_fabrica, array(30,141,144))) {
        $sql_troca = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS data FROM tbl_os_troca WHERE os = {$os}";
        $res_troca = pg_query($con, $sql_troca);

        $data_ressarcimento = pg_fetch_result($res_troca, 0, "data");
    }
    echo "<tr>";
    echo "<TD class='titulo3'  height='15' >Responsável</TD>";
    echo "<TD class='titulo3'  height='15' >Data</TD>";
    //4/1/2008 HD 11068
    if(in_array($login_fabrica,array(45,101))){
        echo "<TD class='titulo3'  height='15' >Observação</TD>";
    }elseif( in_array($login_fabrica, array(11,172)) ){
        echo "<TD class='titulo3'  height='15' >Causa</TD>";
    }else{
        echo "<TD class='titulo3'  height='15' >&nbsp;</TD>";
    }
    echo "</tr>";

    // HD 23030
    if($login_fabrica==3){
        if(strlen($data_fechamento) ==0){
            $data_fechamento = $data_conserto;
        }
    }

    echo "<tr>";
    echo "<TD class='conteudo' height='15'>";
    echo "&nbsp;&nbsp;&nbsp;";
    echo $troca_admin;
    echo "&nbsp;&nbsp;&nbsp;";
    echo "</td>";
    echo "<TD class='conteudo' height='15' nowrap>";
    echo "&nbsp;&nbsp;&nbsp;";
    if(in_array($login_fabrica, array(11,30,141,144,172))) { // HD 56237
        echo $data_ressarcimento;
    }else{
        echo $data_fechamento ;
    }
    echo "&nbsp;&nbsp;&nbsp;";
    echo "</td>";

    //4/1/2008 HD 11068
    if(in_array($login_fabrica,array(45,101))){
        echo "<TD class='conteudo' height='15' width='80%'>$troca_observacao</td>";
    }elseif( in_array($login_fabrica, array(11,172)) ){
        echo "<TD class='conteudo'  height='15' >$troca_causa</TD>";
    }else{
        echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
    }
    echo "</tr>";

    if( in_array($login_fabrica, array(11,172)) ) { // hd 56237
        echo "<tr>";
        echo "<TD class='conteudo' height='15' colspan='100%'>OBS: $troca_observacao</td>";
        echo "</tr>";
    }

    echo "</table>";
}

##########################################################################################
####################### INFORMÇÕES DE TROCA TECTOY HD 311414 25/03/2011 ##################
##########################################################################################

if($login_fabrica == 6) {

    $sql_produto_tectoy = "
                            SELECT
                                tbl_os_troca.produto
                            from tbl_os_troca
                            where os = $os
                            ";
    $res_produto_tectoy = pg_query ($con,$sql_produto_tectoy);
    if ( pg_num_rows($res_produto_tectoy)>0 ) {

        $produto_troca_tectoy = pg_result($res_produto_tectoy,0,"produto");

    }

    $sql = " SELECT
                tbl_causa_troca.descricao as descricao_causa,
                tbl_causa_troca_item.descricao as descricao_causa_item,
            ";

    if ( $produto_troca_tectoy ){
        $sql .= "tbl_familia.descricao,
                 tbl_produto.descricao,";
    }
    $sql .="

                tbl_os_troca.ri

            FROM tbl_os_troca

            JOIN tbl_admin ON tbl_os_troca.admin = tbl_admin.admin
            JOIN tbl_causa_troca USING(causa_troca)
            JOIN tbl_causa_troca_item USING(causa_troca_item)";
    if ( $produto_troca_tectoy ){

    $sql .="
            JOIN tbl_produto USING(produto)
            JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia";
    }

    $sql .="

            WHERE os = $os";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){

        $colspan_tectoy = ($produto_troca_tectoy) ? "colspan='5'" : "colspan='3'";

        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR>";
            echo "<td class='titulo_coluna' $colspan_tectoy >Registro de Troca de Produtos da OS</td>";
        echo "</TR>";

        echo "<tr>";
            if ($produto_troca_tectoy){
                echo "<td class='titulo2' > Produto </td>";
                echo "<td class='titulo2'> Familia </td> ";
            }
                echo "<td class='titulo2'> Causa da Troca </td>";
                echo "<td class='titulo2'> Causa Raiz </td>";
                echo "<td class='titulo2'> Número do Registro </td>";
        echo "</tr>";

        for ($i = 0; $i < pg_num_rows($res); $i++){

            $descricao_causa_troca = pg_result($res,$i,'descricao_causa' );
            $descricao_causa_troca_item = pg_result($res,$i,'descricao_causa_item' );
            $numero_registro = pg_result($res,$i,'ri' );

            if ($produto_troca_tectoy){
            $produto_troca = pg_regult($res,$i,'descricao');
            $familia_troca = pg_regult($res,$i,'familia');
            }


            echo "<TR>";
                if ($produto_troca){
                    echo "<td class='conteudo' > $produto_troca </td>";
                    echo "<td class='conteudo'> $familia_troca </td> ";
                }
                echo "<TD class='conteudo' align='left'> $descricao_causa_troca </TD>";
                echo "<TD class='conteudo' align='left'> $descricao_causa_troca_item </TD>";
                echo "<TD class='conteudo' align='left'> $numero_registro </TD>";
            echo "</TR>";
        }

        echo "</TABLE>";

    }
}

########################### INFORMÇÕES DE TROCA TECTOY - FIM #############################

if (isset($novaTelaOs)) {
    $sql = "
        SELECT  TO_CHAR(tbl_os_troca.data, 'DD/MM/YYYY HH24:MI')                      AS data                             ,
                tbl_os_troca.ressarcimento                                                                              ,
                (produto_trocado.referencia || ' - ' || produto_trocado.descricao)  AS produto_trocado                  ,
                (produto_troca.referencia   || ' - ' || produto_troca.descricao)    AS produto_troca                    ,
                produto_trocado.valor_troca                                         AS valor_base_troca                 ,
                tbl_os_troca.setor                                                                                      ,
                tbl_os_troca.situacao_atendimento                                                                       ,
                tbl_causa_troca.descricao                                           AS causa_troca                      ,
                tbl_os_troca.gerar_pedido                                                                               ,
                tbl_os_troca.distribuidor                                                                               ,
                tbl_os_troca.observacao                                                                                 ,
                tbl_admin.nome_completo                                             AS admin                            ,
                tbl_os_troca.ri                                                     AS numero_registro                  ,
                tbl_os_troca.admin_autoriza                                         AS admin_autoriza                   ,
                tbl_ressarcimento.nome                                              AS ressarcimento_nome               ,
                tbl_ressarcimento.cpf                                               AS ressarcimento_cpf                ,
                tbl_banco.codigo                                                    AS ressarcimento_banco              ,
                tbl_ressarcimento.agencia                                           AS ressarcimento_agencia            ,
                tbl_ressarcimento.conta                                             AS ressarcimento_conta              ,
                tbl_ressarcimento.tipo_conta                                        AS ressarcimento_tipo_conta         ,
                TO_CHAR(tbl_ressarcimento.previsao_pagamento, 'DD/MM/YYYY')         AS ressarcimento_previsao_pagamento ,
                tbl_ressarcimento.valor_original                                    AS ressarcimento_valor              ,
                TO_CHAR(tbl_ressarcimento.liberado,'DD/MM/YYYY')                    AS ressarcimento_data_pagamento,
                TO_CHAR(tbl_ressarcimento.cancelado,'DD/MM/YYYY')                    AS ressarcimento_cancelado,
                TO_CHAR(tbl_ressarcimento.aprovado,'DD/MM/YYYY')                    AS ressarcimento_aprovado
        FROM    tbl_os_troca
        JOIN    tbl_produto AS produto_trocado  ON  produto_trocado.produto     = tbl_os_troca.produto
                                                AND produto_trocado.fabrica_i   = {$login_fabrica}
   LEFT JOIN    tbl_peca    AS produto_troca    ON  produto_troca.peca          = tbl_os_troca.peca
                                                AND produto_troca.fabrica       = {$login_fabrica}
   LEFT JOIN    tbl_causa_troca                 ON  tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca
                                                AND tbl_causa_troca.fabrica     = {$login_fabrica}
   LEFT JOIN    tbl_admin                       ON  tbl_admin.admin             = tbl_os_troca.admin
                                                AND tbl_admin.fabrica           = {$login_fabrica}
   LEFT JOIN    tbl_ressarcimento               ON  tbl_ressarcimento.os_troca  = tbl_os_troca.os_troca
                                                AND tbl_ressarcimento.fabrica   = {$login_fabrica}
   LEFT JOIN    tbl_banco                       ON  tbl_banco.banco             = tbl_ressarcimento.banco
        WHERE   tbl_os_troca.fabric = {$login_fabrica}
        AND     tbl_os_troca.os     = {$os}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $k = 0;
        while ($os_troca = pg_fetch_object($res)) {

            $link_formulario = ($os_troca->ressarcimento == "t") ? "formulario_troca_recompra.php?os=$os&acao=ressarcimento" : "formulario_troca_recompra.php?os=$os&acao=troca";

            $titulo_tabela_troca = ($os_troca->ressarcimento == "t") ? "Produto Ressarcido" : (($os_troca->causa_troca != "Base de Troca") ? "Produto Trocado" : "Produto trocado da base de troca");

            if ($telecontrol_distrib)
                $atende_troca = is_null($os_troca->distribuidor) ? 'FÁBRICA' : 'DISTRIBUIDOR';

            if ($k == 0) { ?>
		    <a href="<?php echo $link_formulario;?>" target="_BLANK">Imprimir formulário de <?php echo $titulo_tabela_troca; ?></a>
	    <?php } ?>

            <table class="Tabela" style="width: 700px; margin: 0 auto; table-layout: fixed;" cellspacing="1" cellpadding="0" border="0" >
                <thead>
                    <tr>
                        <td style="color: #FFFFFF; font-weight: bold; text-align: center;" colspan="4" ><?=$titulo_tabela_troca?></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="titulo"><?=$titulo_tabela_troca?></td>
                        <td class="conteudo" colspan="3"><?=$os_troca->produto_trocado?></td>
                    </tr>
                    <?php
                    if (in_array($login_fabrica, array(169,170)) && $os_troca->ressarcimento == "t") {
                        if (empty($os_troca->ressarcimento_aprovado) && empty($os_troca->ressarcimento_cancelado)) {
                            $ressarcimento_status = "Pendente de Aprovação";
                        } else if (!empty($os_troca->ressarcimento_cancelado)) {
                            $ressarcimento_status = "Cancelado";
                        } else {
                            $ressarcimento_status = "Aprovado";
                        }
                    ?>
                        <tr>
                            <td class="titulo">Status</td>
                            <td class="conteudo" colspan="3"><?=$ressarcimento_status?></td>
                        </tr>
                    <?php
                    }

                    if ($os_troca->ressarcimento == "f" and !empty($os_troca->produto_troca)) {
                    ?>
                        <tr>
                            <td class="titulo">Trocado por</td>
                            <td class="conteudo" colspan="3"><?=$os_troca->produto_troca?></td>
                        </tr>
                    <?php
                    }

                    if ($os_troca->ressarcimento == "t") {
                    ?>
                        <tr>
                            <td class="titulo" >Nome do Cliente</td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_nome?></td>
                            <td class="titulo" >CPF do Cliente</td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_cpf?></td>
                        </tr>
                        <tr>
                            <td class="titulo" >Valor</td>
                            <td class="conteudo" ><?=number_format($os_troca->ressarcimento_valor, 2, ",", ".")?></td>
                            <td class="titulo" >Previsão de Pagamento</td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_previsao_pagamento?></td>
                        </tr>
                        <tr>
                            <td class="titulo" >Banco</td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_banco?></td>
                            <td class="titulo" >Agência</td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_agencia?></td>
                        </tr>
                        <tr>
                            <td class="titulo" >Conta</td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_conta?></td>
                            <td class="titulo" >Tipo de Conta</td>
                            <td class="conteudo" ><?=($os_troca->ressarcimento_tipo_conta == "C") ? "Conta Corrente" : "Conta Poupança"?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    <tr>
                        <td class="titulo">Setor</td>
                        <td class="conteudo">
                            <?php

                            switch ($os_troca->setor) {
                                case "revenda":
                                    echo "Revenda";
                                    break;

                                case "carteira":
                                    echo "Carteira";
                                    break;

                                case "sac":
                                    echo "SAC";
                                    break;

                                case "procon":
                                    echo "Procon";
                                    break;

                                case "sap":
                                    echo "SAP";
                                    break;

                                case "suporte_tecnico":
                                    echo "Suporte Técnico";
                                    break;
                            }

                            ?>
                        </td>
                        <td class="titulo">Situação Atendimento</td>
                        <td class="conteudo">
                            <?php

                            switch ($os_troca->situacao_atendimento) {
                                case 0:
                                    echo "Produto em Garantia";
                                    break;

                                case 50:
                                    echo "Faturado 50%";
                                    break;

                                case 100:
                                    echo "Faturado 100%";
                                    break;
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="titulo">Causa Troca</td>
                        <td class="conteudo"><?=$os_troca->causa_troca?></td>
                        <td class="titulo">Admin</td>
                        <td class="conteudo"><?=$os_troca->admin?></td>
                    </tr>
                    <tr>
<?php
                            if ($os_troca->causa_troca != "Base de Troca") {
?>
                        <td class="titulo">Número de Registro</td>
                        <td class="conteudo"><?=$os_troca->numero_registro?></td>
<?php
                            } else {
?>
                        <td class="titulo">Valor do produto trocado</td>
                        <td class="conteudo">R$ <?=number_format($os_troca->valor_base_troca,2,',','')?></td>
<?php
                            }
?>
                        <td class="titulo">Gera Pedido</td>
                        <td class="conteudo"><?=($os_troca->gerar_pedido == "t") ? "Sim" : "Não"?></td>
                    </tr>
<?php
                    if ($login_fabrica == 151 AND strlen($os_troca->admin_autoriza) > 0) {
                        $sql_in = "SELECT nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$os_troca->admin_autoriza}";
                        $res_in = pg_query($con,$sql_in);

                        if (pg_num_rows($res_in) > 0) {
                            $nome_interventor = pg_fetch_result($res_in, 0, nome_completo);
                            ?>
                            <tr>
                                <td class="titulo">Interventor</td>
                                <td class="conteudo" colspan="3"><?=$nome_interventor?></td>
                            </tr>
                        <?php

                        }

                    }
                    $colSpanObs = isset($atende_troca) ? '' : 'colspan="3"';
?>
                    <tr>
                        <td class="titulo">Data</td>
                        <td class="conteudo"><?=$os_troca->data?></td>
<?php
                    if ($atende_troca) {
?>
                        <td class="titulo">Atendido Por:</td>
                        <td class="conteudo"><?=$atende_troca?></td>
<?php
                    }
                    if ($login_fabrica == 162 && $os_troca->ressarcimento == "t") {
?>
                        <td class="titulo">Data Pagamento:</td>
                        <td class="conteudo"><?=$os_troca->ressarcimento_data_pagamento?></td>
<?php
                    }
?>
                    </tr>
                    <tr>
                        <td class="titulo">Observação</td>
                        <td class="conteudo" colspan="3"><?=$os_troca->observacao?></td>
                    </tr>
                </tbody>
            </table>
            <br />
        <?php
		$k++;
        }

	}

}

if ($ressarcimento <> "t" && !isset($novaTelaOs)) {
    if ($troca_garantia == "t") {
        $display = " style='display:table;' ";
        if($login_fabrica == 30 && $gerar_pedido != 't'){
            $display = " style='display:none;' ";
        }
        $gerar_pedido = $gerar_pedido == 't' ? "Sim" : "Não";
?>
        <table width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela' <?=$display?>>
            <TR height='30'>
                <TD align='center' colspan='3'>
                    <font family='arial' size='2' color='#ffffff'><b>
                        Produto Trocado
                    </b>
                    </font>
                </TD>
            </TR>

            <tr>
                <TD align='left' class='titulo3'  height='15' >Responsável</TD>
                <TD align='left' class='titulo3'  height='15' >Data</TD>
                <TD align='left' class='titulo3'  height='15' >Trocado Por</TD>
            </tr>
<?php
        /*
            Adicionado regra para sempre pesquisar pela última os_troca e pelo último os_produto
            pois estava pegando produto de trocas já canceladas e não tem como uma os_troca menor que a max(os_troca)
            ser a troca atual pois sempre que troca novamente se não tem pedido é excluido a troca anterior e se
            tem pedido o pedido é cancelado
         */

        $sql_max = "SELECT max(os_troca) as os_troca FROM tbl_os_troca WHERE fabric = $login_fabrica AND os = $os";
        $res_max = pg_query($con, $sql_max);
        $os_troca = pg_fetch_result($res_max, 0, "os_troca");

        $sql_max = "SELECT max(os_produto) as os_produto FROM tbl_os_produto WHERE os = $os";
        $res_max = pg_query($con, $sql_max);
        $os_produto = pg_fetch_result($res_max, 0, "os_produto");

        $sql = "SELECT TO_CHAR(data,'dd/mm/yyyy hh:mi') AS data            ,
                        setor                                              ,
                        situacao_atendimento                               ,
                        tbl_os_troca.observacao                            ,
                        tbl_peca.referencia             AS peca_referencia ,
                        tbl_peca.descricao              AS peca_descricao  ,
                        tbl_causa_troca.descricao       AS causa           ,
                        tbl_os_troca.modalidade_transporte                 ,
                        tbl_os_troca.causa_troca                           ,
                        tbl_os_troca.envio_consumidor
                FROM tbl_os_troca
                JOIN tbl_peca        USING(peca)
                JOIN tbl_causa_troca USING(causa_troca)
                JOIN tbl_os          ON tbl_os_troca.os = tbl_os.os
                JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_troca.os
                WHERE tbl_os_troca.os = $os
                AND  tbl_os.fabrica = $login_fabrica
                AND  tbl_os_troca.os_troca = $os_troca
                AND  tbl_os_produto.os_produto = $os_produto ";
        $resX = pg_query ($con,$sql);
        if (pg_num_rows ($resX) > 0) {
            $troca_data           = pg_fetch_result ($resX,0,data);
            $troca_setor          = pg_fetch_result ($resX,0,setor);
            $troca_situacao       = pg_fetch_result ($resX,0,situacao_atendimento);
            $troca_observacao     = pg_fetch_result ($resX,0,observacao);
            $troca_peca_ref       = pg_fetch_result ($resX,0,peca_referencia);
            $troca_peca_des       = pg_fetch_result ($resX,0,peca_descricao);
            $causa_troca          = pg_fetch_result ($resX,0,causa_troca);
            $troca_causa          = pg_fetch_result ($resX,0,causa);
            $troca_transporte     = pg_fetch_result ($resX,0,modalidade_transporte);
            $envio_consumidor     = pg_fetch_result ($resX,0,envio_consumidor);

            if($troca_situacao == 0) $troca_situacao = "Garantia";
            else                     $troca_situacao .= "% Faturado";
            if($envio_consumidor=='t') $envio_consumidor = "Envio para o Consumidor";
            else                       $envio_consumidor = "Envio para o Posto Autorizado";

        if($login_fabrica == 141){
            switch($troca_setor){
                case '02': $troca_setor = 'ILHEUS'; break;
                case '04': $troca_setor = 'MANAUS'; break;
                case '08': $troca_setor = 'EXTREMA'; break;
                case '12': $troca_setor = 'SERVICES'; break;
                case '18': $troca_setor = 'EXTREMA FL'; break;
                case '19': $troca_setor = 'DECODER'; break;
            }
        }
?>
            <tr>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$troca_admin?>&nbsp;&nbsp;&nbsp;
                </td>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$troca_data?>&nbsp;&nbsp;&nbsp;
                </td>
                <TD class='conteudo' align='left' height='15' nowrap >
                    <?=$troca_peca_ref . " - " . $troca_peca_des;?>
                </td>
            </tr>

            <tr>
                <TD align='left' class='titulo3'  height='15' >Setor</TD>
<?php
        if ($login_fabrica != 30) {
?>
                <TD align='left' class='titulo3'  height='15' >Situação do Atendimento</TD>

<?php
        }
        if ( in_array($login_fabrica, array(11,172)) ) {
?>
                <TD align='left' class='titulo3'  height='15' >Causa</TD>
<?php
        } else {
            if($login_fabrica == 30){
                $colspan = "colspan = '2'";
            }
?>
                <TD align='left' class='titulo3'  height='15' <?=$colspan?>>Causa da Troca</TD>
<?php
        }
?>
            </tr>
            <tr>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$troca_setor;?>&nbsp;&nbsp;&nbsp;
                </td>
<?php
        if ($login_fabrica != 30) {
?>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$troca_situacao?>&nbsp;&nbsp;&nbsp;
                </td>
<?php
        }
?>
                <TD class='conteudo' align='left' height='15' nowrap <?=$colspan?>>
                    <?=$troca_causa?>
                </td>
            </tr>
<?php
        if ($login_fabrica == 3) {
            $sql = "SELECT  descricao
                    FROM    tbl_causa_troca
                    WHERE   fabrica         = $login_fabrica
                    AND     causa_troca     = $causa_troca ";
            $res = pg_query($con,$sql);
            $causa_troca = pg_fetch_result($res, 0, 'descricao');
?>
            <tr>
                <TD align='left' class='titulo3'  height='15' >Transporte</TD>
                <TD align='left' class='titulo3'  height='15' >Situação do Atendimento</TD>
                <TD align='left' class='titulo3'  height='15' >Motivo da troca/ressarcimento</TD>
            </tr>
            <tr>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$troca_transporte?>
                </td>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$envio_consumidor?>
                </td>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$causa_troca?>
                </td>
            </tr>
<?php
            }

            if ($login_fabrica != 30) {
?>
            <tr>
                <td class='titulo3' align='left' height='15' nowrap>Gera Pedido</td>
<?php
                if ($login_fabrica != 101) {
?>
                <td colspan='2' class='titulo3'>&nbsp;</td>
<?php
                } else {
?>
                <td colspan='2' align='left' class='titulo3'>Destinatário</td>

<?php
                }
?>
            </tr>
            <tr>
                <td class='conteudo'>&nbsp;&nbsp;&nbsp;<?=$gerar_pedido?></td>
<?php
                if ($login_fabrica != 101) {
?>
                <td colspan='2' class='conteudo'>&nbsp;</td>
<?php
                } else {
?>
                <td colspan='2' class="conteudo"><?=$envio_consumidor?></td>
<?php
                }
?>
            </tr>

<?php
            }
            if ($login_fabrica == 101) {
                $sqlPedidoTroca = "
                    SELECT  TO_CHAR(tbl_faturamento.saida,'DD/MM/YYYY') AS data_saida,
                            UPPER(tbl_faturamento.conhecimento)         AS rastreio
                    FROM    tbl_faturamento
                    JOIN    tbl_faturamento_item USING(faturamento)
                    WHERE   tbl_faturamento_item.os_item IN (
                        SELECT  os_item
                        FROM    tbl_os_item
                        JOIN    tbl_os_produto USING(os_produto)
                        JOIN    tbl_os USING(os)
                        WHERE   os      = $os
                        AND     fabrica = $login_fabrica
                    )
                    AND     tbl_faturamento.fabrica = $login_fabrica
                ";
                $resPedidoTroca = pg_query($con,$sqlPedidoTroca);
                while ($resultado = pg_fetch_object($resPedidoTroca)) {

?>
            <tr>
                <td class='titulo3' align='left' height='15' nowrap>Data Saída</td>
                <td colspan='2' class='titulo3'>Rastreio</td>
            </tr>
            <tr>
                <td class='conteudo'>&nbsp;&nbsp;&nbsp;<?=$resultado->data_saida?></td>
                <td colspan='2' class='conteudo'><?=$resultado->rastreio?></td>
            </tr>
<?php
                }
            }
?>
            <tr>
                <TD class='conteudo' align='left' height='15'  colspan='3'><b>OBS:</b>
                    <?=$troca_observacao?>
                </td>
            </tr>
<?php
        } else {
            $sql_max = "SELECT max(os_troca) as os_troca FROM tbl_os_troca WHERE fabric = $login_fabrica AND os = $os";
            $res_max = pg_query($con, $sql_max);
            $os_troca = pg_fetch_result($res_max, 0, "os_troca");

            $sql_max = "SELECT max(os_produto) as os_produto FROM tbl_os_produto WHERE os = $os";
            $res_max = pg_query($con, $sql_max);
            $os_produto = pg_fetch_result($res_max, 0, "os_produto");

            $sql = "SELECT  TO_CHAR(data,'dd/mm/yyyy hh:mi') AS data,
                            tbl_peca.referencia ,
                            tbl_peca.descricao
                    FROM    tbl_peca
                    JOIN    tbl_os_item     USING (peca)
                    JOIN    tbl_os_produto  USING (os_produto)
                    JOIN    tbl_os_extra    USING (os)
                    JOIN    tbl_os_troca    ON tbl_os_produto.os = tbl_os_troca.os
                    WHERE   tbl_os_produto.os           = $os
                    AND     tbl_peca.produto_acabado    IS TRUE
                    AND     tbl_os_troca.os_troca       = $os_troca
                    AND     tbl_os_produto.os_produto   = $os_produto ";
            $resX = pg_query ($con,$sql);
            if (pg_num_rows ($resX) > 0) {
                for($k = 0; $k < pg_numrows($resX); $k++){
                    $troca_por_referencia = pg_fetch_result ($resX,$k,referencia);
                    $troca_por_descricao  = pg_fetch_result ($resX,$k,descricao);
                    $data_troca  = pg_fetch_result ($resX,$k,data);

?>
            <tr>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=$troca_admin?>&nbsp;&nbsp;&nbsp;
                </td>
                <TD class='conteudo' align='left' height='15' nowrap>
                    &nbsp;&nbsp;&nbsp;<?=(!empty($data_troca)) ? $data_troca : $data_fechamento;?>&nbsp;&nbsp;&nbsp;
                </td>
                <TD class='conteudo' align='left' height='15' nowrap >
                    <?=$troca_por_referencia . " - " . $troca_por_descricao?>
                </td>
            </tr>
<?php
                }
            }
        }
?>
        </table>
<?php
    }
}

// Verifica se o pedido de peça foi cancelado ou autorizado caso a peça esteja bloqueada para garantia

#HD 14830  Fabrica 25
#HD 13618  Fabrica 45


if ($historico_intervencao) {
    $sql_status = "SELECT
                status_os,
                observacao,
                tbl_admin.login,
                to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data,
                tbl_os_status.data as date
                FROM tbl_os_status
                LEFT JOIN tbl_admin USING(admin)
                WHERE os = $os
                AND status_os IN (13,19,20,72,73,62,64,65,67,68,81,87,88,98,99,100,101,102,103,104,116,117,118,147,167,168,171,172,173,179,185,186,187)
                ORDER BY date DESC LIMIT 1";

    $res_status = pg_query($con,$sql_status);

    $resultado  = pg_num_rows($res_status);

    if ($resultado == 1) {

        $data_status       = trim(pg_fetch_result($res_status,0,'data'));
        $status_os         = trim(pg_fetch_result($res_status,0,'status_os'));
        $status_observacao = trim(pg_fetch_result($res_status,0,'observacao'));
        $intervencao_admin = trim(pg_fetch_result($res_status,0,'login'));

        if (strlen($intervencao_admin) > 0 AND $login_fabrica <> 50) {
            $intervencao_admin = "<br><b>OS em intervenção colocada pela Fábrica ($intervencao_admin)</b>";
        }

        if ($status_os == 72 or $status_os == 116) {
            echo "<br />
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
                    <b style='font-size:11px'>A peça solicitada necessita de autorização. <br>O PA aguarda a fábrica analisar o pedido</b>
                    $intervencao_admin
                </div>
                </center><br />";
        } else if ($status_os == 73 or $status_os == 117) {
            echo "<br />
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br />
            ";
        } else if ($status_os == 147) {
            echo "<br>
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br>
            ";
        } else if ($status_os == 62) {
            if($login_fabrica != 124 && $login_fabrica != 128 && $login_fabrica != 6){
                echo "<br>
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br>
                ";
            }else if ($login_fabrica == 6){

                 $sql = "SELECT  troca_obrigatoria
                            FROM    tbl_produto
                            WHERE   referencia = '$produto_referencia'
                            AND fabrica_i = $login_fabrica
                            ";
                $res = @pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $troca_obrigatoria   = trim(pg_fetch_result($res,0,troca_obrigatoria));
                }
                if ($troca_obrigatoria == 't'){
                    echo "<br>
                        <center>
                            <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                                <b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
                                <b style='font-size:11px'>Favor entrar em contato com a Fábrica para envio do produto</b>
                            </div>
                        </center><br>
                        ";
                }else{
                    echo "<br>
                        <center>
                        <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                            <b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
                            <b style='font-size:11px'>$status_observacao</b>
                        </div>
                        </center><br>
                    ";
                }
            }
        } else if (($status_os == 118 or $status_os == 167) and !in_array($login_fabrica, array(129,123,128,125,126,127,124,131,134))) {
            echo "
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        } else if ($status_os == 64) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS Liberada da Assistência Técnica da Fábrica</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        } else if ($status_os == 81) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS Reprovada da Intervenção da Assistência Técnica da Fábrica</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }
        else if ($status_os == 19 && (!in_array($login_fabrica,array(1,91,134)))) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS Aprovada da Auditoria de Peças em Garantia</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }else if ($status_os == 87) {
            echo "<br />
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
                    <b style='font-size:11px'>A peça solicitada necessita de autorização. ";
                if ($login_fabrica == 1) {
                    echo "<br>Entrar em contato com o Suporte de sua região.</b>";
                } else {
                    echo "<br>Aguarde a fábrica analisar seu pedido.</b>";
                }
            echo "</div>
                </center><br />";
            if ($login_fabrica == 1) {
                echo "<script language='JavaScript'>alert('OS em intervenção. Gentileza, entre em contato com o Suporte de sua região');</script>";
            }
        } else if ($status_os == 88) {
            echo "<br />
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br />
            ";
        } else if ($status_os==168){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS sob intervenção de custos extras</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }else if ($status_os==171){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS sob intervenção de custos adicionais</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }else if ($status_os==172){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS liberada da intervenção de custos adicionais</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }else if ($status_os==173){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS recusada da intervenção de custos adicionais</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }else if ($login_fabrica == 50 || $login_fabrica == 74) {
            # HD 42933 - Alterei para Colormaq, não estava mostrando
            #    a última interação da OSs
            #if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){
                $sql_status = /*"select descricao from tbl_status_os where status_os = $status_os";*/
                    "SELECT
                        tbl_os_status.status_os,
                        tbl_os_status.observacao,
                        tbl_admin.login,
                        tbl_status_os.descricao,
                        to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
                    FROM tbl_os_status
                    JOIN tbl_status_os USING (status_os)
                    LEFT JOIN tbl_admin USING (admin)
                    WHERE os = $os
                    ORDER BY tbl_os_status.data DESC LIMIT 1";

                $res_status = pg_query($con, $sql_status );
                if (pg_num_rows($res_status) > 0) {
                    $data_status       = pg_fetch_result($res_status, 0, 'data');
                    $descricao_status  = pg_fetch_result($res_status, 0, 'descricao');
                    $intervencao_admin = pg_fetch_result($res_status, 0, 'login');
                    $descricao_status  = pg_fetch_result($res_status, 0, 'descricao');
                    $status_observacao = pg_fetch_result($res_status, 0, 'observacao');

                echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
                        echo "<TR>";
                            echo "<TD class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='4'>&nbsp;STATUS OS &nbsp;</TD>";
                        echo "</TR>";
                        echo "<TR>";
                            echo "<TD class='inicio' nowrap>&nbsp;DATA &nbsp;</TD>";
                            echo "<TD class='inicio' nowrap>&nbsp;ADMIN &nbsp;</TD>";
                            echo "<TD class='inicio' nowrap>&nbsp;STATUS &nbsp;</TD>";
                            echo "<TD class='inicio' nowrap>&nbsp;MOTIVO &nbsp;</TD>";
                        echo "</TR>";
                        echo "<TR>";
                            echo "<TD class='conteudo' width='10%'>&nbsp; $data_status </TD>";
                            echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
                            echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
                            echo "<TD class='conteudo'>&nbsp;$status_observacao </TD>";
                        echo "</TR>";
                echo "</TABLE>";
                }
        #}
        }else if($status_os == 20){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS em análise pelo fabricante</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        } else if ($status_os == 179 && $login_fabrica == 91) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS Com intervenção de Produto Crítico</b><br>
                </div>
                </center><br>
            ";
        }  else if($status_os == 187){
        echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>{$status_observacao}</b><br>
                </div>
                </center><br>
            ";

    }else if ($status_os == 13 && $login_fabrica == 91) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>{$status_observacao}</b><br>
                </div>
                </center><br>
            ";
        }
    }
    echo "</div>\n";
}

if($login_fabrica == 156 and 1 == 2){

            $sql = "SELECT tbl_os.os FROM tbl_os INNER JOIN tbl_os_extra USING(os) WHERE tbl_os_extra.recolhimento IS TRUE AND tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>OS. Será Reparada Na Fábrica</b><br>
                </div>
                </center><br>
                ";
            }
}

if($historico_auditoria_24hrs){

    $sql_aud = "SELECT  CASE WHEN tbl_os_auditar.liberado IS NOT TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                             THEN 'Entrou em auditoria'
                             WHEN tbl_os_auditar.liberado IS TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                             THEN 'Aprovada'
                             ELSE 'Recusada'
                        END                                         AS status_os    ,
                        tbl_os_auditar.justificativa                AS observacao   ,
                        tbl_admin.login                                             ,
                        to_char(tbl_os_auditar.data,'DD/MM/YYYY')   AS data         ,
                        tbl_os_auditar.data                         AS date
                FROM    tbl_os_auditar
           LEFT JOIN    tbl_admin USING (admin)
                WHERE   tbl_os_auditar.os = $os
          ORDER BY      date DESC
                LIMIT   1
    ";
    #echo nl2br($sql_aud);
    $res_aud    = pg_query($con,$sql_aud);
    $resultado  = pg_num_rows($res_aud);
    if($resultado == 1){
        $data_status        = trim(pg_fetch_result($res_aud,0,'data'));
        $status_os          = trim(pg_fetch_result($res_aud,0,'status_os'));
        $status_observacao  = trim(pg_fetch_result($res_aud,0,'observacao'));
        $auditoria_admin    = trim(pg_fetch_result($res_aud,0,'login'));
?>
<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>
    <tr>
        <th class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='4'>&nbsp;AUDITORIA OS 24hrs &nbsp;</th>
    </tr>
    <tr>
        <th class="inicio" nowrap>&nbsp;DATA &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;ADMIN &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;STATUS &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;MOTIVO &nbsp;</th>
    </tr>
    <tr>
        <td class="conteudo" width="10%">&nbsp; <?=$data_status?> </td>
        <td class="conteudo">&nbsp; <?=$auditoria_admin?> </td>
        <td class="conteudo">&nbsp; <?=$status_os?> </td>
        <td class="conteudo">&nbsp; <?=$status_observacao?> </td>
    </tr>
</table>
<?
    }
}

if($login_fabrica == 114){

    $sql_aud = "SELECT  CASE WHEN tbl_os_auditar.liberado IS NOT TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                             THEN 'Entrou em auditoria'
                             WHEN tbl_os_auditar.liberado IS TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                             THEN 'Aprovada'
                             ELSE 'Recusada'
                        END                                         AS status_os    ,
                        tbl_os_auditar.justificativa                AS observacao   ,
                        tbl_os_auditar.descricao AS auditoria_descricao,
                        tbl_admin.login                                             ,
                        to_char(tbl_os_auditar.data,'DD/MM/YYYY')   AS data         ,
                        tbl_os_auditar.data                         AS date
                FROM    tbl_os_auditar
           LEFT JOIN    tbl_admin USING (admin)
                WHERE   tbl_os_auditar.os = $os
          ORDER BY      date DESC
                LIMIT   1
    ";
    #echo nl2br($sql_aud);
    $res_aud    = pg_query($con,$sql_aud);
    $resultado  = pg_num_rows($res_aud);
    if($resultado == 1){
        $data_status        = trim(pg_fetch_result($res_aud,0,'data'));
        $status_os          = trim(pg_fetch_result($res_aud,0,'status_os'));
        $status_observacao  = trim(pg_fetch_result($res_aud,0,'observacao'));
        $auditoria_descricao = pg_fetch_result($res_aud, 0, "auditoria_descricao");
        $auditoria_admin    = trim(pg_fetch_result($res_aud,0,'login'));
?>
<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>
    <tr>
        <th class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='5'>&nbsp;AUDITORIA OS &nbsp;</th>
    </tr>
    <tr>
        <th class="inicio" nowrap>&nbsp;DATA &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;ADMIN &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;STATUS &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;DESCRIÇÃO &nbsp;</th>
        <th class="inicio" nowrap>&nbsp;MOTIVO &nbsp;</th>
    </tr>
    <tr>
        <td class="conteudo" width="10%">&nbsp; <?=$data_status?> </td>
        <td class="conteudo">&nbsp; <?=$auditoria_admin?> </td>
        <td class="conteudo">&nbsp; <?=$status_os?> </td>
        <td class="conteudo">&nbsp; <?=$auditoria_descricao?> </td>
        <td class="conteudo">&nbsp; <?=$status_observacao?> </td>
    </tr>
</table>
<?
    }
}

if(strlen($extrato)>0) { //HD 61132
    echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
        echo "<TR>";
            echo "<TD class='inicio'>&nbsp;EXTRATO</TD>";
            echo "<TD class='inicio'>&nbsp;PREVISÃO</TD>";
            echo "<TD class='inicio'>&nbsp;PAGAMENTO</TD>";
        echo "</TR>";
        echo "<TR>";
	    echo "<TD class='conteudo' width='33%'>&nbsp;";

            if($login_fabrica == 1){

                $sql_inibido = "SELECT baixado FROM tbl_extrato_extra WHERE extrato = {$extrato}";
                $res_inibido = pg_query($con, $sql_inibido);

                $confere = "SELECT aprovado from tbl_extrato WHERE extrato = {$extrato} and aprovado notnull";
                $confere_res = pg_query($con, $confere);

                if(pg_num_rows($confere_res)==0){

                    $baixado_inibido = pg_fetch_result($res_inibido, 0, "baixado");

                    if(strlen($baixado_inibido) > 0){
                        $info_inibido = "(Extrato está inibido)";
                    }
                }

            }

			$urlEx = "extrato_consulta_os.php?extrato=$extrato";
            echo "<a href='$urlEx' target='_blank'>$extrato_link</a> {$info_inibido}";

        echo "</TD>";
	    echo "<TD class='conteudo' width='33%'>&nbsp;$data_pagamento </TD>";
            echo "<TD class='conteudo' width='33%'>&nbsp;$data_previsao </TD>";
        echo "</TR>";
    echo "</TABLE>";
}


if($login_fabrica ==50 AND strlen($os) > 0) { // HD 37276
    # HD 42933 - Retirei o resultado da tela, deixando apenas um pop-up
    #   mostrando todo o histórico da OS
    /*$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
                  descricao,
                  observacao
            FROM tbl_os_status
            JOIN tbl_status_os using(status_os)
            WHERE os=$os
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){*/
        echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' colspan='1' align='center'>"; #HISTÓRICO</TD>";
        ?>

        <!--<td class="inicio" colspan="1" align="left">--><a style='cursor:pointer;' onclick="javascript:window.open('historico_os.php?os=<? echo $os ?>','mywindow','menubar=1,resizable=yes,scrollbars=yes,width=500,height=350')">&nbsp;VER HISTÓRICO DA OS<!--Ver todo o Histórico--></a></td>

        <?
        echo "</TR>";
        /*for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            echo "<TR>";
            echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
            echo "</tr>";
            echo "<TR>";
            echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
            echo "</TR>";
        }*/
        echo "</TABLE></center>";
    //}
}

///////////////////////////////////////////// OS RETORNO  - FABIO 10/01/2007  - INICIO /////////////////////////////////////////////////////////////
// informações de postagem para envio do produto para BRITANIA
// ADICIONADO POR FABIO 03/01/2007


if ( in_array($login_fabrica, array(3,11,172)) ){
    $sql = "SELECT  nota_fiscal_envio,
                TO_CHAR(data_nf_envio,'DD/MM/YYYY')  AS data_nf_envio,
                numero_rastreamento_envio,
                TO_CHAR(envio_chegada,'DD/MM/YYYY')  AS envio_chegada,
                nota_fiscal_retorno,
                TO_CHAR(data_nf_retorno,'DD/MM/YYYY')  AS data_nf_retorno,
                numero_rastreamento_retorno,
                TO_CHAR(retorno_chegada,'DD/MM/YYYY')  AS retorno_chegada
            FROM tbl_os_retorno
            WHERE   os = $os;";
    $res = pg_query ($con,$sql);
    if (@pg_num_rows($res)==1){
        $retorno=1;
        $nota_fiscal_envio          = trim(pg_fetch_result($res,0,nota_fiscal_envio));
        $data_nf_envio              = trim(pg_fetch_result($res,0,data_nf_envio));
        $numero_rastreamento_envio  = trim(pg_fetch_result($res,0,numero_rastreamento_envio));
        $envio_chegada              = trim(pg_fetch_result($res,0,envio_chegada));
        $nota_fiscal_retorno        = trim(pg_fetch_result($res,0,nota_fiscal_retorno));
        $data_nf_retorno            = trim(pg_fetch_result($res,0,data_nf_retorno));
        $numero_rastreamento_retorno= trim(pg_fetch_result($res,0,numero_rastreamento_retorno));
        $retorno_chegada            = trim(pg_fetch_result($res,0,retorno_chegada));
    } else $retorno=0;

    if ($retorno==1 AND strlen($nota_fiscal_envio)==0){
        $sql_status = "SELECT status_os
                    FROM tbl_os_status
                    WHERE os=$os
                    ORDER BY data DESC LIMIT 1";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado==1){
            $status_os  = trim(pg_fetch_result($res_status,0,status_os));
            if ($status_os==65){
                echo "<br>
                    <center>
                    <b style='font-size:15px;color:#990033;padding:2px 5px'>O reparo deste produto deve ser efetuado pela assistência técnica da fábrica</b></center>";
            }
            else{
                echo "<br>
                    <center>
                    <b style='font-size:15px;background-color:#596D9B;color:white;padding:2px 5px'>O reparo deste produto foi feito pela Fábrica</b></center>";
            }
        }
    }

    if ( $retorno==1 AND $nota_fiscal_envio AND $data_nf_envio AND $numero_rastreamento_envio) {
        if (strlen($envio_chegada)==0){
            echo "<BR><b style='font-size:14px;color:#990033'>O Produto foi enviado a fábrica mas a fábrica ainda não confirmou seu recebimento.<br></b><BR>";
        }else {
            if (strlen($data_nf_retorno)==0){
                echo "<BR><b style='font-size:14px;color:#990033'>O Produto foi recebido pela fábrica em $envio_chegada<br> Aguarde a fábrica efetuar o reparo e enviar ao seu posto.</b><BR>";
            }
            else{
                if (strlen($retorno_chegada)==0){
                    echo "<BR><b style='font-size:14px;color:#990033'>O reparo do produto foi feito pela fábrica e foi enviado ao seu posto em $data_nf_retorno</b><BR>";
                }
                else {
                    echo "<BR><b style='font-size:14px;color:#990033'>O REPARO DO PRODUTO FOI FEITO PELA FÁBRICA.</b><BR>";
                }
            }
        }
    }
    if ( $retorno==1){
    ?>
    <br>

    <TABLE width='430px' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
            <TR>
                <TD class="inicio" background='imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;ENVIO DO PRODUTO À FÁBRICA</TD>
            </TR>
            <TR>
                <TD class="subtitulo" height='19px' colspan='2'>INFORMAÇÕES DO ENVIO DO PRODUTO À FÁBRICA</TD>
            </TR>
            <TR>
                <TD class="titulo3" width='260px' >NÚMERO DA NOTA FISCAL DE ENVIO &nbsp;</TD>
                <TD class="conteudo" width='170px'>&nbsp;<? echo $nota_fiscal_envio ?></TD>
            </TR>
            <TR>
                <TD class="titulo3">DATA DA NOTA FISCAL DO ENVIO &nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo $data_nf_envio ?></TD>
            </TR>
            <TR>
                <TD class="titulo3">NÚMERO O OBJETO / PAC &nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_envio"."BR' target='_blank'>$numero_rastreamento_envio</a>" ?></TD>
            </TR>
            <TR>
                <TD class="titulo3">DATA DA CHEGADA À FÁBRICA &nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo $envio_chegada; ?></TD>
            </TR>
            <TR>
                <TD class="inicio" background='imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;RETORNO DO PRODUTO DA FÁBRICA AO POSTO</TD>
            </TR>
            <TR>
                <TD class="subtitulo" height='19px' colspan='2'>INFORMAÇÕES DO RETORNO DO PRODUTO AO POSTO</TD>
            </TR>
            <TR>
                <TD class="titulo3">NÚMERO DA NOTA FISCAL DO RETORNO &nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo $nota_fiscal_retorno ?></TD>
            </TR>
            <TR>
                <TD class="titulo3">DATA DO RETORNO &nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo $data_nf_retorno ?></TD>
            </TR>
            <TR>
                <TD class="titulo3">NÚMERO O OBJETO / PAC DE RETORNO &nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo ($numero_rastreamento_retorno)?"<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>":""; ?></TD>
            </TR>
            <TR>
                <TD class="titulo3" >DATA DA CHEGADA AO POSTO&nbsp;</TD>
                <TD class="conteudo" >&nbsp;<? echo $retorno_chegada ?></TD>
            </TR>
        </TABLE>
    <br><br>
    <?
    }
}

// Mostra número do Extrato que esta OS's está - A pedido da Edina
// Fabio
// 27/12/2006
if ($login_fabrica==2){
    if (strlen(trim($data_finalizada))>0){
        $query = "SELECT extrato,
                    to_char(data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
                        data_vencimento
                FROM tbl_os
                JOIN tbl_os_extra using(os)
                JOIN tbl_extrato using(extrato)
                LEFT JOIN tbl_extrato_pagamento using(extrato)
                WHERE tbl_os.os = $os
                AND tbl_os.fabrica = 2;";
        $result = pg_query ($con,$query);
        if (pg_num_rows ($result) > 0) {
            $extrato = pg_fetch_result ($result,0,extrato);
            $data_pg = pg_fetch_result ($result,0,data_pagamento);
            $data_vcto = pg_fetch_result ($result,0,data_vencimento);
            ?><!--
            <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
                    <TR  style='font-size:12px;background-color:#ced7e7'>
                        <TD>
                            <b  style='padding:0px 10px;font-weight:normal'>    ESTA OS ESTÁ NO EXTRATO:</b>
                            <a href='http://www.telecontrol.com.br/assist/admin/extrato_consulta_os.php?extrato=<? echo $extrato; ?>' style='font-weight:bold;color:black;'><? echo $extrato; ?></a>
                            <b  style='padding:0px 15px;font-weight:normal'> DATA DO PAGAMENTO:</b>
                            <b><? echo $data_pg; ?></b>
                        </TD>
                    </TR>

            </TABLE><br>

            <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
                    <TR  >
                        <TD class='inicio' style='text-align:center;'>
                            ESTA OS ESTÁ PAGA
                        </td>
                        <TD class='titulo' style='padding:0px 15px;'>
                            EXTRATO
                        </td>
                        <td class='conteudo' style='padding:0px 15px;'>
                            <a href='http://www.telecontrol.com.br/assist/admin/extrato_consulta_os.php?extrato=<? echo $extrato; ?>' style='font-weight:bold;color:black;'><? echo $extrato; ?></a>
                        </td>
                        <td class='titulo' style='padding:0px 15px;'>
                            DATA DO PAGAMENTO
                        </td>
                        <td class='conteudo' style='padding:0px 15px;'>
                            <b><? echo $data_pg; ?></b>
                        </TD>
                    </TR>

            </TABLE><br>-->
            <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
                    <TR >
                        <TD class='inicio' style='text-align:center;'  colspan='4'>
                            EXTRATO
                        </td>
                    </tr>
                    <tr>
                        <TD class='titulo' style='padding:0px 5px;' width='120' >
                            Nº EXTRATO
                        </td>
                        <td class='conteudo' style='padding:0px 5px;' width='226' >
                            <a href='extrato_consulta_os.php?extrato=<? echo $extrato; ?>' ><? echo $extrato; ?></a>
                        </td>
                        <td class='titulo' style='padding:0px 5px;' width='120' >
                            DATA DO PAGAMENTO
                        </td>
                        <td class='conteudo' style='padding:0px 5px;' width='226' >
                            &nbsp;<b><? echo $data_pg; ?></b>
                        </TD>
                    </TR>

            </TABLE><br>

            <?

        }

    }

}// fim mostra número do Extrato
if($login_fabrica ==14 AND strlen($os) > 0){ // HD 65661
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_status_os.descricao,
                  tbl_os_status.observacao,
                  tbl_os_status.status_os
            FROM tbl_os_status
            JOIN tbl_os using(os)
            JOIN tbl_status_os using(status_os)
            WHERE os=$os
            AND   tbl_os.os_reincidente IS TRUE
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (13,19)
            ORDER BY os_status desc
            limit 1";
    //if($ip=='201.76.86.85') echo $sql2;
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' colspan='2' align='center'>Histórico</TD>";
        echo "</TR>";
        for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $status_os       = pg_fetch_result($res2,$i,status_os);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            echo "<TR>";
            echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status";
            if($status_os == 13) {
                echo "- Motivo: $observacao_status";
            }
            if($status_os == 19) {
                echo " da reincidência";
            }
            echo "</TD>";
            echo "</tr>";
        }
        echo "</TABLE></center>";
    }
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 65661
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_admin.login
            FROM tbl_os_status
            JOIN tbl_os using(os)
            JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
            WHERE os=$os
            AND   tbl_os.os_reincidente IS TRUE
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (132,19)
            AND status_os_ultimo = 19
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        $data        = pg_fetch_result($res2,0,'data');
        $login       = pg_fetch_result($res2,0,'login');

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' width='60%'>Admin(APROVOU REINCIDÊNCIA)</TD>";
        echo "<TD class='inicio'>Data</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='conteudo'>$login</TD>";
        echo "<TD class='conteudo'>$data</TD>";
        echo "</tr>";
        echo "</TABLE>";
    }
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_admin.login,
                  status_os
            FROM tbl_os_status
            JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
            WHERE os=$os
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (103,104)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        $data       = pg_fetch_result($res2,0,'data');
        $login      = pg_fetch_result($res2,0,'login');
        $status_os  = pg_fetch_result($res2,0,'status_os');
        $resposta   = ($status_os == 103) ? "APROVOU" : "REPROVOU";

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' width='60%'>Admin (".$resposta." NÚMERO DE SÉRIE)</TD>";
        echo "<TD class='inicio'>Data</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='conteudo'>$login</TD>";
        echo "<TD class='conteudo'>$data</TD>";
        echo "</tr>";
        echo "</TABLE>";
    }
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_admin.login
            FROM tbl_os_status
            JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
            WHERE os=$os
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (99,100)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        $data        = pg_fetch_result($res2,0,'data');
        $login       = pg_fetch_result($res2,0,'login');

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' width='60%'>Admin(APROVOU KM)</TD>";
        echo "<TD class='inicio'>Data</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='conteudo'>$login</TD>";
        echo "<TD class='conteudo'>$data</TD>";
        echo "</tr>";
        echo "</TABLE>";
    }
}

if($auditoria_unica == true || in_array($login_fabrica, array(30))){

    $sqlAuditoria = "SELECT tbl_auditoria_os.liberada,
            tbl_auditoria_os.cancelada,
            tbl_auditoria_os.reprovada,
            tbl_auditoria_os.auditoria_status
        FROM tbl_auditoria_os
            JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
            JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
        WHERE tbl_auditoria_os.os = $os ORDER BY data_input DESC";
    $resAuditoria = pg_query($con,$sqlAuditoria);

    if(pg_num_rows($resAuditoria) > 0){
        $liberada  = pg_fetch_result($resAuditoria, 0, "liberada");
        $cancelada = pg_fetch_result($resAuditoria, 0, "cancelada");
        $reprovada = pg_fetch_result($resAuditoria, 0, "reprovada");
        $auditoria_status = pg_fetch_result($resAuditoria, 0, "auditoria_status");

        if($liberada == "" && $cancelada == "" && $reprovada == ""){

            switch ($auditoria_status) {
                case 1:
                    $msg_auditoria = "OS com Carência de 90 Dias";
                    break;
                case 2:
                    $msg_auditoria = "OS em Auditoria de KM";
                    break;
                case 3:
                    $msg_auditoria = "OS em Auditoria de Produto";
                    break;
                case 4:
                    $msg_auditoria = "OS em Auditoria de Peças";
                    break;
                case 5:
                    $msg_auditoria = "OS em Auditoria de Número de Série";
                    break;
                case 6:
                    $msg_auditoria = "OS em Auditoria da Fábrica";
                    break;
            }
    ?>
        <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" >
            <tr class="msg_erro" >
                <td colspan="4"><?=$msg_auditoria?></td>
            </tr>
        </table>
    <?php }
    }
}

if($login_fabrica == 158 && $exportado == true){?>
<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" >
    <tr >
        <td colspan="4">
            Ordem de Serviço Exportada
        </td>
    </tr>
</table>


<? }

if (isset($novaTelaOs)) {
    if (isset($tipo_posto_multiplo)) {
        $sql = "
            SELECT tbl_tipo_posto.tipo_posto
            FROM tbl_posto_tipo_posto
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto
            WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
            AND tbl_posto_tipo_posto.posto = $posto
            AND tbl_tipo_posto.posto_interno IS TRUE
        ";
    } else {
        $sql = "
            SELECT tbl_tipo_posto.tipo_posto
            FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = $posto
            AND tbl_tipo_posto.posto_interno IS TRUE
        ";
    }

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $posto_interno = true;
    } else {
        $posto_interno = false;
    }
}

$os_externa = NULL;

if($reparoNaFabrica && !$posto_interno){
    $sql = "SELECT recolhimento from tbl_os_extra where os = {$os}";
    $resRec = pg_query($con,$sql);
    if(pg_num_rows($resRec) > 0){
        $aux_reparo_produto = pg_fetch_result($resRec,0,"recolhimento");

        if($aux_reparo_produto == "t"){
            $sql_os_ext = "SELECT os_numero FROM tbl_os WHERE os = $os";
            $qry_os_ext = pg_query($con, $sql_os_ext);

            $os_externa = pg_fetch_result($qry_os_ext, 0, 'os_numero');
        ?>
        <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" >
            <tr class="msg_erro" style='align:center;background-color:#FF4500' >
                <td colspan="4">Essa OS será reparada na Fábrica.</td>
            </tr>
        </table>
    <?php }
    }
}
?>

<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" >
    <tr class="msg_erro" >
        <td colspan="4">
            <?php echo $msg_erro; ?>
        </td>
    </tr>
</table>
<?php

if ($login_fabrica == 72) {
    $sql_obs_adic = "SELECT obs_adicionais FROM tbl_os_extra WHERE os = $os AND extrato = 0";
    $qry_obs_adic = pg_query($con, $sql_obs_adic);

    if (pg_num_rows($qry_obs_adic)) {
        $obs_adicionais = pg_fetch_result($qry_obs_adic, 0, 'obs_adicionais');

        if (!empty($obs_adicionais)) {
            echo "<center>
                <div style='font-family:verdana;align:center; margin-bottom: 5px;' align='center'>
                    <b style='font-size:14px;color:#e0123f'>$obs_adicionais</b>
                </div></center>";
        }
    }

}

if($login_fabrica == 30){
?>
<table width='700' border="0" cellspacing="1" cellpadding="0" align='center'>
    <tr>
        <td style="text-align:right;">
            <button id="histHelp">Histórico Help-Desk</button>
        </td>
    </tr>
</table>
<?php
}
?>
<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
        <TR>
            <TD class="inicio">&nbsp;&nbsp;POSTO</TD>
            <?
                if (strlen(trim($admin)) > 0 ) {
			if (in_array($login_fabrica, array(169, 170))) {
	                	echo "<TD class=\"inicio\" width=\"50%\">OS ADMIN: $admin_nome_completo</TD>";
			} else {
				echo "<TD class=\"inicio\" width=\"50%\">OS ADMIN: $admin</TD>";
			}
		}
            ?>
        </TR>
        <TR>
            <TD class="conteudo" <?php
                if ((strlen(trim($admin)) > 0) or ($login_fabrica == 6)){
                    echo "colspan = 2";
            }
	   ?>>
	<?php
            if( in_array($login_fabrica, array(11,172)) ){
		     	if($situacao_posto == "DESCREDENCIADO"){
				$textColor = "red";
			}
			echo "&nbsp; {$posto_codigo} - {$posto_nome} &nbsp &nbsp STATUS: <label style='color: ".$textColor."'>  &nbsp".$situacao_posto;
		}else{
			echo "&nbsp;{$posto_codigo} - {$posto_nome}";
		}
            ?></label>
            </TD>
        </TR>
        <? if ($login_fabrica == 6) {?>
        <TR>
            <TD class="conteudo">
            <? echo "&nbsp; $posto_endereco, $posto_num $posto_complemento - $posto_cidade/$posto_estado - CEP $posto_cep";?></TD>
            <TD class="conteudo"><?php echo "&nbsp;$posto_fone"; ?></td>
        </TR>
        <?
        }

        if (in_array($login_fabrica, array(169, 170))) {
            if (!empty($os_numero)) {
                $sqlOsNumero = "SELECT sua_os, os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os_numero}";
                $resOsNumero = pg_query($con, $sqlOsNumero);

                $os_numero_sua_os = pg_fetch_result($resOSNumero, 0, "sua_os");

                if (empty($os_numero_sua_os)) {
                    $os_numero_sua_os = $os_numero;
                }
            } else {
                $sqlOsNumero = "SELECT sua_os, os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os_numero = {$os}";
                $resOsNumero = pg_query($con, $sqlOsNumero);

                $os_numero_sua_os = pg_fetch_result($resOSNumero, 0, "sua_os");
                $os_numero        = pg_fetch_result($resOsNumero, 0, "os");

                if (empty($os_numero_sua_os)) {
                    $os_numero_sua_os = $os_numero;
                }
            }

            if (!empty($os_numero_sua_os)) {
            ?>
                <TR>
                    <TD class="inicio">&nbsp;&nbsp;OS DO CONJUNTO</TD>
                </TR>
                <TR>
                    <TD class="conteudo" colspan="<?=(!empty($admin)) ? 2 : 1?>">&nbsp;<a href="os_press.php?os=<?=$os_numero?>" target="_blank" ><?=$os_numero_sua_os?></a></TD>
                </TR>
            <?php
            }
        }

        if ($login_fabrica == 156 and $posto_interno == true) {
            $sql_os_ext = "SELECT os_numero FROM tbl_os WHERE os = $os";
            $qry_os_ext = pg_query($con, $sql_os_ext);

            if (pg_num_rows($qry_os_ext) > 0) {
                $os_externa = pg_fetch_result($qry_os_ext, 0, 'os_numero');

                $sql_posto_externo = "SELECT codigo_posto, nome
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                      AND tbl_posto_fabrica.fabrica = $login_fabrica
                    JOIN tbl_os ON tbl_os.posto = tbl_posto.posto
                    WHERE tbl_os.os = $os_externa";
                $qry_posto_externo = pg_query($con, $sql_posto_externo);

                if (pg_num_rows($qry_posto_externo) > 0) {
                    echo '
                        <TR>
                            <TD class="inicio">&nbsp;&nbsp;POSTO EXTERNO</TD>
                        </TR>
                        <TR>
                            <TD class="conteudo">&nbsp;' .
                                pg_fetch_result($qry_posto_externo, 0, 'codigo_posto') . ' - ' . pg_fetch_result($qry_posto_externo, 0, 'nome')
                                . ' &nbsp;&nbsp;&nbsp; OS: ' . $os_externa . '
                            </TD>
                        </TR>';
                }
            }
        }
        ?>
</TABLE>
<?
// }
?>

<? if($login_fabrica ==35 AND strlen($os) > 0){ // HD 56418
    $sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
                  descricao,
                  observacao
            FROM tbl_os_status
            JOIN tbl_status_os using(status_os)
            WHERE os=$os
            AND status_os IN (13,19,127)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela' >";
        echo "<TR>";
        echo "<TD class='inicio' colspan='2' align='center'>HISTÓRICO</TD>";
        echo "</TR>";
        for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            echo "<TR>";
            echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status</TD>";
            echo "</tr>";
        }
        echo "</TABLE>";
    }
}

if(in_array($login_fabrica,array(50,74)) AND strlen($os) > 0){ // HD 79844

        if($login_fabrica == 50){ //HD-3263360
            $sql2="SELECT CASE
                            WHEN tbl_os_extra.data_fabricacao IS NOT NULL THEN
                                TO_CHAR(tbl_os_extra.data_fabricacao, 'DD/MM/YYYY')
                            ELSE
                                TO_CHAR(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY')
                            END AS data_fabricacao
                    FROM tbl_os
                    JOIN tbl_numero_serie USING (serie,produto)
                    JOIN tbl_os_extra USING (os)
                    WHERE os=$os ";
            $res2=pg_query($con,$sql2);
        }else{
            $sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
                    FROM tbl_os
                    JOIN tbl_numero_serie USING (serie,produto)
                    WHERE os=$os ";
            $res2=pg_query($con,$sql2);
        }

        if(pg_num_rows($res2) > 0){
            $data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
    }else{
        $sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
            FROM tbl_os
            JOIN tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto and tbl_os.serie = substr(tbl_numero_serie.serie,1,length(tbl_numero_serie.serie) -1)
            WHERE os=$os
            AND   tbl_numero_serie.fabrica = $login_fabrica
            AND   tbl_os.fabrica = $login_fabrica
            AND data_fabricacao between '2013-07-25' and '2013-09-13'";
        $res2=pg_query($con,$sql2);
        if(pg_num_rows($res2) > 0){
            $data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
        }
    }
}

//HD 671828
if ($login_fabrica == 91  or $login_fabrica == 120 or $login_fabrica == 201 or $login_fabrica == 131) {
    $formato_data = ($login_fabrica == 131) ? "MM/YYYY" : "DD/MM/YYYY";
    $sql = "SELECT TO_CHAR(data_fabricacao, '$formato_data') AS data_fabricacao FROM tbl_os_extra WHERE os=$os";
    $res2 = pg_query($con, $sql);
    $data_fabricacao = pg_result($res2, 0, 'data_fabricacao');
}

//HD 399700
if($login_fabrica == 96 and !empty($motivo)){
    $linhas = 6;
}else if(in_array($login_fabrica, array(1))){
    $linhas = 5;
}
else{
    $linhas = 4;
}
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <tr>
        <td rowspan='<?= $linhas; ?>' class='conteudo' width='300'><center>OS FABRICANTE<br><br>&nbsp;<b>
            <FONT SIZE='6' COLOR='#C67700'>
<?
            if ($login_fabrica == 1) echo $posto_codigo;
            if (strlen($consumidor_revenda) > 0) {
                if($login_fabrica == 87) {
                    echo $sua_os ."</FONT>";
                } else if ($login_fabrica == 158) {
                    echo $sua_os ."</FONT> - " . $nome_atendimento;
                } else {
                            echo $sua_os ."</FONT> - ". $consumidor_revenda;
                }
            } else {
                echo $sua_os;
            }

            if($login_fabrica==3 OR $login_fabrica==86 or $multimarca == 't'){ echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";}

            if($login_fabrica==104){
                 $marca_nome = ($marca_nome == "DWT") ? $marca_nome : "OVD";
                 echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";
            }

            if($login_fabrica==117 or $login_fabrica == 128){
                 echo ($certificado_garantia <> "null" and strlen($certificado_garantia) > 0) ? "<BR><font color='#D81005' SIZE='4' ><strong>Garantia Estendida</strong></font>" : "";
            }

            if($login_fabrica==171 && $os_projeto == 't'){
                 echo "<BR><font color='#D81005' SIZE='2' ><strong>(OS Projeto)</strong></font>";
            }

            if($login_fabrica == 148 and $tipo_os == 17){
                echo '<br /><span style="color: #FF0000;">(OS Fora de Garantia)</span>';
            }

            if($login_fabrica==86){
                $sql = "SELECT os FROM tbl_cliente_garantia_estendida WHERE fabrica = $login_fabrica AND os = $os AND garantia_mes > 0";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    echo "<BR><font color='#D81005' SIZE='4' ><strong>Garantia Estendida</strong></font>";
                }
            }

            if($login_fabrica == 6 AND $recolhimento == "t"){
                 echo "<BR><font color='#D81005' SIZE='4' ><strong>Produto de Coleta</strong></font>";
            }

            if ($cortesia == "t") {
                echo "<BR><font color='#D81005' SIZE='4' ><strong>Cortesia</strong></font>";
            }

            if(strlen($sua_os_offline)>0){
            echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
            echo "<tr >";
            echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
            echo "</tr>";
            echo "</table>";
            }

            if (in_array($login_fabrica,array(35,151,157,169,170)) && !empty($os_posto)) { ?>
                <br/><?= (in_array($login_fabrica, array(169,170))) ? "OS SAP:" : "OS INTERNA:"; ?> <span style="font-size: 14px; color: #C67700;"><?= $os_posto ?></span>&nbsp;</td>
            <? } ?>
            </b>

            <?php

            if(in_array($login_fabrica,array(30,162))  && strlen($os) > 0){

                $sql_hd_chamado = "SELECT hd_chamado FROM tbl_os WHERE os = {$os}";
                $res_hd_chamado = pg_query($con, $sql_hd_chamado);

                $hd_chamado = pg_fetch_result($res_hd_chamado, 0, "hd_chamado");

                if(strlen($hd_chamado) > 0){

                    $sql_hd_classificacao = "SELECT
                                                tbl_hd_chamado.hd_classificacao,
                                                tbl_hd_chamado.analise AS orientacao_posto,
                                                tbl_hd_chamado_extra.numero_processo AS os_revenda,
                                                tbl_hd_classificacao.descricao          AS hd_classificacao_descricao
                                            FROM tbl_hd_chamado
                                            INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                                            JOIN    tbl_hd_classificacao USING(hd_classificacao)
                                            WHERE
                                                tbl_hd_chamado.hd_chamado = {$hd_chamado}";
                    $res_hd_classificacao = pg_query($con, $sql_hd_classificacao);

                    $hd_classificacao = pg_fetch_result($res_hd_classificacao, 0, "hd_classificacao");
                    $orientacao_posto = pg_fetch_result($res_hd_classificacao, 0, "orientacao_posto");
                    $os_revenda       = pg_fetch_result($res_hd_classificacao, 0, "os_revenda");
                    $hd_classificacao_descricao = pg_fetch_result($res_hd_classificacao, 0, "hd_classificacao_descricao");

                    if($hd_classificacao == 47){
                        echo "<br /> <strong style='color: #ff0000;'>OS Revenda: {$os_revenda}</strong>";
                    }

                    if ($login_fabrica == 162) {
?>
                        <br /> <strong style='color: #ff0000;'>Classificação Atendimento: <?=$hd_classificacao_descricao?></strong>
<?php
                    }
                }
            }

            if(in_array($login_fabrica, [167, 203])){
                if($suprimento == "t"){
                    echo "<br /> <strong style='color: #ff0000;'>SUPRIMENTO</strong>";
                }else{
                    echo "<br /> <strong style='color: #ff0000;'>PRODUTO PRINCIPAL</strong>";
                }
            }

            ?>

            </center>
        </td>
        <td class='inicio' height='15' colspan='4'>&nbsp;DATAS DA OS</td>
    </TR>

    <?php

    if (in_array($login_fabrica, array(101))) {

        $sql_data_entrada = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_data_entrada = pg_query($con, $sql_data_entrada);

        $campos_adicionais = pg_fetch_result($res_data_entrada, 0, "campos_adicionais");

        $data_entrada = "";

        if(strlen($campos_adicionais) > 0){
            $json_campos_adicionais = json_decode($campos_adicionais, true);
            if(isset($json_campos_adicionais["data_recebimento_produto"])){
                $data_entrada = $json_campos_adicionais["data_recebimento_produto"];
            }
        }

    ?>

    <tr>
        <td class='titulo' width='100' height='15'>ENTRADA&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_entrada?></td>
        <td class='titulo' width='100' height='15'>DIGITAÇÃO&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
    </tr>

    <tr>
        <td class='titulo' width='100' height='15'>ABERTURA&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
        <td class='titulo' width='100' height='15'>FECHAMENTO&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
    </tr>

    <tr>
        <td class="titulo"  height='15'>DATA DA NF&nbsp;</td>
        <td class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></td>
        <td class='titulo' width='100' height='15'>FINALIZADA&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>
    </tr>

    <tr>
        <td class='titulo' width='100' height='15'><center><b><?=strtoupper($status_checkpoint)?></b></center></td>
        <td class='titulo' width='100' height='15'>CONSERTADO &nbsp;</td>
        <td class='conteudo' width='100' height='15' colspan ='1'>
        <?php
            $sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto FROM tbl_os WHERE os = $os";
            $resdc = pg_query ($con,$sql_data_conserto);
            if (pg_num_rows ($resdc) > 0) {
                $data_conserto= pg_fetch_result ($resdc,0,data_conserto);
            }
            if(strlen($data_conserto)>0){
                if ($login_fabrica == 1) {
                    echo substr($data_conserto, 0,10);
                }else{
                    echo $data_conserto;
                }
            }else{
                echo "&nbsp;";
            }
        ?>
        </td>
        <td class='titulo' width='100' height='15'> FECHADO EM &nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;
            <?php
            if(strlen($data_fechamento) > 0 && strlen($data_abertura) > 0){

                if ($sinalizador == 18) {
                    echo "<font color='#FF0000'>FECHAMENTO<br>AUTOMÁTICO</font>";
                } else {
                    $sql_data = "SELECT SUM(data_fechamento - data_abertura) as final FROM tbl_os WHERE os = $os";
                    $resD = pg_query ($con,$sql_data);
                    if (pg_num_rows ($resD) > 0) {
                        $total_de_dias_do_conserto = pg_fetch_result ($resD, 0, 'final');
                    }

                    if($total_de_dias_do_conserto==0) echo 'no mesmo dia' ;
                    else echo $total_de_dias_do_conserto;
                    if($total_de_dias_do_conserto==1) echo ' dia' ;
                    if($total_de_dias_do_conserto>1)  echo ' dias' ;
                    if($login_fabrica == 1){
                        $sql_extrato = "SELECT to_char(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_envio
                                        FROM tbl_os_extra
                                        LEFT JOIN tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
                                        WHERE tbl_os_extra.os = $os LIMIT 1";
                        $res_extrato = pg_query($con,$sql_extrato);
                        if(pg_num_rows($res_extrato)>0){
                            $data_envio = pg_fetch_result ($res_extrato,0,data_envio);
                            echo " ";
                            echo "<acronym title='Data de envio para o Financeiro'>$data_envio</acronym>" ;
                        }
                    }
                }
            }else{
                echo "NÃO FINALIZADO";
            }
            ?>
        </td>
    </tr>

    <?php

    } else {

    ?>

    <TR>
        <? if ($login_fabrica != 158) { ?>
            <td class='titulo' width='100' height='15'>
                <?php echo getValorFabrica(['ABERTURA', 3 => 'DATA DE ENTRADA DO PRODUTO NO POSTO', 104 => 'RECEBIMENTO DO PRODUTO']);?>&nbsp;
            </td>
            <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
            <td class='titulo' width='100' height='15'>DIGITAÇÃO&nbsp;</td>
            <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
        <? } else { ?>
            <td class='titulo' width='100' height='15'>ABERTURA&nbsp;</td>
            <td class='conteudo' width='100' height='15'>&nbsp;<?= (strlen($data_hora_abertura) > 0) ? $data_hora_abertura : $data_digitacao ?></td>
            <td class='titulo' width='100' height='15'>DATA FABRICAÇÃO</td>
            <td class='conteudo' width='100' height='15'><?=$data_fabricacao?></td>
        <? } ?>
    </tr>

    <tr>
        <td class='titulo' width='100' height='15'><?=getValorFabrica(['FECHAMENTO', 3 => 'DATA DE RETIRADA DO PRODUTO PELO CONSUMIDOR'])?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
        <td class='titulo' width='100' height='15'>FINALIZADA&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>

    </tr>
    <tr>
        <TD class="titulo"  height='15'>DATA DA NF&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
        <td class='titulo' width='100' height='15'> FECHADO EM &nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;
        <?
        if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
            //HD 204146: Fechamento automático de OS
            if ($login_fabrica == 3) {
                $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                $res_sinalizador = pg_query($con, $sql);
                $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
            }

            if ($sinalizador == 18) {
                echo "<font color='#FF0000'>FECHAMENTO<br>AUTOMÁTICO</font>";
            }
            else {
                $sql_data = "SELECT SUM(data_fechamento - data_abertura) AS final FROM tbl_os WHERE os=$os";
                $resD = pg_query ($con,$sql_data);
                if (pg_num_rows ($resD) > 0) {
                    $total_de_dias_do_conserto = pg_fetch_result ($resD,0,'final');
                }

                if($total_de_dias_do_conserto==0) echo 'no mesmo dia' ;
                else echo $total_de_dias_do_conserto;
                if($total_de_dias_do_conserto==1) echo ' dia' ;
                if($total_de_dias_do_conserto>1)  echo ' dias' ;
                if($login_fabrica == 1){
                    $sql_extrato = "SELECT to_char(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_envio
                                    FROM tbl_os_extra
                                    LEFT JOIN tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
                                    WHERE tbl_os_extra.os = $os LIMIT 1";
                    $res_extrato = pg_query($con,$sql_extrato);
                    if(pg_num_rows($res_extrato)>0){
                        $data_envio = pg_fetch_result ($res_extrato,0,data_envio);
                        echo " ";
                        echo "<acronym title='Data de envio para o Financeiro'>$data_envio</acronym>" ;
                    }
                }
            }
        }else{
            echo "NÃO FINALIZADO";
        }
        ?>
        </td>
    </tr>

    <? if($login_fabrica == 1){ ?>

        <tr>
            <td class='titulo' width='100' height='15'>CONSERTADO&nbsp; </td>
            <td class='conteudo' width='100' height='15' colspan ='1' id='consertado'>&nbsp;
            <?
            $sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto
                                    FROM tbl_os
                                    WHERE os=$os";
            $resdc = pg_query ($con,$sql_data_conserto);
            if (pg_num_rows ($resdc) > 0) {
                $data_conserto= pg_fetch_result ($resdc,0,data_conserto);
            }
            if(strlen($data_conserto)>0){
                if ($login_fabrica == 1) {
                    echo substr($data_conserto, 0,10);
                }else{
                    echo $data_conserto;
                }
            } else {
                echo "&nbsp;";
            }
            echo "</td>";

            $titulo_extrato = "Extrato";

            $sql = "SELECT tbl_extrato.protocolo FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os";
            $res2 = pg_query ($con,$sql);
            $protocolo = @pg_fetch_result($res2, 0, 'protocolo');

            echo "<td class='titulo' width='100'height='15'>{$titulo_extrato}</TD>";
            echo "<td class='conteudo' width='100' height='15'>{$protocolo}</tr>";
        echo "</tr>";
    }

    if ($usaDataConserto) { ?>
        <tr>
	<td class='titulo' width='100' height='15'><center><b><?=strtoupper($status_checkpoint)?></b></center></td>
        <td class='titulo' width='100' height='15'>CONSERTADO &nbsp;</td>
        <td class='conteudo' width='100' height='15' colspan ='1'>
        <?
                $sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' )   as data_conserto FROM tbl_os WHERE os=$os";
                $resdc = pg_query ($con,$sql_data_conserto);
                if (pg_num_rows ($resdc) > 0) {
                    $data_conserto= pg_fetch_result ($resdc,0,data_conserto);
                }
                if(strlen($data_conserto)>0){
                    if ($login_fabrica == 1) {
                        echo substr($data_conserto, 0,10);
                    }else{
                        echo $data_conserto;
                    }
                }else{
                    echo "&nbsp;";
                }
            echo "</td>";

            if (in_array($login_fabrica, array(30, 50))) {
                $titulo_extrato = "FECHADO POR";
                $sql_es = "SELECT UPPER(tbl_os_extra.obs_fechamento) FROM tbl_os_extra WHERE os = $os;";
                $res_es = pg_query($con,$sql_es);

                $protocolo = pg_fetch_result($res_es, 0, 0);
            }

            if ($login_fabrica == 156) {
                $titulo_extrato = 'DATA DA LIBERAÇÃO DO PRODUTO';
                $protocolo = $data_nf_saida;

                if (!empty($os_externa) and !$posto_interno) {
                    $sql_data_liberacao_produto = "SELECT TO_CHAR(tbl_os.data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida FROM tbl_os WHERE os = $os_externa";
                    $qry_data_liberacao_produto = pg_query($con, $sql_data_liberacao_produto);

                    $protocolo = pg_fetch_result($qry_data_liberacao_produto, 0, 'data_nf_saida');
                }
            }

            if(in_array($login_fabrica, [167, 203])){
                if($nome_atendimento == "Garantia Recusada" AND strlen(trim($os_numero)) > 0){
                    $texto_td = "OS Orçamento";
                }else if($nome_atendimento == "Orçamento" AND strlen(trim($os_numero)) > 0){
                    $texto_td = "OS Garantia Recusada";
                }

                echo "<td class='titulo' width='100'height='15'>{$texto_td} </td>";
                echo "<td class='conteudo' width='100' height='15'>{$os_numero}</tr>";
            }else{
                echo "<td class='titulo' width='100'height='15'>{$titulo_extrato}</TD>";
                echo "<td class='conteudo' width='100' height='15'>{$protocolo}</tr>";
            }



            // echo "<td class='titulo' width='100'height='15'>&nbsp;</td>";
            // echo "<td class='conteudo' width='100' height='15'></tr>";
        ?>
        <? } ?>
    <?
    if(strlen($motivo_atraso)>0){

        if($login_fabrica == 52){

            $sql = "SELECT descricao FROM tbl_motivo_atraso_fechamento WHERE motivo_atraso_fechamento = {$motivo_atraso}";
            $res = pg_query($con, $sql);

            $motivo_atraso = pg_fetch_result($res, 0, "descricao");

        }

    ?>
        <tr><td colspan='5' bgcolor='#FF0000' size='2'><b><font color='#FFFF00'>Motivo do atraso: <?=$motivo_atraso?></font></b></td></tr>
    <?
    }
    if(strlen($obs_reincidencia)>0){
    ?>
        <tr><td colspan='5' bgcolor='#FF0000' size='2'><b><font color='#FFFF00'>Justificativa: <?=$obs_reincidencia?></font></b></td></tr>
    <?}

    if(strlen($motivo_reincidencia_desc)>0 and $login_fabrica == 52){
    ?>
        <tr><td colspan='5' bgcolor='#FF0000' size='2'><b><font color='#FFFF00'>Motivo da Reincidência: <?=$motivo_reincidencia_desc?></font></b></td></tr>
    <?}?>

    <? if($login_fabrica == 96){ ?>
        <tr>
            <td class='titulo'>MOTIVO &nbsp;</td>
            <td class='conteudo' colspan='4'>&nbsp;<?php echo $motivo; ?></td>
        </tr>
    <? } ?>

    <?php } ?>
</table>
<?
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
// adicionado para ibbl (90) HD#316365
if(in_array($login_fabrica,array(19,90,115,116,117,120,201,140,141,144))){

    if(strlen($tipo_os) > 0){
        $sqll = "SELECT descricao FROM tbl_tipo_os WHERE tipo_os = {$tipo_os};";
        $ress = pg_query($con,$sqll);
        $tipo_os_descricao = pg_fetch_result($ress,0,0);
    } ?>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class="titulo"  height='15' width='90'>ATENDIMENTO&nbsp;</td>
            <td class="conteudo" height='15'>&nbsp;<?= $tipo_atendimento.' - '.$nome_atendimento ?></td>
            <? if(!in_array($login_fabrica,array(90,115,116,117,120,201,123,124,125,126,127,128,129,131,134,140,141,143,144))) { ?>
                <td class="titulo"  height='15' width='90'>MOTIVO&nbsp;</td>
                <td class="conteudo" height='15'>&nbsp;<? echo $tipo_os_descricao; ?></td>
                <? if ($tecnico_nome) { ?>
                    <td class="titulo" height='15'width='90'>NOME DO TÉCNICO&nbsp;</td>
                    <td class="conteudo" height='15'>&nbsp;<?= $tecnico_nome ?></td>
                <? } ?>
            <? } else if (in_array($login_fabrica,array(90))) { ?>
                <td class="titulo"  height='15' width='90'>Recolhimento</td>
                <td class="conteudo" height='15'>&nbsp;<? echo $recolhimento == 'f' ? 'NÃO' : 'SIM'; ?></td>
                <? if (strlen($reoperacao_gas) > 0 && $login_fabrica == 90) { ?>
                    <td class="titulo" height="15" width="90">Reoperação de Gás</td>
                    <td class="conteudo" height="15">&nbsp;<?= $reoperacao_gas == 'f' ? "NÃO" : "SIM";?></td>
                <? }
            } ?>
        </td>
    </table>
<? } // FIM DA PARTE EXCLUSIVA DA LORENZETTI

// CAMPOS ADICIONAIS SOMENTE PARA BOSCH
if (in_array($login_fabrica, array(20,96))) {
    if ($tipo_atendimento == 13 && $tipo_troca == 1) {
        $tipo_atendimento = 00;
        $nome_atendimento = "Troca em Cortesia Comercial";
    }
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class="titulo"  height='15' width='90'>ATENDIMENTO&nbsp;</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_atendimento.' - '.$nome_atendimento ?></TD>

    <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){?>
        <TD class="titulo"  height='15' width='90'>MOTIVO ORDEM&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $motivo_ordem ?></TD>
    <?php } ?>

    <?if( $tecnico_nome){?>
    <TD class="titulo" height='15'width='90'>NOME DO TÉCNICO&nbsp;</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
    <?}?>
    <?if($tipo_atendimento=='15' or $tipo_atendimento=='16'){?>
            <TD class="titulo"  height='15' width='90'>AUTORIZAÇÃO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $autorizacao_cortesia ?></TD>
            <TD class="titulo"  height='15' width='90'>PROMOTOR&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $promotor_treinamento ?></TD>
    <?}?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA BOSCH
?>
<?
if ($login_fabrica == 15){
if($serie[0]=="9"){

    $sqlx = "select os from tbl_os where serie_reoperado = '$serie' AND posto = $posto AND fabrica = $login_fabrica";
    $xres = pg_query ($con,$sqlx);

    if(pg_num_rows($xres)>0){
        $xos = trim(pg_fetch_result($xres,0,$xos));
    }
    $serie = "<A HREF='os_press.php?os=$xos' target='_blank'>$serie</A>";
}
}
?>
<? if(in_array($login_fabrica, array(50,52,87,94)) || isset($novaTelaOs)){?>
    <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
        <tr>
            <? if ($login_fabrica == 87) { ?>

            <td class="titulo" height='15' >TIPO ATENDIMENTO&nbsp;</td>
            <td class="conteudo"  height='15' >&nbsp;
            <?php
                if(intval($tipo_atendimento) > 0){
                    $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                    $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

			$desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');

                    echo $desc_tipo_atendimento;
                }
            ?>
            </td>
            <td class="titulo" height='15' >HORAS TRABALHADAS&nbsp;</td>
            <td class="conteudo" height='15' width='40' >&nbsp;<? echo $hora_tecnica; ?></td>
            <td class="titulo" height='15' >HORAS TÉCNICAS&nbsp;</td>
            <td class="conteudo" height='15'width='40'  >&nbsp;<? echo $qtde_horas; ?></td>
            <? }

            if (isset($novaTelaOs)) {
                if (in_array($login_fabrica, array(156,158,162))) { ?>
                    <td class="titulo" height="15">TÉCNICO</td>
                    <td class="conteudo" width="170" height="15"><?= $tecnico_nome; ?></td>
                <? } ?>
                <td class="titulo" height='15'>TIPO ATENDIMENTO&nbsp;</td>
                <td class="conteudo"  height='15'>&nbsp;
                    <? if (intval($tipo_atendimento) > 0) {
                        $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
                        $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                        $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
                        echo $desc_tipo_atendimento;
                    } ?>
                </td>
            <? }

            if (!in_array($login_fabrica, array(147,150))) { ?>
                <? if($login_fabrica == 153) {
                    $descricao_tipo_atendimento =  pg_fetch_result($res_tipo_atendimento,0,'descricao');
                    if ($descricao_tipo_atendimento == "Laudo Zero Hora") { ?>
                        <td class="titulo" height='15' width='100' >Código do Lacre</td>
                        <td class="conteudo" height='15' nowrap>&nbsp;<? echo $codigo_lacre; ?></td>
                    <? }
                } else { ?>
                    <td class="titulo" height='15' width='100' >QUANTIDADE DE KM</td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $qtde_km; ?> KM</td>
                <? }
                if ($login_fabrica == 171) {
                ?>
                    <td class="titulo" height='15' width='100' >KM IDA</td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $os_campos_adicionais['qtde_km_ida']; ?> KM</td>
                    <td class="titulo" height='15' width='100' >KM VOLTA</td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $os_campos_adicionais['qtde_km_volta']; ?> KM</td>
                    <td class="titulo" height='15' width='100' >QUANTIDADE DE VISITAS</td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $qtde_diaria; ?></td>
                <?
                }

                if($login_fabrica == 52){
                    $sql = "SELECT pedagio FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
                    $res = pg_query($con, $sql);
                    $pedagio = (strlen(pg_fetch_result($res, 0, "pedagio")) == 0) ? 0 : pg_fetch_result($res, 0, "pedagio"); ?>
                    <td class="titulo" height='15' width='100' >Pedágio</td>
                    <td class="conteudo" height='15' nowrap>R$ <?php echo number_format($pedagio, 2, ",", "."); ?></td>
                <? }

                if (in_array($login_fabrica, array(142,156,169,170))) { ?>
                    <td class="titulo" height='15' width='100' >VISITAS&nbsp;</td>
                    <td class="conteudo" height='15' >&nbsp;<?= $qtde_diaria; ?></td>
                <? }

                if (in_array($login_fabrica, array(164)) && $posto_interno == true) {

                    $sql_destinacao = "SELECT segmento_atuacao FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
                    $res_destinacao = pg_query($con, $sql_destinacao);

                    if(pg_num_rows($res_destinacao) > 0){

                        $segmento_atuacao = pg_fetch_result($res_destinacao, 0, "segmento_atuacao");

                        if(strlen($segmento_atuacao) > 0){

                            $sql_desc = "SELECT descricao FROM tbl_segmento_atuacao WHERE segmento_atuacao = {$segmento_atuacao} AND fabrica = {$login_fabrica}";
                            $res_desc = pg_query($con, $sql_desc);

                            if(pg_num_rows($res_desc) > 0){
                                $destinacao = pg_fetch_result($res_desc, 0, "descricao");
                            }

                        }

                    } ?>
                    <td class="titulo" height='15' width='100' >Destinação</td>
                    <td class="conteudo" height='15' >&nbsp;<? echo $destinacao; ?></td>
                <? }

                if ($login_fabrica == 152) { ?>
                    <td class="titulo" height='15' width='100' >TEMPO DE DESLOCAMENTO (HORAS)&nbsp;</td>
                    <td class="conteudo" height='15' >&nbsp;<? echo $tempo_deslocamento; ?></td>
                <? }
            }

            if (!isset($novaTelaOs)) {
                $sql = "SELECT tbl_solucao.descricao FROM tbl_solucao INNER JOIN tbl_os ON tbl_os.solucao_os = tbl_solucao.solucao WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $solucao_descricao = pg_fetch_result($res, 0, "descricao");
                } ?>
                <td class="titulo" height='15' width='100' >SOLUÇÃO&nbsp;</td>
                <td class="conteudo" height='15' >&nbsp;<? echo $solucao_descricao; ?></td>
            <? } ?>
        </tr>

        <? if (in_array($login_fabrica, array(169,170)) && strlen($motivo_visita) > 0) { ?>
            <tr>
                <td class="titulo">MOTIVO DA(S) VISITA(S)</td>
                <td class="conteudo" colspan="5"><?= $motivo_visita; ?></td>
            </tr>
        <? }

        if (in_array($login_fabrica, array(156))) {
            if (count($os_campos_adicionais) > 0) {
                $nf_envio       = $os_campos_adicionais["nf_envio"];
                $data_nf_envio  = $os_campos_adicionais["data_nf_envio"];
                $valor_nf_envio = $os_campos_adicionais["valor_nf_envio"];

                $nf_mo       = $os_campos_adicionais["nota_fiscal_mo"];
                $data_nf_mo  = $os_campos_adicionais["data_nota_fiscal_mo"];
                $valor_nf_mo = $os_campos_adicionais["valor_nota_fiscal_mo"];

                $nf_peca       = $os_campos_adicionais["nota_fiscal_peca"];
                $data_nf_peca  = $os_campos_adicionais["data_nota_fiscal_peca"];
                $valor_nf_peca = $os_campos_adicionais["valor_nota_fiscal_peca"];

                $nf_retorno       = $os_campos_adicionais["nf_retorno"];
                $data_nf_retorno  = $os_campos_adicionais["data_nf_retorno"];
                $valor_nf_retorno = $os_campos_adicionais["valor_nf_retorno"];
            }

            if ($posto_interno == true) {
            ?>
                <tr>
                    <td class="titulo">NATUREZA DE OPERAÇÃO</td>
                    <td class="conteudo"><?=$natureza_operacao?></td>
                    <td class="titulo">TIPO DE OS</td>
                    <td class="conteudo"><?=$tipo_os_descricao?></td>
                </tr>
                <tr>
                    <td class="titulo" height='15' width='100' >NF MO</td>
                    <td class="conteudo" height='15' ><?=$nf_mo?></td>

                    <td class="titulo" height='15' width='100' >Data NF MO</td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_mo?></td>

                    <td class="titulo" height='15' width='100' >Valor NF MO</td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_mo?></td>
                </tr>
                <tr>
                    <td class="titulo" height='15' width='100' >NF Peça</td>
                    <td class="conteudo" height='15' ><?=$nf_peca?></td>

                    <td class="titulo" height='15' width='100' >Data NF Peça</td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_peca?></td>

                    <td class="titulo" height='15' width='100' >Valor NF Peça</td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_peca?></td>
                </tr>
                <tr>
                    <td class="titulo" height='15' width='100' >NF Retorno</td>
                    <td class="conteudo" height='15' ><?=$nf_retorno?></td>

                    <td class="titulo" height='15' width='100' >Data NF Retorno</td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_retorno?></td>

                    <td class="titulo" height='15' width='100' >Valor NF Retorno</td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_retorno?></td>
                </tr>
            <?php
            }

            if ($posto_interno == true || $aux_reparo_produto == "t") {
            ?>
                <tr>
                    <td class="titulo" height='15' width='100' >NF <?=($posto_interno == true) ? "Recebimento" : "Envio"?></td>
                    <td class="conteudo" height='15' ><?=$nf_envio?></td>

                    <td class="titulo" height='15' width='100' >Data NF <?=($posto_interno == true) ? "Recebimento" : "Envio"?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_envio?></td>

                    <td class="titulo" height='15' width='100' >Valor NF <?=($posto_interno == true) ? "Recebimento" : "Envio"?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_envio?></td>
                </tr>
        <? }
        }
        if ($login_fabrica == 158) { ?>
            <tr>
                <td class="titulo" >INÍCIO DO ATENDIMENTO</td>
                <td class="conteudo" ><?=$inicio_atendimento?></td>
                <td class="titulo" >FIM DO ATENDIMENTO</td>
                <td class="conteudo" ><?=$fim_atendimento?></td>
                <td class="titulo" >AMPERAGEM</td>
                <td class="conteudo" ><?=$amperagem?></td>
            </tr>
        <? } ?>
    </table>
<? }

if (in_array($login_fabrica, array(169,170))) {
    $obs_adicionais = json_decode($obs_adicionais, true);
    $xobs_adicionais = $obs_adicionais['solicitacao_postagem_posto'];

    if(strlen(trim($xobs_adicionais)) > 0 OR strlen(trim($numero_pac)) > 0){
?>
        <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
            <tr class='inicio'>
                <td>INFORMAÇÕES SOLICITAÇÕES LGR POSTO</td>
            </tr>
            <tr>
                <td class='titulo' colspan="2">NÚMERO DE POSTAGEM</td>
                <td class='conteudo'><?=$xobs_adicionais; ?></td>
                <td class='titulo' >CÓDIGO DE RASTREIO</td>
                <td class='conteudo'><?=$numero_pac; ?></td>
            </tr>
        </table>
<?php
    }
}

if (in_array($login_fabrica, array(42,124,141,144)) && strlen($tipo_atendimento) > 0) { //hd_chamado=2704100 adicionada fabrica 124?>
        <TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' align="center">
            <tr>
                <td class='inicio' height='20' width='150'>
                    Tipo Atendimento
                </td>
                <TD class='conteudo' height='20'>
                    <?=$nome_atendimento?>
                </TD>
                <?php
                if (in_array($login_fabrica, array(138,141,144))) {
                ?>
                    <td class='inicio' height='20' width='150'>
                        Quantidade de KM
                    </td>
                    <TD class='conteudo' height='20'>
                        <?=$qtde_km?> KM
                    </TD>
                <?php
                }

                if (in_array($login_fabrica, array(141,144))) {
                    $select_os_tipo_posto = "SELECT tbl_posto_fabrica.tipo_posto
                                               FROM tbl_os
                                               JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                              WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
                    $res_os_tipo_posto = pg_query($con, $select_os_tipo_posto);

                    if (pg_num_rows($res_os_tipo_posto) > 0) {
                        $os_tipo_posto = pg_fetch_result($res_os_tipo_posto, 0, "tipo_posto");
                    }
                }

                if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
                    if (count($os_campos_adicionais) > 0) {
                        $os_remanufatura      = $os_campos_adicionais["os_remanufatura"];
                    }
                    ?>
                    <td class='inicio' height='20' width='100' >Remanufatura</td>
                    <td class='conteudo' height='20' ><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
                <?php
                }
                ?>
            </tr>
        </TABLE>
    <? }
if (in_array($login_fabrica, array(30,50,162))) {
    $sqlV = "SELECT to_char(data,'DD/MM/YYYY') AS data_agendamento FROM tbl_os_visita WHERE os = {$os} ORDER BY data";
    $resV = pg_query($con,$sqlV);

    $sqlBuscaAdmin = "
        SELECT  tbl_os.cliente_admin                                    AS cliente_admin        ,
                tbl_cliente_admin.nome                                  AS cliente_admin_nome   ,
                TO_CHAR(tbl_os.visita_agendada,'DD/MM/YYYY')            AS data_agendamento     ,
                tbl_os.observacao                                       AS cliente_admin_obs    ,
                tbl_hd_chamado_extra.array_campos_adicionais
        FROM    tbl_os
        JOIN    tbl_os_extra USING(os)
        JOIN    tbl_hd_chamado_extra USING(os)
        LEFT JOIN    tbl_cliente_admin USING(cliente_admin)
        WHERE   tbl_os.os = $os
    ";
    $resBuscaAdmin = pg_query($con,$sqlBuscaAdmin);
    if(pg_num_rows($resBuscaAdmin) > 0){
        $cliente_admin      = pg_fetch_result($resBuscaAdmin,0,cliente_admin);
        $cliente_admin_nome = pg_fetch_result($resBuscaAdmin,0,cliente_admin_nome);
        $data_agendamento   = pg_fetch_result($resBuscaAdmin,0,data_agendamento);
        $cliente_admin_obs  = pg_fetch_result($resBuscaAdmin,0,cliente_admin_obs);
        $data_limite        = pg_fetch_result($resBuscaAdmin,0,array_campos_adicionais);
        $data_limite        = json_decode($data_limite);

        if (!empty($cliente_admin) && $login_fabrica != 50) {
?>
            <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
                <tr>
                    <td class='inicio' height='15' colspan="6">&nbsp;ATENDIMENTO CENTRALIZADO&nbsp;</td>
                </tr>
                <tr>
                    <td class="titulo">CLIENTE</td>
                    <td class="conteudo"><?=$cliente_admin_nome?></td>
                    <td class="titulo">DATA LIMITE</td>
                    <td class="conteudo" colspan="3"><?=$data_limite->data_limite?></td>
                </tr>

<?php
                    if(pg_num_rows($resV) > 0){
                        for($j = 0; $j < pg_num_rows($resV); $j++){
                            $data_agendamento = pg_fetch_result($resV, $j, 'data_agendamento');
                            $ln = $j + 1;
?>
                            <tr>
                                <td class="titulo" nowrap>DATA AGENDAMENTO <?=$ln?></td>
                                <td class="conteudo" colspan="7"><?=$data_agendamento?></td>
                            </tr>
<?php
                        }
                    }
?>
                <tr>
                    <td class="titulo">OBSERVAÇÃO DO CALLCENTER</td>
                    <td class="conteudo" colspan="5"><?=$cliente_admin_obs?></td>
                </tr>
            </table>
<?php
        } else {
?>
            <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>

<?php
                    if(pg_num_rows($resV) > 0){
                        for($j = 0; $j < pg_num_rows($resV); $j++){
                            $data_agendamento = pg_fetch_result($resV, $j, 'data_agendamento');
                            $ln = $j + 1;
?>
                            <tr>
                                <td class="titulo" nowrap>DATA AGENDAMENTO <?=$ln?></td>
                                <td class="conteudo" colspan="7"><?=$data_agendamento?></td>
                            </tr>
<?php
                        }
                    }
?>
                <tr>
                    <td class="titulo">OBSERVAÇÃO DO CALLCENTER</td>
                    <td class="conteudo" colspan="5"><?=$cliente_admin_obs?></td>
                </tr>
            </table>
<?
        }
    }
}

if (in_array($login_fabrica, array(158))) {
    $sqlInteg = "SELECT *
                        FROM tbl_hd_chamado_cockpit
                        LEFT JOIN tbl_routine_schedule_log USING(routine_schedule_log)
                        LEFT JOIN tbl_hd_chamado_cockpit_prioridade USING(hd_chamado_cockpit_prioridade)
                        WHERE tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
                        AND tbl_hd_chamado_cockpit.hd_chamado = (SELECT hd_chamado
                                                        FROM tbl_os
                                                        WHERE os = {$os});";
    $resInteg = pg_query($con, $sqlInteg);

    if (pg_num_rows($resInteg) > 0) {
        $dadosIntegracao = pg_fetch_all($resInteg);
        $dadosJSON = json_decode(stripslashes($dadosIntegracao[0]['dados']), true); ?>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <thead class="Tabela inicio">
                <th colspan="10">INFORMAÇÕES DA INTEGRAÇÃO</th>
            </thead>
            <tbody>
                <tr>
                    <td class="titulo2" style="text-align: right;">ARQUIVO:</td>
                    <td class="conteudo"><?= $dadosIntegracao[0]['file_name']; ?></td>
                    <td class="titulo2" style="text-align: right;">CENTRO DISTRIBUIDOR:</td>
                    <td class="conteudo"><?= $dadosJSON['centroDistribuidor']; ?></td>
                </tr>
                <tr>
                    <td class="titulo2" style="text-align: right;">OS KOF:</td>
                    <td class="conteudo"><?= $dadosJSON['osKof']; ?></td>
                    <td class="titulo2" style="text-align: right;">DATA ABERTURA KOF:</td>
                    <td class="conteudo"><?= $dadosJSON['dataAbertura']; ?></td>
                </tr>
                <tr>
                    <td class="titulo2" style="text-align: right;">DATA PROCESSAMENTO:</td>
                    <td class="conteudo"><?= date("d/m/Y H:i:s", strtotime($dadosIntegracao[0]['create_at'])); ?></td>
                    <td class="titulo2" style="text-align: right;">NÚMERO DA MATRICULA DO CLIENTE:</td>
                    <td class="conteudo"><?= $dadosJSON['idCliente']; ?></td>
                </tr>
                <tr>
                    <td class="titulo2" style="text-align: right;">COMENTARIO KOF:</td>
                    <td class="conteudo"><?= $dadosJSON['comentario']; ?></td>
                    <td class="titulo2" style="text-align: right;"></td>
                    <td class="conteudo"></td>
                </tr>
            </tbody>
        </table>
    <? }
    if (!empty($os_campos_adicionais['unidadeNegocio'])) {
        $unidade_negocio = $os_campos_adicionais['unidadeNegocio'];
        $sql = "
            SELECT DISTINCT
                tbl_cidade.nome AS cidade
            FROM tbl_distribuidor_sla
            JOIN tbl_cidade USING(cidade)
            WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica}
            AND tbl_distribuidor_sla.unidade_negocio = '{$unidade_negocio}';
        ";
        $res = pg_query($con, $sql);

        if ($unidade_negocio == "6300") {
            $unidade_negocio_cidade = $unidade_negocio . " - Bebidas Fruki";
        } elseif ($unidade_negocio == "6500") {
            $unidade_negocio_cidade = $unidade_negocio . " - Mato Grosso do Sul";
        } elseif ($unidade_negocio == "6600") {
            $unidade_negocio_cidade = $unidade_negocio . " - Rio de Janeiro";
        } elseif ($unidade_negocio == "6700") {
            $unidade_negocio_cidade = $unidade_negocio . " - Danone";
        } else {
            $unidade_negocio_cidade = $unidade_negocio . " - " . pg_fetch_result($res, 0, cidade);
        }

        ?>
        <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
            <td class='titulo' height='15' width='136'>UNIDADE DE NEGÓCIO</td>
            <td class="conteudo"><?= strtoupper($unidade_negocio_cidade); ?></td>
        </table>
    <? }
} ?>
<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <tr>
        <td class='inicio' height='15' colspan='4'>&nbsp;INFORMAÇÕES DO PRODUTO&nbsp;</td>
    </tr>
    <tr>
        <?if ($login_fabrica == 96) { ?>
            <TD class="titulo" height='15' width='90'>MODELO&nbsp;</ width='100'TD>
            <TD class="conteudo" height='15' >&nbsp;<? echo $produto_modelo ?></TD>
            <TD class="titulo" rowspan='2' height='15' width='90'>DESCRIÇÃO&nbsp;</TD>
            <TD class="conteudo" rowspan='2' height='15' >&nbsp;<? echo $produto_descricao ?></TD>
            <TD class="titulo" rowspan='2' height='15' width='100'>NÚMERO DE SÉRIE&nbsp;</TD>
            <TD class="conteudo" rowspan='2' height='15' width='100'>&nbsp;<? echo $serie ?></TD>
        </tr>
        <tr>
            <TD class="titulo" height='15' width='90'>REFERÊNCIA&nbsp;</TD>
            <TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?> </TD>
        </tr>
        <tr>
        <?} else {

            if (in_array($login_fabrica, array(138,143))) {

                $sql_cont = "SELECT COUNT(*) AS cont FROM tbl_os_produto WHERE os = {$os}";
                $res_cont = pg_query($con, $sql_cont);

                $num_os = pg_fetch_result($res_cont, 0, 'cont');

                if($num_os > 1){
                    $cond_order_by = "ORDER BY tbl_os_produto.os_produto ASC LIMIT 1";
                }

                $sql_produto = "
                    SELECT
                        tbl_produto.referencia,
                        tbl_produto.produto,
                        tbl_produto.descricao,
                        tbl_os_produto.serie
                    FROM tbl_produto
                    JOIN tbl_os_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_os_produto.os = {$os}
                    {$cond_order_by};
                ";

                $res_produto = pg_query($con, $sql_produto);
                $produto_referencia = pg_fetch_result($res_produto, 0, 'referencia');
                $produto_descricao = pg_fetch_result($res_produto, 0, 'descricao');
                $serie = pg_fetch_result($res_produto, 0, 'serie');
                $id_produto = pg_fetch_result($res_produto, 0, 'produto'); //HD-3158226

                if(in_array($login_fabrica,array(138)) ){
                    if(strlen($serie) > 0){
                        $sql = "SELECT  tbl_numero_serie.cnpj,
                                                tbl_revenda.nome,
                                                to_char(data_venda,'DD/MM/YYYY') AS data_venda
                                    FROM tbl_numero_serie
                                    JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_numero_serie.produto
                                    LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_numero_serie.cnpj
                                    WHERE tbl_os_produto.os = {$os}
                                    AND tbl_numero_serie.fabrica = {$login_fabrica}
                                    AND tbl_numero_serie.serie = '{$serie}'";
                        $resS = pg_query($con,$sql);

                        $data_venda_serie   = pg_fetch_result($resS,0,'data_venda');
                        $cnpj_serie         = pg_fetch_result($resS,0,'cnpj');
                        $nome_revenda_serie = pg_fetch_result($resS,0,'nome');
                    }
                }
            }

        ?>
        <TD class="titulo" height='15' width='90'>REFERÊNCIA&nbsp;</TD>
        <TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
        <TD class="titulo" height='15' width='90'>DESCRIÇÃO&nbsp;</TD>
        <TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
        <? if (!in_array($login_fabrica, array(145,127,162,171))){ // HD-2296739 ?>
            <td class="titulo" height='15' width='90'>
                <? if($login_fabrica==35){
                    echo "PO#";
                }else{
                    echo ($login_fabrica == 137 or $login_fabrica == 160) ? "NÚMERO DE LOTE" : "NÚMERO DE SÉRIE";
                }?>
            </td>
            <td class="conteudo" height='15' <?=($login_fabrica == 148) ? "colspan='3'" : ""?>>
                <? if (in_array($login_fabrica, array(156,161)) && $sem_ns == "t") {
                        $serie = "Sem número de série";
                }
                echo $serie; ?>
            </td>

            <?php if(in_array($login_fabrica, [167, 203]) ){
                if( strstr($descricao_familia, "Impressora") == true || strstr($descricao_familia, "Multifunciona") == true ){
            ?>
                    <td class='titulo' height='15' width='50'>CONTADOR</TD>
                    <td class="conteudo">&nbsp;<? echo $contador ?></td>
            <?php
                }
            }
            ?>
            <?php if($login_fabrica == 160){?>
                <td class="titulo" height='15' width='90'>Versão do Produto</td>
                <td class="conteudo" height='15'><?= $type?></td>
            <?php }?>
            <?
            if ($login_fabrica == 19) { ?>
                <td class="titulo" height='15' width='90'>QTDE</td>
                <td class="conteudo" height='15'>&nbsp;<?= $qtde_produtos ?>&nbsp;</td>
            <? }
        }elseif ($login_fabrica == 171) {
        ?>
            <td class='titulo' height='15' width='50'>PRESSÃO DA ÁGUA (MCA)</TD>
            <td class="conteudo">&nbsp;<? echo $regulagem_peso_padrao; ?></td>
            <td class='titulo' height='15' width='50'>TEMPO DE USO (MÊS)</TD>
            <td class="conteudo">&nbsp;<? echo $qtde_horas; ?></td>
        <?php
        }

            if($login_fabrica == 162 and $nome_linha == "Smartphone"){
                echo "<td  class='titulo' height='15' width='90' >IMEI</td>";
                echo "<td  class='conteudo'>$rg_produto</td>";
            }else if($login_fabrica == 162 and $nome_linha = "Informatica"){
                echo "<td  class='titulo' height='15' width='90' >Nº Série Item Agregado</td>";
                echo "<td  class='conteudo'>$key_code</td>";
            } if($login_fabrica == 162){ ?>
                <td class="titulo" height='15' width='90' >
                    Nº de Série
                </td>
                <td class="conteudo" height='15'>
                    <? echo $serie; ?>
                </td>
        <?  }


        if (in_array($login_fabrica,array(11,19,137,172))) {//HD 317527 - ADICIONEI PARA A FABRICA 11 ?>
            <TD class="titulo" height='15' width='90'><?echo ($login_fabrica == 137) ? "DADOS DO PRODUTO" : "RG PRODUTO"; ?></TD>
            <TD class="conteudo" nowrap>
                <?
                    if($login_fabrica == 137){
                        $dados = json_decode($rg_produto);
                        echo (!empty($dados->cfop)) ? " CFOP: ".$dados->cfop."<br />" : "-";
                        echo (!empty($dados->vu)) ? " Valor Unitário: R$ ".$dados->vu."<br />" : " -";
                        echo (!empty($dados->vt)) ? " Total Nota: R$ ".$dados->vt : " -";
                    }else{
                        echo (strlen(trim($rg_produto)) > 0) ? " &nbsp; ".$rg_produto : '- - -';
                    }
                    ?>
            </TD>
        <? }

        if (in_array($login_fabrica, array(141,144))) { ?>
            <TD class="titulo" height='15' width='90'>VALOR UNITÁRIO R$</TD>
            <TD class="conteudo" nowrap>
                <?php
                    echo number_format($rg_produto, 2, ",", ".");
                ?>
            </TD>
        <?php } ?>

        <?}
        if($login_fabrica == 86 and $serie_justificativa != 'null') { // HD 328591?>
            </tr>
            <tr>
                <td class="titulo" height='15' width='90'> JUSTIFICATIVA NÚMERO SÉRIE</td>
                <td class="conteudo" height='15' colspan='5'>&nbsp;<? echo $serie_justificativa ?></td>
            </tr>
            <tr>
        <? } ?>

    <? if($login_fabrica==14 AND strlen($numero_controle)>0){?>
        <TD class="titulo" height='15' width='100'>NÚMERO DE CONTROLE</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $numero_controle;  ?></TD>
    <? }
    if(strlen($data_fabricacao)>0){?>
        <TD class="titulo" height='15' width='100'>DATA FABRICAÇÃO</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $data_fabricacao;  ?></TD>
	<? } ?>
    </tr>
    <? if ($login_fabrica == 15 and strlen($serie_reoperado)>0) { ?>
    <TR>
        <TD class="conteudo" height='15' colspan="4">&nbsp;</TD>
        <TD class="titulo" height='15' width='95'>SÉRIE REOPERADO&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $serie_reoperado ?></TD>
    </tr>
    <?
    }
    if ($login_fabrica == 1) { ?>
    <tr>
        <TD class="titulo" height='15' width='90'>VOLTAGEM&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
        <TD class="titulo" height='15' width='110'>CÓDIGO FABRICAÇÃO&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>
        <?if($tipo_atendimento == 18 and strlen($valor_troca) > 0) { ?>
        <TD class="titulo" height='15' width='110' style='font-weight:bold;' nowrap>VALOR DA TROCA FATURADA&nbsp;</TD>
        <TD class="conteudo" height='15' style='font-weight:bold; color:red'>R$&nbsp;<? echo number_format($valor_troca,2,",","."); ?></TD>
        <? } ?>
    </tr>
    <? }

    if($login_fabrica == 164){

        $sql_va = "
            SELECT
                JSON_FIELD('numero_serie_calefator',campos_adicionais) AS numero_serie_calefator,
                JSON_FIELD('numero_serie_interno_placa_motor',campos_adicionais) AS numero_serie_interno_placa_motor,
                JSON_FIELD('cor_indicativa_carcaca',campos_adicionais) AS cor_indicativa_carcaca
            FROM tbl_os_campo_extra
            WHERE
                fabrica = {$login_fabrica}
                AND os = {$os}";

        $res_va = pg_query($con, $sql_va);

        if (pg_num_rows($res_va) > 0) {

            $numero_serie_calefator           = pg_fetch_result($res_va,0,"numero_serie_calefator");
            $cor_indicativa_carcaca           = pg_fetch_result($res_va,0,"cor_indicativa_carcaca");
            $numero_serie_interno_placa_motor = pg_fetch_result($res_va,0,"numero_serie_interno_placa_motor"); ?>
            <tr>
                <td class="titulo" colspan="3" height="15" >N. DE SÉRIE DO CALEFATOR / MOTOR</td>
                <td class="conteudo" colspan="3"><?= $numero_serie_calefator; ?></td>
            </tr>
            <tr>
                <td class="titulo" colspan="3" height="15" >COR INDICADA NA CARCAÇA</td>
                <td class="conteudo" colspan="3" ><?= $cor_indicativa_carcaca; ?></td>
            </tr>
            <tr>
                <td class="titulo" colspan="3" height="15" style="text-transform: uppercase;">Número de série interno Placa/Motor</td>
                <td class="conteudo" colspan="3" ><?= $numero_serie_interno_placa_motor; ?></td>
            </tr>

        <? }
    }
    if($login_fabrica == 15){ ?>
        <tr>
            <TD class="titulo" height='15' width='90'>Preço do Produto</TD>
            <TD colspan='7' class="conteudo" height='15'>&nbsp;<? echo "R$ ".number_format($preco_produto,2,',','.'); ?>&nbsp;</TD>
        </tr>
    <? } ?>

	<? if(in_array($login_fabrica,array(138))){ ?>
		<tr>
			<td class="titulo">CNPJ Venda</td>
			<td class="conteudo"><?=$cnpj_serie?></td>
			<td class="titulo">Nome Revenda</td>
			<td class="conteudo"><?=$nome_revenda_serie?></td>
			<td class="titulo">Data Venda</td>
			<td class="conteudo"><?=$data_venda_serie?></td>
		</tr>
	<?
    }

    if (in_array($login_fabrica, array(143))) {
    ?>
        <tr>
            <td class="titulo">Horimetro</td>
            <td class="conteudo"><?=$rg_produto?></td>
        </tr>
    <?
    }
    if ($login_fabrica == 148) { ?>
        <tr>
            <td class="titulo" >N. DE SÉRIE MOTOR</td>
            <td class="conteudo" ><?=$serie_motor?></td>
            <td class="titulo" >N. DE SÉRIE TRANSMISSÃO</td>
            <td class="conteudo" ><?=$serie_transmissao?></td>
            <td class="titulo" >HORIMETRO</td>
            <td class="conteudo" ><?=$os_horimetro?></td>
            <td class="titulo" >REVISÃO</td>
            <td class="conteudo" ><?=$os_revisao?></td>
        </tr>
    <? }
    if ($login_fabrica == 158) {
        $sqlFamilia = "
            SELECT
                f.descricao AS familia_descricao
            FROM tbl_familia f
            JOIN tbl_produto p ON p.familia = f.familia AND p.fabrica_i = {$login_fabrica}
            WHERE f.fabrica = {$login_fabrica}
            AND p.produto = {$produto};
        ";
        $resFamilia = pg_query($con, $sqlFamilia);

        $familia_descricao = pg_fetch_result($resFamilia, 0, familia_descricao); ?>
        <tr>
            <td class="titulo" >PATRIMÔNIO</td>
            <td class="conteudo" colspan="3" ><?=$serie_justificativa?></td>
            <td class="titulo" height="15">FAMÍLIA</td>
            <td class="conteudo" height="15" colspan="3"><?= $familia_descricao; ?></td>
        </tr>
    <? }
    if (in_array($login_fabrica, array(169,170))) {
        if (strlen($recolhimento) > 0 && strlen($produto_emprestimo) > 0) {
            $colspanRetEmp = 2;
        } else {
            $colspanRetEmp = 5;
        } ?>
        <tr>
            <? if (strlen($recolhimento) > 0) { ?>
                <td class="titulo" colspan="<?= $colspanRetEmp; ?>">PRODUTO RETIRADO PARA A OFICINA</td>
                <td class="conteudo"><?= ($recolhimento == "t") ? "Sim" : "Não"; ?></td>
            <? }
            if (strlen($produto_emprestimo) > 0) { ?>
                <td class="titulo" colspan="<?= $colspanRetEmp; ?>">EMPRÉSTIMO DE PRODUTO PARA O CONSUMIDOR</td>
                <td class="conteudo"><?= ($produto_emprestimo == "t") ? "Sim" : "Não"; ?></td>
            <? } ?>
        </tr>
        <? if ($serie == $posto_codigo) {
            $sql = "SELECT COUNT(*)+1 FROM tbl_os WHERE posto = {$codigo_posto} AND os < {$os} AND serie = '{$posto_codigo}';";
            $res = pg_query($con, $sql);
            $countSerieCoringa = pg_fetch_result($res, 0, 0); ?>
            <tr>
                <td class="titulo" colspan="4">OS USANDO SÉRIE CORINGA NÚMERO:</td>
                <td class="conteudo" colspan="2"><?= $countSerieCoringa; ?></td>
            </tr>
        <? }
    } ?>
</table>

<? if (in_array($login_fabrica, array(164,165)) && $posto_interno == true) {

    $sql_nf = "SELECT nota_fiscal_saida, data_nf_saida FROM tbl_os WHERE os = {$os}";
    $res_nf = pg_query($con, $sql_nf);

    if (pg_num_rows($res_nf) > 0) {

        $nota_fiscal_saida = pg_fetch_result($res_nf, 0, "nota_fiscal_saida");
        $data_nf_saida     = pg_fetch_result($res_nf, 0, "data_nf_saida");

        if (strlen($nota_fiscal_saida) > 0 && strlen($data_nf_saida) > 0) {

            list($ano, $mes, $dia) = explode("-", $data_nf_saida);
            $data_nf_saida = $dia."/".$mes."/".$ano; ?>

            <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
                <tr>
                    <td class='inicio' height='15' colspan='4'> &nbsp; Informações de Faturamento (Produto Consertado)  </td>
                </tr>
                <tr>
                    <td class="titulo" style="width: 25%;" >NOTA FISCAL DE SAÍDA</td>
                    <td class="conteudo" style="width: 25%;" ><?=$nota_fiscal_saida?></td>
                    <td class="titulo" style="width: 25%;" >DATA DA NF DE SAÍDA</td>
                    <td class="conteudo" style="width: 25%;" ><?=$data_nf_saida?></td>
                </tr>
            </table>
        <? }
    }
}

if (strlen($aparencia_produto) > 0 AND $login_fabrica <> 20) { ?>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='titulo' height='15' width='150'>APARÊNCIA DO PRODUTO</td>
            <td class="conteudo">&nbsp;<?= $aparencia_produto ?></td>
        </tr>
    </table>
<? } ?>
<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
    <TR>
    <TD class='titulo' height='15' width='150'>ACESSÓRIOS DO APARELHO</TD>
    <TD class="conteudo">&nbsp;<?= $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>

<?php
    if($login_fabrica == 138) {
	    $model = ModelHolder::init('OsProduto');
	    $osProduto = $model->find(array('os'=>$os));
		if($login_fabrica == 138 && count($osProduto) > 1 ){

            foreach($osProduto as $key => $value) {
			    if($id_produto == $value['produto']) { //HD-3158226
					unset($osProduto[$key]);
				}
            }
			$sub_array = (array_keys($osProduto));
			$produtoModel = ModelHolder::init('Produto');
			$produto = $produtoModel->select($osProduto[$sub_array[0]]['produto']);
			$htmlBuilder = HtmlBuilder::getInstance();
			$html = array();
			$html[] = array(
				'renderer' => 'table[width=700][border=0][cellspacing=1][cellpadding=0][align=center].Tabela>tbody',
				'content' => array(
					array(
						'renderer' => 'tr>td[height=15][colspan=4].inicio',
						'content' => '&nbsp;INFORMAÇÕES DO SUBCONJUNTO'
					),
					array(
						'renderer' => 'tr',
						'content' => array(
							array(
								'renderer' => 'td[height=15][width=90].titulo',
								'content' => 'REFERÊNCIA',
							),
							array(
								'renderer' => 'td[height=15].conteudo',
								'content' => $produto['referencia'],
							),
							array(
								'renderer' => 'td[height=15][width=90].titulo',
								'content' => 'DESCRIÇÃO',
							),
							array(
								'renderer' => 'td[height=15].conteudo',
								'content' => $produto['descricao'],
							),
							array(
								'renderer' => 'td[height=15][width=90][nowrap=true].titulo',
								'content' => "NÚMERO DE SÉRIE",
							),
							array(
								'renderer' => 'td[height=15].conteudo',
								'content' => $osProduto[$sub_array[0]]['serie'],
							),
						)
					)
				)
			);
			$html = $htmlBuilder->build($html);
			$html->render();

		}

    	if(strlen($osProduto[$sub_array[0]]['serie']) > 0){
    		$sql = "SELECT  tbl_numero_serie.cnpj,
                                            tbl_revenda.nome,
                                            to_char(data_venda,'DD/MM/YYYY') AS data_venda
                                    FROM tbl_numero_serie
                                    JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_numero_serie.produto
                                    LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_numero_serie.cnpj
                                    WHERE tbl_os_produto.os = {$os}
                                    AND tbl_numero_serie.fabrica = $login_fabrica
                                    AND tbl_numero_serie.serie = '{$osProduto[$sub_array[0]]['serie']}'";

    		$resS = pg_query($con,$sql);

    		$data_venda_serie = pg_fetch_result($resS,0,'data_venda');
    		$cnpj_serie       = pg_fetch_result($resS,0,'cnpj');
    		$nome_revenda_serie       = pg_fetch_result($resS,0,'nome');
    	}
	?>
    	<table width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
    		<tr>
    			<td class="titulo">CNPJ Venda</td>
    			<td class="conteudo"><?=$cnpj_serie?></td>
    			<td class="titulo">Nome Revenda</td>
    			<td class="conteudo"><?=$nome_revenda_serie?></td>
    			<td class="titulo">Data Venda</td>
    			<td class="conteudo"><?=$data_venda_serie?></td>
    		</tr>
    	</table>
	<?

    }

?>


<? if (strlen($defeito_reclamado) > 0 && !in_array($login_fabrica, array(19,20,115,116,117,158,169,170))) { //MLG 6/06/2011 - Lorenzetti lista os defeitos no próximo bloco...
	if($login_fabrica == 50){//HD-3282875
		$style_display = "style='display:none;'";
	}
?>
	<TABLE width="700px" border="0" <?=$style_display?> cellspacing="1" cellpadding="0" align='center'class='Tabela'>
    <TR>
        <TD class='titulo' height='15' width='200'>&nbsp;INFORMAÇÕES SOBRE O DEFEITO</TD>
        <TD class="conteudo" >&nbsp;
            <?
            if (strlen($defeito_reclamado) > 0) {
                $sql = "SELECT tbl_defeito_reclamado.descricao
                        FROM   tbl_defeito_reclamado
                        WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
                        //WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";
                $res = pg_query ($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $descricao_defeito = trim(pg_fetch_result($res,0,descricao));

                    //HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
                    //tbl_defeito_reclamado.descricao em outro
                    if ($login_fabrica == 3) {
                        echo $defeito_reclamado_descricao;
                    }
                    else {
                echo $descricao_defeito;

                if(!empty($dfeito_reclamado_descricao)){
                echo " - ".$defeito_reclamado_descricao;
                }
                    }
                }
            }
            ?>
        </TD>
        <?
        if($login_fabrica == 52){
        ?>
        <TD class='titulo' height='15' width='100'>&nbsp;MARCA</TD>
        <TD class="conteudo" >&nbsp;
            <?
            if (strlen($marca_fricon) > 0) {
                $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica AND marca = $marca_fricon order by nome";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res)>0){
                    for($i=0;pg_num_rows($res)>$i;$i++){
                        $xmarca = pg_fetch_result($res,$i,marca);
                        $xnome = pg_fetch_result($res,$i,nome);
                        echo $xnome;
                   }
                }
            }
        ?>
        </TD>
        <? } ?>
    </TR>
</TABLE>
<? } ?>
<? if ($login_fabrica == 19 and (strlen($fabricacao_produto) > 0 or strlen($qtde_km) > 0)) {  // HD 64152?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
    <TR>
        <TD class='titulo' height='15'width='300'>Mês e Ano de Fabricação do Produto&nbsp;</TD>
        <TD class="conteudo" >&nbsp;<?echo $fabricacao_produto;?>
        </TD>
        <TD class='titulo' height='15'width='100'>Quilometragem &nbsp;</TD>
        <TD class="conteudo" >&nbsp;<?echo $qtde_km;?>
        </TD>
    </TR>
</TABLE>
<? } ?>

<? if (in_array($login_fabrica, array(6,30,59,94))) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
    <? if (!in_array($login_fabrica, array(59,94))) { ?>
        <td class='titulo' height='15' width='300'><?= ($login_fabrica == 6) ? "OS Posto" : "OS Revendedor"; ?></td>
        <td class="conteudo">&nbsp;<?= $os_posto; ?></td>
    <? }
    if (in_array($login_fabrica, array(30,59,94))) {
        $width_tecnico = ($login_fabrica == 94) ? 300 : 100;
        $width_tecnico2 = ($login_fabrica == 94) ? 227 : 'auto'; ?>
        <TD class="titulo" height='15' width='<?=$width_tecnico;?>' align='right'>TÉCNICO&nbsp;</TD>
        <TD class="conteudo" width="<?=$width_tecnico2;?>" >&nbsp;<? echo $tecnico_nome ?>&nbsp;</TD>
        <?
        // HD 415550
        if($login_fabrica == 94) {
            $sql = "SELECT mao_de_obra FROM tbl_os WHERE os = $os";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)) {

                echo '<td class="titulo">MÃO-DE-OBRA&nbsp;</td>
                      <td class="conteudo" align="right">&nbsp;R$ '.number_format( pg_result($res,0,0),2,',','.' ).'</td>';

            }
        }
    }
    if($login_fabrica == 6 AND strtoupper($nome_linha) == "TABLET"){ ?>
        <TD class="titulo" height='15' width='150' align='right'>
            <? echo "NÚMERO CORREIOS";?>
        </TD>
        <TD class="conteudo" >&nbsp;<? echo $obs_adicionais; ?>&nbsp;</TD>
    <?php } ?>
</TR>
</TABLE>
<?}?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <TR>
        <TD  height='15' class='inicio' colspan='4'>&nbsp;<?=$tema_titulo?></TD>
    </TR>
    <TR>
        <?php if($login_fabrica <> 20){ #HD-2843341
            if($login_fabrica == 148){
                $colspan = "colspan='4'";
            }
        ?>
        <TD class="titulo" height='15' width='90'>RECLAMADO</TD>
        <TD class="conteudo" <?=$colspan?> height='15' width='150' <?if (in_array($login_fabrica,array(30,43,59,85,91,94,114,152)) || (isset($usaDefeitoReclamadoCadastro) && isset($novaTelaOs))) echo "colspan=4"?>>
            <?
            // HD 22820
            if($login_fabrica == 1){
                //if($troca_garantia=='t' or $troca_faturada=='t')    echo $descricao_defeito ;
                    echo $descricao_defeito ; if(!empty($defeito_reclamado_descricao))echo " - ".$defeito_reclamado_descricao;
            } else if ($login_fabrica == 19){ // hd 64152
                $sql = "
                    SELECT DISTINCT tbl_defeito_reclamado.codigo,tbl_defeito_reclamado.descricao
                    FROM tbl_os_defeito_reclamado_constatado
                    JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                    WHERE os = {$os}
                ";
                $res = pg_query ($con,$sql);

                $array_integridade_reclamado = array();

                if(@pg_num_rows($res)>0){
                    for ($i=0;$i<pg_num_rows($res);$i++){
                        $aux_defeito_reclamado = pg_fetch_result($res,$i,1);
                        array_push($array_integridade_reclamado,$aux_defeito_reclamado);
                    }
                }
                $lista_defeitos_reclamados = implode($array_integridade_reclamado,", ");
                echo "$lista_defeitos_reclamados";

            } elseif(in_array($login_fabrica, array(11, 172)) and strlen($hd_chamado)>0) {
                echo $defeito_reclamado_descricao;
            }else{

                $sql = "
                    SELECT
                        tbl_defeito_reclamado.codigo,
                        tbl_defeito_reclamado.descricao
                    FROM tbl_os
                    JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado and tbl_os.fabrica = tbl_defeito_reclamado.fabrica
                    WHERE os = {$os}
                    AND tbl_defeito_reclamado.fabrica = {$login_fabrica};
                ";

                $res = pg_query ($con,$sql);

                if (pg_num_rows($res) > 0){

                    $xdefeito_reclamado_cod = pg_result($res,0,codigo);
                    $xdefeito_reclamado_desc = pg_result($res,0,descricao);

                    if (in_array($login_fabrica, array(50,86,115,116,158,169,170))){
                        if ($login_fabrica == 158) {
                            echo $xdefeito_reclamado_cod." - ";
                        }

                        echo $xdefeito_reclamado_desc;
                    }else{
                        echo $descricao_defeito;
                    }

                }

                if($defeito_reclamado_descricao and empty($xdefeito_reclamado_desc)) {
                    //HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
                    //tbl_defeito_reclamado.descricao em outro
                    //HD-3331834 Adicinada fabrica 50 no in_array
                    if (!in_array($login_fabrica, array(3,158,165,169,170)) && $defeito_reclamado_descricao != 'null') {

                        echo (strlen($descricao_defeito) > 0) ? " - " : "";
                        echo $defeito_reclamado_descricao;
                    }
                }
            }

            ?></TD>
            <?
            }
            if($login_fabrica == 95){
                echo "<td class='titulo'>&nbsp;</td>";
                echo "<td class='conteudo'>&nbsp;</td>";
            }
            ?>
            <?if (!in_array($login_fabrica,array(20,30,43,59,85,95,131,143,152)) && !isset($defeitoConstatadoMultiplo)) { ?>
      <? if (!in_array($login_fabrica,array(94,114,138))) { ?>
        <TD class="titulo" height='15' width='90'>
            <?php if($admin_consulta_os == false){?>
                <? if($login_fabrica==20)echo "REPARO";else echo $tema_coluna;?> &nbsp;
            <?php }?>
        </td>
        <td class="conteudo" height="15">
     <? } ?>
            <?php if($admin_consulta_os == false && $login_fabrica != 138){?>
                <?php if ($login_fabrica != 94) echo '&nbsp;'; ?>
            <?
            //HD 17683 - VÁRIOS DEFEITOS CONSTATADOS
            if(in_array($login_fabrica,array(19,30,43,94,114,134))){

                $sql = "SELECT DISTINCT tbl_defeito_constatado.codigo,tbl_defeito_constatado.descricao
                    FROM tbl_os_defeito_reclamado_constatado
                    JOIN tbl_defeito_constatado USING(defeito_constatado)
                    WHERE os=$os";
                $res = pg_query ($con,$sql);

                $array_integridade = array();

                if(@pg_num_rows($res)>0){
                    for ($i=0;$i<pg_num_rows($res);$i++){
                        $aux_defeito_constatado = pg_fetch_result($res,$i,0).'-'.pg_fetch_result($res,$i,1);
                        array_push($array_integridade,$aux_defeito_constatado);
                    }
                }
                if(in_array($login_fabrica,array(94,114))) {
                    echo '</tr>';
                    foreach ($array_integridade as  $v ) {

                        echo '
                                  <tr>
                                    <td class="titulo" height="15" width="90">DEFEITO CONSTATADO</td>
                                    <td  class="conteudo" height="15" colspan="4">'.$v.'</td>
                                 </tr>';

                    }

                }else if($login_fabrica == 134){
                    echo "<ul>";
                    foreach($array_integridade AS $key){
                        echo "<li>".$key."</li>";
                    }
                    echo "</ul>";
                }else{
                    $lista_defeitos = implode($array_integridade,", ");
                    echo "$lista_defeitos";
                }
            }else{
                // HD 22820
                if( $login_fabrica==1){
                    if($troca_garantia=='t' or $troca_faturada=='t'){
                        echo $defeito_reclamado_descricao;
                    }else{
                        echo $defeito_constatado;
                    }
                }else{
                    if($login_fabrica==20)echo $defeito_constatado_codigo.' - ';

                    if (isset($novaTelaOs)) {
                        $sql = "SELECT tbl_defeito_constatado.descricao FROM tbl_defeito_constatado INNER JOIN tbl_os_produto ON tbl_os_produto.defeito_constatado = tbl_defeito_constatado.defeito_constatado WHERE tbl_os_produto.os = {$os}";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) > 0) {
                            $defeito_constatado = pg_fetch_result($res, 0, "descricao");
                        }
                    }

                    echo $defeito_constatado;
                }
            }
             ?>
            <?php }?>
<? if (!in_array($login_fabrica,array(94,114)) ) { ?>
        </TD>
        <?php
          if($login_fabrica == 145){?>
        <TD class="titulo" height='15' width='90'>SOLUÇÃO</TD>
        <TD class="conteudo" height='15'>&nbsp;
            <?php
                $sql = "SELECT tbl_solucao.descricao FROM tbl_solucao INNER JOIN tbl_os ON tbl_os.solucao_os = tbl_solucao.solucao WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $solucao_descricao = pg_fetch_result($res, 0, "descricao");
                    echo $solucao_descricao;
                }
            ?>
        </TD>
        <?php
        }
        ?>
    </TR>
   <?if($login_fabrica == 153 and $defeito_constatado == 'Mau Uso'){?>
        <tr>
            <td height='15' class='inicio' colspan="4">&nbsp;Descrição do Mau Uso</td>
        </tr>
        <tr>
            <td  class="conteudo" height='15' colspan="4"><?=$obs_adicionais?></td>
        </tr>
    <? } ?>


    <?php if(!in_array($login_fabrica, array(50,115,116,120,201,121,122,123,124,125,126,127,128,129,131,134,136,138,140,143)) and $login_fabrica < 141){ ?>

    <TR>
        <TD class="titulo" height='15' width='90'>
        <?// 30/08/2010 MLG - Na os_press da fábrica a Lenoxx também tem esta solução, e mais pra baixo aparece
        if (in_array($login_fabrica, array(1,3,6,11,15,24,40,43,45,50,59,72,74,80)) or (!in_array($login_fabrica, array(87,131,140,143)) and $login_fabrica > 80)){
                                    echo "SOLUÇÃO";}
        elseif($login_fabrica==20){ echo "DEFEITO";}

        elseif($login_fabrica==87){echo "TÉCNICO";}

        elseif($login_fabrica==52){echo "GRUPO DEFEITO";}

        else{                       echo "CAUSA"  ;}

        ?>

        &nbsp;</td>
        <td class="conteudo" colspan='3' height='15'>&nbsp;
        <?
        if((in_array($login_fabrica, array(1,3,24, 40, 43, 45,59,72, 74)) or $login_fabrica >= 80 and $login_fabrica<>87) and strlen($solucao_os)>0) {//takashi 30-11
            $sql="SELECT descricao FROM tbl_solucao WHERE solucao=$solucao_os AND fabrica=$login_fabrica LIMIT 1";
            $xres = pg_query($con, $sql);
            if(pg_num_rows($xres)>0){
                $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                echo "$xsolucao";
            }else{
                $sql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $solucao_os AND fabrica = $login_fabrica LIMIT 1;";
                $xres = pg_query($con, $sql);
                $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                echo "$xsolucao";
            }
        }

        if ($login_fabrica == 87) {
            echo $tecnico_nome;
        }

    //HD 53480 Adicionado fabrica = 3
    if (in_array($login_fabrica, array(3,6,11,15,50,172))) {
        if (strlen($solucao_os) > 0){
            //chamado 1451 - não estava validando a data...
            $sql_data = "SELECT SUM(validada - '2006-11-05') AS total_dias FROM tbl_os WHERE os=$os";
            $resD = pg_query ($con,$sql_data);
            if (pg_num_rows ($resD) > 0) {
                $total_dias = pg_fetch_result ($resD,0,total_dias);
            }
            if ( ($total_dias > 0 AND $login_fabrica==6) OR in_array($login_fabrica, array(3,11,15,50,172)) ){
                $sql="SELECT descricao FROM tbl_solucao WHERE solucao=$solucao_os AND fabrica=$login_fabrica LIMIT 1";
                $xres = pg_query($con, $sql);
                if (pg_num_rows($xres)>0){
                    $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                    echo "$xsolucao";
                }

            }else{
                $xsql="SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado= $solucao_os LIMIT 1";
                $xres = pg_query($con, $xsql);
                if (pg_num_rows($xres)>0){
                    $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                    echo "$xsolucao";
                }else{
                    $sql="SELECT descricao FROM tbl_solucao WHERE solucao=$solucao_os AND   fabrica=$login_fabrica LIMIT 1";
                    $xres = pg_query($con, $sql);
                    $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                    echo "$xsolucao";
                }
            }
        }
        }else{
            if($login_fabrica==52){
                echo $defeito_constatado_grupo;
            }
            if (in_array($login_fabrica, array(20,131,143))) {
                echo $causa_defeito_codigo.' - ' ;
                echo $causa_defeito;
            }
        }
        ?>
        </TD>
        <?}?>
    </TR>
    <? }
}

    if ($login_fabrica == 152) {
            $sqlDef = "SELECT
                        tbl_defeito_constatado.descricao AS defeito_constatado,
                        tbl_os_defeito_reclamado_constatado.tempo_reparo
                       FROM tbl_os_defeito_reclamado_constatado
                       INNER JOIN tbl_defeito_constatado
                       ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
                       AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                       WHERE tbl_os_defeito_reclamado_constatado.os = {$os}";
            $resDef = pg_query($con, $sqlDef);

            if (pg_num_rows($resDef) > 0) {
                while ($defeito_constatado = pg_fetch_object($resDef)) {
                    echo "
                        <tr>
                            <td class='titulo'>CONSTATADO</td>
                            <td class='conteudo'>{$defeito_constatado->defeito_constatado}</td>
                            <td class='titulo'>TEMPO DE REPARO (MINUTOS)</td>
                            <td class='conteudo'>{$defeito_constatado->tempo_reparo}</td>
                        </tr>
                    ";
                }
            }
        }

    if($login_fabrica == 131){ ?>
        <TD class="titulo" height='15' width='90'>CAUSA</TD>
            <td class="conteudo" height='15'>
                &nbsp;
                <?php
                $sql = "select tbl_causa_defeito.descricao  from tbl_os join tbl_causa_defeito using(causa_defeito) where os = $os;";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    echo pg_result($res,0,descricao);
                }

                ?>

            </td>
    <?php
    }

	if ($login_fabrica == 138) {
        $sql_defeito_constatado = "
            SELECT
                tbl_produto.referencia AS produto,
                tbl_defeito_constatado.descricao AS defeito_constatado
           FROM tbl_os_produto
           INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
           INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
           WHERE tbl_os_produto.os = {$os};
       ";

        $res_defeito_constatado = pg_query($con, $sql_defeito_constatado);

        if (pg_num_rows($res_defeito_constatado) > 0) {
            while ($result = pg_fetch_object($res_defeito_constatado)) { ?>
                <tr>
                    <td class='titulo'><?= $tema; ?> Produto: <?= $result->produto; ?></td>
                    <td class='conteudo'><?= $result->defeito_constatado; ?></td>
                </tr>
            <? }
        }
    }

    if (in_array($login_fabrica,array(30,43,59,85,95,131,138,143,148,149)) || isset($defeitoConstatadoMultiplo)) {

        if(in_array($login_fabrica, array(138,149))){
            $condLeft = "LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
                        JOIN tbl_solucao USING(solucao)
                        LEFT JOIN tbl_defeito USING(defeito)";
        }else if($login_fabrica == 148){
            $condLeft = "LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
                        LEFT JOIN tbl_solucao USING(solucao)
                        LEFT JOIN tbl_defeito USING(defeito)";
        }else{
            $condLeft = "JOIN tbl_defeito_constatado USING(defeito_constatado)
                        LEFT JOIN tbl_solucao USING(solucao)
                        LEFT JOIN tbl_defeito USING(defeito)";
        }

        $sql_cons = "SELECT
                    tbl_defeito_constatado.defeito_constatado,
                    tbl_defeito_constatado.descricao,
                    tbl_defeito_constatado.codigo,
                    tbl_solucao.solucao,
                    tbl_solucao.descricao AS solucao_descricao,
                    tbl_defeito.defeito,
                    tbl_defeito.descricao AS defeito_descricao
            FROM tbl_os_defeito_reclamado_constatado
            $condLeft
            WHERE os = {$os};";

        $res_dc = pg_query($con, $sql_cons);
        if(pg_num_rows($res_dc) > 0){

            if($login_fabrica <> 148){

                if (in_array($login_fabrica, array(30,131,143,158,169,170))) {
                    $rowspan_defeito = pg_num_rows($res_dc) + 1;
                    echo "<tr>";
                    echo "<td class='titulo' rowspan='".$rowspan_defeito."' height='15'>".$temaMaiusculo."</td>";
                    echo "</tr>";
                }

                for($x=0;$x<pg_num_rows($res_dc);$x++){
                    $dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
                    $dc_solucao = pg_fetch_result($res_dc,$x,solucao);
                    $dc_descricao = pg_fetch_result($res_dc,$x,descricao);
                    $dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
                    $dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);
                    $dc_defeito    = pg_fetch_result($res_dc,$x,defeito);
                    $dc_defeito_descricao = pg_fetch_result($res_dc,$x,defeito_descricao);

                    echo "<tr>";
                        if(!in_array($login_fabrica, array(138))){
                            if (!in_array($login_fabrica, array(30,131,143,158,169,170))){
                                echo "<td class='titulo' height='15'>$temaMaiusculo</td>";
                            }
                            if (in_array($login_fabrica, array(30,131,143,158))){
                                echo "<td class='conteudo' colspan='4'>$dc_codigo - $dc_descricao</td>";
                            } else if (!in_array($login_fabrica, array(169,170))) {
                                echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
                            }

                            if (in_array($login_fabrica, array(169,170)) && !empty($dc_defeito)) {
                                echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
                                echo "<td class='titulo' height='15'>Defeito da Peça</td>";
                                echo "<td class='conteudo'>&nbsp; $dc_defeito_descricao</td>";
                            } else if (in_array($login_fabrica, array(169,170))) {
                                $colspanDC = (in_array($login_fabrica, array(169,170))) ? "colspan='100%'" : "";
                                echo "<td class='conteudo' {$colspanDC}>&nbsp; $dc_descricao</td>";
                            }
                        }
                        if (!in_array($login_fabrica,array(30,131,143,156,158,169,170))){
                            $colspan = (in_array($login_fabrica, array(138,149))) ? "colspan='100%'" : "";
                            echo "<td class='titulo' height='15'>Solucão</td>";
                            echo "<td class='conteudo' $colspan>&nbsp; $dc_solucao_descricao</td>";
                        }
                    echo "</tr>";
                }
            }else{
                $dados_defeito_solucao = pg_fetch_all($res_dc);

                foreach ($dados_defeito_solucao as $key => $value) {
                    if(strlen(trim($value["descricao"])) > 0){
                        echo "<tr>";
                            echo "<td class='titulo' style='width: 150px;' height='15'>DEFEITO CONSTATADO</td>";
                            echo "<td class='conteudo'>&nbsp;".$value["descricao"]."</td>";
                        echo "</tr>";
                    }
                    if(strlen(trim($value["solucao_descricao"])) > 0){
                        echo "<tr>";
                            echo "<td class='titulo' style='width: 150px;' height='15'>SOLUÇÃO</td>";
                            echo "<td class='conteudo'>&nbsp;".$value["solucao_descricao"]."</td>";
                        echo "</tr>";
                    }
                }
            }
        }
    }

    if (in_array($login_fabrica, array(158))) {
            $sqlSolucaoOS = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado INNER JOIN tbl_solucao USING(solucao) WHERE os = {$os}";;
            $resSolucaoOS = pg_query($con, $sqlSolucaoOS);

            if (pg_num_rows($resSolucaoOS) > 0) {
                while ($solucao = pg_fetch_object($resSolucaoOS)) {
                ?>
                    <tr>
                        <td class="titulo">Solução</td>
                        <td class="conteudo" colspan="4"><?=$solucao->codigo. " - ".$solucao->descricao?></td>
                    </tr>
                <?php
                }
            }
            ?>
    <? }

    if($login_fabrica==20 and !empty($solucao_os)){
        $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
        $xres = @pg_query($con, $xsql);
        $xsolucao = trim(@pg_fetch_result($xres,0,descricao));
        echo "<tr>";
        echo "<td class='titulo' height='15' width='90'>IDENTIFICAÇÃO&nbsp;</td>";
        echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
        echo "</tr>";
    }
    ?>
</TABLE>
<?php
    if($consumidor_revenda == "REVENDA" and in_array($login_fabrica,array(145))){
?>
    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DA REVENDA&nbsp;</td>
        </tr>
        <TR>
            <TD class="titulo" height='15'>NOME</TD>
            <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
            <TD class="titulo">TELEFONE&nbsp;</TD>
            <TD class="conteudo"height='15'>&nbsp;<? echo $revenda_fone ?></TD>
        </TR>

        <TR>
            <?php
            if (isset($novaTelaOs)) {
            ?>
                <TD class="titulo" height='15'><?=(strtoupper($consumidor_revenda) == 'REVENDA') ? "CNPJ" : "CPF";?> REVENDA&nbsp;</TD>
            <?php
            } else {
            ?>
                <TD class="titulo" height='15'><?=($login_fabrica == 85 && $campos_adicionais->consumidor_cpf_cnpj == 'R') ? "CNPJ" : "CPF";?> REVENDA&nbsp;</TD>
            <?php
            }
            ?>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
            <TD class="titulo" height='15'>CEP&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_cep ?></TD>
        </TR>
        <TR>
            <TD class="titulo" height='15'>ENDEREÇO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_endereco ?></TD>
            <TD class="titulo" height='15'>NÚMERO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_numero ?></TD>
        </TR>
        <TR>
            <TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_complemento ?></TD>
            <TD class="titulo" height='15'>BAIRRO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_bairro ?></TD>
        </TR>

        <TR>
            <TD class="titulo">CIDADE&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $nome_cidade_revenda ?></TD>
            <TD class="titulo">ESTADO&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $sigla_estado_revenda ?></TD>
        </TR>
        <TR>
            <TD class="titulo">E-MAIL&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $revenda_email ?></TD>
            <?if($login_fabrica==1){?>
                <TD class="titulo">TIPO CONSUMIDOR</TD>
                <TD class="conteudo">&nbsp;<? echo $fisica_juridica; ?></TD>
            <?}elseif( in_array($login_fabrica, array(11,172)) ){?>
                <TD class="titulo">FONE REC</TD>
                <TD class="conteudo"><? echo $consumidor_fone_recado; ?></TD>
            <?
            } else if ($login_fabrica == 122) {
            ?>
                <TD class="titulo">CPD DO CLIENTE</TD>
                <TD class="conteudo"><? echo $consumidor_cpd; ?></TD>
            <?php
            } else if ($login_fabrica == 59) {
                $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                    foreach ($campos_adicionais as $key => $value) {
                        $key = $value;
                    }

                    if (strlen($origem)>0) {
                        $origem = ($origem == "recepcao") ? "Recepção" : "Sedex Reverso";
                    }
                    ?>
                    <TD class="titulo" width="80">ORIGEM&nbsp;</TD>
                    <TD class="conteudo">&nbsp;<?=$origem?> </TD>
                    <?php
                }
            } else {
                    ?>
                        <TD class="titulo">&nbsp;</TD>
                        <TD class="conteudo">&nbsp;</TD>
                    <?
            }
            ?>
        </TR>
    </TABLE>



    <?
}else{

    if (in_array($login_fabrica, array(158))) {
        if (isset($dadosIntegracao)) {
            $cockpitPrioridade = $dadosIntegracao[0]['descricao'];

            $verifKA = strrpos($cockpitPrioridade, "KA");

            if ($verifKA === false){
                $keyAccount = "";
            } else {
                $keyAccount = " (KEY ACCOUNT)";
            }
        }
    }

?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DO CONSUMIDOR&nbsp;</td>
    </tr>
    <TR>
        <TD class="titulo" height='15'>NOME <?=($login_fabrica == 85 && $campos_adicionais->consumidor_cpf_cnpj == 'R') ? "FANTASIA" : "";?>&nbsp;</TD>
        <TD class="conteudo" height='15' width='300'>&nbsp;<?= (in_array($login_fabrica, array(158)) && strlen($keyAccount) > 0) ? $consumidor_nome.$keyAccount : $consumidor_nome; ?></TD>
        <TD class="titulo">TELEFONE&nbsp;</TD>
        <TD class="conteudo"height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
    </TR>
    <? if(in_array($login_fabrica,array(1,3,11,20,35,45,50,59,74,52,80,85,101,104,120,201,145,147)) || $login_fabrica > 147) { ?>
        <TR>
            <TD class="titulo" height='15'>CELULAR&nbsp;</TD>
            <TD class="conteudo" height='15' width='300'>&nbsp;
                <? if ($login_fabrica == 52) {
                    $sql = "SELECT tbl_hd_chamado_extra.celular FROM tbl_hd_chamado_extra INNER JOIN tbl_os ON tbl_os.os = $os AND tbl_os.hd_chamado = tbl_hd_chamado_extra.hd_chamado";
                    $res = pg_query($con, $sql);
                    if(pg_num_rows($res) > 0){
                        $consumidor_celular = pg_fetch_result($res, 0, 'celular');
                        if(strlen($consumidor_celular) > 0){
                            if(count($os_campos_adicionais) > 0){
                                $operadora_celular = $os_campos_adicionais['operadora'];
                                echo $consumidor_celular;
                                echo (strlen(trim($operadora_celular)) > 0) ? " / Operadora: " . $operadora_celular : "";
                            }else{
                                echo "";
                            }
                        }
                    }
                } else {
                    echo $consumidor_celular;
                } ?>
            </TD>
            <? if(in_array($login_fabrica,array(104,11,151,162,3,52,59,74,80,85,120,201,172))) { ?>
                <TD class="titulo">TELEFONE COMERCIAL&nbsp;</TD>
                <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone_comercial ?></TD>
            <? } else if ($login_fabrica == 20) { ?>
                <TD class="titulo" height='15'>CPF/CNPJ CONSUMIDOR&nbsp;</TD>
                <TD class="conteudo" height='15' <?=$coll;?> >&nbsp;<? echo $consumidor_cpf ?></TD>
            <? } else if (in_array($login_fabrica, [167, 203])) { ?>
                <td class="titulo">CONTATO</td>
                <td class="conteudo"><?=$contato_consumidor?></td>
            <? } else if ($login_fabrica == 171) { ?>
                <TD class="titulo">EDIFÍCIO</TD>
                <TD class="conteudo" height='15'><?=($os_campos_adicionais['edificio'] == 't') ? 'Sim' : 'Não'; ?></TD>
            <? } else { ?>
                <TD class="titulo">&nbsp;</TD>
                <TD class="conteudo" height='15'>&nbsp;</TD>
           <? } ?>
        </TR>
    <? }
    if ($login_fabrica <> 20) { ?>
        <TR>
            <? if (isset($novaTelaOs)) { ?>
                <TD class="titulo" height='15'><?=(strtoupper($consumidor_revenda) == 'REVENDA') ? "CNPJ" : "CPF";?> CONSUMIDOR&nbsp;</TD>
            <? } else { ?>
                <TD class="titulo" height='15'><?=($login_fabrica == 85 && $campos_adicionais->consumidor_cpf_cnpj == 'R') ? "CNPJ" : "CPF";?> CONSUMIDOR&nbsp;</TD>
            <? } ?>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
            <TD class="titulo" height='15'>CEP&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
        </TR>
    <? }
    if ($login_fabrica <> 20) { //hd_chamado=2843341 ?>
        <TR>
            <TD class="titulo" height='15'>ENDEREÇO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
            <TD class="titulo" height='15'>NÚMERO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
        </TR>
        <TR>
            <TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
            <TD class="titulo" height='15'>BAIRRO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
        </TR>
    <?php } ?>
    <?php if ($login_fabrica == 52):
        $sql_pr = "SELECT obs from tbl_os_extra where os=$os";
        $res_pr = pg_query($con,$sql_pr);
        $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
    ?>
        <TD class="titulo" height='15'>PONTO DE REFERÊNCIA</TD>
        <TD class="conteudo"  height='15'>&nbsp; <? echo $ponto_referencia ?></TD>
        <TD class="titulo" height='15'>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;</TD>
    <?php endif ?>
    <?php if($login_fabrica <> 20){//hd_chamado=2843341 ?>
        <TR>
            <TD class="titulo">CIDADE&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
            <TD class="titulo">ESTADO&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
        </TR>
        <TR>
            <TD class="titulo">E-MAIL&nbsp;</TD>
            <?if($login_fabrica == 35){?>
                <TD class="conteudo">&nbsp;<? echo ( strlen($consumidor_email)>0)? "$consumidor_email": $obs_adicionais ?></TD>
            <?php }else{ ?>
                <TD class="conteudo">&nbsp;<? echo $consumidor_email ?></TD>
            <?php } ?>

            <?if($login_fabrica==1){?>
                <TD class="titulo">TIPO CONSUMIDOR</TD>
                <TD class="conteudo">&nbsp;<? echo $fisica_juridica; ?></TD>
            <?}elseif( in_array($login_fabrica, array(11,172)) ){?>
                <TD class="titulo">FONE REC</TD>
                <TD class="conteudo"><? echo $consumidor_fone_recado; ?></TD>
            <?
            } else if ($login_fabrica == 122) {
            ?>
                <TD class="titulo">CPD DO CLIENTE</TD>
                <TD class="conteudo"><? echo $consumidor_cpd; ?></TD>
            <?php
            }elseif ($login_fabrica==59) {
                $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                    foreach ($campos_adicionais as $key => $value) {
                        $$key = $value;
                    }
                    if (strlen($origem)>0) {
                        $origem = ($origem == "recepcao") ? "Recepção" : "Sedex Reverso";
                    }
                    ?>
                    <TD class="titulo" width="80">ORIGEM&nbsp;</TD>
                    <TD class="conteudo">&nbsp;<?=$origem?> </TD>
                    <?php
                }
            } elseif ($login_fabrica == 74) {
                $qry_c_adicionais = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
                $consumidor_data_nascimento = '';

                if (pg_num_rows($qry_c_adicionais)) {
                    $os_c_adicionais = json_decode(pg_fetch_result($qry_c_adicionais, 0, 'campos_adicionais'), true);

                    if (array_key_exists("data_nascimento", $os_c_adicionais)) {
                        $consumidor_data_nascimento = $os_c_adicionais["data_nascimento"];
                    }
                }
                ?>
                <TD class="titulo">DATA DE NASCIMENTO
                <TD class="conteudo"><?php echo $consumidor_data_nascimento ?></TD>
                <?php
            } elseif ($login_fabrica == 171) {
            ?>
                <TD class="titulo">QUANTIDADE DE ANDARES</TD>
                <TD class="conteudo"><?=$os_campos_adicionais['edificio_total_andares']; ?></TD>
            <?
            }else{
                    ?>
                        <TD class="titulo">&nbsp;</TD>
                        <TD class="conteudo">&nbsp;</TD>
                    <?
            }
            ?>
        </TR>
    <?php } ?>
    <?php if($login_fabrica == 20){ ?>
        <tr>
            <TD class="titulo">E-MAIL&nbsp;</TD>
            <TD class="conteudo" colspan='3'>&nbsp;<? echo $consumidor_email ?></TD>
        </tr>
    <?php } ?>
</TABLE>
<?
}
/*COLORMAQ TEM 2 REVENDAS*/
if($login_fabrica==50){

    $sql = "SELECT
                cnpj,
                to_char(data_venda, 'dd/mm/yyyy') as data_venda
            FROM tbl_numero_serie
            WHERE serie = trim('$serie')";

    $res_serie = pg_query ($con,$sql);

    if (pg_num_rows ($res_serie) > 0) {


        $txt_cnpj   = trim(pg_fetch_result($res_serie,0,cnpj));
        $data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

        $sql = "SELECT      tbl_revenda.nome              ,
                            tbl_revenda.revenda           ,
                            tbl_revenda.cnpj              ,
                            tbl_revenda.cidade            ,
                            tbl_revenda.fone              ,
                            tbl_revenda.endereco          ,
                            tbl_revenda.numero            ,
                            tbl_revenda.complemento       ,
                            tbl_revenda.bairro            ,
                            tbl_revenda.cep               ,
                            tbl_revenda.email             ,
                            tbl_cidade.nome AS nome_cidade,
                            tbl_cidade.estado
                FROM        tbl_revenda
                LEFT JOIN   tbl_cidade USING (cidade)
                LEFT JOIN   tbl_estado using(estado)
                WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

        $res_revenda = pg_query ($con,$sql);

        # HD 31184 - Francisco Ambrozio (02/08/08) - detectei que pode haver
        #   casos em que o SELECT acima não retorna resultado nenhum.
        #   Acrescentei o if para que não dê erros na página.
        $msg_revenda_info = "";
        if (pg_num_rows ($res_revenda) > 0) {
            $revenda_nome_1       = trim(pg_fetch_result($res_revenda,0,nome));
            $revenda_cnpj_1       = trim(pg_fetch_result($res_revenda,0,cnpj));

            $revenda_bairro_1     = trim(pg_fetch_result($res_revenda,0,bairro));
            $revenda_cidade_1     = trim(pg_fetch_result($res_revenda,0,cidade));
            $revenda_fone_1       = trim(pg_fetch_result($res_revenda,0,fone));
        } else {
            $msg_revenda_info = "Não foi possível obter INFORMAÇÕES DA REVENDA (CLIENTE COLORMAQ): Nome, CNPJ e Telefone.";
        }
?>
        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;<?echo "INFORMAÇÕES DA REVENDA (CLIENTE COLORMAQ)";?></td>
            </tr>
            <? if (strlen($msg_revenda_info) > 0){
                    echo "<tr>";
                    echo "<td class='conteudo' colspan= '4' height='15'><center>$msg_revenda_info</center></td>";
                    echo "</tr>";
                } ?>
            <TR>
                <TD class="titulo"  height='15' ><?echo "NOME";?>&nbsp;</TD>
                <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome_1 ?></TD>
                <TD class="titulo"  height='15' width='80'><?echo "CNPJ";?>&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj_1 ?></TD>
            </TR>
            <TR>
            <?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
                <TD class="titulo"  height='15'><?echo "FONE";?>&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_fone_1  ?></TD>
                <TD class="titulo"  height='15'><?echo "DATA DA NF";?>&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<?echo $data_venda; ?></TD>
            </TR>
        </TABLE>
<?

    }
}
/*COLORMAQ TEM 2 REVENDAS - FIM*/
?>


<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <? if(!in_array($login_fabrica,array(122,143,156))){ ?>
        <tr>
            <? if ($login_fabrica == 145) { ?>
                <td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DA REVENDA/CONSTRUTORA</td>
            <? } else { ?>
                <td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DA REVENDA <? if ($login_fabrica == 50) { echo "(CONSUMIDOR)"; } ?></td>
            <? } ?>
        </tr>
    <? } else{ ?>
        <tr>
            <td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DA NOTA FISCAL</td>
        </tr>
    <? }
    if(!in_array($login_fabrica,array(20,122,143,156))){ ?>
        <TR>
            <? $colspan = ($login_fabrica == 15) ? "colspan='3'" : ""; ?>

            <TD class="titulo"  height='15' width='90'>NOME&nbsp;</TD>
            <TD class="conteudo"  height='15' width='300' <?=$colspan?>>&nbsp;<? echo $revenda_nome ?></TD>
            <? if($login_fabrica <> 15){ ?>
                <TD class="titulo"  height='15' width='80'>CNPJ REVENDA&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
            <? } ?>
        </TR>
    <? }
    if (in_array($login_fabrica, array(169,170))) { ?>
        <tr>
            <td class="titulo">CONTATO</td>
            <td class="conteudo"><?= $revenda_contato; ?></td>
            <td class="titulo"></td>
            <td class="conteudo"></td>
        </tr>
    <? } ?>
    <TR>
        <?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <TD class="titulo"  height='15'>NF NÚMERO&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<FONT COLOR="#FF0000"><? if($login_fabrica==6 and $posto==4260 and strlen($nota_fiscal_saida)>0) echo $nota_fiscal_saida ; else echo $nota_fiscal; ?></FONT></TD>
        <TD class="titulo"  height='15'>DATA DA NF&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<? if($login_fabrica==6 and $posto==4260 and strlen($data_nf_saida)>0) echo $data_nf_saida ; else echo $data_nf; ?></TD>
    </TR>

    <? if (in_array($login_fabrica, array(141,165))) { ?>
        <TR>
            <TD class="titulo"  height='15' nowrap><?=($login_fabrica == 165) ? "TIPO ENTREGA" : "CÓDIGO RASTREIO"?>&nbsp;</TD>
            <TD class="conteudo"  height='15' colspan="3" >&nbsp;
                <? if (!empty($cod_rastreio) && $cod_rastreio != "balcão") {
                    if (in_array($login_fabrica, array(165)) && $cod_rastreio == "sem_rastreio") {

                    } else {
                        echo "<a href='http://websro.correios.com.br/sro_bin/txect01\$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$cod_rastreio' target='_blank' >$cod_rastreio</a>";
                    }
                } else {
                    echo $cod_rastreio;
                } ?>
            </TD>
        </TR>
    <? }
    if ($login_fabrica == 87) { ?>
        <TR>
            <TD class="titulo"  height='15'>TELEFONE&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $revenda_fone;?></TD>
            <TD class="titulo"  height='15'>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;</TD>
        </TR>
    <? }
    if ( in_array($login_fabrica, array(11,172)) ) { ?>
        <TR>
            <TD class="titulo"  height='15'>FONE&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $revenda_fone;?></TD>
            <TD class="titulo"  height='15'>EMAIL&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_email; ?></TD>
        </TR>
    <? }
    if (in_array($login_fabrica, array(11,81,96,172))) {
        $sql = "SELECT nota_fiscal_saida, to_char(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida FROM tbl_os WHERE os = {$os}";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 1) {
            $nota_fiscal_saida = pg_fetch_result($res,0,nota_fiscal_saida);
            $data_nf_saida = pg_fetch_result($res,0,data_nf_saida); ?>
             <TR>
                <TD class="titulo"  height='15' >NF DE SAIDA</TD>
                <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $nota_fiscal_saida;?></TD>
                <TD class="titulo"  height='15'>DATA&nbsp;NF&nbsp;DE&nbsp;SAIDA</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf_saida;?></TD>
            </TR>
        <? }
    }

    if ($login_fabrica == 3){

        $sql = "SELECT
                    tbl_revenda_fabrica.contato_endereco,
                    tbl_revenda_fabrica.contato_numero,
                    tbl_revenda_fabrica.contato_complemento,
                    tbl_revenda_fabrica.contato_bairro,
                    tbl_ibge.cidade,
                    tbl_ibge.estado
                FROM tbl_os
                JOIN tbl_revenda on (tbl_os.revenda_cnpj = tbl_revenda.cnpj)
                JOIN tbl_revenda_fabrica on (tbl_revenda.cnpj = tbl_revenda_fabrica.cnpj and tbl_revenda.revenda = tbl_revenda_fabrica.revenda)
                JOIN tbl_fabrica on (tbl_revenda_fabrica.fabrica = tbl_fabrica.fabrica and tbl_fabrica.fabrica=$login_fabrica)
                LEFT JOIN tbl_ibge on (tbl_revenda_fabrica.contato_cidade = tbl_ibge.cod_ibge)

                WHERE tbl_os.os = $os
                AND tbl_os.fabrica=$login_fabrica
        ";

        $res = pg_query($con,$sql);

        $contato_endereco    = pg_result($res,0,0);
        $contato_numero      = pg_result($res,0,1);
        $contato_complemento = pg_result($res,0,2);
        $contato_bairro      = pg_result($res,0,3);
        $cidade              = pg_result($res,0,4);
        $estado              = pg_result($res,0,5);

    ?>

        <TR>
            <TD class="titulo"  height='15'>ENDEREÇO&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $contato_endereco;?></TD>
            <TD class="titulo"  height='15'>NÚMERO&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $contato_numero; ?></TD>
        </TR>

        <TR>
            <TD class="titulo"  height='15'>COMPLEMENTO&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $contato_complemento;?></TD>
            <TD class="titulo"  height='15'>BAIRRO&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $contato_bairro; ?></TD>
        </TR>

        <TR>
            <TD class="titulo"  height='15'>CIDADE&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $cidade;?></TD>
            <TD class="titulo"  height='15'>ESTADO&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $estado; ?></TD>
        </TR>
    <?php
    }

    if ($login_fabrica == 145) {
    ?>
        <tr>
            <td class="titulo" style="height: 15px;" >CONSTRUTORA</td>
            <td class="conteudo" style="height: 15px;" colspan="3" ><?=$os_construtora?></td>
        </tr>
    <?php
    }
    ?>
</TABLE>

<?php
    /* HD 26244 */
    if ($login_fabrica==30 AND strlen($certificado_garantia)>0){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (105,106,107)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 105){
                    $estendida_observacao = "OS em auditoria";
                }
                if ($estendida_status_os == 106){
                    $estendida_observacao = "OS Aprovada na Auditoria";
                }
                if ($estendida_status_os == 107){
                    $estendida_observacao = "OS Recusada na Auditoria";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;GARANTIA ESTENDIDA </td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='90'>LGI&nbsp;</TD>
                <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $certificado_garantia ?></TD>
                <TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
    /* HD 26244 */
    if ($login_fabrica==30){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (132,19)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 132){
                    $estendida_observacao = "OS em auditoria de reincidência";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;Auditoria de Reincidência</td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
    /* HD 209166 */
    if ($login_fabrica==30){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (102,103,104)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 102){
                    $estendida_observacao = "OS em auditoria de número de série";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;Auditoria de Número de Série</td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
    /* HD 209166 */
    if ($login_fabrica==30){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (98,99,100,101)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 98){
                    $estendida_observacao = "OS em auditoria de ". number_format($qtde_km, 2, ',', '.'). " Km";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;Auditoria de KM</td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
$sql = "SELECT os
        FROM tbl_os_troca_motivo
        WHERE os = $os ";
$res = pg_query($con,$sql);
if($login_fabrica==20 AND pg_num_rows($res)>0) {
    $motivo1 = "Não são fornecidas peças de reposição para este produto";
    $motivo2 = "Há peça de reposição, mas está em falta";
    $motivo3 = "Vicio do produto";
    $motivo4 = "Divergência de voltagem entre embalagem e produto";
    $motivo5 = "Informações adicionais";
    $motivo6 = "Informações complementares";
    $troca = true;
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4'>
<?
if($sistema_lingua=='ES')echo "Informações sobre o MOTIVO DA TROCA";
else {
    echo "Informações sobre o MOTIVO DA TROCA";
}
?>
            </td>
        </tr>
        <tr>
            <td colspan='4' class="conteudo">
                <div>
                    <div>
<?

        $sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
                        tbl_causa_defeito.codigo        AS causa_codigo     ,
                        tbl_causa_defeito.descricao     AS causa_defeito
                FROM   tbl_os_troca_motivo
                JOIN   tbl_servico_realizado USING(servico_realizado)
                JOIN   tbl_causa_defeito     USING(causa_defeito)
                WHERE os     = $os
                AND   motivo = '$motivo1'";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)==1){
            $identificacao1 = pg_fetch_result($res,0,servico_realizado);
            $causa_defeito1 = pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
        ?>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    Data de entrada do produto na assistência técnica
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $data_abertura; ?>
                </div>
            </div>

            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    <br><? echo $motivo1; ?>
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    Identificação do defeito
                </div>
                <div id="contentleft2" style="width: 250px; ">
                    Defeito
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $identificacao1; ?>
                </div>
                <div id="contentleft" style="width: 250px;font:75%">
                    <? echo $causa_defeito1; ?>
                </div>
            </div>
            <?
            }
            $sql = "SELECT
                            TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
                            pedido                                              ,
                            PE.referencia                     AS peca_referencia,
                            PE.descricao                      AS peca_descricao
                    FROM   tbl_os_troca_motivo
                    JOIN   tbl_peca            PE USING(peca)
                    WHERE os     = $os
                    AND   motivo = '$motivo2'";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)==1){
                $peca_referencia = pg_fetch_result($res,0,peca_referencia);
                $peca_descricao  = pg_fetch_result($res,0,peca_descricao);
                $data_pedido     = pg_fetch_result($res,0,data_pedido);
                $pedido          = pg_fetch_result($res,0,pedido);

            ?>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    <br><? echo $motivo2?>
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    Código da Peça
                </div>
                <div id="contentleft2" style="width: 200px; ">
                    Data do Pedido
                </div>
                <div id="contentleft2" style="width: 200px; ">
                    Número do Pedido
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $peca_referencia."-".$peca_descricao; ?>
                </div>
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $data_pedido; ?>
                </div>
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $pedido; ?>
                </div>
            </div>
            <?
            }

            $sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
                            tbl_causa_defeito.codigo        AS causa_codigo     ,
                            tbl_causa_defeito.descricao     AS causa_defeito    ,
                            observacao
                    FROM   tbl_os_troca_motivo
                    JOIN   tbl_servico_realizado USING(servico_realizado)
                    JOIN   tbl_causa_defeito     USING(causa_defeito)
                    WHERE os     = $os
                    AND   motivo = '$motivo3'";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)==1){
                $identificacao2 = pg_fetch_result($res,0,servico_realizado);
                $causa_defeito2 =  pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
                $observacao1    = pg_fetch_result($res,0,observacao);

            ?>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    <br><? echo $motivo3?>
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    Identificação do Defeito
                </div>
                <div id="contentleft2" style="width: 200px; ">
                    Defeito
                </div>
                <div id="contentleft2" style="width: 200px; ">
                    Quais as OS's deste produto:
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $identificacao2; ?>
                </div>
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $causa_defeito2; ?>
                </div>
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $observacao1; ?>
                </div>
            </div>
            <?
            }

            $sql = "SELECT observacao
                    FROM   tbl_os_troca_motivo
                    WHERE os     = $os
                    AND   motivo = '$motivo4'";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)==1){
                $observacao2    = pg_fetch_result($res,0,observacao);
            ?>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 200px; " nowrap>
                    <br><? echo $motivo4; ?>
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft2" style="width: 650px; " nowrap>
                    Qual a divergência:
                </div>
            </div>
            <div id="contentcenter" style="width: 650px;">
                <div id="contentleft" style="width: 200px;font:75%">
                    <? echo $observacao2; ?>
                </div>
            </div>
            <?
            }
            ?>
        </h2>
    </div>
</div>
<?
    $sql = "SELECT observacao
            FROM   tbl_os_troca_motivo
            WHERE os     = $os
            AND   motivo = '$motivo5'";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)==1){
        $observacao3    = pg_fetch_result($res,0,observacao);
        ?>
        <!-- id="container"   -->
        <!-- id="page" -->
        <div >
            <div >
                <h2><?=$motivo5?>
                <div id="contentcenter" style="width: 650px;">
                    <div id="contentleft" style="width: 650px;font:75%"><? echo $observacao3;?></div>
                </div>
                </h2>
            </div>
        </div>
        <?
    }
    /* HD 43302 - 26/9/2008 */
    $sql = "SELECT observacao
            FROM   tbl_os_troca_motivo
            WHERE os     = $os
            AND   motivo = '$motivo6'";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)==1){
	    $observacao4    = pg_fetch_result($res,0,observacao);
	    $observacao4 = wordwrap($observacao4, 77, '<br/>', true);
        ?>
        <!-- id="container"   -->
        <!-- id="page" -->
        <!-- Retirado das divs abaixo, estava estourando layout-->
        <div >
            <div>
                <h2><?=$motivo6?>
                <div id="contentcenter" style="width: 600px;">
                    <div id="contentleft" style="width: 600px;font:75%"><? echo $observacao4;?></div>
                </div>
                </h2>
            </div>
        </div>
        <?
    }
}
?>
        </td>
    </tr>
</table>
<?

/*takashi compressores*/
if ($login_fabrica == 1) {

    $sql = "SELECT tecnico
              FROM tbl_os_extra
             WHERE os = $os";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $relatorio_tecnico = pg_fetch_result($res, 0, 'tecnico');
    }

    if ($tipo_os == 13) {
        $where_visita = " os_revenda = $os_numero";
    } else {
        $where_visita = "os = $os";
    }

    $sql = "SELECT  os                                  ,
                    to_char(data, 'DD/MM/YYYY') as  data,
                    to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
                    to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
                    km_chegada_cliente   as km          ,
                    valor_adicional                     ,
                    justificativa_valor_adicional       ,
                    qtde_produto_atendido
            FROM tbl_os_visita
            WHERE $where_visita";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

        echo "<table border='0' cellpadding='0' cellspacing='1' width='700px' align='center' class='Tabela'>";
        echo "<tr class='inicio'>";

        if ($tipo_os == 13) {
            echo "<td width='100%' colspan='6'>&nbsp;DESPESAS DA OS GEO METAL: $os_numero</td>";
        } else {
            echo "<td width='100%' colspan='6'>&nbsp;DESPESAS DE COMPRESSORES</td>";
        }

        echo "</tr>";

        echo "<tr>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
        if($tipo_os ==13){
            echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtde Produto Atendido</font></td>";
        }
        echo "<td nowrap class='titulo2' colspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td nowrap class='titulo2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
        echo "<td nowrap class='titulo2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
        echo "</tr>";

        for($i=0;$i<pg_num_rows($res);$i++){

            $data                          = pg_fetch_result($res,$i,data);
            $inicio                        = pg_fetch_result($res,$i,inicio);
            $fim                           = pg_fetch_result($res,$i,fim);
            $km                            = pg_fetch_result($res,$i,km);
            $valor_adicional               = pg_fetch_result($res,$i,valor_adicional);
            $justificativa_valor_adicional = pg_fetch_result($res,$i,justificativa_valor_adicional);
            $qtde_produto_atendido         = pg_fetch_result($res,$i,qtde_produto_atendido);


            echo "<tr class='conteudo'>";
            echo "<td align='center'>&nbsp;$data                         </td>";
            echo "<td align='center'>&nbsp;$inicio                       </td>";
            echo "<td align='center'>&nbsp;$fim                          </td>";
            echo "<td align='center'>&nbsp;$km                           </td>";
            if($tipo_os ==13){
                echo "<td align='center'>&nbsp;$qtde_produto_atendido    </td>";
            }
            echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."         </td>";
            echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
            echo "</tr>";
        }
        if($tipo_os==13){
            echo "<tr class='titulo2'>";
            echo "<td align='center' colspan='7'>Relatório Técnico</td>";
            echo "</tr>";
            echo "<tr class='Conteudo'>";
            echo "<td align='left' colspan='7'>$relatorio_tecnico</td>";
            echo "</tr>";
        }
        echo "</table>";

    }
}
if ($login_fabrica == 1) {//HD 235182

    $sql = "SELECT codigo,
                       TO_CHAR(garantia_inicio, 'DD/MM/YYYY') as garantia_inicio,
                       TO_CHAR(garantia_fim, 'DD/MM/YYYY') as garantia_fim,
                       motivo
                  FROM tbl_certificado
                 WHERE os = $os
                   AND fabrica = $login_fabrica";

    $res = pg_query($con, $sql);
    $tot = pg_num_rows($res);

    if ($tot > 0) {?>

        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='tabela'>
            <tr>
                <td class='inicio'>&nbsp;CERTIFICADO DE GARANTIA</td>
            </tr>
            <tr>
                <th class="titulo2">Código</th>
                <th class="titulo2">Data Inicio</th>
                <th class="titulo2">Data Termino</th>
                <th class="titulo2">Motivo</th>
            </tr><?php
            for ($i = 0; $i < $tot; $i++) {
                echo '<tr>';
                    echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res, $i, 'codigo').'</td>';
                    echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res, $i, 'garantia_inicio').'</td>';
                    echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res, $i, 'garantia_fim').'</td>';
                    echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res, $i, 'motivo').'</td>';
                echo '</tr>';
            }?>
        </table><?php

    }

}


if (in_array($login_fabrica,array(30,52,74,91,114,115,116,117,120,201,125,128,129,131,140,166))) {
    if ($login_fabrica == 52) {
        $descKm = "DADOS DO KM";
    } else {
        $descKm = "QUANTIDADE DE KM";
    }
?><!-- Qtde de KM Wanke HD 375933 -->

    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
        <TR>
            <TD class='inicio' colspan='2'><?=$descKm?></TD>
        </TR>
        <TR>
            <TD class="titulo" width='100'>DESLOCAMENTO&nbsp;</TD>
            <TD class="conteudo">&nbsp;
                <?php echo number_format($qtde_km,2,',','.'); ?> KM
                <?php

                    if (strlen($obs_adicionais)) {
                        if ($login_fabrica == 30) {
                            echo " - ({$obs_adicionais})";
                        } else if ($login_fabrica == 52) {
                            echo "<br>Dados sobre KM: ".$obs_adicionais;
                        }
                    }

                ?>
            </TD>
        </TR>
    </TABLE><?php
}

if (in_array($login_fabrica,array(162))) {
    $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

        foreach ($campos_adicionais as $key => $value) {
            $$key = $value;
        }
    }
?>

    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
        <TR>
            <TD class='inicio' colspan='2'>POSTAGEM</TD>
        </TR>
        <TR>
            <TD class="titulo" width="90">DATA SAÍDA</TD>
            <TD class="conteudo" width="300">&nbsp;<?=$data_saida?></TD>
            <TD class="titulo" width="80">CÓD. RASTREIO&nbsp;</TD>
            <TD class="conteudo">&nbsp;<a href="http://www.websro.com.br/correios.php?P_COD_UNI=<?=$rastreio?>" target = "_blank"><?=$rastreio?></a></TD>
        </TR>
    </TABLE><?php
}

if(in_array($login_fabrica, array(169,170))){
    $sql_chamados = "SELECT tbl_hd_chamado.hd_chamado AS numero_chamado,
                            TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY' ) AS data_chamado,
                            tbl_hd_chamado.categoria AS categoria_chamado
                        FROM tbl_hd_chamado
                        JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        WHERE tbl_hd_chamado_extra.os = {$os}
                        AND tbl_hd_chamado.fabrica = {$login_fabrica} ";
    $res_chamados = pg_query($con, $sql_chamados);
    if(pg_num_rows($res_chamados) > 0){
?>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
            <tr>
                <td class='inicio'>CHAMADOS CALLCENTER</td>
            </tr>
            <tr>
                <td class='titulo2'>NÚMERO DO CHAMADO</td>
                <td class='titulo2'>DATA ABERTURA</td>
            </tr>
                <?php
                    for ($i=0; $i < pg_num_rows($res_chamados); $i++) {
                        $numero_chamado     = pg_fetch_result($res_chamados, $i, 'numero_chamado');
                        $data_chamado       = pg_fetch_result($res_chamados, $i, 'data_chamado');
                        $categoria_chamado  = pg_fetch_result($res_chamados, $i, 'categoria_chamado');
                ?>
                    <tr class='conteudo'>
                        <td class='conteudo' style="text-align: center;"><a href="callcenter_interativo_new.php?callcenter=<?=$numero_chamado?>#<?=$categoria_chamado?>" target="blank"><?=$numero_chamado?></a></td>
                        <td class='conteudo' style="text-align: center;"><?=$data_chamado?></td>
                    </tr>
                <?php
                    }
                ?>

        </table>
<?php
    }
}

if ($admin_consulta_os == false) {
    //if (strlen($defeito_reclamado) > 0) { ?>
    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
    <TR>
    <TD colspan="<? if ($login_fabrica == 1) { echo "8"; }else{ echo "7"; } ?>"class='inicio'>&nbsp;DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS</TD>
</TR>
<TR>
    <!--    <TD class="titulo">EQUIPAMENTO</TD> -->
    <?php
        if($login_fabrica == 138):
    ?>
        <td class="titulo2">PRODUTO</td>
    <?php
        endif;
    ?>
    <?
    if($os_item_subconjunto == 't') {
        echo"<TD class=\"titulo2\">SUBCONJUNTO</TD>";
        echo"<TD class=\"titulo2\">POSIÇÃO</TD>";
    }
    ?>
    <? // HD 23036
    if( in_array($login_fabrica, array(11,172)) ){?>
        <TD class="titulo2">ADMIN</TD>
        <? } ?>
    <TD class="titulo2">COMPONENTE</TD>
    <?
    if ($login_fabrica == 50) {

        ?>
        <TD class="titulo2">FORNECEDOR</TD>
        <?

    }
    ?>
    <TD class="titulo2">QTDE</TD>

    <?php
    if($login_fabrica == 148 || (in_array($login_fabrica, array(156,161,167,203)) && $desc_tipo_atendimento == "Orçamento") || (in_array($login_fabrica, array(163)) && $desc_tipo_atendimento == 'Fora de Garantia') ) {
        echo "<td class='titulo2'>PREÇO UNITÁRIO</td>";
        echo "<td class='titulo2'>PREÇO TOTAL</td>";
    }

    ?>

    <? if ($mostrar_valor_pecas) { //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item?>
    <TD class='titulo'>PREÇO</TD>
    <TD class='titulo'>TOTAL</TD>
    <?}?>
    <TD class="titulo2">DIGIT.</TD>

    <? // HD 23036
    if($login_fabrica == 87){?>
        <TD class="titulo2">CAUSA FALHA</TD>
        <? }

    if(!in_array($login_fabrica, array(114,115,116,117,121,122,123,124,125,126,127,128,129,131,134,138,140,141,144)) && !isset($novaTelaOs)) { ?>
        <TD class="titulo2">DEFEITO</TD>
    <? } else if (in_array($login_fabrica, array(151,169,170))) { ?>
        <TD class="titulo2">DEFEITO</TD>
    <? }

    if ($login_fabrica == 96) {
        echo "<TD class='titulo2'>FREE OF CHARGE</TD>";
    } else {
        echo "<TD class='titulo2'>SERVIÇO</TD>";
        if ($login_fabrica == 87) {
            echo "<TD class='titulo2'>SOAF</TD>";
        }
    } ?>

    <TD class="titulo2">PEDIDO</TD>
    <?if ($login_fabrica == 3) { /* ALTERADO TODA A ROTINA DE NF - HD 8973 */ ?>
        <TD class="titulo2" nowrap>N.F. FABRICANTE</TD>
    <?}

    if ($login_fabrica == 87){?>
    <TD class="titulo2">NF/EMISSÃO</TD>
    <?} else{ ?>
        <TD class="titulo2">NOTA FISCAL</TD>
        <TD class="titulo2">EMISSÃO</TD>

        <?php
            if ($login_fabrica == 1 AND $reembolso_peca_estoque == 't' AND empty($tipo_atendimento)) {
                echo "<td class='titulo2'>ESTOQUE</td>";
                echo "<td class='titulo2'>PREVISÃO</td>";
            }
        ?>

        <? if ($login_fabrica == 3) { ?>
            <TD class="titulo2">ANEXOS</TD>
        <? } ?>
        <?php
            if($login_fabrica == 125){?>
                <TD class="titulo2"><span data-title="Lembrando que este prazo oscila conforme o fluxo de entregas e em função de fins de semana e feriados." class="previsao_entrega">PREVISÃO DE ENTREGA <strong style="font-size:13px;">*</strong></span></TD>
        <?php
            }
            if($login_fabrica == 156){?>
                <TD class="titulo2">VOID</TD>
        <?php
            }
        ?>

    <?}?>
    <?
    // nao mostrar data_saida se nao tiver conhecimento gravado



    //Gustavo 12/12/2007 HD 9095
    if (in_array($login_fabrica,array(3,11,35,45,74,80,86,157,160,172)) || $telecontrol_distrib)  echo "<TD class='titulo2'>CONHECIMENTO</TD>";
    if (in_array($login_fabrica, array(147,151,162))) echo "<TD class='titulo2'>CÓDIGO DE RASTREIO</TD>";
    if (in_array($login_fabrica, array(151))) echo "<TD class='titulo2'>DATA DE SAÍDA</TD>";
    if (in_array($login_fabrica,array(35))){
        echo "<TD class='titulo2'>DATA ENTREGA</TD>";
        echo "<TD class='titulo2'>PO-PEÇA</TD>";
    }

    $campos_black = "";
    $cond_black   = "";

    if ($login_fabrica == 1) {

        $campos_black = "tbl_depara.para                       ,
                         tbl_peca_fora_linha.peca_fora_linha   ,
                        ";

        $cond_black   = " LEFT JOIN tbl_depara ON tbl_depara.peca_de = tbl_peca.peca AND tbl_depara.fabrica = $login_fabrica
                          LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
                        ";
    }

    $campo_ext = "";
    if ($login_fabrica == 148) {
        $campo_ext = 'tbl_os.qtde_km_calculada,tbl_os.mao_de_obra,';
    }

    if ($login_fabrica == 161) {
        $campo_ext = 'tbl_peca.ipi,';
    }

    $sql = "SELECT  tbl_produto.referencia                                         ,
                    tbl_produto.descricao                                          ,
                    tbl_produto.referencia_fabrica    as produto_referencia_fabrica       ,
                    tbl_os_produto.serie                                           ,
                    tbl_os_produto.versao                                          ,
                    tbl_os_item.os_item                                            ,
                    tbl_os_item.serigrafia                                         ,
                    tbl_os_item.pedido                                             ,
                    tbl_os_item.pedido_item                                        ,
                    tbl_os_item.peca                                               ,
                    tbl_os_item.obs                                                ,
                    tbl_os_item.qtde                                               ,
                    tbl_os_item.custo_peca                                         ,
                    tbl_os_item.preco                                              ,
                    tbl_os_item.posicao                                            ,
                    tbl_os_item.admin                                              ,
                    tbl_os_item.peca_causadora                                     ,
                    tbl_os_item.soaf                                               ,
                    tbl_os_item.parametros_adicionais                              ,
                    tbl_peca.promocao_site                                         ,
                    tbl_peca.promocao_site                                         ,
                    tbl_peca.referencia_fabrica AS peca_referencia_fabrica         ,
                    {$campo_ext}
                    tbl_peca.parametros_adicionais AS parametros_adicionais_pecas  ,
                    {$campos_black}
                    tbl_os_item.servico_realizado AS servico_realizado_peca        ,
                    tbl_os_item.faturamento_item    AS faturamento_item            ,
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
                    tbl_os_item_nf.nota_fiscal                                     ,
                    TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf       ,
                    CASE
                        WHEN tbl_pedido.pedido_blackedecker > 499999 THEN
                            LPAD ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
                        WHEN tbl_pedido.pedido_blackedecker > 399999 THEN
                            LPAD ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
                        WHEN tbl_pedido.pedido_blackedecker > 299999 THEN
                            LPAD ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
                        WHEN tbl_pedido.pedido_blackedecker > 199999 THEN
                            LPAD ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
                        WHEN tbl_pedido.pedido_blackedecker > 99999 THEN
                            LPAD ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
                    ELSE
                        LPAD(tbl_pedido.pedido_blackedecker::text,5,'0')
                    END                                      AS pedido_blackedecker,
                    tbl_pedido.seu_pedido                                          ,
                    tbl_pedido.distribuidor                                        ,
                    tbl_defeito.descricao           AS defeito                     ,
                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.retorna_conserto       AS retorna_conserto            ,
                    tbl_os_item.peca_obrigatoria  AS devolucao_obrigatoria       ,
                    tbl_peca.descricao              AS descricao_peca              ,";
    //28/08/2010 MLG HD 237471  Fábricas que mostram o valor da peça, baseado na tbl_os_item
    if($mostrar_valor_pecas){
            $sql .= "tbl_os_item.custo_peca  AS preco_peca                  ,
                    tbl_os_item.custo_peca*qtde AS total_peca, ";
    }


    $sql.= "
                    tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                    tbl_status_pedido.descricao     AS status_pedido               ,
                    tbl_produto.referencia          AS subproduto_referencia       ,
                    tbl_produto.descricao           AS subproduto_descricao        ,
                    tbl_admin.login                 AS nome_admin                  ,
                    TO_CHAR (tbl_os_item.data_liberacao_pedido,'DD/MM/YYYY HH24:MI') AS data_liberacao_pedido
            FROM    tbl_os_produto
            JOIN    tbl_produto       USING (produto)
            JOIN    tbl_os            USING (os)
            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN    tbl_os_item       USING (os_produto)
            LEFT JOIN    tbl_peca          USING (peca)
            $left_join_tabela_item
            LEFT JOIN tbl_defeito USING (defeito)
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_admin          ON tbl_os_item.admin        = tbl_admin.admin
            LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            {$cond_black}
            WHERE   tbl_os_produto.os = $os
            ORDER BY tbl_os_item.os_item DESC ";

    $res = pg_query($con,$sql);
    $total = pg_num_rows($res);

    $tem_pedido = 'f';
    $exibe_legenda = 0;
    $manual_ja_imprimiu = 0;
    if(in_array($login_fabrica, array(156,161,167,203)) && $desc_tipo_atendimento == "Orçamento" OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia')){
        $valor_total_pecas = 0;
    }

    if(in_array($login_fabrica, [167, 203])){
        $dados_orcamento_email = array();
    }

    if(in_array($login_fabrica, array(101))){

        $sql_os_troca = "SELECT os_troca FROM tbl_os_troca WHERE os = {$os} AND fabric = {$login_fabrica}";
        $res_os_troca = pg_query($con, $sql_os_troca);

        $os_trocada = false;

        if(pg_num_rows($res_os_troca) > 0){

            $os_trocada = true;

        }

    }

    for ($i = 0 ; $i < $total ; $i++) {
        $pedido                  = trim(pg_fetch_result($res,$i,'pedido'));
        $pedido_item             = trim(pg_fetch_result($res,$i,'pedido_item'));
        $pedido_blackedecker     = trim(pg_fetch_result($res,$i,'pedido_blackedecker'));

        $usa_po_peca             = trim(pg_fetch_result($res,$i,promocao_site));
        $parametros_adicionais_pecas   = json_decode(pg_fetch_result($res, $i, parametros_adicionais_pecas), true);

        if ($login_fabrica == 1) {
            $para            = trim(pg_fetch_result($res,$i,para));
            $peca_fora_linha = trim(pg_fetch_result($res,$i,peca_fora_linha));
        }

        if ($login_fabrica == 148) {
            $qtde_km_calculada = trim(pg_fetch_result($res,$i,qtde_km_calculada));
            $mao_de_obra       = trim(pg_fetch_result($res,$i,mao_de_obra));
        }


        if($login_fabrica == 35 && $usa_po_peca == "t"){
            $parametros_adicionais   = trim(pg_fetch_result($res,$i,parametros_adicionais));
            $parametros_adicionais   = json_decode($parametros_adicionais, true);
            $po_peca = $parametros_adicionais["po_peca"];
        }

        if($login_fabrica == 153){
            $parametros_adicionais   = trim(pg_fetch_result($res,$i,parametros_adicionais));
            $parametros_adicionais   = json_decode($parametros_adicionais, true);
        }

        $seu_pedido              = trim(pg_fetch_result($res,$i,'seu_pedido'));
        $os_item                 = trim(pg_fetch_result($res,$i,'os_item'));
        $parametros_adicionais   = trim(pg_fetch_result($res,$i,'parametros_adicionais'));
        $peca_causadora          = trim(pg_fetch_result($res,$i,'peca_causadora'));
        $soaf                    = trim(pg_fetch_result($res,$i,'soaf'));
        $descricao_peca          = trim(pg_fetch_result($res,$i,'descricao_peca'));
        $peca                    = trim(pg_fetch_result($res,$i,'peca'));
        $servico_realizado_descricao = trim(pg_fetch_result($res, $i, 'servico_realizado_descricao'));
        $faturamento_item        = trim(pg_fetch_result($res,$i,'faturamento_item'));
        //chamado 141 - britania - pega nota fiscal do distribuidor
        if ($login_fabrica == 3) {
            $nota_fiscal_distrib = trim(pg_fetch_result($res,$i,'nota_fiscal'));
            $data_nf_distrib     = trim(pg_fetch_result($res,$i,'data_nf'));
            $nota_fiscal         = "";
            $data_nf             = "";
            $link_distrib        = 0;
        } else {
            $nota_fiscal         = trim(pg_fetch_result($res,$i,'nota_fiscal'));
            $data_nf             = trim(pg_fetch_result($res,$i,'data_nf'));
        }

        //conversado com o Analista sobre essa query.
        if(strlen($nota_fiscal)==0 and $login_fabrica == 160 and !empty($pedido_item)){
            $sql_peca_alternativa = "SELECT peca_alternativa FROM tbl_pedido_item WHERE pedido_item = $pedido_item";
            $res_peca_alternativa = pg_query($con, $sql_peca_alternativa);
            if(pg_num_rows($res_peca_alternativa)>0){
                $peca_alternativa = pg_fetch_result($res_peca_alternativa, 0, peca_alternativa);
            }
        }
        if ($login_fabrica == 171) {
            $peca_referencia_fabrica = " / ".trim(pg_fetch_result($res,$i,'peca_referencia_fabrica'));
        }
        $status_pedido           = trim(pg_fetch_result($res,$i,'status_pedido'));
        $obs                     = trim(pg_fetch_result($res,$i,'obs'));
        $distribuidor            = trim(pg_fetch_result($res,$i,'distribuidor'));
        $digitacao               = trim(pg_fetch_result($res,$i,'digitacao_item'));
        $admin_digitou           = trim(pg_fetch_result($res,$i,'admin'));
        $data_liberacao_pedido   = trim(pg_fetch_result($res,$i,'data_liberacao_pedido'));

        if (strlen($pedido) > 0) $tem_pedido = 't';

        if (strlen($seu_pedido)>0){
            $pedido_blackedecker = fnc_so_numeros($seu_pedido);
        }

        if (strlen($admin_digitou) > 0) {
            $sqla = "SELECT login FROM tbl_admin WHERE admin = $admin_digitou";
            $resa = pg_query($con, $sqla);
            $admin_digitou = " <b style='font-weight:normal;color:#000000;font-size:10px'>(Digitado por ".trim(pg_fetch_result($resa,0,0)).")</b> ";
        }

        /*====--------- INICIO DAS NOTAS FISCAIS ----------===== */
        /* ALTERADO TODA A ROTINA DE NF - HD 8973 */
        /*############ BLACKEDECKER ############*/
        if ($login_fabrica == 1){
            if (strlen ($nota_fiscal) == 0) {
                if (strlen($pedido) > 0) {
                    $sql  = "SELECT trim(nota_fiscal) As nota_fiscal ,
                            TO_CHAR(data, 'DD/MM/YYYY') AS emissao
                            FROM    tbl_pendencia_bd_novo_nf
                            WHERE   pedido_banco = $pedido
                            AND     peca         = $peca";
                    $resx = pg_query ($con,$sql);
                        // HD 22338
                    if (pg_num_rows ($resx) > 0 AND 1==2) {
                        $nf   = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $link = 0;
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                    }else{
                        // HD 30781
                        $sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
                            TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
                            FROM    tbl_os
                            JOIN    tbl_os_produto USING (os)
                            JOIN    tbl_os_item USING (os_produto)
                            JOIN    tbl_peca USING(peca)
                            WHERE   tbl_os_item.pedido= $pedido
                            AND     tbl_os_item.peca         = $peca
                            AND     tbl_peca.produto_acabado IS TRUE ";
                        $resnf = pg_query ($con,$sql);
                        if(pg_num_rows($resnf) >0){
                            $nf   = trim(pg_fetch_result($resnf,0,nota_fiscal_saida));
                            $link = 0;
                            $data_nf = trim(pg_fetch_result($resnf,0,data_nf_saida));
                        }else{
                            $nf      = "Pendente";
                            $data_nf = "";
                            $link    = 1;
                        }
                    }
                }else{
                    $nf = "";
                    $data_nf = "";
                    $link = 0;
                }
            }else{
                $nf = $nota_fiscal;
            }

        /*############ BRITANIA ############*/
        } else if ($login_fabrica == 3) {

            //Nota do fabricante para distribuidor
            //NF para BRITANIA (DISTRIBUIDORES E FABRICANTES chamado 141) =============================

            if (strlen($pedido) > 0) {
                if(strlen($distribuidor) > 0){

                    $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            WHERE   tbl_faturamento_item.pedido  = $pedido
                            AND     tbl_faturamento_item.peca    = $peca
                            AND     tbl_faturamento.posto = $distribuidor";
                    //echo "E2 - " . nl2br($sql);
                    $resx = pg_query ($con,$sql);
                    if (pg_num_rows ($resx) > 0) {
                        $nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                        $link    = 0;
                    } else {
                        $nf      = 'Pendente'; #HD 16354
                        $data_nf = '';
                        $link    = 0;
                    }

                    if ($distribuidor == 4311) {
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento_item.pedido  = $pedido
                                /*AND     tbl_faturamento_item.peca  = $peca*/
                                AND     tbl_faturamento_item.os_item = $os_item ";

                                //retirado por Samuel 4/12/2007 - Um nf do distrib atendendo 2 os não tem como gravar 2 os_item.
                                // Coloquei AND     tbl_faturamento_item.os_item = $os_item - Fabio - HD 7591

                        $sql .= "AND     tbl_faturamento.posto        = $posto
                                AND     tbl_faturamento.distribuidor = 4311";

                        $resx = pg_query ($con,$sql);
                        if (pg_num_rows ($resx) > 0) {
                            $nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
                            $link_distrib        = 1;
                        } else {
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link_distrib        = 0;
                        }
                    }

                    if($distribuidor != 4311) {
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento_item.pedido = $pedido
                                AND     tbl_faturamento_item.peca   = $peca
                                AND     tbl_faturamento.posto       <> $distribuidor;";
                        #echo "E2 - " . nl2br($sql);
                        $resx = pg_query ($con,$sql);

                        if (pg_num_rows ($resx) > 0) {
                            $nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
                            $link_distrib        = 1;
                        } else {
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link_distrib        = 0;
                        }
                    }
                }else{
                    //(tbl_faturamento_item.os = $os) --> HD3709
                /*  HD 72977 */
                    $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                tbl_faturamento.conhecimento AS conhecimento
                        FROM    tbl_faturamento
                        JOIN    tbl_faturamento_item USING (faturamento)
                        WHERE   tbl_faturamento_item.pedido = $pedido
                        AND     tbl_faturamento_item.peca   = $peca
                        AND     tbl_faturamento.posto       = $posto
                        AND     (tbl_faturamento_item.os ISNULL OR tbl_faturamento_item.os = $os)
                        ";

                    $resx = pg_query ($con,$sql);

                    if (pg_num_rows ($resx) > 0){
                        $nf                  = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf             = trim(pg_fetch_result($resx,0,emissao));
                        //se fabrica atende direto posto seta a mesma nota
                        $nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
                        $link = 1;
                        $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));

                    }else{
                        //Foi alterado para buscar primeiro a NF com a peça caso não encontre, busca pela OS. HD 72977 HD 77790 HD 125880
                        $sqly = "SELECT tbl_faturamento.nota_fiscal                                  ,
                                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
                        FROM tbl_faturamento_item
                        JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                             AND  tbl_faturamento.fabrica   = $login_fabrica
                        JOIN   tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
                                        AND tbl_peca.fabrica         = $login_fabrica
                        JOIN tbl_os_troca ON tbl_os_troca.os = tbl_faturamento_item.os
                        WHERE  tbl_faturamento_item.pedido = $pedido
                        AND    (
                                (tbl_faturamento_item.os ISNULL OR tbl_faturamento_item.os IS NULL)
                                OR tbl_faturamento_item.os = $os
                                )
                        AND     tbl_faturamento.posto =  $posto
                        AND     tbl_os_troca.pedido   =  $pedido
                        ORDER   BY tbl_peca.descricao";
                        #echo "E4 - " . nl2br($sqly);
                        $resy = pg_query ($con,$sqly);

                        if (pg_num_rows ($resy) > 0){
                            $nf                  = trim(pg_fetch_result($resy,0,nota_fiscal));
                            $data_nf             = trim(pg_fetch_result($resy,0,emissao));
                            //se fabrica atende direto posto seta a mesma nota
                            $nota_fiscal_distrib = trim(pg_fetch_result($resy,0,nota_fiscal));
                            $data_nf_distrib     = trim(pg_fetch_result($resy,0,emissao));
                            $link = 1;
                        }else{
                            $nf                  = "Pendente";
                            $data_nf             = "";
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link                = 0;
                        }
                    }
                }
            }else{
                $nf                  = "";
                $data_nf             = "";
                $nota_fiscal_distrib = "";
                $data_nf_distrib     = "";
                $link = 0;
            }

        } else if (in_array($login_fabrica,array(35,45,74,80,86,147,151,160,162))) {
            if (in_array($login_fabrica, array(151))) {
                $whereFaturamento = " AND tbl_faturamento.cancelada IS NULL";
            }

            if (strlen ($nota_fiscal) == 0){
                if (strlen($pedido) > 0) {
                    $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                    TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
                                    tbl_faturamento.posto,
                                    tbl_faturamento.conhecimento,
                                    tbl_faturamento.faturamento

                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca
							AND     (tbl_faturamento_item.pedido_item = $pedido_item or tbl_faturamento_item.pedido_item isnull)
                            {$whereFaturamento};";
                    $resx = pg_query ($con,$sql);

                    if (pg_num_rows ($resx) > 0) {
                        $nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $saida   = trim(pg_fetch_result($resx,0,saida));
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                        $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                        $faturamento  = trim(pg_fetch_result($resx,0,faturamento));
                        $xdata_entrega = trim(pg_fetch_result($resx,0,previsao_chegada));
                        $link = 1;
                    }else{
                        //cadence relaciona pedido_item na os_item
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                        TO_CHAR(tbl_faturamento.saida, 'DD/MM/YYYY') AS saida,
                                        TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
                                        tbl_faturamento.posto,
                                        tbl_faturamento.conhecimento,
                                        tbl_faturamento.faturamento
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento_item.pedido      = $pedido
                                AND     tbl_faturamento_item.peca        = $peca
								AND     (tbl_faturamento_item.pedido_item = $pedido_item or tbl_faturamento_item.pedido_item isnull)
                                {$whereFaturamento};";
                        $resx = pg_query ($con,$sql);

                            if($login_fabrica == 160 and pg_num_rows($resx)==0 and !empty($peca_alternativa)){
                                 $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                        TO_CHAR(tbl_faturamento.saida, 'DD/MM/YYYY') AS saida,
                                        TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
                                        tbl_faturamento.posto,
                                        tbl_faturamento.conhecimento,
                                        tbl_faturamento.faturamento,
                                        tbl_pedido_item.obs
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                join    tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                                WHERE   tbl_faturamento_item.pedido      = $pedido
                                AND     tbl_faturamento_item.peca        = $peca_alternativa";

                                $resx = pg_query ($con,$sql);
                            }

                        if (pg_num_rows ($resx) > 0) {

                            $nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $xdata_entrega = trim(pg_fetch_result($resx,0,previsao_chegada));
                            $data_nf      = trim(pg_fetch_result($resx,0,emissao));
                            $saida        = trim(pg_fetch_result($resx,0,saida));
                            $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                            $faturamento  = trim(pg_fetch_result($resx,0,faturamento));
                            if($login_fabrica == 160){
                                $obs_alternativa = pg_fetch_result($resx, 0, obs);
                            }
                            $link         = 1;
                        }else{
                            $nf           = "Pendente";
                            $xdata_entrega      = "";
                            $data_nf      = "";
                            $saida      = "";
                            $conhecimento = "";
                            $link         = 1;
                        }
                    }
                }else{
                    $nf = "";
                    $data_nf = "";
                    $link = 0;
                }
            }else{
                $nf = $nota_fiscal;
            }
        /*############ DEMAIS FABRICANTES ############*/
        } else {

            if (strlen ($nota_fiscal) == 0){
                if (strlen($pedido) > 0) {
		    $cond_pedido_item = (empty($pedido_item)) ? " 1=2 " : " tbl_faturamento_item.pedido_item = $pedido_item ";
                    if($login_fabrica == 106){
                        $join_os_item = " JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item
                            JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_produto.os = $os ";
                    }

		    $leftAlternativa = "";
		    $condPeca = "";
		    if (in_array($login_fabrica, array(169,170))) {
			$leftAlternativa = "LEFT JOIN tbl_peca_alternativa ON tbl_peca_alternativa.peca_de = {$peca} AND tbl_peca_alternativa.fabrica = {$login_fabrica}";
			$condPeca = "AND (tbl_faturamento_item.peca = {$peca} OR tbl_faturamento_item.peca = tbl_peca_alternativa.peca_para)";
		    } else {
			$condPeca = "AND tbl_faturamento_item.peca = {$peca}";
		    } 

                    $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) AS nota_fiscal,
                                    trim(tbl_faturamento.conhecimento) AS conhecimento,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            $join_os_item
			    {$leftAlternativa}
                            WHERE   tbl_faturamento.pedido    = $pedido
			    AND ($cond_pedido_item or tbl_faturamento_item.pedido_item isnull)
                            {$condPeca}";
                        if($login_fabrica == 101 ) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
                        if($login_fabrica == 51 or ($login_fabrica == 81 and !empty($os_item))) $sql.="AND     (tbl_faturamento_item.os_item = $os_item or tbl_faturamento_item.os_item isnull)";
                        if(in_array($login_fabrica,array(88,99,117))) $sql.="AND     (tbl_faturamento_item.os = $os or tbl_faturamento_item.os isnull)";
                    if ($login_fabrica == 2) {
                        $sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal     ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                            FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                            JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
                            JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
                            JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                            AND tbl_faturamento.fabrica = $login_fabrica
                            WHERE    tbl_faturamento_item.peca = $peca";
                    }
                    $resx = pg_query ($con,$sql);

                    if (pg_num_rows ($resx) > 0) {
                        $nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                        $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                        $link = 1;
                    }else{
                        $condicao_01 = "";
                        if (strlen ($distribuidor) > 0) {
                            $condicao_01 = " AND tbl_faturamento.distribuidor = $distribuidor ";
                        }

                        $sql  = "SELECT
                                    trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
                                    tbl_faturamento.posto                            AS posto
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                $join_os_item
                                WHERE   tbl_faturamento_item.pedido = $pedido
                                AND     tbl_faturamento_item.peca   = $peca
								AND     ($cond_pedido_item or tbl_faturamento_item.pedido_item isnull)
                                $condicao_01 ";
                                if($login_fabrica == 101 ) $sql.=" AND   (  tbl_faturamento_item.os_item = $os_item or tbl_faturamento_item.os_item isnull) ";
                                if((!empty($telecontrol_distrib) and !empty($os_item))) $sql.="AND     (tbl_faturamento_item.os_item = $os_item or tbl_faturamento_item.os_item isnull)";
                                if(in_array($login_fabrica,array(88,99,117))) $sql.="AND     (tbl_faturamento_item.os = $os or tbl_faturamento_item.os isnull)";
                        //echo nl2br($sql);
			if (isset($novaTelaOs)) {
				$sql = "SELECT tbl_faturamento.nota_fiscal, TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao, tbl_faturamento.posto
					FROM tbl_faturamento
					INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
					INNER JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item
					INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					WHERE tbl_faturamento_item.pedido = $pedido
					and   tbl_faturamento_item.peca = $peca
					AND tbl_faturamento.cancelada IS NULL
					AND tbl_os_produto.os = $os";
			}
                        $resx = pg_query ($con,$sql);

                        if (pg_num_rows ($resx) > 0) {
                            $nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf      = trim(pg_fetch_result($resx,0,emissao));
                            $link         = 1;
                        }else{
                            //Faturamento manual do distrib
                            $sqlm = "SELECT trim(tbl_faturamento.nota_fiscal)        AS nota_fiscal ,
											TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
											tbl_faturamento.faturamento,
											conhecimento
                                    FROM    tbl_faturamento
                                    JOIN    tbl_faturamento_item USING (faturamento)
                                    WHERE   tbl_faturamento.fabrica = 10
                                    AND     tbl_faturamento_item.os = $os
									AND     tbl_faturamento_item.peca = $peca";
							if(!empty($telecontrol_distrib)  and !empty($os_item)) $sqlm.=" AND (tbl_faturamento_item.os_item = $os_item or tbl_faturamento_item.os_item isnull) ";
							$sqlm .= " LIMIT 1";
                            $resm = pg_query ($con,$sqlm);
                            if (pg_num_rows ($resm) > 0) {
                                $nf           = trim(pg_fetch_result($resm,0,nota_fiscal));
                                $data_nf      = trim(pg_fetch_result($resm,0,emissao));
                                $faturamento  = trim(pg_fetch_result($resm,0,faturamento));
                                $conhecimento = trim(pg_fetch_result($resm,0,conhecimento));
                                $link    = 1;
                                $manual_ja_imprimiu = 1;
                                if($nf == '000000'){
                                    $data_nf = "--";
                                }
                            }else{
                                $nf      = "Pendente";
                                $data_nf = "";
                                $link    = 1;
                                if($login_fabrica==6 and strlen($data_finalizada)>0){ //hd 3437
                                    $nf = "Atendido";
                                }

                                $sqlPedidoCancelado = "
                                    SELECT p.status_pedido
                                    FROM tbl_pedido p
                                    INNER JOIN tbl_pedido_item pi ON pi.pedido = p.pedido
                                    WHERE p.fabrica = {$login_fabrica}
                                    AND pi.pedido_item = $pedido_item
                                    AND (p.status_pedido = 14 OR pi.qtde_cancelada = pi.qtde)
                                ";
                                $resPedidoCancelado = pg_query($con, $sqlPedidoCancelado);

                                if (pg_num_rows($resPedidoCancelado) > 0) {
                                    $nf = "Cancelada";
                                    $data_nf = "";
                                }
                            }
                        }
                    }
                }else{
                    $nf = "";
                    $data_nf = "";
                    $link = 0;
                }
            }else{
                $nf = $nota_fiscal;
            }
        }
        //HD 18479
        if($login_fabrica==3){
            if((strlen($pedido)>0 AND strlen($peca)>0) AND $nf=="Pendente"){
                $sql = "SELECT motivo
                        FROM   tbl_pedido_cancelado
                        WHERE  pedido = $pedido
                        AND    peca   = $peca
                        AND    posto  = $posto;";
                        //echo nl2br($sql)."<br><br>";
                $resx = pg_query ($con,$sql);
                if (pg_num_rows ($resx) > 0) {
                    $motivo = pg_fetch_result($resx,0,motivo);
                    $nf           = "<a href=\"#\" title=\"$motivo\">Cancelada</a>";
                    $data_nf      = "-";
                    $link         = 1;
                }
            }

            if(strlen($nota_fiscal_distrib)==0 AND $nf<>'Pendente' AND strlen($pedido)>0 AND strlen($peca)>0 AND strlen($posto)>0){
                $sql = "SELECT motivo
                        FROM   tbl_pedido_cancelado
                        WHERE  pedido = $pedido
                        AND    peca   = $peca
                        AND    posto  = $posto;";
                        //echo nl2br($sql)."<br><br>";
                $resx = pg_query ($con,$sql);
                if (pg_num_rows ($resx) > 0) {
                    $motivo = pg_fetch_result($resx,0,motivo);
                    $nota_fiscal_distrib = "<a href='#' title='$motivo'>Cancelada</a>";
                }
            }
        }
        if(in_array($login_fabrica,array(52,74,88,99,101,127))){
            if (strlen($pedido)>0 AND strlen($peca)>0 AND strlen($posto)>0 and !empty($pedido_item)) {
		    $campo_os = ($login_fabrica != 52) ? " AND os = $os " : "";

			$sql = "SELECT tbl_pedido_cancelado.peca
				FROM   tbl_pedido_cancelado
				JOIN tbl_pedido_item USING(pedido_item)
				WHERE  tbl_pedido_cancelado.pedido = $pedido
				AND    tbl_pedido_cancelado.peca   = $peca
				AND    tbl_pedido_cancelado.posto  = $posto
				AND (
					(tbl_pedido_cancelado.pedido_item = $pedido_item AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) = 0) 			        OR pedido_item isnull
				)
				{$campo_os}
			";

                $resx = pg_query ($con,$sql);
                if (pg_num_rows ($resx) > 0) {
                    $nf = "Cancelada";
                    $data_nf = "";
                }
            }
        }
        /*====--------- FIM DAS NOTAS FISCAIS ----------===== HD 8973 */

        $devolucao_obrigatoria  = pg_fetch_result($res,$i,'devolucao_obrigatoria');
        if($login_fabrica == 153){
           $parametros_adicionais = json_decode($parametros_adicionais, true);
        }
    ?>

    <tr class="conteudo"

        <?php
            if ($devolucao_obrigatoria == "t" and in_array($login_fabrica, array(50, 51))){
                $exibe_legenda++;
                echo " style='background-color:#FFC0D0'";
            }
            if($parametros_adicionais['recall'] == 1){
                echo " style='background-color:#98FB98'";
                $recall = true;
            }
        ?>
    >
    <?php
        if($login_fabrica == 138){
            $model = ModelHolder::init('OsItem');
            $osItem = pg_fetch_result($res,$i,'os_item');
            $osItem = $model->select($osItem);
            $content = '';
            if($osItem['servicoRealizado'] != 11120){
                $sql = 'SELECT tbl_produto.referencia
                        FROM tbl_produto
                        INNER JOIN tbl_os_produto
                            ON (tbl_produto.produto = tbl_os_produto.produto)
                        INNER JOIN tbl_os_item
                            ON (tbl_os_item.os_produto = tbl_os_produto.os_produto)
                        WHERE tbl_os_item.os_item = :osItem';
                $params = array(':osItem'=>$osItem['osItem']);
                $result = $model->executeSql($sql,$params);
                $content = $result[0]['referencia'];
            }
            $td = HtmlHelper::inlineBuild('td',array('style'=>'text-align:left;padding:5px;'),$content);
            $td->render();
        }
    if($os_item_subconjunto == 't') {
        echo "<TD style=\"text-align:left;\">".pg_fetch_result($res,$i,'subproduto_referencia') . " - " . pg_fetch_result($res,$i,subproduto_descricao)."</TD>";
        echo "<TD style=\"text-align:center;\">".pg_fetch_result($res,$i,'posicao')."</TD>";
    }
    // $status_os -> variavel pegada lá em cima
    $msg_peca_intervencao="";

    $bloqueada_pc           = pg_fetch_result($res,$i,'bloqueada_pc');
    $servico_realizado_peca = pg_fetch_result($res,$i,'servico_realizado_peca');
    $retorna_conserto       = pg_fetch_result($res,$i,'retorna_conserto');

    if ((in_array($login_fabrica, array(3,11,172))) AND ( $bloqueada_pc=='t' OR $retorna_conserto=='t')){

        if ( in_array($login_fabrica, array(11,172)) ) {
            $id_servico_realizado           = 61;
            $id_servico_realizado_ajuste    = 498;
        }
        if ($login_fabrica==3) {
            $id_servico_realizado           = 20;
            $id_servico_realizado_ajuste    = 96;
        }

        if (($status_os=='62' OR $status_os=='87' OR $status_os=='72' OR $status_os=='116') AND $servico_realizado_peca==$id_servico_realizado){
            $msg_peca_intervencao=" <b style='font-weight:normal;color:$cor_intervencao;font-size:10px'>(aguardando autorização da fábrica)</b>";
        }

        if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado){
            $msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(autorizado pela fábrica)</b>";
            $cancelou_peca = "sim";
        }

        if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado_ajuste){
            $msg_peca_intervencao=" <b style='font-weight:normal;color:#CC0000;font-size:10px'>(pedido cancelado pela fábrica)</b>";
            $cancelou_peca = "sim";
        }

        if (($status_os=='62' OR $status_os=='73' OR $status_os=='87' OR $status_os=='116') AND strlen($pedido) > 0 AND $servico_realizado_peca==$id_servico_realizado) {
            $msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(autorizado pela fábrica)</b>";
            $cancelou_peca = "sim";
        }
    }

    if($excluida=='t' and strtolower($nf) == 'pendente') $nf= "Cancelada";

    ?>
    <? // HD 23036
    if( in_array($login_fabrica, array(11,172)) ){?>
        <TD style="text-align:center;"><? echo pg_fetch_result($res,$i,'nome_admin'); ?></TD>
        <? } ?>
    <TD style="text-align:left;"><? echo pg_fetch_result($res,$i,'referencia_peca') . $peca_referencia_fabrica . " - " . pg_fetch_result($res,$i,'descricao_peca');  echo $admin_digitou.$msg_peca_intervencao; ?></TD>
    <?
    if ($login_fabrica == 50 ) {
        $nome_fornecedor = '';
        $sql_f = "SELECT nome_fornecedor FROM tbl_ns_fornecedor WHERE numero_serie in (SELECT  numero_serie FROM tbl_numero_serie WHERE serie = '$serie') and peca = $peca ";
        $res_f = pg_query($con,$sql_f);
        //echo $sql_f;

        if (pg_num_rows($res_f)>0) {
            $nome_fornecedor = pg_fetch_result($res_f, 0, 'nome_fornecedor');

        }
        ?>
        <TD style="text-align:center;"><? echo $nome_fornecedor ?></TD>
        <?
    }
    ?>
    <TD style="text-align:center;"><? echo pg_fetch_result($res,$i,'qtde') ?></TD>

    <?php
    if(in_array($login_fabrica,array(148,166)) || (in_array($login_fabrica, array(156,161,167,203)) && $desc_tipo_atendimento == "Orçamento") OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia'))  {

        if(in_array($login_fabrica, [167, 203])){
            $qtde_pecas     = (strlen(pg_fetch_result($res, $i, "qtde")) > 0) ? pg_fetch_result($res, $i, "qtde") : 0;
            $preco_total    = (strlen(pg_fetch_result($res, $i, "preco")) > 0) ? pg_fetch_result($res, $i, "preco") : 0;

            $preco_unitario = number_format($preco_total , 2,",",".");
            $preco_total_aux = $preco_total*$qtde_pecas;

            $valor_total_pecas += $preco_total_aux;

            $dados_orcamento_email[$i]["qtde_pecas"]                    = $qtde_pecas;
            $dados_orcamento_email[$i]["preco_total"]                   = $preco_total;
            $dados_orcamento_email[$i]["preco_unitario"]                = $preco_unitario;
            $dados_orcamento_email[$i]["preco_total_aux"]               = $preco_total_aux;
            $dados_orcamento_email[$i]["servico_realizado_descricao"]   = utf8_encode($servico_realizado_descricao);
            $dados_orcamento_email[$i]["descricao_peca"]                = utf8_encode($descricao_peca);

        }else{
            $qtde_pecas     = (strlen(pg_fetch_result($res, $i, "qtde")) > 0) ? pg_fetch_result($res, $i, "qtde") : 0;
            $preco_total    = (strlen(pg_fetch_result($res, $i, "custo_peca")) > 0) ? pg_fetch_result($res, $i, "custo_peca") : 0;
            $ipi            = (strlen(pg_fetch_result($res, $i, "ipi")) > 0) ? pg_fetch_result($res, $i, "ipi") : 0;

            if($login_fabrica == 161){
                $preco_total = $preco_total + ($preco_total*($ipi/100));
            }
            $preco_unitario = number_format($preco_total , 2,",",".");
            $preco_total_aux = $preco_total*$qtde_pecas;

            if ($login_fabrica == 163) {
                $preco_total    = (strlen(pg_fetch_result($res, $i, "custo_peca")) > 0) ? pg_fetch_result($res, $i, "custo_peca") : 0;
                $preco_unitario = (strlen(pg_fetch_result($res, $i, "preco")) > 0) ? pg_fetch_result($res, $i, "preco") : 0;

                $preco_total_aux =$preco_unitario*$qtde_pecas;
                $valor_total_pecas += $preco_total_aux;

                $preco_unitario = number_format($preco_unitario , 2,",",".");
                $preco_total_aux = number_format($preco_total_aux , 2,",",".");
                //$valor_total_pecas = number_format($valor_total_pecas , 2,",",".");
            } else {
                $valor_total_pecas += $preco_total_aux;
            }
        }

        echo "<td style='text-align: center;'>";
            echo $preco_unitario;
        echo "</td>";
        echo "<td style='text-align: center;'>";
            echo $preco_unitario = number_format($preco_total_aux , 2,",",".");
        echo "</td>";
    }

    ?>

    <?
    if ($mostrar_valor_pecas/* and ($nf != 'Cancelada' and $nf != ''*/) { //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item
	?>
        <TD style='text-align:right;'><?=number_format(pg_fetch_result($res,$i,'preco_peca'),2,",",".")?></TD>
        <TD style='text-align:right;'><?=number_format(pg_fetch_result($res,$i,'total_peca'),2,",",".")?></TD>
    <?}?>
    <TD style="text-align:center;" title="<?echo 'Data da liberação:'.$data_liberacao_pedido ?>"><? echo pg_fetch_result($res,$i,digitacao_item) ?></TD>

    <? if (!in_array($login_fabrica, array(114,115,116,117,121,122,123,124,125,126,127,128,129,131,134,138,140,141,144)) && !isset($novaTelaOs)) { ?>
        <TD style="text-align:left;"><?= pg_fetch_result($res,$i,defeito) ?></TD>
    <? } else if(in_array($login_fabrica, array(151,169,170))) { ?>
        <TD style="text-align:left;"><?= pg_fetch_result($res,$i,defeito) ?></TD>
    <? } ?>

    <?php
        $servico_peca = pg_fetch_result($res,$i,'servico_realizado_descricao');

        if($login_fabrica == 1 AND $status_os == 110 AND !empty($parametros_adicionais)){
            $parametros_adicionais = json_decode($parametros_adicionais,true);
            $servico_peca = ($parametros_adicionais['debito_peca'] == "t") ? "Gerou Débito" : $servico_peca;
            $servico_peca = ($parametros_adicionais['coleta_peca'] == "t") ? "Coleta de Peça" : $servico_peca;
        }

        if($login_fabrica == 30 && $servico_peca == "Troca de Produto"){
            if(strlen($admin_autoriza) > 0){

                $sql_ressarcimento = "SELECT ressarcimento FROM tbl_servico_realizado WHERE servico_realizado = {$servico_realizado_peca}";
                $res_ressarcimento = pg_query($con, $sql_ressarcimento);

                $ressarcimento_status = pg_fetch_result($res_ressarcimento, 0, "ressarcimento");

                if($ressarcimento_status == "t"){

                    $servico_peca = "&nbsp; Ressarcimento ";

                }else{

                    $sqlAd = "
                        SELECT  nome_completo
                        FROM    tbl_admin
                        WHERE   admin = $admin_autoriza
                    ";
                    $resAd = pg_query($con,$sqlAd);
                    $nomeAdminAutoriza = pg_fetch_result($resAd,0,nome_completo);

                    $servico_peca .= " <b style='font-weight:normal;color:#000000;font-size:10px'>(Aprovado por $nomeAdminAutoriza)</b>";

                }

            } else {

                $sql = "SELECT afirmativa FROM tbl_laudo_tecnico_os WHERE os = {$os} ORDER BY laudo_tecnico_os DESC LIMIT 1";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){

                    $afirmativa = pg_fetch_result($res, 0, "afirmativa");

                    switch ($afirmativa) {
                        case 't': $servico_peca = "Laudo Aprovado"; break;
                        case 'f': $servico_peca = "Troca Recusada"; break;
                        default: $servico_peca = "Aguardando Laudo"; $grava_laudo_botao = 1; break;
                    }

                }else{

                    $servico_peca = "Aguardando Laudo";
                    $grava_laudo_botao = 1;

                }

                /* $sqlRecusaTroca = "
                    SELECT  os
                            status_os
                    FROM    tbl_os_status
                    WHERE   os = $os
                ";
                $resRecusaTroca = pg_query($con,$sqlRecusaTroca);
                $statusOsTroca = pg_fetch_result($resRecusaTroca,0,status_os);

                if ($statusOsTroca == 194) {
                    $servico_peca = "Troca Recusada";
                } else if (!empty($statusOsTroca) && $statusOsTroca != 194){
                    $servico_peca = "Aguardando aprovação";
                } else if (empty($statusOsTroca)){
                    $servico_peca = "Aguardando Laudo";
                    $grava_laudo_botao = 1;
                } */

            }
        }

    ?>

    <TD style="text-align:left;"><? echo  $servico_peca;?></TD>

    <?php if($login_fabrica == 87){?>
        <td>
            <?php
                if(!empty($soaf)){
                    $sql_soaf = "SELECT descricao from tbl_tipo_soaf WHERE fabrica = $login_fabrica  AND tipo_soaf = $soaf;";
                    $res_soaf = pg_query($con, $sql_soaf);
                    if(pg_num_rows($res_soaf)){
                        echo pg_fetch_result($res_soaf, 0, 'descricao');
                    }else echo "&nbsp;";
                }else echo "&nbsp;";
            ?>
        </td>
    <?php }?>

    <TD style="text-align:CENTER;">
        <? if ($login_fabrica==43){?>
                <a href='pedido_admin_consulta.php?pedido=<? echo $pedido ?>' target='_blank'><? echo $pedido;
            }elseif (in_array($login_fabrica , array(142))){

                echo "<a href='print_pedido.php?pedido=$pedido' target='_blank'>$pedido</a>";

            }else{

                $pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;
            ?>
                <a href='pedido_admin_consulta.php?pedido=<? echo $pedido ?>' target='_blank'><? if ($login_fabrica == 1){ echo $pedido_blackedecker;} else{ echo $pedido_aux;}
            } ?></a>&nbsp;
    </TD>

    <TD style="text-align:CENTER;" nowrap>

    <?
        $temCredito = false;
        if ($login_fabrica == 148) {
            $sqlCredito = "SELECT os
                             FROM tbl_extrato_lancamento
                            WHERE tbl_extrato_lancamento.os = {$os}
                              AND tbl_extrato_lancamento.fabrica = {$login_fabrica}
                              AND tbl_extrato_lancamento.posto = {$posto}
                              AND tbl_extrato_lancamento.lancamento = 493";
                              /*AND tbl_extrato_lancamento.lancamento = 486*/
            $resCredito = pg_query($con, $sqlCredito);
            if (pg_num_rows($resCredito) > 0) {
                $temCredito = true;
            }
        }
        if (strtolower($nf) <> 'pendente' and strtolower($nf) <> 'atendido'){

            if ($link == 1) {
                if( in_array($login_fabrica, array(11,50,172)) ) {
                    echo "<a href='nota_fiscal_detalhe.php?pedido=$pedido&peca=$peca' target='_blank'>$nf</a>";
                }else {
                    if ($login_fabrica<>87){
                        if ($temCredito && $login_fabrica == 148) {
                            echo "Crédito Gerado &nbsp;";
                        } else {
                            echo "$nf &nbsp;";
                        }

                    } else {
                        echo $nf . " - " . $data_nf . "$nbsp";
                    }
                }
            }else{
                if ($login_fabrica<>87){
                    if($login_fabrica == 81 AND $posto == 20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0){ //hd_chamado=2788473
                        echo $nota_fiscal_saida;
                    }else{
                        if ($temCredito && $login_fabrica == 148) {
                            echo "Crédito Gerado &nbsp;";
                        } else if ($login_fabrica == 101 && $os_trocada === true && $servico_peca != "Troca de Produto") {
                            echo "Cancelada";
                        } else {
                            echo "$nf &nbsp;";
                        }
                    }
                } else {
                    echo $nf . " - " . $data_nf . "$nbsp";
                }

                //echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
            }
        }else{
            if((in_array($login_fabrica,$fabricas_ped_canc_sem_os) or $login_fabrica  > 100) and $login_fabrica <> 114 and empty($telecontrol_distrib)){
                if(strlen($peca)>0 AND strlen($pedido)>0){
                    $sql  = "SELECT SUM(qtde) AS qtde
                        FROM tbl_pedido_cancelado
                        WHERE peca=$peca and pedido=$pedido
                        AND (os = $os or os isnull)";
					$sql .= (!empty($pedido_item)) ? " AND (pedido_item = $pedido_item or pedido_item isnull) " : "";
                    $resY = pg_query ($con,$sql);

                    #hd_chamado=2895822
                        $qtde_pecas_pedidas = pg_fetch_result($res,$i,'qtde');
                        $qtde_pecas_canceladas = pg_fetch_result($resY, 0, 'qtde');
                    # fim - hd_chamado=2895822
                    if (pg_num_rows ($resY) > 0 AND $qtde_pecas_pedidas <= $qtde_pecas_canceladas) {
                        echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
                    }else{
                        echo "$nf &nbsp;";
                    }
                }
            }else{

                if(strlen($peca)>0 AND strlen($pedido)>0 AND strlen($os)>0) {
                    if($login_fabrica == 24) {
                        $sql_verif  = "SELECT * FROM tbl_pedido_cancelado WHERE pedido=$pedido AND peca=$peca AND os=$os";
                        $res_verif = pg_query ($con,$sql_verif);
                        if (pg_num_rows ($res_verif) > 0) {
                            $tem_os_cancelada   = pg_fetch_result ($res_verif,0,os);


                            $tem_os_cancelada_os ="";
                            if($tem_os_cancelada == $os) {
                                $tem_os_cancelada_os = " AND os =".$tem_os_cancelada;
                            }
                            //echo "TEM OS CANCELADA =".$tem_os_cancelada."<BR><BR>";
                            $sql  = "SELECT * FROM tbl_pedido_cancelado WHERE  peca=$peca AND pedido=$pedido $tem_os_cancelada_os";
                            //echo nl2br($sql)."<br><br><br><br>";
                            $resY = pg_query ($con,$sql);
                            if (pg_num_rows ($resY) > 0) {
                                echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
                            }else{
                                $sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item AND tbl_embarque.faturar IS NOT NULL";

                                $resX = pg_query ($con,$sql);
                                if (pg_num_rows ($resX) > 0) {
                                    echo "Embarque " . pg_fetch_result ($resX,0,embarque) . " - " . pg_fetch_result ($resX,0,faturar) ;
                                }else{
                                    echo "$nf &nbsp;";
                                }
                            }

                        }
                    } else if ($login_fabrica == 81) {
                        $xsql = "SELECT tbl_pedido_item.qtde_cancelada, tbl_pedido_item.qtde FROM tbl_pedido_item JOIN tbl_os_item USING(pedido_item)
                                WHERE tbl_os_item.os_item = $os_item";
                        $xres = pg_query($con, $xsql);

                        if (pg_fetch_result($res,$i,qtde) == pg_fetch_result($xres, 0, "qtde_cancelada")) {
                            echo "Cancelado";
                        } else {
                            $sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item";
                            $resX = pg_query ($con,$sql);
                            if (pg_num_rows($resX) > 0) {
                                if($login_fabrica == 81 AND $posto == 20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0 AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
                                    echo $nota_fiscal_saida;
                                }else{
                                    echo "Embarque " . pg_fetch_result ($resX,0,embarque) . " - " . pg_fetch_result ($resX,0,faturar) ;
                                }
                            } else {
                                if($login_fabrica == 81 AND $posto == 20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0 AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
                                    echo $nota_fiscal_saida;
                                }else{
                                    echo "$nf &nbsp;";
                                }

                            }
                        }
                    } else {
                        if($login_fabrica == 101){
                            $sql = "SELECT *
                                    FROM tbl_pedido_cancelado
                                    JOIN tbl_pedido_item ON tbl_pedido_cancelado.pedido = tbl_pedido_item.pedido
                                    JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item AND tbl_os_item.os_item = $os_item
                                    WHERE tbl_pedido_cancelado.peca=$peca
                                    AND tbl_pedido_cancelado.pedido=$pedido
                                    AND (tbl_pedido_cancelado.os=$os or tbl_pedido_cancelado.os  isnull)
                                    AND tbl_pedido_item.qtde_cancelada > 0";
                        }else{
							$sql  = "SELECT *
								FROM tbl_pedido_cancelado ";
							$sql .= (!empty($pedido_item)) ? "JOIN tbl_pedido_item USING( pedido,peca )" : "";
							$sql .=	"WHERE (os=$os or os isnull)
								AND peca=$peca
								and pedido=$pedido";
								$sql .= (!empty($pedido_item)) ? " AND ((tbl_pedido_cancelado.pedido_item = $pedido_item AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) = 0) or tbl_pedido_cancelado.pedido_item isnull) " : "";
                        }

                        $resY = pg_query ($con,$sql);
                        if (pg_num_rows ($resY) > 0) {
                            $motivo = pg_fetch_result ($resY,0,motivo);
                            echo "<acronym title='".$motivo."'>Cancelado</acronym>" ;
                        }else{
                            if(in_array($login_fabrica,array(51,81,114)) or $telecontrol_distrib){
                                $sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item";
                            }else{
                                $sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item AND tbl_embarque.faturar IS NOT NULL";
                            }
                            $resX = pg_query ($con,$sql);
                            if (pg_num_rows ($resX) > 0) {
                                echo "Embarque " . pg_fetch_result ($resX,0,embarque) . " - " . pg_fetch_result ($resX,0,faturar) ;
                            }else{
                                if ($login_fabrica<>87){
                                echo "$nf &nbsp;";
                                } else {
                                    echo $nf . " - " . $data_nf . "$nbsp";
                                }
                            }
                        }
                    }
                }
            }
        }
    ?>
    </TD>
    <?
        if ($login_fabrica<>87 AND $login_fabrica <> 3){

            if($login_fabrica == 81 AND $posto == 20682 AND $consumidor_revenda == "REVENDA" AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
                echo "<td style='text-align:center;''>
                    $data_nf_saida &nbsp;
                </td>";
            }else{
    ?>
                <td style="text-align:center;"><?= $data_nf ?>&nbsp;</td>
    <?
            }
        }

    ?>

    <?php
        if ($login_fabrica == 1 AND $reembolso_peca_estoque == 't' and empty($tipo_atendimento)) {

            $estoque    = ucfirst($parametros_adicionais_pecas["estoque"]);
            $previsao   = mostra_data($parametros_adicionais_pecas["previsao"]);

            // regra para o obsoleto
            if (strlen($peca_fora_linha) > 0) {
                $estoque = "OBSOLETO";
                $previsao = " - ";
            }
            // regra para o subst
            if (strlen($para) > 0) {
                $estoque = "SUBST";
                $previsao = " - ";
            }

    ?>
        <td style="text-align:center;"><?php echo $estoque;?>&nbsp;</td>
        <td style="text-align:center;"><?php echo $previsao;?>&nbsp;</td>
    <?php }?>

    <?
        if($login_fabrica == 3){
    ?>
        <td style="text-align:center;"><?= $nota_fiscal_distrib ?>&nbsp;</td>
    <?
        }
    ?>


    <?php
        if($login_fabrica == 125){

            if($data_nf > 0){
                $prazo_entrega = '<span data-title="Lembrando que este prazo oscila conforme o fluxo de entregas e em função de fins de semana e feriados." class="previsao_entrega">10 dias a partir da emissão da Nota Fiscal</span>';
            }
    ?>
        <td style="text-align:center;"><?= $prazo_entrega ?>&nbsp;</td>
    <?php
        }
        if($login_fabrica == 156){
    ?>
        <td style="text-align:center;"><?= $parametros_adicionais ?>&nbsp;</td>
    <?php
        }
    ?>

    <? //Gustavo 12/12/2007 HD 9095


    if (in_array($login_fabrica,array(11,35,45,74,80,86,151,157,160,162,172))){

	    if($login_fabrica != 151){
		echo "<TD class='conteudo' style='text-align:CENTER;'>";
		if (strlen ($faturamento) > 0 && !empty($data_nf)) {
		    $sql_verifica_conhecimento = "SELECT conhecimento AS conhecimento FROM tbl_faturamento_correio
						    WHERE fabrica = $login_fabrica and faturamento = $faturamento";
		    $res_verifica_conhecimento = pg_query($con, $sql_verifica_conhecimento);
		// $conhecimento = 'SW338533166BR';
		    if(pg_num_rows($res_verifica_conhecimento)>0){
			echo "<A HREF='./relatorio_faturamento_correios?conhecimento=$conhecimento' rel='shadowbox'>";
		    }else{
			echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
		    }
		    echo $conhecimento;
		    echo "</A>";
		}
		echo "</TD>";
	    }

        if (in_array($login_fabrica, array(147,151))) {
            echo "<TD style='text-align:CENTER;' nowrap>";
            if (preg_match("/^\[.+\]$/", $conhecimento)) {
                $conhecimento = json_decode($conhecimento, true);
                $codigos_rastreio = array();
                foreach ($conhecimento as $key => $codigo_rastreio) {
                    if(pg_num_rows($res_verifica_conhecimento)>0){
                        $codigos_rastreio[] = "<A HREF='./relatorio_faturamento_correios?conhecimento=$codigo_rastreio' rel='shadowbox'>$codigo_rastreio</A>";
                    }else{
                        $codigos_rastreio[] = "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$codigo_rastreio' target = '_blank'>$codigo_rastreio</A>";
                    }
                }

                echo implode(", ", $codigos_rastreio);
            } else {
                if(pg_num_rows($res_verifica_conhecimento)>0){
                    echo "<A HREF='./relatorio_faturamento_correios?conhecimento=$conhecimento' rel='shadowbox'>";
                }else{
                    echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
                }
                echo $conhecimento;
                echo "</A>";
            }
            echo "</TD>";
        }
    }

    if($telecontrol_distrib and !empty($os_item)){
         $sqlConhecimento = "SELECT tbl_os_item.os_item, tbl_etiqueta_servico.etiqueta
                                FROM tbl_os_item
                                INNER JOIN tbl_embarque_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
                                INNER JOIN tbl_etiqueta_servico ON tbl_embarque_item.embarque = tbl_etiqueta_servico.embarque
                                INNER JOIN tbl_embarque on tbl_embarque_item.embarque = tbl_embarque.embarque
                                WHERE tbl_os_item.os_item = $os_item";
            $resConhecimento = pg_query($con, $sqlConhecimento);
            if(pg_num_rows($resConhecimento)){
                $conhecimento = pg_fetch_result($resConhecimento, 0, etiqueta);
			}else{
				$sqlConhecimento = "SELECT conhecimento FROM tbl_faturamento_item join tbl_faturamento using(faturamento) WHERE os = $os and os_item = $os_item ";
				$resConhecimento = pg_query($con, $sqlConhecimento);
				if(pg_num_rows($resConhecimento)){
					$conhecimento = pg_fetch_result($resConhecimento, 0, 'conhecimento');
				}else{
					$conhecimento = "";
				}
            }

        echo "<TD style='text-align:CENTER;'>";
            echo "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
                echo "$conhecimento";
            echo "</a>";
        echo "</TD>";

    }



    if (in_array($login_fabrica,array(151))) {
        echo "<TD style='text-align:CENTER;' nowrap>";
		if (!empty($conhecimento)) {
		            echo $saida;
		}
        echo "</TD>";
    }
    if (in_array($login_fabrica,array(35))) {
        echo "<TD style='text-align:CENTER;' nowrap>";
            echo $xdata_entrega;
        echo "</TD>";
        echo "<TD style='text-align:CENTER;' nowrap>";
            echo $po_peca;
        echo "</TD>";
    }

    ?>

    <? //nf do distribuidor - chamado 141
    if ($login_fabrica==3) {
        echo "<TD style='text-align:CENTER;' nowrap>";

        if (strlen($nota_fiscal_distrib) > 0) {
            echo "<acronym title='Nota Fiscal do Distribuidor.' style='cursor:help;'> $nota_fiscal_distrib"." - ".$data_nf_distrib."</acronym>";
        } else {
            //se não tiver nota do distrib verifica se está em embarque e exibe numero do embarque
            $sql  = "SELECT tbl_embarque.embarque,
                            to_char(liberado ,'DD/MM/YYYY') as liberado,
                            to_char(embarcado ,'DD/MM/YYYY') as embarcado,
                            faturar
                    FROM tbl_embarque
                    JOIN tbl_embarque_item USING (embarque)
                    WHERE tbl_embarque_item.os_item = $os_item ";

            // HD 7319 Paulo alterou para mostrar dia que liberou o embarque
            $resX = pg_query ($con,$sql);
            if (pg_num_rows ($resX) > 0) {
                $liberado  = pg_fetch_result($resX,0,liberado);
                $embarcado = pg_fetch_result($resX,0,embarcado);
                $faturar   = pg_fetch_result($resX,0,faturar);

                echo "<acronym title='Embarque do Distribuidor.' style='cursor:help;'>";
                if(strlen($embarcado) > 0 and strlen($faturar) == 0){
                    echo "Embarque " . pg_fetch_result ($resX,0,embarque);
                } else {
                    echo "Embarcada ". pg_fetch_result($resX,0,liberado);
                }
                echo "</acronym>";
            }else{
                echo "<acronym title='Embarque do Distribuidor.' style='cursor:help;'>";
                echo "$nota_fiscal_distrib";
                echo "</acronym>";
                // HD 7319 Fim
            }
        }
        echo "</TD>";

        echo "<td style='text-align:CENTER;' nowrap>";
            $sqlPF = "SELECT
                        tbl_os_item.os_item
                    FROM tbl_os_item
                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                    WHERE tbl_os.os = {$os}
                    AND tbl_os_item.os_item = {$os_item}
                    AND tbl_os_item.servico_realizado = 20
                    AND tbl_os_item.parametros_adicionais ILIKE '%\"item_foto_upload\":\"t\"%'";
            $resPF = pg_query($con, $sqlPF);

            if (pg_num_rows($resPF) > 0) {
                echo "<span style='color: #63798D; font-weight: bold; text-decoration: none; cursor: pointer;' onclick='window.open(\"mostra_upload.php?os={$os}&os_item={$os_item}\", \"_blank\");' title='OS possui peças com Upload de fotos obrigatório'>Ver anexos</span>";
            }
        echo "</td>";

            //$conhecimento = "DM168150394BR";

            $regex = '/(([A-Z]{2})[0-9]{9}([A-Z]{2}))/';
            if (preg_match($regex, $conhecimento)) {
                $var_correios = "correios";
            }else{
                $var_correios = '';
            }

            if($var_correios == "correios"){
                echo "<TD class='conteudo' >";
                    echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
                     echo $conhecimento;
                    echo "</A>";
                echo "</TD>";
            }else{
                echo "<TD class='conteudo' >";
                     echo $conhecimento;
                echo "</TD>";
            }
    }
    ?>
    </tr>
<?php
    if ($login_fabrica == 1 && !empty($motivo)) {
?>
    <tr>
        <td class = "conteudo" colspan="100%"> Motivo Cancelamento: <?=utf8_decode($motivo)?> </td>
    </tr>
<?php
    $motivo = "";
    }

    if (in_array($login_fabrica,array(169,170)) && !empty($conhecimento)) { ?>
        <tr>
            <td class="conteudo" colspan="8">TRANSPORTADORA: <?= $conhecimento; ?></td>
        </tr>
    <? }

    if($login_fabrica == 160 and strlen(trim($obs_alternativa))>0){ ?>
        <tr>
            <td class='conteudo' colspan="8" style="color:red"><img src='imagens/setinha_linha.gif' border='0' /> <?= $obs_alternativa?></td>
        </tr>
    <?
    }
    //HD 8412
    /**
     * @since HD 749085 - Black
     */
    $mostra_obs = array(1, 3, 14, 30, 35);
    if (in_array($login_fabrica, $mostra_obs) and strlen($obs) > 0) {
        if (in_array($login_fabrica, array(30))) {
            echo "<tr><td class='conteudo' colspan='100%'><img src='imagens/setinha_linha.gif' border='0' /> Motivo novo lançamento: $obs</td></tr>";
        } else {
            echo "<tr><td class='conteudo' colspan='100%'>Obs: $obs</td></tr>";
        }
    }

}

    if(in_array($login_fabrica, array(156,161,167,203)) && $desc_tipo_atendimento == "Orçamento" OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') OR in_array($login_fabrica,array(166))){

        if($login_fabrica == 161 OR in_array($login_fabrica, [167, 203]) OR $login_fabrica == 166){
            if($login_fabrica == 161){
                $condAdicionais = "AND valores_adicionais notnull";
            }else{
                $condAdicionais = "";
            }
            $sql_adicionais = "SELECT valores_adicionais, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os $condAdicionais";
            $res_adicionais = pg_query($con, $sql_adicionais);

            if(pg_num_rows($res_adicionais) > 0){

                if(in_array($login_fabrica, [167, 203]) OR $login_fabrica == 166){
                    $valores_adicionais = pg_fetch_result($res_adicionais, 0, "campos_adicionais");
                    $valores_adicionais = json_decode($valores_adicionais, true);

                    $valor_adicional = $valores_adicionais["valor_adicional_peca_produto"];

                    if(strlen(trim($valor_adicional)) > 0){
                        $valor_adicional = $valores_adicionais["valor_adicional_peca_produto"];
                        $valor_adicional = str_replace(",",".",$valor_adicional);
                    }else{
                        $valor_adicional = 0;
                    }

                    $total_geral = $valor_total_pecas + $valor_adicional;

                    #$dados_orcamento_email['geral']["total_geral"] = $total_geral;

                }else{
                    $valores_adicionais = pg_fetch_result($res_adicionais, 0, "valores_adicionais");
                    $valores_adicionais = json_decode($valores_adicionais, true);

                    $valor_adicional = $valores_adicionais["Valor Adicional"];
                    $desconto        = $valores_adicionais["Desconto"];

                    $total_geral = $valor_total_pecas + $valor_adicional - $desconto;
                }
	    }

	    if($login_fabrica == 166){
		$total_geral = $valor_total_pecas + $valor_adicional;
	    }

            ?>

            <tr>
                <td class='conteudo' colspan="3" align="right">Valor Adicional</td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_adicional, 2, ",", ".")?></td>
            </tr>
            <?php if(!in_array($login_fabrica,array(166,167,203))){ ?>
            <tr>
                <td class='conteudo' colspan="3" align="right">Valor de Desconto</td>
                <td class='conteudo' style="text-align: center;"><?=number_format($desconto, 2, ",", ".")?></td>
            </tr>
            <?php } ?>
            <tr>
                <td class='conteudo' colspan="3" align="right">Valor Total Peças</td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_total_pecas, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right" style="font-size: 15px;">Valor Total Geral</td>
                <td class='conteudo' style="text-align: center; font-size: 15px;"><?=number_format($total_geral, 2, ",", ".")?></td>
            </tr>

            <?php

        }elseif ($login_fabrica == 163) {

            $sql_adicionais = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os AND valores_adicionais notnull";
            $res_adicionais = pg_query($con, $sql_adicionais);

            if(pg_num_rows($res_adicionais) > 0){

                $valores_adicionais = pg_fetch_result($res_adicionais, 0, "valores_adicionais");
                $valores_adicionais = json_decode($valores_adicionais, true);

                $valor_adicional = $valores_adicionais["Valor Adicional"];
                $desconto        = $valores_adicionais["Desconto"];

                // $aux_valor_total_pecas = str_replace(".", "", $valor_total_pecas);
                // $aux_valor_total_pecas = str_replace(",", ".", $aux_valor_total_pecas);

                $total_geral = $valor_total_pecas + $valor_adicional - $desconto;

            }

            ?>
            <tr>
                <td class='conteudo' colspan="3" align="right">Valor Total Peças</td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_total_pecas, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right">Valor Adicional</td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_adicional, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right">Valor de Desconto</td>
                <td class='conteudo' style="text-align: center;"><?=number_format($desconto, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right" style="font-size: 15px;">Valor Total Geral</td>
                <td class='conteudo' style="text-align: center; font-size: 15px;"><?=number_format($total_geral, 2, ",", ".")?></td>
            </tr>

            <?php
        }else{

    ?>
        <tr>
            <td class='conteudo' colspan=3>Valor Total Peças</td>
            <td class='conteudo' style="text-align: center;" ><?=number_format($valor_total_pecas, 2, ",", ".")?></td>
        </tr>

<?php
        }
    }
    //Chamado 2365
    if($login_fabrica == 1 AND (in_array($tipo_atendimento,array(17,18,35,64,65,69)))){

        #HD 15198
        $sql  = "SELECT tbl_os_troca.ri                            AS pedido,
                        tbl_os.nota_fiscal_saida                   AS nota_fiscal,
                        TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf
                FROM tbl_os_troca
                JOIN tbl_os USING(os)
                WHERE tbl_os.os      = $os
                AND   tbl_os.fabrica = $login_fabrica; ";
        $resX = pg_query ($con,$sql);
        if (pg_num_rows ($resX) > 0) {
            $Xpedido      = pg_fetch_result($resX,0,pedido);
            $Xnota_fiscal = pg_fetch_result($resX,0,nota_fiscal);
            $Xdata_nf     = pg_fetch_result($resX,0,data_nf);

            #HD 15198
            echo "<tr align='center'>";

                //hd 21461
                $sql = "SELECT descricao
                        FROM tbl_produto
                        JOIN tbl_os_troca USING(produto)
                        WHERE os = $os";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    echo "<td class='conteudo' align='center'><center>".pg_fetch_result($res,0,0)."</center></td>";
                } else {
                    echo "<td class='conteudo' align='center'><center>$produto_descricao</center></td>";
                }
                echo "<td class='conteudo'></td>";
                echo "<td class='conteudo'></td>";
                echo "<td class='conteudo'></td>";
                echo "<td class='conteudo'></td>";
                echo "<td class='conteudo'></td>";
                echo "<td class='conteudo'></td>";
                echo "<td class='conteudo' align='center'><center>$Xpedido</center></td>";
                echo "<td class='conteudo' align='center'><center>$Xnota_fiscal</center></td>";
                echo "<td class='conteudo' align='center'><center>$Xdata_nf</center></td>";
            echo "</tr>";
        }
    }
    ?>
    </TABLE>
<? }?>
<?php
if( in_array($login_fabrica, array(11,172)) ){
    $sql = "SELECT  tbl_os_item.peca ,
                    tbl_os_item.pedido
                FROM tbl_os_produto
                JOIN tbl_os_item USING (os_produto)
                JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.peca = tbl_os_item.peca AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido
                WHERE tbl_os_produto.os = $os
                AND  tbl_os_item.pedido is not null";
                //echo nl2br($sql); exit;
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0){
            echo "</br> <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
                        <TR>
                            <TD class='inicio' style='text-align:center;' colspan='4' width='700'>PEÇAS CANCELADAS</TD>
                        </TR>
                        <TR align='center'>
                          <TD class='titulo2' align='center' width='70'>PEÇA</TD>
                            <TD class='titulo2' align='center' width='70'>DATA CANCELADO</TD>
                            <TD class='titulo2' align='center' width='70'>PEDIDO</TD>
                            <TD class='titulo2' align='center' width='490'>MOTIVO</TD>
                        </TR>";
            for($i = 0 ; $i < pg_num_rows($res) ; $i++){
                $peca = pg_fetch_result($res,$i,"peca");
                $pedido = pg_fetch_result($res,$i,"pedido");

                $sqlW = "   SELECT  tbl_pedido_cancelado.pedido                                     ,
                                    tbl_pedido_cancelado.motivo                                     ,
                                    to_char(tbl_pedido_cancelado.data_input,'DD/MM/YYYY') AS data_cancelamento
                            FROM    tbl_pedido_cancelado
                            WHERE   os      = $os
                            AND     peca    = $peca
                            AND     pedido  = $pedido
                        ";

                $resW = pg_query($con,$sqlW);
                $nW = pg_num_rows($resW);
               if ($nW > 0) {
                    $sql1 = "SELECT referencia
                            FROM tbl_peca
                            WHERE peca=$peca
                            AND fabrica = {$login_fabrica}";
                $res1 = pg_query($con,$sql1);
                        echo "
                        <TR align='center' style='background-color ;'>
                            <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($res1,0,referencia)."</TD>
                            <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($resW,0,data_cancelamento)."</TD>
                            <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($resW,0,pedido)."</TD>
                            <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($resW,0,motivo)."</TD>
                        </TR>";


                }
            }
            echo "</TABLE>";
        }
    }

?>

<?

//HD 214236: Auditoria Prévia de OS, mostrando status
if ($login_fabrica == 14 || $login_fabrica == 43) {

    $sql = "
    SELECT
    tbl_os_auditar.os_auditar,
    tbl_os_auditar.cancelada,
    tbl_os_auditar.liberado,
    TO_CHAR(tbl_os_auditar.data, 'DD/MM/YYYY HH24:MI') AS data ,
    TO_CHAR(CASE
        WHEN tbl_os_auditar.liberado_data IS NOT NULL THEN tbl_os_auditar.liberado_data
        WHEN tbl_os_auditar.cancelada_data IS NOT NULL THEN tbl_os_auditar.cancelada_data
        ELSE null
    END, 'DD/MM/YYYY HH24:MI') AS data_saida,
    tbl_os_auditar.justificativa

    FROM
    tbl_os_auditar

    WHERE
    tbl_os_auditar.os=$os
    ";
    $res = pg_query($con, $sql);
    $n = pg_numrows($res);

    if ($n > 0) {
        echo "
        <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
            <TR>
                <TD class='inicio' style='text-align:center;' colspan='4' width='700'>
                AUDITORIA PRÉVIA
                </TD>
            </TR>
            <TR align='center'>
                <TD class='titulo2' align='center' width='70'>Status</TD>
                <TD class='titulo2' align='center' width='70'>Data Entrada</TD>
                <TD class='titulo2' align='center' width='70'>Data Saída</TD>
                <TD class='titulo2' align='center' width='490'>Justificativa</TD>
            </TR>";

        for ($i = 0; $i < $n; $i++) {
            //Recupera os valores do resultado da consulta
            $valores_linha = pg_fetch_array($res, $i);

            //Transforma os resultados recuperados de array para variáveis
            extract($valores_linha);

            if ($liberado == 'f') {
                if ($cancelada == 'f') {
                    $legenda_status = "em análise";
                    $cor_status = "#FFFF44";
                }
                elseif ($cancelada == 't') {
                    $legenda_status = "reprovada";
                    $cor_status = "#FF7744";
                }
                else {
                    $legenda_status = "";
                    $cor_status = "";
                }
            }
            elseif ($liberado == 't') {
                $legenda_status = "aprovada";
                $cor_status = "#44FF44";
            }
            else {
                $legenda_status = "";
                $cor_status = "";
            }

            echo "
            <TR align='center' style='background-color: $cor_status;'>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$legenda_status</TD>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data</TD>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data_saida</TD>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$justificativa</TD>
            </TR>";
        }

        echo "
        </TABLE>";
    }
}

if ($login_fabrica == 51 and $login_admin == 586){
    $teste = system("cat /tmp/telecontrol/embarque_novo.txt | grep \"$sua_os\" ");
        if(strlen($teste)>0){
        echo $teste;
    }
}
if ($login_fabrica == 51 and $exibe_legenda > 0){
    echo "<BR>\n";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'>\n";
    echo "<TR style='line-height: 12px'>\n";
    echo "<TD width='5' bgcolor='#FFC0D0'>&nbsp;</TD>\n";
    echo "<TD style='padding-left: 10px; font-size: 14px;'><strong>Peças de retorno obrigatório</strong></TD>\n";
    echo "</TR></TABLE>\n";
}
echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' style='font-size:10px; margin:0 auto'>";
if(in_array($login_fabrica, array(50, 153))){

        if($login_fabrica == 50){
            echo "<tr>";
                echo "<TD colspan='4' align='left'>Legenda:</TD>";
            echo "</tr>";
            echo "<TR>";
            echo "<TD width=20 bgcolor='#FFC0D0' >&nbsp;</TD>";
            echo "<TD width=130>Devolução Obrigatória</TD>";
        }
        if($login_fabrica == 153 and $recall == true){
        echo "<tr>";
            echo "<TD colspan='4' align='left'>Legenda:</TD>";
        echo "</tr>";
        echo "<TR>";
            echo "<TD width=20 bgcolor='#98FB98' >&nbsp;</TD>";
            echo "<TD align='left'>Recall (Peças de substituição obrigatória)</TD>";
        }
        echo "</TR>";

}
echo "</TABLE>";
if ($login_fabrica == 50) {

echo "<BR>";
echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
echo "<TR>";
    echo "<TD class='conteudo'><b>OBS:</b>&nbsp;$obs_os</TD>";
echo "</TR>";
echo "</TABLE>";

    if ($recomendacoes) {
        echo "<BR>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
        echo "<TR>";
            echo "<TD class='conteudo'><b>Dados da Revenda da Nota Fiscal:</b><br>&nbsp;".nl2br($recomendacoes)."</TD>";
        echo "</TR>";
        echo "</TABLE>";
    }

}

// 7/1/2008 HD 11083 - estava mostrando campo null
if (strlen($orientacao_sac) > 0 and $orientacao_sac <> "null"){
    echo "<BR>";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
    echo "<TR>";
    echo "<TD colspan=7 class='inicio'>&nbsp;ORIENTAÇÕES DO SAC AO POSTO AUTORIZADO</TD>";
    echo "</TR>";
    echo "<TR>";
    echo "<TD class='conteudo_sac'>Obs: ".nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$orientacao_sac)))))))."</TD>";
    echo "</TR>";
    echo "</TABLE>";
}
if(in_array($login_fabrica, array(3,11,42,126,137,151,172))) {
    //verifica se tem imagem na OS
    $amazonTC->getObjectList("anexo_os_{$login_fabrica}_{$os}_img_os_");
    $files_anexo_os = $amazonTC->files;

    //verifica se tem imagem no OS Item
    $amazonTC->getObjectList("anexo_os_item_{$login_fabrica}_{$os}_img_os_item_");
    $files_anexo_os_item = $amazonTC->files;

    //verifica se tem imagem no OS Revenda
    $sqlSuaOS = "   SELECT sua_os
                    FROM tbl_os
                    WHERE tbl_os.os = {$os} AND
                        fabrica = {$login_fabrica}";
    $resSuaOs = pg_query($con,$sqlSuaOS);
    $suaOs = pg_fetch_result($resSuaOs, 0, "sua_os");
    list($suaOs,$digito) = explode("-", $suaOs);

    $sqlOsRevenda = "   SELECT os_revenda
                        FROM tbl_os_revenda
                        WHERE sua_os = '{$suaOs}' AND
                            fabrica = {$login_fabrica}";

    $resOsRevenda = pg_query($con,$sqlOsRevenda);
    if(pg_num_rows($resOsRevenda) > 0){
        $osRevenda = pg_fetch_result($resOsRevenda, 0, "os_revenda");
        $amazonTC->getObjectList("anexo_os_revenda_{$login_fabrica}_{$osRevenda}_img_os_revenda_");
        $files_anexo_os_revenda = $amazonTC->files;
    }
    if((count($files_anexo_os_item) > 0) ||  (count($files_anexo_os) > 0) ||  (count($files_anexo_os_revenda) > 0)){
?>
<br/>
        <table width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>
            <thead>
                <tr>
                    <th class='inicio' style="text-align: center !important;" colspan="4">Anexos</th>
                </tr>
            </thead>
            <tr>

                <? if(count($files_anexo_os) > 0){
                        $div_anexo_os = '';
                        $ver_anexo_os = '';
                      $arr_docs = array("pdf", 'doc', 'docx');
                      foreach ($files_anexo_os as $key => $path) {
                          $basename = basename($path);
                          $thumb = $amazonTC->getLink("thumb_".$basename, false, "", "");
                          $full  = $amazonTC->getLink($basename, false, "", "");
                            $pathinfo = pathinfo($full);
                            list($ext,$params) = explode("?", $pathinfo["extension"]);

                            if ( in_array($login_fabrica, array(11,172)) && !in_array($ext, $arr_docs)) {

                                $div_anexo_os.= '<div class="anexo" id="os_' . $key . '">';
                                $div_anexo_os.= '<img style="width: 800px; height: 600px;" alt="Baixar Anexo" src="' . $full . '" />';
                                $div_anexo_os.= '</div>';

                                $tag_abre = '<div style="cursor: pointer" onClick="verAnexo(\'os\', ' . $key . ')">';
                                $tag_fecha = '</div>';
                            } else {
                                $tag_abre = '<a href="' . $full . '">';
                                $tag_fecha = '</a>';
                            }

                          ?>

                           <td  align="center" class='conteudo' style="text-align: center !important;">
                                 <?php echo $tag_abre ?>
                                    <? if($ext == "pdf"){ ?>
                                        <img alt="Baixar Anexo" src="imagens/adobe.JPG" title="Clique para ver a imagem em uma escala maior" style="width: 100px; height: 90px;" />
                                    <?}else{ ?>
                                        <img alt="Baixar Anexo" src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" style="width: 100px; height: 90px;" />
                                    <? } ?>
                                <?php echo $tag_fecha ?> <br/><br/>
                                <button type="button" onclick="deletarImagemOS(this, '<?=$basename?>', 'os_<?php echo $key ?>')">Apagar anexo</button>

                           </td><?
                      }

                    }
                    if(count($files_anexo_os_item) > 0){

                        $div_anexo_os_item = '';
                        $ver_anexo_os_item = '';

                      foreach ($files_anexo_os_item as $key => $path) {
                          $basename = basename($path);
                          $thumb = $amazonTC->getLink($basename, false, "", "");
                          $full  = $amazonTC->getLink($basename, false, "", "");
                          $pathinfo = pathinfo($full);
                          list($ext,$params) = explode("?", $pathinfo["extension"]);
                            if ( in_array($login_fabrica, array(11,172)) && !in_array($ext, $arr_docs) )  {

                                $div_anexo_os.= '<div class="anexo" id="os_item_' . $key . '">';
                                $div_anexo_os.= '<img alt="Baixar Anexo" style="width: 800px; height: 600px;" src="' . $full . '" />';
                                $div_anexo_os.= '</div>';

                                $tag_abre = '<div style="cursor: pointer" onClick="verAnexo(\'os_item\', ' . $key . ')">';
                                $tag_fecha = '</div>';
                            } else {
                                $tag_abre = '<a href="' . $full . '">';
                                $tag_fecha = '</a>';
                            }

                            ?>

                          <td  align="center" class='conteudo' style="text-align: center !important;">
                           <?php echo $tag_abre ?>
                                <? if($ext == "pdf"){ ?>
                                    <img alt="Baixar Anexo" src="imagens/adobe.JPG" title="Clique para ver a imagem em uma escala maior" style="width: 32px; height: 32px;" />
                                <?}else{ ?>
                                    <img alt="Baixar Anexo" src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" style="width: 100px; height: 90px;" />
                                <? } ?>
                            <?php echo $tag_fecha ?>
                            <br/><br/>
                                <button type="button" onclick="deletarImagemOS(this,'<?=$basename?>', 'os_item_<?php echo $key ?>')">Apagar anexo</button>
                          </td><?
                       }
                    }

                    if(count($files_anexo_os_revenda) > 0){
                      foreach ($files_anexo_os_revenda as $path) {
                          $basename = basename($path);
                          $thumb = $amazonTC->getLink($basename, false, "", "");
                          $full  = $amazonTC->getLink($basename, false, "", "");
                          $pathinfo = pathinfo($full);
                          list($ext,$params) = explode("?", $pathinfo["extension"]);
                            ?>

                          <td  align="center" class='conteudo' style="text-align: center !important;">
                            <a href="<?=$full?>" >
                                <? if($ext == "pdf"){ ?>
                                    <img alt="Baixar Anexo" src="imagens/adobe.JPG" title="Clique para ver a imagem em uma escala maior" style="width: 32px; height: 32px;" />
                                <?}else{ ?>
                                    <img alt="Baixar Anexo" src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" style="width: 100px; height: 90px;" />
                                <? } ?>
                            </a>
                            <br/><br/>
                                <button type="button" onclick="deletarImagemOS(this,'<?=$basename?>')">Apagar anexo</button>
                          </td><?
                       }
                    } ?>


            </tr>

        </table><br/>

        <?php
        echo $div_anexo_os;
        echo $div_anexo_os_item;
    }
}



if (in_array($login_fabrica,array(50,88))) {
    if($login_fabrica == 50){
?>
    <p>
    <center>
    <form name='frm_orientacao' method=post action="<? echo "$PHP_SELF?os=$os"; ?>">
        <font size="1" face="Geneva, Arial, Helvetica, san-serif">
        ORIENTAÇÕES DO SAC AO POSTO AUTORIZADO
        </font>
        <br>
        <textarea name='orientacao_sac' rows='4' cols='50'><? if($orientacao_sac!="null") echo trim($orientacao_sac); ?></textarea>
        <br><br>
        <input type="hidden" name="btn_acao" value="">
        <img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_orientacao.btn_acao.value == '' ) { document.frm_orientacao.btn_acao.value='gravar_orientacao' ; document.frm_orientacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Orientação" border='0' style="cursor:pointer;">
        </center>
    </form><br/>

    <?php
    }

    $sql = "SELECT descricao as pergunta, txt_resposta as resposta from tbl_pergunta join tbl_resposta using(pergunta) where os = $os";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        echo "<table width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";

        while ($fetch = pg_fetch_assoc($qry)) {
            //echo '<tr><td class="conteudo">' . utf8_decode($fetch['pergunta']) . ': ' . utf8_decode($fetch['resposta']) . '</td></tr>';
            echo '<tr><td class="conteudo">' . $fetch['pergunta'] . ': ' . $fetch['resposta'] . '</td></tr>';
        }

        echo '</table>';
    }

}
?>

<?php
if($login_fabrica == 3 ) {
    $sql = "SELECT key_code FROM tbl_os JOIN tbl_produto USING (produto) WHERE os = $os AND familia = 1281";
    $query = pg_query($con, $sql);

    if (pg_num_rows($query) > 0) {
        $key_code = pg_fetch_result($query, 0, 'key_code');

        if (!empty($key_code)) {
            echo "<br>";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
            echo "<TR class='inicio'>";
            echo "<TD>LICENÇA COA</TD>";
            echo "</TR>";
            echo "<TR>";
            echo "<TD class='conteudo_sac' align='left'>Windows 8 chave nº:</TD>";
            echo "<TD class='conteudo_sac' align='left'>$key_code</TD>";
            echo "</TR>";
            echo "</TABLE>";
        }
    }
}
?>

<?

if($login_fabrica == 74){

    if($fechamentoOS->isOsVinculada($os)){
        $arrOS = $fechamentoOS->getArrOS();
        echo "<br>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD>";
        echo "OS Vinculada:";
        echo "</TD>";
        echo "</TR>";
        echo "<TR class='conteudo'>";
        echo "<TD>";
        echo  "OS ".$arrOS[0] . " Vinculada à OS ".$arrOS[1];
        echo "</TD>";
        echo "</TR>";
        echo "</TABLE>";

    }


}

if($login_fabrica == 30){

    if($hd_classificacao == 47){

        echo "<br />";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
            echo "<td> ORIENTAÇÃO AO POSTO </td>";
        echo "</TR>";
        echo "<TR>";
            echo "<td class='justificativa' align='left'> {$orientacao_posto} </td>";
        echo "</TR>";
        echo "</TABLE>";


    }

}

if ((strlen(trim($obs_os)) > 0 && strtoupper($obs_os) != "NULL") && !in_array($login_fabrica, array(35, 50))) {
        $obs_os = ($obs_os == 'null' OR $obs_os == 'NULL') ? "" : $obs_os;
        echo "<br>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        if (in_array($login_fabrica, array(152))) {
            echo "<TD>DESCRIÇÃO DETALHADA DO PROBLEMA:&nbsp;</TD>";
        } else if(in_array($login_fabrica,array(156))){
            echo "<TD>LAUDO DE ANÁLISE TÉCNICA:&nbsp;</TD>";
        }else if(in_array($login_fabrica,array(163))){
            echo "<td>Causa do Defeito:</td>";
        }else if(in_array($login_fabrica,array(171))){
            echo "<td>COMENTÁRIO SOBRE A VISITA:</td>";
        }else {
            echo "<TD>OBSERVAÇÃO:&nbsp;</TD>";
        }

        $obs_os = wordwrap($obs_os, 104, '<br/>', true);

        if (in_array($login_fabrica, array(169,170))){
            $sql_script = "
                        SELECT historico_resolucao
                        FROM tbl_os
                        JOIN tbl_hd_chamado_script_falha ON tbl_hd_chamado_script_falha.hd_chamado = tbl_os.hd_chamado
                            AND tbl_hd_chamado_script_falha.fabrica = {$login_fabrica}
                        WHERE tbl_os.os = {$os}
                        AND tbl_os.fabrica = {$login_fabrica}
                        ";
            $res_script = pg_query($con, $sql_script);
            if(pg_num_rows($res_script) > 0){
                $resolution_script = pg_fetch_result($res_script, 0, 'historico_resolucao');
                if (preg_match("/\<br\/\>|\<br\s\/\>|<br>/", $script_resolution)) {
                    $script_resolution = preg_replace("/\<br\/\>|\<br\s\/\>|<br>/", "\n", $script_resolution);
                    $script_resolution = preg_replace("/\s{2,}/", "\n", $script_resolution);
                }
            }
        }

        echo "</TR>";
        echo "<TR>";
        if (in_array($login_fabrica, array(169,170))){

            echo "<TD class='justificativa' align='left'>". nl2br($obs_os)."<br/><br/>";
            if(strlen(trim($resolution_script)) > 0){
                echo "<b>Script de falha executado no callcenter: </b><br/>
                <textarea style='font-family: Arial; font-size: 10px; width: 690px;' rows='6' readonly='true'>$resolution_script</textarea>";
            }
            echo "</TD>";
        }else{
            echo "<TD class='justificativa' align='left'>". nl2br($obs_os)."</TD>";
        }

        echo "</TR>";
        echo "</TABLE>";

}
if($login_fabrica == 20){
    if($tipo_atendimento == 13 or $tipo_atendimento == 66){
        //HD-3200578
            $obs_motivo_ordem = array();
            if($motivo_ordem == 'PROCON (XLR)'){
                $obs_motivo_ordem[] = 'Protocolo:';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['protocolo']);
            }
            if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                $obs_motivo_ordem[] = 'CI ou Solicitante:';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['ci_solicitante']);
            }

            if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                $obs_motivo_ordem[] = "Descrição Peças:";
                if(strlen(trim($os_campos_adicionais['descricao_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['descricao_peca_1']);
                }
                if(strlen(trim($os_campos_adicionais['descricao_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['descricao_peca_2']);
                }
                if(strlen(trim($os_campos_adicionais['descricao_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['descricao_peca_3']);
                }
            }

            if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                if(strlen(trim($os_campos_adicionais['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($os_campos_adicionais['codigo_peca_2']))) > 0 OR strlen(trim($os_campos_adicionais['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Código Peças:';
                }
                if(strlen(trim($os_campos_adicionais['codigo_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['codigo_peca_1']);
                }
                if(strlen(trim($os_campos_adicionais['codigo_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['codigo_peca_2']);
                }
                if(strlen(trim($os_campos_adicionais['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['codigo_peca_3']);
                }

                if(strlen(trim($os_campos_adicionais['numero_pedido_1'])) > 0 OR strlen(trim($os_campos_adicionais['numero_pedido_2'])) > 0 OR strlen(trim($os_campos_adicionais['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Número Pedidos:';
                }
                if(strlen(trim($os_campos_adicionais['numero_pedido_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['numero_pedido_1']);
                }
                if(strlen(trim($os_campos_adicionais['numero_pedido_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['numero_pedido_2']);
                }
                if(strlen(trim($os_campos_adicionais['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['numero_pedido_3']);
                }
            }

            if($motivo_ordem == "Linha de Medicao (XSD)"){
                $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['linha_medicao']);
            }
            if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['pedido_nao_fornecido']);
            }

            if($motivo_ordem == 'Contato SAC (XLR)'){
                $obs_motivo_ordem[] .= 'N° do Chamado:';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['contato_sac']);
            }

            if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
                $obs_motivo_ordem[] .= "Detalhes:";
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['detalhe']);
            }
            echo"<table width='700px' class='Tabela' border='0' cellspacing='1' cellpadding='2' align='center'>";
                echo "<tr class='inicio'>";
                    echo "<TD><b>Observações Motivo Ordem</b></TD>";
                echo "</tr >";
                echo "<tr  class='conteudo'><td>".implode('<br/>', $obs_motivo_ordem)."</td></tr>";
            echo "</table>";
        // FIM HD-3200578 //
    }
}
// Implantação Imbera - Adição de laudo técnico
if ($login_fabrica == 158) {
    $sql = "SELECT * FROM tbl_laudo_tecnico_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $laudos = pg_fetch_all($res);
        foreach ($laudos as $laudo) {
            $procedimentos = array();
            $procedimentos = json_decode($laudo['observacao'], true); ?>
            <br />
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
                <thead>
                    <tr class="inicio">
                        <th><?= strtoupper($laudo['titulo']); ?>:</th>
                    </tr>
                </thead>
                <tbody>
                <? foreach($procedimentos as $pr) { ?>
                    <tr>
                        <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
                            <tr>
                                <td colspan="<?= count($pr['campos']); ?>" class="titulo2">
                                    <?= Utf8_ansi($pr['nome']); ?>
                                </td>
                            </tr>
                            <? foreach($pr['campos'] as $campos_laudo) { ?>
                                <tr>
                                    <td class="titulo">
                                        <?= Utf8_ansi($campos_laudo['nome']); ?>
                                    </td>
                                    <td class="conteudo">
                                        <?= (strpos(strtoupper($campos_laudo['nome']), 'DATA') !== false) ? date('d/m/Y H:i:s', $campos_laudo['valor'] / 1000) : Utf8_ansi($campos_laudo['valor']); ?>
                                    </td>
                                </tr>
                            <? } ?>
                        </table>
                    </tr>
                <? } ?>
                </tbody>
            </table>
        <? } ?>
    <? }
}

if($login_fabrica == 156){
    if (isset($obs_adicionais)) {

        echo "<br />";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD>";
        echo "Observações Administrativas";
        echo "</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='justificativa' align='left'>".nl2br($obs_adicionais)."</TD>";
        echo "</TR>";
        echo "</TABLE>";

    }

    $sql = "SELECT descricao
        FROM tbl_status_os
        JOIN tbl_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
        WHERE os = $os";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        echo '<br><table class="Tabela" width="700px" cellspacing="1" align="center">
                <tr>
                   <td class="inicio">STATUS DA OS</td>
                </tr>
                <tr>
                    <td class="justificativa" align="left">' . pg_fetch_result($res, 0, 'descricao') . '</td>
                </tr>
              </table>';
    }
}

if( $login_fabrica == 52 and !empty($obs_os_log) ){ # HD 925803 para Fricon
    echo "<br />";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
    echo "<TR class='inicio'>";
    echo "<TD align='center'>LOG DE ALTERAÇÕES</TD>";
    echo "</TR>";
    echo "<TR>";
    echo "<TD class='justificativa' align='left'>";
              echo nl2br($obs_os_log); // mostrando os logs criados
    echo "</TD>";
    echo "</TR>";
    echo "</TABLE>";
}


if($login_fabrica == 1) {
    $sql = " SELECT nome_completo,descricao,obs_causa,prateleira_box,tbl_os.rg_produto, tbl_os.consumidor_revenda
            FROM tbl_os_troca
            JOIN tbl_admin ON tbl_os_troca.admin_autoriza = tbl_admin.admin
            JOIN tbl_causa_troca USING(causa_troca)
            JOIN tbl_os USING(os)
            WHERE os = $os";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        $prateleira_box = ucfirst(pg_fetch_result($res, 0, 'prateleira_box'));
        $rg_produto = ucfirst(pg_fetch_result($res, 0, 'rg_produto'));
        $consumidor_revenda = ucfirst(pg_fetch_result($res, 0, 'consumidor_revenda'));

        if($consumidor_revenda == "R" AND $rg_produto == 'Indispl'){
            $prateleira_box = $rg_produto;
        }

        if (!empty($prateleira_box)) {
            if ($prateleira_box == 'Fale') {
                $prateleira_box .= ' Conosco';
            } elseif ($prateleira_box == 'Reclame') {
                $prateleira_box .= ' Aqui';
            }

            $prateleira_box = ' - ' . $prateleira_box;
        }

        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR>";
            echo "<td class='inicio'>Admin Autoriza:</td>";
            echo "<td class='conteudo'>".pg_fetch_result($res,0,nome_completo)."</td>";
            echo "<td class='inicio'>Motivo da Troca:</td>";
            echo "<td class='conteudo'>".pg_fetch_result($res,0,descricao). $prateleira_box ."</td>";
        echo "</TR>";
        echo "<TR>";
            echo "<TD class='conteudo' colspan='4' align='left'>OBS:".pg_fetch_result($res,0,obs_causa)."</TD>";
        echo "</TR>";

        if (count($os_campos_adicionais) > 0) {
            echo '<tr>';
            echo '
                <td class="conteudo" colspan="4" align="left">
                    CHECK LIST<br/>
                    Código: ' . utf8_decode($os_campos_adicionais['chk_lst_codigo']) . '
                    <span style="padding-left: 10px;">Posto: ' . utf8_decode($os_campos_adicionais['chk_lst_posto']) . '</span>
                    <span style="padding-left: 10px;">Nome do Atendente: ' . utf8_decode($os_campos_adicionais['chk_lst_atendente']) . '</span>';

            if (!empty($os_campos_adicionais['chk_lst_obs'])) {
                echo '<br/>Observações: ' . utf8_decode($os_campos_adicionais['chk_lst_obs']);
            }

            echo '
                </td>';
            echo '</tr>';
        }


        echo "</TABLE>";
    }
}

//HD 163220 - Mostrar os chamados aos quais a OS tem relacionamento no Call Center (tbl_hd_chamado_extra.os)
if (in_array($login_fabrica,array(11,50,104,151,172))) {
    $sql = "
    SELECT
    tbl_hd_chamado.hd_chamado,
    TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY') AS data

    FROM
    tbl_hd_chamado
    JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado

    WHERE
    tbl_hd_chamado_extra.os=$os
	AND tbl_hd_chamado.posto ISNULL
    ORDER BY
    tbl_hd_chamado.data ASC
    ";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) == 0){
	    $sql = "SELECT hd_chamado, TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY') AS data FROM tbl_hd_chamado_item JOIN tbl_hd_chamado USING(hd_chamado) WHERE tbl_hd_chamado_item.os = $os";
	    $res = pg_query($con, $sql);
    }

    if (pg_num_rows($res)) {
        echo "
        <TABLE width='300px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
        <br>
        <TR>
            <TD colspan='2' class='inicio'>ATIVIDADE DE CHAMADOS NO CALLCENTER</TD>

        </TR>
        <TR>
            <TD class='titulo2'>CHAMADO</TD>
            <TD class='titulo2'>DATA</TD>
        </TR>";

        for($h = 0; $h < pg_num_rows($res); $h++) {
            $hd_chamado = pg_fetch_result($res, $h, hd_chamado);
            $data = pg_fetch_result($res, $h, data);

            echo "
            <TR>
                <TD class=conteudo style='text-align:center'>
                    <a href='callcenter_interativo_new.php?callcenter=$hd_chamado'>$hd_chamado</a>
                </TD>
                <TD class=conteudo style='text-align:center'>
                    <a href='callcenter_interativo_new.php?callcenter=$hd_chamado'>$data</a>
                </TD>
            </TR>";
        }

        echo "
        </TABLE>";
    }

    if (in_array($login_fabrica,array(104))) {
        $sql = "SELECT
                        tbl_faturamento_correio.conhecimento,
                        tbl_faturamento_correio.numero_postagem
                     FROM
                        tbl_hd_chamado_postagem
                     JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_postagem.hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica
                     JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                     JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os
                     JOIN tbl_faturamento_correio ON (tbl_faturamento_correio.numero_postagem=tbl_hd_chamado_postagem.numero_postagem OR tbl_faturamento_correio.numero_postagem=tbl_os.autorizacao_domicilio)  AND tbl_faturamento_correio.fabrica=$login_fabrica
                     WHERE
                        tbl_os.os=$os
                     AND
                        tbl_os.fabrica=$login_fabrica

                   ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            echo "
            <TABLE width='300px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
            <br>
            <TR>
                <TD colspan='2' class='inicio'>HISTÓRICO DE POSTAGEM</TD>

            </TR>
            <TR>
                <TD class='titulo2'>NÚMERO DA POSTAGEM</TD>
                <TD class='titulo2'>CONHECIMENTO</TD>
            </TR>";

            for($h = 0; $h < pg_num_rows($res); $h++) {
                $conhecimento = pg_fetch_result($res, $h, conhecimento);
                $numero_postagem = pg_fetch_result($res, $h, numero_postagem);

                echo "
                <TR>
                    <TD class=conteudo style='text-align:center'>
                        $numero_postagem
                    </TD>
                    <TD class=conteudo style='text-align:center'>
                    <a HREF='./relatorio_faturamento_correios?conhecimento=$conhecimento' rel='shadowbox'>
                        $conhecimento</a>
                    </TD>
                </TR>";
            }

            echo "
            </TABLE>";
        }
    }

}

if($login_fabrica==3){
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<br>
<TR>
    <TD colspan='2' class='inicio'>LOG DE ALTERAÇÃO NA OS PELO ADMIN</TD>

</TR>

<TR>
    <TD class='titulo2'>NOME COMPLETO</TD>
    <TD class='titulo2'>DATA</TD>
</TR>

<?


    $sql = "select to_char(tbl_os_log_admin.data, 'dd/mm/yyyy hh24:mi') as data,tbl_admin.nome_completo
    from tbl_os_log_admin
    join tbl_admin on tbl_os_log_admin.admin = tbl_admin.admin
    where tbl_os_log_admin.os=$os";
    $res = pg_query($con,$sql);

    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
    $data  = trim(pg_fetch_result($res,$i,data));
    $nome_completo  = trim(pg_fetch_result($res,$i,nome_completo));
?>

<TR>
    <TD class='titulo2'><? echo $nome_completo;?></TD>
    <TD class='titulo2'><? echo $data;?></TD>

</TR>

<?
    }
?>
</TABLE>
<?
}
?>

<?php

/* Transportadora */
if(in_array($login_fabrica, array(157))){

    $sql_os_item = "SELECT os_item FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
    $res_os_item = pg_query($con, $sql_os_item);

    $os_item_arr = array();

    if(pg_num_rows($res_os_item)){

        for ($i = 0; $i < pg_num_rows($res_os_item); $i++) {

            $os_item_arr[] = pg_fetch_result($res_os_item, $i, "os_item");

        }

    }

    if(count($os_item_arr) > 0){

        $in_os_item = implode(",", $os_item_arr);

        $sql = "SELECT
                    tbl_transportadora.nome,
                    tbl_transportadora.cnpj,
                    tbl_faturamento.nota_fiscal
                FROM tbl_faturamento_item
                INNER JOIN tbl_faturamento USING(faturamento)
                INNER JOIN tbl_transportadora USING(transportadora)
                WHERE
                    tbl_faturamento.fabrica = $login_fabrica
                    AND tbl_faturamento_item.os_item IN ({$in_os_item})";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

        ?>

        <br/>

        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='3' style="font-size:11pt; text-align: center;">Informações da Transportadora</td>
            </tr>
            <tr>
                <td class="titulo2" width="50%">Nome</td>
                <td class="titulo2" width="25%">CPNJ</td>
                <td class="titulo2" width="25%">Nota Fiscal</td>
            </tr>

            <?php

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $nome_transportadora = pg_fetch_result($res, 0, "nome");
                $cnpj_transportadora = pg_fetch_result($res, 0, "cnpj");
                $nota_fiscal_transportadora = pg_fetch_result($res, 0, "nota_fiscal");

                ?>

                <tr>
                    <td class="conteudo" style="text-align: center;"><?php echo $nome_transportadora; ?></td>
                    <td class="conteudo" style="text-align: center;"><?php echo $cnpj_transportadora; ?></td>
                    <td class="conteudo" style="text-align: center;"><?php echo $nota_fiscal_transportadora; ?></td>
                </tr>

                <?php

            }

            ?>

        </table>

        <?php

        }

    }

}

?>

<style>
    .Tabela td {
        height: 15px;
    }
</style>

<?
#Mostra os valores de custos adicionais existentes na OS
if($inf_valores_adicionais || isset($fabrica_usa_valor_adicional)){
    include "../valores_adicionais_inc.php";
}

if(isset($os_press_mostra_avulso)) {
	include "../valores_avulsos_inc.php";
}

// HD 2551514 (Esmaltec) - Auditoria de OSs com Peças fora do Pedido Inicial
if($auditoria_unica == true || in_array($login_fabrica, array(42))){
    if ($login_fabrica == 42) {
        $sqlAuditoria = "SELECT
                        tbl_os_status.os_status AS os_status,
                        tbl_os_status.status_os AS status_os,
                        tbl_os_status.observacao AS observacao,
                        tbl_admin.login AS nome_completo,
                        to_char(tbl_os_status.data,'DD/MM/YYYY') AS data_input,
                        tbl_os_status.admin AS admin,
                        tbl_status_os.descricao AS descricao,
                        '' AS justificativa,
                        null as liberada,
                        null AS reprovada,
                        null AS cancelada,
                        null as paga_mao_obra
                    FROM tbl_os_status
                    JOIN tbl_status_os using(status_os)
                    LEFT JOIN tbl_admin USING(admin)
                    WHERE tbl_os_status.os = $os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                        UNION
                    SELECT
                        null AS os_status,
                        null AS status_os,
                        tbl_auditoria_os.observacao AS observacao,
                        tbl_admin.nome_completo,
                        to_char(tbl_auditoria_os.data_input,'DD/MM/YYYY') AS data_input,
                        tbl_auditoria_os.admin AS admin,
                        tbl_auditoria_status.descricao AS descricao,
                        tbl_auditoria_os.justificativa AS justificativa,
                        to_char(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS liberada,
                        to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada,
                        to_char(tbl_auditoria_os.cancelada,'DD/MM/YYYY') AS cancelada,
                        tbl_auditoria_os.paga_mao_obra
                    FROM tbl_auditoria_os
                    JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
                    JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                    WHERE tbl_auditoria_os.os = $os";
    }else{
        $sqlAuditoria = "SELECT tbl_auditoria_status.descricao,
                                tbl_auditoria_os.observacao,
                                to_char(tbl_auditoria_os.data_input,'DD/MM/YYYY') AS data_input,
                                to_char(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS liberada,
                                to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada,
                                to_char(tbl_auditoria_os.cancelada,'DD/MM/YYYY') AS cancelada,
                                tbl_auditoria_os.justificativa,
                                tbl_auditoria_os.paga_mao_obra,
                                tbl_auditoria_os.auditoria_os,
                                tbl_admin.nome_completo
                            FROM tbl_auditoria_os
                                JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                                LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
                                JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                            WHERE tbl_auditoria_os.os = $os ORDER BY tbl_auditoria_os.auditoria_os DESC";
    }
    $resAuditoria = pg_query($con,$sqlAuditoria);

    if(pg_num_rows($resAuditoria) > 0){
 ?>
    <br/>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;">Histórico de Intervenção</td>
        </tr>
        <tr>
            <TD class="titulo2">Data</TD>
            <TD class="titulo2">Descrição</TD>
            <TD class="titulo2">Status</TD>
            <TD class="titulo2">Paga MO</TD>
            <TD class="titulo2">Justificativa</TD>
            <TD class="titulo2">Admin</TD>
        </tr>
        <?
                $count = pg_num_rows($resAuditoria);

                for($i=0; $i < $count; $i++){
                    unset($liberada,$cancelada_auditoria,$reprovada_auditoria,$status_auditoria);
                    $descricao_auditoria     = (pg_fetch_result($resAuditoria, $i, "descricao"));
                    $observacao_auditoria    = (pg_fetch_result($resAuditoria, $i, "observacao"));
                    $data_auditoria          = pg_fetch_result($resAuditoria, $i, "data_input");
                    $liberada                = pg_fetch_result($resAuditoria, $i, "liberada");
                    $reprovada                = pg_fetch_result($resAuditoria, $i, "reprovada");
                    $cancelada_auditoria     = pg_fetch_result($resAuditoria, $i, "cancelada");
                    $reprovada_auditoria     = pg_fetch_result($resAuditoria, $i, "reprovada");
                    $justificativa_auditoria = pg_fetch_result($resAuditoria, $i, "justificativa");
                    $paga_mao_obra           = pg_fetch_result($resAuditoria, $i, "paga_mao_obra");
                    $nome_auditoria          = pg_fetch_result($resAuditoria, $i, "nome_completo");
                    $id_auditoria            = pg_fetch_result($resAuditoria, $i, "auditoria_os");
                    if($paga_mao_obra == 't'){
                        $paga_mao_obra = "Sim";
                    }else{
                        $paga_mao_obra = "Não";
                    }

                    if ($liberada == "" && $cancelada_auditoria == "" && $reprovada_auditoria == ""){
                        $status_auditoria = "Aguardando Admin";
                    } else if ($liberada != "") {
                        $status_auditoria = "Liberado em $liberada";
                    } else if ($cancelada_auditoria != ""){
                        $status_auditoria = "Cancelado em $cancelada_auditoria";
                    } else if ($reprovada_auditoria != "") {
                        $status_auditoria = "Reprovada";
                    }

        ?>
        <tr>
            <td class="conteudo"><?= $data_auditoria; ?></td>
            <td class="conteudo"><?= $descricao_auditoria." - ".$observacao_auditoria; ?></td>
            <td class="conteudo" nowrap>
                <?php
                    echo $status_auditoria;
                    if(in_array($login_fabrica, [167, 203]) && $descricao_auditoria == "Auditoria da Fábrica"){
                        if($observacao_auditoria == "Auditoria de Suprimento" OR $observacao_auditoria == "Auditoria de Garantia"){
                            $sql_laudo_brother = "SELECT laudo_tecnico_os FROM tbl_laudo_tecnico_os WHERE os = $os";
                            $res_laudo_brother = pg_query($con, $sql_laudo_brother);
                            if(pg_num_rows($res_laudo_brother) > 0){
                    ?>
                                <button><a href="consulta_laudo_brother.php?os=<?=$os?>&auditoria=<?=$id_auditoria?>" target="_blank">Ver laudo</a></button>
                    <?php
                            }
                        }
                    }
                ?>
            </td>
            <td class="conteudo"><?= $paga_mao_obra; ?></td>
            <td class="conteudo"><?= $justificativa_auditoria; ?></td>
            <td class="conteudo"><?= $nome_auditoria; ?></td>
        </tr>
        <? }
        } ?>
    </table>
<?php }?>

<?php
    if ($login_fabrica == 148) {

        $total          = $valor_total_pecas + $mao_de_obra + $qtde_km_calculada;
        $total          = number_format ($total,2,",",".");
        $mao_de_obra    = number_format ($mao_de_obra ,2,",",".");
        $valor_peca     = number_format ($valor_total_pecas ,2,",",".");
        $valor_km       = number_format ($qtde_km_calculada ,2,",",".");
?>
    <br/>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;">Valores da OS </td>
        </tr>
        <tr>
            <TD class="titulo2" width="25%">Valor de Deslocamento</TD>
            <TD class="titulo2" width="25%">Valor das Peças</TD>
            <TD class="titulo2" width="25%"><?=(strtolower($nome_atendimento) == 'entrega técnica' AND $login_fabrica == 148)? "Valor da Entrega Técnica" : "Valor de Mão de Obra"?></TD>
            <TD class="titulo2" width="25%">Total</TD>
        </tr>
        <tr>
            <td style="text-align: center;" class="conteudo"><?php echo $valor_km; ?></td>
            <td style="text-align: center;" class="conteudo"><?php echo $valor_peca;?></td>
            <td style="text-align: center;" class="conteudo" nowrap><?php echo $mao_de_obra; ?></td>
            <td style="text-align: center;" class="conteudo"><?php echo $total; ?></td>
        </tr>
    </table>
<?php
}
# adicionado por Fabio - 26/03/2007 - hd chamado 1392
# adicionado para HBTech - #HD 14830 - Fabrica 25
# adicionado para HBTech - #HD 13618 - Fabrica 45
# MLG HD 283928 - Nova

    $troca = "";
    if($login_fabrica == 1){
        $sqlTroca = "
                    SELECT  tbl_os_troca.status_os
                    FROM    tbl_os_troca
                    WHERE   os = $os
        ";
        $resTroca = pg_query($con,$sqlTroca);
        $status_troca = pg_fetch_result($resTroca,0,status_os);

    $troca = 1;
    }else{
        $troca = 1;
    }


	if(($troca == 1 AND $login_fabrica == 104) or $login_fabrica == 134 OR isset($union_auditoria_os)){//hd_chamado=3211646
		$troca = 0 ;
        $sqlAudit = "SELECT
                        tbl_os_status.os_status AS os_status,
                        tbl_os_status.status_os AS status_os,
                        tbl_os_status.observacao AS observacao,
                        tbl_admin.login AS login,
                        tbl_os_status.data AS data_status,
                        tbl_os_status.admin AS admin,
                        tbl_status_os.descricao AS descricao,
						'' AS justificativa,
						null as liberada,
						null as reprovada
                    FROM tbl_os_status
                    JOIN tbl_status_os using(status_os)
                    LEFT JOIN tbl_admin USING(admin)
                    WHERE tbl_os_status.os = $os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                        UNION
                    SELECT
                        null AS os_status,
                        null AS status_os,
                        tbl_auditoria_os.observacao AS observacao,
                        tbl_admin.nome_completo AS login,
                        tbl_auditoria_os.data_input AS data_status,
                        tbl_auditoria_os.admin AS admin,
                        tbl_auditoria_status.descricao AS descricao,
						tbl_auditoria_os.justificativa AS justificativa,
						to_char(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS liberada,
						to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada
                    FROM tbl_auditoria_os
                    JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
                    JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                    WHERE tbl_auditoria_os.os = $os
                    ORDER BY data_status DESC";

        $resAudit = pg_query($con, $sqlAudit);
        if(pg_num_rows($resAudit) > 0){
?>
			<br>
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
                <tr>
                    <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;">Histórico de Intervenção</td>
                </tr>
                <tr>
                    <TD class="titulo2">Data</TD>
                    <TD class="titulo2">Tipo/Status</TD>
                    <TD class="titulo2">Justificativa</TD>
                    <TD class="titulo2">Admin</TD>
                </tr>
        <?php
            for ($t=0; $t < pg_num_rows($resAudit) ; $t++) {

                $os_status = pg_fetch_result($resAudit, $t, 'os_status');
                $status_os = pg_fetch_result($resAudit, $t, 'status_os');
                $observacao = pg_fetch_result($resAudit, $t, 'observacao');
                $login = pg_fetch_result($resAudit, $t, 'login');
                $data_status = pg_fetch_result($resAudit, $t, 'data_status');
                $admin = pg_fetch_result($resAudit, $t, 'admin');
                $liberada = pg_fetch_result($resAudit, $t, 'liberada');
                $reprovada = pg_fetch_result($resAudit, $t, 'reprovada');
                $descricao = pg_fetch_result($resAudit, $t, 'descricao');
                $justificativa = pg_fetch_result($resAudit, $t, 'justificativa');

                $newDate = date("d-m-Y", strtotime($data_status));

                $data_status = str_replace("-", "/", $newDate);

                if(empty($justificativa)){
                    $justificativa = $observacao;
                }else{
                    $justificativa = $justificativa;
                }

				if(!empty($liberada)) {
					$descricao = $descricao . " - Liberado em $liberada";
				}elseif(!empty($reprovada)) {
					$descricao = $descricao . " - Reprovado em $reprovada";
				}
                if(empty($login)){
                    $login = "Automático";
                }else{
                    $login = $login;
                }

        ?>
            <tr>
                <td class="conteudo"><?=$data_status?></td>
                <td class="conteudo"><?=$descricao?></td>
                <td class="conteudo"><?=$justificativa?></td>
                <td class="conteudo"><?=$login?></td>
            </tr>
        <?php
            }
        ?>
			</table>
			<br>
        <?php
        }
    }

    if($troca == 1 && !isset($auditoria_unica) AND $login_fabrica != 120 and $login_fabrica != 201 AND $login_fabrica != 42){//hd_chamado=3211646
            if ($login_fabrica == 131) {
                $sql_status = "SELECT DISTINCT status_os,
                                        observacao,
                                        os_status,
                                        tbl_admin.login AS login,
                                        to_char(data, 'DD/MM/YYYY')   as data_status,
                                        tbl_os_status.admin,
                                        tbl_status_os.descricao
                                FROM    tbl_os_status
                                JOIN    tbl_status_os using(status_os)
                           LEFT JOIN    tbl_admin USING(admin)
                                WHERE   os                              = $os
                                AND     tbl_os_status.fabrica_status    = $login_fabrica
                            GROUP BY status_os,
                                    observacao,
                                    os_status,
                                    login,
                                    data_status,
                                    tbl_os_status.admin,
                                    tbl_status_os.descricao
                          ORDER BY      os_status DESC";
            }else{
                if ($login_fabrica == '1') {
                    $format_data = 'DD/MM/YYYY HH24:MI';
                } else {
                    $format_data = 'DD/MM/YYYY';
                }

                if($login_fabrica == 20){
                    $sql_bosch = " AND status_os NOT IN (92,93,94) ";
                }

                $sql_status = " SELECT  os_status,
                                        status_os,
                                        observacao,
                                        tbl_os_status.extrato,
                                        tbl_admin.login AS login,
                                        tbl_extrato.protocolo,
                                        to_char(data, '{$format_data}')   as data_status,
                                        tbl_os_status.admin,
                                        tbl_status_os.descricao
                                FROM    tbl_os_status
                                JOIN    tbl_status_os using(status_os)
                                LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_status.extrato
                                LEFT JOIN    tbl_admin ON tbl_admin.admin = tbl_os_status.admin
                                WHERE   os                              = $os
                                AND     tbl_os_status.fabrica_status    = $login_fabrica
                                $sql_bosch
                          ORDER BY      data DESC";
                          #echo nl2br($sql_status);exit;
            }

            $res_status = pg_query($con,$sql_status);

            $resultado = pg_num_rows($res_status);
            if ($resultado>0){
                echo "<BR>\n";
                echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>\n";
                echo "<TR class='inicio'>\n";
                echo "<TD colspan='7' align='center'>&nbsp;HISTÓRICO DE INTERVENÇÃO</TD>\n";
                echo "</TR>\n";
                echo "<TR class='titulo2'>\n";
                echo "<TD>DATA</TD>\n";
                echo "<TD>TIPO/STATUS</TD>\n";
                echo "<TD>JUSTIFICATIVA</TD>\n";
                echo "<TD>ADMIN</TD>\n";
                echo "</TR>";

                for ($j=0;$j<$resultado;$j++){
                    $extrato_intervencao    =  trim(pg_fetch_result($res_status,$j,'extrato'));
                    $os_status              = trim(pg_fetch_result($res_status,$j,os_status));
                    $status_os              = trim(pg_fetch_result($res_status,$j,status_os));
                    $status_observacao      = trim(pg_fetch_result($res_status,$j,observacao));
                    $status_admin           = trim(pg_fetch_result($res_status,$j,login));
                    $status_data            = trim(pg_fetch_result($res_status,$j,data_status));
                    $status_admin2          = trim(pg_fetch_result($res_status,$j,admin));
                    $descricao              = trim(pg_fetch_result($res_status,$j,descricao));
                    $protocolo_intervencao  = trim(pg_fetch_result($res_status,$j,protocolo));

                    if($login_fabrica == 1){
                        if(strlen($protocolo_intervencao) > 0){
                            $status_observacao = "Ação tomada no Extrato $protocolo_intervencao: ".$status_observacao;
                        }
                    }else{
                        if(strlen($extrato_intervencao) > 0){
                            $status_observacao = "Ação tomada no Extrato $extrato_intervencao: ".$status_observacao;
                        }
                    }

                    if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0 && !in_array($login_fabrica,array(30,144)) && !isset($novaTelaOs)){
                        $status_observacao = strstr($status_observacao,"Justificativa:");
                        $status_observacao = str_replace("Justificativa:","",$status_observacao);
                    }

                    if($login_fabrica == 30 && $ressarcimento == 't' && in_array($status_os,array(192,193,194))){
                        $descricao = str_replace(array("Troca","aprovada","reprovada"),array("Ressarcimento","aprovado","reprovado"),$descricao);
                    }

                    $status_observacao = trim($status_observacao);

                    if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
                    if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

                    if (strlen($status_admin)>0){
                        $status_admin = " $status_admin";
                        if ( in_array($login_fabrica, array(11,172)) ){
                            $status_observacao = trim(pg_fetch_result($res_status,$j,observacao));
                        }
                    }else{
                        $status_admin = "Autom&aacute;tico";
                    }

                    if($login_fabrica == 30){

                        $sql_os_troca = "SELECT ressarcimento FROM tbl_os_troca WHERE os = {$os}";
                        $res_os_troca = pg_query($con, $sql_os_troca);

                        if(pg_num_rows($res_os_troca) > 0){

                            $ressarcimento_troca = pg_fetch_result($res_os_troca, 0, "ressarcimento");

                            if($ressarcimento_troca == "t" && strstr($status_observacao, " - FAR", true)){
                                $descricao = "Produto em ressarcimento";
                            }

                        }

                    }

                    echo "<TR>\n";

                    echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";
                    echo "<TD  class='justificativa' width='140px'  align='left' nowrap>$descricao</TD>\n";
                    echo "<TD  class='justificativa' width='450px' align='left' > $status_observacao";

                    if ( in_array($login_fabrica, array(11,172)) AND strlen($status_admin2)>0 AND $status_admin2 == $login_admin){
                        echo " <a href=\"javascript:excluirComentario('$os','$os_status');\" title='Apagar este comentário'><img src='imagens/delete_2.gif' align='absmiddle'></a>";
                    }
                    echo "</TD>\n";

                    echo "<TD class='justificativa'>$status_admin</TD>\n";
                    echo "</TR>\n";
                }
                echo "</TABLE>\n";
                if ($login_fabrica == 52) {

                        $sqlM = "SELECT tbl_motivo_atraso_fechamento.descricao
                                    FROM tbl_os_campo_extra
                                    JOIN tbl_motivo_atraso_fechamento ON tbl_os_campo_extra.motivo_atraso_fechamento = tbl_motivo_atraso_fechamento.motivo_atraso_fechamento
                                    WHERE tbl_os_campo_extra.os = $os
                                    AND tbl_os_campo_extra.fabrica = $login_fabrica";
                        $resM = pg_query($con, $sqlM);

                        if (pg_num_rows($resM) > 0) {?>
                            <BR><BR>
                            <table width="700" align="center" class="tabela">
                                <caption class="titulo_tabela">Motivos de atendimentos fora do prazo
                                </caption>
                                <tr class='titulo_coluna'>
                                    <th>OS</th>
                                    <th>Motivo</th>
                                </tr><?php

                            }?>
                        </table>
                    <?php
                    }
            }
       }

//hd 24288
if ($login_fabrica==3) {
    $sql_status = "SELECT  tbl_os.os                            ,
                            (SELECT tbl_status_os.descricao FROM tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
                            tbl_os_status.observacao              ,
                            to_char(tbl_os_status.data, 'dd/mm/yyy') AS data
                            FROM tbl_os
                    LEFT JOIN tbl_os_status USING(os)
                    WHERE tbl_os.os    = $os
                    AND   tbl_os_status.status_os IN(
                            SELECT status_os
                            FROM tbl_os_status
                            WHERE tbl_os.os = tbl_os_status.os
                            AND status_os IN (98,99,101) ORDER BY data DESC
                    )";
    $res_km = pg_query($con,$sql_status);

    if(pg_num_rows($res_km)>0){
        echo "<BR>\n";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
        echo "<TR>\n";
        echo "<TD colspan='7' class='inicio'>&nbsp;Historico Atendimento Domicilio</TD>\n";
        echo "</TR>\n";

        for($x=0; $x<pg_num_rows($res_km); $x++){
            $status_os    = pg_fetch_result($res_km, $x, status_os);
            $observacao   = pg_fetch_result($res_km, $x, observacao);
            $data         = pg_fetch_result($res_km, $x, data);

            echo "<tr>";
                echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_os</font></td>";
                echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$observacao</font></td>";
                echo "<td class='justificativa' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>

<?
if($login_fabrica == 19){ //hd_chamado=2881143

    $sql_list="SELECT tbl_checklist_fabrica.codigo,
                    tbl_checklist_fabrica.descricao
                    FROM tbl_os_defeito_reclamado_constatado
                    JOIN tbl_checklist_fabrica USING(checklist_fabrica)
                    WHERE tbl_os_defeito_reclamado_constatado.os = $os
                    AND tbl_checklist_fabrica.fabrica = $login_fabrica
                    AND tbl_os_defeito_reclamado_constatado.checklist_fabrica is not null ";
    $res_list = pg_query($con, $sql_list);

    if(pg_num_rows($res_list) > 0){
        $rows = pg_num_rows($res_list);
?>
        <br/>
        <input type='button' class='visualizar_check' value='Visualizar Checklist'>
        <input type='button' style='display:none;' class='esconder_check' value='Esconder Checklist'>
        <br/><br/>
        <table width="700" style='padding-left:0px; padding-right: 0px; display: none;' align="center" id='checklist_preenchido' border="0" cellspacing="0" cellpadding="5" class="conteudo">
            <tbody>
                <tr>
                    <td class="titulo_tabela" valign="middle" colspan="5">
                        <label style="margin:auto;font:14px Arial">Checklist preenchido</label>
                    </td>
                </tr>
                <tr>
                <?php
                    for ($i=0; $i < $rows; $i++) {
                        $checklist_fabrica = pg_fetch_result($res_list, $i, 'checklist_fabrica');
                        $codigo = pg_fetch_result($res_list, $i, 'codigo');
                        $descricao = pg_fetch_result($res_list, $i, 'descricao');
                        $descricao = utf8_decode($descricao);
                        if(($i % 2) <> 0){
                            echo "<td class='conteudo' align='left' style='padding-top:1em'>
                                     <input type='checkbox' name='check_fabrica_$i' value='$checklist_fabrica' disabled readonly checked> $codigo - $descricao
                                </td></tr>";
                        } else{
                          echo "<tr><td class='conteudo' align='left' style='padding-top:1em'>
                                <input type='checkbox' name='check_fabrica_$i' value='$checklist_fabrica' disabled readonly checked> $codigo - $descricao
                            </td>";
                        }
                    }
                ?>
                </tr>
            </tbody>
        </table>
    <?php
    }
}
# adicionado por Fabio
# HD 13940 - Bosch
if ($login_fabrica==20) {
    $sql_status = "SELECT
                    tbl_os_status.status_os                                    ,
                    tbl_os_status.observacao                                   ,
                    to_char(tbl_os_status.data, 'DD/MM/YYYY')   as data_status ,
                    tbl_os_status.admin                                        ,
                    tbl_status_os.descricao                                    ,
                    tbl_admin.nome_completo AS nome                            ,
                    tbl_admin.email                                            ,
                    tbl_promotor_treinamento.nome  AS nome_promotor            ,
                    tbl_promotor_treinamento.email AS email_promotor
                FROM tbl_os
                JOIN tbl_os_status USING(os)
                LEFT JOIN tbl_status_os USING(status_os)
                LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
                LEFT JOIN tbl_promotor_treinamento ON tbl_os.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento
                WHERE os = $os
                AND status_os IN (92,93,94)
                ORDER BY data ASC";
    $res_status = pg_query($con,$sql_status);
    $resultado = pg_num_rows($res_status);
    if ($resultado>0){
        echo "<BR>\n";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
        echo "<TR>\n";
        echo "<TD colspan='4' class='inicio'>&nbsp;Histórico</TD>\n";
        echo "</TR>\n";
        echo "<TR>\n";
        echo "<TD  class='titulo2' width='100px' align='center'><b>Data</b></TD>\n";
        echo "<TD  class='titulo2' width='170px' align='left'><b>Status</b></TD>\n";
        echo "<TD  class='titulo2' width='260px' align='left'><b>Observação</b></TD>\n";
        echo "<TD  class='titulo2' width='170px' align='left'><b>Promotor</b></TD>\n";
        echo "</TR>\n";
        for ($j=0;$j<$resultado;$j++){
            $status_os          = trim(pg_fetch_result($res_status,$j,status_os));
            $status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
            $status_data        = trim(pg_fetch_result($res_status,$j,data_status));
            $status_admin       = trim(pg_fetch_result($res_status,$j,admin));
            $descricao          = trim(pg_fetch_result($res_status,$j,descricao));
            $nome               = trim(strtoupper(pg_fetch_result($res_status,$j,nome)));
            $email              = trim(pg_fetch_result($res_status,$j,email));
            $nome_promotor      = trim(strtoupper(pg_fetch_result($res_status,$j,nome_promotor)));
            $email_promotor     = trim(pg_fetch_result($res_status,$j,email_promotor));

            echo "<TR>\n";
            echo "<TD  class='justificativa' align='center'><b>".$status_data."</b></TD>\n";
            echo "<TD  class='justificativa' align='left' nowrap>".$descricao."</TD>\n";
            echo "<TD  class='justificativa' align='left'>".$status_observacao."</TD>\n";
            echo "<TD  class='justificativa' align='left' nowrap>";
            if($status_os == 92) { // HD 55196
                echo "<acronym title='Nome: ".$nome_promotor." - \nEmail:".$email_promotor."'>".$nome_promotor;
            }else{
                echo "<acronym title='Nome: ".$nome." - \nEmail:".$email."'>".$nome;
            }
            echo "</TD>\n";
            echo "</TR>\n";
        }
        echo "</TABLE>\n";
    }
}
?>

<?php

if(strlen($obs_adicionais) > 0 && strstr($obs_adicionais, "O produto da OS")){

    echo "
        <br />
        <table width='700px' align='center' cellspacing='1' cellspacing='0' class='Tabela'>
            <tr class='inicio'>
                <td align='center'>
                    HISTÓRICO DE ALTERAÇÃO DE PRODUTO NA OS
                </td>
            </tr>
            <tr class='titulo2'>
                <td>
                    INFORMAÇÕES GRAVADAS AUTOMATICAMENTE PELO SISTEMA
                </td>
            </tr>
            <tr>
                <td class='justificativa' align='left' style='padding: 5px;'>
                    $obs_adicionais
                </td>
            </tr>
        </table>
    ";

}

?>


<?php
    if(in_array($login_fabrica, array(169,170,171))){
        $sql_agendamento = "
            SELECT
                TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                TO_CHAR(tbl_tecnico_agenda.confirmado, 'DD/MM/YYYY') AS data_confirmacao,
                tbl_tecnico.nome AS nome_tecnico,
                tbl_tecnico_agenda.periodo,
                tbl_tecnico_agenda.obs
            FROM tbl_tecnico_agenda
            LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}
            WHERE tbl_tecnico_agenda.os = {$os}
            AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
            ORDER BY tbl_tecnico_agenda.data_input ASC
        ";
        $res_agendamento = pg_query($con, $sql_agendamento);
        $count_agendamento = pg_num_rows($res_agendamento);
        if($count_agendamento > 0){
?>
            <br/>
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
                <tr>
                    <td class='inicio' colspan='8' style="font-size:11pt; text-align: center;">HISTÓRICO DE AGENDAMENTO</td>
                </tr>
                <tr>
                    <TD class="titulo2">#</TD>
                    <TD class="titulo2">Data agendamento</TD>
                    <td class="titulo2">Período</td>
                    <TD class="titulo2">Data confirmação</TD>
                    <TD class="titulo2">Nome técnico</TD>
                    <td class="titulo2">Motivo</td>
                </tr>
                <?php
                    for ($x = ($count_agendamento - 1); $x >= 0; $x--) {
                        $data_agendamento = pg_fetch_result($res_agendamento, $x, 'data_agendamento');
                        $data_confirmacao = pg_fetch_result($res_agendamento, $x, 'data_confirmacao');
                        $nome_tecnico     = pg_fetch_result($res_agendamento, $x, 'nome_tecnico');
                        $periodo          = pg_fetch_result($res_agendamento, $x, 'periodo');
                        $obs              = pg_fetch_result($res_agendamento, $x, 'obs');

                        if ($periodo == "manha"){
                            $txt_periodo = "Manhã";
                        }else if ($periodo == "tarde"){
                            $txt_periodo = "Tarde";
                        } else {
                            $txt_periodo = "";
                        }

                        if ($agendamento_confirmado) {
                            $confirmacao = "Agendamento Alterado";
                        } else {
                            if(strlen(trim($data_confirmacao)) > 0){
                                $confirmacao = $data_confirmacao;
                                $agendamento_confirmado = true;
                            }else{
                                $confirmacao = "Não confirmado";
                            }
                        }
                ?>
                    <tr>
                        <td class='conteudo'><?=$x + 1?></td>
                        <td class='conteudo'><?=$data_agendamento?></td>
                        <td class='conteudo'><?=$txt_periodo?></td>
                        <td class='conteudo'><?=$confirmacao?></td>
                        <td class='conteudo'><?=$nome_tecnico?></td>
                        <td class='conteudo'><?=$obs?></td>
                    </tr>
                <?php
                    }
                ?>
            </table>
<?php
        }
    }
?>

<!--            Valores da OS           -->
<?

if (in_array($login_fabrica,[120,201])) {
    $sql = "SELECT valores_adicionais, mao_de_obra, qtde_km_calculada, produto
            FROM tbl_os WHERE os = $os ";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        $valor_adicional = pg_fetch_result($qry, 0, 'valores_adicionais');
        $valor_mo = pg_fetch_result($qry, 0, 'mao_de_obra');
        $valor_km = pg_fetch_result($qry, 0, 'qtde_km_calculada');
        $produto = pg_fetch_result($qry, 0, 'produto');

        if (empty($valor_mo)) {
            $sql_prod_mo = "SELECT mao_de_obra, valores_adicionais FROM tbl_produto WHERE produto = $produto";
            $qry_prod_mo = pg_query($con, $sql_prod_mo);

            if (pg_num_rows($qry_prod_mo) > 0) {
                $valor_mo = pg_fetch_result($qry_prod_mo, 0, 'mao_de_obra');
                $valores_adicionais_prod = pg_fetch_result($qry_prod_mo, 0, 'valores_adicionais');

                $arr_vals = json_decode(str_replace(',', '.', $valores_adicionais_prod), TRUE);

                $valor_adicional = 0;

                foreach ($arr_vals as $val) {
                    $valor_adicional+= (float) $val;
                }
            }
        }

        if (empty($valor_km)) {
            $sql_km_posto = "SELECT valor_km from tbl_posto_fabrica where posto = $posto and fabrica = $login_fabrica";
            $qry_km_posto = pg_query($con, $sql_km_posto);
            $val_km_posto = 0;

            if (pg_num_rows($qry_km_posto) > 0) {
                $val_km_posto = (float) pg_fetch_result($qry_km_posto, 0, 'valor_km');
            }

            if ($val_km_posto == 0) {
                $sql_km_posto = "SELECT valor_km from tbl_fabrica where fabrica = $login_fabrica";
                $qry_km_posto = pg_query($con, $sql_km_posto);

                if (pg_num_rows($qry_km_posto) > 0) {
                    $val_km_posto = (float) pg_fetch_result($qry_km_posto, 0, 'valor_km');
                }
            }

            $valor_km = $val_km_posto * $qtde_km;
        }

        $valor_total = $valor_adicional + $valor_mo + $valor_km;

        echo "<br>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD align='center'><b>VALOR ADICIONAL</b></TD>";
        echo "<TD align='center'><b>TOTAL KM</b></TD>";
        echo "<td align='center'><b>MÃO-DE-OBRA</b></td>";
        echo "<td align='center'><b>TOTAL</b></td>";
        echo "</tr>";

        echo "<tr style='font-size: 12px; color:#000000' class='justificativa'>";
        echo "<td align='center'>" ;
        echo "<font color='#333377'><b>" . number_format($valor_adicional, 2, ',', '.') . "</b></font>" ;
        echo "</td>";
        echo "<td align='center'>" ;
        echo "<font color='#333377'><b>" . number_format($valor_km, 2, ',', '.') . "</b></font>" ;
        echo "</td>";
        echo "<td align='center'>" ;
        echo "<font color='#333377'><b>" . number_format($valor_mo, 2, ',', '.') . "</b></font>" ;
        echo "</td>";
        echo "<td align='center'>" ;
        echo "<font color='#FF0000'><b>" . number_format($valor_total, 2, ',', '.') . "</b></font>" ;
        echo "</td>";
        echo '</tr>';

        echo '</table>';

    }
}

if ($login_fabrica == "20" or $login_fabrica=="50" ) {

    $sql = "SELECT mao_de_obra
            FROM tbl_produto_defeito_constatado
            WHERE produto = (   SELECT produto
                                FROM tbl_os
                                WHERE os = $os
            )
            AND defeito_constatado = (  SELECT defeito_constatado
                                        FROM tbl_os
                                        WHERE os = $os
            )";

    /* HD 19054 */
    if ($login_fabrica==50 || $login_fabrica == 20){
        $sql = "SELECT mao_de_obra
                FROM tbl_os
                WHERE os = $os
                AND fabrica = $login_fabrica";
    }
    $res = pg_query ($con,$sql);
    $mao_de_obra = 0 ;
    if (pg_num_rows ($res) == 1) {
        $mao_de_obra = pg_fetch_result ($res,0,0);
    }

    $sql = "SELECT tabela , desconto, desconto_acessorio
            FROM tbl_posto_fabrica
            WHERE posto = $posto
            AND fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);
    $tabela = 0 ;
    $desconto = 0;
    $desconto_acessorio = 0;

    if (pg_num_rows ($res) == 1) {
        $tabela = pg_fetch_result ($res,0,tabela);
        $desconto = pg_fetch_result ($res,0,desconto);
        $desconto_acessorio = pg_fetch_result ($res,0,desconto_acessorio);
    }

    if (strlen ($desconto) == 0) $desconto = "0";

    if (strlen ($tabela) > 0) {

        $sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
                FROM tbl_os
                JOIN tbl_os_produto USING (os)
                JOIN tbl_os_item    USING (os_produto)
                JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
                WHERE tbl_os.os = $os
                AND   tbl_os.fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $pecas = 0 ;


        if (pg_num_rows ($res) == 1) {
            $pecas = pg_fetch_result ($res,0,0);
        }
        $pecas = number_format ($pecas,2);
        if (strlen($pecas)=="" or $pecas=="0"){
            $pecas="0,00";
        }

    }else{
        $pecas = "0,00";
    }

    if($login_fabrica==20 ){
        $sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) >0) {
            $sigla_pais = pg_fetch_result ($res,0,pais);
        }
    }

    echo "<br>";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
    echo "<TR class='inicio'>";
    if ($login_fabrica==50){
        echo "<TD><b>VALOR DESLOCAMENTO</b></TD>";
    }else{
        echo "<TD><b>VALOR DAS PEÇAS</b></TD>";
    }
    echo "<td><b>MÃO-DE-OBRA</b></td>";

    if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {
        echo "<td><b>IMPOSTO</b></td>";
    }
    echo "<td><b>TOTAL</b></td>";
    echo "</tr>";


    if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {

        $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $valor_liquido = pg_fetch_result ($res,0,pecas);
            $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
        }
        $sql = "select imposto_al  from tbl_posto_fabrica where posto=$posto and fabrica=$login_fabrica";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $imposto_al   = pg_fetch_result ($res,0,imposto_al);
            $imposto_al   = $imposto_al / 100;
            $acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
        }
        $total = $valor_liquido + $mao_de_obra + $acrescimo;

        $total          = number_format ($total,2,",",".")         ;
        $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
        $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
        $valor_desconto = number_format ($valor_desconto,2,",",".");
        $valor_liquido  = number_format ($valor_liquido ,2,",",".");

        echo "<tr style='font-size: 12px; color:#000000' class='justificativa'>";
        echo "<td align='right'>" ;
        echo "<font color='#333377'><b>$valor_liquido</b></font>" ;
        echo "</td>";
        echo "<td align='center'>$mao_de_obra</td>";
        if(strlen($sigla_pais)>0 and $sigla_pais <> "BR"){
        echo "<td align='center'>$acrescimo</td>";
        }
        if($sistema_lingua=='ES') echo "<td align='center'>+ $acrescimo</td>";
        echo "<td align='center'><font size='3' color='FF0000'><b>$total</b></font></td>";
        echo "</tr>";

    }else{
        echo "<tr style='font-size: 12px ; color:#000000 ' class='justificativa'>";
        echo "<td align='right'>" ;

        if ($login_fabrica<>50){
            echo number_format($pecas,2,",",".");
        }
        if ($desconto > 0 and $pecas > 0) {
            $pecas = str_replace (",",".",$pecas);
            $sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) == 1) {
                $produto = pg_fetch_result ($res,0,0);
            }
            //echo 'peca'.$pecas;
            if( $produto == '20567' ){
                if($login_fabrica==20){
                    $desconto = $desconto_acessorio;
                }else{
                $desconto = '0.2238';
                }

                if($login_fabrica==20){
                    $valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
                }else{
                $valor_desconto = round ( (round ($pecas,2) * $desconto ) ,2);
                }
            }else{
                $valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
            }
            $valor_liquido  = $pecas - $valor_desconto ;
        } else {
            $valor_liquido = $pecas;
        }

        /* HD 19054 */
        $valor_km = 0;
        if($login_fabrica == 50){
            $sql = "SELECT  tbl_os.mao_de_obra,
                            tbl_os.qtde_km_calculada,
                            tbl_os_extra.extrato
                    FROM tbl_os
                    LEFT JOIN tbl_os_extra USING(os)
                    WHERE tbl_os.os = $os
                    AND   tbl_os.fabrica = $login_fabrica";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {
                $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
                $valor_km      = pg_fetch_result ($res,0,qtde_km_calculada);
                $extrato       = pg_fetch_result ($res,0,extrato);
            }
        }

        $total = $valor_liquido + $mao_de_obra + $valor_km;

        $total          = number_format ($total,2,",",".");
        $mao_de_obra    = number_format ($mao_de_obra ,2,",",".");
        $valor_desconto = number_format ($valor_desconto,2,",",".");
        $valor_liquido  = number_format ($valor_liquido ,2,",",".");
        $valor_km       = number_format ($valor_km ,2,",",".");

        if ($login_fabrica==50){
            echo "<font color='#333377'><b>" . $valor_km . "</b></font>" ;
        }else{
            echo "<br><font color='#773333'>Desc. ($desconto%) " . $valor_desconto . "</font>" ;
            echo "<br><font color='#333377'><b>" . $valor_liquido . "</b></font>" ;
        }
        echo "</td>";
        echo "<td align='center'>$mao_de_obra</td>";
        echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></font></td>";
        echo "</tr>";

        /* HD 19054 */
        if ($login_fabrica==50 and strlen($extrato)==0){
            echo "<tr style='font-size: 12px ; color:#000000 ' class='titulo2'>";
            echo "<td colspan='3'>";
            echo "<font color='#757575'>Valores sujeito a alteração até fechamento do extrato</font>" ;
            echo "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    /*HD 9469 - Alteração no cálculo da BOSCH do Brasil*/
    if($login_fabrica == 20) {
        $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $resx = pg_query ($con,$sql);

        if (pg_num_rows ($resx) == 1) {
            $valor_liquido = pg_fetch_result ($resx,0,pecas);
            $mao_de_obra   = pg_fetch_result ($resx,0,mao_de_obra);
            $xtotal = $valor_liquido +$mao_de_obra;

            if (strlen($mao_de_obra)=="" or $mao_de_obra=="0"){
                $mao_de_obra = "0,00";
            }

            if (strlen($xtotal)=="" or $xtotal=="0"){
                $xtotal = "0,00";
            }

            if (strlen($valor_liquido)=="" or $valor_liquido=="0"){
                $valor_liquido = "0,00";
            }


            $xtotal          = number_format ($xtotal,2,",",".");
            $mao_de_obra    = number_format ($mao_de_obra ,2,",",".");
            $valor_liquido  = number_format ($valor_liquido ,2,",",".");


            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
            echo "<TR class='inicio'>";
            echo "<TD width='323px'><b>PREÇO DE PEÇA COM CUSTO ADMINISTRATIVO:</b>&nbsp;</TD>";
            echo "<TD width='244px'><b>MÃO DE OBRA:</b>&nbsp;</TD>";
            echo "<TD><b>TOTAL:</b>&nbsp;</TD>";
            echo "</tr>";
            echo "<tr style='font-size: 12px; color:#000000' class='justificativa'>";
            echo "<td align='right'>$valor_liquido</td>" ;
            echo "<td>$mao_de_obra</td>";
            echo "<td bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$xtotal</b></font></td>";
            echo "</tr>";
            echo "</table>";
        }
    }

}

if ($login_fabrica == 30 or $login_fabrica == 15 || $login_fabrica == 94 ){
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
    echo "<tr  class='inicio'>";
    echo "<td><b>VALOR DESLOCAMENTO</b></td>";
    if ($login_fabrica<>50 && $login_fabrica <> 15 && $login_fabrica <> 94 ){
        echo "<td><b>VALOR DAS PEÇAS</b></td>";
    }
    echo "<td><b>MÃO-DE-OBRA</b></td>";
    echo "<td><b>TOTAL</b></td>";
    echo "</tr>";

    echo "<tr class='justificativa style='font-size: 12px; color:#000000''>";

    $sql = "SELECT  tbl_os.mao_de_obra,
                    tbl_os.qtde_km_calculada,
                    tbl_os_extra.extrato,
                    tbl_os.pecas
            FROM tbl_os
            LEFT JOIN tbl_os_extra USING(os)
            WHERE tbl_os.os = $os
            AND   tbl_os.fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) == 1) {
        $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
        $valor_km      = pg_fetch_result ($res,0,qtde_km_calculada);
        $extrato       = pg_fetch_result ($res,0,extrato);
        $valor_liquido = pg_fetch_result ($res,0,pecas);
    }

    if($login_fabrica == 94 AND empty($valor_km)){
        $sqlKM = "SELECT CASE WHEN tbl_posto_fabrica.valor_km = 0 THEN
                                    tbl_os.qtde_km * tbl_fabrica.valor_km
                                ELSE
                                    tbl_os.qtde_km * tbl_posto_fabrica.valor_km
                                END AS valor_km
                        FROM tbl_os
                        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
                        WHERE tbl_os.os = $os
                        AND  tbl_os.posto = $posto
                        AND tbl_os.fabrica = $login_fabrica;";
        $resKM = pg_query($con,$sqlKM);

        $valor_km = pg_fetch_result($resKM, 0, 'valor_km');
    }

    $total = $valor_liquido + $mao_de_obra + $valor_km;

    $total          = number_format ($total,2,",",".");
    $mao_de_obra    = number_format ($mao_de_obra ,2,",",".");
    $valor_desconto = number_format ($valor_desconto,2,",",".");
    $valor_liquido  = number_format ($valor_liquido ,2,",",".");
    $valor_km       = number_format ($valor_km ,2,",",".");

    echo "<td>$valor_km </td>";
    echo "</td>";

    if ($login_fabrica<>50 && $login_fabrica <> 15 && $login_fabrica <> 94) {
        echo "<td  align='center'>" ;
        echo "$valor_liquido";
        echo "</td>";
    }

    echo "<td align='center'>$mao_de_obra</td>";
    echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
    echo "</tr>";

    /* HD 19054 */
    if (($login_fabrica==50 OR $login_fabrica==30 || $login_fabrica == 15) and strlen($extrato)==0){
        echo "<tr class='titulo2'>";
        echo "<td colspan='4'>";
        echo "<font color='#000'>VALORES SUJEITO A ALTERAÇÃO ATÉ FECHAMENTO DO EXTRATO" ;
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

//incluido por takashi 19/10/2007 - hd4536
//qdo OS é fechada com peças ainda pedente o posto tem que informar o motivo, o motivo a gente mostra aqui
if ($login_fabrica == 3) {
    $sql = "SELECT obs_fechamento from tbl_os_extra where os=$os";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        $motivo_fechamento = pg_fetch_result($res,0,0);
        if(strlen($motivo_fechamento)>0){
            echo "<BR>";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
            echo "<TR>";
            echo "<TD colspan=7 class='inicio'>&nbsp;Justificativa fechamento de OS com peça ainda pendente</TD>";
            echo "</TR>";
            echo "<TR>";
            echo "<TD class='conteudo'>$motivo_fechamento</TD>";
            echo "</TR>";
            echo "</TABLE>";
        }
    }
}

//Colocado por Fabio - HD 14344
//mostra o status da OS: acumulada ou resucasa
# 53003 - mostrar todas as ocorrências e o admin
if ($login_fabrica == 45) {
    $sql = "SELECT  TO_CHAR(data,'DD/MM/YYYY') AS data,
                    tbl_os_status.status_os    AS status_os,
                    tbl_os_status.observacao   AS observacao,
                    tbl_os_status.extrato,
                    tbl_admin.nome_completo
            FROM tbl_os_extra
            JOIN tbl_os_status USING(os)
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
            WHERE os = $os
            AND tbl_os_status.status_os IN (13,14)
            AND tbl_os_status.fabrica_status = $login_fabrica
            AND tbl_os_extra.extrato IS NULL
            ORDER BY tbl_os_status.data DESC";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        echo "<BR>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
        echo "<TR>";
        echo "<TD colspan=7 class='inicio'>EXTRATO - STATUS DA OS</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='titulo2' align='center'>DATA</TD>";
        echo "<TD class='titulo2' align='center'>ADMIN</TD>";
        echo "<TD class='titulo2' align='center'>EXTRATO</TD>";
        echo "<TD class='titulo2' align='center'>STATUS</TD>";
        echo "<TD class='titulo2' align='center'>OBSERVAÇÃO</TD>";
        echo "</TR>";

        for ($i=0; $i<pg_num_rows($res); $i++){
            $status_data       = pg_fetch_result($res,$i,data);
            $status_status_os  = pg_fetch_result($res,$i,status_os);
            $status_observacao = pg_fetch_result($res,$i,observacao);
            $zextrato          = pg_fetch_result($res,$i,extrato);
            $admin_nome        = pg_fetch_result($res,$i,nome_completo);

            if ($status_status_os==13){
                $status_status_os = "Recusada";
            }elseif ($status_status_os==14){
                $status_status_os = "Acumulada";
            }else{
                $status_status_os = "-";
            }

            echo "<TR>";
            echo "<TD class='conteudo' style='text-align: center'>$status_data</TD>";
            echo "<TD class='conteudo' style='text-align: center'>$admin_nome</TD>";
            echo "<TD class='conteudo' style='text-align: center'>$zextrato</TD>";
            echo "<TD class='conteudo' style='padding-left: 5px'>$status_status_os</TD>";
            echo "<TD class='conteudo' style='padding-left: 5px'>$status_observacao</TD>";
            echo "</TR>";
        }
        echo "</TABLE>";
    }
}

if($login_fabrica == 3){

     $sql_hd_chamado = "SELECT
                            tbl_hd_chamado_posto.seu_hd,
                            tbl_hd_chamado.hd_chamado,
                            tbl_hd_chamado.categoria,
                            tbl_hd_chamado.status
                       FROM tbl_hd_chamado
                       JOIN tbl_hd_chamado_extra USING (hd_chamado)
                       LEFT JOIN tbl_hd_chamado_posto USING (hd_chamado)
                       WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
                       AND tbl_hd_chamado_extra.os = {$os}";
    $res_hd_chamado = pg_query($con, $sql_hd_chamado);

    if (pg_num_rows($res_hd_chamado) > 0) {
        include_once "../helpdesk.inc.php";
    ?>
        <br />
        <!-- <table style="width: 700px; margin: 0 auto;" class="Tabela" border="0" cellspacing="1" cellpadding="0"> -->
        <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
            <tr class="inicio">
                <td  style="text-align: center;" colspan="5">HISTÓRICO DE CHAMADOS</td>
            </tr>
            <tr class="titulo2">
                <td >Nº CHAMADO</td>
                <td >TIPO DE SOLICITAÇÃO</td>
                <td >STATUS</td>
                <td >DATA DA ÚLTIMA INTERAÇÃO DO POSTO</td>
                <td >DATA DA ÚLTIMA INTERAÇÃO DO FÁBRICA</td>
            </tr>
            <?php
            for ($k = 0; $k < pg_num_rows($res_hd_chamado); $k++) {
                $seu_hd     = pg_fetch_result($res_hd_chamado, $k, "seu_hd");
                $hd_chamado = pg_fetch_result($res_hd_chamado, $k, "hd_chamado");
                $categoria  = pg_fetch_result($res_hd_chamado, $k, "categoria");
                $status     = pg_fetch_result($res_hd_chamado, $k, "status");

                $aDados     = hdBuscarChamado($hd_chamado);

                $categoria  = $categorias[$aDados['categoria']]['descricao'];


                //$categoria = $categorias[$categoria]['descricao'];

                unset($data_ultima_interacao_posto, $data_ultima_interacao_admin);

                $sql_hd_chamado_item_posto = "SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data_ultima_interacao_posto
                                              FROM tbl_hd_chamado_item
                                              WHERE hd_chamado = {$hd_chamado}
                                              AND admin IS NULL
                                              ORDER BY hd_chamado_item DESC
                                              LIMIT 1";
                $res_hd_chamado_item_posto = pg_query($con, $sql_hd_chamado_item_posto);

                if (pg_num_rows($res_hd_chamado_item_posto) > 0) {
                    $data_ultima_interacao_posto = pg_fetch_result($res_hd_chamado_item_posto, 0, "data_ultima_interacao_posto");
                }

                $sql_hd_chamado_item_admin = "SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data_ultima_interacao_admin
                                              FROM tbl_hd_chamado_item
                                              WHERE hd_chamado = {$hd_chamado}
                                              AND admin IS NOT NULL
                                              ORDER BY hd_chamado_item DESC
                                              LIMIT 1";
                $res_hd_chamado_item_admin = pg_query($con, $sql_hd_chamado_item_admin);

                if (pg_num_rows($res_hd_chamado_item_admin) > 0) {
                    $data_ultima_interacao_admin = pg_fetch_result($res_hd_chamado_item_admin, 0, "data_ultima_interacao_admin");
                }

                echo "<tr>
                    <td class='justificativa'><a href='helpdesk_cadastrar.php?hd_chamado={$hd_chamado}' target='_blank'>".((!empty($seu_hd)) ? $seu_hd : $hd_chamado)."</a></td>
                    <td class='justificativa'>{$categoria}</td>
                    <td class='justificativa'>{$status}</td>
                    <td class='justificativa'>{$data_ultima_interacao_posto}</td>
                    <td class='justificativa'>{$data_ultima_interacao_admin}</td>
                </tr>";
            }
            ?>
        </table>
    <?php
    }

}

/**
 * Interação na Ordem de Serviço
 */

$array_sms = array(35);

/**
 * Interação na Ordem de Serviço
 */
$array_interacao_os = array(11,14,19,24,30,35,40,45,50,51,52,74,80,81,85,86,90,91,96,101,104,114,122,123,125,126,127,131,132,134,136,172);

if ($login_fabrica >= 137) {
    $array_interacao_os = array($login_fabrica);
}


if ($login_fabrica == 3) {
    if (preg_match("/info|\*/", $login_privilegios)) {
        $array_interacao_os = array($login_fabrica);
    }
}

if(in_array($login_fabrica, array(151))){
    $sqlHelpDesk = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status
        FROM tbl_hd_chamado
            INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
        WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
        AND tbl_hd_chamado.titulo = 'Help-Desk Posto'
        AND tbl_hd_chamado_extra.os = {$os}";
    $resHelpDesk = pg_query($con,$sqlHelpDesk);

    if(pg_num_rows($resHelpDesk) > 0){
    ?>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <thead class="Tabela inicio">
            <tr>
                <th style="text-align:center; font-size:12px;">Histórico de Protocolo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="titulo2" style="width:200px;">ATENDIMENTO</td>
                <td class="titulo2">STATUS</td>
            </tr>
            <?php
            while($objeto_hd = pg_fetch_object($resHelpDesk)){
                ?>
                <tr class="conteudo">
                    <td align="center"><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$objeto_hd->hd_chamado?>"><?=$objeto_hd->hd_chamado?></a></td>
                    <td align="center"><?=$objeto_hd->status?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <?php
    }
}

if ($login_fabrica == 158 && !empty($os_numero)) {
?>
    <br />
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela' style="table-layout: fixed;">
        <thead class="Tabela inicio">
            <tr>
                <th style="text-align: center;" colspan="2" >Histórico Mobile</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="titulo2" style="width:200px;">Status</td>
                <td class="titulo2">Data</td>
            </tr>
            <?php
            $sqlOsMobile = "
                SELECT dados, TO_CHAR(data_input, 'DD/MM/YYYY HH24:MI') AS data
                FROM tbl_os_mobile
                WHERE os = {$os}
                ORDER BY data_input DESC
            ";
	    $resOsMobile = pg_query($con, $sqlOsMobile);

            while ($row = pg_fetch_object($resOsMobile)) {
		$row->dados = json_decode($row->dados);

                if (isset($row->dados->exportada)) {
		    $status = "Exportado para o Dispositivo Móvel";
		    $dataAlteracao = $row->data;
		} else {
		    $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
		    $dt->setTimestamp($row->dados->status->dataAlteracao/1000);
		    $dataAlteracao = $dt->format('d/m/Y H:i');
		    // $dataAlteracao = date('d/m/Y H:i', $row->dados->status->dataAlteracao/1000);
                    $status = utf8_decode($row->dados->status->nome);
                }

                echo "
                    <tr class='conteudo' >
                        <td style='text-align: center;' >{$status}</td>
                        <td style='text-align: center;' >{$dataAlteracao}</td>
                    </tr>
                ";
            }
            ?>
        </tbody>
    </table>
<?php
}

if (in_array($login_fabrica, $array_interacao_os) and file_exists('interacao_os.php') ) {
    if ($excluida == "t") {
        $cancelada = "&cancelada=true";
    }
    ?>
    <br />
    <iframe id="iframe_interacao_os" src="interacao_os.php?os=<?=$os?>&iframe=true<?=$cancelada?>" style="width: 700px;" frameborder="0" scrolling="no" ></iframe>
    <br />
<?php
}
/**
 * FIM Interação na Ordem de Serviço
 */

/**
 * Auditor Log
 */
//  echo "-->".$fabrica_usa_log;
if ($login_fabrica == 156 or $fabrica_usa_log){

    $ch = curl_init();

// 	if($novaTelaOs) {
//     curl_setopt($ch, CURLOPT_URL, 'http://api2.telecontrol.com.br/auditor/auditor/aplication/da82d339d0552bcfcf10188a36125270/table/tbl_os/primaryKey/'.$login_fabrica.'*'.$os.'/limit/1');
// 	}else{

    curl_setopt($ch, CURLOPT_URL, 'http://api2.telecontrol.com.br/auditor/auditor/aplication/02b970c30fa7b8748d426f9b9ec5fe70/table/tbl_os/primaryKey/'.$login_fabrica.'*'.$os.'/limit/1');
// 	}
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $res = curl_exec($ch);
    // print_r($res);
    $res = json_decode($res,true);
    //$alteracoes = array_diff($res['content']['antes'], $res['content']['depois']);
    curl_close($ch);

    $data_alteracao_api = $res['content']['depois']['os']['data_alteracao'];
    if($res['user'] == $res['content']['depois']['posto']['id']) {
        $admin_posto = $res['user'] ;
        if (strlen($res['user'])> 0){
                $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $admin_posto";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)>0){
                $admin_posto = pg_fetch_result($res,0,codigo_posto);
            }else{
                $admin_posto = $res['content']['depois']['posto']['id'];
                if (strlen($res['user'])> 0){
                    $sql = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = ".$res['user'];
                    $res = pg_query($con,$sql);
                    $admin_posto = pg_fetch_result($res,0,nome);
                }else{
                    $admin_posto = 'automatico';
                }
            }
        }
    } else {
        if (strlen($res['user'])> 0) {
            $sql = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = ".$res['user'];
            $res = pg_query($con,$sql);
            $admin_posto = pg_fetch_result($res,0,nome);
        } else {
            $admin_posto = 'automatico';
        }
    }

    if(empty($res['exception'])){

        ?>

        <table class="formulario" style="margin: 0 auto; width: 700px; border: 0; font-weight: bold;" cellpadding="3" cellspacing="2">
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <?
        if(!empty($admin_posto)) { ?>
            <tr>
                <td class='conteudo'>Última alteração em: <? echo $data_alteracao_api ?></td>
                <td class='conteudo'>Usuário:  <? echo $admin_posto;?></td>
            </tr>
        <? } ?>

        <td>
            <?php /*if(isset($novaTelaOs)){ */?>
<!--                 <a target='_BLANK' href='relatorio_log_cadastro_os.php?parametro=tbl_os&id=<?php echo $os; ?>'>Visualizar Log Auditor</a> -->
            <?php /*}else{*/ ?>
                <a target='_BLANK' href='relatorio_log_os.php?parametro=tbl_os&id=<?php echo $os; ?>'>Visualizar Log Auditor</a>
            <?php/* }*/ ?>
        </td>
        </table>

    <?
    }
}
/**
 * Fim - Auditor Log
 */

if(isset($novaTelaOs) || in_array($login_fabrica, array(52))){
    list($dia,$mes,$ano) = explode("/", $data_abertura);

    $s3 = new AmazonTC("os", $login_fabrica);

    $nf = $s3->getObjectList("{$os}_", null, $ano, $mes);

    if (count($nf) > 0) {
        $thumb_nf = $s3->getObjectList("thumb_{$os}_", null, $ano, $mes);
        ?>

        <br />

        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
            <thead class="Tabela inicio">
                <tr>
                    <th align="center">Anexo(s)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="conteudo">
                    <td align="center">
                        <?php
                        foreach ($nf as $key => $value) {

                            $anexo    = basename($value);
                            $extensao = preg_replace("/.+\./", "", $anexo);
                            $anexo    = $s3->getLink($anexo, null, $ano, $mes);

                            if($extensao == "pdf"){
                                $thumb_anexo = "imagens/pdf_icone.png";
                            }else if(in_array($extensao, array("doc", "docx"))){
                                $thumb_anexo = "imagens/docx_icone.png";
                            }else{
                                $thumb_anexo = basename($thumb_nf[$key]);
                                $thumb_anexo = $s3->getLink($thumb_anexo, null, $ano, $mes);
                            }

                            $btn_excluir = "<button type='button' name='excluir_anexo' >Excluir</button>";

                            echo "<span style='margin: 10px; display: inline-block;'>
                                <a href='{$anexo}' target='_blank'><img src='{$thumb_anexo}' style='width: 100px; height: 90px;' /></a>
                                <br />
                                <input type='hidden' name='ano' value='$ano' />
                                <input type='hidden' name='mes' value='$mes' />
                                <input type='hidden' name='anexo' value='".basename($value)."' />
                                {$btn_excluir}
                            </span>";
                        }

                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

    <?php
    }
    if($login_fabrica == 153){

            $s3 = new AmazonTC("os_produto_serie", $login_fabrica);

            $anexos = array();

                unset($full);
                $thumb = "imagens/imagem_upload.png";
                $anexo_s3 = $s3->getObjectList("{$os}.", false);
                if (count($anexo_s3) > 0) {
                    $montrarAnexoNS = true;
                    $extensao = preg_replace("/.+\./", "", basename($anexo_s3[0]));
                    $full = $s3->getLink(basename($anexo_s3[0]), false);

                    if($extensao == "pdf"){
                        $thumb = "imagens/pdf_icone.png";
                    }else if(in_array($extensao, array("doc", "docx"))){
                        $thumb = "imagens/docx_icone.png";
                    }else{
                        $thumb = $s3->getLink("thumb_".basename($anexo_s3[0]), false);
                    }
                }else{
                    $montrarAnexoNS = false;
                }

                $anexos = array(
                    "full"  => $full,
                    "thumb" => $thumb
                );
        }


        /*if ($montrarAnexoNS == true and $login_fabrica == 153) {
        ?>

            <br />

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center">Anexo Produto sem N/S</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <?php
                                echo "<span name='anexo_{$key}' style='margin: 10px; display: inline-block; vertical-align: top;'>";

                                    if ($anexos["full"] == null) {
                                        echo "<img src='{$anexos['thumb']}' style='max-width: 100px; max-height: 90px;' /><br />";
                                        ?>
                                        <form name="form_anexo" method="post" action="os_press.php" enctype="multipart/form-data" style="display: none;" >
                                            <input type="file" name="anexo_upload" value="" />
                                            <input type="hidden" name="ajax_anexo_upload" value="t" />
                                            <input type='hidden' name='os' value='<?=$os?>' />
                                            <input type='hidden' name='posicao' value='<?=$key?>' />
                                            <input type='hidden' name='ano' value='<?=$ano?>' />
                                            <input type='hidden' name='mes' value='<?=$mes?>' />
                                        </form>

                                        <button type='button' name='anexar_arquivo' >Anexar</button>
                                    <?php
                                    } else {
                                        echo "<a href='{$anexos['full']}' target='_blank'><img src='{$anexos['thumb']}' style='max-width: 100px; max-height: 90px;' /></a>";
                                    }
                                echo "</span>";


                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

        <?php
        }*/


    if (isset($anexo_peca_os)) {
        $s3_item = new AmazonTC("os_item", $login_fabrica);
        $anexos = $s3_item->getObjectList("{$os}_");

        if (count($anexos) > 0) {
        ?>

            <br />

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center">Anexo das peças</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <?php
                            foreach ($anexos as $value) {
                                $anexo    = basename($value);
                                $extensao = preg_replace("/.+\./", "", $anexo);

                                $os_item = explode("_", preg_replace("/\..+/", "", $anexo));
                                $os_item = $os_item[2];

                                $sql = "SELECT tbl_peca.referencia
                                        FROM tbl_os_item
                                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                                        WHERE tbl_os_item.os_item = {$os_item}";
                                $res = pg_query($con, $sql);

                                $referencia = pg_fetch_result($res, 0, "referencia");

                                $anexo = $s3_item->getLink($anexo);

                                if($extensao == "pdf"){
                                    $thumb_anexo = "imagens/pdf_icone.png";
                                }else if(in_array($extensao, array("doc", "docx"))){
                                    $thumb_anexo = "imagens/docx_icone.png";
                                }else{
                                    $thumb_anexo = "thumb_".basename($value);
                                    $thumb_anexo = $s3_item->getLink($thumb_anexo);
                                }

                                if ($fabrica_exclui_anexo_peca)
                                    $btn_excluir = "<button type='button' name='excluir_anexo_peca' >Excluir</button>";
                                echo "<span style='margin: 10px; display: inline-block;'>
                                    {$referencia}<br />
                                    <a href='{$anexo}' target='_blank'><img src='{$thumb_anexo}' style='width: 100px; height: 90px;' /></a>
                                    <br />
                                    <input type='hidden' name='anexo' value='".basename($value)."' />
                                    $btn_excluir
                                </span>";
                            }

                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php
        }
    }

    $comprovante_troca  = $s3->getObjectList("{$os}_comprovante_troca.");

    if (count($comprovante_troca) > 0) {
        $thumb_comprovante_troca = $s3->getObjectList("thumb_{$os}_comprovante_troca.");
        ?>

        <br />

        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
            <thead class="Tabela inicio">
                <tr>
                    <th align="center">Anexo da Troca de Produto/Ressarcimento</th>
                </tr>
            </thead>
            <tbody>
                <tr class="conteudo">
                    <td align="center">
                        <?php
                        $anexo = basename($comprovante_troca[0]);
                        $anexo = $s3->getLink($anexo);

                        $thumb_anexo = basename($thumb_comprovante_troca[0]);
                        $thumb_anexo = $s3->getLink($thumb_anexo);

                        echo "<span style='margin: 10px; display: inline-block;'>
                            <a href='{$anexo}' target='_blank'><img src='{$thumb_anexo}' style='max-width: 150px; max-height: 150px;' /></a>
                        </span>";
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

    <?php
    }

    if(in_array($login_fabrica, array(164,165)) && $posto_interno == true){
        $anexoNota = $s3->getObjectList("nota-fiscal-saida-".$os, false);

        if (count($anexoNota) > 0) {
            $extensao = preg_replace("/.+\./", "", basename($anexoNota[0]));

            $full = $s3->getLink(basename($anexoNota[0]), false);

            if($extensao == "pdf"){
                $thumb = "imagens/pdf_icone.png";
            }else if(in_array($extensao, array("doc", "docx"))){
                $thumb = "imagens/docx_icone.png";
            }else{
                $thumb = $s3->getLink("thumb_".basename($anexoNota[0]), false);
            }

        }
?>

        <br />

        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
            <thead class="Tabela inicio">
                <tr>
                    <th align="center">Anexo da Nota Fiscal de Saída do produto</th>
                </tr>
            </thead>
            <tbody>
                <tr class="conteudo">
                    <td align="center">
<?php
                        echo "<span style='margin: 10px; display: inline-block;'>
                            <a href='{$full}' target='_blank'><img src='{$thumb}' style='max-width: 150px; max-height: 150px;' /></a>
                        </span>";
?>
                    </td>
                </tr>
            </tbody>
        </table>
<?php
    }
}

    if(in_array($login_fabrica, [167, 203])){
        include_once "class/tdocs.class.php";
        $s3_tdocs = new TDocs($con, $login_fabrica);

        $sqlTdocs = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND referencia_id = $os ORDER BY tdocs DESC LIMIT 1";
        $resTdocs = pg_query($con, $sqlTdocs);

        if(pg_num_rows($resTdocs) > 0){
            $tdocs_id = pg_fetch_result($resTdocs, 0, 'tdocs');
            $link_tdocs = $s3_tdocs->getDocumentLocation($tdocs_id);
    ?>
            <br/>
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center">Anexo Fechamento OS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <a href='<?=$link_tdocs?>' target='_blank'><img src='<?=$link_tdocs?>' style='max-width: 100px; max-height: 90px;' /></a>
                        </td>
                    </tr>
                </tbody>
            </table>
    <?php
        }
    }
if (in_array($login_fabrica, $array_sms) and file_exists('sms_consumidor.php') ) { ?>
    <br />
    <iframe id="iframe_enviar_sms" src="sms_consumidor.php?os=<?=$os?>&iframe=true&consumidor_celular=<?=$consumidor_celular?>" style="width: 700px;" frameborder="0" scrolling="no" ></iframe>
    <br />
<?php
}



if ($consumidor_revenda == "REVENDA") {
    $sql = "SELECT tbl_os_revenda.os_revenda
                FROM tbl_os
                JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
                JOIN tbl_os_revenda_item USING(os_revenda)
                WHERE tbl_os.fabrica = $login_fabrica
                AND os = $os
                AND (os_lote = $os or tbl_os_revenda.sua_os ~ tbl_os.os_numero::text )";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res)> 0 ) {
        $os_revenda = pg_fetch_result($res, 0, "os_revenda");

        if ($login_fabrica == 80) {
            if ($anexaNotaFiscal && temNF2($os, $os_revenda, 'bool')) {
                echo "<div id='DIVanexos'>";
                echo temNF2($os, $os_revenda, 'linkEx') . $include_imgZoom;
                echo "</div>";
            }
        } else {
            if ($anexaNotaFiscal and temNF($os_revenda, 'bool')) {
                echo '<div id="DIVanexos">' . temNF($os_revenda, 'linkEx') .  '</div>';
            }else{
                if ($anexaNotaFiscal and temNF($os, 'bool')) {
                    echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
                }
            }
        }
    }else{
        if ($anexaNotaFiscal and temNF($os, 'bool')) {
            echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
        }

    }
} else if(!isset($novaTelaOs)) {
    if ($anexaNotaFiscal and temNF($os, 'bool')) {
        if ($login_fabrica == '1') {
            $attTblAttrs['tableAttrs'] = " id='anexos0' class='tabela anexos' align='center'";
        }

        echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', false, false, 0) .  '</div>';

        if (temNF($os, 'count') >= 3) {
            $mais_anexos = true;

            if ($login_fabrica == 1) {
                $attTblAttrs['tableAttrs'] = " id='anexos1' class='tabela anexos' align='center'";

                //$anexos0 = temNF($os, 'linkEx', '', false, false, 0);
                //$anexos1 = temNF($os, 'linkEx', '', false, false, 1);

                //if ($anexos0 == $anexos1) {
                //    $mais_anexos = false;
                //}
            }

            if ($mais_anexos) {
                echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', true, true, 1) .  '</div>';
            }
        }
    }
}

    if($login_fabrica == 114){

        include_once "class/aws/s3_config.php";
        include_once S3CLASS;

        $s3 = new AmazonTC("os", $login_fabrica);
        $selo_obrigatorio = $s3->getObjectList("selo_{$login_fabrica}_{$os}");
        $selo_obrigatorio = basename($selo_obrigatorio[0]);
        $selo_obrigatorio = $s3->getLink($selo_obrigatorio);

        if(strlen($selo_obrigatorio) > 0){

        ?>
        <br />

        <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
            <TR>
                <TD>
                    <font size='2' color='#FFFFFF'>
                        <center><b>IMAGEM SELO OBRIGATÓRIO</b></center>
                    </font>
                </TD>
            </TR>
            <TR>
                <TD class='conteudo' style="text-align: center; padding: 10px;">
                    <?php
                        echo "<a href='{$selo_obrigatorio}' target='_blank'><img src='{$selo_obrigatorio}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>";
                    ?>
                </TD>
            </TR>
        </TABLE>

        <?php
        }

    }

if (in_array($login_fabrica, array(141,144))) {
    $sql = "SELECT
                DATE_PART('MONTH', data_abertura) AS mes,
                DATE_PART('YEAR', data_abertura) AS ano
            FROM tbl_os
            WHERE fabrica = {$login_fabrica}
            AND os = {$os}
            AND troca_garantia IS TRUE
            AND ressarcimento IS TRUE";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $amazonTC = new AmazonTC("os", $login_fabrica);

        $mes = pg_fetch_result($res, 0, "mes");
        $ano = pg_fetch_result($res, 0, "ano");

        $comprovante = $amazonTC->getObjectList("comprovante_ressarcimento_{$os}", false, $ano, $mes);

        if (count($comprovante) > 0) {
            $comprovante_basename = basename($comprovante[0]);
            $comprovante_ext = preg_replace("/.+\./", "", $comprovante_basename);

            $comprovante = $amazonTC->getLink($comprovante_basename, false, $ano, $mes);

            if ($comprovante_ext != "pdf") {
                $comprovante_thumb = $amazonTC->getLink("thumb_{$comprovante_basename}", false, $ano, $mes);
            } else {
                $comprovante_thumb = "../imagens/icone_PDF.png";
            }
            ?>
            <table style="margin: 0 auto; width: 698px;" class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th>Comprovante de ressarcimento</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td style="vertical-align: middle; text-align: center;">
                            <img src="<?=$comprovante_thumb?>" style="cursor: pointer;" onclick="window.open('<?=$comprovante?>');" />
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php
        }
    }
}

if($login_fabrica == 141){
    include_once "class/aws/s3_config.php";
    include_once S3CLASS;

    $s3 = new AmazonTC("os", $login_fabrica);

    $nota_fiscal_saida = $s3->getObjectList("nota-fiscal-saida-{$os}");
    $nota_fiscal_saida = basename($nota_fiscal_saida[0]);

    $ext = explode(".", $nota_fiscal_saida);

    $nota_fiscal_saida = ($ext[1] == "pdf") ? "imagens/pdf_icone.png" : $s3->getLink($nota_fiscal_saida);

    if(strlen($nota_fiscal_saida) > 0){

        echo "
        <table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";

            echo "
                <tr>
                    <td align='center'>
                        <font size='2' color='#fff'><center><b>ANEXO DE NOTA FISCAL DE SAÍDA</b></font>
                    </td>
                </tr>";

        echo "<tr>
                <td class='conteudo' style='text-align: center !important;'>";
                    echo "
                        <br />
                            <a href='{$nota_fiscal_saida}' target='_blank'><img src='{$nota_fiscal_saida}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>
                        <br /> <br />";
                echo "
                </td>
            </tr>
        </table>";

    }

}

if ($login_fabrica == 116) {
    $sql = "SELECT tbl_tipo_atendimento.entrega_tecnica FROM tbl_os JOIN tbL_tipo_atendimento USING(tipo_atendimento) WHERE tbl_os.fabrica = $login_fabrica AND os = $os";
    $res = pg_query($con, $sql);

    if (pg_fetch_result($res, 0, "entrega_tecnica") == "t") {
        $sql = "SELECT os FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
        ?>
            <br />
            <table border="0" style="margin: auto;">
                <tr>
                    <td>
                        <a href="../checklist_entrega_tecnica.php?os=<?=$os?>&admin=admin" target="_blank"><img src='logos/toyama4.png' style='width: 80px;' /></a>
                    </td>
                    <td valign="center" >
                        <a href="../checklist_entrega_tecnica.php?os=<?=$os?>&admin=admin" target="_blank"><button type='button' style="cursor: pointer;">Preencher o Check List</button></a>
                    </td>
                </tr>
            </table>
        <?
        } else {
        ?>
            <br />
            <table border="0" style="margin: auto;">
                <tr>
                    <td>
                        <a href="../checklist_entrega_tecnica.php?imprimir=<?=$os?>&admin=admin" target="_blank"><img src='logos/toyama4.png' style='width: 80px;' /></a>
                    </td>
                    <td valign="center" >
                        <a href="../checklist_entrega_tecnica.php?imprimir=<?=$os?>&admin=admin" target="_blank"><button type='button' style="cursor: pointer;">Imprimir o Check List</button></a>
                    </td>
                </tr>
            </table>
        <?php
        }
    }
}

if($login_fabrica == 114){
    $sql = "SELECT os FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os";
    $res = pg_query($con, $sql);

    $sql_linha = "SELECT tbl_produto.linha,to_char(data_digitacao,'YYYY-MM-DD') as data_digitacao FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.fabrica = $login_fabrica WHERE tbl_os.os = $os AND tbl_produto.fabrica_i = $login_fabrica";
    $res_linha = pg_query($con, $sql_linha);

    $linha = pg_fetch_result($res_linha,0,"linha");
    $data_digitacao = pg_fetch_result($res_linha,0,"data_digitacao");

    if ($linha == 691 and $data_digitacao > date('2014-08-13')) {
    if (pg_num_rows($res) == 0) {
?>
        <br />
        <table border="0" style="margin: auto;">
            <tr>
                <td>
                    <img src='logos/cobimex_admin2.jpg' style='width: 120px;' />
                </td>
                <td valign="center" >
            <span style='color: #F00;'>Checklist não preenchido pelo posto</span>
                </td>
            </tr>
        </table>
<?php
    } else {
?>
        <br />
        <table border="0" style="margin: auto;">
            <tr>
                <td>
                    <a href="../checklist_os_item.php?imprimir=<?=$os?>&admin=sim" target="_blank"><img src='logos/cobimex_admin2.jpg' style='width: 120px;' /></a>
                </td>
                <td valign="center" >
                    <a href="../checklist_os_item.php?imprimir=<?=$os?>&admin=sim" target="_blank"><button type='button' style="cursor: pointer;">Imprimir o Check List</button></a>
                </td>
            </tr>
        </table>
<?php
    }
    }
}

if($login_fabrica == 30){

    $sql = "SELECT
                laudo_tecnico_os,
                os,
                data,
                afirmativa
            FROM tbl_laudo_tecnico_os
            WHERE
                fabrica = $login_fabrica
                AND os = $os;";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        ?>

        <table class="table" cellpadding="6" cellspacing="1" border="0" align="center" width="700px" >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="3">Histórico de Laudos</th>
                </tr>
                <tr>
                    <th class="titulo_coluna" width="33%">Data</th>
                    <th class="titulo_coluna" width="33%">Status</th>
                    <th class="titulo_coluna" width="33%">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $rows = pg_num_rows($res);

                for ($i = 0; $i < $rows; $i++) {

                    $laudo_tecnico_os = pg_fetch_result($res, $i, "laudo_tecnico_os");
                    $os               = pg_fetch_result($res, $i, "os");
                    $data             = pg_fetch_result($res, $i, "data");
                    $afirmativa       = pg_fetch_result($res, $i, "afirmativa");

                    list($data, $hora) = explode(" ", $data);

                    list($ano, $mes, $dia) = explode("-", $data);
                    $data = $dia."/".$mes."/".$ano;

                    list($hora, $mls) = explode(".", $hora);

                    $data = $data." ".$hora;

                    $btn_alterar      = false;
                    $btn_alterar_link = "";

                    switch ($afirmativa) {
                        case 't': $status = "<strong style='color: green;'>Aprovada</strong>"; break;
                        case 'f': $status = "<strong style='color: #ff0000;'>Reprovada</strong>"; break;
                        default: $status  = "<strong style='color: blue;'>Em Aprovação</strong>"; $btn_alterar = true; break;
                    }

                    if($btn_alterar == true){

                        $btn_alterar_link = " &nbsp; &nbsp; <a href=cadastro_laudo_troca.php?alterar={$os}&admin=sim&laudo_tecnico_os={$laudo_tecnico_os}&laudo=' target='_blank'> Alterar </a> ";

                    }

                    echo "
                        <tr>
                            <td>{$data}</td>
                            <td>{$status}</td>
                            <td>
                                <a href='cadastro_laudo_troca.php?imprimir={$os}&admin=sim&laudo_tecnico_os={$laudo_tecnico_os}' target='_blank'> Ver Laudo </a>
                                {$btn_alterar_link}
                            </td>
                        </tr>
                    ";

                }

                ?>
            </tbody>
        </table>

        <?php

    /* $sql_p = "SELECT os
                        FROM tbl_os_status
                        WHERE fabrica_status = $login_fabrica
                        AND os = $os
                        AND status_os = 62;";
    $res_p = pg_query($con,$sql_p);
    if(pg_num_rows($res) > 0 OR pg_num_rows($res_p) > 0)  { */
?>
        <!-- <br />
        <table border="0" style="margin: auto;">
            <tr> -->
<?php
        // if (pg_num_rows($res)>0) {
?>
                <!-- <td valign="center" >
                    <a href="cadastro_laudo_troca.php?imprimir=<?=$os?>&admin=sim" target="_blank"><button type='button' style="cursor: pointer;">Ver Laudo</button></a>
                </td> -->
            <?php
        // }
        // if (pg_num_rows($res_p)>0) {
?>
                <!-- <td>
                    <a href="os_item.php?os=<?=$os?>" target="_blank"><button type='button' style="cursor: pointer;">Alterar</button></a>
                </td> -->
<?php
        // }
?>
            <!-- </tr>
        </table> -->
<?php
    } else if ($grava_laudo_botao == 1) {
?>
        <br />
        <table border="0" style="margin: auto;">
            <thead class="Tabela inicio">
                <tr>
                    <th colspan='2'>Cadastro de Laudo Técnico</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:left;">
                        <input type='radio' name='laudo' id="laudo_fat" value='fat'> <label for='laudo_fat' style='cursor:pointer'> F.A.T.</label> <br>
                        <input type='radio' name='laudo' id="laudo_far" value='far'> <label for='laudo_far' style='cursor:pointer'> F.A.R.</label> <br>
                        <input type='radio' name='laudo' id="laudo_fats" value='fats'> <label for='laudo_fats' style='cursor:pointer'> F.A.T. Sinistro</label> <br>
                    </td>
                    <td>
                        <button type="button" id="cadastra_laudo">Cadastrar Laudo</button>
                    </td>
                </tr>
            </tbody>
        </table>

<?php
    }
?>
        <br />
        <table border="0" style="margin: auto;">
            <tr>
<?
    $sql = "
        SELECT  excluida
        FROM    tbl_os
        WHERE   os = $os
    ";
    $res = pg_query($con,$sql);

    $os_cancelada = pg_fetch_result($res,0,excluida);

    if($os_cancelada == 't'){
?>
                <td>
                    <button type="button" id="cancelar_os" name="cancelar_os" onclick="javascript:cancelarOs(<?=$os?>,'liberar')" style="cursor:pointer;"> Reabrir OS </button>
                </td>
<?
    }else{
?>
                <td>
                    <button type="button" id="cancelar_os" name="cancelar_os" onclick="javascript:cancelarOs(<?=$os?>,'cancelar')" style="cursor:pointer;"> Cancelar OS </button>
                </td>
<?
    }
?>

                <td>
                    <button type="button" id="auditorias" name="auditorias" style="cursor:pointer;"> Visualizar Auditorias </button>
                </td>
            </tr>
        </table>
<?
}

if($login_fabrica == 140){

    $sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
    $res_ta = pg_query($con, $sql_ta);

    if(pg_num_rows($res_ta) > 0){
        $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
    }

    if($desc_tipo_atendimento == "Entrega t&eacute;cnica"){

    ?>

    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
        <thead class="Tabela inicio">
            <tr>
                <th align="center">Laudo Técnico</th>
            </tr>
        </thead>
        <tbody>
            <tr class="conteudo">
                <td align="center">
                    <?php
                    $s3 = new AmazonTC("inspecao", $login_fabrica);
                    $laudo_tecnico = $s3->getObjectList($os);
                    $laudo_tecnico = basename($laudo_tecnico[0]);
                    $laudo_tecnico = $s3->getLink($laudo_tecnico);

                    echo "<a href='{$laudo_tecnico}' target='_blank'><img src='{$laudo_tecnico}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>";

                    ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php

    }

}

if($login_fabrica == 145 ){


            $sql = "SELECT os_item
                    FROM tbl_os_item
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                    WHERE tbl_os.os={$os}";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res)>0){
                        $s3laudo = new AmazonTC("laudo_tecnico", $login_fabrica);
                          ?>
                            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                                <thead class="Tabela inicio">
                                    <tr>
                                        <th align="center">Anexos de Parecer Técnico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="conteudo">
                                        <td align='center'>
                                <?php


                                /*string(21) "analise_os_43604750_5"
                                string(14) "/tmp/phpu5dTgy"
                                string(26) "laudo_tecnico/testes/0145/"
                                string(3) "jpg"*/

                        while ($os_item = pg_fetch_object($res)) {

                                    $itens =  $s3laudo->getObjectList("analise_os_{$os_item->os_item}");

                                    $contador = count($itens);
                                    if($contador != "NULL" ){
                                                for ($i=0; $i < $contador; $i++) {

                                                            $ext = strtolower(preg_replace("/.+\./", "", basename($itens[$i])));

                                                            if ($ext == "pdf") {
                                                                        $anexo_imagem = "imagens/pdf_icone.png";
                                                            } else if (in_array($ext, array("doc", "docx"))) {
                                                                        $anexo_imagem = "imagens/docx_icone.png";
                                                            } else {
                                                                        $anexo_imagem = $s3laudo->getLink("thumb_".basename($itens[$i]));
                                                            }

                                                            $anexo_link    = $s3laudo->getLink(basename($itens[$i]));

                                                            echo "<a href='{$anexo_link}' target='_blank'>
                                                                            <img src='{$anexo_imagem}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' />
                                                                    </a>";
                                                }
                                    }
                        }
                        ?>
                                </td>
                            </tr>
                        </tbody>
                        </table>

                        <?php

            }


}



if (in_array($login_fabrica, array(129, 145))) {
    $sql = "SELECT titulo, observacao
            FROM tbl_laudo_tecnico_os
            WHERE fabrica = $login_fabrica
            AND os = $os
            ORDER BY ordem ASC";
    $res = pg_query($con, $sql);

    $rows = pg_num_rows($res);

    unset($laudo_tecnico);

    if($login_fabrica == 145 && $rows > 0){

        $titulo = pg_fetch_result($res, 0, "titulo");
        $obs    = pg_fetch_result($res, 0, "observacao");

        $obs = json_decode($obs);

        $observacao = utf8_decode($obs->observacao);
        $conclusao= utf8_decode($obs->conclusao);

        ?>

        <br />

        <table class="Tabela" width="700px" style="table-layout: fixed; margin: 0 auto;">
            <tr class="inicio">
                <td style="text-align: center;" colspan="2">LAUDO TÉCNICO: <?php echo $titulo; ?></td>
            </tr>
            <tr class="titulo2">
                <td>OBSERVAÇÃO</td>
                <td>CONCLUSÃO</td>
            </tr>
            <tr class="conteudo">
                <td><?php echo $observacao; ?></td>
                <td><?php echo $conclusao; ?></td>
            </tr>
            <tr class="titulo2">
                <td colspan="2" >ANEXOS</td>
            </tr>
            <tr class="conteudo">
                <td style="text-align: center; padding: 5px;" colspan="2">
                    <?php
                        $s3 = new AmazonTC("inspecao", $login_fabrica);
                        $laudo_tecnico = $s3->getObjectList($os."-laudo-tecnico-");

                        if (count($laudo_tecnico)) {
                            foreach ($laudo_tecnico as $key => $value) {
                                $arquivo = basename($value);
                                $arquivo = $s3->getLink($arquivo);

                                $thumb = basename($value);
                                $thumb = $s3->getLink("thumb_".$thumb);

                                echo "<a href='{$arquivo}' target='_blank'><img src='{$thumb}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>&nbsp;&nbsp;&nbsp;";
                            }
                        } else {
                            echo "Sem laudo técnico anexado";
                        }
                    ?>
                </td>
            </tr>
        </table>
        <?php

    }else{

        if ($rows > 0) {
        ?>
            <style>
            #table_laudo_tecnico {
                width: 700px;
                margin: 0 auto;
                border-collapse: collapse;
            }

            #table_laudo_tecnico .titulo {
                text-align: right;
                max-width: 90px;
            }
            </style>
            <br />
            <table id="table_laudo_tecnico" class="Tabela" border="1" >
                <tr>
                    <td class="inicio" style="text-align: center;" colspan="2">
                        Laudo Técnico
                    </td>
                </tr>
                <?php
                for ($i = 0; $i < $rows; $i++) {
                    $laudo_tecnico[pg_fetch_result($res, $i, "titulo")] = pg_fetch_result($res, $i, "observacao");
                }
                ?>
                <tr>
                    <td class="titulo">
                        Nº DA ASSISTÊNCIA
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_posto_numero']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        INSTALADO EM
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_data_instalado']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        NOME DA INSTALADORA
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        ÁGUA UTILIZADA
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_agua_utilizada"]) {
                            case 'direto_da_rua':
                                echo "DIRETO DA RUA/REDE DE ABASTECIMENTO";
                                break;

                            case 'caixa':
                                echo "CAIXA/REDE DE ABASTECIMENTO";
                                break;

                            case 'poco':
                                echo "POÇO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        PRESSURIZADOR
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_pressurizador"]) {
                            case 'true':
                                echo "SIM";
                                break;

                            case 'false':
                                echo "NÃO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        TENSÃO
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_tensao"]) {
                            case '110v':
                                echo "110V";
                                break;

                            case '220v':
                                echo "220V";
                                break;

                            case 'pilha':
                                echo "PILHA";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        TIPO DE GÁS
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_tipo_gas"]) {
                            case 'gn':
                                echo "GN";
                                break;

                            case 'glp':
                                switch ($laudo_tecnico["laudo_tecnico_gas_glp"]) {
                                    case 'estagio_unico':
                                        $estagio = "ESTÁGIO ÚNICO";
                                        break;

                                    case 'dois_estagios':
                                        $estagio = "DOIS ESTÁGIOS";
                                        break;
                                }

                                echo "GLP, $estagio";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        PRESSÃO DE GÁS
                    </td>
                    <td class="conteudo">
                        DINÂMICA: <?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?> (consumo máx.)<br />
                        ESTÁTICA: <?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        PRESSÃO DE ÁGUA
                    </td>
                    <td class="conteudo">
                        DINÂMICA: <?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?> (consumo máx.)<br />
                        ESTÁTICA: <?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        DIÂMETRO DO DUTO
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_diametro_duto']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        COMPRIMENTO TOTAL DO DUTO
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_comprimento_total_duto']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        QUANT. DE CURVAS
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_quantidade_curvas']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        CARACTERÍSTICAS DO LOCAL DE INSTALAÇÃO
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_caracteristica_local_instalacao"]) {
                            case 'externo':
                                echo "EXTERNO";
                                break;

                            case 'interno':
                                echo "INTERNO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        SE INTERNO QUAL O AMBIENTE
                    </td>
                    <td class="conteudo" nowrap >
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_local_instalacao_interno_ambiente"]) {
                            case 'area_servico':
                                echo "ÁREA DE SERVIÇO";
                                break;

                            case 'outro':
                                echo "OUTRO: {$laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente_outro']}";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        INSTALAÇÃO DE ACORDO COM O NBR 13.103
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_instalacao_nbr"]) {
                            case 'true':
                                echo "SIM";
                                break;

                            case 'false':
                                echo "NÃO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        PROBLEMA DIAGNOSTICADO
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        PROVIDÊNCIAS ADOTADAS
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?>
                    </td>
                </tr>
            </table>
        <?php
        }
    }
}

if ($login_fabrica == 86) {
    $sql = "SELECT laudo_tecnico FROM tbl_os_extra WHERE os = $os";
    $res = pg_query($con, $sql);
    $laudo = pg_fetch_result($res, 0, "laudo_tecnico");

    if (!empty($laudo)) {
        $sql = "SELECT tbl_marca.logo
                FROM tbl_os
                JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                AND tbl_produto.fabrica_i = $login_fabrica
                JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                AND tbl_marca.fabrica = $login_fabrica
                WHERE tbl_os.os = {$_GET['os']}";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $logo = pg_fetch_result($res, 0, 'logo');
            $logo = (empty($logo)) ? "logos/famastil.png" : $logo;
        }
        ?>
            <br />
            <table border="0" style="margin: auto;">
                <tr>
                    <td>
                        <a href="../laudo_tecnico_famastil.php?imprimir=ok&os=<?=$os?>&admin=admin" target="_blank"><img src='<?=$logo?>' style='width: 80px;' /></a>
                    </td>
                    <td valign="center" >
                        <a href="../laudo_tecnico_famastil.php?imprimir=ok&os=<?=$os?>&admin=admin" target="_blank"><button type='button' style="cursor: pointer;">Imprimir o Laudo Técnico</button></a>
                    </td>
                </tr>
            </table>
        <?php

    }
}

# hd 21896 - Francisco Ambrozio - inclusão do laudo técnico
if ($login_fabrica == 1 or $login_fabrica == 19) {
    if($login_fabrica == 1){
        $tiraPesquisa = " AND titulo NOT ILIKE 'Pesquisa %'";
    }
    if ($admin_consulta_os == false) {
        $sql = "SELECT
                tbl_laudo_tecnico_os.*
            FROM
                tbl_laudo_tecnico_os
            WHERE
                os = $os
                $tiraPesquisa
            ORDER BY ordem";
    } else {
        $sql ="SELECT
                tbl_laudo_tecnico_os.*
            FROM
                tbl_laudo_tecnico_os
                    JOIN tbl_laudo_tecnico USING(titulo)
            WHERE
                os = $os
                $tiraPesquisa
                AND tbl_laudo_tecnico.usuario_consulta IS TRUE;";
    }

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {?>
        <br>
        <table width="700" border="0" cellpadding="0" align='center' class='Tabela'>
            <TR>
                <TD colspan="9" class='inicio'>&nbsp;LAUDO TÉCNICO</TD>
            <tr>
                <td class='titulo2' style='width: 30%'><?=($login_fabrica==19)?'QUESTÃO':'TÍTULO';?></td>
                <td class='titulo2' style='width: 10%'>AFIRMATIVA</td>
                <td class='titulo2' style='width: 60%'>OBSERVAÇÃO</td>
            </tr><?php

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $laudo      = pg_fetch_result($res, $i, 'laudo_tecnico_os');
                $titulo     = pg_fetch_result($res, $i, 'titulo');
                $afirmativa = pg_fetch_result($res, $i, 'afirmativa');
                $observacao = pg_fetch_result($res, $i, 'observacao');

                echo "<tr>";
                echo "<td class='conteudo' align='left' style='width: 30%'>&nbsp;$titulo</td>";
                if (strlen($afirmativa) > 0) {
                    echo "<td class='conteudo' style='width: 10%'><center>";
                    echo ($afirmativa == 't') ? "Sim" : "Não";
                    echo "</center></td>";
                } else {
                    echo "<td class='conteudo' style='width: 10%'>&nbsp;</td>";
                }

                if (strlen($observacao) > 0) {
                    echo "<td class='conteudo' style='width: 60%'><CENTER>$observacao</CENTER></td>";
                } else {
                    echo "<td class='conteudo' style='width: 60%'>&nbsp;</td>";
                }

                echo "</tr>";

            } ?>
            </tr>

            <?php if($login_fabrica == 1){?>
                <tr>
                    <td colspan='3' align='center'>
                        <input type='button' value='CLIQUE AQUI PARA IMPRIMIR LAUDO TÉCNICO DESTE ATENDIMENTO' onclick="javascript: window.open('gerar_laudo_tecnico.php?os=<?=$os?>&laudo=<?=$laudo?>');">
                    </td>
                </tr>
            <?php } ?>
        </table><?php

    }

}
# Finaliza inclusão do laudo técnico

if($login_fabrica == 81) {

    $sql = "SELECT laudo_tecnico FROM tbl_os_extra WHERE os = $os AND scrap";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        echo "<br/>";
        echo "<center>";
        echo "<table width='500' align='center' class='Tabela'>";
            echo "<tr class='inicio'><td align='center'>OS Scrapeada</td></tr>";
            echo "<tr class='inicio'>";
                echo "<td  align='center'>Laudo Técnico</td>";
            echo "</tr>";

            $laudo = pg_fetch_result($res,0,laudo_tecnico);

            echo "<tr>";
                echo "<td class='conteudo' align='left'>&nbsp;$laudo</td>";
            echo "</tr>";
        echo "</table></center>";

    }

}

echo '<br />';

if ($login_fabrica == 66 or $login_fabrica == 43 or $login_fabrica == 14) {

    echo "<center><table>
        <tr class='Conteudo'>
            <td align='center'><a href='#'>
                <img src='imagens/btn_solicitar_coleta.gif'></a>
            </td>
        </tr>
    </table></center>";

}
if($login_fabrica == 74){
    if(count($os_campos_adicionais) > 0){
        if($os_campos_adicionais['ns_sequencia'] == "t"){?>
            <table align='center' width='700px' class='Tabela' cellpadding='1' cellspacing='1' border='0'>
                <tr class='inicio'>
                    <td colspan='4' align='center'>DEFEITO NS</td>
                </tr>
                <tr>
                    <td colspan="4" class='titulo2'>O número de série deste produto é referente à um produto com defeito de NS</td>
                </tr>
            </table>
            <br/>
        <? }
    }

    $sql = "
        SELECT
            tbl_produto_serie.observacao
        FROM tbl_os
        JOIN tbl_produto_serie ON tbl_os.produto = tbl_produto_serie.produto
        WHERE tbl_os.os = $os AND tbl_os.serie BETWEEN tbl_produto_serie.serie_inicial AND tbl_produto_serie.serie_final
    ";
    $res_consulta = pg_query($con, $sql);
    if(pg_num_rows($res_consulta) > 0){
        $observacao = pg_fetch_result($res_consulta, 0, 'observacao');

        if (strlen($observacao) > 0) {
            echo "<div style='color: #ff0000; text-align: center; width: 690px; margin: 0 auto; margin-bottom: 10px;  padding: 5px; border: 1px solid #ff0000; font-size: 14px;'>Observação de Número de Série: <strong>".$observacao."</strong></div>";
        }
    }

}
if ($auditoria == 't') {

    $sql = "SELECT admin,login
        FROM tbl_os
        JOIN tbl_admin USING(admin)
        WHERE os = $os
        AND   tbl_os.fabrica = $login_fabrica";

    $res1 = pg_query ($con, $sql);

    if (pg_num_rows($res1) > 0) {
        $sadmin = trim(pg_fetch_result($res1, 0, 'login'));
        echo "OS digitada por <b>$sadmin</b>";
    } else {
        echo "OS digitada por <b>$posto_nome</b>";
    }

    #HD 216600
    echo "<form method='post' name='frm_auditoria' action='".$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']."'>";
    echo "<input type='hidden' name='os' value='$os'>";
    echo "<input type='hidden' name='sua_os' value='$sua_os'>";
    echo "<input type='hidden' name='posto' value='$posto'>";
    echo "<p>";

    if ($tem_pedido == 't') {
        echo "<input type='submit' name='btn_acao' value='Reprovar' disabled>";
    } else {
        echo "<input type='submit' name='btn_acao' value='Reprovar'>";
    }

    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<input type='submit' name='btn_acao' value='Analisar'>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<input type='submit' name='btn_acao' value='Aprovar'>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<input type='submit' name='btn_acao' value='Aprovar sem Mão de Obra'>";
    echo "<br>Motivo da Reprova:<br><textarea name='justificativa_reprova'ROWS='4' COLS='40' ></textarea>";
    echo "</form>";
} else {

    if($login_fabrica == 50 or $login_fabrica == 117 or $login_fabrica == 128){
        if ($S3_sdk_OK) {
            include_once S3CLASS;

            $s3ge = new anexaS3('ge', (int) $login_fabrica);
            $S3_online = is_object($s3ge);

            if ($s3ge->temAnexos($os)) {
                $link = getAttachLink($s3ge->url, '', true);
                if($link['ico'] == 'image.ico'){
                    $link['ico'] = $link['url'];
                }else{
                    $link['ico'] = "imagens/image.ico";
                }
                echo "<br>";
                echo "<table align='center' class='tabela'>
                        <tr class='titulo_tabela'>
                            <td style='color: #fff; font-family: Arial; font-size: 8pt; background-color: #485989;'>COMPROVANTE GARANTIA ESTENDIDA</td>
                        </tr>
                        <tr>
                            <td align='center'>";
                                echo createHTMLLink($link['url'],"<img width='100' src='{$link['ico']}' />", "target='_blank'");
                echo "      </td>
                        </tr>
                      </table>";
            }
        }
    }

        /* Google Maps */

        //HD 367384 - INICIO
        if ($consumidor_revenda[0] == 'C' && in_array($login_fabrica, array(11,50,120,201,114,115,35,91,116,94,117,74,172)) and 1==2) {

            $sql_end_posto = "select contato_endereco, contato_numero, contato_cidade, contato_estado, contato_cep
            from tbl_posto_fabrica join tbl_posto using(posto)
            where posto = $codigo_posto and fabrica = $login_fabrica";
            $res_end_posto = pg_query($con, $sql_end_posto);

            if(pg_num_rows($res_end_posto) > 0){

                /* Endereço do Posto */
				while($end = pg_fetch_object($res_end_posto)){

                    if($end->contato_endereco != "" or $end->contato_cidade != "" or $end->contato_estado != ""){
                        if($end->contato_numero != ""){ $end->contato_numero = ", ".$end->contato_numero; }
                        $end_posto = $end->contato_endereco."".$end->contato_numero.", ".$end->contato_cidade.", ".$end->contato_estado.", Brasil";
                    }else{
                        $end_posto = $end->contato_cep;
                    }
						$posto_cep = $end->contato_cep;
						$posto_cep_mapa = "cep: ".preg_replace('/(\d{5})(\d{3})/','${1}-${2}',$posto_cep);
                }

                /* Endereço do Consumidor */
                if($consumidor_endereco != "" or $consumidor_cidade != "" or $consumidor_estado != ""){
                    if($consumidor_numero != ""){ $consumidor_numero = ", ".$consumidor_numero; }
                    $end_cons = $consumidor_endereco."".$consumidor_numero.", ".$consumidor_cidade.", ".$consumidor_estado.", Brasil";
                }else{
                    $end_cons = $consumidor_cep;
                }

				$consumidor_cep_mapa = "cep: ".preg_replace('/(\d{5})(\d{3})/','${1}-${2}',$consumidor_cep);
                ?>

                    <!-- CSS e JavaScript Google Maps -->
                    <link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">
                    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>

                    <style type="text/css">
                        #GoogleMapsContainer{
                            width: 698px;
                            height: 400px;
                            border: 1px solid #000;
                            margin: 0 auto;
                            margin-bottom: 20px;
                        }
                        #DirectionPanel{
                            width: 249px;
                            height: 400px;
                            float: right;
                            background-color: #fff;
                            overflow: auto;
                        }
                        #GoogleMaps{
                            width: 449px;
                            height: 400px;
                            float: left;
                            background-color: #fff;
                        }

                        @media print {
                            #mostraMapa, #GoogleMapsContainer {
                                display: none;
                            }
                        }

                        button{
                            width: 110px;
                        }
                    </style>

                    <script type="text/javascript">

                        var directionsDisplay = new google.maps.DirectionsRenderer();
                        var directionsService = new google.maps.DirectionsService();
                        var map;

                        function initialize() {
                            var mapOptions = {
                              zoom: 7,
                              mapTypeId: google.maps.MapTypeId.ROADMAP,
                              center: new google.maps.LatLng(41.850033, -87.6500523)
                            };
                            map = new google.maps.Map(document.getElementById('GoogleMaps'),
                                mapOptions);

                            directionsDisplay.setMap(map);

                            directionsDisplay.setPanel(document.getElementById('DirectionPanel'));

                            var request = {
                                  origin: '<?=$end_posto;?>',
                                  destination: '<?=$end_cons;?>',
                                  travelMode: google.maps.DirectionsTravelMode.DRIVING
                            };
							directionsService.route(request, function(response, status) {
								var km = response.routes[0].legs[0].distance.value;
								km = parseFloat(km);
								km = km /1000;
								var distancia_km = parseFloat(<?=$qtde_km;?>);
								if(km*2 - distancia_km > 300) {
									status = 'no';
								}

                                if (status == google.maps.DirectionsStatus.OK) {
                                    directionsDisplay.setDirections(response);
								}else{
										var posto =  '<?=$posto_cep_mapa;?>,Brasil';
										var consumidor =  '<?=$consumidor_cep_mapa;?>, Brasil';
										var request = {
										  origin: posto,
										  destination: consumidor,
										  travelMode: google.maps.DirectionsTravelMode.DRIVING
									  };
										directionsService.route(request, function(response, status) {
												if (status == google.maps.DirectionsStatus.OK) {
													directionsDisplay.setDirections(response);
												}
										});
								}
                            });

                        }

                        function opcaomapa(){
                            $('#GoogleMapsContainer').toggle();
                            if(!$('#GoogleMapsContainer').is(':visible')){
                                $('button[name=opcaomapa]').html('Ver Mapa');
                            }else{
                                $('button[name=opcaomapa]').html('Ocultar Mapa');
                            }
                        }

                        google.maps.event.addDomListener(window, 'load', initialize);

                    </script>

                    <p id="mostraMapa"><strong>Mapa entre o Posto e o Consumidor</strong> <button name='opcaomapa' onclick='opcaomapa()'>Ocultar Mapa</button> </p>

                    <div id="GoogleMapsContainer">
                        <div id="GoogleMaps"></div>
                        <div id="DirectionPanel"></div>
                    </div>

                <?php

            }else{
                echo '<br /> <p id="mostraMapa"><strong>Mapa entre o Posto e o Consumidor</strong></p>';
                echo "<p font='color: red;'>Endereço do Posto não localizado, por favor verifique os dados informados.</p>";
            }

        }
//HD 367384 - FIM - Fim Google Maps
if ($login_fabrica==125){

?>
<br/><br/>

<div name="container_img" id='container_img' style="display: block;">

     <?
        //verifica se tem imagens de peças criticas
        $sqlPecaCritica = " SELECT  referencia
                            FROM tbl_os_item
                            JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
                            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
                            WHERE fabrica_i = $login_fabrica AND
                                  tbl_os.os = $os AND
                                  tbl_peca.peca_critica is true AND
                                  servico_realizado in (10740,10741,10742)";

        $res = pg_query($con,$sqlPecaCritica);
        $qtde_pecas_criticas = pg_num_rows($res);
        if($qtde_pecas_criticas > 0){ ?>
        <span>Fotos das Peças Críticas</span><br/><br/>
        <? }
        for ($i = 0; $i < $qtde_pecas_criticas; $i++) {

            $referencia_peca_critica = pg_fetch_result($res, $i, "referencia");

            $amazonTC->getObjectList("peca_critica-{$os}-{$referencia_peca_critica}-",false,"","");
            $pathinfo = pathinfo($amazonTC->files[0]);
            $dadosPeca = explode("-", $pathinfo["filename"] );
            $linhaPeca = $dadosPeca[3];
            $link = $amazonTC->getLink($pathinfo["basename"]);
            $linkMini = $amazonTC->getLink("thumb_".$pathinfo["basename"]);
            ?>

            <span>Referência Peça: <?=$referencia_peca_critica?></span>
            <a href="<?=$link?>"><img id="mini_anexar_img_<?=$linhaPeca?>" name="mini_anexar_img_<?=$linhaPeca?>" src="<?=$linkMini?>" title="Clique aqui para inserir a imagem" alt="Clique aqui para inserir a imagem" onclick='javascript: $("#img_peca_critica_<?=$linhaPeca?>").click();'></img></a>
            <input type="hidden" value="true" id="peca_critica_<?=$linhaPeca?>" name="peca_critica_<?=$linhaPeca?>" />
            <input type="hidden" value="<?=$pathinfo["extension"]?>" id="peca_critica_ext_<?=$linhaPeca?>" name="peca_critica_ext_<?=$linhaPeca?>" />

            <br/>

    <? }?>

</div>
<br/>
<?
}

if (in_array($login_fabrica,array(88,129,145,161))) {
	$sql = "SELECT tbl_os.os
                FROM tbl_os
                    INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.os = {$os}
                    AND UPPER(tbl_status_checkpoint.descricao) = 'FINALIZADA'";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "SELECT tbl_resposta.resposta
                    FROM tbl_resposta
                        INNER JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa
                            AND tbl_pesquisa.fabrica = {$login_fabrica}
                    WHERE tbl_resposta.os = {$os}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) { ?>

			<div id="pesquisa_satisfacao2" style="background-color: #D9EDF7; padding-top: 8px; padding-bottom: 8px; margin-bottom: 0px; cursor: pointer; width: 700px; margin: 0 auto;" ><h5 style="margin-top: 0px; margin-bottom: 0px; background-color: transparent; color: #5D87AD;" >Clique para visualizar a Pesquisa de Satisfação</h5></div>

        <?php
        } else {

			$sql = "SELECT pesquisa , categoria
                        FROM tbl_pesquisa
                        WHERE fabrica = {$login_fabrica}
                            AND ativo IS TRUE
                            AND categoria in ('ordem_de_servico','ordem_de_servico_email')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

                $categoria_pesquisa = pg_fetch_result($res, 0, categoria);
                ?>
                <div id="pesquisa_satisfacao2" style="background-color: #D9EDF7; padding-top: 8px; padding-bottom: 8px; margin-bottom: 0px; cursor: pointer; width: 700px; margin: 0 auto;" ><h5 style="margin-top: 0px; margin-bottom: 0px; background-color: transparent; color: #5D87AD;" >Clique para preencher a Pesquisa de Satisfação</h5></div>
                <?php
                if ( $categoria_pesquisa == 'ordem_de_servico_email' AND in_array($login_fabrica,array(129,161)) ) { ?>
                    <br />
                    <button id='disparar_pesquisa' rel='<?=$os?>'>Enviar pesquisa por email</button>
                    <br />
                    <br />

                <?php
                }
			}
		} ?>

		<script>

		$(function() {
			$("#pesquisa_satisfacao2").click(function() {
				if ($("#pesquisa_satisfacao_iframe").is(":visible")) {
					$("#pesquisa_satisfacao_iframe").hide();
				} else {
					$("#pesquisa_satisfacao_iframe").show();

					$("#pesquisa_satisfacao_iframe").css({height: $($("#pesquisa_satisfacao_iframe")[0].contentDocument).find("body").css("height")});
				}
			});
		});

		</script>

		<iframe id="pesquisa_satisfacao_iframe" src="pesquisa_satisfacao_new.php?os=<?=$os?>&local=ordem_de_servico" style="width: 700px; display: none; margin: 0 auto;" scrolling="no" frameborder="0" ></iframe>
<?php
	}
}
if (!$admin_consulta_os) {?>
        <div align='center'>
            <br /><?php
            if ($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18 OR $tipo_atendimento == 35)) {
            ?>
                <a href="os_cadastro_troca_black.php"><img src="imagens/btn_lancanovaos.gif"></a><?php
            } else {
                if (isset($novaTelaOs)) {
                    if($login_fabrica == 151){


                        $gera_pedido = "false";
                        $troca_produto = "false";

                        $sql = "SELECT  tbl_os_item.pedido_item,
                                        tbl_servico_realizado.gera_pedido,
                                        tbl_servico_realizado.troca_produto
                                    FROM tbl_os_item
                                    INNER JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                                    INNER JOIN tbl_os on tbl_os_produto.os = tbl_os.os
                                    LEFT JOIN tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                                    INNER JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                    WHERE tbl_os.os = $os
                                    AND tbl_os.fabrica = $login_fabrica ";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res)>0 ){

                            $result = pg_fetch_all($res);
                            // var_dump($result);
                            foreach ($result as $key => $value) {

                                if((!strlen($value["pedido_item"]) or $value["pedido_item"] == NULL ) and $value["gera_pedido"] == "t" ) {
                                    $gera_pedido = "true";
                                    if($value["troca_produto"] == "t"){
                                        $troca_produto = "true";
                                    }
                                }
                            }

                             ?>
                                <script type="text/javascript">

                                $(function() {
                                    var troca = "false";
                                    troca = <?=json_encode($troca_produto)?>;
                                    var os = <?=$os?>;
                                    $("#gerar_pedido_mondial").click(function(){

                                        $.ajax({
                                            async: false,
                                            url : "os_cadastro_unico/fabricas/<?=$login_fabrica?>/ajax_gerar_pedido_manual.php",
                                            type: "get",
                                            data: { gera_pedido_manual: true, os: os , troca :troca },
                                            beforeSend: function() {
                                                 // $("#gerar_pedido_mondial").prop( "disabled", true );
                                                 $("#gerar_pedido_mondial").val("Gerando Pedido...").prop({ disabled: true });
                                                 // $.parseJSON
                                            },
                                            success: function(data) {
                                                data = JSON.parse(data);

                                                if (data.erro) {
                                                    alert(data.erro);
                                                    $("#gerar_pedido_mondial").prop({ disabled: false });
                                                    $("#gerar_pedido_mondial").val("Gerar Pedido");
                                                } else if(data.sucesso == "true") {
                                                    $("#gerar_pedido_mondial").val("Pedido Gerado com Sucesso").prop({ disabled: true });
                                                    location.reload();
                                                }

                                            }
                                        });
                                    });
                                });

                                </script>
                                <?php


                            $sql = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = {$os} AND liberada IS NULL AND bloqueio_pedido IS TRUE";
                            $res = pg_query($con,$sql);

                            if((pg_num_rows($res)==0) and $gera_pedido == "true" and $troca_produto == "false"){

                                // posvenda/rotinas/mondial/gera-pedido.php
                                echo "<input type='button' id='gerar_pedido_mondial' value='Gerar Pedido'><br />";
                            }elseif((pg_num_rows($res)==0) and $gera_pedido == "true" and $troca_produto == "true"){

                                echo "<input type='button' id='gerar_pedido_mondial' value='Gerar Pedido de Troca'><br />";
                            }

                        }
                    }
                ?>
                    <a href="cadastro_os.php"><input type='button' value="Lan&ccedil;ar nova Ordem de Servi&ccedil;o"></a>
                <?php if ($login_fabrica == 158) { ?>
                <a href="os_press.php?exportar=true&os=<?=$os?>"><input type='button' value="Exportar OS"></a>

                <?php } ?>
                <?php
                } else {
                ?>

                    <a href="os_cadastro.php"><input type='button' value="Lan&ccedil;ar nova Ordem de Servi&ccedil;o"></a>
                <?php
                }
            }

            if (in_array($login_fabrica, array(169, 170))) {
                $sql_familia = "
                    SELECT tbl_familia.deslocamento
                    FROM tbl_os_produto
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                    INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
                    WHERE tbl_os_produto.os = {$os}
                    AND tbl_produto.produto_principal IS TRUE
                ";
                $res_familia = pg_query($con, $sql_familia);

                if (pg_num_rows($res_familia) > 0) {
                    $x_familia_deslocamento = pg_fetch_result($res_familia, 0, "deslocamento");

                    $sql_os_numero = "SELECT os_numero FROM tbl_os WHERE fabrica = {$login_fabrica} AND os_numero = {$os}";
                    $res_os_numero = pg_query($con, $sql_os_numero);

                    if (pg_num_rows($res_os_numero) > 0) {
                        $x_os_numero = true;
                    } else {
                        $x_os_numero = false;
                    }

                    if (strtolower($x_familia_deslocamento) == "t" && !empty($hd_chamado) && empty($os_numero) && !$x_os_numero) {
                    ?>
                        <button type="button" onclick="window.open('cadastro_os.php?chave_os_conjunto=<?=sha1($os.$login_fabrica)?>&os=<?=$os?>');" >Abrir Ordem de Serviço para o produto do Conjunto</button>
                    <?php
                    }
                }
            }

            if(in_array($login_fabrica, [167, 203]) AND $nome_atendimento == 'Orçamento'){

                $dados_orcamento_email['geral']["os"]                   = $os;
                $dados_orcamento_email['geral']["produto_referencia"]   = utf8_encode($produto_referencia);
                $dados_orcamento_email['geral']["produto_descricao"]    = utf8_encode($produto_descricao);
                $dados_orcamento_email['geral']["produto_referencia"]   = $produto_referencia;
                $dados_orcamento_email['geral']["produto_descricao"]    = utf8_encode($produto_descricao);
                $dados_orcamento_email['geral']["numero_serie"]         = ($numero_serie) ? $numero_serie : '' ;
                $dados_orcamento_email['geral']["mao_de_obra"]          = ($mao_de_obra)  ? $mao_de_obra : '' ;
                $dados_orcamento_email['geral']["valor_total_pecas"]    = number_format($valor_total_pecas, 2, ",", ".");
                $dados_orcamento_email['geral']["valor_adicional"]      = number_format($valor_adicional, 2, ",", ".");
                $dados_orcamento_email['geral']["total_geral"]          = number_format($total_geral, 2, ",", ".");

                $dados_orcamento_email['geral']["email_consumidor"]     = $consumidor_email;
                $dados_orcamento_email['geral']["posto_nome"]           = utf8_encode($posto_nome);

                $dados_orcamento_email = json_encode($dados_orcamento_email,true);
                #$dados_orcamento_email = str_replace("\\", "\\\\", json_encode($dados_orcamento_email));
            ?>
                <input type="hidden" id="dados_email" value='<?=$dados_orcamento_email?>'>
                <button onclick="enviar_orcamento();" >Enviar orçamento para o consumidor</button>
            <?php
            }
	    if ($login_fabrica == 151) {

	     ?>
                    <a href="cadastro_os.php?os_id=<?=$os?>"><input type='button' value="Alterar"></a>
	     <?php
	    }


            if ($login_fabrica == 24) {?>
                <input type="button" value='Checar Novamente Reincidência' name="btn_checa_reincidencia" id="btn_checa_reincidencia" onclick="window.location='<?echo $PHP_SELF."?os=$os&checa_reinc=s"?>'"/><?php
            }?>

            <?php
            if (in_array($login_fabrica,array(157))) {
                $anexaNotaFiscal = true;
                if ($consumidor_revenda == "REVENDA") {
                    $sql = "SELECT tbl_os_revenda.os_revenda
                                FROM tbl_os
                                JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
                                JOIN tbl_os_revenda_item USING(os_revenda)
                                WHERE tbl_os.fabrica = $login_fabrica
                                AND os = $os
                                AND (os_lote = $os or tbl_os_revenda.sua_os ~ tbl_os.os_numero::text )";
                    $res = pg_query($con, $sql);
                    if (pg_num_rows($res)> 0 ) {
                        $os_revenda = pg_fetch_result($res, 0, "os_revenda");


                        if ($anexaNotaFiscal and temNF($os_revenda, 'bool')) {
                            echo '<div id="DIVanexos">' . temNF($os_revenda, 'linkEx') .  '</div>';
                        } else {
                            if ($anexaNotaFiscal and temNF($os, 'bool')) {
                                echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
                            }
                        }

                    }else{
                        if ($anexaNotaFiscal and temNF($os, 'bool')) {
                            echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
                        }

                    }
                } else if(!isset($novaTelaOs)) {
                    if ($anexaNotaFiscal and temNF($os, 'bool')) {
                        if ($login_fabrica == '1') {
                            $attTblAttrs['tableAttrs'] = " id='anexos0' class='tabela anexos' align='center'";
                        }

                        echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', false, false, 0) .  '</div>';

                        if (temNF($os, 'count') >= 3) {
                            $mais_anexos = true;

                            if ($mais_anexos) {
                                echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', true, true, 1) .  '</div>';
                            }
                        }
                    }
                }

            }

            ?>

            <span style='display:inline-block;_zoom:1;width: 3em'>&nbsp;</span>
            <a href="javascript: void(0);" onclick='window.open("os_print.php?os=<? echo $os ?>","imprimir");'><input type='button' value='Imprimir'></a>
            <?php if(in_array($login_fabrica,array(35))) {?>
                <a href="javascript: void(0);" onclick='window.open("os_print.php?os=<? echo $os ?>&tipo=detalhado","imprimir");'><input type='button' value='Imprimir Versão Completa'></a>
            <?php
            }

            if(in_array($login_fabrica,array(91))) { ?>
                <br />
                <br />
                <a target="_blank" href="relatorio_log_os.php?parametro=tbl_os_item&id=<?=$os?>"><font size="-2">Log de alterações</font></a>
            <?php
            }?>
            <br />
            <br />
            <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_os&id=<?php echo $os; ?>' name="btnAuditorLog">Visualizar Log Auditor</a>
        </div>
        <p>&nbsp;</p><?php
    }

}

if (in_array($login_fabrica, array(169,170))) {
    $sql = "
	SELECT DISTINCT
	    os
	FROM tbl_os o
	JOIN tbl_os_produto op USING(os)
	LEFT JOIN tbl_os_item oi USING(os_produto)
	LEFT JOIN tbl_pedido_item pi USING(pedido_item)
	WHERE o.fabrica = {$login_fabrica}
	AND ((o.finalizada IS NOT NULL AND pi.pedido_item IS NULL)
	OR (pi.pedido_item IS NOT NULL AND o.os_posto IS NULL))
	AND o.cancelada IS NOT TRUE
	AND o.excluida IS NOT TRUE
	AND os = {$os};
    "; 
    $res = pg_query($con, $sql); 
    if (pg_num_rows($res) > 0) { ?>
	<button type="button" class="exporta_os">Exportar OS</button>
    <? }
}

if($login_fabrica == 30){
    if ($S3_sdk_OK) {
        include_once S3CLASS;

        $s3tj = new anexaS3('tj', (int) $login_fabrica);
        $S3_online = is_object($s3tj);

        if ($s3tj->temAnexos($os)) {
            $link = getAttachLink($s3tj->url, '', true);

            echo "<table align='center' class='tabela'>
                    <tr class='titulo_tabela'>
                        <td>Comprovante Troca Produto / Processo Judicial</td>
                    </tr>
                    <tr>
                        <td align='center'>";
                            echo createHTMLLink($link['url'],"<img width='64' src='../imagens/{$link['ico']}' />", "target='_blank'");
            echo "      </td>
                    </tr>
                  </table>";
        }
    }
}

 if (in_array($login_fabrica, array(30))) {

    echo "<center><input type='button' id='pesquisa_satisfacao' value='Pesquisa de Satisfação' /></center>";

    /* echo '<div style="width:700px;margin: 0 auto;">';
        echo '<br />';
        include_once 'pesquisa_satisfacao_new.php';
     echo '</div>';*/

}

include "rodape.php"; ?>

