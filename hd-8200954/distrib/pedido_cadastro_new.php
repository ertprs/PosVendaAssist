<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$login_bloqueio_pedido = $_COOKIE['cook_bloqueio_pedido'];

$titulo = "Cadastro do pedido";
$login_fabrica = 10 ;
$posto = 4311;


$btn_acao = strtolower ($_POST['btn_acao']);
$btn_pre = ($_POST['btn_pre']);
$msg_erro = "";
$msg_debug = "";
$qtde_item = 40;



if ($btn_acao == "gravar"){
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];
	$fornecedor_distrib_posto = trim($_POST['fornecedor_distrib_posto']);

	if (strlen($condicao) == 0) {
		$aux_condicao = "null";
	}else{
		$aux_condicao = $condicao ;
	}

	if (strlen($pedido_cliente) == 0) {
		$aux_pedido_cliente = "null";
	}else{
		$aux_pedido_cliente = "'". $pedido_cliente ."'";
	}

	if (strlen($transportadora) == 0) {
		$aux_transportadora = "null";
	}else{
		$aux_transportadora = $transportadora ;
	}

	if (strlen($observacao_pedido) == 0) {
		$aux_observacao_pedido = "null";
	}else{
		$aux_observacao_pedido = "'$observacao_pedido'" ;
	}

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	}else{
		$sql = "SELECT	tipo_pedido
				FROM	tbl_tipo_pedido
				WHERE	descricao IN ('Faturado','Venda')
				AND		fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$aux_tipo_pedido = "'". pg_fetch_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}

	if(strlen($fornecedor_distrib_posto)==0)	$erro_msg .= "Por favor escolha um fornecedor<br>" ;

	$digitacao_distribuidor = "null";
	

	
	$res = pg_query ($con,"BEGIN TRANSACTION");


if(strlen($msg_erro)==0){
	if (strlen ($pedido) == 0 ) {
		$sql = "INSERT INTO tbl_pedido (
					posto          ,
					fabrica        ,
					condicao       ,
					pedido_cliente ,
					transportadora ,
					distribuidor   ,
					linha          ,
					tipo_pedido    ,
					digitacao_distribuidor,
					obs
				) VALUES (
					$posto              ,
					$login_fabrica      ,
					$aux_condicao       ,
					$aux_pedido_cliente ,
					$aux_transportadora ,
					$fornecedor_distrib_posto,
					$aux_linha          ,
					$aux_tipo_pedido    ,
					$digitacao_distribuidor,
					$aux_observacao_pedido
				)";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro) == 0){
			$res = @pg_query ($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido  = @pg_fetch_result ($res,0,0);
		}
	}else{
		$sql = "UPDATE tbl_pedido SET
					condicao       = $aux_condicao       ,
					pedido_cliente = $aux_pedido_cliente ,
					transportadora = $aux_transportadora ,
					linha          = $aux_linha          ,
					tipo_pedido    = $aux_tipo_pedido
				WHERE pedido  = $pedido
				AND   fabrica = $login_fabrica";

		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}
	if (strlen ($msg_erro) == 0) {
		$nacional  = 0;
		$importado = 0;
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$pedido_item     = trim($_POST['pedido_item_'     . $i]);
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			$preco           = trim($_POST['preco_'           . $i]);
			
			if (strlen ($peca_referencia) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}

			$qtde_anterior = 0;
			$peca_anterior = "";
			if (strlen($pedido_item) > 0 AND $login_fabrica==3){
				$sql = "SELECT peca,qtde 
						FROM tbl_pedido_item 
						WHERE pedido_item = $pedido_item";

				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (pg_num_rows ($res) > 0){
					$peca_anterior = pg_fetch_result($res,0,peca);
					$qtde_anterior = pg_fetch_result($res,0,qtde);
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0) {
				// delete
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $pedido_item
						AND		pedido = $pedido";

				$res = pg_query ($con,$sql);

			}

			if (strlen ($peca_referencia) > 0) {
				$peca_referencia = trim (strtoupper ($peca_referencia));
				$peca_referencia = str_replace ("-","",$peca_referencia);
				$peca_referencia = str_replace (".","",$peca_referencia);
				$peca_referencia = str_replace ("/","",$peca_referencia);
				$peca_referencia = str_replace (" ","",$peca_referencia);

				$sql = "SELECT  tbl_peca.peca   ,
								tbl_peca.origem
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica";

				$res = pg_query ($con,$sql);
				$peca   = pg_fetch_result ($res,0,peca);

				if (pg_num_rows ($res) == 0) {
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca   = pg_fetch_result ($res,0,peca);
					$origem = trim(pg_fetch_result ($res,0,origem));
				}

				if ($origem == "NAC" or $origem == "1") {
					$nacional = $nacional + 1;
				}

				if ($origem == "IMP" or $origem == "2") {
					$importado = $importado + 1;
				}


				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
									pedido ,
									peca   ,
									qtde
								) VALUES (
									$pedido ,
									$peca   ,
									$qtde
								)";

					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde
								WHERE pedido_item = $pedido_item";
					}
					$res = @pg_query ($con,$sql);
					$msg_erro = pg_errormessage($con);

					
					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_fetch_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica) ";
						$res = @pg_query ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}


					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Pedido $pedido foi finalizado com sucesso";
		header ("Location: pedido_finalizado.php?pedido=$pedido");
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}

}


