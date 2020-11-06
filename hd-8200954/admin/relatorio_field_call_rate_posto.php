<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

// Criterio padrão
$_POST["criterio"] = "data_digitacao";
//////////////////

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	if(strlen($_POST["familia"]) > 0) $familia = trim($_POST["familia"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}
	
	if (strlen($erro) == 0) {
		$posto_codigo = trim($_POST["posto_codigo"]);
		$posto_nome   = trim($_POST["posto_nome"]);
		if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto_fabrica.posto        ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
			if (strlen($posto_codigo) > 0)
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
			if (strlen($posto_nome) > 0)
				$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res,0,posto);
				$posto_codigo = pg_result($res,0,codigo_posto);
				$posto_nome   = pg_result($res,0,nome);
				
				$mostraMsgPosto = "<br>no POSTO $posto_codigo - $posto_nome";
			}else{
				$erro .= " Posto não encontrado<br>";
			}
		}
	}

	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$familia      = trim($_POST["familia"]);
		$estado       = trim($_POST["estado"]);
		$posto_codigo = trim($_POST["posto_codigo"]);
		$posto_nome   = trim($_POST["posto_nome"]);
		$criterio     = trim($_POST["criterio"]);
		
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : FAMÍLIA DE PRODUTO";

include "cabecalho.php";

?>

<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

function AbrePeca(produto,data_inicial,data_final,linha,estado,posto){
	janela = window.open("relatorio_field_call_rate_pecas2.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&posto=" + posto,"produto",'resizable=1,scrollbars=yes,width=750,height=350,top=0,left=0');
	janela.focus();
}

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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

-->
</style>

<? include "javascript_pesquisas.php"; ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
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
?>

<br>


<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2" class='PesquisaTabela'>

<caption>Pesquisa</caption>

<TBODY>
<TR>
	<TD colspan='2'><p>As datas se referem à Geração do Extrato e somente OS aprovadas são consideradas</p></TD>
</TR>

<TR>
	<TD>Data Inicial<br><INPUT size="12" maxlength="10" class='Caixa' TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>"></TD>

	<TD>Data Final<br><INPUT size="12" maxlength="10" class='Caixa' TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>"></TD>
</TR>


<TR>
	<TD colspan='2'>
			Família<br>
			<!-- começa aqui -->
			<?
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='familia'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia   = trim(pg_result($res,$x,familia));
					$aux_descricao = trim(pg_result($res,$x,descricao));
					
					echo "<option value='$aux_familia'"; 
					if ($familia == $aux_familia){
						echo " SELECTED "; 
						$mostraMsgfamilia = "<br> da Família $aux_descricao";
					}
					echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n";
			}
			?>
	</TD>
</TR>

<TR>
	<td colspan = '2'>
		Por região<br>
		<select name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>> TODOS OS ESTADOS</option>
			<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
			<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
			<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
			<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
			<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
			<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
			<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
			<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
			<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
			<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
			<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
			<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
			<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
		</select>
	</td>
</TR>
<TR>
	<input type='hidden' name='btn_finalizar' value='0'>
	<TD colspan="2"><br><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
</TR>

</TBODY>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
if ($listar == "ok") {
	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
	if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
	if (strlen ($posto)  > 0)  $cond_3 = " tbl_posto.posto     = $posto ";

	$aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . " 00:00:00";
	$aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . " 23:59:59";
	
	$sql = "
		SELECT os 
		INTO TEMP temp_fcrposto_$login_admin
		FROM tbl_os_extra
		JOIN tbl_extrato on tbl_os_extra.extrato = tbl_extrato.extrato 
		WHERE tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
		AND tbl_extrato.liberado IS NOT NULL
		AND tbl_extrato.fabrica = $login_fabrica;

		CREATE INDEX temp_fcrposto_OS_$login_admin on temp_fcrposto_$login_admin(os);

		SELECT 	tbl_posto_fabrica.codigo_posto, 
				tbl_posto.nome, 
				tbl_posto.estado, 
				tbl_os.os
		INTO TEMP temp_fcrposto2_$login_admin
		FROM tbl_os
		JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto 
		AND  tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
		JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
		JOIN temp_fcrposto_$login_admin fcr1 ON tbl_os.os = fcr1.os
		WHERE tbl_os.fabrica = $login_fabrica 
		AND $cond_1
		AND $cond_2 
		AND tbl_os.excluida IS NOT TRUE;
		
		CREATE INDEX temp_fcrposto2_OS_$login_admin on temp_fcrposto2_$login_admin(os);

		SELECT codigo_posto, nome, estado, count(os) as qtde
		FROM temp_fcrposto2_$login_admin fcr
		GROUP BY codigo_posto, nome, estado
		order by qtde desc";

	$res = pg_exec ($con,$sql);


//echo $sql;
	if (pg_numrows($res) > 0) {
		echo "<br>";
		
		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgfamilia $mostraMsgEstado $mostraMsgPosto</b>";
		
		echo "<br><br>";
		echo "<center><div style='width:750px;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
		echo "<thead>";
		echo "<TR>";
		echo "<TD width='120' height='15' ><b>Código Posto</b></TD>";
		echo "<TD width='55%' height='15' ><b>Posto</b></TD>";
		echo "<TD width='80' height='15' ><b>Estado</b></TD>";		
		echo "<TD width='120' height='15' ><b>Ocorrência</b></TD>";
		echo "<TD width='05%' height='15' ><b>%</b></TD>";
		echo "</TR>";
		echo "</thead>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,qtde);
		}
		echo "<tbody>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$codigo_posto = trim(pg_result($res,$i,codigo_posto));
			$nome  = trim(pg_result($res,$i,nome));
			$estado    = trim(pg_result($res,$i,estado));
			$ocorrencia = trim(pg_result($res,$i,qtde));
			
#			if (strlen($estado) > 0)   $estado      = trim(pg_result($res,$i,estado));
			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			
			$cor = '2';
			if ($i % 2 == 0) $cor = '1';
			
			echo "<TR>";
			echo "<TD align='left' nowrap>$codigo_posto</TD>";
			echo "<TD align='left' nowrap>$nome</TD>";
			echo "<TD align='center' nowrap>$estado</TD>";
			echo "<TD align='center' nowrap>$ocorrencia</TD>";
			echo "<TD align='right' nowrap title='%'>". number_format($porcentagem,2,".",".") ."</TD>";
			echo "</TR>";
		}
		echo "</tbody>";
		echo "</TABLE></div>";


	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgfamilia $mostraMsgEstado $mostraMsgPosto</b>";
	}
	
}

flush();

?>

<p>

<? include "rodape.php" ?>
