<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (strlen($_POST["btn_acao"]) == 0) {
	$data_inicial = $_GET["data_inicial"];
	$data_final = $_GET["data_final"];
	$cnpj = $_GET["cnpj"];
	$razao = $_GET["razao"];
}

//Porque estÃsetando uma cookie de um dia???

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["btn_continuar"]) == 0) {
	setcookie("link", $REQUEST_URI, time()+60*60*24); # Expira em 1 dia
}

$admin_privilegios="financeiro";
include "autentica_admin.php";


// --====================================================================================--//
$os  = $_GET['os'];
$op  = $_GET['op'];
$cor = $_GET['cor'];
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
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado_idioma.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_os.defeito_constatado                    AS id_dc             ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo ,
					tbl_causa_defeito_idioma.descricao                  AS causa_defeito     ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.obs                                                        ,
					tbl_os.excluida                                                   ,
					tbl_os.produto                                                    ,
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
			
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_causa_defeito_idioma  ON tbl_os.causa_defeito      = tbl_causa_defeito_idioma.causa_defeito

			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_tipo_atendimento_idioma ON tbl_tipo_atendimento_idioma.tipo_atendimento = tbl_os.tipo_atendimento
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
			$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado);
			$produto                     = pg_result ($res,0,produto);
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
			$id_dc                       = trim(pg_result($res,0,id_dc));

        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = " SELECT * FROM tbl_produto_idioma
                        WHERE produto     = $produto
                        AND upper(idioma) = 'ES'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
        }

        $sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
                        WHERE defeito_constatado = $id_dc
                        AND upper(idioma)        = 'ES'";
                        
        $res_idioma = @pg_exec($con,$sql_idioma);
          if (@pg_numrows($res_idioma) >0) {
            $defeito_constatado  = trim(@pg_result($res_idioma,0,descricao));
        }
