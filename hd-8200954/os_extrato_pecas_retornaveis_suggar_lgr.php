<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro = "";
$msg      = "";

$periodo = trim($_GET['periodo']);
if (strlen($periodo)==0){
	$periodo = trim($_POST['periodo']);
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}


$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$tem_mais_itens='nao';
$contadorrr=0;
$posto_da_fabrica = "27253";


if (strlen($periodo)==0){
	#header("Location: os_extrato.php");
	#exit;
}

$pecas_pendentes = trim($_GET['pendentes']);
if (strlen($pecas_pendentes)==0){
	$pecas_pendentes = trim($_POST['pendentes']);
}

$extratos = array();

if (strlen($periodo)>0){
	$periodo_array = explode("-",$periodo);

	$mes = $periodo_array[0];
	if (strlen($mes)==1){
		$mes = "0".$mes;
	}
	if ($mes>12 or $mes<0){
		$mes = "01";
	}
	$mes_proximo = $mes +1;
	if ($mes_proximo>12){
		$mes_proximo = "01";
	}

	$ano         = $periodo_array[1];
	$ano_proximo = $ano;

	if ($mes_proximo=="01"){
		$ano_proximo++;
	}
}else{
	if (strlen($extrato)>0){
		$sql = "SELECT TO_CHAR(tbl_extrato.data_geracao,'MM') AS mes, TO_CHAR(tbl_extrato.data_geracao,'YYYY') AS ano
				FROM tbl_extrato
				WHERE tbl_extrato.fabrica  = $login_fabrica
				AND tbl_extrato.posto      = $login_posto
				AND tbl_extrato.extrato    = $extrato
				";
		$resNF = pg_exec ($con,$sql);
		if (pg_numrows ($resNF)>0){
			$mes = pg_result ($resNF,0,mes);
			$ano = pg_result ($resNF,0,ano);
			$periodo = $mes."-".$ano;
		}
	}else{
		header("Location: os_extrato.php");
		exit;
	}
}

if (strlen($msg_erro)==0 AND strlen($periodo)>0){
	$sql = "SELECT DISTINCT extrato
			FROM tbl_extrato_lgr
			JOIN tbl_extrato           USING(extrato)
			WHERE tbl_extrato.data_geracao BETWEEN '$ano-$mes-01 00:00:01' AND '$ano_proximo-$mes_proximo-01 00:00:01'
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			";
	$sql = "SELECT DISTINCT extrato
			FROM tbl_extrato_lgr
			JOIN tbl_extrato           USING(extrato)
			WHERE TO_CHAR(tbl_extrato.data_geracao,'MM-YYYY') = '$mes-$ano'
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			";
	$resNF = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resNF) ; $i++) {
		array_push($extratos,pg_result ($resNF,$i,extrato));
	}
}

if (strlen($extrato)>0){
	$extratos = array();
	array_push($extratos,$extrato);
}

/*
$query = "SELECT count(*)
			FROM tbl_extrato_lgr
			WHERE extrato = $extrato
			AND posto     = $login_posto
			AND qtde - qtde_nf > 0";
$res = pg_exec ($con,$query);
if ( pg_result ($res,0,0)>0){
	$tem_mais_itens='sim';
}
*/

$btn_acao = trim($_POST['botao_acao']);

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {

	#$sql_update = "UPDATE tbl_extrato_lgr
	#		SET qtde_pedente_temp = qtde
	#		WHERE extrato=$extrato";
	#$res_update = pg_exec ($con,$sql_update);
	#$msg_erro .= pg_errormessage($con);

	$numero_linhas   = trim($_POST['qtde_linha']);
}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas         = trim($_POST['qtde_pecas']);
	$numero_linhas      = trim($_POST['qtde_linha']);
	$numero_de_notas    = trim($_POST['numero_de_notas']);

	$data_preenchimento = date("Y-m-d");
	$array_notas        = array();

