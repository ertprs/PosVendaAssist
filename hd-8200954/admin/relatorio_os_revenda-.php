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

<DIV ID="container" style="width: 100%; ">

<br>
<? if(strlen($msg_erro)>0){?>
		<TABLE width="400" align="center" border="2" cellspacing="0" cellpadding="2">
		<TR>
		<TD class="menu_top" align="center"><? echo "$msg_erro"; ?></TD>
		</TR>
		</table>

<? } ?>
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<TABLE width="400" align="center" border="2" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="3" class="menu_top"><div align="center"><b>Pesquisa por
Intervalo entre Datas</b></div></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'>Data Inicial</TD>
	<TD class="table_line" align='left' colspan='2'>Data Final</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'><INPUT size="10"
maxlength="10" TYPE="text" NAME="data_inicial_01" value='dd/mm/aaaa'
onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif"
align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')"
style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" align='left' colspan=2><INPUT size="10"
maxlength="10" TYPE="text" NAME="data_final_01" value='dd/mm/aaaa'
onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif"
align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')"
style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
</TR>
<TR>
	<TD colspan="3" class="table_line"><hr color='#eeeeee'></TD>
</TR>
		<TR>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
		<TD class="table_line" colspan='2'>Nome da revenda</TD>
		</TR>
		<TR>
		<TD class="table_line" style="text-align: center;">&nbsp;</TD>
		<TD colspan='2' class="table_line" style="text-align:
left;"><INPUT TYPE="text" NAME="nome_revenda" size="40"></TD>


</TR>
<TR>
	<TD colspan="3" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD colspan="3" class="table_line" style="text-align: left;">
		<IMG src="imagens_admin/btn_pesquisar_400.gif"
onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as
opções e clique aqui para pesquisar"></TD>
</TR>

</TABLE>
</FORM>
<BR>

</div>
<?
	$revenda = $_POST["nome_revenda"];
	$data_inicial = $_POST['data_inicial_01'];
	$data_final   = $_POST['data_final_01'];
	
	if (strlen($revenda)==0) {$erro .= "Favor informe a revenda<br>";}
	if (strlen($data_inicial)==0) {$erro .= "Favor informe data inicial<br>";}
	if (strlen($data_final)==0) {$erro .= "Favor informe data final<br>";}
	$data_inicial = str_replace (" " , "" , $data_inicial);
	$data_inicial = str_replace ("-" , "" , $data_inicial);
	$data_inicial = str_replace ("/" , "" , $data_inicial);
	$data_inicial = str_replace ("." , "" , $data_inicial);
	
	$data_final = str_replace (" " , "" , $data_final);
	$data_final = str_replace ("-" , "" , $data_final);
	$data_final = str_replace ("/" , "" , $data_final);
	$data_final = str_replace ("." , "" , $data_final);
		
	if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) .
"20" .			substr ($data_inicial,4,2);
	if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) .
"20" .			substr ($data_final  ,4,2);
	
	if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/"
.			substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
	if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/"
.	substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr
		($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) .
"-" . substr ($data_final,0,2);

	
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
				and tbl_os.revenda_nome ilike '%$revenda%' 
				and tbl_os.data_abertura BETWEEN '$x_data_inicial 00:00:00' AND
		'$x_data_final 23:59:59' order by tbl_os.revenda_nome";
			//echo "$sql";
			$res=pg_exec($con, $sql);
		if(pg_numrows($res) > 0){
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
			$posto_nome				= pg_result($res,$a,nome);
			$sua_os				= pg_result($res,$a,sua_os);
			$os				= pg_result($res,$a,os);
			$abertura				= pg_result($res,$a,abertura);
			$produto_descricao				= pg_result($res,$a,descricao);
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
		?>
		
		
		
		
<? include "rodape.php" ?>