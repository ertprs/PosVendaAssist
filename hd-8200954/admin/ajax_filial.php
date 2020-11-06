<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

ob_start();
require_once 'autentica_admin.php';
ob_clean();
include_once 'funcoes.php';
include_once __DIR__ . '/../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

define('CAMPOS_FILIAL', 'tbl_filial.filial, tbl_filial.nome, tbl_filial.cnpj, tbl_filial.ie, tbl_filial.fantasia, tbl_filial.telefone, tbl_filial.cep, tbl_filial.endereco, tbl_filial.numero, tbl_filial.complemento, tbl_filial.bairro, tbl_cidade.nome AS cidade, tbl_filial.estado, tbl_filial.cod_filial, tbl_filial.fabrica');
define('CAMPOS_OBRIG', 'nome,nome_fantasia,cnpj,ie,endereco,numero,bairro,cep,cidade,estado,telefone');

/**
 * Funções que processam as diferentes ações: Create Read e Update
 * Por enquanto sem DELETE (seria um "ativo" false, mas não tem campo ativo...)
 **/

/**
 * Confere o array de dados enviados na requisição, ignora os elementos que não são
 * da filial (banco de dados), limpa esses dados e retorna um novo array.
 * NÃO TRATA E NEM DEVOLVE PARÂMETROS QUE NÃO SEJAM CAMPOS DA TABELA!!
 **/
function prepareUserData($_DATA, $toLatin1 = true) {
    if (!is_array($_DATA))
        return false;

    if (isset($_DATA['nome_fantasia']) and !isset($_DATA['fantasia']))
        $_DATA['fantasia'] = $_DATA['nome_fantasia'];

    if (isset($_DATA['razao_social']) and !isset($_DATA['nome']))
        $_DATA['nome'] = $_DATA['razao_social'];

    if (isset($_DATA['fone']) and !isset($_DATA['telefone']))
        $_DATA['telefone'] = $_DATA['fone'];

    // Corta lava, apara...
    if (isset($_DATA['id']         )) $userData['filial']        = (int)$_DATA['id'];
    if (isset($_DATA['nome']       )) $userData['nome']          = substr($_DATA['nome'], 0, 60);
    if (isset($_DATA['fantasia']   )) $userData['nome_fantasia'] = substr($_DATA['fantasia'], 0, 30);
    if (isset($_DATA['cnpj']       )) $userData['cnpj']          = substr(preg_replace('/\D/', '', $_DATA['cnpj']), 0, 14);
    if (isset($_DATA['ie']         )) $userData['ie']            = substr($_DATA['ie'], 0, 14);
    if (isset($_DATA['endereco']   )) $userData['endereco']      = substr($_DATA['endereco'], 0, 50);
    if (isset($_DATA['numero']     )) $userData['numero']        = substr($_DATA['numero'], 0, 20);
    if (isset($_DATA['complemento'])) $userData['complemento']   = substr($_DATA['complemento'], 0, 40);
    if (isset($_DATA['bairro']     )) $userData['bairro']        = substr($_DATA['bairro'], 0, 30);
    if (isset($_DATA['cep']        )) $userData['cep']           = substr(preg_replace('/\D/', '', $_DATA['cep']), 0, 8);
    if (isset($_DATA['cidade']     )) $userData['cidade']        = $_DATA['cidade'];
    if (isset($_DATA['estado']     )) $userData['estado']        = substr(strtoupper($_DATA['estado']), 0, 2);
    if (isset($_DATA['telefone']   )) $userData['telefone']      = substr(preg_replace('/\D/', '', $_DATA['telefone']), 0, 14);
    if (isset($_DATA['cod_filial'] )) $userData['cod_filial']    = substr($_DATA['cod_filial'], 0, 10);

    if ($toLatin1 and !is_null(json_encode($userData, true)))
        $userData = array_map('utf8_decode', $userData);

    return array_filter($userData, 'strlen');
}

function formatResult(array $row) {
    unset($row['fabrica']);
    if (isset($row['cnpj']))
        $row['cnpj'] = preg_replace(RE_FMT_CNPJ, '$1.$2.$3/$4-$5', $row['cnpj']);
    if (isset($row['cep']))
        $row['cep']  = preg_replace(RE_FMT_CEP, '$1$2-$3', $row['cep']);
    if (isset($row['telefone']))
        $row['telefone']  = phone_format($row['telefone']);
    return $row;
}

