<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';



$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}


#------------ Detecta OS para Auditoria -----------#
$auditoria = $_GET['auditoria'];
$auditoria_motivo = '';
if ($auditoria == 't') {

	$btn_acao = $_POST['btn_acao'];
	$os       = $_POST['os'];

	if ($btn_acao == 'Rejeitar') {
		$sql = "UPDATE tbl_os_extra SET status_os = 13 WHERE os = $os";
		$res = pg_exec ($con,$sql);

		$sql = "UPDATE tbl_os_item SET 
					admin_liberacao = $login_admin, 
					liberacao_pedido = 'f' , 
					data_liberacao_pedido = CURRENT_TIMESTAMP 
				WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
		$sql = "SELECT fn_auditoria_previa ($os,$login_admin,'f')";
		$res = pg_exec ($con,$sql);

	}elseif ($btn_acao == 'Analisar') {
		$sql = "UPDATE tbl_os_extra SET status_os = 20 WHERE os = $os";
		$res = pg_exec ($con,$sql);

		$sql = "UPDATE tbl_os_item SET 
					admin_liberacao = $login_admin, 
					liberacao_pedido = 'f' , 
					data_liberacao_pedido = CURRENT_TIMESTAMP 
				WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
		$sql = "SELECT fn_auditoria_previa ($os,$login_admin,'f')";
		$res = pg_exec ($con,$sql);

	}elseif ($btn_acao == 'Aprovar') {
		$sql = "UPDATE tbl_os_extra SET status_os = 19 WHERE os = $os";
		$res = pg_exec ($con,$sql);

		$sql = "UPDATE tbl_os_item SET 
					admin_liberacao = $login_admin, 
					liberacao_pedido = 't' , 
					data_liberacao_pedido = CURRENT_TIMESTAMP 
				WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
		$sql = "SELECT fn_auditoria_previa ($os,$login_admin,'t')";
		$res = pg_exec ($con,$sql);


	}
	$os = "";



	$data_inicial = '2006-08-24 00:00:00';
	$data_final   = '2006-08-24 23:59:59';

	$sql = "SELECT tbl_os.os 
			FROM tbl_os 
			JOIN tbl_os_produto USING (os) 
			JOIN tbl_os_item    USING (os_produto)
			JOIN tbl_os_extra   ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_servico_realizado USING (servico_realizado)
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto 
				 AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			WHERE tbl_os.fabrica = $login_fabrica ";

	if(strlen($codigo_posto)>0) $sql .=" AND tbl_posto_fabrica.codigo_posto = $codigo_posto ";
	
	$sql .= "AND   tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final' 
			 AND   tbl_servico_realizado.troca_de_peca
			 AND   tbl_os_extra.status_os IS NULL
			GROUP BY tbl_os.os
			HAVING SUM (tbl_os_item.qtde) >= 3 
			ORDER BY tbl_os.os
			LIMIT 1";
#	$res = pg_exec ($con,$sql);


	$sql = "SELECT tbl_os.os , tbl_os.posto , tbl_produto.linha
			FROM  tbl_os
			JOIN  tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.os IN (SELECT tbl_os.os 
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os_item.digitacao_item >= CURRENT_DATE - INTERVAL '1 DAY'
				AND   tbl_os_item.data_liberacao_pedido IS NULL
			)
			ORDER BY tbl_os.os";
	$res = pg_exec ($con,$sql);

	$auditar = false ;

	echo pg_numrows ($res) . " OS para liberar <p>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os    = pg_result ($res,$i,os);
		$posto = pg_result ($res,$i,posto);
		$linha = pg_result ($res,$i,linha);

		#------- 3 peças ou mais -------------#
		$sql = "SELECT SUM (tbl_os_item.qtde) 
				FROM tbl_os_produto 
				JOIN tbl_os_item USING (os_produto)
				JOIN tbl_servico_realizado USING (servico_realizado)
				WHERE tbl_os_produto.os = $os
				AND   tbl_servico_realizado.troca_de_peca";
		$resX = pg_exec ($con,$sql);
		$qtde = pg_result ($resX,0,0);
		if ($qtde >= 3) {
			$auditar = true ;
			$auditoria_motivo = 'Esta OS tem 3 peças ou mais';
			break;
		}

		#------- Posto acima da media -------------#
		$sql = "SELECT * FROM tbl_pecas_por_os
				WHERE posto = $posto AND linha = $linha AND pecas_por_os > 0.6";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) > 0) {
			$auditar = true ;
			$auditoria_motivo = 'servicio acima de la média de piezas por OS';
			break;
		}




		if ($auditar === false) {
			$sql = "UPDATE tbl_os_item SET 
						admin_liberacao = 512   , 
						liberacao_pedido = 't'  , 
						data_liberacao_pedido = CURRENT_TIMESTAMP
					WHERE data_liberacao_pedido IS NULL
					AND   os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
			$sql = "SELECT fn_auditoria_previa ($os,512,'t')";
			$resX = pg_exec ($con,$sql);

			echo "<br>";
			echo "Liberada automaticamente $os";
			echo "<br>";
			flush();
		}
	}
}


