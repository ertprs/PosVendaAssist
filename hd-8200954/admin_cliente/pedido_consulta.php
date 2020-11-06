<?php

$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    include_once '../includes/funcoes.php';
    include '../autentica_admin.php';
    include "../monitora.php";
}

    if ($login_fabrica == 160 or $replica_einhell) {
        $dash = $_GET["dash"];
        
        if (!empty($dash)) {
            include_once "cabecalho_new.php";
            
            $plugins = array(
                "autocomplete",
                "datepicker",
                "shadowbox",
                "mask",
                "multiselect",
                "dataTable",
                "alphanumeric"
            );

            include_once("plugin_loader.php");
            $colspan = " colspan=9 ";

            ?>
                <script>
                     $(function() {
                        var table = new Object();
                        table['table'] = '#tabela_pedidos';
                        table['type'] = 'full';
                        $.dataTableLoad(table);
                    });
                </script>
            <?php
        }
    }
?>
<p>
<script type="text/javascript">

function selecionarTudo(){
    $('input[@rel=imprimir]').each( function (){
        this.checked = !this.checked;
    });
}

function imprimirSelecionados(){
    var qtde_selecionados = 0;
    var linhas_seleciondas = "";
    $('input[@rel=imprimir]:checked').each( function (){
        if (this.checked){
            linhas_seleciondas = this.value+", "+linhas_seleciondas;
            qtde_selecionados++;
        }
    });

    if (qtde_selecionados>0){
        janela = window.open('pedido_print_selecao.php?lista_pedido='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
    }
}

<?php
if ($login_fabrica == 151) {
?>
window.addEventListener("load", function() {
	[].forEach.call(document.querySelectorAll("button.exportar_pedido"), function(e) {
		e.addEventListener("click", function() {
			var pedido = e.dataset.pedido;

			$.ajax({
				async: false,
				url: "os_cadastro_unico/fabricas/<?=$login_fabrica?>/ajax_exporta_pedido_manual.php",
				type: "get",
				dataType:"JSON",
				data: { exporta_pedido_manual: true, pedido: pedido },
				beforeSend: function() {
					e.disabled = true;
					e.innerHTML = "Exportando...";
				}
            })
            .done(function(data) {

                if (data.erro) {
                    alert(data.erro);
                    e.disabled = false;
                    e.innerHTML = "Exportar";
                } else if (data.SdErro && data.SdErro.ErroCod != 0)  {
                    alert(data.SdErro.ErroDesc);
                    e.disabled = false;
                    e.innerHTML = "Exportar";
                } else {
                    e.innerHTML = "Exportado";
                }
			});
		});
	});
});
<?php
}
?>

</script>
<?

    if(in_array($login_fabrica, array(74, 168))){
        $sql = "
            SELECT  tbl_admin.callcenter_supervisor, JSON_FIELD('libera_pedido',parametros_adicionais) AS libera_pedido
            FROM    tbl_admin
            WHERE   tbl_admin.admin = $login_admin
        ";
        $res = pg_query($con,$sql);
        $callcenter_supervisor = pg_fetch_result($res,0,callcenter_supervisor);
        if ($login_fabrica == 168) {
            $libera_pedido = pg_fetch_result($res,0, libera_pedido);
        }
    }

    $msg_erro = "";

    $msg = "";

    if($_POST['chk_opt'])    $chk        = $_POST['chk_opt'];
    if($login_fabrica == 168){
        if($_POST['pdpp'])    $pdpp        = $_POST['pdpp'];
    }

    if ($login_fabrica == 1){
        if($_POST['chk_opt10'])          $chk10                              = $_POST['chk_opt10'];
        if($_POST['chk_opt11'])          $chk11                              = $_POST['chk_opt11'];
        if($_POST['chk_opt12'])          $chk12                              = $_POST['chk_opt12'];
        if($_POST['chk_opt14'])          $chk14                              = $_POST['chk_opt14'];
        if($_POST["codigo"])             $codigo_representante               = trim($_POST["codigo"]);
        if($_POST["nome"])               $nome_representante                 = trim($_POST["nome"]);
        if($_POST["representante"])      $representante_representante        = trim($_POST["representante"]);

    }

    if($_REQUEST['estado_posto_autorizado']) $estado_posto_autorizado = $_REQUEST['estado_posto_autorizado'];
    if($_POST['tipo_pedido']) $tipo_pedido = $_POST['tipo_pedido'];
    if($_POST['tipo'])        $tipo        = $_POST['tipo'];
    if($_POST['pedido_status']) $pedido_status = $_POST['pedido_status'];
    if($_POST["data_inicial_01"])        $data_inicial_01    = trim($_POST["data_inicial_01"]);
    if($_POST["data_final_01"])          $data_final_01      = trim($_POST["data_final_01"]);
    if($_POST['codigo_posto'])           $codigo_posto       = trim($_POST['codigo_posto']);
    if($_POST["produto_referencia"])     $produto_referencia = trim($_POST["produto_referencia"]);

    if($_POST["produto_nome"])           $produto_nome       = trim($_POST["produto_nome"]);
    if($_POST["numero_os"])              $numero_os          = trim($_POST["numero_os"]);
    if($_POST["numero_pedido"])          $numero_pedido      = trim($_POST["numero_pedido"]);
    if($_POST["numero_nf"])              $numero_nf          = trim($_POST["numero_nf"]);
    if($_POST["nome_revenda"])           $nome_revenda       = trim($_POST["nome_revenda"]);
    if($_POST["cnpj_revenda"])           $cnpj_revenda       = trim($_POST["cnpj_revenda"]);
    if($_POST["peca_referencia"])        $peca_referencia_consulta      = trim($_POST["peca_referencia"]);
    if($_POST["peca_descricao"])        $peca_descricao_consulta      = trim($_POST["peca_descricao"]);

    if($_POST["status_pedido"])          $status_pedido      = trim($_POST["status_pedido"]); //HD 49364
    if($_POST["status_pedido_not_in"])          $status_pedido_not_in      = trim($_POST["status_pedido_not_in"]); //HD 49364
    if($_POST["estado_pedido"])          $estado_pedido      = trim($_POST["estado_pedido"]); //HD 49364

    if($_POST["estado"])             $estado             = trim($_POST["estado"]);

    if($login_fabrica == 120){ //hd_chamado=2765193
        if($_POST['linha_produto'])      $linha_produto       = trim($_POST['linha_produto']);
    }

    if (in_array($login_fabrica, [167, 203]) && !empty($numero_pedido)) {
        $sql_data_pedido = "SELECT to_char(data, 'dd/mm/yyyy') AS data_pedido_inicial FROM tbl_pedido WHERE pedido = $numero_pedido AND fabrica = $login_fabrica";
        $res_data_pedido = pg_query($con, $sql_data_pedido);
        if (pg_num_rows($res_data_pedido) > 0) {
            $data_pedido_inicial = pg_fetch_result($res_data_pedido, 0, 'data_pedido_inicial');
            $data_corte = "01/04/2019";

            if (!verifica_data_corte($data_corte, $data_pedido_inicial)) {
                $msg_erro = "Data do pedido inferior a data limite para pesquisa";
            }
        }
    }

    if ($login_fabrica == 101) {
        $destinatario_troca = filter_input(INPUT_POST,'destinatario_troca');
        $joinTroca = "";
        if (!empty($tipo)) {
            $sql = "SELECT codigo FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo";
            $res = pg_query($con,$sql);

            $tipo_troca = pg_fetch_result($res,0,codigo);
// echo $destinaratio_troca;
            if ($tipo_troca == "TRO" && !empty($destinatario_troca)) {
//             echo "HEY!!";
                $destTroca = ($destinatario_troca == "posto") ? "NOT TRUE" : "TRUE";

                $joinTroca = "
                    JOIN    tbl_os_item     ON  tbl_os_item.pedido              = tbl_pedido.pedido
                    JOIN    tbl_os_produto  ON  tbl_os_produto.os_produto       = tbl_os_item.os_produto
                    JOIN    tbl_os_troca    ON  tbl_os_troca.os                 = tbl_os_produto.os
                                            AND tbl_os_troca.fabric             = $login_fabrica
                                            AND tbl_os_troca.envio_consumidor   IS $destTroca
                ";
            }
        }
    }

    if($login_fabrica == 153){ //HD-2921051
        if($_POST["tipo_posto"]) $tipo_posto = trim($_POST["tipo_posto"]);
    }

    #$body_onload = "javascript: document.frm_os.condicao.focus()";

    if(in_array($login_fabrica, array(138, 163))){

        include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
        $tDocs   = new TDocs($con, $login_fabrica);

    }

?>
<?

    if ($login_fabrica==10 OR $login_fabrica == 7){
        $sql_left = " LEFT ";
    }

    if (strlen($peca_referencia_consulta) > 0 and ($login_fabrica <> 6) && (!in_array($login_fabrica, array(40, 151, 153)))) {
        $join_chk8 = "
            JOIN tbl_pedido_item on tbl_pedido.pedido = tbl_pedido_item.pedido";
    }

    if ($login_fabrica == 146) {
        $column_marca = ",tbl_marca.nome AS marca";
        $join_marca = " JOIN tbl_marca on tbl_marca.marca = tbl_pedido.visita_obs::integer";
    }

    if ($login_fabrica == 163) {
        $column_transportadora = ",tbl_pedido.transportadora,
                tbl_transportadora.cnpj AS transportadora_cnpj,
                tbl_transportadora.nome AS transportadora_nome";
        $join_transportadora = " LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora
            LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = 163 and tbl_transportadora.transportadora = tbl_transportadora_fabrica.transportadora ";
    }



    /**
     * Código SQL para relatório detalhado
     */
    if (in_array($login_fabrica, array(40, 151, 153))) {
        $joinAdicionais = array(
            "INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido",
            "INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = tbl_pedido.fabrica"
        );

        $groupBy = array(
            "tbl_pedido.pedido",
            "tbl_pedido.seu_pedido",
            "tbl_pedido.pedido_blackedecker",
            "tbl_pedido.pedido_cliente",
            "posto_nome",
            "tbl_posto_fabrica.codigo_posto",
            "tbl_pedido.fabrica",
            "tbl_pedido.pedido_cliente",
            "tbl_pedido.data",
            "tbl_pedido.entrega",
            "recebido_posto",
            "descricao_tipo_pedido",
            "tbl_status_pedido.status_pedido",
            "descricao_status_pedido",
            "tbl_pedido.exportado",
            "tbl_pedido.exportado_manual",
            "tbl_pedido.status_fabricante",
            "tbl_pedido.origem_cliente",
            "tbl_pedido.pedido_os",
            "tbl_pedido.total",
            "tbl_admin.login",
            "tbl_condicao.descricao",
            "tbl_fabrica.altera_pedido_exportado",
            "item_cancelado"
        );

        if($login_fabrica == 151){
            $groupBy = array_merge($groupBy, array(
                "tbl_os_campo_extra.os_bloqueada", "tbl_os.finalizada"
            ));
        }

        if ($login_fabrica == 153) {
            $joinAdicionais = array_merge($joinAdicionais, array(
                "INNER JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_posto_fabrica.fabrica = $login_fabrica"
            ));

            $groupBy = array_merge($groupBy, array(
                "tbl_posto.cnpj",
                "tbl_tipo_posto.descricao"
            ));
            //hd_chamado=3024788 Alterado para tbl_pedido_item.qtde_faturada_distribuidor
            $colunas_itens = array(
                "tbl_peca.referencia",
                "tbl_peca.descricao",
                "tbl_pedido_item.qtde_faturada_distribuidor",
                "(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_faturada_distribuidor))"
            );
		} else if ($login_fabrica == 151) {
			$data_inicial     = $data_inicial_01;

			list($di, $mi, $yi) = explode("/", $data_inicial_01);

			$aux_data_inicial = "$yi-$mi-$di";
			if(!empty($aux_data_inicial) and checkdate($mi,$di,$yi)) {
				$cond_data = " AND data > '$aux_data_inicial 00:00:00' ";
			}

			if(!empty($numero_pedido)) {
				$sqlp = "SELECT data FROM tbl_pedido where pedido = $numero_pedido ";
				$resp = pg_query($con,$sqlp);
				if(pg_num_rows($resp) > 0) {
					$data_pedido = pg_fetch_result($resp, 0, 'data');

					$cond_data = " AND data > '$data_pedido' ";
				}
			}
            $leftJoinOS = "
                LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido
                LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            ";


            $left_campos_extra = " LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os and tbl_os_campo_extra.fabrica = $login_fabrica ";
            $campo_os_bloqueada = " tbl_os_campo_extra.os_bloqueada,  ";
            $campo_os_finalizada = "tbl_os.finalizada, ";


			$sql_temp = "select  distinct faturamento , to_char(data, 'DD/MM/YYYY') as data , situacao,tbl_faturamento_item.pedido into temp entrega_$login_admin  from tbl_faturamento_correio JOIN tbl_faturamento using(faturamento, fabrica) join tbl_faturamento_item using(faturamento)  where fabrica = $login_fabrica and situacao ~* 'objeto entregue' $cond_data;
			select  distinct faturamento , to_char(data, 'DD/MM/YYYY') as data , situacao , tbl_faturamento_item.pedido into temp postagem_$login_admin  from tbl_faturamento_correio JOIN tbl_faturamento using(faturamento, fabrica) join tbl_faturamento_item using(faturamento)  where fabrica = $login_fabrica and situacao ~* 'objeto postado' $cond_data;
			create index fat_entrega_$login_admin on entrega_$login_admin(faturamento) ;
			create index fat_postagem_$login_admin on postagem_$login_admin(faturamento) ; ";

			$res_temp = pg_query($con,$sql_temp);

			$joinAdicionais = array_merge($joinAdicionais, array(
				"LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item",
				"LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}",
				"LEFT JOIN postagem_$login_admin  as  faturamento_postagem ON faturamento_postagem.faturamento = tbl_faturamento.faturamento  and faturamento_postagem.pedido = tbl_pedido.pedido",
				"LEFT JOIN entrega_$login_admin as  faturamento_entrega ON faturamento_entrega.faturamento = tbl_faturamento.faturamento and faturamento_entrega.pedido = tbl_pedido.pedido",
			));

			$colunas_itens = array(
				"tbl_faturamento.nota_fiscal",
				"tbl_faturamento.emissao",
				"tbl_faturamento.saida",
				"tbl_pedido_item.qtde",
				"tbl_pedido_item.qtde_faturada",
				"(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada))",
				"faturamento_postagem.data",
				"faturamento_entrega.data",
				"tbl_peca.referencia",
				"fn_retira_especiais(tbl_peca.descricao)",
				"tbl_faturamento.conhecimento"
			);
        } else if ($login_fabrica == 40) {
            $groupBy = array_merge($groupBy, array(
                "tbl_posto.cnpj"
            ));

            $colunas_itens = array(
                "tbl_peca.referencia",
                "tbl_peca.descricao",
                "tbl_pedido_item.preco",
                "tbl_pedido_item.qtde",
                "tbl_pedido_item.qtde_faturada",
                "tbl_pedido_item.qtde_cancelada",
                "(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada))"
            );
        }

        if (count($colunas_itens))
            $colunasAdicionais = array(
                "ARRAY_AGG((".implode(",", $colunas_itens).")) AS itens"
            );

        if ($login_fabrica == 40) {
            $colunasAdicionais = array_merge($colunasAdicionais, array(
                "tbl_posto.cnpj AS cnpj_posto"
            ));
        } elseif ($login_fabrica == 153) {
            $colunasAdicionais = array_merge($colunasAdicionais, array(
                "tbl_posto.cnpj AS cnpj_posto",
                "tbl_tipo_posto.descricao AS descricao_tipo_posto"
            ));
        }

        $colunasAdicionais = ",".implode(",", $colunasAdicionais);
        $joinAdicionais    = implode(" ", $joinAdicionais);
        $groupBy           = "GROUP BY ".implode(",", $groupBy);

        $colunaPedidoCancelado = ", (SELECT tbl_pedido_cancelado.pedido FROM tbl_pedido_cancelado WHERE tbl_pedido_cancelado.fabrica = {$login_fabrica} AND tbl_pedido_cancelado.pedido = tbl_pedido.pedido LIMIT 1) AS item_cancelado";
    } else {
        $colunaPedidoCancelado    = ", tbl_pedido_cancelado.pedido AS item_cancelado";
        $innerJoinPedidoCancelado = "LEFT JOIN tbl_pedido_cancelado ON tbl_pedido.pedido = tbl_pedido_cancelado.pedido";
	}

	if (strlen($produto_referencia) > 0 and $login_fabrica == 6){
		$join_produto = " JOIN tbl_produto ON tbl_pedido.produto = tbl_produto.produto ";
	}

    if ($login_fabrica == 158) {
        $leftJoinOS = "
            LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
            LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
        ";
        $campoDescTipoPedido = "(CASE WHEN tbl_tipo_pedido.garantia_antecipada IS NOT TRUE AND tbl_tipo_pedido.pedido_faturado IS NOT TRUE AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE THEN tbl_tipo_pedido.descricao||' - GARANTIA' ELSE tbl_tipo_pedido.descricao END) AS descricao_tipo_pedido,";
    } else {
        $campoDescTipoPedido = "tbl_tipo_pedido.descricao AS descricao_tipo_pedido,";
    }

    if (in_array($login_fabrica, array(169, 170)) && !empty($_REQUEST["admin_sap"])) {
        $whereAdminSap = "AND tbl_posto_fabrica.admin_sap = {$_REQUEST['admin_sap']}";
    }

    if (in_array($login_fabrica, array(169, 170)) && !empty($_REQUEST["tipo_posto"])) {
        $whereTipoPosto = "AND tbl_posto_fabrica.tipo_posto = {$_REQUEST['tipo_posto']}";
    }

    if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
        $camposDetalhado = ",tbl_peca.referencia as referencia_peca,
                             tbl_peca.descricao as descricao_peca,
                             tbl_pedido_item.qtde_faturada,
                             tbl_pedido_item.qtde_cancelada,
                             tbl_tabela.descricao as descricao_tabela,
                             tbl_pedido_item.qtde as qtde_pecas";
        $leftDetalhado = "LEFT JOIN tbl_pedido_item        ON tbl_pedido_item.pedido = tbl_pedido.pedido
       LEFT JOIN tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
       LEFT JOIN tbl_tabela      ON tbl_tabela.tabela = tbl_pedido.tabela";
    }

    if ($login_fabrica == 85) {
        $codigo_cliente = $_POST['cliente_codigo'];

        $sql = "SELECT grupo_cliente FROM tbl_grupo_cliente
            WHERE fabrica = $login_fabrica AND descricao = 'Garantia Contratual'
            AND ativo IS TRUE";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $grupo_cliente = pg_fetch_result($res, 0, 'grupo_cliente');
        }
        $leftCliente = "LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido
                        LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                        LEFT JOIN tbl_hd_chamado_extra ON tbl_os.os = tbl_hd_chamado_extra.os
                        LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        LEFT JOIN tbl_cliente ON tbl_hd_chamado.cliente = tbl_cliente.cliente";

        $camposCliente = ", tbl_cliente.nome AS nome_cliente, tbl_cliente.codigo_cliente AS codigo_cliente, tbl_cliente.cliente as cliente_id";

        if (!empty($codigo_cliente)) {
            $sql = "SELECT tbl_cliente.cliente 
                    FROM tbl_cliente
                    JOIN tbl_grupo_cliente 
                    ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente
                    WHERE tbl_cliente.grupo_cliente = $grupo_cliente
                    AND UPPER(tbl_cliente.codigo_cliente) = UPPER('$codigo_cliente')";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $cliente = pg_fetch_result($res, 0, 'cliente');

                $condCliente = "AND tbl_hd_chamado.cliente = $cliente";

                
            } else {
                $msg_erro['msg'][]    = "Cliente não encontrado";
                $msg_erro['campos'][] = "cliente";
            }
        }
    }

    $campo_nome_destinatario   = ""; 
    $join_tbl_hd_chamado_extra = "";
    if ($telecontrol_distrib || $interno_telecontrol) {
        $campo_nome_destinatario = "CASE
                                        WHEN tbl_hd_chamado_extra.hd_chamado IS NOT NULL
                                        THEN tbl_hd_chamado_extra.nome
                                        ELSE (
                                            SELECT DISTINCT ON (tbl_os.os)
                                                COALESCE(tbl_os.consumidor_nome, tbl_os.revenda_nome)
                                            FROM tbl_os
                                            JOIN tbl_pedido_item pi ON pi.pedido = tbl_pedido.pedido
                                            JOIN tbl_os_item    ON pi.pedido_item = tbl_os_item.pedido_item
                                            JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                            WHERE tbl_os.os = tbl_os_produto.os
                                            AND tbl_os.fabrica = {$login_fabrica}
                                            LIMIT 1
                                        )
                                    END AS nome_destinatario, ";
        $join_tbl_hd_chamado_extra = "LEFT JOIN    tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido";
    }

    /**
     * Ao adicionar uma coluna para todas as fábricas, adicionar a coluna ao group by:
     * 40, 151 e 153
     */
    $sql = "SELECT  DISTINCT
                    tbl_pedido.pedido,
                    tbl_pedido.seu_pedido,
                    tbl_pedido.pedido_blackedecker as pedido_fabricante,
                    tbl_pedido.pedido_cliente,
                    tbl_posto.nome AS posto_nome,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_pedido.fabrica,
                    tbl_pedido.pedido_cliente,
                    tbl_pedido.entrega,
                    to_char(tbl_pedido.data, 'DD/MM/YYYY') AS data,
                    to_char(tbl_pedido.recebido_posto,'DD/MM/YYYY') AS recebido_posto,
                    {$campoDescTipoPedido}
                    {$campo_os_bloqueada}
                    {$campo_os_finalizada}
                    tbl_status_pedido.status_pedido,
                    tbl_status_pedido.descricao AS descricao_status_pedido,
                    tbl_pedido.exportado,
                    tbl_pedido.exportado_manual,
                    tbl_pedido.status_fabricante,
                    $campo_nome_destinatario
                    tbl_pedido.origem_cliente,
                    tbl_pedido.pedido_os,
                    tbl_pedido.total,
                    tbl_admin.login,
                    tbl_condicao.descricao AS condicao_pagamento,
                    tbl_fabrica.altera_pedido_exportado,
		    tbl_pedido.aprovado_cliente
                    {$camposCliente}
                    {$colunaPedidoCancelado}
                    {$colunasAdicionais}
                    {$column_marca}
                    {$column_transportadora}
                    {$camposDetalhado}
            FROM    tbl_pedido
            JOIN    tbl_posto           ON  tbl_posto.posto                 = tbl_pedido.posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto         = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica       = tbl_pedido.fabrica
                                        AND tbl_posto_fabrica.fabrica       = $login_fabrica
            $joinTroca
            {$joinAdicionais}
            {$leftJoinOS}
            {$left_campos_extra}
            {$leftCliente}
       LEFT JOIN    tbl_tipo_pedido     ON  tbl_tipo_pedido.tipo_pedido     = tbl_pedido.tipo_pedido
                                        AND tbl_tipo_pedido.fabrica         = $login_fabrica
            $leftDetalhado
       LEFT JOIN    tbl_condicao        ON  tbl_condicao.condicao           = tbl_pedido.condicao
                                        AND tbl_condicao.fabrica            = $login_fabrica
            $join_chk8
            $sql_left
            JOIN    tbl_status_pedido   ON  tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
       LEFT JOIN    tbl_admin           ON  tbl_admin.admin                 = tbl_pedido.admin
                                        AND tbl_admin.fabrica               = $login_fabrica
            {$innerJoinPedidoCancelado}
            JOIN    tbl_fabrica         ON tbl_fabrica.fabrica              = tbl_pedido.fabrica
            {$join_marca}
            {$join_transportadora}
            {$join_tbl_hd_chamado_extra}
            WHERE   tbl_pedido.fabrica      = $login_fabrica
            AND     tbl_pedido.finalizado   IS NOT NULL
            {$condCliente}
            {$whereAdminSap}
            {$whereTipoPosto}
             ";

