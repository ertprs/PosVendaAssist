<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao     = $_POST['btn_acao'];
$posto_codigo = $_POST['posto_codigo'];
$posto_nome   = $_POST['posto_nome'];
$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final'];
$mostrar      = $_POST['mostrar'];
//ini_set('display_errors','On');
?>

<html>
<head>
<title>Contas a Receber</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>


<? include 'menu.php' ?>

<style>
	.tabela2 {
		width:100%; !important
		margin:0 auto; !important
	}

	.tabela caption{
		border-bottom:5px solid #0099CC;
		margin:0;
		padding:0;
	}
	.legenda{
		display:block;
		font-size:10px;
		color:#6C6C6C;
	}
	.mostrar{
		padding:3px;
		text-align:center;

	}
</style>

<center><h1>Contas a Receber</h1></center>

	<?
		# permissão Valéria, Tulio, Cida, Celso, Ronaldo
		if ($login_unico != 13 and $login_unico != 1  and $login_unico != 1884 and $login_unico != 2075 and $login_unico != 2) {
	        	echo "<p>Sem permissão de acesso!</p>";
	        	exit;
		}
	?>

	<form name='frm_pesquisa' action='<?=$PHP_SELF?>' method='POST'>
	<table align='center'>
		<caption>Pesquisa</caption>
		<tr>
			<td>Código do Posto</td>
			<td>Nome do Posto</td>
		</tr>
		<tr>
			<td><input type='text' name='posto_codigo' size='15' value='<?=$posto_codigo?>'></td>
			<td><input type='text' name='posto_nome'   size='30' value='<?=$posto_nome?>'></td>
		</tr>
		<tr>
			<td>Data Inicial</td>
			<td>Data Final</td>
		</tr>
		<tr>
			<td><input type='text' name='data_inicial' size='12' maxlength='10' value='<?=$data_inicial?>'>
			<span class='legenda'>(Vencimento)</span></td>
			<td><input type='text' name='data_final' size='12' maxlength='10' value='<?=$data_final?>'
			><span class='legenda'>(Vencimento)</span></td>
		</tr>
		<tr>
			<td colspan='2'>
				<div class='mostrar'>
				<span title='Será exibida todos os boletos vencidos, e os não vencidos em aberto'>
				Vencidos e Pendentes<input type="radio" name="mostrar" value='vencidas_pendentes' 
				<?if($mostrar=='vencidas_pendentes'  OR $mostrar=='') echo " CHECKED ";?>>
				</span>
				&nbsp;&nbsp;&nbsp;
				Todos<input type="radio" name="mostrar" title='Será exibida todas os boletos baixados, vencidos, e não vencidos em aberto' value='todas' <?if($mostrar=='todas') echo " CHECKED ";?>>&nbsp;&nbsp;&nbsp;
				Baixados<input type="radio" name="mostrar" title='Será exibida todas os boletos baixados' value='baixadas' <?if($mostrar=='baixadas') echo " CHECKED ";?>>&nbsp;&nbsp;&nbsp;
				Vencidos<input type="radio" name="mostrar" title='Será exibida SOMENTE os boletos vencidos em aberto' value='vencidas' <?if($mostrar=='vencidas') echo " CHECKED ";?>>&nbsp;&nbsp;&nbsp;
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type='submit' name='btn_acao' value='Pesquisar'></td>
		</tr>
		<tr>
			<td colspan='2' align='center'> Legenda:<br>
				<table>
					<tr><td bgcolor='#B4B4B4'>Baixados</td><td bgcolor='#ECAD9D'>Vencidos</td><td bgcolor='#F7F5B7'>Pendentes</td></tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<?

