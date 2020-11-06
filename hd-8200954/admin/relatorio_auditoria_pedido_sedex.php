<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

#TRATAMENTO DA MESANGEM DE ERRO
$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";
$msgErrorPattern03 = "Selecione posto para pesquisa.";
$msgErrorPattern04 = "Nenhum resultado encontrado.";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$tipo_busca = $_GET["busca"];
 
	if (strlen($q)>2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
		          FROM tbl_posto
		          JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		         WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {

			for ($i=0; $i<pg_numrows ($res); $i++ )
			{
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome";
				echo "\n";
			}
		}
	}
	exit;
}

if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
else                                   $data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)   $data_final = $_GET['data_final'];
else                                   $data_final = $_POST['data_final'];

if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];
else                                   $codigo_posto = $_POST['codigo_posto'];

if (strlen($_GET['referencia']) > 0)   $referencia = $_GET['referencia'];
else                                   $referencia = $_POST['referencia'];

if (strlen($_GET['descricao']) > 0)    $descricao = $_GET['descricao'];
else                                   $descricao = $_POST['descricao'];

if (strlen($_GET['btn_gravar']) > 0)   $btn_gravar = $_GET['btn_gravar'];
else                                   $btn_gravar = $_POST['btn_gravar'];

/***************************************************************************************
** Validacao da data, para nao permitir que o form seja submetido com campos em branco
***************************************************************************************/
if ($_POST['btn_gravar'] == "Pesquisar") {
	$status_pedido = $_POST["status_pedido"];
	if ((empty($data_inicial) || empty($data_final)) && ($login_fabrica == 1 && $status_pedido != 18)) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}

	if (!empty($data_inicial) && !empty($data_final)) {
        if (strlen($msg_erro) == 0) {
            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi))
            {
                $msg_erro["msg"][]    = $msgErrorPattern01;
                $msg_erro["campos"][] = "data";
            }
        }

        if (strlen($msg_erro) == 0) {
            list($df, $mf, $yf) = explode("/", $data_final);
            if(!checkdate($mf,$df,$yf))
            {
                $msg_erro["msg"][]    = $msgErrorPattern01;
                $msg_erro["campos"][] = "data";
            }
        }

        if (strlen($msg_erro) == 0) {
            $aux_data_inicial = $yi."-".$mi."-".$di;
            $aux_data_final = "$yf-$mf-$df";
        }

        if (strlen($msg_erro)==0) {
            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = $msgErrorPattern01;
                $msg_erro["campos"][] = "data";
            }
        }
	}

    $peca               = $_POST["peca"];
    $peca_referencia    = $_POST["peca_referencia"];
    $peca_descricao     = $_POST["peca_descricao"];

    if(!empty($peca)){
        $sql_peca = " and tbl_pedido_item.peca = $peca ";
    }


    ######## Produto Composto - Fujitsu [138] HD 2541097 (01/10/2015)#########
    if (strlen(trim($status_pedido))>0) {
        if($status_pedido == 1 OR $status_pedido == 20) {
            $campo_data_aprov = " TO_CHAR(tbl_pedido_status.data,'DD/MM/YYYY  HH24:MI:SS') AS data_aprovacao,\n";
            $campo_data_aprov_temp = "data_aprovacao,";
            $group_by = ", tbl_pedido_status.data";
            $join_data_aprov = " LEFT JOIN tbl_pedido_status ON  tbl_pedido_status.pedido = tbl_pedido.pedido
                                                             AND tbl_pedido_status.status = 1
            ";
            $sql_status_pedido = " AND tbl_pedido.status_pedido not in (18,14)  ";

            $pedido_status = " and pedido_sedex is false ";
            
            if($status_pedido == 20){
                $cond_aprovado_automatico = "and pedido_status_observacao = 'Aprovado Automaticamente' ";
            }else{
                $cond_status_pedido = " and nome_admin IS NOT NULL ";    
            }            
        }else{
            $sql_status_pedido = " AND tbl_pedido.status_pedido = $status_pedido ";
        }

        if($status_pedido != 20){
            $sql_demanda = " and (tbl_pedido_item.valores_adicionais::JSON->>'demanda' = 'true' AND tbl_pedido.valores_adicionais::JSON->>'pendencia_aprovacao_admin' is null)  ";
        }
    }
    if(strlen($codigo_posto)>0  and  strlen($posto_nome)>0) {
        $sql_posto = "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
    }

    if (!empty($data_inicial) && !empty($data_final)) {
        $sql_data = " AND tbl_pedido.finalizado between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
    }

    $sql = "
        SELECT  tbl_pedido.pedido,
                tbl_pedido.seu_pedido,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome,
                tbl_posto_fabrica.categoria, 
                tbl_posto_fabrica.contato_cidade,
                tbl_tipo_posto.descricao as descricao_tipo_posto,
                tbl_posto_fabrica.contato_estado,
                SUM (
                        CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                            ((tbl_pedido_item.preco_base / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6) * tbl_pedido_item.qtde)
                        ELSE
                            (tbl_pedido_item.qtde * tbl_pedido_item.preco)
                        END
                ) AS total_sem_ipi,
                SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco) as total,
                SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde*tbl_pedido_item.ipi )  as total_com_ipi,
                tbl_pedido.data,
                tbl_pedido.finalizado,
                ( select observacao from tbl_pedido_status where pedido = tbl_pedido.pedido order by pedido_status desc limit 1) as pedido_status_observacao,
                $campo_data_aprov
                (
                    SELECT  tbl_admin.nome_completo
                    FROM    tbl_pedido_status
               LEFT JOIN    tbl_admin ON tbl_admin.admin = tbl_pedido_status.admin
                    WHERE   tbl_pedido_status.pedido = tbl_pedido.pedido
              ORDER BY      data DESC
                    LIMIT   1
                ) AS nome_admin

            INTO TEMP tmp_pedido_black_$login_admin
            FROM tbl_pedido
            $join_data_aprov
            INNER JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = $login_fabrica
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_pedido_item on tbl_pedido_item.pedido = tbl_pedido.pedido
            JOIN tbl_peca USING(peca)
            join tbl_tipo_posto on tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto and tbl_tipo_posto.fabrica = $login_fabrica 
            WHERE tbl_pedido.fabrica = $login_fabrica

            $sql_demanda 

            $sql_posto
            $sql_data
            $sql_peca 
            AND tbl_pedido.finalizado is not null
            AND (
                SELECT pedido_status 
                FROM tbl_pedido_status
                WHERE tbl_pedido_status.pedido = tbl_pedido.pedido
                AND tbl_pedido_status.observacao != 'Pedido Dewalt Rental excedeu o valor permitido'
                ORDER BY tbl_pedido_status.data DESC
                LIMIT 1
            ) IS NOT NULL
            AND tbl_pedido.finalizado > '2016-09-29 00:00' /* data de efetivacao */
            $sql_status_pedido
            $pedido_sedex 
            GROUP BY
            tbl_pedido.pedido, tbl_pedido.seu_pedido, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto_fabrica.categoria, 
            tbl_posto_fabrica.contato_cidade, tbl_tipo_posto.descricao, tbl_posto_fabrica.contato_estado,
            tbl_pedido.data, tbl_pedido.finalizado$group_by;

            SELECT pedido, seu_pedido, codigo_posto, nome, categoria,   data, descricao_tipo_posto, finalizado,$campo_data_aprov_temp contato_estado, contato_cidade, nome_admin, pedido_status_observacao, 
                    case when total_sem_ipi <> total then total_sem_ipi else total end as total,
                    case when total_sem_ipi <> total then total else total_com_ipi end as total_com_ipi
                    FROM tmp_pedido_black_$login_admin
                    where 1 = 1 

                    $cond_status_pedido
                    
                    $cond_aprovado_automatico 
                     ";
    $resx = pg_query($con,$sql);

    $qtdeRegistros = pg_num_rows($resx);

    if(isset($_POST['gerar_excel'])){
        $filename = "relatorio-auditoria-pedido-sedex-".date('Ydm').".csv";
        $file     = fopen("/tmp/{$filename}", "w");

        $thead = "Código Posto; Nome Posto; Cidade; UF; Pedido; Tipo Posto; Categoria Posto; Total com IPI; Abertura; Finalizada;Data Aprovação;Motivo;Obs Motivo;Responsável;\n\r";

        fwrite($file, "$thead");

        for ($i=0; $i<pg_num_rows($resx); $i++) {
            $seu_pedido     = trim(pg_result($resx,$i,'seu_pedido'));
            $pedido         = trim(pg_result($resx,$i,'pedido'));
            $codigo_posto   = trim(pg_result($resx,$i,'codigo_posto'));
            $posto_nome     = trim(pg_result($resx,$i,'nome'));
            $total          = trim(pg_result($resx,$i,'total'));
            $data           = mostra_data(substr(trim(pg_result($resx,$i,'data')),0,10));
            $finalizado     = mostra_data(substr(trim(pg_result($resx,$i,'finalizado')),0 ,10 ));
            if($status_pedido == 1 OR $status_pedido == 20) {
                $data_aprovacao = pg_result($resx,$i,'data_aprovacao');
            }
            $contato_estado = trim(pg_result($resx,$i,'contato_estado'));
            $contato_cidade = trim(pg_result($resx,$i,'contato_cidade'));
            $nome_admin     = trim(pg_result($resx,$i,'nome_admin'));
            $total_com_ipi  = trim(pg_result($resx,$i,'total_com_ipi'));
            $descricao_tipo_posto = trim(pg_result($resx,$i,'descricao_tipo_posto'));
            $categoria      = trim(pg_result($resx,$i,'categoria'));
            $pedido_status_observacao = trim(pg_result($resx, $i, 'pedido_status_observacao')); 

            $posto_nome = str_replace(",", "", $posto_nome);

            $total = number_format($total, 2, ',', ' ');
            $total_com_ipi = number_format($total_com_ipi, 2, '.', ' ');

            if($status_pedido == 20 and empty($nome_admin)){
                $nome_admin = "Automático";
            }

            $pedido_status_observacao = explode("|", $pedido_status_observacao);

            $motivo         = $pedido_status_observacao[0];
            $obs_motivo     = $pedido_status_observacao[1];

            $tbody .= "$codigo_posto;$posto_nome;$contato_cidade;$contato_estado;$seu_pedido;$descricao_tipo_posto;$categoria;$total_com_ipi;$data;$finalizado;$data_aprovacao;$motivo;$obs_motivo;$nome_admin; \r\n";


        }

        fwrite($file, "$tbody");
        fclose($file);

        if (file_exists("/tmp/{$filename}")) {
            system("mv /tmp/{$filename} xls/{$filename}");

            echo "xls/{$filename}";
        }
        
        exit;
    }

}


