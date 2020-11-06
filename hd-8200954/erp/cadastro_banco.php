<?

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

$alterar=trim($_GET['alterar']);


if(strlen($_POST["btn_acao"])>0){

	$pessoa       = trim($_POST["pessoa"]);
	$pessoa_banco = trim($_POST["pessoa_banco"]);
	$banco        = trim($_POST["banco"]);
	$agencia      = trim($_POST["agencia"]);
	$conta        = trim($_POST["conta"]);
	$tipo_conta   = trim($_POST["tipo_conta"]);
	$favorecido   = trim($_POST["favorecido"]);
	$observacao   = trim($_POST["observacao"]);

	if(strlen($banco      )== 0) $msg_erro  = "Selecione o banco<br>";
	if(strlen($agencia    )== 0) $msg_erro .= "Digite o número da agência<br>";
	if(strlen($conta      )== 0) $msg_erro .= "Digite o número da conta<br>";
	if(strlen($favorecido )== 0) $msg_erro .= "Digite o nome do favorecido<br>";
	if(strlen($tipo_conta )== 0) $msg_erro .= "Selecione o tipo da conta<br>";
	
	if(strlen($observacao )== 0) $observacao ='null';

	if(strlen($msg_erro)==0){

		$sqlB = "SELECT nome FROM tbl_banco WHERE codigo = '$banco'";
		$resB = pg_exec($con,$sqlB);
		if (pg_numrows($resB) == 1) $xnomebanco = "'" . trim(pg_result($resB,0,0)) . "'";
		else                        $xnomebanco = null;

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if( $pessoa_banco == 0 ){
			$sql = "INSERT INTO tbl_pessoa_banco (
						pessoa    ,
						banco     ,
						nome      ,
						agencia   ,
						conta     ,
						favorecido,
						tipo_conta,
						observacao
				)VALUES(
					$pessoa      ,
					$banco       ,
					$xnomebanco  ,
					'$agencia'   ,
					'$conta'     ,
					'$favorecido',
					'$tipo_conta',
					'$observacao'
				)";
		}else{
			$sql = "UPDATE tbl_pessoa_banco SET
						banco      = $banco       ,
						nome       = $xnomebanco  ,
						agencia    = '$agencia'   ,
						conta      = '$conta'     ,
						favorecido = '$favorecido',
						tipo_conta = '$tipo_conta',
						observacao = '$observacao'
					WHERE pessoa_banco = $pessoa_banco
					AND   pessoa       = $pessoa";
		}

		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);

		if( $pessoa_banco == 0 ){
			$res = @pg_exec ($con,"SELECT currval('tbl_pessoa_banco_pessoa_banco_seq')");
			$pessoa_banco = pg_result($res,0,0);
		}

		$sql = "UPDATE tbl_pessoa_fornecedor
				SET pessoa_banco = $pessoa_banco
				WHERE pessoa     = $pessoa";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

	}
	if(strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "<br>Gravado com sucesso!<br>&nbsp;";

		?>
		<html>
		<head>
		</head>
		<body>
		<script language='javascript'>
			//opener.document.reload();;
			window.opener.location.href = window.opener.location.href;
			setTimeout('this.close()',2000);
		</script>
		</body>
		</html>
		<?

	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

if(strlen($_GET["pessoa"])      >0) $pessoa       = $_GET["pessoa"];
if(strlen($_GET["pessoa_banco"])>0) $pessoa_banco = $_GET["pessoa_banco"];
if(strlen($_GET["redir"])>0)        $redirecionar = $_GET["redir"];


if(strlen($pessoa)>0){
	$sql = "SELECT nome FROM tbl_pessoa WHERE pessoa = $pessoa";
	$res = pg_exec($sql);
	if(pg_numrows($res)>0){
		$pessoa_nome = pg_result($res,0,0);
	}
}

if($redirecionar == 1 AND strlen($pessoa_banco)>0 AND strlen($pessoa)>0){
	$sql = "UPDATE tbl_pessoa_fornecedor
			SET pessoa_banco = $pessoa_banco
			WHERE pessoa     = $pessoa";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	?>
	<script language='javascript'>
		//opener.document.reload();;
		window.opener.location.href = window.opener.location.href;
		this.close();
	</script>
	<?
	exit;
}


if(strlen($pessoa_banco)>0){
	$sql = "SELECT  pessoa_banco,
					banco       ,
					nome        ,
					agencia     ,
					conta       ,
					tipo_conta  ,
					favorecido  ,
					observacao
			FROM tbl_pessoa_banco 
			WHERE pessoa_banco = $pessoa_banco
			AND   pessoa       = $pessoa";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0) {
		$pessoa_banco      = pg_result($res,$i,pessoa_banco);
		$banco             = pg_result($res,$i,banco);
		$nome              = pg_result($res,$i,nome);
		$agencia           = pg_result($res,$i,agencia); 
		$conta             = pg_result($res,$i,conta); 
		$tipo_conta        = pg_result($res,$i,tipo_conta); 
		$favorecido        = pg_result($res,$i,favorecido); 
		$observacao        = pg_result($res,$i,observacao); 
	}
}

?>
<link type="text/css" rel="stylesheet" href="css/estilo.css"> 
<style>
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
<?

if (strlen($msg_erro)>0) echo "<div class='Erro'>$msg_erro</div>";

