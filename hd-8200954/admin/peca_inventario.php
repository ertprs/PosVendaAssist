<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
$layout_menu = 'callcenter';


$btn_acao = strtolower ($_POST['btn_acao']);

if(strlen($btn_acao)>0){
	$qtde_peca     = $_POST['qtde_peca'];
	$qtde_peca     = $qtde_peca + 20;
	$codigo_posto  = $_POST['codigo_posto'];
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$posto = pg_result($res,0,0);
	}else{
		$msg_erro = "Posto não encontrado";
	}
	if(strlen($msg_erro)==0){
		for($x=0;$qtde_peca>$x;$x++){
			$peca_referencia = $_POST['peca_referencia_'.$x];
			$peca_descricao  = $_POST['peca_descricao_'.$x];
			$quantidade      = $_POST['quantidade_'.$x];
			if(strlen($peca_referencia)>0 and strlen($quantidade)>0 and strlen($msg_erro)==0){
				$sql = "SELECT peca 
						from tbl_peca 
						where referencia = '$peca_referencia' 
						and fabrica = $login_fabrica;";
				$res = @pg_exec($con,$sql);
				
	//echo "$sql<BR>";
				if(@pg_numrows($res)>0){
					$peca = pg_result($res,0,0);
						$sql = "INSERT INTO tbl_estoque_posto_movimento(
											fabrica      ,
											posto        ,
											peca         ,
											data         ,
											qtde_entrada ,
											obs
									)values(
											$login_fabrica ,
											$posto         ,
											$peca          ,
											current_date   ,
											$quantidade    ,
											'Inventário de Peças'
									);";
						$res = pg_exec($con,$sql);
	//echo "$sql<BR>";
						$msg_erro .= pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$sql = "SELECT posto 
									from tbl_estoque_posto 
									where peca = $peca 
									and fabrica = $login_fabrica
									and posto   = $posto;";
							$res = pg_exec($con,$sql);
		//echo "$sql<BR>";
							$msg_erro .= pg_errormessage($con);

							if(pg_numrows($res)>0){
								$sql = "UPDATE tbl_estoque_posto set qtde = qtde + $quantidade
										WHERE posto = $posto and fabrica = $login_fabrica and peca = $peca;";
								$res = pg_exec($con,$sql);
		//echo "$sql<BR>";					
							}else{
								$sql = "INSERT into tbl_estoque_posto(
													posto     ,
													fabrica   ,
													peca      ,
													qtde
											)values(
													$posto   ,
													$login_fabrica ,
													$peca          ,
													$quantidade
													);";
								$res = pg_exec($con,$sql);
		//echo "$sql<BR>";				
							}
						}
				
				}
			}
		}
		if(strlen($msg_erro)>0){
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}else{
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Cadastrado com Sucesso!";
		}
	}
}


$title       = "CADASTRO DE INVENTÁRIO DE PEÇAS";


include "cabecalho.php";

?>

<SCRIPT LANGUAGE="JavaScript">
function exibeTipo(){
	f = document.frm_pedido;
	if(f.linha.value == 3){
		f.tipo_pedido.disabled = false;
	}else{
		f.tipo_pedido.selectedIndex = 0;
		f.tipo_pedido.disabled = true;
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo;
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}
</SCRIPT>


<style type="text/css">
body {
	font: 80% Verdana,Arial,sans-serif;
	/* An explicit background color needed for the Safari browser. */
	/* Without this, Safari users will see black in the corners. */
	background: #FFF;
}

/* The styles below are NOT needed to make .corner() work in your page. */
/*

h1 {
	font: bold 150% Verdana,Arial,sans-serif;
	margin: 0 0 0.25em;
	padding: 0;
	color: #009;
}
h2 {
	font: bold 100% Verdana,Arial,sans-serif;
	margin: 0.75em 0 0.25em;
	padding: 0;
	color: #006;
}
ul {
	margin-top: 0.25em;
	padding-top: 0;
}
code {
	font: 90% Courier New,monospace;
	color: #33a;
	font-weight: bold;
}
#demo {

}*/



.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 1px 1px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	font-size:12px;
	color:#FFFFFF;

}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}
.subtitulo P{
	font-size:10px;
	
}
.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px 30px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.content h1 {
	color:black;
	font-size: 120%;
}

.extra {
	background:#BFDCFB;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.extra span {
	color:#FF0D13;
	font-size:14px;
	font-weight:bold;
	padding-left:30px;
}

.error {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}
.error h1 {
	color:#FFFFFF;
	font-size:14px;
	font-size:normal;
	text-transform: capitalize;
}

