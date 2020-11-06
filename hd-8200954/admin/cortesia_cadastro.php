<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

if (strlen($_GET['os_sedex']) > 0)  $os_sedex = $_GET['os_sedex'];
if (strlen($_POST['os_sedex']) > 0) $os_sedex = $_POST['os_sedex'];

$btn_acao = $_POST['btn_acao'];

#--------------- Gravar Sedex ----------------------
if ($btn_acao == 'gravar') {
	$erro = "";

	if (strlen ($_POST["posto_origem"]) == 0){
		$msg_erro = "Digite o posto de origem.";
	}else{
		$xposto_origem = "'". trim($_POST["posto_origem"]) ."'";
	}

	if (strlen ($_POST["posto_destino"]) == 0){
		$msg_erro = "Digite o posto de destino.";
	}else{
		$xposto_destino = "'". trim($_POST["posto_destino"]) ."'";
	}
	
	if (strlen ($_POST["solicitante"]) == 0) {
		$xsolicitante = 'null';
	}else{
		$xsolicitante = "'". trim($_POST["solicitante"]) ."'";

		$sql = "SELECT fnc_limpa_string($xsolicitante)";
		$fnc          = @pg_exec($con,$sql);
		$xsolicitante = "'". @pg_result ($fnc,0,0) . "'";
	}
	
	if (strlen ($_POST["data_lancamento"]) == 0) {
		$xdata_lancamento = 'null';
	}else{
		$data_lancamento = trim($_POST["data_lancamento"]);
		
		if (strlen($data_lancamento) >= 10) {
			$aux_data_lancamento = str_replace ("/","-",$data_lancamento);
			$aux_data_lancamento = str_replace (".","-",$data_lancamento);
		}else{
			$aux_data_lancamento = strval(substr($data_lancamento,0,2)) ."-". strval(substr($data_lancamento,2,2)) ."-". strval(substr($data_lancamento,4,4));
		}
		
		$sql = "SELECT fnc_formata_data('$aux_data_lancamento')";
		$res = @pg_exec ($con,$sql);
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con);
		}else{
			$xdata_lancamento = "'". pg_result($res,0,0) ."'";
		}
	}
	
	if (strlen ($_POST["sua_os"]) == 0) {
		$xsua_os = 'null';
	}else{
		$xsua_os = "'". trim($_POST["sua_os"]) ."'";
	}
	
	$res = pg_exec($con,"BEGIN WORK");
	
	if (strlen ($os_sedex) == 0) {
		$sql = "INSERT INTO tbl_os_sedex (
							fabrica      ,
							posto_origem ,
							posto_destino,
							solicitante  ,
							data         ,
							sua_os
				) VALUES (
							$login_fabrica    ,
							(SELECT tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.codigo_posto = $xposto_origem) ,
							(SELECT tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.codigo_posto = $xposto_destino),
							$xsolicitante     ,
							$xdata_lancamento ,
							$xsua_os
				)";
	}else{
			$sql = "UPDATE tbl_os_sedex SET
							posto_origem  = (SELECT tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.codigo_posto = $xposto_origem) ,
							posto_destino = (SELECT tbl_posto_fabrica.posto WHERE tbl_posto_fabrica.codigo_posto = $xposto_destino),
							solicitante   = $xsolicitante     ,
							data          = $xdata_lancamento ,
							sua_os        = $xsua_os
					WHERE   tbl_os_sedex.os_sedex = $os_sedex;";
	}
	$res = @pg_exec ($con,$sql);
	
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$erro = pg_errormessage ($con) ;
		$erro = substr($erro,6);
		if (strpos($erro,'tbl_os_sedex_unico')) $erro = "Número da OS já digitado anteriormente.";
	}
	
	if (strlen($erro) == 0 AND strlen($os_sedex) == 0) {
		$res      = @pg_exec ($con,"SELECT currval ('tbl_os_sedex_seq')");
		$os_sedex = @pg_result ($res,0,0);
	}
	
	if (strlen($erro) == 0) {
		$sql = "SELECT fn_valida_os_sedex($os_sedex,$login_fabrica);";
		$res = @pg_exec($con,$sql);
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
			$erro = substr($erro,6);
		}
	}
	
	if (strlen($erro) == 0) {
		if ($os_sedex > 0) {
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
				
				$referencia = strtoupper(trim($referencia));
				$referencia = str_replace ("-","",$referencia);
				$referencia = str_replace (" ","",$referencia);
				$referencia = str_replace ("/","",$referencia);
				$referencia = str_replace (".","",$referencia);
				
				if (strlen($referencia) == 0) {
					$xreferencia = "null";
				}else{
					$sql = "SELECT peca 
							FROM tbl_peca
							WHERE referencia_pesquisa = '$referencia'";
					$res = @pg_exec ($con,$sql);
					
					if (strlen(pg_errormessage($con)) > 0) {
						$erro = pg_errormessage($con) ;
						$erro = substr($erro,6);
					}
					
					if (pg_numrows($res) > 0){
						$xpeca = pg_result($res,0,0);
					}else{
						$erro = "Peça $referencia não cadastrada.";
					}
				}
				
				if (strlen($erro) > 0){
					$matriz = $matriz . ";" . $y . ";";
					break;
				}
				
				if (strlen($qtde) == 0) {
					$xqtde = "null";
				}else{
					$xqtde = "'". $qtde ."'";
				}
				
				if(strlen($referencia) == 0) {
					if (strlen($item) > 0 AND $novo == 'f') {
						$sql = "DELETE FROM tbl_os_sedex_item WHERE tbl_os_sedex_item.os_sedex_item = $item";
						$res = @pg_exec($con,$sql);
					}
				}else{
					if ($novo == 't') {
						$sql = "INSERT INTO tbl_os_sedex_item (
											os_sedex,
											peca    ,
											qtde
								) VALUES (
											$os_sedex   ,
											$xpeca,
											$xqtde
								);";
					}else{
						$sql = "UPDATE tbl_os_sedex_item SET
											os_sedex = $os_sedex,
											peca     = $xpeca   ,
											qtde     = $xqtde
								WHERE  tbl_os_sedex_item.os_sedex_item = $item;";
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
					
					if (strlen($erro) == 0 AND strlen($item) == 0) {
						$res           = @pg_exec ($con,"SELECT currval ('tbl_os_sedex_item_seq')");
						$os_sedex_item = @pg_result ($res,0,0);
					}else{
						$os_sedex_item = $item;
					}
					
					if (strlen($erro) == 0) {
						$sql = "SELECT fn_valida_os_sedex_item($os_sedex_item,$login_fabrica);";
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
	}
	
	if (strlen($erro) > 0) {
		$res = pg_exec($con,"ROLLBACK WORK");
		
		if (strpos ($erro,"ExecAppend: Fail to add null value in not null attribute posto_destino") > 0)
		$erro = "Código do posto destino não é válido.";
		
		$os_sedex = $_POST["os_sedex"];
		
		$msg_erro  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg_erro .= $erro;
	}else{
		$res = pg_exec($con,"COMMIT WORK");
		
		header ("Location: $PHP_SELF?gravou=ok");
		exit;
	}
}

if ($gravou == "ok") {
	$msg_erro = "Lançamento de OS de SEDEX efetuado com sucesso !";
}

if (strlen ($os_sedex) > 0) {
	$sql = "SELECT  tbl_os_sedex.posto_origem                       ,
					tbl_os_sedex.posto_destino                      ,
					tbl_os_sedex.solicitante                        ,
					to_char(tbl_os_sedex.data, 'DD/MM/YYYY') AS data,
					tbl_os_sedex.despesas                           ,
					tbl_os_sedex.controle                           ,
					tbl_os_sedex.sua_os                             ,
					tbl_os_sedex.finalizada
			FROM    tbl_os_sedex
			WHERE   tbl_os_sedex.os_sedex = $os_sedex";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		$posto_origem    = trim (pg_result ($res,0,posto_origem));
		$posto_destino   = trim (pg_result ($res,0,posto_destino));
		$solicitante     = trim (pg_result ($res,0,solicitante));
		$data_lancamento = trim (pg_result ($res,0,data));
		$despesas        = trim (pg_result ($res,0,despesas));
		$controle        = trim (pg_result ($res,0,controle));
		$sua_os          = trim (pg_result ($res,0,sua_os));
		$finalizada      = trim (pg_result ($res,0,finalizada));
		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.posto = $posto_origem;";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$posto_origem      = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_origem = trim(pg_result($res1,0,nome));
		}
		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.posto = $posto_destino;";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$posto_destino      = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_destino = trim(pg_result($res1,0,nome));
		}
	}
}

