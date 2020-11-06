<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";

function converte_data($date)
{
	$date = explode("/", $date);
	$date2 = $date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if(isset($_GET["excluir"])){
	$sql = "DELETE FROM tbl_revenda_produto where revenda_produto=".$_GET["excluir"];
	$res = pg_exec ($con,$sql);
}

if (strlen($_POST["acao"]) > 0) $acao = trim( $_POST["acao"]);
if (strlen($_POST["ajax"]) > 0) $ajax = trim($_POST["ajax"]);


##### G R A V A R   P E D I D O #####
if ($acao == "gravar" AND $ajax == "sim") {

	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);
	$qtde_item     = trim($_POST['qtde_item']);

	if (strlen($xrevenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
				FROM	tbl_revenda
				WHERE	tbl_revenda.cnpj = '$xrevenda_cnpj' ";
	
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
		}
	}else $msg_erro .= " Favor informe a revenda correta. ";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if($qtde_item == 0) $msg_erro = "É necessário fazer o lançamento de produtos";

		if (strlen($msg_erro) == 0) {
			$qtde_item = $qtde_item+3;
			for ($i = 0 ; $i < $qtde_item ; $i++) {

				$produto_referencia = trim($_POST['referencia_produto_' . $i]);
				$produto_descricao  = trim($_POST['descricao_produto_'  . $i]);

				$produto_referencia_r = trim($_POST['referencia_produto_r_' . $i]);
				$produto_descricao_r  = trim($_POST['descricao_produto_r_'  . $i]);

				if (strlen($msg_erro) == 0) {

					if (strlen($produto_referencia) > 0 ) {
						$xproduto_referencia = strtoupper($produto_referencia);
						//$xproduto_referencia = str_replace("-","",$xproduto_referencia);
						//$xproduto_referencia = str_replace(".","",$xproduto_referencia);
						//$xproduto_referencia = str_replace("/","",$xproduto_referencia);
						//$xproduto_referencia = str_replace(" ","",$xproduto_referencia);

						if (strlen($xproduto_referencia)==0 ) continue;

						if(strlen($produto_referencia_r)==0) $msg_erro .= "Entre com o código da Revenda";

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
							$sql = "SELECT * FROM tbl_revenda_produto WHERE produto = $produto AND revenda=$revenda";
							$res = @pg_exec($con,$sql);
	
						if (@pg_numrows($res) == 0) {
							$sql = "INSERT INTO tbl_revenda_produto (revenda,produto,referencia,descricao) VALUES ($revenda,$produto,'$produto_referencia_r','$produto_descricao_r')";
							$res = @pg_exec($con,$sql);
							$inseridos     .= "<br> $produto_referencia_r - $produto_descricao_r";
						}else{
							$sql = "UPDATE tbl_revenda_produto SET referencia = '$produto_referencia_r',descricao = '$produto_descricao_r' 
								WHERE revenda = $revenda AND produto = $produto";
							$res = @pg_exec($con,$sql);
							$inseridos_nao .= "<br> $produto_referencia_r - $produto_descricao_r";
						}
					}
				}
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso<br>";
		 if(strlen($inseridos_nao)>0)echo "<br>Os seguintes produto foram atualizados:<b>$inseridos_nao</b>";
		 if(strlen($inseridos)>0)echo "<br><br>Os seguintes produtos foram inseridos<br><b>$inseridos</b>";
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro";
	}

	exit;
}

$importa = $_POST["importar"];
if(strlen($importa)>0){
	$caminho       = "/www/cgi-bin/revenda/entrada";
	$caminho_saida = "/www/cgi-bin/revenda/saida";
	
	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);
	$qtde_item     = trim($_POST['qtde_item']);

	if (strlen($xrevenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
				FROM	tbl_revenda
				WHERE	tbl_revenda.cnpj = '$xrevenda_cnpj' ";
	
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
		}
	}else $msg_erro .= " Favor informe a revenda correta. ";

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		$msg_inicio =  "<tr ><td colspan ='2'>Importando arquivo [ ".$arquivo["name"]." ] !!!<br> Aguarde...</td></tr>";
		flush();

		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> "text/plain") {
			$msg_erro = "Arquivo em formato inválido!";
		} else {
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}
		if (strlen($msg_erro) == 0) {
			// Faz o upload
			$nome_arquivo = $caminho."/".$arquivo["name"];
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$f = fopen("$caminho/".$arquivo["name"], "r");

				$i=1;
				$msg_erro = "";
				$res = pg_exec ($con,"BEGIN TRANSACTION");
				while (!feof($f)){
					$buffer = fgets($f, 4096);
					if($buffer <> "\n" and strlen(trim($buffer))>0){
						list($codigo_revenda, $descricao_revenda, $referencia, $descricao) = explode("\t", $buffer);
						$codigo_revenda    = trim($codigo_revenda);
						$descricao_revenda = trim($descricao_revenda);
						$referencia        = trim($referencia);
						$descricao         = trim($descricao);

						if(strlen($codigo_revenda)==0)    $msg_erro = " Falta o código do produto na Revenda";
						if(strlen($descricao_revenda)==0) $msg_erro = " Falta a descrição do produto na Revenda";
						if(strlen($referencia)==0)        $msg_erro = " Falta a referência do produto na Fábrica";
						if(strlen($descricao)==0)         $msg_erro = " Falta a descrição do produto na Fábrica";
						if(strlen($msg_erro)>0) $msg_erro = "Erro na linha $i:".$msg_erro;
						else{
							$sql =	"
									SELECT tbl_produto.produto
									FROM    tbl_produto
									JOIN    tbl_linha USING(linha)
									WHERE   tbl_linha.fabrica = $login_fabrica
									AND tbl_produto.referencia = '$referencia' ";
							$res = @pg_exec($con,$sql);
	
							if (@pg_numrows($res) == 1) {
								$produto = pg_result($res,0,produto);
							}else{
								$msg_erro .= "<br>Produto $referencia não cadastrado.";
								$linha_erro = $i;
							}
							$sql = "SELECT * FROM tbl_revenda_produto WHERE produto = $produto AND revenda=$revenda";
							$res = @pg_exec($con,$sql);
	
							if (@pg_numrows($res) == 0) {
								$sql = "INSERT INTO tbl_revenda_produto (revenda,produto,referencia,descricao) VALUES ($revenda,$produto,'$codigo_revenda','$descricao_revenda')";
								$res = @pg_exec($con,$sql);
								$inseridos     .= "<br> $codigo_revenda - $descricao_revenda";
							}else{
								$sql = "UPDATE tbl_revenda_produto SET referencia = '$codigo_revenda',descricao = '$descricao_revenda' 
									WHERE revenda = $revenda AND produto = $produto";
								$res = @pg_exec($con,$sql);
								$inseridos_nao .= "<br> $codigo_revenda - $descricao_revenda";
							}
						}

						
					}
					$i++;
				}
			}
			fclose($f);
			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "Carga efetuada com sucesso!";
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				
			}
			$nome_arquivo = $arquivo["name"];
			$dados = "importado".date("d-m-Y-his").".txt";
			exec ("mv $caminho/$nome_arquivo $caminho_saida/$dados");

		}
	}
}



