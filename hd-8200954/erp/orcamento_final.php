a<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

include '../funcoes.php';

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

include "menu.php";
?>

<script language='javascript'>

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function fnc_imprimir(orcamento) {

	var url = "orcamento_print?orcamento="+orcamento;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=auto, directories=no, width=770, height=850, top=18, left=0");
	janela_aut.focus();
}
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
	color:#FFF; 
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

</style>

<!-- CAMPOS PARA ABRIR ORÇAMENTO  -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr >
			<td width='20'></td>
			<td nowrap class='Label'><b>Abrir Orçamento</b></td>
			<td nowrap class='Label'>
				<select name='abrir_orcamento' class="Caixa" onchange="javascript:
				if(this.value!=''){document.location='orcamento_cadastro.php?orcamento='+this.value}
					">
					<option value=''></option>
					<?php
					$sql = "SELECT	LPAD(orcamento,4,'0') as orcamento,
									to_char(data_digitacao,'DD/MM/YYYY') as data
							FROM   tbl_orcamento
							WHERE empresa=$login_empresa
							ORDER BY orcamento DESC";
					$res = pg_exec ($con,$sql) ;
					for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
						$orcamentoY = trim(pg_result ($res,$x,orcamento));
						$data       = trim(pg_result ($res,$x,data));

						echo "<option value='$orcamentoY'>";
						echo "$orcamentoY - $data";
						echo "</option>";
					}				
					?>
				</select>
			</td>
			<td width='20'></td>
			<td nowrap class='Label'>
				
				<a href='orcamento_cadastro.php?tipo_orcamento=orca_venda'>Novo Orçamento de Venda</a><br>
				<a href='orcamento_cadastro.php?tipo_orcamento=fora_garantia'>Novo Orçamento Fora de Garantia</a><br>
				<a href='orcamento_cadastro.php?tipo_orcamento=venda'>Nova Venda</a><br>
			
			</td>
		</tr>
		</table>

	</td>
</tr>
</table>
<br>

<!--LISTAGEM DOS ORCAMENTOS  -->

<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
<caption>
<select name='filtro' onchange="javascript:window.location='<?echo $PHP_SELF; ?>?filtro='+this.value">
	<option value=''></option>
	<option value='aprovados'>Aprovados</option>
	<option value='reprovados'>Reprovados</option>
	<option value='aguardando'>Aguardando Aprovação</option>
	<option value='todos'>Todos</option>
</select>
</caption>
<thead>
	<tr>
		<td nowrap class='Titulo'># Orçamento</td>
		<td nowrap class='Titulo'>Data</td>
		<td nowrap class='Titulo'>Cliente</td>
		<td nowrap class='Titulo'>Aprovação</td>
		<td nowrap class='Titulo'>Valor Total</td>
		<td nowrap class='Titulo'>Vendedor</td>
		<td nowrap class='Titulo'>Ações</td>
	</tr>
</thead>
<tbody>
<? 


	if (strlen($filtro)>0){
		if(strtoupper($filtro)=="APROVADOS"){
			$filtro=" AND tbl_orcamento.data_aprovacao IS NOT NULL ";
		}else
		if(strtoupper($filtro)=="REPROVADOS"){
			$filtro=" AND tbl_orcamento.data_reprovacao IS NOT NULL ";
		}else
		if(strtoupper($filtro)=="AGUARDANDO"){
			$filtro=" AND tbl_orcamento.data_reprovacao IS NULL AND tbl_orcamento.data_aprovacao IS NULL";
		}else{
			$filtro="";
		}
	}

	$sql = "SELECT	
					LPAD(tbl_orcamento.orcamento,4,'0')                 AS orcamento_numero,
					tbl_orcamento.orcamento                             AS orcamento,
					to_char(tbl_orcamento.data_digitacao,'DD/MM/YYYY')  AS data,
					tbl_orcamento.total_mao_de_obra                     AS total_mao_de_obra,
					tbl_orcamento.aprovado                              AS aprovado,
					to_char(tbl_orcamento.data_aprovacao,'DD/MM/YYYY')  AS aprovacao,
					to_char(tbl_orcamento.data_reprovacao,'DD/MM/YYYY') AS reprovacao,
					tbl_cliente.cliente                                 AS cliente,
					tbl_cliente.nome                                    AS cliente_nome,
					tbl_orcamento.consumidor_nome                       AS consumidor_nome,
					tbl_empregado.login                                 AS vendedor,
					tbl_orcamento.total                                 AS total
			FROM   tbl_orcamento
			LEFT JOIN tbl_orcamento_os USING(orcamento)
			LEFT JOIN tbl_cliente USING(cliente)
			LEFT JOIN tbl_empregado ON tbl_empregado.empregado = tbl_orcamento.vendedor
			LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_empregado.loja
			WHERE tbl_orcamento.empresa=$login_empresa
			$filtro
			ORDER BY orcamento DESC";
	$res = pg_exec ($con,$sql) ;
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
		
		
		if (strlen($consumidor_nome)==0){
			$consumidor_nome = $cliente_nome;
		}
		if ($aprovado=='t'){
			$data_aprovacao=" Aprovado em $aprovacao";
		}else
		if ($aprovado=='f'){
			$data_aprovacao=" Reprovado em $reprovacao";
		}else{
			$data_aprovacao=" - ";
		}

		if (strlen($aprovacao)>0){
			$data_aprovaca=" Aprovado em $aprovacao";
		}else
		if (strlen($reprovacao)>0){
			$data_aprovacao=" Reprovado em $reprovacao";
		}else{
			$data_aprovacao=" - ";
		}

		$total_mao_de_obra = number_format($total_mao_de_obra,2,'.','');

		echo "<tr>";
		echo "<td align='center'>$orcamentoX</td>";
		echo "<td align='center'>$data</td>";
		echo "<td>$consumidor_nome</td>";
		echo "<td align='center'>$data_aprovacao</td>";
		echo "<td align='right'>R$ $total</td>";
		echo "<td align='center'>$vendedor</td>";
		echo "<td align='center'><a href='orcamento_cadastro.php?orcamento=$orcamentoX'>Abrir</a> | <a href='javascript:fnc_imprimir($orcamentoY)'>Imprimir</a></td>";
		echo "</td>";
	}
	if(pg_numrows ($res)==0){
		echo "<tr>";
		echo "<td align='center' colspan='7'><b>Nenhum Orçamento encontrado</b></td>";
		echo "</td>";
	}
?>
</tbody>
<tfoot>

</tfoot>
</table>

<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>


<?
 //include "rodape.php";
 ?>

