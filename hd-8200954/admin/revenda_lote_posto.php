<center>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_GET["excluir"]) > 0) {
	$revenda = $_GET["excluir"];
	$posto   = $_GET["posto"];
	$sql = "DELETE FROM tbl_revenda_posto WHERE posto = $posto AND revenda = $revenda AND fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	header("Location:$PHP_SELF");
}

if (strlen($_POST["acao"]) > 0) $acao = trim( $_POST["acao"]);
if (strlen($_POST["ajax"]) > 0) $ajax = trim($_POST["ajax"]);

if ($acao == "gravar" AND $ajax == "sim") {

	$xcodigo_posto = trim($_POST['codigo_posto']);
	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);

	if(strlen($xcodigo_posto)==0) $msg_erro .= "Por favor selecione o posto<br>";
	if(strlen($xrevenda_cnpj)==0) $msg_erro .= "Por favor selecione a revenda<br>";

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
		$sql =  "SELECT tbl_revenda.revenda
			 FROM   tbl_revenda
			 WHERE  tbl_revenda.cnpj = '$xrevenda_cnpj' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
		}else{
			$revenda = "null";
			$msg_erro .= " Favor informe a revenda correta. ";
		}
	}

	$sql = "SELECT * FROM tbl_revenda_posto WHERE posto = $posto AND revenda = $revenda AND fabrica=$login_fabrica";
	$res = @pg_exec ($con,$sql);
	if(@pg_numrows($res)>0) $msg_erro = "Posto já cadastrado para atender esta revenda<br>";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($lote_revenda) == 0) {
			$sql =	"INSERT INTO tbl_revenda_posto (
						posto             ,
						revenda           ,
						fabrica           ,
						ativo
					) VALUES (
						$posto              ,
						$revenda            ,
						$login_fabrica      ,
						TRUE
					)";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if(strpos($msg_erro,"duplicate key violates")) $msg_erro = "Posto já cadastrado para atender esta revenda<br>";
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

$layout_menu = "callcenter";
$title       = "CADASTRO DE POSTO PARA REVENDA";
include "cabecalho.php";

?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}

	$("#codigo_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	$("#nome_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
	});

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

});



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
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
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


<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<?

$qtde_item = 0;
echo "<div id='erro' style='visibility:hidden;opacity:0.85' class='Erro'></div>";
echo "<table  align='center' width='700' border='0' cellspacing='0'  class='formulario'>";
if(strlen($msg_erro)>0){
	echo "<tr height='20' class='msg_erro'>";
	echo "<td colspan='4'>$msg_erro</td>";
	echo "</tr>";
}
echo "<tr height='20' class='titulo_tabela'>";
echo "<td align='right' colspan='3'><b>Posto que atende Revenda</b></td>";
echo "<td align='right'><a href='revenda_inicial.php'>Menu de Revendas</a></td>";
echo "</tr>";

echo "<tr><td colspan='4'><br>";
$aba = 6;
include "revenda_cabecalho.php";
echo "<br>&nbsp;</td></tr>";

echo "<tr height='20'>";
echo "<td align='right' ><b>CNPJ da Revenda</b>&nbsp;</td>";
echo "<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj'  maxlength='18' value='$cnpj' class='frm'>&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\"></td>";
echo "<td align='right' ><b>Nome da Revenda</b>&nbsp;</td>";
echo "<td align='left'><input type='text' name='revenda_nome' id='revenda_nome'size='40' maxlength='60' value='$nome'class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>";
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

echo "<tr>";
echo "<td align='right'><b>Codigo Posto</b>&nbsp;</td>";
echo "<td align='left' ><input type='text' name='codigo_posto' id='codigo_posto' maxlength='14' value='$codigo_posto' class='frm' onFocus=\"nextfield ='nome_posto'\" >&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\"></td>";
echo "<td align='right'><b>Nome</b>&nbsp;</td>";
echo "<td align='left' ><input type='text' name='nome_posto' id='nome_posto' size='40' maxlength='60' value='$nome_post' class='frm' onFocus=\"nextfield ='condicao'\">&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'nome')\"></td>";
echo "</tr>";

