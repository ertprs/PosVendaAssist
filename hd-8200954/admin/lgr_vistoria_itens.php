<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

$msg_erro="";
$msg="";

$posto = trim($_GET['posto']);
if (strlen($posto)==0){
	$posto = trim($_POST['posto']);
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}

$resumo = trim($_GET['resumo']);


if (strlen($posto)==0){
	header("Location: lgr_vistoria.php");
	exit;
}

$btn_acao = trim($_GET['btn_acao']);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST['btn_acao']);
}

#Para estes postos devem ser mostrados somente os produto - HD 13651
/*RETIRADO CONFORME SOLICITADO PELO TULIO (VISITA A BRITANIA) 01/06/2009 -  CONVERSA NO CHAT COM IGOR*/
//$postos_permitidos_novo_processo = array(0 => 'LIXO',1 => '6976', 2 => '20397', 3 => '4044', 4 => '1267', 5 => '6458', 6 => '710', 7 => '5037', 8 => '1752', 9 => '4311', 10 => '1537',11 => '6359');
$postos_permitidos_novo_processo = array(0 => 'LIXO');

if ($btn_acao=="gravar"){

	$resX = pg_query ($con,"BEGIN TRANSACTION");

	$qtde_linhas = trim($_GET['qtde_linhas']);
	if (strlen($qtde_linhas)==0){
		$qtde_linhas = trim($_POST['qtde_linhas']);
	}

	if (strlen($qtde_linhas)==0){
		$qtde_linhas = 0;
	}

	$sql = "SELECT vistoria FROM tbl_vistoria WHERE extrato = $extrato ";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res)==0){
		$sql = "INSERT INTO tbl_vistoria (admin,extrato) VALUES ($login_admin,$extrato)";
		$res = pg_query ($con,$sql);
	}

	for ($i = 1; $i <= $qtde_linhas ; $i++){
		#echo "<hr>";
		$peca                    = trim($_POST["linha_peca_$i"]);
		$qtde_total              = trim($_POST["linha_qtde_$i"]);
		$qtde_total_inspecionada = trim($_POST["linha_qtde_insp_$i"]);
		$linha_peca_check        = trim($_POST["linha_peca_check_$i"]);

		if (strlen($qtde_total_inspecionada)==0){
			continue;
		}
		if ($linha_peca_check != 't'){
			continue;
		}

		if ($qtde_total_inspecionada > $qtde_total){
			$qtde_total_inspecionada = $qtde_total;
		}

		$qtde_total_inspecionada_aux = $qtde_total_inspecionada;

		if ( $qtde_total_inspecionada >0 ){

			#RESETA OS DADOS
			$sqlReset = "UPDATE tbl_faturamento_item SET
								qtde_inspecionada      = 0,
								qtde_inspecionada_real = 0,
								data_inspecao          = CURRENT_DATE
							WHERE (faturamento,faturamento_item,peca,qtde)
							IN (
								SELECT
									tbl_faturamento_item.faturamento,
									tbl_faturamento_item.faturamento_item,
									tbl_faturamento_item.peca,
									tbl_faturamento_item.qtde
								FROM tbl_faturamento
								JOIN tbl_faturamento_item USING(faturamento)
								WHERE tbl_faturamento.fabrica = $login_fabrica
									AND tbl_faturamento.cfop  ~'5949|6949'
									AND tbl_faturamento.posto = $posto
									AND tbl_faturamento.extrato_devolucao = $extrato
									AND tbl_faturamento_item.peca  = $peca

							)";
			$resReset = pg_query ($con,$sqlReset);
			#echo nl2br($sqlReset);
			#echo "<br>";

			$sql = "SELECT
						tbl_faturamento_item.faturamento,
						tbl_faturamento_item.faturamento_item,
						tbl_faturamento_item.peca,
						tbl_faturamento_item.qtde,
						tbl_faturamento_item.qtde_inspecionada
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING(faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
						AND tbl_faturamento.cfop  ~'5949|6949'
						AND tbl_faturamento.posto = $posto
						AND tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento_item.peca  = $peca
						AND tbl_faturamento_item.qtde -
							CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL
								THEN 0
								ELSE tbl_faturamento_item.qtde_inspecionada
							END
							> 0
					ORDER BY tbl_faturamento.emissao ASC";
			$res = pg_query ($con,$sql);
			for ( $j=0; $j< pg_num_rows($res); $j++ ){

				if ( $qtde_total_inspecionada_aux <= 0 ){
					break;
				}

				$faturamento		= pg_fetch_result ($res,$j,faturamento);
				$faturamento_item	= pg_fetch_result ($res,$j,faturamento_item);
				$peca				= pg_fetch_result ($res,$j,peca);
				$qtde				= pg_fetch_result ($res,$j,qtde);
				$qtde_inspecionada	= pg_fetch_result ($res,$j,qtde_inspecionada);

				if (strlen($qtde_inspecionada)==0){
					$qtde_inspecionada = 0;
				}

				$qtde = $qtde - $qtde_inspecionada;

				if ( $qtde  - $qtde_total_inspecionada_aux < 0 ){
					$qtde_atualizar = $qtde;
				}else{
					$qtde_atualizar = $qtde_total_inspecionada_aux;
				}

				$sql2 = "UPDATE tbl_faturamento_item SET
							qtde_inspecionada = (CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END) + $qtde_atualizar,
							qtde_inspecionada_real = (CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END) + $qtde_atualizar,
							data_inspecao     = CURRENT_DATE
						WHERE faturamento    = $faturamento
						AND faturamento_item = $faturamento_item
						AND peca = $peca";
				$res2 = pg_query ($con,$sql2);
				$msg_erro .= pg_errormessage($con);
				#echo nl2br($sql2);
				#echo "<br>";

				$qtde_total_inspecionada_aux = $qtde_total_inspecionada_aux - $qtde_atualizar;

				if (strlen($msg_erro)>0){
					break;
				}
			}
		}
		if ( $qtde_total_inspecionada == 0 ){
			$sql = "UPDATE tbl_faturamento_item SET
						qtde_inspecionada      = 0,
						qtde_inspecionada_real = 0,
						data_inspecao          = CURRENT_DATE
					FROM tbl_faturamento
					WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						AND tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento.fabrica   = $login_fabrica
						AND tbl_faturamento.cfop  ~'5949|6949'
						AND tbl_faturamento.posto     = $posto
						AND tbl_faturamento_item.peca = $peca";
						//AND tbl_faturamento_item.qtde_inspecionada IS NULL
						//AND tbl_faturamento_item.preco = $preco
			#echo nl2br($sql);
			#echo "<br>";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_query ($con,"COMMIT TRANSACTION");
		//$resX = pg_query ($con,"ROLLBACK TRANSACTION");
		$msg = "Conferência gravada com Sucesso!";
	}else{
		$resX = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

/* ROTINA PARA EFETUAR O ABATIMENTO DO VALOR DAS PEÇAS FALTANTES NA MÃO DE OBRA */
if ($btn_acao == "pagamento_confirmar"){

	$resX = pg_query ($con,"BEGIN TRANSACTION");

	$posto                     = trim($_POST['posto']);
	$extrato                   = trim($_POST['extrato']);
	$pagamento_desconto        = trim($_POST['pagamento_desconto']);
	$pagamento_multa           = trim($_POST['pagamento_multa']);
	$pagamento_total           = trim($_POST['pagamento_total']);
	$pagamento_total_geral     = trim($_POST['pagamento_total_geral']);
	$pagamento_numero_parcelas = trim($_POST['pagamento_numero_parcelas']);
	$observacao_historio       = trim($_POST['observacao_historio']);
	$lista_de_pecas            = trim($_POST['lista_de_pecas']);

	$observacao_historio_aux = $observacao_historio;

	if (strlen($lista_de_pecas)>0){
		$observacao_historio .= "\n\n".$lista_de_pecas;
	}

	if (strlen($posto)==0){
		$msg_erro .= " Erro. Posto não selecionado.";
	}

	if (strlen($pagamento_numero_parcelas)==0)	{$pagamento_numero_parcelas = 1;}
	if ($pagamento_numero_parcelas < 0)			{$pagamento_numero_parcelas = 0;}

	if (strlen($msg_erro)==0){

		$valor_parcela = 0;

		if ($pagamento_total_geral >0){
			$valor_parcela = $pagamento_total_geral / $pagamento_numero_parcelas;
		}

		if ($valor_parcela>0){
			$valor_parcela = $valor_parcela * (-1);
		}

		if ($pagamento_total_geral == 0){
			$pagamento_numero_parcelas = 1;
		}

		$sql = "SELECT vistoria FROM tbl_vistoria WHERE extrato = $extrato ";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res)==0){
			$sql = "INSERT INTO tbl_vistoria (admin,extrato) VALUES ($login_admin,$extrato)";
			$res = pg_query ($con,$sql);
			$sql = "SELECT currval('seq_vistoria');";
			$res = pg_query ($con,$sql);
			$vistoria = pg_fetch_result ($res,0,0);
		}else{
			$vistoria = pg_fetch_result ($res,0,vistoria);
		}

		$sql = "UPDATE tbl_vistoria SET
					valor_total = $pagamento_total,
					parcelas    = $pagamento_numero_parcelas,
					desconto    = $pagamento_desconto,
					multa       = $pagamento_multa,
					observacao  = '$observacao_historio_aux'
				WHERE vistoria = $vistoria
				AND extrato = $extrato ";
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		#echo nl2br($sql);
		#echo "<br>";

		$sql = "SELECT lancamento
				FROM tbl_lancamento
				WHERE fabrica = $login_fabrica
				AND descricao ILIKE  '%Vistoria de Peças%'
				AND debito_credito ='D'";
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (pg_num_rows($res)>0){
			$lancamento = pg_fetch_result ($res,0,lancamento);
		}else{
			$sql = "SELECT lancamento
					FROM tbl_lancamento
					WHERE fabrica      = $login_fabrica
					AND debito_credito = 'D'
					LIMIT 1";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (pg_num_rows($res)>0){
				$lancamento = pg_fetch_result ($res,0,lancamento);
			}else{
				$msg_erro .= " Sem lançamento de débito cadastrado.";
			}
		}

		$sql = "SELECT TO_CHAR(CURRENT_DATE,'YYYY-MM-01') AS futuro";
		$res = pg_query ($con,$sql);
		$data_futuro = pg_fetch_result ($res,0,futuro);

		for ($j=0; $j< $pagamento_numero_parcelas; $j++){

			$sql = "INSERT INTO tbl_extrato_lancamento(
						posto         ,
						fabrica       ,
						lancamento    ,
						descricao     ,
						valor         ,
						competencia_futura,
						debito_credito,
						historico,
						admin
					) VALUES (
						$posto         ,
						$login_fabrica ,
						$lancamento    ,
						'Vistoria de Peças - Inspetores',
						'$valor_parcela',
						'$data_futuro',
						'D',
						'$observacao_historio',
						$login_admin
					)";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			#echo nl2br($sql);
			#echo "<br>";

			$sql = "SELECT TO_CHAR('$data_futuro'::DATE + INTERVAL '1 month','YYYY-MM-DD') AS futuro";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$data_futuro = pg_fetch_result ($res,0,futuro);
		}

		#Zera as pendencias do posto após pagamento
		$sqlReset = "UPDATE tbl_faturamento_item SET
							qtde_inspecionada = qtde
						WHERE (faturamento,faturamento_item,peca,qtde)
						IN (
						SELECT
							tbl_faturamento_item.faturamento,
							tbl_faturamento_item.faturamento_item,
							tbl_faturamento_item.peca,
							tbl_faturamento_item.qtde
						FROM tbl_faturamento
						JOIN tbl_faturamento_item USING(faturamento)
						JOIN tbl_peca             USING(peca)
						WHERE tbl_faturamento.fabrica = $login_fabrica
							AND tbl_faturamento.posto = $posto
							AND tbl_faturamento.extrato_devolucao = $extrato
							AND tbl_faturamento.cfop  ~'5949|6949'
							AND tbl_faturamento_item.qtde_inspecionada IS NOT NULL
							";

		#Vistoria para os postos abaixo é válido para todas as peças
		if ($extrato > 240000 AND array_search($posto, $postos_permitidos_novo_processo)>0){
			$sqlReset .=" AND tbl_peca.produto_acabado       IS NOT TRUE ";
		}else{
			$sqlReset .=" AND tbl_peca.devolucao_obrigatoria IS NOT TRUE
						 AND tbl_peca.produto_acabado       IS NOT TRUE
						 ";
		}
		$sqlReset .=")";
		#echo nl2br($sqsqlResetl);
		#echo "<br>";
		$resReset = pg_query ($con,$sqlReset);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_query ($con,"COMMIT TRANSACTION");
		//$resX = pg_query ($con,"ROLLBACK TRANSACTION");
		$msg = "Gravado com sucesso!";
	}else{
		$resX = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "auditoria";
$title = "Vistoria de Peças";

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

.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
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

.disabled{
	background-color: #FDD6CC;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

a:link{
	color:#000000;
	font-weight:bold;
	text-decoration:underline;
}

a:visited {
	color:#FFFFFF;
	font-weight:bold;
	text-decoration:underline;
}

a:hover {
	color:#FFFFFF;
	font-weight:bold;
	text-decoration:none;
}

#btn_resumo{
	font-size:11px;
	width:170px;
	cursor:pointer;
}
</style>

<script type="text/javascript" src="js/jquery-latest.pack.js"></script>

<script language='JavaScript'>

function verificarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value == 'NaN') {
		campo.value = '';
	}
	if (campo.value <0) {
		campo.value = 0;
	}
}

