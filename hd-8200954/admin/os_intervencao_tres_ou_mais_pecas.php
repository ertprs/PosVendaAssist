<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

$ajax = $_POST['ajaxBtnAcao'];
if(!empty($ajax)){
    $os                  = trim($_POST['os']);
    $acao                = trim($_POST['acao']);
    $motivo_cancelamento = utf8_decode(trim($_POST['motivo_cancelamento']));
    $tipo = "Comunicado Inicial";

    $sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res))
        $nome_completo = pg_fetch_result($res, 0, 'nome_completo');

    $sql = "SELECT posto FROM tbl_os WHERE os = $os";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res))
        $posto = pg_fetch_result($res, 0, 'posto');

    $data = Date('d/m/Y H:i:s');

    if($acao == 'aprovar'){
        if($login_fabrica == 40 or $login_fabrica == 134 or $login_fabrica == 72){
            $msg = "OS Aprovada da Intervenção com três ou mais peças em: {$data}";
        }else if($login_fabrica == 114 || $login_fabrica == 104){
            $msg = "OS aprovada da intervenção de peças excedentes";
        }else{
            $msg = "OS Aprovada da Intervenção com cinco ou mais peças em: {$data}";
        }

        $sql = "INSERT INTO tbl_os_status (os, status_os, observacao, admin) VALUES ($os, 187, '{$msg}',$login_admin);";

        $res = pg_query($con, $sql);

        if ($telecontrol_distrib && !isset($novaTelaOs)) {
            if (!os_em_intervencao($os)) {

              $descricao_status_anterior = get_ultimo_status_os($os);
              
              atualiza_status_checkpoint($os, $descricao_status_anterior);

            }
        }

        if($login_fabrica == 40 or $login_fabrica == 134){
            $msg = "OS {$os} foi aprovada da intervenção com três ou mais peças em: {$data}";
        }else if($login_fabrica == 114 || $login_fabrica == 104){
            $msg = "OS {$os} aprovada da intervenção de peças excedentes";
        }else{
            $msg = "OS {$os} foi aprovada da intervenção com cinco ou mais peças em: {$data}";
        }

        $descricao = "OS {$os} FOI APROVADA DA INTERVENÇÃO";

        $sql = "INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, descricao, posto, ativo, obrigatorio_site) VALUES ('$msg', '$tipo', $login_fabrica, '$descricao', $posto, true, true);";
        $res = pg_query($con, $sql);

        echo 0;
    }

    if($acao == 'reprovar'){
        if(in_array($login_fabrica,array(40,134))){
            $msg = "OS Reprovada da intervenção com três ou mais peças em: {$data}<br>Motivo: {$motivo_cancelamento}";
        }else if($login_fabrica == 114 || $login_fabrica == 104){
            $msg = "OS reprovada da intervenção de peças excedentes <br>Motivo: {$motivo_cancelamento}";
        }else{
            $msg = "OS Reprovada da intervenção com cinco ou mais peças em: {$data}<br>Motivo: {$motivo_cancelamento}";
        }
        $sql = "INSERT INTO tbl_os_status (os, status_os, observacao, admin) VALUES ($os, 185, '{$msg}',$login_admin);";
        $res = pg_query($con, $sql);

        if(in_array($login_fabrica,array(40,134))){
            $msg = "OS {$os} foi reprovada da intervenção com três ou mais peças<br>Motivo: {$motivo_cancelamento}";
        }else if($login_fabrica == 114){
            $msg = "OS {$os} reprovada da intervenção de peças excedentes <br>Motivo: {$motivo_cancelamento}";
        }else{
            $msg = "OS {$os} foi reprovada da intervenção com cinco ou mais peças<br>Motivo: {$motivo_cancelamento}";
        }

        $descricao = "OS {$os} FOI REPROVADA DA INTERVENÇÃO";

        $sql = "INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, descricao, posto, ativo, obrigatorio_site) VALUES ('$msg', '$tipo', $login_fabrica, '$descricao', $posto, true, true);";
        $res = pg_query($con, $sql);

		if($login_fabrica == 40){
			$sql = "SELECT os FROM tbl_os_status WHERE os = $os AND status_os = 13";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 1){
				$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin)";
				$res = pg_query($con,$sql);
			}
		}

		if($login_fabrica == 104){
				$sql =  "UPDATE tbl_os_item SET
							servico_realizado          = tbl_servico_realizado.servico_realizado,
							admin                      = $login_admin
						FROM tbl_servico_realizado
						WHERE os_item IN (
								SELECT os_item
								FROM tbl_os
								JOIN tbl_os_produto USING(os)
								JOIN tbl_os_item    USING(os_produto)
								JOIN tbl_servico_realizado USING(servico_realizado)
								WHERE tbl_os.os           = $os
								AND tbl_os.fabrica        = $login_fabrica
								AND tbl_servico_realizado.gera_pedido
								AND tbl_os_item.pedido IS NULL
						)
						AND tbl_servico_realizado.fabrica = $login_fabrica
						AND tbl_servico_realizado.descricao ~* 'cancelado'";
				$res = pg_query($con,$sql);
		}
        echo 0;
    }
    if($acao == "trocar"){
        $sua_os=trim($_GET['trocar']);
        if (strlen($sua_os)>0){
            header("Location: os_cadastro.php?os=$os$str_filtro&osacao=trocar");
            exit();
        }
    }
    if($acao == "reparar"){

    }
    exit;
}


