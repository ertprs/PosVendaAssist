<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include ($_GET['lu_pedido'] == 'sim') ? "login_unico_autentica_usuario.php" : 'autentica_usuario.php';
include 'funcoes.php';
include_once 'class/communicator.class.php';
include_once 'class/json.class.php';


if(isset($_POST['getLinkNF'])){
    
    $nf = $_POST['nf'];
    $cnpj = $_POST["cnpj"];

    $NFeID = getLinkNF($nf, $cnpj);

    echo json_encode(array("NFeID" => $NFeID));

    exit;
}


if (in_array($login_fabrica, array(175)) && $_POST['aguardando_aprovacao']) {
    try {
        $acao   = $_POST['aguardando_aprovacao'];
        $pedido = $_POST['pedido'];
        
        if (empty($pedido)) {
            throw new \Exception('Pedido inválido');
        }
        
        $sqlPedido = "SELECT pedido, aprovado_cliente, status_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND pedido = {$pedido}";
        $resPedido = pg_query($con, $sqlPedido);
        
        if (!pg_num_rows($resPedido)) {
            throw new \Exception('Pedido inválido');
        }
        
        $aprovado = pg_fetch_result($resPedido, 0, 'aprovado_cliente');
        var_dump($aprovado);
        $status   = pg_fetch_result($resPedido, 0, 'status_pedido');
        
        if (!empty($aprovado)) {
            throw new \Exception('Pedido já aprovado');
        }
        
        if (status == 14) {
            throw new \Exception('Pedido já cancelado');
        }
        
        $transaction = false;
        
        if ($acao == 'aprova') {
            $sql = "
                UPDATE tbl_pedido SET
                    aprovado_cliente = CURRENT_TIMESTAMP,
                    exportado = CURRENT_TIMESTAMP,
                    status_pedido = 2
                WHERE pedido = {$pedido}
            ";
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
                throw new \Exception('Erro ao aprovar pedido');
            }
            
            $pedido_aprovado = true;
        } else if ($acao == 'cancela') {
            $sql = "
                SELECT pedido_item, peca, qtde
                FROM tbl_pedido_item
                WHERE pedido = {$pedido}
            ";
            $res = pg_query($con, $sql);
            
            if (!pg_num_rows($res)) {
                throw new \Exception('Erro ao cancelar pedido #1');
            }
            
            $pecas = pg_fetch_all($res);
            
            pg_query($con, 'BEGIN');
            $transaction = true;
            
            $sql = "
                UPDATE tbl_pedido SET
                    status_pedido = 14
                WHERE pedido = {$pedido}
            ";
            $res = pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
                throw new \Exception('Erro ao cancelar pedido #2');
            }
            
            foreach ($pecas as $peca) {
                $sql = "
                    INSERT INTO tbl_pedido_cancelado
                    (pedido, posto, fabrica, peca, qtde, data, pedido_item)
                    VALUES
                    ({$pedido}, {$login_posto}, {$login_fabrica}, {$peca['peca']}, {$peca['qtde']}, CURRENT_DATE, {$peca['pedido_item']})
                ";
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception('Erro ao cancelar pedido #3');
                }
                
                $sql = "
                    UPDATE tbl_pedido_item SET
                        qtde_cancelada = {$peca['qtde']}
                    WHERE pedido_item = {$peca['pedido_item']}
                ";
                $res = pg_query($con, $sql);
                
                if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
                    throw new \Exception('Erro ao cancelar pedido #4');
                }
            }
            
            pg_query($con, 'COMMIT');
            $pedido_cancelado = true;
        }
    } catch(\Exception $e) {
        if ($transaction == true) {
            pg_query($con, 'ROLLBACK');
        }
        
        $msg_erro = $e->getMessage();
    }
}

if(in_array($login_fabrica, array(11,172))){

    $pedido = $_REQUEST["pedido"];

    if(strlen($pedido) > 0){

        $sql_fabrica = "SELECT fabrica FROM tbl_pedido WHERE pedido = {$pedido}";
        $res_fabrica = pg_query($con, $sql_fabrica);

        if(pg_num_rows($res_fabrica) > 0){

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

            if($fabrica_os != $login_fabrica){

                $self = $_SERVER['PHP_SELF'];
                $self = explode("/", $self);

                unset($self[count($self)-1]);

                $page = implode("/", $self);
                $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
                $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?pedido={$pedido}";

                $params = "?cook_admin=&cook_fabrica={$fabrica_os}&page_return={$pageReturn}";
                $page = $page.$params;

                header("Location: {$page}");
                exit;

            }

        }

    }
}

if($_POST['buscaCorreios']){
    $objeto = $_POST['objeto'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://correiosrastrear.com/{$objeto}");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    $resultado = curl_exec($ch);
    curl_close($ch);
    echo utf8_decode($resultado);
    exit;
}

if(in_array($login_fabrica, array(138,141,144,142,168)) || isset($telaPedido0315)){
    include_once S3CLASS;
    $s3 = new AmazonTC("pedido", $login_fabrica);
}

$mostra_data_aprovacao = in_array($login_fabrica, array(138));

if(isset($_POST['btn_anexo'])){

    $pedido = $_POST["pedido"];
    $arquivoS3 = $_FILES["anexo"];
    $anexoS3 = "{$pedido}_0";
    $s3->upload($anexoS3, $arquivoS3);

    $sql_posto_bloqueio = "SELECT posto_bloqueio, desbloqueio FROM tbl_posto_bloqueio WHERE posto = $login_posto AND fabrica = $login_fabrica order by data_input desc limit 1";
    $res_posto_bloqueio = pg_query($con, $sql_posto_bloqueio);

    if(pg_num_rows($res_posto_bloqueio)>0){
        $desbloqueio = pg_fetch_result($res_posto_bloqueio, 0, 'desbloqueio');

        if ($desbloqueio == 'f') {
            $status_pedido = 18;

            $sql_update = "UPDATE tbl_pedido SET status_pedido = $status_pedido WHERE pedido = $pedido";
            $res_update = pg_query($con, $sql_update);

            $sql_status = "INSERT INTO tbl_pedido_status (pedido, status, data, observacao) VALUES ($pedido, $status_pedido, now(), 'Posto bloqueado') ";
            $res_status = pg_query($con, $sql_status);

            $mensagem_email = "Foi gerado um pedido $pedido para o posto $login_nome e esta aguardando aprovação.";
            $assunto = "Aprovação do Pedido $pedido";
        } else {
            $mensagem_email = "O pedido $pedido esta aguardando liberação de pagamento.";
            $assunto = "Liberar o Pedido $pedido";
        }

        $mailTc = new TcComm($externalId);//classe
        $contato_email = "financeiro@acaciaeletro.com.br";
        $res = $mailTc->sendMail(
            $contato_email,
            $assunto,
            $mensagem_email,
            'helpdesk@telecontrol.com.br'
        );
    }
}

function regiao_suframa(){

	global $login_posto, $con;

	$sql_suframa = "SELECT suframa FROM tbl_posto WHERE posto = {$login_posto}";
	$res_suframa = pg_query($con, $sql_suframa);

	$suframa = pg_fetch_result($res_suframa, 0, "suframa");

	return ($suframa == "t") ? true : false;

}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    if (filter_input(INPUT_POST,'tipo') == "aprova_frete") {
        $pedido         = filter_input(INPUT_POST,'pedido');
        $valor_frete    = filter_input(INPUT_POST,'valor_frete');
        $transportadora = filter_input(INPUT_POST,'transportadora_frete');

        $valor_frete_email = number_format($valor_frete, 2, ",", ".");

        $valor_frete = (strstr($valor_frete,',')) ? fnc_limpa_moeda($valor_frete) : $valor_frete;

        pg_query($con,"BEGIN TRANSACTION");

        $sqlGravaFrete = "
            UPDATE  tbl_pedido
            SET     valor_frete = $valor_frete,
                    valores_adicionais = '{$transportadora}',
                    status_pedido = 1
            WHERE   fabrica = $login_fabrica
            AND     pedido = $pedido
        ";
        $resGravaFrete = pg_query($con,$sqlGravaFrete);

        if (pg_last_error($con)) {

            pg_query($con,"ROLLBACK TRANSACTION");
            echo "erro";

            exit;
        } else {

            $sql_posto = "SELECT
                            tbl_posto.nome,
                            tbl_posto.cnpj
                        FROM tbl_posto
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                        WHERE
                            tbl_posto_fabrica.fabrica = {$login_fabrica}
                            AND tbl_posto_fabrica.posto = {$login_posto}";
            $res_posto = pg_query($con, $sql_posto);

            $nome_posto = pg_fetch_result($res_posto, 0, "nome");
            $cnpj_posto = pg_fetch_result($res_posto, 0, "cnpj");

            include_once 'class/communicator.class.php';
            $mailTc = new TcComm($externalId);

            $assunto = "O Pedido n° {$pedido} teve a sua cotação de frete aprovada - Fábrica: Acacia M";

            $mensagem = "
                Prezados, <br />
                A cotação do frete do pedido n° {$pedido} do posto CNPJ: {$cnpj_posto} - {$nome_posto} foi aprovado pelo posto autorizado. <br />
                Forma de envio escolhida: <strong>{$transportadora}</strong> <br />
                Valor do Frete: R$ <strong>{$valor_frete_email}</strong>
            ";

            if($_serverEnvironment == "development"){
                $email_destinatario = "guilherme.silva@telecontrol.com.br, oscar.borges@telecontrol.com.br";
            }else{
                $email_destinatario = "eduardo.miranda@telecontrol.com.br, ricardo.tamiao@telecontrol.com.br";
            }

            $mailTc->sendMail(
                $email_destinatario,
                $assunto,
                $mensagem,
                $externalEmail
            );

        }

        pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("ok"=>true));

        exit;

    }
}

if (isset($_POST["acaoS3"])) {
    $arquivoS3 = $_FILES["arquivoS3"];
    $anexoS3   = $_POST["anexoS3"];
    $posicao   = $_POST["posicao"];

    if (strlen($arquivoS3["tmp_name"]) > 0) {
        $ext = strtolower(preg_replace("/.+\./", "", $arquivoS3["name"]));

        if ($ext == "jpeg") {
            $ext = "jpg";
        }

        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("erro" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
        } else {
            $anexoS3 = "{$pedido}_{$posicao}";

            $s3->upload($anexoS3, $arquivoS3);

            if ($s3->ifObjectExists($anexoS3.".{$ext}")) {
                $imagemS3      = $s3->getLink("{$anexoS3}.{$ext}");

                switch ($ext) {
                    case "pdf":
                        $thumbImagemS3 = "imagens/pdf_icone.png";
                        break;

                    case "doc":
                    case "docx":
                        $thumbImagemS3 = "imagens/docx_icone.png";
                        break;

                    default:
                        $thumbImagemS3 = $s3->getLink("thumb_{$anexoS3}.{$ext}");
                        break;
                }

                $retorno = array(
                    "anexoS3"       => utf8_encode($anexoS3.".{$ext}"),
                    "thumbImagemS3" => utf8_encode($thumbImagemS3),
                    "imagemS3"      => utf8_encode($imagemS3),
                    "posicao"       => $posicao

                );
            } else {
                $retorno = array("erro" => utf8_encode("Erro ao anexar arquivo"));
            }
        }
    } else {
        $retorno = array("erro" => utf8_encode("Erro ao anexar, arquivo não selecionado"));
    }

    exit(json_encode($retorno));
}

// HD 153966
$login_fabrica = (strlen($_GET['lu_fabrica']) > 0) ? $_GET['lu_fabrica'] : $login_fabrica;
if(isset($_GET["lu_fabrica"])){
    $fabrica = $_GET["lu_fabrica"];
    $sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
            tbl_posto_fabrica.posto,
            tbl_posto_fabrica.fabrica
        FROM tbl_posto_fabrica
        WHERE fabrica = $fabrica
        AND posto     = $cook_posto";
    $res = pg_query($sql);
    if(pg_num_rows($res)>0){
        remove_cookie ($cookie_login, "cook_posto_fabrica");
        remove_cookie ($cookie_login, "cook_posto");
        remove_cookie ($cookie_login, "cook_fabrica");
        remove_cookie ($cookie_login, "cook_login_posto");
        remove_cookie ($cookie_login, "cook_login_nome");
        remove_cookie ($cookie_login, "cook_login_cnpj");
        remove_cookie ($cookie_login, "cook_login_fabrica");
        remove_cookie ($cookie_login, "cook_login_fabrica_nome");
        remove_cookie ($cookie_login, "cook_login_pede_peca_garantia");
        remove_cookie ($cookie_login, "cook_login_tipo_posto");
        remove_cookie ($cookie_login, "cook_login_e_distribuidor");
        remove_cookie ($cookie_login, "cook_login_distribuidor");
        remove_cookie ($cookie_login, "cook_pedido_via_distribuidor");

        add_cookie ($cookie_login, "cook_posto_fabrica", pg_fetch_result($res, 0, 'posto_fabrica'));
        add_cookie ($cookie_login, "cook_posto", pg_fetch_result($res, 0, 'posto'));
        add_cookie ($cookie_login, "cook_fabrica", pg_fetch_result($res, 0, 'fabrica'));

        set_cookie_login($token_cookie, $cookie_login);

    }
}

$documento      = $_GET['documento'];
$data_documento = $_GET['data_documento'];
$peca           = $_GET['peca'];
$os             = $_GET['os'];
$pedido         = $_GET['pedido'];
$bloq           = $_GET["bloq"];

if ($login_fabrica == 1){
    header("Location: pedido_blackedecker_finalizado_new.php?pedido=".$_GET['pedido']."&bloq=$bloq");
    exit;
}

$atualiza_conferencia = trim($_GET['atualiza_conferencia']);

if(strlen($atualiza_conferencia)>0){
    $faturamento   = trim($_GET['faturamento']);
    $conferencia   = trim($_GET['conferencia']);
    if(strlen($faturamento)>0 and strlen($conferencia)>0) {

        $conferencia = fnc_formata_data_pg($conferencia);

        if ($conferencia <> 'null' and strlen($conferencia)>0){
            $sql = "UPDATE tbl_faturamento SET conferencia = $conferencia
                    WHERE faturamento = $faturamento
                    AND   fabrica     = $login_fabrica";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_last_error($con);
            if(strlen($msg_erro)>0){
                fecho('atualizado.com.sucesso', $con);
            }
        }
    }
    exit;
}

if ($_POST["aprovar_pedido"]) {
    if ($login_fabrica == 138) {
        $comprovante_pagamento = $_FILES["comprovante_pagamento"];
        $copia_pedido          = $_FILES["copia_pedido"];
        $condicao              = strtoupper($_POST['condicao']);

        pg_query($con, "BEGIN");

		if ($mostra_data_aprovacao)
			$atualiza_aprovado = ', aprovado_cliente = CURRENT_TIMESTAMP';
        $sql = "UPDATE tbl_pedido SET status_pedido = 20$atualiza_aprovado WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND pedido = {$pedido}";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $msg_erro = "Erro ao aprovar pedido";
        }

        if (strlen($copia_pedido["name"]) > 0) {
            $types = array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx");
            $type  = strtolower(preg_replace("/.+\//", "", $copia_pedido["type"]));

            if ($type == "jpeg") {
                $type = "jpg";
            }

            if (!in_array($type, $types)) {
                $msg_erro = "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx";
            }
        } else {
            $msg_erro = "Para aprovar o pedido faça o anexo da cópia do pedido assinado";
        }

        if ($condicao == "ANTECIPADO") {
            if (strlen($comprovante_pagamento["name"]) > 0) {
                $types = array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx");
                $type  = strtolower(preg_replace("/.+\//", "", $comprovante_pagamento["type"]));

                if ($type == "jpeg") {
                    $type = "jpg";
                }

                if (!in_array($type, $types)) {
                    $msg_erro = "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx";
                }
            }else{
                $msg_erro = "Para aprovar o pedido faça o anexo do comprovante de pagamento";
            }
        }

        if (isset($msg_erro)) {
            pg_query($con, "ROLLBACK");
        } else {
            pg_query($con, "COMMIT");
            $s3->upload("{$login_fabrica}_{$login_posto}_{$pedido}", $comprovante_pagamento);
            $s3->upload("copia_{$login_fabrica}_{$login_posto}_{$pedido}", $copia_pedido);
            $msg_sucesso = "Pedido aprovado";
        }
    } else if ($login_fabrica == 143) {
        try {
            include "rotinas/wackerneuson/exporta-pedido-funcao.php";

            pg_query($con, "BEGIN");

            $sql = "SELECT
                        pedido_item,
                        preco AS valor_unitario,
                        total_item AS valor_total,
                        acrescimo_financeiro AS novo_valor_unitario,
                        acrescimo_tabela_base AS novo_valor_total
                    FROM tbl_pedido_item
                    WHERE pedido = {$pedido}";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                throw new Exception("Erro ao aprovar pedido");
            }

            while ($item = pg_fetch_object($res)) {
                $update = "UPDATE tbl_pedido_item SET
                                preco                 = {$item->novo_valor_unitario},
                                total_item            = {$item->novo_valor_total},
                                acrescimo_financeiro  = {$item->valor_unitario},
                                acrescimo_tabela_base = {$item->novo_valor_total}
                           WHERE pedido = {$pedido}
                           AND pedido_item = {$item->pedido_item}";
                $resUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao aprovar pedido");
                }
            }

            $sqlTotal = "SELECT SUM(total_item) AS total_pedido FROM tbl_pedido_item WHERE pedido = {$pedido}";
            $resTotal = pg_query($con, $sqlTotal);

            $total_pedido = pg_fetch_result($resTotal, 0, "total_pedido");

            $sqlDesconto = "SELECT desconto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
            $resDesconto = pg_query($con, $sqlDesconto);

            $desconto = pg_fetch_result($resDesconto, 0, "desconto");

            if ($desconto > 0) {
                $total_pedido += ($total_pedido / 100) * $desconto;
            }

			$update = "UPDATE tbl_pedido SET status_pedido = 2, total = {$total_pedido} WHERE pedido = {$pedido} ;  INSERT into tbl_pedido_status(pedido, status, observacao)values($pedido, 2, 'Aprovado pelo posto') ;  ";
            $resUpdate = pg_query($con, $update);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao aprovar pedido");
            }

            $sql = "SELECT seu_pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido} AND posto = {$login_posto}";
            $res = pg_query($con, $sql);

            $pedido_wacker_neuson = pg_fetch_result($res, 0, "seu_pedido");

            $resultadoAprovacao = aprovaPedidoWackerNeuson($pedido_wacker_neuson);

            if (!empty($resultadoAprovacao->erroExecucao)) {
                throw new itemException("Erro ao aprovar pedido");
            }

            pg_query($con, "COMMIT");
            $msg_sucesso = "Pedido Aprovado";
        } catch (Exception $e) {
            $msg_erro = $e->getMessage();
            pg_query($con, "ROLLBACK");
        }
    }
}

if ($_POST["cancelar_pedido"]) {
    try {
        pg_query($con, "BEGIN");

        $sql = "UPDATE tbl_pedido SET status_pedido = 14 WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND pedido = $pedido;

                UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido = $pedido;

                INSERT INTO tbl_pedido_cancelado
                (pedido, posto, fabrica, peca, qtde, motivo, data)
                SELECT
                    pedido, posto, fabrica, peca, qtde, 'Pedido cancelado pelo Posto Autorizado', current_date
                FROM tbl_pedido
                INNER JOIN tbl_pedido_item USING(pedido)
                WHERE pedido = $pedido";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao cancelar pedido");
        }

        pg_query($con, "COMMIT");
        $msg_sucesso = "Pedido cancelado";
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
        pg_query($con, "ROLLBACK");
    }
}

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "gravar") {
    $qtde_item = $_POST['qtde_item'];

    for ($i = 0 ; $i <= $qtde_item ; $i++) {

        $documento      = $_POST['documento_'.$i];
        $data_documento = $_POST['data_documento_'.$i];
        $peca           = $_POST['peca_'.$i];
        $os             = $_POST['os_'.$i];
        $pedido         = $_POST['pedido_'.$i];

        $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_documento')");
        if (strlen (pg_last_error ($con) ) > 0) $msg_erro = pg_last_error ($con) ;
        $xdata_documento = @pg_fetch_result ($fnc,0,0);

        if(strlen($documento)>0 AND strlen($data_documento)>0){
            $res = @pg_query ($con,"BEGIN TRANSACTION");
            $sql = "INSERT INTO tbl_faturamento(
                        fabrica,
                        posto,
                        emissao,
                        saida,
                        nota_fiscal,
                        total_nota
                    )VALUES(
                        '$login_fabrica',
                        '$login_posto',
                        '$xdata_documento',
                        '$xdata_documento',
                        '$documento',
                        '0'
                    )";
                $res = @pg_query ($con,$sql);
                $msg_erro = pg_last_error($con);
                if (strlen($msg_erro) == 0){
                    $res = @pg_query ($con,"SELECT CURRVAL ('seq_faturamento')");
                    $faturamento  = @pg_fetch_result ($res,0,0);
                }

            $sql = "INSERT INTO tbl_faturamento_item(
                        faturamento,
                        peca,
                        preco,
                        qtde,
                        pedido
                    )VALUES(
                        '$faturamento',
                        '$peca',
                        '0',
                        '1',
                        '$pedido'
                    )";
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_last_error($con);
            if (strlen($msg_erro) == 0){
                $res = pg_query ($con,"SELECT CURRVAL ('seq_faturamento_item')");
                $faturamento_item  = pg_fetch_result ($res,0,0);
            }

            $sql = "SELECT os_item FROM tbl_os_item
                    JOIN tbl_os_produto USING(os_produto)
                    JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
                    WHERE tbl_os.os = $os
                    AND tbl_os_item.peca   = $peca
                    AND tbl_os_item.pedido = $pedido
                    AND tbl_os_item.faturamento_item IS NULL";
            $res = @pg_query ($con,$sql);

            if(pg_num_rows($res)>0){
                $os_item = pg_fetch_result($res,0,os_item);
            }

            $sqlx = "UPDATE tbl_os_item set faturamento_item = '$faturamento_item'
                    WHERE os_item = $os_item";
            $res = @pg_query ($con,$sqlx);
            $msg_erro = pg_last_error($con);
            $res = (strlen ($msg_erro) == 0) ? @pg_query ($con,"COMMIT TRANSACTION") : @pg_query ($con,"ROLLBACK TRANSACTION");
        }
    }
}

$liberar_preco = ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) ? false : true ;

#------------ Le Pedido da Base de dados ------------#
$pedido = (strlen($_GET['pedido'])>0) ? $_GET['pedido'] : $_POST['pedido'];
if(in_array($login_fabrica,array(88,120,201,156))){
    $nome_transportadora = " tbl_transportadora.fantasia AS transportadora_nome, ";
    $join_transportadora = " LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora ";
}

if($login_fabrica == 94){
    $nome_transportadora = " tbl_transportadora.nome AS transportadora_nome, ";
    $join_transportadora = " LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora ";
}

