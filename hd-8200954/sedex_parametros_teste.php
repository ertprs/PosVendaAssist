<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_REQUEST["acao"]) > 0) $btn_acao = strtoupper($_REQUEST["acao"]);

$msg_erro = "";

$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de OS de Sedex Lançadas";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

include "cabecalho.php";

if (strlen($btn_acao) > 0 ) {

	$selecionado = 0;
	
	// recebe as variaveis
	if($_REQUEST['chk_opt1'])  $chk1  = $_REQUEST['chk_opt1']; 
	if($_REQUEST['chk_opt2'])  $chk2  = $_REQUEST['chk_opt2']; 
	if($_REQUEST['chk_opt3'])  $chk3  = $_REQUEST['chk_opt3']; 
	if($_REQUEST['chk_opt4'])  $chk4  = $_REQUEST['chk_opt4'];  
	if($_REQUEST['chk_opt5'])  $chk5  = $_REQUEST['chk_opt5']; 
	if($_REQUEST['chk_opt6'])  $chk6  = $_REQUEST['chk_opt6']; 
	if($_REQUEST['chk_opt7'])  $chk7  = $_REQUEST['chk_opt7']; 
	if($_REQUEST['chk_opt8'])  $chk8  = $_REQUEST['chk_opt8']; 
	
	if($_REQUEST["data_inicial"])		$data_inicial      = trim($_REQUEST["data_inicial"]);
	if($_REQUEST["data_final"])			$data_final        = trim($_REQUEST["data_final"]);
	if($_REQUEST["posto_origem_codigo"])	$posto_origem_codigo  = trim($_REQUEST["posto_origem_codigo"]);
	if($_REQUEST["posto_origem_nome"])		$posto_origem_nome    = trim($_REQUEST["posto_origem_nome"]);
	if($_REQUEST["posto_destino_codigo"])	$posto_destino_codigo = trim($_REQUEST["posto_destino_codigo"]);
	if($_REQUEST["posto_destino_nome"])		$posto_destino_nome   = trim($_REQUEST["posto_destino_nome"]);
	if($_REQUEST["numero_os"])				$numero_os            = trim($_REQUEST["numero_os"]);

	// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
	$sql = "SELECT  tbl_os_sedex.os_sedex                                      ,
					tbl_os_sedex.sua_os_origem                                 ,
					tbl_os_sedex.sua_os_destino                                ,
					to_char (tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data ,
					tbl_os_sedex.finalizada                                    ,
					tbl_os_sedex.extrato                                       ,
					posto_origem.codigo_posto  AS posto_origem                 ,
					posto_destino.codigo_posto AS posto_destino                ,
					dados_posto_origem.nome    AS nome_origem                  ,
					dados_posto_destino.nome   AS nome_destino                 ,
					tbl_admin.login                                            
			FROM 	tbl_os_sedex
			JOIN	tbl_admin USING (admin)
			JOIN	tbl_posto_fabrica AS posto_origem  ON tbl_os_sedex.posto_origem  = posto_origem.posto  AND posto_origem.fabrica  = $login_fabrica
			JOIN	tbl_posto_fabrica AS posto_destino ON tbl_os_sedex.posto_destino = posto_destino.posto AND posto_destino.fabrica = $login_fabrica
			JOIN	tbl_posto AS dados_posto_origem    ON tbl_os_sedex.posto_origem  = dados_posto_origem.posto
			JOIN	tbl_posto AS dados_posto_destino   ON tbl_os_sedex.posto_destino = dados_posto_destino.posto
			WHERE		tbl_os_sedex.fabrica = $login_fabrica 
			AND         (tbl_os_sedex.posto_origem = $login_posto OR tbl_os_sedex.posto_destino = $login_posto)
			AND         (1=2 ";

	if(strlen($chk1) > 0){
		
		$selecionado = 1;
		
		//dia atual
		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
		$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

		$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
		$resX = pg_exec ($con,$sqlX);
		#  $dia_hoje_final = pg_result ($resX,0,0);

		$monta_sql .= " OR (tbl_os_sedex.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
		$dt = 1;

		$msg .= " e OS Sedex lançadas hoje";
	}

	if(strlen($chk2) > 0) {
		
		$selecionado = 1;
		
		// dia anterior
		$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

		$monta_sql .=" OR (tbl_os_sedex.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
		$dt = 1;

		$msg .= " e OS Sedex lançadas ontem";

	}

	if(strlen($chk3) > 0){
		
		$selecionado = 1;
		
		// última semana
		$sqlX = "SELECT to_char (current_date , 'D')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

		$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

		$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

		$monta_sql .=" OR (tbl_os_sedex.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
		$dt = 1;

		$msg .= " e OS Sedex lançadas nesta semana";

	}

	if(strlen($chk4) > 0){
		
		$selecionado = 1;
		
		// do mês
		$mes_inicial = trim(date("Y")."-".date("m")."-01");
		$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

		$monta_sql .= "OR (tbl_os_sedex.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
		$dt = 1;

		$msg .= " e OS Sedex lançadas neste mês ";
	}

	if(strlen($chk5) > 0){
		
		$selecionado = 1;
		
		// entre datas
		if((strlen($data_inicial) == 10) && (strlen($data_final) == 10)){
    
    		if(empty($data_inicial) OR empty($data_final)){
        		$msg_erro = "Data Inválida";
    		}

    		if(strlen($msg_erro)==0){
        		list($di, $mi, $yi) = explode("/", $data_inicial);
        		if(!checkdate($mi,$di,$yi)) 
        	    	$msg_erro = "Data Inválida";
    		}
    	
    		if(strlen($msg_erro)==0){
        		list($df, $mf, $yf) = explode("/", $data_final);
        		if(!checkdate($mf,$df,$yf)) 
            		$msg_erro = "Data Inválida";
    		}
    		

    		

    		if(strlen($msg_erro)==0){
        		$aux_data_inicial = "$yi-$mi-$di";
        		$aux_data_final = "$yf-$mf-$df";
    		}
    
       		if(strlen($msg_erro)==0){
        		if(($aux_data_final < $aux_data_inicial) or ($aux_data_final > date('Y-m-d'))){
            		$msg_erro = "Data Inválida.";
        		}
    		}

			$monta_sql .= "OR (tbl_os_sedex.data BETWEEN '$aux_data_inicial 00:00:00'  AND '$aux_data_final 23:59:59') ";
			$dt = 1;

			$msg .= " e OS Sedex lançadas entre os dias $data_inicial e $data_final ";
		}else{
			$msg_erro = "Data Inválida";
		}
	}

	if(strlen($chk6) > 0){
		
		$selecionado = 1;
		
		// referencia do produto
		if (strlen($posto_origem_codigo) > 0 && is_numeric($posto_origem_codigo)){
			$sqlZ = "SELECT	tbl_posto.posto,
							tbl_posto.nome 
					FROM	tbl_posto
					JOIN	tbl_posto_fabrica USING(posto)
					WHERE	tbl_posto_fabrica.codigo_posto = '$posto_origem_codigo'";
			$resZ = pg_exec ($con,$sqlZ);

			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_os_sedex.posto_origem = '".pg_result($resZ,0,0)."' ";
			$dt = 1;

			$msg .= " e Posto Origem ".pg_result($resZ,0,1);

		}
	}

	if(strlen($chk7) > 0){
		
		$selecionado = 1;
		
		// referencia do produto
		if (strlen($posto_destino_codigo) > 0 && is_numeric($posto_origem_codigo)){
			$sqlZ = "SELECT	tbl_posto.posto,
							tbl_posto.nome 
					FROM	tbl_posto
					JOIN	tbl_posto_fabrica USING(posto)
					WHERE	tbl_posto_fabrica.codigo_posto = '$posto_destino_codigo'";
			$resZ = pg_exec ($con,$sqlZ);

			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_os_sedex.posto_destino = '".pg_result($resZ,0,posto)."' ";
			$dt = 1;

			$msg .= " e Posto Destino ".pg_result($resZ,0,nome);

		}
	}

	if(strlen($chk8) > 0){
		
		$selecionado = 1;
		
		// numero_os
		if ($numero_os){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			if (strpos($numero_os,"-") === false)
				$monta_sql .= "$xsql tbl_os_sedex.sua_os_destino ILIKE '%".$numero_os."%' ";
			else
				$monta_sql .= "$xsql tbl_os_sedex.sua_os_destino ILIKE '%".$numero_os."%' ";

			$xnumero_os = substr($numero_os,strlen($numero_os) - 5,strlen($numero_os));
			$monta_sql .= "$xsql tbl_os_sedex.os_sedex = '".intval($xnumero_os)."' ";

			$dt = 1;

			$msg .= " e OS Sedex lançadas com Nº $numero_os";

		}
	}

	// ordena sql padrao
	$sql .= $monta_sql;
	$sql .= ") ORDER BY lpad (tbl_os_sedex.sua_os_destino,10,'0') DESC ";

	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	//if ($ip == '201.0.9.216') echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br><BR>";
}
?>
<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color: #596D9B;
	color: white;
	font: normal normal bold 11px/normal Arial;
	text-align: center;
}
input[type=button]{
	cursor:pointer;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.littleFont{
    font:bold 11px Arial;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.bg_form{
	background-color:#d9e2ef;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
/* ELEMENTOS DE POSICIONAMENTO */
#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<? include "javascript_pesquisas.php" ?>

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<?php include "javascript_calendario.php";?>
<script>
	$().ready(function(){
		$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_inicial" ).maskedinput("99/99/9999");
		$( "#data_final" ).datePicker({startDate : "01/01/2000"});
		$( "#data_final" ).maskedinput("99/99/9999");
	});
</script>

<script language="JavaScript">
function fnc_pesquisa_posto (campo1, campo2, tipo, posto) {

	var url = "";

	if (tipo == "codigo" ) {
		var xcampo = campo1;
		if(campo1 == ""){
			alert('Informe toda ou parte da informação para realizar a pesquisa');
		}
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
		if(campo2 == ""){
			alert('Informe toda ou parte da informação para realizar a pesquisa');
		}

	}

	if ((campo1 == "" || campo2 == "") && xcampo != "") {
		var url = "";
		url = "pesquisa_posto_sedex.php?campo=" + xcampo + "&tipo=" + tipo + "&posto=" + posto;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.codigo  = campo1;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>

<br>
<?php if(strlen(trim($msg_erro)) > 0):?>
	<table align="center" width="700" cellspacing="1" class="formulario">
		<tr>
			<td class='msg_erro'><?php echo $msg_erro;?></td>
		</tr>
	</table>
<?php endif;?>

<form name="frmdespesa" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">

	<input type="hidden" name="acao">
	
	<table align="center" width="700" cellspacing="1" class="formulario" border="0">

		<tr class='titulo_tabela'>
			<td colspan="6" align="center"><b>Parâmetros de Pesquisa.</b></td>
		</tr>
		
		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>
		
		<tr>
			<td style="width:150px">&nbsp;</td>
			
			<td colspan='4'>
				<label>
					<input type="checkbox" name="chk_opt1" value="1" <?php echo ($chk1) ? 'checked="checked"' : null;?>>
					&nbsp; OS Lançadas Hoje
				</label>
			</td>
			
			<td style="width:150px">&nbsp;</td>
		</tr>

		<tr>
			<td style="width:150px">&nbsp;</td>
			
			<td colspan='4'>
				<label>
					<input type="checkbox" name="chk_opt2" value="1" <?php echo ($chk2) ? 'checked="checked"' : null;?>>
					&nbsp; OS Lançadas Ontem
				</label>
			</td>
			
			<td style="width:150px">&nbsp;</td>
		</tr>

		<tr>
			<td style="width:150px">&nbsp;</td>
			
			<td colspan='4'>
				<label>
					<input type="checkbox" name="chk_opt3" value="1" <?php echo ($chk3) ? 'checked="checked"' : null;?>>
					&nbsp; OS Lançadas Nesta Semana
				</label>
			</td>
			
			<td style="width:150px">&nbsp;</td>
		</tr>

		<tr>
			<td style="width:150px">&nbsp;</td>
			
			<td colspan='4'>
				<label>
					<input type="checkbox" name="chk_opt4" value="1" <?php echo ($chk4) ? 'checked="checked"' : null;?>>
					&nbsp; OS Lançadas Neste Mês
				</label>
			</td>
			
			<td style="width:150px">&nbsp;</td>
		</tr>


		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>

		<tr>
			<td style="width:150px" rowspan="2">&nbsp;</td>
			<td rowspan="2" style="width:100px">
				<label>
					<input type="checkbox" name="chk_opt5" value="1" <?php echo ($chk5) ? 'checked="checked"' : null;?>>
					&nbsp;Entre datas
				</label>
			</td>
			<td align='left' style="width:115px">Data Inicial</td>
			<td align='left' colspan='2'>Data Final</td>
			
			<td style="width:150px" rowspan="2">&nbsp;</td>
		</tr>

		<tr class='bg_form'>
			<td align='left' nowrap="nowrap">
				<label><input size="12" maxlength="10" type="text" name="data_inicial" id="data_inicial" value="<?php echo $data_inicial ;?>">&nbsp;</label>
			</td>
			<td align='left' colspan='2'>
				<label><input size="12" maxlength="10" type="text" name="data_final" id="data_final" value="<?php echo $data_final ;?>">&nbsp;</label>
			</td>
		</tr>

		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>

		<tr class='bg_form'>
			<td style="width:150px" rowspan="2">&nbsp;</td>
			
			<td rowspan="2">
				<label>
					<input type="checkbox" name="chk_opt6" value="1" <?php echo ($chk6) ? 'checked="checked"' : null;?>>
					&nbsp;Posto Origem
				</label>
			</td>
			<td>Código</td>
			<td colspan="2">Nome</td>
			
			<td style="width:150px" rowspan="2">&nbsp;</td>
		</tr>

		<tr class='bg_form'>
			<td align="left">
				<input type="text" name="posto_origem_codigo" SIZE="8" value="<?php echo $posto_origem_codigo;?>">&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem_codigo.value, document.frmdespesa.posto_origem_nome.value, 'codigo', 'origem')" />
			</td>
			<td style="text-align: left;" colspan="2">
				<input type="text" name="posto_origem_nome" size="18" value="<?php echo $posto_origem_nome;?>">&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem_codigo.value, document.frmdespesa.posto_origem_nome.value, 'nome', 'origem')" />
			</td>
		</tr>

		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>
	
		<tr class='bg_form'>
			<td style="width:150px" rowspan="2">&nbsp;</td>
		
			<td rowspan="2">
				<label>
					<input type="checkbox" name="chk_opt7" value="1" <?php echo ($chk7) ? 'checked="checked"' : null;?>>
					&nbsp;Posto Destino
				</label>
			</td>
			<td>Código</td>
			<td colspan="2">Nome</td>
			
			<td style="width:150px" rowspan="2">&nbsp;</td>
		</tr>
		
		<tr class='bg_form'>
			<td align="left">
				<input type="text" name="posto_destino_codigo" SIZE="8" value="<?php $posto_destino_codigo;?>">&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frmdespesa.posto_destino_codigo.value, document.frmdespesa.posto_destino_nome.value, 'codigo', 'destino')" />
			</td>
			<td style="text-align: left;" colspan="2">
				<input type="text" name="posto_destino_nome" size="18" value="<?php $posto_destino_nome;?>">&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frmdespesa.posto_destino_codigo.value, document.frmdespesa.posto_destino_nome.value, 'nome', 'destino')" />
			</td>
		</tr>
		
		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>
		
		<tr class='bg_form'>
			<td style="width:150px">&nbsp;</td>
			
			<td>
				<label>
					<input type="checkbox" name="chk_opt8" value="1" <?php echo ($chk8) ? 'checked="checked"' : null;?>>
					Número da OS
				</label>
			</td>
			
			<td style="text-align:left;" colspan='3'><input type="text" name="numero_os" size="17" value="<?php $numero_os;?>"></td>
			
			<td style="width:150px">&nbsp;</td>			
		</tr>
		
		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>
		
		<tr class='bg_form'>
			<td colspan="6" align="center">
				<input type="button" value="Pesquisar" onclick="document.frmdespesa.acao.value='PESQUISAR'; document.frmdespesa.submit();" />
			</td>
		</tr>
		
		<tr>
			<td colspan="6">&nbsp;</td>
		</tr>
	</table>
</form>

<?php
if (strlen($btn_acao) > 0  && strlen($msg_erro)==0) {

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	
	// ##### PAGINACAO ##### //
	if (@pg_numrows($res) > 0) {
		
		echo '<table align="center" width="700" cellspacing="1" class="tabela">'."\n";

		echo "<TR class='titulo_coluna'>\n";
		echo "<TD>Abertura</TD>\n";
		echo "<TD>Posto Origem</TD>\n";
	#	echo "<TD>OS</TD>\n";
		echo "<TD>Posto Destino</TD>\n";
		echo "<TD>OS Origem</TD>\n";
		echo "<TD>OS Destino</TD>\n";
		echo "<TD>Solicitante</TD>\n";
		echo "<TD>Situação</TD>\n";
		echo "<TD>&nbsp;</TD>\n";
		echo "<TD>&nbsp;</TD>\n";
		echo "</TR>\n";

		for ($i = 0 ; $i < pg_numrows ($res); $i++){

			$os_sedex             = trim(pg_result($res,$i,os_sedex));
			$sua_os_origem        = trim(pg_result($res,$i,sua_os_origem));
			$sua_os_destino       = trim(pg_result($res,$i,sua_os_destino));
			$data                 = trim(pg_result($res,$i,data));
			$posto_origem_codigo  = trim(pg_result($res,$i,posto_origem));
			$posto_destino_codigo = trim(pg_result($res,$i,posto_destino));
			$posto_origem_nome    = trim(pg_result($res,$i,nome_origem));
			$posto_destino_nome   = trim(pg_result($res,$i,nome_destino));
			$finalizada           = trim(pg_result($res,$i,finalizada));
			$extrato              = trim(pg_result($res,$i,extrato));
			$solicitante          = trim(pg_result($res,$i,login));

			$xos_sedex = "00000".$os_sedex;
			$xos_sedex = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
			$xos_sedex = $posto_origem_codigo.$xos_sedex;

			if(strlen($extrato) > 0){
				$status = "Extrato";
			}elseif(strlen($finalizada) > 0){
				$status = "Finalizada";
			}else{
				$status = "Nova";
			}

			$btn = 'amarelo';
			if ($i % 2 == 0) {
				$btn = 'azul';
			}

			if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

			if($sua_os_destino == 'CR'){
				$sql2   = "SELECT sua_os FROM tbl_os WHERE os = '$sua_os_origem' AND tbl_os.fabrica = '$login_fabrica'; ";
				$res2   = pg_exec($con, $sql2);
				$num2 = pg_num_rows($res2);
				if($num2){
					$cr_sua_os = pg_result($res2,0,'sua_os');
				}
				$cr_sua_os = $posto_origem_codigo.$cr_sua_os;
				
			}

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<TR bgcolor='$cor' height='25'>\n";
			echo "<TD nowrap align='center'>$data</TD>\n";
			echo "<TD nowrap align='center'><ACRONYM TITLE=\"$posto_origem_nome\">$posto_origem_codigo</ACRONYM></TD>\n";
	#		echo "<TD nowrap align='center'>$sua_os_origem</TD>\n";
			echo "<TD nowrap align='center'><ACRONYM TITLE=\"$posto_destino_nome\">$posto_destino_codigo</ACRONYM></TD>\n";
			if($sua_os_destino == 'CR'){
				echo "<TD nowrap align='center'>$cr_sua_os</TD>\n";
			}else{
				echo "<TD nowrap align='center'>$xos_sedex</TD>\n";
			}
			echo "<TD nowrap align='center'>$sua_os_destino</TD>\n";
			echo "<TD nowrap align='center'>$solicitante</TD>\n";
			echo "<TD nowrap align='center'>$status</TD>\n";
			if($login_fabrica == 1 AND $sua_os_destino == 'CR'){
				$sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE fabrica = $login_fabrica and os_sedex = $os_sedex ";
				$res_sedex = pg_exec($con,$sql_sedex);
				$sua_os_sedex = pg_result($res_sedex,0,0);
				echo "<TD width='57'><a href='carta_registrada.php?os=$sua_os_sedex'><input type='button' value='Consultar' /></a></TD>\n";
			}else{
				echo "<TD width='57'><a href='sedex_cadastro_complemento.php?os_sedex=$os_sedex'><input type='button' value='Consultar' style='font:12px Arial' /></a></TD>\n";
			}
			echo "<TD width='57'><input type='button' value='Imprimir' style='font:12px Arial' onclick='window.open(\"sedex_print.php?os_sedex=$os_sedex\");'/></TD>\n";
			echo "</TR>\n";

		}
		echo "</TABLE>\n";

		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		echo "<div>";

		if($pagina < $max_links) { 
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}

	}else{
		echo '<div align="center">Não foram Encontrados Resultados para esta Pesquisa</div>';
	}

	// ##### PAGINACAO ##### //
}

include "rodape.php" ?>
