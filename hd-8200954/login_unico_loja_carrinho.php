<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_posto  = $cookie_login['cook_posto'];
if (strlen($cookie_login['cook_pedido_lu']) > 0) $cook_pedido_lu = $cookie_login['cook_pedido_lu'];

# -------------------------------------
// dados padronizados para abetura de pedido
$fabrica       = 10;
$tipo_pedido   = 77;
$status_pedido = 1;
# -------------------------------------

# CLASSE QUE LOCALIZA E RETORNA O CEP
class CalcFrete{
    var $servico,$ceporigem,$cepdestino,$peso;

    function calcular($ceporigem,$cepdestino,$peso,$servico = '40010'){

        $peso = $peso / 1000;

        $this->servico = $servico;
        $this->ceporigem = $ceporigem;
        $this->cepdestino = $cepdestino;
        $this->peso = $peso;
        if(!($this->peso == "0" || $this->peso > "30")){
            $correioFile = "http://www.correios.com.br/encomendas/precos/calculo.cfm?servico=" . $this->servico . "&CepOrigem=".$this->ceporigem."&CepDestino=".$this->cepdestino."&Peso=".$this->peso;
            $resultado = join("",file($correioFile));
            $procura = strpos($resultado,'Tarifa=')+strlen('Tarifa=');
            $resultado = trim(substr($resultado,$procura));
            $fim = strpos($resultado,"&erro=");
            return trim(substr($resultado,0,$fim));
        }else{
            return false;
        }
    }
}


$btn_acao    = $_GET['btn_acao'];
$acao        = $_GET['acao'];

if(strlen($_GET['btn_acao'])>0)  $btn_acao = $_GET["btn_acao"];
if(strlen($_POST['btn_acao'])>0) $btn_acao = $_POST["btn_acao"];

if(strlen($cookie_pedido_lu)==0){
	$sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = 10 AND finalizado IS NULL AND posto=$cook_posto";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if(pg_numrows($res)>0){
		$cook_pedido_lu = pg_result($res, 0, pedido);
		setcookie ("cook_pedido_lu",$cook_pedido_lu);
	}
}

if ($acao=="adicionar"){
	$peca        = $_POST['cod_produto'];
	$referencia  = $_POST['referencia'];
	$qtde        = $_POST['qtde'];
	$valor       = $_POST['valor'];
	$descricao   = $_POST['descricao'];
	$qtde_maxi   = $_POST['qtde_maxi'];
	$qtde_disp   = $_POST['qtde_disp'];


	if (strlen($peca) > 0 and $qtde > 0 ){
		$sql = "SELECT  tbl_peca.peca        ,
				tbl_peca.referencia  ,
				tbl_tabela_item.preco,
				tbl_peca.peso
			FROM    tbl_peca
			JOIN    tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
			WHERE   tbl_peca.fabrica = $fabrica
			AND     tbl_peca.peca    = $peca";

		$res = pg_exec ($con,$sql);
	
		$peca       = pg_result($res, 0, peca);
		$referencia = pg_result($res, 0, referencia);
		$preco      = pg_result($res, 0, preco);
		$peso       = pg_result($res, 0, peso);
		
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($cook_pedido_lu) == 0){

			$sql = "INSERT INTO tbl_pedido(fabrica, posto, tipo_pedido, data, status_pedido) VALUES ($fabrica, $cook_posto, $tipo_pedido, current_timestamp, $status_pedido)";
		
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		
			if (strlen ($msg_erro) == 0) {
				$res    = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
				$cook_pedido_lu  = pg_result ($res,0,0);
				setcookie ("cook_pedido_lu",$cook_pedido_lu);
			}
		}

		$sql = "SELECT *
			FROM   tbl_pedido_item
			WHERE  peca   = $peca
			AND    pedido = $cook_pedido_lu";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0) {
			$sql = "INSERT INTO tbl_pedido_item(
				pedido  ,
				peca    ,
				qtde    ,
				preco
				) VALUES (
				$cook_pedido_lu ,
				$peca        ,
				$qtde        ,
				'$preco'
				);";
		}else{
			$pedido_item = pg_result($res,0,0);
			$sql = "UPDATE tbl_pedido_item SET
					qtde        = qtde + $qtde
				WHERE  pedido_item = $pedido_item
				AND    pedido      = $cook_pedido_lu";
		}
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) $res = pg_exec ($con,"COMMIT TRANSACTION");
		else                         $res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen ($pedido_item) > 0 and $acao == 'remover') {
    $sql = "DELETE FROM tbl_pedido_item
            WHERE  tbl_pedido_item.pedido_item = $pedido_item
            AND    tbl_pedido_item.peca        = $peca
            AND    tbl_pedido.pedido           = $cook_pedido_lu";
    $res = @pg_exec ($con,$sql);
    $msg_erro = pg_errormessage ($con);
}

