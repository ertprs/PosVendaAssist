<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'mlg_funciones.php';

if (!function_exists('menu2html')) {
	function menu2html($menuData, $framework='bs3') {
		static $level = 0;
		$level++;

		$item_tpl = str_repeat("\t", 3) . "<li class='menu-item %4\$s'><a href='%1\$s' %3\$s>%2\$s</a></li>\n";
		$html = array();
		$submenu = function($menuTitle, $subMenuData) use ($level) {
			$icon      = $subMenuData['icon'] ? "<i class = 'glyphicon glyphicon-{$subMenuData['icon']}'></i> " : '';
			$menuTitle = $subMenuData['name'] ? : $menuTitle;
			$submenu   = $level > 1 ? 'dropdown-submenu' : '';
			$caret     = $level > 1 ? '' : "&nbsp;<span class='caret'></span>";
			$attrs     = $subMenuData['s'] ? : '';

			if ($attrs)
				unset($subMenuData['s']);

			return "  <li data-level='".$level--."' class='menu-item dropdown $submenu'>\n".
				"<a href='#' $attrs class='dropdown-toggle' data-toggle='dropdown' role='button' aria-haspopup='true' aria-expanded='false'>$icon$menuTitle$caret</a>\n" .
				"    <ul class='dropdown-menu'>\n    " . menu2html($subMenuData) . "\n    </ul>\n  </li>";
		};

		foreach ($menuData as $itemId => $itemData) {
			if ($itemData['hidden'] === true)
				continue;
            if (basename($_SERVER['PHP_SELF']) == basename($itemData['link']))
               $itemData['disabled'] = true;

			if (in_array($itemData['name'], array('sep', 'separator', 'divider'))) {
				$html[] = "<li class='divider' role='separator'></li>";
				continue;
			}

			if (array_key_exists('hint', $itemData)) {
				$itemData['attr']['title'][] = $itemData['hint'];
			}

			if ($itemData['disabled'] === true) {
				$itemData['link'] = '#';
				$itemData['attr']['onclick'][] = "this.preventDefault();return false";
				$itemData['iAtt'] .= " disabled";
			}

			$menuItemAttrs  = ($itemData['iAtt']) ? ' '.$itemData['iAtt'] : '';

			$attr      = '';
			$attrs     = '';
			$attrsList = array();

			if (array_key_exists('attr', $itemData)) {
				$arrAttr   = (array)$itemData['attr'];

				foreach ($arrAttr as $attrName => $attrValue) {
					$attr = (is_numeric($attrName)) ? 'class' : $attrName;
					$attrsList[$attr][] = is_array($attrValue) ? join(' ', $attrValue) : $attrValue;
				}

				foreach ($attrsList as $name=>$values) {
					$attrs .= " $name='" . join(' ', $values) . "'";
				}
			}

			if (array_key_exists('header', $itemData)) {
				$html[] = sprintf("\t\t\t\t<li class='dropdown-header' %s>%s</li>\n", $attrs, $itemData['header']);
				continue;
			}

			if (array_key_exists('link', $itemData)) {
				// Não é sub-menu
				$text = $itemData['name'] ? : $itemId;
				$link = $itemData['link'];
				$icon = $itemData['icon'] ? "<i class = 'glyphicon glyphicon-{$itemData['icon']}'></i> " : '';
				$text = $icon.$text;

				if (is_null($link)) {
					$html[] = sprintf(str_repeat("\t", 3) . "<li class='menu-item$menuItemAttrs'><div class='link'>%s</div></li>\n", $text);
					continue;
				}

				$html[] = sprintf($item_tpl, $link, $text, $attrs, $menuItemAttrs);
			} elseif (array_key_exists('submenu', $itemData)) {
				$itemData['submenu']['s'] = $attrs; // Para não ter que reprocessar tudo
				$html[] = $submenu($itemData['name']? : $itemId, $itemData['submenu']);
			}
		}
		$level--;
		return implode("\n\t\t\t\t", $html);
	}
}

// configuração do admin.
$analista_hd        = in_array($grupo_admin, array(1,2,7)) ? 'sim' : false;
$grupo_admin_valida = (bool)$grupo_admin;
$supervisor         = (bool)$login_help_desk_supervisor;
$suporte            = 432;
$avatar             = $cookie_login['cook_avatar'];
$fabrica_logo       = $assets['logo'];

// Prepara os dados do usuário
$atende = 'chamado';
$show_current_hd = false;
$where = array(
	'atendente' => $login_admin,
	'fabrica_responsavel' => 10,
	'status!' => array('Resolvido', 'Suspenso', 'Parado', 'Cancelado'),
);

if (!in_array($grupo_admin, [7, 8])) {
	$sql = "SELECT data_termino, hd_chamado
			  FROM tbl_hd_chamado_atendente
			 WHERE admin = $login_admin
			 ORDER BY hd_chamado_atendente DESC LIMIT 1";
	$res = pg_query($con,$sql);
	list($data_termino_atual, $hd_chamado_atual)  = pg_fetch_array($res, 0);
	$show_current_hd = empty($data_termino_atual);
	if (!$show_current_hd)
		$hd_chamado_atual = null;
}

// Usado pelo helpdesk/menu_array.php
$ajax_hdd_page = false;
if (strpos($_SERVER['HTTP_REFERER'], 'hd_chamado')) {
    $hd_chamado = preg_replace('/^.*hd_chamado=(\d+).*$/', '$1', $_SERVER['HTTP_REFERER']);
    if (is_numeric($hd_chamado))
        $ajax_hdd_page = true;
}

if ($hd_chamado or $hd_chamado_atual) {
	$nao_mostrar = array_filter(array($hd_chamado_atual, $hd_chamado));
	if (count($nao_mostrar))
		sort($nao_mostrar);
	$where['#hd_chamado!'] = $nao_mostrar;
	// die("$hd_chamado | $hd_chamado_atual | '$cond_hds'");
}

$meus_HDs = pg_fetch_all(
	pg_query(
		$con,
		$sql = "SELECT hd_chamado,
					   tbl_fabrica.nome AS fabricante,
					   fn_status_hd_item (hd_chamado, CURRENT_DATE) AS status,
					   titulo
				  FROM tbl_hd_chamado
				  JOIN tbl_fabrica USING(fabrica)
				 WHERE " . sql_where($where) . "
				 ORDER BY fabricante, hd_chamado DESC"
	)
);

$menuData = include('menu_array.php');
die(menu2html($menuData['RIGHT']));

