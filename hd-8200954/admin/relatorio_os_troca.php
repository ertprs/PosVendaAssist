<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

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
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];


$btn_acao = $_POST["btn_acao"];


$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ORDEM DE SERVIÇO DE TROCA";

include "cabecalho.php";

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
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

<script type="text/javascript">
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
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

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
	f = document.frm_pesquisa;
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
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

</script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
include_once "../js/js_css.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>


<script type="text/javascript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
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

<script>
function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}
</script>


<? include "javascript_pesquisas.php";

$btn_acao       = $_POST['btn_acao'];

if($btn_acao == 'Pesquisar'){

	$data_inicial        = trim($_POST['data_inicial']);
	$data_final          = trim($_POST['data_final']);
	$status              = trim($_POST['status']);
	$posto_codigo        = trim($_POST['posto_codigo']);
	$troca               = $_POST['troca'];
	$os_troca_especifica = trim($_POST['os_troca_especifica']);
    $modelo_produto      = trim($_POST['modelo_produto']);
    $causa_troca         = $_POST['causa_troca'];
    $linha               = $_POST['linha'];
	$marca               = $_POST['marca'];


	if(strlen($data_inicial) == 0 and strlen($data_final) == 0 and strlen($os_troca_especifica) == 0) {
		$msg_erro = "Data Inválida";
	}

	//Início Validação de Datas
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$xdata_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final);//tira a barra
		$xdata_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($xdata_final < $xdata_inicial){
			$msg_erro = "Data Inválida.";
		}

		//Fim Validação de Datas
	}

}

$aprova        = trim($_POST['aprova']);
$interno_posto = trim($_POST['interno_posto']);


//LEGENDAS hd 14631
echo "<br>";
echo "<table border='0' cellspacing='0' cellpadding='0' align='center' width='700'>";
echo "<tr height='18'>";
echo "<td nowrap width='18' >";
echo "<span style='background-color:#FDEBD0;color:#FDEBD0;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com origem da Intervenção</td>";

echo "<td nowrap width='18' >";
echo "<span style='background-color:#99FF66;color:#00FF00;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  OS com Observação</td>";

echo "<td nowrap width='18' >";
echo "<span style='background-color:#CCFFFF; color:#D7FFE1;border:1px solid #F8B652'>__</span></td>";
echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</td>";
echo "</tr>";
echo "</table>";
echo "<br>";

?>


<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?php if(strlen($msg_erro)>0){ ?>
		<tr class="msg_erro"><td colspan="<?=($login_fabrica != 1) ? '4':'6'?>"><?php echo $msg_erro; ?></td></tr>
<?php } ?>
	<tr class="titulo_tabela" height="20">
			<td colspan="<?=($login_fabrica != 1) ? '4':'6'?>">Parâmetros de Pesquisa</td>
	</tr>

	<tr>
		<td colspan="<?=($login_fabrica != 1) ? '4':'6'?>">&nbsp;</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>
		<td align='left' width="125">Data Inicial</td>
        <td align='left'>Data Final</td>

		<td>&nbsp;</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>

		<td align='left' width="125">
			<input type="text" name="data_inicial" id="data_inicial" size="11" value="<? echo $data_inicial ?>" class="frm">
		</td>

		<td align='left'>
			<input type="text" name="data_final" id="data_final" size="11" value="<? echo $data_final ?>" class="frm">
		</td>

        <td>&nbsp;</td>


	</tr>

