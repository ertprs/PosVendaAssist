<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';

set_time_limit(180);

$admin_privilegios="gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico') {
	include "autentica_admin.php";
}

if($login_fabrica == 14){
	header("Location: produtos_mais_demandados_familia.php");
	exit;
}

include "gera_relatorio_pararelo_include.php";

$msg = "";

$layout_menu = traduz("gerencia");
$title = traduz("PRODUTOS MAIS DEMANDADOS");

include "cabecalho_new.php";

$plugins = array("multiselect", "dataTable", "shadowbox");

include "plugin_loader.php";

if ((strlen($btn_acao) > 0 OR !empty($_GET)) && count($msg_erro) == 0) {
    include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and count($msg_erro)==0){
    include "gera_relatorio_pararelo_verifica.php";
}
?>

<script>

    $(function(){
        <? if ($login_fabrica == 86){ ?>
        	$("#linha").multiselect({
            	selectedText: "selecionados # de #"
            });
        <? } ?>
        <?php if ($telecontrol_distrib || $interno_telecontrol) { ?>
            $('#relatorio').DataTable({
                aaSorting: [[0, 'desc']],
                "oLanguage": {
                    "sLengthMenu": "Mostrar <select>" +
                                    '<option value="10"> 10 </option>' +
                                    '<option value="50"> 50 </option>' +
                                    '<option value="100"> 100 </option>' +
                                    '<option value="150"> 150 </option>' +
                                    '<option value="200"> 200 </option>' +
                                    '<option value="-1"> Tudo </option>' +
                                    '</select> resultados',
                    "sSearch": "Procurar:",
                    "sInfo": "Mostrando de _START_ até _END_ de um total de _TOTAL_ registros",
                    "oPaginate": {
                        "sFirst": "Primeira página",
                        "sLast": "Última página",
                        "sNext": "Próximo",
                        "sPrevious": "Anterior"
                    }
                }
            });
        <?php } ?>
    });

    function pecasPorReferencia(id)
    {
        let referencia = id.dataset.referencia;
        let meses = $('#meses').val();
        let fabrica = '<?=$login_fabrica?>';
        let get = 'produto=' + referencia + '&meses=' + meses + '&fabrica=' + fabrica;

        Shadowbox.init();
        Shadowbox.open({
            content: "detalhes_peca_por_ocorrencia.php?" + get,
            player : "iframe",
            title  : "Peça por ocorrência",
            width  : 800,
            height : 550
        });
    }

</script>

<?
if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["meses"])) > 0) $meses = trim($_POST["meses"]);
if (strlen(trim($_GET["meses"])) > 0)  $meses = trim($_GET["meses"]);

if (strlen(trim($_POST["qtde_produto"])) > 0) $qtde_produto = trim($_POST["qtde_produto"]);
if (strlen(trim($_GET["qtde_produto"])) > 0)  $qtde_produto = trim($_GET["qtde_produto"]);
if($login_fabrica == 86){
	if(count($_POST["linha"])>0){
		$linha = $_POST["linha"];
	}
}else{
    if (strlen(trim($_POST["linha"])) > 0){
        $linha = trim($_POST["linha"]);
    }
	if (strlen(trim($_POST["marca"])) > 0){
		$marca = trim($_POST["marca"]);
	}
}
if (strlen(trim($_GET["linha"])) > 0)  $linha = trim($_GET["linha"]);
if (strlen(trim($_GET["marca"])) > 0)  $marca = trim($_GET["marca"]);
?>

<?
if (count($_GET["linha"]) > 0)  $linha = trim($_GET["linha"]);

