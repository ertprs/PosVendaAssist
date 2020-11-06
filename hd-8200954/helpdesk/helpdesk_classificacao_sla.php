<?php 	
	$classificacao 	= $_GET["classificacao"];
	$clique 		= $_GET['clique'];

	switch ($classificacao) {
		case 'P1':
			$sigla = "P1";
			$descricao = "Prioridade 1 - Crítico";
			$tempoSolucao = "2 a 4 horas corridas appós o registro da chamada";
			$aplicabilidade = "Os níveis de chamados P1 são os mais urgentes e críticos que precisarão de medidas imediatas a fim de evitar prejuízos financeiros aos negócios.	Este nível de chamado será definido sempre que houver falha operacional do sistema da
				CONTRATADA e/ou dos integradores por ela utilizados. Considerando o modelo dos negócios da
				CONTRATANTE, estes chamados que afetam a entrada de pedidos e/ou faturamentos deverão ser
				solucionados entre 2 (duas) a 4 (quatro) horas corridas.
				A prioridade deste atendimento é evitar qualquer dano ao processo de entrada de pedidos e
				faturamentos.";
			$abrangencia1 = "1. Falha operacional do sistema onde não seja possível digitar os pedidos de vendas; <br>
			2. Falha na integração dos sistemas por motivo de erros no sistema da CONTRATADA e/ou no integrador por ela utilizado; <Br>
			3. Falha na segurança do sistema; <br>
			4. Instabilidade operacional do sistema apresentando quedas drásticas de performance e/ou quedas constantes do banco de dados e/ou aplicação.";

		break;
		
		case 'P2':
			$sigla = "P2";
			$descricao = "Prioridade 2 - Alta";
			$tempoSolucao = "1 dia útil após o registro da chamada";
			$aplicabilidade = "Os níveis de chamados P2 serão aplicados aos eventos que não afetem o
faturamento de forma crítica, mas que podem afetar negativamente a entrada de pedidos, ou
administração de pedidos pelos gestores de negócios da CONTRATANTE.
Este nível de chamado será definido sempre que houver falha operacional do sistema que inviabilize
a administração das informações e/ou tomada de decisões pelos gestores da CONTRATANTE.
A prioridade deste atendimento é evitar qualquer dano ao processo de entrada de pedidos, análise
das informações de vendas e/ou faturamento pela equipe de negócios da CONTRATANTE.";
			$abrangencia1 = "";			
		break;
		
		case 'P3':
			$sigla = "P3";
			$descricao = "Prioridade 3 - Média";
			$tempoSolucao = "3 business days após o registro da chamada";
			$aplicabilidade = "Os níveis de chamados P3 serão aplicados aos eventos relacionados a erros de baixo impacto em telas e/ou relatórios diversos e que não tenham impacto na entrada de pedidos e/ou faturamentos.
				Este nível de chamado será definido sempre que houver falha operacional do sistema que inviabilize a administração do site, o uso de telas e relatórios diversos que não sejam de impacto na entrada de pedidos e faturamentos, na criação e/ou manutenção de usuários,"; 
		break;
		
		case 'P4':
			$sigla = "P4";
			$descricao = "Prioridade 4 - Baixa";
			$tempoSolucao = "5 business days ou um prazo acordado entre as partes. Será contado a partir do registro inicial da chamada.";
			$aplicabilidade = "Os níveis de chamados P4 serão aplicados às solicitações de melhoria do sistema e/ou novas demandas. <br> 
				Este nível de chamado será definido sempre que houver necessidade de alterar e/ou implementar melhorias.";			
		break;
	}

?>
<link href="../plugins/bootstrap3/css/bootstrap.min.css" rel="stylesheet">
<link href="../plugins/bootstrap3/css/bootstrap-theme.min.css" rel="stylesheet">
    
                
<style>
	body{
		font-family:Verdana, Geneva, sans-serif;
		height: auto;
		padding: 5px;
	}
	td{
		padding:2px;
	}
	.fundo{
		background-color: #E8E8E8;
	}
	.aplicabilidade{
		text-align: justify;
	}