</table>
<?
if($login_fabrica == 1){
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td width="100">&nbsp;</td>
        <td align='left'>Motivo Troca</td>
        <td align='left'>Linha</td>
        <td align='left'>Marca</td>
        <td width="100">&nbsp;</td>
    </tr>
    <tr>
        <td width="100">&nbsp;</td>
        <td >
            <select name="causa_troca" size="1" style='width:200px; height=18px;' onchange='mostraObs(this)' class="frm">
                <option selected></option>
<?
                        $sql = "SELECT causa_troca,descricao
                                FROM tbl_causa_troca
                                WHERE fabrica = $login_fabrica
                                AND   tipo in ('T','R')
                                AND   ativo
                                ORDER BY descricao";
                        $res = pg_query ($con,$sql) ;
                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                            echo "<option ";
                            if ($causa_troca == pg_fetch_result ($res,$i,causa_troca) ) echo " selected ";
                            echo " value='" . pg_fetch_result ($res,$i,causa_troca) . "'>" ;
                            echo pg_fetch_result ($res,$i,descricao) ;
                            echo "</option>";
                        }
?>
            </select>
        </td>
        <td>
            <select name='linha' size='1' class='frm'>
                <option value=''>&nbsp;</option>
<?
        $sql = "SELECT
                            linha,
                            nome
                    FROM tbl_linha
                    WHERE tbl_linha.fabrica = $login_fabrica
                    ORDER BY tbl_linha.nome ";
        $res_linha = pg_query($con, $sql);

        if (pg_num_rows($res_linha) > 0) {
            for ($j = 0 ; $j < pg_num_rows($res_linha) ; $j++){
                $aux_linha    = trim(pg_fetch_result($res_linha,$j,linha));
                $aux_descricao  = trim(pg_fetch_result($res_linha,$j,nome));
?>
                <option value = "<?=$aux_linha?>" <?=($linha == $aux_linha) ? " SELECTED " : ""?>><?=$aux_descricao?></option>
<?
            }
        }
?>
            </select>
        </td>
        <td>
            <select name="marca" class="frm">
                <option value=''>&nbsp;</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
            </select>
        </td>
        <td width="100">&nbsp;</td>
    </tr>
</table>
<?
}
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

	<tr>
		<td width="100">&nbsp;</td>

		<td align='left' width="125">OS Troca Específica</td>

		<td align='left'>Modelo Produto</td>
		<td >&nbsp;</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>
		<td align='left' width="125">
			<input type="text" name="os_troca_especifica" id="os_troca_especifica" size="13" value="<?echo $os_troca_especifica?>" class="frm">
		</td>
		<td align='left'>
			<input type="text" name="modelo_produto" id="modelo_produto" size="50" value="<?echo $modelo_produto?>" class="frm">
		</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>
		<td>Código do Posto</td>
		<td>Razão Social do Posto</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td width="100">&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" id="posto_codigo" size="10" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: pointer;">
		</td>
		<td>
			<input type="text" name="posto_nome" id="posto_nome" size="50" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: pointer;">
		</td>
		<td>&nbsp;</td>
	</tr>

	<tr><td  colspan='4'>&nbsp;</td></tr>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<tr>
	<td width="85">&nbsp;</td>
	<td  style="size: 10px" >
	<fieldset>
		<legend>Tipo de Troca</legend>
		<table width="100%">
			<tr>
				<td width="210">
					<INPUT TYPE="radio" NAME="interno_posto" value='interno' <? if(trim($interno_posto) == 'interno') echo "checked='checked'"; ?>>Troca Interna
				</td>
				<td>
					<INPUT TYPE="radio" NAME="interno_posto" value='posto' <? if(trim($interno_posto) == 'posto') echo "checked='checked'"; ?>>Troca de Posto
				</td>
			</tr>
		</table>
	</fieldset>
	</td>
	<td >&nbsp;</td>
