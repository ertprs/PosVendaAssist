<?

LUIS, preciso ir embora.

Estes 2 codigos abaixo serao rodados no DBBLACK

Eles extraem em 2 segundos o relatório por produto e por peças, em 2 arquivos que deverão ser relacionados para gerar as 3 maiores peças.

Selects semelhantes farão o UNION com as informações do banco novo TELECONTROL.


Se você fizer as telas do programa amanhã a gente chega as 7:30 e acaba.

Pode ser ?


Este relatório, SEM O DADOS ANTIGOS, serve para todas as fabricas.


T+





$sql = "

DROP TABLE black_visao_produto ;

SELECT  tbl_produto.referencia     AS referencia_produto,
		tbl_produto.voltagem       AS voltagem          ,
		CASE WHEN tbl_marca.marca = 1 THEN
			199
		ELSE
			CASE WHEN tbl_marca.marca = 2 THEN
				198
			ELSE
				200
			END
		END                            AS linha         ,
		tbl_cidade.estado                               ,
		tbl_posto.codigo               AS posto_codigo  ,
		os.financeiro                                   ,
		os.aprovado                                     ,
		os.qtde::int4                                   ,
		os.mao_obra::float                              ,
		os.pecas::float
INTO TEMP TABLE black_visao_produto
FROM      tbl_produto
JOIN      tbl_grupo       USING (grupo)
JOIN      tbl_marca       USING (marca)
LEFT JOIN (
			SELECT  tbl_new_os.posto                                          ,
					tbl_new_os.produto                                        ,
					tbl_new_extrato_financeiro.data_envio        AS financeiro,
					tbl_new_extrato.aprovado                     AS aprovado  ,
					COUNT(*)                                     AS qtde      ,
					SUM (tbl_new_os_extra.total_mao_de_obra)     AS mao_obra  ,
					SUM (tbl_new_os_extra.total_pecas)           AS pecas
			FROM   tbl_new_os
			JOIN   tbl_new_os_extra USING (new_os)
			JOIN   tbl_new_extrato  USING (new_extrato)
			JOIN   tbl_new_extrato_financeiro USING (new_extrato)
			WHERE  tbl_new_extrato_financeiro.data_envio BETWEEN '2004-01-01' AND '2005-12-31'
			GROUP BY    tbl_new_os.posto                     ,
						tbl_new_os.produto                   ,
						tbl_new_extrato_financeiro.data_envio,
						tbl_new_extrato.aprovado
			) os ON tbl_produto.produto = os.produto
LEFT JOIN tbl_posto  ON tbl_posto.posto = os.posto 
LEFT JOIN tbl_cidade ON tbl_posto.municipio = tbl_cidade.municipio
WHERE qtde NOTNULL;

COPY black_visao_produto TO '/tmp/black_visao_produto' WITH NULL AS '';




DROP TABLE black_visao_peca ;

SELECT  tbl_produto.referencia     AS referencia_produto,
		tbl_produto.voltagem       AS voltagem          ,
		CASE WHEN tbl_marca.marca = 1 THEN
			199
		ELSE
			CASE WHEN tbl_marca.marca = 2 THEN
				198
			ELSE
				200
			END
		END                            AS linha         ,
		tbl_cidade.estado                               ,
		tbl_posto.codigo               AS posto_codigo  ,
		os.financeiro                                   ,
		os.aprovado                                     ,
		tbl_peca.referencia                             ,
		os.qtde
INTO TEMP TABLE black_visao_peca
FROM      tbl_produto
JOIN      tbl_grupo       USING (grupo)
JOIN      tbl_marca       USING (marca)
LEFT JOIN (
			SELECT  tbl_new_os.posto                                          ,
					tbl_new_os.produto                                        ,
					tbl_new_extrato_financeiro.data_envio        AS financeiro,
					tbl_new_extrato.aprovado                     AS aprovado  ,
					tbl_new_os_item.peca                                      ,
					SUM (tbl_new_os_item.qtde)                   AS qtde
			FROM   tbl_new_os
			JOIN   tbl_new_os_extra           ON tbl_new_os_extra.new_os                = tbl_new_os.new_os
			JOIN   tbl_new_extrato            ON tbl_new_extrato.new_extrato            = tbl_new_os_extra.new_extrato
			JOIN   tbl_new_extrato_financeiro ON tbl_new_extrato_financeiro.new_extrato = tbl_new_extrato.new_extrato
			LEFT JOIN tbl_new_os_item         ON tbl_new_os.new_os                      = tbl_new_os_item.new_os
			WHERE  tbl_new_extrato.aprovado BETWEEN '2004-01-01' AND '2005-12-31'
			GROUP BY    tbl_new_os.posto                     ,
						tbl_new_os.produto                   ,
						tbl_new_extrato_financeiro.data_envio,
						tbl_new_extrato.aprovado             ,
						tbl_new_os_item.peca
			) os ON tbl_produto.produto = os.produto
LEFT JOIN tbl_posto  ON tbl_posto.posto = os.posto 
LEFT JOIN tbl_cidade ON tbl_posto.municipio = tbl_cidade.municipio
LEFT JOIN tbl_peca   ON tbl_peca.peca = os.peca;

COPY black_visao_peca TO '/tmp/black_visao_peca' WITH NULL AS '';




";

?>
