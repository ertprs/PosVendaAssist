<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$mostraAnexoNF = array(20);

if (strlen($_POST["btn_acao"]) == 0) {
	$data_inicial = $_GET["data_inicial"];
	$data_final = $_GET["data_final"];
	$cnpj = $_GET["cnpj"];
	$razao = $_GET["razao"];
}

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["btn_continuar"]) == 0) {
	setcookie("link", $REQUEST_URI, time()+60*60*24); # Expira em 1 dia
}

$admin_privilegios="financeiro";
include "autentica_admin.php";

include_once('../anexaNF_inc.php');

$os  = $_GET['os'];
$op  = $_GET['op'];
$cor = $_GET['cor'];
if (strlen ($os) > 0 AND $op =='aprovar') {
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "
	INSERT INTO tbl_os_status (
		os        ,
		status_os ,
		observacao,
		extrato   ,
		admin    
	) VALUES (
		$os ,
		19   ,
		'',
		$extrato,
		$login_admin
	);";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	$res = (strlen($msg_erro) == 0) ? pg_exec($con,"COMMIT TRANSACTION") : pg_exec($con,"ROLLBACK TRANSACTION");

	$resposta = (strlen($msg_erro)>0) ? $msg_erro : "OS $os aprovada!";

	echo "ok|$resposta";exit;
}


