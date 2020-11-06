<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include "funcoes.php";

$msg_erro = "";
$btn_acao = $_POST['btn_acao'];
$linha_form = $_GET['linha_form'];
$peca_referencia = $_GET['referencia_peca'];
$condicao        = $_GET['condicao'];
$tipo_pedido     = $_GET['tipo_pedido'];
$qtde            = $_GET['qtde'];
$codigo_posto    = $_GET['posto'];
$tabela = ($tipo_pedido == 193) ? 434 : 435;

if (strlen ($condicao) > 0 OR strlen ($linha_form) > 0) {

	$sql = "SELECT preco
			FROM tbl_tabela_item
			JOIN tbl_peca USING(peca)
			WHERE fabrica = $login_fabrica
			AND   tabela = $tabela
			AND  referencia = '$peca_referencia'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$preco = pg_fetch_result($res,0,'preco');
		$preco = $preco * $qtde;
		$preco = number_format ($preco,2,".","");

		if($tipo_pedido == 193) {
			$sqlMarkup = "SELECT mark_up_percent,
								 discount_percent,
								 interest_rate,
								 tax_rate
								 FROM tbl_black_posto_extra
								 join tbl_posto_fabrica using(posto,fabrica)
								WHERE codigo_posto = '$codigo_posto'
								AND tbl_black_posto_extra.fabrica = $login_fabrica ";
			$resMarkup = pg_query($con,$sqlMarkup);

			if (pg_num_rows($resMarkup)>0) {
					$mark_up_percent  = pg_fetch_result($resMarkup,0,0);
					$discount_percent = pg_fetch_result($resMarkup,0,1);
					$interest_rate    = pg_fetch_result($resMarkup,0,2);
					$tax_rate         = pg_fetch_result($resMarkup,0,3);

					##PRIMEIRA ETAPA CALCULAR POR DENTRO PERCENTUAL DO TAX_RATE (PIS CONFINS ICMS)##
					$tax_rate = (100-$tax_rate)/100; echo "   ";
					$preco_mais = $preco/$tax_rate;
					$preco_mais = number_format ($preco_mais,2,".","");
					#############################################################

					##SEGUNDA ETAPA CALCULAR POR DENTRO PERCENTUAL DO mark_up_percent (VALOR SUGERIDO CLIENTE)##
					$mark_up_percent = (100-$mark_up_percent)/100; echo "    ";
					$preco_mais = $preco_mais/$mark_up_percent;
					$preco_mais = number_format ($preco_mais,2,".","");

					#############################################################
					##TERCEIRA ETAPA CALCULAR POR FORA PERCENTUAL DE DESCONTO DO TIPO DO POSTO##
					$discount_percent = $discount_percent/100;
					$preco_mais = $preco_mais - ($preco_mais*$discount_percent);
					$preco_mais = number_format ($preco_mais,2,".","");
					#############################################################


					#############################################################
					##QUARTA ETAPA CALCULAR POR FORA PERCENTUAL DE JUROS DO POSTO##
					$interest_rate = $interest_rate/100;
					$preco_mais = $preco_mais + ($preco_mais*$interest_rate);
					$preco_mais = number_format ($preco_mais,2,".","");
					$total_preco = $preco_mais;
					$preco_mais = $preco_mais / $qtde;
					$juro = $preco_mais*$interest_rate;
					$juro = number_format ($juro,2,".","");
					#############################################################
			}		
		}else{
			$preco_mais = $preco;	
		} 
	}else{
		$preco = 0;
	}



	#-------- Para uso do AJAX ----------
	echo "<br>";
	echo "<preco>";
	echo number_format ($preco_mais,2,",",".");
	echo "|";
	echo $linha_form;
	echo "|";
	echo "$total_preco";
	echo "|";
	echo $mudou;
	echo "|";
	echo $produto_referencia;
	echo "|";
	echo $cod_devolve;
	echo "|";
	echo $juro;
	echo "</preco>";
	#------------------------------------
}

?>