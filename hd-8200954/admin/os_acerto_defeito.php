<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastro";

include 'autentica_admin.php';

include 'funcoes.php';

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);


if (strlen($_POST['os']) > 0)    $os     = trim($_POST['os'])    ;
if (strlen($_GET['os']) > 0)     $os     = trim($_GET['os'])     ;
if (strlen($_POST['sua_os']) > 0)$sua_os = trim($_POST['sua_os']);
if (strlen($_GET['sua_os']) > 0) $sua_os = trim($_GET['sua_os']) ;


$btn_acao = $_POST['gravar'];

if($btn_acao == 'Gravar'){
	$qtde_item = $_POST['qtde_item'];
	if($qtde_item > 0){
		for($i=0;$i<$qtde_item;$i++){
			$chk        = $_POST ['chk_' . $i] ;
			$os         = $_POST ['os_' . $i] ;
			$defeito_reclamado     = $_POST ['defeito_reclamado_' . $i] ;
			$defeito_constatado    = $_POST ['defeito_constatado_' . $i] ;
			$solucao_os            = $_POST ['solucao_os_' . $i] ;
			if($chk == 'ok'){

				if(strlen($defeito_reclamado) == 0){ $msg_erro= "Por favor, entre com o defeito reclamado na linha $i"; }
				if(strlen($defeito_constatado) == 0){ $msg_erro= "Por favor, entre com o defeito constatado na linha $i"; }
				if(strlen($solucao_os) == 0){$msg_erro= "Por favor, entre com solução na linha $i"; }

//				echo "$chk - $os - $defeito_reclamado - $defeito_constatado - $solucao_os<br>";
				
				$res = pg_exec ($con,"BEGIN TRANSACTION");
				
				$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado    ,
											defeito_constatado = $defeito_constatado,
											solucao_os = $solucao_os                
								WHERE os = $os 
								AND fabrica = $login_fabrica; ";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

//				echo "$sql<br>";
				if(strlen($msg_erro) == 0){
					$res = pg_exec ($con,"COMMIT TRANSACTION");
				}else{
					$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}


//if(strlen($os)==0)$body_onload = "onload = 'javascript: document.frm_os.posto_codigo.focus()'";
$title       = "OS´s"; 
$layout_menu = 'cadastro';

include "cabecalho.php";

?>



<!--========================= AJAX ==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script >
function listaDefeitos(aux_defeito_reclamado,opcoes,valor) {

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
			catch(exc) {
				alert("Esse browser não tem recursos para uso do Ajax"); 
				ajax = null;
			}
		}
	}

	if(ajax) {
		defeito_reclamado = document.getElementById(aux_defeito_reclamado);
		defeito_reclamado.options.length = 1;
		idOpcao  = document.getElementById(opcoes);

		ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
			if(ajax.readyState == 4 ) {
				if(ajax.responseXML) { 
					montaCombo(ajax.responseXML,defeito_reclamado,idOpcao);//após ser processado-chama fun
				} else {
					idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
				}
			}
		}
		var params = "produto_referencia="+valor;
		ajax.send(null);
	}
}

function montaCombo(obj,defeito_reclamado,idOpcao){

	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			 var item = dataArray[i];
			//contéudo dos campos no arquivo XML
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";
			//cria um novo option dinamicamente  
			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
			novo.value = codigo;		//atribui um valor
			novo.text  = nome;//atribui um texto
			defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}


function listaSolucao(aux_solucao_os,opcoes,defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
//verifica se o browser tem suporte a ajax


	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
			catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
	}
	if(ajax) {


		solucao_os = document.getElementById(aux_solucao_os);
		solucao_os.options.length = 1;
		idOpcao  = document.getElementById(opcoes);


	//	 ajax.open("POST", "ajax_produto.php", true);
		ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	
		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {
				idOpcao.innerHTML = "Carregando...!";
			}//enquanto estiver processando...emite a msg
			if(ajax.readyState == 4 ) {
				if(ajax.responseXML) {
					montaComboSolucao(ajax.responseXML,solucao_os,idOpcao);//após ser processado-chama fun
				} else {
					idOpcao.innerHTML = "Selecione o defeito constatado";//caso não seja um arquivo XML emite a mensagem abaixo
				}
			}
		}
		//passa o código do produto escolhido
		var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
		ajax.send(null);
	}
}

