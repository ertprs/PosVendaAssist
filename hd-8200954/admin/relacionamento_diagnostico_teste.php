<center>
<?
$body_onload = "onLoad=\"atualizaCombo('linha');\"";
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
//header("Pragma: no-cache, public");

$diagnostico = trim($_GET['diagnostico']);

if(strlen($diagnostico)>0){
	$sql ="UPDATE tbl_diagnostico set ativo='f' where diagnostico=$diagnostico";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(strlen($msg_erro)==0){
		$msg_erro="Apagado com sucesso!";
	}
}


$linha				= trim($_POST['linha']);
$familia	 		= trim($_POST['familia']);
$defeito_reclamado	= trim($_POST['defeito_reclamado']);
$defeito_constatado	= trim($_POST['defeito_constatado']);
$solucao			= trim($_POST['solucao']);
$btn_acao			= trim($_POST['btn_acao']);

if($linha=='0')				$msg_erro .="Escolha a linha<BR>";
if($familia=='0')			$msg_erro .="Escolha a familia<BR>";
if($defeito_reclamado=='0') $msg_erro .="Escolha o defeito reclamado<BR>";
if($defeito_constatado=='0')$msg_erro .="Escolha o defeito constatado<BR>";
if($solucao=='0')			$msg_erro .="Escolha a solução<BR>";


if(($btn_acao=="gravar") and (strlen($msg_erro)==0)){

	$numero_vezes = 100;
	for ($i=0;$i<$numero_vezes;$i++){
		$int_linha		= trim($_POST["integridade_linha_$i"]);
		$int_familia	= trim($_POST["integridade_familia_$i"]);
		$int_reclamado	= trim($_POST["integridade_defeito_reclamado_$i"]);
		$int_constatado = trim($_POST["integridade_defeito_constatado_$i"]);
		$int_solucao	= trim($_POST["integridade_solucao_$i"]);
		
		if (!isset($_POST["integridade_linha_$i"])) continue;
		if (strlen($int_linha)==0)		continue;
		if (strlen($int_familia)==0)	continue;
		if (strlen($int_reclamado)==0)	continue;
		if (strlen($int_constatado)==0) continue;
		if (strlen($int_solucao)==0)	continue;

		$aux_linha 		 		= $int_linha;
		$aux_familia	 		= $int_familia;
		$aux_defeito_reclamado	= $int_reclamado;
		$aux_defeito_constatado	= $int_constatado;
		$aux_solucao			= $int_solucao;

		$sql = "SELECT diagnostico 
				from tbl_diagnostico 
				where fabrica = $login_fabrica 
					and linha = $int_linha
					and familia = $int_familia
					and defeito_reclamado = $int_reclamado
					and defeito_constatado = $int_constatado
					and solucao = $int_solucao";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$diagnostico = pg_result($res,0,0);
			$sql = "UPDATE tbl_diagnostico 
					SET ativo='t'
					WHERE diagnostico = $diagnostico AND fabrica = $login_fabrica";
			//echo "$sql<br><br>";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)==0){$msg_erro="Adicionado com sucesso!";}

		}else{
		$sql = "INSERT INTO tbl_diagnostico (
							fabrica,
							linha,
							familia,
							defeito_reclamado,
							defeito_constatado,
							solucao, ativo
						) VALUES (
							$login_fabrica,
							$int_linha,
							$int_familia,
							$int_reclamado,
							$int_constatado,
							$int_solucao, 't'
						);";
		$res = @pg_exec ($con,$sql);
		//echo "$sql<br><br>";
		$msg_erro = pg_errormessage($con);
		//echo "$sql";
		if(strlen($msg_erro)==0){
			$msg_erro="Adicionado com sucesso!";

		}
		}
	}
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE DIAGNÓSTICOS";
include 'cabecalho.php';
?>

<script src="js/jquery-latest.pack.js" type="text/javascript"></script>
<!--<script src="js/jquery.cookie.js" type="text/javascript"></script>-->
<script src="js/jquery.treeview.pack.js" type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.flydom-3.0.6.js"></script>
<script type="text/javascript">

