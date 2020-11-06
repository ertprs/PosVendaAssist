<?php
// Vim: set et ts=4 sw=0:
/**
 * @name   Menu Helper
 * @author Manuel López <manuel.lopez@telecontrol.com.br>
 * @desc   Funções, variáveis comuns, constantes, etc. para o menu do posto
 */

define ('MENU_DIR', __DIR__ . DIRECTORY_SEPARATOR);

/**
 * Funções para manipular o array do menu e criar o HTML
 */
include_once MENU_DIR . 'regras_posto.php';
include_once APP_DIR  . 'funcoes.php';
include_once /*APP_DIR  .*/ 'helpdesk/mlg_funciones.php';

class MenuPosto
{

    /**
     * template do menu para diferentes frameworks
     */
    static private
        $templates = array(
            'FMC' => array(
                'headers' => '
        <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:400,300,300italic,400italic,500italic,700,700italic,500">
        <link rel="stylesheet" type="text/css" href="fmc/css/styles.css">
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
                ',
                'navbar' => array(
                    'bar'  => "<div class='header'><div class='nav'><div class='main2'><ul>%s</ul></div></div></div>\n",
                    'item'     => "<li><h1><a %3\$s href='%1\$s'>%2\$s</a></h1></li>",
                    'header'   => "<li><h1 class='header' '%1\$s'>%2\$s</h1></li>",
                    'menu'     => "<li><h1><a %3\$s>%1\$s</a></h1><ul>%2\$s</ul></li>",
                    'menuitem' => "<li><h1><a %3\$s href='%1\$s'>%2\$s</a></h1></li>",
                ),
                'menu' => array(
                    'section' => "<div class='menu2'><div class='main'><ul>%s</ul></div></div>",
                    'item' => "<li><a href='%2\$s'><h2>%1\$s %3\$s</h2><span>%4\$s</span></a></li>",
                ),
                'cabecalho' => "
                 <div class='cabecalho oculta_loja'>
                     <div class='simple'>
                         <h2 class='titulo'>%s</h2>
                         <div class='main2 black'>
                             <div class='table'>
                                 %s
                                 <div>
                                     <div class='right nome'>%s</div>
                                     <div class='right dthr'>%s</div>
                                 </div>
                                    <div class='right dthr'>%s</div>
                             </div>
                         </div>
                     </div>
                 </div>\n",
                'cardsMenu' => '
                    <div class="menu p-tb">
                        <div class="main2">
                            <ul class="cards">%s
                            </ul>
                        </div>
                    </div>',
                 'cardMenuItem' => '
                                <li class="card">
                                    <a href=%1$s>
                                        %2$s 
                                        <div class="title">%3$s</div>
                                    </a>%4$s
                                </li>',
                'alerts' => '
                    <div class="alerts">
                        <div class="alert %2$s margin-top">%1$s</div>
                    </div>',
                'status_icons' => array(
                    'danger' => 'exclamation-circle',
                    'warning' => 'exclamation-triangle',
                    'info' => 'info-circle',
                    'default' => '',
                ),
                'icon' => "<i class='fa fa-fw fa-%s'></i>",
            ),
            'BS2' => array(
                'alerts' => '<div class="alert alert-%2$s">%1$s</div>',
                'navbar' => array(
                    'bar' => "<div class='navbar'>\n<div class='navbar-inner'>\n<ul class='nav'>%s</ul>\n</div>\n</div>\n",
                    'item' => '<li><a href="%1$s" %3$s>%2$s</a></li>',
                    'menu' => '<li class="dropdown"><a class="dropdown-toggle" href="#" data-toggle="dropdown" %3$s>%1$s</a><ul class="dropdown-menu">%2$s</ul></li>',
                    'menuitem' => '<li><a href="%1$s" %3$s>%2$s</a></li>',
                ),
                'menu' => array(
                    'section' => '<ul class="nav nav-tabs nav-stacked">%s</ul>',
                    'item' => "<li><a href='%2\$s'><span class='span1 text-primary'>%1\$s</span><span class='link span7'>%4\$s</span><span class='span4'>%3\$s</span></a></li>\n",
                ),
            ),
            'BS3' => array(
                'headers' => '
        <link rel="stylesheet" href="externos/bootstrap3/css/bootstrap.min.css">
        <script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
        <script src="externos/bootstrap3/js/bootstrap.min.js"></script>
        <style>
            body {margin-top: 60px;}
            .navbar-nav {
              float: initial;
              text-align: center;
            }
            .navbar-nav>li {
              float: initial;
            }
            .nav>li {
              display: inline-block;
            }
        </style>
                ',
                'navbar' => array(
                    'bar'  => "<nav class='navbar navbar-default navbar-fixed-top'>\n<ul class='nav navbar-nav'>\n%s</ul>\n</nav>\n",
                    'navmenu' => "<li class='menu-item'><div class='link'>%s</div></li>\n",
                    'item' => "<li class='menu-item'><a href='%1\$s' %3\$s>%2\$s</a></li>",
                    'menu' => "  <li class='menu-item dropdown'>\n<a href='#' %3\$s class='dropdown-toggle' data-toggle='dropdown' role='button' aria-haspopup='true' aria-expanded='false'>%1\$s</a>\n<ul class='dropdown-menu'>%2\$s</ul>",
                    'menuitem' => "<li class='menu-item dropdown-submenu'><a href='%1\$s' %3\$s>%2\$s</a></li>",
                    'header' => "<li class='dropdown-header' %s>%s</li>\n",
                    'sep' => "<li class='divider' role='separator'></li>\n",
                ),
                'menu' => array(
                    'section' => '<div class="row col-md-8 col-md-offset-2"><table class="table table-hover"><caption>%2$s</caption><tbody>%1$s</tbody></table></div>',
                    'title' => '<caption>%s</caption>',
                    'item' => '<tr><td><a href="%2$s"><span class="col-md-1">%1$s</span><span class="col-md-4">%3$s</span><span class="col-md-7">%4$s</span></a></td></tr>',
                    // Menu usando Panel e lista agrupada
                    // 'section' => "<div class='panel panel-default'>\n%2\$s<div class='panel-body'><ul class='list-group'>\n%1\$s\n</ul>\n</div>\n</div>",
                    // 'title'   => "<div class='panel-heading'>%s</div>\n",
                    // 'item' => "<a href='%2\$s' class='list-group-item'><span class='col-md-1 text-primary'>%1\$s</span><span class='col-md-4'>%3\$s</span>%4\$s</a>\n",
                ),
                'icon' => "<i class='fa fa-fw fa-%s'></i>",
                'caret' => "&nbsp;<span class='caret'></span>",
            ),
        ),
        $imagensLogo,
        $iconList = array(
            'marca25.gif' => 'plus',
            'tela25.gif'  => 'search',
        ),
        $logo     = null; 