function montaComboSolucao(obj,solucao_os,idOpcao){
	var dataArray = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];
			var codigo = item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome   = item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "";
			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes");
			novo.value = codigo;
			novo.text  = nome;
			solucao_os.options.add(novo);
		}
	} else { 
		idOpcao.innerHTML = "Nenhuma solução encontrada";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

function listaConstatado(aux_defeito_constatado,opcoes,linha,familia, defeito_reclamado) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { 
		try {
			ajax = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(ex) {
			try {
				ajax = new XMLHttpRequest();
			}
			catch(exc) {
				alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
			}
		}
	}

	if(ajax) {
		defeito_constatado = document.getElementById(aux_defeito_constatado);
		defeito_constatado.options.length = 1;
		idOpcao  = document.getElementById(opcoes);
		ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {
				idOpcao.innerHTML = "Carregando...!";
			}
			if(ajax.readyState == 4 ) {
				if(ajax.responseXML) {
					montaComboConstatado(ajax.responseXML,defeito_constatado,idOpcao);
				}else {
					idOpcao.innerHTML = "Selecione o defeito reclamado";
				}
			}
		}
		ajax.send(null);
	}
}

function montaComboConstatado(obj,defeito_constatado,idOpcao){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes2");//atribui um ID a esse elemento
			novo.value = codigo;		//atribui um valor
			novo.text  = nome;//atribui um texto
			defeito_constatado.options.add(novo);//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
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

function gravar_aprovar(os,linha,extrato,btn,acao1,acao2) {

	var botao = document.getElementById(btn);
	var os    = document.getElementById(linha);
	var a1    = document.getElementById(acao1);
	var a2    = document.getElementById(acao2);
//	ref = trim(ref);
	var acao='aprovar';

	url = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&os="+escape(os)+"&linha="+escape(linha)+"&extrato="+escape(extrato);
/*	for( var i = 0 ; i < formulatio.length; i++ ){
		if ( formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden'){
			url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			
		}
	}
*/

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					alert(response[1]);
					a1.disabled='true';
					a2.disabled='true';
 					l.style.background = '#D7FFE1';
					botao.value='Aprovada';
					botao.disabled='true';
				}
				if (response[0]=="0"){
					// posto ja cadastrado
					alert(response[1]);
					/*formulatio.btn_acao.value='GRAVAR';*/
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
}
</script>
<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>
<script language="JavaScript">

$(function()
{
	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");
});
</script>

<? include "javascript_pesquisas.php" ?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>


<style>
.Label {
	color: #000000;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	}
}

</style>


<? include "javascript_pesquisas.php" ?>


<script language="javascript" src="js/cal2.js"></script>

<script language="javascript" src="js/cal_conf2.js"></script>

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<? $data_inicial              = trim($_POST["data_inicial_01"]);
   $data_final                = trim($_POST["data_final_01"]);
   $optfamilia                = trim($_POST["optfamilia"]);
   $codigo_posto              = trim($_POST["codigo_posto"]);
   $optdefeito_constatado     = trim($_POST["optdefeito_constatado"]);
   
?>
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg_erro) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>
			
	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="4" class="menu_top" background='imagens_admin/azul.gif' align='center'><b>Pesquisa</b></TD>
</TR>

<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center></center></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>

<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><center>Data Inicial</center></TD>
	<TD class="table_line"><center>Data Final</center></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px; "><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" id='data_inicial'></TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" id='data_final'></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD colspan='4' class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center></center></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<? # HD 32246 - Francisco Ambrozio (12/8/08)
	   # Acrescentado o opção de filtrar por família. ?>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan="2"><center>Família</center></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR bgcolor= "#D9E2EF">
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<? echo "<td class ='lable' colspan='2'>";
		$sqlopt = "SELECT familia, descricao
			FROM    tbl_familia
			WHERE   tbl_familia.fabrica = $login_fabrica
			ORDER BY tbl_familia.descricao;";
		$resopt = pg_exec ($con,$sqlopt);

		if (pg_numrows($resopt) > 0) {
			echo "<select class='text' style='width: 185px;' name='optfamilia'>\n";
			echo "<option value=''> </option>\n";

			for ($x = 0 ; $x < pg_numrows($resopt) ; $x++){
				$auxoptfamilia   = trim(pg_result($resopt,$x,familia));
				$auxoptdescricao = trim(pg_result($resopt,$x,descricao));
				
				echo "<option value='$auxoptfamilia'";
				if ($optfamilia == $auxoptfamilia) {
					echo " SELECTED ";
				}
				echo ">$auxoptdescricao</option>\n";
			}
			echo "</select>\n";
		}
		echo "</td>";
		?>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>

