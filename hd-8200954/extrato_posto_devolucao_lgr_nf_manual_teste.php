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


$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$ok_aceito="nao";
$tem_mais_itens='nao';
$contadorrr=0;



$pecas_pendentes = trim($_GET['pendentes']);
if (strlen($pecas_pendentes)==0)
	$pecas_pendentes = trim($_POST['pendentes']);




$ok_aceito = trim($_POST['ok_aceito']);
if ($ok_aceito=='Concordo') 
	$numero_linhas = trim($_POST['qtde_linha']);

$btn_acao = trim($_POST['botao_acao']);

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {

	$sql_update = "
			UPDATE tbl_extrato_lgr 
			SET qtde_pedente_temp = null
			WHERE extrato=$extrato";
	$res_update = pg_exec ($con,$sql_update);
	$msg_erro .= pg_errormessage($con);

	$numero_linhas   = trim($_POST['qtde_linha']);
	$qtde_pecas      = trim($_POST['qtde_pecas']);
	$pecas_pendentes = trim($_POST['pendentes']);

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=1;$i<=$qtde_pecas;$i++){
		$extrato_lgr	= trim($_POST["item_$i"]);
		$peca_tem		= trim($_POST["peca_tem_$i"]);
		$peca			= trim($_POST["peca_$i"]);
		$qtde_pecas_devolvidas = trim($_POST["$extrato_lgr"]);

		if ($peca_tem>$qtde_pecas_devolvidas){
			$diminuiu='sim';
		}

		if (strlen($qtde_pecas_devolvidas)>0){
				$sql_update = "UPDATE tbl_extrato_lgr 
						SET qtde_pedente_temp = $qtde_pecas_devolvidas
						WHERE extrato=$extrato
						AND peca=$peca";
				$res_update = pg_exec ($con,$sql_update);
				$msg_erro .= pg_errormessage($con);
		}
		else{
			//$msg_erro="Informe a quantidade de pe�as que ser�o devolvidas!";
		}
		if (strlen($msg_erro)>0) break;
	}

	if (strlen($msg_erro) == 0) {
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas = trim($_POST['qtde_pecas']);
	$numero_linhas = trim($_POST['qtde_linha']);
	$numero_de_notas = trim($_POST['numero_de_notas']);
	$numero_de_notas_tc = trim($_POST['numero_de_notas_tc']); # para a telecontrol
	$data_preenchimento = date("Y-m-d");
	$array_notas = array();
	$array_notas_tc = array();

	$resX = pg_exec ($con,"BEGIN TRANSACTION");


	for($i=0;$i<$numero_de_notas;$i++){
			$nota_fiscal = trim($_POST["nota_fiscal_$i"]);

			if($login_posto == 4311) {
				/*IGOR- Copiei do embarque_nota_fiscal.php - Para n�o gerar nota errada*/
				# Fabio Nowaki - 24/01/2008
				$sql = "SELECT MAX (nota_fiscal::integer) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = $login_posto AND nota_fiscal::integer < 111111 ";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$nota_fiscal = pg_result ($res,0,0);

				if (strlen ($nota_fiscal) == 0) {
					$nota_fiscal = "000000";
				}

				$nota_fiscal = $nota_fiscal + 1 ;
				$nota_fiscal = "000000" . $nota_fiscal;
				$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);
			}

		
			#echo "Numero de notas: $nota_fiscal <br> $msg_erro";
			if (strlen($nota_fiscal)==0){
				$msg_erro='Digite todas as notas fiscais!';
				break;
			}

			$nota_fiscal = str_replace(".","",$nota_fiscal);
			$nota_fiscal = str_replace(",","",$nota_fiscal);
			$nota_fiscal = str_replace("-","",$nota_fiscal);
			$nota_fiscal = str_replace("/","",$nota_fiscal);

			$nota_fiscal = ltrim ($nota_fiscal, "0");

			if (!is_numeric($nota_fiscal)) {
				$msg_erro .= "O n�mero das notas fiscais devem ter somente n�meros!";
			}

			if ($nota_fiscal==0) {
				$msg_erro .= "O n�mero das notas fiscais devem ter somente n�meros!";
			}

			/* HD 49206 */
			if (strlen($msg_erro)==0){
				$sql = "SELECT nota_fiscal, TO_CHAR(emissao,'DD/MM/YYYY') as emissao
						FROM tbl_faturamento
						WHERE fabrica      = $login_fabrica
						AND   distribuidor = $login_posto
						AND   nota_fiscal  = '$nota_fiscal'
						AND   posto        = 13996";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res)>0){
					$xxemissao = pg_result ($res,0,emissao);
					$msg_erro = "Foi constatado que a $nota_fiscal j� foi emitida por seu posto em $xxemissao. N�o pode haver duplicidade.";
				}
			}

			array_push($array_notas,$nota_fiscal);

			$total_nota = trim($_POST["id_nota_$i-total_nota"]);
			$base_icms  = trim($_POST["id_nota_$i-base_icms"]);
			$valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
			$base_ipi   = trim($_POST["id_nota_$i-base_ipi"]);
			$valor_ipi  = trim($_POST["id_nota_$i-valor_ipi"]);
			$cfop       = trim($_POST["id_nota_$i-cfop"]);
			$movimento  = trim($_POST["id_nota_$i-movimento"]);

			//$linha_nota = trim($_POST["id_nota_$i-linha"]);

			$qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);

			if (strlen($cfop)>0){
				$cfop = " '$cfop' ";
			}else{
				$cfop = " NULL ";
			}

			if (strlen($msg_erro)==0){
				$sql = "INSERT INTO tbl_faturamento		  
						(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs,cfop, movimento)
						VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',13996,$login_posto,$total_nota,'$nota_fiscal','2','Devolu��o de Garantia', $base_icms, $valor_icms, $base_ipi, $valor_ipi, $extrato, 'Devolu��o de pe�as do posto para � F�brica',$cfop,'$movimento')";
				$res = pg_exec ($con,$sql);

				$sql = "SELECT CURRVAL ('seq_faturamento')";
				$resZ = pg_exec ($con,$sql);
				$faturamento_codigo = pg_result ($resZ,0,0);

				#echo "$faturamento_codigo - ";
				for($x=1;$x<=$qtde_peca_na_nota;$x++){

					$lgr                = trim($_POST["id_item_LGR_$x-$i"]);
					$peca               = trim($_POST["id_item_peca_$x-$i"]);
					$peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
					$peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
					$peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
					$peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
					$peca_total_item    = trim($_POST["id_item_total_$x-$i"]);


					$sql_update = "UPDATE tbl_extrato_lgr 
							SET qtde_nf = (CASE WHEN qtde_nf IS NULL THEN 0 ELSE qtde_nf END) + $peca_qtde_total_nf
							WHERE extrato=$extrato
							AND peca=$peca";
					


					if ($ip == "201.92.1.225"){
						#$msg_erro .= "<br>".nl2br($sql_update)."<br>id_item_peca_$x-$i = $peca<br>";
					}


					$res_update = pg_exec ($con,$sql_update);
					$msg_erro .= pg_errormessage($con);

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
								WHERE tbl_faturamento.fabrica = $login_fabrica
								AND   tbl_faturamento.posto   = $login_posto
								AND   tbl_faturamento.extrato_devolucao = $extrato
								AND   tbl_faturamento_item.peca=$peca
								AND   tbl_faturamento_item.preco=$peca_preco
								AND   tbl_faturamento.distribuidor IS NULL
								AND   tbl_faturamento_item.aliq_icms>0
								ORDER BY tbl_faturamento.nota_fiscal";
							
					$resNF = pg_exec ($con,$sql_nf);
					$qtde_peca_inserir=0;
					if (pg_numrows ($resNF)==0){
						$msg_erro .= "Erro.";
						# Nelson pediu para nw mandar mais email HD 2937
						$email_origem  = "helpdesk@telecontrol.com.br";
						$email_destino = 'fabio@telecontrol.com.br';
						$assunto       = "Extrato com erro";
						$corpo.="MENSAGEM AUTOM�TICA. N�O RESPONDA A ESTE E-MAIL \n\n $msg_erro \n $sql_nf";
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
							

							$qtde_peca_inserir += $peca_qtde;

							if ($qtde_peca_inserir > $peca_qtde_total_nf){
								$peca_base_icms  = 0;
								$peca_valor_icms = 0;
								$peca_base_ipi   = 0;
								$peca_valor_ipi  = 0;
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
									(faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, linha, base_ipi, valor_ipi,nota_fiscal_origem,sequencia)
									VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_linha, $peca_base_ipi, $peca_valor_ipi,'$peca_nota','$sequencia')";
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							#echo nl2br($sql)."<br><br>";
						}else{
							break; //echo "<br>Break<br>";
						}
					}

				}
			}
	}
	


	if (strlen($msg_erro) == 0) {
		$sql_update = "UPDATE tbl_extrato_lgr 
				SET qtde_pedente_temp = null
				WHERE extrato=$extrato";
		$res_update = pg_exec ($con,$sql_update);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (count(array_unique($array_notas))<>$numero_de_notas){
			$msg_erro .= "Erro: n�o � permitido digitar n�mero de notas iguais. Preencha novamente as notas.";
		}
	}

	#$msg_erro = "teste";
	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		#$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	$nota_fiscal = "";
}

