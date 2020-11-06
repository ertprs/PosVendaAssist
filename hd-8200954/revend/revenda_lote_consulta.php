<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


if (strlen($_POST["acao"]) > 0) $acao = trim( $_POST["acao"]);
if (strlen($_POST["ajax"]) > 0) $ajax = trim($_POST["ajax"]);


##### G R A V A R   P E D I D O #####
if ($acao == "gravar" AND $ajax == "sim") {

	$xcodigo_posto = trim($_POST['codigo_posto']);
	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);
	$qtde_item     = trim($_POST['qtde_item']);
	$lote          = trim($_POST['lote']);
	$responsavel   = trim($_POST['responsavel']);
	$nota_fiscal   = trim($_POST['nota_fiscal']);
	$data_nf       = trim($_POST['data_nf']);

	if(strlen($responsavel)==0) $msg_erro .= "<br>Digite o nome do responsável";
	if(strlen($lote)       ==0) $msg_erro .= "<br>Digite o número do lote";

	if (strlen($xcodigo_posto) > 0 ) {
		$sql =	"SELECT tbl_posto.posto
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$xcodigo_posto' ";
	
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = "'".pg_result($res,0,0)."'";
		}else{
			$posto = "null";
			$msg_erro .= " Favor informe o posto correto. ";
		}
	}

	if (strlen($xrevenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
				FROM	tbl_revenda
				WHERE	tbl_revenda.cnpj = '$xrevenda_cnpj' ";
	
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
		}else{
			$revenda = "null";
			$msg_erro .= " Favor informe a revenda correta. ";
		}
	}

	$data_nf = converte_data($data_nf);

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($lote_revenda) == 0) {
			########## I N S E R E ##########
			$sql =	"INSERT INTO tbl_lote_revenda (
						posto             ,
						revenda           ,
						fabrica           ,
						admin             ,
						lote              ,
						nota_fiscal       ,
						data_nf           ,
						responsavel
					) VALUES (
						$posto              ,
						$revenda            ,
						$login_fabrica      ,
						$login_admin        ,
						'$lote'             ,
						'$nota_fiscal'      ,
						'$data_nf'          ,
						'$responsavel'
					)";
		}else{
			########## A L T E R A ##########
			$sql =	"UPDATE tbl_lote_revenda SET
						posto             = $posto              ,
						responsavel       = '$responsavel'
					WHERE tbl_lote_revenda.lote_revenda  = $lote_revenda
					AND   tbl_lote_revenda.fabrica = $login_fabrica";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
			$res = @pg_exec($con,"SELECT CURRVAL ('tbl_lote_revenda_lote_revenda_seq')");
			$lote_revenda = pg_result($res,0,0);
			$msg_erro .= pg_errormessage($con);
		}

		if($qtde_item == 0) $msg_erro = "É necessário fazer o lançamento de peças";
		if (strlen($msg_erro) == 0) {
			$qtde_item = $qtde_item+3;
			for ($i = 0 ; $i < $qtde_item ; $i++) {

				$produto_referencia = trim($_POST['referencia_produto_' . $i]);
				$produto_descricao  = trim($_POST['descricao_produto_'  . $i]);
				$produto_qtde       = trim($_POST['produto_qtde_'       . $i]);

				if (strlen($msg_erro) == 0) {

					if (strlen($produto_referencia) > 0 ) {
						$xproduto_referencia = strtoupper($produto_referencia);
						$xproduto_referencia = str_replace("-","",$xproduto_referencia);
						$xproduto_referencia = str_replace(".","",$xproduto_referencia);
						$xproduto_referencia = str_replace("/","",$xproduto_referencia);
						$xproduto_referencia = str_replace(" ","",$xproduto_referencia);

						if (strlen($xproduto_referencia)==0 ) continue;

						if (strlen ($produto_qtde) == 0) $produto_qtde = 1;
						if ($produto_qtde==0)            $msg_erro .= "Informe a quantidade do produto!<br>";

						$sql =	"
								SELECT tbl_produto.produto
								FROM    tbl_produto
								JOIN    tbl_linha USING(linha)
								WHERE   tbl_linha.fabrica = $login_fabrica
								AND tbl_produto.referencia = '$xproduto_referencia' ";
						$res = @pg_exec($con,$sql);

						if (@pg_numrows($res) == 1) {
							$produto = pg_result($res,0,produto);
						}else{
							$msg_erro .= "<br>Peça $produto_referencia não cadastrada.";
							$linha_erro = $i;
						}

						if (strlen($msg_erro) == 0) {
							$sql =	"INSERT INTO tbl_lote_revenda_item (
										lote_revenda ,
										produto      ,
										qtde
									) VALUES (
										$lote_revenda,
										$produto        ,
										$produto_qtde
									)";
							$res = @pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);

							if (strlen ($msg_erro) > 0) {
								$linha_erro = $i;
								break;
							}
						}
					}
				}
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso";
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro";
	}

	exit;
}