</tr>
<tr><td  colspan='4'>&nbsp;</td></tr>
<tr >
	<td >&nbsp;</td>
	<td  style="size: 10px" >
	<fieldset>
		<legend>Status da OS</legend>
		<table width="100%">
			<tr>
				<td>
					<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' ) echo "checked='checked'"; ?>>Em aprovação
					<div id='mostrar_filtro' style='display: block; margin-left:40px;'>
						<INPUT TYPE="radio" NAME="troca" value='faturada' <? if(trim($troca) == 'faturada') echo "checked='checked'"; ?>>Faturadas<BR>
						<INPUT TYPE="radio" NAME="troca" value='garantia' <? if(trim($troca) == 'garantia') echo "checked='checked'"; ?>>Garantias
					</div>
				</td>

				<td valign="top">
					<INPUT TYPE="radio" NAME="aprova" value='aprovadas_com_nf' <? if(trim($aprova) == 'aprovadas_com_nf') echo "checked='checked'"; ?>>Aprovadas(com número de NF)
				</td>
			</tr>

			<tr>
				<td >
					<INPUT TYPE="radio" NAME="aprova" value='aprovadas_sem_pedido' <? if(trim($aprova) == 'aprovadas_sem_pedido') echo "checked='checked'"; ?>>Aprovadas(sem pedido)
				</td>
				<td>
					<INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas(com pedido)
				</td>
			</tr>

			<tr>
				<td>
					<INPUT TYPE="radio" NAME="aprova" value='excluida' <? if(trim($aprova) == 'excluida') echo "checked='checked'"; ?>>Excluída
				</td>
				<td>
					<INPUT TYPE="radio" NAME="aprova" value='recusada' <? if(trim($aprova) == 'recusada') echo "checked='checked'"; ?>>Recusada
				</td>
			</tr>
		</table>

	</fieldset>
	</td>
	<td width="65">&nbsp;</td>
</tr>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<tr align='left'>
		<td colspan='4'>&nbsp;</td>
</tr>
<tr align='center'>
		<td colspan='4' align='center'><input type="submit" name="btn_acao" value="Pesquisar"></td>
</tr>
</table>
</form>