function atualizaCombo(tipo){
//verifica se o browser tem suporte a ajax
	try {
		ajax = new ActiveXObject("Microsoft.XMLHTTP");
	}
	catch(e) { 
		try {
			ajax = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(ex) {
			try {
				ajax = new XMLHttpRequest();
			}
			catch(exc){
				alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
			}
		}
	}

	//se tiver suporte ajax
	if(ajax) {
		//	alert('1');
		//deixa apenas o elemento 1 no option, os outros sï¿½ excluï¿½os
		//document.forms[0].linha.options.length = 1;
		eval("document.forms[0]."+tipo+".options.length = 1;");
		//opcoes ï¿½o nome do campo combo
		idOpcao  = document.getElementById("opcoes_"+tipo);
		//	 ajax.open("POST", "ajax_produto.php", true);
		
		ajax.open("GET","ajax_defeitos.php?tipo="+tipo);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			//alert('2');
			if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
			if(ajax.readyState == 4 ) {
				//alert('3');
				if(ajax.responseXML) {
					montaCombo(ajax.responseXML,tipo);
				//apï¿½ ser processado-chama fun
				}else {
					idOpcao.innerHTML = "Selecione erro";//caso não seja um arquivo XML emite a mensagem abaixo
				}
			}
		}
	//passa o cï¿½igo do produto escolhido
		var params = "tipo="+tipo;
		ajax.send(null);
	}
}

function montaCombo(obj,tipo){
	//alert('4');
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];
//			contï¿½do dos campos no arquivo XML
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione";
//			cria um novo option dinamicamente  
			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes_"+tipo);//atribui um ID a esse elemento
			novo.value = codigo;		//atribui um valor
			novo.text  = nome;//atribui um texto
//			document.forms[0].linha.options.add(novo);//adiciona
			eval("document.forms[0]."+tipo+".options.add(novo);");
//onovo elemento
		}
	} else { 
		idOpcao.innerHTML = "Não encontrado";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

function adicionaIntegridade() {

	if(document.getElementById('linha').value=="0")             { alert('Selecione a linha');             return false}
	if(document.getElementById('familia').value=="0")           { alert('Seleciona a família');           return false}
	if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
	if(document.getElementById('defeito_constatado').value=="0"){ alert('Selecione o defeito constatado');return false}
	if(document.getElementById('solucao').value=="0")           { alert('Selecione a solução');           return false}

	var tbl = document.getElementById('tbl_integridade');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	if (iteration>0){
		document.getElementById('tbl_integridade').style.display = "inline";
		document.getElementById('bnt_gravar_img').style.display = "inline";

	}

	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// COLUNA 1 - LINHA
	var celula = criaCelula(document.getElementById('linha').options[document.getElementById('linha').selectedIndex].text);
	celula.style.cssText = 'text-align: left; color: #000000;font-size:10px; background-color:#F7F5F0;';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'integridade_linha_' + iteration);
	el.setAttribute('id', 'integridade_linha_' + iteration);
	el.setAttribute('value',document.getElementById('linha').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'integridade_familia_' + iteration);
	el.setAttribute('id', 'integridade_familia_' + iteration);
	el.setAttribute('value',document.getElementById('familia').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'integridade_defeito_reclamado_' + iteration);
	el.setAttribute('id', 'integridade_defeito_reclamado_' + iteration);
	el.setAttribute('value',document.getElementById('defeito_reclamado').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
	el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
	el.setAttribute('value',document.getElementById('defeito_constatado').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'integridade_solucao_' + iteration);
	el.setAttribute('id', 'integridade_solucao_' + iteration);
	el.setAttribute('value',document.getElementById('solucao').value);
	celula.appendChild(el);

	linha.appendChild(celula);
	
	// coluna 2 - FAMÍLIA
	celula = criaCelula(document.getElementById('familia').options[document.getElementById('familia').selectedIndex].text);
	celula.style.cssText = 'text-align: left; color: #000000;font-size:10px; background-color:#F7F5F0;';
	linha.appendChild(celula);

	// coluna 3 - DEFEITO RECLAMADO
	var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px; background-color:#F7F5F0;';
	linha.appendChild(celula);

	// coluna 4 - DEFEITO CONSTATADO
	var celula = criaCelula(document.getElementById('defeito_constatado').options[document.getElementById('defeito_constatado').selectedIndex].text);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px; background-color:#F7F5F0;';
	linha.appendChild(celula);

	// coluna 5 - SOLUCAO
	var celula = criaCelula(document.getElementById('solucao').options[document.getElementById('solucao').selectedIndex].text);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px; background-color:#F7F5F0;';
	linha.appendChild(celula);

	// coluna 6 - botacao
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: right; color: #000000;font-size:10px; background-color:#F7F5F0;';

	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerIntegridade(this);};
	celula.appendChild(el);
	linha.appendChild(celula);

	// finaliza linha da tabela
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	/*linha.style.cssText = 'color: #404e2a;';*/
	tbl.appendChild(tbody);

	//document.getElementById('solucao').selectedIndex=0;
}

