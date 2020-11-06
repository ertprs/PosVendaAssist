<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,auditoria";
include "autentica_admin.php";


$_POST["criterio"] = "data_digitacao";
//////////////////

if ($btn_finalizar == 1) {
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$msg_erro .= "Favor informar a data inicial para pesquisa<br>";
		}

		if (strlen($msg_erro) == 0) {
			$data_inicial = trim($_POST["data_inicial_01"]);
			$fnc          = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				//$msg_erro = pg_errormessage ($con) ;
				$msg_erro .= "Entre com uma data válida!";
			}

			if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ;
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$msg_erro .= "Favor informar a data final para pesquisa<br>";
		}

		if (strlen($msg_erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}

			if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ;
		}
	}

	if(strlen($aux_data_incial)>0 AND strlen($aux_data_final)>0){
		$sql = "select '$aux_data_final'::date - '$aux_data_inicial'::date ";
		$res = pg_exec($con,$sql);
		if(pg_result($res,0,0)>31){
			$msg_erro .= "Período não pode ser maior que 30 dias";
		}
	}

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}



	$codigo_posto = "";
	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

	if(strlen($_POST["familia"]) > 0){
		$familia = trim($_POST["familia"]);
		$sqlX =	"SELECT descricao FROM tbl_familia WHERE fabrica = $login_fabrica and familia = $familia;";
			$resX = pg_exec($con,$sqlX);
			if (pg_numrows($res) == 1) $familia_nome = trim(pg_result($resX,0,0));
			$mostraMsgFamilia = "<br>na FAMÍLIA $familia_nome";
		
		if (strlen($estado) > 0) $mostraMsgFamilia .= " e ";
	}

	if(strlen($_POST["produto"]) > 0){
		$produto = trim($_POST["produto"]);
	}



	if (strlen($msg_erro) == 0) {
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
				$msg_erro .= " Posto não encontrado<br>";
			}
		}
	}

	$produto_referencia = trim($_POST['produto_referencia']); 
	$produto_descricao  = trim($_POST['produto_descricao']) ;
	
	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){
		$sql = "SELECT produto 
			FROM  tbl_produto 
			JOIN  tbl_familia using(familia)
			WHERE tbl_familia.fabrica    = $login_fabrica
			AND   tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}
	}


	if (strlen($msg_erro) == 0) $listar = "ok";

	if (strlen($msg_erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$estado       = trim($_POST["estado"]);
		$criterio     = trim($_POST["criterio"]);
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $msg_erro;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : DEFEITOS CONSTATADOS";

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

function AbrePeca(produto,data_inicial,data_final,linha,estado,posto,defeito_constatado,familia){
	janela = window.open("relatorio_field_call_rate_pecas2.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&posto=" + posto + "&defeito_constatado=" + defeito_constatado,"produto",'resizable=1,scrollbars=yes,width=750,height=350,top=0,left=0');
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
-->
</style>

<? 
include "javascript_pesquisas.php";
include "javascript_calendario.php";  
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
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

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Pesquisa</caption>

<TBODY>
<TR>
	<TD>Data Inicial<br><INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></TD>
	<TD>Data Final<br><INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
</TR>


<TR>
	<td colspan = '2'>
		Por família<br>
		<?
		$sql =	"SELECT *
				FROM tbl_familia
				WHERE fabrica = $login_fabrica
				ORDER BY descricao;";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			echo "<select name='familia' size='1' class='frm' >";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$aux_familia = trim(pg_result($res,$i,familia));
				$aux_nome  = trim(pg_result($res,$i,descricao));
				echo "<option value='$aux_familia'";
				if ($familia == $aux_familia) echo " selected";
				echo ">$aux_nome</option>";
			}
			echo "</select>";
		}
		?>
	</td>
</TR>

	<tr>
		<td >Código Produto<br>
		<input type="text" name="produto_referencia" size="10" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" > 
		<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</td>
		<td>Descrição<br>
		<input type="text" name="produto_descricao" size="25" class='frm' value="<? echo $produto_descricao ?>" >
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		</td>
	</tr>

	<TR>
	<td colspan = '2'>
		Por região<br>
		<select class='frm' name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
<!-- 			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>UF</option> -->
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
	<TD nowrap>
			Código do Posto<br>
			<input type="text" class='frm' name="posto_codigo" size="10" value="<?echo $posto_codigo?>">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
	</TD>
	<TD nowrap>
			Razão Social do Posto<br>
			<input type="text" class='frm' name="posto_nome" size="25" value="<?echo $posto_nome?>">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
	</TD>
</TR>

	<TR>
		<TD colspan="2">
			<input type='hidden' name='btn_finalizar' value='0'>
			<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
		</TD>
	</TR>
</TABLE>

</FORM>

</DIV>

<?
flush();
if ($listar == "ok") {

	$aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . "";
	
	$aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . "";

	$entre_datas = "'$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";	
	
	if (strlen ($posto)    > 0) $cond_1 = " AND tbl_extrato.posto         = $posto ";
	if (strlen($estado)    > 0) $cond_2 = " AND tbl_posto.estado          = '$estado' ";
	if (strlen($familia) > 0) {
		$cond_3 .= " and tbl_produto.familia = $familia ";
		}
	if(strlen($produto)>0) $cond_4 = " AND tbl_os.produto = $produto ";

	$sql = "
		SELECT tbl_os_extra.os 
		INTO TEMP temp_fcr_defeito_constatado_os
		FROM tbl_os_extra
		WHERE tbl_os_extra.extrato IN (
			SELECT extrato 
			FROM tbl_extrato 
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.data_geracao BETWEEN $entre_datas
			$cond_1
			AND   tbl_extrato.liberado IS NOT NULL 
		);

		CREATE INDEX temp_fcr_defeito_constatado_os_os ON temp_fcr_defeito_constatado_os(os);

		SELECT	tbl_os.defeito_constatado,
				COUNT(*) AS qtde
		INTO TEMP temp_fcr_defeito_constatado
		FROM tbl_os
		JOIN temp_fcr_defeito_constatado_os fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto              ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto            ON tbl_os.produto= tbl_produto.produto
		WHERE  tbl_os.excluida IS NOT TRUE
		$cond_2
		$cond_3
		$cond_4
		GROUP BY tbl_os.defeito_constatado
				 ;

		CREATE INDEX temp_fcr_defeito_constatado_defeito_constatado ON temp_fcr_defeito_constatado(defeito_constatado);

		SELECT	tbl_defeito_constatado.descricao, 
				fcr1.qtde AS ocorrencia,
				fcr1.defeito_constatado
		FROM tbl_defeito_constatado
		JOIN temp_fcr_defeito_constatado fcr1 ON tbl_defeito_constatado.defeito_constatado = fcr1.defeito_constatado
		WHERE tbl_defeito_constatado.fabrica=$login_fabrica
		ORDER BY fcr1.qtde DESC ";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<br>";

		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgFamilia $mostraMsgEstado</b>";

		echo "<br><br>";
		echo "<center><div style='width:750px;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' >";
		echo "<thead>";
		echo "<TR  height='25'>";
		echo "<TD width='55%' height='15'><b>Defeito Constatado</b></TD>";
		echo "<TD width='120' height='15'><b>Ocorrência</b></TD>";
		echo "<TD width='50' height='15'><b><center>%</center></b></TD>";
		echo "</TR>";
		echo "</thead>";

		$total = 0;
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		echo "<tbody>";
		for ($i = 0; $i < pg_numrows($res); $i++){
			flush();
			$descricao             = trim(pg_result($res,$i,descricao));
			$ocorrencia            = trim(pg_result($res,$i,ocorrencia));

			$defeito_constatado    = trim(pg_result($res,$i,defeito_constatado));

			if ($total_ocorrencia > 0) {
				$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			}


			echo "<TR>";
			echo "<TD align='left'><a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$familia\",\"$estado\",\"$posto\",\"$defeito_constatado\");'>$descricao</a></TD>";

			echo "<TD align='center'>$ocorrencia</TD>";
			echo "<TD align='right' title='%'>". number_format($porcentagem,2,".",".") ."</TD>";
			echo "</TR>";
			$total = $ocorrencia + $total;
		}
		echo "</tbody>";
		echo "<tr><td colspan='2'><font size='2'><b><CENTER>TOTAL DE DEFEITOS CONSTATADOS</b></td><td colspan='2'><font size='2' color='009900'><b>$total</b></td></tr>";
		echo "</TABLE><br clear=both></div>";

	}else{

		echo "<br>";

		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgFamilia $mostraMsgEstado</b>";
	}
}

?>

<? include "rodape.php" ?>