#------------ Le Pedido da Base de dados ------------#
if (strlen($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido_blackedecker                                   ,
					tbl_pedido.condicao                                              ,
					tbl_posto_fabrica.codigo_posto                                   ,
					tbl_posto.nome                                     AS nome_posto ,
					to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI') AS exportado  
			FROM    tbl_pedido
			JOIN    tbl_posto USING (posto)
			JOIN	tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$pedido_blackedecker = "00000".trim(pg_result($res,0,pedido_blackedecker));
		$pedido_blackedecker = substr($pedido_blackedecker,strlen($pedido_blackedecker)-5,strlen($pedido_blackedecker));
		$condicao            = trim(pg_result($res,0,condicao));
		$codigo_posto        = trim(pg_result($res,0,codigo_posto));
		$nome_posto          = trim(pg_result($res,0,nome_posto));
		$exportado           = trim(pg_result($res,0,exportado));
	}
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido              = $_POST['pedido'];
	$pedido_blackedecker = $_POST['pedido_blackedecker'];
	$condicao            = $_POST['condicao'];
	$codigo_posto        = $_POST['codigo_posto'];
	$nome_posto          = $_POST['nome_posto'];
}

$layout_menu = "callcenter";
$title       = "Cadastro de Pedidos de Peças";
$body_onload = "javascript: document.frm_os.condicao.focus()";

include "cabecalho.php";

?>

<script language="JavaScript">

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_os.revenda_nome;
			janela.cnpj			= document.frm_os.revenda_cnpj;
			janela.fone			= document.frm_os.revenda_fone;
			janela.cidade		= document.frm_os.revenda_cidade;
			janela.estado		= document.frm_os.revenda_estado;
			janela.endereco		= document.frm_os.revenda_endereco;
			janela.numero		= document.frm_os.revenda_numero;
			janela.complemento	= document.frm_os.revenda_complemento;
			janela.bairro		= document.frm_os.revenda_bairro;
			janela.cep			= document.frm_os.revenda_cep;
			janela.email		= document.frm_os.revenda_email;
			janela.proximo		= document.frm_os.codigo_posto;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

</script>
<script language='javascript'>


function adiconarPecaTbl() {

	if (document.getElementById('add_qtde').value==''){
		alert('Informe a quantidade');
		return false;
	}

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	// inicio da tabela
	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// coluna 1 - codigo do item
	var celula = criaCelula(document.getElementById('add_referencia').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'lote_revenda_' + iteration);
	el.setAttribute('id', 'lote_revenda_' + iteration);
	el.setAttribute('value','');
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_' + iteration);
	el.setAttribute('id', 'produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'referencia_produto_' + iteration);
	el.setAttribute('id', 'referencia_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_referencia').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'descricao_produto_' + iteration);
	el.setAttribute('id', 'descricao_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca_descricao').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_qtde_' + iteration);
	el.setAttribute('id', 'produto_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('add_qtde').value);
	celula.appendChild(el);

	linha.appendChild(celula);


	// coluna 2 DESCRIÇÃO
	var celula = criaCelula(document.getElementById('add_peca_descricao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 3 QTDE
	var qtde = document.getElementById('add_qtde').value;
	var celula = criaCelula(qtde);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);


	var total_valor_peca = parseInt(qtde);


	// coluna 9 - ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerPeca(this,total_valor_peca);};
	celula.appendChild(el);

	// fim da linha
	linha.appendChild(celula);
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	//linha.style.cssText = 'color: #404e2a;';
	tbl.appendChild(tbody);

	// incrementa a qtde
	document.getElementById('qtde_item').value++;

	//limpa form de add mao de obra
	document.getElementById('add_referencia').value='';
	document.getElementById('add_peca_descricao').value='';
	document.getElementById('add_qtde').value='';


	// atualiza os totalizador
	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) + parseFloat(total_valor_peca);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);

	document.getElementById('add_referencia').focus();
}

function removerPeca(iidd,valor){
//	var tbl = document.getElementById('tbl_pecas');
//	var lastRow = tbl.rows.length;
//	if (lastRow > 2){
//		tbl.deleteRow(iidd.title);
//		document.getElementById('qtde_item').value--;
//	}
	var tbl = document.getElementById('tbl_pecas');
	var oRow = iidd.parentElement.parentElement;		
	tbl.deleteRow(oRow.rowIndex);
	document.getElementById('qtde_item').value--;

	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) - parseFloat(valor);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);


}