function removerIntegridade(iidd){
	var tbl = document.getElementById('tbl_integridade');
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

<style type="text/css">
.treeview, .treeview ul { 
	padding: 0;
	margin: 0;
	list-style: none;
}	

.treeview li { 
	margin: 0;
	padding: 3px 0pt 3px 16px;
}

ul.dir li { padding: 2px 0 0 16px; }
		
.treeview li { background: url(imagens/treeview/tv-item.gif) 0 0 no-repeat; }
.treeview .collapsable { background-image: url(imagens/treeview/tv-collapsable.gif); }
.treeview .expandable { background-image: url(imagens/treeview/tv-expandable.gif); }
.treeview .last { background-image: url(imagens/treeview/tv-item-last.gif); }
.treeview .lastCollapsable { background-image: url(imagens/treeview/tv-collapsable-last.gif); }
.treeview .lastExpandable { background-image: url(imagens/treeview/tv-expandable-last.gif); }

#red.treeview li { background: url(imagens/treeview/red/tv-item.gif) 0 0 no-repeat; }
#red.treeview .collapsable { background-image: url(imagens/treeview/red/tv-collapsable.gif); }
#red.treeview .expandable { background-image: url(imagens/treeview/red/tv-expandable.gif); }
#red.treeview .last { background-image: url(imagens/treeview/red/tv-item-last.gif); }
#red.treeview .lastCollapsable { background-image: url(imagens/treeview/red/tv-collapsable-last.gif); }
#red.treeview .lastExpandable { background-image: url(imagens/treeview/red/tv-expandable-last.gif); }

#black.treeview li { background: url(imagens/treeview/black/tv-item.gif) 0 0 no-repeat; }
#black.treeview .collapsable { background-image: url(imagens/treeview/black/tv-collapsable.gif); }
#black.treeview .expandable { background-image: url(imagens/treeview/black/tv-expandable.gif); }
#black.treeview .last { background-image: url(imagens/treeview/black/tv-item-last.gif); }
#black.treeview .lastCollapsable { background-image: url(imagens/treeview/black/tv-collapsable-last.gif); }
#black.treeview .lastExpandable { background-image: url(imagens/treeview/black/tv-expandable-last.gif); }

#gray.treeview li { background: url(imagens/treeview/gray/tv-item.gif) 0 0 no-repeat; }
#gray.treeview .collapsable { background-image: url(imagens/treeview/gray/tv-collapsable.gif); }
#gray.treeview .expandable { background-image: url(imagens/treeview/gray/tv-expandable.gif); }
#gray.treeview .last { background-image: url(imagens/treeview/gray/tv-item-last.gif); }
#gray.treeview .lastCollapsable { background-image: url(imagens/treeview/gray/tv-collapsable-last.gif); }
#gray.treeview .lastExpandable { background-image: url(imagens/treeview/gray/tv-expandable-last.gif); }

#treecontrol { margin: 1em 0; }
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
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
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
.espaco{
	padding-left:100px;
}
</style>
<body onLoad="atualizaCombo('linha');">
<?

echo "<form name='frm_cadastro' method='post' action='<? echo $PHP_SELF?>'>";
echo "<table width='700' border='0' align='center' cellpadding='3' cellspacing='3' class='formulario'>";
if (strlen($msg_erro) > 0) { 
	echo "<tr class='msg_erro'>";
	echo "<td colspan='2'>".$msg_erro."</td>"; 
	echo "</tr>";
} 
echo "<tr>";
echo "<td colspan='2' class='titulo_tabela'>Relacionamento de Diagnósticos</td>";
echo "</tr>";
echo "<tr>";
echo "<td class='espaco'>Linha*<BR>";
$sql ="SELECT linha, nome from tbl_linha where fabrica=$login_fabrica order by nome";
$res = pg_exec ($con,$sql);
echo "<select name='linha' id='linha' style='width: 150px;' class='frm'>";
echo "<option id='opcoes_linha' value='0'>Linha</option>";
/*for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$nome = trim(pg_result($res,$y,nome));
	echo "<option value='$linha'"; 
	if ($linha == $aux_linha)
		echo " SELECTED ";
	echo ">$nome</option>";
}*/
echo "</select>";
echo "<BR><a href=\"relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=linha&keepThis=trueTB_iframe=true&height=400&width=500\" 
title=\"Manutenção de Linhas\" class=\"thickbox\"><FONT size='1'>Inserir/Alterar</font></a> &nbsp;&nbsp;&nbsp;";
echo " <a href=\"javascript:atualizaCombo('linha');\"><FONT size='1'>Atualizar</font></a>";

echo "</td>";
echo "<td>Família*<BR>";
$sql ="SELECT familia, descricao from tbl_familia where fabrica=$login_fabrica order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='familia' id='familia' style='width: 150px;' class='frm'>";
echo "<option value='0'  id='opcoes_familia' >Familia</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$familia			= trim(pg_result($res,$y,familia));
	$descricao			= trim(pg_result($res,$y,descricao));
	echo "<option value='$familia'"; 
	if ($familia == $aux_familia) echo " SELECTED ";
	echo ">$descricao</option>";
}
echo "</select>";
echo "<BR><a href=\"relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=familia&keepThis=trueTB_iframe=true&height=400&width=500\" 
title=\"Manutenção de Familia\" class=\"thickbox\"><FONT size='1'>Inserir/Alterar</font></a> &nbsp;&nbsp;&nbsp;";
echo " <a href=\"javascript:atualizaCombo('familia');\"><FONT size='1'>Atualizar</font></a>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='2' class='espaco'>Defeito Reclamado*<BR>";
$sql ="SELECT defeito_reclamado, descricao, duvida_reclamacao from tbl_defeito_reclamado where fabrica=$login_fabrica and ativo='t'";
if($login_fabrica==6){ $sql .=" AND duvida_reclamacao='RC' ";}
$sql .=" AND duvida_reclamacao <> 'CC'  order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='defeito_reclamado' id='defeito_reclamado' style='width: 300px;' class='frm'>";
echo "<option value='0'  id='opcoes_defeito_reclamado' >Defeito Reclamado</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$defeito_reclamado          = trim(pg_result($res,$y,defeito_reclamado));
	$descricao = trim(pg_result($res,$y,descricao));
	$duvida_reclamacao = trim(pg_result($res,$y, duvida_reclamacao));
	echo "<option value='$defeito_reclamado'";
	if ($defeito_reclamado == $aux_defeito_reclamado) echo " SELECTED ";
	echo ">$descricao</option>";
}
echo "</select>";
echo "<BR><a href=\"relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=defeito_reclamado&keepThis=trueTB_iframe=true&height=400&width=500\" 
title=\"Manutenção de Defeito Reclamado\" class=\"thickbox\"><FONT size='1'>Inserir/Alterar</font></a> &nbsp;&nbsp;&nbsp;";
echo " <a href=\"javascript:atualizaCombo('defeito_reclamado');\"><FONT size='1'>Atualizar</font></a>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='2' class='espaco'>Defeito Constatado*<BR>";
$sql ="SELECT defeito_constatado,descricao,codigo from tbl_defeito_constatado where fabrica=$login_fabrica and ativo='t' order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='defeito_constatado' id='defeito_constatado' style='width: 300px;' class='frm'>";
echo "<option value='0'  id='opcoes_defeito_constatado' >Defeito Constatado</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$defeito_constatado          = trim(pg_result($res,$y,defeito_constatado));
	$descricao = trim(pg_result($res,$y,descricao));
	$codigo    = trim(pg_result($res,$y,codigo));
	echo "<option value='$defeito_constatado'";
	if ($defeito_constatado == $aux_defeito_constatado) echo " SELECTED ";
	echo ">";
	if($login_fabrica==30) echo "$codigo - ";
	
	echo "$descricao</option>";
}
echo "</select>";
echo "<BR><a href=\"relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=defeito_constatado&keepThis=trueTB_iframe=true&height=400&width=500\" 
title=\"Manutenção de Defeito Constatado\" class=\"thickbox\"><FONT size='1'>Inserir/Alterar</font></a> &nbsp;&nbsp;&nbsp;";
echo " <a href=\"javascript:atualizaCombo('defeito_constatado');\"><FONT size='1'>Atualizar</font></a>";

echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='2' class='espaco'>Solução*<BR>";
$sql ="SELECT solucao, descricao from tbl_solucao where fabrica=$login_fabrica and ativo='t' order by descricao";
$res = pg_exec ($con,$sql);
echo "<select name='solucao' id='solucao' style='width: 300px;' class='frm'>";
echo "<option value='0' id='opcoes_solucao' >Solução</option>";
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$solucao         = trim(pg_result($res,$y,solucao));
	$descricao = trim(pg_result($res,$y,descricao));
	echo "<option value='$solucao'";
	if ($solucao == $aux_solucao) echo " SELECTED ";
	echo ">$descricao</option>";
}
echo "</select>";
echo "<BR><a href=\"relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=solucao&keepThis=trueTB_iframe=true&height=400&width=500\" 
title=\"Manutenção de Solução\" class=\"thickbox\"><FONT size='1'>Inserir/Alterar</font></a> &nbsp;&nbsp;&nbsp;";
echo " <a href=\"javascript:atualizaCombo('solucao');\"><FONT size='1'>Atualizar</font></a>";

echo "</td>";
echo "</tr>";
echo "<tr>";
?>
<td align='center' colspan='3'>
	<input type='button' onclick="javascript: adicionaIntegridade()" value='Adicionar' name='btn_adicionar'>
	&nbsp;&nbsp;&nbsp;
	<input type='reset' value='Limpar'>
