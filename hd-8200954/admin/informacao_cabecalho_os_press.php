<?php		

	$os = $_GET['os'];	
	$urlTrocar = "os_cadastro.php?os={$os}&osacao=trocar";
	$urlAlterar = "os_cadastro.php?os={$os}";
	if ($novaTelaOs) {
		$urlTrocar = "os_troca_subconjunto.php?os={$os}";
		$urlAlterar = "cadastro_os.php?os_id={$os}";
	}

	
	$data_atual = date('d/m/Y');	
?>
<br>
<table width='700' cellpadding='0' align='center'>
	<tr>
		<td width="20%">
			<table  class='Tabela' width="100%">
				<tr>
					<td class='inicio' style="text-align: center;">CHAMADO</td>
				</tr>
				<tr>
					<td class='conteudo' style="text-align: center;">
						<?php echo ($hd_chamado > 0) ? "<a target='_blank' href='callcenter_interativo_new.php?callcenter={$hd_chamado}'>{$hd_chamado}</a>": 'Não tem' ;?>
					</td>
				</tr>
			</table>
		</td>
		<td width="60%" >
		</td>
		<td width="20%">
			<table class='Tabela' width="100%">
				<tr>
					<td colspan="4" class='inicio' style="text-align: center;">AÇÕES</td>
				</tr>
				<tr>
					<td class='conteudo' style="text-align: center;">
					<a href="<?=$urlTrocar?>" target="_blank">
							<img border="0" src="imagens_admin/btn_trocar_azul.gif">
						</a>
					</td>
					<td class='conteudo' style="text-align: center;">
					<a href="<?=$urlAlterar?>" target="_blank">
							<img border="0" src="imagens_admin/btn_alterar_azul.gif">
						</a>
					</td>
					<td class='conteudo' style="text-align: center;">
						<a href="relatorio_status_os_tempo.php?os=<?=$os?>&data_atual=<?=$data_atual?>" target="_blank">
							<img border="0" src="imagens_admin/btn_timeline_azul.png">
						</a>
					</td>
					<?php if($login_privilegios == '*'){ ?>
					<td class='conteudo' style="text-align: center;">
						<a href="os_press.php?os=<?=$os?>&fabrica=<?=$login_fabrica?>&pedidos=<?=$pedidos?>&gera_embarque=true">
							<img border="0" src="imagens_admin/btn_embarcar_azul.png">
						</a>
					</td>
					<?php } ?>
				</tr>
			</table>
		</td>
	</tr>
</table>
<br>

<?php
	if(isset($_GET['gera_embarque']) && $_GET['gera_embarque'] == true) {
		include "gerar_embarque_os_press.php";		
	}
?>
