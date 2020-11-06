<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$layout_menu = "pedido";
$title = "Dados da Peça";


// Gerar Excel - Black


function retiraVirgula($str){

		$str = str_replace(",", "", $str);
		$str = str_replace(";", "", $str);

		return $str;

	}


if($login_fabrica == 1){
	if($_POST["gerar_excel"]){

		$sql = "
			SELECT tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_produto.referencia_fabrica,
			tbl_produto.referencia AS referencia_produto,
			CASE
			WHEN tbl_peca_fora_linha.peca notnull THEN
				'OBSOLETO'
			WHEN tbl_depara.peca_de notnull THEN
				'SUBST'
			WHEN tbl_peca.bloqueada_venda IS TRUE AND tbl_peca.bloqueada_garantia IS TRUE THEN
				'INPNAT'
			ELSE
				' '
			END AS status,
			tbl_lista_basica.qtde
			FROM tbl_lista_basica
			JOIN tbl_peca ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_peca_fora_linha ON tbl_peca.peca = tbl_peca_fora_linha.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de AND tbl_depara.fabrica = $login_fabrica
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND (tbl_lista_basica.ativo is not false)
			AND tbl_peca.produto_acabado is not true ORDER BY tbl_produto.referencia
		";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_pecas_lista_basicas-{$data}.csv";

			$file = fopen("/tmp/{$fileName}", "w");

			header('Content-Type: application/csv; charset=iso-8859-1');
			header('Content-Disposition: attachment; filename="/tmp/{$fileName}"');

			$thead = "Código da Peça; Descrição da Peça; Status da Peça; Código do Produto Telecontrol; Código do Produto Interno; Quantidade \n";

			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$referencia_peca 		= pg_fetch_result($res, $i, 'referencia');
				$referencia_interna 	= pg_fetch_result($res, $i, 'referencia_fabrica');
				$referencia_produto 	= pg_fetch_result($res, $i, 'referencia_produto');
				$descricao 				= pg_fetch_result($res, $i, 'descricao');
				$status 				= pg_fetch_result($res, $i, 'status');
				$quantidade				= pg_fetch_result($res, $i, 'qtde');

				$referencia_peca		= retiraVirgula($referencia_peca);
				$referencia_interna		= retiraVirgula($referencia_interna);
				$referencia_produto		= retiraVirgula($referencia_produto);
				$descricao				= retiraVirgula($descricao);
				$status					= retiraVirgula($status);
				$quantidade				= retiraVirgula($quantidade);

				if(strlen($referencia_peca) > 0){

					$body .="{$referencia_peca}; {$descricao}; {$status}; {$referencia_produto}; {$referencia_interna}; {$quantidade} \n";

				}
			}

			fwrite($file, $body);
			fwrite($file, "Total de ".pg_num_rows($res)." registros");

			fclose($file);

			if(isset($_POST['peca_consulta_dados']) && $_POST['peca_consulta_dados'] == "sim"){
				
				header("location: xls/{$fileName}");
			}

			if (file_exists("/tmp/{$fileName}")) {
				system("cp /tmp/{$fileName} admin/xls/{$fileName}");

				echo "admin/xls/{$fileName}";
			}
			

		}

		exit;

	}
}

include "cabecalho.php";
include "javascript_pesquisas.php";



?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>

<script language="JavaScript">

//FAZ A BUSCA DA PEÇA
function fnc_pesquisa_peca2 (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_peca.referencia;
		janela.descricao= document.frm_peca.descricao;
		janela.focus();
	}
}
</script>




<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}

</style>
<style type="text/css">

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #485989;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}


.btn_excel {
  cursor: pointer;
  width: 185px;
  margin: 0 auto;
}

.btn_excel span {
  display: inline-block;
}

.btn_excel span img {
  width: 20px;
  height: 20px;
  border: 0px;
  vertical-align: middle;
}

