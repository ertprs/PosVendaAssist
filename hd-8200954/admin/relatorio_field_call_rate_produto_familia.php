<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";

#echo "Programa em Manutenção";
#exit;


if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";


// Criterio padrão
$_POST["criterio"] = "data_digitacao";
$criterio = "data_digitacao";
//////////////////


if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);


if (strlen($btn_acao) > 0) {


	if (strlen(trim($_POST["data_inicial_01"])) > 0) $data_inicial = trim($_POST["data_inicial_01"]);
	if (strlen(trim($_GET["data_inicial_01"])) > 0) $data_inicial = trim($_GET["data_inicial_01"]);

	$fnc            = pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}
	
	//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
	if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

	
	if (strlen(trim($_POST["data_final_01"])) > 0) $data_final = trim($_POST["data_final_01"]);
	if (strlen(trim($_GET["data_final_01"])) > 0) $data_final = trim($_GET["data_final_01"]);

	$fnc            = pg_exec($con,"SELECT fnc_formata_data('$data_final')");
	
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}
	
	//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
	if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);

	if(strlen($aux_data_inicial) == 0 OR strlen($aux_data_final) == 0){
		$msg_erro = "Informe o período para pesquisa";
	}

	if (strlen(trim($_POST["familia"])) > 0) $familia = trim($_POST["familia"]);
	if (strlen(trim($_GET["familia"])) > 0) $familia = trim($_GET["familia"]);

	if(strlen($_POST["estado"]) > 0){

		if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
		if (strlen(trim($_GET["estado"])) > 0) $estado = trim($_GET["estado"]);

		$mostraMsgEstado = "<br>no ESTADO $estado";
	}
	
	if (strlen($msg_erro) == 0) {
		if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
		if (strlen(trim($_GET["posto_nome"])) > 0) $posto_nome = trim($_GET["posto_nome"]);

		if (strlen(trim($_POST["familia"])) > 0) $familia = trim($_POST["familia"]);
		if (strlen(trim($_GET["familia"])) > 0) $familia = trim($_GET["familia"]);

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

<?
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

.divisao{
	width:600px;
	text-align:center;
	margin:0 auto;
	font-size:10px;
	background-color:#FEFCCF;
	border:1px solid #928A03;
	padding:5px;
}
.sucesso{
	width:500px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}


.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
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
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}
	.Conteudo {
		font-family: Arial;
		font-size: 10px;
		font-weight: normal;
	}

	h1.home {
		margin: 0;
		border: none;
	}
	h2 {
		font-size: 1.2em;
		margin: 1em 0 1em;
		padding: 0 0 .3em;
		border-bottom: 2px solid #ccc;
	}
	h3 {
		font-size: 1.1em;
		font-weight: normal;
		padding: 0 0 10px;
		border-bottom: 2px solid #ccc;
	}

</style>
<? include "javascript_pesquisas.php"; ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->


<DIV ID="container" style="width: 100%; ">
<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->


<?

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}


if (strlen($msg_erro) > 0) {
	echo "<table width='730' border='0' cellpadding='2' cellspacing='2' align='center' class='error'>";
	echo "<tr>";
		echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
}
?>


<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>As datas se referem à Geração do Extrato<br>e somente OS aprovadas são consideradas</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><center>Data Inicial</center></TD>
    <TD class="table_line"><center>Data Final</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" id="data_inicial_01" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></center></TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" id="data_final_01" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan='4' CLASS='table_line' > <center>Família</center></TD>
  </TR>

  <TR width='100%' align="center">
	  <TD colspan='4' CLASS='table_line'>
		<center>

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
		</center>
	</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>Por região</center></TD>
  </TR>
  <TR width = '100%' align="center">
	<td colspan = '4' CLASS='table_line'>
		<center>
		<select name="estado" size="1">
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
		</center>
	</td>
  </TR>


  <TR width = '100%' align="center">
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line" nowrap>
		<center>
			Código do Posto<br>
			<input type="text" name="posto_codigo" size="10" value="<?echo $posto_codigo?>">
			<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
		</center>
	</TD>
	<TD class="table_line" nowrap>
		<center>
			Razão Social do Posto<br>
			<input type="text" name="posto_nome" size="25" value="<?echo $posto_nome?>">
			<img border="0" src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
		</center>
	</TD>
	<TD class="table_line">&nbsp;</TD>
	</td>
  </TR>