if (strlen($ok)>0 OR strlen($msg)>0) {
	echo "<center><br><div class='ok'>$msg</div></center>";
}else{
	echo "<BR><font size=3><b>Cadastrar uma nova conta</b></font>";
	echo "<br><BR><table border='0' cellpadding='2' cellspacing='0' class='HD' align='rigth' width='400'>";
	echo "<form method='POST' name='frm' action='$PHP_SELF'>";
	
	echo "<input type='hidden' name='pessoa' value='$pessoa'>";
	echo "<input type='hidden' name='pessoa_banco' value='$pessoa_banco'>";

	echo "<tr height='20'>";
	echo "<td  colspan='2'><b>$pessoa_nome</b></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Banco</td>";
	echo "<td align='left'>";

	$sqlB =	"SELECT codigo, nome
			FROM tbl_banco
			ORDER BY codigo";
	$resB = pg_exec($con,$sqlB);

	if (pg_numrows($resB) > 0) {
		echo "<select name='banco' size='1' class='Caixa'>";
		echo "<option value=''></option>";
		for ($x = 0 ; $x < pg_numrows($resB) ; $x++) {
			$aux_banco     = pg_result($resB,$x,codigo);
			$aux_banconome = pg_result($resB,$x,nome);
			echo "<option value='" . $aux_banco . "'";
			if ($banco == $aux_banco) echo " selected";
			echo ">" . $aux_banco . " - " . $aux_banconome . " $aux_banco</option>";
		}
		echo "</select>";
	}

	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Agência</td>";
	echo "<td align='left'><input type='text' size='10' name='agencia' class='Caixa' value='$agencia'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Conta</td>";
	echo "<td align='left'><input type='text' size='10' name='conta' class='Caixa' value='$conta'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Tipo Conta</td>";
	echo "<td align='left'>";
	echo "<select name='tipo_conta' class='Caixa'>";
	echo "<option selected></option>";
	echo "<option value='Conta conjunta' ";
	if ($tipo_conta == 'Conta conjunta')   echo "selected";
	echo ">Conta conjunta</option>";
	echo "<option value='Conta corrente' ";
	if ($tipo_conta == 'Conta corrente')   echo "selected"; 
	echo ">Conta corrente</option>";
	echo "<option value='Conta individual' ";
	if ($tipo_conta == 'Conta individual') echo "selected"; 
	echo ">Conta individual</option>";
	echo "<option value='Conta jurídica' ";
	if ($tipo_conta == 'Conta jurídica')   echo "selected"; 
	echo ">Conta jurídica</option>";
	echo "<option value='Conta poupança' ";
	if ($tipo_conta == 'Conta poupança')   echo "selected"; 
	echo ">Conta poupança</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Favorecido</td>";
	echo "<td align='left'><input type='text' size='40' name='favorecido' class='Caixa' value='$favorecido'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
	echo "<td>Observação</td>";
	echo "<td align='left'>";
	echo "<textarea class='Caixa' name='observacao' rows='3' cols='50'>$observacao</textarea>";
	echo "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#ddf8cc' class='Conteudo'>";

	//botão de receber
	echo "<td align='center'bgcolor='#FFFFFF' colspan='2'><input type='submit' name='btn_acao' value='Gravar Conta'style='width: 140px;height:30px;font-size:12px '></td>";

	echo "</tr>";
	echo "</form>";
	echo "</table>";
}
if(strlen($pessoa) > 0) {
$sql = "SELECT  pessoa_banco,
				banco       ,
				nome        ,
				agencia     ,
				conta       ,
				tipo_conta 
		FROM tbl_pessoa_banco 
		WHERE pessoa = $pessoa
		ORDER BY banco::numeric ";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {

	echo "<BR><font size='3'><b><center>ou</center><BR>Selecionar a conta desejada para receber o depósito</b></font><BR><BR>";
	echo "<table border='0' cellpadding='2' cellspacing='0' class='HD' align='center' width='600'>";
	echo "<TR height='20' bgcolor='#DDDDDD' align='center'>";
	echo "<TD><b>Banco</b></TD>";
	echo "<TD><b>Agencia</b></TD>";
	echo "<TD><b>Conta</b></TD>";
	echo "<TD><b>Tipo Conta</b></TD>";
	echo "<TD></TD>";
	echo "</TR>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$x=$i+1;

		$pessoa_banco      = pg_result($res,$i,pessoa_banco);
		$banco             = pg_result($res,$i,banco);
		$nome              = pg_result($res,$i,nome);
		$agencia           = pg_result($res,$i,agencia); 
		$conta             = pg_result($res,$i,conta); 
		$tipo_conta        = pg_result($res,$i,tipo_conta); 

		if($cor1=="#eeeeee")$cor1 = '#ffffff';
		else                $cor1 = '#eeeeee';

		echo "<TR bgcolor='$cor1'class='Conteudo'>";
		echo "<TD align='left'><a href='$PHP_SELF?pessoa=$pessoa&pessoa_banco=$pessoa_banco&redir=1'>$banco - $nome</a></TD>";
		echo "<TD align='center'nowrap>$agencia</TD>";
		echo "<TD align='center'nowrap>$conta</TD>";
		echo "<TD align='center'nowrap>$tipo_conta</TD>";
		echo "</TR>";

	}
echo " </TABLE>";

}else{
    echo "<b>Nenhuma conta de banco lançada</b>";
}
}
?>