<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

if(strlen($_POST["btn_acao"])>0){

	$total          = trim($_POST["xtotal"]);
	$troco          = trim($_POST["xtroco"]);
	$contas_receber = trim($_POST["receber"]);

	$cheque         = trim($_POST["cheque"]);
	$dinheiro       = trim($_POST["dinheiro"]);
	$cartao_debito  = trim($_POST["cartao_debito"]);
	$cartao_credito = trim($_POST["cartao_credito"]);

	$plano_conta    = trim($_POST["plano_conta"]);

	$d_caixa_banco  = trim($_POST["d_caixa_banco"]);
	$c_caixa_banco  = trim($_POST["c_caixa_banco"]);
	$cd_caixa_banco = trim($_POST["cd_caixa_banco"]);
	$cc_caixa_banco = trim($_POST["cc_caixa_banco"]);

	//--===== VALIDA SE O TOTAL ESTÁ MENOR QUE O VALOR DO DOCUMENTO =====================================================================
	$sql = "SELECT valor,documento FROM tbl_contas_receber WHERE contas_receber = $contas_receber";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0){
		$total_receber = pg_result($res,0,valor);
		$documento     = pg_result($res,0,documento);
	}
	if($total_receber > $total)
		 $msg_erro = "Valor total pago (R$ $total) não pode ser menor que o valor a receber(R$ $total_receber)";
	if($troco > $dinheiro)
		 $msg_erro = "Valor do troco (R$ $troco) não pode ser menor que o valor em dinheiro(R$ $dinheiro)";
	//--=================================================================================================================================
	if(strlen($plano_conta)== 0) $msg_erro = "Escolha o Plano de Conta";
	if($cheque         > $total_receber) $msg_erro = "O valor do cheque ($cheque) não pode ser maior que o valor a receber(R$ $total_receber)";
	if($cartao_credito > $total_receber) $msg_erro = "O valor do <b>Cartão de Crédito</b> ($cartao_credito) não pode ser maior que o valor a receber (R$ $total_receber)";
	if($cartao_debito  > $total_receber) $msg_erro = "O valor do <b>Cartão de Débito</b> ($cartao_debito) não pode ser maior que o valor a receber (R$ $total_receber)";

	if(strlen($msg_erro)==0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//INSERE MOVIMENTAÇÃO DE CHEQUE
		if( $cheque > 0 ){
			if(strlen($c_caixa_banco)>0){
				$sql = "INSERT INTO tbl_movimento (empresa,contas_receber,plano_conta,documento,caixa_banco,valor)
					VALUES($login_empresa,$contas_receber,$plano_conta,'$documento',$c_caixa_banco,$cheque)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}else{
				$msg_erro = "Por favor selecione o Caixa/Banco para o cheque no valor de R$ $cheque ";
			}
		}

		//INSERE MOVIMENTAÇÃO DE CARTÃO DE CRÉDITO
		if( $cartao_credito > 0 ){
			if(strlen($cc_caixa_banco)>0){
				$sql = "INSERT INTO tbl_movimento (empresa,contas_receber,plano_conta,documento,caixa_banco,valor)
					VALUES($login_empresa,$contas_receber,$plano_conta,'$documento',$cc_caixa_banco,$cartao_credito)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}else{
				$msg_erro = "Por favor selecione o Caixa/Banco para o cheque no valor de R$ $cartao_credito ";
			}
		}

		//INSERE MOVIMENTAÇÃO DE CARTÃO DE DÉBITO	
		if( $cartao_debito > 0 ){
			if(strlen($cd_caixa_banco)>0){
				$sql = "INSERT INTO tbl_movimento (empresa,contas_receber,plano_conta,documento,caixa_banco,valor)
					VALUES($login_empresa,$contas_receber,$plano_conta,'$documento',$cd_caixa_banco,$cartao_debito)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}else{
				$msg_erro = "Por favor selecione o Caixa/Banco para o cheque no valor de R$ $cartao_debito ";
			}
		}

		//INSERE MOVIMENTAÇÃO DE DINHEIRO
		if( $dinheiro > 0 ){
			if(strlen($d_caixa_banco)>0){
				$sql = "INSERT INTO tbl_movimento (empresa,contas_receber,plano_conta,documento,caixa_banco,valor)
					VALUES($login_empresa,$contas_receber,$plano_conta,'$documento',$d_caixa_banco,$dinheiro)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}else{
				$msg_erro = "Por favor selecione o Caixa/Banco para o cheque no valor de R$ $dinheiro ";
			}
		}
		$sql = "UPDATE tbl_contas_receber SET recebimento = current_date , valor_recebido = $total_receber WHERE contas_receber = $contas_receber";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Recebido com sucesso!";
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


include "menu.php";
//ACESSO RESTRITO AO USUARIO MASTER 
if (strpos ($login_privilegios,'financeiro') === false AND strpos ($login_privilegios,'*') === false) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}


