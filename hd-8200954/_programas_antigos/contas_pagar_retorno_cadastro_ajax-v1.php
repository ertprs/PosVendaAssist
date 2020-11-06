<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if($_GET['ajax']=='sim') {
	$fornID		= $_GET["fornID"];//fornecedor
	$fatID		= $_GET["fatID"]; //faturamento
	$documento	= $_GET["documento"];
	$valor		= $_GET["valor"];
	$acao		= $_GET["acao"];
	$valor_pago	= $_GET["valor_pago"];
	$conta_pagar    = $_GET["conta_pagar"];
	$vencimento	= $_GET["vencimento"];
	$pagamento	= $_GET["pagamento"];
	$obs		= $_GET["obs"];

	$multa_p	 = $_GET["multa_p"];
	$multa_valor     = $_GET["multa_valor"];
	$juros_mora_p    = $_GET["juros_mora_p"];
	$juros_mora_valor= $_GET["juros_mora_valor"];
	$desconto	 = $_GET["desconto"];
	$desconto_p	 = $_GET["desconto_p"];
	$desconto_pontualidade	 = $_GET["desconto_pontualidade"];
	$protestar	         = $_GET["protestar"];
	$valor_custas_cartorio	 = $_GET["valor_custas_cartorio"];


	$mensagem	= "&nbsp;";

		if (strlen($obs) == 0) {
			
		}else{
			$obs = str_replace("'","",$obs);
		}
		if(strlen($fatID) == 0){
			//faturamento vazio, entao só cadastra a nota fiscal			
			$fatID = "null";
		}

		if (strlen ($pagamento) == 0) {
			$pagamento = 'null';
		}else{
			$pagamento = "'" . substr ($pagamento,6,4) . "-" . substr ($pagamento,3,2) . "-" . substr ($pagamento,0,2) . "  00:00:00'" ;
		}

		if (strlen ($valor_pago) == 0) {
			$valor_pago= "null";
		}else{
			$valor_pago = str_replace(",",".",$valor_pago);
			$valor_pago = trim(str_replace(".00","",$valor_pago));
		}
		if (strlen($multa_valor)==0){
			if (strlen($multa_p)>0 and strlen($multa_p)<>0) $multa_valor = $valor*$multa_p/100;
			if (strlen($multa_valor)==0) $multa_valor="NULL";
		}else{
			if (!$multa_valor>0) $multa_valor="NULL";
		}
		$multa_valor = str_replace(",",".",$multa_valor);

		if (strlen($juros_mora_valor)==0){
			if (strlen($juros_mora_p)>0 and strlen($juros_mora_p)<>0) $juros_mora_valor = $valor*$juros_mora_p/100;
			if (strlen($juros_mora_valor)==0) $juros_mora_valor="NULL";
		}else{
			if (!$juros_mora_valor>0) $juros_mora_valor="NULL";
		}
		$juros_mora_valor = str_replace(",",".",$juros_mora_valor);

		if (strlen($desconto)==0){
			if (strlen($desconto_p)>0 and strlen($desconto_p)<>0) $desconto = $valor*$desconto_p/100;
			if (strlen($desconto)==0) $desconto="NULL";
		}else{
			if (!$desconto>0) $desconto="NULL";
		}
		$desconto = str_replace(",",".",$desconto);

		if (strlen($protestar)==0) $protestar="NULL";
		else{
			#$vencimento = @converte_data($vencimento);
			if ($vencimento){
				$sql_p = "SELECT ('$vencimento'::date + INTERVAL '$protestar day')::date";
				$res_p = pg_exec($con,$sql_p);
				$protestar = trim(pg_result($res_p, 0, 0));
				$protestar = "'$protestar'";
			}
			else{
				$mensagem .= "Data do vencimento inválido!";
			}
		}

		if (strlen($valor_custas_cartorio)==0) $valor_custas_cartorio = "NULL";
		else $valor_custas_cartorio = str_replace(",",".",$valor_custas_cartorio);

		if ($desconto_pontualidade=='true') $desconto_pontualidade="'t'";
		else $desconto_pontualidade = "'f'";



	//Da baixa no conta a pagar selecionado
	if($acao=="baixar"){

		if(strlen($conta_pagar) == 0){
			$msg_erro = "Selecione um registro para dar Baixa!";
		}

		if (strlen ($pagamento) == 0) {
			$msg_erro .= '<br>É necessário preencher a data de vencimento.';
		}else{
			$pagamento = "'" . substr ($pagamento,6,4) . "-" . substr ($pagamento,3,2) . "-" . substr ($pagamento,0,2) . "'" ;
		}

		if (strlen ($valor_pago) == 0) {
			$msg_erro .= " <br>É necessário preencher o valor pago!";
		}else{
			$valor_pago = str_replace(",",".",$valor_pago);
			$valor_pago = trim(str_replace(".00","",$valor_pago));
		}

		if(strlen($msg_erro) == 0){
			$sql	= "select documento, pagar, pagamento from tbl_pagar where pagar = $conta_pagar;";
			$res	= pg_exec($con,$sql);
			$pagamento	= trim(pg_result($res, 0, pagamento));
			$documento	= trim(pg_result($res, 0, documento));

			if(strlen($pagamento)==0){
				$sql = "UPDATE tbl_pagar SET 
							valor_pago	= $valor_pago ,
							obs			= '$obs',
							pagamento	= current_timestamp
						WHERE pagar = $conta_pagar;";

				$res = pg_exec($con,$sql);
				if(pg_result_error($res))
					$mensagem.= "<font color='#ff0000'>Erro ao executar a baixa!</font>";
				else
					$mensagem.= "<font color='#0000ff'>O documento nº <b>$documento</b> baixado com sucesso!</font>";
			}else{
				$mensagem.= "<font color='#ff0000'>Erro: O documento de nº <b>$documento</b> já foi dado baixa anteriomente </font>";	
			}
		
		}else{
			$mensagem.= "<font color='#ff0000'>ERRO $msg_erro</font>";;
		}	
	}
	
	//Atualiza os dados de contas a pagar
	if($acao=="alterar"){
		if(strlen($documento) == 0){
			$msg_erro .= 'Digite o número do documento.';
		}

		if(strlen($fornID) == 0){
			$msg_erro .= "<br>Selecione um Fornecedor.";
		}
		if(strlen($valor) == 0){
			$msg_erro .= '<br>Digite o valor.';
		}else{
			$valor = str_replace(",",".",$valor);
			$valor = trim(str_replace(".00","",$valor));
		}
		if (strlen ($vencimento) == 0) {
			$msg_erro .= '<br>Digite a data de vencimento.';
		}else{
			$vencimento = @converte_data($vencimento);
			$vencimento = "'$vencimento'";
			#$vencimento = "'" . substr ($vencimento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;
		}		
		if(strlen($msg_erro) == 0){
			$sql = "UPDATE tbl_pagar 
					SET 
							fornecedor = $fornID	,
							documento =	'$documento',
							valor = $valor			,
							vencimento = $vencimento,
							valor_pago = $valor_pago,
							obs			= '$obs'	,
							pagamento	= $pagamento,
							protesto = $protestar,
							valor_multa = $multa_valor,
							valor_juros_dia = $juros_mora_valor,
							valor_desconto = $desconto,
							desconto_pontualidade = $desconto_pontualidade,
							valor_custas_cartorio = $valor_custas_cartorio
						WHERE pagar = $conta_pagar;";
			$res = pg_exec($con,$sql);
			if(pg_result_error($res))
				$mensagem.= "<font color='#ff0000'>Erro ao executar a baixa!</font>";
			else
				$mensagem.= "<font color='#0000ff'>O documento nº <b>$documento</b> foi alterado com sucesso!</font>";		
		}else{
			$mensagem.= "<font color='#ff0000'>ERRO $msg_erro</font>";;
		}	
	}

	//Inserir uma conta a pagar
	if($acao=="insert"){
		if(strlen($documento) == 0){
			$msg_erro .= 'Digite o número do documento.';
		}

		if(strlen($fornID) == 0){
			$msg_erro .= "<br>Selecione um Fornecedor.";
		}
		if(strlen($valor) == 0){
			$msg_erro .= '<br>Digite o valor.';
		}else{
			$valor = str_replace(",",".",$valor);
			$valor = trim(str_replace(".00","",$valor));
		}
		if (strlen ($vencimento) == 0) {
			$msg_erro .= '<br>Digite a data de vencimento.';
		}else{
			$vencimento = @converte_data($vencimento);
			$vencimento = "'$vencimento'";
		}	
		if(strlen($msg_erro) == 0){
			//testa se a conta a pagar ja foi inserida para o respectivo fornecedor.
			$sql	= "select documento
						from tbl_pagar 
						where documento = '$documento' and posto = $login_posto and fornecedor = $fornID;";
			//$mensagem.="sql: $sql";
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0 AND $documento!='-'){
				$mensagem.= "<font color='#ff0000'>Documento já foi inserido anteriormente!</font>";
			}else{



				$sql = "INSERT INTO tbl_pagar (
										posto           ,
										fornecedor      ,
										faturamento		,
										documento       ,
										valor           ,
										vencimento      ,
										obs,
										protesto,
										valor_multa,
										valor_juros_dia,
										valor_desconto,
										desconto_pontualidade,
										valor_custas_cartorio
								) VALUES (
										$login_posto    ,
										$fornID	        ,
										$fatID	        ,
										'$documento'    ,
										$valor          ,
										$vencimento     ,
										'$obs'          ,
										$protestar      ,
										$multa_valor    ,
										$juros_mora_valor,
										$desconto       ,
										$desconto_pontualidade,
										$valor_custas_cartorio
									);";
				//echo "sql:$sql";
				$res = pg_exec($con,$sql);
				$mensagem .= pg_errormessage($con);
				
				if(pg_result_error($res))
					$mensagem .= "<font color='#ff0000'>Erro: ao inserir conta a pagar!</font>";
				else
					$mensagem .= "<font color='#0000ff'>Cadastro efetuado com sucesso para o documento nº <b>$documento</b></font>";

				$conta_pagar	= '';
				$fornecedor     = '';
				$valor          = '';
				$documento      = '';
				$vencimento     = '';
				$valor_pago     = '';
				$obs			= '';
			}
		}else{
			$mensagem .= "<font color='#ff0000'>ERROS $msg_erro</font>";
		}		
	}


	/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
	$resposta .="<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
	
	$resposta .="<tr>";
	$resposta .="<td class='titulo' colspan='4' align='right'>";
	$resposta .="Data da Baixa:<input type='text' name='data_baixa' value='".date('d/m/Y')."' size='12' class='frm'> ";
	$resposta .="</td>";
	$resposta .="<td class='titulo' colspan='3' align='right'>";
	$resposta .="<input type='submit' name='baixar_sel' value='Baixar Selecionados' class='frm'> ";
	$resposta .="</td>";
	$resposta .="</tr>";	
	
	$resposta .="<tr class='Titulo2' >";
	$resposta .="<td colspan='11' align='left' background='admin/imagens_admin/azul.gif'><font size='3' color='#ffffff'>LISTAS DE CONTAS A PAGAR</font></td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Baixa</b></td>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td><b>Faturamento</b></td>";
	$resposta .="<td align='center'><b>Fornecedor</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Ações</b></td>";
	$resposta .="<td align='center'><b>Status</b></td>";
	$resposta .="</tr>";
	//SELECIONA AS CONTAS A PAGAR QUE ESTÃO PENDENTES (Q NAO FORAM PAGAS)	
	$sql="SELECT PAGAR                                        ,
			DOCUMENTO                                     ,
			FATURAMENTO                                   ,
			vencimento,
			TO_CHAR(VENCIMENTO,'DD/MM/YYYY') as VENCIMENTO_,
			CASE WHEN CURRENT_DATE - VENCIMENTO >0 THEN CURRENT_DATE - VENCIMENTO  ELSE 0 END as dias_vencido,
			REPLACE(CAST(CAST(VALOR AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') AS VALOR,
			TBL_POSTO.POSTO,
			NOME,
			valor_multa,
			valor_juros_dia,
			valor_desconto,
			desconto_pontualidade,
			CASE WHEN protesto IS NULL THEN 'NAO' ELSE 'SIM' END as protesto2,
			CASE WHEN CURRENT_DATE >= protesto THEN 'PROTESTADO' ELSE 'AINDANAO' END as protestado,
			valor_custas_cartorio
		FROM TBL_PAGAR
		LEFT JOIN TBL_POSTO ON (TBL_PAGAR.FORNECEDOR = TBL_POSTO.POSTO)
		WHERE tbl_pagar.posto = $login_posto 
		AND pagamento IS NULL 
		ORDER BY vencimento;";
			
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$cont_itens= pg_numrows($res);
			$pagar			= trim(pg_result($res, $i, pagar));
			$documento		= trim(pg_result($res, $i, documento));
			$faturamento	= trim(pg_result($res, $i, faturamento));
			$fornecedor		= trim(pg_result($res, $i, posto));
			$nome			= trim(pg_result($res, $i, nome));
			$vencimento	= trim(pg_result($res, $i, vencimento));
			$vencimento_	= trim(pg_result($res, $i, vencimento_));
			$valor			= trim(pg_result($res, $i, valor));

			$protesto		= trim(pg_result($res, $i, protesto2));
			$protestado		= trim(pg_result($res, $i, protestado));
			$valor_custas_cartorio	= trim(pg_result($res, $i, valor_custas_cartorio));
			$desconto_pontualidade	= trim(pg_result($res, $i, desconto_pontualidade));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			if ($protesto=='SIM'){
				$cor ='#FFD700';
				if ($protestado=='PROTESTADO'){
					$cor = '#EE2C2C';
				}
			}

			
	
			$resposta .="<tr bgcolor='$cor' class='Conteudo2'>";
			$resposta .="<td nowrap align='center'><input type='checkbox' name='pagar[]' id='pagar$i' value='$pagar' onClick='calcula_total_selecionado($cont_itens)'></td>";
			$resposta .="<td nowrap> <a href='#' onClick=\"exibirPagar('dados','$pagar','','mostra')\">$documento </a></td>";
			$resposta .="<td nowrap>";
			if(strlen($faturamento) > 0){ $resposta .="$faturamento"; }else{ $resposta .="-"; };
			$resposta .="</td>";
			$resposta .="<td nowrap align='left'>$fornecedor - $nome</td>";
			$resposta .="<td nowrap>$vencimento_</td>";
			$valor_aux = str_replace(",",".",$valor);
			$resposta .="<td nowrap align='center'>";
			$resposta .="<input type='hidden' name='pagar_$i' id='pagar_$i' value='$valor_aux' align='right'>R$ $valor</td>";
			$resposta .="<td nowrap> <a href='#' onClick=\"exibirPagar('dados','$pagar','','mostra')\">visualizar</a></td>";
			if($vencimento < date('Y-m-d')) $st="<font color='red'>vencido</font>";
			else $st="a vencer";
			$resposta .="<td nowrap align='center'> $st</td>";
			$resposta .="</tr>";
		}

		$resposta .="<tr>";
		$resposta .="<td colspan='7' align='right'>";
		$resposta .="<b>Total:</b>";
		$resposta .="<input type='hidden' id='cont_itens' name='cont_itens' value='$cont_itens' size='4'> ";
  		$resposta .="<input type='text' id='resultado' name='resultado' size='10' value='0' class='frm'> ";
		$resposta .="</td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td colspan='7' align='right'>";
		$resposta .="<input type='submit' name='baixar_sel' value='Baixar Selecionados' class='frm'> ";
		$resposta .="</td>";
		$resposta .="</tr>";
	}else 
		$resposta .="<tr bgcolor='#F7F5F0'><td colspan='10' align='center'><b>Sem Contas a Pagar Pendentes!&nbsp;</b></td></tr>";
	$resposta .="</table>";

	echo  "ok|".$resposta. "|$mensagem";

	exit;
}else{
	$conta_pagar= $_GET["conta_pagar"];
	$acao		= $_GET["acao"];
	
############################ OBS ##################################
########### ALTERAR SELECT PARA CONSULTAR O FORNECEDOR ############
	if($acao == "faturamento"){
		$sql="SELECT FATURAMENTO			
			FROM TBL_FATURAMENTO
			WHERE POSTO = $login_posto limit 10";
//echo $sql;
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$faturamento="<SELECT 'NAME='fat' id='fat' SIZE='7' MULTIPLE onKeyPress='selecionar(event);'>";
				$faturamento.="<OPTION SELECTED>SELECIONAR";
				for($i=0; $i < pg_numrows($res); $i++){
					$fat = trim(pg_result($res, $i, faturamento));
					
					$faturamento.="<OPTION VALUE='$fat'>$fat</OPTION>\n";
				}
				$faturamento.="</SELECT><br>";
				$faturamento.="<INPUT TYPE='button' name='bt' id='bt' value='fechar' onClick=\"ocultar('fat')\">";
				echo  "ok|$faturamento";
			}else{
				echo "ok|<font color='black'>Não tem faturamento para esse fornecedor</font>";
			}
		exit;
	}else{
		$sql="SELECT PAGAR                                         ,
					DOCUMENTO                                      ,
					TO_CHAR(VENCIMENTO,'DD/MM/YYYY') as VENCIMENTO ,
					REPLACE(CAST(CAST(VALOR AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') AS VALOR,
					OBS                                            ,
					TBL_POSTO.NOME									,
					TBL_POSTO.POSTO,
					valor_multa,
					valor_juros_dia,
					valor_desconto,
					desconto_pontualidade,
					protesto - VENCIMENTO as protesto,
					valor_custas_cartorio,
					valor_multa
			FROM TBL_PAGAR
			JOIN TBL_POSTO ON (TBL_PAGAR.FORNECEDOR = TBL_POSTO.POSTO)
			WHERE pagar = $conta_pagar";
			//echo "sql: $sql";
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$documento		= trim(pg_result($res, 0, documento));
				$fornecedor		= trim(pg_result($res, 0, posto));
				$nome			= trim(pg_result($res, 0, nome));
				$vencimento		= trim(pg_result($res, 0, vencimento));
				$valor			= trim(pg_result($res, 0, valor));
				$valor_multa		= trim(pg_result($res, 0, valor_multa));
				$valor_juros_dia	= trim(pg_result($res, 0, valor_juros_dia));
				$valor_desconto		= trim(pg_result($res, 0, valor_desconto));
				$desconto_pontualidade	= trim(pg_result($res, 0, desconto_pontualidade));
				$protesto		= trim(pg_result($res, 0, protesto));
				$valor_custas_cartorio	= trim(pg_result($res, 0, valor_custas_cartorio));
				$obs			= trim(pg_result($res, 0, obs));

				if(strpos($documento, "-")>0){
					$nf= substr($documento, 0, strpos($documento, "-"));
					$doc= substr($documento, (strpos($documento, "-")+1), strlen($documento));
				}else{
					$nf= $documento;
				}

				echo  "ok|$nf|$doc|$fornecedor|$nome|$valor|$vencimento|$obs|$conta_pagar|$valor_multa|$valor_juros_dia|$valor_desconto|$desconto_pontualidade|$protesto|$valor_custas_cartorio";
			}
		exit;
	}
}
?>