if ($auditar === false) {
	echo "<p><h1>Todas as OS auditadas </h1><p>";
	exit;
}


#------------ Le OS da Base de dados ------------#
if (strlen ($os) == 0) $os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.posto                                                      ,
					tbl_os.sua_os                                                     ,
					tbl_os.sua_os_offline                                             ,
					tbl_admin.login                              AS admin             ,
					troca_admin.login                            AS troca_admin       ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
					tbl_os.tipo_atendimento                                           ,
					tbl_os.tecnico_nome                                               ,
					tbl_tipo_atendimento_idioma.descricao                 AS nome_atendimento,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_endereco                                        ,
					tbl_os.consumidor_numero                                          ,
					tbl_os.consumidor_complemento                                     ,
					tbl_os.consumidor_bairro                                          ,
					tbl_os.consumidor_cep                                             ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.revenda_nome                                               ,
					tbl_os.revenda_cnpj                                               ,
					tbl_os.nota_fiscal                                                ,
					tbl_os.cliente                                                    ,
					tbl_os.revenda                                                    ,
					tbl_os.motivo_atraso                                              ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado_idioma.descricao       AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_defeito_constatado_idioma.descricao      AS defeito_constatado,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
					tbl_causa_defeito_idioma.descricao           AS causa_defeito     ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.obs                                                        ,
					tbl_os.excluida                                                   ,
					tbl_produto.referencia                                            ,
					tbl_produto.descricao                                             ,
					tbl_produto.voltagem                                              ,
					tbl_os.qtde_produtos                                              ,
					tbl_os.serie                                                      ,
					tbl_os.posto                                                      ,
					tbl_os.codigo_fabricacao                                          ,
					tbl_os.troca_garantia                                             ,
					tbl_os.troca_via_distribuidor                                     ,
					tbl_os.troca_garantia_admin                                       ,
					to_char(tbl_os.troca_garantia_data,'DD/MM/YYYY') AS troca_garantia_data ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo      ,
					tbl_posto.nome                               AS posto_nome        ,
					tbl_posto.posto                               AS codigo_posto     ,
					tbl_os_extra.os_reincidente                                       ,
					tbl_os.ressarcimento                                              ,
					
					tbl_os.solucao_os
			FROM    tbl_os
			JOIN    tbl_posto                   ON tbl_posto.posto         = tbl_os.posto
			JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_os_extra           ON tbl_os.os               = tbl_os_extra.os
			LEFT JOIN    tbl_admin              ON tbl_os.admin  = tbl_admin.admin
			LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
			LEFT JOIN    tbl_defeito_reclamado  ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN    tbl_defeito_reclamado_idioma  ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado_idioma.defeito_reclamado		
			
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_defeito_constatado_idioma ON tbl_os.defeito_constatado = tbl_defeito_constatado_idioma.defeito_constatado
			
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_causa_defeito_idioma      ON tbl_os.causa_defeito      = tbl_causa_defeito_idioma.causa_defeito
			
			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_tipo_atendimento_idioma ON tbl_tipo_atendimento_idioma.tipo_atendimento = tbl_os.tipo_atendimento
			WHERE   tbl_os.os = $os 
			AND     tbl_os.fabrica = $login_fabrica";


	$res = pg_exec ($con,$sql);
