<?php
$admin_privilegios = "call_center";
$layout_menu       = "callcenter";
$title             = "CADASTRO DE SOLICITAÇÃO DE CHEQUE";
$plugins           = array("datepicker", "mask", "price_format", "shadowbox", "ajaxform", "fancyzoom");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../classes/cep.php';
include_once "../class/tdocs.class.php";
include_once "../class/communicator.class.php";


$tipo_anexo = array("Calculo", "NF", "Ticket", "Outros Anexos 1", "Outros Anexos 2");

$solicitacao_cheque = $_REQUEST['solicitacao_cheque'];

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'busca_cep') {
        $cep = $_POST['cep'];

        try {
            $retorno = CEP::consulta($cep);
            $retorno = array("ok" => array_map('utf8_encode', $retorno));
        } catch(Exception $e) {
            $retorno = array("error" => utf8_encode($e->getMessage()));
        }

        exit(json_encode($retorno));
    }elseif ($_POST['action'] == 'deleta_cheque_item') {
        $solicitacao_cheque_item = $_POST['id'];
        pg_query($con, "DELETE FROM tbl_solicitacao_cheque_item WHERE solicitacao_cheque_item = {$solicitacao_cheque_item}");
        if (strlen(pg_last_error()) > 0) {
            exit(json_encode(array("error" => 'Erro ao tentar deletar um registro')));
        }
        exit(json_encode(array("ok" => 'Registro deletado com sucesso')));
    }elseif ($_POST['action'] == 'retorna_numero_extenso') {
        $numero = str_replace(array('.', ','), array('', '.'), $_POST['numero']);
        exit(json_encode(array("ok" => utf8_encode(numero_por_extenso($numero, true)))));
    }
}

/*if (isset($_POST['btn_acao']) && $_POST['btn_acao'] == 'cadastrar_consumidor') {
    $campos_erro = array();
    foreach ($_POST as $key => $value) {
        if ($key == 'complemento') {
            continue;
        }
        if (empty($value)) {
            $campos_erro[] = $key;
        }else{
            $_POST[$key] = utf8_decode($value);
        }
    }

    if (count($campos_erro)) {
        exit(json_encode(array("error" => utf8_encode("Preencha os campos obrigatórios"), "campos" => $campos_erro)));
    }else{
        $cidade = retira_acentos($_POST['cidade']);
        $res = pg_query($con, "SELECT cidade FROM tbl_cidade WHERE nome ilike '$cidade' AND estado = upper('$uf')");

        $complemento  = (empty($_POST['complemento'])) ? 'null' : "'".$_POST['complemento']."'";
        $cidade       = pg_fetch_result($res, 0, 'cidade');
        $cep          = str_replace('-', '', $cep);
        $razao_social = $_POST['razao_social'];
        $endereco     = $_POST['endereco'];
        $bairro       = $_POST['bairro'];

        $sql = "INSERT INTO tbl_fornecedor (
                    nome,
                    endereco,
                    numero,
                    bairro,
                    complemento,
                    cidade,
                    cnpj,
                    ie,
                    cep
                ) VALUES (
                    '$razao_social',
                    '$endereco',
                    $numero,
                    '$bairro',
                    $complemento,
                    $cidade,
                    '$cnpj_cpf',
                    '$inscricao_estadual',
                    '$cep'
                )";
        pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            exit(json_encode(array("error" => "Ocorreu um erro ao tentar cadastrar um novo fornecedor")));
        }
        exit(json_encode(array("ok" => "Fornecedor cadastrado com sucesso")));
    }
}*/

if (isset($_POST['verifica_reincidencia']) && $_POST['verifica_reincidencia'] == 'sim') {
    if (isset($_GET['solicitacao_cheque']) && $_GET['solicitacao_cheque'] != '') {
        exit(json_encode(array("error" => 'Solicitacao nao encontrada')));
    }
    $remover = array(".","/","-");
    $cpf_cnpj = str_replace($remover,"",$_POST['cpf_cnpj']);
    unset($array_solicitacao);

    $sql_fornecedor = " SELECT sc.solicitacao_cheque, sc.numero_solicitacao 
                        FROM tbl_solicitacao_cheque sc 
                        JOIN tbl_fornecedor f ON sc.fornecedor = f.fornecedor 
                        WHERE f.cnpj = '{$cpf_cnpj}' 
                        AND sc.fabrica = {$login_fabrica}

                        and sc.solicitacao_cheque not in 
                        (SELECT tbl_solicitacao_cheque_acao.solicitacao_cheque
                        FROM tbl_solicitacao_cheque_acao 
                        WHERE tbl_solicitacao_cheque_acao.tipo_acao = 'desativado')

                        ORDER BY solicitacao_cheque ";
    $res_fornecedor = pg_query($con, $sql_fornecedor);
    if (pg_num_rows($res_fornecedor) > 0) {
        for ($s=0; $s < pg_num_rows($res_fornecedor); $s++) { 
            $array_solicitacao['solicitacao_cheque'][] = pg_fetch_result($res_fornecedor, $s, 'solicitacao_cheque'); 
            $array_solicitacao['numero_solicitacao'][] = pg_fetch_result($res_fornecedor, $s, 'numero_solicitacao'); 
        }
        $ultimo = end($array_solicitacao['numero_solicitacao']);
        exit(json_encode(array("ok" => 'Solicitacao reincidente', "solicitacao" => $array_solicitacao['solicitacao_cheque'], "numero" => $array_solicitacao['numero_solicitacao'], "ultimo" => $ultimo) ));              
    } else {
        exit(json_encode(array("error" => 'Solicitacao nao encontrada')));
    }
}        

$tdocs = new TDocs($con, $login_fabrica, 'cheque');

if (isset($_POST['ajax_anexo_upload'])) {
    $posicao = $_POST['anexo_posicao'];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
    $extx = $ext;

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($arquivo['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx'));
        } else {
            $tdocs_id = $tdocs->sendFile($arquivo);
            if($tdocs_id){
                if($ext == 'pdf'){
                    $link = 'imagens/pdf_icone.png';
                    $thumbLink = 'imagens/pdf_icone.png';
                } else if(in_array($ext, array('doc', 'docx'))) {
                    $link = 'imagens/docx_icone.png';
                    $thumbLink = 'imagens/docx_icone.png';
                } else {
                    $link = $tdocs->thumb;
                    $thumbLink = $tdocs->thumb;

                    if ($arquivo['size'] > 11077840) {
                        $thumbLink = 'imagens/visualizarAnexo.png';
                    }
                }

                if (!strlen($link)) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
                } else {
                    $retorno = array(
                        'link'         => $thumbLink,
                        'arquivo_nome' => $arquivo['name'],
                        'href'         => $link,
                        'ext'          => $ext,
                        'tdocs_id'     => $tdocs_id,
                        'size'         => $arquivo['size']
                    );
                }
            }else{
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
            }
        }
    } else {
        #$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
    }

    $retorno['posicao'] = $posicao;

    exit(json_encode($retorno));
}

