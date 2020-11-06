<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

//contas a receber distribuidor = 21716
//5 dias de vencimento
/* TULIO PEDIU PARA DESABILITAR - FABIO - 03-09-2008 */
exit;

$sql = "SELECT contas_receber FROM tbl_contas_receber WHERE distribuidor = 21716 AND posto = $login_posto AND fabrica = 49 AND recebimento IS NULL";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) == 0) {
	$sql = "INSERT INTO tbl_contas_receber (
			documento,
			vencimento,
			valor,
			obs,
			posto,
			fabrica,
			distribuidor,
			remessa
		)VALUES(
			'',
			((current_date + interval '5 day')::date),
			200,
			'Adesão VoIP',
			$login_posto,
			49,
			21716,
			-1
		)";
	$res = pg_exec ($con,$sql);
	
	$sql = "SELECT CURRVAL ('tbl_contas_receber_seq')";
	$res = pg_exec ($con,$sql);
	$contas_receber = pg_result ($res,0,0);
}else{
	$contas_receber = pg_result ($res,0,0);
	
	$sql = "UPDATE tbl_contas_receber SET vencimento = ((current_date + interval '5 day')::date) 
		WHERE contas_receber = $contas_receber";
	$res = pg_exec ($con,$sql);
}

$xsql = "SELECT	nome ,
		cnpj,
		endereco,
		numero,
		cidade,
		estado,
		cep
	FROM tbl_posto 
	WHERE posto = $login_posto";
$res = @pg_exec($con,$xsql);
if(pg_numrows($res)>0){
	$sacado_nome     = pg_result($res,0,nome);
	$sacado_cidade   = pg_result($res,0,cidade);
	$sacado_estado   = pg_result($res,0,estado);
	$sacado_endereco = pg_result($res,0,endereco);
	$sacado_numero   = pg_result($res,0,numero);
	$sacado_cnpj     = pg_result($res,0,cnpj);
	$sacado_cep      = pg_result($res,0,cep);
}

$sql = "SELECT * FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = 49";
$res = pg_exec($con,$sql);
if(pg_numrows($res)==0){


	$digitos    = 2;
	$caracteres = 'abcdefghijklmnopqrstuvwxyz';
	$cp_senha   = ''                          ;
	$y = strlen($caracteres)-1                ;//conta quantos caracteres tem na variavel $caracteres

	for($x=1;$x<=$digitos;$x++){
		$rand = rand(0,$y); 
		$str = substr($caracteres,$rand,1); 
		$cp_senha .= $str;
	}

	//NUMEROS DA SENHA
	$digitos    = 4                    ;
	$caracteres = '1234567890'         ;
	$cp_senha_n = ''                   ;
	$y          = strlen($caracteres)-1;
	
	for($x=1;$x<=$digitos;$x++){
		$rand = rand(0,$y); 
		$str = substr($caracteres,$rand,1);
		$cp_senha_n .= $str;
	}
	$senha = $cp_senha.$cp_senha_n;

	$sql = "INSERT INTO tbl_posto_fabrica
			(	posto,
				fabrica,
				senha,
				distribuidor,
				tipo_posto,
				codigo_posto,
				contato_endereco,
				contato_numero,
				contato_complemento,
				contato_bairro,
				contato_cidade,
				contato_cep,
				contato_estado,
				contato_email,
				nome_fantasia,
				credenciamento
			) VALUES (
				$login_posto,
				49,
				'*',
				21716,
				175,
				(SELECT MAX(codigo_posto::integer)+1 FROM tbl_posto_fabrica WHERE fabrica = 49),
				(SELECT endereco FROM tbl_posto WHERE posto = $login_posto),
				(SELECT numero FROM tbl_posto WHERE posto = $login_posto),
				(SELECT complemento FROM tbl_posto WHERE posto = $login_posto),
				(SELECT bairro FROM tbl_posto WHERE posto = $login_posto),
				(SELECT cidade FROM tbl_posto WHERE posto = $login_posto),
				(SELECT cep FROM tbl_posto WHERE posto = $login_posto),
				(SELECT estado FROM tbl_posto WHERE posto = $login_posto),
				(SELECT email FROM tbl_posto WHERE posto = $login_posto),
				(SELECT nome  FROM tbl_posto WHERE posto = $login_posto),
				'DESCREDENCIADO'
			)";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	
}

