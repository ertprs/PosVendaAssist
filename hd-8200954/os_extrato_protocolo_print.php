<?php


if (strlen(trim($_GET["extrato"])) > 0) $extrato = trim($_GET["extrato"]);

$sql = "SELECT  tbl_posto_fabrica.tipo_posto            ,
				tbl_posto_fabrica.posto                 ,
				tbl_posto_fabrica.reembolso_peca_estoque
		FROM    tbl_posto_fabrica
		JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto_fabrica.posto
		WHERE   tbl_extrato.extrato       = $extrato
		AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$posto                  = trim(pg_result($res,0,posto));
	$tipo_posto             = trim(pg_result($res,0,tipo_posto));
	$reembolso_peca_estoque = trim(pg_result($res,0,reembolso_peca_estoque));
}

#if ($reembolso_peca_estoque == 't' AND $extrato > 46902) {
#	header ("Location: os_extrato_detalhe_print_blackedecker_TESTE.php?extrato=".$extrato);
#	exit;
#}

$layout_menu = "financeiro";
$title = "Black & Decker - Detalhe Extrato - Ordem de Serviço";
?>

<html>

<head>
<title><? echo $title ?></title>
<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires"       content="0">
<meta http-equiv="Pragma"        content="no-cache, public">
<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
<link type="text/css" rel="stylesheet" href="css/css_press.css">

<style>
/*******************************
 ELEMENTOS DE COR FONTE EXTRATO 
*******************************/
.TdBold   {font-weight: bold;}
.TdNormal {font-weight: normal;}
.Tabela{
	font-family: Verdana,sans;
}
</style>

</head>

<body>
<center>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0" class='Tabela'>
<tr>
	<td align='center'>
	<b>Protocolo de Envio da Documentação para Pagamento em Garantia.</b>
	<br><br></td>
</tr>

</TABLE>
<?

/*
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>BLACK & DECKER DO BRASIL LTDA</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>End.</b> Rod. BR 050 S/N KM 167-LOTE 5 QVI &nbsp;&nbsp;-&nbsp;&nbsp; <b>Bairro:</b> DI II</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Cidade:</b> Uberaba &nbsp;&nbsp;-&nbsp;&nbsp; <b>Estado:</b> MG &nbsp;&nbsp;-&nbsp;&nbsp; <b>Cep:</b> 38064-750</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição CNPJ: 53.296.273/0001-91</font>\n";
		echo "</td>\n";
		
		echo "<td nowrap bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição Estadual: 701.948.711.00-98</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
*/
?>

</center>

