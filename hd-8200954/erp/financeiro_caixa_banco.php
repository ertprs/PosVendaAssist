<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if(strlen($_POST["btn_acao"])>0){

	$dinheiro    = trim($_POST["dinheiro"]);
	$plano_conta = trim($_POST["plano_conta"]);
	$caixa_banco = trim($_POST["caixa_banco"]);
	$documento   = trim($_POST["documento"]);
	$descricao   = trim($_POST["descricao"]);

	if(strlen($documento  )== 0) $msg_erro = "Por favor digite o documento<br>";
	if(strlen($descricao  )== 0) $msg_erro .= "Digite a Descrição<br>";
	if(strlen($data)       == 0) $msg_erro .= "Digite a Data do movimento<br>";
	if(strlen($plano_conta)== 0) $msg_erro .= "Escolha o Plano de Conta<br>";
	if(strlen($caixa_banco)== 0) $msg_erro .= "Escolha o Caixa/Banco<br>";
	if($dinheiro           == 0) $msg_erro .= "Por favor entre com o valor<br>";
	$xdata = converte_data($data);
	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		//INSERE MOVIMENTAÇÃO DE CHEQUE
		if( $dinheiro > 0 ){
			$sql = "INSERT INTO tbl_movimento (
						empresa    ,
						plano_conta,
						documento  ,
						caixa_banco,
						valor      ,
						descricao  ,
						data
					)VALUES(
						$login_empresa,
						$plano_conta  ,
						'$documento'  ,
						$caixa_banco  ,
						$dinheiro     ,
						'$descricao'  ,
						'$xdata'
					)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$res = pg_exec ($con,"SELECT CURRVAL ('tbl_movimento_movimento_seq')");
			$movimento  = pg_result ($res,0,0);

			$sql = "SELECT fn_erp_saldo_empresa('M',$movimento,$login_empresa);";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Movimento Caixa/Banco lançado com sucesso!";
		$documento    = '';
		$plano_conta  = '';
		$caixa_banco  = '';
		$dinheiro     = '';
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


include "menu.php";
//ACESSO RESTRITO AO USUARIO MASTER 
if (strpos ($login_privilegios,'financeiro') === false AND strpos ($login_privilegios,'*') === false ) {
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
.Pesquisa{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: none;
	color: #333333;
	border:#485989 1px solid;
	background-color: #EFF4FA;
}

.Pesquisa caption { 
	font-size:14px; 
	font-weight:bold; 
	color: #FFFFFF;
	background-color: #596D9B;
	text-align:'left';
	text-transform:uppercase; 

	padding:0px 5px; 
}

.Pesquisa thead td{ 
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Pesquisa tbody th{ 
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}
.Pesquisa tbody td{ 
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: none;
	text-align:'left';
	color: #333333;

}

.Pesquisa tfoot td{ 
	font-size:10px; 
	font-weight:bold;
	color: #FFFFFF;
	text-align:'left';
	text-transform:uppercase; 
	padding:0px 5px; 
}

</style>
<script language='javascript' src='../ajax.js'></script>

<script type="text/javascript">
	jQuery(function($){
		$("#data").maskedinput("99/99/9999");
	});

	function checarNumero(campo){
		var num = campo.value.replace(",",".");
		campo.value = parseFloat(num).toFixed(2);
		if (campo.value=='NaN') {
			campo.value=0;
		}
	}
</script>
<?
if(strlen($data)==0) $data = date("d/m/Y");
if (strlen($msg_erro)>0) echo "<div class='error'>$msg_erro</div>";
$valor_receber = 0;
if( strlen($_GET["receber"])  > 0 ) $receber  = $_GET["receber"];
if( strlen($_POST["receber"]) > 0 ) $receber  = $_POST["receber"];
if( strlen($dinheiro)        == 0 ) $dinheiro = 0;




if (strlen($ok)>0 OR strlen($msg)>0) {
	echo "<div class='ok'>$msg</div>";
}

	echo "<table border='0' cellpadding='2' cellspacing='0' class='Pesquisa' align='rigth' width='400'>";
	echo "<form method='POST' name='frm' action='$PHP_SELF'>";
	echo "<caption>Movimento Caixa/Banco</caption>";
	echo "<tbody>";
	echo "<tr>";
	echo "<td>Documento</td>";
	echo "<td align='left'><input type='text' size='40' name='documento' class='Caixa' value='$documento'></td>";
	echo "</tr>";
	echo "<td>Descrição</td>";
	echo "<td align='left'><input type='text' size='40' name='descricao' class='Caixa' value='$descricao'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Valor</td>";
	echo "<td align='left'>";
	echo "<input name='dinheiro' id='dinheiro' type='text' onblur=\"javascript:checarNumero(this);\" value='$dinheiro' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Data</td>";
	echo "<td>";
	echo "<input name='data' id='data' type='text' value='$data' class='Caixa' size='10'>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Caixa/Banco</td>";
	echo "<td>";
		$sql = "SELECT  *
				FROM    tbl_caixa_banco
				WHERE   empresa        = $login_empresa
				ORDER BY descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='Caixa' style='width: 150px;' name='caixa_banco'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_caixa_banco = trim(pg_result($res,$x,caixa_banco));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_caixa_banco' ";
				if($c_caixa_banco == $aux_caixa_banco) echo "SELECTED";
				//elseif($aux_descricao=='Caixa Central') echo "SELECTED";
				echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Plano de Conta</td>";
	echo "<td>";
		$sql = "SELECT  *
				FROM    tbl_plano_conta
				WHERE   empresa        = $login_empresa
				AND     debito_credito <>'T'
				ORDER BY debito_credito,descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='Caixa' style='width: 150px;' name='plano_conta'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_plano_conta    = trim(pg_result($res,$x,plano_conta));
				$aux_descricao      = trim(pg_result($res,$x,descricao));
				$aux_debito_credito = trim(pg_result($res,$x,debito_credito));

				if($ant_debito_credito<>$aux_debito_credito){
					if($aux_debito_credito == 'C'){
						$nome = "Crédito";
						$cor  = "#CCFFCC";
					}else{
						$nome = "Débito";
						$cor  = "#FFCCCC";
					}
					echo "<option value='' style='background-color:$cor; font-weight:bold;'> $nome</option>";
				}
				$ant_debito_credito = $aux_debito_credito;
				
				echo "<option value='$aux_plano_conta'";
				if($plano_conta == $aux_plano_conta) echo "SELECTED";
				echo ">&nbsp;&nbsp;$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
	echo "</td>";
	echo "</tr>";

	echo "</tbody>";
	echo "<tfoot>";
	echo "<tr>";
	echo "<td colspan='2' style='text-align:center;'><br><input type='submit' name='btn_acao' value='Lançar Movimento' ></td>";
	echo "</tr>";
	echo "</tfoot>";
	echo "</form>";
	echo "</table>";




include "rodape.php";
?>