</td>
<?
echo "</tr>";
echo "</table>";
echo "</form>";
#########################################
?>
<br>
<br>
<form name='frm_diagnostico' method='post' action='<? echo $PHP_SELF?>'>
	<input type='hidden' name='btn_acao' value=''>
	<table align="center" width="700" cellspacing="1" class="tabela" id='tbl_integridade' style='display:none'>
		<thead>
			<tr class='titulo_coluna'>
				<td align='center'>Linha</td>
				<td align='center'>Família</td>
				<td align='center'>Defeito Reclamado</td>
				<td align='center'>Defeito Constatado</td>
				<td align='center'>Solução</td>
				<td align='center'>Ações</td>
			</tr>
		</thead>
	</table>
	<center>
		<input type='button' value='Gravar' id='bnt_gravar_img' border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_diagnostico.btn_acao.value == '' ) { document.frm_diagnostico.btn_acao.value='gravar' ; document.frm_diagnostico.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;display:none">
	</center>
</form>

<?php
###########################################
$sql ="SELECT 
			linha, 
			nome , codigo_linha
		FROM tbl_linha 
		WHERE linha IN (
						SELECT distinct(linha) 
							FROM tbl_diagnostico 
							WHERE fabrica=$login_fabrica and ativo='t') 
		ORDER BY NOME";
$num=pg_numrows($res);

echo '<table align="center" width="700" cellspacing="1" class="tabela">';
echo "<tr class='titulo_coluna'>";
echo "<td colspan='$num'>Escolha a Linha que você deseja analisar</td>";
echo "</tr>";

$res = pg_exec ($con,$sql);
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$linha_descricao = trim(pg_result($res,$y,nome));
		$a= "#"."$linha_descricao";
	$codigo_linha = trim(pg_result($res,$y,codigo_linha));
	$cor = ($y % 2) ? "#F7F5F0" : "#F1F4FA";
	echo "<tr bgcolor='$cor'>";
	echo "<td style='padding-left:50px;'><font color='#000000'> <A href='$PHP_SELF?linha_abre=$linha'>$codigo_linha - $linha_descricao</A></td>";
	#LINHA
	echo "</tr>";
}
echo "</table>";

//echo "<BR><BR><center><a href='relacionamento_diagnostico_xls.php'><font color='#000000' face='verdana' size='1'>Clique aqui para fazer o download da tabela de relacionamento de integridade</font></a></center>";