<?
if (strlen($extrato) > 0) {
	$data_atual = date("d/m/Y");

	$sql = "SELECT  to_char(min(tbl_os.data_fechamento),'DD/MM/YYYY') AS inicio,
					to_char(max(tbl_os.data_fechamento),'DD/MM/YYYY') AS final
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			WHERE   tbl_os_extra.extrato = $extrato;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$inicio_extrato = trim(pg_result($res,0,'inicio'));
		$final_extrato  = trim(pg_result($res,0,'final'));
	}

	if (strlen($inicio_extrato) == 0 AND strlen($final_extrato) == 0) {
		$sql = "SELECT  to_char(min(tbl_extrato.data_geracao),'DD/MM/YYYY') AS inicio,
						to_char(max(tbl_extrato.data_geracao),'DD/MM/YYYY') AS final
				FROM    tbl_extrato
				WHERE   tbl_extrato.extrato = $extrato";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$inicio_extrato = trim(pg_result($res,0,'inicio'));
			$final_extrato  = trim(pg_result($res,0,'final'));
		}
	}

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto.endereco                                      ,
					tbl_posto.cidade                                        ,
					tbl_posto.estado                                        ,
					tbl_posto.cep                                           ,
					tbl_posto.fone                                          ,
					tbl_posto.fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto.email                                         ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta                                 ,
					tbl_extrato.protocolo                                   ,
					tbl_extrato.total 										,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data ,
					tbl_posto_fabrica.contato_cidade                        
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto.posto
			WHERE   tbl_extrato.extrato = $extrato;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$codigo        = trim(pg_result($res,0,codigo_posto));
		$posto         = trim(pg_result($res,0,posto));
		$nome          = trim(pg_result($res,0,nome));
		$endereco      = trim(pg_result($res,0,endereco));
		$cidade        = trim(pg_result($res,0,cidade));
		$estado        = trim(pg_result($res,0,estado));
		$cep           = substr(pg_result($res,0,cep),0,2) .".". substr(pg_result($res,0,cep),2,3) ."-". substr(pg_result($res,0,cep),5,3);
		$fone          = trim(pg_result($res,0,fone));
		$fax           = trim(pg_result($res,0,fax));
		$contato       = trim(pg_result($res,0,contato));
		$email         = trim(pg_result($res,0,email));
		$cnpj          = trim(pg_result($res,0,cnpj));
		$ie            = trim(pg_result($res,0,ie));
		$banco         = trim(pg_result($res,0,banco));
		$agencia       = trim(pg_result($res,0,agencia));
		$conta         = trim(pg_result($res,0,conta));
		$data_extrato  = trim(pg_result($res,0,data));
		$protocolo     = trim(pg_result($res,0,protocolo));
		$contato_cidade= trim(pg_result($res,0,contato_cidade));
		$total 		   = pg_result($res,0, 'total');

		# Taxa administrativa
		$sql = "SELECT sum(valor)
				FROM   tbl_extrato_lancamento
				WHERE  extrato    = $extrato
				AND    fabrica    = $login_fabrica
				AND    lancamento = 47";
		$res2 = pg_exec($con,$sql);
		$valor_10 = @pg_result($res2,0,0);
		#mao de obra
		$sql = "SELECT SUM (tbl_os.mao_de_obra) as total_mao_obra
				FROM  tbl_os 
				JOIN  tbl_os_extra using (os)
				WHERE tbl_os_extra.extrato = $extrato AND tbl_os_extra.extrato > 47265 ";
