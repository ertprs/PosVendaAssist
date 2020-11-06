<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$calcula_frete = trim ($_GET['calcula_frete']);
$peso          = 0;

$msg_erro = "";

if ($calcula_frete == 'true'){

	$relacao_pecas   = trim ($_GET['relacao_pecas']);
	$cliente_cnpj    = trim ($_GET['cliente_cnpj']);
	$cliente_cnpj    = str_replace (".","",$cliente_cnpj);
	$cliente_cnpj    = str_replace ("-","",$cliente_cnpj);
	$cliente_cnpj    = str_replace ("/","",$cliente_cnpj);
	$cliente_cnpj    = str_replace (",","",$cliente_cnpj);
	$cliente_cnpj    = str_replace (" ","",$cliente_cnpj);

	$lista_peca = explode("@",$relacao_pecas);

	if (strlen($cliente_cnpj)>0){
		$sql = "SELECT tbl_posto.cep
				FROM tbl_posto
				WHERE cnpj = '".$cliente_cnpj."'";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (pg_numrows ($res) > 0) {
			$cep_destino = pg_result($res,0,cep);
		}else{
			$msg_erro .= "Cliente não encontrado";
		}
	}else{
		$msg_erro .= "Informe o Cliente.";
	}

	if (strlen($cep_destino)==0){
		$msg_erro .= "Informe o CEP destino ($cliente_cnpj) ->". trim($_GET['cliente_cnpj']);
	}

	if (count($lista_peca)==0){
		$msg_erro .= "Informe as peças para o cálculo.";
	}

	$peso_total = 0;
	$qtde_total = 0;

	for ($i=0; $i<count($lista_peca); $i++){
		$e = explode("|", $lista_peca[$i]);
		$peca = $e[0];
		$qtde = $e[1];

		if (strlen($qtde)==0){
			$qtde = 1;
		}

		$sql = "SELECT peso AS peso
				FROM tbl_peca
				WHERE referencia = '$peca'
				AND   fabrica    = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			$peso = trim(pg_result ($res,0,peso));
			if(strlen($peso)==0 OR $peso == 0){
				$peso = 0.3; // PADRAO 300g por peça
			}
			$qtde_total += $qtde;
			$peso_total += $peso * $qtde;
		}
	}

	if (strlen($msg_erro)==0 ) {
		if (strlen($peso_total)==0 or $peso_total == 0){
			$peso_total = count($lista_peca) * 0.300; // PADRAO 300g por peça
		}
		$cep_origem = "02054-100"; //Filizola
		$valor_frete = calcula_frete($cep_origem,$cep_destino,$peso_total);
		#echo "CEP Origem: $cep_origem - CEP Destno: $cep_destino - Peso: $peso_total";
		echo "ok|".$valor_frete;
		exit;
	}
	echo "nao|Erro: ".$msg_erro;
	exit;
}
?>