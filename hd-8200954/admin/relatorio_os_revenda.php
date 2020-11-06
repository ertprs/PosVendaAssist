<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Acompanhamento de OS´s de revenda";

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

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_pesquisa.nome_revenda;
	janela.cnpj			= document.frm_pesquisa.cnpj_revenda;
	janela.fone			= document.frm_pesquisa.revenda_fone;
	janela.cidade		= document.frm_pesquisa.revenda_cidade;
	janela.estado		= document.frm_pesquisa.revenda_estado;
	janela.endereco		= document.frm_pesquisa.revenda_endereco;
	janela.numero		= document.frm_pesquisa.revenda_numero;
	janela.complemento	= document.frm_pesquisa.revenda_complemento;
	janela.bairro		= document.frm_pesquisa.revenda_bairro;
	janela.cep			= document.frm_pesquisa.revenda_cep;
	janela.email		= document.frm_pesquisa.revenda_email;
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

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<br>
<? if(strlen($msg_erro)>0){
	echo "<TABLE width='400' align='center' border='2'
cellspacing='0' cellpadding='2'>";
		echo "<TR>";
		echo "<TD class='menu_top' align='center'>$msg_erro</TD>";
		echo "</TR>";
		echo "</table>";
}
echo "<FORM name='frm_pesquisa' METHOD='POST' ACTION=$PHP_SELF>";
echo "<TABLE width='400' align='center' border='0' cellspacing='0' cellpadding='2'>";
echo "<TR>";
echo "<TD colspan='3' class='menu_top' align='center'><b>Pesquisa de OS Revenda
aberta</b></TD>";
echo "</TR>";
	/*	echo "<TR>";
		echo "<TD class='table_line' style='width: 10px'>&nbsp;</TD>";
		echo "<TD class='table_line' align='left' colspan='2'><INPUT
TYPE='checkbox' NAME='ultimos_3_meses' value='1'>Relatório considerando os últimos 3
meses</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD colspan='3' class='table_line'><hr color='#eeeeee'></TD>";
		echo "</TR>";*/
		echo "<TR>";
		echo "<TD class='table_line' style='width: 10px'>&nbsp;</TD>";
		echo "<TD class='table_line' align='left'>Escolha o Mês</TD>";
		echo "<TD class='table_line' align='left' >Escolha o Ano</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='table_line' style='width: 10px'>&nbsp;</TD>";
		echo "<TD class='table_line' align='left'>";

function selectMesSimples($selectedMes){
	for($dtMes=1; $dtMes <= 12; $dtMes++){
	$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;
		
echo "<option value=$dtMesTrue ";
if ($selectedMes == $dtMesTrue) echo "selected";
		echo ">$dtMesTrue</option>\n";}
}
function selectAnoSimples($ant,$pos,$dif=0,$selectedAno){
	$startAno = date("Y"); // ano atual
	for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
	echo "<option value=$dtAno ";
		if ($selectedAno == $dtAno) echo "selected";
		echo ">$dtAno</option>\n";
	}
}
		echo "<select name='mes'>";
		echo "<option value=''></option>";
		selectMesSimples($mes);
		echo "</select>";
		echo "</td>";
		echo "<TD class='table_line' align='left'>";
		echo "<select name='ano'>";
		echo "<option value=''></option>";
		selectAnoSimples(1,0,'',$ano);
		echo "</select>";
		echo "</td>";
		echo "</tr>";
		echo "<TR>";
		echo "<TD colspan='3' class='table_line'><hr color='#eeeeee'></TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD width='19' class='table_line' style='text-align:
left;'>&nbsp;</TD>";
		echo "<TD class='table_line' colspan='2'>20 maiores revendas</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='table_line' style='text-align: center;'>&nbsp;</TD>";
		echo "<TD colspan='2' class='table_line' style='text-align:
		left;'>";
		//echo "<INPUT TYPE='text' NAME='nome_revenda' size='40'>";
		$sql ="SELECT 	substr (tbl_revenda.cnpj,1,8) as cnpj, 
						count(*) 
				FROM tbl_os
				JOIN tbl_revenda using(revenda) 
				WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_os.data_fechamento is null 
				GROUP BY substr(tbl_revenda.cnpj,1,8) 
				ORDER BY count desc limit 20";
		$res=pg_exec($con, $sql);
		echo "<select name='revenda' style='width: 300px;'>";
		for($x=0; $x<pg_numrows($res); $x++){
			$cnpj				= pg_result($res,$x,cnpj);
			$xsql="SELECT nome, substr(cnpj,1,8) as cnpj from tbl_revenda where cnpj like '$cnpj%'
limit 1";
			$xres = pg_exec($con,$xsql);
			$xcnpj				= pg_result($xres,0,cnpj);
//			$xcnpj				= substr($xcnpj, 0,7);
			$revenda_nome		= pg_result($xres,0,nome);
			echo "<option value='$xcnpj'>$revenda_nome</option>";
		}
		echo "</select>";
		echo "</TD>";