#	echo $sql . "<br>- ". pg_numrows ($res);

	if (pg_numrows ($res) > 0) {
		$posto                       = pg_result ($res,0,posto);
		$sua_os                      = pg_result ($res,0,sua_os);
		$admin                       = pg_result ($res,0,admin);
		$data_digitacao              = pg_result ($res,0,data_digitacao);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$data_finalizada             = pg_result ($res,0,finalizada);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$motivo_atraso               = pg_result ($res,0,motivo_atraso);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$produto_voltagem            = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$defeito_constatado          = pg_result ($res,0,defeito_constatado);
		$defeito_constatado_codigo   = pg_result ($res,0,defeito_constatado_codigo);
		$causa_defeito_codigo        = pg_result ($res,0,causa_defeito_codigo);
		$causa_defeito               = pg_result ($res,0,causa_defeito);
		$posto_codigo                = pg_result ($res,0,posto_codigo);
		$posto_nome                  = pg_result ($res,0,posto_nome);
		$obs                         = pg_result ($res,0,obs);
		$qtde_produtos      = pg_result ($res,0,qtde_produtos);
		$excluida                    = pg_result ($res,0,excluida);
		$os_reincidente              = trim(pg_result ($res,0,os_reincidente));
		$solucao_os              = trim(pg_result ($res,0,solucao_os));
		$troca_garantia        = trim(pg_result($res,0,troca_garantia));
		$troca_garantia_data   = trim(pg_result($res,0,troca_garantia_data));
		$troca_garantia_admin  = trim(pg_result($res,0,troca_garantia_admin));

		$tipo_atendimento   = trim(pg_result($res,0,tipo_atendimento));
		$tecnico_nome       = trim(pg_result($res,0,tecnico_nome));
		$nome_atendimento   = trim(pg_result($res,0,nome_atendimento));
		$sua_os_offline     = trim(pg_result($res,0,sua_os_offline));
		
		$ressarcimento = trim(pg_result($res,0,ressarcimento));
		$troca_admin   = trim(pg_result($res,0,troca_admin));
		$codigo_posto   = trim(pg_result($res,0,posto));

		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_exec ($con,$sql);
			
			if (pg_numrows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
				$revenda_cep         = trim(pg_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}
		
$sql_idioma = "SELECT tbl_produto_idioma.* FROM tbl_produto_idioma JOIN tbl_produto USING(produto) WHERE referencia = '$produto_referencia' AND upper(idioma) = '$sistema_lingua'";
		
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
		
		

        if($aparencia_produto=='NEW'){
            if($sistema_lingua) $aparencia = "Buena aparencia";   else $aparencia = "Bom Estado";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USL'){
            if($sistema_lingua) $aparencia = "Uso continuo";      else $aparencia = "Uso intenso";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USN'){
            if($sistema_lingua) $aparencia = "Uso normal";        else $aparencia = "Uso Normal";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USH'){
            if($sistema_lingua) $aparencia = "Uso Pesado";        else $aparencia = "Uso Pesado";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='ABU'){
            if($sistema_lingua) $aparencia = "Uso Abusivo";       else $aparencia = "Uso Abusivo";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='ORI'){
            if($sistema_lingua) $aparencia = "Original, sin uso"; else $aparencia = "Original, sem uso";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='PCK'){
            if($sistema_lingua) $aparencia = "Embalaje";          else $aparencia = "Embalagem";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }


	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = "Confirmación de órdenes de servicio";

$layout_menu = 'os';
include "cabecalho.php";

?>
<style type="text/css">

body {
	margin: 0px;
}

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}

</style>
<p>

<?
if (strlen($os_reincidente) > 0) {
	$sql = "SELECT  tbl_os.sua_os,
					tbl_os.serie
			FROM    tbl_os
			WHERE   tbl_os.os = $os_reincidente;";
	$res1 = pg_exec ($con,$sql);
	
	$sos   = trim(pg_result($res1,0,sua_os));
	$serie = trim(pg_result($res1,0,serie));
	
	echo "<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
	echo "<tr>";
	echo "<td class='titulo'>ATENCIÓN</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='titulo'>ORDEN DE SERVICIO CON NÚMERO DE SERIE: $serie REINCIDENTE. ORDEN DE SERVICIO ANTERIOR: $sos</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
}

if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'DISTRIBUIDOR';
else 
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>
<?
 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################

 if ($excluida == "t") { 
?>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
<TR>
	<TD  bgcolor="#FFE1E1" height='20'><h1>ORDEN DE SERVICIO BORRADA</h1></TD>
</TR>
</TABLE>
<?
} 
?>


<?
if (strlen ($auditoria_motivo) > 0) {
	echo "<center><h2><font size='+2'> $auditoria_motivo </font></h2></center>";
}
?>

<?
if ($ressarcimento == "t") {
	echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
	echo "<TR height='30'>";
	echo "<TD align='left' colspan='3'>";
	echo "<font family='arial' size='2' color='#ffffff'><b>";
	echo "Ressarcimento Financeiro";
	echo "</b></font>";
	echo "</TD>";
	echo "</TR>";

	echo "<tr>";
	echo "<TD class='titulo3'  height='15' >Responsável</TD>";
	echo "<TD class='titulo3'  height='15' >Data</TD>";
	echo "<TD class='titulo3'  height='15' >&nbsp;</TD>";
	echo "</tr>";

	echo "<tr>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $troca_admin;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $data_fechamento ;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";

	echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";

	echo "</tr>";
	echo "</table>";
}
if ($ressarcimento <> "t") {
	if ($troca_garantia == "t") {
		echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
		echo "<TR height='30'>";
		echo "<TD align='left' colspan='3'>";
		echo "<font family='arial' size='2' color='#ffffff'><b>";
		echo "Produto Trocado";
		echo "</b></font>";
		echo "</TD>";
		echo "</TR>";

		echo "<tr>";
		echo "<TD align='left' class='titulo3'  height='15' >Responsável</TD>";
		echo "<TD align='left' class='titulo3'  height='15' >Data</TD>";
		echo "<TD align='left' class='titulo3'  height='15' >Trocado Por</TD>";
#		echo "<TD class='titulo'  height='15' >&nbsp;</TD>";
		echo "</tr>";

		$sql = "SELECT tbl_peca.referencia , tbl_peca.descricao, tbl_os_extra.orientacao_sac
				FROM tbl_peca
				JOIN tbl_os_item USING (peca)
				JOIN tbl_os_produto USING (os_produto)
				JOIN tbl_os_extra USING (os)
				WHERE tbl_os_produto.os = $os
				AND   tbl_peca.produto_acabado IS TRUE ";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) > 0) {
			$troca_por_referencia = pg_result ($resX,0,referencia);
			$troca_por_descricao  = pg_result ($resX,0,descricao);
			$orientacao_sac       = pg_result ($resX,0,orientacao_sac);
		}
				
				
		echo "<tr>";
		echo "<TD class='conteudo' align='left' height='15' nowrap>";
		echo "&nbsp;&nbsp;&nbsp;";
		echo $troca_admin;
		echo "&nbsp;&nbsp;&nbsp;";
		echo "</td>";
		echo "<TD class='conteudo' align='left' height='15' nowrap>";
		echo "&nbsp;&nbsp;&nbsp;";
		echo $data_fechamento;
		echo "&nbsp;&nbsp;&nbsp;";
		echo "</td>";
		echo "<TD class='conteudo' align='left' height='15' nowrap >";
		echo $troca_por_referencia . " - " . $troca_por_descricao;
		echo "</td>";

#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
		echo "</tr>";

		//alterado por Sono, incluido o campo orientacao_sac a pedido de Fabricio, chamado 472
		echo "<tr>";
		echo "<TD class='titulo3' align='left' colspan='3' height='15' nowrap>Orientações SAC ao Posto Autorizado</TD>";
		echo "</tr>";
		echo "<tr>";
		echo "<TD class='conteudo' align='left' colspan='3' height='15' nowrap >";
		echo $orientacao_sac;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
}
?>



<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
		<TR>
			<TD class="inicio">&nbsp;&nbsp;SERVICIO</TD>
		</TR>
		<TR>
			<TD class="conteudo"><? echo "&nbsp; $posto_codigo - $posto_nome"; ?></TD>
		</TR>
</TABLE>
<?
// }
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr >
		<td rowspan='4' class='conteudo' width='300' ><center>OS FABRICANTE<br><br>&nbsp;<b>
			<?
			echo "<FONT SIZE='6' COLOR='#C67700'>";
			if ($login_fabrica == 1) echo $posto_codigo;
			if (strlen($consumidor_revenda) > 0) echo $sua_os ."</FONT> - ". $consumidor_revenda;
			else echo $sua_os;
			?><?
			if(strlen($sua_os_offline)>0){ 
			echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			echo "<tr >";
			echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
			echo "</tr>";
			echo "</table>";
			}
			?>
			</b></center>
		</td>
		<td class='inicio' height='15' colspan='4'>&nbsp;Fecha de la OS</td>
	</TR>
	<TR>
		<td class='titulo'width='100' height='15'>ABERTURA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
		<td class='titulo' width='100' height='15'>DIGITACIÓN&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
	</tr>
	<tr>
		<td class='titulo' width='100' height='15'>CERRAMIENTO&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
		<td class='titulo' width='100' height='15'>CERRADA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>

	</tr>
	<tr>
		<TD class="titulo"  height='15'>FECHA COMPRA&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
		<td class='titulo' width='100' height='15'>CERRADA EN &nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;
		<? 
		if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){

			$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
			$resD = pg_exec ($con,$sql_data);
			if (pg_numrows ($resD) > 0) {
				$total_de_dias_do_conserto = pg_result ($resD,0,final);
			}

			if($total_de_dias_do_conserto==0) echo 'en el mismo día' ;
			else echo $total_de_dias_do_conserto;
			if($total_de_dias_do_conserto==1) echo ' día' ;
			if($total_de_dias_do_conserto>1)  echo ' días' ;
		}else{
			echo "NO CERRADO";
		}
		?>
		</td>
	</tr>
	<?
	if(strlen($motivo_atraso)>0){
	?>
		<tr><td colspan='5' bgcolor='#FF0000' size='2'><b><font color='#FFFF00'>Razón del atraso: <?=$motivo_atraso?></font></b></td></tr>
	<?
	}
	?>