#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
}

$title       = "Cadastro de Pedidos de Peças";

include "menu.php";


?>
<?include "javascript_calendario_new.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" language="javascript">

$().ready(function() {
	$("#fornecedor_distrib").autocomplete("nf_cadastro_ajax_busca.php?tipo=fornecedor", {
		minChars: 2,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			$("#fornecedor_distrib").focus();
			return row[0] ;
		},
		formatResult: function(row) {
		$("#fornecedor_distrib").focus();
			return row[0];
		}
	});

	$("#fornecedor_distrib").result(function(event, data, formatted) {
		$("#fornecedor_distrib").focus();
		$('#fornecedor_distrib_posto').val(data[1]);
	});
});

function fnc_pesquisa_peca_lista (peca_referencia,peca_descricao,tipo,peca_preco) {
	var url = "";
	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo;
	}

	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= peca_preco;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}


</SCRIPT>


<style type="text/css">
body {
	font: 80% Verdana,Arial,sans-serif;
	background: #FFF;
}


.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 1px 1px;
	font-size:12px;
	color:#FFFFFF;
}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; 
	margin: 10px auto;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}

.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px 30px; 
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.content h1 {
	color:black;
	font-size: 120%;
}

.extra {
	background:#BFDCFB;
	width: 600px;
	text-align: center;
	padding: 2px 2px;
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.extra span {
	color:#FF0D13;
	font-size:14px;
	font-weight:bold;
	padding-left:30px;
}

.error {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; 
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}
.error h1 {
	color:#FFFFFF;
	font-size:14px;
	font-size:normal;
	text-transform: capitalize;
}

.inicio {
	background:#8BBEF8;
	width: 600px;
	text-align: center;
	padding: 1px 2px;
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.inicio h1 {
	color:white;
	font-size: 105%;
	font-weight:bold;
}


.subinicio {
	background:#E1EEFD;
	width: 550px;
	text-align: center;
	padding: 1px 2px; 
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.subinicio h1 {
	color:white;
	font-size: 105%;
}


#tabela {
	font-size:12px;
}
#tabela td{
	font-weight:bold;
}


.xTabela{
	font-family: Verdana, Arial, Sans-serif;
	font-size:12px;
	padding:10px;
}

#layout{
	width: 650px;
	margin:0 auto;
}

ul#split, ul#split li{
	margin:50px;
	margin:0 auto;
	padding:0;
	width:600px;
	list-style:none
}

ul#split li{
	float:left;
	width:600px;
	margin:0 10px 10px 0
}

ul#split h3{
	font-size:14px;
	margin:0px;
	padding: 5px 0 0;
	text-align:center;
	font-weight:bold;
	color:white;
}
ul#split h4{
	font-size:90%
	margin:0px;
	padding-top: 1px;
	padding-bottom: 1px;
	text-align:center;
	font-weight:bold;
	color:white;
}

ul#split p{
	margin:0;
	padding:5px 8px 2px
}

ul#split div{
	background: #E6EEF7
}

li#one{
	text-align:left;
	
}

li#one div{
	border:1px solid #596D9B
}
li#one h3{
	background: #7392BF;
}

li#one h4{
	background: #7392BF;
}

.coluna1{
	width:250px;
	font-weight:bold;
	display: inline;
	float:left;
}

</style>



<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	?>
	<div id="layout">
	<div class="error">
	<? echo $erro . $msg_erro; ?>
	</div>
	</div>
<? } ?>


<?

if(strlen($msg)>0){
	echo "<div style='background:#FFFF99'><font color='#000099' size=4><h3>$msg</h3></font></div>";
}

?>

<br>

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido; ?>">
<br>


