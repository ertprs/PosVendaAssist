<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Consulta de pesquisas realizadas";


$excluir = $_GET['excluir'];

if(strlen($excluir) > 0){

	$sql = "DELETE FROM tbl_posto_pesquisa_item WHERE posto_pesquisa = $excluir";
	$res = pg_exec($con,$sql);

	$sql = "DELETE FROM tbl_posto_pesquisa WHERE posto_pesquisa = $excluir";
	$res = pg_exec($con,$sql);


}



?>

<style type="text/css">

body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.fonte {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color: #999999;
	text-decoration: none;
}
.linkk {font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #999999;
	text-decoration: none;
}

.fontenormal {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #6A6A6A;
	text-decoration: none;
	font-style: normal;
	line-height: 23px;
	font-weight: normal;
}
.style1 {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #666666; text-decoration: none; }
.stylebordo {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #663300; text-decoration: none;
}
v\:* {      behavior:url(#default#VML);    }
</style>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script type="text/javascript" src="js/firebug.js"></script>
<script type="text/javascript" src="js/jquery-1.1.2.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">

<script type="text/javascript">

var http1 = new Array();
function mostraPostos(fabrica,estado){

	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();

	url = "pesquisa_acompanhamento.php?fabrica="+fabrica+"&estado="+estado;
	http1[curDateTime].open('get',url);

	var campo = document.getElementById('div_defeitos');
//alert(natureza);
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http1[curDateTime].readyState == 4){
			if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
				var results = http1[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";

			}
		}
	}
	http1[curDateTime].send(null);
}

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

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>


<!-- Valida formularios -->
<script language="javascript">
function fcn_valida_formDatas()
{
	f = document.frm_pesquisa1;
/*	if(f.data_inicial_01.value.length < 10)
	{
		alert('Digite a Data Inicial');
		f.data_inicial_01.focus();
		return false;
	}
	if(f.data_final_01.value.length < 10)
	{
		alert('Digite a Data Final');
		f.data_final_01.focus();
		return false;
	}
	if(f.codigo_posto.value == "")
	{
		alert('Digite o Código do Posto');
		f.codigo_posto.focus();
		return false;
	}
	if(f.nome_posto.value == "")
	{
		alert('Digite o Nome do Posto');
		f.nome_posto.focus();
		return false;
	}
*/
	f.submit();
}



</script>

<?
$layout_menu = "callcenter";
include 'cabecalho.php';


echo "<br><br>";
echo "<FORM METHOD=POST name='frm_pesquisa' ACTION='$PHP_SELF'>";
echo "<INPUT TYPE='hidden' NAME='posto' value='$posto'>";
echo "<table width='500' border='0' align='center' cellpadding='2' cellspacing='2' style='font-size:12px'>";
echo "<tr>";
	echo "<td aling='center'>";
	echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";

	echo "<tr>";
		echo "<td align='center' colspan='4'><strong>RELATÓRIO DE RECLAMAÇÃO</strong></td>";
	echo "</tr>";

	echo "<tr>";
		echo "<TD width='50'>&nbsp;</TD>";
		echo "<TD>Data Inicial<br><INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial_01' id='data_inicial_01'></TD>";
		echo "<TD>Data Final<br><INPUT size='12' maxlength='10' TYPE='text' NAME='data_final_01' id='data_final_01'></TD>";
		echo "<TD>&nbsp;</TD>";
	echo "</tr>";

	echo "<tr>";
		echo "<TD>&nbsp;</TD>";
		echo "<TD>";
			echo "Código do Posto<br><input type='text' name='posto_codigo' size='10' maxlength='20' value='$posto_codigo'>";
			echo "<img border='0' src='imagens_admin/btn_lupa.gif' style='cursor: hand;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick=\"javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')\">";
		echo "</TD>";
		echo "<TD>";
			echo "Nome do Posto<br><input type=text' name='posto_nome' size='25' maxlength='50'  value='$posto_nome'>";
			echo "<img border='0' src='imagens_admin/btn_lupa.gif' style='cursor: hand;1' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick=\"javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')\">";
		echo "</TD>";
		echo "<TD>&nbsp;</TD>";
	echo "</tr>";

	echo "<tr>";
		echo "<TD width='50'>&nbsp;</TD>";
		echo "<TD colspan='2'>Estado<br><select name='estado'>
					<option value=''    selected >TODOS OS ESTADOS</option>
					<option value='AC' >AC - Acre</option>
					<option value='AL' >AL - Alagoas</option>
					<option value='AM' >AM - Amazonas</option>
					<option value='AP' >AP - Amapá</option>
					<option value='BA' >BA - Bahia</option>
					<option value='CE' >CE - Ceará</option>
					<option value='DF' >DF - Distrito Federal</option>
					<option value='ES' >ES - Espírito Santo</option>
					<option value='GO' >GO - Goiás</option>
					<option value='MA' >MA - Maranhão</option>
					<option value='MG' >MG - Minas Gerais</option>
					<option value='MS' >MS - Mato Grosso do Sul</option>
					<option value='MT' >MT - Mato Grosso</option>
					<option value='PA' >PA - Pará</option>
					<option value='PB' >PB - Paraíba</option>
					<option value='PE' >PE - Pernambuco</option>
					<option value='PI' >PI - Piauí</option>
					<option value='PR' >PR - Paraná</option>
					<option value='RJ' >RJ - Rio de Janeiro</option>
					<option value='RN' >RN - Rio Grande do Norte</option>
					<option value='RO' >RO - Rondônia</option>
					<option value='RR' >RR - Roraima</option>
					<option value='RS' >RS - Rio Grande do Sul</option>
					<option value='SC' >SC - Santa Catarina</option>
					<option value='SE' >SE - Sergipe</option>
					<option value='SP' >SP - São Paulo</option>
					<option value='TO' >TO - Tocantins</option>
				</select>
			</TD>";
	echo "<TD>&nbsp;</TD>";
	echo "</tr>";

	echo "<tr>";
		echo "<TD width='50'>&nbsp;</TD>";
		echo "<TD colspan='2'>Atendente<br>";
		$sql = "SELECT login, admin FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo IS TRUE;";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			echo "<select name='atendente'>";
			echo "<option value='' >TODOS</option>";
			for($i=0;$i<pg_numrows($res);$i++){
				$atende_login    = pg_result($res,$i,login);
				$atende_admin    = pg_result($res,$i,admin);
				echo "<option value='$atende_admin' >$atende_login</option>";
			}
			echo "</select>";
		}
		echo "</TD>";
		echo "<TD>&nbsp;</TD>";
	echo "</tr>";

	echo "</table>";
	echo "</td>";

