<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include "../../dbconfig.php";
include "/var/www/includes/dbconnect-inc.php";
include_once "../../class/aws/s3_config.php";
include_once S3CLASS;
include_once "../../class/tdocs.class.php";

$titulo        = "Esmaltec - Fale conosco";
$fabrica       = 30;
$login_fabrica = 30;
$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
$tDocs       = new TDocs($con, $login_fabrica);

$msg_sucesso = $_GET['msg_sucesso'];
$protocolo   = $_GET['protocolo'];

$data_providencia =  date("Y-m-d", strtotime(date("Y-m-d"). ' + 1 days')).' 00:00:00';

$sql = "SELECT fn_calcula_previsao_retorno('{$data_providencia}',1,{$login_fabrica}) as data_util";
$resP = pg_query($con,$sql);

$data_providencia = pg_fetch_result($resP, 0, 'data_util');


if($_GET['produto_familia']){
    $aux_familia_revenda = $_GET['produto_familia'];

    $sql = "SELECT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
                     FROM tbl_diagnostico
                     JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                    WHERE tbl_diagnostico.familia=$aux_familia_revenda
                      AND tbl_diagnostico.fabrica={$fabrica}
                      AND tbl_defeito_reclamado.fabrica={$fabrica}
                 ORDER BY tbl_defeito_reclamado.descricao ASC;";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        echo '<option value="">Selecione um Defeito</option>';
        for ($i=0; $i<pg_num_rows($res); $i++ ){
            $defeito_reclamado = pg_fetch_result($res,$i,'defeito_reclamado');
            $descricao         = pg_fetch_result($res,$i,'descricao');
            
            echo '<option value="'.$defeito_reclamado.'">'.$descricao.'</option>';
        }
    } else {
        echo '<option value="" selected>Nenhum defeito encontrado para esta família.</option>';
    }
    exit;
}

if($_GET['tipo_busca'] == 'revenda'){

    if($_GET["t"] == 'cnpj'){
        $cnpj = $_GET["q"];
        $campos = " tbl_revenda.cnpj ILIKE '$cnpj%' ";
    }else{
        //$nome = preg_replace("/\W/", ".?", getPost('q'));
        $nome = getPost('q');
        $campos = " tbl_revenda.nome ILIKE '%$nome%' ";
    }

    $sql = "SELECT DISTINCT lpad(tbl_revenda.cnpj, 14, '0') AS cnpj ,
                            tbl_revenda.nome ,
                            tbl_revenda.revenda                             
                            FROM tbl_revenda
                            WHERE 
                            $campos 
                            AND tbl_revenda.cnpj_validado IS TRUE 
                            ORDER BY tbl_revenda.nome";
    $res = pg_query($con, $sql);
    
    if(pg_num_rows($res)>0){
        for($i=0;$i<pg_num_rows($res); $i++){
            $cnpj = pg_fetch_result($res, $i, 'cnpj');
            $nome = pg_fetch_result($res, $i, 'nome');
            $revenda = pg_fetch_result($res, $i, 'revenda');

            echo "$revenda|$cnpj|$nome\n";
        }
    }

    exit;

}


if($_GET['tipo_busca'] ==  'produto'){

    $q          = preg_replace("/\W/", ".?", getPost('q'));
   
    $sqlProduto = "SELECT produto, descricao, familia
                     FROM tbl_produto
                    WHERE ativo
                        AND ( tbl_produto.descricao  ~* '$q' OR tbl_produto.referencia ~* '$q' )
                      AND fabrica_i={$login_fabrica}
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

    $txt_protocolo = pg_escape_string($_POST['txt_protocolo']);
    $txt_protocolo = utf8_decode($txt_protocolo);

    $protocolo     = $_POST['protocolo'];

    $tipo_consumidor_revenda = $_POST["tipo_consumidor_revenda"];
    if($tipo_consumidor_revenda == "R"){
        $tipo_consumidor_revenda = "Revenda";
    }else{
        $tipo_consumidor_revenda = "Consumidor";
    }

    //busca admin 
    $sql_admin = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica and login = '$tipo_consumidor_revenda' ";
    $res_admin = pg_query($con, $sql_admin);
    if(pg_num_rows($res_admin)>0){
        $id_admin = pg_fetch_result($res_admin, 0, 'admin');
    }

    if (!empty($protocolo) && !empty($txt_protocolo)) {

        $sql = "INSERT INTO tbl_hd_chamado_item (
                    admin,
                    status_item,
                    hd_chamado ,
                    comentario,
                    empregado
                ) VALUES (
                    $id_admin,
                    'Aberto',
                    $protocolo,
                    '$txt_protocolo',
                    1
                )";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }

    /*if (strlen($msg_erro) == 0) {
        $sql = "UPDATE tbl_hd_chamado SET data_providencia='$data_providencia' WHERE hd_chamado={$protocolo} AND fabrica={$fabrica}";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }
*/
    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        exit(json_encode(array("sucesso" => true, "msn" => "Mensagem registrada com sucesso!")));
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        exit(json_encode(array("erro" => true, "msn" => 'Erro ao gravar registro.')));
    }

}

if($_POST["abrir_novo"]== true){

    $hd_chamado = $_POST["hd_chamado"];

    $sql = "SELECT tbl_hd_chamado_extra.nome, 
                   tbl_hd_chamado_extra.cpf, 
                   tbl_hd_chamado_extra.email, 
                   tbl_hd_chamado_extra.fone, 
                   tbl_hd_chamado_extra.cep,
                   tbl_hd_chamado_extra.bairro, 
                   tbl_hd_chamado_extra.endereco,
                   tbl_hd_chamado_extra.complemento, 
                   tbl_hd_chamado_extra.numero,
                   tbl_hd_chamado_extra.consumidor_revenda,
                   to_char(tbl_hd_chamado_extra.data_nascimento,'DD/MM/YYYY') as data_nascimento                   
            FROM tbl_hd_chamado
            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
            WHERE tbl_hd_chamado.hd_chamado = $hd_chamado 
            and tbl_hd_chamado.fabrica = {$login_fabrica} ";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
        $retorno["erro"] = true;
        $retorno["msn"]  = utf8_encode("Nenhum protocolo encontrado.");
    }else{
        $retorno['nome'] = utf8_encode(pg_fetch_result($res, 0, 'nome'));
        $retorno['cpf'] = pg_fetch_result($res, 0, 'cpf');
        $retorno['email'] = pg_fetch_result($res, 0, 'email');
        $retorno['fone'] = pg_fetch_result($res, 0, 'fone');
        $retorno['cep'] = pg_fetch_result($res, 0, 'cep');
        $retorno['bairro'] = utf8_encode(pg_fetch_result($res, 0, 'bairro'));
        $retorno['endereco'] = utf8_encode(pg_fetch_result($res, 0, 'endereco'));
        $retorno['numero'] = pg_fetch_result($res, 0, 'numero');
        $retorno['consumidor_revenda'] = pg_fetch_result($res, 0, 'consumidor_revenda');
        $retorno['data_nascimento'] = pg_fetch_result($res, 0, 'data_nascimento');
    }

    echo json_encode($retorno);

    exit;
}

if ($_POST["consulta_protocolo"] == true) {
    $retorno   = array();
    $msg_erro  = array();
    $protocolo = $_POST['protocolo'];
    $cpf       = preg_replace("/\D/","",$_POST['cpf']);

    if (strlen($protocolo) > 0) {
        $cond = "tbl_hd_chamado.hd_chamado={$protocolo} AND ";
    } else {
        $cond = "tbl_hd_chamado_extra.cpf = '{$cpf}' AND ";
    }

    if (strlen($cpf) > 0) {

        $sqlCPF = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $resCPF = pg_query($con, $sqlCPF);

        if (strlen(pg_last_error()) > 0) {
            $msg_erro["erro"] = true;
            $msg_erro["msn"]  = utf8_encode("CPF/CNPJ Inválido! Digite corretamente o nº de seu CPF/CNPJ");
        }

    }

    if (strlen($msg_erro) == 0) {
        $hd_chamados = array();
        $sql = "SELECT tbl_hd_chamado.hd_chamado, 
                        tbl_hd_chamado.status, 
                       tbl_hd_chamado_extra.cpf, 
                       tbl_hd_chamado_extra.consumidor_revenda,
                       to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') as data_providencia
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                WHERE $cond
                tbl_hd_chamado.fabrica = {$login_fabrica}
                AND status in ('Aberto', 'Resolvido')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $msg_erro["erro"] = true;
            $msg_erro["msn"]  = utf8_encode("Nenhum protocolo encontrado.");
        } else {

            if (pg_num_rows($res) > 0) {
                $hd_chamados        = pg_fetch_all($res);
                $cpf_cnpj           = pg_fetch_result($res, 0, cpf);
                $data_providencia   = pg_fetch_result($res, 0, data_providencia);
                $status             = pg_fetch_result($res, 0, status);
                $consumidor_revenda = pg_fetch_result($res, 0, consumidor_revenda);
                $retorno["sucesso"] = true;
                $msn_cpf = (strlen($cpf_cnpj) >= 14) ? "CNPJ: {$cpf_cnpj}" : "CPF: {$cpf_cnpj}";
                foreach ($hd_chamados as $k => $row) {
                    $sqlItem = "SELECT 
                                    CASE WHEN tbl_admin.login not in('Consumidor','Revenda')  THEN 
                                        'Esmaltec' 
                                    ELSE 
                                        tbl_admin.login
                                    END AS admin,
                                     to_char(data,'DD/MM/YYYY  HH24:MI') as data_interacao, 
                                     data, 
                                     comentario,
                                     to_char(termino,'DD/MM/YYYY  HH24:MI') as termino, 
                                     status_item 
                                FROM tbl_hd_chamado_item 
                                join tbl_admin using(admin)
                               WHERE (interno IS FALSE OR interno  is null)
                                 AND empregado = 1
                                 AND hd_chamado = {$row['hd_chamado']}
                            ORDER BY data DESC;";

                    $resItem = pg_query($con, $sqlItem);

                    if (pg_num_rows($resItem) > 0) {

                        $dadosItens = pg_fetch_all($resItem);
                        $contador = 0;
                        foreach ($dadosItens as $key => $value) {
                            $dados[$key]["admin"]       = $value["admin"];
                            $dados[$key]["data"]        = $value["data_interacao"];
                            $dados[$key]["data_providencia"] = substr($value["termino"], 0, 10);
							if($contador == 0 and $dados[$key]["data_providencia"] <> $data_providencia) {
								$dados[$key]["data_providencia"] = $data_providencia;
							}
                            $dados[$key]["comentario"]  = utf8_encode($value["comentario"]);
                            $dados[$key]["status_item"] = ($value["status_item"] != null)? $value["status_item"] : '';
                            $contador++;
                        }
                        $hd_chamados[$k]["historico_atendimento"] = $dados; 
                        $hd_chamado_id = $hd_chamados[$k]['hd_chamado'];
                        $tdocs_id = pg_fetch_result($res_tdocs, $t, 'tdocs_id'); 
                        $tDocs->setContext('callcenter');
                        $file_links = $tDocs->getDocumentsByRef($hd_chamado_id)->attachListInfo;

                        $imagemAnexo = "imagens/imagem_upload.png";
                        $linkAnexo   = "#";
                        $tdocs_id   = "";

                        if ($hd_chamado_id > 0) {
                            if (count($file_links) > 0) {
                                foreach ($file_links as $j => $vAnexo) {
                                    $linkAnexo   = $vAnexo["link"];
                                    $imagemAnexo = $vAnexo["link"];
                                    $tdocs_id = $vAnexo["tdocs_id"];
                                    $hd_chamados[$k]["historico_anexo"][] = $linkAnexo ; 
                                }
                            } 
                        }
                    }
                }

                $retorno['status'] = $status; 
                $retorno['consumidor_revenda'] = $consumidor_revenda;
                $retorno["msn"] = utf8_encode("Identificamos que seu <b>{$msn_cpf}</b> já possui protocolo de atendimento e está sendo acompanhado pela nossa equipe especializada<br /><br />
                                Pedimos que verifique abaixo as informações e status do seu protocolo, caso necessário,
                                adicione alguma mensagem no campo abaixo que será direcionada a equipe CARE (Centro de
                                Atenção e Relacionamento Esmaltec) que realizará o contato em até 24 horas úteis a contar do
                                horário da sua solicitação.");
                $retorno["protocolos"] = $hd_chamados;
                exit(json_encode($retorno));
            } else {
                $msg_erro["erro"] = true;
                $msg_erro["msn"]  = utf8_encode("Nenhum protocolo encontrado.");
            }
        }
    }
    exit(json_encode($msg_erro));
}

