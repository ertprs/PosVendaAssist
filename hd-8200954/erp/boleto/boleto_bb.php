<?php
// +----------------------------------------------------------------------+
// | BoletoPhp - Vers�o Beta                                              |
// +----------------------------------------------------------------------+
// | Este arquivo est� dispon�vel sob a Licen�a GPL dispon�vel pela Web   |
// | em http://pt.wikipedia.org/wiki/GNU_General_Public_License           |
// | Voc� deve ter recebido uma c�pia da GNU Public License junto com     |
// | esse pacote; se n�o, escreva para:                                   |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+

// +----------------------------------------------------------------------+
// | Originado do Projeto BBBoletoFree que tiveram colabora��es de Daniel |
// | William Schultz e Leandro Maniezo que por sua vez foi derivado do	  |
// | PHPBoleto de Jo�o Prado Maia e Pablo Martins F. Costa				        |
// | 														                                   			  |
// | Se vc quer colaborar, nos ajude a desenvolver p/ os demais bancos :-)|
// | Acesse o site do Projeto BoletoPhp: www.boletophp.com.br             |
// +----------------------------------------------------------------------+

// +--------------------------------------------------------------------------------------------------------+
// | Equipe Coordena��o Projeto BoletoPhp: <boletophp@boletophp.com.br>              		             				|
// | Desenvolvimento Boleto Banco do Brasil: Daniel William Schultz / Leandro Maniezo / Rog�rio Dias Pereira|
// +--------------------------------------------------------------------------------------------------------+


// ------------------------- DADOS DIN�MICOS DO SEU CLIENTE PARA A GERA��O DO BOLETO (FIXO OU VIA GET) -------------------- //
// Os valores abaixo podem ser colocados manualmente ou ajustados p/ formul�rio c/ POST, GET ou de BD (MySql,Postgre,etc)	//

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../autentica_usuario_empresa.php';

$conta = $_GET['conta'];
if(strlen($conta)==0){
	echo "Ocorreu um erro";
	echo "<script language='JavaScript'>";
		echo "window.close();";
	echo "</script>";

}
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
$res = pg_exec($con,$sql);
//echo $sql;
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
		$agencia                = str_replace("-","",trim(pg_result($res, 0, agencia)));
		$conta                  = str_replace("-","",trim(pg_result($res, 0, conta)));
		$agencia                = substr($agencia,0,strlen($agencia)-1);
		$conta                  = substr($conta,0,strlen($conta)-1);
		$documento              = str_replace("-","",trim($documento));
		$documento              = str_replace("/","",trim($documento));

		if($receber_distribuidor<>0 and strlen($receber_distribuidor)>0){
			$sql = "SELECT nome, cidade, estado, endereco, numero, cnpj
					FROM tbl_posto
					where posto = $receber_distribuidor";

			$xsql = "SELECT	nome ,
							cnpj,
							endereco,
							numero,
							cidade,
							estado,
							cep
					FROM tbl_posto
					where pessoa = $receber_posto";
						//	echo $xsql;
			$xres = @pg_exec($con,$xsql);
			if(pg_numrows($res)>0){
				$sacado_nome     = pg_result($xres,0,nome);
				$sacado_cidade   = pg_result($xres,0,cidade);
				$sacado_estado   = pg_result($xres,0,estado);
				$sacado_endereco = pg_result($xres,0,endereco);
				$sacado_numero   = pg_result($xres,0,numero);
				$sacado_cnpj     = pg_result($xres,0,cnpj);
				$sacado_cep      = pg_result($xres,0,cep);
			}


		}else{
			$sql = "SELECT nome ,cidade, estado, endereco,'' as numero, cnpj
					from tbl_fabrica 
					where fabrica = $receber_fabrica";
			

			$xsql = "SELECT	nome ,
							cnpj,
							endereco,
							numero,
							cidade,
							estado,
							cep
					FROM tbl_pessoa 
					where pessoa = $receber_cliente";
						//	echo $xsql;
			$xres = @pg_exec($con,$xsql);
			if(pg_numrows($res)>0){
				$sacado_nome     = pg_result($xres,0,nome);
				$sacado_cidade   = pg_result($xres,0,cidade);
				$sacado_estado   = pg_result($xres,0,estado);
				$sacado_endereco = pg_result($xres,0,endereco);
				$sacado_numero   = pg_result($xres,0,numero);
				$sacado_cnpj     = pg_result($xres,0,cnpj);
				$sacado_cep      = pg_result($xres,0,cep);
			}

		}
		$res = @pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$cedente_nome     = pg_result($res,0,nome);
			$cedente_cidade   = pg_result($res,0,cidade);
			$cedente_estado   = pg_result($res,0,estado);
			$cedente_endereco = pg_result($res,0,endereco);
			$cedente_numero   = pg_result($res,0,numero);
			$cedente_cnpj     = pg_result($res,0,cnpj);
		}
		

	//	echo $sql;

}

		$sql = "SELECT	multa,
						juros
		FROM tbl_loja_dados 
		WHERE empresa = $login_empresa
		AND   loja    = $login_loja";
		$res = pg_exec($sql);
		if(@pg_numrows($res)>0){
			$multa = trim(pg_result($res,0,multa));
			$juros = trim(pg_result($res,0,juros));
			if(strlen($multa)==0){$multa = "0";}
			if(strlen($juros)==0){$juros = "0";}
		}


// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 5;
$taxa_boleto = 2.95;
//$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
$data_venc = $receber_vencimento;
//$valor_cobrado = "2950,00"; // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

$dadosboleto["nosso_numero"] = $documento;
$dadosboleto["numero_documento"] = $documento;	// Num do pedido ou nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto
$dadosboleto["data_documento"] = $receber_emissao; // Data de emiss�o do Boleto
$dadosboleto["data_processamento"] = $receber_emissao; // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor; 	// Valor do Boleto - REGRA: Com v�rgula e sempre com duas casas depois da virgula


// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = "$sacado_nome";
$dadosboleto["endereco1"] = "$sacado_endereco $sacado_numero";
$dadosboleto["endereco2"] = "$sacado_cidade - $sacado_estado - CEP: $sacado_cep";

// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"] = "Multa de $multa % para pagamento ap�s vencimento.
";
$dadosboleto["demonstrativo2"] = "Juros / Mora de $juros % ao dia.";
$dadosboleto["demonstrativo3"] = "BoletoPhp - http://www.boletophp.com.br";
$dadosboleto["instrucoes4"] = "Emitido pelo sistema Projeto BoletoPhp - www.boletophp.com.br";

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"] = "1";
$dadosboleto["valor_unitario"] = "1";
$dadosboleto["aceite"] = "N";		
$dadosboleto["uso_banco"] = ""; 	
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "DM";


// ---------------------- DADOS FIXOS DE CONFIGURA��O DO SEU BOLETO --------------- //


// DADOS DA SUA CONTA - BANCO DO BRASIL
$dadosboleto["agencia"] = "$agencia"; // Num da agencia, sem digito
$dadosboleto["conta"] = "$conta"; 	// Num da conta, sem digito

// DADOS PERSONALIZADOS - BANCO DO BRASIL
/*$dadosboleto["convenio"] = "7777777";  // Num do conv�nio - REGRA: 6 ou 7 ou 8 d�gitos
$dadosboleto["contrato"] = "999999"; // Num do seu contrato
$dadosboleto["carteira"] = "18";
$dadosboleto["variacao_carteira"] = "-019";  // Varia��o da Carteira, com tra�o (opcional)
*/
$dadosboleto["convenio"] = "";  // Num do conv�nio - REGRA: 6 ou 7 ou 8 d�gitos
$dadosboleto["contrato"] = ""; // Num do seu contrato
$dadosboleto["carteira"] = "18";
$dadosboleto["variacao_carteira"] = "";  // Varia��o da Carteira, com tra�o (opcional)


// TIPO DO BOLETO
$dadosboleto["formatacao_convenio"] = "7"; // REGRA: 8 p/ Conv�nio c/ 8 d�gitos, 7 p/ Conv�nio c/ 7 d�gitos, ou 6 se Conv�nio c/ 6 d�gitos
$dadosboleto["formatacao_nosso_numero"] = "2"; // REGRA: Usado apenas p/ Conv�nio c/ 6 d�gitos: informe 1 se for NossoN�mero de at� 5 d�gitos ou 2 para op��o de at� 17 d�gitos

/*
#################################################
DESENVOLVIDO PARA CARTEIRA 18

- Carteira 18 com Convenio de 8 digitos
  Nosso n�mero: pode ser at� 9 d�gitos

- Carteira 18 com Convenio de 7 digitos
  Nosso n�mero: pode ser at� 10 d�gitos

- Carteira 18 com Convenio de 6 digitos
  Nosso n�mero:
  de 1 a 99999 para op��o de at� 5 d�gitos
  de 1 a 99999999999999999 para op��o de at� 17 d�gitos

#################################################
*/

// SEUS DADOS
$dadosboleto["identificacao"] = "$cedente_nome";
$dadosboleto["cpf_cnpj"] = "$cedente_cnpj";
$dadosboleto["endereco"] = "$cedente_endereco, $cedente_numero";
$dadosboleto["cidade_uf"] = "$cedente_cidade - $cedente_estado";
$dadosboleto["cedente"] = "$cedente_nome";
/*
$dadosboleto["identificacao"] = "BoletoPhp - C�digo Aberto de Sistema de Boletos";
$dadosboleto["cpf_cnpj"] = "";
$dadosboleto["endereco"] = "Rua Central, 123";
$dadosboleto["cidade_uf"] = "Curitiba - PR";
$dadosboleto["cedente"] = "Alcantara & Schmidt Ltda.";*/

// N�O ALTERAR!
include("include/funcoes_bb.php"); 
include("include/layout_bb.php");
?>
<!--
<script language="JavaScript">
	window.print();
</script> -->