if (strlen ($pedido) > 0) {
    if($login_fabrica == 87){
        $campo_nome_admin = " tbl_admin.nome_completo,  ";
        $join_admin = " LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_pedido.admin  ";
    }

    if ($login_fabrica == 146) {
        $column_marca = ",tbl_marca.nome AS marca";
        $join_marca = "inner join tbl_marca on tbl_marca.marca = tbl_pedido.visita_obs::integer";
    }

    if(in_array($login_fabrica, [104,147,153,160]) or $replica_einhell){
        $campo_complemento = " tbl_pedido.atende_pedido_faturado_parcial , ";
    }

    if($login_fabrica == 101){
        $campo_complemento = " TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao, TO_CHAR( tbl_faturamento.saida, 'DD/MM/YYYY') as saida, TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY')as previsao_chegada, tbl_faturamento.conhecimento,  "; 
        $left_join_faturamento = " left join tbl_faturamento_item on tbl_faturamento_item.pedido = tbl_pedido.pedido
            left join tbl_faturamento on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento  ";
    }

    if ($login_fabrica == 175){
        $valoresAdicionais = "
            JSON_FIELD('valor_frete',tbl_pedido.valores_adicionais)    AS pedidoValorFrete,
            JSON_FIELD('valor_despesa',tbl_pedido.valores_adicionais)  AS pedidoValorDespesa,
            JSON_FIELD('valor_seguro',tbl_pedido.valores_adicionais)   AS pedidoValorSeguro,
            JSON_FIELD('valor_desconto',tbl_pedido.valores_adicionais) AS pedidoValorDesconto,
        ";
    }

    $cond_posto = " AND tbl_pedido.posto = $login_posto ";
    if ($login_fabrica == 87) {
        $cond_posto = " AND (tbl_pedido.posto = $login_posto OR tbl_pedido.posto IN (SELECT filial_posto FROM tbl_posto_filial WHERE fabrica = $login_fabrica AND posto = $login_posto)) ";
    }

    $sql = "SELECT  tbl_pedido.pedido                                                       ,
                    tbl_pedido.seu_pedido                                                   ,
                    tbl_pedido.transportadora                                               ,
                    tbl_pedido.transportadora_redespacho                                    ,
                    tbl_pedido.status_pedido                                                ,
                    tbl_pedido.condicao                                                     ,
                    tbl_pedido.valores_adicionais AS valores_adicionais                     ,
                    tbl_pedido.tabela                                                       ,
                    tbl_pedido.distribuidor                                                 ,
                    $campo_complemento
                    $campo_nome_admin
                    tbl_pedido.pedido_cliente                                               ,
                    tbl_pedido.status_pedido_posto                                          ,
                    $valoresAdicionais
                    to_char(tbl_pedido.previsao_entrega,'DD/MM/YYYY') AS data_desejada      ,
                    tbl_pedido.previsao_entrega                                             ,
                    to_char(tbl_pedido.recebido_posto,'DD/MM/YYYY') AS recebido_posto       ,
                    to_char(tbl_pedido.data,'DD/MM/YYYY')           AS pedido_data          ,
                    tbl_pedido.data                                 AS pedido_data2         ,
					to_char(tbl_pedido.data_validade,'DD/MM/YYYY')   AS data_validade,
					tbl_condicao.descricao                          AS condicao_descricao   ,
					tbl_condicao.codigo_condicao,
                    CASE WHEN tbl_pedido.fabrica IN (88,101,120,201,131,156,183)
                         THEN tbl_pedido.tipo_frete
                         ELSE tbl_condicao.frete
                    END                                             AS frete                ,
                    CASE WHEN tbl_pedido.fabrica IN (42, 87, 101, 157,175)
                         THEN tbl_pedido.obs
                         ELSE 'null'
                    END                                             AS obs                  ,
                    $nome_transportadora
                    tbl_pedido.valor_frete                                                  ,
                    tbl_tipo_pedido.descricao                       AS tipo_descricao       ,
                    tbl_pedido.tipo_frete                           AS tipo_frete_posto     ,
                    tbl_tabela.tabela                                                       ,
                    tbl_tabela.descricao                            AS tabela_descricao     ,
                    tbl_posto_fabrica.codigo_posto                                          ,
                    tbl_posto.nome                                  AS posto_nome           ,
                    tbl_posto.cnpj, 
                    tbl_posto.cidade,
                    tbl_posto.estado,
                    distrib.nome_fantasia                           AS distrib_fantasia     ,
                    distrib.nome                                    AS distrib_nome         ,
                    distrib_filial.nome_fantasia                    AS filial_fantasia      ,
                    tbl_classe_pedido.classe                        AS classe_pedido        ,
                    /* COALESCE(tbl_pedido.desconto, 0)                AS pedido_desconto   , */
                    CASE
                        WHEN $login_fabrica = 88 OR $login_fabrica = 157 THEN
                            COALESCE(tbl_posto_fabrica.desconto, 0)
                        ELSE
                            COALESCE(tbl_pedido.desconto, 0)
                    END AS pedido_desconto,
                    TO_CHAR(tbl_pedido.controle_exportacao,'DD/MM/YYYY') AS controle_exportacao,
                    tbl_pedido.total,
                    tbl_pedido.total_original
                    {$column_marca}
            FROM    tbl_pedido
            JOIN    tbl_posto                           ON  tbl_pedido.posto            = tbl_posto.posto
            JOIN    tbl_posto_fabrica                   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
       LEFT JOIN    tbl_condicao                        ON  tbl_condicao.condicao       = tbl_pedido.condicao
       LEFT JOIN    tbl_tipo_pedido                     ON  tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                                                        AND tbl_tipo_pedido.fabrica     = $login_fabrica
       LEFT JOIN    tbl_tabela                          ON  tbl_tabela.tabela           = tbl_pedido.tabela
       LEFT JOIN    tbl_posto         distrib           ON  tbl_pedido.distribuidor     = distrib.posto
       LEFT JOIN    tbl_posto_fabrica distrib_filial    ON  distrib_filial.posto        = tbl_pedido.filial_posto
                                                       AND distrib_filial.fabrica       = $login_fabrica
       LEFT JOIN    tbl_classe_pedido                   ON tbl_classe_pedido.classe_pedido = tbl_pedido.classe_pedido
                    $join_transportadora
                    {$join_marca}
                    {$join_admin}
                    $left_join_faturamento
            WHERE   tbl_pedido.pedido  = $pedido
			$cond_posto
            AND     tbl_pedido.fabrica = $login_fabrica;";
    $res = @pg_query ($con,$sql);
    // pre_echo(pg_fetch_all($res), "PEDIDO", true);
    if (@pg_num_rows($res) > 0) {
        $pedido                     = trim(pg_fetch_result($res, 0, 'pedido'));
        $seu_pedido                 = trim(pg_fetch_result($res, 0, 'seu_pedido'));
        $status_pedido              = trim(pg_fetch_result($res, 0, 'status_pedido'));
        $condicao                   = trim(pg_fetch_result($res, 0, 'condicao_descricao'));
        $codigo_condicao            = trim(pg_fetch_result($res, 0, 'codigo_condicao'));
        $frete                      = trim(pg_fetch_result($res, 0, 'frete'));
        $obs                        = trim(pg_fetch_result($res, 0, 'obs'));
        $distribuidor               = trim(pg_fetch_result($res, 0, 'distribuidor'));
        $tipo_pedido                = trim(pg_fetch_result($res, 0, 'tipo_descricao'));
        $tabela                     = trim(pg_fetch_result($res, 0, 'tabela'));
        $tabela_descricao           = trim(pg_fetch_result($res, 0, 'tabela_descricao'));
        $pedido_cliente             = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
        $pedido_data                = trim(pg_fetch_result($res, 0, 'pedido_data'));
        $pedido_data2               = trim(pg_fetch_result($res, 0, 'pedido_data2'));
        $cnpj                       = trim(pg_fetch_result($res, 0, 'cnpj'));
        $codigo_posto               = trim(pg_fetch_result($res, 0, 'codigo_posto'));
        $posto_nome                 = trim(pg_fetch_result($res, 0, 'posto_nome'));
        $posto_cidade               = pg_fetch_result($res, 0, 'cidade') . ', ' . pg_fetch_result($res, 0, 'estado');
        $data_recebido              = trim(pg_fetch_result($res, 0, 'recebido_posto'));
        $transportadora_redespacho  = trim(pg_fetch_result($res, 0, 'transportadora_redespacho'));
        $transportadora             = trim(pg_fetch_result($res, 0, 'transportadora'));
        $transportadora_nome        = trim(pg_fetch_result($res, 0, 'transportadora_nome'));
        $valor_frete                = trim(pg_fetch_result($res, 0, 'valor_frete'));
        $valores_adicionais         = trim(pg_fetch_result($res, 0, 'valores_adicionais'));
        $tipo_frete_posto           = trim(pg_fetch_result($res, 0, 'tipo_frete_posto'));

        if ($login_fabrica == 183){
            if (!empty($valores_adicionais)){
                $valores_adicionais = json_decode($valores_adicionais, true);
                extract($valores_adicionais);

                if (strlen(trim($id_posto_pedido)) > 0){
                    $sql_posto_info = "
                        SELECT 
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_posto.cnpj
                        FROM tbl_posto 
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        WHERE tbl_posto.posto = {$id_posto_pedido} ";
                    $res_posto_info = pg_query($con, $sql_posto_info);

                    if (pg_num_rows($res_posto_info) > 0){
                        $codigo_posto_info = pg_fetch_result($res_posto_info, 0, "codigo_posto");
                        $nome_posto_info = pg_fetch_result($res_posto_info, 0, "nome");
                        $cnpj_posto_info = pg_fetch_result($res_posto_info, 0, "cnpj");
                    }
                }
            }
        }
        if($login_fabrica == 101){
            $faturamento_conhecimento       = pg_fetch_result($res, 0, conhecimento);
            $faturamento_emissao            = pg_fetch_result($res, 0, emissao);
            $faturamento_saida              = pg_fetch_result($res, 0, saida);
            $faturamento_previsao_chegada   = pg_fetch_result($res, 0, previsao_chegada);
        }

        if ($login_fabrica == 175){
            $pedidoValorFrete     = pg_fetch_result($res, 0, 'pedidoValorFrete');
            $pedidoValorDespesa   = pg_fetch_result($res, 0, 'pedidoValorDespesa');
            $pedidoValorSeguro    = pg_fetch_result($res, 0, 'pedidoValorSeguro');
            $pedidoValorDesconto  = pg_fetch_result($res, 0, 'pedidoValorDesconto');
        }

        if(in_array($login_fabrica, [104,147,153,160]) or $replica_einhell) {
            $atende_parcial = pg_fetch_result($res, 0, 'atende_pedido_faturado_parcial');
            $atende_parcial = ($atende_parcial == 't')? "Sim" : "Não";
        }

        if ($login_fabrica == 146) {
            $marca = pg_fetch_result($res, 0, "marca");
        }


        if($login_fabrica == 87){
            $data_desejada          = trim(pg_fetch_result($res, 0, 'data_desejada'));
            $data_desejada_original = trim(pg_fetch_result($res, 0, 'previsao_entrega'));
            $status_pedido_posto    = trim(pg_fetch_result($res, 0, 'status_pedido_posto'));
            $classe_pedido          = trim(pg_fetch_result($res, 0, 'classe_pedido'));
            $admin_nome_completo    = trim(pg_fetch_result($res, 0, 'nome_completo'));
        }

        $controle_exportacao       = trim(pg_fetch_result($res, 0, 'controle_exportacao'));
		$data_validade = pg_fetch_result($res, 0, 'data_validade');
#echo utf8_decode($obs);
        if ($login_fabrica == 42) {//HD 825655
            $distrib_fantasia = trim(pg_fetch_result($res, 0, 'filial_fantasia'));
            if (strlen($distrib_fantasia) == 0) $distrib_fantasia = '<b>' . traduz('fabrica', $con) . '</b>';

            $obs = json_decode($obs, true);

            $tipo_entrega = "";
            switch ($obs['transporte']) {
                case 'SEDEX':
                    $tipo_entrega = "Sedex A Cobrar";
                    break;
                case 'RETIRA':
                    $tipo_entrega = "Retirada";
                    break;
                default:
                    $tipo_entrega = "Padrão";
            }
        } else {

            $distrib_fantasia = trim(pg_fetch_result($res, 0, 'distrib_fantasia'));
            if (strlen($distrib_fantasia) == 0) $distrib_fantasia = trim(pg_fetch_result($res, 0, 'distrib_nome'));
            if (strlen($distrib_fantasia) == 0) $distrib_fantasia = '<b>' . traduz('fabrica', $con) . '</b>';

        }

        $detalhar = ($login_fabrica <> 15 ) ? "ok" : "";
        $pedido_desconto  = trim(pg_fetch_result ($res,0,pedido_desconto));

        $pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

        $tbl_pedido_total = pg_fetch_result($res, 0, "total");
        $tbl_pedido_total_original = pg_fetch_result($res, 0, 'total_original');

    }
}

if(strlen($pedido) > 0 AND $login_fabrica == 24){ // HD 18327
    $sql="SELECT  sum(qtde) AS qtde,
                  sum(qtde_cancelada) AS qtde_cancelada
            FROM  tbl_pedido
            JOIN  tbl_pedido_item USING(pedido)
            WHERE tbl_pedido.pedido=$pedido
            AND   tbl_pedido.status_pedido <> 14";
    $res=@pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        $qtde           = pg_fetch_result($res,0,qtde);
        $qtde_cancelada = pg_fetch_result($res,0,qtde_cancelada);
        if($qtde == $qtde_cancelada){
            $sql2="UPDATE tbl_pedido SET status_pedido=14
                    WHERE pedido = $pedido";
            $res2=@pg_query($con,$sql2);
        }
    }
}
$msg = $_GET['msg'];
$title = traduz('confirmacao.de.pedido.de.pecas', $con);
$layout_menu = 'pedido';

$jQueryVersion = '1.8.3.min';
$plugins[]     = 'shadowbox';

include "cabecalho.php";
?>
<script type="text/javascript">

var traducao = {

    pedido_confirmado:             '<?=traduz('pedido.confirmado.com.sucesso', $con)?>',
    pedido_aprovado_cliente:       '<?=traduz('pedido.aprovado.pelo.cliente', $con)?>',
    confirma_rejeita_pedido_tela:  '<?=traduz('rejeita.o.pedido.%.como.apresentado.na.tela', $con)?>',
    confirma_confirma_pedido_tela: '<?=traduz('confirma.o.pedido.%.como.apresentado.na.tela', $con)?>',
    pedido_reprovado_cliente:      '<?=traduz('pedido.reprovado.pelo.cliente', $con)?>',
    data_atualizada_ok:            '<?=traduz('data.atualizada.com.sucesso', $con)?>',
    pedido:                        '<?=ucfirst(traduz('pedido', $con))?>',
    aguarde_submissao:             '<?=traduz('aguarde.submissao', $con)?>',
    ocorreu_un_erro:               '<?=traduz('ocorreu.um.erro', $con)?>'

}

<?php if (isFabrica(42)): ?>
function mostraPrevPeca(self) {
    var data = $(self).data();
    var qArr = [];
    var qObj = {
        categoria: 'pendencias_de_pecas',
        'peca_faltante[]': data.ref,
        pedido: '<?=$seu_pedido ? : $pedido?>',
        garantia: '<?=strpos(strtolower($tipo_pedido), 'gar') !== false ? 't':'f'?>',
        data_pedido: data.exportado,
        btnEnviar: 'Enviar'
    };

    for (i in qObj)
        qArr.push(i+"="+qObj[i]);

    data.href = 'helpdesk_cadastrar.php?' + encodeURI(qArr.join('&'));

    var pedidoPecaMsg = "<div class='message'><h2 class='titulo'>Disponibilidade da peça</h2>" +
        "<table class='table table-compact table-striped table-bordered' style='height:auto'>" +
        "<thead><tr><th><?=traduz('referencia')?></th><th><?=traduz('descricao')?></th><th><?=traduz('previsao')?></th></tr></thead>" +
            "<tbody><tr><td>"+data.ref+"</td><td>"+data.desc+"</td><td>"+data.prevista+"</td></tr></tbody>" +
            "<caption style='caption-side:bottom'><?=traduz('solicite.urgencia.de.entrega.atraves.de.chamado')?></caption></table></div>";
    pedidoPecaMsg += "\n<p>&nbsp;</p>\n<div align='center'>" +
        "<a href='"+data.href+"' target='_blank' class='btn btn-default' id='orderPart'><?=traduz('abrir.chamado')?></a></div>";
    // return pedidoPecaMsg;
    Shadowbox.open({
        player: 'html',
        content: pedidoPecaMsg,
        width: 550,
        height: 250
    });
}
<?php endif; ?>

$(function(){
    Shadowbox.init();
    $("#correios").click(function(){

    var obj = $(this).attr("rel");
    $("#historicoCorreios").load("os_press.php .listEvent",{"buscaCorreios":true,"objeto":obj}, function(){
        if($(".listEvent").length){
        Shadowbox.open({
            content: "<div style='background-color:#FFF'>"+$("#historicoCorreios").html()+"</div>",
            player: "html",
            title:  "Histórico Correios",
            width:  800,
            height: 500
        });
        }else{
            alert("Não foram encontradas informações sobre esse código.");
        }
    });
});

<?php if (isFabrica(42)): ?>
    $(".abre_hd_pedido_peca").click(function() {mostraPrevPeca(this);});
<?php endif; ?>

<?php if($login_fabrica == 87) { ?>
    $("#aceitar").click(function(){
        url = 'pedido_finalizado_ajax.php?ajax=s&pedido=<?php echo $pedido ?>&aceitar=s';
        if(confirm(traducao.confirma_confirma_pedido_tela.replace('%', '<?=$pedido?>'))) {
            $("div#status").load(url,function(e){

                if( e == 'Confirmado' ) {
                    /*$("#tipo_pedido").html(traducao.pedido);
                    $("div#status").html(traducao.pedido_confirmado);
                    $("#status_pedido").html(traducao.pedido_aprovado_cliente);*/
                }
                $("div#status").fadeIn("slow");
                location.reload();
            })
            $("#aceite").fadeOut("slow");
        }
    });
    $("#rejeitar").click(function(){
        url = 'pedido_finalizado_ajax.php?ajax=s&pedido=<?php echo $pedido ?>&rejeitar=s';
        if(confirm(traducao.pedido_reprovado_cliente.replace('%', '<?=$pedido?>'))) {
            $("div#status").load(url).fadeIn("slow");
            $("#status_pedido").html(traducao.pedido_reprovado_cliente);
            $("#aceite").fadeOut("slow");
        }
    });

<?php
} else if ($login_fabrica == 168) {
?>
    $("#aprovar_frete").click(function(e){
        e.preventDefault();
        var valor_frete = $("#valor_frete:checked").val();

        if (valor_frete == "" || typeof(valor_frete) == "undefined") {
            alert("Favor, informar o valor do frete");
            return;
        }

        var valores_frete_transportadora = valor_frete.split("|");

        valor_frete = valores_frete_transportadora[0];
        var transportadora_frete = valores_frete_transportadora[1];

        $.ajax({
            url:"pedido_finalizado.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"aprova_frete",
                pedido:<?=$pedido?>,
                valor_frete:valor_frete,
                transportadora_frete: transportadora_frete
            }
        })
        .done(function(data){
            if (data.ok) {
                alert("Frete cadastrado.");
                $(".aprova_frete").hide();
            }
        })
        .fail(function(){
            alert("Erro ao cadastrar frete.");
        });
    })
<?php } ?>
});


function linkLupeon(nf, cnpj){
    $.ajax({
        url:"pedido_finalizado.php",
        type:"POST",
        dataType:"JSON",
        data:{
            getLinkNF:true,
            nf:nf,
            cnpj:cnpj                
        },
        complete: function(data) {
            var data = $.parseJSON(data.responseText);                
            window.open('http://makita.lupeon.com.br/#/detalhes/'+data.NFeID, '_blank');
        }
    })
}


function atualizaConferencia(faturamento, conferencia) {
    $.ajax({
        url: document.location.pathname,
        method: 'GET',
        data: {atualiza_conferencia:'true', 'faturamento': faturamento, conferencia: conferencia.value}
    }).done(function(results) {
        var text = (results.responseText.length > 0) ? results.responseText : traducao.data_atualizada_ok;
        alert(text);
    }).fail(function() {
        alert("Ocorreu um erro");
    });
}
</script>

<style type="text/css">

caption,.menu_top {
    background-color: #596D9B;
    border: 0px solid;
    color:#ffffff;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    text-align: center;
    width: 100%;
}

table.tabela tr.titulo_coluna td {
    font-family: arial, verdana;
    font-size: 10px;
    border-collapse: collapse;
    border: 1px solid #596d9b;
}

.table_line1 {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    text-align: right;
}

.table_line1 .esq {text-align:left}
.table_line1 .center {text-align:center}

.table_line1 .pendencia {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    color: #FF0000;
}

.error {
    background:#ED1B1B;
    width: 600px;
    text-align: center;
    padding: 2px 2px;
    margin: 1em 0.25em;
    color:#FFFFFF;
    font-size:12px;
}

.error h1 {
    color:#FFFFFF;
    font-size:14px;
    font-size:normal;
    text-transform: capitalize;
}

.sucesso{
    background-color:green;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
    display:none;
}

#status { margin-bottom:8px; }

    .condicao_venda p{
        color: #000;
        font-size: 12px;
        margin: 0;
        padding: 0;
    }

    .condicao_venda li{
        margin: 0;
        padding: 2px 12px;
        text-align: left;
        font-size: 11px;
        color: #000;
    }

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    text-transform: uppercase;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
    /* text-transform: capitalize; */
}
</style>

<?php