</style>
<table  cellspacing="0" cellpadding="3" style="background-color: white;">
	<tr class="fundo">
		<td width="170"><b>Sigla</b></td>
		<td><?=$sigla?></td>
	</tr>
	<tr class="fundo">
		<td><b>Descrição</b></td>
		<td><?=$descricao?></td>
	</tr>
	<tr class="fundo">
		<td><b>Tempo Solução</b></td>
		<td><?=$tempoSolucao?></td>
	</tr>
	<tr>
		<td><b> Aplicabilidade </b></td>
		<td></td>
	</tr>
	<tr>
		<td colspan="2" class='aplicabilidade'><?=$aplicabilidade?></td>
	</tr>
	<?php if($classificacao == 'P1'){ ?>
	<tr>
		<td><b>Abrangência</b></td>
		<td><b>Entrada de Pedidos / Crítico para Faturamento</b></td>
	</tr>
	<tr>
		<td colspan="2">
				1. Falha operacional do sistema onde não seja possível digitar os pedidos de vendas; <br>
				2. Falha na integração dos sistemas por motivo de erros no sistema da CONTRATADA e/ou no integrador por ela utilizado; <Br>
				3. Falha na segurança do sistema; <br>
				4. Instabilidade operacional do sistema apresentando quedas drásticas de performance e/ou quedas constantes do banco de dados e/ou aplicação.
			<br>
		</td>
	</tr>
<?php } 
	if($classificacao == 'P2'){ ?>
		<tr>
			<td colspan="2"><b>a) Abrangência: Entrada de Pedidos / não crítico para Faturamento:</b></td>
		</tr>
		<tr>
			<td colspan="2">1. Falha operacional do sistema onde não seja possível digitar os pedidos de vendas; <br>
				2. Falha na integração dos sistemas por motivo de erros no sistema da CONTRATADA e/ou no integrador por ela utilizado; <br>
				3.Falha na segurança do sistema; <br>
				4. Instabilidade operacional do sistema apresentando quedas drásticas de performance e/ou quedas constantes do banco de dados e/ou aplicação.

			</td>
		</tr>
		<tr>
			<td colspan="2"><b>b) Abrangência Disponibilidade de Informações Gerenciais:</b></td>
		</tr>
		<tr>
			<td colspan='2'>1. Falha operacional do sistema onde não seja possível a extração e/ou análise das informações de vendas e/ou faturamentos; <Br>
2. Falha no conteúdo das informações gerenciais por erro no sistema e/ou de falha de
integridade; <br>
3. Instabilidade operacional do sistema apresentando quedas drásticas de performance e/ou
quedas constantes do banco de dados e/ou aplicação</td>
		</tr>
	
	<?php } if($classificacao == 'P3'){ ?> 
		<tr>
			<td colspan="2"><b>Abrangência Disponibilidade de Rotinas Diversas:</b></td>
		</tr>
		<tr>
			<td colspan="2">1. Falha operacional nas rotinas de uso administrativo do sistema - criação/manutenção de usuários, parametrização de segurança e acessos de usuários existentes, etc.; <br>
				2. Falha operacional nas rotinas diversas do sistema que não afetem a entrada de pedidos e/ou faturamentos, sendo elas: relatórios de apoio de vendas, telas de manutenções diversas, etc.; <Br>
				3. Melhorias no sistema que beneficiem a entrada de pedidos e/ou faturamento, bem como, que estas melhorias possam beneficiar a produtividade da equipe e do processo de entrada de pedidos e/ou faturamentos
			</td>
		</tr>
	<?php } ?>


</table>
<br>
<?php if($clique == true){?>
<div style='text-align: center'>
	<a href="#" class="voltar btn btn-primary">Voltar</a>
<div>
<?php } ?>