<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$title = "Telecontrol - Relatório de Número de Série  Reoperado";
include "cabecalho.php";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
	
	if ($btn_finalizar == 1 or strlen($serie_reoperado) > 0) {
	
		
		
		if(strlen($serie)>0 AND strlen($serie_reoperado)>0){
				$cond1 = "AND 1=1 ";
				$cond2 = "AND (tbl_os.serie = trim('$serie') OR tbl_os.serie_reoperado = trim('$serie_reoperado')) ";
				$cond3 = "AND 1=1 ";
		}else{
			if(strlen($codigo_posto) > 0){
				$cond1 = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
			}else{
				$cond1 = " AND 1=1";
			}
			if(strlen($serie)>0){
				$cond2 = "AND tbl_os.serie = trim('$serie')";
			}else{
				$cond2 = "AND 1=1";
			}

			if(strlen($serie_reoperado)>0){
				$cond3 = "AND tbl_os.serie_reoperado = trim('$serie_reoperado')";
			}else{
				$cond3 = "AND 1=1";
			}
		}

		if(strlen($serie) == 0 AND strlen($serie_reoperado) == 0){
				$cond2 = "AND (trim(substr(tbl_os.serie_reoperado,1,1)) ='9' OR trim(substr(tbl_os.serie,1,1)) ='9')";
				$cond3 = "AND 1=1 ";
		}
		if(strlen($serie_reoperado) == 0 and strlen($serie) == 0){
			$mes = trim(strtoupper($_POST['mes']));
			$ano = trim(strtoupper($_POST['ano']));
			$serie = $_POST['serie'];
			$serie_reoperado = $_POST['serie_reoperado'];

			if (strlen($mes) == 0 OR strlen($ano) == 0)  {
				$erro = "Digite o mês e o ano para fazer a pesquisa";
			}

			if (strlen($mes) > 0) {
				$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
				$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
				$cond4 = " AND finalizada BETWEEN '$data_inicial' AND '$data_final'";
			}else{
				$cond4 = "AND 1=1";
			}
		}

		if(strlen($codigo_posto) == 0 and strlen($serie) == 0 and strlen($serie_reoperado) == 0){
			$msg_erro = "Escolha o posto<BR>";
		}

		$msg_erro .= $erro;
		if (strlen($msg_erro) == 0) $listar = "ok";
	}


?>

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
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.conteudoB {
	border: 1px solid;
	border-color:#e6eef7;
	color: #000000;
	font-size: 11px;
	font-family: Arial;
	background-color: #FFFFFF;
}

.conteudoR {
	border: 1px solid;
	border-color:#e6eef7;
	color: #000000;
	font-size: 11px;
	font-family: Arial;
	background-color: #CCFFFF;
}


