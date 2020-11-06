<?php

if (!function_exists('dateFormat')) {

    /**
     * @name:           dateFormat
     * @author: Manuel López -2012
     * @param:  string  $datahora   data (e hora, se precisar) em formato numérico, com 2 dígitos para dia, mes e ano, ou 4 dígitos para o ano
     * @param:  string  $orgFmt     ordem de dia mês e ano na string $datahora. p.e.: "mdy"
     * @param:  string  $destFmt    default 'y-m-d' (ISO date). Formato de saída, incluíndo os separadores de data.
     *                              Também pode ser "long" ou "extenso" para data longa (Terça-feira, 24 de julho de 2012)
     *                              Também pode ser 'unix', para retornar o unix epoch/timestamp
     * @param   string  $idioma     optional    Idioma de saída para data longa, só funciona se existe a função 'traduz()'
     * @returns: mixed  string com a data no formato solicitado ou FALSE se a data não for válida
     *
     * TO-DO: validar hora, tratar hora, min,seg
     **/

    function dateFormat($datahora, $orgFmt, $destFmt='y-m-d', $idioma = null) {

        global $cook_idioma, $debug;

        $longFormat = false;

        list($data, $hora) = explode(' ', $datahora);

        $dataSoNums = preg_replace('/\D/', '', $data);

        $hasTime = ($hora != '');

        // Retorna FALSE se a data não tem 6 ou 8 dígitos (dd/mm/yy ou dd/mm/yyyy)
        if (!in_array(strlen($dataSoNums), array(6,8)))
            return false;

        switch (strtolower($destFmt)) {
            case 'long':
            case 'long format':
            case 'full':
            case 'fulldate':
            case 'extenso':
            case 'completa':
                $destFmt    = 'y-m-d';
                $longFormat = true;
                $idioma     = (is_null($idioma)) ? $cook_idioma : $idioma;
                break;

            case 'iso':
            case 'ISO':
            case 'postgres':
                $destFmt = 'y-m-d';
                break;

            case 'br':
            case 'BR':
                $destFmt = 'd/m/y';
                break;

            case 'euro':
                $destFmt = 'd-m-y';
                break;

            case 'usa':
            case 'USA':
                $destFmt = 'm/d/y';
                break;

            case 'deu':
                $destFmt = 'd.m.y';
                break;

            case 'timestamp':
                $destFmt = 'unix';
                break;

        }

        if (!$idioma) $idioma = 'pt-br';

        $longDateFmts = array(
            'pt-br' => '%s, %d de %s de %d',
            'es'    => '%s, %d de %s de %d',
            'en-us' => '%s, %3$s %2$d, %4$d',
            'de'    => '%s, %d. %s %d' // Formatação copiada do cabeçalho do Frankfurter Allgemeine
        );

        $regexs = array(
            'dmy' => '/(?P<d>\d{2})(?P<m>\d{2})(?P<y>\d{2,4})/',
            'mdy' => '/(?P<m>\d{2})(?P<d>\d{2})(?P<y>\d{2,4})/',
            'ymd' => '/(?P<y>\d{2,4})(?P<m>\d{2})(?P<d>\d{2})/',
        );

        $a = preg_match($regexs[$orgFmt], $dataSoNums, $atoms);

        if ($a === false)
            return false;

        if (isCLI and $debug)
            print_r($atoms);

        extract($atoms); // Cria as variáveis $d $m $y

        if (strlen($y) == 2)
            $y = ($y>60) ? "19$y" : "20$y";

        if (!checkdate($m, $d, $y))
            return false;

        if($destFmt == 'unix'):
            $ret = date('U', "$y-$m-$d $hora");
        else:

            $ret = str_replace('d', $d, $destFmt);
            $ret = str_replace('m', $m, $ret);
            $ret = str_replace('y', $y, $ret);

        endif;

        if ($longFormat) {

            $dias  = array("domingo", "segunda-feira", "terca-feira", "quarta-feira", "quinta-feira", "sexta-feira", "sabado");
            $meses = array(1=>'janeiro', 'fevereiro', 'marco', 'abril', 'maio','junho','julho','agosto', 'setembro', 'outubro','novembro','dezembro');

            if (function_exists('traduz')) {
                array_unshift($meses, 'void');
                $diaSemana = explode(' ', traduz($dias,  $con));
                $mes       = explode(' ', traduz($meses, $con));
            } else {
                $diaSemana = array_filter($dias,  'ucwords');
                $mes       = array_filter($meses, 'ucwords');
            }

            $weekDay = date("w", strtotime("$y-$m-$d"));

            $vardia = intval($d);
            $varmes = $mes[intval($m)];
            $varano = intval($y);
            $diaSem = $diaSemana[$weekDay];

            if (isCLI and $debug)
                echo "$diaSem ($weekDay), Dia $vardia, mês $varmes, Ano $varano ($idioma)\n";

            $ret = sprintf($longDateFmts[$idioma], $diaSem, $vardia, $varmes, $varano);
        }
        if ($hasTime)
            $ret = "$ret $hora"; // adiciona a hora, se é que veio
        return $ret;
    }
}

function formata_data($data) {
    return dateFormat($data, 'dmy', 'y-m-d');
}

function mostra_data($data) {
    $r = dateFormat($data, 'ymd', 'd/m/y');
    return ($r === false) ? null : $r;
}

function mostra_data_hora($data) {
    $r = dateFormat($data, 'ymd', 'd/m/y');
    return ($r === false) ? null : substr($r, 0, 16); //Tira os segundos
}

/* Deshabilitados até testar novamente!
function fnc_formata_data_pg($data) {
    $r = dateFormat($data, 'dmy', 'y-m-d');
    return ($r === false) ? 'null' :  "'$r'";
}

function fnc_formata_data_hora_pg($data) {
    $r = dateFormat($data, 'dmy', 'y-m-d');
    return ($r === false) ? 'null' : "'$r'";
}
 */
