<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$os = $_GET['os'];

if ($login_fabrica <> 7) {
	header("Location: os_press.php?os=$os");
}

include_once('anexaNF_inc.php');// Dentro do include estão definidas as fábricas que anexam imagem da NF e os parâmetros.

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					tbl_os.posto                                                     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.hora_abertura, 'HH24:MI') AS hora_abertura        ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					to_char(tbl_os.data_conserto,'DD/MM/YYYY HH24:MI') AS       data_conserto,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_endereco                                       ,
					tbl_os.consumidor_numero                                         ,
					tbl_os.consumidor_complemento                                    ,
					tbl_os.consumidor_bairro                                         ,
					tbl_os.consumidor_cep                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.cliente                                                   ,
					tbl_os.revenda                                                   ,
					tbl_os.os_numero                                                 ,
					tbl_os.obs                                                       ,
					tbl_os.pedido_cliente                                            ,
					tbl_os.serie                                                     ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_os_extra.desconto_peca                                       ,
					tbl_os_extra.classificacao_os                                    ,
					tbl_os_extra.taxa_visita                                         ,
					tbl_os_extra.hora_tecnica                                        ,
					tbl_os_extra.valor_total_hora_tecnica                            ,
					tbl_os_extra.valor_diaria                                        ,
					tbl_os_extra.qtde_diaria                                         ,
					tbl_os_extra.valor_total_diaria                                  ,
					tbl_os_extra.anormalidades                                       ,
					tbl_os_extra.causas                                              ,
					tbl_os_extra.medidas_corretivas                                  ,
					tbl_os_extra.recomendacoes                                       ,
					tbl_os_extra.obs                                     AS obs_extra,
					tbl_os_extra.selo                                                ,
					tbl_os_extra.lacre_encontrado                                    ,
					tbl_os_extra.tecnico                                             ,
					tbl_os_extra.representante                                       ,
					tbl_os_extra.lacre                                               ,
					tbl_os_extra.regulagem_peso_padrao                               ,
					tbl_os_extra.cobrar_regulagem                                    ,
					tbl_os_extra.certificado_conformidade                            ,
					tbl_os_extra.qtde_horas                                          ,
					tbl_os_extra.deslocamento_km                                     ,
					tbl_os_extra.visita_por_km                                       ,
					tbl_os_extra.valor_por_km                                        ,
					tbl_os_extra.valor_total_deslocamento                            ,
					tbl_os_extra.mao_de_obra                                         ,
					tbl_os_extra.mao_de_obra_por_hora                                ,
					tbl_os_extra.valor_total_diaria                                  ,
					tbl_os_extra.valor_total_hora_tecnica                            ,
					tbl_os_extra.desconto_deslocamento                               ,
					tbl_os_extra.desconto_hora_tecnica                               ,
					tbl_os_extra.desconto_diaria                                     ,
					tbl_os_extra.desconto_regulagem                                  ,
					tbl_os_extra.desconto_certificado                                ,
					tbl_condicao.descricao              AS condicao_descricao        ,
					tbl_classificacao_os.descricao      AS classificacao_os_descricao,
					tbl_classificacao_os.garantia       AS classificacao_os_garantia ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os_revenda.os_manutencao                                     ,
					tbl_os.consumidor_nome_assinatura
			FROM    tbl_os
			LEFT JOIN    tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
			LEFT JOIN    tbl_defeito_reclamado USING(defeito_reclamado)
			LEFT JOIN    tbl_produto           USING(produto)
			LEFT JOIN    tbl_os_extra          USING(os)
			LEFT JOIN    tbl_condicao          ON tbl_condicao.condicao = tbl_os.condicao
			LEFT JOIN    tbl_classificacao_os  ON tbl_os_extra.classificacao_os = tbl_classificacao_os.classificacao_os
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$posto                       = pg_result ($res,0,posto);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$hora_abertura               = pg_result ($res,0,hora_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$data_conserto               = pg_result ($res,0,data_conserto);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf              = pg_result ($res,0,consumidor_cpf);

		if (strlen($consumidor_cpf) == 14){
			$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4) ."-". substr($consumidor_cpf,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
		}

		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$obs                         = pg_result ($res,0,obs);
		$pedido_cliente              = pg_result ($res,0,pedido_cliente);
		$serie                       = pg_result ($res,0,serie);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$taxa_visita                 = pg_result ($res,0,taxa_visita);
		$hora_tecnica                = pg_result ($res,0,hora_tecnica);
		$valor_total_hora_tecnica    = pg_result ($res,0,valor_total_hora_tecnica);
		$valor_diaria                = pg_result ($res,0,valor_diaria);
		$qtde_diaria                 = pg_result ($res,0,qtde_diaria);
		$valor_total_diaria          = pg_result ($res,0,valor_total_diaria);
		$anormalidades               = pg_result ($res,0,anormalidades);
		$causas                      = pg_result ($res,0,causas);
		$medidas_corretivas          = pg_result ($res,0,medidas_corretivas);
		$recomendacoes               = pg_result ($res,0,recomendacoes);
		$obs_extra                   = pg_result ($res,0,obs_extra);
		$selo                        = pg_result ($res,0,selo);
		$lacre_encontrado            = pg_result ($res,0,lacre_encontrado);
		$tecnico                     = pg_result ($res,0,tecnico);
		$representante               = pg_result ($res,0,representante);
		$lacre                       = pg_result ($res,0,lacre);
		$desconto_peca               = pg_result ($res,0,desconto_peca);
		$classificacao_os            = pg_result ($res,0,classificacao_os);
		$classificacao_os_garantia   = pg_result ($res,0,classificacao_os_garantia);
		$regulagem_peso_padrao       = pg_result ($res,0,regulagem_peso_padrao);
		$cobrar_regulagem            = pg_result ($res,0,cobrar_regulagem);
		$certificado_conformidade    = pg_result ($res,0,certificado_conformidade);
		$condicao_descricao          = pg_result ($res,0,condicao_descricao);
		$os_numero                   = pg_result ($res,0,os_numero);
		$qtde_horas                  = pg_result ($res,0,qtde_horas);
		$deslocamento_km             = pg_result ($res,0,deslocamento_km);
		$visita_por_km               = pg_result ($res,0,visita_por_km);
		$valor_por_km                = pg_result ($res,0,valor_por_km);
		$valor_total_deslocamento    = pg_result ($res,0,valor_total_deslocamento);
		$mao_de_obra                 = pg_result ($res,0,mao_de_obra);
		$mao_de_obra_por_hora        = pg_result ($res,0,mao_de_obra_por_hora);
		$classificacao_os_descricao  = pg_result ($res,0,classificacao_os_descricao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$valor_total_hora_tecnica    = pg_result ($res,0,valor_total_hora_tecnica);
		$valor_total_diaria          = pg_result ($res,0,valor_total_diaria);
		$desconto_deslocamento       = pg_result ($res,0,desconto_deslocamento);
		$desconto_hora_tecnica       = pg_result ($res,0,desconto_hora_tecnica);
		$desconto_diaria             = pg_result ($res,0,desconto_diaria);
		$desconto_regulagem          = pg_result ($res,0,desconto_regulagem);
		$desconto_certificado        = pg_result ($res,0,desconto_certificado);
		$os_manutencao               = pg_result ($res,0,os_manutencao);
		$assinou_os                  = pg_result ($res,0,consumidor_nome_assinatura);
		
		if ($os_manutencao == 't'){
			$sql = "SELECT  tbl_os_revenda.os_revenda,
							tbl_os_revenda.taxa_visita,
							tbl_os_revenda.visita_por_km,
							tbl_os_revenda.valor_por_km,
							tbl_os_revenda.veiculo,
							tbl_os_revenda.hora_tecnica,
							tbl_os_revenda.valor_diaria,
							tbl_os_revenda.qtde_horas,
							tbl_os_revenda.regulagem_peso_padrao,
							tbl_os_revenda.desconto_deslocamento,
							tbl_os_revenda.desconto_hora_tecnica,
							tbl_os_revenda.desconto_diaria,
							tbl_os_revenda.valor_total_hora_tecnica,
							tbl_os_revenda.valor_total_diaria,
							tbl_os_revenda.valor_total_deslocamento
					FROM   tbl_os
					JOIN   tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
					WHERE  tbl_os.os = $os
					AND    tbl_os.fabrica = $login_fabrica
					AND    tbl_os.posto   = $posto";
			$res2 = pg_exec ($con,$sql);
			if (pg_numrows ($res2) > 0 ) {
				$os_revenda				= pg_result ($res2,0,os_revenda);
				$taxa_visita			= pg_result ($res2,0,taxa_visita);
				$visita_por_km			= pg_result ($res2,0,visita_por_km);
				$valor_por_km			= pg_result ($res2,0,valor_por_km);
				$veiculo				= pg_result ($res2,0,veiculo);
				$hora_tecnica			= pg_result ($res2,0,hora_tecnica);
				$valor_diaria			= pg_result ($res2,0,valor_diaria);
				$regulagem_peso_padrao	= pg_result ($res2,0,regulagem_peso_padrao);
				$desconto_deslocamento	= pg_result ($res2,0,desconto_deslocamento);
				$desconto_hora_tecnica	= pg_result ($res2,0,desconto_hora_tecnica);
				$desconto_diaria		= pg_result ($res2,0,desconto_diaria);

				$valor_total_hora_tecnica = pg_result ($res2,0,valor_total_hora_tecnica);
				$valor_total_diaria		  = pg_result ($res2,0,valor_total_diaria);
				$valor_total_deslocamento = pg_result ($res2,0,valor_total_deslocamento);
			}
		}

		if ($desconto_regulagem>0 and $regulagem_peso_padrao>0){
			$regulagem_peso_padrao = $regulagem_peso_padrao - ($regulagem_peso_padrao*$desconto_deslocamento / 100);
		}
		
		if ($desconto_certificado>0 and $certificado_conformidade>0){
			$certificado_conformidade = $certificado_conformidade - ($certificado_conformidade*$desconto_certificado / 100);
		}
		
		if ($visita_por_km == 't' and $valor_por_km > 0){
			//$taxa_visita = $valor_por_km * $deslocamento_km * 2;
			$taxa_visita = $valor_total_deslocamento;
		}

		/* PARA OS EM GARANTIA, NAO COBRAR MAO DE OBRA, PEÇAS E REGULAGEM || HD 51554 1==2 */
		if ($classificacao_os_garantia =='t' AND 1==2){
			$valor_total_diaria       = 0;
			$valor_total_hora_tecnica = 0;
			$regulagem_peso_padrao    = 0;
		}

		/* ZERA VALORES PARA OS CANCELADAS - HD 37170 */
		if ($classificacao_os == 5){
			$valor_total_diaria       = 0;
			$valor_total_hora_tecnica = 0;
			$regulagem_peso_padrao    = 0;
			$certificado_conformidade = 0;
			$regulagem_peso_padrao    = 0;
			$mao_de_obra              = 0;
			$taxa_visita              = 0;
		}

		/* Calculo da Mao de Obra */
		$mao_de_obra = $valor_total_hora_tecnica + $valor_total_diaria;

		//Não esta mais sendo gravado o id do cliente na OS
		if (strlen($cliente) > 0 AND 1==2) {
			$sql = "SELECT  tbl_cliente.endereco   ,
							tbl_cliente.numero     ,
							tbl_cliente.complemento,
							tbl_cliente.bairro     ,
							tbl_cliente.cep        ,
							tbl_cliente.cpf        ,
							tbl_cliente.rg
					FROM    tbl_cliente
					WHERE   tbl_cliente.cliente = $cliente;";
			$res1 = pg_exec ($con,$sql);
			
			if (pg_numrows($res1) > 0) {
				$consumidor_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
				$consumidor_numero      = trim(pg_result ($res1,0,numero));
				$consumidor_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
				$consumidor_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
				$consumidor_cep         = trim(pg_result ($res1,0,cep));
				$consumidor_cep         = substr($consumidor_cep,0,2) .".". substr($consumidor_cep,2,3) ."-". substr($consumidor_cep,5,3);
				$consumidor_cpf         = trim(pg_result ($res1,0,cpf));
				
				if (strlen($consumidor_cpf) == 14){
					$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4) ."-". substr($consumidor_cpf,12,2);
				}elseif(strlen($consumidor_cpf) == 11){
					$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
				}
				$consumidor_rg          = trim(pg_result ($res1,0,rg));
			}
		}

		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_exec ($con,$sql);
			
			if (pg_numrows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
				$revenda_cep         = trim(pg_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}
		# HD 48224
		$sqlAbriu = "SELECT tbl_os.admin,
							tbl_admin.nome_completo 
						FROM tbl_os 
						JOIN tbl_admin ON tbl_os.admin = tbl_admin.admin 
						WHERE tbl_os.os = $os";
		$resAbriu = pg_exec ($con,$sqlAbriu);

		if (pg_numrows($resAbriu) > 0){
			$abriu_os = pg_result($resAbriu,0,nome_completo);
		}else{
			$abriu_os = ucfirst($login_nome);
		}
	}
}

