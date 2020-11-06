<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../funcoes.php';

use Posvenda\Log;

$caminho = "/home/brother/brother-telecontrol/ShipConf*";
$login_fabrica = 167;

try{
    $begin = false;
    $sql = "DROP TABLE if exists brother_numero_serie;";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
    	throw new Exception(pg_last_error());
    }

    $sql = "
            CREATE TABLE brother_numero_serie (
                txt_serie               CHARACTER VARYING(30) ,
                txt_referencia_produto  CHARACTER VARYING(30) 
            ); ";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $sql = "GRANT ALL on brother_numero_serie to telecontrol;";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $arquivos = glob($caminho, GLOB_BRACE);

    if (empty($arquivos)) {
	throw new Exception("Nenhum arquivo encontrado");
    }

    foreach($arquivos as $arquivo){
	    $arquivo = explode("\n", file_get_contents($arquivo));

	    foreach($arquivo as $key => $l){
		    if (empty($l)) {
			continue;
		    }

		    list(
			$cnpj, 
			$pedido, 
			$peso_bruto,
			$qtde_volume,
			$sequencia,
			$codigo_produto,
			$qtde_produto,
			$filler,
			$unidade_medida,
			$peso_liquido,
			$tipo_estoque,
			$numero_serie
		    ) = explode(";", $l);

		    $numero_serie = trim($numero_serie);
		    $codigo_produto = trim($codigo_produto);

		    if (empty($numero_serie) || empty($codigo_produto)) {
			    continue;
		    }

		    $sql = "
			    INSERT INTO brother_numero_serie
			    (txt_serie, txt_referencia_produto)
			    VALUES
			    ('$numero_serie', '$codigo_produto')";
		    $res = pg_query($con, $sql);
	    }
    }

    $sql = "ALTER TABLE brother_numero_serie ADD column produto int4";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $sql = "
        UPDATE  brother_numero_serie
        SET     produto = tbl_produto.produto
        FROM    tbl_produto
        WHERE   tbl_produto.fabrica_i = $login_fabrica
        AND     UPPER(TRIM(brother_numero_serie.txt_referencia_produto)) = UPPER(TRIM(tbl_produto.referencia))";

    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $sql = "DROP TABLE if exists brother_numero_serie_falha;";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $sql = "ALTER TABLE brother_numero_serie ADD COLUMN tem_serie boolean;";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $sql = "UPDATE  brother_numero_serie
            SET     tem_serie = 't'
            FROM    tbl_numero_serie
            WHERE   tbl_numero_serie.serie      = brother_numero_serie.txt_serie
            AND     tbl_numero_serie.produto    = brother_numero_serie.produto
            AND     tbl_numero_serie.fabrica    = $login_fabrica;";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    /* Número de Series Nulos */
    $sql = "
        SELECT  *
            INTO TEMP    brother_numero_serie_falha
        FROM    brother_numero_serie
        WHERE   brother_numero_serie.produto IS NULL
    ";

    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }
exit;
    /* Deleta Número de Séries com Erro */
    $sql = "DELETE FROM brother_numero_serie WHERE brother_numero_serie.produto IS NULL";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    /* Begin */
    pg_query($con, "BEGIN");
    $begin = true;

    $sql = "SELECT COUNT(brother_numero_serie.txt_serie) AS total_update
            FROM    brother_numero_serie
            JOIN    tbl_numero_serie ON tbl_numero_serie.serie = brother_numero_serie.txt_serie
            AND     tbl_numero_serie.produto = brother_numero_serie.produto
            AND     tbl_numero_serie.fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $total_update = pg_fetch_result($res, 0, 'total_update');

    $sql = "
        INSERT INTO tbl_numero_serie (
            fabrica             ,
            serie               ,
            referencia_produto  ,
            produto
        )
        SELECT  DISTINCT
                $login_fabrica        ,
                txt_serie             ,
                txt_referencia_produto,
                produto
        FROM    brother_numero_serie
        WHERE   tem_serie IS NOT TRUE;";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $sql = "SELECT count(txt_serie) AS total_insert
        FROM brother_numero_serie
        WHERE tem_serie IS NOT TRUE";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception(pg_last_error());
    }

    $total_insert = pg_fetch_result($res, 0, 'total_insert');

    pg_query($con, "COMMIT");

    foreach($arquivos as $arquivo){
    	system ("mv $arquivo /tmp/brother/".basename($arquivo));
    }
}catch(Exception $e){
    if ($begin === true) {
	    pg_query($con, "ROLLBACK");
    }

    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage();
    $logClass = new Log2();
    $logClass->adicionaLog($msg);

    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail('ronald.santosk@telecontrol.com.br');
    }

    $logClass->enviaEmails();
}
