<?
ini_set("max_execution_time", 900); 
//CONEXAO COM POSTGRES
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include ("bdtc.php");
include ("erro.php");

//CONEXAO COM A TECNOPLUS
$con_tec = ibase_connect("201.44.49.130:/home/tecno/GBANCO.GDB","SYSDBA","masterkey");
if (!$con_tec) {
	echo "Acesso Negado!<br>";
	log_importacao("002", "Erro: importa_produto: Acesso Negado" );
	exit;
}
$inicio = $_GET["inicio"];

echo "<script language='JavaScript'>
	function mudar() {
		window.location=\"importa_produto.php?inicio=$inicio\";
	}
	setTimeout('mudar()',800000);
</script>";

$sql = "SELECT FIRST 300 skip $inicio controle,
			codtabproduto1,
			codtabproduto2,
			codtabproduto3,
			codtabproduto4,
			codtabproduto5,
			nomeproduto,
			nomereduzido,
			unidmedida,
			inativo,
			valorcusto, 
			valorcustomedio, 
			observacao,
			codtabicms,
			valorcompra,
			limite_vendas, 
			tempo_garantia, 
			valor1, 
			valor2, 
			valor3, 
			valor4, 
			valor5, 
			valor6, 
			cod_id_fornecedor,
			caracteristica_tecnica, 
			estoque_minimo,
			data_ultima_conferencia, 
			resultado_conferencia, 
			percipi
		FROM produtos";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	log_importacao("002", "Erro: importa_produto: Error executando a query count" );
	exit;
}

//echo "<a href='importa_produtos.php?inicio=$inicio&fim=$fim'>inserir + 20</a>";
//echo "passou depois select";
$count = 0;
while ($row[$count] = ibase_fetch_assoc($result)){
    $count++;
}
//ibase_close($con_tec);


// ------------------- BANCO LOCAL -------------------//

$conexao_local = new bdtc();