function get_city($cidade, $estado=null, $cep=null) {
    global $con;

    if (is_numeric($cidade)) {
        $res = pg_query($con,
            sql_cmd('tbl_cidade', '*', array('cidade'=>$cidade))
        );

        if (!pg_num_rows($res)) {
            return 'Cidade não encontrada!';
        }
    } else { // procura a cidade pelo nome e estado
        // pre_echo($GLOBALS['estados_BR'], strtoupper($estado));
        if (in_array(strtoupper($estado), $GLOBALS['estados_BR'])) {
            $uf = strtoupper($estado);
        } else {
            $uf = reset(array_keys(array_map('mb_strtolower', $GLOBALS['estados']), mb_strtolower($estado)));
        }

        if (!$uf)
            return "Estado '$uf' desconhecido!";

        $cidade_pesquisa = pg_escape_string(tira_acentos($cidade));
    //    $cidade_pesquisa = preg_replace('/\W/', '.', $cidade_pesquisa);

        $sql_ibge = sql_cmd(
            'tbl_cidade', 'cidade',
            array(
                'UPPER(fn_retira_especiais(nome))=' => "~* E'".$cidade_pesquisa."'",
                'estado' => $uf
            )
        );

        if (pg_num_rows($res_ibge = pg_query($con, $sql_ibge)))
            $cidade = pg_fetch_result($res_ibge, 0, 'cidade');
        else
            return "Cidade $cidade_pesquisa não encontrada em $uf!";
    }

    if (empty($cidade))
        return "Cidade não encontrada!" ;

    // validando CEP
    $CEPs = pg_fetch_pairs(
        $con,
        $sqlCEP = sql_cmd(
            'tbl_cep', 'cep',
            array('UPPER(fn_retira_especiais(cidade))'=>strtoupper($cidade_pesquisa), 'estado' => $uf)
        ) . ' ORDER BY cep'
    );

    if (!is_null($cep)) {
        $cep = preg_replace('/\D/', '', $cep);
        if (!in_array($cep, $CEPs))
            return "CEP '$cep' não existe em '$cidade_pesquisa'";
    }

    return array(
        'cidade'  => $cidade,
        'cep'     => $cep,
        'estado'  => $uf
    );
}

function create_filial($_DATA=null) {
    global $con, $login_fabrica;

    if (is_null($_DATA) or !is_array($_DATA))
        $_DATA = $_POST;

    $insData = prepareUserData($_DATA);
    $insData['fantasia'] = $insData['nome_fantasia'];
    unset($insData['nome_fantasia']);

    if (isset($insData['filial']))
        unset($insData['filial']);

    // valida presença de dados obrigatórios
    $presentes = array_intersect(
        explode(',', CAMPOS_OBRIG),
        array_keys(array_filter($insData, 'strlen'))
    );

    if ($_DATA['test']) {
        pre_echo($insData, 'PARSED INPUT');
    }

    if (count($presentes) < 10) {
        return 'Preencha TODAS as informações obrigatórias para poder cadastrar a filial.';
    }

    // confere se já existe uma filial com mesmo CNPJ para este fabricante
    $resfilial = pg_query($con,
        sql_cmd(
            'tbl_filial', 'filial',
            array(
                'cnpj'=> $insData['cnpj'],
                'fabrica' => $login_fabrica
            )
        )
    );
	
	$retorno = checa_cnpj($insData['cnpj']) ; 

	if($retorno) 
		return 'CNPJ inválido' ;

    if (pg_num_rows($resfilial))
        return 'A filial a ser adicionada já existe!';

    // Se passou o nome da cidade, procura na tabela a cidade e estado
    // Ou simplesmente valida.
    // TODO: Mais pra frente, verificar se o ID não é o IBGE...
    $infoCidade = get_city($insData['cidade'], $insData['estado'], $insData['cep']);

    if (!is_array($infoCidade))
        return $infoCidade;

    $insData['fabrica'] = $login_fabrica;
    $insData = array_replace($insData, $infoCidade);

    $sql = sql_cmd('tbl_filial', $insData);

    if ($_DATA['showSql']) {
        echo $sql;
        return '';
    }
    if ($sql[0] === 'I')
        $res = pg_query($con, $sql);

    if (!is_resource($res))
        return ($_serverEnvironment == 'development') ? pg_last_error($con) : 'Erro na gravação da filial';
    // aqui pode melhorar interpretando o erro, p.e. de duplicidade de nome, fantasia ou código filial...

    return (pg_affected_rows($res) == 1);

}

