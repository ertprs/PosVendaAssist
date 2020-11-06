<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

include 'funcoes.php';
$referencia_produto = $_GET['referencia_produto'];
$numero_serie       = strtoupper($_GET['numero_serie']);
//echo "número série $numero_serie<BR>";
//echo "$referencia_produto - $numero_serie";
$sql = "SELECT numero_serie_obrigatorio
		from tbl_produto
		where referencia = '$referencia_produto'
		";
//		and tbl_produto.ativo is true
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$serie_obrigatorio = pg_result($res,0,0);
	if($serie_obrigatorio=="t"){
		if(strlen($numero_serie)>10 or strlen($numero_serie)<8){
			echo "Número inválido. Tamanho inválido";
			exit;
		}
		$sql = "SELECT TO_CHAR(CURRENT_DATE,'y')::numeric";
		$res = pg_exec($con,$sql);
		$ano_corrente = pg_result($res,0,0);
	
		$meses = array('A','B','C','D','E','F','G','H','I',
					'J','K','L','M','N','O','P','Q','R','S',
					'T','U','V','W','Y','X','Z');
		
		$sql ="SELECT SUBSTR('ABCDEFGHIJKLMNOPQRSTUVWYXZ',TO_CHAR(CURRENT_DATE,'YYYY')::INTEGER - 1994,1)"; 
	//	echo $sql;
		$res = pg_exec($con,$sql);
		$letra_ano = pg_result($res,0,0);

		$sql ="SELECT SUBSTR('ABCDEFGHIJKL',TO_CHAR(CURRENT_DATE,'MM')::INTEGER ,1)"; 
		//echo $sql;
		$res = pg_exec($con,$sql);
		$letra_mes = pg_result($res,0,0);

//		echo substr($numero_serie, 0, 1);

		$letra_inicial = array('1','4','9');
		if(!in_array(substr($numero_serie, 0, 1),$letra_inicial)){
//			echo substr($numero_serie, 0, 1);
			echo "Erro no primeiro digito. Tem que ser 1 ou 4 ou 9";
			exit;
		}

//		echo "<BR>segunda letra ".substr($numero_serie, 1, 1);
		if(is_numeric(substr($numero_serie, 1, 1))){
		//	echo substr($numero_serie, 1, 1);
			echo "Erro no segundo digito. Tem que ser letra";
			exit;
		}

		//echo "<BR>Terceira letra ".substr($numero_serie, 2, 1);
		if(is_numeric(substr($numero_serie, 2, 1))){
		//	echo substr($numero_serie, 2, 1);
			echo "Erro no terceiro digito. Tem que ser letra";
			exit;
		}

		/* QUARTO CARACTER TEM QUE SER LETRA. ANO */
		/* ANO NÃO PODE SER MAIOR QUE O ATUAL */
		//echo "<BR>Quarta letra ".substr($numero_serie, 3, 1);
		//echo "<BR>ano corrente $letra_ano <BR>";
		if(is_numeric(substr($numero_serie, 3, 1)) or substr($numero_serie, 3, 1) > $letra_ano){
	//		echo substr($numero_serie, 3, 1);
			echo " Erro no Quarta digito. Tem que ser letra";
			exit;
		}
		
		/* QUANDO ANO CORRENTE O MES NÃO PODE SER MAIOR QUE O ATUAL */
		//echo "<BR>Quarta letra 2 - ".substr($numero_serie, 3, 1);
		//echo "<BR>mes corrente $letra_mes <BR> mes da OS ".substr($numero_serie, 2, 1)."<BR>";
		if(substr($numero_serie, 3, 1) == $letra_ano){
			if(substr($numero_serie, 2, 1) > $letra_mes){
			//	echo substr($numero_serie, 3, 1);
				echo " Fabricado neste ano, mas o mes esta superior[".substr($numero_serie, 2, 1)."] que o atual [$letra_mes]";
				exit;
			}
		}
	//	echo "resto : ".substr($numero_serie, 4,strlen(trim($numero_serie))-3);
		if(!is_numeric(substr($numero_serie, 4,strlen(trim($numero_serie))-3) )){
			echo "Erro, radical final tem que ser número. Radical final: ".substr($numero_serie, 4,strlen(trim($numero_serie))-3);
			exit;
		}
	//	echo "<BR><BR><STRONG>PARABENS!!! NÚMERO DE SÉRIE SEM PROBLEMAS!!!</STRONG><br><br>";

	}else{
		echo "Número de série não obrigatório";
		exit;
	}
}else{
	echo "Produto não encontrado";
	exit;
}

?>