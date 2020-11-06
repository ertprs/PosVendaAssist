<?

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$qtde_os = $_POST['qtde_os'];
	for($x=0;$qtde_os>$x;$x++){
		$motivo_30          = $_POST['motivo_30_'.$x];
		$motivo_resposta_30 = trim($_POST['motivo_resposta_30_'.$x]);
		$motivo_60          = $_POST['motivo_60_'.$x];
		$motivo_resposta_60 = trim($_POST['motivo_resposta_60_'.$x]);
		if(strlen($motivo_resposta_30)>0){
			$sql = "UPDATE tbl_os set motivo_atraso = '$motivo_resposta_30'
					where os = $motivo_30
					and fabrica = $login_fabrica 
					and posto = $login_posto";
		//	echo "<BR>$sql";
			$res = pg_exec($con,$sql);	
		}
		if(strlen($motivo_resposta_60)>0){
			$sql = "UPDATE tbl_os_extra set motivo_atraso2 = '$motivo_resposta_60'
					where os = $motivo_60";
		//	echo "<BR>$sql";
			$res = pg_exec($con,$sql);	
		}
	}

}


/*if(strlen($_COOKIE["OS_ABERTA"])==0){
$confirma = $_GET['confirma'];
if(strlen($confirma)>0){
	setcookie ("OS_ABERTA", "", time() - 120);
	setcookie("OS_ABERTA", "true", time()+120);

//echo "aa> ".$_COOKIE["OS_ABERTA"];
	header('Location: menu_inicial.php');
}else{*/
	$excluir = $_GET['excluir'];
	if (strlen ($excluir) > 0) {
		$sql = "SELECT fn_os_excluida($excluir,$login_fabrica,null);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	$fechar = $_GET['fechar'];
	if (strlen ($fechar) > 0) {
		$msg_erro = "";
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $fechar AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con) ;
//echo $sql;
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_os($fechar, $login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con) ;
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}
		//exit;
	//}
	}
?>
<style type='text/css'>
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<?

