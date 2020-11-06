<?
session_name("carrinho");
session_start();

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual da Britânia ";
include "cabecalho.php";

$cod_produto = $_POST['cod_produto'];
$referencia  = $_POST['referencia'];
$qtde        = $_POST['qtde'];
$valor       = $_POST['valor'];
$ipi         = $_POST['ipi'];
$descricao   = $_POST['descricao'];
$qtde_maxi   = $_POST['qtde_maxi'];
$qtde_disp   = $_POST['qtde_disp'];

$btn_acao    = $_GET['btn_acao'];
$acao        = $_GET['acao'];

if(strlen($acao)>0){
	if ($acao=="adicionar"){
		#Adiciona o item ao Pedido
		if(strlen($_POST['cod_produto'])>0){

			if ($qtde > $qtde_maxi){
				$msg_erro .= "Quantidade máxima permitida é de $qtde_maxi";
			}
			if ($qtde >= $qtde_disp){
				$msg_erro .= "Quantidade disponível é de $qtde_disp!";
			}

			if (strlen($msg_erro)==0){
				$indice = $_SESSION[cesta][numero];
				for($i=1; $i<=($indice); $i++) {
					if ( strlen($_SESSION[cesta][$i][produto]) > 0 ){
						$produto = $_SESSION[cesta][$i][produto];
						if ($produto==$cod_produto){

							$xqtde  = $_SESSION[cesta][$i][qtde];
							$xqtde  = $xqtde+$qtde;


							if ($xqtde >= $qtde_disp){
								$msg_erro .= "<br>Quantidade disponível para esta peça é de $qtde_disp .";
							}elseif ($xqtde > $qtde_maxi){
								$msg_erro .= "Quantidade máxima permitida para esta peça é de $qtde_maxi .";
							}

							if (strlen($msg_erro)==0){
								$_SESSION[cesta][$i][qtde]=$xqtde;
								$_SESSION[cesta][$i][ipi]=$ipi;
							}
							$cad=1;
						}
					}
				}
				if($cad<>1){
					$_SESSION[cesta][numero]++;
					$indice = $_SESSION[cesta][numero];

					$_SESSION[cesta][$indice][pedido]      = "";
					$_SESSION[cesta][$indice][pedido_item] = "";
					$_SESSION[cesta][$indice][produto]     = $cod_produto;
					$_SESSION[cesta][$indice][referencia]  = $referencia;
					$_SESSION[cesta][$indice][qtde]        = $qtde;
					$_SESSION[cesta][$indice][valor]       = $valor;
					$_SESSION[cesta][$indice][ipi]         = $ipi;
					$_SESSION[cesta][$indice][descricao]   = $descricao;
					
				}
			}
		}
	}

	if($acao=='limpa'){
		//echo"entrou";
		session_unset();
		session_destroy();
	}

	if($acao=='remover'){
		$id =$_GET['id'];
		if (strlen($_SESSION[cesta][$id][pedido_item])>0){
			if (strlen($_SESSION[cesta][removerItem])>0){
				$_SESSION[cesta][removerItem] = "|".$_SESSION[cesta][$id][pedido_item];
			}else{
				$_SESSION[cesta][removerItem] = $_SESSION[cesta][$id][pedido_item];
			}
		}
		unset($_SESSION[cesta][$id][pedido]);
		unset($_SESSION[cesta][$id][pedido_item]);
		unset($_SESSION[cesta][$id][produto]);
		unset($_SESSION[cesta][$id][qtde]);
		unset($_SESSION[cesta][$id][valor]);
		unset($_SESSION[cesta][$indice][descricao]);
		unset($_SESSION[cesta][$id]);

		$_SESSION[cesta][numero]--;
		//$indice = $indice-1;
	}
}