</table>
<? 
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
if($login_fabrica==19 OR $login_fabrica==20){
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class="titulo"  height='15' width='90'>ATENDIMIENTO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_atendimento.' - '.$nome_atendimento ?></TD>
	<?if( $tecnico_nome){?>
	<TD class="titulo" height='15'width='90'>NOMBRE DO TECNICO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
	<?}?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA LORENZETTI
?>
<?
if(strlen($troca_garantia_admin)>0){
	$sql = "SELECT login,nome_completo
			FROM tbl_admin
			WHERE admin = $troca_garantia_admin";
	$res2 = pg_exec ($con,$sql);
			
	if (pg_numrows($res2) > 0) {
		$login                = pg_result ($res2,0,login);
		$nome_completo        = pg_result ($res2,0,nome_completo);

?>
		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<TR>
				<TD class="titulo"  height='15' width='90'>Usuários&nbsp;</TD>
				<TD class="conteudo" height='15'>&nbsp;<? if($nome_completo )echo $nome_completo; else echo $login;  ?></TD>
				<TD class="titulo" height='15'width='90'>Data</TD>
				<TD class="conteudo" height='15'>&nbsp;
				<? echo $troca_garantia_data ?></TD>
			</TR>
			<TR>
				<TD class="conteudo"  height='15'colspan='4'>
				<?
				if($troca_garantia=='t')
					echo '<b><center>Troca Direta</center></b>';
				else
					echo '<b><center>Troca Via Distribuidor</center></b>';
				?>
				</TD>
			</TR>
		</TABLE>
<?
	}
}
?>
<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr>
		<td class='inicio' height='15' colspan='4'>&nbsp;INFORMACIÓN DEL PRODUCTO&nbsp;</td>
	</tr>
	<tr >
		<TD class="titulo" height='15' width='90'>REFERENCIA&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
		<TD class="titulo" height='15' width='90'>DESCRIPCIÓN&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
		<TD class="titulo" height='15' width='90'>NÚMERO DE SERIE&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?></TD>
	</tr>

</table>
<? if (strlen($aparencia_produto) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<td class='titulo' height='15' width='300'>APARENCIA GENERAL DE LA HERRAMIENTA</td>
	<td class="conteudo">&nbsp;<? echo $aparencia_produto ?>
	</td>
</TR>
</TABLE>
<? } ?>
<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
<TR>
	<TD class='titulo' height='15' width='300'>ACCESORIOS DEJADOS JUNTO A LA HERRAMEINTA</TD>
	<TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<TR>
		<TD  height='15' class='inicio' colspan='4'>&nbsp;FALLAS</TD>
	</TR>
	<TR>
		<TD class="titulo" height='15' width='90'>RECLAMADO</TD>
		<TD class="conteudo" height='15' width='150'> &nbsp;<? echo $defeito_reclamado ;  ?></TD>
		<TD class="titulo" height='15' width='90'>REPARACIÓN &nbsp;</td>
		<td class="conteudo" height='15'>&nbsp;
			<? 
			if($login_fabrica==20)echo $defeito_constatado_codigo.' - ';
			echo $defeito_constatado;
			?>
		</TD>
	</TR>

	<TR>
		<TD class="titulo" height='15' width='90'>DEFECTO&nbsp;</td>
		<td class="conteudo"colspan='3' height='15'>&nbsp;
		<? 
			if($login_fabrica==20){echo $causa_defeito_codigo.' - ' ; echo $causa_defeito;}
 		?>
		</TD>
	</TR>
	<?
	if($login_fabrica==20){
		if(strlen($solucao_os) > 0){
			$xsql="SELECT descricao from tbl_servico_realizado_idioma where servico_realizado= $solucao_os limit 1";
			$xres = pg_exec($con, $xsql);
			$xsolucao = trim(pg_result($xres,0,descricao));
			echo "<tr>";
			echo "<td class='titulo' height='15' width='90'>IDENTIFICACION&nbsp;</td>";
			echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
			echo "</tr>";
		}else{
			echo "<tr>";
			echo "<td class='titulo' height='15' width='90'>INDENTIFICACÍON&nbsp;</td>";
			echo "<td class='conteudo'colspan='3' height='15'>&nbsp;</TD>";
			echo "</tr>";
		}
	}
	?>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;INFORMACIONES SOBRE EL CONSUMIDOR&nbsp;</td>
	</tr>
	<TR>
		<TD class="titulo" width='90' height='15'>NOMBRE&nbsp;</TD>
		<TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_nome ?></TD>
		<TD class="titulo" width='80'>TELÉFONO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>ID CONSUMIDOR&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
		<TD class="titulo" height='15'>APARTADO POSTAL&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>DIRECCIÓN&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
		<TD class="titulo" height='15'>NÚMERO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
		<TD class="titulo" height='15'>BARRIO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
	</TR>
	<TR>
		<TD class="titulo">CIUDAD&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
		<TD class="titulo">PROVINCIA&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
	</TR>
</TABLE>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;INFORMACIONES SOBRE EL DISTRIBUIDOR</td>
	</tr>
	<TR>
		<TD class="titulo"  height='15' width='90'>NOMBRE&nbsp;</TD>
		<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
		<TD class="titulo"  height='15' width='80'>ID REVENDA&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
	</TR>
	<TR>
		<TD class="titulo"  height='15'>FACTURA COMERCIAL&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<FONT COLOR="#FF0000"><? echo $nota_fiscal ?></FONT></TD>
		<TD class="titulo"  height='15'>FECHA COMPRA&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
	</TR>
</TABLE>
<p></p>
<? //if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<TD colspan="3" class='inicio'>&nbsp;DIAGNÓSTICOS - COMPONENTES - MANTENIMIENTOS EXECUTADOS</TD>
</TR>
<TR>
<!-- 	<TD class="titulo">EQUIPAMENTO</TD> -->

	<TD class="titulo2">COMPONENTE</TD>
	<TD class="titulo2">CTD</TD>
	<TD class="titulo2">DIGIT.</TD>


</TR>
<?
	$sql = "SELECT  tbl_produto.referencia                                        ,
					tbl_produto.descricao                                         ,
					tbl_os_produto.serie                                          ,
					tbl_os_produto.versao                                         ,
					tbl_os_item.serigrafia                                        ,
					tbl_os_item.pedido    AS pedido_item                          ,
					tbl_os_item.peca                                              ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
					tbl_defeito.descricao AS defeito                              ,
					tbl_peca.referencia   AS referencia_peca                      ,
					tbl_os_item_nf.nota_fiscal                                    ,
					tbl_os_item.obs                                                ,
					TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf      ,
					tbl_peca.descricao    AS descricao_peca                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_status_pedido.descricao     AS status_pedido              ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao        
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			JOIN	tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
									       AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN    tbl_defeito USING (defeito)
			LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";

	$sql = "SELECT  tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_os_produto.serie                                           ,
					tbl_os_produto.versao                                          ,
					tbl_os_item.os_item                                            ,
					tbl_os_item.serigrafia                                         ,
					tbl_os_item.pedido              AS pedido_item                 ,
					tbl_os_item.peca                                               ,
					tbl_os_item.obs                                                ,
					tbl_os_item.custo_peca                                         ,
					tbl_os_item.posicao                                            ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
					tbl_pedido.pedido_blackedecker  AS pedido_blackedecker         ,
					tbl_pedido.distribuidor                                        ,
					tbl_defeito.descricao           AS defeito                     ,
          tbl_peca.referencia             AS referencia_peca             ,
					tbl_os_item_nf.nota_fiscal                                     ,
					TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf      ,
					tbl_peca.descricao              AS descricao_peca              ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao ,
					tbl_status_pedido.descricao     AS status_pedido               ,
					tbl_produto.referencia          AS subproduto_referencia       ,
					tbl_produto.descricao           AS subproduto_descricao        ,
					tbl_os_item.qtde                                               
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";
	$res = pg_exec($con,$sql);
	$total = pg_numrows($res);

	for ($i = 0 ; $i < $total ; $i++) {
		$pedido        = trim(pg_result($res,$i,pedido_item));
		$pedido_blackedecker = trim(pg_result($res,$i,pedido_blackedecker));
		$obs           = trim(pg_result($res,$i,obs));
		$os_item       = trim(pg_result($res,$i,os_item));
		$peca          = trim(pg_result($res,$i,peca));
		$nota_fiscal   = trim(pg_result($res,$i,nota_fiscal));
		$status_pedido = trim(pg_result($res,$i,status_pedido));

		$distribuidor  = trim(pg_result($res,$i,distribuidor));
		$digitacao     = trim(pg_result($res,$i,digitacao_item));
		$data_nf       = trim(pg_result($res,$i,data_nf));

    $descricao_peca = pg_result($res,$i,descricao_peca);
 
        $sql_idioma = "SELECT * FROM tbl_peca_idioma
                        WHERE peca = $peca
                        AND upper(idioma)     = 'ES'";
                        
        $res_idioma = @pg_exec($con,$sql_idioma);
          if (@pg_numrows($res_idioma) >0) {
            $descricao_peca  = trim(@pg_result($res_idioma,0,descricao));
        }
   
    

		if ($login_fabrica == 3 AND 1==2 ) {
			$nf = $status_pedido;
		}else{
			if (strlen ($nota_fiscal) == 0) {
				if (strlen($pedido) > 0) {
					//alterado por Sono 25/08/2006 colocada condição posto
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal , 
								    TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS data_nf
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND tbl_faturamento.posto = $posto
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_exec ($con,$sql);
					
					if (pg_numrows ($resx) > 0) {
						$nf      = trim(pg_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_result($resx,0,data_nf));
						$link = 1;
					}else{
						$condicao_01 = " 1=1 ";
						if (strlen ($distribuidor) > 0) {
							$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
						}
						//alterado por Sono 25/08/2006 colocada condição posto
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
								        TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS data_nf
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca = $peca
								AND tbl_faturamento.posto = $posto
								AND     $condicao_01 ";
						$resx = pg_exec ($con,$sql);
						
						if (pg_numrows ($resx) > 0) {
							$nf = trim(pg_result($resx,0,nota_fiscal));
							$data_nf = trim(pg_result($resx,0,data_nf));
							$link = 1;
						}else{
							$nf = "Pendente";
							$link = 1;
						}
					}
				}else{
					$nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		}



?>
<TR>
<!-- 	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao); ?></TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD class=\"conteudo\" style=\"text-align:left;\">".pg_result($res,$i,subproduto_referencia) . " - " . pg_result($res,$i,subproduto_descricao)."</TD>";
		echo "<TD class=\"conteudo\" style=\"text-align:center;\">".pg_result($res,$i,posicao)."</TD>";
	}
	?>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result($res,$i,referencia_peca) . " - $descricao_peca "; ?></TD>
	<TD class="conteudo" style="text-align:center;"><? echo pg_result($res,$i,qtde) ?></TD>
	<?
	if ($login_fabrica == 1 and 1==2) {
		echo "<TD class='conteudo' style='text-align:center;'>";
		echo number_format (pg_result($res,$i,custo_peca),2,",",".");
		echo "</TD>";
	}
	?>

	</TD>
	<TD class="conteudo" style="text-align:CENTER;"><?= $data_nf ?>&nbsp;</TD>
</tr>
<?
	}
?>
</TABLE>
<? //} ?>

<BR>

<? 

if (strlen($obs) > 0) { 
echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
echo "<TR>";
	echo "<TD class='conteudo'><b>OBS:</b>&nbsp;$obs</TD>";
echo "</TR>";
echo "</TABLE>";
} 
?>





<!--            Valores da OS           -->
<?
if ($login_fabrica == "20") {
	$sql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
	$res = pg_exec ($con,$sql);
	$mao_de_obra = 0 ;
	if (pg_numrows ($res) == 1) {
		$mao_de_obra = pg_result ($res,0,0);
	}


	$sql = "SELECT tabela , desconto FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$tabela = 0 ;
	$desconto = 0;

	if (pg_numrows ($res) == 1) {
		$tabela = pg_result ($res,0,tabela);
		$desconto = pg_result ($res,0,desconto);
	}
	if (strlen ($desconto) == 0) $desconto = "0";


	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total 
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_exec ($con,$sql);
		$pecas = 0 ;


		if (pg_numrows ($res) == 1) {
			$pecas = pg_result ($res,0,0);
		}
		$pecas = number_format ($pecas,2,",",".");
	}else{
		$pecas = "0";
	}

	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Valor de Piezas</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Mano de Obra</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Impuesto IVA</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

        $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $res = pg_exec ($con,$sql);

        if (pg_numrows ($res) == 1) {
            $valor_liquido = pg_result ($res,0,pecas);
            $mao_de_obra   = pg_result ($res,0,mao_de_obra);
        }
        $sql = "select imposto_al  from tbl_posto_fabrica where posto=$posto and fabrica=$login_fabrica";
        $res = pg_exec ($con,$sql);

        if (pg_numrows ($res) == 1) {
            $imposto_al   = pg_result ($res,0,imposto_al);
            $imposto_al   = $imposto_al / 100;
            $acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
        }


    $total = $valor_liquido + $mao_de_obra + $acrescimo;

    $total          = number_format ($total,2,",",".")         ;
    $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
    $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
    $valor_desconto = number_format ($valor_desconto,2,",",".");
    $valor_liquido  = number_format ($valor_liquido ,2,",",".");

    echo "<tr style='font-size: 12px ; color:#000000 '>";
    echo "<td align='right'>" ;
    echo "<font color='#333377'><b>$valor_liquido</b>" ;
    echo "</td>";
    echo "<td align='center'>$mao_de_obra</td>";
    if($sistema_lingua=='ES')echo "<td align='center'>+ $acrescimo</td>";
    echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
    echo "</tr>";
    echo "</table>";

}
?>







<BR><BR>
<!-- =========== FINALIZA TELA NOVA============== -->


<?
if ($auditoria == 't') {
	echo "<form method='post' name='frm_auditoria' action='$PHP_SELF?auditoria=t'>";
	echo "<input type='hidden' name='os' value='$os'>";
	echo "<p>";
	echo "<input type='submit' name='btn_acao' value='Reprobar'>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type='submit' name='btn_acao' value='Analizar'>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type='submit' name='btn_acao' value='Aprobar'>";
	echo "</form>";
}else{
?>
<center>

		<a href="os_cadastro.php"><img src="../imagens/btn_lanzarnovaos.gif"></a>


<div id='container'>
	&nbsp;
</div>
</center>
<?
}
?>


<? include "rodape.php"; ?>
