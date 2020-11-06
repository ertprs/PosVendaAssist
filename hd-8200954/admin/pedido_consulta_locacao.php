<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";
include_once 'funcoes.php';
if($_POST) {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = trim($_POST['codigo_posto']);
	$nome_posto         = trim($_POST['descricao_posto']);
	$numero_serie       = trim($_POST["numero_serie"]);
	$btn_acao           = $_POST['btn_acao'];

	if (strlen($numero_serie) == 0 and empty($codigo_posto) and empty($data_inicial)) {
		$msg_erro['msg'][] = " Preecha um dos campos para fazer pesquisa";
	}else if (strlen($numero_serie) < 4 and strlen($numero_serie) > 0) {
		$msg_erro['msg'][] = " O campo Número de Série deve ser preenchido com mais caracteres. ";
	}

    if(!empty($data_inicial)){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_inicial";
        }
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    }
    if(!empty($data_final)){
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data_final";
        }
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    }
	
	if((!empty($data_inicial) and empty($data_final)) or (empty($data_inicial) and !empty($data_final))) {
		$msg_erro['msg'][] = "Informe o intervalo de data para pesquisa";
	}
   
}
$layout_menu = "cadastro";
$title = "PEDIDO DE LOCAÇÃO";

include "cabecalho_new.php"
	;
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<script language="JavaScript">
function FuncMouseOver (linha, cor) {
	linha.style.cursor = "hand";
	linha.style.backgroundColor = cor;
}
function FuncMouseOut (linha, cor) {
	linha.style.cursor = "default";
	linha.style.backgroundColor = cor;
}
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array( "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
	});


    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>

<br>
<? } ?>

<form name="frm_pesquisa" method="post" action="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

<input type="hidden" name="acao">

<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

	<div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='numero_serie'>Número de Série</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="numero_serie" id="numero_serie" class='span12' value="<? echo $numero_serie ?>" >&nbsp;
                    </div>
                </div>
            </div>
		</div>
		<div class='span2'></div>
    </div>

    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>


</form>
<?
if ($_POST and !empty($btn_acao) && count($msg_erro['msg']) == 0) {
	$sql =	"SELECT tbl_locacao.locacao                                            ,
					tbl_locacao.pedido                                             ,
					tbl_locacao.serie                                              ,
					tbl_locacao.nota_fiscal                                        ,
					tbl_locacao.codigo_fabricacao                                    , 
					TO_CHAR(tbl_locacao.data_emissao,'DD/MM/YYYY') AS data_emissao,
					tbl_produto.referencia, 
					tbl_produto.descricao,
					tbl_posto.nome, 
					tbl_posto_fabrica.codigo_posto
			FROM tbl_locacao
			JOIN tbl_produto USING(produto)
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica using(posto) 
			WHERE fabrica = $login_fabrica";
	if(!empty($numero_serie)) $sql .= " AND tbl_locacao.serie ~'$numero_serie' ";
	if(!empty($codigo_posto)) $sql .= " AND tbl_posto_fabrica.codigo_posto ='$codigo_posto' ";
	if(!empty($data_inicial) and !empty($data_final)) $sql .= " AND tbl_locacao.data_emissao between '$xdata_inicial' and '$xdata_final' ";
	$sql .= " ORDER BY tbl_locacao.pedido, tbl_locacao.serie; ";
	
	$res = pg_query($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		echo "<br>";
		echo "<table id='tabela_resultado' class='table table-striped table-bordered table-hover table-fixed' >";
		echo "<thead>";
		echo "<tr height='15' class='titulo_coluna'>";
		echo "<th>Pedido</th>";
		echo "<th>Código Posto</th>";
		echo "<th>Nome</th>";
		echo "<th>Produto</th>";
		echo "<th>Descrição</th>";
		echo "<th>Série</th>";
		echo "<th>Nota Fiscal</th>";
		echo "<th>Código Fabricação</th>";
		echo "<th>Data Emissão</th>";
		echo "</tr></thead>";
		for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			$locacao      = pg_fetch_result($res,$i,locacao);
			$pedido       = pg_fetch_result($res,$i,pedido);
			$serie        = pg_fetch_result($res,$i,serie);
			$codigo_posto = pg_fetch_result($res,$i,codigo_posto);
			$nome         = pg_fetch_result($res,$i,nome);
			$referencia   = pg_fetch_result($res,$i,referencia);
			$descricao    = pg_fetch_result($res,$i,descricao);
			$nota_fiscal  = pg_fetch_result($res,$i,nota_fiscal);
			$codigo_fabricacao  = pg_fetch_result($res,$i,codigo_fabricacao);
			$data_emissao = pg_fetch_result($res,$i,data_emissao);
			
			
			echo "<tr>";
			echo "<td class='tac'><a href='pedido_cadastro_locacao.php?locacao=$locacao' target='_blank'>$pedido</a></td>";
			echo "<td nowrap>$codigo_posto</td>";
			echo "<td>$nome</td>";
			echo "<td nowrap>$referencia</td>";
			echo "<td>$descricao</td>";
			echo "<td class='tac'>$serie</td>";
			echo "<td nowrap>$nota_fiscal</td>";
			echo "<td class='tac'>$codigo_fabricacao</td>";
			echo "<td nowrap>$data_emissao</td>";
			echo "</tr>";
		}
		echo "</table>";
            if ($i > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#tabela_resultado" });
                </script>
            <?php
            }
	}else{
		echo "<div class='container'>
            <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>";
	}
	
}
?>

<br>

<?
include "rodape.php";
?>
