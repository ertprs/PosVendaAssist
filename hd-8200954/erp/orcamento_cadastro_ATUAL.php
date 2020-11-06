<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

include '../funcoes.php';

if (strlen($_GET['orcamento']) > 0)
	$orcamento = trim($_GET['orcamento']);

if (strlen($_GET['tipo_orcamento']) > 0)
	$tipo_orcamento = strtoupper(trim($_GET['tipo_orcamento']));
else
	$tipo_orcamento = strtoupper(trim($_POST['tipo_orcamento']));

if (strlen($tipo_orcamento)==0 AND strlen($orcamento)==0){
	$msg_erro = "Selecione o tipo do orçamento!";
}

$referencia = trim($_GET['referencia']);
if (strlen($referencia)==0) $referencia = trim($_POST['referencia']);
$descricao = trim($_GET['descricao']);
if (strlen($descricao)==0) $descricao = trim($_POST['descricao']);


if (strlen($_GET['debug']) > 0)	$debug = trim($_GET['debug']);

$data_abertura = date("d/m/Y");

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

	$valores_sub_total   = "0.00";
	$valores_brindes     = "0.00";
	$valores_frete       = "0.00";
	$valores_descontos   = "0.00";
	$valores_acrescimos  = "0.00";
	$valores_acrescimos  = "0.00";
	$valores_total_geral = "0.00";

/*

2  | Aguardando Diagnostico
3  | Em Diagnostico        
4  | Aguardando Pecas      
5  | Sem conserto          
6  | Reparado              
7  | Aguardando reparo     
11 | Devolvido             
9  | Novo Diagnostico      
8  | Sem conserto          

*/


/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($orcamento) > 0) {
	$sql = "SELECT	tbl_orcamento.orcamento                                         ,
			TO_CHAR(tbl_orcamento.data_digitacao,'DD/MM/YYYY') AS data_digitacao    ,
			tbl_orcamento.cliente                                                   ,
			tbl_orcamento.consumidor_nome                                           ,
			tbl_orcamento.consumidor_fone                                           ,
			tbl_orcamento.vendedor                                                  ,
			tbl_orcamento.total_mao_de_obra                                         ,
			tbl_orcamento.total_pecas                                               ,
			tbl_orcamento.brinde                                                    ,
			tbl_orcamento.frete                                                     ,
			tbl_orcamento.desconto                                                  ,
			tbl_orcamento.acrescimo                                                 ,
			tbl_orcamento.total                                                     ,
			tbl_orcamento.aprovado                                                  ,
			tbl_orcamento.faturamento                                               ,
			tbl_status.descricao as status                                          ,
			TO_CHAR(tbl_orcamento.data_previsao,'DD/MM/YYYY')  AS data_previsao     ,
			tbl_pessoa.nome                                    AS vendedor_login    ,
			to_char(data_aprovacao,'DD/MM/YYYY')               AS data_aprovacao    ,
			tbl_orcamento.tipo_aprovacao                                            ,
			TO_CHAR(tbl_orcamento.data_reprovacao,'DD/MM/YYYY') AS data_reprovacao  ,
			tbl_orcamento.motivo_reprovacao                                         ,
			tbl_orcamento.empregado_aprovacao                                       ,
			tbl_orcamento.aprovacao_responsavel                                     ,
			tbl_orcamento.condicao_pagamento                                        ,
			emp2.nome                                   AS empregado_aprovacao_login
		FROM  tbl_orcamento
		JOIN tbl_empregado ON tbl_empregado.empregado=tbl_orcamento.vendedor
		JOIN tbl_pessoa    ON tbl_pessoa.pessoa = tbl_empregado.pessoa
		JOIN tbl_empregado emp ON emp.empregado = tbl_orcamento.empregado_aprovacao
		JOIN tbl_pessoa    emp2 ON emp2.pessoa  = emp.pessoa
		LEFT JOIN tbl_status ON tbl_status.status = tbl_orcamento.status
		WHERE tbl_orcamento.orcamento = $orcamento
		AND   tbl_orcamento.loja      = $login_loja";
	//echo $sql."<br>";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$orcamento                 = trim(pg_result ($res,0,orcamento))          ;
		$data_digitacao            = trim(pg_result ($res,0,data_digitacao))     ;
		$cliente                   = trim(pg_result ($res,0,cliente))            ;
		$consumidor_nome           = trim(pg_result ($res,0,consumidor_nome))    ;
		$consumidor_fone           = trim(pg_result ($res,0,consumidor_fone))    ; 
		$vendedor                  = trim(pg_result ($res,0,vendedor))           ; #id
		$vendedor_login            = trim(pg_result ($res,0,vendedor_login))     ; #nome
		$status                    = trim(pg_result ($res,0,status))             ; #nome

		$data_previsao             = trim(pg_result ($res,0,data_previsao))      ; #Previsão de entrega do prod.
		$condicao_pagamento        = trim(pg_result ($res,0,condicao_pagamento));
		$faturamento               = trim(pg_result ($res,0,faturamento));
		$total_geralzao            = trim(pg_result ($res,0,total));
		
		//APROVAÇÂO
		$aprovado                      = trim(pg_result($res,0,aprovado));
		$apro_data_aprovacao           = trim(pg_result($res,0,data_aprovacao));
		$apro_tipo_aprovacao           = trim(pg_result($res,0,tipo_aprovacao));
		$apro_data_reprovacao          = trim(pg_result($res,0,data_reprovacao));
		$apro_motivo_reprovacao        = trim(pg_result($res,0,motivo_reprovacao));
		$apro_empregado_aprovacao      = trim(pg_result($res,0,empregado_aprovacao));
		$apro_empregado_aprovacao_login= trim(pg_result($res,0,empregado_aprovacao_login));
		$apro_aprovacao_responsavel    = trim(pg_result($res,0,aprovacao_responsavel));

		// VALORES  
		$valores_brindes      = number_format(trim(pg_result($res,0,brinde)),2,".","");
		$valores_frete        = number_format(trim(pg_result($res,0,frete)),2,".","");
		$valores_descontos    = number_format(trim(pg_result($res,0,desconto)),2,".","");
		$valores_acrescimos   = number_format(trim(pg_result($res,0,acrescimo)),2,".","");

		if (strlen($valores_brindes)==0)    $valores_brindes=0.00;
		if (strlen($valores_frete)==0)      $valores_frete=0.00;
		if (strlen($valores_descontos)==0)  $valores_descontos=0.00;
		if (strlen($valores_acrescimos)==0) $valores_acrescimos=0.00;

		$data_abertura=$data_digitacao;

		if (strlen($status)==0)$status="-";


		## VERIFICA SE JÁ FOI FATURADO
		if (strlen($faturamento)>0){
			$sql = "SELECT
						faturamento,
						emissao,
						total_nota
			FROM tbl_faturamento
			WHERE faturamento = $faturamento
			AND fabrica=$login_empresa";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$faturamento        = trim (pg_result ($res,0,faturamento));
				$faturamento_data   = trim (pg_result ($res,0,emissao));
				$faturamento_total  = trim (pg_result ($res,0,total_nota));
			}
		}

		## VERIFICA SE JÁ GEROU CONTAS A RECEBER
		if (strlen($orcamento)>0){
			$sql = "SELECT
						contas_receber,
						valor,
						to_char(recebimento,'DD/MM/YYYY') as recebimento,
						valor_recebido
			FROM tbl_contas_receber
			WHERE orcamento = $orcamento
			AND fabrica=$login_empresa";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$contas_receber             = trim (pg_result ($res,0,contas_receber));
				$contas_receber_valor_total = trim (pg_result ($res,0,valor));
				$contas_receber_data        = trim (pg_result ($res,0,recebimento));
				$contas_receber_valor_pago  = trim (pg_result ($res,0,valor_recebido));
			}
		}

		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA ORCAMENTO
		if (strlen($cliente) > 0 ) {
			$sql = "SELECT
						tbl_pessoa.pessoa,
						tbl_pessoa.empresa,
						tbl_pessoa.nome,
						tbl_pessoa.cnpj,
						tbl_pessoa.endereco,
						tbl_pessoa.numero,
						tbl_pessoa.complemento,
						tbl_pessoa.bairro,
						tbl_pessoa.cidade,
						tbl_pessoa.estado,
						tbl_pessoa.pais,
						tbl_pessoa.fone_residencial,
						tbl_pessoa.fone_comercial,
						tbl_pessoa.cel,
						tbl_pessoa.fax,
						tbl_pessoa.email,
						tbl_pessoa.nome_fantasia,
						tbl_pessoa.ie,
						tbl_pessoa.cep
			FROM tbl_pessoa
			JOIN tbl_pessoa_cliente USING(pessoa)
			WHERE tbl_pessoa.pessoa = $cliente
			AND tbl_pessoa_cliente.empresa = $login_empresa";
			//echo $sql."<br>";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$cliente_cliente     = trim (pg_result ($res,0,pessoa));
				$cliente_empresa     = trim (pg_result ($res,0,empresa));
				$cliente_nome        = trim (pg_result ($res,0,nome));

				if (strtoupper($cliente_nome)!="CONSUMIDOR"){
					$cliente_endereco    = trim (pg_result ($res,0,endereco));
					$cliente_cnpj        = trim (pg_result ($res,0,cnpj));
					$cliente_endereco    = trim (pg_result ($res,0,endereco));
					$cliente_numero      = trim (pg_result ($res,0,numero));
					$cliente_complemento = trim (pg_result ($res,0,complemento));
					$cliente_bairro      = trim (pg_result ($res,0,bairro));
					$cliente_cidade      = trim (pg_result ($res,0,cidade));
					$cliente_estado      = trim (pg_result ($res,0,estado));
					$cliente_pais        = trim (pg_result ($res,0,pais));
					$cliente_fone_residencial    = trim (pg_result ($res,0,fone_residencial));
					$cliente_fone_comercial      = trim (pg_result ($res,0,fone_comercial));
					$cliente_cel          = trim (pg_result ($res,0,cel));
					$cliente_fax          = trim (pg_result ($res,0,fax));
					$cliente_email        = trim (pg_result ($res,0,email));
					$cliente_nome_fantasia= trim (pg_result ($res,0,nome_fantasia));
					$cliente_ie           = trim (pg_result ($res,0,ie));
					$cliente_cep          = trim (pg_result ($res,0,cep));
				}
			}
		}

		$cliente_nome              = $consumidor_nome;
		$cliente_fone_residencial  = $consumidor_fone;

		$sql = "SELECT  tbl_orcamento_os.tecnico   ,
				tbl_orcamento_os.fabrica           ,
				tbl_orcamento_os.fabricante_nome   ,
				tbl_orcamento_os.abertura          ,
				tbl_orcamento_os.fechamento        ,
				tbl_orcamento_os.defeito_reclamado ,
				tbl_orcamento_os.defeito_constatado,
				tbl_orcamento_os.solucao           ,
				tbl_orcamento_os.marca             ,
				tbl_orcamento_os.produto           ,
				tbl_orcamento_os.produto_descricao ,
				tbl_orcamento_os.serie             ,
				tbl_orcamento_os.aparencia         ,
				tbl_orcamento_os.acessorios        ,
				tbl_orcamento_os.revenda           ,
				to_char(tbl_orcamento_os.data_nf,'DD/MM/YYYY') AS data_nf,
				tbl_orcamento_os.nf                ,
				to_char(tbl_orcamento_os.data_visita,'DD/MM/YYYY') AS data_visita,
				to_char(tbl_orcamento_os.data_visita,'HH24:MI') AS hora_visita,
				CASE WHEN CURRENT_TIMESTAMP - tbl_orcamento_os.data_visita > 0 THEN 'sim' ELSE 'nao' END AS efetivar_visita,
				tbl_orcamento_os.reincidencia      ,
				tbl_orcamento_os.status_visita     ,
				tbl_produto.referencia             ,
				tbl_produto.descricao              ,
				tbl_linha.fabrica as fabrica_produto,
				tbl_status_os.descricao as status
			FROM tbl_orcamento_os
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_linha USING(linha)
			LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_orcamento_os.status
			WHERE tbl_orcamento_os.orcamento = $orcamento";
		//echo $sql."<br>";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
			$tipo_orcamento="FORA_GARANTIA";
			$tecnico            = trim (pg_result ($res,0,tecnico))           ;
			$fabrica            = trim (pg_result ($res,0,fabrica))           ;
			$fabricante_nome    = trim (pg_result ($res,0,fabricante_nome))   ;
			$abertura           = trim (pg_result ($res,0,abertura))          ;
			$fechamento         = trim (pg_result ($res,0,fechamento))        ;
			$defeito_reclamado  = trim (pg_result ($res,0,defeito_reclamado)) ;
			$defeito_constatado = trim (pg_result ($res,0,defeito_constatado));
			$solucao            = trim (pg_result ($res,0,solucao))           ;
			$fabrica_produto    = trim (pg_result ($res,0,fabrica_produto))   ;
			$marca_produto      = trim (pg_result ($res,0,marca))             ;
			$produto            = trim (pg_result ($res,0,produto))           ;
			$produto_referencia = trim (pg_result ($res,0,referencia));
			$produto_descricao  = trim (pg_result ($res,0,produto_descricao)) ;
			$produto_serie      = trim (pg_result ($res,0,serie))             ;
			$produto_aparencia  = trim (pg_result ($res,0,aparencia))         ;
			$produto_acessorios = trim (pg_result ($res,0,acessorios))        ;
			$revenda            = trim (pg_result ($res,0,revenda))           ;
			$data_nf            = trim (pg_result ($res,0,data_nf))           ;
			$nota_fiscal        = trim (pg_result ($res,0,nf))                ;
			$reincidencia       = trim (pg_result ($res,0,reincidencia))      ;
			$data_visita        = trim (pg_result ($res,0,data_visita))       ;
			$hora_visita        = trim (pg_result ($res,0,hora_visita))       ;
			$visita_status      = trim (pg_result ($res,0,status_visita))     ;
			$efetivar_visita    = trim (pg_result ($res,0,efetivar_visita))   ;
			$status_os         = trim (pg_result ($res,0,status))   ;
		}else{
			$sql22 = "SELECT  *
					FROM tbl_orcamento_venda
					WHERE orcamento = $orcamento";
			$res22 = pg_exec ($con,$sql22);
			if (pg_numrows ($res22) >0) {
				$tipo_orcamento="ORCA_VENDA";
			}else{
				$tipo_orcamento="VENDA";
			}
		}

	}
}


