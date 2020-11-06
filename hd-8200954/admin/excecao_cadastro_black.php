<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = 'cadastro';
$title = "CADASTRAMENTO DE EXCEÇÕES DE MÃO-DE-OBRA";
include 'cabecalho.php';


if ($_POST['btn_acao']=='_') {

	$produto			= $_POST['produto_final'];
	$defeito_constatado	= $_POST['defeito_constatado_final'];
	$mao_de_obra		= $_POST['mao_de_obra'];

	
	if (strlen($produto)==0) {
		$msg_erro = "Escolha um Produto. ";
	}

	if (strlen($defeito_constatado)==0) {
		$msg_erro = "Escolha um defeito. ";
	}

	if (strlen($mao_de_obra)==0) {
		$msg_erro = "Digite o valor da mão de obra.";
	}
	
	$mao_de_obra = str_replace(',','.',$mao_de_obra);

	if (strlen($msg_erro)==0) {
	$sql = "INSERT INTO tbl_produto_defeito_constatado
								 (defeito_constatado,produto,mao_de_obra)
								 values
								 ($defeito_constatado,$produto,$mao_de_obra)";

	$res = pg_exec($con,$sql);

	$msg_erro = pg_errormessage($con);
	
	
			if (strlen($msg_erro)==0){

				echo "<p class=\"msg_sucesso\" style=\"width:700px; margin: auto;\">Gravado com Sucesso!</p>";

			}
	}
	else {	
			
			echo "<p class=\"msg_erro\" style=\"width:700px; margin: auto;\">".$msg_erro."</p>";

	}
}



