<?
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../funcoes.php';
include 'autentica_usuario_empresa.php';

if (strlen($_GET['orcamento']) > 0) $orcamento = trim($_GET['orcamento']);
else                                $orcamento = trim($_POST['orcamento']);

if (strlen($_GET['finalizar']) > 0) $finalizar = trim($_GET['finalizar']);
else                                $finalizar = trim($_POST['finalizar']);


######### BLOCO DE VERIFICAÇÃO ###########
$sql22 = "SELECT orcamento
	FROM tbl_orcamento_os
	WHERE orcamento = $orcamento";
$res = pg_exec ($con,$sql22);
if (pg_numrows ($res) == 1) {
	$tipo_orcamento="FORA_GARANTIA";
	$tipo_os = "ORÇAMENTO DE SERVIÇO";
}else{
	$sql22 = "SELECT orcamento
			FROM tbl_orcamento_venda
			WHERE orcamento = $orcamento";
	$res22 = pg_exec ($con,$sql22);
	if (pg_numrows ($res22) >0) {
		$tipo_orcamento="ORCA_VENDA";
		$tipo_os = "ORÇAMENTO DE VENDA";
	}else{
		$tipo_orcamento="VENDA";
		$tipo_os = "VENDA";
	}
}
##########################################

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($orcamento) > 0) {
	$sql = "SELECT	tbl_orcamento.orcamento                                     ,
			to_char(tbl_orcamento.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
			to_char(tbl_orcamento.data_digitacao,'YYYY/MM/DD') AS data_digitacao_bd,
			to_char(tbl_orcamento.data_digitacao,'HH24:MI')    AS hora_digitacao,
			tbl_orcamento.cliente                                               ,
			tbl_orcamento.vendedor                                              ,
			tbl_orcamento.total_mao_de_obra                                     ,
			tbl_orcamento.total_pecas                                           ,
			tbl_orcamento.brinde                                                ,
			tbl_orcamento.frete                                                 ,
			tbl_orcamento.desconto                                              ,
			tbl_orcamento.acrescimo                                             ,
			tbl_orcamento.total                                                 ,
			tbl_orcamento.aprovado                                              ,
			tbl_orcamento.consumidor_nome                                       ,
			tbl_orcamento.consumidor_fone                                       ,
			tbl_orcamento.data_aprovacao                                        ,
			tbl_orcamento.tipo_aprovacao                                        ,
			tbl_orcamento.data_reprovacao                                       ,
			tbl_orcamento.motivo_reprovacao                                     ,
			tbl_orcamento.empregado_aprovacao                                   ,
			tbl_orcamento.aprovacao_responsavel                                 ,
			tbl_orcamento.faturamento                                           ,
			tbl_status.descricao as status                                      ,
			tbl_posto.posto                                                     ,
			tbl_posto.nome                                                      ,
			tbl_posto.cnpj                                                      ,
			tbl_posto.endereco                                                  ,
			tbl_posto.bairro                                                    ,
			tbl_posto.numero                                                    ,
			tbl_posto.complemento                                               ,
			tbl_posto.cep                                                       ,
			tbl_posto.cidade                                                    ,
			tbl_posto.estado                                                    ,
			tbl_posto.email                                                     ,
			tbl_posto.fone                                                      ,
			tbl_posto.fax                                                       ,
			tbl_posto.fantasia                                                  ,
			tbl_orcamento.condicao_pagamento                                    ,
			tbl_condicao.parcelas                                               
		FROM  tbl_orcamento
		JOIN  tbl_posto ON tbl_posto.posto = tbl_orcamento.loja
		LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_orcamento.condicao_pagamento
		LEFT JOIN tbl_status ON tbl_status.status = tbl_orcamento.status
		WHERE tbl_orcamento.orcamento = $orcamento
		AND   tbl_orcamento.loja      = $login_loja
		AND   tbl_orcamento.empresa=$login_empresa";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$orcamento                 = pg_result ($res,0,orcamento)      ;
		$data_digitacao            = pg_result ($res,0,data_digitacao) ;
		$data_digitacao_bd            = pg_result ($res,0,data_digitacao_bd) ;
		$hora_digitacao            = pg_result ($res,0,hora_digitacao) ;
		$cliente                   = pg_result ($res,0,cliente)        ;
		$consumidor_nome           = pg_result ($res,0,consumidor_nome);
		$consumidor_fone           = pg_result ($res,0,consumidor_fone);
		$vendedor                  = pg_result ($res,0,vendedor)       ;
		$total_mao_de_obra         = pg_result ($res,0,total_mao_de_obra);
		$total_pecas               = pg_result ($res,0,total_pecas)    ;
		$brinde                    = pg_result ($res,0,brinde)         ;
		$frete                     = pg_result ($res,0,frete)          ;
		$desconto                  = pg_result ($res,0,desconto)       ;
		$acrescimo                 = pg_result ($res,0,acrescimo)      ;
		$total_orcamento           = pg_result ($res,0,total)          ;
		$aprovado                  = pg_result ($res,0,aprovado)       ;
		$data_aprovacao            = pg_result ($res,0,data_aprovacao) ;
		$tipo_aprovacao            = pg_result ($res,0,tipo_aprovacao) ;
		$data_reprovacao           = pg_result ($res,0,data_reprovacao);
		$motivo_reprovacao         = pg_result ($res,0,motivo_reprovacao);
		$empregado_aprovacao       = pg_result ($res,0,empregado_aprovacao);
		$aprovacao_responsavel     = pg_result ($res,0,aprovacao_responsavel);
		$status                    = pg_result ($res,0,status)         ;
		$faturamento               = pg_result ($res,0,faturamento)    ;
		

		$condicao_pagamento        = pg_result ($res,0,condicao_pagamento);
		$parcelas                  = pg_result ($res,0,parcelas)       ;

		$loja_posto                = pg_result ($res,0,posto)          ;
		$loja_nome                 = pg_result ($res,0,nome)           ;
		$loja_cnpj                 = pg_result ($res,0,cnpj)           ;
		$loja_endereco             = pg_result ($res,0,endereco)       ;
		$loja_bairro               = pg_result ($res,0,bairro)         ;
		$loja_numero               = pg_result ($res,0,numero)         ;
		$loja_complemento          = pg_result ($res,0,complemento)    ;
		$loja_cep                  = pg_result ($res,0,cep)            ;
		$loja_cidade               = pg_result ($res,0,cidade)         ;
		$loja_estado               = pg_result ($res,0,estado)         ;
		$loja_email                = pg_result ($res,0,email)          ;
		$loja_fone                 = pg_result ($res,0,fone)           ;
		$loja_fax                  = pg_result ($res,0,fax)            ;

		if ($tipo_orcamento=="ORCA_VENDA" AND $aprovado=='t'){
			$tipo_os = "ORDEM DE SERVIÇO";
		}

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
			AND tbl_pessoa_cliente.empresa = $login_empresa ";
			//echo $sql."<br>";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$cliente_cliente     = trim (pg_result ($res,0,pessoa));
				$cliente_empresa     = trim (pg_result ($res,0,empresa));
				$cliente_nome        = trim (pg_result ($res,0,nome));
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

		$cliente_nome				= $consumidor_nome;
		$cliente_fone_residencial	= $consumidor_fone;

		$sql = "SELECT 
				tbl_orcamento_os.tecnico           ,
				tbl_orcamento_os.fabrica           ,
				tbl_orcamento_os.fabricante_nome   ,
				tbl_orcamento_os.abertura          ,
				tbl_orcamento_os.fechamento        ,
				tbl_orcamento_os.defeito_reclamado ,
				tbl_orcamento_os.defeito_constatado,
				tbl_orcamento_os.solucao           ,
				tbl_orcamento_os.produto           ,
				tbl_orcamento_os.produto_descricao ,
				tbl_orcamento_os.serie             ,
				tbl_orcamento_os.aparencia         ,
				tbl_orcamento_os.acessorios        ,
				tbl_orcamento_os.revenda           ,
				TO_CHAR(tbl_orcamento_os.data_nf,'DD/MM/YYYY') AS data_nf,
				tbl_orcamento_os.nf                ,
				tbl_orcamento_os.status            ,
				tbl_orcamento_os.garantia          ,
				TO_CHAR(tbl_orcamento_os.data_visita,'DD/MM/YYYY') AS data_visita,
				TO_CHAR(tbl_orcamento_os.data_visita,'HH24:MI') AS hora_visita,
				tbl_orcamento_os.reincidencia      ,
				tbl_orcamento_os.status_visita     ,
				tbl_produto.referencia             ,
				tbl_produto.descricao              ,
				tbl_linha.fabrica as fabrica_produto
			FROM tbl_orcamento_os
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_linha USING(linha)
			WHERE tbl_orcamento_os.orcamento = $orcamento";

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
			$produto            = trim (pg_result ($res,0,produto))           ;
			$produto_referencia = trim (pg_result ($res,0,referencia))        ;
			$produto_descricao  = trim (pg_result ($res,0,produto_descricao)) ;
			$produto_serie      = trim (pg_result ($res,0,serie))             ;
			$produto_aparencia  = trim (pg_result ($res,0,aparencia))         ;
			$produto_acessorios = trim (pg_result ($res,0,acessorios))        ;
			$revenda            = trim (pg_result ($res,0,revenda))           ;
			$data_nf            = trim (pg_result ($res,0,data_nf))           ;
			$nota_fiscal        = trim (pg_result ($res,0,nf))                ;
			$status             = trim (pg_result ($res,0,status))            ;
			$garantia           = trim (pg_result ($res,0,garantia))          ;
			$reincidencia       = trim (pg_result ($res,0,reincidencia))      ;
			$data_visita        = trim (pg_result ($res,0,data_visita))       ;
			$hora_visita        = trim (pg_result ($res,0,hora_visita))       ;
			$visita_status      = trim (pg_result ($res,0,status_visita))     ;
		}
	}
}