if (isset($_POST['btn_acao_hidden']) && $_POST['btn_acao_hidden'] == 'cadastrar') {
    
    $msg_erro         = '';
    $campos_erro      = array();
    $nao_valida       = array('qtde_linha_info', 'qtde_linha_val', 'complemento', 'lupa_config', 'solicitacao_cheque', 'protocolo_id', 'produto_referencia', 'produto_id', 'produto_descricao', 'numero_bo', 'justificativa','campos_id_midia', 'status_pecas', 'peca_referencia', 'pecas_status', 'produto_30_dias', 'descricao_referencia', 'pecas_descricao', 'os1', 'numero_processo', 'os2', 'hd_chamado', 'motivo_devolucao', 'texto_justificatica', 'n_reincidente');
    $tipo_solicitacao = explode('_', $tipo_solicitacao);

    $tipo             = strtolower($tipo_solicitacao[1]);
    $tipo_solicitacao = $tipo_solicitacao[0];

    if($tipo_solicitacao == 47 OR $tipo_solicitacao == 49){
        $chave = (array_keys($nao_valida, "produto_descricao"));
        unset($nao_valida[$chave[0]]);
        $chave = (array_keys($nao_valida, "produto_referencia"));
        unset($nao_valida[$chave[0]]);
        $chave = (array_keys($nao_valida, "motivo_devolucao"));
        unset($nao_valida[$chave[0]]);
    }

    if (empty($_POST['produto_referencia']) && empty($_POST['produto_descricao'])) {
        $_POST['produto_id'] = "";   
    }
    $produto_id = $_POST['produto_id'];
    $protocolo_id = $_POST['protocolo_id'];
    $motivo_devolucao   = $_POST['motivo_devolucao'];


    if(empty($motivo_devolucao)){
        $motivo_devolucao = 'null';
    }    
    $numero_bo          = $_POST['numero_bo'];
    $justificativa      = $_POST["justificativa"];

    $arr = array("'", '"');
    $campos_adicionais = array();

    //busca o campos adicionais
    if (isset($_REQUEST['solicitacao_cheque']) && !empty($_REQUEST['solicitacao_cheque'])) {
        $msg = 'alterar';
        $solicitacao_cheque = $_REQUEST['solicitacao_cheque'];

        $sql_solicitacao_cheque = "SELECT campos_adicionais FROM tbl_solicitacao_cheque Where fabrica = $login_fabrica and solicitacao_cheque = $solicitacao_cheque";
        $res_solicitacao_cheque = pg_query($con, $sql_solicitacao_cheque);
        if(pg_num_rows($res_solicitacao_cheque)>0){
            $campos_adicionais = json_decode(pg_fetch_result($res_solicitacao_cheque, 0, 'campos_adicionais'), true);
        }
    }else{
        $n_reincidente = trim($_POST["n_reincidente"]);
        if(!empty($n_reincidente)){
            $campos_adicionais['ultimo_reincidente'] = $n_reincidente;
        }
    }

    if($campos_adicionais['reincidente'] == 'true'){
        $cnpj_cpf = trim($_POST["cnpj_cpf"]);
        $sql_reincidente = "SELECT sc.solicitacao_cheque, sc.numero_solicitacao 
                        FROM tbl_solicitacao_cheque sc 
                        JOIN tbl_fornecedor f ON sc.fornecedor = f.fornecedor 
                        WHERE f.cnpj = '$cnpj_cpf' 
                        AND sc.fabrica = $login_fabrica 
                        and sc.solicitacao_cheque < $solicitacao_cheque 

                        and sc.solicitacao_cheque not in 
                        (SELECT tbl_solicitacao_cheque_acao.solicitacao_cheque
                        FROM tbl_solicitacao_cheque_acao 
                        WHERE tbl_solicitacao_cheque_acao.tipo_acao = 'desativado')

                        order by sc.solicitacao_cheque desc limit 1"; 
        $res_reincidente = pg_query($con, $sql_reincidente);
        if(pg_num_rows($res_reincidente)>0){
            $numero_solicitacao = pg_fetch_result($res_reincidente, 0, 'numero_solicitacao');
            $campos_adicionais['reincidente'] = true;
            $campos_adicionais['ultimo_reincidente'] = $numero_solicitacao;
        }else{
            unset($campos_adicionais['reincidente']);
            unset($campos_adicionais['ultimo_reincidente']);
        }
    }

    $justificativa = str_replace($arr, "", $justificativa);

    $campos_id_midia    = $_POST['campos_id_midia'];
    $midia              = $_POST['midia'];

    $pecas_status       = $_POST['pecas_status'];
    $produto_30_dias    = $_POST["produto_30_dias"];

    $pecas_descricao    = $_POST['pecas_descricao'];

    $hd_chamado         = $_POST['hd_chamado'];

    $os1                = $_POST['os1'];
    $os2                = $_POST['os2'];

    $numero_processo    = $_POST['numero_processo'];

    if(!empty($numero_processo)){
        $campos_adicionais['numero_processo']= utf8_encode($numero_processo);
    }

    if ($login_fabrica == 1 && $_POST['texto_justificatica'] != '') {
        $texto_justificatica = str_replace("'","",$_POST['texto_justificatica']);
        $campos_adicionais['justificativa'] = utf8_encode($texto_justificatica);
        $campos_adicionais['reincidente'] = true;        
    }

    $sql_motivo_devolucao = "SELECT campos_front FROM tbl_motivo_devolucao where motivo_devolucao = $motivo_devolucao and fabrica = $login_fabrica ";
    $res_motivo_devolucao = pg_query($con, $sql_motivo_devolucao);
    if(pg_num_rows($res_motivo_devolucao)>0){
        $campos_front = json_decode(pg_fetch_result($res_motivo_devolucao, 0, campos_front), true);
    }

    if(!empty($produto_id)){
        $sqlProduto = " SELECT tbl_produto.produto, 
                tbl_produto.referencia as referencia_produto,
                tbl_produto.descricao as descricao_produto
                from tbl_produto 
                where produto = $produto_id
                and fabrica_i = $login_fabrica;";
        $resProduto = pg_query($con, $sqlProduto);
        if(pg_num_rows($resProduto)>0){ 
            $produto = pg_fetch_result($resProduto, 0, produto);
            $referencia_produto = pg_fetch_result($resProduto, 0, referencia_produto);
            $descricao_produto = pg_fetch_result($resProduto, 0, descricao_produto);
        }
    }

    //fazer o if por campos_front
    if($campos_front['campos'][0] == 'id'){
        $campos_adicionais['id_midia'] = $campos_id_midia;    
        $campos_adicionais['midia'] = $midia;  

        if($midia == 'reclame_aqui'){
            $chave = (array_keys($nao_valida, "campos_id_midia"));
            unset($nao_valida[$chave[0]]);
        }  
        $chave = (array_keys($nao_valida, "midia"));
        unset($nao_valida[$chave[0]]);        
    }
    
    if($campos_front['campos'][0] == 'os'){
        $campos_adicionais['os1'] = $os1;   
        $campos_adicionais['os2'] = $os2;   
        
        $chave = (array_keys($nao_valida, "os1"));
        unset($nao_valida[$chave[0]]);     
        $chave = (array_keys($nao_valida, "os2"));
        unset($nao_valida[$chave[0]]);     
    }

    if($campos_front['campos'][0] == 'justificativa'){
        $campos_adicionais['justificativa'] = utf8_encode($justificativa);           
        $chave = (array_keys($nao_valida, "justificativa"));
        unset($nao_valida[$chave[0]]);
    }

    if($campos_front['campos'][0] == 'pecas'){
        $campos_adicionais['pecas'] = $pecas_status;  
        
        $chave = (array_keys($nao_valida, "pecas_status"));
        unset($nao_valida[$chave[0]]);
    }

    if($campos_front['campos'][0] == "produto_30_dias"){

        if(strlen(trim($produto_30_dias))==0){
            $campos_erro[] = 'produto_30_dias';
        }
        $campos_adicionais['produto_30_dias'] = utf8_encode($produto_30_dias);            
    }

    if($campos_front['campos'][0] == 'descricao_pecas'){
        $campos_adicionais['descricao_pecas'] = $pecas_descricao;  
        
        $chave = (array_keys($nao_valida, "pecas_descricao"));
        unset($nao_valida[$chave[0]]);
    }

    if(!empty($produto_id)){
        $campos_adicionais['produto']   = $produto_id;
    }

    if(!empty($protocolo_id)){
        $campos_adicionais['protocolo'] = $protocolo_id; 
    }

    if(!empty($hd_chamado)){
        $sqlProtocolo = "SELECT * from tbl_hd_chamado where hd_chamado = $hd_chamado and fabrica = $login_fabrica";
        $resProtocolo = pg_query($con, $sqlProtocolo);
        if(pg_num_rows($resProtocolo) == 0){
            $msg_erro .= "Número de chamado inválido. <Br>"; 
            $campos_erro[] = "hd_chamado";
        }else{
            $campos_adicionais['hd_chamado'] = $hd_chamado; 
        }
    }

    if ($tipo == 'consumidor') {
        $nao_valida = array_merge($nao_valida, array('codigo_posto', 'razao_social_posto', 'posto'));
    }elseif($tipo == 'posto'){
        $nao_valida = array_merge($nao_valida, array('razao_social', 'cnpj_cpf', 'fornecedor'));
    }

    /* VALIDA CAMPOS OBRIGATÓRIOS */
    $linha_info = array();
    $linha_val  = array();
    $cont_info  = 0;
    $cont_val   = 0;

    foreach ($_POST as $key => $value) {
        if (in_array($key, $nao_valida) || strpos($key, 'anexo') !== false) {
            continue;
        }
        if ((empty($value) || $value == '0,00') && strpos($key, 'solicitacao_cheque_item') === false) {
            $campos_erro[] = $key;
        }

        if (strpos($key, '_doc') !== false || strpos($key, '_val') !== false) {
            $key_aux = explode('_', $key);
            if (is_numeric(end($key_aux))) {
                array_pop($key_aux);
            }
            $key = implode('_', $key_aux);

            if (count($linha_info[$cont_info]) == 5) {
                $cont_info += 1;
            }
            if (count($linha_val[$cont_val]) == 6) {
                $cont_val += 1;
            }

            if (strpos($key, '_doc') !== false) {
                $linha_info[$cont_info][$key] = $value;
            }elseif (strpos($key, '_val') !== false) {
                $linha_val[$cont_val][$key] = $value;
            }
        }

    }
   
    if($campos_front['campos'][0] == 'numero_bo'){
        if (empty($_POST['numero_bo'])) {
            $campos_erro[] = 'numero_bo';  
        } else {
            $campos_adicionais['numero_bo'] = $numero_bo;

            $sql = "SELECT advertencia FROM tbl_advertencia WHERE advertencia = $numero_bo and fabrica = $login_fabrica ";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res)==0){
                $msg_erro .= "Número de B.O inválido. <br>";
            }
        }
    }

    if ($_POST['tipo_solicitacao'] == '46_consumidor') {
        $vazio = [];
        
        if (empty($_POST['numero_processo'])) {
            $campos_erro[] = 'numero_processo';
        }

        foreach ($_POST['anexo'] as $ky => $vlue) {
            if (strlen(trim($vlue)) == 0) {
                $vazio[] = 1;
            }
        }    

        if (count($vazio) == 5) {
            $campos_erro[] = 'div_anexo_0'; 
        }
    }

    if (count($campos_erro)) {
        if(empty($msg_erro)){
            $msg_erro .= 'Preencha os campos obrigatórios';
        }
    }else{

        if($login_fabrica == 1){
            $campos_adicionais['supervisao_cheque'] = 'pendente';
            $campos_adicionais['gerencia'] = 'pendente';
        }
           
        $campos_adicionais = json_encode($campos_adicionais, JSON_UNESCAPED_UNICODE);

        
        try{
            $verifica_data = explode('/', $vencimento);
            if (!checkdate($verifica_data[1], $verifica_data[0], $verifica_data[2])){
                $campos_erro[] = 'vencimento';
                throw new Exception("Data de Vencimento inválida");
            } else {
                $xvencimento = $verifica_data[2]."-".$verifica_data[1]."-".$verifica_data[0];
            }

            pg_query($con, 'BEGIN');

            $posto      = (!isset($posto) || empty($posto)) ? 'null' : $posto;
            $fornecedor = (!isset($fornecedor) || empty($fornecedor)) ? 'null' : $fornecedor;
            $valor_liquido  = str_replace(array('.', ','), array('', '.'), $valor_liquido);
            $historico = pg_escape_string($_POST['historico']);

            $motivo_alteracao = pg_escape_literal($con,$_POST['motivo_alteracao']);

            if (isset($_REQUEST['solicitacao_cheque']) && !empty($_REQUEST['solicitacao_cheque'])) {
                $msg = 'alterar';
                $solicitacao_cheque = $_REQUEST['solicitacao_cheque'];

                $sql = "UPDATE tbl_solicitacao_cheque SET
                            tipo_solicitacao = {$tipo_solicitacao},
                            posto = {$posto},
                            fornecedor = {$fornecedor},
                            componente_solicitante = {$componente_solicitante},
                            vencimento = '{$xvencimento}',
                            valor_liquido = {$valor_liquido},
                            valor_liquido_extenso = '{$valor_por_extenso}',
                            campos_adicionais = '$campos_adicionais',
                            motivo_devolucao = $motivo_devolucao,
                            historico = '{$historico}'
                        WHERE fabrica = {$login_fabrica} AND solicitacao_cheque = {$solicitacao_cheque}
                        RETURNING solicitacao_cheque";

            }else{
                $msg = 'cadastrar';
                $sql = "INSERT INTO tbl_solicitacao_cheque(
                            fabrica,
                            admin,
                            posto,
                            fornecedor,
                            tipo_solicitacao,
                            componente_solicitante,
                            vencimento,
                            valor_liquido,
                            valor_liquido_extenso,
                            historico,
                            campos_adicionais,
                            motivo_devolucao,
                            numero_solicitacao
                        )VALUES(
                            {$login_fabrica},
                            {$login_admin},
                            {$posto},
                            {$fornecedor},
                            {$tipo_solicitacao},
                            {$componente_solicitante},
                            '{$xvencimento}',
                            {$valor_liquido},
                            '{$valor_por_extenso}',
                            '{$historico}',
                            '$campos_adicionais',
                            $motivo_devolucao,
                            (SELECT max(numero_solicitacao) + 1 FROM tbl_solicitacao_cheque)
                        ) RETURNING solicitacao_cheque";  //exit($sql);
            }

           
            $res = pg_query($con, $sql);
          
            if (strlen(pg_last_error()) > 0) {
              
                throw new Exception("Erro ao tentar $msg uma nova solicitação de cheque");
            }
            $solicitacao_cheque = pg_fetch_result($res, 0, 'solicitacao_cheque');
            $res_delete = pg_query($con, "DELETE FROM tbl_solicitacao_cheque_acao WHERE solicitacao_cheque = $solicitacao_cheque");
            if (strlen(pg_last_error()) > 0) {
               
                throw new Exception("Erro ao tentar $msg uma nova solicitação de cheque");
            }

            if (pg_affected_rows($res_delete) !== 0){
                if (isset($_REQUEST['aprovado']) && $_REQUEST['aprovado'] == 'a' && $msg == 'alterar') {
                    $sqlMotivoAlteracao = "INSERT INTO tbl_solicitacao_cheque_acao (
                        solicitacao_cheque,
                        admin_acao,
                        tipo_acao,
                        motivo,
                        data_acao
                    ) VALUES (
                        $solicitacao_cheque,
                        {$login_admin},
                        'alteracao_pos_aprovado',
                        {$motivo_alteracao},
                        current_timestamp
                    )";

                    pg_query($con,$sqlMotivoAlteracao);
                }

                /* Pega o login do admin que realizou a ação */
                $sql = "SELECT
                            login
                        FROM tbl_admin WHERE fabrica = {$login_fabrica}
                            AND admin = $login_admin";
                $res = pg_query($con, $sql);
                $login = pg_fetch_result($res, 0, 'login');

                $sql_numero = "SELECT numero_solicitacao FROM tbl_solicitacao_cheque WHERE solicitacao_cheque = {$solicitacao_cheque}";
                $res_numero = pg_query($con, $sql_numero);
                $numero     = pg_fetch_result($res_numero, 0, 'numero_solicitacao');

                if (empty($numero)) {
                    $numero = $solicitacao_cheque;
                }

                /* ENVIA EMAIL PARA O GRUPO CADASTRADO PARA RECEBER AS NOTIFICAÇÕES DAS SOLICITAÇÕES DE CHEQUE */
                $sql = "SELECT
                            email
                        FROM tbl_admin WHERE fabrica = {$login_fabrica}
                            AND JSON_FIELD('solicitacao_cheque', parametros_adicionais) = 't'";
                $res_admin = pg_query($con, $sql);
                if (pg_num_rows($res_admin) > 0) {
                    $msg_email = '
                        Foi alterado e esta disponível para aprovação uma solicitação de cheque reembolso<br /><br />
                        <strong>Solicitação:</strong> '.$numero.'<br />
                        <strong>Componente Solicitante:</strong> '.$componente_solicitante.'<br />
                        <strong>Vencimento:</strong> '.implode('/', array_reverse(explode('-', $vencimento))).'<br />
                        <strong>Valor Líquido:</strong> '.$valor_liquido.'<br />
                        <strong>Alterado pelo Admin:</strong> '.$login;

                    $mailTc = new TcComm($externalId);
                    for ($i = 0; $i < pg_num_rows($res_admin); $i++) {
                        $email = pg_fetch_result($res_admin, $i, 'email');

                        $mailTc->sendMail(
                            $email,
                            'Solicitação de Cheque - Pendente de aprovação',
                            $msg_email
                        );
                    }
                }
            }

            pg_prepare($con, 'insere_solicitacao_item',
                "INSERT INTO tbl_solicitacao_cheque_item(
                    solicitacao_cheque,
                    numero,
                    valor_bruto,
                    valor_liquido,
                    conta_ger,
                    conta_sub,
                    conta_comp,
                    valor,
                    tipo,
                    observacao
                )VALUES({$solicitacao_cheque}, $1, $2, $3, $4, $5, $6, $7, $8, $9)");
            pg_prepare($con, 'altera_solicitacao_item',
                "UPDATE tbl_solicitacao_cheque_item SET
                    numero = $2,
                    valor_bruto = $3,
                    valor_liquido = $4,
                    conta_ger = $5,
                    conta_sub = $6,
                    conta_comp = $7,
                    valor = $8,
                    tipo = $9,
                    observacao = $10
                WHERE solicitacao_cheque_item = $1");

            for ($i = 0; $i < count($linha_info); $i++) {
                $valor_bruto   = str_replace(array('.', ','), array('', '.'), $linha_info[$i]['valor_bruto_doc']);
                $valor_liquido = str_replace(array('.', ','), array('', '.'), $linha_info[$i]['valor_liquido_doc']);

                $insert1 = array(
                        $linha_info[$i]['numero_doc'],
                        $valor_bruto,
                        $valor_liquido,
                        null,
                        null,
                        null,
                        null,
                        'valor_1',
                        pg_escape_string($linha_info[$i]['valor_observacao_doc'])
                    );

                if (isset($linha_info[$i]['solicitacao_cheque_item_doc']) && !empty($linha_info[$i]['solicitacao_cheque_item_doc'])) {
                    $execute = 'altera_solicitacao_item';
                    $msg     = 'alterar';
                    array_unshift($insert1,$linha_info[$i]['solicitacao_cheque_item_doc']);
                }else{
                    $execute = 'insere_solicitacao_item';
                    $msg     = 'cadastrar';
                }

                pg_execute($con, $execute, $insert1);
                if (strlen(pg_last_error()) > 0) {
                    $erro_insert = 1;
                    break;
                }
            }

            if ($erro_insert == 1) {
               
                throw new Exception("Erro ao tentar $msg uma nova solicitação de cheque");
            }

            for ($i = 0; $i < count($linha_val); $i++) {
                $valor = str_replace(array('.', ','), array('', '.'), $linha_val[$i]['valor_val']);
                $insert2 = array(
                        $linha_val[$i]['numero_val'],
                        null,
                        null,
                        $linha_val[$i]['ger_val'],
                        $linha_val[$i]['sub_val'],
                        $linha_val[$i]['comp_val'],
                        $valor,
                        'valor_2',
                        null
                    );
                if (isset($linha_val[$i]['solicitacao_cheque_item_val']) && !empty($linha_val[$i]['solicitacao_cheque_item_val'])) {
                    $execute = 'altera_solicitacao_item';
                    $msg     = 'alterar';
                    array_unshift($insert2,$linha_val[$i]['solicitacao_cheque_item_val']);
                }else{
                    $execute = 'insere_solicitacao_item';
                    $msg     = 'cadastrar';
                }
                pg_execute($con, $execute, $insert2);
                if (strlen(pg_last_error()) > 0) {
                    $erro_insert = 1;
                    break;
                }
            }

            if ($erro_insert == 1) {
                 
                throw new Exception("Erro ao tentar $msg uma nova solicitação de cheque");
            }

            foreach ($anexo as $count => $img) {
                if (empty($img) || empty($_POST['anexo_tdocs_'.$count])) { continue; }

                $fileInfo = array(
                    'tdocs_id' => $_POST['anexo_tdocs_'.$count],
                    'name'     => utf8_encode($img),
                    'size'     => $_POST['anexo_size_'.$count],
                    'tipo_anexo' => $_POST['anexo_tipo_'.$count]
                );

                $t_tipo_anexo = $_POST['anexo_tipo_'.$count]; 
                $json_file_info = json_encode($fileInfo);

                if(strlen(trim($json_file_info)) == 0 ){
                    throw new Exception("Falha ao gravar anexo do tipo ".$t_tipo_anexo." na posição ". ($count+1) ." por favor verifique o nome do arquivo");
                }

                if(!$tdocs->setDocumentReference($fileInfo, $solicitacao_cheque, 'anexar', false)){
                    throw new Exception("Erro ao tentar anexar as imagens selecionadas");
                    break;
                }

                if(strlen(trim($_POST['anexo_tdocs_'.$count]))>0){
                    $sql_upd = "UPDATE tbl_tdocs set situacao = 'inativo' WHERE tdocs_id = '" . $_POST['anexo_tdocs_id_antiga_'.$count]. "' and fabrica = $login_fabrica and referencia_id = '$solicitacao_cheque' ";    
                    $res_upd = pg_query($con, $sql_upd); 
                }
            }

            pg_query($con, 'COMMIT');
            
            if (isset($_REQUEST['solicitacao_cheque']) && !empty($_REQUEST['solicitacao_cheque'])) {
                $param = "cadastro=true&solicitacao_cheque=$solicitacao_cheque";
            }else{
                $sql = "SELECT
                            login
                        FROM tbl_admin WHERE fabrica = {$login_fabrica}
                            AND admin = $login_admin";
                $res = pg_query($con, $sql);
                $login = pg_fetch_result($res, 0, 'login');

                $sql_numero = "SELECT numero_solicitacao FROM tbl_solicitacao_cheque WHERE solicitacao_cheque = {$solicitacao_cheque}";
                $res_numero = pg_query($con, $sql_numero);
                $numero     = pg_fetch_result($res_numero, 0, 'numero_solicitacao');

                if (empty($numero)) {
                    $numero = $solicitacao_cheque;
                }

                $sql = "SELECT
                            email
                        FROM tbl_admin WHERE fabrica = {$login_fabrica}
						and ativo
                            AND JSON_FIELD('solicitacao_cheque', parametros_adicionais) = 't'";
                $res = pg_query($con, $sql);
                if (pg_num_rows($res) > 0) {
                    $mailTc = new TcComm($externalId);
                    for ($i = 0; $i < pg_num_rows($res); $i++) { 
                        $email = pg_fetch_result($res, $i, 'email');
                        
                        $mailTc->sendMail(
                            $email,
                            'Solicitação de Cheque',
                            'Foi cadastrada uma nova solicitação de cheque<br /><br />
                            <strong>Solicitação:</strong> '.$numero.'<br />
                            <strong>Componente Solicitante:</strong> '.$componente_solicitante.'<br />
                            <strong>Vencimento:</strong> '.implode('/', array_reverse(explode('-', $vencimento))).'<br />
                            <strong>Valor Líquido:</strong> '.$valor_liquido.'<br />
                            <strong>Admin responsável:</strong> '.$login
                        );
                    }
                }

                $param = "cadastro=$solicitacao_cheque";
            }
            header("Location: cad_solicitacao_cheque.php?{$param}");
        }catch(Exception $e){
            pg_query($con, 'ROLLBACK');
            $msg_erro = $e->getMessage();
        }
    }
}

