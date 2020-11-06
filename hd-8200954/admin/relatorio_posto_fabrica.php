<?PHP
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="call_center";
include "autentica_admin.php";

include 'funcoes.php';


$layout_menu = "callcenter";
$title = "RELATÓRIO - POSTOS POR FABRICA";

include "cabecalho.php";
?>
<style type="text/css">
.titulo_superior {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color: #FFFFFF;
	font-size: 9px;
	background-image: url(imagens_admin/azul.gif);
	border-bottom: 2px solid #FFFFFF;
}
td.titulo {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 9px;
	color: #FFFFFF;
	background-color: #596D9B;
}
td.text {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #000000;
	font-size: 9px;
}
td.text2 {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #000000;
	font-size: 9px;
}
td.text_center {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #000000;
	text-transform: uppercase;
	font-size: 9px;
}
td.text_emails {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #000000;
	text-transform: lowercase;
	font-size: 9px;
}
td.text_credenciados_sim {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #008000;
	text-transform: lowercase;
	font-size: 9px;
}
td.text_credenciados_nao {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #800000;
	text-transform: lowercase;
	font-size: 9px;
}
td.text_erro {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	color: #000000;
	font-size: 13px;
}
tr.cor1 {
	background-color: #FFFFFF;
}

tr.cor2 {
	background-color: #D9E2EF;
}
#box {
	border: 1px solid #596D9B;
	margin-top: 8px;
	width: 1500px;
}
table.bordasimples {
	border-collapse: collapse;
		margin-top: 10px;
}
table.bordasimples tr td {
	border:1px solid #596D9B;
	font-size: 11px;
	background-color: #D9E2EF;
}
table.fundo tr td {
	font-size: 11px;
	background-color: #D9E2EF;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>
<? 
include "javascript_calendario.php";
include "javascript_pesquisas.php";
?>

<link rel="stylesheet" href="js/blue/relatoriostyle.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<script language="JavaScript">
function GerarRelatorio (produto, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto=' + produto + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<script language='javascript' src='../ajax.js'></script>

<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo){
janela = window.open("callcenter_relatorio_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>
<?PHP
// pesquisa
echo "<FORM name='frm_relatorio' METHOD='POST' ACTION='/assist/admin/relatorio_posto_fabrica.php'>";
	echo "<table width='700px' border='0' cellspacing='0' cellpadding='2' align='center' class='fundo'>";
		echo "<TR>";
			echo "<TD class='titulo_superior' width='100%' colspan='4'>PESQUISA - RELATÓRIO DE POSTOS POR FABRICA</TD>";
		echo "</TR>";
		?>
			<TD align='right' width='25%'><font size='2'>Status</TD>
			<TD align='left'width='25%'>
				<select name='credenciamento' class='Caixa'>
				<option value=''></option>
				<option value='CREDENCIADO'>CREDENCIADO</option>
				<option value='DESCREDENCIADO'>DESCREDENCIADO</option>
				<option value='EM CREDENCIADO'>EM CREDENCIADO</option>
				</select>
			</TD>
		<?PHP

			echo "<TD align='right'><font size='2'width='25%'>Estado</TD>";
			echo "<TD align='left'width='25%'>";
				echo "<select name='estados' class='Caixa'>";
					echo "<option value=''></option>";
					echo "<option value='AC'>Acre</option>";
					echo "<option value='AL'>Alagoas</option>";
					echo "<option value='AM'>Amazonas</option>";
					echo "<option value='AP'>Amapá</option>";
					echo "<option value='BA'>Bahia</option>";
					echo "<option value='CE'>Ceará</option>";
					echo "<option value='DF'>Distrito Federal</option>";
					echo "<option value='ES'>Espírito Santo</option>";
					echo "<option value='GO'>Goiás</option>";
					echo "<option value='MA'>Maranhão</option>";
					echo "<option value='MG'>Minas Gerais</option>";
					echo "<option value='MS'>Mato Grosso do Sul</option>";
					echo "<option value='MT'>Mato Grosso</option>";
					echo "<option value='PA'>Pará</option>";
					echo "<option value='PB'>Paraíba</option>";
					echo "<option value='PE'>Pernambuco</option>";
					echo "<option value='PI'>Piauí</option>";
					echo "<option value='PR'>Paraná</option>";
					echo "<option value='RJ'>Rio de Janeiro</option>";
					echo "<option value='RN'>Rio Grande do Norte</option>";
					echo "<option value='RO'>Rondônia</option>";
					echo "<option value='RR'>Roraima</option>";
					echo "<option value='RS'>Rio Grande do Sul</option>";
					echo "<option value='SC'>Santa Catarina</option>";
					echo "<option value='SE'>Sergipe</option>";
					echo "<option value='SP'>São Paulo</option>";
					echo "<option value='TO'>Tocantins</option>";
				echo "</select>";
			echo "</TD>";
		echo "<TR>";
		?>
				<td  align='right' nowrap width='25%'><font size='2'>Código Posto</font></td>
					<TD align='left'nowrap width='25%'><INPUT TYPE="text" NAME="codigo_posto" SIZE="8"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_relatorio.codigo_posto,3); fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'codigo')" ></TD>
				<td  align='right' nowrap width='25%' ><font size='2'>Nome Posto</font></td>
				<TD align='left' nowrap  width='25%'><INPUT TYPE="text" NAME="nome_posto" size="15"> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_relatorio.nome_posto,3); fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'nome')" ></TD>
		<?PHP
		echo "</TR>";
		echo "<TR>";
			echo "<TD colspan='4' align='center'>";
				echo "<input type='submit' style='cursor:pointer' name='btn_acao' value='Consultar'>";
			echo "</TD>";
		echo "</TR>";
	echo "</table>";
