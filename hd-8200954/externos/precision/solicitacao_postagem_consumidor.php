<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include "../../dbconfig.php";
include "/var/www/includes/dbconnect-inc.php";
include_once "../../class/aws/s3_config.php";
include_once S3CLASS;
include_once "../../class/tdocs.class.php";

$titulo        = "Precision - Fale conosco";
$fabrica       = 80;
$login_fabrica = 80;
$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
$tDocs       = new TDocs($con, $login_fabrica);

$msg_sucesso = $_GET['msg_sucesso'];
$protocolo   = $_GET['protocolo'];
$data_providencia =  date("Y-m-d", strtotime(date("Y-m-d"). ' + 1 days')).' 00:00:00';


use Posvenda\TcMaps;
$oTCMaps = new TcMaps($login_fabrica,$con);


if($_GET['tipo_busca'] ==  'produto'){

    $q = preg_replace("/\W/", ".?", getPost('q'));
   
    $sqlProduto = "SELECT produto, descricao, familia
                     FROM tbl_produto
                    WHERE ( tbl_produto.descricao  ~* '$q' OR tbl_produto.referencia ~* '$q' )
		      AND fabrica_i={$login_fabrica}
			AND (ativo IS TRUE OR uso_interno_ativo IS TRUE)
                 ORDER BY descricao ASC;";
    $resProduto = pg_query($con, $sqlProduto);
    if (pg_num_rows($resProduto) > 0) {
        for ($i=0; $i<pg_num_rows($resProduto); $i++ ){
            $codigoProduto     = pg_fetch_result($resProduto,$i,'produto');
            $descricaoProduto  = pg_fetch_result($resProduto,$i,'descricao');
            $familia           = pg_fetch_result($resProduto,$i,'familia');

            echo "$codigoProduto|$descricaoProduto|$familia\n";
        }
    } else {
        echo 'Nenhum produto encontrado.';
    }

exit;
}


