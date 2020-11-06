<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include "autentica_admin.php";
include 'funcoes.php';


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$layout_menu = "Gerencia";
$title = "RELATÓRIO DE OS's REINCIDENTES";

include "cabecalho.php";

?>
<link type="text/css" rel="stylesheet" href="css/'.css">
<style type="text/css" media="screen">

.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
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

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}


#tooltip{
                
	background: #FF9999;
	border:2px solid #000;
	display:none;
	padding: 2px 4px;
	color: #003399;
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




<script language="JavaScript" type="text/javascript">
	window.onload = function(){

		tooltip.init();

	}
</script>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}


</script>


<script language="JavaScript">
var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}


</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});
</script>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<style type="text/css" media="all">
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
    padding: 3px;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna th{
    text-align: center;
    padding: 2px 1px;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
    width: 700px;
    margin: 0 auto;
    padding: 3px;
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
    margin: 0 auto;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 10px;
	border-collapse: collapse;
	border:1px solid #596d9b;
    text-align: left;
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

.informacao{
	font: 14px Arial; color:rgb(89, 109, 155);
	background-color: #C7FBB5;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{padding:0 0 0 80px; }
</style>
<script language="JavaScript">
$().ready(function() {
    Shadowbox.init();

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});

function pesquisaPosto(campo,tipo){
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player:	    "iframe",
            title:		"Pesquisa Posto",
            width:	    800,
            height:	    500
        });
    }else
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}
    
function pesquisaProduto(campo,tipo){

    if (jQuery.trim(campo.value).length > 2){
        Shadowbox.open({
            content:	"produto_pesquisa_2_nv.php?"+tipo+"="+campo.value,
            player:	    "iframe",
            title:		"Pesquisa Produto",
            width:	    800,
            height:	    500
        });
    }else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
        campo.focus();
    }
}


function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto, cep, endereco, numero, bairro){
    gravaDados('posto_codigo',codigo_posto);
    gravaDados('posto_nome',nome);
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){
    gravaDados('produto_referencia',referencia);
    gravaDados('produto_descricao',descricao);
}