if (strlen ($os) > 0 AND $op=='ver') {
	include "../ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

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
					tbl_tipo_atendimento.descricao                 AS nome_atendimento,
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
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo ,
					tbl_causa_defeito.descricao                  AS causa_defeito     ,
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
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			WHERE   tbl_os.os = $os 
			AND     tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0) {
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
			$causa_defeito               = pg_result ($res,0,causa_defeito);
			$causa_defeito_codigo        = pg_result ($res,0,causa_defeito_codigo);
			$posto_codigo                = pg_result ($res,0,posto_codigo);
			$posto_nome                  = pg_result ($res,0,posto_nome);
			$obs                         = pg_result ($res,0,obs);
			$qtde_produtos               = pg_result ($res,0,qtde_produtos);
			$excluida                    = pg_result ($res,0,excluida);
			$os_reincidente              = trim(pg_result ($res,0,os_reincidente));
			$solucao_os                  = trim(pg_result ($res,0,solucao_os));
			$troca_garantia              = trim(pg_result($res,0,troca_garantia));
			$troca_garantia_data         = trim(pg_result($res,0,troca_garantia_data));
			$troca_garantia_admin        = trim(pg_result($res,0,troca_garantia_admin));
			$tipo_atendimento            = trim(pg_result($res,0,tipo_atendimento));
			$tecnico_nome                = trim(pg_result($res,0,tecnico_nome));
			$nome_atendimento            = trim(pg_result($res,0,nome_atendimento));
			$sua_os_offline              = trim(pg_result($res,0,sua_os_offline));
			$ressarcimento               = trim(pg_result($res,0,ressarcimento));
			$troca_admin                 = trim(pg_result($res,0,troca_admin));
			$codigo_posto                = trim(pg_result($res,0,posto));

			if (strlen($os_reincidente) > 0) {
				$sql = "SELECT  tbl_os.sua_os,
								tbl_os.serie
						FROM    tbl_os
						WHERE   tbl_os.os = $os_reincidente;";
				$res1 = pg_exec ($con,$sql);
				
				$sos   = trim(pg_result($res1,0,sua_os));
				$serie_r = trim(pg_result($res1,0,serie));
				
				$resposta .=  "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
				$resposta .=  "<tr>";
				$resposta .=  "<td align='center'><b><font size='1'>ANTEN«√O</font></b></td>";
				$resposta .=  "</tr>";
				$resposta .=  "<tr>";
				$resposta .=  "<td align='center'><font size='1'>ORDEM DE SERVI«O REINCIDENTE. ORDEM DE SERVI«O ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a></font></td>";
				$resposta .=  "</tr>";
				$resposta .=  "</table>";
				$resposta .=  "<br>";
			}

			if ($ressarcimento == "t") {
				$resposta .= "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela' >";
				$resposta .= "<TR height='30'>";
				$resposta .= "<TD align='left' colspan='3' bgcolor='$cor'>";
				$resposta .= "<font family='arial' size='2' color='#ffffff'><b>";
				$resposta .= "RESSARCIMENTO FINANCEIRO";
				$resposta .= "</b></font>";
				$resposta .= "</TD>";
				$resposta .= "</TR>";

				$resposta .= "<tr>";
				$resposta .= "<TD class='titulo3'  height='15' >RESPONS¡VEL</TD>";
				$resposta .= "<TD class='titulo3'  height='15' >DATA</TD>";
				$resposta .= "<TD class='titulo3'  height='15' >&nbsp;</TD>";
				$resposta .= "</tr>";

				$resposta .= "<tr>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>";
				$resposta .= "&nbsp;&nbsp;&nbsp;";
				$resposta .= $troca_admin;
				$resposta .= "&nbsp;&nbsp;&nbsp;";
				$resposta .= "</td>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>";
				$resposta .= "&nbsp;&nbsp;&nbsp;";
				$resposta .= $data_fechamento ;
				$resposta .= "&nbsp;&nbsp;&nbsp;";
				$resposta .= "</td>";

				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' width='80%'>&nbsp;</td>";

				$resposta .= "</tr>";
				$resposta .= "</table>";
			}
			if ($ressarcimento <> "t") {
				if ($troca_garantia == "t") {
					$resposta .= "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
					$resposta .= "<TR height='20'>";
					$resposta .= "<TD align='left' colspan='3' bgcolor='$cor' class='inicio'>";
					$resposta .= "PRODUTO TROCADO";
					$resposta .= "</TD>";
					$resposta .= "</TR>";

					$resposta .= "<tr>";
					$resposta .= "<TD align='left' class='titulo3'  height='15' >RESPONS√ÅEL</TD>";
					$resposta .= "<TD align='left' class='titulo3'  height='15' >DATA</TD>";
					$resposta .= "<TD align='left' class='titulo3'  height='15' >TROCADO POR</TD>";
					$resposta .= "</tr>";

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
							
							
					$resposta .= "<tr>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' align='left' height='15' nowrap>";
					$resposta .= "&nbsp;&nbsp;&nbsp;";
					$resposta .= $troca_admin;
					$resposta .= "&nbsp;&nbsp;&nbsp;";
					$resposta .= "</td>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' align='left' height='15' nowrap>";
					$resposta .= "&nbsp;&nbsp;&nbsp;";
					$resposta .= $data_fechamento;
					$resposta .= "&nbsp;&nbsp;&nbsp;";
					$resposta .= "</td>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' align='left' height='15' nowrap >";
					$resposta .= $troca_por_referencia . " - " . $troca_por_descricao;
					$resposta .= "</td>";
					$resposta .= "</tr>";
					$resposta .= "<tr>";
					$resposta .= "<TD class='titulo3' align='left' colspan='3' height='15' nowrap>ORIENTA«’ES SAC AO POSTO AUTORIZADO</TD>";
					$resposta .= "</tr>";
					$resposta .= "<tr>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' align='left' colspan='3' height='15' nowrap >";
					$resposta .= $orientacao_sac;
					$resposta .= "</td>";
					$resposta .= "</tr>";
					$resposta .= "</table>";
				}
			}
			$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' 	align='center'>";
			$resposta .="<tr ><td rowspan='4' class='conteudo' bgcolor='$cor' width='300' ><center>OS FABRICANTE<br>&nbsp;<b><FONT SIZE='5' COLOR='#C67700'>";
			
			if ($login_fabrica == 1)             $resposta .= "".$posto_codigo;
			$resposta .= (strlen($consumidor_revenda) > 0) ? $sua_os ." - ". $consumidor_revenda : $sua_os;

			if(strlen($sua_os_offline)>0){ 
				$resposta .= "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
				$resposta .= "<tr >";
				$resposta .= "<td class='conteudo' bgcolor='$cor' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
				$resposta .= "</tr>";
				$resposta .= "</table>";
			}

			$resposta .= "</FONT></b><br><u>NF: "; 
			$resposta .= (strlen($nota_fiscal)==0) ? "N√O INFORMADO" : "$nota_fiscal";
			$resposta .="</U></center>";
			$resposta .= "</td>";
			$resposta .= "<td class='inicio' height='15' colspan='4' bgcolor='$cor'>&nbsp;DATAS DA OS</td>";
			$resposta .= "</TR>";
			$resposta .= "<TR>";
			$resposta .= "<td class='titulo'width='100' height='15'>ABERTURA&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_abertura</td>";
			$resposta .= "<td class='titulo' width='100' height='15'>DIGITA«√O&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_digitacao</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<td class='titulo' width='100' height='15'>FECHAMENTO&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_fechamento</td>";
			$resposta .= "<td class='titulo' width='100' height='15'>FINALIZADA&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_finalizada</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<TD class='titulo'  height='15'>DATA DA NF&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor'  height='15'>&nbsp;$data_nf</TD>";
			$resposta .= "<td class='titulo' width='100' height='15'>FECHADO EM &nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;";
			if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
				$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
				$resD = pg_exec ($con,$sql_data);
				if (pg_numrows ($resD) > 0) {
					$total_de_dias_do_conserto = pg_result ($resD,0,'final');
				}

				$resposta .= ($total_de_dias_do_conserto==0) ? 'no mesmo dia' : $total_de_dias_do_conserto;
				$resposta .= ($total_de_dias_do_conserto>1) ? ' dias' : ' dia';
			}
			$resposta .= "</td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";

			if($login_fabrica==19 OR $login_fabrica==20 OR $login_fabrica==30){
				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='titulo  height='15' width='90'>ATENDIMENTO&nbsp;</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$tipo_atendimento - $nome_atendimento </TD>";
				if(strlen($tecnico_nome)>0){
					$resposta .= "<TD class='titulo' height='15'width='90'>NOME DO T√âCNICO&nbsp;</TD>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$tecnico_nome </TD>";
				}
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}
			if(strlen($troca_garantia_admin)>0){
				$sql = "SELECT login,nome_completo
						FROM tbl_admin
						WHERE admin = $troca_garantia_admin";
				$res2 = pg_exec ($con,$sql);
						
				if (pg_numrows($res2) > 0) {
					$login                = pg_result ($res2,0,login);
					$nome_completo        = pg_result ($res2,0,nome_completo);

					$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
					$resposta .= "<TR>";
					$resposta .= "<TD class='titulo'  height='15' width='90'>Usu·rios&nbsp;</TD>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;";
					$resposta .= ($nome_completo ) ? $nome_completo : $login ;
					$resposta .= "</TD>";
					if(strlen($troca_garantia_data)>0){
						$resposta .= "<TD class='titulo' height='15'width='90'>Data</TD>";
						$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$troca_garantia_data </TD>";
					}
					$resposta .= "</TR>";
					$resposta .= "<TR>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor'  height='15'colspan='4'>";
					$resposta .= ($troca_garantia=='t') ? "<b><center>Troca Direta</center></b>" : "<b><center>Troca Via Distribuidor</center></b>";
					$resposta .= "</TD>";
					$resposta .= "</TR>";
					$resposta .= "</TABLE>";
				}
			}

			$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
			$resposta .= "<tr>";
			$resposta .= "<td class='inicio' height='15' colspan='6' bgcolor='$cor'>&nbsp;INFORMA«’ES DO CONSUMIDOR&nbsp;</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr >";
			$resposta .= "<TD class='titulo' height='15' width='90'>NOME&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_nome </TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>ENDERE«O&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_endereco </TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>TELEFONE&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$consumidor_fone </TD>";
			$resposta .= "</tr>";
			$resposta .= "<tr >";
			$resposta .= "<TD class='titulo' height='15' width='90'>CIDADE&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_cidade </TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>Estado&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_estado </TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>CEP&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$consumidor_cep </TD>";
			$resposta .= "</tr>";
			$resposta .= "</table>";

			$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
			$resposta .= "<tr>";
			$resposta .= "<td class='inicio' height='15' colspan='6' bgcolor='$cor'>&nbsp;INFORMA«’ES DO PRODUTO&nbsp;</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr >";
			$resposta .= "<TD class='titulo' height='15' width='90'>REFER NCIA&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$produto_referencia </TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>DESCRI«√O&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$produto_descricao </TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>N⁄MERO DE S…RIE&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$serie </TD>";
			$resposta .= "</tr>";
			if ($login_fabrica == 1) { 
				$resposta .= "<tr>";
				$resposta .= "<TD class='titulo' height='15' width='90'>VOLTAGEM&nbsp;</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$produto_voltagem </TD>";
				$resposta .= "<TD class='titulo' height='15' width='110'>C”DIGO FABRICA?O&nbsp;</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$codigo_fabricacao </TD>";
				$resposta .= "</tr>";
			} 
			$resposta .= "</table>";
			if (strlen($aparencia_produto) > 0) { 
				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
				$resposta .= "<TR>";
				$resposta .= "<td class='titulo' height='15' width='300'>APARENCIA GERAL DO APARELHO/PRODUTO</td>";
				$resposta .= "<td class='conteudo' bgcolor='$cor'>&nbsp;$aparencia_produto </td>";
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}
			if (strlen($acessorios) > 0) { 
				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='titulo' height='15' width='300'>ACESS”RIOS DEIXADOS JUNTO COM O APARELHO</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor'>&nbsp;$acessorios; </TD>";
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}
			if (strlen($defeito_reclamado) > 0) { 
				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='titulo' height='15'width='300'>&nbsp;INFORMA«’ES SOBRE O DEFEITO</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' >&nbsp;";
				if (strlen($defeito_reclamado) > 0) {
					$sql = "SELECT tbl_defeito_reclamado.descricao
							FROM   tbl_defeito_reclamado
							WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						$descricao_defeito = trim(pg_result($res,0,descricao));
						$resposta .= "$descricao_defeito - $defeito_reclamado_descricao";
					}
				}
				$resposta .= "</TD>";
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD  height='15' class='inicio' colspan='4' bgcolor='$cor'>&nbsp;DEFEITOS</TD>";
			$resposta .= "</TR>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo' height='15' width='90'>RECLAMADO</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' width='150'> &nbsp;$descricao_defeito - $defeito_reclamado_descricao</TD>";
			$resposta .= "<TD class='titulo' height='15' width='90'>";
			$resposta .= ($login_fabrica==20) ? "REPARO" : "CONSTATADO";
			$resposta .= "</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' height='15'>&nbsp;";
			if($login_fabrica==20) $resposta .= $defeito_constatado_codigo.' - ';
			$resposta .= $defeito_constatado;
			$resposta .="</TD>";
			$resposta .= "</TR>";
			$resposta .= "<TR>";

			$resposta .= "<TD class='titulo' height='15' width='90'>";
			$resposta .= ($login_fabrica==6) ? "SOLU«√O" : (($login_fabrica==20) ? "DEFEITO" : "CAUSA");
			$resposta .= "&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' colspan='3' height='15'>";
			if($login_fabrica==6){
				if (strlen($solucao_os)>0){
					$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
					$xres = pg_exec($con, $xsql);
					$xsolucao = trim(pg_result($xres,0,descricao));
					$resposta .= "$xsolucao";
				}
			}else{
				if($login_fabrica==20)$resposta .= $causa_defeito_codigo.' - ' ;
				$resposta .= $causa_defeito;
			}
			$resposta .= "</TD>";
			$resposta .= "</TR>";

			if($login_fabrica==20){
				if($solucao_os){
					$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
					$xres = pg_exec($con, $xsql);
					$xsolucao = trim(pg_result($xres,0,descricao));

					$resposta .= "<tr>";
					$resposta .= "<td class='titulo' height='15' width='90'>IDENTIFICA«√O&nbsp;</td>";
					$resposta .= "<td class='conteudo'bgcolor='$cor'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
					$resposta .= "</tr>";
				}
			}

			$resposta .= "</TABLE>";

			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD colspan='";
			$resposta .= ($login_fabrica == 1) ? "9" : "8"; 
			$resposta .= "' class='inicio' bgcolor='$cor'>&nbsp;DIAGN”STICOS - COMPONENTES - MANUTEN«’ES EXECUTADAS</TD>";
			$resposta .= "</TR>";
			$resposta .= "<TR>";
			if($os_item_subconjunto == 't') {
				$resposta .= "<TD class=\"titulo2\">SUBCONJUNTO</TD>";
				$resposta .= "<TD class=\"titulo2\">POSI«√O</TD>";
			}
			$resposta .= "<TD class='titulo2'>COMPONENTE</TD>";
			$resposta .= "<TD class='titulo2'>QTDE</TD>";
			$resposta .= ($login_fabrica == 1) ? "<TD class='titulo'>PRE?</TD>" : "";
			$resposta .= "<TD class='titulo2'>DIGIT.</TD>";
			$resposta .= "<TD class='titulo2'>PRE«O LIQUIDO</TD>";
			$resposta .= "<TD class='titulo2'>SOLUCAO</TD>";
			$resposta .= "<TD class='titulo2'>PEDIDO</TD>";
			$resposta .= "<TD class='titulo2'>NOTA FISCAL</TD>";
			$resposta .= "<TD class='titulo2'>EMISS√O</TD>";
			$resposta .= "</TR>";

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
							tbl_os_item.preco                                              ,
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
				$preco          = trim(pg_result($res,$i,preco));
				$nota_fiscal   = trim(pg_result($res,$i,nota_fiscal));
				$status_pedido = trim(pg_result($res,$i,status_pedido));

				$distribuidor  = trim(pg_result($res,$i,distribuidor));
				$digitacao     = trim(pg_result($res,$i,digitacao_item));
				$data_nf       = trim(pg_result($res,$i,data_nf));

				if ($login_fabrica == 3 AND 1==2 ) {
					$nf = $status_pedido;
				}else{
					if (strlen ($nota_fiscal) == 0) {
						if (strlen($pedido) > 0) {
							//alterado por Sono 25/08/2006 colocada condi?o posto
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
								//alterado por Sono 25/08/2006 colocada condi?o posto
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

				$resposta .= "<TR>";
				if($os_item_subconjunto == 't') {
					$resposta .= "<TD class=\"conteudo\" style=\"text-align:left;\">".pg_result($res,$i,subproduto_referencia) . " - " . pg_result($res,$i,subproduto_descricao)."</TD>";
					$resposta .= "<TD class=\"conteudo\" style=\"text-align:center;\">".pg_result($res,$i,posicao)."</TD>";
				}
				$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:left;'>".pg_result($res,$i,referencia_peca) . " - " . pg_result($res,$i,descricao_peca)."</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'>".pg_result($res,$i,qtde)."</TD>";

				if ($login_fabrica == 1) {
					$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'>";
					$resposta .=  number_format (pg_result($res,$i,custo_peca),2,",",".");
					$resposta .= "</TD>";
				}
				
				$resposta .= "<TD class='conteudo' bgcolor='$cor' >".pg_result($res,$i,digitacao_item)."</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'>R$ ".number_format (pg_result($res,$i,preco),2,",",".")."</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' >".pg_result($res,$i,servico_realizado_descricao)."</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' ><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>";
				if ($login_fabrica == 1) $resposta .= $pedido_blackedecker; else $resposta .=  $pedido;
				$resposta .= "</a>&nbsp;</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' nowrap>";

				if (strtolower($nf) <> 'pendente'){
					$resposta .= ($link == 1) ? "$nf" : "$nf ";
				}else{
					$sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item AND tbl_embarque.faturar IS NOT NULL";
					$resX = pg_exec ($con,$sql);
					$resposta .= (pg_numrows ($resX) > 0) ? "Embarque " . pg_result ($resX,0,embarque) . " - " . pg_result ($resX,0,faturar) : "$nf &nbsp;";
				}

				$resposta .= "</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' >$data_nf&nbsp;</TD>";
				$resposta .= "</tr>";
			}
			$resposta .= "</TABLE>";
			

		if(in_array($login_fabrica, $mostraAnexoNF)){
			$resposta .= "<script type='text/javascript' src='js/FancyZoom.js'></script>
					    <script type='text/javascript' src='js/FancyZoomHTML.js'></script>
						<script type='text/javascript'>
							setupZoom();
						</script>";			
			$resposta .= "<div id='DIVanexos'> ";
			$resposta .= temNF($os, 'link', '', false, false, 0);
			$resposta .= "</div>";
		
		}
		$resposta .= "<BR>";
			if (strlen($obs) > 0) { 
				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor'><b>OBS:</b>&nbsp;$obs</TD>";
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}

			#HD 111073
			if($login_fabrica==20){
				$sql_y = "select observacao, extrato from tbl_os_status where os=$os and extrato IS NOT NULL;";
				$res_y = pg_exec($con,$sql_y);
				if (pg_numrows($res_y) > 0){
					for ($w = 0 ; $w < pg_numrows($res_y) ; $w++) {
						$nums = $w+1;
						$obs_w   = trim(pg_result($res_y,$w,observacao));
						$extrato_w   = trim(pg_result($res_y,$w,extrato));
						$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
						$resposta .= "<tr class='Conteudo' bgcolor='$cor'>";
						$resposta .= "<td colspan='6'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Obs. Extrato : $extrato_w; </b>" . $obs_w . "</td>";
						$resposta .= "</tr>";
						$resposta .= "</TABLE>";
					}
				}
			}

			$sql = "SELECT os
					FROM tbl_os_troca_motivo
					WHERE os = $os ";
			$res = pg_query($con,$sql);
			if($login_fabrica==20 AND pg_num_rows($res)>0) {
				$motivo1 = "N„o s„o fornecidas peÁas de reposiÁ„o para este produto";
				$motivo2 = "H· peÁa de reposiÁ„o, mas est· em falta";
				$motivo3 = "Vicio do produto";
				$motivo4 = "DivergÍncia de voltagem entre embalagem e produto";
				$motivo5 = "InformaÁıes adicionais";
				$motivo6 = "InformaÁıes complementares";
				$troca = true;

				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
				$resposta .= "<tr>";
				$resposta .= "<td class='inicio' colspan='4' height='15'>";
				$resposta .= "InformaÁıes sobre o MOTIVO DA TROCA";
				$resposta .= "<div id='container'>";
				$resposta .= "<div id='page'>";

					$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
									tbl_causa_defeito.codigo        AS causa_codigo     ,
									tbl_causa_defeito.descricao     AS causa_defeito
							FROM   tbl_os_troca_motivo
							JOIN   tbl_servico_realizado USING(servico_realizado)
							JOIN   tbl_causa_defeito     USING(causa_defeito)
							WHERE os     = $os
							AND   motivo = '$motivo1'";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)==1){
						$identificacao1 = pg_fetch_result($res,0,servico_realizado);
						$causa_defeito1 = pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);

						$resposta .= "<div id='contentcenter' style='width: 650px;'>";
						$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
						$resposta .= "Data de entrada do produto na assistÍncia tÈcnica";
						$resposta .= "</div>";
						$resposta .= "</div>";
						$resposta .= "<div id='contentcenter' style='width: 650px;'>";
						$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
						$resposta .= "$data_abertura";
						$resposta .= "</div>";
						$resposta .= "</div>";

						$resposta .= "<div id='contentcenter' style='width: 650px;'>";
						$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
						$resposta .= "<br>$motivo1";
						$resposta .= "</div>";
						$resposta .= "</div>";
						$resposta .= "<div id='contentcenter' style='width: 650px;'>";
						$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
						$resposta .= "IdentificaÁ„o do defeito";
						$resposta .= "</div>";
						$resposta .= "<div id='contentleft2' style='width: 250px; '>";
						$resposta .= "Defeito";
						$resposta .= "</div>";
						$resposta .= "</div>";
						$resposta .= "<div id='contentcenter' style='width: 650px;'>";
						$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
						$resposta .= "$identificacao1";
						$resposta .= "</div>";
						$resposta .= "<div id='contentleft' style='width: 250px;font:75%'>";
						$resposta .= "$causa_defeito1";
						$resposta .= "</div>";
						$resposta .= "</div>";
					}

						$sql = "SELECT
										TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
										pedido                                              ,
										PE.referencia                     AS peca_referencia,
										PE.descricao                      AS peca_descricao
								FROM   tbl_os_troca_motivo
								JOIN   tbl_peca            PE USING(peca)
								WHERE os     = $os
								AND   motivo = '$motivo2'";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)==1){
							$peca_referencia = pg_fetch_result($res,0,peca_referencia);
							$peca_descricao  = pg_fetch_result($res,0,peca_descricao);
							$data_pedido     = pg_fetch_result($res,0,data_pedido);
							$pedido          = pg_fetch_result($res,0,pedido);

							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
							$resposta .= "<br>$motivo2";
							$resposta .= "</div>";
							$resposta .= "</div>";
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
							$resposta .= "CÛdigo da PeÁa";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft2' style='width: 200px; '>";
							$resposta .= "Data do Pedido";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft2' style='width: 200px; '>";
							$resposta .= "N˙mero do Pedido";
							$resposta .= "</div>";
							$resposta .= "</div>";
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= $peca_referencia.'-'.$peca_descricao;
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= "$data_pedido";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= "$pedido";
							$resposta .= "</div>";
							$resposta .= "</div>";
						}

						$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
										tbl_causa_defeito.codigo        AS causa_codigo     ,
										tbl_causa_defeito.descricao     AS causa_defeito    ,
										observacao
								FROM   tbl_os_troca_motivo
								JOIN   tbl_servico_realizado USING(servico_realizado)
								JOIN   tbl_causa_defeito     USING(causa_defeito)
								WHERE os     = $os
								AND   motivo = '$motivo3'";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)==1){
							$identificacao2 = pg_fetch_result($res,0,servico_realizado);
							$causa_defeito2 =  pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
							$observacao1    = pg_fetch_result($res,0,observacao);

							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
							$resposta .= "<br>$motivo3";
							$resposta .= "</div>";
							$resposta .= "</div>";
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
							$resposta .= "IdentificaÁ„o do Defeito";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft2' style='width: 200px; '>";
							$resposta .= "Defeito";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft2' style='width: 200px; '>";
							$resposta .= "Quais as OSís deste produto:";
							$resposta .= "</div>";
							$resposta .= "</div>";
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= "$identificacao2";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= "$causa_defeito2";
							$resposta .= "</div>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= "$observacao1";
							$resposta .= "</div>";
							$resposta .= "</div>";
						}

						$sql = "SELECT observacao
								FROM   tbl_os_troca_motivo
								WHERE os     = $os
								AND   motivo = '$motivo4'";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)==1){
							$observacao2    = pg_fetch_result($res,0,observacao);
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft2' style='width: 200px; ' nowrap>";
							$resposta .= "<br>$motivo4";
							$resposta .= "</div>";
							$resposta .= "</div>";
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft2' style='width: 650px; ' nowrap>";
							$resposta .= "Qual a divergÍncia:";
							$resposta .= "</div>";
							$resposta .= "</div>";
							$resposta .= "<div id='contentcenter' style='width: 650px;'>";
							$resposta .= "<div id='contentleft' style='width: 200px;font:75%'>";
							$resposta .= "$observacao2";
							$resposta .= "</div>";
							$resposta .= "</div>";
						}

				$sql = "SELECT observacao
						FROM   tbl_os_troca_motivo
						WHERE os     = $os
						AND   motivo = '$motivo5'";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)==1){
					$observacao3    = pg_fetch_result($res,0,observacao);
					$resposta .= "<div id='container'>";
					$resposta .= "<div id='page'>";
					$resposta .= "<h2>$motivo5";
					$resposta .= "<div id='contentcenter' style='width: 650px;'>";
					$resposta .= "<div id='contentleft' style='width: 650px;font:75%'>$observacao3</div>";
					$resposta .= "</div>";
					$resposta .= "</h2>";
					$resposta .= "</div>";
					$resposta .= "</div>";
				}
				/* HD 43302 - 26/9/2008 */
				$sql = "SELECT observacao
						FROM   tbl_os_troca_motivo
						WHERE os     = $os
						AND   motivo = '$motivo6'";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)==1){
					$observacao4    = pg_fetch_result($res,0,observacao);
					$resposta .= "<div id='container'>";
					$resposta .= "<div id='page'>";
					$resposta .= "$motivo6<br><p class='conteudo'>$observacao4</p>";
					$resposta .= "</div>";
					$resposta .= "</div>";
				}
				$resposta .= "</td>";
				$resposta .= "</tr>";
				$resposta .= "</table>";
			}
			if ($login_fabrica==20) {
				$sql_status = "SELECT
								tbl_os_status.status_os                                    ,
								tbl_os_status.observacao                                   ,
								to_char(tbl_os_status.data, 'DD/MM/YYYY')   as data_status ,
								tbl_os_status.admin                                        ,
								tbl_status_os.descricao                                    ,
								tbl_admin.nome_completo AS nome                            ,
								tbl_admin.email                                            ,
								tbl_promotor_treinamento.nome  AS nome_promotor            ,
								tbl_promotor_treinamento.email AS email_promotor
							FROM tbl_os
							JOIN tbl_os_status USING(os)
							LEFT JOIN tbl_status_os USING(status_os)
							LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
							LEFT JOIN tbl_promotor_treinamento ON tbl_os.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento
							WHERE os = $os
							AND status_os IN (92,93,94)
							ORDER BY data ASC";

				$res_status = pg_query($con,$sql_status);
				$resultado = pg_num_rows($res_status);
				if ($resultado>0){
					$resposta .=  "<BR>\n";
					$resposta .=  "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
					$resposta .=  "<TR>\n";
					$resposta .=  "<TD colspan='4' class='inicio'>&nbsp;HistÛrico</TD>\n";
					$resposta .=  "</TR>\n";
					$resposta .=  "<TR>\n";
					$resposta .=  "<TD  class='titulo2' width='100px' align='center'><b>Data</b></TD>\n";
					$resposta .=  "<TD  class='titulo2' width='170px' align='left'><b>Status</b></TD>\n";
					$resposta .=  "<TD  class='titulo2' width='260px' align='left'><b>ObservaÁ„o</b></TD>\n";
					$resposta .=  "<TD  class='titulo2' width='170px' align='left'><b>Promotor</b></TD>\n";
					$resposta .=  "</TR>\n";
					for ($j=0;$j<$resultado;$j++){
						$status_os          = trim(pg_fetch_result($res_status,$j,status_os));
						$status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
						$status_data        = trim(pg_fetch_result($res_status,$j,data_status));
						$status_admin       = trim(pg_fetch_result($res_status,$j,admin));
						$descricao          = trim(pg_fetch_result($res_status,$j,descricao));
						$nome               = trim(strtoupper(pg_fetch_result($res_status,$j,nome)));
						$email              = trim(pg_fetch_result($res_status,$j,email));
						$nome_promotor      = trim(strtoupper(pg_fetch_result($res_status,$j,nome_promotor)));
						$email_promotor     = trim(pg_fetch_result($res_status,$j,email_promotor));

						$resposta .=  "<TR>\n";
						$resposta .=  "<TD  class='justificativa' align='center'><b>".$status_data."</b></TD>\n";
						$resposta .=  "<TD  class='justificativa' align='left' nowrap>".$descricao."</TD>\n";
						$resposta .=  "<TD  class='justificativa' align='left'>".$status_observacao."</TD>\n";
						$resposta .=  "<TD  class='justificativa' align='left' nowrap>";
						if($status_os == 92) { // HD 55196
							$resposta .=  "<acronym title='Nome: ".$nome_promotor." - \nEmail:".$email_promotor."'>".$nome_promotor;
						}else{
							$resposta .=  "<acronym title='Nome: ".$nome." - \nEmail:".$email."'>".$nome;
						}
						$resposta .=  "</TD>\n";
						$resposta .=  "</TR>\n";
					}
					$resposta .=  "</TABLE>\n";
				}
			}
		}
	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok|$resposta";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	flush();
	exit;
}
//FIM DA EXIBI«√O DO AJAX

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));
if (strlen($_POST["extrato"]) > 0)  $extrato = trim($_POST["extrato"]);
if (strlen($_GET["extrato"]) > 0)   $extrato = trim($_GET["extrato"]);

