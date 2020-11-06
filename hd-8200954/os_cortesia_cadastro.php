<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

include "funcoes.php";

$qtde_itens = 30;

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_causa_defeito_os_item = pg_result ($res,0,pedir_causa_defeito_os_item);
$pedir_defeito_constatado_os_item = pg_result ($res,0,pedir_defeito_constatado_os_item);
$ip_fabricante = trim (pg_result ($res,0,ip_fabricante));
$ip_acesso     = $_SERVER['REMOTE_ADDR'];
$os_item_admin = "null";

#if ($login_fabrica == 3 AND strpos ($ip_acesso,$ip_fabricante) !== false ) $os_item_admin = "273";

if (strlen($_GET["os"]) > 0) $os = trim($_GET["os"]);
if (strlen($_POST["os"]) > 0) $os = trim($_POST["os"]);

$btn_acao   = $_POST["btn_acao"];
$os_produto = $_POST["os_produto"];
$produto    = $_POST["produto"];

$msg_erro = "";

if ($btn_acao == "gravar") {

	for ($i = 0 ; $i < $qtde_itens ; $i++) {
		$qtde = trim($_POST["peca_qtde_".$i]);
		$peca_referencia = trim($_POST["peca_referencia_".$i]);
		if (strlen($peca_referencia) > 0 AND strlen($qtde) > 0) {
			$msg_erro_pq = "QP";
		}
		if (strlen($peca_referencia) > 0 AND strlen($qtde) == 0) {
			$msg_erro_q = "Q";
		}
	}
	if ($msg_erro_pq <> "QP" and strlen($msg_erro_pq) > 0) {
		$msg_erro .= " Digite a peça e a quantidade.";
	}
	if ($msg_erro_q == "Q") {
		$msg_erro .= " Digite a quantidade da peça.";
	}

	if (strlen($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($msg_erro) == 0) {
			if (strlen($os) > 0) {

				########## A L T E R A   D A D O S ##########

				$defeito_constatado = $_POST ['defeito_constatado'];

				if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

				if (strlen ($defeito_constatado) > 0) {
					$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $login_posto;";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
				if (strlen ($type) > 0) {
					$sql = "UPDATE tbl_os SET type = $type
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $login_posto;";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

				$x_solucao_os = $_POST['solucao_os'];
				if (strlen ($msg_erro) == 0 and strlen($x_solucao_os) > 0) {
					$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $login_posto";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

				if (strlen($msg_erro) == 0) {
					for ($i = 0 ; $i < $qtde_itens ; $i++) {
						$os_item         = trim($_POST["os_item_".$i]);
						$qtde            = trim($_POST["peca_qtde_".$i]);
						$peca_referencia = trim($_POST["peca_referencia_".$i]);
						$peca_referencia = str_replace("." , "" , $peca_referencia);
						$peca_referencia = str_replace("-" , "" , $peca_referencia);
						$peca_referencia = str_replace("/" , "" , $peca_referencia);
						$peca_referencia = str_replace(" " , "" , $peca_referencia);
						$defeito         = trim($_POST["defeito_".$i]);
						$servico         = trim($_POST["servico_".$i]);

						if (strlen($peca_referencia) > 0) {
							$sql =	"SELECT tbl_peca.peca
									FROM  tbl_peca
									JOIN  tbl_lista_basica USING (peca)
									WHERE UPPER(tbl_peca.referencia_pesquisa) = UPPER('$peca_referencia')
									AND   tbl_lista_basica.produto            = $produto
									AND   tbl_lista_basica.fabrica            = $login_fabrica";
							$res = pg_exec ($con,$sql);
							if (pg_numrows($res) > 0) {
								$peca = pg_result($res,0,0);
							}else{
								$msg_erro .= " Peça $peca_referencia não cadastrada.";
							}
						}

						if (strlen($peca) > 0 AND strlen($qtde) > 0 AND strlen($msg_erro) == 0) {
							if (strlen($os_item) == 0) {
								$sql =	"INSERT INTO tbl_os_item (
												os_produto        ,
												peca              ,
												qtde              ,
												defeito           ,
												servico_realizado 
											) VALUES (
												$os_produto ,
												$peca       ,
												$qtde       ,
												$defeito    ,
												$servico    
											);";
							}else{
								$sql =	"UPDATE tbl_os_item SET
												peca              = $peca    ,
												qtde              = $qtde    ,
												defeito           = $defeito ,
												servico_realizado = $servico 
										WHERE os_item    = $os_item
										AND   os_produto = $os_produto";
							}
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
							$msg_erro = substr($msg_erro,6);
						}

						if (strlen($os_item) > 0 AND strlen($peca_referencia) == 0 AND strlen($msg_erro) == 0) {
							$sql =	"DELETE FROM tbl_os_item 
									WHERE os_item = $os_item
									AND   os_produto = $os_produto";
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
							$msg_erro = substr($msg_erro,6);
						}
						$os_item = '';
						$peca_referencia = '';
						$peca = '';
						$qtde = '';
					}
				}
			}
		}

		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);
			}
			$res      = pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);

			//hd 48676
			if (strlen ($msg_erro) == 0) {
				$res      = @pg_exec ($con,"SELECT fn_valida_os_item($os, $login_fabrica)");
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header ("Location: os_finalizada.php?os=$os");
			exit;
		}

		if(strlen ($msg_erro) > 0) {
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}

	}
}