if ($_POST["carregaProvidencia"] == true) {
    $retorno    = array();
    $msg_erro   = array();
    $hd_chamado = $_POST['hd_chamado'];

    $sql = "SELECT tbl_hd_motivo_ligacao.descricao, tbl_hd_motivo_ligacao.prazo_dias, tbl_hd_chamado_extra.array_campos_adicionais, tbl_hd_chamado.status
              FROM tbl_hd_chamado
              JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
              JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
             WHERE tbl_hd_chamado.hd_chamado={$hd_chamado}
               AND tbl_hd_chamado.fabrica = {$login_fabrica}
               AND tbl_hd_chamado.status in('Aberto', 'Resolvido')";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        $msg_erro["erro"] = true;
        $msg_erro["msn"]  = utf8_encode("Nenhuma providencia encontrada.");
    } else {

        if (pg_num_rows($res) > 0) {
            $rows = pg_fetch_assoc($res);

            $retorno["sucesso"] = true;
            $retorno["descricao"] = utf8_encode($rows["descricao"]);
            $retorno["prazo_dias"] = $rows["prazo_dias"];
            $retorno["status"] = $rows["status"];
            $retorno["observacao_sac"] = "";
            $dadosAdicionais = array_map('utf8_decode',json_decode($rows["array_campos_adicionais"], TRUE));
            //if (isset($dadosAdicionais["observacao_sac"]) && !empty($dadosAdicionais["observacao_sac"])) {
            //    $retorno["observacao_sac"] = utf8_encode("Observação do SAC: <br /><b>".$dadosAdicionais["observacao_sac"]."</b><br /><br>");
            //}

            exit(json_encode($retorno));
        } else {
            $msg_erro["erro"] = true;
            $msg_erro["msn"]  = utf8_encode("Nenhuma providencia encontrada.");
        }

    }
    exit(json_encode($msg_erro));
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
        echo json_encode(array("erro" => true, "msn" => utf8_encode("Nenhum produto encontrada para esta família.")));
    }
    exit;
}

if($_POST["buscaDefeitoRaclamado"] == "buscaDefeitoRaclamado"){

    if ($_GET["lupa"] == true) {
        $produto = $_REQUEST['produto'];
        $sql = "SELECT familia, produto
                  FROM tbl_produto
                 WHERE referencia = '{$produto}'
                   AND fabrica_i  = {$login_fabrica}
                 LIMIT 1";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $familia = pg_fetch_result($res,0,'familia');
            $xproduto = pg_fetch_result($res,0,'produto');
            if (strlen($familia) == 0) {
                echo json_encode(array("erro" => true, "msn" => utf8_encode("Nenhum defeito encontrada para esta família.")));
                exit;
            }
        } else {
            echo json_encode(array("erro" => true, "msn" => utf8_encode("Nenhum defeito encontrada para esta família.")));
            exit;
        }
    } else {
        $familia = $_REQUEST['familia_revenda'];
    }

    if (strlen($familia) == 0) {
        echo json_encode(array("erro" => true, "msn" => utf8_encode("Nenhum defeito encontrada para esta família.")));
        exit;
    }
    $sql = "SELECT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
                     FROM tbl_diagnostico
                     JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                    WHERE tbl_diagnostico.familia=$familia
                      AND tbl_diagnostico.fabrica={$fabrica}
                      AND tbl_defeito_reclamado.fabrica={$fabrica}
                 ORDER BY tbl_defeito_reclamado.descricao ASC;";
    $res = pg_query($con,$sql);
    $retorno = array();
    if (pg_num_rows($res) > 0) {
        for ($i=0; $i<pg_num_rows($res); $i++ ){
            $defeito_reclamado = pg_fetch_result($res,$i,'defeito_reclamado');
            $descricao         = pg_fetch_result($res,$i,'descricao');
            $retorno["defeitos"][] = array("defeito_reclamado" => $defeito_reclamado, "descricao" => utf8_encode($descricao));
        }
            $retorno["familia"] = $familia;
            $retorno["produto"] = $xproduto;

        echo json_encode($retorno);
    } else {
        echo json_encode(array("erro" => true, "msn" => utf8_encode("Nenhum defeito encontrada para esta família.")));
    }
    exit;
}


