<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$liberar_preco = true ;

$layout_menu = "callcenter";
$title = "CONSULTA VALORES DA TABELA DE PREÇOS";
include 'cabecalho.php';

$btn_acao = $_GET['btn_acao'];
if(strlen($btn_acao)>0){

    if($_POST['tabela']) $tabela = $_POST['tabela'];
    if($_GET['tabela'])  $tabela = $_GET['tabela'];

    if($_POST['referencia_produto']) $referencia_produto = $_POST['referencia_produto'];
    if($_GET['referencia_produto'])  $referencia_produto = $_GET['referencia_produto'];

    if($_POST['descricao_produto']) $descricao_produto = $_POST['descricao_produto'];
    if($_GET['descricao_produto'])  $descricao_produto = $_GET['descricao_produto'];

    if($_POST['voltagem_produto']) $voltagem_produto = $_POST['voltagem_produto'];
    if($_GET['voltagem_produto'])  $voltagem_produto = $_GET['voltagem_produto'];

    if($_POST['referencia_peca']) $referencia_peca = $_POST['referencia_peca'];
    if($_GET['referencia_peca'])  $referencia_peca = $_GET['referencia_peca'];

    if($_POST['descricao_peca']) $descricao_peca = $_POST['descricao_peca'];
    if($_GET['descricao_peca'])  $descricao_peca = $_GET['descricao_peca'];

    $posto_codigo = $_REQUEST['posto_codigo'];
    $posto_nome = $_REQUEST['posto_nome'];


    if(strlen($posto_codigo)==0 OR strlen($posto_nome)==0){
        $msg_erro = "Preencha os Parâmetros para Pesquisa";
    }

    if (strlen($posto_codigo) > 0){

        $sql = "SELECT codigo_posto, posto ,contato_estado from tbl_posto_fabrica where codigo_posto= '$posto_codigo' AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $posto = pg_fetch_result($res,0,'posto');
            $contato_estado = pg_fetch_result($res,0,'contato_estado');

            $sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
                            tbl_tipo_posto.tipo_posto                   ,
							tbl_posto_fabrica.pedido_faturado           ,
                            tbl_tipo_posto.distribuidor                 ,
                            tbl_tipo_posto.acrescimo_tabela_base        ,
                            tbl_tipo_posto.tx_administrativa            ,
                            tbl_tipo_posto.acrescimo_tabela_base_venda  ,
                            tbl_tipo_posto.desconto_5estrela            ,
                            tbl_tipo_posto.descontos[1] AS desconto1    ,
							tbl_tipo_posto.descontos[2] AS desconto2    ,
                            tbl_condicao.acrescimo_financeiro           ,
                          	tbl_icms.indice as icms,
							tbl_peca_icms.indice as icms_peca,
                            tbl_posto_fabrica.pedido_em_garantia
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
                                                and tbl_posto_fabrica.fabrica = $login_fabrica
                    JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
                    JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                    JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
                                                and tbl_condicao.condicao     = 50
                    JOIN    tbl_icms             on tbl_icms.estado_destino   = tbl_posto.estado
                    LEFT JOIN tbl_peca_icms      on tbl_peca_icms.estado_destino = tbl_posto_fabrica.contato_estado
                    WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
                    AND     tbl_posto_fabrica.posto   = $posto
                    AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $icms                        = pg_fetch_result($res, 0, 'icms');
                $icms_peca                   = pg_fetch_result($res, 0, 'icms_peca');
                $descricao                   = pg_fetch_result($res, 0, 'descricao');
                $acrescimo_tabela_base       = pg_fetch_result($res, 0, 'acrescimo_tabela_base');
                $acrescimo_tabela_base_venda = pg_fetch_result($res, 0, 'acrescimo_tabela_base_venda');
                $acrescimo_financeiro        = pg_fetch_result($res, 0, 'acrescimo_financeiro');
                $pedido_em_garantia          = pg_fetch_result($res, 0, 'pedido_em_garantia');
                $distribuidor       		 = pg_fetch_result($res, 0, 'distribuidor');
                $tx_administrativa       	 = pg_fetch_result($res, 0, 'tx_administrativa');
                $desconto_5estrela       	 = pg_fetch_result($res, 0, 'desconto_5estrela');
                $desconto1           		 = pg_fetch_result($res, 0, 'desconto1');
				$desconto2           		 = pg_fetch_result($res, 0, 'desconto2');
                $acrescimo_tabela_base       = pg_fetch_result($res, 0, 'acrescimo_tabela_base');
				$pedido_faturado			 = pg_fetch_result($res, 0, 'pedido_faturado');

				if(strlen($desconto_5estrela)==0 ){
					$desconto_5estrela = 1;
				}

				if(strlen($desconto1)==0 ){
					$desconto1 = 1;
				}

				if(strlen($desconto2)==0 ){
					$desconto2 = 1;
				}

            }
        }else{
            $msg_erro = "Posto não Encontrado";
        }
    }else{
        $msg_erro = "Posto não Encontrado";
    }

    if(strlen($posto) > 0){
        $sqlP_adicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
        $resP_adicionais = pg_query($con, $sqlP_adicionais);

        if (pg_num_rows($resP_adicionais) > 0) {
            $parametrosAdicionais = json_decode(pg_fetch_result($resP_adicionais, 0, "parametros_adicionais"), true);
            extract($parametrosAdicionais);

            $tipo_contribuinte = utf8_decode($tipo_contribuinte);

            if($tipo_contribuinte <> 't'){
                $tipo_contribuinte = 'f';
            }
        }else{
            $tipo_contribuinte = 'f';
        }
    }
}
?>



