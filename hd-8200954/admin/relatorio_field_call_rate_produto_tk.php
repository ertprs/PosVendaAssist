<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "monitora.php";
if ($login_fabrica == 14){
	header("Location: relatorio_field_call_rate_produto_familia.php");
	exit;
}
if ($login_fabrica == 15){
	header("Location: relatorio_field_call_rate_produto_latinatec.php");
	exit;
}

// Criterio padr�o
$_POST["criterio"] = "data_digitacao";
//////////////////

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
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
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	
	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}
//???? Duvidas aqui, pra que serve essa condi��o?
if($login_fabrica == 20 and $pais !='BR'){
	if(strlen($_POST["pais"]) > 0) $pais = trim($_POST["pais"]);
}
	$tipo_os = trim($_POST['tipo_os']);

	$codigo_posto = "";
	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_descricao']) ;
	
	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto 
				from tbl_produto 
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}

	}

	/*if (strlen($erro) == 0) {
		if(strlen($_POST["criterio"]) == 0) {
			$erro .= "Favor informar o crit�rio (Abertura ou Lan�amento de OS) para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$aux_criterio = trim($_POST["criterio"]);
		}
	}*/
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$estado       = trim($_POST["estado"]);
		$tipo_pesquisa    = trim($_POST["tipo_pesquisa"]);
		$pais         = trim($_POST["pais"]);
		$criterio     = trim($_POST["criterio"]);
		$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
		$produto_descricao  = trim($_POST['produto_descricao']) ; // HD 2003 TAKASHI
		$tipo_os            = trim($_POST['tipo_os']);
		$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg_erro .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELAT�RIO - FIELD CALL-RATE : LINHA DE PRODUTO";

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