function fnc_formata_data_pg ($string) {

    $xdata = trim ($string);
    $xdata = str_replace ('/','',$xdata);
    $xdata = str_replace ('-','',$xdata);
    $xdata = str_replace ('.','',$xdata);

    if (strlen ($xdata) > 0) {

        if (strlen ($xdata) >= 6) {
            $dia = substr ($xdata,0,2);
            $mes = substr ($xdata,2,2);
            $ano = substr ($xdata,4,4);

            if (strpos ($xdata,"/") > 0) {
                list ($dia,$mes,$ano) = explode ("/",$xdata);
            }
            if (strpos ($xdata,"-") > 0) {
                list ($dia,$mes,$ano) = explode ("-",$xdata);
            }
            if (strpos ($xdata,".") > 0) {
                list ($dia,$mes,$ano) = explode (".",$xdata);
            }
        }else{
            $dia = substr ($xdata,0,2);
            $mes = substr ($xdata,2,2);
            $ano = substr ($xdata,4,4);
        }

        if (strlen($ano) == 2) {
            if ($ano > 50) {
                $ano = "19" . $ano;
            }else{
                $ano = "20" . $ano;
            }
        }
        if (strlen($ano) == 1) {
            $ano = $ano + 2000;
        }

        $mes = "00" . trim ($mes);
        $mes = substr ($mes, strlen ($mes)-2, strlen ($mes));

        $dia = "00" . trim ($dia);
        $dia = substr ($dia, strlen ($dia)-2, strlen ($dia));

        $xdata = "'". $ano . "-" . $mes . "-" . $dia ."'";

    }else{
        $xdata = "null";
    }

    return $xdata;

}

function fnc_formata_data_hora_pg ($data) {

    if (strlen ($data) == 0) return null;

    $xdata = $data.":00 ";
    $aux_ano  = substr ($xdata,6,4);
    $aux_mes  = substr ($xdata,3,2);
    $aux_dia  = substr ($xdata,0,2);
    $aux_hora = substr ($xdata,11,5).":00";

    return "'" . $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora . "'";
}

/*
======================================================================================================
Formata uma string para um valor aceito como moeda (REAIS)
- text                : string informada
Retornos:
- float8  : numero convertido
uso:
    echo fnc_limpa_moeda('01.234.567,89')."<br>";
    echo fnc_limpa_moeda('01234567,89')."<br>";
    echo fnc_limpa_moeda('01.234.567,00')."<br>";
    echo fnc_limpa_moeda('01234567.00')."<br>";
    echo fnc_limpa_moeda('01,234,567.89')."<br>";
    echo fnc_limpa_moeda('0123456789')."<br>";

======================================================================================================
*/

function fnc_limpa_moeda($text) {

    $text = trim($text) ;

    if (substr($text,1,1) == ',' OR substr($text,1,1) == '.')
        $text = '0'.$text;

    if (strlen($text) == 0){
        return false;
    }else{
        $m_pos = -1;
        while ($m_pos < strlen($text)){
            $m_pos ++;
            $m_letra = substr($text,$m_pos,1);
            if (strpos("\,\.", $m_letra) > 0){
                $m_letra = '*';
                $m_aux   = $m_pos;
            }
            if ($m_letra <> '*') $m_limpar = $m_limpar . $m_letra;
        }

        if ($m_aux > 0){
            $m_aux = strlen($text) - $m_aux;

            $m_limpar = fnc_so_numeros(substr($m_limpar,0,strlen($m_limpar)-$m_aux+1)) .".". fnc_so_numeros (substr($m_limpar,strlen($m_limpar)-$m_aux+1,$m_aux));
            $m_retorno = $m_limpar;
        }else{
            $m_limpar   = fnc_so_numeros($m_limpar) .'.00';
            $m_retorno  = $m_limpar;
        }
    }
    return $m_retorno;
}

/*-----------------------------------------------------------------------------
SoNumeros($string)
$string = para ser retirado somente os números
Pega uma string e retorna somente os numeros da mesma
-----------------------------------------------------------------------------*/
function fnc_so_numeros($string){
    $numeros = preg_replace("/[^0-9]/", "", $string);
    return trim($numeros);
}

// ###############################################################
// Funcao para calcular diferenca entre duas horas
// ###############################################################
function calcula_hora($hora_inicio, $hora_fim){
    // Explode
    $ehora_inicio = explode(":",$hora_inicio);
    $ehora_fim    = explode(":",$hora_fim);

    // Tranforma horas em minutos
    $mhora_inicio = ($ehora_inicio[0] * 60) + $ehora_inicio[1];
    $mhora_fim    = ($ehora_fim[0] * 60) + $ehora_fim[1];

    // Subtrai as horas
    $total_horas = ( $mhora_fim - $mhora_inicio );

    // Tranforma em horas
    $total_horas_div = $total_horas / 60;

    // Valor de horas inteiro
    $total_horas_int = intval($total_horas_div);

    // Resto da subtracao = pega minutos
    $total_horas_sub = $total_horas - ($total_horas_int * 60);
/*
    if($total_horas_sub<15){
        $total_horas_sub = 0;
    }elseif ($total_horas_sub>14 AND $total_horas_sub < 45){
        $total_horas_sub = 30;
    }else{
        $total_horas_sub = 0;
        $total_horas_int++;
    }
*/
    // Horas trabalhadas
    if ($total_horas_sub < 10) {
        $total_horas_sub = "0".$total_horas_sub;
    }
    $horas_trabalhadas = $total_horas_int.":".$total_horas_sub;

    // Retorna valor
    return $horas_trabalhadas;
}

function calcula_hora_simples($hora){
    // Explode
    $ehora = explode(":",$hora);

    $total_horas   = $ehora[0] * 60;    // Tranforma em minutos
    $total_minutos = $ehora[1];         // atribui minutos

    $total_horas_minutos = $total_horas + $total_minutos; // soma horas tranformadas em minutos e minutos

    $horas_trabalhadas = ( intval($total_horas_minutos) / 60); // transforma em decimais

    // Retorna valor
    return $horas_trabalhadas;
}

