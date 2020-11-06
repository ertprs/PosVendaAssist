<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$layout_menu = "financeiro";


function converte_data($date){
	//$date = explode("-", ereg_replace('/', '-', $date));
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

$msg_erro = "";
if($btnacao=='filtrar'){
if (strlen($_POST["data_inicial"]) > 0) $data_inicial = $_POST["data_inicial"];
if (strlen($_GET["data_inicial"])  > 0) $data_inicial = $_GET["data_inicial"];

if (strlen($_POST["data_final"]) > 0) $data_final = $_POST["data_final"];
if (strlen($_GET["data_final"])  > 0) $data_final = $_GET["data_final"];

$referencia=$_POST['referencia'];
if(strlen($_GET["referencia"])  > 0) $referencia= $_GET["referencia"];

$descricao =$_POST['descricao'];
if(strlen($_GET["descricao"])  > 0) $descricao = $_GET["descricao"];

if (strlen($referencia)>0){
	$sql_adicional_1 = " AND tbl_peca.referencia= '$referencia' ";
}
if (strlen($data_inicial)>0 AND strlen($data_final)>0){
	$tmp_data_inicial = converte_data($data_inicial);
	$tmp_data_final   = converte_data($data_final);
	$sql_adicional_2  = " AND tbl_faturamento.emissao BETWEEN '$tmp_data_inicial' AND '$tmp_data_final' ";
}

if (strlen($data_inicial)==0 OR strlen($data_final)==0){
	$msg_erro .= "Preencha as duas datas";
}

}

$verPopup    = trim($_GET['pop_up']);
$peca=$_GET['peca'];
?>
<style type="text/css">

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.menu_posto {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
}
.Conteudo_posto {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>
<?
#########################################################################################################
if ($verPopup == "sim"){
	$peca=$_GET['peca'];
	$data_inicial=$_GET['data_inicial'];
	$data_final=$_GET['data_final'];

	if (strlen($peca) > 0){
		$sql="SELECT referencia,descricao from tbl_peca where peca=$peca";
		$res=pg_exec($con,$sql);
		$referencia = pg_result($res,0,referencia);
		$descricao  = pg_result($res,0,descricao);
		$sql_adicional_1  = " AND tbl_faturamento_item.peca= $peca";
	}

	if (strlen($data_inicial)>0 AND strlen($data_final)>0){
		$sql_adicional_2  = " AND tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final' ";
	}

	if ( in_array($login_fabrica, array(11,172)) ){
			$posto_da_fabrica = "20321";
	}

	$sql="SELECT       tbl_posto.nome                         AS nome_posto   ,
						tbl_posto_fabrica.codigo_posto        AS codigo_posto ,
						sum(CASE WHEN tbl_faturamento.conferencia IS NOT NULL THEN COALESCE(tbl_faturamento_item.qtde_inspecionada,0) ELSE 0 END ) as qtde_inspecionada,
						sum(CASE WHEN tbl_faturamento.conferencia IS NULL THEN tbl_faturamento_item.qtde ELSE 0 END ) as qtde_nao_inspecionada
				FROM tbl_faturamento
				JOIN tbl_posto ON tbl_faturamento.distribuidor = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				WHERE tbl_faturamento.distribuidor IS NOT NULL
				AND tbl_faturamento.posto = $posto_da_fabrica
				AND tbl_faturamento.fabrica  = $login_fabrica
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				$sql_adicional_1
				$sql_adicional_2
				GROUP BY tbl_posto.nome,
						 tbl_posto_fabrica.codigo_posto
				ORDER BY qtde_inspecionada DESC";
	$res=pg_exec($con,$sql);
	$qtde=pg_numrows($res);

	$data = date("Y-m-d").".".date("H-i-s");
	$arquivo = "xls/relatorio_retorno_obrigatorio_posto-$login_fabrica.$data.xls";

	echo "<p id='id_download' style='display:none' align='center'><img src='imagens/excell.gif'> <a href='".$arquivo."'>Clique aqui para fazer o download do arquivo em EXCEL</a><br>Você pode ver, imprimir e salvar a tabela para consultas off-line</p>";

	$listaposto  = "";


	$listaposto .=  "<center><table border='0' cellpadding='4' cellspacing='0'  width='600px'>";
	$listaposto .= "<caption class='Titulo'>$referencia - $descricao</caption>";
	$listaposto .=  "<tr class='menu_posto' height='20'>";

	$listaposto .=  "<td align='center'>POSTO</td>";
	$listaposto .=  "<td align='center'>QUANTIDADE DEVOLVIDA</td>";
		$listaposto .=  "<td align='center'>QUANTIDADE A DEVOLVER</td>";
	$listaposto .=  "</tr>";

	for ($i=0;$i<$qtde;$i++){
		$nome              = pg_result($res,$i,nome_posto);
		$codigo_posto        = pg_result($res,$i,codigo_posto);
		$qtde_inspecionada = pg_result($res,$i,qtde_inspecionada);
		$qtde_nao_inspecionada = pg_result($res,$i,qtde_nao_inspecionada);

		$cor = ($i%2==0) ? '#E9E9E9' : '#ffffff';

		$listaposto .= "<tr class='Conteudo_posto' height='20' bgcolor='$cor' align='left'  >";

		$listaposto .= "<td nowrap  align='left' title='$codigo_posto - $nome'>$codigo_posto - $nome</td>";

		$listaposto .= "<td nowrap align='center'>$qtde_inspecionada</td>";
		$listaposto .= "<td nowrap align='center'>$qtde_nao_inspecionada</td>";

		}
		$listaposto .=  "</table>";

		echo `rm /tmp/assist/relatorio_retorno_obrigatorio_posto-$login_fabrica.$data.xls`;

		$fp = fopen ("/tmp/assist/relatorio_retorno_obrigatorio_posto-$login_fabrica.html","w");
		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE PEÇAS DE RETORNO OBRIGATORIO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp,$listaposto);
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_retorno_obrigatorio_posto-$login_fabrica.$data.xls /tmp/assist/relatorio_retorno_obrigatorio_posto-$login_fabrica.html`;
		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block'";
		echo "</script>";

		if ($qtde==0){
			echo "<center><h2 style='font-size:12px;background-color:#D9E2EF;color:black;width:550px'>Nenhum registro encontrado</h2></center>";
		}else {
			echo "<br><br>";
			echo $listaposto;
			echo "<br>";
		}

		exit;

}
########################################################################################################





include "cabecalho.php";



?>

<script src="js/jquery-1.1.2.pack.js"        type="text/javascript"></script>
<script src="js/jquery.bgiframe.js"          type="text/javascript"></script>
<script src="js/jquery.dimensions.tootip.js" type="text/javascript"></script>
<script src="js/chili-1.7.pack.js"           type="text/javascript"></script>
<script src="js/jquery.tooltip.pack.js"           type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />

<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FFFFFF
}
.quadro{
	border: 1px solid #596D9B;
	width:450px;
	height:50px;
	padding:10px;

}
.botao {
		border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 13px;
	        margin-bottom: 10px;
	        color: #0E0659;
		font-weight: bolder;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:12px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:12px;
}
</style>
<script language='javascript' src='../ajax.js'></script>
<? include "javascript_pesquisas.php"; ?>
<script language="JavaScript">



var checkflag = "false";
function check(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}


function verPosto(peca,data_inicial,data_final){
	var largura  = 700;
	var tamanho  = 400;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "<?=$PHP_SELF?>?pop_up=sim&peca=" + peca +"&data_inicial=" + data_inicial + "&data_final=" + data_final ;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=yes, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}

</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 02-10-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#datai').datePicker({startDate:'01/01/2000'});
		$('#dataf').datePicker({startDate:'01/01/2000'});
		$('#datai').maskedinput("99/99/9999");
		$('#dataf').maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>


<?
if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}

echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
?>

<input type='hidden' name='btnacao'>
<table class='Tabela' width='500' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>

	<tr >
		<td class="Titulo" height='20'>RELATÓRIO DE PEÇAS RETORNO OBRIGATÓRIO</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE'>
			<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='2'>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'><br>Referência&nbsp;</td>
						<td><br><input type="text" name="referencia"  size="16" value="<? echo $referencia ?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar peças pela referência" onclick="javascript: fnc_pesquisa_peca (document.frm_extrato.referencia, document.frm_extrato.descricao, 'referencia')"></td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Descrição&nbsp;</td>
						<td>
							<input type="text" name="descricao"  size="30"  value="<?echo $descricao?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar peças pela descricao" onclick="javascript: fnc_pesquisa_peca (document.frm_extrato.referencia, document.frm_extrato.descricao, 'descricao')">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Data do Envio &nbsp;</td>
						<td><input type="text" name="data_inicial" id="datai" size="12"  value="<?echo $data_inicial?>" class="frm"> &nbsp;&nbsp;&nbsp;&nbsp;até
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>&nbsp;</td>
						<td><input type="text" name="data_final" id="dataf" size="12"  value="<?echo $data_final?>" class="frm">
						</td>
					</tr>
					<tr><td colspan='2' bgcolor="#D9E2EF" align='center'><img src="imagens_admin/btn_filtrar.gif" onclick="javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() " ALT="Filtrar" border='0' style="cursor:pointer;"><br></td></tr>
			</TABLE>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>
		</td>
	</tr>

</table>
</form>


<?


if ($btnacao=='filtrar' and strlen($msg_erro) == 0){


	if ( in_array($login_fabrica, array(11,172)) ){
		$posto_da_fabrica = "20321";
	}

	$sql = "SELECT	tbl_peca.peca                        ,
					tbl_peca.referencia                  ,
					tbl_peca.descricao                   ,
					sum(CASE WHEN tbl_faturamento.conferencia IS NOT NULL THEN COALESCE(tbl_faturamento_item.qtde_inspecionada,0) ELSE 0 END ) as qtde_inspecionada,
					sum(CASE WHEN tbl_faturamento.conferencia IS NULL THEN tbl_faturamento_item.qtde ELSE 0 END ) as qtde_nao_inspecionada
			FROM tbl_faturamento
			JOIN tbl_faturamento_item using(faturamento)
			JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
			WHERE tbl_faturamento.distribuidor IS NOT NULL
			AND tbl_faturamento.posto = $posto_da_fabrica
			AND tbl_faturamento.fabrica  = $login_fabrica
			/*hd 14526 - AND tbl_peca.devolucao_obrigatoria is true*/
			AND tbl_faturamento.movimento = 'RETORNAVEL'
			$sql_adicional_1
			$sql_adicional_2
			GROUP BY tbl_peca.referencia,
					 tbl_peca.descricao ,
					 tbl_peca.peca
			ORDER BY qtde_inspecionada desc
		";
	#echo nl2br( $sql);
	#exit;
	$res_notas  = pg_exec ($con,$sql);
	$qtde_notas = pg_numrows($res_notas);

	if(strlen($qtde_notas) > 0) {
		$data = date("Y-m-d").".".date("H-i-s");
		$arquivo = "xls/relatorio_retorno_obrigatorio-$login_fabrica.$data.xls";

		echo "<p id='id_download' style='display:none'><img src='imagens/excell.gif'> <a href='".$arquivo."'>Clique aqui para fazer o download do arquivo em EXCEL</a><br>Você pode ver, imprimir e salvar a tabela para consultas off-line</p>";
	}

	$lista = "";
	$lista .=  "<center><table border='0' cellpadding='4' cellspacing='0'  width='600px'>";

	$lista .=  "<tr ><td class='Titulo' colspan='8'>RELATÓRIO DE PEÇAS RETORNO OBRIGATÓRIO</td></tr>";

	$lista .=  "<tr class='menu_top' height='20'>";

	$lista .=  "<td>REFERENCIA</td>";
	$lista .=  "<td>DESCRIÇÃO</td>";
	$lista .=  "<td>QUANTIDADE DEVOLVIDA</td>";
	$lista .=  "<td>QUANTIDADE A DEVOLVER</td>";
	$lista .=  "</tr>";

	for ($i=0;$i<$qtde_notas;$i++){
		$peca              = pg_result($res_notas,$i,peca);
		$referencia        = pg_result($res_notas,$i,referencia);
		$descricao         = pg_result($res_notas,$i,descricao);
		$qtde_inspecionada = pg_result($res_notas,$i,qtde_inspecionada);
		$qtde_nao_inspecionada = pg_result($res_notas,$i,qtde_nao_inspecionada);

		$cor = ($i%2==0) ? '#E9E9E9' : '#ffffff';

		$lista .= "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";

		$lista .= "<td nowrap  align='center'><a href=\"javascript:verPosto($peca,'$tmp_data_inicial','$tmp_data_final')\">$referencia</a></td>";

		$lista .= "<td nowrap align='left'>$descricao</td>";

		$lista .= "<td nowrap align='center'>$qtde_inspecionada</td>";
		$lista .= "<td nowrap align='center'>$qtde_nao_inspecionada</td>";
		}

		$lista .=  "</table>";

		echo `rm /tmp/assist/relatorio_retorno_obrigatorio-$login_fabrica.$data.xls`;

		$fp = fopen ("/tmp/assist/relatorio_retorno_obrigatorio-$login_fabrica.html","w");
		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE PEÇAS DE RETORNO OBRIGATORIO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp,$lista);
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_retorno_obrigatorio-$login_fabrica.$data.xls /tmp/assist/relatorio_retorno_obrigatorio-$login_fabrica.html`;
		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block'";
		echo "</script>";

	if ($qtde_notas==0){
		echo "<center><h2 style='font-size:12px;background-color:#D9E2EF;color:black;width:550px'>Nenhum registro encontrado</h2></center>";
	}else {
		echo "<br><br>";
		echo $lista;
		echo "<br>";
	}
}

######### FIM ##################################################

echo "<br>";

?>

<br>

<? include "rodape.php"; ?>
