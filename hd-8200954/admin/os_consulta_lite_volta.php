<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

require_once "../classes/excelwriter/excelwriter.inc.php";

if ($login_admin == 2286) {
  echo "Programa em manutencao";
  exit;
}

$admin_privilegios = "call_center,gerencia";
include "autentica_admin.php";

if (isset($_POST["acao_exclui_os"]) && $_POST["acao_exclui_os"] == "t") {
    $os_array         = $_POST["exclui_os"];
    $motivo_exclui_os = trim($_POST["motivo_exclui_os"]);
    $msg_ok_excluir = array();

    if (!count($os_array)) {
        $msg_erro_excluir = "Nenhuma ordem de serviço selecionada para excluir";
    } else if (!strlen($motivo_exclui_os)) {
        $msg_erro_excluir = "Informe o motivo para excluir";
    } else {
        foreach ($os_array as $key => $os) {
            $sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {

                $sua_os = pg_fetch_result($res, 0, "sua_os");

                pg_query($con, "BEGIN");

                $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,admin)
                          VALUES ($os, 15, '$motivo_exclui_os', $login_admin)";
                $res = pg_query($con, $sql);

                if(strlen(pg_last_error()) > 0){
                    $msg_erro_excluir .= "{$sua_os} ".pg_last_error()."<br />";
                    pg_query($con, "ROLLBACK" );
                }else{
                    $sql = "SELECT fn_os_excluida({$os}, {$login_fabrica}, {$login_admin})";
                    $res = pg_query($con,$sql);

                    if(strlen(pg_last_error()) > 0){
                        $msg_erro_excluir .= "{$sua_os} ".pg_last_error()."<br />";
                        pg_query($con, "ROLLBACK" );
                    }else{
                        $msg_ok_excluir[] = $sua_os;
                        pg_query($con, "COMMIT" );
                    }

                }
                //insert na tbl_os_status
                //se der erro, dar rollback falar que a os atual deu erro e ir para a proxima os

                //se não deu nenhum erro chamar a função do banco SELECT fn_os_excluida({$os}, {$login_fabrica}, {$login_admin})
                //se deu algum erro na função, falar que a os atual deu erro, dar rollback e ir para a proxima

                /*
                 * comando de rollback pg_query($con, "ROLLBACK");
                 * se não deu nenhum erro realizar commit para salvar alteração pg_query($con, "COMMIT");
                 * para ir para a proximo item do loop em caso de erro use o comando continue;
                 */
            } else {
                $msg_erro_excluir .= "Ordem de Serviço {$os} não encontrada<br />";
            }
        }
    }
}

if (isset($_POST["post_anterior"]) && isset($_POST["acao_exclui_os"])) {
    $_POST = array_merge($_POST, json_decode(str_replace("\\\"", "\"", $_POST["post_anterior"]), true));

    unset($_POST["post_anterior"]);
}

//  Define alguns comportamentos do programa
$fabrica_copia_os_excluida      = in_array($login_fabrica, array(81,114,30, 122,128));

//  Além desta variável, precisa definir abaixo qual dos dois tipos de relatório
//  será gerado para o admin
$fabrica_baixa_relatorio_os     = in_array($login_fabrica, array(15, 42, 52, 81, 85, 114, 30,72));
$fabrica_autoriza_troca_revenda = in_array($login_fabrica, array(81,114));
$fabrica_autoriza_ressarcimento = in_array($login_fabrica, array(81,114));
$mostra_data_conserto       = in_array($login_fabrica, array(3, 11, 14, 15, 43, 45, 66, 80));

if (in_array($login_fabrica, array(141,144)) && $_POST["solicitaTroca"] == true) {
    $os = $_POST["os"];

    if (strlen($os) > 0) {
        $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $retorno = array("erro" => utf8_encode("OS não encontrada"));
        } else {
            $sql = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
            $res = pg_query($con, $sql);
            $rows = pg_num_rows($res);

            if ($rows > 0) {
                $status_os_troca_produto = pg_fetch_result($res, 0, "status_os");
            }

            if ($rows > 0 && in_array($status_os_troca_produto, array(192,193))) {
                switch ($status_os_troca_produto) {
                    case 192:
                        $retorno = array("erro" => utf8_encode("Já foi solicitado a troca de produto dessa OS"));
                        break;

                    case 193:
                        $retorno = array("erro" => utf8_encode("OS já teve a troca de produto efetuada"));
                        break;
                }
            } else {
                $insert = "INSERT INTO tbl_os_status
                                (os, status_os, observacao)
                                VALUES
                                ({$os}, 192, 'OS com troca de produto em auditoria')";
                $res = pg_query($con, $insert);

                if (strlen(pg_last_error()) > 0) {
                    $retorno = array("erro" => utf8_encode("Erro ao solicitar troca de produto"));
                } else {
                    $retorno = array("ok" => utf8_encode("Foi solicitada a troca de produto para a OS"));
                }
            }
        }
    } else {
        $retorno = array("erro" => utf8_encode("OS não informada"));
    }

    exit(json_encode($retorno));
}


if(in_array($login_fabrica,array(85)) && isset($_POST['abrir_atendimento']) && $_POST['abrir_atendimento'] == "ok"){

    $os = $_POST['os'];

    $sql_dados_os = "
        SELECT
            tbl_os.sua_os,
            tbl_os.posto,
            tbl_os.data_abertura,
            tbl_os.data_nf,
            tbl_os.consumidor_nome,
            tbl_os.consumidor_cpf,
            tbl_os.consumidor_endereco,
            tbl_os.consumidor_numero,
            tbl_os.consumidor_cep,
            tbl_os.consumidor_complemento,
            tbl_os.consumidor_bairro,
            tbl_os.consumidor_cidade,
            tbl_os.consumidor_estado,
            tbl_os.consumidor_fone,
            tbl_os.revenda_cnpj,
            tbl_os.revenda_nome,
            tbl_os.revenda_fone,
            tbl_os.produto,
            tbl_os.serie,
            tbl_os.defeito_reclamado_descricao,
            tbl_os.revenda,
            tbl_os.consumidor_revenda,
            tbl_os.nota_fiscal,
            tbl_os.tipo_atendimento,
            tbl_os.cod_ibge,
            tbl_os.obs,
            tbl_posto.nome AS posto_nome,
            tbl_posto_fabrica.codigo_posto
            FROM tbl_os
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE os  = {$os}
    ";

    $res_dados_os = pg_query($con, $sql_dados_os);

    if(pg_num_rows($res_dados_os) > 0){

        $sua_os                          = pg_fetch_result($res_dados_os, 0, sua_os);
        $posto                          = pg_fetch_result($res_dados_os, 0, posto);
        $data_abertura                  = pg_fetch_result($res_dados_os, 0, data_abertura);
        $data_nf                        = pg_fetch_result($res_dados_os, 0, data_nf);
        $consumidor_nome                = pg_fetch_result($res_dados_os, 0, consumidor_nome);
        $consumidor_cpf                 = pg_fetch_result($res_dados_os, 0, consumidor_cpf);
        $consumidor_endereco            = pg_fetch_result($res_dados_os, 0, consumidor_endereco);
        $consumidor_numero              = pg_fetch_result($res_dados_os, 0, consumidor_numero);
        $consumidor_cep                 = pg_fetch_result($res_dados_os, 0, consumidor_cep);
        $consumidor_complemento         = pg_fetch_result($res_dados_os, 0, consumidor_complemento);
        $consumidor_bairro              = pg_fetch_result($res_dados_os, 0, consumidor_bairro);
        $consumidor_cidade              = pg_fetch_result($res_dados_os, 0, consumidor_cidade);
        $consumidor_estado              = pg_fetch_result($res_dados_os, 0, consumidor_estado);
        $consumidor_fone                = pg_fetch_result($res_dados_os, 0, consumidor_fone);
        $consumidor_email               = pg_fetch_result($res_dados_os, 0, consumidor_email);
        $revenda_cnpj                   = pg_fetch_result($res_dados_os, 0, revenda_cnpj);
        $revenda_nome                   = pg_fetch_result($res_dados_os, 0, revenda_nome);
        $revenda_fone                   = pg_fetch_result($res_dados_os, 0, revenda_fone);
        $produto                        = pg_fetch_result($res_dados_os, 0, produto);
        $serie                          = pg_fetch_result($res_dados_os, 0, serie);
        $defeito_reclamado_descricao    = pg_fetch_result($res_dados_os, 0, defeito_reclamado_descricao);
        $revenda                        = pg_fetch_result($res_dados_os, 0, revenda);
        $consumidor_revenda             = pg_fetch_result($res_dados_os, 0, consumidor_revenda);
        $nota_fiscal                    = pg_fetch_result($res_dados_os, 0, nota_fiscal);
        $tipo_atendimento               = pg_fetch_result($res_dados_os, 0, tipo_atendimento);
        $cod_ibge                       = pg_fetch_result($res_dados_os, 0, cod_ibge);
        $obs                            = pg_fetch_result($res_dados_os, 0, obs);
        $posto_nome                     = pg_fetch_result($res_dados_os, 0, posto_nome);
        $codigo_posto                   = pg_fetch_result($res_dados_os, 0, codigo_posto);

        if(!empty($cod_ibge)){
            $sql_cidade = "SELECT cidade
                           FROM tbl_cidade
                           WHERE UPPER(fn_retira_especiais(nome)) = (SELECT UPPER(fn_retira_especiais(cidade)) FROM tbl_ibge WHERE cod_ibge = $cod_ibge) AND UPPER(estado) = (SELECT UPPER(estado) FROM tbl_ibge WHERE cod_ibge = $cod_ibge)";
            $res_cidade = pg_query($con, $sql_cidade);
            if(pg_num_rows($res_cidade) > 0){
                $cod_ibge = pg_fetch_result($res_cidade, 0, cidade);
            }else{
                $cod_ibge = "null";
            }

        }else{
            $cod_ibge = "null";
        }

        $sql_abre_chamado = "
            INSERT INTO tbl_hd_chamado
            (
                posto,
                titulo,
                status,
                atendente,
                categoria,
                fabrica_responsavel,
                fabrica
            )
            VALUES
            (
                $posto,
                'Atendimento interativo',
                'Aberto',
                $login_admin,
                'reclamacao_produto',
                $login_fabrica,
                $login_fabrica
            ) RETURNING hd_chamado
        ";
        $res_abre_chamado = pg_query($con, $sql_abre_chamado);

        $hd_chamado = pg_fetch_result($res_abre_chamado, 0, hd_chamado);

        if(!empty($hd_chamado)){

            $sql = "UPDATE tbl_os SET hd_chamado = $hd_chamado WHERE os = $os";
            $res = pg_query($con, $sql);

            $sql_extra = "
                INSERT INTO tbl_hd_chamado_extra
                (
                    hd_chamado,
                    produto,
                    revenda_nome,
                    posto,
                    os,
                    serie,
                    data_nf,
                    nota_fiscal,
                    defeito_reclamado_descricao,
                    nome,
                    endereco,
                    numero,
                    complemento,
                    bairro,
                    cep,
                    fone,
                    email,
                    cpf,
                    cidade,
                    revenda_cnpj
                )
                VALUES
                (
                    $hd_chamado,
                    $produto,
                    '$revenda_nome',
                    $posto,
                    $os,
                    '$serie',
                    '$data_nf',
                    '$nota_fiscal',
                    '$defeito_reclamado_descricao',
                    '$consumidor_nome',
                    '$consumidor_endereco',
                    '$consumidor_numero',
                    '$consumidor_complemento',
                    '$consumidor_bairro',
                    '$consumidor_cep',
                    '$consumidor_fone',
                    '$consumidor_email',
                    '$consumidor_cpf',
                    $cod_ibge,
                    '$revenda_cnpj'
                )
            ";

            $res_extra = pg_query($con, $sql_extra);

            if(!$res_extra){
                echo pg_last_error();
            }else{

                echo "$hd_chamado";
            }

        }else{
            echo pg_last_error();
        }

    }else{
        echo "Não foi possivel abrir o Atendimento através dessa OS";
    }

    exit;

}

if(isset($_POST['exclui_hd_chamado'])){

    $hd_chamado = $_POST['exclui_hd_chamado'];
    $motivo = $_POST['motivo'];

    if($login_fabrica == 137){

        $sql_motivo = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado          ,
                            data                ,
                            comentario          ,
                            admin               ,
                            interno             ,
                            status_item
                        ) values (
                            $hd_chamado         ,
                            current_timestamp   ,
                            '$motivo'           ,
                            $login_admin        ,
                            't'  ,
                            'Aberto'
                        )";

        $res_motivo = pg_query($con, $sql_motivo);

    }

    $sql = "UPDATE tbl_hd_chamado_extra SET abre_os = 'f' WHERE hd_chamado = $hd_chamado";
    $res = pg_query($con, $sql);

    if(pg_affected_rows($res) > 0){
        echo "success";
    }else{
        echo "Error";
    }

    exit;
}

if(@$_POST['ajax'] == 'ajax'){

    if($_POST['acao'] == 'intervencao'){
        $os = $_POST['os'];

        $sql = "INSERT INTO tbl_os_status
                    (os,status_os,data,observacao, admin)
                VALUES
                    ($os,158,current_timestamp,'Intervenção Departamento Jurídico', $login_admin)";
        if(pg_query($con,$sql)){
            echo 1; //sucesso!!!
        }
    }

    exit;
}

if (strlen($_POST["btn_acao_pre_os"]) > 0) $btn_acao_pre_os = strtoupper($_POST["btn_acao_pre_os"]);
if (strlen($_GET["btn_acao_pre_os"]) > 0)  $btn_acao_pre_os = strtoupper($_GET["btn_acao_pre_os"]);
//echo "LOGIN FABRICA =".$login_fabrica;

if (!function_exists('verificaSelect')) {

    function verificaSelect($valor1, $valor2) {
        return ($valor1 == $valor2) ? " selected = 'selected' " : "";
    }

}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

    $busca      = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q) > 2) {

        if ($tipo_busca == 'posto') {

            $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

            $sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND tbl_posto.nome ilike '%$q%' ";

            $res = pg_query($con,$sql);

            if (pg_num_rows ($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $cnpj         = trim(pg_fetch_result($res, $i, 'cnpj'));
                    $nome         = trim(pg_fetch_result($res, $i, 'nome'));
                    $codigo_posto = trim(pg_fetch_result($res, $i, 'codigo_posto'));

                    echo "$cnpj|$nome|$codigo_posto";
                    echo "\n";

                }

            }

        }

        if ($tipo_busca == "produto") {

            $sql = "SELECT tbl_produto.produto,
                            tbl_produto.referencia,
                            tbl_produto.descricao
                    FROM tbl_produto
                    JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                    WHERE tbl_linha.fabrica = $login_fabrica ";

            $sql .=  ($busca == "codigo") ? " AND tbl_produto.referencia like '%$q%' " : " AND UPPER(tbl_produto.descricao) ilike '%$q%' ";

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $produto    = trim(pg_fetch_result($res,$i,'produto'));
                    $referencia = trim(pg_fetch_result($res,$i,'referencia'));
                    $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
                    echo "$produto|$descricao|$referencia";
                    echo "\n";
                }
            }

        }

        if ($tipo_busca=="consumidor_cidade"){

            $sql = "SELECT      DISTINCT tbl_posto.cidade
                    FROM        tbl_posto_fabrica
                    JOIN tbl_posto using(posto)
                    WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
                    AND         tbl_posto.cidade ILIKE '%$q%'
                    ORDER BY    tbl_posto.cidade";

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $consumidor_cidade        = trim(pg_fetch_result($res,$i,cidade));
                    echo "$consumidor_cidade";
                    echo "\n";
                }
            }
        }
    }
    exit;
}

$os_excluir = $_GET['excluir']; //hd 61698 waldir

if (strlen ($os_excluir) > 0) {


    include_once '../anexaNF_inc.php';

    if($login_fabrica == 1){
        $sql = "SELECT posto FROM tbl_os WHERE os = $os_excluir";
        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res, 0, 'posto');
        }

        $sql = "SELECT tbl_os.os
                FROM tbl_os
                WHERE tbl_os.fabrica = $login_fabrica
                AND   tbl_os.posto   = $posto
                AND   (tbl_os.data_abertura + INTERVAL '180 days') <= current_date
                AND   tbl_os.data_fechamento IS NULL
                AND  tbl_os.excluida is FALSE LIMIT 1";

        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $tem_os_aberta = pg_fetch_result($res, 0, 'os');
        }
    }

    if ($fabrica_copia_os_excluida) {//HD 278885

        $motivo = $_GET['motivo'];

        $res = pg_query ($con,"BEGIN TRANSACTION");

        $sql = "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  ,
                    admin
                ) VALUES (
                    $os_excluir,
                    '$motivo' ,
                    15       ,
                    $login_admin
                );";

        $res = pg_query ($con,$sql);

        $sql = "UPDATE tbl_os SET excluida = true
                        WHERE  tbl_os.os           = $os_excluir
                        AND    tbl_os.fabrica      = $login_fabrica;";
        $res = pg_query($con,$sql);

        $msg_erro = pg_errormessage($con);

        $sql = "INSERT INTO tbl_os_excluida (
                        fabrica           ,
                        admin             ,
                        os                ,
                        sua_os            ,
                        posto             ,
                        codigo_posto      ,
                        produto           ,
                        referencia_produto,
                        data_digitacao    ,
                        data_abertura     ,
                        data_fechamento   ,
                        serie             ,
                        nota_fiscal       ,
                        data_nf           ,
                        consumidor_nome
                    )
                    SELECT  tbl_os.fabrica            ,
                        $login_admin                  ,
                        tbl_os.os                     ,
                        tbl_os.sua_os                 ,
                        tbl_os.posto                  ,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_os.produto                ,
                        tbl_produto.referencia        ,
                        tbl_os.data_digitacao         ,
                        tbl_os.data_abertura          ,
                        tbl_os.data_fechamento        ,
                        tbl_os.serie                  ,
                        tbl_os.nota_fiscal            ,
                        tbl_os.data_nf                ,
                        tbl_os.consumidor_nome
                    FROM    tbl_os
                    JOIN    tbl_posto_fabrica        on tbl_posto_fabrica.posto = tbl_os.posto and tbl_os.fabrica          = tbl_posto_fabrica.fabrica
                    JOIN    tbl_produto              on tbl_produto.produto     = tbl_os.produto
                    WHERE   tbl_os.os      = $os_excluir
                    AND     tbl_os.fabrica = $login_fabrica ";

        $res = pg_query($con,$sql);
        //HD 278885
        //PARA A SALTON NAO EXCLUI PEDIDO, OS OPERADORES VÃO ADICIONAR UM VALOR AVULSO NO EXTRATO
        //CASO O POSTO QUEIRA FICAR COM A PEÇA, SENAO A OS SERÁ EXCLUIDA APENAS QUANDO O POSTO DEVOLVER A PEÇA
        if ($login_fabrica == 1) {

            $res = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);

            #VERIFICA SE TEM PEDIDO PARA EXCLUIR
            $sql = "SELECT tbl_os_item.pedido_item
                            FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item USING(os_produto)
                            WHERE os = $os_excluir";

            $res = pg_query ($con,$sql);

            if (pg_num_rows($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $pedido_item = pg_fetch_result($res,$i,pedido_item);

                    if (!empty($pedido_item)) {
                        $sql_ped = "SELECT  PE.pedido      ,
                                    PE.distribuidor,
                                    PI.pedido_item ,
                                    PI.peca        ,
                                    PI.qtde        ,
                                    OP.os
                                    FROM   tbl_pedido        PE
                                    JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
                                    LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                                    LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                                    WHERE PI.pedido_item  = $pedido_item
                                    AND   PE.fabrica = $login_fabrica
                                    AND   PI.qtde > PI.qtde_cancelada
                                    AND   PI.qtde_faturada = 0";

                        $res_ped = pg_query ($con,$sql_ped);

                        if (pg_num_rows($res_ped) > 0) {
                            $pedido         = pg_fetch_result ($res_ped,0,pedido);
                            $peca           = pg_fetch_result ($res_ped,0,peca);
                            $qtde           = pg_fetch_result ($res_ped,0,qtde);
                            $os             = pg_fetch_result ($res_ped,0,os);
                            $distribuidor   = pg_fetch_result ($res_ped,0,distribuidor);

                            $sql  = "SELECT fn_pedido_cancela(1,$login_fabrica,$pedido,$peca,'OS excluída pelo fabricante',$login_admin)";
                            $resY = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);
                        } else {
                            $msg_erro = "OS com Peça já faturada";
                        }
                    }
                }
            }

        }//HD 278885

        if (strlen($msg_erro) == 0) {

            /**
             * Exclui os arquivos em anexo, se tiver
             **/
            if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
                foreach ($anexos as $arquivoAnexo) {
                    excluirNF($arquivoAnexo);
                }
            }

            $res = pg_query ($con,"COMMIT");

            if(!empty($tem_os_aberta)){
                $dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
                echo `/usr/bin/php $dir $posto`;
            }

            echo "<script language='javascript'>
                alert('Os Excluída com sucesso!');
                window.location = '$PHP_SELF';
            </script>";
        } else {
            $res = pg_query ($con,"ROLLBACK");
            echo "<script language='javascript'>
                    alert('Não foi possível excluir OS! ');
                    window.location = '$PHP_SELF';
            </script>";
        }

    } else {

        /**
         * Exclui os arquivos em anexo, se tiver
         **/
        if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
            foreach ($anexos as $arquivoAnexo) {
                excluirNF($arquivoAnexo);
            }
        }

        $sql = "SELECT fn_os_excluida($os_excluir,$login_fabrica,$login_admin);";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
        if (strlen ($msg_erro) == 0) {
            header("Location: os_parametros.php");
            exit;
        }
    }
}


$excluir_troca = $_GET['excluir_troca']; //HD 157191

if (strlen ($excluir_troca) > 0) {

    if($login_fabrica == 1){
        $sql = "SELECT posto FROM tbl_os WHERE os = $excluir_troca";
        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res, 0, 'posto');
        }

        $sql = "SELECT tbl_os.os
                FROM tbl_os
                WHERE tbl_os.fabrica = $login_fabrica
                AND   tbl_os.posto   = $posto
                AND   (tbl_os.data_abertura + INTERVAL '180 days') <= current_date
                AND   tbl_os.data_fechamento IS NULL
                AND  tbl_os.excluida is FALSE LIMIT 1";

        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $tem_os_aberta = pg_fetch_result($res, 0, 'os');
        }
    }

    $sql = "UPDATE tbl_os SET data_fechamento = current_date WHERE os = $excluir_troca";
    $res = pg_query ($con,$sql);

    $sql="UPDATE tbl_os_extra set extrato = 0 where os = $excluir_troca;";
    $res= pg_query($con, $sql);

    $sql="UPDATE tbl_os_troca set status_os = 13 where os = $excluir_troca;";
    $res= pg_query($con, $sql);

    $sql = "INSERT INTO tbl_os_status (
                        os             ,
                        status_os      ,
                        observacao     ,
                        admin          ,
                        status_os_troca
                    ) VALUES (
                        '$excluir_troca'             ,
                        '13'                         ,
                        'OS Recusada pelo Fabricante',
                        $login_admin                 ,
                        't'
                    );";

    $res = pg_query ($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");

        if(!empty($tem_os_aberta)){
            $dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
            echo `/usr/bin/php $dir $posto`;
        }

    }else{
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }



    if (strlen ($msg_erro) == 0) {
        header("Location: os_parametros.php");
        exit;
    }
}


$os_fechar = $_GET['fechar'];

if (strlen ($os_fechar) > 0) {
    if ($login_fabrica == 91 && $_GET["sem_pagamento"]) {
        $motivo_fechar = $_GET["motivo"];
    }

    $msg_erro = "";
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql = "SELECT status_os
                FROM tbl_os_status
                WHERE os = $os_fechar
                AND status_os IN (62,64,65,72,73,87,88,116,117)
                ORDER BY data DESC
                LIMIT 1";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res)>0){
            $status_os = trim(pg_fetch_result($res,0,status_os));
            if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
                if ($login_fabrica ==51) { // HD 59408
                    $sql = " INSERT INTO tbl_os_status
                            (os,status_os,data,observacao)
                            VALUES ($os_fechar,64,current_timestamp,'OS Fechada pelo posto')";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
                            WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
                            AND   tbl_os_produto.os = $os_fechar";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
                            WHERE tbl_os.os = $os_fechar";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }else{
                    $msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
                }
            }
        }

        if ($login_fabrica == 91 && $_GET["sem_pagamento"] && strlen ($msg_erro) == 0) {
            $sql = "INSERT INTO tbl_os_status
                    (os, status_os, data, observacao)
                    VALUES
                    ({$os_fechar}, 90, current_timestamp, 'OS fechada sem pagamento: {$motivo_fechar}')";
            $res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con) ;
        }

        if (strlen ($msg_erro) == 0) {
            $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os_fechar AND fabrica = $login_fabrica";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con) ;
        }

        if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
            $sql = "SELECT fn_valida_os_item($os_fechar, $login_fabrica)";
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen ($msg_erro) == 0) {
            $sql = "SELECT fn_finaliza_os($os_fechar, $login_fabrica)";
            $res = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
            if (strlen ($msg_erro) == 0 and ($login_fabrica==1 or $login_fabrica==24)) {
                $sql = "SELECT fn_estoque_os($os_fechar, $login_fabrica)";
                $res = @pg_query ($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
        }

        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");
            echo "ok;XX$os_fechar";
        }else{
            $res = @pg_query ($con,"ROLLBACK TRANSACTION");
            echo "erro;$sql ==== $msg_erro ";
        }
    flush();
    exit;
}

#HD 234532
$sql_status = "SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint";
$res_status = pg_query($con,$sql_status);
$total_status = pg_num_rows($res_status);

for($i=0;$i<$total_status;$i++){
    $id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
    $cor_status = pg_fetch_result($res_status,$i,'cor');
    $descricao_status = pg_fetch_result($res_status,$i,'descricao');

    #Array utilizado posteriormente para definir as cores dos status
    $array_cor_status[$id_status] = $cor_status;
    $array_cor_descricao[$id_status] = $descricao_status;
}

#HD 234532
function exibeImagemStatusCheckpoint($status_checkpoint, $sua_os='', $retorna_descricao = false){

    global $array_cor_status;
    global $array_cor_descricao;

    if ($retorna_descricao === true) {
        return $array_cor_descricao[$status_checkpoint];
    } else {
        /*
        0 | Aberta Call-Center  (imagens/status_branco)
        1 | Aguardando Analise  (imagens/status_vermelho)
        2 | Aguardando Peças    (imagens/status_amarelo)
        3 | Aguardando Conserto (imagens/status_rosa)
    4 | Aguardando Retirada (imagens/status_azul)
    8 | Aguardando Produto  (imagens/status_laranja)
        9 | Finalizada          (imagens/status_cinza)
        */
        if(strlen($status_checkpoint) > 0){
            echo '<span class="status_checkpoint" id="st_ch_'.$sua_os.'" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
        }else{
            echo '<span class="status_checkpoint_sem" id="st_ch_'.$sua_os.'" >&nbsp;</span>';
        }
    }
}


$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) {
    //HD 393737 : filtro IBBL, OS Lançadas : Hoje, Ontem, Esta Semana, Semana Anterior e no Mês
    if($login_fabrica == 90){
        if($_POST['chk_opt1'])    $chk1        = $_POST['chk_opt1'];
        else if($_GET['chk_opt1'])    $chk1        = $_GET['chk_opt1'];
        if($_POST['chk_opt2'])    $chk2        = $_POST['chk_opt2'];
        else if($_GET['chk_opt2'])    $chk2        = $_GET['chk_opt2'];
        if($_POST['chk_opt3'])    $chk3        = $_POST['chk_opt3'];
        else if($_GET['chk_opt3'])    $chk3        = $_GET['chk_opt3'];
        if($_POST['chk_opt4'])    $chk4        = $_POST['chk_opt4'];
        else if($_GET['chk_opt4'])    $chk4        = $_GET['chk_opt4'];
        if($_POST['chk_opt5'])    $chk5        = $_POST['chk_opt5'];
        else if($_GET['chk_opt5'])    $chk5        = $_GET['chk_opt5'];

        if(!empty($chk1) OR !empty($chk2) OR !empty($chk3) OR !empty($chk4) OR !empty($chk5) ){
            $monta_sql .= " AND ( ";
            if (strlen($chk1) > 0) {
                // data do dia
                $sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
                $dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

                $sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
                $resX = pg_exec ($con,$sqlX);
                #  $dia_hoje_final = pg_result ($resX,0,0);

                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
            //  if ($ip == '201.42.44.145') echo $monta_sql;
                $dt = 1;

            }

            if (strlen($chk2) > 0) {
                // dia anterior
                $sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
                $dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

                if(!empty($chk1) ){
                    $monta_sql .=" OR ";
                }

                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
                $dt = 1;

            }

            if (strlen($chk3) > 0) {
                // nesta semana
                $sqlX = "SELECT to_char (current_date , 'D')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

                $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

                $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

                if(!empty($chk1) OR !empty($chk2) ){
                    $monta_sql .=" OR ";
                }

                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
                $dt = 1;

            }

            if (strlen($chk4) > 0) {
                // semana anterior
                $sqlX = "SELECT to_char (current_date , 'D')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_hoje = pg_result ($resX,0,0) - 1 + 7 ;

                $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

                $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

                if(!empty($chk1) OR !empty($chk2) OR !empty($chk3) ){
                    $monta_sql .=" OR ";
                }
                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
                $dt = 1;

            }

            if (strlen($chk5) > 0)
            {
                $mes_inicial = trim(date("Y")."-".date("m")."-01");
                $mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

                if(!empty($chk1) OR !empty($chk2) OR !empty($chk3) OR !empty($chk4) ){
                    $monta_sql .=" OR ";
                }

                $monta_sql .= " tbl_os.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59' ";
                $dt = 1;

            }
            $monta_sql .= ") ";
        }
    }

    //HD 211825: Filtro por tipo de OS: Consumidor/Revenda
    $consumidor_revenda_pesquisa = trim(strtoupper ($_POST['consumidor_revenda_pesquisa']));
    if (strlen($consumidor_revenda_pesquisa) == 0) $consumidor_revenda_pesquisa = trim(strtoupper($_GET['consumidor_revenda_pesquisa']));

    $os_off    = trim (strtoupper ($_POST['os_off']));
    if (strlen($os_off)==0) $os_off = trim(strtoupper($_GET['os_off']));
    $codigo_posto_off      = trim(strtoupper($_POST['codigo_posto_off']));
    if (strlen($codigo_posto_off)==0) $codigo_posto_off = trim(strtoupper($_GET['codigo_posto_off']));
    $posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));
    if (strlen($posto_nome_off)==0) $posto_nome_off = trim(strtoupper($_GET['posto_nome_off']));

    $sua_os    = trim (strtoupper ($_POST['sua_os']));
    if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
    $serie     = trim (strtoupper ($_POST['serie']));
    if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
    $nf_compra = trim (strtoupper ($_POST['nf_compra']));
    if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
    $consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
    if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));

    $rg_produto_os = trim (strtoupper ($_POST['rg_produto_os']));
    if (strlen($rg_produto_os)==0) $rg_produto_os = trim(strtoupper($_GET['rg_produto_os']));


    $marca = $_POST['marca'];

    if ($login_fabrica ==52) {
        if (strlen($marca)==0){
          $marca = $_GET['marca'];
        }
        $cond_marca = (strlen($marca)>0) ? " tbl_os.marca = $marca " :" 1 = 1 ";
    }else if($login_fabrica == 1){
        if (strlen($marca)==0){
          $marca = $_GET['marca'];
        }
        $cond_marca = (strlen($marca)>0) ? " tbl_produto.marca = $marca " :" 1 = 1 ";
    }else{
        if (strlen($marca)==0){
          $marca = $_GET['marca'];
        }
        $cond_marca = (strlen($marca)>0) ? " tbl_marca.marca = $marca " :" 1 = 1 ";

    }

    $regiao     = trim ($_POST['regiao']);
    if (strlen($regiao)==0) $regiao = trim($_GET['regiao']);
    $classificacao_os = trim ($_POST['classificacao_os']); // HD 75762 para Filizola
    if (strlen($classificacao_os)==0) $classificacao_os = trim($_GET['classificacao_os']);
    $cond_classificacao_os = (strlen($classificacao_os)>0) ? " tbl_os_extra.classificacao_os = $classificacao_os " : " 1 = 1 ";

    $natureza = trim ($_POST['natureza']); //HD 45630
    if (strlen($natureza)==0) $natureza = trim($_GET['natureza']);
    $cond_natureza = (strlen($natureza)>0) ? " tbl_os.tipo_atendimento = $natureza " : " 1 = 1 ";

    # HD 48224
    $admin_abriu = trim ($_POST['admin_abriu']);
    if (strlen($admin_abriu)==0) $admin_abriu = trim($_GET['admin_abriu']);
    if(strlen($admin_abriu) > 0){
        $cond_admin = "AND tbl_os.admin = $admin_abriu";
    }

    $rg_produto  = strtoupper(trim ($_POST['rg_produto']));
    $lote        = strtoupper(trim ($_POST['lote']));