if (strlen($status_pedido) > 0 AND (in_array($login_fabrica,array(51,45,24,85,158)))) {
    if ($status_pedido == "pendente") {
        $sql .= " AND tbl_pedido.status_pedido NOT IN(4,5,14) ";
    } else {
        $sql .= "AND tbl_pedido.status_pedido = $status_pedido ";
    }
}

if (in_array($login_fabrica,array(158,169,170)) && strlen($estado_posto_autorizado) > 0) {
    $sql .= " AND tbl_posto_fabrica.contato_estado = '$estado_posto_autorizado'";
}

if ($estado_pedido == 1 ) {
    $sql .= "AND tbl_pedido.status_pedido not in (4,14) ";
}elseif($estado_pedido == 2) {
	$sql .= "AND tbl_pedido.status_pedido in (4,14) ";
}

if (strlen($estado) > 0 AND $login_fabrica==72) { #HD 280384
    $sql .= "AND tbl_posto.estado = '$estado' ";
}

if ($chk == "1") {
    // data do dia
    $sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
    $dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

    $sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
    $resX = pg_exec ($con,$sqlX);

    $monta_sql .=" AND (tbl_pedido.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";

    $msg .= " &middot; Pedidos lançados hoje";
}

if ($chk == "2") {
    // dia anterior
    $sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
    $dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

    $monta_sql .=" AND (tbl_pedido.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";

    $msg .= " Pedidos lançados ontem";

}

