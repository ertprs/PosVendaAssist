<?
# Ponto Digital
##	hd 22567 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($btn_acao=="Gravar"){
	$msg_erro = "";
	$j = 0;
	$sql = "begin transaction;";
	$res = pg_exec ($con,$sql);

	for($i=0;$i<48;$i++){
		$data			 = trim($HTTP_POST_VARS["data_" . $i]);
		$motivo			 = trim($HTTP_POST_VARS["motivo_" . $i]);
		$colaborador     = $HTTP_POST_VARS["colaborador_" . $i];
		$observacao      = trim($HTTP_POST_VARS["observacao_" . $i]);

		if (strlen($data1)==0 and strlen($data_tmp)==0 and strlen($motivo)==0 and strlen($colaborador)==0) {
			$j++;
			continue;
		} else {
			if (strlen($data)==0) {
				$msg_erro = "Reveja campos Data e Hora";
				break;
			}
			if (strlen($motivo)==0) {
				$msg_erro = "Reveja campos Motivo<br>";
				break;
			}
			if (strlen($colaborador)==0) {
				$msg_erro = "Reveja campos Colaborador<br>";
				break;
			}
		}

		$sql = "INSERT INTO tbl_ponto_digital (
									data			,
									motivo			,
									admin			,
									observacao		,
									digitador
								) VALUES (
									'$data'		 ,
									'$motivo'	 ,
									$colaborador ,
									'$observacao',
									$login_admin
								);";
		$res	= pg_exec ($con,$sql);
		$notice = pg_last_notice($con);
		if(strlen($notice)>0) echo "<table align='center'><tr align='center'><td align='center'><font color='red'><b>$notice<br><br>$data</b></font></td></tr></table>"; 
	}

	if (strlen($msg_erro) > 0) {
		$sql = "rollback;";
		$res = pg_exec ($con,$sql);		
	} else {
		if ($j==48){
			$msg_ok = "Todos os campos estão em branco";
		}else{
			$sql = "commit;";
			$res = pg_exec ($con,$sql);
			$msg_ok = "Incluído com sucesso";
			}
	}

}
?>


<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("input[@rel='data_hora']").maskedinput("99/99/99 99:99");
	});
</script>


<?

if (strlen($msg_erro)>0){ echo "<h1><center>$msg_erro</center></h1>";}
if (strlen($msg_ok)>0){ echo "<h1><center>$msg_ok</center></h1>";}

echo "<form name='frm_ponto_digital' method='post' action='$PHP_SELF'><BR>";
echo "<input type='hidden' name='ponto_digital' value='$ponto_digital'>";
echo "<table  align='center' width='700' border='0' class='conteudo' cellpadding='2' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td nowrap colspan='5' align='center'>LIVRO DE PONTO </td>";
echo "</tr>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td nowrap align='center'>Data e hora</td>";
echo "<td nowrap align='center'>Motivo</td>";
echo "<td nowrap align='center'>Colaborador</td>";
echo "<td nowrap align='center'>Observação</td>";
echo "</tr>";
	
	for($i=0;$i<48;$i++){
		if (strlen($msg_erro) > 0) {
			$data			 = trim($HTTP_POST_VARS["data_" . $i]);
			$motivo			 = trim($HTTP_POST_VARS["motivo_" . $i]);
			$colaborador     = $HTTP_POST_VARS["colaborador_" . $i];
			$observacao      = trim($HTTP_POST_VARS["observacao_" . $i]);
		}
		echo "<tr>";
		echo "<td align='left'><input type='text' name='data_$i' rel='data_hora' value='$data' size='18' maxlength='20'></td>";
		echo "<td  class ='sub_label' align='center'>";
		echo "<select class='frm' style='width: 150px;' name='motivo_$i'>\n";
		echo "<option value=''>- ESCOLHA -</option>\n";
		echo "<option value='Entrada'>Entrada</option>\n";
		echo "<option value='Almoço'>Almoço</option>\n";
		echo "<option value='Retorno'>Retorno</option>\n";
		echo "<option value='Saída'>Saída</option>\n";
		echo "</select>\n";
		echo "</td>";
		
		echo "<td  class ='sub_label' align='center'>";
		$sql = "SELECT  *
			FROM    tbl_admin
			WHERE   tbl_admin.fabrica = 10
			and ativo is true
			ORDER BY tbl_admin.nome_completo;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 150px;' name='colaborador_$i'>\n";
			echo "<option value=''>- ESCOLHA -</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_admin = trim(pg_result($res,$x,admin));
				$aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

				echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
			}
			echo "</select>\n";
		}
		echo "</td>";
			echo "<td align='left'><input type='text' name='observacao_$i' value='$observacao' size='28' maxlength='20'></td>";
			echo "</tr>";
		}

?>
<style type="text/css">

input { 
background-color: #ededed; 
font: 12px verdana;
color:#363738;
border:1px solid #969696;
}
</style>
<tr>
<td align='center' colspan='5'>
<center>
<input type="submit" name='btn_acao' value="Gravar"> 
</center>
</td>
</tr>
</form>
</table>
<? include "rodape.php"; ?>