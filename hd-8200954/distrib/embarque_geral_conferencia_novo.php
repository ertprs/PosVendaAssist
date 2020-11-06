<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

#echo "Programa de jun��o est� processando...Aguarde!"; exit;
$btn_acao = trim($_POST['btn_acao']);
if (strlen($btn_acao) == 0) {
    $btn_acao = trim($_GET['btn_acao']);
}

$os_parcial    = trim($_GET['os_parcial']);
$excluir_troca = trim($_GET['excluir_troca']);
$aviso         = trim($_GET['aviso']);
$os            = trim($_GET['os']);
$pedido        = trim($_GET['pedido']);
$email         = trim($_GET['email']);
$tipo          = trim($_GET['tipo']);
$dado          = trim($_GET['dado']);
$posto         = trim($_GET['posto']);

$pedido_rec    = trim($_GET['pedido_recalcula']);

if (strlen($pedido_rec) > 0) {
    $sql = "SELECT fabrica FROM tbl_pedido WHERE pedido = $pedido_rec";
    $res = pg_query($con,$sql);
    $fabrica_rec = pg_fetch_result ($res,0,fabrica);

	$admin_pedido = NULL;
	$i_pedido_rec = (int) $pedido_rec;

    $sql_admin_pedido = "SELECT admin FROM tbl_pedido WHERE pedido = $i_pedido_rec AND admin IS NOT NULL";
	$res_admin_pedido = pg_query($con, $sql_admin_pedido);

	if (pg_num_rows($res_admin_pedido) > 0) {
		$admin_pedido = pg_fetch_result($res_admin_pedido, 0, 'admin');

		$sql_null_admin_pedido = "UPDATE tbl_pedido SET admin = NULL WHERE pedido = $i_pedido_rec";
		$res_null_admin_pedido = pg_query($con, $sql_null_admin_pedido);
	}

    $sql = "SELECT fn_pedido_finaliza($pedido_rec,$fabrica_rec);";
    $res = pg_query($con,$sql);

	if (!empty($admin_pedido)) {
		$sql_admin_admin_pedido = "UPDATE tbl_pedido SET admin = $admin_pedido WHERE pedido = $i_pedido_rec";
		$res_admin_admin_pedido = pg_query($con, $sql_admin_admin_pedido);
	}
}


#HD 20202
if (strlen($aviso) > 0 AND $aviso == '1') {

    if (strlen($os) > 0 and strlen($pedido) > 0) {

        echo '  <style type="text/css">
                    body {
                        font-family : verdana;
                        font-size:12px;
                    }
                    td {
                        font-size: 15pt;
                    }
                    font {
                        font-size: 15pt;
                    }
                    tr {
                        font-size: 15pt;
                    }
                </style>';

        $sql = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto,tbl_posto.nome
                FROM tbl_os
                JOIN tbl_posto USING(posto)
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                WHERE tbl_os.os = $os";
        $resX = pg_query ($con,$sql);
        if (pg_numrows ($resX) >0) {
            $sua_os         = pg_fetch_result ($resX,$x,sua_os);
            $codigo_posto   = pg_fetch_result ($resX,$x,codigo_posto);
            $nome           = pg_fetch_result ($resX,$x,nome);

            if ($email=='1'){
                $sql = "SELECT tbl_admin.email
                        FROM tbl_admin
                        JOIN tbl_os USING(fabrica)
                        WHERE tbl_os.os = $os
                        AND tbl_admin.responsavel_troca IS TRUE";
                $resX = pg_query ($con,$sql);
                $lista_email = array();
                array_push($lista_email,'ronaldo@telecontrol.com.br');
                array_push($lista_email,'fabio@telecontrol.com.br');
                for ($i=0; $i<pg_numrows ($resX); $i++){
                    array_push($lista_email,pg_fetch_result ($resX,$i,email));
                }
                $remetente    = "Telecontrol <ronaldo@telecontrol.com.br>";
                $destinatario = implode(",",$lista_email);
                $assunto      = "OS Sujeito a Procon";
                $mensagem =  "Telecontrol - Sistema Inteligente<br><br>
                            Prezado(a)<br><br>
                            Foi detectado que o pedido <b>$pedido</b> do posto <b>$codigo_posto - $nome</b>, da Ordem de Servi�o n� <b>$sua_os</b>, cont�m o pedido de uma pe�a em garantia para reparo a mais de 20 dias e pode causar um processo PROCON.<br><br><br> Sugerimos antecipar a troca do produto.<br><br>
                            <br><br>
                            N�o responder este email, � gerado automaticamente pelo sistema!";
                $headers = "Return-Path: <ronaldo@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                if(count($destinatario)>0) {
                    if (mail($destinatario,$assunto,$mensagem,$headers)){
                        echo "<p>EMAIL ENVIADO COM SUCESSO!</p>";
                        echo "<p>Foi enviado para os seguintes destinat�rios: ".$destinatario."</p>";
                        echo "<br>";
                        echo "<p><a href='javascript:window.close()'>Fechar Janela</a>";
                    }else{
                        echo "<p>Ocorreu um erro no envio do email. Tente novamente.</p>";
                        echo "<p><a href='javascript:window.close()'>Fechar Janela</a>";
                    }
                }
            }else{
                echo "Foi detectado que o pedido <b>$pedido</b> do posto <b>$codigo_posto - $nome</b>, da Ordem de Servi�o n� <b>$sua_os</b>, cont�m o pedido de uma pe�a em garantia para reparo a mais de 20 dias e pode causar um processo PROCON.";
                echo "<br>";
                echo "<br>";
                echo "<a href='$PHP_SELF?aviso=1&os=$os&pedido=$pedido&email=1'>ENVIAR EMAIL PARA AVISAR O FABRICANTE</a>";
            }
        }
    }
    exit;
}

if ($os_parcial == "1"){
    $sql = "SELECT fn_os_parcial ($login_posto)";
    //echo nl2br($sql);
    $res = pg_query ($con,$sql);
}
if ($excluir_troca=="sim"){
    $Xos_item = trim($_GET['os_item']);
    $Xpedido  = trim($_GET['pedido']);
    $Xpeca    = trim($_GET['peca']);
    if (strlen($Xos_item)>0 AND strlen($Xpedido)>0 AND strlen($Xpeca)>0){
        $sql = "SELECT fabrica FROM tbl_peca WHERE peca = $Xpeca";
        $res = pg_query($con,$sql);
        $fabrica_cancela = pg_fetch_result($res,0,0);
        $sql = "SELECT fn_pedido_cancela_garantia($login_posto,$fabrica_cancela, $Xpedido , $Xpeca, $Xos_item,'Cancelamento de Embarque');";
        $res = pg_query ($con,$sql);
        #echo nl2br($sql);
        echo "Opera��o concluida com sucesso.";
    }else{
        echo "Opera��o cancelada.";
    }
    exit;
}

//Juntar embarque = system("/www/cgi-bin/distrib/juntar-embarques.pl",$ret);
//Desembarcar -parciais - fn_os_parcial_embarque

if (strlen($btn_acao)>0){
    $qtde_embarques = trim($_POST['qtde_embarques']);

    for ($i=0; $i<$qtde_embarques; $i++){
        $ativo    = trim($_POST['ativo_'.$i]);
        $embarque = trim($_POST['embarque_'.$i]);
        if (strlen($ativo)>0 AND strlen($embarque)>0){

            if ($btn_acao == "embarcar" AND strlen($ativo)>0){
                $sql = "SELECT fn_preparar_cargar($embarque)";
                #echo nl2br($sql);
                //$res = pg_query ($con,$sql);
            }

            if ($btn_acao == "liberar_etiqueta"){
                /*
                $sql = "SELECT DISTINCT tbl_embarque.embarque
                        FROM tbl_embarque_item
                        JOIN tbl_embarque USING (embarque)
                        WHERE tbl_embarque.distribuidor = $login_posto
                        AND tbl_embarque.faturar       IS NULL
                        AND tbl_embarque_item.liberado IS NULL
                        AND tbl_embarque_item.impresso IS NULL
                        AND tbl_embarque.embarque       = $embarque
                        AND tbl_embarque.posto NOT IN (
                            SELECT posto
                            FROM  tbl_embarque
                            WHERE faturar >= CURRENT_DATE - INTERVAL '10 days'
                            AND   nf_conferencia IS NOT TRUE
                            AND   distribuidor = $login_posto
                        )";
                //echo nl2br($sql);
                $res = pg_query ($con,$sql);

                if (pg_numrows($res) > 0) {
                    $libera_embarque = pg_fetch_result ($res,0,0);
                    //echo "<hr>".$libera_embarque."<hr>";*/
                    $sql = "SELECT fn_preparar_cargar($embarque)";
                    //echo nl2br($sql);
                    $res = pg_query ($con,$sql);
                    $sql = "SELECT fn_etiqueta_libera ($embarque)";
                    //echo nl2br($sql);
                    $res = pg_query ($con,$sql);
                //}
            }
        }
    }
    //exit;
    $linha          = "";
    $embarque       = "";
    $qtde_embarques = "";

    if ($btn_acao == "liberar_etiqueta"){
        header("Location: embarque_faturamento.php?quais_embarque=liberados");
        exit;
    }
}

$msg_erro = "";
?>

<html>
<head>
<title>Confer�ncia Geral do Embarque</title>

<style type="text/css">
    .body {
    font-family : verdana;
    }

    #contatos {
        background-color: #007bff;
        color: white;
    }

    #contatos:hover {
        background-color: rgb(0,123,255, 0.9);
    }

    #alter-error-correio{
        color: #b94a48;
        background-color: #f2dede;
        border-color: #eed3d7;
        padding: 8px 35px 8px 14px;
        margin-bottom: 20px;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
        border: 1px solid #fbeed5;
        border-radius: 4px;
        font-weight: bold;
        font-size: 13px;
        display: block;
    }

    #alter-error-correio-h4{
        margin: 0;
        font-size: 17.5px;
        font-family: inherit;
        font-weight: bold;
        line-height: 20px;
        color: inherit;
        text-rendering: optimizelegibility;
        text-align: center;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
    }

    table > tbody > tr > td{
        word-break: break-all;
        word-wrap: break-word;
    }
</style>