function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.add_qtde;
		janela.focus();
	}
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

var http_forn = new Array();

function gravar(formulatio,redireciona,pagina,janela) {

	var acao = 'gravar';
	url = "<?=$PHP_SELF?>?ajax=sim&acao="+acao;
	parametros = "";
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type !='button'){
			//alert(formulatio.elements[i].name+' = '+formulatio.elements[i].value);
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){
				
				if(formulatio.elements[i].checked == true){
//					alert(formulatio.elements[i].value);
					parametros = parametros+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				}
			}else{
				parametros = parametros+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}
		}
	}


	var com       = document.getElementById('erro');
	var saida     = document.getElementById('saida');

	com.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;<br><img src='../imagens/carregar2.gif' >";
	saida.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;<br><img src='../imagens/carregar2.gif' >";

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('POST',url,true);
	
	http_forn[curDateTime].setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_forn[curDateTime].setRequestHeader("CharSet", "ISO-8859-1");
	http_forn[curDateTime].setRequestHeader("Content-length", url.length);
	http_forn[curDateTime].setRequestHeader("Connection", "close");

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4){
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304){

			var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="debug"){
					alert(http_forn[curDateTime].responseText);
				}
				if (response[0]=="ok"){
					com.style.visibility = "hidden";
					com.innerHTML = response[1];
					saida.innerHTML = response[1];
					if (document.getElementById('btn_continuar')){
						document.getElementById('btn_continuar').style.display='inline';
					}
					/*
					if (redireciona=='sim'){
						document.getElementById('btn_finalizar').value='Imprimir';
						var destino = pagina+'&orcamento='+orcamento.value;
						if (janela=='sim'){ // se for imprimir, abre em outra janela
							janela_aut = window.open(destino, "_blank", "width=795,height=650,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=no,menubar=no,status=no,left=0,top=0");
							janela_aut.focus();
							window.location = '$PHP_SELF';
						}else{
							window.location = destino;
						}
					}
					*/
					formulatio.btn_acao.value='Gravar';
				}else{
					formulatio.btn_acao.value='Gravar';
				}
				if (response[0]=="1"){
					com.style.visibility = "visible";
					saida.innerHTML = "<font color='#990000'>Ocorreu um erro, verifique!</font>";
					alert('Erro: verifique as informações preenchidas!');
					com.innerHTML = response[1];
					formulatio.btn_acao.value='Gravar';
				}
			}
		}
	}
	http_forn[curDateTime].send(parametros);
}

function adiconarPecaTbl() {

	if (document.getElementById('add_qtde').value==''){
		alert('Informe a quantidade');
		return false;
	}

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	// inicio da tabela
	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// coluna 1 - codigo do item
	var celula = criaCelula(document.getElementById('add_referencia').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'lote_revenda_' + iteration);
	el.setAttribute('id', 'lote_revenda_' + iteration);
	el.setAttribute('value','');
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_' + iteration);
	el.setAttribute('id', 'produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'referencia_produto_' + iteration);
	el.setAttribute('id', 'referencia_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_referencia').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'descricao_produto_' + iteration);
	el.setAttribute('id', 'descricao_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca_descricao').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_qtde_' + iteration);
	el.setAttribute('id', 'produto_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('add_qtde').value);
	celula.appendChild(el);

	linha.appendChild(celula);


	// coluna 2 DESCRIÇÃO
	var celula = criaCelula(document.getElementById('add_peca_descricao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 3 QTDE
	var qtde = document.getElementById('add_qtde').value;
	var celula = criaCelula(qtde);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);


	var total_valor_peca = parseInt(qtde);


	// coluna 9 - ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerPeca(this,total_valor_peca);};
	celula.appendChild(el);

	// fim da linha
	linha.appendChild(celula);
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	//linha.style.cssText = 'color: #404e2a;';
	tbl.appendChild(tbody);

	// incrementa a qtde
	document.getElementById('qtde_item').value++;

	//limpa form de add mao de obra
	document.getElementById('add_referencia').value='';
	document.getElementById('add_peca_descricao').value='';
	document.getElementById('add_qtde').value='';


	// atualiza os totalizador
	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) + parseFloat(total_valor_peca);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);

	document.getElementById('add_referencia').focus();
}

