<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

function calcula_frete($cep_origem,$cep_destino,$peso,$codigo_servico = null ){
	$url = "www.correios.com.br";
	$ip = gethostbyname($url);
	$fp = fsockopen($ip, 80, $errno, $errstr, 10);

	if ($codigo_servico == null){
		$cod_servico     = "40010"; #Código SEDEX
	}else{
		$cod_servico = $codigo_servico;
	}

	if (strlen($cep_origem)>0 AND strlen($cep_destino)>0 AND strlen($peso)>0){
		$correios = "http://www.correios.com.br/encomendas/precos/calculo.cfm?servico=".$cod_servico."&cepOrigem=".$cep_origem."&cepDestino=".$cep_destino."&peso=".$peso."&MaoPropria=N&avisoRecebimento=N&resposta=xml";

		#echo $correios.'<BR><BR>';

		$correios_info = file($correios);

		foreach($correios_info as $info){
			$bsc = "/\<preco_postal>(.*)\<\/preco_postal>/";
			if(preg_match($bsc,$info,$tarifa)){
				$precofrete = $tarifa[1];
			}
		}
		return $precofrete;
	}else{
		return null;
	}
}

$embarque_local = $_GET['embarque_local'];
if (strlen ($embarque_local) > 0) {
	$res = pg_exec ($con,"SELECT posto FROM tbl_embarque WHERE embarque = $embarque_local");
	$posto = pg_result ($res,0,0);

	$res = pg_exec ($con,"SELECT fn_fecha_embarque ($posto, $embarque_local, 0, 0, 1057, 0,0,0)");
	$res = pg_exec ($con,"SELECT fn_fatura_embarque ($embarque_local)");
	header ("Location: $PHP_SELF");
	exit;
}


$btn_acao = $_POST['btn_acao'];
if ($btn_acao == 'faturar') {
	$qtde_item = $_POST['qtde_item'];

	$embarque_array = '';

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$embarque       = $_POST['embarque_'       . $i];
		$qtde_volume    = $_POST['qtde_volume_'    . $i];
		$transportadora = $_POST['transportadora_' . $i];
		$valor_frete    = $_POST['valor_frete_'    . $i];
		$frete_sedex    = $_POST['frete_sedex_'    . $i];
		$peso_real      = $_POST['peso_real_'    . $i];
		$peso_cubado    = $_POST['peso_cubado_'    . $i];
		
		if (strlen ($qtde_volume) > 0) {

			$valor_frete = str_replace (",",".",$valor_frete);
			if (substr_count ($valor_frete,".") > 1 ) {
				echo "<h1>Valor do Frete errado ($valor_frete)</h1>";
				exit;
			}

			$peso_real = str_replace (",",".",$peso_real);
			if (substr_count ($peso_real,".") > 1 ) {
				echo "<h1>Peso real errado ($peso_real)</h1>";
				exit;
			}

			$peso_cubado = str_replace (",",".",$peso_cubado);
			if (substr_count ($peso_cubado,".") > 1 ) {
				echo "<h1>Peso cubado errado ($peso_cubado)</h1>";
				exit;
			}

			$sql = "SELECT posto FROM tbl_embarque WHERE embarque = $embarque";
			$res = pg_exec ($con,$sql);
			$posto = pg_result ($res,0,0);

			if(strlen($frete_sedex)==0){
				$frete_sedex = 0;
			}
			$sql = "SELECT fn_fecha_embarque ($posto, $embarque, $qtde_volume, $valor_frete, $transportadora, $frete_sedex, $peso_real, $peso_cubado)";
#			echo $sql;
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage ($con)) > 0) $msg_erro .= pg_errormessage ($con);

			$embarque_array .= $embarque . ",";
		}
	}

	$res = pg_exec ($con,"COMMIT TRANSACTION");

	if (strlen ($msg_erro) == 0) {
		header ("Location: embarque_nota_fiscal.php?embarque_array=$embarque_array");
		exit;
	}
}



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Faturamento de Embarques</title>
</head>

<body>

<? include 'menu.php' ?>

<center><h1>Faturamento de Embarques</h1></center>

<p>

<?
$sql = "SELECT DATE_PART ('HOUR',current_timestamp)";
$res = pg_exec ($con,$sql);
$hora = pg_result ($res,0,0);
if ($hora > 14) {
#	echo "<center><br><br><br> <h1><font color='#990033'>Faturamento liberado somente até às 15 horas</font></h1> </center>";
#	exit;
}

if ($hora > 12) {
	echo "<center><br><h2><font color='#663366'>Atenção, faturamento apenas até às 15 horas </font> </h2> </center>";
}


$sql = "SELECT tbl_embarque.embarque, tbl_posto.nome, tbl_posto.posto, tbl_posto.cep as cep_destino, 17519255 as cep_origem
	FROM tbl_embarque JOIN tbl_posto USING (posto) 
	WHERE tbl_embarque.faturar IS NULL 
	AND tbl_embarque.embarque <> 0 
	AND tbl_embarque.distribuidor = $login_posto
	ORDER BY tbl_embarque.embarque";
$res = pg_exec ($con,$sql);

