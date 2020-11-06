<?php

/**
 * calculaChaveAcesso
 * Cria uma d�gito verificador para Nota Fiscal 2.0
 *
 * @exemple

	NFe43100506880590000170550000000001847872772215

	[44 | 1005 | 06880590000170 | 55 | 0 | 184 | 1 | 787277221]
	(02) C�digo da UF     = 43
	(04) AAMM da emiss�o  = 1005
	(14) CNPJ do Emitente = 06880590000170
	(02) Modelo           = 55
	(03) S�rie            = 000
	(09) N�mero da NF-e   = 000000184
	(09) Tipo de emiss�o  = 1
	(09) C�digo Num�rico  = 787277221
	(01) DV               = 5
	calculaDV: 5

 * @param  $cUF, $dEmi, $CNPJ, $mod, $serie, $nNF, $cNF
 * @return $chave
 */

function calculaChaveAcesso($cUF, $dEmi, $CNPJ, $mod, $serie, $nNF, $tipo, $cNF) {

	$chave  = sprintf("%02d", $cUF);   // 02 - cUF   - c�digo da UF do emitente do Documento Fiscal
	$chave .= sprintf("%04d", $dEmi);  // 04 - AAMM  - Ano e Mes de emiss�o da NF-e
	$chave .= sprintf("%014s", $CNPJ); // 14 - CNPJ  - CNPJ do emitente
	$chave .= sprintf("%02d", $mod);   // 02 - mod   - Modelo do Documento Fiscal
	$chave .= sprintf("%03d", $serie); // 03 - serie - S�rie do Documento Fiscal
	$chave .= sprintf("%09d", $nNF);   // 09 - nNF   - N�mero do Documento Fiscal
	$chave .= sprintf("%01d", $tipo);  // 01 - tipo  - Forma de emissão da NF-e [01]
	$chave .= sprintf("%08d", $cNF);   // 09 - cNF   - C�digo Num�rico que comp�e a Chave de Acesso
	$chave .= calculaDV($chave);       // 01 - cDV   - D�gito Verificador da Chave de Acesso

	return $chave;

}   

function geraCN($length=8) {

    $numero = '';

    for ($x = 0; $x < $length; $x++) {

        $numero .= rand(0,9);

    }

    return $numero;

}

/**
 * calculaDV
 * Cria uma d�gito verificador para Nota Fiscal 2.0
 * @param  $cUF, $dEmi, $CNPJ, $mod, $serie, $nNF, $cNF
 * @return $chave
 */

function calculaDV($chave43) {

   $multiplicadores = array(2,3,4,5,6,7,8,9);
   $i = 42;
   $soma_ponderada = 0;
	
   while ($i >= 0) {

	  for ($m = 0; $m < count($multiplicadores) && $i >= 0; $m++) {

		 $soma_ponderada += $chave43[$i] * $multiplicadores[$m];
		 $i--;

	  }

   }
	
   $resto = $soma_ponderada % 11;
	
   if ($resto == '0' || $resto == '1') {
	  $cDV = 0;
   } else {
	  $cDV = 11 - $resto;
   }
	
   return $cDV;

}

if (!function_exists('tira_acentos')) {
	function tira_acentos ($texto) {
		$acentos      = array("com" => "������������������������������������������",
							  "sem"	=> "aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC");
		return strtr($texto,$acentos['com'], $acentos['sem']);
	}
}

