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
            $mes = " de Março de ";
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
		<p><strong>INDUSTRIA DE MOTORES ANAUGER S.A.</strong>, sociedade empresária por disposição 
		legal, regularmente constituída e neste ato representada por seus Diretores conforme     
		estatuto social vigente, empresa com sede na Rua Prefeito José Carlos, 2555, bairro 	
		Santa Julia, CEP–13295-000, na cidade de Itupeva, Estado de São Paulo, inscrita no 	    
		CNPJ sob nº 59.134.635/0001-24, doravante denominada simplesmente ANAUGER; e, 			
		de outro lado
		</p>
	</div>

	<div class="paragrafo-direita">
		' . $postoInfo->nome . ', devidamente qualificada via sistema Telecontrol;
	</div>
	<br>
	
	<p>
		Resolvem celebrar o presente CONTRATO DE PARCERIA EMPRESARIAL, que se regerá pelas cláusulas e condições a seguir, de acordo com a legislação aplicável, tudo em conformidade com o disposto abaixo que, mutuamente, outorgam, pactuam e aceitam, obrigando-se a cumpri-las por si e por seus herdeiros 
		e sucessores, a qualquer título:
	</p>

	<strong>1 – OBJETO DO CONTRATO</strong>
	<br><br>
	<div class="objetos">
		<p>1.1 – Constitui objeto deste, a parceria empresarial para prestação de serviço de assistência 
		técnica, pela ' . $postoInfo->nome . ', na qualidade de Posto de Serviço Autorizado Anauger, para             
		manutenção corretiva dos equipamentos de fabricação da ANAUGER que estejam dentro             
		do período de garantia (os “Serviços de Assistência Técnica”).<p>
	</div>
	<div class="objetos">
		<p>1.2 – Em contrapartida aos Serviços de Assistência Técnica realizados, a ANAUGER concederá à ' . $postoInfo->nome . ' determinados benefícios e/ou descontos nas peças que a ' . $postoInfo->nome . ' vier a adquirir para realização de manutenção corretiva de equipamentos de fabricação da ANAUGER fora de garantia.<p>
	</div>
	<div class="objetos">
		1.2.1. Consideram-se benefícios: <br>
	</div>
	<div class="paragrafo-direita">
		<div class="row">
			<div class="col">
				<p>a) Descontos especiais, conforme tabelas vigentes, aplicáveis nas peças que a ' . $postoInfo->nome . ' vier a adquirir para realização de manutenção corretiva de equipamentos de fabricação própria da ANAUGER fora de garantia;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<p>b) Pagamento de mão de obra por Ordem de Serviço finalizada em garantia, dentro do sistema Telecontrol, pagamento este que será realizado por meio de bonificação de peças escolhidas pelo PSA;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<p>c) Divulgação permanente do PSA nos canais de comunicação da ANAUGER, sem qualquer ônus para aquele; e
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col"> 
				<p>d) Canal direto com a fábrica da ANAUGER para suporte técnico e burocrático que 	garantam o bom andamento das atividades desempenhadas junto à ANAUGER.
				</p>
			</div>
		</div>
	</div>
	<br>

	<strong> 2 – CONDIÇÕES GERAIS PARA ATENDIMENTO </strong>
	<br><br>

	<div class="objetos">
		<p>2.1 – Os Serviços de Assistência Técnica em garantia somente poderão ser realizados mediante o prévio lançamento de solicitação de acesso à garantia (“Ordens de Serviço”) pela ' . $postoInfo->nome . ', sujeita a prévia e expressa aprovação pela ANAUGER em sistema especifico (plataforma Telecontrol).</p>
	</div>
	<div class="objetos">
		<p>2.1.1 – Somente após aprovação da Ordem de Serviço pela ANAUGER, em até 3 (três) dias úteis, via plataforma Telecontrol, é que a ' . $postoInfo->nome . ' poderá realizar os Serviços de Assistência Técnica. </p>
	</div>
	<div class="objetos">
		<p>2.2 – A realização, pela ' . $postoInfo->nome . ', de Serviços de Assistência Técnica em garantia sem aprovação da Ordem de Serviço implicará para a ' . $postoInfo->nome . ' a suspensão temporária de seu cadastro como Posto de Serviço Autorizado Anauger, somente resgatando-o mediante a inclusão do respectivo lançamento na plataforma, o que deverá ser comunicado prévia e expressamente à ANAUGER, por meio idôneo e com comprovação de recebimento.</p>
	</div>
	<div class="objetos">
		<p>2.3 – O tempo máximo para manutenção dos reparos constantes de Ordens de Serviço aprovadas pela CONTRATANTE será de 30 (trinta) dias, contados da data do recebimento do produto pela ' . $postoInfo->nome . '.</p>
	</div>

	<strong> 3 – RESPONSABILIDADES DA PARCEIRA </strong>
	<br><br>
	<div class="objetos">
		<p>3.1 – A ' . $postoInfo->nome . ' obriga-se a utilizar somente peças originais da ANAUGER nos Serviços de Assistência Técnica, bem como a manter uma equipe de técnicos de eficiência e capacitação comprovada e em número compatível com o movimento de consertos da oficina, a fim de, assim, garantir o bom nível dos Serviços de Assistência Técnica.</p>
	</div>
	<div class="objetos">
		<p>3.2 – A ' . $postoInfo->nome . ' deverá realizar as manutenções mediante uso de ferramentas adequadas ou indicadas pela ANAUGER, abstendo-se de promover qualquer alteração nas características originais dos produtos. Ainda, a ' . $postoInfo->nome . ' deverá, sob sua exclusiva responsabilidade, desfazer e refazer ou corrigir os Serviços de Assistência Técnica defeituosos ou que tenham sido por ela prestados com erro ou imperfeição técnica ou, ainda, por emprego de processos ou materiais inadequados.</p>
	</div>
	<div class="objetos">
		<p>3.3 – A ' . $postoInfo->nome . ' deverá alimentar o sistema Telecontrol, de maneira a registrar todos os atendimentos prestados em função desta Parceria, e toda a documentação a eles atrelada, tais como, mas não se limitando a, laudos, registros fotográficos, notas fiscais, comprovante de acesso a garantia, durante toda a vigência desta Parceria e pelo período de 2 (dois) anos a contar de seu término ou rescisão.</p>
	</div>
	<div class="objetos">
		<p>3.4 – É proibido à ' . $postoInfo->nome . ', sob qualquer pretexto, a “montagem de produtos” da linha da ANAUGER, seja com peças originais ou não da ANAUGER, com o objetivo de comercialização no mercado, o que será caracterizado como concorrência com a indústria.</p>
	</div>
	<div class="objetos">
		<p>3.5 – A ' . $postoInfo->nome . ' compromete-se a cobrar, pelos serviços e peças que empregar em consertos de equipamentos da ANAUGER fora de garantia, preços compatíveis com as práticas de mercado, com o objetivo de salvaguardar o direito dos consumidores no que diz respeito ao abuso de preços.</p>
	</div>
	<div class="objetos">
		<p>3.6 – Ressalvado o disposto no item 4.1 adiante, a ' . $postoInfo->nome . ' responderá regressivamente perante a ANAUGER em qualquer ação que esta venha a responder perante terceiros, em decorrência de obrigações que, por força do presente Contrato, couberem à ' . $postoInfo->nome . ' observar, diligenciar, cumprir e/ou honrar, obrigando-se a ' . $postoInfo->nome . ' a aceitar a sua denunciação à lide.</p>
	</div>

	<strong> 4 – RESPONSABILIDADES DA ANAUGER </strong>
	<br><br>
	<div class="objetos">
		<p>4.1 – A ANAUGER assumirá a responsabilidade junto ao PROCON ou em ações judiciais cujo objeto sejam discussões relacionadas a manutenção e assistência técnica de produtos da ANAUGER em período de garantia, caso a ' . $postoInfo->nome . ' tenha cumprido, cumulativamente, o quanto segue: </p>
	</div>
	<div class="paragrafo-direita">
		<div class="row">
			<div class="col-11">
				<p>4.1.1 – Lançar no sistema Telecontrol as solicitações de acesso a garantia e toda a documentação a elas atrelada, tais como, mas não se limitando a, laudos, registros fotográficos, notas fiscais, comprovante de acesso a garantia, observando-se o disposto em 3.3, acima, e somente realizar os Serviços de Assistência Técnica mediante aprovação da Ordem de Serviço pela ANAUGER;</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>4.1.2 – Preencher corretamente todos os dados de cadastro do cliente solicitados no sistema Telecontrol;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p> 4.1.3 – Fazer registro fotográfico do produto e lançar no sistema; e
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>4.1.4 – Realizar a manutenção corretiva objeto desta Parceria dentro do prazo legal estabelecido pelo Código de Defesa do Consumidor.
				</p>
			</div>
		</div>
	</div>
	<div class="objetos">
		<p>4.2 – A ANAUGER promoverá treinamento no estabelecimento da PARCEIRA ou à distância, a seu exclusivo critério, a fim de qualificar a ' . $postoInfo->nome . ' para prestar os Serviços de Assistência Técnica em seus produtos (da ANAUGER).
		</p>
	</div>
	<div class="objetos">
		<p>4.3 - A ANAUGER disponibilizará relação de peças de reposição, completa e atualizada, dos produtos com os quais a ' . $postoInfo->nome . ' estará habilitada a prestar os Serviços de Assistência Técnica. 
		</p>
	</div>

	<strong> 5 – VIGÊNCIA DO CONTRATO </strong>
	<br><br>
	<div class="objetos">
		<p>5.1 - O presente contrato tem validade de 12 (doze) meses, a partir da data de assinatura pelas Partes, que poderá se dar, inclusive, por meio eletrônico ou digital, podendo ser rescindido imotivadamente a qualquer tempo por qualquer das partes, mediante comunicação escrita com antecedência de 30 (trinta) dias.
		</p>
	</div>
	<div class="objetos">
		<p>5.2 – Este contrato será automaticamente prorrogado por prazo iguais e sucessivos de 12 (doze) meses, salvo em caso de manifestação expressa de qualquer das partes em contrário com, pelo menos, 60 (sessenta) dias de antecedência de cada termo final.</p>
	</div>
	<div class="objetos">
		<p>5.3 – Ainda, este Contrato poderá ser rescindido de pleno direito, por qualquer das partes, mediante simples comunicação por escrito, nos seguintes casos:</p>
	</div>
	<div class="paragrafo-direita">
		<div class="row">
			<div class="col-11">
				<p>5.3.1 – Pedido ou proposição de recuperação judicial ou extrajudicial; decretação ou homologação de falência; ou qualquer outra forma que caracterize impossibilidade de adimplemento contratual por qualquer das partes; 
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>5.3.2 – Descumprimento de quaisquer das obrigações contratuais não sanado 5 (cinco) dias após o recebimento, pela parte contrária, de notificação escrita apontando a condição desrespeitada, sem que a parte infratora sane, neste prazo, as irregularidades notificadas;
				</p>
			</div>
		</div>
		<div class="row">
			<div class="col-11">
				<p>5.3.3 – Descumprimento de determinações legais.
				</p>
			</div>
		</div>
	</div>
	<strong> 6 – DISPOSIÇÕES GERAIS E FORO </strong>
	<br><br>
	
	<div class="objetos">
		<p>6.1 - As partes são total e absolutamente independentes, não se estabelecendo vínculo associativo, societário, trabalhista ou de qualquer espécie. Ainda, fica expressamente estipulado que não se estabelece, em razão deste Contrato, vínculo empregatício de qualquer natureza entre a ANAUGER e o pessoal utilizado pela ' . $postoInfo->nome . ' para execução dos Serviços de Assistência Técnica, obrigando-se esta por todos os correspondentes encargos trabalhistas, previdenciários, fundiários e quaisquer outros.</p>
	</div>
	<div class="objetos">
		<p>6.2 - A eventual tolerância de uma das partes com o descumprimento de qualquer obrigação contratual será considerada mera liberalidade, não implicando transação, novação ou renúncia, de modo que a parte inocente pode, a qualquer tempo, exigir da parte culpada o integral cumprimento desta obrigação.</p>
	</div>
	<div class="objetos">
		<p>6.3 – As partes concordam que as páginas do presente contrato e de seus eventuais anexos, todas formadas por meio digital com o qual expressamente declaram concordar, representam a consolidação final das negociações havidas entre as partes, de forma que, com a sua assinatura, restam anulados e substituídos todos e quaisquer documentos e tratativas havidas anteriormente entre as partes, formalizados por qualquer outro meio, verbal ou escrito, físico ou digital, ficando válidos, para todos os fins e efeitos de direito, apenas e tão somente o presente instrumento e seus anexos.</p>
	</div>
	<div class="objetos">
		<p>6.4 – Nos termos do art. 10, § 2º, da Medida Provisória nº 2.200-2, as partes expressamente concordam em utilizar e reconhecem como válida qualquer forma de comprovação de anuência aos termos ora acordados em formato eletrônico, incluindo assinaturas eletrônicas, ainda que não utilizem de certificado digital emitido no padrão ICP-Brasil. A formalização desta parceria da maneira ora acordada será suficiente para a validade e integral vinculação das partes ao presente Contrato.</p>
	</div>
	<div class="objetos">
		<p>6.5 – Para todos os fins de direito, o presente Instrumento será considerado título executivo extrajudicial, nos termos do artigo 784, inciso III, do Código de Processo Civil – Lei n.º 13.105/2015. </p>
	</div>
	<div class="objetos">
		<p>6.6 – Fica eleito o Foro da Cidade de Itupeva-SP, com expressa exclusão de qualquer outro, por mais privilegiado que seja, para nele serem dirimidas quaisquer questões oriundas do presente contrato ou sua execução.</p>
	</div>

	<p>E, por estarem assim justas e contratadas, firmam o presente instrumento em duas vias de igual teor e forma, perante as testemunhas abaixo.</p>

	<p>Itupeva, 03 de junho de 2019. </p>

</div>';

?>