echo "<table align='center' cellspacind='4' border='1'>";
echo "<tr bgcolor='#3300CC' align='center' style='color:#ffffff'>";
echo "<td>Embarque</td>";
echo "<td>Posto</td>";
echo "<td>CEP origem</td>";
echo "<td>CEP destino</td>";
echo "<td>Frete SEDEX</td>";
echo "<td>Peso Real</td>";
echo "<td>Peso Cúbico</td>";
echo "<td>Volumes</td>";
echo "<td>Frete R$</td>";
echo "<td>Transp.</td>";
echo "</tr>";

echo "<form name='frm_faturamento' action='$PHP_SELF' method='post'>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	echo "<input type='hidden' name='embarque_$i' value='" . pg_result ($res,$i,embarque) . "'>";
	echo "<tr style='font-size:12px'>";

	echo "<td>";
	$embarque = pg_result ($res,$i,embarque);
	echo $embarque;
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,nome);
	echo "</td>";

	#O seu Laudir estava reclamando que aparecia itens que não foram liberados, e encontrei embarques que nao tinham itens, e que a query abaixo entendia que nao estavam impressas, entao fiz esta gambiara. 2009-06-04. Samuel
	$sqly = "SELECT count(*) as tem FROM tbl_embarque_item 
			WHERE embarque = $embarque";
	$resy = pg_exec ($con,$sqly);
	$nao_mostrar= "";
	if(pg_result($resy,0,tem)==0){
		$nao_mostrar = "não mostrar";
	}

	$sql = "SELECT * FROM tbl_embarque_item WHERE embarque = $embarque AND impresso IS NULL";
	$resX = pg_exec ($con,$sql);

	if (pg_numrows ($resX) > 0 OR strlen($nao_mostrar)>0) {
		echo "<td colspan='3'></td>";
	}else{
		if (pg_result ($res,$i,posto) == 4311) {
			echo "<td colspan='3'><a href='$PHP_SELF?embarque_local=$embarque'><font size='+1'>Liberar</font></a></td>";
		}else{
			// rotina de calculo do frete sedex automatico pelo correio
			echo "<td>";
			$cep_origem  = pg_result ($res,$i,cep_origem);
			echo "<input type='text' size='8' name='cep_origem_$i' value='$cep_origem' readonly>";
			echo "</td>";
			echo "<td>";
			$cep_destino  = pg_result ($res,$i,cep_destino);
			echo "<input type='text' size='8' name='cep_destino_$i' value='$cep_destino' readonly>";
			echo "</td>";
			$peso_total = .3; //calcular sempre pelo peso mínimo a pedido do Tulio.
#			$frete_sedex = calcula_frete($cep_origem,$cep_destino,$peso_total);
# não é mais necessário, e estava atrasando o processamento. TULIO 23/10/2009
			$frete_sedex = 0;
			echo "<td>";
			echo "<input type='text' size='5' name='frete_sedex_$i' value='$frete_sedex' readonly>";
			echo "</td>";
			// final da rotina de calculo sedex

			echo "<td>";
			echo "<input type='text' size='5' name='peso_real_$i' value='$peso_real' >kg";
			echo "</td>";

			echo "<td>";
			echo "<input type='text' size='5' name='peso_cubado_$i' value='$peso_cubado' >kg";
			echo "</td>";

			echo "<td>";
			echo "<input type='text' size='5' name='qtde_volume_$i' value='$qtde_volume'>";
			echo "</td>";

			echo "<td>";
			echo "<input type='text' size='10' name='valor_frete_$i' value='$valor_frete'><br>";
			echo "</td>";

			echo "<td>";
			echo "<select name='transportadora_$i' size='1'>";
	#		echo "<option value='1055' SELECTED>VARIG-LOG</option>";
			echo "<option value='1058' SELECTED>PAC</option>";
			echo "<option value='1061'>PAC (TC)</option>";
			echo "<option value='1062'>PAC (AK)</option>";
			echo "<option value='1056'>SEDEX</option>";
	#		Adicionei e-sedex. Fabio - 05/08/2007 - Solicitado por Ronaldo
			echo "<option value='1060'>E-SEDEX</option>";
			echo "<option value='1057'>PROPRIO</option>";
			echo "<option value='497' >BRASPRESS</option>";
			echo "<option value='703' >MERCURIO</option>";
			echo "<option value='4176' >JADLOG</option>";
	#		echo "<option value='1059'>RODONAVES</option>";
	#		echo "<option value='1060'>E-SEDEX</option>";
			echo "</select>";
			echo "</td>";
		}
	}

	echo "</tr>";
}

echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='hidden' name='qtde_item' value='$i'>";

echo "<tr>";
echo "<td colspan='5' align='center'>";

echo "<br>";

echo "<a href=\"javascript: 
if (confirm ('Confirma Faturamento') == true ) {
	if (document.frm_faturamento.btn_acao.value==''){
		document.frm_faturamento.btn_acao.value='faturar' ;
		document.frm_faturamento.submit();
	}else{
		alert('Aguarde submissão');
	}
} \">

";
echo "<b>Faturar</b>";
echo "</a>";
echo "<br>&nbsp;";
echo "</td>";

echo "</tr>";

echo "</form>";

echo "</table>";


?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
