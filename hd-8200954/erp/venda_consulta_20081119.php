<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

include '../funcoes.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (isset($_POST['gravarDataconserto']) AND isset($_POST['orcamento'])){
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$orcamento = trim($_POST['orcamento']);
	if (strlen($orcamento)>0){
		if(strlen($gravarDataconserto ) > 0) {
			$data = $gravarDataconserto.":00 ";
			$aux_ano  = substr ($data,6,4);
			$aux_mes  = substr ($data,3,2);
			$aux_dia  = substr ($data,0,2);
			$aux_hora = substr ($data,11,5).":00";
			$gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";
		} else {
			$gravarDataconserto ='null';
		}

		$sql = "UPDATE tbl_orcamento_os
				SET data_conserto = $gravarDataconserto
				WHERE orcamento=$orcamento";
		$res = pg_exec($con,$sql);
	}
		exit;
}

if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	if($tipo=="cliente"){
		if (strlen($q)>2){
			if ($tipo_busca == "nome") $sql_1 = " AND UPPER(nome) LIKE UPPER('%$q%')";
			else                       $sql_1 = " AND  cnpj like '%$q%' ";

			$sql= "SELECT 
						pessoa,
						nome  ,
						cidade
					FROM tbl_pessoa
					WHERE empresa = $login_empresa
					$sql_1
					ORDER BY nome ;";
			$res = pg_exec ($con,$sql);
			$numero = pg_numrows ($res);
			for ( $i = 0 ; $i < $numero ; $i++ ) {
				$pessoa	= trim(pg_result($res,$i,pessoa));
				$nome	= trim(pg_result($res,$i,nome));
				echo "$pessoa|$nome\n";
			}
		}
	}
	exit;
}

if (strlen($_GET['filtro']) > 0)
	$filtro = strtoupper(trim($_GET['filtro']));
else
	$filtro = strtoupper(trim($_POST['filtro']));

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

$title='Consulta de Venda / Orçamento de Venda';
include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'vendas') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>
<!-- Scripts para a ToolTip -->
<script src="jquery/jquery.form.js" type="text/javascript" language="javascript"></script>
<script type='text/javascript' src='jquery/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="jquery/jquery.autocomplete.css" />


<link rel="stylesheet" href="jquery/jquery.tooltip.css" />
<script src="jquery/jquery.bgiframe.js"          type="text/javascript"></script>
<script src="jquery/jquery.dimensions.tootip.js" type="text/javascript"></script>
<script src="jquery/chili-1.7.pack.js"           type="text/javascript"></script>
<script src="jquery/jquery.tooltip.js"           type="text/javascript"></script>

<script language='javascript'>
	$(function(){
		$("input[@rel='data_conserto']").maskedinput("99/99/9999 99:99");
	});
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function fnc_imprimir(orcamento) {

	var url = "orcamento_print?orcamento="+orcamento;
	janela_aut = window.open(url, "_blank", "width=795,height=650,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=no,menubar=no,status=no,left=0,top=0");
	janela_aut.focus();
}
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo NOME */
	$("#cliente_nome").autocomplete("<?=$PHP_SELF?>?tipo=cliente&busca=nome", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#cliente_nome").result(function(event, data, formatted) {
		$("#cliente"      ).val(data[0]) ;
		$("#cliente_nome" ).val(data[1]) ;
	});

});


$().ready(function() {
	$("input[@rel='data_conserto']").blur(function(){
		var campo = $(this);
			$.post('<? echo $PHP_SELF; ?>', 
				{ 
					gravarDataconserto : campo.val(),
					orcamento: campo.attr("alt")

				}, 
				function(resposta){
				}
			);
		
	});
});

</script>


<? include "javascript_pesquisas.php" ?>

<style>
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

