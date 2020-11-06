<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include "cabecalho.php";
if (strlen($_POST[data_ini])>0)$data_ini = $_POST[data_ini];
if (strlen($data_ini)<10)$msgerro = "Data Inválida".$data_ini;

if (strlen($_POST[data_fim])>0)$data_fim = $_POST[data_fim];
if (strlen($data_fim)<10)$msgerro = "Data Inválida".$data_fim;


if (strlen($_GET[listar]))$lista = $_GET[listar];
if ((strlen($data_ini)<10 or strlen($data_fim)<10) AND strlen($lista)>0){
	echo "<center><table width='700'><tr><td class='msg_erro'>".$msgerro."</td></tr></table></center>";
}
if(strlen($lista)>0){
	$data_listar = $data_fim;
	$data_listar = substr($data_listar,3,2);
	$data_compara = substr($data_ini,3,2);
	$data_compara = $data_listar - $data_compara;
	if($data_compara > 2 OR $data_compara < 0){
		$msgerro = "Não podem ser gerados relatórios com periodo maior que 2 meses.";
		echo "<center><table><tr><td bgcolor='red'><font color='white'><b>".$msgerro."</b></font></td></tr></table></center>";
	}
	$data_ini_x = substr($data_ini,6,4)."-".substr($data_ini,3,2)."-".substr($data_ini,0,2);
	$data_fim_x = substr($data_fim,6,4)."-".substr($data_fim,3,2)."-".substr($data_fim,0,2);
}