//-----------------------------------------------------
//Funcao: validaCNPJ($cnpj) HD 34921
//Sinopse: Verifica se o valor passado é um CNPJ válido
// Retorno: Booleano
//-----------------------------------------------------
    function checa_cnpj($cnpj)
    {
        if ((!is_numeric($cnpj)) or (strlen($cnpj) <> 14))
        {
            return 2;
        }
        else
        {
            $i = 0;
            while ($i < 14)
            {
            $cnpj_d[$i] = substr($cnpj,$i,1);
            $i++;
            }
            $dv_ori = $cnpj[12] . $cnpj[13];
            $soma1 = 0;
            $soma1 = $soma1 + ($cnpj[0] * 5);
            $soma1 = $soma1 + ($cnpj[1] * 4);
            $soma1 = $soma1 + ($cnpj[2] * 3);
            $soma1 = $soma1 + ($cnpj[3] * 2);
            $soma1 = $soma1 + ($cnpj[4] * 9);
            $soma1 = $soma1 + ($cnpj[5] * 8);
            $soma1 = $soma1 + ($cnpj[6] * 7);
            $soma1 = $soma1 + ($cnpj[7] * 6);
            $soma1 = $soma1 + ($cnpj[8] * 5);
            $soma1 = $soma1 + ($cnpj[9] * 4);
            $soma1 = $soma1 + ($cnpj[10] * 3);
            $soma1 = $soma1 + ($cnpj[11] * 2);
            $rest1 = $soma1 % 11;
            if ($rest1 < 2)
            {
                $dv1 = 0;
            }
            else
            {
                $dv1 = 11 - $rest1;
            }
            $soma2 = $soma2 + ($cnpj[0] * 6);
            $soma2 = $soma2 + ($cnpj[1] * 5);
            $soma2 = $soma2 + ($cnpj[2] * 4);
            $soma2 = $soma2 + ($cnpj[3] * 3);
            $soma2 = $soma2 + ($cnpj[4] * 2);
            $soma2 = $soma2 + ($cnpj[5] * 9);
            $soma2 = $soma2 + ($cnpj[6] * 8);
            $soma2 = $soma2 + ($cnpj[7] * 7);
            $soma2 = $soma2 + ($cnpj[8] * 6);
            $soma2 = $soma2 + ($cnpj[9] * 5);
            $soma2 = $soma2 + ($cnpj[10] * 4);
            $soma2 = $soma2 + ($cnpj[11] * 3);
            $soma2 = $soma2 + ($dv1 * 2);
            $rest2 = $soma2 % 11;
            if ($rest2 < 2)
            {
                $dv2 = 0;
            }
            else
            {
                $dv2 = 11 - $rest2;
            }
            $dv_calc = $dv1 . $dv2;
            if ($dv_ori == $dv_calc)
            {
                return 0;
            }
            else
            {
                return 1;
            }
        }
    }

if (!function_exists('iif')) {
    function iif($condition, $val_true, $val_false = "") {
        if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
        if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
        return ($condition) ? $val_true : $val_false;
    }
}

/**
 * @name    menu_item           imprime uma linha do menu das telas [sub]menu_*
 * @returns string              (print direto em tela)
 * @param   array  $item        dados da linha do menu
 * @param   string $bg_color    cor de fundo
 **/
function menu_item($item, $bg_color=null, $tipo_menu=null) {
    global $login_fabrica, $login_posto, $login_admin, $login_unico;

    if (!is_array($item)) return false;

    extract($item);

    if ($item['disabled']==true or $item['link']=='')
        return false;

    if (isset($fabrica)) {
        if (is_bool($fabrica) and $fabrica === false)
            return false;

        if (is_int($fabrica))
            if ($login_fabrica != $fabrica)
                return false;

        if (is_array($fabrica))
            if (!in_array($login_fabrica, $fabrica))
                return false;
    }

    if (isset($admin)) {
        if (is_bool($admin) and $admin === false)
            return false;

        if (is_int($admin))
            if ($login_admin != $admin)
                return false;

        if (is_array($admin))
            if (!in_array($login_admin, $admin))
                return false;
    }

    if (isset($posto)) {
        if (is_array($posto)) { // p.e.: 'posto' => array(4311, 6359),
            if (!in_array($login_posto, $posto)) return false;
        }
        if ($posto === false) // caso haja um ítem no array tal que: 'posto' => ($tipo_posto == 56), por exemplo...
            return false;

        if (is_int($posto)) { // por exemplo, 'posto' => 4311,
            if ($posto != $login_posto) return false;
        }
    }

    if (isset($fabrica_no)) {
        if (is_bool($fabrica_no) and $fabrica_no !== false)
            return false;

        if (is_int($fabrica_no))
            if ($login_fabrica == $fabrica_no)
                return false;

        if (is_array($fabrica_no))
            if (in_array($login_fabrica, $fabrica_no))
                return false;
    }

    if ($so_testes and $login_posto != 6359)
            return false;

    if (!is_null($bg_color))
        $bgc = " bgcolor='$bg_color'";

    if ($blank === true)
        $bgc .= ' target="_blank"';

    if (!is_null($tipo_menu) and isset($descr))
        $bgc .= " title='$descr'";

    if (isset($attr)) {
        $bgc .= (is_array($attr)) ? implode('', $attr) : $attr;
    }

    // E ainda, pode ter alguns outros parâmetros visuais...
    // Dá para adicionar conforme a necessidade
    // background vai como bgcolor no <TR></TR>
    if (isset($background))
        $bgc .= " bgcolor='$background'";

    if ($link == 'linha_de_separação') {
        if ($login_posto) {
            echo "<tr bgcolor='#D9E2EF'><td colspan='3'><img src='imagens/spacer.gif' height='3'></td></tr>";
        }
        return false;
    }

    // Exclusivo para o cabeçalho de seção do menu do Admin
    if ($tipo_menu == 'secao_admin') {
        if ($login_admin) {
            echo "<h3 class='ui-accordion-header'>$titulo</h3>";
        }

        if ($login_posto) {
            $colExpandImg = ($noexpand) ? '' : "            <img src='imagens/icon_collapse.png' class='colexpand' style='float:right'>";
            echo "<div class='cabecalho'>
                    <img src='imagens/corner_se_laranja.gif' style='float:left' />
                    <span style='text-align:center;height:1.4em;vertical-align:middle'>$titulo</span>
                    <img src='imagens/corner_sd_laranja.gif' style='float:right'>
                    $colExpandImg
             </div>";
        }
        return true;
    }

    //  Agora sim...
    //
    //  Se o ítem for de um submenu ou das abas, a tratativa é diferente

    if ($tipo_menu == 'tab') {

        // if (strpos('sair', $imagem) !== false)
        //  $bgc .= ' style="float:right"';

        $img = implode('_',
                    array_filter(
                        array($idioma, $imagem, $ativo)
                    )
                );
        if (!file_exists("imagens/aba/$img" . '.gif'))
            $img = implode('_', array_filter( array($imagem, $ativo)));

        return sprintf("<a href='%s' border='0'$bgc><img src='imagens/aba/%s.gif' border='0' /></a>", $link, $img);
    }

    if ($tipo_menu == 'sub') {
        return sprintf("<span class='submenu_telecontrol submenu_telecontrol_callcenter'><a href='%s'>%s</a></span>", $link, $titulo);
    }

    if ($tipo_menu == 'subAdm') {
        $link = ($link=='#' or $link=='void()') ? '"void()"' : '"window.location=' . "'$link'" . '"';
        return "<span onclick=$link $bgc>$titulo</span>";
    }

    $bcg .= $TRattrs . $TITLEattrs . $DESCattrs;

    if ($login_admin) {
        $codigoTD = "<td class='ui-content-codigo' style='width: 75px;' title='Código, link curto!'>{$codigo}</td>";
        $style["img"] = "class='ui-content-img'";
        $style["img_path"] = "imagens/icon/";
        $style["link"] = "class='ui-content-link'";
        $style["desc"] = "class='ui-content-desc'";
        unset($bgc);
        unset($TRattrs);
        unset($TITLEattrs);
        unset($LINKattrs);
    }

    if ($login_posto) {
        $style["img"] = "style='width: 25px;'";
        $style["img_path"] = "imagens/";
        $style["a_link"] = "class='menu'";
    }

    echo "<tr {$bgc} {$TRattrs}>
        <td {$style['img']}>
            <img src='{$style['img_path']}{$icone}' />
        </td>
        {$codigoTD}
        <td style='width: 250px;' $TITLEattrs {$style['link']}>";
        if (is_array($titulo) and is_array($link)) {
            $num_titulos = count($titulo);

            // Não é foreach, pq o índice controla se dá 'ENTER', e para pegar
            // o link correto para a descrição
            for ($t=0; $t < $num_titulos; $t++) {
                //echo ($t != 0)?"\n":'';
                echo "<a href='{$link[$t]}' {$style['a_link']}>{$titulo[$t]}</a>";
            }
        } else {
            echo "<a href='$link' $LINKattrs {$style['a_link']}>$titulo</a>";
        }

        $descricao = is_array($descr) ? implode('<br />', $descr) : $descr;
        echo "</td>
        <td {$style['desc']} $DESCattrs>
            $descricao
        </td>
    </tr>";

    return true;
}

