/*
 * This is a JavaScript Scratchpad.
 *
 * Enter some JavaScript, then Right Click or choose from the Execute Menu:
 * 1. Run to evaluate the selected text (Ctrl+R),
 * 2. Inspect to bring up an Object Inspector on the result (Ctrl+I), or,
 * 3. Display to insert the result in a comment after the selection. (Ctrl+L)
 */

$(function(){
  
  var pikaChooseDefaultValues = {
		autoPlay: false,
		speed: 5000,
		text: { play: "", stop: "", previous: "Previous", next: "Next", loading: "Loading" },
		transition:[1],
		showCaption: true,
		IESafe: false,
		showTooltips: false,
		carousel: false,
		swipe: true,
		carouselVertical: true,
		animationFinished: null,
		buildFinished: null,
		bindsFinished: null,
		startOn: 0,
		thumbOpacity: 0.4,
		hoverPause: false,
		animationSpeed: 0,
		fadeThumbsIn: false,
		carouselOptions: {},
		thumbChangeEvent: 'click.pikachoose',
		stopOnClick: false,
		hideThumbnails: false
	};

  var ajaxSaveBasicList = function(basicList){
    window.loading("show");
    $.ajax({
      url : 'vista_explodida_ajax.php',
      method : 'POST',
      data : {
        action : 'setMap',
        listaBasica : basicList.basicList,
        vista : basicList.explodeView,
        x1 : basicList.x1,
        x2 : basicList.x2,
        y1 : basicList.y1,
        y2 : basicList.y2
      },
      complete : function(){
        window.loading("hide");
      }


    })
  };
  
  var explodeViewDefaultArgs = {
    element : null,
    name : null,
    pikaChoose : null,
    rest : "explode_view_ajax.php",    
    views : {},
    basicList : {},
    listener : function(event){console.debug(event);},
    menuOkButton : $('<button class="btn btn-success">OK</button>'),
    menuCancelButton : $('<button class="btn btn-danger">Cancelar</button>'),
    menuPanel : $('<div style="position:fixed;top:50px;right:0px;background: rgba(0, 0, 0, 0.38);padding: 10px;border-radius: 20px 0px 0px 20px;" ></div>'),
    saveBasicList : ajaxSaveBasicList,
  };
  
  var explodeViewCounter = 0;


  
  
  var ExplodeView = function(args){
    var self = this;
    args = $.extend(explodeViewDefaultArgs,args);
    for (key in args){
      this[key] = args[key];
    }
    this.element = $(this.element)[0];
    if(this.element.id){
      this.id = this.element.id;
    }
    if(!this.id){
      this.id = ["ExplodeView",explodeViewCounter].join("-");
      explodeViewCounter += 1;
      $(this.element).attr("id",this.id);
    }
    window[this.id.replace(/-/g,'_')] = this;
    var explodeViews = $(this.element).find("img[explode-view]");
    explodeViews.each(function(){
      self.views[$(this).attr('explode-view')] = $(this)[0];
    });
    var basicLists = $(this.element).find("[basic-list]");
    var attrs = ["href","title","x1","x2","y1","y2","value"];
    basicLists.each(function(){
      var basicList = {};
      basicList.basicList = $(this).attr('basic-list');
      basicList.explodeView = $(this).attr('explode-view');
      for(var i in attrs){
          var attr = attrs[i];
          basicList[attr] = $(this).attr(attr);
      }
      console.debug($(this),basicList);
      self.basicList[$(this).attr('basic-list')] = basicList;
    });
    this.clear();
    this.render();
    this.refreshMap();
  };
  
  ExplodeView.prototype.print = function(){
    console.debug(this);
  };
  
  ExplodeView.prototype.clear = function(){
    $(this.element).html('');
  };

  ExplodeView.prototype.getCurrentImage = function(){
    return $(this.element).find('.pika-stage > img')[0];
  };
  
  ExplodeView.prototype.selectViewIndex = function(){
    var element = $(this.element).find("ul.pika-thumbs li.active")[0];
    return $(this.element).find("ul.pika-thumbs li").index(element);
  };
  
  ExplodeView.prototype.getSelectView = function(){
    return $(this.element).find("img[explode-view].active")[0];
  };
  
  ExplodeView.prototype.setSelectView = function(explodeViewId){
    $(this.element).find("img[explode-view='"+explodeViewId+"']").trigger("click");
  };

  ExplodeView.prototype.removeView = function(explodeViewId){
    var view = $(this.element).find("img[explode-view='"+explodeViewId+"']");
    delete this.views[explodeViewId];
    this.clear();
    this.render();
    this.refreshMap();
  };

  ExplodeView.prototype.putView = function(src,explodeViewId){
    var view = $('<img src="'+src+'" explode-view="'+explodeViewId+'" />');
    this.views[explodeViewId] = view;
    this.clear();
    this.render();
    this.refreshMap();
  };
  
  ExplodeView.prototype.makeArea = function(basicListId){
    var basicList = this.basicList[basicListId];
    var area = $('<area shape="rect"></area>');
    var attrs = ["title","href","x1","x2","y1","y2","value"];
    area.attr("basic-list",basicList.basicList);
    area.attr("explode-view",basicList.explodeView);
    for(var index in attrs){
      var attr = attrs[index];
      if(!attr in basicList)
        continue;
      area.attr(attr,basicList[attr]);
    }
    area.attr("coords",[basicList.x1,basicList.y1,basicList.x2,basicList.y2].join(","));
    return area[0];    
  };
  
  var toArea = function(coord){
    var area = $('<area shape="rect"></area>');
    var attrs = ["title","href","x1","x2","y1","y2","value"];
    area.attr("basic-list",coord.basicList);
    area.attr("explode-view",coord.explodeView);
    for(var index in attrs){
      var attr = attrs[index];
      if(!attr in coord)
        continue;
      area.attr(attr,coord[attr]);
    }
    area.attr("coords",[coord.x1,coord.y1,coord.x2,coord.y2].join(","));
    return area[0];
  };
  
  ExplodeView.prototype.render = function(){
    var maps = {};
    if(Object.keys(this.views).length == 0){
      $(this.element).hide();
    }
    else{
      $(this.element).show(); 
    }
    var imageList = $("<ul></ul>");
    for(var index in this.views){
      var image = this.views[index];
      var listNode = $("<li></li>");
      listNode.append($(image).clone());
      imageList.append(listNode);
      maps[index] = [];
    }
    $(this.element).append(imageList);
    this.pikaChoose = $(this.element).find('ul').PikaChoose(pikaChooseDefaultValues);
    
    
    console.debug("Lista Basica:",this.basicList);
    for(var index in this.basicList){
      var bl = this.basicList[index];
      console.debug(bl);
      if(!bl.explodeView){
        continue;
      }
      console.debug(bl);
      if(bl.explodeView in maps){
        maps[bl.explodeView].push(bl);  
      }
    }

    for(var index in maps){
      var basicLists = maps[index];
      console.debug("Map "+index+":",basicLists);
      var map = $("<map explode-view=\""+index+"\" name=\""+this.id+"-map-"+index+"\"></map>");  
      for(var i in basicLists){
        var basicList = basicLists[i];
        var area = toArea(basicList);
        map.append(area);
      }
      $(this.element).append(map);
    }
    
    var self = this;
    $(this.element).find('.pika-stage > img').on('load',function(){
      self.refreshMap();
    });
    //console.debug(this.basicList);
    //console.debug(this.pikaChoose);
  };

  ExplodeView.prototype.fireMapClickEvent = function(basicListId){
    var self = this;
    var basicList = this.basicList[basicListId];
    var area = $(this.element).find("[basic-list='"+basicListId+"']")[0];
    var event = {
      source : self,
      area : area,
      basicList : basicList
    };
    self.listener(event);
  };
  
  
  ExplodeView.prototype.refreshMap = function(){
    var self = this;
    var explodeView = $(this.getSelectView()).attr('explode-view');
    if(!explodeView){
      window.setTimeout(function(){self.refreshMap();},100);
      return;
    }
    var currentImage = this.getCurrentImage();
    var map = $(this.element).find("map[explode-view='"+explodeView+"']");
    map.find('area').each(function(){
      var x1 = $(this).attr('x1');
      var x2 = $(this).attr('x2');
      var y1 = $(this).attr('y1');
      var y2 = $(this).attr('y2');
      x1 = Math.round(currentImage.width * parseFloat(x1)/100.0);
      x2 = Math.round(currentImage.width * parseFloat(x2)/100.0);
      y1 = Math.round(currentImage.height * parseFloat(y1)/100.0);
      y2 = Math.round(currentImage.height * parseFloat(y2)/100.0);
      $(this).attr('coords',[x1,y1,x2,y2].join(","));
    });
    $(currentImage).attr('usemap','#'+map.attr('name'));
    console.debug(explodeView);
  };
  
  ExplodeView.prototype.refreshBasicList = function(){
    var self = this;
    $(self.element).find("[basic-list]").remove();
    
    for (basicListId in this.basicList){
      var basicList = this.basicList[basicListId];
      var area = this.makeArea(basicListId);
      $(this.element).find("map[explode-view='"+basicList.explodeView+"']").append(area);
    }
    
  };
  
  ExplodeView.prototype.createBasicList = function(basicList){
    var basicList = {
      basicList : basicList,
      explodeView : $(this.getSelectView()).attr("explode-view"),
      x1 : 25,
      x2 : 75,
      y1 : 25,
      y2 : 75
    };
    return basicList;
  };

  ExplodeView.prototype.hasBasicList = function(basicListId){
    return (basicListId in this.basicList);
  };

  var oldMenu = null;

  var makeMenu = function(areaSelect,basicListId,explodeView){
    if(oldMenu)
      oldMenu.remove();
    var okButton = explodeViewDefaultArgs.menuOkButton.clone();
    var cancelButton = explodeViewDefaultArgs.menuCancelButton.clone();
    var menu = explodeViewDefaultArgs.menuPanel.clone();
    menu.addClass("ExplodeViewMenu");
    menu.append(okButton);
    menu.append(cancelButton);
    okButton.click(function(){
      menu.remove();
      selection = areaSelect.getSelection();
      console.debug(selection);
      var currentImage = explodeView.getCurrentImage();
      var explodeViewId = $(explodeView.getSelectView()).attr("explode-view");
      var x1 = (selection.x1 * 100.0)/currentImage.width;
      var x2 = (selection.x2 * 100.0)/currentImage.width;
      var y1 = (selection.y1 * 100.0)/currentImage.height;
      var y2 = (selection.y2 * 100.0)/currentImage.height;
      explodeView.basicList[basicListId].x1 = x1;
      explodeView.basicList[basicListId].x2 = x2;
      explodeView.basicList[basicListId].y1 = y1;
      explodeView.basicList[basicListId].y2 = y2;
      explodeView.basicList[basicListId].explodeView = explodeViewId;
      console.debug(explodeView.basicList[basicListId]);
      areaSelect.cancelSelection();
      explodeView.refreshBasicList();
      explodeView.refreshMap();
      explodeView.saveBasicList(explodeView.basicList[basicListId]);
    });
    cancelButton.click(function(){
      menu.remove();
      areaSelect.cancelSelection();
    });
    $('html').append(menu);
    oldMenu = menu;
  };
  
  ExplodeView.prototype.editBasicList = function(basicListId){
    var basicList = this.createBasicList();
    if(this.hasBasicList(basicListId)){
      basicList = this.basicList[basicListId];
    }
    this.setSelectView(basicList.explodeView);
    var self = this;
    setTimeout(function(){
      var currentImage = self.getCurrentImage();
      self.element.scrollIntoView();
      var areaSelect = $(currentImage).imgAreaSelect({instance:true});
      var x1 = Math.round((basicList.x1 * currentImage.width)/100.0)
      var x2 = Math.round((basicList.x2 * currentImage.width)/100.0)
      var y1 = Math.round((basicList.y1 * currentImage.height)/100.0)
      var y2 = Math.round((basicList.y2 * currentImage.height)/100.0)
      areaSelect.setSelection(x1,y1,x2,y2);
      areaSelect.setOptions({show:true});
      areaSelect.update();
      makeMenu(areaSelect,basicListId,self);
      console.debug(basicList);
    },50);
  };
  
  
  window.ExplodeView = ExplodeView;
  
  
  var InitExplodeView = function(){
    console.debug('InitExplodeView');
    $(".ExplodeView").each(function(){
      var explodeViewArgs = {};
      var element = $(this)[0];
      explodeViewArgs.element = element;
      var listenerName = $(this).attr('listener');
      if(listenerName in window){
        explodeViewArgs.listener = window[listenerName];
      }
      var explodeView = new ExplodeView(explodeViewArgs);
    });
  }
  
  InitExplodeView();


  $(document).on("click",".ExplodeViewMap[basic-list][explode-view]",function(){
    var name = $(this).attr('explode-view').replace(/-/g,"_");
    var basicList = $(this).attr('basic-list');
    if(!name in window){
      console.warn(name+" instancia nao econtrada");
      return;
    }
    var instance = window[name];
    instance.editBasicList(basicList);    
  });

  $(document).on("click",".ExplodeView [basic-list]",function(){
    var basicListId = $(this).attr("basic-list");
    var explodeViewName = $(this).parents(".ExplodeView").attr('id').replace(/-/g,"_");
    if(!explodeViewName in window){
      console.warn(explodeViewName+" instancia nao econtrada");
      return;
    }
    var instance = window[explodeViewName];
    instance.fireMapClickEvent(basicListId);
  });
  
});

