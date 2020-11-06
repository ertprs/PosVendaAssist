<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 ) {

	if (( strlen ($mes) == 0 OR strlen ($ano) == 0) AND (strlen($admin)==0 ) )  {
		$msg .= "Escolha a data ou o inspetor para fazer a pesquisa<br>";
	}
	if (strlen($formulario)==0 ){
				$msg .= "Escolha o formulário que deseja pesquisar";
	}
	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

}

$layout_menu = "tecnica";
$title = "Consulta de formulários Suggar";
include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
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
.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}
</style>
<script>
function abreRelatorio(data_inicial,data_final,inspetor,formulario,pergunta,nota){
	janela = window.open("formulario_consulta_suggar_relatorios.php?data_inicial=" + data_inicial + "&data_final=" + data_final + "&inspetor=" + inspetor + "&formulario=" + formulario +"&pergunta=" + pergunta +"&nota=" + nota ,"formularios",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}
</script>
<br>

<?

if(strlen($msg)>0){
	echo "<table class='Erro' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
}

?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<table class='Tabela' width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Selecione a data ou o inspetor para a pesquisa.</td>
	</tr>
</table>
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td>* Mês</td>
		<td>* Ano</td>
	</tr>
	<tr bgcolor="#D9E2EF" align='center'>
		<td  class="Conteudo">
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
		<td>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2007 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
	</tr>

</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr  bgcolor="#D9E2EF" align='center'>
		<td colspan='2'  class="Conteudo">Nome do Inspetor</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align="center">
		<td colspan='2'>
<?
			$sql="SELECT * 
					FROM tbl_admin 
					WHERE fabrica=24
					AND length(trim(nome_completo)) > 0
					AND ativo IS TRUE
					AND admin !='533'
					ORDER by nome_completo";
			$res=pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='admin'>&nbsp;\n";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_admin          = trim(pg_result($res,$x,admin));
					$aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

					echo "<option value='$aux_admin'"; 
					if ($admin == $aux_admin){
						echo " SELECTED ";
					}
					echo ">$aux_nome_completo</option>\n";
				}
				echo "</select>\n";
			}