<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src='../admin/plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='../admin/plugins/shadowbox_lupa/shadowbox.css' />
<script src="../js/jquery.numeric.js"></script>
<script>

    function buscaEnvio(button,fabrica_contrato, embarque = null){
        //button = button.replace("cota_frete","",button);
        button = button.replace(/[^\d]+/g,'');
        $("#tabela_correios_cotacao_"+button+" tfoot > tr[id=servico_correios_"+button+"]").remove()

        var caixa = $("#caixa"+button).val();

        if(caixa == "tamanho_personalizado"){

        	var comprimento = $("input[name='comp_perso_"+button+"']").val();
			var largura     = $("input[name='larg_perso_"+button+"']").val();
			var altura      = $("input[name='alt_perso_"+button+"']").val();

			var tipo_tamanho_personalizado = $("select[name='tipo_tamanho_personalizado_"+button+"']").val();

			if(comprimento == ""){ comprimento = 0; }
			if(largura == ""){ largura = 0; }
			if(altura == ""){ altura = 0; }

			if(comprimento == 0){

				alert("Por favor insira o Comprimento!");
				$("input[name='comp_perso_"+button+"']").focus();
				return;

			}else if(largura == 0){

				alert("Por favor insira a Largura!");
				$("input[name='larg_perso_"+button+"']").focus();
				return;

			}else if(altura == 0 && tipo_tamanho_personalizado != "2" && tipo_tamanho_personalizado != "3"){

				alert("Por favor insira a Altura!");
				$("input[name='alt_perso_"+button+"']").focus();
				return;

			}else{

				comprimento = parseInt(comprimento);
				largura     = parseInt(largura);
				altura      = parseInt(altura);

				/* Pacotes e caixas */
				if(tipo_tamanho_personalizado == "1"){

					if(comprimento < 16 || comprimento > 105){

						alert("O Comprimento deve estar entre 16 cm e 105 cm.")
						$("input[name='comp_perso_"+button+"']").focus();
						return;

					}

					if(largura < 11 || largura > 105){

						alert("A Largura deve estar entre 11 cm e 105 cm.")
						$("input[name='larg_perso_"+button+"']").focus();
						return;

					}

					if(altura < 2 || altura > 105){

						alert("A Altura deve estar entre 2 cm � 105 cm.")
						$("input[name='alt_perso_"+button+"']").focus();
						return;

					}

					var total_tamanho_personalizado = comprimento + largura + altura;

					if(total_tamanho_personalizado < 29 || total_tamanho_personalizado > 200){

						alert("A soma das medidas deve estar entre 29 cm e 200 cm.")
						return;

					}

				}

				/* Envelopes */
				if(tipo_tamanho_personalizado == "2"){

					$("input[name='alt_perso_"+button+"']").val(0);			
					$("input[name='alt_perso_"+button+"']").attr("disabled", true);			

					if(comprimento < 16 || comprimento > 60){

						alert("O Comprimento deve estar entre 16 cm e 60 cm.")
						$("input[name='comp_perso_"+button+"']").focus();
						return;

					}

					if(largura < 11 || largura > 60){

						alert("A Largura deve estar entre 11 cm e 60 cm.")
						$("input[name='larg_perso_"+button+"']").focus();
						return;

					}

					altura = 0;

				}

				/* Rolos e Cilindros */
				if(tipo_tamanho_personalizado == "3"){

					$("input[name='alt_perso_"+button+"']").val(0);			
					$("input[name='alt_perso_"+button+"']").attr("disabled", true);			

					if(comprimento < 18 || comprimento > 105){

						alert("O Comprimento deve estar entre 18 cm e 105 cm.")
						$("input[name='comp_perso_"+button+"']").focus();
						return;

					}

					if(largura < 5 || largura > 91){

						alert("A Largura deve estar entre 5 cm e 91 cm.")
						$("input[name='larg_perso_"+button+"']").focus();
						return;

					}

					altura = 0;

					var total_tamanho_personalizado = comprimento + (2 * largura);

					if(total_tamanho_personalizado < 28 || total_tamanho_personalizado > 200){

						alert("A soma das medidas deve estar entre 28 cm e 200 cm.")
						return;

					}

				}

				caixa = comprimento+","+largura+","+altura;

			}

        }

        var dataAjax = {
			peso: 		$("#peso_real"+button).val(),
			volume: 	$("#volume"+button).val(),
			caixa: 		caixa,
			cep: 		$("#cep"+button).val(),
			valor_nota: $("#valor_nota"+button).val(),
			funcao: 	"calcPrecoPrazo",
			fabrica: 	fabrica_contrato,
            embarque:   embarque
        };

        $.ajax({
            url: 'funcao_correio.php',
            type: 'get',
            data: dataAjax,
            beforeSend:function(){
                $("input[name=cota_frete"+button+"]").hide();
                $("input[name=cota_frete"+button+"]").prev("img").show();
            },
            success: function(data){
                data         = JSON.parse(data);
                var servicos = "";
				var obs_fim = "";
                var cont     = 0;
                // var td_nome  = "#servico"+button;

                $("#tabela_correios_cotacao_"+button+" > tfoot tr[class=erro_frete]").remove();

                if (data.erro_correios != undefined || data.erro_offline != undefined) {

                    var msg_webservice_correios = "";

                    if (data.erro_correios != "") {
                        msg_webservice_correios += "Aten��o: Realizaremos o c�lculo de frete offline, pois n�o foi poss�vel realizar o c�lculo pelo webservice dos correios. Os erros retornados foram: <br /><br />"+data.erro_correios;
                    }

                    if (data.erro_offline != "" && data.erro_offline != undefined) {
                        msg_webservice_correios += "<br />"+data.erro_offline;
                    }

                    if (msg_webservice_correios != "") {
                        let tr = $("<tr></tr>", {
                            class: "erro_frete"
                        });

                        tr.append($("<td></td>", {
                            colspan: 7,
                            css: {
                                color: '#d90000',
                                'font-weight': 'bolder',
                                'padding-top': 10,
                                'padding-bottom': 10,
                                'font-size': 12,
                                'background-color': '#ffc9c9'
                            }
                        }).html(msg_webservice_correios));

                        $("#tabela_correios_cotacao_"+button+" > tfoot").append(tr);
                    }

                    data = data.dados;

                }

                if (data.erro_offline != undefined && data.dados.length > 0) {
                    data = data.dados;
                }

                $.each(data,function(key, value){

                    if(value.resultado == "false"){
                        // $.each(value[0],function(key, mensagem){
                        // $("#mensagem"+button).html('<div id="alter-error-correio"><h4 id="alter-error-correio-h4">'+mensagem.faultstring+'</h4> </div>');
                        $("#mensagem"+button).html('<div id="alter-error-correio"><h4 id="alter-error-correio-h4">'+value.mensagem+'</h4> </div>');
                        // });
                    }else{
                        $("#mensagem"+button).html("");
                        var readOnly = "";

                        // if(cont == 3){
                        //  $(td_nome).html(servicos);
                        //  td_nome = "#servico2"+button;
                        //  servicos = "";
                        // }else if(cont == 6){
                        //  $(td_nome).html(servicos);
                        //  td_nome = "#servico3"+button;
                        //  servicos = "";
                        // }

                        if(value.valor == 0 || value.valor == 0.0 || value.valor == 0.00){
                            value.valor = "N�o dispon�vel esse servi�o na localidade.";
                            readOnly = "disabled = true";
                        }else{
                            value.valor = "R$ "+value.valor;
                        }


						if(value.obs_fim) {
							obs_fim = "<br><strong>Obs:</strong> "+ value.obs_fim;
						}else{
							obs_fim = "";
						}

                        var funcaoEtiqueta = (value.transportadora != undefined && value.transportadora == true) ? "buscaEtiquetaTransportadora" : "buscaEtiqueta";

                        servicos += '<td colspan="2"><img src="../imagens/loading_img.gif" style="height: 27px; margin-top: 2px;" hidden class="img_loading" /><input type="radio" name="radioServico'+button+'" id="'+value.codigo+'" '+readOnly+' onclick="'+funcaoEtiqueta+'(\''+value.codigo+'\','+button+','+value.valor_real+','+value.prazo_entrega+','+fabrica_contrato+')" value='+value.codigo+'>'+value.descricao+'<br /><b>'+value.valor+'</b><br />Prazo: <b>'+value.prazo_entrega+'</b> dias</td>';
                        
                        cont++;
                        
                        if(cont == 3){
                            $("#tabela_correios_cotacao_"+button+" > tfoot").append("<tr id='servico_correios_"+button+"'>"+servicos+"</tr>");
                            servicos = "";
                            cont     = 0;
                        }
                    }
                });

                // servicos+="</tr>";
                // $(td_nome).html(servicos);
                $("#tabela_correios_cotacao_"+button+" > tfoot").append("<tr id='servico_correios_"+button+"'>"+servicos+"</tr>");

               $("input[name=cota_frete"+button+"]").show();
               $("input[name=cota_frete"+button+"]").prev("img").hide();
            }
        });

    }

    function buscaEtiquetaTransportadora(codigo, button, valor, prazo, fabrica_contrato){
        var confirma = true;
        var caixa = $("#caixa"+button).val();

        if($("#etiqueta_servico"+button).val() != ""){
            confirma = confirm("Deseja realmente alterar o servi�o j� cadastrado neste embarque?");
        }
        
        if(caixa == "tamanho_personalizado"){
            var comprimento = $("input[name='comp_perso_"+button+"']").val();
            var largura     = $("input[name='larg_perso_"+button+"']").val();
            var altura      = $("input[name='alt_perso_"+button+"']").val();
            caixa = comprimento+","+largura+","+altura;
        }

        if(confirma){
            var dataAjax = {
                codigo: codigo,
                quantidade: 1,
                valor: valor,
                prazo: prazo,
                embarque: $("#embarque"+button).val(),
                peso: $("#peso_real"+button).val(),
                caixa: caixa,
                fabrica: fabrica_contrato
            };

            $.ajax({
                url: 'funcao_correio.php?funcao=buscaEtiquetaTransportadoraBanco',
                type: 'get',
                data: dataAjax,
                beforeSend:function(){
                    $("input[name=radioServico"+button+"]").hide();
                    $("input[name=radioServico"+button+"]").prev("img").show();
                },
                success: function(data){
                    data          = JSON.parse(data);
                    var etiquetas = "";

                    $.each(data,function(key, value){
                        if(value.resultado == "true"){
                            $("#mensagem"+button).html("");

                            $("#frete_transportadora"+button).val(value.frete_transportadora);
                            etiquetas += "<td colspan='4'><b>Servi�o selecionado:</b>Transp. Rodovi�rio</td><td colspan='2'><b>Etiqueta: </b><input type='text' readOnly name='estiqueta"+button+"' value='"+value.etiqueta+"'></td>";
                            
                            $("#etiqueta"+button).html(etiquetas);

                        }else{
                            $("#mensagem"+button).html('<div id="alter-error-correio"><h4 id="alter-error-correio-h4">'+value.mensagem+'</h4> </div>');
                            $("#"+codigo)[0].checked = false;
                        }
                    });
                    $("input[name=radioServico"+button+"]").show();
                    $("input[name=radioServico"+button+"]").prev("img").hide();
                },
                error: function() {
                    $("input[name=radioServico"+button+"]").show();
                    $("input[name=radioServico"+button+"]").prev("img").hide();
                }
            });
        }else{
            $("#"+codigo)[0].checked = false;
        }
    }

    function buscaEtiqueta(codigo, button, valor, prazo, fabrica_contrato){
        var confirma = true;
        var caixa = $("#caixa"+button).val();

        if($("#etiqueta_servico"+button).val() != ""){
            confirma = confirm("Deseja realmente alterar o servi�o j� cadastrado neste embarque?");
        }
        
        if(caixa == "tamanho_personalizado"){
            var comprimento = $("input[name='comp_perso_"+button+"']").val();
            var largura     = $("input[name='larg_perso_"+button+"']").val();
            var altura      = $("input[name='alt_perso_"+button+"']").val();
            caixa = comprimento+","+largura+","+altura;
        }

        if(confirma){
            var dataAjax = {
                codigo: codigo,
                quantidade: 1,
                valor: valor,
                prazo: prazo,
                embarque: $("#embarque"+button).val(),
                etiqueta_servico: $("#etiqueta_servico"+button).val(),
                peso: $("#peso_real"+button).val(),
				caixa: caixa,
				fabrica: fabrica_contrato
            };

            $.ajax({
                url: 'funcao_correio.php?funcao=buscaEtiquetaBanco',
                type: 'get',
                data: dataAjax,
                beforeSend:function(){
                    $("input[name=radioServico"+button+"]").hide();
                    $("input[name=radioServico"+button+"]").prev("img").show();
                },
                success: function(data){
                    data          = JSON.parse(data);
                    var etiquetas = "";

                    $.each(data,function(key, value){
                        if(value.resultado == "true"){
                            $("#mensagem"+button).html("");

                            $("#etiqueta_servico"+button).val(value.etiqueta_servico);
                            etiquetas += "<td colspan='2'><b>Etiqueta: </b><input type='text' readOnly name='estiqueta"+button+"' value='"+value.etiqueta+"'></td>";
                            
                            $("#etiqueta"+button).html(etiquetas);

                        }else{
                            $("#mensagem"+button).html('<div id="alter-error-correio"><h4 id="alter-error-correio-h4">'+value.mensagem+'</h4> </div>');
                            $("#"+codigo)[0].checked = false;
                        }
                    });
                    $("input[name=radioServico"+button+"]").show();
                    $("input[name=radioServico"+button+"]").prev("img").hide();
                },
                error: function() {
                    $("input[name=radioServico"+button+"]").show();
                    $("input[name=radioServico"+button+"]").prev("img").hide();
                }
            });
        }else{
            $("#"+codigo)[0].checked = false;
        }
    }

    function abrirAviso(os,pedido){
        window.open('<?="$PHP_SELF?aviso=1&os="?>'+os+'&pedido='+pedido,"janela","toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no,width=200, height=200, top=18, left=0");
    }

    function excluirEmbarque(embarque){
        if(confirm('Deseja realmente excluir este embarque?')){
            window.location='<? echo $PHP_SELF ?>?excluir_embarque='+embarque;
        }
    }
    function excluirItem(url,multiplos = 0){
        if (multiplos == 0) {
            if(confirm('Deseja realmente excluir esta pe�a deste embarque?')){
                window.location=url;
            }
        } else {
            if(confirm('Ao remover todas as pe�as do embarque, o mesmo ser� exclu�do. Deseja continuar?')){
                window.location=url;
            }
        }
    }

    function desembarcarParcial(url){
        if(confirm('Deseja desembarcar as OS�s parciais?')){
            window.location=url;
        }
    }

    function alteraDado(tipo,dado,posto){
        window.open('atualiza_posto.php?tipo=' + tipo+'&dado=' + dado +'&posto=' +posto,"janela","toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no,width=300, height=300, top=18, left=0");
    }

    function tamanho_personalizado(opt, valor){

    	if(valor == "tamanho_personalizado"){

    		$(".box_tamanho_personalizado_"+opt).show();
    		// $(".box_tamanho_comum_"+opt).hide();

    	}else{

    		$(".box_tamanho_personalizado_"+opt).hide();
    		// $(".box_tamanho_comum_"+opt).show();

    	}

    }

    function verifica_tipo_personalizado(tipo, opt){

        $(".nome_larg_"+opt).text("L");
        $(".nome_larg_"+opt).css({"padding-left":"1px"});

    	if(tipo == "2" || tipo == "3"){

    		$("input[name='alt_perso_"+opt+"']").val(0);			
			$("input[name='alt_perso_"+opt+"']").attr("disabled", true);

            if(tipo == "3"){

                $(".nome_larg_"+opt).text("D");
                $(".nome_larg_"+opt).css({"padding-left":"0px"});

            }

    	}else{

    		$("input[name='alt_perso_"+opt+"']").val("");			
			$("input[name='alt_perso_"+opt+"']").attr("disabled", false);

    	}

    }

    function verContatos(posto,fabrica) {
        Shadowbox.init();
        Shadowbox.open({
            content:"exibi_contatos_posto.php?posto="+posto+"&fabrica="+fabrica,
            player: "iframe",
            title:  "Contatos do Posto",
            width:  500,
            height: 200
        });
        //Shadowbox.close();
    }

    $(function(){
        Shadowbox.init();
    	$(".numeric").numeric();

    });