//takashi - não sei pq colocaram isso, estava com problema... caso necessite voltar, consulte o suporte
//takashi alterei novamente conforme Tulio e Samuel falaram
    if((strlen($sua_os)>0) and (strlen($sua_os)<4))$msg="Digite no minímo 4 caracteres para fazer a pesquisa";


    $codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
    if (strlen($codigo_posto)==0){
        $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
        $posto_nome   = trim(strtoupper($_POST['posto_nome']));
    }

    $tipo_atendimento = trim(@$_REQUEST['tipo_atendimento']);
    $descricao_tipo_atendimento = trim(@$_REQUEST['tipo_atendimento']);

    if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
    $consumidor_nome    = trim($_POST['consumidor_nome']);
    if (strlen($consumidor_nome)==0) $consumidor_nome = trim($_GET['consumidor_nome']);
    $produto_referencia = trim(strtoupper($_POST['produto_referencia']));
    if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
    $admin              = trim($_POST['admin']);
    if (strlen($admin)==0) $admin = trim($_GET['admin']);
    $os_aberta          = trim(strtoupper($_POST['os_aberta']));
    if (strlen($os_aberta)==0) $os_aberta = trim(strtoupper($_GET['os_aberta']));
    $os_atendida        = trim($_POST['os_atendida']);
    if (strlen($os_atendida)==0) $os_atendida = trim(strtoupper($_GET['os_atendida']));

    #HD 234532
    $status_checkpoint          = trim(strtoupper($_POST['status_checkpoint']));
    if (strlen($status_checkpoint)==0) $status_checkpoint = trim(strtoupper($_GET['status_checkpoint']));

    $status_checkpoint_pesquisa = $status_checkpoint;

    #115630----
    $os_finalizada      = trim(strtoupper($_POST['os_finalizada']));
    if (strlen($os_finalizada)==0) $os_finalizada = trim(strtoupper($_GET['os_finalizada']));
    #----------
    $os_situacao        = trim(strtoupper($_POST['os_situacao']));
    if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
    $revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
    if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));
    $pais               = trim(strtoupper($_POST['pais']));
    if (strlen($pais)==0) $pais = trim(strtoupper($_GET['pais']));

    $tipo_os               = trim(strtoupper($_POST['tipo_os']));
    if (strlen($tipo_os)==0) $tipo_os = trim(strtoupper($_GET['tipo_os']));

    $data_inicial = $_POST['data_inicial'];
    if (strlen($data_inicial)==0){
        $data_inicial = trim($_GET['data_inicial']);
    }
    $data_final   = $_POST['data_final'];
    if (strlen($data_final)==0){
        $data_final = trim($_GET['data_final']);
    }

    if(!empty($data_inicial) OR !empty($data_final)){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg = "Data inicial inválida";

        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg = "Data final inválida";

        if(strlen($msg)==0){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";

            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                $msg = "Data inicial maior do que a data final";
            }
        }
    }


    if ($login_fabrica <> 15) {
        // HD 139148 - Liberar pesquisa somente com nome do consumidor, deste que seja especificado pello menos 10 letras (augusto)
        if (strlen($consumidor_nome) > 0 && strlen($consumidor_nome) < 10 AND strlen ($codigo_posto) == 0 AND strlen ($produto_referencia) == 0) {
            $msg = "Especifique o posto ou o produto";
        }
    }


    $estado = trim($_POST['estado']);
    if (strlen($estado)==0){
        $estado = $_GET['estado'];
    }

    if($estado){
        switch($estado){
            case 'Norte':
                $consulta_estado = "AC','AP','AM','PA','RO','RR','TO";
            break;

            case 'Nordeste':
                $consulta_estado = "AL','BA','CE','MA','PB','PE','PI','RN','SE";
            break;

            case 'Centro_oeste':
                $consulta_estado = "DF','GO','MT','MS";
            break;

            case 'Sudeste':
                $consulta_estado = "ES','MG','RJ','SP";
            break;

            case 'Sul':
                $consulta_estado = "PR','RS','SC";
            break;

            default: $consulta_estado = $estado;
        }
    }

    if($login_fabrica == 30){
        $consulta_cidade = filter_input(INPUT_POST,'cidade');
        $cons_sql_cidade = " AND tbl_os.consumidor_cidade ILIKE '%$consulta_cidade%'
        ";

        $cliente_admin = filter_input(INPUT_POST,'cliente_admin');
    }

    $consumidor_cpf = preg_replace ("/\D/","",$consumidor_cpf);

    if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
        #HD 17333
        if ($login_fabrica<>20){
            $msg = "Tamanho do CPF do consumidor inválido";
        }
    }

    // HD 415550
    if(isset($_POST['nome_tecnico']) && !empty($_POST['nome_tecnico'])  ) {
        if( empty($codigo_posto) )
            $msg = "Selecione o posto para efetuar essa consulta";
        $tecnico = trim ($_POST['nome_tecnico']);
        $condicao_tecnico = (!empty($tecnico)) ? " AND tbl_os.tecnico_nome ILIKE '" . $tecnico . "%' " : '';
    }

    if ($login_fabrica == 24 ){

        if ( isset($_POST['os_congelada']) OR isset($_GET['os_congelada'])) {
            $os_congelada = $_REQUEST['os_congelada'];

            if($os_congelada == 'congelada'){
                $cond_congelada = " AND  tbl_os.cancelada IS TRUE ";
            }
            elseif($os_congelada == 'congelar'){
                $cond_congelada = " AND tbl_os.data_fechamento IS NULL
                                    AND tbl_os.cancelada IS NOT TRUE
                                    AND tbl_os.data_abertura::date BETWEEN (current_date - interval '60 days')::date and (current_date - interval '30 days')::date
                                ";
            }
        }else{
            $cond_congelada = "  AND tbl_os.cancelada IS NOT TRUE ";
        }
    }


    $revenda_cnpj = str_replace (".","",$revenda_cnpj);
    $revenda_cnpj = str_replace (" ","",$revenda_cnpj);
    $revenda_cnpj = str_replace ("-","",$revenda_cnpj);
    $revenda_cnpj = str_replace ("/","",$revenda_cnpj);
    //HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais
    if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
        $msg = "Digite CNPJ completo para pesquisar";
    }

    if (strlen ($nf_compra) > 0 ) {
        if (($login_fabrica==19) and strlen($nf_compra) > 6) {
            $nf_compra = "0000000" . $nf_compra;
            $nf_compra = substr ($nf_compra,strlen ($nf_compra)-7);
        } elseif($login_fabrica <> 11) {
            if($login_fabrica == 3){
                $nf_compra = $nf_compra;
            }else{
                if(strlen($nf_compra)<=6) {
                    $nf_compra = "000000" . $nf_compra;
                    $nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
                }
            }
        }
    }


        $HI = "00:00:00";
        $HF = "23:59:59";
        $data_inicio_consulta = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_inicial);
        $data_fim_consulta = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_final);



        /*=== VALIDAÇÃO DE DATA ===*/
        $data_valida ="t";
        if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(@!checkdate($mi,$di,$yi))
            $data_valida = "f";
        }
        if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(@!checkdate($mf,$df,$yf))
            $data_valida = "f";
        }
        if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
        }
        if(strlen($msg_erro)==0){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
            or strtotime($aux_data_final) > strtotime('today')){
                $data_valida  = "f";
            }
        }

        /*=== FIM VALIDAÇÃO DE DATA ===*/
        if(strlen($sua_os) ==0 && strlen($serie) ==0 && strlen($consumidor_cpf) ==0 && strlen($dt) == 0){

            $periodo_6meses ="";
            $periodo_12meses ="";
            if(strlen($posto_nome) > 0){

                if(strlen($msg) ==0){
                    if(strlen($data_inicial) > 0 && strlen($data_final) > 0 || strlen($os_aberta) ==0 || strlen($consumidor_nome) ==0 || strlen($produto_referencia) ==0){
                        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                            if(strlen($produto_referencia) ==0){
                                $sqlX = "SELECT '$data_inicio_consulta'::date + interval '6 months' > '$data_fim_consulta'";
                                $resX = pg_query($con,$sqlX);
                                $periodo_6meses = pg_fetch_result($resX,0,0);
                                if($periodo_6meses == 'f'){
                                    $msg = "AS DATAS DEVEM SER NO MÁXIMO 6 MESES";
                                }
                            }

                            $conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";

                        }else{
                            if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                                if(strlen($produto_referencia) ==0){
                                    $sqlX = "SELECT '$data_inicio_consulta'::date + interval '12 months' > '$data_fim_consulta'";
                                    $resX = pg_query($con,$sqlX);
                                    $periodo_12meses = pg_fetch_result($resX,0,0);
                                    if($periodo_12meses == 'f' && $posto_nome == '' ){
                                        $msg = "AS DATAS DEVEM SER NO MÁXIMO 12 MESES";
                                    }
                                }

                                $conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";

                            }

                        }
                    }else{
                       $msg = "PREENCHA MAIS CAMPOS PARA REALIZAR A PESQUISA!!!";
                    }
                }

            }else{

                if(strlen($msg) ==0){
                    if(strlen($data_inicial) > 0 && strlen($data_final) > 0 && strlen($produto_referencia) ==0){

                        $sqlX = "SELECT '$data_inicio_consulta'::date + interval '6 months' > '$data_fim_consulta'";
                        $resX = @pg_query($con,$sqlX);
                        $periodo_6meses = @pg_fetch_result($resX,0,0);
                        if($periodo_6meses == 'f'){
                            $msg = "AS DATAS DEVEM SER NO MÁXIMO 6 MESES";
                        }

                        if($login_fabrica == 96){
                            $codicao_data_temp = " AND   tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
                        }
                        // elseif($login_fabrica == 24 and ( !isset($_POST['os_congelada']) OR !isset($_POST['os_congelada']))){
                        //     $conds_sql .= "  AND tbl_os.data_abertura::date > (current_date - interval '30 days')::date ";
                        // }
                        else{
                            $conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
                        }
                        if($login_fabrica == 141){
                            if(strlen($consulta_estado) > 0){
                                $conds_sql .= " AND tbl_cidade.estado IN ('$consulta_estado')";
                            }
                        }else{
                            if(strlen($consulta_estado) > 0){
                                $conds_sql .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
                            }
                        }
                    }else{
                        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                            if(strlen($produto_referencia) > 0){
                                $sqlX = "SELECT '$data_inicio_consulta'::date + interval '12 months' > '$data_fim_consulta'";
                                $resX = @pg_query($con,$sqlX);
                                $periodo_12meses = @pg_fetch_result($resX,0,0);
                                if($periodo_12meses == 'f' && strlen($posto_nome) == 0 && strlen($os_aberta) == 0 && strlen($consumidor_nome) == 0){
                                    $msg = "AS DATAS DEVEM SER NO MÁXIMO 12 MESES";
                                }
                            }
                            $conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";

                        }else{
                            ## HD-2507504 ##
                            if($login_fabrica == 52 AND strlen($hd_chamado_numero) > 0 OR strlen($pre_os) > 0){
                                $msg = "";
                            }else{
                                $msg = "INFORME A DATA INICIAL E FINAL PARA PESQUISA";
                            }

                        }

                    }
                }

            }

        }else{
            if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
                $conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            }
        }

        if($login_fabrica == 141){
            if(strlen($consulta_estado) > 0){
                $conds_sql .= " AND tbl_cidade.estado IN ('$consulta_estado')";
            }
        }else{
            if(strlen($consulta_estado) > 0){
                $conds_sql .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
            }
        }

        if($login_fabrica == 52) {
            //echo "FABRICAS =".$login_fabrica;
            $numero_ativo = trim (strtoupper ($_POST['numero_ativo']));
            if(strlen($numero_ativo)==0) {
                $numero_ativo = trim(strtoupper($_GET['numero_ativo']));
            }
            $cidade_do_consumidor = trim (strtoupper ($_POST['cidade_do_consumidor']));
            if(strlen($cidade_do_consumidor)==0) {
                $cidade_do_consumidor = trim(strtoupper($_GET['cidade_do_consumidor']));
            }
            $hd_chamado_numero = trim (strtoupper ($_POST['hd_chamado_numero']));
            if(strlen($hd_chamado_numero)==0) {
                $hd_chamado_numero = trim(strtoupper($_GET['hd_chamado_numero']));
            }

            if(strlen($numero_ativo) > 0) {
                if(strlen($data_inicio_consulta)> 0  && strlen($data_fim_consulta)> 0) {

                    $sqlp = "SELECT '$data_inicio_consulta'::date + interval '1 months' >= '$data_fim_consulta'";
                    $resp = @pg_query($con,$sqlp);
                    $periodo_ativo_1 = @pg_fetch_result($resp,0,0);
                    if($periodo_ativo_1 == 't') {
                        $conds_sql_ativo = " JOIN  tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto AND tbl_os.serie = tbl_numero_serie.serie AND tbl_numero_serie.ordem = '$numero_ativo'";
                    }else {
                        $msg = "AS DATAS DEVEM SER NO MÁXIMO 1 MÊS";
                    }

                }else {
                    $msg = "INFORME A DATA INICIAL E FINAL DENTRO DE UM PERÍODO DE 1 MÊS";
                }
            }

            if(strlen($hd_chamado_numero) > 0) {
                $conds_sql .= " AND tbl_os.hd_chamado = '$hd_chamado_numero'";
            }

            if(strlen($cidade_do_consumidor) > 0) {
                if(strlen($data_inicio_consulta)> 0  && strlen($data_fim_consulta)> 0) {

                    $sqlp = "SELECT '$data_inicio_consulta'::date + interval '1 months' >= '$data_fim_consulta'";
                    $resp = @pg_query($con,$sqlp);
                    $periodo_ativo_1 = @pg_fetch_result($resp,0,0);
                    if($periodo_ativo_1 == 't') {
                        $conds_sql .= " AND tbl_os.consumidor_cidade LIKE '%$cidade_do_consumidor%' ";
                    }else {
                        $msg = "AS DATAS DEVEM SER NO MÁXIMO 1 MÊS";
                    }

                }else {
                    $msg = "INFORME A DATA INICIAL E FINAL DENTRO DE UM PERÍODO DE 1 MÊS";
                }
            }
        }

        /*
        echo "OS ABERTA =".strlen($os_aberta)."<BR>";
        echo "CONSUMIDOR =".strlen($consumidor_nome)."<BR>";
        echo "PROD REFERENCIA =".strlen($produto_referencia)."<BR>";
        echo "DATA VALIDA =".$data_valida."<BR>";
        echo "POSTO NOME =".strlen($posto_nome)."<BR>";
        echo "MENSAGEM =".strlen($msg)."<BR>";
        echo "ESTADO =".strlen($estado)."<BR>";
        */
        /*if(strlen($os_aberta) ==0 && strlen($consumidor_nome) ==0 && strlen($produto_referencia) ==0 && $data_valida =='f' && strlen($msg) ==0 && strlen($estado) == 0 && strlen($sua_os) ==0 && strlen($serie) ==0 && strlen($nf_compra) ==0 && strlen($consumidor_cpf) ==0 && strlen($dt) ==0 && ( ($login_fabrica == 94 && strlen( trim($_POST['nome_tecnico'])) == 0 ) || $login_fabrica != 94 ) ){
            $msg = "PREENCHA MAIS CAMPOS PARA REALIZAR A PESQUISA";
        }*/

        //echo "MENSAGME DE ERRO =".$msg."<BR>";

    //validacao para pegar o posto qdo for digitado a os_off
    if(strlen($os_off)>0){
        if ((strlen($codigo_posto_off)==0) OR (strlen($posto_nome_off)==0)){
            $msg = "Informe o Posto desejado";
        }
    }
    //IGOR HD 1967 BLACK - PARA CONSULTAR OS É OBRIGATÓRIO SELECIONAR O POSTO
    /* HD 257239 RETIRAR RESTRIÇÃO QUANDO O ADMIN DIGITA A OS
    if($login_fabrica==1) {
        if ((strlen($codigo_posto)== 0 ) and (strlen($sua_os)>0) )
            $msg = "Para consultar pelo número de OS é necessário Informar o código do posto";
    }*/

    if (strlen($msg) == 0 && strlen($opcao2) > 0) {
        if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
        if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
        if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
        if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
        if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

        if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
            $sql_posto =    "SELECT tbl_posto.posto        ,
                            tbl_posto.nome                 ,
                            tbl_posto_fabrica.codigo_posto ,
                            tbl_posto_fabrica.contato_cidade,
                            tbl_posto_fabrica.contato_estado
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica USING (posto)
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                    AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
            $res = pg_query($con,$sql_posto);
            if (pg_num_rows($res) == 1) {
                $posto        = trim(pg_fetch_result($res,0,posto));
                $posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
                $posto_nome   = trim(pg_fetch_result($res,0,nome));
            }else{
                $erro .= " Posto não encontrado. ";
            }
        }
    }



    if ($login_fabrica == 3) {
        $posto_ordenar = $_POST['posto_ordenar'];
    }

}

if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
include "cabecalho.php";
?>

<style type="text/css">
th.headerSortUp {
    /*background-image: url(imagens/asc.gif);*/
    background-position: right center;
    background-repeat: no-repeat;
    background-color: #596d9b !important;
}
th.headerSortDown {
    /*background-image: url(imagens/desc.gif);*/
    background-position: right center;
    background-repeat: no-repeat;
    background-color: #596d9b !important;
}
th.header {
    font-family: verdana;
    font-size: 11px;
    border:1px solid #596d9b !important;
    cursor: pointer;
    background-color: #596d9b !important;
}
.status_checkpoint{width:9px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}

.legenda_os_cor{width:75px;height:15px;border:1px solid #666;margin:2px 5px;padding:0 5px;}
.legenda_os_texto{margin:2px 5px;padding:0 5px;font-weight: bold;}


.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tablesorter thead tr th, table.tablesorter tfoot tr th{
    border:1px solid #596d9b !important;
    background-color: #596d9b !important;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.subtitulo{

    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}

table.tabela tbody tr td{
    padding:0 5px 0 5px;
}

</style>

<?php include "javascript_pesquisas_novo.php"; ?>
<?php
    include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script type="text/javascript" src="js/tablesorter.min.js"></script>
<script language="javascript">
    $(document).ready(function()
    {
        Shadowbox.init();
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");

        $(".intervencao").click(function()
        {
            var os     = $(this).attr('id');
            var sua_os = $(this).attr('rel');

            if( confirm("Deseja colocar a OS "+sua_os+ " em intervenção Juridica?") )
            {
                $.ajax({
                    type    : 'POST',
                    url     : "<?php echo $_SERVER['PHP_SELF']; ?>",
                    data    : "ajax=ajax&acao=intervencao&os="+os,
                    success : function(data){
                        if(data == 1){
                            $("#"+os).fadeOut();
                            $("#"+os).parent().parent().css('background-color','#FFCCCC');
                        }else
                            alert("Erro ao colocar OS em intervenção!");
                    }
                });
            }
            return ;
        });
    });

    function disp_prompt(os, sua_os){
        var motivo =prompt("Qual o Motivo da Exclusão da os "+sua_os+" ?",'',"Motivo da Exclusão");
        if (motivo !=null && $.trim(motivo) !="" && motivo.length > 0 ){
                var url = '<?=$PHP_SELF?>'+'?excluir='+os+"&motivo="+motivo;
                window.location = url;
        }else{
            alert('Digite um motivo por favor!','Erro');
        }
    }

    function escondeColuna(){
        if($("td[rel='esconde_coluna']").css('display') != 'none'){
            $("td[rel='esconde_coluna']").hide();
            $('#esconde').html('Mostrar Colunas');
        }else{
            $("td[rel='esconde_coluna']").show();
            $('#esconde').html('Esconder Colunas');
        }

    }

$().ready(function() {

    $("#content").tablesorter({decimal: ",", dateFormat: "uk"});

    function formatItem(row) {
        return row[2] + " - " + row[1];
    }

    /* OFFF Busca pelo Código */
    $("#codigo_posto_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#codigo_posto_off").result(function(event, data, formatted) {
        $("#posto_nome_off").val(data[1]) ;
    });

    /* Busca pelo Nome */
    $("#posto_nome_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#cidade").autocomplete("<?echo $PHP_SELF.'?tipo_busca=consumidor_cidade&busca=consumidor_cidade'; ?>", {
        minChars: 3,
        delay: 150,
        width: 205,
        matchContains: true,
        formatItem: function(row) {
            return row[0];
        },
        formatResult: function(row) {
            return row[0];
        }
    });

    $("#posto_nome_off").result(function(event, data, formatted) {
        $("#codigo_posto_off").val(data[2]) ;
        //alert(data[2]);
    });


    /* Busca pelo Código */
    $("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#codigo_posto").result(function(event, data, formatted) {
        $("#posto_nome").val(data[1]) ;
    });

    /* Busca pelo Nome */
    $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#posto_nome").result(function(event, data, formatted) {
        $("#codigo_posto").val(data[2]) ;
        //alert(data[2]);
    });


    /* Busca por Produto */
    $("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#produto_descricao").result(function(event, data, formatted) {
        $("#produto_referencia").val(data[2]) ;
    });

    /* Busca pelo Nome */
    $("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#produto_referencia").result(function(event, data, formatted) {
        $("#produto_descricao").val(data[1]) ;
        //alert(data[2]);
    });

});

<?php
    if($login_fabrica == 137){
    ?>

        function motivoExclusao(hd_chamado){

            Shadowbox.open({
                content: '<div style="width: 90%; padding: 20px;"> \
                            <h1>Informe o Motivo</h1> <br /> \
                            <textarea name="motivo_exclusao" class="input" id="motivo_exclusao" cols="50" rows="6"></textarea> <br /> \
                            <button onClick="exclui_hd_chamado('+hd_chamado+')"> Cadastrar Motivo</button> \
                        </div>',
                player: "html",
                options: {
                    enableKeys: false
                },
                title: "Motivo de Exclusão",
                width: 500,
                height: 250
            });

        }

    <?php
    }
?>

function exclui_hd_chamado(hd_chamado) {

    var motivo = "";

    <?php
        if($login_fabrica == 137){
            ?>
            motivo = $('#motivo_exclusao').val();
            if(motivo == ""){
                alert("Por favor digite o Motivo de Exclusão");
                $('#motivo_exclusao').focus();
                return;
            }

            window.parent.Shadowbox.close();

            <?php
        }
    ?>

    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: 'post',
        data: { exclui_hd_chamado : hd_chamado, motivo : motivo },
        complete: function(res){
            var data = res.responseText;
            if(data == "success"){
                alert("Pré-OS do Atendimento "+hd_chamado+" Excluído com Sucesso!");
                $('#div_atendimento_'+hd_chamado).remove();
            }else{
                alert("Erro ao Excluir a Pré-OS "+hd_chamado);
            }
        }
    });

}

function _trim (s)
{
   //   /            open search
   //     ^            beginning of string
   //     \s           find White Space, space, TAB and Carriage Returns
   //     +            one or more
   //   |            logical OR
   //     \s           find White Space, space, TAB and Carriage Returns
   //     $            at end of string
   //   /            close search
   //   g            global search

   return s.replace(/^\s+|\s+$/g, "");
}

function retornaFechamentoOS (http , sinal, excluir, lancar) {
    if (http.readyState == 4) {
        if (http.status == 200) {
            results = http.responseText.split(";");
            if (typeof (results[0]) != 'undefined') {
                if (_trim(results[0]) == 'ok') {
                    alert ('OS <? echo("fechada.com.sucesso") ?>');

                    if (sinal != undefined) {
                        sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
                        sinal.src='/assist/imagens/pixel.gif';
                    }

                    if (excluir != undefined) {
                        excluir.src='/assist/imagens/pixel.gif';
                    }

                    if (lancar != undefined) {
                        lancar.src='/assist/imagens/pixel.gif';
                    }

                    return true;
                }else{


                    if (http.responseText.indexOf ('de-obra para instala') > 0) {
                        alert ('<? echo("esta.os.nao.tem.mao-de-obra.para.instalacao") ?>');
                    }else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
                        alert ('<? echo("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao") ?>');
                    }else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
                        alert ('<? echo("esta.os.nao.tem.mao-de-obra.para.este.atendimento") ?>');
                    }else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
                        alert ('<? echo("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens") ?>');
                    }else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
                        alert ('<? echo("type.informado.para.o.produto.nao.e.valido") ?>');
                    }else if (http.responseText.indexOf ('OS com peças pendentes') > 0) {
                        alert ('<? echo("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os") ?>');
                    }else if(http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem") ?>');
                    }else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.kilometragem.recusada") ?>');
                    }else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem") ?>');
                    }else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada') > 0){
                        alert ('<? echo("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada") ?>');
                    }else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS') > 0){
                        alert ('<? echo("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens") ?>');
                    }else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO') > 0){
                        alert ('<? echo("por.favor.informar.o.conserto.do.produto.na.tela.consertado") ?>');
                    }else if (http.responseText.indexOf ('pois pedido foi faturado a menos de sete dias') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.faturamento.menos.sete.dias") ?>');
                    }else if (http.responseText.indexOf ('pois pedido não foi faturado') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada.nao.possui.faturamento") ?>');
                    }else if (http.responseText.indexOf ('pois não há pedido gerado') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada.nao.possui.pedido") ?>');
                    } else if (http.responseText.indexOf ('preencher o Check List') > 0) {
                        alert ('Para finalizar a OS é preciso preencher o Check List');
                    } else {
                        alert ('<? echo("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens") ?>');
                    }
                }
            }else{
                alert ('<? echo("fechamento.nao.processado") ?>');
            }
        }
    }
}

