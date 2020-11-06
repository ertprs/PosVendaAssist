<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$nota_fiscal   = $_GET["nota_fiscal"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);


//print_r($_POST);
if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_itens     = trim($_POST["qtde_itens"]);
	if (strlen($qtde_itens)==0){
		$qtde_itens = 0;
	}

	if($select_acao == "retornar" ){
		$upd_devolucao_obrigatoria_lgr = " true ";
	}else{
		$upd_devolucao_obrigatoria_lgr = " false ";
	}

	for ($x=0;$x<$qtde_itens;$x++){

		$xxfaturamento_item         = trim($_POST["check_".$x]);

		if (strlen($xxfaturamento_item) > 0 AND strlen($msg_erro) == 0){

			$res_item = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT faturamento_item,
							peca
					FROM tbl_faturamento_item
					WHERE faturamento_item= $xxfaturamento_item";
			$res_item = pg_exec($con,$sql);
			if (pg_numrows($res_item)>0){
				$peca = pg_result($res_item, 0, peca);

				$sql = "SELECT produto_acabado,
								devolucao_obrigatoria
						FROM tbl_peca 
						WHERE fabrica = $login_fabrica 
							AND peca = $peca ";
				$res_item = pg_exec($con,$sql);
				$produto_acabado       = pg_result($res_item, 0, produto_acabado);
				$devolucao_obrigatoria = pg_result($res_item, 0, devolucao_obrigatoria);
				
				if($produto_acabado == 't'){
					$devolucao_obrigatoria_lgr = 1; 
				}else{
					if($devolucao_obrigatoria =='t'){
						$devolucao_obrigatoria_lgr = 2; 
					}else{
						$devolucao_obrigatoria_lgr = 3; 
					}
				}
				$sql = "UPDATE tbl_faturamento_item
						SET
							devolucao_obrigatoria_lgr = $devolucao_obrigatoria_lgr
						WHERE faturamento_item= $xxfaturamento_item";
				//echo "sql: $sql ";
				$res_item = pg_exec($con,$sql);
			}

			if (strlen($msg_erro)==0){
				$res = pg_exec($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$layout_menu = "auditoria";
$title = "Auditoria de OSs reincidentes, sem peças ou com mais de 3 peças";

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

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}


</script>


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

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}


</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>


<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$nota_fiscal  = trim($_POST['nota_fiscal']);
	if (strlen($nota_fiscal)>0){
		$Xos = " AND nota_fiscal= '$nota_fiscal' ";
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}



?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>LGR MANUAL</caption>

<TBODY>
<TR>

	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm"></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm"></TD>
</TR>
</tbody>
<TR>
	<TD colspan="2">
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>

<TABLE width="300" align="center" border="0" cellspacing='0' cellpadding='0' >
<TR>
	<TD width="20px" bgcolor='#3399FF' >PA</TD>
	<TD>PRODUTO ACABADO</TD>
</TR>
<TR>
	<TD width="20px" bgcolor='#FFCC66' >PC</TD>
	<TD>PEÇA</TD>
</TR>

</table>

<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {
	$posto_codigo= trim($_POST["posto_codigo"]);

	if(strlen($posto_codigo)>0){
		$sql = " 
				SELECT posto 
				FROM tbl_posto_fabrica
				WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo' 
					AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
				$posto = pg_result($res, 0, posto);
		}
	}	


	$sql =  "
			SELECT 
				tbl_faturamento_item.preco,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.aliq_icms,
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				sum(tbl_faturamento_item.qtde) as qtde_real,
				tbl_extrato_lgr.qtde

			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING(faturamento)
			JOIN tbl_peca             ON tbl_peca.peca = tbl_faturamento_item.peca and tbl_peca.fabrica = $login_fabrica
			join tbl_extrato_lgr  on tbl_extrato_lgr.extrato = tbl_faturamento.extrato_devolucao
			WHERE  tbl_faturamento.fabrica = $login_fabrica
				AND    tbl_faturamento.posto   = $posto
				AND    tbl_faturamento.emissao >= '2008-01-01'
				/*AND    tbl_faturamento.emissao <  '2009-04-15'*/
				AND    tbl_faturamento.extrato_devolucao =444767
				AND    (tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923'))
				and tbl_peca.produto_acabado is false
			GROUP BY 
				tbl_faturamento_item.preco,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.aliq_icms,
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_extrato_lgr.qtde
			ORDER BY descricao;";

//echo nl2br($sql); die;
	$res = pg_exec($con,$sql);



	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='posto_codigo'     value='$posto_codigo'>";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Prod/Peça</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Nota Fiscal</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Emissão</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Cód. Peça</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Descrição</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Qtde</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Preço</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Total</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>ICMS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>IPI</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;
		$total_os = pg_numrows($res);
		for ($x=0; $x<pg_numrows($res);$x++){

			//$faturamento_item		= pg_result($res, $x, faturamento_item);
			//$nota_fiscal 			= pg_result($res, $x, nota_fiscal);
			//$emissao				= pg_result($res, $x, emissao);
			$referencia				= pg_result($res, $x, referencia);
			$descricao				= pg_result($res, $x, descricao);
			$qtde					= pg_result($res, $x, qtde);
			$preco					= pg_result($res, $x, preco);
			$icms					= pg_result($res, $x, aliq_icms);
			$ipi					= pg_result($res, $x, aliq_ipi);
			//$devolucao_obrigatoria_lgr= pg_result($res, $x, devolucao_obrigatoria_lgr);
			//$produto_acabado		= pg_result($res, $x, produto_acabado);
			$total					= $qtde * $preco;
			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			$cor_prod = $cor;
			if($produto_acabado =='t'){
				$cor_prod = "#3399FF";
				$produto_acabado_peca = "PA";
			}else{
				$cor_prod = "#FFCC66";
				$produto_acabado_peca = "PC";
			}
			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
				echo "<input type='checkbox' name='check_$x' id='check_$x' value='$faturamento_item' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
				if ($devolucao_obrigatoria_lgr==1 or $devolucao_obrigatoria_lgr==2 or $devolucao_obrigatoria_lgr==3){
					echo " CHECKED ";
				}
				echo ">";
			echo "</td>";
			echo "<td bgcolor='$cor_prod' width='20px' align='center'>$produto_acabado_peca</td>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap >$nota_fiscal</td>";
			echo "<td style='font-size: 9px; font-family: verdana'>$emissao</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap >$referencia</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap >$descricao</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>$qtde</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>$preco</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>$total</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>$icms</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>$ipi</td>";
			echo "</tr>";

		}
		echo "<input type='hidden' name='qtde_itens' value='$x'>";
		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";

		echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
		echo "<select name='select_acao' size='1' class='frm' >";
		echo "<option value=''></option>";
			echo "<option value='retornar'";  if ($_POST["select_acao"] == "retornar")  echo " selected"; echo ">RETORNAR</option>";
			echo "<option value='nao_retornar'";  if ($_POST["select_acao"] == "nao_retornar")  echo " selected"; echo ">NÃO RETORNAR</option>";
		echo "</select>";
		echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhum registro encontrado.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>