<?if ($login_fabrica==15){ // HD 100393?>
	<TR>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line" colspan='2'><center></center></TD>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</TR>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line">Posto</TD>
		<TD class="table_line">Nome do Posto</TD>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line" style="width: 185px" nowrap>
			<input type="text" name="codigo_posto" id="codigo_posto" size="8"  value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td class="table_line" style="width: 185px" nowrap>
			<input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
	<TR>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line" colspan="2">Defeito Constatado</TD>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</TR>
	<TR>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
		<TD class="table_line" colspan="2">
		<? 
			$sqlopt = "SELECT defeito_constatado,
							  descricao
						FROM tbl_defeito_constatado
						WHERE fabrica = $login_fabrica
						ORDER by descricao;";
			$resopt = pg_exec ($con,$sqlopt);

			if (pg_numrows($resopt) > 0) {
				echo "<select class='text' style='width: 185px;' name='optdefeito_constatado'>\n";
				echo "<option value=''> </option>\n";

				for ($x = 0 ; $x < pg_numrows($resopt) ; $x++){
					$aux_codigo_defeito_constatado = trim(pg_result($resopt,$x,defeito_constatado));
					$aux_descricao = trim(pg_result($resopt,$x,descricao));
					
					echo "<option value='$aux_codigo_defeito_constatado'";
					if ($optdefeito_constatado == $aux_descricao) {
						echo " SELECTED ";
					}
					echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n";
			}
			echo "</td>";
			?>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
	</TR>
<?}?>
<TR>
	<TD colspan='4' class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<input type='hidden' name='btn_finalizar' value='0'>
	<TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
</TR>
</TABLE>

</FORM>

<? 


//--==== Defeito Reclamado ===============================================================================
$btn_acao = $_POST['btn_finalizar'];
if($btn_acao == 1){
	$erro = '';

	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if(strlen($erro) == 0){

		if (strlen($optfamilia) > 0) {
			$sqlcondition = "AND tbl_produto.familia = $optfamilia";
		}

		if (strlen($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and codigo_posto = '$codigo_posto'";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0) {
				$posto = pg_result($res,0,posto);
				$sql_posto = "AND tbl_os.posto = $posto";
			}
		}
		if (strlen($optdefeito_constatado) > 0) {
			$sql_defeito_constatado = "AND tbl_os.defeito_constatado = $optdefeito_constatado";
		}

		$sql = "SELECT	tbl_os.os                                           ,
				tbl_os.tipo_atendimento                                     ,
				tbl_os.posto                                                ,
				tbl_posto.nome                             AS posto_nome    ,
				tbl_os.sua_os                                               ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
				tbl_os.produto                                              ,
				tbl_produto.referencia                                      ,
				tbl_produto.descricao                                       ,
				tbl_os.serie                                                ,
				tbl_os.fabrica                                              ,
				tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
				tbl_os.admin                                                ,
				tbl_defeito_reclamado.descricao            AS defeito_reclamado,
				tbl_defeito_constatado.descricao           AS defeito_constatado,
				tbl_solucao.descricao                      AS solucao_os
				FROM	tbl_os
				JOIN	tbl_produto                ON tbl_produto.produto       = tbl_os.produto AND tbl_produto.linha = 449
				JOIN	tbl_posto                  ON tbl_posto.posto           = tbl_os.posto
				JOIN	tbl_fabrica                ON tbl_fabrica.fabrica       = tbl_os.fabrica
				JOIN    tbl_solucao                ON tbl_os.solucao_os         = tbl_solucao.solucao
				JOIN	tbl_posto_fabrica          ON tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
											AND tbl_fabrica.fabrica       = $login_fabrica
				LEFT JOIN   tbl_defeito_reclamado  USING(defeito_reclamado)
				LEFT JOIN   tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
				LEFT JOIN   tbl_servico_realizado  ON tbl_os.solucao_os         = tbl_servico_realizado.servico_realizado
				WHERE   tbl_os.fabrica      = $login_fabrica
				AND     tbl_os.excluida IS FALSE
				AND     tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				$sqlcondition
				$sql_posto
				$sql_defeito_constatado";
		
		$res = pg_exec($con,$sql);
		$total = pg_numrows($res);

		if($total>0){
			echo "<form name='frm_os' method='post' action='$PHP_SELF'>";

			echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center'  border='0'>";
			echo "<tr>";
			echo "<td>OS</td>";
			echo "<td>Produto</td>";
			echo "<td>Defeito Reclamado</td>";
			echo "<td>Defeito Constatado</td>";
			echo "<td>Solução</td>";
			echo "</tr>";
			echo "<INPUT TYPE='hidden' name='qtde_item' value='$total'>";

			for($i = 0 ; $i < $total;$i++){

				$os                  = pg_result ($res,$i,os);
				$tipo_atendimento    = pg_result ($res,$i,tipo_atendimento);
				$posto               = pg_result ($res,$i,posto);
				$posto_nome          = pg_result ($res,$i,posto_nome);
				$sua_os              = pg_result ($res,$i,sua_os);
				$data_abertura       = pg_result ($res,$i,data_abertura);
				$produto_referencia  = pg_result ($res,$i,referencia);
				$produto_descricao   = pg_result ($res,$i,descricao);
				$produto_serie       = pg_result ($res,$i,serie);

				$posto_codigo        = pg_result ($res,$i,posto_codigo);
				$defeito_reclamado   = pg_result ($res,$i,defeito_reclamado);
				$defeito_constatado  = pg_result ($res,$i,defeito_constatado);
				$solucao_os          = pg_result ($res,$i,solucao_os);

						if ($cor == '#F7F5F0') $cor = "#D5DDF0";
						else                    $cor = "#F7F5F0";

				$sql = "SELECT tbl_os.produto,tbl_linha.linha,tbl_familia.familia 
					FROM tbl_os 
					JOIN tbl_produto USING(produto) 
					JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
					JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
					WHERE tbl_os.os = $os
					AND   tbl_os.fabrica = $login_fabrica";
				$res2 = pg_exec($con,$sql) ;

				$produto = pg_result($res2,0,produto);
				$familia = pg_result($res2,0,familia);
				$linha   = pg_result($res2,0,linha);

				echo "<input type='hidden' name='os_$i' value='$os'>";
				echo "<input type='hidden' name='produto_referencia_$i' id='produto_referencia_$i' value='$produto_referencia'>";
				echo "<input type='hidden' name='linha_$i'   id='linha_$i'   value='$linha'>";
				echo "<input type='hidden' name='familia_$i' id='familia_$i' value='$familia'>";

				echo "<tr bgcolor='$cor'>";
				echo "<td class='Label' nowrap><INPUT TYPE='checkbox' name='chk_$i' value='ok'> <a href='os_press?os=$os' target='blank_'>$sua_os</a></td>";
				echo "<td class='Label' align='left'>$produto_referencia - $produto_descricao</td>";

				echo "<td class='Label' align='left'>Atual: <b>$defeito_reclamado</b><br>";
				echo "Novo: <select name='defeito_reclamado_$i' id='defeito_reclamado_$i' class='frm' style='width:220px;' onfocus=\"javascript:listaDefeitos('defeito_reclamado_$i','opcoes_$i',document.frm_os.produto_referencia_$i.value);\" >";
				echo "<option id='opcoes_$i' value=''></option>";
				echo "</select>";
				echo "</td>";

				echo "<td class='Label' align='left'>Atual: <b>$defeito_constatado</b><br>";
				echo "Novo: <select name='defeito_constatado_$i' id='defeito_constatado_$i'  class='frm' style='width: 220px;' onfocus=\"javascript:listaConstatado('defeito_constatado_$i','opcoes2_$i',document.frm_os.linha_$i.value, document.frm_os.familia_$i.value,document.frm_os.defeito_reclamado_$i.value);\" >";
				echo "<option id='opcoes2_$i' value=''></option>";
				echo "</select>";
				echo "</td>";

				echo "<td class='Label' align='left'>Atual: <b>$solucao_os</b><br>";
				echo "Novo: <select name='solucao_os_$i' id='solucao_os_$i' class='frm'  style='width:200px;' onfocus=\"listaSolucao('solucao_os_$i','opcoes3_$i',document.frm_os.defeito_constatado_$i.value, document.frm_os.linha_$i.value, document.frm_os.defeito_reclamado_$i.value,  document.frm_os.familia_$i.value);\" >";
				echo "<option id='opcoes3_$i' value=''></option>";
				echo "</select>";
				echo "</td>";

				echo "</tr>";
			}
			echo "<tr><td colspan='5'><INPUT TYPE='submit' value='Gravar' name='gravar'></td></tr>";
		}
		echo "</table>";
	}else{
		echo "$erro";
	}
}

?>
	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
</table>
</form>
<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>

<p>
<p>
</table></table>
<? include "rodape.php";?>
