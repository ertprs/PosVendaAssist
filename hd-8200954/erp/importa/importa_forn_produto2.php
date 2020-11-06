<?
ini_set("max_execution_time", 900); 
//CONEXAO COM POSTGRES
include ("bdtc.php");
include ("erro.php");

//CONEXAO COM A TECNOPLUS
$con_tec = ibase_connect("201.44.49.130:/home/tecno/GBANCO.GDB","SYSDBA","masterkey");
if (!$con_tec) {
	echo "Acesso Negado!<br>";
	exit;
}
$inicio = $_GET["inicio"];

echo "<script language='JavaScript'>
	function mudar() {
		  window.location=\"importa_forn_produto2.php?inicio=$inicio&teste=asçdlfjasçldkfj\";
	}
	setTimeout('mudar()',800000);
</script>";



$sql = "select FIRST 100 skip $inicio 
			CONTROLE,
			CODFORNECEDOR,
			CODTABPRODUTO2
		from tabfornec2;";
echo "<font color='#ff0000'>".$sql."</font>";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}

$count = 0;
while ($row[$count] = ibase_fetch_assoc($result)){
    $count++;
}

// ------------------- BANCO LOCAL -------------------//

$conexao_local = new bdtc();

for($i=0; $i< $count; $i++){

	$erro="";

	if(strlen($row[$i][CONTROLE])>0)
		$fornecedor2			= $row[$i][CONTROLE];//pk da tabela
	else 
		$erro="CONTROLE<br>";

	if(strlen($row[$i][CODFORNECEDOR])>0)
		$fornecedor			= $row[$i][CODFORNECEDOR];
	else 
		$erro="COFFORNECEDOR<br>";

	if(strlen($row[$i][CODTABPRODUTO2])>0)
		$produto2			= $row[$i][CODTABPRODUTO2];
	else 
		$erro="codtabproduto2<br>";
	

	if(strlen($erro)==0){

		$sql= "SELECT fornecedor2 
			   FROM tbl_fornecedor2 
			   WHERE fornecedor2= $fornecedor2";

		$res= $conexao_local->consultaArray($sql);
		
		if(@pg_numrows ($res)>0){
			echo "<br>UPDATEExiste..:$produto2";
			$res= $conexao_local->consultaArray("BEGIN;");

			echo "- update... $fornecedor2";
			$sql= "UPDATE TBL_fornecedor2
					SET FORNECEDOR= $fornecedor,
						PRODUTO2= $produto2
					WHERE fornecedor2= $fornecedor2";

		}else{
			echo "<br><font color='#ff00ff'>INSERE FORN_PROD2.:$produto2</font>";
			$res= $conexao_local->consultaArray("BEGIN;");
			//INSERE
			$sql= "INSERT INTO TBL_fornecedor2 (fornecedor2, FORNECEDOR, PRODUTO2)
										VALUES($fornecedor2, $fornecedor, $produto2);";
		}

		$res= $conexao_local->consultaArray($sql);
		if( @pg_errormessage($res)){
			$erro= "<font color='#ff0000'>sql: $sql</font>"; 
			echo $erro;
			error('002',$erro); 
			$res= $conexao_local->consultaArray("ROLLBACK;");
		}else{
			$res= $conexao_local->consultaArray("COMMIT;");
			$inseriu= "<font color='#0000ff'>OK $produto2</font>";
			//echo "<font color='#0000ff'>insere: OK $codproduto<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
		error('002',$erro); 
	}

	echo $inseriu;
}

$sql = " SELECT COUNT(CONTROLE) as fim
		 FROM tabfornec2;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "inicio: $inicio -  fim: $fim";
if($inicio > $fim){
	log_importacao("004", " importa_forn_produto2 - fim" );
	log_importacao("004", " importa_forn_produto3 - inicio" );
	echo "<script language='JavaScript'>
		function mudarpag() {
			  window.location=\"importa_forn_produto3.php?inicio=0\";
		}
		setTimeout('mudarpag()',100);
	</script>";

}else{
	$inicio = $inicio+100;

	echo "<script language='JavaScript'>
		function mudar2() {
			  window.location=\"importa_forn_produto2.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',500);
	</script>";
}
?>