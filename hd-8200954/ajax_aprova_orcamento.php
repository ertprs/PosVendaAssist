<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$orcamento = $_GET['orcamento'];
$acao = $_GET['acao'];

if ($acao == 'aprovar') {
	$sql = "UPDATE tbl_orcamento set aprovado = 't',data_aprovacao = current_date where orcamento = $orcamento";
	$res = pg_exec($con,$sql);
	$erro = pg_errormessage($con);

		$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento = $orcamento";
		$res_chamado = pg_query($con, $sql);
		if(pg_num_rows($res_chamado)>0){
			$hd_chamado = pg_fetch_result($res_chamado,0,hd_chamado);
		}

		$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,comentario) VALUES ($hd_chamado,'Orçamento Aprovado')";
		$res_chamado = pg_query($con, $sql);
		$erro .= pg_errormessage($con);

}

if ($acao == 'reprovar') {
	$sql = "UPDATE tbl_orcamento set aprovado = 'f',data_reprovacao = current_date where orcamento = $orcamento";
	$res = pg_exec($con,$sql);
	$erro = pg_errormessage($con);

		$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento = $orcamento";
		$res_chamado = pg_query($con, $sql);
		if(pg_num_rows($res_chamado)>0){
			$hd_chamado = pg_fetch_result($res_chamado,0,hd_chamado);
		}

		$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,comentario) VALUES ($hd_chamado,'Orçamento Reprovado')";
		$res_chamado = pg_query($con, $sql);
		$erro .= pg_errormessage($con);


}

if (strlen($erro)==0) {
	echo "Orçamento Aprovado com Sucesso!!";
}
else {
	echo "Ocorreu um erro ao gravar Orcamento |";
}


?>
