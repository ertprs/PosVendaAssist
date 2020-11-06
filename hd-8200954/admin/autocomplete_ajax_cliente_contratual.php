<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

include_once '../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

/**
 * formata CNPJ ou CPF para saída
 */
$fmtCPFJ = function($str) {
    if (!defined('RE_FMT_CNPJ'))
        throw new Exception('Erro no programa.');

    $str = preg_replace('/\D/', '', $str);
    if (strlen($str)<10 or strlen($str)>14)
        return false;

    if (strlen($str) == 10 or strlen($str) == 13)
        $str = '0'.$str;

    return strlen($str) > 11 ?
        preg_replace(RE_FMT_CNPJ, '$1.$2.$3/$4-$5', $str) :
        preg_replace(RE_FMT_CPF,  '$1.$2.$3-$4', $str);
};

function getCliente($_DATA, $utf8 = false) {
    global $con, $estados_BR, $fmtCPFJ;
    $where = array_intersect_key($_DATA, array_flip(array('cpf','nome','estado','cep','codigo_cliente')));

		$limit = $_DATA['limit'] ? "LIMIT {$_DATA['limit']}" : '';

    // Validações
    if (isset($where['cpf'])) {
        $cpf = preg_replace('/\D/', '', $_DATA['cpf']);
        if (strlen($cpf) < 10)
            $cpf .= '%';
        $where['cpf'] = $cpf;
    }

    if (isset($where['estado'])) {
        if (!in_array($_DATA['estado'], $estados_BR))
            unset($where['estado']);
    }

    if (isset($where['nome']) and strlen($where['nome'])) {
        $where['UPPER(nome)'] .= strtoupper(tira_acentos($where['nome'])).'%';
        unset($where['nome']);
    }

    $res = pg_query($con, sql_cmd('tbl_cliente', '*', $where) . $limit);
    if (!$res)
        die('Erro ao consultar o cliente');

    if (!count($clientes = pg_fetch_all($res)))
        return 0;

    foreach ($clientes as $cliente) {
        if ($utf8)
            $cliente = array_map('utf8_encode', $cliente);

        $id = $cliente['cliente'];
        $ret[$id] = array(
            'cpf'      => $fmtCPFJ($cliente['cpf'])?:$cliente['cpf'],
            'nome'     => $cliente['nome'],
            'email'    => $cliente['email'],
            'fone'     => phone_format($cliente['fone']),
            'endereco' => array(
                'endereco'    => $cliente['endereco'],
                'numero'      => $cliente['numero'],
                'complemento' => $cliente['complemento'],
                'bairro'      => $cliente['bairro'],
                'cep'         => $cliente['cep'],
                'cidade'      => $cliente['cidade'],
                'estado'      => $cliente['estado'],
            ),
            'codigo_cliente'   => $cliente['codigo_cliente'],
            'nome_fantasia'    => $cliente['nome_fantasia'],
            'consumidor_final' => (bool)$cliente['consumidor_final']
        );
        // Nome Fantasia é apenas empresa...
        if (strlen($cliente['cpf'] == 11))
            unset($ret[$cliente['cliente']]['nome_fantasia']);

        // Autocomplete AJAX?
        if (isset($_GET['term'])) {
            $ret[$id]['cod'] = $fmtCPFJ($cliente['cpf'])?:$cliente['cpf'];
            $ret[$id]['desc'] = $cliente['nome'];
        }
    }
    return $ret;
}

if (count($_GET) and isset($_GET['term'])) {
    $q = utf8_decode(getPost('term'));
    $campo = getPost('search');
		$filtro['limit'] = getPost('limit') ? : 10;

    if ($campo and !in_array($campo, array('cod','desc')))
        die('{"erro":"Consulta inválida"}');

    // Padrão se não informa nada: CPF
		// MAS, se o valor é numérico, assume que é CPF, senão, nome
    $campo = ($campo == 'desc' or (!isset($_GET['search']) and !is_numeric($q))) ? 'nome' : 'cpf';
    $filtro[$campo] = $q;

    if (isset($_GET['estado']))
        $filtro['estado'] = getPost('estado');

    $clientes = getCliente($filtro, true);

    foreach($clientes as $id=>$cl) {
        $ret[] = array_merge(
            array('cliente'=>$id),
            $cl

        );
    }
    die (json_encode($ret));
    die(implode(PHP_EOL,$ret));
}

die("[]");

