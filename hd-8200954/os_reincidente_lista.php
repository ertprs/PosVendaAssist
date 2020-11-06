<?
$listar= $_GET['listar'];
if(strlen($listar) > 0) {
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
}
?>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script> <script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<?

if(strlen($listar) > 0) $limite = "";
else                    $limite = " LIMIT 3 ";

	if(strlen($listar) > 0) {
		$sql = "SELECT DISTINCT tbl_os.os                               ,
				tbl_os.sua_os                                           ,
				tbl_os.tipo_atendimento                                 ,
				LPAD(tbl_os.sua_os,10,'0') AS os_ordem                  ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura    ,
				tbl_produto.produto                                     ,
				tbl_produto.referencia                                  ,
				tbl_produto.descricao                                   ,
				tbl_produto.voltagem
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			LEFT JOIN tbl_os_produto using(os)
			WHERE tbl_os.fabrica     = $login_fabrica
			AND tbl_os.posto         = $login_posto
			AND tbl_os.os_reincidente IS TRUE
			AND tbl_os.data_digitacao >'2009-04-16 00:00:00'
			AND (tbl_os.obs_reincidencia IS NULL or length(obs_reincidencia) =0) ";
	}else{
		$sql = "SELECT distinct tbl_os.os                                        ,
				tbl_os.sua_os                                           ,
				tbl_os.tipo_atendimento                                 ,
				LPAD(tbl_os.sua_os,10,'0') AS os_ordem                  ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura    ,
				tbl_produto.produto                                     ,
				tbl_produto.referencia                                  ,
				tbl_produto.descricao                                   ,
				tbl_produto.voltagem
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			LEFT JOIN tbl_os_produto using(os)
			WHERE tbl_os.fabrica     = $login_fabrica
			AND tbl_os.posto         = $login_posto
			AND tbl_os.os_reincidente IS TRUE
			AND tbl_os.data_digitacao >'2009-04-16 00:00:00'
			AND (tbl_os.obs_reincidencia IS NULL or length(obs_reincidencia) =0)
			LIMIT 3 ";
	}
	$res = pg_exec($con,$sql);

	if (@pg_numrows($res) > 0) {
		echo "<div id ='lista'>";
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align = 'center' id='lista_os'>";
		echo "<tr  height='15' bgcolor='#FF0000' height='30'>";
		echo "<td colspan='3' background='admin/imagens_admin/vermelho.gif' class='Titulo' height='30'>";
		echo "&nbsp;O.S's REINCIDENTES SEM JUSTIFICATIVA&nbsp;";
		echo "<br><font color='#FFFF00'>";
		echo "</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15' bgcolor='#FF0000'>";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td>";
		echo "PRODUTO";
		echo "</td>";
		echo "</tr>";
		for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
			$os               = trim(pg_result($res,$a,os));
			$sua_os           = trim(pg_result($res,$a,sua_os));
			$tipo_atendimento = trim(pg_result($res,$a,tipo_atendimento));
			$abertura         = trim(pg_result($res,$a,abertura));
			$produto          = trim(pg_result($res,$a,produto));
			$referencia       = trim(pg_result($res,$a,referencia));
			$descricao        = trim(pg_result($res,$a,descricao));


			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================

			$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			$produto_completo = $referencia . " - " . $descricao;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td class='Conteudo' >";
			echo "<a href='os_motivo_atraso.php?os=$os&justificativa=ok' target='_blank'>";

			if(strlen($sua_os)==0) echo $os;
			else                  echo "$sua_os";
			echo "</a></td>";
			echo "<td align='center'>" . $abertura . "</td>";

			if ($sistema_lingua=='ES') echo "<td nowrap><acronym title='Referencia: $referencia\nDescripción: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
			else echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";

			echo "</tr>";
		}
		if(strlen($listar) == 0) {
			echo "<tr>";
				echo "<td class='Conteudo' colspan='3' align='center'><a href=\"os_reincidente_lista.php?keepThis=true&height=300&width=700&listar=todas\" title='OS reincidentes' class='thickbox'>LISTAR TODAS</a></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "</div>";
	}

?>
