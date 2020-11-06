<?
// INCLUDE NO os_extrato.php.


if($login_fabrica <> 24){
	header("Location: menu_inicial.php");
	exit;
}

$btn_acao = strtolower ($_POST['btn_acao']);

if(strlen($btn_acao)>0){
	$qtde_peca  = $_POST['qtde_peca'];
	$qtde_peca = $qtde_peca + 20;

	$res = pg_exec ($con,"BEGIN TRANSACTION");

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
										$login_posto   ,
										$peca          ,
										current_date,
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
								and posto   = $login_posto;";
						$res = pg_exec($con,$sql);
	//echo "$sql<BR>";
						$msg_erro .= pg_errormessage($con);

						if(pg_numrows($res)>0){
							$sql = "UPDATE tbl_estoque_posto set qtde = qtde + $quantidade
									WHERE posto = $login_posto and fabrica = $login_fabrica and peca = $peca;";
							$res = pg_exec($con,$sql);
	//echo "$sql<BR>";					
						}else{
							$sql = "INSERT into tbl_estoque_posto(
												posto     ,
												fabrica   ,
												peca      ,
												qtde
										)values(
												$login_posto   ,
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

	#Amarra as OS com o estoque com saida 0 para funcionar o LGR
	#HD 5630
	if ($login_fabrica==24){
		$sql = "INSERT INTO tbl_estoque_posto_movimento
				(fabrica,posto,os,peca,data,qtde_saida)
				(SELECT		$login_fabrica,
							$login_posto,
							tbl_os.os,
							tbl_os_item.peca,
							CURRENT_DATE,
							0
				FROM   tbl_os
				JOIN   tbl_os_produto        USING(os)
				JOIN   tbl_os_item           USING(os_produto) 
				JOIN   tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
				AND    tbl_servico_realizado.peca_estoque is TRUE
				LEFT JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os = tbl_os.os
				WHERE  tbl_os.fabrica = $login_fabrica
				AND tbl_os.posto      = $login_posto
				AND tbl_os.finalizada  > '2007-12-31'
				AND tbl_estoque_posto_movimento.posto IS NULL
				)";
		$res = pg_exec($con,$sql);

		$sql = "SELECT fn_estoque_os_arruma_anteriores(os,fabrica)
				FROM (
					SELECT DISTINCT tbl_os.os,tbl_os.fabrica
					FROM   tbl_os
					JOIN   tbl_os_produto        USING(os)
					JOIN   tbl_os_item           USING(os_produto) 
					JOIN   tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
					AND    tbl_servico_realizado.peca_estoque is TRUE
					JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.os = tbl_os.os
					WHERE  tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto      = $login_posto
					AND tbl_os.finalizada > '2007-12-31'
					ORDER BY tbl_os.os ASC
				) xx ";
		$res = pg_exec($con,$sql);
/*
		$remetente    = 'Telecontrol <telecontrol@telecontrol.com.br>'; 
		$destinatario = 'fabio@telecontrol.com.br'; 
		$assunto      = "INVENTARIO - Posto $login_posto ($login_fabrica) FEZ INVENTARIO - VERIFICAR"; 
		$mensagem     = "OS estoque os = $os";  
		$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
		@mail($destinatario,$assunto,$mensagem,$headers);*/
	}

	if(strlen($msg_erro)>0){
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg_erro = "Cadastrado com Sucesso!";
	}
}


$title       = "Cadastro de Inventário de Peças";
$layout_menu = 'pedido';

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
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim";
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

</style>
<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
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
		el.onclick=function(){removerPreco(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);


	}
function removerPreco(iidd){
	var tbl = document.getElementById('tbl_preco');
	var oRow = iidd.parentElement.parentElement;
	tbl.deleteRow(oRow.rowIndex);

}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);

	celula.appendChild(textoNode);
	return celula;
}
</script>



<? include "javascript_pesquisas.php" ?>



<!-- TITULO -->
<center>
<div id="layout" >
	<div class="titulo"><h1>Inventário de Peças</h1></div>
</div>


