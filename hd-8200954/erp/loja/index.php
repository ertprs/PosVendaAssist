<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
include '../../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$login_posto = $_GET['posto'];
if (strlen ($login_posto) > 0) {
	// setcookie ('cook_login_posto',$login_posto);
	add_cookie($cookie_login,'cook_login_posto',$login_posto);	
	set_cookie_login($token_cookie,$cookie_login);
}

$title="Seja bem-vindo à loja da Tecnoplus !";
include "topo.php";

echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
	echo "<td width='182' valign='top'>";
	include "menu.php";
	echo "<BR>";
	echo "</td>";
	echo "<td width='568' align='right' valign='top'>";
	echo "<BR>";
			echo "<table width='555' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 10px'>";
			echo "<tr width='555' height='40'>";
			
				echo "<td  width='13' height='40'><IMG SRC='corpo_dir1.jpg' width='13'  height='40'>";
				echo "</td>";

				echo "<td width='529' height='40' colspan='3' align='left' background='corpo_dir2.jpg' align='center'>&nbsp;&nbsp;<font size='2'><B>Produtos em Destaque</B></font>";
				echo "</td>";
				
				echo "<td  width='13' height='40'><IMG SRC='corpo_dir3.jpg' width='13'  height='40'>";
				echo "</td>";
				
			echo "</tr>";
			//produtos linha a cima

 			//pega produtos
			$sql = "SELECT  peca   ,
							referencia,
							descricao,
					preco_sugerido
					FROM tbl_peca
					WHERE fabrica='$login_empresa' AND promocao_site IS TRUE LIMIT 6";
			$res = pg_exec ($con,$sql);
		//		echo "$sql";
			echo "<tr>";
				echo "<td>";
				echo "</td>";
			if(strlen(pg_numrows($res)>0)){
			for ($i = 0 ; $i < pg_numrows($res); $i++){
				$peca             = trim(pg_result ($res,$i,peca));
				$referencia       = trim(pg_result ($res,$i,referencia));
				$preco_sugerido   = trim(pg_result ($res,$i,preco_sugerido));
				$descricao        = trim(pg_result ($res,$i,descricao));


				echo "<td width='176' align='center' ><BR><a href='detalhe.php?cod_produto=$peca'>";
				
				$sql="select tbl_peca_item_foto.peca, caminho, tbl_peca.peca FROM tbl_peca_item_foto JOIN tbl_peca ON tbl_peca.peca = tbl_peca_item_foto.peca where tbl_peca_item_foto.peca = $peca";
				$xres = pg_exec ($con,$sql);
				//echo $sql;

				if(strlen(pg_numrows($xres)>0)){
					$caminho = trim(pg_result ($xres,0,caminho));
				}else{
					$caminho = "produtos/552497-semimagem.jpg";
				}
				$caminho = str_replace("/www/assist/www/erp/","",$caminho);
				if($caminho <>"produtos/552497-semimagem.jpg"){$caminho = "../".$caminho;}
					
//				echo $caminho;

				echo "<IMG SRC='$caminho' width='80' border='0'><BR>";

				echo "<BR>$descricao</a>";
				echo "</td>";
					if($i == 2){
					echo "<td>";
					echo "</td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td>";
				echo "</td>";
					}
				}
			}else{
				echo "<td colspan='5' align='center'><b>NENHUM PRODUTO DISPONÍVEL EM PROMOÇÃO</b></td>";
			}
			echo "<td>";
			echo "</td>";
			echo "</tr>";

				
				//produtos linha de baixo
				echo "<tr height='34'>";
				echo "<td width='13' height='34'><IMG SRC='corpo_dir4.jpg' width='13' height='34'>";
				echo "</td>";
				echo "<td width='529' colspan='3' background='corpo_dir5.jpg'>";
				echo "</td>";
				echo "<td width='13' height='34'><IMG SRC='corpo_dir6.jpg' width='13' height='34'>";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td colspan='2' height='60' bgcolor='#f3f2f1' align='center'>
		&nbsp;<a href='index.php'>Home</a>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='empresa.php'>Quem Somos</a>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='cadatro.php'>Cadastro</A>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='promocao.php'>Destaque</A>&nbsp;&nbsp;&nbsp;|
		&nbsp;&nbsp;&nbsp;<a href='#'>Fale Conosco</a><BR>
		Tecnoplus 2007 -  Todos os direitos Reservados<BR>
		Sistema Telecontrol";
		echo "</td>";
	echo "</tr>";
echo "</table>";

?>