/*
        $sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
                        WHERE defeito_reclamado = $defeito_reclamado
                        AND upper(idioma)        = 'ES'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $defeito_reclamado_descricao  = trim(@pg_result($res_idioma,0,descricao));
        }
$resposta .="$id_dc $defeito_constatado";
/*
        $sql_idioma = " SELECT * FROM tbl_causa_defeito_idioma
                        WHERE causa_defeito = $causa_defeito
                        AND upper(idioma)   = 'ES'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        
        */
        if (@pg_numrows($res_idioma) >0) {
            $causa_defeito_descricao  = trim(@pg_result($res_idioma,0,descricao));
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



			if($cor=='#F1F4FA') $cor_titulo = '#32508D';
			else                $cor_titulo = '#B6A576';

			if (strlen($os_reincidente) > 0) {
				$sql = "SELECT  tbl_os.sua_os,
								tbl_os.serie
						FROM    tbl_os
						WHERE   tbl_os.os = $os_reincidente;";
				$res1 = @pg_exec ($con,$sql);
				
				$sos   = trim(@pg_result($res1,0,sua_os));
				$serie_r = trim(@pg_result($res1,0,serie));
				
				$resposta .=  "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
				$resposta .=  "<tr>";
				$resposta .=  "<td align='center'><b><font size='1'>ATENCIÓN</font></b></td>";
				$resposta .=  "</tr>";
				$resposta .=  "<tr>";
				$resposta .=  "<td align='center'><font size='1'>ORDEN DE SERVICIO REINCIDENTE. ORDEN DE SERVICIO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a></font></td>";
				$resposta .=  "</tr>";
				$resposta .=  "</table>";
				$resposta .=  "<br>";
			}



			if ($ressarcimento == "t") {
				$resposta .= "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela' >";
				$resposta .= "<TR height='30'>";
				$resposta .= "<TD align='left' colspan='3' bgcolor='$cor'>";
				$resposta .= "<font family='arial' size='2' color='#ffffff'><b>";
				$resposta .= "RESARCIMIENTO FINANCIERO";
				$resposta .= "</b></font>";
				$resposta .= "</TD>";
				$resposta .= "</TR>";

				$resposta .= "<tr>";
				$resposta .= "<TD class='titulo3'  height='15' >RESPONSABLE</TD>";
				$resposta .= "<TD class='titulo3'  height='15' >FECHA</TD>";
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
					$resposta .= "PRODUCTO CAMBIADO";
					$resposta .= "</TD>";
					$resposta .= "</TR>";

					$resposta .= "<tr>";
					$resposta .= "<TD align='left' class='titulo3'  height='15' >RESPONSABLE</TD>";
					$resposta .= "<TD align='left' class='titulo3'  height='15' >FECHA</TD>";
					$resposta .= "<TD align='left' class='titulo3'  height='15' >CAMBIADO POR</TD>";
			#		$resposta .= "<TD class='titulo'  height='15' >&nbsp;</TD>";
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

			#		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' width='80%'>&nbsp;</td>";
					$resposta .= "</tr>";

					//alterado por Sono, incluido o campo orientacao_sac a pedido de Fabricio, chamado 472 
					/*$resposta .= "<tr>";
					$resposta .= "<TD class='titulo3' align='left' colspan='3' height='15' nowrap>ORIENTAÃ‡Ã•ES SAC AO POSTO AUTORIZADO</TD>";
					$resposta .= "</tr>";
					$resposta .= "<tr>";
					$resposta .= "<TD class='conteudo' bgcolor='$cor' align='left' colspan='3' height='15' nowrap >";
					$resposta .= $orientacao_sac;
					$resposta .= "</td>";
					$resposta .= "</tr>";*/
					$resposta .= "</table>";
				}
			}
			$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' 	align='center'>";
			$resposta .="<tr ><td rowspan='4' class='conteudo' bgcolor='$cor' width='300' ><center>OS FABRICANTE<br>&nbsp;<b><FONT SIZE='5' COLOR='#C67700'>";
			
			if ($login_fabrica == 1)             $resposta .= "".$posto_codigo;
			if (strlen($consumidor_revenda) > 0) $resposta .= $sua_os ." - ". $consumidor_revenda;
			else                                 $resposta .= $sua_os;

			if(strlen($sua_os_offline)>0){ 
				$resposta .= "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
				$resposta .= "<tr >";
				$resposta .= "<td class='conteudo' bgcolor='$cor' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
				$resposta .= "</tr>";
				$resposta .= "</table>";
			}

			$resposta .= "</FONT></b><br><u>NF: "; 
			if(strlen($nota_fiscal)==0) $resposta .="NO INFORMADO";
			else                        $resposta .="$nota_fiscal";
			$resposta .="</U></center>";
			$resposta .= "</td>";
			$resposta .= "<td class='inicio' height='15' colspan='4' bgcolor='$cor'>&nbsp;FECHA DE LA OS</td>";
			$resposta .= "</TR>";
			$resposta .= "<TR>";
			$resposta .= "<td class='titulo'width='100' height='15'>ABERTURA&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_abertura</td>";
			$resposta .= "<td class='titulo' width='100' height='15'>DIGITACIÓN&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_digitacao</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<td class='titulo' width='100' height='15'>CERRAMINETO&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_fechamento</td>";
			$resposta .= "<td class='titulo' width='100' height='15'>CERRADA&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_finalizada</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<TD class='titulo'  height='15'>CAMBIO DIRECTO&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor'  height='15'>&nbsp;$data_nf</TD>";
			$resposta .= "<td class='titulo' width='100' height='15'>CERRADO EN &nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;";
			if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
				$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
				$resD = @pg_exec ($con,$sql_data);
				if (@pg_numrows ($resD) > 0) {
					$total_de_dias_do_conserto = pg_result ($resD,0,final);
				}

				if($total_de_dias_do_conserto==0) $resposta .=  'En el mismo día' ;
				else                              $resposta .= $total_de_dias_do_conserto;
				if($total_de_dias_do_conserto==1) $resposta .=  ' día' ;
				if($total_de_dias_do_conserto>1)  $resposta .=  ' días' ;
			}
			$resposta .= "</td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";

	// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
		if($login_fabrica==19 OR $login_fabrica==20){

			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo  height='15' width='90'>ATENDIMIENTO&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$tipo_atendimento - $nome_atendimento </TD>";

			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}//FIM DA PARTE EXCLUSIVA DA LORENZETTI
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
				$resposta .= "<TD class='titulo'  height='15' width='90'>Usuários&nbsp;</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;";
				if($nome_completo )$resposta .= $nome_completo; else $resposta .= $login;  
				$resposta .= "</TD>";
				if(strlen($troca_garantia_data)>0){
				$resposta .= "<TD class='titulo' height='15'width='90'>Data</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$troca_garantia_data </TD>";
				}
				$resposta .= "</TR>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor'  height='15'colspan='4'>";
				if($troca_garantia=='t')
					$resposta .= "<b><center>Troca Direta</center></b>";
				else
					$resposta .= "<b><center>Cambio Via Distribuidor</center></b>";
				$resposta .= "</TD>";
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}
		}

		$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
		$resposta .= "<tr>";
		$resposta .= "<td class='inicio' height='15' colspan='6' bgcolor='$cor'>&nbsp;INFORMACIONES DEL CONSUMIDOR&nbsp;</td>";
		$resposta .= "</tr>";
		$resposta .= "<tr >";
		$resposta .= "<TD class='titulo' height='15' width='90'>NOMBRE&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_nome </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>DIRECCIÓN&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_endereco </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>TELÉFONO&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$consumidor_fone </TD>";
		$resposta .= "</tr>";
		$resposta .= "<tr >";
		$resposta .= "<TD class='titulo' height='15' width='90'>CIUDAD&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_cidade </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>PROVÍNCIA&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$consumidor_estado </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>APARATO POSTAL&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$consumidor_cep </TD>";
		$resposta .= "</tr>";

		$resposta .= "</table>";

		$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
		$resposta .= "<tr>";
		$resposta .= "<td class='inicio' height='15' colspan='6' bgcolor='$cor'>&nbsp;INFORMACIÓN DEL PRODUCTO&nbsp;</td>";
		$resposta .= "</tr>";
		$resposta .= "<tr >";
		$resposta .= "<TD class='titulo' height='15' width='90'>REFERENCIA&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$produto_referencia </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>DESCRIPCIÓN&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$produto_descricao </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>NÚMERO DE SÉRIE&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$serie </TD>";
		$resposta .= "</tr>";

		$resposta .= "</table>";
		if (strlen($aparencia_produto) > 0) { 
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<td class='titulo' height='15' width='300'>APARENCIA GENERAL DE LA HERRAMINETA/PRODUCTO</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor'>&nbsp;$aparencia_produto </td>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
		if (strlen($acessorios) > 0) { 
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo' height='15' width='300'>ACCESORIOS DEJADOS JUNTO A LA HERRAMINETA</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor'>&nbsp;$acessorios; </TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
		if (strlen($defeito_reclamado) > 0) { 
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo' height='15'width='300'>&nbsp;INFORMACIONES SOBRE EL DEFECTO</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' >&nbsp;";

			$resposta .= "</TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
		$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
		$resposta .= "<TR>";
		$resposta .= "<TD  height='15' class='inicio' colspan='4' bgcolor='$cor'>&nbsp;DEFECTOS</TD>";
		$resposta .= "</TR>";
		$resposta .= "<TR>";
		$resposta .= "<TD class='titulo' height='15' width='90'>RECLAMADO</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' width='150'> &nbsp; $defeito_reclamado_descricao</TD>";
//defeito constatado
		$resposta .= "<TD class='titulo' height='15' width='90'>";
		if($login_fabrica==20)$resposta .= "REPARO";
		else                  $resposta .= "CONSTATADO";
		$resposta .= "</td>";
		$resposta .= "<td class='conteudo' bgcolor='$cor' height='15'>&nbsp;";
		if($login_fabrica==20) $resposta .= $defeito_constatado_codigo.' - ';
		$resposta .= $defeito_constatado;
		$resposta .="</TD>";
		$resposta .= "</TR>";
		$resposta .= "<TR>";

		$resposta .= "<TD class='titulo' height='15' width='90'>";
		if($login_fabrica==6)      $resposta .= "SOLUÇÃO";
		elseif($login_fabrica==20) $resposta .= "DEFECTO";
		else                       $resposta .= "CAUSA"  ;

		$resposta .= "&nbsp;</td>";
		$resposta .= "<td class='conteudo' bgcolor='$cor' colspan='3' height='15'>";
		if($login_fabrica==6){
			if (strlen($solucao_os)>0){
				$xsql="SELECT descricao from tbl_servico_realizado_idioma where servico_realizado= $solucao_os limit 1";
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
			$xsql="SELECT descricao from tbl_servico_realizado_idioma where servico_realizado= $solucao_os limit 1";
			$xres = pg_exec($con, $xsql);
			$xsolucao = trim(pg_result($xres,0,descricao));
			
			$resposta .= "<tr>";
			$resposta .= "<td class='titulo' height='15' width='90'>IDENTIFICACIÓN&nbsp;</td>";
			$resposta .= "<td class='conteudo'bgcolor='$cor'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
			$resposta .= "</tr>";
		}
	}

		$resposta .= "</TABLE>";

		$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
		$resposta .= "<TR>";
		$resposta .= "<TD colspan='";
		if ($login_fabrica == 1) {$resposta .= "9"; }else{ $resposta .= "4"; }
		$resposta .= "' class='inicio' bgcolor='$cor'>&nbsp;DIAGNÓSTICOS - COMPONENTES - MANTENIMIENTO EXECUTADOS</TD>";
		$resposta .= "</TR>";
		$resposta .= "<TR>";

		$resposta .= "<TD class='titulo2'>COMPONENTE</TD>";
		$resposta .= "<TD class='titulo2'>CTD</TD>";

		$resposta .= "<TD class='titulo2'>DIGIT.</TD>";
		$resposta .= "<TD class='titulo2'>PRECIO NETO</TD>";

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
		$res = @pg_exec($con,$sql);
		$total = @pg_numrows($res);

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

			$peca_referencia = pg_result($res,$i,referencia_peca);
			$peca_descricao  = pg_result($res,$i,descricao_peca);
					$sql_idioma = "SELECT tbl_peca_idioma.* FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE referencia = '$peca_referencia' AND upper(idioma) = 'ES'";

		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$peca_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

			$resposta .= "<TR>";

			$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:left;'>".$peca_referencia . " - " . $peca_descricao."</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'>".pg_result($res,$i,qtde)."</TD>";

			$resposta .= "<TD class='conteudo' bgcolor='$cor' >".pg_result($res,$i,digitacao_item)."</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'> ".number_format (pg_result($res,$i,preco),2,",",".")."</TD>";

			$resposta .= "</tr>";
		}
		$resposta .= "</TABLE>";
		$resposta .= "<BR>";

		if (strlen($obs) > 0) { 
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor'><b>OBS:</b>&nbsp;$obs</TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		} 
//fim
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
//FIM DA EXIBI?O DO AJAX

// --====================================================================================--//



if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));
if (strlen($_POST["extrato"]) > 0)  $extrato = trim($_POST["extrato"]);
if (strlen($_GET["extrato"]) > 0)   $extrato = trim($_GET["extrato"]);


$msg_erro = "";

if ($btn_acao == 'pedido'){
	header ("Location: relatorio_pedido_peca_kit.php?extrato=$extrato");
	exit;
}

if ($btn_acao == 'baixar') {

	if (strlen($_POST["extrato_pagamento"]) > 0) $extrato_pagamento = trim($_POST["extrato_pagamento"]);
	if (strlen($_GET["extrato_pagamento"]) > 0)  $extrato_pagamento = trim($_GET["extrato_pagamento"]);

	$valor_total     = trim($_POST["valor_total"]) ;
	if(strlen($valor_total) > 0)   $xvalor_total = "'".str_replace(",",".",$valor_total)."'";
	else                           $xvalor_total = 'NULL';

	$acrescimo       = trim($_POST["acrescimo"]) ;
	if(strlen($acrescimo) > 0)     $xacrescimo = "'".str_replace(",",".",$acrescimo)."'";
	else                           $xacrescimo = 'NULL';

	$desconto        = trim($_POST["desconto"]) ;
	if(strlen($desconto) > 0)      $xdesconto = "'".str_replace(",",".",$desconto)."'";
	else                           $xdesconto = 'NULL';

	$valor_liquido   = trim($_POST["valor_liquido"]) ;
	if(strlen($valor_liquido) > 0) $xvalor_liquido = "'".str_replace(",",".",$valor_liquido)."'";
	else                           $xvalor_liquido = 'NULL';

	$nf_autorizacao = trim($_POST["nf_autorizacao"]) ;
	if(strlen($nf_autorizacao) > 0) $xnf_autorizacao = "'$nf_autorizacao'";
	else                            $xnf_autorizacao = 'NULL';

	$autorizacao_pagto = trim($_POST["autorizacao_pagto"]) ;
	if(strlen($nf_autorizacao) > 0) $xautorizacao_pagto = "'$autorizacao_pagto'";
	else                            $xautorizacao_pagto = 'NULL';

	if (strlen($_POST["data_vencimento"]) > 0) {
		$data_pagamento = trim($_POST["data_pagamento"]) ;
		$xdata_pagamento = str_replace ("/","",$data_pagamento);
		$xdata_pagamento = str_replace ("-","",$xdata_pagamento);
		$xdata_pagamento = str_replace (".","",$xdata_pagamento);
		$xdata_pagamento = str_replace (" ","",$xdata_pagamento);

		$dia = trim (substr ($xdata_pagamento,0,2));
		$mes = trim (substr ($xdata_pagamento,2,2));
		$ano = trim (substr ($xdata_pagamento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;

//-=============Verifica data=================-//
		
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_pagamento = "'" . $xdata_pagamento . "'";
		}else{
			$msg_erro="La fecha de pagamiento no está en formato válido";
		}
	}else{
		$xdata_pagamento = "'NULL'";
	}
	
	if (strlen($_POST["data_vencimento"]) > 0) {
		$data_vencimento = trim($_POST["data_vencimento"]) ;
		$xdata_vencimento = str_replace ("/","",$data_vencimento);
		$xdata_vencimento = str_replace ("-","",$xdata_vencimento);
		$xdata_vencimento = str_replace (".","",$xdata_vencimento);
		$xdata_vencimento = str_replace (" ","",$xdata_vencimento);

		$dia = trim (substr ($xdata_vencimento,0,2));
		$mes = trim (substr ($xdata_vencimento,2,2));
		$ano = trim (substr ($xdata_vencimento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_vencimento = "'" . $xdata_vencimento . "'";
		}else{
			$msg_erro .="<br>La fecha de vencimiento no está en formato válido<br>";
		}
	}else{
		$xdata_vencimento = "'NULL'";
	}

	if (strlen($_POST["obs"]) > 0) {
		$obs = trim($_POST["obs"]) ;
		$xobs = "'" . $obs . "'";
	}else{
		$xobs = "NULL";
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($extrato_pagamento) > 0) {
			$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = $login_fabrica AND extrato = $extrato";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) == 0) $msg_erro = "Erro ao cadastrar baixa. Extrato não pertence a esta fábrica.";
		}

		if (strlen($msg_erro) == 0) {
			if (strlen($extrato_pagamento) > 0) {
				$sql = "UPDATE tbl_extrato_pagamento SET
							extrato           = $extrato           ,
							valor_total       = $xvalor_total       ,
							acrescimo         = $xacrescimo         ,
							desconto          = $xdesconto          ,
							valor_liquido     = $xvalor_liquido     ,
							nf_autorizacao    = $xnf_autorizacao    ,
							data_vencimento   = $xdata_vencimento   ,
							data_pagamento    = $xdata_pagamento    ,
							autorizacao_pagto = $xautorizacao_pagto ,
							obs               = $xobs               ,
							admin             = $login_admin
						WHERE tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
						AND   tbl_extrato_pagamento.extrato           = $extrato
						AND   tbl_extrato.fabrica = $login_fabrica";
			}else{
				$sql = "INSERT INTO tbl_extrato_pagamento (
							extrato           ,
							valor_total       ,
							acrescimo         ,
							desconto          ,
							valor_liquido     ,
							nf_autorizacao    ,
							data_vencimento   ,
							data_pagamento    ,
							autorizacao_pagto ,
							obs               ,
							admin
						)VALUES(
							$extrato           ,
							$xvalor_total      ,
							$xacrescimo        ,
							$xdesconto         ,
							$xvalor_liquido    ,
							$xnf_autorizacao   ,
							$xdata_vencimento  ,
							$xdata_pagamento   ,
							$xautorizacao_pagto,
							$xobs              ,
							$login_admin
						)";
			}
			echo $sql;
			//$res = pg_exec ($con,$sql);
			//$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

//--=== ACUMULA TODAS AS OS'S PARA O PR?IMO EXTRATO - OK================================--\\

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

// --====================================================================================--\\


//NOVO PROGRAMA PARA ACUMULAR

if (strlen($_POST["btn_continuar"]) > 0) $btn_continuar = trim($_POST["btn_continuar"]);

if(strlen($btn_continuar)>0){
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {

		$x_os   = trim($_POST["os_" . $k]);
		$x_obs  = trim($_POST["obs_" . $k]);
		$x_acao = trim($_POST["acao_" . $k]);

		if ($x_acao == "Acumular") {
			if (strlen($x_obs) == 0) {
				$msg_erro    = " Informe la observación en la OS $x_os. ";
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
				$msg_erro    = " Informe la observación en la OS $x_os. ";
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
				$sql = "SELECT fn_aprova_os($login_fabrica, $extrato, $x_os, '$x_obs');";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
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
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "Relación de Órdenes de Servicio";
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
</style>
<!-- AJAX PARA EXIBIR OS DADOS DA OS -->
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaOS (http , componente ) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com = document.getElementById(componente);
					com.innerHTML   = results[1];
				}else{
					alert ('Error para abrir la OS \n');
				}
			}else{
				alert ('Cierre no procesado');
			}
		}
	}
}

function pegaOS (os,dados,cor) {
	url = "<?= $PHP_SELF ?>?op=ver&os=" + escape(os)+"&cor="+escape(cor) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaOS (http , dados) ; } ;
	http.send(null);
}



function MostraEsconde(dados,os,imagem,cor)
{
	if (document.getElementById)
	{
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			img.src='../imagens/mais.gif';

			}
		else{
			style2.style.display = "block";
			img.src='../imagens/menos.gif';
			pegaOS(os,dados,cor);
		}
		

		
	}
}
</script>


<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<!--aqui-->
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

?>
<!--aqui-->

<?
echo "<FORM METHOD=POST NAME='frm_extrato_os' ACTION=\"$PHP_SELF\">";

echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
echo "<input type='hidden' name='btn_acao' value=''>";

?>

<?
/*
Verifica de a a?o ?"RECUSAR" ou "ACUMULAR"
para somente mostrar a tela para a digita?o da observa?o.
*/

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
					to_char (tbl_extrato.data_geracao,'DD/MM/YY')              AS data_geracao    ,
					tbl_extrato.total                                            AS total           ,
					tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
					tbl_extrato.pecas                                            AS pecas           ,
					tbl_extrato.aprovado                                                            ,
					lpad (tbl_extrato.protocolo,5,'0')                           AS protocolo       ,
					tbl_posto.nome                                               AS nome_posto      ,
					tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
					tbl_extrato_pagamento.valor_total                                               ,
					tbl_extrato_pagamento.acrescimo                                                 ,
					tbl_extrato_pagamento.desconto                                                  ,
					tbl_extrato_pagamento.valor_liquido                                             ,
					tbl_extrato_pagamento.nf_autorizacao                                            ,
					to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
					to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
					tbl_extrato_pagamento.autorizacao_pagto                                         ,
					tbl_extrato_pagamento.obs                                                       ,
					tbl_extrato_pagamento.extrato_pagamento
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
		ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0)               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC";