?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='2'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center'>RG-GAT-001
			<input type='radio' name='formulario' value='1' <? $formulario=='1' ; echo "checked"; ?> >
		</td>
		<td align='center'>RG-GAT-002
			<input type='radio' name='formulario' value='2' <? $formulario=='2' ?> >
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>
</form>
<BR>
<?
if (strlen($_POST['btn_acao']) > 0 AND strlen($msg) == 0) {

	if($formulario=='1'){ ?>

		<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='680'>
			<tr bgcolor='#596D9B'>
				<? $sql="SELECT count(*) as total
							FROM tbl_visita_posto 
							WHERE visita_posto is not null";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}

						$res=pg_exec($con,$sql);	
						if(pg_numrows($res) ==0 ){
							$total= 0;
						} else {
							$total= trim(pg_result($res,0,total));
						} ?>
				<td colspan='10'><font color='white'>Total dos postos visitados: <? echo $total;?></font></td>
			</tr>
			<tr bgcolor='#d2d7e1'>
				<td align='center' colspan='4' nowrap>Notas</td>
				<td align='center' nowrap><font size='2'>Excelente<BR><center>5</center></td>
				<td align='center' nowrap><font size='2'>Muito Bom<BR><center>4</center></td>
				<td align='center' nowrap><font size='2'>Bom<BR><center>3</center></td>
				<td align='center' nowrap><font size='2'>Regular<BR><center>2</center></td>
				<td align='center' nowrap><font size='2'>Fraco<BR><center>1</center></td>
				<td align='center' nowrap><font size='2'>Não<BR><center>Aplicável</center></td>
			</tr>
			<tr class="table_line">
				<td align='left' colspan='4' nowrap>1 - Obtenção de Informação</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob5
								FROM tbl_visita_posto
								WHERE obtencao_informacao='5' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}

						$res=pg_exec($con,$sql);
						if(pg_numrows($res) == 0 ){
							$ob5= 0;
						} else {
							if(pg_numrows($res) > 0) {
							$ob5= trim(pg_result($res,0,ob5));
							}
						}
						echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','obtencao_informacao','5')\">$ob5</a> "; 
						?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob4
								FROM tbl_visita_posto
								WHERE obtencao_informacao='4' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}							
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob4= 0;
						} else {
							$ob4= trim(pg_result($res,0,ob4));
						}
						;?>
						<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','obtencao_informacao','4')\">$ob4</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob3
								FROM tbl_visita_posto
								WHERE obtencao_informacao='3' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob3= 0;
						} else {
							$ob3= trim(pg_result($res,0,ob3));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','obtencao_informacao','3')\">$ob3</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob2
								FROM tbl_visita_posto
								WHERE obtencao_informacao='2' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
					
						if(pg_numrows($res) ==0 ){
							$ob2= 0;
						} else {
							$ob2= trim(pg_result($res,0,ob2));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','obtencao_informacao','2')\">$ob2</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob1
								FROM tbl_visita_posto
								WHERE obtencao_informacao='1' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob1= 0;
						} else {
							$ob1= trim(pg_result($res,0,ob1));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','obtencao_informacao','1')\">$ob1</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob0
								FROM tbl_visita_posto
								WHERE obtencao_informacao='0' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob0= 0;
						} else {
							$ob0= trim(pg_result($res,0,ob0));
						}
						;?>
			<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','obtencao_informacao','0')\">$ob0</a> "; ?>
					</td>
			</tr>
			<tr class="table_line">
				<td align='left' colspan='4'>2 - Atendimento de Reclamações</td>
					<td align='center'>
					<? $sql="SELECT count(*) as at5
								FROM tbl_visita_posto
								WHERE atendimento_reclamacao='5' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}

						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$at5= 0;
						} else {
							$at5= trim(pg_result($res,0,at5));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','atendimento_reclamacao','5')\">$at5</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as at4
								FROM tbl_visita_posto
								WHERE atendimento_reclamacao='4' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}							
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$at4= 0;
						} else {
							$at4= trim(pg_result($res,0,at4));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','atendimento_reclamacao','4')\">$at4</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as at3
								FROM tbl_visita_posto
								WHERE atendimento_reclamacao='3' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$at3= 0;
						} else {
							$at3= trim(pg_result($res,0,at3));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','atendimento_reclamacao','3')\">$at3</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as at2
								FROM tbl_visita_posto
								WHERE atendimento_reclamacao='2' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
					
						if(pg_numrows($res) ==0 ){
							$at2= 0;
						} else {
							$at2= trim(pg_result($res,0,at2));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','atendimento_reclamacao','2')\">$at2</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as at1
								FROM tbl_visita_posto
								WHERE atendimento_reclamacao='1' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$at1= 0;
						} else {
							$at1= trim(pg_result($res,0,at1));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','atendimento_reclamacao','1')\">$at1</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as at0
								FROM tbl_visita_posto
								WHERE atendimento_reclamacao='0' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$at0= 0;
						} else {
							$at0= trim(pg_result($res,0,at0));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','atendimento_reclamacao','0')\">$at0</a> "; ?>
					</td>
			</tr>
			<tr class="table_line">
				<td align='left' colspan='4' nowrap>3 - Facilidade ao Estabelecer Contato Telefônico</td>
					<td align='center'>
					<? $sql="SELECT count(*) as fa5
								FROM tbl_visita_posto
								WHERE facilidade_contato_fone='5' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}

						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$fa5= 0;
						} else {
							$fa5= trim(pg_result($res,0,fa5));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','facilidade_contato_fone','5')\">$fa5</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as fa4
								FROM tbl_visita_posto
								WHERE facilidade_contato_fone='4' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}							
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$fa4= 0;
						} else {
							$fa4= trim(pg_result($res,0,fa4));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','facilidade_contato_fone','4')\">$fa4</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as fa3
								FROM tbl_visita_posto
								WHERE facilidade_contato_fone='3' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$fa3= 0;
						} else {
							$fa3= trim(pg_result($res,0,fa3));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','facilidade_contato_fone','3')\">$fa3</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as fa2
								FROM tbl_visita_posto
								WHERE facilidade_contato_fone='2' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
					
						if(pg_numrows($res) ==0 ){
							$fa2= 0;
						} else {
							$fa2= trim(pg_result($res,0,fa2));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','facilidade_contato_fone','2')\">$fa2</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as fa1
								FROM tbl_visita_posto
								WHERE facilidade_contato_fone='1' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$fa1= 0;
						} else {
							$fa1= trim(pg_result($res,0,fa1));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','facilidade_contato_fone','1')\">$fa1</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as fa0
								FROM tbl_visita_posto
								WHERE facilidade_contato_fone='0' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$fa0= 0;
						} else {
							$fa0= trim(pg_result($res,0,fa0));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','facilidade_contato_fone','0')\">$fa0</a> "; ?>
					</td>
			</tr>
			<tr class="table_line">
				<td align='left' colspan='4'>4 - Pontualidade na Entrega de Componentes</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob5
								FROM tbl_visita_posto
								WHERE pontualidade_entrega='5' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}

						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob5= 0;
						} else {
							$ob5= trim(pg_result($res,0,ob5));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','pontualidade_entrega','5')\">$ob5</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob4
								FROM tbl_visita_posto
								WHERE pontualidade_entrega='4' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}							
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob4= 0;
						} else {
							$ob4= trim(pg_result($res,0,ob4));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','pontualidade_entrega','4')\">$ob4</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob3
								FROM tbl_visita_posto
								WHERE pontualidade_entrega='3' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob3= 0;
						} else {
							$ob3= trim(pg_result($res,0,ob3));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','pontualidade_entrega','3')\">$ob3</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob2
								FROM tbl_visita_posto
								WHERE pontualidade_entrega='2' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
					
						if(pg_numrows($res) ==0 ){
							$ob2= 0;
						} else {
							$ob2= trim(pg_result($res,0,ob2));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','pontualidade_entrega','2')\">$ob2</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob1
								FROM tbl_visita_posto
								WHERE pontualidade_entrega='1' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob1= 0;
						} else {
							$ob1= trim(pg_result($res,0,ob1));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','pontualidade_entrega','1')\">$ob1</a> "; ?>
					</td>
					<td align='center'>
					<? $sql="SELECT count(*) as ob0
								FROM tbl_visita_posto
								WHERE pontualidade_entrega='0' ";
						if(strlen($mes) > 0 and strlen($admin) > 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'
						AND tbl_visita_posto.admin=$admin";
						}else{
							if (strlen($mes) > 0 and strlen($admin) == 0) {
							$sql .= " AND tbl_visita_posto.data BETWEEN '$data_inicial' AND '$data_final'";
							}
						}
						if (strlen($admin) > 0 and strlen($mes) == 0) {
							$sql .= " AND tbl_visita_posto.admin=$admin";
						}
						$res=pg_exec($con,$sql);
						if(pg_numrows($res) ==0 ){
							$ob0= 0;
						} else {
							$ob0= trim(pg_result($res,0,ob0));
						}
						;?>
					<?echo "<a href=\"javascript: abreRelatorio('$data_inicial','$data_final','$admin','$formulario','pontualidade_entrega','0')\">$ob0</a> "; ?>
					</td>
			</tr>
</table>
<BR><BR>
<?

		$admin    = trim($_GET['admin']);

		if(strlen($admin) == 0) $admin    = trim($_POST['admin']);

		$sql="SELECT v.posto         ,
					 v.visita_posto  ,
				 	 v.admin         ,
					 to_char(v.data,'DD/MM/YYYY') as data    ,
				 	 p.nome                 ,
					 a.nome_completo        
				FROM tbl_visita_posto as v, tbl_posto as p, tbl_admin as a";
		if(strlen($mes) > 0 and strlen($admin) > 0) {
			$sql .= " WHERE v.data BETWEEN '$data_inicial' AND '$data_final'
						AND v.admin=$admin";
		}else{
			if (strlen($mes) > 0 and strlen($admin) == 0) {
				$sql .= " WHERE v.data BETWEEN '$data_inicial' AND '$data_final'";
			}
		}
		if (strlen($admin) > 0 and strlen($mes) == 0) {
			$sql .= " WHERE v.admin=$admin";
		}
		$sql.=" AND v.posto=p.posto 
				AND v.admin=a.admin
				ORDER BY v.data desc";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				if ($i % 50 == 0) {
					echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='680'>";
				}
				if ($i % 50 == 0) {
					echo "<tr class='Titulo' height='15'>";
					echo "<td>Nome de Inspetor</td>";
					echo "<td>Data</td>";
					echo "<td>Nome do Posto</td>";
					echo "<td>Consultar</td>";
					echo "</tr>";
				}
					$nome_posto               = trim(pg_result($res,$i,nome));
					$nome_admin               = trim(pg_result($res,$i,nome_completo));
					$data                     = trim(pg_result($res,$i,data));
					$visita_posto             = trim(pg_result($res,$i,visita_posto));

					echo "<tr class='Conteudo' height='15'>";
					echo "<td>$nome_admin</td>";
					echo "<td>$data</td>";
					echo "<td>$nome_posto</td>";
					echo "<td width='60' align='center'>";
					echo "<a href=rg-gat-001_suggar_antigo.php?visita_posto=$visita_posto target='blank'><img src='imagens/btn_consulta.gif'></a>";
					echo "</td>\n";

					echo "</tr>";
			}
					echo "</table>";
		}
	}

	if($formulario=='2'){

		$sql="SELECT tbl_inspecao_tecnica.admin           ,
					 to_char(tbl_inspecao_tecnica.data,'DD/MM/YYYY') as data            ,
					 tbl_inspecao_tecnica.inspecao_tecnica,
					 tbl_inspecao_tecnica.qtde_visita     ,
					 tbl_admin.nome_completo              
				FROM tbl_inspecao_tecnica
				JOIN tbl_admin ON tbl_admin.admin=tbl_inspecao_tecnica.admin";

		if(strlen($mes) > 0 and strlen($admin) > 0) {
			$sql .= " WHERE tbl_inspecao_tecnica.data BETWEEN '$data_inicial' AND '$data_final' 
						AND tbl_inspecao_tecnica.admin=$admin 
						ORDER BY data desc";
		} else {
			if(strlen($mes) > 0 and strlen($admin) == 0) {
				$sql .= " WHERE tbl_inspecao_tecnica.data BETWEEN '$data_inicial' AND '$data_final' ORDER BY data desc";
			}
		}
		if (strlen($admin) > 0 and strlen($mes) == 0) {
		$sql .= " WHERE tbl_inspecao_tecnica.admin=$admin";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
	
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				if ($i % 50 == 0) {
					echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='50%'>";
				}
				if ($i % 50 == 0) {
					echo "<tr class='Titulo' height='15'>";
					echo "<td>Nome de Inspetor</td>";
					echo "<td>Data</td>";
					echo "<td>Consultar</td>";
					echo "</tr>";
				}
				$nome_admin               = trim(pg_result($res,$i,nome_completo));
				$qtde_visita              = trim(pg_result($res,$i,qtde_visita));
				$data                     = trim(pg_result($res,$i,data));
				$inspecao_tecnica         = trim(pg_result($res,$i,inspecao_tecnica));

				echo "<tr class='Conteudo' height='15'>";
				echo "<td>$nome_admin</td>";
				echo "<td>$data</td>";
				echo "<td><a href=rg-gat-002_suggar.php?inspecao_tecnica=$inspecao_tecnica target=blank><img src='imagens/btn_consulta.gif'></a></td>";
				echo "</tr>";
			}
		echo "</table>";
		}
	}
}
echo "<BR><BR>";

include "rodape.php" ?>
