<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	header("Location: pedido_blackedecker_cadastro.php");
	exit;
}
if($login_fabrica==24 AND 1==2){
	include "cabecalho.php";
	echo "<h4>FAÇA SEUS PEDIDOS DE FORMA CONVENCIONAL<br><br> EM BREVE ESTAREMOS DISPONIBILIZANDO OS PEDIDOS PELO SISTEMA ASSIST</h4>";
	exit;
}
if($login_fabrica == 5){
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDO TEMPORARIAMENTE SUSPENSO.</H4>";
	include "rodape.php";
	exit;
}

if($login_fabrica == 14){
	$layout_menu = 'pedido';
	$title       = "Cadastro de Pedidos de Peças";
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDO INDISPONÍVEL.</H4>";
	include "rodape.php";
	exit;
}


$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_result ($res,0,0) == 'f') {
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}

// BLOQUEIO DE PEDIDO FATURADO PARA O GM TOSCAN
if($login_fabrica == 3 and $login_posto == 970){
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}


if($login_fabrica == 3 and $login_bloqueio_pedido == 't') {
	$sql = "SELECT tbl_posto_linha.posto
			FROM   tbl_posto_linha
			WHERE  tbl_posto_linha.posto        = $login_posto
			AND    tbl_posto_linha.linha NOT IN (2,4);";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) == 0) {
		$layout_menu = 'pedido';
		$title       = "Cadastro de Pedidos de Peças";
		include "cabecalho.php";
		include "rodape.php";
		exit;
	}
}


#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	$distribuidor_digita = pg_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}




$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";
$qtde_item = 40;