include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';

if (isset($_GET['solicitacao_cheque']) && !empty($_GET['solicitacao_cheque'])) {
    $solicitacao = $_GET['solicitacao_cheque'];

    $sql = "SELECT
                tbl_solicitacao_cheque.solicitacao_cheque,
                tbl_solicitacao_cheque.posto,
                tbl_posto.nome AS razao_social_posto,
                tbl_posto_fabrica.codigo_posto,
                tbl_tipo_solicitacao.tipo_solicitacao,
                tbl_solicitacao_cheque.vencimento,
                tbl_solicitacao_cheque.valor_liquido,
                tbl_solicitacao_cheque.valor_liquido_extenso AS valor_por_extenso,
                tbl_solicitacao_cheque.componente_solicitante,
                tbl_solicitacao_cheque.historico,
                tbl_solicitacao_cheque.motivo_devolucao,  
                tbl_solicitacao_cheque.campos_adicionais, 
                tbl_fornecedor.fornecedor,
                tbl_fornecedor.nome AS razao_social,
                tbl_fornecedor.endereco,
                tbl_fornecedor.numero,
                tbl_fornecedor.bairro,
                tbl_fornecedor.complemento,
                tbl_fornecedor.cidade,
                tbl_fornecedor.cnpj AS cnpj_cpf,
                tbl_fornecedor.ie,
                tbl_fornecedor.cep
            FROM tbl_solicitacao_cheque
                LEFT JOIN tbl_posto ON(tbl_posto.posto = tbl_solicitacao_cheque.posto)
                LEFT JOIN tbl_posto_fabrica ON(tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica})
                JOIN tbl_tipo_solicitacao ON(tbl_tipo_solicitacao.tipo_solicitacao = tbl_solicitacao_cheque.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica})
                LEFT JOIN tbl_fornecedor ON(tbl_fornecedor.fornecedor = tbl_solicitacao_cheque.fornecedor)
                
            WHERE tbl_solicitacao_cheque.fabrica = {$login_fabrica}
                AND tbl_solicitacao_cheque.solicitacao_cheque = {$solicitacao}";

    $res = pg_query($con, $sql);

    $num_rows = pg_num_rows($res);
    if ($num_rows > 0) {
        extract(pg_fetch_all($res)[0]);

        $razao_social = strtoupper($razao_social);
        $razao_social_posto = strtoupper($razao_social_posto);
        $valor_liquido = priceFormat($valor_liquido);

        $campos_adicionais = json_decode($campos_adicionais, true);

        $produto_id = $campos_adicionais['produto'];
        $protocolo_id = $campos_adicionais['protocolo']; 
        $numero_bo = $campos_adicionais['numero_bo'];
        $justificativa = utf8_decode($campos_adicionais['justificativa']);

        $numero_processo = utf8_decode($campos_adicionais['numero_processo']);

        $campos_id_midia = $campos_adicionais['id_midia'];
        $midia = $campos_adicionais['midia'];

        $hd_chamado = $campos_adicionais['hd_chamado'];

        $produto_30_dias = $campos_adicionais['produto_30_dias'];

        $pecas_status = $campos_adicionais['pecas'];

        $os1 = $campos_adicionais['os1'];
        $os2 = $campos_adicionais['os2'];

        $pecas_descricao = $campos_adicionais['descricao_pecas'];

        $vencimento = implode('/', array_reverse(explode('-', $vencimento)));

        $res_itens = pg_query($con, "SELECT solicitacao_cheque_item, numero, valor_bruto, valor_liquido, conta_ger, conta_sub, conta_comp, valor, tipo, observacao FROM tbl_solicitacao_cheque_item WHERE solicitacao_cheque = {$solicitacao}");

        if(!empty($produto_id)){
            $sqlProduto = " SELECT tbl_produto.produto, 
                    tbl_produto.referencia as referencia_produto,
                    tbl_produto.descricao as descricao_produto
                    from tbl_produto 
                    where produto = $produto_id
                    and fabrica_i = $login_fabrica;";
            $resProduto = pg_query($con, $sqlProduto);
            if(pg_num_rows($resProduto)>0){ 
                $produto = pg_fetch_result($resProduto, 0, produto);
                $referencia_produto = pg_fetch_result($resProduto, 0, referencia_produto);
                $descricao_produto = pg_fetch_result($resProduto, 0, descricao_produto);
            }
        }

        $cont_info = $cont_val = 0;
        for ($i = 0; $i < pg_num_rows($res_itens); $i++) {
            $valor_liquido_doc = pg_fetch_result($res_itens, $i, 'valor_liquido');

            if (!empty($valor_liquido_doc)) {
                $linha_info[$cont_info]['solicitacao_cheque_item_doc'] = pg_fetch_result($res_itens, $i, 'solicitacao_cheque_item');
                $linha_info[$cont_info]['numero_doc']        = pg_fetch_result($res_itens, $i, 'numero');
                $linha_info[$cont_info]['valor_bruto_doc']   = priceFormat(pg_fetch_result($res_itens, $i, 'valor_bruto'));
                $linha_info[$cont_info]['valor_liquido_doc'] = priceFormat(pg_fetch_result($res_itens, $i, 'valor_liquido'));
                $linha_info[$cont_info]['valor_observacao_doc'] = pg_fetch_result($res_itens, $i, 'observacao');
                $cont_info += 1;
            }else {
                $linha_val[$cont_val]['solicitacao_cheque_item_val'] = pg_fetch_result($res_itens, $i, 'solicitacao_cheque_item');
                $linha_val[$cont_val]['numero_val'] = pg_fetch_result($res_itens, $i, 'numero');
                $linha_val[$cont_val]['ger_val']    = pg_fetch_result($res_itens, $i, 'conta_ger');
                $linha_val[$cont_val]['sub_val']    = pg_fetch_result($res_itens, $i, 'conta_sub');
                $linha_val[$cont_val]['comp_val']   = pg_fetch_result($res_itens, $i, 'conta_comp');
                $linha_val[$cont_val]['valor_val']  = priceFormat(pg_fetch_result($res_itens, $i, 'valor'));
                $cont_val += 1;
            }


        }

    }else{
        $msg_erro = 'Não foi possível localizar a solicitação informada';
    }
}
if (isset($_GET['cadastro'])) {
    if (isset($_GET['solicitacao_cheque'])) {
        $msg_ok = 'alterado';
        $solicitacao_ok = $_GET['solicitacao_cheque'];
    }else{
        $msg_ok = 'realizado';
        $solicitacao_ok = $_GET['cadastro'];
    }
    $sql = "SELECT numero_solicitacao FROM tbl_solicitacao_cheque WHERE solicitacao_cheque = {$solicitacao_ok}";
    $res = pg_query($con, $sql);
    $numero = pg_fetch_result($res, 0, 'numero_solicitacao');

    if (empty($numero)) {
        $numero = $solicitacao_cheque;
    }

    $msg_ok = "Solicitação Nº $numero - Cadastro $msg_ok com sucesso";
}
?>
<div id="alertas_tela">
    <?php if (!empty($msg_erro)) { ?>
    <div class="alert alert-danger"><h4><?=$msg_erro;?></h4></div>
    <?php } ?>
    <?php if (!empty($msg_ok)) { ?>
    <div class="alert alert-success"><h4><?=$msg_ok;?></h4></div>
    <?php } ?>
