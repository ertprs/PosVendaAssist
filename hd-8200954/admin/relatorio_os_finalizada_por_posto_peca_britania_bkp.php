<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

set_time_limit(80);

$admin_privilegios = "auditoria";
$gera_automatico   = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include 'gera_relatorio_pararelo_include.php';
include 'funcoes.php';

$msg_erro    = '';
$layout_menu = "auditoria";
$title       = "Relatório Mensal de Ordens de Serviço Finalizadas";

include "cabecalho.php";
include "javascript_pesquisas.php";?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.divisao{
	width:600px;
	text-align:center;
	margin:0 auto;
	font-size:10px;
	background-color:#FEFCCF;
	border:1px solid #928A03;
	padding:5px;
}
.sucesso{
	width:500px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}


.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
}
</style>

<?php

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = strtolower(trim($_POST["btn_acao"]));
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = strtolower(trim($_GET["btn_acao"]));

if (strlen($btn_acao) > 0) {

	if (strlen(trim($_POST["ano"])) > 0) $ano = trim($_POST["ano"]);
	if (strlen(trim($_GET["ano"])) > 0)  $ano = trim($_GET["ano"]);

	if (strlen(trim($_POST["mes"])) > 0) $mes = trim($_POST["mes"]);
	if (strlen(trim($_GET["mes"])) > 0)  $mes = trim($_GET["mes"]);

	if (strlen(trim($_POST["marca"])) > 0) $marca = trim($_POST["marca"]);
	if (strlen(trim($_GET["marca"])) > 0)  $marca = trim($_GET["marca"]);

}

if (strlen($btn_acao) > 0) {

	if (strlen($ano) == 0 OR strlen($mes) == 0) {
		$msg_erro = " Campos incompletos. Preencha todos os campos.";
	}

}

// if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
// 	include "gera_relatorio_pararelo.php";
// }

// if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
// 	include "gera_relatorio_pararelo_verifica.php";
// }

if (strlen($msg_erro) > 0) {?>
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class='topo'>
	<td colspan='3'>Preencha o MÊS e o ANO para  Gerar o Relatório</td>
</tr>

<tr class='menu_top'>
	<td>Ano</td>
	<td>Mês</td>
</tr>
<tr>
	<td>	<input class="frm" type="text" name="ano" size="13" maxlength="4" value="<? echo $ano ?>" onkeyup=\"re = /\D/g; this.value
= this.value.replace(re, '');\"></td>
	<td>
		<?
			$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
		?>
		<select name="mes" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">".$meses[$i]."</option>\n";
		}
			?>
		</select>
	</td>
</tr>
</table>


<br>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submisso') }" ALT="Confirmar" border='0'>
</center>



<br>

<center>
<b>
OBS:O relatório é gerado somente com as OS finalizadas, pois toma como referência de pesquisa a data de finalização da OS.
</b>
</center>

</form>

<br>

<?

ob_flush();
flush();


