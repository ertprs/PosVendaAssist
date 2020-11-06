<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$msg_erro="";

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
	$conta_pagar= $_GET["conta_pagar"];
	$vencimento	= $_GET["vencimento"];
	$pagamento	= $_GET["pagamento"];
	$obs		= $_GET["obs"];

	$multa_p	     = $_GET["multa_p"];
	$multa_valor     = $_GET["multa_valor"];
	$juros_mora_p    = $_GET["juros_mora_p"];
	$juros_mora_valor= $_GET["juros_mora_valor"];
	$desconto	     = $_GET["desconto"];
	$desconto_p	     = $_GET["desconto_p"];
	$desconto_pontualidade	 = $_GET["desconto_pontualidade"];
	$protestar	     = $_GET["protestar"];
	$valor_custas_cartorio	 = $_GET["valor_custas_cartorio"];

	// opções diversas
	$dividir	 = $_GET["dividir"];
	$quitar		 = $_GET["quitar"];


	$mensagem	= "&nbsp;";

		if (strlen($obs) == 0) {
			
		}else{
			$obs = str_replace("'","",$obs);
		}
		if (strlen ($pagamento) == 0) {
			$pagamento = 'NULL';
		}

		if (strlen ($valor_pago) == 0) {
			$valor_pago= "NULL";
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

		if (strlen($protestar)==0 || $protestar==0) $protestar="NULL";
		else{
			$vencimento_aux = @converte_data($vencimento);
			if ($vencimento_aux){
				$sql_p = "SELECT ('$vencimento_aux'::date + INTERVAL '$protestar day')::date";
				$res_p = pg_exec($con,$sql_p);
				$protestar = trim(pg_result($res_p, 0, 0));
				if ($protestar<0 or $protestar>10) $protestar="NULL";
				else $protestar = "'$protestar'";
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
			$msg_erro .= '<br>É necessário preencher a data da baixa.';
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
			$resX = pg_exec ($con,"BEGIN TRANSACTION");

			$sql = "SELECT documento, 
					pagamento as data_pagamento,
					vencimento,
					valor,
					valor_multa,
					valor_juros_dia,
					valor_desconto,
					desconto_pontualidade,
					protesto - vencimento as protesto,
					valor_custas_cartorio,
					valor_multa,
					current_date - vencimento as dias_vencido
				FROM tbl_pagar 
				WHERE pagar = $conta_pagar";

			$res	= pg_exec($con,$sql);

			$data_pagamento		= trim(pg_result($res, 0, data_pagamento));
			$documento			= trim(pg_result($res, 0, documento));
			$vencimento			= trim(pg_result($res, 0, vencimento));
			$valor				= trim(pg_result($res, 0, valor));
			$valor_multa		= trim(pg_result($res, 0, valor_multa));
			$valor_juros_dia	= trim(pg_result($res, 0, valor_juros_dia));
			$valor_desconto		= trim(pg_result($res, 0, valor_desconto));
			$desconto_pontualidade	= trim(pg_result($res, 0, desconto_pontualidade));
			$protesto			= trim(pg_result($res, 0, protesto));
			$valor_custas_cartorio	= trim(pg_result($res, 0, valor_custas_cartorio));

			if(strlen($data_pagamento)==0){
				if (strlen($valor_custas_cartorio)==0){
					$valor_custas_cartorio=0;
				}
				// para calcular a quantidade a pagar com juros e multa
				$dias_vencido		= trim(pg_result($res, 0, dias_vencido));
				$valor_reajustado = $valor;

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
				if ($valor_pago<$valor_reajustado){
					if ($dividir=='sim'){
						$nf= substr($documento, 0, strpos($documento, "-"));
						$doc= substr($documento, (strpos($documento, "-")+1),strlen($documento));
						$nf = $nf."/B";
						$sql = "INSERT INTO tbl_pagar
							(
							fornecedor,
							obs,
							digitacao,
							faturamento,
							vencimento,
							valor,
							pagamento,
							valor_pago,
							obs_pagamento,
							loja,
							empresa,
							documento,
							valor_multa,
							valor_juros_dia,
							valor_desconto,
							desconto_pontualidade,
							protesto,
							valor_custas_cartorio
							)
							SELECT
							fornecedor,
							obs,
							digitacao,
							faturamento,
							vencimento,
							valor-$valor_pago,
							pagamento,
							valor_pago,
							obs_pagamento,
							loja,
							empresa,
							'$doc-$nf',
							valor_multa,
							valor_juros_dia,
							valor_desconto,
							desconto_pontualidade,
							protesto,
							valor_custas_cartorio
							FROM tbl_pagar
							WHERE pagar = $conta_pagar";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						#$sql = "SELECT CURRVAL ('seq_pagar')";
						#$resZ = pg_exec ($con,$sql);
						#$sequencia = pg_result ($resZ,0,0);

					}
				}
				$sql = "UPDATE tbl_pagar SET 
							valor_pago	= $valor_pago ,
							obs		= '$obs',
							pagamento	= $pagamento
						WHERE pagar = $conta_pagar";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}else{
				$mensagem.= "<font color='#ff0000'>Erro: O documento de nº <b>$documento</b> já foi dado baixa anteriomente em $data_pagamento</font>";	
			}

			if (strlen($msg_erro) == 0) {
				$resX = pg_exec ($con,"COMMIT TRANSACTION");
				//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
				$mensagem.= "<font color='#0000ff'>O documento nº <b>$documento</b> baixado com sucesso!</font>";
			}else{
				$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
				$mensagem.= "<font color='#ff0000'>Erro ao executar a baixa!</font>";
			}

		}else{
			$mensagem.= "<font color='#ff0000'>ERRO $msg_erro</font>";;
		}
	}
	
	//Atualiza os dados de contas a pagar
	if($acao=="alterar"){
		if (strlen ($vencimento) == 0) {
			$msg_erro .= '<br>Digite a data de vencimento.';
		}else{
			$vencimento = @converte_data($vencimento);
			$vencimento = "'$vencimento'";
			#$vencimento = "'" . substr ($vencimento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;
		}
		if(strlen($documento) == 0){
			$msg_erro .= 'Digite o número do documento.';
		}

		if(strlen($fornID) == 0){
			$msg_erro .= "<br>Selecione um Fornecedor.";
		}
		if(strlen($fatID) == 0){
			//faturamento vazio, entao só cadastra a nota fiscal			
			$fatID = "null";
		}

		if(strlen($valor) == 0){
			$msg_erro .= '<br>Digite o valor.';
		}else{
			$valor = str_replace(",",".",$valor);
			$valor = trim(str_replace(".00","",$valor));
		}
		
		if(strlen($msg_erro) == 0){
			$sql = "UPDATE tbl_pagar 
					SET 
							fornecedor = $fornID	,
							faturamento = $fatID    ,
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
		if (strlen ($vencimento) == 0) {
			$msg_erro .= '<br>Digite a data de vencimento.';
		}else{
			$vencimento = @converte_data($vencimento);
			$vencimento = "'$vencimento'";
			#$vencimento = "'" . substr ($vencimento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;
		}
		if(strlen($documento) == 0){
			$msg_erro .= 'Digite o número do documento.';
		}

		if(strlen($fornID) == 0){
			$msg_erro .= "<br>Selecione um Fornecedor.";
		}
		if(strlen($fatID) == 0){
			//faturamento vazio, entao só cadastra a nota fiscal			
			$fatID = "null";
		}

		if(strlen($valor) == 0){
			$msg_erro .= '<br>Digite o valor.';
		}else{
			$valor = str_replace(",",".",$valor);
			$valor = trim(str_replace(".00","",$valor));
		}
	
		if(strlen($msg_erro) == 0){
			//testa se a conta a pagar ja foi inserida para o respectivo fornecedor.
			$sql	= "SELECT documento
						FROM tbl_pagar 
						WHERE documento = '$documento' AND loja = $login_loja AND pessoa_fornecedor = $fornID;";
			//$mensagem.="sql: $sql";
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0 AND $documento!='-'){
				$mensagem.= "<font color='#ff0000'>Documento já foi inserido anteriormente!</font>";
			}else{



				$sql = "INSERT INTO tbl_pagar (
							loja           ,
							empresa,
							pessoa_fornecedor      ,
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
							$login_loja     ,
							$login_empresa  ,
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
	$resposta .="<form name='baixar_selecao' method='post' action='contas_pagar.php'>";
	$resposta .="<table border='0' cellpadding='10' cellspacing='0' style='' class='table_line' bordercolor='#d2e4fc'  align='center' width='750'>";
	$resposta .="<thead>";
	$resposta .="<tr >";
	$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>RELAÇÃO DAS CONTAS A PAGAR</b></font></td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Baixa</b></td>";
	$resposta .="<td><b>Fornecedor</b></td>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td align='center'><b>Faturamento</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Valor Hoje</b></td>";
	$resposta .="<td align='center'><b>Ações</b></td>";
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
			case when protesto is null then 'nao' else 'sim' end as protesto,
			case when current_date >= protesto then 'protestado' else 'aindanao' end as protesto2,
			valor_custas_cartorio,
			current_date - vencimento as dias_vencido
		FROM tbl_pagar
		join tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor
		WHERE tbl_pagar.loja = $login_loja
		AND tbl_pagar.empresa=$login_empresa
		AND pagamento IS null
		order by vencimento;";
			
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$cont_itens = pg_numrows($res);
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
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

			$protesto		= trim(pg_result($res, $i, protesto));
			$protesto_aux	= trim(pg_result($res, $i, protesto2));
			$valor_custas_cartorio	= trim(pg_result($res, $i, valor_custas_cartorio));

			$dias_vencido	= trim(pg_result($res, $i, dias_vencido));


			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

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
	
			$resposta .="<tr bgcolor='$cor' class='linha' id='linha_$i'>";

			$resposta .="<td nowrap align='center'><input type='checkbox' name='pagar[]' id='pagar$i' value='$pagar' onClick='calcula_total_selecionado($cont_itens)' class='check_normal'></td>";
			if (strlen($nome)>29) $nome = substr($nome,0,29)."...";
			$resposta .="<td nowrap align='left'>$fornecedor - $nome</td>";
			$resposta .="<td nowrap> <a href='#' onClick=\"exibirPagar('dados','$pagar','','mostra')\">$documento </a></td>";
			$resposta .="<td nowrap  align='center'>";
			if(strlen($faturamento) > 0)
				$resposta .= "$faturamento";
			else	$resposta .= "-";
			$resposta .="</td>";
			$resposta .="<td nowrap>$vencimento_</td>";
			$resposta .="<td nowrap align='right' onclick=\"javascript:selecionarLinha($i,'$cor')\" style='cursor:pointer'>";
			$resposta .="<input type='hidden' name='pagar_$i' id='pagar_$i' value='$valor_aux' align='right'>R$ $valor</td>";
			$resposta .="<td nowrap align='right'>R$ $valor_reajustado</td>";
			$resposta .="<td nowrap align='center'> <a href='#' onClick=\"exibirPagar('dados','$pagar','','mostra')\">Ver</a></td>";
			if($vencimento < date('Y-m-d')) $st="<font color='red'>vencido</font>";
			else $st="a vencer";
			$resposta .="<td nowrap align='center'> $st</td>";
			$resposta .="</tr>";
		}
		$resposta .="</tbody>";
		$resposta .="<foot>";
		$resposta .="<tr>";
		$resposta .="<td colspan='9' align='right'>";
		$resposta .="<b>Total:</b>";
		$resposta .="<input type='hidden' id='cont_itens' name='cont_itens' value='$cont_itens' size='4'> ";
  		$resposta .="<input type='text' id='resultado' name='resultado' size='10' value='0' class='frm' read-only> ";
		$resposta .="</td>";
		$resposta .="</tr>";

		$resposta .="<tr class='Titulo3'>";
		$resposta .="<td colspan='4' align='left'>";
		$resposta .="Data da Baixa:<input type='text' name='data_baixa' value='".date('d/m/Y')."' size='12' class='frm'> ";
		$resposta .="</td>";
		$resposta .="<td colspan='5' align='right'>";
		$resposta .="<input type='hidden' name='btn_acao' value=''> ";
		$resposta .="<input type='button' name='baixar_sel' value='Baixar Selecionados' class='frm' onclick=\"document.baixar_selecao.btn_acao.value='BAIXAR_LOTE';document.baixar_selecao.submit(); \"> ";
		$resposta .="</td>";
		$resposta .="</tr>";

//		$resposta .="<tr>";
//		$resposta .="<td colspan='9' align='right'>";
//		$resposta .="<input type='submit' name='baixar_sel' value='Baixar Selecionados' class='frm'> ";
//		$resposta .="</td>";
//		$resposta .="</tr>";
		$resposta .="</tfoot>";
	}else 
		$resposta .="<tr bgcolor='#F7F5F0'><td colspan='9' align='center'><b>Sem Contas a Pagar Pendentes!&nbsp;</b></td></tr>";
		$resposta .="</tbody>";
	$resposta .="</table>";
	$resposta .="</form>";

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
				documento                                      ,
				TO_CHAR(digitacao,'DD/MM/YYYY') as digitacao ,
				TO_CHAR(VENCIMENTO,'DD/MM/YYYY') as vencimento ,
				valor,
				obs                                            ,
				tbl_pessoa.nome									,
				tbl_pagar.pessoa_fornecedor,
				valor_multa,
				valor_juros_dia,
				valor_desconto,
				desconto_pontualidade,
				protesto - vencimento as protesto,
				valor_custas_cartorio,
				valor_multa,
				current_date - vencimento as dias_vencido
			FROM tbl_pagar
			JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor
			WHERE tbl_pagar.loja   = $login_loja
			AND   tbl_pagar.empresa= $login_empresa
			AND   tbl_pagar.pagar  = $conta_pagar";
			//echo "sql: $sql";
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$documento		= trim(pg_result($res, 0, documento));
				$fornecedor		= trim(pg_result($res, 0, pessoa_fornecedor));
				$nome			= trim(pg_result($res, 0, nome));
				$digitacao		= trim(pg_result($res, 0, digitacao));
				$vencimento		= trim(pg_result($res, 0, vencimento));
				$valor			= trim(pg_result($res, 0, valor));
				$valor_multa	= trim(pg_result($res, 0, valor_multa));
				$valor_juros_dia= trim(pg_result($res, 0, valor_juros_dia));
				$valor_desconto	= trim(pg_result($res, 0, valor_desconto));
				$desconto_pontualidade	= trim(pg_result($res, 0, desconto_pontualidade));
				$protesto		= trim(pg_result($res, 0, protesto));
				$valor_custas_cartorio	= trim(pg_result($res, 0, valor_custas_cartorio));
				$obs			= trim(pg_result($res, 0, obs));

				if (strlen($valor_custas_cartorio)==0){
					$valor_custas_cartorio=0;
				}

				// para calcular a quantidade a pagar com juros e multa
				$dias_vencido		= trim(pg_result($res, 0, dias_vencido));

				$valor_reajustado = $valor;
				$mora_multa = 0;

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

					$mora_multa += $valor_juros_dia*$dias_vencido;
					$mora_multa += $valor_multa;

				}

				$mora_multa			= number_format($mora_multa,2,'.','');
				$valor_reajustado	= number_format($valor_reajustado,2,'.','');
				$valor				= number_format($valor,2,'.','');

				$valor_multa		= number_format($valor_multa,2,'.','');
				$valor_juros_dia	= number_format($valor_juros_dia,2,'.','');
				$valor_desconto		= number_format($valor_desconto,2,'.','');

				if(strpos($documento, "-")>0){
					$nf= substr($documento, 0, strpos($documento, "-"));
					$doc= substr($documento, (strpos($documento, "-")+1), strlen($documento));
				}else{
					$nf= $documento;
				}


				echo  "ok|$nf|$doc|$fornecedor|$nome|$valor|$vencimento|$obs|$conta_pagar|$valor_multa|$valor_juros_dia|$valor_desconto|$desconto_pontualidade|$protesto|$valor_custas_cartorio|$valor_reajustado|$digitacao|$mora_multa";
			}
		exit;
	}
}
?>