if ($_POST["ajax_interage"] == true) {
	$res = pg_query ($con,"BEGIN TRANSACTION");

    $txt_protocolo = utf8_decode($_POST['txt_protocolo']);
    $protocolo     = $_POST['protocolo'];

    if (!empty($protocolo) && !empty($txt_protocolo)) {

        $sql = "INSERT INTO tbl_hd_chamado_item (
                    admin,
                    status_item,
                    hd_chamado ,
                    comentario,
                    empregado
                ) VALUES (
                    9201,
                    'Aberto',
                    $protocolo,
                    '$txt_protocolo',
                    1
                )";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }

    if (strlen($msg_erro) == 0) {
        $sql = "UPDATE tbl_hd_chamado SET data_providencia='$data_providencia' WHERE hd_chamado={$protocolo} AND fabrica={$fabrica}";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        exit(json_encode(array("sucesso" => true, "msn" => "Mensagem registrada com sucesso!")));
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        exit(json_encode(array("erro" => true, "msn" => 'Erro ao gravar registro.')));
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

$buscaProduto = $_REQUEST['buscaProduto'];
if($buscaProduto == "buscaProduto"){

    $familia = $_REQUEST['familia'];
    $sql = "SELECT produto, descricao,voltagem FROM tbl_produto WHERE familia = $familia AND ativo ORDER BY descricao ASC;";
    $res = pg_query($con,$sql);
    $retorno = array();
    if (pg_num_rows($res) > 0) {
        for ($i=0; $i<pg_num_rows($res); $i++ ){
            $codigo     = pg_fetch_result($res,$i,'produto');
            $descricao  = utf8_encode(pg_fetch_result($res,$i,'descricao'));
            $voltagem   = pg_fetch_result($res,$i,'voltagem');
            $retorno["produtos"][] = array("codigo" => $codigo, "descricao" => $descricao, "voltagem" => $voltagem);
        }
            echo json_encode($retorno);
    }else{
        echo json_encode(array("erro" => true, "msn" => utf8_encode("Nenhum produto encontrada para esta famÌlia.")));
    }
    exit;
}


if (isset($_POST["acao"])) {

    $anexos = array();
    $anexo_c                    = $_POST['anexo'];
    $aux_nome                   = $_POST['nome'];
    $aux_consumidor_cpf_cnpj    = $_POST['consumidor_cpf_cnpj'];
    $aux_cpf                    = preg_replace("/\D/","",$_POST['cpf']);
    $aux_email                  = $_POST['email'];
    $aux_telefone               = $_POST['telefone'];
    $aux_cep                    = str_replace ([".", "-", " "],"",$_POST['cep']);
    $aux_endereco               = $_POST['endereco'];
    $aux_numero                 = $_POST['numero'];
    $aux_complemento            = $_POST['complemento'];
    $aux_bairro                 = $_POST['bairro'];
    $aux_cidade                 = $_POST['cidade'];
    $aux_estado                 = $_POST['estado'];
    $aux_familia                = $_POST['familia'];
    $aux_produto                = $_POST['produto'];
    $aux_msg                    = $_POST['msg'];
    $hd_classificacao           = 241;
    $aux_nf_produto             = $_POST['nf_produto_fale'];
    $aux_data_compra_produto    = $_POST['data_compra_produto_fale'];

	$consumidor_estado = $aux_estado;

    if(empty($aux_data_compra_produto)) {
        $aux_data_compra_produto = "null";
    }

    list($dnd,$dnm,$dna) = explode("/",$aux_data_compra_produto);
    $aux_data_compra_produto = "'".$dna.'-'.$dnm.'-'.$dnd."'";
    if(empty($_POST['data_compra_produto_fale'])) {
        $aux_data_compra_produto = "null";
    }

    if(empty($aux_defeito_reclamado)) {
        $aux_defeito_reclamado = "null";
    }


    if(!empty($aux_produto)){
        $sqlProduto = " SELECT referencia, descricao,ativo,uso_interno_ativo FROM tbl_produto WHERE produto = $aux_produto and fabrica_i = $login_fabrica ";
        $resProduto = pg_query($con, $sqlProduto);
        if(pg_num_rows($resProduto)==0){
            $msg_erro .= "Produto inv·lido";
    	}else{
    		$uso_interno = pg_fetch_result($resProduto,0,'uso_interno_ativo');
    		$ativo =  pg_fetch_result($resProduto,0,'ativo');
    	}
    }

    if (strlen($aux_nome) == 0) {
        $msg_erro .= "Preencha o <b>Nome</b> <br />";
    }

    $msg_tem_protocolo = "";

    if (strlen($aux_cpf) == 0) {
        $msg_erro .= "Preencha o <b>CPF ou CNPJ</b><br />";
    } else {
        $sql = "SELECT fn_valida_cnpj_cpf('{$aux_cpf}')";
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro .= "<b>CPF/CNPJ Inv·lido!</b> Digite corretamente o n∫ de seu CPF/CNPJ <br />";
        } else {

            $sql = "SELECT tbl_hd_chamado_extra.cpf  
                      FROM tbl_hd_chamado 
                      JOIN tbl_hd_chamado_extra USING(hd_chamado) 
		     WHERE tbl_hd_chamado_extra.cpf='{$aux_cpf}' 
			AND tbl_hd_chamado.status NOT IN ('Cancelado', 'Resolvido')
                       AND tbl_hd_chamado.fabrica={$fabrica} ";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $msg_tem_protocolo = "Para o CPF informado j· existe uma solicitaÁ„o em andamento, caso n„o tenha recebido o email confirmando sua solicitaÁ„o, gentileza manter contato com nossa Central de relacionamento.";
            }

        }

    }

    if (strlen($aux_email) == 0) {
        $msg_erro .= "Preencha o <b>E-mail</b> <br />";
    }

    if (strlen($aux_endereco) == 0){
        $msg_erro .= "Preencha o <b>Endere&ccedil;o</b> <br />";
    }

    if (strlen($aux_nome) == 0) {
        $msg_erro .= "Preencha o <b>N&uacute;mero</b> <br />";
    }

    if (strlen($aux_complemento) == 0) {
        $aux_complemento = '';
    }

    if (strlen($aux_bairro) == 0){
        $msg_erro .= "Preencha o <b>Bairro</b> <br />";
    }

    if (strlen($aux_cep) == 0) {
        $msg_erro .= "Preencha o <b>CEP</b> <br />";
    } else {
        $msg_erro .= valida_cep($aux_cep);
    }

    if (strlen($aux_cidade) == 0) {
        $msg_erro .= "Preencha a <b>Cidade</b> <br />";
    } else {
        if (strlen($aux_estado) > 0 and strlen($aux_cidade)>0) {

            $cidade = $aux_cidade;
            $estado = $aux_estado;

            $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0) {

                $sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {

                    $cidade = pg_fetch_result($res, 0, 'cidade');
                    $estado = pg_fetch_result($res, 0, 'estado');

                    $sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
                    $res = pg_query($con, $sql);

                } else {
                    $cidade = 'null';
                }

            } else {
                $cidade = pg_fetch_result($res, 0, 'cidade');
            }


        } elseif ($indicacao_posto=='f') {
            $msg_erro .= "Informe a <b>cidade</b><br />";
        }
    }


    if (strlen($aux_estado) == 0) {
        $msg_erro .= "Preencha o <b>Estado</b> <br />";
    }

    if (strlen($aux_produto) == 0){
        $msg_erro .= "Selecione um <b>produto</b><br />";
    }

    if (strlen($aux_nf_produto) == 0){
        $msg_erro .= "Informe a <b>NF</b><br />";
    }

    if (strlen($aux_msg) == 0) {
        $msg_erro .= "Preencha a <b>mensagem</b><br />";
    }

    if (strlen($anexo_c) == 0) {
        $array_tipo_anexo = array(1 => "NF", 2 => "Comprovante de ResidÍncia", 3 => "Foto do produto");
        foreach ($anexo_c as $key => $rows) {
            if (empty($rows)) {
                if ($key < 3) {
                   $msg_erro .= "O anexo da <b>" . $array_tipo_anexo[$key] . "</b> È ObrigatÛrio.<br />";
                }
            }
        }
    }
    $msg_tem_assist = "";
    if (strlen($msg_erro) == 0 && $ativo == "t") {
        if (verifica_postos_mais_proximos($endereco, $bairro, $cep, $aux_cidade, $estado)) {
           $msg_tem_assist .= "Existe uma assistÍncia tÈcnica prÛxima ‡ sua regi„o.<br/>
                            <a href='https://amvox.com.br/assistencia/'><b>Clique aqui</b></a> para consultar a assistÍncia mais prÛxima";
        }
    }

    if (strlen($msg_erro) == 0 && strlen($msg_tem_assist) == 0 && strlen($msg_tem_protocolo) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");

        $titulo            = 'Atendimento interativo';
        $xstatus_interacao = "'Aberto'";

        $sql = "SELECT count(tbl_admin_atendente_estado.admin) AS total_admin
                  FROM tbl_admin_atendente_estado
                  JOIN tbl_admin USING(admin)
                  JOIN tbl_cidade ON tbl_cidade.cod_ibge = tbl_admin_atendente_estado.cod_ibge AND tbl_cidade.cidade = {$cidade}
                 WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
                   AND tbl_admin_atendente_estado.estado = '$aux_estado'
                   AND tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                   AND tbl_admin.fabrica= {$login_fabrica}
                   AND tbl_admin.nao_disponivel is NULL
                   AND tbl_admin.ativo IS TRUE";
        $resP = pg_query($con,$sql);


        if(pg_fetch_result($resP, 0, 'total_admin') == 0){
            $sql = "SELECT count(tbl_admin_atendente_estado.admin) AS total_admin
                      FROM tbl_admin_atendente_estado
                      JOIN tbl_admin USING(admin)
                     WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
                       AND tbl_admin_atendente_estado.estado = '$consumidor_estado'
                       AND tbl_admin_atendente_estado.cod_ibge IS NULL
                       AND tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                       AND tbl_admin.fabrica= {$login_fabrica}
                       AND tbl_admin.nao_disponivel is NULL
                       AND tbl_admin.ativo IS TRUE";
            $resP = pg_query($con,$sql);

            if(pg_fetch_result($resP, 0, 'total_admin') == 0){
                $sql = "SELECT count(tbl_admin_atendente_estado.admin) AS total_admin
                    FROM tbl_admin_atendente_estado
                    JOIN tbl_admin USING(admin)
                    WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
                AND tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                AND tbl_admin_atendente_estado.estado = ''
                    AND tbl_admin_atendente_estado.cod_ibge IS NULL
                    AND tbl_admin.fabrica = {$login_fabrica}
                AND tbl_admin.nao_disponivel is NULL
                    AND tbl_admin.ativo IS TRUE";
                $resP = pg_query($con,$sql);

                $cond_geral = "  AND tbl_admin_atendente_estado.estado = '' AND tbl_admin_atendente_estado.cod_ibge IS NULL ";
            }else{
                $cond_estado = "AND tbl_admin_atendente_estado.estado = '$consumidor_estado' AND tbl_admin_atendente_estado.cod_ibge IS NULL ";
            }
        }else{
            $join_cidade = " JOIN tbl_cidade ON tbl_cidade.cod_ibge = tbl_admin_atendente_estado.cod_ibge AND tbl_cidade.cidade = {$cidade} ";
            $sqlIbge = "SELECT cod_ibge FROM tbl_cidade WHERE cidade = {$cidade}";
            $resIbge = pg_query($con,$sqlIbge);
            $cond_cidade = " AND tbl_admin_atendente_estado.cod_ibge = ". pg_fetch_result($resIbge,0,'cod_ibge')." ";
        }
        $total_admin = pg_fetch_result($resP, 0, 'total_admin') - 1;

        $total_admin = ($total_admin < 1) ? 1 : $total_admin;


            /*
            * - Verifica TODOS os atendentes
            * Respons·veis pela regi„o E classificaÁ„o
            * do chamado
             */
            $sql = "SELECT  DISTINCT
                        tbl_admin.admin as atendente
                FROM    tbl_admin_atendente_estado
                JOIN    tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
                {$join_cidade}
                WHERE   tbl_admin_atendente_estado.fabrica  = {$login_fabrica}
                AND     tbl_admin.fabrica                           = {$login_fabrica}
                $cond_estado
                AND     tbl_admin_atendente_estado.hd_classificacao = {$hd_classificacao}
                $cond_geral
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
                AND     tbl_hd_chamado.data::DATE = CURRENT_DATE
                ";
            $resCont = pg_query($con,$sqlCont);
            $contaChamados = pg_fetch_result($resCont,0,chamados_hoje);
            $qtdeChamados[$value['atendente']] = $contaChamados;
        }

        /*
         * - Retira o atendente
         * com menor n˙mero de chamados
         * atendidos no dia para gravaÁ„o do prÛximo
         * chamado
         */
        asort($qtdeChamados);
        $atendentesOrdenados = array_keys($qtdeChamados);
        $primeiroAtendente = array_shift($atendentesOrdenados);
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
                            $cond_cidade
                            $cond_estado
                            $cond_geral
                        LIMIT 1";

            $resP = pg_query($con,$sql);

            $novo_atendente = pg_fetch_result($resP, 0, 'admin');
            $nome_atendente = pg_fetch_result($resP, 0, 'login');


        }

        if (strtoupper(trim($novo_atendente)) == 0) {
            $novo_atendente = 11401;
        }

        $camposAddHD  = "hd_classificacao,";
        $valoresAddHD = "$hd_classificacao,";

        #-------------- INSERT ---------------
        $sql = "INSERT INTO tbl_hd_chamado (
                    admin                 ,
                    data                  ,
                    status                ,
                    atendente             ,
                    fabrica_responsavel   ,
                    titulo                ,
                    categoria             ,
                    data_providencia      ,
                    {$camposAddHD}
                    fabrica
                )values(
                    $novo_atendente         ,
                    current_timestamp       ,
                    $xstatus_interacao      ,
                    $novo_atendente         ,
                    $login_fabrica          ,
                    '$titulo'               ,
                    'reclamacao_produto'    ,
                    '{$data_providencia}'   ,
                    {$valoresAddHD}
                    $login_fabrica
            )";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
        $res        = pg_query($con,"SELECT CURRVAL ('seq_hd_chamado')");
        $hd_chamado = pg_fetch_result($res,0,0);

        $fale_conosco = json_encode(array("fale_conosco" => "true"));

        $camposAdd  = "origem,consumidor_revenda,defeito_reclamado,data_nf,nota_fiscal,";
        $valoresAdd = "'fale','C',$aux_defeito_reclamado,$aux_data_compra_produto,'$aux_nf_produto',";
        $anexos = $anexo_c;


        if (empty($msg_erro)) {
            $sql = "INSERT INTO tbl_hd_chamado_extra(
                                hd_chamado           ,
                                produto              ,
                                reclamado            ,
                                nome                 ,
                                cpf                  ,
                                endereco             ,
                                numero               ,
                                complemento          ,
                                bairro               ,
                                cep                  ,
                                fone                 ,
                                email                ,
                                cidade               ,
                                {$camposAdd}
                                array_campos_adicionais
                            )values(
                            $hd_chamado              ,
                            $aux_produto             ,
                            '$aux_msg'               ,
                            upper('$aux_nome')       ,
                            upper('$aux_cpf')        ,
                            upper('$aux_endereco')   ,
                            upper('$aux_numero')     ,
                            upper('$aux_complemento'),
                            upper('$aux_bairro')     ,
                            upper('$aux_cep')        ,
                            upper('$aux_telefone')   ,
                            upper('$aux_email')      ,
                            '$cidade'                ,
                            {$valoresAdd}
                            '$fale_conosco'
                            ) ";
            $res = pg_query($con,$sql);

            if (strlen(pg_errormessage($con)) > 0) {
                $msg_erro .= "N„o foi possÌvel registrar o atendimento.".pg_last_error($con);
            }
	}
	
        if (empty($msg_erro) && !empty($anexos)) {
            $typeID = [1 => "notafiscal", 2 => "documento", 3 => "produto"];
            foreach ($anexos as $kk => $anexo) {
                if (empty($anexo)) {
                    continue;
                }               

                $dadosAnexo = json_decode($anexo, 1);
                if (empty($dadosAnexo)) {
                    continue;
                }

                $anexoID = $tDocs->setDocumentReference($dadosAnexo, $hd_chamado, "anexar", false, "callcenter", $typeID[$kk]);
                if (!$anexoID) {
                    $msg_erro["msg"][] = 'Erro ao fazer upload do anexo!';
                }

            }

        }

    }

    if (strlen($msg_erro) == 0 && strlen($msg_tem_assist) == 0 && strlen($msg_tem_protocolo) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF?msg_sucesso=ok&protocolo=$hd_chamado&tipo_contato=$tipo_contato");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
            $aux_nome                   = $_POST['nome'];
            $aux_consumidor_cpf_cnpj    = $_POST['consumidor_cpf_cnpj'];
            $aux_cpf                    = $_POST['cpf'];
            $aux_email                  = $_POST['email'];
            $aux_telefone               = $_POST['telefone'];
            $aux_cep                    = $_POST['cep'];
            $aux_endereco               = $_POST['endereco'];
            $aux_numero                 = $_POST['numero'];
            $aux_complemento            = $_POST['complemento'];
            $aux_bairro                 = $_POST['bairro'];
            $aux_cidade                 = $_POST['cidade'];
            $aux_estado                 = $_POST['estado'];
            $aux_familia                = $_POST['familia'];
            $aux_produto_fale           = $_POST['produto'];
            $aux_msg                    = $_POST['msg'];
            $anexo_c = $_POST['anexo'];
            $aux_familia_fale           = $_POST['familia_fale'];
            $aux_nf_produto_fale        = $_POST['nf_produto_fale'];
            $aux_data_compra_produto    = $_POST['data_compra_produto_fale'];
    }
}