$title = "Confirmação de Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

?>
<style type="text/css">

body {
	margin: 0px;
}

.titulo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	text-align: center;
	color: #596d9b;
	background: #ced7e7;
	border-top: double #596d9b;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	text-align: center;
	background: #d9e2ef;
}

</style>
<p>

<?/*
if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else 
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
*/
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD colspan="4"><img src="imagens/top_esq.gif"></TD>
</TR>


<TR>

	<TD class='titulo' colspan='2'>OS FABRICANTE</TD>
	<TD class='titulo' colspan='1'>DATA DE ABERTURA</TD>
	<TD class='titulo' colspan='1'>DATA DE CONSERTO</TD>
</TR>
<TR>
	<TD class='conteudo' colspan='2'><? echo $sua_os?></TD>
	<TD class='conteudo' colspan='1'><? echo $data_abertura . " " . $hora_abertura?></TD>
	<TD class='conteudo' colspan='1'><? echo $data_conserto?></TD>
</TR>
<TR>
	<TD class='titulo' colspan='2'>PEDIDO FATURADO</TD>
	<TD class='titulo' colspan='2'>NÚMERO DE SÉRIE</TD>
</TR>
<TR>
	<TD class='conteudo' colspan='2'><? echo $pedido_cliente?></TD>
	<TD class='conteudo' colspan='2'><? echo $serie?></TD>