//if ($ip == "201.43.246.49") echo $sql;
		$resX = pg_exec ($con,$sql);
		$total_pecas = 0 ;
		if (pg_numrows ($resX) > 0) $total_mao_obra = pg_result ($resX,0,0);

		$total_mao_obra = $total_mao_obra + $valor_10;
		$total_mao_obra = $total_mao_obra+$total_retorno;
		$total_mao_obra += $totalTx;
		$sql = "  SELECT sum(valor_extrato_lancamento)
					FROM tbl_extrato_extra_item
					WHERE lancamento in (165,132,45)
					AND extrato=$extrato;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_mao_obra += pg_fetch_result($res,0,0);
		}

		$total_mao_obra = number_format($total_mao_obra,2,',','.');
		$sql = "SELECT tipo_envio_nf
				FROM tbl_tipo_gera_extrato
				WHERE fabrica = $login_fabrica
				AND posto = $posto";

		$res2 = pg_query($con, $sql);

		$tipo_envio = pg_result($res2,0,0);
		$via_correio = false;
		
		switch ($tipo_envio) {

			case 'online_possui_nfe':

				$text_protocolo = "Todos os documentos referentes às ordens de serviço desse extrato foram enviados online.

									Dessa forma, para que o extrato seja encaminhado para o financeiro é necessário enviar somente a NF de serviços eletrônica no valor de R$ $total_mao_obra (Taxa Administrativa + Sub Total Mão-de-obra).

									Para anexar a NF de serviços eletrônica clique no botão INSERIR NF e anexe o arquivo com a impressão da NF (documento auxiliar).

									Lembrando que, para as NF’s eletrônicas (DANFE) é necessário que encaminhe também o arquivo XML para o endereço nfefornec@sbdbrasil.com.br.

									Utilizar o CFOP 5933 para os postos no estado de MG e 6933 para os postos em outros estados. A natureza da operação é Prestação de serviços.";

				break;

			case 'online_nao_possui_nfe' :
				
				$text_protocolo = "Todos os documentos referentes às ordens de serviço desse extrato foram enviados online. 

									Dessa forma, para que o extrato seja encaminhado para o financeiro é necessário enviar somente a NF de serviços no valor de R$ $total_mao_obra (Taxa Administrativa + Sub Total Mão-de-obra).

									Utilizar o CFOP 5933 para os postos no estado de MG e 6933 para os postos em outros estados. A natureza da operação é Prestação de serviços.

									Gentileza encaminhar via correios. Estando tudo correto, quando recebermos esse documento o processo será encaminhada para o financeiro.";

				break;

			default: $via_correio = true; break;
		
		}

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>EXTRATO DE SERVIÇOS </b></font>\n";//$data_extrato
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' nowrap align='right' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>$protocolo</b></font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>POSTO</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' nowrap align='right' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>$codigo</b></font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";

		echo '<div style="width:650px; margin:auto;">' . nl2br($text_protocolo) . '</div>';
	
	}


	
	$xtotal = 0;
	
	$sql = "SELECT tbl_os.os
			FROM    tbl_os_extra
			JOIN    tbl_os USING (os)
			JOIN 	tbl_produto using(produto)
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica;";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0 && $via_correio === true) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";

		$sql =	"SELECT tbl_os.os                                                     ,
				tbl_produto.familia                                           ,
				tbl_produto.referencia as produto_referencia                  ,
				tbl_os.sua_os                                                 ,
				(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS pecas,
				tbl_os.mao_de_obra                                            ,
				tbl_os.nota_fiscal                                            ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf       ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,(tbl_os_extra.qtde_km * tbl_os_extra.valor_por_km) as qtde_km  ,
				(tbl_os_extra.qtde_horas * tbl_os_extra.valor_total_hora_tecnica) as valor_total_hora_tecnica  ,
				tbl_os_extra.mao_de_obra_adicional
		FROM    tbl_os_extra
		JOIN    tbl_os USING (os)
		JOIN 	tbl_produto using(produto)
		WHERE   tbl_os_extra.extrato = $extrato
		AND     tbl_os.fabrica = $login_fabrica
		AND     (tbl_os.nota_fiscal IS NOT NULL or length(tbl_os.nota_fiscal) > 0)
		AND     (tbl_os.tipo_os <> 13 or tbl_os.tipo_os is null)
		ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-'))::text,20,'0') ASC,
				replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-'))::text,20,'0'),'-','') ASC;";
		$res = pg_exec ($con,$sql);

		$total_notas = pg_numrows($res);

		/*ver se existe OS Metais Sanitarios*/
		$sql =	"SELECT tbl_os.os
		FROM    tbl_os_extra
		JOIN    tbl_os USING (os)
		JOIN 	tbl_produto using(produto)
		WHERE   tbl_os_extra.extrato = $extrato
		AND     tbl_os.fabrica = $login_fabrica
		AND     (tbl_os.nota_fiscal IS NOT NULL or length(tbl_os.nota_fiscal) > 0)
		AND     tbl_os.tipo_os = 13 
		ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-'))::text,20,'0') ASC,
				replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-'))::text,20,'0'),'-','') ASC;";
		$res_os_metal = pg_exec ($con,$sql);

		$total_os_metal = pg_numrows($res_os_metal);


		if ($total_notas > 0) {
			echo "<tr>\n";
			echo "<td bgcolor='#FFFFFF'   style='text-align:justify' >\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b><u>Cópias de Notas Fiscais:</u></b><br><br>- Números das Notas Fiscais: \n";

			// monta array da tela
			for ($x = 0; $x < $total_notas; $x++) {
				$sua_os    = trim(pg_result($res,$x,sua_os));
				$os        = trim(pg_result($res,$x,os));
				$pecas     = trim(pg_result($res,$x,pecas));
				$maodeobra = trim(pg_result($res,$x,mao_de_obra));
				$valor_total_hora_tecnica = trim(pg_result($res,$x,valor_total_hora_tecnica));
				$data_abertura   = trim(pg_result($res,$x,data_abertura));
				$data_fechamento = trim(pg_result($res,$x,data_fechamento));
				$nota_fiscal     = trim(pg_result($res,$x,nota_fiscal));
				$data_nf         = trim(pg_result($res,$x,data_nf));
				$qtde_km         = trim(pg_result($res,$x,qtde_km));
				$familia         = trim(pg_result($res,$x,familia));
				$mao_de_obra_adicional  = trim(pg_result($res,$x,mao_de_obra_adicional));
				$produto_referencia   = trim(pg_result($res,$x,produto_referencia));
				$produto_referencia   = substr($produto_referencia,0,8);

				$total_os = $pecas + $maodeobra;

				if($valor_total_hora_tecnica>0 and $mao_de_obra_adicional > 0){
					$mao_de_obra_adicional = number_format($mao_de_obra_adicional,2,",",".");
					$adicional =  "<br> -Comprovante de valor adicional no valor de R$ $mao_de_obra_adicional referente a O.S. $codigo$sua_os ";
				}

				$total_os = $pecas + $maodeobra;

				echo "$nota_fiscal; ";
			}

			echo "<br>Total: $total_notas cópia(s) de Nota(s) Fiscal(is)";
			echo "<br><font color=red>Organizar as Notas fiscais na Ordem do extrato, isso facilita a conferência agilizando o processo.</font>";
		}else{
			echo "<tr>\n";
			echo "<td bgcolor='#FFFFFF'   style='text-align:justify' >\n";
		}
		if($total_os_metal > 0 ){
			echo "<BR><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b><u>OS METAL SANITARIO ASSINADA:</u></b><br>- Para todas as OS's de produtos Metais Sanitios desse extrato é necessário enviar a via assinada pelo cliente com todos os dados do atendimento (cliente, produto e serviço).</font><br>\n";

		}


		/*KM Metal Sanitaio - IGOR HD: */
		$sql =	"
				SELECT  distinct tbl_os_revenda.os_revenda
				FROM tbl_os_revenda
				JOIN tbl_os_visita on tbl_os_revenda.os_revenda = tbl_os_visita.os_revenda
				WHERE tbl_os_revenda.fabrica = $login_fabrica
					AND tbl_os_revenda.extrato_revenda = $extrato
					AND tbl_os_visita.km_chegada_cliente >100;";

		$res = pg_exec ($con,$sql);
		$total_km = pg_numrows($res);
		if ($total_km> 0) {
			echo "<BR><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b><u>É necessário o envio do comprovante de autorização da fábrica para deslocamento das OS's Metais Sanitarios.:</u></b>\n";

			// monta array da tela
			for ($x = 0; $x < $total_km; $x++) {
				$os_revenda= trim(pg_result($res,$x,os_revenda));
				echo "<br> OS Metais Sanitarios: $os_revenda ";
			}

		}

		### OS SATISFAÇÃO DEWALT
		//if($total_notas == 0 ) echo "<td bgcolor='#FFFFFF'   style='text-align:justify' >\n";
		echo "<br><br><b><u>2º Via de Laudo Técnico:</b></u>";
		$sql =	"SELECT tbl_os_extra.os    ,
				tbl_os.os          ,
				(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS pecas  ,
				tbl_os.mao_de_obra ,
				tbl_os.sua_os      ,
				tbl_os.laudo_tecnico,
				tbl_os.nota_fiscal,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf,
				tbl_produto.linha                                                    ,
				tbl_produto.familia                                                  ,
				tbl_produto.referencia             as produto_referencia             ,
				to_char(tbl_os.data_abertura,'DD/MM/YY')   AS data_abertura        ,
				to_char(tbl_os.data_fechamento,'DD/MM/YY') AS data_fechamento      ,
				(tbl_os_extra.qtde_km * tbl_os_extra.valor_por_km) as qtde_km        ,
				(tbl_os_extra.qtde_horas * tbl_os_extra.valor_total_hora_tecnica) as valor_total_hora_tecnica  ,
				tbl_os_extra.mao_de_obra_adicional
			FROM    tbl_os_extra
			JOIN    tbl_os USING (os)
			join    tbl_produto on tbl_os.produto = tbl_produto.produto
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_os.satisfacao IS TRUE
			ORDER BY substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) ASC,
					lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')+1,length(tbl_os.sua_os))::text,5,'0') ASC,
					tbl_os.sua_os;";
		$res = pg_exec ($con,$sql);

		$total_laudo = pg_numrows($res);
		if ($total_laudo > 0) {
			$laudo_tecnico = "";
			for ($x = 0; $x < $total_laudo; $x++) {
				$laudo_tecnico .= trim(pg_result($res,$x,laudo_tecnico))."; ";
			}
			echo "<br>- Número do Laudo Técnico: $laudo_tecnico <font color=red>(Grampear este documento com sua respectiva cópia de NF)</font>";
			echo "<br>Total: $total_laudo Laudo(s) Técnico.";
		}else{
			echo "<br>- Não é necessário o envio de laudo técnico para este extrato.";
		}


		/*ADICIONAL Metais Sanitarios - IGOR HD: */
		$sql =	"
		SELECT 
				tbl_extrato_lancamento.valor,
				tbl_extrato_lancamento.os_revenda
		FROM    tbl_extrato_lancamento
		WHERE   tbl_extrato_lancamento.extrato = $extrato
		AND     tbl_extrato_lancamento.fabrica = $login_fabrica
		AND     tbl_extrato_lancamento.lancamento in(113);";
		$res = pg_exec ($con,$sql);
		//echo "sql: $sql";
		$total_adicional_os_metal = pg_numrows($res);
		if ($total_adicional_os_metal> 0) {
			// monta array da tela
			for ($x = 0; $x < $total_adicional_os_metal; $x++) {
				$valor    = trim(pg_result($res,$x,valor));
				$valor    = number_format($valor,2,",",".");
				$os_revenda= trim(pg_result($res,$x,os_revenda));
				$adicional_metal .=  "<br> -Comprovante de valor adicional no valor de R$ $valor referente a O.S. Metal Sanitario: $os_revenda";
			}
		}

		echo "<br><br><b><u>Comprovante de Valor Adicional:</u></b>";
		
		if(strlen($adicional) > 0 or strlen($adicional_metal)>0){
			echo $adicional;
			echo $adicional_metal;
			if(strlen($adicional) > 0 ){
				echo "<br><font color=red>(Grampear este documento com sua respectiva cópia de NF)</font>";
			}
		}else{
			echo "<br>- Não é necessário o envio de nenhum comprovante de valor adicional para este extrato.";
		}

		echo "<br><br><b><u>Comprovante Crédito Sedex.</u></b>";
		$sql = "SELECT * 
				FROM (
					(
						SELECT	lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex , 
								tbl_os_sedex.total_pecas , 
								tbl_os_sedex.despesas , 
								tbl_os_sedex.total 
						FROM	tbl_os_sedex 
						WHERE	tbl_os_sedex.extrato_origem = $extrato 
						AND		tbl_os_sedex.fabrica = $login_fabrica 
						AND		tbl_os_sedex.finalizada is not null
						ORDER BY tbl_os_sedex.os_sedex
					) union (
						SELECT	lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex , 
								tbl_os_sedex.total_pecas , 
								tbl_os_sedex.despesas , 
								tbl_os_sedex.total 
						FROM	tbl_os_sedex 
						WHERE	tbl_os_sedex.extrato_destino = $extrato 
						AND		tbl_os_sedex.posto_origem = 6901 
						AND		tbl_os_sedex.fabrica = $login_fabrica 
						ORDER BY tbl_os_sedex.os_sedex
					) 
				) AS x;";
		$res = pg_exec ($con,$sql);
		$total_sedex = pg_numrows($res);
		if ($total_sedex > 0) {
			for ($x = 0; $x < $total_sedex; $x++) {
				$sua_os      = trim(pg_result($res,$x,os_sedex));
				$total_pecas = trim(pg_result($res,$x,total_pecas));
				$despesas    = trim(pg_result($res,$x,despesas));
				$total       = trim(pg_result($res,$x,total));
				$xtotal      = number_format($xtotal + $total_pecas + $despesas,2,',','.');
				echo "<br>- Comprovante de Crédito Sedex no valor de R$ $xtotal referente a O.S. $codigo$sua_os";
				echo "<br>(Grampear este documento com sua respectiva cópia de NF)";
			}
		}else{
			echo "<br>- Não é necessário o envio de nenhum comprovante de SEDEX para este extrato.";
		}

		echo "<br><br><b><u>Nota fiscal de mão-de-obra</b></u>";
		echo "<br>- Valor NF mão-de-obra: R$ $total_mao_obra <font color=red>(Taxa Administrativa + Sub Total Mão-de-obra)</font>";
		
		if($contato_cidade == 'SÃO PAULO' or $contato_cidade =='SAO PAULO' or $contato_cidade=='São Paulo' ){
			echo "<br><br>Declaração de Optante pelo Simples";
		}
		/* HD 153553
		echo "<br><br><b><u>Check List&nbsp;(Somente para compressores Dewalt) :</b></u>";

		$sqldw="SELECT tbl_os.sua_os 
				FROM tbl_os_extra
				JOIN tbl_os USING (os)
				JOIN tbl_produto using(produto) 
				WHERE tbl_os_extra.extrato = $extrato
				AND tbl_os.fabrica = $login_fabrica AND ( tbl_os.satisfacao IS NULL OR tbl_os.satisfacao IS FALSE )
				AND tbl_os.posto=$posto
				AND tbl_produto.familia=347
				AND tbl_produto.linha=198 ;";
		$resdw=pg_exec($con,$sqldw);
		
		if(pg_numrows($resdw) > 0){
			echo "<br>É necessário o envio do Check List para a(s) seguinte(s) ordem(s) de serviço(s) de compressor: nº ";
			for ($d = 0; $d < pg_numrows($resdw); $d++) {
				$sua_os=pg_result($resdw,$d,sua_os);
				echo $codigo."$sua_os ;";
			}
		}else{
			echo "<br>Não é necessário o envio do Check List.";
		}
		*/

		echo "<br><br>Utilizar o CFOP 5933 para os postos no estado de MG e 6933 para os postos em outros estados. A natureza da operação é Prestação de serviços.
			  <br><br>É extremamente necessário o envio de toda a documentação relacionada no protocolo acima para o recebimento do extrato.";
		echo "<br><br>Não estando em conformidade, o posto será informado através de e-mail e do site no link pendências de extratos.";
		echo "<br><br>IMPORTANTE: Os extratos com documentação divergentes estarão bloqueados até regularização. No caso de recebimento de comunicado de pendência sempre responder no mesmo e-mail recebido, para que possa ser resolvido via e-mail evitando ligações desnecessárias.";

		echo "<br><br>Atenciosamente,";
		echo "<br>Assistência Técnica Black & Decker.";

		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>";
	}
}
	
?>

<br>

</body>

</html>
<SCRIPT LANGUAGE="JavaScript">
<!--
//window.print();
//-->
</SCRIPT>
