<?php
include_once 'dbconfig.php';
include_once 'dbconnect-inc.php';
$admin_privilegios="call_center";
include_once 'autentica_admin.php';
include_once 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

unset($msg_erro);
$msg_erro = array();

$acao    = trim($_REQUEST["acao"]);
$btnacao = trim($_REQUEST["btn_acao"]);

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $status             = $_POST['status'];
    $hd_chamado         = $_POST['hd_chamado'];
    $admin              = $_POST['admin'];

	if(empty($hd_chamado)){
			if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
				$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
				$xdata_inicial = str_replace("'","",$xdata_inicial);
			}else{
				$msg_erro["msg"][]    ="Data Inválida";
				$msg_erro["campos"][] = "data_inicial";
			}

			if(count($msg_erro["msg"]) == 0){
					if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
						$xdata_final =  fnc_formata_data_pg(trim($data_final));
						$xdata_final = str_replace("'","",$xdata_final);
					}else{
						$msg_erro["msg"][]    ="Data Inválida";
						$msg_erro["campos"][] = "data_final";
					}
			}

			if(count($msg_erro["msg"]) == 0){
				$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) {
					$msg_erro["msg"][]    ="Data Inválida";
					$msg_erro["campos"][] = "data_inicial";
				}
			}
			if(count($msg_erro["msg"]) == 0){
				$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) {
					$msg_erro["msg"][]    ="Data Inválida";
					$msg_erro["campos"][] = "data_final";
				}
			}

			if($xdata_inicial > $xdata_final){
				$msg_erro["msg"][]    ="Data Inválida";
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
	}

	if(!empty($xdata_inicial) and !empty($xdata_final)){
			$cond_data = " AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
	}
    if(strlen($produto_referencia)>0){
        $sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $produto = pg_result($res,0,0);
            $cond_1 = " AND tbl_hd_chamado_extra.produto = $produto ";
        }
    }
    if(strlen($status)>0){
        $cond_2 = " AND tbl_hd_chamado.status = '$status'  ";
    }

    if(strlen($hd_chamado)>0){
        $cond_3 = " AND tbl_hd_chamado.hd_chamado = $hd_chamado  ";
    }

    if(strlen($admin)>0){
        $cond_4 = " AND tbl_hd_chamado.admin = $admin ";
    }

    if(count($msg_erro["msg"]) == 0){
			$sqlCallcenter = "
				SELECT  tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado_extra.nome,
						tbl_produto.descricao   ,
						TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
						tbl_hd_chamado.status,
						tbl_admin.nome_completo
				FROM    tbl_hd_chamado
				JOIN    tbl_hd_chamado_extra    USING (hd_chamado)
				JOIN    tbl_produto             USING (produto)
				JOIN    tbl_admin               ON tbl_admin.admin = tbl_hd_chamado.admin
				WHERE   tbl_hd_chamado.fabrica          = $login_fabrica
				AND     tbl_admin.atendente_callcenter  IS TRUE
				$cond_data
				$cond_1
				$cond_2
				$cond_3
				$cond_4
			";
			$resSubmit = pg_query($con,$sqlCallcenter);

			if ( pg_num_rows($resSubmit) > 0) {

				$data = date("YmdHis");
				$file     = "xls/relatorio-atendimentos-".$login_fabrica."_".$data.".xls";
				$fileTemp = "/tmp/relatorio-atendimentos-$login_fabrica.xls";

				echo `rm -f $fileTemp`;

				$fp     = fopen($fileTemp,"w");

				$head = "<table border='1'>
							<thead>
								<tr >
									<th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='$colspan' >RELATÓRIO DOS ATENDIMENTOS ENVIADOS</th>
								</tr>
								<tr>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendimento</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome do Cliente</th>
				";
				if($login_callcenter_supervisor == 't'){
					$head .= "
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome do Atendente</th>
					";
				}
				$head .= "          <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</th>

								</tr>
							</thead>
							<tbody>";

				fwrite($fp, $head );

				for ( $i = 0; $i < pg_num_rows($resSubmit); $i++ ) {
					$body = '<tr>
								<td>' . pg_result($resSubmit,$i,'hd_chamado') . '</td>
								<td>' . pg_result($resSubmit,$i,'nome') . '</td>
					';
					if($login_callcenter_supervisor == 't'){
						$body .= '
								<td>' . pg_result($resSubmit,$i,'nome_completo') . '</td>
						';
					}
					$body .= '  <td>' . pg_result($resSubmit,$i,'descricao') . '</td>
								<td>' . pg_result($resSubmit,$i,'data_abertura') . '</td>
								<td>' . pg_result($resSubmit,$i,'status') . '</td>
							</tr>';


					fwrite($fp, $body);

				}

				fwrite($fp, '</tbody></table>');
				fclose($fp);
				if(file_exists($fileTemp)){
					$envia = "mv $fileTemp $file ";
					system($envia,$retorno);
				}
			}
	}
}