</TR>

<?/*if (strlen($consumidor_revenda) == 0){} else{
echo "<TR>";
echo "	<TD class='titulo'>OS FABRICANTE</TD>";
echo "	<TD class='titulo'>&nbsp;</TD>";
echo "	<TD class='titulo' colspan='2'>DATA DE ABERTURA</TD>";
echo "</TR>";
echo "<TR>";
echo "	<TD class='conteudo' > $sua_os </TD>";
echo "	<TD class='conteudo' > $consumidor_revenda</TD>";
echo "	<TD class='conteudo' colspan='2'> $data_abertura </TD>";
echo "</TR>";
}*/?>

<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CIDADE</TD>
	<TD class="titulo">ESTADO</TD>
	<TD class="titulo">FONE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
	<TD class="conteudo"><? echo $consumidor_numero ?></TD>
	<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
	<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">CEP</TD>
	<TD class="titulo">CPF/CNPJ DO CONSUMIDOR</TD>
	<TD class="titulo">RG</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
	<TD class="conteudo"><? echo $consumidor_rg ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">CNPJ REVENDA</TD>
	<TD class="titulo">NOME DA REVENDA</TD>
	<TD class="titulo">NF NÚMERO</TD>
	<TD class="titulo">DATA DA NF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
	<TD class="titulo">CEP</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_endereco ?></TD>
	<TD class="conteudo"><? echo $revenda_numero ?></TD>
	<TD class="conteudo"><? echo $revenda_complemento ?></TD>
	<TD class="conteudo"><? echo $revenda_bairro ?></TD>
	<TD class="conteudo"><? echo $revenda_cep ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">OBSERVAÇÕES</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">PRODUTO REFERÊNCIA</TD>
	<TD class="titulo" colspan="3">DESCRIÇÃO DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $produto_referencia ?></TD>
	<TD class="conteudo" colspan="3"><? echo $produto_descricao ?></TD>