    private
        $HTML  = '',       // Irá conter o HTML final, seja navbar, cabeçalho ou menu.
        $FW    = 'FMC',    // Framework a ser usado para renderizar o menu.
        $LU    = false,    // Se deve mostrar o ítem do Login Único
        $menu  = null,     // array do menu ou navbar. 
        $site  = null,     // sites dos fabricantes
        $alerts = array(   // Lista de alertas a serem mostradas agrupadas por tipo
            'danger'  => array(),
            'warning' => array(),
            'info'    => array(),
            'default' => array(),
        );

    function __construct(array $menu, $fw='FMC') {
        $this->menu = $menu;
        $this->HTML = null;
        $this->FW   = $fw;
    }

    public function __get($var) {
        switch (strtolower($var)) {
            case 'html':
                return $this->HTML;
            break;
            case 'fw':
            case 'css':
            case 'framework':
                return $this->FW;
            break;
            case 'logo':
                return self::$logo;
            break;
            case 'site':
            case 'logourl':
                 return $this->site = is_url($value) ? $value : null;
            break;
            case 'data':
            case 'menudata':
                return $this->menu;
            break;
            case 'headers':
                return self::$templates[$this->FW]['headers'];
            break;
        }
        return null;
    }

    public function __set($var, $value) {
        switch (strtolower($var)) {
            case 'fw':
            case 'css':
            case 'framework':
                $this->setFw($value);
            break;

            case 'menu':
            case 'menudata':
                if (is_array($value)) {
                    $this->menu = $value;
                }
            break;

            case 'site':
            case 'logourl':
                $this->site = is_url($value) ? $value : null;
            break;

            case 'logo':
            case 'logotipo':
                if (strpos($value, '/') === false and is_readable($logo)) {
                    self::$logo = $value;
                    return self::$logo;
                }
                return self::$logo = $value;
            break;

            default:
                return null;
            break;
        }
        return $value;
    }

