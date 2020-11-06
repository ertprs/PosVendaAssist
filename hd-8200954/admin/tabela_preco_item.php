<?php

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
    include_once '../class/AuditorLog.php';

	$tabela	= trim($_REQUEST["tabela"]);
	extract(@$_POST);

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

    function str2num($str){ 
        if(strpos($str, '.') < strpos($str,',')){ 
            $str = str_replace('.','',$str); 
            $str = strtr($str,',','.');            
        }else{ 
            $str = str_replace(',','',$str);            
        } 

        return (float) $str; 
    } 

    //autocomplete
    if(!empty($_GET['action']) AND $_GET['action'] == 'autocompletePeca'){
        $q = $_GET['q'];
        $sql = "
            SELECT
                peca,
                referencia,
                descricao
            FROM tbl_peca
            WHERE 
                fabrica = $login_fabrica
                AND ativo IS TRUE
                AND (referencia LIKE '{$q}%' OR descricao ILIKE '%{$q}%')
            ORDER BY descricao ASC
            LIMIT 10;";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)){
            for($i = 0; $i < pg_num_rows($res); $i++){
                extract(pg_fetch_array($res));

                echo "$peca|$referencia|$descricao\n";
            }
        }
        exit;
    }

    if(!empty($action)){

        if(!empty($tabela_item) AND $action == 'apagar'){
            $auditorLog = new AuditorLog;
            $auditorLog->retornaDadosSelect("SELECT t.descricao, 
                                                    p.descricao, 
                                                    ti.preco 
                                            FROM tbl_tabela_item ti 
                                            JOIN tbl_tabela t using(tabela) 
                                            JOIN tbl_peca p using(peca) 
                                            WHERE ti.tabela_item = {$tabela_item}");

            $sql = "DELETE FROM tbl_tabela_item WHERE tabela_item = {$tabela_item}";
            if(pg_query($con, $sql)) {
                $auditorLog->retornaDadosSelect()->enviarLog('delete', 'tbl_tabela_item', $login_fabrica.'*'.$tabela);
                echo "ok";
            }

            exit;
        }

        if(!empty($tabela) AND $action == 'gravarDados'){
            
            $sql = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$peca_referencia';";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res) == 1)
                $x_peca = pg_fetch_result($res, 0, 'peca');
            else{
                $msg_erro[] = "No campo 'peça' você deve começar a digitar um valor e selecionar um item da lista";
            }

            if(empty($peca_preco)){
                $msg_erro[] = "Informe um preço para a peça '{$peca}'";
            }else{
                $x_preco =  str2num(trim(str_replace('R$','',$peca_preco))); 
            }

            if(count($msg_erro) == 0){
                $sql = "SELECT tabela_item FROM tbl_tabela_item WHERE peca = {$x_peca} AND tabela = {$tabela}";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){

                    $tabela_item = pg_fetch_result($res,0,"tabela_item");

                    $auditorLog = new AuditorLog;
                    $auditorLog->retornaDadosSelect("SELECT t.descricao, 
                                                            p.descricao, 
                                                            ti.preco 
                                                    FROM tbl_tabela_item ti 
                                                    JOIN tbl_tabela t using(tabela) 
                                                    JOIN tbl_peca p using(peca) 
                                                    WHERE ti.tabela_item = {$tabela_item}");

                    $sql = "UPDATE tbl_tabela_item SET preco = '$x_preco' WHERE tabela_item = {$tabela_item};";
                    $acao = 'update';
                }else{
                    $auditorLog = new AuditorLog('insert');
                    $sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ($tabela, $x_peca, '$x_preco') RETURNING tabela_item;";
                    $acao = 'insert';
                }

                $query = pg_query($con, $sql);

                if(pg_last_error()) {
                    $msg_erro[] = "Erro ao gravar peça.<erro>".pg_last_error($con)."</erro>";
                } else {
                    if ($acao == 'insert') {
                        $tabela_item = pg_fetch_result($query, 0, 'tabela_item');
                        $sqlLog  = "SELECT t.descricao, 
                                            p.descricao, 
                                            ti.preco 
                                    FROM tbl_tabela_item ti 
                                    JOIN tbl_tabela t using(tabela) 
                                    JOIN tbl_peca p using(peca) 
                                    WHERE ti.tabela_item = {$tabela_item}";
                        $auditorLog->retornaDadosSelect($sqlLog)->enviarLog('insert', 'tbl_tabela_item', $login_fabrica.'*'.$tabela);
                    } else {
                        $auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_tabela_item', $login_fabrica.'*'.$tabela);
                    }

                    $referencia = $peca_referencia;
                    unset($peca_referencia);
                    unset($peca);
                    unset($peca_preco);
                }
            }
        }
    }

    if(!empty($ajax)){

        if(!empty($tabela_item) AND !empty($valor)){
            $preco = number_format($valor,'2','.','');
            $xtabela = $_POST['tabela'];

            $auditorLog = new AuditorLog;
            $auditorLog->retornaDadosSelect("SELECT t.descricao, 
                                                    p.descricao, 
                                                    ti.preco 
                                            FROM tbl_tabela_item ti 
                                            JOIN tbl_tabela t using(tabela) 
                                            JOIN tbl_peca p using(peca) 
                                            WHERE ti.tabela_item = {$tabela_item}");

            $sql = "UPDATE tbl_tabela_item SET preco = '$preco' WHERE tabela_item = {$tabela_item}";
            if(pg_query($con, $sql)) {
                $auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_tabela_item', $login_fabrica.'*'.$xtabela);
                echo "ok";
            }
            else
                echo "<script type='text/javascript'>window.alert('Ocorreu um erro ao atualizar as informação da peça!');</script>";
        }
        exit;
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

            #gridRelatorio{
                margin: 20px 10px;
            }

            .frm{
                border: 1px solid #CCC
            }

            .mensagem{
                background-color: #D9E2EF;
                border: 1px solid #000;
                color: #000;  
                padding: 5px;
                width: 690px;
                margin: 5px auto;
                text-align: left;

            }

            .lp_pesquisando_por span{
                color: #F00;
                font-size: 80%;
                text-align: left;
                display: block;
            }

            .titulo_tabela{
                background-color:#596d9b;
                font: bold 12px "Arial";
                color:#FFFFFF;
                text-align:center;
            }

           
            .titulo_coluna th{
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
                margin: 0 10px;
                padding: 3px 0;
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

             table.tabela{
                margin-top: 20px;
             }

            table.tabela tr td{
                font-family: verdana;
                font-size: 11px;
                border-collapse: collapse;
                border:1px solid #596d9b;
                padding: 1px 2px;
            }

            table.tabela tr:hover{
                background: #CCC;
            }

            #box_gravacao{
                width: 98%;
                margin: 0 auto;
            }


            #box_gravacao #box_gravacao_toogle{
                text-align: right;
                padding-right: 10px;
                font-size: 12px;
                color: #0E2F64;
                cursor: pointer;
                font-weight: bold;
            }

        erro{
            display: none;
        }
		</style>
 		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css" />
      <link rel="stylesheet" type="text/css" href="../js/jquery.autocomplete.css" />
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
        <script src="../js/jquery.autocomplete.1.1.js" type="text/javascript"></script>
        <script src="js/jquery.price_format.1.5.js" type="text/javascript"></script>
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();

                $("#gridRelatorio input[type='button']").click(function(){
                    var tabela_item = $(this).attr('alt');

                    if(confirm("Deseja realmente apagar este registro!")){
                        $.ajax({
                            type    : "POST",  
                            url     : "<?php echo $PHP_SELF;?>",  
                            data    : "action=apagar&tabela_item=" + tabela_item,
                            success : function(retorno){
                                if(retorno == 'ok'){
                                    $("#apagar_"+tabela_item).parent().parent().fadeOut("slow");
                                }

                                $("#gridRelatorio").tablesorter();
                            }  
                        });
                    }else
                        return false;
                });

                $("#gridRelatorio input[type='text']").blur(function(){
                    var tabela_item = $(this).attr('alt');

                    if(tabela_item.length > 0){
                       atualizaTabelaItem(tabela_item);
                    }
                });
                
                formataCampo();
                pecaAutocomplete();
			}); 

            function formataCampo(){
                $("#gridRelatorio input[type='text'], #peca_preco").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            }

            function atualizaTabelaItem(tabela_item){
                var valor_oculto    = $("#hd_preco_"+tabela_item).val();
                var valor           = $("#preco_"+tabela_item).val();
                var xtabela         = $("input[name=tabela]").val();

                valor = valor.replace('.','');
                valor = valor.replace('R$','');
                valor = $.trim(valor.replace(',','.'));
                
                if(valor_oculto != valor){
                    $("#preco_"+tabela_item).attr("disabled", "disabled");
                    $("#preco_"+tabela_item).val('Atualizando!');

                    $.ajax({
                        type    : "POST",  
                        url     : "<?php echo $PHP_SELF;?>",  
                        data    : "ajax=ok&tabela_item=" + tabela_item + "&valor=" + valor + "&tabela=" + xtabela,
                        success : function(retorno){
                            if(retorno == 'ok'){
                                $("#preco_"+tabela_item).css('border','1px solid #0E660B');
                                $("#preco_"+tabela_item).parent().parent().css('background','#D9F4D7');

                                $("#hd_preco_"+tabela_item).val(valor);
                                $("#preco_"+tabela_item).val(valor);
                            }
                            $("#preco_"+tabela_item).removeAttr("disabled");

                            formataCampo();
                        }  
                    });
                }
            }

            function pecaAutocomplete(){
                $('#peca').focus(function(){
                    if (!$(this).attr("readonly")) {
                        $('#peca').autocomplete('<?php echo $PHP_SELF;?>?action=autocompletePeca', {
                            minChars: 3,
                            delay: 300,
                            width: 350,
                            matchContains: true,
                            formatItem: function(row) {return row[1] + ' - ' + row[2];},
                            formatResult: function(row) {return row[1] + ' - ' + row[2];}
                        });

                        $('#peca').result(function(event, data, formatted) {
                            $('#peca_referencia').val(data[1]);
                            $('#peca_preco').focus();
                        });
                    }
                });
            }
		</script>
	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
                    echo "<input type='hidden' name='tabela' value='$tabela' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Peça Referência</label>
								<input type='text' name='referencia' value='$referencia' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>Peça Descrição</label>
								<input type='text' name='descricao' value='$descricao' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

            if(!empty($tabela)){
                if(!empty($referencia) || !empty($descricao)){
                    if(!empty($referencia))
                        $sql = "
                                SELECT DISTINCT peca INTO TEMP tmp_peca_manutencao_$login_admin FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia LIKE '%{$referencia}%';
                                CREATE INDEX tmp_peca_manutencao_peca_$login_admin ON tmp_peca_manutencao_$login_admin(peca);";
                    if(!empty($descricao))
                        $sql = "
                                SELECT DISTINCT peca INTO TEMP tmp_peca_manutencao_$login_admin FROM tbl_peca WHERE fabrica = $login_fabrica AND descricao ILIKE '%{$descricao}%';
                                CREATE INDEX tmp_peca_manutencao_peca_$login_admin ON tmp_peca_manutencao_$login_admin(peca);";
                    //echo $sql;
                    $res = pg_query($con,$sql);

                    $sql = "SELECT peca FROM  tmp_peca_manutencao_$login_admin"; 
                    $res = pg_query($con,$sql);
                    if (pg_num_rows ($res) > 0){
                       $peca_sql = " AND tbl_tabela_item.peca IN (SELECT peca FROM  tmp_peca_manutencao_$login_admin)";
                    }
                }

                $sql = "SELECT 
                            tbl_tabela_item.tabela_item ,
                            tbl_peca.referencia         ,
                            tbl_peca.descricao          ,
                            tbl_tabela_item.preco
                        FROM tbl_tabela_item
                            JOIN tbl_peca ON tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                        WHERE 
                            tbl_tabela_item.tabela = {$tabela}
                            {$peca_sql}
                         ORDER BY tbl_peca.descricao DESC";
                //echo nl2br($sql);
				$res = pg_query($con,$sql);

				if (@pg_num_rows ($res) > 0) {
                    $sql = "SELECT sigla_tabela, descricao FROM tbl_tabela WHERE tabela = {$tabela}";
                    $res_tabela = pg_query($con,$sql);
                    extract(pg_fetch_array($res_tabela));

                    echo "<div class='lp_pesquisando_por'>
                            Tabela: {$sigla_tabela} - {$descricao}<br />
                            <span>* Para atualizar um registro altere o valor que será atualizado automaticamente</span>
                        </div>";?>
                    

                    <div id='box_gravacao'>
                        <?php
                            if(!empty($msg_erro)){
                                if(is_array($msg_erro))
                                    $msg_erro = implode("<br>",$msg_erro);

                                echo "<div class='msg_erro'>{$msg_erro}</div>";
                            }
                        ?>
                        <div class='content'>
                            <form method='post' action='<? echo $PHP_SELF; ?>' name='frm_tabela' >
                            <input type="hidden" name="action" value="gravarDados" />
                            <input type="hidden" name="tabela" value="<?php echo $tabela;?>" />

                            <table border='0' align="center" width="98%" class="formulario" cellspacing='1' cellpadding='4'>
                                <tr class="titulo_tabela">
                                    <td colspan="3">Cadastro Preço</td>
                                </tr>
                                <tr>
                                    <td width='350px' style='padding-left: 80px;'>
                                        Peça Referência / Descrição<br />
                                        <input class="frm" type="text" name="peca" id='peca' style='width: 330px' value="<?php echo $peca;?>" />
                                        <input type="hidden" name="peca_referencia" id='peca_referencia'  value="<?php echo $peca_referencia;?>" />
                                    </td>
                                    <td style='width: 140px'>
                                        Preço<br />
                                        <input class="frm" type="text" name="peca_preco" id='peca_preco'  style='width: 120px' value="<?php echo $peca_preco;?>" />
                                    </td>
                                    <td style='text-align: left; vertical-align: bottom'>
                                        <input type="submit" name="btn_acao" value='Gravar' />
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </div>
                    </div>

					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<th width="*">Peça</th>
								<th width="80px">Valor</th>
                                <th width="80px">Ação</th>
							</tr>
						</thead>
						<tbody>
                            <? 
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                                 extract(pg_fetch_array($res));
                                 $preco = number_format($preco,'2','.','');
                                
                                 $cor = $cor == '#F7F5F0' ? '#F1F4FA' : '#F7F5F0';
                                 echo "<tr bgcolor='{$cor}'>";
                                    echo "<td>{$referencia} - {$descricao}</td>";
                                    echo "
                                        <td  style='text-align: center'>
                                            <input type='text' name='preco_{$tabela_item}' id='preco_{$tabela_item}' size='12' value='{$preco}' class='frm'  alt='{$tabela_item}' />
                                            <input type='hidden' name='hd_preco_{$tabela_item}' id='hd_preco_{$tabela_item}' value='{$preco}' />
                                        </td>";
                                    echo "<td style='text-align: center'>
                                            <input type='button' name='apagar_{$tabela_item}' id='apagar_{$tabela_item}' value='Apagar' alt='{$tabela_item}' />
                                          </td>";
                                 echo "</tr>";
                            }?>
                        </tbody>

                <?php
                }else
                    echo "<div class='lp_msg_erro'>Nehuma peça encontrada!</div>";

            }else{
                echo "<div class='lp_msg_erro'>Nehuma tabela encontrada!</div>";
            } ?>
	</body>
</html>