if ($login_fabrica == 20 ){
	// sem paginacao
	//if ($ip == '201.0.9.216') echo "<br>$sql<br>";

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
	echo "<h1>Ningún resultado encuentrado.</h1>";
}else{
	?>
	<br>

<?

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
		$aprovado          = pg_result ($res,0,aprovado) ;
	}

	if (strlen ($extrato_pagamento) > 0) $ja_baixado = true ;
	else $ja_baixado = false;
 
	$sql = "SELECT count(*) as qtde
			FROM   tbl_os_extra
			WHERE  tbl_os_extra.extrato = $extrato";
	$resx = pg_exec($con,$sql);
	
	if (pg_numrows($resx) > 0) $qtde_os = pg_result($resx,0,qtde);
	
	echo "<table width='750'><tr><td valign='top'>";
	echo "<TABLE width='600' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
	
	echo"<TR class='Titulo'>";

//	if ($sistema_lingua=='ES') 
			echo"<TD align='left' colspan='3' background='imagens_admin/azul.gif'><font size='3'> EXTRACTO: <b>";
//	else
//		echo"<TD align='left' colspan='3' background='imagens_admin/azul.gif'><font size='3'> EXTRATO: <b>";
	
	if ($login_fabrica == 1) echo $protocolo;
	else                     echo $extrato;
	echo "</font></TD>";
	echo "</tr>";
	echo"<TR class='Conteudo2' bgcolor='fafafa'>";
	