if (trim($finalizar)=="1" AND strlen($faturamento)==0){
	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen($frete)==0) $frete=0;

	$sql = "INSERT INTO tbl_faturamento		  
				(fabrica,
				emissao,
				saida, 
				posto, 
				total_nota, 
				valor_frete, 
				nota_fiscal,
				pessoa_cliente,
				movimento,
				obs
				)
				VALUES (
				$login_empresa,
				'$data_digitacao_bd',
				current_date,
				$login_loja,
				$total_orcamento,
				$frete,
				'000000',
				$cliente,
				'SAIDA',
				'Venda')";
	$res = pg_exec ($con,$sql);
	$sql = "SELECT CURRVAL ('seq_faturamento')";
	$resZ = pg_exec ($con,$sql);
	$faturamento_codigo = pg_result ($resZ,0,0);

	$sql = "SELECT  tbl_orcamento_item.orcamento_item                                  ,
			tbl_orcamento_item.qtde                                            ,
			tbl_orcamento_item.descricao                   AS item_descricao   ,
			tbl_orcamento_item.preco                                           ,
			tbl_orcamento_item.peca                                            ,
			tbl_peca.referencia                                                ,
			tbl_peca.descricao                                                 ,
			tbl_defeito.defeito                                                ,
			tbl_servico_realizado.servico_realizado
		FROM tbl_orcamento_item
		LEFT JOIN tbl_peca                 USING (peca)
		LEFT JOIN tbl_defeito              USING (defeito)
		LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
		WHERE   tbl_orcamento_item.orcamento = $orcamento
		ORDER BY tbl_orcamento_item.orcamento_item";
	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$item_orcamento   = trim(pg_result($res,$k,orcamento_item))   ;
			$item_peca        = trim(pg_result($res,$k,peca))       ;
			$item_preco       = trim(pg_result($res,$k,preco))       ;
			$item_referencia  = trim(pg_result($res,$k,referencia))       ;
			$item_qtde        = trim(pg_result($res,$k,qtde))             ;
			$item_descricao   = trim(pg_result($res,$k,item_descricao))   ;
			$sql_item = "INSERT INTO tbl_faturamento_item		  
						(faturamento, peca, qtde,preco)
						VALUES ($faturamento_codigo, $item_peca,$item_qtde, $item_preco)";
			$res_item = pg_exec ($con,$sql_item);
			$msg_erro .= pg_errormessage($con);

			$sql_est = "UPDATE tbl_estoque
						SET qtde = qtde-$item_qtde
						WHERE peca=$item_peca";
			$res_est = pg_exec ($con,$sql_est);
			$msg_erro .= pg_errormessage($con);
		}
	}

