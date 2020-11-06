<?php

error_reporting(E_ERROR | E_PARSE);

$param = array();
for ( $i = 1; $i < $argc; $i++ ) {
    list( $k, $v ) = explode( '=', $argv[$i] );
    $param[ $k ] = $v;
}

if (! $param['fabrica']) {
    echo "ATENÇÃO: fábrica não informada.\nInforme o parâmetro fabrica= no comando\n\n";
    exit(1);
}

$param['fabrica'] = (int) $param['fabrica'] ;

if (! $param['dir']) {
    echo "ATENÇÃO: diretório dos arquivos de vista explodida não informado.\nInforme o parâmetro dir= no comando\n\n";
    exit(2);
}

$produtos = array();

foreach( glob( $param['dir'] . '/*.[pP][dD][fF]' ) as $arquivo ) {
    $produtos[] = array(
        'referencia' => pathinfo( $arquivo, PATHINFO_FILENAME ),
        'arquivo' => $arquivo,
        'extensao' => pathinfo( $arquivo, PATHINFO_EXTENSION )
    );
}

if (count( $produtos ) == 0) {
    echo "NENHUM ARQUIVO PDF ENCONTRADO NO DIRETÓRIO " . $param['dir'] . "\n\n";
    exit(3);
}

require __DIR__ . '/../../dbconfig.php';
require __DIR__ . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/../../class/aws/s3_config.php';

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('ve', (int) $param['fabrica']);
	$S3_online = is_object($s3);
}

if ( ! $S3_online ) {
    echo "ERRO: Erro de conexão com o serviço de armazenamento (AWS S3/Tdocs)";
    exit(10);
}

$result = pg_prepare( $con, 'getProduto',
    'SELECT produto FROM tbl_produto WHERE fabrica_i = $1 AND referencia = $2' );

$result = pg_prepare( $con, 'insertComunicado',
    'INSERT INTO tbl_comunicado' .
    ' (fabrica, tipo, produto, extensao, descricao, ativo, obrigatorio_os_produto)' .
    ' VALUES' .
    ' ($1, $2, $3, $4, $5, $6, $7)' .
    ' RETURNING comunicado');

for ( $i = 0; $i < count( $produtos ); $i++ ) {
    echo "Processando arquivo " . $produtos[$i]['arquivo'] ;

    $data = array(
        $param['fabrica'],
        $produtos[$i]['referencia']
    );

    $resGetProduto = pg_execute( $con, 'getProduto', $data );
    if ( $resGetProduto == false ) {
        // @todo: antes de reiniciar o loop, logar o problema
        echo " - FALHA1: " . pg_last_error() . "\n" ;
        continue;
    }

    $produto = pg_fetch_result( $resGetProduto, 0, 'produto' );
    if ( ! $produto ) {
        echo " - FALHA: Produto não encontrado\n";
        continue;
    }

    $data = array(
        $param['fabrica'],
        'Vista Explodida',
        $produto,
        strtolower( $produtos[$i]['extensao'] ),
        $produtos[$i]['referencia'] . '.' . $produtos[$i]['extensao'],
        't',
        'f'
    );

    pg_query( $con, 'BEGIN TRANSACTION' );

    $resInsert = pg_execute( $con, 'insertComunicado', $data );
    if ( $resInsert == false ) {
        // @todo: antes de reiniciar o loop, logar o problema
        echo " - FALHA2: " . pg_last_error() . "\n";
        pg_query( $con, 'ROLLBACK' );
        continue;
    }

    if ( $s3->uploadFileS3 ( 
        pg_fetch_result( $resInsert, 0, 'comunicado' ),
        $produtos[$i]['arquivo'] ) )
    {
        pg_query( $con, 'COMMIT' );
        echo " - OK\n";

    } else {
        // @todo: logar o problema
        echo " - FALHA3: " . pg_last_error() . "\n";
        pg_query( $con, 'ROLLBACK' );
    }
}

echo count( $produtos ) . " arquivo(s) processado(s)\n\n";
