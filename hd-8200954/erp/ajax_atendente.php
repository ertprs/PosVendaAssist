<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'ajax_cabecalho.php';

if($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar') {
	$hd_chamado = trim($_GET["hd_chamado"]);
	$atendente  = trim($_GET["atendente"]);

	//INSERE ATENDENTE DO CHAMADO
	if(strlen($atendente) > 0 ){
		$sql = "SELECT * FROM tbl_hd_chamado_atendimento WHERE empregado = $atendente AND  hd_chamado = $hd_chamado";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) == 0) {
			$sql = "INSERT INTO tbl_hd_chamado_atendimento (empregado,hd_chamado) VALUES ($atendente,$hd_chamado)";
			$res = pg_exec ($con,$sql);
		}
	}

	$sql = "SELECT * FROM tbl_hd_chamado_atendimento WHERE hd_chamado = $hd_chamado";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows($res); $i++){
			$empregado  = pg_result($res,$i,empregado);
			$sql2 = "SELECT nome     ,
					email
				FROM tbl_empregado 
				JOIN tbl_pessoa USING(pessoa)
				WHERE empregado = $empregado";
			$res2 = @pg_exec ($con,$sql2);
			$nome  = pg_result($res2,0,0);
			$nome_abreviado = explode (' ',$nome);
			$nome_abreviado = $nome_abreviado[0];
	
			$resposta .= "<b>$nome_abreviado</b><br>";
		}
	}else $resposta .= "Nenhum antendente cadastrado";
	echo "ok|$resposta";
	exit;
}

?>