$msg_erro = "";

if ($btn_acao == 'pedido'){
	header ("Location: relatorio_pedido_peca_kit.php?extrato=$extrato");
	exit;
}

if ($btn_acao == "acumulartudo") {
	if (strlen($extrato) > 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$link = $_COOKIE["link"];
			header ("Location: $link");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}

if (strlen($_POST["btn_continuar"]) > 0) $btn_continuar = trim($_POST["btn_continuar"]);

if(strlen($btn_continuar)>0) {

	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	if ($_POST['acaoTodas']) {

		$acao = $_POST['acaoTodas'];
		$obs  = trim($_POST["motivo"]);

		if (strlen($obs) == 0) {
			$msg_erro    = " Informe o Motivo da aÁ„o Geral:";
		}

		for ($k = 0 ; $k < $qtde_os; $k++) {

			$x_os   = trim($_POST["os_" . $k]);

			if ($acao == "Acumular") {
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$obs');"; 
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if ($acao == "Recusar") {
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $x_os, '$obs');";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if ($acao == "Aprovar") {
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT status_os FROM tbl_os_status WHERE os = $x_os order by os_status desc limit 1;";
					$res = @pg_exec($con,$sql);
					if(@pg_result($res,0,0) <> 19){
						$sql = "SELECT fn_aprova_os($login_fabrica, $extrato, $x_os, '$obs');";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}
	} else {

		for ($k = 0 ; $k < $qtde_os; $k++) {

			$x_os   = trim($_POST["os_" . $k]);
			$x_obs  = trim($_POST["obs_" . $k]);
			$x_acao = trim($_POST["acao_" . $k]);

			if ($x_acao == "Acumular") {
				if (strlen($x_obs) == 0) {
					$msg_erro    = " Informe a observaÁ„o na OS $x_os. ";
					$linha_erro  = $k;
				}
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$x_obs');";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if ($x_acao == "Recusar") {
				if (strlen($x_obs) == 0) {
					$msg_erro    = " Informe a observaÁ„o na OS $x_os. ";
					$linha_erro  = $k;
				}
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $x_os, '$x_obs');";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if ($x_acao == "Aprovar") {
				$x_obs= "Aprovar";
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT status_os FROM tbl_os_status WHERE os = $x_os order by os_status desc limit 1;";

					$res = @pg_exec($con,$sql);
					if(@pg_result($res,0,0) <> 19){
						$sql = "SELECT fn_aprova_os($login_fabrica, $extrato, $x_os, '$x_obs');";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}
	}
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato 
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (pg_numrows($res) > 0) {
			if (@pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "<script>
		if (confirm('Processo finalizado, deseja fechar a janela?') == true) {
			window.close();
		}else{
		window.location.href = 'extrato_os_aprova.php?extrato=$extrato';
		}
		</script>";
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "RelaÁ„o de Ordens de ServiÁos";
include "cabecalho.php";

?>
<p>

<style type="text/css">
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#d2e4fc;
	}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
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
	color: #000000;
}
.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Conteudo2 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.Principal{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
div.exibe{
	padding:8px;
	color:  #555555;
	display:none;
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
#DIVanexos table{
    width: 700px;
    text-align: center;
    margin: 0 auto;
    margin-top: 20px;
}
#box-uploader-app {
	width: 60%;
}
</style>

<? include "javascript_calendario_new.php" ?>
<script language='javascript' src='../ajax.js'></script>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<script type="text/javascript" src='plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />

<script language='javascript'>

function MostraEsconde(dados,os,imagem,cor){
	var style2 = document.getElementById(dados);
	var img    = document.getElementById(imagem);
	if (style2.style.display){
		$(style2).addClass('exibe');
		style2.style.display = "";
		img.src='imagens/mais.gif';
	}else{
		style2.style.display = "block";
		img.src='imagens/menos.gif';
		$.ajax({
			type: "GET",
			url: "<?= $PHP_SELF ?>",
			data: "op=ver&os=" + escape(os)+"&cor="+escape(cor),
			beforeSend: function(){
				$(style2).html("<B>Carregando...</B><br><img src='imagens/carregar_os.gif'>");
			},
			complete: function(resposta){
				results = resposta.responseText.split("|");
				if (typeof (results[0]) != 'undefined') {
					if (results[0] == 'ok') {
						$(style2).html(results[1]).removeClass('exibe');
					}else{
						alert ('Erro ao abrir OS' );
					}
				}else{
					alert ('Fechamento nao processado');
				}
			}
		});
	}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

function gravar_aprovar(os,linha,extrato,btn,acao1,acao2) {

	var botao = document.getElementById(btn);
	var l     = document.getElementById(linha);
	var a1    = document.getElementById(acao1);
	var a2    = document.getElementById(acao2);
	var acao='aprovar';

	url = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&os="+escape(os)+"&linha="+escape(linha)+"&extrato="+escape(extrato);

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					alert(response[1]);
					a1.disabled='true';
					a2.disabled='true';
 					l.style.background = '#D7FFE1';
					botao.value='Aprovada';
					botao.disabled='true';
				}
				if (response[0]=="0"){
					alert(response[1]);
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<?
if (strlen($_POST["op"]) > 0)       $op       = trim(strtolower($_POST["op"]));
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro.$extrato;$op=''; ?>
	</td>
</tr>
</table>
<?
}

echo "<FORM METHOD=POST NAME='frm_extrato_os' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
echo "<input type='hidden' name='btn_acao' value=''>";

if (strlen($op) == 0) {
	$sql = "SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
						tbl_os.os                                                                       ,
						tbl_os.sua_os                                                                   ,
						to_char (tbl_os.data_digitacao,'DD/MM/YY')                 AS data            ,
						to_char (tbl_os.data_abertura ,'DD/MM/YY')                 AS abertura        ,
						tbl_os.serie                                                                    ,
						tbl_os.codigo_fabricacao                                                        ,
						tbl_os.consumidor_nome                                                          ,
						tbl_os.consumidor_fone                                                          ,
						tbl_os.revenda_nome                                                             ,
						tbl_os.data_fechamento                                                          ,
						tbl_os.pecas                                                 AS total_pecas     ,
						tbl_os.mao_de_obra                                           AS total_mo        ,
						tbl_os.cortesia                                                                 ,
						tbl_os.tipo_atendimento                                                         ,
						tbl_produto.referencia                                                          ,
						tbl_produto.descricao                                                           ,
						tbl_os_extra.extrato                                                            ,
						tbl_os_extra.os_reincidente                                                     ,
						(   SELECT status_os 
							FROM tbl_os_status 
							WHERE tbl_os_status.os = tbl_os.os 
							ORDER BY os_status DESC LIMIT 1
						)                                                            AS status_os       ,
						(   SELECT status_os 
							FROM tbl_os_status 
							WHERE tbl_os_status.os = tbl_os.os 
							AND tbl_os_status.status_os = 19
							ORDER BY os_status DESC LIMIT 1
						)                                                         AS status_os_aprovada       ,
						to_char (tbl_extrato.data_geracao,'DD/MM/YY')              AS data_geracao    ,
						tbl_extrato.total                                            AS total           ,
						tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
						tbl_extrato.pecas                                            AS pecas           ,
						lpad (tbl_extrato.protocolo,5,'0')                           AS protocolo       ,
						tbl_posto.nome                                               AS nome_posto      ,
						tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
						tbl_posto.pais                                               AS pais_posto      ,
						tbl_extrato_pagamento.valor_total                                               ,
						tbl_extrato_pagamento.acrescimo                                                 ,
						tbl_extrato_pagamento.desconto                                                  ,
						tbl_extrato_pagamento.valor_liquido                                             ,
						tbl_extrato_pagamento.nf_autorizacao                                            ,
						to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
						to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
						tbl_extrato_pagamento.autorizacao_pagto                                         ,
						tbl_extrato_pagamento.obs                                                       ,
						tbl_extrato_pagamento.extrato_pagamento                                         ,
						CASE tbl_os.tipo_atendimento
							WHEN 16 THEN 0
							ELSE 1
						END as cortesia_comercial
			FROM        tbl_extrato
			LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
			LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
			LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
			JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
			JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_os.posto
			JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica      = $login_fabrica
			WHERE		tbl_extrato.fabrica = $login_fabrica
			AND         tbl_extrato.extrato = $extrato
			ORDER BY    ";
			
	//hd 39502
	if ($login_fabrica==20) {
		$sql .= "cortesia_comercial ASC,";
	}

	$sql .= " lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
						replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";

	if ($login_fabrica == 20 ){
		$res = pg_exec($con,$sql);
	}else{

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// m?imo de links ?serem exibidos
		$max_res   = 100;				// m?imo de resultados ?serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o nmero de pesquisas (detalhada ou n?) por p?ina

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //
	}

	$ja_baixado = false ;

	if (@pg_numrows($res) == 0) {
		echo "<h1>Nenhum resultado encontrado.</h1>";
	}else{
		echo "<br>";
		if (strlen ($msg_erro) == 0) {
			$extrato_pagamento = pg_result ($res,0,extrato_pagamento) ;
			$valor_total       = pg_result ($res,0,valor_total) ;
			$acrescimo         = pg_result ($res,0,acrescimo) ;
			$desconto          = pg_result ($res,0,desconto) ;
			$valor_liquido     = pg_result ($res,0,valor_liquido) ;
			$nf_autorizacao    = pg_result ($res,0,nf_autorizacao) ;
			$data_vencimento   = pg_result ($res,0,data_vencimento) ;
			$data_pagamento    = pg_result ($res,0,data_pagamento) ;
			$obs               = pg_result ($res,0,obs) ;
			$autorizacao_pagto = pg_result ($res,0,autorizacao_pagto) ;
			$codigo_posto      = pg_result ($res,0,codigo_posto) ;
			$protocolo         = pg_result ($res,0,protocolo) ;
			$pais_posto        = pg_result ($res,0,pais_posto) ;
		}

		$ja_baixado = (strlen ($extrato_pagamento) > 0) ? true : false;

		$sql = "SELECT count(distinct tbl_os_extra.os ) as qtde,
				count(distinct tbl_os_extra.os_reincidente) AS os_reincidente
				FROM   tbl_os_extra
				WHERE  tbl_os_extra.extrato = $extrato";
				
		$resx = pg_exec($con,$sql);
		
		if (pg_numrows($resx) > 0){
			$qtde_os        = pg_result($resx,0,qtde);
			$os_reincidente = pg_result($resx,0,os_reincidente);
		}

		$sql = "SELECT count(distinct tbl_os_status.os)     AS aprovadas
				FROM   tbl_os_extra
				JOIN tbl_os_status ON tbl_os_status.os = tbl_os_extra.os AND tbl_os_status.status_os =19
				WHERE  tbl_os_extra.extrato = $extrato
				";
		$resx = pg_exec($con,$sql);
		
		if (pg_numrows($resx) > 0){
			$aprovadas      = pg_result($resx,0,aprovadas);
		}
		echo "<center>	";
		echo "<table width='750'><tr><td valign='top'>";
		echo "<TABLE width='600' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
		
		echo"<TR class='Titulo'>";
		echo"<TD align='left' colspan='5' background='imagens_admin/azul.gif'><font size='3'> EXTRATO: <b>";
		echo ($login_fabrica == 1) ? $protocolo : $extrato;
		echo "</font></TD>";
		echo "</tr>";
		echo"<TR class='Conteudo2' bgcolor='fafafa'>";
		echo "<TD align='left'> Data: <b>" . pg_result ($res,0,data_geracao) . "</TD>";
		echo "<TD align='left'> Qtde de OS: <b>". $qtde_os ."</TD>";
		echo "<TD align='left'> Reincidentes: <b>". $os_reincidente ."</TD>";
		echo "<TD align='left'> Aprovadas: <b>". $aprovadas ."</TD>";
		echo "<TD align='left'> Total: <b>R$ " . number_format(pg_result ($res,0,total),2,",",".") . "</TD>";
		echo "</TR>";
		echo "<TR class='Conteudo2'  bgcolor='fafafa'>";
		echo "<TD align='left'> CÛdigo: <b>" . pg_result ($res,0,codigo_posto) . " </TD>";
		echo "<TD align='left' colspan='4'> Posto: <b>" . pg_result ($res,0,nome_posto) . "  </TD>";
		echo "</TR>";

		if (in_array($login_fabrica, [20]) && $pais_posto == "BR") {
			echo "<TR class='Conteudo2'  bgcolor='fafafa'>";

			$sqlCaixas = "SELECT lote_extrato
						  FROM tbl_extrato_extra
						  WHERE extrato = {$extrato}";
			$resCaixas = pg_query($con, $sqlCaixas);

			$qtde_caixas = pg_fetch_result($resCaixas, 0, 'lote_extrato');

			echo "<TD align='left'> Qtde caixas: <b>" . $qtde_caixas . " </TD>";
			echo "</TR>";
		}

		echo "</TABLE>";
		echo "</td><td>";

		if ($login_fabrica <> 6) {
			$sql = "SELECT  tbl_linha.nome
					FROM   tbl_os
					JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
					JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
					JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
										AND tbl_linha.fabrica   = $login_fabrica
					JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os 
										AND tbl_os_status.status_os = 19
					WHERE  tbl_os_extra.extrato = $extrato
					GROUP BY tbl_linha.nome
					ORDER BY count(*)";
			$resx = pg_exec($con,$sql);
			
			if (pg_numrows($resx) > 0) {
				echo "<TABLE width='95%' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
				echo "<TR class='Principal'>";
				echo "<TD align='center' background='imagens_admin/azul.gif' colspan='4'>QUANTIDADE</TD>";
				echo "</TR>";
				echo "<TR class='Principal'>";
				echo "<TD align='left' background='imagens_admin/azul.gif'>LINHA</TD>";
				echo "<TD align='center' background='imagens_admin/azul.gif'>OS</TD>";
				echo "<TD align='center' background='imagens_admin/azul.gif'>REINCIDENTE</TD>";
				echo "<TD align='center' background='imagens_admin/azul.gif'>APROVADAS</TD>";
				echo "</TR>";

				for ($i = 0 ; $i < pg_numrows($resx) ; $i++) {
					$linha          = trim(pg_result($resx,$i,nome));

					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

					echo "<TR class='Conteudo' bgcolor='$cor'>";
					echo "<TD align='left'>$linha</TD>";
					echo "<TD align='center'>$qtde_os</TD>";
					echo "<TD align='center'>$os_reincidente</TD>";
					echo "<TD align='center'>$aprovadas</TD>";
					echo "</TR>";
				}
				echo "</TABLE>";
			}
		}
		echo "</td></tr></table>";

		if ($login_fabrica <> 1){
			$sql = "SELECT pedido FROM tbl_pedido WHERE pedido_kit_extrato = $extrato";
			$resE = pg_exec($con,$sql);
			if (pg_numrows($resE) == 0)
				echo "<img src='imagens/btn_pedidopecaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de PeÁas do Kit' border='0' style='cursor:pointer;'>";
			echo "<br>";
			echo "<br>";
		}

		echo "<img border='0' src='imagens/btn_acumulartodoextrato.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Clique aqui p/ acumular todas OSs deste Extrato' style='cursor: hand;'><br><br>";

		echo "<table width='700' align='center'>";
		echo "<tr>";
		echo "<td class='Conteudo'>";

			echo "<table  border='0' cellpadding='0' cellspacing='0' align='center'>";
			echo "<tr>";
			echo "<td bgcolor='FFCCCC'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>REINCID NCIAS</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td bgcolor='#D7FFE1'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>APROVADAS</b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			echo "</tr>";

			//hd 39502
			if ($login_fabrica==20) {
				echo "<tr>";
					echo "<td bgcolor='#FFFF99'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>CORTESIA COMERCIAL</b></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				echo "</tr>";
			}

			if ($login_fabrica == 1) {
				echo "<tr><td height='3'></td></tr>";
				echo "<tr>";
				echo "<td bgcolor='#D7FFE1'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>OS CORTESIA</b></td>";
				echo "</tr>";
			}
			echo "</table>";

			echo "</td>";
			echo "<td>";
				echo "<table align='center' width='300'>";
				echo "<tr>";
				echo "<td><img src='imagens_admin/status_vermelho.gif'></td>";
				echo "<td align='left' class='Conteudo'>OS com valor de m„o de obra ou peÁas zero</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td><img src='imagens_admin/status_amarelo.gif'></td>";
				echo "<td align='left' class='Conteudo'>OS diferenciais</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td><img src='imagens_admin/status_verde.gif'></td>";
				echo "<td align='left' class='Conteudo'>OS sem nenhum problema</td>";
				echo "</tr>";
				echo "</table>";
			echo "</td>";
			echo "</tr>";
		echo "</table>";

		echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='98%' align='center'>";

		if (strlen($msg) > 0) {
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan=9>$msg</TD>\n";
			echo "</TR>\n";
		}

		echo "<TR class='Titulo'>\n";
		echo "<TD width='075' background='imagens_admin/azul.gif'></TD>\n";
		echo "<TD width='075' background='imagens_admin/azul.gif'>OS</TD>\n";
		
		if ($login_fabrica == 1) echo "<TD width='075' background='imagens_admin/azul.gif'>COD. FABR.</TD>\n";
		if ($login_fabrica <> 1) echo "<TD width='075' background='imagens_admin/azul.gif' title='Data Abertura'>AB.</TD>\n";

		echo "<TD width='130' background='imagens_admin/azul.gif'>PRODUTO</TD>\n";

		if ($login_fabrica == 1 OR $login_fabrica == 20 OR $login_fabrica==30) {
			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>PECA</TD>\n";
			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>MO</TD>\n";
			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>PECA+MO</TD>\n";
		}

		echo "<TD width='30' background='imagens_admin/azul.gif'></TD>\n";
		echo "<TD background='imagens_admin/azul.gif'>STATUS</TD>\n";
		echo "<TD background='imagens_admin/azul.gif'>MOTIVO</TD>\n";

		echo "</TR>\n";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$os                 = trim(pg_result ($res,$i,os));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			$data               = trim(pg_result ($res,$i,data));
			$abertura           = trim(pg_result ($res,$i,abertura));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			$serie              = trim(pg_result ($res,$i,serie));
			$codigo_fabricacao  = trim(pg_result ($res,$i,codigo_fabricacao));
			$tipo_atendimento   = trim(pg_result ($res,$i,tipo_atendimento));
			$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
			$consumidor_fone    = trim(pg_result ($res,$i,consumidor_fone));
			$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
			$produto_nome       = trim(pg_result ($res,$i,descricao));
			$produto_referencia = trim(pg_result ($res,$i,referencia));
			$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
			$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
			$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
			$total_pecas        = trim(pg_result ($res,$i,total_pecas));
			$total_mo           = trim(pg_result ($res,$i,total_mo));
			$cortesia           = trim(pg_result ($res,$i,cortesia));
			$status_os          = trim(pg_result ($res,$i,status_os));
			$status_os_aprovada = trim(pg_result ($res,$i,status_os_aprovada));
			$texto              = "";

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
			$btn = ($i % 2 == 0) ? "azul" : "amarelo";

			#Status 93 - Aprovada previamente pelo promotor

			if(($status_os==93) or ($status_os==19) or ($status_os_aprovada==19)) $cor = '#D7FFE1';

			if (strlen($os_reincidente) > 0) {
				$texto = "-R";
				$cor   = "#FFCCCC";
			}

			if ($tipo_atendimento == 16) {
				$cor   = "#FFFF99";
			}

			if ($login_fabrica == 1 && $cortesia == "t") $cor = "#D7FFE1";

			echo "<TR class='Conteudo' id='$i'style='background-color: $cor;'>\n";

			echo "<TD nowrap height='30'>";
			echo "<img src='imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$os','visualizar_$i','$cor');\">";
			echo "</td>";
			echo "<TD nowrap height='30'>$ja_baixado";

			if ($ja_baixado == false) {
				echo "<a href=\"javascript:void(0);\" onclick=\"javascript:MostraEsconde('dados_$i','$os','visualizar_$i','$cor');\"><input type='hidden' name='os_$i' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'>";
			}else{
				echo "<a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_ordem_servico.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
			}

			if($login_fabrica == 1)echo $codigo_posto;
			echo $sua_os . $texto . "</a></TD>\n";

			 echo "<TD align='center'>$abertura</TD>\n";

			echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">";
			if ($login_fabrica == 1) echo $produto_referencia; else echo substr($produto_nome,0,17);
			echo "</ACRONYM></TD>\n";

			$total_os = $total_pecas + $total_mo;
			echo "<TD align='right' nowrap>" . number_format($total_pecas,2,",",".") . "</TD>\n";
			echo "<TD align='right' nowrap>" . number_format($total_mo,2,",",".")    . "</TD>\n";
			echo "<TD align='right' nowrap>" . number_format($total_os,2,",",".")    . "</TD>\n";

			echo "<TD align='center' nowrap width='30'>";

			if($total_pecas=='0' OR $total_pecas==NULL OR $tipo_atendimento==13){  
				echo "<img src='imagens_admin/status_vermelho.gif'>";
			}else{
				//SE TIVER UMA OS COM O MESMO NUMERO DE S?IE COM INTERVALO DE 90 DIAS OU FOR GARANTIA DE CONSERTO
				if ($tipo_atendimento  == 14 OR $tipo_atendimento  == 15 OR $tipo_atendimento  == 16 ){
					echo "<img src='imagens_admin/status_amarelo.gif'>";
				}else {
				//DEMAIS CASOS
				echo "<img src='imagens_admin/status_verde.gif'>";
				}
			}

			echo "</TD>\n";

			echo "<TD align='right' nowrap>";
			echo "<table><tr align='center'>";
			echo "<td><INPUT TYPE='radio' ID='acao_0$i' NAME='acao_$i' value='Aprovar' onclick='javascript:
				 if(this.value==\"Aprovar\"){
					this.form.obs_$i.style.visibility=\"hidden\";
					this.form.aprovar_$i.style.visibility=\"visible\";
					if (document.getElementById(\"aprovado_$i\")){
						document.getElementById(\"aprovado_$i\").style.display = \"block\";
					}
				}'";
			if (($status_os==93) OR ($status_os==19) or ($status_os_aprovada==19))  echo "CHECKED "; 
			echo "></td>";
			echo "<td><INPUT TYPE='radio' ID='acao_1$i' NAME='acao_$i' value='Recusar' ";
			if (($status_os==19) or ($status_os_aprovada==19)) echo "DISABLED "; 
			echo "onclick='javascript: 
				if(this.value==\"Recusar\"){
					this.form.obs_$i.style.visibility=\"visible\";
					this.form.aprovar_$i.style.visibility=\"hidden\";
					if (document.getElementById(\"aprovado_$i\")){
						document.getElementById(\"aprovado_$i\").style.display = \"none\";
					}
				}'></td>";
			echo "<td><INPUT TYPE='radio' ID='acao_2$i' NAME='acao_$i' value='Acumular' ";
			if(($status_os<>19) and ($status_os_aprovada<>19)){
				if ($status_os <> 93){
					echo "CHECKED ";
				}
			}else {
				echo "DISABLED";
			}
			echo " onclick='javascript: 
				if(this.value==\"Acumular\"){
					this.form.obs_$i.style.visibility=\"visible\";
					this.form.aprovar_$i.style.visibility=\"hidden\";
					if (document.getElementById(\"aprovado_$i\")){
						document.getElementById(\"aprovado_$i\").style.display = \"none\";
					}
				}'></td>";
			echo "</td>";
			echo "<tr><td>Aprovada</td><td>Recusada</td><td>Acumular</td></tr></table>";
			echo "</TD>\n";
			echo "<TD align='center' nowrap>";

			//IGOR HD 3537- Estava com erro nas OSs aprovadas e conforme conversa com raphael, deveria adicionar um campo para encontrar qualquer status que fosse como aprovado, pois se o ultimo status fosse reincidencia ele n„o setaria corretamente como aprovado.
			if(($status_os == 93) or ($status_os == 19) or ($status_os_aprovada==19)){
				if ($status_os == 93){
					echo "<div id='aprovado_$i'><font color='#336666'><b>APROVADA PELO PROMOTOR</b></font></div>";
					echo "<INPUT TYPE='text' NAME='obs_$i' class='frm' value='Em analise' style='visibility:hidden'>";
				}else{
					echo "<div id='aprovado_$i'><font color='#336666'><b>APROVADA</b></font></div>";
				}
			}else{
				echo "<INPUT TYPE='text'  NAME='obs_$i' class='frm' value='Em analise'>";
			}
			if($status_os<>19 and $status_os_aprovada<>19){
				$mostrar_gravar = ($status_os == 93) ? "" : "style='visibility:hidden;'";
				echo "<br><input type='button' name='aprovar_$i' id='aprovar_$i' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_aprovar('$os','$i','$extrato','aprovar_$i','acao_1$i','acao_2$i');}\" $mostrar_gravar>\n";
			}
			echo "</TD>\n";

			echo "</TR>\n";
			if($status_os == 14){
				$sql2 = "SELECT observacao 
					FROM tbl_os_status 
					WHERE os=$os 
					ORDER BY os_status DESC 
					LIMIT 1";
				$res2 = pg_exec($con,$sql2);
				$status_observacao = trim(pg_result ($res2,0,observacao));
				echo "<tr class='Conteudo' style='background-color: $cor'><TD align='left' nowrap colspan='12'><b>Foi acumulado pelo seguinte motivo:</b> $status_observacao</TD></tr>\n";
			}

			echo "<tr heigth='1' class='Conteudo'><td colspan='10'>";
			echo "<DIV class='exibe' id='dados_$i' value='1'></DIV>";
			echo "</td></tr>";
		}//FIM FOR
		if (strlen($extrato_valor) == 0 AND $ja_baixado == false ) {
			if ($login_fabrica == 1) $colspan = 10; else $colspan = 6;

			$op='executar';

			echo "<input type='hidden' name='qtde_os' value='$i'>";
			echo "<input type='hidden' name='btn_continuar' value='continuar'>";
			echo "<input type='hidden' name='op' value='$op'>";

			echo "</table>";

			if (in_array($login_fabrica, [20]) && $pais_posto == "BR" && $extrato > 4289724) {

                /*$sqlValidaAnexos = "SELECT tbl_tdocs.obs
                                   FROM tbl_tdocs
                                   WHERE referencia_id = '{$extrato}'
                                   AND situacao = 'ativo'
                                   AND fabrica = {$login_fabrica}";
                $resValidaAnexos = pg_query($con, $sqlValidaAnexos);

                $anexosInseridos = [];
                while ($dadosTdocs = pg_fetch_object($resValidaAnexos)) {

                    $arrObs = json_decode($dadosTdocs->obs, true);

                    $anexosInseridos[] = $arrObs[0]["typeId"];

                }*/

                if (!anexoExtratoEnviadoBosch($extrato)) {  ?>
                	<br />
                	<div style="width: 600px;height: 90px;background-color: #ede99f;color: #9c9400;font-size: 14pt;">
                		<br />
                		<strong>Aguardando Anexos</strong>
                	</div>
                	<br />
                <?php
                } else {
                	 $boxUploader = array(
				        "titulo_tabela" => traduz("Anexos"),
				        "div_id" => "div_anexos",
				        "append" => $anexo_append,
				        "context" => "extrato",
				        "unique_id" => $extrato,
				        "hash_temp" => $anexoNoHash,
				        "bootstrap" => false,
				        "hidden_button" => true
				    );

				    include "box_uploader.php";
                }

			}

			echo "<table border='0'>";
			echo "<tr>";
				echo "<td colspan='3'>";
					echo "<font color='red' align='center'><b>ATEN«√O - O uso desta funÁ„o ter· resultado para todas as Ordens de ServiÁo acima";
				echo "</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><input type='radio' name='acaoTodas' value='Acumular'>Acumular Todas</td>";
				echo "<td><input type='radio' name='acaoTodas' value='Recusar'>Recusar Todas</td>";
				echo "<td><input type='radio' name='acaoTodas' value='Aprovar'>Aprovar Todas</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td colspan='3'>Motivo ObrigatÛrio</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td colspan='3'><textarea cols='70' rows='5' name='motivo'></textarea></td>";
			echo "</tr>";
			echo "</table>";
			echo "<table>";
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='10' align='left'>Preencha o campo observaÁ„o informando o motivo pelo qual ser· ACUMULADO OU RECUSADO</td>";
			echo "</tr>";

			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='10' align='left'> <img border='0' src='imagens/btn_continuar.gif' 	align='absmiddle' onclick=\"javascript:												if(document.frm_extrato_os.acaoTodas[0].checked == false &&							   document.frm_extrato_os.acaoTodas[1].checked == false &&							   document.frm_extrato_os.acaoTodas[2].checked == false){
						contaOS(); 
				} 
				else {
					if(confirm('Tem certeza que deseja efetuar a aÁ„o seleciona para todas as OSs deste extrato?')==true) { document.frm_extrato_os.submit();
					}
				}\" style='cursor: hand;'>";
			echo "</TD>\n";
			echo "</TR>\n";
			echo "</table>";

		}

		echo "<input type='hidden' name='qtde_os' id='qtde_os' value='$i'>";
		echo "</TABLE>\n";
	}//FIM ELSE
	?>
	<input type='hidden' name='qtde_aprovadas' id='qtde_aprovadas'>
	<input type='hidden' name='qtde_recusadas' id='qtde_recusadas' >
	<input type='hidden' name='qtde_acumuladas' id='qtde_acumuladas'>
	<script language='javascript'>
	function contaOS(){
		var qtde_os          = document.getElementById('qtde_os'); 
		var qtde_aprovadas   = document.getElementById('qtde_aprovadas'); 
		var qtde_recusadas   = document.getElementById('qtde_recusadas'); 
		var qtde_acumuladas  = document.getElementById('qtde_acumuladas'); 
		var i;
		var acao;
		qtde_aprovadas.value   = 0;
		qtde_recusadas.value   = 0;
		qtde_acumuladas.value  = 0;
		
		for (i=0;i<qtde_os.value;i++){
			eval('acao = document.frm_extrato_os.acao_'+i);
				if(acao[0].checked == true){		
						qtde_aprovadas.value++;
				}
				if(acao[1].checked == true){
					qtde_recusadas.value++;
				}
				if(acao[2].checked == true){		
					qtde_acumuladas.value++;
					}
		}
		if (confirm('Extrato '+<?echo $extrato?> +' contem :\n'+
		'OS aprovadas: '+ qtde_aprovadas.value + '\n' +
		'OS recusadas: '+ qtde_recusadas.value + '\n' +
		'OS acumuladas: '+ qtde_acumuladas.value + '\n' +
		'Deseja continuar?') == true) {
		document.frm_extrato_os.submit(); 
		}
	}
	</script>

	<?
	if ($login_fabrica == 20){
		// sem paginacao
	}else{
		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		echo "<div>";

		if($pagina < $max_links) {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Pr?ima' e 'Anterior' ser? exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// fun?o que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (P·gina <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
	}

}  // Fecha a visualiza?o dos extratos

echo "<br>";

##### LAN«AMENTO DE EXTRATO AVULSO - INICIO #####
if ($login_fabrica == 1) {
	$sql = "SELECT  'OS SEDEX' AS descricao          ,
					tbl_extrato_lancamento.os_sedex  ,
					''      AS historico             ,
					tbl_extrato_lancamento.automatico,
					sum(tbl_extrato_lancamento.valor) as valor
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY tbl_extrato_lancamento.os_sedex, tbl_extrato_lancamento.automatico";

	$sql = "SELECT 'OS SEDEX' AS descricao ,
					tbl_extrato_lancamento.descricao AS descricao_lancamento ,
					tbl_extrato_lancamento.os_sedex ,
					'' AS historico ,
					tbl_extrato_lancamento.historico AS historico_lancamento,
					tbl_extrato_lancamento.automatico,
					sum(tbl_extrato_lancamento.valor) as valor
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.automatico,
						tbl_extrato_lancamento.descricao,
						tbl_extrato_lancamento.historico;";
}else{
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.os_sedex  ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     ,
					tbl_extrato_lancamento.automatico,
					tbl_extrato_lancamento.extrato_lancamento
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			ORDER BY    tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.descricao";
}
$res_avulso = pg_exec($con,$sql);

if (pg_numrows($res_avulso) > 0) {
	$colspan = ($login_fabrica == 1) ? 5 : 4;
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='$colspan'>LAN«AMENTO DE EXTRATO AVULSO</td>\n";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td>DESCRI«√O</td>\n";
	echo "<td>HIST”RICO</td>\n";
	echo "<td>VALOR</td>\n";
	echo "<td>AUTOM¡TICO</td>\n";
	if ($login_fabrica == 1) echo "<td>A«’ES</td>\n";

	if (in_array($login_fabrica, [20])) {
		echo "<td>ANEXO</td>";
	}
	echo "</tr>\n";
	for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
		$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		$descricao            = pg_result($res_avulso, $j, descricao);
		$historico            = pg_result($res_avulso, $j, historico);
		$os_sedex             = pg_result($res_avulso, $j, os_sedex);
		$extrato_lancamento   = pg_fetch_result($res_avulso, $j, 'extrato_lancamento');

		if ($login_fabrica == 1){
			if (strlen($os_sedex) == 0){
				$descricao = @pg_result($res_avulso, $j, descricao_lancamento);
				$historico = @pg_result($res_avulso, $j, historico_lancamento);
			}
		}
		echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
		echo "<td width='35%'>" . $descricao . "</td>";
		echo "<td width='35%'>" . $historico . "</td>";
		echo "<td width='10%' align='right' nowrap> R$ " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";

		echo "<td width='10%' align='center' nowrap>" ;
		echo (pg_result($res_avulso, $j, automatico) == 't') ? "S" : "&nbsp;";
		echo "</td>";
		echo "<td width='10%' align='center' nowrap>";
		if ($login_fabrica == 1 AND strlen($os_sedex) > 0) echo "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: hand;' alt='Consultar OS Sedex'>";

		if (in_array($login_fabrica, [20])) {
			$tDocs->setContext('avulso');
			$info = $tDocs->getDocumentsByRef($extrato_lancamento)->attachListInfo;

			if (count($info) > 0) {
				foreach ($info as $valor) {
					$link = $valor['link']; 
				?>
					<a href="<?= $link ?>" target="_blank">
						Anexo
					</a>
				<?php
					
				}
			}
		}

		echo "</td>";
		echo "</tr>";
	}
	echo "</table>\n";
	echo "<br>\n";

	# HD 111271

	if (!in_array($login_fabrica, [20])) {
		$dir = "documentos/";
		$dh  = opendir($dir);

		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$extrato") !== false){
				$po = strlen($extrato);
				if(substr($filename, 0,$po)==$extrato){
					echo "<div style='font-size:13px;'>&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagens_admin/clip.png' border='0' width='25'>Anexo</a>&nbsp;&nbsp;</div>";
				}
			}
		}
	}
}
##### LAN«AMENTO DE EXTRATO AVULSO - FIM #####

##### VERIFICA BAIXA MANUAL #####
$sql = "SELECT posicao_pagamento_extrato_automatico FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);
$posicao_pagamento_extrato_automatico = pg_result($res,0,posicao_pagamento_extrato_automatico);

if ($posicao_pagamento_extrato_automatico == 'f' and $login_fabrica <> 1) {
?>

	<HR WIDTH='600' ALIGN='CENTER'>

	<TABLE width='700' border='1' align='center' cellspacing='1' cellpadding='0' bgcolor='#F1F4FA' style='border-collapse: collapse' bordercolor='#d2e4fc'>
	<TR>
		<TD height='20' class="Titulo" colspan='4'><b>PAGAMENTO</b></TD>
	</TR>
	<TR>
		<TD align='left' class="Conteudo2"><center>VALOR TOTAL (R$)</center></TD>
		<TD align='left' class="Conteudo2"><center>ACR…SCIMO (R$)</center></TD>
		<TD align='left' class="Conteudo2"><center>DESCONTO (R$)</center></TD>
		<TD align='left' class="Conteudo2"><center>VALOR LÕQUIDO (R$)</center></TD>
	</TR>

	<TR>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_total'  size='10' maxlength='10' value='" . $valor_total . "' style='text-align:right' class='frm'>";
		else                      echo number_format($valor_total,2,',','.');
	?>
		</TD>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo'  size='10' maxlength='10' value='" . $acrescimo . "' style='text-align:right' class='frm'>";
		else                      echo number_format($acrescimo,2,',','.');
	?>
		</TD>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto'  size='10' maxlength='10' value='" . $desconto . "' style='text-align:right' class='frm'>";
		else                      echo number_format($desconto,2,',','.');
	?>
		</TD>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_liquido'  size='10' maxlength='10' value='" . $valor_liquido . "' style='text-align:right' class='frm'>";
		else                      echo number_format($valor_liquido,2,',','.');
	?>
		</TD>
	</TR>

	<TR>
		<TD align='left' class="Conteudo2"><center>DATA DE VENCIMENTO</center></TD>
		<TD align='left' class="Conteudo2"><center>N NOTA FISCAL</center></TD>
		<TD align='left' class="Conteudo2"><center>DATA DE PAGAMENTO</center></TD>
		<TD align='left' class="Conteudo2"><center>AUTORIZA«√O N</center></TD>
	</TR>

	<TR>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_vencimento'  size='10' maxlength='10' value='" . $data_vencimento . "' class='frm'>";
		else                      echo $data_vencimento;
	?>
		</TD>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='nf_autorizacao'  size='10' maxlength='20' value='" . $nf_autorizacao . "' class='frm'>";
		else                      echo $nf_autorizacao;
	?>
		</TD>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_pagamento'  size='10' maxlength='10' value='" . $data_pagamento . "' class='frm'>";
		else                      echo $data_pagamento;
	?>
		</TD>
		<TD align='center' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='autorizacao_pagto' size='10' maxlength='20' value='" . $autorizacao_pagto . "' class='frm'>";
		else                      echo $autorizacao_pagto;
	?>
		</TD>
	</TR>

	<TR>
		<TD align='left' class="Conteudo2" colspan='4'><center>OBSERVA«√O</center></TD>
	</TR>
	<TR>
		<TD align='center' colspan='4' class='Conteudo2'>
	<?
		if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='obs'  size='96' maxlength='255' value='" . $obs . "' class='frm'>";
		else                      echo $obs;
	?>
		</TD>
	</TR>
	</TABLE>
	</center>
	<BR>

	<?
	if ($ja_baixado == false){
		echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
		echo "<input type='hidden' name='data_final' value='$data_final'>";
		echo "<input type='hidden' name='cnpj' value='$cnpj'>";
		echo "<input type='hidden' name='razao' value='$razao'>";
		echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
		echo"</TABLE>";
	}
}

?>
</FORM>
<br>

<center>
<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>
<? if ($login_fabrica == 1) { ?>
<img src='imagens/btn_imprimirsimplificado_15.gif' onclick="javascript: window.open('os_extrato_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Simplificado' border='0' style='cursor:pointer;'>
<img src='imagens/btn_imprimirdetalhado_15.gif' onclick="javascript: window.open('os_extrato_detalhe_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>
<? } ?>
<br><br>
<img border='0' src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: hand;'>
</center>

<? include "rodape.php"; ?>