echo "<table class='formulario' align='center' width='700' border='0'height='40'>";
echo "<tr>";
echo "<td width='50' valign='middle'  align='LEFT' colspan='4'><input type='button' name='btn_acao'  value='Gravar' onClick=\"if (this.value!='Gravar'){ alert('Aguarde');}else {this.value='Gravando...'; gravar(this.form,'sim','$PHP_SELF','nao');}\" style=\"width: 150px;\"></td>";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
?>


<?
$sql = "SELECT  tbl_revenda.revenda,
		tbl_revenda.nome          AS revenda_nome,
		tbl_revenda.cnpj          AS revenda_cnpj,
		tbl_revenda_fabrica.email AS revenda_email,
		tbl_revenda_fabrica.senha AS revenda_senha,
		tbl_posto.posto,
		tbl_posto.nome,
		tbl_posto_fabrica.codigo_posto,
		tbl_revenda_posto.ativo
	FROM  tbl_revenda_posto
	JOIN  tbl_revenda USING(revenda)
	JOIN  tbl_posto   USING(posto)
	JOIN  tbl_revenda_fabrica ON tbl_revenda_fabrica.revenda = tbl_revenda.revenda AND tbl_revenda_fabrica.fabrica = $login_fabrica
	JOIN  tbl_posto_fabrica   ON tbl_posto_fabrica.posto     = tbl_posto.posto     AND tbl_posto_fabrica.fabrica   = $login_fabrica
	WHERE tbl_revenda_posto.fabrica = $login_fabrica";

$res = pg_exec ($con,$sql) ;
if (pg_numrows($res) > 0) {
	echo "<form name='frm_revenda' method='post' action='$PHP_SELF'>";
	echo "<br><table class='tabela' align='center' width='700' border='0' cellspacing='1'>";

	echo "<tr class='titulo_coluna' height='25'>";
	echo "<td align='left'>Revenda";
	echo "<td align='left'>Posto";
	echo "<td align='left'> Ativo";
	echo "<td> <b>Ação</td>";
	echo "</tr>";

	$qtde_item = pg_numrows($res);
	for ($i = 0 ; $i<$qtde_item ; $i++) {
		$nome          = pg_result ($res,$i,nome);
		$posto         = pg_result ($res,$i,posto);
		$codigo_posto  = pg_result ($res,$i,codigo_posto);
		$revenda       = pg_result ($res,$i,revenda);
		$revenda_nome  = pg_result ($res,$i,revenda_nome);
		$revenda_cnpj  = pg_result ($res,$i,revenda_cnpj);
		$ativo         = pg_result ($res,$i,ativo);
		$revenda_email = pg_result ($res,$i,revenda_email);
		$revenda_senha = pg_result ($res,$i,revenda_senha);
		
		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		if($ativo == "t") $ativo = "Sim";
		else              $ativo = "Não";

		echo "<tr bgcolor='$cor' height='20'>";
		echo "<td align='left'>$revenda_cnpj -  $revenda_nome </td>";
		echo "<td align='left'>$codigo_posto - $nome </td>";
		echo "<td align='left'>$ativo";
		echo "<input type=\"hidden\" name=\"revenda_email_$i\" value='$revenda_email'>";
		echo "<input type=\"hidden\" name=\"revenda_senha_$i\" value='$revenda_senha'>";
		echo "<input type=\"hidden\" name=\"revenda_cnpj_$i\" value='$revenda_cnpj'>";
		echo "</td>";
		echo "<td><a href='$PHP_SELF?excluir=$revenda&posto=$posto'>Excluir</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		?>
		<a href='javascript: alert("Atenção, irá abrir uma nova janela para que se trabalhe como se fosse esta revenda ! "+ document.frm_revenda.revenda_cnpj_<?echo $i;?>.value); document.frm_login.login.value = document.frm_revenda.revenda_email_<?echo $i;?>.value ; document.frm_login.senha.value = document.frm_revenda.revenda_senha_<?echo $i;?>.value ; document.frm_login.submit() ; document.location = "<? echo $PHP_SELF ?>";'>LOGAR</a>
<?
		echo "</td>";

		echo "</tr>";
	}
	echo "</table><br>";
	echo "</form>";
}else{
	echo "<center><font color='#FF0000' size='3'>Nenhum posto cadastrado</font></center>";
}

?>

<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
<input type="hidden" name="login">
<input type="hidden" name="senha">
<input type="hidden" name="btnAcao" value="Enviar">
</form>

<? include "rodape.php"; ?>