</TR>
</TABLE>

<p>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">APARÊNCIA</TD>
	<TD class="titulo">ACESSÓRIOS</TD>
	<TD class="titulo">DEFEITO RECLAMADO</TD>
</TR>
<TR>
	<TD class="conteudo">&nbsp;<? echo $aparencia_produto; ?></TD>
	<TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
	<TD class="conteudo">&nbsp;
<?
		if (strlen($defeito_reclamado) > 0) {
			$sql = "SELECT tbl_defeito_reclamado.descricao
					FROM   tbl_defeito_reclamado
					WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
					//WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				$descricao_defeito = trim(pg_result($res,0,descricao));
				echo $descricao_defeito ." - ";
			}
		}
		echo $defeito_reclamado_descricao;
?>
	</TD>
</TR>
</TABLE>

<p>

<!-- exibe conteudo de os_extra-->
<table width='700' border='0' cellpadding="0" cellspacing="1">
	<tr>
		<td class="titulo">Data</td>
		<td class="titulo">Início  Serviço   <!-- Chegada Cliente --></td>
		<td class="titulo">Término Serviço   <!-- Saída Cliente --></td>
		<td class="titulo">Início  Intervalo <!-- Saída Almoço --></td>
		<td class="titulo">Término Intervalo <!-- Chegada Almoço --></td>
	</tr>
<?
if (strlen($os) > 0) {
	$sql = "SELECT os_visita FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	if ($os_manutencao == 't'){
		$sql = "SELECT os_visita FROM tbl_os_visita WHERE os_revenda = $os_numero ORDER BY os_visita";
	}
	$vis = pg_exec ($con,$sql);
	$qtde_visitas = pg_numrows($vis);
}