if ($login_fabrica == 15 and (count($_POST['codigo_posto']) == 0 AND count($_POST['referencia']) == 0)) {
	$msg_erro["msg"][]    = $msgErrorPattern03;
	$msg_erro["campos"][] = "posto";
}

if (strlen($codigo_posto)>0) {

	$sql = "SELECT posto
	          FROM tbl_posto_fabrica
	         WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) < 1) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "posto";
	} else {
		$posto = pg_result($res,0,0);
		if (strlen($posto)==0) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "posto";
		} else {
			$cond_3 = " AND   tbl_os.posto   = $posto ";
		}
	}
}


$layout_menu = "auditoria";
$title 		 = "RELATÓRIO DE PEDIDOS - PEDIDOS NÃO SEDEX";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

?>

<script type="text/javascript">

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("td.pecas-pedido").on("click", function(){
			var pedido = $(this).data("pedido");
            Shadowbox.open({
                content:"pecas_pedido.php?pedido="+pedido,
                player:"iframe",
                width:1100,
                height:500,
                options: {  
                        onClose: function() {
                            if ($("#aprovou_pedido").val() == 1) {
                                $("input[name=btn_gravar]").click();
                            }
                        }
                }
            });    
		});
	});

	function retorna_posto(retorno){
		console.log(retorno);
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

    function retorna_peca(retorno){
        $("#peca").val(retorno.peca);
        $("#peca_referencia").val(retorno.referencia);
        $("#peca_descricao").val(retorno.descricao);
    }

</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_relatorio' method='post' id='condicoes_cadastradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<?php
if ($login_fabrica != 1) {
?>
                                <h5 class='asteristico'>*</h5>
<?php
}
?>
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
<?php
if ($login_fabrica != 1) {
?>
                                <h5 class='asteristico'>*</h5>
<?php
}
?>
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
							<!--<h5 class='asteristico'>*</h5>-->
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_nome'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<!--<h5 class='asteristico'>*</h5>-->
							<input type="text" name="posto_nome" id="descricao_posto" class='span12' value="<? echo $posto_nome ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_referencia'>Ref. Peças</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='descrição peça'>Status do pedido</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<select name="status_pedido">
									<option value="18" <?php echo ($status_pedido == "18")? " selected " : " " ?> >Aguardando Aprovação</option>
                                    <option value="20" <?php echo ($status_pedido == "20")? " selected " : " " ?> >Aprovado automaticamente</option>
									<option value="1" <?php echo ($status_pedido == "1")? " selected " : " " ?> >Pedidos Aprovados</option>
									<option value="14" <?php echo ($status_pedido == "14")? " selected " : " " ?> >Pedidos Cancelados</option>
                                    
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>

			<center>
				<input type='submit' name='btn_gravar' value='Pesquisar' class='btn' />
				<input type='hidden' name='acao' value="<?=$acao?>" />
                <input type='hidden' name='peca' id="peca" value="<?=$peca?>" />
                <input type='hidden' name='aprovou_pedido' id="aprovou_pedido" value="" />
			</center>
			<br />

		</div>

	</form>
	</div>

<?php
if($_POST['btn_gravar'] == "Pesquisar") {

    if ($qtdeRegistros > 0) { ?>
            <br />
            <table class='table table-striped table-bordered table-hover table-fixed'>
                <thead>
                    <tr class='titulo_coluna'>
                        <th>Código Posto</th>
                        <th>Nome Posto</th>
                        <th>Cidade</th>
                        <th>UF</th>
                        <th>Pedido</th>
                        <th>Tipo Posto</th>
                        <th>Categoria Posto</th>
                        <th>Total com IPI</th>
                        <th>Abertura</th>
                        <th>Finalizada</th>
                        <th>Motivo</th>
                        <th>OBS Motivo</th>
<?php
        if($status_pedido == 1) {
?>
                        <th>Data Aprovação</th>
<?php
        }
?>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody>
<?php
        for ($i=0; $i<$qtdeRegistros; $i++) {
            $seu_pedido     = trim(pg_result($resx,$i,seu_pedido));
            $pedido         = trim(pg_result($resx,$i,pedido));
            $codigo_posto   = trim(pg_result($resx,$i,codigo_posto));
            $posto_nome     = trim(pg_result($resx,$i,nome));
            $total          = trim(pg_result($resx,$i,total));
            $data           = mostra_data(substr(trim(pg_result($resx,$i,data)),0,10));
            $finalizado     = mostra_data(substr(trim(pg_result($resx,$i,finalizado)),0 ,10 ));
            $data_aprovacao = pg_result($resx,$i,data_aprovacao);
            $contato_estado = trim(pg_result($resx,$i,contato_estado));
            $contato_cidade = trim(pg_result($resx,$i,contato_cidade));
            $nome_admin     = trim(pg_result($resx,$i,nome_admin));
            $total_com_ipi  = trim(pg_result($resx,$i,total_com_ipi));
            $descricao_tipo_posto = trim(pg_result($resx,$i,descricao_tipo_posto));
            $categoria      = trim(pg_result($resx,$i,categoria));
            $pedido_status_observacao = trim(pg_result($resx, $i, 'pedido_status_observacao'));

            $total = number_format($total, 2, ',', ' ');
            $total_com_ipi = number_format($total_com_ipi, 2, ',', ' ');
            

            if(strlen($nome_admin)>0){
                $obs_motivo = $pedido_status_observacao; 
            }else{
                $pedido_status_observacao = explode("|", $pedido_status_observacao);
                $motivo         = $pedido_status_observacao[0];
                $obs_motivo     = $pedido_status_observacao[1];
            }

            if($status_pedido == 20 and empty($nome_admin)){
                $nome_admin = "Automático";
            }
?>
                    <tr>
                        <td><?=$codigo_posto?></td>
                        <td nowrap><?=$posto_nome?></td>
                        <td><?=$contato_cidade?></td>
                        <td><?=$contato_estado?></td>
<?php
            if ($login_fabrica == 1) {
?>
                        <td data-pedido='<?=$pedido?>' class='pecas-pedido'><a href='#'><?=$seu_pedido?></a></td>
<?php
            } else if ($login_fabrica == 104) {
?>
                        <td data-pedido='<?=$pedido?>' class='pecas-pedido' ><a href='#'><?=$pedido?></a></td>
<?php
            }
?>
                        <td nowrap class="tac"><?= $descricao_tipo_posto ?></td>
                        <td nowrap class="tac"><?= $categoria ?></td>
                        <td><?="R$ ".$total_com_ipi?></td>
                        <td><?=$data?></td>
                        <td><?=$finalizado?></td>
                        <td><?=$motivo?></td>
                        <td><?=$obs_motivo?></td>
<?php
            if ($status_pedido == 1) {
?>
                        <td><?=$data_aprovacao?></td>
<?php
            }
?>
                        <td><?=$nome_admin?></td>
                    </tr>
<?php
        }

?>
                </tbody>
            </table>
            <br>
            <br>
        <?php
            if(isset($_POST['btn_gravar']) and pg_num_rows($resx) > 0 ){
                $jsonPOST = excelPostToJson($_POST);
                ?>

                <div id='gerar_excel' class="btn_excel">
                    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                    <span><img src='imagens/excel.png' /></span>
                    <span class="txt">Gerar Arquivo Excel</span>
                </div>
                <br>
                
            <?php
            }
        ?>
<?php
    } else {
?>
            <div class='container'>
                <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
                </div>
            </div>
<?
    }
}

include 'rodape.php';
?>
