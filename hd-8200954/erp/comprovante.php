<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

if(strlen($_POST["btn_acao"])>0){

	$pessoa  = trim($_POST["pessoa"]);
	$valor   = trim($_POST["valor"]);
	$tipo    = trim($_POST["tipo"]);
	$descricao   = trim($_POST["descricao"]);

	//--===== VALIDA SE O TOTAL ESTÁ MENOR QUE O VALOR DO DOCUMENTO =====================================================================

	/*if($total_receber > $total)
		 $msg_erro = "Valor total pago (R$ $total) não pode ser menor que o valor a receber(R$ $total_receber)";
	if($troco > $valor)
		 $msg_erro = "Valor do troco (R$ $troco) não pode ser menor que o valor em valor(R$ $valor)";*/
	//--=================================================================================================================================

	if(strlen($pessoa  )== 0) $msg_erro = "Por favor digite o pessoa";
	if(strlen($tipo  )== 0) $msg_erro .= "Digite a Tipo<br>";
	if(strlen($descricao  )== 0) $msg_erro .= "Digite a Descrição<br>";
	if($valor           == 0) $msg_erro .= "Por favor entre com o valor<br>";

	if(strlen($msg_erro)==0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//INSERE MOVIMENTAÇÃO DE CHEQUE
		if( $valor > 0 ){
			$sql = "INSERT INTO tbl_comprovante (pessoa, tipo, descricao , valor)
				VALUES($pessoa, $tipo,'$descricao', $valor)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			//echo $sql;
		}

	}
	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Movimento Caixa/Banco lançado com sucesso!";
		$tipo    = '';
		$valor     = '';
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


include "menu.php";
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


function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value=0;
	}
}
</script>
<?

if (strlen($msg_erro)>0)             echo "<div class='error'>$msg_erro</div>";
$valor_receber = 0;

if( strlen($_GET["receber"])  > 0 ) $receber  = $_GET["receber"];
if( strlen($_POST["receber"]) > 0 ) $receber  = $_POST["receber"];
if( strlen($valor)        == 0 ) $valor = 0;




if (strlen($ok)>0 OR strlen($msg)>0) {
	echo "<div class='ok'>$msg</div>";
}

	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='400'>";
	echo "<form method='POST' name='frm' action='$PHP_SELF'>";
	echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";
	echo "<td colspan='2' align='center'><b>Movimento Caixa/Banco</b></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Pessoa</td>";
	echo "<td align='left'><input type='text' size='40' name='pessoa' class='Caixa' value='$pessoa'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>tipo</td>";
	echo "<td align='left'><input type='text' size='40' name='tipo' class='Caixa' value='$tipo'></td>";
	echo "</tr>";
	echo "<td>Descrição</td>";
	echo "<td align='left'><input type='text' size='40' name='descricao' class='Caixa' value='$descricao'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>valor</td>";
	echo "<td align='left'>";
	echo "<input name='valor' id='valor' type='text' onblur=\"javascript:checarNumero(this);recalcular()\" value='$valor' class='CaixaValor' size='6'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";

	//botão de receber
	echo "<td align='center'bgcolor='#FFFFFF' colspan='2'><input type='submit' name='btn_acao' value='Lançar Movimento'style='width: 180px;height:40px;font-size:12pt '></td>";

	echo "</tr>";
	echo "</form>";
	echo "</table>";




include "rodape.php";
?>