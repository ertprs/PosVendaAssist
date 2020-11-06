<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btnAcao"]) > 0) {
	$btnAcao = strtolower(trim($_POST["btnAcao"]));
}

if (strlen($_GET["peca"]) > 0) {
	$peca = trim($_GET["peca"]);
}

if (strlen($_POST["peca"]) > 0) {
	$peca = trim($_POST["peca"]);
}

if (strlen($_POST["tabela_item"]) > 0) {
	$tabela_item = trim($_POST["tabela_item"]);
}

if (strlen($_GET["tabela_item"]) > 0) {
	$tabela_item = trim($_GET["tabela_item"]);
}

if (strlen($_GET["Borrar"]) > 0) {
	$apagar = trim($_GET["Borrar"]);
}

$btnAcao = str_replace("ó","o", $btnAcao);
$btnAcao = strtoupper($btnAcao);

if ($btnAcao == "INVESTIGACION") {
	$peca = "";
}

if ($btnAcao == "REGISTRO") {

	if (strlen($_POST["preco"]) > 0) {
		$aux_preco = "'". trim($_POST["preco"]) ."'";
	}else{
		$msg_erro = "Anote el precio de parte.";
	}
	
	if (strlen($_POST["tabela"]) > 0) {
		$aux_tabela = "'". trim($_POST["tabela"]) ."'";
	}else{
		$msg_erro = "Seleccione una lista de precios.";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");
		

		if (strlen($tabela_item) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_tabela_item (
						tabela,
						peca  ,
						preco
					) VALUES (
						$aux_tabela,
						$peca      ,
						fnc_limpa_moeda($aux_preco)
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_tabela_item SET
							tabela = $aux_tabela,
							peca   = $peca,
							preco  = fnc_limpa_moeda($aux_preco)
					FROM    tbl_tabela
					WHERE   tbl_tabela_item.tabela      = tbl_tabela.tabela
					AND     tbl_tabela.fabrica          = $login_fabrica
					AND     tbl_tabela_item.tabela_item = $tabela_item;";
		}
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		
		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_query ($con,"COMMIT TRANSACTION");
			
			header ("Location: $PHP_SELF?peca=$peca&msg=Grabado con éxito!");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			
			if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_tabela_item_unico") > 0)
				$msg_erro = "Precio ya está registrado en esta tabla.";

			if (strpos ($msg_erro,"duplicate key value violates unique constraint \"tbl_tabela_item_unico\"") > 0)
				$msg_erro = "Precio ya está registrado en esta tabla.";
			
			$peca = $_POST["peca"];
			
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg_erro
}

if (strlen($apagar) > 0) {
	#MPL6710007
	$res = pg_query ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_tabela_item
			USING  tbl_tabela
			WHERE  tbl_tabela_item.tabela      = tbl_tabela.tabela
			AND    tbl_tabela.fabrica          = $login_fabrica
			AND    tbl_tabela_item.tabela_item = $apagar;";
	$res = pg_query ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?peca=$peca");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$peca = $_POST["peca"];
		
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($produto) > 0) {
	$sql = "SELECT tbl_produto.referencia
			FROM   tbl_produto
			JOIN   tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE  tbl_produto.produto = $produto
			AND    tbl_linha.fabrica = $login_fabrica;";
	$res = @pg_query ($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		$produto_referencia = trim(pg_fetch_result($res,0,'referencia'));
	}
}

if (strlen($peca) > 0) {
	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia
			FROM    tbl_peca
			WHERE   tbl_peca.peca    = $peca
			AND     tbl_peca.fabrica = $login_fabrica;";
	$res = @pg_query ($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		$peca            = trim(pg_fetch_result($res,0,'peca'));
		$peca_referencia = trim(pg_fetch_result($res,0,'referencia'));
	}
	
	if (strlen($peca) > 0) {
		$sql = "SELECT   tbl_tabela_item.tabela,
							tbl_tabela_item.preco,
						tbl_tabela_item.tabela_item
				FROM     tbl_tabela_item
				JOIN     tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
				WHERE    tbl_tabela.fabrica   = $login_fabrica
				AND      tbl_tabela_item.peca = $peca
				AND 	tbl_tabela.sigla_tabela  = '$login_pais' 
				ORDER BY tbl_tabela.sigla_tabela;";
		$res = @pg_query ($con,$sql);
		
		if (pg_num_rows($res) > 0) {
			$tabela = trim(pg_fetch_result($res,0,'tabela'));
			$preco  = trim(pg_fetch_result($res,0,'preco'));
			$tabela_item  = trim(pg_fetch_result($res,0,'tabela_item'));
		}
	}
}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = "REGISTRO DE PRECIOS DE PRODUCTOS";
include 'cabecalho.php';
?>
<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.espaco{padding-left:100px;}
</style>

<script language="JavaScript">
function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
	else
		alert("Rellene todos o parte de la información para realizar la búsqueda!");
}

</script>
<?

if ($btnAcao == "INVESTIGACION" or $btn_acao == "INVESTIGACION") {

		$referencia = trim($_POST['referencia']);
		$descricao = trim($_POST['descricao']);

}

if ($_GET['peca']){
	
	$peca = $_GET['peca'];
	
	$sql = "SELECT referencia, descricao from tbl_peca where peca = $peca AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	
	$referencia = (pg_num_rows($res)>0) ? pg_fetch_result($res,0,0) : $referencia ;
	$descricao  = (pg_num_rows($res)>0) ? pg_fetch_result($res,0,1) : $descricao  ;
}

?>
<div id="wrapper">
<form name="frm_peca" method="post" action="<?php echo $PHP_SELF;?>">
	<input type="hidden" name="peca"         value="<? echo $peca ?>">
	<input type="hidden" name="tabela_item"  value="<? echo $tabela_item ?>">
	<table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario" width = '700'>
		<? if (strlen($msg_erro) > 0) { ?>
			<tr class="msg_erro"><td colspan='4'><? echo $msg_erro; ?></td></tr>
		<? } ?>

		<? if (strlen($msg) > 0) { ?>
			<tr class="sucesso"><td colspan='4'><? echo $msg; ?></td></tr>
		<? } ?>

		<tr class="titulo_tabela">
			<td colspan="4">
				Parámetros de búsqueda
			</td>
		</tr>

		<tr><td colspan="4">&nbsp;</td></tr>

		<tr>
			<td class="espaco">&nbsp;</td>
			<td align="left">
				Parte de referencia 
				<br>
				<input type="text" name="referencia" value="<? echo $referencia ?>" size="20" maxlength="20" class="frm">&nbsp;
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')">
			</td>
			<td align="left">
				Descripción
				<br>
				<input type="text" name="descricao" value="<? echo $descricao ?>" size="40" maxlength="50" class="frm">&nbsp;
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')">
			</td>
			<td>&nbsp;</td>
		</tr>

		<tr><td colspan="4">&nbsp;</td></tr>

		<tr>
			<td colspan="4">
				<input type="hidden" name="btnAcao" value="">
				<input type="submit" value="Investigación" style="cursor:pointer;" onclick="javascript: if (document.frm_peca.btnAcao.value == '' ) { document.frm_peca.btnAcao.value='Investigación' ;  document.frm_peca.submit() } else { alert ('Aguarde submissão') }" alt="Búsqueda de precios pieza">
			</td>
		</tr>

		<tr><td colspan="4">&nbsp;</td></tr>

	</table>
	<br />
	<?
	if ($btnAcao == "INVESTIGACION" or $btn_acao == "INVESTIGACION") {
	
		if (strlen($referencia) > 0) {
			$sql = "SELECT  tbl_peca.peca      ,
							tbl_peca.referencia,
							tbl_peca.descricao
					FROM    tbl_peca
					WHERE   tbl_peca.fabrica              = $login_fabrica
					AND     tbl_peca.referencia           = '$referencia'
					ORDER BY tbl_peca.descricao;";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
				echo '<table align="center" width="700" cellspacing="1" class="tabela">';
				echo "<tr class='titulo_coluna'>";
				echo "<td colspan='2'>Lista de piezas</td>";
				echo "</tr>";
				

				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$pec        = trim(pg_fetch_result($res,$x,peca));
					$referencia = trim(pg_fetch_result($res,$x,referencia));
					$descricao  = trim(pg_fetch_result($res,$x,descricao));
					if ($x % 2 ==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
					echo "<tr bgcolor='$cor'>";
					echo "<td>$referencia</td>";
					echo "<td><a href='$PHP_SELF?peca=$pec'>$descricao</a></td>";
					echo "<tr>";
				}
				echo "</table>";

			}else{
				echo "<div align='center'\n";
				echo "<div>No hubo resultados para esta búsqueda ha encontrado</div>\n";
				echo "</div>\n";
			}
		}
	}
			
	if (strlen($peca) > 0) {
		
		
		$sql = "SELECT  tbl_tabela_item.tabela_item,
						tbl_tabela.tabela          ,
						tbl_tabela.sigla_tabela    ,
						tbl_tabela.ativa           ,
						tbl_tabela_item.preco      ,
						tbl_peca.referencia        ,
						tbl_peca.descricao
				FROM    tbl_tabela
				JOIN    tbl_tabela_item USING (tabela)
				JOIN    tbl_peca        ON tbl_peca.peca = tbl_tabela_item.peca
				WHERE   tbl_tabela_item.peca = $peca
				AND     tbl_tabela.fabrica   = $login_fabrica 
				AND 	tbl_tabela.sigla_tabela  = '$login_pais' 
				ORDER BY tbl_tabela.ativa, tbl_tabela.sigla_tabela DESC;";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo '<table align="center" width="700" cellspacing="1" class="tabela">'."\n";
			echo "<tr><td class='titulo_coluna'>Tablas indexadas precio</td></tr>\n";

			//echo $sql;
			for ($y = 0 ; $y < pg_num_rows($res) ; $y++){

				$tabela_item     = trim(pg_fetch_result($res,$y,tabela_item));
				$tabela          = trim(pg_fetch_result($res,$y,tabela));
				$sigla           = trim(pg_fetch_result($res,$y,sigla_tabela));
				$ativa           = trim(pg_fetch_result($res,$y,ativa));
				$preco           = trim(pg_fetch_result($res,$y,preco));
				$peca_referencia = trim(pg_fetch_result($res,$y,referencia));
				$peca_descricao  = trim(pg_fetch_result($res,$y,descricao));
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				if ($peca_referencia <> $peca_referencia_anterior) {
					echo "<tr>\n";
					echo "<td class='subtitulo'>";
					echo " 		$peca_referencia - $peca_descricao\n";
					echo "</td>\n";
					echo "</tr>\n";
					echo "<tr bgcolor='$cor'>\n";
					echo "<td>\n";
					echo      "$sigla\n";
					if($ativa == 't'){ 
						echo "Activo de la Tabla";
					} else { 
						echo "Tabla Inactivos";
					}
					echo     "R$ ". number_format($preco,2,",",".");
					if($ativa == 't') {	
						echo "<br><input type='button' value='Borrar' onclick=\"if (confirm ('Realmente borrar?') == true) { window.location='$PHP_SELF?peca=$peca&apagar=$tabela_item' }\">";
					}
					echo "</td>\n";
					echo "</tr>\n";
				}else{
					
					echo "<tr bgcolor='$cor'>\n";
					echo "<td>\n";
					echo "$sigla\n";
					if($ativa == 't'){ 
						echo "Activo de la Tabla ";
					} else { 
						echo "Tabela Inativa ";
					}
					echo "R$ ". number_format($preco,2,",",".");
					if($ativa == 't') {
						echo "<input type='button' value='Borrar' onclick=\"if (confirm ('Realmente borrar?') == true) { window.location='$PHP_SELF?peca=$peca&apagar=$tabela_item' }\">";
					} 
					echo "</td>\n";
					echo "</tr>\n";
				}

				$peca_referencia_anterior = trim(@pg_fetch_result($res,$y+1,'referencia'));
			}

			echo "</table><br>\n";
		}
			
		echo "<table width='700' class='formulario' cellspacing='1' cellpadding='2' align='center' border='0'>";
			echo "<tr class='titulo_tabela'>";
			echo "<td colspan='4'>Registro de Precio</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td width='150'>&nbsp;</td>";
			echo "<td align='left'>Precio de mesa</td>";
			echo "<td align='left'>Preço</td>";
			echo "<td width='200'>&nbsp;</td>";
			echo "</tr>";
			echo "<tr><td width='100'>&nbsp;</td><td align='left'>";

			$sql = "SELECT 
						distinct(tbl_tabela.tabela) as tabela,
						tbl_tabela.sigla_tabela
					FROM 
						tbl_tabela 
					JOIN tbl_pais
					ON tbl_tabela.sigla_tabela = '$login_pais'
					WHERE tbl_tabela.fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
						echo "<select class='frm' name='tabela'>\n";
							for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
							$aux_tabela = trim(pg_fetch_result($res,$x,tabela));
							$aux_sigla  = trim(pg_fetch_result($res,$x,sigla_tabela));
							echo "<option value='$aux_tabela'"; if ($tabela == $aux_tabela) echo " SELECTED "; echo ">$aux_sigla</option>\n";
							}
						echo "</select>\n";
					echo "</td>";
					echo "<td align='left'>";
						echo "<input type=\"text\" name=\"preco\" value=\"$preco\" size=\"10\" maxlength=\"\" class='frm'>";
					echo "</td><td width='100'>&nbsp;</td></tr>";
				
			}

			echo "<tr><td colspan='4'>";

			echo "<br><input type='button' value='Registro' onclick=\"javascript: if (document.frm_peca.btnAcao.value == '' ) { document.frm_peca.btnAcao.value='Registro' ; document.frm_peca.submit() } else { alert ('Espere Presentación') }\" alt='Registro' border='0' style='cursor:pointer'><br><br>";
			
			echo "</td ></tr>";
			echo "</table>\n";
		}
		?>
		</form>
	</div>
	<? include "rodape.php"; ?>

</body>
</html>
