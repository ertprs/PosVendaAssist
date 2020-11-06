<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
//include dirname(__FILE__) . '/../../classes/mpdf/MPDF57/mpdf.php';
include __DIR__ . '/../../classes/mpdf61/mpdf.php';
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

$array_meses = array('01'=>'Janeiro','02'=>'Fevereiro','03'=>'Marco','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro');
$mes = date('m');

$posto = $_GET['posto'];

//$posto = 6359;

$sql = "SELECT upper(tbl_posto.nome) AS nome, 
	upper(tbl_posto_fabrica.contato_endereco) AS contato_endereco, 
	upper(tbl_posto_fabrica.contato_bairro) AS contato_bairro,
	tbl_posto_fabrica.contato_numero,
	upper(tbl_posto_fabrica.contato_complemento) AS contato_complemento,
	tbl_posto_fabrica.contato_cep, 
	UPPER(tbl_posto_fabrica.contato_nome) AS contato_nome,
	upper(tbl_posto_fabrica.contato_cidade) AS contato_cidade,
	upper(tbl_posto_fabrica.contato_estado) AS contato_estado,
	tbl_posto_fabrica.contato_email,
	upper(tbl_posto.ie) AS ie,
	tbl_posto.cnpj
	FROM tbl_posto
	JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 151
	WHERE tbl_posto.posto = $posto";

$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
	
	$posto_nome_aux	= pg_fetch_result($res,0,nome);
	$endereco 		= pg_fetch_result($res,0,contato_endereco);
	$numero 		= pg_fetch_result($res,0,contato_numero);
	$complemento 	= pg_fetch_result($res,0,contato_complemento);
	$cep 			= pg_fetch_result($res,0,cep);
	$nome_aux 	    = ucfirst(strtolower(pg_fetch_result($res,0,contato_nome)));
	$ie 			= pg_fetch_result($res,0,ie);
	$cnpj 			= pg_fetch_result($res,0,cnpj);
	$bairro 		= pg_fetch_result($res,0,contato_bairro);
	$cidade 		= pg_fetch_result($res,0,contato_cidade);
	$estado 		= pg_fetch_result($res,0,contato_estado);
	$email 			= pg_fetch_result($res,0,contato_email);

	$nome_aux = explode(" ", $nome_aux);
	foreach ($nome_aux as $indice => $nome) {
		$contato_nome .= ucfirst(strtolower($nome))." ";
	}

	$posto_nome_aux = explode(" ", $posto_nome_aux);
	foreach ($posto_nome_aux as $indice => $nome) {
		$posto_nome .= ucfirst(strtolower($nome))." ";
	}


$html = "<p style='text-align:center; text-decoration:underline; font-weight:bold;'>CONTRATO DE PRESTA&Ccedil;&Atilde;O DE SERVI&Ccedil;O DE ASSIST&Ecirc;NCIA T&Eacute;CNICA</p>

<p><b>1 - COMO <b>CONTRATANTE</b></b>, a empresa <b>MK ELETRODOM&Eacute;STICOS <b>MONDIAL</b> S.A.</b>, com sede na Estrada da Volta, 1200 - Galp&atilde;o 3, Centro, CEP 44245-000, em Concei&ccedil;&atilde;o do Jacu&iacute;pe - BA, inscrita no CNPJ sob n&deg; 07.666.567/0002-21 e Inscri&ccedil;&atilde;o Estadual n&deg; 83.186.365, neste ato representada por representante legal, denominada simplesmente <b><b>MONDIAL</b></b>.</p>
<p><b>2 - COMO <b>CONTRATADA</b>, POSTO AUTORIZADO</b>, $posto_nome, com sede na $endereco N $numero $complemento, $bairro, CEP $cep, em $cidade - $estado, inscrito no CNPJ sob o n&deg; $cnpj, e Inscri&ccedil;&atilde;o Estadual n&deg; $ie, neste ato representado pelo seu representante legal ______________________________________, RG __________________ e CPF ___________________.</p>
<p><b><b>Par&aacute;grafo &Uacute;nico</b>:</b> As partes acima identificadas t&ecirc;m, entre si, justas e acertadas o presente <b>CONTRATO PARTICULAR DE PRESTA&Ccedil;&Atilde;O DE SERVI&Ccedil;OS DE ASSIST&Ecirc;NCIA T&Eacute;CNICA</b>, que se reger&aacute; pelas cl&aacute;usulas seguintes e pelas condi&ccedil;&otilde;es de pre&ccedil;o, forma e termo de pagamentos.</p>