/***
 * @name:   menuTC()
 * @param   $menu   array   Ítens do menu, para seerem repassado à função menu_item()
 * @param   $tabela array   Opcional. Parâmetros para a tabela, key com o atributo e o valor com o valor do atributo.
 *                          Os valores passados neste array sobrescrevem os valores padrão.
 * @param   $cor    string  Opcional. Cor para as linhas ímpares (default #fafafa).
 * @param   $cor2   string  Opcional. Cor para as linhas pares   (default #f0f0f0).
 * @returns int,false       Imprime o menu na saída, ou devolve false se houve erro. Se não há erro, retorna o nº de ítens da saída.
 * @seealso menu_item()
 **/
function menuTC($menu, $tbl_param=null, $cor = '#fafafa', $cor2 = '#f0f0f0') {
    global $login_posto, $login_admin;

    $tbl_params = array(
        'border'      => 0,
        'id'          => 'tbl_menu',
        'cellpadding' => 0,
        'cellspacing' => 0,
        'align'       => 'center'
    );
    if (!is_null($tbl_param)) {
        $tbl_params = array_merge($tbl_params, $tbl_param);
    }

    if ($login_admin) {
        $style["width"] = "100%";
        $style["class"] = "ui-accordion-content";
    }

    if ($login_posto) {
        $style["width"] = "700px";
    }

    // $table = "<table ";
    // foreach ($tbl_params as $attr=>$val) {
    //  $table .= " $attr='$val'";
    // }
    // echo $table . ">\n";

    echo "<div style='margin: 0 auto;' class='{$style['class']}'><table style='width: {$style['width']};'";
    foreach ($tbl_params as $attr=>$val) {
        $table .= " $attr='$val'";
    }
    echo "$table >";

    $c = 0;
    $bgcolor  = $cor;
    foreach ($menu as $menu_item) {

        if ($menu_item['disabled'] == true)
            continue; // Já nem repassa se está deshabilitado...

        // menu_item devolve true se imprimiu o ítem ou false se não... Só altera a cor se imprimiu
        if (menu_item($menu_item, $bgcolor)) {
            $c++;
            $bgcolor = ($bgcolor == $cor2) ? $cor : $cor2;
        }
    }
    //echo "</table>";
    echo "</table></div> <br />";
    return $c;
}

if (!function_exists('menuTCAdmin')) {
    function menuTCAdmin($menu, $tbl_param=null, $corA='#fafafa', $corA2='#f0f0f0') {

        global $login_admin, $login_fabrica;

        //pre_echo ("Procesando o menu para a fábrica <strong>$login_fabrica</strong>");

        foreach($menu as $secao=>$itens) {
            if (key($menu[$secao]) == 'secao') {

                if (isset($menu[$secao]['secao']['fabrica'])) {
                    $fabricas_sim = $menu[$secao]['secao']['fabrica'];
                    //echo "Mostrar a seção " . $menu[$secao]['secao']['titulo'] . " apenas para as fábricas " . implode(', ',$menu[$secao]['secao']['fabrica']);
                    //print_r($menu[$secao]['secao']['fabrica']);

                    if (is_bool($fabricas_sim))
                        if ($fabricas_sim === false)
                            continue;
                    $ver_fabrica = (is_array($fabricas_sim)) ? $fabricas_sim : array($fabricas_sim);
                    if (!in_array($login_fabrica, $ver_fabrica))
                        continue;
                }

                if (isset($menu[$secao]['secao']['fabrica_no'])) {
                    //echo "Não mostrar a seção " . $menu[$secao]['secao']['titulo'] . " para as fábricas " . implode(', ',$menu[$secao]['secao']['fabrica_no']);
                    //print_r($menu[$secao]['secao']['fabrica_no']);
                    if ($menu[$secao]['secao']['fabrica_no'] === true)
                        continue;
                    if (in_array($login_fabrica, $menu[$secao]['secao']['fabrica_no']))
                        continue;
                }

                echo "<div style='margin: 0 auto;' class='ui-accordion'>";
                menu_item($itens['secao'],
                          array(
                              'id'=>'tbl_menu_'.$menu[$secao]['secao']['titulo'],

                          ),
                          'secao_admin'
                      );

                unset($menu[$secao]['secao']);

            }
            //echo $menu[$secao]['titulo'];

            $this_section_itens = menuTC($menu[$secao], $tbl_param);
            echo "</div>";

            if ($this_section_itens > 0):
                echo ob_get_clean();
            endif;
            ob_end_clean();
        }
    }
}


function subMenu($itens, $idioma=null) {

    global $cook_idioma, $login_posto, $login_fabrica, $login_admin;

    if (!is_array($itens))
        return false;
    if (!count($itens))
        return false;

    if (is_null($idioma))
        $idioma = strtolower($cook_idioma);

    if (!in_array($idioma,  array('pt-br'))) // No array, colocar idiomas cuja tradução é "localizada" (não 'genérica')
        $img_suffix = substr($idioma, 0, 2); // Pega os dos primeiros caracteres: es, en, de, zn, etc.

    foreach($itens as $menu_item) {
        $menu_item[]['idioma'] = $img_suffix;
        $submenu[] = ($login_posto) ? menu_item($menu_item, null, 'sub') : menu_item($menu_item, null, 'subAdm');
    }

    foreach ($submenu as $key => $value) {
        if (strlen(trim($value)) == 0) {
            unset($submenu[$key]);
        }
    }

    if ($login_admin) {
        echo implode('', $submenu);
    } else if ($login_posto) {
        echo '<div class="sys_submenu"> | ' . implode(' | ', array_filter($submenu)) . ' | </div>';
    }

    return count(array_filter($submenu)); // Retorna os ítens utilizados
}