if (isset($_POST["acao"])) {

    $tipo_contato   = $_REQUEST['tipo_contato'];
    $anexos = array();
    if ($tipo_contato == "C") {
        $anexo_c                    = $_POST['anexo'];
        $aux_nome                   = $_POST['nome'];
        $aux_consumidor_cpf_cnpj    = $_POST['consumidor_cpf_cnpj'];
        $aux_cpf                    = preg_replace("/\D/","",$_POST['cpf']);
        $aux_email                  = $_POST['email'];
        $aux_telefone               = $_POST['telefone'];
        $aux_cep                    = $_POST['cep'];
        $aux_cep                    = str_replace (".","",$aux_cep);
        $aux_cep                    = str_replace ("-","",$aux_cep);
        $aux_cep                    = str_replace (" ","",$aux_cep);
        $aux_endereco               = $_POST['endereco'];
        $aux_numero                 = $_POST['numero'];
        $aux_complemento            = $_POST['complemento'];
        $aux_bairro                 = $_POST['bairro'];
        $aux_cidade                 = $_POST['cidade'];
        $aux_estado                 = $_POST['estado'];
        $aux_assunto                = $_POST['assunto'];
        $aux_familia                = $_POST['familia'];
        $aux_produto                = $_POST['produto'];
        $aux_msg                    = pg_escape_string($_POST['msg']);
        $hd_classificacao           = 44;
        $aux_nf_produto             = $_POST['nf_produto_fale'];
        $aux_serie_produto          = filter_input(INPUT_POST,'serie_produto_fale',FILTER_SANITIZE_NUMBER_INT);
        $aux_data_compra_produto    = $_POST['data_compra_produto_fale'];
        $aux_defeito_reclamado      = $_POST['defeito_reclamado_fale'];

        $id_revenda                 = (int)$_POST["id_revenda_revenda_consumidor"];
        $descricao_revenda          = $_POST["descricao_revenda_consumidor"];
        $revenda_cnpj               = $_POST["cnpj_rev_consumidor"];

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

        $tipoContato = "Consumidor";
    }

    if ($tipo_contato == "R") {

        $anexo_r                    = $_POST['anexo_r'];
        $aux_nome                   = $_POST['nome_revenda'];
        $aux_consumidor_cpf_cnpj    = $_POST['cpf_cnpj_revenda'];
        $aux_cpf                    = preg_replace("/\D/","",$_POST['cpf_revenda']);
        $aux_email                  = $_POST['email_revenda'];
        $aux_telefone               = $_POST['telefone_revenda'];
        $aux_cep                    = $_POST['cep_revenda'];
        $aux_cep                    = str_replace (".","",$aux_cep);
        $aux_cep                    = str_replace ("-","",$aux_cep);
        $aux_cep                    = str_replace (" ","",$aux_cep);
        $aux_endereco               = $_POST['endereco_revenda'];
        $aux_numero                 = $_POST['numero_revenda'];
        $aux_complemento            = $_POST['complemento_revenda'];
        $aux_bairro                 = $_POST['bairro_revenda'];
        $aux_cidade                 = $_POST['cidade_revenda'];
        $aux_estado                 = $_POST['estado_revenda'];
        $aux_assunto                = $_POST['assunto_revenda'];
        $aux_familia                = $_POST['familia_revenda'];
        $aux_produto                = $_POST['produto_revenda'];

        $id_revenda                 = (int)$_POST["id_revenda_revenda"];
        $descricao_revenda          = $_POST["descricao_revenda"];
        $revenda_cnpj               = $_POST["cnpj_rev"];

                

        /* if ($_POST['tipo_busca'] == 'produto') {
            $aux_produto            = $_POST['produto_revenda_lupa'];
        }
        if ($_POST['tipo_busca'] == 'familia') {
            $aux_produto            = $_POST['produto_revenda'];
        } */
        $aux_serie_produto            = filter_input(INPUT_POST,'serie_produto_revenda',FILTER_SANITIZE_NUMBER_INT);
        $aux_nf_produto               = $_POST['nf_produto_revenda'];
        $aux_data_compra_produto      = $_POST['data_compra_produto_revenda'];
        
        if(empty($aux_data_compra_produto)) {
            $aux_data_compra_produto = "null";
        }
        $aux_data_nasc_revenda    = $_POST['data_nasc_revenda'];
        $aux_defeito_reclamado      = $_POST['defeito_reclamado_revenda'];
        if(empty($aux_defeito_reclamado)) {
            $aux_defeito_reclamado = "null";
        }
        $aux_msg                    = pg_escape_string($_POST['msg_revenda']);

        if (!empty($aux_data_nasc_revenda)) {
            list($dd,$mm,$aaaa) = explode("/",$aux_data_nasc_revenda);
            $aux_data_nasc_revenda = "'".$aaaa."-".$mm."-".$dd."'";
        } else {
            $aux_data_nasc_revenda = 'NULL';
        }

        list($dnd,$dnm,$dna) = explode("/",$aux_data_compra_produto);
        $aux_data_compra_produto = "'".$dna.'-'.$dnm.'-'.$dnd."'";
        if(empty($_POST['data_compra_produto_revenda'])) {
            $aux_data_compra_produto = "null";
        }
        
        $tipoContato = "Revenda";
        $hd_classificacao = 45;

    }

    //busca admin 
    $sql_admin = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica and login = '$tipoContato' ";
    $res_admin = pg_query($con, $sql_admin);
    if(pg_num_rows($res_admin)>0){
        $id_admin = pg_fetch_result($res_admin, 0, 'admin');
    }else{
        $msg_erro .= "Erro ao encontrar usuário do atendimento.";
    }
    
    if(!empty($aux_produto)){
        $sqlProduto = " SELECT referencia, descricao FROM tbl_produto WHERE produto = $aux_produto and fabrica_i = $login_fabrica ";
        $resProduto = pg_query($con, $sqlProduto);
        if(pg_num_rows($resProduto)==0){
            $msg_erro .= "Produto inválido";
        }
    }

    $array_assuntos = array(
                        "sugestao"           =>  "Sugestão",
                        "reclamacao_at"      =>  "Reclamação da Assistência Técnica",
                        "reclamacao_empresa" =>  "Reclamação da Empresa",
                        "reclamacao_produto" =>  "Reclamação de Produto/Defeito",
                      );

    if(strlen($aux_nome) == 0){
        $msg_erro = "Preencha o nome <br>";
    }
    $msg_erro_existe_protocolo = false;
    $msg_erro_existe_protocolo_r = false;
    if(strlen($aux_cpf) == 0){
        $msg_erro .= "Preencha o CPF ou CNPJ <Br>";
    } else {
         $sql = "SELECT fn_valida_cnpj_cpf('{$aux_cpf}')";
         $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= "CPF/CNPJ Inválido! Digite corretamente o nº de seu CPF/CNPJ";
        } else {
            if ($tipo_contato == 'C') {
                $sql = "SELECT  tbl_hd_chamado.hd_chamado, tbl_hd_chamado_extra.cpf
                        FROM    tbl_hd_chamado
                        JOIN    tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        WHERE   tbl_hd_chamado.fabrica      = {$login_fabrica}
                        AND     tbl_hd_chamado_extra.cpf    = '{$aux_cpf}'
                        AND     status                      = 'Aberto'";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $hd_chamados = pg_fetch_all($res);
                    $cpf_cnpj = pg_fetch_result($res, 0, cpf);
                    $msn_cpf = (strlen($cpf_cnpj) >= 14) ? "CNPJ: {$cpf_cnpj}" : "CPF: {$cpf_cnpj}";

                    $msg_erro_existe_protocolo = true;
                    $msg_erro = "Identificamos que seu <b>{$msn_cpf}</b> já possui protocolo de atendimento e está sendo acompanhado pela nossa equipe especializada<br /><br />
                                Pedimos que verifique abaixo as informações e status do seu protocolo, caso necessário,
adicione alguma mensagem no campo abaixo que será direcionada a equipe CARE (Centro de
Atenção e Relacionamento Esmaltec) que realizará o contato em até 24 horas úteis a contar do
horário da sua solicitação.";
                }
            }
        }
    }

    if (strlen($anexo_r) == 0 AND strlen($msg_erro) == 0 && $tipo_contato == "R"){
        $array_tipo_anexo = array(1 => "NF", 2 => "Etiqueta de série", 3 => "Foto do produto");
        foreach ($anexo_r as $key => $rows) {
            if (empty($rows)) {
                if ($key < 3) {
                   # $msg_erro .= "O anexo da <b>" . $array_tipo_anexo[$key] . "</b> é Obrigatório.<br />";
                }
            }
        }
    }

    if (strlen($anexo_c) == 0 AND strlen($msg_erro) == 0 && $tipo_contato == "C"){
        $array_tipo_anexo = array(1 => "NF", 2 => "Etiqueta de série", 3 => "Foto do produto");
        foreach ($anexo_c as $key => $rows) {
            if (empty($rows)) {
                if ($key < 3) {
                   # $msg_erro .= "O anexo da <b>" . $array_tipo_anexo[$key] . "</b> é Obrigatório.<br />";
                }
            }
        }
    }

    if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
        $aux_email = "";
    }

    if(strlen($aux_nf_produto) == 0){
        $msg_erro .= "Preencha o campo NF <br>";
    }

    if(strlen($aux_endereco) == 0){
        $msg_erro .= "Preencha o campo Endere&ccedil;o <br>";
    }

    if(strlen($aux_numero) == 0){
        $msg_erro .= "Preencha o campo N&uacute;mero <br>";
    }

    if(strlen($aux_complemento) == 0 AND strlen($msg_erro) == 0){
        $aux_complemento = '';
    }

    if(strlen($aux_bairro) == 0){
        $msg_erro .= "Preencha o campo Bairro <br>";
    }

    if(strlen($aux_estado) == 0){
        $msg_erro .= "Preencha o campo Estado <br>";
    }

    if(strlen($aux_cep) == 0 AND strlen($msg_erro) == 0){
        $msg_erro .= "Preencha o campo CEP <br>";
    }

    if($id_revenda == 0){
        $msg_erro .= "Preencha o campo revenda.<br>";
    }

    $xtelefone = str_replace(array("(", ")", "-", " "), "", $aux_telefone);

    if(strlen($xtelefone) == 0 ){
        $msg_erro .= "Preencha o campo Telefone <br>";
    }

    if(strlen($xtelefone)< 10 and strlen($xtelefone) > 0){
        $msg_erro .= "Número de Telefone Inválido <br>";   
    }

    if(strlen($aux_cidade) == 0 ){
        $msg_erro .= "Preencha o campo Cidade  <br>";
    }else{
        if(strlen($msg_erro)==0){
            if (strlen($aux_estado)>0 and strlen($aux_cidade)>0) {
                    // Verifica Cidade

                    $cidade = $aux_cidade;
                    $estado = $aux_estado;

                    $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
                    $res = pg_query($con, $sql);

                    if(pg_num_rows($res) == 0){

                        $sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
                        $res = pg_query($con, $sql);

                        if(pg_num_rows($res) > 0){

                            $cidade = pg_fetch_result($res, 0, 'cidade');
                            $estado = pg_fetch_result($res, 0, 'estado');

                            $sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
                            $res = pg_query($con, $sql);

                        }else{
                            $cidade = 'null';
                        }

                    }else{
                        $cidade = pg_fetch_result($res, 0, 'cidade');
                    }


            }elseif($indicacao_posto=='f') {
                $msg_erro .= "Informe a cidade do consumidor";
            }
        }
    }

    if(strlen($aux_assunto) < 2 ){
        $msg_erro .= "Selecione um assunto  <br>";
    }

    /*if((strlen($aux_familia) == 0 OR $aux_familia == 0) AND strlen($msg_erro) == 0){
        $msg_erro = "Selecione uma família";
    }*/

    if((strlen($aux_produto) == 0 OR $aux_produto == 0)){
        $msg_erro .= "Selecione um produto <br>";
    }

    if(strlen($aux_msg) == 0){
        $msg_erro .= "Preencha o campo mensagem <br>";
    }

    /*if(strlen($aux_msg) == 0){
        $msg_erro .= "Preencha o campo NF";
    }*/

    if(strlen($msg_erro) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");

        $titulo            = 'Atendimento interativo';
        $xstatus_interacao = "'Aberto'";

        $aux_msg = '<b>'.$array_assuntos[$aux_assunto].'</b> <br /> '.$aux_msg;

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


        $sql = "SELECT  hd_classificacao,
                        descricao
                    FROM tbl_hd_classificacao
                    WHERE fabrica = {$login_fabrica}
                    AND hd_classificacao = {$hd_classificacao}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $descricao_cla = pg_fetch_result($res, 0, descricao);

            if ($descricao_cla === 'PROJETO - BACKOFFICE CENTRALIZAÇÃO' ){
                $sql = "SELECT tbl_posto_fabrica.admin_sap as atendente
                            FROM tbl_posto_fabrica
                            WHERE posto = $xcodigo_posto
                                AND fabrica = $login_fabrica
                                AND admin_sap IS NOT NULL;";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $atDoDia = pg_fetch_all($res);
                    $backoffice_centralizado = true;
                }
            }
        }

        if (empty($atDoDia)) {
            /*
            * - Verifica TODOS os atendentes
            * Responsáveis pela região E classificação
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
        }

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
                AND     tbl_hd_chamado.data::DATE = CURRENT_DATE
                ";
            $resCont = pg_query($con,$sqlCont);
            $contaChamados = pg_fetch_result($resCont,0,chamados_hoje);
            $qtdeChamados[$value['atendente']] = $contaChamados;
        }

        /*
         * - Retira o atendente
         * com menor número de chamados
         * atendidos no dia para gravação do próximo
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
            if ($backoffice_centralizado == true) {
                $sql = "SELECT  tbl_posto_fabrica.admin_sap as admin,
                                tbl_admin.login
                            FROM tbl_posto_fabrica
                                JOIN tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
                                    AND tbl_posto_fabrica.fabrica={$login_fabrica}
                                JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
                            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                                AND tbl_posto_fabrica.posto = {$xcodigo_posto}
                                AND tbl_posto_fabrica.admin_sap IN(".implode(",",$atendentes).")
                            GROUP BY    tbl_posto_fabrica.admin_sap,
                                        tbl_admin.login
                            ORDER BY tbl_posto_fabrica.admin_sap";
            } else {
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
            }

            $resP = pg_query($con,$sql);

            $novo_atendente = pg_fetch_result($resP, 0, 'admin');
            $nome_atendente = pg_fetch_result($resP, 0, 'login');
        }

        if(strlen($aux_email) == 0 AND strlen($msg_erro) == 0){
            $aux_email = "null";
        }

        if ($tipo_contato == "C") {
            $camposAddHD  = "hd_classificacao,";
            $valoresAddHD = "$hd_classificacao,";
        }

        if ($tipo_contato == "R") {
            $camposAddHD  = "hd_classificacao,";
            $valoresAddHD = "$hd_classificacao,";
        }

        $sqlMotivo = "SELECT hd_motivo_ligacao, prazo_dias
                        FROM tbl_hd_motivo_ligacao
                       WHERE fabrica={$login_fabrica}
                         AND hd_classificacao ='$hd_classificacao'";
        $resMotivo = pg_query($con, $sqlMotivo);

        if (pg_num_rows($resMotivo) > 0) {
            $hd_motivo_ligacao = pg_fetch_result($resMotivo, 0, 'hd_motivo_ligacao');

            $sqlProvidencia = "SELECT fn_calcula_previsao_retorno(current_date,prazo_dias,$login_fabrica)::date AS data_providencia FROM tbl_hd_motivo_ligacao
                                WHERE hd_motivo_ligacao = {$hd_motivo_ligacao}";
            $resProvidencia = pg_query($con, $sqlProvidencia);
            $data_providencia = pg_fetch_result($resProvidencia, 0, 'data_providencia');

        }

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
                    $id_admin               ,
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

        if ($tipo_contato == "C") {
            $camposAdd  = "origem,consumidor_revenda,defeito_reclamado,data_nf,nota_fiscal,";
            $valoresAdd = "'fale','C',$aux_defeito_reclamado,$aux_data_compra_produto,'$aux_nf_produto',";
            $anexos = $anexo_c;
        }

        if ($tipo_contato == "R") {
            $camposAdd  = "data_nascimento,origem,consumidor_revenda,defeito_reclamado,data_nf,nota_fiscal,";
            $valoresAdd = "$aux_data_nasc_revenda,'fale','R',$aux_defeito_reclamado,$aux_data_compra_produto,'$aux_nf_produto',";
            $anexos = $anexo_r;
        }

        $sql = "INSERT INTO tbl_hd_chamado_extra(
                            hd_chamado           ,
                            revenda ,
                            revenda_nome , 
                            revenda_cnpj , 
                            produto              ,
                            serie               ,
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
                            hd_motivo_ligacao    ,
                            {$camposAdd}
                            array_campos_adicionais
                        )values(
                        $hd_chamado              ,
                        $id_revenda              , 
                        '$descricao_revenda'     ,
                        '$revenda_cnpj'          ,
                        $aux_produto             ,
                        '$aux_serie_produto'     ,
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
                        $hd_motivo_ligacao       ,
                        {$valoresAdd}
                        '$fale_conosco'
                        ) ";
        $res = pg_query($con,$sql);

        if (strlen(pg_errormessage($con)) > 0) {
            $msg_erro .= "Não foi possível registrar o atendimento.".pg_last_error($con);
        }

        //Adicionado para registra a 1ª interação do consumidor na consulta hd-6279240
        $sql = "INSERT INTO tbl_hd_chamado_item (
                    admin,
                    status_item,
                    hd_chamado ,
                    comentario,
                    termino,
                    empregado
                ) VALUES (
                    $id_admin,
                    'Aberto',
                    $hd_chamado,
                    '$aux_msg',
                    '$data_providencia',
                    1
                )";
        $res = pg_query($con,$sql);

        if (strlen(pg_errormessage($con)) > 0) {
            $msg_erro .= "Não foi possível registrar o interação do consumidor.".pg_last_error($con);
        }

        if (empty($msg_erro) && !empty($anexos)) {

            foreach ($anexos as $anexo) {
                if (empty($anexo)) {
                    continue;
                }               

                $dadosAnexo = json_decode($anexo, 1);
                if (empty($dadosAnexo)) {
                    continue;
                }

   
                $anexoID = $tDocs->setDocumentReference($dadosAnexo, $hd_chamado, "anexar", false, "callcenter");
                if (!$anexoID) {
                    $msg_erro["msg"][] = 'Erro ao fazer upload do banner!';
                }
            }

        }

    }

    if (strlen ($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF?msg_sucesso=ok&protocolo=$hd_chamado&tipo_contato=$tipo_contato");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        if ($tipo_contato == "C") {
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
            $aux_assunto                = $_POST['assunto'];
            $aux_familia                = $_POST['familia'];
            $aux_produto_fale           = $_POST['produto'];
            $aux_msg                    = $_POST['msg'];
            $anexo_c = $_POST['anexo'];
            $aux_familia_fale           = $_POST['familia_fale'];
            $aux_nf_produto_fale        = $_POST['nf_produto_fale'];
            $aux_serie_produto          = filter_input(INPUT_POST,'serie_produto_fale',FILTER_SANITIZE_NUMBER_INT);
            $aux_data_compra_produto    = $_POST['data_compra_produto_fale'];
            $aux_defeito_reclamado      = $_POST['defeito_reclamado_fale'];
        }

        if ($tipo_contato == "R") {
            $aux_nome_revenda           = $_POST['nome_revenda'];
            $aux_cpf_cnpj_revenda       = $_POST['cpf_cnpj_revenda'];
            $aux_cpf_revenda            = $_POST['cpf_revenda'];
            $aux_email_revenda          = $_POST['email_revenda'];
            $aux_telefone_revenda       = $_POST['telefone_revenda'];
            $aux_cep_revenda            = $_POST['cep_revenda'];
            $aux_endereco_revenda       = $_POST['endereco_revenda'];
            $aux_numero_revenda         = $_POST['numero_revenda'];
            $aux_complemento_revenda    = $_POST['complemento_revenda'];
            $aux_bairro_revenda         = $_POST['bairro_revenda'];
            $aux_cidade_revenda         = $_POST['cidade_revenda'];
            $aux_estado_revenda         = $_POST['estado_revenda'];
            $aux_assunto_revenda        = $_POST['assunto_revenda'];
            $aux_familia_revenda        = $_POST['familia_revenda'];
            $aux_produto_revenda       = $_POST['produto_revenda'];
            $aux_voltagem_produto_revenda       = $_POST['voltagem_produto_revenda'];
            $aux_nf_produto_revenda             = $_POST['nf_produto_revenda'];
            $aux_data_compra_produto_revenda    = $_POST['data_compra_produto_revenda'];
            $aux_data_nasc_revenda    = $_POST['data_nasc_revenda'];
            $aux_defeito_reclamado_revenda      = $_POST['defeito_reclamado_revenda'];
            $aux_msg_revenda                    = $_POST['msg_revenda'];
            $anexo_r = $_POST["anexo_r"];
        }

    }
}


if ($_POST["ajax_anexo_upload"] == true) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif'),'posicao' => $posicao);

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


if ($_POST["ajax_anexo_upload_r"] == true) {
    $posicao = $_POST["anexo_posicao_r"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_r_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif'),'posicao' => $posicao);

        } else {

            if ($_FILES["anexo_r_upload_{$posicao}"]["tmp_name"]) {

                $anexoID      = $tDocs->sendFile($_FILES["anexo_r_upload_{$posicao}"]);
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
    $msg = "Contato Gravado com Sucesso! <br /> Número de protocolo: <strong>$protocolo</strong> <br /> Entraremos em contato em até 24 horas úteis. Como a Esmaltec se localiza no estado do Ceará, as nossas ligações serão identificadas através do DDD 85. ";

    if ($tipo_contato == "C") {
        $mensagem_sucesso = $msg;
    }

    if ($tipo_contato == "R") {
        $mensagem_sucesso_r = $msg;
    }

}

if (strlen($msg_erro) > 0) {
    $tipo_contato   = $_POST['tipo_contato'];

    if ($tipo_contato == "C") {
        $mensagem_erro = $msg_erro;
    }

    if ($tipo_contato == "R") {
        $mensagem_erro_r = $msg_erro;
    }
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
    "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
    "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
    "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
    "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
    "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
    "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
    "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <title><?php echo $titulo;?></title>
            <link href="css/style.css?v=<?php echo date("YmdHis");?>" rel="stylesheet">
            <style type="text/css">
                @import "../../plugins/jquery/datepick/telecontrol.datepick.css";

                .ac_results { padding: 0px; border: 1px solid black; background-color: white; overflow: hidden; z-index: 99999; }
                .ac_results ul { width: 100%; list-style-position: outside; list-style: none; padding: 0; margin: 0; }
                .ac_results li { margin: 0px; padding: 2px 5px; cursor: default; display: block;
                    /*
                    if width will be 100% horizontal scrollbar will apear
                    when scroll mode will be used
                    */
                    /*width: 100%;*/
                    font: menu; font-size: 12px;
                    /*
                    it is very important, if line-height not setted or setted
                    in relative units scroll will be broken in firefox
                    */
                    line-height: 16px; overflow: hidden;
                }
                .ac_loading { background: white url('../css/indicator.gif') right center no-repeat; }
                .ac_odd { background-color: #eee; }
                .ac_over { background-color: #0A246A; color: white; }
            </style>


        </head>
		<body>
<div style="position:relative; top:1px ;left:5px  " class='tabs'>
  <div style="line-height:20px; text-align: justify;z-index: 1000;top:100;left 50; width:485px; background-color:#eee ;font-size:13px;color:#444;padding:20px;">


<b>Prezados,</b><br>

Estamos seguindo as orientações da OMS - Organização Mundial de Saúde - e atendendo a determinação do Plano de Retomada da Economia do Governo Estadual do Ceará, estamos retornando às nossas atividades de forma gradual e em etapas com todos os cuidados e segurança recomendados.
<bR><br>
Com isso, o nosso prazo de retorno está um pouco maior do que o habitual, porém fiquem tranquilos, pois nos empenharemos para fazê-lo o mais breve possível.
<br><br>
Solicitamos a gentileza de aguardar o nosso breve contato.
<br><br>
Agradecemos a compreensão e lembramos que juntos somos mais fortes!
<br><br>
 </div>
</div>                <div id="tabs" class="tabs">
                    <ul class="tabset">
                        <li class="select_tipo <?php if ($tipo_contato == "C" && strlen($tipo_contato) == 0) {echo 'active';}?>" data-tipo="C" id="opentab_1"><h3 href="#" onclick="opentab(1);"><span><span>Canal Consumidor</span></span></h3></li>
                        <li class="select_tipo <?php if ($tipo_contato == "R") {echo 'active';}?>" data-tipo="R" id="opentab_2"><h3 href="#" onclick="opentab(2);"><span><span>Canal Revenda</span></span></h3></li>
                        <li class="select_tipo active <?php if ($tipo_contato == "P") {echo 'active';}?>" data-tipo="R" id="opentab_3"><h3 href="#" onclick="opentab(3);"><span><span>Consulta de Protocolo</span></span></h3></li>
                    </ul>
                    <div id="content">
                        <div class="blocks" style="<?php echo ($tipo_contato == "C" || strlen($tipo_contato) == 0) ? "display: block;" : "display: none;"; ?>">
                        <div class="t">&nbsp;</div>
                            <div class="holder">
                            <div class="frame">
                                <div class="tab-content">

                                    <?php
                                        if (strlen($mensagem_sucesso) > 0) {
                                            echo "<p class='clear sucesso'>$mensagem_sucesso</p>";
                                        }

                                        if (strlen($mensagem_erro) > 0 && $msg_erro_existe_protocolo) {
                                    ?>
                                            <div class="clear msg_erro errorproto">
                                                <?php echo $mensagem_erro;?>
                                                <br /><hr/><br />
                                                <div id="men_retorno" class="men_retorno"></div>
                                                <div id="form_protrocolo" align="left">
                                                    <input type="hidden" name="tipo" id="tipo" value="C" />
                                                    <label class="txt_label">Nº Protocolo: </label>
                                                    <?php
                                                        if(count($hd_chamados) == 1) {
                                                            echo "<input name='protocolo' style='width:30%' readonly='readonly' class='input_text protocolo' value='".$hd_chamados[0]["hd_chamado"]."' id='consulta_protocolo'/>";
                                                        } else {
                                                    ?>
                                                    <select class="input_select protocolo" name="protocolo" style="width: 50%" id="protocolo">
                                                        <option value="" >Escolha o protocolo que deseja interagir...</option>
                                                        <?php foreach ($hd_chamados as $key => $value) {?>
                                                        <option value="<?php echo $value["hd_chamado"];?>"><?php echo $value["hd_chamado"];?></option>
                                                        <?php }?>
                                                    </select>
                                                    <?php }?>
                                                    <div class="mensagem_providencia_c" style="display: none"></div>
                                                    <div class="mensagem_historicos_c" style="display: none"></div>
                                                    <label class="txt_label">Mensagem: </label>
                                                    <textarea name="txt_protocolo" rows="10" id="txt_protocolo" class="textarea input_text"></textarea><br />
                                                    
                                                    <button type="button" name="btn_enviar_msn" id="btn_enviar_msn">Enviar</button>
                                                </div>
                                            </div>
                                    <?php
                                        }
                                        if (strlen($mensagem_erro) > 0 && !$msg_erro_existe_protocolo) {
                                            echo "<div class='clear msg_erro error'>$mensagem_erro</div>";
                                        }

                                    ?>
                                    <form method="post"  action="<?=$PHP_SELF?>" method="post" name="FormContato" id="FormContato" enctype="multipart/form-data">
                                        <input type="hidden" value='C' name="tipo_contato" class="tipo_contato">
                                        <input type="hidden" value='Gravar' name="acao" >

                                        <div class="FimDosFloats"></div>
                                        <label for="Nome" class="Nome campo_obrigatorio">*Nome:</label>
                                        <input type="text" maxlength="50" name="nome" id="nome" value='<?php echo $aux_nome;?>' class="input_text">

                                        <div class="ColEsq">
                                            <input type='radio' name='consumidor_cpf_cnpj' id='consumidor_cfp' value='C'
                                            <?PHP
                                                if ($aux_consumidor_cpf_cnpj  == "C") {
                                                    echo "CHECKED";
                                                }
                                            ?>
                                            onclick="fnc_tipo_atendimento(this)">

                                            <label for="cpf">CPF</label>
                                            <input type='radio' name='consumidor_cpf_cnpj' id='consumidor_cnpj' value='R'
                                                <?PHP
                                                    if ($aux_consumidor_cpf_cnpj == "R") {
                                                        echo "CHECKED";
                                                    }
                                                ?>
                                                onclick="fnc_tipo_atendimento(this)">

                                            <label for="consumidor_cfp">CNPJ</label>
                                            <input type="text" name="cpf" id="cpf" value='<?php echo $aux_cpf;?>'  class="input_text cpf">
                                        </div>

                                        <div class="ColDir">
                                            <label for="email">E-mail:</label>
                                            <input type="email" name="email" id="email" value='<?php echo $aux_email;?>'  class="input_text">
                                        </div>

                                        <div class="FimDosFloats"></div>

                                        <div class="ColEsq">
                                            <label for="telefone" class="campo_obrigatorio">*Telefone:</label>
                                            <input type="text" name="telefone" id="telefone" value='<?php echo $aux_telefone;?>' class="input_text telefone">
                                        </div>
                                        <div class="ColDir">
                                            <label for="cep" class="campo_obrigatorio">*CEP:</label>
                                            <input type="text" name="cep" id="cep" onblur="buscaCEP(this.value, 'C')" value='<?php echo $aux_cep;?>' class="input_text cep">
                                        </div>
                                        <div class="FimDosFloats"></div>

                                        <div class="ColEsq">
                                            <label for="endereco" class="campo_obrigatorio">*Endere&ccedil;o:</label>
                                            <input type="text" maxlength="70" name="endereco" id="endereco" value='<?php echo $aux_endereco;?>' class="input_text">
                                        </div>
                                        <div class="ColDir">
                                            <label for="numero" class="campo_obrigatorio">*N&uacute;mero:</label>
                                            <input type="text" maxlength="20" name="numero" id="numero" value='<?php echo $aux_numero;?>' class="input_text">
                                        </div>
                                        <div class="FimDosFloats"></div>
                                        <div class="ColEsq">
                                            <label for="complemento">Complemento:</label>
                                            <input type="text" maxlength="40" name="complemento" id="complemento" value='<?php echo $aux_complemento;?>' class="input_text">
                                        </div>
                                        <div class="ColDir">
                                            <label for="bairro" class="campo_obrigatorio">*Bairro:</label>
                                            <input type="text" maxlength="60" name="bairro" id="bairro" value='<?php echo $aux_bairro;?>' class="input_text">
                                        </div>
                                        <div class="FimDosFloats"></div>
                                        <div class="ColEsq">
                                            <label for='estado' class="campo_obrigatorio">*Estado:</label>
                                            <select name='estado' id='estado' class="input_select" style="display: block;">
                                                <option></option>
                                                <?php
                                                    foreach ($array_estado as $k => $v) {
                                                        echo '<option value="'.$k.'"'.($aux_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="ColDir">
                                            <label for="cidade" class="campo_obrigatorio">*Cidade:</label>
                                            <select name="cidade" class="input_select" id='cidade' title='Selecione um estado para escolher uma cidade' style="display: block;">
                                                <option></option>
                                                <?php
                                                    if (strlen($aux_cidade) > 0 ) {
                                                        echo '<option value"'.$aux_cidade.'" selected>'.$aux_cidade.'</option>';
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="FimDosFloats"></div>
                                        <label for='assunto' class="campo_obrigatorio">*Assunto:</label>
                                        <select name="assunto" class="input_select" id='assunto' style="display: block;">
                                            <option value='0' selected> - Selecione -</option>
                                            <option value='sugestao' <?php if($aux_assunto == 'sugestao') echo " selected ";?>>Sugestão</option>
                                            <option value='reclamacao_at' <?php if($aux_assunto == 'reclamacao_at') echo " selected ";?>>Reclamação da Assistência Técnica</option>
                                            <option value='reclamacao_empresa' <?php if($aux_assunto == 'reclamacao_empresa') echo " selected ";?>>Reclamação da Empresa</option>
                                            <option value='reclamacao_produto' <?php if($aux_assunto == 'reclamacao_produto') echo " selected ";?>>Reclamação de Produto/Defeito</option>
                                        </select>
                                        <p style="display: block; margin:10px 0; color: #999494;">Se a d&uacute;vida for sobre produto, preencha tamb&eacute;m as op&ccedil;&otilde;es abaixo.</p>
                                        <div class="FimDosFloats"></div>
                                        <div class="ColEsq">
                                            <label class="campo_obrigatorio">*Produto:</label>
                                            <input type='hidden' name='produto' id='produto' value="<?php echo $produto?>">
                                            <input name="produto_descricao" class="input_text" id="produto_descricao" value="<?php echo $produto_descricao; ?>" type="text" size="80" maxlength="80" title="Produto" />
                                            <input type='hidden' name='familia_fale' id='familia_fale' value="<?php echo $aux_familia_fale?>">
                                        </div>
                                        <div class="ColDir">
                                            <input type="hidden" name="aux_serie" id="aux_serie" value="">
                                            <label style="display: block;" for="serie_produto_fale" class="serieproduto">Série:</label>
                                            <div style="width: 80%;float: left;">
                                            <input class="input_text " type="text" id="serie_produto_fale" value='<?php echo $serie_produto_fale;?>' name="serie_produto_fale" maxlength="14">
                                            </div>
                                            <div style="width: 14%;float: right;margin-top: -8px;">
                                                <img onclick="javascript: fnc_pesquisa_serie (null,null,'serie',null,document.FormContato.serie_produto_fale,null,'fale')" src="../img/lupa_rota.png" class="btn-lupa-serie" />
                                            </div>
                                        </div>
                                        <div class="FimDosFloats"></div>

                                        <div class="ColEsq">
                                            <label style="display: block;" for="descricao_revenda" class="descricao_revenda">*Nome Revenda:</label>
                                            <div style="width: 100%;float: left;">
                                            <input class="input_text " type="text" id="descricao_revenda_consumidor" value='<?php echo $descricao_revenda;?>' name="descricao_revenda_consumidor" maxlength="14">
                                            <input type="hidden" name="id_revenda_revenda_consumidor" id="id_revenda_revenda_consumidor" value="<?=$id_revenda?>">
                                            </div>
                                        </div>
                                        <div class="ColDir">
                                            <label style="display: block;" for="cnpj_rev_consumidor" class="cnpj_rev">*CNPJ Revenda:</label>
                                            <div style="width: 100%;float: left;">
                                            <input class="input_text " type="text" id="cnpj_rev_consumidor" value='<?php echo $revenda_cnpj;?>' name="cnpj_rev_consumidor" maxlength="14">
                                            </div>
                                        </div>
                                        <div class="FimDosFloats"></div>

                                        <div class="ColEsq">
                                            <label for="nf_produto_fale" class="Nfproduto campo_obrigatorio">*NF:</label>
                                            <input class="input_text" type="text" maxlength="20" id="nf_produto_fale" value='<?php echo $aux_nf_produto_fale;?>' name="nf_produto_fale">
                                        </div>
                                        <div class="ColDir">
                                            <label for="data_compra_produto_fale" class="Datacompraproduto">Data de compra:</label>
                                            <input class="input_text " type="text" id="data_compra_produto_fale" value='<?php echo $aux_data_compra_produto_fale;?>' name="data_compra_produto_fale">
                                        </div>
                                        <div class="FimDosFloats"></div>
                                        <label for="defeito_reclamado_fale" class="Defeitoreclamado">Defeito reclamado:</label>
                                        <select name='defeito_reclamado_fale' class="input_select" id='defeito_reclamado_fale' style="display: block;">
                                            <option value="0">Selecione o produto</option>
                                            <?php
                                                if (strlen($aux_familia_fale) > 0 ) {

                                                    $sql = "SELECT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
                                                                     FROM tbl_diagnostico
                                                                     JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                                                                    WHERE tbl_diagnostico.familia=$aux_familia_fale
                                                                      AND tbl_diagnostico.fabrica={$fabrica}
                                                                      AND tbl_defeito_reclamado.fabrica={$fabrica}
                                                                 ORDER BY tbl_defeito_reclamado.descricao ASC;";
                                                    $res = pg_query($con,$sql);
                                                    $retorno = array();
                                                    if (pg_num_rows($res) > 0) {
                                                        for ($i=0; $i<pg_num_rows($res); $i++ ){
                                                            $defeito_reclamado = pg_fetch_result($res,$i,'defeito_reclamado');
                                                            $descricao         = pg_fetch_result($res,$i,'descricao');
                                                            $selected2  = ($defeito_reclamado == $_POST["defeito_reclamado_fale"]) ? 'selected' : '';
                                                            echo '<option value="'.$defeito_reclamado.'" '.$selected2.'>'.$descricao.'</option>';
                                                        }
                                                    } else {
                                                        echo '<option value="" selected>Nenhum defeito encontrado para esta família.</option>';
                                                    }
                                                }
                                            ?>
                                        </select>
                                        <div class="FimDosFloats"></div>
                                        <label for="msg" class="campo_obrigatorio">*Mensagem</label>
                                        <textarea name="msg" id="msg" class="input_text textarea"><?php echo $aux_msg;?></textarea>
                                        <h5 class="label_anexo">Anexo(s)</h5><br />
                                        <div style="text-align: center;" align="center">
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
                                                    $labelBotao = "Etiqueta de série";
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
                                                    $linkAnexo = "http://api2.telecontrol.com.br/tdocs/document/id/".$anexo_id;
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
                                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                                        </div>
                                        <?php } ?>
                                        </div>
                                        <div style="margin:  0 auto;margin-top: 40px;text-align: center;" align="center">
                                            <button type="submit" name="Enviar" id="Enviar">Enviar</button>
                                        </div>
                                        <div>
                                            <br>
                                            <label class="txt_label campo_obrigatorio"><b>Observação:</b> Os campos com asterisco (*) são de preenchimento obrigatório.</label>
                                        </div>

                                    </form>
                                    <?php for ($i = 1; $i <=  3; $i++) {?>
                                        <form name="form_anexo" method="post" action="fale_conosco.php" enctype="multipart/form-data" style="display: none !important;" >
                                            <input type="file" name="anexo_upload_<?=$i?>" value="" />
                                            <input type="hidden" name="ajax_anexo_upload" value="t" />
                                            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
                                            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
                                        </form>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        
                        </div>
                        <div class="blocks" style="<?php echo ($tipo_contato == "R") ? "display: block;" : "display: none;"; ?>">
                            <div class="t">&nbsp;</div>
                            <div class="holder">
                                <div class="frame">
                                    <div class="tab-content">
                                        <?php
                                            if (strlen($mensagem_sucesso_r) > 0 and strlen($msg_erro) == 0) {
                                                echo "<p class='clear sucesso'> $mensagem_sucesso_r</p>";
                                            }
                                            if (strlen($mensagem_erro_r) > 0 && $msg_erro_existe_protocolo_r) {
                                        ?>

                                                <div class="clear msg_erro errorproto">
                                                    <?php echo $mensagem_erro_r;?>
                                                    <br /><hr/><br />

                                                    <div id="men_retorno_revenda" class="men_retorno"></div>
                                                    <div id="form_protrocolo_revenda" align="left">

                                                        <input type="hidden" name="tipo" id="tipo" value="R" />
                                                        <label class="txt_label">Nº Protocolo: </label>
                                                        <?php
                                                            if(count($hd_chamados) == 1) {
                                                                echo "<input name='protocolo_revenda' style='width:30%' readonly='readonly' class='input_text protocolo_revenda' value='".$hd_chamados[0]["hd_chamado"]."' id='consulta_protocolo'/>";
                                                            } else {
                                                        ?>
                                                        <select class="input_select protocolo_revenda" name="protocolo_revenda" style="width: 50%" id="protocolo_revenda">
                                                            <option value="" >Escolha o protocolo que deseja interagir...</option>
                                                            <?php foreach ($hd_chamados as $key => $value) {?>
                                                            <option value="<?php echo $value["hd_chamado"];?>"><?php echo $value["hd_chamado"];?></option>
                                                            <?php }?>
                                                        </select>
                                                        <?php }?>
                                                         <div class="mensagem_providencia_r" style="display: none"></div>
                                                         <div class="mensagem_historicos_r" style="display: none"></div>

                                                        <label class="txt_label">Mensagem: </label>
                                                        <textarea name="txt_protocolo_revenda" rows="10" id="txt_protocolo_revenda" class="textarea input_text"></textarea><br />
                                                        <button type="button" name="btn_enviar_msn" id="btn_enviar_msn">Enviar</button>
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                            if (strlen($mensagem_erro_r) > 0 && !$msg_erro_existe_protocolo_r) {
                                                echo "<div class='clear msg_erro error'>$mensagem_erro_r</div>";
                                            }

                                        ?>
                                        <form id="FormRevenda" name="FormRevenda" action="" method="post" class=" ">
                                            <input type="hidden" value='R' name="tipo_contato" class="tipo_contato">
                                            <input type="hidden" value='Gravar' name="acao" >

                                            <h3>Dados da Revenda</h3>

                                            <div class="FimDosFloats"></div>
                                            <div class="ColEsq2">
                                                <label for="nome_revenda" class="Nome campo_obrigatorio">*Nome:</label>
                                                <input type="text" maxlength="50" name="nome_revenda" id="nome_revenda" value='<?php echo $aux_nome_revenda;?>' class="input_text">
                                            </div>
                                            <div class="ColDir2">
                                                <label for="data_nasc_revenda" class="Datacompraproduto">Data de Nascimento:</label>
                                                <input class="input_text " type="text" id="data_nasc_revenda" value='<?php echo $aux_data_nasc_revenda;?>' name="data_nasc_revenda">
                                            </div>
                                            <div class="ColEsq">
                                                <input type='radio' name='cpf_cnpj_revenda' id='consumidor_cfp' value='C'
                                                <?PHP

                                                    if ($aux_cpf_cnpj_revenda == "C") {
                                                        echo "CHECKED";
                                                    }
                                                ?>
                                                onclick="fnc_tipo_atendimento(this)">

                                                <label for="cpf">CPF</label>
                                                <input type='radio' name='cpf_cnpj_revenda' id='consumidor_cnpj' value='R'
                                                    <?PHP
                                                        if ($aux_cpf_cnpj_revenda == "R") {
                                                            echo "CHECKED";
                                                        }
                                                    ?>
                                                    onclick="fnc_tipo_atendimento(this)">

                                                <label for="consumidor_cfp">CNPJ</label>
                                                <input type="text" name="cpf_revenda" id="cpf" value='<?php echo $aux_cpf_revenda;?>'  class="input_text cpf">
                                            </div>

                                            <div class="ColDir">
                                                <label for="email">E-mail:</label>
                                                <input type="email" name="email_revenda" id="email" value='<?php echo $aux_email_revenda;?>'  class="input_text">
                                            </div>

                                            <div class="FimDosFloats"></div>

                                            <div class="ColEsq">
                                                <label for="telefone" class="campo_obrigatorio">*Telefone:</label>
                                                <input type="text" name="telefone_revenda" id="telefone_revenda" value='<?php echo $aux_telefone_revenda;?>' class="input_text telefone">
                                            </div>
                                            <div class="ColDir">
                                                <label for="cep" class="campo_obrigatorio">*CEP:</label>
                                                <input type="text" name="cep_revenda" id="cep_revenda" onblur="buscaCEP(this.value,'R' )" value='<?php echo $aux_cep_revenda;?>' class="input_text cep">
                                            </div>
                                            <div class="FimDosFloats"></div>

                                            <div class="ColEsq">
                                                <label for="endereco" class="campo_obrigatorio">*Endere&ccedil;o:</label>
                                                <input type="text" maxlength="70" name="endereco_revenda" id="endereco_revenda" value='<?php echo $aux_endereco_revenda;?>' class="input_text">
                                            </div>
                                            <div class="ColDir">
                                                <label for="numero" class="campo_obrigatorio">*N&uacute;mero:</label>
                                                <input type="text" maxlength="20" name="numero_revenda" id="numero" value='<?php echo $aux_numero_revenda;?>' class="input_text">
                                            </div>
                                            <div class="FimDosFloats"></div>
                                            <div class="ColEsq">
                                                <label for="complemento">Complemento:</label>
                                                <input type="text" maxlength="40" name="complemento_revenda" id="complemento" value='<?php echo $aux_complemento_revenda;?>' class="input_text">
                                            </div>
                                            <div class="ColDir">
                                                <label for="bairro" class="campo_obrigatorio">*Bairro:</label>
                                                <input type="text" maxlength="60" name="bairro_revenda" id="bairro_revenda" value='<?php echo $aux_bairro_revenda;?>' class="input_text">
                                            </div>
                                            <div class="FimDosFloats"></div>
                                            <div class="ColEsq">
                                                <label for="cidade" class="campo_obrigatorio">*Cidade:</label>
                                                <select name="cidade_revenda" class="input_select" id='cidade_revenda' title='Selecione um estado para escolher uma cidade' style="display: block;width: 100%">
                                                    <option></option>
                                                    <?php
                                                        if (strlen($aux_cidade_revenda) > 0 ) {
                                                            echo '<option value"'.$aux_cidade_revenda.'" selected>'.$aux_cidade_revenda.'</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="ColDir">
                                                <label for='estado' class="campo_obrigatorio">*Estado:</label>
                                                <select name='estado_revenda' id='estado_revenda' class="input_select" style="display: block;">
                                                    <option></option>
                                                    <?php
                                                        foreach ($array_estado as $k => $v) {
                                                            echo '<option value="'.$k.'"'.($aux_estado_revenda == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="FimDosFloats"></div>
                                            <label for='assunto' class="campo_obrigatorio">*Assunto:</label>
                                            <select name="assunto_revenda" class="input_select" id='assunto' style="display: block;">
                                                <option value='0' selected> - Selecione -</option>
                                                <option value='sugestao' <?php if($aux_assunto_revenda == 'sugestao') echo " selected ";?>>Sugestão</option>
                                                <option value='reclamacao_at' <?php if($aux_assunto_revenda == 'reclamacao_at') echo " selected ";?>>Reclamação da Assistência Técnica</option>
                                                <option value='reclamacao_empresa' <?php if($aux_assunto_revenda == 'reclamacao_empresa') echo " selected ";?>>Reclamação da Empresa</option>
                                                <option value='reclamacao_produto' <?php if($aux_assunto_revenda == 'reclamacao_produto') echo " selected ";?>>Reclamação de Produto/Defeito</option>
                                            </select>
                                            <div class="FimDosFloats"></div>
                                            <div class="ColEsq">
                                                <label class="campo_obrigatorio">*Produto:</label>
                                                <input type='hidden' name='produto_revenda' id='produto_revenda' value="<?php echo $aux_produto?>">
                                                <input type='hidden' name='familia_revenda' id='familia_revenda' value="<?php echo $aux_familia_revenda?>">
                                                <input name="produto_descricao_revenda" class="input_text" id="produto_descricao_revenda" value="<?php echo $produto_descricao_revenda; ?>" type="text" size="80" maxlength="80" title="Produto" placeholder='Digite o produto aqui para fazer a pesquisa' />
                                            </div>
                                            <div class="ColDir">
                                                <label style="display: block;" for="serie_produto_revenda" class="serieproduto">Série:</label>
                                                <div style="width: 80%;float: left;">
                                                <input class="input_text " type="text" id="serie_produto_revenda" value='<?php echo $serie_produto_revenda;?>' name="serie_produto_revenda" maxlength="14">
                                                </div>
                                                <div style="width: 14%;float: right;margin-top: -8px;">
                                                    <img onclick="javascript: fnc_pesquisa_serie (null,null,'serie',null,document.FormRevenda.serie_produto_revenda,null,'revenda')" src="../img/lupa_rota.png" class="btn-lupa-serie" />
                                                </div>
                                            </div>
                                            <div class="FimDosFloats"></div>
                                            
                                            <div class="ColEsq">
                                                <label style="display: block;" for="descricao_revenda" class="descricao_revenda">*Nome Revenda:</label>
                                                <div style="width: 100%;float: left;">
                                                <input class="input_text " type="text" id="descricao_revenda" value='<?php echo $descricao_revenda;?>' name="descricao_revenda" maxlength="14">
                                                <input type="hidden" name="id_revenda_revenda" id="id_revenda_revenda" value="<?=$id_revenda_revenda?>">
                                                </div>
                                            </div>
                                            <div class="ColDir">
                                                <label style="display: block;" for="cnpj_rev" class="cnpj_rev">*CNPJ Revenda:</label>
                                                <div style="width: 100%;float: left;">
                                                <input class="input_text " type="text" id="cnpj_rev" value='<?php echo $cnpj_rev;?>' name="cnpj_rev" maxlength="14">
                                                </div>
                                            </div>
                                            <div class="FimDosFloats"></div>
                                            <div class="ColEsq">
                                                <label for="nf_produto_revenda" class="Nfproduto campo_obrigatorio">*NF:</label>
                                                <input class="input_text" type="text" maxlength="20" id="nf_produto_revenda" value='<?php echo $aux_nf_produto_revenda;?>' name="nf_produto_revenda">
                                            </div>
                                            <div class="ColDir">
                                                <label for="data_compra_produto_revenda" class="Datacompraproduto">Data de compra:</label>
                                                <input class="input_text " type="text" id="data_compra_produto_revenda" value='<?php echo $aux_data_compra_produto_revenda;?>' name="data_compra_produto_revenda">
                                            </div>
                                            <div class="FimDosFloats"></div>
                                            <label for="defeito_reclamado_revenda" class="Defeitoreclamado">Defeito reclamado:</label>
                                            <select name='defeito_reclamado_revenda' class="input_select" id='defeito_reclamado_revenda' style="display: block;">
                                                <option value="0">Selecione o produto</option>
                                                <?php
                                                    if (strlen($aux_familia_revenda) > 0 ) {

                                                        $sql = "SELECT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
                                                                         FROM tbl_diagnostico
                                                                         JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                                                                        WHERE tbl_diagnostico.familia=$aux_familia_revenda
                                                                          AND tbl_diagnostico.fabrica={$fabrica}
                                                                          AND tbl_defeito_reclamado.fabrica={$fabrica}
                                                                     ORDER BY tbl_defeito_reclamado.descricao ASC;";
                                                        $res = pg_query($con,$sql);
                                                        $retorno = array();
                                                        if (pg_num_rows($res) > 0) {
                                                            for ($i=0; $i<pg_num_rows($res); $i++ ){
                                                                $defeito_reclamado = pg_fetch_result($res,$i,'defeito_reclamado');
                                                                $descricao         = pg_fetch_result($res,$i,'descricao');
                                                                $selected2  = ($defeito_reclamado == $_POST["defeito_reclamado_revenda"]) ? 'selected' : '';
                                                                echo '<option value="'.$defeito_reclamado.'" '.$selected2.'>'.$descricao.'</option>';
                                                            }
                                                        } else {
                                                            echo '<option value="" selected>Nenhum defeito encontrado para esta família.</option>';
                                                        }
                                                    }  else {
                                                    }
                                                ?>
                                            </select>
                                            <div class="FimDosFloats"></div>
                                            <label for="msg" class="campo_obrigatorio">*Mensagem</label>
                                            <textarea name="msg_revenda" id="msg" class="input_text textarea"><?php echo $aux_msg_revenda;?></textarea>
                                            <h5 class="label_anexo">Anexo(s)</h5><br />
                                            <div style="text-align: center;" align="center">
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
                                                    $labelBotao = "Etiqueta de série";
                                                }
                                                if ($i == 3) {
                                                    $labelBotao = "Foto do produto";
                                                }

                                                $imagemAnexo = "../../admin/imagens/imagem_upload.png";
                                                $linkAnexo   = "#";
                                                $anexo = $anexo_r[$i];
                                                $anexo_c_array = json_decode($anexo_r[$i], true); 
                                                $anexo_id = $anexo_c_array['tdocs_id'];
                                                if (!empty($anexo_id)) {
                                                    $linkAnexo = "http://api2.telecontrol.com.br/tdocs/document/id/".$anexo_id;
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
                                            <div id="div_anexo_<?=$i?>_r" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                                                <?php if ($linkAnexo != "#") { ?>
                                                <a href="<?=$linkAnexo?>" target="_blank" >
                                                <?php } ?>
                                                    <img src="<?=$imagemAnexo?>" class="anexo_thumb_r" style="width: 100px; height: 90px;margin-bottom: 10px;" />
                                                <?php if ($linkAnexo != "#") { ?>
                                                </a>
                                                <script>setupZoom();</script>
                                                <?php } ?>
                                                <button type="button" class="btn-anexar" name="anexar_r" rel="<?=$i?>" ><?=$labelBotao?></button>
                                                <img src="../../admin/imagens/loading_img.gif" class="anexo_loading_r" style="width: 64px; height: 64px;margin-bottom: 10px; display: none;" />
                                                <input type="hidden" rel="anexo_r" name="anexo_r[<?=$i?>]" value="<?=$anexo_r?>" />
                                            </div>
                                            <?php } ?>
                                            </div>
                                            <div style="margin:  0 auto;margin-top: 40px;text-align: center;" align="center">
                                                <button type="submit" name="Enviar" id="Enviar">Enviar</button>
                                            </div>
                                            <div>
                                                <br>
                                                <label class="txt_label campo_obrigatorio"><b>Observação:</b> Os campos com asterisco (*) são de preenchimento obrigatório.</label>
                                            </div>

                                        </form>
                                        <?php for ($i = 1; $i <=  3; $i++) {?>
                                            <form name="form_anexo_r" method="post" action="fale_conosco.php" enctype="multipart/form-data" style="display: none !important;" >
                                                <input type="file" name="anexo_r_upload_<?=$i?>" value="" />
                                                <input type="hidden" name="ajax_anexo_upload_r" value="t" />
                                                <input type="hidden" name="anexo_posicao_r" value="<?=$i?>" />
                                                <input type="hidden" name="anexo_chave_r" value="<?=$anexo_chave?>" />
                                            </form>
                                        <?php }?>
                                    </div>
                                </div>
                           </div>
                        </div>
                        

                        <div class="blocks" style="<?php echo ($tipo_contato == "P") ? "display: block;" : "display: none;"; ?>">
                            <div class="t">&nbsp;</div>
                            <div class="holder">
                                <div class="frame">
                                    <form id="FormRevenda" name="FormConsulta" action="" method="post" class=" ">
                                        <div id="men_erro_consulta" style="display: none;" class="error"></div>
                                        <input type="hidden" value='P' name="tipo_contato" class="tipo_contato">
                                        <p align="center" style="color: #999494; margin-bottom: 35px;">Digite seu CPF/CNPJ ou Número do Protocolo.</p>
                                        <div class="FimDosFloats"></div>
                                        <div class="ColEsq">
                                            <input type='radio' name='cpf_cnpj_consulta' id='consumidor_cfp' value='C' onclick="fnc_tipo_atendimento(this)">
                                            <label for="cpf">CPF</label>
                                            <input type='radio' name='cpf_cnpj_consulta' id='consumidor_cnpj' value='R' onclick="fnc_tipo_atendimento(this)">
                                            <label for="consumidor_cfp">CNPJ</label>
                                            <input type="text" name="consulta_cpf" id="cpf" class="input_text cpf">
                                        </div>
                                        <div class="ColDir" style="margin-top: 4px;">
                                            <label for="telefone">Nº Protocolo:</label>
                                            <input type="text" maxlength="7" name="consulta_numero_protocolo" id="consulta_numero_protocolo" class="input_text">
                                        </div>
                                        <div class="FimDosFloats"></div>
                                        <div align="center">
                                            <button type="button" class="btn_consulta_protocolo" name="Consultar" id="Consultar">Consultar</button>
                                        </div>
                                    </form><br />
                                    <div class="clear dados_consulta_protocolo errorproto" style="display: none;">
                                        <p id="txt_msn_protocolo"></p>
                                        <br /><br />
                                        <div id="men_retorno_consulta" style="padding: 10px;" class="men_retorno_consulta"></div>
                                        <hr>
                                        <div id="form_protrocolo_consulta" align="left">
                                            <input type="hidden" name="tipo" id="tipo" value="P" />
                                            <label class="txt_label">Nº Protocolo: </label>
                                            <div class="campo_protocolo">
                                                <select class="input_select" name="consulta_protocolo" style="width: 50%" id="consulta_protocolo">
                                                    <option value="" >Escolha o protocolo que deseja interagir...</option>
                                                </select>
                                            </div>
                                            <div class="mensagem_providencia" style="display: none"></div>
                                            <div class="mensagem_historicos" style="display: none"></div>
                                            <div id="msn_txt_protocolo">
                                                <label class="txt_label">Mensagem: </label>
                                                <textarea name="consulta_txt_protocolo" rows="10" id="consulta_txt_protocolo" class="textarea input_text"></textarea><br />
                                                <input type='hidden' name='tipo_consumidor_revenda' id="tipo_consumidor_revenda" value=''>
                                                <button type="button" name="btn_enviar_msn" id="btn_enviar_msn">Enviar</button>
													<div id='msg_status'></div>
                                            </div>
                                            <div id="msn_txt_protocolo2" style="display:none">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

        <script language="JavaScript">
            function selecionaFamilia(familia, aba) {
                if (aba == "fale") {
                    $(document).on("click", "#familia_produto", function(){
                        $(this).val(familia);
                    });
                    $("#familia_produto").trigger("click");
                } else {
                    $(document).on("click", "#familia_revenda", function(){
                        $(this).val(familia);
                    });
                    $("#familia_revenda").trigger("click");
                }
            }

            function selecionaProduto(produto) {
                $(document).on("change", "#produto_revenda", function(){
                    $(this).val(produto);
                });
                $("#produto_revenda").trigger("change");
            }

            function fnc_pesquisa_serie(campo, campo2, tipo, mapa_linha,campo3, pos, aba) {

                if (tipo == "serie") {
                    var xcampo = campo3;
                }

                
                if (aba != "") {
                    $("#aux_serie").val(aba);
                } else {
                    $("#aux_serie").val("");
                }

                if (xcampo.value != "") {
                    Shadowbox.open({
                        content :   "../../admin/produto_serie_pesquisa_new_nv.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t" + "&pos=" + pos+"&fale_conosco_esmaltec=true",
                        player  :   "iframe",
                        title   :   "Pesquisa",
                        width   :   800,
                        height  :   500
                    });
                }else{
                    alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
                    return false;
                }
            }


            function mostraDefeitos(natureza, produto, aba, defeito, tipo_serie = false) {

                if (produto != '') {
                    $.ajax({
                        type: "POST",
                        url:  "fale_conosco.php?lupa=true",
                        data: {produto : produto, buscaDefeitoRaclamado : 'buscaDefeitoRaclamado'},
                        dataType : "json",
                        cache: false,
                        success: function(resposta){
                            var dados = resposta.defeitos;
                            var options = "";
                            if (resposta.erro) {
                                options = "<option value=''>"+resposta.msn+"</option>";
                            } else {
                                options += "<option value='' selected>Selecione um defeito reclamado</option>";
                                for (var i = 0; i < $(dados).length; i++ ) {
                                    options += "<option value='"+dados[i].defeito_reclamado+"'>"+dados[i].descricao+"</option>";
                                }
                            }
                            if (aba == "fale") {
                                $("#defeito_reclamado_fale").html(options);
                            } else {
                                $("#defeito_reclamado_revenda").html(options);
                            }
                            selecionaFamilia(resposta.familia, aba);
                            if (tipo_serie == true) {
                                buscaProduto(resposta.familia, 'R');
                                setTimeout(function(){
                                    selecionaProduto(resposta.produto);
                                }, 1000);
                            }

                        }
                    });

                }
            }

            function retorna_produto(descricao, referencia, voltagem, marca_produto, produto, linha, pos, informatica,serie_obrigatorio, linha_descricao) {
                $("#referencia_produto_revenda").val(referencia);
                $("#produto_revenda").val(produto);
                $("#produto_revenda_lupa").val(produto);
                $("#descricao_produto_revenda").val(descricao);

            }

            function retorna_serie(descricao, referencia, serie, voltagem, produto, ordem, linha, pos ) {
                var aba = $("#aux_serie").val();

                if (aba == "fale") {
                    $("#produto").val(produto);
                    $("#produto_descricao").val(descricao);
                } else {
                    $("#produto_revenda").val(produto);
                    $("#referencia_produto_revenda").val(referencia);
                    $("#produto_revenda_lupa").val(produto);
                    $("#produto_descricao_revenda").val(descricao);
                }
                
                mostraDefeitos('Reclamado', referencia, aba);
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
                        alert ("Selecione a família do produto");
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
                //$("#telefone").mask("(99) 99999-9999");
                //$("#telefone_revenda").mask("(99) 99999-9999");

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

                $("#data_compra_produto_revenda").mask("99/99/9999");
                $("#data_compra_produto_fale").mask("99/99/9999");
                $("#data_nasc_revenda").mask("99/99/9999");
                $("#cep").mask("99999-999");
                $("#cep_revenda").mask("99999-999");
                $('#data_nasc_revenda').datepick({startDate:'01/01/1900'});
                $('#data_compra_produto_revenda').datepick({startDate:'01/01/2000'});
                $('#data_compra_produto_fale').datepick({startDate:'01/01/2000'});
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

                $(document).on("click", "#abrir_novo", function(){
                    var hd_chamado = $("#consulta_numero_protocolo").val();
                    $.ajax({
                        type: "POST",
                        url:  "fale_conosco.php",
                        data: "abrir_novo=true&hd_chamado="+hd_chamado,
                        success: function(resposta){
                            data = JSON.parse(resposta);
                            if(data.consumidor_revenda == "C"){                            
                                $("#nome").val(data.nome);
                                $("#cpf").val(data.cpf);
                                $("#email").val(data.email);    
                                $("#telefone").val(data.fone);
                                $("#cep").val(data.cep);
                                $("#endereco").val(data.endereco);
                                $("#numero").val(data.numero);
                                $("#bairro").val(data.bairro);
                                $("#complemento").val(data.complemento);
                            }
                            if(data.consumidor_revenda == "R"){
                                $("#nome_revenda").val(data.nome);
                                $("input[name='cpf_revenda']").val(data.cpf);
                                $("input[name='email_revenda']").val(data.email);    
                                $("#telefone_revenda").val(data.fone);
                                $("#cep_revenda").val(data.cep);
                                $("#endereco_revenda").val(data.endereco);
                                $("input[name='numero_revenda']").val(data.numero);
                                $("#bairro_revenda").val(data.bairro);
                                $("#complemento").val(data.complemento);
                                $("#data_nasc_revenda").val(data.data_nascimento);
                            }
                            opentab((data.consumidor_revenda == "C") ? "1" : "2");   
                            buscaCEP(data.cep, data.consumidor_revenda);
                        }
                    });                    
                });

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

                <?php if (strlen($mensagem_erro) > 0 && $msg_erro_existe_protocolo) {?>
                    <?php if(count($hd_chamados) == 1) {?>
                        carrega_providencia("<?php echo $hd_chamados[0]["hd_chamado"];?>","C");
                    <?php }?>
                <?php }?>

                <?php if (strlen($mensagem_erro_r) > 0 && $msg_erro_existe_protocolo_r) {?>
                    <?php if(count($hd_chamados) == 1) {?>
                        carrega_providencia("<?php echo $hd_chamados[0]["hd_chamado"];?>","R");
                    <?php }?>
                <?php }?>


                $("#produto_revenda").change(function () {
                    var voltagem = $("#produto_revenda option:selected").data("voltagem");
                    $("#voltagem_produto_revenda").val(voltagem);
                });

                $(".btn_consulta_protocolo").click(function(){
                    var cpf       = $("input[name=consulta_cpf]").val();
                    var protocolo = $("input[name=consulta_numero_protocolo]").val();
                    var tipo = $("input[name=cpf_cnpj_consulta]").val();


                    if (cpf == "" && protocolo == "") {
                        alert("Digite seu CPF/CNPJ ou Número do Protocolo.");
                        $("input[name=consulta_cpf]").focus();
                        return false;
                    }
                    $.ajax({
                        type: "POST",
                        url:  "fale_conosco.php",
                        data: "consulta_protocolo=true&protocolo="+protocolo+"&cpf="+cpf+"&tipo="+tipo,
                        success: function(resposta){
                            data = JSON.parse(resposta);
                            var options = "";
                            if (data.erro) {
                                $("#men_erro_consulta").show();
                                $("#men_erro_consulta").html(data.msn);
                            } else {
                                $("#men_erro_consulta").hide();
                                $("#men_erro_consulta").html("");

                                if (data.protocolos.length == 1) {
                                    $("select[name=consulta_protocolo]").remove();
                                    $(".campo_protocolo").html("<input name='consulta_protocolo' style='width:30%' readonly='readonly' class='input_text' value='"+data.protocolos[0].hd_chamado+"' id='consulta_protocolo'/>");
                                    
                                    carrega_providencia(data.protocolos[0].hd_chamado, 'P', data.protocolos);

                                } else {
                                    options += "<option value='' selected>Escolha o protocolo que deseja interagir...</option>";
                                    for (var i = 0; i < data.protocolos.length; i++ ) {

                                        options += "<option value='"+data.protocolos[i].hd_chamado+"'>"+data.protocolos[i].hd_chamado+"</option>";
                                    }
                                    $("select[name=consulta_protocolo]").html(options);
                                }
                                $("#msn_txt_protocolo").show();
                                $("#msn_txt_protocolo2").hide();
                                $(".sucesso").html('');
                                $(".sucesso").hide('');
                                $("#tipo_consumidor_revenda").val(data.consumidor_revenda);
                                if(data.status == 'Resolvido'){
                                    $("#msn_txt_protocolo").hide();
                                    $("#msn_txt_protocolo2").show();
                                    $("#msn_txt_protocolo2").html("<span style='cursor:pointer; color:red; font-weight:bold' id='abrir_novo' data-hdchamado='"+data.protocolos[0].hd_chamado+"'> O Protocolo está finalizado. Clique aqui para abrir uma nova solicitação. </span>");
                                    $("#msn_txt_protocolo2").css('text-align', 'center');
                                }
                            }
                            $("#txt_msn_protocolo").html(data.msn);
                            $(".dados_consulta_protocolo").show();
                        }
                    });
                });

                $("select[name=consulta_protocolo]").change(function(){
                    var hd_chamado = $(this).val();
                    carrega_providencia(hd_chamado,'P');
                });

                $(".protocolo").change(function(){
                    var hd_chamado = $(this).val();
                    carrega_providencia(hd_chamado,'C');
                });

                $(".protocolo_revenda").change(function(){
                    var hd_chamado = $(this).val();
                    carrega_providencia(hd_chamado,'R');
                });

                $("#Enviar").click(function(){
                    $(".sucesso").html('');
                });

                $("#btn_enviar_msn").click(function(){
                    
                    var tipo = $("#tipo").val();
                    if (tipo == "C") {
                        var protocolo     = $("#consulta_protocolo");
                        var txt_protocolo = $("#txt_protocolo");
                    }
                    if (tipo == "R") {
                        var protocolo     = $("#protocolo_revenda");
                        var txt_protocolo = $("#txt_protocolo_revenda");
                    }

                    if (tipo == "P") {
                        var protocolo     = $("#consulta_protocolo");
                        var txt_protocolo = $("#consulta_txt_protocolo");
                    }

                    var tipo_consumidor_revenda = $("#tipo_consumidor_revenda").val();

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
                                    'protocolo': protocolo.val(),
                                    'tipo_consumidor_revenda' :tipo_consumidor_revenda
                                    };
                        $.ajax({
                            type: "POST",
                            url:  "fale_conosco.php",
                            data:  dados,
                            dataType : "json",
                            cache: false,
                            complete: function(resposta){

                                data = $.parseJSON(resposta.responseText);

                                if (tipo == "C") {
                                    if (data.erro == true) {
                                        $("#men_retorno").html(data.msn);
                                        $("#men_retorno").addClass('txterror');
                                    } else {
                                        $("#men_retorno").html(data.msn);
                                        $("#men_retorno").addClass('txtsucesso');
                                        $("#form_protrocolo_revenda").hide();
                                        setTimeout(function(){
                                            window.location.href = "fale_conosco.php";
                                        }, 5000);
                                    }
                                }

                                if (tipo == "R") {
                                    if (data.erro == true) {
                                        $("#men_retorno_revenda").html(data.msn);
                                        $("#men_retorno_revenda").addClass('txterror');
                                    } else {
                                        $("#men_retorno_revenda").html(data.msn);
                                        $("#men_retorno_revenda").addClass('txtsucesso');
                                        $("#form_protrocolo_revenda").hide();
                                        setTimeout(function(){
                                            window.location.href = "fale_conosco.php";
                                        }, 5000);
                                    }
                                }

                                if (tipo == "P") {
                                    if (data.erro == true) {
                                        $("#men_retorno_consulta").html(data.msn);
                                        $("#men_retorno_consulta").addClass('txterror');
                                    } else {
                                        $("#men_retorno_consulta").html(data.msn);
                                        $("#men_retorno_consulta").addClass('txtsucesso');
                                        $("#form_protrocolo_consulta").hide();
                                        setTimeout(function(){
                                            window.location.href = "fale_conosco.php";
                                        }, 5000);
                                    }
                                }
                            }
                        });
                    }
                });

                $("#familia_revenda").change(function () {
                    var familia_revenda = $("#familia_revenda option:selected").val();
                    if (familia_revenda != 0){
                        $.ajax({
                            type: "POST",
                            url:  "fale_conosco.php",
                            data: "familia_revenda="+familia_revenda+"&buscaDefeitoRaclamado=buscaDefeitoRaclamado",
                            success: function(resposta){
                                data = JSON.parse(resposta);
                                dados = data.defeitos;
                                var options = "";
                                if (resposta.erro) {
                                    options = "<option value=''>"+data.msn+"</option>";
                                } else {
                                    options += "<option value='' selected>Selecione um defeito reclamado</option>";
                                    for (var i = 0; i < $(dados).length; i++ ) {
                                        options += "<option value='"+dados[i].defeito_reclamado+"'>"+dados[i].descricao+"</option>";
                                    }
                                }
                                $("#defeito_reclamado_revenda").html(options);
                            }
                        });
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
                            $(imagem).attr({ src: data.link });

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
                            $(imagem).attr({ src: data.link });

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

                //descricao revenda
                $("#descricao_revenda").autocomplete("fale_conosco.php?tipo_busca=revenda",
                {
                    minChars      : 3,
                    delay         : 150,
                    width         : 350,
                    matchContains : true,
                    formatItem    : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1]; },
                    formatResult  : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1];}
                });

                $("#descricao_revenda").result(function(event, data, formatted)
                {
                    $("#id_revenda_revenda").val(data[0]);
                    $("#descricao_revenda").val(data[2]);
                    $("#cnpj_rev").val(data[1]);
                }); 

                //descricao revenda consumidor
                $("#descricao_revenda_consumidor").autocomplete("fale_conosco.php?tipo_busca=revenda",
                {
                    minChars      : 3,
                    delay         : 150,
                    width         : 350,
                    matchContains : true,
                    formatItem    : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1];  },
                    formatResult  : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1]; }
                });

                $("#descricao_revenda_consumidor").result(function(event, data, formatted)
                {
                    $("#id_revenda_revenda_consumidor").val(data[0]);
                    $("#descricao_revenda_consumidor").val(data[2]);
                    $("#cnpj_rev_consumidor").val(data[1]);
                }); 



                //cnpj_revenda consumidor
                $("#cnpj_rev_consumidor").autocomplete("fale_conosco.php?tipo_busca=revenda&t=cnpj",
                {
                    minChars      : 3,
                    delay         : 150,
                    width         : 350,
                    matchContains : true,
                    formatItem    : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1];   },
                    formatResult  : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1];  }
                });

                $("#cnpj_rev_consumidor").result(function(event, data, formatted)
                {
                    $("#id_revenda_revenda_consumidor").val(data[0]);
                    $("#descricao_revenda_consumidor").val(data[2]);
                    $("#cnpj_rev_consumidor").val(data[1]);
                }); 

                //cnpj revenda
                $("#cnpj_rev").autocomplete("fale_conosco.php?tipo_busca=revenda&t=cnpj",
                {
                    minChars      : 3,
                    delay         : 150,
                    width         : 350,
                    matchContains : true,
                    formatItem    : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1];   },
                    formatResult  : function(row){ if (row.length > 2) {
                                                    return row[1] + " - " + row[2];
                                                  }
                                                  return row[1];  }
                });

                $("#cnpj_rev").result(function(event, data, formatted)
                {
                    $("#id_revenda_revenda").val(data[0]);
                    $("#descricao_revenda").val(data[2]);
                    $("#cnpj_rev").val(data[1]);
                }); 



                /* # HD 941072 - Busca produto pela descrição */                
                $("#produto_descricao").autocomplete("fale_conosco.php?tipo_busca=produto",
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
                    defeitoReclamado(data[2], "fale");
                });    

                /* # HD 941072 - Busca produto pela descrição */                
                $("#produto_descricao_revenda").autocomplete("fale_conosco.php?tipo_busca=produto",
                {
                    minChars      : 3,
                    delay         : 150,
                    width         : 350,
                    matchContains : true,
                    formatItem    : function(row){ return row[1]  },
                    formatResult  : function(row){ return row[1]; }
                });

                $("#produto_descricao_revenda").result(function(event, data, formatted)
                {
                    $("#produto_revenda").val(data[0]);
                    $("#produto_descricao_revenda").val(data[1]);
                    $("#familia_revenda").val(data[2]);
                    defeitoReclamado(data[2], "revenda");
                    
                });
            });

            function defeitoReclamado(familia, aba){

                $.ajax({
                    type: "GET",
                    url:  "fale_conosco.php?produto_familia="+familia,
                    cache: false,
                    complete: function(resposta){
                        if (aba == "fale") {
                            $("#defeito_reclamado_fale").html(resposta.responseText);
                        } else {
                            $("#defeito_reclamado_revenda").html(resposta.responseText);
                        }
                    }
                });

            }

            function carrega_providencia(hd_chamado, tipo, dados_hd = "") {
                if (hd_chamado == '' || tipo == '') {
                    alert("Protocolo não encontrado.");
                    return false;
                }

                $.ajax({
                    async: false,
                    url: "fale_conosco.php",
                    type: "POST",
                    data: { carregaProvidencia: true, hd_chamado: hd_chamado },
                    cache: false,
                    complete: function (data) {
                        data = $.parseJSON(data.responseText);
                        if (data.erro) {
                            return false;
                        } else {
                            var dados = "\
                                        <b style='font-size:17px'>Status do Protocolo:</b> <br><br>\
                                        <b>"+data.descricao+"</b><br><br>\
                                    ";

                                    /*MONTA TABELA DE HISTORICO DE ATENDMENTO*/
                                    var tabela_atendimento = "";
                                    var tabela_anexos = "";

                                    if (dados_hd != "") {

                                        if (dados_hd[0].historico_atendimento != undefined) {
                                            var total_atendimento = dados_hd[0].historico_atendimento.length;
                                            tabela_atendimento = "<p>Status e histórico de acompanhamento: </p>\
                                                            <table border='1' cellpadding='2' cellspacing='0' style='border-color:#cccccc;width:100%;'>\
                                                                <thead style='font-size:11px;'>\
                                                                    <tr bgcolor='#cccccc'>\
                                                                        <th colspan='5'>Histórico de Atendimentos</th>\
                                                                    </tr>\
                                                                    <tr bgcolor='#dddddd'>\
                                                                        <th>Data / Hora</th>\
                                                                        <th>Responsável</th>\
                                                                        <th>Interação</th>\
                                                                        <th>Próximo contato</th>\
                                                                        <th>Status</th>\
                                                                    </tr>\
                                                                </thead><tbody>";
                                            for (i = 0; i < total_atendimento; i++) {
                                                cor = (i % 2 == 0) ? '#ffffff' : '#eeeeee';
                                                tabela_atendimento += "<tr bgcolor='"+cor+"' style='font-size:11px;'>\
                                                                    <td>"+dados_hd[0].historico_atendimento[i].data+"</td>\
                                                                    <td>"+dados_hd[0].historico_atendimento[i].admin+"</td>\
                                                                    <td>"+dados_hd[0].historico_atendimento[i].comentario+"</td>\
                                                                    <td>"+dados_hd[0].historico_atendimento[i].data_providencia+"</td>\
                                                                    <td>"+dados_hd[0].historico_atendimento[i].status_item+"</td>\
                                                                  </tr>\
                                                                </thead>";
                                                cor++;
                                            }
                                            tabela_atendimento += "</table><br />";
                                        }
                                        /*MONTA TABELA DE ANEXOS*/
                                        var tabela_anexos = "";
                                        if (dados_hd[0].historico_anexo != undefined) {
                                            var total_anexos = dados_hd[0].historico_anexo.length;
                                            var nome_anexo = "";
                                            tabela_anexos = "<table border='1' cellpadding='2' cellspacing='0' style='border-color:#cccccc;width:100%;'>\
                                                                <thead bgcolor='#cccccc'>\
                                                                    <tr>\
                                                                        <th colspan='2'>Histórico de Anexos</th>\
                                                                    </tr>\
                                                                </thead><tbody>";

                                            for (i = 0; i < total_anexos; i++) {
                                                if (i == 0) {
                                                    nome_anexo = "NF";
                                                }else if (i == 1) {
                                                    nome_anexo = "Etiqueta de Série";
                                                }else if (i == 2) {
                                                    nome_anexo = "Foto do Produto";
                                                }
                                                tabela_anexos += "<tr bgcolor='#ffffff'>\
                                                                    <td>"+nome_anexo+"</td>\
                                                                    <td width='20%'><a href='"+dados_hd[0].historico_anexo[i]+"' target='_blank'><b>Visualizar</b></a></td>\
                                                                  </tr>\
                                                                </thead>";
                                            }

                                            tabela_anexos += "</table><br /><br /><hr />";
                                        }

                                    }

                            if (tipo == 'C') {
                                $(".mensagem_providencia_c").show();
                                $(".mensagem_providencia_c").html(dados);
                                $(".mensagem_historicos_c").show();
                                $(".mensagem_historicos_c").html(tabela_atendimento+tabela_anexos);
                            }
                            if (tipo == 'R') {
                                $(".mensagem_providencia_r").show();
                                $(".mensagem_providencia_r").html(dados);
                                $(".mensagem_historicos_r").show();
                                $(".mensagem_historicos_r").html(tabela_atendimento+tabela_anexos);
                            }

                            if (tipo == 'P') {
                                $(".mensagem_providencia").show();
                                $(".mensagem_providencia").html(dados);
                                $(".mensagem_historicos").show();
                                $(".mensagem_historicos").html(tabela_atendimento+tabela_anexos);
                            }
							
							if(data.status == 'Resolvido') {
								$('#btn_enviar_msn').hide();
								$('#msg_status').html('Protocolo Finalizado, não é possível interagir');
							}else{
								$('#btn_enviar_msn').show();
								$('#msg_status').html(' ');
							}
                        }
                    }
                });



            }

            function retiraAcentos(obj) {

                com_acento = '.áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
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

            function buscaCEP(cep, tipo) {
                $.ajax({
                    type: "GET",
                    url:  "../../admin/ajax_cep.php",
                    data: "cep="+escape(cep),
                    cache: false,
                    complete: function(resposta){
                        results = resposta.responseText.split(";");
                        if (tipo == "C") {
                            if (typeof (results[1]) != 'undefined') $('#endereco').val(results[1]);
                            if (typeof (results[2]) != 'undefined') $('#bairro').val(results[2]);
                            if (typeof (results[4]) != 'undefined') $('#estado').val(results[4]);
                        }

                        if (tipo == "R") {
                            if (typeof (results[1]) != 'undefined') $('#endereco_revenda').val(results[1]);
                            if (typeof (results[2]) != 'undefined') $('#bairro_revenda').val(results[2]);
                            if (typeof (results[4]) != 'undefined') $('#estado_revenda').val(results[4]);
                        }

                        buscaCidade(results[4], results[3],tipo);
                    }
                });
            }

            function buscaProduto(familia, tipo_contato) {
                if(familia != 0){
                    $.ajax({
                        type: "POST",
                        url:  "fale_conosco.php",
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
                            if (tipo_contato == 'C') {
                                $("#produto_fale").html(options);
                            }
                            if (tipo_contato == 'R') {
                                $("#produto_revenda").html(options);
                            }
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
                    if (sMask.charAt(i-1) == "9") { // apenas números...
                        return ((nTecla > 47) && (nTecla < 58)); } // números de 0 a 9
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