</div>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_cad_solicitacao_cheque" method="post" action="cad_solicitacao_cheque.php">
    <input type="hidden" name="solicitacao_cheque" value="<?=$_REQUEST['solicitacao_cheque']?>">
    <div class="titulo_tabela">Tipo de Solicitação</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('tipo_solicitacao', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for=''>Tipo de Solicitação</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <select name="tipo_solicitacao">
                        <option value=""></option>
                        <?php
                        $sql = "SELECT
                                    tipo_solicitacao,
                                    descricao,
                                    informacoes_adicionais
                                FROM tbl_tipo_solicitacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY 2;";
                        $res = pg_query($con, $sql);
                        for ($i = 0; $i < pg_num_rows($res); $i++) {
                            $descricao              = pg_fetch_result($res, $i, 'descricao');
                            $informacoes_adicionais = pg_fetch_result($res, $i, 'informacoes_adicionais');
                            $tipo_solicitacao_aux   = pg_fetch_result($res, $i, 'tipo_solicitacao');

                            $selected = ($tipo_solicitacao == $tipo_solicitacao_aux) ? 'selected' : '';
                            echo "<option value='{$tipo_solicitacao_aux}_{$informacoes_adicionais}' {$selected}>{$descricao}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span4">
            <div style='display: none;' class="protocolo control-group <?=(in_array('protocolo', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for=''>Protocolo</label>

                <div class='controls controls-row'>
                    <input type='text' name='protocolo_id' value='<?=$protocolo_id?>'>
                </div>
            </div>

            <div style='display: none;' class="hd_chamado control-group <?=(in_array('hd_chamado', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for=''>Informe Nº Chamado</label>

                <div class='controls controls-row'>
                    <input type='text' name='hd_chamado' value='<?=$hd_chamado?>'>
                </div>
            </div>
        </div>
    </div>
    <div class="div_numero_processo" style='display: none;'>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group <?=(in_array('numero_processo', $campos_erro)) ? 'error' : '';?>">
                    <label class="control-label" for=''>Número Processo</label>
                    <div class='controls controls-row'>
                        <h5 class="asteristico">*</h5>
                        <input type="text" name="numero_processo" value="<?=$numero_processo?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $hidden = (isset($_GET['visualizar'])) ? 'display:none' : ''; ?>
    <div id="ficha_cadastral" style="display: none;">
        <br />
        <div class="titulo_tabela">Fornecedor</div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span6">
                <div class="control-group <?=(in_array('razao_social', $campos_erro) || in_array('fornecedor', $campos_erro)) ? 'error' : '';?>">
                    <label class="control-label" for='razao_social'>Razão Social</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <h5 class="asteristico">*</h5>
                            <input class="span12" type="text" name="razao_social" value="<?=$razao_social?>">
                            <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                            <input type="hidden" name="lupa_config" tipo="fornecedor" parametro="nome" />
                            <input type="hidden" name="fornecedor" id="fornecedor" value="<?=$fornecedor?>">
                        </div>
                        <button type="button" class="btn btn-info btn-small" name="btn_cadastrar_consumidor" style="<?=$hidden?>">Cadastrar</button>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group <?=(in_array('cnpj_cpf', $campos_erro) || in_array('fornecedor', $campos_erro)) ? 'error' : '';?>">
                    <label class="control-label" for='cnpj_cpf'>CNPJ/CPF</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <h5 class="asteristico">*</h5>
                            <input class="span12" type="text" name="cnpj_cpf" value="<?=$cnpj_cpf?>">
                            <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                            <input type="hidden" name="lupa_config" tipo="fornecedor" parametro="cpf" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="ficha_produto" style="display: none;">
        <br />
        <div class="titulo_tabela">Produto</div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array('produto_referencia', $campos_erro) || in_array('fornecedor', $campos_erro)) ? 'error' : '';?>'>
                    <label class='control-label'>Ref. Produto</label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <h5 class="asteristico">*</h5>
                                <input type="text" id="produto_referencia" name="produto_referencia" maxlength="20" value="<? echo $referencia_produto ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                                <input type="hidden" name="produto_id" id="produto_id" tipo="produto" parametro="produto_id" value="<?=$produto_id?>" />
                            </div>
                        </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group <?=(in_array('produto_descricao', $campos_erro) || in_array('fornecedor', $campos_erro)) ? 'error' : '';?>'>
                    <label class='control-label'>Descrição Produto</label><br>
                        <div class='controls controls-row input-append'>
                            <h5 class="asteristico">*</h5>
                            <input type="text" id="produto_descricao" name="produto_descricao" size="12" class='frm' value="<? echo $descricao_produto ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
    </div>

    <div id="ficha_motivo_devolucao" style="display: none;">
        <br />
        <div class="titulo_tabela">Motivo da Devolução</div>
        <br />
        <div class="row-fluid">
            <div class="span4"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array('motivo_devolucao', $campos_erro)) ? 'error' : '';?>'>
                    <label class='control-label'>Motivo</label><br>
                        <div class='controls controls-row input-append'>
                            <h5 class="asteristico">*</h5>
                            <select id="motivo_devolucao" name='motivo_devolucao'>
                                <option value="">Selecione um Motivo</option>
                                <?php 
                                $sqlMotivo = "SELECT * FROM tbl_motivo_devolucao where fabrica = $login_fabrica order by descricao ";
                                $resMotivo = pg_query($con, $sqlMotivo);

                                for($i=0; $i<pg_num_rows($resMotivo); $i++){
                                    $descricao          = pg_fetch_result($resMotivo, $i, descricao);
                                    $motivo_devolucao_db   = pg_fetch_result($resMotivo, $i, motivo_devolucao);
                                    $campos_front = pg_fetch_result($resMotivo, $i, campos_front);

                                    $campos_front = json_decode($campos_front, true);
                                    $campos = implode("|", $campos_front['campos']);

                                    if($motivo_devolucao_db == $motivo_devolucao){
                                        $selected = " selected ";
                                    }else{
                                        $selected = "";
                                    }

                                    echo "<option $selected value='$motivo_devolucao_db' data-campos='$campos'>$descricao</option>";
                                }

                                ?>
                                
                            </select>
                        </div>
                   </div>
            </div>
        </div>
        <div class='campos numero_bo' style='display: none'>
            <div class="row-fluid" >
                <div class="span4"></div>
                <div class='span4'>
                    <div class="control-group <?=(in_array('numero_bo', $campos_erro)) ? 'error' : '';?>">
                        <label class='control-label'>Número B.O</label><br>
                        <div class='controls controls-row input-append'>
                            <h5 class="asteristico">*</h5>
                            <input type="text" onkeyup="somenteNumeros(this);" name="numero_bo" pattern="[0-9]*" value="<?=$numero_bo?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='campos os' style='display: none'>
            <div class="row-fluid" >
                <div class="span2"></div>
                <div class='span4'>
                    <div class="control-group <?=(in_array('os1', $campos_erro)) ? 'error' : '';?>">
                        <label class='control-label'>O.S 1</label><br>
                        <div class='controls controls-row input-append'>
                            <input type="text" name="os1" maxlength="15" value="<?=$os1?>">
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class="control-group <?=(in_array('os2', $campos_erro)) ? 'error' : '';?>">
                        <label class='control-label'>O.S 2</label><br>
                        <div class='controls controls-row input-append'>
                            <input type="text" name="os2" maxlength="15" value="<?=$os2?>">
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
        </div>
        <div class='campos justificativa' style='display: none'>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class='span10'>
                    <div class="control-group <?=(in_array('justificativa', $campos_erro)) ? 'error' : '';?>">
                        <label class='span12 control-label'>Justificativa</label><br>
                        <div class='controls controls-row input-append'>
                            <textarea style="margin: 0px; width: 670px; height: 70px;" name="justificativa"><?=$justificativa?></textarea>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
        </div>
        <div class='campos id' style='display: none'>
            <div class="row-fluid" >
                <div class="span3"></div>
                <div class="span6">
                    <div class="control-group <?=(in_array('campos_id_midia', $campos_erro)) ? 'error' : '';?>">
                        
                        <input class="radio_midia" type="radio" name='midia' <?php if($midia == "facebook"){echo " checked ";}?> value="facebook"> <label class='control-label'>Facebook</label>
                        <input class="radio_midia" type="radio" name='midia' <?php if($midia == "fale_conosco"){echo " checked ";}?> value="fale_conosco"> <label class='control-label'>Fale Conosco</label>  
                        <input class="radio_midia" type="radio" name='midia' <?php if($midia == "reclame_aqui"){echo " checked ";}?> value="reclame_aqui"> <label class='control-label'>Reclame Aqui</label>
                        <input class="radio_midia" type="radio" name='midia' <?php if($midia == "youtube"){echo " checked ";}?> value="youtube"> <label class='control-label'>Youtube</label> 
                        
                    </div>
                </div>
                <div class="span3"></div>
            </div>
            <div class="row-fluid reclame_aqui" <?= ($midia != 'reclame_aqui') ? "style='display: none'" : "style='display: block'"; ?>>
                <div class="span4"></div>
                <div class='span4'>
                    <div class="control-group <?=(in_array('campos_id_midia', $campos_erro)) ? 'error' : '';?>">
                        <label class='control-label'>ID</label><br>
                        <div class='controls'>
                            <input type="text" name="campos_id_midia" value="<?=$campos_id_midia?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--<div class='campos produto_indisponivel' style='display: none'>
            <div class="row-fluid" >
                <div class="span2"></div>
                <div class="span8">
                    <input type="radio" name='produto_indisponivel' <?php if($produto_indisponivel == "atraso_no_embarque"){echo " checked ";} ?> value="atraso_no_embarque"> Atraso no Embarque  
                    <input type="radio" name='produto_indisponivel' <?php if($produto_indisponivel == "atraso_na_transportadora"){echo " checked ";} ?> value="atraso_na_transportadora"> Atraso na transportadora  
                    <input type="radio" name='produto_indisponivel' <?php if($produto_indisponivel == "item_com_divergência"){echo " checked ";} ?> value="item_com_divergência"> Item com Divergência                      
                </div>
                <div class="span2"></div>
            </div>
        </div>-->
        <div class='campos produto_30_dias' style='display: none'>
            <div class="row-fluid" >
                <div class="span2"></div>
                <div class="span8">
                    <div class='control-group <?=(in_array('produto_30_dias', $campos_erro)) ? 'error' : '';?>'>