## ATUALIZA O FATURAMENTO
	$sql_est = "UPDATE tbl_orcamento
				SET faturamento = $faturamento_codigo
				WHERE orcamento=$orcamento";
	$res_est = pg_exec ($con,$sql_est);
	$msg_erro .= pg_errormessage($con);

## INSERIR CONTAS A RECEBER
	$parcelas_array = explode("|",$parcelas);
	$valor_parcela=number_format($total_orcamento/count($parcelas_array),2,".","");
	$parc = array();
	for ($i=0;$i<count($parcelas_array);$i++){
		$X = str_pad($i+1, 2, "0", STR_PAD_LEFT);
		$orcamentoX =  str_pad($orcamento, 7, "0", STR_PAD_LEFT);
		$X = $orcamentoX."-".$X;
		$data_tmp = date("Y-m-d",strtotime($data_digitacao_bd)+$parcelas_array[$i]*60*60*24);
		$sql = "INSERT INTO tbl_contas_receber		  
					(
					remessa,
					transacao,
					emissao,
					vencimento,
					valor,
					posto,
					fabrica,
					cliente,
					distribuidor,
					orcamento,
					documento
					)
					VALUES (
					0,
					0,
					'$data_digitacao_bd',
					'$data_tmp', 
					$valor_parcela,
					$login_loja, 
					$login_empresa,
					$cliente,
					0,
					$orcamento,
					'$X'
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT CURRVAL ('tbl_contas_receber_seq')";
		$resW = pg_exec ($con,$sql);
		$codigo_contas_receber = pg_result ($resZ,0,0);
		$msg_erro .= pg_errormessage($con);	

		$sql_est = "UPDATE tbl_contas_receber
					SET valor_dias_atraso = 0
					WHERE contas_receber=$codigo_contas_receber";
		$res_est = pg_exec ($con,$sql_est);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

?>

<script language='javascript'>

</script>

<style>
a{
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-size: 10px;
}
.Titulo{
	font-size: 12px;
	font-weight: bold;
	text-align:left;
}
.Dados{
	font-size:12px;
}
.Dados2{
	font-size:10px;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}
.Preco{
	font-size: 12px;
	padding-left:4px;
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
body{
	font-family:Arial;
}
caption{
	text-align:left;
	font-weight:bold;
}

tr.relatorio td {
	border-bottom: 1px solid #c0c0c0; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}

</style>

<table style=' border:#444444 1px solid; background-color: #FFFFFF ' align='center' width='750' border='0'>
<tr >
	<td nowrap align='center' width='20%' ><b>Data</b> <? echo $data_digitacao; ?></td>
	<td nowrap class='Titulo' align='center' style='padding:10px;text-align:center;font-size:18px'><b><? echo $tipo_os." ".$orcamento; ?></b></td>
	<td nowrap  align='center' width='20%'><b>Hora</b> <? echo $hora_digitacao; ?></td>
</tr>
</table>

<br>
<!--  ################################### EMPRESA  #####################################-->
<table style=' border:#444444 1px solid; background-color: #FFFFFF ' align='center' width='750' border='0'>
<tr >
	<td nowrap class='Titulo' style='padding-left:4px'><b>Empresa:</b></td>
	<td class='Dados' colspan='5'><? echo $loja_nome; ?></td>
</tr>
<tr >
	<td nowrap class='Titulo'style='padding-left:4px'><b>Endereço:</b></td>
	<td class='Dados' colspan='2'><? echo $loja_nome; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Bairro:</b></td>
	<td class='Dados' colspan='2'><? echo $loja_bairro; ?></td>
</tr>
<tr >
	<td nowrap class='Titulo' style='padding-left:4px'><b>Cidade:</b></td>
	<td class='Dados'><? echo $loja_cidade; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Estado:</b></td>
	<td class='Dados'><? echo $loja_estado; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>CEP:</b></td>
	<td class='Dados'><? echo $loja_cep; ?></td>
</tr>
<tr >
	<td nowrap class='Titulo' style='padding-left:4px'><b>Telefone:</b></td>
	<td class='Dados'><? echo $loja_fone; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>FAX:</b></td>
	<td class='Dados'><? echo $loja_fax; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Email:</b></td>
	<td class='Dados'><? echo $loja_email; ?></td>
</tr>
</table>
<br>
<!--  ################################### CLIENTE #####################################-->
<table style=' border:#444444 1px solid; background-color: #FFFFFF ' align='center' width='750' border='0'>
<tr >
	<td nowrap class='Titulo'style='padding-left:4px'><b>Cliente:</b></td>
	<td class='Dados'><? echo $cliente_cliente; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Nome:</b></td>
	<td class='Dados' colspan='3'><? echo $consumidor_nome; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>CPF:</b></td>
	<td class='Dados'><? echo $cliente_cnpj; ?></td>
</tr>
<tr >
	<td nowrap class='Titulo'style='padding-left:4px'><b>Endereço:</b></td>
	<td class='Dados'><? echo $cliente_endereco; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Bairro:</b></td>
	<td class='Dados'><? echo $cliente_bairro; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Cidade:</b></td>
	<td class='Dados'><? echo $cliente_cidade; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Estado:</b></td>
	<td class='Dados'><? echo $cliente_estado; ?></td>
</tr>
<tr >
	<td nowrap class='Titulo' style='padding-left:4px'><b>Fone:</b></td>
	<td class='Dados'><? echo $cliente_fone_residencial; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Emissão</b></td>
	<td class='Dados'><? echo $data_digitacao; ?></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Validade</b></td>
	<td class='Dados'>30 dias</td>
</tr>
</table>
<br>

<? 
###############################################
if ($tipo_orcamento=="FORA_GARANTIA") {
?>
<!--
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
$produto            = trim (pg_result ($res,0,produto))           ;
$produto_referencia = trim (pg_result ($res,0,referencia))        ;
$produto_descricao  = trim (pg_result ($res,0,produto_descricao)) ;
$produto_serie      = trim (pg_result ($res,0,serie))             ;
$produto_aparencia  = trim (pg_result ($res,0,aparencia))         ;
$produto_acessorios = trim (pg_result ($res,0,acessorios))        ;
$revenda            = trim (pg_result ($res,0,revenda))           ;
$data_nf            = trim (pg_result ($res,0,data_nf))           ;
$nota_fiscal        = trim (pg_result ($res,0,nf))                ;
$status             = trim (pg_result ($res,0,status))            ;
$garantia           = trim (pg_result ($res,0,garantia))          ;
$reincidencia       = trim (pg_result ($res,0,reincidencia))      ;
$data_visita        = trim (pg_result ($res,0,data_visita))       ;
$hora_visita        = trim (pg_result ($res,0,hora_visita))       ;
$visita_status      = trim (pg_result ($res,0,status_visita))     ;
-->

<table style=' border:#444444 1px solid; background-color: #FFFFFF ' align='center' width='750' border='0'>
<tr >
	<td nowrap class='Titulo' style='padding-left:4px'>Código: <b  style='font-weight:normal'> <? echo $produto_referencia; ?></b></td>
	<td nowrap class='Titulo'style='padding-left:4px'>Produto: <b style='font-weight:normal'> <? echo $produto_descricao; ?></b></td>
	<td nowrap class='Titulo' style='padding-left:4px'>Nº Série: <b  style='font-weight:normal'><? echo $produto_serie; ?></td>
</tr>
<tr>
<td colspan='3'>
	<table cellpadding='5'>
	<tr class='relatorio' >
		<td nowrap class='Titulo'style='padding-left:4px' width='220'><b>Defeito</b></td>
		<td nowrap class='Titulo' style='padding-left:4px' width='220'><b>Constatado</b></td>
		<td nowrap class='Titulo' style='padding-left:4px' width='220'><b>Solução:</b></td>
	</tr>
	<tr>
		<td class='Dados2' valign='top'><? echo $defeito_reclamado; ?></td>
		<td class='Dados2' valign='top'><? echo $defeito_constatado; ?></td>
		<td class='Dados2' valign='top'><? echo $solucao; ?></td>
	</tr>
	</table>
</td>
</tr>
</table>
<br>

<? } ?>

<!--  ################################### PEÇAS #####################################-->
<table  border='1' cellspacing='0' cellpadding='3' bordercolor='#000' style='border-collapse:collapse;font-size:12px' align='center' width='750' id='lista_pecas'>
<tr >
	<td nowrap class='Titulo'style='padding-left:4px'><b>Peça</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Descrição</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Quantidade</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Valor Unitário</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Valor Total</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Garantia</b></td>
</tr>

<?
	$total_geral = 0; 
	$sql = "SELECT  tbl_orcamento_item.orcamento_item                                  ,
			tbl_orcamento_item.qtde                                            ,
			tbl_orcamento_item.descricao                   AS item_descricao   ,
			tbl_orcamento_item.preco                                           ,
			tbl_orcamento_item.peca                                            ,
			tbl_peca.referencia                                                ,
			tbl_peca.descricao                                                 ,
			tbl_defeito.defeito                                                ,
			tbl_servico_realizado.servico_realizado
		FROM    tbl_orcamento_item
		LEFT JOIN    tbl_peca              USING (peca)
		LEFT JOIN tbl_defeito              USING (defeito)
		LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
		WHERE   tbl_orcamento_item.orcamento = $orcamento
		ORDER BY tbl_orcamento_item.orcamento_item;";
	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		$qtde_item = pg_numrows($res);

		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$item_orcamento          = trim(pg_result($res,$k,orcamento_item))   ;
			$item_peca               = trim(pg_result($res,$k,peca))       ;
			$item_preco              = trim(pg_result($res,$k,preco))       ;
			$item_referencia         = trim(pg_result($res,$k,referencia))       ;
			$item_qtde               = trim(pg_result($res,$k,qtde))             ;
			$item_descricao          = trim(pg_result($res,$k,item_descricao))   ;
			$item_defeito            = trim(pg_result($res,$k,defeito))          ;
			$item_servico            = trim(pg_result($res,$k,servico_realizado));

			$total_geral += $item_preco*$item_qtde;

			$total = number_format($item_qtde*$item_preco,2,'.',' ');

			$item_preco =  number_format($item_preco,2,'.',' ');

			echo "<tr class='relatorio'>";
			echo "<td class='Dados2'>$item_referencia</td>";
			echo "<td class='Dados2'>$item_descricao</td>";
			echo "<td class='Dados2' align='center'>$item_qtde</td>";
			echo "<td class='Dados2' align='right'>R$ $item_preco</td>";
			echo "<td class='Dados2' align='right'>R$ $total</td>";
			echo "<td class='Dados2'></td>";
			echo "</tr>";
		}
	}

?>
</table>
<br>
<!--  LISTAGEM DOS SERVIÇOS - MANUTENÇÃO -->
<table align='center' cellspacing='0' cellpadding='3'>
<tr>
<td valign='top'>
	<!--  LISTAGEM DOS SERVIÇOS - MANUTENÇÃO -->
	<table  border='1' cellspacing='0' cellpadding='2' bordercolor='#000' style='border-collapse:collapse;font-size:12px' align='center' width='450' >
	<caption>
	Serviços
	</caption>
	<tr >
		<td nowrap class='Titulo'style='padding-left:4px'><b>Código</b></td>
		<td nowrap class='Titulo' style='padding-left:4px'><b>Descrição</b></td>
		<td nowrap class='Titulo' style='padding-left:4px'><b>Quantidade</b></td>
		<td nowrap class='Titulo' style='padding-left:4px'><b>Valor Unitário</b></td>
		<td nowrap class='Titulo' style='padding-left:4px'><b>Valor Total</b></td>
	</tr>
	<?
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
				$mo_servico         = trim(pg_result($res,$k,servico));
				$mo_descricao       = trim(pg_result($res,$k,descricao));
				$mo_valor           = number_format(trim(pg_result($res,$k,valor)),2,'.',',');
				$mo_data_lancamento = trim(pg_result($res,$k,data_lancamento));
				$mo_descricao       = trim(pg_result($res,$k,descricao));
				$mo_qtde            = trim(pg_result($res,$k,qtde));

				$total_geral += $mo_qtde*$mo_valor;

				$total = number_format($mo_qtde*$mo_valor,2,'.',' ');
				$mo_valor =  number_format($mo_valor,2,'.',' ');

				echo "<tr class='relatorio'>";
				echo "<td class='Dados2'>$mo_servico</td>";
				echo "<td class='Dados2'>$mo_descricao</td>";
				echo "<td class='Dados2' align='center'>$mo_qtde</td>";
				echo "<td class='Dados2' align='right'>R$ $mo_valor</td>";
				echo "<td class='Dados2' align='right'>R$ $total</td>";
				echo "</tr>";

			}
		}
	?>
	</table>
