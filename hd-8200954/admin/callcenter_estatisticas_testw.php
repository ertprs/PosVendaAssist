<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";

include "cabecalho.php";

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>

<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,atendente){
	if (typeof atendente == 'undefined') {
		atendente = '';
	}
	janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter_ebano.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}

/* POP-UP IMPRIMIR */
	function abrir(URL) { 
		var width = 700; 
		var height = 600; 
		var left = 90; 
		var top = 90; 

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no'); 
	} 

</script>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>

<? include "javascript_pesquisas.php" ?>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório Período de Atendimentos</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td> 
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr class="Conteudo" bgcolor="#D9E2EF">
				<td width="10">&nbsp;</td>	
		<? /*
					<td align='right'><font size='2'>Natureza</td>
					<td align='left'>
					<select name='natureza_chamado' class='Caixa'>
					<option value=''></option>

					<?PHP //HD39566
					$sqlx = "SELECT nome            ,
									descricao       
							FROM tbl_natureza
							WHERE fabrica=$login_fabrica
							AND ativo = 't'
							ORDER BY nome";

					$resx = pg_exec($con,$sqlx);
						if(pg_numrows($resx)>0){
							for($y=0;pg_numrows($resx)>$y;$y++){
								$nome     = trim(pg_result($resx,$y,nome));
								$descricao     = trim(pg_result($resx,$y,descricao));
								echo $nome;
								echo "<option value='$nome'";
									if($natureza_chamado == $nome) {
										echo "selected";
									}
								echo ">$descricao</option>";
							}
						
						}
						</select>
					</td>
					*/
					?>
					<td align='right'><font size='2'>Status</td> 
					<td align='left' colspan='3'>
					<select name="status" size="1" class='Caixa'>
					<option value=''></option>
					<?
						$sql = "select distinct status from tbl_hd_chamado where fabrica_responsavel = $login_fabrica";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res)>0){
							for($x=0;pg_numrows($res)>$x;$x++){
								$xstatus = pg_result($res,$x,status);
								echo "<option value='$xstatus'"; if ($xstatus == $status) echo "selected";echo" >$xstatus</option>";
							
							}
						
						}
					?>
					</select>
					</td>
					<td width="10">&nbsp;</td>
					<td width="10">&nbsp;</td>
				</tr>
				<!-- HD 234177: Acrescentar busca por atendente -->
			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>


<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];
	$atendente          = $_POST['atendente'];

	
	$cond_1 = " 1 = 1 ";
	$cond_2 = " AND 1 = 1 ";
	$cond_3 = " AND 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";
	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = "Por favor informar a data inicial";
	}
	
	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = "Por favor informar a data final";
	}

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	
	if(strlen($status)>0){
		$cond_3 = " AND tbl_hd_chamado.status = '$status'  ";
	}

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if(strlen($atendente)>0){
		$cond_5 = " tbl_hd_chamado.atendente = '$atendente'  ";
	}


	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}

	if(strlen($msg_erro)==0){

		$sql = "SELECT 
					count(CASE WHEN 
								tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento' 
						  END),
					CASE WHEN 
							tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento' 
					END
				FROM tbl_hd_chamado 
				WHERE fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				$cond_3
			GROUP BY CASE WHEN tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento' END";
		//echo nl2br($sql);
		//	echo $sql;
//		if($ip == '200.228.76.102') echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<br><table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR height=30>\n";
			echo "<td class='menu_top' colspan=3>Visão Geral</u></TD>\n";
			echo "</TR >\n";

			for ($i=0;$i<pg_num_rows($res);$i++) {
				$count[] = pg_result($res,$i,0);
				$count2 = pg_result($res,$i,0);
				$tipo = pg_result($res,$i,1);
				$total += $count2 ;
				$total2 += $count2 ;
			}

				echo "<tr>
				<td align='left'>Fale Conosco</td>
				<td>$count[0]</td>";
				$porc1 = ($count[0]/$total)*100;
				$porc1 = number_format($porc1,'2','.','.');
				echo "<td>$porc1 %</td>";
			echo "</tr>";
			echo "<tr>
				<td align='left'>Atendimento</td>
				<td>$count[1]</td>";
				$porc2 = ($count[1]/$total)*100;
				$porc2 = number_format($porc2,'2','.','.');
				echo "<td>$porc2 %</td>";
			echo "<tr>";
			echo "<td align='left'>Total</td>";
			echo "<td>$total</td>";
			echo "<td>100%</td>";
			echo "</tr>";
			echo "</table>";
			echo "<BR><BR>";

			$total_fale = $count[0];
			$total_aten = $count[1];
		
		}
		######################PARTE 2##################################

		$sql = "SELECT 
					count(*),tbl_admin.login
				FROM tbl_hd_chamado 
				JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin 
				WHERE tbl_hd_chamado.fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				$cond_3
				group by tbl_admin.login";
		//echo nl2br($sql);
		//	echo $sql;
