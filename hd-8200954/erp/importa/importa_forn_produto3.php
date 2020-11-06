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
		  window.location=\"importa_forn_produto3.php?inicio=$inicio&teste=asçdlfjasçldkfj\";
	}
	setTimeout('mudar()',800000);
</script>";

$sql = "select FIRST 100 skip $inicio 
			CONTROLE,
			CODFORNECEDOR,
			CODTABPRODUTO3
		from tabfornec3;";

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
		$fornecedor3			= $row[$i][CONTROLE];//pk da tabela
	else 
		$erro="erro em:CONTROLE<br>";

	if(strlen($row[$i][CODFORNECEDOR])>0)
		$fornecedor			= $row[$i][CODFORNECEDOR];
	else 
		$erro="erro em:COFFORNECEDOR<br>";

	if(strlen($row[$i][CODTABPRODUTO3])>0)
		$produto3			= $row[$i][CODTABPRODUTO3];
	else 
		$erro="erro em:codtabproduto3<br>";

	if(strlen($erro)==0){

		$sql= "SELECT fornecedor3 
			   FROM tbl_fornecedor3 
			   WHERE fornecedor3= $fornecedor3";

		$res= $conexao_local->consultaArray($sql);
		
		if(@pg_numrows ($res)>0){
			echo "<br>UPDATEExiste..:$produto3";
			$res= $conexao_local->consultaArray("BEGIN;");

			echo "- update... $fornecedor3";
			$sql= "UPDATE TBL_fornecedor3
					SET FORNECEDOR= $fornecedor,
						produto3= $produto3
					WHERE fornecedor3= $fornecedor3";

		}else{
			echo "<br><font color='#ff00ff'>INSERE FORN_PROD3.:$produto3</font>";
			$res= $conexao_local->consultaArray("BEGIN;");
			//INSERE
			$sql= "INSERT INTO TBL_fornecedor3 (fornecedor3, FORNECEDOR, produto3)
										VALUES($fornecedor3, $fornecedor, $produto3);";
		}

		$res= $conexao_local->consultaArray($sql);
		if( @pg_errormessage($res)){
			$erro= "<font color='#ff0000'>sql: $sql</font>"; 
			echo $erro;
			error('002',$erro); 
			$res= $conexao_local->consultaArray("ROLLBACK;");
		}else{
			$res= $conexao_local->consultaArray("COMMIT;");
			$inseriu= "<font color='#0000ff'>OK $produto3</font>";
			//echo "<font color='#0000ff'>insere: OK $codproduto<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
		error('003',$erro); 
	}

	echo $inseriu;
}

$sql = " SELECT COUNT(CONTROLE) as fim
		 FROM tabfornec3;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "inicio: $inicio -  fim: $fim";
if($inicio > $fim){
	log_importacao("004", " importa_forn_produto3 - fim" );
	log_importacao("004", " importa_forn_produto4 - inicio" );
	echo "<script language='JavaScript'>
		function mudarpag() {
			  window.location=\"importa_forn_produto4.php?inicio=0\";
		}
		setTimeout('mudarpag()',100);
	</script>";

}else{

	$inicio = $inicio+100;

	echo "<script language='JavaScript'>
		function mudar2() {
			  window.location=\"importa_forn_produto3.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',500);
	</script>";
}
?>