function checkarItem(campo, input_qtde_inpecionada, input_qtde){
	var inspecao = document.getElementById(input_qtde_inpecionada);
	var qtde     = document.getElementById(input_qtde).value;

	if (campo.checked){
		inspecao.value=qtde;
		inspecao.disabled = false;

	}else{
		inspecao.value='';
		inspecao.disabled = true;
	}

}

function verificarMes(selecionado,extrato){

	if (selecionado=='SIM'){
		//$("input[@rel=extrato_"+extrato+"]").attr({disabled: false});
		//$("input[@rel=extrato_"+extrato+"]").removeClass('disabled');
		$("input[@rel=extrato_"+extrato+"]").each(
				function (){
					$(this).attr({disabled: false});
					$(this).val( $(this).next("input").val() );
					$(this).removeClass('disabled');
				}
			);
	}
	if (selecionado=='NAO'){
		/*
		$("input[@rel=extrato_"+extrato+"]").attr({disabled: true});
		$("input[@rel=extrato_"+extrato+"]").val('');
		$("input[@rel=extrato_"+extrato+"]").addClass('disabled');
		*/
		$("input[@rel=extrato_"+extrato+"]").each(
				function (){
					$(this).attr({disabled: true});
					$(this).val('');
					$(this).addClass('disabled');
				}
			);
	}

	/*
	$("input[@rel=extrato_"+extrato+"]").each(
			function(){

					//if (!this.checked){
						//this.click();
					//}
					//if (this.disabled){
						this.disabled = false;
					//}
				}
				if (selecionado=='NAO'){
					//if (this.checked){
						//this.click();
					//}
					//if (!this.disabled){
						this.disabled = true;
					//}
				}
			}
	);
	*/
}

