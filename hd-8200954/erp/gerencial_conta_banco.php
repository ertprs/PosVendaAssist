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
	url = "ajax_caixa_movimento.php?ajax=sim&acao=detalhes&data=" + escape(data);

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


if(strlen($msg_erro)==0){


	$sql = "SELECT 
				tbl_caixa_banco.caixa_banco						AS caixa_banco,
				tbl_caixa_banco.descricao						AS descricao,
				TO_CHAR(a.data,'DD/MM/YYYY')					AS data,
				sum(cast(a.total_recebido as numeric(12,2)))	AS total_recebido,
				sum(cast(a.total_pago as numeric(12,2))     )   AS total_pago 
			FROM tbl_caixa_banco 
			LEFT JOIN 	
			(
				(
					SELECT  								
						tbl_caixa_banco.caixa_banco,
						recebimento as data,
						SUM (tbl_movimento.valor)    AS total_recebido,
						null           AS total_pago
					FROM tbl_caixa_banco
					JOIN tbl_movimento on tbl_movimento.caixa_banco = tbl_caixa_banco.caixa_banco
					LEFT JOIN tbl_contas_receber on tbl_contas_receber.contas_receber = tbl_movimento.contas_receber
					WHERE tbl_movimento.empresa = 27
					AND recebimento = current_date
					GROUP BY tbl_caixa_banco.caixa_banco,recebimento
				) UNION (
					SELECT  	
						tbl_caixa_banco.caixa_banco,
						pagamento as data,
						SUM (tbl_movimento.valor)    AS total_recebido,
						null           AS total_pago
					FROM tbl_caixa_banco
					JOIN tbl_movimento on tbl_movimento.caixa_banco = tbl_caixa_banco.caixa_banco
					LEFT JOIN tbl_pagar on tbl_pagar.pagar = tbl_movimento.pagar
					WHERE tbl_movimento.empresa = 27
					AND pagamento = current_date
					GROUP BY tbl_caixa_banco.caixa_banco,pagamento 
				)
			) AS a on tbl_caixa_banco.caixa_banco = a.caixa_banco

			GROUP BY tbl_caixa_banco.caixa_banco, a.data, tbl_caixa_banco.descricao
			ORDER BY tbl_caixa_banco.caixa_banco";
	
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		echo "<br><table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='750'>";
		echo "<TR  height='20' bgcolor='#DDDDDD' align='center'>";
		echo "<TD colspan='2' align='left'><b>CAIXA / BANCO</b></TD>";
		echo "<TD width='130' align='left'><b>Descricao</b></TD>";
		echo "<TD width='130' align='right'><b>Valor Entrada</b></TD>";
		echo "<TD width='130' align='right'><b>Valor Saída</b></TD>";
		echo "<TD width='150' align='right'><b>Saldo</b></TD>";
	
		echo "</TR>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$caixa_banco	= pg_result($res,$i,caixa_banco);
			$descricao		= pg_result($res,$i,descricao);			
			$data			= pg_result($res,$i,data);
			$total_recebido = pg_result($res,$i,total_recebido);
			$total_pago     = pg_result($res,$i,total_pago);
	
			$saldo          = $total_recebido - $total_pago;
	
			$saldo_recebido = $saldo_recebido + $total_recebido;
	
			$saldo_pago     = $saldo_pago     + $total_pago;
	
			$total_geral    = $saldo_recebido - $saldo_pago;
	
			$total_recebido = number_format($total_recebido,2,',','.');
			$total_pago     = number_format($total_pago,2,',','.');
			$xsaldo         = number_format($saldo,2,',','.');
	
			$xtotal_geral   = number_format($total_geral,2,',','.');
	
			if($cor1=="#eeeeee")$cor1 = '#ffffff';
			else               $cor1 = '#eeeeee';
	
			if($total_geral < 0)$cor2 = "#990000";
			else                $cor2 = "#009900";
	
			echo "<TR bgcolor='$cor1'class='Conteudo'>";
			echo "<td align='center' colspan='1'>";
/*			echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$data_baixa','visualizar_$i');\" align='absmiddle'>";
			echo "</td>";
			echo "<TD ><a href=\"javascript:MostraEsconde('dados_$i','$data_baixa','visualizar_$i');\">$caixa_banco</a></TD>";
			echo "<TD ><a href=\"javascript:MostraEsconde('dados_$i','$data_baixa','visualizar_$i');\">$descricao</a></TD>";*/
			echo "</td>";
			echo "<TD >$caixa_banco</TD>";
			echo "<TD >$descricao</TD>";
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
		echo "<td align='center' colspan='3'><b>TOTAL</b></td>";
		echo "<td align='right' ><b>$saldo_recebido</b></td>";
		echo "<td align='right' ><b>$saldo_pago</b></td>";
		echo "<td align='right' nowrap><font size='2' color ='$cor2'><b>$xtotal_geral</b></font></td>";
		echo "</tr>";
		echo "</TABLE>";
	}
}

include "rodape.php";
?>