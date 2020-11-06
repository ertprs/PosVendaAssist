<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

include_once '../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';


function getCliente($term, $utf8 = false) {
    global $con, $login_fabrica;
  
    $ilike = "tbl_os.consumidor_cpf ILIKE '{$term}%' OR TO_ASCII(tbl_os.consumidor_cpf, 'LATIN-9') ILIKE TO_ASCII('{$term}%', 'LATIN-9')";

	$sql = "SELECT  tbl_os.consumidor_cpf, 
					tbl_os.consumidor_nome,
					tbl_os.consumidor_cep,
					tbl_os.consumidor_estado,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_bairro,
					tbl_os.consumidor_endereco,
					tbl_os.consumidor_numero,
					tbl_os.consumidor_complemento,
					tbl_os.consumidor_celular,
					tbl_os.consumidor_fone_comercial,
					tbl_os.consumidor_email
			FROM tbl_os
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND {$ilike}
			AND tbl_os.data_abertura BETWEEN CURRENT_DATE AND CURRENT_DATE - INTERVAL '2 YEAR'
			";
	$res = pg_query($con,$sql);

    if (!$res)
        die('Erro ao consultar o cliente');

    if (!count($clientes = pg_fetch_all($res)))
        return 0;

    foreach ($clientes as $cliente) {
        if ($utf8)
            $cliente = array_map('utf8_encode', $cliente);

        $id = $cliente['consumidor_cpf'];
        $ret[$id] = array(
        	'cod'                       => $cliente['consumidor_cpf'],
        	'desc'                      => $cliente['consumidor_nome'],
            'consumidor_cpf'            => $cliente['consumidor_cpf'],
            'consumidor_nome'           => $cliente['consumidor_nome'],
            'consumidor_cep'            => $cliente['consumidor_cep'],
            'consumidor_estado'         => $cliente['consumidor_estado'],
            'consumidor_cidade'         => $cliente['consumidor_cidade'],
            'consumidor_bairro'         => $cliente['consumidor_bairro'],
            'consumidor_endereco'       => $cliente['consumidor_endereco'],
            'consumidor_numero'         => $cliente['consumidor_numero'],
            'consumidor_complemento'    => $cliente['consumidor_complemento'],
            'consumidor_celular'        => $cliente['consumidor_celular'],
            'consumidor_fone_comercial' => $cliente['consumidor_fone_comercial'],
            'consumidor_email'          => $cliente['consumidor_email']
        );
    }
    return $ret;
}

if (count($_GET) and isset($_GET['term'])) {
    $term = $_GET['term'];

    $clientes = getCliente($term, true);

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