$(function () {

    $("#seleciona_todas_os_excluida").change(function() {
        if ($(this).is(":checked")) {
            $("input[type=checkbox][name='exclui_os[]']").each(function() {
                $(this)[0].checked = true;
            });
        } else {
            $("input[type=checkbox][name='exclui_os[]']").each(function() {
                $(this)[0].checked = false;
            });
        }
    });

    $("#button_exclui_os").click(function() {
        var motivo = $.trim($("#motivo_exclui_os").val());

        if (motivo.length == 0) {
            alert("Informe o motivo para excluir");
        } else {
            $("#form_exclui_os").submit();
        }
    });

    $("img[name^=fechar_os_30_dias_]").click(function () {
        if (confirm("Deseja realmente fechar a OS ?")) {
            var os = $(this).attr("rel");
            var i  = $(this).next("input[name=i]").val();

            window.open("fechar_os_30_dias.php?os="+os+"&posicao="+i, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        }
    });
});

function fecha_os_30_dias (os, i, motivo, sem_pagamento) {
    var date = new Date();

    var url = "<?=$_SERVER['PHP_SELF']?>?fechar="+os+"&motivo="+motivo+"&dt="+date;

    if (sem_pagamento != undefined && sem_pagamento == true) {
        url += "&sem_pagamento=true";
    }

    http.open("GET", url, true);
    http.onreadystatechange = function () {
        if (retornaFechamentoOS(http) == true) {
            $("img[name=fechar_os_30_dias_"+i+"]").remove();
        }
    };
    http.send(null);
}

function fechaOS (os , sinal , excluir , lancar ) {
    var curDateTime = new Date();
    url = "<?= $PHP_SELF ?>?fechar=" + escape(os) + '&dt='+curDateTime;
    http.open("GET", url , true);
    http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
    http.send(null);
}

function selecionarTudo(){
    $('input[@rel=imprimir]').each( function (){
        this.checked = !this.checked;
    });
}

function imprimirSelecionados(){
    var qtde_selecionados = 0;
    var linhas_seleciondas = "";
    $('input[@rel=imprimir]:checked').each( function (){
        if (this.checked){
            linhas_seleciondas = this.value+", "+linhas_seleciondas;
            qtde_selecionados++;
        }
    });

    if (qtde_selecionados>0){
        janela = window.open('os_print_selecao.php?lista_os='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
    }
}

function aprovaOrcamento(sua_os, num_os,opcao){

    if(confirm("Deseja "+opcao+" a OS : "+sua_os)){
        $.post('../admin/ajax_aprova_orcamento.php',{os : num_os, op : opcao},
            function (resposta){
                if(resposta === "OK"){
                    if(opcao=="Aprovar"){
                        $("#st_ch_"+sua_os).css('background','#3BAD48');
                        alert("Orçamento aprovado com sucesso");
                        //$('#aprovar_'+num_os).parent().parent().css('background','#33CC00');
                    }else{
                        //$('#reprovar_'+num_os).parent().parent().css('background','#C94040');
                        $("#st_ch_"+sua_os).css('background','#6E54FF');
                        alert("Orçamento Reprovado com sucesso");
                    }
                    $('#aprovar_'+num_os).remove();
                    $('#reprovar_'+num_os).remove();
                }else{
                    alert(resposta);
                }
        });
    }
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados("codigo_posto",codigo_posto);
        gravaDados("posto_nome",nome);
        <?if ($login_fabrica == 19 || $login_fabrica == 10)
        {?>
        gravaDados("codigo_posto_off",codigo_posto);
        gravaDados("posto_nome_off",nome);
        <?}?>
}
function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao){
        gravaDados("produto_referencia",referencia);
        gravaDados("produto_descricao",descricao);
}

</script>
<?
#-------------- Obriga a digitação de alguns critérios ---------------
#-------------- TULIO 26/02/2007 - Nao mudar sem me avisar -----------
/*
if (strlen ($os_off) == 0 AND
    strlen ($sua_os) == 0 AND
    strlen ($serie)  == 0 AND
    strlen ($nf_compra) == 0 AND
    strlen ($consumidor_cpf) == 0 AND
    strlen ($chk1) == 0 AND
    strlen ($chk2) == 0 AND
    strlen ($chk3) == 0 AND
    strlen ($chk4) == 0 AND
    strlen ($chk5) == 0 AND
    strlen ($mes) == 0 AND
    strlen ($ano) == 0 AND
    strlen ($consumidor_nome) == 0 AND
    strlen ($posto_codigo) == 0 AND
    strlen ($posto_nome) == 0 AND
    strlen ($produto_referencia) == 0 AND
    strlen($rg_produto) == 0 AND
    strlen($rg_produto_os) == 0 AND
    strlen($os_posto) == 0 AND (strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0)) {
        $msg = "Necessário especificar mais campos para pesquisaa";
}
*/
#--------------------------------------------------------------------
$pre_os = $_POST['pre_os'];

if (strlen($pre_os)>0) {
    if(strlen($btn_acao_pre_os) == 0 or empty($btn_acao_pre_os)) {
        $msg = " Para consultar por número de atendimento, favor clicar em Pesquisar Pré-OS" ;
    }
}

if (isset($_POST["acao_exclui_os"]) && strlen($msg_erro_excluir) > 0) {
    echo "<div class='msg_erro' style='width: 700px; margin: 0 auto;' >{$msg_erro_excluir}</div>";
}

if (isset($_POST["acao_exclui_os"]) && count($msg_ok_excluir) > 0) {
    echo "<br /><div class='msg_sucesso' style='width: 700px; margin: 0 auto; font: bold 16px Arial; color: #FFF; text-align: center; background-color: #438900;' >OS(s) excluidas com sucesso: ".implode(", ", $msg_ok_excluir)."</div>";
}

if ($login_fabrica == 35) {
    if (!empty($_POST['os_posto']) and empty($_POST['codigo_posto'])) {
        $msg.= 'Por favor informe o Posto.<br/>';
    }
}

if(strlen($msg)>0){
    echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
    echo "<tr>";
    echo "<td  class='msg_erro' align='left'> $msg</td>";
    echo "</tr>";
    echo "</table>";
}

if (((strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) AND strlen($msg) == 0)OR strlen($btn_acao_pre_os) > 0) {
    $pre_os = $_POST['pre_os'];

    if (strlen($pre_os)>0) {
        $sql_pre_os = " AND tbl_hd_chamado.hd_chamado = $pre_os";
    }

    if (strlen($btn_acao_pre_os) > 0) {

        // Habilitar Filtros para consulta de Pré OS HD2535001
        $dataInicialPreOS = $_POST['data_inicial'];
        if (strlen($dataInicialPreOS) == 0) $dataInicialPreOS = $_GET['data_inicial'];
        $dataFinalPreOS = $_POST['data_final'];
        if (strlen($dataFinalPreOS) == 0) $dataFinalPreOS = $_GET['data_final'];
        $seriePreOS = $_POST['serie'];
        $NFCompraPreOS = $_POST['nf_compra'];
        $consumidorCPFPreOS = $_POST['consumidor_cpf'];
        $consumidorNomePreOS = $_POST['consumidor_nome'];
        $codPostoPreOS = $_POST['codigo_posto'];
        $nomePostoPreOS = $_POST['posto_nome'];
        $produtoRefPreOS = $_POST['produto_referencia'];
        $produtoDescPreOS = $_POST['produto_descricao'];

        $sqlFiltrosPreOS = '';

        if (in_array($login_fabrica, array(11)) && (strlen($dataInicialPreOS) > 0 && strlen($dataFinalPreOS) > 0)) {

            if(!empty($dataInicialPreOS) OR !empty($dataFinalPreOS)){
                list($di, $mi, $yi) = explode("/", $dataInicialPreOS);
                if(!checkdate($mi,$di,$yi))
                    $msg = "Data inicial inválida";

                list($df, $mf, $yf) = explode("/", $dataFinalPreOS);
                if(!checkdate($mf,$df,$yf))
                    $msg = "Data final inválida";

                if(strlen($msg)==0){
                    $auxDataInicialPreOS = "$yi-$mi-$di";
                    $auxDataFinalPreOS = "$yf-$mf-$df";

                    if(strtotime($auxDataInicialPreOS) < strtotime($auxDataFinalPreOS)) {
                        $msg = "Data inicial maior do que a data final";
                    }
                }
            }

            $sqlFiltrosPreOS = " AND tbl_hd_chamado.data BETWEEN to_date('".$auxDataInicialPreOS." 00:00:00', 'YYYY-MM-DD HH24:MI:SS') AND to_date('".$auxDataFinalPreOS." 23:59:59', 'YYYY-MM-DD HH24:MI:SS')";
        }

        if (in_array($login_fabrica, array(11)) && strlen($seriePreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_hd_chamado_extra.serie = '".$seriePreOS."'";
        }

        if (in_array($login_fabrica, array(11)) && strlen($NFCompraPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_hd_chamado_extra.nota_fiscal = '".$NFCompraPreOS."'";
        }

        if (in_array($login_fabrica, array(11)) && strlen($consumidorCPFPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_hd_chamado_extra.cpf = '".$consumidorCPFPreOS."'";
        }

        if (in_array($login_fabrica, array(11)) && strlen($consumidorNomePreOS) > 0) {
            $sqlFiltrosPreOS .= " AND upper(tbl_hd_chamado_extra.nome) LIKE upper('%".$consumidorNomePreOS."%')";
        }

        if (in_array($login_fabrica, array(11)) && strlen($codPostoPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_posto_fabrica.codigo_posto = '".$codPostoPreOS."'";
        }

        if (in_array($login_fabrica, array(11)) && strlen($nomePostoPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND upper(tbl_posto.nome) LIKE upper('".$nomePostoPreOS."')";
        }

        if (in_array($login_fabrica, array(11)) && strlen($produtoRefPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_produto.referencia = '".$produtoRefPreOS."'";
        }

        if (in_array($login_fabrica, array(11)) && strlen($produtoDescPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND upper(tbl_produto.descricao) LIKE upper('".$produtoDescPreOS."')";
        }
        // Fim - Habilitar Filtros para consulta de Pré OS HD2535001

        if (!in_array($login_fabrica,array(30,52,96))) {
            if(strlen($cook_cliente_admin)>0){
                $cond_cliente_admin = " AND tbl_hd_chamado.cliente_admin = $cook_cliente_admin ";
            }

            $sqlinf = "SELECT hd_chamado, '' as sua_os, serie, nota_fiscal    ,
            TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data               ,
            TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY')            AS data_nf           ,
            tbl_hd_chamado_extra.posto                                        ,
            tbl_posto_fabrica.codigo_posto                                    ,
            tbl_posto_fabrica.credenciamento                                  ,
            tbl_posto.nome                              AS posto_nome         ,
            tbl_hd_chamado_extra.fone as consumidor_fone                      ,
            tbl_hd_chamado_extra.nome                                         ,
            tbl_hd_chamado_extra.array_campos_adicionais                      ,
            tbl_marca.nome as marca_nome                                      ,
            tbl_produto.referencia                                            ,
            tbl_produto.descricao
            FROM tbl_hd_chamado_extra
            JOIN tbl_hd_chamado using(hd_chamado)
            LEFT JOIN tbl_produto on tbl_hd_chamado_extra.produto = tbl_produto.produto
            LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
            LEFT JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_hd_chamado_extra.posto
            LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_hd_chamado.fabrica = $login_fabrica
            $cond_cliente_admin
            $sql_pre_os
            $sqlFiltrosPreOS
            AND tbl_hd_chamado_extra.abre_os = 't'
            AND tbl_hd_chamado.status != 'Resolvido'
            AND tbl_hd_chamado_extra.os is null";

        } else {
            if ($login_fabrica == 52) {
                $campo_serie = " , tbl_numero_serie.ordem as ordem_ativo ";
                $join_serie = " left join tbl_numero_serie ON tbl_hd_chamado_item.produto = tbl_numero_serie.produto and tbl_hd_chamado_item.serie = tbl_numero_serie.serie and tbl_numero_serie.fabrica = $login_fabrica";
            }
            $sqlinf = "SELECT
                    tbl_hd_chamado.hd_chamado,
                    tbl_hd_chamado_item.hd_chamado_item,
                    '' as sua_os                                                           ,
                    tbl_hd_chamado_item.serie                                              ,
                    tbl_hd_chamado_extra.nota_fiscal                                                            ,
                    TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY')            AS data_nf           ,
                    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')            AS data           ,
                    TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD HH24:MI:SS') AS dt_hr_abertura ,
                    tbl_posto_fabrica.codigo_posto                                         ,
                    tbl_posto_fabrica.credenciamento                                       ,
                    tbl_posto.nome                              AS posto_nome              ,
                    tbl_hd_chamado_extra.fone as consumidor_fone                           ,
                    tbl_hd_chamado_extra.nome                                              ,
                    tbl_hd_chamado_extra.tipo_atendimento                                  ,
                    tbl_marca.nome as marca_nome                                           ,
                    tbl_produto.referencia, tbl_produto.descricao
                    $campo_serie
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra using(hd_chamado)
                    JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado_item.produto is not null
                    LEFT JOIN tbl_produto on (tbl_hd_chamado_item.produto = tbl_produto.produto or tbl_hd_chamado_extra.produto = tbl_produto.produto)
                    LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
                    JOIN      tbl_posto         ON  tbl_posto.posto         = tbl_hd_chamado_extra.posto
                    LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    $join_serie
                    WHERE tbl_hd_chamado.fabrica = $login_fabrica
                    AND tbl_hd_chamado_extra.abre_os = 't'
                    ".(($login_fabrica != 96) ? "AND tbl_hd_chamado.status != 'Resolvido'" : "")."
                    $sql_pre_os
                    AND tbl_hd_chamado_item.os is null";

            if ($login_fabrica == 30) {
                if ($cook_cliente_admin_master == 't') {//ADMIN MASTER vê de toda fabrica
                    if(strlen($cook_cliente_admin)>0){
                        $sqlinf .= " AND tbl_hd_chamado.cliente_admin = $cook_cliente_admin ";
                    }
                } else {//ADMIN vê apenas o que ele cadastrou
                    if(strlen($cook_admin)>0){
                        $sqlinf .= " AND tbl_hd_chamado.admin = $cook_admin ";
                    }
                    if(strlen($cook_cliente_admin)>0){
                        $sqlinf .= " AND tbl_hd_chamado.cliente_admin = $cook_cliente_admin ";
                    }
                }
            } else {
                if(strlen($cook_cliente_admin)>0){
                    $sqlinf .= " AND tbl_hd_chamado.cliente_admin = $cook_cliente_admin ";
                }
            }

        }
          //echo nl2br($sqlinf);
        $res = pg_query ($con,$sqlinf);

        ##### PAGINAÇÃO - INÍCIO #####
        $sqlCount  = "SELECT count(*) FROM (";
        $sqlCount .= $sqlinf;
        $sqlCount .= ") AS count";

        require "_class_paginacao.php";

        // definicoes de variaveis
        $max_links = 11;                // máximo de links à serem exibidos
        $max_res   = 50;                // máximo de resultados à serem exibidos por tela ou pagina
        $mult_pag  = new Mult_Pag();    // cria um novo objeto navbar
        $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

        #echo "<BR>".nl2br($sql)."<BR>";exit;
        $res = $mult_pag->executar($sqlinf, $sqlCount, $con, "otimizada", "pgsql");

        ##### PAGINAÇÃO - FIM #####

    } else {

        $join_especifico = "";
        $especifica_mais_1 = "1=1";
        $especifica_mais_2 = "1=1";

        if (strlen ($produto_referencia) > 0) {
            $sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";

            $resX = pg_query ($con,$sqlX);
            if (pg_num_rows ($resX) > 0){
                $produto = pg_fetch_result ($resX,0,0);
                $especifica_mais_1 = "tbl_os.produto = $produto";
            }
        }

        if (strlen ($codigo_posto) > 0) {
            $sqlX = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND upper(codigo_posto) = upper('$codigo_posto')";
            $resX = pg_query ($con,$sqlX);
            if (pg_num_rows($resX) > 0) {
                $posto = pg_fetch_result ($resX,0,0);
                $especifica_mais_2 = "tbl_os.posto = $posto";
            }
        }

        if($login_fabrica ==50 AND $tipo_os =='OS_COM_TROCA'){ // HD 48198
            $join_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os ";
        }

        if($login_fabrica ==45 AND ($tipo_os =='TROCA' OR $tipo_os == 'RESSARCIMENTO')){ //HD 62394 waldir
                $join_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os ";
                $join_troca .= ($tipo_os =='TROCA') ? " AND tbl_os_troca.ressarcimento IS FALSE ":" and tbl_os_troca.ressarcimento ";
        }

        if ($login_fabrica == 45){
            if($tipo_os =='RESOLVIDOS'){
                $join_troca = "
                            JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                            join tbl_faturamento_item using(pedido,peca)
                ";
            }

            if($tipo_os =='PENDENTES'){
                $join_troca = "
                            JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.ressarcimento IS FALSE
                        LEFT join tbl_faturamento_item using(pedido,peca)
                ";
                $where_troca = " AND tbl_faturamento_item.faturamento_item isnull ";
            }

        }
        if($login_fabrica==7){
            $HI = "00:00:00";
            $HF = "23:59:59";
        }

        if (strlen($consumidor_revenda_pesquisa)) {
            $condicao_consumidor_revenda = " AND consumidor_revenda='$consumidor_revenda_pesquisa'";
        }

        if (strlen($os_off) > 0) {
            $condicao_os_off = " AND (tbl_os.sua_os_offline = '$os_off') ";
        }

        if (strlen($serie) > 0) {
            $condicao_serie = " AND tbl_os.serie = '$serie'";
            if($login_fabrica == 94) {
                $condicao_serie = " AND lpad(tbl_os.serie, 12, '0') = lpad('$serie', 12, '0') ";
            }
        }

        if (strlen($nf_compra) > 0) {
            if($login_fabrica == 1){
                $condicao_nf_compra = " AND tbl_os.nota_fiscal ILIKE '%$nf_compra%'";
            }else{
                $condicao_nf_compra = " AND tbl_os.nota_fiscal = '$nf_compra'";
            }
        }

        if (strlen($consumidor_cpf) > 0) {
            $condicao_consumidor_cpf = " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
        }

        if (strlen($status_checkpoint) > 0) {

            if($status_checkpoint == 10){
                $condicao_status_checkpoint = " AND tbl_os.hd_chamado isnull ";
            }else{
                $condicao_status_checkpoint = " AND tbl_os.status_checkpoint = $status_checkpoint";
            }

        }

        if(strlen($data_inicial > 0 ) && strlen($data_final > 0 )){
            if($login_fabrica == 96){
                $codicao_data_temp = " AND   tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            }else{
                $codicao_data_temp = " AND   tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            }
        }

        if (strlen($os_aberta) > 0) {
            $condicao_os_temp = " AND tbl_os.os_fechada IS FALSE
            AND tbl_os.excluida IS NOT TRUE";
        }

        if (strlen($consumidor_nome) > 0) {
            $condicao_os_consumidor = " AND tbl_os.consumidor_nome = '$consumidor_nome'";
        }

        $condicao_sua_os = "1 = 1";

        if(strlen($sua_os) > 0){
            //HD 683858 - inicio - Não estava pegando o $xsua_os qndo fazia a consulta da temp abaixo. então copiei este trecho da linha 1659 para poder fazer a consulta qndo passar a "sua_os"
            if ($login_fabrica == 1) {
                $pos = strpos($sua_os, "-");
                if ($pos === false) {
                    //hd 47506
                    if(strlen ($sua_os) > 11){
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    } elseif(strlen ($sua_os) > 10) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-6);
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    }else{
                        $pos = strlen($sua_os);
                    }
                }else{
                    //hd 47506
                    if(strlen (substr($sua_os,0,$pos)) > 11){#47506
                        $pos = $pos - 7;
                    } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                        $pos = $pos - 6;
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = $pos - 5;
                    }
                }
                if(strlen ($sua_os) > 9) {
                    $xsua_os = substr($sua_os, $pos,strlen($sua_os));
                    $codigo_posto = substr($sua_os,0,5);
                    $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
                    $res = pg_exec($con,$sqlPosto);
                    $xposto =  pg_result($res,0,posto) ;
                    $condicao_sua_os .= " AND tbl_os.posto = $xposto ";
                }
            }
            //HD 683858 - fim

            $pos = strpos($sua_os, "-");
            if ($pos === false && ($login_fabrica != 121 && $login_fabrica != 137)) {
                if(!ctype_digit($sua_os)){
                    $condicao_sua_os .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    //hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
                    //HD 683858 - acrescentado OR "tbl_os.os = $sua_os"

                    if($login_fabrica == 1){
                        $condicao_sua_os .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os = '$xsua_os' OR tbl_os.os = $sua_os) ";
                    }else{
                        $condicao_sua_os .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os  = '$sua_os')";
                    }
                }

            }else{
                $conteudo = explode("-", $sua_os);
                $os_numero    = $conteudo[0];
                $os_sequencia = $conteudo[1];
                if(!ctype_digit($os_sequencia) && $login_fabrica != 121 && $login_fabrica != 137){
                    $condicao_sua_os .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    if($login_fabrica ==1) { // HD 51334
                        $sua_os2 = $sua_os;
                        $sua_os = "000000" . trim ($sua_os);
                        if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
                        }elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){#46900
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
                        }else{
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                        }
                        $sua_os = strtoupper ($sua_os);

                        $condicao_sua_os .= "   AND (
                                        tbl_os.sua_os = '$sua_os' OR
                                        tbl_os.sua_os = '0$sua_os' OR
                                        tbl_os.sua_os = '00$sua_os' OR
                                        tbl_os.sua_os = '000$sua_os' OR
                                        tbl_os.sua_os = '0000$sua_os' OR
                                        tbl_os.sua_os = '00000$sua_os' OR
                                        tbl_os.sua_os = '000000$sua_os' OR
                                        tbl_os.sua_os = '0000000$sua_os' OR
                                        tbl_os.sua_os = '00000000$sua_os' OR
                                        tbl_os.sua_os = substr('$sua_os2',6,length('$sua_os2')) OR
                                        tbl_os.sua_os = substr('$sua_os2',7,length('$sua_os2'))     ";
                        /* hd 4111 */
                        for ($i=1;$i<=40;$i++) {
                            $condicao_sua_os .= "OR tbl_os.sua_os = '$sua_os-$i' ";
                        }
                        $condicao_sua_os .= " OR 1=2) ";
                    }else{
                        if($login_fabrica == 121 OR $login_fabrica == 137){
                            $condicao_sua_os .= " AND tbl_os.sua_os like '$sua_os%' ";
                        }else{
                            $condicao_sua_os .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                        }
                    }
                }
            }
        }

        if(isset($novaTelaOs)){
            $column_serie = "tbl_os_produto.serie,";
            $join_produto = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto ";
        }else{
            $join_produto = " LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
            $column_serie = "tbl_os.serie,";
        }

    if (in_array($login_fabrica, array(138, 145))) {
        $distinct_os = "DISTINCT ON(tbl_os.os)";
    }

        if(strlen($msg) == 0) {
            if ($login_fabrica == 45){
                if ($tipo_os == 'INTERACAO'){
                    $sqlTP = "
                            select inte.os

                            INTO TEMP tmp_consulta_$login_admin
                            from tbl_os

                            join tbl_os_interacao inte on tbl_os.os = inte.os and os_interacao in (select max(os_interacao) from tbl_os_interacao where tbl_os_interacao.fabrica = 45 and tbl_os.os = tbl_os_interacao.os )

                            $join_troca

                            $conds_sql_ativo

                            where tbl_os.fabrica = 45
                            and inte.admin isnull

                            AND   $especifica_mais_1
                            AND   $especifica_mais_2
                            AND   $condicao_sua_os
                            $condicao_os_temp
                            $condicao_os_consumidor
                            $codicao_data_temp
                            $condicao_consumidor_revenda
                            $condicao_os_off
                            $condicao_tecnico
                            $condicao_serie
                            $condicao_nf_compra
                            $condicao_consumidor_cpf
                            $condicao_status_checkpoint
                            $conds_sql
                            $where_troca

                    ";
                }else{
                    $sqlTP = "
                            SELECT tbl_os.os
                            INTO TEMP tmp_consulta_$login_admin
                            FROM tbl_os

                            $join_troca

                            $conds_sql_ativo

                            WHERE tbl_os.fabrica = $login_fabrica
                            AND   $especifica_mais_1
                            AND   $especifica_mais_2
                            AND   $condicao_sua_os
                            $condicao_os_temp
                            $condicao_os_consumidor
                            $codicao_data_temp
                            $condicao_consumidor_revenda
                            $condicao_os_off
                            $condicao_tecnico
                            $condicao_serie
                            $condicao_nf_compra
                            $condicao_consumidor_cpf
                            $condicao_status_checkpoint
                            $conds_sql
                            $where_troca ";
                            // echo nl2br($sqlTP);
                }
            }else{
                $sqlTP = "
                    SELECT tbl_os.os
                    INTO TEMP tmp_consulta_$login_admin
                    FROM tbl_os
                    LEFT JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
                    LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                    $join_troca

                    $conds_sql_ativo

                    WHERE tbl_os.fabrica = $login_fabrica
                    AND   $especifica_mais_1
                    AND   $especifica_mais_2
                    AND   $condicao_sua_os
                    $condicao_os_temp
                    $condicao_os_consumidor
                    $codicao_data_temp
                    $condicao_consumidor_revenda
                    $condicao_os_off
                    $condicao_tecnico
                    $condicao_serie
                    $condicao_nf_compra
                    $condicao_consumidor_cpf
                    $condicao_status_checkpoint
                    $conds_sql
                    $where_troca ";
                    //echo nl2br($sqlTP);exit;
            }
            #echo nl2br($sqlTP);exit;
            if($login_fabrica == 45 and $tipo_os == 'RESSARCIMENTO'){
                $sqlTP .=" AND tbl_os_troca.ressarcimento = 't'";
            }
            if(empty($sua_os) and empty($condicao_serie) and empty($condicao_consumidor_cpf) and empty($condicao_os_consumidor)){
                $data_inicio_explode = explode("-", $data_inicio_consulta);
                $data_fim_explode = explode("-", $data_fim_consulta);
                $data_resultado = $data_fim_explode[2]-$data_inicio_explode[2];

                if(($data_fim_explode[2]>$data_inicio_explode[2] && $data_resultado>3) || $data_inicio_explode[1]!=$data_fim_explode[1]){
                    $sqlTP .= ";CREATE INDEX tmp_consulta_OS_$login_admin ON tmp_consulta_$login_admin(os)";
                }
            }

//     echo $sqlTP;exit;
            $resX = pg_query ($con,$sqlTP);
        }
        $join_especifico = "JOIN tmp_consulta_$login_admin oss ON tbl_os.os = oss.os ";

        if ($login_fabrica == 11) {
            if (strlen($rg_produto_os)>0) {
                $sql_rg_produto = " AND tbl_os.rg_produto = '$rg_produto_os' ";
            }
        }

        //HD 14927
        //if(in_array($login_fabrica,array(3,11,14,15,43,45,66,80))){
        if($mostra_data_conserto){
            $sql_data_conserto=" , to_char(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto ";
        }

        if ($login_fabrica == 145) {
            $pesquisa_satisfacao = $_POST["pesquisa_satisfacao"];

            switch ($pesquisa_satisfacao) {
                case "realizada":
                    $joinPesquisaSatisfacao = "
                        INNER JOIN tbl_resposta ON tbl_resposta.os = tbl_os.os
                        INNER JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = {$login_fabrica}
                    ";
                    break;

                case "nao_realizada":
                    $joinPesquisaSatisfacao = "
                        LEFT JOIN tbl_resposta ON tbl_resposta.os = tbl_os.os
                        LEFT JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = {$login_fabrica}
                    ";
                    $wherePesquisaSatisfacao = "
                        AND tbl_resposta.resposta IS NULL
                    ";
                    break;
            }
        }

        // OS não excluída
        $sql =  "SELECT
            $distinct_os
            tbl_os.os                                                         ,
                        tbl_os.sua_os                                                     ,
                        tbl_os.nota_fiscal                                                ,
                        tbl_os.os_numero                                                  ,
                        sua_os_offline                                                    ,
                        LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
                        TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
                        TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
                        TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
                        TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
                        to_char(tbl_os.data_nf,'DD/MM/YYYY')        as data_nf            ,
                        $column_serie
                        tbl_os.excluida                                                   ,
                        tbl_os.motivo_atraso                                              ,
                        tbl_os.tipo_os_cortesia                                           ,
                        tbl_os.consumidor_revenda                                         ,
                        tbl_os.consumidor_nome                                            ,
                        tbl_os.consumidor_fone                                            , ";

        if($login_fabrica == 72){
            $sql .= " tbl_os.defeito_reclamado_descricao,
                    tbl_solucao.descricao as solucao_os,
                    tbl_posto_fabrica.contato_estado,
                    tbl_posto_fabrica.contato_cidade,
                    tbl_defeito_constatado.descricao as defeito_constatado, ";
        }

        if($login_fabrica == 137){

            $sql .= "
                    tbl_os.revenda_cnpj AS revenda_cnpj,
            ";

        }

        if($login_fabrica == 30 OR $login_fabrica == 137){
                        $sql .= " tbl_os.consumidor_endereco                              ,
                                  tbl_os.consumidor_cidade                              ,
                                  tbl_os.consumidor_estado                              ,
                                  tbl_os.defeito_reclamado_descricao AS defeito_reclamado_os,
                                  tbl_defeito_constatado.descricao AS defeito_constatado ,";
        }

                    $sql .= " tbl_os.revenda_nome                                               ,
                        tbl_os.tipo_atendimento                                           ,
                        tbl_os.os_reincidente                      AS reincidencia        ,
                        tbl_os.os_posto                                                   ,
                        tbl_os.aparencia_produto                                          ,
                        tbl_os.tecnico_nome                                               ,
            tbl_os.rg_produto                                                 ,";
    if(in_array($login_fabrica,array(30,35,85,145))){
        $sql .= "tbl_hd_chamado_extra.hd_chamado,";
    }else{
        $sql .= "tbl_os.hd_chamado,";
    }
                $sql .= "
                        tbl_tipo_atendimento.descricao                                    ,
                        tbl_tipo_atendimento.grupo_atendimento,
                        tbl_posto_fabrica.codigo_posto                                    ,
                        tbl_posto_fabrica.contato_estado                                  ,
                        tbl_posto_fabrica.contato_cidade                                  ,
                        tbl_posto_fabrica.credenciamento                                  ,
                        tbl_posto.nome                              AS posto_nome         ,
                        tbl_posto.capital_interior                                        ,
                        tbl_posto.estado                                                  ,
                        tbl_os_extra.impressa                                             ,
                        tbl_os_extra.extrato                                              ,
                        tbl_os_extra.os_reincidente                                       ,
                        tbl_produto.referencia                      AS produto_referencia ,
                        tbl_produto.descricao                       AS produto_descricao  ,
                        tbl_produto.voltagem                        AS produto_voltagem   ,
                        tbl_os.status_checkpoint                                          ,
                        distrib.codigo_posto                        AS codigo_distrib     ,";
        if ($login_fabrica == '30') {
            $sem_listar_peca = $_POST['sem_listar_peca'];

            if($sem_listar_peca <> 1){ // HD-2415933
                $sql.= "
                    TO_CHAR(tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
                    tbl_pedido_item.pedido,
                    tbl_faturamento.nota_fiscal as nf_fat,
                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS nf_emissao,
                    tbl_peca.referencia as peca_referencia,
                    tbl_peca.descricao as peca_descricao, ";
            }
            $sql.= "
                tbl_cliente_admin.nome AS cliente_admin_nome,
                tbl_os_extra.termino_atendimento,
            ";
        }

        if($login_fabrica == 6){
            $sql.="
                tbl_os.consumidor_email,
                tbl_os.revenda_cnpj,
                tbl_os.revenda_nome,
                tbl_os.revenda_cnpj,
                tbl_os.obs,
                ";
        }
        if ($login_fabrica == 24) {
            $sql .= "
                    tbl_os.cancelada,
                    CASE WHEN tbl_os.data_abertura::date BETWEEN (current_date - interval '60 days')::date AND (current_date - interval '30 days')::date THEN 'true' AND tbl_os.finalizada IS NULL
                    END AS congelar ,
                    ";
        }

        if ($login_fabrica == 3 OR $login_fabrica == 86 or $multimarca == 't') {
                            $sql .= "tbl_marca.marca ,
                                     tbl_marca.nome as marca_nome,";
        }
        if ($login_fabrica == 52) {
                            $sql .= "tbl_os.marca,";
        }
        if($login_fabrica == 74) {
                            $sql .= "tbl_os_interacao.os_interacao, ";
                            $sql .= "tbl_os_interacao.atendido, ";
                            $sql .= "TO_CHAR(tbl_os_interacao.data_contato,'DD/MM/YYYY') AS data_contato, ";
        }
        if ($login_fabrica == 52) {
                            $sql .= "tbl_numero_serie.ordem AS ordem_ativo,";
        }
        if ($login_fabrica == 115 OR $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) {
                            $sql .= " tbl_os.qtde_km  AS valor_km,";
        }
        if ($login_fabrica == 45){
                            //SE O RESULTADO DA SUBQUERY TROUXER VAZIO É PORQUE NÃO É INTERACAO DO POSTO, LOGO NAO SERÁ ATRIBUIDO A COR DE LEGENDA CORRETA
                            $sql .= "
                                    (SELECT admin from tbl_os_interacao where tbl_os_interacao.os = tbl_os.os order by data desc limit 1) as campo_interacao,
                            ";
        }
        if($login_fabrica == 85){
            $sql .= "
                        tbl_hd_chamado_extra.array_campos_adicionais,
            ";
        }

            $sql .= " status_os_ultimo AS status_os
            $sql_data_conserto
                FROM      tbl_os
                $join_especifico";

        if($login_fabrica == 74) {
                $sql .=  " left join tbl_os_interacao ON tbl_os.os = tbl_os_interacao.os AND os_interacao in (select max(os_interacao) from tbl_os_interacao where tbl_os_interacao.fabrica = $login_fabrica and tbl_os.os = tbl_os_interacao.os )" . (strlen($os_atendida) > 0 ? " AND (tbl_os_interacao.atendido IS FALSE)" : "");

        }

        if($login_fabrica == 72){
            $sql .= "left join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado and tbl_os.fabrica = $login_fabrica";
            $sql .= "left join tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os and tbl_os.fabrica = $login_fabrica ";
        }

        if($login_fabrica==30 OR $login_fabrica == 137){
                $sql .= "LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado ";
            if($login_fabrica == 30){
                $sql .= "
                    LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_os.cliente_admin
                ";
            }
        }
        if($login_fabrica == 52) {
           $sql .="LEFT JOIN  tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto
                    AND tbl_os.serie = tbl_numero_serie.serie ";

            ## HD-2507504 ##
            if (strlen($pre_os)>0) {
                $sql .=" LEFT JOIN tbl_hd_chamado ON tbl_os.posto = tbl_hd_chamado.posto ";
            }
        }

            $sql .= " LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
                JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                $join_produto
                LEFT JOIN      tbl_linha       ON  tbl_produto.linha       = tbl_linha.linha
                LEFT JOIN      tbl_familia     ON  tbl_produto.familia     = tbl_familia.familia
                LEFT JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
                JOIN           tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica ";

            if($login_fabrica == 141){
                $sql .= " JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
                JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade";
            }
        if ($login_fabrica == '30') {
            $os_troca = $_POST['os_troca'];
            if($os_troca == 1){
                $sql.= "
                    JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
                ";
            }

            $sem_listar_peca = $_POST['sem_listar_peca'];

            if($sem_listar_peca <> 1){ // HD-2415933
                $sql.= " left JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                    LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    left join tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                    left join tbl_faturamento_item on tbl_pedido_item.pedido = tbl_faturamento_item.pedido and tbl_pedido_item.peca = tbl_faturamento_item.peca
                    left join tbl_faturamento using(faturamento)
                    LEFT JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca";
            }
        }

        if (strlen($os_situacao) > 0) {
            $sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
            if ($os_situacao == "PAGA")
                $sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
        }
        if ($login_fabrica == 3 OR $login_fabrica == 86 or $multimarca =='t') {
            $sql .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
    }

    if(in_array($login_fabrica,array(30,35,85,145))){
        $sql .= "
                LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
        ";
    }

        if((in_array($login_fabrica,array(87,94,115,116,117,120,141,144,145))) AND !empty($descricao_tipo_atendimento)){
            $sql2_cond_tipo_atendimento = " AND tbl_tipo_atendimento.tipo_atendimento = $descricao_tipo_atendimento ";
        }
        $sql .= "
                LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
                LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
                {$joinPesquisaSatisfacao}
                WHERE tbl_os.fabrica = $login_fabrica {$sql2_cond_tipo_atendimento}
                {$wherePesquisaSatisfacao}
                AND   $especifica_mais_2
                $cond_admin";
        if ($login_fabrica == 42 AND $_POST["entrega_tecnica"] == 't') {
            $sql .= " AND tbl_tipo_atendimento.entrega_tecnica IS TRUE ";
        }

        if(!in_array($login_fabrica,array(3,11,20,24,45))) {
            if(!in_array($login_fabrica,array(14,35,50))){
                $sql .=" AND   tbl_os.excluida IS NOT TRUE ";
            }
            $sql .=" AND  (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL)";
        }

        #HD 13940 - Para mostrar as OS recusadas
        if($login_fabrica==20) {
            $sql .=" AND (tbl_os.excluida IS NOT TRUE OR tbl_os_extra.status_os = 94 )
                     AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
        }

        if (strlen($linha) > 0) { // HD 72899
            $sql .= " AND tbl_linha.linha = $linha ";
        }

        if (strlen($familia) > 0) { // HD 72899
            $sql .= " AND tbl_familia.familia = $familia ";
        }

        if (!empty($condicao_tecnico) ) {
            $sql .= $condicao_tecnico;
        }
        if ($login_fabrica == 24) {
            $sql .= $cond_congelada;
        }


        if(strlen($consulta_cidade) > 0){
            $sql .= $cons_sql_cidade;
        }

        if(strlen($cliente_admin) > 0){
            $sql .= "
                AND tbl_cliente_admin.cliente_admin = $cliente_admin
            ";
        }

        if (strlen($idPosto) > 0) {
            $sql .= " AND (tbl_os.posto = '$idPosto' OR distrib.posto = '$idPosto')";
        }

        if (strlen($produto_referencia) > 0) {
            $sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
        }

        if (strlen($admin) > 0) {
            $sql .= " AND tbl_os.admin = '$admin' ";
        }
        if(in_array($login_fabrica,array(1,3,52,86)) or $multimarca == 't' ) {
            $sql .= " AND $cond_marca ";
        }

        if($login_fabrica == 7 ){
            $sql .= " AND $cond_natureza AND $cond_classificacao_os"; // HD 75762 para Filizola
        }

        if($login_fabrica == 137 && !empty($lote)){
            $sql .= " AND tbl_os.serie ilike '%{$lote}%' "; // HD 75762 para Filizola
        }

        if($login_fabrica == 45) {
            if(strlen($rg_produto)>0){
                $sql .= " AND tbl_os.os IN (SELECT os FROM tbl_produto_rg_item WHERE UPPER(rg) = '$rg_produto') ";
            }
        }
        ##tirou o ilike porque estava travando o banco 30/06/2010 o samuel que pediu para tirar
        if (strlen($os_posto) > 0) { // HD 72899
            $sql .= " AND tbl_os.os_posto = '$os_posto' ";
        }

        if (strlen($sua_os) > 0) {
            #A Black tem consulta separada(os_consulta_avancada.php).
            if ($login_fabrica == 1) {
                $pos = strpos($sua_os, "-");

                if ($pos === false) {
                    //hd 47506
                    if(strlen ($sua_os) > 11){
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    } elseif(strlen ($sua_os) > 10) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-6);
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    }else{
                        $pos = strlen($sua_os);
                    }
                }else{

                    //hd 47506
                    if(strlen (substr($sua_os,0,$pos)) > 11){#47506
                        $pos = $pos - 7;
                    } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                        $pos = $pos - 6;
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = $pos - 5;
                    }
                }
                if(strlen ($sua_os) > 9) {
                    $xsua_os = substr($sua_os, $pos,strlen($sua_os));
                    $codigo_posto = substr($sua_os,0,5);
                    $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
                    $res = pg_exec($con,$sqlPosto);
                    $xposto = pg_result($res,0,posto);
                    $sql .= " AND tbl_os.posto = $xposto ";
                }
            }
            $sua_os = strtoupper ($sua_os);
            $pos = strpos($sua_os, "-");
            if ($pos === false && $login_fabrica != 121 && $login_fabrica != 137) {
                if(!ctype_digit($sua_os)){
                    $sql .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    //hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
                    //HD 683858 - acrescentado OR "tbl_os.os = $sua_os"
                    if($login_fabrica ==1){
                        #$sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os like '%$xsua_os' )";
                        $sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os = '$xsua_os' or tbl_os.os = $sua_os )";
                    }else{
                        $sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os  = '$sua_os')";
                    }
                }


            }else{
                $conteudo = explode("-", $sua_os);
                $os_numero    = $conteudo[0];
                $os_sequencia = $conteudo[1];
                if(!ctype_digit($os_sequencia) && $login_fabrica != 121 && $login_fabrica != 137){
                    $sql .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    if($login_fabrica ==1) { // HD 51334
                        $sua_os2 = $sua_os;
                        $sua_os = "000000" . trim ($sua_os);
                        if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
                        }elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){#46900
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
                        }else{
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                        }
                        $sua_os = strtoupper ($sua_os);

                        $sql .= "   AND (
                                    tbl_os.sua_os = '$sua_os' OR
                                    tbl_os.sua_os = '0$sua_os' OR
                                    tbl_os.sua_os = '00$sua_os' OR
                                    tbl_os.sua_os = '000$sua_os' OR
                                    tbl_os.sua_os = '0000$sua_os' OR
                                    tbl_os.sua_os = '00000$sua_os' OR
                                    tbl_os.sua_os = '000000$sua_os' OR
                                    tbl_os.sua_os = '0000000$sua_os' OR
                                    tbl_os.sua_os = '00000000$sua_os' OR
                                    tbl_os.sua_os = substr('$sua_os2',6,length('$sua_os2')) OR
                                    tbl_os.sua_os = substr('$sua_os2',7,length('$sua_os2'))     ";
                        /* hd 4111 */
                        for ($i=1;$i<=40;$i++) {
                            $sql .= "OR tbl_os.sua_os = '$sua_os-$i' ";
                        }
                        $sql .= " OR 1=2) ";


                    }else{
                        if($login_fabrica == 121 OR $login_fabrica == 137){
                            $sql .= " AND tbl_os.sua_os like '$sua_os%' ";
                        }else{
                            $sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                        }
                    }
                }
            }
        }

        //HD 211825: Filtro por tipo de OS: Consumidor/Revenda
        if (strlen($consumidor_revenda_pesquisa)) {
            $sql .= " AND tbl_os.consumidor_revenda='$consumidor_revenda_pesquisa'";
        }

        if (strlen($os_off) > 0) {
            #$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%') ";
            $sql .= " AND (tbl_os.sua_os_offline = '$os_off') ";

        }

        if (strlen($serie) > 0) {
            if($login_fabrica == 94 ) {
                $sql .= " AND lpad(tbl_os.serie, 12, '0') = lpad('$serie', 12, '0') ";
            }else{
                $sql .= " AND tbl_os.serie = '$serie'";
            }
        }

        if (strlen($nf_compra) > 0) {

            if($login_fabrica == 1){
                $nf_compra = (int)$nf_compra;
                $sql .= " AND tbl_os.nota_fiscal ilike '%$nf_compra'";
            }else{
                $sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
            }
        }

        if (strlen($consumidor_nome) > 0) {
            $sql .= " AND tbl_os.consumidor_nome = '$consumidor_nome'";

        }

        if (strlen($consumidor_cpf) > 0) {
            $sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
        }

        if (strlen($os_aberta) > 0) {
            $sql .= " AND tbl_os.os_fechada IS FALSE
                      AND tbl_os.excluida IS NOT TRUE";
        }

        #HD 234532
        if (strlen($status_checkpoint) > 0) {
            if($status_checkpoint == 10){
                $sql .= " AND tbl_os.hd_chamado isnull";
            }else{
                $sql .= " AND tbl_os.status_checkpoint = $status_checkpoint";
            }
        }

        #HD 115630---------
        if($login_fabrica==35){
            if (strlen($os_finalizada) > 0) {
                $sql .= " AND tbl_os.os_fecha30da IS TRUE
                          AND tbl_os.excluida IS NOT TRUE";
            }
        }
        #------------------
        if ($os_situacao == "APROVADA") {
            $sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
        }
        if ($os_situacao == "PAGA") {
            $sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
        }

        if (strlen($revenda_cnpj) > 0) {
            //HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais
            $sql .= " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%' ";
            //$sql .= " AND tbl_os.revenda_cnpj = '$revenda_cnpj' ";
        }

        if (strlen($pais) > 0) {
            $sql .= " AND tbl_posto.pais ='$pais' ";
        }

        if ($login_fabrica == 11 ){
            $sql .= $sql_rg_produto ;
        }

        if($login_fabrica == 141){
            if(strlen($consulta_estado) > 0){
                $sql .= " AND tbl_cidade.estado IN ('$consulta_estado')";
            }
        }else{
            if(strlen($consulta_estado) > 0){
                $sql .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
            }
        }

        if ($login_fabrica == 45 AND strlen($regiao) > 0) {
            if ($regiao == 1) {
                $sql .= " AND tbl_posto_fabrica.contato_estado = 'SP'";
            }
            if ($regiao == 2) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('SC', 'RS', 'PR')";
            }
            if ($regiao == 3) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('RJ', 'ES', 'MG')";
            }
            if ($regiao == 4) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF', 'CE', 'RN')";
            }
            if ($regiao == 5) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('SE','AL', 'PE', 'PB', 'BA')";
            }
            if ($regiao == 6) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO', 'MA', 'PI')";
            }
            if ($regiao == 7) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF', 'CE', 'RN', 'TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO', 'MA', 'PI')";
            }

        }

        if ($login_fabrica == 80 AND strlen($regiao) > 0) {
            if ($regiao == 1) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('PE','PB')";
            }
            if ($regiao == 2) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('RJ','GO','MG','AC','AM','DF','ES','PI','MA','MS','MT','PA','PR','RO','RR','RS','SC','TO','AP')";
            }
            if ($regiao == 3) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('BA','SE','AL')";
            }
            if ($regiao == 4) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('CE','RN','SP')";
            }
        }

        if($login_fabrica == 50 AND strlen($tipo_os) >0) { // HD 48198
            if($tipo_os=='REINCIDENTE'){
                $sql .=" AND tbl_os.os_reincidente IS TRUE ";
            }elseif($tipo_os=='MAIS_CINCO_DIAS'){
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 5
                         AND CURRENT_DATE - tbl_os.data_abertura < 10
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            }elseif($tipo_os=='MAIS_DEZ_DIAS'){
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 10
                         AND CURRENT_DATE - tbl_os.data_abertura < 20
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            }elseif($tipo_os=='MAIS_VINTE_DIAS'){
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 20
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            }elseif($tipo_os == 'EXCLUIDAS'){
                $sql .=" AND tbl_os.excluida IS TRUE ";
            }
        }

        if ($login_fabrica == 45 AND strlen($tipo_os) > 0) { // HD 62394 waldir
            if ($tipo_os == 'REINCIDENTE') {
                $sql .=" AND tbl_os.os_reincidente IS TRUE ";
            } elseif($tipo_os == 'BOM') {
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura < 16
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            } elseif ($tipo_os == 'MEDIO') {
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 15
                         AND CURRENT_DATE - tbl_os.data_abertura < 26
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            } elseif ($tipo_os == 'RUIM') {
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 25
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            } elseif ($tipo_os == 'EXCLUIDA') {
                $sql .=" AND tbl_os.excluida IS TRUE ";
            }
        }

        if (in_array($login_fabrica,array(7,30,52,96)) and !empty($cook_cliente_admin)) {
            $sql .= " AND tbl_os.cliente_admin = $cook_cliente_admin ";
        }

        if($admin_consulta_os AND $login_fabrica == 19){
            $sql .= " AND tbl_os.tipo_atendimento = 20
                    AND tbl_os.os_fechada IS TRUE
                    AND tbl_os.excluida IS NOT TRUE";
        }

        if($login_fabrica == 52){ ## HD-2507504 ##
            if (strlen($pre_os)>0) {
                $sql_pre_os = " AND tbl_os.hd_chamado = $pre_os";

                $sql .= $sql_pre_os;
            }
        }

        //HD 393737 IBBL
        if($login_fabrica == 90){
            $sql .= $monta_sql;
        }
        $sql .= $conds_sql;

        if ($login_fabrica == 7){
            $sql .= " ORDER BY tbl_os.data_abertura ASC, LPAD(tbl_os.sua_os,20,'0') ASC ";
        } elseif ($login_fabrica == 45){
            $sql .= " ORDER BY tbl_os.data_abertura DESC ";
        }elseif ($login_fabrica == 30){
            $sql .= " ORDER BY tbl_os_extra.termino_atendimento";
        }else {
#           $sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC "; Sameul 02-07-2009
            if ($login_fabrica == 3 and $posto_ordenar == 'sim') {
                $sql .= " ORDER BY tbl_posto_fabrica.codigo_posto ";
            }else if($login_fabrica == 121 OR $login_fabrica == 137){
                $sql .= " ORDER BY tbl_os.os ASC ";
            } else {
                $sql .= " ORDER BY tbl_os.os DESC ";
            }

            if ($login_fabrica == '30') {
                $sql.= ', pedido ASC';
            }
        }

        $sqlT = str_replace ("\n"," ",$sql) ;
        $sqlT = str_replace ("\t"," ",$sqlT) ;

        $resT = @pg_query ($con,"/* QUERY -> $sqlT  */");
       // echo nl2br($sql);
        $resxls = pg_query($con,$sql);

        echo pg_last_error($con);

        flush();

        ##### PAGINAÇÃO - INÍCIO #####
        $sqlCount  = "SELECT count(*) FROM (";
        $sqlCount .= $sql;
        $sqlCount .= ") AS count";

        require "_class_paginacao.php";

        // definicoes de variaveis
        $max_links = 11;                // máximo de links à serem exibidos
        $max_res   = 50;                // máximo de resultados à serem exibidos por tela ou pagina
        $mult_pag  = new Mult_Pag();    // cria um novo objeto navbar
        $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