<script language="JavaScript">

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produtoXXX (referencia,descricao,tabela) {
	var url = "";
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&retorno=<?echo $PHP_SELF?>";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.tabela     = tabela;
		janela.focus();
	}
}
</script>

<style>
.letras {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: bold;
	border: 0px solid;
	color:#007711;
	background-color: #ffffff
}

.lista {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: normal;
	border: 0px solid;
	color:#000000;
}

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
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
</style>
<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->


<script language=JavaScript>
//script from www.argnet.tk
function blockError(){return true;}
window.onerror = blockError;
</script>

<script language=JavaScript>
function disableselect(e){
    return false
}
function reEnable(){
    return true
}

//if IE4+
document.onselectstart=new Function ("return false")
 //if NS6
//if (window.sidebar){
  //  document.onmousedown=disableselect
   // document.onclick=reEnable
//}
</script>

<? include "javascript_pesquisas.php" ?>

<form method='get' action='<? echo $PHP_SELF ?>' name='frm_tabela'>

<table align="center" width="700" class="formulario" border='0'>
<?php if(strlen($msg_erro)>0){ ?>
		<tr class="msg_erro"><td colspan="3"><?php echo $msg_erro; ?> </td></tr>
<?php } ?>
<tr class="titulo_tabela"><td colspan="3">Parâmetros de Pesquisa</td></tr>
<tr><td colspan="3">&nbsp;</td></tr>
<tr>
	<td width="60">&nbsp;</td>
	<td nowrap width="235">
		Código do Posto<br>
		<input class="frm" type="text" name="posto_codigo" size="20" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto(document.frm_tabela.posto_codigo,document.frm_tabela.posto_nome,'codigo')" style="cursor:pointer;">
	</td>
	<td nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
		<br>
		<input class="frm" type="text" name="posto_nome" size="35" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_tabela.posto_codigo,document.frm_tabela.posto_nome,'nome')" style="cursor:pointer;">
	</td>
</tr>
<tr><td colspan="3">&nbsp;</td></tr>
<?
	// se foi selecionado o posto
	if (strlen($posto) > 0){
?>
<tr>
	<td>&nbsp;</td>
	<td align="left" >
		Referência do Produto <br>
		<input type='text' name='referencia_produto' size='20' maxlength='30' value='<? echo $referencia_produto ?>' class="frm">
		&nbsp;
		<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"referencia", document.frm_tabela.voltagem_produto)' style="cursor:pointer;">
	</td>

	<td align="left" >
		Descrição do Produto <br>
		<input type='text' name='descricao_produto' size='35' maxlength='50' value='<? echo $descricao_produto ?>' class="frm">
		&nbsp;
		<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"descricao", document.frm_tabela.voltagem_produto)' style="cursor:pointer;">
		<input type="hidden" name="voltagem_produto" value="<?echo $voltagem_produto?>">
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="left">
		Código da Peça <br>
		<input type='text' name='referencia_peca' size='20' maxlength='30' value='<? echo $referencia_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFERÊNCIA DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');" class="frm">
		&nbsp;
		<a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"referencia")'></a>
	</td>

	<td align="left" >
		Descrição da Peça <br>
		<input type='text' name='descricao_peca' size='35' maxlength='50' value='<? echo $descricao_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRIÇÃO DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');" class="frm">
		&nbsp;
		<a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")'></a>
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="left" colspan='3'>
		Tabela <br>
		<select name="tabela" size="1" tabindex="0"  class="frm">
