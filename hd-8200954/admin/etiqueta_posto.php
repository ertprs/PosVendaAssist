<?php
/************************************************************************
*	HD 64220   - Relatótio de Etiquetas									*
*	Francicsco - Gera um CSV com as etiquetas conforme postos digitados *
*   09/03/2009 - Manolo-Sem lupa, pesquisa via AJAX por código de posto *
*   10/03/2009 - Inicia versão 2, para gerar um HTML com as etiquetas   *
*                prontas para imprimir                                  *
************************************************************************/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

function anti_injection($string){
$string = str_replace("'",  "", $string);
$string = str_replace("*",  "", $string);
$string = str_replace('"',  "", $string);
$string = str_replace("\\", "", $string);
$string = trim($string);
$string = strip_tags($string);
$string = addslashes($string);
return $string;
}

// Resposta à consulta AJAX
if (trim($_GET['ajax'])=="sim") {
	// Limpa o código do posto
		$codigo_posto = anti_injection(trim (strtoupper(trim($_GET['posto']))));
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace (",","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("<","",$codigo_posto);
		$codigo_posto = str_replace (">","",$codigo_posto);
	if ($codigo_posto=="") {
	echo "";
	exit;
	}
	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                       ,
                    trim(tbl_posto.nome)                        AS nome  ,
                    trim(tbl_posto_fabrica.contato_endereco)    AS rua   ,
                    trim(tbl_posto_fabrica.contato_numero)      AS numero,
                    trim(tbl_posto_fabrica.contato_complemento) AS compl ,
                    trim(tbl_posto_fabrica.contato_bairro)      AS bairro,
                    trim(tbl_posto_fabrica.contato_cidade) || ' - '||
                         tbl_posto_fabrica.contato_estado       AS cidade,
                    to_char(tbl_posto_fabrica.contato_cep::numeric,'99999-999') AS cep
                FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE    tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%'
                    AND  tbl_posto_fabrica.fabrica = $login_fabrica
                ORDER BY tbl_posto.nome";
	$res = pg_query($con,$sql);
	
//	Para evitar o cache da página e devolver dados anteriores
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sun, 01 Mar 2009 05:00:00 GMT");

	if (@pg_numrows ($res) > 1) {
		echo "O código $codigo_posto devolveu mais de um resultado";
		exit;
	}
	if (@pg_numrows ($res) == 1) {
		echo implode("|",pg_fetch_array($res,0, PGSQL_NUM));
		exit;
	}
	echo "Posto não Encontrado";
	exit;
}   // Fim da resposta AJAX

$title       = "GERAÇÃO DE ETIQUETAS";
$cabecalho   = "GERAÇÃO DE ETIQUETAS";
$layout_menu = "gerencia";

include 'cabecalho.php';

$btn_acao = $_POST["btn_acao"];
?>

<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<!-- Inicializando o AJAX -->
<script type="text/javascript">
function SetAjax() {
var xmlhttp;
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}
	return xmlhttp;
}

function AtualizaLinha(ajax,i) {
//	alert ("Status: "+ajax.readyState);
	document.getElementsByName('posto_nome_'+i)[0].value="Espere...";
	if (ajax.readyState==4)  // Primero conferir se chegou a resposta...
	{
		var campos = Array(7);
// Declara os objetos
		var resposta = ajax.responseText;
		var posto	= document.getElementsByName('posto_codigo_'+i)[0];
		var nome	= document.getElementsByName('posto_nome_'	+i)[0];
		var endereco= document.getElementsByName('endereco_'	+i)[0];
		var bairro	= document.getElementsByName('bairro_'		+i)[0];
		var cidade	= document.getElementsByName('cidade_uf_'	+i)[0];
		var cep		= document.getElementsByName('cep_'			+i)[0];

		if (resposta == "" || resposta.indexOf("|") == -1)
		{   // Se não tem resposta, sai
			alert(resposta);
			nome.value="";
			posto.value="";
            posto.focus();
			return;
		}
//  Copia a resposta para um array
		campos   = resposta.split("|",8);
		var endc = campos[2];
        if (campos[3] != "") {
            endc = endc + ", " +campos[3];
        }
		if (campos[4] != "") {
            endc = endc + " – " +campos[4];
        }

//  Copia os elementos do array para os campos
		   posto.value = campos[0];
			nome.value = campos[1];
		endereco.value = endc;
		  bairro.value = campos[5];
		  cidade.value = campos[6];
			 cep.value = campos[7];
	}
}

function dados_posto(posto,linha) {
	if (posto.length < 1) {
		alert("Não digitou posto!");
		document.getElementsByName('posto_codigo_'+linha)[0].focus();
		return;
	}

var ajax = new SetAjax(); // Cria um novo objeto HTTPRequest
	if (ajax == null) {
		alert ("Seu navegador não aceita AJAX!");
		document.getElementsByName('posto_codigo_'+linha)[0].focus();
		return;
	}

	var url ="<?=$PHP_SELF?>";
	url  = url + '?ajax=sim&posto=' + posto;
	ajax.open("GET",url,false);
	ajax.send(null);
	ajax.onreadystatechange=AtualizaLinha(ajax,linha);
}
</script>

