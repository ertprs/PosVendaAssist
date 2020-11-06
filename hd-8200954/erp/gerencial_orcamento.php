<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

include "menu.php";

$btn_acao = $_POST["btn_acao"];

$cond1 = " AND vencimento >= current_date";
$cond2 = "";
$cond3 = " AND vencimento >= current_date";
$cond4 = "";

if(strlen($btn_acao)>0){
	$de        = trim($_POST["de"]);
	$ate       = trim($_POST["ate"]);
	$situacao  = trim($_POST["situacao"]);
	$tipo_data = trim($_POST["tipo_data"]);

	//TRATANDO PRIMEIRA DATA
	if(strlen($de)>0){
		if (strlen($de) == 0) $msg_erro .= "Favor informar a data para pesquisa<br>";
		if (strlen($de) > 10) $msg_erro .= "Tamanho incorreto para data<br>";
		
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$de')");
		if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	
	
		if (strlen($msg_erro) == 0){
			$aux_de = @pg_result ($fnc,0,0);
		}

	}

	//TRATANDO SEGUNDA DATA
	if(strlen($ate)>0){
		if (strlen($ate) == 0) $msg_erro .= "Favor informar a data para pesquisa<br>";
		if (strlen($ate) > 10) $msg_erro .= "Tamanho incorreto para data<br>";
		
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$ate')");
		if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	
	
		if (strlen($msg_erro) == 0){
			$aux_ate = @pg_result ($fnc,0,0);
		}

	}

	if($tipo_data == "pagamento"){
		$cond1 = " AND recebimento BETWEEN '$aux_de' AND '$aux_ate' ";
	}

	if($tipo_data == "emissao"){
		$cond1 = " AND emissao BETWEEN '$aux_de' AND '$aux_ate' ";
	}

/*

*/
	if($situacao == "aberto"){
		$cond2 = " AND recebimento IS NULL";
	}
	if($situacao == "pago"){
		$cond2 = " AND recebimento IS NOT NULL";
		
	}
	echo $situcao;
}

$sql = "SELECT (
		SELECT sum(valor_recebido) 
		FROM tbl_contas_receber 
		WHERE fabrica = $login_empresa 
		AND   recebimento IS NOT NULL
		$cond1
	) AS valor_recebeu      ,
	(
		SELECT sum(valor)
		FROM tbl_contas_receber 
		WHERE fabrica = $login_empresa 
		AND   recebimento IS NULL
		$cond1
	) AS valor_receber      ,
	(
		SELECT sum(valor_pago) 
		FROM tbl_pagar 
		WHERE empresa = $login_empresa 
		AND   pagamento IS NOT NULL
		$cond3
	) AS valor_pago      ,
	(
		SELECT sum(valor) 
		FROM tbl_pagar
		WHERE empresa = $login_empresa 
		AND   pagamento IS NULL
		$cond3
	) AS valor_pagar";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$valor_recebeu  = pg_result($res,0,valor_recebeu);
	$valor_receber  = pg_result($res,0,valor_receber);
	$valor_pago     = pg_result($res,0,valor_pago);
	$valor_pagar    = pg_result($res,0,valor_pagar);

	$total_caixa = $valor_recebeu - $valor_pago;

	$valor_recebeu = number_format($valor_recebeu,2,',','.');
	$valor_receber = number_format($valor_receber,2,',','.');
	$valor_pago    = number_format($valor_pago,2,',','.');
	$valor_pagar   = number_format($valor_pagar,2,',','.');
	$total_caixa   = number_format($total_caixa,2,',','.');
}

if(strlen($de)==0)  $de  = date("d/m/Y");
if(strlen($ate)==0) $ate = date("d/m/Y");

