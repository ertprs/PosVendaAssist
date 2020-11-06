<?php

//takashi inseriu o campo origem
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = '';

$linha_macrofamilia = ($login_fabrica == 117) ? traduz('Macro-Família') : traduz('Linha');

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];
else                                $btn_acao = $_GET["btn_acao"];

if (strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);
else                             $linha = trim($_GET["linha"]);

if (strlen($_POST["familia"]) > 0) $familia = trim($_POST["familia"]);
else                               $familia = trim($_GET["familia"]);


if ($btn_acao == "ATUALIZAR") {

    $mao_de_obra_troca = trim($_POST['mao_de_obra_troca']);
    $mao_de_obra_troca = str_replace(',','.',$mao_de_obra_troca);

    $res = pg_exec($con,"BEGIN TRANSACTION");

    $sql = "UPDATE tbl_produto
               SET mao_de_obra_troca = '$mao_de_obra_troca'
             WHERE linha             = $linha";

    $res      = pg_exec($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        $res = pg_exec($con,"COMMIT TRANSACTION");
        header("Location: $PHP_SELF?btn_acao=LISTAR&linha=$linha&msg=Atualizado com Sucesso!");
        exit;
    } else {
        $res = pg_exec($con,"ROLLBACK TRANSACTION");
        $btn_acao = "LISTAR";
    }

}

