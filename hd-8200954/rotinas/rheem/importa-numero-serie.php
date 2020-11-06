<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

// $caminho = "/var/www/assist/www/admin/xls/numero_serie.txt";
$caminho = "/tmp/rheem/num_serie.txt";

if(!file_exists($caminho)){
	$erro .= "Arquivo não encontrado. ";
}

$arquivo = file($caminho);

$login_fabrica = 154;
try{

	$sql = "DROP TABLE if exists rheem_numero_serie;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "
			CREATE TABLE rheem_numero_serie (
				txt_serie              CHARACTER VARYING(20) ,
				txt_referencia_produto CHARACTER VARYING(50) ,
				txt_cnpj         CHARACTER VARYING(14)
			); ";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "GRANT ALL on rheem_numero_serie to telecontrol;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	/*
		A variável $arquivo é um array de String, por isso o explode resulta NULL, 
	fazendo com que não entre no foreach
	*/
	// $linha = explode("\n", $arquivo);

	foreach($arquivo as $key => $l){
		list($serie, $produto, $cnpj) = explode("\t", $l);
		$cnpj = trim($cnpj);

		$sql = "
			INSERT INTO rheem_numero_serie
			(txt_serie, txt_referencia_produto, txt_cnpj)
			VALUES
			('$serie', '$produto', '$cnpj')";
		$res = pg_query($con, $sql);
	}

	$sql = "ALTER TABLE rheem_numero_serie ADD column produto int4";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "
		UPDATE rheem_numero_serie
			SET produto = tbl_produto.produto
		FROM tbl_produto
		WHERE tbl_produto.fabrica_i = $login_fabrica
		AND upper(trim(rheem_numero_serie.txt_referencia_produto)) = upper(trim(tbl_produto.referencia))";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "DROP TABLE if exists rheem_numero_serie_falha;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "ALTER TABLE rheem_numero_serie ADD COLUMN tem_serie boolean;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "UPDATE  rheem_numero_serie
				SET tem_serie = 't'
			FROM tbl_numero_serie
			WHERE tbl_numero_serie.serie = rheem_numero_serie.txt_serie
			AND   tbl_numero_serie.produto = rheem_numero_serie.produto
			AND   tbl_numero_serie.fabrica = $login_fabrica;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	/* Número de Series Nulos */
	$sql = "
		SELECT *
		INTO TEMP rheem_numero_serie_falha
		FROM rheem_numero_serie
		WHERE rheem_numero_serie.produto IS NULL";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	/* Deleta Número de Séries com Erro */
	$sql = "DELETE FROM rheem_numero_serie WHERE rheem_numero_serie.produto IS NULL";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);
	$msg_erro = null;
	/* Begin */
	pg_query($con, "BEGIN");

	$sql = "SELECT count(rheem_numero_serie.txt_serie) AS total_update
			FROM rheem_numero_serie
			JOIN tbl_numero_serie ON tbl_numero_serie.serie = rheem_numero_serie.txt_serie
			AND tbl_numero_serie.produto = rheem_numero_serie.produto
			AND tbl_numero_serie.fabrica = $login_fabrica";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);
	$total_update = pg_fetch_result($res, 0, 'total_update');

	pg_query($con, "BEGIN TRANSACTION");

	$sql = "
		INSERT INTO tbl_numero_serie (
			fabrica           ,
			serie             ,
			referencia_produto,
			cnpj        ,
			produto
		)
		SELECT DISTINCT
			$login_fabrica        ,
			txt_serie             ,
			txt_referencia_produto,
			txt_cnpj,
			produto
		FROM rheem_numero_serie
		WHERE tem_serie is not true;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "SELECT count(txt_serie) AS total_insert
		FROM rheem_numero_serie
		WHERE tem_serie is not true";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);
	$total_insert = pg_fetch_result($res, 0, 'total_insert');

	if(strlen($msg_erro) > 0){

		pg_query($con, "ROLLBACK");

		echo $mensagem = $msg_erro;

	}else{
		pg_query($con, "COMMIT");
		// echo "Total de $total_insert registro cadastrado";
	}

	$data_sistema = date("Y_m_d_h_i_s");

	system ("cp $caminho /tmp/rheem/numero_serie_$data_sistema.txt");


}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - rheem - Importa faturamento (importa-faturamento.php)", $msg);

}
?>