if ($login_fabrica == 143 && $status_pedido == 19) {
?>
    <br />
    <div style="width: 700px; background-color: #F9A227; color: #FFFFFF; font-weight: bold; text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 14px;" >
        Seu pedido passará por atualização de valores referente a impostos e taxas, assim que atualizado enviaremos um comunicado para você verificar e aprovar o pedido !
    </div>
    <br />
<?php
} else if ($login_fabrica == 143 && $status_pedido == 24) {
?>
    <br />
    <div style="width: 700px; background-color: #F9A227; color: #FFFFFF; font-weight: bold; text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 14px;" >
        Pedido sob análise da fábrica
    </div>
    <br />
<?php
}
if ($login_fabrica == 143) {
	require_once "classes/Posvenda/Fabricas/_143/PedidoWackerNeuson.php";
}
 $tamanho = ($login_fabrica == 50) ?'690':'100%';
    if ($login_fabrica == 101) $tamanho = '110%'; ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<? if(strlen($msg)>0){ ?>
        <tr class="sucesso"><td><? echo $msg; ?> </td></tr>
<? } ?>
<tr>
    <td valign="top" align="center">
        <table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
        <tr>
            <td nowrap align='center' style='font:normal normal 10px Geneva, Arial, Helvetica, san-serif;'>
                <b><?=traduz('atencao', $con)?>:</b>&nbsp;<?=traduz('pedidos.a.prazo.dependerao.de.analise.do.departamento.de.credito.', $con)?>
            </td>
        </tr>
        </table>
        <? if(strlen($msg_erro)>0){?>
            <div class="error">
                <? echo $msg_erro; ?>
            </div>
        <?}?>

         <? if(strlen($msg_sucesso)>0){?>
            <div class="sucesso">
                <? echo $msg_sucesso; ?>
            </div>
        <?}?>
        <table width="<? echo $tamanho;?>" border="0" cellspacing="1" cellpadding="3" align='center'>
        <tr>
            <td class='titulo_tabela'><?=traduz('emitente.do.pedido', $con)?></td>
        </tr>
        <tr class='table_line1'>
            <td class='center'>
                <?php
                echo $codigo_posto . " - " . $posto_nome;

                if ($login_fabrica == 87) {
                    echo ' - ' , $posto_cidade;
                }
                ?>
           </td>
        </tr>

        <?php if($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep")) AND strlen(trim($nome_posto_info)) > 0){ ?>
                <tr>
                    <td class='titulo_tabela'><?=traduz('cliente.do.pedido', $con)?></td>
                </tr>
                <tr class='table_line1'>
		<td class='center'><b>CNPJ:</b> <?=$cnpj_posto_info?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Código:</b><?=$codigo_posto_info?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Descrição:</b> <?=$nome_posto_info?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Nota Fiscal do Cliente:</b><?=$nota_fiscal_posto_pedido?></td>
                </tr>
        <?php } ?>
        <?php
            if($login_fabrica == 87){
                $sqlFilial = "SELECT posto FROM tbl_posto_fabrica
                        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                            AND tbl_tipo_posto.fabrica = $login_fabrica
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                        AND tbl_posto_fabrica.posto = $login_posto
                        AND tbl_tipo_posto.codigo = 'REVENDA'";
                $resFilial = pg_query($con,$sqlFilial);

                if(pg_num_rows($resFilial) > 0){
                    $sqlFilial = "SELECT tbl_posto.nome, tbl_posto.cidade, tbl_posto.estado,
                            tbl_posto_fabrica.codigo_posto
                        FROM tbl_pedido
                            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.filial_posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica
                            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                        WHERE tbl_pedido.pedido = $pedido
                            AND tbl_pedido.fabrica = $login_fabrica
                            AND tbl_pedido.posto = $login_posto";
                    $resFilial = pg_query($con,$sqlFilial);

                    if(pg_num_rows($resFilial) > 0){
                        $filial_nome  = pg_fetch_result($resFilial, 0, "nome");
                        $filial_codigo = pg_fetch_result($resFilial, 0, "codigo_posto");
                        $filial_cidade = pg_fetch_result($resFilial, 0, "cidade");
                        $filial_cidade .= ', ' . pg_fetch_result($resFilial, 0, 'estado');

                        ?>
                        <tr>
                            <td class='titulo_tabela'><?=traduz('revenda.filial', $con)?></td>
                        </tr>
                        <tr class='table_line1'>
                            <td class='center'>
                                <?php echo $filial_codigo . " - " . $filial_nome . ' - ' . $filial_cidade; ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
            }
        ?>
        </table>

        <table width="<? echo $tamanho;?>" border="0" cellspacing="1" cellpadding="3" align='center'>
            <thead>
                <tr class='titulo_coluna'>
                    <td><?=traduz('pedido', $con)?></td>
                    <? if (strlen ($pedido_cliente) > 0) {
                      echo "<td>".traduz('pedido.cliente', $con)."</td>";
                    }
                    ?>
                    <?php if ($login_fabrica == 183 AND strlen(trim($seu_pedido)) > 0){ ?>
                        <td><?=traduz('pedido.representante', $con)?></td>
                    <?php } ?>
		        <?php if ($login_fabrica == 175) { ?>
                        <td><?=traduz('pedido.ibramed', $con)?></td>
                    <?php } ?>

                    <?php if ($login_fabrica == 87) { ?>
                        <td><?=traduz('status', $con)?></td>
                    <?php } ?>
                    <td><?=traduz('data', $con)?></td>
                    <? if (in_array($login_fabrica, array(147, 160)) or $replica_einhell) {
                        $aux_sql = "
                            SELECT
                                TO_CHAR(data, 'DD/MM/YYYY HH24:MI:SS') AS data_aprovacao,
                                status,
                                observacao
                            FROM
                                tbl_pedido_status
                            WHERE
                                pedido = {$pedido}
                            ORDER BY pedido_status DESC
                            LIMIT 1
                        ";
                        $aux_res = pg_query($con, $aux_sql);
                        $aux_dat = pg_fetch_result($aux_res, 0, 'data_aprovacao');
                        $aux_sta = pg_fetch_result($aux_res, 0, 'status');
                        $aux_obs = pg_fetch_result($aux_res, 0, 'observacao');

                        if (($aux_sta == "1" || $aux_sta == "14")  && ($aux_obs == "Pedido Aprovado" || $aux_obs == "Pedido Recusado")) {
                            switch ($aux_obs) {
                                case 'Pedido Aprovado':
                                    $aux_lbl = "Liberado da Intervenção";
                                    break;
                                default:
                                    $aux_lbl = "Recusado da Intervenção";
                                    break;
                            }
                            ?> <td><?echo $aux_lbl?></td> <?
                        }
                    }
                    if ($login_fabrica==24) { ?>
                    <td><?=traduz(array('recebido','posto'), $con)?></td>
                    <? }
                    if ($tipo_pedido <>'Garantia') { 
                        if ($login_fabrica == 87 && $tipo_pedido == 'Orçamento' && !empty($obs)) {
                            $xcols = "colspan='2'";
                        }
                    ?>
                        <td <?=$xcols?>><?=traduz('condicao.de.pagamento', $con)?></td>
                    <? } ?>
                    <?php if ($login_fabrica == 87 and $tipo_pedido == "Orçamento") : ?>
                        <td <?=$xcols?> ><?=traduz('data.de.validade', $con)?></td>
                    <?php endif; ?>
                    <td><?=traduz('tipo.pedido', $con)?></td>
                    <?php
                    if ($login_fabrica == 146) {
                        echo "<td>Marca</td>";
                    }
                    ?>
                    <td><?=traduz('tabela.de.precos', $con)?></td>
                    <?php if(in_array($login_fabrica,array(88,104,105,120,201,131,156,183))){ ?>
                    <td><?=traduz('frete', $con)?></td>
                    <? if(in_array($login_fabrica,array(88,120,201,156))){

                    ?>
                    <td><?=ucfirst(traduz('transportadora', $con))?></td>
                    <td><?=traduz('valor.frete', $con)?></td>
                    <?php } ?>
                    <?php } ?>
  
                    <?if (in_array($login_fabrica, array(101,151))) {?>
                    <td><?=traduz(array('tipo','frete'), $con)?></td>
                    <?}?>

                    <td><?=traduz(array('atendido','por'), $con)?></td>

                    <?php if($login_fabrica == 87 AND strlen($admin_nome_completo)>0){ ?>
                        <td><?=traduz(array('admin'), $con) ?></td>
                    <?php }?>

                    <?php if(in_array($login_fabrica, [104,147, 153,160]) or $replica_einhell) { ?>
                        <td><?=traduz(array('atende','parcial'), $con)?></td>
                    <?php } ?>
                    <? if ($login_fabrica==42) { ?>
                    <td><?=traduz(array('data','exportacao'), $con)?></td>
                    <td><?= traduz(array('tipo', 'de', 'entrega'), $con) ?></td>
                    <? } ?>
                </tr>            

            </thead>
        <tbody>
        <tr class='table_line1'>
<?php      if($login_fabrica == 30){
                if(strlen($seu_pedido) > 0){
                    echo '<td>'.$seu_pedido.'</td>';
                }else{
                    echo '<td>'.$pedido_aux.'</td>';

                }
            }else{

                echo '<td>'.$pedido_aux.'</td>';
            }
?>
	    <?php if($login_fabrica == 175){
                 if (strlen ($pedido_cliente) > 0) {
					echo "<td class='center'>".$pedido_cliente."</td>";
				 }
			echo "<td class='center'>".$seu_pedido."</td>";
	     } ?>

            <? if (strlen($pedido_cliente) > 0 AND $login_fabrica <> 175) {
            echo "<td class='center'>".$pedido_cliente."</td>";
            } ?>
         
            <?php if ($login_fabrica == 183 AND strlen(trim($seu_pedido)) > 0){ ?>
                <td class='center'><?=$seu_pedido?></td>                
            <?php } ?>

            <?php if ($login_fabrica == 87) { ?>
                <td align="center" id="status_pedido">
                    <?php
                        if(!empty($status_pedido)) {

                            $sql_stat = "SELECT descricao FROM tbl_status_pedido WHERE status_pedido = " . $status_pedido;
                            $res_stat = pg_query($con,$sql_stat);
                            echo pg_result($res_stat,0,0);

                        }
                    ?>
                </td>
            <?php } ?>
            <td class='center'><?echo $pedido_data?></td>
            <? if (in_array($login_fabrica, array(147, 160)) or $replica_einhell) { 
                    if (($aux_sta == "1" || $aux_sta == "14")  && ($aux_obs == "Pedido Aprovado" || $aux_obs == "Pedido Recusado")) {
                        ?> <td class='center'><?echo $aux_dat?></td>  <?
                    }
                }
            if ($login_fabrica==24) { ?>
            <td class='center'><?echo $data_recebido?></td>
            <? }
            if ($tipo_pedido <>'Garantia') { ?>
            <td <?=$xcols?> class='center'><?echo $condicao?></td>
            <? } ?>
            <?php if ($login_fabrica == 87 and $tipo_pedido == "Orçamento") : ?>
                <td <?=$xcols?> class="center"><?php echo $data_validade ?></td>
            <?php endif; ?>
            <td class='center' id="tipo_pedido"><?echo $tipo_pedido?></td>
            <?php
            if ($login_fabrica == 146) {
                echo "<td class='center'>$marca</td>";
            }
            ?>
            <td class='center'><?echo $tabela_descricao?></td>

            <? if($login_fabrica == 151) {
                ?><td class='center'><?=$tipo_frete_posto?></td><?
            }?>

            <?php if(in_array($login_fabrica,array(88,101,104,105,120,201,131,156,183))){
                    if($login_fabrica == 88){
                        $frete = ($frete == "NOR") ? "NORMAL" : "URGENTE";
                    }
            ?>
                    <td class='center'><?echo $frete?></td>
            <?
                    if(in_array($login_fabrica,array(88,94,120,201,156))){

           ?>
                    <td class='center'><?echo $transportadora_nome?></td>
                    <td class='center'><?echo number_format($valor_frete,2,',','');?></td>
            <?php } ?>
            <?php } ?>
            <td class='center'><?echo $distrib_fantasia?></td>
            <?php if($login_fabrica == 87 AND strlen($admin_nome_completo)>0){ ?>
                <td class='center'><?echo $admin_nome_completo ?></td>
            <?php } ?>

            <?php if(in_array($login_fabrica, [104,147, 153,160]) or $replica_einhell){ ?>
                <td class='center'><?echo $atende_parcial?></td>
            <?php }?>

            <? if ($login_fabrica==42) { ?>
            <td class='center'><?echo $controle_exportacao?></td>
            <td class='center'><?= $tipo_entrega ?></td>
            <? } ?>
        </tr>
       
        <?php if($login_fabrica == 87 or $login_fabrica == 94) {
                    $colspan = ($login_fabrica == 87) ? '5' : '6';
        ?>
                <tr class='menu_top'>
                    <td colspan="<?php echo $colspan; ?>"><?=ucfirst(traduz('transportadora', $con))?></td>
                    <?php 
                    if($login_fabrica == 87) { 
                        $xcolspan = (empty($obs)) ? '4' : '6';
                    ?>
                    <td colspan="<?=$xcolspan?>"><?=traduz('transportadora.redespacho', $con)?></td>
                    <?php }else{ ?>

                    <?php
                    ?>
                        <td colspan="<?php echo $colspan; ?>"><?=ucfirst(traduz('frete', $con))?></td>
                    <?php
                         }
                    ?>
                </tr>
                <?php
                    if (!empty ($transportadora) || !empty($transportadora_redespacho)) {
                        if(!empty ($transportadora)) {
                            $sql = "SELECT nome FROM tbl_transportadora WHERE transportadora = $transportadora";
                            $res = pg_query($con,$sql);
                            $transportadora = pg_result($res,0,0);
                        }
                        if(!empty ($transportadora_redespacho) and $login_fabrica == 87) {
                            $sql = "SELECT nome FROM tbl_transportadora WHERE transportadora = $transportadora_redespacho";
                            $res = pg_query($con,$sql);
                            $transportadora_redespacho = pg_result($res,0,0);
                        }

                ?>
                        <tr class="table_line1">
                            <td colspan="<?php echo $colspan; ?>" align="center"><?php echo $transportadora; ?></td>
                        <?php if($login_fabrica == 94) { ?>
                            <td colspan="<?php echo $colspan; ?>" align="center"><?php echo number_format($valor_frete,2,",","."); ?></td>
                        <?php }else{ ?>
                            <td colspan="3" align="center"><?php echo $transportadora_redespacho; ?></td>
                        <?php } ?>
                        </tr>

        <?php
                    }
            }

            if($data_desejada != ""){

                if($status_pedido_posto == 23){
                    $sqlAux = "SELECT data_exportacao FROM tbl_pedido_log_exportacao where pedido = $pedido";
                    $resAux = pg_query($con,$sqlAux);
                    if(pg_num_rows($res)>0){
                        $data_desejada_alterada = pg_result($resAux,0,data_exportacao);

                        if(strtotime($data_desejada_alterada) > strtotime($data_desejada_original)){
                            $sqlAux =   "SELECT date '$data_desejada_alterada' - date '$data_desejada_original' as dias";
                            $resAux = pg_query($con,$sqlAux);
                            $dias_data_desejada = pg_result($resAux,0,dias);


                            $aviso = "Após o faturamento sua mercadoria será entregue em $dias_data_desejada dias úteis";
                            $colspanDt = '3';
                        }else{
                            $colspanDt = '4';
                            $aviso = "";
                        }
                    }else{
                        $colspanDt = '4';
                        $aviso = "";
                    }


                }elseif($login_fabrica == 87){
                    $colspanDt = '5';
                    $aviso = "";
                    
                    if (!empty($obs)) {
                        $aviso = $obs;
                    }
                }else{
                    $colspanDt = '4';
                    $aviso = "";
                }

                ?>
                <tr class='menu_top'>
                    <td colspan="<?php echo $colspanDt; ?>"> Classe do Pedido </td>
                    <td colspan="<?php echo $colspanDt; ?>"> Data Desejada </td>
                    <?php if($aviso != ""){ ?>
                    <td colspan="<?php echo $colspanDt; ?>"> Aviso </td>
                    <?php } ?>
                </tr>
                <tr class="table_line1">
                    <td colspan="<?php echo $colspanDt; ?>" align="center"><?php echo $classe_pedido; ?></td>
                    <td colspan="<?php echo $colspanDt; ?>" align="center"><?php echo $data_desejada; ?></td>
                    <?php if($aviso != ""){ ?>
                    <td style="background-color:#ffd6d6" colspan="<?php echo $colspanDt; ?>" align="center"><?php echo $aviso; ?></td>
                    <?php } ?>
                </tr>
                <?php
            }

        ?>
        </tbody>
        </table>
        <br>
        <table width="<? echo $tamanho;?>" border="0" cellspacing="1" cellpadding="3" align='center' class='tabela'>
            <thead>
            <tr height="20" class='titulo_coluna'>
<?php
                $colunas = 6;
                if($login_fabrica == 15) {
                    if ($tipo_pedido=='Garantia') {
                        $colunas++;
                    }
                   ?><td><?=traduz('os', $con)?></td><?
                }

                if ($login_fabrica == 147) {
?>
                <td><?=traduz('peca.referencia', $con)?></td>
                <td><?=traduz('peca.descricao', $con)?></td>
<?php
                } else {
?>
                <td><?=traduz('componente', $con)?></td>
<?php
                }
?>

                <td><?=traduz('qtde.pedida', $con)?></td>
                <td><?=traduz('qtde.cancelada', $con)?></td>
                <td><?=traduz('qtde.faturada', $con)?></td>
            <?php if( !in_array($login_fabrica, array(87))) { ?>
                <td><?=traduz('pendencia.do.pedido', $con)?></td>

                <?php
		if ($login_fabrica == 183){
		?>
                	<td><?=traduz('nota.fiscal', $con)?></td>
			<td><?=traduz('emissao', $con)?></td>
		<?php
		}

                if(in_array($login_fabrica, array(152,180,181,182))){
                ?>
                <td><?=traduz('previsao.faturamento', $con)?></td>
                <?php
                }

                if(in_array($login_fabrica, array(143)) and $status_pedido <> 19){
                ?>
                <td><?=traduz('disponibilidade', $con)?></td>
                <?php
                }

                if( !in_array($login_fabrica, array(2,3,6,11,14,15,24,30,35,45,40,42,46,50,51,52,72,74,80,81,85,88,90,91,94,172)) and $login_fabrica < 95){
                ?>
                <td><?=traduz('pendencia.total', $con)?></td>
                <?php
                }
                ?>
            <?php
            } else {
            ?>
                <td><?=traduz('pendencia', $con)?></td>
            <?php
            }
            
            if (in_array($login_fabrica, array(175))) {
            ?>
                <td><?=traduz('Preço').' (R$) '.traduz('anterior')?></td>
            <?php
            }

            if ( $liberar_preco && ($login_fabrica != 143 || ($login_fabrica == 143 && $status_pedido != 19) ) ) {
            ?>
                <td>
                    <?php
                    if ($login_fabrica == 35) { ?>
                        Preço Unitário
                    <?php
                    }elseif($login_fabrica == 160 or $replica_einhell){
                        echo "Preço Tabela" ;
                    } else {
                        echo ($login_fabrica != 87) ? traduz('preco', $con) . ' (R$)' :traduz('preco.inicial', $con);
                    }
                    ?>
                </td>
                    <?
                    if ($login_fabrica == 140) { 
?>
                        <td><?=traduz('Preço Total', $con)?></td>
<?php               }
                $colunas++;
                if (!in_array($login_fabrica,array(15))) {
                    if (!isset($telaPedido0315) && !in_array($login_fabrica, array(35,120,201))) {
                    ?>
                        <td>IPI (%)</td>
                    <?php
                    }

                    if(in_array($login_fabrica, array(147,149,153,156,157,165,168)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                    ?>
                        <td>IPI (%)</td>
                    <?php
                    }
                    ?>
                    
                    <?php
                    if($login_fabrica == 160 or $replica_einhell){
                        echo "<td>Desconto</td>"; 
                        echo "<td nowrap>Preço UNIT <br> com desconto </td>"; 
                    }
                    
                    if (in_array($login_fabrica, array(175))) {
                    ?>
                        <td><?=traduz('Aliq. IPI')?></td>
                        <td><?=traduz('Base. IPI')?></td>
                        <td><?=traduz('IPI')?></td>
                        <td><?=traduz('Aliq. ICMS')?></td>
                        <td><?=traduz('Base ICMS')?></td>
                        <td><?=traduz('ICMS')?></td>
                        <td><?=traduz('Total impostos')?></td>
                        <td><?=traduz('Total anterior')?></td>

                    <?php
                    }
                    ?>
                    <td>
                    <?php
                    if (!isset($telaPedido0315)) {
                            if ($login_fabrica == 35) { ?>
                                Preço
                            <?php
                            } else {
                                echo (!in_array($login_fabrica, array(87,120,201))) ? traduz(array('total','com.ipi'), $con) . ' (R$)' : traduz('preco.final', $con);
                            }
                        ?>
                        </td>
                    <?php
                    } else {
                        if(in_array($login_fabrica, array(147,149,153,156,157,165)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
                            echo "Total c/ IPI";
                        }else{
                            echo "Total";
                        }
                        ?>
                        </td>
                    <?php
                    }
                    $colunas = $colunas + 2;
                }
            }

            // HD 3765012
            if ($login_fabrica == 42) {
                echo "<td nowrap>Disp. Status</td>";
                $colunas--;
                $extra_footer = true;
            }

            ?>
            <?php if($login_fabrica == 87){ ?>
                <?php
                echo '<td>Preço Final Total s/ S.T e IPI</td>';
                $colunas++;
                ?>
                <td ><?=traduz('preco.final.total', $con)?></td>
                <td width="150px"><?=traduz('condicao', $con)?></td>
                <?php if ($tipo_pedido == "Orçamento"): ?>
                    <td>Disponibilidade</td>
                <?php endif ?>
<?php
            }
            if ($login_fabrica == 147) {
?>
                <td>Desconto (%)</td>
                <td>Total com Desconto</td>
<?php
            }
?>
            </tr>
            </thead>
        <?
        //sql que calcula o total do pedido
        $sql = "SELECT  case
                            when tbl_pedido.fabrica = 14 then
                                rpad (sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::text,7,'0')::float

                            when tbl_pedido.fabrica = 30 then
                                    sum(tbl_pedido_item.qtde * tbl_pedido_item.preco)
                            when tbl_pedido.fabrica = 24 then
                                    sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1+(tbl_peca.ipi/100)))
                            when tbl_pedido.fabrica = 87 then
                                    tbl_pedido.total
                            when tbl_pedido.fabrica = 88 OR tbl_pedido.fabrica = 126 then
                                sum ((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco)
                            else
                                sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))
                            end as total_pedido
                FROM  tbl_pedido
                JOIN  tbl_pedido_item USING (pedido)
                JOIN  tbl_peca        USING (peca)
                WHERE tbl_pedido_item.pedido = $pedido
                and tbl_pedido.fabrica = $login_fabrica
                GROUP BY tbl_pedido.pedido , tbl_pedido.total";
        $res = pg_query ($con,$sql);
        //echo nl2br($sql);

        $total_pedido = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,total_pedido) : 0;

        if(in_array($login_fabrica,array(43,74,86,99))) { // HD 112647
            $sql = "SELECT  tbl_pedido_item.peca,
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_peca.ipi,
                            ' ' AS pedido_item,
                            SUM(tbl_pedido_item.qtde) AS qtde,
                            SUM(tbl_pedido_item.qtde_faturada) AS qtde_faturada,
                            SUM(tbl_pedido_item.qtde_cancelada) AS qtde_cancelada,
                            SUM(tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_faturada_distribuidor,
                            tbl_pedido_item.preco,
                            tbl_pedido.desconto,
                            tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)) AS total,
                            tbl_pedido_item_nf.qtde_nf AS qtde_faturada_outros,
                            tbl_pedido_item.obs
                    FROM  tbl_pedido
                    JOIN  tbl_pedido_item USING (pedido)
                    JOIN  tbl_peca        USING (peca)
                    LEFT JOIN    tbl_pedido_item_nf USING (pedido_item)
                    WHERE tbl_pedido_item.pedido = $pedido
                    AND   tbl_pedido.fabrica = $login_fabrica
                    GROUP BY tbl_pedido_item.peca,
                            tbl_pedido_item.peca,
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_peca.ipi,
                            tbl_pedido_item.qtde,
                            tbl_pedido_item.preco,
                            tbl_pedido.desconto,
                            tbl_pedido_item_nf.qtde_nf,
                            tbl_pedido_item.obs
                    ORDER BY tbl_peca.descricao;";
        }else{
            $order = ($login_fabrica == 15 OR $login_fabrica == 87) ? " ORDER BY tbl_peca.descricao " : " ORDER BY tbl_pedido_item.pedido_item; ";

            $sql = "SELECT  tbl_pedido_item.peca,
                            tbl_peca.referencia,
                            tbl_peca.referencia_fabrica   AS peca_referencia_fabrica,
                            tbl_peca.descricao,
                            tbl_peca.ipi,
                            tbl_peca.parametros_adicionais AS peca_pa,
                            CASE
                                WHEN tbl_pedido.fabrica = 42
                                THEN
                                    CASE
                                        WHEN tbl_peca.previsao_entrega IS NULL THEN ''
                                        ELSE TO_CHAR(tbl_peca.previsao_entrega,'DD/MM/YYYY')
                                    END
                                ELSE ''
                            END AS previsao_entrega,
                            tbl_pedido_item.pedido_item    ,
                            tbl_pedido_item.qtde           ,
                            tbl_pedido.valores_adicionais  ,
                            tbl_pedido_item.valores_adicionais::text AS valores_item,
                            tbl_pedido_item.qtde_faturada  ,
                            tbl_pedido_item.qtde_faturada_distribuidor  ,
                            tbl_pedido_item.qtde_cancelada ,
                            CASE
                                WHEN tbl_pedido.fabrica = 87 AND tbl_pedido_item.preco_base IS NOT NULL THEN
                                    tbl_pedido_item.preco_base
                                ELSE
                                    tbl_pedido_item.preco
                            END as preco,
                            CASE
                                WHEN tbl_pedido.fabrica IN (88, 104) THEN
                                    tbl_posto_fabrica.desconto
                                ELSE
                                    tbl_pedido.desconto
                            END as desconto,
                            CASE
                                WHEN tbl_pedido.fabrica = 14 THEN
                                    TRUNC((tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::NUMERIC, 5)
                                WHEN tbl_pedido.fabrica IN (35, 87) THEN
                                    tbl_pedido_item.preco
                                WHEN tbl_pedido.fabrica IN (88,126,160) THEN
                                    (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco
                                WHEN tbl_pedido.fabrica = 24  THEN
                                    tbl_pedido_item.qtde * tbl_pedido_item.preco
                                WHEN tbl_pedido.fabrica IN (138, 143) THEN
                                    (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))
                                ELSE tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))
                            END AS total,
                            tbl_pedido_item.obs,
                            tbl_pedido_item.estoque,
                            CASE
                                WHEN tbl_pedido.fabrica = 160 THEN
                                    tbl_pedido_item.total_item - (tbl_pedido_item.preco * tbl_pedido_item.qtde_cancelada)
                                ELSE
                                    tbl_pedido_item.total_item
                            END AS total_item,
                            tbl_pedido_item_nf.qtde_nf  AS qtde_faturada_outros,
                            tbl_pedido_item.condicao    AS item_condicao,
                            tbl_pedido.previsao_entrega AS previsao_faturamento,
                            tbl_pedido_item.preco_base
                    FROM tbl_pedido

                    JOIN tbl_pedido_item           USING (pedido)
                    JOIN tbl_peca                  USING (peca)
               LEFT JOIN tbl_pedido_item_nf        USING (pedido_item)
                    JOIN tbl_posto_fabrica
                      ON tbl_pedido.posto          = tbl_posto_fabrica.posto
                     AND tbl_posto_fabrica.fabrica = $login_fabrica
                   WHERE tbl_pedido_item.pedido = $pedido
                     AND tbl_pedido.fabrica     = $login_fabrica
                    $order ";
        }
        $res = pg_query ($con,$sql);
        if($login_fabrica == 94){
            $total_pedido = 0;
        }
        $total_pedido = 0;
        $total_geral_ipi = 0;
        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
            $peca                        = pg_fetch_result($res, $i, 'peca');
            $qtde                        = pg_fetch_result($res, $i, 'qtde');
            $qtde_faturada               = pg_fetch_result($res, $i, 'qtde_faturada');
            $qtde_faturada_distribuidor  = pg_fetch_result($res, $i, 'qtde_faturada_distribuidor');
            $qtde_faturada_outros        = pg_fetch_result($res, $i, 'qtde_faturada_outros');
            $qtde_cancelada              = pg_fetch_result($res, $i, 'qtde_cancelada');
            $pedido_item                 = pg_fetch_result($res, $i, 'pedido_item');
            $preco                       = pg_fetch_result($res, $i, 'preco');
            $preco_base                  = pg_fetch_result($res, $i, 'preco_base');
            $desconto                    = pg_fetch_result($res, $i, 'desconto');
            $ipi                         = pg_fetch_result($res, $i, 'ipi');
          
            if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696) {
                $ipi = 0;
            }
          
            $total                       = pg_fetch_result($res, $i, 'total');
            $referencia                  = pg_fetch_result($res, $i, 'referencia');
            $descricao                   = pg_fetch_result($res, $i, 'descricao');
            $previsao_entrega            = pg_fetch_result($res, $i, 'previsao_entrega');
            $obs_pedido_item             = pg_fetch_result($res, $i, 'obs');
            $peca_param_ad               = pg_fetch_result($res, $i, 'peca_pa');
            $pedido_valores_adicionais   = pg_fetch_result($res, $i, 'valores_adicionais');
            

	    if ($login_fabrica == 175){
                $valoresItens = pg_fetch_result($res, $i, 'valores_item');
                        
                $valoresItens = str_replace('"{', '{', $valoresItens);
                $valoresItens = str_replace('}"', '}', $valoresItens);
                $valoresItens = str_replace('\\', '', $valoresItens);
                $valoresItens = json_decode($valoresItens, true);
                
                if (!empty($valoresItens['aliq_ipi'])){
                    $aliq_ipi_item = $valoresItens['aliq_ipi'];
                }else{
                    $aliq_ipi_item = 0;
                }
                if (!empty($valoresItens['base_ipi'])){
                    $base_ipi_item = $valoresItens['base_ipi'];
                }else{
                    $base_ipi_item = 0;
                }
                if (!empty($valoresItens['ipi'])){
                    $ipi_item = $valoresItens['ipi'];
                }else{
                    $ipi_item = 0;
                }
                if (!empty($valoresItens['aliq_icms'])){
                    $aliq_icms_item = $valoresItens['aliq_icms'];
                }else{
                    $aliq_icms_item = 0;
                }
                if (!empty($valoresItens['base_icms'])){
                    $base_icms_item = $valoresItens['base_icms'];
                }else{
                    $base_icms_item = 0;
                }
                if (!empty($valoresItens['icms'])){
                    $icms_item = $valoresItens['icms'];
                }else{
                    $icms_item = 0;
                }
                if (!empty($valoresItens['total_impostos'])){
                    $total_impostositem = $valoresItens['total_impostos'];
                }else{
                    $total_impostositem = 0;
                }
            }

            if (isFabrica(42)) {
                $peca_pa = new Json(trim(pg_fetch_result($res, $i, 'peca_pa'), false)); // false: sem throw Exception
                $disponibilidade = '';
                // pre_echo(pg_fetch_assoc($res, $i), $descricao, true);

                // HD 3765012
                if (($qtde - $qtde_faturada - $qtde_cancelada) > 0) {
                    // pre_echo($peca_pa->data, $peca_param_ad, true);

                    $disponibilidade  = $peca_pa->status;
                    $previsao_chegada = is_date($peca_pa->previsaoEntrega, '', 'EUR') ? : '';

                    if ($disponibilidade == 'I') {
                        $disponibilidade = "<button type='button' class='abre_hd_pedido_peca' ".
                            "data-peca='$peca' data-prevista='$previsao_chegada' data-ref='$referencia' data-desc='$descricao' ".
                            "data-exportado='$pedido_data' data-pedido='$pedido' ".
                            "title='" . traduz('produto.indisponivel.em.estoque.no.momento.clique.para.ver.a.previsao.de.chegada') . "'".
                            ">Indisponível</button>";
                    } elseif ($disponibilidade == 'D') {
                        $disponibilidade = '<span title="' . traduz('produto.disponivel.em.estoque.aguardando.transferencia.entre.as.filiais.para.entrega') . '">'.
                            'Disponível</span>';
                    } else {
                        $disponibilidade = "&mdash;";
                    }
                }
                // $disponibilidade  = "$qtde - $qtde_faturada - $qtde_cancelada";
            }

            if ($login_fabrica == 143) {
                $disponibilidade = new PedidoWackerNeuson($referencia);

                $disponibilidade = $disponibilidade->verificaEstoquePeca();
                $disponibilidade = $disponibilidade->retornosEstoque->temEst == 'N' ? 'Não' : 'Sim';
                sleep(1);                
            }

            // pre_echo($peca_pa, "Peça: $referencia ($peca) $qtde - $qtde_faturada - $qtde_cancelada");

            if ($login_fabrica!= 43 AND $login_fabrica != 99) {
                $condicao_item = pg_fetch_result ($res,$i,'item_condicao');
            }
            $peca_referencia_fabrica = "";
            if ($login_fabrica == 171) {
                $peca_referencia_fabrica = " / " . pg_fetch_result ($res,$i,'peca_referencia_fabrica');
            }

            $peca_descricao    = pg_fetch_result ($res,$i,'referencia') . $peca_referencia_fabrica . " - " . pg_fetch_result ($res,$i,'descricao');
            $xpeca_referencia  = pg_fetch_result ($res,$i,'referencia');
            $xpeca_descricao   = pg_fetch_result ($res,$i,'descricao');

            switch (pg_fetch_result($res, $i, 'estoque')) {
                case 't':
                    $estoque = 'SIM';
                    break;
                case 'f':
                    $estoque = 'NÃO';
                    break;
                default:
                    $estoque = '';
                    break;
            }

            $total_item = pg_fetch_result($res, $i, 'total_item');

			if((in_array($login_fabrica,array(11,24,74,86,94)) or $login_fabrica >= 138) and $login_fabrica <> 160 and !$replica_einhell){
				$ipi = ($login_fabrica == 138) ? 0 : $ipi;
                $total = ($preco + ($preco * $ipi)/100) *($qtde - $qtde_cancelada);
                $total_pedido += $total;
            }else if($login_fabrica==7){
                if(strlen($pedido)>0 AND strlen($peca)>0){
                    $sqlx = "SELECT tbl_os_item.preco
                             FROM tbl_os
                             JOIN tbl_os_produto USING(os)
                             JOIN tbl_os_item    USING(os_produto)
                             WHERE tbl_os.fabrica = $login_fabrica
                             AND (tbl_os_item.pedido = $pedido OR tbl_os_item.pedido_cliente = $pedido)
                             AND tbl_os_item.peca   = $peca";
                    #echo nl2br($sqlx);
                    $resx = @pg_query ($con,$sqlx);
                    if (@pg_num_rows($resx)>0){
                        $preco        = pg_fetch_result($resx,0,preco);
                        $total        = $preco + ($preco * $ipi / 100);
                        $total_pedido = $total;
                    }
                }
            }else if($login_fabrica == 87){
                //$total_pedido += $total * $qtde;
            }else{
                $total_pedido += $total;
            }

            if (!(in_array($login_fabrica, array(2,3,6,11,14,15,24,30,35,42,45,50,51,52,72,74,80,81,85,87,88,90,91,94)) or $login_fabrica >= 95)) {
                // VERIFICA PENDÊNCIAS DESTA PEÇA NO FATURAMENTO PARA O PEDIDO ESPECÍFICO
                if(!in_array($login_fabrica,array(59,65))){
                    if(in_array($login_fabrica,array(7,42,43))){
                        $sql = "SELECT  tbl_faturamento_item.pendente
                                FROM    tbl_faturamento_item
                                JOIN    tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                WHERE   tbl_faturamento_item.peca = $peca
                                AND     tbl_faturamento_item.pedido    = $pedido;";
                    }else{
                        $sql = "SELECT  tbl_faturamento_item.pendente
                                FROM    tbl_faturamento_item
                                JOIN    tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                WHERE   tbl_faturamento_item.peca = $peca
                                AND     tbl_faturamento.pedido    = $pedido;";
                    }
                }else{
                    $sql = "SELECT  tbl_faturamento_item.pendente
                            FROM    tbl_faturamento_item
                            JOIN    tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                            WHERE   tbl_faturamento_item.peca = $peca
                            AND     tbl_faturamento_item.pedido_item = $pedido_item
                            AND     tbl_faturamento.pedido    = $pedido ORDER BY tbl_faturamento_item.pendente ASC;";
                }
                $resx = @pg_query ($con,$sql);
                $pendente =  (pg_num_rows($resx) > 0) ? trim(pg_fetch_result($resx,0,pendente)) : $qtde;

                // VERIFICA PENDÊNCIA TOTAL DO ITEM NO FATURAMENTO PARA O POSTO
                ###############################################################################
                # ESTAVA ASSIM, MAS TEM QUE AGRUPAR COM OS NÃO FATURADOS
                $sql = "SELECT  sum(tbl_faturamento_item.pendente) AS pendencia_total
                        FROM    tbl_faturamento_item
                        JOIN    tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                        WHERE   tbl_faturamento_item.peca = $peca
                        AND     tbl_faturamento.posto     = $login_posto
                        AND     tbl_faturamento.fabrica   = $login_fabrica;";
                ###############################################################################
                if($login_fabrica == 59 OR $login_fabrica == 65) { $limit1 = 'limit 1'; }
                ###############################################################################
                # BUSCA OS PEDIDOS COM FATURAMENTO E OS PEDIDOS JÁ EXPORTADOS E SEM FATURAMENTO
                $sql = "SELECT  sum(x.pendencia_total) AS pendencia_total
                        FROM (
                            (
                                SELECT tbl_faturamento_item.pendente AS pendencia_total
                                FROM   tbl_faturamento_item
                                JOIN   tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                WHERE  tbl_faturamento_item.peca = $peca
                                AND    tbl_faturamento.posto     = $login_posto
                                AND    tbl_faturamento.fabrica   = $login_fabrica
                                GROUP BY tbl_faturamento_item.pendente $limit1
                            ) UNION (
                                SELECT sum(tbl_pedido_item.qtde) AS pendencia_total
                                FROM   tbl_pedido_item
                                JOIN   tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
                                WHERE  tbl_pedido_item.peca = $peca
                                AND    tbl_pedido.posto     = $login_posto
                                AND    tbl_pedido.fabrica   = $login_fabrica
                                AND    tbl_pedido.exportado NOTNULL
                                AND (tbl_pedido.status_pedido NOT IN (4,13,14) OR tbl_pedido.status_pedido IS NULL)
                                AND    tbl_pedido.pedido NOT IN (
                                    SELECT tbl_faturamento.pedido
                                    FROM   tbl_faturamento
                                    JOIN   tbl_faturamento_item USING (faturamento)
                                    WHERE  tbl_faturamento.fabrica = $login_fabrica
                                    AND    tbl_faturamento.posto   = $login_posto
                                    AND    tbl_faturamento_item.peca = $peca
                                )
                            )
                        ) AS x;";
                $resx = @pg_query ($con,$sql);
                $pendente_total = (pg_num_rows($resx) > 0) ? trim(pg_fetch_result($resx,0,pendencia_total)) : $qtde;

                // CASO A PENDÊNCIA TOTAL = 0 E A PENDÊNCIA DO PEDIDO NÃO TENHA SIDO FATURADA
                // A PENDÊNCIA SERÁ A QUANTIDADE PEDIDA
                if ($pendente_total == 0 AND $pendente > 0) $pendente_total = $qtde;
            }
            if ($login_fabrica==3 and $distribuidor <> 4311){
                $sql_X = "SELECT    peca, sum(tbl_os_item_nf.qtde_nf) as total
                        FROM    tbl_os
                        JOIN    tbl_os_produto USING (os)
                        JOIN    tbl_os_item    USING (os_produto)
                        JOIN    tbl_os_item_nf USING (os_item)
                        JOIN    tbl_peca USING (peca)
                        WHERE   tbl_os.posto   = $login_posto
                        AND     tbl_peca.peca=$peca
                        AND     tbl_os.fabrica = $login_fabrica
                        AND     tbl_os_item.pedido = $pedido
                        GROUP BY peca";
                $res_X = @pg_query ($con,$sql_X);
                $pendencia_outros = (pg_num_rows($res_X) > 0) ? trim(pg_fetch_result($res_X,0,total)) : 0;
            }
            if (strlen($distribuidor)==0){
                if(!in_array($login_fabrica,array(51,59,65))){
                    $sql_2 = "SELECT distinct tbl_faturamento.faturamento                     ,
                                    tbl_faturamento.nota_fiscal                               ,
                                    to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                                    tbl_faturamento_item.faturamento_item                     ,
                                    tbl_faturamento_item.peca                                 ,
                                    tbl_faturamento_item.qtde                                 ,
                                    tbl_peca.peca                                             ,
                                    tbl_peca.referencia                                       ,
                                    tbl_peca.descricao
                            FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido ) tbl_pedido_item
                            JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
                            JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                        AND tbl_faturamento.fabrica = $login_fabrica
                            JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
                            WHERE tbl_peca.peca=$peca
                            ORDER   BY tbl_peca.descricao";
                }else{
                    $sql_2 = "SELECT distinct tbl_faturamento.faturamento                      ,
                                    tbl_faturamento.nota_fiscal                                ,
                                    to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao  ,
                                    tbl_faturamento_item.faturamento_item                      ,
                                    tbl_faturamento_item.peca                                  ,
                                    (select sum(qtde) from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_faturamento.pedido AND tbl_faturamento_item.peca = tbl_peca.peca AND tbl_faturamento_item.pedido_item = $pedido_item) as qtde,
                                    tbl_peca.peca                                              ,
                                    tbl_peca.referencia_fabrica   AS peca_referencia_fabrica,
                                    tbl_peca.referencia                                        ,
                                    tbl_peca.descricao
                            FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                            JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
                            JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                        AND tbl_faturamento.fabrica = $login_fabrica
                            JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
                            WHERE  tbl_peca.peca=$peca
                            AND    tbl_faturamento_item.pedido_item = $pedido_item
                            ORDER   BY tbl_peca.descricao";
                }
                $res_2 = @pg_query ($con,$sql_2);
                $pendencia_distr = (pg_num_rows($res_2) > 0) ? trim(pg_fetch_result($res_2,0,qtde)) : 0;
		if ($login_fabrica == 183 AND pg_num_rows($res_2) > 0){
			$numero_nf_faturamento = pg_fetch_result($res_2, 0, 'nota_fiscal');
			$data_emissao = pg_fetch_result($res_2,0,'emissao');
		}
            }

            if($login_fabrica == 15) { # HD 126026
                if ($peca_anterior == $peca) {
                    $lista_os .= (!empty($lista_os)) ? ",".$os : $os;
                    $condicao1 =  " AND tbl_os.os not in ($lista_os) ";
                }else{
                    $condicao1 = '';
                    $lista_os  = '';
                }

                $sql_os = "SELECT  distinct
                        tbl_os.os,
                        tbl_os.sua_os
                FROM    tbl_pedido
                JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
                JOIN    tbl_os_item     ON  tbl_os_item.peca           = tbl_pedido_item.peca AND tbl_os_item.pedido         = tbl_pedido.pedido
                JOIN tbl_os_produto     ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                JOIN tbl_os             ON tbl_os.os                 = tbl_os_produto.os
                WHERE   tbl_pedido_item.pedido = $pedido
                AND     tbl_pedido_item.pedido_item  = $pedido_item
                $condicao1 ";
                $res_os = @pg_query($con,$sql_os);
                $os ='';
                $sua_os ='';
                if(@pg_num_rows($res_os) > 0){
                    $os     = pg_fetch_result($res_os,0,os);
                    $sua_os = pg_fetch_result($res_os,0,sua_os);
                }
            }
        ?>
        <tr class='table_line1' bgcolor="<?php echo $cor;?>">
