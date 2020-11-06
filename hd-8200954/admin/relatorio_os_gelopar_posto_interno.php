
<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if (isset($_POST['btn_acao'])) {
	//HD 284058: Permitir que a pesquisa seja feita usando apenas o número da OS, se informou a OS não tem porque exigir data inicial e final. Coloquei o código que recupera a OS antes para poder tratar
	$os = $_POST['os'] ;

	if(strlen($os)>0) {
		$cond_os = " tbl_os.sua_os like '%$os%' ";
	}else{
		$cond_os = " 1 = 1 ";
	}

	//HD 284058: Se veio OS não precisa da data inicial e final
	if (strlen($erro) == 0 && strlen($os) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_inicial = @pg_fetch_result ($fnc,0,0);
		}
	}
	
	//HD 284058: Se veio OS não precisa da data inicial e final
	if (strlen($erro) == 0 && strlen($os) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_final = @pg_fetch_result ($fnc,0,0);
		}
	}
	//HD 284058: FIM
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg_erro .= $erro;
	}
	
}


$layout_menu = "gerencia";
$title = "RELATÓRIO OS POSTO INTERNO";

include "cabecalho.php";

?>

<script language="JavaScript">


</script>

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
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.botao{
	background-color: #D9E2EF;
	border:none;
}
</style>


<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario_new.php"; ?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>


<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?

if (strlen($msg_erro) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>
			
	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Relatório de Ordem de Serviço do Posto 10641</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este relatório considera a data de abertura da OS.</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><label for='data_inicial_01'>Data Inicial</label></TD>
    <TD class="table_line"><label for='data_final_01'>Data Final</label></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px">
		<INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<?=$data_inicial?>" tabindex='1'>
	</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<?=$data_final?>" tabindex='2'>
	</TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><label for='os'>OS</label></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px" colspan='2'>
		<INPUT size="12" maxlength="10" TYPE="text" NAME="os" id="os" value="<?=$os?>" tabindex='3'>
	</TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <input type='hidden' name='btn_acao' >
    <TD colspan="4" class="table_line" style="text-align: center;"><button class='botao'><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar' tabindex='4'></button></TD>
  </TR>
</TABLE>

</FORM>


<?
flush();

//HD 284058: Permitir que a pesquisa seja feita usando apenas o número da OS, se informou a OS não tem porque exigir data inicial e final
if (strlen($os) > 0 || (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0)) {
	if (strlen($os) > 0) {
		$cond_data = "1=1";
	}
	else {
		$cond_data = "tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}

	$sql="
		SELECT tbl_os.os,
				tbl_os.sua_os,
				tbl_produto.familia,
				tbl_posto_fabrica.tipo_posto,
				tbl_posto_linha.tabela
		FROM tbl_os
		JOIN tbl_produto       ON tbl_produto.produto     = tbl_os.produto 
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_os.posto
		WHERE tbl_os.fabrica = $login_fabrica 
		AND   tbl_os.posto   = 60908
		AND   defeito_constatado IS NOT NULL
		AND   $cond_os
		AND   $cond_data
		";
	//HD 284058: FIM 
	$res = @pg_query($con, $sql);
	$qtde = pg_num_rows($res);
	if(pg_num_rows($res)>0){
		echo "<br/>";
		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
		echo "<tr height='25' style='color:#ffffff;font-weight:bold'>";
		echo "<td>OS</td>";
		echo "<td>Defeito Constatado</td>";
		echo "<td>Solução</td>";
		echo "<td>Mão-De-Obra</td>";
		echo "<td>Peças</td>";
		echo "<td>Mão-De-Obra + Peças</td>";
		echo "</tr>";
		for ($i=0; $i<pg_num_rows($res); $i++){
			$os			= trim(pg_fetch_result($res,$i,os));
			$sua_os		= trim(pg_fetch_result($res,$i,sua_os));
			$familia	= trim(pg_fetch_result($res,$i,familia));
			$tipo_posto	= trim(pg_fetch_result($res,$i,tipo_posto));
			$tabela		= trim(pg_fetch_result($res,$i,tabela));

			if($tipo_posto == 239) {
				$sqls="SELECT mao_de_obra,tbl_defeito_constatado.descricao as dc_descricao,
								tbl_solucao.descricao as so_descricao
					FROM tbl_diagnostico
					JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado and tbl_os_defeito_reclamado_constatado.solucao = tbl_diagnostico.solucao
					JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
					JOIN tbl_solucao ON tbl_os_defeito_reclamado_constatado.solucao = tbl_solucao.solucao
					WHERE os = $os
					AND   tbl_diagnostico.familia = $familia
					ORDER BY mao_de_obra DESC LIMIT 1;";
			}else{
				$sqls="SELECT mao_de_obra_revenda,
								tbl_defeito_constatado.descricao as dc_descricao,
								tbl_solucao.descricao as so_descricao
					FROM tbl_diagnostico
					JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado and tbl_os_defeito_reclamado_constatado.solucao = tbl_diagnostico.solucao
					JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
					JOIN tbl_solucao ON tbl_os_defeito_reclamado_constatado.solucao = tbl_solucao.solucao
					WHERE os = $os
					AND   tbl_diagnostico.familia = $familia;";
			}
			$ress = pg_query($con,$sqls);
			if(pg_num_rows($ress) > 0){
				$mao_de_obra = pg_fetch_result($ress,0,0);
				$mao_de_obra = number_format($mao_de_obra ,2,",",".");
				$dc_descricao = pg_fetch_result($ress,0,1);
				$so_descricao = pg_fetch_result($ress,0,2);
			}
			
			$cor = ($y % 2 == 0) ? "#FFFFFF": '#f4f7fb';
			echo "<tr bgcolor='$cor'>";
			echo "<td class='table_line2'><a href='os_press.php?os=$os' target='blank'>$sua_os</A></td>";
			echo "<td class='table_line2' nowrap>$dc_descricao</td>";
			echo "<td class='table_line2' nowrap>$so_descricao</td>";
			echo "<td class='table_line2' nowrap>R$ $mao_de_obra</td>";

			$sqlp = "SELECT tbl_tabela_item.preco,
							tbl_peca.referencia,
							tbl_peca.descricao
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela=$tabela
					WHERE os = $os";
			$resp = pg_query($con,$sqlp);
			echo "<td class='table_line2' nowrap> ";
			if(pg_num_rows($resp) > 0){
				for($j =0;$j<pg_num_rows($resp);$j++) {
					$referencia = pg_fetch_result($resp,$j,referencia);
					$descricao  = pg_fetch_result($resp,$j,descricao);
					$preco      = pg_fetch_result($resp,$j,preco);
					echo $referencia . " - " . $descricao . " : R$ " . $preco . "<br/>";
				}
			}else{
					echo "Nenhuma peça encontrada";
			}
			echo "</td>";
			echo "<td>";
			$sqlps = "SELECT SUM(tbl_tabela_item.preco) AS preco
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela=$tabela
					WHERE os = $os";
			$resps = pg_query($con,$sqlps);
			if(pg_num_rows($resps) > 0){
				$preco = pg_fetch_result($resps,0,preco);
			}
			$mo_preco = $mao_de_obra + $preco;
			$mo_preco = number_format($mo_preco ,2,",",".");

			echo $mo_preco;
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<BR>";
	}else{
	echo "<center>Nenhuma Ordem de Serviço encontrada.</center>";
	}
}

?>

<? include "rodape.php"; ?>