<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "../classes/form/GeraComboType.php";
}
$qtde_itens = 10;

if (strlen($_POST['os']) > 0) $os = trim($_POST['os']);
else $os = trim($_GET['os']);

$btn_acao = $_POST['btn_acao'];

$os_produto = $_POST['os_produto'];

$msg_erro = "";

if ($btn_acao == "gravar") {

	$posto_codigo = trim($_POST['posto_codigo']);
	if (strlen($posto_codigo) == 0) {
		$msg_erro .= " Digite o Código do Posto.";
	}else{
		$posto_codigo = str_replace("-","",$posto_codigo);
		$posto_codigo = str_replace(".","",$posto_codigo);
		$posto_codigo = str_replace("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);
	}

	$data_abertura      = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($data_abertura == "null") $msg_erro .= " Digite a Data de Abertura da OS.";

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);
	if (strlen($produto_referencia) == 0) $msg_erro .= " Digite a Referência do produto.";

	$produto_voltagem   = trim($_POST['produto_voltagem']);

	$produto_type       = trim($_POST['produto_type']);
#	if (strlen($produto_type) == 0) $msg_erro .= " Selecione o Tipo do produto.";

	$produto_serie      = trim($_POST['produto_serie']);
	if (strlen($produto_serie) == 0) $xproduto_serie = 'null';
	else                             $xproduto_serie = "'".$produto_serie."'";

	$tipo_os_cortesia   = $_POST['tipo_os_cortesia'];
	if (strlen($tipo_os_cortesia) == 0) $msg_erro .= " Selecione o Tipo da OS Cortesia.";

	$consumidor_nome    = trim($_POST['consumidor_nome']);

	$consumidor_cpf     = trim($_POST['consumidor_cpf']);
	$consumidor_cpf     = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf     = str_replace(" ","",$consumidor_cpf);
	$consumidor_cpf     = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf     = str_replace(".","",$consumidor_cpf);
	if (strlen($consumidor_cpf) < 10) $msg_erro .= " Tamanho do CPF/CNPJ do cliente inválido.";

	$nota_fiscal        = trim($_POST['nota_fiscal']);

	$data_nf            = fnc_formata_data_pg(trim($_POST['data_nf']));

	if ($tipo_os_cortesia == 'Garantia') {
		if (strlen($nota_fiscal) == 0) $msg_erro .= " Digite a Nota Fiscal.";
		if ($data_nf == "null")        $msg_erro .= " Digite a Data da Compra.";
	}

	if (strlen($msg_erro) == 0) {

		if (strlen($produto_referencia) > 0) {
			$sql =	"SELECT tbl_produto.produto
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE UPPER(trim(tbl_produto.referencia_pesquisa)) = UPPER(trim('$produto_referencia'))
					AND UPPER(trim(tbl_produto.voltagem)) = UPPER(trim('$produto_voltagem'))
					AND tbl_linha.fabrica = $login_fabrica;";
# echo $sql."<br><br>";

			$res      = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				$produto = pg_result($res,0,produto);
			}else{
				$msg_erro .= " Produto $produto_referencia não cadastrado.";
			}
		}

		if (strlen($posto_codigo) > 0) {
			$sql =	"SELECT tbl_posto.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica	ON tbl_posto.posto = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				$posto = pg_result ($res,0,0);
			}else{
				$msg_erro .= " Posto $posto_codigo não cadastrado.";
			}
		}

		$codigo_fabricacao  = trim ($_POST['codigo_fabricacao']);
		if ($login_fabrica == 1 AND strlen($codigo_fabricacao) == 0) $msg_erro = "Digite o Código de fabricação do produto.";
		if ($login_fabrica <> 1) $codigo_fabricacao = 'null';

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($msg_erro) == 0) {
			if (strlen($os) == 0) {

				$sql =	"SELECT MAX(sua_os) AS ultima_sua_os
						FROM tbl_os
						WHERE posto = $posto
						AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					$ultima_sua_os = intval( pg_result($res,0,0) + 1 );
				}else{
					$ultima_sua_os = 'null';
				}
				$ultima_sua_os = "00000" . trim($ultima_sua_os);
				$ultima_sua_os = substr($ultima_sua_os, strlen($ultima_sua_os)-5, strlen($ultima_sua_os));

				########## I N S E R E   D A D O S ##########
				$sql = "INSERT INTO tbl_os (
								sua_os          ,
								posto           ,
								data_abertura   ,
								fabrica         ,
								admin           ,
								produto         ,
								serie           ,
								consumidor_nome ,
								consumidor_cpf  ,
								nota_fiscal     ,
								data_nf         ,
								codigo_fabricacao,
								tipo_os_cortesia,
								type            ,
								cortesia
							) VALUES (
								'$ultima_sua_os'    ,
								$posto              ,
								$data_abertura      ,
								$login_fabrica      ,
								$login_admin        ,
								$produto            ,
								$xproduto_serie      ,
								'$consumidor_nome'  ,
								'$consumidor_cpf'   ,
								'$nota_fiscal'      ,
								$data_nf            ,
								'$codigo_fabricacao',
								'$tipo_os_cortesia' ,
								'$produto_type'     ,
								't'
							);";

				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				if (strlen($msg_erro) == 0) {
					if (strlen($os) == 0) {
						$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
						$os  = pg_result ($res,0,0);
					}
					$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
					$msg_erro = @pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}

				if (strlen ($msg_erro) == 0) {
					if (strlen($os) > 0) {
						$sql =	"INSERT INTO tbl_os_produto (
										os      ,
										produto ,
										serie   ,
										versao
									) VALUES (
										$os              ,
										$produto         ,
										'$produto_serie' ,
										'$produto_type'  
									);";
						$res      = @pg_exec($con,$sql);
						$msg_erro = @pg_errormessage($con);
						$msg_erro = substr($msg_erro,6);
					}
				}

				if (strlen($msg_erro) == 0) {
					if (strlen($os_produto) == 0) {
						$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
						$os_produto  = pg_result ($res,0,0);
					}
					$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
					$msg_erro = @pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}
			
			}else{
			
				########## A L T E R A   D A D O S ##########
				$sql =	"UPDATE tbl_os SET
							sua_os            = '$sua_os'           ,
							posto             = $posto              ,
							data_abertura     = $data_abertura      ,
							admin             = $login_admin        ,
							produto           = $produto            ,
							serie             = $xproduto_serie      ,
							consumidor_nome   = '$consumidor_nome'  ,
							consumidor_cpf    = '$consumidor_cpf'   ,
							nota_fiscal       = '$nota_fiscal'      ,
							data_nf           = $data_nf            ,
							codigo_fabricacao = $codigo_fabricacao  ,
							tipo_os_cortesia = '$tipo_os_cortesia'  
						WHERE os      = $os
						AND   fabrica = $login_fabrica";
# echo $sql."<br><br>";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				if (strlen ($msg_erro) == 0) {
					$sql =	"UPDATE tbl_os_produto SET
									produto = $produto         ,
									serie   = '$produto_serie' ,
									versao  = '$produto_type'  
							WHERE os         = $os
							AND   os_produto = $os_produto";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
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
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header ("Location: os_cortesia_item.php?os=$os");
			exit;
		}

		if(strlen ($msg_erro) > 0) {
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}

	}
}