.conteudotop {
	border: 1px solid;
	border-color:#e6eef7;
	background-color: #e6eef7;
	color: #000000;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

</style>

<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>


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
	<TABLE width="400" align="center" border="1" cellspacing="0" cellpadding="2">
		<TR>
			<TD colspan="3" class="menu_top" background='imagens_admin/azul.gif' align='center'><b>Pesquisa</b></TD>
		</TR>
		<TR>
			<TD class="table_line" style="width: 60px">N. Série</TD>
			<TD class="table_line" style="width: 160px">N. Série Reoperado</TD>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</TR>
		<TR>
			<TD class="table_line" style="width: 100px"><INPUT TYPE="text" NAME="serie" size="14"  value="<? echo $serie ?>" class="frm" ></TD>
			<TD class="table_line" style="width: 100px"><INPUT TYPE="text" NAME="serie_reoperado" size="14"  value="<? echo $serie_reoperado ?>" class="frm" ></TD>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</TR>
	<tr>
		<TD class="table_line" style="width: 40px">* Mês</td>
		<TD class="table_line" style="width: 40px">* Ano</td>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
	<tr>
	<TD class="table_line" style="width: 10px">
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
	<TD class="table_line" style="width: 10px">
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
	</td>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
		<TR>
			<TD class="table_line" style="width: 80px">Cód. Posto</TD>
			<TD class="table_line" style="width: 80px">Nome Posto</TD>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</TR>
		<tr>
			<TD class="table_line" style="width: 90px">
			<input type="text" name="codigo_posto" size="8"  value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
			</td>
			<TD class="table_line" style="width: 400px">
			<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
			</td>
			<TD class="table_line" style="width: 10px">&nbsp;</TD>
		</tr>
		<? //botão pesquisa?>
		<TR>
			<input type='hidden' name='btn_finalizar' value='0'>
			<TD colspan="3" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
		</TR>
	</TABLE>
</FORM>
<BR>

<?
	if($listar=="ok"){

	echo "<TABLE align='center'>";
	echo "<TR>";
		echo "<TD bgcolor='#CCFFFF'  width='15'>&nbsp;</TD>";
		echo "<TD style='font: 12px Arial;'>Numero de Série Reoperado.</TD>";
	echo "</TR>";
	echo "</TABLE>";

	
	$sql ="select 
			distinct tbl_posto.nome,
			tbl_posto_fabrica.codigo_posto,
			tbl_os.os,
			tbl_os.sua_os,
			tbl_os.produto,
			tbl_produto.descricao,
			tbl_os.posto,
			tbl_os.serie,
			tbl_os.serie_reoperado
		from tbl_os
		JOIN tbl_posto USING(posto)
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_produto USING(produto)
		where tbl_os.fabrica = $login_fabrica
		$cond1
		$cond2
		$cond3
		$cond4";
		
		
		//echo $sql;
		$res = pg_exec ($con,$sql);


		if(pg_numrows($res)>0){
		echo "<center><TABLE width='400' border='0' cellspacing='0' cellpadding='4' align='center'>";
		echo "<thead>";
			echo "<TR class='conteudotop'>";
				echo "<TD height='15'><b>OS</b></TD>";
				echo "<TD height='15'><b>POSTO</b></TD>";
				echo "<TD height='15'><b>PRODUTO</b></TD>";
				echo "<TD height='15'><b>SÉRIE</b></TD>";
				echo "<TD height='15'><b>SÉRIE REOPERADO</b></TD>";
			echo "</TR>";
		echo "</thead>";
		echo "<tbody>";

		for ($i=0; $i<pg_numrows($res); $i++){
			$codigo_posto    = trim(pg_result($res,0,codigo_posto));
			$nome_posto      = trim(pg_result($res,0,nome));
			$os              = trim(pg_result($res,$i,os));
			$sua_os          = trim(pg_result($res,$i,sua_os));
			$produto_nome    = trim(pg_result($res,$i,descricao));
			$serie           = trim(pg_result($res,$i,serie));
			$serie_reoperado = trim(pg_result($res,$i,serie_reoperado));
			
				if(strlen($serie)>0){
					$xserie = $serie;
				}else{
					$xserie = "&nbsp;";
				}

				if(strlen($serie_reoperado)>0){
					$xserie_reoperado = $serie_reoperado;
				}else{
					$xserie_reoperado = "&nbsp;";
				}

				if($serie[0]=="9"){
					$class = "conteudoR";
				}else{
					$class = "conteudoB";
				}


			echo "</TR>";
				echo "<TD class='$class' align='left' nowrap title=''><A HREF='os_press.php?os=$os' target='_blanck'>$sua_os</A></TD>";
				echo "<TD class='$class' align='left' nowrap title=''>$codigo_posto - $nome_posto</TD>";
				echo "<TD class='$class' align='left' nowrap title=''>$produto_nome</TD>";
				if($serie[0]=="9"){
					echo "<TD class='$class' align='right' nowrap title=''><A HREF='$PHP_SELF?serie_reoperado=$xserie&codigo_posto=$codigo_posto&serie=$xserie'>$xserie</A></TD>";
				}else{
					echo "<TD class='$class' align='right' nowrap title=''>$xserie</TD>";
				}
				echo "<TD class='$class' align='right' nowrap title=''>$xserie_reoperado</TD>";
			echo "</TR>";
		}
		echo "</tbody>";
		echo "</TABLE>";

	}

	}

 include "rodape.php" ?>