<?
if (strlen($msg_erro) == 0 AND $btn_acao == 'Pesquisar') {
	//$aprova = $_POST['aprova'];
	$cond_status = " 1=1 ";
	$cond_posto  = " 1=1 ";
    $cond_interno = " 1=1 ";
	$cond_motivo = " 1=1 ";
	$codigo_posto = trim($_POST['posto_codigo']);

	if(strlen($posto_codigo) > 0){
		$cond_posto = "  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
						AND tbl_posto_fabrica.fabrica = $login_fabrica ";
	}else if(strlen($posto_codigo) == 0 AND strlen($os_troca_especifica) > 0 ){
		$posto_codigo = substr($os_troca_especifica, 0, 5);

		$cond_posto .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'
		AND tbl_posto_fabrica.fabrica = $login_fabrica ";
	}

	if(strlen($aprova) > 0){

		if($aprova == "aprovadas"){
			$cond_status="       tbl_os_troca.status_os = 19 and tbl_os_status.observacao ~ 'Aprovada'
					 AND (tbl_os_troca.ri IS NOT NULL OR tbl_os_item.pedido IS NOT NULL)
					 AND (
							(tbl_os.nota_fiscal_saida IS NULL OR tbl_os.data_nf_saida IS NULL)
							AND
							(tbl_os_item_nf.nota_fiscal IS NULL OR tbl_os_item_nf.data_nf IS NULL)
						)";
		}

		if($aprova == "aprovadas_sem_pedido"){
			$cond_status="       tbl_os_troca.status_os = 19 and tbl_os_status.observacao ~ 'Aprovada'
							 AND tbl_os_troca.ri       IS NULL
							 AND tbl_os_item.pedido    IS NULL
							 AND ((tbl_os.nota_fiscal_saida IS NULL) OR (tbl_os.data_nf_saida IS NULL))";
		}

		if($aprova =="aprovacao"){
			$cond_status="  tbl_os_troca.status_os IS NULL
							AND (
								(tbl_os.nota_fiscal_saida IS NULL OR tbl_os.data_nf_saida IS NULL)
								AND
								(tbl_os_item_nf.nota_fiscal IS NULL OR tbl_os_item_nf.data_nf IS NULL)
								)";
		}

		if($aprova =="aprovacao" AND $troca =="garantia"){ //HD 75737
			$cond_troca = " AND tbl_os.tipo_atendimento = 17";
		}

		if($aprova =="aprovacao" AND $troca =="faturada"){ //HD 75737
			$cond_troca = " AND tbl_os.tipo_atendimento = 18";
		}


		if($aprova == "aprovadas_com_nf"){
			$cond_status="       tbl_os_troca.status_os = 19 and tbl_os_status.observacao ~ 'Aprovada'
							 AND (
								(tbl_os_troca.ri IS NOT NULL AND tbl_os.nota_fiscal_saida  IS NOT NULL AND tbl_os.data_nf_saida IS NOT NULL )
								OR
								(tbl_os_item_nf.nota_fiscal IS NOT NULL AND tbl_os_item_nf.data_nf IS NOT NULL )
								)";
		}

		if($aprova == "excluida"){
			//status_os =15 OS excluída pelo fabricante
			//status_os =96 OS excluída pelo posto
			$cond_status="  (tbl_os_troca.status_os = 15 or tbl_os_troca.status_os=96)";
		}

		if($aprova == "recusada"){
			$cond_status="  tbl_os_troca.status_os = 13";
		}
	}

	if(strlen($interno_posto) >0 ){
		if($interno_posto == 'interno'){
			$cond_interno = "  tbl_os.admin is not null ";
		}else{
			$cond_interno = "  tbl_os.admin is null ";
		}
	}

	if(strlen($os_troca_especifica) > 0){
		if ($login_fabrica == 1) {
			$pos = strpos($os_troca_especifica, "-");
			if ($pos === false) {
				$pos = strlen($os_troca_especifica) - 5;
			}else{
				$pos = $pos - 5;
			}
			$os_troca_especifica = substr($os_troca_especifica, $pos,strlen($os_troca_especifica));
		}
		$os_troca_especifica = trim (strtoupper ($os_troca_especifica));

		$cond_os_troca_especifica .= " AND tbl_os.sua_os = '$os_troca_especifica'";
	}

    if(strlen($modelo_produto) > 0){ //HD 75737
        $cond_modelo = " AND tbl_produto.referencia = '$modelo_produto'";
    }

    if(strlen($linha) > 0){
        $cond_linha = " AND tbl_produto.linha = $linha";
    }

	if(strlen($marca) > 0){
		$cond_marca = " AND tbl_produto.marca = $marca";
	}

	if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
		$cond_data = "AND tbl_os.data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
	}
	if(strlen($causa_troca) > 0){
        	$cond_motivo = " tbl_os_troca.causa_troca = $causa_troca ";
	}

    if ($login_fabrica == '1') {
        $format_data_aprovacao = 'DD/MM/YYYY HH24:MI';
    } else {
        $format_data_aprovacao = 'DD/MM/YYYY';
    }

    if($login_fabrica == 1){
        $campos_black  = "tbl_os.consumidor_revenda , tbl_os.rg_produto, ";
    }

	//hD 14932 mudou a busca de data abertura pela data de digitação
	$sql = "SELECT  DISTINCT tbl_os.os                                         ,
					tbl_os.sua_os                                              ,
					tbl_os.consumidor_nome                                     ,
                    $campos_black
					tbl_os.os_reincidente               AS reincidencia        ,
					tbl_posto.nome AS posto_nome                               ,
					tbl_posto_fabrica.codigo_posto                             ,
					tbl_posto_fabrica.contato_estado                           ,
					tbl_admin.login AS admin_nome                              ,
					tbl_produto.descricao                                      ,
					tbl_produto.referencia AS produto_referencia               ,
					tbl_produto.voltagem                                       ,
					tbl_tipo_atendimento.descricao AS tipo_atendimento         ,
					tbl_status_os.descricao AS status                          ,
					tbl_os_troca.total_troca                                   ,
					tbl_os_status.admin AS admin_manutencao                    ,
                    			tbl_os_troca.observacao AS observacao                     ,
					tbl_causa_troca.descricao AS causa_troca_descricao        ,
					to_char(tbl_os_status.data,'{$format_data_aprovacao}') as data_avaliacao ,
					to_char(tbl_os.data_digitacao,'DD/MM/YY') as data_digitacao,
					tbl_os_troca.ri                                            ,
					tbl_os.nota_fiscal_saida                                   ,
					tbl_os_item_nf.nota_fiscal                    AS nota_fiscal,
					to_char(tbl_os_item_nf.data_nf,'DD/MM/YYYY')  AS data_nf    ,
					case when tbl_pedido.pedido_blackedecker > 99999 then
							lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
						else
							lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
						end                                     AS pedido_os_item,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') as data_nf_saida ,
					tbl_os.excluida,
                    tbl_os.nota_fiscal as nota_fiscal_os,
                    tbl_os.prateleira_box
				FROM tbl_os_troca
				JOIN tbl_os               ON tbl_os.os               = tbl_os_troca.os
				LEFT JOIN tbl_os_produto  ON tbl_os_produto.os       = tbl_os.os
				LEFT JOIN tbl_os_item     ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
				LEFT JOIN tbl_os_item_nf  ON tbl_os_item_nf.os_item  = tbl_os_item.os_item
				LEFT JOIN tbl_pedido      ON tbl_pedido.pedido       = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
				LEFT JOIN tbl_causa_troca ON tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca
				JOIN tbl_produto          ON tbl_produto.produto     = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
				JOIN tbl_posto            ON tbl_os.posto            = tbl_posto.posto
				JOIN tbl_posto_fabrica    ON tbl_posto.posto         = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_admin       ON tbl_admin.admin         = tbl_os.admin
				JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os_troca.situacao_atendimento
				LEFT join tbl_status_os   ON tbl_status_os.status_os = tbl_os_troca.status_os
				LEFT JOIN tbl_os_status   ON tbl_os_status.os        = tbl_os.os  and tbl_os_status.status_os = tbl_os_troca.status_os and tbl_os_status.fabrica_status = $login_fabrica and tbl_os_status.observacao !~'liberada para Troca'
				WHERE tbl_os_troca.fabric = $login_fabrica
				AND   tbl_os.fabrica      = $login_fabrica
				AND $cond_posto
				AND $cond_status
				$cond_troca
				$cond_status_ap
				AND $cond_motivo
				AND $cond_interno
				$cond_os_troca_especifica
				$cond_data
                $cond_modelo
                $cond_linha
				$cond_marca
				ORDER BY tbl_posto_fabrica.codigo_posto asc,tbl_os.os asc	";