<p style='text-decoration:underline; font-weight:bold;'>I - DO OBJETO DO CONTRATO</p>

<p><b>Cl&aacute;usula 1&ordf;</b> - A <b>CONTRATANTE</b> fabrica e/ou importa, para comercializa&ccedil;&atilde;o, produtos Eletrodom&eacute;sticos nas linhas Port&aacute;teis, Ferramentas e Eletr&ocirc;nicos com a marca <b>MONDIAL</b> e seus clientes finais, se necess&aacute;rio, ser&atilde;o atendidos pela <b>CONTRATADA</b>.</p>

<p><b>Par&aacute;grafo &Uacute;nico</b>: Al&eacute;m dos produtos com a marca <b>MONDIAL</b>, a <b>CONTRATANTE</b> fabrica e/ou importa, para comercializa&ccedil;&atilde;o produtos com outras marcas. A rela&ccedil;&atilde;o dos produtos com as marcas a serem atendidos pela <b>CONTRATADA</b> ser&aacute; informada periodicamente atrav&eacute;s de Comunicados e informativos.</p>

<p><b>Cl&aacute;usula 2&ordf;</b> A <b>CONTRATADA</b> &eacute; uma empresa que presta servi&ccedil;os de assist&ecirc;ncia t&eacute;cnica e est&aacute; habilitada para tal, e concorda em prestar os servi&ccedil;os de assist&ecirc;ncia t&eacute;cnica aos clientes dos produtos <b><b>MONDIAL</b></b>.</p>


<p style='text-decoration:underline; font-weight:bold;'>II - DAS OBRIGA&Ccedil;&Otilde;ES DO CONTRATANTE</p>

<p><b>Cl&aacute;usula 3&ordf;</b> - A <b>CONTRATANTE</b> tem como objetivo a satisfa&ccedil;&atilde;o dos usu&aacute;rios de seus produtos <b>MONDIAL</b>, dentro e ap&oacute;s o prazo de garantia.</p>

<p><b>Cl&aacute;usula 4&ordf;</b> - A <b>CONTRATANTE</b> pelo presente instrumento contrata a <b>CONTRATADA</b> para que preste os servi&ccedil;os de assist&ecirc;ncia t&eacute;cnica como <b>POSTO AUTORIZADO <b>MONDIAL</b></b> devendo esta realizar reparos nos produtos assim como, vender aos consumidores pe&ccedil;as, partes e acess&oacute;rios originais para reparos de produtos <b>FORA DE GARANTIA</b>.</p>

<p><b>Cl&aacute;usula 5&ordf;</b> - A <b>CONTRATANTE</b> fornecer&aacute; a <b>CONTRATADA</b> quando da celebra&ccedil;&atilde;o deste contrato, acesso ao sistema de reporte de ordens de servi&ccedil;o, consulta aos materiais t&eacute;cnicos e pedidos de pe&ccedil;as mediante senha e instru&ccedil;&otilde;es, obrigando-se a <b>CONTRATADA</b> a conserv&aacute;-la em sigilo e deixar de utiliz&aacute;-la em caso de vencimento do prazo ou rescis&atilde;o deste contrato.</p>

<p><b><u>III - DA OBRIGA&Ccedil;&Atilde;O DO <b>CONTRATADO</u></b></p>

<p><b>Cl&aacute;usula 6&ordf;</b> - A <b>CONTRATADA</b> tem como obriga&ccedil;&atilde;o principal, assegurar &agrave; <b>CONTRATANTE</b> a presta&ccedil;&atilde;o de servi&ccedil;os de assist&ecirc;ncia t&eacute;cnica dos produtos, reparando-os, <b>DENTRO E FORA DO PER&Iacute;ODO DE GARANTIA</b> (exceto impedimento por motivo de for&ccedil;a maior ou outras alheias ao seu controle ou a sua vontade), dedicando o melhor de sua habilidade, conhecimento t&eacute;cnico e dilig&ecirc;ncia para a execu&ccedil;&atilde;o dos reparos dos produtos bem como primar pela excel&ecirc;ncia no atendimento aos consumidores.</p>

