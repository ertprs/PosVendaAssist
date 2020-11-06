<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

# SELECTS para teste dos valores
# Verificar o motivo de tantas notas de entrada estarem SEM CONFERENCIA
# 12/01/09
#
/*

select fabrica, cfop, sum (total_nota) from tbl_faturamento where posto = 4311 and emissao between '2008-12-01' and '2008-12-31' and conferencia IS NOT NULL group by fabrica,cfop order by fabrica, cfop ;

select fabrica, cfop, sum (total_nota) from tbl_faturamento where distribuidor = 4311 and emissao between '2008-12-01' and '2008-12-31' group by fabrica,cfop order by fabrica, cfop ;


*/



$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) == 0) {

	echo "<h1>Geração de Arquivos Contábeis - CUCA FRESCA</h1>";

	echo "Instruções: <br>
	1)Escolha o mês e o ano.<br>
	2)Escolha se o movimento é de entrada ou saída<br>
	3)A opção de <b>apenas totais</b> é exclusiva do Dir. Túlio<br>
	4)Clique em gerar arquivo<br>
	5)Após mostrar todo o conteúdo concluido na nova tela, no Internet Explorer,<br> clique em exibir codigo fonte, copie o conteúdo do código fonte, grave em um<br>
	arquivo no Editplus, salve e envie para roseli@deltacontabil.com.br.";

	echo "<form method='post' name='frm_contabil' action='$PHP_SELF'>";
	echo "Mes -Ano ";
	echo "<select name='mes_ano' size='1'>";
#	echo "<option value='2007-03'>Março 2007</option>";
#	echo "<option value='2007-04'>Abril 2007</option>";
#	echo "<option value='2007-05'>Maio 2007</option>";
#	echo "<option value='2007-06'>Junho 2007</option>";
#	echo "<option value='2007-07'>Julho 2007</option>";
#	echo "<option value='2007-08'>Agosto 2007</option>";
#	echo "<option value='2007-09'>Setembro 2007</option>";
#	echo "<option value='2007-10'>Outubro 2007</option>";
#	echo "<option value='2007-11'>Novembro 2007</option>";
#	echo "<option value='2007-12'>Dezembro 2007</option>";
#	echo "<option value='2008-01'>Janeiro 2008</option>";
#	echo "<option value='2008-02'>Fevereiro 2008</option>";
#	echo "<option value='2008-03'>Março 2008</option>";
#	echo "<option value='2008-04'>Abril 2008</option>";
#	echo "<option value='2008-05'>Maio 2008</option>";
#	echo "<option value='2008-06'>Junho 2008</option>";
#	echo "<option value='2008-07'>Julho 2008</option>";
#	echo "<option value='2008-08'>Agosto 2008</option>";
#	echo "<option value='2008-09'>Setembro 2008</option>";
#	echo "<option value='2008-10'>Outubro 2008</option>";
#	echo "<option value='2008-11'>Novembro 2008</option>";
#	echo "<option value='2008-12'>Dezembro 2008</option>";
#	echo "<option value='2009-01'>Janeiro 2009</option>";
#	echo "<option value='2009-02'>Fevereiro 2009</option>";
#	echo "<option value='2009-03'>Março 2009</option>";
#	echo "<option value='2009-04'>Abril 2009</option>";
#	echo "<option value='2009-05'>Maio 2009</option>";
#	echo "<option value='2009-06'>Junho 2009</option>";
#	echo "<option value='2009-07'>Julho 2009</option>";
#	echo "<option value='2009-08'>Agosto 2009</option>";
#	echo "<option value='2009-09'>Setembro 2009</option>";
#	echo "<option value='2009-10'>Outubro 2009</option>";
#	echo "<option value='2009-11'>Novembro 2009</option>";
#	echo "<option value='2009-12'>Dezembro 2009</option>";
#	echo "<option value='2010-01'>Janeiro 2010</option>";
#	echo "<option value='2010-02'>Fevereiro 2010</option>";
#	echo "<option value='2010-03'>Março 2010</option>";
#	echo "<option value='2010-04'>Abril 2010</option>";
#	echo "<option value='2010-05'>Maio 2010</option>";
#	echo "<option value='2010-06'>Junho 2010</option>";
#	echo "<option value='2010-07'>Julho 2010</option>";
#	echo "<option value='2010-08'>Agosto 2010</option>";
#	echo "<option value='2010-09'>Setembro 2010</option>";
#	echo "<option value='2010-10'>Outubro 2010</option>";
#	echo "<option value='2010-11'>Novembro 2010</option>";
#	echo "<option value='2010-12'>Dezembro 2010</option>";
	echo "<option value='2011-01'>Janeiro 2011</option>";
	echo "<option value='2011-02'>Fevereiro 2011</option>";
	echo "<option value='2011-03'>Março 2011</option>";
	echo "<option value='2011-04'>Abril 2011</option>";
	echo "<option value='2011-05'>Maio 2011</option>";


# select posto, cep into temp table x from tbl_posto join tbl_faturamento using (posto) where tbl_faturamento.distribuidor = 4311 and tbl_faturamento.emissao > '2010-04-01' and tbl_posto.cod_ibge is null ;

# update tbl_posto set cod_ibge = (select cod_ibge from tbl_posto where cod_ibge is not null and substr (tbl_posto.cep,1,4) = substr (x.cep,1,4) limit 1) from x where tbl_posto.posto = x.posto and tbl_posto.cod_ibge is null ;