</td>

<td valign='top'>
	<table  border='0' cellspacing='0' cellpadding='2' bordercolor='#000' style='border:1px solid black;font-size:12px' align='center' width='300' >
	<caption>
	Totais
	</caption>

	<tr class='relatorio'>
		<td nowrap class='Preco'>Total Produto</td>
		<td nowrap class='Preco' align='right' >R$ <? echo number_format($total_pecas,2,'.',','); ?></td>
	</tr >
	<tr class='relatorio'>
		<td nowrap class='Preco'>Total de Serviços</td>
		<td nowrap class='Preco' align='right' ><b>R$ <? echo number_format($total_mao_de_obra,2,'.',','); ?></b></td>
	</tr >
	<tr class='relatorio'>
		<td nowrap class='Preco'>Total de Brindes</td>
		<td nowrap class='Preco' align='right' ><b>R$ <? echo number_format($brinde,2,'.',','); ?></b></td>
	</tr >
	<tr class='relatorio'>
		<td nowrap class='Preco'>Descontos</td>
		<td nowrap class='Preco' align='right' ><b>R$ <? echo number_format($desconto,2,'.',','); ?></b></td>
	</tr >
	<tr class='relatorio'>
		<td nowrap class='Preco'>Acréscimos</td>
		<td nowrap class='Preco' align='right' ><b>R$ <? echo number_format($acrescimo,2,'.',','); ?></b></td>
	</tr >
	<tr class='relatorio'>
		<td nowrap class='Preco'>Total Orçamento</td>
		<td nowrap class='Preco' align='right' ><b>R$ <? echo number_format($total_orcamento,2,'.',','); ?></b></td>
	</tr>
	</table>