if ($btn_acao == "gravar"){
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];

	if (strlen($condicao) == 0) {
		$aux_condicao = "null";
	}else{
		$aux_condicao = $condicao ;
	}

	if (strlen($pedido_cliente) == 0) {
		$aux_pedido_cliente = "null";
	}else{
		$aux_pedido_cliente = "'". $pedido_cliente ."'";
	}

	if (strlen($transportadora) == 0) {
		$aux_transportadora = "null";
	}else{
		$aux_transportadora = $transportadora ;
	}

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	}else{
		$sql = "SELECT	tipo_pedido
				FROM	tbl_tipo_pedido
				WHERE	descricao IN ('Faturado','Venda')
				AND		fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$aux_tipo_pedido = "'". pg_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}


	
	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_exec($con,$sql);
			if (pg_numrows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------


	
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen ($pedido) == 0) {

		#-------------- insere pedido ------------
		$sql = "INSERT INTO tbl_pedido (
					posto          ,
					fabrica        ,
					condicao       ,
					pedido_cliente ,
					transportadora ,
					linha          ,
					tipo_pedido    ,
					digitacao_distribuidor
				) VALUES (
					$posto              ,
					$login_fabrica      ,
					$aux_condicao       ,
					$aux_pedido_cliente ,
					$aux_transportadora ,
					$aux_linha          ,
					$aux_tipo_pedido    ,
					$digitacao_distribuidor
				)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro) == 0){
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido  = @pg_result ($res,0,0);
		}
	}else{
		$sql = "UPDATE tbl_pedido SET
					condicao       = $aux_condicao       ,
					pedido_cliente = $aux_pedido_cliente ,
					transportadora = $aux_transportadora ,
					linha          = $aux_linha          ,
					tipo_pedido    = $aux_tipo_pedido
				WHERE pedido  = $pedido
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
//$msg_debug .= " $i ) <b>CURRVAL Pedido </b>".$sql." - [ $pedido ]<br><br>";

		$nacional  = 0;
		$importado = 0;

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$pedido_item     = trim($_POST['pedido_item_'     . $i]);
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			$preco           = trim($_POST['preco_'           . $i]);
			
			if (strlen ($peca_referencia) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}
			//verifica se a peça tem o valor da peca caso nao tenha exibe a msg 
			//só verifica os precos dos campos que tenha a referencia da peça.
			if ($login_fabrica == '15' AND strlen($peca_referencia) > 0 )
			{
				if($tipo_pedido <> '90')
				{
					if(strlen($preco) == 0)
					{
						$msg_erro = 'Existem peças sem preço.<br>';
						$linha_erro = $i;
						break;
					}
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0) {
				// delete
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $pedido_item
						AND		pedido = $pedido";
				$res = pg_exec ($con,$sql);
			}

			if (strlen ($peca_referencia) > 0) {
				$peca_referencia = trim (strtoupper ($peca_referencia));
				$peca_referencia = str_replace ("-","",$peca_referencia);
				$peca_referencia = str_replace (".","",$peca_referencia);
				$peca_referencia = str_replace ("/","",$peca_referencia);
				$peca_referencia = str_replace (" ","",$peca_referencia);

				$sql = "SELECT  tbl_peca.peca   ,
								tbl_peca.origem
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_exec ($con,$sql);

				$peca   = pg_result ($res,0,peca);

				if (pg_numrows ($res) == 0) {
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca   = pg_result ($res,0,peca);
					$origem = trim(pg_result ($res,0,origem));
				}

				if ($origem == "NAC" or $origem == "1") {
					$nacional = $nacional + 1;
				}

				if ($origem == "IMP" or $origem == "2") {
					$importado = $importado + 1;
				}

				if ($nacional > 0 and $importado > 0 and $login_fabrica <> 3 and $login_fabrica <> 5 and $login_fabrica <> 8) {
					$msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
					$linha_erro = $i;
					break;
				}
				
				/*
				if ($login_fabrica == 3 && strlen($peca_referencia) > 0) {
					$sqlX =	"SELECT referencia
							FROM tbl_peca
							WHERE UPPER(referencia_pesquisa) = UPPER('$peca_referencia')
							AND   fabrica = $login_fabrica
							AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
					$resX = pg_exec($con,$sqlX);
					if (pg_numrows($resX) > 0) {
						$peca_previsao = pg_result($resX,0,0);
						$msg_erro = "Não há previsão de chegada da Peça $peca_previsao. Favor encaminhar e-mail para <a href='mailto:leila.beatriz@britania.com.br'>leila.beatriz@britania.com.br</a>, informando o número da ordem de serviço. Somente serão aceitas requisições via email! Não utilizar o 0800.";
					}
				}
				*/
				
				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
									pedido ,
									peca   ,
									qtde
								) VALUES (
									$pedido ,
									$peca   ,
									$qtde
								)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde
								WHERE pedido_item = $pedido_item";
					}
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}//faz a somatoria dos valores das peças para verificar o total das pecas pedidas
				//Apenas para Latina.
				if($login_fabrica == 15)
				{
					if( strlen($preco) > 0 AND strlen($qtde) > 0){
						$total_valor = (($total_valor) + ( str_replace( "," , "." ,$preco) * $qtde));
					}
				}
			}
		}
		//modificado para a Latina pois o valor nao pode ser menor do que 80,00 reias.
		if($login_fabrica == 15){
			if($tipo_pedido <> '90')
				{
				if($total_valor < 80){
					$msg_erro .= 'O valor mínimo não foi atingido ';
				}else{
					//condicoes de pagamento depedendo do valor não se pode escolher a forma de pagamento
					if($condicao == 75 AND $total_valor < 200){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
					if($condicao == 98 AND $total_valor < 350){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
					if($condicao == 99 AND $total_valor < 600){
						$msg_erro = 'O total do pedido não atinge o valor mínimo da condição de pagamento';
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT	TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.exportado
			FROM	tbl_pedido
			LEFT JOIN tbl_transportadora USING (transportadora)
			left JOIN	tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto        USING (produto)
			WHERE	tbl_pedido.pedido   = $pedido
			AND		tbl_pedido.posto    = $login_posto
			AND		tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$data                  = trim(pg_result ($res,0,data));
		$transportadora        = trim(pg_result ($res,0,transportadora));
		$transportadora_cnpj   = trim(pg_result ($res,0,transportadora_cnpj));
		$transportadora_codigo = trim(pg_result ($res,0,transportadora_codigo));
		$transportadora_nome   = trim(pg_result ($res,0,transportadora_nome));
		$pedido_cliente        = trim(pg_result ($res,0,pedido_cliente));
		$tipo_pedido           = trim(pg_result ($res,0,tipo_pedido));
		$produto               = trim(pg_result ($res,0,produto));
		$produto_referencia    = trim(pg_result ($res,0,produto_referencia));
		$produto_descricao     = trim(pg_result ($res,0,produto_descricao));
		$linha                 = trim(pg_result ($res,0,linha));
		$condicao              = trim(pg_result ($res,0,condicao));
		$exportado             = trim(pg_result ($res,0,exportado));
	}
}


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
}

$title       = "Cadastro de Pedidos de Peças";
$layout_menu = 'pedido';

include "cabecalho.php";

if($login_fabrica == 3 and $login_bloqueio_pedido == 't'){
	echo "<p>";
	echo "<table border=1 align='center'><tr><td align='center'>";
	echo "<font face='verdana' size='2' color='FF0000'><b>Existem títulos pendentes de seu posto autorizado junto ao Distribuidor.
	<br>
	Não será possível efetuar novo pedido faturado das linhas de eletro e branca.
	<br><br>
	Para regularizar a situação solicitamos um contato urgente com a TELECONTROL:
	<br>
	(14) 3413-6588 / (14) 3413-6589 / distribuidor@telecontrol.com.br
	<br>
	Entrar em contato com o departamento de cobranças ou <br>
	efetue o depósito em conta corrente no <br><BR>
	Banco Bradesco<BR>
	Agência 2155-5<br>
	C/C 17427-0<br><br>
	e encaminhe um fax (14 3413-6588) com o comprovante.</b>
	<br><br>
	<b>Para visualizar os títulos <a href='posicao_financeira_telecontrol.php'>clique aqui</a></b>
	</font>";
	echo "</td></tr></table>";
	echo "<p>";
}

?>

<SCRIPT LANGUAGE="JavaScript">
function exibeTipo(){
	f = document.frm_pedido;
	if(f.linha.value == 3){
		f.tipo_pedido.disabled = false;
	}else{
		f.tipo_pedido.selectedIndex = 0;
		f.tipo_pedido.disabled = true;
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo;
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}
</SCRIPT>

<? include "javascript_pesquisas.php" ?>

<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<table width="730" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td valign="middle" align="center" class='error'>
<? 
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	echo $erro . $msg_erro;
?>
	</td>
</tr>
</table>
<p>
<?
//	echo $msg_debug;
}
?>

<?

$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10,0) ";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$frase = "PREENCHA SEU PEDIDO DE COMPRA/GARANTIA";
}else{
	$frase = "PREENCHA SEU PEDIDO DE COMPRA";
}
?>

<br>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td nowrap align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b><? echo $frase; ?></b>.</font>
	</td>
</tr>
</table>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<? 	if ($login_fabrica == 3) { ?>
	<td align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>Atenção Linha Áudio e Vídeo:</b> Pedidos de peças para linha de áudio e vídeo feitos nesta tela devem ser para <br> uso em consertos fora da garantia, e gerarão fatura e duplicata.<br>Pedidos para conserto em garantia serão gerados automaticamente pela Ordem de Serviço.<br>Leia o Manual e a Circular na primeira página.</font>
		<br><br>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
		<br><br>
	</td>
	<? 	} elseif ($login_fabrica == 15) { ?>
	<td align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>Condições de Pagamento:</b> <br> Até R$ 200,00 30 dias ; Até R$ 350,00 30-45 dias <br> Até R$ 600,00 , 30-60 dias ; Acima de R$ 600,00 , 30-60-90 dias</font>
		<br><br>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='#990000'><b>*** Pedidos abaixo de R$80,00 não serão faturados ***</b>
		<br><br>
		<b>Despesas de frete de peças faturadas serão por conta do Posto Autorizado.</b>
		<br>
			Sudeste/Sul: R$ 28,36<br>
			Centroeste: R$ 30,00<br>
			Norte/Nordeste: R$ 33.80<br>
		<br>
		<b>Despesas de frete de peças em garantia serão por conta da LATINATEC.</b></font>
		<br><br>
	</td>
	<? }else{ ?>
	<td nowrap align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Atenção:</b> Pedidos a prazo dependerão de análise do departamento de crédito.</font>
	</td>
	<? } ?>
</tr>
<? if($login_fabrica<>24){ ?>
<tr>
	<td nowrap align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.</font>
	</td>
</tr>
<? }else{ ?>
<tr>
	<td nowrap align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">O fabricante limita em 2 (dois) pedidos de garantia por mês.</font>
	</td>
</tr>
<? } ?>
<?
//alterado por Wellington 13-10-2006 chamado 575
if ($login_fabrica == "11") {
	echo "<tr>";
	echo "<td nowrap align='center'><BR>";
		echo "<font color='#FF0000' size='2' face='Geneva, Arial, Helvetica, san-serif'>Nesta tela devem ser digitados somente pedidos de <B>VENDA</B>.<BR>Pedidos de peça na <B>GARANTIA</B> devem ser feitos somente através da abertura da Ordem de Serviço.</font>";
	echo "</td>";
	echo "</tr>";
}
?>


</table>

<hr>

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido; ?>">
<input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">

<center>
<p>
<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr valign='top' style='font-size:12px'>
		<td nowrap align='center'>
		Distribuidor pode digitar pedidos para seus postos.
		<br>
		Digite o código do posto
		<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
		ou deixe em branco para seus próprios pedidos.
		</td>
	</tr>
	</table>
<? } ?>
</center>

