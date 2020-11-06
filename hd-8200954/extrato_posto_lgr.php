<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";


$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_exec ($con,$sql);
$posto_da_fabrica = pg_result ($res2,0,0);

$msg_erro = "";
$msg      = "";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$tem_mais_itens='nao';
$contadorrr=0;


if (strlen($extrato)==0){
	#header("Location: os_extrato.php");
	echo "extrato: $extrato";
	exit;
}

if ($login_fabrica == 11){
	$sql = "SELECT  CASE WHEN data_geracao > '2008-08-01'::date THEN '1' ELSE '0' END
			FROM tbl_extrato
			WHERE extrato = $extrato ";
	$res2 = pg_exec ($con,$sql);
	$verificacao = pg_result ($res2,0,0);
	#2008-06-01 - HD 16362
	#Prorrogado o prazo para AGOSTO/2008, conforme chamado 22638
}

if ($login_fabrica==50){#HD 48024
	$verificacao = "1";
}

$pecas_pendentes = trim($_GET['pendentes']);
if (strlen($pecas_pendentes)==0){
	$pecas_pendentes = trim($_POST['pendentes']);
}

if ($pecas_pendentes!="sim" AND strlen($posto_da_fabrica)>0){
	$sql = "SELECT posto,distribuidor,extrato_devolucao
			FROM tbl_faturamento
			WHERE distribuidor      = $login_posto
			AND   posto             = $posto_da_fabrica
			AND   extrato_devolucao = $extrato";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		header("Location: extrato_posto_lgr_itens.php?extrato=$extrato");
		exit();
	}else{
		$sql = "SELECT distinct
					tbl_faturamento.extrato_devolucao,
					tbl_faturamento.distribuidor,
					CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					CASE WHEN  tbl_os_item.peca_obrigatoria IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS devolucao_obrigatoria
			FROM    tbl_faturamento
			JOIN    tbl_faturamento_item USING (faturamento)
			JOIN	tbl_os_item ON tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
			JOIN    tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca OR tbl_peca.peca = tbl_faturamento_item.peca_pedida
			WHERE   tbl_faturamento.extrato_devolucao = $extrato
			AND     tbl_faturamento.posto             = $login_posto
			AND     tbl_faturamento.distribuidor IS NULL
			AND     tbl_faturamento.cancelada is null
			AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '5.9%' OR tbl_faturamento.cfop LIKE '69%' OR tbl_faturamento.cfop LIKE '6.9%') ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			for($i=0;$i< pg_num_rows($res);$i++ ){
				$produto_acabado = pg_fetch_result($res, $i, 'produto_acabado');
				$devolucao_obrigatoria = pg_fetch_result($res, $i, 'devolucao_obrigatoria');
				if($produto_acabado == "TRUE" or $devolucao_obrigatoria == "TRUE"){
					$direciona = 'nao';
				}
			}
			if(empty($direciona)) {
				header("Location: extrato_posto_lgr_itens.php?extrato=$extrato");
				exit();
			}

		}
	}
}

$query = "SELECT count(*) FROM tbl_extrato_lgr WHERE extrato=$extrato AND posto=$login_posto AND qtde-qtde_nf>0";
$res = pg_exec ($con,$query);
if ( pg_result ($res,0,0)>0){
	$tem_mais_itens='sim';
}