</script>

</head>

<body>

<style>
    .nivel1{
        background-color:#DFDFDF;
        border:1px solid #151515;
    }
    .nivel2{
        background-color:#DF0D12;
        color:#FFFFFF;
        border:1px solid #151515;
    }
    .selecionado{
        background-color:#33D237;
        border:1px solid #151515;
    }
</style>
<div class=noprint>
<? include 'menu.php' ?>
</div>

<center><h1>Confer�ncia Geral do Embarque</h1></center>


<?

if (strlen($btn_acao)==0){
    $etapa=1;
}

if ($btn_acao == "embarcar"){
    $etapa = 2;
}
if ($btn_acao == "liberar_etiqueta"){
    $etapa = 3;
}

//include "etapas.php";

if (strlen($btn_acao)==0){
    //echo "<p><a href='$PHP_SELF?os_parcial=1'>Reprocessar OS's parcial</a></p>";
}
if (isset($_GET['tipo_embarque'])) {
    $quais_embarques = $_GET['tipo_embarque'];
} else {
$quais_embarques = $_POST['quais_embarques'];
    if (strlen ($quais_embarques) == 0) $quais_embarques = "todos";
}
?>

<center>
    <form method='post' name='frm_conferencia' action='<?= $PHP_SELF ?>'>
        <input type='radio' name='quais_embarques' id='quais_embarques' <? if ($quais_embarques == "todos") echo " checked " ?> value='todos' >N�o liberados
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type='radio' name='quais_embarques' id='quais_embarques2' <? if ($quais_embarques == "liberados") echo " checked " ?> value='liberados' >Apenas os liberados
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type='submit' name='btn_pesquisar' value='Listar'>
    </form>
</center>

<p>
<?
$excluir_embarque      = trim($_GET['excluir_embarque']);
$numero_embarque       = trim($_GET['numero_embarque']);
$excluir_embarque_peca = trim($_GET['excluir_embarque_peca']);
$desembarcar_parcial   = trim($_GET['desembarcar_parcial']);
$exclui_embarque_item  = trim($_GET['exclui_embarque_item']);

if (isset($_GET['desembarcar_todos'])) {
    $excluir_embarque_array = explode(",",$excluir_embarque);
    $numero_embarque_array = explode(",",$numero_embarque);
    $exclui_embarque_item_array = explode(",",$exclui_embarque_item);
    $excluir_embarque_peca_array = explode(",",$excluir_embarque_peca);
}

$msg = "";

