<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";

?>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status){
janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status, "Callcenter",'scrollbars=yes,width=1000,height=550,top=315,left=0');
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


	$data_inicial       = $_GET['data_inicial'];
	$data_final         = $_GET['data_final'];
	$produto            = $_GET['produto'];
    $natureza_chamado   = $_GET['natureza'];
	$status_chamado     = $_GET['status'];
	$reclamado          = $_GET['reclamado'];
	$familia            = $_GET['familia'];

	if($login_fabrica == 101){
		$origem = $_GET["origem"];
	}

//	echo "$data_inicial - $data_final - prod:$produto - nature:$natureza_chamado - recla:$reclamado<BR>";

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";

	if(strlen($produto)>0){
		$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		if($produto==0){
			$cond_1 = " tbl_hd_chamado_extra.produto  is null ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}

	if(strlen($reclamado)>0 and $reclamado <> 'null'){
		$cond_4 = " tbl_hd_chamado_extra.defeito_reclamado = $reclamado  ";
		if($reclamado==0){
			$cond_4 = " tbl_hd_chamado_extra.defeito_reclamado is null ";
		}
	}
	if ( strlen($familia) > 0 ) {
		$cond_5  = "tbl_produto.familia ";
		$cond_5 .= ( $familia == 0 ) ? ' is null ' : " = '{$familia}'" ;
	}

	if($login_fabrica == 101 and strlen(trim($origem))>0){
        $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
    }

	if(strlen($status)>0){
        switch($status){
            case 'andamento':
                $cond_6 .= "
                AND tbl_hd_chamado.status IN (
                                                        'AGUARDANDO AUTORIZADA',
                                                        'AGUARDANDO CLIENTE',
                                                        'AGUARDANDO FABRICA',
                                                        'AGUARDANDO JURIDICO',
                                                        'AGUARDANDO LAUDO',
                                                        'AGUARDANDO PEDIDO SAA',
                                                        'AGUARDANDO REVENDA',
                                                        'CONTATAR AUTORIZADA',
                                                        'CONTATAR CLIENTE',
                                                        'CONTATAR REVENDA',
                                                        'ENVIADO EMAIL PARA AGENDAMENT',
                                                        'PECAS ENVIADAS PELA FABRICA',
                                                        'PEDIDO IMPLANTADO',
                                                        'TROCA AUTORIZADA PELA FABRICA',
                                                        'TROCA ENCAMINHADA',
                                                        'TROCA SOLICITACAO',
                                                        'VISITA AGENDADA',
                                                        'VISITA REAGENDADA'
                                                        )  ";
            break;
            case 'informacoes':
                $cond_6 .= "
                AND tbl_hd_chamado.status IN ('PROTOCOLO DE INFORMACAO')  ";
            break;
            case 'finalizado':
                $cond_6 .= "
                AND tbl_hd_chamado.status IN ('Resolvido')  ";
            break;
            default:
                $cond_6 .= "
                AND    1=1";
            break;
        }
    }

	if(strlen($msg_erro)==0){

		if($login_fabrica == 74){
            $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
        }

        if(in_array($login_fabrica, array(169,170))){
			$sql_campos = ", tbl_hd_chamado_origem.descricao AS origem ,
							tbl_hd_classificacao.descricao AS classificacao ,
							tbl_hd_motivo_ligacao.descricao AS providencia,
							tbl_motivo_contato.descricao as motivo_contato_descricao,
                       		tbl_hd_providencia.descricao as descricao_providencia
			";

			$sql_joins .= "	JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
								AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
							JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
								AND tbl_hd_classificacao.fabrica = {$login_fabrica}
							LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
				           	AND tbl_hd_providencia.fabrica = {$login_fabrica}
				            LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
				            AND tbl_motivo_contato.fabrica = {$login_fabrica}";
		}

		$sql = "
				SELECT	distinct tbl_hd_chamado.hd_chamado                           ,
						tbl_hd_chamado.titulo                              ,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data  ,
						( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao ,
						tbl_produto.descricao as produto                   ,
						tbl_produto.referencia as produto_referencia,
						tbl_hd_chamado.categoria                           ,
                        tbl_hd_chamado.hd_chamado_anterior                 ,
                        tbl_hd_motivo_ligacao.descricao AS hd_motivo_ligacao,
						case when tbl_hd_chamado_extra.defeito_reclamado notnull then tbl_defeito_reclamado.descricao else tbl_hd_chamado_extra.defeito_reclamado_descricao end  as defeito_reclamado,
						tbl_admin.login
						$sql_campos
				from tbl_hd_chamado
				join tbl_hd_chamado_extra using(hd_chamado)
				LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
				LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
	            LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
				JOIN tbl_admin on tbl_hd_chamado.atendente = tbl_admin.admin
				$sql_joins
				where tbl_hd_chamado.fabrica = $login_fabrica
				and  tbl_hd_chamado.status<>'Cancelado'
				AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
                AND tbl_hd_chamado.posto is null
				AND $cond_1
				AND $cond_2
				AND $cond_4
				AND $cond_5
                    $cond_6
                    $cond_admin_fale_conosco
                    $cond_origem
";

		if($login_fabrica == 52) {
			$sql = str_replace('extra','item',$sql);
		}

		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
?>
        <div id="border_table">
            <table class="table table-striped table-bordered table-hover " >
                <thead>
                    <tr class='titulo_coluna'>
                    	<?php if ($login_fabrica == 35) { ?>
                    		<td class='tac'>Protocolo</TD>
                    	<?php } else { ?>
                        	<td class='tac'>Chamado</TD>
                        <?php } 
                         if($login_fabrica == 50){ ?>
                        <td class='tac'>Tipo de Atendimento</td>
                        <?php } ?>
                        <?php if($login_fabrica == 115){ //hd_chamado=2710901 ?>
                        <td class='tac'>At.Relacionado</TD>
                        <?php } ?>
                        <?php if(!in_array($login_fabrica, array(169,170))){ ?>
                        	<TD class='tac'>Assunto</TD>
                        <?php } ?>
                        <TD class='tac'>Abertura</TD>
                        <TD class='tac'>Fechamento</TD>
                        <TD class='tac'>Natureza</TD>
                        <td class='tac'>Referência Produto</td>
                        <TD class='tac'>Descrição Produto</TD>
                        <TD class='tac'>Defeito Reclamado</TD>
                        <TD class='tac'>Atendente</TD>

                        <?php if(in_array($login_fabrica, array(169,170))){ ?>
							<th class='tac'>Classificação</th>
							<th class='tac'>Origem</th>
							<th class='tac'>Providência</th>
							<th class='tac'>Providência nv. 3</th>
							<th class='tac'>Motivo Contato</th>
						<?php } ?>
                    </TR >
                </thead>
                <tbody>
<?
			for($y=0;pg_num_rows($res)>$y;$y++){
				$callcenter       = pg_fetch_result($res,$y,"hd_chamado");
				$titulo           = pg_fetch_result($res,$y,"titulo");
				$abertura         = pg_fetch_result($res,$y,"data");
				$ultima_interacao = pg_fetch_result($res,$y,"data_interacao");
				$login            = pg_fetch_result($res,$y,"login");
				$categoria        = pg_fetch_result($res,$y,"categoria");
				$produto          = pg_fetch_result($res,$y,"produto");
				$defeito_reclamado = pg_fetch_result($res, $y, 'defeito_reclamado');
				$hd_motivo_ligacao = pg_fetch_result($res, $y, 'hd_motivo_ligacao');
				$produto_referencia = pg_fetch_result($res, $y, 'produto_referencia');
                if($login_fabrica == 115){ //hd_chamado=2710901
                    $hd_chamado_anterior = pg_fetch_result($res, $y, 'hd_chamado_anterior');
                }

                $origem           	 = pg_fetch_result($res,$y,'origem');
				$classificacao       = pg_fetch_result($res,$y,'classificacao');
				$providencia         = pg_fetch_result($res,$y,'providencia');
				$providencia_descricao  = pg_fetch_result($res,$y,'descricao_providencia');
				$motivo_contato         = pg_fetch_result($res,$y,'motivo_contato_descricao');

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
?>
				<TR bgcolor='$cor'>
<?
				if($login_fabrica == 6){
?>
					<TD class="tac">
                        <a href='cadastra_callcenter.php?callcenter=<?=$callcenter?>' target='blank'><?=$callcenter?></a>
                    </TD>
<?
				}else{
?>
					<TD class="tac">
                        <a href='callcenter_interativo.php?callcenter=<?=$callcenter?>' target='blank'><?=$callcenter?></a>
                    </TD>
<?
				}

				if($login_fabrica == 50){
					echo "<td>$hd_motivo_ligacao</td>";
				}

                if($login_fabrica == 115){ //hd_chamado=2710901
?>
                <TD class="tac">
                    <a href='callcenter_interativo.php?callcenter=<?=$hd_chamado_anterior?>' target='blank'><?=$hd_chamado_anterior?></a>
                </TD>
<?php
                }

				if(!in_array($login_fabrica, array(169,170))){
?>
					<TD class="tal"><?=$titulo?></TD>
<?
				}
?>
				<TD class="tac"><?=$abertura?></TD>
				<TD class="tac"><?=$ultima_interacao?></TD>
				<TD class="tac"><?=$categoria?></TD>
				<td class='tac'><?=$produto_referencia?></td>
				<TD class="tac"><?=$produto?></TD>
				<TD class="tac"><?=$defeito_reclamado?></TD>
				<TD class="tal"><?=$login?></TD>
<?php
				if(in_array($login_fabrica, array(169,170))){
					echo "<TD align='center' nowrap>$classificacao</TD>\n";
					echo "<TD align='center' nowrap>$origem</TD>\n";
					echo "<TD align='left' nowrap>$providencia</TD>\n";
					echo "<TD align='left' nowrap>$providencia_descricao</TD>\n";
					echo "<TD align='left' nowrap>$motivo_contato</TD>\n";
				}
?>
				</TR >
<?
			}
?>
                </tbody>
			</table>
        </div>
<?
		}
	}

?>