function gravaDados(name, valor){
    try{
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}


function abreInteracao(linha,os,tipo) {

	var div = document.getElementById('interacao_'+linha);
	var os = os;
	var tipo = tipo;

	//alert('ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo);
	
	requisicaoHTTP('GET','ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo, true , 'div_detalhe_carrega2');
	
}


function div_detalhe_carrega2 (campos) {
	campos_array = campos.split("|");
	resposta = campos_array [0];
	linha = campos_array [1];
	var div = document.getElementById('interacao_'+linha);
	div.innerHTML = resposta;
	var comentario = document.getElementById('comentario_'+linha);
	comentario.focus();
}

function gravarInteracao(linha,os,tipo) {
	
var linha = linha;
var os = os;
var tipo = tipo;
var comentario = document.getElementById('comentario_'+linha).value;
//alert('ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo);

requisicaoHTTP('GET','ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo, true , 'div_detalhe_carrega');

}

function div_detalhe_carrega (campos) {
	campos_array = campos.split("|");
	resposta = campos_array [1];
	linha = campos_array [2];
	os = campos_array [3];

	if (resposta == 'ok') {
		document.getElementById('interacao_' + linha).innerHTML = "Gravado Com sucesso!!!";
		document.getElementById('btn_interacao_' + linha).innerHTML = "<font color='red'><a href='#' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'><img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'></a></font>";
//		var linha = new Number(linha+1);
		var table = document.getElementById('linha_'+linha);
//		alert(document.getElementById('linha_'+linha).innerHTML);
		table.style.background = "#FFCC00";
	
	}
}

	function recusaFabricante(){
		var motivo =prompt("Qual o Motivo da Recusa da(s) OS(s)  ?",'',"Motivo da Recusa");
		if (motivo !=null && $.trim(motivo) !="" && motivo.length > 0 ){
				document.getElementById('motivo_recusa').value = motivo;
				document.frm_pesquisa2.submit();
		}else{
			alert('Digite um motivo por favor!','Erro');
		}
	}


</script>


<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial           = trim($_POST['data_inicial']);
	$data_final             = trim($_POST['data_final']);
	$aprova                 = trim($_POST['aprova']);
	$os                     = trim($_POST['os']);
	$status_os              = trim($_POST['status_os']);
    $produto_referencia     = trim($_POST['produto_referencia']);
    $estado                 = trim($_POST['estado']);
    $posto_codigo           = trim($_POST["posto_codigo"]);

	if (strlen($os)>0){
		$Xos = " AND os = ".intval($os);
	}

	if(strlen($aprova) == 0){
		$aprova = "aprovacao";
		switch($login_fabrica) {
			case ($login_fabrica ==14 or $login_fabrica ==11):
			$aprovacao = "67, 68,70";
			break;
			case 52:
			$aprovacao = "67,134";
			break;
			case 24: $aprovacao = "67, 68,70";
			break;

		}
		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE ";
	}elseif($aprova=="aprovacao"){
		switch($login_fabrica) {
			case ($login_fabrica ==14 or $login_fabrica ==11):
			$aprovacao = "67, 68,70";
			break;
			case 52:
			$aprovacao = "67,134";
			break;
			case 24: $aprovacao = "67, 68,70";
			break;
		}
		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "19";
		$sql_add2 = " AND tbl_os_status.extrato IS NULL  
		AND tbl_os.excluida IS NOT TRUE";
	}elseif($aprova=="reprovadas"){
		
		$aprovacao = ($login_fabrica ==11 or $login_fabrica == 24) ? "13,15	" : "131";

		$sql_add2 = " AND tbl_os_status.extrato IS NULL ";
	}
	
	if (strlen($status_os)>0) {
		$sql_tipo = $status_os;
	}else{
		switch($login_fabrica) {
			case ($login_fabrica ==14 or $login_fabrica ==11):
			    $sql_tipo = "67, 68,70,131,19,13";
			break;
			case 52:
			    $sql_tipo = "67, 134,135,13,19,131";
			break;
			case 24:
		    	$sql_tipo = "67, 68,70,131,19,13";
			case 106:
		    	$sql_tipo = "67, 68,70,131";
			break;
			default: 
			$sql_tipo = "67,68,70";
			break;
		}

	}

    if(strlen(strlen($data_inicial)) > 0 AND strlen($data_final) > 0){
        
        if(strlen($msg_erro)==0){
            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi)) 
                $msg_erro = "Data Inválida";
        }
        
        if(strlen($msg_erro)==0){
            list($df, $mf, $yf) = explode("/", $data_final);
            if(!checkdate($mf,$df,$yf)) 
                $msg_erro = "Data Inválida";
        }

        if(strlen($msg_erro)==0){
            $xdata_inicial = "$yi-$mi-$di 00:00:00";
            $xdata_final = "$yf-$mf-$df 23:59:59";

            if($xdata_final < $xdata_inicial){
                $inverte_data = $xdata_inicial;
                $xdata_inicial = $xdata_final;
                $xdata_final = $inverte_data;

                $inverte_data = $data_inicial;
                $data_inicial = $data_final;
                $data_final = $inverte_data;

            }
        }

    }else{
        if(strlen($posto_codigo) == 0 AND strlen($os) == 0)
            $msg_erro = "Data Inválida";
    }

	if(strlen($posto_codigo)>0){
		$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$posto_codigo' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

        if(pg_num_rows($res) > 0){
            $posto = pg_result($res,0,0);

            $sql_add .= " AND tbl_os.posto = '$posto' ";
        }else{
            $msg_erro = "Posto inválido.";
        }
	}

    if(!empty($produto_referencia)){
		$sql = "SELECT 
                    produto 
                FROM tbl_produto 
                    JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha 
                WHERE tbl_linha.ativo IS TRUE 
                    AND tbl_produto.ativo IS TRUE 
                    AND tbl_produto.referencia = '$produto_referencia' 
                    AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

        if(pg_num_rows($res) > 0){
            $produto = pg_result($res,0,0);

            $sql_add .= " AND tbl_os.produto = $produto ";
        }else{
            $msg_erro = "Produto inválido.";
        }
    }

    if(!empty($estado)){
        $sql_add .= " AND tbl_posto.estado = '$estado' ";
    }

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql_data2 .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";

	}

}