<?php
            if($login_fabrica == 15 and $tipo_pedido =='Garantia'){
?>
                <td><a href='os_press.php?os=<?php echo $os;?>' target='_blank'><?php echo $os;?></a></td>
<?php
            }

            if($login_fabrica == 15 and $tipo_pedido !='Garantia') { # HD 126026
?>
                <td><a href='os_press.php?os=<?php echo $os;?>' target='_blank'><?php echo $sua_os;?></a></td>
<?php
            }
            if ($login_fabrica == 147) {
?>
            <td class='esq'  nowrap><?=$xpeca_referencia?></td>
            <td class='esq'  nowrap><?=$xpeca_descricao?></td>
<?php
            } else {
?>
            <td class='esq'  nowrap><?=$peca_descricao?></td>
<?php
            }
?>
            <td align='center'><?=$qtde?></td>
            <td align='center' style='color:#FF0000;font-weight:bold;'>
                <?
                echo ($qtde_cancelada == 0 OR strlen($qtde_cancelada) == 0) ? "0" : $qtde_cancelada;
                ?>
            </td>

            <?
            if($login_fabrica == 3){
                if ($distribuidor == 4311){
                    $qtde_faturada = $qtde_faturada_distribuidor;
                }

                if ($distribuidor <> 4311){
                    if(strlen($qtde_faturada) == 0){
                        $qtde_faturada = $qtde_faturada_distribuidor+$qtde_faturada_outros+$pendencia_outros-$qtde_cancelada;
                    }
                }
            }else{
                if ( $distribuidor == 4311)
                    $qtde_faturada = $qtde_faturada_distribuidor;

                //não pode setar para cadence, pois cadence pode atender o mesmo pedido parcialmente
                //não pode setar para DYNACOM, pois a rotina de atualização de pedidos não relaciona
                //diretamente com o faturamento e sim em tbl_pedido_item_faturamento_item
                if ($telecontrol_distrib) {
                    $qtde_faturada = $qtde_faturada_distribuidor;
                }
            }
            ?>

            <td align='center'><?=$qtde_faturada?></td>

            <?php
            //$lista_fabrica = array(2,3,6,11,14,15,24,30,35,45,50,51,72,74,80,81,85,90,91,94);
            //if (in_array($login_fabrica,$lista_fabrica) or $login_fabrica > 95) {
            if ( in_array($login_fabrica, array(2,3,6,11,14,15,24,30,35,42,45,50,51,52,72,74,80,81,85,88,90,91,94)) or $login_fabrica >= 95) {
                $qtde_pendente = $qtde - $qtde_faturada - $qtde_cancelada;
                if($qtde_faturada == 0 and $qtde_cancelada == 0){
                    $qtde_pendente = $qtde;
                }
                echo "<td class='pendencia' align='center'>";
                if ($qtde_pendente <= 0 OR strlen($qtde_pendente) == 0) { echo "0"; } else { echo $qtde_pendente; }
                echo "</td>";
            }
            else if($login_fabrica == 87) {

                echo '<td style="color:red;">'.($qtde - ($qtde_faturada + $qtde_cancelada) ).'</td>';

            }
            else{
                if ($qtde_cancelada == $qtde and $login_fabrica == 40){
                    $pendente = 0;
                    $pendente_total = 0;
                }else if ( $login_fabrica==40 ){
                    $pendente = $qtde - $qtde_cancelada;
                    $pendente_total = $qtde - $qtde_cancelada;
                }
                echo "<td class='pendencia' align='center'> $pendente </td>";

                if($login_fabrica <> 74 and $login_fabrica <> 40 and $login_fabrica <> 46 and $login_fabrica < 95){
                    echo "<td class='pendencia'>";
                    #não vi diferença entre as pendências HD21715
                    //$lista_fabrica2 = array(3, 11, 35, 51, 45, 72);
                    if (!in_array($login_fabrica, array(3,11,35,51,45,72,74,172))) { echo $pendente_total; } else { echo $pendente; }
                    echo "</td>";
                }
            }

	    if ($login_fabrica == 183){
		echo "<td> $numero_nf_faturamento </td>";
		echo "<td> $data_emissao </td>";
	    }
			if($login_fabrica == 143 and $status_pedido <> 19) {
				echo "<td class='pendencia' align='center'> $disponibilidade </td>";
			}

            if(in_array($login_fabrica, array(152,180,181,182))){

                $data_previsao_faturamento = pg_fetch_result($res, $i, "previsao_faturamento");
                list($ano, $mes, $dia) = explode("-", $data_previsao_faturamento);
                $data_previsao_faturamento = $dia."/".$mes."/".$ano;
            ?>
            <td style="text-align:center;"><?=$data_previsao_faturamento?></td>
            <?php
            }
            
            if (in_array($login_fabrica, array(175))) {
            ?>
                <td><?=number_format($preco_base, 2, ',', '.')?></td>
            <?php
            }

            if ( $liberar_preco && ($login_fabrica != 143 || ($login_fabrica == 143 && $status_pedido != 19) ) ) {
                if($login_fabrica == 88){
                    $preco = str_replace(",",".",$preco);
                }

                if (in_array($login_fabrica, array(81,122,123,125,114,128))){
                    echo "<td>".number_format ( $preco,4,",",".")."</td>";
                }else if ( $login_fabrica != 85 and $login_fabrica != 87) {
                    echo "<td>".number_format ( $preco,2,",",".")."</td>";
                } else {
                    echo "<td>".$preco."</td>";
                }

               if ($login_fabrica == 140) {
                    echo "<td>".number_format ( $preco*$qtde,2,",",".")."</td>";
                } 

               if (!in_array($login_fabrica,array(15))) {
                    if (!isset($telaPedido0315) && !in_array($login_fabrica, array(35,120,201))) {
                        echo "<td align='center'> $ipi </td>";
                    }
                    if(in_array($login_fabrica, array(147,149,153,156,157,165,168)) || ($usa_calculo_ipi) || (regiao_suframa() == false && in_array($login_fabrica, array(161)))) {
                        echo "<td align='center'> $ipi </td>";
                    }
                    if($login_fabrica == 24){
                        //$total = number_format($preco_original * $qtde,2,',','.');
                    }

                    if(in_array($login_fabrica, array(35,88,120,201))){
                        if($login_fabrica == 88){
                            #$preco = $preco + (($preco / 100) * $ipi);
                        }
                        $total = $preco * ($qtde - $qtde_cancelada);

                        if ($login_fabrica == 35) {
                            $total_geral_item += $total;
                        }
                    }

                    if($login_fabrica == 160 or $replica_einhell){
                        $valorDesconto = $total - ($total * ($pedido_desconto / 100));
                        $descontoValor = $total - $valorDesconto;
                        $valorTotalDesconto += $valorDesconto;

                        echo "<td> ". $pedido_desconto . "%</td>";
                        echo "<td> ".number_format( ($preco * ($pedido_desconto / 100)) ,2,",",".") ." </td>";
                    }


                    if((regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                        $total_sem_ipi += $preco * ($qtde - $qtde_cancelada);
                    }
                    
                    if (in_array($login_fabrica, array(175))) {
                    ?>
                        
                        <td align='right'><?=number_format($aliq_ipi_item,2,",",".")?></td>
                        <td align='right'><?=number_format($base_ipi_item,2,",",".")?></td>
                        <td align='right'><?=number_format($ipi_item,2,",",".")?></td>
                        <td align='right'><?=number_format($aliq_icms_item,2,",",".")?></td>
                        <td align='right'><?=number_format($base_icms_item,2,",",".")?></td>
                        <td align='right'><?=number_format($icms_item,2,",",".")?></td>
                        <td align='right'><?=number_format($total_impostositem,2,",",".")?></td>
                       <td><?=number_format(($preco_base * $qtde), 2, ',', '.')?></td>

                        <?php    
                            $total_icms_item           += $icms_item;
                            $total_ipi_item            += $ipi_item;

                            if ($qtde_cancelada > 0) {
                                $total_pecas                = $qtde - $qtde_cancelada;
                                $total_geral_impostos_item  += $total_pecas * $total_impostositem;
                            } else {
                                $total_geral_impostos_item  += $total_impostositem;
                            }

                        ?>                
                    <?php
                    }

                    if (isset($telaPedido0315)) {
                        if(in_array($login_fabrica,array(138,139,147,156,157,165,168))){
                            $total_item = ( $preco * (1 + ($ipi/100)) * $qtde);
                        }

                        if($login_fabrica == 147) { /*HD-3844543 26/10/2017*/
                            $total_geral_ipi += $total_item;
                        }

                        echo "<td align='char' char=','>".number_format($total_item, 2, ",", ".")."</td>";
                    } else if ($login_fabrica != 87) {
                        echo "<td align='right' char=','>".number_format($total,2,",",".")."</td>";
                    }  else {
                        echo "<td align='char' char=','>".$total."</td>";
                    }
                }
            }

            // HD 3765012
            if (isFabrica(42)) {
                echo "<td bgcolor='$cor' align='center'>$disponibilidade</td>";
            }

            if (strlen($previsao_entrega) > 0) {
                echo "<tr bgcolor='$cor'>";
                echo "<td colspan='9'>";
                echo "<font face='Verdana' size='1' color='#CC0066'>";
                echo "Esta peça estará disponível em $previsao_entrega";
                echo "<br>";
                echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor.";
                echo "</font>";
                echo "</td>";
                echo "</tr>";
            }

            // HD 8412
            if(($login_fabrica==35 or $login_fabrica == 160 or $replica_einhell) and strlen($obs_pedido_item)>0){
                echo "<tr bgcolor='$cor'>";
                echo "<td colspan='$colunas'> <img src='imagens/setinha_linha.gif' border='0' />  ";
                echo "<font face='Arial' size='1' color='#000099'>";
                echo "$obs_pedido_item";
                echo "</font>";
                echo "</td>";
                echo "</tr>";
            }
            if($login_fabrica == 87 ) {
                $sqlTotItem = "SELECT total_item, total_sem_st FROM tbl_pedido_item_jacto WHERE pedido_item = $pedido_item";
                $qryTotItem = pg_query($con, $sqlTotItem);

                $total_sem_st = 0;

                if (pg_num_rows($qryTotItem) == 1) {
                    if (empty($total_item)) {
                        $total_item = pg_fetch_result($qryTotItem, 0, 'total_item');
                    }
                    $total_sem_st = pg_fetch_result($qryTotItem, 0, 'total_sem_st');
                }

                $total_pedido += $total_item;
                $total_pedido_sem_st += $total_sem_st;
                echo "<td>".number_format($total_sem_st, 2, ",", ".")."</td>";
                echo "<td>".number_format($total_item, 2, ",", ".")."</td>";
                echo '<td>';
                if(!empty($condicao_item)) {
                    $sql_cond = "SELECT descricao FROM tbl_condicao WHERE condicao = $condicao_item";
                    $res_cond = pg_query($con,$sql_cond);
                    if(pg_num_rows($res)) {
                        $desc_condicao = pg_result($res_cond,0,0);
                        echo $desc_condicao;
                    } else {
                        echo '&nbsp;';
                    }
                }
                echo '</td>';

                if ($tipo_pedido == "Orçamento") {
                    echo '<td class="center">' , $estoque , '</td>';
                }
            }
            if ($login_fabrica == 147) {
                $valorDesconto = $total - ($total * ($pedido_desconto / 100));
                $valorTotalDesconto += $valorDesconto;
?>
                <td><?=$pedido_desconto?></td>
                <td><?=number_format($valorDesconto,2,',','.')?></td>
<?php
			}
            ?>
        </tr>
        <?
        $peca_anterior = $peca;
        }
        if($login_fabrica == 87)
            $colunas = 9;

        if(in_array($login_fabrica, array(2,3,6,11,14,15,24,30,35,40,45,46,50,51,52,72,74,80,81,85,88,90,91,94)) or $login_fabrica >= 95)
            $colunas = 8;

		if($login_fabrica == 35) $colunas='6';
        ?>

        <?
        if ($liberar_preco) {
            if (!in_array($login_fabrica, array(138,143,104)) && !isset($telaPedido0315)) {

                if($login_fabrica != 120 and $login_fabrica != 201){
        ?>
                <tr>
                    

<?php
                if(in_array($login_fabrica, array(120,201))){
                    $colunas = $colunas - 2;
                }elseif ($login_fabrica == 140) {
                    $colunas = 8;
                }else{
                    $colunas = $colunas - 1;
                } 
?>
                    <td colspan='<?php echo $colunas; ?>' class='titulo_coluna'>

                    <?
                        if( in_array($login_fabrica, array(11,172)) ){
                            echo "SUBTOTAL";
                        } else {
                            if(in_array($login_fabrica,array(88,94,120,201))){

                                $desconto = (strlen(trim($desconto)) == 0) ? 0 : $desconto;

                                fecho('valor.total.do.saldo.com.ipi.em.r.com.desconto.de.%', $con, $cook_idioma, array($desconto.'%'));

                                echo " e valor do frete";
                            } else {
                                echo strtoupper(traduz('total', $con));
                            }
                        }

                    ?>
                    </td>
                    <td class='menu_top' style='font-weight:bold; color:white;'>
                    <? if ($login_fabrica <> 14){
                            if(in_array($login_fabrica,array(88,94))){
                                #echo $total_pedido ."= ".$total_pedido ."- ((".$total_pedido ."*". $desconto.") / 100) + ".$valor_frete;

                                    $total_pedido += $valor_frete;

                            }

                            if ($login_fabrica == 87) {
                                echo number_format ($total_pedido_sem_st,2,",",".");
                                echo '</td>';
                                echo "<td class='menu_top' colspan='1' style='font-weight:bold; color:white;'>";
                            }
                            echo number_format ($total_pedido,2,",",".");


                    } else {
                          echo str_replace (".",",",$total_pedido);
                    }
                    if($login_fabrica == 87)  {
                        if ($tipo_pedido == "Orçamento") {
                            echo '<td colspan="2" class="menu_top">&nbsp;</td>';
                        } else {
                            echo '<td colspan="1" class="menu_top">&nbsp;</td>';
                        }
                    }
?>
                    </td>
                    <?php  
                    if ($login_fabrica == 35) { ?>
                        <td class='titulo_coluna'>
                            <?= number_format ($total_geral_item,2,",","."); ?>
                        </td>
                    <?php
                    }
                    if ($extra_footer === true) {
                        echo "\n\t\t<td class='titulo_coluna'>&nbsp;</td>\n";
                    }
                    ?>
                </tr>
                <?php } ?>
                <?php
                if($login_fabrica == 120 or $login_fabrica == 201){

                    $valor_total_sem_ipi = $tbl_pedido_total + $valor_frete;

                ?>
                <tr>
                    <td colspan="7" style="background-color: #ff0000; color: #fff; text-align: center; font-weight: bold;">
                        ATENÇÃO: O valor do seu pedido é de R$ <?php echo number_format($valor_total_sem_ipi, 2); ?> com o frete e sem os impostos.
                    </td>
                </tr>
                <?php } ?>
            <?
            }
            if (in_array($login_fabrica,array(11,172)) and strtoupper($tipo_pedido)=="VENDA")  {
                echo "<tr>";
                echo "<td colspan='7' class='menu_top'>" . traduz('desconto.sobre.pedido.de.venda', $con) . " ($pedido_desconto%)</td>";
                echo "<td class='menu_top'><b>";
                echo str_replace ('.',',',$total_pedido * $pedido_desconto / 100)."</b></td>";
                echo "</tr>";
                echo "<tr class='table_line1'>";
                echo "<td colspan='7' class='menu_top'>TOTAL</td>";
                echo "<td align='right' class='menu_top'><b>";


                $total_geral = $total_pedido - ($total_pedido * $pedido_desconto / 100);
                echo str_replace ('.',',',number_format($total_geral,2,",","."))."</b></td>";
                echo "</tr>";
            }else{
                if(!in_array($login_fabrica, array(149,152,153,156,157,161,165,168,180,181,182)) AND (!isset($usa_calculo_ipi))) {
                    $colspanTotal = ((isset($telaPedido0315)) ? 6 : 7);
                }else{
                    $colspanTotal = (regiao_suframa() == true && in_array($login_fabrica, array(161))) ? 6 : 7;
                }

                    $colspanTotal = ($login_fabrica == 160 or $replica_einhell) ? 8 : $colspanTotal;
                $usa_desconto = \Posvenda\Regras::get("usa_desconto", "pedido_venda", $login_fabrica);

                if($usa_desconto == true){
                    $mostra_desconto = false;
                    if ($login_fabrica == 138 && $tipo_pedido == "VENDA") {
                        $mostra_desconto = true;
                    }else if(in_array($login_fabrica, [167, 203]) && $tipo_pedido == "VENDA"){
                        $mostra_desconto = true;
                    } else if ($login_fabrica == 143 && $tipo_pedido == "USO/CONSUMO") {
                        $mostra_desconto = true;
                    } else if (!in_array($login_fabrica, array(138, 143, 156, 167, 203))) {
                        $mostra_desconto = true;
                    }

                    if($login_fabrica == 147) $colspanTotal = 8; /* HD-3844543 26/10/2017*/
                    if($login_fabrica == 143 and $status_pedido == 19) $colspanTotal = 4 ; 
                    if($login_fabrica == 143 and $status_pedido != 19) $colspanTotal = 7 ; 

            		if($mostra_desconto == true && !in_array($login_fabrica,array(147,160)) && !$replica_einhell) {
                        echo "<tr class='table_line1'>";
                        echo "<td colspan='$colspanTotal' class='menu_top'>Desconto sobre pedido de venda </td>";
                        echo "<td align='right' class='menu_top'><b>{$pedido_desconto}%</b></td>";
                        echo "</tr>";
                    }
            	}
 
                $usa_frete = \Posvenda\Regras::get("usa_frete", "pedido_venda", $login_fabrica);
                $usa_frete_estado = \Posvenda\Regras::get("usa_frete_estado", "pedido_venda", $login_fabrica);
                if(!empty($usa_frete) || !empty($usa_frete_estado) OR in_array($login_fabrica, array(163)) ){
                    echo "<tr class='table_line1'>";
                    echo "<td colspan='$colspanTotal' class='menu_top'>Frete</td>";
                    echo "<td align='right' class='menu_top'><b>";
                    echo number_format($valor_frete, 2, ",", ".")."</b></td>";
                    echo "</tr>";
                }

                if(in_array($login_fabrica, [167, 203]) && $tipo_pedido == "VENDA"){
                    $pedido_valores_adicionais = json_decode($pedido_valores_adicionais,true);

                    echo "<tr class='menu_top'>
                        <td colspan='7'>Desconto Fabricante</td>
                        <td>{$pedido_valores_adicionais['valor_desconto_fabricante']}%</td>
                    </tr>";
                    echo "<tr class='menu_top'>
                        <td colspan='7'>Adicional Fabricante</td>
                        <td>{$pedido_valores_adicionais['adicional_fabricante']}</td>
                    </tr>";
                }
                if( (regiao_suframa() == false && in_array($login_fabrica, array(161, 104)))){
                    echo "<tr class='table_line1'>";
                    echo "<td colspan='$colspanTotal' class='menu_top'>";
                    echo ($login_fabrica == 160 or $replica_einhell) ? "TOTAL S/DESCONTO" : "TOTAL S/ IPI";
                    echo "</td>";
                    echo "<td align='right' class='menu_top'><b>";
                    echo str_replace ('.',',',number_format($total_sem_ipi,2,",","."))."</b></td>";
                    echo "</tr>";
                }
   
                if($login_fabrica == 168){
                    $sql = "SELECT  tbl_condicao.acrescimo_financeiro
                            FROM tbl_pedido
                            INNER JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
                            WHERE tbl_pedido.pedido = $pedido AND tbl_pedido.fabrica = $login_fabrica";
                    $res = pg_query($con, $sql);
                    if(pg_num_rows($res)>0){
                        $acrescimo_financeiro = pg_fetch_result($res, 0, acrescimo_financeiro);

                        if(empty($acrescimo_financeiro)){
                            $acrescimo_financeiro = 0;
                        }else{
                            $acrescimo_financeiro = ($acrescimo_financeiro - 1) * 100;
                        }

                        $acrescimo_financeiro = number_format($acrescimo_financeiro, 2, ",", ".");

                        echo "
                        <tr>
                            <td colspan='$colspanTotal' class='menu_top'>Acréscimo financeiro</td>
                            <td class='menu_top'>$acrescimo_financeiro%</td>
                        </tr>";
                    }

                    if(strlen($valor_frete) > 0){

                        $sql_t = "SELECT valores_adicionais FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$login_fabrica}";
                        $res_t = pg_query($con, $sql_t);

                        $transportadora_frete = pg_fetch_result($res_t, 0, "valores_adicionais");

                        $valor_frete_desc = number_format($valor_frete, 2, ",", ".");

                        echo "
                        <tr>
                            <td colspan='$colspanTotal' class='menu_top'> FRETE ({$transportadora_frete}) </td>
                            <td class='menu_top'>{$valor_frete_desc}</td>
                        </tr>";

                    }

                }
                ?>
                <?php if ($login_fabrica == 175){ 
                        $colspan_i = "15";
                        
                    ?>
                    
                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('VALOR DO FRETE')?></td>
                        <td><?=(!empty($pedidoValorFrete)) ? number_format($pedidoValorFrete,2,",",".") : "0,00"?></td>
                    </tr>
                    
                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('VALOR DA DESPESA')?></td>
                        <td><?= (!empty($pedidoValorDespesa)) ? number_format($pedidoValorDespesa,2,",",".") : "0,00"?></td>
                    </tr>
                    
                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('VALOR DO SEGURO')?></td>
                        <td><?= (!empty($pedidoValorSeguro)) ? number_format($pedidoValorSeguro,2,",",".") : "0,00" ?></td>
                    </tr>
                    
                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('VALOR DO ICMS RETIDO')?></td>
                        <td><?= (!empty($total_icms_item)) ? number_format($total_icms_item,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('VALOR TOTAL DO IPI')?></td>
                        <td><?= (!empty($total_ipi_item)) ? number_format($total_ipi_item,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('VALOR TOTAL DOS IMPOSTOS')?></td>
                        <td><?= (!empty($total_geral_impostos_item)) ? number_format($total_geral_impostos_item,2,",",".") : "0,00" ?></td>
                    </tr>

                    <tr class='titulo_coluna'>
                        <td colspan="<?=$colspan_i?>"><?=traduz('TOTAL DESCONTO')?></td>
                        <td><?= (!empty($pedidoValorDesconto)) ? number_format($pedidoValorDesconto,2,",",".") : "0,00" ?></td>
                    </tr>
                    
                <?php } ?>
                <?php
                if (!in_array($login_fabrica, array(3,35,42,87,140))) { //hd_chamado=2890050
                    echo "<tr class='table_line1'>";

                        if(in_array($login_fabrica, array(152,180,181,182))){
                            $colspanTotal = 7;
                        }
			
			if ($login_fabrica == 183){
				$colspanTotal = 8;
			}

                        if(in_array($login_fabrica, array(120,201))){
                            $colspanTotal = 6;
                        }
                        
                        if (in_array($login_fabrica, array(175))) {
                            $colspanTotal = 14;
                        }
                        if (!in_array($login_fabrica, array(40, 101, 143)) || in_array($login_fabrica, array(143)) && $status_pedido <> 19) {
                            $nome_total = ((regiao_suframa() == false && in_array($login_fabrica, array(161))))? " TOTAL C/ IPI" : "TOTAL";

                            $nome_total = (in_array($login_fabrica,array(160,169,170,191)) or $replica_einhell )? " TOTAL C/ DESCONTO" : $nome_total;

                            echo "<td colspan='$colspanTotal' class='menu_top'>". $nome_total ."</td>";
                            
                            if (in_array($login_fabrica, array(175))) {

                                $sqlTotalPedido = "SELECT sum((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco) as total FROM tbl_pedido_item WHERE pedido = $pedido";
                                $resTotalPedido = pg_query($con,$sqlTotalPedido);
                                $total_pedido_faturado = pg_fetch_result($resTotalPedido,0,0);

                                $tbl_pedido_total = $total_pedido_faturado + $total_geral_impostos_item;

                            ?>
                                <td align='right' class='menu_top'>
                                    <?= number_format($total_pedido_faturado, 2,".", ",") ?>
                                </td>
                            <?php
                            }
                            
                            echo "<td align='right' class='menu_top'><b>";
                            if ((isset($telaPedido0315) || in_array($login_fabrica, array(120,201,168)))) {
                                $total_geral = (in_array($login_fabrica, array(120,201,168))) ? $total_pedido + $valor_frete :  $tbl_pedido_total;
                                $total_geral = (in_array($login_fabrica, [138, 139])) ? $total_pedido : $total_geral;
                            }else {
                                $total_geral = $total_pedido;
                            }
                            if(in_array($login_fabrica, [167, 203])){
                                $valor_desconto = $pedido_valores_adicionais['valor_desconto_fabricante'];
                                $valor_adicionais_pedido = $pedido_valores_adicionais['adicional_fabricante'];

                                $valor_desconto = str_replace(",", ".", $valor_desconto);
                                $valor_adicionais_pedido = str_replace(",", ".", $valor_adicionais_pedido);

                                $total_com_desconto = $total_geral - ($total_geral * $valor_desconto / 100);
                                #$total_geral = $total_com_desconto + $valor_adicionais_pedido;
                                $total_geral = $total_com_desconto;
                                echo number_format($total_geral,2,",",".");
                            }else{
                                if(in_array($login_fabrica, [169,170])){
                                    $total_geral = $total_geral - ($total_geral * $desconto / 100);
                                    //echo number_format($total_geral,2,",",".");
                                }

                                if($login_fabrica == 147 && $pedido_desconto > 0) { /*HD-3844543 26/10/2017*/
                                    $total_geral = $total_geral_ipi - (($pedido_desconto / 100)*$total_geral_ipi);
                                } else if($login_fabrica == 147 && $pedido_desconto <= 0) {

                                    $total_geral = $total_geral_ipi;
                                }

                                if($usa_desconto and $login_fabrica <> 157){
                                    $total_geral = $total_geral - (($pedido_desconto / 100)*$total_geral);
                                }

                                echo number_format($total_geral,2,",",".");

                            }
                            echo "</b></td>";


                            if ($login_fabrica == 147) {
?>
                                <td class='menu_top'>&nbsp;</td>
                                <td align='right' class='menu_top'><?=number_format($valorTotalDesconto,2,',','.')?></td>
<?php
                            }
                        }
                    echo "</tr>";
                }
            }
        } ?>
        </table>
        <br />
        <?php

        /**
         * @author William Castro <william.castro@telecontrol.com.br>
         * 
         * hd-6517984
         * 
         * Adição dos botoes de Histórico HelpDesk e Abrir HelpDesk
         *
         */

       if (in_array($login_fabrica, [35])) {
    
            if (isset($tipo_pedido) == "Faturado") {

                // DIRECIONA PARA A TELA DE ABERTURA DO HELP DESK
                echo "<div>";
                echo "<a  class='btn' rel='shadowbox' style='float:right; margin-left: 5px' rel='shadowbox' href='shadowbox_lista_helpdesk.php?pedido={$pedido}' value='Historico'>Histórico Reclamação</a>";

                echo "<a target='_blank' rel='noopener noreferrer' class='btn' style='float:right; margin-left: 5px' href='helpdesk_posto_autorizado_novo_atendimento.php?posto_num={$codigo_posto}' value='Abrir'>Reclamação</a>";
                echo "</div>";

            } 
        }

        if (in_array($login_fabrica, array(175))) {
            if ($status_pedido == 18) {
            ?>
                <style>
                .btn {
                    display: inline-block;
                    padding: 4px 12px;
                    margin-bottom: 0;
                    font-size: 14px;
                    line-height: 20px;
                    text-align: center;
                    vertical-align: middle;
                    cursor: pointer;
                    border: 1px solid #cccccc;
                    border-radius: 4px;
                    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
                }
                
                .btn:hover, .btn:focus {
                    text-decoration: none;
                    background-position: 0 -15px;
                    transition: background-position 0.1s linear;
                }
                
                .btn-cancela-pedido {
                    color: #ffffff;
                    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
                    background-color: #da4f49;
                    background-image: linear-gradient(to bottom, #ee5f5b, #bd362f);
                    background-repeat: repeat-x;
                    border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
                }
                
                .btn-cancela-pedido:hover {
                    background-color: #bd362f;
                }
                
                .btn-aprova-pedido {
                    color: #ffffff;
                    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
                    background-color: #5bb75b;
                    background-image: linear-gradient(to bottom, #62c462, #51a351);
                    background-repeat: repeat-x;
                    border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
                }
                
                .btn-aprova-pedido:hover {
                    background-color: #51a351;
                }
                </style>
                <form method='post' >
                    <input type='hidden' name='pedido' value='<?=$pedido?>' />
                    <button type='submit' name='aguardando_aprovacao' value='aprova' class='btn btn-aprova-pedido' >Aprovar Pedido</button>
                    <button type='submit' name='aguardando_aprovacao' value='cancela' class='btn btn-cancela-pedido' >Cancelar Pedido</button>
                </form>
            <?php
            }
            
            if ($pedido_aprovado === true) {
            ?>
                <style>
                .alert {
                    padding: 8px 35px 8px 14px;
                    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
                    border: 1px solid #fbeed5;
                    border-radius: 4px;
                }
                
                .alert-success {
                    color: #468847;
                    background-color: #dff0d8;
                    border-color: #d6e9c6;
                }
                </style>
                <div class='alert alert-success' >Pedido aprovado com sucesso!</div>
                <script>
                if (typeof window.opener.pedidoAprovado == 'function') {
                    window.opener.pedidoAprovado(<?=$pedido?>);
                }
                </script>
            <?php
            }
            
            if ($pedido_cancelado === true) {
            ?>
                <style>
                .alert {
                    padding: 8px 35px 8px 14px;
                    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
                    border: 1px solid #fbeed5;
                    border-radius: 4px;
                }
                
                .alert-danger {
                    color: #b94a48;
                    background-color: #f2dede;
                    border-color: #eed3d7;
                }
                </style>
                <div class='alert alert-danger' >Pedido cancelado com sucesso!</div>
                <script>
                if (typeof window.opener.pedidoCancelado == 'function') {
                    window.opener.pedidoCancelado(<?=$pedido?>);
                }
                </script>
            <?php
            }
        }

            if($login_fabrica == 168){
                if ($status_pedido == 27) {
                    $valores_frete = json_decode($pedido_valores_adicionais);
?>
                <table width='100%' border='0' cellspacing='3' cellpadding='1' align='center' class='aprova_frete' bgcolor='#D9E2EF'>
                    <tr>
                        <th colspan="2" class='menu_top' style="font-size:14px">Aprovação de Frete</th>
                    </tr>
                    <tr>
                        <td>
                            <tr>
                                <td style="width:15px;">&nbsp;</td>
                                <td style='font-size: 12px;'>
                                    <b>Nº Volume:</b>
                                    <?=$valores_frete->volume?>
                                </td>
                            </tr>
<?php
    foreach ($valores_frete->valor_frete as $valores) {
?>
                            <tr style='font-size: 10px;'>
                                <td style="width:15px;">&nbsp;</td>
                                <td>
                                    <input type="radio" name="valor_frete" id="valor_frete" value="<?=$valores->valor?>|<?=$valores->descricao?>" />
                                    <?=$valores->descricao?>:

                                    R$<?=number_format($valores->valor,2,',','.')?>
                                </td>
                            </tr>
<?php
    }
?>
                            <tr>
                                <td colspan="2" style="text-align:center;">
                                    <button name="aprovar_frete" id="aprovar_frete">Aprovar Frete</button>
                                </td>
                            </tr>
                        </td>
                    </tr>
                </table>
<?php
                }
                $sql = "SELECT banco, agencia, conta, nomebanco, favorecido_conta, cpf_conta FROM tbl_posto_fabrica
                    INNER JOIN tbl_fabrica ON tbl_fabrica.posto_fabrica = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    where tbl_fabrica.fabrica = $login_fabrica";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res)> 0 ){
					$banco               = pg_fetch_result($res, 0, 'banco');
					$agencia             = pg_fetch_result($res, 0, 'agencia');
					$conta               = pg_fetch_result($res, 0, 'conta');
					$nomebanco           = pg_fetch_result($res, 0, 'nomebanco');
					$favorecido_conta    = pg_fetch_result($res, 0, 'favorecido_conta');
					$cpf_conta           = pg_fetch_result($res, 0, 'cpf_conta');


					if($codigo_condicao == "AV"){
?>

				<table width='100%' border='0' cellspacing='3' cellpadding='1' align='center' class='formulario' bgcolor='#D9E2EF'>
					<tr>
						<td class='menu_top' style="font-size:14px">Dados Para Deposito</td>
					</tr>
					<tr>
						<td><table width='100%'>
								<tr>
									<td style='font-size: 12px;'>
										<b>Nome:</b>
									</td>
									<td>
										<input type='text' name='dados_fabrica_nome' value='<?=$favorecido_conta?>'  readonly='true'>
									</td>
									<td style='font-size: 12px;'>
										<b>CNPJ:</b>
									</td>
									<td>
										<input type='text' name='dados_fabrica_cnpj' value='<?=$cpf_conta?>' readonly='true'>
									</td>
								</tr>
								<tr>
									<td style='font-size: 12px;'>
										<b>Ag:</b>
									</td>
									<td>
										<input type='text' name='dados_fabrica_agencia' value='<?=$agencia?>' readonly='true'>
									</td>
									<td style='font-size: 12px;'>
										<b>Conta Corrente:</b>
									</td>
									<td>
										<input type='text' name='dados_fabrica_conta_corrente' value='<?=$conta?>' readonly='true'>
									</td>
								</tr>
								<tr>
									<td style='font-size: 12px;'><b>Banco:</b></td>
									<td> <input type='text' name='dados_fabrica_banco' value='<?=$nomebanco?>' readonly='true'></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<br>
<?php
					}
				}
                $anexos = $s3->getObjectList("{$pedido}_");

                if ((count($anexos) == 0 && $login_fabrica != 168) || (count($anexos) == 0 && $codigo_condicao == "AV" && $login_fabrica == 168)) { ?>
                <form name='frm_anexo_pedido_finalizado' method='POST' action='pedido_finalizado.php' enctype="multipart/form-data">
                <table width='100%' border='0' cellspacing='3' cellpadding='1' align='center' class='formulario' bgcolor='#D9E2EF'>
                 <tr>
                        <td class='menu_top' style='font-size: 14px'>Anexo</td>
                    </tr>
                    <tr>
                        <td><table width='100%'>
                            <tr>
                                <td style='font-size: 12px;'>
                                    <b>Anexar Comprovante de Pagamento:</b>
                                </td>
                                <td>
                                    <input type='file' name='anexo' value=''>
                                </td>
                            </tr>
                            <tr>
                                <td colspan='2' align='center'>
                                     <input type='submit' name='btn_anexo' value='Gravar'>
                                     <input type='hidden' name="pedido" value='<?=$pedido?>'>
                                </td>
                            </tr>


                            </table>
                        </td>
                    </tr>
                </table>
                </form>

            <?php }
            }

            if($login_fabrica == 87) {
                    $sql = "SELECT tbl_tipo_posto.tipo_posto FROM tbl_tipo_posto
                                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                            WHERE tbl_posto_fabrica.posto = {$login_posto} AND tbl_tipo_posto.codigo !~* 'filial'";
                    $resTipoPosto = pg_query($con,$sql);

                    if(pg_num_rows($resTipoPosto) > 0){
                        ?>
                    <div id="status" class="texto_avulso"></div>
                    <?php

                        if ($tipo_pedido == "Orçamento") {
                            $validade_cond = " AND data_validade::date >= current_date";
                        } else {
                            $validade_cond = '';
                        }

                        $sql = "SELECT pedido,  
                                       valores_adicionais
                                FROM tbl_pedido
                                WHERE fabrica = $login_fabrica
                                AND pedido = $pedido
                                AND aprovado_cliente IS NULL
                                AND status_pedido = 18
                                $validade_cond;";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0) {
                            $vl = json_decode(pg_fetch_result($res, 0, 'valores_adicionais'),treu);
                            if (isset($vl['nova_condicao'])) {
                                if ($vl['nova_condicao'] == 1) {
                                    echo '<div style="background-color : #FF0000; color: #FFFAFA"; id="nova_condicao"><b>Sistema possui divergência de compensação, condição alterada para A Vista.<br>Para mais informações, entre em contato com área financeira Jacto.</b></div><br />';
                                } else {
                                    echo '<div style="background-color : #FF0000; color: #FFFAFA"; id="nova_condicao"><b>Cliente no sistema +Crédito temporariamente indisponível, condição de Compra alterada para<br>A Prazo - JACTO<br>Para mais informações, entre em contato com área financeira Jacto.</b></div><br />';
                                }
                            }
                            echo '<div id="aceite"><button type="button" id="aceitar">Aceitar</button>&nbsp;&nbsp;';
                            echo '<button type="button" id="rejeitar">' . traduz('rejeitar', $con) . '</button></div>';

                        }

                    ?>
                <?php
                }
            }

    if ($detalhar == "ok") {
        if(in_array($login_fabrica,array(11,35,95,98,99,172))){
            $join_os_item = "LEFT JOIN tbl_os_item ON  (tbl_os_item.peca = tbl_pedido_item.peca or tbl_os_item.pedido_item = tbl_pedido_item.pedido_item) AND tbl_os_item.pedido = tbl_pedido.pedido ";
        } else if($login_fabrica == 101){
            $join_os_item = "LEFT JOIN tbl_os_item ON  tbl_os_item.peca = tbl_pedido_item.peca AND tbl_os_item.pedido_item = tbl_pedido_item.pedido_item ";
        }else{
            $join_os_item = "JOIN tbl_os_item ON  tbl_os_item.peca = tbl_pedido_item.peca AND tbl_os_item.pedido = tbl_pedido.pedido";
        }

        if($login_fabrica == 99 AND strtolower($tipo_pedido) == "garantia"){
            $cond_os = " AND tbl_faturamento_item.os = tbl_os.os ";
        }
        $sql = "SELECT  distinct
                        lpad(tbl_os.sua_os::text,10,'0') ,
                        tbl_peca.peca      ,
                        tbl_peca.referencia,
                        tbl_peca.referencia_fabrica   AS peca_referencia_fabrica,
                        tbl_peca.descricao ,
                        tbl_os.os          ,
                        tbl_os.sua_os      ,
                        tbl_os.revenda_nome,
                        tbl_faturamento.nota_fiscal,
                        tbl_os.nota_fiscal_saida,
                        TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida,
                        tbl_os.consumidor_revenda,
                        tbl_pedido_item.pedido_item,
                        tbl_pedido_item.qtde,
                        tbl_faturamento_item.qtde AS qtde_faturada,
                        TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                        tbl_pedido_item.qtde_cancelada,
                        tbl_faturamento.transp,
                        tbl_os_item.os_item,
                        tbl_os_produto.produto,
                        tbl_os.consumidor_nome,
                        tbl_faturamento.previsao_chegada
                FROM    tbl_pedido
                JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
                JOIN    tbl_peca ON  tbl_peca.peca              = tbl_pedido_item.peca
                $join_os_item
                LEFT JOIN tbl_os_produto ON  tbl_os_produto.os_produto  = tbl_os_item.os_produto
                LEFT JOIN tbl_os ON  tbl_os.os                  = tbl_os_produto.os
                LEFT JOIN tbl_os_item_nf ON  tbl_os_item.os_item        = tbl_os_item_nf.os_item
                LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item $cond_os
                LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_faturamento.fabrica = $login_fabrica
                WHERE   tbl_pedido_item.pedido = $pedido
                AND  ((tbl_os_item.pedido_item  = tbl_pedido_item.pedido_item) or tbl_os_item.pedido_item ISNULL)
                and tbl_pedido.fabrica = $login_fabrica
                ORDER BY tbl_peca.descricao";
        $res = @pg_query ($con,$sql);
        //echo nl2br($sql);
        if(in_array($login_fabrica,array(101,157,175)) && strlen($obs) > 0){
        ?>
        <script type="text/javascript">
            window.onload=function(){
                $("td").each(function(){
                    $("#sair").remove();
                });
            }
        </script>
        <table class="tabela" width="100%" cellspacing="1" cellpadding="3" border="0" align="center">
            <tr class="titulo_tabela" id="obs">
                <td colspan="100%"><?=traduz('observações', $con)?></td>
            </tr>
            <tr>
                <td colspan="100%">
                    <?php echo html_entity_decode($obs,ENT_QUOTES,'ISO8859-15');?>
                </td>
            </tr>
        </table>
        <br />

        <?
        }
        
	if (pg_num_rows($res) > 0) {
		
		$previsao_chegada = trim(pg_fetch_result($res, 0, 'previsao_chegada'));
            if (in_array($login_fabrica, [35]) AND strlen ($previsao_chegada) > 0) {
		   
                $data5 = date('Y-m-d', strtotime("+5 days",strtotime($previsao_chegada)));
		    	$data5 = new DateTime($data5);               
			    $hoje = new DateTime( date('Y-m-d'));

                echo "<div>";
                echo "<a class='btn' style='float:right; margin-left: 5px' rel='shadowbox' href='shadowbox_lista_helpdesk.php?pedido={$pedido}' value='Historico'>Histórico Reclamação</a>";
                
                if ($data5 >= $hoje) {
                    $sqlPro = "SELECT tbl_produto.descricao, 
                                    tbl_produto.referencia 
                            FROM tbl_os_produto 
                            JOIN tbl_produto
                            ON tbl_os_produto.produto = tbl_produto.produto 
                            WHERE tbl_os_produto.os = " . pg_fetch_result($res,0, os);
                    $resPro = @pg_query ($con,$sqlPro);
                    $url = "helpdesk_posto_autorizado_novo_atendimento.php";
                    $url .= "?tipo_solicitacao=reclamacao_pecas";
                    $url .= "&ordem_de_servico=" . pg_fetch_result($res,0, os);
                    $url .= "&pedido=$pedido";
                    $url .= "&produto_referencia=" . pg_fetch_result($resPro,0, referencia);
                    $url .= "&produto_descricao=" . pg_fetch_result($resPro,0, descricao);
                    $url .= "&cliente=" . pg_fetch_result($res,0, consumidor_nome);
                    echo "<a class='btn' style='float:right;' target='_blank' href='$url' value='Reclamacao'>Reclamação</a>";    
                }            
                echo "</div>";
            }
            echo "<table width='$tamanho' border='0' cellspacing='1' cellpadding='3' align='center' class='tabela'>";
            if(in_array($login_fabrica,array(11,35,95,98,99,101,172))){
                if($login_fabrica == 101){
                    echo "<div id='historicoCorreios' style='display:none;'></div>";  
                }               
                echo "<tr class='titulo_tabela'><td colspan='100%'>" . traduz('notas.fiscais.que.atenderam.a.este.pedido', $con) . "</td></tr>";
            } else {
                echo "<caption class='titulo_tabela'>" . traduz('ordens.de.servico.que.geraram.o.pedido.acima', $con) . "</caption>";
            }
            echo "<tr class='titulo_coluna'>";
            echo (strpos(strtolower($condicao),"garantia") !== false OR stripos ($tipo_pedido,"Garantia") !== false OR strpos ($tipo_pedido,"antecipada") !== false OR strpos($tipo_pedido,"Consignação") !== false) ? "<td>Sua OS</td>" : "";

            echo (( ($login_fabrica <> 3) or ($login_fabrica==3 and $login_e_distribuidor == 't') ) && !in_array($login_fabrica, array(11,172)) ) ? "<td>" . traduz('nota.fiscal', $con) . "</td>" : "";
            if(!in_array($login_fabrica,array(11,172))){
                if($login_fabrica == 3){ //HD-2890050
                    echo "<td>Nota Fiscal</td>";
                }
                if($login_fabrica != 161 || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                    if($login_fabrica == 3 || $login_fabrica == 101){
                        echo "<td>Emissão</td>";
                    }else{
                        echo "<td>" . traduz('data.nf', $con) . "</td>";
                    }
                }
                if($login_fabrica == 157){
                    echo "<td>Transportadora</td>";
                }
                if(!$login_fabrica == 125){
                    echo "<td>Qtde</td>";
                }
            }
            //Gustavo 12/12/2007 HD 9590
            echo (in_array($login_fabrica, array(11,35,45,74,80,86,147,151,172))) ? "<td>Conhecimento</td>" : "";
            echo (in_array($login_fabrica, array(175))) ? "<td>Código de Rastreio</td>" : "";
            echo ( in_array($login_fabrica, array(11,172)) ) ? "<td>Revenda</td>" : "";
            echo "<td>Peça</td>";
            if($login_fabrica == 3){ //HD-2890050
                echo "<td>Qtde</td>";
            }
            if ( in_array($login_fabrica, array(11,172)) ) {
                echo "<td>Nota Fiscal</td>";
                echo "<td>Data</td>";
            }
            if ($login_fabrica == 101){
                echo "<td>Rastreio Transporte</td>";
                echo "<td>Saída</td>";
                echo "<td>Previsão De Chegada</td>";

            }
            echo "</tr>";

            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

                $peca               = pg_fetch_result($res,      $i, peca);
                $os                 = pg_fetch_result($res,      $i, os);
                $nota_fiscal_saida  = pg_fetch_result($res,      $i, nota_fiscal_saida);  // hd_chamado=2788473
                $consumidor_revenda = pg_fetch_result($res,      $i, consumidor_revenda); // hd_chamado=2788473
                $data_nf_saida      = pg_fetch_result($res,      $i, data_nf_saida);      // hd_chamado=2788473
                $sua_os             = pg_fetch_result($res,      $i, sua_os);
                $revenda_nome       = trim(pg_fetch_result($res, $i, revenda_nome));

                $peca_referencia_fabrica = "";
                if ($login_fabrica == 171) {
                    $peca_referencia_fabrica = " / " . pg_fetch_result ($res,$i,'peca_referencia_fabrica');
                }

                $peca_descricao = pg_fetch_result ($res,$i,referencia) . $peca_referencia_fabrica  . " - " . pg_fetch_result ($res,$i,descricao);

		$nota_fiscal    = trim(pg_fetch_result ($res,$i,nota_fiscal));

                $data_nf     = trim(pg_fetch_result($res,$i,emissao));
                $os_item     = trim(pg_fetch_result ($res,$i,os_item));
                $ped_item    = trim(pg_fetch_result ($res,$i,pedido_item));
                $qtde        = trim(pg_fetch_result ($res,$i,qtde));
                $qtde_fat    = trim(pg_fetch_result ($res,$i,qtde_faturada));
                $transp    = trim(pg_fetch_result ($res,$i,transp));
                $qtde_cancel = trim(pg_fetch_result ($res,$i,qtde_cancelada));
                $qtde_item   = $qtde_item + $i;
                //Da maneira que está o SELECT não serve para a Cadence HD17547
                if($login_fabrica <> 35){
                    if($telecontrol_distrib){
                        $sql ="SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
										tbl_faturamento.faturamento                      ,
										TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
										tbl_faturamento.conhecimento
                        FROM    tbl_faturamento
                        JOIN    tbl_faturamento_item USING (faturamento)
                        WHERE   tbl_faturamento.fabrica in( $login_fabrica,10)
                        AND     (tbl_faturamento.pedido    = $pedido OR tbl_faturamento_item.pedido=$pedido)
                        AND     (tbl_faturamento_item.peca = $peca or tbl_faturamento_item.pedido_item = $ped_item)";
                        if($login_fabrica <> 10 and !empty($os_item)) {
                            $sql .= " AND     (tbl_faturamento_item.os_item = $os_item) ";
                        }
                        $sql .= "ORDER BY trim(tbl_faturamento.nota_fiscal) ASC;";

                        $resx = pg_query ($con,$sql);

                        if (pg_num_rows ($resx) == 0) {

                            $sql = "SELECT tbl_faturamento.faturamento,
                                            tbl_faturamento.nota_fiscal ,
                                            TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                                            tbl_faturamento.conhecimento
                                    FROM tbl_faturamento
                                    JOIN tbl_faturamento_item USING (faturamento)
                                    WHERE tbl_faturamento.posto = 4311
                                    AND   tbl_faturamento_item.pedido = $pedido";
                            if($login_fabrica <> 10) {
                                $sql .= " AND     (tbl_faturamento_item.os_item = $os_item ) ";
                            }
                            $sql .= "AND   tbl_faturamento_item.peca   = $peca";
                            $resx = pg_query ($con,$sql);

                            if (pg_num_rows ($resx) == 0) {
                                $sql = "SELECT  tbl_faturamento.nota_fiscal,
                                                to_char(emissao,'DD/MM/YYYY') AS emissao
                                        FROM    tbl_faturamento_item
                                        JOIN    tbl_faturamento USING(faturamento)
                                        WHERE   tbl_faturamento_item.peca   = $peca
                                        AND     tbl_faturamento.fabrica     IN (10,$login_fabrica)
										and (tbl_faturamento_item.pedido = $pedido or tbl_faturamento_item.pedido isnull)
                                        AND     tbl_faturamento_item.os     = $os
                                ";

                                $resx = pg_query ($con,$sql);
                            }

                        }

                    } else if (!in_array($login_fabrica, array(59,65))) {
                        $sql = "SELECT DISTINCT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
                                    TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                                    tbl_faturamento.conhecimento,
                                    tbl_faturamento.faturamento
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento.fabrica = $login_fabrica
                                AND     tbl_faturamento_item.pedido=$pedido
                                AND     tbl_faturamento_item.peca = $peca
                                ";
                        if ($login_fabrica <> 104)  $sql .="AND (tbl_faturamento_item.pedido_item = $ped_item or tbl_faturamento_item.pedido_item isnull) ";
                        if($login_fabrica == 51 || (in_array($login_fabrica,array(101,121)) && strtoupper($tipo_pedido) == "GARANTIA")) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
                        if($login_fabrica == 99) $sql .= " GROUP BY tbl_faturamento.nota_fiscal,tbl_faturamento.emissao,tbl_faturamento.conhecimento ,tbl_faturamento.faturamento";
                        if($login_fabrica != 99) $sql .=" ORDER BY trim(tbl_faturamento.nota_fiscal) ASC; ";
                        $resx = pg_query($con,$sql);

                        /*HD: 126280 - antes tinha OR para pedido, custo menor dividir em 2 SQL */
                        if(pg_num_rows($resx) ==0){
                            $sql = "SELECT DISTINCT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
                            TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                                        tbl_faturamento.conhecimento
                                    FROM    tbl_faturamento
                                    JOIN    tbl_faturamento_item USING (faturamento)
                                    WHERE   tbl_faturamento.fabrica   = $login_fabrica
                                    AND     tbl_faturamento.pedido    = $pedido
                                    AND     tbl_faturamento_item.peca = $peca ";
							if($login_fabrica == 51 || (in_array($login_fabrica,array(101,121)) && strtoupper($tipo_pedido) == "GARANTIA")) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
                            if($login_fabrica == 99 or $login_fabrica == 127 ) $sql.=" AND     tbl_faturamento_item.pedido_item = $ped_item ";
                            if($login_fabrica == 99) $sql .= " GROUP BY tbl_faturamento.nota_fiscal,tbl_faturamento.emissao,tbl_faturamento.conhecimento ";
                            //$sql .=" ORDER BY lpad(tbl_faturamento.nota_fiscal::text,20,'0') ASC; ";
                            $resx = @pg_query ($con,$sql);

                        }

                    }else{
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
                                tbl_faturamento.conhecimento,
                                tbl_faturamento.faturamento
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento.pedido    = $pedido
                                AND     tbl_faturamento_item.peca = $peca;";
                        $resx = @pg_query ($con,$sql);
                    }
                }else{
                    $sql  = "SELECT DISTINCT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
                            TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                            tbl_faturamento.conhecimento,
                            tbl_faturamento.faturamento
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            WHERE   tbl_faturamento_item.pedido    = $pedido
                            AND     tbl_faturamento_item.peca      = $peca
                            AND     tbl_faturamento.fabrica        = $login_fabrica
                            AND     tbl_faturamento.posto          = $login_posto
                            ;";
                    $resx = @pg_query ($con,$sql);
                }

                if($login_posto==4311){ // HD 20787
                    $sql = "SELECT DISTINCT trim(tbl_faturamento.nota_fiscal) As nota_fiscal,
                            tbl_faturamento.conhecimento
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            WHERE tbl_faturamento.posto = 4311
                            AND   tbl_faturamento_item.pedido = $pedido
                            AND   tbl_faturamento_item.peca   = $peca
                            ";
                    $resx = @pg_query($con,$sql);
                }

                if (strlen($nota_fiscal) == 0) {
                    if (pg_num_rows ($resx) > 0) {

                        $xnf = "";
                        for ($x=0; $x < pg_num_rows($resx); $x++) {
                            $nf   = trim(pg_fetch_result($resx,$x,nota_fiscal));

                            //Gustavo 12/12/2007 HD 9590
                            if(in_array($login_fabrica, array(35,147))){
                                $conhecimento   = trim(pg_fetch_result($resx,$x,conhecimento));
                            }
                            $data_nf = trim(pg_fetch_result($resx,$x,emissao));
                            if(!($login_fabrica == 125 && $telecontrol_distrib)){
                                $linx[$x] = "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a><br>";
                            }else{
                                $linx[$x] = $nf;
                            }
                        }
                        $link = 1;
                        $qtde_link = $x;

                    }else{

                        if (!in_array($login_fabrica,array(11,172))) {
                            if(($qtde - ($qtde_fat + $qtde_cancel)) > 0){
                                $nf = "Pendente";
                            }
                            else if($qtde_cancel == $qtde){
                                $nf = "<span style='color:#FF0000;'>Cancelada</span>";
                                $nf_link = "Cancelada";
                            }
                            if ($login_fabrica == 101) {

                                if(strlen(trim($os))>0){
                                    $pedido_cancelado_os = " and tbl_pedido_cancelado.os = $os ";
                                }

                                $sqlQtdeCancelada = "SELECT (tbl_os_item.qtde - tbl_pedido_cancelado.qtde) AS qtde
                                        FROM tbl_pedido_cancelado
                                        JOIN tbl_os_produto ON tbl_os_produto.os = tbl_pedido_cancelado.os
                                        JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                        WHERE
                                         tbl_pedido_cancelado.peca = $peca
                                          and tbl_pedido_cancelado.pedido = $pedido
                                        AND tbl_pedido_cancelado.fabrica = $login_fabrica";
                                $resQtdeCancelada = pg_query($con, $sqlQtdeCancelada);

                                if (pg_num_rows($resQtdeCancelada) > 0 && pg_fetch_result($resQtdeCancelada, 0, "qtde") == 0) {
                                    $nf = "<span style='color:#FF0000;'>Cancelada</span>";
                                } else {
                                    $nf = "pendente";
                                }
                            }

                            if ($login_fabrica == 52) {
                                $sqlQtdeCancelada = "SELECT (tbl_os_item.qtde - tbl_pedido_cancelado.qtde) AS qtde
                                        FROM tbl_pedido_cancelado
                                        JOIN tbl_os_produto ON tbl_os_produto.os = tbl_pedido_cancelado.os
                                        JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                        WHERE tbl_pedido_cancelado.os = $os
                                        AND tbl_pedido_cancelado.peca = $peca
                                        AND tbl_pedido_cancelado.fabrica = $login_fabrica";
                                $resQtdeCancelada = pg_query($con, $sqlQtdeCancelada);

                                if (pg_num_rows($resQtdeCancelada) > 0 && pg_fetch_result($resQtdeCancelada, 0, "qtde") == 0) {
                                    $nf = "Cancelado";
                                }
                            }
                        } else {
                            ///////////////////////////////////coloquei esta rotina porque na rotina abaixo mostra o pedido cancelado. 12/7 Samuel

                            if (!empty($os) and is_int($os)) {

                                $sqlpc =    "SELECT tbl_peca.referencia  ,
                                        tbl_peca.descricao          ,
                                        tbl_pedido_cancelado.qtde   ,
                                        tbl_pedido_cancelado.motivo ,
                                        to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data ,
                                        tbl_os.sua_os
                                FROM tbl_pedido_cancelado
                                JOIN tbl_peca USING (peca)
                                LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
                                WHERE tbl_pedido_cancelado.pedido  = $pedido
                                AND   tbl_pedido_cancelado.fabrica = $login_fabrica
                                AND   tbl_pedido_cancelado.peca = $peca
                                AND   tbl_pedido_cancelado.os = $os";

                                $respc = pg_query($con,$sqlpc);
                                $nf = (pg_num_rows($respc) > 0) ? "Cancelada" : "Pendente";

                            }

                            $link = 0;
                            $qtde_link = 0;

                        }
                    }
                }else{
                    if(in_array($login_fabrica, array(11,35,45,74,80,86,147,151,172,175))){
                                $conhecimento   = trim(pg_fetch_result($resx,$x,conhecimento));
                                $faturamento    = trim(pg_fetch_result($resx,$x,faturamento));
                    }
                    if ($login_fabrica == 127 ){
                        $sql = "SELECT excluida from tbl_os where os = $os";
                        $res_os = pg_query($con,$sql);
                        $cancelada = pg_fetch_result($res_os, 0,"excluida");
                        if ($cancelada == "true" and ($qtde_cancel == $qtde)){
                            $nf = "<span style='color:#FF0000;'>Cancelada</span>";
                        }else{
                            $nf = $nota_fiscal;
                            $link = 1;
                            $qtde_link = 0;
                        }
                    }else{
                        $nf = $nota_fiscal;
                        $link = 1;
                        $qtde_link = 0;
                    }

                }

                if (strlen($sua_os) == 0) $sua_os = $os;

                echo "<tr bgcolor='$cor'>";
                if (strpos(strtolower($condicao),"garantia") !== false OR stripos ($tipo_pedido,"Garantia") !== false OR strpos ($tipo_pedido,"antecipada") !== false OR strpos($tipo_pedido,"Consignação") !== false) {
                    echo "<td align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
                }

                if ( ( ($login_fabrica <> 3) or ($login_fabrica==3 and $login_e_distribuidor == 't')) && !in_array($login_fabrica, array(11,172)) ) {
                    echo "<td align='center'>";
                    if (strtolower($nf) <> 'pendente'){
                        if ($link == 1) {
                            if ($qtde_link > 0) {
                                for ($x=0; $x < $qtde_link; $x++) {
                                    $link = $linx[$x];
                                    echo $link;
                                }
                            }else{
                                if ($login_fabrica==3 and $login_e_distribuidor == 't') {
                                    echo $nf;
                                } else {

                                    if($login_fabrica == 42){
                                        echo "<span style='cursor:pointer' onclick='linkLupeon(\"$nf\", \"$cnpj\")'>$nf</a>";
                                    }else{
                                        echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=".((strpos($nf, "Cancelada")) ? $nf_link : $nf)."&peca=$peca' target='_blank'>{$nf}</a>";
                                    }
                                }
                            }
                        }else{
                            echo $nf;
                        }
                    }else{
                        #hd 212245

                        if ($login_fabrica == 14) {

                            $sqldata = "SELECT CASE WHEN '$pedido_data2' < '2009-08-27' THEN 'sim' ELSE 'nao' END";
                            $resdata = pg_query($con,$sqldata);

                            $resposta = pg_fetch_result($resdata,0,0);

                            if ($resposta == 'sim') {
                                $nf = '';
                            }
                        }

                        if($login_fabrica == 81 AND $login_posto == 20682 AND $consumidor_revenda == "R" AND strlen($nota_fiscal_saida) > 0 AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
                            echo "$nota_fiscal_saida &nbsp;";
                        }else{

                            if (!empty($os) && $telecontrol_distrib && strtolower($nf) == 'pendente') {
                                $sqlNT = "SELECT nota_fiscal_saida, 
                                                 TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
                                          FROM tbl_os
                                          WHERE os = $os 
                                          AND fabrica = $login_fabrica
                                          AND nota_fiscal_saida NOTNULL
                                          AND data_nf_saida NOTNULL ";
                                $resNT = pg_query($con, $sqlNT);
                                if (pg_num_rows($resNT) > 0) {
                                    $nf = pg_fetch_result($resNT, 0, 'nota_fiscal_saida');
                                    $data_nf = pg_fetch_result($resNT, 0, 'data_nf_saida');
                                }
                            }

                            echo "$nf &nbsp;";
                        }

                    }
                    echo "</td>";
                }

                if(!in_array($login_fabrica,array(11,172))){
                    if($login_fabrica == 3){ //HD-2890050
                        echo "<td>$nf</td>";
                    }
                    if($login_fabrica != 161 || (regiao_suframa() == false && in_array($login_fabrica, array(161)))){
                        echo "<td align='center'>".$data_nf."</td>";
                    }
                    if($login_fabrica == 157){
                        echo "<td align='center'>".$transp."</td>";
                    }

                    if($login_fabrica == 81 AND $login_posto == 20682 AND $consumidor_revenda == "R" AND strlen($nota_fiscal_saida) > 0 AND strtolower($nf) == 'pendente'){ //hd_chamado=2788473
                        echo "<td align='center'>".$data_nf_saida."</td>";
					}

                }

                  if(!$login_fabrica == 125){
                       echo "<td align='center'>$qtde_fat</td>";
                  }

                if (in_array($login_fabrica, array(11,35,45,74,80,86,147,151,172,175))) {
                    echo "<TD style='text-align:CENTER;'";


                    if (strlen($faturamento)>0){
                        $sql_verifica_conhecimento = "SELECT conhecimento AS conhecimento FROM tbl_faturamento_correio
                                                                        WHERE fabrica = $login_fabrica and faturamento = $faturamento";
                        $res_verifica_conhecimento = pg_query($con, $sql_verifica_conhecimento);

                        //Gustavo 12/12/2007 HD 9590
                        if (in_array($login_fabrica, array(11,35,45,74,80,86,172))) {
                            echo " class='conteudo'>";

			    if(strpos($conhecimento,"http") !== false){
				    echo "<A HREF='$conhecimento' target = '_blank'>Rastreio Pedido</A>";
			    }else if(pg_num_rows($res_verifica_conhecimento)>0){
                                echo "<A HREF='./relatorio_faturamento_correios?conhecimento=$conhecimento' rel='shadowbox'>{$conhecimento}</A>";
                            }else{
                                echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>{$conhecimento}</A>";
                            }
                        }

                        if (in_array($login_fabrica, array(147,151,175))) {
                            echo " nowrap>";

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
                        }
                    } else {
                        echo ">";
                    }
                    echo "</TD>";
                }

                if ( !in_array($login_fabrica, array(11,172)) ) echo "<td class='esq'>$peca_descricao</td>";

                if ($login_fabrica == 101){
                    if(strpos($faturamento_conhecimento,"http") !== false){
                        $rastreio_longhi = "<A HREF='' target = '_blank'>Rastreio Pedido</A>";
                    }else if(pg_num_rows($res_verifica_conhecimento)>0){
                        $rastreio_longhi = "<A HREF='./relatorio_faturamento_correios?conhecimento=$faturamento_conhecimento' rel='shadowbox'>{$faturamento_conhecimento}</A>";
                        }else{
                            $rastreio_longhi = "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$faturamento_conhecimento' target = '_blank'>{$faturamento_conhecimento}</A>";
                        }

                echo "<td align='center'>".$rastreio_longhi."</td>";
                echo "<td align='center'>".$faturamento_saida."</td>";
                echo "<td align='center'>".$faturamento_previsao_chegada."</td>";
                
                }

                if($login_fabrica == 3){ //HD-2890050
                    echo "<td align='center'>$qtde</td>";
                }

                if ( in_array($login_fabrica, array(11,172)) ) {
                    echo "<td class='esq'>$revenda_nome</td>";
                    echo "<td class='esq'>$peca_descricao</td>";
                    //-----------------------------------------------------------
                    $documento = "";
                    $data_documento = "";
                    $sql = "SELECT tbl_faturamento.nota_fiscal ,
                            to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
                    FROM    tbl_faturamento_item
                    JOIN    tbl_faturamento USING (faturamento)
                    JOIN    tbl_peca USING (peca)
                    JOIN    tbl_pedido_item ON tbl_pedido_item.peca = tbl_faturamento_item.peca AND
                            tbl_pedido_item.pedido = tbl_faturamento_item.pedido
                    WHERE   tbl_faturamento.fabrica = $login_fabrica
                    AND     tbl_faturamento_item.pedido = $pedido
                    AND     tbl_faturamento_item.peca   = $peca
                    ORDER BY  tbl_faturamento.nota_fiscal , tbl_peca.referencia";
                    $resx = @pg_query ($con,$sql);
                    if(pg_num_rows($resx)>0) {
                        $documento      = trim (pg_fetch_result ($resx,$z,nota_fiscal));
                        $data_documento = trim (pg_fetch_result ($resx,$z,emissao));
                    }
                    if(strlen($documento)>0 AND strlen($data_documento)>0){
                        echo "<td class='center'>";
                        echo $documento;
                        echo "</td>";
                        echo "<td class='center'>";
                        echo $data_documento;
                        echo "</td>";
                    }else{
                        //HIDDEN
                        echo "<form method='post' name='frm_documento_$i' action=$PHP_SELF>";
                        echo "<td class='center'>";
                        echo "<input type='hidden' name='pedido_$i' value='$pedido'>";
                        echo "<input type='hidden' name='os_$i'   value='$os'>";
                        echo "<input type='hidden' name='peca_$i'   value='$peca'>";
                        echo "<input type='text' name='documento_$i' size='9' value='$documento' align='right' maxlength='8'>";
                        echo "</td>";
                        echo "<td class='center'>";
                        echo "<input type='text' name='data_documento_$i' value='$data_documento' align='right' size='11' maxlength='10'>";
                        echo "</td></form>";
                    }
                }
				echo "</tr>";
            }
            echo "</table>";

        }
	}
    if($login_fabrica == 15) { # HD 126026
        echo "<br />";
        $sql = "SELECT DISTINCT
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    sum(qtde) as qtde
            FROM tbl_pedido
            JOIN tbl_pedido_item USING(pedido)
            JOIN tbl_peca        USING(peca)
            WHERE tbl_pedido.pedido = $pedido
            GROUP BY tbl_peca.referencia,
                    tbl_peca.descricao
            ORDER BY tbl_peca.descricao,
                    tbl_peca.referencia ;";
        $res2 = pg_query ($con,$sql);
        if (pg_num_rows($res2) > 0) {
            echo "<table width='750' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
            echo "<thead>";
            echo "<tr class='menu_top'>";
            echo "<td>PEÇA</td>";
            echo "<td>QTDE</td>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
                $referencia   = pg_fetch_result ($res2,$i,referencia);
                $descricao    = pg_fetch_result ($res2,$i,descricao);
                $qtde         = pg_fetch_result ($res2,$i,qtde);
                $cor = ($i % 2 == 0) ? "#FFFFFF": "#F1F4FA";

                echo "<tr style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal; background-color:$cor' >";
                echo "<td align='left'>$referencia - $descricao</td>";
                echo "<td align='center'>$qtde</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "<br />";
        }
    }
    ?>
    </td>
    <td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<?php if($login_fabrica == 87) {
// Texto JACTO
        $jacto_cgv = array(
        'titulo' => array(
            'pt-br' => 'Condições Gerais de Venda',
            'es'    => 'Condiciones Generales de Venta',
            'en-us' => 'Global Sales Terms',
        ),
        'item_1' => array(
            'pt-br' => 'Este pedido está sujeito a confirmação por parte da fábrica e não vale como recibo;',
            'es'    => 'Este pedido está sujeto a la confirmación por parte de la fábrica y no es válido como recibo.',
            'en-us' => '',
        ),
        'item_2' => array(
            'pt-br' => 'Os preços constantes deste pedido são com frete incluso para entrega ao contratante e estão sujeitos a acréscimo de impostos e encargos financeiros para vendas a prazo;',
            'es'    => 'Los precios incluidos en esta aplicación se incluye con el envío para la entrega al contratista y están sujetos a la adición de los impuestos y las cargas financieras para las ventas a crédito;',
            'en-us' => '',
        ),
        'item_3' => array(
            'pt-br' => 'Os preços estão sujeitos a reajustes sem aviso prévio;',
            'es'    => 'Los precios están sujetos a ajustes sin previo aviso.',
            'en-us' => '',
        ),
        'item_4' => array(
            'pt-br' => 'Para pedidos, obedecer a quantidade mínima por embalagem indicada ou seus múltiplos;',
            'es'    => 'Para pedidos, obedecer a la cantidad mínima indicada por contenedor o múltiplos.',
            'en-us' => '',
        ),
        'item_5' => array(
            'pt-br' => 'O status SIM/NÃO da disponibilidade é apenas informativo, não garante a disponibilidade do item no ato do aceite do pedido.',
            'es'    => 'El estado ON/OFF de la disponibilidad es sólo informativo, no garantiza la disponibilidad del artículo en el momento de la aceptación del pedido.',
            'en-us' => '',
        ),
        'item_6' => array(
            'pt-br' => 'Esta cotação tem validade de 30 dias a partir da sua data de criação. Após esse período, será cancelada.',
            'es'    => 'Esta cotización es válida por 30 días a partir de la fecha de su creación. Después de este período, será cancelada.',
            'en-us' => '',
        ),

    );

    echo "<tr>";
        echo "<td height='50' align='center' colspan='3'>";
            echo "<div class='texto_avulso' style='display: block;'>";
            echo "<p style='padding: 5px; margin: 0;  font-size: 12px; color: #000; text-align: left;'><b>{$jacto_cgv['titulo'][$cook_idioma]}</b></p>";
                echo "<div class='condicao_venda'>
                        <ol type='I'>
                            <li>{$jacto_cgv['item_1'][$cook_idioma]}</li>
                            <li>{$jacto_cgv['item_2'][$cook_idioma]}</li>
                            <li>{$jacto_cgv['item_3'][$cook_idioma]}</li>
                            <li>{$jacto_cgv['item_4'][$cook_idioma]}</li>
                            <li>{$jacto_cgv['item_5'][$cook_idioma]}</li>
                            <li>{$jacto_cgv['item_6'][$cook_idioma]}</li>
                        </ol>
                    </div>";
            echo "</div>";
        echo "</td>";
    echo "</tr>";
}


if ($login_fabrica == 138) {
    $sql = "SELECT tbl_pedido.status_pedido, tbl_tipo_pedido.pedido_faturado, UPPER(tbl_condicao.descricao) AS condicao
            FROM tbl_pedido
            INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
            INNER JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = $login_fabrica
            WHERE tbl_pedido.fabrica = {$login_fabrica}
            AND tbl_pedido.posto = {$login_posto}
            AND tbl_pedido.pedido = {$pedido}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $status_pedido      = pg_fetch_result($res, 0, "status_pedido");
        $pedido_faturado    = pg_fetch_result($res, 0, "pedido_faturado");
        $condicao           = pg_fetch_result($res, 0, "condicao");

        if (in_array($status_pedido, array(23, 17)) && $pedido_faturado == "t") {
        ?>
            <tr>
                <td colspan="100%" align="center">
                    <form method="post" enctype="multipart/form-data" >
                         <table style="table-layout:fixed" bgcolor="">
                                <input type="hidden" name="condicao_pedido" value="<?=$condicao?>" />
                            <?php
                                if($condicao == "ANTECIPADO"){
                                    $disabled = "disabled";
                            ?>
                            <tr>
                                <td valign="top">
                                    Comprovante de pagamento:<input type="file" name="comprovante_pagamento" onchange="$('input[name=aprovar_pedido]').removeAttr('disabled');" style="width: 200px;" /> <br />
                                </td>
                            <?php
                                }
                            ?>
                                <td  valign="top">
                                  Cópia do Pedido:<input type="file" name="copia_pedido" onchange="$('input[name=aprovar_pedido]').removeAttr('disabled');" style="width: 200px;" /> <br /> <br />
                                </td>
                            </tr>
                            <tr>
                                <td align="center" colspan="2">
                                <input type="submit" style="background-color:green; color:#000 " name="aprovar_pedido" value="Aprovar Pedido" <?=$disabled?> />
                                <input type="submit" style="background-color:red ;color:#000  " name="cancelar_pedido" value="Cancelar Pedido" />
                                </td>
                            </tr>
                         </table>
                    </form>
                </td>
            </tr>
        <?php
        } else {
            $comprovante_pagamento = $s3->getObjectList("{$login_fabrica}_{$login_posto}_{$pedido}");
            $comprovante_pagamento = basename($comprovante_pagamento[0]);

            $ext = strtolower(preg_replace("/.+\./", "", basename($comprovante_pagamento)));

            if (!in_array($ext, array("pdf", "doc", "docx"))) {
                $comprovante_pagamento_thumb = $s3->getObjectList("thumb_{$login_fabrica}_{$login_posto}_{$pedido}");
                $comprovante_pagamento_thumb = basename($comprovante_pagamento_thumb[0]);

                $comprovante_pagamento_thumb = $s3->getLink($comprovante_pagamento_thumb);
            }

            $comprovante_pagamento = $s3->getLink($comprovante_pagamento);

            if (!empty($comprovante_pagamento)) {

                if (!in_array($ext, array("pdf", "doc", "docx"))) {
                   echo "
                    <tr>
                        <td>
                            <script src='js/FancyZoom.js'></script>
                            <script src='js/FancyZoomHTML.js'></script>
                            <script>
                                $(function(){
                                    setupZoom();
                                });
                            </script>
                            <table style='margin: 0 auto; width: 700px;'>
                                <tbody>
                                    <tr>
                                        <td class='titulo_tabela'>Comprovante de Pagamento</td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center;' >
                                            <a href='{$comprovante_pagamento}' ><img src='$comprovante_pagamento_thumb' /></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    ";
                }

                echo "
                <tr>
                    <td colspan='100%' style='text-align: center;'>
                        <a href='$comprovante_pagamento' target='_blank'>Clique aqui para fazer o download do comprovante de pagamento</a>
                    </td>
                </tr>
                ";
            }
            // $s3->upload("copia_{$login_fabrica}_{$login_posto}_{$pedido}", $copia_pedido);
             $copia_pedido = $s3->getObjectList("copia_{$login_fabrica}_{$login_posto}_{$pedido}");
            $copia_pedido = basename($copia_pedido[0]);

            $ext = strtolower(preg_replace("/.+\./", "", basename($copia_pedido)));

            if (!in_array($ext, array("pdf", "doc", "docx"))) {
                $copia_pedido_thumb = $s3->getObjectList("thumb_copia_{$login_fabrica}_{$login_posto}_{$pedido}");
                $copia_pedido_thumb = basename($copia_pedido_thumb[0]);

                $copia_pedido_thumb = $s3->getLink($copia_pedido_thumb);
            }

            $copia_pedido = $s3->getLink($copia_pedido);

            if (!empty($copia_pedido)) {

                if (!in_array($ext, array("pdf", "doc", "docx"))) {
                   echo "
                    <tr>
                        <td>
                            <script src='js/FancyZoom.js'></script>
                            <script src='js/FancyZoomHTML.js'></script>
                            <script>
                                $(function(){
                                    setupZoom();
                                });
                            </script>
                            <table style='margin: 0 auto; width: 700px;'>
                                <tbody>
                                    <tr>
                                        <td class='titulo_tabela'>Cópia do pedido</td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center;' >
                                            <a href='{$copia_pedido}' ><img src='$copia_pedido_thumb' /></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    ";
                }

                echo "
                <tr>
                    <td colspan='100%' style='text-align: center;'>
                        <a href='$copia_pedido' target='_blank'>Clique aqui para fazer o download da cópia do comprovante de pagamento</a>
                    </td>
                </tr>
                ";
            }
        }
    }
}