.titulo_tabela {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

table { 
	font:0.7em Arial, Helvetica, sans-serif; 
	/*background-color:#F2F2F2; */
}
caption { 
/*	background-color:#5A666E; */
	text-align:'right';
/*	background-color:#D5EAEA;*/
	text-transform:uppercase; 
	font-weight:bold; 
	font-size:10px; 
}
caption select{
	font-size:10px; 
}
thead th { 
	background-color:#F5B348; 
	color:#724809; 
	padding:2px; 
	text-transform:uppercase; 
	border-top:1px solid #F4D39E; 
	border-left:1px solid #F4D39E; 
	border-bottom:1px solid #B76E00; 
	border-right:1px solid #B76E00; 
}
tfoot th { 
	background-color:#F29601; 
	color:#724809; 
	padding:2px; 
	text-transform:uppercase; 
	font-size:1.2em; 
}
tfoot td { 
	background-color:#FC0; 
	color:#724809; 
	font-weight:bold; 
	text-transform:uppercase; 
	font-size:1.2em; 
	padding:0px 5px; 
}
.odd {  }
tbody td { 
	/* #F1F4FA" : "#F7F5F0"; */
	/*background-color:#F1F4FA; */
	color:#5A666E; 
/*	padding:2px; 
	text-align:center; 
	border-top:1px solid #FFF; 
	border-left:1px solid #FFF; 
	border-bottom:1px solid #AFB5B8; 
	border-right:1px solid #AFB5B8;  */
}
tbody th { 
/*	background-color:#5A666E; 
	color:#D7DBDD; */
	padding:2px; 
	text-align:center; 
	border-top:1px solid #93A1AA; 
	border-left:1px solid #93A1AA; 
	border-bottom:1px solid #2F3B42; 
	border-right:1px solid #2F3B42;
}
tbody td a {  
	color:#724809; 
	text-decoration:none; 
	font-weight:bold;
}
tbody td a:hover { 
	background-color:#F5B348; 
	color:#FFF;
}
tbody th a {
	color:#FFF; 
	text-decoration:none; 
	font-weight:bold;
}
tbody th a:hover { 
	color:#FC0; 
	text-decoration:underline;
}

a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Pesquisa{
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: none;
	color: #333333;
	border:#7392BF 1px solid;
	background-color: #EFF4FA;
}

.Pesquisa caption { 
	font-size:14px; 
	font-weight:bold; 
	color: #FFFFFF;
	background-color: #7392BF;
	text-align:'left';
	text-transform:uppercase; 
	padding:0px 5px; 
}

.Pesquisa thead td{ 
	text-align: center;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #7392BF;
}

.Pesquisa tbody th{ 
	font-size: 10px;
	font-weight: normal;
	text-align:'left';
	color: #333333;
	border:#7392BF 0px solid;
}
.Pesquisa tbody td{ 
	font-size: 10px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}

.Pesquisa tfoot td{ 
	font-size:10px; 
	font-weight:bold;
	color: #000000;
	text-align:'center';
	text-transform:uppercase; 
	padding:0px 5px; 
	background-color: #EFF4FA;
}

</style>

<FORM METHOD='POST' ACTION='<?=$PHP_SELF?>'>
<table class='Pesquisa' align='center' width='750' border='0'>
	<caption><?=$title?></caption>
	<tbody>
	<tr>
		<th nowrap width='80'>Venda:</th>
		<td nowrap><input type='text' name='orcamento' id='orcamento'value='<?=$orcamento?>' class='Caixa' size='10' maxlength='20'></td>
	</tr>
	<tr>
		<th nowrap width='80'>Mês</th>
		<td nowrap>
			<select name="mes" size="1" class="Caixa">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<th nowrap width='60' style='text-align:right'>Ano</th>
		<td nowrap>
			<select name="ano" size="1" class="Caixa">
				<option value=''></option>
				<?
				//for ($i = 2003 ; $i <= date("Y") ; $i++) {
				for($i = date("Y"); $i > 2003; $i--){
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
			</select>
		</td>
	</tr>
	<tr >
		<th nowrap width='80'>Cliente:</th>
		<td nowrap colspan='3'><input type='hidden' name='cliente' id='cliente' value='<?=$cliente?>'><input type='text' name='cliente_nome' id='cliente_nome'value='<?=$cliente_nome?>' class='Caixa' size='50'></td>
	</tr>
	</tbody>
	<tfoot>
		<tr>
			<td nowrap colspan='4'><input type='submit' name='btn_acao' value='Pesquisar'></td>
		</tr>
	</tfoot>
</table>
</FORM>


<? 

if (strlen($aprovacao)>0){
	if(strtoupper($aprovacao)=="APROVADOS"){
		$filtro=" AND tbl_orcamento.data_aprovacao IS NOT NULL ";
	}elseif(strtoupper($aprovacao)=="REPROVADOS"){
		$filtro=" AND tbl_orcamento.data_reprovacao IS NOT NULL ";
	}elseif(strtoupper($aprovacao)=="AGUARDANDO"){
		$filtro=" AND tbl_orcamento.data_reprovacao IS NULL AND tbl_orcamento.data_aprovacao IS NULL";
	}elseif(strtoupper($aprovacao)=="TODOS"){
		$filtro=" AND 1 = 1";
	}else{
		$filtro=" AND tbl_orcamento.aprovado IS TRUE AND tbl_orcamento.status <> 35";
	}
}
if(strlen($consumidor_cpf)>0){
	$filtro .= " AND tbl_orcamento.cliente = $cliente";
}

if(strlen($cliente)>0){
	$filtro .= " AND tbl_orcamento.cliente = $cliente";
}

if (strlen($mes) > 0 AND strlen($ano) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	$filtro .= " AND tbl_orcamento.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
}
if(strlen($orcamento)>0){
	$filtro .= " AND tbl_orcamento.orcamento = $orcamento";
}
if(strlen($filtro)==0) {
	$filtro =" AND tbl_orcamento_os.fechamento is null";
}

$sql = "
		SELECT
			LPAD(tbl_orcamento.orcamento::text,4,'0')                            AS orcamento_numero ,
			tbl_orcamento.orcamento                                        AS orcamento        ,
			to_char(tbl_orcamento.data_digitacao,'DD/MM/YYYY')             AS data             ,
			tbl_orcamento.total_mao_de_obra                                AS total_mao_de_obra,
			tbl_orcamento.aprovado                                         AS aprovado         ,
			to_char(tbl_orcamento.data_aprovacao,'DD/MM/YYYY')             AS aprovacao        ,
			to_char(tbl_orcamento.data_reprovacao,'DD/MM/YYYY')            AS reprovacao       ,
			tbl_cliente.cliente                                            AS cliente          ,
			tbl_cliente.nome                                               AS cliente_nome     ,
			tbl_orcamento.consumidor_nome                                  AS consumidor_nome  ,
			tbl_pessoa.nome                                                AS vendedor         ,
			tbl_orcamento.total                                            AS total            ,
			tbl_status.descricao                                           AS status           ,
			tbl_status_os.status_os                                        AS status_os        ,
			tbl_status_os.descricao                                        AS status_os_desc   ,
			to_char(tbl_orcamento_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) AS data_conserto    ,
			to_char(tbl_orcamento_os.fechamento, 'DD/MM/YY' )              AS fechamento       ,
			to_char(tbl_orcamento_os.data_nf_saida, 'DD/MM/YY' )           AS data_nf_saida    ,
			tbl_orcamento_os.nf_saida                                                          ,
			tbl_orcamento_os.prateleira_box                                                    ,
			tbl_orcamento_os.orcamento_garantia
		FROM   tbl_orcamento
		LEFT JOIN tbl_orcamento_venda USING(orcamento)
		LEFT JOIN tbl_orcamento_os USING (orcamento)
		LEFT JOIN tbl_cliente      USING (cliente)
		LEFT JOIN tbl_empregado    ON tbl_empregado.empregado = tbl_orcamento.vendedor
		LEFT JOIN tbl_pessoa       ON tbl_pessoa.pessoa       = tbl_empregado.pessoa
		LEFT JOIN tbl_posto        ON tbl_posto.posto         = tbl_empregado.loja
		LEFT JOIN tbl_status       ON tbl_status.status       = tbl_orcamento.status
		LEFT JOIN tbl_status_os    ON tbl_status_os.status_os = tbl_orcamento_os.status
		WHERE tbl_orcamento.empresa=$login_empresa
		AND tbl_orcamento_os.fabrica IS NULL
		$filtro
		ORDER BY orcamento DESC";

$res = pg_exec ($con,$sql) ;

if(pg_numrows($res)>0){

	echo "<br>";
	echo "<br>";
	echo "<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='98%' border='0'>";
	echo "<thead>";
	echo "<tr>";
	echo "<td nowrap class='Titulo'>#</td>";
	echo "<td nowrap class='Titulo'>Data</td>";
	echo "<td nowrap class='Titulo'>Cliente</td>";
	echo "<td nowrap class='Titulo'>Aprovação</td>";
	echo "<td nowrap class='Titulo'>Fechamento</td>";
	echo "<td nowrap class='Titulo'>Valor Total</td>";
	echo "<td nowrap class='Titulo'>Vendedor</td>";
	echo "<td nowrap class='Titulo'>NF Saída</td>";
	echo "<td nowrap class='Titulo'>Data NF Saída</td>";
	#echo "<td nowrap class='Titulo'>Data Conserto</td>";
	echo "<td nowrap class='Titulo'>Status</td>";
	echo "<td nowrap class='Titulo'>Ações</td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";


	for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
		$orcamentoX        = trim(pg_result ($res,$x,orcamento_numero));
		$orcamentoY        = trim(pg_result ($res,$x,orcamento));
		$data              = trim(pg_result ($res,$x,data));
		$total_mao_de_obra = trim(pg_result ($res,$x,total_mao_de_obra));
		$aprovado          = trim(pg_result ($res,$x,aprovado));
		$aprovacao         = trim(pg_result ($res,$x,aprovacao));
		$reprovacao        = trim(pg_result ($res,$x,reprovacao));
		$cliente           = trim(pg_result ($res,$x,cliente));
		$cliente_nome      = trim(pg_result ($res,$x,cliente_nome));
		$consumidor_nome   = trim(pg_result ($res,$x,consumidor_nome));
		$vendedor          = trim(pg_result ($res,$x,vendedor));
		$total             = trim(pg_result ($res,$x,total));
		$status            = trim(pg_result ($res,$x,status));
		$data_conserto     = trim(pg_result ($res,$x,data_conserto));
		$nf_saida          = trim(pg_result ($res,$x,nf_saida));
		$data_nf_saida     = trim(pg_result ($res,$x,data_nf_saida));
		$prateleira_box    = trim(pg_result ($res,$x,prateleira_box));
		$status_os_desc    = trim(pg_result ($res,$x,status_os_desc));
		$fechamento        = trim(pg_result ($res,$x,fechamento));
		$orcamento_garantia= trim(pg_result ($res,$x,orcamento_garantia));

		if (strlen($consumidor_nome)==0){
			$consumidor_nome = $cliente_nome;
		}

		if (strlen($consumidor_nome)>25){
			$consumidor_nome = substr($consumidor_nome,0,25)."...";
		}

		if ($aprovado=='t'){
			$data_aprovacao="<b style='color:blue;font-weight:normal'>Aprovado</b>";
		}else
		if ($aprovado=='f'){
			$data_aprovacao="<b style='color:red;font-weight:normal'>Reprovado</b> ";
		}else{
			$data_aprovacao=" - ";
		}

		if (strlen($aprovacao)>0){
			$data_aprovaca="<b style='color:blue;font-weight:normal'>Aprovado</b>";
		}else
		if (strlen($reprovacao)>0){
			$data_aprovacao="<b style='color:red;font-weight:normal'>Reprovado</b>";
		}else{
			$data_aprovacao=" - ";
		}

		$total = number_format($total,2,',','.');

			if($cor == "#FFFFFF") $cor = "#F0F5FB";
			else                  $cor = "#FFFFFF";
		if(strlen($orcamento_garantia)>0 ) $cor='#FFFFCC';
		echo "<tr bgcolor='$cor'>";
		echo "<td align='center'><a href='orcamento_cadastro.php?orcamento=$orcamentoY' target='_blank'>$orcamentoX</a></td>";
		echo "<td align='center'>$data</td>";
		echo "<td>$consumidor_nome</td>";
		echo "<td align='center'>$data_aprovacao</td>";
		echo "<td align='center'>$fechamento&nbsp;</td>";
		echo "<td align='right'> $total</td>";
		echo "<td align='center'>$vendedor</td>";
		echo "<td align='center'>$nf_saida</td>";
		echo "<td align='center'>$data_nf_saida</td>";
		#echo "<td><input  type='text' name='data_conserto_$i' alt='$orcamentoY' rel='data_conserto' size='16' maxlength='16' value='$data_conserto'></td>";
		echo "<td align='center'>$status_os_desc</td>";
		echo "<td align='center'><a href='orcamento_cadastro.php?orcamento=$orcamentoX'>Abrir</a> | <a href='javascript:fnc_imprimir($orcamentoY)'>Imprimir</a></td>";
		echo "</td>";
	}
	echo "</tbody>";
	echo "<tfoot>";
	echo "</tfoot>";
	echo "</table>";
}else{
	echo "<b>Nenhuma venda encontrada</b>";
}
?>
<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>


<?
 include "rodape.php";
 ?>