    public function __toString() {
        if (!strlen($this->HTML)) {
            if (!is_array($this->menu)) {
                return '';
            }

            if (array_key_exists('HOME', $this->menu))
                $this->navBar();
            else
                $this->menu();
        }
        return $this->HTML;
    }

    /**
     * @name    setFw()
     * @param   String $fw   Ativa o framework informado, se existe
     * @return  Object self
     */
    public function setFw($fw = 'FMC') {
        $fw = strtoupper($fw);
        if (in_array($fw, array('FMC', 'BS2', 'BS3', 'TC')))
            $this->FW = $fw;
        return $this;
    }

    public function setMenuData(array $menuData) {
        $this->menu = $menuData;
        return $this;
    }

    /**
     * @name   navBar()
     * @param  Optional String  Ambiente (os, tecnica, pedidos...) para informar na navbar
     * @param  Optional Array   Array com os dados para montar o navBar
     * @return Object self
     * @desc
     * Este método cria o HTML do menu, que pode depois ser usado ao "imprimir"
     * o objeto, ou pegando o atributo 'HTML'.
     */
    public function navBar($modulo=null, $navBar=null) {
        // a princípio, o que foi entregue quando instanciou objeto,
        // mas poderia receber um outro array e trabalhar com ele.
        $navBar = $navBar ? : $this->menu;
        $templ  = self::$templates[$this->FW];


        foreach ($navBar as $navKey => $navItem) {
            if ($navItem['hidden'] == true) {
                continue;
            }

            // pre_echo ($navItem, "Processando o navBar... Menu $navKey");
            $attrs  = $navItem['attr'] ? self::getItemAttrs($navItem['attr'])    : '';
            $ICONE  = self::getValorFabrica($navItem['icon']);
            $icone  = $ICONE ? self::icon($ICONE, $this->FW) : '';

            if ($navItem['link']) {
                $attrs .= " href='{$navItem['link']}'";
            }

            $titulo = $icone . $navItem['name'] ? : $navKey;
            $attrs .= in_array($modulo, $navItem['layouts']) ? ' class="active"' : '';

            if (array_key_exists('submenu', $navItem) and is_array($navItem['submenu'])) {
                $navItemMenu = $this->navSubmenu($navItem['submenu']);
                $tpl         = $templ['navbar']['menu'];
                $titulo     .= $templ['caret'];
                $itens      .= sprintf($tpl, $titulo, $navItemMenu, $attrs);
                continue;
            }

            $link   = $navItem['link'];
            $tpl    = strlen($link) ? $templ['navbar']['item'] : $templ['navbar']['navmenu'];
            $itens .= sprintf($tpl, $link, $titulo, $attrs);
        }
        $this->HTML = sprintf($templ['navbar']['bar'], $itens);
        return $this;
    }

    /**
     * @name:   menu()
     * @param   $menu   array   Ítens do menu, para serem repassados à função menu_item()
     * @return  Self    Object  Object self. O menu está no atributo 'HTML'.
     */
    public function menu($menuData) {
        $template = self::$templates[$this->FW]['menu'];
        $menu =is_array($menuData) ? $menuData : $this->menu;

        if ($menu['title'] and $template['cabecalho']) {
            $titulo = $menu['title'];
            unset($menu['title']);
        }

        foreach ($menu as $item) {
            if (self::showMenuItem($item) === false) {
                continue;
            }
            if ($item['link'] == 'linha_de_separação') {
                continue;
            }
            if ($item['link'] == 'linha_de_separacao') {
                continue;
            }

            $icone = $this->icon(self::novoIcone($item['icone']), $this->FW);

            $link = is_array($item['link'])
                ? $this->getValorFabrica($item['link'])
                : $item['link'];

            $titulo = is_array($item['titulo'])
                ? $this->getValorFabrica($item['titulo'])
                : $item['titulo'];

            $descr = is_array($item['descr'])
                ? $this->getValorFabrica($item['descr'])
                : $item['descr'];

            $html .= sprintf($template['item'], $icone, $link, $titulo, $descr);
        }

        $this->HTML = sprintf($template['cabecalho'], $titulo ) .
            sprintf($template['section'], $html, $titulo);
        return $this;
    }

