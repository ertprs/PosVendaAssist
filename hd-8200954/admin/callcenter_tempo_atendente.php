<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $status             = $_POST['status'];
    $xatendente         = $_POST['xatendente'];
    $os				    = $_POST['os'];
    $hd_chamado         = $_POST['hd_chamado'];

    $limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

    $cond_1 = "";
    $cond_2 = "";
    $cond_3 = "";
    $cond_4 = "";
    $cond_5 = "";
    $cond_6 = "";

	if(empty($hd_chamado) and empty($os) ) {
		if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
			$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
			$xdata_inicial = str_replace("'","",$xdata_inicial);

		}else{
			$msg_erro["msg"][]    ="Data Inválida";
			$msg_erro["campos"][] = "data_inicial";
		}

		if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
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

        if(!empty($data_inicial)) {
            $cond_6=" and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
        }

	}
    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data_inicial";
    }

    if (strlen($xatendente)>0){
        $cond_atend = "AND tbl_hd_chamado.atendente = $xatendente";
    }


    if(strlen($status)>0){
        $cond_3 =  " and fn_retira_especiais(tbl_hd_chamado.status) = fn_retira_especiais('$status')  ";
    }

    if(!empty($os)) {
        $cond_8 = " AND tbl_hd_chamado_extra.os in ($os)";
    }

    if (strlen($hd_chamado)>0){
        $cond_4 = "AND tbl_hd_chamado.hd_chamado = $hd_chamado";
    }

        $sql = "SELECT distinct tbl_hd_chamado.hd_chamado, tbl_hd_chamado.data, tbl_hd_chamado_extra.nome, tbl_hd_chamado.status, tbl_hd_chamado_extra.os
            FROM tbl_hd_chamado
            inner join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            inner join tbl_hd_chamado_item on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado_item.admin_transferencia notnull
            WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
            $cond_8
            $cond_3
            $cond_4
            $cond_6
            $cond_atend
            ";
    $resChamado = pg_query($con, $sql);
}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

include "cabecalho_new.php";

$plugins = array(
    "datepicker",
    "Shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function mostraTempo(a){

        if($("#linha_chamado_"+a+"_hidden").is(":visible")){
            $("#linha_chamado_"+a+"_hidden").hide();
        }else{
            $("#linha_chamado_"+a+"_hidden").show();
        }
    }

</script>

<style>
    .mais{
        font-size: 18px;
        font-weight: bold;
    }
    .mais:hover{
        cursor: pointer;
    }
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

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
                <div class='control-group '>
                    <label class='control-label' for='hd_chamado'>Atendimento</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                                <input type="text" name="hd_chamado" id="hd_chamado" size="15" maxlength="10" class='span12' value= "<?=$hd_chamado?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group' >
                <label class='control-label' for='os'>OS</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="text" name="os" id="os" size="15" maxlength="10" class='span12' value="<?=$os?>" >
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
                <label class='control-label' for='xatendente'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="xatendente" id="xatendente">
                            <option value=""></option>
						<?

                            $sql = "SELECT admin, login
									from tbl_admin
									where fabrica = $login_fabrica
									and ativo is true
									and (privilegios like '%call_center%' or privilegios like '*')
                                    order by login";
							$res = pg_exec($con,$sql);
							foreach (pg_fetch_all($res) as $key) {

                                    $selected_atendente = ( isset($xatendente) and ($xatendente == $key['admin']) ) ? "SELECTED" : '' ;

?>
                            <option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
                                <?php echo $key['login']?>
                            </option>
<?php
                            }