echo "<table width='750' bgcolor='#ffffff'>";
echo "<tr>";
echo "<td>";
	echo "<form name='busca' action='$PHP_SELF' method='POST'>";
	echo "<table>";
	echo "<tr class='Conteudo'>";
	echo "<td>Período</td>";
	echo "<td><input name='de' value='$de' type='text' size='10' maxlength='10' class='Caixa'> até <input name='ate' value='$ate' type='text' size='10' maxlength='10' class='Caixa'></td>";
	echo "</tr>";
	echo "<tr class='Conteudo'>";
	echo "<td>Situação</td>";
	echo "<td>";
	echo "<select name='situacao' class='Caixa'>";
	echo "<option value='todos'>Aberto e Pago</option>";
	echo "<option value='aberto'";
	if($situacao == "aberto") echo "SELECTED";
	echo ">Aberto</option>";
	echo "<option value='pago'";
	if($situacao == "pago") echo "SELECTED";
	echo ">Pago</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr class='Conteudo'>";
	echo "<td >Análise por</td>";
	echo "<td>";
	echo "<select name='tipo_data' class='Caixa'>";
	echo "<option value='movimento'";
	if($tipo_data == "movimento") echo "SELECTED";
	echo ">Data movimento</option>";
	echo "<option value='pagamento'";
	if($tipo_data == "pagamento") echo "SELECTED";
	echo ">Data pagamento</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr class='Conteudo'>";
	echo "<td colspan='2' align='center'><input name='btn_acao' value='Ver relatório' type='submit'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

echo "</td>";

echo "<td align='right'>";

	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='300'>";
	echo "<tr bgcolor='#dddddd' border='0'>";
	echo "<td><b>Conta</b></td>";
	echo "<td align='right'><b>Valor</b></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo' >";
	echo "<td>Valor Recebido</td>";
	echo "<td align='right'>$valor_recebeu</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF'  class='Conteudo'>";
	echo "<td>Valor a Receber</td>";
	echo "<td align='right'>$valor_receber</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Valor Pago</td>";
	echo "<td align='right'>$valor_pago</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Total a Pagar</td>";
	echo "<td align='right'>$valor_pagar</td>";
	echo "</tr>";
 	echo "<tr bgcolor='#dddddd' >";
	echo "<td><b>TOTAL</b></td>";
	echo "<td align='right'><b>$total_caixa</b></td>";
	echo "</tr>";


	echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";

$sql = "SELECT  contas_receber                                       ,
		documento                                            ,
		TO_CHAR(emissao    ,'DD/MM/YYYY') AS data_emissao    ,
		TO_CHAR(recebimento,'DD/MM/YYYY') AS data_recebimento,
		valor                                                ,
		valor_dias_atraso                                    ,
		valor_recebido                                       ,
		tbl_pessoa.nome
	FROM tbl_contas_receber
	JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
	WHERE fabrica = $login_empresa
	AND   posto   = $login_loja
	$cond1
	$cond2
	ORDER BY data_emissao,nome
	";

	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {

		echo "<P><font size='2'><b>Contas a Receber";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='750'>";
		echo "<TR class='HD' height='20' bgcolor='$cor' align='center'>";
		echo "<TD ><b>Cliente</b></TD>";
		echo "<TD ><b>Documento</b></TD>";
		echo "<TD width='25'><b>Emissão</b></TD>";
		echo "<TD width='25'><b>Recebimento</b></TD>";
		echo "<TD width='70'><b>Valor</b></TD>";
		echo "<TD width='70'><b>Dias de Atraso</b></TD>";
		echo "<TD width='70'><b>Valor Recebido</b></TD>";
		echo "<TD ><b>Situação</b></TD>";
		echo "</TR>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$x=$i+1;
			$contas_receber    = pg_result($res,$i,contas_receber);
			$documento         = pg_result($res,$i,documento);
			$data_emissao      = pg_result($res,$i,data_emissao);
			$data_recebimento  = pg_result($res,$i,data_recebimento);
			$valor             = pg_result($res,$i,valor);
			$valor_dias_atraso = pg_result($res,$i,valor_dias_atraso);
			$valor_recebido    = pg_result($res,$i,valor_recebido);
			$nome              = pg_result($res,$i,nome);

			$valor             = number_format($valor,2,',','.');
			$valor_dias_atraso = number_format($valor_dias_atraso,2,',','.');
			$valor_recebido    = number_format($valor_recebido,2,',','.');

			if($cor=="#fafafa")$cor1 = '#fdfdfd';
			else               $cor1 = '#fafafa';

			if(strlen($data_recebimento)>0) $situacao = "<img src='imagens/status_verde.gif'> Recebido";
			else                            $situacao = "<img src='imagens/status_vermelho.gif'> A Receber";

			echo "<TR bgcolor='$cor1'class='Conteudo'>";
			echo "<TD align='left'>$nome</TD>";
			echo "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
			echo "<TD align='center'nowrap>$data_emissao</TD>";
			echo "<TD align='center'nowrap>$data_recebimento</TD>";
			echo "<TD align='right'nowrap>$valor</TD>";
			echo "<TD align='right'nowrap>$valor_dias_atraso</TD>";
			echo "<TD align='right'nowrap>$valor_recebido</TD>";
			echo "<TD align='center'nowrap>$situacao</TD>";
			echo "</TR>";

		}
		echo " </TABLE>";

	}else{
		echo "<b>Nenhuma conta a receber neste perídodo</b>";
	}