//if(strlen($os)==0) $body_onload = "onload = 'javascript: document.frm_os.posto_codigo.focus()'";
$title       = "Cadastro de Ordem de Serviço - ADMIN"; 

include "menu.php";
?>

<script language='javascript'>


function adiconarPecaTbl() {

	if (document.getElementById('add_qtde').value==''){
		alert('Informe a quantidade');
		return false;
	}
	if (document.getElementById('add_preco').value==''){
		alert('Informe o preço unitário');
		return false;
	}

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	// inicio da tabela
	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// coluna 1 - codigo do item
	var celula = criaCelula(document.getElementById('add_referencia').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'orcamento_item_' + iteration);
	el.setAttribute('id', 'orcamento_item_' + iteration);
	el.setAttribute('value','');
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'peca_' + iteration);
	el.setAttribute('id', 'peca_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'referencia_peca_' + iteration);
	el.setAttribute('id', 'referencia_peca_' + iteration);
	el.setAttribute('value',document.getElementById('add_referencia').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'descricao_peca_' + iteration);
	el.setAttribute('id', 'descricao_peca_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca_descricao').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'peca_preco_' + iteration);
	el.setAttribute('id', 'peca_preco_' + iteration);
	el.setAttribute('value',document.getElementById('add_preco').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'peca_qtde_' + iteration);
	el.setAttribute('id', 'peca_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('add_qtde').value);
	celula.appendChild(el);

	/*
	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'peca_qtde_' + iteration);
	el.setAttribute('id', 'peca_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('add_qtde').value);
	celula.appendChild(el);
	*/

	 linha.appendChild(celula);

	/*
	var celula = criaCelula(document.getElementById('add_referencia').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);
	*/

	// coluna 2 DESCRIÇÃO
	var celula = criaCelula(document.getElementById('add_peca_descricao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 3 QTDE
	var qtde = document.getElementById('add_qtde').value;
	var celula = criaCelula(qtde);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 4 - preço
	var valor = parseFloat(document.getElementById('add_preco').value).toFixed(2);
	var celula = criaCelula(valor);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	//  coluna 5 - ESTOQUE
	var estoque = document.getElementById('peca_estoque').value;
	celula = criaCelula(estoque);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	//  coluna 6 - DEFEITO
	//	var servico_realizado_item =document.getElementById('add_defeito').options[document.getElementById('add_defeito').selectedIndex].text;
	//	celula = criaCelula(servico_realizado_item);
	//	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	//	linha.appendChild(celula);

	//  coluna 6 - QTDE COMPRA
	var quantidade_entregar =document.getElementById('peca_qtde_entrega').value;
	celula = criaCelula(quantidade_entregar);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	//  coluna 7 - SERVIÇO
	//	celula = criaCelula(document.getElementById('add_servico_realizado').options[document.getElementById('add_servico_realizado').selectedIndex].text);
	//	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	//	linha.appendChild(celula);

	//  coluna 7 -  PREVISÃO
	var celula = criaCelula(document.getElementById('peca_data_previsao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);


	//  coluna 8 - TOTAL
	var total_valor_peca = parseFloat(qtde*valor).toFixed(2);
	celula = criaCelula(total_valor_peca);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 9 - ações
	var celula = document.createElement('td');
	//   pickLink=document.createElement('a');
	//   pickText=document.createTextNode('Excluir');
	//   pickLink.appendChild(pickText);
	//   pickLink.setAttribute('title',iteration);
	//   pickLink.setAttribute('href','#');
	//   pickLink.onclick=function(){removerPeca(this);return false;};
	//   celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	//   celula.appendChild(pickLink);

	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerPeca(this,total_valor_peca);};
	celula.appendChild(el);

	// fim da linha
	linha.appendChild(celula);
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	//linha.style.cssText = 'color: #404e2a;';
	tbl.appendChild(tbody);

	// incrementa a qtde
	document.getElementById('qtde_item').value++;

	//limpa form de add mao de obra
	document.getElementById('add_referencia').value='';
	document.getElementById('add_peca_descricao').value='';
	document.getElementById('add_qtde').value='';
	document.getElementById('add_preco').value='';
	document.getElementById('peca_estoque').value='';
	document.getElementById('peca_qtde_entrega').value='';
	document.getElementById('peca_data_previsao').value='';
	//	document.getElementById('add_defeito').selectedIndex=0;
	//	document.getElementById('add_servico_realizado').selectedIndex=0;

	// atualiza os totalizador
	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) + parseFloat(total_valor_peca);
	document.getElementById('valor_total_itens').innerHTML = parseFloat(aux_valor).toFixed(2);
	recalcular()

	document.getElementById('add_referencia').focus();
}

function removerPeca(iidd,valor){
	//	var tbl = document.getElementById('tbl_pecas');
	//	var lastRow = tbl.rows.length;
	//	if (lastRow > 2){
	//		tbl.deleteRow(iidd.title);
	//		document.getElementById('qtde_item').value--;
	//	}
	var tbl = document.getElementById('tbl_pecas');
	var oRow = iidd.parentElement.parentElement;		
	tbl.deleteRow(oRow.rowIndex);
	document.getElementById('qtde_item').value--;

	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) - parseFloat(valor);
	document.getElementById('valor_total_itens').innerHTML = parseFloat(aux_valor).toFixed(2);
	recalcular();

}

function adicionaLinha() {

	if(document.getElementById('mao_de_obra_valor').value=="") { alert('Selecione a mão de obra'); return false}
	if(document.getElementById('txt_qtde_mao_obra').value=="") { alert('Informe a quantidade'); return false}
	if(document.getElementById('txt_mao_obra').value=="")      { alert('Informe o valor'); return false}


	var tbl = document.getElementById('tbl_mo');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	var celula = criaCelula(document.getElementById('txt_mao_obra').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'servico_' + iteration);
	el.setAttribute('id', 'servico_' + iteration);
	el.setAttribute('value',document.getElementById('txt_mao_obra').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'mao_valor_' + iteration);
	el.setAttribute('id', 'mao_valor_' + iteration);
	el.setAttribute('value',document.getElementById('mao_de_obra_valor').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'mao_qtde_' + iteration);
	el.setAttribute('id', 'mao_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('txt_qtde_mao_obra').value);
	celula.appendChild(el);

	linha.appendChild(celula);

	// coluna 1 - Código da MO
	celula = criaCelula(document.getElementById('txt_mao_obra').options[document.getElementById('txt_mao_obra').selectedIndex].text);
	celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 2 - Data
	/*var curDateTime = new Date();
	var data_insercao = curDateTime.getDate()+"/"+curDateTime.getMonth()+"/"+curDateTime.getYear();
	var celula = criaCelula(data_insercao);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);*/

	// coluna 3 - Valor
	var valorMao = parseFloat(document.getElementById('mao_de_obra_valor').value).toFixed(2);
	var celula = criaCelula(valorMao);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 4 - Qtde
	var celula = criaCelula(document.getElementById('txt_qtde_mao_obra').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 5 - Total
	var total_mo = valorMao*document.getElementById('txt_qtde_mao_obra').value;
	celula = criaCelula(parseFloat(total_mo).toFixed(2));
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 6 - Ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	//   pickLink=document.createElement('a');
	//   pickText=document.createTextNode('Excluir');
	//   pickLink.appendChild(pickText);
	//   pickLink.setAttribute('title',iteration);
	//   pickLink.setAttribute('href','#');
	//   pickLink.onclick=function(){removerMO(this);};
	//   celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	//   celula.appendChild(pickLink);

	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerMO(this,total_mo);};
	celula.appendChild(el);
	linha.appendChild(celula);

	// finaliza linha da tabela
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	/*linha.style.cssText = 'color: #404e2a;';*/
	tbl.appendChild(tbody);


	//limpa form de add mao de obra
	document.getElementById('mao_de_obra_valor').value='';
	document.getElementById('txt_qtde_mao_obra').value='';
	document.getElementById('txt_mao_obra').selectedIndex=0;

	// atualizar o totalizador
	var aux_valor = document.getElementById('valor_total_mo').innerHTML;
	aux_valor = parseFloat(aux_valor) + total_mo;
	document.getElementById('valor_total_mo').innerHTML = parseFloat(aux_valor).toFixed(2);
	recalcular()

	// incrementa qtde de MO
	document.getElementById('qtde_mo').value++;

	document.getElementById('txt_mao_obra').focus();

}


function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function removerMO(iidd,valor){
	var tbl = document.getElementById('tbl_mo');
//	var lastRow = tbl.rows.length;
//	if (lastRow > 2){
//		tbl.deleteRow(iidd.title);
//		document.getElementById('qtde_mo').value--;
//	}
//	var current = window.event.srcElement;
//	while ( (current = current.parentElement)  && current.tagName !="TR");
//		current.parentElement.removeChild(current);

	/* src refers to the input button that was clicked.	
	   to get a reference to the containing <tr> element,
	   get the parent of the parent (in this case case <tr>)
	*/

	var oRow = iidd.parentElement.parentElement;

	tbl.deleteRow(oRow.rowIndex);
	document.getElementById('qtde_mo').value--;

	// atualizar o totalizador
	var aux_valor = document.getElementById('valor_total_mo').innerHTML;
	aux_valor = parseFloat(aux_valor) - valor;
	document.getElementById('valor_total_mo').innerHTML = parseFloat(aux_valor).toFixed(2);
	recalcular()
}

function ajustar_data(input , evento){
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
	}
	if ( tecla == 13) return false; 
	if ((tecla<48)||(tecla>57)){
		return false;
	}
	key = String.fromCharCode(tecla); 
	input.value = input.value+key;
	temp="";
	for (var i = 0; i<input.value.length;i++ ){
		if (temp.length==2) temp=temp+"/";
		if (temp.length==5) temp=temp+"/";
		if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
			temp=temp+input.value.substr(i,1);
		}
	}
	input.value = temp.substr(0,10);
	return false;
}
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function recalcular(){

	var valor_total_mo=0;

	if (document.getElementById('valor_total_mo')){
		valor_total_mo     = parseFloat(document.getElementById('valor_total_mo').innerHTML);
	}

	var valor_total_itens  = parseFloat(document.getElementById('valor_total_itens').innerHTML);
	var aux_cal            = valor_total_mo+valor_total_itens;
	
	document.getElementById('valores_sub_total').innerHTML = parseFloat(aux_cal).toFixed(2);

	var valores_sub_total  = parseFloat(document.getElementById('valores_sub_total').innerHTML);
	var valores_brindes    = parseFloat(document.getElementById('valores_brindes').innerHTML);
	var valores_frete      = parseFloat(document.getElementById('valores_frete').innerHTML);
	var valores_descontos  = parseFloat(document.getElementById('valores_descontos').value);
	var valores_acrescimos = parseFloat(document.getElementById('valores_acrescimos').value);
	var valores_total_geral= parseFloat(document.getElementById('valores_total_geral').innerHTML);

	valores_total_geral = valores_sub_total+valores_frete+valores_acrescimos-valores_descontos;

	document.getElementById('valores_total_geral').innerHTML = parseFloat(valores_total_geral).toFixed(2);

}