$title     = "OS Cortesia";
$cabecalho = "OS Cortesia";

include "cabecalho.php";

if(strlen($data_lancamento) == 0) $data_lancamento = date("d/m/Y");


?>

<script language="JavaScript">

function fnc_pesquisa_posto (campo1, campo2, tipo, posto) {
	var url = "";
	if (tipo == "codigo" ) {
		var xcampo = campo1;
	}
	if (tipo == "nome" ) {
		var xcampo = campo2;
	}
	if ((campo1 == "" || campo2 == "") && xcampo != "") {
		var url = "";
		url = "pesquisa_posto_sedex.php?campo=" + xcampo + "&tipo=" + tipo + "&posto=" + posto;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.codigo  = campo1;
		janela.nome    = campo2;
		janela.focus();
	}

}

function fnc_pesquisa_codigo_peca (codigo, nome, linha) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_peca_sedex.php?referencia=" + codigo + "&linha=" + linha;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_peca (codigo, nome, linha) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_peca_sedex.php?nome=" + nome + "&linha=" + linha;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.focus();
    }
}


function mascara_data(data){
    var mydata = '';
        mydata = mydata + data;
        myform = "data_lancamento";

        if (mydata.length == 2){
            mydata = mydata + '/';
            window.document.frm_cortesia.elements[myform].value = mydata;
        }
        if (mydata.length == 5){
            mydata = mydata + '/';
            window.document.frm_cortesia.elements[myform].value = mydata;
        }
        if (mydata.length == 10){
            verifica_data();
        }
    }

