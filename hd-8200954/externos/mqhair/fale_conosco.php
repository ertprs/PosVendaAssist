<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../plugins/fileuploader/TdocsMirror.php';
include_once '../../class/communicator.class.php';

$login_fabrica = 186;

if($_POST['query']){

    $param = $_POST['query'];
    $sql = "SELECT referencia, descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND (referencia ~* '{$param}' OR descricao ~* '{$param}');";
    $res = pg_query($con,$sql);
    $dados = pg_fetch_all($res);

    if(pg_num_rows($res) > 0){
	    foreach ($dados as $key => $value) {
		$value['descricao'] = (mb_detect_encoding($value['descricao'], 'UTF-8', true)) ? $value['descricao'] : utf8_encode($value['descricao']);
            $retorno[] = array("label" => $value['referencia']." : ".$value['descricao'], "value" => $value['referencia']." : ".$value['descricao']);
        }
    }
    
    echo json_encode($retorno);
    exit;
}
if($_POST['ajax_carrega_defeito']){

    $referencia = trim($_POST['referencia']);
    $sql = "SELECT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao 
              FROM tbl_produto 
              JOIN tbl_diagnostico ON tbl_diagnostico.familia=tbl_produto.familia AND tbl_diagnostico.fabrica={$login_fabrica}
              JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado=tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica={$login_fabrica}
             WHERE tbl_produto.fabrica_i = {$login_fabrica} 
               AND (tbl_produto.referencia ~* '{$referencia}' OR tbl_produto.descricao ~* '{$referencia}');";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        foreach (pg_fetch_all($res)as $key => $value) {
            $retorno[] = ["defeito" => $value["defeito_reclamado"] , "descricao" => utf8_encode($value['descricao'])];
        }
    }
    echo json_encode($retorno);
    exit;
}
/*

Alterações Fale Conosco
 - Alterar para gravar os produtos na tbl_hd_chamado_item. Igual Viapol
 - Retirar o campo Qtde e adicionar o campo Defeito Reclamado
 - Criar um novo combo de Assunto (Irão passar uma listagem e depois vamos decidir onde irá gravar)
 - Inserir um helper (ícone de "?") com um texto avisando que pode ser incluído mais de um produto



*/
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


if ($_POST["ajax_interage"] == true) {
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $txt_protocolo = pg_escape_string($_POST['txt_protocolo']);
    $txt_protocolo = utf8_decode($txt_protocolo);
    $protocolo     = $_POST['protocolo'];
  
    if (!empty($protocolo) && !empty($txt_protocolo)) {

        $sql = "INSERT INTO tbl_hd_chamado_item (
                    status_item,
                    hd_chamado ,
                    comentario,
                    empregado
                ) VALUES (
                    'Aberto',
                    $protocolo,
                    '$txt_protocolo',
                    1
                )";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
/*
        $sqlExt = "
            SELECT * 
            FROM tbl_hd_chamado_extra
            WHERE hd_chamado = {$protocolo};
        ";
        $resExt = pg_query($con, $sqlExt);
        $consumidor_nome = pg_fetch_result($resExt, 0, 'nome');
        $consumidor_email = pg_fetch_result($resExt, 0, 'email');
        $consumidor_celular = pg_fetch_result($resExt, 0, 'celular');
        $corpoEmail = "
            <p><b>Nome:</b> {$consumidor_nome}</p>
            <p><b>Email:</b> {$consumidor_email}</p>
            <p><b>Celular:</b> {$consumidor_celular}</p>
            <p><b>".utf8_decode('Interação').":</b> {$txt_protocolo} </p>
        ";

        $sql_busca_origem = "
            SELECT 
                hd_chamado_origem AS origem_id
            FROM tbl_hd_chamado_origem
            WHERE fabrica = {$login_fabrica}
            AND UPPER(descricao) = 'FALE CONOSCO'
            LIMIT 1;
        ";
        $res_busca_origem = pg_query($con, $sql_busca_origem);

        if (pg_num_rows($res_busca_origem) > 0) {
            $origem_id = pg_fetch_result($res_busca_origem, 0, 'origem_id');
            $sql_busca_admin  = "
                SELECT
                    a.admin,
                    a.email
                FROM tbl_admin a
                JOIN tbl_hd_origem_admin hoa ON hoa.admin = a.admin AND hoa.fabrica = {$login_fabrica}
                WHERE a.fabrica = {$login_fabrica}
                AND hoa.hd_chamado_origem = {$origem_id};
            ";

            $res_busca_admin  = pg_query($con, $sql_busca_admin);
            
            if (pg_num_rows($res_busca_admin) > 0) {
                $id_atendente    = pg_fetch_result($res_busca_admin, 0, 'admin');
                $email_atendente = pg_fetch_result($res_busca_admin, 0, 'email');

                $mailTc = new TcComm('smtp@posvenda');
                $res = $mailTc->sendMail(
                    $email_atendente,
                    "Interação no Atendimento Nº {$protocolo} - Fale Conosco via site MQ Professional",
                    $corpoEmail,
                    'noreply@telecontrol.com.br'
                );
            }
        }
*/

        exit(json_encode(array("sucesso" => true, "msn" => "Mensagem registrada com sucesso!")));
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        exit(json_encode(array("erro" => true, "msn" => 'Erro ao gravar registro.')));
    }

}