if (strlen($os) > 0) {
	$sql =	"SELECT tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.posto                                                ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os.fabrica                                              ,
					tbl_os.admin                                                ,
					tbl_os.produto                                              ,
					tbl_os.serie                                                ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_cpf                                       ,
					tbl_os.nota_fiscal                                          ,
					tbl_os.defeito_constatado                                   ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_os.tipo_os_cortesia                                     ,
					tbl_os.troca_faturada                                       ,
					tbl_os.motivo_troca                                         ,
					tbl_os.solucao_os                                           ,
					tbl_os_produto.os_produto                                   ,
					tbl_os.type                                                 ,
					tbl_produto.referencia                                      ,
					tbl_produto.voltagem                                        ,
					tbl_posto_fabrica.codigo_posto                              
			FROM	tbl_os
			JOIN	tbl_os_produto USING (os)
			JOIN	tbl_produto ON tbl_os.produto  = tbl_produto.produto
			JOIN	tbl_posto   ON tbl_posto.posto = tbl_os.posto
			JOIN	tbl_posto_fabrica	ON tbl_posto.posto            = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$os                 = pg_result($res,0,os);
		$sua_os             = "00000" . pg_result($res,0,sua_os);
		$sua_os             = substr($sua_os, strlen($sua_os)-5, strlen($sua_os));
		$posto              = pg_result($res,0,posto);
		$data_abertura      = pg_result($res,0,data_abertura);
		$fabrica            = pg_result($res,0,fabrica);
		$admin              = pg_result($res,0,admin);
		$produto            = pg_result($res,0,produto);
		$produto_serie      = pg_result($res,0,serie);
		$consumidor_nome    = pg_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_result($res,0,consumidor_cpf);
		$nota_fiscal        = pg_result($res,0,nota_fiscal);
		$defeito_constatado = pg_result($res,0,defeito_constatado);
		$data_nf            = pg_result($res,0,data_nf);
		$os_produto         = pg_result($res,0,os_produto);
		$tipo_os_cortesia   = pg_result($res,0,tipo_os_cortesia);
		$troca_faturada     = pg_result($res,0,troca_faturada);
		$motivo_troca       = pg_result($res,0,motivo_troca);
		$solucao_os         = pg_result($res,0,solucao_os);
		$produto_referencia = pg_result($res,0,referencia);
		$produto_voltagem   = pg_result($res,0,voltagem);
		$posto_codigo       = pg_result($res,0,codigo_posto);
		$type               = pg_result($res,0,type);
	}
}

$title = "Cadastro de Ordem de Serviço do Tipo Cortesia"; 
$layout_menu = 'os';
include "cabecalho.php";
?>

<script>
function fnc_pesquisa_peca_lista (produto_referencia, peca_referencia, peca_descricao, peca_preco, tipo) {
	var url = "";

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&voltagem=" + document.frm_os.produto_voltagem.value + "&tipo=" + tipo + "&faturado=sim" ;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&voltagem=" + document.frm_os.produto_voltagem.value + "&tipo=" + tipo + "&faturado=sim" ;
	}

	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=502, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= peca_preco;
		janela.focus();
	}else{
		alert("Digite pelo menos 4 caracteres!");
	}
}
</script>

