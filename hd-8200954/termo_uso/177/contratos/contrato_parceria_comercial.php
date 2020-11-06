<?php

/*require __DIR__ . '/../../../dbconfig.php';
require __DIR__ . '/../../../includes/dbconnect-inc.php';*/

$query = "SELECT nome
		  FROM   tbl_posto
		  WHERE  posto = $login_posto";

$postoInfo = pg_query($con, $query);
$postoInfo = pg_fetch_object($postoInfo);

function formataDataHoje() {
    $hoje = new DateTime();
    $mes = "";

    switch ($hoje->format('m')) {
        case '01':
            $mes = " de Janeiro de ";
            break;
        case '02':
            $mes = " de Fevereiro de ";
            break;
        case '03':
            $mes = " de Mar�o de ";
            break;
        case '04':
            $mes = " de Abril de ";
            break;
        case '05':
            $mes = " de Maio de ";
            break;
        case '06':
            $mes = " de Junho de ";
            break;
        case '07':
            $mes = " de Julho de ";
            break;
        case '08':
            $mes = " de Agosto de ";
            break;
        case '09':
            $mes = " de Setembro de ";
            break;
        case '10':
            $mes = " de Outubro de ";
            break;
        case '11':
            $mes = " de Novembro de ";
            break;
        case '12':
            $mes = " de Dezembro de ";
            break;
    }

    return $hoje->format("d").$mes.$hoje->format("Y");
}

$content = '
<style type="text/css">
	
	.center {
		text-align: center;
	}

	.paragrafo-direita {
		margin-left: 13%;
	}

	.paragrafo-esquerda {
		margin-left: 10%;
	}

	.objetos {
		margin-left: 10%;
	}

	.text-center {
		text-align: center;
	}

	p { text-align: justify; }