include_once "cabecalho.php";

$plugins = array(
            "autocomplete",
            "tooltip",
            "shadowbox",
            "dataTable"
        );

include_once "plugin_loader.php";

?>

<script type="text/javascript" charset="utf-8">
$(function() {
    $("#data_inicial").datepicker();
    $("#data_final").datepicker();
    $.autocompleteLoad(Array("produto"));
    Shadowbox.init();
    $.dataTableLoad();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
});
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
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
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
            <div class='control-group'>
                <label class='control-label' for='hd_chamado'>Atendimento</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="hd_chamado" name="hd_chamado" class='span12' value="<? echo $hd_chamado ?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status'>Status</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value="">Todos</option>
                            <option value="aberto">Aberto</option>
                            <option value="Cancelado">Cancelado</option>
                            <option value="Resolvido">Resolvido</option>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
    </div>
<?
if($login_callcenter_supervisor == 't'){
?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='hd_chamado'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="admin" id="admin">
                            <option value="">Todos</option>
<?
    $sql = "SELECT  admin,
                    nome_completo
            FROM    tbl_admin
            WHERE   fabrica = $login_fabrica
            AND     atendente_callcenter IS TRUE;
    ";
    $res = pg_query($con,$sql);

    foreach (pg_fetch_all($res) as $key) {

        $selected_status = ( isset($admin) and ($admin == $key['admin']) ) ? "SELECTED" : '' ;
?>
                            <option value="<?php echo $key['admin']?>" <?php echo $selected_status ?> >
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
<?
}else{
?>
    <input type="hidden" name="admin" value="<?=$login_admin?>" />
<?
}
?>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<br />

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
<table id="callcenter_consulta" class='table table-striped table-bordered table-hover table-large' >
    <thead>
        <TR class='titulo_coluna'>
            <th>Atendimento</th>
            <th>Nome do Cliente</th>
<?
        if($login_callcenter_supervisor == 't'){
?>
            <th>Nome do Atendente</th>
<?
        }
?>
            <th>Produto</th>
            <th>Data Abertura</th>
            <th>Status</th>
        </TR >
    </thead>
    <tbody>
<?
        for($y=0;pg_numrows($resSubmit)>$y;$y++){
            $hd_chamado    = pg_fetch_result($resSubmit,$y,hd_chamado);
            $nome          = pg_fetch_result($resSubmit,$y,nome);
            $nome_completo = pg_fetch_result($resSubmit,$y,nome_completo);
            $descricao     = pg_fetch_result($resSubmit,$y,descricao);
            $data_abertura = pg_fetch_result($resSubmit,$y,data_abertura);
            $status        = pg_fetch_result($resSubmit,$y,status);

            if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
?>
        <TR bgcolor='$cor'>
            <TD class="tal">
                <a href="callcenter_interativo.php?hd_chamado=<?=$hd_chamado?>" target="_blank"><?=$hd_chamado?></a>
            </TD>
            <TD class="tac"><?=$nome?></TD>
<?
        if($login_callcenter_supervisor == 't'){
?>
            <TD class="tac"><?=$nome_completo?></TD>
<?
        }
?>
            <TD class="tac"><?=$descricao?></TD>
            <TD class="tac"><?=$data_abertura?></TD>
            <TD class="tac"><?=$status?></TD>
        </TR >
<?
        }
?>
    </tbody>
    <tfoot>
        <TR class='titulo_coluna'>
            <TD class="tac" colspan="<?=$login_callcenter_supervisor == 't' ? 6 : 5 ?>">
                <button class='btn' id="btn_acao" type="button"  onclick="javascript:window.open('<?=$file?>');">Gerar Planilha</button>
            </TD>
        </TR >
    </tfoot>
</table>
<?
    }else{
        echo "<center>Nenhum Resultado Encontrado</center>";
    }
}
include_once "../admin/rodape.php";
?>