for($i=0; $i< $count; $i++){
	//INSERE
	$erro="";

	if(strlen($row[$i][CONTROLE])>0)
		$controle			= $row[$i][CONTROLE];
	else 
		$erro="CONTROLE: $controle<br>";

	if(strlen($row[$i][CODTABPRODUTO1])>0)
		$codtabproduto1		= $row[$i][CODTABPRODUTO1];
	else 
		$erro="codtabproduto1: $codtabproduto1<br>";

	if(strlen($row[$i][CODTABPRODUTO2])>0)
		$codtabproduto2		= $row[$i][CODTABPRODUTO2];
	else 
		$erro="codtabproduto2: $codtabproduto2<br>";

	if(strlen($row[$i][CODTABPRODUTO3])>0)
		$codtabproduto3		= $row[$i][CODTABPRODUTO3];
	else 
		$erro="codtabproduto3: $codtabproduto3<br>";

	if(strlen($row[$i][CODTABPRODUTO4])>0)
		$codtabproduto4		= $row[$i][CODTABPRODUTO4];
	else 
		$erro="codtabproduto4: $codtabproduto4<br>";

	if(strlen($row[$i][CODTABPRODUTO5])>0)
		$codtabproduto5		= $row[$i][CODTABPRODUTO5];
	else 
		$erro="codtabproduto5: $codtabproduto5<br>";

	if(strlen($row[$i][NOMEPRODUTO])>0)
		$nomeproduto		= "'". str_replace("'", "´", utf8_encode($row[$i][NOMEPRODUTO]))."'";
	else
		$nomeproduto		= "null";

	if(strlen($row[$i][NOMEREDUZIDO])>0)
		$nomereduzido		= "'".  str_replace("'", "´", utf8_encode($row[$i][NOMEREDUZIDO]))."'";
	else
		$nomereduzido		= "null"		;

	if(strlen($row[$i][UNIDMEDIDA])>0)		
		$unidmedida			= "'". utf8_encode($row[$i][UNIDMEDIDA])."'";
	else	
		$unidmedida			= "null";

	if(strlen($row[$i][INATIVO])>0)		
		$inativo			= $row[$i][INATIVO];
	else
		$inativo			= "0";

	if(strlen($row[$i][VALORCUSTO])>0)		
		$valorcusto			= $row[$i][VALORCUSTO];
	else
		$valorcusto			= "0";

	if(strlen($row[$i][VALORCUSTOMEDIO])>0)		
		$valorcustomedio	= $row[$i][VALORCUSTOMEDIO];
	else
		$valorcustomedio	= "0";

	if(strlen($row[$i][OBSERVACAO])>0)		
		$observacao			= "'". str_replace("'", "´", utf8_encode($row[$i][OBSERVACAO]))."'";
	else
		$observacao			="null";

	if(strlen($row[$i][CODTABICMS])>0)		
		$codtabicms			= $row[$i][CODTABICMS];
	else
		$codtabicms			= "0";

	if(strlen($row[$i][VALORCOMPRA])>0)		
		$valorcompra		= $row[$i][VALORCOMPRA];
	else
		$valorcompra		=  "0";

	if(strlen($row[$i][LIMITE_VENDAS])>0)		
		$limite_vendas		= $row[$i][LIMITE_VENDAS];
	else
		$limite_vendas		= "0";

	if(strlen($row[$i][TEMPO_GARANTIA])>0)		
		$tempo_garantia		= $row[$i][TEMPO_GARANTIA];
	else
		$tempo_garantia		= "0";

	if(strlen($row[$i][VALOR1])>0)		
		$valor1				= $row[$i][VALOR1];
	else
		$valor1				= "0";

	if(strlen($row[$i][VALOR2])>0)		
		$valor2				= $row[$i][VALOR2];
	else
		$valor2				= "0";

	if(strlen($row[$i][VALOR3])>0)		
		$valor3				= $row[$i][VALOR3];
	else
		$valor3				= "0";

	if(strlen($row[$i][VALOR4])>0)		
		$valor4				= $row[$i][VALOR4];
	else
		$valor4				= "0";

	if(strlen($row[$i][VALOR5])>0)		
		$valor5				= $row[$i][VALOR5];
	else
		$valor5				= "0";

	if(strlen($row[$i][VALOR6])>0)		
		$valor6				= $row[$i][VALOR6];
	else
		$valor6				= "0";

	if(strlen($row[$i][COD_ID_FORNECEDOR])>0)		
		$cod_id_fornecedor	= "'". utf8_encode($row[$i][COD_ID_FORNECEDOR])."'";
	else
		$cod_id_fornecedor	= "0";

	if(strlen($row[$i][CARACTERISTICA_TECNICA])>0)		
		$caracteristica_tecnica	= "'".  str_replace("'", "´", utf8_encode($row[$i][CARACTERISTICA_TECNICA]))."'";
	else
		$caracteristica_tecnica = "null";

	if(strlen($row[$i][ESTOQUE_MINIMO])>0)		
		$estoque_minimo		= $row[$i][ESTOQUE_MINIMO];
	else
		$estoque_minimo		= "0";

	if(strlen($row[$i][DATA_ULTIMA_CONFERENCIA])>0)		
		$data_ultima_conferencia= $row[$i][DATA_ULTIMA_CONFERENCIA];
	else
		$data_ultima_conferencia= "0";

	if(strlen($row[$i][RESULTADO_CONFERENCIA])>0)		
		$resultado_conferencia	= "'". utf8_encode($row[$i][RESULTADO_CONFERENCIA])."'";
	else
		$resultado_conferencia	= "0";

	if(strlen($row[$i][PERCIPI])>0)		
		$percipi			= $row[$i][PERCIPI];
	else
		$percipi			= "0";

	if(strlen($erro)==0){

		$sql= "SELECT produto, produto1, produto2, produto3, produto4
			   FROM TBL_PRODUTO 
			   WHERE PRODUTO= $controle";

		$res= $conexao_local->consultaArray($sql);
		if(@pg_numrows($res)>0){
			echo "UPDATE - já foi inserido : $controle...";
			
			$res= $conexao_local->consultaArray("BEGIN;");

			$sql= "UPDATE TBL_PRODUTO
				SET 
					PRODUTO1				=$codtabproduto1, 
					PRODUTO2				=$codtabproduto2, 
					PRODUTO3				=$codtabproduto3,
					PRODUTO4				=$codtabproduto4, 
					PRODUTO5				=$codtabproduto5, 
					NOME					=$nomeproduto,
					NOMEREDUZIDO			=$nomereduzido, 
					UNIDMEDIDA				=$unidmedida, 
					INATIVO					=$inativo,
					VALORCUSTO				=$valorcusto, 
					VALORCUSTOMEDIO			=$valorcustomedio, 
					OBSERVACAO				=$observacao, 
					CODTABICMS				=$codtabicms, 
					VALORCOMPRA				=$valorcompra, 
					LIMITE_VENDAS			=$limite_vendas, 
					TEMPO_GARANTIA			=$tempo_garantia,
					VALOR1					=$valor1, 
					VALOR2					=$valor2,
					VALOR3					=$valor3, 
					VALOR4					=$valor4, 
					VALOR5					=$valor5, 
					VALOR6					=$valor6, 
					COD_ID_FORNECEDOR		=$cod_id_fornecedor, 
					CARACTERISTICA_TECNICA	=$caracteristica_tecnica, 
					ESTOQUE_MINIMO			=$estoque_minimo, 
					DATA_ULTIMA_CONFERENCIA	=$data_ultima_conferencia, 
					RESULTADO_CONFERENCIA	=$resultado_conferencia, 
					PERCIPI					=$percipi
					WHERE PRODUTO=$controle;";

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
			$res= $conexao_local->consultaArray("BEGIN;");

			$sql= "INSERT INTO TBL_PRODUTO
				(PRODUTO, PRODUTO1, PRODUTO2, PRODUTO3, PRODUTO4, PRODUTO5, NOME, NOMEREDUZIDO, UNIDMEDIDA, INATIVO, VALORCUSTO, VALORCUSTOMEDIO, OBSERVACAO, CODTABICMS, VALORCOMPRA, LIMITE_VENDAS, TEMPO_GARANTIA, VALOR1, VALOR2, VALOR3, VALOR4, VALOR5, VALOR6, COD_ID_FORNECEDOR, CARACTERISTICA_TECNICA, ESTOQUE_MINIMO, DATA_ULTIMA_CONFERENCIA, RESULTADO_CONFERENCIA, PERCIPI)
			VALUES
				($controle, $codtabproduto1, $codtabproduto2, $codtabproduto3, $codtabproduto4, $codtabproduto5, $nomeproduto, $nomereduzido, $unidmedida, $inativo, $valorcusto, $valorcustomedio, $observacao, $codtabicms, $valorcompra, $limite_vendas, $tempo_garantia, $valor1, $valor2, $valor3, $valor4, $valor5, $valor6, $cod_id_fornecedor, $caracteristica_tecnica, $estoque_minimo, $data_ultima_conferencia, $resultado_conferencia, $percipi);";

			$res= $conexao_local->consultaArray($sql);
			if( @pg_errormessage($res)){
				echo "<font color='#ff0000'>sql: $sql</font>"; 
				log_importacao("002", "Erro: importa_produto: SQL:$sql" );
				$res= $conexao_local->consultaArray("ROLLBACK;");
			}else{
				$res= $conexao_local->consultaArray("COMMIT;");
				$inseriu= "OK $controle<br>";
				//echo "<font color='#0000ff'>insere: OK $controle<br></font>"; 
			}
		}
	}else{
		echo "ERRO:". $erro;	
		log_importacao("002", "Erro: importa_produto: $erro" );
	}
	//echo "<BR>PRODUTO:".$row[$i][NOMEPRODUTO];
	echo $inseriu;
}

$sql = " SELECT count(controle) as fim
		 FROM produtos;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	log_importacao("002", "Erro: importa_produto: Error executando a query count" );
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "<br>inicio: $inicio -  fim: $fim";
if($inicio > $fim){
	log_importacao("002", " importa_produto - fim" );
	log_importacao("003", " importa_estoque - inicio" );
echo "passou aquiassssssssssssssssssssssssss";
	echo "<script language='JavaScript'>
	function mudarpag() {
			
			temp='importa_estoque.php?inicio=0';

			window.location=temp
	}
	setTimeout('mudarpag()',10);
	</script>";
}else{

	$inicio = $inicio+300;
	echo "<script language='JavaScript'>
		function mudar2() {
			window.location=\"importa_produto.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',100);
	</script>";
}
?>