<p><b>Par&aacute;grafo &Uacute;nico</b> - Nos reparos a que se refere o presente contrato, a <b>CONTRATADA</b> empregar&aacute; &uacute;nica e exclusivamente pe&ccedil;as originais fornecidas pela <b><b>MONDIAL</b></b>, sendo-lhe expressa e irrevogavelmente vetada a utiliza&ccedil;&atilde;o de outras marcas mesmo que similar.</p>

<p><b>Cl&aacute;usula 7&ordf;</b> - A <b>CONTRATADA</b> obriga-se a adotar o logotipo e/ou identidade visual da marca <b>MONDIAL</b>, na cor e modelo definido pela <b>CONTRATANTE</b>. Terminada a vig&ecirc;ncia desse contrato, cessar&aacute; imediata e automaticamente o direito ao uso de qualquer impresso ou propaganda com nomes e s&iacute;mbolos pertencentes &agrave; <b><b>MONDIAL</b></b>.</p>

<p><b>Par&aacute;grafo Primeiro</b> - Toda propaganda, promo&ccedil;&atilde;o ou publicidade, envolvendo a marca <b>MONDIAL</b>, somente poder&aacute; ser utilizada com autoriza&ccedil;&atilde;o pr&eacute;via da <b>CONTRATANTE</b>, mediante licen&ccedil;a espec&iacute;fica.</p>

<p><b>Par&aacute;grafo Segundo</b> - A <b>CONTRATADA</b>, durante o per&iacute;odo de vig&ecirc;ncia do contrato, poder&aacute; apontar em seu papel timbrado, cart&atilde;o, placas ou pain&eacute;is fixados na fachada do seu estabelecimento, a logomarca <b>MONDIAL</b>, desde que siga o logotipo oficial estabelecido pela <b>MONDIAL</b>, e pela <b>CONTRATANTE</b>.</p>

<p><b>Par&aacute;grafo Terceiro</b> - A <b>CONTRATADA</b>, responder&aacute; civil e criminalmente pelo uso indevido da marca <b>MONDIAL</b>.</p>

<p><b>Par&aacute;grafo Quarto</b> - Se por delibera&ccedil;&atilde;o da <b>CONTRATANTE</b>, for enviado para <b>CONTRATADA</b> pe&ccedil;as antecipadas para atendimentos em garantia, a mesma se compromete controlar e prestar conta da utiliza&ccedil;&atilde;o sempre que solicitado pela <b>CONTRATANTE</b>. O uso destas pe&ccedil;as em produtos <b>FORA DA GARANTIA</b> poder&aacute; ocorrer mediante autoriza&ccedil;&atilde;o pr&eacute;via da <b>CONTRATANTE</b>.</p>


<p style='text-decoration:underline; font-weight:bold;'>IV - DA REMUNERA&Ccedil;&Atilde;O</p>

<p><b>Cl&aacute;usula 8&ordf;</b> - Pelos servi&ccedil;os de assist&ecirc;ncia t&eacute;cnica prestados, o <b>CONTRATADO</b> perceber&aacute; a quantia resultando do montante de atendimentos executados pagos conforme tabela de valores de m&atilde;o-de-obra vigente.</p>

<p><b>Cl&aacute;usula 9&ordf;</b> - Sempre que deliberado pela <b>CONTRATANTE</b>, os valores devidos &agrave; <b>CONTRATADA</b> a t&iacute;tulo de presta&ccedil;&atilde;o de servi&ccedil;o de assist&ecirc;ncia t&eacute;cnica, poder&aacute; ser utilizado para compensa&ccedil;&atilde;o de valores credores oriundos de compra de pe&ccedil;as junto a <b>CONTRATANTE</b> nos moldes de Encontro de Contas, em que, sendo apurado saldo de valores a receber, ser&atilde;o devidamente creditados para a <b>CONTRATADA</b> nos prazos habituais.</p>

<p><b>Cl&aacute;usula 10&ordf;</b> - O prazo para pagamentos &eacute; no 5&deg; dia &uacute;til do m&ecirc;s subsequente ao m&ecirc;s da presta&ccedil;&atilde;o de servi&ccedil;os, sempre que <u>Nota Fiscal de Servi&ccedil;os</u> e <u>Extrato(s) de Pagamento</u> forem recebidos em tempo h&aacute;bil para auditoria e enquanto vigorar este contrato.</p>


<p style='text-decoration:underline; font-weight:bold;'>V - DISPOSIC&Otilde;ES GERAIS</p>

