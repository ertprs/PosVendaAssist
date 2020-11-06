<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica <> 6) {
	header ("Location: menu_callcenter.php");
	exit;
}
$ajax =  $_GET['ajax'];
if(strlen($ajax)>0){
	$pedido = $_GET['pedido'];
	$sql =	"SELECT tbl_pedido.pedido                                , 
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data    , 
				tbl_peca.referencia                              , 
				tbl_peca.descricao                               , 
				tbl_pedido_item.qtde                             , 
				tbl_posto.nome                                   , 
				tbl_posto_fabrica.codigo_posto                   , 
				tbl_pedido_item.pedido_item
		FROM tbl_pedido 
		JOIN tbl_pedido_item using(pedido) 
		JOIN tbl_peca using(peca) 
		JOIN tbl_posto on tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
		AND  tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_pedido.fabrica = $login_fabrica
		AND  tbl_pedido.finalizado notnull
		AND  tbl_pedido.exportado is null 
		AND  tbl_pedido.controle_exportacao is null
		AND  tbl_pedido.tipo_pedido = 4
		AND  tbl_pedido.pedido = $pedido
		order by codigo_posto, pedido, referencia";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		echo "<table border='0' cellpadding='2' cellspacing='1' width='500'  align='center' >\n";
		echo "<tr class='Titulo'>\n";
		echo "<td>Pedido</td>\n";
		echo "<td>Data</td>\n";
		echo "<td>Peça</td>\n";
		echo "<td>Qtde</td>\n";
		echo "</tr>\n";
		for($x=0;pg_numrows($res)>$x;$x++){
			$pedido               = trim(pg_result($res,$x,pedido));
			$data                 = trim(pg_result($res,$x,data));
			$peca_referencia      = trim(pg_result($res,$x,referencia));
			$peca_descricao       = trim(pg_result($res,$x,descricao));
			$qtde                 = trim(pg_result($res,$x,qtde));
			$posto_codigo         = trim(pg_result($res,$x,codigo_posto));
			$posto_nome           = trim(pg_result($res,$x,nome));
			$pedido_item          = trim(pg_result($res,$x,pedido_item));
			$cor = ($x % 2 == 0) ? "#d2d7e1" : "#efeeea";
			echo "<tr class='Conteudo' bgcolor='$cor'>\n";
			echo "<td><font size='1'>$pedido</font></td>\n";
			echo "<td><font size='1'>$data</font></td>\n";
			echo "<td><font size='1'>$peca_referencia - $peca_descricao</font></td>\n";
			echo "<td><font size='1'>$qtde</font></td>\n";
			echo "</tr>\n";
		}
		echo "</table>";
	}else{
		echo "Nenhum item encontrado";
	}
exit;
}
?>
<script language='javascript' src='ajax.js'></script>
<script language="JavaScript">
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
	
function mostraPedido(pedido){
	if (document.getElementById('dados_' + pedido)){
		var style2 = document.getElementById('dados_' + pedido); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaMovimentacao(pedido);
		}
	}
}
var http3 = new Array();
function retornaMovimentacao(pedido){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url ="libera_os_item_faturado2.php?ajax=true&pedido="+pedido;
	http3[curDateTime].open('get',url);
	
	var campo = document.getElementById('dados_'+pedido);

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}
</script>
<?

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];
if (strlen($_GET["btn_acao"]) > 0)  $btn_acao = $_GET["btn_acao"];