#	echo "<option value='2005-08'>Agosto 2005</option>";
#	echo "<option value='2005-09'>Setembro 2005</option>";
#	echo "<option value='2005-10'>Outubro 2005</option>";
#	echo "<option value='2005-11'>Novembro 2005</option>";
#	echo "<option value='2005-12'>Dezembro 2005</option>";
#	echo "<option value='2006-01'>Janeiro 2006</option>";
#	echo "<option value='2006-02'>Fevereiro 2006</option>";
#	echo "<option value='2006-03'>Março 2006</option>";
#	echo "<option value='2006-04'>Abril 2006</option>";
#	echo "<option value='2006-05'>Maio 2006</option>";
#	echo "<option value='2006-06'>Junho 2006</option>";
#	echo "<option value='2006-07'>Julho 2006</option>";
#	echo "<option value='2006-08'>Agosto 2006</option>";
#	echo "<option value='2006-09'>Setembro 2006</option>";
#	echo "<option value='2006-10'>Outubro 2006</option>";
#	echo "<option value='2006-11'>Novembro 2006</option>";
#	echo "<option value='2006-12'>Dezembro 2006</option>";
#	echo "<option value='2007-01'>Janeiro 2007</option>";
#	echo "<option value='2007-02'>Fevereiro 2007</option>";

	echo "</select>";

	echo "<p>";

	echo "Movimento ";
	echo "<select name='movto' size='1'>";
	echo "<option value='saida'>Saídas</option>";
	echo "<option value='entrada'>Entradas</option>";
	echo "</select>";

	
	echo "<p>";

	echo "<input type='checkbox' name='totais' value='T'>" ;
	echo " Apenas Totais" ;

	
	echo "<p>";

	echo "<input type='submit' name='btn_acao' value='Gerar Arquivo'>";


	echo "</form>";

}else{

	$movto        = $_POST['movto'] ;
	$mes_ano      = $_POST['mes_ano'] . "-01";
	$data_inicial = $mes_ano ;
	$data_final   = pg_result (pg_exec ($con,"SELECT ('$data_inicial'::date + INTERVAL '1 month' - INTERVAL '1 day')::date "),0,0);
	$data_final   = $data_final . " 23:59:59";

	$total_vendas   = 0;
	$total_garantia = 0;
	$total_outras   = 0;
	$qtde_acertos = 0 ;

	if ($movto == "saida") {
		$sql = "SELECT  tbl_faturamento.nota_fiscal , 
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
						tbl_faturamento.emissao AS emissao_data ,
						tbl_faturamento.tipo_pedido ,
						tbl_faturamento.total_nota ,
						tbl_faturamento.cfop ,
						tbl_faturamento.base_icms ,
						tbl_faturamento.valor_icms ,
						tbl_faturamento.valor_frete ,
						tbl_faturamento.garantia_antecipada ,
						tbl_faturamento.emir ,
						tbl_posto.posto , 
						tbl_posto.nome , 
						tbl_posto.cnpj ,
						tbl_posto.cidade,
						tbl_posto.estado ,
						tbl_posto.ie
				FROM tbl_faturamento
				JOIN tbl_posto USING (posto)
				WHERE tbl_faturamento.distribuidor = 4311
				AND   tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final'
				AND   (tbl_faturamento.emir IS NOT FALSE OR tbl_faturamento.emissao > '2006-12-01')
				AND   tbl_faturamento.fabrica <> 0
				ORDER BY tbl_faturamento.nota_fiscal";

/*
				AND   tbl_faturamento.fabrica      IN (3,25,51, 45)
Dados corretos da Britania 
Cnpj: 76.492.701/0001-57
'Inscr. Estadual: 1050341565
*/
		$res = pg_exec ($con,$sql);

		$especie = "NF";
		$serie   = "  ";
		$subserie_nota = " ";

		#$cod_ibge = "41.255.06"; # S.J.Pinhais-PR
		#$cod_ibge = "35.290.05"; # Marilia-SP
		# Samuel alterou 14/08/2008 para ler o codigo do IBGE na tbl_municipio

		echo "<pre>";

		if ($mes_ano >= '2011-01') {
			echo "VERSAO LAYOUT:2011-A.2";
			echo "\r\n";
		} else if ($mes_ano >= '2006-01') {
			echo "VERSAO LAYOUT:2006-B";
			echo "\r\n";
		}

		for ($i = 0; $i < pg_numrows($res); $i++) {

			$nota_fiscal         = pg_result ($res,$i,nota_fiscal) ;
			$estado              = pg_result ($res,$i,estado) ;
			$nome                = substr (trim (pg_result ($res,$i,nome)),0,40) ;
			$cnpj                = pg_result ($res,$i,cnpj) ;
			$ie                  = pg_result ($res,$i,ie) ;
			$total_nota          = pg_result ($res,$i,total_nota) ;
			$cfop                = pg_result ($res,$i,cfop) ;
			$tipo_pedido         = pg_result ($res,$i,tipo_pedido) ;
			$garantia_antecipada = pg_result ($res,$i,garantia_antecipada) ;
			$emir                = pg_result ($res,$i,emir) ;
			$valor_frete         = pg_result ($res,$i,valor_frete);
			$base_icms           = pg_result ($res,$i,base_icms);
			$valor_icms          = pg_result ($res,$i,valor_icms);
			$cidade              = pg_result ($res,$i,cidade);
			$posto               = pg_result ($res,$i,posto);

			# HD 33979
			$sql_mun = "SELECT cod_ibge 
					FROM tbl_posto
					WHERE cnpj = '$cnpj' 
					LIMIT 1";
			$res_mun  = pg_exec ($con,$sql_mun);
			$cod_ibge = pg_result ($res_mun,0,cod_ibge);
			if(strlen($cod_ibge) == 0){
				echo "Não consta na tabela tbl_posto o código do IBGE do CNPJ $cnpj POSTO $posto. Favor procurar a cidade correta deste posto e localize o codigo do IBGE na tbl_municipo, e atualize a tbl_posto! Programa abortou geração de arquivo! Depois do acerto aperte F5";
				exit;
			}

			$valor_frete = $total_nota - $base_icms ;
			$valor_icms  = $base_icms * 0.18 ;

			$faturado_garantia = 'outros';
			if ($tipo_pedido == 2 OR $tipo_pedido == 76 OR $tipo_pedido == 77 OR $tipo_pedido == 116 OR $tipo_pedido == 131 OR $tipo_pedido == 120 OR $tipo_pedido == 153 ) {
				$faturado_garantia = 'faturado';
			}
			if ($tipo_pedido == 3 OR $tipo_pedido == 115 OR $tipo_pedido == 158 OR $tipo_pedido == 132 OR $tipo_pedido == 119 OR $tipo_pedido == 154 OR $tipo_pedido == 158 ) {
				$faturado_garantia = 'garantia';
			}

			if (strlen ($emissao_data) == 0) {
				$emissao_data = pg_result ($res,$i,emissao_data) ;
				$emissao      = pg_result ($res,$i,emissao) ;
			}
			if (pg_result ($res,$i,emissao_data) > $emissao_data ) {
				$emissao      = pg_result ($res,$i,emissao) ;
				$emissao_data = pg_result ($res,$i,emissao_data) ;
			}

			
			$codigo_contabil = "    ";
			$cfop = pg_result ($res,$i,cfop);
			$cfop = substr ($cfop,0,1) . "." . substr ($cfop,1) . " ";


			if ($faturado_garantia == 'faturado') {
				$codigo_contabil = 'V015';
/*				if ($estado == 'SP') {
					$cfop   =  "5.102 ";
				}else{
					$cfop   =  "6.102 ";
				}*/
			}else{
				$codigo_contabil = "R030";
/*				if ($estado == 'SP') {
					$cfop   =  "5.949 ";
				}else{
					$cfop   =  "6.949 ";
				}*/
			}
	
			/* BRITANIA - DEVOLUCAO */
			if ($tipo_pedido == 99) {
				$codigo_contabil = "R030";
				if ($estado == 'SP') {
					$cfop   =  "5.949 ";
				}else{
					$cfop   =  "6.949 ";
				}
			}

			/* BRITANIA - DEVOLUCAO EXCEDENTE */
			if ($tipo_pedido == 101) {
				$codigo_contabil = "D011";
				if ($estado == 'SP') {
					$cfop   =  "5.202 ";
				}else{
					$cfop   =  "6.202 ";
				}
			}

			/* DEVOL. QUEBRADOS */
			if ($tipo_pedido == 105) {
				$codigo_contabil = "D011";
				if ($estado == 'SP') {
					$cfop   =  "5.202 ";
				}else{
					$cfop   =  "6.202 ";
				}
			}


			$aliq_icms   = "18,0000";
			$total_nota  = pg_result ($res,$i,total_nota) ;

			if ($mes_ano <= '2005-11' AND ($faturado_garantia == 'garantia' OR $garantia_antecipada == "t") ) {
				$aliq_icms   = "00,0000";
				$base_icms   = 0;
				$valor_icms  = 0;
				$outras_icms = $total_nota;
			}else{
				$outras_icms = pg_result ($res,$i,valor_frete) ;
				$base_icms   = $total_nota - $outras_icms ;
				$valor_icms  = round ($base_icms * 0.18,2) ;
			}

#---------- Reducao da Base de Calculo ------------
#			if ($faturado_garantia == 'faturado' AND ($emissao_data >= '2006-02-01' AND $emissao_data < '2006-05-25') ) {
#				$base_icms   = round ($base_icms / 3,2);
#				$total_nota  = $base_icms;
#				$valor_icms  = round ($base_icms * 0.18,2);
#				$valor_frete = 0 ;
#				$outras_icms = 0 ;
#			}


			#---------- Ajuste para Setembro de 2007 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 900 AND ($emissao_data >= '2007-09-01' AND $emissao_data < '2007-09-30') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
			}

			#---------- Ajuste para Outubro de 2007 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 95 AND ($emissao_data >= '2007-10-01' AND $emissao_data < '2007-10-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Dezembro de 2007 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 160 AND ($emissao_data >= '2007-12-01' AND $emissao_data < '2007-12-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Janeiro de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 500 AND ($emissao_data >= '2008-01-01' AND $emissao_data < '2008-01-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Fevereiro de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 400 AND ($emissao_data >= '2008-02-01' AND $emissao_data <= '2008-02-29') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Março de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 300 AND ($emissao_data >= '2008-03-01' AND $emissao_data <= '2008-03-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Abril de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 400 AND ($emissao_data >= '2008-04-01' AND $emissao_data <= '2008-04-30') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Maio de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 50 AND ($emissao_data >= '2008-05-01' AND $emissao_data <= '2008-05-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Junho de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 100 AND ($emissao_data >= '2008-06-01' AND $emissao_data <= '2008-06-30') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Julho de 2008 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 100 AND ($emissao_data >= '2008-07-01' AND $emissao_data <= '2008-07-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Julho de 2009 -------------------
			if ($faturado_garantia == 'garantia' AND $base_icms > 500 AND ($emissao_data >= '2009-07-01' AND $emissao_data <= '2009-07-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Agosto de 2009 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 500 AND ($emissao_data >= '2009-08-01' AND $emissao_data <= '2009-08-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			if ($faturado_garantia == 'garantia' AND $base_icms > 2500 AND ($emissao_data >= '2009-08-01' AND $emissao_data <= '2009-08-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			#---------- Ajuste para Outubro de 2009 -------------------
			if ($faturado_garantia == 'faturado' AND $base_icms > 100 AND ($emissao_data >= '2009-10-01' AND $emissao_data <= '2009-10-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			if ($faturado_garantia == 'garantia' AND $base_icms > 600 AND ($emissao_data >= '2009-10-01' AND $emissao_data <= '2009-10-31') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}


			#---------- Ajuste para Fevereiro 2010 -------------------
/*
			if ($faturado_garantia == 'faturado' AND $base_icms > 150 AND ($emissao_data >= '2010-02-01' AND $emissao_data <= '2010-02-28') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

			if ($faturado_garantia == 'garantia' AND $base_icms > 150 AND ($emissao_data >= '2010-02-01' AND $emissao_data <= '2010-02-28') ) {
				$base_icms   = round ($base_icms / 10,2);
				$total_nota  = $base_icms;
				$valor_icms  = round ($base_icms * 0.18,2);
				$valor_frete = 0 ;
				$outras_icms = 0 ;
				$qtde_acertos = $qtde_acertos + 1;
			}

*/
			
			#--------- NFs com flag para Exportação -------------------
			if ($emir == "f" AND $emissao_data >= '2006-12-01' ) {
				$base_icms   = round ($base_icms / 10 , 2);
				$total_nota  = $base_icms;
				if ($tipo_pedido == 99 OR $tipo_pedido == 101 OR $tipo_pedido == 105) {
					$valor_icms  = round ($base_icms * 0.12,2);
					$aliq_icms   = "12,0000";
				}else{
					$valor_icms  = round ($base_icms * 0.18,2);
				}
				$valor_frete = 0 ;
				$outras_icms = 0 ;
			}


			$base_icms   = number_format ($base_icms  ,2,",","");
			$valor_icms  = number_format ($valor_icms ,2,",","");
			$outras_icms = number_format ($outras_icms,2,",","");

			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2) ;
			$total_nota = number_format ($total_nota,2,",","");

			echo "04.716.427/0001-41";
			echo "S";
			echo str_pad($especie,                        5, " ", STR_PAD_RIGHT);
			if ($mes_ano >= '2006-01') {
				echo str_pad($serie,                      3, " ", STR_PAD_RIGHT);
				echo str_pad($subserie_nota,              2, " ", STR_PAD_RIGHT);
			}else{
				echo str_pad($serie,                      4, " ", STR_PAD_RIGHT);
			}
			echo str_pad($nota_fiscal,                   12, " ", STR_PAD_RIGHT);
			echo $emissao;
			echo $estado;
			echo $cnpj;
			echo str_pad($nome,                          40, " ", STR_PAD_RIGHT);
			echo str_pad($ie,                            20, " ", STR_PAD_RIGHT);
			echo str_pad($cod_ibge,                       9, " ", STR_PAD_RIGHT);
			echo str_pad($total_nota,                    12, "0", STR_PAD_LEFT);
			echo "1";
			echo "000000";
			echo "00000000000000,00";  
			echo "00000000000000,00";
			echo "00000000000000,00";
			echo "00000000000000,00";
			echo "000000000000000"  ;  # Registro 31 da GIA
			echo "      " ;            # NF de Devolucao
			echo "     "  ;            # Especie NF Devolucao
			if ($mes_ano >= '2006-01') {
				echo "   "    ;            # Serie   NF Devolucao
				echo "  "     ;            # Subserie NF Devolucao
			}else{
				echo "    "    ;           # Serie   NF Devolucao
			}
			echo "00"     ;            # Desdobramento
			echo "         "     ;     # DIPAM Origem Frete
			echo "000000" ;            # CRO Contador Reinicio Operacao
			echo "00000000000000,00";  # Valor GT Antes do CRO

			echo "\r\n";


			#------------------------------------------------------------

			echo "  ";
			echo str_pad($nota_fiscal,                   12, " ", STR_PAD_RIGHT);
			echo "  " ;					# Centro de Custo
			echo $codigo_contabil ;
			echo str_pad($cfop,                           6, " ", STR_PAD_RIGHT);
			echo str_pad($total_nota,                    12, "0", STR_PAD_LEFT);
			echo str_pad($base_icms,                     12, "0", STR_PAD_LEFT);
			echo $aliq_icms ; 
			echo str_pad($valor_icms,                    12, "0", STR_PAD_LEFT);
			echo "000000000,00";		# valor isento ICMS
			echo str_pad($outras_icms,                   12, "0", STR_PAD_LEFT);
			echo "000000000,00";		# valor diversos ICMS
			echo "00,0000" ;            # aliquota interna ICMS 
			echo "000000000,00";		# valor imposto aliq interna ICMS
			echo "000000000,00";		# base subst. tribut.
			echo "00,0000" ;            # aliquota subst. tribut.
			echo "000000000,00";		# imposto subst. tribut.
			echo "000000000,00";		# inss retido subst. tribut.
			echo "000000000,00";		# base ipi
			echo "00,0000" ;            # aliquota ipi
			echo "000000000,00";		# valor ipi
			echo "000000000,00";		# isento ipi
			echo "000000000,00";		# outras ipi
			echo "000000000,00";		# diversos ipi
			echo "0";
			echo "                      " ; # controle interno
			echo "NF\t";                    # Modelo de Transporte
			echo "1   ";                    # Serie de Transporte
			echo "      ";                  # Nota de Transporte
			echo "          " ;             # Emissao
			echo "  " ;                     # UF Transporte
			echo "                  " ;     # CNPJ Transporte
			echo "                   " ;    # IE Transporte
			echo "0000000000000,00" ;       # Total Transporte
			echo "0" ;                      # Tipo Frete
			echo "000" ;                    # Codigo OBS
			echo str_pad(" ",  250, " ", STR_PAD_RIGHT);
			echo "0" ;                      # Petroleo
			echo "0" ;                      # COFINS Lucro Real
			echo "SP" ;                     # Inicio Transporte

			echo "\r\n";

			if ($faturado_garantia == 'faturado') {
				$total_vendas = $total_vendas   + $total_nota ;
			}else if ($faturado_garantia == 'garantia') {
				$total_garantia = $total_garantia + $total_nota ;
			}else{
				$total_outras = $total_outras + $total_nota ;
			}

		}
	}else{
		#
		#------------------ ENTRADA ----------------------------
		#
		$sql = "SELECT  tbl_faturamento.faturamento ,
						tbl_faturamento.nota_fiscal , 
						tbl_faturamento.fabrica , 
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
						tbl_faturamento.emissao AS emissao_data ,
						tbl_faturamento.tipo_pedido ,
						tbl_faturamento.total_nota ,
						tbl_faturamento.cfop ,
						tbl_faturamento.base_icms ,
						tbl_faturamento.valor_icms ,
						tbl_faturamento.valor_ipi ,
						tbl_faturamento.valor_frete ,
						tbl_faturamento.garantia_antecipada ,
						tbl_faturamento.emir ,
						tbl_faturamento.distribuidor ,
						tbl_posto.nome , 
						tbl_posto.cnpj ,
						tbl_posto.estado ,
						tbl_posto.ie
				FROM  tbl_faturamento
				JOIN  tbl_posto USING (posto)
				LEFT JOIN tbl_posto emissor ON tbl_faturamento.distribuidor = emissor.posto
				WHERE tbl_faturamento.posto   = 4311
				AND   tbl_faturamento.fabrica <> 0
				AND   tbl_faturamento.conferencia IS NOT NULL
				AND   tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final'
				AND   (tbl_faturamento.emir IS NOT FALSE OR tbl_faturamento.emissao > '2006-12-01')
				AND   (tbl_faturamento.fabrica IN (3,51,25,45,81) OR tbl_faturamento.distribuidor IN (58810,26907,114910))
				ORDER BY tbl_faturamento.emissao";

#				Este AND Fabrica OR Distribuidor foi adicionado em agosto de 2010
#				AND   tbl_faturamento.fabrica IN (3,25,51, 45)
#				WHERE tbl_faturamento.faturamento IN (1086471,1086472,1086473,1086474,1086475,1086476,1086477,1086478,1086479,1086480,1086482,1086483,1086484,1086485,1086486,1086487,1086488,1086489,1086490,1086491,1086492,1086493,1086494,1086495,1086496,1086497,1086498,1086499,1086500,1086501,1086502,1086503,1086504,1086505,1086506,1086507,1086508,1086509,1086481,1064809,1075527,1099322,1099639,1100214,1100961) OR (


		$res = pg_exec ($con,$sql);


		$especie = "NF";
		$serie   = "  ";
		$subserie_nota = " ";

		#$cod_ibge = "41.255.06"; # S.J.Pinhais-PR
		#$cod_ibge = "35.290.05"; # Marilia-SP
		# Samuel alterou 14/08/2008 para ler o codigo do IBGE na tbl_municipio

		echo "<pre>";

		if ($mes_ano >= '2011-01') {
			echo "VERSAO LAYOUT:2011-A.2";
			echo "\r\n";
		} else if ($mes_ano >= '2007-01') {
			echo "VERSAO LAYOUT:2006-B";
			echo "\r\n";
		} else if ($mes_ano >= '2006-01') {
			echo "VERSAO LAYOUT:2006-A";
			echo "\r\n";
		}

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$faturamento         = pg_result ($res,$i,faturamento) ;
			$fabrica             = pg_result ($res,$i,fabrica) ;
			$nota_fiscal         = substr (trim (pg_result ($res,$i,nota_fiscal)),0,6) ;
			$estado              = pg_result ($res,$i,estado) ;
			$nome                = substr (trim (pg_result ($res,$i,nome)),0,40) ;
			$cnpj                = pg_result ($res,$i,cnpj) ;
			$ie                  = pg_result ($res,$i,ie) ;
			$total_nota          = pg_result ($res,$i,total_nota) ;
			$tipo_pedido         = pg_result ($res,$i,tipo_pedido) ;
			$garantia_antecipada = pg_result ($res,$i,garantia_antecipada) ;
			$emir                = pg_result ($res,$i,emir) ;
			$valor_frete         = pg_result ($res,$i,valor_frete);
			$base_icms           = pg_result ($res,$i,base_icms);
			$valor_icms          = pg_result ($res,$i,valor_icms);
			$valor_ipi           = pg_result ($res,$i,valor_ipi);
			$emissao_data        = pg_result ($res,$i,emissao_data) ;
			$emissao             = pg_result ($res,$i,emissao) ;
			$distribuidor        = pg_result ($res,$i,distribuidor) ;

			if ($emissao_data < '2008-07-01') {
				$emissao_data = '2008-07-01';
				$emissao      = '01/07/2008';
			}
			
			$codigo_contabil = "    ";
			$cfop = pg_result ($res,$i,cfop);
			$cfop = substr ($cfop,0,1) . "." . substr ($cfop,1) . " ";


			if (substr ($cfop,2,1) == "1") {
				$codigo_contabil = "F011";
				$faturado_garantia = "faturado";
				if ($estado == 'SP') {
					$cfop = "1.102";
				}else{
					$cfop = "2.102";
				}
			}

			if (substr ($cfop,2,1) == "4") {
				$codigo_contabil = "F011";
				$faturado_garantia = "faturado";
				if ($estado == 'SP') {
					$cfop = "1.102";
				}else{
					$cfop = "2.102";
				}
			}

			if (substr ($cfop,2,1) == "9") {
				$codigo_contabil = "R030";
				$faturado_garantia = "garantia";
				if ($estado == 'SP') {
					$cfop = "1.949";
				}else{
					$cfop = "2.949";
				}
			}


			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2) ;

			$total_nota  = pg_result ($res,$i,total_nota) ;
			$total_nota  = number_format ($total_nota ,2,",","");
			$total_nota  = str_pad($total_nota,12, "0", STR_PAD_LEFT);

			if ($fabrica == 3) {
				$fabrica_nome   = 'BRITANIA ELETRODOMESTICOS SA            ';
				$fabrica_cnpj   = '76.492.701/0001-57';
				$fabrica_ie     = '10503415-65         ';
				$fabrica_ibge   = '41.255.06';
				$fabrica_icms   = 12 ;
				$aliq_icms      = "12,0000";
				$fabrica_estado = 'PR';
			}

			if ($fabrica == 51) {
				$fabrica_nome   = 'BRASVINCI COM ACESS EQUIPS BELEZA LTDA  ';
				$fabrica_cnpj   = '07.881.054/0001-52';
				$fabrica_ie     = '149.256.240.117     ';
				$fabrica_ibge   = '29.274.08';
				$fabrica_icms   = 18 ;
				$aliq_icms      = "18,0000";
				$fabrica_estado = 'SP';
			}

			if ($fabrica == 45) {
				$fabrica_nome   = 'CBI INDUSTRIA E COMERCIO LTDA           ';
				$fabrica_cnpj   = '02.093.397/0005-62';
				$fabrica_ie     = '62893908-NO         ';
				$fabrica_ibge   = '29.274.08';
				$fabrica_icms   = 12 ;
				$aliq_icms      = "12,0000";
				$fabrica_estado = 'BA';
			}

			if ($fabrica == 25) {
				$fabrica_nome   = 'GRUPO HB ELECTRONICS ASSESS. COML LTDA  ';
				$fabrica_cnpj   = '03.401.837/0001-30';
				$fabrica_ie     = '115.472.395.117     ';
				$fabrica_ibge   = '35.503.08';
				$fabrica_icms   = 18 ;
				$aliq_icms      = "18,0000";
				$fabrica_estado = 'SP';
			}


			if ($fabrica == 81) {
				$fabrica_nome   = 'SALTON BRASIL COM IMP EXT PROD ELETRONIC';
				$fabrica_cnpj   = '05.753.154/0001-78';
				$fabrica_ie     = '116.624.320.119     ';
				$fabrica_ibge   = '35.503.08';
				$fabrica_icms   = 18 ;
				$aliq_icms      = "18,0000";
				$fabrica_estado = 'SP';
			}


			#--------- Mudança feita a partir de agosto 2010 ------------------
			if ($fabrica == 10) {
				if ($distribuidor == '58810') {
					$fabrica_nome   = 'SALTON BRASIL COM IMP EXT PROD ELETRONIC';
					$fabrica_cnpj   = '05.753.154/0001-78';
					$fabrica_ie     = '116.624.320.119     ';
					$fabrica_ibge   = '35.503.08';
					$fabrica_icms   = 18 ;
					$aliq_icms      = "18,0000";
					$fabrica_estado = 'SP';
				}

				if ($distribuidor == '26907') {
					$fabrica_nome   = 'BRASITECH IND. COM. DE AP. P BELEZA LTDA';
					$fabrica_cnpj   = '07.881.054/0001-52';
					$fabrica_ie     = '149.256.240.117     ';
					$fabrica_ibge   = '35.503.08';
					$fabrica_icms   = 18 ;
					$aliq_icms      = "18,0000";
					$fabrica_estado = 'SP';
				}

				if ($distribuidor == '114910') {
					$fabrica_nome   = 'BRASITECH IND. COM. DE AP. P BELEZA LTDA';
					$fabrica_cnpj   = '07.293.118/0004-47';
					$fabrica_ie     = '147.618.438.110     ';
					$fabrica_ibge   = '35.503.08';
					$fabrica_icms   = 18 ;
					$aliq_icms      = "18,0000";
					$fabrica_estado = 'SP';
				}

			
			}
			#-------------------------------------------------


			#--------------- Acertos para Agosto de 2010 (tem que fazer em 2 lugares) ----------------------------------------
			if ($faturado_garantia == 'garantia' AND strpos ("2004996, 1989380, 1995900, 2005001, 2004993, 2000315, 2000314, 2004999, 1993468, 1989315, 1989376, 1974162" , $faturamento)!== false AND ($emissao_data >= '2010-08-01' AND $emissao_data <= '2010-08-31') ) {
				$total_nota  = pg_result ($res,$i,total_nota) ;
				$total_nota   = round ($total_nota / 1000,2);
				$total_nota  = number_format ($total_nota ,2,",","");
				$total_nota  = str_pad($total_nota,12, "0", STR_PAD_LEFT);
				$qtde_acertos = $qtde_acertos + 1;
			}

			#--------------- Acertos para Setembro de 2010 (tem que fazer em 2 lugares) ----------------------------------------
			if ($faturado_garantia == 'garantia' AND strpos ("2045170, 2008664, 2032327, 2013516, 2001371, 2008659, 2026320, 2032330, 2013515, 2002102, 2037713, 2003478, 2045486, 2037712, 2049652, 2031260, 2031259, 2045482, 2006749" , $faturamento)!== false AND ($emissao_data >= '2010-09-01' AND $emissao_data <= '2010-09-30') ) {
				$total_nota  = pg_result ($res,$i,total_nota) ;
				$total_nota   = round ($total_nota / 1000,2);
				$total_nota  = number_format ($total_nota ,2,",","");
				$total_nota  = str_pad($total_nota,12, "0", STR_PAD_LEFT);
				$qtde_acertos = $qtde_acertos + 1;
			}

			
			
			
			echo "04.716.427/0001-41";
			echo "E";
			echo $emissao;
			echo str_pad($especie,                        5, " ", STR_PAD_RIGHT);
			if ($mes_ano >= '2006-01') {
				echo str_pad($serie,                      3, " ", STR_PAD_RIGHT);
				echo str_pad($subserie_nota,              2, " ", STR_PAD_RIGHT);
			}else{
				echo str_pad($serie,                      4, " ", STR_PAD_RIGHT);
			}
			echo str_pad($nota_fiscal,                    6, "0", STR_PAD_LEFT);
			echo $emissao;
			echo $fabrica_cnpj;                              # CNPJ Fornecedor
			echo $fabrica_nome;                              # Razao Social Fornecedor
			echo $fabrica_ie;                                # Inscr. Estadual
			echo $fabrica_ibge;                              # Cod. IBGE Britania
			echo $total_nota;
			echo "         ";                                # Conta Banco Pagto Cheque
			echo "00/00/0000";                               # Data do Cheque
			echo "000000";                                   # Numero do Cheque
			echo "1";                                        # A prazo
			echo "            ";                             # NF Devolucao a prazo
			echo "     "  ;                                  # Especie NF Devolucao
			if ($mes_ano >= '2006-01') {
				echo "   "    ;                              # Serie    NF Devolucao
				echo "  "     ;                              # Subserie NF Devolucao
			}else{
				echo "    "    ;                             # Serie    NF Devolucao
			}
			echo "00"     ;                                  # Desdobramento

			echo "0"     ;                                   # GISS
			echo "                                        ";
			echo "     " ;
			echo "         ";
			echo "                              ";
			echo "  ";
			echo "           ";

			echo "\r\n";


			#------------------------------------------------------------

			if ($mes_ano <= '2005-10' AND $codigo_contabil == "R030") {
				$aliq_icms    = "00,0000";
				$fabrica_icms = 0;
				$total_nota   = pg_result ($res,$i,total_nota);
				$base_icms    = "000000000,00";
				$valor_icms   = "000000000,00";
				$outras_icms  = $total_nota;
			}else{
				$faturamento = pg_result ($res,$i,faturamento);
				$sql = "SELECT ROUND ((SUM (tbl_faturamento_item.preco * tbl_faturamento_item.qtde * tbl_peca.ipi / 100))::numeric,2) FROM tbl_faturamento_item JOIN tbl_peca USING (peca) WHERE tbl_faturamento_item.faturamento = $faturamento";
				$resX = pg_exec ($con,$sql);
				$valor_ipi   = pg_result ($resX,0,0);
				$total_nota  = pg_result ($res,$i,total_nota);
				$base_icms   = $total_nota - $valor_ipi ;
				$valor_icms  = $base_icms * ($fabrica_icms / 100) ;
				$outras_icms = $total_nota - $base_icms ;
			}
			


			#--------------- Acertos para Agosto de 2010 (tem que fazer em 2 lugares) ----------------------------------------
			if ($faturado_garantia == 'garantia' AND strpos ("2004996, 1989380, 1995900, 2005001, 2004993, 2000315, 2000314, 2004999, 1993468, 1989315, 1989376, 1974162" , $faturamento)!== false AND ($emissao_data >= '2010-08-01' AND $emissao_data <= '2010-08-31') ) {
				$total_nota   = round ($total_nota  / 1000,2);
				$valor_ipi    = round ($valor_ipi   / 1000,2);
				$base_icms    = round ($base_icms   / 1000,2);
				$valor_icms   = round ($valor_icms  / 1000,2);
				$outras_icms  = round ($outras_icms / 1000,2);
			}
			#--------------- Acertos para Setembro de 2010 (tem que fazer em 2 lugares) ----------------------------------------
			if ($faturado_garantia == 'garantia' AND strpos ("2045170, 2008664, 2032327, 2013516, 2001371, 2008659, 2026320, 2032330, 2013515, 2002102, 2037713, 2003478, 2045486, 2037712, 2049652, 2031260, 2031259, 2045482, 2006749" , $faturamento)!== false AND ($emissao_data >= '2010-09-01' AND $emissao_data <= '2010-09-30') ) {
				$total_nota   = round ($total_nota  / 1000,2);
				$valor_ipi    = round ($valor_ipi   / 1000,2);
				$base_icms    = round ($base_icms   / 1000,2);
				$valor_icms   = round ($valor_icms  / 1000,2);
				$outras_icms  = round ($outras_icms / 1000,2);
			}

			
			$total_nota  = number_format ($total_nota ,2,",","");
			$total_nota  = str_pad($total_nota,12, "0", STR_PAD_LEFT);

			$base_icms   = number_format ($base_icms ,2,",","");
			$base_icms   = str_pad($base_icms,12, "0", STR_PAD_LEFT);

			$valor_icms  = number_format ($valor_icms ,2,",","");
			$valor_icms  = str_pad($valor_icms,12, "0", STR_PAD_LEFT);

			$outras_icms = number_format ($outras_icms ,2,",","");
			$outras_icms = str_pad($outras_icms,12, "0", STR_PAD_LEFT);

			
			echo "  ";
			echo str_pad($nota_fiscal,                    6, " ", STR_PAD_RIGHT);
			echo "  " ;					# Centro de Custo
			echo $codigo_contabil ;
			echo str_pad($cfop,                           6, " ", STR_PAD_RIGHT);
			echo $total_nota ;
			echo $base_icms;
			echo $aliq_icms ; 
			echo $valor_icms;
			echo "000000000,00";		# valor isento ICMS
			echo $outras_icms;
			echo "000000000,00";		# valor diversos ICMS
			echo "00,0000";				# aliq. interna
			echo "000000000,00";		# imposto aliq. interna
			echo "000000000,00";		# base subst. tribut.
			echo "00,0000";				# aliq. subst. tribut.
			echo "000000000,00";		# valor subst. tribut.

			echo "000000000,00";		# base ipi
			echo "00,0000";				# aliq. ipi
			echo "000000000,00";		# valor ipi
			echo "000000000,00";		# isento ipi
			echo "000000000,00";		# outras ipi
			echo "000000000,00";		# diversos ipi
			echo "000000000,00";		# pvv cigarro
			echo "000000000,00";		# saidas 12
			echo "000000000,00";		# saidas 25
			echo "000000000,00";		# base calc red
			echo "00,0000";				# aliq. efetiva

			echo "     ";				# controle interno
			echo "  ";				    # controle interno
			echo "      ";				# controle interno
			echo "          ";			# controle interno
			echo "  ";					# controle interno
			echo "                  ";	# controle interno
			echo "                   ";	# controle interno
			echo "0000000000000,00";	# controle interno

			echo "1";				    # tipo do frete
			echo "   ";				    # codigo obs
			echo str_pad(" ",  250, " ", STR_PAD_RIGHT);
			echo "0" ;                      # Petroleo
			echo "0" ;                      # DNF
			echo "0" ;                      # Aliq. COFINS

			echo "          " ;             # Cod. Servico Tomado
			echo "000000000,00";		    # valor servico
			echo "00,0000";                 # aliq iss
			echo "000000000,00";		    # valor iss retido
			echo "000000000,00";		    # valor inss retido
			echo "000000000,00";		    # valor irrf retido
			echo "000000000,00";		    # valor pis retido
			echo "000000000,00";		    # valor cofins retido
			echo "000000000,00";		    # valor contrib. social retido

			echo $fabrica_estado;			# estado do fornecedor

			echo "\r\n";


			if ($codigo_contabil == "F011") {
				$total_vendas = $total_vendas   + $total_nota ;
			}else if ($codigo_contabil == "R030") {
				$total_garantia = $total_garantia + $total_nota ;
			}else{
				$total_outras = $total_outras + $total_nota ;
			}


		
		}
	}


	/*

	R30 - 2949       Mercadorias recebidas da Britania em GARANTIA
	F11 - 2102       Mercadorias recebidas da Britania em FATURADA

	V11 - 5102  	 Vendas - Revenda
	R30 - 5949       Saidas em Garantia

	R30 - 6949       Devolucoes em Garantia para a Fabrica
	R30 - 1949       Devolucoes recebidas dos postos

	D11 - 6202       Devolucao de compra para BRITANIA

	aumentou para 4 digitos em 2011

	roseli@deltacontabil.com.br
	Enviar no email para a Roseli o resumo dos totais

	*/
}


if ($totais == "T") {
	echo "<hr>";
	echo "Vendas - " ;
	echo number_format ($total_vendas,2,",",".") ;
	echo "<br>";

	echo "Garantia - " ;
	echo number_format ($total_garantia,2,",",".") ;
	echo "<br>";

	echo "Outras - " ;
	echo number_format ($total_outras,2,",",".") ;
	echo "<br>";

	echo "Acertos - " ;
	echo number_format ($qtde_acertos,0,",",".") ;
	echo "<br>";


}

?>