if (count($msg_erro["msg"]) > 0 and !empty($msg_erro)) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (strlen($msg_aguard) > 0 and !in_array($login_fabrica, [152, 180, 181, 182])) {
?>
    <div class="alert alert-sucesses">
	<h4><?=$msg_aguard?></h4>
    </div>
<?php
}
?>
<div class="container tc-container">
    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela'><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("qtde_produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='qtde_produto'><?php echo traduz("Exibir os"); ?></label>
                    <div class='controls controls-row'>
                        <select name='qtde_produto' id='qtde_produto' size='1' style='text-align:right;width:4em' class='frm'>
                            <option value='1' <? if ($qtde_produto == "1" ) echo " selected " ?> >1</option>
                            <option value='2' <? if ($qtde_produto == "2" ) echo " selected " ?> >2</option>
                            <option value='3' <? if ($qtde_produto == "3" ) echo " selected " ?> >3</option>
                            <option value='4' <? if ($qtde_produto == "4" ) echo " selected " ?> >4</option>
                            <option value='5' <? if ($qtde_produto == "5" ) echo " selected " ?> >5</option>
                            <option value='6' <? if ($qtde_produto == "6" ) echo " selected " ?> >6</option>
                            <option value='7' <? if ($qtde_produto == "7" ) echo " selected " ?> >7</option>
                            <option value='8' <? if ($qtde_produto == "8" ) echo " selected " ?> >8</option>
                            <option value='9' <? if ($qtde_produto == "9" ) echo " selected " ?> >9</option>
                            <option value='10' <? if($qtde_produto == "10") echo " selected " ?> >10</option>
                            <? // HD 408049 - Aumentar o nº de produtos da lista
                            if ($login_fabrica == 72) { ?>
                            <option value='15' <? if($qtde_produto == "15") echo " selected " ?> >15</option>
                            <option value='20' <? if($qtde_produto == "20") echo " selected " ?> >20</option>
                            <option value='30' <? if($qtde_produto == "30") echo " selected " ?> >30</option>

                            <?}?>
                        </select> <?php echo traduz("produtos que mais quebraram"); ?>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group <?=(in_array("meses", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='meses'> <?php echo traduz("Nos Últimos"); ?></label>
                    <div class='controls controls-row'>
                        <select name='meses' id='meses' style='text-align:right;width:4em' size='1' class='frm'>
                            <option value='3' <? if ($meses == "3" or strlen ($meses) == 0) echo " selected " ?> >3</option>
                            <option value='6' <? if ($meses == "6" ) echo " selected " ?> >6</option>
                            <option value='12' <? if ($meses == "12" ) echo " selected " ?> >12</option>
                        </select> 	 <?php echo traduz("meses"); ?>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <div class="row-fluid">
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='linha'><?=($login_fabrica == 117)?traduz("Macro - Família"):traduz("Linha")?></label>
                    <div class='controls controls-row'>
                        <?
                        if ($login_fabrica == 117) {
                            $sql_linha = "SELECT DISTINCT tbl_linha.linha,
                                                   tbl_linha.nome
                                                FROM tbl_linha
                                                    JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                                    JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                                WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                                                    AND     tbl_linha.ativo = TRUE
                                                ORDER BY tbl_linha.nome;";
                        } else {                        
                            $sql_linha = "SELECT
                                                linha,
                                                nome
                                            FROM tbl_linha
                                            WHERE tbl_linha.fabrica = $login_fabrica
                                            ORDER BY tbl_linha.nome ";
                        }
                            $res_linha = pg_query($con, $sql_linha); ?>
<?
                            if($login_fabrica != 86){
?>
                        <select name='linha' id='linha'>
<?
                                if (pg_num_rows($res_linha) > 0) {
?>
                            <option value=''>ESCOLHA</option> <?

                                    for ($x = 0 ; $x < pg_numrows($res_linha) ; $x++){
                                        $aux_linha = trim(pg_result($res_linha, $x, linha));
                                        $aux_nome = trim(pg_result($res_linha, $x, nome));
                                        if ($linha == $aux_linha) {
                                            $selected = "SELECTED";
                                        }else {
                                            $selected = "";
                                        }
?>

                            <option value='<?=$aux_linha?>' <?=$selected?>><?=$aux_nome?></option>
<?
                                    }
                                }else{
?>
                            <option value=''><?php echo traduz("Não existem linhas cadastradas"); ?></option>
<?
                                }
?>
                        </select>
<?
                            }else{
?>
                        <select name="linha[]" id="linha" multiple="multiple">
<?php

                                    $selected_linha = array();
                                    foreach (pg_fetch_all($res_linha) as $key) {
                                        if(isset($linha)){
                                            foreach ($linha as $id) {
                                                if ( isset($linha) && ($id == $key['linha']) ){
                                                    $selected_linha[] = $id;
                                                }
                                            }
                                        }
?>
                            <option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> ><?php echo $key['nome']?></option>
<?php
                                    }
?>
                        </select>

<?
                            }
?>
                    </div>
                </div>
            </div>
<?

        if($login_fabrica == 1){

            $sqlMarca = "
                SELECT  marca,
                        nome
                FROM    tbl_marca
                WHERE   fabrica = $login_fabrica;
            ";
            $resMarca = pg_query($con,$sqlMarca);
            $marcas = pg_fetch_all($resMarca);
?>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='marca'><?php echo traduz("Marca"); ?></label>
                    <div class='controls controls-row'>
                        <select name="marca" id="marca">
                            <option value="">&nbsp;</option>
<?
            foreach($marcas as $chave => $valor){
?>
                            <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
            }
?>
                        </select>
                    </div>
                </div>
            </div>
<?
        }
?>
            <div class='span2'></div>
        </div>

        <p>
            <br />
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),1);" ><?php echo traduz("Pesquisar"); ?> </button>
        </p>
        <br />
	</form>
