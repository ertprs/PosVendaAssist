<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'ajax_cabecalho.php';




//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {

	$data  = $_GET["data"];
	$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data')");
	if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	

	if (strlen($msg_erro) == 0){
		$data = @pg_result ($fnc,0,0);
	}

	$cond1 = " AND vencimento = '$data'";
	$cond2 = "";
	$cond3 = " AND vencimento = '$data'";
/*
	$sql = "SELECT  a.documento                            ,
			a.nome                                 ,
			a.data_baixa                           ,
			a.total_recebido      AS total_recebido,
			a.total_pago          AS total_pago 
		FROM (
			(
				SELECT  documento                                            ,
					TO_CHAR(recebimento,'DD/MM/YYYY') AS data_baixa      ,
					valor_recebido                    AS total_recebido  ,
					null                              AS total_pago      ,
					tbl_pessoa.nome
				FROM tbl_contas_receber
				JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
				WHERE fabrica = $login_empresa
				AND   tbl_contas_receber.recebimento = '$data'
			) UNION (
				SELECT  documento                                         ,
					TO_CHAR(pagamento ,'DD/MM/YYYY') AS data_baixa    ,
					null                             AS total_recebido,
					valor_pago                       AS total_pago    ,
					tbl_pessoa.nome
				FROM tbl_pagar 
				JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor 
				WHERE tbl_pagar.empresa = $login_empresa 
				AND   tbl_pagar.pagamento = '$data'
			)
		) AS a 
		WHERE (total_recebido > 0 OR total_pago > 0)";
	
		$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
	
		$resposta .= "<P><font size='2'><b>FLUXO";
		$resposta .= "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='700'>";
		$resposta .= "<TR  height='20' bgcolor='#DDDDDD' align='center'>";
		$resposta .= "<TD><b></b></TD>";
		$resposta .= "<TD align='left'><b>Cliente</b></TD>";
		$resposta .= "<TD ><b>Documento</b></TD>";
		$resposta .= "<TD width='25'><b>Vencimento</b></TD>";
		$resposta .= "<TD width='100' align='right'><b>Valor</b></TD>";

		$resposta .= "</TR>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$x=$i+1;
			$documento         = pg_result($res,$i,documento);
			$data_baixa        = pg_result($res,$i,data_baixa);
			$total_recebido    = pg_result($res,$i,total_recebido);
			$total_pago        = pg_result($res,$i,total_pago);
			$nome              = pg_result($res,$i,nome);

			if(strlen($total_recebido)>0){
				$saldo = $total_recebido;
				$aux = "<font color='#009900'>E</font>";
			}
			else{
				$saldo = $total_pago;
				$aux = "<font color='#FF0000'>S</font>";
			}

			$saldo  = number_format($saldo,2,',','.');
	
			if($cor1=="#EEEEEE")$cor1 = '#FFFFFF';
			else                $cor1 = '#EEEEEE';
	

	
			$resposta .= "<TR bgcolor='$cor1'class='Conteudo'>";
			$resposta .= "<TD align='center'>$aux</TD>";
			$resposta .= "<TD align='left'>$nome</TD>";
			$resposta .= "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
			$resposta .= "<TD align='center'nowrap>$data_baixa</TD>";
			$resposta .= "<TD align='right'nowrap>$saldo</TD>";
			$resposta .= "</TR>";
	
		}
		$resposta .= " </TABLE>";
	
	}else{
		$resposta .= "<b>Nenhuma conta a receber neste perídodo</b>";
	}
*/

	$sql = "SELECT  contas_receber                                       ,
			documento                                            ,
			TO_CHAR(vencimento ,'DD/MM/YYYY') AS data_vencimento ,
			TO_CHAR(recebimento,'DD/MM/YYYY') AS data_recebimento,
			valor                                                ,
			valor_dias_atraso                                    ,
			valor_recebido                                       ,
			tbl_pessoa.nome                                      ,
			case when current_date - vencimento >0 then current_date - vencimento  else 0 end as dias_vencido
		FROM tbl_contas_receber
		LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
		WHERE fabrica = $login_empresa
		AND   posto   = $login_loja
		AND   tbl_contas_receber.recebimento IS NULL
		$cond1
		ORDER BY vencimento,nome
		";
	
		$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
	
		$resposta .= "<P><font size='2'><b>Contas a Receber";
		$resposta .= "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='700'>";
		$resposta .= "<TR  height='20' bgcolor='#DDDDDD' align='center'>";
		$resposta .= "<TD ><b>Cliente</b></TD>";
		$resposta .= "<TD ><b>Documento</b></TD>";
		$resposta .= "<TD width='25'><b>Vencimento</b></TD>";
		$resposta .= "<TD width='100' align='right'><b>Valor</b></TD>";
		$resposta .= "<TD width='100' align='right'><b>Valor hoje</b></TD>";
		$resposta .= "</TR>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$x=$i+1;
			$contas_receber    = pg_result($res,$i,contas_receber);
			$documento         = pg_result($res,$i,documento);
			$data_vencimento   = pg_result($res,$i,data_vencimento);
			$data_recebimento  = pg_result($res,$i,data_recebimento);
			$valor             = pg_result($res,$i,valor);
			$valor_dias_atraso = pg_result($res,$i,valor_dias_atraso);
			$valor_recebido    = pg_result($res,$i,valor_recebido);
			$nome              = pg_result($res,$i,nome);
			$dias_vencido      = pg_result($res,$i,dias_vencido);

			$valor_reajustado = $valor;
			if ($dias_vencido>0){
				$valor_reajustado += $valor_multa;
				$valor_reajustado += $valor_juros_dia*$dias_vencido;
				$valor_reajustado += $valor_custas_cartorio;
			}

			$valor             = number_format($valor,2,',','.');
			$valor_dias_atraso = number_format($valor_dias_atraso,2,',','.');
			$valor_reajustado  = number_format($valor_reajustado,2, ',', '');
	
			if($cor1=="#EEEEEE")$cor1 = '#FFFFFF';
			else                $cor1 = '#EEEEEE';

			if(strlen($data_recebimento)>0) $situacao = "<img src='imagens/status_verde.gif'> Recebido";
			else                            $situacao = "<img src='imagens/status_vermelho.gif'> A Receber";

			$resposta .= "<TR bgcolor='$cor1'class='Conteudo'>";
			$resposta .= "<TD align='left'>$nome</TD>";
			$resposta .= "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
			$resposta .= "<TD align='center'nowrap>$data_vencimento</TD>";
			$resposta .= "<TD align='right'nowrap>$valor</TD>";
			$resposta .= "<TD align='right'nowrap>$valor_reajustado</TD>";
			$resposta .= "</TR>";
	
		}
		$resposta .= " </TABLE>";
	
	}else{
		$resposta .= "<b>Nenhuma conta a receber neste perídodo</b>";
	}
	
	$sql = "SELECT  pagar                                       ,
			documento                                            ,
			TO_CHAR(vencimento,'DD/MM/YYYY') AS data_vencimento  ,
			TO_CHAR(pagamento ,'DD/MM/YYYY') AS data_pagamento   ,
			valor                                                ,
			valor_pago                                           ,
			valor_juros_dia                                      ,
			valor_multa                                          ,
			valor_desconto                                       ,
			desconto_pontualidade                                ,
			tbl_pessoa.nome                                      ,
			case when current_date - vencimento >0 then current_date - vencimento  else 0 end as dias_vencido
		FROM tbl_pagar
		JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor
		WHERE tbl_pagar.empresa = $login_empresa
		AND   tbl_pagar.pagamento IS NULL
		$cond3
		ORDER BY tbl_pagar.vencimento,nome
		";
	
	//$resposta .= $sql;
	
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
	
		$resposta .= "<P><font size='2'><b>Contas a Pagar";
		$resposta .= "<table border='0' cellpadding='2' cellspacing='0'  align='center' width='700' class='HD'>";
		$resposta .= "<TR  height='20' bgcolor='#DDDDDD' align='center'>";
		$resposta .= "<TD align='left'><b>Cliente</b></TD>";
		$resposta .= "<TD align='left'><b>Documento</b></TD>";
		$resposta .= "<TD width='25' ><b>Vencimento</b></TD>";
		$resposta .= "<TD width='100' align='right'><b>Valor</b></TD>";
		$resposta .= "<TD width='100' align='right'><b>Valor hoje</b></TD>";
		$resposta .= "</TR>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$x=$i+1;
			$pagar                 = pg_result($res,$i,pagar);
			$documento             = pg_result($res,$i,documento);
			$data_vencimento       = pg_result($res,$i,data_vencimento);
			$data_pagamento        = pg_result($res,$i,data_pagamento);
			$valor                 = pg_result($res,$i,valor);
			$valor_pago            = pg_result($res,$i,valor_pago);
			$valor_multa           = pg_result($res,$i,valor_multa);
			$valor_juros_dia       = pg_result($res,$i,valor_juros_dia);
			$valor_desconto        = pg_result($res,$i,valor_desconto);
			$desconto_pontualidade = pg_result($res,$i,desconto_pontualidade);
			$dias_vencido          = pg_result($res,$i,dias_vencido);
			$nome                  = pg_result($res,$i,nome);

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


			$valor            = number_format($valor,2,',','.');
			$valor_pago       = number_format($valor_pago,2,',','.');
			$valor_reajustado = number_format($valor_reajustado,2, ',', '');

			if($cor1=="#EEEEEE")$cor1 = '#FFFFFF';
			else                $cor1 = '#EEEEEE';

			$resposta .= "<TR bgcolor='$cor1'class='Conteudo'>";
			$resposta .= "<TD align='left'>$nome</TD>";
			$resposta .= "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
			$resposta .= "<TD align='center'nowrap>$data_vencimento</TD>";
			$resposta .= "<TD align='right'nowrap>$valor</TD>";
			$resposta .= "<TD align='right'nowrap>$valor_reajustado</TD>";
	
			$resposta .= "</TR>";
	
		}
		$resposta .= " </TABLE>";
	
	}else{
		$resposta .= "<br><b>Nenhuma conta a pagar neste perídodo</b>";
	}


	echo  "ok|$hd_chamado|".$resposta."<p>";
	exit;
}

?>