    /**
     * @name    cabecalho()
     * @param   string  $logo   Imagem do logotipo do fabricante. FabricaID, Path ou URL.
     *
     * A logo a princípio é deduzida pelo $login_fabrica. Está como parêmetro opcional
     * por se por algum motivo fosse necessário sobrescrever o default.
     */
    public function cabecalho($titulo, $banner=null, $admin_master=null, $link_logo_tdocs=null) {
        global $login_posto;
        $headerTpl = self::$templates[$this->FW]['cabecalho'];

        self::init();

        if ($admin_master == 't'){
            $subir_logo_empresa = '
                <br/>
                <a href="javascript: void(0)" class="btn" onclick="subir_logo_empresa('.$login_posto.')"><i class="fa fa-paperclip fa-lg"></i>&nbsp;Subir logo da empresa</a>
                <br/>
                <a href="'.$link_logo_tdocs.'" id="visualizar_logo"><span style="border-radius: 3px; padding: 1px 17px; color:#ffffff; background-color: ">Visualizar logomarca</span></a>
                <script> setupZoom(); </script>
            ';
        }

        $pais      = strtolower($GLOBALS['login_pais']);
        $data_hora = substr(is_date('agora', 'iso', 'EUR'), 0, 16);

        $nomePosto[] = implode(
            ' &ndash; ', array_filter(
                array(
                    $GLOBALS['login_codigo_posto'],
                    change_case(Convert($GLOBALS['login_nome'], 'iso-8859-1'), 'u'),
                    $GLOBALS['posto_nome']
                )
            )
        );

        $logo   = $GLOBALS['login_fabrica'] ?  : 10;

        if ($titulo == 'menu.inicial'  and $logo != 87) {
            $banner = self::getImageAttributes(self::$imagensLogo[10], 180, 120, 'html');
        }
	if ($admin_master != 't') {
		$subir_logo_empresa = null;
	}
        if ($GLOBALS['login_fabrica'] == 1) {
            $logoHtml  = '<div>' . self::getLogoFabrica($logo)['html'] . '</div>';            
            $video = verificaVideoTela(PROGRAM_NAME);
        }elseif ($GLOBALS['login_fabrica'] == 80) { /*HD - 6164934*/
            $logoHtml  = '<div>' . self::getLogoFabrica($logo, 180)['html'] . '</div>';
        } else {
            $logoHtml  = '<div>'.self::getLogoFabrica($logo, 180, 120)['html'].$subir_logo_empresa.'</div>';
        }

        if($GLOBALS['login_fabrica'] == 11){
            $logoHtml = "<img id='logo_fabrica' src='logos/logo_lenox_new.jpg' alt='Lenoxx' style='height:60px; width:260px' >";
        }



        if (!is_null($this->site)) {
            $logoHtml = "<a href='{$this->site}' target='_blank'>$logoHtml</a>";
        }

        if ($banner)
            $logoHtml .= "<div style='text-align:center'>$banner</div>";

        if ($lu = $GLOBALS['login_unico']) {
            $nomePosto[] .= $GLOBALS['login_unico_nome'];
        }

        if($GLOBALS['login_fabrica'] == 87) {
            $nomePosto[] = "{$GLOBALS['login_contato_cidade']} - {$GLOBALS['login_contato_estado']}";
        }

        // Quando tivermos avatar do Login Único, acrescentar no lugar da bandeira,
        // Ocupando as três linhas (Posto, LU e data).
        // Retirada bandeira à pedido do Túlio
        if (false and strlen($pais)  and $pais != 'br') {
            $nomePosto[] = self::getCountryFlag($pais);
        }

        if (strpos($titulo, '.')) $titulo = traduz($titulo);

        $this->HTML = sprintf(
            $headerTpl,
            $titulo, $logoHtml,
            implode('<br>', $nomePosto),
            $data_hora, $video 
        );
        return $this;
    }