</div>
<br>

<?
if (strlen($btn_acao) > 0 and strlen($msg_erro)==0) {
	flush();
	$array_meses = array (1 => traduz("Janeiro"), traduz("Fevereiro"), traduz("Março"), traduz("Abril"), traduz("Maio"), traduz("Junho"), traduz("Julho"), traduz("Agosto"), traduz("Setembro"), traduz("Outubro"), traduz("Novembro"), traduz("Dezembro"));

	$data_final = date ('Y-m-') . "01";

	$sql    = "SELECT TO_CHAR(current_timestamp, 'MM-DD-YYYY'), TO_CHAR(current_timestamp, 'YYYY-MM-DD') AS data_2";
	//echo $sql;
	$res = pg_query($con,$sql);
	$data_2 = pg_fetch_result($res,0,data_2);

	$nome_programa	  = "produtos_mais_demandados";
	$arquivo_nome     = "relatorio_automatico_$nome_programa-".$login_fabrica.".".$login_admin.".xls";
	$caminho_arquivo  = "xls/".$arquivo_nome;
	fopen($caminho_arquivo, "w+");
	$fp = fopen($caminho_arquivo, "a");

	$cond_1 = " fabrica = $login_fabrica ";
	if($login_fabrica == 86){

		if (count($linha) > 0){
			$cond_1 = " tbl_produto.linha IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$cond_1 .= $linha[$i].")";
				}else {
					$cond_1 .= $linha[$i].", ";
				}
			}

		}
	}else{
        $cond_2 = "";
		if (strlen ($linha) > 0){
			$cond_1 = " tbl_produto.linha = $linha ";
		}
		if(strlen($marca) > 0){
            $cond_2 = "AND tbl_produto.marca = $marca";
		}
	}
	// echo $cond_1;
