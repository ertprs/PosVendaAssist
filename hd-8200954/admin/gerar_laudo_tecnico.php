<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


if($_POST){
	$motivo_troca = $_POST['motivo_troca'];

	if(empty($motivo_troca)){
		$msg_erro = "Informe o motivo da troca";
	}
    
    $produto = $_POST['produto'];

    if($motivo_troca == "produto_com_listabasica"){
        $sql = "SELECT * from tbl_lista_basica where produto = $produto and fabrica = $login_fabrica ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            $peca_listabasica = $_POST["peca_listabasica"];

            foreach ($peca_listabasica as $linha) {
                $observacao .= " $linha; ";
            }
        }else{
            $msg_erro .= "Produto sem lista básica, escolha outro motivo. ";
        }        
        $titulo = "Peça não consta na lista básica";
    }

    if($motivo_troca == "sem_lista_basica"){

        $sql = "SELECT * from tbl_lista_basica where produto = $produto and fabrica = $login_fabrica ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            $msg_erro .= "Produto com lista básica, escolha outro motivo. ";
        }
        $titulo = "Produto sem lista básica";

    }
	
	if($motivo_troca == "autorizada" OR $motivo_troca == "fabrica"){
		$peca_faltante = $_POST['peca_faltante'];
		$titulo = "Falta de peça na AT";

		if($motivo_troca == "fabrica"){
			$pedido = fnc_so_numeros($_POST['pedido']);

			if(empty($pedido)){
				$msg_erro = "Informe o número do pedido";
			}

			$observacao = "Referente ao pedido : $pedido <br>";
			$titulo = "Falta de peça na fábrica";
		}

		if(count($peca_faltante) == 0){
			$msg_erro = "Informe a peça";
		}else{
			for($i =0;$i<count($peca_faltante);$i++) {

				$sql = "SELECT peca,referencia,descricao
							FROM tbl_peca 
							WHERE referencia = '{$peca_faltante[$i]}' 
							AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$observacao .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
				}else{
					$msg_erro = "Peça {$peca_faltante[$i]} não encontrada";
				}
			}
		}
	}else if($motivo_troca == "outros"){
		$observacao = $_POST['obs'];
		$titulo = "Outros";
		if(empty($observacao)){
			$msg_erro = "Informe o motivo";
		}
	}

	if(empty($msg_erro)){
        $sqlOs = "SELECT COUNT(1) AS num_os FROM tbl_laudo_tecnico_os WHERE os = $os";
        $resOs = pg_query($con,$sqlOs);
        $numOs = pg_fetch_result($resOs,0,num_os);
        if($numOs == 0){
            $sql = "SELECT MAX(ordem) AS ordem FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $ordem = pg_fetch_result($res, 0, 'ordem');
                $ordem = ($ordem > 0) ? $ordem + 1 : 338751;
            }else{
                $ordem = 338751;
            }
            pg_query($con,'BEGIN TRANSACTION');
            $sql = "INSERT INTO tbl_laudo_tecnico_os(
                                    titulo,
                                    os,
                                    observacao,
                                    fabrica,
                                    ordem) VALUES(
                                    '$titulo',
                                    $os,
                                    '$observacao',
                                    $login_fabrica,
                                    $ordem) RETURNING laudo_tecnico_os";
            $res = pg_query($con,$sql);
            if(!pg_last_error($con)){
                pg_query($con,'COMMIT TRANSACTION');
                $laudo = pg_fetch_result($res, 0, 'laudo_tecnico_os');
                header("Location: gerar_laudo_tecnico.php?os=$os&laudo=$laudo");    
            }else{
                pg_query($con,"ROLLBACK TRANSACTION");
                $msg_erro = "Não foi possível gravar o laudo";
            }
        }else{
            $msg_erro = "Houve a tentativa de duplicação desse laudo!";
        }
	}
}


include "cabecalho.php";
?>

<script src="https://code.jquery.com/jquery-latest.min.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript">

	$().ready(function() {
		Shadowbox.init();

		$('form').submit(function(){
			$('#peca_faltante option').attr('selected','selected');

            $('#peca_listabasica option').each(function(){
                $(this).attr('selected','selected');
            });
		});

        $("#sem_lista_basica").click(function(){
            $("#fora_listabasica").hide('slow');
            $("#linha_peca").hide('slow');
        });        

		informaPeca();
	});

	function informaPeca(){
		var motivo_troca = $("input[name='motivo_troca']:checked").val();
	
		if(motivo_troca == "fabrica"){			
			$("#texto_obs").hide('slow');
			$("#linha_peca").show('slow');
			$("#num_pedido").show('slow');
            $("#fora_listabasica").hide('slow');			
		}else if(motivo_troca == "outros"){			
			$("#linha_peca").hide('slow');
			$("#texto_obs").show('slow');			
            $("#fora_listabasica").hide('slow');            
		}else if(motivo_troca == "autorizada"){
			$("#linha_peca").show('slow');
			$("#num_pedido").hide('slow');
			$("#texto_obs").hide('slow');
            $("#fora_listabasica").hide('slow');
		}
	}

	function fnc_pesquisa_peca_2 (referencia, descricao) {

		if (referencia.length > 2 || descricao.length > 2) {
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?referencia=" + referencia + "&descricao=" + descricao,
				player:	"iframe",
				title:		"Pesquisa Peça",
				width:	800,
				height:	500
			});
		}
		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa");
		}
	}

	function retorna_dados_peca (peca, referencia, descricao, ipi, origem, estoque, unidade, ativo, posicao)
	{
		gravaDados("peca_referencia", referencia);
		gravaDados("peca_descricao", descricao);
	}


	function gravaDados(name, valor){
	    try {
	        $("input[name="+name+"]").val(valor);
	    } catch(err){
	        return false;
	    }
	}

	function addItPeca() {
		if ($('#peca_referencia').val()=='') return false;
		if ($('#peca_descricao').val()=='') return false;
		var ref_peca  = $('#peca_referencia').val();
		var desc_peca = $('#peca_descricao').val();
		$('#peca_faltante').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");

		if($('.select').length ==0) {
			$('#peca_faltante').addClass('select');
		}

		$('#peca_referencia').val("").focus();
		$('#peca_descricao').val("");
	}

	function delItPeca() {
		$('#peca_faltante option:selected').remove();
		if($('.select').length ==0) {
			$('#peca_faltante').addClass('select');
		}

	}

	function imprimir(via){
		
		var laudo = $("#laudo_tec").val();
		var os = $("#os").val();
		
		window.open('gerar_laudo_tecnico.inc.php?laudo='+laudo+'&os='+os+'&print=1&via='+via);		
		
	}

    function verifica_lista_basica(){
        $("#linha_peca").hide('slow');
        $("#texto_obs").hide('slow');
        $("#fora_listabasica").show('slow');
    }

    function add_peca2(){
        var desc_peca = $('#descricao_peca').val();
        $('#peca_listabasica').append("<option value='"+desc_peca+"'>" + desc_peca +"</option>");

        $('#descricao_peca').val("").focus();

    }

    function delPeca2() {
        $('#peca_listabasica option:selected').remove();
        if($('.select').length ==0) {
            $('#peca_listabasica').addClass('select');
        }
    }


</script>

<?php

include "gerar_laudo_tecnico.inc.php";

include "rodape.php";
?>