    /**
     * Devolve o HTML do ícone. Se for o "ícone" antigo, tenta 'converter' ele antes.
     */
    public static function icon($icname, $fw='FMC') {
        if (strpos($icname, '/') and preg_match('/\.(ico|jpe?g|png|gif)$/', $icname)) {
            return "<img src='$icname' style='height:24px' />";
        }

        $icname = $fw == 'TC' ? $icname : self::novoIcone($icname);
        return sprintf(self::$templates[$fw]['icon'], $icname);
    }

    /**
     * @method addAlert()
     * @author Manuel López <manuel.lopez@telecontrol.com.br>
     * @param  String $msg     Required    Mensagem a ser mostrada
     * @param  String $type    Optional    Tipo de alert (info, warning...)
     * @param  String $icon    Optional    nome da classe FontAwesome para o ícone
     * @return Object self
     */
    public function addAlert($msg, $type='', $icon='') {
        global $traducao;
        $icon = $icon ? : 'no-icon';
        $type = $type ? : 'default';

        if (strlen($msg)) {
            if (array_key_exists($msg, $traducao['pt-br']))
                $msg = traduz($msg);
            $this->alerts[$type][$icon][] = $msg;
        }
        return $this;
    }

    /**
     * @method getAlertsHtml()
     * @author Manuel López <manuel.lopez@telecontrol.com.br>
     * @param  String $type    Optional    Tipo de alert (info, warning...)
     * @return String          HTML com os alerts escolhidos
     *
     * Se $type is null, devolve todos os erros adicionados com addAlert()
     * Se $type é 'danger', 'warning', 'info' ou 'default', devolve apenas
     * esse tipo de alert.
     * Por enquanto não vai filtrar por ícone, acredito que seja desnecessário.
     */
    public function getAlertsHtml($type=null) {
        $html = '';
        foreach ($this-alerts as $alertType => $alerts) {
            if (!count($alerts) or (!is_null($type) and $type !== $alertType)) {
                continue;
            }

            foreach ($alerts as $icone => $msg) {
                $icon  = ($icone == 'no-icon') ? '' : $icone;
                $html .= $this->alert($msg, $alertType, $icon);

            }
        }
        return $html;
    }

    /**
     * @method alert()
     * @author Manuel López manuel.lopez@telecontrol.com.br
     * @param  String $msg     Required    Mensagem a ser mostrada
     * @param  String $type    Optional    Tipo de alert (info, warning...)
     * @param  String $icon    Optional    nome da classe FontAwesome para o ícone
     *
     * @return String   HTML do 'alert'
     */
     public function alert($msg, $type='', $icon='') {
        $icon = $icon ? : self::$templates[$this->FW]['status_icons'][$type];

        if (is_array($msg) and !is_int(key($msg))) {
            $ret = '';
            foreach ($msg as $type => $messages) {
                $message = implode('<br />', (array)$messages);
                $ico  = $icon ? self::icon($icon) . '&nbsp;' : '';
                $ret .= sprintf(self::$templates[$this->FW]['alerts'], $ico.$message, $type);
            }
            return $ret;
        }

        $message = is_string($msg)
            ? $msg
            : implode('<br />', array_filter((Array)$msg));

        $ico  = $icon ? self::icon($icon) . '&nbsp;' : '';

        if (strlen($message))
            return sprintf(self::$templates[$this->FW]['alerts'], $ico.$message, $type);

        return '';
    }