?>
<style>
#total,#total_receber{
	font-family:arial;
	font-size:12pt;
	font-weight:bold;
}
#troco{
	font-family:arial;
	font-size:10pt;
	font-weight:bold;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}
.ok{
	font-family: Verdana;
	font-size: 12px;
	color:blue;
	border:#39AED5 1px solid; background-color: #B0DFEE;
}
</style>
<script language='javascript' src='../ajax.js'></script>
<script>

function recalcular(){

	var valor_receber=0;
	var valor_total_geral=0;

	if (document.getElementById('total_receber')){
		valor_receber = parseFloat(document.getElementById('total_receber').innerHTML);
	}
/*
	var valor_total_itens  = parseFloat(document.getElementById('cheque').innerHTML);
	var aux_cal            = valor_total_mo+valor_total_itens;
	
	document.getElementById('valores_sub_total').innerHTML = parseFloat(aux_cal).toFixed(2);
*/
	var valor_dinheiro   = parseFloat(document.getElementById('dinheiro').value);
	var valor_cheque     = parseFloat(document.getElementById('cheque').value);
	var valor_debito     = parseFloat(document.getElementById('cartao_debito').value);
	var valor_credito    = parseFloat(document.getElementById('cartao_credito').value);

	valor_total_geral = parseFloat(valor_dinheiro) + parseFloat(valor_cheque) + parseFloat(valor_debito) + parseFloat(valor_credito);

	valor_troco = parseFloat(valor_total_geral) - parseFloat(valor_receber);

	if (valor_troco=='NaN') {
		valor_troco =0;
	}

	document.getElementById('total').innerHTML    = parseFloat(valor_total_geral).toFixed(2);
	document.getElementById('xtotal').value = parseFloat(valor_total_geral).toFixed(2);

	if(valor_troco < 0) valor_troco = 0;
	document.getElementById('troco').innerHTML = parseFloat(valor_troco).toFixed(2);
	document.getElementById('xtroco').value    = parseFloat(valor_troco).toFixed(2);

}
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value=0;
	}
}
</script>
<?
$btn_acao = $_POST["btn_acao"];


/*BUSCA*/
$busca = trim($_POST["busca"]);
$tipo  = trim($_POST["tipo"]);
if (strlen($msg_erro) == 0 ) {
	//--=== Busca por Data de Previsão ==============================================================
	if($tipo == 'd'){
		if (strlen($busca) == 0) $msg_erro .= "Favor informar a data para pesquisa<br>";
		if (strlen($busca) > 10) $msg_erro .= "Tamanho incorreto para data<br>";
		
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$busca')");
		if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	

		if (strlen($msg_erro) == 0){
			$aux_busca = @pg_result ($fnc,0,0);
			$cond2 = " AND tbl_orcamento.data_previsao = '$aux_busca' ";
		}
	}
	//--=== Busca por Vendedor ======================================================================
	if($tipo == 'v'){
		$aux_busca = strtoupper($busca);
		$cond2 = "AND tbl_hd_chamado.empregado IN (
				SELECT empregado
				FROM  tbl_empregado
				JOIN  tbl_posto     ON tbl_posto.posto = tbl_empregado.posto_empregado
				WHERE tbl_empregado.empresa = $login_empresa
				AND   upper(nome)  LIKE '%$aux_busca%' 
			)";
	}
	//--=== Busca por Vendedor ======================================================================
	if($tipo == 'c'){
		$aux_busca = strtoupper($busca);
		$cond2 = "AND tbl_hd_chamado.orcamento IN (
				SELECT orcamento 
				FROM  tbl_orcamento
				WHERE tbl_orcamento.empresa = $login_empresa
				AND   upper(consumidor_nome)  LIKE '%$aux_busca%'
			)";
	}

	if(strpos($msg_erro,"invalid input syntax for integer")){
		$msg_erro = "Este não é um formato válido para data";
	}
	if (strlen($busca)    > 0 ) echo "Você está buscando por: $busca<br>";
	if (strlen($msg_erro) > 0 ) echo "<font color='#FF0000'>$msg_erro</font><br>";

}
?>
<!--
<form method='POST'>
<table align='center'>
<tr>
	<td><input name="busca" size="41" maxlength="2048" value="<?=$busca?>" title="Pesquisar" type="text" ><font size="-1"> <input name="btnG" value="Pesquisar" type="submit"></td>