if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	echo "<span id='msg_carregando'>Aguarde a geração de arquivo<br><img src='imagens/ajax-carregando.gif'></span>";
	ob_flush();
	flush();

	if (strlen($mes) > 0 AND strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}


	$sql = "SELECT  tbl_os.os                                                           ,
					tbl_os.sua_os                                                       ,
					tbl_os.consumidor_nome                                              ,
					tbl_os.consumidor_revenda                                           ,
					tbl_os.consumidor_fone                                              ,
					tbl_os.serie                                                        ,
					tbl_os.revenda_nome                                                 ,
					tbl_os.data_digitacao                                               ,
					tbl_os.data_abertura                                                ,
					tbl_os.data_fechamento                                              ,
					tbl_os.finalizada                                                   ,
					tbl_os.data_conserto                                                ,
					tbl_os.data_nf                                                      ,
					tbl_os.nota_fiscal_saida                                            ,
					tbl_os.data_nf_saida                                                ,
					tbl_os.obs     as obs_os                                            ,
					tbl_os.obs_reincidencia                                             ,
					data_abertura::date - tbl_os.data_nf::date    AS dias_uso           ,
					tbl_os.produto, tbl_os.posto 										, 
					tbl_os.defeito_constatado 											,
					tbl_os.defeito_reclamado 											, 
					tbl_os.solucao_os 													,
					tbl_os.fabrica 														,
					tbl_os.excluida 													,
					tbl_os.defeito_reclamado_descricao as df_descricao 					,
					tbl_os.aparencia_produto            AS aparencia_produto,
					tbl_os.status_os_ultimo,
					tbl_os.troca_garantia_admin, 
					tbl_os.acessorios                   AS acessorios
					INTO TEMP tmp_of_$login_admin
					FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.finalizada BETWEEN '$data_inicial' AND '$data_final'
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.posto <> 6359; 

		CREATE INDEX tmp_of_os_$login_admin on tmp_of_$login_admin(os);
		CREATE INDEX tmp_of_produto_$login_admin on tmp_of_$login_admin(produto);
		CREATE INDEX tmp_of_posto_$login_admin on tmp_of_$login_admin(posto);
		CREATE INDEX tmp_of_troca_garantia_admin_$login_admin on tmp_of_$login_admin(troca_garantia_admin);
				SELECT	tmp_of_$login_admin.*,
					tbl_produto.referencia 		,
					tbl_produto.descricao,
					tbl_produto.linha,
					tbl_produto.familia,
					tbl_produto.marca,
					tbl_posto_fabrica.codigo_posto                                      ,
					tbl_posto.nome,
					tbl_posto_fabrica.contato_estado,
					tbl_os_item.digitacao_item,
					tbl_os_item.peca, tbl_os_item.servico_realizado, tbl_os_item.pedido,
					tbl_linha.nome                                AS nome_linha,
					troca_admin.login                            AS troca_admin,
					TO_CHAR(data,'dd/mm/yyyy hh:mi') AS data_troca          ,
					setor                            AS setor_troca         ,
					situacao_atendimento             AS situacao_atend_troca,
					tbl_os_troca.observacao          AS observacao_troca    ,
					tbl_peca.referencia             AS peca_referencia_troca ,
					tbl_peca.descricao              AS peca_descricao_troca  ,
					tbl_causa_troca.descricao       AS causa_troca           ,
					tbl_os_troca.modalidade_transporte  AS modalidade_transporte_troca,
					tbl_os_troca.envio_consumidor       AS envio_consumidor_troca,
					tbl_os_extra.orientacao_sac         AS orientacao_sac,
					tbl_os_item.obs                     AS obs,
					CASE WHEN tbl_os_extra.mao_de_obra_desconto > 0 THEN 0
					ELSE tbl_os_extra.mao_de_obra
					END AS mao_de_obra
					into temp tmp_os_$login_admin
					FROM tmp_of_$login_admin
					JOIN tbl_produto                 ON tmp_of_$login_admin.produto = tbl_produto.produto
					and tmp_of_$login_admin.fabrica = tbl_produto.fabrica_i
					JOIN tbl_linha                   ON tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica=$login_fabrica
					JOIN tbl_posto                   ON tmp_of_$login_admin.posto   = tbl_posto.posto
					JOIN tbl_posto_fabrica           ON tbl_posto.posto               = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_os_produto         ON tmp_of_$login_admin.os        = tbl_os_produto.os
					LEFT JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
					LEFT JOIN tbl_admin troca_admin  ON tmp_of_$login_admin.troca_garantia_admin = troca_admin.admin
					LEFT JOIN tbl_os_troca           ON tbl_os_troca.os = tmp_of_$login_admin.os and tbl_os_troca.fabric = $login_fabrica
					LEFT JOIN tbl_peca               ON tbl_os_troca.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
					LEFT JOIN tbl_causa_troca        ON tbl_os_troca.causa_troca = tbl_causa_troca.causa_troca and tbl_causa_troca.fabrica=$login_fabrica
					LEFT JOIN tbl_os_extra           ON tbl_os_extra.os = tmp_of_$login_admin.os;