// chama CLASSE de calculo de frete
$ofrete = new CalcFrete();

// Valores para calcular o CEP
$cepOrigem = '17513230';                        // CEP DE ORIGEM DA EMPRESA

$cepDestino = $_POST['cep'];                    // CEP DESTINO

if ($btn_acao == 'consultacep'){
    $res  = pg_exec ($con,"SELECT sum(tbl_peca.peso * tbl_pedido_item.qtde) AS total_peso FROM tbl_pedido_item join tbl_peca using(peca) WHERE tbl_pedido_item.pedido = $cook_pedido_lu");
    $peso         = pg_result ($res,0,total_peso);

    $ofrete = new CalcFrete();
    $valor_cep = $ofrete->calcular($cepOrigem, $cepDestino, $peso);
    setcookie ("cep", $_POST['cep']);
    setcookie ("valor_cep", $valor_cep);
}

if (strlen($cookie_login['cep']) > 0) $cep = $cookie_login['cep'];

if($btn_acao == 'fechar_pedido'){
	$sql = "SELECT  sum(tbl_pedido_item.preco * tbl_pedido_item.qtde) AS total_compra,
			sum(tbl_peca.peso * tbl_pedido_item.qtde)         AS total_peso
		FROM   tbl_pedido_item 
		JOIN   tbl_peca         USING(peca)
		WHERE  tbl_pedido_item.pedido = $cook_pedido_lu";
	$res   = pg_exec ($con,$sql);
	
	$total_pecas = pg_result ($res,0,total_compra);
	$total = $valor_cep + $total_pecas;
	
	$sql = "UPDATE tbl_pedido SET total = $total ,finalizado = current_timestamp WHERE fabrica = $fabrica and pedido = ".$cook_pedido_lu;

	$res = pg_exec ($con,$sql);
	
	header("Location: login_unico_pedido_confirmacao.php");
	exit;
}

//########################################################################################################
$aba = 3;
$title = "BEM-VINDO a loja virtual da Telecontrol ";
include "login_unico_cabecalho.php";

?>
<style>
.Titulo{
	font-family: Arial;
	font-size: 14px;
	font-weight:bold;
	color: #333;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
}
</style>
<BODY TOPMARGIN=0>
<?

if(strlen($msg_erro)>0){
	$error = str_replace("ERROR:","",$msg_erro);
	echo "<h4 style='color:red'>".$error."</h4>";
}
if(strlen($msg)>0){
	echo "<h4 style='color:blue'>".$msg."</h4>";
}

if($cook_posto){
	$sql="SELECT * FROM tbl_posto WHERE posto = $cook_posto";
	$res = pg_exec ($con, $sql);
	if(pg_numrows($res)>0){
		$posto			= trim(pg_result ($res,0,posto));
		$nome			= trim(pg_result ($res,0,nome));
	}
}
echo "<form name='frm' method='post' action='$PHP_SELF'><input type='hidden' name='peso' value='$peso'><input type='hidden' name='cep' value='$cep'><input type='hidden' name='btn_acao' value=''>";
echo "<table width='700' border='1' align='center' cellpadding='3' cellspacing='0' style='border:#B5CDE8 1px solid;  bordercolor:#d2e4fc;border-collapse: collapse'>";

echo "<tr>";
echo "<td align='left'  colspan='7' bgcolor='#e6eef7' class='Titulo' ><br><B>Carrinho de Compras</B><br></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='7' align='right' class='Conteudo' style='font-size:14px;padding:5px'><a href='login_unico_loja.php'>Continuar Comprando</a> | <a href=\"javascript:if (confirm('Deseja limpar o seu carrinho de compras?')) window.location='$PHP_SELF?acao=limpa'\">Limpar Carrinho</a> | <a href=\"javascript:if (confirm('Deseja finalizar este pedido? Este pedido será enviado para a Fábrica.')) window.location = '$PHP_SELF?btn_acao=fechar_pedido'\" value='Fechar Pedido'>Fechar Pedido</a>";
echo "</td>";
echo "</tr>";

//cabeca
echo "<tr bgcolor='#e6eef7' class='Titulo'>";
	echo "<td width='20' height='30' align='center' >Remover?&nbsp;&nbsp;</td>";
	echo "<td  height='30' align='left'>Peça</td>";
	echo "<td  height='30' align='right'>Qtde</td>";
	echo "<td  height='30' align='right'>Valor Unit.</td>";
	echo "<td  height='30' align='right'>Valor Total</td>";
echo "</tr>";
//fim cabeca