<br>



<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#FFFFFF">
<!-- ------------- Formulário ----------------- -->

<tr>
	<td align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido do Cliente</b></font>
		<br>
		<input class="frm" type="text" name="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
	</td>

	<td valign="top" align="center" nowrap>
		<?
		$res = pg_exec ("SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

		if (pg_result ($res,0,0) == 'f') {
			echo "<input type='hidden' name='condicao' value=''>";
		}else{
			echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Condição Pagamento</b></font>";
			echo "<br>";

			echo "<select size='1' name='condicao' class='frm'>";
			$sql = "SELECT   tbl_condicao.*
					FROM     tbl_condicao
					JOIN     tbl_posto_condicao USING (condicao)
					WHERE    tbl_posto_condicao.posto = $login_posto
					AND      tbl_condicao.fabrica     = $login_fabrica
					AND      tbl_condicao.visivel       IS TRUE
					AND      tbl_posto_condicao.visivel IS TRUE
					ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10,0) ";
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 0 or $login_fabrica==2) {
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						WHERE    tbl_condicao.fabrica = $login_fabrica
						AND      tbl_condicao.visivel IS TRUE
						ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10,0) ";
				$res = pg_exec ($con,$sql);
			}

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option value='" . pg_result ($res,$i,condicao) . "'";
				if (pg_result ($res,$i,condicao) == $condicao) echo " selected";
				echo ">" . pg_result ($res,$i,descricao) . "</option>";
			}

			echo "</select>";
		}
		?>

	</td>