function removerPeca(iidd,valor){
//	var tbl = document.getElementById('tbl_pecas');
//	var lastRow = tbl.rows.length;
//	if (lastRow > 2){
//		tbl.deleteRow(iidd.title);
//		document.getElementById('qtde_item').value--;
//	}
	var tbl = document.getElementById('tbl_pecas');
	var oRow = iidd.parentElement.parentElement;		
	tbl.deleteRow(oRow.rowIndex);
	document.getElementById('qtde_item').value--;

	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) - parseFloat(valor);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);


}


function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.add_qtde;
		janela.focus();
	}
}

</script>
<style>
.Conteudo{
	font-family: Arial;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

</style>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">


<?
$qtde_item=0;
echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' cellspacing='0'>";

echo "<tr height='20' bgcolor='#BCCBE0'>";
echo "<td align='left' colspan='3'><b>Lote de Produto</b></td>";
echo "<td align='right' class='Conteudo'><a href='revenda_inicial.php'>Menu de Revendas</a></td>";
echo "</tr>";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Lote</b></td>";
echo "<td align='left' ><input type='text' name='lote' id='lote' value='$lote' class='Caixa'></td>";
echo "<td align='right' ><b>Responsável</b></td>";
echo "<td align='left' ><input type='text' name='responsavel' id='responsavel' value='$responsavel' class='Caixa'></td>";
echo "</tr>";
echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Nota Fiscal</b></td>";
echo "<td align='left' ><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='Caixa'></td>";
echo "<td align='right' ><b>Data</b></td>";
echo "<td align='left' ><input type='text' name='data_nf' id='data_nf' value='$data_nf' class='Caixa' onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf')\"><font color='#FF0000'> Ex: ".date("d/m/Y")."</font<</td>";
echo "</tr>";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>CNPJ da Revenda</b></td>";
echo "<td align='left'><input type='text' name='revenda_cnpj'  maxlength='18' value='$cnpj' class='Caixa' onblur=\"fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\">&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\"></td>";
echo "<td align='right' ><b>Nome da Revenda</b></td>";
echo "<td align='left'><input type='text' name='revenda_nome' size='50' maxlength='60' value='$nome'class='Caixa' >&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>";
echo "</tr>";

echo "<input type='hidden' name='revenda_fone'>";
echo "<input type='hidden' name='revenda_cidade'>";
echo "<input type='hidden' name='revenda_estado'>";
echo "<input type='hidden' name='revenda_endereco'>";
echo "<input type='hidden' name='revenda_numero'>";
echo "<input type='hidden' name='revenda_complemento'>";
echo "<input type='hidden' name='revenda_bairro'>";
echo "<input type='hidden' name='revenda_cep'>";
echo "<input type='hidden' name='revenda_email'>";




echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Codigo Posto</b></td>";
echo "<td align='left' ><input type='text' name='codigo_posto' maxlength='14' value='$codigo_posto' class='Caixa' onFocus=\"nextfield ='nome_posto'\" onblur=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">
</td>";
echo "<td align='right' ><b>Nome</b></td>";
echo "<td align='left'><input type='text' name='nome_posto' size='50' maxlength='60' value='$nome_post' class='Caixa' onFocus=\"nextfield ='condicao'\">&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'nome')\"></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='4'>";
	echo "<table border='0' cellspacing='0' width='100%'>";
	echo "<tr bgcolor='#BCCBE0' class='Conteudo'>";
	echo "<td align='left' ><b>Destino</td>";
	echo "</tr>";
	echo "<tr bgcolor='#F0F4FF' class='Conteudo' valign='top'>";
	echo "<td align='left' valign='top'>";
	echo "<input type='radio' name='tipo' value='0'   id='C'> Conserto/Reparação<br>";
	echo "<input type='radio' name='tipo' value='50'  id='T'> Troca<br>";
	echo "<input type='radio' name='tipo' value='100' id='D'> Devolução<br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "</tr>";

echo "<tr bgcolor='#BCCBE0' class='Conteudo'>";
echo "<td align='left' colspan='4' ><b>Produto</td>";
echo "</tr>";

echo "<input type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='add_peca'           id='add_peca'>\n";

echo "<tr><td colspan='4'>";

	echo "<table>";
	echo "<tr>";
	echo "<td align='center' class='Conteudo' width='200'>Código <input class='Caixa' type='text' name='add_referencia' id='add_referencia' size='8' value='' onblur=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'referencia'); \">\n";
	echo "<img src='../imagens/btn_lupa_novo.gif' border='0' align='absmiddle' 
	onclick=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'referencia'); \" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
	echo "</td>\n";
	echo "<td align='center' class='Conteudo' nowrap>Descrição <input class='Caixa' type='text' name='add_peca_descricao' id='add_peca_descricao' size='40' value='' >\n";
	echo "<img src='../imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'descricao'); \" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
	echo "</td>\n";
	echo "<td align='center' class='Conteudo' nowrap>Qtde <input class='Caixa' type='text' name='add_qtde' id='add_qtde' size='2' maxlength='4' value='' >\n &nbsp;";
	echo "</td>\n";
	echo "<td class='Conteudo' nowrap><input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adiconarPecaTbl()'></td>";
	echo "</tr>";

	echo "</table>";

