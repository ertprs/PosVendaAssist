<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
	
$layout_menu = "gerencia";
$title = "RELATÓRIO - Auditoria de Ordem de Serviço";

include "cabecalho.php";

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
</style>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<?
if (strlen($msg) > 0){
	?>
	<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
	<tr>
		<td align="center" class='error'>
				<? echo $msg ?>
		</td>
	</tr>
</table>
<br>
<?
}
?><br>
<TABLE width="500" align="center" border="0" cellspacing="0" cellpadding="2">
	<TR>
		<TD colspan="4" class="menu_top"><div align="center"><b>Relatório de OS Auditadas</b></div></TD>
	</TR>
	<TR>
	<?
	$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
	echo "<td class='table_line' width='110'>Selecione o mês:</td>";
	echo "<td class='table_line'><select name='mes' size='1' class='frm'>";
		echo "<option value=''></option>";
		for ($i = 1 ; $i <= count($meses) ; $i++) {
			echo "<option value='$i'";
			if ($mes == $i) echo " selected";
			echo ">" . $meses[$i] . "</option>";
		}
		echo "</select>
		</td>";
		echo "<td class='table_line'>Ano:</td> ";
		echo "<td class='table_line'><select name='ano' size='1' class='frm'>";
		echo "<option value=''></option>";
		for ($i = 2003 ; $i <= date("Y") ; $i++) {
		echo "<option value='$i'";
		if ($ano == $i) echo " selected";
		echo ">$i</option>";
		}
		echo "</select>";
		echo "</td>";
?>
	</TR>
	<tr>
		<td colspan=4 style='width: 10px' class='table_line'>Pesquisar:</td>
	</tr>
	<tr>
		<td colspan=2 class='table_line'><input type='checkbox' name='ns' <? if ($_POST['ns']) { echo "CHECKED"; }?>>Auditoria de Número de Série</td>
		<td colspan=2 class='table_line'><input type='checkbox' name='re' <? if ($_POST['re']) { echo "CHECKED"; }?> >Auditoria de 
		reincidência</td>
	</tr>
	<tr>
		<td colspan=2 class='table_line'><input type='checkbox' name='3pc' <? if ($_POST['3pc']) { echo "CHECKED"; }?>>Auditoria de mais de 03 peças </td>
		<td class='table_line' colspan=2><input type='checkbox' name='spc' <? if ($_POST['spc']) { echo "CHECKED"; }?>> auditoria de OS sem peças</td>
	</tr>
	<tr>
		<td class='table_line' colspan=4><input type='checkbox' name='sau' <? if ($_POST['sau']) { echo "CHECKED"; }?>>ordens de serviço que não passaram por nenhuma auditoria.</td>
	</tr>
	<tr>
		<td align='center' class='table_line' colspan='4'><input type='submit' name='btnacao' id='btnacao' value='Pesquisar'></td>
	</tr>
</table>

</FORM>


<!-- =========== AQUI TERMINA O FORMULRIO FRM_PESQUISA =========== -->
<?


if (($_POST['btnacao'])=='Pesquisar' ) {

		if (strlen(trim($_POST["ano"])) > 0) $ano = trim($_POST["ano"]);
		if (strlen(trim($_GET["ano"])) > 0)  $ano = trim($_GET["ano"]);

		if (strlen(trim($_POST["mes"])) > 0) $mes = trim($_POST["mes"]);
		if (strlen(trim($_GET["mes"])) > 0)  $mes = trim($_GET["mes"]);

		//tratamento de datas
		$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));

		if ($_POST['ns']) {

			$status_os = '102,103,104,';

		}

		if ($_POST['re']) {

			$status_os .= '67,68,70,13,14,';

		}

		if ($_POST['3pc']) {

			$status_os .= '118,185,187,';

		}

		if ($_POST['spc']) {

			$status_os .= '115,13,19,';

		}
	
	if ($login_fabrica == 50 ) { //hd 71341 waldir

		$cond_excluidas = "AND tbl_os.excluida is not true "; 
	}

$status_os=substr($status_os,0,strlen($status_os)-1); //tira a ultima virgula


	if (!$_POST['mes']) {

		$msg = "Favor escolher um mês para pesquisa";

	}

	if (!$_POST['ano']) {

		$msg = "Favor escolher um ano para pesquisa";

	}


	if ((!$_POST['ns']) AND (!$_POST['re']) AND (!$_POST['3pc']) AND (!$_POST['spc']) AND (!$_POST['sau'])) {

		$msg = "Favor escolher um critério para pesquisa";

	}
	


if (strlen($msg)>0) {

	echo $msg;
	exit;
}
else {



		if ($_POST['sau']) {
			
			$status_os  = '102,103,104,';
			$status_os .= '67,68,70,13,14,';
			$status_os .= '118,13,19,';
			$status_os .= '115,13,19';


			$sql =  "SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($status_os) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($status_os) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN ($status_os) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tbl_os
				LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os_status.fabrica_status = $login_fabrica
				AND tbl_os_status.os is NULL";
			if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) {
				$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						 ";
			}
				$sql.="$cond_excluidas
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os";

		}else {


			$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN ($status_os) AND tbl_os_status.os = ultima.os  and data > '$data_inicial 00:00' ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN ($status_os) and data > '$data_inicial 00:00' ) ultima
			) interv
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);


			SELECT	tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($status_os) ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($status_os) ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($status_os) ORDER BY data DESC LIMIT 1) AS status_descricao
				FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica";
			if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) {
				$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						 ";
			}
				$sql.="$cond_excluidas
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os";




		}
				

	//echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";

		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Email</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>STATUS</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_numrows($res);$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$posto_email			= pg_result($res, $x, posto_email);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os				= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			if(strlen($sua_os)==o)$sua_os=$os;
			echo "<tr bgcolor='$cor' id='linha_$x'>";

			echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap ><a href='mailto:$posto_email'>$posto_email</a></td>";


			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title='Observação: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
			echo "</tr>";

		}
		echo "<tr bgcolor='$cor'>";
			echo "<td colspan=7 align='right' style='font-size: 14px; font-family: verdana' nowrap>";
				echo "<b>Total:</b> $x";
			echo "</td>";
		echo "</tr>";
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
		echo "</td></tr></table>";
	}
	else {

		echo "NÃO FORAM ENCONTRADAS AUDITORIAS";
	}
	}
}

?>
		<? include "rodape.php"; ?>
 
