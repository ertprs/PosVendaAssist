<style>
P{
	font-family: Arial;
	font-size: 12px;
	text-align: justify;
}
</style>

<table width='400' border='0'  cellspacing='0' cellpadding='5' align='center'>

	<tr>
		<td>
			<?php if (!isset($_GET['sem_acao'])) { ?>
			<p><center><font color='#ff0000'>ATEN��O!</font></p>
			<?php } ?>
			<p>O CNPJ informado nessa O.S � da Stanley Black & Decker. Nesse caso � necess�rio seguir as orienta��es abaixo:</p>
			<p>1 - Se o produto for de estoque de revenda dever� ser digitado na O.S de revenda com uma NF de remessa para conserto emitida para seu posto autorizado</p>
			<p>2 - Produto de loca��o possui 6 meses de garantia, mas, para esse atendimento n�o deve ser digitado ordem de servi�o no Telecontrol, pois a m�o de obra � paga pelo pr�prio locador, caso optar em realizar o reparo na assist�ncia autorizada.</p>
			<p>3 - Se houver uma situa��o diferente das mencionadas no item 1 e 2 gentileza informar o motivo na observa��o da ordem de servi�o para que possamos analisar. Em caso de d�vidas, gentileza entrar em contato com o suporta da sua regi�o</p>
			<p>Obrigado.</p>
		</td>
	</tr>
	<?php if (!isset($_GET['sem_acao'])) { ?>
	<tr>
		<td>
			<center><input type="button" name="btn_acao" value="Li e Confirmo." onClick="javascript:window.close('#');">
		</td>
	</tr>
	<?php } ?>

</table>