<?if ($login_fabrica==14 AND $login_admin==567){?>
	<tr>
		<TD class="table_line">&nbsp;</TD>
		<TD class="table_line" nowrap colspan='2'><hr></td>
		<TD class="table_line">&nbsp;</TD>
	</tr>

	<tr align="center">
		<TD class="table_line">&nbsp;</TD>
		<TD class="table_line" align='center' style='text-align:center' colspan='2'>
		Tipo Arquivo para Download <br>
		<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
		&nbsp;&nbsp;&nbsp;
		<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
		</td>
		<TD class="table_line">&nbsp;</TD>
	</tr>
<? } ?>
  <TR>
    <input type='hidden' name='btn_acao' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '0' ) { document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {

	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	$cond_4 = "1=1";
	if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
	if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
	if (strlen ($posto)  > 0)  $cond_3 = " tbl_posto.posto     = $posto ";
	if (strlen ($posto)  > 0)  $cond_4 = " tbl_extrato.posto   = $posto ";

		if($login_fabrica <> 14) $aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . " 00:00:00";
		else                     $aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . "";
		if($login_fabrica <> 14) $aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . " 23:59:59";
		else                     $aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . "";
/*
	$sql = "SELECT  tbl_produto.produto   ,
					tbl_produto.familia   ,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					os.ocorrencia
			FROM tbl_produto 
			JOIN (
					SELECT tbl_os.produto, COUNT(*) AS ocorrencia
					FROM   tbl_os
					JOIN   tbl_posto        ON tbl_posto.posto     = tbl_os.posto
					JOIN   tbl_produto      ON tbl_produto.produto = tbl_os.produto
					LEFT JOIN tbl_os_status ON tbl_os_status.os    = tbl_os.os
					WHERE  tbl_os.fabrica = $login_fabrica
					AND    tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
					AND    tbl_os.excluida IS NOT TRUE
					AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
					AND    $cond_1
					AND    $cond_2
					AND    $cond_3
					GROUP BY tbl_os.produto
			) os ON tbl_produto.produto = os.produto
			ORDER BY os.ocorrencia DESC";
*/

if(strlen($msg_erro)==0){
	if($login_fabrica == 14) $entre_datas = "'$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
	else                     $entre_datas = "'$aux_data_inicial' AND '$aux_data_final'";
	$sql = "SELECT tbl_os_extra.os, (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
		INTO TEMP temp_fcr_famila_os
		FROM tbl_os_extra
		WHERE tbl_os_extra.extrato IN (
			SELECT extrato 
			FROM tbl_extrato 
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.data_geracao BETWEEN $entre_datas 
			AND   $cond_4
			AND   tbl_extrato.liberado IS NOT NULL 
		);

		CREATE INDEX temp_fcr_familia_os_os ON temp_fcr_famila_os(os);

		SELECT tbl_os.produto, tbl_os.mao_de_obra, COUNT(*) AS qtde
		INTO TEMP temp_fcr_familia_produto
		FROM tbl_os
		JOIN temp_fcr_famila_os fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto              ON tbl_os.posto = tbl_posto.posto
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		AND tbl_os.excluida IS NOT TRUE
		AND $cond_2
		AND $cond_3
		GROUP BY tbl_os.produto, tbl_os.mao_de_obra;

		CREATE INDEX temp_fcr_familia_produto_produto ON temp_fcr_familia_produto(produto);

		SELECT DISTINCT tbl_produto.produto    ,
				tbl_produto.referencia ,
				tbl_produto.descricao  ,
				fcr1.qtde AS ocorrencia,
				tbl_produto.familia    ,
				fcr1.mao_de_obra
		FROM tbl_produto
		JOIN temp_fcr_familia_produto fcr1 ON tbl_produto.produto = fcr1.produto
		WHERE $cond_1
		ORDER BY fcr1.qtde DESC";
}
#echo nl2br($sql);
$res = pg_exec ($con,$sql);

/* takashi retirou 19/09 hd 3977, (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
(fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND
*/
	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

//if ($ip == "201.0.9.216") { echo nl2br($sql) . "<br>"; echo pg_numrows($res) . "<br>"; }
	if (pg_numrows($res) > 0) {
		echo "<br>";
		
		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgfamilia $mostraMsgEstado $mostraMsgPosto</b>";
		
		echo "<br><br>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='500'>";
		echo "<TR>";
		echo "<TD width='20%' height='15' class='Titulo' background='imagens_admin/azul.gif'><b>Referência</b></TD>";
		echo "<TD width='30%' height='15' class='Titulo' background='imagens_admin/azul.gif'><b>Produto</b></TD>";
		echo "<TD width='10%' height='15' class='Titulo' background='imagens_admin/azul.gif'><b>Ocorrência</b></TD>";
		if($login_fabrica==14){
		echo "<TD width='30%' height='15' class='Titulo' background='imagens_admin/azul.gif'><b>Mão de Obra</b></TD>";
		}
		echo "<TD width='10%' height='15' class='Titulo' background='imagens_admin/azul.gif'><b>%</b></TD>";
		echo "	</TR>";

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}


		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));
			$produto    = trim(pg_result($res,$i,produto));
			if (strlen($familia) > 0) $familia = trim(pg_result($res,$i,familia));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			$mao_de_obra = trim(pg_result($res,$i,mao_de_obra));
			
#			if (strlen($estado) > 0)   $estado      = trim(pg_result($res,$i,estado));
			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			$mao_de_obra = number_format($mao_de_obra, 2, ",",".");
			
			echo "<TR bgcolor='$cor'>";
			echo "<TD class='Conteudo' align='left' nowrap><a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\",\"$posto\");'>$referencia</a></TD>";
			echo "<TD class='Conteudo' align='left' nowrap>$descricao</TD>";
			echo "<TD class='Conteudo' align='center' nowrap>$ocorrencia</TD>";
			if($login_fabrica==14){
				echo "<TD class='Conteudo' align='center' nowrap>$mao_de_obra</TD>";
			}
			echo "<TD class='Conteudo' align='right' nowrap>". number_format($porcentagem,2,",",".") ." %</TD>";
			echo "</TR>";

		}
		echo "</TABLE>";
		
		echo "<br>";
		echo "<hr width='600'>";
		echo "<br>";
		
		// monta URL
		
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo  "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto_familia-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&familia=$familia&estado=$estado&posto=$posto&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";

		if ($login_fabrica==14 or $login_fabrica==66){ #HD 270024
			echo"<br><table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
			echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto_familia_os_xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&familia=$familia&estado=$estado&posto=$posto&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download das OS (EXCEL)</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo "</tr>";
			echo "</table><br>";
		}

		include ("relatorio_field_call_rate_produto_familia_grafico.php"); 

	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgfamilia $mostraMsgEstado $mostraMsgPosto</b>";
	}
	
}

?>

<p>

<? include "rodape.php" ?>