if ($jah_digitado>0 AND $pecas_pendentes!= 'sim'){
	header("location: extrato_posto_devolucao_lgr_itens.php?extrato=$extrato");
	exit();
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

$sql = "SELECT  distinct to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto,
				tbl_faturamento_vistoria.faturamento_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_faturamento_vistoria on tbl_faturamento_vistoria.extrato_devolucao = tbl_extrato.extrato
		WHERE tbl_extrato.extrato = $extrato 
		";
$res       = pg_exec ($con,$sql);
echo "sql: $sql";
$data      = pg_result ($res,0,data);
$periodo   = pg_result ($res,0,periodo);
$nome      = pg_result ($res,0,nome);
$codigo    = pg_result ($res,0,codigo_posto);
$faturamento_posto= pg_result ($res,0,faturamento_posto);


echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";


?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='33%'><a href='<?php echo $PHP_SELF ?>?mao=sim&extrato=<? echo $extrato ?>'>Ver M�o-de-Obra</a></td>
<td align='center' width='33%'><a href='extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>

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

<? if (strlen($numero_linhas) > 0 AND $diminuiu=='sim' AND $ok_aceito!='Concordo') { ?>
	<br>

	<form method='post' action='<? echo $PHP_SELF; ?>#notas_d' name='frm_confirmar' id='frm_confirmar' >
	<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
	<input type='hidden' name='qtde_linha' value='<? echo $numero_linhas; ?>'>
	<input type='hidden' name='ok_aceito' value='Concordo'>
	<input type='hidden' name='pendentes' value='sim'>
	<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="0">
	<TR>
		<TD colspan="10" class="menu_top2" ><div align="center" style='font-size:16px'>
		<b>
		ATEN��O
		</b></div></TD>
	</TR>
	<TR>
		<TD colspan='8' class="menu_top3" style='padding:10px;'>
		As pe�as ou produtos n�o devolvidos neste extrato ser�o apresentadas na tela de consulta de pend�ncias. Caso n�o sejam efetivadas as devolu��es, os itens ser�o cobrados do posto autorizado.
		<br><br>
		</td>
	</tr>
	<TR>
		<TD colspan='8' class="menu_top2" align='center'>
		<center>
		<input type='button' name='ok' value='Concordo' class='frm' onclick="javascript:if (this.value=='Concordo.'){altert('Aguarde submiss�o.');}else{if(confirm('Deseja continuar?')){this.value='Concordo.';document.frm_confirmar.submit();}}">
		<input type='button' value='Voltar' name='voltar' onclick="javascript:
					if(confirm('Deseja voltar?')) window.location='<? echo $PHP_SELF; ?>?extrato=<? echo $extrato; ?>';">
			<? echo ?>
		</center>
		</TD>
	</TR>
	</table>
	</form>
<? exit(); } ?>

<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" >
	<div align="center" style='font-size:16px'>
		<b>
		<?
			if ($pecas_pendentes=="sim") echo "DEVOLU��O PENDENTE";
			else                         echo "ATEN��O";
		?>
		</b>
	</div>
	</TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
		As pe�as ou produtos n�o devolvidos neste extrato ser�o apresentadas na tela de consulta de pend�ncias. 
		Caso n�o sejam efetivadas as devolu��es, os itens ser�o cobrados do posto autorizado.
	<br><br>
	<? //HD 15408 ?>
	<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolu��o nos mesmos valores e impostos, referenciando NF de origem, e postagem da NF de acordo com o cabe�alho de cada nota fiscal.</b>
	</TD>
</TR>
</table>
<br>

<? 

$sql = "UPDATE tbl_faturamento_item 
			SET linha = (
							SELECT tbl_produto.linha FROM tbl_produto 
							JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_faturamento_item.peca = tbl_lista_basica.peca LIMIT 1
			)
		FROM tbl_faturamento
		WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.extrato_devolucao = $extrato";
$res = pg_exec ($con,$sql);

if ($login_fabrica == 3) {
	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			FROM tbl_faturamento
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica 
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";
	$res = pg_exec ($con,$sql);
}

$sql = "SELECT * 
		FROM tbl_posto 
		WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

if ($res_qtde > 0 OR 1==1) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
	echo "<input type='hidden' name='notas_d' value=''>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;

	if($login_fabrica == 3){
		$qtde_for=2;
	}else{
		$qtde_for=4;
	}
	for($xx=1;$xx< $qtde_for ;$xx++) {

		$extrato_devolucao = $extrato;

		$devolucao = " RETORNO OBRIGAT�RIO ";
		$movimento = "RETORNAVEL";
		$pecas_produtos = "PE�AS";
		$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
		$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria = 't'";

		//HD43448
		$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
		$endereco = "Rua Dona Francisca, 12340 - Bairro Pirabeiraba ";
		$cidade   = "Joinville";
		$estado   = "SC";
		$cep      = "89239270";
		$fone     = "(41) 2102-7700";
		$cnpj     = "76492701000742";
		$ie       = "254.861.652";

		$distribuidor = "null";

		$cabecalho  = "<br><br>\n";
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >passou\n";

		$cabecalho .= "<tr align='left'  height='16'>\n";
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
		$cabecalho .= "</td>\n";
		$cabecalho .= "</tr>\n";

		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Natureza <br> <b>Devolu��o de Garantia</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b> (CFOP) </b> </td>\n";
		$cabecalho .= "<td>Emiss�o <br> <b>$data</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);

		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Raz�o Social <br> <b>$razao</b> </td>\n";
		$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
		$cabecalho .= "<td>Inscri��o Estadual <br> <b>$ie</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Endere�o <br> <b>$endereco </b> </td>\n";
		$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
		$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
		$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$topo ="";
		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
		$topo .=  "<thead>\n";
echo "lINHA: $numero_linhas";
		if ($numero_linhas==5000){
			$topo .=  "<tr align='left'>\n";
			$topo .=  "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
			$topo .=  "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
			$topo .=  "</td>\n";
			$topo .=  "</tr>\n";
		}

		$topo .=  "<tr align='center'>\n";
		$topo .=  "<td><b>C�digo</b></td>\n";
		$topo .=  "<td><b>Descri��o</b></td>\n";
		$topo .=  "<td><b>Qtde.</b></td>\n";

		if ($numero_linhas==5000){
			$topo .=  "<td><b>Qtde. Devolu��o</b></td>\n";
		}
		else{
			$topo .=  "<td><b>Pre�o</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";


		if ($numero_linhas!=5000){
			$sql_adicional_peca=" AND tbl_extrato_lgr.qtde_pedente_temp>0";
		}
		else{
			$sql_adicional_peca="";
		}


		$sql = "  SELECT 
					tbl_peca.peca, 
					tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.ipi, 
					tbl_peca.devolucao_obrigatoria,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento_item.qtde as qtde_real,
					tbl_faturamento.cfop,
					tbl_faturamento_item.base_icms AS base_icms, 
					tbl_faturamento_item.valor_icms AS valor_icms,
					tbl_faturamento_item.base_ipi AS base_ipi,
					tbl_faturamento_item.valor_ipi AS valor_ipi
				FROM tbl_faturamento 
				JOIN tbl_faturamento_item on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				JOIN tbl_peca USING (peca)
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND tbl_faturamento.faturamento = $faturamento_posto
				ORDER BY tbl_peca.referencia";
			echo nl2br($sql);
		$notas_fiscais=array();
		$qtde_peca=0;

		if ($ip == '200.228.76.102'){
			echo nl2br($sql);
			#flush();
		}

		$resX = pg_exec ($con,$sql);

		if (pg_numrows ($resX)==0) continue;


		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
		$peca_ant="";
		$qtde_acumulada=0;
		$lista_pecas = array();
		
		$z=0;
		$total_qtde = pg_numrows ($resX);
		for ($x = 0 ; $x < $total_qtde ; $x++) {

			$tem_mais_itens='sim';

			$contador++;
			$item_nota++;
			$z++;

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$peca_preco          = pg_result ($resX,$x,preco);
			$qtde_real           = pg_result ($resX,$x,qtde_real);
			/*$qtde_total_item     = pg_result ($resX,$x,qtde_total_item);
			$qtde_total_nf       = pg_result ($resX,$x,qtde_total_nf);
			$qtde_pedente_temp   = pg_result ($resX,$x,qtde_pedente_temp);
			$qtde_pedente_temp_AUX= pg_result ($resX,$x,qtde_pedente_temp);*/
//			$qtde_restatante     = pg_result ($resX,$x,qtde_restatante);
			$extrato_lgr         = pg_result ($resX,$x,extrato_lgr);
//			$total_item          = pg_result ($resX,$x,total_item);
			$base_icms           = pg_result ($resX,$x,base_icms);
			$valor_icms          = pg_result ($resX,$x,valor_icms);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$base_ipi            = pg_result ($resX,$x,base_ipi);
			$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
			$valor_ipi           = pg_result ($resX,$x,valor_ipi);
			$ipi                 = pg_result ($resX,$x,ipi);
			$cfop                = pg_result ($resX,$x,cfop);
//			$peca_produto_acabado= pg_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria= pg_result ($resX,$x,devolucao_obrigatoria);

			if ($pecas_pendentes=='sim' and 1==2){
				$qtde_total_item    = $qtde_restatante;
				$qtde_pedente_temp  = $qtde_restatante;
			}
			if ($qtde_pedente_temp>$qtde_real AND $numero_linhas!=5000){
				$qtde_pedente_temp=$qtde_real;
			}

			if ($peca_ant==$peca){
				if ($numero_linhas==5000){
					$peca_ant=$peca;
					continue;
				}
				if ($peca_ok==1){
					$peca_ant=$peca;
					$contador--;
					$item_nota--;
					$z--;
					continue;
				}
			}

			if ($peca_ant!=$peca){
				$qtde_acumulada = $qtde_real;
				$peca_ok = 0;
			}else{
				$qtde_acumulada += $qtde_real;
			}

			if ($qtde_acumulada >= $qtde_pedente_temp_AUX){
				$qtde_real = $qtde_pedente_temp_AUX - ($qtde_acumulada - $qtde_real);
				$peca_ok = 1;
			}

			$peca_ant=$peca;

			if (strlen($qtde_pedente_temp)==0){
				$qtde_pedente_temp=$qtde_total_item;
			}

			array_push($lista_pecas,$peca);

			if (1==2){
				$sql_nf = "SELECT tbl_faturamento.nota_fiscal,
								  tbl_faturamento_item.qtde
						FROM tbl_faturamento_item 
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.posto   = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento_item.peca = $peca
						ORDER BY tbl_faturamento.nota_fiscal";
				echo nl2br($sql_nf);
				$resNF = pg_exec ($con,$sql_nf);
				
				if (strlen($qtde_total_nf)==0) $qtde_total_nf=0;

				$qtde_aux=0;
				$qtde_peca=0;

				for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
					if ($qtde_aux<$qtde_total_nf) {
						$qtde_aux += pg_result ($resNF,$y,qtde);
						continue;
					}
					if ($qtde_peca <= $qtde_real){
						$qtde_peca += pg_result ($resNF,$y,qtde);
						array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
					}
					$notas_fiscais = array_unique($notas_fiscais);
					asort($notas_fiscais);
	//				print_r($notas_fiscais);
				}
			}

//			if ($qtde_pedente_temp==0)
//				$preco       =  $total_item;
//			else
//				$preco       =  $total_item / $qtde_total_item;
			
			$total_item  = $peca_preco * $qtde_real;

//			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
//			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

			if ($aliq_icms==0){
				$base_icms=0;
				$valor_icms=0;
			}
			else{
				$base_icms=$total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if ($peca_produto_acabado=='NOT TRUE'){ # se for peca, IPI = 0
				$aliq_ipi=0;
			}

			if (strlen($aliq_ipi)==0) $aliq_ipi=0;

			if ($aliq_ipi==0) 	{
				$base_ipi=0;
				$valor_ipi=0;
			}
			else {
				$base_ipi=$total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

//			if ($base_icms > $total_item) $base_icms = $total_item;
//			if ($aliq_final == 0) $aliq_final = $aliq_icms;
//			if ($aliq_final <> $aliq_icms) $aliq_final = -1;

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;

			if ($x == 0){
				if ($numero_linhas!=5000){
					#$x_cabecalho = str_replace("(CFOP)","$cfop",$cabecalho);
					/* HD 40994 */
					$x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
					echo $x_cabecalho;
				}
				echo $topo;
			}

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>aqui1111 - ";
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

			if ($numero_linhas==5000){
				echo "<td align='center'>$qtde_total_item</td>\n";
				echo "<td align='center' bgcolor='#FAE7A5'>\n
						<input type='hidden' name='item_$contador' value='$extrato_lgr'>\n
						<input type='hidden' name='peca_tem_$contador' value='$qtde_total_item'>\n
						<input type='hidden' name='peca_$contador' value='$peca'>\n
						<input style='text-align:right' type='text' size='4' maxlength='4' name='$extrato_lgr' value='$qtde_pedente_temp' onblur='javascript:if (this.value > $qtde_total_item || this.value==\"\" ) {alert(\"Quantidade superior!\");this.value=\"$qtde_total_item\"}'>\n
						</td>\n";
			}else{
				echo "<td align='center'>$qtde_real</td>\n";
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>total_item:" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				echo "<td align='right'>$aliq_ipi</td>\n";
			}
			echo "</tr>\n";


			if ($numero_linhas!=5000){
				if ($z%$numero_linhas==0 AND $z>0 AND ($x+1 < $total_qtde)){
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

					if (count ($lista_pecas) >0){
						$notas_fiscais = array();
						$sql_nf = "SELECT tbl_faturamento.nota_fiscal
									FROM tbl_faturamento_item 
									JOIN tbl_faturamento      USING (faturamento)
									WHERE tbl_faturamento.fabrica = $login_fabrica
									AND   tbl_faturamento.posto   = $login_posto
									AND   tbl_faturamento.extrato_devolucao = $extrato
									AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
									ORDER BY tbl_faturamento.nota_fiscal";
						$resNF = pg_exec ($con,$sql_nf);
						for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
							array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
						}
						$notas_fiscais = array_unique($notas_fiscais);

						if (count($notas_fiscais)>0){
							echo "<tfoot>";
							echo "<tr>";
							echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
							echo "</tr>";
							echo "</tfoot>";
						}
					}
					$notas_fiscais=array();
					$lista_pecas = array();
					$qtde_peca="";
					echo "</table>\n";
					if (strlen ($nota_fiscal)==0) {
						echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
						echo "<tr>";
						echo "<td>aqui2";
						echo "\n<br>";
//						echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_icms'  value='$total_base_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi'   value='$total_base_ipi'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi'  value='$total_valor_ipi'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-cfop'       value='$cfop'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-movimento'  value='$movimento'>\n";
						echo "<center>";
						echo "<b>Preencha este Nota de Devolu��o e informe o n�mero da Nota Fiscal</b><br>Este n�mero n�o poder� ser alterado<br>";
						echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>N�mero da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
						echo "<br><br>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
						$numero_nota++;
					}else{
						if (strlen ($nota_fiscal) >0){
							echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
							echo "<tr>\n";
							echo "<td>aqui3<h1><center>Nota de Devolu��o $nota_fiscal</center></h1></td>\n";
							echo "</tr>";
							echo "</table>";
						}
					}
					#$x_cabecalho = str_replace("(CFOP)","$cfop",$cabecalho);
					/* HD 40994 */
					$x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
					echo $x_cabecalho;
					echo $topo;

					$total_base_icms  = 0;
					$total_valor_icms = 0;
					$total_base_ipi   = 0;
					$total_valor_ipi  = 0;
					$total_nota       = 0;
					$item_nota=0;
				}
			}
			flush();
		}

		if (count ($lista_pecas) >0){
			$notas_fiscais = array();
			$sql_nf = "SELECT tbl_faturamento.nota_fiscal
						FROM tbl_faturamento_item 
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.posto   = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
						ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_exec ($con,$sql_nf);
			for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
				array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
			}
			$notas_fiscais = array_unique($notas_fiscais);

			if (count($notas_fiscais)>0){
				echo "<tfoot>";
				echo "<tr>";
				echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
				echo "</tr>";
				echo "</tfoot>";
			}
		}

		echo "</table>\n";

//		$total_valor_icms = $total_base_icms * $aliq_final / 100;

		if ($numero_linhas!=5000) {
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>aqui4 Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";
		}

		if ($numero_linhas!=5000 AND strlen ($nota_fiscal) == 0) {

			$total_geral=$total_nota+$total_valor_ipi;

//			echo "\n<br>";
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>blabla";
			echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-cfop'      value='$cfop'>\n";
			echo "<center>";
			echo "<b>Preencha este Nota de Devolu��o e informe o n�mero da Nota Fiscal</b><br>Este n�mero n�o poder� ser alterado<br>";
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>N�mero da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			//echo "<br><br>";
			$item_nota=0;
			$numero_nota++;
		}else{
			if (strlen ($nota_fiscal)>0){
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td><h1><center>Nota de Devolu��o $nota_fiscal</center></h1></td>\n";
				echo "</tr>";
				echo "</table>";
			}
		}
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;

	}




	if ($numero_linhas==5000){

		if ($tem_mais_itens=='nao' AND $jah_digitado_tc>0){
			if($login_fabrica == 3){
				#echo "<b>N�o h� mais pe�as para devolu��o.<br><br>";
			}else{
				echo "<b>N�o h� mais pe�as para devolu��o.<br><br>";
			}
			echo "<a href='extrato_posto_devolucao_lgr_itens.php?extrato=$extrato'>Clique aqui para consultar as notas de devolu��o</a></b>";
		}else{
			if ($pecas_pendentes=='sim'){
				echo "<input type='hidden' name='pendentes' value='sim'>";
			}

			echo "<br>
					<input type='hidden' name='qtde_pecas' value='$contador'>
					<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'> 
					<b style='font-size:12px'>

		
					<b>Informar a quantidade de linhas no formul�rio de Nota Fiscal do Posto Autorizado:</b>
					<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
					Essa informa��o definir� a quantidade de NFs que o posto autorizado dever� emitir e enviar � Brit�nia
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
		}
	}else{
		
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
	}

	echo "</form>";

}else{

	echo "<h1><center> Extrato de M�o-de-obra Liberado. Recarregue a p�gina. </center></h1>";
	$sql =	"UPDATE tbl_extrato_extra SET
				nota_fiscal_devolucao              = '000000' ,
				valor_total_devolucao              = 0        ,
				base_icms_devolucao                = 0        ,
				valor_icms_devolucao               = 0        ,
				nota_fiscal_devolucao_distribuidor = '000000' ,
				valor_total_devolucao_distribuidor = 0        ,
				base_icms_devolucao_distribuidor   = 0        ,
				valor_icms_devolucao_distribuidor  = 0
			WHERE extrato = $extrato;";
	//$res = pg_exec ($con,$sql);

}
?>


<? include "rodape.php"; ?>