if ($_POST["ajax_anexo_upload"] == true) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif', 'pdf'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inv·lido, s„o aceitos os seguintes formatos: png, jpeg, bmp, gif, pdf'),'posicao' => $posicao);

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


if ($msg_sucesso =='ok') {
	$mensagem_sucesso = "Ol·, seu N∫ de protocolo È <b>{$protocolo}</b>.<br><br>Sua SolicitaÁ„o foi recebida com sucesso e j· foi direcionada para nossa equipe de atendimento. <br>Em breve estar· recebendo a aprovaÁ„o de sua solicitaÁ„o e um email com o cÛdigo de postagem para que possa enviar o produto.";

    include_once '../../class/communicator.class.php';

    $sql = "SELECT email FROM tbl_hd_chamado_extra WHERE hd_chamado={$protocolo}";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $consu_email = strtolower(pg_fetch_result($res, 0, 'email'));
        $remetente = "noreply@telecontrol.com.br";
        $assunto = "Amvox - Abertura de Protocolo";

        $mailer = new TcComm('smtp@posvenda');
        if (!$mailer->sendMail($consu_email, $assunto, $mensagem_sucesso, $remetente)) {
            $msg_erro = "Erro ao enviar email para $aux_email";
        }
    }

}

if (strlen($msg_erro) > 0) {
    $mensagem_erro = $msg_erro;
}