</script>

<!--========================= AJAX ==================================-->
<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='../ajax_cep.js'></script>
<script language='javascript' src='ajax_orcamento.js'></script>
<? include "javascript_pesquisas.php" ?>

<style>

a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}

.LabelTitulo{
	font-family: Verdana;
	font-size: 14px;
	font-weight:bold;
}

.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

#tabela_totais span{
	font-size:14px;
	font-weight:bold;
	padding-right:10px.
}

</style>

<!-- CAMPOS PARA ABRIR ORÇAMENTO  -->

<? if (1===4) {?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr >
			<td width='20'></td>
			<td nowrap class='Label'><b>Abrir Orçamento</b></td>
			<td nowrap class='Label'>
				<select name='abrir_orcamento' class="Caixa" onchange="javascript:
				if(this.value!=''){document.location='<? echo $PHP_SELF ?>?orcamento='+this.value}
					">
					<option value=''></option>
					<?php
					$sql = "SELECT	LPAD(orcamento,4,'0') as orcamento,
									to_char(data_digitacao,'DD/MM/YYYY') as data
							FROM   tbl_orcamento
							WHERE empresa=$login_empresa
							ORDER BY orcamento DESC";
					$res = pg_exec ($con,$sql) ;
					for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
						$orcamentoY = trim(pg_result ($res,$x,orcamento));
						$data       = trim(pg_result ($res,$x,data));

						echo "<option value='$orcamentoY'>";
						echo "$orcamentoY - $data";
						echo "</option>";
					}				
					?>
				</select>
			</td>
			<td width='20'></td>
			<td nowrap class='Label'>
				<a href='<? echo $PHP_SELF?>?tipo_orcamento=orca_venda'>Novo Orçamento de Venda</a><br>
				<a href='<? echo $PHP_SELF?>?tipo_orcamento=fora_garantia'>Novo Orçamento Fora de Garantia</a><br>
				<a href='<? echo $PHP_SELF?>?tipo_orcamento=venda'>Nova Venda</a><br>
			</td>
		</tr>
		</table>

	</td>
