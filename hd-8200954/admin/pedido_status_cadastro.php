<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$seleciona_status = 'f';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0) {
	$btn_acao = trim($_POST["btn_acao"]);
}

// grava novo status do pedido
if ($btn_acao == "gravar") {
	
	if($_POST['pedido'])        $pedido        = $_POST['pedido'];
	if($_POST['status_pedido']) $status_pedido = trim($_POST['status_pedido']);

	if (strlen($pedido) > 0) {
		$sql = "INSERT INTO tbl_pedido_status (
					pedido,
					data  ,
					status
				) VALUES (
					$pedido          ,
					current_timestamp,
					$status_pedido
				)";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0){
		echo "<SCRIPT LANGUAGE='JavaScript'>\n";
		echo "	opener.location.reload(1);\n";
		echo "	window.close();\n";
		echo "</SCRIPT>\n";
		exit;
	}
}
// grava novo status do pedido

$title = "Altera status do pedido";

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

</style>

</HEAD>

<body>

<br>

<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='0'>
<TR class='table_line'>
	<td align='center' background='#D9E2EF'><? echo $title .": <b>".$pedido."</b>"; ?></td>
</TR>
</TABLE>

<?
if (strlen($msg_erro) > 0){
?>
<br>
<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='0'>
<TR>
	<td align='center' background='#ff0000'><? echo $msg_erro; ?></td>
</TR>
</TABLE>
<br>
<?
}
?>

<?
	if($_GET['pedido'])         $pedido = $_GET['pedido'];

	// seleciona o último status do pedido selecionado
	$sql = "SELECT  tbl_status_pedido.work_flow
			FROM	tbl_status_pedido
			JOIN	tbl_pedido_status ON tbl_status_pedido.status_pedido = tbl_pedido_status.status
			WHERE	tbl_pedido_status.pedido = '$pedido'
			ORDER BY tbl_pedido_status.status DESC LIMIT 1";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 0)
		$work_flow_inicial = 0;
	else
		$work_flow_inicial = trim(pg_result ($res,0,work_flow));

	// seleciona os status já existentes para pedido selecionado
	$sql = "SELECT  *
			FROM	tbl_status_pedido
			WHERE	work_flow > $work_flow_inicial
			AND		work_flow = $work_flow_inicial + 1
			ORDER BY work_flow ASC ";

	//if ($seleciona_status == 'f') $sql .= " LIMIT 1";

	$res = pg_exec($con,$sql);

	if (@pg_numrows($res) == 0) {
		echo "<BR><BR>";
		echo "<TABLE width='100%' align='center' border='0' cellspacing='2' cellpadding='3'>\n";
		echo "<TR>\n";
		echo "	<TD class='menu_top'>NÃO EXISTEM MAIS OPÇÕES DE STATUS PARA ESTE PEDIDO</TD>\n";
		echo "</TR>\n";
		echo "</TABLE>\n";
	}elseif (@pg_numrows($res) > 0) {
		echo "<FORM name='frm_pedido_status' action='$PHP_SELF' method='post'>\n";
		echo "<input type='hidden' name='pedido' value='$pedido'>\n";

		echo "<br>\n";
		echo "<TABLE width='100%' align='center' border='0' cellspacing='2' cellpadding='3'>\n";

		echo "<TR>\n";
		echo "	<TD class='menu_top'>";
		if ($seleciona_status <> 't') echo "PRÓXIMO ";
		echo "STATUS</TD>\n";
		echo "</TR>\n";

		echo "<TR style='background-color: $cor;'>\n";
		echo "	<TD class='table_line' align='center'>\n";

		if ($seleciona_status == 't'){
			echo "		<select class='frm' name='status_pedido'>\n";
			for ($i = 0 ; $i < pg_numrows($res); $i++){
				$status_pedido = trim(pg_result ($res,$i,status_pedido));
				$descricao     = trim(pg_result ($res,$i,descricao));
				$work_flow     = trim(pg_result ($res,$i,work_flow));

				echo "			<option value='$status_pedido'"; 
				if ($work_flow == $work_flow_inicial) echo " SELECTED "; 
				echo ">$descricao</option>\n";
			}
			echo "		</select>\n";
		}else{
			if(pg_numrows($res) > 1){
				echo "		<select class='frm' name='status_pedido'>\n";
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$status_pedido = trim(pg_result ($res,$i,status_pedido));
					$descricao     = trim(pg_result ($res,$i,descricao));
					echo "			<option value='$status_pedido'>$descricao</option>\n";
				}
				echo "		</select>\n";
			}else{
				// exibe próximo status para confirmação
				$status_pedido = trim(pg_result ($res,$i,status_pedido));
				$descricao     = trim(pg_result ($res,$i,descricao));

				echo $descricao;
				echo "<INPUT TYPE='hidden' name='status_pedido' value='$status_pedido'>\n";
			}
		}

		echo "	</TD>\n";
		echo "</TR>\n";
		echo "<TR align='center'>\n";
		echo "	<TD><input type='hidden' name='btn_acao' value=''><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_pedido_status.btn_acao.value == '' ) { document.frm_pedido_status.btn_acao.value='gravar' ; document.frm_pedido_status.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar novo status do pedido $pedido' border='0' style='cursor:pointer;'></TD>\n";
		echo "</TR>\n";
		echo "</TABLE>\n";
		echo "</FORM>\n";

	}

//$sql = "delete from tbl_pedido_status where pedido = '$pedido'";
//$res = pg_exec($con,$sql);
?>

</BODY>
</HTML>