<script type="text/javascript">
function qtdeLinhas(campo){
	
	var infopags= document.getElementById('pags');
    var pags    = Array();
        pags[6] = "1/2 página";
        pags[14]= "1 página";
        pags[20]= "1 1/2 páginas";
        pags[28]= "2 páginas";		
    infopags.innerHTML = "até "+pags[campo.value];

	var linha   = 0;
	
	if (campo.value > 0){
		
		$(".tabela_item tr").each( function (){
			linha = parseInt( $(this).attr("rel") );
			if (linha  +1 > campo.value) {
				$(this).css('display','none');
			}else{
				$(this).css('display','');
			}
		});
	}
}
/*  12-03-2009  O Antônio pediu para tirar a opção de gerar o CSV  */
</script>

<style type="text/css">
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
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
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
</style>
<div class='texto_avulso' style='width:700px;'>
   Por favor, confira os dados, principalmente o endereço e complemento, antes de gerar o arquivo ou imprimir.      
</div>
<br />
<form name='frm_posto' method='post' style='font-size: 9pt'
	action="./etiqueta_posto_imprimir.php" target='_blank'>
<?php
$qtde_item          = 28;
$qtde_item_visiveis =  6;
$qtde_linhas        =  0;
?>
	<div name='download' id='download' style='display:none;' align='center'>
		<a href='xls/$arquivo_nome' style='color: #C00;font-size: 14px' target='_blank'>
			Fazer download do arquivo
		</a>
	</div>
	<br>
	<table align='center' width='700' class='formulario' border='0'>
	<tr class='titulo_tabela'><td colspan='2' >Parâmetros de Pesquisa</td></tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td align='right' width='50%'>
				Número de Etiquetas em <SPAN id='pags'>&nbsp;at&eacute; 1/2 p&aacute;gina</SPAN>
			</td>
			<td align='left' >
				<select onChange='qtdeLinhas(this)' class='frm'>
					<option value= '6'> 6 Etiquetas</option>
					<option value='14'>14 Etiquetas</option>
					<option value='20'>20 Etiquetas</option>
					<option value='28'>28 Etiquetas</option>
				</select> 
			</td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
	</table>
	<br />
	<table class='tabela_item tabela' cellspacing='1' align='center'>
	
	<THEAD>
	<tr class='titulo_tabela'>
		<td colspan='6' style='font-size:14px;'>
			Inserção de Dados
		</td>
	</tr>
	<tr class='titulo_coluna' >
		<td>Código Posto</td>
		<td>Nome Posto</td>
		<td>Endereço</td>
		<td>Bairro</td>
		<td>Cidade - UF</td>
		<td>CEP</td>
	</tr>
	</THEAD>
	<TBODY>
<?
for ($i=0; $i<$qtde_item; $i++) {
	$posto_codigo = "";
	$posto_nome   = "";
	$endereco     = "";
	$bairro       = "";
	$cidade_uf    = "";
	$cep          = "";
	$ocultar_linha= "";
	if ($i+1 > $qtde_item_visiveis and $i+1 > $qtde_linhas){
		$ocultar_linha = " style='display:none' ";
	}
	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	?>
		<tr <?=$ocultar_linha?> rel='<?=$i?>' bgcolor="<? echo $cor; ?>">
		<td align='center'>
			<input class='frm' type='text' name='posto_codigo_<?=$i?>'
					size='15' value='<?=$posto_codigo?>' onChange='dados_posto(this.value,<?=$i?>);'>
		</td>
		<td align='center'>
			<input class='frm' type='text' name='posto_nome_<?=$i?>'
					size='25' value='<?=$posto_nome?>'>
		</td>
		<td align='center'>
			<input class='frm' type='text' name='endereco_<?=$i?>'
					size='25' value='<?=$endereco?>'></td>
		<td align='center'>
			<input class='frm' type='text' name='bairro_<?=$i?>'
					size='20' value='<?=$bairro?>'></td>
		<td align='center'>
			<input class='frm' type='text' name='cidade_uf_<?=$i?>'
					size='20' value='<?=$cidade_uf?>'></td>
		<td align='center'>
			<input class='frm' type='text' name='cep_<?=$i?>' size='15' value='<?=$cep?>'>
		</td>
		</tr>
	<?}?>
	</TBODY>
	
	</table>
	<br>
	<br>
	<center>
		<input type='submit' name='imprimir' class='frm' value='Imprimir'
		      title='Prévisualizar dados para impressão' style='cursor: pointer'>
<!--		<input type='button' name='btn_acao' class='frm' value='Gerar Etiquetas'
		    onClick='act_form("csv");' style='cursor: pointer'>
-->		<input type='reset'  name='btn_limp' class='frm' value='Limpar'
			onClick='document.frm_posto.posto_codigo_0.focus();'
		      style='cursor: pointer'>
	</center>
</form>

<?php
/*	12-03-2009	O Antônio pediu para tirar a opção de gerar o CSV   */
include "rodape.php";
?>
