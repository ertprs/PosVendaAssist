<?php
// define('DEBUG', true);
include_once '../class/tdocs.class.php';
$tDocs = new TDocs($con, $login_fabrica);

define('APP_URL', '//' . $_SERVER["HTTP_HOST"] .
	preg_replace('#/(admin|admin_es|admin_callcenter|helpdesk)#', '',
		dirname($_SERVER['SCRIPT_NAME'])) . DIRECTORY_SEPARATOR
);

//Verifica se o usuário está habilitado para usar a ferramenta de Chat.
function base64UrlEncode($_input) {
	return str_replace(array('=','+','/'),array('_','-',','),base64_encode($_input));
}

$filtro = array("<input ", "<form", "</form" );
$tDocs  = new TDocs($con, $login_fabrica);

$jsAtendentes = json_encode(pg_fetch_pairs(
	$con,
	"SELECT admin, login
	   FROM tbl_admin
	  WHERE fabrica = 10 AND ativo AND grupo_admin IS NOT NULL
	  ORDER BY login"
));

$jsFabricas = json_encode(pg_fetch_pairs(
	$con,
	"SELECT fn_retira_especiais(nome) as nome, fabrica FROM tbl_fabrica WHERE ativo_fabrica AND fabrica NOT IN (0,46)"
));

$assets = array(
	'icon' => 'imagens/tc_2009.ico',
	'logo' => '../imagens/icone_telecontrol_branco.png',
	'script' => array(
		'jQuery' => 'https://code.jquery.com/jquery-1.9.1.min.js',
		'BS3js'  => 'externos/bootstrap3/js/bootstrap.min.js',
		'mask' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js',
		'notifications' => 'helpdesk/notificacao_inicio_trabalho.js',
	),
	'style' => array(
		'BS3css' => 'externos/bootstrap3/css/bootstrap.min.css',
		'BS3thm' => 'externos/bootstrap3/css/bootstrap-theme.min.css',
		'tc_css' => '../admin/css/tc_css.css',
		'ajuste' => '../admin/bootstrap/css/ajuste.css',
		// 'extra'  => 'https://bootswatch.com/spacelab/bootstrap.min.css',
	)
);

if (in_array('datepicker', $bs_extras)) {
	$assets['script']['datepicker'] = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js';
	$assets['style']['datepicker']  = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.min.css';
	// $assets['style']['datepicker']  = 'externos/bootstrap3/datepicker/css/datepicker.css';
	// $assets['script']['datePicker'] = 'externos/bootstrap3/datepicker/js/bootstrap-datepicker.js';
	$assets['script']['dp_locale']  = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/locales/bootstrap-datepicker.'.$cook_idioma.'.min.js';
}

if (in_array('shadowbox', $bs_extras)) {
	$assets['script']['shadowbox'] = 'plugins/shadowbox/shadowbox.js';
	$assets['style']['shadowbox']  = 'plugins/shadowbox/shadowbox.css';
}

if (in_array('shadowbox_lupas', $bs_extras)) {
	$assets['script']['shadowbox_lupas'] = 'admin/plugins/shadowbox_lupa/shadowbox.js';
	$assets['style']['shadowbox_lupas']  = 'admin/plugins/shadowbox_lupa/shadowbox.css';
}

if (in_array('bstable', $bs_extras)) {
	$assets['script']['bstable']        = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js';
	$assets['script']['bstable_i8n']    = "//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/locale/bootstrap-table-$cook_idioma.min.js";
	$assets['script']['bstable_export'] = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/extensions/export/bootstrap-table-export.min.js';
	$assets['script']['table_export']   = '//rawgit.com/hhurz/tableExport.jquery.plugin/master/tableExport.js';
	$assets['script']['bstable_sticky'] = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/extensions/sticky-header/bootstrap-table-sticky-header.min.js';
	$assets['style']['bstable']         = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.css';
	$assets['style']['bstable_sticky']  = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/extensions/sticky-header/bootstrap-table-sticky-header.css';
}