//         echo "<BR>".nl2br($sql)."<BR>";exit;
        $res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

        ##### PAGINAÇÃO - FIM #####
    }

    $resultados = pg_num_rows($res);

    $old_os = 0;

    if (pg_num_rows($res) > 0) {

        # HD 234532
        ##### LEGENDAS - INÍCIO - HD 234532 #####
        /*
         0 | Aberta Call-Center               | #D6D6D6
         1 | Aguardando Analise               | #FF8282
         2 | Aguardando Peças                 | #FAFF73
         3 | Aguardando Conserto              | #EF5CFF
         4 | Aguardando Retirada              | #9E8FFF
         9 | Finalizada                       | #8DFF70
         13| Pedido Cancelado                 | #EE9A00
        */

        #Se for Bosh Security modificar a condição para pegar outros status também.
        $condicao_status = ($login_fabrica == 96) ? '0,1,2,3,5,6,7,9' : '0,1,2,3,4,9';

        if (in_array($login_fabrica, array(51,81,114))) {
            $condicao_status = '0,1,2,3,4,8,9';
        }

        if ($login_fabrica == 141) {
            $condicao_status = '0,1,14,2,8,11,3,10,12,4,9';
        }

        if ($login_fabrica == 144) {
            $condicao_status = '0,1,14,2,8,11,3,10,4,9';
        }

        if($login_fabrica == 131){ // HD-2181938
          $condicao_status = '0,1,2,3,4,9,13';
        }

        if($login_fabrica == 3){
            $condicao_status = '0,1,2,3,4,9,10';
        }

        if (isset($novaTelaOs)) {
            $condicao_status = '0,1,2,8,3,4,9';
        }

        $sql_status   = "SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.")";
        $res_status   = pg_query($con, $sql_status);
        $total_status = pg_num_rows($res_status);
?>

            <table border='0' cellspacing='0' cellpadding='0' width='700px' style="font-size:11px;" align='center'>
                <tr>
                    <td style='text-align: left; '  valign='bottom'>
<?php
        if($login_fabrica == 96 AND strlen($btn_acao_pre_os) > 0){
                        //Retirar OS status para BOSCH HD - 669464
        }else{
?>
                        <div align='left' style='position:relative;left:25'>
                            <h4>Status das OS</h4>
                            <table border='0' cellspacing='0' cellpadding='0'>
<?php
            for($i=0;$i<$total_status;$i++){

                $id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
                $cor_status = pg_fetch_result($res_status,$i,'cor');
                $descricao_status = pg_fetch_result($res_status,$i,'descricao');

                #Array utilizado posteriormente para definir as cores dos status
                $array_cor_status[$id_status] = $cor_status;

                if ($login_fabrica <> 87 OR ($login_fabrica == 87 AND $id_status != 0)) {
?>
                                <tr height='18'>
                                    <td width='18' >
                                        <div class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</div>
                                    </td>
                                    <td align='left'>
                                        <font size='1'>
                                            <b>
                                                <!-- <a href=\"javascript: filtro('vermelho')\"> -->
                                                    <?php
                                                            if($login_fabrica == 96 AND $id_status == 3){
                                                                $descricao_status = "Em conserto";
                                                            }
                                                            echo $descricao_status;
                                                        ?>
                                                <!-- </a> -->
                                            </b>
                                        </font>
                                    </td>
                                </tr>
<?php
                }
            }
?>

                            </table>
                        </div>
<?php
        }
?>
                    </td>
                    <td style='text-align: left; '  valign='bottom'>
<?php
        ##### LEGENDAS - INÍCIO #####
        echo "<div align='left' style='margin: 0 auto;width:90%;'>";
        echo "<table border='0' cellspacing='0' cellpadding='0' align='center' width='400px;'>";

        if ($login_fabrica == 96 && strlen($btn_acao_pre_os) > 0) { //HD391024

            echo "<tr height='18'>";
                echo "<td width='18' bgcolor='#C94040' class='legenda_os_cor'></td>";
                echo "<td align='left' class='legenda_os_texto'>Fora de garantia</td>";
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

            echo "<tr height='18'>";
                echo "<td width='18' bgcolor='#FFFF66' class='legenda_os_cor'></td>";
                echo "<td align='left' class='legenda_os_texto'>Garantia</td>";
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

            echo "<tr height='18'>";
                echo "<td width='18' bgcolor='#33CC00' class='legenda_os_cor'>&nbsp;</td>";
                echo "<td align='left' class='legenda_os_texto'>Retorno de garantia</td>";
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

        } else {
            if ($excluida == "t") {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica != 1) {
                if ($login_fabrica == 87){
                    $cor = "#40E0D0";
                }else{
                    $cor = "#D7FFE1";
                }

                echo "<tr height='3'>";
                    echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>Reincidências</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            } else {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFC891' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 85) { #HD 284058

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#AEAEFF' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>Peça fora da garantia aprovada na intervenção da OS para gerar pedido</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 14) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#91C8FF' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>OSs abertas há mais de 3 dias sem data de fechamento</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>OSs abertas há mais de 5 dias sem data de fechamento</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            } else {

                if ($login_fabrica == 50) {

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#91C8FF' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'>OSs abertas há mais de 5 dias sem data de fechamento</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FF6633' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'>OSs abertas há mais de 10 dias sem data de fechamento</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'>OSs abertas há mais de 20 dias sem data de fechamento</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                } else {

                    if ($login_fabrica == 45) {

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#1e85c7' class='legenda_os_cor'></td>";
                            echo "<td align='left' class='legenda_os_texto'>BOM (OSs abertas até 15 dias sem data de fechamento)</td>";
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#FF6633' class='legenda_os_cor'></td>";
                            echo "<td align='left' class='legenda_os_texto'>MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)</td>";
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#9512cc' class='legenda_os_cor'></td>";
                            echo "<td align='left' class='legenda_os_texto'>RUIM (OSs abertas a mais de 25 dias sem data de fechamento)</td>";
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                    } else if ($login_fabrica == 43) {

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#FF0033' class='legenda_os_cor'></td>";
                            echo "<td align='left' class='legenda_os_texto'>OSs abertas há mais de 10 dias sem data de fechamento</td>";
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                    } else {

                        if ($login_fabrica == 87){
                            $cor = "#A4B3FF";
                        }else{
                            $cor = "#91C8FF";
                        }
                        echo "<tr height='3'>";
                            echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'></td>";
                            echo "<td align='left' nowrap class='legenda_os_texto'>OSs abertas há mais de ".(($login_fabrica == 91) ? "30" : "25" )." dias sem data de fechamento</td>";
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                    }

                }

                if ($login_fabrica == 35) {

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                }

            }

            if ($login_fabrica == 91 or $login_fabrica == 114) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFCCCC' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "OS com Intervenção da Fábrica. Aguardando Liberação";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if (in_array($login_fabrica, array(3,11,51,43,87,115,116,117,120,121,122,123,125))) {

                if ($login_fabrica == 87){
                    $cor = "#FFA5A4";
                }else {
                    $cor = "#FFCCCC";
                }

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='$cor' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "OS com Intervenção da Fábrica. Aguardando Liberação";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                if ($login_fabrica != 87) {

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FFFF99' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'> ";
                        echo "OS com Intervenção da Fábrica. Reparo na Fábrica";
                        echo "</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                }

                if ($login_fabrica == 87){
                    $cor = "#FEFFA4";
                }else{
                    $cor = "#00EAEA";
                }

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='$cor' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo "OS Liberada Pela Fábrica";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 3 OR $login_fabrica == 11 OR $login_fabrica == 45) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo " OS Cancelada";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CCCCFF' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "OS com Ressarcimento Financeiro";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 20) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CACACA' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>OS Reprovada pelo Promotor</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
            //HD 907550: Também Cobimex
            if ($fabrica_autoriza_troca_revenda) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#d89988' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo "Autorização de Devolução de Venda";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            //HD 163220 - Colocar legenda nas OSs com atendimento Procon/Jec (Jurídico) - tbl_hd_chamado.categoria='procon'
            if ($login_fabrica == 11) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#C29F6A' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo "OS com Atendimento Procon/Jec (Jurídico) no Call-Center";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 51) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CACACA' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>OS Recusada do extrato</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            echo "<tr height='3'>";
            if ($login_fabrica == 87){
                $cor = "#D2D2D2";
            }else{
                $cor = "#CC9900";
            }
            echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'></td>";
            echo "<td align='left' class='legenda_os_texto'>OS reincidente e aberta a mais de 25 dias </td>";
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

            if ($login_fabrica != 87) {
                echo "<tr height='3'>";
                    echo "<td width='55' bgcolor='#FFCC66' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "OS com Troca de Produto";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 30) {
?>
                <tr height='18'>
                    <td width='18' bgcolor='#33CC00' class='legenda_os_cor'></td>
                    <td align='left' class='legenda_os_texto'>OS com limite a mais de 72 horas </td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>

                <tr height='18'>
                    <td width='18' bgcolor='#FFFF66' class='legenda_os_cor'></td>
                    <td align='left' class='legenda_os_texto'>OS com limite a mais de 24 horas e menos de 72 horas</td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>

                <tr height='18'>
                    <td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>
                    <td align='left' class='legenda_os_texto'>OS com limite a menos de 24 horas</td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>
<?
            }

            if ($login_fabrica == 94) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='$cor' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo "OS com Intervenção da Fábrica. Aguardando Liberação";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($fabrica_autoriza_ressarcimento) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CCCCFF' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "Os com Ressarcimento";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 40) {#HD 284058

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#BFCDDB' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>OS com 3 ou mais peças</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 94) {#HD 785254

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='silver' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>OS foi Aberta automaticamente por causa de uma troca gerada</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 3) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CB82FF' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>OS com pendência de fotos</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#A4A4A4' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>OS com intervenção de display</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if (in_array($login_fabrica, array(141,144))) {
            ?>
                <tr height="18" >
                    <td width="18" bgcolor="#CB82FF" class="legenda_os_cor" >&nbsp;</td>
                    <td align="left" class="legenda_os_texto" >OS com troca de produto recusada</td>
                </tr>
                <tr height="3"><td colspan="2"></td></tr>
            <?php
            }

             if ($login_fabrica == 91) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CB82FF' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>OS recusada pela fábrica</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 45){
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#F98BB2' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>Os com Interação do Posto</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#56BB71' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>Os com Troca de Produtos - Resolvidos</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#EAEA1E' class='legenda_os_cor'>&nbsp;</td>";
                    echo "<td align='left' class='legenda_os_texto'>Os com Troca de Produtos - Pendentes</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }
        }

        echo "<tr height='3'><td colspan='2'></td></tr>";
        echo "</table>";
        echo "</div>";
        ##### LEGENDAS - FIM #####
?>
                    </td>
                </tr>
             </table>

