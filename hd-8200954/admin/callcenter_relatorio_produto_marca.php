<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO ATENDIMENTO POR PRODUTO OU MARCA";

$btn_acao = $_REQUEST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial       = $_REQUEST['data_inicial'];
	$data_final         = $_REQUEST['data_final'];
	$produto_referencia = $_REQUEST['produto_referencia'];
	$produto_descricao  = $_REQUEST['produto_descricao'];
	$marcas             = $_REQUEST['marca'];
	$atendentes         = $_REQUEST['atendente'];
    $atendentes         = array_filter($atendentes);
    $marcas             = array_filter($marcas);

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
    $cond_3 = " 1 = 1 ";
    $cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";

	if(strlen($data_inicial) > 0){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
	}

	if(strlen($data_final) > 0){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_final";
	}

	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }
	}
	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_final";
        }
	}

	if($xdata_inicial > $xdata_final){
		$msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data_inicial";
    }

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND fabrica = {$login_fabrica} where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	
    if(count($atendentes) > 0){
        $cond_3 = " tbl_hd_chamado.admin IN(".implode(",",$atendentes).") ";
    }else{
        $msg_erro["msg"][]    ="Informe os atendentes";
        $msg_erro["campos"][] = "atendente";
    }

   
    if(count($msg_erro["msg"]) == 0){

        if(count($marcas) > 0 AND strlen($produto) == 0){
            $campos = "tbl_marca.marca,
                        tbl_marca.nome ,";  
            $order = "tbl_marca.nome";  
            $join_marca = "JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica AND tbl_marca.marca IN(".implode(",",$marcas).")"; 

        }else{
            $campos = "tbl_produto.produto        ,
                        tbl_produto.referencia     ,
                        tbl_produto.descricao      ,";
            $order = "tbl_produto.descricao";
        }

        $sql = "SELECT  $campos
                        tbl_admin.nome_completo    ,
                        tbl_admin.admin            ,
                        count(*) as qtde
                FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                    JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
                    JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_extra.produto
                    $join_marca
                WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                    AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
                    AND  tbl_hd_chamado.status <> 'Cancelado'
                    AND $cond_1
                    AND $cond_2
                    AND $cond_3  
                GROUP BY    $campos
                            tbl_admin.nome_completo,
                            tbl_admin.admin            
                ORDER BY tbl_admin.nome_completo, $order, qtde desc
        ";
        
        $res = pg_exec($con,$sql);

        if(pg_numrows($res)>0){
            $dadosAtendimentos = pg_fetch_all($res);
            $dadosAtendimentos = array_filter($dadosAtendimentos);

            foreach ($dadosAtendimentos as $key => $value) {
                if(count($marcas) > 0 AND strlen($produto) == 0){
                    $produtos[] = $value['marca'];
                }else{
                    $produtos[] = $value['produto'];
                }
                
            }

            if(count($marcas) > 0 AND strlen($produto) == 0){
                $join_produto = " JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.marca IN(".implode(",",$produtos).") ";
                $campo = "tbl_produto.marca";
                $chave = "marca";
            }else{
                $cond = " AND tbl_hd_chamado_extra.produto IN(".implode(",",$produtos).") ";
                $campo = "tbl_hd_chamado_extra.produto";
                $chave = "produto";
            }

            $sql = "SELECT tbl_hd_chamado_item.admin,$campo,
                    count(tbl_hd_chamado_item.hd_chamado_item) AS interacoes
                    FROM tbl_hd_chamado_item
                    JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
                    JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                    $join_produto
                    WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
                    $cond
                    AND tbl_hd_chamado_item.data between '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
                    AND tbl_hd_chamado.status <> 'Cancelado'
                    AND tbl_hd_chamado_item.admin IN(".implode(",",$atendentes).")
                    GROUP BY tbl_hd_chamado_item.admin,$campo";
            $res = pg_exec($con,$sql);
            $dadosInteracoes = pg_fetch_all($res);

            foreach ($dadosInteracoes as $key => $value) {
                $interacoes[$value['admin']][$value[$chave]] = $value['interacoes'];
            }

            if ($_POST["gerar_excel"]) {

                $data = date("d-m-Y-H:i");

                $fileName = "relatorio-atendimentos-interacao-{$login_fabrica}-{$data}.csv";

                $file = fopen("/tmp/{$fileName}", "w");

                if(count($marcas) > 0 AND strlen($produto) == 0){
                    $header = "Marca;Atendente;Qtde Atendimentos;Qtde Interações\n";
                }else{
                    $header = "Produto;Atendente;Qtde Atendimentos;Qtde Interações\n";
                }

                fwrite($file, $header);

                foreach ($dadosAtendimentos as $key => $value) {

                    if(count($marcas) > 0 AND strlen($produto) == 0){
                        $id    = $value['marca'];
                        $nome_produto = $value["nome"];
                    }else{
                        $id    = $value['produto'];
                        $nome_produto = $value['referencia']." - ".$value['descricao'];
                    }

                    $qtde       = $value['qtde'];
                    $admin      = $value['admin'];
                    $nome       = $value['nome_completo'];
                    $qtde_interacoes = $interacoes[$admin][$id];

                    $qtde_interacoes = (strlen($qtde_interacoes) == 0) ? 0 : $qtde_interacoes;

                    $total_atendimentos += $qtde;
                    $total_interacoes += $qtde_interacoes;

                    if(strlen($id)==0){
                        $nome_produto  = "Chamado sem produto";
                    }

                    $dados = "$nome_produto;$nome;$qtde;$qtde_interacoes\n";
                    fwrite($file, $dados);

                }

                fclose($file);

                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");

                    echo "xls/{$fileName}";
                }

                exit;
            }
        }

    }
}