<?
//if($ip=="201.27.215.103"){echo $sql;}
?>

	<td valign="top" align="center" nowrap>
		<?
		// se posto pode escolher tipo_pedido
		echo "<td nowrap align='center'>";
		echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Tipo de Pedido</b></font>";
		echo "<br>";

		$sql = "SELECT   *
				FROM     tbl_posto_fabrica
				WHERE    tbl_posto_fabrica.posto   = $login_posto
				AND      tbl_posto_fabrica.fabrica = $login_fabrica";
if($login_fabrica<>24) $sql .= " AND      tbl_posto_fabrica.pedido_em_garantia IS TRUE;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select size='1' name='tipo_pedido' class='frm'>";
			$sql = "SELECT   *
					FROM     tbl_tipo_pedido
					WHERE    fabrica = $login_fabrica
					ORDER BY tipo_pedido ";
			$res = pg_exec ($con,$sql);

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
				if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
				echo ">" . pg_result($res,$i,descricao) . "</option>";
			}

			echo "</select>";
		}else{
			echo "<select size='1' name='tipo_pedido' ";
			if ($login_fabrica == 3) echo "disabled";
			echo ">";
			$sql = "SELECT   *
					FROM     tbl_tipo_pedido
					WHERE    (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
					       OR tbl_tipo_pedido.descricao ILIKE '%Venda%')
					AND      tbl_tipo_pedido.fabrica = $login_fabrica
					ORDER BY tipo_pedido;";
			$res = pg_exec ($con,$sql);

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
				if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
				echo ">" . pg_result($res,$i,descricao) . "</option>";
			}

			echo "</select>";
		}
		?>

	</td>
</tr>
</table>