<?php

        echo "<br>";

        if (strlen($btn_acao_pre_os) > 0 and $login_fabrica == 52 and pg_num_rows($res) > 0){
            flush();
            echo `rm /tmp/assist/relatorio-pre-os-$login_fabrica.xls`;
            $fp = fopen ("/tmp/assist/relatorio-pre-os-$login_fabrica.html","w");

            fputs ($fp,"<table border='1' align='center' cellspacing='5px' cellpadding='2px' width='950'>
                            <tr>
                                <th colspan='8' style='color: #373B57; background-color: #F1C913;'>Relatório de Pré-OS</th>
                            </tr>
                            <tr>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Nº Atendimento</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Nº Ativo</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Série</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Data Abertura</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Posto</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Consumidor</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Telefone</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Produto</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Marca</th>
                            </tr>");

            for ($i = 0; $i < pg_num_rows($res); $i++){
                $hd_chamado         = trim(pg_result($res, $i, "hd_chamado"));
                $numero_ativo_res   = trim(pg_result($res, $i, "ordem_ativo"));
                $serie              = trim(pg_result($res, $i, "serie"));
                $abertura           = trim(pg_result($res, $i, "data"));
                $posto_nome         = trim(pg_result($res, $i, "posto_nome"));
                $consumidor_nome    = trim(pg_result($res, $i, "nome"));
                $marca_logo         = trim(pg_result($res, $i, "marca"));
                if($login_fabrica == 85){
                    $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                    if(!empty($array_campos_adicionais)){
                        $campos_adicionais = json_decode($array_campos_adicionais);
                        if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                            $consumidor_nome = $campos_adicionais->nome_fantasia;
                        }
                    }
                }

                $consumidor_fone    = trim(pg_result($res, $i, "consumidor_fone"));
                $produto_referencia = trim(pg_result($res, $i, "referencia"));
                $produto_descricao  = trim(pg_result($res, $i, "descricao"));

                $cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

                if(!empty($marca_logo)) {
                    $sql_logo="select nome from  tbl_marca where marca = $marca_logo;";
                    $res_logo=pg_exec($con,$sql_logo);
                    $marca_logo_nome         = pg_fetch_result($res_logo, 0, 'nome');
                }
                fputs ($fp,"<tr style='text-align: left;'>
                                <td style='background-color: $cor;' nowrap>$hd_chamado</td>
                                <td style='background-color: $cor;' nowrap>$numero_ativo_res</td>
                                <td style='background-color: $cor;' nowrap>$serie</td>
                                <td style='background-color: $cor;' nowrap>$abertura</td>
                                <td style='background-color: $cor;' nowrap>$posto_nome</td>
                                <td style='background-color: $cor;' nowrap>$consumidor_nome</td>
                                <td style='background-color: $cor;' nowrap>$consumidor_fone</td>
                                <td style='background-color: $cor;' nowrap>$produto_referencia - $produto_descricao</td>
                                <td style='background-color: $cor;' nowrap>$marca_logo_nome</td>
                            </tr>");
            }

            $preos_total = pg_num_rows($res);
            fputs ($fp,"    <tr>
                                <th colspan='8' style='color: #373B57; background-color: #F1C913;'>Total de Pré-OS: $preos_total</th>
                            </tr>
                        </table>");
            fclose ($fp);

            $data = date("Y-m-d").".".date("H-i-s");

            if (strlen($login_cliente_admin) == 0){
                rename("/tmp/assist/relatorio-pre-os-$login_fabrica.html", "xls/relatorio-pre-os-$login_fabrica.$data.xls");

                echo "<br /> <a href='xls/relatorio-pre-os-$login_fabrica.$data.xls' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Gerar Arquivo Excel</a> <br />";
            }else{
                rename("/tmp/assist/relatorio-pre-os-$login_fabrica.html", "../admin/xls/relatorio-pre-os-$login_fabrica.$data.xls");

                echo "<br /> <a href='../admin/xls/relatorio-pre-os-$login_fabrica.$data.xls' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Gerar Arquivo Excel</a> <br />";
            }
        }

        if($login_fabrica == 30 ) {
            echo "<button onclick='javascript: escondeColuna()' id='esconde'>Esconder Colunas</button><br/><br/>";
        }
        //comentei porque estava deixado tr em branco e retirando a cor do status_os
        //$table_cor = ($login_fabrica == 74) ?' tablesorter ':'';
        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
            if ($i % 50 == 0) {
                echo "</table>";
                flush();

                echo "<table border='0' cellpadding='2' cellspacing='1' class='$table_cor tabela'  align='center' width='80%' id='content'>";
            }

            if ($i % 50 == 0) {

                // O código é mais longo, mas é mais fácil de entender e manter
                switch ($login_fabrica) {
                    case 7:
                    case 96:
                        $colspan = 5;
                        break;

                    case in_array($login_fabrica,array(1,14,20,24,50,66)):
                        $colspan = 6;
                        break;

                    case (in_array($login_fabrica, array(11,81,114,30, 122))):
                        $colspan = 4;
                        break;

                    default:
                        $colspan = 3;
                        break;
                }

                $titulo = (strlen($btn_acao_pre_os) > 0) ? "pre-os" : "os";

                echo "<thead>
                        <tr class='titulo_coluna' height='15'>";

                /* Titulo da Pré-OS */
                if($titulo == "pre-os"){

                    if ($login_fabrica == 3) {
                        echo "<td>CÓD POSTO</td>";
                    }

                    if (strlen($btn_acao_pre_os)==0) {



                        echo "<td>OS</td>";
                        if($login_fabrica == 74) {
                            echo "<td>Status de Atendimento OS</td>";
                            echo "<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>";
                        }

                    } else {

                        echo "<td>Nº Atendimento</td>";

                        if($login_fabrica == 74) {
                            echo "<td>Status de Atendimento OS</td>";
                            echo "<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>";
                        }
                    }

                    if (($login_fabrica == 52 or $login_fabrica == 30) and strlen($btn_acao_pre_os)==0) {
                        echo "<td>Nº Atendimento</td>";
                    }


                    if($login_fabrica == 52){
                        echo "<td>Número Ativo</td>";
                    }

                    echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<td>OS OFF LINE</td>" : "";

                    if($login_fabrica == 30){
                        echo "<td>OS Revendedor</td>";
                    }#HD 117540;

                    if(!in_array($login_fabrica,array(1,3,20,50,81,145))){
                        echo "<td>";
                        echo ($login_fabrica==35) ? "PO#" : "SÉRIE ";
                        echo "</td>";
                    }

                    echo "<td>AB</td>";

                    echo ($login_fabrica==11) ? "<td>DP</td>" : ""; // HD 74587

                    if ($mostra_data_conserto){
                        echo "<td><acronym title='Data de conserto do produto' style='cursor:help;'>DC</a></td>"; //HD 14927
                    }

                    if(!in_array($login_fabrica, array(3,139)))
                        echo "<td>DF</td>";
                    //echo "<td>FC</td>";

                    echo ($login_fabrica==115 or $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120 or in_array($login_fabrica, array(141,144))) ? "<td align='center'>Tipo de Atendimento</td>" : "";

                    if ($login_fabrica == 52){
                        echo "<td> Aberto acima de </td>";
                    }

                    if(!in_array($login_fabrica, array(3, 11)) && $login_fabrica != ($login_fabrica > 100)){
                        echo "<td>POSTO</td>";
                    }

                    if(in_array($login_fabrica,array(106,114,122,123,127))){
                        echo "<td>POSTO</td>";
                    }

                    echo "<td nowrap>NOME POSTO</td>";

                    if ($login_fabrica == 11) {
                        echo "<td nowrap>SITUAÇÃO POSTO</td>";
                    }

                    echo ($login_fabrica==2)  ? "<td>CONSUMIDOR/REVENDA</td>" : "<td rel='esconde_coluna'>CONSUMIDOR/REVENDA</td>";

                    if(in_array($login_fabrica, array(3,6,11,50,1,117,90,15,19,42,72,40,45,88,80,24,91,74,81,114,85,52,94,96,35,98,101,127,86,106,123,122,20,124))){
                        echo "<td >TELEFONE</td>";
                    }

                    if(!in_array($login_fabrica, array(3,6,11,50,1,117,90,15,19,42,72,40,45,88,80,24,91,74,81,114,85,52,94,96,35,98,101,127,86,106,123,122,20,124,142,143))){
                        echo "<td>NF</td>";
                    }
                    echo ($login_fabrica==3 OR $login_fabrica == 86 OR $login_fabrica == 52 or $multimarca =='t')  ? "<td>MARCA</td>" : "";
                    echo ($login_fabrica==80 )  ? "<td>DATA DE COMPRA</td>" : "";

                    echo ($login_fabrica==11) ? "<td>REFERÊNCIA</td>" : "<td rel='esconde_coluna'>PRODUTO</td>"; // hd 74587

                    echo ($login_fabrica == 145) ? "<td>DIAS SEM OS</td>" : "";

                    if ($login_fabrica==30){
                        echo "<td nowrap rel='esconde_coluna'>DEFEITO RECLAMADO</td>";
                        echo "<td nowrap rel='esconde_coluna'>END. CONSUMIDOR</td>";
                        echo "<td nowrap rel='esconde_coluna'>CIDADE CONSUMIDOR</td>";
                        echo "<td nowrap rel='esconde_coluna'>UF CONSUMIDOR</td>";
                        echo "<td nowrap rel='esconde_coluna'>DEFEITO CONSTATADO</td>";
                    }
                    echo ($login_fabrica==45 or $login_fabrica == 11) ? "<td align='center'>RG PRODUTO</td>" : "";

                    echo ($login_fabrica==19) ? "<td>Atendimento</td>" : "";

                    echo ($login_fabrica==19 || $login_fabrica == 94) ? "<td>Nome do técnico</td>" : "";

                    echo ($login_fabrica==1)  ? "<td>APARÊNCIA</td>" : "";//TAKASHI HD925

                    echo ($login_fabrica==115 or $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<td align='center'>KM</td>" : "";

                    if($login_fabrica == 11 AND $login_admin_intervensor){
                        $colspan += 1;
                    }

                    if(in_array($login_fabrica,array(85)) && empty($hd_chamado)){
                        $colspan = 4;
                    }

                    if($telecontrol_distrib){
                        $colspan = 5;
                    }

                    echo "<td colspan='$colspan'>AÇÕES</td>";

                    echo ($login_fabrica==7)  ? "<td colspan='$colspan'> <a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'></a></td>" : "";

                }else{
                    /* Titulo da OS */

                    if ($login_fabrica == 3) {
                        echo "<td>CÓD POSTO</td>";
                    }

                    if (strlen($btn_acao_pre_os)==0) {

                        if($telecontrol_distrib){
                            echo "<td><input type='checkbox' id='seleciona_todas_os_excluida' form='form_exclui_os' title='selecionar todas' /></td>";
                        }

                        echo "<td>OS</td>";
                        if($login_fabrica == 74) {
                            echo "<td>Status de Atendimento OS</td>";
                            echo "<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>";
                        }

                    } else {

                        echo "<td>Nº Atendimento</td>";

                        if($login_fabrica == 74) {
                            echo "<td>Status de Atendimento OS</td>";
                            echo "<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>";
                        }
                    }

                    if (in_array($login_fabrica, array(30, 35, 52)) and strlen($btn_acao_pre_os)==0) {
                        echo "<td>Nº Atendimento</td>";
                        if($login_fabrica == 30){
?>
                        <td>Atendimento Centralizado</td>
<?
                        }
                    }


                    if($login_fabrica == 52){
                        echo "<td>Número Ativo</td>";
                    }

                    echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<td>OS OFF LINE</td>" : "";

                    if($login_fabrica == 30){
                        echo "<td>OS Revendedor</td>"; #HD 117540;
                    }

                    if(!in_array($login_fabrica,array(1,3,20,50,81,137,127,145))){ // HD-2296739
                        echo "<td>";
                        echo ($login_fabrica==35) ? "PO#" : "SÉRIE ";
                        echo "</td>";
                    }else if($login_fabrica == 137){
                        echo "<td>N. LOTE</td>";
                    }

                    echo "<td>AB</td>";

                    if ($login_fabrica == '30') {
                        if($sem_listar_peca <> 1){ // HD-2415933
                            echo '<td>DATA PEDIDO</td>';
                            echo '<td>Nº PEDIDO</td>';
                            echo '<td>NOTA FISCAL</td>';
                            echo '<td>CÓDIGO</td>';
                            echo '<td>DESCRIÇÃO</td>';
                        }
                    }

                    echo ($login_fabrica==11) ? "<td>DP</td>" : ""; // HD 74587


                    if ($mostra_data_conserto) {
                        echo "<td><acronym title='Data de conserto do produto' style='cursor:help;'>DC</a></td>"; //HD 14927
                    }
                    if(!in_array($login_fabrica, array(3))) echo "<td>FC </td>";


                    echo ($login_fabrica==115 or $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<td align='center'>Tipo de Atendimento</td>" : "";

                    if(in_array($login_fabrica, array(87,94,141,144))){
                        echo "<td>TIPO DE ATENDIMENTO</td>";

                        if(in_array($login_fabrica, array(94,141,144))){
                            echo "<td>C / R</td>";
                        }
                    }else{
                        echo "<td>C / R</td>";
                    }

                    if ($login_fabrica == 52){
                        echo "<td> Aberto acima de </td>";
                    }

                    echo ($login_fabrica==72) ? "<td>Data NF</td>" : "";

                    if($login_fabrica == 30 OR $login_fabrica == 74){
                        echo "<td>POSTO</td>";
                    }

                    echo "<td nowrap>NOME POSTO</td>";

                    if ($login_fabrica == 11) {
                        echo "<td nowrap>SITUAÇÃO POSTO</td>";
                    }

                    echo "<td>CIDADE</td>";
                    echo "<td>ESTADO</td>";
                    echo ($login_fabrica==2)  ? "<td>CONSUMIDOR/REVENDA</td>" : "<td rel='esconde_coluna'>CONSUMIDOR/REVENDA</td>";

                    if(!empty($sua_os) && strstr($sua_os, "-") and !isset($novaTelaOs)){
                        $coluna_revenda = true;
                        echo "<td>REVENDA</td>";
                    }

                    if($login_fabrica == 141){ //HD - 2386867
                        echo "<td>UF CONSUMIDOR</td>";
                        echo "<td>UF POSTO</td>";
                    }

                    if(in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127))) {
                        echo "<td rel='esconde_coluna'>TELEFONE</td>";
                    }
                    echo ($login_fabrica==80 )  ? "<td>DATA DE COMPRA</td>" : "";
                    if(!in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127))) {
                        echo "<td>NF</td>";
                    }
                    if($login_fabrica == 30) {
                        echo "<td>NF PRODUTO</td>";
                    }
                    echo ($login_fabrica==3 OR $login_fabrica == 86 OR $login_fabrica == 52 or $multimarca=='t')  ? "<td>MARCA</td>" : "";

                    echo ($login_fabrica==11) ? "<td>REFERÊNCIA</td>" : "<td rel='esconde_coluna'>PRODUTO</td>"; // hd 74587

                    if($login_fabrica == 131){ ## HD -2181938
                        echo "<td>Data do Pedido</td>";
                        echo "<td>Data da Reprova</td>";
                        echo "<td>Aguardando Conserto</td>";
                    }

                    echo ($login_fabrica == 85) ? "<td nowrap>DIAS EM ABERTO</td>" : "";

                    if ($login_fabrica == 145 && strlen($btn_acao_pre_os) > 0) {
                        echo "<td nowrap>DIAS EM ABERTO</td>";
                    }

                    if ($login_fabrica==30){
                        echo "<td nowrap rel='esconde_coluna'>DEFEITO RECLAMADO</td>";
                        echo "<td nowrap rel='esconde_coluna'>END. CONSUMIDOR</td>";
                        echo "<td nowrap rel='esconde_coluna'>CIDADE CONSUMIDOR</td>";
                        echo "<td nowrap rel='esconde_coluna'>UF CONSUMIDOR</td>";
                    }
                    if(in_array($login_fabrica, array(30,137))){
                        echo "<td nowrap rel='esconde_coluna'>DEFEITO CONSTATADO</td>";
                    }
                    echo ($login_fabrica==45 or $login_fabrica == 11) ? "<td align='center'>RG PRODUTO</td>" : "";

                    echo ($login_fabrica==19) ? "<td>Atendimento</td>" : "";

                    echo ($login_fabrica==19 || $login_fabrica == 94) ? "<td>Nome do técnico</td>" : "";

                    echo ($login_fabrica==1)  ? "<td>APARÊNCIA</td>" : "";//TAKASHI HD925

                    if($login_fabrica == 137){
                        echo "<td>CFOP</td>";
                        echo "<td>Valor Unitário</td>";
                        echo "<td style='min-width: 80px !important;'>Valor Total Nota</td>";
                    }

                    if(in_array($login_fabrica, array(143))){
                        echo "<td>Horimetro</td>";
                    }

            echo ($login_fabrica==115 or $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<td align='center'>KM</td>" : "";

            if($login_fabrica == 11){
            $colspan = 5;
            }

                    if($login_fabrica == 11 AND $login_admin_intervensor){
                        $colspan += 1;
                    }

                    if((in_array($login_fabrica,array(85)) && empty($hd_chamado)) || $login_fabrica == 35){
                        $colspan = 4;
                    }

            if($telecontrol_distrib){
                $colspan = 5;
            }

                    if (strlen($btn_acao_pre_os)==0) {
                        if( in_array($login_fabrica,array(45,74,91,128))){
                            $colspan += 1; /* HD 940122 - Deixado com colspan de 4 */
                        }

                        echo "<td colspan='$colspan'>AÇÕES</td>";


                        echo ($login_fabrica==7)  ? "<td colspan='$colspan'> <a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'></a></td>" : "";

                    }
                }

                    echo "</tr></thead><tbody>";
            }

                if (strlen($btn_acao_pre_os) > 0) {
                    $hd_chamado         = trim(pg_fetch_result($res,$i,hd_chamado));
                    if($login_fabrica == 52){
                        $numero_ativo_res       = trim(pg_fetch_result($res,$i,ordem_ativo));
                    }

                    $sua_os             = trim(pg_fetch_result($res,$i,sua_os));
                    $serie              = trim(pg_fetch_result($res,$i,serie));
                    $nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));
                    $abertura           = trim(pg_fetch_result($res,$i,data));
                    if($login_fabrica==30 or $login_fabrica==52) $dt_hr_abertura     = trim(pg_fetch_result($res,$i,dt_hr_abertura));
                    $consumidor_nome    = trim(pg_fetch_result($res,$i,nome));
                    $consumidor_fone    = trim(pg_fetch_result($res,$i,consumidor_fone));
                    if($login_fabrica == 85){
                        $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                        if(!empty($array_campos_adicionais)){
                            $campos_adicionais = json_decode($array_campos_adicionais);
                            if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                                $consumidor_nome = $campos_adicionais->nome_fantasia;
                            }
                        }
                    }
                    $posto_nome         = trim(pg_fetch_result($res,$i,posto_nome));
                    $situacao_posto     = trim(pg_fetch_result($res,$i,credenciamento));
                    $marca_nome         = trim(pg_fetch_result($res,$i,marca_nome));
                    $produto_referencia = trim(pg_fetch_result($res,$i,referencia));
                    $data_nf            = trim(pg_fetch_result($res, $i, data_nf));
                    $produto_descricao  = trim(pg_fetch_result($res,$i,descricao));
                    if($login_fabrica == 96){
                        $tipo_atendimento = trim(pg_fetch_result($res,$i,tipo_atendimento));
                    }
                } else {

                    $cidade_posto       = trim(pg_fetch_result($res,$i,contato_cidade));
                    $estado_posto       = trim(pg_fetch_result($res,$i,contato_estado));
                    $cidade_uf          = $cidade_posto."/".$estado_posto;
                    $os                 = trim(pg_fetch_result($res,$i,os));
                    $sua_os             = trim(pg_fetch_result($res,$i,sua_os));
                    $hd_chamado         = trim(pg_fetch_result($res,$i,hd_chamado));

                    if($login_fabrica == 52){
                        $numero_ativo_res       = trim(pg_fetch_result($res,$i,ordem_ativo));
                    }

                    $nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));
                    $os_numero          = trim(pg_fetch_result($res,$i,os_numero));
                    $digitacao          = trim(pg_fetch_result($res,$i,digitacao));
                    $abertura           = trim(pg_fetch_result($res,$i,abertura));
                    $fechamento         = trim(pg_fetch_result($res,$i,fechamento));
                    $finalizada         = trim(pg_fetch_result($res,$i,finalizada));
                    $serie              = trim(pg_fetch_result($res,$i,serie));
                    $excluida           = trim(pg_fetch_result($res,$i,excluida));
                    $motivo_atraso      = trim(pg_fetch_result($res,$i,motivo_atraso));
                    $tipo_os_cortesia   = trim(pg_fetch_result($res,$i,tipo_os_cortesia));
                    $consumidor_revenda = trim(pg_fetch_result($res,$i,consumidor_revenda));
                    $consumidor_nome    = trim(pg_fetch_result($res,$i,consumidor_nome));
                    $consumidor_fone    = trim(pg_fetch_result($res,$i,consumidor_fone));
                    $revenda_nome       = trim(pg_fetch_result($res,$i,revenda_nome)); 

                    $nome_consumidor_revenda = ($consumidor_revenda == "C") ? $consumidor_nome : $revenda_nome;

                    if($login_fabrica == 85){
                        $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                        if(!empty($array_campos_adicionais)){
                            $campos_adicionais = json_decode($array_campos_adicionais);
                            if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                                $consumidor_nome = $campos_adicionais->nome_fantasia;
                            }
                        }
                    }
                    if($login_fabrica == 30){
                        $consumidor_endereco    = trim(pg_fetch_result($res,$i,consumidor_endereco));
                        $consumidor_cidade      = trim(pg_fetch_result($res,$i,consumidor_cidade));
                        $consumidor_estado      = trim(pg_fetch_result($res,$i,consumidor_estado));
                        $defeito_constatado     = trim(pg_fetch_result($res,$i,defeito_constatado));
                        $defeito_reclamado_os   = trim(pg_fetch_result($res,$i,defeito_reclamado_os));
                        $cliente_admin_nome     = trim(pg_fetch_result($res,$i,cliente_admin_nome));
                        $data_limite            = trim(pg_fetch_result($res,$i,termino_atendimento));

                        $cliente_admin_nome = (empty($cliente_admin_nome)) ? "Normal" : $cliente_admin_nome;
                    }
                    if(in_array($login_fabrica, array(30,137))){
                        $defeito_constatado = trim(pg_fetch_result($res,$i,defeito_constatado));
                    }
                    $revenda_nome       = trim(pg_fetch_result($res,$i,revenda_nome));
                    $codigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
                    $uf_posto           = pg_fetch_result($res, $i, "contato_estado");
                    $posto_nome         = trim(pg_fetch_result($res,$i,posto_nome));
                    $situacao_posto     = trim(pg_fetch_result($res,$i,credenciamento));
                    $impressa           = trim(pg_fetch_result($res,$i,impressa));
                    $extrato            = trim(pg_fetch_result($res,$i,extrato));
                    $os_reincidente     = trim(pg_fetch_result($res,$i,os_reincidente));
                    $produto_referencia = trim(pg_fetch_result($res,$i,produto_referencia));
                    $produto_descricao  = trim(pg_fetch_result($res,$i,produto_descricao));
                    $produto_voltagem   = trim(pg_fetch_result($res,$i,produto_voltagem));
                    $tipo_atendimento   = trim(pg_fetch_result($res,$i,tipo_atendimento));
                    $grupo_atendimento  = pg_fetch_result($res, $i, "grupo_atendimento");
                    $data_nf            = trim(pg_fetch_result($res,$i,'data_nf'));
                    $tecnico_nome       = trim(pg_fetch_result($res,$i,tecnico_nome));
                    $nome_atendimento   = trim(pg_fetch_result($res,$i,descricao));
                    $sua_os_offline     = trim(pg_fetch_result($res,$i,sua_os_offline));
                    $reincidencia       = trim(pg_fetch_result($res,$i,reincidencia));
                    $rg_produto         = trim(pg_fetch_result($res,$i,rg_produto));
                    $aparencia_produto  = trim(pg_fetch_result($res,$i,aparencia_produto));//TAKASHI HD925
                    $status_os          = trim(pg_fetch_result($res,$i,status_os)); //fabio
                    //HD391024
                    $status_checkpoint   = trim(pg_fetch_result($res,$i,status_checkpoint));
                    #117540


                    if($login_fabrica==30 or $login_fabrica==6){
                        $os_posto_x   = trim(pg_fetch_result($res,$i,os_posto));
                    }
                    if(in_array($login_fabrica, array(3,52,86)) or $multimarca =='t'){
                        $marca     = trim(pg_fetch_result($res,$i,marca));
                        $marca_nome     = trim(pg_fetch_result($res,$i,marca_nome));
                    }
                    if($login_fabrica ==52 and !empty($marca)){
                        $sqlx="select nome from  tbl_marca where marca = $marca;";
                        $resx=pg_exec($con,$sqlx);
                        $marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
                    }

                    //HD 14927
                    //if(in_array($login_fabrica,array(11,45,15,3,43,66,14,80))){
                    if($mostra_data_conserto){
                        $data_conserto=trim(pg_fetch_result($res,$i,data_conserto));
                    }

                    if ($login_fabrica == 45){
                        $campo_interacao = trim(pg_fetch_result($res, $i, 'campo_interacao'));
                    }

                    if($login_fabrica == 74) {
                        $os_interacao = pg_fetch_result($res, $i, os_interacao);
                        $atendido     = pg_fetch_result($res, $i, atendido);
                        $data_contato = pg_fetch_result($res, $i, data_contato);
                    }

                    if(in_array($login_fabrica,array(115,116,117,120))){
                        $valor_km = trim(pg_fetch_result($res,$i,valor_km));
                    }
                    if ($login_fabrica == 24) {
                        $status_cancelada = pg_fetch_result($res,$i,'cancelada');
                        $congelar = pg_fetch_result($res,$i,'congelar');
                    }

                    if ($login_fabrica == '30') {
                        $pedido = pg_fetch_result($res, $i, 'pedido');

                        if (!empty($pedido)) {

                            $digitacao_item = pg_fetch_result($res, $i, 'digitacao_item');
                            $nf = pg_fetch_result($res, $i, 'nf_fat') . ' ' . pg_fetch_result($res, $i, 'nf_emissao');
                            $peca_referencia = pg_fetch_result($res, $i, 'peca_referencia');
                            $peca_descricao = pg_fetch_result($res, $i, 'peca_descricao');


                        } else {

                            if ($old_os == $os) {
                                continue;
                            }

                            $digitacao_item = '';
                            $nf = '';
                            $peca_referencia = '';
                            $peca_descricao = '';
                        }

                        $old_os = $os;
                    }
                }

                $cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                $botao = ($i % 2 == 0) ? "azul" : "amarelo";

                /*IGOR - HD: 44202 - 22/10/2008 */
                if($login_fabrica==3){
                    $sqlI = "SELECT  status_os
                            FROM    tbl_os_status
                            WHERE   os = $os
                            AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
                            ORDER BY data DESC LIMIT 1";
                    $resI = pg_query ($con,$sqlI);
                    if (pg_num_rows ($resI) > 0){
                        $status_os = trim(pg_fetch_result($resI,0,status_os));
                        if ($status_os == 126 || $status_os == 143) {
                            $cor="#FF0000";
                            #$excluida = "t"; HD 56464
                        }
                    }
                }

                ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
                unset($marca_reincidencia);
                if ($reincidencia =='t' and $login_fabrica != 1 ) {
                    if($login_fabrica == 87) $cor = "#40E0D0"; else $cor = "#D7FFE1";
                    $marca_reincidencia = 'sim';
                }
                if ($excluida == "t"){
                    $cor = "#FF0000";
                }
                if ($login_fabrica==20 AND $status_os == "94" AND $excluida == "t"){
                    $cor = "#CACACA";
                }
                $vintecincodias = "";

                if ($login_fabrica == 91 && $status_os == 179) {
                    $cor="#FFCCCC";
                }

                if ($login_fabrica == 91 && $status_os == 13) {
                    $cor = "#CB82FF";
                }

                if($login_fabrica == 114){
                    if ($status_os == "62") {
                        $cor = ($login_fabrica == 114) ? "#FFCCCC" : "#E6E6FA";
                    }
                }
                if (in_array($login_fabrica,array(3,11,43,51,87,))) {

                    if ($status_os == "62") {
                        $cor = ($login_fabrica==43 or $login_fabrica==51) ? "#FFCCCC" : "#E6E6FA"; //HD 46730 HD 288642
                    }
                    if (in_array($status_os,array("72","87","116","120","122","140","141"))){
                        $cor="#FFCCCC";
                    }

                    if($login_fabrica == 87 AND ($cor == "#FFCCCC" OR $cor == "#E6E6FA")) {
                        $cor = "#FFA5A4";
                    }

                    if (($status_os=="64" OR $status_os=="73"  OR $status_os=="88" OR $status_os=="117") && strlen($fechamento)==0) {
                        if($login_fabrica == 87){
                            $cor = "#FEFFA4";
                        }else{
                            $cor = "#00EAEA";
                        }
                    }
                    if ($status_os=="65"){
                        $cor="#FFFF99";
                    }
                }

                if (in_array($login_fabrica, array(141,144))) {
                    switch ($status_os) {
                        case 192:
                            $cor = "#FFCCCC";
                            break;

                        case 193:
                            $cor = "#CCFFFF";
                            break;

                        case 194:
                            $cor = "#CB82FF";
                            break;
                    }
                }

                if ($login_fabrica==94){
                    $sqlI = "SELECT status_os
                            FROM    tbl_os_status
                            WHERE   os = $os
                            AND     status_os IN (62,64)
                      ORDER BY      data DESC
                            LIMIT   1";
                    $resI = pg_query ($con,$sqlI);
                    if (pg_num_rows ($resI) > 0){
                        $status_os = trim(pg_fetch_result($resI,0,status_os));
                        if ($status_os <> 64) {
                            $cor="#FFCCCC";
                        }
                    }
                }

                if ($login_fabrica == 3 && $status_os == 174) {
                    $cor = "#CB82FF";
                }

                if($status_os == "175"){
                    $cor = "#A4A4A4";
                }


                // OSs abertas há mais de 25 dias sem data de fechamento
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '".(($login_fabrica == 91) ? "30" : "25")." days','YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                        $vintecincodias = "sim";
                    }
                }

                if (strlen($btn_acao_pre_os) > 0) {

                    // OSs abertas há menos de 24 horas sem data de fechamento
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 30) {

                        $sqlX = "SELECT TO_CHAR('$dt_hr_abertura'::timestamp + INTERVAL '24 hours','YYYY-MM-DD HH24:MI:SS')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_timestamp,'YYYY-MM-DD HH24:MI:SS');";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta >= $aux_atual) {
                            $cor = "#33CC00";
                            $vintequatrohoras = "sim";
                            $smile = 'js/fckeditor/editor/images/smiley/msn/regular_smile.gif';
                        }

                    }

                    // OSs abertas há mais de 24 horas e menor que 72 sem data de fechamento
                    // maior que 72 horas sem data de fechamento
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 30) {

                        //$dt_hr_abertura = '2010-06-11 16:04:23';//data de teste
                        $sqlX = "SELECT TO_CHAR('$dt_hr_abertura'::timestamp + INTERVAL '72 hours','YYYY-MM-DD HH24:MI:SS')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_timestamp,'YYYY-MM-DD HH24:MI:SS');";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta <= $aux_atual) {
                            $cor = "#FF0000";//maior que 72
                            $smile = 'js/fckeditor/editor/images/smiley/msn/angry_smile.gif';
                        } else if ($vintequatrohoras != 'sim' && $aux_consulta > $aux_atual) {
                            $cor = "#FFFF66";//menor que 72
                            $smile = 'js/fckeditor/editor/images/smiley/msn/whatchutalkingabout_smile.gif';
                        }

                    }

                }

                /**
                 * - Legendas para prazo limite
                 */
                if(strlen($fechamento) == 0  && $login_fabrica == 30 && strlen($data_limite) > 0){
                    $sqlLimite = "SELECT ('$data_limite' - CURRENT_DATE) AS limite;";
                    $resLimite = pg_query($con,$sqlLimite);
                    $tempo_limite = pg_fetch_result($resLimite,0,limite);
                   // $tempo_limite = explode("day",$auxLimite);
                   // $tempo_limite = (int)$tempo_limite[0];

                    if(strlen($fechamento) == 0 && $tempo_limite > 3){
                        $cor = "#3C0";
                    }elseif(strlen($fechamento) == 0 && ($tempo_limite >= 1 && $tempo_limite < 4)){
                        $cor = "#FF6";
                    }elseif(strlen($fechamento) == 0 && $tempo_limite < 1){
                        $cor = "#F00";
                    }
                }

                // OSs abertas há mais de 10 dias sem data de fechamento - Nova
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 43) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        $cor = "#FF0033";
                    }
                }

                // CONDIÇÕES PARA INTELBRÁS - INÍCIO
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_atual = pg_fetch_result($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                    }

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
                }
                // CONDIÇÕES PARA INTELBRÁS - FIM

                // CONDIÇÕES PARA COLORMAQ - INÍCIO
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 50) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_atual = pg_fetch_result($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                    }

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        $cor = "#FF6633";
                    }

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        $cor = "#FF0000";
                    }
                }

                if($excluida=='t' AND ($login_fabrica==50 or $login_fabrica ==14)){//HD 37007 5/9/2008
                    $cor = "#FFE1E1";
                }
                // CONDIÇÕES PARA COLORMAQ - FIM

                // CONDIÇÕES PARA NKS - INÍCIO
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 45) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);
                    $sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta2 = pg_fetch_result($resX,0,0);

                    if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#1e85c7";

                    $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '25 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta2 = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta3 = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta2 <= $aux_consulta3 AND $aux_consulta3 <= $aux_consulta && strlen($fechamento) == 0) $cor = "#FF6633";

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta2 = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#9512cc";
                }
                // CONDIÇÕES PARA NKS - FIM

                // CONDIÇÕES PARA BLACK & DECKER - INÍCIO
                // Verifica se não possui itens com 5 dias de lançamento

                //HD 163220 - Colocar legenda nas OSs com atendimento Procon/Jec (Jurídico) - tbl_hd_chamado.categoria='procon'
                if ($login_fabrica == 11) {
                    $sql_procon = "
                    SELECT
                    tbl_hd_chamado.hd_chamado

                    FROM
                    tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado

                    WHERE
                    tbl_hd_chamado_extra.os=$os
                    AND tbl_hd_chamado.categoria IN ('pr_reclamacao_at', 'pr_info_at', 'pr_mau_atend', 'pr_posto_n_contrib', 'pr_demonstra_desorg', 'pr_bom_atend', 'pr_demonstra_org')
                    ";
                    $res_procon = pg_query($con, $sql_procon);

                    if (pg_num_rows($res_procon)) {
                        $cor = "#C29F6A";
                    }
                }

                // Verifica se está sem fechamento há 20 dias ou mais da data de abertura
                if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_atual = pg_fetch_result($resX,0,0);

                    if ($consumidor_revenda != "R") {
                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                            $mostra_motivo = 1;
                            if($login_fabrica == 87){
                                $cor = "#A4B3FF";
                            }else{
                                $cor = "#91C8FF";
                            }
                        }
                    }
                }

                $sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os ORDER BY data desc limit 1 ";
                $resX = @pg_query($con,$sqlX);
                if(@pg_num_rows($resX)==1){
                    $cor = (pg_fetch_result($resX,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
                }

                if ($login_fabrica == 1) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $data_hj_mais_5 = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sql = "SELECT COUNT(tbl_os_item.*) AS total_item
                            FROM tbl_os_item
                            JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
                            WHERE tbl_os.os = $os
                            AND   tbl_os.data_abertura::date >= '$aux_consulta'";
                    $resItem = pg_query($con,$sql);

                    $itens = pg_fetch_result($resItem,0,total_item);

                    if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#ffc891";

                    $mostra_motivo = 2;
                }
                // CONDIÇÕES PARA BLACK & DECKER - FIM

                // Gama
                if ($login_fabrica==51){ // HD 65821
                    $sqlX = "SELECT status_os,os FROM tbl_os JOIN tbl_os_status USING(os) WHERE os = $os AND status_os = 13";
                    $resX = pg_query($con,$sqlX);
                    if(pg_num_rows($resX)> 0){
                        $cor = "#CACACA";
                    }
                }

                if ($login_fabrica == 94 AND strlen($os) > 0) {

                    $sqlT = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                    $resT = pg_query($con, $sqlT);

                    if (pg_num_rows($resT)) {
                        $cor = "silver";
                    }

                }

                //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
                if ($fabrica_autoriza_troca_revenda && strlen($os)) {
                    $sql = "
                    SELECT
                    troca_revenda

                    FROM
                    tbl_os_troca

                    WHERE
                    os=$os
                    ";
                    $res_troca_revenda = pg_query($con, $sql);

                    if (pg_num_rows($res_troca_revenda)) {
                        $troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
                    }
                    else {
                        $troca_revenda = "";
                    }
                }

                if ($troca_revenda == 't') {
                    $cor = "#d89988";
                }

                if ($vintecincodias == 'sim' and $marca_reincidencia == 'sim') {
                    if($login_fabrica == 87) $cor = "#D2D2D2"; else $cor = "#CC9900";
                }

                // CONDIÇÕES PARA GELOPAR - INÍCIO
                if($login_fabrica==85 AND strlen($os)>0){
                    $sqlG = "SELECT
                                interv.os
                            FROM (
                                SELECT
                                ultima.os,
                                (
                                    SELECT status_os
                                    FROM tbl_os_status
                                    WHERE status_os IN (147)
                                    AND tbl_os_status.os = ultima.os
                                    ORDER BY data
                                    DESC LIMIT 1
                                ) AS ultimo_status
                                FROM (
                                        SELECT os FROM tbl_os WHERE tbl_os.os = $os
                                ) ultima
                            ) interv
                            WHERE interv.ultimo_status IN (64,147);";
                            #echo nl2br($sqlG);
                    $resG = pg_exec($con,$sqlG);

                    if(pg_numrows($resG)>0){
                        $cor = "#AEAEFF";
                    }
                }
                // CONDIÇÕES PARA GELOPAR - FIM

                ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

                if (strlen($sua_os) == 0){
                    $sua_os = $os;
                }
                if ($login_fabrica == 1) {
                    $sua_os2 = $codigo_posto.$sua_os;
                    $sua_os = "<a href='etiqueta_print.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";
                }

                //HD391024
                if($login_fabrica == 96 and strlen($btn_acao_pre_os) > 0){
                    if($tipo_atendimento == '92') $cor = "#FFFF66";
                    if($tipo_atendimento == '93') $cor = "#C94040";
                    if($tipo_atendimento == '94') $cor = "#33CC00";
                }

                if($login_fabrica == 40 AND $status_os == 118){
                    $cor = "#BFCDDB";
                }

                if (in_array($status_os, array(158))){
                    $cor="#FFCCCC";
                }

                if ($login_fabrica == 45){
                    //INTERACAO

                    $sqlxyz = "SELECT count(*) from tbl_os_interacao where os = $os";
                    $resxyz = pg_query($con,$sqlxyz);
                    $count_interacao = pg_fetch_result($resxyz, 0, 0);
                    if ($count_interacao > 0){

                        if (strlen(trim($campo_interacao))==0){
                            $cor = "#F98BB2";
                        }
                    }

                    if ($tipo_os != 'INTERACAO'){

                        //OS TROCA - RESOLVIDO
                        $sqlaaa = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os";
                        $resaaa = pg_query($con,$sqlaaa);

                        if (pg_num_rows($resaaa)>0){
                            $cor = "#56BB71";
                        }

                        //OS TROCA - PENDENTE
                        $sqlbbb = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) left join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os and tbl_faturamento_item.faturamento_item is null and tbl_os_troca.ressarcimento is false ";
                        $resbbb = pg_query($con,$sqlbbb);

                        if (pg_num_rows($resbbb)>0){
                            $cor = "#EAEA1E";
                        }
                    }
                }

                $atendido = ($atendido == 't' or (strlen($finalizada) and !strlen($os_interacao))) ? 't' : 'f';

                echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left' id='div_atendimento_$hd_chamado'>";

        if($telecontrol_distrib){
            echo "<td><input type='checkbox' name='exclui_os[]' value='$os' form='form_exclui_os' /></td>";
        }

                if ($login_fabrica == 3) {
                    echo "<td nowrap>&nbsp;$codigo_posto</td>";
                }

                if (strlen($btn_acao_pre_os)==0) {
                    //hd 231922
                    echo "<td nowrap>&nbsp;";
                        exibeImagemStatusCheckpoint($status_checkpoint,$sua_os);
?>
                        <a href="os_press.php?os=<?=$sua_os?>" target="_blank"><?=$sua_os?></a>
<?
                    echo "</td>";
                }else {
                    if($login_fabrica == 96){
                        echo "<td nowrap><a href='print_atendimento_gravado.php?hd_chamado=$hd_chamado' target=_blank>&nbsp;" . $hd_chamado . "</a></td>";
                    }
                    else{
                        echo "<td nowrap><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>&nbsp;" . $hd_chamado . "</a></td>";
                    }
                }
                if (in_array($login_fabrica, array(30,35,52)) and strlen($btn_acao_pre_os)==0) {
                    if ($login_fabrica == 30){
                        if ($login_cliente_admin){
                            echo "<td nowrap><a href='../admin_cliente/pre_os_cadastro_sac_esmaltec.php?callcenter=$hd_chamado' target=_blank>&nbsp;" . $hd_chamado . "</a></td>";
                        }else{
                            echo "<td nowrap><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target=_blank>&nbsp;" . $hd_chamado . "</a></td>";
                        }
                    }else{
                        echo "<td nowrap><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target=_blank>&nbsp;" . $hd_chamado . "</a></td>";
                    }
                    if($login_fabrica == 30){
?>
                        <td nowrap><?=$cliente_admin_nome?></td>
<?
                    }
                }

                echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<td nowrap>&nbsp;" . $sua_os_offline . "</td>" : "";
                #117540
                if($login_fabrica ==30){
                    if(strlen($os_posto_x)<=0)$os_posto_x = "-";
                    echo "<td nowrap align='center'>&nbsp;" . $os_posto_x . "</td>";
                }

                if($login_fabrica==52) {
                    echo "<td nowrap>&nbsp;" .$numero_ativo_res. "</td>";
                }

                # HD-776394
                if($login_fabrica == 74) {
                    echo "<td nowrap>&nbsp;" . ($atendido == 't' ? "" : "Não ") . "Atendido</td>";
                    echo "<td nowrap>" . ((empty($os_interacao) and !empty($finalizada)) ? "" : $data_contato) . "</td>";
                }

                if(!in_array($login_fabrica,array(1,3,20,50,81,127,145))){ // HD-2296739
                    echo "<td nowrap>&nbsp;" . $serie . "</td>";
                }

                echo "<td nowrap><acronym title='Data Abertura: $abertura' style='cursor: help;'>&nbsp;" . substr($abertura,0,5) . "</acronym></td>";

                if ($login_fabrica == '30') {
                    if($sem_listar_peca <> 1){ // HD-2415933
                        echo '<td nowrap>' . $digitacao_item . '</td>';
                        echo '<td nowrap>' . $pedido . '</td>';
                        echo '<td nowrap>' . $nf . '</td>';
                        echo '<td nowrap>' . $peca_referencia . '</td>';
                        echo '<td nowrap>' . $peca_descricao . '</td>';
                    }
                }
                if ($login_fabrica ==11) { // HD 74587
                    $sql_p = " SELECT to_char(tbl_pedido.data,'DD/MM/YYYY') as data_pedido
                                FROM tbl_os_produto
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_pedido  USING(pedido)
                                WHERE tbl_os_produto.os = $os
                                AND   tbl_pedido.fabrica = $login_fabrica
                                ORDER BY tbl_pedido.pedido ASC LIMIT 1 ";
                    $res_p = @pg_query($con,$sql_p);
                    echo "<td nowrap >";
                    if (pg_num_rows($res_p) > 0) {
                        $data_pedido = pg_fetch_result($res_p,0,data_pedido);
                        echo "<acronym title='Data Pedido: $data_pedido' style='cursor: help;'>" . substr($data_pedido,0,5) . "</acronym>";
                    }
                    echo "</td>";
                }

                //HD 14927
                //if(in_array($login_fabrica,array(3,11,14,15,43,45,66,80))){
                if($mostra_data_conserto){
                    echo "<td nowrap ><acronym title='Data do Conserto: $data_conserto' style='cursor: help;'>&nbsp;" . substr($data_conserto,0,5) . "</acronym></td>";
                }

                $aux_fechamento = ($login_fabrica == 1) ? $finalizada : $fechamento;
                //HD 204146: Fechamento automático de OS
                if ($login_fabrica == 3) {
                    $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                    $res_sinalizador = pg_query($con, $sql);
                    $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
                }

                if ($sinalizador == 18) {
                    echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento - FECHAMENTO AUTOMÁTICO' style='cursor: help; color:#FF0000; font-weight: bold;'>F. AUT</acronym></td>";
                }else {
                    if(!in_array($login_fabrica, array(3,139))) echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>&nbsp;" . substr($aux_fechamento,0,5) . "</acronym></td>";
                }
                //HD 211825: Filtrar por tipo de OS: Consumidor/Revenda
                if ($btn_acao_pre_os) {

                }else {
                    if(in_array($login_fabrica, array(87)) AND !empty($tipo_atendimento)){
                        $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                        $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                        $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');

                        echo "<td>$desc_tipo_atendimento</td>";
                    }else{
                        if(in_array($login_fabrica, array(94,115,116,117,120,141,144))){

                            if(!empty($tipo_atendimento)){
                                $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                                $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                                $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
                            }else{
                                $desc_tipo_atendimento = "";
                            }

                            echo "<td nowrap>$desc_tipo_atendimento</td>";
                        }
                        switch ($consumidor_revenda) {
                            case "C":
                                echo "<td nowrap><acronym title='Consumidor' style='cursor: help;'>CONS</acronym></td>";
                            break;

                            case "R":
                                echo "<td nowrap><acronym title='Revenda' style='cursor: help;'>REV</acronym></td>";
                            break;

                            case "":
                                    /*HD-1899424*/
                                    if(in_array($login_fabrica, array(138)))    echo "<td nowrap> &nbsp; </td>";
                                    else                                        echo "<td nowrap> N/I</td>";
                            break;
                        }
                    }

                }

                if ($login_fabrica == 52) {
                    $sql = "SELECT data_abertura,current_date as data_atual
                            FROM   tbl_os
                            where fabrica = $login_fabrica
                            and os=$os";
                    $resx = pg_query($con,$sql);
                    if(pg_num_rows($resx) > 0){
                        $data_abertura_os = pg_fetch_result($resx, 0, 0);
                        $data_atual = pg_fetch_result($resx, 0, 1);
                        $start = strtotime($data_abertura_os);

                        $end = strtotime($data_atual);

                        $diff = $end - $start;

                        $diff = ($diff / 86400);

                        if ($diff == 1){
                            $diferença = "24 horas";
                        }

                        if ($diff == 2){
                            $diferença = "48 horas";
                        }

                        if ($diff == 3){
                            $diferença = "72 horas";
                        }

                        if ($diff > 3){
                            $diferença = "+ 72 horas";
                        }

                        if (empty($diferença)) {
                            $diferença = "&nbsp;";
                        }
                        if (empty($aux_fechamento)){
                            echo "<td>".$diferença."</td>";
                        }else{
                            echo "<td>".$diferença."</td>";
                        }
                    }else{
                        echo "<td></td>";
                    }
                }

                if($login_fabrica==72) {
                    echo "<td nowrap>$data_nf</td>";
                }

                if ($login_fabrica ==30 OR $login_fabrica == 74 ){
                    echo "<td nowrap>$codigo_posto</td>";
                }

                echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
                if ($login_fabrica == 11){
                    echo "<td nowrap>$situacao_posto</td>";
                }

                if (in_array($login_fabrica, array(11)) && strlen($btn_acao_pre_os) == 0) {
                    echo "<td>$cidade_posto</td>";
                    echo "<td align='center'>$estado_posto</td>";
                } else if (!in_array($login_fabrica, array(11))){
                    echo "<td>$cidade_posto</td>";
                    echo "<td align='center'>$estado_posto</td>";
                }

                if ($login_fabrica==2 and $consumidor_revenda=="R" and $consumidor_nome==''){
                    echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
                }else if (!isset($novaTelaOs)) {
                    if($consumidor_revenda=="C" || strlen($btn_acao_pre_os) > 0 || empty($consumidor_revenda)){
                        echo "<td nowrap rel='esconde_coluna'>&nbsp;<acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>";
                        if (strlen($smile) > 0) {
                            echo '<img src="'.$smile.'" border="0" />&nbsp;';
                        }
                        echo substr($consumidor_nome,0,15) . "</acronym></td>";
                    }else{
                        if ($coluna_revenda) {
                                echo "<td></td>";
                        }
                    }
                }

                if($login_fabrica == 145 and $consumidor_revenda == "C"){
                    $tipo_consumidor_revenda = "Consumidor";
                }elseif($login_fabrica == 145 and $consumidor_revenda == "R"){
                    $tipo_consumidor_revenda = "Revenda";
                }else{
                    $tipo_consumidor_revenda = "Consumidor";
                }

                if (isset($novaTelaOs)) {
                    echo "<td>&nbsp;<acronym title='$tipo_consumidor_revenda: $nome_consumidor_revenda' style='cursor: help;'>".$nome_consumidor_revenda."</acronym></td>";
                }

                if(((!empty($sua_os) && strstr($sua_os, "-")) or $consumidor_revenda=='R') and empty($novaTelaOs)){
                    echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
                }

                if($login_fabrica == 141){ //HD -2386867
                    $sqlEstados = "SELECT tbl_os.consumidor_estado,
                                        tbl_cidade.estado,
                                        tbl_posto_fabrica.contato_estado
                                    FROM tbl_os
                                    JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                    JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
                                    JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                                    WHERE os = $os
                                    ";
                    $resEstados = pg_query($con, $sqlEstados);
                    if(pg_num_rows($resEstados) > 0){
                        if($consumidor_revenda == "R" ){
                            $uf_consumidor = pg_fetch_result($resEstados, 0, 'estado');
                        }else{
                            $uf_consumidor = pg_fetch_result($resEstados, 0, 'consumidor_estado');
                        }

                        $uf_posto = pg_fetch_result($resEstados, 0, 'contato_estado');
                    }
                    echo "<td>$uf_consumidor</td>";
                    echo "<td>$uf_posto</td>";

                }

                if(in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127))) {
                    echo "<td nowrap rel='esconde_coluna' >&nbsp;<acronym title='Telefone: $consumidor_fone' style='cursor: help;'>&nbsp;" .
                    $consumidor_fone. "</acronym></td>";
                }
                if(!in_array($login_fabrica, array(1,3,6,11,15,19,20,24,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127))) {
                    echo "<td nowrap>&nbsp;<acronym title='NF: $nota_fiscal' style='cursor: help;'>$nota_fiscal</acronym></td>";
                }
                echo ($login_fabrica==3 OR $login_fabrica == 86 or $multimarca == 't' ) ? "<td nowrap>&nbsp;$marca_nome</td>" : "";//TAKASHI HD925
                echo ($login_fabrica == 52) ? "<td nowrap>&nbsp;$marca_logo_nome</td>" : "";//Tobias

                $produto = ($login_fabrica ==11) ? $produto_referencia : $produto_referencia . " - " . $produto_descricao; # hd 74587
                if ($login_fabrica == 80) {
                    echo "<td nowrap>$data_nf</td>";
                }

                echo "<td nowrap rel='esconde_coluna' >&nbsp;<acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>&nbsp;" . substr($produto,0,20) . "</acronym></td>";

                if(in_array($login_fabrica, array(85))){

                    if(!empty($os)){

                        $sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$os}";
                        $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                        if(pg_num_rows($res_dias_aberto) == 1){
                            $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                            $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                        }

                    }

                    echo "<td align='center'>$dias_aberto</td>";
                }

                if ($login_fabrica == 145 && strlen($btn_acao_pre_os) > 0) {
                    if (!empty($os)) {
                        $dias_aberto = "Pré-OS Atendida";
                    } else {
                        $sql_dias_aberto = "SELECT date(now())::date - data::date AS dias_aberto FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} AND fabrica = {$login_fabrica}";
                        $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                        if(pg_num_rows($res_dias_aberto) == 1){
                            $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                            $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                        }
                    }

                    echo "<td align='center'>$dias_aberto</td>";
                }

                if($login_fabrica == 131){ // HD-2181938

                    $digitacaoItem = '';

                    $sqlDigitacao = "SELECT TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS digitacao_item
                        FROM tbl_os_item
                        JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        WHERE tbl_os_produto.os = $os
                        AND tbl_os_item.fabrica_i = $login_fabrica
                        ORDER BY digitacao_item ASC LIMIT 1";
                    $resDigitacao = pg_query($con, $sqlDigitacao);

                    if(pg_num_rows($resDigitacao) > 0){

                        $digitacaoItem = pg_fetch_result($resDigitacao, 0, 'digitacao_item');
                    }

                    $dataReprova = '';

                    $sqlReprovada = "SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_intervencao,
                            tbl_os_status.status_os
                            FROM tbl_os_status
                            WHERE os = $os
                            AND tbl_os_status.status_os = 203
                            AND tbl_os_status.fabrica_status = $login_fabrica
                            ORDER BY data DESC
                            LIMIT 1";
                    $resReprovada = pg_query ($con,$sqlReprovada);
                    if (pg_num_rows ($resReprovada) > 0){
                        $dataReprova = trim(pg_fetch_result($resReprovada,0,'data_intervencao'));
                    }

                    $dataAprovacao = '';

                    $sqlAprovada = "SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_intervencao,
                            tbl_os_status.status_os
                            FROM tbl_os_status
                            WHERE os = $os
                            AND tbl_os_status.status_os = 204
                            AND tbl_os_status.fabrica_status = $login_fabrica
                            ORDER BY os_status DESC
                            LIMIT 1";
                    $resAprovada = pg_query ($con,$sqlAprovada);
                    if (pg_num_rows ($resAprovada) > 0){
                        $dataAprovacao = trim(pg_fetch_result($resAprovada,0,'data_intervencao'));
                    }

                    echo "<td>".$digitacaoItem."</td>";
                    echo "<td>".$dataReprova."</td>";
                    echo "<td>".$dataAprovacao."</td>";
                }

                if($login_fabrica == 137) echo "<td nowrap rel='esconde_coluna'>$defeito_constatado</td>";

                echo ($login_fabrica==45 or $login_fabrica == 11) ? "<td align='center'>$rg_produto</td>" : "";

                    if($login_fabrica == 137){
                        $dados = json_decode($rg_produto);
                        echo "<td>".$dados->cfop."</td>";
                        echo "<td>".$dados->vu."</td>";
                        echo "<td>".$dados->vt."</td>";
                    }

                    if(in_array($login_fabrica, array(143))){
                         echo "<td>".$rg_produto."</td>";
                    }

                    echo ($login_fabrica==19) ? "<td>&nbsp;$tipo_atendimento $nome-atendimento</td>" : "";
                    echo ($login_fabrica==19 || $login_fabrica == 94) ? "<td>$tecnico_nome</td>" : "";
                    echo ($login_fabrica==1) ? "<td>&nbsp;$aparencia_produto</td>" : "";//TAKASHI HD925
                    echo ($login_fabrica==115 OR $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<td align='center' nowrap>".number_format($valor_km,2,',','.')."</td>" : "";
                    if ($login_fabrica ==30 ) echo "<td nowrap rel='esconde_coluna'>$defeito_reclamado_os</td>";
                    if ($login_fabrica ==30 ){
                        echo "<td nowrap rel='esconde_coluna'>$consumidor_endereco</td>";
                        echo "<td nowrap rel='esconde_coluna'>$consumidor_cidade</td>";
                        echo "<td nowrap rel='esconde_coluna'>$consumidor_estado</td>";
                    }
                    if ($login_fabrica ==30 ) echo "<td nowrap rel='esconde_coluna'>$defeito_constatado</td>";

                    //HD 194732 - Para OSs com extrato não deve ser possível alterar
                    if ($btn_acao_pre_os){
                        $os = 0;
                    }
                    $sql_os_extrato = " SELECT  extrato
                                        FROM    tbl_os_extra
                                        WHERE   os=$os
                                        AND     (
                                                    extrato=0
                                                OR  extrato IS NULL
                                                )
                    ";
                    $res_os_extrato = pg_query($con, $sql_os_extrato);

                    //HD 194731 - No programa os_cadastro.php estavam na mesma tela as opções de alteração da OS
                    //e de troca da OS, no entanto, em formulários diferentes. Desta forma ao submeter um formulário
                    //as alterações do outro se perdiam. Dentro do programa os_cadastro.php continuam as duas funções
                    //mas agora cada uma é acessada por um botão diferente
                    if($login_fabrica == 96 and $login_cliente_admin != ""){

                    }else{
                        if (pg_num_rows($res_os_extrato) && $excluida <>'t') {
                            echo "<td width='60' align='center'>";
                            if ($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)) {
                                echo "<a href='os_cadastro_troca.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                            } else {
                                if ($login_fabrica == 30 and !$login_cliente_admin){
                                    $sqlAdmin = "
                                        SELECT  tbl_admin.responsavel_postos
                                        FROM    tbl_admin
                                        WHERE   fabrica = $login_fabrica
                                        AND     admin   = $login_admin;
                                    ";
                                    $resAdmin = pg_query($con,$sqlAdmin);
                                    $cadastra_laudo = pg_fetch_result($resAdmin,0,responsavel_postos);

                                    if($cadastra_laudo == 't'){
                                        echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                    }
                                } else if (in_array($login_fabrica, array(141,144))) {
                                    $select_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                    $res_troca_produto    = pg_query($con, $select_troca_produto);

                                    if (!(pg_num_rows($res_troca_produto) > 0 && in_array(pg_fetch_result($res_troca_produto, 0, "status_os"), array(192,193)))) {
                                        echo "<img border='0' src='imagens_admin/btn_trocar_$botao.gif' onclick='solicitaTroca($os, this)' >";
                                    }
                                } else if (isset($novaTelaOs)) {
                                    if (in_array($login_fabrica, array(145))) {
                                        if ($grupo_atendimento != "R") {
                                            $select_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                            $res_troca_produto    = pg_query($con, $select_troca_produto);

                                            if (!(pg_num_rows($res_troca_produto) > 0 && in_array(pg_fetch_result($res_troca_produto, 0, "status_os"), array(192,193)))) {
                                                echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                            }
                                        }
                                    }else if(in_array($login_fabrica, array(147))) {
                                            $select_produto = "SELECT produto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
                                            $res_produto    = pg_query($con, $select_produto);

                                            if ((pg_num_rows($res_produto) > 0 && !in_array(pg_fetch_result($res_produto, 0, "produto"), array(234103)))) {
                                                echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                            }
                                    } else {
                                        echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                    }
                                } else if ($login_fabrica <> 30){
                                    echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                }
                            }
                            echo "</td>";

                        }else{  // RETIRADO - HD 410675 - Nao pode trocar OS em extrato.. pois gerou varios problemas
                            //Mesmo se a OS estiver finalizada pode fazer a TROCA novamente
                            if($login_fabrica==3 and strlen($btn_acao_pre_os) == 0){
                                echo "<td width='60' align='center'>&nbsp;";
                                echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                echo "</td>";
                            }else if (!in_array($login_fabrica, array(139))) {
                                echo "<td></td>";
                            }else if(in_array($login_fabrica, array(145)) && strlen($btn_acao_pre_os) > 0){
                                echo "<td><a href='direcionar_pre_os.php?hd_chamado=$hd_chamado' rel='shadowbox;height=450;width=800'><button type='button'>Direcionar Pré-OS</button></a></td>";
                            }
                        }

                        if (strlen($btn_acao_pre_os)==0) {
                            echo "<td width='60' align='center'>";
                            if($excluida <>'t'){
                                if (pg_num_rows($res_os_extrato)) {
                                    if ($login_fabrica==1 AND ($tipo_atendimento==17 OR $tipo_atendimento==18)){
                                        echo "<a href='os_cadastro_troca.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                    }else{
                                        if($login_fabrica == 11 AND strlen($finalizada) > 0){
                                            echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                        }else{
                                            if ($login_fabrica == 30 and !$login_cliente_admin){
                                                echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                            }else if ($login_fabrica <> 30){
                                                if (isset($novaTelaOs)) {

                                                     if(in_array($login_fabrica,array(145,152))){
                                                        if($grupo_atendimento == "R"){
                                                            echo "<a href='cadastro_os_revisao.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                        }elseif($grupo_atendimento == "A"){
                                                            if($login_fabrica == 145){
                                                                echo "<a href='cadastro_os_entrega_tecnica.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
                                                        }else{
                                                            $sql_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                                            $res_troca_produto = pg_query($con, $sql_troca_produto);

                                                            $status_troca_produto = pg_fetch_result($res_troca_produto, 0, "status_os");

                                                            $sql_troca_peca = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(199,200,201) ORDER BY data DESC LIMIT 1";
                                                            $res_troca_peca = pg_query($con, $sql_troca_peca);

                                                            $status_troca_peca = pg_fetch_result($res_troca_peca, 0, "status_os");

                                                            if ($status_troca_produto != 194 && $status_troca_peca != 201) {
                                                                echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
                                                        }
                                                    }else{
                                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                    }

                                                } else {
                                                    echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            echo "</td>\n";
                        }

                    }

                    if (strlen($btn_acao_pre_os)==0) {
                        echo "<td width='60' align='center'>";
                        if ($login_fabrica == 145 && $grupo_atendimento == "R") {
                            echo "<a href='os_press_revisao.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
                        } else {
                            echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
                        }
                        echo "</td>\n";
                        if($login_fabrica == 96 AND !empty($status_checkpoint)){ //HD391024
                            if($status_checkpoint == 5){
                                echo "<td align='center'>&nbsp;";
                                    echo "<input type='button' value='Aprovar' onclick=\"aprovaOrcamento(".$sua_os.",".$os.",'Aprovar')\" id='aprovar_".$os."'>&nbsp;";
                                    echo "<input type='button' value='Reprovar' onclick=\"aprovaOrcamento(".$sua_os.",".$os.",'Reprovar')\" id='reprovar_".$os."'>&nbsp;";
                                    echo "<input type='button' value='Orçamento' onclick=\"window.open('../print_orcamento.php?os=".$os."','Orçamento')\" id='orcamento'>";
                                echo "</td>\n";
                            }elseif($status_checkpoint == 6 OR $status_checkpoint == 7){
                                $status_checkpoint_ant = $status_checkpoint;
                                echo "<td align='center'>&nbsp;";
                                    echo "<input type='button' value='Orçamento' onclick=\"window.open('../print_orcamento.php?os=".$os."')\" id='orcamento'>";
                                echo "</td>\n";
                            }else{
                                echo "<td width='160' align='center'>&nbsp;</td>\n";
                            }
                        }
                    }else if(!in_array($login_fabrica, array(139,145))) {
                        echo "<td></td>";
                    }

                    if(in_array($login_fabrica, array(85))){
                        if(empty($hd_chamado)){
                            echo "<td align='center' id='box_{$i}'>";
                                echo "<button style='white-space: nowrap; font-size: 12px;' onClick='abreAtendimento(\"{$os}\", \"{$i}\")'>Abrir Atendimento</button>";
                            echo "</td>\n";
                        }else{
                            echo "<td></td>";
                        }
                    }

                    if($login_fabrica == 11 AND $admin_interventor == 't' ){
                        echo "<td width='60' align='center'>&nbsp;";
                        if($status_os != 158 AND empty($fechamento)){
                            echo "<input type='button' name='intervencao' class='intervencao' id='$os'  rel='$sua_os' title='Intervenção Departamento Juridico' value=' Bloquear ' style='cursor: pointer' />";
                            echo "</td>\n";
                        }
                    }

                if($login_fabrica == 24){
                     if (strlen($fechamento) == 0 and strlen($btn_acao_pre_os) == 0){
                        if ($status_cancelada == "t") {
                            echo "<td align='center' id='box_{$i}'>";
                                echo "<img border='0' src='imagens_admin/descongelar_os.gif' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")'>";
                            echo "</td>\n";
                        }elseif($congelar == 't'){
                            echo "<td align='center' id='box_{$i}'>";
                                echo "<img border='0' src='imagens_admin/congelar_os.jpg' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")' >";
                            echo "</td>\n";
                        }else{
                            echo "<td></td>";
                        }
                    }else{
                            echo "<td></td>";
                    }
                }
                    if (($login_fabrica==50 and ($excluida <> 't')) or ($login_fabrica == 14 and $excluida <> 't') or in_array($login_fabrica,array(20,24,66)) ) {

                        if (strlen($fechamento) == 0 and !in_array($login_fabrica,array(20,24)) and strlen($btn_acao_pre_os) == 0) {
                            echo "<td>&nbsp;<a href='os_item.php?os=$os' target='_blank'>";
                            if($sistema_lingua == "ES"){
                                echo "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'>";
                            }else{
                                // $data_conserto > "03/11/2008" HD 50435
                                $xdata_conserto = fnc_formata_data_pg($data_conserto);

                                $sqlDC = "SELECT $xdata_conserto::date > '2008-11-03'::date AS data_anterior";
                                $resDC = pg_query($con, $sqlDC);
                                if(pg_num_rows($resDC)>0){
                                    $data_anterior = pg_fetch_result($resDC, 0, 0);
                                }

                                echo ($login_fabrica==11 AND strlen($data_conserto)>0 AND $data_anterior == 't') ? "" : "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'>";
                            }
                            echo "</a></td>";
                        }

                        if ($login_fabrica <> 74){
                            echo ((strlen($fechamento) == 0 and strlen($btn_acao_pre_os) == 0) or ($login_fabrica == 20 and strlen($btn_acao_pre_os) == 0)) ? "<td nowrap='' align='center'><a href=\"javascript: if (confirm('Deseja realmente excluir a os $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\">&nbsp;<img id='excluir_$i' border='0' src='imagens/btn_excluir_novo.gif'></a></td>" : "<td width='60' align='center'> </td>";
                        }
                        if (strlen($fechamento) == 0 AND $status_os!="62" && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143" and !in_array($login_fabrica,array(20,24,74)) and strlen($btn_acao_pre_os) == 0) {
                            echo "<td>&nbsp;<img id='sinal_$i' border='0' src='imagens/btn_fecha.gif' onclick=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor nao seja hoje, utilize a opcao de fechamento de os para informar a data correta! confirma o fechamento da os $sua_os com a data de hoje?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"></td>";
                        }
                    }

                    if ($fabrica_copia_os_excluida and strlen($btn_acao_pre_os) == 0) { //HD 278885
                        echo "<td width='60' align='center'>";
                        echo (pg_num_rows($res_os_extrato)) ? "<a href=\"javascript: if (confirm('Deseja realmente excluir a os $sua_os2?') == true) disp_prompt('$os','$sua_os2');\">
                        <img id='excluir_$i' border='0' src='imagens/btn_excluir_novo.gif'></a>" : "&nbsp;";
                        echo "</td>";
                    }

                    if( in_array($login_fabrica, array(7, 45)) ){ // HD 31598, 48441 e 940122

                        if( $login_fabrica == 45 and empty($fechamento) ){  // HD 940122 - não mostrar o botão "Lançar Itens" para OS Finalizadas na NKS
                            echo "<td width='60' align='center'>&nbsp;";
                            echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca_$botao.gif'></a>";
                            echo "</td>\n";
                        }else if( $login_fabrica == 7 ){
                            echo "<td width='60' align='center'>&nbsp;";
                            echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca_$botao.gif'></a>";
                            echo "</td>\n";


                            echo "<td width='60' align='center'>&nbsp;";
                            echo "<a href='os_transferencia_filizola.php?sua_os=$sua_os&posto_codigo_origem=$codigo_posto&posto_nome_origem=$posto_nome' target='_blank'><img border='0' src='imagens/btn_transferir_$botao.gif'></a>";
                            echo "</td>\n";

                            echo "<td width='60' align='center'>&nbsp;";
                            echo ($consumidor_revenda=="R") ? "<a href='os_print_manutencao.php?os_manutencao=$os_numero' target='_blank'>" : "<a href='os_print.php?os=$os' target='_blank'>";//HD 80470
                            echo "<img border='0' src='imagens/btn_imprimir_$botao.gif'></a></td>\n";

                            echo "<td width='60' align='center'>&nbsp;";
                            echo "<input name='imprimir_$i' type='checkbox' id='imprimir' rel='imprimir' value='".$os."' />";
                            echo "</td>\n";
                        }
                    }

                    if ($login_fabrica == 91 && $vintecincodias == "sim" and strlen($btn_acao_pre_os) == 0) {
                        echo "<td>
                            <img src='imagens/btn_fecha.gif' name='fechar_os_30_dias_{$i}' rel='{$os}' style='cursor: pointer;' alt='Fechar OS com mais de 30 dias aberta' title='Fechar OS com mais de 30 dias aberta' />
                            <input type='hidden' name='i' value='{$i}' />
                        </td>";
                    }

                    $texto = ($login_fabrica == 145) ? "Pré-OS" : "";

                    if(strlen($btn_acao_pre_os) > 0){
                        $onClick = ($login_fabrica != 137) ? "exclui_hd_chamado($hd_chamado)" : "motivoExclusao($hd_chamado)";
                        echo "<td> <button type='button' onClick='{$onClick}'>Excluir $texto</button> </td>";
                    }

                    if($login_fabrica == 35){
                        echo "<td>";
                        $sqlV = "
                            SELECT  extrato
                            FROM    tbl_os_extra
                            WHERE   os = $os
                        ";
                        $resV = pg_query($con,$sqlV);
                        $verificaExtrato = pg_fetch_result($resV,0,extrato);

                        if($verificaExtrato == ""){
                            $sqlMO = "
                                SELECT  DISTINCT
                                        status_os
                                FROM    tbl_os_status
                                WHERE   os = $os
                                AND     status_os = 81
                            ";
                            $resMO = pg_query($con,$sqlMO);
                            $status_os_cancela = pg_fetch_result($resMO,0,status_os);
                            if($status_os_cancela == ""){
                                echo "<a href='os_cadastro.php?os=$os&cancela_mao_obra=ok' target='_blank'>Cancelar Mão-obra</a>";
                            }else{
                                echo "&nbsp;";
                            }
                        }else{
                            echo "&nbsp;";
                        }
                        echo "</td>";
                    }

                    echo "</tr>";

                if ($login_fabrica == 7) {
                    echo "<tr>";
                    echo "<td colspan='11'>";
                    echo "&nbsp;";
                    echo "</td>";
                    echo "<td colspan='2'>&nbsp;";
                    echo "<a href='javascript:imprimirSelecionados()' style='font-size:10px'>Imprime Selecionados</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
    echo "</tbody>";
    if($telecontrol_distrib){
        echo "
                <tfoot>
                    <tr class='titulo_coluna' >
                        <td colspan='100%' style='text-align: left;' >
                            Motivo:
                            <input type='text' id='motivo_exclui_os' name='motivo_exclui_os' style='width: 300px;' form='form_exclui_os' />
                            <button type='button' id='button_exclui_os' name='button_exclui_os' >Excluir OS(s)</button>
                        </td>
                    </tr>
            </tfoot>";
    }

        echo "</table>";
        } else {
            if (strlen($btn_acao_pre_os) > 0) {
                echo "Não Existem Pré-Ordens de Serviço.";
            }
        }

            ##### PAGINAÇÃO - INÍCIO #####
            echo "<br />";
            echo "<div>";

            if($pagina < $max_links){
                $paginacao = $pagina + 1;
            }else{
                $paginacao = $pagina;
            }

            // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
            @$todos_links       = $mult_pag->Construir_Links("strings", "sim");

            // função que limita a quantidade de links no rodape
            $links_limitados    = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

            for ($n = 0; $n < count($links_limitados); $n++) {
                echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
            }

            echo "</div>";

            $resultado_inicial = ($pagina * $max_res) + 1;
            $resultado_final   = $max_res + ( $pagina * $max_res);
            $registros         = $mult_pag->Retorna_Resultado();

            $valor_pagina   = $pagina + 1;
            $numero_paginas = intval(($registros / $max_res) + 1);

            if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

            if ($registros > 0){
                echo "<br>";
                echo "<div>";
                echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
                echo "<font color='#cccccc' size='1'>";
                echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
                echo "</font>";
                echo "</div>";
            }
            ##### PAGINAÇÃO - FIM #####
            echo "<br />";

            if (in_array($login_fabrica, array(15, 81, 85, 30, 72, 137)) and pg_num_rows($resxls)) { # HD 193344

                flush();

                $data         = date ("d/m/Y H:i:s");
                $arquivo_nome = "consulta-os-$login_admin.xls";
                #$path         = "/www/assist/www/admin/xls/";
                $path         = "xls/"; // Para teste remover comentário
                $path_tmp     = "/tmp/";

                $arquivo_completo     = $path.$arquivo_nome;
                $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

                echo `rm $arquivo_completo_tmp `;
                echo `rm $arquivo_completo `;

                // Cria um novo relatório
                $excel = new ExcelWriter($arquivo_completo_tmp);

                if(!$excel) {
                    $msg_erro = $excel->error;
                }

                switch($login_fabrica) {
                        case 30 :
                    $titulos = array("OS",
                                    "STATUS",
                                    "SÉRIE",
                                    "AB",
                                    "DATA PEDIDO",
                                    "Nº PEDIDO",
                                    "NOTA FISCAL",
                                    "CÓDIGO",
                                    "DESCRIÇÃO",
                                    "DC",
                                    "FC",
                                    "POSTO",
                                    "NOME POSTO",
                                    "UF",
                                    "CONSUMIDOR/REVENDA",
                                    "TELEFONE",
                                    "NF PRODUTO",
                                    "PRODUTO",
                                    "DEFEITO RECLAMADO",
                                    "END. CONSUMIDOR",
                                    "CIDADE CONSUMIDOR",
                                    "UF CONSUMIDOR",
                                    "DEFEITO CONSTATADO"
                            );
                        break;
                        case 85 :
                            $titulos = array("OS",
                                    "SÉRIE",
                                    "AB",
                                    "FC",
                                    "C / R",
                                    "POSTO",
                                    "CONSUMIDOR/REVENDA",
                                    "TELEFONE",
                                    "PRODUTO",
                                    "DAIS EM ABERTO");
                            break;
                        case 137 :
                                $titulos = array("OS",
                                    "DATA DE ABERTURA",
                                    "CNPJ REVENDA",
                                    "REVENDA",
                                    "CIDADE DA REVENDA",
                                    "ESTADO DA REVENDA",
                                    "NOTA FISCAL ENTRADA",
                                    "NOTA FISCAL SAIDA",
                                    "CFOP",
                                    "DATA NOTA ENTRADA",
                                    "DATA NOTA SAIDA",
                                    "VALOR UNITARIO PRODUTO",
                                    "VALOR TOTAL DA NF",
                                    "PRODUTO",
                                    "TRANSPORTADORA",
                                    "NUMERO LOTE",
                                    "DEFEITO CONSTATADO");
                                break;
                        case 72 :
                                $titulos = array("OS",
                                    "COD. PRODUTO",
                                    "PRODUTO",
                                    "SÉRIE",
                                    "DEFEITO RECLAMADO",
                                    "DEFEITO CONSTATADO",
                                    "SOLUÇÃO",
                                    "AB",
                                    "DC",
                                    "FC",
                                    "Data NF",
                                    "C/R",
                                    "COD. POSTO",
                                    "POSTO",
                                    "CIDADE",
                                    "UF",
                                    "CONSUMIDOR/REVENDA",
                                    "TELEFONE CONSUMIDOR"
                                    );
                                break;
                        case 145:
                            $titulos = array("OS",
                                    "AB",
                                    "DC",
                                    "FC",
                                    "POSTO",
                                    "CONSUMIDOR/REVENDA",
                                    "NF",
                                    "PRODUTO");
                            break;
                        default :
                                $titulos = array("OS",
                                    "SÉRIE",
                                    "AB",
                                    "DC",
                                    "FC",
                                    "POSTO",
                                    "CONSUMIDOR/REVENDA",
                                    "TELEFONE",
                                    "PRODUTO");
                                break;
                }

                $excel->writeLine($titulos, "default_title");

                $old_os = 0;

                for($x =0;$x<pg_num_rows($resxls);$x++) {

                    $sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
                    $nota_fiscal        = trim(pg_fetch_result($resxls,$x,nota_fiscal));
                    $digitacao          = trim(pg_fetch_result($resxls,$x,digitacao));
                    $abertura           = trim(pg_fetch_result($resxls,$x,abertura));
                    $fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
                    $finalizada         = trim(pg_fetch_result($resxls,$x,finalizada));
                    $data_conserto      = trim(@pg_fetch_result($resxls,$x,data_conserto));
                    $serie              = trim(pg_fetch_result($resxls,$x,serie));
                    $consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
                    $consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
                    $codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
                    $posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
                    $situacao_posto     = trim(pg_fetch_result($resxls,$i,credenciamento));
                    $uf_posto           = pg_fetch_result($resxls, $x, "contato_estado");
                    $produto_referencia = trim(pg_fetch_result($resxls,$x,produto_referencia));
                    $produto_descricao  = trim(pg_fetch_result($resxls,$x,produto_descricao));
                    $produto_voltagem   = trim(pg_fetch_result($resxls,$x,produto_voltagem));
                    $consumidor_revenda = trim(pg_fetch_result($resxls,$x,consumidor_revenda));
                    $data_nf            = trim(pg_fetch_result($resxls,$x,data_nf));
                    $revenda_cnpj       = trim(pg_fetch_result($resxls,$x,revenda_cnpj));
                    $revenda_nome        = trim(pg_fetch_result($resxls,$x,revenda_nome));
                    $status_checkpoint  = trim(pg_fetch_result($resxls,$x,status_checkpoint));
                    $defeito_constatado = trim(pg_fetch_result($resxls,$x,defeito_constatado));
                    $rg_produto         = trim(pg_fetch_result($resxls,$x,rg_produto));

                    if($login_fabrica == 72){
                        $defeito_reclamado_descricao    = trim(pg_fetch_result($resxls,$x,defeito_reclamado_descricao));
                        $defeito_constatado             = trim(pg_fetch_result($resxls,$x,defeito_constatado));
                        $solucao_os                     = trim(pg_fetch_result($resxls,$x,solucao_os));
                        $contato_estado                 = trim(pg_fetch_result($resxls,$x,contato_estado));
                        $contato_cidade                 = trim(pg_fetch_result($resxls,$x,contato_cidade));
                    }

                    $sql_revenda = "SELECT tbl_cidade.nome, tbl_cidade.estado
                                    FROM tbl_cidade
                                    JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
                                    JOIN tbl_os ON tbl_os.revenda = tbl_revenda.revenda AND tbl_os.os = {$os}";
                    $res_revenda = pg_query($con, $sql_revenda);

                    if(pg_num_rows($res_revenda) > 0){
                        $cidade_revenda = pg_fetch_result($res_revenda, 0, 'nome');
                        $estado_revenda = pg_fetch_result($res_revenda, 0, 'estado');
                    }

                    if(!empty($sua_os)){

                        $sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$sua_os}";
                        $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                        if(pg_num_rows($res_dias_aberto) == 1){
                            $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                            $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                        }

                    }
                    // CHAMADO 2528193 TODAS AS FABRICA RECEBERÃO consumidor/revenda
                    $nome_consumidor_revenda = ($consumidor_revenda == "C") ? $consumidor_nome : $revenda_nome;

                    if($login_fabrica == 30){
                        $os = pg_fetch_result($resxls, $x, 'os');
                        $consumidor_endereco = trim(pg_fetch_result($resxls,$x,consumidor_endereco));
                        $consumidor_cidade = trim(pg_fetch_result($resxls,$x,consumidor_cidade));
                        $consumidor_estado = trim(pg_fetch_result($resxls,$x,consumidor_estado));
                        $defeito_constatado = trim(pg_fetch_result($resxls,$x,defeito_constatado));
                        $defeito_reclamado_os = trim(pg_fetch_result($resxls,$x,defeito_reclamado_os));

                        $pedido = pg_fetch_result($resxls, $x, 'pedido');

                        if (!empty($pedido)) {
                            $digitacao_item = pg_fetch_result($resxls, $x, 'digitacao_item');
                            $nf = pg_fetch_result($resxls, $x, 'nf_fat') . ' ' . pg_fetch_result($resxls, $x, 'nf_emissao');
                            $peca_referencia = pg_fetch_result($resxls, $x, 'peca_referencia');
                            $peca_descricao = pg_fetch_result($resxls, $x, 'peca_descricao');
                        } else {

                            if ($old_os == $os) {
                                continue;
                            }

                            $digitacao_item = '';
                            $nf = '';
                            $peca_referencia = '';
                            $peca_descricao = '';
                        }

                        $old_os = $os;

                        $status_os_xls = exibeImagemStatusCheckpoint($status_checkpoint, $sua_os, true);
                        $titulos = array($sua_os,
                                    $status_os_xls,
                                    "$serie&nbsp;",
                                    $abertura,
                                    $digitacao_item,
                                    $pedido,
                                    $nf,
                                    $peca_referencia,
                                    $peca_descricao,
                                    $data_conserto,
                                    $fechamento,
                                    $codigo_posto,
                                    $posto_nome,
                                    $uf_posto,
                                    $nome_consumidor_revenda,
                                    $consumidor_fone,
                                    $nota_fiscal,
                                    $produto_referencia . "-" . $produto_descricao,
                                    $defeito_reclamado_os,
                                    $consumidor_endereco,
                                    $consumidor_cidade,
                                    $consumidor_estado,
                                    $defeito_constatado);
                    }else if($login_fabrica == 85){

                        switch ($consumidor_revenda) {
                            case "C":
                                $consumidor_revenda = "CONS";
                            break;

                            case "R":
                                $consumidor_revenda = "REV";
                            break;

                            case "":
                                $consumidor_revenda = "";
                            break;
                        }
                        $titulos = array($sua_os,
                                        "$serie&nbsp;",
                                        $abertura,
                                        $fechamento,
                                        $consumidor_revenda,
                                        $posto_nome,
                                        $nome_consumidor_revenda,
                                        $consumidor_fone,
                                        $produto_referencia . "-" . $produto_descricao,
                                        $dias_aberto);
                    }elseif($login_fabrica == 137){

                        $os = pg_fetch_result($resxls, $x, 'os');

                        $sql_dados_adicionais = "SELECT obs_adicionais FROM tbl_os_extra WHERE os = {$os}";
                        $res_dados_adicionais = pg_query($con, $sql_dados_adicionais);
                        if(pg_num_rows($res_dados_adicionais) > 0){
                            $dados_adicionais   = pg_fetch_result($res_dados_adicionais, 0, 'obs_adicionais');
                            $dados_adicionais   = json_decode($dados_adicionais);

                            $nota_fiscal_saida          = $dados_adicionais->nota_fiscal_saida;
                            $data_nota_fiscal_saida     = $dados_adicionais->data_nota_fiscal_saida;
                            $transportadora             = $dados_adicionais->transportadora;
                        }

                        list($ano, $mes, $dia) = explode("-", $data_nf);
                        $data_nf = $dia."/".$mes."/".$ano;

                        $dados          = json_decode($rg_produto);
                        $cfop           = $dados->cfop;
                        $valor_unitario = "R$ ".$dados->vu;
                        $valor_total    = "R$ ".$dados->vt;

                        $titulos = array($sua_os,
                                    $abertura,
                                    $revenda_cnpj,
                                    $revenda_nome,
                                    $cidade_revenda, // new
                                    $estado_revenda, // new
                                    $nota_fiscal,
                                    $nota_fiscal_saida, // new
                                    $cfop,
                                    $data_nf,
                                    $data_nota_fiscal_saida, // new
                                    $valor_unitario,
                                    $valor_total,
                                    $produto_referencia . "-" . $produto_descricao,
                                    $transportadora, // new
                                    $serie,
                                    $defeito_constatado);

                    }elseif($login_fabrica == 72){
                        switch ($consumidor_revenda) {
                            case "C":
                                $consumidor_revenda = "CONS";
                            break;

                            case "R":
                                $consumidor_revenda = "REV";
                            break;

                            case "":
                                $consumidor_revenda = "";
                            break;
                        }
                        $titulos = array($sua_os,
                                        $produto_referencia,
                                        $produto_descricao,
                                        "$serie&nbsp;",
                                        $defeito_reclamado_descricao,
                                        $defeito_constatado,
                                        $solucao_os,
                                        $abertura,
                                        $data_conserto,
                                        $fechamento,
                                        $data_nf,
                                        $consumidor_revenda,
                                        $codigo_posto,
                                        $posto_nome,
                                        $contato_cidade,
                                        $contato_estado,
                                        $nome_consumidor_revenda,
                                        $consumidor_fone
                                        );
                    }else if($login_fabrica == 145) {
                        $titulos = array($sua_os,
                                        $abertura,
                                        $data_conserto,
                                        $fechamento,
                                        $codigo_posto,
                                        $nome_consumidor_revenda,
                                        $nota_fiscal,
                                        $produto_referencia . "-" . $produto_descricao);
                    } else if (isset($novaTelaOs) && $login_fabrica != 145) {
                        $titulos = array($sua_os,
                                        "$serie&nbsp;",
                                        $abertura,
                                        $data_conserto,
                                        $fechamento,
                                        $codigo_posto,
                                        $nome_consumidor_revenda,
                                        $nota_fiscal,
                                        $produto_referencia . "-" . $produto_descricao);
                    }else{
                        $titulos = array($sua_os,
                                        "$serie&nbsp;",
                                        $abertura,
                                        $data_conserto,
                                        $fechamento,
                                        $codigo_posto,
                                        $nome_consumidor_revenda,
                                        $consumidor_fone,
                                        $produto_referencia . "-" . $produto_descricao);
                    }
                    $excel->writeLine($titulos, "default_content");
                }

                $excel->close();

                echo ` cp $arquivo_completo_tmp $path `;

                $data = date("Y-m-d").".".date("H-i-s");

                //echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;

                $resposta .= "<br>";
                $resposta .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
                $resposta .= "<tr>";
                $resposta .= "<td colspan=\"$colspan_excel\" style='border: 0; font: bold 14px \"Arial\";'><a href=\"xls/$arquivo_nome\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>";
                $resposta .= "";
                $resposta .= "</tr>";
                $resposta .= "</table>";
                echo $resposta;
                echo "<br/>";
            }elseif(pg_num_rows($resxls) > 0 && $login_fabrica != 137) { # HD 193344
                $host   = $_SERVER['SCRIPT_NAME'];
                $host   = str_replace('admin_cliente','admin',$host);
                $host   = str_replace('/os_consulta_lite.php','',$host);
                $path_2 = getcwd();
                $path_2 = str_replace('admin_cliente','admin/',$path_2);

                flush();
                $data = date ("d/m/Y H:i:s");

                $path             = "/xls/";
                $artquivo_nome    = "consulta-os-$login_fabrica-$login_admin.xls";
                $arquivo_completo = $path_2.$path.$artquivo_nome;
                $caminho_donwload = $host.$path.$artquivo_nome;
                $fp = fopen ($arquivo_completo,"w+");
                $fp = fopen($arquivo_completo, "a");

                fputs ($fp,"<table border='1' bordercolor='#000000'  align='center' width='100%'>");
                fputs ($fp,"<td nowrap><b>OS</b></td>");
                if ($login_fabrica == 52) {
                    fputs ($fp,"<td nowrap><b>Nº Atendiment</b></td>");
                    fputs ($fp,"<td nowrap><b>Número Ativo</b></td>");
                }
                if($login_fabrica == 35){
                     fputs ($fp,"<td nowrap><b>Nº Atendiment</b></td>");
                }
                if(!in_array($login_fabrica,array(1,3,20,81,145))){
                    fputs ($fp,"<td nowrap><b>SÉRIE</b></td>");
                }
                fputs ($fp,"<td nowrap><b>AB</b></td>");
                if($login_fabrica == 6){
                    fputs ($fp,"<td nowrap><b>DATA NF</b></td>");
                }

                if($mostra_data_conserto){
                    fputs ($fp,"<td nowrap><b>DC</b></td>");
                }else{
                    fputs ($fp,"<td nowrap><b>FC</b></td>");
                }
                #fputs ($fp,"<td nowrap><b>FC</b></td>");
                if (!in_array($login_fabrica, array(141,144))) {
                    fputs ($fp,"<td nowrap><b>C/R</b></td>");
                } else {
                    fputs ($fp,"<td nowrap><b>TIPO DE ATENDIMENTO</b></td>");
                }
                if($login_fabrica == 152){
                    fputs ($fp,"<td nowrap><b>Tempo p/ defeito</b></td>");
                }
                fputs ($fp,"<td nowrap><b>POSTO</b></td>");
                fputs ($fp,"<td nowrap><b>CIDADE</b></td>");
                fputs ($fp,"<td nowrap><b>ESTADO</b></td>");
                if ($login_fabrica == 11) {
                    fputs ($fp,"<td nowrap><b>SITUAÇÃO POSTO</b></td>");
                }
                fputs ($fp,"<td nowrap><b>CONSUMIDOR/REVENDA</b></td>");
                if (!in_array($login_fabrica, array(141,144)) && !isset($novaTelaOs)) {
                    fputs ($fp,"<td nowrap><b>TELEFONE</b></td>");
                } else {
                    fputs ($fp,"<td nowrap><b>NOTA FISCAL</b></td>");
                }

                if($login_fabrica == 141){ // HD-2386867
                    fputs ($fp,"<td nowrap><b>UF CONSUMIDOR</b></td>");
                    fputs ($fp,"<td nowrap><b>UF POSTO</b></td>");
                }

                if ($login_fabrica == 80) {
                    fputs($fp,"<td nowrap><b>DATA DE COMPRA</b></td>");
                }
                if($login_fabrica == 6){
                    fputs ($fp,"<td nowrap><b>E-MAIL CONSUMIDOR</b></td>");
                    fputs ($fp,"<td nowrap><b>NOME REVENDA</b></td>");
                    fputs ($fp,"<td nowrap><b>CNPJ REVENDA</b></td>");
                    fputs ($fp,"<td nowrap><b>NF DA REVENDA</b></td>");
                }
                fputs ($fp,"<td nowrap><b>PRODUTO</b></td>");
                if($login_fabrica == 85){
                    fputs($fp, "<td nowrap><b>DIAS EM ABERTO</b></td>");
                }
                if(in_array($login_fabrica, array(3,52,86)) or $multimarca =='t'){
                      fputs($fp, "<td nowrap><b>MARCA</b></td>");
                }
                if(in_array($login_fabrica, array(115,116,117,120))){
                      fputs($fp, "<td nowrap><b>KM</b></td>");
                }

                fputs ($fp,"<td nowrap><b>STATUS</b></td>");
                if($login_fabrica == 6){
                    fputs ($fp,"<td nowrap><b>OBSERVAÇÃO</b></td>");
                    fputs ($fp,"<td nowrap><b>ORIENTAÇÃO</b></td>");
                }
                fputs ($fp,"<td nowrap><b>SITUAÇÃO</b></td>");
                
                fputs ($fp,"</tr>");
                for($x =0;$x<pg_num_rows($resxls);$x++) {
                    $cor                = "";
                    $sua_os             = "";
                    $hd_chamado         = "";
                    $numero_ativo_res   = "";
                    $nota_fiscal        = "";
                    $digitacao          = "";
                    $abertura           = "";
                    $consumidor_revenda = "";
                    $fechamento         = "";
                    $finalizada         = "";
                    $data_conserto      = "";
                    $serie              = "";
                    $consumidor_nome    = "";
                    $consumidor_fone    = "";
                    $codigo_posto       = "";
                    $posto_nome         = "";
                    $produto_referencia = "";
                    $produto_descricao  = "";
                    $produto_voltagem   = "";
                    $marca_logo_nome    = "";
                    $situacao_posto     = "";
                    $data_nf            = "";
                    $cidade_uf          = "";

                    $cor   = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

                    $os                 =  trim(pg_fetch_result($resxls,$x,os));
                    $sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
                    $hd_chamado         = trim(pg_fetch_result($resxls,$x,hd_chamado));
                    if ($login_fabrica == 52){
                        $numero_ativo_res   = trim(pg_fetch_result($resxls,$x,ordem_ativo));
                    }

                    $cidade_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_cidade));
                    $estado_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_estado));

                    $cidade_uf          = $cidade_posto_xls."/".$estado_posto_xls;

                    $nota_fiscal        = trim(pg_fetch_result($resxls,$x,nota_fiscal));
                    $digitacao          = trim(pg_fetch_result($resxls,$x,digitacao));
                    $abertura           = trim(pg_fetch_result($resxls,$x,abertura));
                    $consumidor_revenda = trim(pg_fetch_result($resxls,$x,consumidor_revenda));
                    $fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
                    $finalizada         = trim(pg_fetch_result($resxls,$x,finalizada));
                    $data_conserto      = trim(@pg_fetch_result($resxls,$x,data_conserto));
                    $serie              = trim(pg_fetch_result($resxls,$x,serie));
                    $reincidencia       = trim(pg_fetch_result($resxls,$x,reincidencia));
                    $consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
                    $excluida           = trim(pg_fetch_result($resxls,$x,excluida));
                    $consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
                    $data_nf            = trim(pg_fetch_result($resxls,$x,data_nf));
                    $codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
                    $posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
                    $produto_referencia = trim(pg_fetch_result($resxls,$x,produto_referencia));
                    $status_os          = trim(pg_fetch_result($resxls,$x,status_os));
                    $produto_descricao  = trim(pg_fetch_result($resxls,$x,produto_descricao));
                    $produto_voltagem   = trim(pg_fetch_result($resxls,$x,produto_voltagem));
                    $status_checkpoint  = trim(pg_fetch_result($resxls,$x,status_checkpoint));
                    $marca_logo         = trim(pg_fetch_result($resxls,$x,marca));
                    $situacao_posto     = trim(pg_fetch_result($resxls,$x,credenciamento));
                    $revenda_nome       = trim(pg_fetch_result($resxls,$x,revenda_nome));
                    $obs                = trim(pg_fetch_result($resxls,$x,obs));
                    $nome_consumidor_revenda = ($consumidor_revenda == "C") ? $consumidor_nome : $revenda_nome;

                    $consumidor_email    = trim(pg_fetch_result($resxls,$x,consumidor_email));
                    $revenda_cnpj_tec    = trim(pg_fetch_result($resxls,$x,revenda_cnpj));
                    $revenda_nome_tec    = trim(pg_fetch_result($resxls,$x,revenda_nome));


                    if(in_array($login_fabrica,array(115,116,117,120))){
                        $valor_km = trim(pg_fetch_result($resxls,$x,valor_km));
                    }

                    if (in_array($login_fabrica, array(141,144))) {
                        $tipo_atendimento = pg_fetch_result($resxls, $x, tipo_atendimento);
                        $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                        $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                        $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
                    }
                    $tempo_para_defeito = trim(pg_fetch_result($resxls,$x,tempo_para_defeito));

                    unset($marca_reincidencia);
                    if ($reincidencia =='t' and $login_fabrica != 1) {
                        if($login_fabrica == 87) $cor = "#40E0D0"; else $cor = "#D7FFE1";
                        $marca_reincidencia = 'sim';
                    }
                    if ($login_fabrica==20 AND $status_os == "94" AND $excluida == "t"){
                        $cor = "#CACACA";
                    }
                    $vintecincodias = "";

                    if ($login_fabrica == 91 && $status_os == 179) {
                        $cor="#FFCCCC";
                    }

                    if ($login_fabrica == 91 && $status_os == 13) {
                        $cor = "#CB82FF";
                    }

                    if($login_fabrica == 114){
                        if ($status_os == "62") {
                            $cor = ($login_fabrica == 114) ? "#FFCCCC" : "#E6E6FA";
                        }
                    }
                    if (in_array($login_fabrica,array(3,11,43,51,87,))) {

                        if ($status_os == "62") {
                            $cor = ($login_fabrica==43 or $login_fabrica==51) ? "#FFCCCC" : "#E6E6FA"; //HD 46730 HD 288642
                        }
                        if (in_array($status_os,array("72","87","116","120","122","140","141"))){
                            $cor="#FFCCCC";
                        }

                        if($login_fabrica == 87 AND ($cor == "#FFCCCC" OR $cor == "#E6E6FA")) {
                            $cor = "#FFA5A4";
                        }

                        if (($status_os=="64" OR $status_os=="73"  OR $status_os=="88" OR $status_os=="117") && strlen($fechamento)==0) {
                            if($login_fabrica == 87){
                                $cor = "#FEFFA4";
                            }else{
                                $cor = "#00EAEA";
                            }
                        }
                        if ($status_os=="65"){
                            $cor="#FFFF99";
                        }
                    }

                    if (in_array($login_fabrica, array(141,144))) {
                        switch ($status_os) {
                            case 192:
                                $cor = "#FFCCCC";
                                break;

                            case 193:
                                $cor = "#CCFFFF";
                                break;

                            case 194:
                                $cor = "#CB82FF";
                                break;
                        }
                    }

                    if ($login_fabrica==94){
                        $sqlI = "SELECT status_os
                                FROM    tbl_os_status
                                WHERE   os = $os
                                AND     status_os IN (62,64)
                          ORDER BY      data DESC
                                LIMIT   1";
                        $resI = pg_query ($con,$sqlI);
                        if (pg_num_rows ($resI) > 0){
                            $status_os = trim(pg_fetch_result($resI,0,status_os));
                            if ($status_os <> 64) {
                                $cor="#FFCCCC";
                            }
                        }
                    }

                    if ($login_fabrica == 3 && $status_os == 174) {
                        $cor = "#CB82FF";
                    }

                    if($status_os == "175"){
                        $cor = "#A4A4A4";
                    }


                    // OSs abertas há mais de 25 dias sem data de fechamento
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '".(($login_fabrica == 91) ? "30" : "25")." days','YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                            if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                            $vintecincodias = "sim";
                        }
                    }
                    // OSs abertas há mais de 10 dias sem data de fechamento - Nova
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 43) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                            $cor = "#FF0033";
                        }
                    }

                    // CONDIÇÕES PARA INTELBRÁS - INÍCIO
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_atual = pg_fetch_result($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                            if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                        }

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
                    }
                    // CONDIÇÕES PARA INTELBRÁS - FIM

                    // CONDIÇÕES PARA COLORMAQ - INÍCIO
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 50) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_atual = pg_fetch_result($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                            if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                        }

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                            $cor = "#FF6633";
                        }

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                            $cor = "#FF0000";
                        }
                    }

                    if($excluida=='t' AND ($login_fabrica==50 or $login_fabrica ==14)){//HD 37007 5/9/2008
                        $cor = "#FFE1E1";
                    }
                    // CONDIÇÕES PARA COLORMAQ - FIM

                    // CONDIÇÕES PARA NKS - INÍCIO
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 45) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);
                        $sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta2 = pg_fetch_result($resX,0,0);

                        if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#1e85c7";

                        $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '25 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta2 = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta3 = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta2 <= $aux_consulta3 AND $aux_consulta3 <= $aux_consulta && strlen($fechamento) == 0) $cor = "#FF6633";

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta2 = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#9512cc";
                    }
                    // CONDIÇÕES PARA NKS - FIM


                    //HD 163220 - Colocar legenda nas OSs com atendimento Procon/Jec (Jurídico) - tbl_hd_chamado.categoria='procon'
                    if ($login_fabrica == 11) {
                        $sql_procon = "
                        SELECT
                        tbl_hd_chamado.hd_chamado

                        FROM
                        tbl_hd_chamado
                        JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado

                        WHERE
                        tbl_hd_chamado_extra.os=$os
                        AND tbl_hd_chamado.categoria IN ('pr_reclamacao_at', 'pr_info_at', 'pr_mau_atend', 'pr_posto_n_contrib', 'pr_demonstra_desorg', 'pr_bom_atend', 'pr_demonstra_org')
                        ";
                        $res_procon = pg_query($con, $sql_procon);

                        if (pg_num_rows($res_procon)) {
                            $cor = "#C29F6A";
                        }
                    }

                    // Verifica se está sem fechamento há 20 dias ou mais da data de abertura
                    if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $aux_atual = pg_fetch_result($resX,0,0);

                        if ($consumidor_revenda != "R") {
                            if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                                $mostra_motivo = 1;
                                if($login_fabrica == 87){
                                    $cor = "#A4B3FF";
                                }else{
                                    $cor = "#91C8FF";
                                }
                            }
                        }
                    }

                    $sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os ORDER BY data desc limit 1 ";
                    $resX = @pg_query($con,$sqlX);
                    if(@pg_num_rows($resX)==1){
                        $cor = (pg_fetch_result($resX,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
                    }
                                        // CONDIÇÕES PARA BLACK & DECKER - INÍCIO
                    // Verifica se não possui itens com 5 dias de lançamento
                    if ($login_fabrica == 1) {
                        $aux_abertura = fnc_formata_data_pg($abertura);

                        $sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
                        $resX = pg_query($con,$sqlX);
                        $data_hj_mais_5 = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sql = "SELECT COUNT(tbl_os_item.*) AS total_item
                                FROM tbl_os_item
                                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
                                WHERE tbl_os.os = $os
                                AND   tbl_os.data_abertura::date >= '$aux_consulta'";
                        $resItem = pg_query($con,$sql);

                        $itens = pg_fetch_result($resItem,0,total_item);

                        if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#ffc891";

                        $mostra_motivo = 2;
                    }
                    // CONDIÇÕES PARA BLACK & DECKER - FIM

                    // Gama
                    if ($login_fabrica==51){ // HD 65821
                        $sqlX = "SELECT status_os,os FROM tbl_os JOIN tbl_os_status USING(os) WHERE os = $os AND status_os = 13";
                        $resX = pg_query($con,$sqlX);
                        if(pg_num_rows($resX)> 0){
                            $cor = "#CACACA";
                        }
                    }

                    if ($login_fabrica == 94 AND strlen($os) > 0) {

                        $sqlT = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                        $resT = pg_query($con, $sqlT);

                        if (pg_num_rows($resT)) {
                            $cor = "silver";
                        }

                    }

                    //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
                    if ($fabrica_autoriza_troca_revenda && strlen($os)) {
                        $sql = "
                        SELECT
                        troca_revenda

                        FROM
                        tbl_os_troca

                        WHERE
                        os=$os
                        ";
                        $res_troca_revenda = pg_query($con, $sql);

                        if (pg_num_rows($res_troca_revenda)) {
                            $troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
                        }
                        else {
                            $troca_revenda = "";
                        }
                    }

                    if ($troca_revenda == 't') {
                        $cor = "#d89988";
                    }

                    if ($vintecincodias == 'sim' and $marca_reincidencia == 'sim') {
                        if($login_fabrica == 87) $cor = "#D2D2D2"; else $cor = "#CC9900";
                    }

                    // CONDIÇÕES PARA GELOPAR - INÍCIO
                    if($login_fabrica==85 AND strlen($os)>0){
                        $sqlG = "SELECT
                                    interv.os
                                FROM (
                                    SELECT
                                    ultima.os,
                                    (
                                        SELECT status_os
                                        FROM tbl_os_status
                                        WHERE status_os IN (147)
                                        AND tbl_os_status.os = ultima.os
                                        ORDER BY data
                                        DESC LIMIT 1
                                    ) AS ultimo_status
                                    FROM (
                                            SELECT os FROM tbl_os WHERE tbl_os.os = $os
                                    ) ultima
                                ) interv
                                WHERE interv.ultimo_status IN (64,147);";
                                #echo nl2br($sqlG);
                        $resG = pg_exec($con,$sqlG);

                        if(pg_numrows($resG)>0){
                            $cor = "#AEAEFF";
                        }
                    }
                    // CONDIÇÕES PARA GELOPAR - FIM

                    ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

                    if (strlen($sua_os) == 0){
                        $sua_os = $os;
                    }
                    if ($login_fabrica == 1) {
                        $sua_os2 = $codigo_posto.$sua_os;
                        $sua_os = "<a href='etiqueta_print.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";
                    }

                    //HD391024
                    if($login_fabrica == 96 and strlen($btn_acao_pre_os) > 0){
                        if($tipo_atendimento == '92') $cor = "#FFFF66";
                        if($tipo_atendimento == '93') $cor = "#C94040";
                        if($tipo_atendimento == '94') $cor = "#33CC00";
                    }

                    if($login_fabrica == 40 AND $status_os == 118){
                        $cor = "#BFCDDB";
                    }

                    if (in_array($status_os, array(158))){
                        $cor="#FFCCCC";
                    }

                    if ($login_fabrica == 45){
                        //INTERACAO

                        $sqlxyz = "SELECT count(*) from tbl_os_interacao where os = $os";
                        $resxyz = pg_query($con,$sqlxyz);
                        $count_interacao = pg_fetch_result($resxyz, 0, 0);
                        if ($count_interacao > 0){

                            if (strlen(trim($campo_interacao))==0){
                                $cor = "#F98BB2";
                            }
                        }

                        if ($tipo_os != 'INTERACAO'){

                            //OS TROCA - RESOLVIDO
                            $sqlaaa = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os";
                            $resaaa = pg_query($con,$sqlaaa);

                            if (pg_num_rows($resaaa)>0){
                                $cor = "#56BB71";
                            }

                            //OS TROCA - PENDENTE
                            $sqlbbb = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) left join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os and tbl_faturamento_item.faturamento_item is null and tbl_os_troca.ressarcimento is false ";
                            $resbbb = pg_query($con,$sqlbbb);

                            if (pg_num_rows($resbbb)>0){
                                $cor = "#EAEA1E";
                            }
                        }
                    }

                    fputs ($fp,"<tr align='left'>");
                    fputs ($fp,"<td bgcolor='$cor'>".$sua_os."</td>");
                    if ($login_fabrica == 52) {
                        fputs ($fp,"<td bgcolor='$cor'>".$hd_chamado."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$numero_ativo_res."</td>");
                    }
                    if($login_fabrica == 35){
                        fputs ($fp,"<td bgcolor='$cor'>".$hd_chamado."</td>");
                    }
                    if ($login_fabrica == 50) {
                        fputs ($fp,"<td bgcolor='$cor'>".$serie."C</td>");
                    }else if(!in_array($login_fabrica,array(1,3,20,50,81,145))){
                        fputs ($fp,"<td bgcolor='$cor'>".$serie."</td>");
                    }

                    fputs ($fp,"<td bgcolor='$cor'>".$abertura."</td>");
                    if($login_fabrica == 6){
                        fputs ($fp,"<td bgcolor='$cor'>".$data_nf."</td>");
                    }
                    fputs ($fp,"<td bgcolor='$cor'>".$fechamento."</td>");
                    if (!in_array($login_fabrica, array(141,144))) {
                        switch ($consumidor_revenda) {
                            case "C":
                                fputs ($fp,"<td bgcolor='$cor'>CONS</td>");
                            break;

                            case "R":
                                fputs ($fp,"<td bgcolor='$cor'>REV</td>");
                            break;

                            case "":
                                fputs ($fp,"<td bgcolor='$cor'>&nbsp;</td>");
                            break;
                        }
                    } else {
                        fputs ($fp,"<td bgcolor='$cor'>".$desc_tipo_atendimento."</td>");
                    }

					if($login_fabrica == 152){
	                    fputs ($fp,"<td bgcolor='$cor'>".$tempo_para_defeito."</td>");
					}
                    fputs ($fp,"<td bgcolor='$cor'>".$codigo_posto."-".$posto_nome."</td>");
                    fputs ($fp,"<td bgcolor='$cor'>$cidade_posto_xls</td>");
                    fputs ($fp,"<td bgcolor='$cor' align='center'>$estado_posto_xls</td>");
                    if ($login_fabrica == 11) {
                        fputs ($fp,"<td bgcolor='$cor'>".$situacao_posto."</td>");
                    }
                    fputs ($fp,"<td bgcolor='$cor'>".$nome_consumidor_revenda."</td>");

                    if (!in_array($login_fabrica, array(141,144)) && !isset($novaTelaOs)) {
                        fputs ($fp,"<td bgcolor='$cor'>".$consumidor_fone."</td>");
                    } else {
                        fputs ($fp,"<td bgcolor='$cor'>".$nota_fiscal."</td>");
                    }

                    if($login_fabrica == 141){ // HD-2386867
                        $sqlEstados = "SELECT tbl_os.consumidor_estado,
                                            tbl_cidade.estado,
                                            tbl_posto_fabrica.contato_estado
                                        FROM tbl_os
                                        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                                        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                        JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
                                        JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                                        WHERE os = $os
                                        ";
                        $resEstados = pg_query($con, $sqlEstados);
                        if(pg_num_rows($resEstados) > 0){
                            if($consumidor_revenda == "R" ){
                                $uf_consumidor = pg_fetch_result($resEstados, 0, 'estado');
                            }else{
                                $uf_consumidor = pg_fetch_result($resEstados, 0, 'consumidor_estado');
                            }

                            $uf_posto = pg_fetch_result($resEstados, 0, 'contato_estado');
                        }
                        fputs ($fp,"<td bgcolor='$cor'>".$uf_consumidor."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$uf_posto."</td>");
                    }


                    if ($login_fabrica == 80) {
                        fputs ($fp,"<td bgcolor='$cor'>".$data_nf."</td>");
                    }
                    if($login_fabrica == 6){
                        fputs ($fp,"<td bgcolor='$cor'>".$consumidor_email."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$revenda_nome_tec."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$revenda_cnpj_tec."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$nota_fiscal."</td>");
                    }
                    fputs ($fp,"<td bgcolor='$cor'>".$produto_referencia."-".$produto_descricao."</td>");

                    if($login_fabrica == 85){

                        if(!empty($sua_os)){

                            $sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$sua_os}";
                            $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                            if(pg_num_rows($res_dias_aberto) == 1){
                                $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                                $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                            }

                        }

                        fputs ($fp,"<td bgcolor='$cor'>".$dias_aberto."</td>");

                    }
                    if(in_array($login_fabrica, array(3,52,86)) or $multimarca =='t'){

                        if($login_fabrica ==52 and !empty($marca_logo)){
                            $sqlx="select nome from  tbl_marca where marca = $marca_logo;";
                            $resx=pg_exec($con,$sqlx);
                            $marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');

                            fputs ($fp,"<td bgcolor='$cor'>".$marca_logo_nome."</td>");
                        }else {
                            fputs ($fp,"<td bgcolor='$cor'>".$marca_nome."</td>");
                        }
                    }
                    if(in_array($login_fabrica,array(115,116,117,120))){
                       fputs ($fp,"<td  bgcolor='$cor' >".number_format($valor_km,2,',','.')."</td>");
                    }

                    if($login_fabrica == 6){
                        $sql = "select orientacao_sac from tbl_os_extra where os = $os";
                        $res = pg_query($con, $sql);
                        if(pg_num_rows($res)>0){
                            $orientacao_sac = pg_fetch_result($res, 0, orientacao_sac);
                        }
                    }

                    fputs ($fp,"<td bgcolor='$cor'>".$array_cor_descricao[$status_checkpoint]."</td>");
                    
                    if($login_fabrica == 6){
                        $quebra_linha   = array('<br>', '<br/>', '<br />', '\n', '\t', '\r\n');
                        $obs            = str_replace($quebra_linha, "", $obs);
                        $orientacao_sac = str_replace($quebra_linha, "", $orientacao_sac);

                        fputs ($fp,"<td bgcolor='$cor'>$obs</td>");
                        fputs ($fp,"<td bgcolor='$cor'>$orientacao_sac</td>");
                    }                    

                    if ($login_fabrica == 96 && strlen($btn_acao_pre_os) > 0) {
                        switch ($cor) {
                            case '#C94040':
                                fputs ($fp,"<td bgcolor='$cor'>Retorno de garantia</td>");
                                break;
                            case '#FFFF66':
                                 fputs ($fp,"<td bgcolor='$cor'>Garantia</td>");
                                break;
                            case '#33CC00':
                                 fputs ($fp,"<td bgcolor='$cor'>Retorno de garantia</td>");
                                break;
                            default:
                                fputs ($fp,"<td bgcolor='$cor'></td>");
                                break;
                        }
                    }else{
                        switch ($cor) {

                            case '#FFE1E1':
                                 fputs ($fp,"<td bgcolor='$cor'>Excluidas do sistema</td>");
                                break;
                            case '#40E0D0':
                                 fputs ($fp,"<td bgcolor='$cor'>Reincidências</td>");
                                break;
                            case '#D7FFE1':
                                 fputs ($fp,"<td bgcolor='$cor'>Reincidências</td>");
                                break;
                            case '#ffc891':
                                 fputs ($fp,"<td bgcolor='$cor'>OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</td>");
                                break;
                            case '#FFCC66':
                                fputs ($fp,"<td bgcolor='$cor'>OS com Troca de Produto</td>");
                                break;
                            case '#FF0000':
                                if ($login_fabrica == 14) {
                                    fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de 5 dias sem data de fechamento</td>");
                                }elseif ($login_fabrica == 50 ) {
                                    fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de 20 dias sem data de fechamento</td>");
                                }elseif($login_fabrica ==35){
                                    fputs ($fp,"<td bgcolor='$cor'>Excluídas do sistema</td>");
                                }elseif ($login_fabrica == 3 OR $login_fabrica == 11 OR $login_fabrica == 45) {
                                    fputs ($fp,"<td bgcolor='$cor'>Excluídas do sistema</td>");
                                }elseif ($login_fabrica == 30 && strlen($btn_acao_pre_os) > 0) {
                                    fputs ($fp,"<td bgcolor='$cor'>OS Abertas a mais de 72 horas </td>");
                                }
                                break;
                            case '#AEAEFF':
                                 fputs ($fp,"<td bgcolor='$cor'>Peça fora da garantia aprovada na intervenção da OS para gerar pedido</td>");
                                break;
                            case '#91C8FF':
                                if ($login_fabrica == 14) {
                                    fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de 3 dias sem data de fechamento</td>");
                                }elseif ($login_fabrica == 50) {
                                    fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de 5 dias sem data de fechamento</td>");
                                }else{
                                    fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de ".(($login_fabrica == 91) ? "30" : "25" )." dias sem data de fechamento</td>");
                                }
                                break;
                            case '#FF6633':
                                if ($login_fabrica==50) {
                                    fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de 10 dias sem data de fechamento</td>");
                                }elseif ($login_fabrica==45) {
                                    fputs ($fp,"<td bgcolor='$cor'>MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)</td>");
                                }
                                break;
                            case '#1e85c7':
                                 fputs ($fp,"<td bgcolor='$cor'>BOM (OSs abertas até 15 dias sem data de fechamento)</td>");
                                break;
                            case '#9512cc':
                                 fputs ($fp,"<td bgcolor='$cor'>RUIM (OSs abertas a mais de 25 dias sem data de fechamento)</td>");
                                break;

                            case '#FF0033':
                                 fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de 10 dias sem data de fechamento</td>");
                                break;
                            case '#A4B3FF':
                                 fputs ($fp,"<td bgcolor='$cor'>OSs abertas há mais de ".(($login_fabrica == 91) ? "30" : "25" )." dias sem data de fechamento</td>");
                                break;
                            case '#FFCCCC':
                                 fputs ($fp,"<td bgcolor='$cor'>OS com Intervenção da Fábrica. Aguardando Liberação</td>");
                                break;
                            case '#FFA5A4':
                                 fputs ($fp,"<td bgcolor='$cor'>OS com Intervenção da Fábrica. Aguardando Liberação</td>");
                                break;
                            case '#FFFF99':
                                 fputs ($fp,"<td bgcolor='$cor'>OS com Intervenção da Fábrica. Reparo na Fábrica</td>");
                                break;
                            case '#FEFFA4':
                                 fputs ($fp,"<td bgcolor='$cor'>OS Liberada Pela Fábrica</td>");
                                break;
                            case '#00EAEA':
                                 fputs ($fp,"<td bgcolor='$cor'>OS Liberada Pela Fábrica</td>");
                                break;
                            case '#CCCCFF':
                                 fputs ($fp,"<td bgcolor='$cor'>OS com Ressarcimento</td>");
                                break;
                            case '#CACACA':
                                if($login_fabrica == 51){
                                    fputs ($fp,"<td bgcolor='$cor'>OS Recusada do extrato</td>");
                                }else{
                                    fputs ($fp,"<td bgcolor='$cor'>OS Reprovada pelo Promotor</td>");
                                }
                                break;
                            case '#d89988':
                                 fputs ($fp,"<td bgcolor='$cor'>Autorização de Devolução de Venda</td>");
                                break;
                            case '#CC9900':
                                 fputs ($fp,"<td bgcolor='$cor'>OS reincidente e aberta a mais de 25 dias</td>");
                                break;
                            case '#D2D2D2':
                                 fputs ($fp,"<td bgcolor='$cor'>OS reincidente e aberta a mais de 25 dias</td>");
                                break;
                            case '#FFFF66':
                                 fputs ($fp,"<td bgcolor='$cor'>OS Abertas a mais de 24 horas e menos de 72 horas</td>");
                                break;
                            case '#33CC00':
                                 fputs ($fp,"<td bgcolor='$cor'>OS Abertas a menos de 24 horas</td>");
                                break;
                            case '#BFCDDB':
                                 fputs ($fp,"<td bgcolor='$cor'>OS com 3 ou mais peças</td>");
                                break;
                            case '#silver':
                                 fputs ($fp,"<td bgcolor='$cor'>OS foi Aberta automaticamente por causa de uma troca gerada</td>");
                                break;
                            case '#A4A4A4':
                                 fputs ($fp,"<td bgcolor='$cor'>OS com intervenção de display</td>");
                                break;
                            case '#CB82FF':
                                if (in_array($login_fabrica, array(141,144))) {
                                    fputs ($fp,"<td bgcolor='$cor'>OS com troca de produto recusada</td>");
                                 }elseif ($login_fabrica == 3) {
                                    fputs ($fp,"<td bgcolor='$cor'>OS com pendência de fotos</td>");
                                 }else{
                                    fputs ($fp,"<td bgcolor='$cor'>OS recusada pela fábrica</td>");
                                 }
                                break;
                            case '#F98BB2':
                                 fputs ($fp,"<td bgcolor='$cor'>Os com Interação do Posto</td>");
                                break;
                            case '#56BB71':
                                 fputs ($fp,"<td bgcolor='$cor'>Os com Troca de Produtos - Resolvidos</td>");
                                break;
                            case '#EAEA1E':
                                 fputs ($fp,"<td bgcolor='$cor'>Os com Troca de Produtos - Pendentes</td>");
                                break;
                            default:
                                 fputs ($fp,"<td bgcolor='$cor'></td>");
                            break;
                        }
                    }
                    fputs($fp,"</tr>");
                }
                fputs ($fp, "</table>");
                if ($login_fabrica != 30) {
                    $resposta = "<br>";
                    $resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
                    $resposta .="<tr>";
                    $resposta .= "<td align='center' style='border: 0; font: bold 14px \"Arial\";'><a href=\"$caminho_donwload\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>";
                    $resposta .= "</tr>";
                    $resposta .= "</table>";
                    echo $resposta;
                    echo "<br/>";
                }

                // teste echo $email_consumidor.'<br />3251';exit;
            }
        }

        $sua_os             = trim (strtoupper ($_POST['sua_os']));
        if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
        $serie              = trim (strtoupper ($_POST['serie']));
        if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
        $nf_compra          = trim (strtoupper ($_POST['nf_compra']));
        if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
        $consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
        if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));
        $produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
        if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
        $produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));
        if (strlen($produto_descricao)==0) $produto_descricao = trim(strtoupper($_GET['produto_descricao']));
        $codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
        if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
        $posto_nome      = trim (strtoupper ($_POST['posto_nome']));
        if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
        $consumidor_nome = trim ($_POST['consumidor_nome']);
        if (strlen($consumidor_nome)==0) $consumidor_nome = trim($_GET['consumidor_nome']);
        $consumidor_fone = trim (strtoupper ($_POST['consumidor_fone']));
        if (strlen($consumidor_fone)==0) $consumidor_fone = trim(strtoupper($_GET['consumidor_fone']));
        $os_situacao     = trim (strtoupper ($_POST['os_situacao']));
        if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
        if($login_fabrica == 52) {
            $numero_ativo = trim (strtoupper ($_POST['numero_ativo']));
            if(strlen($numero_ativo)==0) {
                $numero_ativo = trim(strtoupper($_GET['numero_ativo']));
            }
            $cidade_do_consumidor = trim (strtoupper ($_POST['cidade_do_consumidor']));
            if(strlen($cidade_do_consumidor)==0) {
                $cidade_do_consumidor = trim(strtoupper($_GET['cidade_do_consumidor']));
            }
        }