if (filter_input(INPUT_POST,'btn_submit')) {

    $assunto                = trim(filter_input(INPUT_POST,"assunto",FILTER_SANITIZE_SPECIAL_CHARS));
    $canal                = trim(filter_input(INPUT_POST,"canal",FILTER_SANITIZE_SPECIAL_CHARS));
    $tipo                   = trim(filter_input(INPUT_POST,"tipo",FILTER_SANITIZE_SPECIAL_CHARS));
    $consumidor_nome        = utf8_decode(trim(filter_input(INPUT_POST,"consumidor_nome",FILTER_SANITIZE_SPECIAL_CHARS)));
    $data_nascimento        = trim(filter_input(INPUT_POST, "data_nascimento", FILTER_SANITIZE_SPECIAL_CHARS));

    $cpf                    = trim(filter_input(INPUT_POST, "cpf", FILTER_SANITIZE_SPECIAL_CHARS));
    $cpf                    = preg_replace("/[^0-9]/", "", $cpf);
    
    $consumidor_email       = trim(filter_input(INPUT_POST,"consumidor_email",FILTER_SANITIZE_EMAIL));
    $consumidor_celular     = trim(filter_input(INPUT_POST,"consumidor_celular",FILTER_SANITIZE_NUMBER_INT));
    $consumidor_cep         = trim(filter_input(INPUT_POST,"consumidor_cep",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
    $consumidor_endereco    = utf8_decode(trim(filter_input(INPUT_POST,"consumidor_endereco")));
    $consumidor_numero      = trim(filter_input(INPUT_POST,"consumidor_numero"));
    $consumidor_bairro      = utf8_decode(trim(filter_input(INPUT_POST,"consumidor_bairro",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW)));
    $consumidor_complemento = trim(filter_input(INPUT_POST,"consumidor_complemento",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
    $consumidor_cidade      = trim(filter_input(INPUT_POST,"consumidor_cidade",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
    $consumidor_estado      = trim(filter_input(INPUT_POST,"consumidor_estado",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
    $aceito                 = trim(filter_input(INPUT_POST,"aceito",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
    $mensagem               = utf8_decode(trim(filter_input(INPUT_POST,"mensagem",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW)));
    $produtoDescricao       = trim(filter_input(INPUT_POST, "produto_descricao_multi", FILTER_SANITIZE_SPECIAL_CHARS));

    $lista_produtos  = $_POST['PickList'];

    if (empty($assunto)) {
        $msg_erro["msg"][] = "Preencha o campo Assunto";
        $msg_erro['campos'][] = "assunto";
    }
    if (empty($canal)) {
        $msg_erro["msg"][] = "Preencha o campo Canal";
        $msg_erro['campos'][] = "canal";
    }

    $sqlObrigaCampos = "SELECT obriga_campos FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$canal};";
    $resObrigaCampos = pg_query($con, $sqlObrigaCampos);
    $obriga_campos = pg_fetch_result($resObrigaCampos, 0, "obriga_campos");

    if (empty($consumidor_nome)) {
        $msg_erro["msg"][] = "Preencha o campo Nome Completo";
        $msg_erro['campos'][] = "consumidor_nome";
    }

    if (empty($consumidor_email)) {
        $msg_erro["msg"][] = "Preencha o campo Email";
        $msg_erro['campos'][] = "consumidor_email";
    }

    if (empty($consumidor_celular)) {
        $msg_erro["msg"][] = "Preencha o campo Celular";
        $msg_erro['campos'][] = "consumidor_celular";
    }
 
    if (!filter_input(INPUT_POST,"mensagem")) {
        $msg_erro["msg"][] = "Preencha o campo Mensagem";
        $msg_erro['campos'][] = "mensagem";
    }
 
    if (!filter_input(INPUT_POST,"cpf")) {
        $msg_erro["msg"][] = "Preencha o campo CPF/CNPJ";
        $msg_erro['campos'][] = "cpf";
    }

    if ($obriga_campos == 't') {

        if (empty($consumidor_cep)) {
            $msg_erro["msg"][] = "Preencha o campo CEP";
            $msg_erro['campos'][] = "consumidor_cep";
        }

        if (empty($consumidor_endereco)) {
            $msg_erro["msg"][] = "Preencha o campo Endereço";
            $msg_erro['campos'][] = "consumidor_endereco";
        }

        if (empty($consumidor_numero)) {
            $msg_erro["msg"][] = "Preencha o campo Número";
            $msg_erro['campos'][] = "consumidor_numero";
        }

        if (empty($consumidor_bairro)) {
            $msg_erro["msg"][] = "Preencha o campo Bairro";
            $msg_erro['campos'][] = "consumidor_bairro";
        }

        if (empty($consumidor_cidade)) {
            $msg_erro["msg"][] = "Preencha o campo Cidade";
            $msg_erro['campos'][] = "consumidor_cidade";
        }
        if (empty($consumidor_estado)) {
            $msg_erro["msg"][] = "Preencha o campo UF";
            $msg_erro['campos'][] = "consumidor_estado";
        }

    }

    if (!empty($produtoDescricao)) {
        $msg_erro["msg"][] = 'É necessário clicar no botão "Adicionar" para adicionar um produto';
        $msg_erro['campos'][] = "produto_descricao";
    }

    if (!empty($cpf)) {
        $valida_cpf_cnpj = valida_consumidor_cpf($cpf);

        if ($valida_cpf_cnpj === false){
            $msg_erro["msg"][] = "CPF/CNPJ informado inváido";
            $msg_erro['campos'][] = "cpf";
        }

        if ($tipo == 'M') {
            if (strlen($cpf) < 14) {
                $msg_erro["msg"][] = "Informar um CNPJ válido";
                $msg_erro['campos'][] = "cpf";
            }
        }
    }

    if (strlen($data_nascimento) > 0 ) {
        list($di, $mi, $yi) = explode("/", $data_nascimento);
        if (!checkdate($mi, $di, $yi)) {
            $msg_erro["msg"][] = "Data Inválida";
            $msg_erro['campos'][] = "data_nascimento";
        }
        $data_nascimento = "'".converte_data($data_nascimento)."'";
    }else{
        $data_nascimento = "null";
    }

    $sql = "SELECT  tbl_hd_chamado.hd_chamado, tbl_hd_chamado_extra.cpf
            FROM    tbl_hd_chamado
            JOIN    tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
            WHERE   tbl_hd_chamado.fabrica      = {$login_fabrica}
            AND     tbl_hd_chamado_extra.cpf    = '{$cpf}'
            AND     status                      = 'Aberto'";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $hd_chamados = pg_fetch_all($res);
        $cpf_cnpj = pg_fetch_result($res, 0, cpf);
        $msn_cpf = (strlen($cpf_cnpj) >= 14) ? "CNPJ: {$cpf_cnpj}" : "CPF: {$cpf_cnpj}";

        $msg_erro_existe_protocolo = true;
        $msg_erro["msg"][] = "Identificamos que seu <b>{$msn_cpf}</b> já possui protocolo de atendimento e está sendo acompanhado pela nossa equipe especializada<br /><br />
                    Pedimos que verifique abaixo as informações e status do seu protocolo, caso necessário,
adicione alguma mensagem no campo abaixo que será direcionada a nossa equipe que realizará o contato o mais breve possível.";
    }



    if (count($msg_erro["msg"]) == 0) {
            
        if (empty($consumidor_cidade)){
            $cidade = "null";
        }else{
            $consumidor_cidade = getCidades($con, $consumidor_cidade, $consumidor_estado);
            $cidade = $consumidor_cidade["cidade_id"];
            $cidade_nome = $consumidor_cidade["cidade_nome"];
        }

        $consumidor_celular     = str_replace("-","",$consumidor_celular);
        $consumidor_cep     = str_replace(["-", " ", "."],"",$consumidor_cep);
               
        $fone  = "";
        $fone2 = "";
        
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

        $sqlHdO = "SELECT tbl_hd_origem_admin.admin 
                  FROM tbl_hd_origem_admin 
                 WHERE hd_chamado_origem = {$hd_chamado_origem} 
                   AND fabrica = {$login_fabrica}";
        $resHdO = pg_query($con, $sqlHdO);
        $xadmin = null;
        if (pg_num_rows($resHdO) > 0) {
            $xadmin = pg_fetch_result($resHdO, 0, 'admin');
        }

        $sql_classificacao = "SELECT hd_classificacao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND descricao = 'Fale Conosco'";
        $res_classificacao = pg_query($con,$sql_classificacao);
        if (pg_num_rows($res_classificacao) > 0) {
            $hd_classificacao = pg_fetch_result($res_classificacao, 0, 'hd_classificacao');
        }

        $res = pg_query($con,"BEGIN TRANSACTION");
        $erro = array();

        $sqlInsHd = "
            INSERT INTO tbl_hd_chamado (
                fabrica,
                atendente,
                admin,
                fabrica_responsavel,
                status,
                titulo,
                hd_classificacao,
                categoria
            ) VALUES (
                $login_fabrica,
                $xadmin,
                $xadmin,
                $login_fabrica,
                'Aberto',
                'Atendimento Fale Conosco - Site {$site}',
                $hd_classificacao,
                'reclamacao_produto'
            ) RETURNING hd_chamado;
        ";

        $resInsHd = pg_query($con,$sqlInsHd);

        if (strlen(pg_last_error($con)) > 0) {
            $erro[] = "Ocorreu um erro inesperado #001";
        } else {
            $hd_chamado = pg_fetch_result($resInsHd,0,'hd_chamado');

            if(count($_POST['PickList']) > 0){
                foreach ($_POST['PickList'] as $key => $value) {
                    list($referencia, $nome_produto, $defeito_reclamado, $defeito_reclamado_descricao) = explode(":", $value);
                    $sqlProduto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND referencia = '".trim($referencia)."'";
                    $resProduto = pg_query($con, $sqlProduto);

                    if (pg_num_rows($resProduto) > 0) {
                        $produto = pg_fetch_result($resProduto, 0, 'produto');
                    } else {
                        $erro[] = "Produto {$nome_produto} não encontrado";
                    }

                    $sqlDef = "SELECT defeito_reclamado FROM tbl_defeito_reclamado WHERE fabrica = {$login_fabrica} AND defeito_reclamado = ".trim($defeito_reclamado);
                    $resDef = pg_query($con, $sqlDef);

                    if (pg_num_rows($resDef) > 0) {
                        $defeito_reclamado = pg_fetch_result($resDef, 0, 'defeito_reclamado');
                    } else {
                        $erro[] = "Defeito Reclamado {$defeito_reclamado_descricao} não encontrado";
                    }

                    if (count($erro) == 0) {
                         $sqlInsItem = "INSERT INTO tbl_hd_chamado_item(
                                    hd_chamado   ,
                                    data         ,
                                    interno      ,
                                    produto      ,
                                    defeito_reclamado,
                                    qtde,
                                    status_item
                                ) values (
                                    $hd_chamado,
                                    current_timestamp,
                                    't',
                                    '$produto',
                                    '$defeito_reclamado',
                                    '1',
                                    'Aberto'
                                    )";
                        $resInsItem = pg_query($con,$sqlInsItem);
                        if (strlen(pg_last_error($con)) > 0) {
                            $erro[] = "Ocorreu um erro inesperado #008";
                        }
                    }

                }

                $msg = "";
                if (count($produtos) > 1) {
                    $msg = "Abertura de chamado via Fale Conosco para o(s) produto(s):<br />".implode("<br />", $produtos);
                    $mensagem = $msg."<br /><br />".$mensagem;
                }
            }

            if (count($erro) == 0) {
                $sqlInsEx = "
                    INSERT INTO tbl_hd_chamado_extra (
                        fone,
                        fone2,
                        hd_chamado,
                        hd_chamado_origem,
			hd_motivo_ligacao,
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
			1048,
                        'Fale Conosco',
                        '$tipo',
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

                if (strlen(pg_last_error($con)) > 0) {
                    $erro[] = "Ocorreu um erro inesperado #002".pg_last_error($con);
                } else {

                    if (strlen($assunto) > 0) {
                        $camsposAdd["assunto_fale_conosco"] = utf8_encode($assunto);
                        $array_campos_adicionais = json_encode($camsposAdd);
                        $updHDE = "UPDATE tbl_hd_chamado_extra SET array_campos_adicionais = '{$array_campos_adicionais}' WHERE hd_chamado = {$hd_chamado}";
                        $resUpdHDE = pg_query($con,$updHDE);
                    }

                    $sqlInsItem = "
                        INSERT INTO tbl_hd_chamado_item (
                            hd_chamado,
                            comentario,
                            status_item
                        ) VALUES (
                            $hd_chamado,
                            'Abertura de chamado via Fale Conosco',
                            'Aberto'
                        )
                    ";

                    $resInsItem = pg_query($con,$sqlInsItem);
                    if (strlen(pg_last_error($con)) > 0) {
                        $erro[] = "Ocorreu um erro inesperado #003";
                    }
                }
            }
        }

        if (isset($_FILES['anexo_nf']) AND !empty($_FILES['anexo_nf']['name']) && count($erro) == 0) {
            $data_hora = date("Y-m-d\TH:i:s");
            $destino   = '/tmp/';

            $extensoes = array('jpg', 'png', 'gif', 'pdf');

            $anx_nf    = $_FILES["anexo_nf"];
            $extensao  = pathinfo($_FILES['anexo_nf']['name'], PATHINFO_EXTENSION);
            
            if (array_search($extensao, $extensoes) === false) {
                $erro[] = "Por favor, envie arquivos com as seguintes extensões: jpg, png, pdf ou gif";
            }


            $nome_final = $login_fabrica.'_'.$hd_chamado.'.'.$extensao;
            $caminho    = $destino.$nome_final;

            if (move_uploaded_file($_FILES['anexo_nf']['tmp_name'], $caminho)) {
                $tdocsMirror = new TdocsMirror();
                $response = $tdocsMirror->post($caminho);
                
                if (array_key_exists("exception", $response)) {

                    $erro[] = "Ocorreu um erro ao realizar o upload: ".$response['message'];

                } else {

                    $file = $response[0];
                    
                    foreach ($file as $filename => $data) {
                        $unique_id = $data['unique_id'];
                    }

                    $sql_verifica = "
                        SELECT * 
                        FROM tbl_tdocs
                        WHERE fabrica = {$login_fabrica} 
                        AND contexto  = 'callcenter'
                        AND tdocs_id  = '$unique_id';
                    ";
                    $res_verifica = pg_query($con,$sql_verifica);

                    if (pg_num_rows($res_verifica) == 0){
                        $obs = json_encode(array(
                            "acao"     => "anexar",
                            "filename" => "{$nome_final}",
                            "filesize" => "".$_FILES['anexo_nf']['size']."",
                            "data"     => "{$data_hora}",
                            "fabrica"  => "{$login_fabrica}",
                            "page"     => "externos/mqhair/faleconosco.php",
                            "typeId"   => "notafiscal",
                            "descricao"=> ""
                        ));

                        $sql = "
                            INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
                            VALUES('$unique_id', $login_fabrica, 'callcenter', 'ativo', '[$obs]', 'callcenter', $hd_chamado);";  
                        $res = pg_query($con, $sql);
                        
                        if (strlen(pg_last_error($con)) > 0) {
                            $erro[] = "Ocorreu um erro inesperado #004";
                        }

                    }
                }
            } else {
                $erro[] = "Não foi possível enviar o arquivo, tente novamente";
            }
        }

        if (!empty($erro)) {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $msg_erro["msg"][] = implode("<br />", $erro);
        } else {
            $res = pg_query($con,"COMMIT TRANSACTION");
            
            /*
            $corpoEmail = "
                <p><b>Nome:</b> {$consumidor_nome}</p>
                <p><b>Email:</b> {$consumidor_email}</p>
                <p><b>Celular:</b> {$consumidor_celular}</p>
                <p><b>CEP:</b> {$consumidor_cep} - <b>".utf8_decode('Endereço').":</b> {$consumidor_endereco}, <b>".utf8_decode('Número').":</b> {$consumidor_numero}</p>
                <p><b>Bairro:</b> {$consumidor_bairro} <b>Cidade:</b> {$cidade_nome} - <b>UF:</b> {$consumidor_estado}</p>
                <p><b>Mensagem:</b> {$mensagem} </p>
            ";

            $sql_busca_origem = "
                SELECT 
                    hd_chamado_origem AS origem_id
                FROM tbl_hd_chamado_origem
                WHERE fabrica = {$login_fabrica}
                AND UPPER(descricao) = 'FALE CONOSCO'
                LIMIT 1;
            ";
            $res_busca_origem = pg_query($con, $sql_busca_origem);
    
            if (pg_num_rows($res_busca_origem) > 0) {
                $origem_id = pg_fetch_result($res_busca_origem, 0, 'origem_id');
                $sql_busca_admin  = "
                    SELECT
                        a.admin,
                        a.email
                    FROM tbl_admin a
                    JOIN tbl_hd_origem_admin hoa ON hoa.admin = a.admin AND hoa.fabrica = {$login_fabrica}
                    WHERE a.fabrica = {$login_fabrica}
                    AND hoa.hd_chamado_origem = {$origem_id};
                ";

                #$res_busca_admin  = pg_query($con, $sql_busca_admin);
                
                if (pg_num_rows($res_busca_admin) > 0) {
                    $id_atendente    = pg_fetch_result($res_busca_admin, 0, 'admin');
                    $email_atendente = pg_fetch_result($res_busca_admin, 0, 'email');

                    $mailTc = new TcComm('smtp@posvenda');
                    $res = $mailTc->sendMail(
                        $email_atendente,
                        "Fale Conosco via site MQ Professional N° ".$hd_chamado,
                        $corpoEmail,
                        'noreply@telecontrol.com.br'
                    );
                }
            } */
            
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
            $_POST["assunto"] = "";
            $_POST["canal"] = "";
            $_POST["PickList"] = [];
            $lista_produtos = [];

            $msg = "Atendimento aberto com sucesso!<br> <b>N° do protocolo:  {$hd_chamado}</b><br> Em breve nossa equipe entrará em contato.";
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
<link rel="stylesheet" href="../bootstrap3/css/bootstrap.min.css" />
<link rel="stylesheet" href="../roca/principal/roca-web-theme/css/normalize.min.css" />
<link rel='stylesheet' type='text/css' href='../../plugins/select2/select2.css' />
<!-- <link rel="stylesheet" href="../roca/principal/roca-web-theme/css/main_roca.css" /> -->

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

    .campos_obg{
        color: #d90000;
        font-size: 0.7em;
    }
   
    .texto_anexo{
        font-weight: bold;
        color: red;
        font-size: 0.80em;
    }

    input[type="textSearch"], input[type="text"], input[type="password"], input[type="date"], input[type="datetime"], input[type="email"], input[type="number"], input[type="search"], input[type="tel"], input[type="time"], input[type="url"] {
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

    textarea {
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

    input[type="text"].oversize, input[type="password"].oversize, input[type="date"].oversize, input[type="datetime"].oversize, input[type="email"].oversize, input[type="number"].oversize, input[type="search"].oversize, input[type="tel"].oversize, input[type="time"].oversize, input[type="url"].oversize {
      font-size: 17px;
      padding: 4px 6px;
    }

    textarea.oversize {
      font-size: 17px;
      padding: 4px 6px;
    }

    input[type="text"]:focus, input[type="password"]:focus, input[type="date"]:focus, input[type="datetime"]:focus, input[type="email"]:focus, input[type="number"]:focus, input[type="search"]:focus, input[type="tel"]:focus, input[type="time"]:focus, input[type="url"]:focus {
      background: #fafafa;
      border-color: #b3b3b3;
    }

    textarea:focus {
      background: #fafafa;
      border-color: #b3b3b3;
    }

    input[type="text"][disabled], input[type="password"][disabled], input[type="date"][disabled], input[type="datetime"][disabled], input[type="email"][disabled], input[type="number"][disabled], input[type="search"][disabled], input[type="tel"][disabled], input[type="time"][disabled], input[type="url"][disabled] {
      background-color: #ddd;
    }

    textarea {
      height: auto;
    }
    textarea[disabled] {
      background-color: #ddd;
    }

    select {
      width: 100%;
    }

    .no-scroll-x {
        overflow-x: hidden !important;
    }

    .btn-roxo {
        background: #5a2e89 !important;
    }

    @media only screen 
      and (max-device-width: 700px) {
        .largo {
            display: block !important;
            width: 100% !important;
        }
        .select2 {
            width: 100% !important;
        }
    }


</style>
<?php
$sql_classificacao = "SELECT hd_classificacao, descricao, obriga_campos FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND  descricao ='Fale Conosco';";
$res_classificacao = pg_query($con, $sql_classificacao);
$array_canal = pg_fetch_all($res_classificacao);
$array_assunto = [
"PRODUTO PARA TROCA/CONSERTO" => "PRODUTO PARA TROCA/CONSERTO",
"ENDEREÇO DE ASSISTENCIA AUTORIZADA" => "ENDEREÇO DE ASSISTENCIA AUTORIZADA",
"DUVIDAS SOBRE PRODUTO" => "DUVIDAS SOBRE PRODUTO",
"COMPRAS ONLINE" => "COMPRAS ONLINE",
"QUERO SER DISTRIBUIDOR" => "QUERO SER DISTRIBUIDOR",
"QUERO SER UM POSTO AUTORIZADO" => "QUERO SER UM POSTO AUTORIZADO",
"QUERO SER PARCEIRO" => "QUERO SER PARCEIRO",
"JÁ SOU REVEDEDOR" => "JÁ SOU REVEDEDOR",
"CONTATO DE REPRESENTANTE / DISTRIBUIDOR" => "CONTATO DE REPRESENTANTE / DISTRIBUIDOR",
"OUTROS" => "OUTROS",
];
?>
</head>
<body>

<div class="container no-scroll-x">
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-8">
            <?php if (count($msg_erro["msg"]) > 0 && !$msg_erro_existe_protocolo) {?>
                <br />
                <div class="alert alert-danger">
                    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
                </div>
            <?php } ?>
            <?php if (!empty($msg)) {?>
                <br />
                <div class="alert alert-success">
                    <h4><?=$msg?></h4>
                </div>
            <?php }?>
            <?php if (count($msg_erro["msg"]) > 0 && $msg_erro_existe_protocolo) {?>
                    <div class="clear msg_erro errorproto">
                        <div class="alert alert-info">
                            <?=implode("<br />", $msg_erro["msg"])?>
                        </div>
                        <br /><hr/><br />
                        <div id="men_retorno" class="men_retorno"></div>
                        <div id="form_protrocolo" align="left">
                            <label class="txt_label">Nº Protocolo: </label>
                            <?php
                                if(count($hd_chamados) == 1) {
                                    echo "<input id='protocolo' name='protocolo' type='hidden' value='".$hd_chamados[0]["hd_chamado"]."'/>";
                                    echo "<input style='width:30%' readonly='readonly' class='input_text protocolo' value='".$hd_chamados[0]["hd_chamado"]."'/>";
                                } else {
                            ?>
                            <select class="input_select protocolo" name="protocolo" style="width: 50%" id="protocolo">
                                <option value="" >Escolha o protocolo que deseja interagir...</option>
                                <?php foreach ($hd_chamados as $key => $value) {?>
                                <option value="<?php echo $value["hd_chamado"];?>"><?php echo $value["hd_chamado"];?></option>
                                <?php }?>
                            </select>
                            <?php }?>
                            <label class="txt_label">Mensagem: </label>
                            <textarea name="txt_protocolo" rows="10" id="txt_protocolo" class="textarea input_text"></textarea><br />
                            <button type="button" name="btn_enviar_msn" id="btn_enviar_msn">Enviar</button>
                            <a href='fale_conosco.php'>Voltar ao formulário</a>
                        </div>
                    </div>
            <?php }?>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <div class="contactArea contato no-scroll-x" <?php echo ($msg_erro_existe_protocolo) ? "style='display:none;'" : "";?>>
        <div class="formArea formulario_contato">
            <form id="frmFaleConosco" class="<?php echo $class_form;?>" enctype="multipart/form-data" method="POST">

                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-2">
                        <div class="field">
                            <div class="form-group">
                                <input type='radio' class="tipo" name='tipo' id='tipo' value="C" <?=($tipo == "C" || empty($tipo)) ? "checked" : ""?> /> &nbsp;Consumidor
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="field">
                            <div class="form-group">
                                <input type='radio' class="tipo" name='tipo' id='tipo' value="D" <?=($tipo == "D") ? "checked" : ""?> /> &nbsp;Distribuidor
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field">
                            <div class="form-group">
                                <input type='radio' class="tipo" name='tipo' id='tipo' value="M" <?=($tipo == "M") ? "checked" : ""?> /> &nbsp;Mercado Especializado
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                </div>

                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-4">
                        <div class="field">
                            <div class="form-group <?=(in_array('canal', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                <label class='control-label' for='canal'><span class="span_assunto">*</span> Canal</label>
                                <select name="canal" class="" id="canal">
                                    <option value="">Selecione o Canal da sua mensagem</option>
                                    <?php 
                                        foreach ($array_canal as $key => $value) {
                                           $selected = ($_POST["canal"] == $value["hd_classificacao"]) ? "selected" : "";
                                           echo '<option '.$selected .' data-obriga_campos="'.$value["obriga_campos"].'" value="'.$value["hd_classificacao"].'">'.utf8_encode($value["descricao"]).'</option>';
                                        }
                                    ?>
                                </select> 
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field">
                            <div class="pull-right campos_obg">Campos (*) são obrigatórios</div>
                            
                            <div class="form-group <?=(in_array('assunto', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                <label class='control-label' for='assunto'><span class="span_assunto">*</span> Assunto</label>
                                <select name="assunto" class="" id="assunto">
                                    <option value="">Selecione o Assunto da sua mensagem</option>
                                    <?php 
                                        foreach ($array_assunto as $value) {
                                           $selected = ($_POST["assunto"] == $value) ? "selected" : "";
                                           echo '<option '.$selected .' value="'.$value.'">'.$value.'</option>';
                                        }
                                    ?>
                                </select> 
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                </div>
                
                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8">
                        <div class="field">
                            <div class="form-group <?=(in_array('consumidor_nome', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                <label class="control-label" for="consumidor_nome"><span class="span_nome">*</span> Nome Completo</label>
                                <input type='text' class="" name='consumidor_nome' id='consumidor_nome' value="<?php echo (isset($_POST["consumidor_nome"]) && strlen($_POST["consumidor_nome"]) > 0) ? $_POST["consumidor_nome"]  : "";?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                </div>

                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-4">
                        <div class="field">
                        <div class="form-group <?=(in_array('cpf', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="cpf" id="label_cpf"><span class="span_cpf">*</span> CPF/CNPJ</label>
                            <input type='text' class="" name='cpf' id='cpf' value="<?php echo (isset($_POST["cpf"]) && strlen($_POST["cpf"]) > 0) ? $_POST["cpf"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                  
                    <div class="col-sm-4 dt_ns">
                        <div class="field">
                        <div class="form-group ">
                            <label class="control-label" for="data_nascimento"><span class="span_nascimento"></span> Data Nascimento</label>
                            <input type='text' class="" placeholder='dd/mm/aaaa' name='data_nascimento' id='data_nascimento' value="<?php echo (isset($_POST["data_nascimento"]) && strlen($_POST["data_nascimento"]) > 0) ? $_POST["data_nascimento"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                </div>
                
                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-5">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_email', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_email"><span class="span_email">*</span> E-mail</label>
                            <input type='email' class="" name='consumidor_email' id='consumidor_email' value="<?php echo (isset($_POST["consumidor_email"]) && strlen($_POST["consumidor_email"]) > 0) ? $_POST["consumidor_email"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                  
                    <div class="col-sm-3">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_celular', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_celular"><span class="span_celular">*</span> Celular</label>
                            <input type='text' class=" telefone" placeholder='(00) 00000-0000' name='consumidor_celular' id='consumidor_celular' value="<?php echo (isset($_POST["consumidor_celular"]) && strlen($_POST["consumidor_celular"]) > 0) ? $_POST["consumidor_celular"] : "";?>"/>
                        </div>
                        </div>
                    </div>

                    <div class="col-sm-2"></div>
                </div>

                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-2">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_cep', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_cep"><span class="span_cep">*</span> CEP  <a class="tooltips" href="#"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span><span class="tips">Ao digitar o CEP o endereço será preenchido automaticamente</span></a></label>
                            <input type='text' class="" name='consumidor_cep' id='consumidor_cep' value="<?php echo (isset($_POST["consumidor_cep"]) && strlen($_POST["consumidor_cep"]) > 0) ? $_POST["consumidor_cep"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_endereco', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_endereco"><span class="span_endereco">*</span> Endereço</label>
                            <input type='text' class="" name='consumidor_endereco' id='consumidor_endereco' value="<?php echo (isset($_POST["consumidor_endereco"]) && strlen($_POST["consumidor_endereco"]) > 0) ? $_POST["consumidor_endereco"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_numero', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_numero"><span class="span_numero">*</span> Número</label>
                            <input type='text' class="" maxlength="5" name='consumidor_numero' id='consumidor_numero' value="<?php echo (isset($_POST["consumidor_numero"]) && strlen($_POST["consumidor_numero"]) > 0) ? $_POST["consumidor_numero"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8">
                        <div class="field">
                        <div class="form-group">
                            <label class="control-label" for="consumidor_complemento"><span class="span_bairro"></span> Complemento</label>
                            <input type='text' class="" name='consumidor_complemento' id='consumidor_complemento' maxlength="40" value="<?php echo (isset($_POST["consumidor_complemento"]) && strlen($_POST["consumidor_complemento"]) > 0) ? $_POST["consumidor_complemento"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-2"></div>
                </div>
                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-3">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_bairro', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_bairro"><span class="span_bairro">*</span> Bairro</label>
                            <input type='text' class="" name='consumidor_bairro' id='consumidor_bairro' value="<?php echo (isset($_POST["consumidor_bairro"]) && strlen($_POST["consumidor_bairro"]) > 0) ? $_POST["consumidor_bairro"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_cidade', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_cidade"><span class="span_cidade">*</span> Cidade</label>
                            <input type='text' class="" name='consumidor_cidade' id='consumidor_cidade' value="<?php echo (isset($_POST["consumidor_cidade"]) && strlen($_POST["consumidor_cidade"]) > 0) ? $_POST["consumidor_cidade"] : "";?>"/>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <div class="field">
                        <div class="form-group <?=(in_array('consumidor_estado', $msg_erro['campos'])) ? "has-error" : "" ?>">
                            <label class="control-label" for="consumidor_estado"><span class="span_uf">*</span> UF</label>
                            <select id="consumidor_estado" class="" name="consumidor_estado">
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
                </div>

                    <!-- Multi Produtos -->
                <div id='id_multi'>

                    <div class='row'>

                        <div class='col-sm-2'></div>

                        <div class='col-sm-4'>
                            <div class="form-group" <?=(in_array('produto_descricao', $msg_erro['campos'])) ? "has-error" : "" ?>">
                                <label class='control-label' for='produto_descricao_multi'>Produto <a class="tooltips" href="#"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span><span class="tips">Você pode incluir mais de um produto.</span></a></label>
                                <input type="text" id="produto_descricao_multi" name="produto_descricao_multi" class="" value="<? echo $produto_descricao ?>" >
                            </div>
                        </div>

                        <div class='col-sm-2'>
                            <div class="form-group">
                                <label class='control-label' for='produto_descricao_multi'>Defeito Reclamado</label>
                                <select name="defeito_reclamado" id="defeito_reclamado">
                                    <option value="">Selecione</option>
                                </select>
                            </div>
                        </div>

                        <div class='col-sm-2'>
                            <label>&nbsp;</label>
                            <input type='button' name='adicionar' id='adicionar' value='Adicionar' class='btn btn-success' onclick='addIt();' style="width: 100%;">
                        </div>

                        <div class='col-sm-2'></div>

                    </div>

                    

                    <div class='row'>

                        <div class='col-sm-2'></div>

                        <div class='col-sm-8'>
                            <label class='control-label'>
                                (Selecione o produto e clique em <strong>Adicionar</strong>)
                            </label>

                            <select multiple size='5' id="PickList" name="PickList[]" >

                            <?php
                                if (count($lista_produtos)>0){
                                    for ($i=0; $i<count($lista_produtos); $i++){
                                        $linha_prod = $lista_produtos[$i];
                                        echo "<option value='".$linha_prod."'>".$linha_prod."</option>";
                                    }
                                }
                            ?>

                            </select>

                        <!--     <p>
                                <input type="button" value="Remover" onclick="deletaItens();" class='btn btn-danger largo' style="width: 126px;">
                            </p>
 -->
                        </div>

                        <div class='col-sm-2'></div>

                    </div>

                </div>
                
                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8">
                        <div class="field">
                        <div class="form-group <?=(in_array('mensagem', $msg_erro['campos'])) ? "has-error" : "" ?>">
                        <label class='control-label' for='mensagem'><span class="span_mensagem">*</span> Mensagem</label>
                        <textarea rows="5" class="" id="mensagem" name="mensagem" onkeyup="limite_textarea(this.value)"><?php echo (isset($_POST["mensagem"]) && strlen($_POST["mensagem"]) > 0) ? $_POST["mensagem"] : "";?></textarea>
                        <span id="caracteres">0</span> <span id='digito'>Digitado </span> <br>
                        <script>
                           function limite_textarea(valor) {
                                quant = 50;
                                total = valor.length;
                                document.getElementById('caracteres').innerHTML = valor.length;
                                if(total > 0){
                                    $('#digito').html('Digitados');
                                }else{
                                    $('#digito').html('Digitado');
                                }
                            }
                        </script>
                    </div>
                    </div>
                    </div>
                    <div class="col-sm-2"></div>
                </div>
                <div class="row">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8" id="div_anexo_nf">
                        <label for="anexo_nf" >Para agilizar o atendimento recomendamos que anexe a foto da NF do produto</label>
                        <input type="file" class="" name="anexo_nf" id="anexo_nf" />
                        <span class='texto_anexo'>Anexar arquivos nas extensções: (JPG, PNG, PDF ou GIF)</span>
                    </div>
                    <div class="col-sm-2"></div>
                </div><br/><br/>

                <div class='row'>
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8" align="center" >
                        <p class="tac">
                            <input type="submit" class="btn btn-info btn-roxo largo" value="Enviar" name="btn_submit" onclick="selIt();" >
                        </p>
                    </div>
                    <div class="col-sm-2"></div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Script -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<!-- jQuery UI -->
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js?v=<?php echo date("YmHis");?>"></script>
<script type="text/javascript" src="../../admin/js/jquery.mask.js?v=<?php echo date("YmHis");?>"></script>
<script src='../../plugins/select2/select2.js'></script>    

<script type="text/javascript">
$(document).on('click', ".select2-selection__choice__remove", function () {
    var title = $(this).parent().attr('title');
    $('#PickList option[value="' + title + '"]').remove();
    $("#PickList").change();
});

$(function(){
    $('#PickList').select2();

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
    $.datepicker.regional['pt-BR'] = {
        closeText: 'Fechar',
        prevText: '&#x3c;Anterior',
        nextText: 'Pr&oacute;ximo&#x3e;',
        currentText: 'Hoje',
        monthNames: ['Janeiro','Fevereiro','Mar&ccedil;o','Abril','Maio','Junho',
        'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
        monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun',
        'Jul','Ago','Set','Out','Nov','Dez'],
        dayNames: ['Domingo','Segunda-feira','Ter&ccedil;a-feira','Quarta-feira','Quinta-feira','Sexta-feira','S&aacute;bado'],
        dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','S&aacute;b'],
        dayNamesMin: ['Dom','Seg','Ter','Qua','Qui','Sex','S&aacute;b'],
        weekHeader: 'Sm',
        dateFormat: 'dd/mm/yy',
        firstDay: 0,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''};
        $.datepicker.setDefaults($.datepicker.regional['pt-BR']);
    $("#data_nascimento").datepicker().mask("00/00/0000");
    $("#qtde").mask("00");
    
    var options = {
        onKeyPress : function(cpfcnpj, e, field, options) {
            var masks = ['000.000.000-000', '00.000.000/0000-00'];
            cpfcnpj = cpfcnpj.replace("/[^0-9]/","");
            var mask = (cpfcnpj.length > 14) ? masks[1] : masks[0];
            $('#cpf').mask(mask, options);
            if (cpfcnpj.length > 14) {
                $(".dt_ns").hide();
            } else if ($("input[name='tipo']:checked").val() != "M") {
                $(".dt_ns").show();
            }
        }
    };

    $("#btn_enviar_msn").click(function(){
      
        var protocolo         = $("#protocolo");
        var txt_protocolo     = $("#txt_protocolo");

        if (protocolo.val() == "") {
            alert("Protocolo não encontrado.");
            $(protocolo).focus();
            return false;
        } else if (txt_protocolo.val() == "") {
            alert("Digite uma mensagem!");
            $(txt_protocolo).focus();
            return false;
        } else {
            var dados = {
                        'ajax_interage': true,
                        'txt_protocolo': txt_protocolo.val(),
                        'protocolo': protocolo.val()
                        };
            $.ajax({
                type: "POST",
                url:  "fale_conosco.php",
                data:  dados,
                dataType : "json",
                cache: false,
                complete: function(resposta){

                    data = $.parseJSON(resposta.responseText);

                    if (data.erro == true) {
                        $("#men_retorno").html(data.msn);
                        $("#men_retorno").addClass('alert');
                        $("#men_retorno").addClass('alert-danger');
                    } else {
                        $("#men_retorno").html(data.msn);
                        $("#men_retorno").addClass('alert');
                        $("#men_retorno").addClass('alert-success');
                        setTimeout(function(){
                            window.location.href = "fale_conosco.php";
                        }, 1000);
                    }
                  
                }
            });
        }
    });

    <?php if (strlen($cpf) > 11) { ?>
        $('#cpf').mask('00.000.000/0000-00', options);
        $("#data_nascimento").val('');
        $(".dt_ns").hide();
    <?php } else { ?>
        $('#cpf').mask('000.000.000-000', options);
        $("#data_nascimento").val('');
        $(".dt_ns").show();
    <?php } ?>

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

    $( "#produto_referencia_multi, #produto_descricao_multi").autocomplete({
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
            var descricao_produto = ui.item.label;
            $('#produto_descricao_multi').val(descricao_produto);
            //$("#qtde").val(1);
            var vetor = descricao_produto.split(":");
            carrega_defeito_reclamado(vetor[0]);
            return false;
        }
    });

    var singleSelect = true;  // Allows an item to be selected once only
    var sortSelect = true;  // Only effective if above flag set to true
    var sortPick = true;  // Will order the picklist in sort sequence

    var phoneMask = function() {
        if($(this).val().match(/^\(0/)) {
                $(this).val('(');
                return;
            }
            if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
                $(this).mask('(00) 0000-0000'); /* M?cara default */
            } else {
                $(this).mask('(00) 00000-0000');  // 9? D?ito
            }
            $(this).keyup(phoneMask);
    };
    $('.telefone').keyup(phoneMask);

    $('#consumidor_numero').keyup(function() {
        $(this).val(this.value.replace(/[^0-9.]/g, ''));
    });

    $(".tipo").click(function() {
        if ($(this).val() == "M") {
            $("#label_cpf").html('').html("* CNPJ")
            $("#data_nascimento").val('')
            $(".dt_ns").hide()
        } else {
            $("#label_cpf").html('').html("* CPF/CNPJ")
            let cnpf_cpf = $("#cpf").val()
            cnpf_cpf = cnpf_cpf.replace("/[^0-9]/","")
            if (cnpf_cpf.length < 14) {
                $(".dt_ns").show()   
            }
        }
    })

});
    function carrega_defeito_reclamado(referencia) {
        if (referencia == "") {
            return false;
        }

        $.ajax({
                async: true,
                url: "fale_conosco.php",
                type: "POST",
                dataType: "JSON",
                data: {
                    ajax_carrega_defeito: true,
                    referencia: referencia
                },
                beforeSend: function() {
                    $("#defeito_reclamado").prop("disabled","disabled");
                    $("#defeito_reclamado").html("<option value=''>Carregando ...</option>");
                },
                success: function(data) {
                    $("#defeito_reclamado").removeAttr("disabled");
                    $("#defeito_reclamado").html("");
                    if (data.length == 0) {
                        $("#defeito_reclamado").html("<option value=''>Nenhum defeito encontrado</option>");
                    } else {
                        var opt = "<option value=''>Selecione um Defeito</option>";
                        for (var i = 0; i < data.length; i++) {
                            opt += "<option value='"+data[i]["defeito"]+"'>"+data[i]["descricao"]+"</option>";
                        }
                        $("#defeito_reclamado").html(opt);
                    }
                }
        });



    }
 // Initialise - invoked on load
    function initIt() {
      var pickList = document.getElementById("PickList");
      var pickOptions = pickList.options;
      pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
    }

    // Adds a selected item into the picklist
    function addIt() {

        var fabrica = <?=$login_fabrica?>;

        if ($('#produto_descricao_multi').val()=='') {
            alert("Digite o nome do produto");
            $('#produto_descricao_multi').focus();
            return false;
        }

        if ($('#defeito_reclamado option:selected').val()=='') {
            alert("Selecione um Defeito");
            $('#defeito_reclamado').focus();
            return false;
        }

        var pickList = document.getElementById("PickList");
        var pickOptions = pickList.options;
        var pickOLength = pickOptions.length;

        pickOptions[pickOLength] = new Option($('#produto_descricao_multi').val()+" : "+ $('#defeito_reclamado option:selected').val() +" : "+ $('#defeito_reclamado option:selected').text(), $('#produto_descricao_multi').val()+" : "+ $('#defeito_reclamado option:selected').val() +" : "+ $('#defeito_reclamado option:selected').text(), true, false);

        $('#defeito_reclamado').val("");
        $('#produto_descricao_multi').val("");

        pickOLength = pickOptions.length;

        var optionsSelect2 = $("#PickList").find("option");

        $("#PickList").each(function(){
            $(this).children("option").each(function(){
                $(this).prop('selected', true);

            });
        });

        $("#PickList").change();
    }

 
    /*--------------------------------------*/
    // Deletes an item from the picklist
    function delIt() {
      var pickList = document.getElementById("PickList");
      var pickIndex = pickList.selectedIndex;
      var pickOptions = pickList.options;
      while (pickIndex > -1) {
        pickOptions[pickIndex] = null;
        pickIndex = pickList.selectedIndex;
      }
    }

    // Selection - invoked on submit
    function selIt(btn) {
        var pickList = document.getElementById("PickList");
        if (pickList == null) return true;
        var pickOptions = pickList.options;
        var pickOLength = pickOptions.length;

        for (var i = 0; i < pickOLength; i++) {
            pickOptions[i].selected = true;
        }
    /*  return true;*/
    }


function valida_campos(){
    var obriga_campos = $("#assunto > option:selected").data("obriga_campos");

    if (obriga_campos == "f"){
        $(".span_cpf, .span_nascimento, .span_email, .span_cep, .span_endereco, .span_numero, .span_bairro, .span_cidade, .span_uf").hide();
    }else{
        $(".span_nome, .span_cpf, .span_nascimento, .span_email, .span_celular, .span_cep, .span_endereco, .span_numero, .span_bairro, .span_cidade, .span_uf, .span_mensagem").show();
    }
}

function busca_cep(cep,method){
    var img = $("<img />", { src: "../../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });
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

$(document).ready(function() {
    if ($("input[name='tipo']:checked").val() == "M") {
        $("#label_cpf").html('').html("* CNPJ")
        $("#data_nascimento").val('');
        $(".dt_ns").hide();
    } else {
        $("#label_cpf").html('').html("* CPF/CNPJ")
        $(".dt_ns").show()
    }

    let cnpf_cpf = $("#cpf").val()
    cnpf_cpf = cnpf_cpf.replace("/[^0-9]/","")
    if (cnpf_cpf.length > 14) {
        $(".dt_ns").hide();
    } else if ($("input[name='tipo']:checked").val() != "M") {
        $(".dt_ns").show()   
    }
})

</script>
</body>
</html>