function tabsMenu($itens, $layout, $idioma=null) {

    global $cook_idioma;

    if (!is_array($itens))
        return false;
    if (!count($itens))
        return false;

    if (is_null($idioma))
        $idioma = strtolower($cook_idioma);

    if ($idioma != 'pt-br')
        $img_suffix = substr($idioma, 0, 2); // Pega os dos primeiros caracteres: es, en, de, zn, etc.

    $width = $itens['largura'];
    unset($itens['largura']);

    foreach($itens['abas'] as $nome => $menu_item) {
        $menu_item['idioma'] = $img_suffix;
        $menu_item['ativo']  = ($nome == $layout) ? 'ativo' : '';
        $tabs[] = menu_item($menu_item, null, 'tab');
    }

    echo "<div class='sys_tabs' style='width: $width'>" .
         implode('', array_filter($tabs)) .
         '</div>';
    return count(array_filter($tabs)); // Retorna os ítens utilizados
}

// Array com os nomes das imagens para os menus
$icone = array(
    "limpar"     => "limpar.png",
    "acesso"     => "acesso.png",
    "computador" => "computador.png",
    "cadastro"   => "cadastro.png",
    "consulta"   => "consulta.png",
    "relatorio"  => "relatorio.png",
    "bi"         => "bi.png",
    "email"      => "email.png",
    "upload"     => "upload.png",
    "anexo"      => "anexo.png",
    "print"      => "print.png",
    "chart"      => "chart.png",
    "usuario"    => "usuario.png"
);

if (!function_exists('calcula_frete')) {
    /**
     *
     * FUNÇÃO calcula_frete()
     *
     * @Parametros: $cep_origem, $cep_destino. $peso, $codigo_servico
     * @Retorno   : float;
     * HD 40324
     *
     **/
    function calcula_frete($cep_origem,$cep_destino,$peso,$codigo_servico = null ){

        $url = "www.correios.com.br";
        $ip = gethostbyname($url);
        $fp = fsockopen($ip, 80, $errno, $errstr, 10);

        if ($codigo_servico == null){
            $codigo_servico     = "40010"; #Código SEDEX
        }

        if (strlen($cep_origem)>0 AND strlen($cep_destino)>0 AND strlen($peso)>0){
            $saida  = "GET /encomendas/precos/calculo.cfm?servico=$codigo_servico&CepOrigem=$cep_origem&CepDestino=$cep_destino&Peso=$peso HTTP/1.1\r\n";
            $saida .= "Host: www.correios.com.br\r\n";
            $saida .= "Connection: Close\r\n\r\n";
            fwrite($fp, $saida);

            $resposta = "";
            while (!feof($fp)) {
                $resposta .= fgets($fp, 128);
            }
            fclose($fp);
            #echo htmlspecialchars ($resposta);

            $posicao = strpos ($resposta,"Tarifa=");
            $tarifa  = substr ($resposta,$posicao+7);
            $posicao = strpos ($tarifa,"&");
            $tarifa  = substr ($tarifa,0,$posicao);
            return $tarifa;
        }else{
            return null;
        }
    }

}


function mostraMarcaExtrato($extrato){
    global $con;
    global $login_fabrica;

    if($login_fabrica == 104){
        $campo_marca = " CASE WHEN tbl_marca.nome <> 'DWT' THEN 'OVD' ELSE 'DWT' END ";
    }else{
        $campo_marca = " tbl_marca.nome ";
    }

    $sqlM = "SELECT  $campo_marca as marca
                FROM   tbl_os
                JOIN   tbl_os_extra ON tbl_os.os = tbl_os_extra.os
                JOIN   tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
                JOIN   tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica
                WHERE  tbl_os_extra.extrato = $extrato ;";
    $resM = pg_query($con,$sqlM);

    if(pg_num_rows($resM) > 0){
        $marca = pg_result($resM,0,'marca');
        return $marca;
    }
}

function verificaCpfCnpj($cpf_cnpj){
    global $login_pais;
    $cpf_cnpj = trim($cpf_cnpj);
    if(!empty($cpf_cnpj) AND $login_pais == "BR"){
        if(strlen($cpf_cnpj) <> 11 and strlen($cpf_cnpj) <> 14){
            $erro = "Quantidade de dígitos do CPF/CNPJ inválida <br />";
        }

        if(!is_numeric($cpf_cnpj) AND empty($erro)){
            $erro = "CPF/CNPJ não deve conter letras <br />";
        }

        if(strlen($cpf_cnpj) == 14 AND empty($erro)){
            switch($cpf_cnpj){
                case "11111111111111":
                case "22222222222222":
                case "33333333333333":
                case "44444444444444":
                case "55555555555555":
                case "66666666666666":
                case "77777777777777":
                case "88888888888888":
                case "99999999999999":
                case "00000000000000":
                    $erro = "$cpf_cnpj não é um CNPJ válido <br />";
                break;

            }
        }

        if(strlen($cpf_cnpj) == 11 AND empty($erro)){
            switch($cpf_cnpj){
                case "11111111111":
                case "22222222222":
                case "33333333333":
                case "44444444444":
                case "55555555555":
                case "66666666666":
                case "77777777777":
                case "88888888888":
                case "99999999999":
                case "00000000000":
                    $erro = "$cpf_cnpj não é um CPF válido <br />";
                break;

            }
        }
    }

    return $erro;
}