</tr>
</table>
<br>
<? } ?>
<!-- INICIO DA PAGINA  -->

<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input class="Caixa" type="hidden" name="orcamento"  id="orcamento" value="<? echo $orcamento ?>">
<input class="Caixa" type="hidden" name="tipo_orcamento" value="<? echo $tipo_orcamento ?>">

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
			<tr >
			<td width='20'></td>
			<td nowrap class='Label' width='80px'>Data Abertura</td>
			<td><input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="Caixa" tabindex="0" READONLY ><font size='-3' COLOR='#000099'></td>
			<td width='20'></td>
			<td>
				<?
				echo "<b>$vendedor_login</b>";
				echo "<input type='hidden' name='vendedor' value=''>";
				?>
			</td>

				<td class='Label'>
				</td>
				<td class='LabelTitulo' nowrap colspan='2' align='right'>
					<b>
					<?
						if($tipo_orcamento=='FORA_GARANTIA'){
							echo "<INPUT TYPE='hidden' NAME='tipo_orcamento' value='FORA_GARANTIA'>";
							echo "Ordem de Serviço Fora da Garantia";
						}
						if($tipo_orcamento=='ORCA_VENDA'){
							echo "<INPUT TYPE='hidden' NAME='tipo_orcamento' value='ORCA_VENDA'>";
							echo "Orçamento de Venda";
						}
						if($tipo_orcamento=='VENDA'){
							echo "<INPUT TYPE='hidden' NAME='tipo_orcamento' value='VENDA'>";
							echo "Venda";
						}
					?>
					</b>
					<?
						if (strlen($orcamento)>0){
							echo "<b style='border:2px solid black;padding:3px;font-size:16'>$orcamento</b>";
						}
					?>
				</td>
			</tr>
		</table>

	</td>
</tr>
<tr><td><img height="6" width="16" src="../imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<!-- Informações do Consumidor  -->
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">

		<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>
		<tr>
			<td rowspan='8' width='20px' valign='top'><img src='imagens/titulo_cliente.gif'></td>
		</tr>
		<tr>
			<td class='Label'>Nome:</td>

			<td><input class="Caixa" type="text" name="cliente_nome" size="40" maxlength="50" value="<? echo $cliente_nome ?>">
			<input type="hidden" name="cliente_cliente" value="<? echo $cliente_cliente ?>">
			<img src='../imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.cliente_nome,"nome")'  style='cursor: pointer'>
			</td>

			<td class='Label'>CPF / CNPJ: </td>

			<td><input class="Caixa" type="text" name="cliente_cnpj" size="10" maxlength="20" value="<? echo $cliente_cnpj ?>">
			<img src='../imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.cliente_cnpj,"cnpj")'  style='cursor: pointer'>
			</td>

			<td class='Label'>Fone Residencial:</td>

			<td><input class="Caixa" type="text" name="cliente_fone_residencial"   size="12" maxlength="30" value="<? if (strlen($cliente_fone_residencial)==0) echo $consumidor_fone; else echo $cliente_fone_residencial; ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Email</td>
			<td ><input class="Caixa" type="text" name="cliente_email"   size="40" maxlength="100" value="<? echo $cliente_email ?>"></td>
			<td class='Label'>CEP</td>
			<td><input class="Caixa" type="text" name="cliente_cep"   size="10" maxlength="10" value="<? echo $cliente_cep ?>" onblur="buscaCEP(this.value, document.frm_os.cliente_endereco, document.frm_os.cliente_bairro, document.frm_os.cliente_cidade, document.frm_os.cliente_estado) ;"></td>
			<td class='Label'>Fone Comercial:</td>
			<td><input class="Caixa" type="text" name="cliente_fone_comercial"   size="12" maxlength="30" value="<? echo $cliente_fone_comercial ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Endereço:</td>
			<td><input class="Caixa" type="text" name="cliente_endereco"   size="40" maxlength="50" value="<? echo $cliente_endereco ?>"></td>
			<td class='Label'>Número:</td>
			<td><input class="Caixa" type="text" name="cliente_numero"   size="5" maxlength="10" value="<? echo $cliente_numero ?>"></td>
			<td class='Label'>Celular:</td>
			<td><input class="Caixa" type="text" name="cliente_fone_celular"   size="12" maxlength="30" value="<? echo $cliente_cel ?>"></td>
		<tr>
		</tr>
			<td class='Label'>Bairro:</td>
			<td colspan='3'><input class="Caixa" type="text" name="cliente_bairro"   size="30" maxlength="30" value="<? echo $cliente_bairro ?>"></td>
			<td class='Label'>FAX:</td>
			<td><input class="Caixa" type="text" name="cliente_fone_fax"   size="12" maxlength="30" value="<? echo $cliente_fax ?>"></td>
		<tr>
		</tr>
			<td class='Label'>Cidade:</td>
			<td><input class="Caixa" type="text" name="cliente_cidade"   size="30" maxlength="50" value="<? echo $cliente_cidade ?>"></td>
			<td class='Label'>Estado:</td>
			<td><input class="Caixa" type="text" name="cliente_estado"   size="2" maxlength="2" value="<? echo $cliente_estado ?>"></td>
			<td class='Label'>Complemento:</td>
			<td><input class="Caixa" type="text" name="cliente_complemento"   size="5" maxlength="10" value="<? echo $cliente_complemento ?>"></td>
		</tr>
		</table>

	</td>
</tr>
<?
if ($tipo_orcamento=='FORA_GARANTIA'){
?>

	<tr><td><img height="6" width="6" src="../imagens/spacer.gif"></td></tr>
	<tr>
		<td valign="top" align="left">

			<!-- Informações da OS  -->
			<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
			<tr>
				<td rowspan='7' width='20px' valign='top'><img src='imagens/titulo_produto.gif'></td>
			</tr>
			<tr>
				<td nowrap class='Label' width='140'>Selecione a Marca</td>
				<td>
				<select name='marca' class="Caixa">
					<option value=''></option>
					<?php
					$sql2 = "SELECT marca,fabrica,nome
							FROM   tbl_marca
							WHERE  
							(empresa=$login_empresa OR fabrica>0)
							ORDER BY nome";
					$res2 = pg_exec ($con,$sql2) ;
					
					for ($x = 0 ; $x < pg_numrows ($res2) ; $x++ ) {
						$marcaa   = trim(pg_result ($res2,$x,marca));
						$nomeee   = trim(pg_result ($res2,$x,nome));
						$fabricaa = trim(pg_result ($res2,$x,fabrica));

						$selecionado="";

						if ($marca_produto==$marcaa){
							$selecionado=" SELECTED ";
						}

						echo "<option value='$marcaa' $selecionado>";
						echo "$nomeee";
						echo "</option>";
					}				
					?>
				</select>
				<!-- <img src='imagens/altera_cadastro2.gif' width='25' border='0'  align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto_fabrica (document.frm_os.marca,document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem)" alt='Clique para cadastrar'>
				-->
				</td>
				<td nowrap class='Label'>Modelo</td>
				<td colspan='3'><input class="Caixa" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >&nbsp;<img src='../imagens/btn_lupa_novo.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_fabrica (document.frm_os.marca,document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)">
				<input type="hidden" name="produto_referencia" value="<? echo $produto_referencia ?>" >
				</td>
				<!--
				<td nowrap class='Label'>
				ou Digite 
				</td>
				<td nowrap class='Label' width='140' colspan='3'>
				<input class="Caixa" type="text" name="fabricante_nome" id="fabricante_nome" size="15" maxlength="20" value="<? echo $fabricante_nome ?>" >
				</td> -->
				</tr>
				<!--
			<tr>
				<td nowrap class='Label' width='140'>Modelo</td>
				<td><input class="Caixa" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >&nbsp;<img src='../imagens/btn_lupa_novo.gif' border='0' id='img_lupa_1' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto_fabrica (document.frm_os.marca,document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem)"></td>
			</tr>
				-->
			<tr>
				<td nowrap class='Label'>N. Série.</td>
				<td><input class="Caixa" type="text" name="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" ></td>
				<!-- onblur='javascript:liberar_os_item(this.form);' -->
				<td nowrap class='Label'>Aparência</td>
				<td><input class="Caixa" type="text" name="produto_aparencia" size="20" value="<? echo $produto_aparencia;?>" ></td>

				<td nowrap class='Label'>Acessórios</td>
				<td><input class="Caixa" type="text" name="produto_acessorios" size="20" value="<? echo $produto_acessorios ?>" ></td>
				
			</tr>
			<input class="Caixa" type="hidden" name="produto_voltagem" size="15" maxlength="20" value="">
			<!-- Informações da OS - FIM  -->

			<tr>
			<td class='Label' align='left' valign='top'>Defeito Reclamado:</td>
			<td colspan='5'><textarea class='Caixa' rows='2' cols='105' name='defeito_reclamado' id='defeito_reclamado'><? echo $defeito_reclamado ?></textarea></td>
			</tr>

			<tr>
			<td class='Label' align='left' valign='top'>Defeito Constatado:</td>
			<td colspan='5'><textarea class='Caixa' rows='2' cols='105' name='defeito_constatado' id='defeito_constatado'><? echo $defeito_constatado ?></textarea></td>
			</tr>

			<tr>
			<td class='Label' align='left' valign='top' >Solução:</td>
			<td colspan='5'><textarea class='Caixa' rows='2' cols='105' name='solucao_os' id='solucao_os'><? echo $solucao ?></textarea></td>
			</tr>
		</table>

		</td>
	</tr>
<?php
	}else{
		echo "<input type='hidden' name='produto_referencia' id='produto_referencia' value=''>";
		echo "<input type='hidden' name='marca' id='marca' value='$login_empresa'>";
	}