function getStatus($i) {

	$msg_retorno = array(
	'100' => 'Autorizado o uso da NF-e',
	'101' => 'Cancelamento de NF-e homologado',
	'102' => 'Inutiliza��o de n�mero homologado',
	'103' => 'Lote recebido com sucesso',
	'104' => 'Lote processado',
	'105' => 'Lote em processamento',
	'106' => 'Lote n�o localizado',
	'107' => 'Servi�o em Opera��o',
	'108' => 'Servi�o Paralisado Momentaneamente (curto prazo)',
	'109' => 'Servi�o Paralisado sem Previs�o',
	'110' => 'Uso Denegado',
	'111' => 'Consulta cadastro com uma ocorr�ncia',
	'112' => 'Consulta cadastro com mais de uma ocorr�ncia',
	'201' => 'Rejei��o: O numero m�ximo de numera��o de NF-e a inutilizar ultrapassou o limite',
	'202' => 'Rejei��o: Falha no reconhecimento da autoria ou integridade do arquivo digital',
	'203' => 'Rejei��o: Emissor n�o habilitado para emiss�o da NF-e',
	'204' => 'Rejei��o: Duplicidade de NF-e',
	'205' => 'Rejei��o: NF-e est� denegada na base de dados da SEFAZ',
	'206' => 'Rejei��o: NF-e j� est� inutilizada na Base de dados da SEFAZ',
	'207' => 'Rejei��o: CNPJ do emitente inv�lido',
	'208' => 'Rejei��o: CNPJ do destinat�rio inv�lido',
	'209' => 'Rejei��o: IE do emitente inv�lida',
	'210' => 'Rejei��o: IE do destinat�rio inv�lida',
	'211' => 'Rejei��o: IE do substituto inv�lida ',
	'212' => 'Rejei��o: Data de emiss�o NF-e posterior a data de recebimento',
	'213' => 'Rejei��o: CNPJ-Base do Emitente difere do CNPJ-Base do Certificado Digital',
	'214' => 'Rejei��o: Tamanho da mensagem excedeu o limite estabelecido',
	'215' => 'Rejei��o: Falha no schema XML',
	'216' => 'Rejei��o: Chave de Acesso difere da cadastrada',
	'217' => 'Rejei��o: NF-e n�o consta na base de dados da SEFAZ',
	'218' => 'Rejei��o: NF-e  j� esta cancelada na base de dados da SEFAZ',
	'219' => 'Rejei��o: Circula��o da NF-e verificada',
	'220' => 'Rejei��o: NF-e autorizada h� mais de 7 dias (168 horas)',
	'221' => 'Rejei��o: Confirmado o recebimento da NF-e pelo destinat�rio',
	'222' => 'Rejei��o: Protocolo de Autoriza��o de Uso difere do cadastrado',
	'223' => 'Rejei��o: CNPJ do transmissor do lote difere do CNPJ do transmissor da consulta',
	'224' => 'Rejei��o: A faixa inicial � maior que a faixa final',
	'225' => 'Rejei��o: Falha no Schema XML do lote de NFe',
	'226' => 'Rejei��o: C�digo da UF do Emitente diverge da UF autorizadora',
	'227' => 'Rejei��o: Erro na Chave de Acesso - Campo Id . falta a literal NFe',
	'228' => 'Rejei��o: Data de Emiss�o muito atrasada',
	'229' => 'Rejei��o: IE do emitente n�o informada',
	'230' => 'Rejei��o: IE do emitente n�o cadastrada',
	'231' => 'Rejei��o: IE do emitente n�o vinculada ao CNPJ',
	'232' => 'Rejei��o: IE do destinat�rio n�o informada',
	'233' => 'Rejei��o: IE do destinat�rio n�o cadastrada',
	'234' => 'Rejei��o: IE do destinat�rio n�o vinculada ao CNPJ',
	'235' => 'Rejei��o: Inscri��o SUFRAMA inv�lida',
	'236' => 'Rejei��o: Chave de Acesso com d�gito verificador inv�lido',
	'237' => 'Rejei��o: CPF do destinat�rio inv�lido',
	'238' => 'Rejei��o: Cabe�alho - Vers�o do arquivo XML superior a Vers�o vigente',
	'239' => 'Rejei��o: Cabe�alho - Vers�o do arquivo XML n�o suportada',
	'240' => 'Rejei��o: Cancelamento/Inutiliza��o - Irregularidade Fiscal do Emitente',
	'241' => 'Rejei��o: Um n�mero da faixa j� foi utilizado',
	'242' => 'Rejei��o: Cabe�alho - Falha no Schema XML',
	'243' => 'Rejei��o: XML Mal Formado',
	'244' => 'Rejei��o: CNPJ do Certificado Digital difere do CNPJ da Matriz e do CNPJ do Emitente',
	'245' => 'Rejei��o: CNPJ Emitente n�o cadastrado',
	'246' => 'Rejei��o: CNPJ Destinat�rio n�o cadastrado',
	'247' => 'Rejei��o: Sigla da UF do Emitente diverge da UF autorizadora',
	'248' => 'Rejei��o: UF do Recibo diverge da UF autorizadora',
	'249' => 'Rejei��o: UF da Chave de Acesso diverge da UF autorizadora',
	'250' => 'Rejei��o: UF diverge da UF autorizadora',
	'251' => 'Rejei��o: UF/Munic�pio destinat�rio n�o pertence a SUFRAMA',
	'252' => 'Rejei��o: Ambiente informado diverge do Ambiente de recebimento',
	'253' => 'Rejei��o: Digito Verificador da chave de acesso composta inv�lida',
	'254' => 'Rejei��o: NF-e complementar n�o possui NF referenciada',
	'255' => 'Rejei��o: NF-e complementar possui mais de uma NF referenciada',
	'256' => 'Rejei��o: Uma NF-e da faixa j� est� inutilizada na Base de dados da SEFAZ',
	'257' => 'Rejei��o: Solicitante n�o habilitado para emiss�o da NF-e',
	'258' => 'Rejei��o: CNPJ da consulta inv�lido',
	'259' => 'Rejei��o: CNPJ da consulta n�o cadastrado como contribuinte na UF',
	'260' => 'Rejei��o: IE da consulta inv�lida',
	'261' => 'Rejei��o: IE da consulta n�o cadastrada como contribuinte na UF',
	'262' => 'Rejei��o: UF n�o fornece consulta por CPF',
	'263' => 'Rejei��o: CPF da consulta inv�lido',
	'264' => 'Rejei��o: CPF da consulta n�o cadastrado como contribuinte na UF',
	'265' => 'Rejei��o: Sigla da UF da consulta difere da UF do Web Service',
	'266' => 'Rejei��o: S�rie utilizada n�o permitida no Web Service ',
	'267' => 'Rejei��o: NF Complementar referencia uma NF-e inexistente ',
	'268' => 'Rejei��o: NF Complementar referencia uma outra NF-e Complementar',
	'269' => 'Rejei��o: CNPJ Emitente da NF Complementar difere do CNPJ da NF Referenciada ',
	'270' => 'Rejei��o: C�digo Munic�pio do Fato Gerador: d�gito inv�lido',
	'271' => 'Rejei��o: C�digo Munic�pio do Fato Gerador: difere da UF do emitente',
	'272' => 'Rejei��o: C�digo Munic�pio do Emitente: d�gito inv�lido',
	'273' => 'Rejei��o: C�digo Munic�pio do Emitente: difere da UF do emitente',
	'274' => 'Rejei��o: C�digo Munic�pio do Destinat�rio: d�gito inv�lido',
	'275' => 'Rejei��o: C�digo Munic�pio do Destinat�rio: difere da UF do Destinat�rio',
	'276' => 'Rejei��o: C�digo Munic�pio do Local de Retirada: d�gito inv�lido',
	'277' => 'Rejei��o: C�digo Munic�pio do Local de Retirada: difere da UF do Local de Retirada',
	'278' => 'Rejei��o: C�digo Munic�pio do Local de Entrega: d�gito inv�lido',
	'279' => 'Rejei��o: C�digo Munic�pio do Local de Entrega:  difere da UF do Local de Entrega',
	'280' => 'Rejei��o: Certificado Transmissor inv�lido',
	'281' => 'Rejei��o: Certificado Transmissor Data Validade',
	'282' => 'Rejei��o: Certificado Transmissor sem CNPJ',
	'283' => 'Rejei��o: Certificado Transmissor - erro Cadeia de Certifica��o',
	'284' => 'Rejei��o: Certificado Transmissor revogado',
	'285' => 'Rejei��o: Certificado Transmissor difere ICP-Brasil',
	'286' => 'Rejei��o: Certificado Transmissor erro no acesso a LCR',
	'287' => 'Rejei��o: C�digo Munic�pio do FG - ISSQN: d�gito inv�lido',
	'288' => 'Rejei��o: C�digo Munic�pio do FG - Transporte: d�gito inv�lido',
	'289' => 'Rejei��o: C�digo da UF informada diverge da UF solicitada',
	'290' => 'Rejei��o: Certificado Assinatura inv�lido',
	'291' => 'Rejei��o: Certificado Assinatura Data Validade',
	'292' => 'Rejei��o: Certificado Assinatura sem CNPJ',
	'293' => 'Rejei��o: Certificado Assinatura - erro Cadeia de Certifica��o',
	'294' => 'Rejei��o: Certificado Assinatura revogado',
	'295' => 'Rejei��o: Certificado Assinatura difere ICP-Brasil',
	'296' => 'Rejei��o: Certificado Assinatura erro no acesso a LCR',
	'297' => 'Rejei��o: Assinatura difere do calculado',
	'298' => 'Rejei��o: Assinatura difere do padr�o do Projeto',
	'301' => 'Denegado: Irregularidade fiscal do emitente',
	'302' => 'Denegado: Irregularidade fiscal do destinat�rio',
	'299' => 'Rejei��o: XML da �rea de cabe�alho com codifica��o diferente de UTF-8',
	'401' => 'Rejei��o: CPF do remetente inv�lido',
	'402' => 'Rejei��o: XML da �rea de dados com codifica��o diferente de UTF-8',
	'403' => 'Rejei��o: O grupo de informa��es da NF-e avulsa � de uso exclusivo do Fisco',
	'404' => 'Rejei��o: Uso de prefixo de namespace n�o permitido',
	'405' => 'Rejei��o: C�digo do pa�s do emitente: d�gito inv�lido',
	'406' => 'Rejei��o: C�digo do pa�s do destinat�rio: d�gito inv�lido',
	'407' => 'Rejei��o: O CPF s� pode ser informado no campo emitente para a NF-e avulsa',
	'409' => 'Rejei��o: Campo cUF inexistente no elemento nfeCabecMsg do SOAP Header',
	'410' => 'Rejei��o: UF informada no campo cUF n�o � atendida pelo Web Service',
	'411' => 'Rejei��o: Campo versaoDados inexistente no elemento nfeCabecMsg do SOAP Header',
	'420' => 'Rejei��o: Cancelamento para NF-e j� cancelada',
	'450' => 'Rejei��o: Modelo da NF-e diferente de 55',
	'451' => 'Rejei��o: Processo de emiss�o informado inv�lido',
	'452' => 'Rejei��o: Tipo Autorizador do Recibo diverge do �rg�o Autorizador',
	'453' => 'Rejei��o: Ano de inutiliza��o n�o pode ser superior ao Ano atual',
	'454' => 'Rejei��o: Ano de inutiliza��o n�o pode ser inferior a 2006',
	'478' => 'Rejei��o: Local da entrega n�o informado para faturamento direto de ve�culos novos',
	'502' => 'Rejei��o: Erro na Chave de Acesso - Campo Id n�o corresponde � concatena��o dos campos correspondentes',
	'503' => 'Rejei��o: S�rie utilizada fora da faixa permitida no SCAN (900-999)',
	'504' => 'Rejei��o: Data de Entrada/Sa�da posterior ao permitido',
	'505' => 'Rejei��o: Data de Entrada/Sa�da anterior ao permitido',
	'506' => 'Rejei��o: Data de Sa�da menor que a Data de Emiss�o',
	'507' => 'Rejei��o: O CNPJ do destinat�rio/remetente n�o deve ser informado em opera��o com o exterior ',
	'508' => 'Rejei��o: O CNPJ com conte�do nulo s� � v�lido em opera��o com exterior',
	'509' => 'Rejei��o: Informado c�digo de munic�pio diferente de .9999999. para opera��o com o exterior',
	'510' => 'Rejei��o: Opera��o com Exterior e C�digo Pa�s destinat�rio � 1058 (Brasil) ou n�o informado',
	'511' => 'Rejei��o: N�o � de Opera��o com Exterior e C�digo Pa�s destinat�rio difere de 1058 (Brasil)',
	'512' => 'Rejei��o: CNPJ do Local de Retirada inv�lido',
	'513' => 'Rejei��o: C�digo Munic�pio do Local de Retirada deve ser 9999999 para UF retirada = EX',
	'514' => 'Rejei��o: CNPJ do Local de Entrega inv�lido',
	'515' => 'Rejei��o: C�digo Munic�pio do Local de Entrega deve ser 9999999 para UF entrega = EX',
	'516' => 'Rejei��o: Falha no schema XML . inexiste a tag raiz esperada para a mensagem',
	'517' => 'Rejei��o: Falha no schema XML . inexiste atributo versao na tag raiz da mensagem',
	'518' => 'Rejei��o: CFOP de entrada para NF-e de sa�da',
	'519' => 'Rejei��o: CFOP de sa�da para NF-e de entrada',
	'520' => 'Rejei��o: CFOP de Opera��o com Exterior e UF destinat�rio difere de EX',
	'521' => 'Rejei��o: CFOP n�o � de Opera��o com Exterior e UF destinat�rio � EX',
	'522' => 'Rejei��o: CFOP de Opera��o Estadual e UF emitente difere UF destinat�rio.',
	'523' => 'Rejei��o: CFOP n�o � de Opera��o Estadual e UF emitente igual a UF destinat�rio.',
	'524' => 'Rejei��o: CFOP de Opera��o com Exterior e n�o informado NCM',
	'525' => 'Rejei��o: CFOP de Importa��o e n�o informado dados da DI',
	'526' => 'Rejei��o: CFOP de Exporta��o e n�o informado Local de Embarque',
	'527' => 'Rejei��o: Opera��o de Exporta��o com informa��o de ICMS incompat�vel',
	'528' => 'Rejei��o: Valor do ICMS difere do produto BC e Al�quota',
	'529' => 'Rejei��o: NCM de informa��o obrigat�ria para produto tributado pelo IPI',
	'530' => 'Rejei��o: Opera��o com tributa��o de ISSQN sem informar a Inscri��o Municipal',
	'531' => 'Rejei��o: Total da BC ICMS difere do somat�rio dos itens',
	'532' => 'Rejei��o: Total do ICMS difere do somat�rio dos itens',
	'533' => 'Rejei��o: Total da BC ICMS-ST difere do somat�rio dos itens',
	'534' => 'Rejei��o: Total do ICMS-ST difere do somat�rio dos itens',
	'535' => 'Rejei��o: Total do Frete difere do somat�rio dos itens',
	'536' => 'Rejei��o: Total do Seguro difere do somat�rio dos itens',
	'537' => 'Rejei��o: Total do Desconto difere do somat�rio dos itens',
	'538' => 'Rejei��o: Total do IPI difere do somat�rio dos itens',
	'539' => 'Rejei��o: Duplicidade de NF-e, com diferen�a na Chave de Acesso',
	'540' => 'Rejei��o: CPF do Local de Retirada inv�lido',
	'541' => 'Rejei��o: CPF do Local de Entrega inv�lido',
	'542' => 'Rejei��o: CNPJ do Transportador inv�lido',
	'543' => 'Rejei��o: CPF do Transportador inv�lido',
	'544' => 'Rejei��o: IE do Transportador inv�lida ',
	'545' => 'Rejei��o: Falha no schema XML . vers�o informada na versaoDados do SOAPHeader diverge da vers�o da mensagem',
	'546' => 'Rejei��o: Erro na Chave de Acesso . Campo Id . falta a literal NFe',
	'547' => 'Rejei��o: D�gito Verificador da Chave de Acesso da NF-e Referenciada inv�lido',
	'548' => 'Rejei��o: CNPJ da NF referenciada inv�lido.',
	'549' => 'Rejei��o: CNPJ da NF referenciada de produtor inv�lido.',
	'550' => 'Rejei��o: CPF da NF referenciada de produtor inv�lido.',
	'551' => 'Rejei��o: IE da NF referenciada de produtor inv�lido.',
	'552' => 'Rejei��o: D�gito Verificador da Chave de Acesso do CT-e Referenciado inv�lido',
	'553' => 'Rejei��o: Tipo autorizador do recibo diverge do �rg�o Autorizador.',
	'554' => 'Rejei��o: S�rie difere da faixa 0-899',
	'555' => 'Rejei��o: Tipo autorizador do protocolo diverge do �rg�o Autorizador.',
	'556' => 'Rejei��o: Justificativa de entrada em conting�ncia  n�o deve ser  informada para tipo de emiss�o normal.',
	'557' => 'Rejei��o: A Justificativa de entrada em conting�ncia deve ser informada.',
	'558' => 'Rejei��o: Data de entrada em conting�ncia posterior a data de emiss�o.',
	'559' => 'Rejei��o: UF do Transportador n�o informada',
	'560' => 'Rejei��o: CNPJ base do emitente difere do CNPJ base da primeira NF-e do lote recebido',
	'561' => 'Rejei��o: M�s de Emiss�o informado na Chave de Acesso difere do M�s de Emiss�o da NF-e',
	'562' => 'Rejei��o: C�digo Num�rico informado na Chave de Acesso difere do C�digo Num�rico da NF-e',
	'563' => 'Rejei��o: J� existe pedido de Inutiliza��o com a mesma faixa de inutiliza��o',
	'564' => 'Rejei��o: Total do Produto / Servi�o difere do somat�rio dos itens',
	'565' => 'Rejei��o: Falha no schema XML . inexiste a tag raiz esperada para o lote de NF-e',
	'567' => 'Rejei��o: Falha no schema XML . vers�o informada na versaoDados do SOAPHeader diverge da vers�o do lote de NF-e',
	'568' => 'Rejei��o: Falha no schema XML . inexiste atributo versao na tag raiz do lote de NF-e',
	'629' => 'Rejei��o: Valor do Produto difere do produto Valor Unit�rio de Comercializa��o e Quantidade Comercial',
	'630' => 'Rejei��o: Valor do Produto difere do produto Valor Unit�rio de Tributa��o e Quantidade Tribut�vel',
	'999' => 'Erro n�o catalogado (informar a mensagem de erro capturado no tratamento da exce��o)');

	return $msg_retorno[$i];

}

?>