<table width="600" border="0" cellspacing="1" cellpadding="5" align='center'>
<tr height="20" bgcolor="#ffffff">
		<?

		#-------------------- Transportadora -------------------

		$sql = "SELECT	tbl_transportadora.transportadora        ,
						tbl_transportadora.cnpj                  ,
						tbl_transportadora.nome                  ,
						tbl_transportadora_fabrica.codigo_interno
				FROM	tbl_transportadora
				JOIN	tbl_transportadora_fabrica USING(transportadora)
				JOIN	tbl_fabrica USING(fabrica)
				WHERE	tbl_transportadora_fabrica.fabrica        = $login_fabrica
				AND		tbl_transportadora_fabrica.ativo          = 't'
				AND		tbl_fabrica.pedido_escolhe_transportadora = 't'";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
		?>
			<td align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Transportadora</font>
				<br>
				<?
				if (pg_numrows ($res) <= 20) {

					echo "<select name='transportadora'>";
					echo "<option selected></option>";
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='".pg_result($res,$i,transportadora)."' ";
						if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
						echo ">";
						echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
						echo "</option>\n";
					}
					echo "		</select>";
				}else{

					echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
					echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

#					echo "<input type='text' name='transportadora_cnpj' size='20' maxlength='18' value='$transportadora_cnpj' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_cnpj,'cnpj')\" style='cursor:pointer;'>";

					echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

					echo "&nbsp;&nbsp;&nbsp;";

//					echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' onblur='javascript: lupa_transportadora_nome.click()'>&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
					echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";

				}
				?>
			</td>
		<?
		}
		?>


		<?

		#-------------------- Linha do pedido -------------------

		$sql = "SELECT	tbl_linha.linha            ,
						tbl_linha.nome
				FROM	tbl_linha
				JOIN	tbl_fabrica USING(fabrica)
				JOIN	tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
										AND tbl_posto_linha.linha = tbl_linha.linha
				WHERE	tbl_fabrica.linha_pedido is true
				AND     tbl_linha.fabrica = $login_fabrica ";
		
		// BLOQUEIO DE PEDIDOS PARA A LINHA ELETRO E BRANCA EM
		// CASO DE INADIMPLÊNCIA
		if ($login_fabrica == 3 and $login_bloqueio_pedido == 't') {
			$sql .= "AND tbl_linha.linha NOT IN (2,4)";
		}
		
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
		?>
			<td align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Linha</font>
				<br>
				<?
				echo "<select name='linha' class='frm' ";
				if ($login_fabrica == 3) echo " onChange='exibeTipo()'";
				echo ">";
				echo "<option selected></option>";
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					echo "<option value='".pg_result($res,$i,linha)."' ";
					if ($linha == pg_result($res,$i,linha) ) echo " selected";
					echo ">";
					echo pg_result($res,$i,nome);
					echo "</option>\n";
				}
				echo "</select>";
				?>
			</td>
		<?
		}
		?>
</tr>
</table>
<? if($login_fabrica<>24){ ?>
<table width="400" border="0" cellspacing="5" cellpadding="0" align='center'>
<tr height="20" bgcolor="#bbbbbb">
	<td align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">Referência Produto</font>
	</td>
	<td align='center'>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">Descrição Produto</font>
	</td>
</tr>

<tr height="20">
	<td align='center'>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia,document.frm_pedido.produto_descricao,'referencia',document.frm_pedido.produto_voltagem)">
	</td>
	<td align='center'>
		<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' alt="Clique para pesquisar pela descrição do produto" onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia,document.frm_pedido.produto_descricao,'descricao',document.frm_pedido.produto_voltagem)">
		<input type="hidden" name="produto_voltagem">
	</td>
</tr>
</table>
<? }else{ ?>
<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
<? } ?>
<p>

<table border="0" cellspacing="5" cellpadding="0" align="center">
<tr height="20" bgcolor="#bbbbbb">
	<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif">Referência Componente</font></td>
	<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif">Descrição Componente</font></td>
	<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif">Qtde</font></td>
<? if ($login_fabrica != 14 AND $login_fabrica!=24) { ?>
	<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif">Preço</font></td>
	<? } ?>
</tr>