</style>
<div class="container">
	<br>
	<div class="center">
		<strong>CONTRATO DE PARCERIA EMPRESARIAL</strong>
	</div>
	<br>
	<div class="">
		Pelo presente instrumento, de um lado: 
	</div>
	<br>
	<div class="paragrafo-direita text-center">
		<p><strong>INDUSTRIA DE MOTORES ANAUGER S.A.</strong>, sociedade empres�ria por disposi��o 
		legal, regularmente constitu�da e neste ato representada por seus Diretores conforme     
		estatuto social vigente, empresa com sede na Rua Prefeito Jos� Carlos, 2555, bairro 	
		Santa Julia, CEP�13295-000, na cidade de Itupeva, Estado de S�o Paulo, inscrita no 	    
		CNPJ sob n� 59.134.635/0001-24, doravante denominada simplesmente ANAUGER; e, 			
		de outro lado
		</p>
	</div>

	<div class="paragrafo-direita">
		' . $postoInfo->nome . ', devidamente qualificada via sistema Telecontrol;
	</div>
	<br>
	
	<p>
		Resolvem celebrar o presente CONTRATO DE PARCERIA EMPRESARIAL, que se reger� pelas cl�usulas e condi��es a seguir, de acordo com a legisla��o aplic�vel, tudo em conformidade com o disposto abaixo que, mutuamente, outorgam, pactuam e aceitam, obrigando-se a cumpri-las por si e por seus herdeiros 
		e sucessores, a qualquer t�tulo:
	</p>

	<strong>1 � OBJETO DO CONTRATO</strong>
	<br><br>
	<div class="objetos">
		<p>1.1 � Constitui objeto deste, a parceria empresarial para presta��o de servi�o de assist�ncia 
		t�cnica, pela ' . $postoInfo->nome . ', na qualidade de Posto de Servi�o Autorizado Anauger, para             
		manuten��o corretiva dos equipamentos de fabrica��o da ANAUGER que estejam dentro             
		do per�odo de garantia (os �Servi�os de Assist�ncia T�cnica�).<p>
	</div>
	<div class="objetos">
		<p>1.2 � Em contrapartida aos Servi�os de Assist�ncia T�cnica realizados, a ANAUGER conceder� � ' . $postoInfo->nome . ' determinados benef�cios e/ou descontos nas pe�as que a ' . $postoInfo->nome . ' vier a adquirir para realiza��o de manuten��o corretiva de equipamentos de fabrica��o da ANAUGER fora de garantia.<p>
	</div>
	<div class="objetos">
		1.2.1. Consideram-se benef�cios: <br>
	</div>
	<div class="paragrafo-direita">
		<div class="row">
			<div class="col">
				<p>a) Descontos especiais, conforme tabelas vigentes, aplic�veis nas pe�as que a ' . $postoInfo->nome . ' vier a adquirir para realiza��o de manuten��o corretiva de equipamentos de fabrica��o pr�pria da ANAUGER fora de garantia;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<p>b) Pagamento de m�o de obra por Ordem de Servi�o finalizada em garantia, dentro do sistema Telecontrol, pagamento este que ser� realizado por meio de bonifica��o de pe�as escolhidas pelo PSA;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<p>c) Divulga��o permanente do PSA nos canais de comunica��o da ANAUGER, sem qualquer �nus para aquele; e
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col"> 
				<p>d) Canal direto com a f�brica da ANAUGER para suporte t�cnico e burocr�tico que 	garantam o bom andamento das atividades desempenhadas junto � ANAUGER.
				</p>
			</div>
		</div>
	</div>
	<br>

	<strong> 2 � CONDI��ES GERAIS PARA ATENDIMENTO </strong>
	<br><br>

	<div class="objetos">
		<p>2.1 � Os Servi�os de Assist�ncia T�cnica em garantia somente poder�o ser realizados mediante o pr�vio lan�amento de solicita��o de acesso � garantia (�Ordens de Servi�o�) pela ' . $postoInfo->nome . ', sujeita a pr�via e expressa aprova��o pela ANAUGER em sistema especifico (plataforma Telecontrol).</p>
	</div>
	<div class="objetos">
		<p>2.1.1 � Somente ap�s aprova��o da Ordem de Servi�o pela ANAUGER, em at� 3 (tr�s) dias �teis, via plataforma Telecontrol, � que a ' . $postoInfo->nome . ' poder� realizar os Servi�os de Assist�ncia T�cnica. </p>
	</div>
	<div class="objetos">
		<p>2.2 � A realiza��o, pela ' . $postoInfo->nome . ', de Servi�os de Assist�ncia T�cnica em garantia sem aprova��o da Ordem de Servi�o implicar� para a ' . $postoInfo->nome . ' a suspens�o tempor�ria de seu cadastro como Posto de Servi�o Autorizado Anauger, somente resgatando-o mediante a inclus�o do respectivo lan�amento na plataforma, o que dever� ser comunicado pr�via e expressamente � ANAUGER, por meio id�neo e com comprova��o de recebimento.</p>
	</div>
	<div class="objetos">
		<p>2.3 � O tempo m�ximo para manuten��o dos reparos constantes de Ordens de Servi�o aprovadas pela CONTRATANTE ser� de 30 (trinta) dias, contados da data do recebimento do produto pela ' . $postoInfo->nome . '.</p>
	</div>

	<strong> 3 � RESPONSABILIDADES DA PARCEIRA </strong>
	<br><br>
	<div class="objetos">
		<p>3.1 � A ' . $postoInfo->nome . ' obriga-se a utilizar somente pe�as originais da ANAUGER nos Servi�os de Assist�ncia T�cnica, bem como a manter uma equipe de t�cnicos de efici�ncia e capacita��o comprovada e em n�mero compat�vel com o movimento de consertos da oficina, a fim de, assim, garantir o bom n�vel dos Servi�os de Assist�ncia T�cnica.</p>
	</div>
	<div class="objetos">
		<p>3.2 � A ' . $postoInfo->nome . ' dever� realizar as manuten��es mediante uso de ferramentas adequadas ou indicadas pela ANAUGER, abstendo-se de promover qualquer altera��o nas caracter�sticas originais dos produtos. Ainda, a ' . $postoInfo->nome . ' dever�, sob sua exclusiva responsabilidade, desfazer e refazer ou corrigir os Servi�os de Assist�ncia T�cnica defeituosos ou que tenham sido por ela prestados com erro ou imperfei��o t�cnica ou, ainda, por emprego de processos ou materiais inadequados.</p>
	</div>
	<div class="objetos">
		<p>3.3 � A ' . $postoInfo->nome . ' dever� alimentar o sistema Telecontrol, de maneira a registrar todos os atendimentos prestados em fun��o desta Parceria, e toda a documenta��o a eles atrelada, tais como, mas n�o se limitando a, laudos, registros fotogr�ficos, notas fiscais, comprovante de acesso a garantia, durante toda a vig�ncia desta Parceria e pelo per�odo de 2 (dois) anos a contar de seu t�rmino ou rescis�o.</p>
	</div>
	<div class="objetos">
		<p>3.4 � � proibido � ' . $postoInfo->nome . ', sob qualquer pretexto, a �montagem de produtos� da linha da ANAUGER, seja com pe�as originais ou n�o da ANAUGER, com o objetivo de comercializa��o no mercado, o que ser� caracterizado como concorr�ncia com a ind�stria.</p>
	</div>
	<div class="objetos">
		<p>3.5 � A ' . $postoInfo->nome . ' compromete-se a cobrar, pelos servi�os e pe�as que empregar em consertos de equipamentos da ANAUGER fora de garantia, pre�os compat�veis com as pr�ticas de mercado, com o objetivo de salvaguardar o direito dos consumidores no que diz respeito ao abuso de pre�os.</p>
	</div>
	<div class="objetos">
		<p>3.6 � Ressalvado o disposto no item 4.1 adiante, a ' . $postoInfo->nome . ' responder� regressivamente perante a ANAUGER em qualquer a��o que esta venha a responder perante terceiros, em decorr�ncia de obriga��es que, por for�a do presente Contrato, couberem � ' . $postoInfo->nome . ' observar, diligenciar, cumprir e/ou honrar, obrigando-se a ' . $postoInfo->nome . ' a aceitar a sua denuncia��o � lide.</p>
	</div>

	<strong> 4 � RESPONSABILIDADES DA ANAUGER </strong>
	<br><br>
	<div class="objetos">
		<p>4.1 � A ANAUGER assumir� a responsabilidade junto ao PROCON ou em a��es judiciais cujo objeto sejam discuss�es relacionadas a manuten��o e assist�ncia t�cnica de produtos da ANAUGER em per�odo de garantia, caso a ' . $postoInfo->nome . ' tenha cumprido, cumulativamente, o quanto segue: </p>
	</div>
	<div class="paragrafo-direita">
		<div class="row">
			<div class="col-11">
				<p>4.1.1 � Lan�ar no sistema Telecontrol as solicita��es de acesso a garantia e toda a documenta��o a elas atrelada, tais como, mas n�o se limitando a, laudos, registros fotogr�ficos, notas fiscais, comprovante de acesso a garantia, observando-se o disposto em 3.3, acima, e somente realizar os Servi�os de Assist�ncia T�cnica mediante aprova��o da Ordem de Servi�o pela ANAUGER;</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>4.1.2 � Preencher corretamente todos os dados de cadastro do cliente solicitados no sistema Telecontrol;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p> 4.1.3 � Fazer registro fotogr�fico do produto e lan�ar no sistema; e
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>4.1.4 � Realizar a manuten��o corretiva objeto desta Parceria dentro do prazo legal estabelecido pelo C�digo de Defesa do Consumidor.
				</p>
			</div>
		</div>
	</div>
	<div class="objetos">
		<p>4.2 � A ANAUGER promover� treinamento no estabelecimento da PARCEIRA ou � dist�ncia, a seu exclusivo crit�rio, a fim de qualificar a ' . $postoInfo->nome . ' para prestar os Servi�os de Assist�ncia T�cnica em seus produtos (da ANAUGER).
		</p>
	</div>
	<div class="objetos">
		<p>4.3 - A ANAUGER disponibilizar� rela��o de pe�as de reposi��o, completa e atualizada, dos produtos com os quais a ' . $postoInfo->nome . ' estar� habilitada a prestar os Servi�os de Assist�ncia T�cnica. 
		</p>
	</div>

	<strong> 5 � VIG�NCIA DO CONTRATO </strong>
	<br><br>
	<div class="objetos">
		<p>5.1 - O presente contrato tem validade de 12 (doze) meses, a partir da data de assinatura pelas Partes, que poder� se dar, inclusive, por meio eletr�nico ou digital, podendo ser rescindido imotivadamente a qualquer tempo por qualquer das partes, mediante comunica��o escrita com anteced�ncia de 30 (trinta) dias.
		</p>
	</div>
	<div class="objetos">
		<p>5.2 � Este contrato ser� automaticamente prorrogado por prazo iguais e sucessivos de 12 (doze) meses, salvo em caso de manifesta��o expressa de qualquer das partes em contr�rio com, pelo menos, 60 (sessenta) dias de anteced�ncia de cada termo final.</p>
	</div>
	<div class="objetos">
		<p>5.3 � Ainda, este Contrato poder� ser rescindido de pleno direito, por qualquer das partes, mediante simples comunica��o por escrito, nos seguintes casos:</p>
	</div>
	<div class="paragrafo-direita">
		<div class="row">
			<div class="col-11">
				<p>5.3.1 � Pedido ou proposi��o de recupera��o judicial ou extrajudicial; decreta��o ou homologa��o de fal�ncia; ou qualquer outra forma que caracterize impossibilidade de adimplemento contratual por qualquer das partes; 
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>5.3.2 � Descumprimento de quaisquer das obriga��es contratuais n�o sanado 5 (cinco) dias ap�s o recebimento, pela parte contr�ria, de notifica��o escrita apontando a condi��o desrespeitada, sem que a parte infratora sane, neste prazo, as irregularidades notificadas;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>5.3.3 � Descumprimento de determina��es legais.
				</p>
			</div>
		</div>
	</div>
	<strong> 6 � DISPOSI��ES GERAIS E FORO </strong>
	<br><br>
	
	<div class="objetos">
		<p>6.1 - As partes s�o total e absolutamente independentes, n�o se estabelecendo v�nculo associativo, societ�rio, trabalhista ou de qualquer esp�cie. Ainda, fica expressamente estipulado que n�o se estabelece, em raz�o deste Contrato, v�nculo empregat�cio de qualquer natureza entre a ANAUGER e o pessoal utilizado pela ' . $postoInfo->nome . ' para execu��o dos Servi�os de Assist�ncia T�cnica, obrigando-se esta por todos os correspondentes encargos trabalhistas, previdenci�rios, fundi�rios e quaisquer outros.</p>
	</div>
	<div class="objetos">
		<p>6.2 - A eventual toler�ncia de uma das partes com o descumprimento de qualquer obriga��o contratual ser� considerada mera liberalidade, n�o implicando transa��o, nova��o ou ren�ncia, de modo que a parte inocente pode, a qualquer tempo, exigir da parte culpada o integral cumprimento desta obriga��o.</p>
	</div>
	<div class="objetos">
		<p>6.3 � As partes concordam que as p�ginas do presente contrato e de seus eventuais anexos, todas formadas por meio digital com o qual expressamente declaram concordar, representam a consolida��o final das negocia��es havidas entre as partes, de forma que, com a sua assinatura, restam anulados e substitu�dos todos e quaisquer documentos e tratativas havidas anteriormente entre as partes, formalizados por qualquer outro meio, verbal ou escrito, f�sico ou digital, ficando v�lidos, para todos os fins e efeitos de direito, apenas e t�o somente o presente instrumento e seus anexos.</p>
	</div>
	<div class="objetos">
		<p>6.4 � Nos termos do art. 10, � 2�, da Medida Provis�ria n� 2.200-2, as partes expressamente concordam em utilizar e reconhecem como v�lida qualquer forma de comprova��o de anu�ncia aos termos ora acordados em formato eletr�nico, incluindo assinaturas eletr�nicas, ainda que n�o utilizem de certificado digital emitido no padr�o ICP-Brasil. A formaliza��o desta parceria da maneira ora acordada ser� suficiente para a validade e integral vincula��o das partes ao presente Contrato.</p>
	</div>
	<div class="objetos">
		<p>6.5 � Para todos os fins de direito, o presente Instrumento ser� considerado t�tulo executivo extrajudicial, nos termos do artigo 784, inciso III, do C�digo de Processo Civil � Lei n.� 13.105/2015. </p>
	</div>
	<div class="objetos">
		<p>6.6 � Fica eleito o Foro da Cidade de Itupeva-SP, com expressa exclus�o de qualquer outro, por mais privilegiado que seja, para nele serem dirimidas quaisquer quest�es oriundas do presente contrato ou sua execu��o.</p>
	</div>

	<p>E, por estarem assim justas e contratadas, firmam o presente instrumento em duas vias de igual teor e forma, perante as testemunhas abaixo.</p>

	<p>Itupeva, 03 de junho de 2019. </p>

</div>';

?>