</script>

<p>

<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='100%'><input type='button' value='Pesquisar Novo Posto' onclick="window.location='lgr_vistoria.php'"></td>
</tr>
</table>

<div id='loading'></div>

<? if ($resumo!="1" OR 1==1) { ?>
<br>
<table width="700" border="0" align="center" class="texto_avulso">
	<tr>
		<td><img src='imagens/info.png' align='absmiddle'> &nbsp; <u>Novo procedimento:</u> A vistoria agora é por MÊS. Sequência da vistoria:
		<ol style='padding-left:50px; text-align:left'>
			<li>Selecione o mês que deseja fazer a vistoria</li>
			<li>Confirme a quantidade de cada peça vistoriada</li>
			<li>Clique em RESUMO PEÇAS FALTANTES e preencha com as informações de débito se houve peças faltantes</li>
		</ol>
		<p>* Se houver necessidade de não vistoriar determinada peça, desmarque a peça. Assim é possível vistoriar a peça posteriormente.</p>
		</td>
	</tr>
</table>
<br>
<? } ?>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="700" border="0" align="center" class="msg_erro">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<? if (strlen($msg) > 0) { ?>
<br>
<table width="700" border="0" align="center" class="menu_top">
	<tr>
		<td><?echo $msg ?></td>
	</tr>
</table>
<? } ?>

