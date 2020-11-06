<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$msg_erro="";


if($_GET['ajax']=='sim') {
	$faturamento	= $_GET["faturamento"];
	
	if(strlen($faturamento)>0){

	/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
	$resposta .="<table class='table_line' border='0' width='750px' cellpadding='2' cellspacing='0' style='' bordercolor='#d2e4fc'  align='center'>";
	$resposta .="<thead>";
	$resposta .="<tr class='titulo'>";
	$resposta .="<td colspan='7'><b>Forma de Pagamento</b></td>";
	$resposta .="</td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Fornecedor</b></td>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td><b>Faturamento</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Valor Hoje</b></td>";
	$resposta .="<td align='center'><b>Status</b></td>";
	$resposta .="</tr>";
	$resposta .="</head>";
	$resposta .="<tbody>";
	//SELECIONA AS CONTAS A PAGAR QUE ESTÃO PENDENTES (Q NAO FORAM PAGAS)	
	$sql="SELECT pagar                                        ,
			documento                                     ,
			faturamento                                   ,
			vencimento,
			TO_CHAR(vencimento,'dd/mm/yyyy') as vencimento_,
			case when current_date - vencimento >0 then current_date - vencimento  else 0 end as dias_vencido,
			replace(cast(cast(valor as numeric(12,2)) as varchar(14)),'.', ',') as valor,
			valor as valor2,
			tbl_pagar.pessoa_fornecedor,
			tbl_pessoa.nome,
			valor_multa,
			valor_juros_dia,
			valor_desconto,
			desconto_pontualidade,
			current_date - vencimento as dias_vencido
		FROM tbl_pagar
		join tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor
		WHERE tbl_pagar.loja = $login_loja
		AND tbl_pagar.empresa=$login_empresa
		AND tbl_pagar.faturamento = $faturamento
		AND pagamento IS null
		order by vencimento;";
			
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$cont_itens		= pg_numrows($res);
			$pagar			= trim(pg_result($res, $i, pagar));
			$documento		= trim(pg_result($res, $i, documento));
			$faturamento	= trim(pg_result($res, $i, FATURAMENTO));
			$fornecedor		= trim(pg_result($res, $i, pessoa_fornecedor));
			$nome			= trim(pg_result($res, $i, nome));
			$vencimento		= trim(pg_result($res, $i, vencimento));
			$vencimento_	= trim(pg_result($res, $i, vencimento_));
			$valor			= trim(pg_result($res, $i, valor));
			$valor2			= trim(pg_result($res, $i, valor2));

			$valor_multa	= trim(pg_result($res, $i, valor_multa));
			$valor_juros_dia= trim(pg_result($res, $i, valor_juros_dia));
			$valor_desconto	= trim(pg_result($res, $i, valor_desconto));
			$desconto_pontualidade	= trim(pg_result($res, $i, desconto_pontualidade));
			$dias_vencido	= trim(pg_result($res, $i, dias_vencido));


			if($cor=="#fafafa")$cor = '#eeeeff';
			else               $cor = '#fafafa';

			if ($dias_vencido>0 AND $protesto=='SIM'){
				$cor ='#FFD700';
				if ($protesto_aux=='PROTESTADO'){
					$cor = '#EE2C2C';
				}
			}

			if (strlen($valor_custas_cartorio)==0){
				$valor_custas_cartorio=0;
			}

			// para calcular a quantidade a pagar com juros e multa

			$valor_reajustado = $valor2;

			if ($desconto_pontualidade<>'t'){
				$valor_reajustado -= $valor_desconto;
			}
			if ($dias_vencido<=0 AND $desconto_pontualidade=='t'){
				$valor_reajustado -= $valor_desconto;
			}
			if ($dias_vencido>0){
				$valor_reajustado += $valor_multa;
				$valor_reajustado += $valor_juros_dia*$dias_vencido;
				$valor_reajustado += $valor_custas_cartorio;
			}
			$valor_reajustado = number_format($valor_reajustado,2, ',', '');


			$valor_aux = str_replace(",",".",$valor);
	
			$resposta .="<tr bgcolor='$cor' class='linha'  id='linha_$i'>";

			if (strlen($nome)>29) $nome = substr($nome,0,29)."...";
			$resposta .="<td nowrap align='left'>$fornecedor - $nome</td>";
			$resposta .="<td nowrap> $documento </td>";
			$resposta .="<td nowrap>$faturamento</td>";
			$resposta .="<td nowrap>$vencimento_</td>";
			$resposta .="<td nowrap align='center'>R$ $valor</td>";
			$resposta .="<td nowrap align='right'>R$ $valor_reajustado</td>";
			if($vencimento < date('Y-m-d')) $st="<font color='red'>vencido</font>";
			else $st="a vencer";
			$resposta .="<td nowrap align='center'> $st</td>";
			$resposta .="</tr>";
		}
		$resposta .="</tbody>";
	}else {
			$resposta .="<tr bgcolor='#F7F5F0'><td colspan='9' align='center'><b>Sem Contas a Pagar Pendentes!&nbsp;</b></td></tr>";
	
	}
	/*	$resposta .="<tr bgcolor='#F7F5F0'><td colspan='9' align='center'><b>Sem Contas a Pagar Pendentes!&nbsp;</b></td></tr>";
		$resposta .="</tbody>";
	$resposta .="</table>";*/

	echo  "ok|".$resposta. "|$mensagem";

	exit;

	
	}else{

	/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
	$resposta .="<table class='table_line' border='1' width='99%' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
	$resposta .="<thead>";
	$resposta .="<tr class='titulo'>";
	$resposta .="<td colspan='7'><b>Forma de Pagamento</b></td>";
	$resposta .="</td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Fornecedor</b></td>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td><b>Faturamento</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Valor Hoje</b></td>";
	$resposta .="<td align='center'><b>Status</b></td>";
	$resposta .="</tr>";
	$resposta .="</head>";
	$resposta .="<tbody>";
	for ( $i = 0 ; $i < 2 ; $i++ ) {
		if($cor=="#fafafa")$cor = '#eeeeff';
		else               $cor = '#fafafa';

		$resposta .="<tr bgcolor='$cor' class='Conteudo2' >";
		$resposta .="<td nowrap align='left'>&nbsp;</td>";
		$resposta .="<td nowrap>&nbsp;</td>";
		$resposta .="<td nowrap>&nbsp;</td>";
		$resposta .="<td nowrap>&nbsp;</td>";
		$resposta .="<td nowrap align='center'>R$ 0,00</td>";
		$resposta .="<td nowrap align='right'>R$ 0,00</td>";
		$resposta .="<td nowrap align='center'>&nbsp;</td>";
		$resposta .="</tr>";
	}
	$resposta .="</tbody>";

	echo  "ok|".$resposta. "|$mensagem";

	exit;
	
	}
}
?>