<? if (strlen ($msg_erro) > 0) { ?>
<br>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '700'>
<tr>
	<td valign="middle" align="center" class='error'>
<? 
	// Retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$msg_erro = substr($msg_erro, 6);
	}
	echo "Foi detectado o seguinte erro:<br>".$msg_erro; 
?>
	</td>
</tr>
</table>
<? } ?>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="os" value="<? echo $os; ?>">
<input type="hidden" name="os_produto" value="<? echo $os_produto; ?>">
<input type="hidden" name="produto" value="<? echo $produto; ?>">
<input type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
<input type="hidden" name="produto_voltagem" value="<? echo $produto_voltagem; ?>">

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700">
	<tr valign="top" align="left">
<? if (strlen($os) > 0) { ?>
		<td>
			<input type="hidden" name="sua_os" value="<? echo $sua_os; ?>">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $posto_codigo.$sua_os; ?></b></font>
		</td>
<? } ?>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $posto_codigo ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data de Abertura</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $data_abertura ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo da OS cortesia</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $tipo_os_cortesia ?></b></font>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700">
	<tr valign="top" align="left">
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência do Produto</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $produto_referencia ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem do Produto</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $produto_voltagem ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $type ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nº de Série</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $produto_serie ?></b></font>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700">
	<tr valign="top" align="left">
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $consumidor_nome ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ Consumidor</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $consumidor_cpf ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $nota_fiscal ?></b></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
			<br>
			<font face="Verdana, Tahoma" size="2"><b><? echo $data_nf ?></b></font>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700">
	<tr valign="top" align="left">
		<?
			if ($pedir_defeito_constatado_os_item <> 'f') {
		?>
		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Constatado</font>
			<br>
			<select name="defeito_constatado" size="1" class="frm">
				<option selected></option>
			<?
			$sql = "SELECT defeito_constatado_por_familia, defeito_constatado_por_linha FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$defeito_constatado_por_familia = pg_result ($res,0,0) ;
			$defeito_constatado_por_linha   = pg_result ($res,0,1) ;

			if ($defeito_constatado_por_familia == 't') {
				$sql = "SELECT familia FROM tbl_produto WHERE produto = $produto";
				$res = pg_exec ($con,$sql);
				$familia = pg_result ($res,0,0) ;

				if ($login_fabrica == 1){

					$sql = "SELECT tbl_defeito_constatado.* FROM tbl_familia  JOIN   tbl_familia_defeito_constatado USING(familia) JOIN   tbl_defeito_constatado USING(defeito_constatado) ";
					if ($linha == 198) $sql .= " JOIN tbl_produto_defeito_constatado USING(defeito_constatado) ";
					$sql .= " WHERE  tbl_defeito_constatado.fabrica = $login_fabrica AND tbl_familia_defeito_constatado.familia = $familia";
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
					if ($linha == 198) $sql .= " AND tbl_produto_defeito_constatado.produto = $produto_os ";
					$sql .= " ORDER BY tbl_defeito_constatado.descricao";
				}else{
					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_familia
							JOIN   tbl_familia_defeito_constatado USING(familia)
							JOIN   tbl_defeito_constatado         USING(defeito_constatado)
							WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
							AND    tbl_familia_defeito_constatado.familia = $familia";
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
					$sql .= " ORDER BY tbl_defeito_constatado.descricao";
				}
			}else{

				if ($defeito_constatado_por_linha == 't') {
					$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
					$res   = pg_exec ($con,$sql);
					$linha = pg_result ($res,0,0) ;

					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							JOIN   tbl_linha USING(linha)
							WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
							AND    tbl_linha.linha = $linha";
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
					$sql .= " ORDER BY tbl_defeito_constatado.descricao";
				}else{
					$sql = "SELECT tbl_defeito_constatado.*
						FROM   tbl_defeito_constatado
						WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
					$sql .= " ORDER BY tbl_defeito_constatado.descricao";
				}
			}

			$res = pg_exec ($con,$sql) ;
#echo $sql;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option ";
				if ($defeito_constatado == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
				echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
				echo pg_result ($res,$i,codigo) ." - ". pg_result ($res,$i,descricao) ;
				echo "</option>";
			}
			?>
			</select>
		</td>
		<? } ?>

		<?if ($pedir_causa_defeito_os_item <> 'f' and $login_fabrica <> 5) { ?>
		<td nowrap>
			<?
			if ($login_fabrica == 1){
				echo "<INPUT TYPE='hidden' name='name='causa_defeito' value='149'>";
			}else{
			?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Causa do Defeito</font>
			<br>
			<select name="causa_defeito" size="1" class="frm">
				<option selected></option>
<?
				$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
				$res = pg_exec ($con,$sql) ;

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option ";
					if ($causa_defeito == pg_result ($res,$i,causa_defeito) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,causa_defeito) . "'>" ;
					echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
					echo "</option>\n";
				}
?>
			</select>
			<? } ?>
		</td>
		<? } ?>
	</tr>

	<tr valign="top" align="left">
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Solução</font>
			<br>
			<select name="solucao_os" size="1" class="frm">
			<option value=""></option>
			<?
			if($login_fabrica==1){
			
			if ($login_reembolso_peca_estoque == 't') {
				$sql_add1 = " AND ( descricao NOT ILIKE 'Troca de pe%' OR descricao ILIKE 'subst%' ) ";
				}else{
					$sql_add1 = " AND (descricao ILIKE 'troca%' OR descricao NOT ILIKE 'subst%') ";
				}
				if ($cortesia <> 't') {
					$sql .= " descricao NOT ILIKE 'Devolução de dinheiro%' ";
				}

				$sql = "SELECT 	solucao,
						descricao
					FROM tbl_solucao
					WHERE fabrica = $login_fabrica 
					AND   ativo IS TRUE
					$sql_add1
					ORDER BY descricao";
				$res = pg_exec($con, $sql);
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
					$aux_solucao_os    = pg_result ($res,$x,solucao);
					$solucao_descricao = pg_result ($res,$x,descricao);
					echo "<option id='opcoes' value='$aux_solucao_os' "; if($aux_solucao_os == $solucao_os) echo " SELECTED"; echo ">$solucao_descricao</option>";
				}
			}else{

				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
				
				if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
					$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				}

				if ($login_fabrica == 1) {
					if ($login_reembolso_peca_estoque == 't') {
						$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
					}else{
						$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
						$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
					}
				}

				$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
				$res = pg_exec ($con,$sql) ;

				if (pg_numrows($res) == 0) {
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}

					if ($login_fabrica == 1) {
						if ($login_reembolso_peca_estoque == 't') {
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						}
					}

					$sql .=	" AND tbl_servico_realizado.linha IS NULL
							AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
					$res = pg_exec ($con,$sql) ;
				}

				for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
					echo "<option ";
					if ($solucao_os == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
					echo "</option>";
				}
			}
			?>
			</select>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="2" align="center">
	<tr bgcolor="#CCCCCC">
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Código</b></font> <a href="peca_consulta_por_produto.php?produto=<?echo $produto?>" target="_black"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><b>Lista Básica</b></font></a></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Descrição</b></font></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Qtde</b></font></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Defeito</b></font></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Serviço</b></font></td>
	</tr>