function AbrePeca(produto,data_inicial,data_final,linha,estado,pais,posto,tipo,tipo_pesquisa){
	janela = window.open("relatorio_field_call_rate_pecas2_tk.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&pais=" + pais +"&posto=" + posto + "&consumidor_revenda=" + tipo + "&tipo_pesquisa=" + tipo_pesquisa ,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
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
	text-align: center;
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
.bgTRConteudo3{
	background-color: #FFCCCC;
}

-->
</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUN��ES> ================================!-->
<!--  XIN�S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<DIV ID="container" style="width: 100%; ">

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
	<TD colspan="4" class="menu_top" background='imagens_admin/azul.gif' align='center'><b>Pesquisa</b></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este relat�rio considera a data de gera��o do extrato aprovado.</center></TD>
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
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calend�rio"></center></TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calend�rio"></center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan='4' CLASS='table_line' > <center>Linha</center></TD>
  </TR>
  
  <TR width='100%' align="center">
	  <TD colspan='4' CLASS='table_line'>
		<center>
			<!-- come�a aqui -->
			<?
			$w = "";
			// HD 2670 - IGOR - PARA A TECTOY, N�O MOSTRAR A LINHA GERAL, QUE VAI SER EXCLUIDA
			if($login_fabrica==6){
				$w = " AND linha<>39 ";
			}

			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica 
					$w
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='linha'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				if($login_fabrica == 15){
					echo "<option value='LAVADORAS LE'>";
					echo "LAVADORAS LE</option>";
					echo "<option value='LAVADORAS LS'>";
					echo "LAVADORAS LS</option>";
					echo "<option value='LAVADORAS LX'>";
					echo "LAVADORAS LX</option>";
					echo "<option value='IMPORTA��O DIRETA WAL-MART'>";
					echo "IMPORTA��O DIRETA WAL-MART</option>";
					echo "<option value='Purificadores / Bebedouros - Eletr�nicos'>";
					echo "Purificadores / Bebedouros - Eletr�nicos</option>";
				}
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; 
					if ($linha == $aux_linha){
						echo " SELECTED "; 
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
          	?>
		</center>
	</TD>


	</TR>
<? if($login_fabrica==20 or $login_fabrica == 11){ //HD 4170 Paulo colocado (or $login_fabrica == 11)?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<td class="table_line" >Ref. Produto</td>
		<td class="table_line" >Descri��o Produto</td>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>

	<tr align="center">
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<td CLASS='table_line' align='center'>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
		&nbsp;
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</td>

		<td CLASS='table_line'>
		<input class="frm" type="text" name="produto_descricao" size="15" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
<? } ?>
<? if($login_fabrica==24){ ?>
  <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>Por tipo</center></TD>
  </TR>
  <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>
<select name="tipo_os" size="1">
<option value=""></option>
<option value="C">Consumidor</option>
<option value="R">Revenda</option>
</select>
</center></TD>
  </TR>

<? } 

	// Alterado por Paulo atrav�s do chamado Samel para Field Call Rate pa�ses fora do Brasil
	if($login_fabrica == 20){    ?>
	
		<TR width = '100%' align="center">
		   <TD colspan='4' CLASS='table_line' >Pa�s</TD>
		</TR>

		<TR width='100%' align="center">
			<TD colspan='4' CLASS='table_line' >
			<?
				$sql = "SELECT  *
						FROM    tbl_pais
						$w
						ORDER BY tbl_pais.nome;";
				$res = pg_exec ($con,$sql);
				
				if (pg_numrows($res) > 0) {
					echo "<select name='pais'>\n";
					if(strlen($pais) == 0 ) {
						$pais = 'BR';
					}
					
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_pais  = trim(pg_result($res,$x,pais));
						$aux_nome  = trim(pg_result($res,$x,nome));
						
						echo "<option value='$aux_pais'"; 
						if ($pais == $aux_pais){
							echo " SELECTED "; 
							$mostraMsgPais = "<br> do PA�S $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n";
				} ?>
			</td>
		</tr>
		<TR width = '100%' align="center">
		   <TD colspan='2' CLASS='table_line' >C�d. Posto</TD>
		   <TD colspan='2' CLASS='table_line' >Nome Posto</TD>
		</TR>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2' align='center'>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td colspan='1' align='center'>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
		</td>
		<td colspan='1' align='center'>
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
	</tr>

		
		<?
	}
		?>
  <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>Por regi�o</center></TD>
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
			<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amap�</option>
			<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Cear�</option>
			<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Esp�rito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goi�s</option>
			<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranh�o</option>
			<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Par�</option>
			<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Para�ba</option>
			<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piau�</option>
			<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paran�</option>
			<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rond�nia</option>
			<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - S�o Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
		</select>
		</center>
	</td>
  </TR>
<?  if($login_fabrica==6){?>
 <TR width = '100%' align="center">
	  <TD colspan = '4' CLASS='table_line' > <center>Pela data</center></TD>
  </TR>
  <TR width = '100%' align="center">
	<td colspan = '4' CLASS='table_line'>
		<center>
		<select name="tipo_pesquisa" size="1">
			<option value="data_geracao" <?if($tipo_pesquisa=="data_geracao"){echo "SELECTED";}?>>de gera��o de extrato</option>
			<option value="data_abertura" <?if($tipo_pesquisa=="data_abertura"){echo "SELECTED";}?>>de abertura de OS</option>
		</select>
		</center>
	</td>
  </TR>
<? } ?>
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submiss�o da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<?
/* Chamado 1444

LINHA IMPORTA��O DIRETA WAL-MART Compreende:
Atlantic Breese, Audiologic, Aurora, Climatizador De Vinho, Coby, 
Derens, Digistar, Digital Lab, Digix, Durabrand, Envirocaire, 
Galanz, Gourmet Wave, Gpx, Honeywell, Ihome, In Motion, Memorex, 
Monacia, Pelonis, Ritech, Royal, Simz, Studebacker, Trc Sound, 
Venturer, Vivitar
*/

/*
Purificadores / Bebedouros - Eletr�nicos
*/

/* Chamado 2009
LE = Deve compreender os modelos: 
	LE4.6 / LE4.6 / LE 4.16A / LE 4.6M / GL / MN / TI / CA / FLEX.
('21641','21640','11753','11750','11690','11905','11906','11907','11908','11909','11910','11543','11524','11525','11819','11818')

LS = Deve compreender os modelos: 
	LS5E / LS5A / 5M / 20AR / 20AN e LS32RE.
('21639','21638','11529','11820','11863','11552','11553','11530','11531','11521','11522','11532','11533','11838','11984','11821','11911','12015','12008','11519','11520','12002','11854','11528','11542','11912','11511','11913','11523','11526','11527','11510')

LX = Deve compreender os modelos: 
	VL / VR / CT / LX / LX4.5 / MAX / VIP.
('21645','21644','21643','21642','21639','21638','11745','12003','11746','11917','11916','11991','11914','11915')

*/

?>
<!-- =========== AQUI TERMINA O FORMUL�RIO FRM_PESQUISA =========== -->
</DIV>

<?
if ($listar == "ok") {

	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	$cond_4 = "1=1"; // HD 2003 TAKASHI
	$cond_5 = "1=1";
	$cond_6 = "1=1";
	if($login_fabrica == 6){
		$cond_1 = " linha <> 39 ";
	}

	//cond_3
	if($login_fabrica == 20 and strlen($codigo_posto)>0){
		$sql = "SELECT  posto
				FROM    tbl_posto_fabrica 
				WHERE fabrica = 20 and codigo_posto = '$codigo_posto';";
		//echo "sql: $sql";
		
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$posto = trim(pg_result($res,0,posto));
		}
	}

	if (strlen ($linha)    > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado)   > 0) $cond_2 = " tbl_posto.estado  = '$estado' ";
	if (strlen ($posto)    > 0) $cond_3 = " tbl_posto.posto   = $posto ";
	if (strlen ($produto)  > 0) $cond_4 = " tbl_os.produto    = $produto "; // HD 2003 TAKASHI
	if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
	if (strlen ($pais)     > 0) $cond_6 = " tbl_posto.pais	  = '$pais' ";
	//Chamado = 1444
	if ($linha == "IMPORTA��O DIRETA WAL-MART" AND $login_fabrica == 15) $cond_1 = " tbl_produto.linha in('398','344','311','403','390','343','329','400','342','317','401','338','399','346','307','393','395','345','310','375','339','396','330','376','392','341','402') ";
	if ($linha == "LAVADORAS LE" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21641','21640','11753','11750','11690','11905','11906','11907','11908','11909','11910','11543','11524','11525','11819','11818') ";
	if ($linha == "LAVADORAS LS" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21639','21638','11529','11820','11863','11552','11553','11530','11531','11521','11522','11532','11533','11838','11984','11821','11911','12015','12008','11519','11520','12002','11854','11528','11542','11912','11511','11913','11523','11526','11527','11510') ";
	if ($linha == "LAVADORAS LX" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21645','21644','21643','21642','21639','21638','11745','12003','11746','11917','11916','11991','11914','11915') ";
	if ($linha == "Purificadores / Bebedouros - Eletr�nicos" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('12007','12017') ";

	//Para a Bosch tem a tradu��o do produto
	if($login_fabrica == 20 and $pais !='BR'){
		$produto_descricao   ="tbl_produto_idioma.descricao ";
		$join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
	}else{
		$produto_descricao   ="tbl_produto.descricao ";
		$join_produto_idioma =" ";
	}




	$sql = "SELECT tbl_produto.produto     ,
					tbl_produto.ativo      ,
					tbl_produto.referencia ,
					$produto_descricao     ,
					fcr1.qtde AS ocorrencia,
					tbl_produto.familia    ,
					tbl_produto.linha
			 FROM tbl_produto
			 $join_produto_idioma
			 JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os 
							FROM tbl_os_extra
							JOIN tbl_extrato        USING (extrato)
							JOIN tbl_extrato_extra  ON tbl_extrato_extra.extrato = tbl_extrato.extrato
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND ";

	if($login_fabrica == 20 and $pais != 'BR'){
		$sql .=	" tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}else{
		if($login_fabrica == 20){ 
			$sql .=	" tbl_extrato_extra.exportado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}else{
			$sql .=	" tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}
	}
	if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL";

	// Alterado por Paulo atrav�s do chamado Samel para Field Call Rate pa�ses fora do Brasil
	$sql .= " ) fcr ON tbl_os.os = fcr.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_os.excluida IS NOT TRUE
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
				AND $cond_6
				GROUP BY tbl_os.produto
		) fcr1 ON tbl_produto.produto = fcr1.produto
		WHERE $cond_1
		ORDER BY fcr1.qtde DESC " ;
		


// echo "<BR>";
if($login_fabrica==24){

	$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia, tbl_produto.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha
			FROM tbl_produto
			JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os
							FROM tbl_os_extra
							JOIN tbl_extrato       USING (extrato)
							JOIN tbl_extrato_extra USING (extrato)
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND  tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE tbl_os.excluida IS NOT TRUE
					AND $cond_2
					AND $cond_3
					AND $cond_4
					AND $cond_5
					GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1 
			ORDER BY fcr1.qtde DESC " ;



}//echo $sql;
if($login_fabrica==6){
	$sql = "SELECT tbl_produto.produto, 
					tbl_produto.ativo, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					fcr1.qtde AS ocorrencia,
					tbl_produto.familia,
					tbl_produto.linha
			FROM tbl_produto 
			JOIN (
				SELECT tbl_os.produto, COUNT(*) AS qtde
				FROM tbl_os
				JOIN (
					SELECT tbl_os_extra.os
					FROM tbl_os_extra
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' 
				) fcr ON tbl_os.os = fcr.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE  tbl_os.excluida IS NOT TRUE
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
			GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1 
			ORDER BY fcr1.qtde DESC " ;
	if($tipo_pesquisa == "data_abertura"){
		$sql = "SELECT tbl_produto.produto, 
					tbl_produto.ativo, 
					tbl_produto.referencia, 
					tbl_produto.descricao, 
					fcr1.qtde AS ocorrencia,
					tbl_produto.familia,
					tbl_produto.linha
			FROM tbl_produto 
			JOIN (
				SELECT tbl_os.produto, COUNT(*) AS qtde
				FROM tbl_os
				JOIN (
					SELECT tbl_os.os
					FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final' 
				) fcr ON tbl_os.os = fcr.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_os.excluida IS NOT TRUE
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
			GROUP BY tbl_os.produto
			) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1 
			ORDER BY fcr1.qtde DESC " ;
	
	}
}
flush();
 echo "<br>1� SQL $sql<br> ";
/*if($ip=="189.18.153.173") {
	echo $sql; 
	//exit;
}*/
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$total = 0;
		echo "<br>";
		
		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais </b>";
		
		echo "<br><br>";
	
		if ($login_fabrica==5){
			echo "<div name='leg' align='center' style='padding-left:10px'>";
			echo "<b style='border:1px solid #666666;background-color:#FFCCCC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Produtos que est�o inativos</b>";
			echo "</div>";
		}else{
			echo "<FONT SIZE=\"2\">(*) Produtos que est�o inativos.</FONT>";
		}

		echo "<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "	<TR>";
		echo "		<TD width='30%' height='15' class='table_line'><b>Refer�ncia</b></TD>";
		echo "		<TD width='55%' height='15' class='table_line'><b>Produto</b></TD>";
		echo "		<TD width='10%' height='15' class='table_line'><b>Ocorr�ncia</b></TD>";
		echo "		<TD width='05%' height='15' class='table_line'><b>%</b></TD>";
		echo "	</TR>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia   = trim(pg_result($res,$i,referencia));
			$ativo        = trim(pg_result($res,$i,ativo));
			$descricao    = trim(pg_result($res,$i,descricao));
			if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
			$descricao    = "<font color = 'red'>Tradu��o n�o cadastrada.</font>";
			}
			$produto      = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			
#			if (strlen($estado) > 0)   $estado      = trim(pg_result($res,$i,estado));
			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			
			$cor = '2';
			if ($i % 2 == 0) $cor = '1';
// Todo produto que for inativo estar� com um (*) na frente para indicar se est� Inativo ou Ativo.
			if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';} 

// Alterado por Fabio em 14/03/2007
// Para identificar as pe�as inativas, trocei o * pela cor de funco avermelhada
			if ($login_fabrica==5 and $ativo=='f') {
				$ativo="";	
				$cor="3";
			}

				echo "<TR class='bgTRConteudo$cor'>";
				echo "<TD class='conteudo10' align='left' nowrap>$ativo<a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"$tipo_os\",\"$tipo_pesquisa\");'>$referencia</a></TD>";
				echo "<TD class='conteudo10' align='left' nowrap>$descricao</TD>";
				echo "<TD class='conteudo10' align='center' nowrap>$ocorrencia</TD>";
				echo "<TD class='conteudo10' align='right' nowrap>". number_format($porcentagem,2,",",".") ." %</TD>";
				echo "</TR>";
			
				$total = $ocorrencia + $total;

		}
				echo "<tr class='table_line'><td colspan='2'><font size='2'><b><CENTER>TOTAL DE PRODUTOS COM DEFEITOS</b></td><td colspan='2'><font size='2' color='009900'><b>$total</b></td></tr>";
				echo " </TABLE>";
		
				echo "<br>";
				echo "<hr width='600'>";
				echo "<br>";
		
		// monta URL
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$estado       = trim($_POST["estado"]);
		$pais         = trim($_POST["pais"]);
		$criterio     = trim($_POST["criterio"]);
		$tipo_pesquisa    = trim($_POST["tipo_pesquisa"]);
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&estado=$estado&pais=$pais&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Voc� pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		
	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";
	}
	
}

flush();

?>

<p>

<? include "rodape.php" ?>