<?

		$sql = "SELECT *
				FROM   tbl_tabela
				WHERE  tbl_tabela.fabrica = $login_fabrica
				AND    tbl_tabela.tabela  IN (54,1053,1054)
				ORDER BY tbl_tabela.ordem ASC";
		$res = pg_exec($con,$sql);

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$aux_tabela       = trim(pg_result($res,$i,tabela));
			$aux_sigla_tabela = trim(pg_result($res,$i,descricao));

			echo "<option "; if ($tabela == $aux_tabela) echo " selected "; echo " value='$aux_tabela'>$aux_sigla_tabela</option>";
		}
?>
		</select>
	</td>
</tr>

<? } // se foi setado o posto ?>
<tr>
	<td height="27" valign="middle" align="center" colspan='3' ><br>
		<input type="hidden" name="btn_acao" value="">
		<input type="button" style='background:url(imagens/btn_continuar.gif); width:95px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('Aguarde submissão') }" ALT="Listar tabela de preços" border='0' >
		&nbsp;&nbsp;
		<input type="button" style='background:url(imagens/btn_limpar.gif); width:75px; cursor:pointer;' value="&nbsp;" onclick="javascript: document.location = '<? echo $PHP_SELF ?>';">
	</td>
</tr>
<tr><td colspan='3'>&nbsp;</td></tr>
</table>



</form>
<br />
<?

if (strlen ($_GET['relatorio']) > 0) {
	if (strlen($tabela) == 0) $tab = "682";

	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo = 't'
			ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);

	echo "<table align='center' border='0' width='700' class='tabela'>";
	echo "<tr class='titulo_coluna'>";

	echo "<td>REFERÊNCIA</td>";
	echo "<td>DESCRIÇÃO</td>";

	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = '#F7F5F0';
		if ($i % 2 == 0) $cor = '#F1F4FA';

		$refer = pg_result ($res,$i,referencia);
		$descr = pg_result ($res,$i,descricao);

		echo "<tr bgcolor='$cor'>";

		echo "<td>";
		if ($login_fabrica == 1) echo "<a href='$PHP_SELF?tabela=$tab&referencia_produto=$refer&descricao_produto=$descr&btn_acao=continuar'>";
		echo $refer;
		if ($login_fabrica == 1) echo "</a>";
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}

if (strlen($posto) > 0){
	# verifica se posto pode ver pecas de itens de aparencia
	$sql = "SELECT   tbl_posto_fabrica.item_aparencia
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING(posto)
			WHERE    tbl_posto.posto           = $posto
			AND      tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$item_aparencia = pg_result($res,0,item_aparencia);
	}
}