<!-- SUBTITULO -->
<div id="layout">
	<div class="subtitulo">
	<p>Caro Posto Autorizado,</p>
	<P>Por favor informar a quantidade de peças recebidas em GARANTIA no seu estoque.</p>
	<P>As informações de quantidade de estoque digitadas neste sistema são de inteira responsabilidade do Posto Autorizado, e poderão ser alvo de questionamento. Mantenha arquivado os documentos de comprovação, como: Nota Fiscal de recebimento, e número das O.S. que comprovem do uso da peça.</p>
	<P>O preenchimento do inventário deverá ser feito de forma única, após informar as peças e o posto encontrar algum erro ou equivoco deverá entrar em contato com o fabricante.</p>
	<P>**APENAS PEÇAS RECEBIDAS EM GARANTIA ANTECIPADA**</P>
	<BR>
	</div>
</div>
</center>

<?
if (strlen ($msg_erro) > 0) {
	?>
	<div id="layout">
	<div class="error">
	<? echo $msg_erro; ?>
	</div>
	</div>
<? } ?>



<!-- OBSERVAÇÕES -->

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
<table width='650' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
<td>Referência:
<input class="frm" type="text" name="peca_referencia" id="peca_referencia" size="10" value="<? echo $peca_referencia; ?>">
<img src='imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia,window.document.frm_pedido.peca_descricao,window.document.frm_pedido.preco,window.document.frm_pedido.voltagem,'referencia')">

</td>
<td>Descrição:
<input class="frm" type="text" name="peca_descricao" id="peca_descricao" size="20" value="<? echo $peca_descricao ?>"><img src='imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle'  onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia,window.document.frm_pedido.peca_descricao,window.document.frm_pedido.preco,window.document.frm_pedido.voltagem,'descricao')">
<input type="hidden" name="preco" value="<? echo $preco ?>">
<input type="hidden" name="voltagem" value="<? echo $voltagem ?>">
</td>
<td>Quantidade:
<input type="text" name="quantidade" id='quantidade' size='3' maxlength='3' value="<? echo $quantidade ?>">
</td>
<td>
<input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adicionaPreco()'>
</td>
</tr>
</table><BR><BR>
<table width='650' id='tbl_preco' cellspacing='2' cellpadding='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px' align='center'>
<thead>
<tr>
<td bgcolor='#48558A' width='100'><font color='#FFFFFF'>Referência</FONT></td>
<td bgcolor='#48558A' width='400'><font color='#FFFFFF'>Descrição</FONT></td>
<td bgcolor='#48558A' width='20'><font color='#FFFFFF'>Qtde</FONT></td>
</tr>
</thead>
<tbody>
</tbody>
</table><BR>
<table width='650' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
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
$sql = "SELECT tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_estoque_posto.qtde
		FROM tbl_estoque_posto
		join tbl_peca on tbl_peca.peca = tbl_estoque_posto.peca
		WHERE tbl_estoque_posto.posto = $login_posto
		and tbl_estoque_posto.fabrica = $login_fabrica
		order by tbl_peca.referencia";
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){
?>
Estoque cadastrado
<table width='650' cellspacing='2' cellpadding='2' style='border:#A84F28 1px solid; background-color: #F5DCC5;font-size:10px'>
<thead>
<tr>
<td bgcolor='#D27D44' width='100'><font color='#FFFFFF'>Referência</FONT></td>
<td bgcolor='#D27D44' width='400'><font color='#FFFFFF'>Descrição</FONT></td>
<td bgcolor='#D27D44' width='20'><font color='#FFFFFF'>Qtde</FONT></td>
</tr>
</thead>
<tbody>
<? for($x=0;pg_numrows($res)>$x;$x++){
	$peca_referencia = pg_result($res,$x,referencia);
	$peca_descricao  = pg_result($res,$x,descricao);
	$peca_qtde       = pg_result($res,$x,qtde);
	if($cor=="#F5DCC5"){$cor = "#FFFFFF";}ELSE{$cor="#F5DCC5";}
	echo "<tr>";
	echo "<td bgcolor='$cor'>$peca_referencia</td>";
	echo "<td bgcolor='$cor'>$peca_descricao</td>";
	echo "<td bgcolor='$cor' align='center'>$peca_qtde</td>";
	echo "</tr>";
} ?>
</tbody>
</table>
<?}?>

<? include "rodape.php"; ?>
