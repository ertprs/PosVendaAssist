<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'gerencial') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>
<script language='javascript' src='../ajax.js'></script>
<script>
function retornaCaixa (http , componente ) {
	com = document.getElementById(componente);

	com.innerHTML   ="Carregando<br><img src='../imagens/carregar2.gif'>";
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[2];

				}else{
					alert ('Erro ao abrir CRM' );
					alert(results[0]);
				}
			}
		}
	}
}

function pegaCaixa (data,dados,cor) {
	url = "ajax_caixa_orcamento.php?ajax=sim&acao=detalhes&data=" + escape(data);

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaCaixa (http , dados) ; } ;
	http.send(null);
}

function MostraEsconde(dados,data,imagem,cor)
{
	if (document.getElementById)
	{
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style2.innerHTML   ="";
			img.src='../imagens/mais.gif';

			}
		else{
			style2.style.display = "block";
			img.src='../imagens/menos.gif';
			pegaCaixa(data,dados);
		}

	}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();


</script>
<?
$btn_acao = $_POST["btn_acao"];



if(strlen($btn_acao)>0){
	$de        = trim($_POST["de"]);
	$ate       = trim($_POST["ate"]);

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

	if($tipo_data == "vencimento"){
		$cond1 = " AND vencimento BETWEEN '$aux_de' AND '$aux_ate' ";
	}

}else{
	if(strlen($ate)==0){
		$ate     = date("d/m/Y");
		$aux_ate = date("Y-m-d");
	}

	if(strlen($de)==0){

		$sql = "select		CURRENT_DATE - interval '30 day',
					 to_char(CURRENT_DATE - interval '30 day','DD/MM/YYYY');";
		$res     = @pg_exec($con,$sql);
		$aux_de = @pg_result ($res,0,0);
		$de     = @pg_result ($res,0,1);

		$cond1 = " AND vencimento BETWEEN '$aux_de' AND '$aux_ate' ";
		
	}
 	
}


echo "<table width='750' bgcolor='#ffffff' cellpadding='2' cellspacing='0'>";
echo "<tr bgcolor='#efefef'>";
echo "<td>";
	echo "<form name='busca' action='$PHP_SELF' method='POST'>";
	echo "<table>";
	echo "<tr class='Conteudo'>";
	echo "<td>Período</td>";
	echo "<td><input name='de' value='$de' type='text' size='10' maxlength='10' class='Caixa'> até <input name='ate' value='$ate' type='text' size='10' maxlength='10' class='Caixa'></td>";
	echo "</tr>";
	echo "<tr class='Conteudo'>";
	echo "<td colspan='2' align='center'><input name='btn_acao' value='Ver relatório' type='submit'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

echo "</td>";