if (strlen(trim($_REQUEST["btn_acao"])) > 0) $btn_acao = trim($_REQUEST["btn_acao"]);
if (strlen(trim($_REQUEST["codigo_posto"])) > 0) $codigo_posto = trim($_REQUEST["codigo_posto"]);
if (strlen(trim($_REQUEST["posto_nome"])) > 0) $posto_nome = trim($_REQUEST["posto_nome"]);
if (strlen(trim($_REQUEST["os"])) > 0) $os = trim($_REQUEST["os"]);

if (strlen($btn_acao)>0 AND strlen($os) == 0){

    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];



    if(empty($data_inicial) OR empty($data_final)){
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }

    if(strlen($msg_erro)==0){

        if($aux_data_final > DATE('Y-m-d')){
            $aux_data_final = DATE('Y-m-d');
            $_REQUEST["data_final"] = Date('d/m/Y');
        }

        if($aux_data_final < $aux_data_inicial){
            $msg_erro = "Data Inválida";
        }else{
            $data_inicial = $aux_data_inicial." 00:00:00";
            $data_final = $aux_data_final." 23:59:59";
        }
    }

    if(strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0 && strlen($msg_erro) == 0 && $login_fabrica != 122){
        $sql = "SELECT '$aux_data_inicial'::date + interval '31 days' > '$aux_data_final'";
        $res = pg_query($con,$sql);
        $periodo = pg_fetch_result($res,0,0);
        if($periodo == 'f')
            $msg_erro = "Data Inválida - Período maior que um mês";
    }

    if(!empty($codigo_posto) AND strlen($msg_erro) == 0){
        $sql = "SELECT tbl_posto_fabrica.posto, nome FROM tbl_posto_fabrica JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto WHERE codigo_posto = '{$codigo_posto}' AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res,0,'posto');
            $where_posto = " AND tbl_os.posto = {$posto}";
        }else{
            $msg_erro = "Posto Inválido!";
        }
    }
}

$layout_menu = "auditoria";

if(in_array($login_fabrica,array(40,134,72))){
    $titulo = "Relatório de OS Intervenção com três ou mais peças";
    $title  = "RELATÓRIO DE OS INTERVENÇÃO COM TRÊS OU MAIS PEÇAS";
}else if($login_fabrica == 114){
    $titulo = "Relatório de OS Intervenção com duas ou mais peças";
    $title  = "RELATÓRIO DE OS INTERVENÇÃO COM DUAS OU MAIS PEÇAS";
}else if($login_fabrica == 104){
    $titulo = "Relatório de OS Intervenção de peças excedentes";
    $title  = "RELATÓRIO DE OS INTERVENÇÃO DE PEÇAS EXCEDENTES";
}else{
    $titulo = "Relatório de OS Intervenção com cinco ou mais peças";
    $title  = "RELATÓRIO DE OS INTERVENÇÃO COM CINCO OU MAIS PEÇAS";
}


include 'cabecalho.php';

include "javascript_calendario_new.php";
include "../js/js_css.php";
?>
<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
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
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>
<?