//	if ($sistema_lingua=='ES') {
		echo "<TD align='left'> Fecha: <b>" . pg_result ($res,0,data_geracao) . "</TD>";
		echo "<TD align='left'> Ctd. OS: <b>". $qtde_os ."</TD>";
		echo "<TD align='left'> Total: <b> " . number_format(pg_result ($res,0,total),2,",",".") . "</TD>";
		echo "</TR>";
		echo "<TR class='Conteudo2'  bgcolor='fafafa'>";
		echo "<TD align='left'> Código: <b>" . pg_result ($res,0,codigo_posto) . " </TD>";
		echo "<TD align='left' colspan='2'> Servicio: <b>" . pg_result ($res,0,nome_posto) . "  </TD>";
//	} else {
//		echo "<TD align='left'> Data: <b>" . pg_result ($res,0,data_geracao) . "</TD>";
//		echo "<TD align='left'> Qtde de OS: <b>". $qtde_os ."</TD>";
//		echo "<TD align='left'> Total: <b> " . number_format(pg_result ($res,0,total),2,",",".") . "</TD>";
//		echo "</TR>";
//		echo "<TR class='Conteudo2'  bgcolor='fafafa'>";
//		echo "<TD align='left'> Código: <b>" . pg_result ($res,0,codigo_posto) . " </TD>";
//		echo "<TD align='left' colspan='2'> Posto: <b>" . pg_result ($res,0,nome_posto) . "  </TD>";
//	}

	
	
	echo "</TR>";
	echo "</TABLE>";
	echo "</td><td>";

	if ($login_fabrica <> 6) {
		$sql = "SELECT  count(*) as qtde,
						tbl_linha.nome
				FROM   tbl_os
				JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
				JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
				JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
									AND tbl_linha.fabrica   = $login_fabrica
				WHERE  tbl_os_extra.extrato = $extrato
				GROUP BY tbl_linha.nome
				ORDER BY count(*)";
		$resx = pg_exec($con,$sql);
		
		if (pg_numrows($resx) > 0) {
			echo "<TABLE width='95%' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
			echo "<TR class='Principal'>";

//			if ($sistema_lingua=='ES') {
				echo "<TD align='left' background='imagens_admin/azul.gif'>LINEA</TD>";
				echo "<TD align='center' background='imagens_admin/azul.gif'>Ctd. OS</TD>";
//			} else {
//				echo "<TD align='left' background='imagens_admin/azul.gif'>LINHA</TD>";
//				echo "<TD align='center' background='imagens_admin/azul.gif'>QTDE OS</TD>";
//			}
			echo "</TR>";

			for ($i = 0 ; $i < pg_numrows($resx) ; $i++) {

				$linha = trim(pg_result($resx,$i,nome));
				$qtde  = trim(pg_result($resx,$i,qtde));

				if ($i % 2 == 0) $cor = "#F1F4FA";
				else             $cor = "#F7F5F0";

				echo "<TR class='Conteudo' bgcolor='$cor'>";
				echo "<TD align='left'>$linha</TD>";
				echo "<TD align='center'>$qtde</TD>";
				echo "</TR>";
			}
			echo "</TABLE>";
		}
	}
	echo "</td></tr></table>";

	if ($login_fabrica <> 1){
		$sql = "SELECT pedido FROM tbl_pedido WHERE pedido_kit_extrato = $extrato";
		$resE = pg_exec($con,$sql);
		if (pg_numrows($resE) == 0) {
//			if ($sistema_lingua=='ES') {
				//echo "<img src='imagens_admin/btn_pedidopiezaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedidos de piezas del kit' border='0' style='cursor:pointer;'>";
//			} else {
//					echo "<img src='imagens_admin/btn_pedidopecaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de Peças do Kit' border='0' style='cursor:pointer;'>";
//			}
		}
		echo "<br>";
		echo "<br>";
	}
	if(strlen($aprovado)==0){
//	if ($sistema_lingua=='ES') {
		echo "<img border='0' src='imagens/btn_acumulartodoextracto.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Haga um click aquí para acumular todas las OS de este extracto' style='cursor: hand;'><br><br>";
	}
