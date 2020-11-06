<?
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico)>0){
		include 'login_unico_autentica_usuario.php';
		$login_fabrica = 10;
	}elseif (strlen($cook_fabrica)==0 AND strlen($cook_login_simples)>0){
		include 'login_simples_autentica_usuario.php';
	}else{
		include 'autentica_usuario.php';
	}

	if(strlen($_POST['produto_acabado'])>0) $produto_acabado = $_POST['produto_acabado'];
	else                                    $produto_acabado = $_GET['produto_acabado'];
?>

<style>
	body{
		background-color: #EBEBEB;
	}

	P{
		text-align: justify;
	}

	.Titulo{
		text-align: center;
		font-size: 18px;
		font-weight: bold;
	}

	.sub_titulo{
		font-size: 14px;
		font-weight: bold;
	}

	.link{
		text-align: center;
		font-size: 15px;
		font-weight: bold;
		text-decoration: none;
		color: #0033FF;
	}

</style>

<?
echo "<table width='700' border='0' cellpadding='5' cellspacing='5' align='center' style='border-width:5px ; border-style:  groove; border-color: #808080;' bgcolor='#FFFFFF'>";
	echo "<tr align='center'>";
			echo "<td width='180' align='center'>
				<IMG SRC='logos/britania.jpg'></td>";
			echo "<td width='50%' height='60'><br></td>";
			echo "<td width='180' align='center'>
				<IMG SRC='logos/telecontrol.jpg'></td>";
	echo "</tr>";

	echo "<tr>";
		echo "<td colspan='3'>";
			echo "<P class='titulo'>CONTRATO COMERCIAL � VENDA PRODUTOS AOS POSTOS AUTORIZADOS</P>";

			echo "<P class='sub_titulo'>Pedido</P>";

			echo "<ul>";
			echo "<li>Os pedidos dever�o ser implantados diretamente na Loja Virtual e estar�o sujeitos a aprova��o financeira.";
			echo "<li>A quantidade de unidades estar� vinculada a disponibilidade do estoque e a quantidade m�xima permitida por Posto Autorizado.";
			echo "<li>Os pedidos ser�o aprovados ap�s an�lise de cr�dito e do Posto Autorizado.";
			echo "<li>Pedidos pendentes poder�o ser cancelados pela Brit�nia a qualquer momento.";
			echo "</ul>";

			echo "<P>Frete e Entrega";
			echo "Frete: CIF";
			echo "Prazo de entrega: 2 a 12 dias �teis ap�s a emiss�o da NF de venda.";
			echo "</P>";

			echo "<P class='sub_titulo'>Envio e Recebimento do produto</P>";

			echo "<ul>";
			echo "<li>Todos os produtos ser�o despachados em caixas personalizadas e lacradas. ";
			echo "ATEN��O: No ato do recebimento, se houver ind�cios de viola��o, quebra ou molhadura, recusar o recebimento do produto e entrar em contato com a Central de Atendimento, 0800-415300 ou sap@britania.com.br;";
			echo "<li>N�o h� possibilidade de agendamento para a entrega; ";
			echo "<li>Recusar o recebimento do Produto no ato da entrega quando o produto recebido for diferente daquele solicitado.";
			echo "<li>Caso algum produto n�o solicitado seja recebido, o prazo para solicitar devolu��o � de 72 (setenta e duas) horas, contados do recebimento do mesmo.";
			echo "</ul>";

			echo "<P class='sub_titulo'>Trocas e devolu��es</P>";

			echo "<ul>";
			echo "<li>Em caso de devolu��o ou troca, e somente mediante autoriza��o da Brit�nia, o produto dever� ser encaminhado ao endere�o de origem na embalagem original, sem ind�cios de uso, sem viola��o do lacre do fabricante, com todos os acess�rios.";
			echo "</ul>";

			echo "<P class='sub_titulo'>Formas de pagamento</P>";

			echo "<P>A compra poder� ser parcelada em 30, 60 e 90 dias de acordo com o valor da compra, por�m estes valores est�o sujeitos a an�lise financeira, respeitando os seguintes crit�rios.</P>";

			echo "<ul>";
			echo "<li>At� R$ 200,00 pagamento em 30 dias";
			echo "<li>De R$ 200,00 at� R$ 300,00 pagamento em 30 e 60 dias";
			echo "<li>Acima de R$ 300,00 pagamento em 30, 60 e 90 dias.";
			echo "</ul>";

			echo "<P>N�o ser� realizado encontro de contas relacionados a M�o de Obra de produtos reparados em garantia.<br>";
			echo "Caso o pagamento n�o ocorra na data prevista, implicar� em cobran�as financeiras e administrativas e o bloqueio de novas compras.</P>";

			echo "<P class='sub_titulo'>Defeito</P>";

			echo "<ul>";
			echo "<li>Dever�o ser respeitados os crit�rios definidos no certificado de garantia do produto.";
			echo "</ul>";

			echo "<P>Em caso de d�vida, entre em contato com o Servi�o de Atendimento ao posto autorizado no telefone n� 0800-415300 ou atrav�s do e-mail <a href='mailto:sap@britania.com.br'>sap@britania.com.br</a></P>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
			echo "<td align='center' colspan='3'>";
				echo "<a href=\"javascript:if (confirm('Deseja finalizar este pedido? Este pedido ser� enviado para a F�brica.')) window.location = 'lv_carrinho.php?btn_acao=fechar_pedido&produto_acabado=t'\" value='Fechar Pedido' class='link'>CONCORDO</a>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;";
				echo "<a href='lv_carrinho.php?produto_acabado=t' class='link'>N�O CONCORDO</a>";
			echo "</td>";
	echo "</tr>";
echo "</table>";
?>