for($x=0; $x < 5; $x++) {

	if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
		if ($qtde_visitas > 0) {
			$os_visita = trim(@pg_result($vis,$x,os_visita));
		}

		if (strlen($os_visita) > 0) {
			$sql =  "SELECT tbl_os_visita.os_visita                                                       ,
							to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                ,
							to_char(tbl_os_visita.hora_saida_sede, 'HH24:MI')      AS hora_saida_sede     ,
							tbl_os_visita.km_saida_sede                                                   ,
							to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente,
							tbl_os_visita.km_chegada_cliente                                              ,
							to_char(tbl_os_visita.hora_saida_almoco, 'HH24:MI')    AS hora_saida_almoco   ,
							tbl_os_visita.km_saida_almoco                                                 ,
							to_char(tbl_os_visita.hora_chegada_almoco, 'HH24:MI')  AS hora_chegada_almoco ,
							tbl_os_visita.km_chegada_almoco                                               ,
							to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente  ,
							tbl_os_visita.km_saida_cliente                                                ,
							to_char(tbl_os_visita.hora_chegada_sede, 'HH24:MI')    AS hora_chegada_sede   ,
							tbl_os_visita.km_chegada_sede
					FROM    tbl_os_visita
					WHERE   tbl_os_visita.os_visita = $os_visita
					ORDER BY tbl_os_visita.os_visita;";
			$vis1 = pg_exec ($con,$sql);

			if (@pg_numrows($vis1) > 0) {
				$os_visita            = trim(pg_result($vis1,0,os_visita));
				$data                 = trim(pg_result($vis1,0,data));
				$hora_saida_sede      = trim(pg_result($vis1,0,hora_saida_sede));
				$km_saida_sede        = trim(pg_result($vis1,0,km_saida_sede));
				$hora_chegada_cliente = trim(pg_result($vis1,0,hora_chegada_cliente));
				$km_chegada_cliente   = trim(pg_result($vis1,0,km_chegada_cliente));
				$hora_saida_almoco    = trim(pg_result($vis1,0,hora_saida_almoco));
				$km_saida_almoco      = trim(pg_result($vis1,0,km_saida_almoco));
				$hora_chegada_almoco  = trim(pg_result($vis1,0,hora_chegada_almoco));
				$km_chegada_almoco    = trim(pg_result($vis1,0,km_chegada_almoco));
				$hora_saida_cliente   = trim(pg_result($vis1,0,hora_saida_cliente));
				$km_saida_cliente     = trim(pg_result($vis1,0,km_saida_cliente));
				$hora_chegada_sede    = trim(pg_result($vis1,0,hora_chegada_sede));
				$km_chegada_sede      = trim(pg_result($vis1,0,km_chegada_sede));

				echo "<TR>\n";
				echo "<TD class='conteudo'>$data</TD>\n";
				#echo "<TD class='conteudo'>$hora_saida_sede</TD>\n";
				#echo "<TD class='conteudo'>$km_saida_sede</TD>\n";
				echo "<TD class='conteudo'>$hora_chegada_cliente</TD>\n";
				#echo "<TD class='conteudo'>$km_chegada_cliente</TD>\n";
				echo "<TD class='conteudo'>$hora_saida_cliente</TD>\n";
				#echo "<TD class='conteudo'>$km_saida_cliente</TD>\n";
				#echo "<TD class='conteudo'>$hora_chegada_sede</TD>\n";
				#echo "<TD class='conteudo'>$km_chegada_sede</TD>\n";
				echo "<TD class='conteudo'>$hora_saida_almoco</TD>\n";
				#echo "<TD class='conteudo'>$km_saida_almoco</TD>\n";
				echo "<TD class='conteudo'>$hora_chegada_almoco</TD>\n";
				#echo "<TD class='conteudo'>$km_chegada_almoco</TD>\n";
				echo "</TR>\n";
			}
		}
	}
}
?>
</table>

<p>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">ANORMALIDADES ENCONTRADAS</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $anormalidades; ?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">CAUSA DAS ANORMALIDADES</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $causas; ?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">MEDIDAS CORRETIVAS</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $medidas_corretivas; ?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">RECOMENDAÇÕES AO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $recomendacoes ?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">OBSERVAÇÕES</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs_extra ?>&nbsp;</TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">ABERTO POR</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $abriu_os; ?></TD>
</TR>
<TR>
	<TD class="titulo">ASSINATURA</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $assinou_os; ?></TD>
</TR>
</TABLE>

<p>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD colspan="9"><img src="imagens/top_diagnostico.gif"></TD>
</TR>
<TR>
	<TD class="titulo" rowspan="2">COMPONENTE</TD>
	<TD class="titulo" rowspan="2">DEFEITO</TD>
	<TD class="titulo" rowspan="2">SERVIÇO</TD>
	<TD class="titulo" rowspan="2">PEDIDO</TD>
	<TD class="titulo" rowspan="2">PEDIDO FATURADO</TD>
	<TD class="titulo" rowspan="2">NOTA FISCAL</TD>
	<TD class="titulo" colspan="3">PREÇO</TD>