//	} else {
//		echo "<img border='0' src='imagens/btn_acumulartodoextrato.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Clique aqui p/ acumular todas OSs deste Extrato' style='cursor: hand;'><br><br>";
//	}
	echo "<table width='700' align='center'>";
	echo "<tr>";
	echo "<td class='Conteudo'>";

		echo "<table  border='0' cellpadding='0' cellspacing='0' align='center'>";
		echo "<tr>";
//		if ($sistema_lingua=='ES') {
			echo "<td bgcolor='FFCCCC'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>REINCIDENCIAS</b></td>";
//		} else {
//			echo "<td bgcolor='FFCCCC'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>REINCIDÊNCIAS</b></td>";
//		}
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "</tr>";
		echo "<tr>";
//		if ($sistema_lingua=='ES') {
			echo "<td bgcolor='#D7FFE1'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>APROBADAS</b></td>";
//		} else {
//			echo "<td bgcolor='#D7FFE1'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>APROVADAS</b></td>";
//		}
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "</tr>";
	
//		if ($login_fabrica == 1) {
//			echo "<tr><td height='3'></td></tr>";
//			echo "<tr>";
//			echo "<td bgcolor='#D7FFE1'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>OS CORTESIA</b></td>";
//			echo "</tr>";
//		}
		echo "</table>";

		echo "</td>";
		echo "<td>";
			echo "<table align='center' width='300'>";
			echo "<tr>";
			echo "<td><img src='imagens_admin/status_vermelho.gif'></td>";