function verifica_data () {
    dia = (window.document.frm_cortesia.elements[myform].value.substring(0,2));
    mes = (window.document.frm_cortesia.elements[myform].value.substring(3,5));
    ano = (window.document.frm_cortesia.elements[myform].value.substring(6,10));

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

        if (window.document.frm_cortesia.elements[myform].value == "") {
            situacao = "falsa";
        }

        if (situacao == "falsa") {
            alert("Data inválida!");
            window.document.frm_cortesia.elements[myform].focus();
        }
    }

function mascara_hora(hora, controle){
    var myhora = '';
    myhora = myhora + hora;
    myform = "hora" + controle;

    if (myhora.length == 2){
        myhora = myhora + ':';
        window.document.frm_cortesia.elements[myform].value = myhora;
    }
    if (myhora.length == 5){
        verifica_hora();
    }
}

function verifica_hora(){
    hrs = (window.document.frm_cortesia.elements[myform].value.substring(0,2));
    min = (window.document.frm_cortesia.elements[myform].value.substring(3,5));

    situacao = "";
    // verifica data e hora
    if ((hrs < 00 ) || (hrs > 23) || ( min < 00) ||( min > 59)){
        situacao = "falsa";
    }

    if (window.document.frm_cortesia.elements[myform].value == "") {
        situacao = "falsa";
    }

    if (situacao == "falsa") {
        alert("Hora inválida!");
        window.document.frm_cortesia.elements[myform].focus();
    }
}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<form name="frm_cortesia" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="os_sedex" value="<? echo $os_sedex ?>">

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<? 
		echo $msg_erro;
		$data_msg = date ('d-m-Y h:i');
		echo `echo '$data_msg ==> $msg_erro' >> /tmp/black-os-solicitacao.err`;
?>
	</td>
</tr>
</table>
<?
}
?>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td width="100%" align='left' class="menu_top" colspan="3">Posto</td>
</tr>
<tr>
	<td class="menu_top">Código</td>
	<td class="menu_top">Nome</td>
	<td class="menu_top">Liberação</td>
</tr>
<tr>
	<td align='left' class="table_line">
		<input type="text" name="posto_destino" size="10" maxlength="" value="<? echo $posto_destino ?>" onblur="javascript:fnc_pesquisa_posto (document.frm_cortesia.posto_destino.value, document.frm_cortesia.nome_posto_destino.value, 'codigo', 'destino')" class="frm" style="width:70px">
		<img src="imagens/btn_lupa.gif" onclick="javascript:fnc_pesquisa_posto (document.frm_cortesia.posto_destino.value, document.frm_cortesia.nome_posto_destino.value, 'codigo', 'destino')" style='cursor:hand;'>
	</td>
	<td align='left' class="table_line">
		<input type="text" name="nome_posto_destino" size="50" maxlength="50" value="<? echo $nome_posto_destino ?>" onblur="javascript:fnc_pesquisa_posto (document.frm_cortesia.posto_destino.value, document.frm_cortesia.nome_posto_destino.value, 'nome', 'destino')" class="frm" style="width:310px">
		<img src="imagens/btn_lupa.gif" onclick="javascript:fnc_pesquisa_posto (document.frm_cortesia.posto_destino.value, document.frm_cortesia.nome_posto_destino.value, 'codigo', 'destino')" style='cursor:hand;'>
	</td>
	<td align='left' class="table_line">
		<input type="text" name="liberacao" size="12" maxlength="12" value="<? echo $liberacao ?>" class="frm">
	</td>
