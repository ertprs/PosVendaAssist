<?php

$sql = "
SELECT MAX(data_geracao) FROM tbl_posto_media_atendimento WHERE fabrica=$login_fabrica AND linha IS NULL
";
$res = pg_exec($con, $sql);
$data_geracao = pg_result($res, 0, 0);

if (strlen($data_geracao) > 0) {
$sql = "
SELECT
qtde_media_extrato,
qtde_aberta,
qtde_media,
qtde_extrato,
qtde_os_reincidente_90,
porc_qtde_os_reincidente_90,
porc_qtde_pedidos_90,
(qtde_peca_90::double precision/qtde_digitada_90)::double precision AS peca_por_os,
qtde_finalizadas_30,
ranking,
TO_CHAR(data_geracao, 'DD/MM/YYYY') AS data_geracao

FROM
tbl_posto_media_atendimento

WHERE
tbl_posto_media_atendimento.posto=$login_posto
AND data_geracao='$data_geracao'	
";
$res = pg_exec($con, $sql);

$qtde_media_extrato = number_format(pg_result($res, qtde_media_extrato), 2, ",", ".");
$qtde_aberta = pg_result($res, qtde_aberta);
$qtde_media = number_format(pg_result($res, qtde_media), 2, ",", ".");
$qtde_extrato = pg_result($res, qtde_extrato);
$qtde_os_reincidente_90 = pg_result($res, qtde_os_reincidente_90);
$porc_qtde_os_reincidente_90 = number_format(pg_result($res, porc_qtde_os_reincidente_90), 2, ",", ".");
$porc_qtde_pedidos_90 = number_format(pg_result($res, porc_qtde_pedidos_90), 2, ",", ".");
$peca_por_os = number_format(pg_result($res, peca_por_os), 2, ",", ".");
$qtde_finalizadas_30 = number_format(pg_result($res, qtde_finalizadas_30), 2, ",", ".");
$data_geracao = pg_result($res, data_geracao);
$ranking = pg_result($res, ranking);
//echo $qtde_extrato;
#if ($qtde_extrato > 100)
#{
#	$sql = "
#	SELECT
#	MAX(ranking) AS max_ranking
#	
#	FROM
#	tbl_posto_media_atendimento
#
#	WHERE
#	qtde_extrato > 100
#	";
#
#	$res = pg_query($con, $sql);
#	$max_ranking = pg_result($res, max_ranking);
#}
#elseif ($qtde_extrato > 10)
#{
	$sql = "
	SELECT MAX(data_geracao) FROM tbl_posto_media_atendimento WHERE fabrica=$login_fabrica
	";
	$res = pg_exec($con, $sql);
	$data_geracao = pg_result($res, 0, 0);

	$sql = "
	SELECT
	MAX (ranking) AS max_ranking
	
	FROM
	tbl_posto_media_atendimento

	WHERE
	tbl_posto_media_atendimento.fabrica=$login_fabrica
	AND tbl_posto_media_atendimento.data_geracao='$data_geracao'
	AND qtde_extrato > 10
	AND qtde_extrato <= 100
	";

	$res = pg_query($con, $sql);
	$max_ranking = pg_result($res, max_ranking);
#}


echo "
<style>
	table
	{
		border-collapse: collapse;
	}

	td
	{
		border-collapse: collapse;
	}

	#pecanome
	{
		width: 528px;
	}

	.relcabecalho
	{
		background-color: #596D9B;
		border: 1px solid #FFFFFF;
		color: #FFFFFF;
	}

	.relcabecalhoos
	{
		background-color: #999999;
	}

	.relerro
	{
		color: #FF0000;
		font-size: 11pt;
		padding: 20px;
		background-color: #F7F7F7;
		text-align: center;
	}

	.rellinha0
	{
		background-color: #F1F4FA;
		border: solid 1px #FFFFFF;
		height: 20px;
	}

	.rellinha1
	{
		background-color: #F7F5F0;
		border: solid 1px #FFFFFF;
		height: 20px;
	}

	.relinstrucoes
	{
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
		height: 20px;
	}

	.relopcoes
	{
		text-align: center;
		background-color: #BBBBEE;
		height: 30px;
		text-align: center;
		width: 696px;
	}

	.relprincipal
	{
		font-size: 10pt;
		border: 2px solid #596D9B;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		width: 700px;
	}

	.reltitulo
	{
		text-align: center;
		background-color: #596D9B;
		height: 40px;
		font-size: 11pt;
		font-weight: bold;
		color: #FFFFFF;
		border-bottom: 1px solid #FFFFFF;
	}

	.rellink
	{
		text-decoration: none;
		font-weigth: normal;
		color: #000000;
	}
</style>

	<table class=relprincipal align=center>
		<tr>
			<td class=reltitulo colspan=4>
				ANÁLISE DE INDICADORES<br>
				<font style='font-size:7pt'>Última atualização: " . implode('/', array_reverse(explode('-', $data_geracao))) . "</font>
			</td>
		</tr>
		<tr class=relinstrucoes>
			<td width=500>
				INDICADOR
			</td>
			<td width=50 align=right colspan=2>
				VALOR
			</td>
			<td width=150 align=center>
				META
			</td>
		</tr>
		<tr class=rellinha0>
			<td>
				<label title='Prazo médio de fechamento de OSs nos últimos três extratos. Data de fechamento menos a data de abertura.'>1) PRAZO MÉDIO DE ATENDIMENTO (3 MESES)</label>
			</td>
			<td align=right>
				<label title='Prazo médio de fechamento de OSs nos últimos três extratos. Data de fechamento menos a data de abertura.'>$qtde_media_extrato</label>
			</td>
			<td>
				<label title='Prazo médio de fechamento de OSs nos últimos três extratos. Data de fechamento menos a data de abertura.'>&nbsp;dias</label>
			</td>
			<td align=center>
				<label title='Prazo médio de fechamento de OSs nos últimos três extratos. Data de fechamento menos a data de abertura.'>10 dias</label>
			</td>
		</tr>
		<tr class=rellinha0>
			<td>
				<label title='Quantidade de OSs reincidentes dos últimos três meses dividido pelo total de OSs finalizadas nos últimos três meses.'>2) % REINCIDÊNCIAS</label>
			</td>
			<td align=right>
				<label title='Quantidade de OSs reincidentes dos últimos três meses dividido pelo total de OSs finalizadas nos últimos três meses.'>$porc_qtde_os_reincidente_90</label>
			</td>
			<td>
				<label title='Quantidade de OSs reincidentes dos últimos três meses dividido pelo total de OSs finalizadas nos últimos três meses.'>&nbsp;%</label>
			</td>
			<td align=center>
				<label title='Quantidade de OSs reincidentes dos últimos três meses dividido pelo total de OSs finalizadas nos últimos três meses.'>0,5%</label>
			</td>
		</tr>
		<tr class=rellinha0>
			<td>
				<label title='Quantidade de peças pedidas divididas pela Quantidade de OSs Finalizadas- Média dos 3 últimos Extratos.'>3) PEÇAS POR OS</label>
			</td>
			<td align=right>
				<label title='Quantidade de peças pedidas divididas pela Quantidade de OSs Finalizadas- Média dos 3 últimos Extratos.'>$peca_por_os</label>
			</td>
			<td>
			</td>
			<td align=center>
				<label title='Quantidade de peças pedidas divididas pela Quantidade de OSs Finalizadas- Média dos 3 últimos Extratos.'>0,60</label>
			</td>
		</tr>
		<tr class=rellinha0>
			<td>
				<label title='Quantidade de OSs Fechadas abaixo de 30 dias dividido por Quantidade de OSs Finalizadas- Média de 3 Extratos.'>4) % de OSs abaixo de 30 dias</label>
			</td>
			<td align=right>
				<label title='Quantidade de OSs Fechadas abaixo de 30 dias dividido por Quantidade de OSs Finalizadas- Média de 3 Extratos.'>$qtde_finalizadas_30</label>
			</td>
			<td>
				<label title='Quantidade de OSs Fechadas abaixo de 30 dias dividido por Quantidade de OSs Finalizadas- Média de 3 Extratos.'>&nbsp;%</label>
			</td>
			<td align=center>
				<label title='Quantidade de OSs Fechadas abaixo de 30 dias dividido por Quantidade de OSs Finalizadas- Média de 3 Extratos.'>100%</label>
			</td>
		</tr>
		";
		if ($ranking)
		{
			echo "
		<tr class=relinstrucoes>
			<td colspan=4 style='font-size: 12pt;'>
				RANKING DO POSTO: $ranking / $max_ranking
			</td>
		</tr>";
		}
		echo "
	</table>";