//			if ($sistema_lingua=='ES') {
				echo "<td align='left' class='Conteudo'>OS con valor de mano de obra o piezas zero</td>";
//			} else {
//				echo "<td align='left' class='Conteudo'>OS com valor de mão de obraou peças zero</td>";
//			}
			echo "</tr>";
			echo "<tr>";
			echo "<td><img src='imagens_admin/status_amarelo.gif'></td>";
//			if ($sistema_lingua=='ES') {
				echo "<td align='left' class='Conteudo'>OS diferenciais</td>";
//			} else {
//				echo "<td align='left' class='Conteudo'>OS OS diferenciales</td>";
//			}
			echo "</tr>";
			echo "<tr>";
			echo "<td><img src='imagens_admin/status_verde.gif'></td>";
//			if ($sistema_lingua=='ES') {
				echo "<td align='left' class='Conteudo'>OS sem nenhum problema</td>";
//			} else {
//				echo "<td align='left' class='Conteudo'>OS sin ningún problema</td>";
//			}
			echo "</tr>";
			echo "</table>";
		echo "</td>";
		echo "</tr>";
	echo "</table>";






	echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='750' align='center'>";

	if (strlen($msg) > 0) {
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=9>$msg</TD>\n";
		echo "</TR>\n";
	}

	echo "<TR class='Titulo'>\n";
	echo "<TD width='075' background='imagens_admin/azul.gif'></TD>\n";
	echo "<TD width='075' background='imagens_admin/azul.gif'>OS</TD>\n";
	
	if ($login_fabrica == 1) echo "<TD width='075' background='imagens_admin/azul.gif'>COD. FABR.</TD>\n";
	if ($login_fabrica <> 1) echo "<TD width='075' background='imagens_admin/azul.gif'>ABERTURA</TD>\n";
	