if(strlen($tabela) > 0) {
	if ((strlen($descricao_produto) > 0 AND strlen($referencia_produto) > 0) OR (strlen($descricao_peca) > 0 AND strlen($referencia_peca) > 0)) {
		$letra = (strlen($_GET['letra']) == 0) ? 'a' : $_GET['letra'];

		$sql = "SELECT DISTINCT tbl_peca.peca                         ,
						tbl_peca.referencia AS peca_referencia,
						tbl_peca.descricao  AS peca_descricao ,
						tbl_peca.unidade                      ,
						tbl_peca.ipi,
						tbl_peca.multiplo                     , ";
		if($tabela == 54) {
			$preco = (strtoupper($contato_estado) == "MG") ? " tbl_tabela_item.preco_avista " : " tbl_tabela_item.preco ";
			$sql .= "to_char(($preco)::numeric,'999999990.99')::float AS preco,";
		}else{
				$sqlO = "SELECT origem FROM tbl_peca WHERE upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) AND fabrica = $login_fabrica";
					$resO = pg_query($con,$sqlO);
					$origemO = pg_result($resO,0,origem);

					if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
						if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){
							$sql .= " (tbl_tabela_item.preco/(1-(9.25 + CASE
                                WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 19
                                WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
                                ELSE $icms_peca END)/100) * (1 + (tbl_peca.ipi / 100))) AS preco, ";
						}else{
							$sql .= " (tbl_tabela_item.preco/(1-(9.25 + CASE
                                WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 19
                                WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
                                ELSE $icms_peca END)/100)) AS preco, ";
						}
					}else if(substr($descricao,0,3) == "TMI"){
						$sql .= "tbl_tabela_item.preco / (1 - (1.65 + 7.6 + $icms) /100 )/ 0.9/ 0.7/ 0.7 * $desconto_5estrela * $desconto1 * $desconto2 AS preco, ";
					}else if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){
						$sql .= " (tbl_tabela_item.preco/(1-(9.25 + CASE
                            WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 19
                            WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
                            ELSE $icms_peca END)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2 * (1 + (tbl_peca.ipi / 100))) AS preco, ";
					}else{
						$sql .= " (tbl_tabela_item.preco/(1-(9.25 + CASE
                            WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 19
                            WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
                            ELSE $icms_peca END)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2) AS preco, ";
					}
		}
			$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $icms)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2 * $acrescimo_tabela_base * $acrescimo_financeiro) AS compra ";

			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				$sql .= ", (tbl_tabela_item.preco/(1-(9.25 + $icms )/100) * (1 + (tbl_peca.ipi/100))) AS venda ";
			}else{
				$sql .= ", (tbl_tabela_item.preco/(1-(9.25 + $icms )/100)/0.9/0.7/0.7 * $acrescimo_tabela_base_venda * $acrescimo_financeiro * (1 + (tbl_peca.ipi/100))) AS venda ";
			}


			if( substr($descricao,0,3) == "DIS" OR substr($descricao,0,3) == "TOP") {
				if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
					$sql .= ", (tbl_tabela_item.preco/(1-(9.25 + $icms )/100) * (1 + (tbl_peca.ipi/100)))       AS distrib";
				}else{
					$sqlPreco = "SELECT desconto_5estrela,
										descontos[1] AS desconto1,
										descontos[2] AS desconto2
								FROM tbl_tipo_posto
								WHERE fabrica = $login_fabrica
								AND descricao = 'AUT'";
					$resPreco = pg_query($con,$sqlPreco);
					$desconto_5estrela_aux = pg_fetch_result($resPreco, 0, 'desconto_5estrela');
					$desconto1_aux = pg_fetch_result($resPreco, 0, 'desconto1');
					$desconto2_aux = pg_fetch_result($resPreco, 0, 'desconto2');

					$desconto_5estrela_aux = ($desconto_5estrela_aux == "") ? 1 : $desconto_5estrela_aux;
					$desconto1_aux = ($desconto1_aux == "") ? 1 : $desconto1_aux;
					$desconto2_aux = ($desconto2_aux == "") ? 1 : $desconto2_aux;


					$sql .= ", (tbl_tabela_item.preco/(1-(9.25 + $icms )/100)/0.9/0.7/0.7 * 0.7 * (1+(tbl_peca.ipi/100)) )       AS distrib ";
				}
			}

		$sql .= "FROM tbl_peca
				JOIN tbl_tabela_item         ON tbl_tabela_item.peca     = tbl_peca.peca
											AND tbl_tabela_item.tabela   = $tabela
				LEFT JOIN  tbl_lista_basica  ON tbl_lista_basica.peca    = tbl_peca.peca
											AND tbl_lista_basica.fabrica = $login_fabrica
				LEFT JOIN  tbl_produto       ON tbl_produto.produto      = tbl_lista_basica.produto
				WHERE tbl_peca.fabrica = $login_fabrica
				AND   tbl_peca.ativo    IS TRUE ";

		if (strlen($referencia_produto) > 0) {
			$sql .= "AND   tbl_produto.ativo IS TRUE ";
		}

		if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";

		if (strlen($referencia_peca) > 0) {
			$sql .= "AND upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
		}elseif (strlen($descricao_peca) > 0) {
			$sql .= " AND tbl_peca.descricao ilike '%$descricao_peca%' ";
		}

		if (strlen($referencia_produto) > 0) {
			$sql .= "AND upper(trim(tbl_produto.referencia)) = upper(trim('$referencia_produto')) ";
		}elseif (strlen($descricao_produto) > 0) {
			$sql .= " AND tbl_produto.descricao ilike '%$descricao_produto%' ";
		}

		if (strlen($voltagem_produto) > 0) {
			$sql .= "AND upper(trim(tbl_produto.voltagem)) = upper(trim('$voltagem_produto')) ";
		}

		// ORDENACAO

		$sql .= "ORDER BY   tbl_peca.descricao";

		$res = pg_exec ($con,$sql);
		if (strlen($msg_erro) == 0){
			if (pg_numrows($res) == 0) {
				echo "<center><font face='arial' size='-1'>Produto informado não encontrado</font></center>";
			}

			#---------- listagem -------------
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
				$unidade            = trim(pg_result ($res,$i,unidade));
				$ipi                = trim(pg_result ($res,$i,ipi));
				$multiplo           = trim(pg_fetch_result ($res,$i,multiplo));

				if ($tabela == 54) {
					$preco = pg_result($res,$i,preco);
				}else{
					switch ( substr($descricao,0,3) ) {
						case "Dis" :
						case "DIS" :
						case "TOP" :
							$preco            = pg_result($res, $i, preco);
							$preco_distrib    = pg_result($res, $i, distrib);
							$preco_venda      = pg_result($res, $i, venda);
						break;
						case "Vip" :
							$preco            = pg_result($res, $i, preco);
							$preco_venda      = pg_result($res, $i, venda);
						break;
						case "Loc" :
							$preco            = pg_result($res, $i, preco);
						break;
						default :
							$preco            = pg_result($res, $i, preco);
							$preco_compra     = pg_result($res, $i, compra);
							$preco_venda      = pg_result($res, $i, venda);
						break;
					}
				}

				$cor = '#F7F5F0';
				if ($i % 2 == 0) $cor = '#F1F4FA';

				if ($i == 0) {
					flush();
					echo "<table width='700' align='center' cellspacing='1' border='0' class='tabela'>";
					echo "<tr class='titulo_coluna'>";
					echo "<td>Peça</td>";
					echo "<td>Descrição</td>";
					echo "<td>UN</td>";

					if ($tabela == 54) {
						echo "<td>Preço</td>";
						echo "<td>IPI</td>";
					}else{
						if ($liberar_preco) {
							switch ( substr($descricao,0,3) ) {
								case "Dis" :
									echo "<td>Preço<br>Sugerido<br>Com IPI</td>";
								break;
								case "DIS" :
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "TOP" :
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "5SA" :
									case "5SB" :
									case "5SC" :
									case "VIP" :
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "AUT" :
										if($pedido_faturado == 't'){
											echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
											echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										}else{
											echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>com IPI</b></font></td>";
										}
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
								case "Vip" :
									echo "<td>Preço<br>Sugerido<br>Com IPI</td>";
									echo "<td>IPI</td>";
								break;
								case "Loc" :
									echo "<td>Compra<br>Sem IPI</td>";
									echo "<td>IPI</td>";
								break;
								default :
									echo "<td>Preço<br>Sugerido<br>Com IPI</td>";
								break;
							}
						}
					}
					echo "</tr>";
				}

				echo "<tr bgcolor='$cor'>";

				echo "<td>";
				echo $peca_referencia;
				echo "</td>";

				echo "<td align='left'>";
				echo $peca_descricao;
				echo "</td>";

				echo "<td>";
				echo $unidade;
				echo "</td>";

				if ($tabela == 54) {
					echo "<td align='right'>";
					echo number_format ($preco,2,",",".");
					echo "</td>";

					echo "<td align='right'>";
					echo $ipi;
					echo "</td>";
				}else{
					if ($liberar_preco) {
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
								echo "<td align='right'>";
								echo number_format ($preco_venda,2,",",".");
								echo "</td>";
							break;
							case "DIS" :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo $ipi;
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_distrib,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
								case "TOP" :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo $ipi;
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_distrib,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
								case "5SA" :
								case "5SB" :
								case "5SC" :
								case "VIP" :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo $ipi;
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
								case "AUT" :
									if($pedido_faturado == 't'){
										echo "<td align='right'>";
										echo "<font face='arial' size='-2'>";
										echo $ipi;
										echo "</font>";
										echo "</td>";
									}
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";

									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
							case "Vip" :
								echo "<td align='right'>";
								echo number_format ($preco_venda,2,",",".");
								echo "</td>";
								echo "<td align='right'>";
								echo $ipi;
								echo "</td>";
							break;
							case "Loc" :
								echo "<td align='right'>";
								echo number_format ($preco,2,",",".");
								echo "</td>";

								echo "<td align='right'>";
								echo $ipi;
								echo "</td>";
							break;
							default :
								echo "<td align='right'>";
								echo number_format ($preco_venda,2,",",".");
								echo "</td>";
							break;
						}
					}
				}
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
