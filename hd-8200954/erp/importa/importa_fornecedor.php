<?
ini_set("max_execution_time", 900); 
//CONEXAO COM POSTGRES
include ("bdtc.php");
include ("erro.php");

//CONEXAO COM A TECNOPLUS
$con_tec = ibase_connect("201.44.49.130:/home/tecno/GBANCO.GDB","SYSDBA","masterkey");
if (!$con_tec) {
	echo "Acesso Negado!<br>";
	log_importacao("005", "Erro: importa_fornecedor > Acesso Negado!" );
	exit;
}
$inicio = $_GET["inicio"];

echo "<script language='JavaScript'>
	function mudar() {
		window.location=\"importa_fornecedor.php?inicio=$inicio&teste=erro\";
	}
	setTimeout('mudar()',800000);
</script>";

$sql = "select FIRST 100 skip $inicio 
			CONTROLE,
			NOME, 
			ENDERECO, 
			BAIRRO , 
			COMPLEMENTO , 
			CEP , 
			CNPJ, 
			INSCESTADUAL, 
			FONE , 
			FAX , 
			EMAIL , 
			CIDADE , 
			ESTADO , 
			NUMERO , 
			FANTASIA , 
			INATIVO , 
			EMAILRMA
		from fornecedor;";

//echo "<font color='#ff0000'>sql:".$sql."</font>";
$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	log_importacao("005", "Erro: importa_fornecedor > Error executando a query count!" );
	exit;
}

$count = 0;
while ($row[$count] = ibase_fetch_assoc($result)){
    $count++;
}
// ------------------- BANCO LOCAL -------------------//

$conexao_local = new bdtc();

