<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
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
			$revenda = $_POST['revenda'];
			$produto = $_POST['produto'];
			$nota_fiscal = $_POST['nota_fiscal'];

			if(empty($pedido)){
				$msg_erro = "Informe o número do pedido";
			}else{
				$sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = $login_fabrica AND posto = $login_posto AND seu_pedido like '%$pedido'";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) == 0){
					$msg_erro = "Pedido não encontrado";
				}

				$sql = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto
							FROM tbl_os
							JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE tbl_os.fabrica 	= $login_fabrica
							AND tbl_os.posto     	= $login_posto
							AND tbl_os.revenda 		= $revenda
							AND tbl_os.nota_fiscal 	= '$nota_fiscal'
							AND tbl_os.produto 		= $produto
							AND tbl_os.finalizada is null
							AND tbl_os.excluida not true";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$sua_os 	= pg_fetch_result($res, 0, 'sua_os');
					$cod_posto 	= pg_fetch_result($res, 0, 'codigo_posto');

					$msg_erro = "ATENÇÃO! EXISTE UMA OS ({$cod_posto}{$sua_os}) REINCIDENTE PARA ESSE MESMO PRODUTO . FAVOR VERIFICAR COM O SUPORTE DA SUA REGIÃO PARA QUE SEJA REALIZADA A EXCLUSÃO DA O.S ANTERIOR SE HOUVER DUPLICIDADE, EVITANDO ASSIM QUALQUER TRANSTORNO COM A NOSSA AUDITORIA";
				}
			}

			$observacao = "Peças que faltaram referente ao pedido : $pedido <br>";
			$titulo = "Falta de peça na fábrica";
		}

		if(count($peca_faltante) == 0){
			$msg_erro = "Informe a peça";
		}else{
			for($i =0;$i<count($peca_faltante);$i++) {

				$sql = "SELECT tbl_peca.peca,tbl_peca.referencia,tbl_peca.descricao
							FROM tbl_peca
                            JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca
                            WHERE tbl_peca.referencia = '{$peca_faltante[$i]}'
							AND tbl_lista_basica.produto = $produto
                            AND tbl_peca.fabrica = $login_fabrica";
				$res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
					$observacao .="<br>".pg_fetch_result($res,0,referencia)." | ". pg_fetch_result($res,0,descricao);
				}else{
					$msg_erro = "Peça {$peca_faltante[$i]} não encontrada, Verifique a lista básica do produto.";
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
            pg_query($con,'BEGIN TRANSACTION');
            $sql = "SELECT  tbl_marca.nome

                    FROM    tbl_os
                    JOIN    tbl_produto ON  tbl_os.produto          = tbl_produto.produto
                                        AND tbl_produto.fabrica_i   = $login_fabrica
                    JOIN    tbl_marca   ON  tbl_marca.marca         = tbl_produto.marca
                    WHERE   tbl_os.os           = $os
                    AND     tbl_os.satisfacao   IS TRUE
                    AND     tbl_os.fabrica      = $login_fabrica";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $marca = pg_fetch_result($res, 0, 'nome');
                $solucao = ($marca == 'Dewalt') ? 471 : 486;

                $sqlU = "UPDATE tbl_os SET solucao_os = $solucao WHERE os = $os";
                $resU = pg_query($con,$sqlU);
            }

            $sql = "SELECT MAX(ordem) AS ordem FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $ordem = pg_fetch_result($res, 0, 'ordem');
                $ordem = ($ordem > 0) ? $ordem + 1 : 338751;
            }else{
                $ordem = 338751;
            }
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
                $laudo = pg_fetch_result($res, 0, 'laudo_tecnico_os');

                $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_DATE, finalizada = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
                $res = pg_query($con,$sql);

                if(!pg_last_error($con)){
                    pg_query($con,'COMMIT TRANSACTION');
                    header("Location: gerar_laudo_tecnico.php?os=$os&laudo=$laudo");
                }
            }else{
                $msg_erro = "Não foi possível gravar o laudo.";
                pg_query($con,'ROLLBACK TRANSACTION');
            }
        } else {
            $msg_erro = "Houve a tentativa de duplicação desse laudo!";
        }
    }
}


include "cabecalho.php";
?>

<script src="admin/js/jquery-1.8.3.min.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript">

	$().ready(function() {
		Shadowbox.init();

		$("#gravar").click(function(){
			$('#peca_faltante option').each(function(){
				$(this).attr('selected','selected');
			});

            $('#peca_listabasica option').each(function(){
                $(this).attr('selected','selected');
            });
			if(confirm('A OS será finalizada automáticamente ao gravar o laudo.')){
				$('form').submit();
			}
		});

        $("#sem_lista_basica").click(function(){
            $("#fora_listabasica").hide('slow');
            $("#linha_peca").hide('slow');
        });

		informaPeca();
	});

    var produto;
var referencia;
var descricao;
var preco;
var qtde;
var qtde_fotos;
var serial_lcd;
var qtde_estoque;
var tela_pedido = false;



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

	function fnc_pesquisa_peca_2 (referencia, descricao,produto) {

		if (referencia.length > 2 || descricao.length > 2) {
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?referencia=" + referencia + "&descricao=" + descricao + "&produto=" + produto,
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
