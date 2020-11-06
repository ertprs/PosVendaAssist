SELECT tbl_os.os, tbl_os.sua_os, tbl_defeito_constatado.descricao as defeito_constatado, to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura , to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento , to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada , tbl_posto_fabrica.codigo_posto AS codigo_posto , tbl_posto.nome AS posto_nome , tbl_os.produto , tbl_produto.familia , tbl_produto.referencia_pesquisa AS referencia , tbl_produto.descricao as produto_descricao 
FROM tbl_os 
JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
JOIN tbl_posto_fabrica ON tbl_posto.posto= tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 14 
JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado 
WHERE tbl_os.fabrica = 14 AND (data_abertura::date BETWEEN '2006-09-01' AND '2006-09-13') AND tbl_os.produto=10928
GROUP BY tbl_os.defeito_constatado, 
order by tbl_defeito_constatado.descricao, tbl_os.data_abertura, tbl_os.data_fechamento