.inicio {
	background:#8BBEF8;
	width: 600px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.inicio h1 {
	color:white;
	font-size: 105%;
	font-weight:bold;
}


.subinicio {
	background:#E1EEFD;
	width: 550px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.subinicio h1 {
	color:white;
	font-size: 105%;
}


#tabela {
	font-size:12px;
}
#tabela td{
	font-weight:bold;
}


.xTabela{
	font-family: Verdana, Arial, Sans-serif;
	font-size:12px;
	padding:10px;
}

.xTabela td{
	/*border-bottom:2px solid #9E9E9E;*/
}

</style>

<style type="text/css">

ul#split, ul#split li{
	margin:50px;
	padding:0;
	width:600px;
	list-style:none
}

ul#split li{
	float:left;
	width:600px;
	margin:0 10px 10px 0
}

ul#split h3{
	font-size:14px;
	margin:0px;
	padding: 5px 0 0;
	text-align:center;
	font-weight:bold;
	color:white;
}
ul#split h4{
	font-size:90%
	margin:0px;
	padding-top: 1px;
	padding-bottom: 1px;
	text-align:center;
	font-weight:bold;
	color:white;
}

ul#split p{
	margin:0;
	padding:5px 8px 2px
}

ul#split div{
	background: #E6EEF7
}

li#one{
	text-align:left;
	
}

li#one div{
	border:1px solid #596D9B
}
li#one h3{
	background: #7392BF;
}

li#one h4{
	background: #7392BF;
}

.coluna1{
	width:250px;
	font-weight:bold;
	display: inline;
	float:left;
}


table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

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


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$(".titulo").corner("round");
		$(".subtitulo").corner("round");
		$(".content").corner("dog 10px");
		$(".error").corner("dog2 10px");
		$(".extra").corner("dog");
		$(".inicio").corner("round");
		$(".subinicio").corner("round");

	});
</script>

<!-- Bordas Arredondadas para a NIFTY -->
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>

<script type="text/javascript">

function adicionaPreco() {

		if(document.getElementById('peca_descricao').value=="") { alert('Informe a peça');   return false}
		if(document.getElementById('peca_referencia').value==""){ alert('Informe a peça');   return false}
		if(document.getElementById('quantidade').value=="")     { alert('Informe a quantidade'); return false}

		var tbl = document.getElementById('tbl_preco');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;
		document.getElementById('qtde_peca').value = lastRow;
		var linha = document.createElement('tr');
		linha.setAttribute('id','linha_'+iteration);
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		
//		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
		var celula = criaCelula(document.getElementById('peca_referencia').value);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'peca_referencia_' + iteration);
		el.setAttribute('id', 'peca_referencia_' + iteration);
		el.setAttribute('value',document.getElementById('peca_referencia').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'peca_descricao_' + iteration);
		el.setAttribute('id', 'peca_descricao_' + iteration);
		el.setAttribute('value',document.getElementById('peca_descricao').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'quantidade_' + iteration);
		el.setAttribute('id', 'quantidade_' + iteration);
		el.setAttribute('value',document.getElementById('quantidade').value);
		celula.appendChild(el);

		linha.appendChild(celula);

		// coluna 3 - TIPO
		var celula = criaCelula(document.getElementById('peca_descricao').value);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
		linha.appendChild(celula);

		var celula = criaCelula(document.getElementById('quantidade').value);
		celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 4 - Ações
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerPreco(iteration);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);


	}
function removerPreco(iidd){
	// alert (iidd);
	$("#linha_"+iidd).remove();

}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);

	celula.appendChild(textoNode);
	return celula;
}
</script>



<? include "javascript_pesquisas.php" ?>

<!-- OBSERVAÇÕES -->
<?
if (strlen ($msg_erro) > 0) {
	?>
	<table width='700px' align='center'>
	<tr class='msg_erro'>
		<td>
			<? echo $msg_erro; ?>
		</td>
	</tr>
	</table>
<? } 

if (strlen($msg)>0){
	?>
	<table width='700px' align='center'>
	<tr class='sucesso'>
		<td>
			<? echo $msg; ?>
		</td>
	</tr>
	</table>
<?
}

?>



<!-- TITULO -->

<table class='titulo_tabela' width="700px" align="center">
	<tr>
		<td>Parâmetros de Pesquisa</td>
	</tr>
</table>




<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">

<table width='700' border='0' align='center' cellpadding="2" cellspacing="2" class='formulario'>

