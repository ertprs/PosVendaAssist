<?
ini_set("max_execution_time", 900); 
//CONEXAO COM POSTGRES
include ("bdtc.php");
include ("erro.php");
if(strlen($_GET["primeira"])==0)
	log_importacao("001", " importa_produto1 - inicio" );
	

//CONEXAO COM A TECNOPLUS
$con_tec = ibase_connect("201.44.49.130:/home/tecno/GBANCO.GDB","SYSDBA","masterkey");
if (!$con_tec) {
	echo "Acesso Negado!<br>";
	exit;
}
$inicio = $_GET["inicio"];

echo "<script language='JavaScript'>
	function mudar() {
		window.location=\"importa_produto1.php?inicio=$inicio&&primeira=nao\";
	}
	setTimeout('mudar()',800000);
</script>";

$sql = "select FIRST 100 skip $inicio controle, descricao
		from tabproduto1;";
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
	$produto1= $row[$i][CONTROLE];
	
	$sql= "SELECT produto1 
		   FROM tbl_produto1 
		   WHERE produto1= $produto1";

	$res= $conexao_local->consultaArray($sql);

	if(@pg_numrows ($res)>0){
		echo "<br>UPDATEExiste..:$produto1";
		$erro="";
		if(strlen($row[$i][DESCRICAO])>0)
			$descricao = "'". str_replace("'", "´", utf8_encode($row[$i][DESCRICAO]))."'";
		else
			$erro="DESCRICAO: $produto1<br>";

		if(strlen($erro)==0){
			$res= $conexao_local->consultaArray("BEGIN;");

			if(strlen($produto1)>0){
				echo "- update... $produto1";
				$sql= "UPDATE TBL_PRODUTO1
						SET DESCRICAO= $descricao
						WHERE PRODUTO1= $produto1";
			}
		}else{
			echo $erro;
		}
	}else{
		echo "<br><font color='#ff00ff'>NAO TEM ESSE PROD.:$produto1</font>";

		$erro="";

		if(strlen($row[$i][CONTROLE])>0)
			$produto1			= $row[$i][CONTROLE];
		else 
			$erro="PRODUTO1: $produto1<br>";

		if(strlen($row[$i][DESCRICAO])>0)
			$descricao = "'".utf8_encode($row[$i][DESCRICAO])."'";
		else
			$erro="DESCRICAO: $produto1<br>";

		if(strlen($erro)==0){
			$res= $conexao_local->consultaArray("BEGIN;");
			//INSERE
			ECHO "VAI INSERIR: $produto1";
			$sql= "INSERT INTO TBL_PRODUTO1 (PRODUTO1, DESCRICAO)
			VALUES($produto1, $descricao);";
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
			$inseriu= "<font color='#0000ff'>OK $produto1</font>";
			//echo "<font color='#0000ff'>insere: OK $codproduto<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
		error('001',$erro); 
	}
	echo $inseriu;
}

$sql = " SELECT COUNT(CONTROLE) as fim
		 FROM tabproduto1;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "inicio: $inicio -  fim: $fim";
if($inicio > $fim){
log_importacao("001", " importa_produto1 - fim" );
log_importacao("001", " importa_produto2 - inicio" );
echo "<script language='JavaScript'>
function mudarpag() {
		  window.location=\"importa_produto2.php?inicio=0\";
	}
	setTimeout('mudarpag()',100);
</script>";

}else{

	$inicio = $inicio+100;

	echo "<script language='JavaScript'>
		function mudar2() {
			
			  window.location=\"importa_produto1.php?inicio=$inicio&primeira=nao\";
		}
		setTimeout('mudar2()',500);
	</script>";
}
?>