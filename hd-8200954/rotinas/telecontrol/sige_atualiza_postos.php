<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../admin/sige_sync.php';
include dirname(__FILE__) . '/../../funcoes.php';

$sql = "SELECT p.posto,
        CASE
        WHEN LENGTH( TRIM( p.nome_fantasia ) ) = 0 THEN p.nome
        WHEN p.nome_fantasia IS NULL THEN p.nome
        ELSE p.nome_fantasia
        END,
        p.nome, p.cnpj , p.ie, p.endereco, p.numero,
        p.complemento, p.bairro, p.cidade, p.pais, p.cod_ibge AS cod_ibge_cidade,
        p.cep, p.estado, p.fone,
        CASE WHEN p.email IS NULL OR trim(p.email) = '' THEN
	  (
	    SELECT contato_email FROM tbl_posto_fabrica AS epf
	    JOIN tbl_fabrica AS ef USING (fabrica)
	    WHERE ativo_fabrica IS TRUE
	    AND posto = p.posto
	    AND LOWER( ef.parametros_adicionais::json->>'telecontrol_distrib') IN ( 't', 'true' )
	    AND contato_email IS NOT NULL
	    AND trim(contato_email) <> ''
	    LIMIT 1
	   )
	ELSE
	  p.email
	END AS email
    FROM tbl_posto AS p
    WHERE p.posto IN (
        SELECT DISTINCT( posto )
        FROM tbl_posto_fabrica AS pf
        JOIN tbl_fabrica AS f USING (fabrica)
        WHERE ativo_fabrica IS TRUE
        AND credenciamento = 'CREDENCIADO'
        AND LOWER( f.parametros_adicionais::json->>'telecontrol_distrib') IN ( 't', 'true' )
    )";

if( PHP_SAPI == 'cli' ) {
    echo "Separando os registros...\n";
}

$res = pg_query( $con, $sql );
$fp = logFile( 'sige_sync_pessoas' );
$fmt = 'Y-m-d H:i:s';
$sige = array();
$debug = file_exists('devel');

$registros = pg_num_rows( $res );

if ( PHP_SAPI == 'cli' ) {
    $progressBar = "0%..........................50%.........................100%";
    echo "Sincronizando $registros registros.\n" . $progressBar . "\n";
    $step = intdiv( $registros, strlen( $progressBar ) );
}


for ($i = 0; $i < $registros; $i++ ) {
    $posto = pg_fetch_result( $res, $i, 'posto' );
    $nome_fantasia = pg_fetch_result( $res, $i, 'nome' );
    $nome = pg_fetch_result( $res, $i, 'nome' );
    $xcnpj = pg_fetch_result( $res, $i, 'cnpj' );
    $ie = pg_fetch_result( $res, $i, 'ie' );
    $endereco = pg_fetch_result( $res, $i, 'endereco' );
    $numero = pg_fetch_result( $res, $i, 'numero' );
    $complemento = pg_fetch_result( $res, $i, 'complemento' );
    $bairro = pg_fetch_result( $res, $i, 'bairro' );
    $cidade = pg_fetch_result( $res, $i, 'cidade' );
    $pais = pg_fetch_result( $res, $i, 'pais' );
    $cod_ibge_cidade = pg_fetch_result( $res, $i, 'cod_ibge_cidade' );
    $cep = pg_fetch_result( $res, $i, 'cep' );
    $estado = pg_fetch_result( $res, $i, 'estado' );
    $fone = pg_fetch_result( $res, $i, 'fone' );
    $email = pg_fetch_result( $res, $i, 'email' );

    if (empty($xcnpj) or empty($email)) {
		continue;
	}

    $dataInicio = date($fmt);
    if ( $debug == false ) {
        $sige = sige_sync_pessoa();
    }

    if ( PHP_SAPI == 'cli' && $i % $step == 0 ) {
        echo '.';
    }

    $dataTermino = date($fmt);
    $log = "POSTO: $posto - [$dataInicio - $dataTermino] : " . $sige["code"] . ", " . $sige["message"] . "\n";
    fwrite( $fp, $log );
}

if( PHP_SAPI == 'cli' ) {
    echo "\n";
}

fclose( $fp );
