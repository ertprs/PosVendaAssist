<?
//as tabs definem a categoria do chamado
/* OBSERVACAO HBTECH
	* O produto Hibeats possui uma garantia estendida, ou seja, 1 ano de garantia normal e se ele entrar no site do hibeats ou solicitar via SAC a extensão o cliente ganha mais 6 meses de garantia ficando com 18 meses.
	* Para verificar os produtos que tem garantia estendida acessamos o bd do hibeats (conexao_hbflex.php) e verificamos o número de série.
		* Todos numeros de series vendidos estao no bd do hibeats, caso nao esteja lá não foi vendido ou a AKabuki não deu carga no bd.
		* AKabuki é a agencia que toma conta do site da hbflex, responsavel pelo bd e atualizacao do bd. Contato:
			Allan Rodrigues
			Programador
			AGÊNCIA KABUKI
			* allan@akabuki.com.br
			* www.akabuki.com.br
			( 55 11 3871-9976
	** Acompanhar os lancamentos destas garantias, liberado no ultimo dia do ano e ainda estamos acompanhando

*/



include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

header('Content-Type: text/html; charset=ISO-8859-1');

if (isset($_GET["categoria"])){
	$tipo_registro= trim ($_GET["tipo_registro"]);
	$categoria = trim ($_GET["categoria"]);

	$sql = "SELECT  tbl_hd_motivo_ligacao.hd_motivo_ligacao, 
					tbl_hd_motivo_ligacao.descricao
			FROM tbl_hd_motivo_ligacao
			WHERE 1 = 1 ";
	if (strlen($categoria) > 0 and strlen($tipo_registro) > 0){
		$sql .= " AND tbl_hd_motivo_ligacao.categoria ='$categoria'";
		$sql .= " AND tbl_hd_motivo_ligacao.tipo_registro ='$tipo_registro'";
	}else{
		// ERRO
		$sql .= " AND 1=2 ";
	}
	$sql .= " ORDER BY descricao  ";

//	echo "sql: $sql";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		echo "<option value=''></option>";
		for($i=0;$i<pg_numrows($res);$i++){
			$xhd_motivo_ligacao= pg_result($res,$i,hd_motivo_ligacao);
			$xdescricao = pg_result($res,$i,descricao);
			echo "<option value='$xhd_motivo_ligacao'>".$xdescricao."</option>";
		}
	}else{
		echo "<option value=''>Não encontrado.</option>";
	}
	exit;
}


?>