</tr>
<tr>
	<td class='Conteudo'>Pesquisar por: <input type='radio' name='tipo' value='d' checked> Documento <input type='radio' name='tipo' value='c' <?if($tipo=='c') echo "CHECKED";?>> Cliente </td>
</tr>
</table>
</form>
-->


<?

$sql = "SELECT  contas_receber                                       ,
		documento                                            ,
		TO_CHAR(vencimento ,'DD/MM/YYYY') AS data_vencimento ,
		valor                                                ,
		valor_dias_atraso                                    ,
		valor_recebido                                       ,
		tbl_pessoa.nome                                      ,
		case when current_date - vencimento >0 then current_date - vencimento  else 0 end as dias_vencido
	FROM tbl_contas_receber
	JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
	WHERE fabrica = $login_empresa
	AND   posto   = $login_loja
	AND   recebimento IS NULL
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
		$valor_recebido    = number_format($valor_recebido,2,',','.');

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';
//<a href='contas_receber.php?receber=$contas_receber'></a>
		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'>$nome</TD>";
		echo "<TD align='left'><a href='$PHP_SELF?receber=$contas_receber'>$documento</a></TD>";
		echo "<TD align='center'nowrap>$data_vencimento</TD>";
		echo "<TD align='center'nowrap>$data_recebimento</TD>";
		echo "<TD align='right'nowrap>$valor</TD>";
		echo "<TD align='right'nowrap>$valor_reajustado</TD>";
		echo "</TR>";

	}
	echo " </TABLE>";

}else{
	echo "<b>Nenhuma conta a receber neste período</b>";
}


$sql = "SELECT  contas_receber                                       ,
		documento                                            ,
		TO_CHAR(vencimento ,'DD/MM/YYYY') AS data_vencimento ,
		valor                                                ,
		valor_dias_atraso                                    ,
		valor_recebido                                       ,
		tbl_pessoa.nome                                      ,
		case when current_date - vencimento >0 then current_date - vencimento  else 0 end as dias_vencido
	FROM tbl_contas_receber
	JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
	WHERE fabrica = $login_empresa
	AND   posto   = $login_loja
	AND   recebimento IS NULL
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
		$valor_recebido    = number_format($valor_recebido,2,',','.');

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';
//<a href='contas_receber.php?receber=$contas_receber'></a>
		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'>$nome</TD>";
		echo "<TD align='left'><a href='$PHP_SELF?receber=$contas_receber'>$documento</a></TD>";
		echo "<TD align='center'nowrap>$data_vencimento</TD>";
		echo "<TD align='center'nowrap>$data_recebimento</TD>";
		echo "<TD align='right'nowrap>$valor</TD>";
		echo "<TD align='right'nowrap>$valor_reajustado</TD>";
		echo "</TR>";

	}
	echo " </TABLE>";

}else{
	echo "<b>Nenhuma conta a receber neste perídodo</b>";
}
echo "<br>";

if (strlen($msg_erro)>0)             echo "<div class='error'>$msg_erro</div>";


$valor_receber = 0;

