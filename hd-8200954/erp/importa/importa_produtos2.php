<?
//CONEXAO COM POSTGRES
include ("bdtc.php");

//CONEXAO COM A TECNOPLUS
$con_tec = ibase_connect("201.44.49.130:/home/tecno/GBANCO.GDB","SYSDBA","masterkey");
if (!$con_tec) {
	echo "Acesso Negado!<br>";
	exit;
}



$inicio = $_GET["inicio"]+100;
$fim	= $inicio+100;

if($inicio>5800){
echo "terminou aqui...";
exit();
}
	  echo "<script language='JavaScript'>
			function mudar() {
				  window.location=\"importa_produtos2.php?inicio=$inicio\";

			}
			
			setTimeout('mudar()',1000);
			
			</script>";


$str= "утlср";
echo "str1:$str";
$str= utf8_encode($str);
echo "str2:$str";
$str= utf8_decode($str);
echo "str3:$str";



$sql = "SELECT FIRST 100 SKIP $inicio CODPRODUTO,
			QUANTIDADE,
			CONSUMO_40,
			CONSUMO_20,
			CONSUMO_7,
			DISPONIVEL
		FROM ESTOQUE
		WHERE CODEMPRESA=2
		ORDER BY CODPRODUTO";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
//$inicio	= $inicio+20;
//$fim	= $fim+20;

$count = 0;
while ($row[$count] = ibase_fetch_assoc($result)){
    $count++;
}


// ------------------- BANCO LOCAL -------------------//

$conexao_local = new bdtc();

for($i=0; $i< $count; $i++){
	$codproduto= $row[$i][CODPRODUTO];
	
	$sql= "SELECT PRODUTO FROM TBL_ESTOQUE WHERE PRODUTO= $codproduto";

	$res= $conexao_local->consultaArray($sql);

	$erro="";

	if(strlen($row[$i][CODPRODUTO])>0)
		$codproduto			= $row[$i][CODPRODUTO];
	else 
		$erro="CODPRODUTO: $codproduto<br>";

	if(strlen($row[$i][CONSUMO_40])>0)
		$consumo_40		= $row[$i][CONSUMO_40];
	else
		$consumo_40		= "0"		;
	
	if(strlen($row[$i][CONSUMO_20])>0)
		$consumo_20		= $row[$i][CONSUMO_20];
	else
		$consumo_20		= "0"		;

	if(strlen($row[$i][CONSUMO_7])>0)
		$consumo_7		= $row[$i][CONSUMO_7];
	else
		$consumo_7		= "0"		;

	if(strlen($row[$i][DISPONIVEL])>0)
		$disponivel		= $row[$i][DISPONIVEL];
	else
		$disponivel		= "0"		;

	if(strlen($erro)==0){
		$res= $conexao_local->consultaArray("BEGIN;");

		if(@pg_numrows($res)>0){
			//nao insere
			echo "- jс foi inserido: faz update$controle<br>";
			$sql= "UPDATE TBL_ESTOQUE
			SET QUANTIDADE_DISPONIVEL=$disponivel,
				MEDIA1=$consumo_40,
				MEDIA2=$consumo_20,
				MEDIA3=$consumo_7
			WHERE PRODUTO= $codproduto";

		}else{
			//INSERE
			$sql= "INSERT INTO TBL_ESTOQUE (PRODUTO, QUANTIDADE_DISPONIVEL, MEDIA1, MEDIA2, MEDIA3)
			VALUES($codproduto, $disponivel, $consumo_40, $consumo_20, $consumo_7);";
		}
		$res= $conexao_local->consultaArray($sql);
		if( @pg_errormessage($res)){
			echo "<font color='#ff0000'>sql: $sql</font>"; 
			$res= $conexao_local->consultaArray("ROLLBACK;");
		}else{
			$res= $conexao_local->consultaArray("COMMIT;");
			$inseriu= "OK $controle<br>";
			//echo "<font color='#0000ff'>insere: OK $controle<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
	}
	echo $inseriu;
}
?>