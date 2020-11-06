<style>
#table_laudo_tecnico {
	border: #485989 1px solid;
	width: 700px;
	margin: 0 auto;
	border-collapse: collapse;
}

#table_laudo_tecnico td {
	font-size: 12px;
	padding-right: 5px;
	padding-top: 5px;
	padding-bottom: 5px;
}

#table_laudo_tecnico .titulo_laudo_tecnico {
	background-color: #596D9B;
	color: #FFFFFF;
	font-size: 12px;
	font-weight: bold;
	text-align: right;
}
</style>
<script>
	$(function () {
		$("input[name=laudo_tecnico_data],input[name=laudo_tecnico_data_instalado],input[name=laudo_tecnico_nota_fiscal_data]").datepick({startdate:'01/01/2000'});
		$("input[name=laudo_tecnico_data],input[name=laudo_tecnico_data_instalado],input[name=laudo_tecnico_nota_fiscal_data]").mask("99/99/9999");
	});
</script>
<br />
<table id="table_laudo_tecnico" border="1" >
	<thead>
		<tr>
			<th class="titulo_laudo_tecnico" style="text-align: center;" colspan="2">
				Laudo Técnico
			</th>
		</tr>
	</thead>
	<tbody>
	<tr>
		<td class="titulo_laudo_tecnico">
			Nº DA ASSISTÊNCIA
		</td>
		<td>
			<input class="frm" type="text" name="laudo_tecnico_posto_numero" value="<?=$laudo_tecnico['laudo_tecnico_posto_numero']?>" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			INSTALADO EM
		</td>
		<td>
			<input class="frm" type="text" name="laudo_tecnico_data_instalado" value="<?=$laudo_tecnico['laudo_tecnico_data_instalado']?>" style="width: 90px;" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			NOME DA INSTALADORA
		</td>
		<td>
			<input class="frm" type="text" name="laudo_tecnico_instaladora_nome" value="<?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?>" style="width: 100%;" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			ÁGUA UTILIZADA
		</td>
		<td>
			DIRETO DA RUA/REDE DE ABASTECIMENTO<input class="frm" type="radio" name="laudo_tecnico_agua_utilizada" value="direto_da_rua" <?=($laudo_tecnico['laudo_tecnico_agua_utilizada'] == 'direto_da_rua') ? "CHECKED": ""?> /><br />
			CAIXA/REDE DE ABASTECIMENTO<input class="frm" type="radio" name="laudo_tecnico_agua_utilizada" value="caixa" <?=($laudo_tecnico['laudo_tecnico_agua_utilizada'] == 'caixa') ? "CHECKED": ""?> /><br />
			POÇO<input class="frm" type="radio" name="laudo_tecnico_agua_utilizada" value="poco" <?=($laudo_tecnico['laudo_tecnico_agua_utilizada'] == 'poco') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			PRESSURIZADOR
		</td>
		<td>
			SIM<input class="frm" type="radio" name="laudo_tecnico_pressurizador" value="true" <?=($laudo_tecnico['laudo_tecnico_pressurizador'] == 'true') ? "CHECKED": ""?> />
			NÃO<input class="frm" type="radio" name="laudo_tecnico_pressurizador" value="false" <?=($laudo_tecnico['laudo_tecnico_pressurizador'] == 'false') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			TENSÃO
		</td>
		<td>
			110V<input class="frm" type="radio" name="laudo_tecnico_tensao" value="110v" <?=($laudo_tecnico['laudo_tecnico_tensao'] == '110v') ? "CHECKED": ""?> />
			220V<input class="frm" type="radio" name="laudo_tecnico_tensao" value="220v" <?=($laudo_tecnico['laudo_tecnico_tensao'] == '220v') ? "CHECKED": ""?> />
			PILHA<input class="frm" type="radio" name="laudo_tecnico_tensao" value="pilha" <?=($laudo_tecnico['laudo_tecnico_tensao'] == 'pilha') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			TIPO DE GÁS
		</td>
		<td>
			GN<input class="frm" type="radio" name="laudo_tecnico_tipo_gas" value="gn" <?=($laudo_tecnico['laudo_tecnico_tipo_gas'] == 'gn') ? "CHECKED": ""?> />
			GLP<input class="frm" type="radio" name="laudo_tecnico_tipo_gas" value="glp" <?=($laudo_tecnico['laudo_tecnico_tipo_gas'] == 'glp') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			SE GLP
		</td>
		<td>
			ESTÁGIO ÚNICO<input class="frm" type="radio" name="laudo_tecnico_gas_glp" value="estagio_unico" <?=($laudo_tecnico['laudo_tecnico_gas_glp'] == 'estagio_unico') ? "CHECKED": ""?> />
			DOIS ESTÁGIOS<input class="frm" type="radio" name="laudo_tecnico_gas_glp" value="dois_estagios" <?=($laudo_tecnico['laudo_tecnico_gas_glp'] == 'dois_estagios') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			PRESSÃO DE GÁS
		</td>
		<td>
			DINÂMICA<input class="frm" type="text" name="laudo_tecnico_pressao_gas_dinamica" value="<?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?>" />(consumo máx.)<br />
			ESTÁTICA<input class="frm" type="text" name="laudo_tecnico_pressao_gas_estatica" value="<?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?>" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			PRESSÃO DE ÁGUA
		</td>
		<td>
			DINÂMICA<input class="frm" type="text" name="laudo_tecnico_pressao_agua_dinamica" value="<?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?>" />(consumo máx.)<br />
			ESTÁTICA<input class="frm" type="text" name="laudo_tecnico_pressao_agua_estatica" value="<?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?>" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			DIÂMETRO DO DUTO
		</td>
		<td>
			<input class="frm" type="text" name="laudo_tecnico_diametro_duto" value="<?=$laudo_tecnico['laudo_tecnico_diametro_duto']?>" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			COMPRIMENTO TOTAL DO DUTO
		</td>
		<td>
			<input class="frm" type="text" name="laudo_tecnico_comprimento_total_duto" value="<?=$laudo_tecnico['laudo_tecnico_comprimento_total_duto']?>" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			QUANT. DE CURVAS
		</td>
		<td>
			<input class="frm" type="text" name="laudo_tecnico_quantidade_curvas" value="<?=$laudo_tecnico['laudo_tecnico_quantidade_curvas']?>" style="width: 50px;" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			CARACTERÍSTICAS DO LOCAL DE INSTALAÇÃO
		</td>
		<td>
			EXTERNO<input class="frm" type="radio" name="laudo_tecnico_caracteristica_local_instalacao" value="externo" <?=($laudo_tecnico['laudo_tecnico_caracteristica_local_instalacao'] == 'externo') ? "CHECKED": ""?> />
			INTERNO<input class="frm" type="radio" name="laudo_tecnico_caracteristica_local_instalacao" value="interno" <?=($laudo_tecnico['laudo_tecnico_caracteristica_local_instalacao'] == 'interno') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			SE INTERNO QUAL O AMBIENTE
		</td>
		<td nowrap >
			ÁREA DE SERVIÇO<input class="frm" type="radio" name="laudo_tecnico_local_instalacao_interno_ambiente" value="area_servico" <?=($laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente'] == 'area_servico') ? "CHECKED": ""?> />
			OUTRO/ESPECIFIQUE<input class="frm" type="radio" name="laudo_tecnico_local_instalacao_interno_ambiente" value="outro" <?=($laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente'] == 'outro') ? "CHECKED": ""?> />
			:<input class="frm" type="text" name="laudo_tecnico_local_instalacao_interno_ambiente_outro" value="<?=$laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente_outro']?>" />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			INSTALAÇÃO DE ACORDO COM O NBR 13.103
		</td>
		<td>
			SIM<input class="frm" type="radio" name="laudo_tecnico_instalacao_nbr" value="true" <?=($laudo_tecnico['laudo_tecnico_instalacao_nbr'] == 'true') ? "CHECKED": ""?> />
			NÃO<input class="frm" type="radio" name="laudo_tecnico_instalacao_nbr" value="false" <?=($laudo_tecnico['laudo_tecnico_instalacao_nbr'] == 'false') ? "CHECKED": ""?> />
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			PROBLEMA DIAGNOSTICADO
		</td>
		<td>
			<textarea class="frm"  name="laudo_tecnico_problema_diagnosticado" style="width: 100%;" ><?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?></textarea>
		</td>
	</tr>
	<tr>
		<td class="titulo_laudo_tecnico">
			PROVIDÊNCIAS ADOTADAS
		</td>
		<td>
			<textarea class="frm"  name="laudo_tecnico_providencias_adotadas" style="width: 100%;" ><?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?></textarea>
		</td>
	</tr>
</table>
<br /><br />