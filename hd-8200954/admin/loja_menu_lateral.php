
<?
echo "<a href='loja_liquidacao.php'>LIQUIDAÇÃO</a> \n ";
echo "<ul id=\"menuver\"> \n ";
	$sql = "SELECT	distinct tbl_linha.linha, 
					upper(tbl_linha.nome) as descricao 
			from tbl_linha 
			join tbl_produto on tbl_produto.linha = tbl_linha.linha
			where tbl_linha.fabrica = $login_fabrica 
			and tbl_produto.ativo is true
			order by descricao;";
		
	$res = pg_exec($con,$sql);
//	echo $sql;
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$linha           = pg_result($res,$i,linha);
			$linha_descricao = pg_result($res,$i,descricao);
			echo "<li>";
			echo "<b>$linha_descricao</b> \n ";

			$xsql = "SELECT distinct tbl_familia.familia, 
							tbl_familia.descricao 
					FROM tbl_familia
					JOIN tbl_produto on tbl_produto.familia = tbl_familia.familia
					WHERE tbl_familia.fabrica = $login_fabrica
					AND   tbl_produto.linha   = $linha
					and tbl_produto.ativo is true
					order by descricao";
//	echo $xsql;
			$xres = pg_exec($con,$xsql);
			if(pg_numrows($xres)>0){
				echo "<ul> \n ";
				for($y=0;pg_numrows($xres)>$y;$y++){
					$familia           = pg_result($xres,$y,familia);
					$familia_descricao = ucfirst  (pg_result($xres,$y,descricao));
					echo "<li> - <a href='loja_completa.php?categoria=$familia&categoria_tipo=familia'>$familia_descricao</a></li> \n ";

				}
				echo "</ul> \n ";
			}
			echo "</li> \n ";


		}
	}
echo "</ul> \n ";
flush();
?>