if(strlen($msg_erro) > 0){
	echo "<p class='msg_erro'>$msg_erro</p>";
}



?>
<div id="page-container">
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">


<table width="700" border="0" cellspacing='3' cellpadding='2' class='formulario'>
    <caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
    <tr>
        <td width='100px'>&nbsp;</td>
        <td width='250px'>&nbsp;</td>
        <td width='250px'>&nbsp;</td>
        <td width='100px'>&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            Número da OS<br>
            <input type="text" name="os" id="os" size="15" maxlength="20" value="<? echo $os ?>" class="frm"></TD>
        <td>
            Estado<br>
            <select name="estado" class="frm" id="estado">
                <option value='' selected="selected"></option>
                <?php
                    $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas","AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal","ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais","MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba", "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro", "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima","RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe", "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
                    foreach ($array_estado as $k => $v) {
                         echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
                    }?>
            </select>
        </td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            Data Inicial<br>
            <input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
        </td>
        <td>
            Data Final<br>
            <input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
        </td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            Código do Posto<br>
            <input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaPosto(document.frm_pesquisa.posto_codigo, 'codigo')" />
        </td>
        <td width='320px'>
            Nome do Posto<br>
            <input type="text" name="posto_nome" id="posto_nome" size="30"  value="<? echo $posto_nome ?>" class="frm" />
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar" onclick="pesquisaPosto(document.frm_pesquisa.posto_nome, 'nome')" />
        </td>
         <td>&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            Produto Referência<br>
            <input type="text" name="produto_referencia" id="produto_referencia" size="15"  value="<? echo $produto_referencia ?>" class="frm" />
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.frm_pesquisa.produto_referencia, 'referencia')" />
        </td>
        <td>
            Produto Descrição<br>
            <input type="text" name="produto_descricao" id="produto_descricao" size="30"  value="<? echo $produto_descricao ?>" class="frm" />
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.frm_pesquisa.produto_descricao, 'descricao')" />
        </td>
         <td>&nbsp;</td>
    </tr>
<!--
    <tr>
        <td>&nbsp;</td>
        <td>Status<br>
            <select class='frm' name='status_os'>
                <option></option>
                <?php 
                    $sql = "select * from tbl_status_os where status_os in(67, 68,70,131)";
                    $res = pg_exec($con,$sql);

                    for ($i=0;$i<pg_numrows($res);$i++) {
                        $status_os_x = pg_result($res,$i,status_os);
                        $descricao = pg_result($res,$i,descricao);?>
                        <option value="<? echo $status_os_x;?>" <? if ($status_os == $status_os_x) echo "SELECTED";?>><?php echo $descricao;?></option>
                <?php }  ?>
            </select>
        </td>
        <td colspan='2'>&nbsp;</td>
    </tr>
-->
    <tr>
        <td colspan="4" style='text-align: center;' >
            <br>
            <input type='hidden' name='btn_acao' value=''>
            <input type='button' value=' Pesquisar ' onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar' />
        </td>
    </tr>
    <tr>
        <td colspan='4'>&nbsp;</td>
    </tr>
</table>
</form>


