<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

include 'token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$btn_acao = strtolower($_POST['btn_acao']);

$msg_erro = "";
/*
$res_distrib = @pg_exec($con,"SELECT * FROM tbl_distribuidor WHERE posto = $login_posto");
if (pg_numrows($res_distrib) > 0) {
	$visual_black = "os-distr";
	$ident = "distr";
}else{
	$visual_black = "os-posto";
	$ident = "posto";
}
*/
if (strlen($cookie_login["cook_solicitacao"]) > 0) {
	$cook_solicitacao = trim($cookie_login["cook_solicitacao"]);
}

#--------------- Gravar OS ----------------------
if ($btn_acao == '1') {
	$erro = "";
	
	if (strlen ($_POST["posto_origem"]) == 0) {
		$xposto_origem = 'null';
	}else{
		$xposto_origem = "'". trim($_POST["posto_origem"]) ."'";
	}
	
	if (strlen ($_POST["posto_destino"]) == 0) {
		$xposto_destino = 'null';
	}else{
		$xposto_destino = "'". trim($_POST["posto_destino"]) ."'";
	}
	
	if (strlen ($_POST["solicitante"]) == 0) {
		$xsolicitante = 'null';
	}else{
		$xsolicitante = "'". trim($_POST["solicitante"]) ."'";
		
		$fnc          = @pg_exec($con,"SELECT fnc_limpa_string($xsolicitante)");
		$xsolicitante = "'". @pg_result ($fnc,0,0) . "'";
	}
	
	if (strlen ($_POST["data"]) == 0) {
		$xdata = 'null';
	}else{
		$data = trim($_POST["data"]);
		
		if (strlen($data) >= 10) {
			$aux_data = str_replace ("/","-",$data);
			$aux_data = str_replace (".","-",$data);
		}else{
			$aux_data = strval(substr($data,0,2)) ."-". strval(substr($data,2,2)) ."-". strval(substr($data,4,4));
		}
		
		$res = @pg_exec ($con,"SELECT fnc_formata_data('$aux_data')");
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con);
		}else{
			$xdata = "'". pg_result($res,0,0) ."'";
		}
	}
	
	if (strlen ($_POST["peca"]) == 0) {
		$xpeca = 'null';
	}else{
		$xpeca = "'". trim($_POST["peca"]) ."'";
	}
	
	if (strlen ($_POST["despesas"]) == 0) {
		$xdespesas = '0';
	}else{
		$xdespesas = "'". trim($_POST["despesas"]) ."'";
		$xdespesas = str_replace (",",".",$xdespesas);
		
		$fnc       = @pg_exec($con,"SELECT fnc_limpa_moeda($xdespesas)");
		$xdespesas = "'". @pg_result ($fnc,0,0) . "'";
	}

	if (strlen ($_POST["controle"]) == 0) {
		$xcontrole = 'null';
	}else{
		$xcontrole = "'". trim($_POST["controle"]) ."'";
	}
	
	if (strlen ($_POST["sua_os"]) == 0) {
		$xsua_os = 'null';
	}else{
		$xsua_os = "'". trim($_POST["sua_os"]) ."'";
		
		$fnc     = @pg_exec($con,"SELECT fnc_so_numeros($xsua_os)");
		$xsua_os = "'". @pg_result ($fnc,0,0) . "'";
	}
	
	$res = pg_exec($con,"BEGIN WORK");
	
	if (strlen ($cook_solicitacao) == 0) {
		$sql = "INSERT INTO tbl_os_solicitacao (
							posto_origem ,
							posto_destino,
							solicitante  ,
							data         ,
							despesas     ,
							controle     ,
							sua_os
				) VALUES (
							(SELECT tbl_posto.posto WHERE tbl_posto.codigo = $xposto_origem) ,
							(SELECT tbl_posto.posto WHERE tbl_posto.codigo = $xposto_destino),
							$xsolicitante  ,
							$xdata         ,
							$xdespesas     ,
							$xcontrole     ,
							$xsua_os
				)";
		}else{
			$sql = "UPDATE tbl_os_solicitacao SET
							posto_origem  = (SELECT tbl_posto.posto WHERE tbl_posto.codigo = $xposto_origem) ,
							posto_destino = (SELECT tbl_posto.posto WHERE tbl_posto.codigo = $xposto_destino),
							solicitante   = $xsolicitante      ,
							data          = $xdata             ,
							despesas      = $xdespesas         ,
							controle      = $xcontrole         ,
							sua_os        = $xsua_os
					WHERE   tbl_os_solicitacao.os_solicitacao = $cook_solicitacao";
	}
	$res = @pg_exec ($con,$sql);
	
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$erro = pg_errormessage ($con) ;
		$erro = substr($erro,6);
	}
	
	if (strlen($erro) > 0) {
		$res = pg_exec($con,"ROLLBACK WORK");
		
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}
	
	if (strlen($msg) == 0) {
		if (strlen ($cookie_login["cook_solicitacao"]) == 0) {
			$res              = @pg_exec ($con,"SELECT currval ('tbl_os_solicitacao_seq')");
			$cook_solicitacao = @pg_result ($res,0,0);
			
			# cookie expira em 24 horas
			setcookie ("cook_solicitacao",$cook_solicitacao,time()+(3600*24));
		}else{
			$cook_solicitacao = trim($cookie_login["cook_solicitacao"]);
		}
	
		if ($cook_solicitacao > 0) {
			$sem_item   = 0;
			$qtde_linha = 5;
			
			for ($y=0; $y<$qtde_linha; $y++){
				$referencia = trim($_POST["referencia" .$y]);
				
				if (strlen($referencia) == 0) {
					$sem_item = $sem_item + 1;
				}
			}
			
			for ($y=0; $y<$qtde_linha; $y++){
				$novo       = trim($_POST["novo"       .$y]);
				$item       = trim($_POST["item"       .$y]);
				$referencia = trim($_POST["referencia" .$y]);
				$qtde       = trim($_POST["qtde"       .$y]);
				$preco      = trim($_POST["preco"      .$y]);
				
				if (strlen($referencia) == 0) {
					$aux_referencia = "null";
				}else{
					$aux_referencia = "'". $referencia ."'";
				}
				
				if (strlen($qtde) == 0) {
					$aux_qtde = "null";
				}else{
					$aux_qtde = "'". $qtde ."'";
				}
				
				if (strlen($preco) == 0) {
					$aux_preco = "null";
				}else{
					$aux_preco = "'". $preco ."'";
				}
				
				if(strlen($referencia) == 0) {
					if (strlen($item) > 0 AND $novo == 'f') {
						$sql = "DELETE FROM tbl_item_os_solicitacao WHERE item_os_solicitacao= $item";
						$res = @pg_exec($con,$sql);
					}
				}else{
					if ($novo == 't') {
						$sql = "INSERT INTO tbl_item_os_solicitacao (
											os_solicitacao,
											referencia    ,
											qtde          ,
											preco
								) VALUES (
											$cook_solicitacao,
											$aux_referencia  ,
											$aux_qtde        ,
											fnc_limpa_moeda($aux_preco)
								)";
					}else{
						$sql = "UPDATE tbl_item_os_solicitacao SET
											os_solicitacao = $cook_solicitacao,
											referencia     = $aux_referencia  ,
											qtde           = $aux_qtde        ,
											preco          = fnc_limpa_moeda($aux_preco)
								WHERE  tbl_item_os_solicitacao.item_os_solicitacao = $item";
					}
					$res = @pg_exec($con,$sql);
					
					if (strlen ( pg_errormessage ($con) ) > 0) {
						$erro = pg_errormessage ($con) ;
						$erro = substr($erro,6);
					}
					
					if (strlen($erro) > 0){
						$matriz = $matriz . ";" . $y . ";";
						break;
					}
				}
			}
		}
	}
	
	if (strlen($erro) > 0) {
		setcookie ("cook_solicitacao");
		$cook_solicitacao = "";
		
		$res = pg_exec($con,"ROLLBACK WORK");
		
		if (strpos ($erro,"ExecAppend: Fail to add null value in not null attribute posto_destino") > 0)     $erro = "Código do posto destino não é válido.";
		
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}else{
		$res = pg_exec($con,"COMMIT WORK");
		
		header ("Location: os_finaliza_solicitacao.php?solicitacao=$cook_solicitacao");
		exit;
	}
}