<tr><td colspan='4'>&nbsp;</td></tr>
<tr>
	<td align='center'>
		
		<table class="formulario" width="600px">
			<tr>

				<td>Código Posto</td>
								
				<td align='left'>Nome Posto</td>
				
			</tr>
			
			<tr>
				
				<td>
					<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="10" value="<? echo $codigo_posto; ?>">
					<img src='imagens/lupa.png' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle'  onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.codigo_posto, document.frm_pedido.posto_nome, 'codigo')">
				</td>
				
				<td>
					<input class="frm" type="text" name="posto_nome" id="posto_nome" size="40" value="<? echo $peca_descricao ?>"><img src='imagens/lupa.png' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.codigo_posto, document.frm_pedido.posto_nome, 'nome')">
				</td>
				
			</tr>
			
			<tr><td colspan='4'>&nbsp;</td></tr>
			
			<tr>
				
				<td>Referência </td>
							
				<td>Descrição</td>
				
			</tr>
			
			<tr>
			
				<td>			
					<input class="frm" type="text" name="peca_referencia" id="peca_referencia" size="10" value="<? echo $peca_referencia; ?>">
					<img src='imagens/lupa.png' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia,window.document.frm_pedido.peca_descricao,window.document.frm_pedido.preco,window.document.frm_pedido.voltagem,'referencia')">
				</td>
				
				<td>				
					<input class="frm" type="text" name="peca_descricao" id="peca_descricao" size="40" value="<? echo $peca_descricao ?>"><img src='imagens/lupa.png' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle'  onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia,window.document.frm_pedido.peca_descricao,window.document.frm_pedido.preco,window.document.frm_pedido.voltagem,'descricao')">
					<input type="hidden" name="preco" value="<? echo $preco ?>">
					<input type="hidden" name="voltagem" value="<? echo $voltagem ?>">				
				</td>
			
			</tr>

			<tr><td colspan='4'>&nbsp;</td></tr>
			
			<tr>
				<td>Quantidade</td>
			</tr>
			
			<tr>
				<td>
					<input type="text" name="quantidade" id='quantidade' size='2' maxlength='3' value="<? echo $quantidade ?>" class='frm'>
				</td>
			</tr>
			
			<tr><td colspan='4'>&nbsp;</td></tr>
			
			<tr>
				<td colspan='4' align='center'>
					<input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adicionaPreco()'>
				</td>
			</tr>
		</table>
		
	</td>
</tr>


</table>

<BR><BR>

<table width='700' id='tbl_preco' cellspacing='2' cellpadding='2' class='tabela' align='center'>

	<tr class='titulo_coluna'>
		<td width='100'> Referência </td>
		<td width='400'> Descrição </td>
		<td width='20'> Qtde </td>
		<td width="50"> Ações </td>
	</tr>

	<tbody>
	</tbody>
</table>

<BR>

<table width='700' border='0' align='center'>
	<tr>
		<td align='center'>
			<input type='hidden' name='qtde_peca' id='qtde_peca' type='button' value='0'>
			<input class="botao" type="hidden" name="btn_acao"  value=''>
			<input id='grava_inventario' type='button' value='Gravar Inventário' onClick="javascript:if (document.frm_pedido.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_pedido.btn_acao.value='Gravar';document.frm_pedido.submit();}">
		</td>
	</tr>
</table>
</form>
<BR />


<?
if(strlen($posto)>0){
$sql = "SELECT tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_estoque_posto.qtde
		FROM tbl_estoque_posto
		join tbl_peca on tbl_peca.peca = tbl_estoque_posto.peca
		WHERE tbl_estoque_posto.posto = $posto
		and tbl_estoque_posto.fabrica = $login_fabrica
		order by tbl_peca.referencia";
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){
?>
Estoque cadastrado
<table width='700' align='center' cellspacing='1' cellpadding='1' class='tabela'>

<tr class='titulo_coluna'>
	<td width='100'> Referência </td>
	<td  width='400'> Descrição </td>
	<td width='20'> Qtde </td>
</tr>

<tbody>
<? for($x=0;pg_numrows($res)>$x;$x++){
	$peca_referencia = pg_result($res,$x,referencia);
	$peca_descricao  = pg_result($res,$x,descricao);
	$peca_qtde       = pg_result($res,$x,qtde);
	 
	$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
	echo "<tr bgcolor='{$cor}'>";
	    echo "<td>$peca_referencia</td>";
	    echo "<td align='left'>$peca_descricao</td>";
	    echo "<td  align='center'>$peca_qtde</td>";
	echo "</tr>";
} ?>
</tbody>
</table>
<?}
}?>

<? include "rodape.php"; ?>
