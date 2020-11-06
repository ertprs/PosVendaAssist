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
			<p><center><font color='#ff0000'>ATENÇÃO!</font></p>
			<?php } ?>
			<p>O CNPJ informado nessa O.S é da Stanley Black & Decker. Nesse caso é necessário seguir as orientações abaixo:</p>
			<p>1 - Se o produto for de estoque de revenda deverá ser digitado na O.S de revenda com uma NF de remessa para conserto emitida para seu posto autorizado</p>
			<p>2 - Produto de locação possui 6 meses de garantia, mas, para esse atendimento não deve ser digitado ordem de serviço no Telecontrol, pois a mão de obra é paga pelo próprio locador, caso optar em realizar o reparo na assistência autorizada.</p>
			<p>3 - Se houver uma situação diferente das mencionadas no item 1 e 2 gentileza informar o motivo na observação da ordem de serviço para que possamos analisar. Em caso de dúvidas, gentileza entrar em contato com o suporta da sua região</p>
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