echo "<form name='frm_consulta' method='post' action='$PHP_SELF'>";
echo "<table border='0' cellspacing='2' cellpadding='2' align='center' class='formulario' width='700'>";
if (strlen($msg_erro) > 0) {
	echo "<tr class='msg_erro'><td colspan='5'>".$msg_erro."</td></tr>";
}
	echo "<tr class='titulo_tabela'>";
		echo "<td colspan='5'>Parâmetros de Pesquisa</td>";
	echo "</tr>";
    echo "<tr>";
        echo "<td width='100'>&nbsp;</td>";
        echo "<td align='left'>
                Número da OS<br>
                <input type='text' name='os' id='os' size='11' maxlength='10' value='{$_POST['os']}' class='frm' tabindex='2'>
             </td>";
        echo "<td align='left'>&nbsp; </td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td width='100'>&nbsp;</td>";
        echo "<td align='left'>
                Data Inicial<br>
                <input type='text' name='data_inicial' id='data_inicial' size='15' maxlength='10' value='{$_POST['data_inicial']}' class='frm' tabindex='2'>
             </td>";
        echo "<td align='left'>
                Data Final<br>
                <input type='text' name='data_final' id='data_final' size='15' maxlength='10' value='{$_POST['data_final']}' class='frm' tabindex='3'>
              </td>";
    echo "</tr>";
    echo "<tr >";
        echo "<td width='30'>&nbsp;</td>";
        echo "<td align='left'>";
            echo "Código Posto <br><input type='text' name='codigo_posto' size='15' value='$codigo_posto' class='frm'>";
            echo "<img border='0' src='imagens/lupa.png' style='cursor: hand;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto(document.frm_consulta.codigo_posto, \"codigo\")'>";
        echo "</td>";
        echo "<td align='left'>";
            echo "Nome Posto<br><input type='text' name='posto_nome' size='30' value='$posto_nome' class='frm'>";
            echo "<img border='0' src='imagens/lupa.png' style='cursor: hand;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: pesquisaPosto (document.frm_consulta.posto_nome, \"nome\")'>";
        echo "</td>";
    echo "</tr>";

    echo "<tr>";
        echo "<td colspan='3' align='center'><br /><input type='submit' name='btn_acao' value='Pesquisar'><br><br></td>";
    echo "</tr>";

echo "</table>";
echo "</form>";