for($i=0; $i< $count; $i++){
	//INSERE
	$erro="";

	if(strlen($row[$i][CONTROLE])>0)
		$fornecedor			= $row[$i][CONTROLE];
	else 
		$erro="CONTROLE: $controle<br>";

	if(strlen($row[$i][NOME])>0)
		$nome	= "'". str_replace("'", "´", utf8_encode($row[$i][NOME]))."'";
	else
		$nome	= "null";

	if(strlen($row[$i][ENDERECO])>0)
		$endereco		= "'". str_replace("'", "´", utf8_encode($row[$i][ENDERECO]))."'";
	else
		$endereco		= "null";

	if(strlen($row[$i][BAIRRO])>0)
		$bairro		= "'". str_replace("'", "´", utf8_encode($row[$i][BAIRRO]))."'";
	else
		$bairro		= "null";

	if(strlen($row[$i][COMPLEMENTO])>0)
		$complemento		= "'". str_replace("'", "´", utf8_encode($row[$i][COMPLEMENTO]))."'";
	else
		$complemento		= "null";

	if(strlen($row[$i][CEP])>0)
		$cep		= "'". str_replace("'", "´", utf8_encode($row[$i][CEP]))."'";
	else
		$cep		= "null";

	if(strlen($row[$i][CNPJ])>0)
		$cnpj		= "'". str_replace("'", "´", utf8_encode($row[$i][CNPJ]))."'";
	else
		$cnpj		= "null";

	if(strlen($row[$i][INSCESTADUAL])>0)
		$inscestadual		= "'". str_replace("'", "´", utf8_encode($row[$i][INSCESTADUAL]))."'";
	else
		$inscestadual		= "null";

	if(strlen($row[$i][FONE])>0)
		$fone		= "'". str_replace("'", "´", utf8_encode($row[$i][FONE]))."'";
	else
		$fone		= "null";

	if(strlen($row[$i][FAX])>0)
		$fax		= "'". str_replace("'", "´", utf8_encode($row[$i][FAX]))."'";
	else
		$fax		= "null";

	if(strlen($row[$i][EMAIL])>0)
		$email		= "'". str_replace("'", "´", utf8_encode($row[$i][EMAIL]))."'";
	else
		$email		= "null";

	if(strlen($row[$i][CIDADE])>0)
		$cidade		= "'". str_replace("'", "´", utf8_encode($row[$i][CIDADE]))."'";
	else
		$cidade		= "null";

	if(strlen($row[$i][ESTADO])>0)
		$estado= "'". str_replace("'", "´", utf8_encode($row[$i][ESTADO]))."'";
	else
		$estado	= "null";

	if(strlen($row[$i][NUMERO])>0)
		$numero= "'". str_replace("'", "´", utf8_encode($row[$i][NUMERO]))."'";
	else
		$numero= "null";

	if(strlen($row[$i][FANTASIA])>0)
		$fantasia= "'". str_replace("'", "´", utf8_encode($row[$i][FANTASIA]))."'";
	else
		$fantasia= "null";

	if(strlen($row[$i][INATIVO])>0)
		$inativo= str_replace("'", "´", utf8_encode($row[$i][INATIVO]));
	else
		$inativo= "null";


	if(strlen($row[$i][EMAILRMA])>0)
		$emailrma= "'". str_replace("'", "´", utf8_encode($row[$i][EMAILRMA]))."'";
	else
		$emailrma= "null";

	if(strlen($erro)==0){

		$sql= "SELECT fornecedor
			   FROM TBL_FORNECEDOR 
			   WHERE FORNECEDOR= $fornecedor";
		//echo "sql: $sql";
		$res= $conexao_local->consultaArray($sql);
		if(@pg_numrows($res)>0){
			echo "UPDATE - já foi inserido : $fornecedor...<br>";
			
			$res= $conexao_local->consultaArray("BEGIN;");

			$sql= "UPDATE TBL_FORNECEDOR
				SET 
					NOME		= $nome, 
					ENDERECO	= $endereco, 
					BAIRRO		= $bairro, 
					COMPLEMENTO = $complemento, 
					CEP			= $cep, 
					CNPJ		= $cnpj, 
					INSCESTADUAL= $inscestadual, 
					FONE		= $fone, 
					FAX			= $fax, 
					EMAIL		= $email, 
					CIDADE		= $cidade, 
					ESTADO		= $estado, 
					NUMERO		= $numero, 
					FANTASIA	= $fantasia, 
					INATIVO		= $inativo, 
					EMAILRMA	= $emailrma
					WHERE FORNECEDOR=$fornecedor;";

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

			$sql= "INSERT INTO TBL_FORNECEDOR
						(FORNECEDOR, NOME, ENDERECO, BAIRRO, COMPLEMENTO, CEP, CNPJ, INSCESTADUAL, FONE, FAX, EMAIL, CIDADE, ESTADO, NUMERO, FANTASIA, INATIVO, EMAILRMA)
					VALUES($fornecedor, $nome, $endereco, $bairro, $complemento, $cep, $cnpj, $inscestadual, $fone, $fax, $email, $cidade, $estado, $numero, $fantasia, $inativo, $emailrma);";

			$res= $conexao_local->consultaArray($sql);
			if( @pg_errormessage($res)){
				echo "<font color='#ff0000'>sql: $sql</font>"; 
				log_importacao("005", "Erro: importa_fornecedor > rollback SQL:>$sql!" );
				$res= $conexao_local->consultaArray("ROLLBACK;");

			}else{
				$res= $conexao_local->consultaArray("COMMIT;");
				$inseriu= "OK $controle<br>";
				//echo "<font color='#0000ff'>insere: OK $controle<br></font>"; 
			}
		}
	}else{
		echo "ERRO:". $erro;	
		log_importacao("005", "Erro: importa_fornecedor> $erro" );
	}
	//echo "<BR>FORNECEDOR:".$row[$i][NOMEPRODUTO];
	echo $inseriu;
}

$sql = " SELECT count(controle) as fim
		 FROM fornecedor;";

$result = ibase_query($con_tec, $sql);
if (!$result){
	echo "<br>Error executando a query count!";
	exit;
}
$row[0] = ibase_fetch_assoc($result);
$fim= $row[0][FIM];

echo "<br>inicio: $inicio -  fim: $fim";
if($inicio > $fim){	
	log_importacao("004", " importa_fornecedor - fim" );
	log_importacao("004", " importa_forn_produto1 - inicio" );

	echo "<script language='JavaScript'>
		function mudarpag() {
			  window.location=\"importa_forn_produto1.php?inicio=0\";
		}
		setTimeout('mudarpag()',100);
	</script>";
}else{

	$inicio = $inicio+100;
	echo "<script language='JavaScript'>
		function mudar2() {
			window.location=\"importa_fornecedor.php?inicio=$inicio\";
		}
		setTimeout('mudar2()',1000);
	</script>";

}

?>