?>
<tr><td><img height="6" width="16" src="../imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?
$qtde_item = 0;

if(strlen($orcamento) > 0 AND strlen ($msg_erro) == 0){
	$sql = "SELECT  tbl_orcamento_item.orcamento_item                          ,
			tbl_orcamento_item.qtde                                            ,
			tbl_orcamento_item.descricao                   AS item_descricao   ,
			tbl_orcamento_item.preco                                           ,
			tbl_orcamento_item.peca                                            ,
			tbl_peca.referencia                                                ,
			tbl_peca.descricao                                                 ,
			tbl_defeito.defeito                                                ,
			tbl_estoque.qtde AS estoque,
			tbl_estoque_extra.quantidade_entregar,
			to_char(tbl_estoque_extra.data_atualizacao,'DD/MM/YYYY') as data_atualizacao
		FROM    tbl_orcamento_item
		LEFT JOIN tbl_peca              USING (peca)
		LEFT JOIN tbl_defeito           USING (defeito)
		LEFT JOIN tbl_estoque ON tbl_estoque.peca = tbl_peca.peca
		LEFT JOIN tbl_estoque_extra ON tbl_estoque_extra.peca = tbl_peca.peca
		WHERE   tbl_orcamento_item.orcamento = $orcamento
		ORDER BY tbl_orcamento_item.orcamento_item;";

	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		
		$qtde_item = pg_numrows($res);

		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$item_orcamento[$k]          = trim(pg_result($res,$k,orcamento_item))   ;
			$item_peca[$k]               = trim(pg_result($res,$k,peca))       ;
			$item_preco[$k]              = trim(pg_result($res,$k,preco))       ;
			$item_referencia[$k]         = trim(pg_result($res,$k,referencia))       ;
			$item_qtde[$k]               = trim(pg_result($res,$k,qtde))             ;
			$item_descricao[$k]          = trim(pg_result($res,$k,item_descricao))   ;
			$item_defeito[$k]            = trim(pg_result($res,$k,defeito))          ;
			$item_estoque[$k]            = trim(pg_result($res,$k,estoque));
			$item_quantidade_entregar[$k]= trim(pg_result($res,$k,quantidade_entregar));
			$item_data_atualizacao[$k]   = trim(pg_result($res,$k,data_atualizacao));

			if(strlen($descricao[$k])==0) 
				$descricao[$k] = $item_descricao[$k];
		}
	}
}


//--===== Lançamento das Peças da OS ====================================================================
echo "<input type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";
echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>";
echo "<thead>";

echo "<tr>";
echo "<td rowspan='2' width='20px' valign='top'><img src='imagens/titulo_peca.gif'></td>";
echo "</tr>";

echo "<tr height='20'>";
	echo "<input type='hidden' name='add_peca'           id='add_peca'>\n";
	echo "<input type='hidden' name='peca_estoque'       id='peca_estoque'>\n";
	echo "<input type='hidden' name='peca_qtde_entrega'  id='peca_qtde_entrega'>\n";
	echo "<input type='hidden' name='peca_data_previsao' id='peca_data_previsao'>\n";

	echo "<td align='center' class='Label'>Código ";
	echo "<input class='Caixa' type='text' name='add_referencia' id='add_referencia' size='8' value='' >\n";
	echo "<img src='../imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript:fnc_pesquisa_peca_empresa (document.frm_os.marca, document.frm_os.add_referencia, document.frm_os.add_peca_descricao , document.frm_os.add_preco, document.frm_os.peca_estoque,document.frm_os.peca_qtde_entrega,document.frm_os.peca_data_previsao, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
	echo "</td>\n";
	
	echo "<td align='center' class='Label'>Descrição <input class='Caixa' type='text' name='add_peca_descricao' id='add_peca_descricao' size='40' value='' >\n";
	echo "<img src='../imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_empresa (document.frm_os.marca, document.frm_os.add_referencia, document.frm_os.add_peca_descricao , document.frm_os.add_preco, document.frm_os.peca_estoque,document.frm_os.peca_qtde_entrega,document.frm_os.peca_data_previsao, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
	echo "</td>\n";

	echo "<td align='center' class='Label'>Preço <input class='Caixa' type='text' name='add_preco' id='add_preco' size='3' maxlength='8' value=''   onblur=\"javascript:checarNumero(this)\" style='text-align:right'>\n &nbsp;";
	echo "</td>\n";

	echo "<td align='center' class='Label'>Qtde <input class='Caixa' type='text' name='add_qtde' id='add_qtde' size='2' maxlength='4' value='' >\n &nbsp;";
	echo "</td>\n";

	echo "<td class='Label'><input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adiconarPecaTbl()'></td>";

echo "</tr>";
echo "</table>";
if (strlen($descricao) == 0 or strlen($referencia) == 0) {
	$sql = "INSERT INTO tbl_produto_tmp (referencia,descricao)
							VALUES ('add_referencia','add_peca_descricao')";
}

echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' id='tbl_pecas'>";
echo "<thead>";
echo "<tr height='20' bgcolor='#A8BBD7'>";
echo "<td align='center' class='Label'><b>Código</b>&nbsp;&nbsp;&nbsp;<div id='lista_basica' style='display:inline;'></div></td>";
echo "<td align='center' class='Label'><b>Descrição</b></td>";
echo "<td align='center' class='Label'><b>Qtde</b></td>";
echo "<td align='center' class='Label'><b>Preço</b></td>";
echo "<td align='center' class='Label'><b>Estoque</b></td>";
echo "<td align='center' class='Label'><b>Compra</b></td>";
echo "<td align='center' class='Label'><b>Previsão</b></td>";
echo "<td align='center' class='Label'><b>Total</b></td>";
echo "<td align='center' class='Label'><b>Ações</b></td>";
echo "</tr>";
echo "</thead>";

echo "<tbody>";

//MOSTRA OS ITESN SE JAH FORAM GRAVADOS NO ORCAMENTO

$valor_total_itens=0;
if($qtde_item>0){
	for ($k=0;$k<$qtde_item;$k++){
			echo "<tr style='color: #000000; text-align: center; font-size:10px'>";
			echo "<td>$item_referencia[$k]";

			echo "<input type='hidden' name='orcamento_item_$k'  id='orcamento_item_$k'  value='$item_orcamento[$k]'>";
			echo "<input type='hidden' name='referencia_peca_$k' id='referencia_peca_$k' value='$item_referencia[$k]'>";
			echo "<input type='hidden' name='peca_qtde_$k'       id='peca_qtde_$k'       value='$item_qtde[$k]'>";
			echo "<input type='hidden' name='peca_$k'            id='peca_$k'            value='$item_peca[$k]'>";
			echo "<input type='hidden' name='descricao_peca_$k'            id='peca_$k'            value='$item_descricao[$k]'>";
			echo "<input type='hidden' name='peca_preco_$k'            id='peca_$k'            value='$item_preco[$k]'>";


			echo "</td>";
			echo "<td style=' text-align: left;'>$item_descricao[$k]</td>";
			echo "<td>$item_qtde[$k]</td>";
			echo "<td>$item_preco[$k]</td>";
			echo "<td>$item_estoque[$k]</td>";
			echo "<td>$item_quantidade_entregar[$k]</td>";
			echo "<td>$item_data_atualizacao[$k]</td>";

			$total_item = $item_preco[$k]*$item_qtde[$k];
			$valor_total_itens += $total_item;
			echo "<td>$total_item</td>";
			echo "<td><input type='button' onclick='javascript:removerPeca(this,$total_item);' value='Excluir' /></td>";
			echo "</tr>";
	}
}
echo "</tbody>";
echo "<tfoot>";
echo "<tr height='12' bgcolor='#A8BBD7'>";
echo "<td align='center' class='Label' colspan='7'><b>Total</b></td>\n";
echo "<td align='center' class='Label'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
echo "<td align='center' class='Label' colspan='2'></td>\n";
echo "</tr>\n";
echo "</tfoot>";
echo "</table>\n";

$valores_sub_total   += $valor_total_itens;

//--===== FIM - Lançamento de Peças =====================================================================

?>
	</td>
</tr>