<TR>
	<TD class="titulo">UNITÁRIO</TD>
	<TD class="titulo">TOTAL</TD>
	<TD class="titulo">IPI</TD>
</TR>
<?
	$sql = "SELECT  tbl_os_item.serigrafia                                        ,
					tbl_os_item.pedido    AS pedido_item                          ,
					tbl_os_item.peca                                              ,
					tbl_os_item.qtde                                              ,
					tbl_os_item.servico_realizado                                 ,
					tbl_os_item.pedido_cliente                                    ,
					tbl_defeito.descricao AS defeito                              ,
					tbl_peca.referencia   AS referencia_peca                      ,
					tbl_peca.descricao    AS descricao_peca                       ,
					tbl_peca.ipi          AS ipi                                  ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_os_item.preco     AS preco_os                             ,
					tbl_tabela_item.preco AS preco_item                           
			FROM      tbl_os_produto
			JOIN      tbl_os          ON tbl_os_produto.os       = tbl_os.os
			JOIN      tbl_produto     ON tbl_produto.produto     = tbl_os.produto
			JOIN	  tbl_os_item     ON tbl_os_item.os_produto  = tbl_os_produto.os_produto 
			JOIN      tbl_peca        ON tbl_peca.peca           = tbl_os_item.peca 
			LEFT JOIN tbl_condicao    ON tbl_condicao.condicao   = tbl_os.condicao
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_condicao.tabela AND tbl_tabela_item.peca  = tbl_peca.peca
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			WHERE     tbl_os_produto.os = $os
			ORDER BY tbl_produto.referencia;";
	$res = pg_exec ($con,$sql);
	$total = pg_numrows ($res);

	for ($i = 0 ; $i < $total ; $i++) {
		$pedido = trim(pg_result ($res,$i,pedido_item));
		$peca   = trim(pg_result ($res,$i,peca));
		$ipi    = pg_result ($res,$i,ipi);
		$preco  = pg_result ($res,$i,preco_item);
		$preco_os  = pg_result ($res,$i,preco_os);
		$qtde   = pg_result($res,$i,qtde);
		$pedido_cliente2    = pg_result($res,$i,pedido_cliente);

		if(strlen($preco_os)>0) $preco = $preco_os;
		
		if (strlen($desconto_peca) > 0){
			$preco = $preco - ($preco * ($desconto_peca / 100));
		}

		$preco_unitario = $preco;

		$preco  = $preco + ($preco_unitario * $ipi / 100);

		$valor_total = $qtde * $preco;
		$total_geral = $total_geral + $valor_total;

		$unit = number_format ($preco_unitario,2,',','.');
		$all  =  number_format ($valor_total,2,',','.');
		
		if (strlen($pedido) > 0) {
			$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
					FROM    tbl_faturamento
					JOIN    tbl_faturamento_item USING (faturamento)
					WHERE   tbl_faturamento.pedido    = $pedido
					AND     tbl_faturamento_item.peca = $peca;";
			$resx = pg_exec ($con,$sql);
			
			if (pg_numrows ($resx) > 0) {
				$nf = trim(pg_result($resx,0,nota_fiscal));
			}else{
				$nf = "";
			}
		}else{
			if (!empty($pedido_cliente2)) {
				$sqlxx  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento_item.pedido    = $pedido_cliente2
						AND     tbl_faturamento_item.peca = $peca;";
				$resxx = pg_exec ($con,$sqlxx);
				
				if (pg_numrows ($resxx) > 0) {
					$nf = trim(pg_result($resxx,0,nota_fiscal));
				}else{
					$nf = "";
				}
			}
		}

		if (pg_result($res,$i,servico_realizado) == 36){
			$recuperadas = $recuperadas + (pg_result($res,$i,qtde) * pg_result($res,$i,preco_item));
		}else{
			$pecas = $pecas + $valor_total;
		}

/*
		if (pg_result($res,$i,servico_realizado) == 36){
			$recuperadas = $recuperadas + (pg_result($res,$i,qtde) * pg_result($res,$i,preco_item));
		}elseif (pg_result($res,$i,servico_realizado) == 12){
			$pecas = $pecas + (pg_result($res,$i,qtde) * pg_result($res,$i,preco_item));
		}
*/
?>
<TR>
<!-- 	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao); ?></TD> -->
	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,referencia_peca) . " - " . pg_result ($res,$i,descricao_peca) ?></TD>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,defeito) ?></TD>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,servico_realizado_descricao) ?></TD>
	<TD class="conteudo" style="text-align:CENTER;"><a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'><? echo $pedido ?></a>&nbsp;</TD>
	<TD class='conteudo' style='text-align:left;'><?php echo $pedido_cliente2 ?></TD>
	<TD class="conteudo" style="text-align:CENTER;"><? echo $nf ?>&nbsp;</TD>
	<TD class="conteudo" style="text-align:right;"><? echo $unit ?>&nbsp;</TD>
	<TD class="conteudo" style="text-align:right;"><? echo $all ?>&nbsp;</TD>
	<TD class="conteudo" style="text-align:right;" nowrap><? echo "$ipi %" ?>&nbsp;</TD>