<form name='frm_posto' method='GET' action='<? echo $PHP_SELF ?>?'>
<input type='hidden' name='posto'   value='<? echo $posto; ?>'>

<?
	if ($resumo == "1"){
		$colspan_1 = 3;
		$colspan_2 = 5;
	}else{
		$colspan_1 = 2;
		$colspan_2 = 4;
	}

	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND posto     = $posto";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res)>0){
		$posto_codigo = pg_fetch_result($res,0,codigo_posto);
		$posto_nome   = pg_fetch_result($res,0,nome);
	}

	# Vistoria é sempre os últimos três extratos
	$sql = "SELECT extrato,
				TO_CHAR(data_geracao,'DD/MM/YYYY') AS data_geracao
			FROM tbl_extrato
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto
			ORDER BY tbl_extrato.data_geracao DESC
			LIMIT 3";
	$res = pg_query ($con,$sql);
	$numero_de_extrato = pg_num_rows($res);
	$select_mes = "<select name='extrato' style='width:300px' onChange='this.form.submit()'>";
	$select_mes .= "<option value=''></option>";
	for ($i=0; $i<$numero_de_extrato; $i++){
		$x_extrato      = pg_fetch_result($res,$i,extrato);
		$x_data_geracao = pg_fetch_result($res,$i,data_geracao);
		$selected = "";
		if ($extrato == $x_extrato){
			$mes_selecionado = $i+1;
			$extrato_data    = $x_data_geracao;
			$selected        = " SELECTED ";
		}
		$select_mes .= "<option value='".$x_extrato."' $selected>Mês ".($i+1)." - &nbsp;&nbsp;".$x_data_geracao." &nbsp;&nbsp;&nbsp;(Extrato Nº ".$x_extrato.")</option>";
	}
	$select_mes .= "</select>";
?>



<table border='0' cellspacing='0' cellpadding='3' width='700' align='center' class='formulario'>

<caption style='background-color:#596D9B;color:white;font-size:14px;padding:3px'><b>Vistoria de Peças</b></caption>

<thead>
<tr>
<td align='left' style='padding-left:230px;' width='100'><b>Posto</b><br><?=$posto_codigo?></td>
<td align='left' colspan='$colspan_1'><b>Nome</b><br><?=$posto_nome?></td>
</tr>
</thead>

<tbody>
<tr>
	<td align='center' colspan='2' height='50px'>Selecione o mês da Vistoria<br><?=$select_mes?></td>
</tr>
</thead>

</thead>
</table>
</form>
<br />
<?
	# Pega a data do ultimo extrato
	$sql = "SELECT TO_CHAR(data_geracao,'MM-YYYY') AS data_geracao,
			TO_CHAR(data_geracao,'DD/MM/YYYY') AS data_extrato
			FROM tbl_extrato
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto
			ORDER BY tbl_extrato.data_geracao DESC
			LIMIT 1";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res)>0){
		$_MES_DO_EXTRATO = pg_fetch_result($res,0,data_geracao);
		$data_extrato = pg_fetch_result($res,0,data_extrato);
	}

?>