.btn_excel span.txt {
  color: #FFF; 
  font-size: 14px;
  font-weight: bold;
  border-radius: 4px 4px 4px 4px;
  border-width: 1px;
  border-style: solid;
  border-color: #4D8530;
  background: -moz-linear-gradient(top, #559435 0%, #63AE3D 72%);
  background: -webkit-linear-gradient(top, #559435 0%, #63AE3D 72%);
  background: -o-linear-gradient(top, #559435 0%, #63AE3D 72%);
  background: -ms-linear-gradient(top, #559435 0%, #63AE3D 72%);
  background: linear-gradient(top, #559435 0%, #63AE3D 72%);
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#559435', endColorstr='#63AE3D',GradientType=1 );
  line-height: 18px;
  padding-right: 3px;
  padding-left: 3px;
}


</style>




<body>

<div id="wrapper">
<form name="frm_peca" method="post" action="<? $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<? echo $peca ?>">

<!-- TABELA CONTENDO OS CAMPOS PARA BUSCA DA PEÇA -->
<br>
<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF' style='font-family: verdana; font-size: 12px'>
<tr align='center'>
		<td bgcolor='#D9E2EF'><b>Referência</b> (*)</td>
		<td bgcolor='#D9E2EF'><b>Descrição</b> (*)</td>
</tr>
<tr align='center'>
		<td bgcolor='#FfFfFF'>
			<input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20" onfocus="if (document.getElementById('erro_fora_linha')) {  document.getElementById('erro_fora_linha').innerHTML = ''; }"><a href="javascript: fnc_pesquisa_peca2 (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_lupa.gif" ></a></td>
		<td bgcolor='#FfFfFF'>
		<input class='frm' size='50' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"  onfocus="if (document.getElementById('erro_fora_linha')) { document.getElementById('erro_fora_linha').innerHTML = ''; }"><a href="javascript: fnc_pesquisa_peca2 (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_lupa.gif" ></a></td>
</tr>
<tr>
	<td colspan='2' align='center' bgcolor='#FFFFFF'><INPUT TYPE="submit" name='btn_busca' value='Buscar'></td>
</tr>
</table>

</form>
<!-- FIM -->

<?
if (strlen($_POST["btn_busca"]) > 0) {
	$btnacao = trim($_POST["btn_busca"]);
}

//FAZ A PESQUISA DA PEÇA PRA SABER SE A MESMA ESTÁ CADASTRADA NO NOSSO BANCO DE DADOS.
if( $btnacao == 'Buscar'){ 
	$referencia = trim($_POST['referencia']);
	if(($referencia > 0) OR ($referencia == 0)) {
	//OBTEM O CODIGO DA PEÇA DO BANCO(SEQUENCE)
		$sql = "SELECT peca FROM tbl_peca
					WHERE	referencia = '$referencia'
					AND		fabrica = $login_fabrica "; 
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0){
			$peca  = trim(pg_result($res,0,peca));
		}else{ echo "<FONT COLOR=\"#FF0000\"><B>Peça não encontrada.</B></FONT>"; exit; }
	}
}



$peca = trim($_POST["referencia"]);


if(($peca > 0) OR ($peca != null)){

if (strlen($referencia) > 0){
		if( $btnacao == 'Buscar'){ 
		//OBTEM O CODIGO DA PEÇA DO BANCO(SEQUENCE)
			$sql = "SELECT peca FROM tbl_peca
						WHERE	referencia = '$referencia'
						AND		fabrica = $login_fabrica "; 
			$res = pg_exec($con,$sql);
				
			if (pg_numrows($res) > 0){
				$peca  = trim(pg_result($res,0,peca));
			}//FIM
			
			
			//CARREGA DADOS 
			$sql = "SELECT tbl_peca.peca                   ,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							tbl_peca.ipi                   ,
							tbl_peca.garantia_diferenciada ,
							tbl_peca.devolucao_obrigatoria ,
							tbl_peca.item_aparencia        ,
							tbl_peca.bloqueada_garantia    ,
							tbl_peca.acessorio             ,
							tbl_peca.aguarda_inspecao      ,
							tbl_peca.peca_critica          ,
							tbl_peca.produto_acabado
					FROM    tbl_peca
					WHERE   fabrica = $login_fabrica
					AND     tbl_peca.peca = '$peca' ";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$peca                     = trim(pg_result($res,0,peca));
				$referencia               = trim(pg_result($res,0,referencia));
				$descricao                = trim(pg_result($res,0,descricao));
				$ipi                      = trim(pg_result($res,0,ipi));
				$garantia_diferenciada    = trim(pg_result($res,0,garantia_diferenciada));
				$devolucao_obrigatoria    = trim(pg_result($res,0,devolucao_obrigatoria));
				$item_aparencia           = trim(pg_result($res,0,item_aparencia));
				$bloqueada_garantia       = trim(pg_result($res,0,bloqueada_garantia));
				$acessorio                = trim(pg_result($res,0,acessorio));
				$aguarda_inspecao         = trim(pg_result($res,0,aguarda_inspecao));
				$peca_critica             = trim(pg_result($res,0,peca_critica));
				$produto_acabado          = trim(pg_result($res,0,produto_acabado));
			}//FIM
			
			
			
			//VERIFICA SE A PEÇA ESTÁ FORA DE GARANTIA - SE SIM MOSTRA MENSAGEM.
				$sql = "SELECT peca,libera_garantia FROM tbl_peca_fora_linha
						WHERE	fabrica = $login_fabrica
						AND		peca = '$peca' ";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0){

				$mlibera_garantia = pg_result($res,0,libera_garantia);			
			?>
			<br>
			<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#FF0000' id='erro_fora_linha'>
			<tr  style='font-size:14px'>
				<? if ($mlibera_garantia == "t"){ ?>
					<td><B><FONT COLOR="#FFFFFF">Peça fora de linha - Atendimento somente para garantia</FONT></B></td> <?}else{?>
					<td><B><FONT COLOR="#FFFFFF">Peça fora de linha</FONT></B></td>
					<?}?>
			</tr>
			</table><br>
			<? } ?>
			<!-- FIM DA TABELA Q VERIFICA SE PECA ESTA FORA DE LINHA-->

			<?php if ($login_fabrica <> 1){ ?>			
				<!-- PRIMEIRA TABELA -->
				<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
				<tr bgcolor='#D9E2EF' style='font-size:10px'>
					<td align='center' height='25' style='font-size: 14px;' colspan='8'><B>Informações sobre a peça: <? echo "$referencia"; ?></B></td>
				</tr>
				</table>
				<!-- FIM  DA PRIMEIRA TABELA-->

				<!-- SEGUNDA TABELA - CONTEM AS INFORMAÇOES DA PEÇA - SE TIVER COLOCA UM "X" CASO CONTRARIO "-" -->
				<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
				<tr  bgcolor='#D9E2EF' style='font-size:10px'>
					<td align='center' colspan='2'><b>Garantia Diferenciada (meses)</b></td>
					<td align='center'><b>Devolução Obrigatória</b></td>
					<td align='center'><b>Item de Aparência</b></td>
					<td align='center'><b>Bloqueada para Garantia</b></td>
				</tr>
				<tr class='Conteudo' bgcolor='#FFFFFF' >
					<td align='center' colspan='2'>
						<? echo $garantia_diferenciada ?>
					</td>

					<td align='center'>
						<? if ($devolucao_obrigatoria == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>

					<td align='center'>
						<? if ($item_aparencia == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>

					<td align='center'>
						<? if ($bloqueada_garantia == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>
				</tr>
				<tr bgcolor='#D9E2EF' style='font-size:10px'>
					<td align='center'><b>IPI</b> (*)</td>
					<td align='center'><b>Acessório</b></td>
					<td align='center'><b>Aguarda Inspeção</b></td>
					<td align='center'><b>Peça Crítica</b></td>
					<td align='center'><b>Produto Acabado</b></td>
				</tr>
				<tr class='Conteudo' bgcolor='#FFFFFF' >
				
					<td align='center'>
						<? echo "$ipi"; ?>
					</td>
					
					<td align='center'>
						<? if ($acessorio == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>

					<td align='center'>
						<? if ($aguarda_inspecao == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>

					<td align='center'>
						<? if ($peca_critica == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>

					<td align='center'>
						<? if ($produto_acabado == 't' ) {echo " <B>X</B> ";}else{echo " - ";} ?>
					</td>
				</tr>
				</table>
				<!-- FIM DA SEGUNDA TABELA -->
			<?php } ?>
			
			<?
			If($peca > 0){
				
				if ($login_fabrica <> 1){
						//FAZ A VERIFICACAO SE A PECA FOI SUBSTITUIDA - DE-PARA
						
						$sql = "SELECT  tbl_peca.descricao, tbl_peca.referencia
								FROM tbl_peca 
								JOIN tbl_depara ON tbl_depara.para = tbl_peca.referencia
								WHERE tbl_depara.fabrica = $login_fabrica
								AND tbl_depara.peca_de = '$peca' ";
						
					
						$res = pg_exec($con,$sql);
						
						//FIM

						if (pg_numrows($res) > 0){
							//SE ENCONTROU REGISTRO DE "DE - PARA" EXIBE TABELA COM AS INFORMAÇÕES SOBRE A PEÇA PELA QUAL FOI TROCADA
							echo "<br><br>";
							echo "<TABLE width='500' align='center' bgcolor='#F0F7FF' border='0' cellspacing='1' cellpadding='2' style='font-size: 10px'>";
							echo "<TR style='font-size:12px'>";
							echo "	<TD COLSPAN='2' align='center' bgcolor='#D9E2EF'><B>Peça Substituida por</B></TD>";
							echo "</TR>";

							echo "<TR class='menu_top' bgcolor='#D9E2EF' align='center'>";
							echo "<TD width='100'>Referência</TD>";
							echo "<TD >Descrição</TD>";
							echo "</TR>";

							for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
								$referencia_para   = trim(pg_result($res,$i,referencia));
								$descricao_para = trim(pg_result($res,$i,descricao));

								$cor = '#F1F4FA';

								echo "<TR align='center' bgcolor='FFFFFF'>";
								echo "<TD >$referencia_para</TD>";
								echo "<TD >$descricao_para</TD>";
								echo "</TR>";
							}echo "</TABLE>";
							//FIM DA TABELA - "DE - PARA"
						}

					//	$preco_com_ipi = $preco * (1 + $ipi/100);
		?>
					<!-- INICIO DA TABELA  DE IMAGEM -->
					<?
					$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
					if (!empty($xpecas->attachListInfo)) {

						$a = 1;
						foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
						    $fotoPeca = $vFoto["link"];
						    if ($a == 1){break;}
						}
					?>
						<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
							<tr  bgcolor='#D9E2EF' style='font-size:10px'>
								<td align='center'><b>Imagem da peça - <?echo $referencia ;?></b></td>
							</tr>
							<tr class='Conteudo' bgcolor='#FFFFFF' >
								<td align='center'>
								<img width="180" src='<?php echo $fotoPeca; ?>' border='0'>
								</td>
						</table>
					<?php
					} else {
						if ($dh = opendir('imagens_pecas/media/')) {
							$contador=0;
							while (false !== ($filename = readdir($dh))) {
								if($contador == 1) break;
								if (strpos($filename,$referencia) !== false){
									$contador++;
									//$peca_referencia = ntval($peca_referencia);
									$po = strlen($referencia);
									if(substr($filename, 0,$po)==$referencia){?>
										<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
										<tr  bgcolor='#D9E2EF' style='font-size:10px'>
										<td align='center'><b>Imagem da peça - <?echo $referencia ;?></b></td>
										</tr>
										<tr class='Conteudo' bgcolor='#FFFFFF' >
										<td align='center'>
										<img src='imagens_pecas/media/<?echo $filename; ?>' border='0'>
										</td>
										</table>
						<?			}
								}
							}
						}
					}
					?>

					<!-- INICIO DA TABELA  DE IMAGEM -->
					<?
					if($login_fabrica == 3){
						//MOSTRA O VALOR DA PECA NAS TABELAS CORRESPONDENTES.
						$sql = "SELECT DISTINCT tbl_tabela_item.tabela_item,
										tbl_tabela.tabela          ,
										tbl_tabela.sigla_tabela    ,
										tbl_tabela_item.preco      ,
										tbl_tabela.descricao       ,
										tbl_peca.referencia
								FROM    tbl_tabela
								JOIN    tbl_tabela_item USING (tabela)
								JOIN    tbl_peca        ON tbl_peca.peca = tbl_tabela_item.peca
								JOIN    tbl_posto_linha USING (tabela)
								JOIN    tbl_linha    ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
								WHERE   tbl_tabela_item.peca = $peca
								AND     tbl_posto_linha.posto = $login_posto
								AND     tbl_tabela.ativa   = 't'
								AND     tbl_tabela.fabrica   = $login_fabrica";
						$res = pg_exec ($con,$sql);

						if (pg_numrows($res) > 0) {
							echo "<br><br><TABLE width='500' align='center' bgcolor='#F0F7FF' border='0' cellspacing='1' cellpadding='2' style='font-size: 10px'>";
							echo "<TR style='font-size:12px'>";
							echo "	<TD COLSPAN='4' align='center' bgcolor='#D9E2EF'> <B>Tabela(s) e preço(s) da peça</B> </TD>";
							echo "</TR>";
							echo "<TR class='menu_top' align='center' bgcolor='#D9E2EF'>";
							echo "	<TD > Tabela</TD>";
							echo "	<TD > Preço </TD>";
							echo "	<TD > IPI</TD>";
							echo "	<TD > Total </TD>";
							echo "</TR>";



							for ($y = 0 ; $y < pg_numrows($res) ; $y++){
								$sigla           = trim(pg_result($res,$y,sigla_tabela));
								$preco           = trim(pg_result($res,$y,preco));
								$descricao       = trim(pg_result($res,$y,descricao));
								echo "	<TR bgcolor='FFFFFF'>";
								echo "	<TD align='center'>$descricao</TD>";
								echo "	<TD align='center'>R$ ". number_format($preco,2,",",".");
								echo "	<TD align='center'>$ipi</TD>";
								//calculo do IPI
								$preco_com_ipi = $preco * (1 + $ipi/100);
								echo "	<TD align='center'>R$ ". number_format($preco_com_ipi,2,",",".");
								echo "	</TR>";
								}echo "</TABLE>";
						}
					}	
				}
				//MOSTRA OS PRODUTOS QUE CONTEM ESSA PEÇA
				$sql = "SELECT DISTINCT tbl_produto.referencia, tbl_produto.descricao
								FROM tbl_lista_basica
								JOIN tbl_produto USING (produto)
								WHERE tbl_lista_basica.fabrica = $login_fabrica
								AND   tbl_lista_basica.peca = '$peca' limit 30";

				$res = pg_exec ($con,$sql);
				
				if(pg_numrows($res) > 0)
				{
					echo "<br><br><TABLE width='500' align='center' border='0' cellspacing='1' bgcolor='#F0F7FF' cellpadding='2' style='font-size: 10px'>";
					echo "<tr bgcolor='#D9E2EF' style='font-size:12px'>";
					echo "	<td colspan='2' align='center'><B>Produto(s) que contém a peca</B></td>";
					echo "</tr>";
					echo "<tr class='menu_top' bgcolor='#D9E2EF' align='center'>";
					echo "	<td width='150'>Referência</td>";
					echo "	<td>Descrição</td>";
					echo "</tr>";

					for ($i = 0 ; $i < pg_numrows($res) ; $i++){
						$produto           = trim(pg_result($res,$i,referencia));
						$descricao         = trim(pg_result($res,$i,descricao));
						echo "<tr bgcolor='FFFFFF'>";
						echo "	<td align='center'>$produto<br>";
						echo "	<td>$descricao<br>";
						echo "</tr>";
					}echo "</table>";
				}//FIM - DA TABELA COM OS PRODUTOS QUE CONTEM A PEÇA.
			}	
		}
	}
}
echo "<BR><BR>";

if($login_fabrica == 1){
?>

<div style="position:fixed;margin-left:47%;display:none;" id="loading">

	<img src="admin/imagens/loading_img.gif" />
</div>

<script>
function loading (display) {
    		switch (display) {
    			case "show":
    				$("#loading").show();
					$("#loading_action").val("t");
    				break;

    			case "hide":
    				$("#loading").hide();
					$("#loading_action").val("f");
    				break;
    		}
    	}

function ajaxAction () {
    		if ($("#loading_action").val() == "t") {
    			alert("Espere o processo atual terminar!");
    			return false;
    		} else {
    			return true;
    		}
    	}

	$(function () {
    		$("#gerar_excel").click(function () {
    			if (ajaxAction()) {
    				var json = $.parseJSON($("#jsonPOST").val());
    				json["gerar_excel"] = true;

	    			$.ajax({
	    				url:"peca_consulta_dados.php",
	    				type: "POST",
	    				data: json,
	    				beforeSend: function () {
	    					loading("show");
	    				},
	    				complete: function (data) {
	    					window.open(data.responseText, "_blank");

	    					loading("hide");
	    				}
	    			});
    			}
    		});

    		$("input[type!=radio][type!=checkbox], select, textarea").bind("valid", function (e, obj) {
    			if ($.trim($(obj).val()).length > 0) {
    				$(obj).parents("div.control-group.error").removeClass("error");
    			}
    		});

    		$("input[type!=radio][type!=checkbox], select, textarea").change(function () {
    			$(this).trigger("valid", [ $(this) ]);
    		});
    	});



	// $(function(){
	// 	$("#gerar_excel").click(function(){
	// 		$.ajax({
	// 			url:"peca_consulta_dados.php",
	// 			type:"POST",
	// 			data:{gerar_excel:true},
	// 			beforeSend: function(){
	// 				$("#loading").show();
	// 			},
	// 			complete:function(data){
	// 				data = data.responseText;
	// 				 if(data != undefined && data.length > 0 ){
	// 				 	//window.open(data);
	// 				 }else{
	// 				 	alert("ERRO ao Gerar Excel!");
	// 				 }
	// 				$("#loading").hide();	
	// 			}
	// 		});

	// 	});
	// });
</script>
<!--
<div id='gerar_excel' class="btn_excel">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
</div>
-->
<?
if($login_fabrica == 1){
	?>
<div id='gerar_excel' >
				<input type="hidden" id="jsonPOST" value='{"gerar_excel" : true}' />
				<span><!-- <img src='imagens/excel.png' /> --></span>
				<button class="btn">Gerar Excel - Peças que Constam em Lista Básica de Produtos</button>
			</div>
<?
}
}
include "rodape.php"; 

?>