if (in_array('dataTable', $bs_extras)) {
	$assets['script']['dataTable'] = '';
	$assets['style']['dataTable'] = '';
}

if (in_array('toggle', $bs_extras)) {
	$assets['script']['bsToggle'] = 'externos/bootstrap3/plugins/toggle/js/bootstrap-toggle.min.js';
	$assets['style']['bsToggle'] = 'externos/bootstrap3/plugins/toggle/css/bootstrap-toggle.min.css';
}

if (in_array('bstable', $bs_extras)) {
	$assets['script']['bstable'] = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js';
	$assets['script']['bstable_i8n'] = "//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/locale/bootstrap-table-$cook_idioma.min.js";
	$assets['style']['bstable'] = '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.css';
}

$URLs = array(
	'chat' => 'https://chat.telecontrol.com.br/',
	'livezilla' => 'http://www.livezilla.net'
);

array_walk($assets['script'], function(&$v) {
	if (substr($v, 0, 4) !== 'http' and substr($v, 0, 2) !== '//')
		$v = APP_URL . $v;
});

array_walk($assets['style'], function(&$v) {
	if (substr($v, 0, 4) !== 'http' and substr($v, 0, 2) !== '//')
		$v = APP_URL . $v;
});

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

				foreach($attrsList as $name=>$values) {
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
$analista_hd        = in_array($grupo_admin, array(1,2,7,9)) ? 'sim' : false;
$grupo_admin_valida = (bool)$grupo_admin;
$supervisor         = (bool)$login_help_desk_supervisor;
$suporte            = 432;
$avatar             = $cookie_login['cook_avatar'] ? : $tDocs->getDocumentsByRef($login_admin, 'adminfoto')->url;
$fabrica_logo       = $tDocs->getDocumentsByRef($login_fabrica, 'logo')->url ? : $assets['logo'];

// if ($login_fabrica == 10) {
// 	$fabrica_logo = $assets['icon'];
// }

//Verifica se o usuário está habilitado para usar a ferramenta de Chat.
if ($login_live_help) {
	$habilita_chat = true;
	$chat_nome     = base64UrlEncode($login_nome_completo);
	$chat_email    = base64UrlEncode($login_email);
	$chat_fabrica  = base64UrlEncode($fabrica_nome);
}

$analista_nome  = trim($login_nome_completo);
$analista_login = trim($login_login);
$analista_admin = trim($login_admin);

$iniciais = strpos($analista_nome, ' ') ?
	mb_strtoupper(substr($analista_nome, 0, 1) . substr($analista_nome, strpos($analista_nome, ' ')+1, 1)) :
	mb_strtoupper(substr($analista_nome, 0, 2));

if ($login_fabrica == 10) {
	$atende  = ($login_admin == $suporte) ? 'chamado' : 'atendimento';
	if (is_numeric($grupo_admin)) {
		$prefixo = 'adm_';
		$pref    = '_insere';
	} else {
		$prefixo = '';
		$atende  = 'chamado';
	}

	$show_current_hd = false;
	$where = array(
		'atendente' => $login_admin,
		'fabrica_responsavel' => 10,
		'status!' => array('Resolvido', 'Suspenso', 'Parado', 'Cancelado'),
	);

	if (!in_array($grupo_admin, [7, 8])) {
		$sql = "SELECT data_termino,hd_chamado
				  FROM tbl_hd_chamado_atendente
				 WHERE admin = $login_admin
				 ORDER BY hd_chamado_atendente DESC LIMIT 1";
		$res = pg_query($con,$sql);
		list($data_termino_atual, $hd_chamado_atual)  = pg_fetch_array($res, 0);
		$show_current_hd = empty($data_termino_atual);
		if (!$show_current_hd)
			$hd_chamado_atual = null;
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

} else {
	$atende = 'chamado';
}

$menuData = include('./menu_array.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="iso-8859-1">
	<title><?=$TITULO?></title>
<?php

foreach($assets as $type => $links) {
	// echo "<!-- $type " . join(', ', (array)($links)) . " -->\n";
	if ($type == 'script') {
		foreach ($links as $srcAddr)
			echo "\t<script type='text/javascript' src='$srcAddr'></script>\n";
	}
	if ($type == 'style')
		foreach ($links as $href)
			echo "\t<link rel='stylesheet' href='$href'>\n";
	if ($type == 'icon')
		echo "\t<link href='$links' rel='shortcut icon' />\n";
}
?>
	<style type="text/css">
	.img-initials {
		/* content: attr(data-letters); */
		color: #FAC81A;
		background: #373865;
		display: inline-block;
		width: 2.5em;
		height: 2.5em;
		line-height: 2.5em;
		text-align: center;
		vertical-align: middle;
		margin-right: 1em;
		font-weight: bold;
		font-size: 1em;
		border-radius: 50%;
	}
	.navbar .img-initials {
		width: 2em;
		height: 2em;
		line-height: 2em;
		font-size: 0.9em;
		float: left;      /* avoids vertical resize of menubar */
	}

	/* nav on hover */
	.dropdown:hover>.dropdown-menu {
		display: block;
	 }
	/**
	 * Submenus in dropdown was REMOVED in BS3!!!
	 */
	.dropdown-submenu {
		position:relative;
	}
	.dropdown-submenu>.dropdown-menu {
		top:0;
		left:100%;
		margin-top:-6px;
		margin-left:-1px;
		-webkit-border-radius:0 6px 6px 6px;
		-moz-border-radius:0 6px 6px 6px;
		border-radius:0 6px 6px 6px;
	}
	.dropdown-submenu:hover>.dropdown-menu {
		display:block;
	}
	.dropdown-submenu>a:after {
		display:block;
		content:" ";
		float:right;
		width:0;
		height:0;
		border-color:transparent;
		border-style:solid;
		border-width:5px 0 5px 5px;
		border-left-color:#cccccc;
		margin-top:5px;
		margin-right:-10px;
	}
	.dropdown-submenu:hover>a:after {
		border-left-color:#ffffff;
	}
	.dropdown-submenu.pull-left {
		float:none;
	}
	.dropdown-submenu.pull-left>.dropdown-menu {
		left:-100%;
		margin-left:10px;
		-webkit-border-radius:6px 0 6px 6px;
		-moz-border-radius:6px 0 6px 6px;
		border-radius:6px 0 6px 6px;
	}
	.link {padding: 3px 20px; cursor: pointer;}
	.linked {cursor: pointer;}

	/* Menu on hover if not mobile */
	.dropdown:hover>.dropdown-menu {
		display: block;
	}
	/* Estabelece um cabeçalho "azul TC" nas tabelas quando o <thead>
	* tiver a class 'primary' (seguindo o padrão do BS).
	*/
	thead.primary th, th.primary {
		background-color: #494999;
		color: white;
		text-transform: capitalize;
	}
	</style>
	<script>
	var lang = '<?=$cook_idioma?>' || 'pt-BR';
	var idioma_verifica_servidor = lang.toLowerCase();

	var Menu = {
		Fabricas: JSON.parse('<?=$jsFabricas?>'),
		Atendentes: JSON.parse('<?=$jsAtendentes?>'),
			userMenuIntID: null,
			userMenuTime: 90000,
		URLs: {
			prefixo: '<?=$prefixo?>',
			detalhe: 'chamado_detalhe',
			lista: 'chamado_lista',
			termino: 'chamado_detalhe',
			inicio: 'chamado_detalhe',
			logout: '',
			hd_atual: '<?=$hd_chamado_atual?>',
			hd_cur: '<?=$hd_chamado?>'
		},
		parseSearchInput: function(qStr) {
			var val = qStr || $("#menu_search_input").val().toLowerCase() || '';
			var parseStr = val.match(/^(\w{1,3}):\s?(.*)$/);
			var action = parseStr[2] !== undefined ?  parseStr[1] : false;
			qStr = parseStr.length == 3 ? parseStr[2] : val.trim();
		},
		getUrl: function(action, qParams) {
            qParams = qParams || {};
			var prog = this.URLs.hasOwnProperty(action) ? this.URLs.prefixo + this.URLs[action] + '.php' : action;

            switch (action) {
                case 'inicio':   qParams.inicio_trabalho = 1;  break;
                case 'consulta': qParams.consultar = 'sim';    break;
                case 'termino':  qParams.termino_trabalho = 1; break;
            }
			var parameters = typeof qParams === 'object' ? this.toQueryString(qParams) : qParams || '';

			if (parameters.length > 0)
				prog += '?';
			return prog + parameters;
		},
		toQueryString: function(obj, sep) {
			if (!typeof obj === 'object')
				return '';
			var r=[],
				joinStr = sep || '&';

			if (Array.isArray(obj))
				return obj.join(joinStr);

			for (var k in obj)
				r.push(encodeURIComponent(k)+'='+encodeURIComponent(obj[k]));
			return r.join(joinStr);
		},
		indexOf: function(val, obj) {
			var i;
			if (Array.isArray(obj))
				return obj.indexOf(val);
			for (i in obj) {
				if (obj[i].indexOf(val)>=0)
					return i;
			}
			return null;
		},
		index: function(val, obj) {
			if (Array.isArray(obj))
				return obj.indexOf(val);
			for (var i in obj) {
				if (i.toLowerCase().indexOf(val.toLowerCase())>=0)
					return i;
			}
			return null;
		},
		valueOf: function(val, obj) {
			if (Array.isArray(obj))
				return obj.indexOf(val);
			for (var i in obj) {
				if (i.indexOf(val)>=0)
					return obj[i];
			}
			return null;
		},
		updateUserMenu: function(action) {
			action = action || 'update';
			switch (action) {
				case 'start':
					if (this.userMenuIntID !== null) {
						return true;
					}
					this.userMenuIntID = window.setInterval(this.updateUserMenu, this.userMenuTime);
					return true;

				case 'stop':
					return window.clearInterval(this.userMenuIntID);

				case 'update':
					$("nav ul.navbar-right").load(
						'ajax_update_usermenu.php',
						function() {NotificationTC.updateIcon();}
					);
					break;
			}
		},
		toggleSubMenu: function(event) {
			// Avoid following the href location when clicking
			event.preventDefault();
			// Avoid having the menu to close when clicking
			event.stopPropagation();
			// If a menu is already open we close it
			$('ul.dropdown-submenu [data-toggle=dropdown]').parent().removeClass('open');
			// opening the one you clicked on
			$(this).parent().addClass('open');

			var menu = $(this).parent().find("ul");
			var menupos = menu.offset();

			if ((menupos.left + menu.width()) + 30 > $(window).width()) {
				var newpos = 0 - menu.width();
			} else {
				var newpos = $(this).parent().width();
			}
			menu.css({ left:newpos });
		}
	};

	function getHeight() {
		var winH = window.innerHeight,
			barH = $(".navbar").outerHeight(true),
			tbrH = $(".fixed-table-toolbar .columns").outerHeight(true),
			pgnH = $(".pagination").outerHeight(true);
		return winH - barH - tbrH - pgnH;
	}

	$(function() {
		function search(qStr) {
			var params = {};
			var val = qStr || $("#menu_search_input").val().toLowerCase() || '';
			// var parseStr = val.match(/^(\w{1,3}):\s?(.*)$/);
			// var action = parseStr.length == 3 ?  parseStr[1] : false;
			// qStr = parseStr.length == 3 ? parseStr[2] : val.trim();

			if (/^(?:log: ?)([a-zA-Z0-9_.\/-]+)$/.test(val)) {
				val = val.match(/^(?:log: ?)([a-zA-Z0-9_.\/-]+)$/)[1];
				$("#modalGL .modal-content").load('gitlog.php?page='+val);
				$("#modalGL").modal();
				$("#modalGL").on("hidden.bs.modal", function (ev) {
					$("#modalGL .modal-content").html('');
				});
				return true;
			}

			if (/^\d{6,7}$/.test(val) || /^hd(?: |: ?)(\d{5,7})$/.test(val)) {
				val = val.replace(/\D/g, '');
				console.log('HD', val);
				document.location.href = Menu.getUrl('detalhe', {hd_chamado: val, consultar:'sim'});
				return true;
			}
<?php       if ($prefixo === 'adm_'): ?>

			if (/(?:f: ?)(\w+)/.test(val)) {
				var f = Menu.index(RegExp.lastParen, Menu.Fabricas);
				if (f !== null) {
					// alert ("Pesquisando HDs da fábrica " + f);
					params.data_pesquisa = 'abertura';
					params.fabrica_busca = f;
				}
			}

			if (/(?:at: ?)(\w+)/.test(val)) {
				var atID = Menu.indexOf(RegExp.lastParen, Menu.Atendentes);
				if (atID !== null) {
					// alert ("Pesquisando HDs do atendente " + f);
                    params.atendente_busca = atID;
				}
			}
			if (Object.keys(params).length > 1) {
				document.location.href = Menu.getUrl('lista', params);
				return true;
			}
			<?php endif; ?>

			if (/^(?:t: ?)?(.+)$/.test(val)) {
				if (val.length > 4)
					document.location.href = Menu.getUrl('lista', {data_pesquisa:'abertura',valor_chamado: val});
			}
			return true;
		}

		$(window).keyup(function (e) {
			if (['INPUT', 'TEXTAREA'].indexOf(e.target.tagName) > -1)
				return true;
			if  (e.keyCode == 111 || e.key == '/')
				$("#menu_search_input").focus();
		});

		/**
		 * Submenus in dropdown was REMOVED in BS3!!!
		 */
		if (typeof $('a').on == 'function') {
			$('nav').on('click', 'li.dropdown-submenu [data-toggle=dropdown]', Menu.toggleSubMenu);
			$('nav').on('click', ".menu-item .linked", function() {
				var action = $(this).data('linktype');
				var hd     = $(this).data('hd');
				var lnk    = $(this).data('link') || '';
				if (lnk.length > 0) {
					document.location.assign(lnk);
				}
				document.location.assign(Menu.getUrl(action, {hd_chamado: hd}));
				return true;
			});
		} else {
			$('nav').live('li.dropdown-submenu [data-toggle=dropdown]', 'click', Menu.toggleSubMenu);
			$('nav').live(".menu-item .linked", 'click', function() {
				var action = $(this).data('linktype');
				var hd     = $(this).data('hd');
				var lnk    = $(this).data('link') || '';
				if (lnk.length > 0) {
					document.location.assign(lnk);
				}
				document.location.assign(Menu.getUrl(action, {hd_chamado: hd}));
				return true;
			});
		}

		$('[data-toggle=popover]').popover();

		$('#menu_search_input').change(
			function() {
				search(this.value);
		});
		$('#menu_search_input+span').click(
			function() {
				search($('#menu_search_input+span').val());
		});

		// Atualiza userMenu
		Menu.updateUserMenu('start');

	});
<?php if (array_key_exists('datepicker', $assets['script'])): ?>

	$('.input-group.date').datepicker({
		format: 'dd/mm/yyyy',
		language: lang,
		weekStart: (lang == 'es') ? 1 : 0,
		endDate: '0d'
	});
<?php endif ?>
<?php if (array_key_exists('bootstrapTable', $assets['script'])): ?>

	$("#results").bootstrapTable({
		showExport: true,
		ExportTypes: ['csv', 'txt', 'excel'],
		stickyHeader: true,
		// stickyHeaderOffsetY: '70px',
		height: getHeight()
	});

	$('#toolbar').find('select').change(function () {
		$("#results").bootstrapTable('destroy').bootstrapTable({
			exportDataType: $(this).val()
		});
	});

	$(window).resize(function () {
		$("#results").bootstrapTable('resetView', {
		height: getHeight()
		});
	});
<?php endif ?>
	</script>
<?php if (isset($headerHTML)): ?>
<?=$headerHTML?>
<?php endif ?>
</head>
<body style="padding-top: 64px">
<nav class="navbar navbar-default navbar-fixed-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#hd-main-menu" aria-expanded="false">
				<span class="sr-only">Menu</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<span class="navbar-brand">
			<img src="<?=$fabrica_logo?>" height="24" alt="<?=$fabrica_nome?>">
			</span>
		</div>
		<div id="hd-main-menu" class="collapse navbar-collapse">
			<ul class="nav navbar-nav">
				<?=menu2html($menuData['LEFT'])?>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<?=menu2html($menuData['RIGHT'])?>
		<?php if ($live): ?>
				<li><a id='chat_action'><img src='<?=$chat_icon?>' /></a></li>
		<?php endif; ?>
			</ul>
		<?php if ($menuData['RIGHT']['BuscarHD'] === true): ?>
			<form class="navbar-form navbar-right visible-md-inline visible-lg-inline" action="javascript:search(this)">
				<div class="form-group">
					<div class="input-group input-group-sm">
					<input type="search" id="menu_search_input" class="form-control"
					placeholder="<?=traduz('Pesquisar')?>" data-toggle='popover' data-html='true'
				 data-placement='bottom' data-trigger='focus'
				   data-content="
<ul class='list-group'>
	<li class='list-group-item'><h6><i class='glyphicon glyphicon-info-sign'></i> Abrir HD</h6><p class='list-group-item-text small'>Digitar o núm. do HD</p></li>
	<li class='list-group-item'><h6><i class='glyphicon glyphicon-filter'></i> Chamados de um fabricante</h6><p class='list-group-item-text small'>Escreva o nome ou <strong>f: </strong> parte do nome do fabricante</p></li>
	<li class='list-group-item'><h6><i class='glyphicon glyphicon-user'></i> Fila de HDs de um atendente</h6><p class='list-group-item-text small'>Digite <strong>at:</strong> <em>login do atendente</em></p></li>
	<li class='list-group-item'><h6><i class='glyphicon glyphicon-search'></i> Buscar HD por título</h6><p class='list-group-item-text small'>Digite o texto que estiver procurando. </p></li>
	<li class='list-group-item'><h6><i class='glyphicon glyphicon-tasks'></i> Git-log de um programa</h6><p class='list-group-item-text small'>Digite <strong>log:</strong> <em>path</em> do programa para <strong>git-log</strong></p></li>
</ul>
">
						<span class="input-group-addon input-group-addon-sm"><i class="glyphicon glyphicon-search" aria-hidden="true"></i></span>
					</div>
				</div>
			</form>
		<?php endif; ?>
		</div>
	</div>
</nav>
<div id="modalGL" class="modal" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content"></div>
	</div>
</div>
<?php
$comunicado_tc_obrigatorio = true;

$sql_c_tc = "SELECT admin,data_confirmacao FROM tbl_comunicado_tc_leitura WHERE admin = $login_admin AND data_confirmacao > '2012-04-09'";
$res_c_tc = pg_query($con, $sql_c_tc);

if (pg_num_rows($res_c_tc) == 0 and file_exists('tc_comunicado_hd.php')) {
#	include 'tc_comunicado_hd.php';
	if ($comunicado_tc_obrigatorio) {
#		include "rodape.php";
#		exit();
	}
}

if ($msg_success):
	$text = is_array($msg_success) ? join("<br />\n", $msg_success) : $msg_success;
?>
	<div class="container">
		<div class="alert alert-success alert-dismissible" role="alert">
			<button class="close" type="button" data-dismiss="alert"><span aria-hiden="true">&times;</span></button>
			<?=$text?>
		</div>
	</div>
<?php endif;

if ($msg_erro):
	$text = is_array($msg_erro) ? join("<br />\n", $msg_erro) : $msg_erro;
?>
	<div class="container">
		<div class="alert alert-danger alert-dismissible tac" role="alert">
			<button class="close" type="button" data-dismiss="alert"><span aria-hiden="true">&times;</span></button>
			<h4><b><?=$text?></b></h4>
		</div>
	</div>
<?php endif;
if ($desabilita_tela):
	include_once('rodape.php');
	exit;
endif;
flush();