</tr>
<?
	}

	/* PARA OS EM GARANTIA, NAO COBRAR MAO DE OBRA, PEÇAS E REGULAGEM */
	if ($classificacao_os_garantia =='t'){
		$pecas = 0;
	}

	/* ZERA VALORES PARA OS CANCELADAS - HD 37170 */
	if ($classificacao_os == 5){
		$pecas = 0;
	}
?>
</TABLE>

<?
	/* HD 37870 */
	/* Valores das Outras OS (nos casos de OS Multipla */
	$valores_os = 0;
	/* HD 52362 */
	/* Valores dos Certificados das outras OS */
	$total_certificado_conformidade = 0;

	if ($os_manutencao == 't' AND strlen($os_revenda)>0) {

		$sql = "SELECT  tbl_os_item.peca                                              ,
						tbl_os_item.qtde                                              ,
						tbl_peca.ipi          AS ipi                                  ,
						tbl_tabela_item.preco AS preco_item                           ,
						tbl_os_extra.desconto_peca
				FROM      tbl_os
				JOIN      tbl_os_extra    ON tbl_os_extra.os           = tbl_os.os
				JOIN      tbl_os_produto  ON tbl_os_produto.os         = tbl_os.os
				LEFT JOIN tbl_os_revenda  ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
				JOIN      tbl_produto     ON tbl_produto.produto       = tbl_os.produto
				JOIN	  tbl_os_item     ON tbl_os_item.os_produto    = tbl_os_produto.os_produto 
				JOIN      tbl_peca        ON tbl_peca.peca             = tbl_os_item.peca 
				LEFT JOIN tbl_condicao    ON tbl_condicao.condicao     = tbl_os.condicao
				LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela    = tbl_condicao.tabela AND tbl_tabela_item.peca  = tbl_peca.peca
				WHERE tbl_os.os <> $os
				AND   tbl_os_revenda.os_revenda = $os_revenda
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto 
				AND   tbl_os_extra.classificacao_os <> 46 ";
		$res = pg_exec ($con,$sql);
		$total = pg_numrows ($res);
		for ($i = 0 ; $i < $total ; $i++) {
			$peca   = pg_result ($res,$i,peca);
			$ipi    = pg_result ($res,$i,ipi);
			$preco  = pg_result ($res,$i,preco_item);
			$qtde   = pg_result($res,$i,qtde);
			$desconto_peca_item = pg_result($res,$i,desconto_peca);
			

			if (strlen($desconto_peca_item) > 0){
				$preco = $preco - ($preco * ($desconto_peca_item / 100));
			}

			$preco_unitario = $preco;

			$preco  = $preco + ($preco_unitario * $ipi / 100);

			$valor_total = $qtde * $preco;
			$valores_os = $valores_os + $valor_total;
		}

		/* HD 52362 */
		/* Valores dos Certificados das outras OS */
		$sql = "SELECT    SUM(tbl_os_extra.certificado_conformidade) AS total_certificado_conformidade
				FROM      tbl_os
				JOIN      tbl_os_extra    ON tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_os_revenda  ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
				WHERE tbl_os.os <> $os
				AND   tbl_os_revenda.os_revenda = $os_revenda
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto 
				AND   tbl_os_extra.classificacao_os <> 46 ";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0 ){
			$total_certificado_conformidade   = pg_result ($res,0,total_certificado_conformidade);
		}
	}

?>
<p>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">SELO</TD>
	<TD class="titulo">LACRE</TD>
	<TD class="titulo">LACRE ENCONTRADO</TD>
	<TD class="titulo">TÉCNICO</TD><?php
	if ($_COOKIE['cook_login_tipo_posto'] == 214 || $_COOKIE['cook_login_tipo_posto'] == 215) {//HD 254633?>
		<TD class="titulo">REPRESENTANTE</TD><?php
	}?>
	<TD class="titulo">CLASS. OS</TD>
	<TD class="titulo">CONDIÇÃO PAGTO.</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $selo ?></TD>
	<TD class="conteudo"><? echo $lacre ?></TD>
	<TD class="conteudo"><? echo $lacre_encontrado ?></TD>
	<TD class="conteudo"><? echo $tecnico ?></TD><?php
	if ($_COOKIE['cook_login_tipo_posto'] == 214 || $_COOKIE['cook_login_tipo_posto'] == 215) {//HD 254633
		$sql_repre = "SELECT nome FROM tbl_representante where representante = $representante";
		$res_repre = @pg_exec($con, $sql_repre);
		if (@pg_numrows($res_repre) > 0) {
			$nome_representante = @pg_result($res_repre, 0, 'nome');
		}?>
		<TD class="conteudo"><? echo $nome_representante ?></TD><?php
	}?>
	<TD class="conteudo"><? echo $classificacao_os_descricao ?></TD>
	<TD class="conteudo"><? echo $condicao_descricao ?></TD>
