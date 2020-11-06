/**
 * Date: 03/07/2013
 * Description: Script para fazer as requisições e trocar de página.
 * Dev: Anderson Luciano
 */

$(document).ready(function() {
    $("a").tooltip({
        'selector': '',
        'placement': 'bottom'
    });
});

window.onload = function() {
    var menuLinks = document.getElementById("menu-acesso").getElementsByTagName('a')

    for (i = 0; i < menuLinks.length; i++) {
        menuLinks[i].addEventListener('click', clickLink, false);
    }

    var submenu = document.getElementsByClassName("list-rodape");
    for (j = 0; j < submenu.length; j++) {
        var submenuLinks = submenu[j].getElementsByTagName('a');

        for (i = 0; i < submenuLinks.length; i++) {
            submenuLinks[i].addEventListener('click', clickLinkSub, false);
        }
    }

}

function clickLink() {
    var pagina = this.getAttribute('pagina');
    getPage(pagina);
    changeMenu(this)
}

function clickLinkSub() {
    var pagina = this.getAttribute('pagina');
    getPage(pagina);
}


function getPage(pagina) {
    $.ajaxSetup({ cache: false });

    $.ajax({
        url: "documentacao/" + pagina + ".php",
        cache: false,
        success: function(retorno) {
            $("#documentacao").html("");            
            $("#documentacao").html(retorno);
        }
    });
}
var tes;
function changeMenu(menu) {
    tes = menu;
    if (document.getElementsByClassName('active')) {
        document.getElementsByClassName('active')[0].setAttribute("class", "");
        menu.parentNode.setAttribute("class", "active");
    }

}