if ($chk == "3") {
    // nesta semana
    $sqlX = "SELECT to_char (current_date , 'D')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

    $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

    $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

    $monta_sql .=" AND (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";

    $msg .= " Pedidos lançados nesta semana";

}

if ($chk == "4") {
    // semana anterior
    $sqlX = "SELECT to_char (current_date , 'D')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_hoje = pg_result ($resX,0,0) - 1 + 7 ;

    $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

    $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

    $monta_sql .=" AND (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";

    $msg .= " Pedidos lançados na semana anterior";

}

if ($chk == "5")
{
    $mes_inicial = trim(date("Y")."-".date("m")."-01");
    $mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
    $monta_sql .= " AND (tbl_pedido.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";

    $msg .= " Pedidos lançados neste mês";

}

if(strlen(trim($pdpp))> 0){
    $monta_sql .= " AND tbl_pedido.status_pedido = $pdpp ";
}

if (!empty($data_inicial_01) && !empty($data_final_01)) {
    $data_inicial     = $data_inicial_01;
    $data_final       = $data_final_01;

    list($di, $mi, $yi) = explode("/", $data_inicial_01);
    list($df, $mf, $yf) = explode("/", $data_final_01);

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial_01);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inicial Inválida";
    }

    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final_01);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Final Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }

    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
            $msg_erro = "Data final não pode ser menor que data inicial ou maior que a data atual.";
        }
    }

    if (strlen(trim($msg_erro)) == 0) {
        $data_corte = '01/04/2019';
        if (!verifica_data_corte($data_corte, $data_inicial) && in_array($login_fabrica, [167, 203])) {
            $msg_erro = "Data informada inferior a data limite para pesquisa";
        }
    }

    /*O trecho abaixo, colocar apenas se o relatório não permitir pesquisa em um
        intervalo maios que 60 dias.
    ===================INICIO=======================*/
    if(strlen($msg_erro)==0){
        if (strtotime($aux_data_inicial.'+6 months') < strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 6 meses';
        }
    }
    
    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di 00:00:00";
        $aux_data_final = "$yf-$mf-$df 23:59:59";
    }

    if (empty($msg_erro)){

        if ($login_fabrica == 1) {
            $monta_sql .=" and (tbl_pedido.exportado BETWEEN '$aux_data_inicial' AND '$aux_data_final') ";
        }else{
            $monta_sql .=" AND (tbl_pedido.data BETWEEN '$aux_data_inicial' AND '$aux_data_final') ";
        }


        $msg .= "Pedidos lançados entre os dias $data_inicial e $data_final ";


    }

}

if (!empty($codigo_posto))
{
    $monta_sql .= " AND tbl_posto_fabrica.codigo_posto ='$codigo_posto' ";
}
if(!empty($tipo_posto) AND $login_fabrica == 153){ //HD-2921051
    $monta_sql .= " AND tbl_posto_fabrica.tipo_posto = $tipo_posto ";
}

if(!empty($linha_produto)){ //hd_chamado=2765193
    if ((empty($data_inicial_01) && empty($data_final_01)) && empty($chk)) {
        $msg_erro = "Para este tipo de pesquisa, insira uma data.";
    }
    $monta_sql .= " AND tbl_pedido.linha = $linha_produto ";
}

# Peça
if (strlen($peca_referencia_consulta) > 0 and ($login_fabrica <> 6)) {

    if (!empty($peca_referencia_consulta) and (empty($data_inicial_01) && empty($data_final_01))) {
        $msg_erro = "Para este tipo de pesquisa, insira uma data.";
    }

    $peca_referencia_pesquisa = str_replace("-","",$peca_referencia_consulta);

    $sql_verifica_peca = "SELECT peca from tbl_peca where fabrica = $login_fabrica and  (referencia_pesquisa = '".$peca_referencia_consulta."' or referencia = '".$peca_referencia_consulta."'  )  ";
    $res_verifica_peca = pg_query($con,$sql_verifica_peca);

    if (pg_num_rows($res_verifica_peca)>0){

        $peca_pesquisa = pg_fetch_result($res_verifica_peca, 0, 0);
        $monta_sql .= " AND tbl_pedido_item.peca = '".$peca_pesquisa."' ";

    }else{
        $msg_erro = "Peça pesquisada não existe";
    }

}else if (strlen($produto_referencia) > 0 and $login_fabrica == 6){

    $sql_produto = "SELECT produto,referencia,descricao from tbl_produto join tbl_linha using (linha) where tbl_produto.referencia = '$produto_referencia' and tbl_linha.fabrica = $login_fabrica";
    $res_produto = pg_query($con,$sql_produto);

    if (pg_num_rows($res_produto)>0){
        $produto = pg_fetch_result($res_produto, 0, 0);
        $produto_ref = pg_fetch_result($res_produto, 0, 1);
        $produto_desc = pg_fetch_result($res_produto, 0, 2);
        $monta_sql .= " AND tbl_pedido.produto =".$produto." ";

    }else{
        $msg_erro = "Produto pesquisado não existe";
    }
}

if (strlen($numero_pedido) > 0)
{
    if($login_fabrica == 30){
        $numero_pedido = str_replace(array("T","F"), "", $numero_pedido);
    }
    $id_pedido = preg_replace('/\D/','', $numero_pedido);
    if(in_array($login_fabrica,array(88,175))){
        $monta_sql .= " AND (tbl_pedido.seu_pedido='".$numero_pedido."' OR tbl_pedido.pedido = $id_pedido )";
    }else{
        $monta_sql .= " AND (tbl_pedido.pedido_cliente='".$numero_pedido."' OR tbl_pedido.pedido = $id_pedido) ";
    }


}

if (strlen($tipo_pedido) > 0)
{

    $statuss = str_replace("\\","",$_POST['statuss']);
    $statuss = json_decode($statuss);
    $statuss = (array) $statuss;
    switch ($tipo_pedido) {
        case 0:
            break;
        default :
            $monta_sql .= " AND tbl_pedido.status_pedido = $tipo_pedido";
            break;
    }
}

if (strlen($pedido_status) > 0) {
    $monta_sql .= "AND tbl_pedido.status_pedido = $pedido_status ";
}

if (strlen($tipo) > 0)
{
    if (!is_numeric($tipo)) {
        switch ($tipo) {
            case "garantia":
                $monta_sql .= " AND tbl_tipo_pedido.pedido_em_garantia IS TRUE ";
                break;

            case "fora_garantia":
                $monta_sql .= " AND tbl_tipo_pedido.pedido_em_garantia IS NOT TRUE ";
                break;
        }
    } else {
        $monta_sql .= " AND tbl_pedido.tipo_pedido = $tipo";
    }
}
if($login_fabrica == 24 && strlen($numero_pedido) == 0 && strlen($status_pedido) == 0){ // HD 18161
    if ($tipo_pedido <> 5){
        $sql_status_pedido=" AND tbl_pedido.status_pedido <> 14 ";
    }
}

$sql .= $monta_sql;
$sql .= " $sql_status_pedido {$groupBy} ORDER BY pedido DESC";
 
if(strlen($msg_erro) == 0){
    $res = pg_query($con, $sql);
    $num_rows = pg_num_rows($res);
	// pre_echo($sql, pg_last_error($con));
}

if(strlen($msg_erro) > 0){
?>
    <div id="div_erro" class="msg_erro alert alert-danger">
        <h4><?= $msg_erro ?></h4>
    </div>
<?php
}