?>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<style type="text/css">
	.titulo_tabela	{ background-color:#596d9b;font: bold 14px "Arial";color:#FFFFFF;text-align:center;}
	.frm			{ background-color:#F0F0F0;border:1px solid #888888;font-family:Verdana;font-size:8pt;font-weight:bold;}
	.msg_erro		{ background-color:#FF0000; font: bold 16px "Arial"; color:#FFFFFF; text-align:center; }
	.msg_sucesso	{ background-color: green; font: bold 16px "Arial"; color: #FFFFFF; text-align:center; }
	.subtitulo		{ background-color: #7092BE;font:bold 11px Arial;color: #FFFFFF;}
	.formulario		{ background-color: #D9E2EF; font: normal normal normal 11px/normal Arial; text-align: left; }
	.titulo_coluna	{ background-color:#596d9b;font: bold 11px "Arial";color:#FFFFFF;text-align:center; }	
</style>

<script language="javascript">

function autocompletar_descricao(conteudo,linha) {
	
	var conteudo2 = conteudo.value;
	var linha = linha;
	if (linha != 'x') {
		var	url = "ajax_defeito_constatado_mao_obra.php?q=" + conteudo2;
	//	alert(url);
		$('#descricao_'+linha).autocomplete(url, {
			minChars: 3,
			delay: 150,
			width: 400,
			scroll: true,
			scrollHeight: 500,
			matchContains: false,
			highlightItem: true,
			formatItem: function (row)   {return row[1]},
			formatResult: function(row)  {return row[1];}
		});

		$('#descricao_'+linha).result(function(event, data, formatted) {
		$('#defeito_constatado_'+linha).val(data[0])     ;
		});
	}
	else {
	var	url = "ajax_defeito_constatado_mao_obra.php?q=" + conteudo2;
		//alert(url);
		$('#descricao_final').autocomplete(url, {
			minChars: 3,
			delay: 150,
			width: 400,
			scroll: true,
			scrollHeight: 500,
			matchContains: false,
			highlightItem: true,
			formatItem: function (row)   {return row[1]},
			formatResult: function(row)  {return row[1];}
		});

		$('#descricao_final').result(function(event, data, formatted) {
		$('#defeito_constatado_final').val(data[0])     ;
		});
	}
}

function autocompletar_produto(conteudo) {
	
	var conteudo2 = conteudo.value;
//	alert(conteudo2);
	
	var	url = "ajax_produto_mao_obra.php?q=" + conteudo2;
//	alert(url);
	$('#produto_descricao').autocomplete(url, {
		minChars: 3,
		delay: 150,
		width: 400,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[3]},
		formatResult: function(row)  {return row[3];}
	});

	$('#produto_descricao').result(function(event, data, formatted) {	$('#produto_final').val(data[2])     ;
	});

}

function chamaAjax(linha,produto,cache) {
	if (document.getElementById('div_sinal_' + linha).innerHTML == '+') {
		requisicaoHTTP('GET','mostra_defeito_produto_ajax.php?linha='+linha+'&produto='+produto+'&cachebypass='+cache+'&tipo=mostrar', true , 'div_detalhe_carrega');
	}
	else
	{
		document.getElementById('div_detalhe_' + linha).innerHTML = "";
		document.getElementById('div_sinal_' + linha).innerHTML = '+';
	}

}

function load(linha) {
	document.getElementById('div_detalhe_' + linha).innerHTML = "<img src='a_imagens/ajax-loader.gif'>";
}

function div_detalhe_carrega (campos) {
	campos_array = campos.split("|");
	linha = campos_array [0];
	document.getElementById('div_detalhe_' + linha).innerHTML = campos_array[1];
	document.getElementById('div_sinal_' + linha).innerHTML = '-';
}

function alterar(linha,id,valor) {

	var linha = linha ;
	var id = id;
	var valor = valor;
//	alert(valor);

	if (confirm('Deseja Altera Valor da Mão de obra?') == true) {

		requisicaoHTTP('GET','mostra_defeito_produto_ajax.php?linha='+linha+'&id='+id+'&tipo=alterar&valor='+valor, true , 'div_detalhe_carrega2');

	}

}

function excluir(linha,id,produto) {

	var linha = linha ;
	var id = id;
	var produto = produto;

	if (confirm('Deseja Excluir Defeito deste produto?') == true) {

		requisicaoHTTP('GET','mostra_defeito_produto_ajax.php?linha='+linha+'&id='+id+'&tipo=excluir&produto='+produto, true , 'div_detalhe_carrega2');

	}

}

function div_detalhe_carrega2 (campos) {
	
	var campos_array = campos.split("|");
	var linha = campos_array [0];
	var resposta = campos_array [1];
	var cache = campos_array [2];
	var produto = campos_array [3];
	if (resposta == 'ok') {

		document.getElementById('div_sinal_' + linha).innerHTML = '+'
		chamaAjax(linha,produto,cache);

	}
	
}


function gravar(produto,linha) {

	var linha = linha ;
	var produto = produto;
	var defeito = document.getElementById('defeito_constatado_'+linha).value;
	var mao_obra = document.getElementById('valor_'+linha).value;
	var descricao = document.getElementById('descricao_'+linha).value;
	var erro = '';
	//alert(mao_obra);
	if (mao_obra == '') {
		erro = 'Digite o Valor da mão de obra';
	}

	if (defeito == '') {
		erro += 'Escolha o Defeito';
	}
	
//	alert(linha+erro);
	if (erro == '') {
		//alert('mostra_defeito_produto_ajax_teste.php?linha='+linha+'&produto='+produto+'&tipo=gravar&valor='+mao_obra+'&defeito='+defeito);
		requisicaoHTTP('GET','mostra_defeito_produto_ajax.php?linha='+linha+'&produto='+produto+'&tipo=gravar&valor='+mao_obra+'&defeito='+defeito, true , 'div_detalhe_carrega3');
	}
	else {
		alert(erro);
	}

	
	
}

function div_detalhe_carrega3 (campos) {

	var campos_array = campos.split("|");
	var linha = campos_array [0];
	var resposta = campos_array [1];
	var cache = campos_array [2];
	var produto = campos_array [3];
//	alert(resposta);

	if (resposta == 'ok') {
	
		//alert(linha);
		document.getElementById('div_sinal_' + linha).innerHTML = '+'
		chamaAjax(linha,produto,cache);
		var mao_obra = document.getElementById('valor_'+linha).value;
		var defeito = document.getElementById('defeito_constatado_'+linha).value;
		var descricao = document.getElementById('descricao_'+linha).value;
		mao_obra = " ";
		defeito = " ";
		descricao = " ";
	}
	else {
	alert(linha);
	}
	

}

</script>

<?

	$sql = "SELECT DISTINCT referencia,tbl_produto.descricao,produto
				FROM tbl_produto_defeito_constatado 
				JOIN tbl_defeito_constatado USING(defeito_constatado) 
				JOIN tbl_produto USING (produto)
				WHERE fabrica = $login_fabrica
				AND tbl_produto_defeito_constatado.mao_de_obra <> 0 
				AND produto <> 0 order by referencia";

$res = pg_exec($con,$sql);

?>
<center>
<form method='post' name='frm_produto' action=''>
<table border='1' cellpadding='1' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700' >
	<tr class='Titulo'>
		<td align='center' colspan='3' class="titulo_tabela">Produtos com MO e Serviços Realizados Cadastrados</td>
	</tr>
	<tr class='Titulo'>
		<td class="titulo_coluna"></td>
		<td class="titulo_coluna" style="text-align:left;"><b>Referência</td>
		<td class="titulo_coluna" style="text-align:left;"><b>Produto</td>
	</tr>
	<?
	
	$num = pg_num_rows($res);
	for ($i = 0;$i<$num;$i++) {
		$produto_referencia = pg_result($res,$i,referencia);
		$produto_nome = pg_result($res,$i,descricao);
		$produto = pg_result($res,$i,produto);

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			$total_pecas = $total_pecas + $qtde;
			echo "<tr>";

	?>
	<tr>
		<td class="table_line" onMouseOver="this.style.cursor='pointer';" onClick="load(<?=$i?>);chamaAjax(<?=$i?>,'<?=$produto?>','<?=$cachebypass?>')"><div id=div_sinal_<?=$i?>>+</div></td>
		<td bgcolor=<?=$cor?> class="table_line"><?=$produto_referencia?></td>
		<td bgcolor=<?=$cor?> class="table_line"><?=$produto_nome?></td>
	</tr>
	<tr>
		<td colspan='3' align='center'>
			<div id='div_detalhe_<?=$i?>'></div>
		</td>
	</tr>
	<?
	}
	?>
	<tr>
		<td colspan='3' class="formulario"><b>Total <?=$num?></b></td>
	</tr>
<?
	echo "<table style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700' class='formulario' >";
			echo "<tr>";
			echo "<td colspan=4 align=center class=subtitulo>Adicionar defeito a produto</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Produto</td>";
			echo "<td>Defeito</td>";
			echo "<td>Mão de Obra</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td><input type=text size=30 class=frm name=produto_descricao id=produto_descricao onfocus=autocompletar_produto(this)></td>";
			echo "<td><input type=hidden size=20 class=frm name=defeito_constatado_final id=defeito_constatado_final>
			<input type=text size=30 class=frm name=descricao_final id=descricao_final onfocus=autocompletar_descricao(this,'x')> </td>";
			echo "<td><input type=text size=20 class=frm name=mao_de_obra id=mao_de_obra><input type=hidden size=20 class=frm name=produto_final id=produto_final></td>";
			echo '</tr>';
			echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
			echo '<tr>';
			echo "<td colspan=3 align=center><input type='submit' style='background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;' name='btn_acao' value='_' style='background: url(imagens/btn_gravar.gif);background-repeat: no-repeat;width: 75px;'></td>";
			echo "</tr>";
			echo "</table>";
include "rodape.php";
?>
</form>
</body>
</html>


