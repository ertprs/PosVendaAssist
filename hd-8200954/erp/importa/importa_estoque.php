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
		window.location=\"importa_estoque.php?inicio=$inicio\";
	}
	setTimeout('mudar()',800000);
</script>";

$sql = "
	select FIRST 300 skip $inicio  estoque.codproduto,
       estoque.quantidade as quantidade_estoque,
       estoque.disponivel,
        (select sum(cotacoes.prev_entrega) from itenscotacao
           left outer join cotacoes on (cotacoes.controle = itenscotacao.codcotacao )
             where itenscotacao.codproduto = estoque.codproduto and (cotacoes.data_entrega = 0 or cotacoes.data_entrega is null) and ((consumo_7>0.04) or (consumo_20>0.04) or (consumo_40>0.01)) ) as quantidade_entregar,
        estoque.consumo_7,
        estoque.consumo_20,
        estoque.consumo_40
	from estoque
	where estoque.codempresa = 2;";

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

	if(strlen($row[$i][CODPRODUTO])>0)
		$codproduto			= $row[$i][CODPRODUTO];
	else 
		$erro="CODPRODUTO: $codproduto<br>";

	if(strlen($row[$i][QUANTIDADE_ENTREGAR])>0)
		$quantidade_entregar		= $row[$i][QUANTIDADE_ENTREGAR];
	else
		$quantidade_entregar		= "0"		;

	if(strlen($row[$i][QUANTIDADE_ESTOQUE])>0)
		$quantidade_estoque		= $row[$i][QUANTIDADE_ESTOQUE];
	else
		$quantidade_estoque		= "0"		;



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
	
		$sql= "SELECT ESTOQUE FROM TBL_ESTOQUE WHERE PRODUTO= $codproduto";
		//echo "<BR>SQL_ESTOQUE:$sql";
		$res= $conexao_local->consultaArray($sql);


		if(@pg_numrows ($res)>0){
			$estoque= trim (pg_result($res,0, estoque));
			echo "ESTOQUE: $estoque";
			echo "<br>Existe..:$codproduto";
			//nao insere
			echo "- update... $codproduto";
			$res= $conexao_local->consultaArray("BEGIN;");	

			$sql= "UPDATE TBL_ESTOQUE
			SET QUANTIDADE_DISPONIVEL= $disponivel,
				QUANTIDADE_ESTOQUE   = $quantidade_estoque,
				QUANTIDADE_ENTREGAR  = $quantidade_entregar,	
				MEDIA1=$consumo_40,
				MEDIA2=$consumo_20,
				MEDIA3=$consumo_7
			WHERE PRODUTO= $codproduto";



		}else{
			$res= $conexao_local->consultaArray("BEGIN;");
			echo "<br><font color='#ff00ff'>NAO TEM ESSE PROD. NO ESTOQUE:$produto</font>";
			//INSERE
			ECHO "VAI INSERIR: $codproduto";
			$sql= "INSERT INTO TBL_ESTOQUE (PRODUTO, QUANTIDADE_DISPONIVEL, QUANTIDADE_ESTOQUE, QUANTIDADE_ENTREGAR,	MEDIA1, MEDIA2, MEDIA3)
			VALUES($codproduto, $disponivel, $quantidade_estoque, $quantidade_entregar, $consumo_40, $consumo_20, $consumo_7);";
		}
		$res= $conexao_local->consultaArray($sql);

		if( @pg_errormessage($res)){
			$erro= "<font color='#ff0000'>sql: $sql</font>"; 
			log_importacao("005", "Erro: importa_estoque > rollback SQL:>$sql!" );
			$res= $conexao_local->consultaArray("ROLLBACK;");
		}else{
			$res= $conexao_local->consultaArray("COMMIT;");
			$inseriu= "<font color='#0000ff'>OK $controle</font>";
			//echo "<font color='#0000ff'>insere: OK $codproduto<br></font>"; 
		}
	}else{
		echo "ERRO:". $erro;		
		error('001',$erro); 
		log_importacao("003", "Erro: importa_estoque $erro" );
	}
	echo $inseriu;
		if($codproduto==7581){
			echo "SQL DO PROD 7581>>>$sql";
			//exit;
		}
}



$sql = " SELECT COUNT(CONTROLE) as fim
		 from estoque
		 where estoque.codempresa = 2;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	log_importacao("003", "Erro: importa_estoque > Error executando a query count!" );
	exit;
}

$row[0] = ibase_fetch_assoc($result);
$fim	= $row[0][FIM];

echo "inicio: $inicio -  fim: $fim";
if($inicio > $fim){
	log_importacao("003", " importa_estoque - fim" );
	log_importacao("004", " importa_fornecedor - inicio" );
	echo "<script language='JavaScript'>
		function mudarpag() {
			window.location=\"importa_fornecedor.php?inicio=0\";
		}
		setTimeout('mudarpag()',1);
	</script>";
}else{

	$inicio = $inicio+300;

	echo "<script language='JavaScript'>
		function mudar2() {
			window.location=\"importa_estoque.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',500);
	</script>";
}

?>