if($telecontrol_distrib){
?>

<form id="form_exclui_os" name="form_exclui_os" method="post" style="display: none;" >
    <input type="hidden" name="acao_exclui_os" value="t" />
    <?php

    if (isset($_POST["acao_exclui_os"])) {
        unset($_POST["acao_exclui_os"]);
    }

    if (isset($_POST["exclui_os"])) {
        unset($_POST["exclui_os"]);
    }

    if (isset($_POST["motivo_exclui_os"])) {
        unset($_POST["motivo_exclui_os"]);
    }

    ?>
    <input type="hidden" name="post_anterior" value='<?=arrayToJson($_POST)?>' />

</form>
<?php } ?>
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td class="titulo_tabela" >Parâmetros de Pesquisa</td>
    </tr>
</table>
<?php
        if($login_fabrica == 90){
?>
        <TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
            <TR><TD COLSPAN="2">&nbsp;</TD></TR>
            <TR>
                <TD style="width: 125px">&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt1" value="1" id='chk_opt1' <?php if(!empty($chk1)) echo "CHECKED";?>><label for='chk_opt1'>&nbsp;OS  Abertas Hoje</label></TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt2" value="2" <?php if(!empty($chk2)) echo "CHECKED";?>>&nbsp;OS  Abertas Ontem</TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt3" value="3" <?php if(!empty($chk3)) echo "CHECKED";?>>&nbsp;OS  Abertas Nesta Semana</TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt4" value="3" <?php if(!empty($chk4)) echo "CHECKED";?>>&nbsp;OS  Abertas Na Semana Anterior</TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt5" value="4" <?php if(!empty($chk5)) echo "CHECKED";?>>&nbsp;OS  Abertas Neste Mês</TD>
            </TR>
            <TR><TD COLSPAN="2">&nbsp;</TD></TR>
        </TABLE>
<?php
        }