    private function navSubmenu($subMenuData, $menuTitle='') {
        $html     = array();

        $template = self::$templates[$this->FW]['navbar'];
        $template['icon'] = self::$templates[$this->FW]['icon'];
        extract($template, EXTR_PREFIX_ALL, 'tpl');

        foreach ($subMenuData as $name => $item) {
            if ($item['hidden'] === true)
                continue;

            if (in_array($item['name'], array('sep', 'separator', 'divider'))) {
                $html[] = $tpl_sep;
                continue;
            }

            if (array_key_exists('header', $item)) {
                $html[] = sprintf($tpl_header, $attrs, $item['header']);
                continue;
            }

            if ($item['hint']) {
                $item['attr']['title'][] = $item['hint'];
            }
            if ($item['disabled'] === true or basename($_SERVER['PHP_SELF']) == basename($link)) {
                $item['attr']['disabled'][] = "disabled";
            }

            $icon  = $item['icon'] ? sprintf($tpl_icon, self::getValorFabrica($item['icon'])) : '';
            $text  = $item['name'] ? self::getValorFabrica($item['name']) : traduz($name);
            $link  = $item['link'] ? self::getValorFabrica($item['link']) : '#';

            if (array_key_exists('attr', $item))
                $attr = MenuPosto::getItemAttrs($item['attr']);

            $html[] = sprintf($tpl_menuitem, $link, $icon.$text, $attr);
        }
        return implode("\t\t\n", $html);
    }

    public function cardsMenu(array $cards, $w=null, $h=null) {
        $HTML = '';
        $tpl  = self::$templates[$this->FW]['cardsMenu'];
        $tpli = self::$templates[$this->FW]['cardMenuItem'];

        foreach ($cards as $cardItem) {
            if ($cardItem['hidden'])
                continue;

            $link  = is_array($cardItem['links']) ? self::getValorFabrica($cardItem['links']) : $cardItem['links'];
            $title = is_array($cardItem['title']) ? self::getValorFabrica($cardItem['title']) : $cardItem['title'];
            $icon  = is_array($cardItem['icon'])  ? self::getValorFabrica($cardItem['icon'])  : $cardItem['icon'];
            $attr  = array_key_exists('default', $cardItem['attr'])
                ? self::getValorFabrica($cardItem['attr'])
                : $cardItem['attr'];
            $link  = "\"$link\"";
            $temAlert = '';
            $temVagas = "";

            if (isset($cardItem['alert']) && $cardItem['alert']) {
                $countAlert = self::getValorFabrica($cardItem['tem_alert']);
                $temAlert = '<div class="tem_alert">'.$countAlert.'</div>';
            }

            if (isset($cardItem['vagas']) && $cardItem['vagas']) {
                $temVagas = self::getValorFabrica($cardItem['tem_vagas']);
                var_dump();
                if (!empty($temVagas)) {
                    $temAlert = '<p class="tem_vagas">'.$temVagas.'</p>';
                }
            }

            if (is_array($attr)) {
                $link .= ' ' . self::getItemAttrs($attr);

            }

            if (strpos($icon, '<') === false) {
                // Normalmente $icon é uma TAG <img/> pronta,
                // mas pode receber a imagem apenas, então TEM
                // que receber também a largura ou altura máxima
                $icon  = self::getImageAttributes($icon, $w, $h, 'html');
            }

            $HTML .= sprintf($tpli, $link, $icon, traduz($title), $temAlert);
        }

        if (strlen($HTML)) {
            $this->HTML = sprintf($tpl, $HTML);
        }
        return $this;
    }

    public static function getLogoFabrica($imagem, $W=200, $H=56, $fmt=null) {
        if (!is_null(self::$logo)) {
            $imagem = self::$logo;
            return ['html' => $imagem, 'alt' => $GLOBALS['login_fabrica_nome']];
        }



        self::init();

        if (is_numeric($imagem)) {
            if (!array_key_exists($imagem, self::$imagensLogo)) {
                return $GLOBALS['login_fabrica_nome'];
            }
         

            // Escolhe uma das imagens, se tiver mais de uma
            $links   = self::$imagensLogo[$imagem];
            $idx     = count($links) > 1 ? rand(0, count($links)-1) : 0;
            $imagem  = 'logos/'.$links[$idx];
        }

        $logoAttrs = '';

        return self::getImageAttributes($imagem, $W, $H, $fmt);
    }