echo "<td align='right'>";
if(strlen($msg_erro)==0){
	$sql = "SELECT 
		(
			SELECT sum(valor)
			FROM tbl_contas_receber 
			WHERE fabrica = $login_empresa 
				AND recebimento IS NOT NULL
				AND vencimento   BETWEEN '$aux_de' AND '$aux_ate' 
		) AS valor_receber      ,
		(
			SELECT sum(valor) 
			FROM tbl_pagar
			WHERE empresa = $login_empresa 
				AND pagamento IS NOT NULL
				AND vencimento   BETWEEN '$aux_de' AND '$aux_ate' 
		) AS valor_pagar";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0) {
		$valor_receber  = pg_result($res,0,valor_receber);
		$valor_pagar    = pg_result($res,0,valor_pagar);
	
		$total_caixa = $valor_recebeu - $valor_pago;
	
		$valor_recebeu = number_format($valor_recebeu,2,',','.');
		$valor_receber = number_format($valor_receber,2,',','.');
		$valor_pago    = number_format($valor_pago,2,',','.');
		$valor_pagar   = number_format($valor_pagar,2,',','.');
		$total_caixa   = number_format($total_caixa,2,',','.');
	
		echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='300'>";
	
		echo "<tr bgcolor='#FFFFFF'  class='Conteudo'>";
		echo "<td>Inadinplência</td>";
		echo "<td align='right'>$valor_receber</td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
		echo "<td>Pagamento em Atraso</td>";
		echo "<td align='right'>$valor_pagar</td>";
		echo "</tr>";
	
		echo "</table>";
	
	}
}else echo "<font size='2' color = '#990000'>$msg_erro</font>";
echo "</td>";
echo "</tr>";
echo "</table>";
if(strlen($msg_erro)==0){
	$sql = "SELECT TO_CHAR(a.vencimento,'DD/MM/YYYY') AS data_baixa,
		sum(a.total_recebido)                     AS total_recebido,
		sum(a.total_pago)                         AS total_pago 
		FROM (
			(
				SELECT  vencimento                      ,
					SUM (valor)    AS total_recebido,
					null           AS total_pago
				FROM tbl_contas_receber 
				JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente 
				WHERE fabrica = $login_empresa
				AND vencimento BETWEEN '$aux_de' AND '$aux_ate'
				AND recebimento IS NOT NULL
				GROUP BY vencimento
			) UNION (
				SELECT  vencimento                      ,
					null           AS total_recebido,
					sum(valor)     AS total_pago
				FROM tbl_pagar 
				JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor 
				WHERE tbl_pagar.empresa = $login_empresa 
				AND vencimento   BETWEEN '$aux_de' AND '$aux_ate' 
				AND pagamento IS NOT NULL
				GROUP BY vencimento 
			)
		) AS a 
		WHERE (total_recebido > 0 OR total_pago > 0)
		GROUP BY a.vencimento";
	
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		echo "<br><table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='750'>";
		echo "<TR  height='20' bgcolor='#DDDDDD' align='center'>";
		echo "<TD colspan='2' align='left'><b>ORÇAMENTO</b></TD>";
		echo "<TD width='130' align='right'><b>Valor Entrada</b></TD>";
		echo "<TD width='130' align='right'><b>Valor Saída</b></TD>";
		echo "<TD width='150' align='right'><b>Saldo</b></TD>";
	
		echo "</TR>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$data_baixa     = pg_result($res,$i,data_baixa);
			$total_recebido = pg_result($res,$i,total_recebido);
			$total_pago     = pg_result($res,$i,total_pago);
	
			$saldo          = $total_recebido - $total_pago;
	
			$saldo_recebido = $saldo_recebido + $total_recebido;
	
			$saldo_pago     = $saldo_pago     + $total_pago;
	
			$total_geral    = $saldo_recebido - $saldo_pago;
	
			$total_recebido = number_format($total_recebido,2,',','.');
			$total_pago     = number_format($total_pago,2,',','.');
			$xsaldo          = number_format($saldo,2,',','.');
	
			$xtotal_geral    = number_format($total_geral,2,',','.');
	
			if($cor1=="#eeeeee")$cor1 = '#ffffff';
			else               $cor1 = '#eeeeee';
	
			if($total_geral < 0)$cor2 = "#990000";
			else                $cor2 = "#009900";
	
			echo "<TR bgcolor='$cor1'class='Conteudo'>";
			echo "<td align='center' width='20'>";
			echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$data_baixa','visualizar_$i');\" align='absmiddle'>";
			echo "</td>";
			echo "<TD ><a href=\"javascript:MostraEsconde('dados_$i','$data_baixa','visualizar_$i');\">$data_baixa</a></TD>";
			echo "<TD align='right'nowrap>$total_recebido</TD>";
			echo "<TD align='right'nowrap>$total_pago</TD>";
			echo "<TD align='right'nowrap><b><font color='$cor2'> $xtotal_geral</font></b></TD>";
			echo "</TR>";
	
				echo "<tr heigth='1' class='Conteudo' bgcolor='$cor1'><td colspan='9'>";
				echo "<DIV class='exibe' id='dados_$i' value='1' align='center'>";
				echo "</DIV>";
				echo "</td></tr>";
	
		}
		if($total_geral < 0)$cor2 = "#990000";
		else                $cor2 = "#009900";
	
		$saldo_recebido = number_format($saldo_recebido,2,',','.');
		$saldo_pago     = number_format($saldo_pago,2,',','.');
	
		echo "<TR bgcolor='#DDDDDD'class='Conteudo'>";
		echo "<td align='center' colspan='2'><b>TOTAL</b></td>";
		echo "<td align='right' ><b>$saldo_recebido</b></td>";
		echo "<td align='right' ><b>$saldo_pago</b></td>";
		echo "<td align='right' nowrap><font size='2' color ='$cor2'><b>$xtotal_geral</b></font></td>";
		echo "</tr>";
		echo "</TABLE>";
	}
}
/*
$sql = "SELECT  contas_receber                                       ,
		documento                                            ,
		TO_CHAR(vencimento ,'DD/MM/YYYY') AS data_vencimento ,
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
	ORDER BY vencimento,nome
	";

	$res = pg_exec ($con,$sql);
	
if (@pg_numrows($res) > 0) {

	echo "<P><font size='2'><b>Contas a Receber";
	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='750'>";
	echo "<TR height='20' bgcolor='#DDDDDD' align='center'>";
	echo "<TD ><b>Cliente</b></TD>";
	echo "<TD ><b>Documento</b></TD>";
	echo "<TD width='25'><b>Emissão</b></TD>";
	echo "<TD width='25'><b>Vencimento</b></TD>";
	echo "<TD width='100' align='right'><b>Valor</b></TD>";
	echo "<TD width='100' align='right'><b>Valor Atual</b></TD>";
	echo "</TR>";

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

		$valor             = number_format($valor,2,',','.');
		$valor_dias_atraso = number_format($valor_dias_atraso,2,',','.');
		$valor_recebido    = number_format($valor_recebido,2,',','.');

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';

		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'>$nome</TD>";
		echo "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
		echo "<TD align='center'nowrap>$data_vencimento</TD>";
		echo "<TD align='center'nowrap>$data_recebimento</TD>";
		echo "<TD align='right'nowrap>$valor</TD>";
		echo "<TD align='right'nowrap>$valor_recebido</TD>";
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
	echo "<table border='0' cellpadding='2' cellspacing='0'class='HD'  align='center' width='750'>";
	echo "<TR  height='20' bgcolor='#DDDDDD' align='center'>";
	echo "<TD ><b>Cliente</b></TD>";
	echo "<TD ><b>Documento</b></TD>";
	echo "<TD width='25'><b>Vencimento</b></TD>";
	echo "<TD width='25'><b>Pagamento</b></TD>";
	echo "<TD width='100' align='right'><b>Valor</b></TD>";
	echo "<TD width='100' align='right'><b>Valor Atual</b></TD>";
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

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';

		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'>$nome</TD>";
		echo "<TD align='left'><a href='contas_receber.php'>$documento</a></TD>";
		echo "<TD align='center'nowrap>$data_vencimento</TD>";
		echo "<TD align='center'nowrap>$data_pagamento</TD>";
		echo "<TD align='right'nowrap>$valor</TD>";
		echo "<TD align='right'nowrap>$valor_pago</TD>";

		echo "</TR>";

	}
	echo " </TABLE>";

}else{
	echo "<br><b>Nenhuma conta a pagar neste perídodo</b>";
}
*/






include "rodape.php";
?>