<?
if ($tipo_orcamento=='FORA_GARANTIA'){
?>
<tr>
	<td><img height="6" width="16" src="../imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

<?
//--===== INICIO - MÃO DE OBRA =====================================================================

$qtde_mo = 0;

if(strlen($orcamento) > 0 AND strlen ($msg_erro) == 0){
	$sql = "SELECT
					servico,
					descricao,
					valor,
					to_char(data_lancamento,'DD/MM/YYYY') as data_lancamento,
					descricao,
					qtde
		FROM    tbl_orcamento_mao_de_obra
		WHERE   orcamento = $orcamento
		ORDER BY orcamento_mao_de_obra";

	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		$qtde_mo = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_mo ; $k++) {
			$mo_servico[$k]         = trim(pg_result($res,$k,servico));
			$mo_descricao[$k]       = trim(pg_result($res,$k,descricao));
			$mo_valor[$k]           = number_format(trim(pg_result($res,$k,valor)),2,'.','');
			$mo_data_lancamento[$k] = trim(pg_result($res,$k,data_lancamento));
			$mo_descricao[$k]       = trim(pg_result($res,$k,descricao));
			$mo_qtde[$k]            = trim(pg_result($res,$k,qtde));
		}
	}
}

echo "<input type='hidden' id='qtde_mo' value='$qtde_mo'>";
echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>";

echo "<tr bgcolor='#e6eef7'>";
echo "<td rowspan='2' width='20px' valign='top'><img src='imagens/titulo_servico.gif'></td>";
echo "</tr>";

echo "<tr height='20' >";
echo "<td colspan='6'>";

echo "<table>\n";
echo "<tr>\n";
echo "<td class='Label'>Descrição</td>\n";
echo "<td class='Label' width='350'>";
	$sql = "SELECT servico,descricao,valor
		FROM   tbl_servico
		WHERE  fabrica=$login_empresa
		AND posto=$login_loja
		AND ativo IS TRUE
		ORDER BY descricao ASC";
	$res = pg_exec($con,$sql) ;
	
	for ($x = 0 ; $x < pg_numrows($res) ; $x++ ){
		echo "<input type='hidden' value='".number_format(pg_result ($res,$x,valor),2,'.','')."' id='valor_id_".pg_result ($res,$x,servico)."'>";
	}

	echo "<select name='txt_mao_obra' id='txt_mao_obra' onchange=\"javascript:document.getElementById('txt_qtde_mao_obra').value=1;document.getElementById('mao_de_obra_valor').value=document.getElementById('valor_id_'+this.value).value\">";
	echo "<option value=''></option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++ ){
		echo "<option value='".pg_result ($res,$x,servico)."'>\n";
		echo substr(pg_result ($res,$x,descricao),0,40);
		echo "</option>\n";
	}
echo "</select>\n";
//echo "<img src='imagens/altera_cadastro2.gif' width='25' border='0'  align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_produto_fabrica (document.frm_os.marca,document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem)\" alt='Clique para cadastrar'>";
echo "</td>";
echo "<td class='Label'>Valor</td>";
echo "<td class='Label' width='100'><input name='mao_de_obra_valor' id='mao_de_obra_valor' type='text'  size='8' class='Caixa' onblur=\"javascript:checarNumero(this)\" style='text-align:right'></td>";
echo "<td class='Label'>Qtde</td>";
echo "<td class='Label' width='100'><input name='txt_qtde_mao_obra' id='txt_qtde_mao_obra' type='text' size='5' class='Caixa'></td>";
echo "<td class='Label'><input name='gravar_mao_de_obra' id='gravar_mao_de_obra' type='button' value='Adicionar' onClick='javascript:adicionaLinha()'></td>";
echo "</tr>";
echo "</table>";

echo "</td>\n";
echo "</tr>\n";
echo "</table>";

echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'  id='tbl_mo' >";

echo "<tr height='20' bgcolor='#A8BBD7'>";
echo "<td align='center' class='Label'><b>#</b></td>\n";
echo "<td align='center' class='Label'><b>Descrição</b></td>\n";
echo "<td align='center' class='Label'><b>Valor</b></td>\n";
echo "<td align='center' class='Label'><b>Qtde</b></td>\n";
echo "<td align='center' class='Label'><b>Total</b></td>\n";
echo "<td align='center' class='Label'><b>Ação</b></td>\n";
echo "</tr>\n";


echo "<tbody>";
$valor_total_mo=0;
if($qtde_mo>0){
	for ($k=0;$k<$qtde_mo;$k++){
			echo "<tr style='color: #000000; text-align: center; font-size:10px'>";
			echo "<td>$mo_servico[$k]";

			echo "<input type='hidden' name='servico_$k'  id='servico_$k'  value='$mo_servico[$k]'>";
			echo "<input type='hidden' name='mao_valor_$k' id='mao_valor_$k' value='$mo_valor[$k]'>";
			echo "<input type='hidden' name='mao_qtde_$k'       id='mao_qtde_$k'       value='$mo_qtde[$k]'>";

			$total_mo = number_format($mo_valor[$k]*$mo_qtde[$k],2,'.','');
			$valor_total_mo += $total_mo;

			echo "</td>";
			echo "<td style=' text-align: left;'>$mo_descricao[$k]</td>";
			echo "<td>$mo_valor[$k]</td>";
			echo "<td>$mo_qtde[$k]</td>";
			echo "<td>$total_mo</td>";
			echo "<td><input type='button' onclick='javascript:removerMO(this,$total_mo)' value='Excluir'></td>";
			echo "</tr>";
	}
	$valor_total_mo = number_format($valor_total_mo,2,'.','');
}
echo "</tbody>";
echo "<tfoot>";
echo "<tr height='12' bgcolor='#A8BBD7'>";
echo "<td align='center' class='Label' colspan='4'><b>Total</b></td>\n";
echo "<td align='center' class='Label'><span style='font-weight:bold' id='valor_total_mo'>$valor_total_mo</span></td>\n";
echo "<td align='center' class='Label'></td>\n";
echo "</tr>\n";
echo "</tfoot>";
echo "</table>\n";
$valores_sub_total += $valor_total_mo;

}
?>
<tr>
	<td><img height="0" width="16" src="../imagens/spacer.gif"></td></tr>