if (strlen($numero_embarque)>0 AND $desembarcar_parcial == "1"){

    $msg .= "Desembarque das OS's parciais do embarque $numero_embarque ... ";

    $res = @pg_query($con,"BEGIN TRANSACTION");

    $res  = @pg_query($con,"SELECT MAX(embarque) FROM tbl_embarque");
    $t_embarque_max = pg_fetch_result ($res,0,0);

/*
    $sql = "
        SELECT distinct tbl_embarque.posto, tbl_embarque_item.embarque, osx.os_item, tbl_embarque_item.pedido_item, tbl_embarque_item.peca, tbl_embarque_item.qtde
        INTO TEMPORARY  TABLE x_os_parcial
        FROM (
            SELECT DISTINCT oss.os_item
            FROM (
                SELECT tbl_os.os, tbl_os_item.os_item
                FROM tbl_os
                JOIN tbl_os_produto    USING (os)
                JOIN tbl_os_item       USING (os_produto)
                JOIN tbl_embarque_item USING (os_item)
                JOIN tbl_embarque      USING (embarque)
                WHERE tbl_embarque.distribuidor   = $login_posto
                AND   tbl_embarque.embarque       = $numero_embarque
                AND   tbl_embarque.faturar       IS NULL
                AND   tbl_embarque_item.impresso IS NULL
            ) oss
            JOIN tbl_os                 ON tbl_os.os                     = oss.os
            JOIN tbl_os_produto         ON oss.os                        = tbl_os_produto.os
            JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
            JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
            LEFT JOIN tbl_embarque_item ON tbl_os_item.os_item           = tbl_embarque_item.os_item
            LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.os = tbl_os.os AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
            WHERE (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto OR tbl_servico_realizado.ressarcimento)
            AND tbl_embarque_item.os_item   IS NULL
            AND tbl_pedido_cancelado.pedido IS NULL
        ) osx
        JOIN tbl_os_item       ON osx.os_item           = tbl_os_item.os_item
        JOIN tbl_embarque_item ON osx.os_item           = tbl_embarque_item.os_item
        JOIN tbl_embarque      ON tbl_embarque.embarque = tbl_embarque_item.embarque
        ;

        DELETE FROM tbl_embarque_item USING x_os_parcial WHERE tbl_embarque_item.os_item = x_os_parcial.os_item ;


        DELETE FROM tbl_embarque WHERE faturar IS NULL AND embarque IN (SELECT tbl_embarque.embarque FROM tbl_embarque LEFT JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque.faturar IS NULL AND tbl_embarque_item.embarque IS NULL) ;

        INSERT INTO tbl_embarque (posto, distribuidor) (SELECT DISTINCT x_os_parcial.posto,  $login_posto FROM x_os_parcial) ;

        INSERT INTO tbl_embarque_item (embarque, peca, qtde, os_item, pedido_item)
            (SELECT tbl_embarque.embarque, x_os_parcial.peca, x_os_parcial.qtde, x_os_parcial.os_item, x_os_parcial.pedido_item
            FROM tbl_embarque
            JOIN x_os_parcial ON tbl_embarque.posto = x_os_parcial.posto AND tbl_embarque.embarque > $t_embarque_max ) ;
    ";
*/

    $sql = "SELECT fn_os_parcial_embarque($login_posto, $numero_embarque);";
    $res = pg_query ($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if (strlen ($msg_erro) == 0) {
        $msg .=  "Opera��o realizada com sucesso.";
        $res = @pg_query ($con,"COMMIT TRANSACTION");
        //$res = pg_query ($con,"ROLLBACK TRANSACTION");
    }else{
        $msg .=  "Opera��o n�o realizada. Erro: $msg_erro";
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}


if (strlen($numero_embarque)>0 AND strlen($excluir_embarque_peca)>0){

    if (isset($_GET["desembarcar_todos"])) {
        for ($n = 0;$n < count($numero_embarque_array);$n++) {

            $res = @pg_query($con,"BEGIN TRANSACTION");
            $arquivo  = fopen ("/tmp/telecontrol/log_delete_embarque_item.txt", "a+");
            $peca = $excluir_embarque_peca_array[$n];


            if (strlen($os)>0) {
                $sql_D = "SELECT embarque_item 
                            FROM tbl_embarque_item 
                            WHERE os_item IN ( SELECT os_item 
                                                    FROM tbl_os_item 
                                                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
                                                    WHERE os = $os)";
                $res_D = pg_query($con,$sql_D);

                if (pg_num_rows($res_D)>0) {
                    for ($x = 0 ; $x < pg_numrows ($res_D); $x++) {
                        $embarque_item = pg_fetch_result ($res_D,$x,embarque_item);

                        #$sql = "SELECT fn_cancelar_embarque_item($embarque_item);";
                        $sql = "SELECT fn_delete_embarque_item($embarque_item);";
                        fwrite($arquivo, "\n\n Excluir item $embarque_item (".date("d/m/Y H:i:s").") \n [ $sql ] pelo $login_unico\n ");
                        $res = pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                }   
            }else{

                #Envia email para o PA quando � feito o cancelamento de pe�as faturadas
                $sqlX = "SELECT DISTINCT tbl_posto.nome,
                                tbl_posto.email,
                                tbl_peca.referencia,
                                tbl_peca.descricao,
                                tbl_embarque_item.pedido_item,
                                tbl_pedido.pedido
                        FROM   tbl_embarque
                        JOIN   tbl_embarque_item USING(embarque)
                        JOIN   tbl_peca          USING(peca)
                        JOIN   tbl_posto       ON tbl_posto.posto             = tbl_embarque.posto
                        JOIN   tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
                        JOIN   tbl_pedido      ON tbl_pedido.pedido           = tbl_pedido_item.pedido
                        JOIN   tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                        WHERE  tbl_embarque.embarque  = ".$numero_embarque_array[$n]."
                        AND    tbl_embarque_item.peca = ".$excluir_embarque_peca_array[$n]."
                        AND    (tbl_tipo_pedido.descricao ilike '%faturad%' OR tbl_tipo_pedido.descricao ilike '%venda%')";
                $resX = pg_query ($con,$sqlX);
                if (pg_numrows ($resX) >0) {
                    $nome      = pg_fetch_result ($resX,$x,nome);
                    $email     = pg_fetch_result ($resX,$x,email);
                    $referencia = pg_fetch_result ($resX,$x,referencia);
                    $descricao = pg_fetch_result ($resX,$x,descricao);
                    $pedido    = pg_fetch_result ($resX,$x,pedido);
                    if (strlen($email)>0){
                        $remetente    = "Telecontrol <ronaldo@telecontrol.com.br>";
                        $destinatario = $email;
                        $assunto      = "Cancelamento de Pedido de Pe�a do Distribuidor";
                        $mensagem =  "At. Respons�vel,<br><br>A pe�a $referencia - $descricao do pedido de n�mero $pedido foi cancelado.
                        <br>
                        <br> Caso tenha alguma d�vida, entre em contato com o distribuidor Telecontrol
                        <br><br>Telecontrol Networking";
                        $headers="Return-Path: <ronaldo@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                        if(strlen($mensagem)>0) {
                            //Comentei a pedido do Paulo Lin. �bano
                            //mail($destinatario,$assunto,$mensagem,$headers);
                        }
                    }
                }

                if(!empty($exclui_embarque_item_array[$n])) {
                    $cond = " and embarque_item = ".$exclui_embarque_item_array[$n]." ";
                }

                $sqlX = "SELECT embarque_item
                        FROM   tbl_embarque_item
                        WHERE  embarque = ".$numero_embarque_array[$n]."
                        $cond
                        AND    peca     = $peca";
                $resX = pg_query ($con,$sqlX);
                for ($x = 0 ; $x < pg_numrows ($resX); $x++) {
                    $embarque_item = pg_fetch_result ($resX,$x,embarque_item);

                    #$sql = "SELECT fn_cancelar_embarque_item($embarque_item);";
                    $sql = "SELECT fn_delete_embarque_item($embarque_item);";
                    fwrite($arquivo, "\n\n Excluir item $embarque_item (".date("d/m/Y H:i:s").") \n [ $sql ] pelo $login_unico\n");
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }   

            if (strlen ($msg_erro) == 0) {
                $msg =  "Opera��o realizada com sucesso.";
                fwrite($arquivo, "\n COMMIT TRANSACTION \n");
                $res = @pg_query ($con,"COMMIT TRANSACTION");
            }else{
                $msg .=  "Opera��o n�o realizada. Erro: $msg_erro";
                fwrite($arquivo, "\n ROLLBACK \n");
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
            }
            fclose ($arquivo);
            echo "<br><br>";
        }
    } else {
        $res = @pg_query($con,"BEGIN TRANSACTION");
        $arquivo  = fopen ("/tmp/telecontrol/log_delete_embarque_item.txt", "a+");
        $peca = $excluir_embarque_peca;

        if (strlen($os)>0) {
            $sql_D = "SELECT embarque_item 
                        FROM tbl_embarque_item 
                        WHERE os_item IN ( SELECT os_item 
                                                FROM tbl_os_item 
                                                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
                                                WHERE os = $os)";
            $res_D = pg_query($con,$sql_D);

            if (pg_num_rows($res_D)>0) {
                for ($x = 0 ; $x < pg_numrows ($res_D); $x++) {
                    $embarque_item = pg_fetch_result ($res_D,$x,embarque_item);

                    #$sql = "SELECT fn_cancelar_embarque_item($embarque_item);";
                    $sql = "SELECT fn_delete_embarque_item($embarque_item);";
                    fwrite($arquivo, "\n\n Excluir item $embarque_item (".date("d/m/Y H:i:s").") \n [ $sql ] pelo $login_unico\n");
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }   
        }else{

            #Envia email para o PA quando � feito o cancelamento de pe�as faturadas
            $sqlX = "SELECT DISTINCT tbl_posto.nome,
                            tbl_posto.email,
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_embarque_item.pedido_item,
                            tbl_pedido.pedido
                    FROM   tbl_embarque
                    JOIN   tbl_embarque_item USING(embarque)
                    JOIN   tbl_peca          USING(peca)
                    JOIN   tbl_posto       ON tbl_posto.posto             = tbl_embarque.posto
                    JOIN   tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
                    JOIN   tbl_pedido      ON tbl_pedido.pedido           = tbl_pedido_item.pedido
                    JOIN   tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                    WHERE  tbl_embarque.embarque  = $numero_embarque
                    AND    tbl_embarque_item.peca = $excluir_embarque_peca
                    AND    (tbl_tipo_pedido.descricao ilike '%faturad%' OR tbl_tipo_pedido.descricao ilike '%venda%')";
            $resX = pg_query ($con,$sqlX);
            if (pg_numrows ($resX) >0) {
                $nome      = pg_fetch_result ($resX,$x,nome);
                $email     = pg_fetch_result ($resX,$x,email);
                $referencia = pg_fetch_result ($resX,$x,referencia);
                $descricao = pg_fetch_result ($resX,$x,descricao);
                $pedido    = pg_fetch_result ($resX,$x,pedido);
                if (strlen($email)>0){
                    $remetente    = "Telecontrol <ronaldo@telecontrol.com.br>";
                    $destinatario = $email;
                    $assunto      = "Cancelamento de Pedido de Pe�a do Distribuidor";
                    $mensagem =  "At. Respons�vel,<br><br>A pe�a $referencia - $descricao do pedido de n�mero $pedido foi cancelado.
                    <br>
                    <br> Caso tenha alguma d�vida, entre em contato com o distribuidor Telecontrol
                    <br><br>Telecontrol Networking";
                    $headers="Return-Path: <ronaldo@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                    if(strlen($mensagem)>0) {
                        //Comentei a pedido do Paulo Lin. �bano
                        //mail($destinatario,$assunto,$mensagem,$headers);
                    }
                }
            }

            if(!empty($exclui_embarque_item)) {
                $cond = " and embarque_item = $exclui_embarque_item ";
            }

            $sqlX = "SELECT embarque_item
                    FROM   tbl_embarque_item
                    WHERE  embarque = $numero_embarque
                    $cond
                    AND    peca     = $peca";
            $resX = pg_query ($con,$sqlX);
            for ($x = 0 ; $x < pg_numrows ($resX); $x++) {
                $embarque_item = pg_fetch_result ($resX,$x,embarque_item);

                #$sql = "SELECT fn_cancelar_embarque_item($embarque_item);";
                $sql = "SELECT fn_delete_embarque_item($embarque_item);";
                fwrite($arquivo, "\n\n Excluir item $embarque_item (".date("d/m/Y H:i:s").") \n [ $sql ] pelo $login_unico\n");
                $res = pg_query ($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
			$sql = "SELECT fn_cancela_embarque(distribuidor, posto, embarque) from tbl_embarque left join tbl_embarque_item using(embarque) where tbl_embarque_item.embarque isnull and tbl_embarque.embarque = $numero_embarque ;";
			fwrite($arquivo, "\n\n Exclui embarque $numero_embarque (".date("d/m/Y H:i:s").") \n [ $sql ] pelo $login_unico\n");
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
        }   

        if (strlen ($msg_erro) == 0) {
            $msg .=  "Opera��o realizada com sucesso.";
            fwrite($arquivo, "\n COMMIT TRANSACTION \n");
            $res = @pg_query ($con,"COMMIT TRANSACTION");
        }else{
            $msg .=  "Opera��o n�o realizada. Erro: $msg_erro";
            fwrite($arquivo, "\n ROLLBACK \n");
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }
        fclose ($arquivo);
        echo "<br><br>";
        }
    }    

if (strlen($excluir_embarque)>0){


    $res = @pg_query($con,"BEGIN TRANSACTION");

    $sql="SELECT fn_cancelar_embarque($excluir_embarque)";
    $res = pg_query ($con,$sql);
    $msg_erro .= pg_errormessage($con);
    #echo nl2br($sql);


    if (strlen ($msg_erro) == 0) {
        $msg .=  "Opera��o realizada com sucesso.";
        $arquivo  = fopen ("log_excluir_embarque.txt", "a+");
        fwrite($arquivo, "\n\n Excluir embarque $excluir_embarque (".date("d/m/Y H:i:s").") \n [ $sql ]\n");
        fclose ($arquivo);

        $res = @pg_query ($con,"COMMIT TRANSACTION");
    }else{
        $msg .=  "Opera��o n�o realizada. Erro: $msg_erro";
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
    echo "<br><br>";
}
?>

<?
if (strlen($msg)>0){
    echo "<h4 style='color:black;text-align:center;border:1px solid #2FCEFD;background-color:#E1FDFF'>$msg</h4>";
}
?>


<?
$embarque = trim($_GET['embarque']);
$maior_embarque = trim($_GET['maior_embarque']);
$cond_01 = " 1=1 ";

if (strlen ($maior_embarque) > 0 AND 1==2) {
    $cond_01 = " tbl_embarque.embarque <= $maior_embarque ";

    $sql = "SELECT DISTINCT tbl_embarque.embarque
            FROM tbl_embarque_item
            JOIN tbl_embarque USING (embarque)
            WHERE tbl_embarque.distribuidor = $login_posto
            AND tbl_embarque.faturar IS NULL
            AND tbl_embarque_item.liberado IS NULL
            AND tbl_embarque_item.impresso IS NULL
            AND tbl_embarque.embarque <= $maior_embarque
            AND tbl_embarque.posto NOT IN (
                SELECT posto
                FROM  tbl_embarque
                WHERE faturar >= CURRENT_DATE - INTERVAL '10 days'
                AND   nf_conferencia IS NOT TRUE
                AND   distribuidor = $login_posto
            )";
    $res = pg_query ($con,$sql);

    for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
        $libera_embarque = pg_fetch_result ($res,$i,0);
        pg_query ($con,"SELECT fn_etiqueta_libera ($libera_embarque)");
    }
}

if (strlen ($embarque) > 0) $cond_01 = " tbl_embarque.embarque = $embarque ";

if ($quais_embarques == "aprovados") {
    //$cond_01 = " tbl_embarque.embarque IN (SELECT DISTINCT embarque FROM tbl_embarque_item WHERE liberado IS NOT NULL ) ";
}
if ($quais_embarques == "liberados") {
    $sql_juncao = "SELECT distinct a.embarque,0 as ordem
                INTO TEMP temp_embarque_juncao
                FROM tbl_embarque_item a 
                    JOIN tbl_embarque_item b using(embarque)
                WHERE a.embarcado::date > b.liberado::date 
                AND b.liberado IS NOT NULL
                AND a.liberado isnull;
                ";
}else{
    $sql_juncao = "SELECT distinct a.embarque,0 as ordem
                INTO TEMP temp_embarque_juncao
                FROM tbl_embarque_item a 
                    JOIN tbl_embarque_item b using(embarque)
                WHERE a.embarcado::date > b.embarcado::date 
                    AND a.liberado IS NULL;
                ";
}

$res_juncao = pg_query($con,$sql_juncao);

$sql = "SELECT  ordem,
                TO_CHAR (tbl_embarque.data,'DD/MM') AS data_embarque,
                tbl_posto.posto,
                tbl_posto.nome,
                tbl_posto.cidade,
                tbl_posto.cnpj,
                tbl_posto.ie,
                tbl_posto.estado,
                tbl_posto.fone,
                tbl_posto_fabrica.contato_cep as cep,
                tbl_posto.data_expira_sintegra,
                tbl_embarque.carga_preparada,
                tbl_embarque.fabrica,
                tbl_embarque.embarque,
                tbl_fabrica.nome as fabrica_nome
        FROM tbl_embarque ";
if ($quais_embarques == "liberados") {
    // $sql .= "
    //      JOIN (
    //      SELECT distinct embarque
    //      FROM tbl_embarque_item
    //      WHERE liberado IS NOT NULL
    //      ) emb ON emb.embarque = tbl_embarque.embarque
    //  ";
    $sql .= "
            JOIN (
            SELECT embarque,ordem
                FROM temp_embarque_juncao 
            UNION
            SELECT distinct c.embarque, 
                1 as ordem 
            FROM tbl_embarque_item c
            JOIN tbl_embarque_item d using(embarque)
            WHERE c.embarcado::date = d.embarcado::date 
                AND c.liberado IS NOT NULL
                AND c.embarque NOT IN (SELECT embarque FROM temp_embarque_juncao)
            ) emb ON emb.embarque = tbl_embarque.embarque
        ";
}else{
    // $sql .= "
    //      JOIN (
    //      SELECT distinct embarque
    //      FROM tbl_embarque_item
    //      WHERE liberado IS NULL
    //      ) emb ON emb.embarque = tbl_embarque.embarque
    //  ";
    $sql .= "
    JOIN (
        SELECT embarque,ordem
                FROM temp_embarque_juncao 
            UNION
            SELECT distinct c.embarque, 
                1 as ordem 
            FROM tbl_embarque_item c
            JOIN tbl_embarque_item d using(embarque)
            WHERE c.embarcado::date = d.embarcado::date 
                AND c.liberado IS NULL
                AND c.embarque NOT IN (SELECT embarque FROM temp_embarque_juncao)
    ) emb ON emb.embarque = tbl_embarque.embarque
    ";
}
$sql .= "
        JOIN tbl_posto USING (posto)
        JOIN tbl_fabrica USING(fabrica)
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
        AND  tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
        WHERE tbl_embarque.faturar IS NULL
        AND   $cond_01
        AND   tbl_embarque.distribuidor = $login_posto ";


/*
if ($btn_acao == ""){
    $sql .= " AND tbl_embarque.carga_preparada IS NOT TRUE ";
}
if ($btn_acao == "embarcar"){
    $sql .= " AND tbl_embarque.carga_preparada IS TRUE ";
}
if ($btn_acao == "liberar_etiqueta"){
    $sql .= " AND tbl_embarque.carga_preparada IS TRUE ";
}*/
$sql .= " ORDER BY ordem,fabrica,embarque";

$res = pg_query ($con,$sql);

$embarque = 0;
$valor_mercadorias = 0;
$pendencia_total   = 0;
$total_pecas       = 0;
$total_embarques   = 0;

echo "<form name='frm_embarque' action='$PHP_SELF' method='POST'>";

if (strlen($btn_acao)==0){
    //echo "<input type='hidden' name='btn_acao' value='embarcar'>";
    echo "<input type='hidden' name='btn_acao' value='liberar_etiqueta'>";
}

if ($btn_acao == "embarcar"){
    //echo "<input type='hidden' name='btn_acao' value='liberar_etiqueta'>";
}
$qtde_embarques = pg_numrows ($res); ?>
<div style="padding-left: 35% !important;">
    <table>
        <tr height="18">
            <td width="18">
                <div class="status_checkpoint" style="background-color: #006400;">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1"><b>Ordem de Servi�o Reincidente</b></font>
            </td>
        </tr>
    </table>
</div>
<br>
<?php
for ($i = 0 ; $i < $qtde_embarques ; $i++) {

    $lista_embarques = array();
    $lista_pecas = array();
    $lista_embarque_item = array();

    $ordem = pg_fetch_result($res, $i, ordem);
    $embarque      = pg_fetch_result ($res,$i,embarque);
    $posto         = pg_fetch_result ($res,$i,posto);
    $data_embarque = pg_fetch_result ($res,$i,data_embarque);
    $carga_preparada= pg_fetch_result ($res,$i,carga_preparada);
    $nome          = pg_fetch_result ($res,$i,nome);
    $fabrica_nome          = pg_fetch_result ($res,$i,'fabrica_nome');
    $fabrica      = pg_fetch_result ($res,$i,'fabrica');
    $cidade        = pg_fetch_result ($res,$i,cidade);
    $estado        = pg_fetch_result ($res,$i,estado);
    $fone          = pg_fetch_result ($res,$i,fone);
    $cep          = pg_fetch_result ($res,$i,cep);
    $cnpj        = pg_fetch_result ($res,$i,cnpj);
    $ie          = pg_fetch_result ($res,$i,ie);
    $data_expira_sintegra = pg_fetch_result ($res,$i,data_expira_sintegra);
    if(strlen($data_expira_sintegra) > 0){
        $sqld="select current_date - '$data_expira_sintegra' > 90 ;";
        $resd=pg_query($con,$sql);
        $bloqueia = pg_fetch_result($resd,0,0);
    }
    $nivel = 0;
    $valor_mercadorias = 0;

    $sqlStatus = "SELECT tbl_embarque_item.embarque, tbl_pedido.pedido, tbl_pedido_status.status 
                 from tbl_embarque_item 
                 join tbl_pedido_item on tbl_pedido_item.pedido_item  = tbl_embarque_item.pedido_item 
                 join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido 
                 join tbl_pedido_status on tbl_pedido_status.pedido = tbl_pedido.pedido   
                 where embarque = $embarque order by tbl_pedido_status.data desc limit 1 ";
    $resStatus = pg_query($con, $sqlStatus);
    if(pg_num_rows($resStatus)>0){
        $status = pg_fetch_result($resStatus, 0, status);
        if($status == 11){
            continue;
        }
    } 


    $fabrica_contrato_correios = $fabrica;

    $sqlfab = "SELECT tipo_posto FROM tbl_tipo_posto
            join tbl_posto_fabrica using(tipo_posto,fabrica)
            where tbl_posto_fabrica.fabrica = $fabrica
            and posto = $posto
            and tbl_tipo_posto.distribuidor";
    $resfab = pg_query($con,$sqlfab);
    if(pg_num_rows($resfab) > 0) {
        $dis = "DI:";
    }else{
        $dis = "";
    }

    if ($i>0){
        echo "<p align='right' class='embarquenumimpressao'>Embs.: $total_embarques ; Acumulado Pe�as: $total_pecas <br></p>";
        if ($btn_acao == "embarcar"){
            //echo "<a href='$PHP_SELF?maior_embarque=$embarque'>Embarcar at� aqui</a>";
        }
    }
    if($quais_embarques == "liberados"){
        ?><div id="mensagem<?=$i?>"></div><?php
    }

    echo "\n<table id='tabela_correios_cotacao_$i' border='1' align='center' cellpadding='3' cellspacing='0' width='850'>";

    $sql = "SELECT  tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_peca.ipi,
                    emb.peca,
                    emb.qtde,
                    tbl_posto_estoque_localizacao.localizacao,
                    (   SELECT tbl_tabela_item.preco
                        FROM tbl_tabela_item
                        JOIN tbl_posto_linha ON tbl_posto_linha.posto = $login_posto
                        AND tbl_posto_linha.tabela = tbl_tabela_item.tabela
                        WHERE tbl_peca.peca = tbl_tabela_item.peca
                        ORDER BY preco DESC
                        LIMIT 1) AS preco
            FROM tbl_embarque
            JOIN (SELECT embarque, peca, SUM (qtde) AS qtde
                FROM tbl_embarque_item ";
            if ($quais_embarques == "liberados") {
                $sql .= " WHERE liberado IS NOT NULL ";
            }else{
                $sql .= " WHERE liberado IS NULL ";
            }
            $sql .= "GROUP BY embarque,peca
            ) emb ON tbl_embarque.embarque = emb.embarque
            JOIN tbl_posto USING (posto)
            JOIN tbl_peca  USING (peca)
            LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = tbl_embarque.distribuidor AND tbl_posto_estoque_localizacao.peca = emb.peca
            WHERE tbl_embarque.embarque      = $embarque
            AND   tbl_embarque.distribuidor  = $login_posto
            AND   tbl_embarque.faturar       IS NULL
            ORDER BY referencia";
        //Buscar pre�o dos itens da loja virtual tb
            $sql = "SELECT tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_peca.ipi,
                        tbl_peca.fabrica,
                        emb.peca,
						emb.qtde ,
						emb.pedido_item,
                        tbl_posto_estoque_localizacao.localizacao,
                        tbl_pedido_item.preco,
                        tbl_pedido.desconto
                    FROM tbl_embarque
                    JOIN (SELECT embarque, peca, pedido_item, SUM (qtde) AS qtde
                    FROM tbl_embarque_item ";
                if ($quais_embarques == "liberados") {
                    $sql .= " WHERE liberado IS NOT NULL ";
                }else{
                    $sql .= " WHERE liberado IS NULL ";
                }
                $sql .= "
                    GROUP BY embarque,peca,pedido_item
                    ) emb ON tbl_embarque.embarque = emb.embarque
                    JOIN tbl_posto USING (posto)
                    JOIN tbl_peca  USING (peca)
                    JOIN tbl_pedido_item USING(pedido_item)
                    JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                    LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = tbl_embarque.distribuidor AND tbl_posto_estoque_localizacao.peca = emb.peca
                    LEFT join tbl_faturamento_item using(pedido_item) 
                    WHERE tbl_embarque.embarque      = $embarque
                    AND   tbl_embarque.distribuidor  = $login_posto
                    AND   tbl_embarque.faturar       IS NULL
                    and   tbl_faturamento_item.os isnull
                    GROUP BY  tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_peca.ipi,
                        tbl_peca.fabrica,
						emb.peca,
						emb.qtde,
						emb.pedido_item,
                        tbl_posto_estoque_localizacao.localizacao,
                        tbl_pedido_item.preco,
                        tbl_pedido.desconto
                    ORDER BY localizacao,referencia";
    $resZ = pg_query ($con,$sql);
    
    echo "<tbody>";
    for ($j = 0 ; $j < pg_numrows ($resZ) ; $j++) {
        $referencia = pg_fetch_result ($resZ,$j,referencia);
        $descricao  = pg_fetch_result ($resZ,$j,descricao);
        $ipi        = pg_fetch_result ($resZ,$j,ipi);
        $peca       = pg_fetch_result ($resZ,$j,peca);
        $qtde       = pg_fetch_result ($resZ,$j,qtde);
        $pedido_item= pg_fetch_result ($resZ,$j,'pedido_item');
        $localizacao= pg_fetch_result ($resZ,$j,localizacao);
        $preco      = pg_fetch_result ($resZ,$j,preco);
        $fabrica_peca      = pg_fetch_result ($resZ,$j,fabrica);
        $desconto_pedido   = pg_fetch_result ($resZ,$j,desconto);
        $preco_original = $preco;

        if ($fabrica_peca == 147) {
            $preco = ($preco * (($ipi/100) +1 )) * (1 - ($desconto_pedido / 100));
        } else {
        }
		$recall = "";
        echo "<tr style='font-size:12px'>";

        echo "<td nowrap>";
            $sql = "SELECT  tbl_embarque_item.embarque_item ,
                            CASE WHEN tbl_os.data_abertura IS NOT NULL THEN
                                    CURRENT_DATE - tbl_os.data_abertura::date
                                    ELSE CURRENT_DATE - tbl_pedido.data::date END  AS dias,
                            CASE WHEN tbl_tipo_pedido.pedido_em_garantia THEN 'G'
                                 WHEN tbl_tipo_pedido.pedido_faturado THEN 'F'
                                  WHEN tbl_embarque_item.os_item IS NULL THEN 'F' ELSE 'G' END  AS fat_gar ,
                            tbl_os.sua_os ,
                            tbl_os.os,
                            tbl_os.fabrica,
                            tbl_os_item.os_item,
                            tbl_os_item.parametros_adicionais,
                            tbl_os.os_reincidente,
                            tbl_pedido.pedido,
                            tbl_embarque_item.impresso,
                            tbl_pedido.origem_cliente
                    FROM   tbl_embarque_item
                    JOIN   tbl_pedido_item USING (pedido_item)
                    JOIN   tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
                    JOIN   tbl_tipo_pedido USING(tipo_pedido)
                    LEFT JOIN tbl_os_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
                    LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    LEFT JOIN tbl_os         ON tbl_os_produto.os = tbl_os.os
                    LEFT JOIN tbl_faturamento_item on tbl_faturamento_item.os_item = tbl_os_item.os_item 
                    WHERE  tbl_embarque_item.embarque = $embarque
                    AND    tbl_embarque_item.peca     = $peca
                    AND    tbl_embarque_item.pedido_item     = $pedido_item
                   AND     ( tbl_pedido_item.preco= '$preco_original' or (tbl_pedido.fabrica = 168 and tbl_pedido_item.preco>0))  ";
                if ($quais_embarques == "liberados") {
                    $sql .= "AND tbl_embarque_item.liberado IS NOT NULL ";
                }else{
                    $sql .= "AND tbl_embarque_item.liberado IS NULL
                            AND tbl_faturamento_item.os isnull  ";
                }
            $sql .= "ORDER BY tbl_embarque_item.embarque_item";
            $resx = pg_query ($con,$sql);
            //echo $sql;

            for ($x = 0 ; $x < pg_numrows ($resx); $x++) {

                $sql3 = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca limit 1";
                $res3 = pg_query($con,$sql3);
                if(pg_numrows($res3)>0){
                    $os_item_preco = pg_fetch_result($res3,0,0);
                }


                $os            = pg_fetch_result ($resx,$x,os);
                $sua_os        = pg_fetch_result ($resx,$x,sua_os);
                $fabrica_id    = pg_fetch_result ($resx,$x,fabrica);
                $embarque_item = pg_fetch_result ($resx,$x,embarque_item);
                $fat_gar       = pg_fetch_result ($resx,$x,fat_gar);
                $dias          = pg_fetch_result ($resx,$x,dias);
                $impresso      = pg_fetch_result ($resx,$x,impresso);
                $pedido        = pg_fetch_result ($resx,$x,pedido);
                $parametros_adicionais        = pg_fetch_result ($resx,$x,'parametros_adicionais');
                $os_item       = pg_fetch_result ($resx,$x,os_item);
                $os_reincidente       = pg_fetch_result ($resx,$x,os_reincidente);
                $origem_cliente= pg_fetch_result ($resx,$x,'origem_cliente');

                if(strpos($parametros_adicionais,'recall')) {
                    $recall = true;
                }else{
                    $recall = false; 
                }

                $parcial = "";

                if (strlen($os)>0){


                    $sqlTroca = "SELECT tbl_os.os
                                FROM tbl_os
                                JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
                                WHERE tbl_os.fabrica = $fabrica_id
                                AND   tbl_os.os      = $os";
                    $resTroca = pg_query ($con,$sqlTroca);
                    if ( pg_numrows ($resTroca) >0 ){

                        //echo "<acronym title='Esta OS � de TROCA. Excluia o item deste embarque'><b style='color:red'>(T) <a href='$PHP_SELF?excluir_troca=sim&os_item=$os_item&pedido=$pedido&peca=$peca' target='_blank' class='noprint'>(Excluir)</a></b></acronym> ";
                        //echo " <a href=\"javascript:excluirItem('$PHP_SELF?numero_embarque=$embarque&excluir_embarque_peca=$peca&os=$os')\" class=noprint>(Excluir)</a>";
                        echo "<acronym title='Esta OS � de TROCA. Excluia o item deste embarque'><b style='color:red'>(T) <a href=\"javascript:excluirItem('$PHP_SELF?numero_embarque=$embarque&excluir_embarque_peca=$peca&os=$os')\" class=noprint>(Excluir)</a></b></acronym> ";
                    }

                    if (1==1){
                        $sql_parcial = "SELECT tbl_embarque.posto, tbl_embarque_item.embarque, osx.os_item, tbl_embarque_item.pedido_item, tbl_embarque_item.peca, tbl_embarque_item.qtde
                            FROM (
                                SELECT DISTINCT oss.os_item
                                FROM (
                                    SELECT tbl_os.os, tbl_os_item.os_item
                                    FROM tbl_os
                                    JOIN tbl_os_produto USING (os)
                                    JOIN tbl_os_item    USING (os_produto)
                                    JOIN tbl_embarque_item USING (os_item)
                                    JOIN tbl_embarque      USING (embarque)
                                    WHERE tbl_embarque.distribuidor  = $login_posto
                                    AND   tbl_os.os                  = $os
                                    AND   tbl_embarque.faturar       IS NULL
                                    AND tbl_embarque_item.impresso   IS NULL
                                ) oss
                                JOIN tbl_os                 ON tbl_os.os                     = oss.os AND tbl_os.os = $os
                                JOIN tbl_os_produto         ON oss.os                        = tbl_os_produto.os
                                JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto AND oss.os_item = tbl_os_item.os_item
                                JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                                LEFT JOIN tbl_embarque_item ON tbl_os_item.os_item           = tbl_embarque_item.os_item
                                LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.os = tbl_os.os AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
                                WHERE (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto OR tbl_servico_realizado.ressarcimento)
                                AND tbl_embarque_item.os_item IS NULL
                                AND tbl_pedido_cancelado.pedido IS NULL
                            ) osx
                            JOIN tbl_os_item        ON osx.os_item           = tbl_os_item.os_item
                            JOIN tbl_embarque_item  ON osx.os_item           = tbl_embarque_item.os_item
                            JOIN tbl_embarque       ON tbl_embarque.embarque = tbl_embarque_item.embarque";
                        $resParcial = pg_query ($con,$sql_parcial);
                        //echo nl2br($sql_parcial);

                        if ( pg_numrows ($resParcial) >0 ){
    /*
                            $sql = "SELECT tbl_os_item.peca
                                    FROM   tbl_os
                                    JOIN   tbl_os_produto USING (os)
                                    JOIN   tbl_os_item    USING (os_produto)
                                    LEFT JOIN tbl_embarque_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
                                    WHERE  tbl_os.os         = $os
                                    AND    tbl_os.posto      = $posto
                                    AND    tbl_embarque_item.embarque_item IS NULL
                                    AND    tbl_os_item.os_item <> $os_item";
                            $resPecas = pg_query ($con,$sql);

                            for ($Y = 0 ; $Y < pg_numrows ($resPecas); $Y++) {
                                $Xpeca = pg_fetch_result ($resPecas,$Y,peca);
    /*
                                $sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                                TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                                        FROM    tbl_faturamento
                                        JOIN    tbl_faturamento_item USING (faturamento)
                                        WHERE   tbl_faturamento.posto        = $posto
                                        AND     tbl_faturamento.distribuidor = $login_posto
                                        AND     tbl_faturamento_item.pedido  = $pedido
                                        AND     tbl_faturamento_item.peca    = $Xpeca
                                         ";
                                $resPedido = pg_query ($con,$sql);
                                if ( pg_numrows ($resPedido) == 0 ){
                                    */
                                    if ($os <> "4119143" AND $os <> "759223" AND $os<>"4215483"  AND $os<>"4215484"  AND $os<>"4215487"  AND $os<>"4418718"  AND $os<>"4169775" AND $os<>"4039364"){
                                        $os_parcial .= " / ".$os;
                                        $parcial = 1;
                                    }
                                //}
                            #}
                        }
                    }
                }

                if (strlen($impresso)>0){
                    echo "";
                }else{
                }

                echo $embarque_item;
                echo " - " ;
                echo $fat_gar;
                echo " " ;

                if ($dias > 15){
                    echo "<font size='+1' color='#ff0000'><b>".$dias."</b></font> - ";
                }else{
                    echo $dias." - ";
                }
                
                if (strlen($pedido) > 0) { /*HD - 6072298*/
                    $lbl_pedido = "";

                    if ($j == 0) {
                        $lbl_pedido = "(Pedido) ";
                    }

                    echo " <span style='color:#ac8b0d' class='noprint'><b>". $lbl_pedido . $pedido ."</b></span> ";
                    echo "</span>";
                }

                if (strlen($sua_os) > 0) {
                    if (strlen($pedido) > 0) {
                        echo "<span style='color:#ac8b0d' class='noprint'><b> - </b></span>";
                    }
                    
                    echo "<a href='/assist/os_press.php?os=".$os."&login_posto=".$posto."&distribuidor=4311' target='_blank'>";             

                    if ($parcial==1){
                        echo "<span style='color:#FF0909'>";
                        echo $sua_os." <span style='font-size:10px'>(parcial)</span>";
                        echo "</span>";
                    }else{
                        /*HD - 6009882*/
                        if ($os_reincidente == 't') {
                            echo "<label style='color: #006400;'>$sua_os</label>";
                        } else {
                            echo $sua_os;
                        }
                    }
                    echo "</a>";
                    
                }

                

                if (strlen($sua_os)>0) {
                    //echo " <a href=\"javascript:excluirItem('$PHP_SELF?numero_embarque=$embarque&excluir_embarque_peca=$peca&os=$os')\" class=noprint>(Desembarcar)</a>";
                }

                if($origem_cliente == 't' and !empty($pedido)) {
                        $sqlf = "SELECT tbl_hd_chamado_extra.nome, tbl_fabrica.nome, tbl_hd_chamado_extra.cep
                                FROM tbl_hd_chamado_extra
                                JOIN tbl_pedido USING(pedido)
                                JOIN tbl_fabrica USING(fabrica)
                                WHERE tbl_pedido.pedido = $pedido";
                        $resf = pg_query($con,$sqlf);

                        if(pg_num_rows($resf) > 0 ) {
							$cep = pg_fetch_result($resf,0,'cep');
                            echo pg_fetch_result($resf,0,0);
                            echo " - ";
                            echo pg_fetch_result($resf,0,1);
                        }
                }
                #HD 20202
                if (($dias > 20 or $os_reincidente == 't') and $fat_gar=='G' and strlen($os)>0 ){
                    echo " <span style='color:#FF0033' class='noprint'>- (<a href='javascript:abrirAviso($os,$pedido)' style='color:#FF0033'>ATEN��O</a>)</span> ";
                    echo "</span>";
                }

                if ($x <= pg_numrows($resx)) echo "<br>";
            }
        echo "</td>";
        if(strlen($os_item_preco) == 0){$cor_preco = "style='color:#33CC00'";}else{$cor_preco = '';}
        echo "<td $cor_preco class=destaqueimpressao nowrap>".$referencia;
        echo ($recall) ? " - <font color='red'>RECALL</font>": "";
        echo "</td>";
        $os_item_preco = '';
        echo "<td class='produtoimpressao'>". $descricao. "</td>";
        echo "<td align='right' width='20' class=destaqueimpressao>".$qtde."</td>";
        echo "<td nowrap align='left' class=destaqueimpressao>".$localizacao."</td>";

        $total_pecas += $qtde;

        echo "<td class=noprint nowrap align='left' title='Cancela o embarque, volta as pe�as para o estoque, mas n�o cancela o pedido!'.>";
        for ($x = 0 ; $x < pg_numrows ($resx); $x++) {
            $embarque_item = pg_fetch_result ($resx,$x,embarque_item);

            $lista_embarques[] = $embarque;
            $lista_pecas[] = $peca;
            $lista_embarque_item[] = $embarque_item;

            echo "<p><a href=\"javascript:excluirItem('$PHP_SELF?numero_embarque=$embarque&excluir_embarque_peca=$peca&exclui_embarque_item=$embarque_item&tipo_embarque=$quais_embarques')\" class=noprint>Desembarcar</a></p>";
        }
        echo "</td>";
        
        echo "</tr>";

        $valor_mercadorias += $qtde * $preco;
    }
    echo "</tbody>";

    $sql = "SELECT  TO_CHAR(emissao,'DD/MM/YYYY') AS ultimo_faturamento,
            CURRENT_DATE - emissao AS dias_do_ultimo_faturamento
            FROM  (
                SELECT embarque
                FROM tbl_embarque
                WHERE posto      = $posto
                AND distribuidor = $login_posto
                AND fabrica = $fabrica
                AND faturar      IS NOT NULL
                ORDER BY data DESC LIMIT 1
            ) emb
            JOIN tbl_faturamento ON tbl_faturamento.embarque = emb.embarque
            WHERE posto    = $posto
    ";
    $resY = pg_query ($con,$sql);
    
    if (pg_numrows ($resY)>0){
        $ultimo_faturamento      = pg_fetch_result ($resY,0,ultimo_faturamento);
        $dias_do_ultimo_embarque = pg_fetch_result ($resY,0,dias_do_ultimo_faturamento);
    }else{
        $ultimo_faturamento      = "Primeiro Embarque";
        $dias_do_ultimo_embarque = "8";
    }

    if ($dias_do_ultimo_embarque > 7){
        $nivel += 5;
    }

    if ($fabrica == 168) {
        //C�digo que verifica o campo parametros_adicionais na tbl_f�brica e que monta as vari�veis de verifica��o
        $sql_calc_ipi = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $fabrica_pedido ;";
        $res_calc_ipi = pg_query($con,$sql_calc_ipi);
        if (pg_num_rows($res_calc_ipi) > 0) {
            $param_adc = json_decode(pg_fetch_result($res_calc_ipi, 0, 'parametros_adicionais'), true); // Array
            if (array_key_exists('usa_calculo_ipi', $param_adc)) {
                $calcula_ipi = true;
            } else {
                $calcula_ipi = false;
            }
        }

        $sql_cond = "SELECT tbl_embarque_item.qtde,
                            CASE WHEN tbl_tabela_item.preco IS NOT NULL THEN
                                    tbl_tabela_item.preco
                                    ELSE 
                                    tbl_pedido_item.preco 
                                    END  AS preco,
                            tbl_peca.ipi,
                            tbl_condicao.parcelas,
                            tbl_condicao.acrescimo_financeiro,
                            tbl_condicao.desconto_financeiro,
                            tbl_pedido.pedido
                        FROM tbl_embarque_item
                            JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca
                            JOIN tbl_pedido_item ON tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item
                            JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                            JOIN tbl_tipo_pedido USING (tipo_pedido)
                            JOIN tbl_condicao ON tbl_pedido.condicao = tbl_condicao.condicao
                            LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_pedido.tabela AND tbl_tabela_item.peca = tbl_embarque_item.peca
                        WHERE tbl_embarque_item.embarque = $embarque";
        $res_cond = pg_query($con,$sql_cond);
        $total_embarque_cond = 0;
        if (pg_num_rows($res_cond) > 0 ) {
            for ($k=0; $k < pg_num_rows($res_cond) ; $k++) {
                $qtde_item_cond       = pg_fetch_result($res_cond, $k, qtde);
                $preco_item_cond      = pg_fetch_result($res_cond, $k, preco);
                $ipi_item_cond        = pg_fetch_result($res_cond, $k, kipi);
                $parcelas_item_cond   = pg_fetch_result($res_cond, $k, parcelas);
                $acrescimo_financeiro = pg_fetch_result($res_cond, $k, acrescimo_financeiro);
                $desconto_financeiro  = pg_fetch_result($res_cond, $k, desconto_financeiro);
                $pedido_item_cond     = pg_fetch_result($res_cond, $k, pedido);

                $preco_total_item_cond = $qtde_item_cond * $preco_item_cond;

                if ($calcula_ipi) {
                    $preco_total_item_cond = $preco_total_item_cond + (( $ipi_item_cond / 100 ) * $preco_total_item_cond );
                }

                if ($acrescimo_financeiro > 0.00) {
                    if (!empty($acrescimo_financeiro) && $acrescimo_financeiro > 0) {
                        $acrescimo_financeiro = ($acrescimo_financeiro - 1) * 100;
                    }
                    $taxa = $acrescimo_financeiro / 100;
                    $total_embarque_cond = $total_embarque_cond + ( $preco_total_item_cond * (pow((1 + $taxa), $parcelas_item_cond)) );
                    
                } elseif ($desconto_financeiro > 0.00) {
                    $total_embarque_cond = $total_embarque_cond + $preco_total_item_cond;
                    $porcento = ($desconto_financeiro / 100) * $preco_total_item_cond;
                    $total_embarque_cond = $total_embarque_cond - $porcento;
                } else {
                    $total_embarque_cond = $total_embarque_cond + $preco_total_item_cond;
                }
            }
        }
        $valor_mercadorias = $total_embarque_cond;
    }

    if ($valor_mercadorias > 50){
        $nivel += 5;
    }

/*
    $sql = "SELECT  count(*) AS qtde_os_prazo
            FROM   tbl_embarque_item
            JOIN   tbl_pedido_item USING (pedido_item)
            JOIN   tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
            LEFT JOIN tbl_os_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
            LEFT JOIN tbl_os         ON tbl_os_produto.os = tbl_os.os
            WHERE  tbl_embarque_item.embarque = $embarque
            AND    tbl_embarque_item.os_item  IS NOT NULL
            AND    CURRENT_DATE - tbl_pedido.data::date > 14
            ";
    $resX = pg_query ($con,$sql);
    if (pg_numrows ($resX)>0){
        $qtde_os_prazo = pg_fetch_result ($resX,0,qtde_os_prazo);
        if ($qtde_os_prazo>0){
            $nivel += 4;
        }
    }
*/
    echo "<thead>";
    echo "<tr>";
    echo "\n<input type='hidden' name='embarque_".$i."' value='$embarque'>\n";
    echo "<td colspan='5' align='center'>";
        #HD 195632
        if(strlen($embarque)>0){
            $sqlP = "SELECT DISTINCT tbl_pedido.pedido             ,
                            tbl_pedido.posto              ,
                            tbl_pedido.pedido_loja_virtual,
                            (select fabrica from tbl_posto_fabrica where tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = 51) as atende
                    FROM tbl_pedido
                    JOIN tbl_pedido_item   USING(pedido)
                    JOIN tbl_embarque_item USING(pedido_item)
                    JOIN tbl_embarque      USING(embarque)
                    WHERE tbl_embarque.embarque = $embarque;";
                    #echo nl2br($sqlP);
            $resP = pg_query($con, $sqlP);

            if(pg_numrows($resP)>0){
                $pedido_loja_virtual = pg_fetch_result($resP,0,pedido_loja_virtual);
                $atende              = pg_fetch_result($resP,0,atende);

                if($pedido_loja_virtual=="t"){
                    if($atende=="51"){
                        echo "<div style='text-align: left; color:#FF0000; font-size:11px; font-weight:bold;'>Frete Gr�tis - Loja Virtual (Posto Gama)</div>";
                    }else{
                        echo "<div style='text-align: left; color:#FF0000; font-size:11px; font-weight:bold;'>Cobrar Frete - Loja Virtual</div>";
                    }
                }
                }
        }
    echo "<b>";
    //echo "<a href='embarque_conferencia.php?embarque=$embarque&etiqueta=S' target='_blank'>Etiquetas: </a>";
    // 16/4/9 MLG Alterado tamanho do n� de embarque a pedido do Sr. Laudir
    
    if ($ordem == 0 and $quais_embarques == 'liberados') {
        $avisoJuncao = "<font size='+1' color='#ff0000'><b>Jun��o</b></font>";
    }else{
        $avisoJuncao ="";
    }
    echo "<B style='font-size: 2em;font-weight: normal;'>".$avisoJuncao." (".$embarque.")</B> - " . $data_embarque . " - <a href =\"javascript: alteraDado('nome','$nome','$posto')\" class='destaqueimpressao'><b style='color:red'>$dis</b> $nome</a> - $fabrica_nome";
    echo "</b><br>";
    echo "<a href =\"javascript: alteraDado('cnpj','$cnpj','$posto')\">CNPJ: $cnpj</a>  -  <a href =\"javascript: alteraDado('ie','$ie','$posto')\">I.E.: $ie</a> ";
    echo " - ";
    echo $cidade . " - " . $estado;
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<button type='button' id='contatos' name='contatos' onclick='verContatos($posto,$fabrica)' ><b>Contatos</b></button>";
    //echo $fone;

    echo " <br> �ltimo embarque: ";
    if ($dias_do_ultimo_embarque > 7){
        echo "<span style='color:red'>".$ultimo_faturamento."</span>";
    }else{
        echo "<span>".$ultimo_faturamento."</span>";
    }
    echo "</td>";

    $classe = "nivel1";

    if ($nivel > 9){
        $classe = "nivel2";
    }

    echo "<td align='center' class=noprint>";
//  if (strlen($btn_acao)==0){
        //echo $nivel;

    if ($quais_embarques != "liberados") {
        if (strlen($os_parcial) > 0 && 1 == 2) {//HD 280635 - DESATIVADO
            echo "<b style='color:#FF0909'>OS Parcial</b>";
            // <a href='$PHP_SELF?desembarcar_parcial=$embarque'>(desembarcar parcial)</a>
        } else {
            $sql3 = "SELECT tbl_pedido_item.peca, pedido
                        FROM tbl_embarque_item
                        JOIN tbl_pedido_item using(pedido_item)
                        WHERE embarque = $embarque
                        AND (tbl_pedido_item.preco IS NULL OR tbl_pedido_item.preco in ('0.01','10000'));";
            $res3 = pg_query($con,$sql3);
            if (pg_numrows($res3) > 0) {
                $pedido_cal = pg_fetch_result($res3,0,pedido);
                echo "<FONT COLOR='#33CC00'>Bloqueado por falta de pre�o na pe�a<a href='embarque_geral_conferencia_novo.php?pedido_recalcula=$pedido_cal'>&nbsp;Recalcula</a></FONT>";
            } else {
                //samuel desabilitou provisoriamente
                //if(strlen($data_expira_sintegra) ==0 or strlen($ie) ==0 or $bloqueia == 't' ){
                //  echo "Bloqueado por falta<br>de dados na Sintegra";
                //}else{
                    echo "<input type='button' class='$classe' onClick=\"

                            if (this.value=='LIBERAR'){
                                this.form.ativo_".$i.".value='$embarque';
                                this.value='CANCELAR';
                                this.className='selecionado';
                            }else{
                                this.form.ativo_".$i.".value='';
                                this.value='LIBERAR';
                                this.className='$classe';
                            }

                            \" value='LIBERAR'>";
                    echo "<input type='hidden' name='ativo_".$i."' value=''>";
                //}
            }
        }
    }
//  }

    echo "</td>";

    echo "</tr>";
    echo "</thead>";

    echo "<tfoot>";
    if($quais_embarques == "liberados"){

        $sql = "SELECT tbl_etiqueta_servico.etiqueta_servico,
                tbl_etiqueta_servico.etiqueta,
                tbl_etiqueta_servico.preco,
                tbl_etiqueta_servico.peso,
                tbl_etiqueta_servico.caixa,
                tbl_servico_correio.descricao
            FROM tbl_etiqueta_servico 
                JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio = tbl_etiqueta_servico.servico_correio
            WHERE embarque = ".$embarque;
        $resultadoEtiqueta = pg_query($con, $sql);
        
        $etiqueta_servico = "";
        $etiqueta = "";
        $preco = "";
        $caixa = "";
        $peso  = "";
        $descricao_etiqueta = "";
        $frete_transportadora = "";

        if(pg_num_rows($resultadoEtiqueta) > 0){
            $resultadoEtiqueta = pg_fetch_array($resultadoEtiqueta);
            
            $etiqueta_servico   = $resultadoEtiqueta['etiqueta_servico'];
            $etiqueta           = $resultadoEtiqueta['etiqueta'];
            $descricao_etiqueta = $resultadoEtiqueta['descricao'];
            $preco              = $resultadoEtiqueta['preco'];
            $peso     = $resultadoEtiqueta['peso'];
            $caixa    = $resultadoEtiqueta['caixa'];
        } else {

            $sql = "SELECT tbl_frete_transportadora.frete_transportadora,
                        tbl_frete_transportadora.codigo_rastreio as etiqueta,
                        tbl_frete_transportadora.preco,
                        tbl_frete_transportadora.peso,
                        tbl_frete_transportadora.caixa,
                        tbl_servico_transportadora.descricao
                    FROM tbl_frete_transportadora 
                    JOIN tbl_servico_transportadora ON tbl_servico_transportadora.servico_transportadora = tbl_frete_transportadora.servico_transportadora
                    WHERE tbl_frete_transportadora.embarque = {$embarque}";
            $resultadoEtiqueta = pg_query($con, $sql);

            $resultadoEtiqueta = pg_fetch_array($resultadoEtiqueta);

            $frete_transportadora   = $resultadoEtiqueta['frete_transportadora'];
            $etiqueta               = $resultadoEtiqueta['etiqueta'];
            $descricao_etiqueta     = $resultadoEtiqueta['descricao'];
            $preco                  = $resultadoEtiqueta['preco'];
            $peso                   = $resultadoEtiqueta['peso'];
            $caixa                  = $resultadoEtiqueta['caixa'];

        }
?>
    <tr>
    <td colspan='2'>
        <b>Peso Real: <input type="text" name="<?php echo 'peso_real'.$i; ?>" id="<?php echo 'peso_real'.$i; ?>" value="<?=$peso?>"></b>
        <input hidden type="text" value="<?=$cep?>" name="<?php echo 'cep'.$i; ?>" id="<?php echo 'cep'.$i; ?>">
        <input hidden type="text" value="<?=$embarque?>" name="<?php echo 'embarque'.$i; ?>" id="<?php echo 'embarque'.$i; ?>">
        <input hidden type="text" value="<?=$etiqueta_servico?>" name="<?php echo 'etiqueta_servico'.$i; ?>" id="<?php echo 'etiqueta_servico'.$i; ?>">
        <input hidden type="text" value="<?=$frete_transportadora?>" name="<?php echo 'frete_transportadora'.$i; ?>" id="<?php echo 'frete_transportadora'.$i; ?>">
        <input hidden type="text" value="<?php echo number_format($valor_mercadorias,2,",","."); ?>" name="<?php echo 'valor_nota'.$i; ?>" id="<?php echo 'valor_nota'.$i; ?>">
    </td>
    <td colspan='1' style="min-width: 140px;">
        <!-- Antigas caixas Tipo 1 18x13,5x9  == Tipo 5 54x36x27 -->
        <strong>Caixa (CxLxA):</storng> 

        <div class="box_tamanho_comum_<?php echo $i; ?>">
	        <select name="<?php echo 'caixa'.$i; ?>" id="<?php echo 'caixa'.$i; ?>" onchange="tamanho_personalizado(<?php echo $i; ?>, this.value)" >
	            <option value="18,13,6" <?php echo $caixa == "18,13,6" ? "selected" : ""; ?>>Tipo 1 (18x13x6)</option>
	            <option value="32,13,10" <?php echo $caixa == "32,13,10" ? "selected" : ""; ?>>Tipo 2 (32x13x10)</option>
	            <option value="30,20,13" <?php echo $caixa == "30,20,13" ? "selected" : ""; ?>>Tipo 3 (30x20x13)</option>
	            <option value="30,20,23" <?php echo $caixa == "30,20,23" ? "selected" : ""; ?>>Tipo 4 (30x20x23)</option>
	            <option value="35,35,20" <?php echo $caixa == "35,35,20" ? "selected" : ""; ?>>Tipo 5 (35x35x20)</option>
	            <option value="48,22,43" <?php echo $caixa == "48,22,43" ? "selected" : ""; ?>>Tipo 6 (48x22x43)</option>
	            <option value="60,35,35" <?php echo $caixa == "60,35,35" ? "selected" : ""; ?>>Tipo 7 (60x35x35)</option>
	            <option value="30,30,25" <?php echo $caixa == "30,30,25" ? "selected" : ""; ?>>Tipo 8 (30x30x25)</option>
	            <option value="45,30,10" <?php echo $caixa == "45,30,10" ? "selected" : ""; ?>>Tipo 9 (45x30x10)</option>
	            <option value="53,53,45" <?php echo $caixa == "53,53,45" ? "selected" : ""; ?>>Tipo 10 (53x53x45)</option>
	            <option value="61,52,40" <?php echo $caixa == "61,52,40" ? "selected" : ""; ?>>Tipo 11 (61x52x40)</option>
	            <option value="49,26,26" <?php echo $caixa == "49,26,26" ? "selected" : ""; ?>>MPBX 100 (49x26x26)</option>
	            <option value="tamanho_personalizado" >Personalizado</option>
	        </select>
        </div>

        <div class="box_tamanho_personalizado_<?php echo $i; ?>" style="display: none;">
        	<select name="tipo_tamanho_personalizado_<?php echo $i; ?>" onchange="verifica_tipo_personalizado(this.value, <?php echo $i; ?>)" style="margin-bottom: 4px; margin-top: 4px;" >
        		<option value="1">Pacotes e Caixas</option>
        		<option value="2">Envelopes</option>
        		<option value="3">Rolos e Cilindros</option>
        	</select>
        	<br />
            C: <input type="text" name="comp_perso_<?php echo $i; ?>" maxlength="3" class="numeric" style="width: 50px; margin-bottom: 4px;" title="Comprimento"> cm <br />
            <span class="nome_larg_<?php echo $i; ?>" style="padding-left: 1px;">L</span>: <input type="text" name="larg_perso_<?php echo $i; ?>" maxlength="3" class="numeric" style="width: 50px; margin-bottom: 4px;" title="Largura"> cm <br />
            A: <input type="text" name="alt_perso_<?php echo $i; ?>" maxlength="3" class="numeric" style="width: 50px; margin-bottom: 4px;" title="Altura"> cm <br />
        </div>

    </td>
    <td colspan='2'>
        <b>Vol.: <input type="text" name="<?php echo 'volume'.$i; ?>" id="<?php echo 'volume'.$i; ?>" value="1"></b>
    </td>
    <td colspan='2' align="center">
        <img src='../imagens/loading_img.gif' style='height: 27px; margin-top: 2px;' hidden class="img_loading" />
        <input type="button" name="<?php echo 'cota_frete'.$i; ?>" value="Cotar Frete" onclick="buscaEnvio(this.name,<?=$fabrica_contrato_correios?>, '<?= $embarque ?>')">
    </td>
    </tr>
    <tr>
       <!--  <table id="tabela_correios_cotacao_<?=$i?>" border='1' align='center' cellpadding='3' cellspacing='0' width='850'>
            <tbody>
                
            </tbody>
        </table> -->
    </tr>
    <!-- <tr id="servico<?=$i?>">
    </tr>
    <tr id="servico2<?=$i?>">
    </tr>
    <tr id="servico3<?=$i?>">
    </tr> -->
    <tr id="etiqueta<?=$i?>">
        <?php 
            if($etiqueta != ""){
                ?>
                    <td colspan="4"><b><?php echo "Servi�o selecionado: </b>".$descricao_etiqueta." no valor de R$ ".str_replace(".", ",", $preco);?></td>
                    <td colspan="2"><b>Etiqueta: </b><input type="text" readOnly name="<?php echo 'etiqueta'.$i; ?>" value="<?php echo $etiqueta ?>"></td>
                <?php
            } else if (!empty($frete_transportadora)) { ?>
                <td colspan="4"><b><?php echo "Servi�o selecionado: </b>".$descricao_etiqueta." no valor de R$ ".str_replace(".", ",", $preco);?></td>
                    <td colspan="2"><b>Etiqueta: </b><input type="text" readOnly name="<?php echo 'etiqueta'.$i; ?>" value="<?php echo $etiqueta ?>"></td>
            <?php
            }
        ?>
    </tr>
<?php } ?>
    <!-- echo "<td colspan='2'><b>Peso Real:</b></td>";
    echo "<td colspan='1'><b>Peso C�bico:</b></td>";
    echo "<td colspan='2'><b>Vol.:</b></td>"; -->
    <tr>
        <td colspan='2'><b>Coleta:</b></td>
        <td colspan='1'><b>Conf.:</b></td>
        <td colspan='2'><b>NF:</b></td>
        <td rowspan='2'><b><?php

        $lista_embarques_string = implode(",", $lista_embarques);
        $lista_pecas_string     = implode(",", $lista_pecas);
        $lista_embarque_item_string = implode(",", $lista_embarque_item);

        echo "<a href=\"javascript:excluirItem('$PHP_SELF?desembarcar_todos=t&numero_embarque=$lista_embarques_string&excluir_embarque_peca=$lista_pecas_string&exclui_embarque_item=$lista_embarque_item_string&tipo_embarque=$quais_embarques','multiplos')\" class=noprint>Excluir Embarque</a>"; 

        ?>            
        </b></td>
    </tr>
<?php
    $sql = "SELECT  SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_cancelada) AS qtde ,
                    TO_CHAR (AVG (CURRENT_DATE - tbl_pedido.data::date)::numeric,'999') AS media_dias
            FROM  tbl_pedido
            JOIN  tbl_pedido_item USING (pedido)
            WHERE tbl_pedido.posto = $posto
            AND   tbl_pedido.distribuidor = $login_posto
            AND   tbl_pedido.status_pedido_posto IN (1,2,5,7,8,9,10,11,12)";
    //$resX = pg_query ($con,$sql);
    //$pendencia_total = pg_fetch_result ($resX,0,qtde);
    //$media_dias      = pg_fetch_result ($resX,0,media_dias);
    echo "<tr>";
    ?>
        <input type="text" hidden id="<?php echo 'valor_mercadoria'.$i; ?>" value="<?php echo number_format($valor_mercadorias,2,".","."); ?>"/>
    <?php
    echo "<td colspan='2'><b>Valor Mercadorias: </b> R$ " . number_format ($valor_mercadorias,2,",",".") . "</td>";
    //echo "<td colspan='5'><b>Pend�ncia Total: </b> " . number_format ($pendencia_total,0,",",".") . " pe�as (m�dia $media_dias dias) </td>";
    echo "<td colspan='3'><b>Transportadora:</b></td>";
    echo "</tr>";

    //For para tipos de envio dos Correios

    if (strlen($os_parcial)>0){
        echo "<tr>";
        echo "<td colspan='5' align='right'>";
        //echo "<a href='javascript:excluirEmbarque($embarque)' alt='Excluir Embarque'>Excluir Embarque";
        echo "<br><a href=\"javascript:desembarcarParcial('$PHP_SELF?numero_embarque=$embarque&desembarcar_parcial=1')\"> Desembarcar as OS�s parciais</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tfoot>";
    echo "</table>";

    $os_parcial = "";
    $total_embarques += 1;
}
echo "<p align='right'>Embs.: $total_embarques ; Acumulado Pe�as: $total_pecas <br></p>";
if ($btn_acao == "embarcar"){
    //echo "<a href='$PHP_SELF?maior_embarque=$embarque'>Embarcar at� aqui</a>";
}

if ($quais_embarques=="todos"){
    echo "<p>Selecione os embarques e clique em Gravar para liberar</p>";
    echo "<input type='button' name='btn_gravar' value='LIBERAR EMBARQUES' onClick='this.form.submit()'>";
}

if ($btn_acao == "embarcar"){
    echo "<p>Todos os embarques acima ser�o liberados. Clique em 'Continuar'</p>";
    echo "<input type='button' name='btn_gravar' value='Continuar >>>>' onClick='this.form.submit()'>";
}

echo "<input type='hidden' name='qtde_embarques' value='".$qtde_embarques."'>";
echo "</form>";


?>


<p>

<? #include "rodape.php"; ?>

<style>
@media print {
    td, th, a, a:hover {
        font-size: 10pt;
        font-family: verdana;
    }

    td {
        border-collapse: collapse;
    }

    .noprint {
        display:none;
    }

    .destaqueimpressao {
        font-size: 12pt;
    }

    .produtoimpressao {
        font-size: 9pt;
    }

    .embarquenumimpressao {
        display: inline;
        margin-bottom: 10px;
    }
}
</style>

</body>
</html>