<?
if (strlen($_GET['os']) > 0) {
	$sql =	"SELECT tbl_os_item.os_item                              ,
					tbl_os_item.peca                                 ,
					tbl_os_item.qtde                                 ,
					tbl_os_item.defeito                              ,
					tbl_os_item.servico_realizado                    ,
					tbl_peca.referencia           AS peca_referencia ,
					tbl_peca.descricao            AS peca_descricao  
			FROM  tbl_os_item
			JOIN  tbl_peca       ON tbl_os_item.peca       = tbl_peca.peca
			JOIN  tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN  tbl_os         ON tbl_os_produto.os      = tbl_os.os
			WHERE tbl_os_produto.produto    = $produto
			AND   tbl_os_produto.os_produto = $os_produto
			AND   tbl_os.fabrica            = $login_fabrica
			ORDER BY tbl_os_item.peca";
	$res = pg_exec($con,$sql);
	$num_linhas = pg_numrows($res);
}

for ($i = 0 ; $i < $qtde_itens ; $i++) {

	$os_item         = "";
	$peca            = "";
	$peca_referencia = "";
	$peca_descricao  = "";
	$peca_qtde       = "";
	$defeito         = "";
	$servico         = "";

	if ($i < $num_linhas) {
		$os_item         = pg_result($res,$i,os_item);
		$peca            = pg_result($res,$i,peca);
		$peca_referencia = pg_result($res,$i,peca_referencia);
		$peca_descricao  = pg_result($res,$i,peca_descricao);
		$peca_qtde       = pg_result($res,$i,qtde);
		$defeito         = pg_result($res,$i,defeito);
		$servico         = pg_result($res,$i,servico_realizado);
	}

	if (strlen($msg_erro) > 0) {
		$os_item         = trim($_POST["os_item_".$i]);
		$peca            = trim($_POST["peca_".$i]);
		$peca_referencia = trim($_POST["peca_referencia_".$i]);
		$peca_descricao  = trim($_POST["peca_descricao_".$i]);
		$peca_qtde       = trim($_POST["peca_qtde_".$i]);
		$defeito         = trim($_POST["defeito_".$i]);
		$servico         = trim($_POST["servico_".$i]);
	}
?>
	<tr>
		<td>
			<input type="hidden" name="os_item_<? echo $i ?>" value="<? echo $os_item ?>">
			<input type="hidden" name="peca_<? echo $i ?>" value="<? echo $peca ?>">
			<input type="hidden" name="produto" value="<? echo $produto ?>">
			<input type="hidden" name="preco">
			<input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" maxlength="20" value="<? echo $peca_referencia ?>">
			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_referencia.value , document.frm_os.peca_referencia_<? echo $i ?> , document.frm_os.peca_descricao_<? echo $i ?>, document.frm_os.preco , 'referencia')" alt="Clique para efetuar a pesquisa" style='cursor:pointer;'>
		</td>
		<td>
			<input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="25" value="<? echo $peca_descricao ?>">
			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_referencia.value , document.frm_os.peca_referencia_<? echo $i ?> , document.frm_os.peca_descricao_<? echo $i ?>, document.frm_os.preco , 'descricao')" alt="Clique para efetuar a pesquisa" style='cursor:pointer;'>
		</td>
		<td><input class="frm" type="text" name="peca_qtde_<? echo $i ?>" size="5" value="<? echo $peca_qtde ?>"></td>
		<td>
			<select class='frm' size='1' name='defeito_<? echo $i ?>'>
				<option></option>
				<?
				$sqlD = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica;";
				$resD = pg_exec ($con,$sqlD) ;
				for ($x = 0 ; $x < pg_numrows($resD) ; $x++ ) {
					echo "<option ";
					if ($defeito == pg_result($resD,$x,defeito)) echo " selected ";
					echo " value='" . pg_result($resD,$x,defeito) . "'>" ;
					echo pg_result($resD,$x,descricao) ;
					echo "</option>";
				}
				?>
			</select>
		</td>
		<td>
			<select class='frm' size='1' name='servico_<? echo $i ?>'>
				<option></option>