echo "</td>";
echo "</tr>";
echo "<table>";

echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' id='tbl_pecas'>";
echo "<thead>";
echo "<tr height='20' bgcolor='#BCCBE0'>";
echo "<td align='center' class='Conteudo'><b>Código</b>&nbsp;&nbsp;&nbsp;<div id='lista_basica' style='display:inline;'></div></td>";
echo "<td align='center' class='Conteudo'><b>Descrição</b></td>";
echo "<td align='center' class='Conteudo'><b>Qtde</b></td>";
echo "<td align='center' class='Conteudo'><b>Ações</b></td>";
echo "</tr>";
echo "</thead>";

echo "<tbody>";

//MOSTRA OS ITESN SE JAH FORAM GRAVADOS NO ORCAMENTO

$valor_total_itens=0;

if(strlen($lote_revenda) > 0 AND strlen ($msg_erro) == 0){
	$sql = "SELECT  
			tbl_lote_revenda_item.lote_revenda_item                            ,
			tbl_lote_revenda_item.qtde                                         ,
			tbl_produto.produto                                                ,
			tbl_produto.descricao                                              ,
			tbl_produto.referencia
		FROM      tbl_lote_revenda_item
		LEFT JOIN tbl_produto              USING (produto)
		WHERE   tbl_lote_revenda_item.lote_revenda = $lote_revenda
		ORDER BY tbl_lote_revenda_item.lote_revenda_item;";

	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$item_lote_revenda[$k]       = trim(pg_result($res,$k,lote_revenda_item))   ;
			$item_produto[$k]            = trim(pg_result($res,$k,produto))       ;
			$item_referencia[$k]         = trim(pg_result($res,$k,referencia))       ;
			$item_qtde[$k]               = trim(pg_result($res,$k,qtde))             ;
			$item_descricao[$k]          = trim(pg_result($res,$k,descricao))   ;

			if(strlen($descricao[$k])==0) 
				$descricao[$k] = $item_descricao[$k];
		}
	}
}


if($qtde_item>0){
	for ($k=0;$k<$qtde_item;$k++){
			echo "<tr style='color: #000000; text-align: center; font-size:10px'>";
			echo "<td>$item_referencia[$k]";

			echo "<input type='hidden' name='lote_revenda_item_$k'  id='lote_revenda_item_$k'  value='$item_lote_revenda[$k]'>";
			echo "<input type='hidden' name='referencia_produto_$k' id='referencia_produto_$k' value='$item_referencia[$k]'>";
			echo "<input type='hidden' name='produto_qtde_$k'       id='produto_qtde_$k'       value='$item_qtde[$k]'>";
			echo "<input type='hidden' name='produto_$k'            id='produto_$k'            value='$item_peca[$k]'>";

			echo "</td>";
			echo "<td style=' text-align: left;'>$item_descricao[$k]</td>";
			echo "<td>$item_qtde[$k]</td>";

			$total_item = $item_qtde[$k];
			$valor_total_itens += $total_item;

			echo "<td><input type='button' onclick='javascript:removerPeca(this,$total_item);' value='Excluir' /></td>";
			echo "</tr>";
	}
}
echo "</tbody>";

echo "<tfoot>";
echo "<tr height='12' bgcolor='#BCCBE0'>";
echo "<td align='center' class='Conteudo' colspan='2'><b>Total</b></td>\n";
echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
echo "<td align='center' class='Conteudo' colspan='2'></td>\n";
echo "</tr>\n";
echo "</tfoot>";
echo "</table>\n";

$valores_sub_total   += $valor_total_itens;

echo "<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>";
echo "<tr>";
echo "<td valign='middle' align='LEFT' class='Label' >";
echo "</td>";
	echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='button' name='btn_acao'  value='Gravar' onClick=\"if (this.value!='Gravar'){ alert('Aguarde');}else {this.value='Gravando...'; gravar(this.form,'sim','$PHP_SELF','nao');}\" style=\"width: 150px;\"></td>";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";
?>
<div id='erro' style='visibility:hidden;opacity:0.85' class='Erro'></div>







<? include "rodape.php"; ?>