<p><b>Cl&aacute;usula 11&ordf;</b>  - Fica assegurado &agrave; <b>CONTRATANTE</b> livre acesso as instala&ccedil;&otilde;es da <b>CONTRATADA</b>, para examinar os equipamentos, m&eacute;todos de opera&ccedil;&atilde;o e inspecionar os servi&ccedil;os de reparos efetuados pela <b>CONTRATADA</b> nos produtos <b>MONDIAL</b>, podendo sugerir modifica&ccedil;&otilde;es que visem &agrave; melhoria do atendimento ao cliente e o bom andamento ao cliente e o bom andamento dos servi&ccedil;os.</p>

<p><b>Cl&aacute;usula 12&ordf;</b>  - A <b>CONTRATADA</b> dever&aacute; garantir a salvaguarda do conceito da <b>MONDIAL</b>, contra as rela&ccedil;&otilde;es de demandas relacionadas a reparos efetuados, bem como das despesas com isso relacionada.</p>

<p><b>Cl&aacute;usula 13&ordf;</b>  - A <b>CONTRATADA</b> conduzir&aacute; seus neg&oacute;cios rigidamente dentro dos princ&iacute;pios &eacute;ticos e t&eacute;cnicos em estreita conformidade com as leis, decretos vigentes no pa&iacute;s, tanto Federais, Estaduais e Municipais, e as referentes ao C&oacute;digo de Defesa do Consumidor, sendo o &uacute;nico respons&aacute;vel por qualquer infra&ccedil;&atilde;o dessa legisla&ccedil;&atilde;o.</p>

<p><b>Cl&aacute;usula 14&ordf;</b>  - A <b>CONTRATADA</b> assume exclusiva e incontinentemente toda a responsabilidade civil, trabalhista, previdenci&aacute;ria, acident&aacute;ria e criminal, quanto aos seus funcion&aacute;rios, prepostos e eventuais representantes, isentando
a <b>CONTRATANTE</b> de presente e de futuro de qualquer compromissos ou &ocirc;nus dessa natureza. A rela&ccedil;&atilde;o ora estabelecida &eacute; meramente comercial sendo <b>CONTRATANTE</b> e a <b>CONTRATADA</b> totalmente independentes entre si. De qualquer tempo, forma ou &acirc;mbito a <b>CONTRATANTE</b> n&atilde;o &eacute; respons&aacute;vel por d&iacute;vida, d&uacute;vidas, manifesta&ccedil;&otilde;es, reclama&ccedil;&otilde;es, atos, compromissos ou obriga&ccedil;&otilde;es assumidas pela <b>CONTRATADA</b> ou por quem eventualmente represent&aacute;-la.*</p>

<p><b>Cl&aacute;usula 15&ordf;</b>  - A <b>CONTRATANTE</b> fornecer&aacute; gratuitamente &agrave; <b>CONTRATADA</b> toda documenta&ccedil;&atilde;o t&eacute;cnica dos aparelhos <b>MONDIAL</b> que estiverem sendo comercializados no Brasil nesta data, assim como os que vierem a ser lan&ccedil;ados durante a vig&ecirc;ncia deste contrato.</p>

<p><b>Cl&aacute;usula 16&ordf;</b>   A <b>CONTRATADA</b>, compromete-se a fazer a solicita&ccedil;&atilde;o das pe&ccedil;as necess&aacute;rias ao completo atendimento ao consumidor, dentro do prazo improrrog&aacute;vel de 05 ( cinco ) dias,  a contar da data do recebimento do produto em seu posto autorizado.</p>

<p><b>Cl&aacute;usula 17&ordf;</b>  - As pe&ccedil;as e acess&oacute;rios necess&aacute;rios para levar a cabo reparos de aparelhos <b>MONDIAL</b>, assim como a venda aos consumidores, ser&atilde;o faturados pela <b>CONTRATANTE</b> &agrave; <b>CONTRATADA</b> nas condi&ccedil;&otilde;es vigentes na &eacute;poca do faturamento.</p>

<p><b>Par&aacute;grafo Primeiro</b> - Os pre&ccedil;os de venda ao consumidor para pe&ccedil;as, partes e acess&oacute;rios, bem como as taxas de m&atilde;o de obra utilizados em reparos <b>FORA DA GARANTIA</b> ter&atilde;o uma lista de pre&ccedil;os sugerida pela <b>CONTRATANTE</b>. Entretanto no caso de abusos por parte da <b>CONTRATADA</b> na pr&aacute;tica dos pre&ccedil;os, ensejar&aacute; direito &agrave; <b>CONTRATANTE</b> em intervir no caso, podendo inclusive gerar a rescis&atilde;o do presente contrato.</p>