    public static function getImageAttributes($imagem, $W, $H, $fmt=null) {
        if (is_readable($imagem) and !is_null($W)) {
            $logoAttrs = self::setLogoSize($imagem, $W, $H, 'css');
        }
        $info = array(
            'html'  => sprintf(
                '<img id="logo_fabrica" src="%s" alt="%s" style="%s" />',
                $imagem, $GLOBALS['login_fabrica_nome'], $logoAttrs
            ),
            'src'   => $imagem,
            'alt'   => $GLOBALS['login_fabrica_nome'],
            'style' => $logoAttrs
        );

        return is_null($fmt) ? $info : $info[$fmt];
    }

    /**
     * Devolve a URL da bandeira do ID (ISO 2) do país.
     * O formato pode ser png ou svn (minúsculo)
     */
    public static function getCountryFlag($pais, $fmt='png') {
        $pais = strtolower(substr($pais, 0, 2));
        $ext = $fmt == 'svg' ? : 'png';
        $fmt = $fmt == 'svg' ? : 'png250px';
        $url  = "https://cdn.rawgit.com/hjnilsson/country-flags/master/$fmt/$pais.$ext";
        return sprintf(
            '<img src="%s" style="margin-left:3px;%s" alt="%s" />',
            $url, self::setLogoSize($url, 32, 24, 'css'), $pais
        );
    }

    private static function getItemAttrs($arrAttrs) {
        foreach ($arrAttrs as $attrName => $attrValue) {
            $attr = (is_numeric($attrName)) ? 'class' : $attrName;
            $attrsList[$attr][] = is_array($attrValue) ? join(' ', $attrValue) : $attrValue;
        }
        foreach($attrsList as $name=>$values) {
            $attrs .= " $name='" . join(' ', $values) . "'";
        }
        return $attrs;
    }

    private static function showMenuItem($item) {
        global $login_fabrica, $login_posto, $login_admin, $login_unico;
        $show = false;

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

        if ($so_testes and $login_posto != 6359)
            return false;

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

    }

    /**
     * 'converte' os GIFs dos menus antigos para FontAwesome.
     * Considerar fazer um Search&replace nos arquivos na próxima
     * atualização.
     */
    public static function novoIcone($oldIcon) {
        if (array_key_exists($oldIcon, self::$iconList))
            return self::$iconList[$oldIcon];
        return $oldIcon;
    }

    public static function setLogoSize($logoImg, $maxX=220, $maxY=64, $type='css') {
        /*********************************************************
         * HD 746876 - Acertar no possível a medida das logos... *
         * Atualizando medida em função do "aspecto" da imagem.  *
         *********************************************************/
        if (is_readable($logoImg) or is_url($logoImg)) {
            list($logo_w, $logo_h) = @getimagesize($logoImg);

            if ($type == 'css') {
                $ratio = ($logo_w >= $logo_h) ? (100 * $maxX) / $logo_w : (100 * $maxY) / $logo_h;
                $new_w = intval($logo_w * ($ratio / 100));
                $new_h = intval($logo_h * ($ratio / 100));

				$new_h = ($logo_h > $maxY*0.9) ? $maxY : $new_h;
				$new_w = ($logo_w > $maxX*0.9) ? $maxX : $new_w;
                return "max-height:70px; max-width:250px;width:auto";
            }

            //Proporção da imagem, altura entre largura. 1 seria quadrada, > 1 seria mais alto que largo...
            $ratio = $logo_h / $logo_w;

            $max_h = ($logo_h > $maxY*0.9) ? $maxY : $logo_h;
            $max_w = ($logo_w > $maxX*0.9) ? $maxX : $logo_w;

            return ($ratio >= 0.25) ? " height='$max_h'" : " width='$max_w'";
        }
    }

    public static function getValorFabrica($values, $key=null) {
        if (!is_array($values))
            return $values;

        if (is_null($key))
            $key = $GLOBALS['login_fabrica'];

        return array_key_exists($key, $values)
            ? $values[$key]
            : $values['default'];
    }

    /**
     * Inicializa valores de propriedades estáticas, já que elas não podem
     * ser inicializadas em tempo de compilação com valores não constantes.
     */
    private static function init() {
        if (is_null($imagensLogo)) {
            self::$imagensLogo = include(APP_DIR . 'logos.inc.php');
        }
        return;
    }

}

