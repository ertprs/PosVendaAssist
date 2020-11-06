<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
//ESTÁ EM TESTE PARA A TECTOY 27/09/06 TAKASHI
if($login_fabrica=='6'){
include "defeito_reclamado_cadastro_new.php";
exit;
}
include 'funcoes.php';

if (strlen($_GET["defeito"]) > 0) {
	$defeito = trim($_GET["defeito"]);
}

if (strlen($_POST["defeito"]) > 0) {
	$defeito = trim($_POST["defeito"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}


if ($btnacao == "deletar" and strlen($defeito) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_defeito_reclamado
			WHERE  tbl_defeito_reclamado.defeito_reclamado = $defeito
			AND    (tbl_defeito_reclamado.linha = tbl_linha.linha OR tbl_defeito_reclamado.familia = tbl_familia.familia)
			AND    (tbl_linha.fabrica = $login_fabrica OR tbl_familia.fabrica = $login_fabrica);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'defeito_reclamado_fk') > 0) $msg_erro = "Este defeito não pode ser excluido";

	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=Apagado_Com_Sucesso");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$defeito			= $_POST["defeito"];
		$linha				= $_POST["linha"];
		$familia			= $_POST["familia"];
		$duvida_reclamacao	= $_POST["duvida_reclamacao"];
		$descricao			= $_POST["descricao"];
		$ativo				= $_POST["ativo"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	
	if (strlen($_POST["descricao"]) > 0) {
		$aux_descricao = "'". trim($_POST["descricao"]) ."'";
	}else{
		$msg_erro = "Informe o defeito reclamado.";
	}
	
	if (strlen($_POST["linha"]) > 0) {
		$aux_linha = "'". trim($_POST["linha"]) ."'";
	}else{
		$aux_linha = "null";
	}

	if (strlen($_POST["familia"]) > 0) {
		$aux_familia = "'". trim($_POST["familia"]) ."'";
	}else{
		$aux_familia = 'null';
	}

	if (strlen($_POST["duvida_reclamacao"]) > 0) {
		$aux_duvida_reclamacao = "'". trim($_POST["duvida_reclamacao"]) ."'";
	}else{
		$aux_duvida_reclamacao = 'null';
	}
	
	$ativo = $_POST['ativo'];
	$aux_ativo = $ativo;
	
	if (strlen ($ativo) == 0) $aux_ativo = "f";
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($defeito) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_defeito_reclamado (
						linha            ,
						familia          ,
						duvida_reclamacao,
						descricao        ,
						ativo,
						fabrica
					) VALUES (
						$aux_linha            ,
						$aux_familia          ,
						$aux_duvida_reclamacao,
						$aux_descricao        ,
						'$aux_ativo',
						$login_fabrica
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_defeito_reclamado SET
							linha             = $aux_linha            ,
							familia           = $aux_familia          ,
							duvida_reclamacao = $aux_duvida_reclamacao,
							descricao         = $aux_descricao        ,
							ativo             = '$aux_ativo'
					WHERE  tbl_defeito_reclamado.linha = tbl_linha.linha
					AND    tbl_linha.fabrica           = $login_fabrica
					AND    tbl_defeito_reclamado.defeito_reclamado = $defeito;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
//echo $sql;
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
//		$tipo = strpos
		header ("Location: $PHP_SELF?msg=Gravado_Com_Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$defeito			 = $_POST["defeito"];
		$linha				 = $_POST["linha"];
		$familia			 = $_POST["familia"];
		$duvida_reclamacao   = $_POST["duvida_reclamacao"];
		$descricao			 = $_POST["descricao"];
		$ativo				 = $_POST["ativo"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}//fim msg_erro
}


###CARREGA REGISTRO
if (strlen($defeito) > 0) {
	$sql = "SELECT  tbl_defeito_reclamado.linha            ,
					tbl_defeito_reclamado.familia          ,
					tbl_defeito_reclamado.duvida_reclamacao,
					tbl_defeito_reclamado.descricao        ,
					tbl_defeito_reclamado.ativo
			FROM    tbl_defeito_reclamado
			LEFT JOIN tbl_linha   USING (linha)
			LEFT JOIN tbl_familia USING (familia)
			WHERE     tbl_defeito_reclamado.defeito_reclamado = $defeito
			AND       (tbl_linha.fabrica = $login_fabrica OR tbl_familia.fabrica = $login_fabrica);";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$linha               = trim(pg_result($res,0,linha));
		$familia             = trim(pg_result($res,0,familia));
		$duvida_reclamacao   = trim(pg_result($res,0,duvida_reclamacao));
		$descricao           = trim(pg_result($res,0,descricao));
		$ativo               = trim(pg_result($res,0,ativo));
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "CADASTRAMENTO DE DEFEITOS RECLAMADOS";
	include 'cabecalho.php';
?>

<style>
td { 
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
}
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}.titulo_tabela{
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
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
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
f-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}
</style>
<?php if ( isset($_GET['msg']) ) : ?>
<table align="center">
	<tr><td  class="msg_sucesso"><? echo  str_replace('_',' ',$_GET['msg']); ?></td></tr>
</table>
<?php endif; ?>
<form name="frm_defeito" method="post" action="<? echo $PHP_SELF ?>">

<table width="700" border="0" class='formulario' align='center' cellpadding='0' cellspacing="0">
	<input type="hidden" name="defeito" value="<? echo $defeito ?>">

	<? if (strlen($msg_erro) > 0) { ?>
	<tr><td class='msg_erro' colspan="4"><? echo $msg_erro; ?></td></tr>
	<? } ?>

<tr>
	<td class='titulo_tabela' colspan='4'>Cadastro</td>
</tr>
<tr><td colspan="4">&nbsp;</td></tr>
	<tr style="padding-top:10px;">
	<td>&nbsp;</td>
		<td align="left">
	<?
			$sql = "SELECT    DISTINCT
							  tbl_linha.linha,
							  tbl_linha.nome
					FROM      tbl_linha
					WHERE     tbl_linha.fabrica = $login_fabrica
					ORDER BY  tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "Linha *<br>";
				echo "<select class='frm' name='linha'>\n";
				echo "<option value=''>FAÇA SUA ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha        = trim(pg_result($res,$x,linha));
					$aux_nome_defeito = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome_defeito</option>\n";
				}
				
				echo "</select>\n";
			}
			?>
		</td>

		<!-- FAMILIA -->
		<td align="left">
			<?
			$sql = "SELECT    DISTINCT
							  tbl_familia.familia,
							  tbl_familia.descricao
					FROM      tbl_familia
					WHERE     tbl_familia.fabrica = $login_fabrica
					ORDER BY  tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "Família<br>";
				echo "<select class='frm' name='familia'>\n";
				echo "<option value=''>FAÇA SUA ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia        = trim(pg_result($res,$x,familia));
					$aux_descricao_defeito = trim(pg_result($res,$x,descricao));
					
					echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao_defeito</option>\n";
				}
				
				echo "</select>\n";
			}
			?>
		</td>

		<!-- Dúvida ou Reclamação -->
		<td align="left">Dúvida ou Reclamação<br>
			<select name="duvida_reclamacao" class="frm" style="width: 150px;">
				<option value="">ESCOLHA</option>
				<? if ($login_fabrica <> 6) { ?>
				<option value="DV" <? if ($duvida_reclamacao == "DV") echo " SELECTED "; ?>>Dúvida</option>
				<option value="RC" <? if ($duvida_reclamacao == "RC") echo " SELECTED "; ?>>Reclamação</option>
				<option value="IS" <? if ($duvida_reclamacao == "IS") echo " SELECTED "; ?>>Insatisfação</option>
				<? } else { ?>
				<option value="RC" <? if ($duvida_reclamacao == "RC") echo " SELECTED "; ?>>Reclamação</option>
				<option value="IN" <? if ($duvida_reclamacao == "IN") echo " SELECTED "; ?>>Informação</option>
				<option value="IS" <? if ($duvida_reclamacao == "IS") echo " SELECTED "; ?>>Insatisfação</option>
				<option value="TP" <? if ($duvida_reclamacao == "TP") echo " SELECTED "; ?>>Troca de Produto</option>
				<option value="EN" <? if ($duvida_reclamacao == "EN") echo " SELECTED "; ?>>Engano</option>
				<option value="OA" <? if ($duvida_reclamacao == "OA") echo " SELECTED "; ?>>Outras Áreas</option>
				<? } ?>
			</select>
		</td>
	</tr>

	<!-- DESCRIÇÃO -->
	<tr>
	<td>&nbsp;</td>
		<td colspan="2" align="left">
			Descrição do Defeito *<br>
			<input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="40" maxlength="50">
		</td>
		<td align="left">
			<input type="checkbox" class="frm" name="ativo" <? if ($ativo == 't' ) echo " checked " ?> value='t'> Ativo
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
<tr>
	<td align='center' colspan='4'>