";

	$sql .= "CREATE INDEX tmp_os_fabrica_os on tmp_os_$login_admin(fabrica,os);";

	$sql .= "CREATE INDEX tmp_os_fabrica_os_peca on tmp_os_$login_admin(peca);";

	$sql .= "CREATE INDEX tmp_os_fabrica_os_pedido on tmp_os_$login_admin(pedido);";

	$sql .= "CREATE INDEX tmp_os_fabrica_os_status_os_ultimo on tmp_os_$login_admin(fabrica,status_os_ultimo);";


	$sql .= "CREATE INDEX tmp_os_fabrica_os_servico_realizado on tmp_os_$login_admin(servico_realizado);";

	$sql .= "CREATE INDEX tmp_os_fabrica_os_posto_excluida on tmp_os_$login_admin(fabrica,os,posto,excluida);";

	$sql .= "SELECT distinct tmp_os_$login_admin.os                                     ,
					tmp_os_$login_admin.sua_os                                          ,
					tmp_os_$login_admin.consumidor_nome                                 ,
					tmp_os_$login_admin.consumidor_revenda                              ,
					tmp_os_$login_admin.consumidor_fone                                 ,
					tmp_os_$login_admin.serie                                           ,
					tmp_os_$login_admin.revenda_nome                                    ,
					tmp_os_$login_admin.df_descricao                                    ,
					tmp_os_$login_admin.obs_os                                          ,
					tmp_os_$login_admin.obs_reincidencia                                ,
					to_char (tmp_os_$login_admin.data_digitacao,'DD/MM/YYYY')  AS data_digitacao,
					to_char (tmp_os_$login_admin.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
					to_char (tmp_os_$login_admin.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					to_char (tmp_os_$login_admin.finalizada,'DD/MM/YYYY')      AS data_finalizada,
					to_char (tmp_os_$login_admin.data_conserto,'DD/MM/YYYY')   AS data_conserto  ,
					to_char (tmp_os_$login_admin.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					tmp_os_$login_admin.dias_uso                                                 ,
					tbl_marca.nome                                AS marca_nome         ,
					tmp_os_$login_admin.referencia                AS produto_referencia ,
					tmp_os_$login_admin.descricao                 AS produto_descricao  ,
					tbl_peca.referencia                           AS peca_referencia    ,
					tbl_peca.descricao                            AS peca_descricao     ,
					tbl_servico_realizado.descricao               AS servico            ,
					tbl_defeito_constatado.descricao              AS defeito_constatado ,
					tbl_defeito_reclamado.descricao               AS defeito_reclamado  ,
					tbl_solucao.descricao                         AS solucao            ,
					tmp_os_$login_admin.nome_linha                AS linha              ,
					tbl_familia.descricao                         AS familia            ,
					TO_CHAR (tmp_os_$login_admin.digitacao_item,'DD/MM/YYYY')  AS data_digitacao_item,
					tmp_os_$login_admin.codigo_posto                                      ,
					tmp_os_$login_admin.nome                           AS nome_posto         ,
					tmp_os_$login_admin.contato_estado              AS estado_posto,
					tbl_faturamento.nota_fiscal                                         ,
					tbl_faturamento.emissao                                             ,

					case
						when tbl_pedido_item.qtde = tbl_pedido_item.qtde_faturada then
							'FATURADO INTEGRAL'
						when tbl_pedido_item.qtde = tbl_pedido_item.qtde_cancelada then
							'CANCELADO TOTAL'
						when tbl_pedido_item.qtde < tbl_pedido_item.qtde_faturada then
							'FATURADO PARCIAL'
					else
						'AGUARDANDO FATURAMENTO'
					end                                            AS status_pedido ,

					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tmp_os_$login_admin.os AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY os_status DESC LIMIT 1) AS status_os,
					tbl_pedido.pedido                                                   ,
					tmp_os_$login_admin.troca_admin,
					tmp_os_$login_admin.data_troca          ,
					tmp_os_$login_admin.setor_troca         ,
					tmp_os_$login_admin.situacao_atend_troca,
					tmp_os_$login_admin.observacao_troca    ,
					tmp_os_$login_admin.peca_referencia_troca ,
					tmp_os_$login_admin.peca_descricao_troca  ,
					tmp_os_$login_admin.causa_troca           ,
					tmp_os_$login_admin.modalidade_transporte_troca,

					tmp_os_$login_admin.envio_consumidor_troca,
					tmp_os_$login_admin.orientacao_sac,
					tmp_os_$login_admin.aparencia_produto,
					tmp_os_$login_admin.acessorios,
					tbl_defeito_reclamado.descricao   AS defeito_reclamado_descricao,
					tmp_os_$login_admin.obs,
					tmp_os_$login_admin.mao_de_obra,
					tmp_os_$login_admin.nota_fiscal_saida ,
					to_char (tmp_os_$login_admin.data_nf_saida,'DD/MM/YYYY')  AS data_nf_saida
					FROM tmp_os_$login_admin				
					LEFT JOIN tbl_peca               ON tmp_os_$login_admin.peca              = tbl_peca.peca           AND tbl_peca.fabrica = $login_fabrica
					LEFT JOIN tbl_familia            ON tmp_os_$login_admin.familia           = tbl_familia.familia     AND tbl_familia.fabrica=$login_fabrica
					LEFT JOIN tbl_marca              ON tbl_marca.marca               = tmp_os_$login_admin.marca
					LEFT JOIN tbl_defeito_reclamado  ON tmp_os_$login_admin.defeito_reclamado      = tbl_defeito_reclamado.defeito_reclamado  AND tbl_defeito_reclamado.fabrica = $login_fabrica
					LEFT JOIN tbl_defeito_constatado ON tmp_os_$login_admin.defeito_constatado     = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
					LEFT JOIN tbl_servico_realizado  ON tmp_os_$login_admin.servico_realizado = tbl_servico_realizado.servico_realizado  AND tbl_servico_realizado.fabrica=$login_fabrica
					LEFT JOIN tbl_solucao            ON tmp_os_$login_admin.solucao_os             = tbl_solucao.solucao AND tbl_solucao.fabrica=$login_fabrica
					LEFT JOIN tbl_pedido             ON tmp_os_$login_admin.pedido            = tbl_pedido.pedido
					LEFT JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tmp_os_$login_admin.peca = tbl_pedido_item.peca
					LEFT JOIN tbl_faturamento_item   ON tbl_faturamento_item.pedido   =
					tmp_os_$login_admin.pedido and tbl_faturamento_item.os = tmp_os_$login_admin.os  and
					tmp_os_$login_admin.peca = tbl_faturamento_item.peca 
					LEFT JOIN tbl_faturamento        ON  tbl_faturamento.faturamento  = tbl_faturamento_item.faturamento and tbl_faturamento.fabrica=$login_fabrica
					LEFT JOIN tbl_status_pedido      ON tbl_pedido.status_pedido      = tbl_status_pedido.status_pedido
					WHERE (tmp_os_$login_admin.status_os_ultimo is null or tmp_os_$login_admin.status_os_ultimo NOT IN(126, 143, 15))
					; ";
	$res      = pg_query($con, $sql);
	$msg_erro = pg_errormessage($con);

	$arquivo_nome  = "/var/www/assist/www/download/os_finalizada_por_posto_peca_" . $login_admin . ".csv";
	$arquivo_nome2 = "os_finalizada_por_posto_peca_" . $login_admin . ".csv";
	$arquivo_zip   = "/var/www/assist/www/download/os_finalizada_por_posto_peca_" . $login_admin . ".zip";
	$arquivo_link  = "/assist/download/os_finalizada_por_posto_peca_" . $login_admin . ".zip";

	if (is_file($arquivo_zip)) {
		unlink($arquivo_zip);
	}

	if (is_file($arquivo_nome)) {
		unlink($arquivo_nome);
	}

	$arquivo = @fopen($arquivo_nome, "w");

	if (!is_resource($arquivo)) {
		$msg_erro = 'Erro ao gerar arquivo, entre em contato com o suporte.';
	}

	ob_flush();
	flush();

	if (!empty($msg_erro)) {

		echo "<center>$msg_erro</center>";

	} else if (pg_numrows($res) > 0) {

		fwrite ($arquivo, "Sua OS");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Consumidor/Revenda");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Consumidor Nome");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Consumidor Fone");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Número de Série");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Solução");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data Digitação");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data Abertura");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data Fechamento");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data Finalizada");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data Conserto");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Mão de Obra");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data NF Compra");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Dias de Uso");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Marca");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Produto Referência");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Produto Descrição");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Linha");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Familia");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Peça Referência");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Peça Descrição");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Defeito Reclamado");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Defeito Constatado");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Serviço");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Digitação Item");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Código Posto");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Nome Posto");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Estado");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Nome Revenda");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Nota Fiscal");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "NF de Saída");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data NF Saída");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Emissão");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Status do Pedido");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Status da OS");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Responsável");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Data");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Trocado Por");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Causa da Troca");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Observação Troca");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Justificativa do Posto");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Aparencia geral do aparelho/produto");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Acessórios deixados junto com o aparelho");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Informações sobre o defeito");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Orientações do SAC ao Posto Autorizado");
		fwrite ($arquivo, ";");
		fwrite ($arquivo, "Justificativa do Pedido de Peça");
		
		fwrite ($arquivo, "\n");

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$os                  = pg_fetch_result ($res,$i,os);
			$sua_os              = pg_fetch_result ($res,$i,sua_os);
			$consumidor_nome     = pg_fetch_result ($res,$i,consumidor_nome);
			$consumidor_revenda  = pg_fetch_result ($res,$i,consumidor_revenda);
			$consumidor_fone     = pg_fetch_result ($res,$i,consumidor_fone);
			$serie               = pg_fetch_result ($res,$i,serie);
			$solucao             = pg_fetch_result ($res,$i,solucao);
			$data_digitacao      = pg_fetch_result ($res,$i,data_digitacao);
			$data_abertura       = pg_fetch_result ($res,$i,data_abertura);
			$data_fechamento     = pg_fetch_result ($res,$i,data_fechamento);
			$data_finalizada     = pg_fetch_result ($res,$i,data_finalizada);
			$data_conserto       = pg_fetch_result ($res,$i,data_conserto);
			$data_nf             = pg_fetch_result ($res,$i,data_nf);
			$dias_uso            = pg_fetch_result ($res,$i,dias_uso);
			$marca_nome          = pg_fetch_result ($res,$i,marca_nome);
			$produto_referencia  = pg_fetch_result ($res,$i,produto_referencia);
			$produto_descricao   = pg_fetch_result ($res,$i,produto_descricao);
			$linha               = pg_fetch_result ($res,$i,linha);
			$familia             = pg_fetch_result ($res,$i,familia);
			$peca_referencia     = pg_fetch_result ($res,$i,peca_referencia);
			$peca_descricao      = pg_fetch_result ($res,$i,peca_descricao);
			$servico             = pg_fetch_result ($res,$i,servico);
			$defeito_constatado  = pg_fetch_result ($res,$i,defeito_constatado);
			$defeito_reclamado   = pg_fetch_result ($res,$i,defeito_reclamado);
			$data_digitacao_item = pg_fetch_result ($res,$i,data_digitacao_item);
			$codigo_posto        = pg_fetch_result ($res,$i,codigo_posto);
			$nome_posto          = pg_fetch_result ($res,$i,nome_posto);
			$estado_posto        = pg_fetch_result ($res,$i,estado_posto);
			$revenda_nome        = pg_fetch_result ($res,$i,revenda_nome);
			$nota_fiscal         = pg_fetch_result ($res,$i,nota_fiscal);
			$emissao             = pg_fetch_result ($res,$i,emissao);
			$status_pedido       = pg_fetch_result ($res,$i,status_pedido);
			$status_os           = pg_fetch_result ($res,$i,status_os);
			$troca_admin                 = pg_fetch_result ($res,$i,troca_admin);
			$data_troca                  = pg_fetch_result ($res,$i,data_troca);
			$setor_troca                 = pg_fetch_result ($res,$i,setor_troca);
			$situacao_atend_troca        = pg_fetch_result ($res,$i,situacao_atend_troca);
			$observacao_troca            = pg_fetch_result ($res,$i,observacao_troca);
			$peca_referencia_troca       = pg_fetch_result ($res,$i,peca_referencia_troca);
			$peca_descricao_troca        = pg_fetch_result ($res,$i,peca_descricao_troca);
			$modalidade_transporte_troca = pg_fetch_result ($res,$i,modalidade_transporte_troca);
			$envio_consumidor_troca      = pg_fetch_result ($res,$i,envio_consumidor_troca);
			$orientacao_sac              = pg_fetch_result ($res,$i,orientacao_sac);
			$causa_troca                 = pg_fetch_result ($res,$i,causa_troca);
			$aparencia_produto           = pg_fetch_result ($res,$i,aparencia_produto);
			$acessorios                  = pg_fetch_result ($res,$i,acessorios);
			$df_descricao                = pg_fetch_result ($res,$i,df_descricao);
			$obs_os                      = pg_fetch_result ($res,$i,obs_os);
			$obs_reincidencia            = pg_fetch_result ($res,$i,obs_reincidencia);
			$mao_de_obra            	 = pg_fetch_result ($res,$i,mao_de_obra);
			$nota_fiscal_saida           = pg_fetch_result ($res,$i,'nota_fiscal_saida');
			$data_nf_saida            	 = pg_fetch_result ($res,$i,'data_nf_saida');

			$sua_os              = str_replace (";","",$sua_os);
			$consumidor_revenda  = str_replace (";","",$consumidor_revenda);
			$consumidor_nome     = str_replace (";","",$consumidor_nome);
			$consumidor_fone     = str_replace (";","",$consumidor_fone);
			$serie               = str_replace (";","",$serie);
			$solucao             = str_replace (";","",$solucao);
			$data_digitacao      = str_replace (";","",$data_digitacao);
			$data_abertura       = str_replace (";","",$data_abertura);
			$data_fechamento     = str_replace (";","",$data_fechamento);
			$data_finalizada     = str_replace (";","",$data_finalizada);
			$data_conserto       = str_replace (";","",$data_conserto);
			$data_nf             = str_replace (";","",$data_nf);
			$dias_uso            = str_replace (";","",$dias_uso);
			$marca_nome          = str_replace (";","",$marca_nome);
			$produto_referencia  = str_replace (";","",$produto_referencia);
			$produto_descricao   = str_replace (";","",$produto_descricao);
			$linha               = str_replace (";","",$linha);
			$familia             = str_replace (";","",$familia);
			$peca_referencia     = str_replace (";","",$peca_referencia);
			$peca_descricao      = str_replace (";","",$peca_descricao);
			$servico             = str_replace (";","",$servico);
			$defeito_constatado  = str_replace (";","",$defeito_constatado);
			$defeito_reclamado   = str_replace (";","",$defeito_reclamado);
			$data_digitacao_item = str_replace (";","",$data_digitacao_item);
			$codigo_posto        = str_replace (";","",$codigo_posto);
			$nome_posto          = str_replace (";","",$nome_posto);
			$estado_posto        = str_replace (";","",$estado_posto);
			$revenda_nome        = str_replace (";","",$revenda_nome);
			$nota_fiscal         = str_replace (";","",$nota_fiscal);
			$emissao             = str_replace (";","",$emissao);
			$status_pedido       = str_replace (";","",$status_pedido);
			$status_os           = str_replace (";","",$status_os);
			$troca_admin                 = str_replace (";","",$troca_admin);
			$data_troca                  = str_replace (";","",$data_troca);
			$setor_troca                 = str_replace (";","",$setor_troca);
			$situacao_atend_troca        = str_replace (";","",$situacao_atend_troca);
			$peca_referencia_troca       = str_replace (";","",$peca_referencia_troca);
			$peca_descricao_troca        = str_replace (";","",$peca_descricao_troca);
			$modalidade_transporte_troca = str_replace (";","",$modalidade_transporte_troca);
			$envio_consumidor_troca      = str_replace (";","",$envio_consumidor_troca);
			$df_descricao                = str_replace (";","",$df_descricao);
			$df_descricao                = str_replace ("null","",$df_descricao);
			$aparencia_produto           = str_replace (";","",$aparencia_produto);
			$acessorios                  = str_replace ("null","",(str_replace (";","",$acessorios)));
			$orientacao_sac            = str_replace("\r","",str_replace("\t","",str_replace("<br />","",str_replace("\n"," ",str_replace("null","",str_replace (";","",$orientacao_sac))))));
			$obs_os            = str_replace("\r","",str_replace("\t","",str_replace("<br />","",str_replace("\n"," ",str_replace("null","",str_replace (";","",$obs_os))))));
			$obs_reincidencia            = str_replace("\r","",str_replace("\t","",str_replace("<br />","",str_replace("\n"," ",str_replace("null","",str_replace (";","",$obs_reincidencia))))));
			$observacao_troca            = str_replace("\r","",str_replace("\t","",str_replace("<br />","",str_replace("\n"," ",str_replace("null","",str_replace (";","",$observacao_troca))))));
			$mao_de_obra       = str_replace (";","",$mao_de_obra);
			$nota_fiscal_saida       = str_replace (";","",$nota_fiscal_saida);
			$data_nf_saida       = str_replace (";","",$data_nf_saida);

			fwrite ($arquivo, $sua_os             );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $consumidor_revenda );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $consumidor_nome    );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $consumidor_fone    );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $serie              );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $solucao            );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_digitacao     );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_abertura      );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_fechamento    );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_finalizada    );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_conserto      );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $mao_de_obra        );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_nf            );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $dias_uso           );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $marca_nome         );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $produto_referencia );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $produto_descricao  );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $linha              );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $familia            );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $peca_referencia    );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $peca_descricao     );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $defeito_reclamado  );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $defeito_constatado );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $servico            );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_digitacao_item);
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $codigo_posto       );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $nome_posto         );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $estado_posto       );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $revenda_nome       );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $nota_fiscal        );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $nota_fiscal_saida  );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $data_nf_saida      );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $emissao            );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $status_pedido      );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $status_os          );
			fwrite ($arquivo, ";"                 );
			fwrite ($arquivo, $troca_admin                 );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $data_troca                  );
			fwrite ($arquivo, ";"                          );

			if(!empty($peca_referencia_troca)) {
				fwrite ($arquivo, $peca_referencia_troca." - ".$peca_descricao_troca        );
			}else{
				fwrite ($arquivo, " " );
			}

			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $causa_troca                 );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $observacao_troca            );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $obs_reincidencia            );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $aparencia_produto           );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $acessorios                  );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $df_descricao                );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $obs_os                      );
			fwrite ($arquivo, ";"                          );
			fwrite ($arquivo, $orientacao_sac              );
			fwrite ($arquivo, ";"                          );
			
			$sql_status = "SELECT
						os_status,
						status_os,
						observacao,
						tbl_admin.login AS login,
						to_char(data, 'DD/MM/YYYY')   as data_status,
						tbl_os_status.admin
						FROM tbl_os_status
						LEFT JOIN tbl_admin USING(admin)
						WHERE os=$os
						AND status_os IN (72,73,62,64,65,87,88,116,117)
						ORDER BY data ASC";
			$res_status = pg_query($con,$sql_status);
			$resultado = pg_num_rows($res_status);
			$conteudo = "";
			if ($resultado>0){
				for ($j=0;$j<$resultado;$j++){
					$os_status          = trim(pg_fetch_result($res_status,$j,os_status));
					$status_os          = trim(pg_fetch_result($res_status,$j,status_os));
					$status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
					$status_admin       = trim(pg_fetch_result($res_status,$j,login));
					$status_data        = trim(pg_fetch_result($res_status,$j,data_status));
					$status_admin2      = trim(pg_fetch_result($res_status,$j,admin));

					if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0){
						$status_observacao = strstr($status_observacao,"Justificativa:");
						$status_observacao = str_replace("Justificativa:","",$status_observacao);
					}

					$status_observacao = trim($status_observacao);

					if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
					if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

					if (strlen($status_admin)>0){
						$status_admin = " ($status_admin)";
					}

					$conteudo .="Data: $status_data     ";
					if ($status_os==72)
						$conteudo .="Justificativa do Posto: ";
					if ($status_os==73)
						$conteudo .="Resposta da Fábrica: ";
					if ($status_os==62)
						$conteudo .="OS em Intervenção ";
					if ($status_os==65)
						$conteudo .="OS em reparo na Fábrica ";
					if ($status_os==64)
						$conteudo .="Resposta da Fábrica: ";
					if ($status_os==87 OR $status_os==116){
						$conteudo .="Fábrica: ";
					}
					if ($status_os==88 OR $status_os==117){
						$conteudo .="Fábrica:";
					}
					$conteudo .="     Obs: $status_observacao     ";
				}
			}
			$conteudo            = str_replace("\r","",str_replace("\t","",str_replace("<br />","",str_replace("\n"," ",str_replace("null","",str_replace (";","",$conteudo))))));

			fwrite ($arquivo,$conteudo);
			
			fwrite ($arquivo, "\n"                );
		}
		
		fclose ($arquivo);

		ob_flush();
		flush();

		system ("cd /var/www/assist/www/download/ ; zip $arquivo_zip $arquivo_nome2 > /dev/null");

		echo "<font size='+2'><a href='" . $arquivo_link . "'> Clique para fazer o download do arquivo gerado </a></font>";
		echo "<script>document.getElementById('msg_carregando').style.display='none';</script>";

	} else {

		echo "<center>Nenhum Resultado Encontrado</center>";

	}
}


echo "<br>";
echo "<br>";
echo "<br>";
echo "<br>";

include "rodape.php";
?>
