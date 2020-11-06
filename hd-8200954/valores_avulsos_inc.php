<?php
	$sqlAvulso = "SELECT historico, valor, extrato FROM tbl_extrato_lancamento WHERE os = $os AND extrato notnull";
	$resAvulso = pg_query($con,$sqlAvulso);

	if(pg_num_rows($resAvulso) > 0){

?>
		<br />
		<table width='700px' align='center' class='Tabela' cellspacing='1' cellpadding='0'>
			<tr class='inicio' ><td colspan='2' align='center'>VALOR AVULSO NO EXTRATO</td></tr>
			<tr class='titulo2'>
				<td align='center'>DESCRIÇÃO</td>
				<td align='center'>VALOR</td>
				<td align='center'>EXTRATO</td>
			</tr>
<?php
			for($i=0;$i<pg_num_rows($resAvulso);$i++) {
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$valor		= pg_fetch_result($resAvulso,$i,'valor');
				$historico	= pg_fetch_result($resAvulso,$i,'historico');
				$extrato	= pg_fetch_result($resAvulso,$i,'extrato');

?>
				<tr bgcolor='<?=$cor?>'>
						<td align='center' class='justificativa'> <?=$historico?> </td>
						<td align='right' class='justificativa'> <?=number_format($valor,"2",",",".")?> </td>
						<td align='right' class='justificativa'> <?=$extrato?> </td>
				</tr>
<?php				
			}
?>
		</table>
<?php

	}