function excelPostToJson ($post) {
    $json = array();

    $json["gerar_excel"] = true;

    foreach ($post as $key => $value) {
        if(!is_array($value)){
            $json[$key] = utf8_encode($value);
        }else{
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

if (!function_exists('moneyDB')){
    function moneyDB($money){

        $money = preg_replace("/[^0-9.,]/", "", $money);
        $money = str_replace(".", "", $money);
        $money = str_replace(",", ".", $money);

        if(empty($money))
                return "null";
        else
                return $money;
    }
}

if(!function_exists('priceFormat')){
    function priceFormat($price){
        global $login_fabrica;
        if(empty($price)){
            return 0;
        }else{
            return (in_array($login_fabrica, array(81,122,123,125,114,128)))?/*number_format($price,4,",",".")*/$price: number_format($price,2,",",".");
        }
    }
}

function getValue ($key) {
    /*
        $_RESULT  -> armazena o resultado dos inputs pegos atravez de select no banco
    */
    global $_RESULT;

    /*
        Pega o valor do campo
        Regra:
        se existe o $_RESULT do campo e não existe o $_POST o campo recebera o valor do $_RESULT
        se não irá receber o valor do $_POST
    */
    return (isset($_RESULT[$key]) && !isset($_POST[$key])) ? $_RESULT[$key] : $_POST[$key];
}

function montaForm ($inputs = array(), $hiddens = array()) {
    /*
        $msg_erro -> usada para deixar o campo com a class de erro caso ele exista no $msg_erro["campos"]
        $_RESULT  -> armazena o resultado dos inputs pegos atravez de select no banco
    */
    global $msg_erro, $_RESULT;

    /*
        Monta o elemento dos campos hiddens
    */
    if (count($hiddens) > 0) {
        /*  $hiddens = array("name") ou $hiddens = array("name" => array("value"=>"valor"))
            $name_id -> será o name e id do campo
                     -> Se $name_id for array, $key será o name e id e $name_id['value'] = valor
        */
        foreach ($hiddens as $key => $name_id) {
            /*
                Populate do campo
            */

            if(is_array($name_id)){

                echo "<input type='hidden' id='{$key}' name='{$key}' value='{$name_id["value"]}' />";

            }else{
                $value = getValue($name_id);
                echo "<input type='hidden' id='{$name_id}' name='{$name_id}' value='{$value}' />";

            }
        }
    }

    /*
        Monta o elemento dos campos
    */
    if (count($inputs) > 0) {
        /*
            $k -> contador para definir a key de cada campo dentro da array $html
        */
        $k = 0;

        /*
            $key    -> será o id e name do campo
            todo name de checkbox será array name[]

            $config -> array de configuração do campo, pode conter as seguintes configurações

            span      -> do espaço ocupado em tela pelo campo (1 a 12)
            label     -> texto que irá aparecer no elemento <label> do campo
            type      -> tipo do campo, input/(types do elemento input), select, option, checkbox, radio
            width     -> tamanho do input (1 a 12)
            inptc     -> tamanho do input que utiliza a class inptc que é uma class para tamanhos especificos (1 a 12) substitui o tamanho normal
            required  -> se for true irá colocar o * na frente do campo
            maxlength -> coloca o valor para o atributo maxlength
            readonly  -> se for true coloca o atributo readonly no campo
            class     -> classes adicionais para o campo deve ser uma string
            title     -> atributo title do elemento

            atributo especifico do select
            options -> array que armazena os options do select
            a key será o value do option e o valor será o texto do option
                    -> Caso o option precisar de parâmetros adicionais:
                        [key]['label'] = Label do campo
                        [key]['extra'] = array("nome_do_atributo" => valor)

            atributo especifico do checkbox
            checks -> array que armazena os checkboxs desta familia de checkbox
            a key será o value do checkbox e o valor será o label do checkbox

            atributo especifido do radio
            radios -> array que armazena os radios desta familia de radio
            a key será o value do radio e o valor será o label do radio

            icon-append -> adiciona um icone ou texto no formato de icone ao final do campo
            deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
            se for icon deve olhar o nome do icon na pagina de icones na doc

            icon-prepend -> adiciona um icone ou texto no formato de icone no inicio do campo
            deve ser um array, a key deve ser text ou icon, se for text o valor pode ser o que desejar
            se for icon deve olhar o nome do icon na pagina de icones na doc

            extra -> array de configuração de atributos extras
            a key sera o nome do atributo e o value o valor do atributo

            lupa -> monta html da lupa no campo, deve ser uma array com as seguintes configurações
                name      -> nome da lupa irá no rel do span do icone da lupa
                tipo      -> define pelo o que quer pesquisar (produto, posto, peça)
                parametro -> define pelo o que esta pequisando (referencia, nome, cpf)
                extra     -> parametros extras da lupa deve ser uma array
                             a key sera o nome do atributo extra e o value do valor do atributo extra
        */
        foreach ($inputs as $key => $config) {
            /*
                unset realizado em todas as variaveis usadadas dentro do foreach
                para evitar elses e possiveis problemas em relação a memoria
            */
            unset($elemento, $type, $class, $maxlength, $readonly, $value, $options, $title, $width, $span, $extra, $inptc, $array, $icon, $i, $lupa, $lupa_extra, $checks, $radios);

            /*
                $elemento -> tipo do elemento
                $type -> caso seja um input irá pegar o type do input
            */
            list($elemento, $type) = explode("/", $config["type"]);

            /*
                pega os atributos do campo
            */

            $class = $config["class"];
            $width = $config["width"];
            $span  = $config["span"];

            $key_id = (isset($config["id"])) ? $config["id"] : $key;

            if($elemento == "textarea"){
                $cols = $config["cols"];
                $rows = $config["rows"];
            }

            if ($config["title"]) {
                $title = "title='{$config["title"]}'";
            }

            if ($config["readonly"] == true) {
                $readonly = "readonly='true'";
            }

            if ($config["inptc"]) {
                $inptc = $config["inptc"];
            }

            if (count($config["extra"]) > 0) {
                foreach ($config["extra"] as $attr => $attrValue) {
                    $extra[] = "{$attr}='{$attrValue}'";
                }

                $extra = implode(" ", $extra);
            }

            if ($config["icon-append"]) {
                $icon["append"]["class"] = "input-append";

                switch (key($config["icon-append"])) {
                    case 'icon':
                        $i = "<i class='{$config["icon-append"]["icon"]}'></i>";
                        break;

                    case 'text':
                        $i = $config["icon-append"]["text"];
                        break;
                }

                $icon["append"]["span"]  = "<span class='add-on'>{$i}</span>";
            }

            if ($config["icon-prepend"]) {
                $icon["prepend"]["class"] = "input-prepend";

                switch (key($config["icon-prepend"])) {
                    case 'icon':
                        $i = "<i class='{$config["icon-prepend"]["icon"]}'></i>";
                        break;

                    case 'text':
                        $i = $config["icon-prepend"]["text"];
                        break;
                }

                $icon["prepend"]["span"]  = "<span class='add-on'>{$i}</span>";
            }

            if (is_array($config["lupa"]) && count($config["lupa"]) > 0) {
                $icon["append"]["class"] = "input-append";
                $icon["append"]["span"]  = "<span class='add-on' rel='{$config["lupa"]["name"]}' ><i class='icon-search'></i></span>";

                if (count($config["lupa"]["extra"]) > 0) {
                    foreach ($config["lupa"]["extra"] as $attr => $attrValue) {
                        $lupa_extra[] = "{$attr}='{$attrValue}'";
                    }

                    $lupa_extra = implode(" ", $lupa_extra);
                }

                $lupa = "<input type='hidden' name='lupa_config' tipo='{$config["lupa"]["tipo"]}' parametro='{$config["lupa"]["parametro"]}' {$lupa_extra} />";
            }

            if (is_array($config["popover"]) && count($config["popover"]) > 0) {
                $icon["append"]["class"] = "input-append";
                $icon["append"]["span"]  = "<span class='add-on'><i id='{$config["popover"]["id"]}'  rel='popover' data-placement='top' data-trigger='hover' data-delay='500' title='Informação' class='icon-question-sign' data-content='{$config["popover"]["msg"]}' class='icon-question-sign'></i></span>";
            }

            /*
                Cria o elemento
            */
            switch ($elemento) {
                case "input":
                    /*
                        Como maxlength é um atributo especifico do input ele so é pego se o elemento for input
                    */
                    if ($config["maxlength"]) {
                        $maxlength = "maxlength='{$config["maxlength"]}'";
                    }

                    /*
                        Populate
                    */
                    $value = getValue($key);

                    $elemento = "<input type='{$type}' id='{$key_id}' name='{$key}' class='span12 {$class}' {$maxlength} {$readonly} {$title} {$extra} value='{$value}' />";
                    break;

                case "textarea":
                    /*
                        Populate
                    */
                    $value = nl2br(getValue($key));

                    $elemento = "<textarea id='{$key_id}' cols='{$cols}' rows='{$rows}' name='{$key}' value='{$value}'> $value </textarea>";
                break;

                case "checkbox":
                    /*
                        Verifica se tem checkbox a ser criado nesta familia de check
                    */
                    if (count($config["checks"]) > 0) {
                        /*
                            Populate
                        */
                        $xvalue = getValue($key);

                        if (is_array($xvalue)) {
                            $array = true;
                        }

                        foreach ($config["checks"] as $value => $label) {
                            /*
                                Verifica se o checkbox recebera o atributo CHECKED
                            */
                            if ($array) {
                                $checked = (in_array($value, $xvalue)) ? "CHECKED" : "";
                            } else {
                                $checked = ($value == $xvalue) ? "CHECKED" : "";
                            }

                            /*
                                Cria o elemento checkbox
                            */
                            $checks[] = "<label class='checkbox' ><input type='checkbox' name='{$key}[]' value='{$value}' {$checked} {$readonly} {$title} {$extra} /> {$label}</label>";
                        }
                    }

                    if (count($checks) > 0) {
                        $elemento = implode("&nbsp;&nbsp;&nbsp;", $checks);
                    }
                    break;

                case "radio":
                    /*
                        Verifica se tem radio a ser criado nesta familia de radio
                    */
                    if (count($config["radios"]) > 0) {
                        /*
                            Populate
                        */
                        $xvalue = getValue($key);

                        foreach ($config["radios"] as $value => $label) {
                            /*
                                Verifica se o radio recebera o atributo CHECKED
                            */
                            $checked = ($value == $xvalue) ? "CHECKED" : "";

                            /*
                                Cria o elemento radio
                            */
                            $radios[] = "<label class='radio' ><input type='radio' name='{$key}' value='{$value}' {$checked} {$readonly} {$title} {$extra} /> {$label}</label>";
                        }
                    }

                    if (count($radios) > 0) {
                        $elemento = implode("&nbsp;&nbsp;&nbsp;", $radios);
                    }
                    break;

                case "select":
                    /*
                        Verifica se tem options a serem criados no select
                    */
                    if (count($config["options"]) > 0) {
                        /*
                            Populate
                        */
                        $xvalue = getValue($key);

                        foreach ($config["options"] as $value => $label) {
                            /*
                                Verifica se $label é array para colocar atributos extras
                            */
                            if(is_array($label)){
                                $option_extra = array();
                                /*
                                Verifica se o option recebera o atributo SELECTED
                                */
                                $selected = ($value == $xvalue) ? "SELECTED" : "";
                                if (count($label["extra"]) > 0) {
                                    foreach ($label["extra"] as $attr => $attrValue) {

                                        $option_extra[] = "{$attr}='{$attrValue}'";

                                    }

                                    $param_extra = implode(" ", $option_extra);
                                }
                                /*
                                    Cria o elemento option
                                */
                                $options[] = "<option value='{$value}' {$param_extra} {$selected} >{$label['label']}</label>";
                            }else{

                                /*
                                Verifica se o option recebera o atributo SELECTED
                                */
                                $selected = ($value == $xvalue) ? "SELECTED" : "";

                                /*
                                    Cria o elemento option
                                */
                                $options[] = "<option value='{$value}' {$selected} >{$label}</label>";
                            }

                        }
                    }

                    /*
                        Cria o elemento select
                    */
                    $elemento = "<select id='{$key_id}' name='{$key}' class='span12 {$class}' {$readonly} {$title} {$extra} ><option value=''>Selecione</option>".implode("", $options)."</select>";
                    break;
            }

            /*
                Armazena todas as configurações do elemento
                tamanho, icone, required, label etc
            */
            $html[$k]["label"] = "<label class='control-label' for='{$key}'>{$config['label']}</label>";
            $html[$k]["campo"] = $elemento;
            $html[$k]["width"] = $width;
            $html[$k]["span"]  = $span;

            if ($inptc) {
                $html[$k]["inptc"] = $inptc;
            }

            if ($config["required"] == true) {
                $html[$k]["required"] = "<h5 class='asteristico'>*</h5>";
            }

            /*
                se existe o id do campo dentro da array $msg_erro["campos"] quer armazena os campos que devem receber a
                class de erro seta a configuração error como true isso irá adicionar a class error no elemento
            */
            if (in_array($key, $msg_erro["campos"])) {
                $html[$k]["error"] = true;
            }

            if (isset($icon)) {
                $html[$k]["icon"] = $icon;
            }

            if (isset($lupa)) {
                $html[$k]["lupa"] = $lupa;
            }

            $k++;
        }

        /*
            $i -> contador utilizado para fazer a soma de tamanhos para controle de criação de linhas
            $x -> contador utilizado para contagem de elementos para controle do fechamento da ultima linha
        */
        $i = 0;
        $x = 1;

        /*
            Monta o form
        */
        foreach ($html as $elemento) {
            /*
                if {
                    Se $i for 0 cria a primeira linha do form ja com a margem
                    aqui o $i ja passa a ser 2(tamanho da margem) + tamanho do espaço do elemento
                }

                else if {
                    Se $i + tamanho do espaço do elemento a ser criado for maior que 10 fecha a linha atual ja com margem da
                    direita
                    e cria uma nova linha ja com a margem para este elemento
                    e o $i passa a ser $i 2(tamanho da margem) + tamanho do espaço do elemento
                }

                else {
                    $i + tamanho do espaço do elemento
                }
            */
            if ($i == 0) {
                echo "<div class='row-fluid'><div class='span2'></div>";
                $i = 2 +  $elemento["span"];
            } else if (($i + $elemento["span"]) > 10) {
                echo "<div class='span2'></div></div>";
                echo "<div class='row-fluid'><div class='span2'></div>";
                $i = 2 +  $elemento["span"];
            } else {
                $i = $i + $elemento["span"];
            }

            /*
                Se error for true adiciona a class error no elemento
            */
            $classError = ($elemento["error"] == true) ? "error" : "";

            /*
                Verifica se o tamanho do input irá usar o tamanho padrão do bootstrap ou o inptc
            */
            if ($elemento["inptc"]) {
                $width = "inptc{$elemento['inptc']}";
            } else {
                $width = "span{$elemento['width']}";
            }

            /*
                Elemento
            */
            echo "<div class='span{$elemento["span"]}'>
                <div class='control-group {$classError}'>
                    {$elemento['label']}
                    <div class='controls controls-row'>
                        <div class='{$width} {$elemento['icon']['prepend']['class']} {$elemento['icon']['append']['class']}'>
                            {$elemento['required']}
                            {$elemento['icon']['prepend']['span']}
                            {$elemento['campo']}
                            {$elemento['icon']['append']['span']}
                            {$elemento['lupa']}
                        </div>
                    </div>
                </div>
            </div>";

            /*
                Verifica se é o utlimo item da array para fazer o fechamento da linha
            */
            if ($x == count($html)) {
                echo "<div class='span2'></div></div>";
            } else {
                $x++;
            }
        }
    }
}

function array_map_recursive($callback, $array) {
    foreach ($array as $key => $value) {
        if (is_array($array[$key])) {
            $array[$key] = array_map_recursive($callback, $array[$key]);
        }
        else {
            $array[$key] = call_user_func($callback, $array[$key]);
        }
    }
    return $array;
}

function location ($url) {
    echo "<script> window.location = '{$url}'; </script>";
}

if(!function_exists('retira_acentos')){
    function retira_acentos( $texto ){
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
        return str_replace( $array1, $array2, $texto );
    }
}


 if (!function_exists('moneyDB')){
   function moneyDB($money){

           $money = preg_replace("/[^0-9.,]/", "", $money);
           $money = str_replace(".", "", $money);
           $money = str_replace(",", ".", $money);

           if(empty($money))
                return "null";
           else
                return $money;
   }
}

#   Declaração de variáveis usadas normalmente
#   Dias e Meses do ano. Os dias começam com o '0' em Domingo, para ficar
#   igual o padrão do pSQL e PHP, fica mais fácil de mexer
$Dias = array(
    'pt-br' => array(
                0 => "Domingo",     "Segunda-feira","Terça-feira",
                     "Quarta-feira","Quinta-feira", "Sexta-feira",
                     "Sábado",      "Domingo"),
    'es'    => array(
                0 => "Domingo", "Lunes",    "Martes", "Miércoles",
                     "Jueves",  "Viernes",  "Sábado" ),
    'en-us' => array(
                0 => "Sunday",  "Monday", "Tuesday", "Wednesday",
                     "Thursday","Friday", "Saturday")
);

$meses_idioma = array(
    'pt-br' => array(1 => "Janeiro", "Fevereiro","Março",   "Abril",
                          "Maio",    "Junho",    "Julho",   "Agosto",
                          "Setembro","Outubro",  "Novembro","Dezembro"),
    'es'    => array(1 => "Enero",    "Febrero","Marzo",    "Abril",
                          "Mayo",     "Junio",  "Julio",    "Agosto",
                          "Septiembre", "Octubre",  "Noviembre","Diciembre"),
    'en-us' => array(1 => "January",    "February", "March",    "April",
                          "May",        "June",     "July",     "August",
                          "September",  "October",  "November", "December")
);

$estados_BR = array("AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO",
                    "MA", "MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR",
                    "RJ", "RN", "RO", "RR", "RS", "SC", "SE", "SP", "TO");

$array_estados = array("AC" => "Acre",          "AL" => "Alagoas",          "AM" => "Amazonas",
                 "AP" => "Amapá",           "BA" => "Bahia",            "CE" => "Ceará",
                 "DF" => "Distrito Federal","ES" => "Espírito Santo",   "GO" => "Goiás",
                 "MA" => "Maranhão",        "MG" => "Minas Gerais",     "MS" => "Mato Grosso do Sul",
                 "MT" => "Mato Grosso",     "PA" => "Pará",             "PB" => "Paraíba",
                 "PE" => "Pernambuco",      "PI" => "Piauí",            "PR" => "Paraná",
                 "RJ" => "Rio de Janeiro",  "RN" => "Rio Grande do Norte","RO"=>"Rondônia",
                 "RR" => "Roraima",         "RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
                 "SE" => "Sergipe",         "SP" => "São Paulo",        "TO" => "Tocantins");


function createThumb ($file) {
    $basename = basename($file);
    $basename = preg_replace("/\?+.*/", "", $basename);
    $type     = strtolower(preg_replace("/.+\./", "", $basename));

    list($width, $height) = getimagesize($file);

    $widthNew  = 100;
    $heightNew = 90;

    if ($width < $widthNew) {
        $widthNew = $width;
    }

    if ($height < $heightNew) {
        $heightNew = $height;
    }

    $thumb = imagecreatetruecolor($widthNew, $heightNew);

    switch ($type) {
        case "jpeg":
        case "jpg":
            $source = imagecreatefromjpeg($file);
            break;

        case "png":
            $source = imagecreatefrompng($file);
            break;

        case "gif":
            $source = imagecreatefromgif($file);
            break;
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $widthNew, $heightNew, $width, $height);

    if (file_exists("../osImagem/thumb/{$basename}")) {
        system("rm -rf ../osImagem/thumb/{$basename}");
    }

    switch ($type) {
        case "jpeg":
        case "jpg":
            $fileMini = imagejpeg($thumb, "../osImagem/thumb/{$basename}");
            break;

        case "png":
            $fileMini = imagepng($thumb, "../osImagem/thumb/{$basename}");
            break;

        case "gif":
            $fileMini = imagegif($thumb, "../osImagem/thumb/{$basename}");
            break;
    }

    return "../osImagem/thumb/{$basename}";
}