/*	$sql = "SELECT posto,distribuidor,extrato_devolucao
			FROM tbl_faturamento
			WHERE distribuidor      = $login_posto
			AND   posto             = $posto_da_fabrica
			AND   extrato_devolucao = $extrato";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		#header("Location: os_extrato.php");
		#exit();
	} */

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=0;$i<$numero_de_notas;$i++){

		$nota_fiscal = trim($_POST["nota_fiscal_$i"]);

		if (strlen($nota_fiscal)==0){
			$msg_erro .='Digite todas as notas fiscais!';
			break;
		}

		if (!is_numeric($nota_fiscal)){
			$msg_erro .='Digite somente número nas NF';
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

		$Xextrato = $extratos[0];
		if (strlen($extrato)==0){
			$Xextrato = " NULL ";
		}
		if (strlen($extrato)>0){
			$Xextrato = $extrato;
		}

		$sql = "INSERT INTO tbl_faturamento
				(fabrica, emissao,saida, posto, distribuidor, cfop, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi,  obs, extrato_devolucao)
				VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',$posto_da_fabrica, $login_posto,'$cfop',$total_nota,'$nota_fiscal','2','Devolução de peças em garantia', $base_icms, $valor_icms, $base_ipi, $valor_ipi, 'Devolução de peças do posto para à Fábrica',$Xextrato)";
		$res = pg_exec ($con,$sql);

		$sql = "SELECT CURRVAL ('seq_faturamento')";
		$resZ = pg_exec ($con,$sql);
		$faturamento_codigo = pg_result ($resZ,0,0);

		for($x=1;$x<=$qtde_peca_na_nota;$x++){

			$lgr                = trim($_POST["id_item_LGR_$x-$i"]);
			$peca               = trim($_POST["id_item_peca_$x-$i"]);
			$peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
			$peca_qtde          = trim($_POST["id_item_qtde_$x-$i"]);
			$peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
			$peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
			$peca_total_item    = trim($_POST["id_item_total_$x-$i"]);
/*
			$sql_update = "UPDATE tbl_extrato_lgr
							SET qtde_nf   = (CASE WHEN qtde_nf IS NULL THEN 0 ELSE qtde_nf END) + $peca_qtde_total_nf
							WHERE extrato = $extrato
							AND peca      = $peca";
			$res_update = pg_exec ($con,$sql_update);
			$msg_erro .= pg_errormessage($con);
*/
/*
			$sql_nf = "
				SELECT
					tbl_peca.peca                                    AS peca,
					tbl_peca.referencia                              AS peca_referencia,
					tbl_peca.descricao                               AS peca_descricao ,
					sum(tbl_os_item.qtde)                            AS qtde            ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')  AS data_geracao
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				JOIN tbl_os_produto using(os)
				JOIN tbl_os_item using(os_produto)
				JOIN tbl_peca using(peca)
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				WHERE tbl_extrato.data_geracao BETWEEN '$ano-$mes-01 00:00:01' AND '$ano-$mes_proximo-01 00:00:01'
				AND tbl_extrato.fabrica                   = $login_fabrica
				AND tbl_extrato.posto                     = $login_posto
				AND tbl_peca.devolucao_obrigatoria        IS TRUE
				AND tbl_servico_realizado.troca_de_peca   IS TRUE
				AND tbl_servico_realizado.gera_pedido     IS TRUE
				GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_extrato.data_geracao
			";
			//echo "<br><br>$sql_nf";
			//echo "<br>$peca | $peca_preco |	$peca_qtde_total_nf | $peca_total_item ";
			$resNF = pg_exec ($con,$sql_nf);
			$qtde_peca_inserir=0;

			if (pg_numrows ($resNF)==0){
				$msg_erro .= "Erro.";
				# Nelson pediu para nw mandar mais email HD 2937
				$email_origem  = "helpdesk@telecontrol.com.br";
				$email_destino = 'fabio@telecontrol.com.br';
				$assunto       = "Extrato com erro";
				$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n $msg_erro \n $sql_nf";
				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem);
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

					if (strlen($peca_linha)==0){
						$peca_linha = " NULL ";
					}

					$qtde_peca_inserir += $peca_qtde;

					if ($qtde_peca_inserir > $peca_qtde_total_nf){
						#echo "<br><br>Precisa desmembrar<br><br>";
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
*/
					if(strlen($peca_preco) == 0) {
						$peca_preco = 0;
					}
					$sql = "INSERT INTO tbl_faturamento_item
							(faturamento, peca, qtde,preco, aliq_icms, aliq_ipi,  valor_icms, valor_ipi)
							VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $valor_icms,  $valor_ipi)";

					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql_update = "UPDATE tbl_extrato_lgr
									SET qtde_nf = qtde
									WHERE extrato IN (".implode(",",$extratos).")
									and peca = $peca";
					$res_update = pg_exec ($con,$sql_update);
					$msg_erro .= pg_errormessage($con);

					/*
				}else{
					break;
				}
			}*/
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql_update = "UPDATE tbl_extrato_lgr
						SET qtde_pedente_temp = NULL
						WHERE extrato IN (".implode(",",$extratos).")";
		$res_update = pg_exec ($con,$sql_update);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (count(array_unique($array_notas))<>$numero_de_notas){
			$msg_erro .= "Erro: não é permitido digitar número de notas iguais. Preencha novamente as notas.";
		}
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: extrato_posto_devolucao_itens.php?extrato=$extrato&periodo=$periodo");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/*
$sql = "SELECT posto,distribuidor,extrato_devolucao
		FROM tbl_faturamento
		WHERE distribuidor      = $login_posto
		AND   posto             = $posto_da_fabrica
		AND   extrato_devolucao = $extrato";
$res = pg_exec ($con,$sql);
if (pg_numrows($res)>0){
	#header("Location: extrato_posto_devolucao_itens.php?extrato=$extrato");
	#exit();
}
*/

if (count($extratos)>0){
	$sql = "SELECT count(*) as qtde
			FROM tbl_extrato_lgr
			JOIN tbl_extrato           USING(extrato)
			JOIN tbl_peca              USING(peca)
			WHERE tbl_extrato.extrato IN (".implode(",",$extratos).")
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			AND tbl_peca.devolucao_obrigatoria        IS TRUE
			AND tbl_extrato_lgr.qtde_nf > 0
			";
	$res = pg_exec ($con,$sql);
	$res_qtde = pg_result ($res,0,qtde);
	if ($res_qtde>0){
		header("Location: os_extrato_pecas_retornaveis_suggar_itens.php?extrato=$extrato&periodo=".$periodo);
		exit;
	}
}

$msg = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

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

$mes_nome=array("","Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");

if (strlen($extrato)>0){
	echo "<font size='+1' face='arial'>Devolução de Peças - Extrato ".$extrato."</font>";
	echo "<br>";
}else{
	echo "<font size='+1' face='arial'>Peças referentes aos extratos de ".$mes_nome[intval($mes)]."/$ano</font>";
	echo "<br>";
}
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
		echo ($pecas_pendentes=="sim") ? "DEVOLUÇÃO PENDENTE" : "ATENÇÃO";
	?>
	</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	<b style='font-size:14px;font-weight:normal'>Emitir as NFs de devolução nos mesmos valores e impostos, referenciando NF de origem Suggar, e postagem da NF para a Suggar</b>
	</TD>
</TR>
</table>

<br>

<?

$nota_fiscal = "";

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

$res_qtde = 0;
if (count($extratos)>0){
	$sql = "SELECT count(*) as qtde
			FROM tbl_extrato_lgr
			JOIN tbl_extrato           USING(extrato)
			JOIN tbl_peca              USING(peca)
			WHERE tbl_extrato.extrato IN (".implode(",",$extratos).")
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			AND tbl_peca.devolucao_obrigatoria        IS TRUE
			AND ( tbl_extrato_lgr.qtde > tbl_extrato_lgr.qtde_nf OR tbl_extrato_lgr.qtde_nf IS NULL)
			";
	$res = pg_exec ($con,$sql);
	$res_qtde = pg_result ($res,0,qtde);

	$sql = "SELECT count(*) as qtde
			FROM tbl_os_extra
			JOIN tbl_os_produto        using(os)
			JOIN tbl_os_item           using(os_produto)
			JOIN tbl_peca              using(peca)
			JOIN tbl_extrato           using(extrato)
			JOIN tbl_servico_realizado using(servico_realizado)
			JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os  = tbl_os_extra.os
				AND tbl_estoque_posto_movimento.peca = tbl_os_item.peca
			left JOIN tbl_faturamento             ON tbl_faturamento.faturamento     = tbl_estoque_posto_movimento.faturamento_devolucao
			left JOIN tbl_faturamento_item        ON tbl_faturamento_item.faturamento= tbl_faturamento.faturamento
				AND tbl_faturamento_item.peca = tbl_os_item.peca
			WHERE tbl_os_extra.extrato IN (".implode(",",$extratos).")
			AND tbl_extrato.fabrica                   = $login_fabrica
			AND tbl_extrato.posto                     = $login_posto
			AND tbl_peca.devolucao_obrigatoria        IS TRUE
			AND tbl_servico_realizado.troca_de_peca   IS TRUE
			AND tbl_servico_realizado.gera_pedido     IS TRUE
			AND tbl_estoque_posto_movimento.qtde_entrada IS NULL
			";
	$resX = pg_exec ($con,$sql);
	$resX_qtde = pg_result ($resX,0,qtde);
}

/* LOG DE ERRO LGR HD 122163 */
if ($res_qtde == 0 AND $resX_qtde > 0) {
/* HD 334920
	$sqlxx = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
				(
					SELECT
					tbl_os_extra.extrato,
					tbl_os.posto,
					tbl_peca.peca,
					SUM(tbl_os_item.qtde)
					FROM tbl_os
					JOIN tbl_os_extra          using(os)
					JOIN tbl_os_produto        using(os)
					JOIN tbl_os_item           using(os_produto)
					JOIN tbl_peca              using(peca)
					JOIN tbl_extrato           using(extrato)
					JOIN tbl_servico_realizado using(servico_realizado)
					JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os  = tbl_os.os  AND tbl_estoque_posto_movimento.peca = tbl_os_item.peca
					JOIN tbl_faturamento             ON tbl_faturamento.faturamento     = tbl_estoque_posto_movimento.faturamento_devolucao
					WHERE tbl_os_extra.extrato                = $extrato
					AND tbl_extrato.fabrica                   = $login_fabrica
					AND tbl_extrato.posto                     = $login_posto
					AND tbl_peca.devolucao_obrigatoria        IS TRUE
					AND tbl_servico_realizado.troca_de_peca   IS TRUE
					AND tbl_servico_realizado.gera_pedido     IS TRUE
					AND tbl_estoque_posto_movimento.qtde_entrada IS NULL
					GROUP BY tbl_os.posto,tbl_os_extra.extrato,tbl_peca.peca
				)";
	$resxx = pg_exec($con,$sqlxx);
*/
}

if ($res_qtde > 0 AND $resX_qtde > 0) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
	echo "<input type='hidden' name='periodo' value='$periodo'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' name='extratos' value='$extratos'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;

	#for( $xx = 0; $xx < $res_qtde ; $xx++) {
	if (1==1){
/*
		$distribuidor          = trim (pg_result ($res,$xx,distribuidor));
		$produto_acabado       = trim (pg_result ($res,$xx,produto_acabado));
		$devolucao_obrigatoria = trim (pg_result ($res,$xx,devolucao_obrigatoria));
		$extrato_devolucao     = trim (pg_result ($res,$xx,extrato_devolucao));

		$extrato_devolucao = $extrato;
*/
		$condicao_3 = "";
/*
		if ($produto_acabado == "NOT TRUE"){
			$devolucao = " NÃO RETORNÁVEIS ";
			$movimento = "NAO_RETOR.";
			$pecas_produtos = "PEÇAS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
			$condicao_3 = "";
			$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria IS NOT TRUE";
		}

		if ($devolucao_obrigatoria == 't'){
			$devolucao = " RETORNO OBRIGATÓRIO ";
			$movimento = "RETORNAVEL";
			$pecas_produtos = "PEÇAS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
			$condicao_3 = "";
			$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria IS TRUE";
		}

		if ($produto_acabado == "TRUE"){
			$devolucao = " RETORNO OBRIGATÓRIO ";
			$movimento = "RETORNAVEL";
			$pecas_produtos = "PRODUTOS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS TRUE ";
			$condicao_3 = "";
			$sql_adicional_peca2 = "";
		}
*/

		if (strlen ($posto_da_fabrica) > 0) {
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
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;

/*
		$condicao_3 = "  AND tbl_faturamento.distribuidor IS NULL ";

		$distribuidor = "null";
		$condicao_1   = " AND tbl_faturamento.distribuidor IS NULL ";
*/
		if ($numero_linhas!=5000){
			//$sql_adicional_peca=" AND tbl_extrato_lgr.qtde_pedente_temp>0";
		}else{
			$sql_adicional_peca="";
		}

		$sql = "SELECT
						tbl_peca.peca                                    AS peca,
						tbl_peca.referencia                              AS peca_referencia,
						tbl_peca.descricao                               AS peca_descricao ,
						sum(tbl_os_item.qtde)                            AS qtde            ,
						tbl_faturamento.cfop,
						tbl_faturamento_item.preco,
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.aliq_ipi
				FROM tbl_os_extra
				JOIN tbl_os_produto        using(os)
				JOIN tbl_os_item           using(os_produto)
				JOIN tbl_peca              using(peca)
				JOIN tbl_extrato           using(extrato)
				JOIN tbl_servico_realizado using(servico_realizado)
				JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os  = tbl_os_extra.os AND tbl_estoque_posto_movimento.peca = tbl_os_item.peca
				left JOIN tbl_faturamento             ON tbl_faturamento.faturamento     = tbl_estoque_posto_movimento.faturamento_devolucao
				left JOIN tbl_faturamento_item        ON tbl_faturamento_item.faturamento= tbl_faturamento.faturamento AND tbl_faturamento_item.peca = tbl_os_item.peca AND tbl_faturamento_item.extrato_devolucao = tbl_extrato.extrato
				WHERE tbl_os_extra.extrato IN (".implode(",",$extratos).")
				AND tbl_extrato.fabrica                   = $login_fabrica
				AND tbl_extrato.posto                     = $login_posto
				AND tbl_peca.devolucao_obrigatoria        IS TRUE
				AND tbl_servico_realizado.troca_de_peca   IS TRUE
				AND tbl_servico_realizado.gera_pedido     IS TRUE
				AND tbl_estoque_posto_movimento.qtde_entrada IS NULL
				GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao,  tbl_faturamento.cfop, tbl_faturamento_item.preco,tbl_faturamento_item.aliq_icms,tbl_faturamento_item.aliq_ipi
				";

		$notas_fiscais=array();
		$qtde_peca=0;

		$resX = pg_exec ($con,$sql);

		if (pg_numrows ($resX)==0) ;

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
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
			$peca_referencia     = pg_result ($resX,$x,peca_referencia);
			$peca_descricao      = pg_result ($resX,$x,peca_descricao);
			$qtde_real           = pg_result ($resX,$x,qtde);
			#$nota_fiscal         = pg_result ($resX,$x,nota_fiscal);
			$cfop                = pg_result ($resX,$x,cfop);
			$peca_preco          = pg_result ($resX,$x,preco);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$ipi                 = pg_result ($resX,$x,aliq_ipi);

			#array_push($notas_fiscais,$nota_fiscal);
/*
			$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.cfop, preco, aliq_icms, aliq_ipi
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING(faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND tbl_faturamento.posto     = $login_posto
					AND tbl_faturamento_item.peca = $peca
					ORDER BY tbl_faturamento.faturamento DESC
					LIMIT 1";
			$resY = pg_exec ($con,$sql);
			if (pg_numrows ($resY)>0){
				$peca_preco      = pg_result ($resY,0,preco);
				$aliq_icms       = pg_result ($resY,0,aliq_icms);
				$ipi             = pg_result ($resY,0,aliq_ipi);
				$cfop            = pg_result ($resY,0,cfop);
			}else{
				$peca_preco      = 0;
				$aliq_icms       = 0;
				$ipi             = 0;
				$cfop            = "";
			}
*/
			#$peca                = pg_result ($resX,$x,peca);
/*
			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$peca_preco          = pg_result ($resX,$x,preco);
			$qtde_real           = pg_result ($resX,$x,qtde_real);
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

			if ($qtde_pedente_temp>$qtde_real AND $numero_linhas!=5000){
				$qtde_pedente_temp=$qtde_real;
			}

			$qtde_acumulada  = $qtde_real;
			$qtde_acumulada += $qtde_real;

*/
			$total_item  = $peca_preco * $qtde_real;

			if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

			if ($aliq_icms==0){
				$base_icms=0;
				$valor_icms=0;
			}else{
				$base_icms  = $total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if (strlen($aliq_ipi)==0) {
				$aliq_ipi=0;
			}

			if ($aliq_ipi==0){
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


			/* CABEÇALHO DA NOTA */
			$cabecalho  = "<br><br>\n";
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

			$cabecalho .= "<tr align='left'  height='16'>\n";
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			$cabecalho .= "<b>&nbsp;<b>Devolução Obrigatória </b><br>\n";
			$cabecalho .= "</td>\n";
			$cabecalho .= "</tr>\n";

			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
			$cabecalho .= "<td>CFOP <br> <b> $cfop </b> </td>\n";
			$cabecalho .= "<td>Emissão <br> <b>".date("d/m/Y")."</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";


			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
			$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
			$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
			$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
			$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
			$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$topo ="";
			$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
			$topo .=  "<thead>\n";

			$topo .=  "<tr align='center'>\n";
			$topo .=  "<td><b>Código</b></td>\n";
			$topo .=  "<td><b>Descrição</b></td>\n";
			$topo .=  "<td><b>Qtde.</b></td>\n";
			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";
			$topo .=  "</tr>\n";
			$topo .=  "</thead>\n";
			/* FIM CABEÇALHO DA NOTA */

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
				echo "<td align='right'>$aliq_ipi</td>\n";
				echo "</tr>\n";
			}

			if ($numero_linhas !=5000 AND ($z%$numero_linhas==0 OR $x+1 == $total_qtde)){
				//$total_valor_icms = $total_base_icms * $aliq_final / 100;
				$total_geral=$total_nota+$total_valor_ipi;
				echo "</table>\n";
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
				echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
				echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
				echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
				echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
				echo "</tr>\n";

				$sql_nf = " SELECT DISTINCT nota_fiscal
							FROM tbl_os_extra
							JOIN tbl_os_produto        using(os)
							JOIN tbl_os_item           using(os_produto)
							JOIN tbl_peca              using(peca)
							JOIN tbl_extrato           using(extrato)
							JOIN tbl_servico_realizado using(servico_realizado)
							JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os  = tbl_os_extra.os
								AND tbl_estoque_posto_movimento.peca = tbl_os_item.peca
							JOIN tbl_faturamento             ON tbl_faturamento.faturamento     = tbl_estoque_posto_movimento.faturamento_devolucao
							WHERE tbl_os_extra.extrato IN (".implode(",",$extratos).")
							AND tbl_extrato.fabrica                   = $login_fabrica
							AND tbl_extrato.posto                     = $login_posto
							AND tbl_peca.devolucao_obrigatoria        IS TRUE
							AND tbl_servico_realizado.troca_de_peca   IS TRUE
							AND tbl_servico_realizado.gera_pedido     IS TRUE
							AND tbl_estoque_posto_movimento.qtde_entrada IS NULL
							";
					
				$resNF = pg_exec ($con,$sql_nf);
				for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
					array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
				}
				#$notas_fiscais = asort($notas_fiscais);

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
					echo "<b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
					echo "<br><IMG SRC='imagens/setona_h.gif' width='53' height='29' border='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
					echo "<br><br>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					$numero_nota++;
				}else{
					if (strlen ($nota_fiscal) >0){
						echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
						echo "<tr>\n";
						echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
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


					<b>Informar a quantidade de linhas no formulário da Nota Fiscal do Posto Autorizado:</b>
					<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
					Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar à Fábrica
					<br><br>
					<input type='button' id='fechar' value='Gerar Nota Fiscal de Devolução' name='gravar' onclick=\"javascript:
					if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0')
							alert('Informe a quantidade de itens!!');
					else{
						if (document.frm_devolucao.botao_acao.value=='digitou_qtde'){
							alert('Aguarde submissão');
						}
						else{
							document.frm_devolucao.botao_acao.value='digitou_qtde';
							this.form.submit();
						}
					}
						\"><br><br>
				  ";
	}else{
		if(strlen($msg_notas)==0){
			echo "<br><br><br>
					<input type='hidden' name='qtde_linha' value='$numero_linhas'>
					<input type='hidden' name='numero_de_notas' value='$numero_nota'>

					<b>Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
					<br><br>
					<input type='button' value='Confirmar notas de devolução' name='gravar' onclick=\"javascript:
						if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
							alert('Aguarde Submissão');
						}else{
							if(confirm('Deseja continuar? As notas de devolução não poderão ser alteradas!')){
								if (verificar('frm_devolucao')){
									document.frm_devolucao.botao_acao.value='digitou_as_notas';
									document.frm_devolucao.submit();
								}
							}
						}
						\">
					<br>";
			echo "<br><br><input type='button' value='Voltar a Tela Anterior' name='gravar' onclick=\"javascript:
					if(confirm('Deseja voltar?')) window.location='$PHP_SELF?extrato=$extrato&periodo=$periodo';\">";
		}else{
			echo "<h4>$msg_notas</h4>";
		}
	}
	echo "</form>";
}else{
/*###########################################################
	PEÇAS RETORNAVEIS - HD 92975
  ###########################################################*/

	if(1==1){
		echo "<br><br>";
		$sqlx = "SELECT tbl_peca.peca
		INTO TEMP tmp_suggar_fat_$login_posto
		FROM tbl_os_extra
		JOIN tbl_os_produto        using(os)
		JOIN tbl_os_item           using(os_produto)
		JOIN tbl_peca              using(peca)
		JOIN tbl_extrato           using(extrato)
		JOIN tbl_servico_realizado using(servico_realizado)
		JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os  = tbl_os_extra.os
		AND tbl_estoque_posto_movimento.peca = tbl_os_item.peca
		JOIN tbl_faturamento             ON tbl_faturamento.faturamento     = tbl_estoque_posto_movimento.faturamento_devolucao
		JOIN tbl_faturamento_item        ON tbl_faturamento_item.faturamento= tbl_faturamento.faturamento
		AND tbl_faturamento_item.peca = tbl_os_item.peca
		WHERE tbl_os_extra.extrato IN (".implode(",",$extratos).")
		AND tbl_extrato.fabrica                   = $login_fabrica
		AND tbl_extrato.posto                     = $login_posto
		AND tbl_peca.devolucao_obrigatoria        IS TRUE
		AND tbl_servico_realizado.troca_de_peca   IS TRUE
		AND tbl_servico_realizado.gera_pedido     IS TRUE
		AND tbl_estoque_posto_movimento.qtde_entrada IS NULL;

		SELECT peca FROM tmp_suggar_fat_$login_posto;";

		$resx = @pg_exec ($con,$sqlx);
		if(@pg_numrows($resx)>0){
			$cond_temp = " AND tbl_peca.peca NOT IN(SELECT peca FROM tmp_suggar_fat_$login_posto) ";
		}

		$sql = "SELECT DISTINCT tbl_os.os                        ,
						tbl_os.sua_os                            ,
						tbl_os.consumidor_nome                   ,
						tbl_peca.referencia as peca_referencia   ,
						tbl_peca.descricao     as peca_nome      ,
						tbl_os_item.qtde                         ,
						tbl_os_item.preco                        ,
						tbl_os_item.custo_peca                   ,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				JOIN tbl_os_produto using(os)
				JOIN tbl_os_item using(os_produto)
				JOIN tbl_peca using(peca)
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_servico_realizado ON 
				tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				JOIN tbl_extrato_lgr ON tbl_extrato.extrato = tbl_extrato_lgr.extrato
				WHERE tbl_os_extra.extrato = $extrato
				AND tbl_extrato.fabrica    = $login_fabrica
				AND tbl_os_item.servico_realizado IN(504)
				AND tbl_peca.devolucao_obrigatoria IS TRUE
				AND tbl_servico_realizado.troca_de_peca IS TRUE
				$cond_temp";
			#echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		$totalRegistros = pg_numrows($res);

		if ($totalRegistros > 0){
		echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
			echo "<TR class='menu_top'>\n";
				echo "<TD colspan='4' align = 'center'>";
				echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) . " - RETORNO OBRIGATÓRIO" ;
				echo "</TD>";
			echo "</TR>\n";
			echo "<TR class='menu_top'>\n";
				echo "<TD align='center' >OS</TD>\n";
				echo "<TD align='center' >CLIENTE</TD>\n";
				echo "<TD align='center' >PEÇA</TD>\n";
				echo "<TD align='center' >QTDE</TD>\n";
			echo "</TR>\n";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
				$os					= trim(pg_result ($res,$i,os));
				$sua_os				= trim(pg_result ($res,$i,sua_os));
				$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
				$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
				$peca_nome			= trim(pg_result ($res,$i,peca_nome));
				$preco				= trim(pg_result ($res,$i,preco));
				$qtde				= trim(pg_result ($res,$i,qtde));
				$preco				= number_format($preco,2,",",".");

				$cor = (strstr($matriz, ";" . $i . ";")) ? '#E49494' : (($i % 2 == 0) ? '#F1F4FA' : "#d9e2ef");
				$btn = ($i % 2 == 0) ? 'azul' : 'amarelo';

				if (strlen ($sua_os) == 0) $sua_os = $os;

				echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
					echo "<TD align='left' nowrap>$consumidor</TD>\n";
					echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
					echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR>\n";
			}
			echo "</TABLE>\n";
		}else{
			echo "<h1><center> Não há peças para devolução </center></h1>";
		}
	}
}

?>

<? include "rodape.php"; ?>