<p><b>Par&aacute;grafo Segundo</b> - As pe&ccedil;as originais destinadas a reparo de produtos &quot;<b>EM GARANTIA</b>&quot; ser&atilde;o enviadas a <b>CONTRATADA</b>, pela <b>CONTRATANTE</b>, sem &ocirc;nus, mediante o pedido de pe&ccedil;as feito atrav&eacute;s do cadastro de pe&ccedil;as na Ordem de Servi&ccedil;o gerada eletronicamente.</p>

<p><b>Cl&aacute;usula 18&ordf;</b>  - Caso a <b>CONTRATADA</b> n&atilde;o efetue, dentro do prazo devido, o pagamento de suas d&iacute;vidas para com a <b>CONTRATANTE</b>, poder&aacute; esta &uacute;ltima sustar o procedimento de pedidos de pe&ccedil;as, independentes da eventual rescis&atilde;o de contrato, a crit&eacute;rio da <b>CONTRATANTE</b>, nos termos da cl&aacute;usula 21.</p>

<p><b>Cl&aacute;usula 19&ordf;</b>  - A <b>CONTRATADA</b> compromete-se a identificar e conservar em seu poder as pe&ccedil;as substitu&iacute;das em aparelhos consertados <b>EM GARANTIA</b> pelo PER&Iacute;ODO DE 90 (NOVENTA) DIAS, ap&oacute;s o envio das Ordens de Servi&ccedil;o &agrave; <b>CONTRATANTE</b>, ap&oacute;s este prazo dever&aacute; destruir tais pe&ccedil;as.</p>

<p><b>Cl&aacute;usula 20&ordf;</b>  - A <b>CONTRATADA</b> compromete-se a destruir todas as pe&ccedil;as substitu&iacute;das em aparelhos <b>FORA DE GARANTIA</b>, de maneira a assegurar a impossibilidade de sua restaura&ccedil;&atilde;o ou reutiliza&ccedil;&atilde;o posterior.</p>

<p style='text-decoration:underline; font-weight:bold;'>VI - DA RESCIS&Atilde;O CONTRATUAL</p>

<p><b>Cl&aacute;usula 21&ordf;</b> - O presente instrumento ter&aacute; a dura&ccedil;&atilde;o por tempo indeterminado, podendo ser rescindido a qualquer momento por qualquer das partes, sem qualquer &ocirc;nus para ambas as partes.</p>

<p><b>Cl&aacute;usula 22&ordf;</b> - As partes poder&atilde;o rescindir este contrato, por meio de simples notifica&ccedil;&atilde;o expressa, com anteced&ecirc;ncia de 30 (trinta) dias por mera conveni&ecirc;ncia, atrav&eacute;s de CARTA REGISTRADORA OU PROTOCOLADA, enviada ou entregue nos endere&ccedil;os comerciais constantes do presente, bem como por desrespeito e infring&ecirc;ncia a qualquer cl&aacute;usula deste contrato. Essa rescis&atilde;o n&atilde;o concede &agrave; <b>CONTRATADA</b> qualquer direito de reclama&ccedil;&atilde;o ou indeniza&ccedil;&atilde;o dela decorrente a qualquer tempo ou t&iacute;tulo.</p>
<p><b>Par&aacute;grafo &Uacute;nico</b> - O presente instrumento poder&aacute; ainda ser rescindido no caso de:</p>
<p style='padding-left:50px;'><b>I)</b> Fal&ecirc;ncia, concordata ou liquida&ccedil;&atilde;o, judicial ou extrajudicial, de qualquer uma das partes;</p>
<p style='padding-left:50px;'><b>II)</b> Viola&ccedil;&atilde;o de qualquer obriga&ccedil;&atilde;o legal ou contratual;</p>
<p style='padding-left:50px;'><b>III)</b> For&ccedil;a maior ou caso fortuito; ou.</p>
<p style='padding-left:50px;'><b>IV)</b> Altera&ccedil;&atilde;o do controle acion&aacute;rio da <b>CONTRATADA</b>.</p>
<p><b>Cl&aacute;usula 23&ordf;</b> - <b>CONTRATADA</b> obriga-se a manter confidencialidade sobre os documentos recebidos da <b>CONTRATANTE</b>. O n&atilde;o cumprimento dessa obriga&ccedil;&atilde;o constitui falta grave, ensejando imediata rescis&atilde;o desse contrato.</p>