?>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2"  class="formulario">
    <tr>
        <td width="100px"> &nbsp; </td>
        <td width="200px">Número da OS</td>
        <td width="200px">
            <? echo ($login_fabrica==35) ? "PO#" : "Número de Série"; ?>
        </td>
        <td width="200px">NF. Compra</td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
        <td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
        <td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
    </tr>
</table>




<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <?php
        if ($login_fabrica == 35) {
            $tdwidth = '163px';
        } else {
            $tdwidth = '86px';
        }
        ?>
        <td align="left" style="width: <?=$tdwidth?>;" > &nbsp;</td>
        <td align="left" style="width: 230px;" >CPF/CNPJ Consumidor</td>
        <?php
        // HD 415550
        if ($login_fabrica == 94) {
        ?>
            <td align="left" width="400px">Nome do Técnico</td>
        <?php
        }
        if($login_fabrica == 137){
        ?>
            <td align="left" width="300px">N. Lote</td>
        <?php
        }
        if($login_fabrica==45){
        ?>
            <td align="left" width="300px">RG do Produto</td>
        <?php
        }
        if($login_fabrica==30){
        ?>
            <td align="left" width="300px">OS Revendedor</td>
        <?php
        }
        if($login_fabrica == 6 or $login_fabrica == 35){
        ?>
            <td align="left"width="300px">OS
            <?php
            if ($login_fabrica == 35) {
                echo 'Interna';
            } else {
                echo 'Posto';
            }
            ?>
            </td>
        <?php
        }
        if ($login_fabrica == 11){?>
            <td align="left" width="400px">RG do Produto</td>
        <?php
        }
        if($login_fabrica != 45 && $login_fabrica != 30 && $login_fabrica != 2 && $login_fabrica != 11 && $login_fabrica != 94){
        ?>
            <td align="left" width="300px">&nbsp;</td>
        <?php
        }
        ?>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td><input type="text" id="consumidor_cpf" name="consumidor_cpf" size="17" maxlength='14' value="<?php echo $consumidor_cpf?>" class="frm"></td>
        <?php
        if ($login_fabrica == 94) {
        ?>
            <td>
                <input class="frm" type="text" name="nome_tecnico" maxlength="20" value="<?php echo $_POST['nome_tecnico'];?>" />
            </td>
        <?php
        }
        if($login_fabrica==45) {
?>
        <td><input class="frm" type="text" name="rg_produto" size="15" maxlength="20" value="<? echo $_POST['rg_produto'] ?>" ></td>
<?
        }elseif($login_fabrica == 137){

?>
        <td><input class="frm" type="text" name="lote" size="15" maxlength="20" value="<? echo $_POST['lote'] ?>" ></td>
<?php

        }elseif($login_fabrica==30 or $login_fabrica == 35) {
?>
        <td><input class="frm" type="text" name="os_posto" size="15" maxlength="20" value="<? echo $_POST['os_posto'] ?>" ></td>
<?
        } elseif($login_fabrica == 6){
            echo'<td><input class="frm" type="text" name="os_posto" size="12" maxlength="10" value="';
            if (isset($_POST['os_posto']{0})){
                echo $_POST['os_posto'];
            }
            echo '" ></td>';
        }