/*
$sql = "SELECT
				tbl_contas_receber.contas_receber,
				tbl_contas_receber.documento,
				to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') as emissao,
				to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
				tbl_contas_receber.vencimento as vencimento_bd,
				tbl_contas_receber.valor,
				tbl_contas_receber.valor_dias_atraso,
				to_char(tbl_contas_receber.recebimento,'DD/MM/YYYY') as recebimento,
				tbl_contas_receber.obs,
				tbl_contas_receber.posto,
				tbl_contas_receber.fabrica,
				tbl_contas_receber.cliente,
				tbl_contas_receber.distribuidor,
				tbl_contas_receber.obs          ,
				tbl_posto.nome as cedente,
				tbl_caixa_banco.agencia,
				tbl_caixa_banco.conta,
				tbl_contas_receber.cliente
	FROM tbl_contas_receber 
	JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
	JOIN tbl_caixa_banco on tbl_caixa_banco.caixa_banco = tbl_contas_receber.caixa_banco
	WHERE  tbl_contas_receber.contas_receber=$conta";
	//echo $sql;
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
		$receber_receber		= trim(pg_result($res, 0, contas_receber));
		$receber_documento		= trim(pg_result($res, 0, documento));
		$receber_emissao		= trim(pg_result($res, 0, emissao));
		$receber_vencimento		= trim(pg_result($res, 0, vencimento));
		$receber_vencimento_bd	= trim(pg_result($res, 0, vencimento_bd));
		$receber_valor			= trim(pg_result($res, 0, valor));
		$documento              = trim(pg_result($res, 0, documento));
		$receber_recebimento	= trim(pg_result($res, 0, recebimento));
		$receber_obs			= trim(pg_result($res, 0, obs));
		$receber_posto			= trim(pg_result($res, 0, posto));
		$receber_fabrica		= trim(pg_result($res, 0, fabrica));
		$receber_cliente		= trim(pg_result($res, 0, cliente));
		$obs                    = trim(pg_result($res, 0, obs));
		$receber_distribuidor   = trim(pg_result($res, 0, distribuidor));
		$valor                  = trim(pg_result($res, 0, valor));
		$valor		     		= number_format($valor,2,',','');
		$agencia         = str_replace("-","",trim(pg_result($res, 0, agencia)));
		$conta           = str_replace("-","",trim(pg_result($res, 0, conta)));
		$xagencia        = substr($agencia,0,strlen($agencia)-1);
		$agencia_digito  = substr($agencia,strlen($agencia)-1,strlen($agencia));
		$xconta          = substr($conta,0,strlen($conta)-1);
		$conta_digito    = substr($conta,strlen($conta)-1,strlen($conta));

		$documento           = str_replace("-","",trim($documento));
		$documento           = str_replace("/","",trim($documento));

}

*/




$nosso_numero = $contas_receber;
$documento    = $contas_receber;


$valor = 200;
$xagencia       = '2155'; // Num da agencia, sem digito
$agencia_digito = '5'; // Digito do Num da agencia
$xconta         = '0013602'; 	// Num da conta, sem digito
$conta_digito   = '6'; 	// Digito do Num da conta

$cedente_nome     = "Teracell Network";
$cedente_cnpj     = "03.152.629/0001-45";
$cedente_endereco = "Av. Hercules Galetti";
$cedente_numero   = "382";
$cedente_cidade   = "Marília";
$cedente_estado   = "SP";





// DADOS PERSONALIZADOS - Bradesco
$dadosboleto["conta_cedente"]    = $xconta; // ContaCedente do Cliente, sem digito (Somente Números)
$dadosboleto["conta_cedente_dv"] = $conta_digito; // Digito da ContaCedente do Cliente
$dadosboleto["carteira"]         = "06";  // Código da Carteira



// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 5;
$taxa_boleto = 0;
$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
//$data_venc = $receber_vencimento;
echo $data_venc;
//$valor_cobrado = "2950,00"; // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = $valor;
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto  = number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
echo "$valor_boleto";

$dadosboleto["nosso_numero"]       = $nosso_numero;  // Nosso numero sem o DV - REGRA: Máximo de 11 caracteres!
$dadosboleto["numero_documento"]   = $documento;     // Num do pedido ou do documento = Nosso numero
$dadosboleto["data_vencimento"]    = $data_venc;     // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"]     = date("d/m/Y");  // Data de emissão do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y");  // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"]       = $valor_boleto;  // Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
/*
$dadosboleto["sacado"]    = "José da Silva";
$dadosboleto["endereco1"] = "Rua ABC";
$dadosboleto["endereco2"] = "São Paulo - SP - CEP: 010200-000";
*/
$dadosboleto["sacado"]    = "$sacado_nome";
$dadosboleto["endereco1"] = "$sacado_endereco $sacado_numero";
$dadosboleto["endereco2"] = "$sacado_cidade - $sacado_estado - CEP: $sacado_cep";



// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"]   = "Tele-VoIP"; //"Adesão ao sistema Tele-VoIP";
$dadosboleto["demonstrativo2"]   = ""; //"Pagamento da primeira mensalidade";
//$dadosboleto["demonstrativo3"]   = "Ligações Válidas até dd/mm/yyyy";
$dadosboleto["instrucoes1"] = "Não receber após vencimento.";
$dadosboleto["instrucoes2"] = "Não cobrar juros nem multa.";
//$dadosboleto["instrucoes4"] = "Emitido pelo sistema Projeto BoletoPhp - www.boletophp.com.br";

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"]     = "001";
$dadosboleto["valor_unitario"] = $valor_boleto;
$dadosboleto["aceite"]         = "";
$dadosboleto["uso_banco"]      = "";
$dadosboleto["especie"]        = "R$";
$dadosboleto["especie_doc"]    = "";


// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


// DADOS DA SUA CONTA - Bradesco
$dadosboleto["agencia"]    = $xagencia; // Num da agencia, sem digito
$dadosboleto["agencia_dv"] = $agencia_digito; // Digito do Num da agencia
$dadosboleto["conta"]      = $xconta; 	// Num da conta, sem digito
$dadosboleto["conta_dv"]   = $conta_digito; 	// Digito do Num da conta



// DADOS PERSONALIZADOS - Bradesco
$dadosboleto["conta_cedente"]    = $xconta; // ContaCedente do Cliente, sem digito (Somente Números)
$dadosboleto["conta_cedente_dv"] = $conta_digito; // Digito da ContaCedente do Cliente
$dadosboleto["carteira"]         = "06";  // Código da Carteira

// SEUS DADOS
$dadosboleto["identificacao"] = "$cedente_nome";
$dadosboleto["cpf_cnpj"]      = "$cedente_cnpj";
$dadosboleto["endereco"]      = "$cedente_endereco, $cedente_numero";
$dadosboleto["cidade_uf"]     = "$cedente_cidade - $cedente_estado";
$dadosboleto["cedente"]       = "$cedente_nome";




// NÃO ALTERAR!
include("/var/www/assist/www/erp/boleto/include/funcoes_bradesco.php"); 
include("/var/www/assist/www/erp/boleto/include/layout_bradesco.php");

$nosso_numero = "0000000000000" . trim ($nosso_numero);
$nosso_numero = substr ($nosso_numero,strlen($nosso_numero)-11);

$sql = "UPDATE tbl_contas_receber 
	SET nosso_numero = '$nosso_numero' || $dv_nosso_numero 
	WHERE contas_receber = $contas_receber";
$res = pg_exec ($con,$sql);

?>