<?
for ($i = 0 ; $i < $qtde_item ; $i++) {

	if (strlen($pedido) > 0 AND strlen ($msg_erro) == 0){
		$sql = "SELECT  tbl_pedido_item.pedido_item,
						tbl_peca.referencia        ,
						tbl_peca.descricao         ,
						tbl_pedido_item.qtde       ,
						tbl_pedido_item.preco
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $pedido
				AND   tbl_pedido.posto   = $login_posto
				AND   tbl_pedido.fabrica = $login_fabrica
				ORDER BY tbl_pedido_item.pedido_item";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$pedido_item     = trim(@pg_result($res,$i,pedido_item));
			$peca_referencia = trim(@pg_result($res,$i,referencia));
			$peca_descricao  = trim(@pg_result($res,$i,descricao));
			$qtde            = trim(@pg_result($res,$i,qtde));
			$preco           = trim(@pg_result($res,$i,preco));
			if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');
		}else{
			$pedido_item     = $_POST["pedido_item_"     . $i];
			$peca_referencia = $_POST["peca_referencia_" . $i];
			$peca_descricao  = $_POST["peca_descricao_"  . $i];
			$qtde            = $_POST["qtde_"            . $i];
			$preco           = $_POST["preco_"           . $i];
		}
	}else{
		$pedido_item     = $_POST["pedido_item_"     . $i];
		$peca_referencia = $_POST["peca_referencia_" . $i];
		$peca_descricao  = $_POST["peca_descricao_"  . $i];
		$qtde            = $_POST["qtde_"            . $i];
		$preco           = $_POST["preco_"           . $i];
	}

	$peca_referencia = trim ($peca_referencia);

	#--------------- Valida Peças em DE-PARA -----------------#
	$tem_obs = false;
	$linha_obs = "";

	$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
	$resX = pg_exec ($con,$sql);
	if (pg_numrows ($resX) > 0) {
		$linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
		$peca_referencia = pg_result ($resX,0,0);
		$tem_obs = true;
	}

	#--------------- Valida Peças Fora de Linha -----------------#
	$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
	$resX = pg_exec ($con,$sql);
	if (pg_numrows ($resX) > 0) {
		$linha_obs .= "Peça acima está fora de linha <br>&nbsp;";
		$tem_obs = true;
	}




	if (strlen ($peca_referencia) > 0) {
		$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) > 0) {
			$peca_descricao = pg_result ($resX,0,0);
		}
	}

	$peca_descricao = trim ($peca_descricao);



	$cor="";
	if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
	if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
	if ($tem_obs) $cor='#FFCC33';
?>
	<tr bgcolor="<? echo $cor ?>">
		<td align='left'>
			<input type="hidden" name="pedido_item_<? echo $i ?>" size="15" value="<? echo $pedido_item; ?>">
			<input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" value="<? echo $peca_referencia; ?>"><img src='imagens/btn_buscar5.gif' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' <? if ($login_fabrica == 14) { ?> onclick="javascript: fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia.value , document.frm_pedido.peca_referencia_<?echo $i?> , document.frm_pedido.peca_descricao_<?echo $i?> , document.frm_pedido.posicao, 'referencia')" <? }else{ ?> onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<? echo $i ?>,window.document.frm_pedido.preco_<? echo $i ?>,window.document.frm_pedido.voltagem,'referencia')" <? } ?>>
		</td>
		<td align='left'>
			<input type="hidden" name="posicao">
			<input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="30" value="<? echo $peca_descricao ?>"><img src='imagens/btn_buscar5.gif' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' <? if ($login_fabrica == 14) { ?> onclick="javascript: fnc_pesquisa_peca_lista_intel (document.frm_pedido.produto_referencia.value , document.frm_pedido.peca_referencia_<?echo $i?> , document.frm_pedido.peca_descricao_<?echo $i?> , document.frm_pedido.posicao, 'descricao')" <? }else{ ?> onclick="javascript: fnc_pesquisa_peca_lista (window.document.frm_pedido.produto_referencia.value, window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<? echo $i ?>,window.document.frm_pedido.preco_<? echo $i ?>,window.document.frm_pedido.voltagem,'descricao')" <? } ?>>
		</td>
		<td align='center'><input class="frm" type="text" name="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>"></td>
		<? if ($login_fabrica != 14 AND $login_fabrica!=24) { ?>
		<td align='center'><input class="frm" type="text" name="preco_<? echo $i ?>" size="10"  value="<? echo $preco ?>" readonly style='text-align:right'></td>
		<? } ?>
		<? if ($login_fabrica==24){ ?>
		<input type="hidden" name="preco_<? echo $i ?>" value="<? echo $preco ?>">

		 <? } ?>
	</tr>

	<?
	if ($tem_obs) {
		echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
		echo "<td colspan='4'>$linha_obs</td>";
		echo "</tr>";
	}
	?>

<?
}
?>

</table>

<center>

<input type="hidden" name="btn_acao" value="">
<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>

</form>

<p>

<? include "rodape.php"; ?>