if (isset($telaPedido0315)) {

    $anexo      = \Posvenda\Regras::get("anexo", "pedido_venda", $login_fabrica);
    $anexo_qtde = \Posvenda\Regras::get("anexo_qtde", "pedido_venda", $login_fabrica);

    if ($anexo == true) {
         $anexos = $s3->getObjectList("{$pedido}_");
        ?>
            <tr>
                <td colspan="100%" >
                    <script src="plugins/jquery.form.js" ></script>
                    <script>

                    $(function() {
                        $("button[name=submitForm]").click(function() {
                            var form   = $(this).parent("form");

                            $(form).find("input[name=arquivoS3]").click();
                        });

                        $("input[name=arquivoS3]").change(function() {
                            if ($(this).val().length > 0) {
                                var form   = $(this).parent("form");

                                $(form).find("img.anexoS3").hide();
                                $(form).find("img.anexoLoading").show();
                                $(form).find("button").hide();
                                $(form).submit();
                            }
                        });

                        $("form.anexoS3Form").each(function() {
                            $(this).ajaxForm({
                                complete: function(data) {
                                    data = $.parseJSON(data.responseText);

                                    var form = $("form[name=anexo_pedido_"+data.posicao+"]");

                                    if (data.error) {
                                        alert(data.error);
                                        $(form).find("button").show();
                                    } else {
                                        $(form).find("input[name=anexoS3]").val(data.anexoS3);
                                        $(form).find("img.anexoS3").attr({ src: data.thumbImagemS3 });
                                        $(form).find("a.linkAnexoS3").attr({ href: data.imagemS3 });
                                        $(form).find("button").remove();
                                    }

                                    $(form).find("img.anexoLoading").hide();
                                    $(form).find("img.anexoS3").show();
                                }
                            });
                        });
                    });

                    </script>
                    <table class="tabela" style="margin: 0 auto; width: 100%;" >
                        <tr>
                            <th class="titulo_coluna" colspan="100%">Comprovante de pagamento</th>
                        </tr>
                        <tr>
                            <?php
                            for ($i = 0; $i < $anexo_qtde; $i++) {
                                unset($anexoS3, $acaoS3);

                                $anexo = $s3->getObjectList("{$pedido}_{$i}.");

                                if (count($anexo) > 0) {
                                    $anexoS3  = basename($anexo[0]);
                                    $imagemS3 = $s3->getLink($anexoS3);
                                    $ext      = strtolower(preg_replace("/.+\./", "", $anexoS3));

                                    switch ($ext) {
                                        case "pdf":
                                            $thumbImagemS3 = "imagens/pdf_icone.png";
                                            break;

                                        case "doc":
                                        case "docx":
                                            $thumbImagemS3 = "imagens/docx_icone.png";
                                            break;

                                        default:
                                            $thumbImagemS3 = $s3->getLink("thumb_".$anexoS3);
                                            break;
                                    }
                                } else {
                                    $acaoS3        = "Anexar";
                                    $imagemS3      = "#";
                                    $thumbImagemS3 = "imagens/imagem_upload.png";
                                }
                                ?>
                                <td>
                                    <form name="anexo_pedido_<?=$i?>" class="anexoS3Form" method="post" enctype="multipart/form-data" style="text-align: center;" >
                                        <a class="linkAnexoS3" href="<?=$imagemS3?>" target="_blank" >
                                            <img class="anexoS3" src="<?=$thumbImagemS3?>" style="width: 100px; height: 90px;" />
                                        </a>

                                        <img class="anexoLoading" src="imagens/loading_img.gif" style="width: 64px; height: 64px; display: none;" />

                                        <br />

                                        <input type="file" name="arquivoS3" value="" style="display: none;" />
                                        <?php
                                        if (isset($acaoS3)) {
                                        ?>
                                            <button type="button" name="submitForm" ><?=$acaoS3?></button>
                                            <input type="hidden" name="acaoS3" value="<?=$acaoS3?>" />
                                        <?php
                                        }
                                        ?>
                                        <input type="hidden" name="anexoS3" value="<?=$anexoS3?>" />
                                        <input type="hidden" name="posicao" value="<?=$i?>" />
                                    </form>
                                </td>
                            <?php
                            }
                            ?>
                        </tr>
                    </table>
                    <br />
                </td>
            </tr>
        <?php
    }
}