<?
				$sqlS = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
				
				if (strlen($linha) > 0) {
					$sqlS .= " AND tbl_servico_realizado.linha = '$linha' ";
				}
				
				if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
					$sqlS .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				}
				
				if ($login_fabrica == 1) {
					if ($login_reembolso_peca_estoque == 't') {
						$sqlS .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						$sqlS .= "OR tbl_servico_realizado.descricao ILIKE '%pedido%') ";
					}else{
						$sqlS .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
						$sqlS .= "OR tbl_servico_realizado.descricao NOT ILIKE '%pedido%') ";
					}
				}
				
				$sqlS .= "AND tbl_servico_realizado.ativo = 't' ORDER BY descricao ";
				$resS = pg_exec ($con,$sqlS) ;
				
				for ($x = 0 ; $x < pg_numrows($resS) ; $x++ ) {
					echo "<option ";
					if ($servico == pg_result($resS,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result($resS,$x,servico_realizado) . "'>" ;
					echo pg_result($resS,$x,descricao) ;
					echo "</option>";
				}
				?>
		</td>
	</tr>
<?
}
?>

</table>

<input type="hidden" name="btn_acao" value="">

<br>

<center><img border="0" src="imagens/btn_gravar.gif" onclick="javascript: if (document.frm_os.btn_acao.value =='') { document.frm_os.btn_acao.value='gravar'; document.frm_os.submit() }else{ alert('Aguarde submissão') }" ALT="Gravar" style="cursor:pointer;"></center>

</form>

<? include "rodape.php";?>