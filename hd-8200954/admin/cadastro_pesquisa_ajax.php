<?php
// 408341
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
header('Content-Type: text/html; charset=iso-8859-1');
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$pesquisa = (int) $_GET['pesquisa'];
$pergunta = (int) $_GET['pergunta'];
$login_fabrica = (int) $_GET['fabrica'];
if(empty($pesquisa) ) {
    echo '<tr><td colspan="5">Erro na passagem de par&atilde;metros</td></tr>';
    exit;
}

if ( !empty($pergunta) ) {

	$sql = "DELETE FROM tbl_pesquisa_pergunta WHERE pesquisa = $pesquisa AND pergunta = $pergunta";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	echo ( empty( $msg_erro ) ) ? 't' : $msg_erro;
	exit;

}

if($login_fabrica == 30){
	$left_cond = " LEFT ";
}

$sql = "SELECT tbl_pergunta.pergunta,tbl_pergunta.descricao,CASE WHEN tbl_pergunta.ativo IS TRUE THEN 'Ativo' ELSE 'Inativo' END AS ativo, tbl_pesquisa_pergunta.ordem,tbl_pesquisa.texto_ajuda,tbl_tipo_pergunta.descricao as tipo_pergunta
		FROM tbl_pesquisa_pergunta
		JOIN tbl_pergunta USING(pergunta)
		JOIN tbl_pesquisa USING(pesquisa)
		{$left_cond} JOIN tbl_tipo_pergunta USING(tipo_pergunta)
		WHERE pesquisa = $pesquisa order by tbl_tipo_pergunta.descricao desc, tbl_pesquisa_pergunta.ordem desc";

$res = pg_query($con,$sql);

$result = array();

if ( pg_num_rows($res) == 0 ) {
	$result[0]['pergunta'] = null;
	$result[0]['descricao'] = 'Nenhuma pergunta cadastrada';
}

for($i = 0; $i < pg_num_rows($res); $i++ ) {

	$result[$i]['pergunta']    = pg_result($res,$i,'pergunta');
	$result[$i]['descricao']   = utf8_encode (pg_result($res,$i,'descricao') );
	$result[$i]['ativo']       = pg_result($res,$i,'ativo');
	$result[$i]['ordem']       = pg_result($res,$i,'ordem');
	$result[$i]['texto_ajuda'] = utf8_encode(pg_result($res,$i,'texto_ajuda'));
	$result[$i]['tipo_pergunta'] = utf8_encode(pg_result($res,$i,'tipo_pergunta'));

}

echo json_encode($result);