echo "</FORM>";

// fim da pesquisa

// download de relatorio xls
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$pcodigo_posto    = $_POST['codigo_posto'];
	$pestados            = $_POST['estados'];
	$pcredenciamento     = $_POST['credenciamento'];
	$pnome_posto         = $_POST['nome_posto'];

	$data = date('Ymd');
	$arquivo_nome     = "postos_por_fabrica_xls-$login_fabrica-$data.xls";
	$arquivo ="/var/www/assist/www/admin/xls/postos_por_fabrica_xls-$login_fabrica-$data.xls";
	$fp = fopen($arquivo, "w");

		fputs($fp, "<table width='100%' border='1' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter'>");
		fputs($fp, "<TR bgcolor='#596D9B'>");
			fputs($fp, "<TD class='titulo' width='10%'>CÓDIGO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>CREDENCIAMENTO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>RAZÃO SOCIAL</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>NOME FANTASIA</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>ENDEREÇO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>NÚMERO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>COMPLEMENTO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>BAIRRO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>CEP</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>CIDADE</TD>");
			fputs($fp, "<TD class='titulo' width='5%'>ESTADO</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>TELEFONE</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>E-MAIL</TD>");
			fputs($fp, "<TD class='titulo' width='10%'>CONTATO</TD>");
			fputs($fp, "</TR>");

		if(strlen($pcodigo_posto)>0){
		$cond_1 = " and tbl_posto_fabrica.codigo_posto = '$pcodigo_posto'  ";
		}
		if(strlen($pestados)>0){
		$cond_2 = " and tbl_posto_fabrica.contato_estado = '$pestados'  ";
		}
		if(strlen($pcredenciamento)>0){
		$cond_3 = " and tbl_posto_fabrica.credenciamento = '$pcredenciamento'  ";
		}
		if(strlen($pnome_posto)>0){
		$cond_4 = " and tbl_posto.nome = '$pnome_posto'";
		}

		$sqlx = "SELECT tbl_posto_fabrica.codigo_posto                     ,
					tbl_posto_fabrica.credenciamento                       ,
					tbl_posto.nome                                         ,
					tbl_posto_fabrica.contato_endereco       as endereco   ,
					tbl_posto_fabrica.contato_numero         as numero     ,
					tbl_posto_fabrica.contato_complemento    as complemento,
					tbl_posto_fabrica.contato_bairro         as bairro     ,
					tbl_posto_fabrica.contato_cep            as cep        ,
					tbl_posto_fabrica.contato_cidade                       ,
					tbl_posto_fabrica.contato_estado                       ,
					tbl_posto_fabrica.contato_fone_comercial as fone       ,
					tbl_posto_fabrica.contato_email                        ,
					tbl_posto_fabrica.nome_fantasia                        ,
					tbl_posto.contato                  
				FROM tbl_posto_fabrica 
				JOIN tbl_posto using(posto)
				WHERE fabrica = $login_fabrica
				$cond_1
				$cond_2
				$cond_3
				$cond_4
				";

	
		$resx = pg_exec ($con,$sqlx);
		for ($i=0; $i<pg_numrows($resx); $i++){
			$xposto                  = pg_result($resx,$i,codigo_posto);
			$xposto_credenciamento   = pg_result($resx,$i,credenciamento);
			$xnome_posto             = pg_result($resx,$i,nome);
			$xposto_endereco         = pg_result($resx,$i,endereco);
			$xposto_numero           = pg_result($resx,$i,numero);
			$xposto_complemento      = pg_result($resx,$i,complemento);
			$xposto_bairro           = pg_result($resx,$i,bairro);
			$xposto_cep              = pg_result($resx,$i,cep);
			$xposto_cidade           = pg_result($resx,$i,contato_cidade);
			$xposto_estado           = pg_result($resx,$i,contato_estado);
			$xnome_fantasia          = pg_result($resx,$i,nome_fantasia);
			$xposto_fone             = pg_result($resx,$i,fone);
			$xposto_email            = pg_result($resx,$i,contato_email);
			$xposto_contato          = pg_result($resx,$i,contato);
		
		if ($login_fabrica == 25) {

			if (strlen($xposto) == 14) {
				$xposto = substr($xposto,0,2) .".". substr($xposto,2,3) .".". substr($xposto,5,3) ."/". substr($xposto,8,4) ."-". substr($xposto,12,2);
			};			
		}

			fputs($fp, "<TR bgcolor='#D9E2EF'>");
			fputs($fp, "<TD class='text' align='left'>$xposto</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_credenciamento</TD>");
			fputs($fp, "<TD class='text' align='left'>$xnome_posto</TD>");
			fputs($fp, "<TD class='text' align='left'>$xnome_fantasia</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_endereco</TD>");
			fputs($fp, "<TD class='text_center' align='left'>$xposto_numero</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_complemento</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_bairro</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_cep</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_cidade</TD>");
			fputs($fp, "<TD class='text_center' align='left'>$xposto_estado</TD>");
			fputs($fp, "<TD class='text2' align='left'>$xposto_fone</TD>");
			fputs($fp, "<TD class='text_emails' align='left'>$xposto_email</TD>");
			fputs($fp, "<TD class='text' align='left'>$xposto_contato</TD>");
			fputs($fp, "</TR>");
		}
		fputs($fp, "</table>");
		fclose($fp);

		flush();
		if(pg_numrows($resx)>0){
		echo "<table width='100%' border='0' cellspacing='0' cellpadding='2' align='center' class='bordasimples'>";
		echo "<TR>";
		echo "<TD class='titulo_superior' width='100%'>DOWNLOAD - RELATÓRIO POSTOS POR FABRICA</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Downalod em formato XLS (Colunas separadas com TABULAÇÃO)</font><br><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Clique aqui para fazer o download </font></a> </td>";
		echo "</TR>";
		echo "</table>";
		} else {
		echo "";
		}
		flush();
}