//alterado por Gustavo HD 3780
if ($btn_acao == "fechar_pedido"){
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	//$linha             = $_POST['linha'];

	
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
	#------------------------------------------------------

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$pedido        = trim ($_SESSION[cesta][pedido]);
	$indice        = trim ($_SESSION[cesta][numero]);
	$itens_remover = trim ($_SESSION[cesta][removerItem]);

	$itens_remover = explode("|",$itens_remover);

	#remove os itens que foram apagados- Somente se já tiver pedido e ele excluir o item
	if(strlen($pedido)>0 AND count($itens_remover)>0){
		for ($i=0; $i < count($itens_remover); $i++){
			$ped_temp = $itens_remover[$i];
			if (strlen($ped_temp)>0){
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $ped_temp
						AND		pedido = $pedido";
				$res = pg_exec ($con,$sql);
			}
		}
	}

	if(strlen($msg_erro)==0){
		if (strlen ($pedido) == 0 ) {
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						fabrica        ,
						condicao       ,
						pedido_cliente ,
						tipo_pedido    ,
						pedido_loja_virtual 
					) VALUES (
						$login_posto        ,
						$login_fabrica      ,
						$aux_condicao       ,
						$aux_pedido_cliente ,
						$aux_tipo_pedido    ,
						TRUE
					)";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (strlen($msg_erro) == 0){
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
				$pedido  = pg_result ($res,0,0);
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						condicao       = $aux_condicao       ,
						pedido_cliente = $aux_pedido_cliente ,
						tipo_pedido    = $aux_tipo_pedido
					WHERE pedido  = $pedido
					AND   posto   = $login_posto
					AND   fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	$valor_total=0;

	if ($indice==0){
		$msg_erro .= "Selecione no mínimo 1 peça!";
	}

	if (strlen ($msg_erro) == 0) {
		for($i=1; $i<=$indice; $i++) {
			$pedidoX         = $_SESSION[cesta][$i][pedido];
			$pedido_item     = $_SESSION[cesta][$i][pedido_item];
			$peca            = $_SESSION[cesta][$i][produto];
			$qtde            = $_SESSION[cesta][$i][qtde];
			$valor           = $_SESSION[cesta][$i][valor];
			$ipi             = $_SESSION[cesta][$i][ipi];
			$descricao       = $_SESSION[cesta][$i][descricao];

			if (strlen ($peca) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
				$msg_erro   = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}

			//verifica se a peça tem o valor da peca caso nao tenha exibe a msg 
			//só verifica os precos dos campos que tenha a referencia da peça.
			if ($login_fabrica == '3' AND strlen($peca_referencia) > 0 ){
				if($tipo_pedido <> '90'){
					if(strlen($valor) == 0){
						$msg_erro = 'Existem peças sem preço.<br>';
						$linha_erro = $i; echo "=>".$valor;
						break;
					}
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca) == 0) {
				// delete
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $pedido_item
						AND		pedido = $pedidoX";
				echo $sql ;
				$res = pg_exec ($con,$sql);
			}

			if (strlen ($peca) > 0) {
				$peca = trim   (strtoupper ($peca));
				$peca = str_replace ("-","",$peca);
				$peca = str_replace (".","",$peca);
				$peca = str_replace ("/","",$peca);
				$peca = str_replace (" ","",$peca);

				$sql = "SELECT  tbl_peca.peca
						FROM    tbl_peca
						WHERE   tbl_peca.peca = $peca
						AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 0) {
					$msg_erro = "Peça $peca não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca = pg_result ($res,0,peca);
				}

				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
										pedido ,
										peca   ,
										qtde   ,
										preco
									) VALUES (
										$pedido,
										$peca  ,
										$qtde  ,
										$valor
									)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde,
									preco = $valor
								WHERE pedido_item = $pedido_item";
					}

					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}
					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
						$res = pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
				//faz a somatoria dos valores das peças para verificar o total das pecas pedidas
				//Apenas para Britania.
				if($login_fabrica == 3)
				{
					if( strlen($valor) > 0 AND strlen($qtde) > 0){
						$total_valor = (($total_valor) + ( str_replace( "," , "." ,$valor) * $qtde));
					}
				}
			}
			//modificado para a Britania pois o valor nao pode ser menor do que 50,00 reias.
			if($login_fabrica == 3 AND 1==2){
				$sql="select posto, capital_interior from tbl_posto where posto=$login_posto";

				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0){
					$posto            = trim(pg_result ($res,0,posto));
					$capital_interior = trim(pg_result ($res,0,capital_interior));
				}

				$sql="select valor_pedido_minimo, valor_pedido_minimo_capital from tbl_fabrica where fabrica=$login_fabrica";

				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0){
					$valor_pedido_minimo         = trim(pg_result ($res,0,valor_pedido_minimo));
					$valor_pedido_minimo_capital = trim(pg_result ($res,0,valor_pedido_minimo_capital));
				}

				if($capital_interior=="CAPITAL"){
					$valor_minimo= $valor_pedido_minimo_capital;
				}
				if($capital_interior=="INTERIOR"){
					$valor_minimo= $valor_pedido_minimo;
				}

				if($tipo_pedido <> '90'){
					if($total_valor < $valor_minimo){
						$msg_erro = '<FONT SIZE="2" COLOR="#FF0000">O valor mínimo não foi atingido.</FONT>';
					}else{
						//condicoes de pagamento depedendo do valor não se pode escolher a forma de pagamento
						if($condicao == 75 AND $total_valor < 200){
							$msg_erro = '<FONT SIZE="2" COLOR="#FF0000">O total do pedido não atinge o valor mínimo da condição de pagamento</FONT>';
						}
						if($condicao == 98 AND $total_valor < 400){
							$msg_erro = '<FONT SIZE="2" COLOR="#FF0000">O total do pedido não atinge o valor mínimo da condição de pagamento</FONT>';
						}
					}
				}
			}
		}

	

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_pedido_finaliza_loja_virtual($pedido,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			#header ("Location: loja_finalizado.php?pedido=$pedido&loc=1");
			$msg = "Pedido finalizado com sucesso!";
			session_unset();
			session_destroy();
			echo "<script languague='javascript'>window.location = 'loja_completa.php?pedido=$pedido&status=finalizado'; </script>";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


echo "<form action='loja_carrinho.php' method='POST' name='frmcarrinho' align='center'>";
echo "<input class='frm' type='hidden' name='pedido' value='<? echo $pedido; ?>'>";

$res = pg_exec ("SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

if (pg_result ($res,0,0) == 'f') {
	echo "<input type='hidden' name='condicao' value=''>";
}else{
	echo "<input type='hidden' name='condicao' value='$condicao'>";
}

/*
$sql = "SELECT   *
		FROM     tbl_posto_fabrica
		WHERE    tbl_posto_fabrica.posto   = $login_posto
		AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
*/
/*
echo "<input class='frm' type='hidden' name='tipo_pedido' value='<? echo $tipo_pedido; ?>'>";
echo "<input class='frm' type='hidden' name='pedido_cliente' size='5' maxlength='20' value='<? echo $pedido_cliente ?>'>";

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
		$pedido_item     = trim(pg_result($res,$i,pedido_item));
		$peca_referencia = trim(pg_result($res,$i,referencia));
		$peca_descricao  = trim(pg_result($res,$i,descricao));
		$qtde            = trim(pg_result($res,$i,qtde));
		$preco           = trim(pg_result($res,$i,preco));
		if (strlen($preco) > 0) {
			$preco = number_format($preco,2,',','.');
		}
	}
}
*/
echo "<input type='hidden' name='pedido_item'size='15' value='<? echo $pedido_item; ?>'>";
echo "</form>";

//########################################################################################################

$layout_menu = 'pedido';
$title = "Carrinho de Compras";

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

include 'loja_menu.php';
$indice = $_SESSION[cesta][numero] ;

if ($ip=='189.47.76.179' AND 1==2){
	for($i=0; $i<=($indice); $i++) {
		//PEGA O INDICE DO PRODUTO
		if (strlen($_SESSION[cesta][$i][produto])>0){
			$cod_produto2   = $_SESSION[cesta][$i][produto];
			$qtde2          = $_SESSION[cesta][$i][qtde];
			$valor2         = $_SESSION[cesta][$i][valor];
			$descricao2     = $_SESSION[cesta][$i][descricao];
			$pedido         = $_SESSION[cesta][$i][pedido];
			$pedido_item    = $_SESSION[cesta][$i][pedido_item];

			echo "<br>$cod_produto2 - $qtde2 - $valor2 - $descricao2";
		}
	}
}

if(strlen($msg_erro)>0){
	$error = str_replace("ERROR:","",$msg_erro);
	echo "<h4 style='color:red'>".$error."</h4>";
}
if(strlen($msg)>0){
	echo "<h4 style='color:blue'>".$msg."</h4>";
}

if($login_posto){
	$sql="SELECT * FROM tbl_posto WHERE posto = $login_posto";
	$res = pg_exec ($con, $sql);
	if(pg_numrows($res)>0){
		$posto			= trim(pg_result ($res,0,posto));
		$nome			= trim(pg_result ($res,0,nome));
	}
}
echo "<table width='700' border='1' align='center' cellpadding='0' cellspacing='0' style='border:#B5CDE8 1px solid;  bordercolor:#d2e4fc;border-collapse: collapse'>";

echo "<tr>";
echo "<td align='left'  colspan='6' bgcolor='#e6eef7' class='Titulo' ><br><B>Carrinho de Compras</B><br></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='6' align='right' class='Conteudo' style='font-size:14px;padding:5px'><a href='loja_completa.php'>Continuar Comprando</a> | <a href='$PHP_SELF?acao=limpa'>Limpar Carrinho</a> | <a href='$PHP_SELF?btn_acao=fechar_pedido' value='Fechar Pedido'>Fechar Pedido</a>";
echo "</td>";
echo "</tr>";

//cabeca
echo "<tr bgcolor='#e6eef7' class='Titulo'>";
	echo "<td width='20' height='30' align='center' >Remover?&nbsp;&nbsp;</td>";
	echo "<td  height='30' align='left'>Peça</td>";
	echo "<td  height='30' align='right'>Qtde</td>";
	echo "<td  height='30' align='right'>Valor Unit.</td>";
	echo "<td  height='30' align='center'>IPI</td>";
	echo "<td  height='30' align='right'>Valor Total</td>";
echo "</tr>";
//fim cabeca

for($i=1; $i<=($indice); $i++) {

	//PEGA O INDICE DO PRODUTO
	if (strlen($_SESSION[cesta][$i][produto])>0){

		$pedido         = $_SESSION[cesta][$i][pedido];
		$pedido_item    = $_SESSION[cesta][$i][pedido_item];
		$cod_produto2   = $_SESSION[cesta][$i][produto];
		$qtde2          = $_SESSION[cesta][$i][qtde];
		$valor2         = $_SESSION[cesta][$i][valor];
		$ipi            = $_SESSION[cesta][$i][ipi];
		$descricao2     = $_SESSION[cesta][$i][descricao];



		$valor2      = str_replace(",",".",$valor2);

		if (strlen($ipi)>0 AND $ipi>0){
			$valor_total = $valor2 * $qtde2 + ($valor2*$qtde2 *$ipi/100);
			$ipi = $ipi." %";
		}else{
			$ipi = "";
			$valor_total = ($valor2*$qtde2);
		}

		$valor2      = str_replace(".",",",$valor2);
		$soma       += $valor_total;
	
		$valor_total = number_format($valor_total, 2, ',', '');

		$soma = number_format($soma, 2, ',', '');
		$soma = str_replace(".",",",$soma);

		//EXIBE OS PRODUTOS DA CESTA
		$a++;
		$cor = "#FFFFFF"; 
		//alterado HD 3780 - Gustavo

		if ($a % 2 == 0){
			$cor = '#EEEEE3';
		}

		echo "<tr class='Conteudo'>";
		echo "<td  bgcolor='$cor'  align='center'><a href='$PHP_SELF?acao=remover&id=$i&pedido_item=$pedido_item'><IMG SRC='imagens/excluir_loja.gif' alt='Remover Produto'border='0'></a></td>";
		echo "<td  bgcolor='$cor' align='left'>$cod_produto2 - $descricao2</td>";
		echo "<td  bgcolor='$cor' align='right'>$qtde2</td>";
		echo "<td  bgcolor='$cor' align='right'>R$ $valor2</td>";
		echo "<td  bgcolor='$cor' align='center'>$ipi</td>";
		echo "<td  bgcolor='$cor' align='right'>R$ $valor_total</td>";
		echo "</tr>";
	}
}

//TOTAL
echo "<tr>";
echo "<td  colspan='5' height='30' align='right' bgcolor='#D5D5CB'><font size='2'><B>Total da Compra:</B></font>";
echo "</td>";
echo "<td align='right' bgcolor='#D5D5CB'><font color='#008800'><b>R$ $soma</B></font>";
echo "</td>";
echo "</tr>";
//TOTAL

echo "<tr>";
echo "<td align='center' bgcolor='#e6eef7' colspan='7' class='Titulo' style='padding:5px;'><a href='javascript:history.back()'>Voltar</a></td>";
echo "</tr>";

echo "</table>";

include 'rodape.php'
?>