$sql = "SELECT  
			tbl_pedido.pedido              ,
			tbl_pedido_item.pedido         ,
			tbl_pedido_item.pedido_item    ,
			tbl_peca.peca                  ,
			tbl_peca.referencia            ,
			tbl_peca.descricao             ,
			tbl_peca.ipi                   ,
			tbl_peca.promocao_site         ,
			tbl_peca.qtde_disponivel_site  ,
			tbl_pedido_item.qtde           ,
			tbl_pedido_item.preco          ,
			tbl_linha.nome as linha_desc
	FROM  tbl_pedido
	JOIN  tbl_pedido_item USING (pedido)
	JOIN  tbl_peca        USING (peca)
	LEFT JOIN tbl_linha USING(linha)
	WHERE tbl_pedido.posto   = $cook_posto
	AND   tbl_pedido.pedido  = $cook_pedido_lu
	AND   tbl_pedido.fabrica = 10 
	AND   tbl_pedido.exportado IS NULL
	ORDER BY tbl_pedido.pedido DESC";
$res = @pg_exec ($con,$sql);

$pedido_ant = "";

//echo nl2br($sql);

if (@pg_numrows($res) > 0) {
	for($i=0; $i< pg_numrows($res); $i++) {
		$pedido          = trim(pg_result($res,$i,pedido));
		$pedido_item     = trim(pg_result($res,$i,pedido_item));
		$peca            = trim(pg_result($res,$i,peca));
		$referencia      = trim(pg_result($res,$i,referencia));
		$peca_descricao  = trim(pg_result($res,$i,descricao));
		$qtde            = trim(pg_result($res,$i,qtde));
		$preco           = trim(pg_result($res,$i,preco));
		$ipi             = trim(pg_result($res,$i,ipi));
		$promocao_site   = trim(pg_result($res,$i,promocao_site));
		$qtde_disponivel = trim(pg_result($res,$i,qtde_disponivel_site));

		$preco_2         = str_replace(",",".",$preco);

		$valor_total = $preco * $qtde;


		$soma       += $valor_total;
		$preco       = number_format($preco, 2, ',', '');
		$preco       = str_replace(".",",",$preco);
		$valor_total = number_format($valor_total, 2, ',', '');

		//EXIBE OS PRODUTOS DA CESTA
		$a++;
		$cor = "#FFFFFF"; 
		if ($a % 2 == 0){
			$cor = '#EEEEE3';
		}

		echo "<tr class='Conteudo' height='25'>";
		echo "<td  bgcolor='$cor'  align='center'><a href=\"javascript: if(confirm('Deseja excluir o item $referencia - $peca_descricao?')){window.location='$PHP_SELF?acao=remover&peca=$peca&pedido=$pedido&pedido_item=$pedido_item'}\"> <IMG SRC='imagens/excluir_loja.gif' alt='Remover Produto'border='0'></a></td>";
		echo "<td  bgcolor='$cor' align='left'>$referencia - $peca_descricao</td>";
		echo "<td  bgcolor='$cor' align='right'>$qtde</td>";
		echo "<td  bgcolor='$cor' align='right'>R$ $preco</td>";
		echo "<td  bgcolor='$cor' align='right'>R$ $valor_total</td>";
		echo "</tr>";
	}
		// CEP
	echo "<tr align='center'>\n";
	echo "<td colspan='4' bgcolor='#FFFFDD'><font face='Tahoma' color='#000000'><small>&nbsp; Digite o CEP do endereço de entrega: </small></font><INPUT TYPE='text' NAME='cep' size='10' maxlength='10' value = '$cep'><INPUT TYPE='button' value=' OK ' onclick=\"javascript: document.forms[0].btn_acao.value='consultacep'; document.forms[0].submit() ;\"></td>\n";
	$Xvalor_cep = number_format($valor_cep,2,',','.');
	echo "<td align='right' bgcolor='#FFFFDD'>R$ ".$Xvalor_cep."</td>\n";
	echo "</tr>\n";

	// TOTALIZACAO
	$soma += $valor_cep;
	$soma = number_format($soma, 2, ',', '');
	$soma = str_replace(".",",",$soma);

}else{
	echo "<tr class='Conteudo'>";
	echo "<td  bgcolor='#FFFFFF' colspan='5' align='center'><br>Carrinho vazio<br><br></td>";
	echo "</tr>";
}
//TOTAL
echo "<tr>";
echo "<td  colspan='4' height='30' align='right' bgcolor='#D5D5CB'><font size='2'><B>Total da Compra:</B></font>";
echo "</td>";
echo "<td align='right' bgcolor='#D5D5CB'><font color='#008800'><b>R$ $soma</B></font>";
echo "</td>";
echo "</tr>";
//TOTAL

echo "<tr>";
echo "<td align='center' bgcolor='#e6eef7' colspan='5' class='Titulo' style='padding:5px;'><a href='javascript:history.back()'>Voltar</a></td>";
echo "</tr>";

echo "</table>";

include 'login_unico_rodape.php'
?>