$btn_acao = trim($_POST['botao_acao']);

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {

	$sql_update = "	UPDATE tbl_extrato_lgr
					SET qtde_pedente_temp = qtde
					WHERE extrato=$extrato";
	$res_update = pg_exec ($con,$sql_update);
	$msg_erro .= pg_errormessage($con);

	$numero_linhas   = trim($_POST['qtde_linha']);
}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas         = trim($_POST['qtde_pecas']);
	$numero_linhas      = trim($_POST['qtde_linha']);
	$numero_de_notas    = trim($_POST['numero_de_notas']);

	$data_preenchimento = date("Y-m-d");
	$array_notas        = array();

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=0;$i<$numero_de_notas;$i++){

		$nota_fiscal = trim($_POST["nota_fiscal_$i"]);

		#echo "Numero de notas: $nota_fiscal <br> $msg_erro";

		if (strlen($nota_fiscal)==0){
			$msg_erro .='Digite todas as notas fiscais!';
			break;
		}

		if (!is_numeric($nota_fiscal)){
			$msg_erro .='Digite somente n�mero nas NF';
			break;
		}

		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(",","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);

		array_push($array_notas,$nota_fiscal);

		$total_nota = trim($_POST["id_nota_$i-total_nota"]);
		$base_icms  = trim($_POST["id_nota_$i-base_icms"]);
		$valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
		$base_ipi   = trim($_POST["id_nota_$i-base_ipi"]);
		$valor_ipi  = trim($_POST["id_nota_$i-valor_ipi"]);
		$cfop       = trim($_POST["id_nota_$i-cfop"]);
		$movimento  = trim($_POST["id_nota_$i-movimento"]);

		$qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);

		#HD 18528
		if ($verificacao=='1'){
			$base_ipi   = 0;
			$valor_ipi  = 0;
		}

		$natureza = "Devolu��o de Garantia ";
		if ($login_fabrica == 50){
			$natureza = "Retorno de remessa para troca";
		}

		$sql_nota = "SELECT nota_fiscal,extrato_devolucao from tbl_faturamento where nota_fiscal = '$nota_fiscal' and distribuidor = $login_posto and posto = $posto_da_fabrica and fabrica = $login_fabrica";

		$res_nota = pg_exec($con,$sql_nota);

		if(pg_num_rows($res_nota)>0) {
			$nota_fiscal = pg_result($res_nota,0,0);
			$extrato_devolucao = pg_result($res_nota,0,1);
			$msg_erro .= "A Nota fiscal $nota_fiscal j� foi utilizada no extrato $extrato_devolucao, por favor digite outra nota";
		}

		$sql = "INSERT INTO tbl_faturamento
				(fabrica, emissao,saida, posto, distribuidor, cfop, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs, movimento)
				VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',$posto_da_fabrica,$login_posto,'$cfop',$total_nota,'$nota_fiscal','2','$natureza', $base_icms, $valor_icms, $base_ipi, $valor_ipi, $extrato, 'Devolu��o de pe�as do posto para � F�brica','$movimento')";
		$res = pg_exec ($con,$sql);

		#echo "<hr>Nota Fiscal: $nota_fiscal <br>".$sql;

		$sql = "SELECT CURRVAL ('seq_faturamento')";
		$resZ = pg_exec ($con,$sql);
		$faturamento_codigo = pg_result ($resZ,0,0);

		for($x=1;$x<=$qtde_peca_na_nota;$x++){

			$lgr                = trim($_POST["id_item_LGR_$x-$i"]);
			$peca               = trim($_POST["id_item_peca_$x-$i"]);
			$peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
			$peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
			$peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
			$peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
			$peca_total_item    = trim($_POST["id_item_total_$x-$i"]);

			$sql_update = "UPDATE tbl_extrato_lgr
							SET qtde_nf   = (CASE WHEN qtde_nf IS NULL THEN 0 ELSE qtde_nf END) + $peca_qtde_total_nf
							WHERE extrato = $extrato
							AND peca      = $peca";
			$res_update = pg_exec ($con,$sql_update);
			$msg_erro .= pg_errormessage($con);

			$peca_aliq_icms2 = $peca_aliq_icms;
			$peca_aliq_ipi2  = $peca_aliq_ipi;

			if($login_fabrica == 43) {  #676641
				$peca_aliq_icms2 = 0 ;
			}

			if (strlen($peca_aliq_icms2)>0 AND $peca_aliq_icms2 > 0) {
				$peca_aliq_icms2 = " AND   tbl_faturamento_item.aliq_icms = ".$peca_aliq_icms2;
			}else{
				$peca_aliq_icms2 = "  AND  (tbl_faturamento_item.aliq_icms = 0 OR tbl_faturamento_item.aliq_icms IS NULL)";
			}

			if (strlen($peca_aliq_ipi2)>0 AND $peca_aliq_ipi2 > 0) {
				$peca_aliq_ipi2 = " AND   tbl_faturamento_item.aliq_ipi = ".$peca_aliq_ipi2;
			}else{
				$peca_aliq_ipi2 = "  AND  (tbl_faturamento_item.aliq_ipi = 0 OR tbl_faturamento_item.aliq_ipi IS NULL)";
			}

			$sql_nf = "SELECT
							tbl_faturamento_item.faturamento_item,
							tbl_faturamento.nota_fiscal,
							tbl_faturamento_item.qtde,
							tbl_faturamento_item.peca,
							tbl_faturamento_item.preco,
							tbl_faturamento_item.aliq_icms,
							tbl_faturamento_item.aliq_ipi,
							tbl_faturamento_item.base_icms,
							tbl_faturamento_item.valor_icms,
							tbl_faturamento_item.linha,
							tbl_faturamento_item.base_ipi,
							tbl_faturamento_item.valor_ipi,
							tbl_faturamento_item.sequencia
						FROM tbl_faturamento_item
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica           = $login_fabrica
						AND   tbl_faturamento.posto             = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   (tbl_faturamento_item.peca         = $peca or tbl_faturamento_item.peca_pedida = $peca)
						AND   tbl_faturamento_item.preco        = $peca_preco
						$peca_aliq_icms2 ";
			if ($verificacao!='1'){
				$sql_nf .= " $peca_aliq_ipi2  ";
			}
			$sql_nf .= "AND   tbl_faturamento.distribuidor      IS NULL
						AND   tbl_faturamento.cancelada         IS NULL
						ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_exec ($con,$sql_nf);
			$qtde_peca_inserir=0;

			if (pg_numrows ($resNF)==0 ){
				$email_origem  = "helpdesk@telecontrol.com.br";
				$email_destino = 'helpdesk@telecontrol.com.br';
				$assunto       = "Extrato com erro Fabrica = $login_fabrica $extrato ";
				$corpo.="MENSAGEM AUTOM�TICA. extrato_posto_lgr.php(posto) N�O RESPONDA A ESTE E-MAIL \n\n $msg_erro \n $sql_nf";
				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
				break;
			}

			for ($w = 0 ; $w < pg_numrows ($resNF) ; $w++) {

				if ($qtde_peca_inserir < $peca_qtde_total_nf){

					$faturamento_item= pg_result ($resNF,$w,faturamento_item);
					$peca_nota       = pg_result ($resNF,$w,nota_fiscal);
					$peca_qtde       = pg_result ($resNF,$w,qtde);
					$peca_peca       = pg_result ($resNF,$w,peca);
					$peca_preco      = pg_result ($resNF,$w,preco);
					$peca_aliq_icms  = pg_result ($resNF,$w,aliq_icms);
					$peca_base_icms  = pg_result ($resNF,$w,base_icms);
					$peca_valor_icms = pg_result ($resNF,$w,valor_icms);
					$peca_linha      = pg_result ($resNF,$w,linha);
					$peca_aliq_ipi   = pg_result ($resNF,$w,aliq_ipi);
					$peca_base_ipi   = pg_result ($resNF,$w,base_ipi);
					$peca_valor_ipi  = pg_result ($resNF,$w,valor_ipi);
					$sequencia       = pg_result ($resNF,$w,sequencia);

					#HD 18528
					if ($verificacao=='1'){
						$aliq_ipi        = 0;
						$peca_valor_ipi  = 0;
						$peca_base_ipi   = 0;
					}

					# ICMS
					if (strlen($peca_aliq_icms)==0) {$peca_aliq_icms = "0";}
					if (strlen($peca_valor_icms)==0){$peca_valor_icms = "0";}
					if (strlen($peca_base_icms)==0) {$peca_base_icms = "0";}

					#IPI
					if (strlen($peca_aliq_ipi)==0) {$peca_aliq_ipi = "0";}
					if (strlen($peca_valor_ipi)==0){$peca_valor_ipi = "0";}
					if (strlen($peca_base_ipi)==0) {$peca_base_ipi = "0";}

					if (strlen($peca_linha)==0){
						$peca_linha = " NULL ";
					}

					$qtde_peca_inserir += $peca_qtde;

					if ($qtde_peca_inserir > $peca_qtde_total_nf and $login_fabrica <> 50){
						$peca_base_icms  = 0;
						$peca_valor_icms = 0;
						$peca_base_ipi   = 0;
						$peca_valor_ipi  = 0;
						$peca_qtde       = $peca_qtde-$qtde_peca_inserir;
						$peca_qtde       = $peca_qtde - ($qtde_peca_inserir-$peca_qtde_total_nf);

						if ($peca_aliq_icms>0){
							$peca_base_icms = $peca_qtde_total_nf*$peca_preco;
							$peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
						}
						if ($peca_aliq_ipi>0){
							$peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
							$peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
						}
					}

					$sql = "INSERT INTO tbl_faturamento_item
							(	faturamento,
								peca,
								qtde,
								preco,
								aliq_icms,
								aliq_ipi,
								base_icms,
								valor_icms,
								linha,
								base_ipi,
								valor_ipi,
								nota_fiscal_origem,
								sequencia,
								extrato_devolucao
							)
							VALUES (
								$faturamento_codigo,
								$peca,
								$peca_qtde,
								$peca_preco,
								$peca_aliq_icms,
								$peca_aliq_ipi,
								$peca_base_icms,
								$peca_valor_icms,
								$peca_linha,
								$peca_base_ipi,
								$peca_valor_ipi,
								'$peca_nota',
								'$sequencia',
								$extrato
								)";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					break;
				}
			}

		}
	}

	if (strlen($msg_erro) == 0) {
		$sql_update = "UPDATE tbl_extrato_lgr
				SET qtde_pedente_temp = null
				WHERE extrato = $extrato";
		$res_update = pg_exec ($con,$sql_update);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (count(array_unique($array_notas))<>$numero_de_notas){
			$msg_erro .= "Erro: n�o � permitido digitar n�mero de notas iguais. Preencha novamente as notas.";
		}
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: extrato_posto_lgr_itens.php?extrato=$extrato");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


$msg = "";

$layout_menu = "os";
$title = "Pe�as Retorn�veis do Extrato";

include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: red
}
.menu_top3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #FA8072
}