function verifica_postos_mais_proximos($endereco, $bairro, $cep, $cidade, $estado) {
    global $con, $login_fabrica, $oTCMaps;
    $latLon     = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, 'Brasil');
    $lat        = $latLon['latitude'];
    $lon        = $latLon['longitude'];
    $latLonStr  = $lat.'@'.$lon;
    $order      = " distance";

    if (strlen($cep) > 2 || (!empty($lat) and !empty($lon))) {

        if (!empty($lat) and !empty($lon)) {
            $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);
            $lat = $latLon['latitude'];
            $lon = $latLon['longitude'];
            $latLonStr = $lat.'@'.$lon;
            $cond = " AND (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) <= 35 ";
            $order = " distance";
        
            $campo_distance = ", (111.045 * DEGREES(ACOS(COS(RADIANS({$lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$lon})) + SIN(RADIANS({$lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance";

        }

        if (strlen($cep) > 0) {
            $limit = "LIMIT 5";
        } else {
            $cond .= "AND tbl_posto_fabrica.contato_estado = '$estado' AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade')";
            $order = " nome ";
        }
    } else {
        $cond .= " AND tbl_posto_fabrica.contato_estado = '$estado' AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade') ";
        $order = " nome ";
    }

    $sql = "SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, 
                            tbl_posto.posto, 
                            tbl_posto_fabrica.contato_endereco, 
                            tbl_posto_fabrica.contato_numero,
                            tbl_posto_fabrica.contato_email,
                            tbl_posto_fabrica.contato_bairro, 
                            tbl_posto_fabrica.contato_fone_comercial, 
                            tbl_posto_fabrica.contato_cidade, 
                            tbl_posto_fabrica.obs_conta, 
                            tbl_posto_fabrica.parametros_adicionais, 
                            tbl_posto_fabrica.latitude,
                            tbl_posto_fabrica.longitude
                            $campo_distance
            FROM tbl_posto_fabrica
            JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
           WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
             AND tbl_posto_fabrica.posto NOT IN(6359,4311) 
             AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                 {$cond}
        ORDER BY $order $limit"; 
    $res = pg_query($con, $sql);
    if (pg_last_error($con) || pg_num_rows($res) == 0) {
        return false;
    }

    return true;
}