</td>

</tr>
</table>
<br>

<!--  ################################### CONDIÇÕES DE PAGAMENTO #####################################-->
<table  border='1' cellspacing='0' cellpadding='3' bordercolor='#000' style='border-collapse:collapse;font-size:12px' align='center' width='750'>
<caption>
Condições de Pagamento
</caption>
<tr class='relatorio'>
	<td nowrap class='Titulo'style='padding-left:4px'><b>Parcela</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Total</b></td>
	<td nowrap class='Titulo'style='padding-left:4px'><b>Parcela</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Total</b></td>
	<td nowrap class='Titulo'style='padding-left:4px'><b>Parcela</b></td>
	<td nowrap class='Titulo' style='padding-left:4px'><b>Total</b></td>
</tr>

<?
		$parcelas = explode("|",$parcelas);
		$numero_parcelas = count($parcelas);

		if ($numero_parcelas>0){
			$data_abertura = converte_data($data_abertura);
			$valor_parcela = number_format($total_orcamento/$numero_parcelas,2,".","");

			for ($i=0;$i<$numero_parcelas;$i++){
				$data_tmp = date("d/m/Y",strtotime($data_abertura)+$parcelas[$i]*60*60*24);
				$X = $i+1;
				if ($i%3==0 AND $i<>0){
						echo "</tr>";
				}
				if ($i%3==0){
						echo "<tr class='relatorio'>";
				}
				echo "<td class='Dados2'><b>$X ª Parc.</b> $data_tmp</td>";
				echo "<td class='Dados2'>$valor_parcela</td>";

			}
		}