echo "</td>";
echo "<tr>";
echo "<td>";

	echo "<center>";
		echo "<INPUT TYPE='submit' name='consultar' value='Consultar'>";
	echo "</center>";

echo "</td>";
echo "</tr>";

echo "</td>";
echo "</tr>";
echo "</table>";
echo "</FORM>";
echo "<br>";

$btn_acao = $_POST['consultar'];

if($btn_acao == 'Consultar'){


	if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
	if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
	if($_POST['posto_codigo'])			$posto_codigo       = trim($_POST['posto_codigo']);
	if($_POST['estado'])				$estado             = trim($_POST['estado']);
	if($_POST['atendente'])				$xatendente         = trim($_POST['atendente']);
	if(strlen($data_inicial_01) > 0 AND strlen($data_final_01) > 0) {
		$data_inicial     = $data_inicial_01;
		$data_final       = $data_final_01;
		$sql_data = " AND (tbl_posto_pesquisa.data_cadastro::date BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
	}else{
		$sql_data = "";
	}

	if(strlen($posto_codigo) > 0){
		$sql_posto = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
	}else{
		$sql_posto = "";
	}

	if(strlen($estado) > 0){
		$sql_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
	}else{
		$sql_estado = "";
	}

	if(strlen($atendente) > 0){
		$sql_atendente = " AND tbl_posto_pesquisa.admin = '$xatendente' ";
	}else{
		$sql_atendente = "";
	}

	if(strlen($sql_data) == 0 AND strlen($sql_posto) == 0 AND strlen($sql_estado) == 0 AND strlen($sql_atendente) == 0){
		echo "<center><p style='font-size: 14px; color: #FF0000'><b>Especifique algum campo para a pesquisa</b></p></center>";;
	}else{
		$sql = "SELECT tbl_posto_fabrica.posto             ,
					tbl_posto.fone                         ,
					tbl_posto.nome                         ,
					tbl_posto_fabrica.contato_email        ,
					to_char(tbl_posto_pesquisa.data,'DD/MM/YYYY') as data_pesquisa,
					tbl_posto_pesquisa.posto_pesquisa      ,
					tbl_posto_pesquisa.data_cadastro       ,
					tbl_posto_pesquisa.contato             ,
					tbl_posto_pesquisa.linha_atende        ,
					tbl_admin.login    AS atendente
				FROM tbl_posto_pesquisa
				JOIN tbl_posto USING(posto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_admin.admin = tbl_posto_pesquisa.admin
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				$sql_data
				$sql_posto
				$sql_estado
				$sql_atendente
				;";
	#echo nl2br($sql);
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			if(strlen($posto_codigo) == 0){
				for($i=0;$i<pg_numrows($res);$i++){

					$posto          = pg_result($res,$i,posto);
					$posto_pesquisa = pg_result($res,$i,posto_pesquisa);
					$posto_nome     = pg_result($res,$i,nome);
					$contato        = pg_result($res,$i,contato);
					$linha_atende   = pg_result($res,$i,linha_atende);
					$posto_fone     = pg_result($res,$i,fone);
					$posto_email    = pg_result($res,$i,contato_email);
					$data_pesquisa  = pg_result($res,$i,data_pesquisa);
					$atendente      = pg_result($res,$i,atendente);


					echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='font-size:12px'>";
					echo "<tr>";
						echo "<td aling='center'>";
						echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";

						echo "<tr>";
							echo "<td align='center' colspan='4'><strong>POSTO AUTORIZADO</strong></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td nowrap width='80'>Razão Social:</td> ";
							echo "<td><b>$posto_nome</b></td>";
							echo "<td width='80'>Contato: </td>";
							echo "<td><b>$contato</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td>Telefone:</td>";
							echo "<td><b>$posto_fone</b></td>";
							echo "<td>Email:</td>";
							echo "<td><b>$posto_email</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td colspan='1' width='80'>Linha de Atendimento:</td>";
							echo "<td colspan='3'><b>$linha_atende</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td colspan='1'>Data:</td>";
							echo "<td colspan='1'><b>$data_pesquisa</b></td>";

							echo "<td colspan='1'>Atendente:</td>";
							echo "<td colspan='1'><b>$atendente</b></td>";
						echo "</tr>";

						echo "<tr>";
							echo "<td colspan='4'>&nbsp;</td>";
						echo "</tr>";


					$sql2 = "SELECT posto_pesquisa, titulo, descricao, seleciona
								FROM tbl_posto_pesquisa_item
								WHERE posto_pesquisa = $posto_pesquisa ;
							";
					$res2 = pg_exec($con,$sql2);

				if(pg_numrows($res2) > 0){
					for($j=0;$j<pg_numrows($res2);$j++){
						$seleciona        = pg_result($res2,$j,seleciona);
						if(strlen($descricao) == 0) {
							$descricao     = pg_result($res2,$j,descricao);
						}

						echo "<tr width='100%'>";
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo "></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						if(strlen(@pg_result($res2,$j+1,titulo)) > 0){
							$j++;
							$seleciona        = pg_result($res2,$j,seleciona);
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo "></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						}
						echo "</tr>";
					}
					echo "<tr>";
						echo "<td>Descrição</td> ";
						echo "<td colspan='3'><TEXTAREA NAME='descricao' ROWS='3' COLS='50'>"; if(strlen($descricao) > 0) { echo "$descricao"; } echo "</TEXTAREA></td>";
					echo "</tr>";
					$descricao = '';

					echo "<tr>";
						echo "<td colspan='4' align='center'><a href='$PHP_SELF?excluir=$posto_pesquisa'>EXCLUIR PESQUISA</a></td>";
					echo "</tr>";

					echo "</table>";
					}
				}
				echo "<center>";
				echo "<a href='acompanhamento_imprime.php?data_inicial=$data_inicial&data_final=$data_final&posto=$posto_codigo&estado=$estado&atendente=$xatendente' target='_blank'><img src='imagens/btn_imprimir.gif'></a>";
				echo "</center>";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
			}else{
				$cont = '0';
				for($i=0;$i<pg_numrows($res);$i++){

					$posto          = pg_result($res,$i,posto);
					$posto_pesquisa = pg_result($res,$i,posto_pesquisa);
					$posto_nome     = pg_result($res,$i,nome);
					$contato        = pg_result($res,$i,contato);
					$linha_atende   = pg_result($res,$i,linha_atende);
					$posto_fone     = pg_result($res,$i,fone);
					$posto_email    = pg_result($res,$i,contato_email);
					$data_pesquisa  = pg_result($res,$i,data_pesquisa);
					$atendente      = pg_result($res,$i,atendente);

					if($cont == '0'){
						$cont = '1';
						echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='font-size:12px'>";
						echo "<tr>";
							echo "<td aling='center'>";
							echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";

							echo "<tr>";
								echo "<td align='center' colspan='4'><strong>POSTO AUTORIZADO</strong></td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td nowrap width='80'>Razão Social:</td> ";
								echo "<td><b>$posto_nome</b></td>";
								echo "<td width='80'>&nbsp;</td>";
								echo "<td>&nbsp;</td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td>Telefone:</td>";
								echo "<td><b>$posto_fone</b></td>";
								echo "<td>Email:</td>";
								echo "<td><b>$posto_email</b></td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td colspan='1' width='80'>Linha de Atendimento:</td>";
								echo "<td colspan='3'><b>$linha_atende</b></td>";
							echo "</tr>";

							echo "<tr>";
								echo "<td colspan='1'>Atendente:</td>";
								echo "<td colspan='1'><b>$atendente</b></td>";

								echo "<td colspan='1'>&nbsp;</td>";
								echo "<td colspan='1'>&nbsp;</td>";
							echo "</tr>";
						echo "</table>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
					}

					echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";

					echo "<tr>";
						echo "<td nowrap width='80'>Contato:</td> ";
						echo "<td><b>$contato</b></td>";
						echo "<td colspan='1'>Data:</td>";
						echo "<td colspan='1'><b>$data_pesquisa</b></td>";
					echo "</tr>";



					$sql2 = "SELECT posto_pesquisa, titulo, descricao, seleciona, contato
								FROM tbl_posto_pesquisa_item
								JOIN tbl_posto_pesquisa USING(posto_pesquisa)
								WHERE posto_pesquisa = $posto_pesquisa ;
							";
					$res2 = pg_exec($con,$sql2);
	#echo nl2br($sql2);

				if(pg_numrows($res2) > 0){
					for($j=0;$j<pg_numrows($res2);$j++){
						$seleciona        = pg_result($res2,$j,seleciona);
						$contato          = pg_result($res2,$j,contato);
						if(strlen($descricao) == 0) {
							$descricao     = pg_result($res2,$j,descricao);
						}

						echo "<tr width='700'>";
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo "></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						if(strlen(@pg_result($res2,$j+1,titulo)) > 0){
							$j++;
							$seleciona        = pg_result($res2,$j,seleciona);
							echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto' "; if($seleciona == 't'){ echo "CHECKED";} echo "></td> ";
							echo "<td><b>". pg_result($res2,$j,titulo) ."</b></td>";
						}
						echo "</tr>";
					}
					echo "<tr>";
						echo "<td>Descrição</td> ";
						echo "<td colspan='3'><TEXTAREA NAME='descricao' ROWS='3' COLS='50'>"; if(strlen($descricao) > 0) { echo "$descricao"; } echo "</TEXTAREA></td>";
					echo "</tr>";
					$descricao = '';

					echo "<tr>";
						echo "<td colspan='4' align='center'><a href='$PHP_SELF?excluir=$posto_pesquisa'>EXCLUIR PESQUISA</a></td>";
					echo "</tr>";

					echo "</table>";
					}
					echo "<br>";
				}
				echo "<center>";
				echo "<a href='acompanhamento_imprime.php?data_inicial=$data_inicial&data_final=$data_final&posto=$posto_codigo&estado=$estado&atendente=$xatendente' target='_blank'><img src='imagens/btn_imprimir.gif'></a>";
				echo "</center>";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
			}

		}else{
			echo "<center><p style='font-size: 12px'>Nenhum resgistro encontrado!</p></center>";
		}
	}


}

echo "</td>";
echo "</tr>";
echo "</table>";
echo "<br>";
include "rodape.php";
?>