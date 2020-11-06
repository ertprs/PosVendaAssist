<?php
	$sqlCustoAdicional = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os AND valores_adicionais notnull";
	$resCustoAdicional = pg_query($con,$sqlCustoAdicional);

	if(pg_num_rows($resCustoAdicional) > 0){

		$custos_adicionais = pg_fetch_result($resCustoAdicional,0,'valores_adicionais');
		$custos_adicionais = json_decode($custos_adicionais,true);
		
		$monta_tabela = false;
		foreach ($custos_adicionais as $key => $value) {
			if (is_array($value) ) {
				$monta_tabela = true;
			}
		}

		if ($monta_tabela === true) {
			?>
			<br />
			<table width='700px' align='center' class='Tabela' cellspacing='1' cellpadding='0'>
				<tr class='inicio' ><td colspan='2' align='center'>CUSTOS ADICIONAIS</td></tr>
				<tr class='titulo2'>
					<td align='center'>SERVIÇO</td>
					<?php
						if($login_fabrica <> 125){
					?>
							<td align='center'>VALOR</tr>
					<?php
						}
					?>
				</tr>
				<?php
				$i = 0;
				foreach ($custos_adicionais as $key => $value) {
					foreach ($value as $chave => $valor) {
						$total_servico += str_replace(",",".",$valor);
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA"; ?>
						<tr bgcolor='<?=$cor?>'>
							<td align='left' class='justificativa' width='600'> <?=utf8_decode($chave)?> </td>
							<?php
							if($login_fabrica <> 125){ ?>
								<td align='right' class='justificativa'> <?=$valor?> </td>
							<?php
							} ?>
						</tr>
						<?php				
						$i++;
					}
				}
				if(in_array($login_fabrica,array(166))){
				?>
					<tr>
						<td>Valor Total de Serviços</td>
						<td><?=number_format($total_servico,2,',','.')?></td>
					</tr>
				<?php
				}
				?>
			</table>
		<?php			
		}
	}