<input type="radio" name='produto_30_dias' <?php if($produto_30_dias == "atraso_no_embarque"){echo " checked ";} ?> value="atraso_no_embarque"> <label class='control-label'>Atraso no Embarque  </label>
<input type="radio" name='produto_30_dias' <?php if($produto_30_dias == "atraso_na_transportadora"){echo " checked ";} ?> value="atraso_na_transportadora"> <label class='control-label'>Atraso na transportadora  </label>
<input type="radio" name='produto_30_dias' <?php if($produto_30_dias == "item_com_divergência"){echo " checked ";} ?> value="item_com_divergência"> <label class='control-label'>Item com Divergência    </label>                  
                </div>
            </div>
                <div class="span2"></div>
            </div>
        </div>
        <div class='campos pecas' style='display: none'>
            <div class="row-fluid" >
                <div class="span2"></div>
                <div class='span3'>
                    <div class='control-group'>
                    <label class='control-label' for='peca_referencia'>Ref. Peças</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="peca_referencia" name="peca_referencia" class='span12 peca' maxlength="20" value="<? echo $peca_referencia ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
                        </div>
                    </div>
                </div>
                </div>
                <div class='span3'>
                    <label class='control-label'>Status da Peças</label><br>
                    <div class='controls'>
                        <select name='status_pecas' class="span11 status_pecas">
                            <option value="">Status da Peça</option>
                            <option value="INDISPL">INDISPL</option>
                            <option value="SUBST">SUBST</option>
                            <option value="IMPINAT">IMPINAT</option>
                            <option value="OBSOLETO">OBSOLETO</option>
                            <option value="NLM">NLM</option>
                        </select>
                    </div>
                </div>
                <div class='span3'>
                    <div class='controls'>
                        <br>
                        <button type="button" class="btn btn-primary adicionar_peca_status">Adicionar</button>
                    </div>
                </div>
            </div>
            <div class="row-fluid" >
                <div class="span1"></div>
                <div class="span8">
                    <div class="control-group <?=(in_array('pecas_status', $campos_erro)) ? 'error' : '';?>">
                        Selecione a peça e clique em 'Adicionar'
                        <textarea name="pecas_status" class="pecas_status" style="width: 670px; height: 70px;" readonly="true"><?=$pecas_status?></textarea>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
        </div>
    </div>


    <div class='campos descricao_pecas' style='display: none'>
        <div class="row-fluid" >
            <div class="span4"></div>
            <div class='span3'>
                <div class='control-group'>
                <label class='control-label' for='peca_referencia'>Desc. Peças</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="descricao_referencia" name="descricao_referencia" class='span12 peca' maxlength="30" value="<?= $descricao_referencia ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
                    </div>
                </div>
            </div>
            </div>
            <div class='span3'>
                <div class='controls'>
                    <br>
                    <button type="button" class="btn btn-primary adicionar_descricao">Adicionar</button>
                </div>
            </div>
        </div>
        <div class="row-fluid" >
            <div class="span1"></div>
            <div class="span8">
                <div class="control-group <?=(in_array('pecas_descricao', $campos_erro)) ? 'error' : '';?>">
                    Selecione a peça e clique em 'Adicionar'
                    <textarea name="pecas_descricao" class="pecas_descricao" style="width: 670px; height: 70px;" readonly="true"><?=$pecas_descricao?></textarea>
                </div>
            </div>
            <div class="span1"></div>
        </div>
    </div>
    <div id="posto_autorizado" style="display: none;">
        <br />
        <div class="titulo_tabela">Posto Autorizado</div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span4">
                <div class="control-group <?=(in_array('codigo_posto', $campos_erro) || in_array('posto', $campos_erro)) ? 'error' : '';?>">
                    <label class="control-label" for='codigo_posto'>Código</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <h5 class="asteristico">*</h5>
                            <input class="span12" type="text" name="codigo_posto" value="<?=$codigo_posto?>">
                            <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                            <input type="hidden" name="posto" id="posto" value="<?=$posto?>">
                        </div>
                        <button type="button" class="btn btn-info btn-small" name="btn_cadastrar_posto" style="<?=$hidden?>">Cadastrar</button>
                    </div>
                </div>
            </div>
            <div class="span6">
                <div class="control-group <?=(in_array('razao_social_posto', $campos_erro) || in_array('posto', $campos_erro)) ? 'error' : '';?>">
                    <label class="control-label" for='razao_social_posto'>Nome</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <h5 class="asteristico">*</h5>
                            <input class="span12" type="text" name="razao_social_posto" value="<?=$razao_social_posto?>">
                            <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="titulo_tabela">Solicitação de Cheque</div>
    <br />
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <div class="control-group <?=(in_array('componente_solicitante', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='componente_solicitante'>Componente Solicitante</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="number" min="0" name="componente_solicitante" value="<?=(empty($componente_solicitante)) ? 0 : (!empty($_POST['componente_solicitante'])) ? $_POST['componente_solicitante'] : $componente_solicitante ?>">
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group <?=(in_array('vencimento', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='vencimento'>Vencimento</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="vencimento" id="vencimento" value="<?= (!empty($_POST['vencimento'])) ? $_POST['vencimento'] : $vencimento ?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('valor_liquido', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='valor_liquido'>Valor Líquido</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="valor_liquido" price="true" value="<?= (!empty($_POST['valor_liquido'])) ? $_POST['valor_liquido'] : $valor_liquido ?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
            <div class="control-group <?=(in_array('valor_por_extenso', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='valor_por_extenso'>Valor Líquido por Extenso</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="valor_por_extenso" value="<?=$valor_por_extenso?>" readonly>
                </div>
            </div>
        </div>
    </div>
    <?php
    $qtde_linha_info = (count($linha_info)) ? count($linha_info) : 1;
    ?>
    <input type="hidden" name="qtde_linha_info" id="qtde_linha_info" value="<?=$qtde_linha_info?>">
    <input type="hidden" name="solicitacao_cheque_item_doc_1" id="solicitacao_cheque_item_1" value="<?=$linha_info[0]['solicitacao_cheque_item_doc']?>">
    <div id="linha_info_1" class="row-fluid">
        <div class="span1"></div>
        <div class="span2">
            <div class="control-group <?=(in_array('numero_doc', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='numero_doc'>Número</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="number" name="numero_doc" value="<?=(!empty($_POST['numero_doc'])) ? $_POST['numero_doc'] : $linha_info[0]['numero_doc'] ?>">
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('valor_liquido_doc', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='valor_liquido_doc'>Valor Líquido</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="valor_liquido_doc" price="true" value="<?=(!empty($_POST['valor_liquido_doc'])) ? $_POST['valor_liquido_doc'] : $linha_info[0]['valor_liquido_doc'] ?>">
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('valor_bruto_doc', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='valor_bruto_doc'>Valor Bruto</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="valor_bruto_doc" price="true" value="<?=(!empty($_POST['valor_bruto_doc'])) ? $_POST['valor_bruto_doc'] : $linha_info[0]['valor_bruto_doc'] ?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('valor_observacao_doc', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='valor_observacao_doc'>Observação</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <?php $disabled = (isset($_GET['visualizar'])) ? 'disabled' : ''; ?>
                    <input class="span10" type="text" name="valor_observacao_doc" value="<?=(!empty($_POST['valor_observacao_doc'])) ? $_POST['valor_observacao_doc'] : $linha_info[0]['valor_observacao_doc'] ?>">
                    <button type="button" class="btn btn-success replica_linha" <?=$disabled?>>+</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    for ($i = 1; $i < $qtde_linha_info; $i++) {
        $num_aux              = $i + 1;
        $numero_doc           = 'numero_doc_'.$num_aux;
        $valor_liquido_doc    = 'valor_liquido_doc_'.$num_aux;
        $valor_bruto_doc      = 'valor_bruto_doc_'.$num_aux;
        $valor_observacao_doc = 'valor_observacao_doc_'.$num_aux;

    ?>
    <input type="hidden" name="solicitacao_cheque_item_doc_<?=$num_aux?>" id="solicitacao_cheque_item_<?=$num_aux?>" value="<?=$linha_info[$i]['solicitacao_cheque_item_doc']?>">
    <div id='linha_info_<?=$i+1?>' class="row-fluid">
        <div class="span1"></div>
        <div class="span2">
            <div class="control-group <?=(in_array($numero_doc, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$numero_doc?>'>Número</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="number" name="<?=$numero_doc?>" value="<?=(!empty($_POST[$numero_doc])) ? $_POST[$numero_doc] : $linha_info[$i]['numero_doc'] ?>">
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array($valor_liquido_doc, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$valor_liquido_doc?>'>Valor Líquido</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="<?=$valor_liquido_doc?>" price="true" value="<?=(!empty($_POST[$valor_liquido_doc])) ? $_POST[$valor_liquido_doc] : $linha_info[$i]['valor_liquido_doc'] ?>">
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array($valor_bruto_doc, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$valor_bruto_doc?>'>Valor Bruto</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="<?=$valor_bruto_doc?>" price="true" value="<?=(!empty($_POST[$valor_bruto_doc])) ? $_POST[$valor_bruto_doc] : $linha_info[$i]['valor_bruto_doc'] ?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array($valor_observacao_doc, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$valor_observacao_doc?>'>Observação</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span10" type="text" name="<?=$valor_observacao_doc?>" value="<?=(!empty($_POST[$valor_observacao_doc])) ? $_POST[$valor_observacao_doc] : $linha_info[$i]['valor_observacao_doc'] ?>">
                    <button type="button" class="btn btn-danger" name="<?='deleta_linha_'.$num_aux?>">-</button>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
            <div class="control-group <?=(in_array('historico', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='historico'>Histórico</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <textarea class="span12" type="text" name="historico"><?= (!empty($_POST['historico'])) ? $_POST["historico"] : $historico ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <?php
    $qtde_linha_val = (count($linha_val)) ? count($linha_val) : 1;
    ?>
    <br />
    <input type="hidden" name="solicitacao_cheque_item_val_1" id="solicitacao_cheque_item_val_1" value="<?=$linha_val[0]['solicitacao_cheque_item_val']?>">
    <!-- Não apagar o texto_justificatica pois Black usa-->
    <input type="hidden" name="texto_justificatica" >
    <input type="hidden" name="n_reincidente" id="n_reincidente" value="">
    <input type="hidden" name="qtde_linha_val" id="qtde_linha_val" value="<?=$qtde_linha_val?>">
    <div id="linha_val_1" class="row-fluid">
        <div class="span1"></div>
        <div class="span2">
            <div class="control-group <?=(in_array('numero_val', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='numero_val'>Número</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="number" name="numero_val" value="<?=(!empty($_POST['numero_val'])) ? $_POST['numero_val'] : $linha_val[0]['numero_val'] ?>">
                </div>
            </div>
        </div>
        <div class="span1">
            <div class="control-group <?=(in_array('ger_val', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='ger_val'>GER</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="ger_val" value="<?=(!empty($_POST['ger_val'])) ? $_POST['ger_val'] : $linha_val[0]['ger_val'] ?>">
                </div>
            </div>
        </div>
        <div class="span1">
            <div class="control-group <?=(in_array('sub_val', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='sub_val'>SUB</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="sub_val" value="<?=(!empty($_POST['sub_val'])) ? $_POST['sub_val'] : $linha_val[0]['sub_val'] ?>">
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('comp_val', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='comp_val'>COMP</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="comp_val" value="<?=(!empty($_POST['comp_val'])) ? $_POST['comp_val'] : $linha_val[0]['comp_val'] ?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('valor_val', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='valor_val'>Valor em R$</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span10" type="text" name="valor_val" price="true" value="<?=(!empty($_POST['valor_val'])) ? $_POST['valor_val'] : $linha_val[0]['valor_val'] ?>">
                    <?php $disabled = (isset($_GET['visualizar'])) ? 'disabled' : ''; ?>
                    <button type="button" class="btn btn-success replica_linha2" <?=$disabled?>>+</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    for ($i = 1; $i < $qtde_linha_val; $i++) {
        $num_aux    = $i + 1;
        $numero_val = "numero_val_$num_aux";
        $ger_val    = "ger_val_$num_aux";
        $sub_val    = "sub_val_$num_aux";
        $comp_val   = "comp_val_$num_aux";
        $valor_val  = "valor_val_$num_aux";
    ?>
    <input type="hidden" name="solicitacao_cheque_item_val_<?=$num_aux?>" id="solicitacao_cheque_item_val_<?=$num_aux?>" value="<?=$linha_val[$i]['solicitacao_cheque_item_val']?>">
    <div id="linha_val_<?=$num_aux?>" class="row-fluid">
        <div class="span1"></div>
        <div class="span2">
            <div class="control-group <?=(in_array($numero_val, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$numero_val?>'>Número</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="number" name="<?=$numero_val?>" value="<?=(!empty($_POST[$numero_val])) ? $_POST[$numero_val] : $linha_val[$i]['numero_val']?>">
                </div>
            </div>
        </div>
        <div class="span1">
            <div class="control-group <?=(in_array($ger_val, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$ger_val?>'>GER</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="<?=$ger_val?>" value="<?=(!empty($_POST[$ger_val])) ? $_POST[$ger_val] : $linha_val[$i]['ger_val']?>">
                </div>
            </div>
        </div>
        <div class="span1">
            <div class="control-group <?=(in_array($sub_val, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$sub_val?>'>SUB</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="<?=$sub_val?>" value="<?=(!empty($_POST[$sub_val])) ? $_POST[$sub_val] : $linha_val[$i]['sub_val']?>">
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array($comp_val, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$comp_val?>'>COMP</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="<?=$comp_val?>" value="<?=(!empty($_POST[$comp_val])) ? $_POST[$comp_val] : $linha_val[$i]['comp_val']?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array($valor_val, $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='<?=$valor_val?>'>Valor em R$</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span10" type="text" name="<?=$valor_val?>" price="true" value="<?=(!empty($_POST[$valor_val])) ? $_POST[$valor_val] : $linha_val[$i]['valor_val']?>">
                    <button type="button" class="btn btn-danger" name="<?='deleta_val_'.$num_aux?>">-</button>
                </div>
            </div>
        </div>
    </div>
    <?php } 

    if ($_REQUEST['aprovado'] == 'a') {
    ?>
        <br /><br />
        <div class="titulo_tabela">Motivo da Alteração</div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="control-group <?=(in_array('motivo_alteracao', $campos_erro)) ? 'error' : '';?>">
                    <label class="control-label" for='motivo'>Motivo</label>
                    <div class='controls controls-row'>
                        <h5 class="asteristico">*</h5>
                        <textarea class="span12" type="text" name="motivo_alteracao"><?=$_POST['motivo_alteracao']?></textarea>
                        <input type="hidden" name="aprovado" value="<?= $_REQUEST['aprovado'] ?>" />
                    </div>
                </div>
            </div>
        </div>
        <br />
    <?php 
    }
    ?>
    <br />
    <div class="titulo_tabela">Anexo(s)</div>
    <br />
    <?php
    $anexos      = array();
    $qtde_anexos = 5;

    if (isset($_REQUEST['solicitacao_cheque']) && !empty($_REQUEST['solicitacao_cheque'])) {
        $ret = $tdocs->getDocumentsByRef($_REQUEST['solicitacao_cheque']);

        if (count($ret->attachListInfo)) {

            foreach ($ret->attachListInfo as $array_file) {

                $key_tipo_anexo = array_search($array_file['extra']['tipo_anexo'] ,$tipo_anexo);

                $anexos[$key_tipo_anexo] = array(
                    'anexo_imagem' => $array_file['link'],
                    'size'         => $array_file['filesize'],
                    'anexo_aux'    => $array_file['filename'],
                    'tipo_anexo'   => $array_file['extra']['tipo_anexo'],
                    'tdocs_id'     => $array_file['tdocs_id']
                );
            }
        }
    }

    for ($i = 0; $i < $qtde_anexos; $i++) {
        $anexo_tdocs = $_POST["anexo_tdocs_$i"];
        if (!empty($anexo_tdocs)) {
            $anexos[] = array(
                'anexo_imagem' => $_POST["anexo_link_$i"],
                'size'         => $_POST["anexo_size_$i"],
                'anexo_aux'    => $anexo[$i],
                'anexo_tdocs'  => $anexo_tdocs
            );
        }
    }

    $xtipo_solicitacao = $_POST['tipo_solicitacao'];
    $xanex = $_POST['anexo'];
    $class_button = "btn-primary";
    $vazio = [];

    if ($xtipo_solicitacao == '46_consumidor') {

        foreach ($xanex as $key => $value) {
            if (strlen(trim($value)) == 0) {
                $vazio[] = 1;
            }
        }

        if (count($vazio) == 5) {
            $class_button = "btn-danger";
        }

    }

    for ($i = 0; $i < $qtde_anexos; $i++) {
        if ($class_button == 'btn-danger' && $i != 0) {
            $class_button = 'btn-primary';
        }

        $anexo_imagem = (isset($anexos[$i]['anexo_imagem'])) ? $anexos[$i]['anexo_imagem'] : "imagens/imagem_upload.png";
        $anexo_aux    = (isset($anexos[$i]['anexo_aux'])) ? $anexos[$i]['anexo_aux'] : "";
        $anexo_tdocs  = (isset($anexos[$i]['anexo_tdocs'])) ? $anexos[$i]['anexo_tdocs'] : null;
        $anexo_size   = (isset($anexos[$i]['size'])) ? $anexos[$i]['size'] : 0;

        $disabled = (isset($_GET['visualizar'])) ? 'disabled' : '';

        $nome_tipo_anexo = (!empty($anexos[$i]['tipo_anexo'])) ? $anexos[$i]['tipo_anexo'] : $tipo_anexo[$i]; 

        /* VALIDA SE FOR UM ARQUIVO DO TIPO PDF */
        $ext = strtolower(preg_replace("/.+\./", "", basename($anexo_imagem)));
        if ($ext == "pdf") { $anexo_imagem = "imagens/pdf_icone.png"; }

        ?>
        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
            <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
            <button type='button' class='btn btn-mini <?=$class_button?> btn-block class_anexo_<?=$i?>' name='anexar' rel='<?=$i?>' <?=$disabled?> ><?=$nome_tipo_anexo ?></button>
            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo_aux?>" />
            <input type="hidden" name="anexo_tdocs_<?=$i?>" value="<?=$anexo_tdocs?>">
            <input type="hidden" name="anexo_size_<?=$i?>" value="<?=$anexo_size?>">
            <input type="hidden" name="anexo_link_<?=$i?>" value="<?=$anexo_imagem?>">
            <input type="hidden" name="anexo_tdocs_id_antiga_<?=$i?>" value="<?=$anexos[$i]['tdocs_id']  ?>">
            <input type="hidden" name="anexo_tipo_<?=$i?>" value="<?=$tipo_anexo[$i]?>">
        </div>
        <?php
    }
    if (!isset($_GET['visualizar'])) {
    ?>
    <br /><br /><br />
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <?php 
                if (isset($solicitacao_cheque)) { ?>
                <button type="button" class="btn btn_acao" name="btn_acao" id='btn_acao' value="cadastrar">Alterar</button>
                <input type="hidden" name="btn_acao_hidden" value="cadastrar">
				<input type="hidden" name="solicitacao_cheque" value="<?=$solicitacao_cheque?>">
            <?php }else{ ?>
                <button type="button" class="btn btn_acao" name="btn_acao" id='btn_acao' value="cadastrar">Cadastrar</button>
                <input type="hidden" name="btn_acao_hidden" value="cadastrar">
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</form>
<?php
if ($qtde_anexos > 0) {
    for ($i = 0; $i < $qtde_anexos; $i++) {
    ?>
        <form name="form_anexo" method="post" action="cad_solicitacao_cheque.php" enctype="multipart/form-data" style="display: none;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />

            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php
    }
} ?>
<script type="text/javascript">
    $(function(){
        Shadowbox.init();
        $.datepickerLoad(['vencimento'], { dateFormat: "dd/mm/yy", minDate: <?=date('d/m/Y')?> });

        $("input[name=cep], input[name=cep2]").mask("99999-999",{ placeholder:"" });

        $("span[rel=lupa]").click(function() {
            //var parametros_lupa = [];
            //parametros_lupa.push("referencia_descricao");
            $.lupa($(this));
        });

        $("span[rel=lupa]").each(function() {
            $(this).next().attr({ referencia_descricao: true });
        });

        

        if($('.alert-danger, .alert-success').length){
            setTimeout(function(){
                $('#alertas_tela').fadeOut()
            }, 5000);
        }

        $('button[name=btn_cadastrar_posto]').on('click', function(){
            window.open('posto_cadastro.php');
        });

        $('button[name=btn_cadastrar_consumidor]').on('click', function(){
            window.open('fornecedor_cadastro.php');
        });

        $(".adicionar_descricao").click(function(){             

            var descricao_referencia    = $("#descricao_referencia").val();
            var pecas_descricao            = $(".pecas_descricao").val();

            if(descricao_referencia.length == 0){
                alert("Informe a descrição da peças.");
                return false;
            }

            if(descricao_referencia.length == 0){
                var dados = descricao_referencia + "\n";
            }else{
                var dados = pecas_descricao + descricao_referencia + "\n";
            }

            $(".pecas_descricao").val(dados);

        });


        $(".adicionar_peca_status").click(function(){             

            var status_pecas    = $(".status_pecas").val();
            var pecas_status    = $(".pecas_status").val();
            var peca            = $(".peca").val();


            if(peca.length == 0  || status_pecas.length == 0 ){
                alert("Informe a peça e o status.");
                return false;
            }

            if(pecas_status.length == 0){
                var dados = peca + " | " + status_pecas + "\n";
            }else{
                var dados = pecas_status + peca + " | " + status_pecas + "\n";
            }

            $(".pecas_status").val(dados);

        });

        $("select[name=motivo_devolucao]").on('change', function(){
            verifica_motivo_devolucao();
        });
        verifica_motivo_devolucao();

        $(".radio_midia").change(function(){
            var value = $("input[name='midia']:checked ").val();
            $(".reclame_aqui").hide();
            if(value == 'reclame_aqui'){
                $(".reclame_aqui").show();
            }
        });

        $('select[name=tipo_solicitacao]').on('change', function(){ verifica_ficha_cadastral(); });
        verifica_ficha_cadastral();

        $('.replica_linha').on('click', function(){
            var qtde         = parseInt($('#qtde_linha_info').val()) + 1;
            var ultima_linha = parseInt($('#qtde_linha_info').val());

            $('#linha_info_'+ultima_linha).after("<div id='linha_info_"+qtde+"' class='row-fluid'>"+$('#linha_info_1').html()+"</div>");

            $("#linha_info_"+qtde+" input").each(function(){
                var name = $(this).attr('name')+"_"+qtde;
                $(this).attr('name', name).val('');
            });

            $("#linha_info_"+qtde).append("<input type='hidden' name='solicitacao_cheque_item_doc_"+qtde+"' id='solicitacao_cheque_item_"+qtde+"'>");

            $('input[name=numero_doc_'+qtde+']').focus();
            $('input[name=valor_liquido_doc_'+qtde+'], input[name=valor_bruto_doc_'+qtde+']').priceFormat({
                prefix: '',
                thousandsSeparator: '.',
                centsSeparator: ',',
                centsLimit: parseInt(2)
            });

            $("#linha_info_"+qtde+" button").text('-').removeClass('replica_linha').removeClass('btn-success').attr('name','deleta_linha_'+qtde).addClass('btn-danger');
            $('#qtde_linha_info').val(qtde);
        });

        $('.replica_linha2').on('click', function(){
            var qtde         = parseInt($('#qtde_linha_val').val()) + 1;
            var ultima_linha = parseInt($('#qtde_linha_val').val());

            $('#linha_val_'+ultima_linha).after("<div id='linha_val_"+qtde+"' class='row-fluid'>"+$('#linha_val_1').html()+"</div>");

            $("#linha_val_"+qtde+" input").each(function(){
                var name = $(this).attr('name')+"_"+qtde;
                $(this).attr('name', name).val('');
            });

            $("#linha_val_"+qtde).append("<input type='hidden' name='solicitacao_cheque_item_val_"+qtde+"' id='solicitacao_cheque_item_val_"+qtde+"'>");

            $('input[name=numero_val_'+qtde+']').focus();
            $('input[name=valor_val_'+qtde+']').priceFormat({
                prefix: '',
                thousandsSeparator: '.',
                centsSeparator: ',',
                centsLimit: parseInt(2)
            });

            $("#linha_val_"+qtde+" button").text('-').removeClass('replica_linha2').removeClass('btn-success').attr('name','deleta_val_'+qtde).addClass('btn-danger');
            $('#qtde_linha_val').val(qtde);
        });

        $(document).on('click', 'button[name^=deleta_linha_]', function(){
            var qtde  = parseInt($('#qtde_linha_info').val()) - 1;
            var name  = $(this).attr('name').split('_');
            var id    = $('#solicitacao_cheque_item_'+qtde).val();

            if (id !== undefined && id.length !== 0) {
                if (confirm('Deseja realmente deletar esta linha? O mesmo será apagada da base de dados...')) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: { ajax: 'sim', action: 'deleta_cheque_item', id: id },
                        timeout: 8000
                    }).fail(function(){
                        alert('Ocorreu um erro ao tentar deletar a linha selecionada');
                    }).done(function(data){
                        data = JSON.parse(data);
                        if (data.ok !== undefined) {
                            $('#qtde_linha_info').val(qtde);
                            $('#linha_info_'+name[2]).remove();
                            $('#solicitacao_cheque_item_'+name[2]).remove();
                        }else{
                            alert('Ocorreu um erro ao tentar deletar a linha selecionada');
                        }
                    });
                }
            }else{
                $('#qtde_linha_info').val(qtde);
                $('#linha_info_'+name[2]).remove();
                $('#solicitacao_cheque_item_'+name[2]).remove();
            }


        });
        $(document).on('click', 'button[name^=deleta_val_]', function(){
            var qtde = parseInt($('#qtde_linha_val').val()) - 1;
            var name = $(this).attr('name').split('_');
            var id    = $('#solicitacao_cheque_item_val_'+qtde).val();

            if (id !== undefined && id.length !== 0) {
                if (confirm('Deseja realmente deletar esta linha? O mesmo será apagada da base de dados...')) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: { ajax: 'sim', action: 'deleta_cheque_item', id: id },
                        timeout: 8000
                    }).fail(function(){
                        alert('Ocorreu um erro ao tentar deletar a linha selecionada');
                    }).done(function(data){
                        data = JSON.parse(data);
                        if (data.ok !== undefined) {
                            $('#qtde_linha_val').val(qtde);
                            $('#linha_val_'+name[2]).remove();
                            $('#solicitacao_cheque_item_val_'+name[2]).remove();
                        }else{
                            alert('Ocorreu um erro ao tentar deletar a linha selecionada');
                        }
                    });
                }
            }else{
                $('#qtde_linha_val').val(qtde);
                $('#linha_val_'+name[2]).remove();
                $('#solicitacao_cheque_item_val_'+name[2]).remove();
            }

        });

        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");

            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        $('input[name=numero2]').focusout(function(){
            if ($('input[name=endereco2]').val() !== '') {
                $('input[name=valor_liquido]').focus();
            }
        });
        $('input[name=numero]').focusout(function(){
            if ($('input[name=endereco]').val() !== '') {
                $('input[name=complemento]').focus();
            }
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });
                    $('input[name=anexo_link_'+data.posicao).val(data.link);

                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo_"+data.posicao).prepend(link);

                    if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                        setupZoom();
                    }

                    $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
                $("#div_anexo_"+data.posicao).find("input[name=anexo_tdocs_"+data.posicao+"]").val(data.tdocs_id);
                $("#div_anexo_"+data.posicao).find("input[name=anexo_size_"+data.posicao+"]").val(data.size);
            }
        });
        $('input[name=valor_liquido]').blur(function(){
            if ($(this).val() !== '') {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: { ajax: 'sim', action: 'retorna_numero_extenso', numero: $(this).val() },
                    timeout: 5000
                }).fail(function(){
                    alert('Ocorreu um erro ao tentar escrever o número informado por extenso');
                }).done(function(data){
                    data = JSON.parse(data);
                    $('input[name=valor_por_extenso]').val(data.ok);
                });
            }
        });
    });

    function verifica_motivo_devolucao(){
        var campos = $('select[name=motivo_devolucao] :selected').data('campos');

        $(".campos").hide();
        $('.'+campos).show();
    }

    $("button[name=btn_acao]").click(function() {   
        $('.btn_acao').hide();     
        <?php if ($login_fabrica != 1) { ?>            
                $('form[name=frm_cad_solicitacao_cheque]').submit();
        <?php } else { ?>
                var cpf_cnpj = $('input[name=cnpj_cpf]').val();

                if (cpf_cnpj !== '' && cpf_cnpj != undefined) {
                     $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: { verifica_reincidencia: 'sim', cpf_cnpj: cpf_cnpj },
                        timeout: 8000
                    }).fail(function(){
                        alert('Ocorreu um erro ao tentar deletar a linha selecionada');
                    }).done(function(data){
                        data = JSON.parse(data);
                        var conteudo = '';
                        if (data.ok !== undefined) {
                            $("#n_reincidente").val(data.ultimo);
                            $.each(data.numero, function( index, value ) {
                              conteudo += '<div class="span3" style="margin-left : 0px;"><a href="solicitacao_cheque.php?xsolicitacao_cheque='+data.solicitacao[index]+'&num_solicitacao='+value+'" target=“_blank”>'+value+'</a></div>'; 
                            });

                            Shadowbox.open({
                                content: '<div class="row-fluid"> \
                                                <div class="span12">\
                                                    <h4 style="text-align:center;">Essa solicitação de cheque apresentou reincidência de CPF/CNPJ. Acesse abaixo.</h4> <br /> \
                                                </div>\
                                          </div>\
                                          <div class="row-fluid box_solicitacao"> \
                                                <div class="span12 tac" > \
                                                    <h4 style="background-color:#596D9B; color:#fff; font-weight: bold;">\
                                                        Número de Solicitação\
                                                    </h4>\
                                                </div>\
                                          </div>\
                                          <div class="row-fluid box_solicitacao" style="max-height : 50px; overflow-y:auto;">\
                                                '+conteudo+'\
                                          </div>\
                                           <div class="row-fluid box_justificativa" style="display:none;"> \
                                                <div class="span12 tac" > \
                                                    <h4 style="background-color:#596D9B; color:#fff; font-weight: bold;">\
                                                        Justificativa\
                                                    </h4>\
                                                </div>\
                                          </div>\
                                          <div class="row-fluid box_justificativa" style="display:none;"> \
                                                <div class="span12 tac" > \
                                                    <textarea name="text_justificativa"></textarea>\
                                                </div>\
                                          </div>\
                                          <hr>\
                                          <div class="row-fluid">\
                                                <div class="span6" style="text-align: right;">\
                                                    <button class="btn btn-danger" type="button" onClick="Shadowbox.close()";> Cancelar</button> \
                                                </div>\
                                                <div class="span6" style="text-align: left;">\
                                                    <button class="btn btn-success btn_continuar" type="button">Continuar</button> \
                                                    <button class="btn btn-success btn_enviar" type="button" style="display:none;" onClick="$(\'input[name=texto_justificatica]\').val($(\'textarea[name=text_justificativa]\').val());  $(\'form[name=frm_cad_solicitacao_cheque]\').submit(); $(\'.btn_enviar\').hide(); ">Enviar</button> \
                                                </div>\
                                          </div>',
                                player: "html",
                                options: {
                                    enableKeys: false
                                },
                                title: "Solicitação Reincidente",
                                width: 500,
                                height: 300
                            });

                            $(document).on('click','.btn_continuar', function() { 
                                $('.box_solicitacao').hide();
                                $(this).hide();
                                $('.box_justificativa').show();
                                $('.btn_enviar').show();
                            });

                        }else{
                            $('form[name=frm_cad_solicitacao_cheque]').submit();    
                        }
                    });   
		}else{
			$('form[name=frm_cad_solicitacao_cheque]').submit();
		}

        <?php } ?>        
        $('.btn_acao').show();
    });

    function verifica_ficha_cadastral(){

        var t_anexos = ["Anexo", "Anexo", "Anexo", "Anexo", "Anexo"];

        var tipo = $('select[name=tipo_solicitacao] :selected').val();
        if (tipo.indexOf('consumidor') !== -1) {
            $('#ficha_cadastral').show();
            $('#posto_autorizado').hide();
        }else if (tipo.indexOf('posto') !== -1) {
            $('#ficha_cadastral').hide();
            $('#posto_autorizado').show();
        }else{
            $('#posto_autorizado').hide();
            $('#ficha_cadastral').hide();
        }
        $('.protocolo').hide();   
        $('.hd_chamado').hide();
        $(".div_numero_processo").hide();


        if(tipo == '47_consumidor'){
           var t_anexos = ["Calculo", "NF", "Ticket", "Outros Anexos 1", "Outros Anexos 2"]; 
        }

        if(tipo == '49_consumidor' || tipo == '47_consumidor'){
            $('#ficha_produto').show();
            $('#ficha_motivo_devolucao').show();
        }else{
            $('#ficha_produto').hide();
            $('#ficha_motivo_devolucao').hide();
        }
        if(tipo == '47_consumidor' || tipo == '46_consumidor'){
            $('.protocolo').show();   
        }

        if(tipo == '46_consumidor'){
            $(".div_numero_processo").show();
            $(".class_anexo_0").removeClass('btn-primary');
            $(".class_anexo_0").addClass('btn-danger');
        }else{
            $(".class_anexo_0").addClass('btn-primary');
            $(".class_anexo_0").removeClass('btn-danger');
        }

        if(tipo == '49_consumidor'){
            $('.hd_chamado').show();
        }

        t_anexos.forEach(function(valor, chave){
            $(".class_anexo_"+chave).text(valor);
            $( "input[name='anexo_tipo_"+chave+"']" ).val(valor);
        });
    }

    function retorna_fornecedor(retorno){
        $('#fornecedor').val(retorno.fornecedor);
        $('input[name=razao_social]').val(retorno.nome.toUpperCase()).parents('div.control-group').removeClass('error');
        $('input[name=cnpj_cpf]').val(retorno.cnpj).parents('div.control-group').removeClass('error');

        if ($("input[name=cnpj_cpf]").val().length > 0) {
            if ($("input[name=cnpj_cpf]").val().length >= 14) {
                $("input[name=cnpj_cpf]").mask("99.999.999/9999-99");
            }else{
                $("input[name=cnpj_cpf]").mask("999.999.999-99");
            }
        }
    }

    function retorna_peca(retorno){ 
        var pecas_descricao = retorno.referencia + ' | ' + retorno.descricao;

        $("#peca_referencia").val(pecas_descricao);        
        $("#descricao_referencia").val(pecas_descricao);
    }

    function retorna_posto(retorno) {
        $('#posto').val(retorno.posto);
        $('input[name=codigo_posto]').val(retorno.codigo).parents('div.control-group').removeClass('error');
        $('input[name=razao_social_posto]').val(retorno.nome.toUpperCase()).parents('div.control-group').removeClass('error');
    }


    function retorna_produto(retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
        $("#produto_id").val(retorno.produto);
    }

    function somenteNumeros(num) {
        var er = /[^0-9.]/;
        er.lastIndex = 0;
        var campo = num;
        if (er.test(campo.value)) {
          campo.value = "";
        }
    }

</script>
<?php include_once "rodape.php"; ?>