if (strlen ($cook_solicitacao) > 0) {
	$sql = "SELECT  tbl_os_solicitacao.posto_destino                      ,
					tbl_os_solicitacao.solicitante                        ,
					to_char(tbl_os_solicitacao.data, 'DD/MM/YYYY') AS data,
					tbl_os_solicitacao.despesas                           ,
					tbl_os_solicitacao.controle                           ,
					tbl_os_solicitacao.sua_os
			FROM    tbl_os_solicitacao
			WHERE   tbl_os_solicitacao.os_solicitacao = $cook_solicitacao";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		$posto_destino = trim (pg_result ($res,0,posto_destino));
		$solicitante   = trim (pg_result ($res,0,solicitante));
		$data          = trim (pg_result ($res,0,data));
		$despesas      = trim (pg_result ($res,0,despesas));
		$controle      = trim (pg_result ($res,0,controle));
		$sua_os        = trim (pg_result ($res,0,sua_os));
		
		$sql = "SELECT  tbl_posto.codigo     ,
						tbl_posto.nome       ,
						tbl_posto.endereco   ,
						tbl_posto.numero     ,
						tbl_posto.complemento,
						tbl_posto.bairro     ,
						tbl_posto.cep        ,
						tbl_cidade.cidade    ,
						tbl_cidade.estado
				FROM    tbl_posto
				JOIN    tbl_cidade ON tbl_cidade.municipio = tbl_posto.municipio
				WHERE   tbl_posto.posto = $posto_destino;";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$posto_destino             = trim(pg_result($res1,0,codigo));
			$nome_posto_destino        = trim(pg_result($res1,0,nome));
			$endereco_posto_destino    = trim(pg_result($res1,0,endereco));
			$numero_posto_destino      = trim(pg_result($res1,0,numero));
			$complemento_posto_destino = trim(pg_result($res1,0,complemento));
			$bairro_posto_destino      = trim(pg_result($res1,0,bairro));
			$cep_posto_destino         = trim(pg_result($res1,0,cep));
			$cidade_posto_destino      = trim(pg_result($res1,0,cidade));
			$estado_posto_destino      = trim(pg_result($res1,0,estado));
		}
		
		$sql = "SELECT  tbl_peca.referencia
				FROM    tbl_peca
				WHERE   trim(tbl_peca.peca) = upper(trim($peca));";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$peca = trim(pg_result($res1,0,referencia));
		}
	}
}