// fim do download de relatorio xls
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$pcodigo_posto    = $_POST['codigo_posto'];
	$pestados            = $_POST['estados'];
	$pcredenciamento     = $_POST['credenciamento'];
	$pnome_posto         = $_POST['nome_posto'];

		echo "<div id='box'>";
		echo "<table width='1500px' border='0' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter'>";
			echo "<caption class='titulo_superior' width='100%' colspan='14'>RELATÓRIO - POSTOS POR FABRICA</caption>";

			echo "<thead>";
			echo "<TR>";
			echo "<TD class='titulo' width='10%'>CÓDIGO</TD>";
			echo "<TD class='titulo' width='10%'>CREDENCIAMENTO</TD>";
			echo "<TD class='titulo' width='10%'>RAZÃO SOCIAL</TD>";
			echo "<TD class='titulo' width='10%'>NOME FANTASIA</TD>";
			echo "<TD class='titulo' width='10%'>ENDEREÇO</TD>";
			echo "<TD class='titulo' width='5%'>NÚMERO</TD>";
			echo "<TD class='titulo' width='10%'>COMPLEMENTO</TD>";
			echo "<TD class='titulo' width='10%'>BAIRRO</TD>";
			echo "<TD class='titulo' width='10%'>CEP</TD>";
			echo "<TD class='titulo' width='10%'>CIDADE</TD>";
			echo "<TD class='titulo' width='5%'>ESTADO</TD>";
			echo "<TD class='titulo' width='10%'>TELEFONE</TD>";
			echo "<TD class='titulo' width='10%'>E-MAIL</TD>";
			echo "<TD class='titulo' width='10%'>CONTATO</TD>";
			echo "</TR>";
			echo "</thead>";

			echo "<tbody>";
	if(strlen($pcodigo_posto)>0){
		$cond_1 = " and tbl_posto_fabrica.codigo_posto = '$pcodigo_posto'  ";
	}
	if(strlen($pestados)>0){
		$cond_2 = " and tbl_posto_fabrica.contato_estado = '$pestados'  ";
	}
	if(strlen($pcredenciamento)>0){
		$cond_3 = " and tbl_posto_fabrica.credenciamento = '$pcredenciamento'  ";
	}
	if(strlen($pnome_posto)>0){
		$cond_4 = " and tbl_posto.nome = '$pnome_posto'";
	}
	$sql = "SELECT tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto_fabrica.credenciamento                       ,
					tbl_posto.nome                                         ,
					tbl_posto_fabrica.contato_endereco       as endereco   ,
					tbl_posto_fabrica.contato_numero         as numero     ,
					tbl_posto_fabrica.contato_complemento    as complemento,
					tbl_posto_fabrica.contato_bairro         as bairro     ,
					tbl_posto_fabrica.contato_cep            as cep        ,
					tbl_posto_fabrica.contato_cidade                       ,
					tbl_posto_fabrica.contato_estado                       ,
					tbl_posto_fabrica.contato_fone_comercial as fone       ,
					tbl_posto_fabrica.contato_email                        ,
					tbl_posto_fabrica.nome_fantasia                        ,
					tbl_posto.contato                  
			FROM tbl_posto_fabrica 
			JOIN tbl_posto using(posto)
			WHERE fabrica = $login_fabrica
			$cond_1
			$cond_2
			$cond_3
			$cond_4
			";

	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
	for ($i=0; $i<pg_numrows($res); $i++){
		$posto                  = pg_result($res,$i,codigo_posto);
		$posto_credenciamento   = pg_result($res,$i,credenciamento);
		$posto_nome             = pg_result($res,$i,nome);
		$nome_fantasia          = pg_result($res,$i,nome_fantasia);
		$posto_endereco         = pg_result($res,$i,endereco);
		$posto_numero           = pg_result($res,$i,numero);
		$posto_complemento      = pg_result($res,$i,complemento);
		$posto_bairro           = pg_result($res,$i,bairro);
		$posto_cep              = pg_result($res,$i,cep);
		$posto_cidade           = pg_result($res,$i,contato_cidade);
		$posto_estado           = pg_result($res,$i,contato_estado);
		$posto_fone             = pg_result($res,$i,fone);
		$posto_email            = pg_result($res,$i,contato_email);
		$posto_contato          = pg_result($res,$i,contato);
		$cont = $i/2;
		$cont1 = $cont+0.5;
		$cont = round ($cont,"0");
		if ($cont == $cont1) {
			echo "<TR class='cor1'>";
		} else {
			echo "<TR class='cor2'>";
		}
			echo "<TD class='text'>$posto</TD>";
			echo "<TD class='text'>$posto_credenciamento</TD>";
			echo "<TD class='text'>$posto_nome</TD>";
			echo "<TD class='text'>$nome_fantasia</TD>";
			echo "<TD class='text'>$posto_endereco</TD>";
			echo "<TD class='text'>$posto_numero</TD>";
			echo "<TD class='text'>$posto_complemento</TD>";
			echo "<TD class='text'>$posto_bairro</TD>";
			echo "<TD class='text'>$posto_cep</TD>";
			echo "<TD class='text'>$posto_cidade</TD>";
			echo "<TD class='text_estados'>$posto_estado</TD>";
			echo "<TD class='text2'>$posto_fone</TD>";
			echo "<TD class='text_emails'>$posto_email</TD>";
			echo "<TD class='text'>$posto_contato</TD>";
			echo "</TR>";
	} 
	}else {
			echo "<TR>";
			echo "<TD class='text_erro' colspan='8'>Nenhum Posto encontrado</TD>";
			echo "</TR>";
	}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";

	flush();
}

include "rodape.php";
?>