//identação do diagnostico INICIO
$linha_abre = $_GET['linha_abre'];
if(strlen($linha_abre)>0){
echo "<br>";
echo '<table align="center" width="700" cellspacing="1" class="tabela">';
echo "<tr class='titulo_coluna'>";
echo "<td  colspan='6' style='font-size:14px;'>Diagnósticos Cadastrados</td>";
echo "</tr>";
echo "<tr  class='titulo_coluna'>";
echo "<td align='center' width='120'>Linha</td>";
echo "<td align='center' width='120' >Família</td>";
echo "<td align='center' width='120'>Defeito Reclamado</td>";
echo "<td align='center' width='120'>Defeito Constatado</td>";
echo "<td align='center' width='200'>Solução</td>";
echo "<td align='center'>Ações</td>";
echo "</tr>";
#LINHA

$sql ="SELECT 
			linha, 
			nome , codigo_linha
		FROM tbl_linha 
		WHERE linha =$linha_abre
		ORDER BY NOME";
$res = pg_exec ($con,$sql);
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$linha_descricao = trim(pg_result($res,$y,nome));
	$codigo_linha = trim(pg_result($res,$y,codigo_linha));
	echo "<tr>";
	echo "<td align='left' bgcolor='#7092BE'><B><A name='$linha_descricao'>$codigo_linha - $linha_descricao</A></B></td>";
	echo "<td align='right' bgcolor='#7092BE' colspan='5'><A href='#inicio'>
	<font color='#ffffff' size='1'>Voltar ao topo</font></a></td>";
	echo "</tr>";
#LINHA
#FAMILIA	
	$sqlfamilia ="SELECT 
						familia, 
						descricao ,codigo_familia
					FROM tbl_familia 
					WHERE familia IN (
										SELECT DISTINCT(familia) 
										FROM tbl_diagnostico 
										WHERE fabrica=$login_fabrica AND linha=$linha and ativo='t'
										)
					ORDER BY descricao";
	$resfamilia = @pg_exec ($con,$sqlfamilia);
	for ($x = 0 ; $x < pg_numrows($resfamilia) ; $x++){
		$familia           = trim(pg_result($resfamilia,$x,familia));
		$descricao_familia = trim(pg_result($resfamilia,$x,descricao));
		$codigo_familia = trim(pg_result($resfamilia,$x,codigo_familia));
		echo "<tr>";
		echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
		echo "<td align='left' colspan='8' bgcolor='#F7F5F0' colspan='5'>
		<B><A name='$descricao_familia'>$codigo_familia - $descricao_familia</B></A></td>";
/*		echo "<td  bgcolor='#819CB4' colspan='3'>&nbsp;</td>";
		echo "<td  bgcolor='#819CB4'><A href='#inicio'><font color='#ffffff'>Voltar ao topo</font></a></td>";*/
		echo "</tr>";
#DEFEITO_RECLAMADO
		$sqldefeito_reclamado = "SELECT 
										defeito_reclamado, 
										descricao , ativo,codigo
									FROM tbl_defeito_reclamado 
									WHERE defeito_reclamado IN (
																SELECT DISTINCT(defeito_reclamado) 
																	FROM tbl_diagnostico 
																	WHERE fabrica=$login_fabrica 
																			AND linha=$linha 
																			AND familia=$familia and ativo='t')
									ORDER BY descricao";
			$resdefeito_reclamado = pg_exec ($con,$sqldefeito_reclamado);
			for ($w = 0 ; $w < pg_numrows($resdefeito_reclamado) ; $w++){
				$defeito_reclamado  = trim(pg_result($resdefeito_reclamado,$w,defeito_reclamado));
				$descricao_defeito_reclamado = trim(pg_result($resdefeito_reclamado,$w,descricao));
				$ativo_defeito_reclamado = trim(pg_result($resdefeito_reclamado,$w,ativo));
				if($ativo_defeito_reclamado == "f"){
					$ativo_defeito_reclamado = "<font color='#CC0033'> (Inativo)</font>";
				}else{
					$ativo_defeito_reclamado="";
				}
				$codigo_reclamado = trim(pg_result($resdefeito_reclamado,$w,codigo));
				echo "<tr>";
				echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
				echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
				echo "<td align='left' colspan='4' bgcolor='#F1F4FA'>
				<B>$codigo_reclamado - $descricao_defeito_reclamado</B>$ativo_defeito_reclamado</td>";
				//echo "<td  bgcolor='#819CB4'> &nbsp;</td>";
				echo "</tr>";
#DEFEITO_CONSTATADO
				$sqldefeito_constatado ="SELECT defeito_constatado, 
												descricao , ativo, codigo
											FROM tbl_defeito_constatado 
											WHERE defeito_constatado IN (
																		SELECT DISTINCT(defeito_constatado) 
																		FROM tbl_diagnostico 
																		WHERE fabrica=$login_fabrica 
																		AND linha=$linha 
																		AND familia=$familia 
																		AND defeito_reclamado=$defeito_reclamado and ativo='t')
											ORDER BY descricao";
				$resdefeito_constatado = pg_exec ($con,$sqldefeito_constatado);
						
				for ($z = 0 ; $z < pg_numrows($resdefeito_constatado) ; $z++){
					$defeito_constatado           = trim(pg_result($resdefeito_constatado,$z,defeito_constatado));
					$descricao_defeito_constatado = trim(pg_result($resdefeito_constatado,$z,descricao));
					$ativo_defeito_constatado = trim(pg_result($resdefeito_constatado,$z,ativo));
					$codigo_constatado = trim(pg_result($resdefeito_constatado,$z,codigo));
					if($ativo_defeito_constatado == "f"){
						$ativo_defeito_constatado = " <font color='#CC0033'> (Inativo)</font>";
					}else{
						$ativo_defeito_constatado = "";}
					echo "<tr>";
					echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
					echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
					echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
					echo "<td align='left' bgcolor='#819CB4' colspan='3'>
					<font color='#ffffff'><B>$codigo_constatado - $descricao_defeito_constatado</B>$ativo_defeito_constatado</td>";
					//echo "<td bgcolor='#819CB4'> &nbsp;</td>";
					echo "</tr>";
#SOLUCAO
					$sqlsolucao ="SELECT solucao, 
										descricao ,ativo
									FROM tbl_solucao 
									WHERE solucao IN (
													SELECT DISTINCT(solucao) 
													FROM tbl_diagnostico 
													WHERE fabrica=$login_fabrica 
													AND linha=$linha 
													AND familia=$familia 
													AND defeito_reclamado=$defeito_reclamado
													AND defeito_constatado=$defeito_constatado and ativo='t')
									ORDER BY descricao";
					$ressolucao = pg_exec ($con,$sqlsolucao);
					for ($k = 0 ; $k < pg_numrows($ressolucao) ; $k++){
						$solucao          = trim(pg_result($ressolucao,$k,solucao));
						$descricao_solucao = trim(pg_result($ressolucao,$k,descricao));
						$ativo_solucao = trim(pg_result($ressolucao,$k,ativo));
						if($ativo_solucao == "f"){
							$ativo_solucao = "<font color='#CC0033'> (Inativo)</font>";
						}else{
							$ativo_solucao="";
						}
						$sqldiagnostico="SELECT diagnostico from tbl_diagnostico where fabrica=$login_fabrica and linha=$linha and familia=$familia and defeito_reclamado=$defeito_reclamado and defeito_constatado=$defeito_constatado and solucao=$solucao";
						$resdiagnostico=@pg_exec($con,$sqldiagnostico);
						$diagnostico          = trim(pg_result($resdiagnostico,0,diagnostico));
						echo "<tr>";
						echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
						echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
						echo "<td bgcolor='#ced7e7'>&nbsp;</td>";
						echo "<td bgcolor='#ced7e7'> &nbsp;</td>";
						echo "<td align='left' bgcolor='#D6DFF0'><font color='#000000'><B>$descricao_solucao</B>$ativo_solucao</td>";
						echo "<td bgcolor='#FFFFFF'><input type='button' value='Apagar' onclick=\"window.location='$PHP_SELF?diagnostico=$diagnostico'\"></td>";
						echo "</tr>";
					}
#SOLUCAO
				}
#DEFEITO_CONSTATADO
			}
#DEFEITO_RECLAMADO
	}
#FAMILIA

}
echo "</table>";
}
//identação do diagnostico FIM