$body        = "onload=\"javascript: frm_solicitacao.posto_destino.focus()\";";
$title      = "Solicitação de envio de peças em garantia";
$cabecalho   = "Envio de peças em garantia";
$layout_menu = 'os';

include "cabecalho.php";

?>


<script language="JavaScript">
function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_codigo_peca (codigo, nome, linha) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_peca_solicitacao.php?codigo=" + codigo + "&linha=" + linha;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_peca (codigo, nome, linha) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_peca_solicitacao.php?nome=" + nome + "&linha=" + linha;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.focus();
    }
}


function mascara_data(data){
    var mydata = '';
        mydata = mydata + data;
        myform = "data";

        if (mydata.length == 2){
            mydata = mydata + '/';
            window.document.frm_solicitacao.elements[myform].value = mydata;
        }
        if (mydata.length == 5){
            mydata = mydata + '/';
            window.document.frm_solicitacao.elements[myform].value = mydata;
        }
        if (mydata.length == 10){
            verifica_data();
        }
    }

function verifica_data () {
    dia = (window.document.frm_solicitacao.elements[myform].value.substring(0,2));
    mes = (window.document.frm_solicitacao.elements[myform].value.substring(3,5));
    ano = (window.document.frm_solicitacao.elements[myform].value.substring(6,10));

    situacao = "";
   // verifica o dia valido para cada mes
       if ((dia < 01)||(dia < 01 || dia > 30) && (  mes == 04 || mes == 06 || mes == 09 || mes == 11 ) || dia > 31) {
           situacao = "falsa";
       }

    // verifica se o mes e valido
        if (mes < 01 || mes > 12 ) {
            situacao = "falsa";
        }

    // verifica se e ano bissexto
        if (mes == 2 && ( dia < 01 || dia > 29 || ( dia > 28 && (parseInt(ano / 4) != ano / 4)))) {
            situacao = "falsa";
        }

        if (window.document.frm_solicitacao.elements[myform].value == "") {
            situacao = "falsa";
        }

        if (situacao == "falsa") {
            alert("Data inválida!");
            window.document.frm_solicitacao.elements[myform].focus();
        }
    }