<input type='hidden' name='btnacao' value=''>

<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='gravar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" /> 

<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='deletar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" /> 

<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" /> 

</td>
</tr>
<tr><td colspan="4">&nbsp;</td></tr>
</table>
<table class='tabela' width='700px' align='center' cellpadding='2' cellspacing='1'>
	<tr>
		<td>Para efetuar alterações, clique na descrição do defeito reclamado.</td>
	</tr>
	<tr>
		<td class='titulo_tabela'>Relação de Defeitos Reclamados</td>
	</tr>
	

<?
$sql = "SELECT  DISTINCT
				tbl_linha.linha,
				tbl_linha.nome
		FROM    tbl_linha
		left JOIN    tbl_defeito_reclamado USING (linha)
		WHERE   tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_linha.linha;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$linha = trim(pg_result($res,$x,linha));
		
		$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado AS defeito_reclamado           ,
						tbl_defeito_reclamado.descricao         AS defeito_reclamado_descricao ,
						tbl_linha.nome                          AS linha_nome                  ,
						tbl_familia.descricao                   AS familia_descricao
				FROM    tbl_defeito_reclamado
				JOIN    tbl_linha USING (linha)
				LEFT JOIN    tbl_familia USING (familia)
				WHERE   tbl_linha.fabrica = $login_fabrica
				AND     tbl_defeito_reclamado.linha   = '$linha'
				ORDER BY tbl_familia.descricao, tbl_defeito_reclamado.descricao;";
		$res2 = pg_exec ($con,$sql);
		
		for ($y = 0 ; $y < pg_numrows($res2) ; $y++){
			$defeito_reclamado           = trim(pg_result($res2,$y,defeito_reclamado));
			$defeito_reclamado_descricao = trim(pg_result($res2,$y,defeito_reclamado_descricao));
			$linha_nome                  = trim(pg_result($res2,$y,linha_nome));
			$familia_descricao           = trim(pg_result($res2,$y,familia_descricao));
			
			if ($linha_nome <> $linha_nome_anterior) {
				echo "<tr class='titulo_coluna'>\n";
				echo "<td><b>$linha_nome</b></td>\n";
				echo "</tr>\n";
			}
			
			$cor = ($y % 2 == 0) ? "#F7F5F0": '#F1F4FA';
			
			if ($familia_descricao <> $familia_descricao_anterior) {
				echo "<tr bgcolor='#D9E2EF'>\n";
				echo "<td align='left'><b>$familia_descricao</b>&nbsp;</td>\n";
				echo "</tr>\n";
			}
			
			echo "<tr bgcolor='$cor'>\n";
			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?defeito=$defeito_reclamado'>$defeito_reclamado_descricao</a>";
			echo "</td>\n";
			echo "</tr>\n";
			
			$familia_descricao_anterior = $familia_descricao;
			$linha_nome_anterior = $linha_nome;
		}
	}
}else{
	$sql = "SELECT  DISTINCT
					tbl_familia.familia,
					tbl_familia.descricao
			FROM    tbl_familia
			JOIN    tbl_defeito_reclamado USING (familia)
			WHERE   tbl_familia.fabrica = $login_fabrica
			ORDER BY tbl_familia.familia;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$familia = trim(pg_result($res,$x,familia));
			
			$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado AS defeito_reclamado           ,
							tbl_defeito_reclamado.descricao         AS defeito_reclamado_descricao ,
							tbl_familia.descricao                   AS familia_descricao
					FROM    tbl_defeito_reclamado
					LEFT JOIN    tbl_linha USING (linha)
					JOIN    tbl_familia USING (familia)
					WHERE   tbl_familia.fabrica = $login_fabrica
					AND     tbl_defeito_reclamado.familia = '$familia'
					ORDER BY tbl_familia.descricao, tbl_defeito_reclamado.descricao;";
			$res2 = pg_exec ($con,$sql);
			
			for ($y = 0 ; $y < pg_numrows($res2) ; $y++){
				$defeito_reclamado           = trim(pg_result($res2,$y,defeito_reclamado));
				$defeito_reclamado_descricao = trim(pg_result($res2,$y,defeito_reclamado_descricao));
				$familia_descricao           = trim(pg_result($res2,$y,familia_descricao));
				
				
				if ($familia_descricao <> $familia_descricao_anterior) {
					echo "<table width='400' align='center' border='0' class='conteudo' cellpadding='2' cellspacing='1'>\n";
					echo "<tr bgcolor='#D9E2EF'>\n";
					echo "<td><b>$familia_descricao</b></td>\n";
					echo "</tr>\n";
				}
				
				$cor = ($y % 2 == 0) ? "#FFFFFF": '#F1F4FA';
				
				
				echo "<tr bgcolor='$cor'>\n";
				echo "<td align='left'>";
				echo "<a href='$PHP_SELF?defeito=$defeito_reclamado'>$defeito_reclamado_descricao</a>";
				echo "</td>\n";
				echo "</tr>\n";
				
				$familia_descricao_anterior = $familia_descricao;
			}
			echo "</table>\n";
			echo "<br >\n";
		}
	}
}
?>

</form>
</div>
<?
	include "rodape.php";
?>
</body>
</html>