<?
if (strlen($extrato)>0){

	$sql = "SELECT vistoria FROM tbl_vistoria WHERE extrato = $extrato ";
	$res = pg_query ($con,$sql);
	$vistoria_feita=0;
	if (pg_num_rows($res) > 0){
		$vistoria_feita = 1;
	}

?>
<form name='frm_vistoria' method='POST' action='<? echo $PHP_SELF ?>?'>
<input type='hidden' name='posto'   value='<? echo $posto; ?>'>
<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
<table border='0' cellspacing='1' cellpadding='3' width='700' align='center' class='tabela'>


<tr align='center' >
<td colspan='<?=$colspan_2?>' style="border:0px;">&nbsp;</td>
</tr>
<tr align='center' class='titulo_tabela'>
<td colspan='<?=($colspan_2-1)?>' align='left'  style='font-size:14px'>
<b>Peças para Vistoria - Mês <?=$mes_selecionado?></b> <br><span style='font-size:14px'>Extrato Nº <?=$extrato?> - <?=$extrato_data?> </span>
</td>
<?
	if ($resumo=="1"){
		echo "<td align='left' ><b>Peças</b><br><a href='$PHP_SELF?posto=$posto&extrato=$extrato'>TODAS PEÇAS</a></td>\n";
	}else{
		echo "<td align='left' ><b>Resumo</b><br><input type='button' id='btn_resumo' value='RESUMO PEÇAS FALTANTES' onclick=\"window.location='$PHP_SELF?posto=$posto&extrato=$extrato&resumo=1'\"></td>\n";
	}
?>
</tr>

<tr align='center' class='titulo_coluna'>
<td><b>Código</b></td>
<td><b>Descrição</b></td>
<?	if ($resumo == "1"){?>
		<td><b>Preço</b></td>
		<td><b>Qtde</b></td>
		<td><b>Total</b></td>
<?}else{?>
		<td><b>Qtde</b></td>
		<td><b>Qtde. Vistoriada</b></td>
<?}?>
</tr>

<?
	$data = DateTime::createFromFormat("d/m/Y",$data_extrato);
	$data_corte = DateTime::createFromFormat("d/m/Y","01/09/2018");

	if ($login_fabrica == 3 and $data >= $data_corte) {
		$faturamento_emissao_cond = " AND tbl_faturamento.emissao >= '2018-01-01' ";
	}

	# A variavel $_MES_DO_EXTRATO serve para armazenar o mes e ano do ultimo extrato.
	# Serve para controlar as notas fiscais inspecionadas.
	$sql = "SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_peca.devolucao_obrigatoria,
				SUM (tbl_faturamento_item.qtde)                   AS qtde,
				SUM (tbl_faturamento_item.qtde_inspecionada)      AS qtde_inspecionada,
				SUM (tbl_faturamento_item.qtde_inspecionada_real) AS qtde_inspecionada_real
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING (faturamento)
			JOIN tbl_peca             USING (peca)
			WHERE tbl_faturamento.fabrica             = $login_fabrica
				AND tbl_faturamento.posto             = $posto
				AND tbl_faturamento.extrato_devolucao = $extrato
				AND tbl_faturamento.cfop  ~'5949|6949'
				/* HD 15735 => AND (tbl_faturamento_item.data_inspecao IS NULL OR (tbl_faturamento_item.data_inspecao IS NOT NULL AND TO_CHAR(tbl_faturamento_item.data_inspecao,'MM-YYYY') = '$_MES_DO_EXTRATO')) */
				";

	#Vistoria para os postos abaixo é válido para todas as peças
	if (($extrato > 240000 AND array_search($posto, $postos_permitidos_novo_processo)>0) or $login_fabrica == 43){
		$sql .=" AND tbl_peca.produto_acabado       IS NOT TRUE ";
	}else{
		$sql .=" AND tbl_peca.devolucao_obrigatoria IS NOT TRUE
				 AND tbl_peca.produto_acabado       IS NOT TRUE ";
	}

	if ($resumo=="1"){
		$sql .=" AND tbl_faturamento_item.qtde_inspecionada IS NOT NULL
				 AND tbl_faturamento_item.qtde - tbl_faturamento_item.qtde_inspecionada > 0";
	}

	$sql .=" $faturamento_emissao_cond
			GROUP BY
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.devolucao_obrigatoria,
				tbl_peca.produto_acabado,
				tbl_peca.ipi
			ORDER BY tbl_peca.descricao";
	$resX = pg_query ($con,$sql);
	//echo nl2br($sql);

	$notas_fiscais = array();
	$qtde_peca  = 0;
	$base_icms  = 0;
	$base_ipi   = 0;
	$valor_icms = 0;
	$valor_ipi  = 0;
	$total_nota = 0;
	$y=0;

	$qtde_itens = pg_num_rows ($resX);

	for ($x = 0 ; $x < $qtde_itens ; $x++) {

		$y++;

		$peca                = pg_fetch_result ($resX,$x,peca);
		$peca_referencia     = pg_fetch_result ($resX,$x,referencia);
		$peca_descricao      = pg_fetch_result ($resX,$x,descricao);
		$peca_produto_acabado= pg_fetch_result ($resX,$x,produto_acabado);
		$peca_devolucao_obrigatoria = pg_fetch_result ($resX,$x,devolucao_obrigatoria);

		#$aliq_icms           = pg_fetch_result ($resX,$x,aliq_icms);
		#$aliq_ipi            = pg_fetch_result ($resX,$x,aliq_ipi);
		//$preco               = pg_fetch_result ($resX,$x,preco);
		//$total               = pg_fetch_result ($resX,$x,total);
		$qtde                = pg_fetch_result ($resX,$x,qtde);
		$qtde_inspecionada   = pg_fetch_result ($resX,$x,qtde_inspecionada);

		$lista_de_pecas .= $peca_referencia." - ".$peca_descricao." (Qtde: $qtde)\n ";

		$sql_preco = "	SELECT preco
						FROM tbl_tabela_item
						WHERE tabela IN (
							SELECT tabela
							FROM tbl_posto_linha
							WHERE posto = $posto
							AND linha IN (
								SELECT DISTINCT linha
								FROM tbl_peca
								JOIN tbl_lista_basica using(peca)
								JOIN tbl_produto  on tbl_lista_basica.produto = tbl_produto.produto
								WHERE peca = $peca
							)
						)
						AND peca = $peca
						ORDER BY tabela DESC
						LIMIT 1;
					";
		$resPreco = pg_query ($con,$sql_preco);

		$preco = 0;
		$total = 0;

		if (pg_num_rows ($resPreco)>0){
			$preco = pg_fetch_result ($resPreco,0,preco);
			$total = $preco * $qtde;
		}

		$qtde_peca_pendente = $qtde - $qtde_inspecionada;
		if (strlen($qtde_peca_pendente)==0){
			$qtde_peca_pendente = 0;
		}

		if ($resumo=="1"){

			$qtde = $qtde_peca_pendente;
			$total = $preco * $qtde;

			$total_nota += $total;

			if (strlen($aliq_icms)==0){
				$aliq_icms = 0;
			}
			if (strlen($aliq_ipi)==0){
				$aliq_ipi = 0;
			}

			if ($aliq_icms>0){
				$base_icms += $total;
				$valor_icms += $preco * $qtde * $aliq_icms/100;
			}

			if ($aliq_ipi>0){
				$base_ipi  += $total;
				$valor_ipi += $preco * $qtde * $aliq_ipi/100;
			}
		}

		$cor = ($y % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
		echo "<td align='left'>";
		echo "$peca_referencia";
		echo "</td>\n";
		echo "<td align='left'>$peca_descricao</td>\n";

		$desabilitar = "";
		if ( $qtde - $qtde_inspecionada == 0){
			$desabilitar = " DISABLED style='background-color:#DFDFDF'";
		}
		if ( strlen($qtde_inspecionada)>0 AND $qtde_inspecionada == 0 ){
			$desabilitar = " style='background-color:#FECBCB'";
		}

		$qtde_cor = $qtde;
		if ($qtde_cor == 0){
			$qtde_cor = "<span style='color:red'>$qtde</span>";
		}

		#PRovisorio, para bloquear alteração de qtde depois que inspecionado
		if ( strlen($qtde_inspecionada)>0){
			$desabilitar = " DISABLED style='background-color:#DFDFDF'";
		}

		if ($qtde_inspecionada == $qtde){
			$cor_celula = "#D8FEDA";
		}elseif (($qtde_inspecionada=="0" OR $qtde_inspecionada < $qtde) AND strlen($qtde_inspecionada)>0){
			$cor_celula = "#FDE7DF";
		}else{
			$cor_celula = "#FAE7A5";
		}

		if (strlen($qtde_inspecionada)==0){
			$qtde_inspecionada = $qtde;
		}

		if ($resumo == "1"){
			echo "<td align='right'>".number_format($preco,2,",",".")."</td>\n";
			echo "<td align='center'>$qtde</td>\n";
			//echo "<td align='center'>$aliq_icms</td>\n";
			//echo "<td align='center'>$aliq_ipi</td>\n";
			echo "<td align='right'>".number_format($total,2,",",".")."</td>\n";
		}else{

			$peca_selecionada = " CHECKED ";
			if (isset($_POST["linha_peca_check_$y"])){
				if ($_POST["linha_peca_check_$y"]=='t'){
					$peca_selecionada = " CHECKED ";
				}else{
					$peca_selecionada = "";
					$qtde_inspecionada = "";
				}
			}else{
				$peca_selecionada = " CHECKED ";
			}

			echo "<td align='center'>$qtde_cor</td>\n";
			echo "<td align='center' bgcolor='#FAE7A5'>\n";
			//echo "<input type='hidden' name='linha_extrato_$y'   value='$extrato_numero'>\n";
			echo "<input type='hidden' name='linha_peca_$y'      value='$peca'>\n";
			echo "<input type='hidden' name='linha_preco_$y'     value='$preco'>\n";
			echo "<input type='text'   name='linha_qtde_insp_$y' value='$qtde_inspecionada' size='4' maxlength='4'  id='linha_qtde_insp_$y' $desabilitar >\n";
			echo "<input type='hidden' name='linha_qtde_$y'  rel='qtde'    value='$qtde'>\n";

			echo "<input type='checkbox' name='linha_peca_check_$y' value='t' ".$peca_selecionada." onClick=\"javascript:checkarItem(this,'linha_qtde_insp_$y','linha_qtde_$y') \" $desabilitar>";
			echo "</td>\n";
		}

		echo "</tr>\n";
		flush();
	}

	if ($qtde_itens==0){
		echo "<tr>\n";
		echo "<td colspan='$colspan_2'>";
		if ($resumo==1){
			if ($vistoria_feita){
				echo "Não há peças mais peças pendentes.";
			}else{
				echo "Não foi feito a vistoria";
			}
		}else{
			echo "Não há peças para inspeção ou peças já inspecionadas.";
		}

		echo "</td>";
		echo "</tt>";
	}

	if ( $resumo == "1" AND $x > 0 ){
		$total_nota   = $total_nota;
		$total_geral += $total_nota;
		echo "<tr>\n";
		echo "<td colspan='$colspan_2'>";
		echo "<table  border='0' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='100%' >";
			echo "<tr>";
			echo "<td align='right'>Total <br><b>".number_format($total_nota,2,",",".")."</b></td>";
			echo "</tr>";
		echo "</table>";
		echo "</td>";
		echo "</tr>\n";
	}

	echo "</table>\n";

	if ($resumo <>"1"){
		echo "<input type='hidden' name='btn_acao' value='gravar'>";
		echo "<input type='hidden' name='qtde_linhas' value='$qtde_itens'>";
		echo "<br><br><center><input type='button' value='Gravar' onclick=\"javascript: if (confirm('Deseja gravar? Os campos preenchidos serão dadas como confirmado a inspeção'))this.form.submit();\"></center>";
	}
}
?>
</form>

<?
	if ( $resumo == "1" AND $total_geral>0){

		echo "<form name='frm_conferencia' method='POST' action='".$PHP_SELF."?resumo=1#pagamento_total'>";
		echo "<input type='hidden' name='posto' value='".$posto."'>";
		echo "<input type='hidden' name='extrato' value='".$extrato."'>";

		if ($btn_acao == "pagamento"){

			$pagamento_total           = trim($_POST['pagamento_total']);
			$pagamento_numero_parcelas = trim($_POST['pagamento_numero_parcelas']);
			$pagamento_desconto        = trim($_POST['pagamento_desconto']);
			$pagamento_multa           = trim($_POST['pagamento_multa']);

			if (strlen($pagamento_numero_parcelas)==0)	{$pagamento_numero_parcelas = 1;}
			if ($pagamento_numero_parcelas < 0)			{$pagamento_numero_parcelas = 1;}
			if (strlen($pagamento_desconto)==0)			{$pagamento_desconto = 0;}
			if ($pagamento_desconto<0)					{$pagamento_desconto = 0;}
			if (strlen($pagamento_multa)==0)			{$pagamento_multa = 0;}
			if ($pagamento_multa < 0)					{$pagamento_multa = 0;}

			if ($pagamento_multa > 20){
				$pagamento_multa = 20;
			}

			if ($pagamento_numero_parcelas == 0){
				$pagamento_numero_parcelas = 1;
			}

			$pagamento_total_geral = $pagamento_total;
			$valor_desconto        = 0;
			$valor_multa           = 0;

			if ($pagamento_total_geral == 0){
				$pagamento_numero_parcelas = 1;
			}

			if ($pagamento_desconto>0){
				if ($pagamento_desconto>100){
					$pagamento_desconto = 100;
				}
				$valor_desconto = $pagamento_total_geral * $pagamento_desconto/100;
				$pagamento_total_geral = $pagamento_total_geral - $valor_desconto;
			}

			if ($pagamento_multa>0){
				$valor_multa = $pagamento_total_geral * $pagamento_multa/100;
				$pagamento_total_geral = $pagamento_total_geral + $valor_multa;
			}

			$valor_parcela = 0;
			if ($pagamento_total_geral>0){
				$valor_parcela = $pagamento_total_geral / $pagamento_numero_parcelas;
				$valor_parcela = number_format($valor_parcela,2,",",".");
			}

			echo "<input type='hidden' name='btn_acao'                  value='pagamento_confirmar'>";
			echo "<input type='hidden' name='pagamento_total'           value='$pagamento_total'>";
			echo "<input type='hidden' name='pagamento_total_geral'     value='$pagamento_total_geral'>";
			echo "<input type='hidden' name='pagamento_numero_parcelas' value='$pagamento_numero_parcelas'>";

			echo "<input type='hidden' name='pagamento_desconto' value='$pagamento_desconto'>";
			echo "<input type='hidden' name='pagamento_multa' value='$pagamento_multa'>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='450' >\n";
			echo "<caption style='background-color:#596D9B;color:white;font-size:14px;padding:3px'><b>Confirmação</b></caption>\n";

			echo "<tbody>\n";
			echo "<tr>\n";
			echo "<td align='left'><b>Valor de Peças</b></td>\n";
			echo "<td align='center'>(+)</td>\n";
			echo "<td align='right'>R$ ".number_format($pagamento_total,2,",",".")."</td>\n";
			echo "</tr>\n";

			echo "<tr background-color:'#C9DDF5'>\n";
			echo "<td align='left'><b>Desconto</b></td>\n";
			echo "<td align='center'>(-)</td>\n";
			echo "<td align='right'>".$pagamento_desconto." % <span style='font-size:10px'>(R$ ".number_format($valor_desconto,2,",",".").")</span></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td align='left'><b>Multa</b></td>\n";
			echo "<td align='center'>(+)</td>\n";
			echo "<td align='right'>".$pagamento_multa." % <span style='font-size:10px'>(R$ ".number_format($valor_multa,2,",",".").")</span></td>\n";
			echo "</tr>\n";

			echo "<tr style='background-color:#EAF1FB'>\n";
			echo "<td align='left'><b>Total</b></td>\n";
			echo "<td align='center'>(=)</td>\n";
			echo "<td align='right'>R$ ".number_format($pagamento_total_geral,2,",",".")."</td>\n";
			echo "</tr>\n";

			echo "<tr style='background-color:#EAF1FB'>\n";
			echo "<td align='left'><b>Número de Parcelas</b></td>\n";
			echo "<td align='center'></td>\n";
			echo "<td align='right'>".$pagamento_numero_parcelas." x</td>\n";
			echo "</tr>\n";

			echo "</tbody>\n";
			echo "</table>";

			$sql = "SELECT TO_CHAR(CURRENT_DATE,'YYYY-MM') AS futuro";
			$res = pg_query ($con,$sql);
			$data_futuro = pg_fetch_result ($res,0,futuro);

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='450' >\n";
			echo "<caption style='background-color:#919EBF;color:white;font-size:10px;padding:2px'><b>Parcelas</b></caption>\n";

			echo "<tbody>\n";
			echo "<tr style='background-color:#C9DDF5'>\n";
			echo "<td align='center'><b>Parcela</b></td>\n";
			echo "<td align='center'><b>Data</b></td>\n";
			echo "<td align='center'><b>Valor</b></td>\n";
			echo "</tr>\n";

			for ($j=0; $j< $pagamento_numero_parcelas; $j++){

				$aux_data_futuro = $data_futuro."-01";

				$sql = "SELECT TO_CHAR('$aux_data_futuro'::DATE + INTERVAL '1 month','YYYY-MM') AS futuro, TO_CHAR('$aux_data_futuro'::DATE + INTERVAL '1 month','MM/YYYY') AS futuro_2";
				$res = pg_query ($con,$sql);
				$data_futuro   = pg_fetch_result ($res,0,futuro);
				$data_futuro_2 = pg_fetch_result ($res,0,futuro_2);

				echo "<tr>\n";
				echo "<input type='hidden' name='pagamento_parcela_$j' value='$data_futuro_2'>";
				echo "<input type='hidden' name='pagamento_parcela_valor_$j' value='".number_format($valor_parcela,2,".","")."'>";
				echo "<td align='left'><b>Parcela Nº ".($j+1)." </b></td>\n";
				echo "<td align='center'>01/".$data_futuro_2."</td>\n";
				echo "<td align='right'>".$valor_parcela."</td>\n";
				echo "</tr>\n";
			}

			echo "</tbody>\n";
			echo "</table>";
			echo "<br>";
			echo "<p>";
			echo "<center>Observação que será incluída no lançamento:</center>";
			echo "<textarea name='lista_de_pecas' rows='6' cols='60' readonly='readonly'>";
			echo "Lançamento avulso referente as peças: \n";
			echo $lista_de_pecas;
			echo "</textarea>";
			echo "</p>";
			echo "<p>";
			echo "<center>Observação:</center>";
			echo "<input type='text' name='observacao_historio' value='' size='60' maxlength='250' class='frm'>";
			echo "</p>";
			echo "<br>";
			echo "<input type='button' value='Voltar' onclick=\"javascript: window.location='$PHP_SELF?posto=$posto&extrato=$extrato&resumo=1#pagamento_total'\"> ";
			echo "&nbsp;&nbsp;&nbsp;";
			echo "<input type='button' value='Confirmar' onclick=\"javascript: if (confirm('Deseja confirmar o lançamento avulso?'))this.form.submit();\"> ";
			echo "<br>";

		}else{

			echo "<input type='hidden' name='pagamento_total'           value='$total_geral'>";
			echo "<input type='hidden' name='btn_acao'                  value='pagamento'>";
			echo "<br><br>";
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='450'  align='center' >\n";
			echo "<caption style='background-color:#596D9B;color:white;font-size:14px;padding:3px'><b>Pagamento</b></caption>\n";

			echo "<tbody>\n";
				echo "<tr background-color:'#C9DDF5'>\n";
				echo "<td align='left'><b>Total</b></td>\n";
				echo "<td align='right'>".number_format($total_geral,2,",",".")."</td>\n";
				echo "<td align='left'>R$ </td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<td align='left'><b>Parcelamento do Débito</b></td>\n";
				echo "<td align='right'><input type='text' class='frm' value='' size='10' name='pagamento_numero_parcelas' onBlur='verificarNumero(this);'></td>\n";
				echo "<td align='left'>Vezes</td>\n";
				echo "</tr>\n";
				echo "<tr background-color:'#C9DDF5'>\n";
				echo "<td align='left'><b>Desconto</b></td>\n";
				echo "<td align='right'><input type='text' class='frm' value='' size='10' name='pagamento_desconto' onBlur='verificarNumero(this);if (this.value>100) this.value=100'></td>\n";
				echo "<td align='left'>%</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<td align='left'><b>Multa</b></td>\n";
				echo "<td align='right'><input type='text' class='frm' value='' size='10' name='pagamento_multa' onBlur='verificarNumero(this);if (this.value>20) this.value=20'></td>\n";
				echo "<td align='left'>% (Máximo 20%)</td>\n";
				echo "</tr>\n";
			echo "</tbody>\n";
			echo "</table>";
			echo "<input type='button' value='Continuar' onclick=\"javascript: if (confirm('Deseja continuar? Será mostrado os valores e será solicitado uma confirmação antes que o lançamento avulso seja efetuado.'))this.form.submit();\"> ";
		}
		echo "</form>";
	}
?>


<? include "rodape.php"; ?>