function mascara_hora(hora, controle){
    var myhora = '';
    myhora = myhora + hora;
    myform = "hora" + controle;

    if (myhora.length == 2){
        myhora = myhora + ':';
        window.document.frm_solicitacao.elements[myform].value = myhora;
    }
    if (myhora.length == 5){
        verifica_hora();
    }
}

function verifica_hora(){
    hrs = (window.document.frm_solicitacao.elements[myform].value.substring(0,2));
    min = (window.document.frm_solicitacao.elements[myform].value.substring(3,5));

    situacao = "";
    // verifica data e hora
    if ((hrs < 00 ) || (hrs > 23) || ( min < 00) ||( min > 59)){
        situacao = "falsa";
    }

    if (window.document.frm_solicitacao.elements[myform].value == "") {
        situacao = "falsa";
    }

    if (situacao == "falsa") {
        alert("Hora inválida!");
        window.document.frm_solicitacao.elements[myform].focus();
    }
}
</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}
</style>

<form name="frm_solicitacao" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="posto_origem" value="<? echo $posto ?>">

<?
	if(strlen($msg) > 0){
?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" width="98%" class="error">
<?
	echo $msg;
	$data_msg = date ('d-m-Y h:i');
	echo `echo '$data_msg ==> $msg' >> /tmp/black-os-solicitacao.err`;
?>
	</td>
</tr>
</table>
<?
	}
?>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="100%" align='left' class="menu_top">Posto Origem da Mercadoria</td>
</tr>
<tr>
	<td width="100%" align='left' class="table_line"><? echo $posto ." - ". $nome ?></td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="100%" align='left' class="menu_top" colspan="2">Posto Destino da Mercadoria</td>
</tr>
<tr>
	<td width="20%" align='left' class="menu_top">Código</td>
	<td width="80%" align='left' class="menu_top">Nome</td>
</tr>
<tr>
	<td width="20%" align='left' class="">
		<input type="text" name="posto_destino" size="10" maxlength="" value="<? echo $posto_destino ?>" onblur="javascript:fnc_pesquisa_codigo_posto (this.value, window.document.frm_solicitacao.nome_posto_destino.value)" class="textbox" style="width:70px">
	</td>
	<td width="80%" align='left' class="">
		<input type="text" name="nome_posto_destino" size="50" maxlength="50" value="<? echo $nome_posto_destino ?>" onblur="javascript:fnc_pesquisa_nome_posto (window.document.frm_solicitacao.posto_destino.value, this.value)" class="textbox" style="width:310px">
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="40%" align='left' class="menu_top">Endereço</td>
	<td width="20%" align='left' class="menu_top">Número</td>
	<td width="40%" align='left' class="menu_top">Complemento</td>
