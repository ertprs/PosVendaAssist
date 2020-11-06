<?php 

$cons_status_checkpoint = "0,1,2,3,4,8,9,28";

$cons_lista_de_legendas = [
						    ['descricao' => 'Reincidências', 'cor' => '#D7FFE1'],
						    ['descricao' => 'OSs abertas há mais de 25 dias sem data fechamento', 'cor' => '#91C8FF'],
						    ['descricao' => 'OS reincidente e aberta a mais de 25 dias', 'cor' => '#CC9900'],
						    ['descricao' => 'OS com Troca de Produto', 'cor' => '#FFCC66'],
						    ['descricao' => 'Os com Ressarcimento', 'cor' => '#CCCCFF']
];

$defaultFieldsCsv = [
    'os' => 'OS',
    'serie' => 'SERIE',
    'abertura' => 'DATA ABERTURA',
    'fechamento' => 'DATA FECHAMENTO',
    'descricao' => 'TIPO ATENDIMENTO',
    'consumidor_revenda' => 'C/R',
    'posto_nome' => 'NOME POSTO',
    'contato_cidade' => 'CIDADE',
    'contato_estado' => 'ESTADO',
    'revenda_nome' => 'CONSUMIDOR/REVENDA',
    'nota_fiscal' => 'NF',
    'produto_descricao' => 'PRODUTO',
    'status_checkpoint' => 'STATUS',
    'situacao' => 'SITUACAO'
];

$defaultFieldsPreOSCsv =[
            'hd_chamado' => 'N ATENDIMENTO',
            'serie' => 'SERIE',
            'data' => 'DATA ABERTURA',
            'df' => 'DATA FECHAMENTO',
            'posto_nome' => 'NOME POSTO',
            'nome' => 'CONSUMIDOR/REVENDA',
            'nota_fiscal' => 'NF',
            'produto_descricao' => 'PRODUTO'
    ];


function retorna_botao_excluir_os($con, $os, $reparoNaFabrica = '', $posicao)
{   
    global $login_fabrica;
    
    $botao_excluir_os = "";

    $sql_os = "SELECT tbl_os.os,
                        tbl_os.excluida,
                      tbl_os.os_reincidente AS reincidencia,
                      tbl_os.status_checkpoint,
                      tbl_os_extra.status_os,
                      tbl_os.data_conserto,
                      tbl_os.admin ,
                      tbl_os.data_fechamento AS fechamento 
                 FROM tbl_os 
                 JOIN tbl_os_extra USING(os)
                 WHERE tbl_os.os={$os} 
                  AND tbl_os.fabrica={$login_fabrica}";
    $res_os = pg_query($con, $sql_os);
    if (pg_num_rows($res_os) > 0) {

        $os             = pg_fetch_result($res_os, 0, 'os');
        $excluida           = pg_fetch_result($res_os, 0, 'excluida');
        $reincidencia       = pg_fetch_result($res_os, 0, 'reincidencia');
        $status_checkpoint  = pg_fetch_result($res_os, 0, 'status_checkpoint');
        $status_os          = pg_fetch_result($res_os, 0, 'status_os');
        $data_conserto      = pg_fetch_result($res_os, 0, 'data_conserto');
        $admin              = pg_fetch_result($res_os, 0, 'admin');
        $fechamento         = pg_fetch_result($res_os, 0, 'fechamento');


        if (strlen($fechamento) == 0 && $status_checkpoint < 3) {

            if ((!in_array($status_os,[20,62,65,158,72,87,116,120,122,126,140,141,143])) || $reincidencia=='t') {        
                if (($excluida == "f" || strlen($excluida) == 0) && !$reparoNaFabrica) {

                    if (strlen($admin) == 0 && strlen($data_conserto) == 0) {
                        $botao_excluir_os = '
                                            <li>

                                                <a href="javascript: if(confirm(\''.traduz("deseja.realmente.excluir.a.os",$con).' (OS nº '.$os.')!\') == true) { excluirOs('.$os.','.$posicao.');}" title="'.traduz("Excluir O.S.").'"><i class="icon-remove"></i>  '.traduz("Excluir O.S.").'</a>


                                                </a>
                                            </li>
                                            <li class="divider"></li>
                                        ';

                    }   
                }
            }
        }
    }

    return $botao_excluir_os;

}
function retorna_botao_consertado_os($con, $os, $data_conserto, $finalizada, $posicao, $sua_os = '')
{
    global $cook_idioma, $login_fabrica; 

    if( !empty($sua_os) ){
        $numero_os = $sua_os;
    }else{
        $numero_os = $os;
    }         

    $botao_consertado =  "";

    if (strlen($data_conserto) == 0  && empty($finalizada)) {
        $botao_consertado =  '
                            <li>
                                <a href="javascript: if(confirm(\''.traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($numero_os)).'!\') == true) { consertadoOS('.$os.','.$posicao.','.$login_fabrica.');}" title="'.traduz("Consertado").'" ><i class="icon-check"></i> '.traduz("Consertado").'</a>
                            </li>
                            <li class="divider"></li>';

    }

    return $botao_consertado;
}                                
