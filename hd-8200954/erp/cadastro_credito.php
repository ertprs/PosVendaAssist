<?

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';


if(strlen($_POST["btn_acao"])>0){
    $pessoa      = trim($_POST["pessoa"]);
	$dinheiro    = trim($_POST["dinheiro"]);
	$plano_conta = trim($_POST["plano_conta"]);
	$caixa_banco = trim($_POST["caixa_banco"]);
	$documento   = trim($_POST["documento"]);
	$descricao   = trim($_POST["descricao"]);

	//--===== VALIDA SE O TOTAL ESTÁ MENOR QUE O VALOR DO DOCUMENTO =====================================================================

	if($total_receber > $total)
		 $msg_erro = "Valor total pago (R$ $total) não pode ser menor que o valor a receber(R$ $total_receber)";
	if($troco > $dinheiro)
		 $msg_erro = "Valor do troco (R$ $troco) não pode ser menor que o valor em dinheiro(R$ $dinheiro)";
	//--=================================================================================================================================
	if(strlen($documento  )== 0) $msg_erro = "Por favor digite o documento<br>";
	if(strlen($descricao  )== 0) $msg_erro .= "Digite a Descrição<br>";
	if($dinheiro           == 0) $msg_erro .= "Por favor entre com o valor<br>";

	if(strlen($msg_erro)==0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//INSERE MOVIMENTAÇÃO DE CHEQUE
		if( $dinheiro > 0 ){
			$sql = "INSERT INTO tbl_pessoa_movimento (pessoa,documento,valor,descricao,debito_credito)
				VALUES($pessoa,'$documento',$dinheiro,'$descricao','C')";
			$res = @pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);
			//echo $sql;
		}

	}
	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "<br>Foi lançado um <u>crédito</u> no valor de R$ $dinheiro com sucesso!<br>&nbsp;";
        $documento    = '';
		$dinheiro     = '';
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

if(strlen($_GET["pessoa"])>0)$pessoa = $_GET["pessoa"];
if(strlen($pessoa)>0){
        
    $sql = "SELECT nome FROM tbl_pessoa WHERE pessoa = $pessoa";
    $res = pg_exec($sql);
    if(pg_numrows($res)>0){
        $nome = pg_result($res,0,0);
    }
}

?>
<link type="text/css" rel="stylesheet" href="css/estilo.css"> 
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


function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value=0;
	}
}
</script>
<?

if (strlen($msg_erro)>0)             echo "<div class='Erro'>$msg_erro</div>";
$valor_receber = 0;

if( strlen($_GET["receber"])  > 0 ) $receber  = $_GET["receber"];
if( strlen($_POST["receber"]) > 0 ) $receber  = $_POST["receber"];
if( strlen($dinheiro)        == 0 ) $dinheiro = 0;




if (strlen($ok)>0 OR strlen($msg)>0) {
	echo "<center><br><div class='ok'>$msg</div></center>";
}else{

	echo "<br><table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='400'>";
	echo "<form method='POST' name='frm' action='$PHP_SELF'>";
    echo "<input type='hidden' name='pessoa' value='$pessoa'>";
    echo "<tr height='20'>";
    echo "<td  colspan='2'><b>$nome</b></td>";
    echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Documento</td>";
	echo "<td align='left'><input type='text' size='40' name='documento' class='Caixa' value='$documento'></td>";
	echo "</tr>";
	echo "<td>Descrição</td>";
	echo "<td align='left'><input type='text' size='40' name='descricao' class='Caixa' value='$descricao'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Dinheiro</td>";
	echo "<td align='left'>";
	echo "<input name='dinheiro' id='dinheiro' type='text' onblur=\"javascript:checarNumero(this);\" value='$dinheiro' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";

	//botão de receber
	echo "<td align='center'bgcolor='#FFFFFF' colspan='2'><input type='submit' name='btn_acao' value='Lançar Crédito'style='width: 140px;height:30px;font-size:12px '></td>";

	echo "</tr>";
	echo "</form>";
	echo "</table>";
}

$sql = "SELECT * FROM tbl_pessoa_movimento WHERE pessoa = $pessoa";
    $res = pg_exec ($con,$sql);
    
if (@pg_numrows($res) > 0) {

    echo "<P><font size='2'><b>Débito/Créditos Lançados";
	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='550'>";
	echo "<TR height='20' bgcolor='#DDDDDD' align='center'>";
	echo "<TD ><b>Documento</b></TD>";
	echo "<TD width='25'><b>Descrição</b></TD>";
	echo "<TD width='100' align='right'><b>Valor</b></TD>";
	echo "<TD width='100' align='right'><b>Saldo</b></TD>";
	echo "</TR>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$x=$i+1;
		$documento         = pg_result($res,$i,documento);
		$valor             = pg_result($res,$i,valor);
		$descricao         = pg_result($res,$i,descricao);
		$debito_credito    = pg_result($res,$i,debito_credito); 

		
		if($debito_credito == 'C') $saldo = $saldo + $valor;
		else                       $saldo = $saldo - $valor;
		$valor             = number_format($valor,2,',','.');
		$saldo             = number_format($saldo,2,',','.'); 
		
		if($saldo < 0)$cor2 = "#990000";
		else          $cor2 = "#009900";        

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';
	//<a href='contas_receber.php?receber=$contas_receber'></a>
		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'>$documento</a></TD>";
		echo "<TD align='center'nowrap>$descricao</TD>";
		echo "<TD align='right'nowrap>$valor</TD>";
		echo "<TD align='right'nowrap><font color='$cor2' size='2'><b>$saldo</b></font></TD>"; 
		echo "</TR>";

	}
	echo " </TABLE>";

}else{
    echo "<b>Nenhuma crédito lançado</b>";
}

?>