//	if ($sistema_lingua=='ES') {
		echo "<TD width='130' background='imagens_admin/azul.gif'>PRODUCTO</TD>\n";
//	} else {
//		echo "<TD width='130' background='imagens_admin/azul.gif'>PRODUTO</TD>\n";
//	}
	if ($login_fabrica == 1 OR $login_fabrica == 20) {
//		if ($sistema_lingua=='ES') {
			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>TOTAL DE PIEZAS</TD>\n";
			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>TOTAL MO</TD>\n";
			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>PIEZA + MO</TD>\n";
//		} else {
//			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>TOTAL PECA</TD>\n";
//			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>TOTAL MO</TD>\n";
//			echo "<TD width='80' nowrap background='imagens_admin/azul.gif'>PECA + MO</TD>\n";
//		}
	}

	
	echo "<TD width='30' background='imagens_admin/azul.gif'></TD>\n";
	echo "<TD background='imagens_admin/azul.gif'>STATUS</TD>\n";

//	if ($sistema_lingua=='ES') {
		echo "<TD background='imagens_admin/azul.gif'>RAZÓN</TD>\n";
//	} else {
//		echo "<TD background='imagens_admin/azul.gif'>MOTIVO</TD>\n";
//	}


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
		$texto              = "";

			$sql_idioma = "SELECT tbl_produto_idioma.* FROM tbl_produto_idioma JOIN tbl_produto using(produto)WHERE referencia = '$produto_referencia' AND upper(idioma) = 'ES'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$produto_nome  = trim(@pg_result($res_idioma,0,descricao));
			}


		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}

		if (strlen($os_reincidente) > 0) {
			$texto = "-R";
			$cor   = "#FFCCCC";
		}
		if($status_os==19)$cor = '#D7FFE1';
		if ($login_fabrica == 1 && $cortesia == "t") $cor = "#D7FFE1";

		echo "<TR class='Conteudo' style='background-color: $cor;'>\n";

//--=========================================================================================================--\\
		echo "<TD nowrap height='30'>";
		echo "<img src='imagens_admin/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$os','visualizar_$i','$cor');\">";
		echo "</td>";
//--=========================================================================================================--\\
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

//--=== VALOR DA OS =========================================================================================--\\
		$total_os = $total_pecas + $total_mo;
		echo "<TD align='right' nowrap> " . number_format($total_pecas,2,",",".") . "</TD>\n";
		echo "<TD align='right' nowrap> " . number_format($total_mo,2,",",".")    . "</TD>\n";
		echo "<TD align='right' nowrap> " . number_format($total_os,2,",",".")    . "</TD>\n";

//--=== STATUS DA OS - CORES ================================================================================--\\
		echo "<TD align='center' nowrap width='30'>";

		//SE O PRE? DA PE? ESTIVER ZERADO OU FOR TROCA DE PRODUTO
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
		echo"<td><INPUT TYPE='radio' NAME='acao_$i' value='Aprovar' onclick='javascript: if(this.value==\"Aprovar\")this.form.obs_$i.style.visibility=\"hidden\";'";
		if ($status_os==19) echo "CHECKED "; 
		echo"></td>";
		echo "<td><INPUT TYPE='radio' NAME='acao_$i' value='Recusar' ";
		if ($status_os==19) echo "DISABLED "; 
		echo "onclick='javascript: if(this.value==\"Recusar\")this.form.obs_$i.style.visibility=\"visible\";'></td>";
		echo "<td><INPUT TYPE='radio' NAME='acao_$i' value='Acumular' ";
		if($status_os<>19) echo "CHECKED ";
		else echo "DISABLED";
		echo " onclick='javascript: if(this.value==\"Acumular\")this.form.obs_$i.style.visibility=\"visible\";'></td>";
		echo "</td>";