<p style='text-decoration:underline; font-weight:bold;'>VII - DO T&Iacute;TULO EXECUTIVO</p>

<p><b>Cl&aacute;usula 24&ordf;</b> - As partes acordam que o presente instrumento particular preenche os requisitos de t&iacute;tulo executivo, podendo ser exigido o seu cumprimento pela via executiva, nos termos da Lei Adjetiva p&aacute;tria.</p>


<p style='text-decoration:underline; font-weight:bold;'>VIII - DA IMPOSSIBILIDADE DE CESS&Atilde;O</p>

<p><b>Cl&aacute;usula 25&ordf;</b> - &Eacute; vedado &agrave;s partes ceder ou transferir, no todo ou em parte, os direitos e obriga&ccedil;&otilde;es deste contrato a terceiros, ainda quando do mesmo grupo econ&ocirc;mico, sem a pr&eacute;via e expressa anu&ecirc;ncia da outra parte, sob pena de autom&aacute;tica rescis&atilde;o deste ajuste.</p>


<p style='text-decoration:underline; font-weight:bold;'>IX - DA IMPOSSIBILIDADE DE NOVA&Ccedil;&Atilde;O</p>

<p><b>Cl&aacute;usula 26&ordf;</b> - Todo e qualquer ato praticado pela <b>CONTRATADA</b>, em desconformidade com o previsto neste Contrato, ser&aacute; tido como mera liberalidade, n&atilde;o implicando altera&ccedil;&atilde;o t&aacute;cita dos termos deste Instrumento.</p>


<p style='text-decoration:underline; font-weight:bold;'>X - DA IRREVOGABILIDADE E IRRETRATABILIDADE</p>

<p><b>Cl&aacute;usula 27&ordf;</b> - As partes acordam que o presente Contrato &eacute; firmado em car&aacute;ter irrevog&aacute;vel e irretrat&aacute;vel, obrigando, em todos os seus termos, n&atilde;o s&oacute; as partes <b>CONTRATANTE</b>s, como tamb&eacute;m seus herdeiros e sucessores.</p>


<p style='text-decoration:underline; font-weight:bold;'>XI - DA LIVRE MANIFESTA&Ccedil;&Atilde;O DA VONTADE</p>

<p><b>Cl&aacute;usula 28&ordf;</b> - As partes <b>CONTRATANTE</b>s declaram expressamente que firmaram o presente aven&ccedil;a informadas pela mais livre manifesta&ccedil;&atilde;o de suas vontades, vedando-se toda e qualquer argui&ccedil;&atilde;o posterior relativa &agrave; validade de quaisquer das cl&aacute;usulas ou condi&ccedil;&otilde;es aqui inscritas.</p>


<p style='text-decoration:underline; font-weight:bold;'>XII - DO CAR&Aacute;TER DIVIS&Iacute;VEL DESTE INSTRUMENTO</p>

<p><b>Cl&aacute;usula 29&ordf;</b> - O presente instrumento contratual possui car&aacute;ter divis&iacute;vel, de forma que, a declara&ccedil;&atilde;o de nulidade de uma delas n&atilde;o implicar&aacute; na nulidade das demais, permanecendo em vigor todos os outros termos do presente instrumento.</p>


<p style='text-decoration:underline; font-weight:bold;'>XIII - DAS DISPOSI&Ccedil;&Otilde;ES COMPLEMENTARES</p>

<p><b>Cl&aacute;usula 30&ordf;</b> - Todas e quaisquer disposi&ccedil;&otilde;es complementares ao presente instrumento somente poder&atilde;o ser formalizadas atrav&eacute;s de respectivo &quot;Termo de Aditamento de Contrato&quot;, n&atilde;o se admitindo qualquer outro meio.</p>


<p style='text-decoration:underline; font-weight:bold;'>XIV - DO FORO</p>

<p><b>Cl&aacute;usula 31&ordf;</b> - As partes elegem o foro da Barueri, Estado de S&atilde;o Paulo, para dirimir quaisquer celeumas oriundas deste contrato, excluindo qualquer outro, por mais privilegiado que seja.</p>

