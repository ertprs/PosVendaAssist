<?php 	
	$classificacao 	= $_GET["classificacao"];
	$clique 		= $_GET['clique'];

	switch ($classificacao) {
		case 'P1':
			$sigla = "P1";
			$descricao = "Prioridade 1 - Cr�tico";
			$tempoSolucao = "2 a 4 horas corridas app�s o registro da chamada";
			$aplicabilidade = "Os n�veis de chamados P1 s�o os mais urgentes e cr�ticos que precisar�o de medidas imediatas a fim de evitar preju�zos financeiros aos neg�cios.	Este n�vel de chamado ser� definido sempre que houver falha operacional do sistema da
				CONTRATADA e/ou dos integradores por ela utilizados. Considerando o modelo dos neg�cios da
				CONTRATANTE, estes chamados que afetam a entrada de pedidos e/ou faturamentos dever�o ser
				solucionados entre 2 (duas) a 4 (quatro) horas corridas.
				A prioridade deste atendimento � evitar qualquer dano ao processo de entrada de pedidos e
				faturamentos.";
			$abrangencia1 = "1. Falha operacional do sistema onde n�o seja poss�vel digitar os pedidos de vendas; <br>
			2. Falha na integra��o dos sistemas por motivo de erros no sistema da CONTRATADA e/ou no integrador por ela utilizado; <Br>
			3. Falha na seguran�a do sistema; <br>
			4. Instabilidade operacional do sistema apresentando quedas dr�sticas de performance e/ou quedas constantes do banco de dados e/ou aplica��o.";

		break;
		
		case 'P2':
			$sigla = "P2";
			$descricao = "Prioridade 2 - Alta";
			$tempoSolucao = "1 dia �til ap�s o registro da chamada";
			$aplicabilidade = "Os n�veis de chamados P2 ser�o aplicados aos eventos que n�o afetem o
faturamento de forma cr�tica, mas que podem afetar negativamente a entrada de pedidos, ou
administra��o de pedidos pelos gestores de neg�cios da CONTRATANTE.
Este n�vel de chamado ser� definido sempre que houver falha operacional do sistema que inviabilize
a administra��o das informa��es e/ou tomada de decis�es pelos gestores da CONTRATANTE.
A prioridade deste atendimento � evitar qualquer dano ao processo de entrada de pedidos, an�lise
das informa��es de vendas e/ou faturamento pela equipe de neg�cios da CONTRATANTE.";
			$abrangencia1 = "";			
		break;
		
		case 'P3':
			$sigla = "P3";
			$descricao = "Prioridade 3 - M�dia";
			$tempoSolucao = "3 business days ap�s o registro da chamada";
			$aplicabilidade = "Os n�veis de chamados P3 ser�o aplicados aos eventos relacionados a erros de baixo impacto em telas e/ou relat�rios diversos e que n�o tenham impacto na entrada de pedidos e/ou faturamentos.
				Este n�vel de chamado ser� definido sempre que houver falha operacional do sistema que inviabilize a administra��o do site, o uso de telas e relat�rios diversos que n�o sejam de impacto na entrada de pedidos e faturamentos, na cria��o e/ou manuten��o de usu�rios,"; 
		break;
		
		case 'P4':
			$sigla = "P4";
			$descricao = "Prioridade 4 - Baixa";
			$tempoSolucao = "5 business days ou um prazo acordado entre as partes. Ser� contado a partir do registro inicial da chamada.";
			$aplicabilidade = "Os n�veis de chamados P4 ser�o aplicados �s solicita��es de melhoria do sistema e/ou novas demandas. <br> 
				Este n�vel de chamado ser� definido sempre que houver necessidade de alterar e/ou implementar melhorias.";			
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
		<td><b>Descri��o</b></td>
		<td><?=$descricao?></td>
	</tr>
	<tr class="fundo">
		<td><b>Tempo Solu��o</b></td>
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
		<td><b>Abrang�ncia</b></td>
		<td><b>Entrada de Pedidos / Cr�tico para Faturamento</b></td>
	</tr>
	<tr>
		<td colspan="2">
				1. Falha operacional do sistema onde n�o seja poss�vel digitar os pedidos de vendas; <br>
				2. Falha na integra��o dos sistemas por motivo de erros no sistema da CONTRATADA e/ou no integrador por ela utilizado; <Br>
				3. Falha na seguran�a do sistema; <br>
				4. Instabilidade operacional do sistema apresentando quedas dr�sticas de performance e/ou quedas constantes do banco de dados e/ou aplica��o.
			<br>
		</td>
	</tr>
<?php } 
	if($classificacao == 'P2'){ ?>
		<tr>
			<td colspan="2"><b>a) Abrang�ncia: Entrada de Pedidos / n�o cr�tico para Faturamento:</b></td>
		</tr>
		<tr>
			<td colspan="2">1. Falha operacional do sistema onde n�o seja poss�vel digitar os pedidos de vendas; <br>
				2. Falha na integra��o dos sistemas por motivo de erros no sistema da CONTRATADA e/ou no integrador por ela utilizado; <br>
				3.Falha na seguran�a do sistema; <br>
				4. Instabilidade operacional do sistema apresentando quedas dr�sticas de performance e/ou quedas constantes do banco de dados e/ou aplica��o.

			</td>
		</tr>
		<tr>
			<td colspan="2"><b>b) Abrang�ncia Disponibilidade de Informa��es Gerenciais:</b></td>
		</tr>
		<tr>
			<td colspan='2'>1. Falha operacional do sistema onde n�o seja poss�vel a extra��o e/ou an�lise das informa��es de vendas e/ou faturamentos; <Br>
2. Falha no conte�do das informa��es gerenciais por erro no sistema e/ou de falha de
integridade; <br>
3. Instabilidade operacional do sistema apresentando quedas dr�sticas de performance e/ou
quedas constantes do banco de dados e/ou aplica��o</td>
		</tr>
	
	<?php } if($classificacao == 'P3'){ ?> 
		<tr>
			<td colspan="2"><b>Abrang�ncia Disponibilidade de Rotinas Diversas:</b></td>
		</tr>
		<tr>
			<td colspan="2">1. Falha operacional nas rotinas de uso administrativo do sistema - cria��o/manuten��o de usu�rios, parametriza��o de seguran�a e acessos de usu�rios existentes, etc.; <br>
				2. Falha operacional nas rotinas diversas do sistema que n�o afetem a entrada de pedidos e/ou faturamentos, sendo elas: relat�rios de apoio de vendas, telas de manuten��es diversas, etc.; <Br>
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