<ul id="split">
<li id="one">
<h3><? echo $frase; ?></h3>
<div>
		<p><span class='coluna1'>Fornecedor</span><input type='text' name='fornecedor_distrib' id='fornecedor_distrib' size ='45' value='<?=$fornecedor_distrib?>'>
		<input type='hidden' name='fornecedor_distrib_posto' id='fornecedor_distrib_posto' value='<?=$fornecedor_distrib_posto?>' ></p>
		<p><span class='coluna1'>Tipo de Pedido</span>
		<?
			echo "<select size='1' name='tipo_pedido' class='frm'>";
			$sql = "SELECT   tipo_pedido,descricao
					FROM     tbl_tipo_pedido
					WHERE    (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
					       OR tbl_tipo_pedido.descricao ILIKE '%Venda%')
					AND      tbl_tipo_pedido.fabrica = $login_fabrica
					AND      (garantia_antecipada is false or garantia_antecipada is null)
					ORDER BY tipo_pedido;";
			$res = pg_query ($con,$sql);

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "'";
				if (pg_fetch_result ($res,$i,tipo_pedido) == $tipo_pedido){
					echo " selected";
				}
				echo ">" . pg_fetch_result($res,$i,descricao) . "</option>";
			}
			echo "</select>";
		?>
		</p>

		<p><span class='coluna1'>Pré-pedido</span>
		<?
			echo "<select size='1' name='marca' class='frm'>";
			$sql = "SELECT   marca,
							nome
					FROM tbl_marca
					WHERE fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				echo "<option value='" . pg_fetch_result($res,$i,marca) . "'";
				if (pg_fetch_result ($res,$i,marca) == $tipo_pedido){
					echo " selected";
				}
				echo ">" . pg_fetch_result($res,$i,nome) . "</option>";
			}
			echo "</select>";
			?>
		<input type='submit' name='btn_pre' value='Pré-Pedido' >
		</p>

		<h4>Peças</h4>

		<p>
		<table border="0" cellspacing="0" cellpadding="2" align="center" class='xTabela'>
			<tr height="20" bgcolor="#CDDBF1">
				<td align='center'>
				Referência Componente</td>
				<td align='center'>Descrição Componente</font></td>
				<td align='center'>Qtde</td>
				<td align='center'>Preço</td>
			</tr>

			<?
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				if (strlen($pedido) > 0 AND strlen ($msg_erro) == 0){
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";
					$res = pg_query ($con,$sql);

					if (pg_num_rows($res) > 0) {
						$pedido_item     = trim(@pg_fetch_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_fetch_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_fetch_result($res,$i,descricao));
						$qtde            = trim(@pg_fetch_result($res,$i,qtde));
						$preco           = trim(@pg_fetch_result($res,$i,preco));
						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');
					}else{
						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}else{
					$pedido_item     = $_POST["pedido_item_"     . $i];
					$peca_referencia = $_POST["peca_referencia_" . $i];
					$peca_descricao  = $_POST["peca_descricao_"  . $i];
					$qtde            = $_POST["qtde_"            . $i];
					$preco           = $_POST["preco_"           . $i];
				}

				if(isset($btn_pre) > 0) {
					$marca = $_POST['marca'];
					
					if(!empty($marca)) {
						$sql = " SELECT	
									referencia,descricao,preco
								from tbl_peca
								join tbl_tabela_item USING(peca)
								where (qtde_disponivel_site <= 0 or qtde_disponivel_site < qtde_minima_estoque)
								and promocao_site
								and fabrica=$login_fabrica
								and marca= $marca
								ORDER BY peca
								limit 1 offset $i;";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$peca_referencia = trim(@pg_fetch_result($res,0,referencia));
							$peca_descricao  = trim(@pg_fetch_result($res,0,descricao));
							$preco           = trim(@pg_fetch_result($res,0,preco));
							$preco = number_format($preco,2,",",".");
						}else{
							$peca_referencia = "";
							$peca_descricao  = "";
							$qtde            = "";
							$preco           = "";
						}
					}else{
						$peca_referencia = "";
						$peca_descricao  = "";
						$qtde            = "";
						$preco           = "";
					}
				}else{
						$peca_referencia = "";
						$peca_descricao  = "";
						$qtde            = "";
						$preco           = "";
				}

				$peca_referencia = trim ($peca_referencia);

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($tem_obs) $cor='#FFCC33';
			?>
				<tr bgcolor="<? echo $cor ?>">
					<td align='left' nowrap>
						<input type="hidden" name="pedido_item_<? echo $i ?>" size="15" value="<? echo $pedido_item; ?>">
						<input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" value="<? echo $peca_referencia; ?>">
						<img src='../imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' 
						 onclick="javascript: fnc_pesquisa_peca_lista(window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<? echo $i ?>,'referencia',window.document.frm_pedido.preco_<? echo $i ?>)" >
					</td>
					<td align='left' nowrap>
						<input type="hidden" name="posicao">
						<input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="30" value="<? echo $peca_descricao ?>"><img src='../imagens/btn_lupa_novo.gif' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista(window.document.frm_pedido.peca_referencia_<? echo $i ?>,window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao',window.document.frm_pedido.preco_<? echo $i ?>)" >
					</td>
					<td align='center'>
						<input class="frm" type="text" name="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>">
					</td>

					<td align='center'>
						<input class="frm" type="text" name="preco_<? echo $i ?>" size="10"  value="<? echo $preco ?>" readonly style='text-align:right'>
					</td>

				</tr>

			<?
			}
			?>
			</table>
		</p>
		<p><center>
		<input type="hidden" name="btn_acao" value="">
		<img src='../imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
		</center>
		</p>
</div>
</li>
</ul>

</form>
<br clear='both'>
<p>

<? include "rodape.php"; ?>