<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {
    $sql =  "
            SELECT interv.os
            INTO TEMP tmp_interv_$login_admin
            FROM (
                SELECT	ultima.os,
                        (	
                            SELECT status_os 
                            FROM tbl_os_status
                            JOIN tbl_os USING(os)
                            WHERE status_os IN ($sql_tipo) 
                            AND tbl_os_status.os = ultima.os 
                            AND tbl_os.fabrica = $login_fabrica
                            AND tbl_os.os_reincidente IS TRUE
                            $sql_add2
                            $sql_add
                            $sql_data
                            $sql_data2
                            ORDER BY os_status DESC 
                            LIMIT 1
                        ) AS ultimo_status
                FROM (
                        SELECT DISTINCT os 
                        FROM tbl_os_status 
                        JOIN tbl_os USING(os)
                        WHERE status_os IN ($sql_tipo) 
                        AND tbl_os.fabrica = $login_fabrica
                        AND tbl_os.os_reincidente IS TRUE
                        $sql_add
                        $sql_data
                        $sql_data2
                ) ultima
            ) interv
            WHERE /*interv.ultimo_status IN ($aprovacao)*/ 1 = 1 
            $Xos;

            CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);


            SELECT	tbl_os.os                                                   ,
                    tbl_os.serie                                                ,
                    tbl_os.sua_os                                               ,
                    tbl_os.consumidor_nome                                      ,
                    tbl_os.revenda_nome                                         ,
                    tbl_os.consumidor_revenda                                   ,
                    tbl_os.consumidor_fone                                      ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                    TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                    tbl_os.nota_fiscal                                          ,
                    TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
                    tbl_os.fabrica                                              ,
                    tbl_os.consumidor_nome                                      ,
                    tbl_posto.nome                     AS posto_nome            ,
                    UPPER(tbl_posto.estado)                     AS posto_estado ,
                    tbl_posto_fabrica.codigo_posto                              ,
                    tbl_posto_fabrica.contato_email       AS posto_email        ,
                    tbl_produto.referencia             AS produto_referencia    ,
                    tbl_produto.descricao              AS produto_descricao     ,
                    tbl_produto.voltagem                                        ,
                    tbl_motivo_reincidencia.descricao  AS motivo_reincidencia_desc ,
                    tbl_os_extra.os_reincidente                                 ,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
                    (SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
                    (SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao,
                    tbl_os.obs_reincidencia                                    ,
                    (SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
                    (SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
                FROM tmp_interv_$login_admin X
                JOIN tbl_os                   ON tbl_os.os                  = X.os
                JOIN tbl_os_extra             ON tbl_os.os                  = tbl_os_extra.os
                JOIN tbl_produto              ON tbl_produto.produto        = tbl_os.produto
                JOIN tbl_posto                ON tbl_os.posto               = tbl_posto.posto
                LEFT JOIN tbl_motivo_reincidencia  ON tbl_os.motivo_reincidencia = tbl_motivo_reincidencia.motivo_reincidencia
                JOIN tbl_posto_fabrica        ON tbl_os.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE tbl_os.fabrica = $login_fabrica
                AND  tbl_os_extra.extrato IS NULL 
                $sql_add
                $sql_data ";
                
    # ---- 2009-07-08 - Tulio - na Intelbras - Ramona pediu para que somente aparecessem para analise OS FECHADAS -------------


        $sql.=" ORDER BY tbl_posto.nome,status_observacao,tbl_os.os";

        if ($login_fabrica == 114) {
        	$sql = "SELECT	tbl_os.os                                                   ,
                    tbl_os.serie                                                ,
                    tbl_os.sua_os                                               ,
                    tbl_os.consumidor_nome                                      ,
                    tbl_os.revenda_nome                                         ,
                    tbl_os.consumidor_revenda                                   ,
                    tbl_os.consumidor_fone                                      ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                    TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                    tbl_os.nota_fiscal                                          ,
                    TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
                    tbl_os.fabrica                                              ,
                    tbl_os.consumidor_nome                                      ,
                    tbl_posto.nome                     AS posto_nome            ,
                    UPPER(tbl_posto.estado)                     AS posto_estado ,
                    tbl_posto_fabrica.codigo_posto                              ,
                    tbl_posto_fabrica.contato_email       AS posto_email        ,
                    tbl_produto.referencia             AS produto_referencia    ,
                    tbl_produto.descricao              AS produto_descricao     ,
                    tbl_produto.voltagem                                        ,
                    tbl_motivo_reincidencia.descricao  AS motivo_reincidencia_desc ,
                    tbl_os_extra.os_reincidente                                 ,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
                    (SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
                    (SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao,
                    tbl_os.obs_reincidencia                                    ,
                    (SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
                    (SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
                FROM tbl_os
                JOIN tbl_os_extra             ON tbl_os.os                  = tbl_os_extra.os
                JOIN tbl_produto              ON tbl_produto.produto        = tbl_os.produto
                JOIN tbl_posto                ON tbl_os.posto               = tbl_posto.posto
                LEFT JOIN tbl_motivo_reincidencia  ON tbl_os.motivo_reincidencia = tbl_motivo_reincidencia.motivo_reincidencia
                JOIN tbl_posto_fabrica        ON tbl_os.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN ($sql_tipo)
                WHERE tbl_os.fabrica = $login_fabrica
                AND  tbl_os_extra.extrato IS NULL 
                $sql_add
                $sql_data
                ORDER BY tbl_posto.nome,status_observacao,tbl_os.os";
        }
   // echo nl2br($sql);
    $res = pg_exec($con,$sql);
    if(pg_numrows($res)>0){

        echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

        echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
        echo "<input type='hidden' name='data_final'     value='$data_final'>";
        echo "<input type='hidden' name='aprova'         value='$aprova'>";
        echo "<input type='hidden' name='posto_codigo'     value='$posto_codigo'>";
        echo "<input type='hidden' name='posto_nome'     value='$posto_nome'>";

        $css_th = "style = 'background-color:#596d9b;	font: bold 11px \"Arial\";	color:#FFFFFF;	text-align:center;'";
        $xls = "<table width='90%' border='0' align='center' cellpadding='0' cellspacing='1' class='tabela'>";
            $xls .= "<tr class='titulo_coluna'>";
                $xls .= "<th $css_th >OS</th>";
                $xls .= "<th $css_th >Série</th>";
                $xls .= "<th $css_th >Data <br>Abertura</th>";
                $xls .= "<th $css_th >Data <br>Fechamento</th>";
                $xls .= "<th $css_th >Posto</th>";
                $xls .= "<th $css_th >UF</th>";
                $xls .= "<th $css_th >Nota Fiscal</th>";
                $xls .= "<th $css_th >Consumidor</th>";
                $xls .= "<th $css_th >Produto</th>";
                $xls .= "<th $css_th >Defeito Constatado</th>";
                $xls .= "<th $css_th >Status</th>";
                $xls .= "<th $css_th >Justificativa</th>";
            $xls .= "</tr>";

        $cores = '';
        $qtde_intervencao = 0;

        for ($x=0; $x<pg_numrows($res);$x++){

            $os						= pg_result($res, $x, os);
            $serie					= pg_result($res, $x, serie);
            $data_abertura			= pg_result($res, $x, data_abertura);
            $data_fechamento		= pg_result($res, $x, data_fechamento);
            $sua_os					= pg_result($res, $x, sua_os);
            $codigo_posto			= pg_result($res, $x, codigo_posto);
            $posto_nome				= pg_result($res, $x, posto_nome);
            $posto_estado			= pg_result($res, $x, posto_estado);
            $posto_email			= pg_result($res, $x, posto_email);
            $nota_fiscal			= pg_result($res, $x, nota_fiscal);
            $data_nf				= pg_result($res, $x, data_nf);
            $consumidor_nome		= pg_result($res, $x, consumidor_nome);
            $consumidor_revenda     = pg_result($res, $x, consumidor_revenda);
            $revenda_nome           = pg_result($res, $x, revenda_nome);
            $consumidor_fone		= pg_result($res, $x, consumidor_fone);
            $produto_referencia		= pg_result($res, $x, produto_referencia);
            $produto_descricao		= pg_result($res, $x, produto_descricao);
            $produto_voltagem		= pg_result($res, $x, voltagem);
            $data_digitacao			= pg_result($res, $x, data_digitacao);
            $data_abertura			= pg_result($res, $x, data_abertura);
            $status_os				= pg_result($res, $x, status_os);
            $status_observacao		= pg_result($res, $x, status_observacao);
            $status_descricao		= pg_result($res, $x, status_descricao);
            $os_reincidente			= pg_result($res, $x, os_reincidente);
            $obs_reincidencia		= pg_result($res, $x, obs_reincidencia);
            $defeito_constatado		= pg_result($res, $x, defeito_constatado);
            $defeito_reclamado		= pg_result($res, $x, defeito_reclamado);
            if ($login_fabrica == 52){
                $motivo_reincidencia_desc = pg_result($res, $x, 'motivo_reincidencia_desc');
            }

            if(strlen($os_reincidente)>0 && $status_os != 67){

                $sql =  "SELECT	tbl_os.os                                                   ,
                                tbl_os.serie                                                ,

                                tbl_os.sua_os                                               ,
                                tbl_os.consumidor_nome                                      ,
                                tbl_os.revenda_nome                                         ,
                                tbl_os.consumidor_revenda                                   ,
                                tbl_os.consumidor_fone                                      ,
                                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
                                TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                                tbl_os.nota_fiscal                                          ,
                                TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
                                tbl_os.fabrica                                              ,
                                tbl_os.consumidor_nome                                      ,
                                tbl_os.obs_reincidencia                                     ,
                                tbl_posto.nome                     AS posto_nome            ,
                                tbl_posto.estado                     AS posto_estado        ,
                                tbl_posto_fabrica.codigo_posto                              ,
                                tbl_posto_fabrica.contato_email       AS posto_email        ,
                                tbl_produto.referencia             AS produto_referencia    ,
                                tbl_produto.descricao              AS produto_descricao     ,
                                tbl_produto.voltagem                                        ,
                                tbl_os_extra.os_reincidente                                 ,
                                (SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
                                (SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
                        FROM tbl_os                   
                        JOIN tbl_os_extra             ON tbl_os.os = tbl_os_extra.os
                        JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
                        JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
                        JOIN tbl_posto_fabrica        ON tbl_os.posto     = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
                        WHERE tbl_os.os = $os_reincidente
                        LIMIT 1";
                $res_reinc = pg_exec($con,$sql);

                $reinc_os					= pg_result($res_reinc, 0, os);
                $reinc_serie				= pg_result($res_reinc, 0, serie);
                $reinc_data_abertura		= pg_result($res_reinc, 0, data_abertura);
                $reinc_data_fechamento		= pg_result($res_reinc, 0, data_fechamento);
                $reinc_sua_os				= pg_result($res_reinc, 0, sua_os);
                $reinc_codigo_posto			= pg_result($res_reinc, 0, codigo_posto);
                $reinc_posto_nome			= pg_result($res_reinc, 0, posto_nome);
                $reinc_posto_estado			= pg_result($res_reinc, 0, posto_estado);
                $reinc_posto_email			= pg_result($res_reinc, 0, posto_email);
                $reinc_nota_fiscal			= pg_result($res_reinc, 0, nota_fiscal);
                $reinc_data_nf				= pg_result($res_reinc, 0, data_nf);
                $reinc_consumidor_nome		= pg_result($res_reinc, 0, consumidor_nome);
                $reinc_revenda_nome		    = pg_result($res_reinc, 0, revenda_nome);
                $reinc_consumidor_revenda   = pg_result($res_reinc, 0, consumidor_revenda);
                $reinc_consumidor_fone		= pg_result($res_reinc, 0, consumidor_fone);
                $reinc_produto_referencia	= pg_result($res_reinc, 0, produto_referencia);
                $reinc_produto_descricao	= pg_result($res_reinc, 0, produto_descricao);
                $reinc_produto_voltagem		= pg_result($res_reinc, 0, voltagem);
                $reinc_data_digitacao		= pg_result($res_reinc, 0, data_digitacao);
                $reinc_data_abertura		= pg_result($res_reinc, 0, data_abertura);
                $reinc_defeito_constatado	= pg_result($res_reinc, 0, defeito_constatado);
                $reinc_defeito_reclamado	= pg_result($res_reinc, 0, defeito_reclamado);
                $reinc_obs_reincidencia	    = pg_result($res_reinc, 0, obs_reincidencia);


            }

            $cores++;
            $cor = ($cores % 2) ? "#F7F5F0" : "#F1F4FA";

            $sqlint = " SELECT 
                            os_interacao,
                            admin 
                        FROM tbl_os_interacao
                        WHERE os = $os
                        ORDER BY 
                            os_interacao DESC 
                        LIMIT 1";

            $resint = pg_exec($con,$sqlint);

            if(pg_num_rows($resint)>0) {
            
                $admin = pg_result($resint,0,admin);

                if (strlen($admin)>0) {
                    $cor = "#FFCC00";
                }
            }

            if(strlen($sua_os)==0)$sua_os=$os;

            $xls .= "<tr' id='linha_$x'>";
                $xls .= "<td style='background: $cor'><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a> </td>";
                $xls .= "<td style='background: $cor'>$serie</td>";
                $xls .= "<td style='background: $cor'>$data_abertura</td>";
                $xls .= "<td style='background: $cor'>$data_fechamento </td>";
                $xls .= "<td style='background: $cor' title='".$codigo_posto." - ".$posto_nome."'>"; if(strlen($posto_nome) > 20) $xls .=  substr($posto_nome,0,20)."..."; else $xls .=  $posto_nome; $xls .= "</td>";
                $xls .= "<td style='background: $cor'>$posto_estado</td>";
                $xls .= "<td style='background: $cor'>$nota_fiscal</td>";
                if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;
                if ($consumidor_revenda=='R'){
                    if (strlen($revenda_nome) == 0){
                        $revenda_nome = $consumidor_nome;
                    }
                }
                $xls .= "<td style='background: $cor'>$consumidor_nome</td>";
                $xls .= "<td style='background: $cor' title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>";
                $xls .= "<td style='background: $cor'>$defeito_constatado</td>";
                $xls .= "<td style='background: $cor' title='Observação: ".$status_observacao."' rowspan>$status_descricao</td>";
                $xls .= "<td title='$sua_os - Motivo: ".$obs_reincidencia."' nowrap>&nbsp;".substr($obs_reincidencia,0,50). "</td>";
            $xls .= "</tr>";

           // $xls .= "<tr>";
           //     $xls .= "<td colspan='12'  bgcolor='#C0E2D9'><div id='interacao_".$x."'></div></td>";
           // $xls .= "</tr>";


            /* ---------------- OS REINCIDENTE -------------------*/
           if(strlen($os_reincidente)>0 && $status_os != 67){
	            $xls .= "<tr>";
	                $xls .= "<td  bgcolor='$cor' style='text-align: center'>Reinc.<br>$reinc_sua_os</a></td>";
	                $xls .= "<td  bgcolor='$cor'>$reinc_serie</td>";
	                $xls .= "<td  bgcolor='$cor'>$reinc_data_abertura</td>";
	                $xls .= "<td bgcolor='$cor'>$reinc_data_fechamento </td>";
	                $xls .= "<td bgcolor='$cor'>"; if(strlen($reinc_posto_nome) > 20) $xls .= substr($reinc_posto_nome,0,20)."..."; else $xls .= $reinc_posto_nome; $xls .= "</td>";
	                $xls .= "<td bgcolor='$cor'>$reinc_posto_estado</td>";
	                $xls .= "<td bgcolor='$cor'>$reinc_nota_fiscal</td>";
	                //HD 119665
	                if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;
	                if ($reinc_consumidor_revenda=='R'){
	                    if (strlen($reinc_revenda_nome) == 0){
	                        $reinc_revenda_nome = $reinc_consumidor_nome;
	                    }
	                }
	                $xls .= "<td bgcolor='$cor'>$reinc_consumidor_nome</td>";
	                $xls .= "<td bgcolor='$cor'>$reinc_produto_referencia - ".substr($reinc_produto_descricao ,0,20)."</td>";
	                $xls .= "<td bgcolor='$cor'>$reinc_defeito_constatado</td>";
	                $xls .= "<td bgcolor='$cor' title='Observação: ".$reinc_status_observacao."'>$reinc_status_descricao</td>";
	                $xls .= "<td bgcolor='$cor'>$reinc_obs_reincidencia</td>";
	            $xls .= "</tr>";
        	}
            /* ---------------- OS REINCIDENTE -------------------*/
        }
        $xls .= "</table>";
        $xls .= "</form>";

        echo $xls;

        $data_xls = date("Y-m-d_H-i-s");

        $arquivo_nome = "relatorio-os-reincidente-$login_fabrica-$data_xls.xls";

        $path       = "xls/";
        $path_tmp   = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        $fp = fopen($arquivo_completo_tmp, "w+");
            fputs($fp, $xls);
        fclose($fp);

        if(file_exists($arquivo_completo_tmp)){
            echo `cp $arquivo_completo_tmp $arquivo_completo `;	
            echo `rm $arquivo_completo_tmp `;	

            echo"<br><br><table  border='0' cellspacing='2' cellpadding='2' align='center'>";
            echo"<tr>";		
                echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='$arquivo_completo'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
            echo "</tr>";
            echo "</table>";
        }

    }else{
        echo "<center>Nenhuma OS encontrada.</center>";
    }
}

include "rodape.php" ?>
</div>