?>
                        </select>
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
                            <option value=""></option>
                            <?php

                                $sql = "select status, status AS status_desc from tbl_hd_status where fabrica = $login_fabrica order by status";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $key['status'] = ($key['status']);
                                    $key['status_desc'] = ($key['status_desc']);

                                    $selected_status = ( isset($status) and ($status== $key['status']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >
                                        <?php echo $key['status_desc']; ?>
                                    </option>


                                <?php
                                }

                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
    </div>

    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>

<?php
    if ($_POST["btn_acao"] == "submit") {
        if(pg_num_rows($resChamado) > 0){

?>

    <table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-fixed' >
    <thead>
        <TR class='titulo_coluna'>
            <th align='left'>+</TD>
            <th align='left'>Atendimento</TD>
            <th align='left'>Consumidor/Revenda</TD>
            <th align='left'>Data Abertura</TD>
        </TR >
    </thead>
        <?

        $data = date("d-m-Y-H-i");
        $fileName = "relatorio_tempo_chamado_{$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $head = "Atendimento;Consumidor/Revenda;Data Abertura;At. Status;OS;Atendente;Dia(s);Hora(s);Atendente;Dia(s);Hora(s);Atendente;Dia(s);Hora(s);Atendente;Dia(s);hora(s);Atendente;Dia(s);Hora(s);Atendente;Dia(s);Hora(s);Atendente;Dia(s);Hora(s) \r\n";

        fwrite($file, $head);
        $body = '';

        $count = pg_num_rows($resChamado);
        for($a = 0; $a<pg_num_rows($resChamado); $a++){
            $hd_chamado_         = pg_fetch_result($resChamado, $a, "hd_chamado");
            $data               = substr(pg_fetch_result($resChamado, $a, "data"), 0 , 10);
            $nome               = pg_fetch_result($resChamado, $a, "nome");
            $status             = pg_fetch_result($resChamado, $a, "status");
            $os                 = pg_fetch_result($resChamado, $a, "os" );
            list ($ano,$mes,$dia) = explode ("-",$data);
            $data_hd =  "$dia/$mes/$ano";

            $sql = "SELECT tbl_hd_chamado.hd_chamado,
                                tbl_hd_chamado.data,
                                (select max(data) from tbl_hd_chamado_item where tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado) as ultima_data
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                    WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                    AND tbl_hd_chamado.status is not null
                    AND tbl_hd_chamado.posto isnull
                    $cond_1
                    $cond_2
                    $cond_3
                    AND tbl_hd_chamado.hd_chamado = $hd_chamado_
                    $cond_5
                    $cond_6
                    $cond_7
                    $cond_8
                    $cond_atend
                    $limit  ";
            $resSubmit = pg_query($con,$sql);

            $mais = (pg_num_rows($resSubmit) > 0 ) ? "<span onclick='mostraTempo($a)' class='mais'>+</span>" : " " ;

            $body .= " $hd_chamado_; $nome; $data_hd ; $status ; $os ";

            echo "<tr id='linha_chamado_$a '>";
                echo "<td class='tac'> $mais </td>";
                echo "<td><a href='callcenter_interativo_new.php?callcenter=$hd_chamado_' target=blank> $hd_chamado_ </a></td>";
                echo "<td>$nome</td>";
                echo "<td>$data_hd</td>";
            echo "</tr>";
            //bloco de que pega o tempo

            if (isset($resSubmit)) {
                if (pg_num_rows($resSubmit) > 0) {

                    echo "<tr id='linha_chamado_".$a."_hidden' style='display:none !important;'>";
                        echo "<td colspan='5' style='padding:0px !important'>";
                    echo "<table class='table table-bordered table-large table-fixed'>";
                    echo "<thead>";
                       echo "<TR class='titulo_coluna'>";
                            echo "<th align='left'>Atendente</th>";
                            echo "<th align='left'>Tempo</th>";
                        echo "</TR >";
                    echo "</thead>";

                    echo "<tbody>";

                    for($y=0;$y<pg_num_rows($resSubmit);$y++){
                        unset($hora_atendente);
                        $hd_chamado  = pg_fetch_result($resSubmit,$y,'hd_chamado');
                        $data_aberta = pg_fetch_result($resSubmit,$y,'data');
                        $ultima_data = pg_fetch_result($resSubmit,$y,'ultima_data');
                        $sqli = "SELECT hd_chamado_item, tbl_hd_chamado_item.admin, data,admin_transferencia
                                 from tbl_hd_chamado_item
                                 where hd_chamado = $hd_chamado
                                 and admin_transferencia notnull
                                 order by hd_chamado_item ";
                        $resi = pg_query($con, $sqli);
                        $max = array();
                        $min = array();


                        for($i=0;$i<pg_num_rows($resi);$i++){
                            $hd_chamado_item        = pg_fetch_result($resi,$i,'hd_chamado_item');
                            $admin                  = pg_fetch_result($resi,$i,'admin');
                            $admin_transferencia    = pg_fetch_result($resi,$i,'admin_transferencia');
                            $data                   = pg_fetch_result($resi,1+$i,'data');

                                if($i == 0) {
                                    $data_incio = new DateTime($data_aberta);
                                    if(!empty($admin_transferencia)){
                                        $datax = pg_fetch_result($resi,1+$i,'data');
                                        $hora_atendente[$admin] = $data_incio->diff(new DateTime($data_aberta));
					$hora_atendente[$admin_transferencia] = $data_incio->diff(new DateTime($datax));
                                    }else{
                                        $hora_atendente[$admin] = $data_incio->diff(new DateTime($data));
                                    }
                                }elseif(empty($admin_transferencia)){
                                     $data_incio = new DateTime($data_ant);
                                    if(!empty($hora_atendente[$admin])){
                                        $diferenca = $data_incio->diff(new DateTime($data));
                                        $hora_atendente[$admin]->m = $hora_atendente[$admin]->m + $diferenca->m;
                                        $hora_atendente[$admin]->d = $hora_atendente[$admin]->d + $diferenca->d;
                                        $hora_atendente[$admin]->h = $hora_atendente[$admin]->h + $diferenca->h;
                                        $hora_atendente[$admin]->i = $hora_atendente[$admin]->i + $diferenca->i;
                                        $hora_atendente[$admin]->s = $hora_atendente[$admin]->s + $diferenca->s;
                                    }else{
                                        $hora_atendente[$admin] = $data_incio->diff(new DateTime($data));
                                    }
                                }else{
                                    $data_incio = new DateTime($data_ant);
                                    $diferenca = $data_incio->diff(new DateTime($data));

                                    $hora_atendente[$admin_transferencia]->m = $hora_atendente[$admin_transferencia]->m + $diferenca->m;
                                    $hora_atendente[$admin_transferencia]->d = $hora_atendente[$admin_transferencia]->d + $diferenca->d;
                                    $hora_atendente[$admin_transferencia]->h = $hora_atendente[$admin_transferencia]->h + $diferenca->h;
                                    $hora_atendente[$admin_transferencia]->i = $hora_atendente[$admin_transferencia]->i + $diferenca->i;
                                    $hora_atendente[$admin_transferencia]->s = $hora_atendente[$admin_transferencia]->s + $diferenca->s;

                                    if(empty($hora_atendente[$admin])) {
                                        $hora_atendente[$admin] = 0;
                                    }

                                }
                                $data_ant = $data;

                            if(1+$i == pg_num_rows($resi)) {
                                    $data_incio = new DateTime($data_ant);

                                    if(!empty($hora_atendente[$admin_transferencia])){
                                        $diferenca = $data_incio->diff(new DateTime('now'));

                                        $hora_atendente[$admin_transferencia]->m = $hora_atendente[$admin_transferencia]->m + $diferenca->m;
                                        $hora_atendente[$admin_transferencia]->d = $hora_atendente[$admin_transferencia]->d + $diferenca->d;
                                        $hora_atendente[$admin_transferencia]->h = $hora_atendente[$admin_transferencia]->h + $diferenca->h;
                                        $hora_atendente[$admin_transferencia]->i = $hora_atendente[$admin_transferencia]->i + $diferenca->i;
                                        $hora_atendente[$admin_transferencia]->s = $hora_atendente[$admin_transferencia]->s + $diferenca->s;
                                    }else{
                                        $hora_atendente[$admin_transferencia] = $data_incio->diff(new DateTime('now'));
                                    }
                            }
                        }

                        foreach($hora_atendente as $admin => $tempo){
                            $sqlAdmin = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin";
                            $resAdmin = pg_query($con, $sqlAdmin);
                            if(pg_num_rows($resAdmin)){
                                $nome_completo = pg_fetch_result($resAdmin, 0, nome_completo);
                            }else{
                                continue;
                            }

                            $dias =  (strlen($tempo->d) > 0) ? $tempo->d : 0;
                            $hora =  (strlen($tempo->h) > 0) ? $tempo->h : 0;
                            $min =   (strlen($tempo->i) > 0) ? $tempo->i : 0;

			    $body .= "  ; $nome_completo; $dias; $hora:$min ";

                            if($dias > 01){
                                $dias = $dias . " dias ";
                            }else{
                                $dias = $dias . " dia ";
                            }

                            $desc_tempo = $dias .  $hora. "hs ". $min."min ";

                            echo "<tr>";
                                echo "<td>$nome_completo </td>";
                                echo "<td>".$desc_tempo."</td>";
                            echo "</tr>";
                        }
                    }

                    echo "</tbody>";
                    echo "</table>";
                 echo "</td>";
                echo "</tr>";
                }
            // fim do bloco que pega o tempo
            }// fim do for de chamados
        $body .= "\r\n";

    }

?>
                </tbody>
                <tfoot>

                </tfoot>
            </table>

            <?php
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#callcenter_relatorio_atendimento" });
                </script>
            <?php
            }
            ?>
        <br />
        <?php

            $body = $body;
            fwrite($file, $body);
            fclose($file);
            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");
                //echo "xls/{$fileName}";
            }

            echo "<div id='container' style='width: 150px; height: 50px; margin: 0 auto'>
                <img width='20' height='20' src='imagens/excel.png'> <a href='xls/$fileName'>Gerar Arquivo Excel</a>
            </div>";

        }else{
            echo "<div class='container'>
            <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>";
        }



    }

?>


<? include "rodape.php" ?>