<? 
if ($tipo_orcamento<>'VENDA'){
?>
<tr>
	<td valign="top" align="left">


	<table cellpadding='10'>
	<tr>
	<td valign='top'>
<?
	if (strlen($data_visita)>0){
		$visita_checada=" CHECKED ";
		$campos_bloqueados = "";
		//$hora_visita
	}else{
		$campos_bloqueados = " disabled ";
	}

	//--===== AGENDAMENTO DE VISITA -  ========================================================================
	echo "<table style=' border:#C1C1C1 1px solid; background-color: #E8E8E8' align='center' width='300' border='0'>";
	echo "<tr>";
	echo "<td rowspan='4' width='20px' valign='top'><img src='imagens/titulo_visita.gif'></td>";
	echo "</tr>";
	echo "<tr >";
	echo "<td colspan='4'>";

		echo "<table  width='100%' >";
		echo "<tr >";
		echo "<td class='Label' width='100px'>Visita</td>";

		echo "<td class='Label'>
		<input type='checkbox' name='fazer_visita' onclick=\"javascript:
				document.getElementById('label_data_visita').disabled=!this.checked;
				document.getElementById('label_horario_visita').disabled=!this.checked;
				document.getElementById('txt_data_visita').disabled=!this.checked;
				document.getElementById('txt_horario_visita').disabled=!this.checked;
		\" value='sim' $visita_checada></td>";

		echo "</tr>";
		echo "<tr>";

		echo "<input type='hidden' name='txt_data_visita_anterior' value='$data_visita'>";

		echo "<td class='Label'><label name='label_data_visita'  id='label_data_visita' $campos_bloqueados>Data da Visita</td>";
		echo "<td class='Label'><input name='txt_data_visita' id='txt_data_visita' type='text' class='Caixa' size='12' maxlength='10' value='$data_visita' $campos_bloqueados></label></td>";

		echo "</tr>";
		echo "<tr>";

		echo "<td class='Label'>
		<label name='label_horario_visita' id='label_horario_visita' $campos_bloqueados>Horário";

		echo "<td class='Label'>
		<input type='hidden' name='txt_horario_visita_anterior' value='$hora_visita'>
		<select name='txt_horario_visita' id='txt_horario_visita' class='Caixa' $campos_bloqueados>
			<option  value=''></option>";
			echo "<option value='08:00' ";echo ($hora_visita=='08:00')?" SELECTED ":""; echo ">8:00</option>";
			echo "<option value='08:30' ";echo ($hora_visita=='08:30')?" SELECTED ":""; echo ">8:30</option>";
			echo "<option value='09:00' ";echo ($hora_visita=='09:00')?" SELECTED ":""; echo ">9:00</option>";
			echo "<option value='09:30' ";echo ($hora_visita=='09:30')?" SELECTED ":""; echo ">9:30</option>";
			echo "<option value='10:00' ";echo ($hora_visita=='10:00')?" SELECTED ":""; echo ">10:00</option>";
			echo "<option value='10:30' ";echo ($hora_visita=='10:30')?" SELECTED ":""; echo ">10:30</option>";
			echo "<option value='11:00' ";echo ($hora_visita=='11:00')?" SELECTED ":""; echo ">11:00</option>";
			echo "<option value='11:30' ";echo ($hora_visita=='11:30')?" SELECTED ":""; echo ">11:30</option>";
			echo "<option value='12:00' ";echo ($hora_visita=='12:00')?" SELECTED ":""; echo ">12:00</option>";
			echo "<option value='12:30' ";echo ($hora_visita=='12:30')?" SELECTED ":""; echo ">12:30</option>";
			echo "<option value='13:00' ";echo ($hora_visita=='13:00')?" SELECTED ":""; echo ">13:00</option>";
			echo "<option value='13:30' ";echo ($hora_visita=='13:30')?" SELECTED ":""; echo ">13:30</option>";
			echo "<option value='14:00' ";echo ($hora_visita=='14:00')?" SELECTED ":""; echo ">14:00</option>";
			echo "<option value='14:30' ";echo ($hora_visita=='14:30')?" SELECTED ":""; echo ">14:30</option>";
			echo "<option value='15:00' ";echo ($hora_visita=='15:00')?" SELECTED ":""; echo ">15:00</option>";
			echo "<option value='15:30' ";echo ($hora_visita=='15:30')?" SELECTED ":""; echo ">15:30</option>";
			echo "<option value='16:00' ";echo ($hora_visita=='16:00')?" SELECTED ":""; echo ">16:00</option>";
			echo "<option value='16:30' ";echo ($hora_visita=='16:30')?" SELECTED ":""; echo ">16:30</option>";
			echo "<option value='17:00' ";echo ($hora_visita=='17:00')?" SELECTED ":""; echo ">17:00</option>";
			echo "<option value='17:30' ";echo ($hora_visita=='17:30')?" SELECTED ":""; echo ">17:30</option>";
			echo "<option value='18:00' ";echo ($hora_visita=='18:00')?" SELECTED ":""; echo ">18:00</option>";
			echo "<option value='18:30' ";echo ($hora_visita=='18:30')?" SELECTED ":""; echo ">18:30</option>";
			echo "<option value='19:00' ";echo ($hora_visita=='19:00')?" SELECTED ":""; echo ">19:00</option>";
			echo "<option value='19:30' ";echo ($hora_visita=='19:30')?" SELECTED ":""; echo ">19:30</option>";
			echo "<option value='20:00' ";echo ($hora_visita=='20:00')?" SELECTED ":""; echo ">20:00</option>";
			echo "<option value='20:30' ";echo ($hora_visita=='20:30')?" SELECTED ":""; echo ">20:30</option>";
		echo "</select>
		</label>
		</td>";
		echo "</tr>";

		if ($efetivar_visita=='sim' AND $visita_status<>"Executada"){
			echo "<tr>";
			echo "<td class='Label'>Visitado</td>";
			echo "<td> <input type='checkbox' name='efetuar_visita' value='SIM'></td>";
			echo "</tr>";
		}
		if (strlen($visita_status)==0 AND strlen($data_visita)>0){
			$visita_status="Pendente";
		}
		if ($visita_status=="Pendente"){
			$visita_status_msg = "<b style='color:orange'>$visita_status</b>";
		}
		if ($visita_status=="Cancelado" || $visita_status=="Cancelada" || $visita_status=="CANCELADA"){
			$visita_status_msg = "<b style='color:red'>$visita_status</b>";
		}
		if ($visita_status=="Executada"){
			$visita_status_msg = "<b style='color:blue'>$visita_status</b>";
		}
		echo "<tr>";
		echo "<td class='Label'>Status do Visita</td>";
		if (strlen($visita_status_msg)==0) $visita_status_msg="-";
		echo "<td class='Label'><input type='hidden' name='visita_status' value='$visita_status'>$visita_status_msg</td>";
		echo "</tr>";

		echo "</table>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

?>
		</td>
		<td  valign='top'>
		<?
		//--===== SITUAÇÂO DA OS -  ========================================================================
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='430' border='0'>";
		echo "<tr bgcolor='#e6eef7' >";
		echo "<td rowspan='8' width='20px' valign='top'><img src='imagens/titulo_status.gif'></td>";
		echo "</tr>";
		echo "<td>";
		if (strlen($status)==0)    $status="-";
		if (strlen($status_os)==0) $status_os="-";

		if ($tipo_orcamento=='FORA_GARANTIA'){
			echo "<tr  valign='top'>";
			echo "<td width='45%' class='Label' valign='top'><label name='previsao_conserto'>Previsão Término do Conserto</label></td>";
			echo "<td width='55%' class='Label' align='left'>";
			if (strlen($data_previsao)>0){
				echo "&nbsp;<b>$data_previsao</b>
				<input type='hidden' name='data_previsao_ok' value='SIM'>
					<input type='hidden' name='data_previsao' value='$data_previsao'>";
			}else{
				echo "<input name='data_previsao' id='data_previsao' type='text' class='Caixa' size='12' maxlength='10' value=''>";
			}
			echo "</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td class='Label'><label name='status_os'>Situação da OS</label></td>";
			echo "<td class='Label' align='left'><b>$status</b></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td class='Label'><label name='status_produto'>Status do Produto</label></td>";
			echo "<td  class='Label' align='left'><b>$status_os</b></td>";
			echo "</tr>";
		}else{
			echo "<tr >";
			echo "<td width='45%' class='Label'>Previsão Término da Entrega</td>";
			echo "<td width='55%' class='Label' align='left'>";
			if (strlen($data_previsao)>0){
				echo "&nbsp;<b>$data_previsao</b>
				<input type='hidden' name='data_previsao_ok' value='SIM'>
					<input type='hidden' name='data_previsao' value='$data_previsao'>";
			}else{
				echo "<input name='data_previsao' id='data_previsao' type='text' class='Caixa' size='12' maxlength='10' value=''>";
			}
			echo "</td>";
			echo "</tr>";
//			echo "<tr>";
//			echo "<td class='Label'>Data</td>";
//			echo "<td class='Label' align='left'>&nbsp;$os_situacao_data</td>";
//			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Status do Produto</td>";
			echo "<td class='Label' align='left'><b>$status</b></td>";
			echo "</tr>";
		}
		echo "</table>";
		?>
		</td>
		</tr>
		</table>
	</td>
</tr>

<? } ?>
<tr>
	<td valign="top" align="right">

	<table cellpadding='6'>
	<tr>
	<td valign='top'>
<?
if ($tipo_orcamento<>'VENDA'){

	//--===== APROVAÇÂO - REPROVAÇÂO ========================================================================

	echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='250' border='0'>";
	echo "<tr bgcolor='#e6eef7'>";
	echo "<td rowspan='6' width='20px' valign='top'><img src='imagens/titulo_status.gif'></td>";
	echo "</tr>";
	echo "<tr height='140' >";
	echo "<td colspan='4'  valign='top'>";

	echo "<table>";

	if (strlen($aprovado)==0){
		echo "<tr>";
		echo "<td class='Label'>Aprovação</td>";
		echo "<td class='Label' width='150'>";
		echo "
			<SELECT NAME='aprovacao' onChange=\"
				if (this.value=='APROVAR'){
					document.getElementById('txt_tipo_aprovado_label').style.display='inline';
					document.getElementById('txt_motivo_reprova_label').style.display='none';
					document.getElementById('txt_quem_aprovou_label').style.display='inline';
					document.getElementById('txt_quem_reprovou_label').style.display='none';
					
					
				}
				if (this.value=='REPROVAR'){
					document.getElementById('txt_tipo_aprovado_label').style.display='none';
					document.getElementById('txt_motivo_reprova_label').style.display='inline';
					document.getElementById('txt_quem_aprovou_label').style.display='none';
					document.getElementById('txt_quem_reprovou_label').style.display='inline';
				}
				if (this.value==''){
					document.getElementById('txt_tipo_aprovado_label').style.display='none';
					document.getElementById('txt_motivo_reprova_label').style.display='none';
					document.getElementById('txt_quem_aprovou_label').style.display='none';
					document.getElementById('txt_quem_reprovou_label').style.display='none';

				}
				
				\">
				<option value=''></option>
				<option value='APROVAR'>Aprovar</option>
				<option value='REPROVAR'>Reprovar</option>
			</SELECT>
			
			</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td class='Label' colspan='2'>";
		echo "<label name='txt_quem_aprovou_label' id='txt_quem_aprovou_label' style='display:none'>Por:<br> <input name='txt_quem_aprovou' id='txt_quem_aprovou' type='text' class='Caixa' size='30' maxlength='240'></label>";
		echo "<label name='txt_quem_reprovou_label' id='txt_quem_reprovou_label' style='display:none'>Por:<br> <input name='txt_quem_reprovou' id='txt_quem_reprovou' type='text' class='Caixa' size='30' maxlength='240'></label>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td class='Label' colspan='2'>";
		echo "<label name='txt_tipo_aprovado_label' id='txt_tipo_aprovado_label' style='display:none'>Tipo de Aprovação <select name='txt_tipo_aprovado' id='txt_tipo_aprovado' class='Caixa'>
			<option value='Telefone'>Telefone</option>
			<option value='E-mail'>Email</option>
			<option value='Impresso'>Impresso</option>
		</select>
			</label>
			";
		echo "<label name='txt_motivo_reprova_label' id='txt_motivo_reprova_label' style='display:none'>Motivo<br> <input name='txt_motivo_reprova' id='txt_motivo_reprova' type='text' class='Caixa' size='30' maxlength='240'></label>";
		echo "</td>";
		echo "</tr>";
	}else{
		echo "<input type='hidden' name='ja_aprovado' value='sim'>";
		if(strlen($apro_data_aprovacao)>0){
			echo "<tr>";
			echo "<td class='Label'>Orçamento</td>";
			echo "<td class='Label'><b style='color:blue'>APROVADO</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Data</td>";
			echo "<td class='Label'><b>$apro_data_aprovacao</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Por:</td>";
			echo "<td class='Label'><b>$apro_aprovacao_responsavel</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Via:</td>";
			echo "<td class='Label'><b>$apro_tipo_aprovacao</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Usuário:</td>";
			echo "<td class='Label'><b>$apro_empregado_aprovacao_login</b></td>";
			echo "</tr>";
		}else
		if(strlen($apro_data_reprovacao)>0){
			echo "<tr>";
			echo "<td class='Label'>Orçamento</td>";
			echo "<td class='Label'><b style='color:red'>REPROVADO</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Data</td>";
			echo "<td class='Label'><b>$apro_data_reprovacao</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Por:</td>";
			echo "<td class='Label'><b>$apro_aprovacao_responsavel</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Motivo:</td>";
			echo "<td class='Label'><b>$apro_motivo_reprovacao</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='Label'>Usuário:</td>";
			echo "<td class='Label'><b>$apro_empregado_aprovacao_login</b></td>";
			echo "</tr>";

		}
	}

	echo "</table>";

	echo "</td>";
	echo "</tr>";

	if (strlen($msg_aprovado)>0 AND 1==2){
		echo "<tr height='20' bgcolor='#e6eef7'>";
		echo "<td align='center' ><span style='color:black;font-size:12px'>$msg_aprovado</span></td>";
		echo "</tr>";
	}

	echo "</table>";
		}