</tr>
<tr>
	<td width="40%" align='left'>
		<input type="text" name="endereco_posto_destino" size="50" maxlength="50" value="<? echo $endereco_posto_destino ?>" class="textbox" style="width:280px" disabled>
	</td>
	<td width="20%" align='left'>
		<input type="text" name="numero_posto_destino" size="50" maxlength="50" value="<? echo $numero_posto_destino ?>" class="textbox" style="width:50px" disabled>
	</td>
	<td width="40%" align='left'>
		<input type="text" name="complemento_posto_destino" size="50" maxlength="50" value="<? echo $complemento_posto_destino ?>" class="textbox" style="width:280px" disabled>
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="35%" align='left' class="menu_top">Bairro</td>
	<td width="15%" align='left' class="menu_top">Cep</td>
	<td width="35%" align='left' class="menu_top">Cidade</td>
	<td width="15%" align='left' class="menu_top">UF</td>
</tr>
<tr>
	<td width="30%" align='left'>
		<input type="text" name="bairro_posto_destino" size="50" maxlength="50" value="<? echo $bairro_posto_destino ?>" class="textbox" style="width:250px" disabled>
	</td>
	<td width="15%" align='left'>
		<input type="text" name="cep_posto_destino" size="50" maxlength="50" value="<? echo $cep_posto_destino ?>" class="textbox" style="width:70px" disabled>
	</td>
	<td width="30%" align='left'>
		<input type="text" name="cidade_posto_destino" size="50" maxlength="50" value="<? echo $cidade_posto_destino ?>" class="textbox" style="width:280px" disabled>
	</td>
	<td width="15%" align='left'>
		<input type="text" name="estado_posto_destino" size="50" maxlength="50" value="<? echo $estado_posto_destino ?>" class="textbox" style="width:30px" disabled>
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="30%" align='left' class="menu_top">Solicitado por</td>
	<td width="20%" align='left' class="menu_top">Data</td>
	<td width="20%" align='left' class="menu_top">Despesas</td>
	<td width="30%" align='left' class="menu_top">Controle do Objeto</td>
</tr>
<tr>
	<td width="30%" align='left'>
		<input type="text" name="solicitante" size="100" maxlength="50" value="<? echo $solicitante ?>" class="textbox" style="width:325px">
	</td>
	<td width="20%" align='left'>
		<input type="text" name="data" size="10" maxlength="10" value="<? echo $data ?>" OnKeyUp='mascara_data(this.value)' class="textbox" style="width:85px">
	</td>
	<td width="20%" align='left'>
		<input type="text" name="despesas" size = "10" maxlength="10" value="<? echo $despesas ?>" class="textbox" style="width:85px">
	</td>
	<td width="30%" align='left'>
		<input type="text" name="controle" size = "13" maxlength="13" value="<? echo $controle ?>" class="textbox" style="width:120px">
	</td>
</tr>
</table>

<table width="700" align='center' border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="100%" align='left' class="menu_top" colspan="4">OS Black&Decker</td>
</tr>
<tr>
	<td width="100%" align='left' class="" colspan="4">
		<input type='text' name='sua_os' size='20' maxlength='20' value='<? echo $sua_os ?>' class='textbox' style='width:70px'>
	</td>
</tr>
<tr>
	<td width="100%" align='left' class="menu_top" colspan="4">Selecione a(s) peça(s)</td>
</tr>
<tr>
	<td width="20%" align='left' class="menu_top">Referência</td>
	<td width="40%" align='left' class="menu_top">Descrição</td>
	<td width="20%" align='left' class="menu_top">Qtde</td>
	<td width="20%" align='left' class="menu_top">Preço</td>
</tr>
<?
if (strlen($cook_solicitacao) > 0 AND strlen($erro) == 0){
	$sql = "SELECT  tbl_item_os_solicitacao.peca,
					tbl_peca.nome               ,
					tbl_item_os_solicitacao.preco
			FROM    tbl_item_os_solicitacao
			JOIN    tbl_peca ON tbl_peca.peca = tbl_item_os_solicitacao.peca
			WHERE   tbl_item_os_solicitacao.os_solicitacao = $cook_solicitacao";
	$res = @pg_exec ($con,$sql);
}