include "cabecalho_new.php";

include_once("callcenter_suggar_assuntos.php");

$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "multiselect",
    "dataTable",
    "ajaxform"
);

include ("plugin_loader.php");
?>
<script type="text/javascript" src="js/highcharts_4.0.3.js"></script>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status){
janela = window.open("callcenter_relatorio_produto_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
$(function() {
    $.dataTableLoad({ table: "#callcenter_relatorio_produto" });
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("#atendente").multiselect({
       selectedText: "# of # selected"
    });

    $("#marca").multiselect({
       selectedText: "# of # selected"
    });

});

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 700;
		var height = 600;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<br/>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
			<label class='control-label' for='natureza_chamado'>Marca</label>
			<div class='controls controls-row'>
				<div class='span4'>
                    <select name="marca[]" id="marca" multiple="multiple">
                        <option value=''></option>
<?php
							$sqlx = "SELECT marca            ,
											nome
									FROM tbl_marca
									WHERE fabrica = $login_fabrica
									AND ativo IS TRUE
									ORDER BY nome";

							$resx = pg_exec($con,$sqlx);
							if(pg_numrows($resx)>0){
								foreach (pg_fetch_all($resx) as $key) {
                                    $selected_marca = ( in_array($key['marca'], $marcas) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['marca']?>" <?php echo $selected_marca ?> >

                                        <?php echo $key['nome']?>

                                    </option>
                                <?php
                                }
							}				
?>
                    </select>
                </div>
            </div>
        </div>
         <div class='span4'>
            <div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='atendente'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                        <select name="atendente[]" id="atendente" multiple="multiple">
    <?PHP                        
                                $sqlx = "SELECT admin            ,
                                                nome_completo
                                        FROM tbl_admin
                                        WHERE fabrica=$login_fabrica
                                        AND atendente_callcenter IS TRUE
                                        AND ativo IS TRUE
                                        ORDER BY nome_completo";

                                $resx = pg_exec($con,$sqlx);

                                foreach (pg_fetch_all($resx) as $key) {
                                    $selected_atendente = ( in_array($key['admin'], $atendentes) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >

                                        <?php echo $key['nome_completo']?>

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
    <p><br/>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
	</p><br/>
</FORM>
<br />

<?

if(strlen($btn_acao)>0){

	if(strlen($msg_erro)==0){ 

        if(count($dadosAtendimentos) > 0){

?>
            <table id="callcenter_relatorio_produto" class='table table-striped table-bordered table-hover table-large' >
                <thead>
                    <TR class='titulo_coluna'>
                        <?php
                            if(count($marcas) > 0 AND strlen($produto) == 0){
                                echo "<th>Marca</th>";
                            }else{
                                echo "<th>Produto</th>";
                            }
                        ?>
                        <th>Atendente</th>
                        <th>Qtde Atendimentos</TD>
                        <th>Qtde Interacoes</TD>
                    </tr>
                </thead>
                <tbody>
<?
			foreach ($dadosAtendimentos as $key => $value) {

                if(count($marcas) > 0 AND strlen($produto) == 0){
                    $id    = $value['marca'];
                    $nome_produto = $value["nome"];
                }else{
    				$id    = $value['produto'];
    				$nome_produto = $value['referencia']." - ".$value['descricao'];
                }

                $qtde       = $value['qtde'];
                $admin      = $value['admin'];
				$nome       = $value['nome_completo'];
                $qtde_interacoes = $interacoes[$admin][$id];

                $qtde_interacoes = (strlen($qtde_interacoes) == 0) ? 0 : $qtde_interacoes;

                $total_atendimentos += $qtde;
                $total_interacoes += $qtde_interacoes;

				if(strlen($id)==0){
					$descricao  = "Chamado sem produto";
				}

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
?>
                    <tr bgcolor='<?=$cor?>'>

                        <td align='left' nowrap>
                            <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>','<?=$id?>','<?=$natureza_chamado?>','<?=$status?>');"><?=$ativo?> <?=$nome_produto?></a>
                        </td>
                        <td align='left' nowrap>
                            <?=$nome?>
                        </td>
                        <td class='tac'><?=$qtde?></td>
            			<td class='tac'><?=$qtde_interacoes?></td>
                    </tr >
<?php
            }
?>
                    
                </tbody>
                <tfoot>
                    <tr class='titulo_coluna'>
                        <td colspan="2">Total</td>
                        <td class='tac'><?=$total_atendimentos?></td>
                        <td class='tac'><?=$total_interacoes?></td>
                    </tr>
                </tfoot>
            </table>

            <br />
            <?php
                $jsonPOST = excelPostToJson($_POST);
            ?>

            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>  

<?	
    	}else{
                echo "<center>Não foram encontrados resultados para esta pesquisa!</center>";
        }
    }
}

?>

<p>

<? include "rodape.php" ?>