if ($login_fabrica == 143 && $status_pedido == 18) {
?>
    <tr>
        <td>
            <div style="width: 700px; background-color: #59B259; color: #FFFFFF; font-weight: bold; text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 14px; margin: 0 auto;" >
                Pedido calculado aguardando aprovação
            </div>
            <table class="tabela" style="margin: 0 auto; width: 100%;">
                <tr>
                    <th class="titulo_coluna" colspan="100%">ATUALIZAÇÃO DE VALORES</th>
                </tr>
                <tr class="titulo_coluna">
                    <th>Componente</th>
                    <th>Qtde</th>
                    <th>Valor Unitário</th>
                    <th>Valor Total</th>
                    <th>Novo Valor Unitário</th>
                    <th>Novo Valor Total</th>
                </tr>
                <?php
                $sqlItensAtualizados = "
                    SELECT
                        (tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS componente,
                        tbl_pedido_item.qtde,
                        tbl_pedido_item.preco AS valor_unitario,
                        tbl_pedido_item.total_item AS valor_total,
                        tbl_pedido_item.acrescimo_financeiro AS novo_valor_unitario,
                        tbl_pedido_item.acrescimo_tabela_base AS novo_valor_total
                    FROM tbl_pedido_item
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                    WHERE tbl_pedido_item.pedido = {$pedido};
                ";
                $resItensAtualizados = pg_query($con, $sqlItensAtualizados);

                $novo_total_pedido = 0;

                if (pg_num_rows($resItensAtualizados)) {
                    while ($item = pg_fetch_object($resItensAtualizados)) {
                        $novo_total_pedido += $item->novo_valor_total;

                        echo "
                            <tr>
                                <td>{$item->componente}</td>
                                <td style='text-align: center;' >{$item->qtde}</td>
                                <td style='text-align: right;' >".number_format($item->valor_unitario, 2, ",", ".")."</td>
                                <td style='text-align: right;' >".number_format($item->valor_total, 2, ",", ".")."</td>
                                <td style='text-align: right; font-weight: bold; color: #FF6600;' >".number_format($item->novo_valor_unitario, 2, ",", ".")."</td>
                                <td style='text-align: right; font-weight: bold; color: #FF6600;' >".number_format($item->novo_valor_total, 2, ",", ".")."</td>
                            </tr>
                        ";
                    }
                }
                ?>
                <tr>
                    <th colspan="3" class="titulo_coluna" style="text-align: right;" >Total</th>
                    <td style='text-align: right;' ><?=number_format($total_pedido, 2, ",", ".")?></td>
                    <th class="titulo_coluna" style="text-align: right;" >Novo Total</th>
                    <td style='text-align: right; font-weight: bold; color: #FF6600;' ><?=number_format($novo_total_pedido, 2, ",", ".")?></td>
                </tr>
                <tr>
                    <td colspan="100%" style="text-align: center;" >
                        <form name="frm_documento" method="post" style="display: inline;" >
                            <input type="submit" name="aprovar_pedido" value="Aprovar Pedido" style="cursor: pointer; background-color: #59B259; color: #FFFFFF; font-weight: bold; padding-top: 3px; padding-bottom: 3px; border-radius: 4px;" />
                            &nbsp;&nbsp;&nbsp;
                            <input type="submit" name="cancelar_pedido" value="Cancelar Pedido" style="cursor: pointer; background-color: #D34842; color: #FFFFFF; font-weight: bold; padding-top: 3px; padding-bottom: 3px; border-radius: 4px;" />
                        </form>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
<?php
}

if (strlen($_GET['lu_fabrica']) == 0) {?>
<tr>
    <td height="27" valign="middle" align="center" colspan="3">

        <br>
        <? if( in_array($login_fabrica, array(11,172)) && ($login_posto == 14301 OR $login_posto == 20321 OR $login_posto == 6359)){?>
        <input type='hidden' name='pedido' value='<? echo $pedido ?>'>
        <input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
        <input type="hidden" name="btn_acao" value="">
        <img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_documento.btn_acao.value == '' ) { document.frm_documento.btn_acao.value='gravar' ; document.frm_documento.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Documento e Data" border='0' style="cursor:pointer;">
        <?}?>
        &nbsp;&nbsp;
        <a href="pedido_cadastro.php"><img src='imagens/btn_lancarnovopedido.gif'></a>
        &nbsp;&nbsp;
<?  // se veio de pedido_cadastro.php, para retorno
    $link =  ($_GET['loc'] == 1) ? "pedido_cadastro.php?pedido=$pedido" : "javascript:history.back()";
?>
        <a href="<? echo $link; ?>"><img src='imagens/btn_voltar.gif'></a>&nbsp;&nbsp;
        <a href="pedido_print.php?pedido=<?=$pedido?>" target="_blank"><img src='imagens/btn_imprimir.gif'></a>
    </td>
</tr>
<?}?>
</form>
</table>

<?php
if(in_array($login_fabrica, array(163))){

    include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
    $tDocs   = new TDocs($con, $login_fabrica);

?>
    <!-- ANexo -->
    <table align="center">
        <tr>
            <td align="center">
                <?php

                $fabrica_qtde_anexos = 1;

                if ($fabrica_qtde_anexos > 0) {

                    echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

                    $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;

                    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                        unset($anexo_link);

                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3          = false;
                        $anexo             = "";

                        if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {
                                    if ($countDocs != $i) {
                                        continue;
                                    }

                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }

                                    $anexo_item_link = $anexo['link'];
                                    $countDocs++;

                                }

                                $anexo        = basename($anexos[0]);
                                $anexo_s3     = true;
                            }
                        }
                        ?>

                        <br />

                        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                            <?php if (isset($anexo_item_link)) { ?>
                                <a href="<?=$anexo_item_link?>" target="_blank" >
                            <?php } ?>

                            <img src="<?=$anexo_item_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                            <br />

                            <?php if (isset($anexo_item_link)) { ?>
                                </a>
                                <script>setupZoom();</script>
                            <?php } ?>

                            <?php
                            if ($anexo_s3 === false) {
                            ?>
                                <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" >Anexar</button>
                            <?php
                            }
                            ?>

                            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                            <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                            <?php
                            if ($anexo_s3 === true) {?>
                                <!--<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button> -->
                                <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')">Baixar</button>

                            <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                }
                ?>
            </td>
        </tr>
    </table>
    <!-- Fim anexo-->