?>
	</td>
	<td valign='top'>
<?


	//--===== FORMAS DE PAGAMENTO -  ========================================================================
	echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='250' border='0'>";
	echo "<tr height='20' bgcolor='#e6eef7'>";
	echo "<td rowspan='6' width='20px' valign='top'><img src='imagens/titulo_pagamento.gif'></td>";
	echo "</tr>";
	echo "<tr height='140' >";
	echo "<td colspan='4'  valign='top'>";
		echo "<table  width='100%' >";
		echo "<tr >";
		echo "<td class='Label' width='100px'>Pagamento </td>";
		echo "<td class='Label'>";

		echo "<select class='Caixa' size='1' name='condicao_pagamento'  id='condicao_pagamento' onchange='javascript:verificar_pagamento(this.value)'>\n";
		echo "<option value=''></option>\n";

		$sql = "SELECT condicao,descricao,parcelas,visivel
				FROM tbl_condicao
				WHERE fabrica=$login_empresa
				AND visivel IS TRUE
				ORDER BY condicao ASC";
		$res = pg_exec ($con,$sql) ;
		//echo ">>>".$sql;
		if (pg_numrows($res) > 0) {
			for ($k = 0; $k <pg_numrows($res) ; $k++) {
				$condicao      = trim(pg_result($res,$k,condicao));
				$descricao     = trim(pg_result($res,$k,descricao));
				$parcelas      = trim(pg_result($res,$k,parcelas));
				if ($condicao_pagamento==$condicao) {
					$select_cond = " SELECTED ";
					$dias_parcela = $parcelas;
				}
				$parcelas_array = explode("|",$parcelas);
				$parcelas_qtde  = count($parcelas_array);
				$parcelas = str_replace("|"," / ",$parcelas);

				if ($parcelas_qtde==1 AND trim($parcelas)==0){
					$parcelas = "Á Vista";
				}
				echo "<option value='$condicao' $select_cond>$parcelas</option>\n";
				$select_cond="";
			}
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		if (strlen($dias_parcela)>0 AND strlen($msg_erro)==0){
			$parcelas = explode("|",$dias_parcela);
			$data_abertura = converte_data($data_abertura);
			$valor_parcela=number_format($total_geralzao/count($parcelas),2,".","");
			$parc = array();
			for ($i=0;$i<count($parcelas);$i++){
				$data_tmp = date("d/m/Y",strtotime($data_abertura)+$parcelas[$i]*60*60*24);
				array_push($parc,$data_tmp);
			}
			for ($i=1;$i<=count($parc);$i++){
				$resposta .= $i."ª Parc. ".$parc[$i-1]." - R$ $valor_parcela<br>";
			}
		}

		echo "<td class='Label' colspan='2'><span id='id_condicao_pagamento'>$resposta</span></td>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

?>
	</td>
	<td  valign='top'>
<?

// ========================================================================
//--===== SUBTOTAL  =====  TOTAL
// ========================================================================

$valores_total_geral = $valores_sub_total + $valores_acrescimos + $valores_frete - $valores_descontos;

if (strlen($valores_total_geral)>0) $valores_total_geral = number_format($valores_total_geral,2,".","");
if (strlen($valores_sub_total)>0) $valores_sub_total = number_format($valores_sub_total,2,".","");

echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='225' border='0' id='tabela_totais'>";
echo "<tr height='10px'>";
echo "<td rowspan='7' width='20px' valign='top'><img src='imagens/titulo_total.gif'></td>";
echo "</tr>";
	echo "<tr  >";
	echo "<td width='20%' class='Label'>Subt-Total</td>";
	echo "<td width='80%' class='Label' align='right'><span id='valores_sub_total'>$valores_sub_total</span></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Label'>Brinde</td>";
	echo "<td class='Label' align='right'><span id='valores_brindes'>$valores_brindes</span></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Label'>Frete</td>";
	echo "<td class='Label' align='right'><span id='valores_frete'>$valores_frete</span></td>";
	echo "</tr>";
	echo "<td class='Label'>Descontos</td>";
	echo "<td class='Label' align='right'>
	<input type='text' class='Caixa' size='10' name='valores_descontos' id='valores_descontos' value='$valores_descontos' onblur=\"javascript:checarNumero(this);recalcular()\" style='text-align:right'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Label'>Acréscimos</td>";
	echo "<td class='Label' align='right'><input type='text' size='10' name='valores_acrescimos' id='valores_acrescimos' class='Caixa' value='$valores_acrescimos' onblur=\"javascript:checarNumero(this);recalcular()\" style='text-align:right'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Label'><b>TOTAL</b></td>";
	echo "<td class='Label' align='right'><span id='valores_total_geral'>$valores_total_geral</span></td>";
	echo "</table>";
?>
		</td>
		</tr>
		</table>
	</td>
</tr>

<tr>
	<td><img height="10" width="16" src="../imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?

//--===== FINALIZAR =========================================================================

if (strlen($orcamento)>0 AND strlen($condicao_pagamento)>0 AND $aprovado=='t' AND strlen($apro_data_aprovacao)>0){
	$mostra_finalizar=" style='display:inline' ";
}else{
	$mostra_finalizar=" style='display:none' ";
}
if (strlen($orcamento)==0){
	$mostra_imprimir=" style='display:none' ";
}

echo "<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>";
echo "<tr>";
echo "<td valign='middle' align='LEFT' class='Label' >";
echo "</td>";

if ($tipo_orcamento=='VENDA'){
	echo "<td width='50' valign='middle'  align='LEFT'><input type='button' name='btn_acao' class='frm' value='Gravar' onClick=\"if (this.value!='Gravar'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_os(this.form,'sim','orcamento_cadastro.php?redic=1','nao');}\" style=\"width: 150px;\"></td>";
	echo "<td width='100px'></td>";
	echo "<td width='50' valign='middle'  align='right'><input type='button' name='btn_finalizar' id='btn_finalizar' class='frm' value='Finalizar' onClick=\"if (this.value!='Finalizar'){ alert('Aguarde');}else {this.value='Aguarde...'; finalizar($orcamento);}\" style=\"width: 150px;\"></td>";
}
else{
	echo "<td width='50' valign='middle'  align='LEFT'><input type='button' name='btn_acao' class='frm' value='Gravar' onClick=\"if (this.value!='Gravar'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_os(this.form,'sim','orcamento_cadastro.php?redic=1','nao');}\" style=\"width: 150px;\"></td>";

	//echo "<td valign='middle' align='LEFT' class='Label'><input type='button' id='btn_continuar' value='Continuar' class='frm' $mostra onClick=\"window.location='orcamento_finalizar.php?orcamento=$orcamento'\"></td>";

	echo "<td valign='middle' align='LEFT' class='Label'><input type='button' id='btn_continuar' value='Imprimir' class='frm' $mostra_imprimir onClick=\"javascript:imprimir('orcamento_print.php?orcamento=$orcamento')\"></td>";

	echo "<td width='50' valign='middle'  align='right'><input type='button' name='btn_finalizar' id='btn_finalizar' class='frm' value='Finalizar' $mostra_finalizar onClick=\"if (this.value!='Finalizar'){ alert('Aguarde');}else {this.value='Aguarde...'; finalizar($orcamento);}\" style=\"width: 150px;\"></td>";
}

echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";
//--=====================================================================================================

?>
	</td>
</tr>
</table>
</form>
<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>
<br>
<br>

<?
 //include "rodape.php";
?>