function valida_cep($cep) {
    global $con;

    if (strlen($cep) > 0) {
        $cep = preg_replace("/\D/", "", $cep);

        $sql = "SELECT cep FROM tbl_cep WHERE cep = '{$cep}'";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            return "<b>CEP</b> Inv·lido <br>";
        }
    }
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
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <!--<base href="https://amvox.com.br/"> -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Amvox">
        <meta name="keywords" content="Amvox, Reistar, linha de eletros, linha branca, linha ·udio, linha beleza, linha cozinha, linha lar, linha digital">
        <meta name="author" content="Start ComunicaÁ„o">
        <title><?php echo $titulo;?></title>
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
        <!-- Bootstrap core CSS
        ================================================== -->
        <link href="../bootstrap3/css/bootstrap.min.css" rel="stylesheet">
        <!-- CSS Tema Custom
        ================================================== -->
        <link href="style.css?v=10" rel="stylesheet">
     
        <style type="text/css">
            @import "../../plugins/jquery/datepick/telecontrol.datepick.css";
            .nocel{margin-top: -24px;}
            @media screen and (max-width: 767px) {
                
                .nocel{margin-top: 0px;}
                .txt_cel{padding-top: 20px;}
            }
            .datepick-month-header, .datepick-month-header select {
                    height: 20px !important;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div id="main-col" class="col-sm-12 col-md-12">
                    <h4 class="titulo">Solicitar Coleta / Postagem do produto em garantia</h4>
                    <div style="display: block;padding-bottom: 20px;">
                        <em>Favor informar e-mail v·lido para receber as notificaÁıes da nossa central de relacionamento.</em>
                    <div class="pull-right text-danger txt_cel">Os campos com asterisco (*) s„o de preenchimento obrigatÛrio.</div></div>
                    <?php
                        if (strlen($mensagem_sucesso) > 0) {
                            echo "<br /><div class='alert alert-success'>$mensagem_sucesso</div>";
                        }

                        if (strlen($mensagem_erro) > 0) {
                            echo "<br /><div class='alert alert-danger'>$mensagem_erro</div>";
                        }

                        if (strlen($msg_tem_assist) > 0) {
                            echo "<br /><div class='alert alert-info' align='center'>$msg_tem_assist</div>";
                        }

                        if (strlen($msg_tem_protocolo) > 0) {
                            echo "<br /><div class='alert alert-info' align='center'>$msg_tem_protocolo</div>";
                        }

                    ?>
                    <!-- Contact Form -->
                    <div class="contact-form">
                        <form method="post" id="form-contato" action="<?=$PHP_SELF?>"  name="FormContato" id="FormContato" enctype="multipart/form-data">
                            <input type="hidden" value='Gravar' name="acao" >
                            <!-- Cols Wrapper -->
                            <div class="clearfix">
                                <!-- Left Col -->
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <input type="text" maxlength="50" placeholder="Informe seu nome" name="nome" id="nome" value='<?php echo $aux_nome;?>' class="form-control">
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <div class="nocel">
                                            <input type='radio' name='consumidor_cpf_cnpj' id='consumidor_cfp' value='C' <?php echo ($aux_consumidor_cpf_cnpj  == "C") ? "CHECKED" : "";?> onclick="fnc_tipo_atendimento(this)">
                                            <label for="cpf">CPF</label>
                                            <input type='radio' name='consumidor_cpf_cnpj' id='consumidor_cnpj' value='R'<?php echo ($aux_consumidor_cpf_cnpj == "R") ? "CHECKED": "";?> onclick="fnc_tipo_atendimento(this)">
                                            <label for="consumidor_cfp">CNPJ</label>
                                            <input type="text" name="cpf" id="cpf" value='<?php echo $aux_cpf;?>' placeholder="Informe seu CPF / CNPJ *" class="form-control cpf">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <input type="email" name="email" placeholder="Informe seu E-mail" id="email" value='<?php echo $aux_email;?>'  class="form-control">
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <input type="text" placeholder="Informe seu telefone" name="telefone" id="telefone" value='<?php echo $aux_telefone;?>' class="form-control fone telefone">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-4">
                                        <input type="text" name="cep" placeholder="Informe seu cep *" id="cep" onblur="buscaCEP(this.value)" value='<?php echo $aux_cep;?>' class="form-control cep">
                                    </div>
                                    <div class="col-sm-12 col-md-6" >
                                        <input type="text" maxlength="70" name="endereco"  placeholder="Informe seu endereÁo *"  id="endereco" value='<?php echo $aux_endereco;?>' class="form-control">
                                    </div>
                                    <div class="col-sm-12 col-md-2">
                                        <input type="text" maxlength="20" name="numero"  placeholder="Numero *" id="numero" value='<?php echo $aux_numero;?>' class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <input type="text" maxlength="40" placeholder="Informe seu complemento" name="complemento" id="complemento" value='<?php echo $aux_complemento;?>' class="form-control">
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <input type="text" maxlength="60" placeholder="Informe seu bairro *" name="bairro" id="bairro" value='<?php echo $aux_bairro;?>' class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <select name='estado' id='estado' class="form-control">
                                            <option value="">Escolha um Estado *</option>
                                            <?php
                                                foreach ($array_estado as $k => $v) {
                                                    echo '<option value="'.$k.'"'.($aux_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <select name="cidade" class="form-control" id='cidade' title='Selecione um estado para escolher uma cidade' style="display: block;">
                                            <option value="">Escolha uma Cidade *</option>
                                            <?php
                                                if (strlen($aux_cidade) > 0 ) {
                                                    echo '<option value"'.$aux_cidade.'" selected>'.$aux_cidade.'</option>';
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div><br/>
                                <div class="row">
                                    <div class="col-sm-12 col-md-8">
                                        <input type='hidden' name='produto'  id='produto' value="<?php echo $produto?>">
                                        <input name="produto_descricao" placeholder="Informe o produto *" class="form-control" id="produto_descricao" value="<?php echo $produto_descricao; ?>" type="text" size="80" maxlength="80" title="Produto" />
                                        <input type='hidden' name='familia_fale' id='familia_fale' value="<?php echo $aux_familia_fale?>">
                                    </div>                                        

                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-6">
                                        <input class="form-control" placeholder="Informe a NF *" type="text" maxlength="20" id="nf_produto_fale" value='<?php echo $aux_nf_produto_fale;?>' name="nf_produto_fale">
                                    </div>
                                    <div class="col-sm-12 col-md-6"> 
                                        <input class="form-control " placeholder="Informe a data de compra" type="text" id="data_compra_produto_fale" value='<?php echo $data_compra_produto_fale;?>' name="data_compra_produto_fale">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-md-12"> 
                                        <textarea  class="form-control" name="msg" id="msg"  placeholder="Digite a mensagem *"><?php echo $aux_msg;?></textarea>
                                    </div>
                                </div>

                            <div style="text-align: center;background: #fff" align="center">
                                <h5 class="label_anexo titulo">Anexo(s)</h5><br />
                                <?php
                                    $tDocs->setContext('callcenter');
                                    $info = $tDocs->getDocumentsByRef($hd_chamado)->attachListInfo;
                                    $pos  = 1;
                                    if (count($info) > 0) {

                                        foreach ($info as $k => $vAnexo) {
                                            $info[$k]["posicao"] = $pos++;
                                        }

                                    }

                                    for ($i=1; $i <= 3 ; $i++) {  
                                        if ($i == 1) {
                                            $labelBotao = "NF";
                                        }
                                        if ($i == 2) {
                                            $labelBotao = "Comprovante ResidÍncia";
                                        }
                                        if ($i == 3) {
                                            $labelBotao = "Foto do produto";
                                        }
                                        $imagemAnexo = "../../admin/imagens/imagem_upload.png";
                                        $linkAnexo   = "#";
                                        $anexo = $anexo_c[$i];
                                        $anexo_c_array = json_decode($anexo_c[$i], true); 
                                        $anexo_id = $anexo_c_array['tdocs_id'];
                                        if (!empty($anexo_id)) {
                                            if($anexo_c_array['type'] == "application/pdf"){
                                                $linkAnexo = "../../imagens/pdf_icone.png";
                                            }else{
                                                $linkAnexo = "http://api2.telecontrol.com.br/tdocs/document/id/".$anexo_id;
                                            }                                            
                                            $imagemAnexo = $linkAnexo;
                                        }

                                        if ($hd_chamado > 0) {
                                            if (count($info) > 0) {

                                                foreach ($info as $k => $vAnexo) {

                                                    if ($vAnexo["posicao"] != $i) {
                                                        continue;
                                                    }

                                                    $linkAnexo   = $vAnexo["link"];
                                                    $imagemAnexo = $vAnexo["link"];
                                                }
                                            } 
                                        }  
                                ?>
                                <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                                    <?php if ($linkAnexo != "#") { ?>
                                    <a href="<?=$linkAnexo?>" target="_blank" >
                                    <?php } ?>
                                        <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;margin-bottom: 10px;" />
                                    <?php if ($linkAnexo != "#") { ?>
                                    </a>
                                    <?php } ?>
                                    <button type="button" class="btn-anexar" name="anexar" rel="<?=$i?>" ><?php echo $labelBotao;?></button>
                                    <img src="../../admin/imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;margin-bottom: 10px;" />
                                    <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value='<?=$anexo?>' />
                                </div>
                                <?php } ?>
                            </div><hr>
                
                            <div style="text-align: center;margin-top: 40px;" align="center">
                                <button type="submit" class="btn btn-primary" name="Enviar" id="Enviar">
                                    Enviar                                    
                                </button>
                            </div>
                        </form>
                        <?php for ($i = 1; $i <=  3; $i++) {?>
                            <form name="form_anexo" method="post" action="solicitacao_postagem_consumidor.php" enctype="multipart/form-data" style="display: none !important;" >
                                <input type="file" name="anexo_upload_<?=$i?>" value="" />
                                <input type="hidden" name="ajax_anexo_upload" value="t" />
                                <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
                                <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
                            </form>
                        <?php }?>
                    </div>
                    <!-- /Contact Form -->
                </div>
            </div>
        </div>

        <script type="text/javascript" src="../../admin/js/jquery-1.8.3.min.js"></script>
        <script type="text/javascript" src="../../js/jquery.form.js"></script>
        <script src="../../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="../../plugins/shadowbox/shadowbox.css" media="all">

        <script language="JavaScript" src="../../admin/js/jquery.mask.js"></script>
        <script type="text/javascript" src="../../plugins/jquery/datepick/jquery.datepick.js"></script>
        <script type="text/javascript" src="../../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

        <!--<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>-->

            <script type='text/javascript' src='../../js/jquery.bgiframe.min.js'></script>
            <script type='text/javascript' src='../css/jquery.autocomplete.min.js'></script>
	    <script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>
	
	<script type="text/javascript" src="../../js/jquery.alphanumeric.js"></script>

        <script language="JavaScript">
            function selecionaFamilia(familia) {
                $(document).on("click", "#familia_produto", function(){
                    $(this).val(familia);
                });
                $("#familia_produto").trigger("click");
            
            }

            function selecionaProduto(produto) {
                $(document).on("change", "#produto_revenda", function(){
                    $(this).val(produto);
                });
                $("#produto_revenda").trigger("change");
            }


            function retorna_produto(descricao, referencia, voltagem, marca_produto, produto, linha, pos, informatica,serie_obrigatorio, linha_descricao) {
                $("#referencia_produto_revenda").val(referencia);
                $("#produto_revenda").val(produto);
                $("#produto_revenda_lupa").val(produto);
                $("#descricao_produto_revenda").val(descricao);

            }

            function fnc_pesquisa_produto(campo, campo2, tipo, mapa_linha, pos) {
                if (tipo == "referencia" ) {
                    var xcampo = campo;
                }

                if (tipo == "descricao" ) {
                    var xcampo = campo2;
                }

                var familia = "";

                if ($("select#familia_produto").length > 0) {
                    var id_familia = $("select#familia_produto").val();

                    if (id_familia.length == 0) {
                        alert ("Selecione a famÌlia do produto");
                        campo.focus();
                        return false;
                    }
                    familia = "&familia="+id_familia;
                }
              
                if ((typeof xcampo != 'undefined' && xcampo != "")) {
                    var url = "../../admin/produto_pesquisa_3.php?campo=" +
                        xcampo.value + "&tipo=" +
                        tipo +"&familia=" + familia +
                        "&mapa_linha=t&voltagem=t&pos=" + pos+"&fale_conosco_esmaltec=true";

                    Shadowbox.open({
                        content :   url,
                        player  :   "iframe",
                        title   :   "Pesquisa",
                        width   :   800,
                        height  :   500
                    });
                }
            }

            $(function(){
                $("#telefone").mask("(99) 99999-9999");
                $("#cep").mask("99999-999");
		$('#data_compra_produto_fale').datepick({startDate:'01/01/2000'});
		$("#numero").numeric();
                Shadowbox.init();
                if (($(".cpf").val().length == 14) || $("input[name=consumidor_cpf_cnpj]:checked").val() == "C" || $("input[name=cpf_cnpj_revenda]:checked").val() == "C") {

                    $('.cpf').attr('maxLength', 14);

                    $('.cpf').keypress (function(e){
                        return txtBoxFormat($(this), '999.999.999-99', e);
                    });
                }else{
                    $('.cpf').attr('maxLength', 18);

                    $('.cpf').keypress (function(e){
                        return txtBoxFormat($(this), '99.999.999/9999-99', e);
                    });
                }

                $("input[name=tipo_busca]").click(function () {
                    if ($("input[name=tipo_busca]:checked").val() == "familia") {
                        $('.tipo_busca_produto').hide();
                        $('.tipo_busca_familia_produto').show();
                    } else {

                        $('.tipo_busca_familia_produto').hide();
                        $('.tipo_busca_produto').show();
                    }
                });


                $("#estado").change(function () {
                    if ($(this).val().length > 0) {
                        buscaCidade($(this).val());
                    } else {
                        $("#cidade > option[rel!=default]").remove();
                    }
                });

                /* ANEXO DE FOTOS REVENDA*/
                $("input[name^=anexo_r_upload_]").change(function() {
                    var i = $(this).parent("form").find("input[name=anexo_posicao_r]").val();

                    $("#div_anexo_"+i+"_r").find("button").hide();
                    $("#div_anexo_"+i+"_r").find("img.anexo_thumb_r").hide();
                    $("#div_anexo_"+i+"_r").find("img.anexo_loading_r").show();

                    $(this).parent("form").submit();
                });

                $("button[name=anexar_r]").click(function() {
                    var posicao = $(this).attr("rel");
                    $("input[name=anexo_r_upload_"+posicao+"]").click();
                });

                $("form[name=form_anexo_r]").ajaxForm({
                    complete: function(data) {
                        console.log(data)
                        data = $.parseJSON(data.responseText);

                        if (data.error) {
                            alert(data.error);
                        } else {
                            var imagem = $("#div_anexo_"+data.posicao+"_r").find("img.anexo_thumb_r").clone();
                            if(data.ext == 'pdf'){
                                $(imagem).attr( "src", "../../imagens/pdf_icone.png" );
                            }else{
                                $(imagem).attr({ src: data.link });    
                            }                            

                            $("#div_anexo_"+data.posicao+"_r").find("img.anexo_thumb_r").remove();

                            var link = $("<a></a>", {
                                href: data.href,
                                target: "_blank"
                            });

                            $(link).html(imagem);

                            $("#div_anexo_"+data.posicao+"_r").prepend(link);


                            $("#div_anexo_"+data.posicao+"_r").find("input[rel=anexo_r]").val(data.arquivo_nome);
                        }

                        $("#div_anexo_"+data.posicao+"_r").find("img.anexo_loading_r").hide();
                        $("#div_anexo_"+data.posicao+"_r").find("button").show();
                        $("#div_anexo_"+data.posicao+"_r").find("img.anexo_thumb_r").show();
                    }
                });
                /* FIM ANEXO DE FOTOS */
                /* ANEXO DE FOTOS */
                $("input[name^=anexo_upload_]").change(function() {
                    var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

                    $("#div_anexo_"+i).find("button").hide();
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
                        console.log(data)
                        data = $.parseJSON(data.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                            if(data.ext == 'pdf'){
                                $(imagem).attr( "src", "../../imagens/pdf_icone.png" );
                            }else{
                                $(imagem).attr({ src: data.link });    
                            }                            

                            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                            var link = $("<a></a>", {
                                href: data.href,
                                target: "_blank"
                            });

                            $(link).html(imagem);

                            $("#div_anexo_"+data.posicao).prepend(link);

                            $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                        }

                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+data.posicao).find("button").show();
                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
                    }
                });
                /* FIM ANEXO DE FOTOS */

                
                /* # HD 941072 - Busca produto pela descriÁ„o */                
                $("#produto_descricao").autocomplete("solicitacao_postagem_consumidor.php?tipo_busca=produto",
                {
                    minChars      : 3,
                    delay         : 150,
                    width         : 350,
                    matchContains : true,
                    formatItem    : function(row){ return row[1]  },
                    formatResult  : function(row){ return row[1]; }
                });

                $("#produto_descricao").result(function(event, data, formatted)
                {
                    $("#produto").val(data[0]);
                    $("#produto_descricao").val(data[1]);
                });    

            });


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

            function buscaCidade (estado, cidade) {
                $.ajax({
                    async: false,
                    url: "solicitacao_postagem_consumidor.php",
                    type: "POST",
                    data: { buscaCidade: true, estado: estado },
                    cache: false,
                    complete: function (data) {
                        data = $.parseJSON(data.responseText);

                        if (data.cidades) {
                            $("#cidade > option[rel!=default]").remove();
                            
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

                                $("#cidade").append(option);
                            });
                        } else {
                            $("#cidade > option[rel!=default]").remove();
                        }
                    }
                });
            }

            function buscaCEP(cep) {
                $.ajax({
                    type: "GET",
                    url:  "../../admin/ajax_cep.php",
                    data: "cep="+escape(cep),
                    cache: false,
                    complete: function(resposta){
                        results = resposta.responseText.split(";");
                        if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
                        if (typeof (results[2]) != 'undefined') $('#bairro').val(results[2]);
                        if (typeof (results[4]) != 'undefined') $('#estado').val(results[4]);
                        $('#numero').focus();
                        buscaCidade(results[4], results[3]);                     
                    }
                });
            }

            function buscaProduto(familia) {
                if(familia != 0){
                    $.ajax({
                        type: "POST",
                        url:  "solicitacao_postagem_consumidor.php",
                        data: "familia="+familia+"&buscaProduto=buscaProduto",
                        success: function(resposta){
                            data = JSON.parse(resposta);
                            dados = data.produtos;

                            var options = "";
                            if (resposta.erro) {
                                options = "<option value=''>"+data.msn+"</option>";
                            } else {
                                options += "<option value='' selected>Selecione um produto</option>";
                                for (var i = 0; i < $(dados).length; i++ ) {
                                    options += "<option data-voltagem='"+dados[i].voltagem+"' value='"+dados[i].codigo+"'>"+dados[i].descricao+"</option>";
                                }
                            }
                            $("#produto_fale").html(options);
                           
                        }
                    });
                }
            }

            function fnc_tipo_atendimento(tipo) {
                $('.cpf').val('');
                if (tipo.value == 'C') {
                    $('.cpf').attr('maxLength', 14);
                    $('.cpf').keypress (function(e){
                        return txtBoxFormat($(this), '999.999.999-99', e);
                    });
                } else {
                    if (tipo.value == 'R') {
                        $('.cpf').attr('maxLength', 18);
                        $('.cpf').keypress(function(e){
                            return txtBoxFormat($(this), '99.999.999/9999-99', e);
                        });
                    }
                }
            }

            function txtBoxFormat(strField, sMask, evtKeyPress) {
                var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

                if(document.all) { // Internet Explorer
                    nTecla = evtKeyPress.keyCode;
                } else if(document.layers) { // Nestcape
                    nTecla = evtKeyPress.which;
                } else {
                    nTecla = evtKeyPress.which;
                    if (nTecla == 8) {
                        return true;
                    }
                }

                sValue = $(strField).val();

                sValue = sValue.toString().replace( "-", "" );
                sValue = sValue.toString().replace( "-", "" );
                sValue = sValue.toString().replace( ".", "" );
                sValue = sValue.toString().replace( ".", "" );
                sValue = sValue.toString().replace( "/", "" );
                sValue = sValue.toString().replace( "/", "" );
                sValue = sValue.toString().replace( "/", "" );
                sValue = sValue.toString().replace( "(", "" );
                sValue = sValue.toString().replace( "(", "" );
                sValue = sValue.toString().replace( ")", "" );
                sValue = sValue.toString().replace( ")", "" );
                sValue = sValue.toString().replace( " ", "" );
                sValue = sValue.toString().replace( " ", "" );
                fldLen = sValue.length;
                mskLen = sMask.length;

                i = 0;
                nCount = 0;
                sCod = "";
                mskLen = fldLen;

                while (i <= mskLen) {
                bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
                bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))


                if (bolMask) {
                    sCod += sMask.charAt(i);
                    mskLen++;

                } else {
                    sCod += sValue.charAt(nCount);
                    nCount++;
                }
                i++;
                }

                $(strField).val(sCod);

                if (nTecla != 8) { // backspace
                    if (sMask.charAt(i-1) == "9") { // apenas n˙meros...
                        return ((nTecla > 47) && (nTecla < 58)); } // n˙meros de 0 a 9
                    else { // qualquer caracter...
                        return true;
                    }
                } else {
                    return true;
                }
            }

            <?php if(!isset($_POST)){ ?>

            jQuery(document).ready(function() {
                jQuery("#content > div").hide();
                jQuery("#content > div:eq(0)").show();
            });

            <?php } ?>

            function opentab(num) {
                    jQuery("#content > div").hide();
                    jQuery("#content > div:eq(" + (num-1) + ")").fadeIn();
                    jQuery(".tabset li").each(function(){
                        jQuery(this).attr("class","");
                    });
                jQuery("#opentab_"+num).attr("class","active");
            }

        </script>
        </body>
    </html>