.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<script type="text/javascript">

function verificar(forrr){
	var theform = document.getElementById('frm_devolucao');
	var returnval=true;
	for (i=0; i<theform.elements.length; i++){
		if (theform.elements[i].type=="text"){
			if (theform.elements[i].value==""){ //if empty field
				alert("Por favor, informe todas as notas!");
				theform.botao_acao.value='';
				returnval=false;
				break;
			}
		}
	}
	return returnval;
}

</script>

<br><br>
<?

if (strlen($posto_da_fabrica)==0){
	echo "<center><h1>Devolu��o n�o configurada.</h1></center>";
	echo "<br>";
	echo "<br>";
	include "rodape.php";
	exit;
}

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_exec ($con,$sql);
$data    = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome    = pg_result ($res,0,nome);
$codigo  = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

?>

<p>
<div id='loading'></div>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<center>

<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'>
	<b>
	<?
		if ($pecas_pendentes=="sim") echo "DEVOLU��O PENDENTE";
		else                         echo "ATEN��O";
	?>
	</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	As pe�as ou produtos n�o devolvidos neste extrato ser�o cobrados do posto autorizado.
	<br><br>
	<?
		if ($login_fabrica==2) {
			echo "<b style='font-size:14px;font-weight:normal'>Emitir as NFs de devolu��o nos mesmos valores e impostos, referenciando NF de origem Dynacom, e postagem da NF para Dynacom</b>";
		}elseif ($login_fabrica==11) {
			echo "<b style='font-size:14px;font-weight:normal'>Emitir as NFs de devolu��o nos mesmos valores e impostos, referenciando NF de origem Lenoxx, e postagem da NF para Lenoxx</b>";
		}
	?>

	</TD>