if (strlen($_GET['os']) > 0) {
	$sql =	"SELECT tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.posto                                                ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os.fabrica                                              ,
					tbl_os.admin                                                ,
					tbl_os.produto                                              ,
					tbl_os.serie                                                ,
					tbl_os.codigo_fabricacao                                    ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_cpf                                       ,
					tbl_os.nota_fiscal                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_os.tipo_os_cortesia                                     ,
					tbl_os_produto.os_produto                                   ,
					tbl_os_produto.versao                                       ,
					tbl_produto.referencia                                      ,
					tbl_produto.voltagem                                        ,
					tbl_posto_fabrica.codigo_posto                              
			FROM	tbl_os
			JOIN	tbl_os_produto USING (os)
			JOIN	tbl_produto ON tbl_os.produto  = tbl_produto.produto
			JOIN	tbl_posto   ON tbl_posto.posto = tbl_os.posto
			JOIN	tbl_posto_fabrica	ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$os                 = pg_result($res,0,os);
		$sua_os             = pg_result($res,0,sua_os);
		$sua_os             = substr($sua_os, strlen($sua_os)-5, strlen($sua_os));
		$posto              = pg_result($res,0,posto);
		$data_abertura      = pg_result($res,0,data_abertura);
		$fabrica            = pg_result($res,0,fabrica);
		$admin              = pg_result($res,0,admin);
		$produto            = pg_result($res,0,produto);
		$produto_serie      = pg_result($res,0,serie);
		$codigo_fabricacao  = pg_result($res,0,codigo_fabricacao);
		$consumidor_nome    = pg_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_result($res,0,consumidor_cpf);
		$nota_fiscal        = pg_result($res,0,nota_fiscal);
		$data_nf            = pg_result($res,0,data_nf);
		$os_produto         = pg_result($res,0,os_produto);
		$tipo_os_cortesia   = pg_result($res,0,tipo_os_cortesia);
		$produto_referencia = pg_result($res,0,referencia);
		$produto_voltagem   = pg_result($res,0,voltagem);
		$posto_codigo       = pg_result($res,0,codigo_posto);
		$produto_type       = pg_result($res,0,versao);
	}
}

if (strlen($msg_erro) > 0) {
	$os                 = $_POST['os'];
	$sua_os             = $_POST['sua_os'];
	$posto              = $_POST['posto'];
	$posto_codigo       = trim($_POST['posto_codigo']);
	$data_abertura      = trim($_POST['data_abertura']);
	$os_produto         = $_POST['os_produto'];
	$produto            = $_POST['produto'];
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_voltagem   = trim($_POST['produto_voltagem']);
	$produto_type       = trim($_POST['produto_type']);
	$produto_serie      = trim($_POST['produto_serie']);
	$tipo_os_cortesia   = $_POST['tipo_os_cortesia'];
	$codigo_fabricacao  = trim($_POST['codigo_fabricacao']);
	$consumidor_nome    = trim($_POST['consumidor_nome']);
	$consumidor_cpf     = trim($_POST['consumidor_cpf']);
	$nota_fiscal        = trim($_POST['nota_fiscal']);
	$data_nf            = trim($_POST['data_nf']);
}