if (($_GET["datainicial"]) && ($_GET["datafinal"]) && 1==2  )
{
	$data_inicial = implode("-", array_reverse(explode("/", $_GET["datainicial"])));
	$data_final = implode("-", array_reverse(explode("/", $_GET["datafinal"])));

	//SELECIONANDO ACESSOS NO PERÍODO
	$sql = "
	SELECT
	tbl_admin.admin,
	tbl_admin.login,
	COUNT(log_programa.programa) AS acessos
	FROM
	tbl_admin
	JOIN log_programa ON tbl_admin.admin=log_programa.admin
	WHERE
	tbl_admin.fabrica=$login_fabrica
	AND log_programa.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
	GROUP BY
	tbl_admin.admin,
	tbl_admin.login
	ORDER BY
	tbl_admin.login	";

	@$res = pg_exec($con, $sql);

	//************************* HTML DA TELA *************************//
	$colunas = 4;
	
	if ($res)
	{
		echo "
		<table class=relprincipal align=center>
			<tr>
				<td colspan=$colunas class=reltitulo>
					$title
				</td>
			</tr>
			<tr>
				<td width=100 class=relcabecalho>
					USUÁRIO
				</td>
				<td width=70 class=relcabecalho>
					ACESSOS
				</td>
				<td width=100 class=relcabecalho>
					ÚLTIMO ACESSO
				</td>
				<td width=380 class=relcabecalho>
					ÚLTIMO LINK ACESSADO
				</td>
			</tr>";

		for($i = 0; $i < pg_numrows($res); $i++)
		{
			$linha = $i % 2;

			//SELECIONANDO ÚLTIMO LINK E ÚLTIMO ACESSO
			$admin = pg_result($res, $i, admin);
			$login = pg_result($res, $i, login);
			$acessos = pg_result($res, $i, acessos);

			$sql = "SELECT MAX(data) FROM log_programa WHERE admin=$admin";
			$res_ultimo = pg_exec($con, $sql);
			$ultima_data = pg_result($res_ultimo, 0, 0);

			$sql = "
			SELECT
			programa
			FROM
			log_programa
			WHERE
			admin=$admin
			AND data='$ultima_data'
			";
			$res_ultimo = pg_exec($con, $sql);
			$ultimo_programa = pg_result($res_ultimo, 0, 0);
			$parts = explode(" ", $ultima_data);
			$parts[0] = implode("/", array_reverse(explode("-", $parts[0])));
			$parts[1] = explode(".", $parts[1]);
			$parts[1] = $parts[1][0];
			$ultima_data = $parts[0] . " " . $parts[1];

			echo "
			<tr class=rellinha$linha>
				<td>
					$login
				</td>
				<td>
					$acessos
				</td>
				<td>
					$ultima_data
				</td>
				<td>
					$ultimo_programa
				</td>
			</tr>";
		}

		echo "
		</table>";

		//************************* FIM HTML DA TELA *************************//
	}
	else
	{
		echo "
		<div class=relerro>
		Nenhuma acesso encontrado para as datas informadas
		</div>";
	}
}
} //IF strlen($data_geracao) > 0)
?>