//echo nl2br($sql);
//$wwres = pg_exec($con,$sql);
if(pg_numrows($wwres)>0){
	
	echo "<table border='0' cellpadding='5' cellspacing='1' width='500'  align='center' bgcolor='#FF9900'>\n";
	echo "<tr>\n";
	echo "<td bgcolor='#FFCC99' align='center'><font size='2' face='verdana'>CASO A ORDEM DE SERVIÇO NÃO FOR FECHADA NUM PERIODO DE ATÉ 90 DIAS, SERÁ FECHADA AUTOMATICAMENTE E NÃO SERÁ PAGA A MÃO-DE-OBRA, DA MESMA.</font></td>";
	echo "</tr>";
	echo "</table>";
	echo "<BR><BR>";
	if(strlen($msg_erro)>0){
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<table border='0' cellpadding='5' cellspacing='1' width='100%'  align='center' bgcolor='#FF3333'>\n";
	echo "<tr>\n";
	echo "<td align='center'><font size='1' face='verdana'>$msg_erro</font></td>";
	echo "</tr>";
	echo "</table><BR>";
	
	}
	echo "<form name='form' action = '$PHP_SELF' method='post'>";
	echo "<table border='0' cellpadding='1' cellspacing='1' width='500'  align='center'>\n";
	echo "<tr>\n";
	echo "<td bgcolor='#FFFF66' width='10'>&nbsp;</td>";
	echo "<td><font size='1' face='verdana'>Ordem de Serviço aberta a <B>menos de 60 dias </B>e não fechada</font></td>";
	echo "</tr>";
		echo "<tr>\n";
	echo "<td bgcolor='#FF6600' width='10'>&nbsp;</td>";
	echo "<td><font size='1' face='verdana'>Ordem de Serviço aberta a <B>mais de 60 dias</B> e não fechada</font></td>";
	echo "</tr>";
		echo "<tr>\n";
	echo "<td bgcolor='#F54A2C' width='10'>&nbsp;</td>";
	echo "<td><font size='1' face='verdana'>Ordem de Serviço aberta a <B>mais de 90 dias</B> e não fechada</font></td>";
	echo "</tr>";
	echo "</table>";
	echo "<BR><BR>";
	echo "<table border='0' cellpadding='2' cellspacing='1' width='500'  align='center'>\n";
	echo "<tr class='Titulo'>\n";
	echo "<td>OS</td>";
	echo "<td>Data Abertura</td>";
	echo "<td>Dias em Aberto</td>";
	echo "<td>Produto</td>";
	echo "<td>Consumidor</td>";
	echo "<td>Ações</td>";
	echo "</tr>\n";
	for($i=0;pg_numrows($wwres)>$i;$i++){
		$os                 = pg_result($wwres,$i,os);
		$sua_os             = pg_result($wwres,$i,sua_os);
		$data_abertura      = pg_result($wwres,$i,data_abertura);
		$tempo_em_aberto    = pg_result($wwres,$i,tempo_em_aberto);
		$produto_referencia = pg_result($wwres,$i,produto_referencia);
		$produto_descricao  = pg_result($wwres,$i,produto_descricao);
		$consumidor_nome    = pg_result($wwres,$i,consumidor_nome);
		$motivo_atraso      = pg_result($wwres,$i,motivo_atraso);
		$motivo_atraso2      = pg_result($wwres,$i,motivo_atraso2);

		$cor = ($i % 2 == 0) ? "#d2d7e1" : "#efeeea";
		if($tempo_em_aberto<60)$cor = "#FFFF66";
		if($tempo_em_aberto>60)$cor = "#FF6600";
		if($tempo_em_aberto>90)$cor = "#F54A2C";
		echo "<tr class='Conteudo'>\n";
		echo "<td bgcolor='$cor'><a href='os_item_new.php?os=$os'>$sua_os</a></td>";
		echo "<td bgcolor='$cor'>$data_abertura</td>";
		echo "<td bgcolor='$cor' align='center'><b>$tempo_em_aberto</b></td>";
		echo "<td bgcolor='$cor' nowrap>$produto_referencia - $produto_descricao</td>";
		echo "<td bgcolor='$cor' nowrap>$consumidor_nome</td>";
		echo "<td bgcolor='$cor' nowrap>";
		echo "<a href=\"javascript: if (confirm('Desea realmente excluir la OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img border='0' src='imagens/btn_excluir.gif'></a> ";
		echo " <a href=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor não seja HOJE, utilize a opção de Fechamento de OS para informar a data correta! Confirma o fechamento da OS $sua_os com a data de HOJE?') == true) { window.location='$PHP_SELF?fechar=$os';}\"><img border='0' src='/assist/imagens/btn_fecha.gif'></a> ";
		echo "</td>";
		echo "</tr>\n";
		echo "<tr class='Conteudo'>\n";
		echo "<td bgcolor='$cor' colspan='3'><B>Motivo Atraso Fechamento:</b></td>";
		echo "<td bgcolor='$cor' nowrap colspan='3'>";
		if($tempo_em_aberto<60){
			echo "<input type='hidden' name='motivo_30_$i' value='$os'><input type='text' class='caixa' name='motivo_resposta_30_$i'size='70' maxlength='255' value='$motivo_atraso'>";
		}
		if($tempo_em_aberto>60){
			echo "<input type='hidden' name='motivo_60_$i' value='$os'><input type='text' class='caixa' name='motivo_resposta_60_$i'size='70' maxlength='255' value='$motivo_atraso2'>";
		}
		echo "</td>";
		echo "</tr>\n";
	}
	echo "</table>";
	echo "<input type='hidden' name='qtde_os' value='$i'>";
	echo "<center><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.form.btn_acao.value == '' ) { document.form.btn_acao.value='gravar' ; document.form.submit() } else { alert ('Aguarde submissão') }\" ALT=\"Gravar\" border='0' style=\"cursor:pointer;\"></center>";
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "</form>";
//echo "<BR><BR><font size='2' face='verdana'><center><B><a href='$PHP_SELF?confirma=true'>Li e estou Ciente</a></b></center></font>";
exit;
}



?>