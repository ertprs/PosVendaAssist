<?php

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

// $caminho = "/var/www/assist/www/admin/xls/numero_serie.txt";
$caminho = "/tmp/tecvoz/num_serie.txt";

if(!file_exists($caminho)){
    $erro .= "Arquivo não encontrado. ";
}

$arquivo = file($caminho);
// print_r($arquivo);exit;
$login_fabrica = 165;
try{

    $sql = "DROP TABLE if exists tecvoz_numero_serie;";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "
            CREATE TABLE tecvoz_numero_serie (
                txt_serie               CHARACTER VARYING(30),
                txt_referencia_produto  CHARACTER VARYING(20),
                txt_data_fabricacao     CHARACTER VARYING(11)
            ); ";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "GRANT ALL on tecvoz_numero_serie to telecontrol;";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    /*
        A variável $arquivo é um array de String, por isso o explode resulta NULL,
    fazendo com que não entre no foreach
    */
    // $linha = explode("\n", $arquivo);

    foreach($arquivo as $key => $l){
	list($serie, $produto, $dataF) = explode("\t", $l);

        $serie = trim($serie);
        $produto = trim($produto);
        $dataF = trim($dataF);

	if (!substr_count($dataF,"-",0,2)) {
            $msg_erro .= "Data no formato inválido";
        }
	
	$sql = "
            INSERT INTO tecvoz_numero_serie
            	(txt_serie, txt_referencia_produto, txt_data_fabricacao)
            VALUES
	    	('{$serie}', '{$produto}', '{$dataF}');
	";

	$res = pg_query($con, $sql);
    }

    $sql = "ALTER TABLE tecvoz_numero_serie ADD column produto int4";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "
        UPDATE tecvoz_numero_serie
        SET produto = tbl_produto.produto
        FROM tbl_produto
        WHERE tbl_produto.fabrica_i = $login_fabrica
	AND UPPER(TRIM(tecvoz_numero_serie.txt_referencia_produto)) = UPPER(TRIM(tbl_produto.referencia));
    ";

    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "DROP TABLE if exists tecvoz_numero_serie_falha;";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE tecvoz_numero_serie ADD COLUMN tem_serie boolean;";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "UPDATE  tecvoz_numero_serie
            SET     tem_serie = 't'
            FROM    tbl_numero_serie
            WHERE   tbl_numero_serie.serie      = tecvoz_numero_serie.txt_serie
            AND     tbl_numero_serie.produto    = tecvoz_numero_serie.produto
            AND     tbl_numero_serie.fabrica    = $login_fabrica;";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    /* Número de Series Nulos */
    $sql = "
        SELECT  *
   	INTO TEMP    tecvoz_numero_serie_falha
        FROM    tecvoz_numero_serie
        WHERE   tecvoz_numero_serie.produto IS NULL
    ";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    /* Deleta Número de Séries com Erro */
    $sql = "DELETE FROM tecvoz_numero_serie WHERE tecvoz_numero_serie.produto IS NULL";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);
    $msg_erro = null;
    /* Begin */
    pg_query($con, "BEGIN");

    $sql = "SELECT COUNT(tecvoz_numero_serie.txt_serie) AS total_update
            FROM    tecvoz_numero_serie
            JOIN    tbl_numero_serie ON tbl_numero_serie.serie = tecvoz_numero_serie.txt_serie
            AND     tbl_numero_serie.produto = tecvoz_numero_serie.produto
            AND     tbl_numero_serie.fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);
    $total_update = pg_fetch_result($res, 0, 'total_update');

    pg_query($con, "BEGIN TRANSACTION");

    $sql = "
        INSERT INTO tbl_numero_serie (
            fabrica             ,
            serie               ,
            referencia_produto  ,
            data_fabricacao     ,
            produto
        )
        SELECT  DISTINCT
                $login_fabrica        ,
                txt_serie             ,
                txt_referencia_produto,
                txt_data_fabricacao::DATE,
                produto
        FROM    tecvoz_numero_serie
        WHERE   tem_serie IS NOT TRUE;";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "SELECT count(txt_serie) AS total_insert
        FROM tecvoz_numero_serie
        WHERE tem_serie IS NOT TRUE";
    $res = pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);
    $total_insert = pg_fetch_result($res, 0, 'total_insert');

    if(strlen($msg_erro) > 0){

        pg_query($con, "ROLLBACK");

        echo $mensagem = $msg_erro;

    }else{
        pg_query($con, "COMMIT");
    }

    $data_sistema = date("Y_m_d_h_i_s");

    system ("cp $caminho /tmp/tecvoz/numero_serie_$data_sistema.txt");


}catch(Exception $e){

    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - tecvoz - Importa faturamento (importa-faturamento.php)", $msg);

}