$title = "Cadastro de Ordem de Serviço do Tipo Cortesia - ADMIN"; 

$layout_menu = 'callcenter';


include "cabecalho.php";
?>

<script>
function fnc_pesquisa_produto (campo, campo2, tipo, voltagem) {
	var url = "";

	if (campo.value != "") {
		url = "produto_pesquisa.php?campo=" + campo.value + "&tipo=referencia";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");

		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.voltagem = document.frm_os.produto_voltagem;
		janela.focus();
	}
}

</script>

<? if (strlen ($msg_erro) > 0) { ?>
<br>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
		$produto_referencia = trim($_POST["produto_referencia"]);
		$produto_voltagem   = trim($_POST["produto_voltagem"]);
		$sqlT =	"SELECT tbl_lista_basica.type
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
				AND   tbl_produto.voltagem = '$produto_voltagem'
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type
				ORDER BY tbl_lista_basica.type;";
		$resT = pg_exec ($con,$sqlT);
		if (pg_numrows($resT) > 0) {
			$s = pg_numrows($resT) - 1;
			for ($t = 0 ; $t < pg_numrows($resT) ; $t++) {
				$typeT = pg_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			$msg_erro .= "<br>Selecione o Type: $result_type";
		}
	}

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

<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="top" align="left">
<? if (strlen($os) > 0) { ?>
		<td>
			<input type="hidden" name="sua_os" value="<? echo $sua_os; ?>">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
			<br>
			<input class="frm" type="text" name="sua_os" size="15" value="<? echo $posto_codigo.$sua_os; ?>" disabled>
		</td>
<? } ?>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
			<br>
			<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data de Abertura</font>
			<br>
			<input class="frm" type="text" name="data_abertura" size="15" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" readonly><br>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo da OS cortesia</font>
			<br>
			<select name='tipo_os_cortesia' class="frm">
				<? if(strlen($tipo_os_cortesia) == 0) echo "<option value=''></option>"; ?>
				<option value='Garantia' <? if($tipo_os_cortesia == 'Garantia') echo "selected"; ?>>Garantia</option>
				<option value='Sem Nota Fiscal' <? if($tipo_os_cortesia == 'Sem Nota Fiscal') echo "selected"; ?>>Sem Nota Fiscal</option>
				<option value='Fora da Garantia' <? if($tipo_os_cortesia == 'Fora da Garantia') echo "selected"; ?>>Fora da Garantia</option>
<? if($login_admin == 155) { ?><option value='Transformação' <? if($tipo_os_cortesia == 'Transformação') echo "selected"; ?>>Transformação</option><? } ?>
<? if($login_admin == 155) { ?><option value='Promotor' <? if($tipo_os_cortesia == 'Promotor') echo "selected"; ?>>Promotor</option><? } ?>
				<option value='Mau uso' <? if($tipo_os_cortesia == 'Mau uso') echo "selected"; ?>>Mau uso</option>
			</select>
			<br>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="bottom" align="left">
		<td>
			<input type="hidden" name="os_produto" value="<? echo $os_produto; ?>">
			<input type="hidden" name="produto_descricao">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência do Produto</font>
			<br>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
			&nbsp;
			<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem do Produto</font>
			<br>
			<input class="frm" type="text" name="produto_voltagem" size="14" value="<? echo $produto_voltagem ?>">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo</font>
			<br>

			<? 
			 GeraComboType::makeComboType($parametrosAdicionaisObject, $produto_type, "produto_type", array("class"=>"frm"));
      		 	 echo GeraComboType::getElement();
			?>
			
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nº de Série</font>
			<br>
			<input class="frm" type="text" name="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" >
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código fabricação</font>
			<br>
			<input class="frm" type="text" name="codigo_fabricacao" size="20" maxlength="20" value="<? echo $codigo_fabricacao ?>" >
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="top" align="left">
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
			<br>
			<input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ Consumidor</font>
			<br>
			<input class="frm" type="text" name="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
			<br>
			<input class="frm" type="text" name="nota_fiscal" size="8" maxlength="8" value="<? echo $nota_fiscal ?>">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
			<br>
			<input class="frm" type="text" name="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>"><br><font face='arial' size='1'>Ex.: 25/10/2004</font><br>
		</td>
	</tr>
</table>

<br>

<input type="hidden" name="btn_acao" value="">

<center><img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_os.btn_acao.value =='') { document.frm_os.btn_acao.value='gravar'; document.frm_os.submit() }else{ alert('Aguarde submissão') }" ALT="Gravar" style="cursor:pointer;"></center>

</form>

<? include "rodape.php";?>