/* Feito por Fábio em  19/07/2007 - Mas Rogério não gostou
//identação do diagnostico INICIO
$linha_abre = $_GET['linha_abre'];
if(strlen($linha_abre)>0){
echo "<BR><BR>";
echo "<div style='width:700px;align:left;text-align:left'>";
echo '<ul id="browser" class="dir">';

#LINHA
$sql ="SELECT 
			linha, 
			nome 
		FROM tbl_linha 
		WHERE linha =$linha_abre
		ORDER BY NOME";
$res = pg_exec ($con,$sql);
for ($y = 0 ; $y < pg_numrows($res) ; $y++){
	$linha           = trim(pg_result($res,$y,linha));
	$linha_descricao = trim(pg_result($res,$y,nome));

	echo "<li><img src='imagens/treeview/folder.gif' /> $linha_descricao \n";
	echo "<ul> \n";

#LINHA
#FAMILIA	
	$sqlfamilia ="SELECT 
						familia, 
						descricao 
					FROM tbl_familia 
					WHERE familia IN (
										SELECT DISTINCT(familia) 
										FROM tbl_diagnostico 
										WHERE fabrica=$login_fabrica AND linha=$linha and ativo='t'
										)
					ORDER BY descricao";
	$resfamilia = @pg_exec ($con,$sqlfamilia);
	for ($x = 0 ; $x < pg_numrows($resfamilia) ; $x++){
		$familia           = trim(pg_result($resfamilia,$x,familia));
		$descricao_familia = trim(pg_result($resfamilia,$x,descricao));
		echo "<li class='closed'><img src='imagens/treeview/folder.gif' /> $descricao_familia</li> \n";
		echo "<ul> \n";


#DEFEITO_RECLAMADO
		$sqldefeito_reclamado = "SELECT 
										defeito_reclamado, 
										descricao 
									FROM tbl_defeito_reclamado 
									WHERE defeito_reclamado IN (
																SELECT DISTINCT(defeito_reclamado) 
																	FROM tbl_diagnostico 
																	WHERE fabrica=$login_fabrica 
																			AND linha=$linha 
																			AND familia=$familia and ativo='t')
									ORDER BY descricao";
			$resdefeito_reclamado = pg_exec ($con,$sqldefeito_reclamado);
			for ($w = 0 ; $w < pg_numrows($resdefeito_reclamado) ; $w++){
				$defeito_reclamado  = trim(pg_result($resdefeito_reclamado,$w,defeito_reclamado));
				$descricao_defeito_reclamado = trim(pg_result($resdefeito_reclamado,$w,descricao));

				echo "<li ><img src='imagens/treeview/folder.gif' /> $descricao_defeito_reclamado \n";
				echo "<ul> \n";
#DEFEITO_CONSTATADO
				$sqldefeito_constatado ="SELECT defeito_constatado, 
												descricao 
											FROM tbl_defeito_constatado 
											WHERE defeito_constatado IN (
																		SELECT DISTINCT(defeito_constatado) 
																		FROM tbl_diagnostico 
																		WHERE fabrica=$login_fabrica 
																		AND linha=$linha 
																		AND familia=$familia 
																		AND defeito_reclamado=$defeito_reclamado and ativo='t')
											ORDER BY descricao";
				$resdefeito_constatado = pg_exec ($con,$sqldefeito_constatado);

				for ($z = 0 ; $z < pg_numrows($resdefeito_constatado) ; $z++){
					$defeito_constatado           = trim(pg_result($resdefeito_constatado,$z,defeito_constatado));
					$descricao_defeito_constatado = trim(pg_result($resdefeito_constatado,$z,descricao));

					#SOLUCAO

					echo "<li><img src='imagens/treeview/folder.gif' /> $descricao_defeito_constatado \n";
					echo "<ul> \n";

					$sqlsolucao ="SELECT solucao, 
										descricao 
									FROM tbl_solucao 
									WHERE solucao IN (
													SELECT DISTINCT(solucao) 
													FROM tbl_diagnostico 
													WHERE fabrica=$login_fabrica 
													AND linha=$linha 
													AND familia=$familia 
													AND defeito_reclamado=$defeito_reclamado
													AND defeito_constatado=$defeito_constatado and ativo='t')
									ORDER BY descricao";
					$ressolucao = pg_exec ($con,$sqlsolucao);

					for ($k = 0 ; $k < pg_numrows($ressolucao) ; $k++){
						$solucao          = trim(pg_result($ressolucao,$k,solucao));
						$descricao_solucao = trim(pg_result($ressolucao,$k,descricao));
						$sqldiagnostico="SELECT diagnostico from tbl_diagnostico where fabrica=$login_fabrica and linha=$linha and familia=$familia and defeito_reclamado=$defeito_reclamado and defeito_constatado=$defeito_constatado and solucao=$solucao";
						$resdiagnostico=@pg_exec($con,$sqldiagnostico);
						$diagnostico          = trim(pg_result($resdiagnostico,0,diagnostico));

						echo " <li><img src='imagens/treeview/file.gif' /> $descricao_solucao";
						echo " <a href='$PHP_SELF?diagnostico=$diagnostico'><img border='0' src='imagens/delete_2.gif' alt='Apagar Diagóstico' align='absmiddle'></A>";
						echo "</li> \n";
					}
					echo "</ul> \n";
					echo "</li> \n";
#SOLUCAO
				}
			echo "</ul> \n";
			echo "</li> \n";
#DEFEITO_CONSTATADO
			}
#DEFEITO_RECLAMADO
		echo "</ul> \n";
		echo "</li> \n";
	}
#FAMILIA
		echo "</ul> \n";
		echo "</li> \n";
}
echo "</ul> \n";
echo "</div>";
}
//identação do diagnostico FIM
*/
include "rodape.php";
?>