if ($btn_acao == 'Pesquisar'){

	if (strlen ($data_inicial) == 10) {
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	}
	if (strlen ($data_final)   == 10) {
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	}
	$sql = "SELECT 	tbl_contas_receber.contas_receber,
					tbl_contas_receber.faturamento_fatura,
					tbl_contas_receber.nome_arquivo_remessa,
					tbl_contas_receber.nome_arquivo_retorno,
					tbl_contas_receber.identificacao_ocorrencia,
					tbl_contas_receber.descricao_identificacao_ocorrencia,
					tbl_contas_receber.motivo_ocorrencia_1,
					tbl_contas_receber.descricao_motivo_ocorrencia_1,
					tbl_posto.nome, 
					tbl_posto.cnpj,	
					tbl_posto.fone, 
					tbl_posto.email, 
					tbl_contas_receber.documento, 
					tbl_contas_receber.nosso_numero,
					tbl_contas_receber.valor AS valor,
					(current_date - tbl_contas_receber.vencimento::date)::int4 AS dias_atraso,
					TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
					TO_CHAR(tbl_contas_receber.recebimento,'DD/MM/YYYY') as recebimento,
					tbl_contas_receber.valor_recebido,
					valor_dias_atraso
			FROM tbl_posto
			JOIN tbl_contas_receber USING (posto)";

	if (strlen($posto_codigo)>0){
		$sql .= "JOIN (
					SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
				) post ON post.posto = tbl_contas_receber.posto";
	}

	$sql .= " WHERE tbl_contas_receber.distribuidor  = $login_posto";

	if ($mostrar=='vencidas_pendentes'){
		$sql .=" AND (tbl_contas_receber.recebimento IS NULL 
				 OR 
				 (tbl_contas_receber.recebimento IS NULL  AND (current_date - tbl_contas_receber.vencimento::date)::int4 > 0) ) ";
	}

	if ($mostrar=='vencidas'){
		$sql .=" AND tbl_contas_receber.recebimento IS NULL 
				 AND (current_date - tbl_contas_receber.vencimento::date)::int4 > 0";
	}
	if ($mostrar=='baixadas'){
		$sql .=" AND tbl_contas_receber.recebimento notnull ";
	}

	if (strlen($posto_nome)>0 AND strlen($posto_codigo)==0){
		$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
	}

	if (strlen($x_data_inicial)>0 AND strlen($x_data_final)>0){
		$sql .= " AND tbl_contas_receber.vencimento BETWEEN '$x_data_inicial' AND '$x_data_final'";
	}

	$sql .= "ORDER BY tbl_contas_receber.vencimento";
	//echo nl2br($sql); exit;
	$res = pg_exec ($con,$sql);


	echo "<br><table with=1412 align='center' border='0' >";
		echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td  width=260 colspan='2'>Dados para auxiliar na cobrança</td>";
			echo "<td  width=210 colspan='3'>Origem do boleto</td>";
			echo "<td  width=622 colspan='8'>Boletos gerados</td>";
			echo "<td  width=320 colspan='2'>Cobrança Bancária</td>";
		echo "</tr>";
		echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td width=180>CNPJ - Posto</td>";
			echo "<td width=80>Fone/Email</td>";
			echo "<td width=70>Nota Fiscal</td>";
			echo "<td width=70>Emissão</td>";
			echo "<td width=70>Valor</td>";
			echo "<td width=95>Documento</td>";
			echo "<td width=107 nowrap>Nosso Número</td>";
			echo "<td width=70>Vencimento</td>";
			echo "<td width=70>Valor</td>";
			echo "<td width=70>Recebimento</td>";
			echo "<td width=70>Valor Recebido</td>";
			echo "<td width=70>Juros</td>";
			echo "<td width=70>Total</td>";
			echo "<td width=120>Remessa</td>";
			echo "<td width=200>Retorno</td>";
		echo "</tr>";

	$total = 0 ;
	$ja_imprimiu = array();

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$contas_receber                     = pg_result ($res,$i,contas_receber);
		$pular_pq_ja_imprimiu = 'NAO';
		foreach($ja_imprimiu as $contas_receber_ja){
			if($contas_receber_ja == $contas_receber){
				$pular_pq_ja_imprimiu = 'SIM';
				break;
			}
		}
		if($pular_pq_ja_imprimiu == 'SIM'){
			continue;
		}

		$dias_atraso         = pg_result($res,$i,dias_atraso);
		$valor_dias_atraso   = pg_result($res,$i,valor_dias_atraso);

		$cnpj         = pg_result ($res,$i,cnpj);
		$nome         = pg_result ($res,$i,nome);
		$fone         = pg_result ($res,$i,fone);
		$email        = pg_result ($res,$i,email);
		$emissao      = pg_result ($res,$i,emissao);
		$documento    = pg_result ($res,$i,documento);
		$nosso_numero = pg_result ($res,$i,nosso_numero); 
		$vencimento   = pg_result ($res,$i,vencimento);
		$recebimento    = pg_result ($resc,$f,recebimento);
		$valor_recebido = pg_result ($resc,$f,valor_recebido);
		$valor        = pg_result ($res,$i,valor);

		$faturamento_fatura                 = pg_result ($res,$i,faturamento_fatura);
		$nome_arquivo_remessa               = pg_result ($res,$i,nome_arquivo_remessa);
		$nome_arquivo_retorno               = pg_result ($res,$i,nome_arquivo_retorno);
		$identificacao_ocorrencia           = pg_result ($res,$i,identificacao_ocorrencia);          
		$descricao_identificacao_ocorrencia = pg_result ($res,$i,descricao_identificacao_ocorrencia); 
		$motivo_ocorrencia_1                = pg_result ($res,$i,motivo_ocorrencia_1);
		$descricao_motivo_ocorrencia_1      = pg_result ($res,$i,descricao_motivo_ocorrencia_1);

		$cor = "#cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';
		$rowspanf = 0;
		if(strlen($faturamento_fatura)>0){
			$sqlf = "select nota_fiscal, TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao, total_nota
						FROM tbl_faturamento 
						WHERE faturamento_fatura = '$faturamento_fatura';";
			$resf = pg_exec ($con,$sqlf);
			$rowspanf = pg_num_rows($resf);
		}

		$sqlc = "select tbl_contas_receber.contas_receber,
					tbl_contas_receber.documento, 
					tbl_contas_receber.nosso_numero,
					tbl_contas_receber.valor AS valor,
					TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
					TO_CHAR(tbl_contas_receber.recebimento,'DD/MM/YYYY') as recebimento,
					tbl_contas_receber.valor_recebido,
					(current_date - tbl_contas_receber.vencimento::date)::int4 AS dias_atraso,
					valor_dias_atraso
					FROM tbl_contas_receber
					WHERE 1 = 1 ";
		if(strlen($faturamento_fatura)>0){
			$sqlc .= " AND faturamento_fatura = '$faturamento_fatura' ";
		}else{
			$sqlc .= " AND tbl_contas_receber.contas_receber = $contas_receber
					/* AND   documento <> '$documento' */";
		}
					//echo nl2br($sqlc); exit;
		$resc = pg_exec ($con,$sqlc);

		$rowspan =  ($rowspanf > pg_num_rows($resc)) ? $rowspanf : pg_num_rows($resc);

		echo "<tr bgcolor='$cor'>";

			echo "<td width='180' rowspan='{$rowspan}'>";
				echo $cnpj."<BR>".$nome;
			echo "</td>";
			
			echo "<td width=80 rowspan='{$rowspan}'>";
			echo $fone."<br>".str_replace(array(";"),"<br>",$email);
			echo "</td>";
		
			if($rowspanf>0){
				$f = 0; // somente a primeira linha será impressa, as outras abaixo rowspan
				$nota_fiscalf = pg_result ($resf,$f,nota_fiscal);
				$emissaof     = pg_result ($resf,$f,emissao);
				$total_notaf  = pg_result ($resf,$f,total_nota);
				echo "<td align='center' width=70>$nota_fiscalf</td><td align='center' width=70>$emissaof</td><td  width=70 align='right'>".number_format($total_notaf,2,",",".")."</td>";
			}else{
				echo "<td width=70>&nbsp;</td><td width=70>&nbsp;</td><td  width=70 align='right'>&nbsp;</td>";
			}
			
			if(pg_numrows($resc)>0){
				//for ($f = 0 ; $f < pg_numrows ($resc) ; $f++) {
				$f=0; //somente a primeira linha será impressa. As outras serão impressa abaixo no rowspan
				$contas_receberc = pg_result ($resc,$f,contas_receber);
				$documentoc      = trim(pg_result ($resc,$f,documento));
				$nosso_numeroc   = trim(pg_result ($resc,$f,nosso_numero));
				$valorc          = pg_result ($resc,$f,valor);
				$vencimentoc     = pg_result ($resc,$f,vencimento);
				$recebimentoc    = pg_result ($resc,$f,recebimento);
				$valor_recebidoc = pg_result ($resc,$f,valor_recebido);
				$dias_atrasoc    = pg_result ($resc,$f,dias_atraso);
				$valor_dias_atrasoc    = pg_result ($resc,$f,valor_dias_atraso);
				array_push($ja_imprimiu,$contas_receberc);

				if($recebimentoc>0){
					$corfont="#B4B4B4";
				}elseif($dias_atrasoc<1){
					$corfont="#F7F5B7";
				}else{
					$corfont="#ECAD9D";
				}

				echo   "<td align='center' bgcolor='$corfont' width=95>$documentoc</td>
						<td align='center' bgcolor='$corfont' width=107>$nosso_numeroc</td>
						<td align='center' bgcolor='$corfont' width=70>$vencimentoc</td>
						<td bgcolor='$corfont' width=70 align='right'>".number_format($valorc,2,",",".")."</td>";
				if($recebimentoc==""){
					echo "<td bgcolor='$corfont' width=70>&nbsp;</td>";
				}else{
					echo "<td align='center' bgcolor='$corfont' width=70>$recebimentoc</td>";
				}
				echo "<td bgcolor='$corfont' width=70 align='right'>".number_format($valor_recebidoc,2,",",".")."</td>";
				if(strlen($valor_recebidoc)==0 or $valor_recebidoc == 0){
					$juros_dias_atrasoc   = $dias_atrasoc * $valor_dias_atrasoc;
					$jurosc               = $valorc * 2 / 100;
					$tarifa_cancelamentoc = 6;
					if($dias_atrasoc<1) {
						$jurosc = 0;
						$juros_dias_atrasoc =0;
					}
					$total_jurosc         = $juros_dias_atrasoc + $jurosc + $tarifa_cancelamentoc;

					echo "<td bgcolor='$corfont' width=70 align='right' width='100' title='Juros = ( dias em atraso($dias_atrasoc) * valor do dia por atraso(".number_format ($valor_dias_atrasoc,2,",",".").") + 2% multa(".number_format ($jurosc,2,",",".").") + tarifa_cancelamento(6,00)'>";
						echo number_format ($total_jurosc,2,",",".");
					echo "</td>";

					echo "<td bgcolor='$corfont' width=70 align='right' width='100'>";
						$total_doctoc = $total_jurosc + $valorc;
						echo number_format ($total_doctoc,2,",",".");
						$total += $total_doctoc;
					echo "</td>";
				}else{
					echo "<td bgcolor='$corfont' width=70>&nbsp;</td>";
					echo "<td bgcolor='$corfont' width=70>&nbsp;</td>";
				}
			}else{
				echo "<td width=95>&nbsp;</td>";
				echo "<td width=107 nowrap>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
			}

			echo "<td  align='center' width=120 rowspan='{$rowspan}'>";
			echo $nome_arquivo_remessa;
			echo "</td>";

			echo "<td  width=200 rowspan='{$rowspan}'>";
			echo $nome_arquivo_retorno."<BR>".$identificacao_ocorrencia."-".$descricao_identificacao_ocorrencia."<BR>".$motivo_ocorrencia_1."-".$descricao_motivo_ocorrencia_1;
			echo "</td>";

		echo "</tr>";
		for ($f = 1 ; $f < $rowspan ; $f++) {
			$cor = "#cccccc";
			if ($i % 2 == 0) $cor = '#eeeeee';
			echo "<tr bgcolor='$cor'>";
			$nota_fiscalf = @pg_result ($resf,$f,nota_fiscal);
			$emissaof     = @pg_result ($resf,$f,emissao);
			$total_notaf  = @pg_result ($resf,$f,total_nota);
			if(strlen($nota_fiscalf)>0){
				echo "<td align='center' width=70>$nota_fiscalf</td><td align='center' width=70>$emissaof</td><td  width=70 align='right'>".number_format($total_notaf,2,",",".")."</td>";
			}else{
				echo "<td width=70>&nbsp;</td><td width=70>&nbsp;</td><td  width=70 align='right'>&nbsp;</td>";
			}

			$contas_receberc = @pg_result ($resc,$f,contas_receber);
			$documentoc      = trim(@pg_result ($resc,$f,documento));
			$nosso_numeroc   = trim(@pg_result ($resc,$f,nosso_numero));
			$valorc          = @pg_result ($resc,$f,valor);
			$vencimentoc     = @pg_result ($resc,$f,vencimento);
			$recebimentoc    = @pg_result ($resc,$f,recebimento);
			$valor_recebidoc = @pg_result ($resc,$f,valor_recebido);
			$dias_atrasoc    = @pg_result ($resc,$f,dias_atraso);
			$valor_dias_atrasoc    = @pg_result ($resc,$f,valor_dias_atraso);
			if(strlen($contas_receberc)>0){
				if($recebimentoc>0){
					$corfont="#B4B4B4";
				}elseif($dias_atrasoc<1){
					$corfont="#F7F5B7";
				}else{
					$corfont="#ECAD9D";
				}
				array_push($ja_imprimiu,$contas_receberc);
				echo   "<td align='center' bgcolor='$corfont' width=95>$documentoc</td>
						<td align='center' bgcolor='$corfont' width=107>$nosso_numeroc</td>
						<td align='center' bgcolor='$corfont' width=70>$vencimentoc</td>
						<td bgcolor='$corfont' width=70 align='right'>".number_format($valorc,2,",",".")."</td>";
				if($recebimentoc==""){
					echo "<td bgcolor='$corfont' width=70>&nbsp;</td>";
				}else{
					echo "<td align='center' bgcolor='$corfont' width=70>$recebimentoc</td>";
				}
				echo "<td bgcolor='$corfont' width=70 align='right'>".number_format($valor_recebidoc,2,",",".")."</td>";
				if(strlen($valor_recebidoc)==0 or $valor_recebidoc == 0){
					$juros_dias_atrasoc   = $dias_atrasoc * $valor_dias_atrasoc;
					$jurosc               = $valorc * 2 / 100;
					$tarifa_cancelamentoc = 6;
					if($dias_atrasoc<1) {
						$jurosc = 0;
						$juros_dias_atrasoc =0;
					}
					$total_jurosc         = $juros_dias_atrasoc + $jurosc + $tarifa_cancelamentoc;

					echo "<td bgcolor='$corfont' width=70 align='right' width='100' title='Juros = ( dias em atraso($dias_atrasoc) * valor do dia por atraso(".number_format ($valor_dias_atrasoc,2,",",".").") + 2% multa(".number_format ($jurosc,2,",",".").") + tarifa_cancelamento(6,00)'>";
						echo number_format ($total_jurosc,2,",",".");
					echo "</td>";

					echo "<td bgcolor='$corfont' width=70 align='right' width='100'>";
						$total_doctoc = $total_jurosc + $valorc;
						echo number_format ($total_doctoc,2,",",".");
						$total += $total_doctoc;
					echo "</td>";
				}else{
					echo "<td bgcolor='$corfont' width=70>&nbsp;</td>";
					echo "<td bgcolor='$corfont' width=70>&nbsp;</td>";
				}
			}else{
				echo "<td width=95>&nbsp;</td>";
				echo "<td width=107 nowrap>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
				echo "<td width=70>&nbsp;</td>";
			}
		echo "</tr>";
		}
	}

	$total = number_format ($total,2,",",".");

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";



	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' >";
		echo "<td colspan='12' align ='right'>Total em aberto&nbsp;</td>";
		echo "<td width=70 align='right'>$total</td>";
		echo "<td width=320 colspan='2'>&nbsp;</td>";
	echo "</tr>";
	echo "</table>";
}
?>
<? #include "rodape.php"; ?>

</body>
</html>