if( strlen($_GET["receber"])  > 0 ) $receber = $_GET["receber"];
if( strlen($_POST["receber"]) > 0 ) $receber = $_POST["receber"];
if(strlen($dinheiro)       == 0 ) $dinheiro       = 0;
if(strlen($cheque)         == 0 ) $cheque         = 0;
if(strlen($cartao_debito)  == 0 ) $cartao_debito  = 0;
if(strlen($cartao_credito) == 0 ) $cartao_credito = 0;

echo "<table width='750'>";
echo "<form method='POST' name='frm' action='$PHP_SELF'>";
echo "<tr>";
echo "<td valign='top'>";
	if (strlen($ok)>0 OR strlen($msg)>0) {
		echo "<div class='ok'>$msg</div>";
	}else{
		if(strlen($receber)>0){
		
			$sql = "SELECT  tbl_contas_receber.contas_receber                                ,
					tbl_contas_receber.documento                                     ,
					TO_CHAR(tbl_contas_receber.emissao,   'DD/MM/YYYY') AS emissao   ,
					TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento,
					tbl_contas_receber.valor                                         ,
					tbl_contas_receber.orcamento                                     ,
					tbl_pessoa.nome
				FROM tbl_contas_receber
				JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
				WHERE contas_receber = $receber";
			$res = pg_exec ($con,$sql);
		
			if (@pg_numrows($res) > 0) {
		
				$contas_receber  = pg_result($res,0,contas_receber);
				$orcamento       = pg_result($res,0,orcamento);
				$nome            = pg_result($res,0,nome);
				$documento       = pg_result($res,0,documento);
				$valor_receber   = pg_result($res,0,valor);
				$data_vencimento = pg_result($res,0,vencimento);
		
				echo "<input type='hidden' name='receber' id='receber' value='$contas_receber'>";
				echo "<input type='hidden' name='valor_receber' id='valor_receber'  value='$valor_receber'>";
		
				$valor_receber    = number_format($valor_receber,2,'.','');
		
				echo "<table border='0' cellpadding='2' cellspacing='0'  align='center' class='HD' width='300'>";
				echo "<TR  height='20' bgcolor='#e5ecf9'>";
				echo "<TD><b>Orçamento</b></TD>";
				echo "<TD><font size='2'><b>$orcamento</b></font></TD>";
				echo "</TR>";
				echo "<TR height='20'>";
				echo "<TD><b>Cliente</b></TD>";
				echo "<TD>$nome</TD>";
				echo "</TR>";
				echo "<TR height='20' >";
				echo "<TD><b>Documento</b></TD>";
				echo "<TD>$documento</TD>";
				echo "</TR>";
				echo "<TR  height='20'>";
				echo "<TD><b>Vencimento</b></TD>";
				echo "<TD>$data_vencimento</TD>";
				echo "</TR>";
				echo "<TR  height='20'>";
				echo "<TD><b>Plano de Conta</b></TD>";
				echo "<TD>";
					$sql = "SELECT  *
							FROM    tbl_plano_conta
							WHERE   empresa        = $login_empresa
							AND     debito_credito = 'C'
							ORDER BY descricao;";
					$res = pg_exec ($con,$sql);
			
					if (pg_numrows($res) > 0) {
						echo "<select class='Caixa' style='width: 150px;' name='plano_conta'>\n";
						echo "<option value=''>ESCOLHA</option>\n";
			
						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_plano_conta = trim(pg_result($res,$x,plano_conta));
							$aux_descricao  = trim(pg_result($res,$x,descricao));
			
							echo "<option value='$aux_plano_conta'";
							if($plano_conta == $aux_plano_conta) echo "SELECTED";
							echo ">$aux_descricao</option>\n";
						}
						echo "</select>\n";
					}
				echo "</TD>";
				echo "</TR>";
				echo "</table>";
			}
		
		}
	}
	echo "</td>";
	echo "<td width='405' align='right'>";
	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='400'>";
	
	echo "<tr bgcolor='#FFFFFF'  class='Conteudo'>";
	echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";
	echo "<td><b>TOTAL A RECEBER</b></td>";
	echo "<td align='right'><div id='total_receber'>$valor_receber</div></td>";
	echo "<td>";
	echo "</td>";
	echo "</tr>";
	echo "<td>Dinheiro</td>";
	echo "<td align='right'>";
	echo "<input name='dinheiro' id='dinheiro' type='text' onblur=\"javascript:checarNumero(this);recalcular()\" value='$dinheiro' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "<td>";
		$sql = "SELECT  *
				FROM    tbl_caixa_banco
				WHERE   empresa        = $login_empresa
				ORDER BY descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='Caixa' style='width: 150px;' name='d_caixa_banco'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_caixa_banco = trim(pg_result($res,$x,caixa_banco));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_caixa_banco' ";
				if($d_caixa_banco == $aux_caixa_banco) echo "SELECTED";
				elseif($aux_descricao=='Caixa Central') echo "SELECTED";
				echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Cheque</td>";
	echo "<td align='right'>";
	echo "<input name='cheque' id='cheque' type='text' onblur=\"javascript:checarNumero(this);recalcular()\" value='$cheque' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "<td>";
		$sql = "SELECT  *
				FROM    tbl_caixa_banco
				WHERE   empresa        = $login_empresa
				ORDER BY descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='Caixa' style='width: 150px;' name='c_caixa_banco'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_caixa_banco = trim(pg_result($res,$x,caixa_banco));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_caixa_banco' ";
				if($c_caixa_banco == $aux_caixa_banco) echo "SELECTED";
				elseif($aux_descricao=='Caixa Central') echo "SELECTED";
				echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
	echo "</td>";
	echo "</tr>";

	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Cartão de Débito</td>";
	echo "<td align='right'>";
	echo "<input name='cartao_debito' id='cartao_debito' type='text' onblur=\"javascript:checarNumero(this);recalcular()\" value='$cartao_debito' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "<td>";
		$sql = "SELECT  *
				FROM    tbl_caixa_banco
				WHERE   empresa        = $login_empresa
				ORDER BY descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='Caixa' style='width: 150px;' name='cd_caixa_banco'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_caixa_banco = trim(pg_result($res,$x,caixa_banco));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_caixa_banco' ";
				if($cd_caixa_banco == $aux_caixa_banco) echo "SELECTED";
				echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Cartão de Crédito</td>";
	echo "<td align='right'>";
	echo "<input name='cartao_credito' id='cartao_credito' type='text' onblur=\"javascript:checarNumero(this);recalcular()\" value='$cartao_credito' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "<td>";
		$sql = "SELECT  *
				FROM    tbl_caixa_banco
				WHERE   empresa        = $login_empresa
				ORDER BY descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='Caixa' style='width: 150px;' name='cc_caixa_banco'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_caixa_banco = trim(pg_result($res,$x,caixa_banco));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_caixa_banco' ";
				if($cc_caixa_banco == $aux_caixa_banco) echo "SELECTED";
				echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
	echo "</td>";
	echo "</tr>";

	echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";
	//valor total
	echo "<td><b>TOTAL PAGO</b></td>";
	echo "<td align='right'>";
	echo "<div id='total'>";
	if($total>0) echo $total;
	else         echo "0";
	echo "</div>";
	echo "<input type='hidden' name='xtotal' id='xtotal'  value='";
	if($total>0) echo $total;
	else         echo "0";
	echo "'>";
	echo "</td>";

	//botão de receber
	echo "<td align='center' rowspan='2' bgcolor='#FFFFFF'><input type='submit' name='btn_acao' value='Receber'style='width: 120px;height:40px;font-size:12pt '></td>";
	
	echo "</tr>";

	//valor de troco
	echo "<tr bgcolor='#EEEEEE' class='Conteudo'>";
	echo "<td>TROCO</td>";
	echo "<td align='right'><div id='troco'>";
	if($troco>0) echo $troco;
	else         echo "0";
	echo "</div>";
	echo "<input type='hidden' name='xtroco' id='xtroco' value='";
	if($troco>0) echo $troco;
	else         echo "0";
	echo "'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";

	echo "</tr>";
	
	echo "</table>";

echo "</td>";
echo "</tr>";
echo "</form>";
echo "</table>";


include "rodape.php";
?>