function get_filial($_INPUT=null) {
    global $con, $login_fabrica;

    if (is_null($_INPUT))
        $_INPUT = $_GET;

    $_DATA = array_replace(prepareUserData($_INPUT));

    if ($_REQUEST['test']) {
        pre_echo($_DATA, 'PARSED INPUT');
    }

    $limit = $_INPUT['limit'] ? 'LIMIT ' . $_INPUT['limit'] : '';
    extract($_DATA);

    if ($cnpj)
        $where['cnpj'] = (isset($_INPUT['ajax']) and strlen($cnpj) < 14) ? "$cnpj%" : $cnpj;

    if ($cod_filial)
        $where['cod_filial'] = $cod_filial;

    if ($nome and !$nome_fantasia)
        $where['fn_retira_especiais(tbl_filial.nome)='] = "~* E'".tira_acentos($nome)."' ";

    if ($nome_fantasia and !$nome)
        $where['fn_retira_especiais(fantasia)='] = "~* E'".tira_acentos($nome_fantasia)."' ";

    if ($nome_fantasia and $nome)
        $where['busca_por_nome'] = array(
            'fn_retira_especiais(tbl_filial.nome)=' => "~* E'".tira_acentos($nome)."' ",
            '@fn_retira_especiais(fantasia)=' => "~* E'".tira_acentos($nome_fantasia)."' "
        );

    if (is_numeric($cidade))
        $where['cidade'] = $cidade;

    if ($estado)
        $where['tbl_filial.estado'] = strtoupper($estado);

    // se recebeu apenas a filial, então filtra apenas por filial e fábrica
    if ($filial)
        $where = array('filial' => $filial);

    $where['fabrica'] = $login_fabrica;

    $sql = sql_cmd('tbl_filial JOIN tbl_cidade USING(cidade)', CAMPOS_FILIAL, $where);
    // die($sql);

    if ($_REQUEST['showSql']) {
        echo $sql;
        die;
    }

    if ($sql[0] == 'S')
        $res = pg_query($con, $sql);

    if (!is_resource($res))
        return ($_serverEnvironment == 'development') ? pg_last_error($con) : 'Erro na consulta da filial';

    if ($rows = pg_num_rows($res)) {
        return pg_fetch_all($res);
    }
    return array();
}

function update_filial($_DATA) {
    global $con, $login_fabrica;

    $usrData = prepareUserData($_DATA);

    if ($_REQUEST['test'] or $_DATA['test']) {
        pre_echo($usrData, 'PARSED INPUT');
    }

    $filial = $usrData['filial'];

    if (empty($filial)) {
        $filial = $_DATA['filial_id'];
    }

    $where = array(
        'filial' => (int) $filial,
        'cnpj' => $usrData['cnpj']
    );

    if (count(array_filter($where)) == 0) {
        return 'Informe o ID ou o CNPJ da filial a ser atualizada';
    }

    $where['fabrica'] = $login_fabrica;
    $resfilial = pg_query($con, sql_cmd('tbl_filial', '*', $where));

    if (!pg_num_rows($resfilial))
        return 'A filial a ser atualizada não existe!';

    $dbData = pg_fetch_assoc($resfilial, 0);
    // retira campos-chave, para evitar sobreescrever o índice
    unset($dbData['filial'], $dbData['fabrica']);

    $infoCidade = get_city($usrData['cidade'], $usrData['estado'], $usrData['cep']);

    if (!is_array($infoCidade))
        return $infoCidade;

    $usrData = array_replace($usrData, $infoCidade);

    foreach ($dbData as $field=>$data) {
        if ($data != $usrData[$field])
            $formData[$field] = $usrData[$field];
    }

    // Deixa um array com os elementos dos dados do usuário que são diferentes
    // dos dados que vieram da consulta no banco de dados.
    $sqlUpdateData = array_diff($formData, $dbData);

    if(count($sqlUpdateData)) {
        $sql = sql_cmd('tbl_filial', $sqlUpdateData, $where);

        if ($_REQUEST['showSql'] or $_DATA['showSql']) {
            pre_echo($sql);
            die;
        }
        if ($sql[0] == 'U') {
            $res = pg_query($con, $sql);

            if (!is_resource($res))
                return ($_serverEnvironment == 'development') ? pg_last_error($con) : 'Erro na gravação da filial';
            // aqui pode melhorar interpretando o erro, p.e. de duplicidade de nome, fantasia ou código filial...

            return (pg_affected_rows($res) == 1);
        }
    }
    return 'Nada a atualizar.';
}

/**
 * AJAX server
 * Busca por nome, fantasia ou CNPJ
 **/