if (empty($msg_erro)){
    if(pg_num_rows($res) > 0 and (isset($_POST['frm_submit']) || isset($_POST['form_submit']))){
        if (isset($_POST['form_submit'])) {
            $statuss = str_replace("\\","",$_POST['statuss']);
            $statuss_json = $statuss;
            $statuss = json_decode($statuss);
        } else {
        $statuss = array();
        for ($i = 0 ; $i < $num_rows ; $i++){
            $status_pedido             = trim(pg_result ($res,$i,'status_pedido'));
            $descricao_status_pedido             = trim(pg_result ($res,$i,'descricao_status_pedido'));
            if(!empty($status_pedido)) {
                $statuss[$status_pedido] = retira_acentos($descricao_status_pedido);
            }
        }
            $statuss = array_unique($statuss);
            asort($statuss);
            $statuss_json = json_encode($statuss);
        }
    } else {
        $statuss = str_replace("\\","",$_POST['statuss']);
        $statuss_json = $statuss;
        $statuss = json_decode($statuss);
    }

    echo "<div id='box'>";
        echo "<TABLE class='table table-striped table-bordered'>";

        echo "<FORM NAME='frm_tipo_pedido' class='form-search form-inline tc_formulario' METHOD='POST' ACTION='pedido_parametros.php'>\n";
            echo "<INPUT TYPE='hidden' name='form_submit'        value='submit'>\n";
            echo "<INPUT TYPE='hidden' name='chk_opt'           value='$chk'>\n";
            echo "<INPUT TYPE='hidden' name='chk_opt10'          value='$chk10'>\n";
            echo "<INPUT TYPE='hidden' name='tipo_pedido'        value='$tipo_pedido'>\n";
            echo "<INPUT TYPE='hidden' name='tipo'               value='$tipo'>\n";
            echo "<INPUT TYPE='hidden' name='data_inicial_01'    value='$data_inicial_01'>\n";
            echo "<INPUT TYPE='hidden' name='data_final_01'      value='$data_final_01'>\n";
            echo "<INPUT TYPE='hidden' name='codigo_posto'       value='$codigo_posto'>\n";
            echo "<INPUT TYPE='hidden' name='nome_posto'         value='$nome_posto'>\n";
            echo "<INPUT TYPE='hidden' name='peca_referencia'    value='$peca_referencia_consulta'>\n";
            echo "<INPUT TYPE='hidden' name='produto_referencia' value='$produto_referencia'>\n";
            echo "<INPUT TYPE='hidden' name='produto_nome'       value='$produto_nome'>\n";
            echo "<INPUT TYPE='hidden' name='numero_pedido'      value='$numero_pedido'>\n";
            echo "<INPUT TYPE='hidden' name='estado'             value='$estado'>\n";
            echo "<INPUT TYPE='hidden' name='statuss'            value='$statuss_json'>\n";
            echo "<TR>\n";

        echo "</FORM>";

        echo "</table>";
    echo "</div>";

    if (@pg_numrows($res) == 0) {
        echo "<div class='alert alert-warning'><h4>Não existem pedidos com estes parâmetros</h4></div>";
    }
    if (@pg_numrows($res) > 0) {

    if (!empty($peca_referencia_consulta)) {
        echo "<div class='alert alert-warning'><h4 style='font-size: 12pt;'>Filtro por peça selecionado. Os dados da tabela serão mostrados de acordo com a peça escolhida.</h4></div>";
    }
	echo "<br /><table>
                        <tr>
                          <td width='80' height='20' style='border: 1px solid black;' bgcolor='#FF8282'></td>
                          <td> &nbsp;&nbsp;&nbsp;Pedido com itens cancelados</td>
                        </tr>
          </table><br />";

                    echo "</div><table id='tabela_pedidos' class='table table-bordered table-large'>";
                        echo "<THEAD>";
                        echo "<tr class='titulo_tabela'>";
                            if (!empty($peca_referencia_consulta)) {
                                echo "<TH>Pedidos referentes à peça ".$peca_referencia_consulta." - ".$peca_descricao_consulta." </TH>";
                            } else {
                                echo "<TH $colspan >Lista de pedidos</TH>";
                            }
                        echo "</tr>";
                        echo "<TR class='titulo_coluna'>";
                            echo "<TH>Pedido</TH>";
                if ($login_fabrica == 145) {
                    echo "<TH>Pedido Fabricante</th>";
                }

                if ($login_fabrica == 175) {
                    echo "<TH>Pedido Ibramed</th>";
                }
                            if ($login_fabrica == 85) {
                               echo "<th>Cliente Contratual</th>";
			    }

			    if(!$telecontrol_distrib){
                            	echo "<TH>";
                            	echo ($login_fabrica == 95) ? "Série" : "Pedido Cliente" ;
			    	echo "</TH>";
			    }
                            echo "<TH>Tipo</TH>";
                            if($login_fabrica == 178){
                                echo "<th>Marca</th>";
                            }
                            if($login_fabrica == 168){
                                echo "<TH>Condição de Pagamento</TH>";
                            }
                            if($login_fabrica == 7){
                                echo "<TH>Origem (OS/Compra)</TH>";
                                echo "<TH>Solicitante (PTA/Cliente)</TH>";
                                echo "<TH>Admin</TH>";
                            }
                            echo "<TH>Status</TH>";
                            if($login_fabrica == 151){
                                echo "<TH>Status OS</TH>";
                            }
                            if($login_fabrica == 88) { echo "<TH>Liberado p/ Exportação</TH>"; }
                            if($login_fabrica == 45) { echo "<TH>Status Fabricante</TH>"; }
                            echo "<TH>Data</TH>";
                            if ($login_fabrica == 24) {echo "<TH>Recebido</TH>";}
                            echo "<TH>Posto</TH>";
                            if ($login_fabrica == 14) {echo "<TH>Valor</TH>";}
                            if ($login_fabrica == 146) { echo "<tH>Marca</tH>"; }
                            if ($login_fabrica == 163) { echo "<TH>Transportadora</TH>";}
                            if ($login_fabrica == 163) { echo "<TH>Tipo de Entrega</TH>";}
                            if ($login_fabrica == 163) { echo "<TH>Anexo</TH>";}
                            if ($login_fabrica == 42 || $login_fabrica == 74) { $span = 2;}


							if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
                                echo "<TH>Referência Peça</TH>";
                                echo "<TH>Descrição Peça</TH>";
                            }

                                if (empty($peca_referencia_consulta)) {
                                    echo "<TH>Qtde. Itens Pedido</TH>";
                                    echo "<TH class='money_column'>Total Pedido</TH>";
                                } else {
                                    echo "<TH>Qtde. da<br /> Peça</TH>";
                                    echo "<TH>Total da Peça</TH>";
                                    echo "<TH>Qtde. Itens Pedido</TH>";
                                    echo "<TH>Total Pedido</TH>";
                                }
                                if ($login_fabrica == 85) {
                                    echo "<TH nowrap>Referência do Produto</TH>";
                                    echo "<TH nowrap>Nº de Série do Produto</TH>";
                                    echo "<TH>O.S.</TH>";
                                    echo "<TH nowrap>Peças do Pedido</TH>";
                                    echo "<TH>Preço</TH>";
                                    echo "<TH>IPI</TH>";
                                    echo "<TH>Total com IPI</TH>";
                                    echo "<TH>Ações</TH>";
                                }
                                if (!in_array($login_fabrica, [85,167,203])) {
                                    echo "<TH colspan='$span'>Ações</TH>";
                                }

                            if($login_fabrica == 50) {
                                echo "<TH colspan='2'><a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'> </a></TH>";

                            }

                            if(in_array($login_fabrica, array(138))){
                                echo "<td class='tac'> Boleto Bancário </td>";
                            }

                        echo "</TR></THEAD><tbody>";

        $xls_rows = pg_num_rows($res);
        if (pg_num_rows($res))
        {
            $rows = pg_num_rows($res);
        }

        for ($i = 0 ; $i < $rows ; $i++){

            $pedido            = trim(pg_result ($res,$i,pedido));
            $seu_pedido        = trim(pg_result ($res,$i,seu_pedido));
            $pedido_fabricante = ($login_fabrica == 145) ? pg_fetch_result($res,$i,pedido_fabricante) : $seu_pedido;
            $pedido_cliente    = trim(pg_result ($res,$i,pedido_cliente));
            $descricao_tipo    = trim(pg_result ($res,$i,descricao_tipo_pedido));
            $exportado         = trim(pg_result ($res,$i,exportado));
            $exportado_manual  = trim(pg_result ($res,$i,exportado_manual));
            $situacao_pedido   = trim(pg_result ($res,$i,status_pedido));
            if ($login_fabrica == 2 and strlen($exportado) > 0)
                $status             = "OK";
            else
                $status             = trim(pg_result ($res,$i,descricao_status_pedido));
            $data               = trim(pg_result ($res,$i,data));
            $codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
            $posto_nome         = trim(pg_result ($res,$i,posto_nome));
            $data_recebido      = trim(pg_result ($res,$i,recebido_posto));
            $status_fabricante  = trim(pg_result ($res,$i,status_fabricante));

            $origem_cliente     = trim(pg_result ($res,$i,origem_cliente));
            $pedido_os          = trim(pg_result ($res,$i,pedido_os));
            $total              = trim(pg_result ($res,$i,total));
            $login              = trim(pg_result ($res,$i,login));
            $cliente_descricao  = (!empty(pg_result ($res,$i,'codigo_cliente'))) ? trim(pg_result ($res,$i,'codigo_cliente'))."  - ".trim(pg_result ($res,$i,'nome_cliente')) : "";
            $cliente_id         = trim(pg_result ($res,$i,cliente_id));

	    if ($login_fabrica == 175) {
		$aprovado_cliente = pg_fetch_result($res, $i, 'aprovado_cliente');
	    }

			if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
                $tabela            = trim(pg_result ($res,$i,'descricao_tabela'));
                $referencia_peca   = trim(pg_result ($res,$i,'referencia_peca'));
                $descricao_peca    = trim(pg_result ($res,$i,'descricao_peca'));
                $qtde_faturada     = trim(pg_result ($res,$i,'qtde_faturada'));
                $qtde_cancelada    = trim(pg_result ($res,$i,'qtde_cancelada'));
                $qtde_pecas        = trim(pg_result ($res,$i,'qtde_pecas'));
            }

            $altera_pedido_exportado = pg_fetch_result($res,$i,'altera_pedido_exportado');
            $condicao_pagamento = pg_fetch_result($res,$i,'condicao_pagamento');
            if($login_fabrica == 42) {
                if(empty($status)) {
                    $status = "Pedido não finalizado";
                }
            }

            if ($login_fabrica == 146) {
                $marca = pg_fetch_result($res, $i, "marca");
            }

            if($login_fabrica == 151){
                $os_bloqueada           = pg_fetch_result($res, $i, 'os_bloqueada');
                $finalizada             = pg_fetch_result($res, $i, 'finalizada');

                if($os_bloqueada == 't' and empty($finalizada)){
                    $os_bloqueada= "Congelada";
                }elseif($os_bloqueada != 't' and empty($finalizada)){
                    $os_bloqueada= "Descongelada";
                }
            }

            if ($login_fabrica == 163) {
                //$transportadora_cnpj      = pg_fetch_result($res, $i, 'transportadora_cnpj');
                $transportadora_nome = pg_fetch_result($res, $i, 'transportadora_nome');
                $entrega             = trim(pg_result ($res,$i,entrega));
            }

            $cor = 'white';
            $btn = ($i % 2) ?'amarelo':'azul';

            if($login_fabrica == 95) {
                $sqls = " SELECT
                            DISTINCT tbl_os.serie
                        JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
                        JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
                        WHERE
                        tbl_pedido_item.pedido=$pedido";
                $ress = pg_query($con,$sqls);
                if(pg_num_rows($ress) > 0){
                    $serie = pg_fetch_result($ress,0,'serie');
                }
            }

        $pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

        $cor = (pg_fetch_result($res,$i,'item_cancelado')) ? "#FF8282" : $cor;
        echo "<TR style='background-color: $cor;'>\n";
        if($login_fabrica == 30){
            if(strlen($seu_pedido) > 0){
                echo "  <TD><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'>$seu_pedido</a></TD>\n";
            }else{
                echo "  <TD><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'>$pedido_aux</a></TD>\n";

            }
        }else{
            if (in_array($login_fabrica, [167, 203])) {
                echo "  <TD class='tac'>$pedido_aux</TD>\n";    
            } else {
                echo "  <TD class='tac'><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'>$pedido_aux</a></TD>\n";
            }
        }

        if (in_array($login_fabrica,array(145,175))) {
            echo "<td>$pedido_fabricante</td>";
        }   
            if ($login_fabrica == 85) {
                echo "<td>{$cliente_descricao}</td>";
	    }

	    if(!$telecontrol_distrib){
            	echo "  <TD>";
            	echo ($login_fabrica  == 95) ? $serie : $pedido_cliente;
	    	echo "</TD>\n";
	    }
            echo "  <TD <TD class='tac'>$descricao_tipo</TD>\n";
            
            if($login_fabrica == 178){
                $sql_marca = "SELECT DISTINCT tbl_marca.nome AS marca_nome
                        FROM tbl_os_campo_extra
                        JOIN tbl_marca USING(marca)
                        JOIN tbl_os_produto USING(os)
                        JOIN tbl_os_item USING(os_produto)
                        WHERE tbl_os_item.pedido = $pedido";
                $res_marca = pg_query($con, $sql_marca);
                echo "<td>".pg_fetch_result($res_marca, 0, "marca_nome")."</td>";
            }
            if($login_fabrica == 168){
            echo "  <TD>$condicao_pagamento</TD>\n";
            }
            if($login_fabrica == 7) {
                    if($pedido_os =='t'){
                        $pedido_os_descricao = " Ordem Serviço";
                    }else{
                        $pedido_os_descricao = " Compra Manual";
                    }
                    echo "<td>". $pedido_os_descricao ."</td>";
                    if($origem_cliente == 't'){
                        $origem_descricao = "Cliente";
                    }else{
                        $origem_descricao = "PTA";
                    }
                    echo "<td>".$origem_descricao ."</td>";
                    echo "<td>".$login."</td>";
            }
            echo "  <TD>$status</TD>\n";
            if($login_fabrica == 151){
                echo "  <TD>$os_bloqueada</TD>\n";
            }
            if($login_fabrica == 88) {
                $exportado_manual = ($exportado_manual == "t") ? "Sim" : "Não";
                echo "  <TD>$exportado_manual</TD>\n";
            }
            if($login_fabrica == 45) {
                echo "  <TD>$status_fabricante</TD>\n";
            }
            echo "  <TD <TD class='tac'>$data</TD>\n";
            if ($login_fabrica == 24) {echo "   <TD>$data_recebido</TD>\n";}
            echo "  <TD>$codigo_posto - <ACRONYM TITLE=\"$posto_nome\">$posto_nome</ACRONYM></TD>\n";
            if ($login_fabrica == 14) {
                echo "<TD><b>". number_format($total,2,",",".") ."</b></TD>\n";
            }
            if ($login_fabrica == 146) {
                echo "<td>{$marca}</td>";
            }


                //caso exista peça
                if (!empty($peca_referencia_consulta)) {
                    $sql_pedido_peca = "SELECT case when $login_fabrica = 88 then sum(tbl_pedido_item.qtde * tbl_pedido_item.preco) else sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) end as total_pedido, SUM(tbl_pedido_item.qtde) as qtd_total, COUNT(*) as qtd_item
                                    FROM  tbl_pedido
                                    JOIN  tbl_pedido_item USING (pedido)
                                    JOIN  tbl_peca        USING (peca)
                                    WHERE tbl_pedido_item.pedido = $pedido
                                    AND tbl_peca.referencia = '$peca_referencia_consulta'
                                    GROUP BY tbl_pedido.pedido";

                    $res_pedido_peca = pg_query($con, $sql_pedido_peca);

                    $total_pedido_peca = pg_result($res_pedido_peca,0,'total_pedido');
                    $qtd_total_peca = pg_result($res_pedido_peca,0,'qtd_total');
                    $qtd_item_peca = pg_result($res_pedido_peca,0,'qtd_item');
                }

                //fábricas que não fazem calculo de ipi
                if (!in_array($login_fabrica, array(171))) {
                     $calculo_ipi = "* (1 + (tbl_peca.ipi / 100))";
                }

                $sql_pedido = "SELECT case when $login_fabrica = 88 then sum(tbl_pedido_item.qtde * tbl_pedido_item.preco) else sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) end as total_pedido, SUM(tbl_pedido_item.qtde) as qtd_total, COUNT(*) as qtd_item
                                FROM  tbl_pedido
                                JOIN  tbl_pedido_item USING (pedido)
                                JOIN  tbl_peca        USING (peca)
                                WHERE tbl_pedido_item.pedido = $pedido
                                GROUP BY tbl_pedido.pedido";

                $res_pedido = pg_query($con, $sql_pedido);

                $total_pedido = pg_result($res_pedido,0,'total_pedido');
                $qtd_total = pg_result($res_pedido,0,'qtd_total');
                $qtd_item = pg_result($res_pedido,0,'qtd_item');

				if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
                    echo "<TD class='tac'>".$referencia_peca."</TD>";
                    echo "<TD>".$descricao_peca."</TD>";
                }

                if ($login_fabrica == 85) {

                    $joinClienteContratual = "";
                    if (!empty($cliente_id)) {
                            $joinClienteContratual = " JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
                                                       JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado 
                                                       AND tbl_hd_chamado.cliente = $cliente_id";

                            $sql_cliente = "SELECT sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as total_pedido, SUM(tbl_pedido_item.qtde) as qtd_total, COUNT(*) as qtd_item
                                FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
                                JOIN tbl_produto ON tbl_os_produto.produto=tbl_produto.produto
                                JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
                                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                                {$joinClienteContratual}
                               WHERE tbl_pedido_item.pedido=$pedido
                                 AND tbl_os.fabrica=$login_fabrica";
                            $res_cliente = pg_query($con, $sql_cliente);

                            $total    = pg_result($res_cliente,0,'total_pedido');
                            $qtd_total = pg_result($res_cliente,0,'qtd_total');
                            $qtd_item = pg_result($res_cliente,0,'qtd_item');

                    }
                }

                if (!empty($peca_referencia_consulta)) {
                    echo "<TD class='tac'>".$qtd_total_peca."</TD>";
                    echo "<TD>".number_format($total_pedido_peca,2,",",".")."</TD>";
                    echo "<TD class='tac'>".$qtd_item."</TD>";
                    echo "<TD>".number_format($total_pedido,2,",",".")."</TD>";
		} else {

		    if($login_fabrica == 91){
			echo "<TD class='tac'>".$qtd_total."</TD>";
		    }else{
			    echo "<TD class='tac'>".$qtd_item."</TD>";
		    }
                    echo "<TD style='text-align: right;'>".number_format($total,2,",",".")."</TD>";
                }

                if ($login_fabrica == 85) {
                    $serie_produto      = array();
                    $oss                = array();
                    $referencia_produto = array();
                    $descricao_peca     = array();
                    $preco_peca         = array();
                    $ipi_item           = array();
                    $total_ped          = array();

                    $sqls = " SELECT tbl_os.os, tbl_os.serie, tbl_produto.referencia
                                FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
                                JOIN tbl_produto ON tbl_os_produto.produto=tbl_produto.produto
                                JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
                                {$joinClienteContratual}
                               WHERE tbl_pedido_item.pedido=$pedido
                                 AND tbl_os.fabrica=$login_fabrica";
                    $ress = pg_query($con, $sqls);
                    if (pg_num_rows($ress) > 0) {
                        $dados = pg_fetch_all($ress);
                        foreach ($dados as $key => $rowss) {
                            $serie_produto[] = $rowss['serie'];
                            $oss[]   = $rowss['os'];
                            $referencia_produto[]   = $rowss['referencia'];
                        }
                    }

                    $sqlPeca = " SELECT 
                                 tbl_os_item.pedido,
                                 tbl_os_item.qtde,
                                 tbl_peca.referencia || ' - ' || tbl_peca.descricao AS descricao,
                                 tbl_pedido_item.preco,
                                 tbl_peca.ipi
                                    FROM    tbl_os
                                    JOIN    tbl_os_produto USING (os)
                                    JOIN    tbl_os_item USING (os_produto)
                                    JOIN    tbl_peca ON tbl_os_item.peca = tbl_peca.peca
                                    JOIN    tbl_pedido_item ON tbl_pedido_item.pedido = $pedido 
                                    AND     tbl_os_item.peca = tbl_pedido_item.peca
                                    {$joinClienteContratual}
                                    WHERE   tbl_os_item.pedido = $pedido
                                    AND     tbl_os.fabrica   = $login_fabrica";

                    $resPeca = pg_query($con, $sqlPeca);
                    if (pg_num_rows($resPeca) > 0) {
                        $dadospeca = pg_fetch_all($resPeca);
                        foreach ($dadospeca as $key => $rowss) {
                            $descricao_peca[]   = $rowss['descricao'];
                            $preco_peca[]       = number_format($rowss['preco'], 2, ".", ",");
                            $ipi_item[]         = number_format($rowss['ipi'], 2, ".", ",")."%";
                            $total_ped[]        = number_format(($rowss['qtde']*$rowss['preco'])*(1+$rowss['ipi'] / 100), 2, ".", ",");
                        }
                    }

                    echo "<TD class='tal' nowrap>".implode("<br />", $referencia_produto)."</TD>";
                    echo "<TD class='tac'>".implode("<br />", $serie_produto)."</TD>";
                    echo "<TD class='tac'>".implode("<br />", $oss)."</TD>";
                    echo "<TD class='tal' nowrap>".implode("<br />", $descricao_peca)."</TD>";
                    echo "<TD class='tac'>".implode("<br />", $preco_peca)."</TD>";
                    echo "<TD class='tac'>".implode("<br />", $ipi_item)."</TD>";
                    echo "<TD class='tac'>".implode("<br />", $total_ped)."</TD>";
                }


            if ($login_fabrica == 163) {

                $entrega_desc = ($entrega == "TRANSP") ? "Transportadora" : "Retirar na Fábrica";

                echo "<td> {$transportadora_nome}</td>";
                echo "<td> {$entrega_desc}</td>";

                if($entrega == "RFAB"){

                    $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;

                    for ($j = 0; $j < $fabrica_qtde_anexos; $j++) {
                        unset($anexo_link);

                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3          = false;
                        $anexo             = "";

                        if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {

                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }

                                    $anexo_item_link = $anexo['link'];
                                    $countDocs++;

                                }

                                $anexo    = basename($anexos[0]);
                                $anexo_s3 = true;

                            }
                        }
                    }
                }

                echo "<td>";
                    if(isset($anexo_item_link) > 0){
                        echo "<a href='".$anexo_item_link."' target='_blank' > Link Arquivo </a>";
                    }
                echo "</td>";

                unset($anexo_item_link);

            }

            if (strtolower($descricao_tipo) == "garantia" && empty($exportado) && in_array($login_fabrica, array(151))) {
                echo "  <td>";
                    if (!in_array($situacao_pedido, array(14))) {
                        echo "<button type='button' class='btn exportar_pedido' data-pedido='{$pedido}' style='background-color: #E1EAF1; color: #596D9B; height: 18px; border: 0px; margin-left: 10px; font-weight: bold; vertical-align: top; cursor: pointer;' >Exportar</button>";
                    }
                echo "</td>";
            }
            //HD-900300
            if (((empty($exportado)) || (!empty($exportado) && $altera_pedido_exportado =='t') || $login_fabrica == 101) && strpos("garantia",strtolower($descricao_tipo)) === false && !in_array($login_fabrica, [167, 203])){
                if ($login_fabrica == 14) {
					echo "  <TD>&nbsp;</TD>\n";
                } else if ($login_fabrica == 80) {
                    $sql = "SELECT
                                tbl_pedido.pedido

                                FROM
                                tbl_pedido
                                LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido

                                WHERE
                                tbl_pedido.fabrica = $login_fabrica
                                AND tbl_pedido.exportado IS NOT NULL
                                AND tbl_status_pedido.descricao <> 'Faturado Integral'
                                AND tbl_status_pedido.status_pedido <> 14
                                AND tbl_pedido.pedido=$pedido
                                ";
                    $res_faturado = pg_query($con, $sql);
                    if (pg_num_rows($res_faturado)) {
                        echo "<TD><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar_".$btn.".gif'>Alterar</a>&nbsp;<a href='pedido_nao_faturado_cadastro.php?pedido=$pedido'><img src='imagens/btn_faturar_".$btn.".gif'>Faturar</a></TD>\n";
                    } else {
                        echo "<TD><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a></TD>\n";
                    }
                } else if (in_array($login_fabrica, array(151))) {
                    echo "<td nowrap>";
                    if (!in_array($situacao_pedido, array(2,4,5,14))) {
                        echo "<a class='btn btn-primary' href='pedido_cadastro.php?pedido=$pedido'> Alterar</a>&nbsp;";
                    }

                    if (empty($exportado) && !in_array($situacao_pedido, array(14))) {
                        echo "<button type='button' class='btn exportar_pedido' data-pedido='{$pedido}' >Exportar</button>";
                    }
                    echo "</td>";
                }elseif($login_fabrica != 168){
                    if((!in_array($situacao_pedido,array(4,14)) || $login_fabrica == 101) && !(in_array($login_fabrica, array(143)) && in_array($situacao_pedido, array(19,17,1)))){

			if (in_array($login_fabrica, array(175))) {
				if ($situacao_pedido == 1 && empty($aprovado_cliente)) {
	        	                echo "<TD>";
        	                	    echo "<a class='btn btn-primary' href='pedido_cadastro.php?pedido=$pedido'>Alterar</a>";
	                	        echo "</TD>\n";
				} else {
					echo "<td>&nbsp;</td>";
				}
			} else {
				echo "<TD>";
        	                    echo "<a class='btn btn-primary' href='pedido_cadastro.php?pedido=$pedido'>Alterar</a>";
	                        echo "</TD>\n";
			}
                    } else {
                        echo "<td>&nbsp;</td>";
                    }
                    if ($login_fabrica == 42) {
                        echo "<TD><a href='#' onclick='javascript: if (confirm(\"Tem certeza que deseja reintegrar o pedido, o mesmo ficará com status de aguardando exportação?\")==true) { window.location = \"pedido_parametros.php?reintegrar=sim&pedido=$pedido\"}'>Re-integrar</a></TD>\n";
                    }

                }elseif($login_fabrica == 168){

                    echo "<TD class='tac'>";

                    if(in_array($situacao_pedido, array(18)) && $libera_pedido == 't'){
                        ?>
                        <img class='btn btn-success' id="liberar_<?=$pedido?>" ALT='Liberar Pedido' /><br /><br />
                        <img class='btn btn-danger' id="cancelar_<?=$pedido?>" ALT='Cancelar Pedido' /> <br /><br />
                        <?php
                        if(in_array($situacao_pedido, array(18))){
                            echo "<a class='btn btn-primary' href='pedido_cadastro.php?pedido=$pedido'>Alterar</a>\n";
                        }

                    }elseif(!in_array($situacao_pedido, array(14,18,25,28)) ){
                        echo "<a class='btn btn-primary' href='pedido_cadastro.php?pedido=$pedido'>Alterar</a>\n";
                    }
                    echo "</TD>\n";
                }
                if($login_fabrica == 50) {
                    echo "  <TD><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>Imprimir</a></TD>\n";
                    echo "  <TD><input name='imprimir_$i' type='checkbox' id='imprimir' rel='imprimir' value='".$pedido."' /></TD>\n";
                }
                if($login_fabrica == 74){
                    if($situacao_pedido == 1 && $callcenter_supervisor == 't'){
                        ?>
                        <td>
                            <img id="bloquear_<?=$pedido?>" class='btn btn-danger' ALT='Bloquear' />
                        </td>
                        <?php
                    }else if($situacao_pedido == 18 && $callcenter_supervisor == 't'){
                        ?>
                        <td>
                            <img class='btn btn-success' id="liberar_<?=$pedido?>" ALT='Liberar' />
                        </td>
                        <?php
                    } else {
                        ?>
                        <td>&nbsp;</td>
                        <?
                    }

                }
            } else if ($login_fabrica == 120 && $situacao_pedido == 1){
                echo "  <TD nowrap width='85'><a class='btn btn-primary' href='pedido_cadastro.php?pedido=$pedido'>Alterar</a></TD>\n";
            } else if($login_fabrica == 74) {
                if($situacao_pedido == 1 && $callcenter_supervisor == 't'){
                    ?>
                    <td>
                        <img id="bloquear_<?=$pedido?>" src='imagens_admin/btn_bloquear_vermelho.gif' ALT='Bloquear o pedido' />
                    </td>
                    <?php
                }else if($situacao_pedido == 18 && $callcenter_supervisor == 't'){
                    ?>
                    <td>
                    <img class='btn' id="liberar_<?=$pedido?>" src='imagens_admin/btn_liberar.jpg' ALT='Liberar o pedido' />
                </td>
                <?php
                } else {
                    ?>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <?
                }

            } else {
                if (!in_array($login_fabrica, [167, 203])) {
                    if(in_array($login_fabrica, [169,170])){
                        if(in_array($situacao_pedido, [4,5])){
                            echo "<td> <button class='alterar_nota btn btn-primary' data-pedido='$pedido'>Alterar Nota</button> </td>";        
                        }else{
                            echo "<td>&nbsp;</td>";    
                        }
                    }else{
                        echo "<td>&nbsp;</td>";    
                    }
                    echo (in_array($login_fabrica, array(50)) ) ? "<td>&nbsp;</td>": "";
                }
            }

            if(in_array($login_fabrica, array(138))){

                $idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                $countDocs = 0;

                $fabrica_qtde_anexos = 1;

                for ($j = 0; $j < $fabrica_qtde_anexos; $j++) {
                    unset($anexo_link);

                    $anexo_item_imagem = "imagens/imagem_upload.png";
                    $anexo_s3          = false;
                    $anexo             = "";

                    if(strlen($pedido) > 0) {

                        if (count($idAnexo) > 0) {
                            foreach($idAnexo as $anexo) {
                                if ($countDocs != $j) {
                                    continue;
                                }

                                $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

                                if ($ext_item == "pdf") {
                                    $anexo_item_imagem = "imagens/pdf_icone.png";
                                } else if (in_array($ext_item, array("doc", "docx"))) {
                                    $anexo_item_imagem = "imagens/docx_icone.png";
                                } else {
                                    $anexo_item_imagem = $anexo['link'];
                                }

                                $anexo_item_link = $anexo['link'];
                                $countDocs++;

                            }

                            $anexo        = basename($anexos[0]);
                            $anexo_s3     = true;
                        }
                    }
                    ?>

                    <td nowrap>
                        <?php if ($anexo_s3 === true) { ?>
                            <button id="baixar_<?=$i?>" type="button" class="btn btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')"> &nbsp; Visualizar boleto &nbsp; </button>
                        <?php } ?>
                    </td>
                <?php
                }

            }

            echo "</tr>";

        }

        echo "</tbody></table>";
    }


    if ($xls_rows > 0) {
        flush();

        $data = date("Y-m-d").".".date("H-i-s");
        // Alterar para de produção 
        //system("rm /tmp/assist/relatorio-consulta-pedido-$login_fabrica.csv");
        //$fp = fopen ("/tmp/assist/relatorio-consulta-pedido-$login_fabrica.csv","w");

        system("rm xls/relatorio-consulta-pedido-$login_fabrica.$data.csv");
        $fp = fopen ("xls/relatorio-consulta-pedido-$login_fabrica.$data.csv","w");

        fputs ($fp,"Relatório de Pedidos\n");

        $cabecalho = array();

        if($login_fabrica <> 153){
            $cabecalho[] = "Pedido";
        }

        if ($login_fabrica == 145) {
            $cabecalho[] = "Pedido Fabricante";
		}

        if ($login_fabrica == 85) {
            $cabecalho[] = "Cliente Contratual";
        }

        if ($login_fabrica == 95){
            $cabecalho[] = "Série";
        }

	if(!in_array($login_fabrica, array(95, 153)) && !$telecontrol_distrib){
            $cabecalho[] = "Pedido Cliente";
        }

		if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
            $cabecalho[] = "Tabela";
        }

        if ($login_fabrica == 72){
            $cabecalho[] = "Qtde de Itens";
            $cabecalho[] = "Ref. - Desc.";
            $cabecalho[] = "Qtde de Peças";
        }
        if($login_fabrica <> 153){
            $cabecalho[] = "Tipo";
        }
        if ($login_fabrica == 7){
            $cabecalho[] = "Origem (OS/Compra)";
            $cabecalho[] = "Solicitante (PTA/Cliente)";
            $cabecalho[] = "Admin";
        }

        if($login_fabrica == 151){
            $cabecalho[] = "Status OS";
        }

        if($login_fabrica <> 153){
            $cabecalho[] = "Status";
        }


        if ($login_fabrica == 45){
            $cabecalho[] = "Status Fabricante";
        }
        if($login_fabrica <> 153){
            $cabecalho[] = "Data";
        }

        if ($login_fabrica == 24){
            $cabecalho[] = "Recebido";
        }
        if($login_fabrica <> 153){
            $cabecalho[] = "Posto";
        }

        if (empty($peca_referencia_consulta)) {
            $cabecalho[] = "Qtde. Itens";
            $cabecalho[] = "Total Pedido";
        } else {
            $cabecalho[] = "Qtde. Peças";
            $cabecalho[] = "Total Item";
            $cabecalho[] = "Qtde. Itens";
            $cabecalho[] = "Total Pedido";
        }

        if ($login_fabrica == 14){
            $cabecalho[] = "Valor";
        }
        if ($login_fabrica == 146) {
            $cabecalho[] = "Marca";
        }
		if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
            $cabecalho[] = "Referencia";
            $cabecalho[] = "Descrição";
            $cabecalho[] = "Qtde";
            $cabecalho[] = "Qtde. cancelada";
            $cabecalho[] = "Qtde. faturada";
        }

        if (in_array($login_fabrica, array(85))) {
            $cabecalho[] = "Referência do Produto";
            $cabecalho[] = "Nº de Série do Produto";
            $cabecalho[] = "O.S.";
            $cabecalho[] = "Peças do Pedido";
            $cabecalho[] = "Preço";
            $cabecalho[] = "IPI";
            $cabecalho[] = "Total com IPI";
        }
	
        if (($telecontrol_distrib || $interno_telecontrol)) {
            $cabecalho[] = "Destinatário/Consumidor";
            $cabecalho[] = "Nota Fiscal";
            $cabecalho[] = "Emissão";
            $cabecalho[] = "Conhecimento";
            $cabecalho[] = "Condição de Pagamento";
            $cabecalho[] = "Atende Parcial";
            $cabecalho[] = "Preço Unitário";
            $cabecalho[] = "Preço";
            $cabecalho[] = "IPI";
            $cabecalho[] = "Total com IPI";
            $cabecalho[] = "Quantidade Faturada";
            $cabecalho[] = "Quantidade Cancelada";
            $cabecalho[] = "Pendência do Pedido";
        }

	if ($telecontrol_distrib && !$_POST['csv_detalhado']) {
                $cabecalho[] = "Componentes";
                $cabecalho[] = "Qtde";
		$cabecalho[] = "Estoque Distrib";
		for($k = 0; $k < 12; $k++){
			$cabecalho[] = "Ferramentas";
		}
	}

        fputs ($fp, implode(";", $cabecalho)."\n");
        for ($i = 0; $i < $xls_rows; $i++){

            $pedido            = pg_fetch_result($res, $i, "pedido");
            $seu_pedido        = pg_fetch_result($res, $i, "seu_pedido");
            $pedido_cliente    = pg_fetch_result($res, $i, "pedido_cliente");
            $descricao_tipo    = pg_fetch_result($res, $i, "descricao_tipo_pedido");
            $exportado         = pg_fetch_result($res, $i, "exportado");
            $situacao_pedido   = pg_fetch_result($res, $i, "status_pedido");
            $status            = ($login_fabrica == 2 && strlen($exportado) > 0) ? "OK" : pg_fetch_result($res, $i, "descricao_status_pedido");
            $data              = pg_fetch_result($res, $i, "data");
            $codigo_posto      = pg_fetch_result($res, $i, "codigo_posto");
            $posto_nome        = pg_fetch_result($res, $i, "posto_nome");
            $data_recebido     = pg_fetch_result($res, $i, "recebido_posto");
            $status_fabricante = pg_fetch_result($res, $i, "status_fabricante");
            $origem_cliente    = pg_fetch_result($res, $i, "origem_cliente");
            $pedido_os         = pg_fetch_result($res, $i, "pedido_os");
            $total             = pg_fetch_result($res, $i, "total");
            $login             = pg_fetch_result($res, $i, "login");
            if ($telecontrol_distrib || $interno_telecontrol) {
                $nome_destinatario = pg_fetch_result($res, $i, "nome_destinatario");
                $condicao_pagamento = pg_fetch_result($res,$i,'condicao_pagamento');
            }
            $pedido_fabricante = pg_fetch_result($res, $i, "pedido_fabricante");
            $cliente_descricao  = (!empty(pg_fetch_result ($res,$i,'codigo_cliente'))) ? trim(pg_result ($res,$i,'codigo_cliente'))."  - ".trim(pg_fetch_result ($res,$i,'nome_cliente')) : "";
            $cliente_id = pg_fetch_result($res, $i, "cliente_id");

			if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
                $tabela            = trim(pg_result ($res,$i,'descricao_tabela'));
                $referencia_peca   = trim(pg_result ($res,$i,'referencia_peca'));
                $descricao_peca    = trim(pg_result ($res,$i,'descricao_peca'));
                $qtde_faturada     = trim(pg_result ($res,$i,'qtde_faturada'));
                $qtde_cancelada    = trim(pg_result ($res,$i,'qtde_cancelada'));
                $qtde_pecas        = trim(pg_result ($res,$i,'qtde_pecas'));
            }

            if($login_fabrica == 151){
                $os_bloqueada           = pg_fetch_result($res, $i, 'os_bloqueada');
                $finalizada             = pg_fetch_result($res, $i, 'finalizada');

                if($os_bloqueada == 't' and empty($finalizada)){
                    $os_bloqueada= "Congelada";
                }elseif($os_bloqueada != 't' and empty($finalizada)){
                    $os_bloqueada= "Descongelada";
                }
            }

            if($login_fabrica == 153){
                $cnpj_posto           = pg_fetch_result($res, $i, "cnpj_posto");
                $descricao_tipo_posto = pg_fetch_result($res, $i, "descricao_tipo_posto");
            }

            if ($login_fabrica == 146) {
                $marca = pg_fetch_result($res, $i, "marca");
            }

            if (($telecontrol_distrib || $interno_telecontrol) && $_POST['csv_detalhado']) {
                $sql_dados_exel = " SELECT tbl_faturamento.nota_fiscal,
                                           tbl_faturamento.emissao,
                                           tbl_faturamento.conhecimento,
                                           tbl_pedido.atende_pedido_faturado_parcial AS atende_parcial,
                                           tbl_pedido_item.preco AS preco_unitario,
                                           tbl_pedido_item.qtde_faturada,
                                           tbl_pedido_item.qtde_cancelada,
                                           tbl_peca.ipi,
                                           tbl_pedido_item.qtde,
                                           (tbl_pedido_item.qtde * tbl_pedido_item.preco) AS preco,
                                           (tbl_pedido_item.qtde_faturada - tbl_pedido_item.qtde) AS pendencia_pedido      
                                    FROM tbl_pedido 
                                    JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido 
                                    JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                                    LEFT JOIN tbl_faturamento_item ON tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                                    LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                    WHERE tbl_pedido.fabrica = $login_fabrica 
                                    AND tbl_pedido.pedido = $pedido";
                                    
                $res_dados_exel = pg_query($con, $sql_dados_exel);
                
                $nota_fiscal_tc      = "";
                $emissao_tc          = "";
                $conhecimento_tc     = "";
                $atende_parcial_tc   = "";
                $preco_unitario_tc   = "";
                $qtde_faturada_tc    = "";
                $qtde_cancelada_tc   = "";
                $ipi_tc              = "";
                $qtde_tc             = "";
                $preco_tc            = "";
                $pendencia_pedido_tc = "";

                if (pg_num_rows($res_dados_exel) > 0) {
                    $nota_fiscal_tc      = pg_fetch_result($res_dados_exel, 0, 'nota_fiscal');
                    $emissao_tc          = pg_fetch_result($res_dados_exel, 0, 'emissao');
                    $conhecimento_tc     = pg_fetch_result($res_dados_exel, 0, 'conhecimento');
                    $atende_parcial_tc   = pg_fetch_result($res_dados_exel, 0, 'atende_parcial');
                    $atende_parcial_tc   = ($atende_parcial_tc == 't') ? 'Sim' : 'Não';
                    $preco_unitario_tc   = pg_fetch_result($res_dados_exel, 0, 'preco_unitario');
                    $qtde_faturada_tc    = pg_fetch_result($res_dados_exel, 0, 'qtde_faturada');
                    $qtde_cancelada_tc   = pg_fetch_result($res_dados_exel, 0, 'qtde_cancelada');
                    $ipi_tc              = pg_fetch_result($res_dados_exel, 0, 'ipi');
                    $qtde_tc             = pg_fetch_result($res_dados_exel, 0, 'qtde');
                    $preco_tc            = pg_fetch_result($res_dados_exel, 0, 'preco');
                    $pendencia_pedido_tc = pg_fetch_result($res_dados_exel, 0, 'pendencia_pedido');
                    $pendencia_pedido_tc = ($pendencia_pedido_tc < 0) ? 0 : $pendencia_pedido_tc;
                }
            }

            $linha = array();

            $pedido_aux2 = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;


            if($login_fabrica <> 153){
                $linha[] = $pedido_aux2;
            }

            if ($login_fabrica == 145) {
                $linha[] = $pedido_fabricante;
        	}

            if ($login_fabrica == 85) {
                $linha[] = $cliente_descricao;
            }

            if ($login_fabrica  == 95){
                $linha[] = $serie;
            }

            if(!in_array($login_fabrica, array(95, 153)) && !$telecontrol_distrib){
                $linha[] = $pedido_cliente;
            }

			if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
                $linha[] = $tabela;
            }

            if ($login_fabrica == 72){
                $qres    = pg_query($con, "SELECT SUM(qtde) AS qtde FROM tbl_pedido_item WHERE tbl_pedido_item.pedido = $pedido");
                $linha[] = pg_fetch_result($qres, 0, "qtde");

                $xsql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_pedido_item.qtde
                         FROM tbl_peca
                         INNER JOIN tbl_pedido_item ON tbl_pedido_item.peca = tbl_peca.peca
                         WHERE tbl_peca.fabrica = $login_fabrica
                         AND tbl_pedido_item.pedido = $pedido";
                $xres = pg_query($con, $xsql);

                $pecas = array();
                $qtdes = array();

                for ($y = 0; $y < pg_num_rows($xres); $y++){
                    $peca_referencia = pg_result($xres,$y,'referencia');
                    $peca_descricao  = pg_result($xres,$y,'descricao');
                    $qtde_i          = pg_result($xres,$y,'qtde');

                    $pecas[] = "$peca_referencia - $peca_descricao";
                    $qtdes[] = $qtde_i;
                }

                $linha[] = implode(",", $pecas);
                $linha[] = implode(",", $qtdes);
            }

            if($login_fabrica <> 153){
                $linha[] = $descricao_tipo;
            }

            if ($login_fabrica == 7){
                $linha[] = ($pedido_os = "t") ? "Ordem Serviço" : "Compra Manual";
                $linha[] = ($origem_cliente == "t") ? "Cliente" : "PTA";
                $linha[] = $login;
            }

            if($login_fabrica == 151){
                $linha[] = $os_bloqueada;
            }

            if($login_fabrica <> 153){
                $linha[] = $status;
            }

            if ($login_fabrica == 45){
                $linha[] = $status_fabricante;
            }

            if($login_fabrica <> 153){
                $linha[] = $data;
            }

            if ($login_fabrica == 24){
                $linha[] = $data_recebido;
            }

            if($login_fabrica <> 153){
                $linha[] = "$codigo_posto - $posto_nome";
            }

            //caso exista peça
            if (!empty($peca_referencia_consulta)) {
                $sql_pedido_peca = "SELECT sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as total_pedido, SUM(tbl_pedido_item.qtde) as qtd_total, COUNT(*) as qtd_item
                                FROM  tbl_pedido
                                JOIN  tbl_pedido_item USING (pedido)
                                JOIN  tbl_peca        USING (peca)
                                WHERE tbl_pedido_item.pedido = $pedido
                                AND tbl_peca.referencia = '$peca_referencia_consulta'
                                GROUP BY tbl_pedido.pedido";

                $res_pedido_peca = pg_query($con, $sql_pedido_peca);

                $total_pedido_peca = pg_result($res_pedido_peca,0,'total_pedido');
                $qtd_total_peca = pg_result($res_pedido_peca,0,'qtd_total');
                $qtd_item_peca = pg_result($res_pedido_peca,0,'qtd_item');
            }

            $sql_pedido = "SELECT sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as total_pedido, SUM(tbl_pedido_item.qtde) as qtd_total, COUNT(*) as qtd_item
                            FROM  tbl_pedido
                            JOIN  tbl_pedido_item USING (pedido)
                            JOIN  tbl_peca        USING (peca)
                            WHERE tbl_pedido_item.pedido = $pedido
                            GROUP BY tbl_pedido.pedido";

            $res_pedido = pg_query($con, $sql_pedido);

            $total_pedido = pg_result($res_pedido,0,'total_pedido');
            $qtd_total = pg_result($res_pedido,0,'qtd_total');
            $qtd_item = pg_result($res_pedido,0,'qtd_item');

            if ($login_fabrica == 85) {

                $joinClienteContratual = "";
                if (!empty($cliente_id)) {
                        $joinClienteContratual = " JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
                                                   JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado 
                                                   AND tbl_hd_chamado.cliente = $cliente_id";

                        $sql_cliente = "SELECT sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as total_pedido, SUM(tbl_pedido_item.qtde) as qtd_total, COUNT(*) as qtd_item
                            FROM tbl_os
                            JOIN tbl_os_produto USING(os)
                            JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
                            JOIN tbl_produto ON tbl_os_produto.produto=tbl_produto.produto
                            JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
                            JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                            {$joinClienteContratual}
                           WHERE tbl_pedido_item.pedido=$pedido
                             AND tbl_os.fabrica=$login_fabrica";
                        $res_cliente = pg_query($con, $sql_cliente);

                        $total_pedido = pg_result($res_cliente,0,'total_pedido');
                        $qtd_total    = pg_result($res_cliente,0,'qtd_total');
                        $qtd_item     = pg_result($res_cliente,0,'qtd_item');

                }
            }

            $total_pedido_formatado      = number_format($total_pedido, 2, ",", "");
            $total_pedido_peca_formatado = number_format($total_pedido_peca, 2, ",", "");


            if (!empty($peca_referencia_consulta)) {
                $linha[] = $qtd_total_peca;
                $linha[] = $total_pedido_peca_formatado;
                $linha[] = $qtd_item;
                $linha[] = $total_pedido_formatado;
	    } else {
		if($login_fabrica == 91){
			 $linha[] = $qtd_total;
		}else{
			$linha[] = $qtd_item;
		}
                $linha[] = $total_pedido_formatado;
            }

            if ($login_fabrica == 14){
                $linha[] = number_format($total, 2, ",", ".");
            }

            if ($login_fabrica == 146) {
                $linha[] = $marca;
            }

			if (($login_fabrica == 160 or $replica_einhell) && $_POST['csv_detalhado']) {
                $linha[] = $referencia_peca;
                $linha[] = $descricao_peca;
                $linha[] = $qtde_pecas;
                $linha[] = $qtde_cancelada;
                $linha[] = $qtde_faturada;
            }

            if (($telecontrol_distrib || $interno_telecontrol) && $_POST['csv_detalhado']) {
                $linha[] = $nome_destinatario;
                $linha[] = $nota_fiscal_tc;
                $linha[] = $emissao_tc;
                $linha[] = $conhecimento_tc;
                $linha[] = $condicao_pagamento;
                $linha[] = $atende_parcial_tc;
                $linha[] = $preco_unitario_tc;
                $linha[] = $preco_tc;
                $linha[] = $total_pedido;
                $linha[] = $qtde_faturada_tc;
                $linha[] = $qtde_cancelada_tc;
                $linha[] = $pendencia_pedido_tc;
                $linha[] = $ipi_tc;
            }

            if ($login_fabrica == 85) {
                $serie_produto_csv      = array();
                $oss_csv                = array();
                $referencia_produto_csv = array();
                $descricao_peca_csv     = array();
                $preco_peca_csv         = array();
                $ipi_item_csv           = array();
                $total_ped_csv          = array();

                $sqls_csv = " SELECT DISTINCT tbl_os.os, tbl_os.serie, tbl_produto.referencia
                            FROM tbl_os
                            JOIN tbl_os_produto USING(os)
                            JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
                            JOIN tbl_produto ON tbl_os_produto.produto=tbl_produto.produto
                            JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
                           WHERE tbl_pedido_item.pedido=$pedido
                             AND tbl_os.fabrica=$login_fabrica";
                $ress_csv = pg_query($con, $sqls_csv);

                $ress_tot = pg_num_rows($ress_csv);
                if ($ress_tot > 0) {
                    $dados_csv = pg_fetch_all($ress_csv);
                }

                $sqlPeca_csv = "SELECT 
                                 tbl_os_item.pedido,
                                 tbl_os_item.qtde,
                                 tbl_peca.referencia || ' - ' || tbl_peca.descricao AS descricao,
                                 tbl_pedido_item.preco,
                                 tbl_peca.ipi
                                FROM    tbl_os
                                JOIN    tbl_os_produto USING (os)
                                JOIN    tbl_os_item USING (os_produto)
                                JOIN    tbl_peca ON tbl_os_item.peca = tbl_peca.peca
                                JOIN    tbl_pedido_item ON tbl_pedido_item.pedido = $pedido 
                                AND     tbl_os_item.peca = tbl_pedido_item.peca
                                WHERE   tbl_os_item.pedido = $pedido
                                AND     tbl_os.fabrica   = $login_fabrica";
                $resPeca_csv = pg_query($con, $sqlPeca_csv);

                $resPeca_tot = pg_num_rows($resPeca_csv);
                if ($resPeca_tot > 0) {
                    $dadospeca_csv = pg_fetch_all($resPeca_csv);
                }

                $tot_loop = ($resPeca_tot > $ress_tot) ? $resPeca_tot : $ress_tot;
                $linha2 = array();

                for ($j=0; $j < $tot_loop ; $j++) {

                    if ($j == 0) {
                        if ($ress_tot > 0) {
                                $linha[] = $dados_csv[$j]['referencia'];
                                $linha[] = $dados_csv[$j]['serie'];
                                $linha[] = $dados_csv[$j]['os'];
                        } else {
                            $linha[] = '';
                            $linha[] = '';
                            $linha[] = '';
                        }
                        if ($resPeca_tot > 0) {
                            $linha[] = $dadospeca_csv[$j]['descricao'];
                            $linha[] = number_format($dadospeca_csv[$j]['preco'], 2, ".", ",");
                            $linha[] = number_format($dadospeca_csv[$j]['ipi'], 2, ".", ",")."%";
                            $linha[] = number_format(($dadospeca_csv[$j]['qtde']*$dadospeca_csv[$j]['preco'])*(1+$dadospeca_csv[$j]['ipi'] / 100), 2, ".", ",");
                        } else {
                            $linha[] = '';
                            $linha[] = '';
                            $linha[] = '';
                        }
                    } else {
                        if ($ress_tot > 0) {
                            $linha2[$i][$j][] = $dados_csv[$j]['referencia'];
                            $linha2[$i][$j][] = $dados_csv[$j]['serie'];
                            $linha2[$i][$j][] = $dados_csv[$j]['os'];
                        } else {
                            $linha2[$i][$j][] = '';
                            $linha2[$i][$j][] = '';
                            $linha2[$i][$j][] = '';
                        }
                        
                        if ($resPeca_tot > 0) {
                            $linha2[$i][$j][] = $dadospeca_csv[$j]['descricao'];                    
                            $linha2[$i][$j][] = number_format($dadospeca_csv[$j]['preco'], 2, ".", ",");
                            $linha2[$i][$j][] = number_format($dadospeca_csv[$j]['ipi'], 2, ".", ",")."%";
                            $linha2[$i][$j][] = number_format(($dadospeca_csv[$j]['qtde']*$dadospeca_csv[$j]['preco'])*(1+$dadospeca_csv[$j]['ipi'] / 100), 2, ".", ",");
                        } else {
                            $linha2[$i][$j][] = '';
                            $linha2[$i][$j][] = '';
                            $linha2[$i][$j][] = '';
                        }
                    }
                }
            }
 
 	    if (($telecontrol_distrib && !$_POST['csv_detalhado']) || ($telecontrol_distrib || $interno_telecontrol && !$_POST['csv_detalhado'])) {
		    $xsqlRefPeca = "SELECT tbl_peca.referencia,
                        tbl_peca.peca, 
			    		tbl_peca.descricao, 
		   		    	tbl_posto_estoque.qtde AS qtde_distrib, 
			    		tbl_pedido_item.qtde,
			    		(SELECT array_to_string(array_agg(tbl_produto.referencia),';')
			    		FROM    tbl_produto
			    		JOIN    tbl_lista_basica USING (produto)
			    		WHERE   tbl_lista_basica.fabrica   = tbl_peca.fabrica
			    		AND     tbl_lista_basica.peca = tbl_peca.peca) AS ferramentas
			    	    FROM tbl_peca
			    	    JOIN tbl_pedido_item ON tbl_pedido_item.peca = tbl_peca.peca
			    	    JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca
			    	    WHERE tbl_peca.fabrica = $login_fabrica
				    AND tbl_pedido_item.pedido = $pedido";
                $resRefPeca = pg_query($con, $xsqlRefPeca);
                if (pg_num_rows($resRefPeca) > 0) {
                    foreach (pg_fetch_all($resRefPeca) as $key => $rows) {
                        if ($telecontrol_distrib || $interno_telecontrol) {
                            $peca_tc = $rows['peca'];
                            $sql_dados_exel = " SELECT tbl_faturamento.nota_fiscal,
                                               tbl_faturamento.emissao,
                                               tbl_faturamento.conhecimento,
                                               tbl_pedido.atende_pedido_faturado_parcial AS atende_parcial,
                                               tbl_pedido_item.preco AS preco_unitario,
                                               (tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as total_pedido,
                                               tbl_pedido_item.qtde_faturada,
                                               tbl_pedido_item.qtde_cancelada,
                                               tbl_peca.ipi,
                                               tbl_pedido_item.qtde,
                                               (tbl_pedido_item.qtde * tbl_pedido_item.preco) AS preco,
                                               (tbl_pedido_item.qtde_faturada - tbl_pedido_item.qtde) AS pendencia_pedido      
                                        FROM tbl_pedido 
                                        JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido 
                                        JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                                        LEFT JOIN tbl_faturamento_item ON tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                                        LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                        WHERE tbl_pedido.fabrica = $login_fabrica 
                                        AND tbl_pedido.pedido = $pedido
                                        AND tbl_peca.peca = $peca_tc";
                                        
                        $res_dados_exel = pg_query($con, $sql_dados_exel);
                        
                        $nota_fiscal_tc      = "";
                        $emissao_tc          = "";
                        $conhecimento_tc     = "";
                        $atende_parcial_tc   = "";
                        $preco_unitario_tc   = "";
                        $qtde_faturada_tc    = "";
                        $qtde_cancelada_tc   = "";
                        $ipi_tc              = "";
                        $qtde_tc             = "";
                        $preco_tc            = "";
                        $pendencia_pedido_tc = "";

                        if (pg_num_rows($res_dados_exel) > 0) {
                            
                                $nota_fiscal_tc      = pg_fetch_result($res_dados_exel, 0, 'nota_fiscal');
                                $emissao_tc          = pg_fetch_result($res_dados_exel, 0, 'emissao');
                                $conhecimento_tc     = pg_fetch_result($res_dados_exel, 0, 'conhecimento');
                                $atende_parcial_tc   = pg_fetch_result($res_dados_exel, 0, 'atende_parcial');
                                $atende_parcial_tc   = ($atende_parcial_tc == 't') ? 'Sim' : 'Não';
                                $preco_unitario_tc   = pg_fetch_result($res_dados_exel, 0, 'preco_unitario');
                                $total_pedido_tc     = pg_fetch_result($res_dados_exel, 0, 'total_pedido');
                                $total_pedido_tc     = number_format($total_pedido_tc, 2, ",", "");
                                $qtde_faturada_tc    = pg_fetch_result($res_dados_exel, 0, 'qtde_faturada');
                                $qtde_cancelada_tc   = pg_fetch_result($res_dados_exel, 0, 'qtde_cancelada');
                                $ipi_tc              = pg_fetch_result($res_dados_exel, 0, 'ipi');
                                $qtde_tc             = pg_fetch_result($res_dados_exel, 0, 'qtde');
                                $preco_tc            = pg_fetch_result($res_dados_exel, 0, 'preco');
                                $pendencia_pedido_tc = pg_fetch_result($res_dados_exel, 0, 'pendencia_pedido');
                                $pendencia_pedido_tc = ($pendencia_pedido_tc < 0) ? 0 : $pendencia_pedido_tc;
                            
                        }
                    }

                        $linhax = $linha;
                        if ($telecontrol_distrib || $interno_telecontrol) {
                            $linhax[] = $nome_destinatario;
                            $linhax[] = $nota_fiscal_tc;
                            $linhax[] = $emissao_tc;
                            $linhax[] = $conhecimento_tc;
                            $linhax[] = $condicao_pagamento;
                            $linhax[] = $atende_parcial_tc;
                            $linhax[] = $preco_unitario_tc;
                            $linhax[] = $preco_tc;
                            $linhax[] = $ipi_tc;
                            $linhax[] = $total_pedido_tc;
                            $linhax[] = $qtde_faturada_tc;
                            $linhax[] = $qtde_cancelada_tc;
                            $linhax[] = $pendencia_pedido_tc;
                        }
                        $linhax[] = $rows['referencia']. ' - ' . $rows['descricao'];
                        $linhax[] = $rows['qtde'];
    		            $linhax[] = $rows['qtde_distrib'];
    		            $linhax[] = $rows['ferramentas'];
                        fputs($fp, implode(";", $linhax)."\n");
                    }
                }
	    }else{
		fputs($fp, implode(";", $linha)."\n");
	    }

            if (!empty($linha2)) {
                foreach ($linha2[$i] as $key_linha2 => $value_linha2) {
                    fputs($fp,";;;;;;;;;".implode(";", $value_linha2)."\n");
                }
            }
        }

        fputs ($fp, "Total de Pedidos;{$xls_rows}");

        fclose ($fp);

        $data = date("Y-m-d").".".date("H-i-s");
        // alterar para produção 
        //rename("/tmp/assist/relatorio-consulta-pedido-$login_fabrica.csv", "xls/relatorio-consulta-pedido-$login_fabrica.$data.csv");
        //rename("/home/kaique/public_html/PosVenda/admin/xls/relatorio-consulta-pedido-$login_fabrica.csv", "xls/relatorio-consulta-pedido-$login_fabrica.$data.csv");

        if ($login_fabrica != 153 && (isset($_POST['csv'])) && $_POST['csv'] == 'csv') {
            echo"
                <table width='300' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
                    <tr>
                        <td align='left' valign='absmiddle'>
                            <a href='xls/relatorio-consulta-pedido-$login_fabrica.$data.csv' target='_blank'>
                                <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>    Download do Arquivo CSV
                            </a>
                        </td>
                    </tr>
                </table>
            ";
        }

        /**
         * CSV detalhado por Itens do Pedido
         */
        if (in_array($login_fabrica, array(40, 151, 153))) {
            $xlsdata = date ("d/m/Y H:i:s");

            system("rm /tmp/assist/relatorio-consulta-pedido-detalhado-$login_fabrica.csv");
            $fp = fopen ("/tmp/assist/relatorio-consulta-pedido-detalhado-$login_fabrica.csv","w");

            fputs ($fp,"RELATÓRIO DETALHADO DE PEDIDOS\n");

            if ($login_fabrica == 40) {
                $cabecalho = array(
                    "POSTO AUTORIZADO",
                    "CNPJ POSTO AUTORIZADO",
                    "PEDIDO",
                    "TIPO",
                    "STATUS",
                    "DATA",
                    "REFERÊNCIA PEÇA",
                    "DESCRIÇÃO PEÇA",
                    "PREÇO UNITÁRIO",
                    "QTDE SOLICITADA",
                    "QTDE FATURADA",
                    "QTDE CANCELADA",
                    "QTDE PENDENTE"
                );
            } else if ($login_fabrica == 153) {
                $cabecalho = array(
                    "CNPJ",
                    "NOME",
                    "TIPO POSTO",
                    "PEDIDO",
                    "PEDIDO CLIENTE",
                    "TIPO",
                    "STATUS",
                    "DATA",
                    "REFERÊNCIA PEÇA",
                    "DESCRIÇÃO PEÇA",
                    "QTDE FATURADA",
                    "QTDE PENDENTE"
                );
            } else if ($login_fabrica == 151) {
                $cabecalho = array(
                    "PEDIDO",
                    "TIPO DE PEDIDO",
                    "DATA DE ABERTURA DO PEDIDO",
                    "STATUS PEDIDO",
                    "STATUS OS",
                    "NOTA FISCAL",
                    "DATA DE EMISSÃO DA NOTA",
                    "DATA DE SAÍDA DA NOTA FISCAL",
                    "QUANTIDADE DAS PEÇAS SOLICITADAS",
                    "QUANTIDADE FATURADA",
                    "QUANTIDADE PENDENTE",
                    "DATA DA POSTAGEM",
                    "DATA DA ENTREGA",
                    "CÓDIGO DO POSTO",
                    "POSTO",
                    "CÓDIGO DO COMPONENTE",
                    "COMPONENTE",
                    "ITEM DA PEÇA",
                    "RASTREIO"
                );
            }

            fputs ($fp, implode(";", $cabecalho)."\n");

            while ($row = pg_fetch_object($res)) {

                if($login_fabrica == 151){
                    $os_bloqueada           = $row->os_bloqueada;
                    $finalizada             = $row->finalizada;

                    if($os_bloqueada == 't' and empty($finalizada)){
                        $os_bloqueada= "Congelada";
                    }elseif($os_bloqueada != 't' and empty($finalizada)){
                        $os_bloqueada= "Descongelada";
                    }
                }

                /**
                 * Ordem de Campos no array $itens
                 *
                 * FÃ¡brica 40:
                 * 0 - referÃªncia da peça
                 * 1 - descrição da peça
                 * 2 - preço unitÃ¡rio da peça
                 * 3 - quantidade solicitada da peça
                 * 4 - quantidade faturada da peça
                 * 5 - quantidade cancelada da peça
                 * 6 - quantidade pendente da peça
                 *
                 * FÃ¡brica 151:
                 * 0  - nÃºmero da nota fiscal
                 * 1  - data de emissão da nota fiscal
                 * 2  - data de saÃ­da da nota fiscal
                 * 3  - quantidade
                 * 4  - quantidade faturada
                 * 5  - quantidade pendente
                 * 6  - data de postagem
                 * 7  - data de entrega
                 * 8  - referÃªncia da peça
                 * 9  - descrição da peça
                 * 10 - json do código de rastreio (pode ter mais que um)
                 *
                 * FÃ¡brica 153:
                 * 0 - referÃªncia da peça
                 * 1 - descrição da peça
                 * 2 - quantidade faturada
                 * 3 - quantidade pendente
                 */
				$itens = pg_format_array_multidimensional($row->itens);
				if($login_fabrica == 151) {
					$itens = unique_multidim_array($itens,'8');
				}
				$n = 1;
				foreach ($itens as $item_pedido => $peca) {
                    if ($login_fabrica == 153) {
                        if($peca[3] < 0){ //hd_chamado=3024788
                            $peca[3] = 0;
                        }
                        $linha = array(
                            $row->cnpj_posto,
                            $row->posto_nome,
                            $row->descricao_tipo_posto,
                            $row->pedido,
                            $row->pedido_cliente,
                            $row->descricao_tipo_pedido,
                            $row->descricao_status_pedido,
                            $row->data,
                            $peca[0],
                            $peca[1],
                            $peca[2],
                            $peca[3]
                        );
                    } else if ($login_fabrica == 151) {
                        $rastreio = json_decode($peca[10], true);

                        $linha = array(
                            $row->pedido,
                            $row->descricao_tipo_pedido,
                            $row->data,
                            $row->descricao_status_pedido,
                            $os_bloqueada,
                            $peca[0],
                            $peca[1],
                            $peca[2],
                            $peca[3],
                            $peca[4],
                            $peca[5],
                            $peca[6],
                            $peca[7],
                            $row->codigo_posto,
                            $row->posto_nome,
                            $peca[8],
                            $peca[9],
                            $n,
                            implode(",", $rastreio)
                        );
                    } else if ($login_fabrica == 40) {
                        $auxiliar = explode(',', $peca[0]);
                        $auxiliar[2] = number_format($auxiliar[2],2,",","");

                        $linha = array(
                            $row->posto_nome,
                            $row->cnpj_posto,
                            $row->pedido,
                            $row->descricao_tipo_pedido,
                            $row->descricao_status_pedido,
                            $row->data,
                            $auxiliar[0],
                            $auxiliar[1],
                            $auxiliar[2],
                            $auxiliar[3],
                            $auxiliar[4],
                            $auxiliar[5],
                            $auxiliar[6]
                        );

                        unset($auxiliar);
                    }

					fputs($fp, implode(";", $linha)."\n");
					$n++;
                }
            }

            fclose ($fp);

            $data = date("Y-m-d").".".date("H-i-s");

            rename("/tmp/assist/relatorio-consulta-pedido-detalhado-$login_fabrica.csv", "xls/relatorio-consulta-pedido-detalhado-$login_fabrica.$data.csv");

            echo"
                <br />
                <table width='300' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
                    <tr>
                        <td align='left' valign='absmiddle'>
                            <a href='xls/relatorio-consulta-pedido-detalhado-$login_fabrica.$data.csv' target='_blank'>
                                <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>    Download do Arquivo CSV Detalhado
                            </a>
                        </td>
                    </tr>
                </table>
            ";
        }
    }

    if($login_fabrica == 50) {
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>";
    echo "<tr>";
            echo "<td colspan='7'>";
            echo "";
            echo "</td>";
            echo "<td colspan='2'>";
            echo "<a href='javascript:imprimirSelecionados()' style='font-size:10px'>Imprime Selecionados</a>";
            echo "</td>";
    echo "</tr>";
    echo "</TABLE>\n";
    }

        echo "<br><TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
        echo "<TR>";
        echo "<td align='center'>";
        if($login_fabrica == 101){
            echo "&nbsp; &nbsp; &nbsp; <input type='button' value='Imprimir' onclick='window.print();'>";
        }
        echo "</td>";
        echo "</TR>";
        echo "</TABLE>";

        echo "<br>";

}
?>
<p>
</div>