$sql = "SELECT  pagar                                       ,
		documento                                            ,
		TO_CHAR(vencimento,'DD/MM/YYYY') AS data_vencimento  ,
		TO_CHAR(pagamento ,'DD/MM/YYYY') AS data_pagamento   ,
		valor                                                ,
		valor_pago                                           ,
		valor_multa                                          ,
		tbl_pessoa.nome
	FROM tbl_pagar
	JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor
	WHERE tbl_pagar.empresa = $login_empresa
	$cond3
	$cond4
	ORDER BY tbl_pagar.vencimento,nome
	";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0) {

		echo "<P><font size='2'><b>Contas a Pagar";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='750'>";
		echo "<TR class='HD' height='20' bgcolor='$cor' align='center'>";
		echo "<TD ><b>Cliente</b></TD>";
		echo "<TD ><b>Documento</b></TD>";
		echo "<TD width='25'><b>Vencimento</b></TD>";
		echo "<TD width='25'><b>Pagamento</b></TD>";
		echo "<TD width='70'><b>Valor</b></TD>";
		echo "<TD width='70'><b>Valor Pago</b></TD>";
		echo "<TD width='70'><b>Multa</b></TD>";
		echo "<TD ><b>Situação</b></TD>";
		echo "</TR>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$x=$i+1;
			$pagar             = pg_result($res,$i,pagar);
			$documento         = pg_result($res,$i,documento);
			$data_vencimento   = pg_result($res,$i,data_vencimento);
			$data_pagamento    = pg_result($res,$i,data_pagamento);
			$valor             = pg_result($res,$i,valor);
			$valor_pago        = pg_result($res,$i,valor_pago);
			$valor_multa       = pg_result($res,$i,valor_multa);
			$nome              = pg_result($res,$i,nome);

			$valor         = number_format($valor,2,',','.');
			$valor_pago    = number_format($valor_pago,2,',','.');
			$valor_multa   = number_format($valor_multa,2,',','.');

			if($cor=="#fafafa")$cor1 = '#fdfdfd';
			else               $cor1 = '#fafafa';

			if(strlen($data_pagamento)>0) $situacao = "<img src='imagens/status_verde.gif'> Pago";
			else                           $situacao = "<img src='imagens/status_vermelho.gif'> A Pagar";

			echo "<TR bgcolor='$cor1'class='Conteudo'>";
			echo "<TD align='left'>$nome</TD>";
			echo "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
			echo "<TD align='center'nowrap>$data_vencimento</TD>";
			echo "<TD align='center'nowrap>$data_pagamento</TD>";
			echo "<TD align='right'nowrap>$valor</TD>";
			echo "<TD align='right'nowrap>$valor_pago</TD>";
			echo "<TD align='right'nowrap>$valor_multa</TD>";
			echo "<TD align='center'nowrap>$situacao</TD>";
			echo "</TR>";

		}
		echo " </TABLE>";

	}else{
		echo "<br><b>Nenhuma conta a pagar neste perídodo</b>";
	}




include "rodape.php";
?>