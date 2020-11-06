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
		  window.location=\"importa_forn_produto4.php?inicio=$inicio&teste=asçdlfjasçldkfj\";
	}
	setTimeout('mudar()',800000);
</script>";

$sql = "select FIRST 100 skip $inicio 
			CONTROLE,
			CODFORNECEDOR,
			CODTABPRODUTO4
		from tabfornec4;";

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
		$fornecedor4			= $row[$i][CONTROLE];//pk da tabela
	else 
		$erro="erro em:CONTROLE<br>";

	if(strlen($row[$i][CODFORNECEDOR])>0)
		$fornecedor			= $row[$i][CODFORNECEDOR];
	else 
		$erro="erro em:COFFORNECEDOR<br>";

	if(strlen($row[$i][CODTABPRODUTO4])>0)
		$produto4			= $row[$i][CODTABPRODUTO4];
	else 
		$erro="erro em:codtabproduto4<br>";

	if(strlen($erro)==0){

		$sql= "SELECT fornecedor4 
			   FROM tbl_fornecedor4 
			   WHERE fornecedor4= $fornecedor4";

		$res= $conexao_local->consultaArray($sql);
		
		if(@pg_numrows ($res)>0){
			echo "<br>UPDATEExiste..:$produto4";
			$res= $conexao_local->consultaArray("BEGIN;");

			echo "- update... $fornecedor4";
			$sql= "UPDATE TBL_fornecedor4
					SET FORNECEDOR= $fornecedor,
						produto4= $produto4
					WHERE fornecedor4= $fornecedor4";

		}else{
			echo "<br><font color='#ff00ff'>INSERE FORN_PROD3.:$produto4</font>";
			$res= $conexao_local->consultaArray("BEGIN;");
			//INSERE
			$sql= "INSERT INTO TBL_fornecedor4 (fornecedor4, FORNECEDOR, PRODUTO4)
										VALUES($fornecedor4, $fornecedor, $produto4);";
		}

		$res= $conexao_local->consultaArray($sql);
		if( @pg_errormessage($res)){
			$erro= "<font color='#ff0000'>sql: $sql</font>"; 
			echo $erro;
			error('002',$erro); 
			$res= $conexao_local->consultaArray("ROLLBACK;");
		}else{
			$res= $conexao_local->consultaArray("COMMIT;");
			$inseriu= "<font color='#0000ff'>OK $produto4</font>";
			//echo "<font color='#0000ff'>insere: OK $codproduto<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
		error('003',$erro); 
	}

	echo $inseriu;
}

$sql = " SELECT COUNT(CONTROLE) as fim
		 FROM tabfornec4;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "inicio: $inicio -  fim: $fim";
if($inicio > $fim){
	$sql= "SELECT max(cotacao) AS cotacao FROM TBL_COTACAO;";
	$res= $conexao_local->consultaArray($sql);
	if(@pg_numrows($res)>0){
		$cotacao =trim(pg_result($res, 0, cotacao));	

		$sql= "select fn_criar_cotacao2($cotacao)";
		$res= $conexao_local->consultaArray($sql);
		if(@pg_numrows($res)>0){
			echo "gerou cotacao com sucesso!";
			log_importacao("006", " gerou cotacao com sucesso" );
		}else{
			echo "Problemas ao gerar a Cotação!";
			log_importacao("005", " Problemas ao gerar a Cotação!" );
		}	
	}else{
		echo "Problemas ao gerar a Cotação!";
		log_importacao("005", " Problemas ao gerar a Cotação! SQL: $sql" );
	}

	echo "sql: $sql";
	log_importacao("005", " importa_fornecedor - fim AQUI ACABA TODA IMPORTAÇÃO" );
	echo "<script language='JavaScript'>
		function mudarpag() {
			  alert('concluido a importação de fornecedores!');
			  this.close();
		}
		setTimeout('mudarpag()',100);
	</script>";

}else{

	$inicio = $inicio+100;

	echo "<script language='JavaScript'>
		function mudar2() {
			  window.location=\"importa_forn_produto4.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',500);
	</script>";
}
?>