</TR>
</table>

<br>

<?

$nota_fiscal = "";

$sql = "SELECT contato_estado as estado FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica = $login_fabrica";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

if ($numero_linhas!==5000){
	$distinct = " DISTINCT ";
}
	$sql = "SELECT $distinct
					tbl_faturamento.extrato_devolucao,
					tbl_faturamento.distribuidor,
					CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					CASE WHEN  tbl_os_item.peca_obrigatoria IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS devolucao_obrigatoria
	FROM    tbl_faturamento
	JOIN    tbl_faturamento_item USING (faturamento)
	JOIN	tbl_os_item ON tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
	JOIN    tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca OR tbl_peca.peca = tbl_faturamento_item.peca_pedida
	WHERE   tbl_faturamento.extrato_devolucao = $extrato
	AND     tbl_faturamento.posto             = $login_posto
	AND     tbl_faturamento.distribuidor IS NULL
	AND     tbl_faturamento.cancelada is null
	AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '5.9%' OR tbl_faturamento.cfop LIKE '69%' OR tbl_faturamento.cfop LIKE '6.9%')
	";
if (($login_fabrica == 11 AND $verificacao=='1') OR $login_fabrica==2 OR $login_fabrica==45 OR $login_fabrica==80 OR $login_fabrica==72 OR $login_fabrica > 80) {
	$sql .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
}

if ($numero_linhas!==5000){
	$sql .= " ORDER BY produto_acabado DESC , devolucao_obrigatoria DESC ";
}else{
	$sql .= " LIMIT 1";
}
$res = pg_exec ($con,$sql);
$res_qtde = pg_numrows ($res);


if ($res_qtde == 0) {
	echo "<h1><center> N�o h� pe�as para devolu��o </center></h1>";
}