//		if($ip == '200.228.76.102') echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<br><table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR height=30>\n";
			echo "<td class='menu_top' colspan=3>ESTATISTICAS DA VISÃO GERAL</u></TD>\n";
			echo "</TR >\n";
			echo "<td align='left'>Total</td>";

			$total = '';
			for ($i=0;$i<pg_num_rows($res);$i++) {
				$count = pg_result($res,$i,0);
				$login = pg_result($res,$i,1);
				$total += $count;
				
				echo "<tr>
				<td align='left'>$login</td>
				<td>$count</td>";
				$porc2 = ($count/$total2)*100;
				$porc2 = number_format($porc2,'2','.','.');
				echo "<td>$porc2 %</td>";
				echo "<tr>";
				echo "</tr>";
			}
			echo "<tr>";
			echo "<td align='left'>Total</td>";
			echo "<td>$total</td>";
			echo "<td>100%</td>";
			echo "</tr>";

			echo "</table>";
		
			echo "<BR><BR>";
			

		}

		######################PARTE 3##################################

		$sql = "SELECT 
					count(*),tbl_admin.login
				FROM tbl_hd_chamado 
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin 
				WHERE tbl_hd_chamado.fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'  and tbl_hd_chamado.admin = 2473
				$cond_3
				group by tbl_admin.login";
		//echo nl2br($sql);
		//	echo $sql;
//		if($ip == '200.228.76.102') echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<br><table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR height=30>\n";
			echo "<td class='menu_top' colspan=3>ESTATISTICAS DO FALE CONOSCO</u></TD>\n";
			echo "</TR >\n";

			for ($i=0;$i<pg_num_rows($res);$i++) {
				$count = pg_result($res,$i,0);
				$login = pg_result($res,$i,1);
				
				echo "<tr>
				<td align='left'>$login</td>
				<td>$count</td>";
				$porc2 = ($count/$total_fale)*100;
				$porc2 = number_format($porc2,'2','.','.');
				echo "<td>$porc2 %</td>";
				echo "</tr>";
			}

			echo "<tr>";
			echo "<td align='left'>Total</td>";
			echo "<td>$total_fale</td>";
			echo "<td>100%</td>";
			echo "</tr>";


			echo "</table>";
		
			echo "<BR><BR>";
		
		}

		######################PARTE 4##################################

		$sql = "SELECT 
					count(*),tbl_admin.login
				FROM tbl_hd_chamado 
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin 
				WHERE tbl_hd_chamado.fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'  and tbl_hd_chamado.admin <> 2473
				$cond_3
				group by tbl_admin.login";
		//echo nl2br($sql);
		//	echo $sql;
//		if($ip == '200.228.76.102') echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<br><table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR height=30>\n";
			echo "<td class='menu_top' colspan=3>ESTATISTICAS DO ATENDIMENTO</u></TD>\n";
			echo "</TR >\n";

			for ($i=0;$i<pg_num_rows($res);$i++) {
				$count = pg_result($res,$i,0);
				$login = pg_result($res,$i,1);
				
				echo "<tr>
				<td align='left'>$login</td>
				<td>$count</td>";
				$porc2 = ($count/$total_aten)*100;
				$porc2 = number_format($porc2,'2','.','.');
				echo "<td>$porc2 %</td>";

				echo "</tr>";
			}

			echo "<tr>";
			echo "<td align='left'>Total</td>";
			echo "<td>$total_aten</td>";
			echo "<td>100%</td>";
			echo "</tr>";


			echo "</table>";
		
			echo "<BR><BR>";
		
		}


	}
}

if(strlen($msg_erro)>0){
echo "<center>$msg_erro</center>";

}
?>

<p>

<? include "rodape.php" ?>