if (strlen($btn_acao)>0 and strlen($msg_erro)==0){
    if(!empty($data_inicial) AND !empty($data_final))
        $where_data .= " AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' ";

    if(!empty($os)){
        $where_os = "AND tbl_os.os = {$os}";
        $where_posto = null;
        $where_data = null;
    }

    $sql = "SELECT
                MAX(tbl_os_status.os_status) AS os_status,
                tbl_os.os
                INTO TEMP tmp_os_intervencao_mais_peca_$login_admin
            FROM tbl_os
                JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND tbl_os_status.fabrica_status = $login_fabrica
            WHERE
                tbl_os.fabrica = $login_fabrica
                {$where_posto}
                {$where_data}
                {$where_os}
                AND tbl_os_status.status_os IN (118,185,187)
            GROUP BY
                tbl_os.os;";

	$sql .= "SELECT
                tbl_os_status.status_os,
                tbl_posto_fabrica.codigo_posto,
			    tbl_os.os,
                tbl_posto.nome,
			    tbl_os.sua_os::text ,
			    TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
     			tbl_produto.descricao
	    	FROM tbl_os
                JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os_status.fabrica_status = $login_fabrica
                JOIN tmp_os_intervencao_mais_peca_$login_admin ON tmp_os_intervencao_mais_peca_$login_admin.os_status = tbl_os_status.os_status
			    JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto and tbl_produto.fabrica_i=$login_fabrica
			WHERE
                tbl_os.fabrica = $login_fabrica
			    AND tbl_os.excluida IS NOT TRUE
                AND tbl_os_status.status_os IN (118)
                AND tbl_os.troca_garantia IS NOT TRUE
             ORDER BY tbl_posto.nome, tbl_produto.descricao, tbl_os.sua_os;";
			#echo nl2br($sql);
			#exit;
	$res = pg_query ($con,$sql);

	if( pg_num_rows ($res) > 0){
        echo "<br />";
        echo "<table id='tbl_os_reprova' border='0' cellspacing='1' cellpadding='4' align='center' class='formulario' style='width: 700px; border: 1px solid #596D9B; display: none; '>";
            echo "<tr class='titulo_tabela'>";
                echo "<th colspan='2' style='width: 700px;'></th>";
            echo "</tr>";
            echo "<tr>";
                echo "<td style='text-align: left'>
                        <input type='hidden' id='os_reprova' name='os_reprova' value='' />
                        <input type='text' id='os_reprova_justificativa' name='os_reprova_justificativa' value='' class='frm' style='width: 590px' />
                      </td>";
                echo "<td style='text-align: center'>
                        <input type='submit' name='btn_acao' value=' Confirmar ' id='btn_reprovacao' />
                      </td>";
            echo "</tr>";
        echo "</table>";


        echo "<br />";
        echo "<table border='0' cellpadding='4' cellspacing='1' align='center' class='tabela' width='700'>";
             echo "<tr class='titulo_coluna'>";
                echo "<td><B>OS</B></td>";
                echo "<td><B>Abertura</B></td>";
                echo "<td><B>Nome do Posto</B></td>";
                echo "<td><B>Produto</B></td>";
                echo "<td width='165px'><B>Ação</B></td>";
            echo "</tr>";

            for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
                $os           = pg_result ($res,$i,os);
                $abertura     = pg_result ($res,$i,abertura);
                $sua_os       = pg_result ($res,$i,sua_os);
                $codigo_posto = pg_result ($res,$i,codigo_posto);
                $nome         = pg_result ($res,$i,nome);
                $descricao    = pg_result ($res,$i,descricao);
                $estado    = pg_result ($res,$i,estado);

                $cor = "#F7F5F0";
                if ($i % 2 == 0) $cor = '#F1F4FA';
                echo "<tr bgcolor='$cor'>";
                    echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
                    echo "<td>$abertura</td>";
                    echo "<td align='left' nowrap>$codigo_posto - $nome</td>";
                    echo "<td align='left' nowrap>$descricao</td>";
                    echo "<td align='center' id='{$os}' class='btnAction' nowrap>";
                    if($login_fabrica == 114){
?>
                    <input type='button' name='trocar' value=' Trocar ' />
<?
                    }
                    echo "      <input type='button' name='aprovar' value=' Aprovar ' />
                                <input type='button' name='reprovar' value=' Reprovar ' />
                          </td>";
                    echo "</td>";
                echo "</tr>";
            }
        echo "</table>";

	}else{
	    echo "<center>Nenhum resultado encontrado</center>";
	}
}
?>
<link rel="stylesheet" type="text/css" href="../plugins/jquery/apprise/apprise.min.css" media="all">
<script src="../plugins/jquery/apprise/apprise-1.5.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type='text/javascript'>
    $(document).ready(function(){
        Shadowbox.init();

        $( "#data_inicial" ).datepick({startDate : "01/01/2000"});
        $( "#data_inicial" ).mask("99/99/9999");

        $( "#data_final" ).datepick({startDate : "01/01/2000"});
        $( "#data_final" ).mask("99/99/9999");


        $(".btnAction input").click(function(){
            var acao = $(this).attr('name');
            var os   = $(this).parent().attr('id');
            var motivo_reprova;
            if(acao == 'aprovar'){
                var pergunta = "Deseja realmente aprovar a "+os+"?";
                if(confirm(pergunta)){
                    $("#tbl_os_reprova").fadeOut(1000);
                    $.ajax({
                        url: "<?php echo $PHP_SELF;?>",
                        type: "POST",
                        data: "ajaxBtnAcao=AprovaReprova&os="+os+"&acao="+acao,
                        success: function(retorno){
                            if(retorno == 0){
                                $("#"+os).html("<div style='text-align: center; color: #0A7525'>Aprovado!</div>");
                                $("#"+os).parent().fadeOut(2000);
                            }
                        }
                    });
                }
            }


            if(acao == 'reprovar') {
                var os   = $(this).parent().attr('id');
                $("#os_reprova").val('');
                $("#os_reprova_justificativa").val('');

                if(os.length > 0){
                    $("#os_reprova").val(os);
                    $("#tbl_os_reprova th").html('Informe o motivo de cancelamento da OS: '+os);

                    $("#tbl_os_reprova").css('display','block');

                    $("#os").focus();
                    $("#os_reprova_justificativa").focus();
                }
            }
            if(acao == "trocar"){
                var pergunta = "Deseja realizar a troca deste produto pela Fábrica? A OS "+os+" será liberada.";
                if(confirm(pergunta)){
                    $("#tbl_os_reprova").fadeOut(1000);
                    window.open('os_cadastro.php?os='+os+'&btnacao=filtrar&osacao=trocar');
                }
            }
        });

        $("#btn_reprovacao").click(function(){

            var os              = $("#os_reprova").val();
            var justificativa   = $("#os_reprova_justificativa").val();
            var acao            = 'reprovar';

           if(os.length > 0 && justificativa.length > 0 ){
                $.ajax({
                    url: "<?php echo $PHP_SELF;?>",
                    type: "POST",
                    data: "ajaxBtnAcao=AprovaReprova&os="+os+"&acao="+acao+"&motivo_cancelamento="+justificativa,
                    success: function(retorno) {
                        if(retorno == 0){
                            $("#"+os).html("<div style='text-align: center; color: #F00'>Reprovado!</div>");
                            $("#"+os).parent().fadeOut(2000);
                            $("#tbl_os_reprova").fadeOut(1000);
                        }
                    }
                });
           }else{
                alert('Informe o motivo de cancelamento da OS: '+os);
           }
        });

    });

    function pesquisaPosto(campo,tipo){
        var campo = campo.value;

        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
                content:    "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                player:     "iframe",
                title:      "Pesquisa Posto",
                width:      800,
                height:     500
            });
        }else
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }


    function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('posto_nome',nome);
    }

    function gravaDados(name, valor){
        try{
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }
</script>
<?php
include "rodape.php";


?>