<?php
}
?>

<!------------ Atendimento Direto de Pedidos ------------------- -->
<?
$sql = "SELECT posto, distribuidor FROM tbl_pedido WHERE pedido = $pedido";
$res = @pg_query ($con,$sql);

if (@pg_fetch_result ($res,0,posto) <> @pg_fetch_result ($res,0,distribuidor) AND strlen (@pg_fetch_result ($res,0,distribuidor)) > 0) {
    echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Pedido atendido via distribuidor</h2>";

    #------------- Atendimento TELECONTROL -------------------
    if (pg_fetch_result ($res,0,distribuidor) == 4311) {
        echo "<table width='550' align='center' border='0' cellspacing='3'>";
        echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
        if($login_fabrica == 168){
            echo "<td nowrap>Nota Fiscal</td>";
        }else{
            echo "<td nowrap>Nota Fiscal Tele</td>";
        }
        echo "<td>Data</td>";
        echo "<td>Peça</td>";
        echo "<td>Qtde</td>";
        echo "</tr>";

        $sql = "SELECT  tbl_faturamento.faturamento ,
                        tbl_faturamento.nota_fiscal ,
                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        tbl_peca.referencia         ,
                        tbl_faturamento_item.qtde   ,
                        tbl_peca.descricao
                FROM    tbl_faturamento_item
                JOIN    tbl_faturamento USING (faturamento)
                JOIN    tbl_peca USING (peca)
                WHERE   tbl_faturamento.distribuidor in($distribuidor,376542)
                AND     tbl_faturamento.fabrica in ( $login_fabrica,10)
                AND     tbl_faturamento_item.pedido = $pedido
                ORDER BY  tbl_faturamento.nota_fiscal , tbl_peca.referencia";

        $res = @pg_query ($con,$sql);

        for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {
            $nota_fiscal = trim (pg_fetch_result ($res,$i,nota_fiscal));
            $emissao = trim (pg_fetch_result ($res,$i,emissao));

            echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
            if($login_fabrica == 3){
                echo "<td><a href='nf_detalhe_britania.php?faturamento=" . pg_fetch_result ($res,$i,faturamento) . "'><b>". $nota_fiscal . "</b></td>";
            }else{
                echo "<td><a href='nf_detalhe.php?faturamento=" . pg_fetch_result ($res,$i,faturamento) . "'><b>". $nota_fiscal . "</b></td>";          }
            echo "<td>" .$emissao. "</td>";
            echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
            echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    #------------- Atendimento GARANTIA DISTRIBUIDOR -------------------
    if (@pg_fetch_result ($res,0,distribuidor) <> 4311 AND $tipo_pedido == "Garantia" ) {


        //wellington - Para exibir pedidos atendidos via distriuidor também na tela do distribuidor
        if ($login_e_distribuidor == true) {
            $sqlp = "SELECT posto FROM tbl_pedido WHERE pedido = $pedido";
            $resp = @pg_query ($con,$sqlp);
            $dposto = pg_fetch_result($resp,0,0);
        } else {
            $dposto = $login_posto;
        }

        $sql = "SELECT  tbl_os.sua_os ,
                        tbl_os.consumidor_nome,
                        tbl_os_item_nf.nota_fiscal ,
                        TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf ,
                        tbl_peca.referencia         ,
                        tbl_os_item_nf.qtde_nf   ,
                        tbl_peca.descricao
                FROM    tbl_os
                JOIN    tbl_os_produto USING (os)
                JOIN    tbl_os_item    USING (os_produto)
                JOIN    tbl_os_item_nf USING (os_item)
                JOIN    tbl_peca USING (peca)
                WHERE   tbl_os.posto   = $dposto
                AND     tbl_os.fabrica = $login_fabrica
                AND     tbl_os_item.pedido = $pedido
                ORDER BY  tbl_os_item_nf.nota_fiscal , tbl_peca.referencia";

        $res = @pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            echo "<table width='550' align='center' border='0' cellspacing='3'>";
            echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
            echo "<td nowrap>O.S.</td>";
            echo "<td nowrap>Nota Fiscal-$tipo_pedido</td>";
            echo "<td>Data</td>";
            echo "<td>Peça</td>";
            echo "<td>Qtde</td>";
            echo "</tr>";
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                $nota_fiscal = trim (pg_fetch_result ($res,$i,nota_fiscal));

                echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
                echo "<td nowrap><b>". pg_fetch_result ($res,$i,sua_os) . "</b></td>";
                echo "<td><b>". $nota_fiscal . "</b></td>";
                echo "<td>" . pg_fetch_result ($res,$i,data_nf) . "</td>";
                echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
                echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde_nf) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }

    #------------- Atendimento FATURADO DISTRIBUIDOR -------------------
    if (@pg_fetch_result ($res,0,distribuidor) <> 4311 AND $tipo_pedido == "Venda" ) {
        echo "<table width='550' align='center' border='0' cellspacing='3'>";
        echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
        echo "<td nowrap>Nota Fiscal-$tipo_pedido</td>";
        echo "<td>Data</td>";
        echo "<td>Peça</td>";
        echo "<td>Qtde</td>";
        echo "</tr>";

        //wellington - Para exibir pedidos atendidos via distriuidor também na tela do distribuidor
        if ($login_e_distribuidor == true) {
            $sqlp = "SELECT posto FROM tbl_pedido WHERE pedido = $pedido";
            $resp = @pg_query ($con,$sqlp);
            $dposto = pg_fetch_result($resp,0,0);
        } else {
            $dposto = $login_posto;
        }

        $sql = "SELECT  tbl_pedido_item_nf.nota_fiscal ,
                        TO_CHAR (tbl_pedido_item_nf.data_nf,'DD/MM/YYYY') AS data_nf ,
                        tbl_peca.referencia         ,
                        tbl_pedido_item_nf.qtde_nf   ,
                        tbl_peca.descricao
                FROM    tbl_pedido_item
                JOIN    tbl_pedido         USING (pedido)
                JOIN    tbl_pedido_item_nf USING (pedido_item)
                JOIN    tbl_peca USING (peca)
                WHERE   tbl_pedido.posto   = $dposto
                AND     tbl_pedido.fabrica = $login_fabrica
                AND     tbl_pedido_item.pedido = $pedido
                ORDER BY  tbl_pedido_item_nf.nota_fiscal , tbl_peca.referencia";

        $res = @pg_query ($con,$sql);

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            $nota_fiscal = trim (pg_fetch_result ($res,$i,nota_fiscal));

            echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
            echo "<td><b>". $nota_fiscal . "</b></td>";
            echo "<td>" . pg_fetch_result ($res,$i,data_nf) . "</td>";
            echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
            echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde_nf) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

}else{
    if(!in_array($login_fabrica,array(40,11,35,95,98,99,101,150,172)) AND strtolower($tipo_pedido) != "garantia" ){
        //HD 26175 - Samuel
        if($login_fabrica == 45){
            echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Confirma Recebimento de Notas Fiscais</h2>";
            echo "<table width='450' align='center' border='0' cellspacing='3'>";
            echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
            echo "<td>Nota Fiscal</td>";
            echo "<td>Data</td>";
            echo "<td>Recebimento</td>";
            echo "</tr>";
            $sql = "SELECT  distinct tbl_faturamento.nota_fiscal ,
                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        tbl_faturamento.faturamento,
                        to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia
                FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
                JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica = $login_fabrica
                JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
                ORDER   BY tbl_faturamento.nota_fiscal";
                $res = @pg_query ($con,$sql);
                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                    $conferencia = pg_fetch_result ($res,$i,conferencia);
                    $faturamento = pg_fetch_result ($res,$i,faturamento);
                    echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
                    echo "<td align='center'>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
                    echo "<td align='center'>" . pg_fetch_result ($res,$i,emissao) . "</td>";
                    ?>
                    <td align='center'>
                    <input type='text' size='12' maxlength ='10' name='conferencia' id='conferencia' value='<?= $conferencia ?>' <?
                    ?> class='caixa' onblur="javascript:atualizaConferencia('<?echo $faturamento;?>',this)">
                    </td>
                    </tr>
                    <?
                }
            echo "</table>";
        }

        $sql = "SELECT  distinct tbl_faturamento.faturamento ,
                        tbl_faturamento.nota_fiscal ,
                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        tbl_faturamento.conhecimento,
                        tbl_transportadora.nome AS transp,
                        tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.peca ,
                        tbl_faturamento_item.qtde ,
                        tbl_peca.peca ,
                        tbl_peca.referencia ,
                        tbl_faturamento.total_nota,
                        tbl_faturamento.cfop,
                        TO_CHAR( tbl_faturamento.cancelada, 'DD/MM/YYYY') as cancelada,
                        tbl_peca.descricao,
                        tbl_pedido_item.obs,
                        tbl_faturamento.envio_frete
                FROM    tbl_pedido_item
                JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND ( ( tbl_pedido_item.peca_alternativa IS NOT NULL AND tbl_pedido_item.peca_alternativa = tbl_faturamento_item.peca ) OR tbl_pedido_item.peca = tbl_faturamento_item.peca)
                JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica = $login_fabrica
				JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
                LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_faturamento.transportadora
				WHERE	tbl_pedido_item.pedido = $pedido
                ORDER   BY tbl_peca.descricao";

        if($login_fabrica == 2 ){
            $sql = "
                    SELECT distinct tbl_faturamento.faturamento ,
                        tbl_faturamento.nota_fiscal ,
                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        tbl_faturamento.conhecimento,
                        tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.peca ,
                        tbl_pedido_item_faturamento_item.qtde ,
                        tbl_peca.peca ,
                        tbl_peca.referencia ,
                        tbl_peca.descricao
                    FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                    JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
                    JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
                    JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                        AND tbl_faturamento.fabrica = $login_fabrica
                    JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                    ORDER BY tbl_peca.descricao";
                    //echo nl2br($sql);
        }

        //HD: 43990 - IGOR 03/10/2008
        if($login_fabrica == 50 or $login_fabrica==43){
            $sql = "
                    SELECT
                        distinct
                        tbl_faturamento.faturamento ,
                        tbl_faturamento.nota_fiscal ,
                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        to_char (tbl_faturamento.saida,'DD/MM/YYYY')   AS saida,
                        tbl_faturamento.transp,
                        tbl_faturamento.valor_frete,
                        tbl_faturamento.total_nota,
                        tbl_faturamento.conhecimento,
                        tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.peca ,
                        tbl_faturamento_item.qtde ,
                        tbl_peca.peca ,
                        tbl_peca.referencia ,
                        tbl_peca.descricao
                    FROM    tbl_faturamento_item
                    JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                AND tbl_faturamento.fabrica = $login_fabrica
                    JOIN    tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
                    WHERE   tbl_faturamento_item.pedido = $pedido
                    ORDER   BY tbl_peca.descricao";
        }
        $res = @pg_query ($con,$sql);

        if($login_fabrica == 3 and pg_num_rows ($res)==0){
            /*HD: 47887 - IGOR 10/11/2008 */
            $sql = "
                    SELECT  distinct tbl_faturamento.faturamento ,
                        tbl_faturamento.nota_fiscal ,
                        to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
                        tbl_faturamento.conhecimento,
                        tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.peca ,
                        tbl_faturamento_item.qtde ,
                        tbl_peca.peca ,
                        tbl_peca.referencia ,
                        tbl_peca.descricao
                    FROM    tbl_faturamento_item
                    JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                            AND tbl_faturamento.fabrica = $login_fabrica
                    JOIN    tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca
                    WHERE tbl_faturamento_item.pedido = $pedido
                    ORDER   BY tbl_peca.descricao";
            $res = @pg_query ($con,$sql);

        }
		
        if(in_array($login_fabrica,array(87))) {
            echo '<div class="texto_avulso" style="display:block; margin:15px 0 15px;">Notas canceladas em vermelho</div>';
        }
        
        if(pg_num_rows($res)> 0){ //HD-2890050 em conversa com ronaldo o mesmo solicitou para remover essa tabela para Britânia.
            echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>";
            echo "<tr class='titulo_tabela'><td colspan='100%'>" . traduz('notas.fiscais.que.atenderam.a.este.pedido', $con)  . "</td></tr>";
        }
        /*HD: 64617*/
        if($login_fabrica == 50 and pg_num_rows($res)> 0){
            echo "<tr class='titulo_coluna'>";
            echo "<td colspan = '5'>";
            echo "<table width='450' align='center' border='0' cellspacing='3'>";
            echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
            echo "<td >Transportadora</td>";
            echo "<td >Valor do Frete</td>";
            echo "<td>Valor Total</td>";
            echo "</tr>";
            echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
            echo "<td align= 'center'>" . pg_fetch_result ($res,0,transp) . "</td>";
            echo "<td align= 'center'>" . pg_fetch_result ($res,0,valor_frete) . "</td>";
            echo "<td align= 'center'>" . pg_fetch_result ($res,0,total_nota) . "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</td>";
            echo "</tr>";
        }
        if(pg_num_rows($res)> 0){ //HD-2890050 em conversa com ronaldo o mesmo solicitou para remover essa tabela para Britânia.
            echo "<tr class='titulo_coluna'>";
            echo "<td>Nota Fiscal</td>";

            if($login_fabrica == 30) {
                echo "<td align='center'><b>Data prevista de entrega</b></td>";
                echo "<td align='center'><b>Status de entrega</b></td>";
            }

            echo "<td>Data</td>";
            echo ($login_fabrica == 157) ? "<td>Transportadora</td>" : "";
            echo ($login_fabrica == 50) ? "<td>Saída</td>" : "";
            //Gustavo 12/12/2007 HD 9590
            echo (in_array($login_fabrica, array(35,147))) ? "<td>Conhecimento</td>" : "";
            if($login_fabrica == 87) {
                echo '<td>Total da Nota</td>
                      <td>CFOP</td>';
            }
            echo "<td>Peça</td>";
            if ($login_fabrica == 86 )
                        {
                            echo "<td align='center'><b>Rastreio Transporte</b></td>";
                            echo "<td align='center'><b>Tipo Frete</b></td>";
                        }
            echo "<td>Qtde</td>";
            echo "</tr>";
        }
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                //Gustavo 12/12/2007 HD 9095
                if (in_array($login_fabrica, array(35,147))) {
                    $conhecimento = trim(pg_fetch_result($res,$i,conhecimento));
                }
                if(in_array($login_fabrica,array(87))) {
                    $canc      = pg_fetch_result($res,$i,'cancelada');
                    $cancelada = (!empty($canc)) ? 'style="color:red;" title="Cancelada em '.$canc.'"' : '';
                }
                $cor = $i % 2 == 0 ? '#F7F5F0' : '#F1F4FA';
                echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' bgcolor='$cor' >";
                if($login_fabrica == 42){
                    $nf_x = pg_fetch_result ($res,$i,nota_fiscal);

                    echo "<td $cancelada align='center'>";
                        echo "<span style='cursor:pointer' onclick='linkLupeon(\"$nf_x\", \"$cnpj\")'>" . $nf_x. '</span>';
                    echo "</td>";

                } else {
                    echo "<td $cancelada>" . pg_fetch_result ($res,$i,nota_fiscal) . "</td>";
                }

                if($login_fabrica == 30) {

                    $obs = explode(";", pg_fetch_result($res, $i, obs));

                    echo "<td align='center'>$obs[0]</td>";
                    echo "<td align='center'>$obs[1]</td>";
                }

                echo "<td>" . pg_fetch_result ($res,$i,emissao) . "</td>";
                echo ($login_fabrica == 157) ? "<td>" . pg_fetch_result ($res,$i,transp) . "</td>" : "";
                echo ($login_fabrica == 50) ? "<td>" . pg_fetch_result ($res,$i,saida) . "</td>" : "";
                if(in_array($login_fabrica, array(35,147))){
                    echo "<td>";
                    echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target='_blank'>";
                    echo $conhecimento;
                    echo "</A>";
                    echo "</td>";
                }
                if($login_fabrica == 87) {
                    echo '<td>'.pg_fetch_result ($res,$i,total_nota).'</td>
                          <td>'.pg_fetch_result ($res,$i,cfop).'</td>';
                }
                echo "<td nowrap>" . pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao) . "</td>";
                if ($login_fabrica == 86)
                        {
                            echo "<td align='center'>".pg_fetch_result($res,$i,rastreio)."</td>";
                            echo "<td align='center'>".pg_fetch_result($res,$i,envio_frete)."</td>";
                        }
                echo "<td align='right'>" . pg_fetch_result ($res,$i,qtde) . "</td>";
                echo "</tr>";
            }
            echo "</table> <br>";
    }
}
if($login_fabrica == 74){
    $sqlObs = "SELECT obs FROM tbl_pedido_item WHERE pedido = $pedido AND obs notnull";
    $resObs = pg_query($con,$sqlObs);
    if(pg_num_rows($resObs) > 0){
        echo "<table align='center' class='tabela' width='700'>";
        echo "<td class='titulo_tabela' colspan='100%'>".traduz('notas.fiscais.canceladas',$con)."</td>";
        echo "<tr class='titulo_coluna'>";
        echo "<th>Nota Fiscal</th>";
        echo "<th>Peça</th>";
        echo "<th>Data Cancelamento</th>";
        echo "</tr>";

        for($z = 0; $z < pg_num_rows($resObs); $z++){
            $obs = pg_fetch_result($resObs, $z, 'obs');
            list($nf_obs,$peca_obs,$data_obs) = explode('|',$obs);
            $cor = ($z % 2 ) ? '#F1F4FA' : "#F7F5F0";

            echo "<tr bgcolor='$cor'>";
            echo "<td align='center'>$nf_obs</td>";
            echo "<td>$peca_obs</td>";
            echo "<td align='center'>$data_obs</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>

<?php

if(in_array($login_fabrica, array(138))){

    include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
    $tDocs   = new TDocs($con, $login_fabrica);

?>
    <br /> <br />

    <!-- ANexo -->
    <table class="tabela" align="center" width="700" cellspacing="1" cellpadding="6">
        <tr class="titulo_coluna">
            <td> Boleto Bancário </td>
        </tr>
        <tr>
            <td align="center">
                <?php

                $fabrica_qtde_anexos = 1;

                if ($fabrica_qtde_anexos > 0) {

                    echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

                    $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;

                    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                        unset($anexo_link);

                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3          = false;
                        $anexo             = "";

                        if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {
                                    if ($countDocs != $i) {
                                        continue;
                                    }

                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }

                                    $anexo_item_link = $anexo['link'];
                                    $countDocs++;

                                }

                                $anexo        = basename($anexos[0]);
                                $anexo_s3     = true;
                            }
                        }
                        ?>

                        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                            <?php if (isset($anexo_item_link)) { ?>
                                <a href="<?=$anexo_item_link?>" target="_blank" >
                            <?php } ?>

                            <img src="<?=$anexo_item_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                            <br />

                            <?php if (isset($anexo_item_link)) { ?>
                                </a>
                                <script>setupZoom();</script>
                            <?php } ?>


                            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                            <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                            <?php
                            if ($anexo_s3 === true) {?>
                                <!--<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button> -->
                                <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')">Visualizar boleto</button>

                            <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                }
                ?>
            </td>
        </tr>
    </table>
    <!-- Fim anexo-->

<?php
}
?>

<br>
<!-- ########## PEDIDO CANCELADO ########## -->
<?

if ($login_fabrica == 104) {
    $join_os = "
    LEFT JOIN tbl_os_item on tbl_pedido_cancelado.pedido_item = tbl_os_item.pedido_item
    LEFT JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
    LEFT JOIN tbl_os on tbl_os_produto.os = tbl_os.os
    ";
}else{
    $join_os = "
    LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
    ";
}

$sql =  "SELECT tbl_peca.referencia         ,
                tbl_peca.descricao          ,
                tbl_pedido_cancelado.qtde   ,
                tbl_pedido_cancelado.motivo ,
                to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data ,
                tbl_os.sua_os
        FROM tbl_pedido_cancelado
        JOIN tbl_peca USING (peca)
        $join_os
        WHERE tbl_pedido_cancelado.pedido  = $pedido
        AND   tbl_pedido_cancelado.fabrica = $login_fabrica";

$res = @pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
        if ($i == 0) {
            echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>";
            echo "<tr class='titulo_tabela'><td colspan='4'>Itens cancelados que pertencem a este pedido</td></tr>";
            echo "<tr class='titulo_coluna'>";
            if (strtoupper($condicao) == "GARANTIA") {
                echo "<td>OS</td>";
            }
            echo "<td>Data</td>";
            echo "<td>Peça</td>";
            echo "<td>Qtde</td>";
            echo "</tr>";
        }
        echo "<tr bgcolor='$cor'>";
        if (strtoupper($condicao) == "GARANTIA") {
            echo "<td nowrap align='center'>".pg_fetch_result($res,$i,sua_os)."</td>";
        }
        echo "<td nowrap align='center'>".pg_fetch_result($res,$i,data)."</td>";
        echo "<td nowrap align='center'>".pg_fetch_result($res,$i,referencia)." - ".pg_fetch_result($res,$i,descricao)."</td>";
        echo "<td nowrap align='center'>".pg_fetch_result($res,$i,qtde)."</td>";
        echo "</tr>";
        echo "<tr bgcolor='$cor'>";
        echo "<td class='titulo_coluna'>Motivo</td>";
        echo "<td colspan='3' nowrap>".pg_fetch_result($res,$i,motivo)."</td>";
        echo "</tr>";
    }
    echo "</table>";
}else{
    echo "<p align='center'>Não há nenhum pedido cancelado.</p>";
}