if ($res_qtde > 0) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
	echo "<input type='hidden' name='notas_d' value=''>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;

	for( $xx = 0; $xx < $res_qtde ; $xx++) {

		$distribuidor          = trim (pg_result ($res,$xx,distribuidor));
		$produto_acabado       = trim (pg_result ($res,$xx,produto_acabado));
		$devolucao_obrigatoria = trim (pg_result ($res,$xx,devolucao_obrigatoria));
		$extrato_devolucao     = trim (pg_result ($res,$xx,extrato_devolucao));

		$extrato_devolucao = $extrato;

		$condicao_3 = "";

		if($login_fabrica == 50 AND $produto_acabado != "TRUE" AND $devolucao_obrigatoria != "TRUE"){
			continue;
		}

		if ($produto_acabado == "NOT TRUE"){
			$devolucao = " N�O RETORN�VEIS ";
			$movimento = "NAO_RETOR.";
			$pecas_produtos = "PE�AS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
			$condicao_3 = "";
			$sql_adicional_peca2 = " AND tbl_os_item.peca_obrigatoria IS NOT TRUE";
		}

		if ($devolucao_obrigatoria == 't' or $devolucao_obrigatoria == 'TRUE'){
			$devolucao = " RETORNO OBRIGAT�RIO ";
			$movimento = "RETORNAVEL";
			$pecas_produtos = "PE�AS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
			$condicao_3 = "";
			$sql_adicional_peca2 = " AND tbl_os_item.peca_obrigatoria IS TRUE";
		}

		if ($produto_acabado == "TRUE"){
			$devolucao = " RETORNO OBRIGAT�RIO ";
			$movimento = "RETORNAVEL";
			$pecas_produtos = "PRODUTOS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS TRUE ";
			$condicao_3 = "";
			$sql_adicional_peca2 = "";
		}

		if (strlen ($posto_da_fabrica) > 0 && $login_fabrica <> 43) {
			$sql  = "SELECT * FROM tbl_posto WHERE posto = $posto_da_fabrica";
			$resX = pg_exec ($con,$sql);

			$estado   = pg_result ($resX,0,estado);
			$razao    = pg_result ($resX,0,nome);
			$endereco = trim (pg_result ($resX,0,endereco)) . " " . trim (pg_result ($resX,0,numero));
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);
		}else{
			$sql  = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$resX = pg_exec ($con,$sql);

			$razao    = pg_result ($resX,0,razao_social);
			$endereco = pg_result ($resX,0,endereco);
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);
		}

		$condicao_3 = "  AND tbl_faturamento.distribuidor IS NULL ";
		$distribuidor = "null";
		$condicao_1   = " AND tbl_faturamento.distribuidor IS NULL ";

		if ($numero_linhas!=5000){
			#$sql_adicional_peca=" AND tbl_extrato_lgr.qtde_pedente_temp>0";
		}else{
			$sql_adicional_peca="";
		}

		$sql = "SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_os_item.peca_obrigatoria as devolucao_obrigatoria,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				SUM(tbl_faturamento_item.qtde) as qtde_real,
				sum(tbl_os_item.qtde) as qtde_item,
				tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END AS qtde_total_item,
				tbl_extrato_lgr.qtde_nf AS qtde_total_nf,
				tbl_extrato_lgr.qtde_pedente_temp AS qtde_pedente_temp,
				tbl_extrato_lgr.extrato_lgr AS extrato_lgr,
				(tbl_extrato_lgr.qtde_pedente_temp * tbl_faturamento_item.preco) AS total_item,
				tbl_faturamento.cfop,
				SUM (tbl_faturamento_item.base_icms) AS base_icms,
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
				FROM tbl_peca
				JOIN tbl_os_item ON tbl_os_item.peca = tbl_peca.peca
				JOIN tbl_faturamento_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido AND (tbl_os_item.peca = tbl_faturamento_item.peca OR tbl_os_item.peca = tbl_faturamento_item.peca_pedida)
				JOIN tbl_faturamento      USING (faturamento)
				JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_lgr.extrato
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   tbl_faturamento.posto=$login_posto
				/*AND (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END)>0*/
				AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '5.9%' OR tbl_faturamento.cfop LIKE '69%' OR tbl_faturamento.cfop LIKE '6.9%')
				AND tbl_faturamento.cancelada is null
				$condicao_1
				$condicao_2
				$condicao_3
				$sql_adicional_peca
				$sql_adicional_peca2
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento.emissao > tbl_extrato.data_geracao - interval '6 months'
				GROUP BY tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_os_item.peca_obrigatoria,
					tbl_peca.produto_acabado,
					tbl_peca.ipi,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento.cfop,
					tbl_faturamento_item.qtde,
					tbl_extrato_lgr.qtde,
					total_item,
					qtde_total_nf,
					qtde_pedente_temp,
					extrato_lgr
					ORDER BY tbl_peca.referencia";
		$notas_fiscais=array();
		$qtde_peca=0;

		$resX = pg_exec ($con,$sql);

		if (pg_numrows ($resX)==0) {
			continue;
		}

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
		$tota_pecas       = 0;
		$peca_ant="";
		$qtde_acumulada=0;

		$z=0;
		$total_qtde = pg_numrows ($resX);
		for ($x = 0 ; $x < $total_qtde ; $x++) {
			$tem_mais_itens = 'sim';
			$contador++;
			$item_nota++;
			$z++;

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$peca_preco          = pg_result ($resX,$x,preco);
			$qtde_real           = pg_result ($resX,$x,qtde_real);
			$qtde_item           = pg_result ($resX,$x,qtde_item);
			$qtde_total_item     = pg_result ($resX,$x,qtde_total_item);
			$qtde_total_nf       = pg_result ($resX,$x,qtde_total_nf);
			$qtde_pedente_temp   = pg_result ($resX,$x,qtde_pedente_temp);
			$qtde_pedente_temp_AUX= pg_result ($resX,$x,qtde_pedente_temp);
			$extrato_lgr         = pg_result ($resX,$x,extrato_lgr);
			$total_item          = pg_result ($resX,$x,total_item);
			$base_icms           = pg_result ($resX,$x,base_icms);
			$valor_icms          = pg_result ($resX,$x,valor_icms);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$base_ipi            = pg_result ($resX,$x,base_ipi);
			$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
			$valor_ipi           = pg_result ($resX,$x,valor_ipi);
			$ipi                 = pg_result ($resX,$x,ipi);
			$cfop                = pg_result ($resX,$x,cfop);
			$peca_produto_acabado= pg_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria= pg_result ($resX,$x,devolucao_obrigatoria);

			$aliq_icms2 = $aliq_icms;
			$aliq_ipi2  = $aliq_ipi;

			if (strlen($aliq_icms2)>0){
				$aliq_icms2 = " = ".$aliq_icms2;
			}else{
				$aliq_icms2 = " IS NULL ";
			}

			if (strlen($aliq_ipi2)>0){
				$aliq_ipi2 = " = ".$aliq_ipi2;
			}else{
				$aliq_ipi2 = " IS NULL ";
			}

			if ($qtde_pedente_temp>$qtde_real AND $numero_linhas!=5000){
				$qtde_pedente_temp = $qtde_real;
			}

			if($qtde_total_item < $qtde_real) {
				$qtde_real = $qtde_total_item;
			}

			if($qtde_real > $qtde_item) {
				$qtde_real = $qtde_item;
			}
			$qtde_acumulada = $qtde_real;
			$qtde_acumulada += $qtde_real;

			$sql_nf = "		SELECT	tbl_faturamento.nota_fiscal,
									tbl_faturamento_item.qtde
							FROM tbl_faturamento_item
							JOIN tbl_faturamento      USING (faturamento)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.posto   = $login_posto
							AND   tbl_faturamento.extrato_devolucao = $extrato
							AND   tbl_faturamento.emissao > tbl_extrato.data_geracao - interval '6 months'
							AND   tbl_faturamento.cancelada      IS NULL
							AND tbl_faturamento_item.peca  = $peca
							/*HD 20204*/
							AND   tbl_faturamento_item.preco     = $peca_preco
							AND   tbl_faturamento_item.aliq_icms $aliq_icms2 ";
			if ($verificacao!='1'){
				$sql_nf .= "AND   tbl_faturamento_item.aliq_ipi  $aliq_ipi2 ";
			}
			$sql_nf .= "	AND   tbl_faturamento.cfop = '$cfop'::text
							ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_exec ($con,$sql_nf);

			if (strlen($qtde_total_nf)==0) {
				$qtde_total_nf=0;
			}

			$qtde_aux=0;
			$qtde_peca=0;

			if (strlen($qtde_pedente_temp)==0){
				$qtde_pedente_temp=$qtde_total_item;
			}

			for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
				if ($qtde_aux<$qtde_total_nf) {
					$qtde_aux += pg_result ($resNF,$y,qtde);
					continue;
				}

				if ($qtde_peca <= $qtde_real){
					$qtde_peca += pg_result ($resNF,$y,qtde);
				}

			}

			for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
				array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
				$notas_fiscais = array_unique($notas_fiscais);
				asort($notas_fiscais);
			}

			if (strlen ($aliq_icms)  == 0) {
				$aliq_icms = 0;
			}

			if (strlen($aliq_ipi)==0) {
				$aliq_ipi=0;
			}

			if ($verificacao=='1'){
				$aliq_ipi = "0"; #HD 18528
				if ($aliq_ipi>0){
					$peca_preco = $peca_preco + ($peca_preco * $aliq_ipi/100);
				}
			}

			$total_item  = $peca_preco * $qtde_real;
			$tota_pecas += $total_item;

			if($login_fabrica <> 43) { // HD 102163
				if ($aliq_icms==0){
					$base_icms=0;
					$valor_icms=0;
				}else{
						$base_icms  = $total_item;
						$valor_icms = $total_item * $aliq_icms / 100;
				}
			}else{
				$aliq_icms = $valor_icms / $peca_preco * 100;
				$aliq_icms = number_format($aliq_icms,0,'','');
			}

			if ($aliq_ipi==0) 	{
				$base_ipi=0;
				$valor_ipi=0;
			}else {
				$base_ipi  = $total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;


			/* CABE�ALHO DA NOTA */
			$cabecalho  = "<br><br>\n";
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

			$cabecalho .= "<tr align='left'  height='16'>\n";
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
			$cabecalho .= "</td>\n";
			$cabecalho .= "</tr>\n";

			$cabecalho .= "<tr>\n";
			if ($login_fabrica == 50){ /*HD 48024*/
				$cfop = "5949H";
				if($estado_origem !='SP') {
					$cfop = "6949";
				}
				$cabecalho .= "<td>Natureza <br> <b>Retorno de remessa para troca</b> </td>\n";
				$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
			}else{
				$cabecalho .= "<td>Natureza <br> <b>Devolu��o de Garantia</b> </td>\n";
				$cabecalho .= "<td>CFOP <br> <b> ".$cfop." </b> </td>\n";
			}
			$cabecalho .= "<td>Emiss�o <br> <b>".date("d/m/Y")."</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cnpjx = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Raz�o Social <br> <b>$razao</b> </td>\n";
			$cabecalho .= "<td>CNPJ <br> <b>$cnpjx</b> </td>\n";
			$cabecalho .= "<td>Inscri��o Estadual <br> <b>$ie</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cepx = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Endere�o <br> <b>$endereco </b> </td>\n";
			$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
			$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
			$cabecalho .= "<td>CEP <br> <b>$cepx</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$topo ="";
			$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
			$topo .=  "<thead>\n";

			$topo .=  "<tr align='center'>\n";
			$topo .=  "<td><b>C�digo</b></td>\n";
			$topo .=  "<td><b>Descri��o</b></td>\n";
			$topo .=  "<td><b>Qtde.</b></td>\n";
			$topo .=  "<td><b>Pre�o</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			if ($verificacao!='1'){
				$topo .=  "<td><b>% IPI</b></td>\n";
			}
			$topo .=  "</tr>\n";
			$topo .=  "</thead>\n";

			if ( ( $x == 0 OR $imprimir_cabecalho == 1 ) AND $numero_linhas!=5000 ){
				echo $cabecalho;
				echo $topo;
				$imprimir_cabecalho=0;
			}

			if ( $numero_linhas!=5000 ){
				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
				echo "<td align='left'>";
				echo "$peca_referencia";
				echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
				echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
				echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$peca_preco'>\n";
				echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde_real'>\n";
				echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
				echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
				echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
				echo "</td>\n";
				echo "<td align='left'>$peca_descricao</td>\n";
				echo "<td align='center'>$qtde_real</td>\n";
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				if ($verificacao!='1'){
					echo "<td align='right'>$aliq_ipi</td>\n";
				}
				echo "</tr>\n";
			}


			if ($numero_linhas !=5000 AND ($z%$numero_linhas==0 OR $x+1 == $total_qtde)){
				if ($verificacao=='1'){
					$total_geral = $total_nota;
				}else{
					$total_geral = $total_nota+$total_valor_ipi;
				}
				echo "</table>\n";
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
				echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
				if ($verificacao=='1'){
					echo "<td>Total de Pe�as <br> <b> " . number_format ($tota_pecas,2,",",".") . " </b> </td>\n";
				}else{
					echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
				}
				echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
				echo "</tr>\n";
				if (count($notas_fiscais)>0){
					echo "<tfoot>";
					echo "<tr>";
					echo "<td colspan='8'> Referente as NFs. " . implode(", ",$notas_fiscais) . "</td>";
					echo "</tr>";
					echo "</tfoot>";
				}
				$notas_fiscais=array();
				$qtde_peca="";
				echo "</table>\n";

				if (strlen ($nota_fiscal)==0) {
					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
					echo "<tr>";
					echo "<td>";
					echo "\n<br>";
//					echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-cfop'       value='$cfop'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-movimento'  value='$movimento'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-base_icms'  value='$total_base_icms'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi'   value='$total_base_ipi'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi'  value='$total_valor_ipi'>\n";
					echo "<center>";

					/* HD 48024*/
					if ($login_fabrica == 50){
						echo "<p><b>Observa��o:</b> RETORNO DE REMESSA DE MERCADORIA PARA TROCA EM GARANTIA.  PARTES E PECAS DE BEM VENDIDO EM GARANTIA, SUSPENSO DO IPI, ART 42  ITEM XIII, DECRETO No. 4.544/02</p>";
					}

					echo "<b>Preencha esta Nota de Devolu��o e informe o n�mero da Nota Fiscal</b><br>Este n�mero n�o poder� ser alterado<br>";
					echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>N�mero da sua Nota Fiscal: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='7' value='$nota_fiscal'>";
					echo "<br><br>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					$numero_nota++;
				}else{
					if (strlen ($nota_fiscal) >0){
						echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
						echo "<tr>\n";
						echo "<td><h1><center>Nota de Devolu��o $nota_fiscal</center></h1></td>\n";
						echo "</tr>";
						echo "</table>";
					}
				}

				$imprimir_cabecalho = 1;
				$total_base_icms  = 0;
				$total_valor_icms = 0;
				$total_base_ipi   = 0;
				$total_valor_ipi  = 0;
				$total_nota       = 0;
				$tota_pecas       = 0;
				$item_nota=0;
			}

			flush();
		}

		echo "</table>\n";
	}



	if ($numero_linhas==5000){

			if ($pecas_pendentes=='sim'){
				echo "<input type='hidden' name='pendentes' value='sim'>";
			}

			echo "<br>
					<input type='hidden' name='qtde_pecas' value='$contador'>
					<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>
					<b style='font-size:12px'>


					<b>Informar a quantidade de linhas no formul�rio da Nota Fiscal do Posto Autorizado:</b>
					<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
					Essa informa��o definir� a quantidade de NFs que o posto autorizado dever� emitir e enviar � F�brica
					<br><br>
					<input type='button' id='fechar' value='Gerar Nota Fiscal de Devolu��o' name='gravar' onclick=\"javascript:
					if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0')
							alert('Informe a quantidade de itens!!');
					else{
						if (document.frm_devolucao.botao_acao.value=='digitou_qtde'){
							alert('Aguarde submiss�o');
						}
						else{
							document.frm_devolucao.botao_acao.value='digitou_qtde';
							this.form.submit();
						}
					}
						\"><br><br>
				  ";
	}else{

		if ($login_fabrica == 11){
			$sqlConf = "
						SELECT extrato,admin_lgr
						FROM tbl_extrato
						WHERE fabrica=11
						AND posto=$login_posto
						AND extrato < $extrato
						AND extrato > 206832
						AND liberado IS NOT NULL
						ORDER BY data_geracao DESC
						LIMIT 1";
			$resConf = pg_exec ($con,$sqlConf);
			if (pg_numrows($resConf)>0){

				$admin_lgr  = trim(pg_result($resConf,0,admin_lgr));

				# Verifica se as notas de devolu��o do Mes anterior foi recebido pela Fabrica
				$sqlConf = "SELECT	faturamento,
									nota_fiscal,
									emissao - CURRENT_DATE AS dias_emitido,
									conferencia,
									devolucao_concluida
							FROM tbl_faturamento
							WHERE fabrica = $login_fabrica
							AND distribuidor = $login_posto
							AND   tbl_faturamento.cancelada      IS NULL
							AND posto IS NOT NULL
							AND extrato_devolucao in (
										SELECT extrato
										FROM tbl_extrato
										WHERE fabrica = $login_fabrica
										AND posto     = $login_posto
										AND extrato < $extrato
										AND liberado IS NOT NULL
										ORDER BY data_geracao DESC
										LIMIT 1
							)";
				$resConf = pg_exec ($con,$sqlConf);
				$notas_array = array();
				$msg_notas = "";

				$sqlLgr = " SELECT count(*)
							FROM   tbl_faturamento
							WHERE  fabrica           = $login_fabrica
							AND   tbl_faturamento.cancelada      IS NULL
							AND extrato_devolucao in (
										SELECT extrato
										FROM tbl_extrato
										WHERE fabrica = $login_fabrica
										AND posto     = $login_posto
										AND extrato < $extrato
										AND liberado IS NOT NULL
										ORDER BY data_geracao DESC
										LIMIT 1
							)
							AND   (cfop LIKE '59%' OR cfop LIKE '69%' OR cfop LIKE '5.9%' OR cfop LIKE '6.9%')
							AND    distribuidor IS NULL";
				$resLGR = pg_exec ($con,$sqlLgr);
				$qtde_devolucao = trim(@pg_result($resLGR,0,0));

				if (pg_numrows($resConf)>0 OR $qtde_devolucao == 0){
					for ( $w=0; $w < pg_numrows($resConf); $w++ ){
						$fat_faturamento  = trim(pg_result($resConf,$w,faturamento));
						$fat_nota_fiscal  = trim(pg_result($resConf,$w,nota_fiscal));
						$fat_dias_emitido = trim(pg_result($resConf,$w,dias_emitido));
						$fat_conferencia  = trim(pg_result($resConf,$w,conferencia));
						$fat_concluido    = trim(pg_result($resConf,$w,devolucao_concluida));

						// $admin_lgr -> se a F�brica liberou o mes anterior, deixa digitar este mes
						if (strlen($admin_lgr)==0 AND strlen($fat_conferencia)==0 AND $fat_concluido!='t'){
							array_push($notas_array,$fat_nota_fiscal);
						}
					}
					if (count($notas_array)==1){
						$msg_notas = "O Fabricante n�o acusou o recebimento da nota N� ".implode(",",$notas_array).". Favor entrar em contato c/ a Taiz TEL: 071 3379-1997, P/ posterior libera��o da M.O.";
					}elseif(count($notas_array)>1){
						$msg_notas = "O Fabricante n�o acusou o recebimento das notas N� ".implode(",",$notas_array).". Favor entrar em contato c/ a Taiz TEL: 071 3379-1997, P/ posterior libera��o da M.O.";
					}
				}else{
					# Se nenhuma nota foi preenchida
					$msg_notas = "As notas fiscais referente ao extrato anterior n�o foram preenchidas.";
				}
			}
		}

		if(strlen($msg_notas)==0){
			echo "<br><br><br>
					<input type='hidden' name='qtde_linha' value='$numero_linhas'>
					<input type='hidden' name='numero_de_notas' value='$numero_nota'>
					<input type='hidden' name='numero_de_notas_tc' value='$numero_nota_tc'>

					<b>Preencha TODAS as notas acima e clique no bot�o abaixo para confirmar!</b>
					<br><br>
					<input type='button' value='Confirmar notas de devolu��o' name='gravar' onclick=\"javascript:
						if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
							alert('Aguarde Submiss�o');
						}else{
							if(confirm('Deseja continuar? As notas de devolu��o n�o poder�o ser alteradas!')){
								if (verificar('frm_devolucao')){
									document.frm_devolucao.botao_acao.value='digitou_as_notas';
									document.frm_devolucao.submit();
								}
							}
						}
						\">

					<br>";

			echo "<br><br><input type='button' value='Voltar a Tela Anterior' name='gravar' onclick=\"javascript:
					if(confirm('Deseja voltar?')) window.location='$PHP_SELF?extrato=$extrato';\">";
		}else{
			echo "<h4>$msg_notas</h4>";
		}

	}
	echo "</form>";
}

?>


<? include "rodape.php"; ?>