//		if ($sistema_lingua=='ES') {
			echo "<tr><td>Aprobadas</td><td>Rechazadas</td><td>Acumular</td></tr></table>";
//		} else {
//			echo "<tr><td>Aprovada</td><td>Recusada</td><td>Acumular</td></tr></table>";
//		}
		
		echo "</TD>\n";
		echo "<TD align='center' nowrap>";
		if($status_os==19) {
//			if ($sistema_lingua=='ES') {
				echo "<font color='#336666'><b>APROBADA</b></font>";
//			} else {
//				echo "<font color='#336666'><b>APROVADA</b></font>";
//			}
		} else {
			echo "<INPUT TYPE='text'  NAME='obs_$i' class='frm' value='En analisis'>";
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


//--=== DADOS DA OS ================================================================================--\\
	echo "<tr heigth='1' class='Conteudo'><td colspan='10'>";
	echo "<DIV class='exibe' id='dados_$i' value='1'><B>Carregando...</B><br><img src='../imagens/carregar_os.gif'></DIV>";
	echo "</td></tr>";


	}//FIM FOR
	if (strlen($extrato_valor) == 0 AND $ja_baixado == false ) {
		if ($login_fabrica == 1) $colspan = 10; else $colspan = 6;
		
		$op='executar';
		
		echo "<input type='hidden' name='qtde_os' value='$i'>";
		echo "<input type='hidden' name='btn_continuar' value='continuar'>";
		echo "<input type='hidden' name='op' value='$op'>";
		

		echo "<TR class='menu_top'>\n";
//		if ($sistema_lingua=='ES') {
			echo "<TD colspan='10' align='left'>Llene el campo observación informando la razón de ser ACUMULADO o RECHASADO</td>";
//		} else {
//			echo "<TD colspan='10' align='left'>Preencha o campo observação informando o motivo pelo qual será ACUMULADO OU RECUSADO</td>";
//		}
		echo "</tr>";

		echo "<TR class='menu_top'>\n";
		echo "<TD colspan='10' align='left'>";
		if(strlen($aprovado)==0){
			echo " <img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick='javascript: document.frm_extrato_os.submit()' style='cursor: hand;'>";
		}else{
			echo "EXTRACTO APROBADO";
		}
		echo "</TD>\n";
		echo "</TR>\n";
	}
//Quantidade de OS na tela
	
	echo "<input type='hidden' name='qtde_os' value='$i'>";
	
	echo "</TABLE>\n";
}//FIM ELSE

if ($login_fabrica == 20){
	// sem paginacao
}else{

}

}  // Fecha a visualiza?o dos extratos


echo "<br>";

##### LAN?MENTO DE EXTRATO AVULSO - IN?IO #####
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
//echo $sql;
}else{
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.os_sedex  ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     ,
					tbl_extrato_lancamento.automatico
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			ORDER BY    tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.descricao";
}
//echo $sql;
$res_avulso = pg_exec($con,$sql);

if (pg_numrows($res_avulso) > 0) {
	if ($login_fabrica == 1) $colspan = 5;
	else                     $colspan = 4;
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='$colspan'>LANCAMIENTO DE EXTRACTO AVULSO</td>\n";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td>DESCRIPCIÓN</td>\n";
	echo "<td>HISTÓRICO</td>\n";
	echo "<td>VALOR</td>\n";
	echo "<td>AUTOMATICO</td>\n";
	if ($login_fabrica == 1) echo "<td>AÇÕES</td>\n";
	echo "</tr>\n";
	for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
		$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
			
		$descricao            = pg_result($res_avulso, $j, descricao);
		$historico            = pg_result($res_avulso, $j, historico);
		$os_sedex             = pg_result($res_avulso, $j, os_sedex);

		if ($login_fabrica == 1){
			if (strlen($os_sedex) == 0){
				$descricao = @pg_result($res_avulso, $j, descricao_lancamento);
				$historico = @pg_result($res_avulso, $j, historico_lancamento);
			}
		}
		echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
		echo "<td width='35%'>" . $descricao . "</td>";
		echo "<td width='35%'>" . $historico . "</td>";
		echo "<td width='10%' align='right' nowrap>  " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";

		echo "<td width='10%' align='center' nowrap>" ;
		if (pg_result($res_avulso, $j, automatico) == 't') {
			echo "S";
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
		echo "<td width='10%' align='center' nowrap>";
		if ($login_fabrica == 1 AND strlen($os_sedex) > 0) echo "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: hand;' alt='Consultar OS Sedex'>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>\n";
	echo "<br>\n";
}
##### LAN?MENTO DE EXTRATO AVULSO - FIM #####



 // fecha verifica?o se f?rica usa baixa manual

?>
</FORM>
<br>

<center>

<br><br>
<!--<img border='0' src='imagens/btn_volver.gif' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: hand;'>-->
</center>

<? include "rodape.php"; ?>
