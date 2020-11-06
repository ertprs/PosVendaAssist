<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($_GET['ajax']=='sim') {
	$fornID		= $_GET["fornID"];//fornecedor
	$fatID		= $_GET["fatID"]; //faturamento
	$documento	= $_GET["documento"];
	$valor		= $_GET["valor"];
	$acao		= $_GET["acao"];
	$valor_pago	= $_GET["valor_pago"];
	$conta_pagar= $_GET["conta_pagar"];
	$vencimento	= $_GET["vencimento"];
	$pagamento	= $_GET["pagamento"];
	$obs		= $_GET["obs"];
	$mensagem	= "&nbsp;";

	//Da baixa no conta a pagar selecionado
	if($acao=="baixar"){
		if(strlen($conta_pagar) == 0){
			$msg_erro = "Selecione um registro para dar Baixa!";
		}
		if (strlen($obs) == 0) {
			
		}else{
			$obs = str_replace("'","",$obs);
		}

		if (strlen ($pagamento) == 0) {
			$msg_erro .= '<br>É necessário preencher a data de vencimento.';
		}else{
			$pagamento = "'" . substr ($pagamento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;
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
			$documento	= trim(pg_result($res, 0, pagamento));

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
		if(strlen($conta_pagar) == 0){
			$msg_erro = "Selecione um registro para dar Baixa!";
		}

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

		if (strlen($obs) == 0) {
			
		}else{
			$obs = str_replace("'","",$obs);
		}

		if (strlen ($vencimento) == 0) {
			$msg_erro .= '<br>Digite a data de vencimento.';
		}else{
			$vencimento = "'" . substr ($vencimento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;
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

		if(strlen($msg_erro) == 0){
			$sql = "UPDATE tbl_pagar 
					SET 
							fornecedor = $fornID	,
							documento =	'$documento',
							valor = $valor			,
							vencimento = $vencimento,
							valor_pago	= $valor_pago,
							obs			= '$obs'	,
							pagamento	= $pagamento
						WHERE pagar = $conta_pagar;";
			//$mensagem .="$sql";
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

		if(strlen($fatID) == 0){
			//faturamento vazio, entao só cadastra a nota fiscal			
			$fatID = "null";
		}else{
			//cadastra o faturamento
			
		}

		
		if(strlen($valor) == 0){
			$msg_erro .= '<br>Digite o valor.';
		}else{
			$valor = str_replace(",",".",$valor);
			$valor = trim(str_replace(".00","",$valor));
		}
		
		if (strlen($obs) == 0) {
			
		}else{
			$obs = str_replace("'","",$obs);
		}
		
		if (strlen ($vencimento) == 0) {
			$msg_erro .= '<br>Digite a data de vencimento.';
		}else{
			$vencimento = "'" . substr ($vencimento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;
		}
		if(strlen($msg_erro) == 0){
			//testa se a conta a pagar ja foi inserida para o respectivo fornecedor.
			$sql	= "select documento
						from tbl_pagar 
						where documento = '$documento' and posto = $login_posto and fornecedor = $fornID;";
			$mensagem.="sql: $sql";
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$mensagem.= "<font color='#ff0000'>Documento já foi inserido anteriormente!</font>";
			}else{
				$sql = "INSERT INTO tbl_pagar (
										posto           ,
										fornecedor      ,
										faturamento		,
										documento       ,
										valor           ,
										vencimento      ,
										obs
								) VALUES (
										$login_posto    ,
										$fornID	        ,
										$fatID	        ,
										'$documento'    ,
										$valor          ,
										$vencimento     ,
										'$obs'
									);";
				//echo "sql:$sql";
				$res = pg_exec($con,$sql);
				
				if(pg_result_error($res))
					$mensagem.= "<font color='#ff0000'>Erro: ao inserir conta a pagar!</font>";
				else
					$mensagem.= "<font color='#0000ff'>Cadastro efetuado com sucesso para o documento nº <b>$documento</b></font>";

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
	
	$resposta .="<tr class='Titulo' >";
	$resposta .="<td colspan='11' align='left' background='../admin/imagens_admin/azul.gif'><font size='3' color='#ffffff'>LISTAS DE CONTAS A PAGAR</font></td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo'>";
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
				REPLACE(CAST(CAST(VALOR AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') AS VALOR,
				TBL_POSTO.POSTO,
				NOME                                          
		FROM TBL_PAGAR
		LEFT JOIN TBL_POSTO ON (TBL_PAGAR.FORNECEDOR = TBL_POSTO.POSTO)
		WHERE tbl_pagar.posto = $login_posto and pagamento is null ORDER BY VENCIMENTO;";
		
	
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
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$resposta .="<tr bgcolor='$cor' class='Conteudo'>";
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
					TBL_POSTO.POSTO
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
				$obs			= trim(pg_result($res, 0, obs));

				if(strpos($documento, "-")>0){
					$nf= substr($documento, 0, strpos($documento, "-"));
					$doc= substr($documento, (strpos($documento, "-")+1), strlen($documento));
				}else{
					$nf= $documento;
				}


				echo  "ok|$nf|$doc|$fornecedor|$nome|$valor|$vencimento|$obs>>> nf: $nf Doc:$doc|$conta_pagar";
			}
		exit;
	}
}
?>