for ($y=0; $y<5; $y++) {
	if (strlen($cook_solicitacao) > 0 AND strlen($erro) == 0){
		$xpeca = trim(@pg_result($res,$y,peca));
		
		$sql = "SELECT  tbl_item_os_solicitacao.item_os_solicitacao,
						tbl_peca.referencia                        ,
						tbl_peca.nome                              ,
						tbl_item_os_solicitacao.qtde               ,
						tbl_item_os_solicitacao.preco
				FROM    tbl_item_os_solicitacao
				JOIN    tbl_peca ON tbl_peca.peca = tbl_item_os_solicitacao.peca
				WHERE   tbl_item_os_solicitacao.peca           = $xpeca
				AND     tbl_item_os_solicitacao.os_solicitacao = $cook_solicitacao";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) == 0) {
			$novo       = 't';
			$item       = $_POST["item"       .$y];
			$referencia = $_POST["referencia" .$y];
			$descricao  = $_POST["descricao"  .$y];
			$qtde       = $_POST["qtde"       .$y];
			$preco      = $_POST["preco"      .$y];
		}else{
			$novo       = 'f';
			$item       = trim(pg_result($res1,0,item_os_solicitacao));
			$referencia = trim(pg_result($res1,0,referencia));
			$descricao  = trim(pg_result($res1,0,nome));
			$qtde       = trim(pg_result($res1,0,qtde));
			$preco      = trim(pg_result($res1,0,preco));
		}
	}else{
		$novo       = 't';
		$item       = $_POST["item"       .$y];
		$referencia = $_POST["referencia" .$y];
		$descricao  = $_POST["descricao"  .$y];
		$qtde       = $_POST["qtde"       .$y];
		$preco      = $_POST["preco"      .$y];
	}
	
	if (strstr($matriz, ";" . $y . ";")) {
		$cor = "#CC3333";
	}else{
		$cor = "#F6F6D6";
	}
	
	echo "<tr>\n";

	echo "<td width='20%' align='center' bgcolor='#FFFFFF'>\n";
	echo "<input type='text' name='referencia$y' size='10' maxlength='15' value='$referencia' onblur='javascript:fnc_pesquisa_codigo_peca (this.value, window.document.frm_solicitacao.descricao$y.value, $y)' class='textbox' style='width:100px'>\n";
	echo "</td>\n";
	
	echo "<td width='40%' align='left' bgcolor='#FFFFFF'>\n";
	echo "<input type='text' name='descricao$y' size='50' maxlength='50' value='$descricao' onblur='javascript:fnc_pesquisa_nome_peca (window.document.frm_solicitacao.referencia$y.value, this.value, $y)' class='textbox' style='width:350px'>\n";
	echo "</td>\n";
	
	echo "<td width='20%' align='center' bgcolor='#FFFFFF'>\n";
	echo "<input type='text' name='qtde$y' size='10' maxlength='10' value='$qtde' class='textbox' style='width:70px'>\n";
	echo "</td>\n";
	
	echo "<td width='20%' align='center' bgcolor='#FFFFFF'>\n";
	echo "<input type='text' name='preco$y' size='10' maxlength='' value='$preco' class='textbox' style='width:70px'>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	
	echo "<input type='hidden' name='novo$y' value='$novo'>";
	echo "<input type='hidden' name='item$y' value='$item'>";
}
?>
</table>

<!-- ============================ Botoes de Acao ========================= -->
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center' width="100%">
		<!--<input type="image" src="imagens/gravar.gif" name="btngravar">-->
		<input type='hidden' name='btn_acao' value=''>
		<img src='imagens/gravar.gif' style='cursor: pointer;' onclick="javascript: if ( document.frm_solicitacao.btn_acao.value == '0' ) { alert('Gravando OS') ; document.frm_solicitacao.btn_acao.value='1'; document.frm_solicitacao.submit() ; } else { alert ('Aguarde submissão da OS...'); }">
	</td>
</tr>
</table>

</form>

<?include "rodape.php";?>
