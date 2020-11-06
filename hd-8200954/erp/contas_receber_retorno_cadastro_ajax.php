<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'ajax_cabecalho.php';

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
	$fornID			= $_GET["fornID"];//fornecedor
	$fatID			= $_GET["fatID"]; //faturamento
	$documento		= $_GET["documento"];
	$valor			= $_GET["valor"];
	$acao			= $_GET["acao"];
	$valor_pago		= $_GET["valor_pago"];
	$conta_receber	= $_GET["conta_receber"];
	$vencimento		= $_GET["vencimento"];
	$pagamento		= $_GET["pagamento"];
	$obs			= $_GET["obs"];

	$multa_p		= $_GET["multa_p"];
	$multa_valor	= $_GET["multa_valor"];
	$juros_mora_p	= $_GET["juros_mora_p"];
	$juros_mora_valor= $_GET["juros_mora_valor"];
	$desconto		= $_GET["desconto"];
	$desconto_p		= $_GET["desconto_p"];
	$desconto_pontualidade	 = $_GET["desconto_pontualidade"];
	$protestar		 = $_GET["protestar"];
	$valor_custas_cartorio	 = $_GET["valor_custas_cartorio"];

	// opções diversas
	$dividir		= $_GET["dividir"];
	$quitar			= $_GET["quitar"];
	

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

		

	//Da baixa no conta a receber selecionado
	if($acao=="baixar"){
		if(strlen($conta_receber) == 0){
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

			$sql	= "SELECT documento,
							to_char(emissao,'DD/MM/YYYY') as emissao,
							to_char(vencimento,'DD/MM/YYYY') as vencimento,
							valor,
							valor_dias_atraso,
							to_char(recebimento,'DD/MM/YYYY') as recebimento,
							valor_recebido,
							tbl_contas_receber.status,
							faturamento_fatura,
							obs,
							tbl_contas_receber.posto,
							fabrica,
							cliente
				FROM tbl_contas_receber 
				JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
				WHERE tbl_contas_receber.contas_receber = $conta_receber
				AND tbl_contas_receber.posto=$login_loja OR tbl_contas_receber.distribuidor = $login_loja";
			$res	= pg_exec($con,$sql);

			$receber_documento		= trim(pg_result($res, 0, documento));
			$receber_emissao		= trim(pg_result($res, 0, emissao));
			$receber_vencimento		= trim(pg_result($res, 0, vencimento));
			$receber_valor			= trim(pg_result($res, 0, valor));
			$receber_recebimento	= trim(pg_result($res, 0, recebimento));
			$receber_valor_recebido	= trim(pg_result($res, 0, valor_recebido));
			$receber_status			= trim(pg_result($res, 0, status));
			$receber_faturamento	= trim(pg_result($res, 0, faturamento_fatura));
			$receber_obs			= trim(pg_result($res, 0, obs));
			$receber_posto			= trim(pg_result($res, 0, posto));
			$receber_fabrica		= trim(pg_result($res, 0, fabrica));
			$receber_cliente		= trim(pg_result($res, 0, cliente));

			if(strlen($receber_recebimento)==0){
				// para calcular a quantidade a receber com juros e multa
				$sql = "UPDATE tbl_contas_receber
							SET 
							valor_recebido	= $valor_pago ,
							obs		= '$obs',
							recebimento	= $pagamento
						WHERE contas_receber = $conta_receber";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}else{
				$mensagem.= "<font color='#ff0000'>Erro: O documento de nº <b>$receber_documento</b> já foi dado baixa anteriomente em $data_pagamento</font>";	
			}

			if (strlen($msg_erro) == 0) {
				$resX = pg_exec ($con,"COMMIT TRANSACTION");
				//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
				$mensagem.= "<font color='#0000ff'>O documento nº <b>$receber_documento</b> baixado com sucesso!</font>";
			}else{
				$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
				$mensagem.= "<font color='#ff0000'>Erro ao executar a baixa!</font>";
			}

		}else{
			$mensagem.= "<font color='#ff0000'>ERRO $msg_erro</font>";;
		}
	}
	
	//Atualiza os dados de contas a receber
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
			$sql = "UPDATE tbl_contas_receber 
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
						WHERE receber = $conta_receber;";
			$res = pg_exec($con,$sql);
			if(pg_result_error($res))
				$mensagem.= "<font color='#ff0000'>Erro ao executar a baixa!</font>";
			else
				$mensagem.= "<font color='#0000ff'>O documento nº <b>$documento</b> foi alterado com sucesso!</font>";		
		}else{
			$mensagem.= "<font color='#ff0000'>ERRO $msg_erro</font>";;
		}	
	}

	//Inserir uma conta a receber
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
			//testa se a conta a receber ja foi inserida para o respectivo fornecedor.
			$sql	= "select documento
						from tbl_contas_receber 
						where documento = '$documento' and posto = $login_posto and fornecedor = $fornID;";
			//$mensagem.="sql: $sql";
			$res	= pg_exec($con,$sql);
			if(pg_numrows($res)>0 AND $documento!='-'){
				$mensagem.= "<font color='#ff0000'>Documento já foi inserido anteriormente!</font>";
			}else{



				$sql = "INSERT INTO tbl_contas_receber (
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
					$mensagem .= "<font color='#ff0000'>Erro: ao inserir conta a receber!</font>";
				else
					$mensagem .= "<font color='#0000ff'>Cadastro efetuado com sucesso para o documento nº <b>$documento</b></font>";

				$conta_receber	= '';
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
	$resposta .="<form name='baixar_selecao' method='post' action='contas_receber.php'>";
	$resposta .="<table border='1' cellpadding='2' cellspacing='0'  bordercolor='#d2e4fc'  align='center' width='700px' class='table_line'>";
		$resposta .= "<form metohd='post' name='baixar_lote' action='$PHP_SELF' onsubmit=\"this.form.baixar_em_lote.value='sim'\">";
		$resposta .="<input type='hidden' name='baixar_em_lote'>";
		$resposta .="<thead>";
		$resposta .="<tr >";
		$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>RELAÇÃO DAS CONTAS A RECEBER</b></font></td>";
		$resposta .="</tr>";
		$resposta .="<tr class='Titulo2'>";
		$resposta .="<td><b>Baixa</b></td>";
		$resposta .="<td><b>Cliente</b></td>";
		$resposta .="<td><b>Documento</b></td>";
		$resposta .="<td><b>Faturamento</b></td>";
		$resposta .="<td><b>Vencimento</b></td>";
		$resposta .="<td align='center'><b>Valor</b></td>";
		$resposta .="<td align='center'><b>Ações</b></td>";
		$resposta .="<td align='center'><b>Status</b></td>";
		$resposta .="</tr>";
		$resposta .="</head>";

	$resposta .="<tbody>";
	//SELECIONA AS CONTAS A receber QUE ESTÃO PENDENTES (Q NAO FORAM PAGAS)	
	$sql	= "SELECT 
					tbl_contas_receber.contas_receber,
					tbl_contas_receber.documento,
					to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') as emissao,
					to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
					tbl_contas_receber.vencimento as vencimento_bd,
					tbl_contas_receber.valor,
					tbl_contas_receber.valor_dias_atraso,
					to_char(tbl_contas_receber.recebimento,'DD/MM/YYYY') as recebimento,
					tbl_contas_receber.valor_recebido,
					tbl_contas_receber.status,
					tbl_contas_receber.faturamento_fatura,
					tbl_contas_receber.obs,
					tbl_contas_receber.posto,
					tbl_contas_receber.distribuidor,
					tbl_contas_receber.fabrica,
					tbl_contas_receber.cliente,
					tbl_pessoa.nome
		FROM tbl_contas_receber 
		JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
		LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
		WHERE  (tbl_contas_receber.posto=$login_loja OR
				tbl_contas_receber.distribuidor=$login_loja
						) 
		AND tbl_contas_receber.recebimento IS NULL
		AND tbl_contas_receber.valor_recebido IS NULL
		ORDER BY tbl_contas_receber.posto, tbl_contas_receber.vencimento ASC";
	$res	= pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$cont_itens= pg_numrows($res);
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			
			$receber_receber		= trim(pg_result($res, $i, contas_receber));
			$receber_documento		= trim(pg_result($res, $i, documento));
			$receber_emissao		= trim(pg_result($res, $i, emissao));
			$receber_vencimento		= trim(pg_result($res, $i, vencimento));
			$receber_vencimento_bd	= trim(pg_result($res, $i, vencimento_bd));
			$receber_valor			= trim(pg_result($res, $i, valor));
			$receber_recebimento	= trim(pg_result($res, $i, recebimento));
			$receber_valor_recebido	= trim(pg_result($res, $i, valor_recebido));
			$receber_status			= trim(pg_result($res, $i, status));
			$receber_faturamento	= trim(pg_result($res, $i, faturamento_fatura));
			$receber_obs			= trim(pg_result($res, $i, obs));
			$receber_posto			= trim(pg_result($res, $i, posto));
			$receber_fabrica		= trim(pg_result($res, $i, fabrica));
			$receber_cliente		= trim(pg_result($res, $i, cliente));
			$receber_cliente_nome	= trim(pg_result($res, $i, nome));
			$rebecer_distribuidor	= trim(pg_result($res, $i, distribuidor));
			if(strlen(receber_distribuidor)>0){
				$sql2="SELECT tbl_posto.nome, tbl_posto.fone, tbl_posto.email, rec.qtde, rec.valor	
						FROM   tbl_posto
						LEFT JOIN  (SELECT posto, COUNT(*) AS qtde, SUM (valor) AS valor
							FROM  tbl_contas_receber
							WHERE posto = $receber_posto
							AND   distribuidor = $rebecer_distribuidor
							AND   recebimento IS NULL
							AND   vencimento < CURRENT_DATE
							GROUP BY posto) rec ON tbl_posto.posto = rec.posto
						WHERE tbl_posto.posto = $receber_posto ";
				$res2 = pg_exec ($con,$sql2);
				$receber_cliente_nome = trim(pg_result($res2, nome));
				if(strlen($receber_cliente_nome) == 0) $receber_cliente_nome = "Não achou o cliente!";
				$hint = $receber_posto.trim(pg_result($res2, fone))."-".trim(pg_result($res2, email))."- Qtd=".trim(pg_result($res2, qtde))." Valor=".trim(pg_result($res2, valor));
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			$receber_valor = str_replace(",",".",$receber_valor);
	
			$resposta .="<tr bgcolor='$cor' class='linha'  id='linha_$i'>";

			//checkbox
			$resposta .="<td nowrap align='center'><input type='checkbox' name='receber[]' id='receber_receber$i' value='$receber_receber' onClick='calcula_total_selecionado($cont_itens)' class='check_normal'></td>";

			//nome
			if (strlen($receber_cliente_nome)>29) $receber_cliente_nome = substr($receber_cliente_nome,0,29)."...";
			$resposta .="<td nowrap title='$hint' align='left'>$receber_cliente_nome</td>";

			//documento
			$resposta .="<td nowrap align='center'> <a href='#' onClick=\"exibirreceber('dados','$receber_receber','','mostra')\">$receber_documento </a></td>";

			$resposta .="<td nowrap>&nbsp;";
			$resposta .="</td>";
			
			//vencimento
			$resposta .="<td nowrap align='center'>$receber_vencimento</td>";

			// valor
			$resposta .="<td nowrap align='right' onclick=\"javascript:selecionarLinha($i,'$cor')\" style='cursor:pointer' align='right'><input type='hidden' name='receber_$i' id='receber_$i' value='$valor_aux'>R$ $receber_valor";
			$resposta .="</td>";

			//acoes
			$resposta .="<td nowrap align='center'> <a href='#' onClick=\"exibirreceber('dados','$receber_receber','','mostra')\">Ver</a></td>";
			if($receber_vencimento_bd < date('Y-m-d')) $st="<font color='red'>Vencido</font>";
			else $st="A vencer";
			$resposta .="<td nowrap align='center'>$st</td>";
			$resposta .="</tr>";
		}
		$resposta .="</tbody>";

		$resposta .="<foot>";
		$resposta .="<tr>";
		$resposta .="<td colspan='8' align='right'>";
		$resposta .="<b>Total:</b>";
		$resposta .="<input type='hidden' id='cont_itens' name='cont_itens' value='$cont_itens' size='4'> ";
		$resposta .="<input type='text' id='resultado' name='resultado' size='10' value='0' class='frm' read-only> ";

		$resposta .="<tr class='Titulo3'>";
		$resposta .="<td colspan='8' align='left'>";
		$resposta .="Data da Baixa:<input type='text' name='data_baixa' value='".date('d/m/Y')."' size='12' class='frm'> ";
		$resposta .="<input type='hidden' name='btn_acao' value=''> ";
		$resposta .="<input type='button' name='baixar_sel' value='Baixar Selecionados' class='frm' onclick=\"document.baixar_selecao.btn_acao.value='BAIXAR_LOTE';document.baixar_selecao.submit(); \"> ";
		$resposta .="</td>";
		$resposta .="</tr>";

		$resposta .="</tfoot>";
	}else {
		$resposta .="<tr bgcolor='#F7F5F0'><td colspan='8' align='center'><b>Sem Contas a receber Pendentes!&nbsp;</b></td></tr>";
	}
	$resposta .="</form>";
	$resposta .="</tbody>";
	$resposta .="</table>";

	echo  "ok|".$resposta. "|$mensagem";

	exit;
}else{
	$conta_receber	= $_GET["conta_receber"];
	$acao			= $_GET["acao"];
	
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
		$sql="SELECT
					tbl_contas_receber.contas_receber,
					tbl_contas_receber.documento,
					to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') as emissao,
					to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
					tbl_contas_receber.vencimento as vencimento_bd,
					tbl_contas_receber.valor,
					tbl_contas_receber.valor_dias_atraso,
					to_char(tbl_contas_receber.recebimento,'DD/MM/YYYY') as recebimento,
					current_date - tbl_contas_receber.vencimento as dias_vencido,
					tbl_contas_receber.valor_recebido,
					tbl_contas_receber.status,
					tbl_contas_receber.faturamento_fatura,
					tbl_contas_receber.obs,
					tbl_contas_receber.posto,
					tbl_contas_receber.fabrica,
					tbl_contas_receber.cliente,
					tbl_contas_receber.distribuidor,
					tbl_pessoa.nome,
					tbl_posto.nome as cedente
		FROM tbl_contas_receber 
		JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
		LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
		WHERE  tbl_contas_receber.contas_receber=$conta_receber";
		$res	= pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$receber_receber		= trim(pg_result($res, 0, contas_receber));
			$receber_documento		= trim(pg_result($res, 0, documento));
			$receber_emissao		= trim(pg_result($res, 0, emissao));
			$receber_vencimento		= trim(pg_result($res, 0, vencimento));
			$receber_vencimento_bd	= trim(pg_result($res, 0, vencimento_bd));
			$receber_dias_vencido	= trim(pg_result($res, 0, dias_vencido));
			$receber_valor			= trim(pg_result($res, 0, valor));
			$receber_valor_dias_atraso= trim(pg_result($res, 0, valor_dias_atraso));
			$receber_recebimento	= trim(pg_result($res, 0, recebimento));
			$receber_valor_recebido	= trim(pg_result($res, 0, valor_recebido));
			$receber_status			= trim(pg_result($res, 0, status));
			$receber_faturamento	= trim(pg_result($res, 0, faturamento_fatura));
			$receber_obs			= trim(pg_result($res, 0, obs));
			$receber_posto			= trim(pg_result($res, 0, posto));
			$receber_fabrica		= trim(pg_result($res, 0, fabrica));
			$receber_cliente		= trim(pg_result($res, 0, cliente));
			$receber_cliente_nome	= trim(pg_result($res, 0, nome));
			$receber_valor			= number_format($receber_valor,2,'.','');
			$mora_multa				= number_format($mora_multa,2,'.','');
			$valor_reajustado		= number_format($valor_reajustado,2,'.','');
			$valor_multa			= number_format($valor_multa,2,'.','');
			$valor_juros_dia		= number_format($valor_juros_dia,2,'.','');
			$valor_desconto			= number_format($valor_desconto,2,'.','');

			// para calcular a quantidade a receber com juros e multa
			$valor_reajustado = $receber_valor;
			$mora_multa = 0;

			if ($desconto_pontualidade<>'t'){
//				$valor_reajustado -= $valor_desconto;
			}

			if ($receber_dias_vencido<=0){
//				$valor_reajustado -= $valor_desconto;
			}

			if ($receber_dias_vencido>0){
				$valor_reajustado += $valor_multa;
				$valor_reajustado += $valor_juros_dia*$receber_dias_vencido;
				$valor_reajustado += $valor_custas_cartorio;
				$mora_multa += $receber_valor_dias_atraso*$receber_dias_vencido;
				$mora_multa += $valor_multa;
			}

			$valor_total = $receber_valor+$mora_multa;

			$mora_multa			= number_format($mora_multa,2,'.','');
			$valor_reajustado	= number_format($valor_reajustado,2,'.','');
			$valor				= number_format($valor,2,'.','');

			$valor_multa		= number_format($valor_multa,2,'.','');
			$valor_juros_dia	= number_format($valor_juros_dia,2,'.','');
			$valor_desconto		= number_format($valor_desconto,2,'.','');

			$valor_total		= number_format($valor_total,2,'.','');

			if(strpos($receber_documento, "-")>0){
				$nf = substr($receber_documento, 0, strpos($receber_documento, "-"));
				$doc= substr($receber_documento, (strpos($receber_documento, "-")+1), strlen($receber_documento));
			}else{
				$nf= $receber_documento;
			}
			echo "ok|$nf|$doc|$receber_cliente|$receber_cliente - $receber_cliente_nome|$receber_valor|$receber_vencimento|$receber_obs|$receber_receber|$receber_valor_dias_atraso|$receber_status|$receber_emissao|$cedente|$valor_total|$mora_multa";
		}
	exit;
	}
}
?>