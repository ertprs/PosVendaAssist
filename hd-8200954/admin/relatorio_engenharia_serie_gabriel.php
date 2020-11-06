<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
$title = "Relatório de defeitos por Nº série";
include 'autentica_admin.php';
$msg= "";
$relatorio = $_POST['relatorio'];

$msg_erro = '';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

<?
$array_mes = array( 1 => 'A',
				2 => 'B',
				3 => 'C',
				4 => 'D',
				5 => 'E',
				6 => 'F',
				7 => 'G',
				8 => 'H',
				9 => 'I',
				10 => 'J',
				11 => 'K',
				12 => 'L',
			);

$array_ano = array( 1995 => 'A',
				1996 => 'B',
				1997 => 'C',
				1998 => 'D',
				1999 => 'E',
				2000 => 'F',
				2001 => 'G',
				2002 => 'H',
				2003 => 'I',
				2004 => 'J',
				2005 => 'K',
				2006 => 'L',
				2007 => 'M',
				2008 => 'N',
				2009 => 'O',
				2010 => 'P',
			);

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

include "cabecalho.php";



if (strlen($msg_ero) == 0) {
	$cont = 0;
	$sql = "
	SELECT  nome_linha,
	descricao_familia,
	os,
	sua_os,
	referencia,
	serie,
	dia_nota,
	mes_nota,
	ano_nota,
	dia_abertura,
	mes_abertura,
	ano_abertura,
	nome,
	cidade,
	estado,
	defeito_reclamado,
	defeito_constatado,
	solucao_os,
	servico_realizado,
	dia_digitacao,
	mes_digitacao,
	ano_digitacao,
	dia_fechamento,
	mes_fechamento,
	ano_fechamento,
	dia_finalizada  ,
	mes_finalizada  ,
	ano_finalizada  ,
	dif_dias_fechamento_abertura,
	dif_dias_finalizada_digitacao,
	dif_dias_compra_abertura,
	dif_meses_compra_abertura,
	dif_dias_fabricacao_compra,
	dif_meses_finalizada_digitacao,
	dif_meses_fechamento_abertura,
	mao_de_obra,
	consumidor_revenda,
	revenda_nome,
	peça_referencia,
	descricao_peca,
	defeito_peca

	FROM
	tbl_engenharia_serie

	WHERE
	fabrica = $login_fabrica

	ORDER BY
	ano_abertura,
	mes_abertura,
	os; 
	";

/*

SELECT  tbl_os.os                ,
	tbl_os.sua_os                ,
	tbl_produto.referencia    ,
	tbl_os.serie                 ,
	to_char(tbl_os.data_nf,'MM') as mes_nota              ,
	to_char(tbl_os.data_nf,'YYYY') as ano_nota              ,
	to_char(tbl_os.data_abertura,'MM') as mes_abertura          ,
	to_char(tbl_os.data_abertura,'YYYY') as ano_abertura          ,
	tbl_posto.nome                  ,
	tbl_posto.cidade                ,
	tbl_posto.estado                ,
	tbl_defeito_reclamado.descricao as defeito_reclamado       ,
	tbl_defeito_constatado.descricao as descricao_constatado    ,
	tbl_solucao.descricao as descricao_solucao_os            ,
	tbl_peca.descricao       ,
	tbl_servico_realizado.descricao as descricao_servico_realizado
from tbl_extrato
JOIN tbl_os_extra           ON tbl_os_extra.extrato = tbl_extrato.extrato
JOIN tbl_os                 ON tbl_os_extra.os = tbl_os.os
JOIN tbl_produto            ON tbl_os.produto = tbl_produto.produto
JOIN tbl_posto              ON tbl_os.posto   = tbl_posto.posto
JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
JOIN tbl_solucao            ON tbl_os.solucao_os = tbl_solucao.solucao
LEFT JOIN tbl_os_produto    ON tbl_os.os = tbl_os_produto.os
LEFT JOIN tbl_os_item       ON tbl_os_produto.os_produto = tbl_os_item.os_produto
LEFT JOIN tbl_peca          ON tbl_peca.peca = tbl_os_item.peca
JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
WHERE tbl_os.excluida is not true
AND   tbl_os.fabrica = 15
AND   tbl_os.data_digitacao between '2007-08-01 00:00:00' and '2007-08-02 23:59:59';


Select do field call rate - produtos-->

SELECT  data_abertura
FROM tbl_os
JOIN tbl_os_extra on tbl_os_extra.os = tbl_os.os
JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
WHERE tbl_os.excluida is not true
AND   tbl_os.fabrica = 15
AND   tbl_os.produto = 12017
AND   tbl_posto.estado = 'SP'
AND   tbl_extrato.data_geracao between '2007-08-01 00:00:00' and '2007-08-31 23:59:59';

*/

	$res = pg_exec($con,$sql);
	$count = 0;
	if(pg_numrows($res) > 0){

		$arquivo_nome     = "relatorio-engenharia-os.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");
		set_time_limit(900);
		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATORIO ENGENHARIA OS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<TR>");

		fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Linha</b></TD>");
		fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Família</b></TD>");
		fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Código do Produto</b></TD>");
		fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Nº da OS</b></TD>");
		fputs ($fp,"<TD bgcolor=#FF0000><b>Nº de Série</b></TD>");
		fputs ($fp,"<TD bgcolor=#FF0000><b>Fábrica</b></TD>");
		fputs ($fp,"<TD bgcolor=#FF0000><b>Versão</b></TD>");
		fputs ($fp,"<TD bgcolor=#FF0000 align=center><b>Mês fabricação</b></TD>");
		fputs ($fp,"<TD bgcolor=#FF0000 align=center><b>Ano fabricação</b></TD>");
		fputs ($fp,"<TD bgcolor=#FF0000 align=center><b>Número sequêncial</b></TD>");
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Dia NF compra</b></TD>");
		}
		fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Mês NF compra</b></TD>");
		fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Ano NF compra</b></TD>");
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Diferença entre fabricação e compra (Dias)</b></TD>");
		}
		fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Diferença entre fabricação e compra (Meses)</b></TD>");
		
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Dia abertura OS</b></TD>");
		}
		fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Mês abertura OS</b></TD>");
		fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Ano abertura OS</b></TD>");
		
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Diferença entre compra e OS (Dias)</b></TD>");
		}
		
		fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Diferença entre compra e OS (Meses)</b></TD>");
		
	
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Dia Digitação</b></TD>");
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Mes Digitação</b></TD>");
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Ano Digitação</b></TD>");
		
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Dia Fechamento</b></TD>");
		}
		
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Mes Fechamento</b></TD>");
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Ano Fechamento</b></TD>");
		
		
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Diferenca Entre Abertura e Fechamento (Dias)</b></TD>");
		}
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Diferenca Entre Abertura e Fechamento (Meses)</b></TD>");
		
		if ($login_fabrica == 15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Dia Finalização</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Mês Finalização</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Ano Finalização</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Diferença Entre Digitação e Finalização (Dias)</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Diferença Entre Digitação e Finalização (Meses)</b></TD>");
		}
		
		
		if ($login_fabrica==15){ #HD 368781
			fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Valor pago na OS</b></TD>");
		}
		fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Consumidor/Revenda</b></TD>");
		fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Revenda Nome</b></TD>");
		fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Posto Autorizado</b></TD>");
		fputs ($fp,"<TD bgcolor=#FFC68C><b>Cidade</b></TD>");
		fputs ($fp,"<TD bgcolor=#FFC68C><b>Estado</b></TD>");
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Defeito Reclamado</b></TD>");
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Defeito Constatado</b></TD>");
		fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Solução</b></TD>");
		if($login_fabrica == 15){
			$sqlCount = "select count(peça_referencia) from tbl_engenharia_serie where fabrica=$login_fabrica group by os order by count(peça_referencia) desc limit 1";
			$resCount = pg_query($con,$sqlCount);
			$total = pg_result($resCount,0,0);
			for($i = 1; $i <= $total; $i++){
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada $i</b></TD>");
			}

			for($i = 1; $i <= $total; $i++){
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça $i</b></TD>");
			}
		}
		else{
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 1</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 2</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 3</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 4</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 5</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 6</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 7</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 8</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 9</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 10</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 11</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 12</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 13</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 14</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 15</b></TD>");

			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 1</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 2</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 3</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 4</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 5</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 6</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 7</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 8</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 9</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 10</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 11</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 12</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 13</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 14</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 15</b></TD>");
			
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 1</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 2</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 3</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 4</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 5</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 6</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 7</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 8</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 9</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 10</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 11</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 12</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 13</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 14</b></TD>");
			fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 15</b></TD>");
		}
		fputs ($fp,"</TR>");

		$os_ant = "";

		for($i=0;$i<pg_numrows($res);$i++){
			$serie              = pg_result($res,$i,serie);
			$serie = trim(str_replace( ' ', '', $serie));

			$controle = 1;

			if(array_search(substr($serie, 2, 1), $array_mes) == 0){
				$controle = 2;
			}

			if(array_search(substr($serie, 3, 1), $array_ano) == 0){
				$controle = 2;
			}

			$teste = substr($serie, 0, 1);

			if($teste <> 1 AND $teste <> 4 AND $teste <> 9) {
				$controle = 2;
			}

			if($controle == 1){
				if(preg_match('/^[A-Za-z]+$/', substr($serie, 1, 3))){
					$controle = '1';
				}else{
					$controle = '2';
				}
			}

			if(strlen($serie) > 7 AND $controle == 1){
				$nome_linha         = pg_result($res,$i,nome_linha);
				$descricao_familia  = pg_result($res,$i,descricao_familia);
				$os                 = pg_result($res,$i,os);
				$sua_os             = pg_result($res,$i,sua_os);
				$produto_referencia = pg_result($res,$i,referencia);
				$mes_nota           = pg_result($res,$i,mes_nota);
				$ano_nota           = pg_result($res,$i,ano_nota);
				$mes_abertura       = pg_result($res,$i,mes_abertura);
				$ano_abertura       = pg_result($res,$i,ano_abertura);
				$posto_nome         = pg_result($res,$i,nome);
				$posto_cidade       = pg_result($res,$i,cidade);
				$posto_estado       = pg_result($res,$i,estado);
				$defeito_reclamado  = pg_result($res,$i,defeito_reclamado);
				$defeito_constatado = pg_result($res,$i,defeito_constatado);
				$solucao_os         = pg_result($res,$i,solucao_os);
				$servico_realizado  = pg_result($res,$i,servico_realizado);
				$dia_digitacao      = pg_result($res,$i,dia_digitacao);
				$mes_digitacao      = pg_result($res,$i,mes_digitacao);
				$ano_digitacao      = pg_result($res,$i,ano_digitacao);
				$mes_fechamento     = pg_result($res,$i,mes_fechamento);
				$ano_fechamento     = pg_result($res,$i,ano_fechamento);
				$consumidor_revenda = pg_result($res,$i,consumidor_revenda);
				$revenda_nome       = pg_result($res,$i,revenda_nome);
				$peca_referencia    = pg_result($res,$i,peça_referencia);
				$peca_descricao     = pg_result($res,$i,descricao_peca);
				$defeito_peca       = pg_result($res,$i,defeito_peca);
				
				if ($login_fabrica ==  15){
					$dia_nota                      = pg_fetch_result($res, $i, 'dia_nota');
					$dia_abertura                  = pg_fetch_result($res, $i, 'dia_abertura');
					$dia_fechamento                = pg_fetch_result($res, $i, 'dia_fechamento');
					$dia_finalizada 			   = pg_fetch_result($res, $i, 'dia_finalizada');
					$mes_finalizada 			   = pg_fetch_result($res, $i, 'mes_finalizada');
					$ano_finalizada 			   = pg_fetch_result($res, $i, 'ano_finalizada');
					$mao_de_obra                   = pg_fetch_result($res, $i, 'mao_de_obra');
					$dif_dias_fechamento_abertura  = pg_fetch_result($res, $i, 'dif_dias_fechamento_abertura');
					$dif_meses_fechamento_abertura = pg_fetch_result($res, $i, 'dif_meses_fechamento_abertura');
					$dif_dias_finalizada_digitacao  = pg_fetch_result($res, $i, 'dif_dias_finalizada_digitacao');
					$dif_meses_finalizada_digitacao = pg_fetch_result($res, $i, 'dif_meses_finalizada_digitacao');
					$dif_dias_compra_abertura      = pg_fetch_result($res, $i, 'dif_dias_compra_abertura');
					$dif_meses_compra_abertura     = pg_fetch_result($res, $i, 'dif_meses_compra_abertura');
					$dif_dias_fabricacao_compra    = pg_fetch_result($res, $i, 'dif_dias_fabricacao_compra');
								
				}

				if ($os_ant != $os){
					if ($i != 0){
						fputs ($fp,"</TR>");
					}

					fputs ($fp,"<tr>");
					fputs ($fp,"<TD nowrap>$nome_linha</TD>");
					fputs ($fp,"<TD nowrap>$descricao_familia</TD>");
					fputs ($fp,"<TD nowrap>$produto_referencia</TD>");
					fputs ($fp,"<TD nowrap>$sua_os</TD>");
					fputs ($fp,"<TD nowrap>$serie</TD>");
					fputs ($fp,"<TD nowrap align=center>".substr($serie, 0, 1)."</TD>");
					fputs ($fp,"<TD nowrap align=center>".substr($serie, 1, 1)."</TD>");

					$fabricao_mes = array_search(substr($serie, 2, 1), $array_mes); // $key = 2;
					if($fabricao_mes < 10) $fabricao_mes = "0".$fabricao_mes;

					fputs ($fp,"<TD align=center>$fabricao_mes</TD>");

					$letra = substr($serie, 3, 1);
						$ano = ord($letra) + 1930;
						
						$fabricao_ano = $ano; 

					fputs ($fp,"<TD nowrap align=center>$fabricao_ano</TD>");
					fputs ($fp,"<TD nowrap align=right>".substr($serie, 4, strlen($serie))."</TD>");
					if ($login_fabrica == 15){
						fputs ($fp,"<TD nowrap align=center>$dia_nota</TD>");
					}
					fputs ($fp,"<TD nowrap align=center>$mes_nota</TD>");
					fputs ($fp,"<TD nowrap align=center>$ano_nota</TD>");
					
					if ($login_fabrica == 15){#HD 368781
						fputs ($fp,"<TD nowrap align=center>$dif_dias_fabricacao_compra</TD>");
					}
					
					$data_nota = $ano_nota."-".$mes_nota."-01";
					$data_fabricacao=$fabricao_ano."-".$fabricao_mes."-01";

					$sql2="SELECT ('$data_nota'::date)-('$data_fabricacao'::date) as dias1";
					$res2 = @pg_exec($con,$sql2);
					$dias1 = @pg_result($res2,0,dias1);
//					if(pg_errormessage ($con) )echo pg_errormessage ($con) . " - $i - $data_nota - $data_fabricacao - $serie - $os";
					$mes_dif=$dias1/30;
					$mes_dif= number_format(str_replace( ',', '', $mes_dif), 0, ',','');

					fputs ($fp,"<TD nowrap align=center>$mes_dif</TD>");
					if ($login_fabrica == 15){#HD 368781
						fputs ($fp,"<TD nowrap align=center>$dia_abertura</TD>");
					}
					fputs ($fp,"<TD nowrap align=center>$mes_abertura</TD>");
					fputs ($fp,"<TD nowrap align=center>$ano_abertura</TD>");

					if ($login_fabrica == 15){#HD 368781
						fputs ($fp,"<TD nowrap align=center>$dif_dias_compra_abertura</TD>");
					}
					fputs ($fp,"<TD nowrap align=center>$dif_meses_compra_abertura</TD>");
					
					if(strlen($dia_digitacao)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
					} else {
						fputs ($fp,"<TD nowrap>$dia_digitacao</TD>");
					}
					if(strlen($mes_digitacao)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
					} else {
						fputs ($fp,"<TD nowrap>$mes_digitacao</TD>");
					}
					if(strlen($ano_digitacao)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
					} else {
						fputs ($fp,"<TD nowrap>$ano_digitacao</TD>");
					}
					
					if ($login_fabrica == 15){#HD 368781
						if(strlen($dia_fechamento)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
						fputs ($fp,"<TD nowrap>$dia_fechamento</TD>");
						}
					}
					
					if(strlen($mes_fechamento)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
					} else {
						fputs ($fp,"<TD nowrap>$mes_fechamento</TD>");
					}
					
					if(strlen($ano_fechamento)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
					} else {
						fputs ($fp,"<TD nowrap>$ano_fechamento</TD>");
					}
					
					if ($login_fabrica == 15){ #HD 368781
						
						if(strlen($dif_dias_fechamento_abertura)==0) {
							fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$dif_dias_fechamento_abertura</TD>");
						}
						
						if(strlen($dif_meses_fechamento_abertura)==0) {
							fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$dif_meses_fechamento_abertura</TD>");
						}
						
						if(strlen($dia_finalizada)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$dia_finalizada</TD>");
						}
						
						if(strlen($mes_finalizada)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$mes_finalizada</TD>");
						}
						
						if(strlen($ano_finalizada)==0) {
						fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$ano_finalizada</TD>");
						}
						
					}
					
					
					if ($login_fabrica == 15){ #HD 368781
						if(strlen($dif_dias_finalizada_digitacao)==0) {
							fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$dif_dias_finalizada_digitacao</TD>");
						}
						
						if(strlen($dif_meses_finalizada_digitacao)==0) {
							fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$dif_meses_finalizada_digitacao</TD>");
						}
						
						if(strlen($mao_de_obra)==0) {
							fputs ($fp,"<TD>&nbsp;</TD>");
						} else {
							fputs ($fp,"<TD nowrap>$mao_de_obra</TD>");
						}
						
					}
					
					fputs ($fp,"<TD nowrap>$consumidor_revenda</TD>");
					fputs ($fp,"<TD nowrap>$revenda_nome</TD>");
					fputs ($fp,"<TD nowrap>$posto_nome</TD>");
					fputs ($fp,"<TD nowrap>$posto_cidade</TD>");
					fputs ($fp,"<TD nowrap>$posto_estado</TD>");
					fputs ($fp,"<TD nowrap>$defeito_reclamado</TD>");
					fputs ($fp,"<TD nowrap>$defeito_constatado</TD>");
					fputs ($fp,"<TD nowrap>$solucao_os</TD>");

					#HD 230018
					if(strlen($os)>0){
						$sql_peca = "SELECT tbl_engenharia_serie.peça_referencia ,
									tbl_engenharia_serie.descricao_peca         ,
									tbl_engenharia_serie.defeito_peca
									FROM tbl_engenharia_serie
									JOIN tbl_peca ON tbl_engenharia_serie.peça_referencia =
									tbl_peca.referencia AND tbl_engenharia_serie.fabrica = tbl_peca.fabrica
									LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
									LEFT JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.ativa IS TRUE
									AND  tbl_tabela.fabrica = tbl_engenharia_serie.fabrica
									WHERE tbl_engenharia_serie.fabrica = $login_fabrica
									AND   tbl_engenharia_serie.os      = $os
									ORDER BY tbl_tabela_item.preco DESC
									";
									#echo nl2br($sql_peca); exit;
						$res_peca = pg_exec($con,$sql_peca);

						$vet = array();
						$y   = 0;
						if($login_fabrica == 15){
							while ($row = pg_fetch_assoc($res_peca)) {
								$vet['peca_referencia'][$y] = trim($row['peça_referencia']);
								$vet['descricao_peca'][$y]  = trim($row['descricao_peca']);
								$y++;

							}							
							if($y < $total){
								for($j = $y; $j < $total; $j++){
									$vet['peca_referencia'][$j] = "";
									$vet['descricao_peca'][$j]  = "";
								}
							}	
							
							
						}
						else{
							while ($row = pg_fetch_assoc($res_peca)) {
								$vet['peca_referencia'][$y] = trim($row['peça_referencia']);
								$vet['descricao_peca'][$y]  = trim($row['descricao_peca']);
								$vet['defeito_peca'][$y]    = trim($row['defeito_peca']);
								$y++;
							}
						}
						
						foreach($vet['peca_referencia'] AS $xpeca_referencia){
							if(strlen($xpeca_referencia)>0){
								fputs ($fp,"<TD nowrap>$xpeca_referencia</TD>");
							}
							else{
								fputs ($fp,"<TD nowrap>&nbsp;</TD>");
							}
						}
						
						foreach($vet['descricao_peca'] AS $xdescricao_peca){
							if(strlen($xdescricao_peca)>0){
								fputs ($fp,"<TD nowrap>$xdescricao_peca</TD>");
							}
							else{
								fputs ($fp,"<TD nowrap>&nbsp;</TD>");
							}
						}
						
						if($login_fabrica <> 15){
							foreach($vet['defeito_peca'] AS $xdefeito_peca){
								if(strlen($xpeca_referencia)>0){
									fputs ($fp,"<TD nowrap>$xdefeito_peca</TD>");
								}
							}
						}
					}
				}
				
				$os_ant = $os;
			}else{
				$nome_linha         = pg_result($res,$i,nome_linha);
				$descricao_familia  = pg_result($res,$i,descricao_familia);
				$sua_os             = pg_result($res,$i,sua_os);
				$os                 = pg_result($res,$i,os);
				$produto_referencia = pg_result($res,$i,referencia);
				$posto_nome         = pg_result($res,$i,nome);
				$posto_cidade       = pg_result($res,$i,cidade);
				$posto_estado       = pg_result($res,$i,estado);
				$defeito_reclamado  = pg_result($res,$i,defeito_reclamado);
				$defeito_constatado = pg_result($res,$i,defeito_constatado);
				$solucao_os         = pg_result($res,$i,solucao_os);
				$servico_realizado  = pg_result($res,$i,servico_realizado);
				$dia_digitacao      = pg_result($res,$i,dia_digitacao);
				$mes_digitacao      = pg_result($res,$i,mes_digitacao);
				$ano_digitacao      = pg_result($res,$i,ano_digitacao);
				$mes_fechamento     = pg_result($res,$i,mes_fechamento);
				$ano_fechamento     = pg_result($res,$i,ano_fechamento);
				$consumidor_revenda = pg_result($res,$i,consumidor_revenda);
				$revenda_nome       = pg_result($res,$i,revenda_nome);
				$mes_nota           = pg_result($res,$i,mes_nota);
				$ano_nota           = pg_result($res,$i,ano_nota);
				$mes_abertura       = pg_result($res,$i,mes_abertura);
				$ano_abertura       = pg_result($res,$i,ano_abertura);
				$peca_referencia    = pg_result($res,$i,peça_referencia);
				$peca_descricao     = pg_result($res,$i,descricao_peca);
				$defeito_peca       = pg_result($res,$i,defeito_peca);
				$fabrica            = substr($serie, 0, 1);
				$versao             = substr($serie, 1, 1);
				$fabricao_mes       = array_search(substr($serie, 2, 1), $array_mes); // $key = 2;
				if($fabricao_mes < 10) $fabricao_mes = "0".$fabricao_mes;
				$fabricao_ano        = array_search(substr($serie, 3, 1), $array_ano); // $key = 2;

				$sequencial         = substr($serie, 4, strlen($serie));
				$data_nota = $ano_nota."-".$mes_nota."-01";
				$data_fabricacao=$fabricao_ano."-".$fabricao_mes."-01";

				$sql2="SELECT ('$data_nota'::date)-('$data_fabricacao'::date) as dias1";
				$res2 = @pg_exec($con,$sql2);
				$dias1 = @pg_result($res2,0,dias1);

				$mes_dif=$dias1/30;
				$mes_dif= number_format(str_replace( ',', '', $mes_dif), 0, ',','');

				$data_abertura = $ano_abertura."-".$mes_abertura."-01";
				$sql3="SELECT ('$data_abertura'::date)-('$data_nota'::date) as dias2;";
				$res3 = @pg_exec($con,$sql3);
				$dias2 = @pg_result($res3,0,dias2);
				$mes_dif2 = $dias2/30;
				$mes_dif2 = (number_format(str_replace( ',', '', $mes_dif2), 0, ',','') + 1);

				
				$data_abertura_digitacao = $ano_digitacao."-".$mes_digitacao."-01";
				$data_fechamento_digitacao = $ano_fechamento."-".$mes_fechamento."-01";
				$sql4="SELECT ('$data_fechamento_digitacao'::date)-('$data_abertura_digitacao'::date) as dias4";
				$res4 = @pg_exec($con,$sql4);
				$dias4 = @pg_result($res4,0,dias4);
				$mes_dif4=$dias4/30;
				$mes_dif4= number_format(str_replace( ',', '', $mes_dif4), 0, ',','');

				$array_linha[$count]                 = $nome_linha;
				$array_familia[$count]               = $descricao_familia;
				$array_os[$count]                    = $os;
				$array_sua_os[$count]                = $sua_os;
				$array_serie[$count]                 = $serie;
				$array_produto_referencia[$count]    = $produto_referencia;
				$array_posto_nome[$count]            = $posto_nome;
				$array_cidade[$count]                = $posto_cidade;
				$array_estado[$count]                = $posto_estado;
				$array_defeito_reclamado[$count]     = $defeito_reclamado;
				$array_defeito_constatado[$count]    = $defeito_constatado;
				$array_solucao_os[$count]            = $solucao_os;
				$array_servico_realizado[$count]     = $servico_realizado;
				$array_dia_digitacao[$count]         = $dia_digitacao;
				$array_mes_digitacao[$count]         = $mes_digitacao;
				$array_ano_digitacao[$count]         = $ano_digitacao;
				$array_mes_fechamento[$count]        = $mes_fechamento;
				$array_ano_fechamento[$count]        = $ano_fechamento;
				$array_consumidor_revenda[$count]    = $consumidor_revenda;
				$array_revenda_nome[$count]          = $revenda_nome;
				$array_fabricao_mes[$count]          = $fabricao_mes;
				$array_fabricao_ano[$count]          = $fabricao_ano;
				$array_fabrica[$count]               = $fabrica;
				$array_versao[$count]                = $versao;
				$array_mes_nota[$count]              = $mes_nota;
				$array_ano_nota[$count]              = $ano_nota;
				$array_mes_abertura[$count]          = $mes_abertura;
				$array_ano_abertura[$count]          = $ano_abertura;
				$array_sequencial[$count]            = $sequencial;
				$array_mes_dif[$count]               = $mes_dif;
				$array_mes_dif2[$count]              = $mes_dif2;
				$array_mes_dif4[$count]              = $mes_dif4;
				$array_peca_referencia[$count]       = $peca_referencia;
				$array_peca_descricao[$count]        = $peca_descricao;
				$array_defeito_peca[$count]          = $defeito_peca;
				$count++;
			}
			
		}
		
		fputs ($fp,"</TR>");
		echo "</TABLE>";
		echo "<br><br>";
		
		$os_ant = "";
		if(count($array_os) > 0){
			fputs ($fp,"<p style='font-size: 12px; font-family: verdana;'><b>Nº de série fora do padrão</b></p>");
			fputs ($fp,"<TABLE style='font-size: 10px' cellspacing='0' cellpadding='0' border='1'>");
			fputs ($fp,"<tr>");
				fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Linha</b></TD>");
				fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Família</b></TD>");
				fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Código do Produto</b></TD>");
				fputs ($fp,"<TD bgcolor=#C0C0C0 align=center><b>Nº da OS</b></TD>");
				fputs ($fp,"<TD bgcolor=#FF0000><b>Nº de Série</b></TD>");
				fputs ($fp,"<TD bgcolor=#FF0000><b>Fábrica</b></TD>");
				fputs ($fp,"<TD bgcolor=#FF0000><b>Versão</b></TD>");
				fputs ($fp,"<TD bgcolor=#FF0000 align=center><b>Mês fabricação</b></TD>");
				fputs ($fp,"<TD bgcolor=#FF0000 align=center><b>Ano fabricação</b></TD>");
				fputs ($fp,"<TD bgcolor=#FF0000 align=center><b>Número sequêncial</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Mês NF compra</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Ano NF compra</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFFF00 align=center><b>Diferença entre fabricação e compra (meses)</b></TD>");
				fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Mês abertura OS</b></TD>");
				fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Ano abertura OS</b></TD>");
				fputs ($fp,"<TD bgcolor=#00FF40 align=center><b>Diferença entre compra e OS (meses)</b></TD>");
				if($login_fabrica <> 15){
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Dia Digitação</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Mês Digitação</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Ano Digitação</b></TD>");
				}
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Mes Fechamento</b></TD>");
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Ano Fechamento</b></TD>");
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Diferenca entre abertura e fechamento</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Consumidor/Revenda</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Revenda Nome</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Posto Autorizado</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Cidade</b></TD>");
				fputs ($fp,"<TD bgcolor=#FFC68C align=center><b>Estado</b></TD>");
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Defeito Reclamado</b></TD>");
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Defeito Constatado</b></TD>");
				fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Solução</b></TD>");
				if($login_fabrica == 15){
					$sqlCount = "select count(peça_referencia) from tbl_engenharia_serie where fabrica=$login_fabrica group by os order by count(peça_referencia) desc limit 1";
					$resCount = pg_query($con,$sqlCount);
					$total = pg_result($resCount,0);
					for($i = 1; $i <= $total; $i++){
						fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada $i</b></TD>");
					}

					for($i = 1; $i <= $total; $i++){
						fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça $i</b></TD>");
					}
				}
				else{
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 1</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 2</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 3</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 4</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 5</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 6</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 7</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 8</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 9</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 10</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 11</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 12</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 13</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 14</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Código da peça trocada 15</b></TD>");

					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 1</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 2</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 3</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 4</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 5</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 6</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 7</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 8</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da Peça 9</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 10</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 11</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 12</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 13</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 14</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Descrição da peça 15</b></TD>");

					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 1</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 2</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 3</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 4</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 5</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 6</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 7</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 8</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 9</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 10</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 11</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 12</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 13</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 14</b></TD>");
					fputs ($fp,"<TD bgcolor=#C4FFFF align=center><b>Comentário 15</b></TD>");
				}
				fputs ($fp,"</TR>");
			for($i=0; $i< count($array_os);$i++){

				if ($os_ant != $array_os[$i]){
					if ($i != 0){
						fputs ($fp,"</TR>");
					}
					fputs ($fp,"<tr>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_linha[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_familia[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_produto_referencia[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_sua_os[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_serie[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_fabrica[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_versao[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_fabricao_mes[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_fabricao_ano[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='right'>&nbsp;".$array_sequencial[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_mes_nota[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_ano_nota[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_mes_dif[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_mes_abertura[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_ano_abertura[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap align='center'>&nbsp;".$array_mes_dif2[$i]."&nbsp;</td>");
					if($login_fabrica <> 15){
						fputs ($fp,"<td nowrap>&nbsp;".$array_dia_digitacao[$i]."&nbsp;</td>");
						fputs ($fp,"<td nowrap>&nbsp;".$array_mes_digitacao[$i]."&nbsp;</td>");
						fputs ($fp,"<td nowrap>&nbsp;".$array_ano_digitacao[$i]."&nbsp;</td>");
					}
					fputs ($fp,"<td nowrap>&nbsp;".$array_mes_fechamento[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_ano_fechamento[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_mes_dif4[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_consumidor_revenda[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_revenda_nome[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_posto_nome[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_cidade[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_estado[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_defeito_reclamado[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_defeito_constatado[$i]."&nbsp;</td>");
					fputs ($fp,"<td nowrap>&nbsp;".$array_solucao_os[$i]."&nbsp;</td>");

					#HD 230018
					$xos = $array_os[$i];
					if(strlen($xos)>0){
						$sql_peca = "SELECT tbl_engenharia_serie.peça_referencia ,
									tbl_engenharia_serie.descricao_peca         ,
									tbl_engenharia_serie.defeito_peca
									FROM tbl_engenharia_serie
									JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_engenharia_serie.peca
									JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.ativa IS TRUE
									WHERE tbl_engenharia_serie.fabrica = $login_fabrica
									AND   tbl_engenharia_serie.os = $xos
									ORDER BY tbl_tabela_item.preco DESC";
									#echo nl2br($sql_peca);
						$res_peca = pg_exec($con,$sql_peca);

						$vet = array();
						$y   = 0;

						if($login_fabrica == 15){
							while ($row = pg_fetch_assoc($res_peca)) {
								$vet['peca_referencia'][$y] = trim($row['peça_referencia']);
								$vet['descricao_peca'][$y]  = trim($row['descricao_peca']);
								$y++;

							}							
							if($y < $total){
								for($j = $y; $j < $total; $j++){
									$vet['peca_referencia'][$j] = "";
									$vet['descricao_peca'][$j]  = "";
								}
							}	
							
							
						}
						else{
							while ($row = pg_fetch_assoc($res_peca)) {
								$vet['peca_referencia'][$y] = trim($row['peça_referencia']);
								$vet['descricao_peca'][$y]  = trim($row['descricao_peca']);
								$vet['defeito_peca'][$y]    = trim($row['defeito_peca']);
								$y++;
							}
						}
						
						foreach($vet['peca_referencia'] AS $xpeca_referencia){
							if(strlen($xpeca_referencia)>0){
								fputs ($fp,"<TD nowrap>$xpeca_referencia</TD>");
							}
							else{
								fputs ($fp,"<TD nowrap>&nbsp;</TD>");
							}
						}
						
						foreach($vet['descricao_peca'] AS $xdescricao_peca){
							if(strlen($xdescricao_peca)>0){
								fputs ($fp,"<TD nowrap>$xdescricao_peca</TD>");
							}
							else{
								fputs ($fp,"<TD nowrap>&nbsp;</TD>");
							}
						}
						
						if($login_fabrica <> 15){
							foreach($vet['defeito_peca'] AS $xdefeito_peca){
								if(strlen($xpeca_referencia)>0){
									fputs ($fp,"<TD nowrap>$xdefeito_peca</TD>");
								}
							}
						}
					}
				}

				$os_ant = $array_os[$i];
			}
		fputs ($fp,"</tr>");
		fputs ($fp,"</table><br><br>");
		}
	}else{
		fputs ($fp,"<p align='center'>Nenhum resultado encontrado.</p>");
	}
	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);
	echo ` cp $arquivo_completo_tmp $path `;

	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	#system("zip -o /www/assist/www/admin/xls/relatorio-engenharia-os.zip $arquivo_completo ");
	echo ` cd /www/assist/www/admin/xls/; rm relatorio-engenharia-os.zip; zip -o relatorio-engenharia-os.zip $arquivo_nome > NULL `;
	#echo ` cd $path; rm $arquivo_nome.zip; zip $arquivo_nome.zip $arquivo_nome > NULL`;

	echo"<BR><table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'><input type='button' value='Download em Excel' onclick=\"window.location='xls/relatorio-engenharia-os.zip'\"></td>";
	echo "</tr>";
	echo "</table>";


}
?>

<table width='500' align='center'><tr><td style='font-size: 9px; font-family:verdana; color:#C7C7C7' align='center'>
*Este relatório compreende todas as OS´s que estão em extrato.<br>
**Apenas os produtos das seguintes famílias fazem parte deste relatório:<br>
Bebedouro Convencional; Bebedouro Eletrônico; Bebedouro Hot & Cold; Centrífuga de Roupas;
Lavadora LE; Lavadora LS; Lavadora LX; Purificador Convencional; Purificador Eletrônico;
Purificador Hot & Cold; Ventilador de Teto.</td></tr></table>
<br><br>
<?

include "rodape.php";
?>