echo "</TR>";
echo "<TR>";
echo "<TD colspan='3' class='table_line'><hr color='#eeeeee'></TD>";
echo "</TR>";
echo "<TR>";
echo "<TD colspan='3' class='table_line' style='text-align: left;'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<center><img src='imagens/btn_continuar.gif' onclick=\"javascript: if
(document.frm_pesquisa.btn_acao.value == '' )
{ document.frm_pesquisa.btn_acao.value='continuar' ; document.frm_pesquisa.submit() }
else { alert ('Aguarde submissão') }\" ALT=\"Pesquisar\" border='0' style='cursor:
		pointer'></center></TD>";
echo "</TR>";
echo "</TABLE>";
echo "</FORM><BR>";

	$btn_acao= $_POST["btn_acao"];
	if(strlen($btn_acao)>0){
	$revenda = $_POST["revenda"];
// 	$data_inicial = $_POST['data_inicial_01'];
// 	$data_final   = $_POST['data_final_01'];
	$ano = $_POST["ano"];
	$mes = $_POST["mes"];
	$ultimos_3_meses = $_POST['ultimos_3_meses'];
	if (strlen($revenda)==0) {$erro .= "Favor informe a revenda<br>";}
		if (strlen($ano)==0) {$erro .= "Favor escolha o ano<br>";}
	if (strlen($mes)==0) {$erro .= "Favor escolha o mês<br>";}
	if (strlen($erro)==0) {
	//datas
	$data_ano = "$ano-01-01";
	$data     = "$ano-$mes-01";
	$sql = "SELECT fn_dias_mes('$data',0)";
	$resX = pg_exec($con,$sql);
	$data_inicial = pg_result($resX,0,0);

	$sql = "SELECT fn_dias_mes('$data',1)";
	$resX = pg_exec($con,$sql);
	$data_final = pg_result($resX,0,0);

	$sql = "SELECT fn_dias_mes('$data_ano',0)";
	$resX = pg_exec($con,$sql);
	$data_inicial_ano = pg_result($resX,0,0);
	}
	if (strlen($erro) == 0) {
			$sql="SELECT 	tbl_os.revenda_nome, 
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_os.sua_os,
							tbl_os.os,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
							tbl_produto.descricao
				FROM tbl_os
				JOIN tbl_posto on tbl_posto.posto=tbl_os.posto
				JOIN tbl_posto_fabrica on tbl_posto.posto=tbl_posto_fabrica.posto
					and tbl_posto_fabrica.fabrica=$login_fabrica 
				JOIN tbl_produto on tbl_produto.produto =tbl_os.produto
				where finalizada is null 
				and tbl_os.fabrica=$login_fabrica and tbl_os.consumidor_revenda='R' 
				and tbl_os.revenda_cnpj ilike '$revenda%' 
				and tbl_os.data_abertura BETWEEN '$data_inicial' AND
		'$data_final' order by tbl_os.revenda_nome";
//		echo "$sql";
			$res=pg_exec($con, $sql);
		if(pg_numrows($res) > 0){
		echo "<font face='verdana' size=2>Encontrada(s)
		".pg_numrows($res)." OS</font>";
		echo "<TABLE width='650' cellspacing='1' cellpadding='4' border='0'
		align='center' bgcolor='#485989'>";
		echo "<TR>";
		echo "<TD><font face='verdana' size=2 color='#FFFFFF'>Revenda</FONT></TD>";
		echo "<TD><font face='verdana' size=2 color='#FFFFFF'>Código
Posto</FONT></TD>";
		echo "<TD><font face='verdana' size=2 color='#FFFFFF'>Nome
Posto</FONT></TD>";
		echo "<TD><font face='verdana' size=2 color='#FFFFFF'>OS</FONT></TD>";
		echo "<TD><font face='verdana' size=2 color='#FFFFFF'>Data
Abertura</FONT></TD>";
		echo "<TD><font face='verdana' size=2 color='#FFFFFF'>Produto</FONT></TD>";
		echo "</TR>";
		for($a=0; $a<pg_numrows($res); $a++){
			$revenda_nome				= pg_result($res,$a,revenda_nome);
			$codigo_posto				= pg_result($res,$a,codigo_posto);
			$posto_nome					= pg_result($res,$a,nome);
			$sua_os						= pg_result($res,$a,sua_os);
			$os							= pg_result($res,$a,os);
			$abertura					= pg_result($res,$a,abertura);
			$produto_descricao			= pg_result($res,$a,descricao);
			$cor = '#ffffff';
			if ($a % 2 == 0) $cor = '#f4f4f4';
			echo "<TR>";
			echo "<TD align='left' bgcolor='$cor'><font face='verdana' size=1
		color='#000000'>$revenda_nome</font></TD>";
			echo "<TD bgcolor='$cor'><font face='verdana' size=1
		color='#000000'>$codigo_posto</font></TD>";
			echo "<TD align='left' bgcolor='$cor'><font face='verdana' size=1
		color='#000000'>$posto_nome</font></TD>";
			echo "<TD bgcolor='$cor'><font face='verdana' size=1
		color='#000000'><a href='os_press.php?os=$os'
		target='_blank'>$sua_os</a></font></TD>";
			echo "<TD bgcolor='$cor'><font face='verdana' size=1
		color='#000000'>$abertura</font></TD>";
			echo "<TD align='left' bgcolor='$cor'><font face='verdana' size=1
		color='#000000'>$produto_descricao</font></TD>";
			echo "</TR>";
		}
		echo "</table>";
		}
	}
	}
		?>
		
		
		
		
<? include "rodape.php" ?>