if (isset($_GET['ajax']) and $_GET['search_by']) {

    $campo   = getPost('search_by');
    $valor   = getPost('q');
    $retJSON = ($_GET['type']=='json');
    $retStr  = '';

    if ($campo and $valor) {
        $_GET[$campo] = $valor;
        $rows = get_filial($_GET);

        if (count($rows)) {
            $result = array();
            foreach ($rows as $row) {
                $row = formatResult($row);
                $result[] = ($retJSON) ? array_map('utf8_encode', $row) : implode('|', $row);
            }
            $retStr = ($retJSON) ? json_encode($result) : implode("\n", $result);
        } else {
            $retStr = $valor.'|Nenhuma filial encontrada';
        }
    }
    if (($retJSON))
        header("Content-Type: application/json; charset=utf-8");
    else
        header("Content-Type: text/plain");
    die($retStr);
}

/**
 * AJAX Gravar NOVA Filial
 * AJAX Alterar Filial
 */
if (isset($_POST['ajax'])) {
    if ($_POST['ajax'] == 'create') {
        $gravar = create_filial($_POST);
    } else if ($_POST['ajax'] == 'edit') {
        $gravar = update_filial($_POST);
    }

    if ($gravar === true) {
        $info = get_filial(array('cnpj'=>$_POST['cnpj']));

        if (is_array($info)) {
            die (implode('|', formatResult($info[0])));
        }
    } else if ($gravar === false) {
        die ("ERRO:Erro desconhecido ao gravar as informações da filial");
    }
    die ($gravar);
}

/**
 * web service mode:
 * GET    para consultar
 * POST   para inserir
 * PUT    para isnerir/alterar
 * DELETE para excluir
 * deve passar parâmetro 'x-ws-client: POSVENDA' no HEADER
 */
if (isset($_SERVER['HTTP_X_WS_CLIENT']) and $_SERVER['HTTP_X_WS_CLIENT'] == 'POSVENDA') {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'PUT' || $method == 'DELETE') {
        parse_str(file_get_contents('php://input'), $request_parameters);
        $GLOBALS["_{$method}"] = $request_parameters;
        // Add these request vars into _REQUEST, mimicing default behavior, PUT/DELETE will override existing COOKIE/GET vars
        $_REQUEST = $request_parameters + $_REQUEST;
    }

    // Devel e talvez posvenda, não estão configurados para receber requisições
    // como método DELETE.
    if ($method == 'GET' and $_GET['request_action']=='DELETE')
        $method = 'DELETE';

    switch ($method) {
        case 'GET':
            $dados = get_filial();

            if (is_array($dados)) {
                header('Content-Type: application/json');
                die (json_encode(array_map('utf8_encode', $dados)));
            }
            header("HTTP/1.0 400 $dados");
        break;

        case 'POST':
            if ($method == 'POST' and count($_POST) < 9) {
                header('HTTP/1.0 400 Needs more data');
                pre_echo($_POST, 'Preencha dos dados necessários para o cadastro', true);
            }

            if (create_filial() === true)
                header('HTTP/1.0 201 Created');
            else
                header("HTTP/1.0 400 $status");
        break;

        // PUT - Atualiza dados da revenda
        case 'PUT':
            $status = update_filial($_REQUEST);

            if ($status === true)
                header('HTTP/1.0 201 Updated');
            else if ($status == 'Nada a atualizar.')
                header("HTTP/1.0 200 $status");
            else
                header("HTTP/1.0 400 $status");
        break;

        case 'DELETE':
            if ($_REQUEST['cnpj']) {
                $cnpj = preg_replace('/\D/', '', $_REQUEST['cnpj']);
                // esta condição muda se o país <> 'BR', quando tbl_filial tiver país...
                if (strlen($cnpj) !== 14)
                    die ('Invalid CNPJ');
                $where['cnpj'] = $cnpj;
            }

            if ($_REQUEST['filial'])
                $where['filial'] = (int)$_REQUEST['filial'];

            $where['fabrica'] = $login_fabrica;

            if (count($where) === 1)
                die('Invalid filters for DELETE action');

            $sql = sql_cmd('tbl_filial', 'delete', $where);
            header('HTTP/1.0 204 Deleted');
        break;
    }
    // header('Content-Type: application/json');

    pre_echo($sql, $method.' METHOD');
    pre_echo(sql_cmd('tbl_filial', $_GET, 125), "UPDATE");
    die (json_encode(array('SQL' => utf8_encode($sql))));
}

header('HTTP/1.0 401 Unauthorized');
