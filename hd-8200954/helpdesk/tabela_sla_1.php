<style>
	.cabecalho, .fabricante{
		background: #E7E6E6;
		font-size: 14px;
		font-weight: bold;
	}

	.fabricante{
		background: #D0CECE;
	}

	body td{
		font-size: 14px;
		text-align: center;
	}
	.demanda60{
		background-color: #F08080;
	}

	.laranja{
		background-color: #F8CBAD;
	}

	.amarelo{
		background-color: #FFF2CC;
	}

	div.box_tabela {
		margin: 15px  0 0 20px;
		background-color: #fff;
	}
</style>

<div class="box_tabela">
	<table width="350"  border=1 cellpadding="4" cellspacing="0">
		<tr>
			<td colspan="3" class='fabricante'>Stanley Black&Decker</td>
		</tr>
		<tr class="cabecalho">
			<td align="center">Tipo do Chamado</td>
			<td align="center"  style=" width: 100px; ">Impacto Financeiro
			</td>
			<td align="center"  style=" width: 120px; ">Prazo</td>
		</tr>
		<tr style="background-color: #F8CBAD">
			<td align="center" rowspan="2" style="background-color: #FF5050; width: 150px;">Erro em Programa</td>
			<td>SIM</td>
			<td>3 Dias Úteis</td>
		</tr>
		<tr>
			<td class="amarelo">NÃO</td>
			<td class="amarelo">5 Dias Úteis</td>
		</tr>
	</table>
	<br>
	<table width="350" border=1 cellpadding="4" cellspacing="0" >
		<tr >
			
			<td class="cabecalho" align="center">Tipo do Chamado</td>
			<td class="cabecalho" style=" width: 100px; ">Etapa</td>
			<td class="cabecalho"  style=" width: 120px; ">Previsão Cliente</td>
		</tr>
		<tr>
			<td style="background-color: #C6E0B4;  width: 150px" rowspan="5" align="center">Alteração de Dados</td>
			<td class="amarelo">Orçamento</td>
			<td class="amarelo">7 Dias Úteis</td>
		</tr>
		<tr>
			<td class="amarelo">< 30h </td>
			<td class="amarelo">10 Dias Úteis</td>
		</tr>
		<tr>
			<td class="amarelo">31h > < 60h</td>
			<td class="amarelo">25 Dias Úteis</td>
		</tr>
		<tr>
			<td class="amarelo">61h > < 90h </td>
			<td class="amarelo">35 Dias Úteis</td>
		</tr>
		<tr>
			<td class="amarelo">  91h < </td>
			<td class="amarelo">À Combinar</td>
		</tr>
		<tr>
			<td colspan="3" class='laranja' >Prazo para Chamados de Alteração de Dados, abertos quando já existir uma Demanda >= 60 horas em desenvolvimento</td>
		</tr>
		<tr>
			<td colspan="3" class="amarelo" >Adicionar ao Prazo + 50% de dias</td>
		</tr>
	</table>
</div>