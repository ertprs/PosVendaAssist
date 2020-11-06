<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include "funcoes.php";

if ($login_fabrica != 93) {
	if($login_fabrica != 3){
		header ("Location: pedido_cadastro.php");
		exit;
	}
}


$btn_acao = trim(strtolower($_POST['btn_acao']));

$msg_erro = "";

$qtde_item = 30;

if (strlen($_GET['pedido']) > 0)  $pedido = trim($_GET['pedido']);
if (strlen($_POST['pedido']) > 0) $pedido = trim($_POST['pedido']);

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

##### E X C L U I R   P E D I D O #####
if ($btn_acao == "apagar") {

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido  = $pedido
			AND    tbl_pedido.fabrica = $login_fabrica;";
	$res = @pg_query($con,$sql);

	if (strlen(pg_errormessage($con)) > 0) {
		$res = pg_query($con,"ROLLBACK TRANSACTION");
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_pedido
				WHERE  tbl_pedido.pedido  = $pedido
				AND    tbl_pedido.fabrica = $login_fabrica;";
		$res = @pg_query($con,$sql);

		if (strlen(pg_errormessage($con)) > 0) {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
			$msg_erro = pg_errormessage($con);
		}else{
			$res = pg_query($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}
	}
}

##### F I N A L I Z A R   P E D I D O #####
if ($btn_acao == "finalizar") {
	if (strlen($msg_erro) == 0) {
		$sql =	"UPDATE tbl_pedido SET
					finalizado = current_timestamp,
					unificar_pedido = 't'
				WHERE tbl_pedido.pedido = $pedido
				AND   tbl_pedido.unificar_pedido ISNULL;";
		$res = pg_query ($con,$sql);

		if (strlen(pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "INSERT INTO tbl_pedido_alteracao (
					pedido
				)VALUES(
					$pedido
				);";
		$res = pg_query($con,$sql);

		if (strlen(pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage($con) ;
		}
	}

	if (strlen($msg_erro) == 0) {
		#$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		#$res = @pg_query($con,$sql);
		#$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($pedido,$login_fabrica);";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
/*
	$sql = "SELECT tbl_pedido.pedido
			FROM   tbl_pedido
			JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE  tbl_pedido.pedido = $pedido
			AND    tbl_pedido.total <= 200
			AND    trim(tbl_condicao.codigo_condicao) <> '15'
			AND    trim(tbl_condicao.codigo_condicao) <> '30'
			AND    trim(tbl_condicao.codigo_condicao) <> '60'
			AND    trim(tbl_condicao.codigo_condicao) <> '90';";
	$res = pg_query ($con,$sql);

	if (pg_numrows($res) > 0) $msg_erro = "
	<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
	<tr>
	<td align='left'>
	<font face='Verdana, Arial' size='2' color='#FFFFFF'>
	<b>Pedidos de valor até R$ 200,00 gerarão parcela única, sendo disponível estas opções</b>:
	<br>
	<UL>
		<LI>À VISTA ou 30 dias direto (sem taxa financeira);
		<LI>60 dias direto (3%);
		<LI>90 dias direto (6,10%)
	</UL>
	<br>
	<center>Favor alterar a condição de pagamento e clicar em gravar.</center>
	<br><br>
	</font>
	</td>
	</tr>
	</table>";
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
				WHERE  tbl_pedido.pedido = $pedido
				AND    tbl_pedido.total >  200
				AND    tbl_pedido.total <= 400
				AND    trim(tbl_condicao.codigo_condicao) <> '15'
				AND    trim(tbl_condicao.codigo_condicao) <> '30'
				AND    trim(tbl_condicao.codigo_condicao) <> '47'
				AND    trim(tbl_condicao.codigo_condicao) <> '60'
				AND    trim(tbl_condicao.codigo_condicao) <> '76'
				AND    trim(tbl_condicao.codigo_condicao) <> '90';";
		$res = pg_query($con,$sql);
		
		if (pg_numrows($res) > 0) $msg_erro = "
		<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
		<tr>
		<td align='left'>
		<font face='Verdana, Arial' size='2' color='#FFFFFF'>
		<b>Pedidos acima de R$ 200,00 e até R$ 400,00 gerarão duas parcelas, sendo disponível estas opções</b>:
		<br>
		<UL>
			<LI>30/60 dias (1,5%);
			<LI>60/90 dias (4,5%);
		</UL>
		e/ou
		<br>
		<UL>
			<LI>À VISTA ou 30 dias direto (sem taxa financeira);
			<LI>60 dias direto (3%);
			<LI>90 dias direto (6,10%)
		</UL>
		<br>
		<center>Favor alterar a condição de pagamento e clicar em gravar.</center>
		<br><br>
		</font>
		</td>
		</tr>
		</table>";
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
				WHERE  tbl_pedido.pedido = $pedido
				AND    tbl_pedido.total > 400
				AND    trim(tbl_condicao.codigo_condicao) <> '15'
				AND    trim(tbl_condicao.codigo_condicao) <> '30'
				AND    trim(tbl_condicao.codigo_condicao) <> '47'
				AND    trim(tbl_condicao.codigo_condicao) <> '60'
				AND    trim(tbl_condicao.codigo_condicao) <> '62'
				AND    trim(tbl_condicao.codigo_condicao) <> '76'
				AND    trim(tbl_condicao.codigo_condicao) <> '90'
				AND    trim(tbl_condicao.codigo_condicao) <> '191';";
		$res = pg_query($con,$sql);
		
		if (pg_numrows($res) > 0) $msg_erro = "
		Pedidos acima de R$ 400,00 gerarão três parcelas, sendo disponível estas opções:
		<br>
		<UL>
			<LI>30/60/90 dias (3%);
			<LI> 60/90/120 dias (6,10%);
		</UL>
		e/ou
		<br>
		<UL>
			<LI>À VISTA ou 30 dias direto (sem taxa financeira);
			<LI>60 dias direto (3%);
			<LI>90 dias direto (6,10%)
		</UL>
		e/ou
		<UL>
			<LI>30/60 dias (1,5%);
			<LI>60/90 dias (4,5%);
		</UL>
		<br>
		<br>
		Favor alterar a condição de pagamento e clicar em gravar.
		<br><br>";
	}
*/
	if (strlen($msg_erro) == 0) {
		header ("Location: pedido_finalizado.php?pedido=".$pedido);
		exit;
	}
}

##### D E L E T A R   I T E M   D O   P E D I D O #####
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	
	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_query($con,$sql);

	//DELETA PEDIDO SEM ITEM - HD 21009 27/5/2008
	$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
	$resP = @pg_query ($con,$sqlP);
	if(pg_numrows($resP)==0){
		$sql = "DELETE FROM tbl_pedido
		WHERE  tbl_pedido.pedido = $pedido";
		$res = @pg_query ($con,$sql);
	}

	if (strlen(pg_errormessage($con) ) > 0) {
		$msg_erro = pg_errormessage($con) ;
	}else{
		$pedido = $_GET["pedido"];
		header ("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}
}

##### D E L E T A R   T O D O S   O S   I T E N S   D O   P E D I D O #####
if ($_GET["excluir"] == "tudo") {
	$pedido = trim($_GET["pedido"]);

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido  = $pedido
			AND    tbl_pedido.fabrica = $login_fabrica;";

	$res = @pg_query($con,$sql);
	
	if (strlen(pg_errormessage($con) ) > 0) {
		$msg_erro = pg_errormessage($con) ;
	}else{
		header ("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}
}

##### G R A V A R   P E D I D O #####
if ($btn_acao == "gravar") {

	$xcodigo_posto = strtoupper(trim($_POST['codigo_posto']));
	$xnome_posto   = trim($_POST['nome_posto']);
	if (strlen($xcodigo_posto) > 0 OR strlen($xnome_posto) > 0) {
		$sql =	"SELECT tbl_posto.posto
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica";	
		if (strlen($xcodigo_posto) > 0)
			$sql .= " AND upper(tbl_posto_fabrica.codigo_posto) = '$xcodigo_posto' ";
		if (strlen($xnome_posto) > 0) 
			$sql .= " AND tbl_posto.nome ILIKE '%$xnome_posto%' ";
	
		$res = @pg_query($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = "'".pg_fetch_result($res,0,0)."'";
		}else{
			$posto = "null";
			$msg_erro .= " Favor informe o posto correto. ";
		}
	}

	$xcondicao = trim($_POST['condicao']);
	if (strlen($xcondicao) == 0) {
		$xcondicao = "null";
		$msg_erro .= " Favor informe a condição de pagamento. ";
	}

	##### VERIFICA SE A PEÇA FOI DIGITADA COM A QTDE #####
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca_referencia = trim($_POST["peca_referencia_" . $i]);
		$peca_qtde       = trim($_POST["peca_qtde_"       . $i]);

		if (strlen($peca_referencia) > 0 AND strlen($peca_qtde) == 0) {
			$msg_erro .= " Favor informe quantidade da Peça $peca_referencia. ";
			$linha_erro = $i;
		}
	}

	##### VERIFICA TIPO PEDIDO #####
	$xtipo_pedido = $_POST['tipo_pedido'];

	$xtabela = ($xtipo_pedido == 193) ? 434 : 435;


	if ($xcondicao == '1639') $xnatureza_operacao = "'SN-GART'";
	else                    $xnatureza_operacao = "'VN-COML'";

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		if (strlen($pedido) == 0) {
			########## I N S E R E   P E D I D O ##########
			$sql =	"INSERT INTO tbl_pedido (
						posto             ,
						fabrica           ,
						admin             ,
						condicao          ,
						tabela            ,
						tipo_pedido       ,
						bloco_os          ,
						natureza_operacao ,
						pedido_sedex      
					) VALUES (
						$posto              ,
						$login_fabrica      ,
						$login_admin        ,
						'$xcondicao'        ,
						$xtabela            ,
						$xtipo_pedido       ,
						'0'                 ,
						$xnatureza_operacao ,
						't'
					)";
		}else{
			########## A L T E R A   P E D I D O ##########
			$sql =	"UPDATE tbl_pedido SET
						posto             = $posto              ,
						fabrica           = $login_fabrica      ,
						condicao          = $xcondicao          ,
						tabela            = $xtabela            ,
						tipo_pedido       = $xtipo_pedido       ,
						bloco_os          = '0'                 ,
						exportado         = null                ,
						finalizado        = null                ,
						natureza_operacao = $xnatureza_operacao 
					WHERE tbl_pedido.pedido  = $pedido
					AND   tbl_pedido.fabrica = $login_fabrica";

			
		}
//		echo $sql;
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
//if($login_fabrica == 3 ) echo $sql;
		if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
			$res = @pg_query($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido = pg_fetch_result($res,0,0);
			$msg_erro = pg_errormessage($con);
		}

		

		if (strlen($msg_erro) == 0) {
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$peca_referencia = trim($_POST['peca_referencia_' . $i]);
				$peca_descricao  = trim($_POST['peca_descricao_' . $i]);
				$peca_qtde       = trim($_POST['peca_qtde_'       . $i]);
				$preco           = trim($_POST['preco_'           . $i]);
				$juro            = trim($_POST['juro_'            . $i]);

				if (strlen ($juro) == 0) $juro = "null";
				if (strlen ($preco) == 0) $preco = "null";
				$preco = str_replace (",",".",$preco);

				if (strlen($msg_erro) == 0) {
					if (strlen($peca_referencia) > 0 OR strlen($peca_descricao) > 0) {
						$xpeca_referencia = strtoupper($peca_referencia);
						$xpeca_referencia = str_replace("-","",$xpeca_referencia);
						$xpeca_referencia = str_replace(".","",$xpeca_referencia);
						$xpeca_referencia = str_replace("/","",$xpeca_referencia);
						$xpeca_referencia = str_replace(" ","",$xpeca_referencia);

						$xpeca_descricao  = strtoupper($peca_descricao);
						if( ($login_fabrica==3 AND strlen(trim($peca_referencia))>0) or $login_fabrica<>3 ) {

							$sql =	"SELECT tbl_peca.peca
									FROM    tbl_peca
									WHERE   tbl_peca.fabrica = $login_fabrica ";
							if (strlen($xpeca_referencia) > 0) $sql .= " AND tbl_peca.referencia_pesquisa = '$xpeca_referencia' ";
							//if (strlen($xpeca_descricao) > 0)  $sql .= " AND tbl_peca.descricao = '$xpeca_descricao' ";
							$res = @pg_query($con,$sql);
							
							if (pg_numrows($res) == 1) {
								$peca = pg_fetch_result($res,0,peca);
							}else{
								$msg_erro = " Peça $peca_referencia não cadastrada. ";
								$linha_erro = $i;
							}

						
							if(strlen($msg_erro) == 0) {//bloquear peças já cadastradas e-mail do suporte.
								$sql2 = "SELECT peca FROM tbl_pedido_item WHERE pedido = $pedido AND peca = $peca; ";
								$res2 = pg_query($con,$sql2);
								$verificador = @pg_fetch_result($res2,0,peca);

								if(strlen($verificador) > 0){
									$msg_erro = " Peça $peca_referencia em destaque em duplicidade, favor retirar!";
									$linha_erro = $i;
									$verificador = '';
								}
							}

							if (strlen($msg_erro) == 0) {
								$sql =	"INSERT INTO tbl_pedido_item (
											pedido ,
											peca   ,
											qtde   ,
											preco  ,
											acrescimo_financeiro
										) VALUES (
											$pedido    ,
											$peca      ,
											$peca_qtde ,
											$preco  ,
											$juro
										) RETURNING pedido_item";

								$res = @pg_query($con,$sql);
								$msg_erro = pg_errormessage($con);
//echo $sql;
								if (strlen($msg_erro) == 0) {
									$pedido_item = pg_fetch_result($res,0,0);
								}

								if (strlen($msg_erro) == 0) {
									$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
									$res = @pg_query($con,$sql);
									$msg_erro = pg_errormessage($con);
								}

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
	}

	if (strlen($msg_erro) == 0 ) {
		 $sql = "SELECT fn_finaliza_pedido_blacktest ($pedido,$login_fabrica)";
//		echo $sql;
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}


#------------ Le Pedido da Base de dados ------------#
if (strlen($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido_blackedecker                                   ,
					tbl_pedido.seu_pedido                                            ,
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
	$res = pg_query ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$pedido_blackedecker = "00000".trim(pg_fetch_result($res,0,pedido_blackedecker));
		$pedido_blackedecker = substr($pedido_blackedecker,strlen($pedido_blackedecker)-5,strlen($pedido_blackedecker));
		$seu_pedido          = trim(pg_fetch_result($res,0,seu_pedido));
		$condicao            = trim(pg_fetch_result($res,0,condicao));
		$codigo_posto        = trim(pg_fetch_result($res,0,codigo_posto));
		$nome_posto          = trim(pg_fetch_result($res,0,nome_posto));
		$exportado           = trim(pg_fetch_result($res,0,exportado));

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}
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
$body_onload = "javascript: document.frm_pedido.condicao.focus()";

include "cabecalho.php";

?>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
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

<!-- Início 
nextfield = "codigo_posto"; // coloque o nome do primeiro campo do form 
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE 
	k = (netscape) ? DnEvents.which : window.event.keyCode; 
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos 
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frm_pedido.' + nextfield + '.focus()'); 
			return false; 
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes 
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP); 
// Fim -->

function fnc_black_preco (linha_form) {
	var condicao    = window.document.frm_pedido.condicao.value ;
	var tipo_pedido = window.document.frm_pedido.tipo_pedido.value;
	var posto       = window.document.frm_pedido.codigo_posto.value;
	
	if ((condicao.length)==0){
		alert("Por favor escolha uma condição de pagamento");
		return false;
	}
	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = "";

	peca_referencia = 'peca_referencia_' + linha_form;
	peca_referencia = document.getElementById(peca_referencia).value;

	qtde       = 'qtde_' + linha_form;
	qtde       = document.getElementById(qtde).value;


	url = 'black_valida_regras.php?linha_form=' + linha_form + '&posto='+posto+'&referencia_peca=' + peca_referencia + '&condicao=' + condicao + '&tipo_pedido=' + tipo_pedido + '&qtde=' + qtde +'&cache_bypass=<?= $cache_bypass ?>';
	requisicaoHTTP ('GET', url , true , 'fnc_black_responde_preco');

}

function fnc_black_responde_preco (campos) {
	campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
	campos = campos.substring (0,campos.indexOf('</preco>'));
	campos_array = campos.split("|");

	preco      = campos_array[0] ;
	linha_form = campos_array[1] ;
	juro       = campos_array[6] ;
	campo_preco = 'preco_' + linha_form;
	campo_juro  = 'juro_' + linha_form;
	document.getElementById(campo_preco).value = preco;
	document.getElementById(campo_juro).value = juro;
}

</script>

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

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<? if (strlen ($msg_erro) > 0) { ?>
<br>
<table class="table" align='center' width="700" border="0" cellpadding="0" cellspacing="0" >
	<tr>
		<td valign="middle" align="center" class='error'>
			<?
			if (strpos($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0)
				$msg_erro = "Esta ordem de serviço já foi cadastrada";

			echo $msg_erro;
			?>
		</td>
	</tr>
</table>
<? } ?>

<? if (strlen($exportado) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#F4F4F4'>
		<p align='justify'><font size='1'><b>
		<font color='#FF0000'>O SEU PEDIDO <? echo $pedido_blackedecker ?> FOI EXPORTADO EM <? echo $exportado ?></font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE, AGUARDANDO A PRÓXIMA EXPORTAÇÃO.
		</b></font></p>
	</td>
</tr>
</table>
<? } ?>

<?
#if (strlen($pedido) > 0) {
?>
<!--<br>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#F4F4F4'>
		<p align='justify'><font size=1>
		<font color='#FF0000'><b>O SEU PEDIDO NÚMERO: <? echo $pedido_blackedecker ?> SERÁ EXPORTADO ÀS 13h55</font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE. SE O PEDIDO NÃO FOR FINALIZADO APÓS A INCLUSÃO DE NOVOS ITENS, SERÁ EXPORTADO PARA A BLACK & DECKER APENAS O PEDIDO FINALIZADO INICIALMENTE</b>.<br>
		</font></p>
	</td>
</tr>
</table>-->
<?# } ?>

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="pedido" value="<? echo $pedido ?>">
<input type="hidden" name="pedido_blackedecker" value="<? echo $pedido_blackedecker ?>">

<table class="table" width='750' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>Posto</b>
	</td>
	<td align='center'>
		<b>Razão Social</b>
	</td>
	<td align='center'>
		<b>Condição de Pagamento</b>
	</td>
	<td align='center'>
		<b>Tipo Pedido</b>
	</td>

</tr>

<tr class="table_line">
	<td align='center'>
		<input type="text" name="codigo_posto" id="codigo_posto" size="14" maxlength="14" value="<? echo $codigo_posto ?>" class="frm" onFocus="nextfield ='nome_posto'">&nbsp;<img src='../imagens/btn_lupa.gif' style="cursor: pointer;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_pedido.codigo_posto,document.frm_pedido.nome_posto,'codigo')">
	</td>
	<td align='center'>
		<input type="text" name="nome_posto" size="50" maxlength="60" value="<? echo $nome_posto ?>" class="frm" onFocus="nextfield ='condicao'">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor: pointer;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_pedido.codigo_posto,document.frm_pedido.nome_posto,'nome')">
	</td>
	<td align='center'>
		<?
		$sql =	"SELECT *
				FROM tbl_condicao
				WHERE fabrica = $login_fabrica
				ORDER BY lpad(trim(tbl_condicao.codigo_condicao),10,'0');";
		$res = pg_query($con,$sql);
		if (pg_numrows($res) > 0) {
			echo "<select name='condicao' size='1' class='frm' onFocus=\"nextfield ='peca_referencia_0'\">";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				echo "<option value='".pg_fetch_result($res,$i,condicao)."'";
				if ($condicao == pg_fetch_result($res,$i,condicao) ) echo " selected";
				echo ">".pg_fetch_result($res,$i,descricao)."</option>";
			}
			echo "</select>";
		}
		?>
	</td>

		<td align='center'>
		<?
			echo "<select size='1' name='tipo_pedido' class='frm'>";
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica 
						$cond_locadora";
				$sql .= " ORDER BY tipo_pedido DESC ";
				$res = pg_query ($con,$sql);
				echo "<option value =''></option>";
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
					if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
						echo " selected";
					}
					echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
				}
				echo "</select>";
		?>
	</td>

</tr>
</table>

<br>

<table border="0" cellspacing="5" cellpadding="0" align='center'>
	<tr height="20" class="menu_top">
		<td align='center'>Referência</td>
		<td align='center'>Qtde</td>
	</tr>
	<?
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		if (strlen($msg_erro) > 0) {
			$peca_referencia = $_POST["peca_referencia_" . $i];
			$peca_descricao  = $_POST["peca_descricao_"  . $i];
			$peca_qtde       = $_POST["peca_qtde_"       . $i];
			$preco           = trim($_POST['preco_'          . $i]);
			$juro            = trim($_POST['juro_'            . $i]);
		}

		echo "<input type='hidden' name='item_$i' value='$item'>\n";

		if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor = "#FFCCCC";

		echo "<tr bgcolor='$cor'>\n";

		echo "<td nowrap>";
		echo "<input type='text' name='peca_referencia_$i' size='15' value='$peca_referencia' id='peca_referencia_$i' class='frm' onFocus=\"nextfield ='peca_qtde_$i'\"> <img src='../imagens/btn_buscar5.gif' alt='Clique para pesquisar por referência da peça' border='0' hspace='5' align='absmiddle' onclick=\"fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_$i, window.document.frm_pedido.peca_descricao_$i, 'referencia')\" style='cursor: pointer;' >";
		echo "</td>\n";

		echo "<input type='hidden' name='peca_descricao_$i' value='$peca_descricao'>";
	
		$prox = $i + 1;
		$done = $qtde_item - 1;

		echo "<td nowrap>";
		echo "<input type='text' name='peca_qtde_$i' id='qtde_$i' size='5'  value='$peca_qtde' class='frm'";
		if ($prox <= $done) { echo " onFocus=\"nextfield ='peca_referencia_$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}
		echo "onblur='javascript: fnc_black_preco ($i);'"; 
		echo ">";

		echo "<input class='frm' id='preco_$i' type='text' name='preco_$i' size='10'  value='$preco'  >";
		echo "<input class='frm' id='juro_$i' type='hidden' name='juro_$i'   value='$juro' >";
		echo "</td>\n";

		echo "</tr>\n";
		$cor = "#FFFFFF";
	}
	?>
</table>

<br>

<input type='hidden' name='btn_acao' value=''>

<center>
<img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '') { document.frm_pedido.btn_acao.value='gravar'; document.frm_pedido.submit() }else{ alert('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
</center>

<br>

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
	<tr>
		<td align='center' bgcolor='#F4F4F4'>
			<p align='justify'><font size='1'><b>PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.</b></font></p>
		</td>
	</tr>
	<tr>
		<td align='center' bgcolor='#F4F4F4'>
			<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
		</td>
	</tr>
</table>

<br>

<? if (strlen($pedido) > 0) { ?>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan="4" align="center" class='menu_top'>
		<font face="arial" color="#ffffff" size="+2"><b>Resumo do Pedido</b></font>
	</td>
</tr>
<tr>
	<td width="25%" align='center' class="menu_top">
		<b>Referência</b>
	</td>
	<td width="50%" align='center' class="menu_top">
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Quantidade</b>
	</td>
	<td width="10%" align='center' class="menu_top">
		<b>Preço</b>
	</td>
</tr>
<?
	$sql = "SELECT	a.oid      ,
					a.*        ,
					referencia ,
					descricao  
			FROM	tbl_peca
			JOIN	(
						SELECT	oid, *
						FROM	tbl_pedido_item
						WHERE	pedido = $pedido
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = @pg_query($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < @pg_numrows($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";

		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_fetch_result ($res,$i,pedido_item) . "&pedido=$pedido'>";

		echo "<img src='imagens/btn_excluir.gif' align='absmiddle' hspace='5' border='0'>";
		echo "</a>";
		echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left' class='table_line1'>";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' class='table_line1'>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right' class='table_line1'>";
		echo number_format (pg_fetch_result ($res,$i,preco),2,",",".");
		echo "</td>";

		echo "</tr>";
		
		$total = $total + (pg_fetch_result ($res,$i,preco) * pg_fetch_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="3" class="menu_top">
		<b>T O T A L</b>
	</td>
	<td align='right' class="menu_top" style='text-align:right'>
		<b>
		<? echo number_format ($total,2,",",".") ?>
		</b>
	</td>
</tr>
<? if (strlen($exportado) == 0) { ?>
<tr>
	<td colspan="4" class='table_line1' align='left'>
		<a href="<? echo $PHP_SELF ?>?excluir=tudo&pedido=<?echo $pedido?>"><font color="#FF0000">Excluir Todos Itens</font></a>
	</td>
</tr>
<? } ?>
</table>

<br>

<center>
<!--<a href="<? echo $PHP_SELF ?>?pedido=<? echo $pedido ?>&finalizar=1&unificar=t">-->
<img src='imagens/btn_finalizar.gif' border='0' style="cursor: hand;" onclick="javascript: document.frm_pedido.btn_acao.value='finalizar'; document.frm_pedido.submit();">
<img src='imagens/btn_apagar.gif' border='0' style="cursor: hand;" onclick="javascript: document.frm_pedido.btn_acao.value='apagar'; document.frm_pedido.submit();">
<a href="<? echo $PHP_SELF ?>"><img src='imagens/btn_lancarnovopedido.gif' border='0'></a>
</center>
</form>
<br>

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
	<tr>
		<td align='center' bgcolor='#F4F4F4'>
		<p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR ACIMA.</b></font></p>
		</td>
	</tr>
</table>

<br>
<? } ?>


<? include "rodape.php"; ?>