if (strtoupper($btn_acao) == "GRAVAR") {
	$qtde_os_item = $_POST['qtde_os_item'];
	if(strlen($qtde_os_item)>0){
		$pedido_anterior=0;
		for($x=0;$x<$qtde_os_item;$x++){
			$recusar       = $_POST['recusar_'.$x];
			$aceitar       = $_POST['os_item_'.$x];
			$pedido        = $_POST['pedido_' .$x];

			//	echo "$x =>$aceitar - $recusar <BR>";
		if(strlen($aceitar)>0){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
//			echo "Pedido $aceitar aceito<BR>";
			$sql = "UPDATE tbl_pedido set controle_exportacao = current_timestamp 
					where pedido = $pedido and fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
			//echo $sql;
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}

		}
		if(strlen($recusar)>0){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
//			echo "Pedido $recusar recusado<BR>";
			$sql = "DELETE from tbl_pedido where pedido = $pedido and fabrica = $login_fabrica;";
			//echo "$sql<BR>";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
	
			if (strlen($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}
			
			
		}
	}

}

$layout_menu = "gerencia";
$title = "Manutenção de Itens de OS para Pedidos";
include 'cabecalho.php';
?>

<style type='text/css'>
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
</style>
<script language="JavaScript">
function marcaLinha(id,cor){
	var elemento = document.getElementById(id);
	elemento.setAttribute('bgColor', cor);
}
</script>
<br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class='error'><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<?
$sql =	"SELECT tbl_pedido.pedido                                , 
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data    , 
				tbl_posto.nome                                   , 
				tbl_posto_fabrica.codigo_posto                   
		FROM tbl_pedido 
		JOIN tbl_posto on tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
		AND  tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_pedido.fabrica = $login_fabrica
		AND  tbl_pedido.finalizado notnull
		AND  tbl_pedido.exportado is null 
		AND  tbl_pedido.controle_exportacao is null
		AND  tbl_pedido.tipo_pedido = 4
		order by codigo_posto, pedido";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<form name='frm_os_item' method='post' action='$PHP_SELF'>\n";
	echo "<input type='hidden' name='btn_acao'>\n";
	echo "<input type='hidden' name='posto'>\n";
	
	echo "<table border='0' cellpadding='2' cellspacing='1' width='500'  align='center'>\n";
	
	$qtde_liberar_inicio = 0;
	$qtde_liberar_final  = 0;
	$cont = 0;

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$pedido               = trim(pg_result($res,$i,pedido));
		$data                 = trim(pg_result($res,$i,data));
		$posto_codigo         = trim(pg_result($res,$i,codigo_posto));
		$posto_nome           = trim(pg_result($res,$i,nome));
		$checked              = "";
		
		if ($posto_codigo_anterior != $posto_codigo) {
			if ($i != 0) {
				echo "<tr class='Conteudo' id = '$i'>\n";
				echo "<td>\n";
				echo "<script language=\"JavaScript\">\n";
				echo "var CheckFlagLiberar$cont = \"false\";\n";
				echo "function SelecionarLiberar$cont (campo, campo_inicio, campo_final) {\n";
				echo "if (CheckFlagLiberar$cont == \"false\") {\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagLiberar$cont = \"true\";\n";
				echo "return true;\n";
				echo "}else{\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagLiberar$cont = \"false\";\n";
				echo "return true;\n";
				echo "}\n";
				echo "}\n";
				echo "</script>\n";
				echo "<input type='checkbox' name='liberar' value='liberar' onclick=\"SelecionarLiberar$cont ('os_item', '$qtde_liberar_inicio', '$qtde_liberar_final'); \" class='frm'>";
				echo "</td>\n";

				echo "<td>\n";
				echo "<script language=\"JavaScript\">\n";
				echo "var CheckFlagRecusar$cont = \"false\";\n";
				echo "function SelecionarRecusar$cont (campo, campo_inicio, campo_final) {\n";
				echo "if (CheckFlagRecusar$cont == \"false\") {\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagRecusar$cont = \"true\";\n";
				echo "return true;\n";
				echo "}else{\n";
				echo "for (i = campo_inicio; i < campo_final; i++) {\n";
				echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
				echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
				echo "}\n";
				echo "}\n";
				echo "CheckFlagRecusar$cont = \"false\";\n";
				echo "return true;\n";
				echo "}\n";
				echo "}\n";
				echo "</script>\n";
				echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarRecusar$cont ('recusar', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>\n";
				echo "</td>\n";

				echo "<td colspan='4' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
				echo "</tr>\n";
				
				$qtde_liberar_inicio = $qtde_liberar_final;
				
				echo "<tr class='Conteudo'>\n";
				echo "<td colspan='4'>\n";
				
				echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto_anterior'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'>";
				echo "<br>";
				echo "<br>";
				echo "</td>\n";
				echo "</tr>\n";
				
				$cont++;
			}
			echo "<tr class='Titulo'>\n";
			echo "<td colspan='5'><b>Posto: $posto_codigo - $posto_nome</b></td>\n";
			echo "</tr>\n";
			
			echo "<tr class='Titulo'>\n";
			echo "<td>Liberar</td>\n";
			echo "<td>Recusar</td>\n";
			echo "<td>Pedido</td>\n";
			echo "<td>Data</td>\n";
			echo "<td>Detalhes</td>\n";
		//	echo "<td>Qtde</td>\n";
			echo "</tr>\n";
		}
		
		$cor = ($i % 2 == 0) ? "#d2d7e1" : "#efeeea";
		
		if ($diferenca > 30) $cor = "#FFCCCC";
		
		if ($peca_item_aparencia == "t" && $posto_item_aparencia == "t") $cor = "#D7FFE1";
		
		if (strlen($login) > 0) $cor = "#91C8FF";
		
//		if ($os_item == $_POST["os_item_".$i]) $checked = "checked";
		
		echo "<tr class='Conteudo' bgcolor='$cor' height='16' id='$i'>\n";
		
		echo "<td bgcolor='#4c664b'  align='center'>";
		echo "<input type='hidden' name='posto_$i' value='$posto'>\n";
		echo "<input type='hidden' name='pedido_$i' value='$pedido'>\n";
		echo "<input type='checkbox' name='os_item_$i' value='$pedido' class='frm' onclick=\"javascript:marcaLinha('$i','#4c664b');\">";
		echo "</td>\n";
		echo "<td bgcolor='#dcc6c6' align='center'><input type='checkbox' $checked name='recusar_$i' value='$pedido' class='frm' onclick=\"javascript:marcaLinha('$i','#dcc6c6');\"></td>\n";
		echo "<td nowrap align='center'><a href='pedido_cadastro.php?pedido=$pedido' target='_blank'>$pedido</a></td>\n";
		echo "<td nowrap align='center'>$data</td>\n";
		echo "<td nowrap align='center'>";
		echo "<a href=\"javascript:mostraPedido($pedido);\">Abrir</a>";
		echo "</td>\n";
	//	echo "<td nowrap align='right'><input type='hidden' name='qtde_real_$i' value='$qtde'><input type='text' size='2' maxlength='3' name='qtde_alterada_$i' value='$qtde'></td>\n";
		echo "</tr>\n";
		echo "<tr class='Conteudo' bgcolor='$cor'>\n";
		
		echo "<td colspan='5' >";
		echo "<div id='dados_$pedido' style='position:relative; display:none; border: 1px solid #949494;background-color: #b8b7af;width:490px;'></div>";
		echo "</td>\n";
		echo "</tr>\n";
		$qtde_liberar_final++;
		$posto_codigo_anterior = $posto_codigo;
		$posto_anterior = $posto;
	}
	echo "<tr class='Conteudo'>\n";
	echo "<td>";
	echo "<script language=\"JavaScript\">\n";
	echo "var CheckFlagLiberar$cont = \"false\";\n";
	echo "function SelecionarLiberar$cont (campo, campo_inicio, campo_final) {\n";
	echo "if (CheckFlagLiberar$cont == \"false\") {\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagLiberar$cont = \"true\";\n";
	echo "return true;\n";
	echo "}else{\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagLiberar$cont = \"false\";\n";
	echo "return true;\n";
	echo "}\n";
	echo "}\n";
	echo "</script>\n";
	echo "<input type='checkbox' name='liberar' value='liberar' onclick=\"SelecionarLiberar$cont ('os_item', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'>";
	echo "</td>\n";

	echo "<td>";
	echo "<script language=\"JavaScript\">\n";
	echo "var CheckFlagRecusar$cont = \"false\";\n";
	echo "function SelecionarRecusar$cont (campo, campo_inicio, campo_final) {\n";
	echo "if (CheckFlagRecusar$cont == \"false\") {\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = true;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagRecusar$cont = \"true\";\n";
	echo "return true;\n";
	echo "}else{\n";
	echo "for (i = campo_inicio; i < campo_final; i++) {\n";
	echo "if (document.frm_os_item.elements[campo + \"_\" + i].disabled == false) {";
	echo "document.frm_os_item.elements[campo + \"_\" + i].checked = false;\n";
	echo "}\n";
	echo "}\n";
	echo "CheckFlagRecusar$cont = \"false\";\n";
	echo "return true;\n";
	echo "}\n";
	echo "}\n";
	echo "</script>\n";
	echo "<input type='checkbox' name='recusar' value='recusar' onclick=\"SelecionarRecusar$cont ('recusar', '$qtde_liberar_inicio', '$qtde_liberar_final');\" class='frm'></td>\n";

	echo "<td colspan='2' align='left'>Clique no campo ao lado para selecionar todos</td>\n";
	echo "</tr>\n";
	echo "<tr class='Conteudo'>\n";
	echo "<td colspan='5'>";
	echo "<input type='hidden' name='qtde_os_item' value='" . pg_numrows($res) . "'>";
	//echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' style='cursor: hand;'>";
	echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os_item.btn_acao.value == '') { document.frm_os_item.btn_acao.value='GRAVAR'; document.frm_os_item.posto.value = '$posto'; document.frm_os_item.submit(); } else { alert('Aguarde submissão'); }\" ALT='Gravar' style='cursor: hand;'> ";

	echo "<br>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

}
?>

</form>

<? include "rodape.php"; ?>
