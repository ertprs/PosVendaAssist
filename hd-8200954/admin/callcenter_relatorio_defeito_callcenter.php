<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";

?>

<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status){
janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="plugins/dataTable.js"></script>
<script src="plugins/resize.js"></script>

<?
function _acronym_helper($string, $length = 10) {
    $short = substr($string,0,$length);
    return "<acronym title=\"{$string}\">{$short}</acronym>";
}

	$data_inicial      = $_GET['data_inicial'];
	$data_final        = $_GET['data_final'];
	$produto           = $_GET['produto'];
	$natureza          = $_GET['natureza_chamado'] ? : $_GET['natureza'];
	$status            = $_GET['status'];
	$tipo              = $_GET['tipo'];
	$defeito_reclamado = $_GET['defeito_reclamado'];
	$faq               = $_GET['faq'];

  $familia           = $_GET['familia'];

    $cond_produto  = (!$produto)  ? '' : "AND tbl_hd_chamado_extra.produto = $produto ";
    $cond_natureza = (!$natureza) ? '' : "AND tbl_hd_chamado.categoria = '$natureza' ";
    $cond_status   = (!$status)   ? "AND tbl_hd_chamado.status <> 'Cancelado'  "
                                  : "AND tbl_hd_chamado.status = '$status'";
    $cond_defeito  = '';

	if ($login_fabrica==6) {
		$cond_status = "AND tbl_hd_chamado.status <> 'Cancelado'  ";
	}

    if (!strlen($status) and $login_fabrica == 74) {
        $cond_status = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

	if(strlen($defeito_reclamado)>0){
        if($defeito_reclamado == "null"){ //hd_chamado=2710901
            $cond_defeito = "AND tbl_hd_chamado_extra.defeito_reclamado IS NULL";
        }else{
            $cond_defeito = "AND tbl_hd_chamado_extra.defeito_reclamado = $defeito_reclamado  ";
        }
	}

	if (strlen($faq) > 0) {
        $join_faq = "JOIN tbl_hd_chamado_faq      ON tbl_hd_chamado.hd_chamado                = tbl_hd_chamado_faq.hd_chamado
                                              AND tbl_hd_chamado_faq.faq = $faq";
	}
    if ($login_fabrica == 74) {
        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

  if(in_array($login_fabrica, array(169,170))){
      $sql_campos = ", tbl_hd_chamado_origem.descricao AS origem ,
              tbl_hd_classificacao.descricao AS classificacao ,
              tbl_hd_motivo_ligacao.descricao AS providencia
      ";

      $sql_joins .= "
              JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
              JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
                AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
              JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
                AND tbl_hd_classificacao.fabrica = {$login_fabrica}";

    if(strlen(trim($familia)) > 0){
      $cond_familia = " AND tbl_produto.familia = {$familia} ";
    }
  }

	if(strlen($msg_erro)==0){
        $sql = "SELECT tbl_hd_chamado.hd_chamado,
                       tbl_hd_chamado.titulo,
                       tbl_hd_chamado.hd_chamado_anterior,
                       tbl_hd_chamado.categoria,
                       tbl_hd_chamado_extra.nome,
                       tbl_hd_chamado_extra.endereco,
                       tbl_hd_chamado_extra.numero,
                       tbl_hd_chamado_extra.bairro,
                       tbl_hd_chamado_extra.cep,
                       tbl_hd_chamado_extra.fone,
                       tbl_cidade.estado,
                       tbl_cidade.nome                           AS cidade_nome,
                       TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
                       (
                           SELECT TO_CHAR(HDI.data,'DD/MM/YYYY') AS data
                             FROM tbl_hd_chamado_item AS HDI
                            WHERE HDI.hd_chamado = tbl_hd_chamado.hd_chamado
                         ORDER BY data DESC
                            LIMIT 1
                        )                                        AS data_interacao,
                       tbl_produto.descricao                     AS produto,
                       tbl_defeito_reclamado.descricao           AS defeito_reclamado,
                       tbl_hd_motivo_ligacao.descricao           AS hd_motivo_ligacao,
                       tbl_admin.login,
                       tbl_motivo_contato.descricao as motivo_contato_descricao,
                       tbl_hd_providencia.descricao as descricao_providencia
                       $sql_campos
                  FROM tbl_hd_chamado
                  JOIN tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado                = tbl_hd_chamado_extra.hd_chamado
             LEFT JOIN tbl_produto             ON tbl_produto.produto                      = tbl_hd_chamado_extra.produto
             LEFT JOIN tbl_defeito_reclamado   ON tbl_defeito_reclamado.defeito_reclamado  = tbl_hd_chamado_extra.defeito_reclamado
             LEFT JOIN tbl_hd_motivo_ligacao   ON tbl_hd_chamado_extra.hd_motivo_ligacao   = tbl_hd_motivo_ligacao.hd_motivo_ligacao
                                              AND tbl_hd_motivo_ligacao.fabrica            = $login_fabrica
             LEFT JOIN tbl_cidade              ON tbl_hd_chamado_extra.cidade              = tbl_cidade.cidade
             LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
              AND tbl_hd_providencia.fabrica = {$login_fabrica}
              LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
              AND tbl_motivo_contato.fabrica = {$login_fabrica}
                  JOIN tbl_admin               ON tbl_hd_chamado.atendente                 = tbl_admin.admin
                  $join_faq
                  $sql_joins
                 WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                   AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
                   and tbl_hd_chamado.posto is null
                   $cond_produto
                   $cond_natureza
                   $cond_status
                   $cond_defeito
                   $cond_admin_fale_conosco
                   $cond_familia
			";
###########################################

		$res = pg_exec($con,$sql);
    
		if(pg_numrows($res)>0){
?>
        <div id="border_table">
            <table class="table table-striped table-bordered table-hover table-fixed " >
                <thead>
                    <tr class='titulo_coluna'>
                        <td class='titulo_coluna' >Chamado</TD>
                        <?php if($login_fabrica == 50){ ?>
                            <td class='titulo_coluna'>Tipo de Atendimento</td>
                        <?php } ?>
<?php
                        if($login_fabrica == 115){ //hd_chamado=2710901
?>
                            <td class='titulo_coluna' >Chamado Relacionado</TD>
<?php
                        }
?>
<?
			if(!in_array($login_fabrica, array(74,169,170))){
?>
                        <TD class='titulo_coluna' >Assunto</TD>
<?
			}
?>
                        <TD class='titulo_coluna' >Abertura</TD>
<?
			if($login_fabrica != 74){
?>
                        <TD class='titulo_coluna' >Última Interação</TD>
                        <TD class='titulo_coluna' >Atendente</TD>
<?
			}
      if(in_array($login_fabrica, array(169,170))){
?>
                        <th >Classificação</th>
                        <th >Origem</th>
                        <th >Providência</th>
                        <th >Providência nv. 3</th>
                        <th >Motivo Contato</th>
<?php
      }
			if($login_fabrica == 74){
?>
                        <TD class='titulo_coluna' >Cliente</TD>
                        <TD class='titulo_coluna' >Endereço</TD>
                        <TD class='titulo_coluna' >Bairro</TD>
                        <TD class='titulo_coluna' >CEP</TD>
                        <TD class='titulo_coluna' >Cidade</TD>
                        <TD class='titulo_coluna' >UF</TD>
                        <TD class='titulo_coluna' >Telefone</TD>
                        <TD class='titulo_coluna' >Produto</TD>
<?
			}
?>
                    </tr >
                </thead>
                <tbody>
<?
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter             = pg_result($res,$y,hd_chamado);
				$titulo                 = pg_result($res,$y,titulo);
				$abertura               = pg_result($res,$y,data);
				$login                  = pg_result($res,$y,login);
				$categoria              = pg_result($res,$y,categoria);
				$defeito_reclamado      = pg_result($res,$y,defeito_reclamado);
				$produto                = pg_result($res,$y,produto);
				$ultima_interacao       = pg_result($res,$y,data_interacao);
				$consumidor_nome        = pg_result($res,$y,nome);
        $consumidor_endereco    = pg_result($res,$y,endereco);
        $consumidor_numero      = pg_result($res,$y,numero);
        $consumidor_bairro      = pg_result($res,$y,bairro);
        $consumidor_cep         = pg_result($res,$y,cep);
        $consumidor_tel         = pg_result($res,$y,'fone');
        $consumidor_cidade      = pg_result($res,$y,'cidade_nome');
        $consumidor_estado      = pg_result($res,$y,'estado');
        $hd_motivo_ligacao      = pg_fetch_result($res,$y, 'hd_motivo_ligacao');
        $descricao_providencia  = pg_fetch_result($res,$y, 'descricao_providencia');
        $motivo_contato         = pg_fetch_result($res,$y, 'motivo_contato_descricao');

        $origem              = pg_result($res,$y,'origem');
        $classificacao       = pg_result($res,$y,'classificacao');
        $providencia         = pg_result($res,$y,'providencia');

        if($login_fabrica == 115){ //hd_chamado=2710901
            $hd_chamado_anterior = pg_fetch_result($res, $y, 'hd_chamado_anterior');
        }

                $cor = ($y % 2) ? "#F7F5F0" : "#F1F4FA";
?>
                    <TR bgcolor='<?=$cor?>'>
<?
				if($login_fabrica == 6){
?>
                        <TD class="tac">
                            <a href='cadastra_callcenter.php?callcenter=<?=$callcenter?>' target='blank'><?=$callcenter?></a>&nbsp;
                        </TD>
<?
				}else{
?>
                        <TD class="tac">
                            <a href='callcenter_interativo.php?callcenter=<?=$callcenter?>' target='_blank'><?=$callcenter?></a>&nbsp;
                        </TD>
<?
				}

                if($login_fabrica == 50){
                    echo "<td>$hd_motivo_ligacao</td>";
                }

                if($login_fabrica == 115){ //hd_chamado=2710901
                    ?>
                    <TD class="tac">
                        <a href='callcenter_interativo.php?callcenter=<?=$hd_chamado_anterior?>' target='_blank'><?=$hd_chamado_anterior?></a>&nbsp;
                    </TD>
                    <?php
                }

				if(!in_array($login_fabrica, array(74,169,170))){
?>
                        <TD class="tal"><?=$titulo?>&nbsp;</TD>
<?
				}
?>
                        <TD class="tac"><?=$abertura?>&nbsp;</TD>
<?
				if($login_fabrica != 74){
?>
                        <TD class="tac"><?=$ultima_interacao?>&nbsp;</TD>
                        <TD class="tal"><?=$login?>&nbsp;</TD>
<?
				}
        if(in_array($login_fabrica, array(169,170))){
?>

                        <TD align='center' nowrap><?=$classificacao?></TD>
                        <TD align='center' nowrap><?=$origem?></TD>
                        <TD align='left' nowrap><?=$providencia?></TD>
                        <TD align='left' nowrap><?=$descricao_providencia?></TD>
                        <TD align='left' nowrap><?=$motivo_contato?></TD>

<?php
        }
				if($login_fabrica == 74){
?>
                        <TD class="tal" nowrap><?php echo _acronym_helper($consumidor_nome);?></TD>
                        <TD class="tal" nowrap><?php echo _acronym_helper($consumidor_endereco.", ".$consumidor_numero);?></TD>
                        <TD class="tal" nowrap><?php echo _acronym_helper($consumidor_bairro);?></TD>
                        <TD class="tar"><?=$consumidor_cep?>&nbsp;</TD>
                        <TD class="tal"><?php echo _acronym_helper($consumidor_cidade);?></TD>
                        <TD class="tal"><?=$consumidor_estado?>&nbsp;</TD>
                        <TD class="tar"><?=$consumidor_tel?>&nbsp;</TD>
                        <TD class="tal" nowrap><?php echo _acronym_helper($produto)?></TD>
<?
				}
?>
				</TR >
<?
			}
?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="100%" class="tac">
                        Quantidade de registros: <?php echo pg_numrows($res);?>
                    </td>
                </tr>
            </tfoot>
        </table>
<?
		}
	}

?>