flush();
//echo nl2br($sql);exit;
	$res = pg_exec($con,$sql);
	$qtde_os = pg_numrows($res);
	if($qtde_os>0){


		echo "<BR><BR><table width='700' border='0' align='center' cellpadding='3' cellspacing='1'class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>OS</td>";
		echo "<td>Código do Posto</td>";
		echo "<td>Razão Social</td>";
		echo "<td>UF</td>";
		echo "<td>Produto</td>";
		echo "<td>Volt.</td>";
		echo "<td>Valor total</td>";
		echo "<td>Digitado por:</td>";
        echo "<td>Avaliado por:</td>";
		if($login_fabrica == 1){
            echo "<td>Motivo Troca</td>";
		}
		if(trim($aprova) =='excluida'){
			echo "<td>Excluído por:</td>";
		}

		if ($login_fabrica == 1){
			echo "<td>NF</td>";

		}

		if(trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' or trim($aprova) == 'aprovadas_com_nf'){
			echo "<td>Pedido</td>";

			if ($login_fabrica == 1) {
				echo "<td>NF Saida</td>";
				echo "<td>Data NF Saida</td>";
			} else {
				echo "<td>NF</td>";
				echo "<td>Data NF</td>";
			}
		}

		echo "<td>Clas. da OS:</td>";
		echo "<td>Data <br>Digitação</td>";
		echo "<td>Data Aprovação</td>";
		echo "<td>Obs. Posto:</td>";
		if ( ($login_fabrica == 1) AND ($_POST['interno_posto'] == "interno" )AND ( $_POST['aprova'] == "recusada" )){
			echo "<td>Ação</td>";
		}
		echo "</tr>";
		$cores = '';
		for ($x=0; $x<$qtde_os;$x++){

			$os						= pg_result($res, $x, os);
			$sua_os					= pg_result($res, $x, sua_os);
			$reincidencia			= pg_result($res, $x, reincidencia);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$consumidor_nome		= pg_result($res, $x, consumidor_nome);
            if($login_fabrica == 1){
                $consumidor_revenda     = pg_result($res, $x, consumidor_revenda);
                $rg_produto             = pg_result($res, $x, rg_produto);
            }            
            $tipo_atendimento		= pg_result($res, $x, tipo_atendimento);
            $valor_pago             = pg_result($res, $x, total_troca);
			$causa_troca_descricao  = pg_result($res, $x, causa_troca_descricao);
			if(strlen($valor_pago)==0) $valor_pago = "0";

			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$posto_nome				= pg_result($res, $x, posto_nome);
			$posto_estado			= pg_result($res, $x,contato_estado);
			$admin_nome				= pg_result($res, $x, admin_nome);
			$data_avaliacao			= pg_result($res, $x, data_avaliacao);

			$admin_manutencao		= pg_result($res, $x, admin_manutencao);
			$status                 = pg_result($res, $x, status);
			$observacao             = pg_result($res, $x, observacao);
            $data_digitacao			= pg_result($res, $x, data_digitacao);

            $prateleira_box = pg_fetch_result($res, $x, 'prateleira_box');

            /* hd_chamado=3115682 */
            if($login_fabrica == 1){
                if($os_anterior === $os){
                    $qtde_os_anterior[] = $x;
                    continue;
                }
                $os_anterior = $os;
            }
            /* FIM hd_chamado=3115682 */
			if(trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' or trim($aprova) == 'aprovadas_com_nf'){
				$nota_fiscal_saida	= pg_result($res, $x, nota_fiscal_saida);
				$data_nf_saida		= pg_result($res, $x, data_nf_saida);
				$nota_fiscal		= pg_result($res, $x, nota_fiscal);
				$data_nf			= pg_result($res, $x, data_nf);
				$pedido				= pg_result($res, $x, ri);
				$pedido_os_item		= pg_result($res, $x, pedido_os_item);

				if(strlen($pedido==0) AND strlen($pedido_os_item>0)){//hd 21142 17/6/2008
					$pedido = $pedido_os_item;
				}

				if (strlen($nota_fiscal_saida)==0){
					$nota_fiscal_saida = $nota_fiscal;
				}
				if (strlen($data_nf_saida)==0){
					$data_nf_saida = $data_nf;
				}
			}
			if ($login_fabrica == 1){
				$nota_fiscal_os                 = pg_result($res, $x, nota_fiscal_os);
				$nf_saida			= pg_result($res, $x, nf_saida);
				$data_nf_saida		= pg_result($res, $x, data_nf_saida);
			}
			$excluida			= pg_result($res, $x, excluida);
			// hd 18827
			if($excluida=='t' and trim($aprova) =='excluida'){
				$xsql = "SELECT tbl_admin.login from tbl_admin JOIN tbl_os ON tbl_os.admin_excluida=tbl_admin.admin where os=$os";

				$xres = pg_exec($con,$xsql);
				if(pg_numrows($xres)>0){
					$admin_excluida = pg_result($xres,0,0);
				}else{
					$admin_excluida="POSTO";
				}
			}
			if(strlen($admin_manutencao)>0){
				$xsql = "SELECT tbl_admin.login from tbl_admin where admin= $admin_manutencao";
				$xres = pg_exec($con,$xsql);
				if(pg_numrows($xres)>0){
					$admin_manutencao = pg_result($xres,0,0);

				}
			}
			$cores++;
			$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';

			if(strlen($observacao)> 0){
				$cor = "#99FF66";
			}

			if ($reincidencia =='t') $cor = "#CCFFFF";

			$sql_int = "SELECT status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (62,64,65,72,73,87,88)
						ORDER BY data DESC LIMIT 1";
			$resInt = pg_exec($con,$sql_int);
			if (pg_numrows($resInt)>0){
				$status_intervencao = pg_result($resInt, 0, status_os);
				# Se for 87, saiu da intervencao e veio para a TROCA
				if ($status_intervencao == "87" or $status_intervencao == "88"){
					$cor = "#FDEBD0";
					$qtde_intervencao++;
				}
			}

			echo "<tr bgcolor='$cor' id='linha_$x'>";

			echo "<td  nowrap >";
			if($aprova<>'excluida'){
				echo "<a href='os_press.php?os=$os'  target='_blank'>";
			}
			echo "$codigo_posto$sua_os";
			if($aprova<>'excluida'){
				echo "</a>";
			}
			echo "</td>";
			echo "<td align='left'  nowrap>".$codigo_posto."</td>";
			echo "<td align='left'  nowrap><acronym title='Posto: $posto_nome' style='cursor: help'>".$posto_nome ."</acronym></td>";
			echo "<td align='left'  nowrap>".$posto_estado ."</acronym></td>";
			echo "<td align='left' ><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help' nowrap>". $produto_referencia ."</acronym></td>";
			echo "<td >$produto_voltagem</td>";
			echo "<td align='center' nowrap >R$". number_format($valor_pago, 2, ',', ' ') ."</td>";
			echo "<td >"; if(strlen($admin_nome) > 0) {echo "$admin_nome";}else{echo "Posto";} echo "</td>";
			echo "<td >$admin_manutencao</td>";

            if($login_fabrica == 1){
                if($consumidor_revenda == "R" AND $rg_produto == 'indispl'){
                    $prateleira_box = $rg_produto;
                }
                if (!empty($prateleira_box)) {
                    if ($prateleira_box == 'fale') {
                        $prateleira_box .= ' Conosco';
                    } elseif ($prateleira_box == 'reclame') {
                        $prateleira_box .= ' Aqui';
                    }
                    $causa_troca_descricao .= '-' . ucfirst($prateleira_box);
                }
                echo "<td>$causa_troca_descricao</td>";
			}
			if(trim($aprova) =='excluida'){
				echo "<td >".$admin_excluida. "</td>";
			}

			if ($login_fabrica == 1) {
				echo "<td>$nota_fiscal_os</td>";
			}

			if(trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' or trim($aprova) == 'aprovadas_com_nf'){
				echo "<td >$pedido</td>";
				echo "<td >$nota_fiscal_saida</td>";
				echo "<td >$data_nf_saida</td>";
			}


			echo "<td align='left' nowrap>";
			echo "$tipo_atendimento";
			echo "</td>";
			echo "<td align='center' nowrap>$data_digitacao</td>";
			echo "<td >$data_avaliacao</td>";
			echo "<td><a href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">VER</a>";
			echo "</td>";
			if ( ($login_fabrica == 1) AND ($_POST['interno_posto'] == "interno" )AND ( $_POST['aprova'] == "recusada" )){
					echo "<td><a href='os_cadastro_troca_black.php?os={$os}' >Alterar</a></td>";
			}
			echo "</tr>";
		}

		echo "</table>";

		if($login_fabrica == 1){/* hd_chamado=3115682 */
            $qtde_anterior = count($qtde_os_anterior);
            $qtde_anterior = $qtde_os - $qtde_anterior;
            echo "<p>OS encontradas: $qtde_anterior </p>";
        }else{
            echo "<p>OS encontradas: $qtde_os</p>";
        }

	}else{
		echo "<center>Não foram encontrados resultados para esta pesquisa</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