</TR>
</TABLE>

<BR>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">REGULAGEM PESO PADRÃO</TD>
	<TD class="conteudo" width='150' style="text-align:right; padding-right: 10px;"><? if ($cobrar_regulagem=='t') echo number_format($regulagem_peso_padrao,2,',','.'); else echo number_format($regulagem_peso_padrao=0,2,',','.'); ?></TD>
</TR>
<TR>
	<TD class="titulo">CERTIFICADO DE CONFORMIDADE</TD>
	<TD class="conteudo" width='150' style="text-align:right; padding-right: 10px;"><? echo number_format($certificado_conformidade,2,',','.'); ?></TD>
</TR>
<!--<TR>
	<TD class="titulo">RECUPERADAS</TD>
	<TD class="conteudo" width='150' style="text-align:right; padding-right: 10px;"><? echo number_format($recuperadas,2,',','.'); ?></TD>
</TR>-->
<TR>
	<TD class="titulo">PEÇAS</TD>
	<TD class="conteudo" style="text-align:right; padding-right: 10px;"><? echo number_format($pecas,2,',','.'); ?></TD>
</TR>
<TR>
	<TD class="titulo">MÃO-DE-OBRA</TD>
	<TD class="conteudo" style="text-align:right; padding-right: 10px;"><? 
	echo number_format($mao_de_obra,2,',','.'); 
	?></TD>
</TR>
<TR>
	<TD class="titulo">TAXA DE VISITA</TD>
	<TD class="conteudo" style="text-align:right; padding-right: 10px;"><? 
	echo number_format($taxa_visita,2,',','.'); 
	?></TD>
</TR>
<TR>
	<TD class="titulo">TOTAL</TD>
	<TD class="conteudo" style="text-align:right; padding-right: 10px; font-size: 11px"><b>
	<? 
		$total_geral = $regulagem_peso_padrao + $certificado_conformidade + $taxa_visita + $mao_de_obra + $pecas + $recuperadas;
		echo number_format($total_geral,2,',','.');
	?>
	</b></TD>
</TR>
<?if((strlen($valores_os)>0 AND $valores_os > 0) or (strlen($total_geral)>0 AND $total_geral > 0)){ /* HD 37870 */ ?>

<TR>
	<TD class="titulo">TOTAL GERAL DAS OS</TD>
	<TD class="conteudo" style="text-align:right; padding-right: 10px;"><? 
	echo number_format($total_geral + $valores_os + $total_certificado_conformidade,2,',','.'); 
	?></TD>
</TR>
<?}?>
</TABLE>
<BR>
<BR>
<?php
	if ($S3_sdk_OK) {
		include_once S3CLASS;

		$s3ge = new anexaS3('od', (int) $login_fabrica);
		$S3_online = is_object($s3ge);

		if ($s3ge->temAnexos($os)) {
			$link = getAttachLink($s3ge->url, '', true);
			if($link['ico'] == 'image.ico'){
				$link['ico'] = $link['url'];
			}else{
				$link['ico'] = "imagens/image.ico";
			}
			echo "<br>";
			echo "<table align='center' class='tabela'>
					<tr class='titulo_tabela'>
						<td style='color: #fff; font-family: Arial; font-size: 8pt; background-color: #485989;'>ORDEM DE SERVIÇO DIGITALIZADA</td>
					</tr>
					<tr>
						<td align='center'>";
							echo createHTMLLink($link['url'],"<img width='100' src='{$link['ico']}' />", "target='_blank'");
			echo "		</td>
					</tr>
				  </table> <BR><BR>";
		}
	}
?>

<!-- =========== FINALIZA TELA NOVA============== -->

<div id='container'>
	<div id="contentleft2" style="width: 150px;">
		&nbsp;
	</div>

	<div id="contentleft2" style="width: 150px;">
		<a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a>
	</div>
	<div id="contentleft2" style="width: 150px;">
		<a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a>
	</div>
</div>

<? include "rodape.php"; ?>