</tr>
</table>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td width="100%" class="menu_top" colspan="3">Selecione a(s) peça(s)</td>
</tr>
<tr>
	<td width="20%" class="menu_top">Referência</td>
	<td width="65%" class="menu_top">Descrição</td>
	<td width="15%" class="menu_top">Qtde</td>
</tr>
<?
if (strlen($os_sedex) > 0 AND strlen($erro) == 0){
	$sql = "SELECT  tbl_os_sedex_item.peca
			FROM    tbl_os_sedex_item
			JOIN    tbl_peca     USING (peca)
			JOIN    tbl_os_sedex USING (os_sedex)
			WHERE   tbl_os_sedex.os_sedex = $os_sedex;";
	$res = @pg_exec ($con,$sql);
}

for ($y=0; $y<5; $y++) {
	if (strlen($os_sedex) > 0 AND strlen($erro) == 0){
		$xpeca = trim(@pg_result($res,$y,peca));
		
		$sql = "SELECT  tbl_os_sedex_item.os_sedex_item,
						tbl_peca.referencia            ,
						tbl_peca.descricao             ,
						tbl_os_sedex_item.qtde         ,
						tbl_os_sedex_item.preco
				FROM    tbl_os_sedex_item
				JOIN    tbl_peca     USING (peca)
				JOIN    tbl_os_sedex USING (os_sedex)
				WHERE   tbl_os_sedex_item.peca     = $xpeca
				AND     tbl_os_sedex_item.os_sedex = $os_sedex;";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) == 0) {
			$novo       = 't';
			$item       = $_POST["item"       .$y];
			$referencia = $_POST["referencia" .$y];
			$descricao  = $_POST["descricao"  .$y];
			$qtde       = $_POST["qtde"       .$y];
		}else{
			$novo       = 'f';
			$item       = trim(pg_result($res1,0,os_sedex_item));
			$referencia = trim(pg_result($res1,0,referencia));
			$descricao  = trim(pg_result($res1,0,descricao));
			$qtde       = trim(pg_result($res1,0,qtde));
		}
	}else{
		$novo       = 't';
		$item       = $_POST["item"       .$y];
		$referencia = $_POST["referencia" .$y];
		$descricao  = $_POST["descricao"  .$y];
		$qtde       = $_POST["qtde"       .$y];
	}
	
	if (strstr($matriz, ";" . $y . ";")) {
		$cor = "#CC3333";
	}else{
		$cor = "#ffffff";
	}
	
	echo "<tr bgcolor='$cor'>\n";
	echo "<td align='center' class='table_line'>\n";
	echo "<input type='text' name='referencia$y' size='10' maxlength='15' value='$referencia' class='frm' style='width:100px'>\n";
	echo "<img src=\"imagens/btn_lupa.gif\" onclick=\"javascript:fnc_pesquisa_codigo_peca (document.frm_cortesia.referencia$y.value, document.frm_cortesia.descricao$y.value, $y)\" style='cursor:hand;'>";
	echo "</td>\n";
	
	echo "<td align='left' class='table_line'>\n";
	echo "<input type='text' name='descricao$y' size='50' maxlength='50' value='$descricao' class='frm' style='width:410px'>\n";
	echo "<img src=\"imagens/btn_lupa.gif\" onclick=\"javascript:fnc_pesquisa_nome_peca (document.frm_cortesia.referencia$y.value, document.frm_cortesia.descricao$y.value, $y)\" style='cursor:hand;'>";
	echo "</td>\n";
	
	echo "<td align='center' class='table_line'>\n";
	echo "<input type='text' name='qtde$y' size='10' maxlength='10' value='$qtde' class='frm' style='width:70px'>\n";
	echo "</td>\n";

	echo "</tr>\n";
	
	echo "<input type='hidden' name='novo$y' value='$novo'>";
	echo "<input type='hidden' name='item$y' value='$item'>";
}
?>
</table>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td class="menu_top">Observações</td>
</tr>
<tr>
	<td align='left' class="table_line">
		<textarea name="posto_destino" cols="50" rows='5' class="frm" style="width:100%"><? echo $posto_destino ?></textarea>
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center' width="100%">
		<!--<input type="image" src="imagens/gravar.gif" name="btngravar">-->
		<input type='hidden' name='btn_acao' value='0'>
		<img src='imagens/btn_gravar.gif' style='cursor: hand;' onclick="javascript: if ( document.frm_cortesia.btn_acao.value == '0' ) { alert('Gravando e enviando os e-Mails de confirmação...') ; document.frm_cortesia.btn_acao.value='gravar'; document.frm_cortesia.submit() ; } else { alert ('Aguarde submissão...'); }">
	</td>
</tr>
</table>

</form>

<?include "rodape.php";?>