if(strlen($lista)>0 AND strlen($data_fim)>0 AND strlen($data_ini)>0 AND strlen($msgerro)<=0){

$sql = "SELECT tbl_produto.produto ,
tbl_produto.referencia_fabrica AS produto_referencia ,
tbl_produto.descricao AS produto_descricao ,
tbl_peca.peca ,
tbl_peca.referencia AS peca_referencia ,
tbl_peca.descricao AS peca_descricao ,
x.qtde ,
x.custo_peca
FROM (
SELECT produto, peca, SUM (qtde) AS qtde , custo_peca
FROM bi_os_item
WHERE bi_os_item.data_finalizada BETWEEN '$data_ini_x' AND '$data_fim_x'
AND bi_os_item.fabrica = 1
GROUP BY bi_os_item.peca , bi_os_item.produto, bi_os_item.custo_peca
) x
JOIN tbl_peca ON x.peca = tbl_peca.peca
JOIN tbl_produto ON x.produto = tbl_produto.produto;";
//echo $sql;

$res = pg_exec ($con,$sql);

		$arquivo = "/tmp/assist/tabela_garantia_black.txt";
		$fp = fopen ($arquivo,"w");
		fputs ($fp,"Referência Produto");
		fputs ($fp,";");
		fputs ($fp,"Descrição Produto");
		fputs ($fp,";");
		fputs ($fp,"Peca");
		fputs ($fp,";");
		fputs ($fp,"Referência Peça");
		fputs ($fp,";");
		fputs ($fp,"Descrição Peça");
		fputs ($fp,";");
		fputs ($fp,"Qtde");
		fputs ($fp,";");
		fputs ($fp,"Custo R$");
		fputs ($fp,";");
		fputs ($fp,"\n");

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$produto_referencia = trim(@pg_result ($res,$i,produto_referencia));
			$produto_descricao  = trim(@pg_result ($res,$i,produto_descricao));
			$peca               = trim(@pg_result ($res,$i,peca));
			$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
			$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
			$qtde               = trim(@pg_result ($res,$i,qtde));
			$custo_peca              = trim(pg_result ($res,$i,custo_peca));
			
			fputs ($fp,$produto_referencia);
			fputs ($fp,";");
			fputs ($fp,$produto_descricao);
			fputs ($fp,";");
			fputs ($fp,$peca);
			fputs ($fp,";");
			fputs ($fp,$peca_referencia);
			fputs ($fp,";");
			fputs ($fp,$peca_descricao);
			fputs ($fp,";");
			fputs ($fp,$qtde);
			fputs ($fp,";");
			fputs ($fp,number_format ($custo_peca,2,",","."));
			fwrite ($fp,"\n");

		}
		fclose ($fp);
		flush();
		echo `mv  /tmp/assist/tabela_garantia_black.txt /var/www/assist/www/download/tabela_garantia_black.txt`;

		echo"<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='../download/tabela_garantia_black.txt'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em TXT</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
}else{
include "javascript_calendario.php";
?>
<html>
	<head>
	<title> Gera TXT de Garantia </title>
	<script type="text/javascript" charset="utf-8">
		jQuery.fn.slideFadeToggle = function(speed, easing, callback) {
			return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
		}
		$(function()
		{
			//$('#data_inicial').datePicker({startDate:'01/01/2000'});
			//$('#data_final').datePicker({startDate:'01/01/2000'});
			$("#data_inicial").maskedinput("99/99/9999");
			$("#data_final").maskedinput("99/99/9999");
		});

		function toogleProd(radio){
			var obj = document.getElementsByName('radio_qtde_produtos');
			if (obj[0].checked){
				$('#id_um').show("");
				$('#id_multi').hide("");
			}
			if (obj[1].checked){
				$('#id_um').hide("");
				$('#id_multi').show("");
			}
		}

		var singleSelect = true;  
		var sortSelect = true; 
		var sortPick = true; 


		function initIt() {
		  var pickList = document.getElementById("PickList");
		  var pickOptions = pickList.options;
		  pickOptions[0] = null; 
		}

		function addIt() {
			if ($('#produto_referencia_multi').val()=='')
				return false;
			if ($('#produto_descricao_multi').val()=='')
				return false;

			var pickList = document.getElementById("PickList");
			var pickOptions = pickList.options;
			var pickOLength = pickOptions.length;
			pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
			pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

			$('#produto_referencia_multi').val("");
			$('#produto_descricao_multi').val("");

			if (sortPick) {
				var tempText;
				var tempValue;
				while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
					tempText = pickOptions[pickOLength-1].text;
					tempValue = pickOptions[pickOLength-1].value;
					pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
					pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
					pickOptions[pickOLength].text = tempText;
					pickOptions[pickOLength].value = tempValue;
					pickOLength = pickOLength - 1;
				}
			}

			pickOLength = pickOptions.length;
			$('#produto_referencia_multi').focus();
		}
		function delIt() {
		  var pickList = document.getElementById("PickList");
		  var pickIndex = pickList.selectedIndex;
		  var pickOptions = pickList.options;
		  while (pickIndex > -1) {
			pickOptions[pickIndex] = null;
			pickIndex = pickList.selectedIndex;
		  }
		}

		function selIt(btn) {
			var pickList = document.getElementById("PickList");
			var pickOptions = pickList.options;
			var pickOLength = pickOptions.length;
			for (var i = 0; i < pickOLength; i++) {
				pickOptions[i].selected = true;
			}
			
		}
	</script>
	<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
		<script type="text/javascript">
			function Formatadata(Campo, teclapres)
			{
				var tecla = teclapres.keyCode;
				var vr = new String(Campo.value);
				vr = vr.replace("/", "");
				vr = vr.replace("/", "");
				vr = vr.replace("/", "");
				tam = vr.length + 1;
				if (tecla != 8 && tecla != 8)
				{
					if (tam > 0 && tam < 2)
						Campo.value = vr.substr(0, 2) ;
					if (tam > 2 && tam < 4)
						Campo.value = vr.substr(0, 2) + '/' + vr.substr(2, 2);
					if (tam > 4 && tam < 7)
						Campo.value = vr.substr(0, 2) + '/' + vr.substr(2, 2) + '/' + vr.substr(4, 7);
				}
			}
		</script>
	<style type="text/css">

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
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


			/*****************************
			ELEMENTOS DE POSICIONAMENTO
			*****************************/

			#container {
			  border: 0px;
			  padding:0px 0px 0px 0px;
			  margin:0px 0px 0px 0px;
			  background-color: white;
			}
			#logo{
				BORDER-RIGHT: 1px ;
				BORDER-TOP: 1px ;
				BORDER-LEFT: 1px ;
				BORDER-BOTTOM: 1px ;
				position: absolute;
				right: 10px;
				z-index: 5;
			}
		</style>
	</head>
	<body>
	<center>
		<table width="700" align="center" class="formulario" border="0">
		<CAPTION class="titulo_tabela">Parâmetros de Pesquisa</CAPTION>
		<tbody>
			<form name='relatorio' method="post" action="?listar=s">
				<tr>
					<th colspan="4" align="center" style="padding:10px 0 10px 0px;">Listar Os's finalizadas:</th>
				</tr>
				<tr>
					<th width="70" align="right" style="padding:0 0 0 150px;">Entre:</th><td width='130'><input type="text" name="data_ini" maxlength="10" size='12' onkeyup="Formatadata(this,event)" class='frm' id="data_inicial"></td>
				
					<th align="center">E:</th><td><input type="text" name="data_fim" maxlength="10" size='12' onkeyup="Formatadata(this,event)" class='frm' id="data_final"></td>
				</tr>
				<tr align='center'>
					<td colspan="4" id='diferente' style="padding:10px 0 10px 0px;"><input type="submit" value="Listar"></td>
				</tr>
			</form>
		</tbody>
		</table>
	</center>
	</body>
	</html>
<?
}
?>