if ($btn_acao == "GRAVAR") {

    $produto_qtde = $_POST["produto_qtde"];
    $pagina_atual = $_POST["pagina_atual"];

    $res = pg_exec($con,"BEGIN TRANSACTION");

    for ($i = 0 ; $i < $produto_qtde; $i++) {

        $xproduto                   = trim($_POST["produto_".$i]);
        $xreferencia                = trim($_POST["referencia_".$i]);
        $xvoltagem                  = trim($_POST["voltagem_".$i]);
        $xgarantia                  = trim($_POST["garantia_".$i]);
        $xgarantia_horas            = trim($_POST["garantia_horas_".$i]);

        $xmao_de_obra               = trim($_POST["mxlao_de_obra_posto_".$i]);
        $xmao_de_obra               = str_replace(",",".",$xmao_de_obra);

        $xmao_de_obra_admin         = trim($_POST["mao_de_obra_admin_".$i]);
        $xmao_de_obra_admin         = str_replace(",",".",$xmao_de_obra_admin);

        $xmao_de_obra_troca         = trim($_POST["mao_de_obra_troca_".$i]);
        $xmao_de_obra_troca         = str_replace(",",".",$xmao_de_obra_troca);

        $xradical_serie             = trim($_POST["radical_serie_".$i]);
        $xproduto_critico           = trim($_POST["produto_critico_".$i]);
        $xnumero_serie_obrigatorio  = trim($_POST["numero_serie_obrigatorio_".$i]);
        $xativo                     = trim($_POST["ativo_".$i]);
        $xtroca_obrigatoria         = trim($_POST["troca_obrigatoria_".$i]);
        $xlista_troca               = trim($_POST["lista_troca_".$i]);
	if (in_array($login_fabrica, [151])) {
            $xtroca_direta          = trim($_POST["troca_direta_".$i]);
        }
        $xorigem                    = trim($_POST["origem_".$i]);
        $xfamilia                   = trim($_POST["familia_".$i]);
        $xlinha                     = trim($_POST["linha_".$i]);
        $xentrega_tecnica           = $_POST["entrega_tecnica_".$i];

        if($login_fabrica == 52){
            $xmarca = trim($_POST["marca_".$i]);
        }
        if ($xentrega_tecnica <> "t") {
            $xentrega_tecnica = "f";
        }
        $xabre_os          = $_POST["abre_os_".$i];
        if ($xabre_os <> "t") {
            $xabre_os = "f";
        }
        //takashi
        if (strlen($_POST["origem_$i"]) > 0) $xorigem = "'". trim($_POST["origem_$i"]) ."'";
        else $xorigem = "null";

        //takashi
        if (strlen($xgarantia) > 0) $xgarantia = "'".$xgarantia."'";
        else                        $msg_erro = traduz("Digite a quantidade de meses da garantia do Produto $xreferencia.");

        if($login_fabrica == 87){
            if (strlen($xgarantia) > 0) $xgarantia = "'".$xgarantia_horas."'";
            else                        $msg_erro  = traduz("Digite a quantidade de horas da garantia do Produto $xreferencia.");
        } else {
            $xgarantia_horas = "null";
        }


        if (strlen($xmao_de_obra) > 0) { $xmao_de_obra = "'".$xmao_de_obra."'"; }
        else {
			if($login_fabrica == 87){
				$msg_erro = traduz("Digite o valor da Mão de Obra do Canal do Produto $xreferencia.");
			} elseif($login_fabrica <> 151) {
				$msg_erro = traduz("Digite o valor da Mão de Obra do Posto do Produto $xreferencia.");
			}else{
				$xmao_de_obra = 0;
			}
		}


        if (strlen($msg_erro) > 0) $produto_erro = $xproduto;

        if (strlen($xmao_de_obra_admin) > 0) $xmao_de_obra_admin = "'".$xmao_de_obra_admin."'";
        else                                 $xmao_de_obra_admin = '0';

        if (strlen($xmao_de_obra_troca) > 0) $xmao_de_obra_troca = "'".$xmao_de_obra_troca."'";
        else                                 $xmao_de_obra_troca = '0';

        if (strlen($xradical_serie) > 0) $xradical_serie = "'".$xradical_serie."'";
        else                             $xradical_serie = 'null';

        if (strlen($xnumero_serie_obrigatorio) > 0) $xnumero_serie_obrigatorio = "'".$xnumero_serie_obrigatorio."'";
        else                                        $xnumero_serie_obrigatorio = 'null';

        if (strlen($xproduto_critico) > 0) $xproduto_critico = "'".$xproduto_critico."'";
        else                               $xproduto_critico = 'null';

        if (strlen($xativo) > 0) $xativo = "'".$xativo."'";
        else                     $xativo = "'f'";

        if (strlen($xtroca_obrigatoria) > 0) $xtroca_obrigatoria = "'".$xtroca_obrigatoria."'";
        else                                 $xtroca_obrigatoria = "'f'";

        if (strlen($xlista_troca) > 0) $xlista_troca = "'".$xlista_troca."'";
        else                                 $xlista_troca = "'f'";

	if (in_array($login_fabrica, [151])) {
            if (strlen($xtroca_direta) == 0) {
                $xtroca_direta = "f";
            }

            $parametros_adicionais = json_encode(["troca_direta" => $xtroca_direta]);
        }

        if (strlen($xfamilia) > 0) $xfamilia = "'".$xfamilia."'";
        else                       $xfamilia = 'null';

        if($login_fabrica == 52){
            if (strlen($xmarca) > 0) $xmarca = "'".$xmarca."'";
            else                       $xmarca = 'null';
        }
        if (strlen($_POST["linha_$i"]) > 0) $xlinha = "'". trim($_POST["linha_$i"]) ."'";
        else $xlinha = "null";

        if (strlen($_POST["classe_$i"]) > 0) $xclasse = "'". trim($_POST["classe_$i"]) ."'";
        else $xclasse = "null";

        $grv_linha = '';
        if (strlen($_POST["linha_$i"]) > 0){
            $grv_linha = 'linha ='.$xlinha.',';
        }
        if($login_fabrica == 52){
            $update = ", marca = $xmarca";
        }
        if ($login_fabrica == 42) {
            $update = ", entrega_tecnica = '$xentrega_tecnica'";
        }
        if ($login_fabrica == 11 or $login_fabrica == 172) {
            $update = ", abre_os = '$xabre_os'";
        }

        if ($login_fabrica == 74){
            $update = ", voltagem = '$xvoltagem' ";
        }

	   if (in_array($login_fabrica, [151])) {
            $update = ", parametros_adicionais = '" . $parametros_adicionais . "'";
        }

        if(empty($msg_erro)){
            $sqlA = "select * from tbl_produto where produto = $produto and fabrica_i = $login_fabrica";
            $resA = pg_query($con,$sqlD);
            $auditor_antes = pg_fetch_assoc($resD);
        }

        if(in_array($login_fabrica, [169,170])){
            $sql = "SELECT familia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$xproduto}";
            $res = pg_query($con,$sql);
            $familia_atual = pg_fetch_result($res, 0, 'familia');
        }


        if (strlen($msg_erro) == 0) {
           $sql =    "UPDATE tbl_produto SET
                        garantia                 = $xgarantia                 ,
                        garantia_horas           = $xgarantia_horas           ,
                        mao_de_obra              = $xmao_de_obra              ,
                        mao_de_obra_admin        = $xmao_de_obra_admin        ,
                        mao_de_obra_troca        = $xmao_de_obra_troca        ,
                        radical_serie            = $xradical_serie            ,
                        numero_serie_obrigatorio = $xnumero_serie_obrigatorio ,
                        produto_critico          = $xproduto_critico            ,
                        ativo                    = $xativo                    ,
                        troca_obrigatoria        = $xtroca_obrigatoria        ,
                        lista_troca              = $xlista_troca                ,
                        origem                   = $xorigem                   ,
                        $grv_linha
                        familia                  = $xfamilia                  ,
                        admin                    = $login_admin               ,
                        data_atualizacao         = now()
                        $update
                    WHERE produto = $xproduto;";
            $res = pg_exec($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if(in_array($login_fabrica, [169,170])){
            $nova_familia = str_replace("'", "", $xfamilia);

            if($familia_atual <> $nova_familia){
                //Função está no arquivo funcoes.php
                gravaValoresAdicionaisProduto($xproduto,$familia_atual,$nova_familia);
            }else{
                //Função está no arquivo funcoes.php
                gravaValoresAdicionaisProduto($xproduto,$familia_atual);
            }
        }

        if(empty($msg_erro)){
            $sqlD = "select * from tbl_produto where produto = $xproduto and fabrica_i = $login_fabrica";
            $resD = pg_query($con,$sqlD);
            $auditor_depois = pg_fetch_assoc($resD);
            auditorLog($peca,$auditor_antes,$auditor_depois,"tbl_produto");
        }

		if($login_fabrica == 42 AND strlen($msg_erro) == 0 AND $xclasse != "null"){
			$sql = "SELECT produto,classe FROM tbl_classe_produto WHERE produto = $xproduto";
			$res = pg_query ($con,$sql);
			if(pg_numrows($res) > 0){
				$aux_classe = pg_result($res,0,'classe');
				if($aux_classe != $xclasse){
					$sql = "UPDATE tbl_classe_produto SET classe = $xclasse WHERE produto = $xproduto";
					$res = pg_query ($con,$sql);
				}
			}else if(!empty($xclasse)){
				$sql = "INSERT INTO tbl_classe_produto(classe,produto) VALUES ($xclasse,$xproduto)";
				$res = pg_query ($con,$sql);
			}
		}

    }
	$pagina_atual = $_POST["pagina_atual"];
    if (strlen($msg_erro) == 0) {
        $res = pg_exec($con,"COMMIT TRANSACTION");
		$total_pagina	= $_POST['numero_paginas'];
		$ttl_atual		= $pagina_atual + 1;
		//echo "TOTAL =".$total_pagina." == ".$ttl_atual;exit;
		if($total_pagina == $ttl_atual){

		}else{
			$pagina_atual = $pagina_atual + 1;
		}
		header("Location: $PHP_SELF?btn_acao=LISTAR&pagina=$pagina_atual&linha=$linha");

		if (in_array($login_fabrica, [151]) && !empty($familiaRedirect)) {
	            $location .= "&familia=$familiaRedirect";
        	}

    } else {
        $res = pg_exec($con,"ROLLBACK TRANSACTION");
        $btn_acao = "LISTAR";
    }

}

if ($btn_acao == "LISTAR" AND strlen($linha) == 0 AND !in_array($login_fabrica,array(175))) {
    $msg_erro = traduz("Selecione uma linha.");
}

if ($btn_acao == "LISTAR" AND strlen($familia) == 0 AND in_array($login_fabrica,array(175))) {
    $msg_erro = traduz("Selecione uma família.");
}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = traduz("MANUTENÇAO DE PRODUTOS");
include 'cabecalho_new.php';
?>

<!--Mensagem de erro-->
<?php if (strlen($msg_erro) > 0) { ?>
    <p> <div class="alert alert-error"> <h4><?php echo $msg_erro; ?></h4></div> </p>
<?php } ?>

<!--Mensagem de sucesso-->
<?php if (strlen($msg_sucesso) > 0) { ?>
    <p> <div class="alert alert-success"><h4><?php echo $msg_sucesso; ?></h4></div> </p>
<?php } ?>

<?php
if (($login_fabrica == 35 ) && strlen($linha) > 0)
{?>

    <form name="frm_produto_troca" method="POST" action="<?=$PHP_SELF?>">
    <input type="hidden" name="linha" value="<?=$linha?>" />
        <table class='table table-striped table-bordered table-hover table-large' cellspacing='1' cellpadding='3'>
            <thead class='formulario'>
                <tr class='titulo_tabela'>
                    <th align='center' colspan="2" ><?=traduz('Ajustar Mão de Obra de Troca para todos produtos dessa')?> <?=$linha_macrofamilia; ?></th>
                </tr>
            </thead>
            <tbody>
                <tr class='titulo_coluna'>
                    <td align="right"><?=traduz('Mão de Obra de Troca')?></td>
                    <td align="left"><?php echo $real ?><input type="text" name="mao_de_obra_troca" id="mao_de_obra_troca" class="frm"/></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="btn_acao" id="btn_acao"  value="ATUALIZAR" class='btn' />
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
    <br />
    <br />
    <?php
}
?>

<form class="form-search form-inline tc_formulario" name="frm_produto" method="POST" action="<?=$PHP_SELF?>">
<input type='hidden' name='btn_acao' value='' />

<?php if ($btn_acao <> 'LISTAR') { ?>

    <div class="titulo_tabela">  <?=traduz('Parâmetro de Pesquisa')?></div>
    <br />
    <div class='container'>
        <div class="row-fluid">
            <div class="span2"></div>
            <?php
            if(!in_array($login_fabrica, array(175))){
            ?>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="tabela"> <?=traduz('Selecione a')?> <?=$linha_macrofamilia; ?></label>
                    <?php
                        if ($login_fabrica == 117) {
                            $joinElgin = "JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha";
                        }

                        $sql = "SELECT DISTINCT tbl_linha.linha, tbl_linha.nome FROM tbl_linha $joinElgin WHERE tbl_linha.fabrica = $login_fabrica ORDER BY tbl_linha.nome;";
                        $res = pg_exec($con,$sql);
                        if (pg_numrows($res) > 0) {
                            echo "<select class='frm' name='linha' style='width: 280px;' >\n";
                            echo "<option value=''>ESCOLHA</option>\n";

                            for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
                                $linha = trim(pg_result($res,$x,linha));
                                $nome  = trim(pg_result($res,$x,nome));
                                echo "<option value='$linha'>$nome</option>\n";
                            }
                            echo "</select>\n";
                        }
                    ?>
                </div>
            </div>
            <?php
            }
            ?>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='familia'><?=traduz('Familia')?></label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <select name="familia" id="familia">
                                <option value=""></option>
                                <?php

                                    $sql = "
                                        SELECT familia, descricao
                                        FROM tbl_familia
                                        WHERE fabrica = $login_fabrica
                                        AND ativo ORDER BY descricao";
                                    $res = pg_query($con,$sql);
                                    foreach (pg_fetch_all($res) as $key) {
                                        $selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;
                                    ?>
                                        <option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
                                            <?php echo $key['descricao']?>
                                        </option>
                                    <?php
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    </div>
    <div class='row-fluid'>
        <br/>
        <p class='tac'>
            <button class="tac btn" onclick="javascript: if (document.frm_produto.btn_acao.value == '') { document.frm_produto.btn_acao.value='LISTAR'; document.frm_produto.submit() }else{ alert('<?=traduz("Aguarde Submissão")?>') }" ><?=traduz('Pesquisar')?></button>
        </p>
    </div>

<?php } else { ?>

    <input type='hidden' name='linha' value='<?=$linha?>' />
    <div class="titulo_tabela">  <?=$linha_macrofamilia; ?> <?=traduz('Selecionada')?></div>
    <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="tabela"> <?=$linha_macrofamilia; ?> <?=traduz('selecionada')?> : </label>
<?php
                        if (strlen($linha) > 0) {
                            $sql = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha = $linha;";
                            $res = pg_exec($con,$sql);
                            if (pg_numrows($res) > 0) {
                                echo "<label class='control-label' for='tabela'>".pg_result($res,0,0)."</label>";
                            }
                        }
?>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="tabela"> <?=traduz('Família selecionada')?> : </label>
<?php
                        if (strlen($familia) > 0) {
                            $sql = "SELECT descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND familia = $familia;";
                            $res = pg_exec($con,$sql);
                            if (pg_numrows($res) > 0) {
                                echo "<label class='control-label' for='tabela'>".pg_result($res,0,0)."</label>";

                            }
                        }
                    ?>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <p align='center'>
            <input type="button" class='btn btn-success' value='<?=traduz("Localizar outra")?> <?=$linha_macrofamilia; ?>' onclick="javascript: window.location='<?=$PHP_SELF?>'" />
        </p>

        <br />

        </div>
<?php }

echo "<br>\n";

if (strlen(trim($msg_erro)) == 0) {
    if ($btn_acao == "LISTAR" AND (strlen($linha) > 0 OR (in_array($login_fabrica, array(175)) AND strlen($familia) > 0)) ) {
        if (strlen($familia) > 0){
            $cond_familia = " AND tbl_familia.familia = {$familia} ";
        }

        if (strlen($linha) > 0){
            $cond_linha = " AND tbl_linha.linha = {$linha} ";
        }

        $campo_fabrica = (in_array($login_fabrica, array(175))) ? "tbl_familia" : "tbl_linha";

            $sql = "
                SELECT  tbl_produto.produto                                         ,
                        tbl_produto.referencia                                      ,
                        tbl_produto.referencia_fabrica AS produto_referencia_fabrica,
                        tbl_produto.descricao                                       ,
                        tbl_produto.linha                                           ,
                        tbl_produto.voltagem                                        ,
                        tbl_produto.garantia                                        ,
                        tbl_produto.garantia_horas                                  ,
                        tbl_produto.mao_de_obra              AS mao_de_obra_posto   ,
                        tbl_produto.mao_de_obra_admin                               ,
                        tbl_produto.mao_de_obra_troca                               ,
                        tbl_produto.radical_serie                                   ,
                        tbl_produto.ativo                                           ,
                        tbl_produto.numero_serie_obrigatorio                        ,
                        tbl_produto.produto_critico                                 ,
                        tbl_familia.familia                                         ,
                        tbl_familia.descricao                AS familia_descricao   ,
                        tbl_marca.marca,
                        tbl_marca.nome,
                        tbl_produto.origem,
                        tbl_produto.troca_obrigatoria,
                        tbl_classe.classe,
                        tbl_classe.nome AS classe_nome,
                        tbl_produto.entrega_tecnica,
                        tbl_produto.abre_os,
                        tbl_produto.lista_troca
                FROM    tbl_produto
           LEFT JOIN    tbl_familia USING (familia)
           LEFT JOIN    tbl_marca USING (marca)
           LEFT JOIN    tbl_linha on tbl_produto.linha =  tbl_linha.linha
           LEFT JOIN    tbl_classe_produto ON tbl_produto.produto = tbl_classe_produto.produto
           LEFT JOIN    tbl_classe ON tbl_classe_produto.classe = tbl_classe.classe
                WHERE   $campo_fabrica.fabrica   = $login_fabrica
                $cond_linha
                $cond_familia
          ORDER BY      tbl_familia.descricao,
                        tbl_produto.referencia";
#echo nl2br($sql);
        $res = pg_exec($con,$sql);

        $data = date("d-m-Y-H-i");
        $fileName = "relatorio_acerto_linha_{$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $xreferenciaFabrica = "";
        $criticoProduto = "";

        if ($login_fabrica == 35) {
            $criticoProduto = traduz("Produto Crítico;");
        }
        if ($login_fabrica == 171) {
            $xreferenciaFabrica = traduz("Referência FN;");
        }

        if ($login_fabrica == 52) {
            $head = "Referência;Descrição;Voltagem;Garantia (Meses);M. Obra Posto (". $real .");M. Obra Admin (". $real .");M. Obra Troca (". $real .");Origem;Radical Nº Série;Nº Série Obrigatório;Ativo;Troca obrigatória;Lista Troca;Marca;Familia;\r\n";
        } else if (!in_array($login_fabrica,[35,176,179])) {
    		if ($login_fabrica == 190) {
                $head = "{$xreferenciaFabrica}Referência;Descrição;Voltagem;Garantia (Meses);M. Obra Posto (R$);M. Obra Treinamento (R$);Origem;Nº Série Obrigatório;Ativo;Troca obrigatória;Lista Troca;Familia;\r\n";
    		} else {
                $head = "{$xreferenciaFabrica}Referência;Descrição;Voltagem;Garantia (Meses);M. Obra Posto (R$);M. Obra Admin (R$);M. Obra Troca (R$);Origem;Nº Série Obrigatório;Ativo;Troca obrigatória;Lista Troca;Familia;\r\n";
    		}
	    } elseif (in_array($login_fabrica, [151])) {
            $head = "{$xreferenciaFabrica}Referência;Descrição;Voltagem;Garantia (Meses);M. Obra Posto (". $real .");M. Obra Admin (". $real .");M. Obra Troca (". $real .");Origem;Radical Nº Série;Nº Série Obrigatório;Ativo;Troca obrigatória;Lista Troca;Troca Direta;Familia;\r\n";
        } else {
            $head = "{$xreferenciaFabrica}Referência;Descrição;Voltagem;Garantia (Meses);M. Obra Posto (".  $real .");M. Obra Admin (". $real .");M. Obra Troca (". $real .");Origem;Radical Nº Série;$criticoProduto Nº Série Obrigatório;Ativo;Troca obrigatória;Lista Troca;Familia;\r\n";
        }

        fwrite($file, $head);
        $body = "";

        for ($i = 0 ; $i < pg_numrows($res); $i++) {

            $produto                    = pg_result($res,$i,'produto');
            $referencia                 = pg_result($res,$i,'referencia');
            $produto_referencia_fabrica = pg_result($res,$i,'produto_referencia_fabrica');
            $descricao                  = pg_result($res,$i,'descricao');
            $voltagem                   = pg_result($res,$i,'voltagem');
            $garantia                   = pg_result($res,$i,'garantia');
            $garantia_horas             = pg_result($res,$i,'garantia_horas');
            $produto_critico            = pg_result($res,$i,'produto_critico');

            $mao_de_obra_posto          = pg_result($res,$i,'mao_de_obra_posto');
            $mao_de_obra_posto          = number_format($mao_de_obra_posto, 2, ',', '');

            $mao_de_obra_admin          = pg_result($res,$i,'mao_de_obra_admin');
            $mao_de_obra_admin          = number_format($mao_de_obra_admin, 2, ',', '');

            $mao_de_obra_troca          = pg_result($res,$i,'mao_de_obra_troca');//HD 247716
            $mao_de_obra_troca          = number_format($mao_de_obra_troca, 2, ',', '');//HD 247716

            $radical_serie              = pg_result($res,$i,'radical_serie');
            $ativo                      = pg_result($res,$i,'ativo');
            $numero_serie_obrigatorio   = pg_result($res,$i,'numero_serie_obrigatorio');
            $familia                    = pg_result($res,$i,'familia');
            $familia_descricao          = pg_result($res,$i,'familia_descricao');
            $marca                      = pg_fetch_result($res, $i, 'marca');
            $marca_descricao            = pg_fetch_result($res, $i, 'nome');
            $troca_obrigatoria          = pg_result($res,$i,'troca_obrigatoria');
            $lista_troca                = pg_result($res,$i,'lista_troca');
            $origem                     = pg_result($res,$i,'origem');
            $linha                      = pg_result($res,$i,'linha');
            $aux_classe                 = pg_result($res,$i,'classe');
            $entrega_tecnica            = pg_result($res,$i,'entrega_tecnica');
            $abre_os                    = pg_result($res,$i,"abre_os");


            if (strtoupper($origem) == "NAC") $origem_excel = "Nacional";
            if (strtoupper($origem) == "IMP") $origem_excel = "Importado";
            if (strtoupper($origem) == "USA") $origem_excel = "Importado USA";
            if (strtoupper($origem) == "ASI") $origem_excel = "Importado Asia";


            $ativo_excel                    = ($ativo == 't') ? "Sim" : "Não";
            $numero_serie_obrigatorio_excel = ($numero_serie_obrigatorio == 't') ? "Sim" : "Não";
            $troca_obrigatoria_excel        = ($troca_obrigatoria == 't') ? "Sim" : "Não";
            $lista_troca_excel              = ($lista_troca == 't') ? "Sim" : "Não";

	    if (in_array($login_fabrica, [151])) {
                $parametros_adicionais_excel  = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
                $troca_direta_excel     = (!empty($parametros_adicionais_excel['troca_direta']) && $parametros_adicionais_excel['troca_direta'] == 't') ? "Sim" : "Não";
            }
 
            $referenciaFabrica = "";
            $criticoProdutoVal = "";
            if ($login_fabrica == 35) {
                $produto_critico_excel  = ($produto_critico == 't') ? "Sim" : "Não";
                $criticoProdutoVal      = "$produto_critico_excel;";
            }
            if ($login_fabrica == 171) {
                $referenciaFabrica = "$produto_referencia_fabrica;";
            }
            ##procedimento para excel
            if ($login_fabrica == 52) {
                $body .= "$referencia;$descricao;$voltagem;$garantia;$mao_de_obra_posto;$mao_de_obra_admin;$mao_de_obra_troca;$origem_excel;$radical_serie;$numero_serie_obrigatorio_excel;$ativo_excel;$troca_obrigatoria_excel;$lista_troca_excel;$marca_descricao;$familia_descricao;\r\n";
	    } else if (!in_array($login_fabrica,[35,151,176,179])) {

		if ($login_fabrica == 190) {
			$body .= "{$referenciaFabrica}$referencia;$descricao;$voltagem;$garantia;$mao_de_obra_posto;$mao_de_obra_admin;$origem_excel;$numero_serie_obrigatorio_excel;$ativo_excel;$troca_obrigatoria_excel;$lista_troca_excel;$familia_descricao;\r\n";
		} else {
                	$body .= "{$referenciaFabrica}$referencia;$descricao;$voltagem;$garantia;$mao_de_obra_posto;$mao_de_obra_admin;$mao_de_obra_troca;$origem_excel;$numero_serie_obrigatorio_excel;$ativo_excel;$troca_obrigatoria_excel;$lista_troca_excel;$familia_descricao;\r\n";
		}

            } else if(in_array($login_fabrica,[151])){
			 $body .= "{$referenciaFabrica}$referencia;$descricao;$voltagem;$garantia;$mao_de_obra_posto;$mao_de_obra_admin;$mao_de_obra_troca;$origem_excel;$radical_serie;$numero_serie_obrigatorio_excel;$ativo_excel;$troca_obrigatoria_excel;$lista_troca_excel;$troca_direta_excel;$familia_descricao;\r\n";
	    }else{
                $body .= "{$referenciaFabrica}$referencia;$descricao;$voltagem;$garantia;$mao_de_obra_posto;$mao_de_obra_admin;$mao_de_obra_troca;$origem_excel;$radical_serie;$criticoProdutoVal $numero_serie_obrigatorio_excel;$ativo_excel;$troca_obrigatoria_excel;$lista_troca_excel;$familia_descricao;\r\n";
            }
        }

        if (pg_numrows($res) == 0) {
            echo "<p> <div class='alert'><h4>".traduz("Nenhum resultado encontrado").".</h4></div> </p>";
        } else {
            $sqlCount  = "SELECT count(*) FROM (";
            $sqlCount .= $sql;
            $sqlCount .= ") AS count";

            require "_class_paginacao.php";

            // definicoes de variaveis
            $max_links = 11;                // máximo de links à serem exibidos
            $max_res   = 100;                // máximo de resultados à serem exibidos por tela ou pagina
            $mult_pag  = new Mult_Pag();    // cria um novo objeto navbar
            $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

            $res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
?>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type="text/javascript" src="js/jquery.fixedheadertable.min.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript">

$(document).ready(function() {

   // $('.myTable01').fixedHeaderTable({ width: '100%', height: '550', footer: false, cloneHeadToFoot: true, altClass: 'odd', themeClass: 'fancyTable', autoShow: false });

    $('.myTable01').fixedHeaderTable('show', 1300);

	$('#gravando_dados').click(function() {
		$.blockUI({
			message: '<h4><?=traduz("Aguarde ... Gravando Dados")?></h4>'
		});
	});


	$(document).keydown(function (e) {
		if(e.which == 27){
			$.unblockUI();
		}
	});

});
</script>

<?php
            if (pg_numrows($res) > 0) {



                echo "<br/>  <table border='0' cellpadding='2' cellspacing='1' align='center' class='table table-striped table-bordered table-hover table-large'>
                        <thead cellpadding='2' cellspacing='1' align='center'>
                        <tr class='titulo_coluna'>";
                if ($login_fabrica == 171) {
                    echo "   <th>".traduz("Referência FN")."</th>";
                    echo "  <th>".traduz("Referência Grohe")."</th>";
                } else {
                    echo "  <th>".traduz("Referência")."</th>";
                }
                echo "<th>".traduz("Descrição")."</th>
                        <th>".traduz("Voltagem")."</th>
                        <th>".traduz("Garantia (Meses)")."</th>";

				if($login_fabrica <> 151) {
					if ($login_fabrica == 87) {
						echo "   <th>".traduz("Garantia Horas")."     </th>
								<th nowrap>".traduz("M. Obra")."<br>".traduz("Canal")."   </th>";
					} else {
						echo "   <th nowrap>".traduz("M. Obra Posto")." (". $real .")</th>";
					}

					if (!in_array($login_fabrica,array(94,99,101,98,95,104,105,106,129,190))) {
						echo "<th nowrap>".traduz("M. Obra Admin")." (" . $real .")</th>";
					}
				}
		        if (in_array($login_fabrica,array(190))) {
                    echo "<th nowrap>".traduz("M. Obra Treinamento (R$)")."</th>";
                }

                if ($login_fabrica == 35 || $login_fabrica == 72) {
                    echo "<th nowrap>".traduz("M. Obra Troca")." (". $real .")</th>";//HD 247716
                }

                echo "<th>".traduz("Origem")."</th>";
                if (!in_array($login_fabrica,[176,179])) {
                    echo "<th>".traduz("Radical Nº Série")."</th>";
                }
                if ($login_fabrica == 35) {
                    echo "<th>".traduz("Produto Crítico")."</th>";
                }
                echo "<th>".traduz("Nº Série Obrigatório")."</th>";
                echo "<th>".traduz("Ativo")."</th>";
                echo "<th>".traduz("Troca obrigatória")."</th>";

                if ($login_fabrica == 11 || $login_fabrica == 172) {
                    echo "<th>".traduz("Abre OS")."</th>";
                }

                echo "<th>".traduz("Lista Troca")."</th>";
                if (in_array($login_fabrica,array(94,99,101,98,95,104,105,106,189))) {
                    echo "<th>".traduz("Linha")."</th>";
                }

		if (in_array($login_fabrica, [151])) {
                    echo "<th>".traduz("Troca Direta")."</th>";
                }

                if ($login_fabrica == 42) {
                    echo "<th>".traduz("Entrega Técnica")."</th>";
                }

                if($login_fabrica == 52){//hd_chamado=2655572
                    echo "<th>".traduz("Marca")."</th>";
                }
                echo "<th>".traduz("Família")."</th>";

                if ($login_fabrica == 42) {
                    echo "<th>".traduz("Classe")."</th>";
                }

                echo "  </tr>
                    <tbody>";
                if (!in_array($login_fabrica, array(117))) {
                    echo " <tr style='border-top:1px solid white;'>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>";
                    if (in_array($login_fabrica, array(171))) {
                        echo '<td><b>&nbsp;</b></td>';
                    }
                    echo "      <td class='tac'>
                                    <input type='text' class='frm span1' name='vl_garantia' id='vl_garantia' size='5' maxlength='20'>
                                </td>";

					if($login_fabrica <> 151) {
						if ($login_fabrica == 87) {
							echo "   <td>
										<label for='Horas' class='control-label'>".traduz("Horas")."</label>
										<input type='text' class='frm' name='vl_garantia_horas' id='vl_garantia_horas' size='5' maxlength='20'>
									</td>";
						}

						echo "      <td class='tac'>
										<input type='text' class='frm span1' name='vl_mxlao_de_obra_posto' id='vl_mxlao_de_obra_posto' size='7' maxlength='20'>
									</td>";

						if (!in_array($login_fabrica,array(94,99,101,98,95,104,105,106,129))) {
							echo "  <td class='tac'>
										<input type='text' class='frm span1' name='vl_mao_de_obra_admin' id='vl_mao_de_obra_admin' size='7' maxlength='20'>
									</td>";
						}
					}
                    if ($login_fabrica == 35 || $login_fabrica == 72) {
                        echo "  <td class='tac'>
                                    <input type='text' class='frm span1' name='vl_mao_de_obra_troca' id='vl_mao_de_obra_troca' size='7' maxlength='20' />
                                </td>";
                    }
                    echo "      <td class='tac'>
                                <select name='vl_origem' id='vl_origem'>
                                <option value=''>".traduz("ESCOLHA")."</option>";

                    if ($login_fabrica == 129) {
                        echo "<option value='NAC'"; if (strtoupper($origem) == "NAC") echo " SELECTED "; echo ">".traduz("Nacional")."</option>";
                        echo "<option value='IMP'"; if (strtoupper($origem) == "IMP") echo " SELECTED "; echo ">".traduz("Importado")."</option>";
                        echo "<option value='USA'"; if (strtoupper($origem) == "USA") echo " SELECTED "; echo ">".traduz("Importado USA")."</option>";
                        echo "<option value='ASI'"; if (strtoupper($origem) == "ASI") echo " SELECTED "; echo ">".traduz("Importado Asia")."</option>";
                    } else if ($login_fabrica == 35) {
                        echo "<option value='NAC'"; if ($origem == "NAC") echo " SELECTED "; echo ">".traduz("Nacional")."</option>";
                        echo "<option value='IMP'"; if ($origem == "IMP") echo " SELECTED "; echo ">".traduz("Importado")."</option>";
                    } else {
                        echo "<option value='Nac'"; if (ucfirst($origem) == "Nac") echo " SELECTED "; echo ">".traduz("Nacional")."</option>";
                        echo "<option value='Imp'"; if (ucfirst($origem) == "Imp") echo " SELECTED "; echo ">".traduz("Importado")."</option>";
                    }
                    echo "      </select>
                                </td>";
					if (!in_array($login_fabrica,[176,179])) {
                        echo " <td class='tac'>
                                    <input type='text' class='frm span2' name='vl_radical_serie' id='vl_radical_serie' size='5' maxlength='10'>
                                </td>";
                    }
                    if ($login_fabrica == 35) {
                        echo "
                            <td class='tac'>
                                <label class='checkbox' for=''>
                                    <input type='checkbox' class='frm' name='vl_produto_critico' id='vl_produto_critico' />
                                </label>
                            </td>
                        ";
                    }
                    echo "      <td class='tac'>
                                    <label class='checkbox' for=''>
                                        <input type='checkbox' class='frm' name='vl_numero_serie_obrigatorio' id='vl_numero_serie_obrigatorio'>
                                    </label>
                                </td>

                                <td class='tac'>
                                    <label class='checkbox' for=''>
                                        <input type='checkbox' name='vl_ativo' id='vl_ativo' class='frm'>
                                    </label>
                                </td>
                                <td class='tac'>
                                    <label class='checkbox' for=''>
                                        <input type='checkbox' name='vl_troca_obrigatoria' id='vl_troca_obrigatoria' class='frm'>
                                    </label>
                                </td>";

                    if ($login_fabrica == 11 or $login_fabrica == 172) {
                        echo "  <td class='tac'>
                                    <label class='checkbox' for=''>
                                        <input type='checkbox' name='vl_abre_os' id='vl_abre_os' class='frm'>
                                    </label>
                                </td>";
                    }

                    echo "<td class='tac'><input type='checkbox' name='vl_lista_troca' id='vl_lista_troca' class='frm'></td>\n";

		    if (in_array($login_fabrica, [151])) {
                        echo "<td class='tac'><input type='checkbox' name='vl_troca_direta' id='vl_troca_direta' class='frm'></td>\n";
                    }

                    if ($login_fabrica == 42) {
                        echo "<td class='tac'><input type='checkbox' name='vl_entrega_tecnica' id='vl_entrega_tecnica' class='frm'></td>\n";
                    }

                    $sqlt = "SELECT  *
                    FROM    tbl_linha
                    WHERE   tbl_linha.fabrica = $login_fabrica
                    ORDER BY tbl_linha.nome;";
                    $rest = pg_exec($con,$sqlt);

                    $sqlt = "SELECT *
                            FROM tbl_linha
                            WHERE tbl_linha.fabrica = $login_fabrica
                        ORDER BY tbl_linha.nome;";
                    $rest = pg_exec($con,$sqlt);

                    if (in_array($login_fabrica,array(94,99,101,98,95,104,105,106,189))) {
                        echo "  <td class='tac'>
                                    <select name='vl_linha' class='vl_linha' id='vl_linha'>";
                                    for ($t = 0 ; $t < pg_numrows($rest) ; $t++)
                                    {
                                    $linha_codigo	= trim(pg_result($rest,$t,linha));
                                    $linha_nome		= trim(pg_result($rest,$t,nome));

                                    echo "<option value='$linha_codigo'>".$linha_nome."</option>\n";
                                    }
                        echo "      </select>
                                </td>";
                    }

                    if ($login_fabrica == 52) { //hd_chamado=2655572
                        echo "  <td class='tac'>";

                            $sqlm = "SELECT tbl_marca.marca, tbl_marca.nome
                                    FROM tbl_marca
                                    WHERE tbl_marca.fabrica = $login_fabrica
                                    AND tbl_marca.ativo = true
                                ORDER BY tbl_marca.nome;";
                            $resm = pg_query($con,$sqlm);

                        if (pg_numrows($resm) > 0) {
                            echo "<select class='frm' style='width: 200px;' name='vl_marca' id='vl_marca'>
                                    <option value=''>".traduz("ESCOLHA")."</option>";

                            for ($m = 0 ; $m < pg_num_rows($resm) ; $m++) {
                                $aux_marca   = trim(pg_fetch_result($resm, $m, 'marca'));
                                $aux_marca_descricao = trim(pg_fetch_result($resm, $m, 'nome'));

                                echo "<option value='$aux_marca'";if ($marca == $aux_marca) echo " SELECTED "; echo ">$aux_marca_descricao</option>";
                            }
                            echo "</select>";
                        }
                        echo "</td>";
                    }

                    echo "  <td class='tac'>";

                    $sqlf = "SELECT *
                            FROM tbl_familia
                            WHERE tbl_familia.fabrica = $login_fabrica
                        ORDER BY tbl_familia.descricao;";

                    $resf = pg_exec($con,$sqlf);

                    if (pg_numrows($resf) > 0) {
                        echo "      <select class='frm' style='width: 200px;' name='vl_familia' id='vl_familia'>
                                <option value=''>".traduz("ESCOLHA")."</option>";

                        for ($x = 0 ; $x < pg_numrows($resf) ; $x++) {
                            $aux_familia   = trim(pg_result($resf,$x,familia));
                            $aux_descricao = trim(pg_result($resf,$x,descricao));

                            echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>";
                        }
                        echo "      </select>";
                    }
                    echo "   </td>";

                    if ($login_fabrica == 42) {
?>
                        <td>
                            <select name="vl_classe" id="vl_classe" class="frm" >
                            <option value=""><?=traduz('ESCOLHA')?></option>
<?php
                        $sqlC = "SELECT classe, nome FROM tbl_classe WHERE fabrica = $login_fabrica ORDER BY nome";
                        $resC = pg_query($con,$sqlC);
                        if (pg_numrows($resC) > 0) {
                            for($z = 0; $z < pg_numrows($resC); $z++) {
                                $classe      = pg_result($resC,$z,'classe');
                                $nome_classe = pg_result($resC,$z,'nome');

                                echo "<option value='$classe'";
                                echo ($classe == $aux_classe) ? "SELECTED" : "";
                                echo ">$nome_classe</option>";
                            }
                        }
?>
                            </select>
                        </td>
<?php
                    }
                    echo "</tr>";
                }
            }
?>
		<script type="text/javascript">
			$(document).ready(function() {
                $('#vl_voltagem').change(function(){
                    var campo_origem = $('#vl_voltagem').val();
                    if(campo_origem !=''){
                        $('.res_voltagem').val(campo_origem);
                    }else{
                        $('.res_voltagem').val('');
                    }
                });

				$('#vl_garantia').change(function(){
					var campo_origem = $('#vl_garantia').val();
					if(campo_origem !=''){
						$('.res_garantia').val(campo_origem);
					}else{
						$('.res_garantia').val('');
					}
				}).numeric();

				$('#vl_garantia_horas').change(function(){
					var campo_origem = $('#vl_garantia_horas').val();
					if(campo_origem !=''){
						$('.res_garantia_horas').val(campo_origem);
					}else{
						$('.res_garantia_horas').val('');
					}
				});

				$('#vl_mxlao_de_obra_posto').change(function(){
					var campo_origem = $('#vl_mxlao_de_obra_posto').val();
					if(campo_origem !=''){
						$('.res_mxlao_de_obra_posto').val(campo_origem);
					}else{
						$('.res_mxlao_de_obra_posto').val('');
					}
				}).numeric({allow: ','});

				$('#vl_mao_de_obra_admin').change(function(){
					var campo_origem = $('#vl_mao_de_obra_admin').val();
					if(campo_origem !=''){
						$('.res_mao_de_obra_admin').val(campo_origem);
					}else{
						$('.res_mao_de_obra_admin').val('');
					}
				}).numeric({allow: ','});

				$('#vl_mao_de_obra_troca').change(function(){
					var campo_origem = $('#vl_mao_de_obra_troca').val();
					if(campo_origem !=''){
						$('.res_mao_de_obra_troca').val(campo_origem);
					}else{
						$('.res_mao_de_obra_troca').val('');
					}
				});

				$('#vl_origem').change(function(){
					var campo_origem = $('#vl_origem').val();
					if(campo_origem !=''){
						$('.res_origem').val(campo_origem);
					}else{
						$('.res_origem').val('');
					}
				});

				$('#vl_radical_serie').change(function(){
					var campo_origem = $('#vl_radical_serie').val();
					if(campo_origem !=''){
						$('.res_radical_serie').val(campo_origem);
					}else{
						$('.res_radical_serie').val('');
					}
				});

				$('#vl_produto_critico').click(function(){
					if($('#vl_produto_critico').is(':checked')){
						$('.res_produto_critico').attr('checked','checked');
					}else{
						$('.res_produto_critico').removeAttr('checked');
					}
				});
				$('#vl_numero_serie_obrigatorio').click(function(){
					if($('#vl_numero_serie_obrigatorio').is(':checked')){
						$('.res_numero_serie_obrigatorio').attr('checked','checked');
					}else{
						$('.res_numero_serie_obrigatorio').removeAttr('checked');
					}
				});

				$('#vl_ativo').click(function(){
					if($('#vl_ativo').is(':checked')){
						$('.res_ativo').attr('checked','checked');
					}else{
						$('.res_ativo').removeAttr('checked');
					}
				});

				$('#vl_troca_obrigatoria').click(function(){
					if($('#vl_troca_obrigatoria').is(':checked')){
						$('.res_troca_obrigatoria').attr('checked','checked');
					}else{
						$('.res_troca_obrigatoria').removeAttr('checked');
					}
				});

                $('#vl_abre_os').click(function(){
                    if($('#vl_abre_os').is(':checked')){
                        $('.res_abre_os').attr('checked','checked');
                    }else{
                        $('.res_abre_os').removeAttr('checked');
                    }
                });

		$('#vl_lista_troca').click(function(){
                    if($('#vl_lista_troca').is(':checked')){
                        $('.res_lista_troca').attr('checked','checked');
                    }else{
                        $('.res_lista_troca').removeAttr('checked');
                    }
                });


                $('#vl_entrega_tecnica').click(function(){
                    if($('#vl_entrega_tecnica').is(':checked')){
                        $('.res_entrega_tecnica').attr('checked','checked');
                    }else{
                        $('.res_entrega_tecnica').removeAttr('checked');
                    }
                });

				$('#vl_linha').change(function(){
					var campo_origem = $('#vl_linha').val();
					if(campo_origem !=''){
						$('.res_linha').val(campo_origem);
					}else{
						$('.res_linha').val('');
					}
				});

				$('#vl_familia').change(function(){
					var campo_origem = $('#vl_familia').val();
					if(campo_origem !=''){
						$('.res_familia').val(campo_origem);
					}else{
						$('.res_familia').val('');
					}
				});

                $('#vl_marca').change(function(){
                    var campo_origem = $('#vl_marca').val();
                    if(campo_origem !=''){
                        $('.res_marca').val(campo_origem);
                    }else{
                        $('.res_marca').val('');
                    }
                });

				$('#vl_classe').change(function(){
					var campo_origem = $('#vl_classe').val();
					if(campo_origem !=''){
						$('.res_classe').val(campo_origem);
					}else{
						$('.res_classe').val('');
					}
				});

			});
		</script>

<?php

            for ($i = 0 ; $i < pg_numrows($res); $i++) {

                $cor = ($i % 2 == 0) ? "#F7F5F0": '#F1F4FA';

                if (strlen($msg_erro) == 0) {

                    $produto                  = pg_result($res,$i,'produto');
                    $referencia               = pg_result($res,$i,'referencia');
                    $produto_referencia_fabrica = pg_result($res,$i,'produto_referencia_fabrica');
                    $descricao                = pg_result($res,$i,'descricao');
                    $voltagem                 = pg_result($res,$i,'voltagem');
                    $garantia                 = pg_result($res,$i,'garantia');
                    $garantia_horas           = pg_result($res,$i,'garantia_horas');

                    $mao_de_obra_posto        = pg_result($res,$i,'mao_de_obra_posto');
                    $mao_de_obra_posto        = number_format($mao_de_obra_posto, 2, ',', '');

                    $mao_de_obra_admin        = pg_result($res,$i,'mao_de_obra_admin');
                    $mao_de_obra_admin        = number_format($mao_de_obra_admin, 2, ',', '');

                    $mao_de_obra_troca        = pg_result($res,$i,'mao_de_obra_troca');//HD 247716
                    $mao_de_obra_troca        = number_format($mao_de_obra_troca, 2, ',', '');//HD 247716

                    $radical_serie            = pg_result($res,$i,'radical_serie');
                    $ativo                    = pg_result($res,$i,'ativo');
                    $numero_serie_obrigatorio = pg_result($res,$i,'numero_serie_obrigatorio');
                    $familia                  = pg_result($res,$i,'familia');
                    $familia_descricao        = pg_result($res,$i,'familia_descricao');
                    $marca                    = pg_fetch_result($res, $i, 'marca');
                    $marca_descricao          = pg_fetch_result($res, $i, 'nome');
                    $troca_obrigatoria        = pg_result($res,$i,'troca_obrigatoria');
                    $lista_troca	          = pg_result($res,$i,'lista_troca');
		    if (in_array($login_fabrica, [151])) {
                        $parametros_adicionais = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);
                        $troca_direta         = (!empty($parametros_adicionais['troca_direta']) && $parametros_adicionais['troca_direta'] == "t") ? "t" : "f";
                    }
                    $origem                   = pg_result($res,$i,'origem');
                    $linha                    = pg_result($res,$i,'linha');
                    $aux_classe               = pg_result($res,$i,'classe');
                    $entrega_tecnica          = pg_result($res,$i,'entrega_tecnica');
                    $abre_os                  = pg_result($res,$i,"abre_os");
                    $produto_critico          = pg_result($res,$i,'produto_critico');

                } else {

                    $produto                  = $_POST["produto_".$i];
                    $referencia               = $_POST["referencia_".$i];
                    $produto_referencia_fabrica = pg_result($res,$i,'produto_referencia_fabrica');
                    $descricao                = stripslashes($_POST["descricao_".$i]);
                    $voltagem                 = $_POST["voltagem_".$i];
                    $garantia                 = $_POST["garantia_".$i];
                    $garantia_horas           = $_POST["garantia_horas_".$i];

                    $mao_de_obra_posto        = $_POST["mxlao_de_obra_posto_".$i];
                    $mao_de_obra_troca        = $_POST["mao_de_obra_troca_".$i];
                    $mao_de_obra_admin        = $_POST["mao_de_obra_admin_".$i];
                    $radical_serie            = $_POST["radical_serie_".$i];
                    $ativo                    = $_POST["ativo_".$i];
                    $numero_serie_obrigatorio = $_POST["numero_serie_obrigatorio_".$i];
                    $familia                  = $_POST["familia_".$i];
                    $marca                  = $_POST["marca_".$i];
                    $familia_descricao        = $_POST["familia_descricao_".$i];
                    $troca_obrigatoria        = $_POST["troca_obrigatoria_".$i];
                    $lista_troca	          = $_POST["lista_troca_".$i];
		    if (in_array($login_fabrica, [151])) {
                        $troca_direta         = $_POST["troca_direta_" . $i];
                    }
                    //takashi 14-11
                    $origem                   = $_POST["origem".$i];
                    $linha                    = $_POST["linha_".$i];
                    //takashi 14-11
                    if ($produto == $produto_erro) $cor ="#FF0000";

                    $aux_classe               = $_POST["classe_".$i];
                    $entrega_tecnica          = $_POST["entrega_tecnica_".$i];
                    $abre_os                  = $_POST["abre_os_".$i];
                    $produto_critico          = $_POST["produto_critico_".$i];

                }
                //left right button
                //echo "<th>$familia_descricao</th>\n";

                echo "<tr bgcolor='$cor'>\n";
                if ($login_fabrica == 171) {
                    echo "<td nowrap align='center'>$produto_referencia_fabrica</td>";
                }
                echo "<td nowrap align='center'>";
                echo "<input type='hidden' name='produto_$i' value='$produto'>\n";
                echo "<input type='hidden' name='referencia_$i' value='$referencia'>\n";
                echo "<input type='hidden' name='descricao_$i' value='$descricao'>\n";

                echo "<input type='hidden' name='voltagem_$i' value='$voltagem'>\n";
                echo $referencia;

                echo "</td>\n";
                echo "<td nowrap class='tal'>$descricao</td>\n";
                if ($login_fabrica == 74 ) {
                    echo "<td nowrap class='tac'>";
                    echo "<select name='voltagem_$i' class='res_voltagem' id='res_voltagem'>";
                    $sqlv = "SELECT  DISTINCT voltagem
                            FROM    tbl_produto
                            WHERE   tbl_produto.fabrica_i = $login_fabrica
                            ";
                    $resv = pg_query($con,$sqlv);
                    for ($w = 0 ; $w < pg_num_rows($resv) ; $w++) {
                        $voltagem2   = trim(pg_fetch_result($resv,$w,'voltagem'));
                        if (strlen($voltagem2)== 0 ){
                            $voltagem2 = "ESCOLHA";
                        }
                        echo "<option value='$voltagem2'"; if (($voltagem2 == $voltagem) OR ($voltagem2 == 'ESCOLHA')){ echo "SELECTED"; } echo "  >".$voltagem2."</option>\n";
                    }
                    echo "</select>";
                    echo "</td>\n";
                } else {
                    echo "<td nowrap class='tac'>$voltagem</td>\n";

                }
                echo "<td nowrap class='tac'><input type='text' class='frm res_garantia span1' name='garantia_$i' value='$garantia' size='5' maxlength='20' id='res_garantia'></td>\n";

                if ($login_fabrica == 87) {
                echo "     <td nowrap align='center'>
                                <input type='text' class='frm res_garantia_horas' name='garantia_horas_$i' value='$garantia_horas' size='5' maxlength='20' id='res_garantia_horas'>
                            </td>";
                }
				if($login_fabrica <> 151) {
					echo "      <td nowrap class='tac'><input type='text' class='frm res_mxlao_de_obra_posto span1' id='res_mxlao_de_obra_posto' name='mxlao_de_obra_posto_$i' value='$mao_de_obra_posto' size='7' maength='20'></td>\n";

					if (!in_array($login_fabrica,array(94,99,101,98,95,104,105,106,129))) {
						echo "  <td nowrap class='tac'>
									<input type='text' class='frm res_mao_de_obra_admin span1' name='mao_de_obra_admin_$i' value='$mao_de_obra_admin' size='7' maxlength='20' id='res_mao_de_obra_admin'>
								</td>";
					}
				}

                if ($login_fabrica == 35 || $login_fabrica == 72) {//HD 247716
                    echo "  <td nowrap class='tac'>
                                <input type='text' class='frm res_mao_de_obra_troca span1' name='mao_de_obra_troca_$i' id='res_mao_de_obra_troca' value='$mao_de_obra_troca' size='7' maxlength='20' />
                            </td>";
                }


                    echo "  <td nowrap class='tac'>
                                <select name='origem_$i' class='res_origem' id='res_origem'>
                                <option value=''>ESCOLHA</option>";
                if ($login_fabrica == 129) {
                    echo "<option value='NAC'"; if (strtoupper($origem) == "NAC") echo " SELECTED "; echo ">".traduz("Nacional")."</option>";
                    echo "<option value='IMP'"; if (strtoupper($origem) == "IMP") echo " SELECTED "; echo ">".traduz("Importado")."</option>";
                    echo "<option value='USA'"; if (strtoupper($origem) == "USA") echo " SELECTED "; echo ">".traduz("Importado USA")."</option>";
                    echo "<option value='ASI'"; if (strtoupper($origem) == "ASI") echo " SELECTED "; echo ">".traduz("Importado Asia")."</option>";

                } else if ($login_fabrica == 35) {
                    echo "<option value='NAC'"; if ($origem == "NAC") echo " SELECTED "; echo ">".traduz("Nacional")."</option>";
                    echo "<option value='IMP'"; if ($origem == "IMP") echo " SELECTED "; echo ">".traduz("Importado")."</option>";
                } else {
                    echo "<option value='Nac'"; if (ucfirst(strtolower($origem)) == "Nac") echo " SELECTED "; echo ">".traduz("Nacional")."</option>";
                    echo "<option value='Imp'"; if (ucfirst(strtolower($origem)) == "Imp") echo " SELECTED "; echo ">".traduz("Importado")."</option>";
                }
                echo " </select>
                            </td>";
				if (!in_array($login_fabrica,[176,179])) {
                    echo "  <td nowrap class='tac'>
                                <input type='text' class='frm res_radical_serie span2' name='radical_serie_$i' value='$radical_serie' size='5' maxlength='10' id='res_radical_serie'>
                            </td>";
                }
                if ($login_fabrica == 35) {
                    echo "
                        <td class='tac'>
                            <label class='checkbox' for=''>
                                <input type='checkbox' class='frm res_produto_critico' name='produto_critico_$i' id='produto_critico_$i' value='t' ".(($produto_critico == 't') ? " checked" : "")." />
                            </label>
                        </td>
                    ";
                }
                echo "      <td nowrap class='tac'>
                                <label class='checkbox' for=''>
                                    <input type='checkbox' class='frm res_numero_serie_obrigatorio' id='res_numero_serie_obrigatorio' name='numero_serie_obrigatorio_$i'"; if ($numero_serie_obrigatorio == 't' ) echo " checked"; echo " value='t'>
                                </label>
                            </td>
                            <td nowrap class='tac'>
                                <label class='checkbox' for=''>
                                    <input type='checkbox' class='frm res_ativo' id='res_ativo' name='ativo_$i'"; if ($ativo == 't' ) echo " checked"; echo " value='t'>
                                </label>
                            </td>
                            <td nowrap class='tac'>
                                <label class='checkbox' for=''>
                                    <input type='checkbox' class='frm res_troca_obrigatoria' id='troca_obrigatoria' name='troca_obrigatoria_$i'"; if ($troca_obrigatoria == 't' ) echo " checked"; echo " value='t'>
                                </label>
                            </td>";

                if ($login_fabrica == 11 or $login_fabrica == 172) {
                    $ao_checked = ($abre_os == "t") ? "checked" : "" ;
                    echo "  <td nowrap class='tac'>
                                <label class='checkbox' for=''>
                                    <input type='checkbox' class='frm res_abre_os' id='abre_os' name='abre_os_$i' value='t' {$ao_checked} >
                                </label>
                            </td>";
                }

                $lt_checked = ($lista_troca == "t") ? "checked" : "" ;
                echo "<td nowrap class='tac'><input type='checkbox' class='frm res_lista_troca' id='lista_troca' name='lista_troca_$i' value='t' {$lt_checked} ></td>\n";

		if (in_array($login_fabrica, [151])) {
                    $td_checked = ($troca_direta == "t") ? "checked" : "";
                    echo "<td nowrap class='tac'><input type='checkbox' " . $td_checked . " class='frm res_troca_direta' id='troca_direta' name='troca_direta_$i' value='t' {$troca_direta_checked} ></td>\n";
                }

                if ($login_fabrica == 42) {
                    $et_checked = ($entrega_tecnica == "t") ? "checked" : "" ;
                    echo "  <td nowrap class='tac'>
                                <label class='checkbox' for=''>
                                    <input type='checkbox' class='frm res_entrega_tecnica' id='entrega_tecnica' name='entrega_tecnica_$i' value='t' {$et_checked} >
                                </label>
                            </td>";
                }

                $sqlt = "SELECT *
                        FROM tbl_linha
                        WHERE tbl_linha.fabrica = $login_fabrica
                    ORDER BY tbl_linha.nome;";
                $rest = pg_exec($con,$sqlt);

                if (in_array($login_fabrica,array(94,99,101,98,95,104,105,106,189))) {
                    echo "  <td nowrap class='tac'>
                                <select name='linha_$i' class='res_linha' id='res_linha'>";
                    for ($t = 0 ; $t < pg_numrows($rest) ; $t++) {
                        $linha_codigo	= trim(pg_result($rest,$t,linha));
                        $linha_nome		= trim(pg_result($rest,$t,nome));

                        echo "<option value='$linha_codigo'"; if ($linha_codigo == $linha) echo " SELECTED "; echo    ">$linha_nome</option>\n";
                    }
                    echo "      </select>
                            </td>";
                }


                if ($login_fabrica == 52) { //hd_chamado=2655572
                    echo "  <td class='tac'>";

                    $sqlm = "SELECT tbl_marca.marca, tbl_marca.nome
                            FROM tbl_marca
                            WHERE tbl_marca.fabrica = $login_fabrica
                            AND tbl_marca.ativo = true
                        ORDER BY tbl_marca.nome;";
                    $resm = pg_query($con,$sqlm);
                    if (pg_numrows($resm) > 0) {
                        echo "<select class='frm res_marca' style='width: 200px;' name='marca_$i' id='res_marca'>
                                <option value=''>ESCOLHA</option>";

                        for ($m = 0 ; $m < pg_num_rows($resm) ; $m++){
                            $aux_marca   = trim(pg_fetch_result($resm, $m, 'marca'));
                            $aux_marca_descricao = trim(pg_fetch_result($resm, $m, 'nome'));

                            echo "<option value='$aux_marca'"; if ($marca == $aux_marca) echo " SELECTED "; echo ">$aux_marca_descricao</option>";
                        }
                        echo "</select>";
                    }
                    echo "</td>";
                }

                // HD 22851
                echo "      <td class='tac'>";

                $sqlf = "SELECT *
                        FROM tbl_familia
                        WHERE tbl_familia.fabrica = $login_fabrica
                    ORDER BY tbl_familia.descricao;";

                $resf = pg_exec($con,$sqlf);

                if (pg_numrows($resf) > 0) {
                    echo "      <select class='frm res_familia' id='res_familia' style='width: 200px; margin: 0 auto;' name='familia_$i'>
                                <option value=''>".traduz("ESCOLHA")."</option>";

                    for ($x = 0 ; $x < pg_numrows($resf) ; $x++) {
                        $aux_familia   = trim(pg_result($resf,$x,familia));
                        $aux_descricao = trim(pg_result($resf,$x,descricao));
                        echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo    ">$aux_descricao</option>\n";
                    }

                    echo "      </select>";
                }

                echo "      </td>";

                if ($login_fabrica == 42) {
?>
                            <td class='tac'>
                                <select name="classe_<?=$i?>" name="classe_<?=$i?>" class="frm res_classe" >
                                <option value=""><?=traduz('ESCOLHA')?></option>
                                <?php
                    $sqlC = "SELECT classe, nome FROM tbl_classe WHERE fabrica = $login_fabrica ORDER BY nome";
                    $resC = pg_query($con,$sqlC);
                    if(pg_numrows($resC) > 0) {
                        for ($z = 0; $z < pg_numrows($resC); $z++) {
                            $classe      = pg_result($resC,$z,'classe');
                            $nome_classe = pg_result($resC,$z,'nome');
                            echo "<option value='$classe'";
                            echo ($classe == $aux_classe) ? "SELECTED" : "";
                            echo ">$nome_classe</option>";
                        }
                    }
?>
                                </select>
                            </td>
<?
                }

                echo "  </tr>";

                $familia_anterior = $familia;

            } # FIM DO FOR
            echo "  </table>
                <input type='hidden' name='produto_qtde' value='".pg_numrows($res)."'>";

            //HD  22851
            echo "  <table width='100%' class='tabela_paginacao' border='0' cellpadding='2' cellspacing='1' align='center'>
                <thead>
                <tr>
                    <th border='0' colspan='10' align='center'>";


        /*
        *******************************************************
        ****    PAGINACAO               ****
        ****    links da paginacao      ****
        *******************************************************
        */
            echo "<br />";

            if ($pagina < $max_links) {
                $paginacao = pagina + 1;
            } else {
                $paginacao = pagina;
            }

        /*
        *******************************************************
        ****    paginacao com restricao de links da paginacao
        ****    pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
        *******************************************************
        */
            $todos_links = $mult_pag->Construir_Links("strings", "sim");

        /*
        *******************************************************
        ****    função que limita a quantidade de links no rodape
        *******************************************************
        */
            $links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

            for ($n = 0; $n < count($links_limitados); $n++) {
                echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
            }

            $resultado_inicial = ($pagina * $max_res) + 1;
            $resultado_final   = $max_res + ( $pagina * $max_res);
            $registros         = $mult_pag->Retorna_Resultado();

            $valor_pagina   = $pagina + 1;
            $numero_paginas = intval(($registros / $max_res) + 1);

            if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

            if ($registros > 0) {
                echo "<br>";
                echo "<font size='2'>".traduz("Resultados de ")." <b>$resultado_inicial</b> a <b>$resultado_final</b> ".traduz("do total de")." <b>$registros</b> ".traduz("registros").".</font>";
                echo "<font color='#cccccc' size='1'>";
                echo traduz("(Página")." <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
                echo "</font>";
                echo "</div>";
            }

        // ##### PAGINACAO ##### //
            echo "</th>";
            echo "</tr>";
            echo "</table>";


            echo "<br />
                <center>
                    <input type='button' class='btn btn-primary' value='".traduz("Gravar")."' id='gravando_dados' onclick=\"javascript: if (document.frm_produto.btn_acao.value == '' ) { document.frm_produto.btn_acao.value='GRAVAR'; document.frm_produto.submit(); } else { document.frm_produto.submit(); }\" ALT='Gravar' border='0' style='cursor:pointer;'>
                </center>
            <br />  ";

            fwrite($file, $body);
            fclose($file);
            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");
            }

            echo "<center>
                    <br /> <a href='xls/{$fileName}' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;".traduz("Gerar Arquivo Excel")."</a> <br />
                </center>
            <br />  ";
        }
    }
}

?>
<input type="hidden" name="numero_paginas" id="numero_paginas" value="<?php echo $numero_paginas;?>" />
</form>
<?php include "rodape.php"; ?>