?>
</table>
<br>


<!--  ################################### OBSERVAÇÕES GERAIS #####################################-->

<table  border='1' cellspacing='0' cellpadding='3' bordercolor='#000' style='border-collapse:collapse;font-size:12px' align='center' width='750'>
<caption>
Observações
</caption>
<tr class='relatorio'>
	<td class='relatorio'style='padding-left:4px'>&nbsp;
	<?
	if ($tipo_orcamento=="VENDA" || $tipo_orcamento=="ORCA_VENDA") {
	?>
		GARANTIA BALCÃO: 1 ANO PARA MICRO E MONITOR. BRINDE NA COMPRA DE MICRO COM MONITOR: ESTABILIZADOS, CAPAS E PAD. 6 HORAS DE CURSO NA MICROWAY (EXCETO MANUTENÇÃO DE HARDWARE). ENTREGA EM 5 DIAS COM 1 HORA DE ORIENTEÇÃO SOBRE INF. E INTERNET. SUGERIMOS FLASH DA TVC 3413-5333. MONITORES, IMPRESSORAS E MULTIFUNCIONAIS COM GARANTIA DIRETO NAS AUTORIZADAS DOS FABRICANTES EM TODO TERRITORIO NACIONAL. ANÁLISE DE CRÁDITO SIMPLIFICADA: CÓPIA DE RG, CPF, COMPROVANTE DE RESIDÊNCIA, 3 INFORMAÇÕES BANCÁRIA E COMERCIAIS COM COMPROVANTES DE PAGAMENTO DE PARCELAS COMPATÍVEIS COM A COMPRA. COMRPOVANTE DE RENDA (HOLERITE, OU EXTRATO DE RECEBIMENTO EM BANCO)
	<? }else{ ?>
			
	<? } ?>
	</td>
</tr>

</table>
<br>
<?
 //include "rodape.php";
 echo "<script languague='javascript'>window.print();</script>";
 echo "</body>";
 echo "</hmtl>";
 ?>