/*
	$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, os.mes, os.qtde
			FROM tbl_produto
			JOIN (SELECT produto, to_char (tbl_os.data_digitacao,'MM') AS mes, COUNT(*) AS qtde FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.excluida IS NOT TRUE
					AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS' AND '$data_final'::date + INTERVAL '1 MONTHS'
					AND   tbl_os.produto IN ( SELECT produto FROM (
						SELECT produto , COUNT(*) FROM tbl_os
							JOIN  tbl_produto USING (produto)
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.excluida IS NOT TRUE
							AND   $cond_1
							AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS' AND '$data_final'::date + INTERVAL '1 MONTHS'
							GROUP BY tbl_os.produto
							ORDER BY COUNT(*) DESC
							LIMIT $qtde_produto
						) os1
					)
					GROUP BY tbl_os.produto, to_char (tbl_os.data_digitacao,'MM')
			) os ON tbl_produto.produto = os.produto
			ORDER BY tbl_produto.referencia, os.mes";
	$res = pg_exec ($con,$sql);
*/
	//HD 14453
	$sql_order="";
	if($login_fabrica == 45){
		$sql_order="select produto,
					sum(qtde) as total
					into temp tmp_pmd4_$login_admin
					from tmp_pmd3_$login_admin
					group by produto;

					CREATE INDEX tmp_pmd4_PRODUTO_$login_admin ON tmp_pmd4_$login_admin(produto); ";
		$sql_order2=", total ";
		$sql_order3=" join tmp_pmd4_$login_admin produto on produto.produto=tbl_produto.produto";
		$sql_order4=" ORDER BY total desc ; ";
	}else{
		$sql_order4=" ORDER BY tbl_produto.referencia,os.mes; ";
	}

	$join_os_extra = '';
	$cond_os_extra = '';
	if ($login_fabrica == 117) {
		$join_os_extra = ' JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os ';
		$cond_os_extra = ' AND tbl_os_extra.garantia IS NOT false ';
	}

    if($login_fabrica == 163){
        $join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
        $cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
    }

            if (in_array($login_fabrica, array(138))) {
                        $sql = "SELECT tbl_produto.*
                                    INTO TEMP tmp_pmd1_$login_admin
                                    FROM tbl_produto
                                    JOIN tbl_linha using(linha)
                                    WHERE $cond_1
                                    $cond_2;

                                    CREATE INDEX tmp_pmd1_PRODUTO_$login_admin ON tmp_pmd1_$login_admin(produto);

                                        SELECT tbl_os_produto.produto , COUNT(*)
                                        INTO TEMP tmp_pmd2_$login_admin
                                        FROM tbl_os_produto
                                        JOIN tbl_os USING (os)
                                        JOIN  tmp_pmd1_$login_admin ON tmp_pmd1_$login_admin.produto = tbl_os_produto.produto
                                        JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                                        $join_os_extra
                                        WHERE tbl_os.fabrica = $login_fabrica
                                        AND   tbl_os.excluida IS NOT TRUE
                                        AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS'
                                        AND '$data_final'::date
                                        $cond_os_extra
                                        GROUP BY tbl_os_produto.produto
                                        ORDER BY COUNT(*) DESC
                                        LIMIT $qtde_produto ;

                                    CREATE INDEX tmp_pmd2_PRODUTO_$login_admin ON tmp_pmd2_$login_admin(produto);

                                    SELECT  tbl_os_produto.produto,
                                        to_char (tbl_os.data_digitacao,'MM') AS mes,
                                        COUNT(*) AS qtde
                                    INTO TEMP tmp_pmd3_$login_admin
                                    FROM tbl_os_produto
                                    JOIN tbl_os USING (os)
                                    JOIN tmp_pmd2_$login_admin ON tmp_pmd2_$login_admin.produto = tbl_os_produto.produto
                                    $join_os_extra
                                    WHERE tbl_os.fabrica = $login_fabrica
                                    AND   tbl_os.excluida IS NOT TRUE
                                    AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS'
                                    AND '$data_final'::date
                                    $cond_os_extra
                                    GROUP BY tbl_os_produto.produto,
                                         to_char (tbl_os.data_digitacao,'MM');

                                    CREATE INDEX tmp_pmd3_PRODUTO_$login_admin ON tmp_pmd3_$login_admin(produto);

                                    $sql_order

                                    SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, os.mes, os.qtde
                                    $sql_order2
                                    FROM tbl_produto
                                    JOIN tmp_pmd3_$login_admin os ON tbl_produto.produto = os.produto
                                    $sql_order3
                                    $sql_order4 ";
            } else {

            	$sql = "SELECT tbl_produto.*
            		INTO TEMP tmp_pmd1_$login_admin
            		FROM tbl_produto
            		JOIN tbl_linha using(linha)
            		WHERE $cond_1
            		$cond_2;

            		CREATE INDEX tmp_pmd1_PRODUTO_$login_admin ON tmp_pmd1_$login_admin(produto);

            			SELECT produto , COUNT(*)
            			INTO TEMP tmp_pmd2_$login_admin
            			FROM tbl_os
            			JOIN  tmp_pmd1_$login_admin tbl_produto USING (produto)
            			$join_os_extra
                        $join_163
            			WHERE tbl_os.fabrica = $login_fabrica
            			AND   tbl_os.excluida IS NOT TRUE
            			AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS'
            			AND '$data_final'::date
            			$cond_os_extra
                        $cond_163
            			GROUP BY tbl_os.produto
            			ORDER BY COUNT(*) DESC
            			LIMIT $qtde_produto ;

            		CREATE INDEX tmp_pmd2_PRODUTO_$login_admin ON tmp_pmd2_$login_admin(produto);

            		SELECT  produto,
            			to_char (tbl_os.data_digitacao,'MM') AS mes,
            			COUNT(*) AS qtde
            		INTO TEMP tmp_pmd3_$login_admin
            		FROM tbl_os
            		JOIN tmp_pmd2_$login_admin USING(produto)
            		$join_os_extra
                    $join_163
            		WHERE tbl_os.fabrica = $login_fabrica
            		AND   tbl_os.excluida IS NOT TRUE
            		AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS'
            		AND '$data_final'::date
            		$cond_os_extra
                    $cond_163
            		GROUP BY tbl_os.produto,
            			 to_char (tbl_os.data_digitacao,'MM');

            		CREATE INDEX tmp_pmd3_PRODUTO_$login_admin ON tmp_pmd3_$login_admin(produto);

            		$sql_order

            		SELECT tbl_produto.produto, tbl_produto.referencia,tbl_produto.referencia_fabrica, tbl_produto.descricao, os.mes, os.qtde
            		$sql_order2
            		FROM tbl_produto
            		JOIN tmp_pmd3_$login_admin os ON tbl_produto.produto = os.produto
            		$sql_order3
            		$sql_order4 ";
            }


            /*echo $sql;
            exit();*/
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){?>
	</div><?
		echo "<TABLE name='relatorio' id='relatorio' class='table table-striped table-bordered table-hover table-large'>";
		fputs ($fp,"<TABLE width='700' border='1' cellspacing='1' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter'>");
		echo "<thead>";
			echo "<tr class='titulo_tabela'>";
			fputs ($fp,"<tr>");
                if ($login_fabrica == 171) {
                    echo "<td  height='15' nowrap>". traduz("Referência Fábrica") ."</td>";
                }
				echo "<td  height='15' nowrap>". traduz("Referência")."</td>";
				fputs ($fp,"<td  height='15' nowrap><B>". traduz("Referência")."</B></td>");
				echo "<td height='15' nowrap>". traduz("Produto")."</td>";
				fputs ($fp,"<td height='15' nowrap><B>". traduz("Produto")."</B></td>");
				$mes_final   = intval (date('m',mktime (0,0,0,date('m')-1)));
				$mes_inicial = intval (date('m',mktime (0,0,0,date('m')-$meses)));

				$mes_corrente = $mes_inicial;
				for ($i=0; $i<$meses;$i++) {
					$vetor_mes[] = $mes_corrente;

					if ($mes_corrente == 12) {
						$mes_corrente = 1;
					} else {
						$mes_corrente++;
					}
				}

				$indice = 0;

			//	for ($i = $mes_inicial ; $i <= $mes_final ; $i++) {

				for ($i = 0; $i<count($vetor_mes);$i++) {
			//		echo "<td>" . $array_meses [ $i ] . "</td>";
					echo "<td>".$array_meses[$vetor_mes[$i]]."</td>";
					fputs ($fp,"<td  align='right'><B>".$array_meses[$vetor_mes[$i]]."</B></td>");
					$coluna[$indice] = "<td>&nbsp;</td>";
			//		$mes_coluna[$indice] = $i;
					$mes_coluna[$indice] = str_pad($vetor_mes[$i], 2, "0", STR_PAD_LEFT);
					$indice++;
				}

                if ($telecontrol_distrib || $interno_telecontrol) {
                    #$coluna[$indice] = "<td>&nbsp;</td>";
                    echo "<td height='15' nowrap>Total</td>";
                    fputs ($fp,"<td height='15' nowrap><B>Total</B></td>");
                }

			echo "</tr>";
			fputs ($fp,"</tr>");
		echo "</thead>";
		$produto_antigo = "" ;
        
        $total = [];

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			if ($produto_antigo <> pg_fetch_result($res,$i,produto)){
				if (strlen ($produto_antigo) > 0) {
                    $totalProduto = 0;
					for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
                        $totalProduto += $coluna[$indice];
						echo "<td nowrap align='right'>&nbsp;" .$coluna[$indice] . "</td>";
						fputs($fp,"<td nowrap align='right'>&nbsp;" .$coluna[$indice]. "</td>");
					}
                    
                    if ($telecontrol_distrib || $interno_telecontrol) {
					   echo "<td>{$totalProduto}</td></tr>";
                    }

					fputs ($fp,"</tr>");
				}

				echo "<tr align='left' style='font-size:12px'>";
				fputs ($fp,"<tr align='left' style='font-size:12px'>");
                if ($login_fabrica == 171) {
                    echo "<td nowrap>".pg_fetch_result($res,$i,referencia_fabrica)."</td>";
                }
				
                echo "<td nowrap>";
				
                $referencia = pg_fetch_result($res, $i, 'referencia');
                
                if ($telecontrol_distrib || $interno_telecontrol) {
                    echo '<div id="btnOcorrencias" onclick="pecasPorReferencia(this)" data-referencia="'. $referencia . '">';
                    echo '<a href="#">' . $referencia . '</a>';
                    echo '</div>';

                } else {

                    echo $referencia;
                }

				echo "</td>";
				fputs ($fp,"<td nowrap>".pg_fetch_result($res,$i,referencia)."</td>");
				echo "<td nowrap>";
				echo pg_fetch_result($res,$i,descricao);
				echo "</td>";
				fputs ($fp,"<td nowrap>".pg_fetch_result($res,$i,descricao)."</td>");
				for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
                    $coluna[$indice] = "&nbsp;";
				}

				$produto_antigo = pg_fetch_result($res,$i,produto);
			}

			$indice = array_search (pg_fetch_result($res,$i,mes) , $mes_coluna);
			$coluna[$indice] = pg_fetch_result($res,$i,qtde);
        }

        $totalProduto = 0;

		for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
            $totalProduto += $coluna[$indice];
            echo "<td nowrap align='right'>&nbsp;" .$coluna[$indice]. "</td>";
            fputs($fp,"<td nowrap align='right'>&nbsp;" .$coluna[$indice]. "</td>");
		}

        if ($telecontrol_distrib || $interno_telecontrol) { 
            echo "<td nowrap align='right'>{$totalProduto}</td>";
        }

		echo "</tr>";
		fputs ($fp,"</tr>");
		echo "</table>";
	    fputs ($fp,"</table>");

		if(in_array($login_fabrica, array(86,157))){
			if(file_exists($caminho_arquivo)) {
				echo  "<br>";
				echo "<table width='700px' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo "<tr>";
				echo  "<td align='center'><button type='button' onclick=\"window.location='$caminho_arquivo'\">". traduz("Download em Excel")."</button></td>";
				echo  "</tr>";
				echo  "</table>";
			}
		}
	}
	else
		echo "<center>". traduz("Nenhum Produto Encontrado!") ."</center>";
}
echo "<br>";
echo "</form>";
include "rodape.php";
?>