if(in_array($login_fabrica, array(141,144)) && $tipo_pedido == "VENDA"){

    $comprovante = $s3->getObjectList("thumb_comprovante_pedido_{$login_fabrica}_{$pedido}");

    $link_img    = $comprovante[0];

    if(!empty($link_img)){

        $link_img = str_replace("thumb_", "", $link_img);
        $link_img = explode("/", $link_img);
        $link_img = $link_img[count($link_img) -1];

        $comprovante = basename($comprovante[0]);
        $comprovante = $s3->getLink($comprovante);

        echo "
            <p align='center'>
                <br />
                <a href='{$comprovante}' target='_blank'><img src='{$comprovante}' style='max-width: 100px; max-height: 100px;_height:100px;*height:100px;' /></a> <br />
                <strong>Comprovante de Pagamento</strong>
            </p>";

    }else{
        echo "<p align='center'>Sem Comprovante de Pagamento Inserido</p>";
    }

}
if (in_array($login_fabrica, array(151))) {
    $sql =  "SELECT tbl_pedido.obs
            FROM  tbl_pedido
            JOIN  tbl_pedido_item USING (pedido)
            JOIN  tbl_peca        USING (peca)
            WHERE tbl_pedido_item.pedido = $pedido
            AND   tbl_pedido.fabrica     = $login_fabrica
            AND tbl_pedido.obs IS NOT NULL";
    $res = pg_query($con,$sql);

    $tabela_obs = '';
    if (pg_num_rows($res) > 0) {
        $tabela_obs .= "<table width='700' cellpadding='2' cellspacing='1' class='tabela' >";
        $tabela_obs .= "<thead>";
        $tabela_obs .= "<tr class='titulo_tabela'>";
        $tabela_obs .= "<th>Observa&ccedil;&atilde;o Cancelamento</th>";
        $tabela_obs .= "</tr>";
        $tabela_obs .= "</thead>";
        $tabela_obs .= "<tbody>";
        $tabela_obs .= "<tr>";
        $tabela_obs .= "<td>";
        $tabela_obs .= pg_fetch_result($res, 0, "obs");
        $tabela_obs .= "</td>";
        $tabela_obs .= "</tr>";
        $tabela_obs .= "</tbody>";
        $tabela_obs .= "</table>";

        echo $tabela_obs;
    }
}
if ($telecontrol_distrib) { ?>
    <br />
    <center>
        <iframe id="iframe_interacao" src="interacoes.php?tipo=pedido&reference_id=<?=$pedido?>&posto=<?=$login_posto?>" style="width: 700px;" frameborder="0" scrolling="no"></iframe>
    </center>
    <br />
<?php }
include "rodape.php";

