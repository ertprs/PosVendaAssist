<style language='text/css'>
#menuver {
	width: 180px;
	padding: 0; 
	margin: 0;
	font: 10px Verdana, sans-serif;
	background: #dadfe8; 
	/*border-top: 3px solid #B5CDE8; */
	border-bottom: 3px solid #242946;
	font-weight: normal;
}
SPAN.expande {
	line-height:inherit;
	font-size:1.3em;
	font-weight: bold;
    font-family: Lucida Console,Courier New,courier;
	cursor: pointer;
}
</style>
<script type="text/javascript" language="JavaScript">
	function ToggleView(tglchar, lista) {
		var estado = lista.style.display;
		if (estado=='none') {
			lista.style.display = 'block';
			tglchar.innerHTML = ' &mdash; ';
		}else{
			lista.style.display = 'none';
			tglchar.innerHTML = ' + ';
		}
	}
</script>

<?

if ($login_fabrica == 3) { //HD 399313 - Mostrar todas as linha para Britania
	$cond_1 = " AND tbl_linha.linha IN (SELECT linha FROM tbl_posto_fabrica JOIN tbl_posto_linha USING (posto) WHERE tbl_posto_linha.posto = $login_posto AND fabrica = $login_fabrica) ";
}

echo "<ul id=\"menuver\">\n";
echo "<img src='imagens/top_lojas.gif'>";
$sql = "SELECT	DISTINCT 
			tbl_linha.linha, 
			tbl_linha.nome as descricao 
		FROM 
			tbl_linha 
		JOIN 
			tbl_produto on tbl_produto.linha = tbl_linha.linha
		WHERE 
			tbl_linha.fabrica = $login_fabrica 
			AND tbl_linha.ativo   IS TRUE
			AND tbl_produto.ativo IS TRUE
		ORDER BY 
			tbl_linha.nome";

	if (((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10) OR $login_fabrica == 1){
		$sql = "SELECT	DISTINCT 
					tbl_peca.linha_peca AS linha,
					tbl_linha.nome      AS descricao
			FROM 
				tbl_peca
			JOIN 
				tbl_linha ON tbl_linha.linha = tbl_peca.linha_peca
			WHERE 
				tbl_peca.fabrica = $login_fabrica 
				AND tbl_linha.ativo        IS TRUE
				AND tbl_peca.ativo         IS TRUE
				AND tbl_peca.promocao_site IS TRUE
			ORDER BY 
				tbl_linha.nome";
	}
	
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$linha           = pg_result($res,$i,linha);
			$linha_descricao = maiusculo(strtoupper (pg_result($res,$i,descricao)));
			echo "<LI>\n\t";
			echo "<SPAN class='expande' ".
				"onClick=\"javascript:ToggleView(this,document.getElementById('linha_$i'));\">".
				" + </SPAN>\n\t";
			echo "<a href='lv_completa.php?categoria=$linha&categoria_tipo=linha'>";
			if($linha=='200')     echo "<img src='imagens/loja_black.gif' >";
			elseif($linha=='198') echo "<img src='imagens/loja_dewalt.jpg' >";
			else                  echo "<b>$linha_descricao</b>";
			echo "</a>\n";

			if($login_fabrica == 85){

				$xsql = "	SELECT DISTINCT f.familia, f.descricao 
							FROM tbl_familia f 
							INNER JOIN tbl_produto p ON p.familia = f.familia 
							INNER JOIN tbl_lista_basica lb ON p.produto = lb.produto 
							RIGHT JOIN tbl_peca pc ON lb.peca = pc.peca 							
							WHERE f.fabrica = $login_fabrica AND 
							pc.promocao_site is true AND pc.fabrica = $login_fabrica AND
							p.ativo is true AND p.fabrica_i = $login_fabrica AND
							f.ativo is true AND f.fabrica = $login_fabrica AND
							pc.ativo is true AND p.linha = $linha AND
							lb.ativo is true AND lb.fabrica = $login_fabrica";

							
			}else{
				$xsql = "SELECT distinct tbl_familia.familia, 
						tbl_familia.descricao 
					FROM tbl_familia
					JOIN tbl_produto on tbl_produto.familia = tbl_familia.familia
					WHERE tbl_familia.fabrica = $login_fabrica
					AND   tbl_produto.linha   = $linha
					AND   tbl_familia.ativo IS TRUE
					AND   tbl_produto.ativo IS TRUE
					ORDER BY descricao";	
			}			

			if (((strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10 ) OR $login_fabrica == 1 ){				
				$xsql = "SELECT DISTINCT tbl_familia.familia, 
										tbl_familia.descricao 
					FROM tbl_familia
					JOIN tbl_peca on tbl_peca.familia_peca = tbl_familia.familia
					WHERE tbl_peca.linha_peca = $linha
					AND   tbl_peca.promocao_site IS TRUE
					AND   tbl_peca.ativo IS TRUE
					ORDER BY tbl_familia.descricao";			
			}
			
			$xres = pg_exec($con,$xsql);			
			if(pg_numrows($xres)>0){
				echo "<UL id='linha_$i' style='display: none'>\n";
				for($y=0;pg_numrows($xres)>$y;$y++){
					$familia           = pg_result($xres,$y,familia);
					$familia_descricao = ucfirst  (pg_result($xres,$y,descricao));					
					echo "<li>";
					/*echo " - <a href='loja_completa_teste.php?categoria=$familia&categoria_tipo=familia'>$familia_descricao</a>";*/
					echo  "• <a href='lv_completa.php?categoria=$familia&categoria_tipo=familia' ".
						  "	   style='font-size:9px;'>$familia_descricao</a>";

					if ( (strlen($login_unico)>0 OR strlen($login_simples)>0) AND $login_fabrica==10 OR $login_fabrica == 3){
						#nao imprimi para a Telecontrol
					}else{						
						echo "<h3 style='display:inline;' class='head'>&nbsp;</h3>";
						echo "<ul>";
							// if($login_fabrica == 85){
							// 	$wsql = "SELECT distinct p.peca, pr.produto, pr.descricao from tbl_produto pr 
							// 				inner join tbl_lista_basica lb on pr.produto = lb.produto 
							// 				inner join tbl_peca p on p.peca = lb.peca 
							// 				where p.promocao_site is true
							// 				and pr.familia = $familia
							// 				and pr.linha = $linha
							// 				and pr.ativo is true
							// 				";
											
							//}else{
							if($login_fabrica != 85){
								$wsql = "SELECT produto, descricao from tbl_produto 
											where linha = $linha 
											and familia = $familia 
											and ativo is true 
											order by descricao";

								$wres = pg_exec($con,$wsql);
								if(pg_numrows($wres)>0){
									for($w=0;pg_numrows($wres)>$w;$w++){
										$produto           = pg_result($wres,$w,produto);
										$produto_descricao = ucfirst  (pg_result($wres,$w,descricao));
										echo "<li> &nbsp;&nbsp;&nbsp;- <a href='lv_completa.php?categoria=$produto&categoria_tipo=produto'>$produto_descricao</a>";
										echo "</li>";
									}
								}											
							}
								
							//}							
							
						
						echo "</ul>";
					}
					echo "</li>\n";

				}
				echo "</ul>\n";
			}
			echo "</li>\n";


		}
	}

echo "</ul>\n";

?>