<p>E por estarem assim justas e <b>CONTRATADA</b>s, assinam o presente perante as testemunhas abaixo, em duas (02) vias de igual teor e para um s&oacute; fim, para que surta os seus efeitos legais e jur&iacute;dicos.</p> <br>


<p>Barueri, ".date('d')." de $array_meses[$mes]  de ".date('Y').".</p>
 


<p>____________________________________________________<br>
Giovanni Marins Cardoso - MK Eletrodom&eacute;sticos Mondial S.A.</p>



<p>____________________________________________________<br>
$contato_nome - $posto_nome</p>


<p>Testemunha (1) _______________________________________</p>
<p>RG:</p>
<p>CPF:</p>


<p>Testemunha (2) _______________________________________</p>
<p>RG: </p>
<p>CPF:</p>";

	$caminho = "/var/www/assist/www/credenciamento/contrato/";
	if (strtolower($_serverEnvironment) == 'development') {
		$caminho = __DIR__."/";
	}

	//$html = ob_get_contents();
	$html = utf8_encode($html);
	$logo = "http://ww2.telecontrol.com.br/assist/logos/logo_mondial.jpg";
	$img = "<img src='{$logo}' style='width:150px;float:right;margin-top:-20px;' alt=''><br>";

	$mpdf = new mPDF('c','A4','','',10,10,27,25,16,13);
	//$mpdf = new \Mpdf\Mpdf;
	$mpdf->list_indent_first_level = 0;
	$mpdf->allow_charset_conversion=true;
	$mpdf->SetHTMLHeader("$img");
	$mpdf->SetFooter('|{PAGENO}/{nb}|');
	$mpdf->WriteHTML($html);
	$mpdf->Output($caminho."mondial_contrato_{$posto}.pdf","F");
	
	if(strlen($email) > 0){

		$to = $email;
		$from = "rede.autorizada@telecontrol.com.br";
		$subject ="CONTRATO MONDIAL";

	    $file1 = $caminho."mondial_contrato_{$posto}.pdf";
	    $files = array($file1);

		$message .= "<center><div style='width:600px'>
	                                <p align='justify'>
	                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
	                                        Em parceria com a Telecontrol, estamos credenciando novas assistências técnicas para ingressar em nossa rede.
	                                        <br /><br />
	                                        &bull;
	                                        <br /><br />
	                                        &bull; Os contratos e a gestão da rede serão realizados diretamente com a {$nome_fabrica}.
	                                        <br /><br />
	                                        <strong>
	                                            <font color='red'>Obs.</font>
	                                            Somente o termo de adesão deverá ser preenchido, assinado e devolvido para a Telecontrol.
	                                        </strong>
	                                    </font>
	                                </p>
	                                <p align='left'>
	                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
	                                        Envio feito através dos Correios, enviar para o endereço: <br />
	                                        Av. Carlos Artêncio, 420-B    CEP: 17.519-255 - Bairro Fragata    Marília, SP - Brasil
	                                        <br /><br />
	                                        Envio feito através de E-mail, enviar para: <br />
	                                        rede.autorizada@telecontrol.com.br
	                                    </font>
	                                </p>
	                                <p align='left'>
	                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
	                                        Duvidas:
	                                        <br />SAC Rede Autorizada: 0800-718-7825
	                                        <br />E-mail: rede.autorizada@telecontrol.com.br
	                                    </font>
	                                </p>
	                                </div></center>";

	    $headers = "From: $from";

	    $semi_rand = md5(time());
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

		$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

		$message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
		$message .= "--{$mime_boundary}\n";

		
		for($x=0;$x<count($files);$x++){
			$file = fopen($files[$x],"rb");
			$data = fread($file,filesize($files[$x]));
			fclose($file);
			$data = chunk_split(base64_encode($data));
			$message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"contrato.pdf\"\n" .
			"Content-Disposition: attachment;\n" . " filename=\"contrato.pdf\"\n" .
			"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
			$message .= "--{$mime_boundary}\n";
		}
		
		if(mail($to, $subject, $message, $headers)){
			exit(json_encode(array("ok" => "Contrato enviado com sucesso!")));
	    }else{
			exit(json_encode(array("erro" => "Falha ao tentar enviar o email, verifique o email cadastrado e tente novamente!")));
	    }
	}else{
		exit(json_encode(array("erro" => "Arquivo nao encontrado para anexar ao e-mail!")));
	}
}else{
	exit(json_encode(array("erro" => "Dados do posto nao encontrados!")));
}