?>
        </td>
<?
        if ($login_fabrica == 11) {
?>
            <td><input type="text" name="rg_produto_os" size="17" value="<?echo $_POST['rg_produto_os']?>" class="frm"></td>
<?
        }
        if($login_fabrica != 45 && $login_fabrica != 30 && $login_fabrica != 2 && $login_fabrica != 11){
?>
            <td>&nbsp;</td>
        <?php
        }
?>
    </tr>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?
        if($login_fabrica==45) {
?>
    <tr>
        <td align="left" width="100px"> &nbsp; </td>
        <td align="left" width="600px">Status</td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
            <select name='tipo_os' id='tipo_os' style='font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;font-size: 10px;'>
                <option>TODAS AS OPÇÕES</option>
                <option value='REINCIDENTE' <? if ($tipo_os == 'REINCIDENTE') echo " SELECTED "; ?>>Reincidências</option>
                <option value='BOM' <? if ($tipo_os == 'BOM') echo " SELECTED "; ?>>BOM (OSs abertas até 15 dias sem data de fechamento)</option>
                <option value='MEDIO' <? if ($tipo_os == 'MEDIO') echo " SELECTED "; ?>>MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)</option>
                <option value='RUIM' <? if ($tipo_os == 'RUIM') echo " SELECTED "; ?>>RUIM (OSs abertas a mais de 25 dias sem data de fechamento)</option>
                <option value='EXCLUIDA' <? if ($tipo_os == 'EXCLUIDA') echo " SELECTED "; ?>>OS Cancelada </option>
                <option value='RESSARCIMENTO' <? if ($tipo_os == 'RESSARCIMENTO') echo " SELECTED "; ?>>OS com Ressarcimento Financeiro</option>
                <option value='TROCA' <? if ($tipo_os == 'TROCA') echo " SELECTED "; ?>>OS com Troca de Produto</option>
                <?php if ($login_fabrica == 45): ?>
                    <option value='INTERACAO' <?  if ($tipo_os == 'INTERACAO')  echo " SELECTED "; ?>>OS com interação do posto</option>
                    <option value='RESOLVIDOS' <? if ($tipo_os == 'RESOLVIDOS') echo " SELECTED "; ?>>OS com troca de Produtos - Resolvidos</option>
                    <option value='PENDENTES' <?  if ($tipo_os == 'PENDENTES')  echo " SELECTED "; ?>>OS com troca de Produtos - Pendentes</option>
                <?php endif ?>
            </select>
        </td>
    </tr>
<?
        }
?>

</table>

<!-- CONSULTA OS OFF LINE -->
<?
        if($login_fabrica==19 OR $login_fabrica==10){
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr align='left' class="subtitulo">
            <td colspan='2'><center>Consulta OS Off Line</center></td>
        </tr>
</table>

    <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td align="left" width="200px">OS Off Line</td>
            <td align="left" width="400px"> &nbsp; </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><input type="text" name="os_off" size="10" value="" class="frm"></td>
            <td>&nbsp;</td>
        </tr>
    </table>



    <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td align="left" width="200px">Posto</td>
            <td align="left" width="400px">Nome do Posto</td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td>
                <input width="200" type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '');" <? } ?> value="<? echo $codigo_posto_off ?>" class="frm">
                <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '')">
            </td>
            <td>
                <input type="text" name="posto_nome_off" id="posto_nome_off" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome);" <? } ?> value="<?echo $posto_nome_off ?>" class="frm">
                <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome)">
            </td>
        </tr>
    </table>

    <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr><td align="left" width="700px">&nbsp;</td></tr>
        <tr>
            <td align="center" width="700px">
                <input type="submit" name="btn_acao" value="Pesquisar">
            </td>
        </tr>
    </table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr align='left'>
            <td colspan='2'><hr></td>
        </tr>
</table>

<?
        }
?>

<!--fim consulta off line -->

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

    <? if($login_fabrica==7){ ?>
        <tr>
            <td align='left' width="100px"> &nbsp; </td>
            <td align='left' width="200px">&nbsp;Data Inicial</td>
            <td align='left' width="200px">&nbsp;Data Final</td>
            <td align='left' width="200px">&nbsp;</td>
        </tr>

        <tr valign='top'>
            <td> &nbsp; </td>
            <td>
                <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
            </td>
            <td>
                <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
                &nbsp;
            </td>
            <td>
                <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> ><label for="os_aberta">Apenas OS em aberto</label>
            </td>
        </tr>
    <? }else{ ?>
            <!-- HD 211825: Filtrar por tipo de OS: Consumidor/Revenda -->
<?php
            switch ($consumidor_revenda_pesquisa) {
                case "C":
                    $selected_c = "SELECTED";
                break;

                case "R":
                    $selected_r = "SELECTED";
                break;
            }
?>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td align="left" width="200px">
            <?php
                if(in_array($login_fabrica, array(87,141,144))){
                    echo "Tipo de Atendimento";
                }else{
                    echo "Tipo de OS";
                }
            ?>
            </td>

<?php
                if(in_array($login_fabrica, array(94,115,116,117,120))){
?>
                    <td>Tipo de Atendimento</td>
<?php
                }
?>
            <td align="left" width="400px">
                <?php
                #HD 234532
                if($login_fabrica != 96){
?>
                    Status da OS
<?
                }
?>
            </td>
        </tr>

        <tr>
            <td width="100px"> &nbsp; </td>
            <td>

<?php
                if(in_array($login_fabrica, array(87,141,144))){
                    $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                    $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
                ?>
                <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                <?php
                    if(pg_num_rows($res_tipo_atendimento)>0){
                        echo '<option value="" selected></option>';
                        for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                            $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                            $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                            echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                        }
                    }
?>
                </select>
<?php
                }else{
?>
                <select id="consumidor_revenda_pesquisa" name="consumidor_revenda_pesquisa" class='frm' style='width:95px'>
                    <option value="">Todas</option>
                    <option value="C" <?php echo $selected_c; ?>>Consumidor</option>
                    <option value="R" <?php echo $selected_r; ?>>Revenda</option>
                </select>
<?php
                }
?>

            </td>

<?php
                if(in_array($login_fabrica, array(94,115,116,117,120))){
                    $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                    $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
?>
                    <td nowrap>
                        <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                        <?php
                            if(pg_num_rows($res_tipo_atendimento)>0){
                                echo '<option value="" selected></option>';
                                for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                                    $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                                    $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                                    echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                                }
                            }
                        ?>
                        </select>
                    </td>
<?php
                }
?>
            <td>
<?php
                #HD 234532
                if($login_fabrica != 96){
?>
                    <select id="status_checkpoint" name="status_checkpoint" class='frm'>
                        <option value=""></option>
<?php
                    if(!in_array($login_fabrica, array(87))){
?>
                            <option value="0" <?php echo ($status_checkpoint_pesquisa == 0 && strlen($status_checkpoint_pesquisa) > 0) ? 'selected' : null; ?> >    Aberta Call-Center</option>
<?
                    }
?>
                        <option value="1" <?php echo ($status_checkpoint_pesquisa == 1) ? 'selected' : null; ?> >   Aguardando Análise</option>
                        <option value="2" <?php echo ($status_checkpoint_pesquisa == 2) ? 'selected' : null; ?> >   Aguardando Peças</option>
<?
                    if(in_array($login_fabrica, array(51,81,114,141,144)) || isset($novaTelaOs)) {
?>
                            <option value="8" <?php echo ($status_checkpoint_pesquisa == 8) ? 'selected' : null; ?> >   Aguardando Produto</option>
<?
                    }

                    if(in_array($login_fabrica, array(131))) {
?>
                        <option value="13" <?php echo ($status_checkpoint_pesquisa == 13) ? 'selected' : null; ?> >   Pedido Cancelado</option>

<?php
                    }

                    if(in_array($login_fabrica, array(141,144))) {
                        ?>
                        <option value="10" <?php echo ($status_checkpoint_pesquisa == 10) ? 'selected' : null; ?> >Aguardando Remanufatura</option>
                        <option value="11" <?php echo ($status_checkpoint_pesquisa == 11) ? 'selected' : null; ?> >Aguardando Faturamento</option>
                        <option value="14" <?php echo ($status_checkpoint_pesquisa == 14) ? 'selected' : null; ?> >Aguardando Auditoria</option>
                        <?
                    }
                    if($login_fabrica == 141){
                    ?>
                        <option value="12" <?php echo ($status_checkpoint_pesquisa == 12) ? 'selected' : null; ?> >Aguardando Código de Rastreio</option>
                    <?php
                    }


                    ?>
?>
                        <option value="3" <?php echo ($status_checkpoint_pesquisa == 3) ? 'selected' : null; ?> >   Aguardando Conserto</option>
                        <option value="4" <?php echo ($status_checkpoint_pesquisa == 4) ? 'selected' : null; ?> >   Aguardando Retirada (Consertada)</option>
                        <option value="9" <?php echo ($status_checkpoint_pesquisa == 9) ? 'selected' : null; ?> >   Finalizada</option>

                        <?php
                        if(in_array($login_fabrica, array(85))) {
                        ?>
                            <option value="10" <?php echo ($status_checkpoint_pesquisa == 10) ? 'selected' : null; ?> > OS sem vinculo com Call-Center</option>
                        <?php } ?>

                    </select>
<?
                }
?>
            </td>

        </tr>

         <?php
        if ($login_fabrica == 145) {
        ?>
            <tr>
                <td align="left" width="100px"> &nbsp; </td>
                <td>Tipo de Atendimento</td>
                <td>Pesquisa de Satisfação</td>
            </tr>
            <tr>
                <td align="left" width="100px"> &nbsp; </td>
                <?php
                $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
                ?>
                    <td nowrap>
                        <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                        <?php
                            if(pg_num_rows($res_tipo_atendimento)>0){
                                echo '<option value="" selected></option>';
                                for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                                    $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                                    $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                                    echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                                }
                            }
                        ?>
                        </select>
                    </td>
                <td>
                    <select class="frm" name="pesquisa_satisfacao" >
                        <option value="" ></option>
                        <option value="realizada" <?=($_POST["pesquisa_satisfacao"] == "realizada") ? "selected" : ""?> >Pesquisa realizada</option>
                        <option value="nao_realizada" <?=($_POST["pesquisa_satisfacao"] == "nao_realizada") ? "selected" : ""?> >Pesquisa não realizada</option>
                    </select>
                </td>
            </tr>
        <?php
        }
        if($login_fabrica == 30){
?>
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td colspan="2" align="left">Atendimento Centralizado</td>
        </tr>
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td colspan="2" align="left">
                <select id="cliente_admin" name="cliente_admin" class="frm">
                    <option value="">Todos</option>
<?
            $sql = "SELECT  tbl_cliente_admin.cliente_admin,
                            tbl_cliente_admin.nome
                    FROM    tbl_cliente_admin
                    WHERE   tbl_cliente_admin.fabrica = $login_fabrica
              ORDER BY      tbl_cliente_admin.nome
            ";
            $res = pg_query($con,$sql);
            $todos_cliente_admin = pg_fetch_all($res);

            foreach($todos_cliente_admin as $valor){
?>
                    <option value="<?=$valor['cliente_admin']?>" <?=($valor['cliente_admin'] == $cliente_admin) ? "selected" : ""?>><?=$valor['nome']?></option>
<?
            }

?>
                </select>
            </td>
        </tr>
<?
        }
        ?>
        <tr>
            <td colspan='4' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"><br><br></td>

        </tr>
        <?php
        if($login_fabrica == 30){
        ?>
            <tr>
            <td colspan="4" align="center">
                <input type='checkbox' name='sem_listar_peca' value='1' <? if (strlen ($sem_listar_peca) > 0 ) echo " checked " ?> ><label for="sem_listar_peca">Consultar OS Sem listar peças</label><br />
            </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
        <?php
        }
        ?>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr align='left' class="subtitulo">
            <td colspan='2'>&nbsp;</td>
        </tr>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario" >
        <tr>
            <td style="width: 97px;">&nbsp;</td>
<?
if($login_fabrica == 1){
?>
            <td> Marca</td>
<?
}
?>
            <td style="width: 197px;"> Linha</td>
            <td> Família</td>
<?
if($login_fabrica == 30){
?>
            <td>&nbsp;</td>
<?
}
?>
        </tr>
        <tr>
            <td>&nbsp;</td>
<?
if($login_fabrica == 1){
?>
            <td>
                <select name="marca" class="frm">
                    <option value=''>&nbsp;</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
                    <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_POST['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
                </select>
            </td>
<?
}
?>
            <td>
<?
                echo "<select name='linha' size='1' class='frm' style='width:95px'>";
                echo "<option value=''></option>";
                $sql = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res)>0){
                    for($i=0;pg_num_rows($res)>$i;$i++){
                        $xlinha = pg_fetch_result($res,$i,linha);
                        $xnome = pg_fetch_result($res,$i,nome);
?>
                    <option value="<?echo $xlinha;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
<?
                    }
                }
                echo "</SELECT>";
?>
            </td>
            <td>
<?
                echo "<select name='familia' size='1' class='frm' style='width:95px'>";
                echo "<option value=''></option>";
                $sql = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res)>0){
                    for($i=0;pg_num_rows($res)>$i;$i++){
                        $xfamilia = pg_fetch_result($res,$i,familia);
                        $xdescricao = pg_fetch_result($res,$i,descricao);
                        ?>
                        <option value="<?echo $xfamilia;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xdescricao;?></option>
                        <?
                    }
                }
                echo "</SELECT>";
?>
            </td>
<?
if($login_fabrica == 30){
?>
            <td>
                <input type='checkbox' name='os_troca' value='1' <? if (strlen ($os_troca) > 0 ) echo " checked " ?> ><label for="os_troca">Apenas OS Troca</label><br />
            </td>

<?
}
?>
        </tr>

</table>

<script>
    //HD 115630-----
    function clika_a(){
        if ( document.getElementById('os_aberta').checked == true ) {
            document.getElementById('os_aberta').checked = false
        }
    }
    function clika_b(){
        if ( document.getElementById('os_finalizada').checked == true ) {
            document.getElementById('os_finalizada').checked = false
        }
    }
    //------------

    <?php

        if (in_array($login_fabrica, array(141,144))) {
        ?>

            function solicitaTroca(os, button) {
                if (os != undefined && os > 0) {
                    $.ajax({
                        url: "os_consulta_lite.php",
                        type: "post",
                        data: { solicitaTroca: true, os: os },
                        complete: function(data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                alert(data.ok);
                                button.remove();
                            }
                        }
                    });
                }
            }

        <?php
        }

        if(in_array($login_fabrica, array(85))){

            ?>

                function abreAtendimento(os, box){

                    $.ajax({
                        url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                        type: "POST",
                        data: {
                            abrir_atendimento : "ok",
                            os : os
                        },
                        complete: function(data){
                            var hd_chamado = data.responseText;
                            hd_chamado = "<a href='callcenter_interativo_new.php?callcenter="+hd_chamado+"&os="+os+"' target='_blank'>"+hd_chamado+"</a>";
                            $('#box_'+box).html(hd_chamado);
                        }
                    });

                }

            <?php

        }
        if ($login_fabrica == 24) {

    ?>
            function congelar_os(os,status,i){

                if(confirm("Deseja alterar a OS : "+os)){
                    $.post('../admin/ajax_cancela_os.php',{ sua_os :os , op : status},
                        function (resposta){
                            if(resposta === "OK"){
                                if(status=="t"){

                                    $( "#box_"+i ).empty();
                                    $( "#box_"+i).append( " <img border='0' src='imagens_admin/congelar_os.jpg' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")'> " );
                                    alert("Os Descongelada com sucesso : "+os);
                                }else{

                                    $( "#box_"+i ).empty();
                                    $( "#box_"+i).append( "<img border='0' src='imagens_admin/descongelar_os.gif' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")' >" );
                                    alert("Os Congelada com sucesso : "+os);
                                }
                            }else{
                                alert(resposta);
                            }
                    });
                }
            }
    <?php
        };
    ?>
</script>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px">&nbsp;Data Inicial</td>
        <td align='left' width="200px">&nbsp;Data Final</td>
        <td align='left' width="200px">&nbsp;</td>
    </tr>

    <tr valign='top'>
        <td> &nbsp; </td>
        <td>
            <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
        </td>
        <td>
            <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
            &nbsp;
        </td>
        <td>
                                <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> ><label for="os_aberta">Apenas OS em aberto </label><br />
            <?if ($login_fabrica == 74) {?>
            <input type='checkbox' name='os_atendida' value='1' <? if (strlen ($os_atendida) > 0 ) echo " checked " ?> ><label for="os_atendida">Apenas OS não atendida </label><br />
<? }
            if ($login_fabrica == 42) {?>
            <input type='checkbox' name='entrega_tecnica' value='t' <? if ($_POST["entrega_tecnica"] == "t" ) echo " checked " ?> ><label for="entrega_tecnica">OS de entrega técnica</label>
<?
            }
            if($login_fabrica==35){
?>
                    <br>
                    <input type='checkbox' id='os_finalizada' name='os_finalizada' value='1' <? if (strlen ($os_finalizada) > 0 ) echo " checked " ?> onClick="clika_a();"><label for="os_finalizada">Apenas OS Fechada</label>
<?php
            }else{
?>
                &nbsp;
<?php
            }
?>
        </td>
    </tr>
</table>
<?
        }
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px">&nbsp;Posto</td>
        <td align='left' width="400px">&nbsp;Nome do Posto</td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
            <input type="text" name="codigo_posto" id="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '')">
        </td>
        <td>
            <input type="text" name="posto_nome" id="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome);" <? } ?> value="<?echo $posto_nome?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome)">
        </td>
    </tr>
</table>



<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px"><?php echo ($login_fabrica==3 OR $login_fabrica == 86 OR $login_fabrica == 52 or $multimarca == 't') ? "Marca" : ""; ?></td>
        <td align='left' width="400px">Nome do Consumidor</td>
    </tr>

    <tr>
        <td> &nbsp; </td>
        <td>
<?
        if(in_array($login_fabrica, array(3,52,86)) or $multimarca == 't'){
            echo "<select name='marca' size='1' class='frm' style='width:95px'>";
            echo "<option value=''></option>";
            $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)>0){
                for($i=0;pg_num_rows($res)>$i;$i++){
                    $xmarca = pg_fetch_result($res,$i,marca);
                    $xnome = pg_fetch_result($res,$i,nome);
                    ?>
                    <option value="<?echo $xmarca;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
                    <?
                }
            }
            echo "</SELECT>";
        }
?>
        </td>
        <!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
        <td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"> <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'></td>
    </tr>
</table>

<?php

        if($login_fabrica == 86){
            $array_estado = array(  "Norte"         => "Região Norte(AC, AP, AM, PA, RO, RR, TO)"           ,
                            "Nordeste"      => "Região Nordeste(AL, BA, CE, MA, PB, PE, PI, RN, SE)",
                            "Centro_oeste"  => "Região Centro-Oeste(DF, GO, MT, MS)"                ,
                            "Sudeste"       => "Região Sudeste(ES, MG, RJ, SP)"                     ,
                            "Sul"           => "Região Sul(PR, RS, SC)"                             ,
                            "AC"            => "AC - Acre"                                          ,
                            "AL"            => "AL - Alagoas"                                       ,
                            "AM"            => "AM - Amazonas"                                      ,
                            "AP"            => "AP - Amapá"                                         ,
                            "BA"            => "BA - Bahia"                                         ,
                            "CE"            => "CE - Ceará"                                         ,
                            "DF"            => "DF - Distrito Federal"                              ,
                            "ES"            => "ES - Espírito Santo"                                ,
                            "GO"            => "GO - Goiás"                                         ,
                            "MA"            => "MA - Maranhão"                                      ,
                            "MG"            => "MG - Minas Gerais"                                  ,
                            "MS"            => "MS - Mato Grosso do Sul"                            ,
                            "MT"            => "MT - Mato Grosso"                                   ,
                            "PA"            => "PA - Pará"                                          ,
                            "PB"            => "PB - Paraíba"                                       ,
                            "PE"            => "PE - Pernambuco"                                    ,
                            "PI"            => "PI - Piauí"                                         ,
                            "PR"            => "PR - Paraná"                                        ,
                            "RJ"            => "RJ - Rio de Janeiro"                                ,
                            "RN"            => "RN - Rio Grande do Norte"                           ,
                            "RO"            => "RO - Rondônia"                                      ,
                            "RR"            => "RR - Roraima"                                       ,
                            "RS"            => "RS - Rio Grande do Sul"                             ,
                            "SC"            => "SC - Santa Catarina"                                ,
                            "SE"            => "SE - Sergipe"                                       ,
                            "SP"            => "SP - São Paulo"                                     ,
                            "TO"            => "TO - Tocantins"
                    );
        }else{
            $array_estado = array(  "AC"=>"AC - Acre"                   ,
                            "AL"=>"AL - Alagoas"                ,
                            "AM"=>"AM - Amazonas"               ,
                            "AP"=>"AP - Amapá"                  ,
                            "BA"=>"BA - Bahia"                  ,
                            "CE"=>"CE - Ceará"                  ,
                            "DF"=>"DF - Distrito Federal"       ,
                            "ES"=>"ES - Espírito Santo"         ,
                            "GO"=>"GO - Goiás"                  ,
                            "MA"=>"MA - Maranhão"               ,
                            "MG"=>"MG - Minas Gerais"           ,
                            "MS"=>"MS - Mato Grosso do Sul"     ,
                            "MT"=>"MT - Mato Grosso"            ,
                            "PA"=>"PA - Pará"                   ,
                            "PB"=>"PB - Paraíba"                ,
                            "PE"=>"PE - Pernambuco"             ,
                            "PI"=>"PI - Piauí"                  ,
                            "PR"=>"PR - Paraná"                 ,
                            "RJ"=>"RJ - Rio de Janeiro"         ,
                            "RN"=>"RN - Rio Grande do Norte"    ,
                            "RO"=>"RO - Rondônia"               ,
                            "RR"=>"RR - Roraima"                ,
                            "RS"=>"RS - Rio Grande do Sul"      ,
                            "SC"=>"SC - Santa Catarina"         ,
                            "SE"=>"SE - Sergipe"                ,
                            "SP"=>"SP - São Paulo"              ,
                            "TO"=>"TO - Tocantins"
                    );
        }
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="400px">Estado</td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>

    <tr>
        <td> &nbsp; </td>
        <td>
            <select name="estado" id="estado" size="1" class="frm" style="width:350px">
            <option value="">Selecione um Estado<?php if($login_fabrica == 86){ echo " ou Região"; }?></option>
<?php
        foreach ($array_estado as $k => $v) {
            echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
        }
?>
            </select>
        </td>
        <td> &nbsp; </td>
</table>
<?
if($login_fabrica == 30){
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="400px">Cidade</td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td><input type='text' class="frm" id='cidade' name='cidade' value='<?=$cidade?>'>
        <td> &nbsp; </td>
    </tr>
</table>
<?
}
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?
        if ($login_fabrica == 45 || $login_fabrica == 80) {
?>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="600px">
            Região
        </td>
    </tr>
<?
        }
        if($login_fabrica==50){
?>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="600px">
            Status OS
        </td>
    </tr>
<?
        }
?>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="600px">
<?
        if($login_fabrica==45){
            echo "<select name='regiao' size='1' class='frm' style='width:370px'>";
?>
                <option value=''></option>
                <option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>Estado de São Paulo </option>
                <option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>Sul (SC,RS e PR)</option>
                <option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>Sudeste (RJ, ES e MG)</option>
                <option value='5' <? if ($regiao == 5) echo " SELECTED "; ?>>Nordeste (SE, AL, PE, PB e BA)</option>
                <option value='7' <? if ($regiao == 7) echo " SELECTED "; ?>>Centro-Oeste, Norte e Nordeste (GO, MS, MT, DF, CE, RN, TO, PA, AP, RR, AM, AC, RO, MA, PI)</option>
<?
            echo "</SELECT>";
        }elseif($login_fabrica==80){
            echo "<select name='regiao' size='1' class='frm' style='width:320px'>";
?>
                <option value=''></option>
                <option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>PE/PB</option>
                <option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>RJ/GO/MG/AC/AM/DF/ES/PI/MA/MS/MT/PA/PR/RO/RR/RS/SC/TO/AP</option>
                <option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>BA/SE/AL</option>
                <option value='4' <? if ($regiao == 4) echo " SELECTED "; ?>>CE/RN/SP</option>
<?
            echo "</SELECT>";
        }elseif($login_fabrica==50){
?>
                <select name='tipo_os' size='1' class='frm' style='width:300px'>";
                <option value=''></option>
                <option value='reincidente' <? if ($tipo_os == 'REINCIDENTE') echo " SELECTED "; ?>>Reincidências</option>
                <option value='mais_cinco_dias' <? if ($tipo_os == 'MAIS_CINCO_DIAS') echo " SELECTED "; ?>>Mais de 5 dias sem data de fechamento</option>
                <option value='mais_dez_dias' <? if ($tipo_os == 'MAIS_DEZ_DIAS') echo " SELECTED "; ?>>Mais de 10 dias sem data de fechamento</option>
                <option value='mais_vinte_dias' <? if ($tipo_os == 'MAIS_VINTE_DIAS') echo " SELECTED "; ?>>Mais de 20 dias sem data de fechamento</option>
                <option value='excluidas' <? if ($tipo_os == 'EXCLUIDAS') echo " SELECTED "; ?>>Excluídas do sistema</option>
                <option value='os_com_troca' <? if ($tipo_os == 'OS_COM_TROCA') echo " SELECTED "; ?>>OS com Troca de Produto</option>
                </SELECT>
<?
        }
?>
        </td>
    </tr>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px">Ref. Produto</td>
        <td align='left' width="400px">Descrição Produto</td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
        <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?=$produto_referencia?>" >
        &nbsp;
        <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto ('', document.frm_consulta.produto_referencia,'')">
        </td>
        <td>
        <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
        &nbsp;
        <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_descricao, '','')">
    </tr>
</table>


<?php

        if($login_fabrica == 52) {
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px">Número Ativo</td>
        <td align='left' width="400px">Cidade do consumidor</td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
        <input class="frm" type="text" name="numero_ativo" id="numero_ativo" size="15" maxlength="20" value="<?php echo $numero_ativo;?>" >
        </td>
        <td>
        <input class="frm" type="text" name="cidade_do_consumidor" id="cidade_do_consumidor" size="30" value="<? echo $cidade_do_consumidor;?>" >
    </tr>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px">Número do Atendimento</td>
        <td align='left' width="400px">&nbsp; </td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
        <input class="frm" type="text" name="hd_chamado_numero" id="hd_chamado_numero" size="15" maxlength="20" value="<?php echo $hd_chamado_numero;?>" >
        </td>
        <td>&nbsp;</tr>
</table>

<?php
        }
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?
        if ($login_fabrica == 3) {
?>

    <tr>
        <td align='left' width="100px">&nbsp;</td>
        <td align='left' width="400px">Admin</td>
        <td align='left' width="200px">&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
        <select name="admin" size="1" class="frm">
            <option value=''></option>
<?
            $sql =  "SELECT admin, login
                    FROM tbl_admin
                    WHERE fabrica = $login_fabrica
                    ORDER BY login;";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                    $x_admin = pg_fetch_result($res,$i,admin);
                    $x_login = pg_fetch_result($res,$i,login);
                    echo "<option value='$x_admin'";
                    if ($admin == $x_admin) echo " selected";
                    echo ">$x_login</option>";
                }
            }
?>
            </select>
        </td>
        <td>&nbsp;</td>
    </tr>

<?
        }
?>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px">&nbsp;</td>
        <td align='left' width="200px"><input type="radio" id="os_situacao_aprovada" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>><label for="os_situacao_aprovada">OS´s Aprovadas</label>
        </td>
        <td align='left' width="400px">
            <input type="radio" id="os_situacao_paga" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>>
            <label for="os_situacao_paga">OS´s Pagas</label>
        </td>
    </tr>
</table>
<?php
if ($login_fabrica == 24) {
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px">&nbsp;</td>
        <td align='left' width="200px">
            <input type="radio" id="os_congelada" name="os_congelada" value="congelada" <? if ($os_congelada == "congelada") echo "checked"; ?>>
            <label for="os_congelada">OS´s congeladas</label>
        </td>
        <td align='left' width="400px">
            <input type="radio" id="os_para_congelar" name="os_congelada" value="congelar" <? if ($os_congelada == "congelar") echo "checked"; ?>>
            <label for="os_para_congelar">OS´s para congelar</label>
        </td>
    </tr>
</table>

<?php
}
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

    <tr align='left' class="subtitulo">
        <td colspan='3'><center>Consultar Pré-Ordem de Serviço</center></td>
    </tr>
    <tr>
        <td align='left' width="100px">&nbsp; </td>
        <td align='left' width="400px">Número do Atendimento<input type='text' name='pre_os' id='pre_os' class='frm'></td>
        <td align='left' width="200px">
<?
        if (!in_array($login_fabrica,array(30,52,6)) OR $usaPreOS) { ?>
                <input type="submit" name="btn_acao_pre_os" value="Pesquisar Pré-OS">
<?
        }
?>
        </td>
    </tr>

<?
        if ($login_fabrica==3) {
            if ($posto_ordenar == 'sim') {
                $checked ='CHECKED';
            }
?>
    <tr>
        <td> &nbsp; </td>
        <td align='left' colspan='2'><input type="checkbox" name="posto_ordenar" value="sim" <?=$checked;?>>Ordenar por Posto </td>
    </tr>
<?
        }
?>


<?
        if($login_fabrica == 20){
            // MLG 2009-08-04 HD 136625
            $sql = "SELECT pais,nome FROM tbl_pais where america_latina is TRUE;";
            $res = pg_query($con,$sql);
            $p_tot = pg_num_rows($res);
            for ($i=0; $i<$p_tot; $i++) {
                list($p_code,$p_nome) = pg_fetch_row($res, $i);
                $sel_paises .= "\t\t\t\t<option value='$p_code'";
                $sel_paises .= ($pais==$p_code)?" selected":"";
                $sel_paises .= ">$p_nome</option>\n";
            }
?>
    <tr>
        <td> &nbsp; </td>
        <td colspan='2'>País<br>
            <select name='pais' size='1' class='frm'>
             <option></option>
            <?echo $sel_paises;?>
            </select>
        </td>
    </tr>
<?
        }
?>

<? if (in_array($login_fabrica, array(11))) { ?>

<tr>
    <td colspan="3" style="text-align:center;padding: 0 40px;">Os seguintes campos estão habilitados para consulta de pré-OS:<br /><strong>Data Inicial / Data Final, Número de Série, NF Compra, CPF Consumidor, Nome do Consumidor, Posto, Nome do Posto, Ref. Produto e Descrição do Produto.</strong></td>
</tr>

<? } ?>

    <tr>
        <td colspan='3'> <hr> </td>
    </tr>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="400px"> OS em aberto da Revenda = CNPJ
        <!-- HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais -->
        <input class="frm" type="text" name="revenda_cnpj" size="12" maxlength='8' value="<? echo $revenda_cnpj ?>" > /0000-00
        </td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>

<?
        if($login_fabrica==7){ // HD 75762 para Filizola ?>
        <tr>
            <td colspan='3'> <hr> </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>
                Classificação da OS
                <select name='classificacao_os' id='classificacao_os' size="1" class="frm">
                    <option value='' selected></option>
<?
            $sql = "SELECT  *
                    FROM    tbl_classificacao_os
                    WHERE   fabrica = $login_fabrica
                    AND     ativo IS TRUE
              ORDER BY      descricao";
            $res = @pg_query ($con,$sql);
            if(pg_num_rows($res) > 0){
                for($i=0; $i < pg_num_rows($res); $i++){
                    $classificacao_os=pg_fetch_result($res,$i,classificacao_os);
                    $descricao=pg_fetch_result($res,$i,descricao);
                    echo "<option value='$classificacao_os'>$descricao</option>\n";
                }
            }
?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan='3'> <hr> </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>
                Natureza
                <select name="natureza" class="frm">
                    <option value='' selected></option>
<?
            $sqlN = "SELECT *
                FROM tbl_tipo_atendimento
                WHERE fabrica = $login_fabrica
                AND   ativo IS TRUE
                ORDER BY tipo_atendimento";
            $resN = pg_query ($con,$sqlN) ;

            for ($z=0; $z<pg_num_rows($resN); $z++){
                $xxtipo_atendimento = pg_fetch_result($resN,$z,tipo_atendimento);
                $xxcodigo           = pg_fetch_result($resN,$z,codigo);
                $xxdescricao        = pg_fetch_result($resN,$z,descricao);

                echo "<option ";
                $teste1 = $natureza;
                $teste2 = $xxtipo_atendimento;
                if($natureza==$xxtipo_atendimento) echo " selected ";
                echo " value='" . $xxtipo_atendimento . "'" ;
                echo " > ";
                echo $xxcodigo . " - " . $xxdescricao;
                echo "</option>\n";
            }
?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan='3'> <hr> </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>
                Aberto por
                <select name="admin_abriu" class="frm">
                    <option value='' selected></option>
<?php
            $sqlM = "SELECT admin, nome_completo
                FROM tbl_admin
                WHERE fabrica = $login_fabrica
                AND ativo IS TRUE
                ORDER BY nome_completo";
            $resM = pg_query ($con,$sqlM);

            for ($j=0; $j<pg_num_rows($resM); $j++){
                $jadmin = pg_fetch_result($resM,$j,admin);
                $jadmin_nome = pg_fetch_result($resM,$j,nome_completo);

                echo "<option ";
                if($admin_abriu == $jadmin){
                    echo " selected ";
                }
                echo "value='" . $jadmin . "'>";
                echo $jadmin_nome;
                echo "</option>";
            }
?>
                </select>
            </td>
        </tr>
<?
        }
?>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
    </tr>
    <?php
    if($login_fabrica == 30){
    ?>
        <tr>
            <td colspan="4" align="center">
                <input type='checkbox' name='sem_listar_peca' value='1' <? if (strlen ($sem_listar_peca) > 0 ) echo " checked " ?> ><label for="sem_listar_peca">Consultar OS Sem listar peças</label><br />
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
    <?php
    }
    ?>
</table>
</table>
</form>
<? include "rodape.php"; ?>
