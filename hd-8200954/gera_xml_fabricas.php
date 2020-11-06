<?
/* ESTE PROGRAMA GERA AS TABELAS XML P/ O PROGRAMA OFFLINE, SEPARADAS POR FABRICANTE */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

//PEGA OS FABRICANTES
$sql = "SELECT  tbl_fabrica.fabrica                        ,
				tbl_fabrica.nome                           ,
				tbl_fabrica.pedido_escolhe_condicao        ,
				tbl_fabrica.defeito_constatado_por_familia 
		FROM tbl_fabrica
		WHERE tbl_fabrica.sistema_offline IS TRUE
		ORDER BY tbl_fabrica.fabrica;";
$resX = pg_exec($con,$sql);


//GERA OS ARQUIVOS PARA CADA FABRICANTE
if (pg_numrows($resX) > 0) {
	for ($x=0; $x < pg_numrows($resX); $x++) {
		$fabricante      = pg_result($resX,$x,fabrica);
		$fabricante_nome = pg_result($resX,$x,nome);
		
		//CRIA PASTA DO FABRICANTE
		if (!is_dir("xml/".$fabricante_nome)) {
			echo ` cd xml; mkdir $fabricante_nome; chmod 777 $fabricante_nome; cd $fabricante_nome; `;
		}else{
			echo ` cd xml; chmod 777 $fabricante_nome; cd $fabricante_nome; ` ;
		}
		
		##################################################################################################################
		// PRODUTOS
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                         AS fabrica   ,
						trim(tbl_produto.produto)                         AS produto   ,
						trim(tbl_produto.linha)                           AS linha     ,
						trim(tbl_produto.familia)                         AS familia   ,
						trim(tbl_produto.referencia)                      AS referencia,
						trim(replace(tbl_produto.descricao,'\"','\'\''))  AS descricao ,
						trim(tbl_produto.voltagem)                        AS voltagem  ,
						tbl_produto.garantia                                           ,
						tbl_produto.radical_serie                                      ,
						tbl_produto.numero_serie_obrigatorio                           ,
						tbl_produto.abre_os
				FROM        tbl_fabrica
				JOIN        tbl_linha        ON tbl_linha.fabrica        = tbl_fabrica.fabrica
				JOIN        tbl_produto      ON tbl_produto.linha        = tbl_linha.linha
				LEFT JOIN   tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto
											AND tbl_lista_basica.fabrica = tbl_fabrica.fabrica
											AND tbl_lista_basica.fabrica IN ($fabricante)
				LEFT JOIN   tbl_familia      ON tbl_familia.familia      = tbl_produto.familia
				WHERE       tbl_produto.ativo IS TRUE ";
		if (strlen($fabricante) > 0) 
			$sql .= "AND (tbl_linha.fabrica IN ($fabricante) OR tbl_familia.fabrica IN ($fabricante)) ";
		$sql .= "GROUP BY   tbl_fabrica.fabrica                 ,
							tbl_produto.produto                 ,
							tbl_produto.linha                   ,
							tbl_produto.familia                 ,
							tbl_produto.referencia              ,
							tbl_produto.descricao               ,
							tbl_produto.voltagem                ,
							tbl_produto.garantia                ,
							tbl_produto.radical_serie           ,
							tbl_produto.numero_serie_obrigatorio,
							tbl_produto.abre_os
				ORDER BY    tbl_fabrica.fabrica,
							lpad(tbl_produto.referencia,20,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_produto = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_produto .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_produto .= "<METADATA>\n";
		$xml_produto .= "<FIELDS>\n";
		$xml_produto .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_produto .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_produto .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_produto .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_produto .= "<FIELD attrname=\"referencia\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_produto .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_produto .= "<FIELD attrname=\"voltagem\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_produto .= "<FIELD attrname=\"garantia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_produto .= "<FIELD attrname=\"radical_serie\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
		$xml_produto .= "<FIELD attrname=\"numero_serie_obrigatorio\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
		$xml_produto .= "<FIELD attrname=\"abre_os\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
		$xml_produto .= "</FIELDS>\n";
		$xml_produto .= "</METADATA>\n";
		$xml_produto .= "<ROWDATA>\n";
		
		$fabrica    = "";
		$produto    = "";
		$linha      = "";
		$familia    = "";
		$referencia = "";
		$descricao  = "";
		$voltagem   = "";
		$garantia   = "";
		$radical    = "";
		$serie_ob   = "";
		$abre_os    = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_produto = pg_fetch_array($res)){
				$contador++;
				$fabrica    = trim($ln_produto['fabrica']);
				$produto    = trim($ln_produto['produto']);
				$linha      = trim($ln_produto['linha']);
				$familia    = trim($ln_produto['familia']);
				$referencia = trim($ln_produto['referencia']);
				$descricao  = trim($ln_produto['descricao']);
				$voltagem   = trim($ln_produto['voltagem']);
				$garantia   = trim($ln_produto['garantia']);
				$radical    = trim($ln_produto['radical_serie']);
				$serie_ob   = trim($ln_produto['numero_serie_obrigatorio']);
				$abre_os    = trim($ln_produto['abre_os']);
				
				if (strlen($serie_ob) == 0) $serie_ob = "f";
				//if (strlen($abre_os) == 0)  $abre_os  = "f";
				
				$xml_produto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" referencia=\"$referencia\" descricao=\"$descricao\" voltagem=\"$voltagem\" garantia=\"$garantia\" radical_serie=\"$radical\" numero_serie_obrigatorio=\"$serie_ob\" abre_os=\"$abre_os\"/>\n";
				flush();
			}
		}else{
			$xml_produto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" referencia=\"$referencia\" descricao=\"$descricao\" voltagem=\"$voltagem\" garantia=\"$garantia\" radical_serie=\"$radical\" numero_serie_obrigatorio=\"$serie_ob\" abre_os=\"$abre_os\"/>\n";
			flush();
		}
		
		$xml_produto .= "</ROWDATA>\n";
		$xml_produto .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/produto.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_produto);
		fclose($arquivo);


		##################################################################################################################
		// SUB-PRODUTO
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                         AS fabrica    ,
						trim(tbl_subproduto.produto_pai)                  AS produto_pai    ,
						trim(tbl_subproduto.produto_filho)                AS produto_filho
				FROM    tbl_subproduto
				JOIN    tbl_produto  ON tbl_produto.produto = tbl_subproduto.produto_pai
				JOIN    tbl_linha    ON tbl_linha.linha     = tbl_produto.linha
				JOIN    tbl_familia  ON tbl_familia.familia = tbl_produto.familia
				JOIN    tbl_fabrica  ON tbl_fabrica.fabrica = tbl_linha.fabrica
									AND tbl_fabrica.fabrica = tbl_familia.fabrica
				WHERE   tbl_fabrica.os_item_subconjunto IS TRUE ";
		if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_subproduto.produto_pai, tbl_subproduto.produto_filho;";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_subproduto  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml_subproduto .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_subproduto .= "<METADATA>\n";
		$xml_subproduto .= "<FIELDS>\n";
		$xml_subproduto .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_subproduto .= "<FIELD attrname=\"produto_pai\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_subproduto .= "<FIELD attrname=\"produto_filho\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_subproduto .= "</FIELDS>\n";
		$xml_subproduto .= "</METADATA>\n";
		$xml_subproduto .= "<ROWDATA>\n";
		
		$fabrica       = "";
		$produto_pai   = "";
		$produto_filho = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_subproduto = pg_fetch_array($res)){
				$contador++;
				$fabrica       = $ln_subproduto['fabrica'];
				$produto_pai   = $ln_subproduto['produto_pai'];
				$produto_filho = $ln_subproduto['produto_filho'];
				
				$xml_subproduto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto_pai=\"$produto_pai\" produto_filho=\"$produto_filho\"/>\n";
				flush();
			}
		}else{
			$xml_subproduto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto_pai=\"$produto_pai\" produto_filho=\"$produto_filho\"/>\n";
			flush();
		}
		
		$xml_subproduto .= "</ROWDATA>\n";
		$xml_subproduto .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/subproduto.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_subproduto);
		fclose($arquivo);


		##################################################################################################################
		// PEÇA  //trim(replace(tbl_peca.descricao,'\"','\'\''))  AS descricao  ,
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                      AS fabrica    ,
						trim(tbl_peca.peca)                            AS peca       ,
						trim(tbl_peca.referencia)                      AS referencia ,
						trim(replace(tbl_peca.descricao,'\"',' '))     AS descricao  ,
						trim(tbl_peca.origem)                          AS origem     ,
						trim(tbl_peca.multiplo)                        AS multiplo   ,
						trim(tbl_peca.garantia_diferenciada)           AS garantia_diferenciada
				FROM    tbl_peca
				JOIN    tbl_fabrica      ON tbl_fabrica.fabrica      = tbl_peca.fabrica
				JOIN    tbl_lista_basica ON tbl_lista_basica.peca    = tbl_peca.peca
										AND tbl_lista_basica.fabrica IN ($fabricante)
										AND tbl_lista_basica.fabrica = tbl_fabrica.fabrica
				WHERE   tbl_peca.ativo IS TRUE ";
		if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "GROUP BY   tbl_fabrica.fabrica,
							tbl_peca.peca      ,
							tbl_peca.referencia,
							tbl_peca.descricao ,
							tbl_peca.origem    ,
							tbl_peca.multiplo  ,
							garantia_diferenciada
				ORDER BY    lpad(tbl_peca.referencia,20,0)";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_peca  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml_peca .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_peca .= "<METADATA>\n";
		$xml_peca .= "<FIELDS>\n";
		$xml_peca .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_peca .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_peca .= "<FIELD attrname=\"referencia\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_peca .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_peca .= "<FIELD attrname=\"qtde_pedido\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_peca .= "<FIELD attrname=\"multiplo\" fieldtype=\"string\" WIDTH=\"3\"/>\n";
		$xml_peca .= "</FIELDS>\n";
		$xml_peca .= "</METADATA>\n";
		$xml_peca .= "<ROWDATA>\n";
		
		$fabrica               = "";
		$peca                  = "";
		$referencia            = "";
		$descricao             = "";
		$origem                = "";
		$multiplo              = "";
		$garantia_diferenciada = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_peca = pg_fetch_array($res)){
				$contador++;
				$fabrica               = $ln_peca['fabrica'];
				$peca                  = $ln_peca['peca'];
				$referencia            = $ln_peca['referencia'];
				$descricao             = $ln_peca['descricao'];
				$origem                = $ln_peca['origem'];
				$multiplo              = $ln_peca['multiplo'];
				
				$xml_peca .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" qtde_pedido=\"\" multiplo=\"$multiplo\"/>\n";
				flush();
			}
		}else{
			$xml_peca .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" qtde_pedido=\"\" multiplo=\"$multiplo\"/>\n";
			flush();
		}
		
		$xml_peca .= "</ROWDATA>\n";
		$xml_peca .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/peca.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_peca);
		fclose($arquivo);
	

		##################################################################################################################
		// LISTA BÁSICA  //trim(replace(tbl_peca.descricao,'\"','\'\''))  AS descricao  ,
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                      AS fabrica    ,
						trim(tbl_produto.produto)                      AS produto    ,
						trim(tbl_peca.peca)                            AS peca       ,
						trim(tbl_peca.referencia)                      AS referencia ,
						trim(replace(tbl_peca.descricao,'\"',' '))     AS descricao  ,
						trim(tbl_peca.origem)                          AS origem     ,
						trim(tbl_lista_basica.type)                    AS type       ,
						trim(tbl_lista_basica.posicao)                 AS posicao    ,
						trim(tbl_lista_basica.qtde)                    AS qtde
				FROM    tbl_fabrica
				JOIN    tbl_lista_basica ON tbl_lista_basica.fabrica = tbl_fabrica.fabrica
				JOIN    tbl_produto      ON tbl_produto.produto      = tbl_lista_basica.produto
				JOIN    tbl_linha        ON tbl_linha.linha          = tbl_produto.linha
										AND tbl_linha.fabrica        = tbl_fabrica.fabrica
				JOIN    tbl_peca         ON tbl_peca.peca            = tbl_lista_basica.peca
										AND tbl_peca.fabrica         = tbl_fabrica.fabrica
				WHERE   tbl_peca.ativo    IS TRUE
				AND     tbl_produto.ativo IS TRUE ";
		if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_produto.produto, tbl_peca.peca;";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_lbm  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml_lbm .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_lbm .= "<METADATA>\n";
		$xml_lbm .= "<FIELDS>\n";
		$xml_lbm .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"referencia\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"origem\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"type\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"posicao\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"qtde\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_lbm .= "<FIELD attrname=\"qtde_pedido\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_lbm .= "</FIELDS>\n";
		$xml_lbm .= "</METADATA>\n";
		$xml_lbm .= "<ROWDATA>\n";
		
		$fabrica     = "";
		$produto     = "";
		$peca        = "";
		$referencia  = "";
		$descricao   = "";
		$origem      = "";
		$type        = "";
		$posicao     = "";
		$qtde        = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_lbm = pg_fetch_array($res)){
				$contador++;
				$fabrica     = $ln_lbm['fabrica'];
				$produto     = $ln_lbm['produto'];
				$peca        = $ln_lbm['peca'];
				$referencia  = $ln_lbm['referencia'];
				$descricao   = $ln_lbm['descricao'];
				$origem      = $ln_lbm['origem'];
				$type        = $ln_lbm['type'];
				$posicao     = $ln_lbm['posicao'];
				$qtde        = $ln_lbm['qtde'];
				
				flush();
				$xml_lbm .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" origem=\"$origem\" type=\"$type\" posicao=\"$posicao\" qtde=\"$qtde\" qtde_pedido=\"\"/>\n";
			}
		}else{
			$xml_lbm .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" origem=\"$origem\" type=\"$type\" posicao=\"$posicao\" qtde=\"$qtde\" qtde_pedido=\"\"/>\n";
			flush();
		}
		
		$xml_lbm .= "</ROWDATA>\n";
		$xml_lbm .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/lbm.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_lbm);
		fclose($arquivo);


		##################################################################################################################
		// DEFEITO RECLAMADO
		##################################################################################################################		
		// cabeçalho
		$xml_defeito_reclamado  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_defeito_reclamado .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_defeito_reclamado .= "<METADATA>\n";
		$xml_defeito_reclamado .= "<FIELDS>\n";
		$xml_defeito_reclamado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito_reclamado .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito_reclamado .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito_reclamado .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito_reclamado .= "<FIELD attrname=\"defeito_reclamado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito_reclamado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_defeito_reclamado .= "</FIELDS>\n";
		$xml_defeito_reclamado .= "</METADATA>\n";
		$xml_defeito_reclamado .= "<ROWDATA>\n";
		
		if ($fabricante == 19) {
			$sql = "SELECT  trim(tbl_defeito_reclamado.fabrica)                        AS fabrica          ,
							trim(tbl_defeito_reclamado.linha)                          AS linha            ,
							trim(tbl_familia_defeito_reclamado.familia)                        AS familia          ,
							trim(tbl_defeito_reclamado.defeito_reclamado)              AS defeito_reclamado,
							trim(replace(tbl_defeito_reclamado.descricao,'\"','\'\'')) AS descricao
					FROM tbl_defeito_reclamado
					JOIN tbl_familia_defeito_reclamado on tbl_familia_defeito_reclamado.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
					WHERE tbl_defeito_reclamado.fabrica = $fabricante;";
			$res = pg_exec($con,$sql);

			$gerado = "nao";
			if (pg_numrows($res) > 0) {
				$fabrica           = "";
				$produto           = "";
				$linha             = "";
				$familia           = "";
				$defeito_reclamado = "";
				$descricao         = "";
				
				$contador = 0;
				while ($ln_defeito_reclamado = pg_fetch_array($res)){
					$contador++;
					$fabrica           = trim($ln_defeito_reclamado['fabrica']);
					$linha             = trim($ln_defeito_reclamado['linha']);
					$familia           = trim($ln_defeito_reclamado['familia']);
					$defeito_reclamado = trim($ln_defeito_reclamado['defeito_reclamado']);
					$descricao         = trim($ln_defeito_reclamado['descricao']);
					
					$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
					flush();
				}
				$gerado = "sim";
			}
		}else{
			### DEFEITOS POR FAMILIA
			$sql = "SELECT      trim(tbl_fabrica.fabrica)                                  AS fabrica          ,
								trim(tbl_defeito_reclamado.linha)                          AS linha            ,
								trim(tbl_familia.familia)                                  AS familia          ,
								trim(tbl_defeito_reclamado.defeito_reclamado)              AS defeito_reclamado,
								trim(replace(tbl_defeito_reclamado.descricao,'\"','\'\'')) AS descricao
					FROM        tbl_fabrica
					JOIN        tbl_familia              ON tbl_familia.fabrica           = tbl_fabrica.fabrica
					JOIN        tbl_defeito_reclamado    ON tbl_defeito_reclamado.familia = tbl_familia.familia
					WHERE       tbl_fabrica.fabrica = $fabrica
					ORDER BY    tbl_defeito_reclamado.defeito_reclamado, lpad(tbl_defeito_reclamado.descricao,50,0);";
			$res = pg_exec($con,$sql);
		
			if (pg_numrows($res) > 0) {
				$fabrica           = "";
				$produto           = "";
				$linha             = "";
				$familia           = "";
				$defeito_reclamado = "";
				$descricao         = "";
				
				$contador = 0;
				while ($ln_defeito_reclamado = pg_fetch_array($res)){
					$contador++;
					$fabrica           = trim($ln_defeito_reclamado['fabrica']);
					$linha             = trim($ln_defeito_reclamado['linha']);
					$familia           = trim($ln_defeito_reclamado['familia']);
					$defeito_reclamado = trim($ln_defeito_reclamado['defeito_reclamado']);
					$descricao         = trim($ln_defeito_reclamado['descricao']);
					
					$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
					flush();
				}
				$gerado = "sim";
			}
		
			if ($gerado == "nao") {
				### DEFEITOS POR LINHA
				$sql = "SELECT      trim(tbl_fabrica.fabrica)                                  AS fabrica          ,
									trim(tbl_defeito_reclamado.linha)                          AS linha            ,
									trim(tbl_familia.familia)                                  AS familia          ,
									trim(tbl_defeito_reclamado.defeito_reclamado)              AS defeito_reclamado,
									trim(replace(tbl_defeito_reclamado.descricao,'\"','\'\'')) AS descricao
						FROM        tbl_fabrica
						JOIN        tbl_linha                ON tbl_linha.fabrica             = tbl_fabrica.fabrica
						JOIN        tbl_defeito_reclamado    ON tbl_defeito_reclamado.linha   = tbl_linha.linha
						WHERE       tbl_fabrica.fabrica = $fabrica
						ORDER BY    tbl_defeito_reclamado.defeito_reclamado, lpad(tbl_defeito_reclamado.descricao,50,0);";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows($res) > 0) {
					$fabrica           = "";
					$produto           = "";
					$linha             = "";
					$familia           = "";
					$defeito_reclamado = "";
					$descricao         = "";
					
					$contador = 0;
					while ($ln_defeito_reclamado = pg_fetch_array($res)){
						$contador++;
						$fabrica           = trim($ln_defeito_reclamado['fabrica']);
						$linha             = trim($ln_defeito_reclamado['linha']);
						$familia           = trim($ln_defeito_reclamado['familia']);
						$defeito_reclamado = trim($ln_defeito_reclamado['defeito_reclamado']);
						$descricao         = trim($ln_defeito_reclamado['descricao']);
						
						$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
						flush();
					}
					$gerado = "sim";
				}
			}
		}


		if ($gerado == "nao") {
			$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
			flush();
		}

		$xml_defeito_reclamado .= "</ROWDATA>\n";
		$xml_defeito_reclamado .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/defrecl.xml";
		$arquivo = fopen($file, "w+");
		
		fwrite($arquivo, $xml_defeito_reclamado);
		fclose($arquivo);


		##################################################################################################################
		// DEFEITO
		##################################################################################################################
		$sql = "SELECT      trim(tbl_fabrica.fabrica)                         AS fabrica  ,
							trim(tbl_defeito.defeito)                         AS defeito  ,
							trim(tbl_defeito.codigo_defeito)                  AS codigo   ,
							trim(replace(tbl_defeito.descricao,'\"','\'\''))  AS descricao
				FROM        tbl_fabrica
				JOIN        tbl_defeito ON tbl_defeito.fabrica = tbl_fabrica.fabrica ";
		$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_defeito.descricao,30,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_defeito  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_defeito .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_defeito .= "<METADATA>\n";
		$xml_defeito .= "<FIELDS>\n";
		$xml_defeito .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito .= "<FIELD attrname=\"defeito\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_defeito .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
		$xml_defeito .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
		$xml_defeito .= "</FIELDS>\n";
		$xml_defeito .= "</METADATA>\n";
		$xml_defeito .= "<ROWDATA>\n";
		
		$fabrica   = "";
		$defeito   = "";
		$codigo    = "";
		$descricao = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_defeito = pg_fetch_array($res)){
				$contador++;
				$fabrica   = trim($ln_defeito['fabrica']);
				$defeito   = trim($ln_defeito['defeito']);
				$codigo    = trim($ln_defeito['codigo']);
				$descricao = trim($ln_defeito['descricao']);
				
				$xml_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" defeito=\"$defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
				flush();
			}
		}else{
			$xml_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" defeito=\"$defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
		
		$xml_defeito .= "</ROWDATA>\n";
		$xml_defeito .= "</DATAPACKET>\n";
		
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/defeito.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_defeito);
		fclose($arquivo);


		##################################################################################################################
		// DEFEITO CONSTATADO
		##################################################################################################################
		$status = "f";
			
		$contador = 0;
		if (pg_result($resX,$x,defeito_constatado_por_familia) == "t") {
			$sql = "SELECT      trim(tbl_fabrica.fabrica)                                   AS fabrica           ,
								trim(tbl_produto.produto)                                   AS produto           ,
								''                                                          AS linha             ,
								trim(tbl_familia.familia)                                   AS familia           ,
								trim(tbl_defeito_constatado.defeito_constatado)             AS defeito_constatado,
								trim(tbl_defeito_constatado.codigo)                         AS codigo            ,
								trim(replace(tbl_defeito_constatado.descricao,'\"','\'\'')) AS descricao
					FROM        tbl_fabrica
					JOIN        tbl_familia                      ON tbl_familia.fabrica                       = tbl_fabrica.fabrica
					JOIN        tbl_familia_defeito_constatado   ON tbl_familia_defeito_constatado.familia    = tbl_familia.familia
					JOIN        tbl_produto                      ON tbl_produto.familia                       = tbl_familia.familia
					JOIN        tbl_defeito_constatado           ON (tbl_defeito_constatado.defeito_constatado = tbl_familia_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.defeito_constatado = 10823 AND tbl_produto.linha=261) OR
																	(tbl_defeito_constatado.defeito_constatado = tbl_familia_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.defeito_constatado <> 10823) ";
			if (strlen(pg_result($resX,$x,fabrica)) > 0) 
				$sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
			$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
			//hd chamado 3470, defeito 10823 só para linha metais apesar de estar amarrado a todas
			
			$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_produto.produto, tbl_familia.familia, tbl_defeito_constatado.defeito_constatado, lpad(tbl_defeito_constatado.descricao,150,0);";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				if ($status == "f") {
					// cabeçalho
					$xml_defeito_constatado  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
					$xml_defeito_constatado .= "<DATAPACKET Version=\"2.0\">\n";
					$xml_defeito_constatado .= "<METADATA>\n";
					$xml_defeito_constatado .= "<FIELDS>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"defeito_constatado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
					$xml_defeito_constatado .= "</FIELDS>\n";
					$xml_defeito_constatado .= "</METADATA>\n";
					$xml_defeito_constatado .= "<ROWDATA>\n";
					
					$status = "t";
				}
				
				while ($ln_defeito_constatado = pg_fetch_array($res)){
					$contador++;
					$fabrica            = trim($ln_defeito_constatado['fabrica']);
					$produto            = trim($ln_defeito_constatado['produto']);
					$linha              = trim($ln_defeito_constatado['linha']);
					$familia            = trim($ln_defeito_constatado['familia']);
					$defeito_constatado = trim($ln_defeito_constatado['defeito_constatado']);
					$codigo             = trim($ln_defeito_constatado['codigo']);
					$descricao          = trim($ln_defeito_constatado['descricao']);
					
					$xml_defeito_constatado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_constatado=\"$defeito_constatado\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
					flush();
				}
			}
		}else{
			$sql = "SELECT      trim(tbl_fabrica.fabrica)                                   AS fabrica           ,
								''                                                          AS produto           ,
								''                                                          AS linha             ,
								''                                                          AS familia           ,
								trim(tbl_defeito_constatado.defeito_constatado)             AS defeito_constatado,
								trim(tbl_defeito_constatado.codigo)                         AS codigo            ,
								trim(replace(tbl_defeito_constatado.descricao,'\"','\'\'')) AS descricao
					FROM        tbl_fabrica
					JOIN        tbl_defeito_constatado   ON tbl_defeito_constatado.fabrica = tbl_fabrica.fabrica ";
			if (strlen(pg_result($resX,$x,fabrica)) > 0) 
				$sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
			$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_defeito_constatado.defeito_constatado, lpad(tbl_defeito_constatado.descricao,150,0);";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				if ($status == "f") {
					// cabeçalho
					$xml_defeito_constatado  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
					$xml_defeito_constatado .= "<DATAPACKET Version=\"2.0\">\n";
					$xml_defeito_constatado .= "<METADATA>\n";
					$xml_defeito_constatado .= "<FIELDS>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"defeito_constatado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
					$xml_defeito_constatado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
					$xml_defeito_constatado .= "</FIELDS>\n";
					$xml_defeito_constatado .= "</METADATA>\n";
					$xml_defeito_constatado .= "<ROWDATA>\n";
					
					$status = "t";
				}
				
				while ($ln_defeito_constatado = pg_fetch_array($res)){
					$contador++;
					$fabrica            = trim($ln_defeito_constatado['fabrica']);
					$produto            = trim($ln_defeito_constatado['produto']);
					$linha              = trim($ln_defeito_constatado['linha']);
					$familia            = trim($ln_defeito_constatado['familia']);
					$defeito_constatado = trim($ln_defeito_constatado['defeito_constatado']);
					$codigo             = trim($ln_defeito_constatado['codigo']);
					$descricao          = trim($ln_defeito_constatado['descricao']);
					
					$xml_defeito_constatado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_constatado=\"$defeito_constatado\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
					flush();
				}
			}
		}

		if ($status == "t") {
			$xml_defeito_constatado .= "</ROWDATA>\n";
			$xml_defeito_constatado .= "</DATAPACKET>\n";
			
			
			// GERA XML DO ARQUIVO
			$file    = "xml/$fabricante_nome/defcons.xml";
			$arquivo = fopen($file, "w+");
			fwrite($arquivo, $xml_defeito_constatado);
			fclose($arquivo);
		}


		##################################################################################################################
		// PEÇA DE - PARA
		##################################################################################################################
		$sql = "SELECT  tbl_depara.fabrica  ,
						tbl_depara.peca_de  ,
						tbl_depara.peca_para
				FROM    tbl_depara
				JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_depara.fabrica ";
		if (strlen($fabricante) > 0) $sql .= "AND tbl_depara.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_depara.fabrica, tbl_depara.peca_de;";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_depara  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_depara .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_depara .= "<METADATA>\n";
		$xml_depara .= "<FIELDS>\n";
		$xml_depara .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_depara .= "<FIELD attrname=\"peca_de\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_depara .= "<FIELD attrname=\"peca_para\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_depara .= "</FIELDS>\n";
		$xml_depara .= "</METADATA>\n";
		$xml_depara .= "<ROWDATA>\n";
		
		$fabrica   = "";
		$peca_de   = "";
		$peca_para = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			
			while ($ln_depara = pg_fetch_array($res)){
				$contador++;
				$fabrica    = trim($ln_depara['fabrica']);
				$peca_de    = trim($ln_depara['peca_de']);
				$peca_para  = trim($ln_depara['peca_para']);
				
				$xml_depara .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca_de=\"$peca_de\" peca_para=\"$peca_para\"/>\n";
				flush();
			}
		}else{
			$xml_depara .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca_de=\"$peca_de\" peca_para=\"$peca_para\"/>\n";
			flush();
		}
		
		$xml_depara .= "</ROWDATA>\n";
		$xml_depara .= "</DATAPACKET>\n";
		
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/depara.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_depara);
		fclose($arquivo);


		##################################################################################################################
		// CAUSA DO DEFEITO
		##################################################################################################################
		$sql = "SELECT      trim(tbl_fabrica.fabrica)                              AS fabrica      ,
							trim(tbl_causa_defeito.causa_defeito)                  AS causa_defeito,
							trim(tbl_causa_defeito.codigo)                         AS codigo       ,
							trim(replace(tbl_causa_defeito.descricao,'\"','\'\'')) AS descricao
				FROM        tbl_fabrica
				JOIN        tbl_causa_defeito ON tbl_causa_defeito.fabrica = tbl_fabrica.fabrica ";
		$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_causa_defeito.descricao,30,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_causa_defeito  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_causa_defeito .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_causa_defeito .= "<METADATA>\n";
		$xml_causa_defeito .= "<FIELDS>\n";
		$xml_causa_defeito .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_causa_defeito .= "<FIELD attrname=\"causa_defeito\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_causa_defeito .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
		$xml_causa_defeito .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
		$xml_causa_defeito .= "</FIELDS>\n";
		$xml_causa_defeito .= "</METADATA>\n";
		$xml_causa_defeito .= "<ROWDATA>\n";
		
		$fabrica       = "";
		$causa_defeito = "";
		$codigo        = "";
		$descricao     = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_causa_defeito = pg_fetch_array($res)){
				$contador++;
				$fabrica       = trim($ln_causa_defeito['fabrica']);
				$causa_defeito = trim($ln_causa_defeito['causa_defeito']);
				$codigo        = trim($ln_causa_defeito['codigo']);
				$descricao     = trim($ln_causa_defeito['descricao']);
				
				$xml_causa_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" causa_defeito=\"$causa_defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
				flush();
			}
		}else{
			$xml_causa_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" causa_defeito=\"$causa_defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
		
		$xml_causa_defeito .= "</ROWDATA>\n";
		$xml_causa_defeito .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/causadef.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_causa_defeito);
		fclose($arquivo);

	
		##################################################################################################################
		// TRANSPORTADORAS
		##################################################################################################################
		$sql = "SELECT  tbl_transportadora.transportadora        ,
						tbl_transportadora_fabrica.fabrica       ,
						tbl_transportadora_fabrica.codigo_interno,
						tbl_transportadora.nome                  ,
						tbl_transportadora.cnpj                  ,
						tbl_transportadora.fantasia
				FROM    tbl_transportadora
				JOIN    tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
				WHERE   tbl_transportadora_fabrica.ativo IS TRUE ";
		if (strlen($fabricante) > 0) $sql .= "AND tbl_transportadora_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_transportadora_fabrica.fabrica, lpad(tbl_transportadora_fabrica.codigo_interno,10,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_transportadora  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_transportadora .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_transportadora .= "<METADATA>\n";
		$xml_transportadora .= "<FIELDS>\n";
		$xml_transportadora .= "<FIELD attrname=\"transportadora\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_transportadora .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_transportadora .= "<FIELD attrname=\"codigo_interno\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_transportadora .= "<FIELD attrname=\"nome\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_transportadora .= "<FIELD attrname=\"cnpj\" fieldtype=\"string\" WIDTH=\"14\"/>\n";
		$xml_transportadora .= "<FIELD attrname=\"fantasia\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
		$xml_transportadora .= "</FIELDS>\n";
		$xml_transportadora .= "</METADATA>\n";
		$xml_transportadora .= "<ROWDATA>\n";
		
		$transportadora    = "";
		$fabrica           = "";
		$codigo_interno    = "";
		$nome              = "";
		$cnpj_transp       = "";
		$fantasia          = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			
			while ($ln_transportadora = pg_fetch_array($res)){
				$contador++;
				$transportadora    = trim($ln_transportadora['transportadora']);
				$fabrica           = trim($ln_transportadora['fabrica']);
				$codigo_interno    = trim($ln_transportadora['codigo_interno']);
				$nome              = trim($ln_transportadora['nome']);
				$cnpj_transp       = trim($ln_transportadora['cnpj']);
				$fantasia          = trim($ln_transportadora['fantasia']);
				
				$xml_transportadora .= "<ROW RowState=\"4\" transportadora=\"$transportadora\" fabrica=\"$fabrica\" codigo_interno=\"$codigo_interno\" nome=\"$nome\" cnpj=\"$cnpj_transp\" fantasia=\"$fantasia\"/>\n";
				flush();
			}
		}else{
			$xml_transportadora .= "<ROW RowState=\"4\" transportadora=\"$transportadora\" fabrica=\"$fabrica\" codigo_interno=\"$codigo_interno\" nome=\"$nome\" cnpj=\"$cnpj_transp\" fantasia=\"$fantasia\"/>\n";
			flush();
		}
		
		$xml_transportadora .= "</ROWDATA>\n";
		$xml_transportadora .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/transp.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_transportadora);
		fclose($arquivo);



		##################################################################################################################
		// SERVIÇO REALIZADO
		##################################################################################################################
		$sql = "SELECT      trim(tbl_fabrica.fabrica)                                   AS fabrica            ,
							trim(tbl_servico_realizado.servico_realizado)               AS servico_realizado  ,
							trim(replace(tbl_servico_realizado.descricao,'\"','\'\''))  AS descricao
				FROM        tbl_fabrica
				JOIN        tbl_servico_realizado ON tbl_servico_realizado.fabrica = tbl_fabrica.fabrica
				and         tbl_servico_realizado.ativo IS TRUE ";
		$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_servico_realizado.descricao,50,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_servico_realizado  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_servico_realizado .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_servico_realizado .= "<METADATA>\n";
		$xml_servico_realizado .= "<FIELDS>\n";
		$xml_servico_realizado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_servico_realizado .= "<FIELD attrname=\"servico_realizado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_servico_realizado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_servico_realizado .= "</FIELDS>\n";
		$xml_servico_realizado .= "</METADATA>\n";
		$xml_servico_realizado .= "<ROWDATA>\n";
		
		$fabrica           = "";
		$servico_realizado = "";
		$descricao         = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_servico_realizado = pg_fetch_array($res)){
				$contador++;
				$fabrica           = trim($ln_servico_realizado['fabrica']);
				$servico_realizado = trim($ln_servico_realizado['servico_realizado']);
				$descricao         = trim($ln_servico_realizado['descricao']);
				
				$xml_servico_realizado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" servico_realizado=\"$servico_realizado\" descricao=\"$descricao\"/>\n";
				flush();
			}
		}else{
			$xml_servico_realizado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" servico_realizado=\"$servico_realizado\" descricao=\"$descricao\"/>\n";
			flush();
		}
		
		$xml_servico_realizado .= "</ROWDATA>\n";
		$xml_servico_realizado .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/servreal.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_servico_realizado);
		fclose($arquivo);


		##################################################################################################################
		// PEÇA FORA DE LINHA
		##################################################################################################################
		$sql = "SELECT  tbl_peca_fora_linha.fabrica,
						tbl_peca_fora_linha.peca
				FROM    tbl_peca_fora_linha
				JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca_fora_linha.fabrica ";
		if (strlen($fabricante) > 0) $sql .= "AND tbl_peca_fora_linha.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_peca_fora_linha.fabrica, tbl_peca_fora_linha.peca;";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_peca_fora_linha  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml_peca_fora_linha .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_peca_fora_linha .= "<METADATA>\n";
		$xml_peca_fora_linha .= "<FIELDS>\n";
		$xml_peca_fora_linha .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_peca_fora_linha .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_peca_fora_linha .= "</FIELDS>\n";
		$xml_peca_fora_linha .= "</METADATA>\n";
		$xml_peca_fora_linha .= "<ROWDATA>\n";
		
		$fabrica = "";
		$peca    = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			
			while ($ln_peca_fora_linha = pg_fetch_array($res)){
				$contador++;
				$fabrica = trim($ln_peca_fora_linha['fabrica']);
				$peca    = trim($ln_peca_fora_linha['peca']);
				
				$xml_peca_fora_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\"/>\n";
				flush();
			}
		}else{
			$xml_peca_fora_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\"/>\n";
			flush();
		}
		
		$xml_peca_fora_linha .= "</ROWDATA>\n";
		$xml_peca_fora_linha .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/obsoleta.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_peca_fora_linha);
		fclose($arquivo);


		##################################################################################################################
		// FAMILIA
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                         AS fabrica    ,
						trim(tbl_familia.familia)                         AS familia    ,
						trim(tbl_familia.codigo_familia)                  AS codigo     ,
						trim(replace(tbl_familia.descricao,'\"','\'\''))  AS descricao
				FROM    tbl_familia
				JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_familia.fabrica ";
		if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_familia.codigo_familia,10,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_familia  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml_familia .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_familia .= "<METADATA>\n";
		$xml_familia .= "<FIELDS>\n";
		$xml_familia .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_familia .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_familia .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_familia .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_familia .= "</FIELDS>\n";
		$xml_familia .= "</METADATA>\n";
		$xml_familia .= "<ROWDATA>\n";
		
		$fabrica     = "";
		$familia     = "";
		$codigo      = "";
		$descricao   = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_familia = pg_fetch_array($res)){
				$contador++;
				$fabrica     = $ln_familia['fabrica'];
				$familia     = $ln_familia['familia'];
				$codigo      = $ln_familia['codigo'];
				$descricao   = $ln_familia['descricao'];
				
				$xml_familia .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" familia=\"$familia\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
				flush();
			}
		}else{
			$xml_familia .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" familia=\"$familia\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
		
		$xml_familia .= "</ROWDATA>\n";
		$xml_familia .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/familia.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_familia);
		fclose($arquivo);


		##################################################################################################################
		// LINHA
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                  AS fabrica    ,
						trim(tbl_linha.linha)                      AS linha      ,
						trim(tbl_linha.codigo_linha)               AS codigo     ,
						trim(replace(tbl_linha.nome,'\"','\'\''))  AS descricao
				FROM    tbl_linha
				JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica ";
		if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_linha.codigo_linha,10,0);";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_linha = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml_linha .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_linha .= "<METADATA>\n";
		$xml_linha .= "<FIELDS>\n";
		$xml_linha .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_linha .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_linha .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_linha .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
		$xml_linha .= "</FIELDS>\n";
		$xml_linha .= "</METADATA>\n";
		$xml_linha .= "<ROWDATA>\n";
		
		$fabrica     = "";
		$linha       = "";
		$codigo      = "";
		$descricao   = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_linha = pg_fetch_array($res)){
				$contador++;
				$fabrica     = $ln_linha['fabrica'];
				$linha       = $ln_linha['linha'];
				$codigo      = $ln_linha['codigo'];
				$descricao   = $ln_linha['descricao'];
				
				$xml_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" linha=\"$linha\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
				flush();
			}
		}else{
			$xml_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" linha=\"$linha\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
		
		$xml_linha .= "</ROWDATA>\n";
		$xml_linha .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/linha.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_linha);
		fclose($arquivo);


		##################################################################################################################
		// TIPO ATENDIMENTO
		##################################################################################################################
		$sql = "SELECT  trim(tbl_fabrica.fabrica)                    AS fabrica         ,
						trim(tbl_tipo_atendimento.tipo_atendimento)  AS tipo_atendimento,
						trim(tbl_tipo_atendimento.descricao)         AS descricao       
				FROM    tbl_tipo_atendimento
				JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_tipo_atendimento.fabrica ";
		if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
		$sql .= "ORDER BY tbl_tipo_atendimento.tipo_atendimento;";
		$res = pg_exec($con,$sql);
		
		// cabeçalho
		$xml_tatendimento  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml_tatendimento .= "<DATAPACKET Version=\"2.0\">\n";
		$xml_tatendimento .= "<METADATA>\n";
		$xml_tatendimento .= "<FIELDS>\n";
		$xml_tatendimento .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
		$xml_tatendimento .= "<FIELD attrname=\"tipo_atendimento\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
		$xml_tatendimento .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
		$xml_tatendimento .= "</FIELDS>\n";
		$xml_tatendimento .= "</METADATA>\n";
		$xml_tatendimento .= "<ROWDATA>\n";
		
		$fabrica          = "";
		$tipo_atendimento = "";
		$descricao        = "";
		
		if (pg_numrows($res) > 0) {
			$contador = 0;
			while ($ln_tipo_atendimento = pg_fetch_array($res)){
				$contador++;
				$fabrica          = $ln_tipo_atendimento['fabrica'];
				$tipo_atendimento = $ln_tipo_atendimento['tipo_atendimento'];
				$descricao        = $ln_tipo_atendimento['descricao'];
				
				$xml_tatendimento .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" tipo_atendimento=\"$tipo_atendimento\" descricao=\"$descricao\"/>\n";
				flush();
			}
		}else{
			$xml_tatendimento .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" tipo_atendimento=\"$tipo_atendimento\" descricao=\"$descricao\"/>\n";
			flush();
		}
		
		$xml_tatendimento .= "</ROWDATA>\n";
		$xml_tatendimento .= "</DATAPACKET>\n";
		
		// GERA XML DO ARQUIVO
		$file    = "xml/$fabricante_nome/tipoatendimento.xml";
		$arquivo = fopen($file, "w+");
		fwrite($arquivo, $xml_tatendimento);
		fclose($arquivo);
	}
}

//DATA DA GERAÇÃO
$file    = "xml/geracao.txt";
$arquivo = fopen($file, "w+");
$hj = date("j-m-Y  G:i");
fwrite($arquivo, $hj);
fclose($arquivo);

?>