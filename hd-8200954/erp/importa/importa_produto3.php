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
		  window.location=\"importa_produto3.php?inicio=$inicio&teste=asçdlfjasçldkfj\";
	}
	setTimeout('mudar()',800000);
</script>";



$sql = "select FIRST 100 skip $inicio controle, descricao
		from tabproduto3;";
//echo "<font color='#ff0000'>".$sql."</font>";

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
	$produto3= $row[$i][CONTROLE];
	
	$sql= "SELECT produto3 
		   FROM tbl_produto3 
		   WHERE produto3= $produto3";
//echo "sql: $sql";
	$res= $conexao_local->consultaArray($sql);


	if(@pg_numrows ($res)>0){
		echo "<br>UPDATEExiste..:$produto3";
		$produto3 = trim(pg_result($res, 0, produto3));	
		echo "produto: ...$produto3";
		$erro="";
		if(strlen($row[$i][DESCRICAO])>0)
			$descricao = "'". str_replace("'", "´", utf8_encode($row[$i][DESCRICAO]))."'";
		else
			$erro="DESCRICAO: $produto3<br>";

		if(strlen($erro)==0){
			$res= $conexao_local->consultaArray("BEGIN;");

			if(strlen($produto3)>0){
				echo "- update... $produto3";
				$sql= "UPDATE TBL_produto3
						SET DESCRICAO= $descricao
						WHERE produto3= $produto3";
			}
		}else{
			echo $erro;
		}
	}else{
		echo "<br><font color='#ff00ff'>NAO TEM ESSE PROD.:$produto3</font>";

		$erro="";

		if(strlen($row[$i][CONTROLE])>0)
			$produto3			= $row[$i][CONTROLE];
		else 
			$erro="produto3: $produto3<br>";

		if(strlen($row[$i][DESCRICAO])>0)
			$descricao = "'".utf8_encode($row[$i][DESCRICAO])."'";
		else
			$erro="DESCRICAO: $produto3<br>";

		if(strlen($erro)==0){
			$res= $conexao_local->consultaArray("BEGIN;");
			//INSERE
			ECHO "VAI INSERIR: $produto3";
			$sql= "INSERT INTO TBL_produto3 (produto3, DESCRICAO)
			VALUES($produto3, $descricao);";
		}else{
			echo $erro;		
		}
	}

	if(strlen($erro)==0){
		$res= $conexao_local->consultaArray($sql);
		if( @pg_errormessage($res)){
			$erro= "<font color='#ff0000'>sql: $sql</font>"; 
			echo $erro;
			error('001',$erro); 
			$res= $conexao_local->consultaArray("ROLLBACK;");
		}else{
			$res= $conexao_local->consultaArray("COMMIT;");
			$inseriu= "<font color='#0000ff'>OK $produto3</font>";
			//echo "<font color='#0000ff'>insere: OK $codproduto<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
		error('001',$erro); 
	}
	echo $inseriu;
}

$sql = " SELECT COUNT(CONTROLE) as fim
		 FROM tabproduto3;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "<br>inicio: $inicio -  fim: $fim";
if($inicio > $fim){
	log_importacao("001", " importa_produto3 - fim" );
	log_importacao("001", " importa_produto4 - inicio" );
	echo "<script language='JavaScript'>
	function mudarpag() {
			  window.location=\"importa_produto4.php?inicio=0\";
		}
		setTimeout('mudarpag()',100);
	</script>";

}else{

	$inicio = $inicio+100;

	echo "<script language='JavaScript'>
		function mudar2() {
			  window.location=\"importa_produto3.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',500);
	</script>";
}
?>