$layout_menu = "callcenter";
$title       = "CADASTRO DE PEDIDO DE PEÇAS";
$body_onload = "javascript: document.frm_os.condicao.focus()";

include "cabecalho.php";

?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}

	$("#revenda_cnpj").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj").result(function(event, data, formatted) {
		$("#revenda_nome").val(data[1]) ;
	});

	$("#revenda_nome").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome").result(function(event, data, formatted) {
		$("#revenda_cnpj").val(data[0]) ;
	});

	$("#revenda_cnpj2").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj2").result(function(event, data, formatted) {
		$("#revenda_nome2").val(data[1]) ;
	});

	$("#revenda_nome2").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome2").result(function(event, data, formatted) {
		$("#revenda_cnpj2").val(data[0]) ;
	});

	$("#revenda_cnpj3").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj3").result(function(event, data, formatted) {
		$("#revenda_nome3").val(data[1]) ;
	});

	$("#revenda_nome3").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome3").result(function(event, data, formatted) {
		$("#revenda_cnpj3").val(data[0]) ;
	});

});
</script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->
<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs({fxSpeed: 'fast'} );
		
	});
</script>
<script language="JavaScript">

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
			janela.proximo		= document.frm_os.add_referencia_r;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

function fnc_pesquisa_revenda2 (campo, tipo) {
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
			janela.nome			= document.frm_upload.revenda_nome;
			janela.cnpj			= document.frm_upload.revenda_cnpj;
			janela.fone			= document.frm_upload.revenda_fone;
			janela.cidade		= document.frm_upload.revenda_cidade;
			janela.estado		= document.frm_upload.revenda_estado;
			janela.endereco		= document.frm_upload.revenda_endereco;
			janela.numero		= document.frm_upload.revenda_numero;
			janela.complemento	= document.frm_upload.revenda_complemento;
			janela.bairro		= document.frm_upload.revenda_bairro;
			janela.cep			= document.frm_upload.revenda_cep;
			janela.email		= document.frm_upload.revenda_email;
			janela.proximo		= document.frm_upload.arquivo;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

</script>
<script language='javascript'>

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
		janela.proximo      = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
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
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){
				
				if(formulatio.elements[i].checked == true){
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
	
	http_forn[curDateTime].setRequestHeader("X-Requested-With","XMLHttpRequest");
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
	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	// inicio da tabela
	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// coluna 1 - codigo do item
	var celula = criaCelula(document.getElementById('add_referencia_r').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	var celula = criaCelula(document.getElementById('add_peca_descricao_r').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

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
	el.setAttribute('name', 'referencia_produto_r_' + iteration);
	el.setAttribute('id', 'referencia_produto_r_' + iteration);
	el.setAttribute('value',document.getElementById('add_referencia_r').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'descricao_produto_r_' + iteration);
	el.setAttribute('id', 'descricao_produto_r_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca_descricao_r').value);
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

	linha.appendChild(celula);


	// coluna 2 DESCRIÇÃO
	var celula = criaCelula(document.getElementById('add_peca_descricao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);


	// coluna 9 - ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerPeca(this,0);};
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
	document.getElementById('add_referencia_r').value='';
	document.getElementById('add_peca_descricao_r').value='';
	document.getElementById('add_referencia').value='';
	document.getElementById('add_peca_descricao').value='';

	// atualiza os totalizador

	document.getElementById('add_referencia_r').focus();
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




}


function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
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
#Propaganda{
	text-align: justify;
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
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>




<?
$cnpj = trim($_POST['revenda_cnpj']);
$nome = trim($_POST['revenda_nome']);

$qtde_item=0;
echo "<table align='center' width='700' border='1' cellspacing='0'  class='formulario'>";

echo "<tr height='20'class='titulo_tabela'>";
echo "<td align='right' colspan='3'><b>Cadastro de Produto</b>&nbsp;</td>";
echo "<td align='right' ><a href='revenda_inicial.php'>Menu de Revendas</a></td>";
echo "</tr>";
echo "<tr><td colspan='4' ><br>";
$aba = 5;
include "revenda_cabecalho.php";
echo "</td></tr>";
echo "<tr><td colspan='4' >";
?>
<br>

<div id="container-Principal" style='width:100%;' class='formulario'>
	<ul>
		<li>
			<a href="#manual" onclick="javascript:$('#tab_atual').val('manual')"><span><img src='imagens/rec_produto.png' width='10' align="absmiddle" alt='Reclamação Produto/Defeito'>Cadastro Manual</span></a>
		</li>
		<li>
			<a href="#auto" onclick="javascript:$('#tab_atual').val('auto')"><span><img src='imagens/duv_produto.png' width='10' align=absmiddle>Upload.</span></a>
		</li>
		<li>
			<a href="#consulta" onclick="javascript:$('#tab_atual').val('consulta')"><span><img src='imagens/duv_produto.png' width='10' align=absmiddle>Consulta.</span></a>
		</li>
	</ul>
<div id="manual" class='formulario'>
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<?
	echo "<table  class='formulario' >";
	echo "<tr height='20'>";
	echo "<td align='left' nowrap>CNPJ da Revenda <br>";
	echo "<input type='text' name='revenda_cnpj' id='revenda_cnpj' maxlength='18' value='$cnpj' class='frm'>&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\"></td>";
	echo "<td align='left' nowrap >Nome da Revenda <br>";
	echo "<input type='text' name='revenda_nome' id='revenda_nome' size='35' maxlength='60' value='$nome'class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>";
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

	echo "<tr class='subtitulo'>";
	echo "<td align='left' colspan='4' >Produto</td>";
	echo "</tr>";

	echo "<input type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";
	echo "<input type='hidden' name='add_peca'        id='add_peca'>\n";

	echo "<tr><td colspan='4'>";

		echo "<table width='100%' class='formulario'>";
		echo "<tr>";
		echo "<td align='left'  width='200'>Código da Revenda<br><input class='frm' type='text' name='add_referencia_r' id='add_referencia_r' size='8' value=''>\n</td>\n";
		echo "<td align='left'  nowrap>Descrição da Revenda<br> <input class='frm' type='text' name='add_peca_descricao_r' id='add_peca_descricao_r' size='25' value='' >\n";
		echo "</td>\n";
	
		echo "<td align='left'  width='200'>Código da fábrica<br><input class='frm' type='text' name='add_referencia' id='add_referencia' size='8' value='' >\n";
		echo "<img src='../imagens/lupa.png' border='0' align='absmiddle' 
		onclick=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'referencia'); \" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
		echo "</td>\n";
		echo "<td align='left'  nowrap>Descrição da Fábrica<br><input class='frm' type='text' name='add_peca_descricao' id='add_peca_descricao' size='25' value='' >\n";
		echo "<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'descricao'); \" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
		echo "</td>\n";
		echo "<td nowrap><input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adiconarPecaTbl()'></td>";
		echo "</tr>";

		echo "</table>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<table class='formulario' align='center' width='100%' border='0' id='tbl_pecas'>";
	echo "<thead>";
	echo "<tr height='20' class='titulo_coluna'>";
	echo "<td>Código da Revenda&nbsp;</td>";
	echo "<td>Descrição da Revenda&nbsp;</td>";
	echo "<td>Código da Fábrica&nbsp;</td>";
	echo "<td>Descrição da Fábrica&nbsp;</td>";
	echo "<td>Ações&nbsp;</td>";
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

				if(strlen($descricao[$k])==0) $descricao[$k] = $item_descricao[$k];
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
	echo "<tr height='12' class='titulo_coluna'>";
	echo "<td colspan='2'><b>Total</b>&nbsp;</td>\n";
	echo "<td><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	echo "<td colspan='2'></td>\n";
	echo "</tr>\n";
	echo "</tfoot>";
	echo "</table>\n";

	$valores_sub_total   += $valor_total_itens;

	echo "<table class='formulario' align='center' width='100%' border='0'height='40'>";
	echo "<tr>";
	echo "<td valign='middle' align='LEFT' class='Label' >";
	echo "</td>";
		echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='button' name='btn_acao'  value='Gravar' onClick=\"if (this.value!='Gravar'){ alert('Aguarde');}else {this.value='Gravando...'; gravar(this.form,'sim','$PHP_SELF','nao');}\" style=\"width: 150px;\"></td>";
	echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
	echo "</tr>";
	echo "</table>";
?>
	<div id='erro' style='visibility:hidden;opacity:0.85' class='Erro'></div>
	</form>
</div>
<div id='auto' class='formulario' style="text-align:left;">
	<div > 
	<div  class='msg_erro'><?=$msg_erro?></div>
	O layout para UPLOAD de produtos da revenda deve conter os seguintes campos:<br>
	- referência do produto da revenda (código interno do produto na revenda);<br>
	- descrição do produto da revenda (descrição do produto utilizada na revenda);<br>
	- referência do produto da fabrica (código do produto utilizada na fabrica);<br>
	- descrição do produto da fabrica (descrição do produto utilizada na fabrica);<br>
	
	Este arquivo poderá ser preenchido no excel. Depois salvar como, escolher o tipo de arquivo (Salvar como tipo) : Escolher Texto em unicode (*.txt) separado por TAB(/t)<br>Não esqueça de retirar o cabeçalho do excel<br>
	OBS.: Neste arquivo tem que conter somente as informções, cabeçalhos ou outras informações deverão ser excluídas antes de salvar como txt!<br></div>
	<form name="frm_upload" method="post" action="<? echo "$PHP_SELF#auto" ?>" enctype='multipart/form-data'>

	<table>
	<tr height='20' >
	<td align='right' ><b>CNPJ da Revenda</b>&nbsp;</td>
	<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj2' maxlength='18' value='<?=$cnpj?>' class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda2 (document.frm_upload.revenda_cnpj, 'cnpj')"></td>
	<td align='right' ><b>Nome da Revenda</b>&nbsp;</td>
	<td align='left'><input type='text' name='revenda_nome' id='revenda_nome2' size='40' maxlength='60' value='<?=$nome?>' class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda2 (document.frm_upload.revenda_nome, 'nome')"></td>
	</tr>
	
	<input type='hidden' name='revenda_fone'>
	<input type='hidden' name='revenda_cidade'>
	<input type='hidden' name='revenda_estado'>
	<input type='hidden' name='revenda_endereco'>
	<input type='hidden' name='revenda_numero'>
	<input type='hidden' name='revenda_complemento'>
	<input type='hidden' name='revenda_bairro'>
	<input type='hidden' name='revenda_cep'>
	<input type='hidden' name='revenda_email'>
	
	<tr >
	<td align='right' ><b>Arquivo</b>&nbsp;</td>
	<td align='left' colspan='3'><b><input type='file' name='arquivo' size='30' class='frm'></td>
	</tr>
	</table>

	<table class='formulario' align='center' width='100%' border='0' height='40'>
	<tr>
	<td valign='middle' align='LEFT' class='Label' >
	</td>
	<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='importar' value='Importar'></td>
	<td ><div style='display:inline;'><?=$msg_erro?><? if(strlen($msg)>0){ echo "<b><font size='2'>$msg</font></b>"; if(strlen($inseridos_nao)>0)echo "<br>Os seguintes produto foram atualizados:<b>$inseridos_nao</b>"; if(strlen($inseridos)>0)echo "<br><br>Os seguintes produtos foram inseridos<br><b>$inseridos</b>";}?></div></td>
	</tr>
	</table>


	</form>
	</div>
	<div id='consulta' class='formulario'>
	<form name="frm_consulta" method="post" action="<? echo "$PHP_SELF#consulta" ?>" >

	<table>
	<tr height='20' >
	<td width="100">&nbsp;</td>
	<td align='left' >CNPJ da Revenda <br>
	<input type='text' name='revenda_cnpj' id='revenda_cnpj3'  maxlength='18' value='<?=$cnpj?>' class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda (document.frm_upload.revenda_cnpj, 'cnpj')"></td>
	<td align='left' >Nome da Revenda <br>
	<input type='text' name='revenda_nome' id='revenda_nome3' size='40' maxlength='60' value='<?=$nome?>' class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda (document.frm_upload.revenda_nome, 'nome')"></td>
	</tr>
	
	<input type='hidden' name='revenda_fone'>
	<input type='hidden' name='revenda_cidade'>
	<input type='hidden' name='revenda_estado'>
	<input type='hidden' name='revenda_endereco'>
	<input type='hidden' name='revenda_numero'>
	<input type='hidden' name='revenda_complemento'>
	<input type='hidden' name='revenda_bairro'>
	<input type='hidden' name='revenda_cep'>
	<input type='hidden' name='revenda_email'>
	
	</table>
	<table class='formulario' align='center' width='100%' border='0' height='40'>
	<tr>
	<td valign='middle' align='LEFT' class='Label' >
	</td>
	<tr><td width='50' valign='middle'  align='center' colspan='4'><input type='submit' name='consultar' value='Consultar'></td>
	<td ></td>
	</tr>
	</table>
<?
if(isset($consultar)){
	if (strlen($revenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
				FROM	tbl_revenda
				WHERE	tbl_revenda.cnpj = '$revenda_cnpj' ";
	
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
		}
	}else $msg_erro .= " Favor informe a revenda correta. ";
	$sql = "SELECT  revenda_produto,
			tbl_revenda_produto.referencia AS revenda_referencia,
			tbl_revenda_produto.descricao  AS revenda_descricao ,
			tbl_produto.referencia         AS produto_referencia,
			tbl_produto.descricao          AS produto_descricao ,
			tbl_produto.voltagem
		FROM tbl_produto
		JOIN tbl_revenda_produto USING(produto)
		JOIN tbl_linha           USING(linha)
		WHERE tbl_linha.fabrica           = $login_fabrica
		AND   tbl_revenda_produto.revenda = $revenda
		ORDER BY tbl_revenda_produto.descricao,tbl_produto.descricao";
	//tbl_revenda_produto.referencia,
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
		echo "<br><table class='tabela' align='center' width='98%' border='0' cellspacing='1'>\n";

		echo "<tr class='titulo_coluna' >";
		echo "<td align='left' width='100'> <b>Código Revenda</td>";
		echo "<td align='left'> <b>Descrição Revenda</td>";
		echo "<td align='left' width='100'> <b>Código Fabricante</td>";
		echo "<td align='left'> <b>Descrição Fabricante</td>";
		echo "<td align='left'> <b>Voltagem</td>";
		echo "<td align='left'> <b>Ação</td>";
		echo "</tr>";

		for($i = 0 ; $i < pg_numrows($res) ; $i++){
			$revenda_produto = pg_result($res,$i,revenda_produto);
			$revenda_referencia = pg_result($res,$i,revenda_referencia);
			$revenda_descricao  = pg_result($res,$i,revenda_descricao);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$voltagem   = pg_result($res,$i,voltagem);
	
				$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
	
			echo "<tr bgcolor='$cor' height='15'>";
			echo "<td align='left' width='100'>$revenda_referencia</td>";
			echo "<td align='left' >$revenda_descricao</td>";
			echo "<td align='left' width='100'>$produto_referencia</td>";
			echo "<td align='left' >$produto_descricao</td>";
			echo "<td align='lef' >$voltagem</td>";
			echo "<td align='lef' ><a href='?excluir=$revenda_produto'>Excluir</a></td>";
	
			echo "</tr>";
		}
		echo "</table>";
	}

}?>
	</div>
</div>
</td></tr></table>

<br clear=both>
<?


 include "rodape.php"; ?>
