portalApp
  .directive("document", directiveDocument)
  .directive("group", group)
  .directive("groupul", groupul)
  .directive("iso8601", iso8601)
  .directive("searchEngine", search)
  .directive("onFocusSelect", onFocusSelect)
  .directive('uploadBox', uploadbox)
  .directive('uploadList', uploadList)
  .directive('workPlanCell', workPlanCell)
  .directive('ckEditor', ckEditor)
  .directive('preventKeyboardInput', preventKeyboardInput)
  .directive('limitednumber', limitedNumber)
  .directive('validNumber', validNumber)
  .directive('whenScrolled', whenScrolled)
  .directive('focusMe', focusMe)
  .directive('ngRightClick', ngRightClick)
  .directive('ctrlEnter', ctrlEnter)
  .directive('ddTextCollapse', ['$compile', ddTextCollapse])
  .directive('addParticipants', addParticipants)
  .directive('quoteSelection', quoteSelection)
  .directive('createDoc', createDoc)
  .directive('headMenu', headMenu)
  .directive('headSearch', headSearch)
  .directive('headMobileMenu', headMobileMenu)
  .directive('indexTasks', indexTasks)
  .directive('tMce', tMce)
  .directive('copyBody', copyBody)
  .directive('moveWindow', moveWindow)
  .directive('fastReply', fastReply)
  .directive('floatingMenu', floatingMenu)
  .directive('verticalScroll', verticalScroll)
  .directive('keepInViewport', keepInViewport)
  .directive('fadingOut', fadingOut)
  .directive('sidebarBlock', sidebarBlock)
  .directive('sidebarCollections', sidebarCollections)
  .directive('sidebarTasks', sidebarTasks)
  .directive('sidebarPopDiscus', sidebarPopDiscus)
  .directive('sidebarOrganizations', sidebarOrganizations)
  .directive('sidebarMainStat', sidebarMainStat)
  .directive('votings', votings)
  .directive('topLikes', topLikes)
  .directive('gallery', gallery)
  .directive('informers', informers)
  .directive('lineGraph', lineGraph)
  .directive('mainStat', mainStat)
  .directive('bindCompiledHtml', bindCompiledHtml)
  .directive('refresher', refresher)
  .directive('taskDateDifficultyModal', taskDateDifficultyModal)
  .directive('involvement', involvement)
  .directive('scrollOnDiscusLinks', scrollOnDiscusLinks)
  .directive('taskAdditions', taskAdditions)
  .directive('logClick', logClick)
  .directive('lightgallery', lightgallery)
  .directive('logClickTmceToolbarObserver', logClickTmceToolbarObserver)
  .directive('highlightingQuote', highlightingQuote)
  .directive('tagsList', tagsList)
  .directive('otherCommand', otherCommand)
  .directive('docDiff', docDiff)
  .directive('departmentsTeams', departmentsTeams)
  .directive('addTeamsdepartments', addTeamsdepartments)
  .directive('scrollList', scrollList)
;

highlightingQuote.$inject = ['$state'];
function highlightingQuote($state){
  return {
    restrict: 'A',
    link: function (scope, elem, attrs) {
      setTimeout(function(){
        if(attrs.fromSocket) {
          var match = scope.doc.body.match(/highlighting-quote-(.*?)\"/);
          if(match && match[1]){
            $('.highlighting-quote-'+match[1]).on('click', function(){
              $state.go('body.discus', {id: match[1]});
            });
          }
        }
        else if(scope.doc.unid) {
          var select = $('.highlighting-quote-'+scope.doc.unid);

          if(select.length){
            select.on('click', function(e){
              var quoteText = $(this).text();
              var oldBody = scope.doc.body;

              if(scope.doc.body.indexOf(quoteText) != -1){
                scope.doc.body = scope.doc.body.replace(quoteText, '<b>'+quoteText+'</b>');
              }
              else {
                scope.doc.body = '<b>'+scope.doc.body+'</b>';
              }

              setTimeout(function(){scope.doc.body = oldBody;}, 3000);
            });
          }
        }
      }, 1500);
    }
  }
}

function ddTextCollapse($compile) {
  return {
    restrict: 'A',
    link: function(scope, element, attrs) {
      scope.doc.ddCollapsed = false;
      var child = $('#'+scope.doc.unid+' .ddColl');

      scope.doc.toggle = function() {
        scope.doc.ddCollapsed = !scope.doc.ddCollapsed;
        if (scope.doc.ddCollapsed)
          child.css({
            'max-height': attrs.calculatedCollapseHeight + 'px',
            'overflow': 'hidden'
          });
        else
          child.css({
            'max-height': '',
            'overflow': ''
          });
        child.toggleClass('collapsed'); // this now only changes the visibility of images as the styles are overridden
      };

      // function counts nested <blockquote> which will be cut when block is collapsed
      function blockquoteRecursiveCounter(bq, offset, maxHeight) {
        var bqContent = bq.children();
        var bqCount = 1;
        for (var j = 0; j < bqContent.length; j++) {
          if (bqContent.eq(j).filter('blockquote').length
              && bqContent.eq(j).offset().top - offset + bqContent.eq(j).outerHeight() > maxHeight) {
                bqCount += blockquoteRecursiveCounter(bqContent.eq(j), offset, maxHeight);
                break;
              }
        }
        return bqCount;
      };

      // wait for text to load
      attrs.$observe('ddTextCollapseMaxHeight', function() {
        var maxHeight = scope.$eval(attrs.ddTextCollapseMaxHeight);
        var collapsedHeight = maxHeight;
        if (!child.length) child = $('#'+scope.doc.unid+' .ddColl');

        if (child.height() > maxHeight) {
          var childOffsetTop = child.offset().top;
          var content = child.children();
          for (var i = 0; i < content.length; i++) {
            var o = content.eq(i).offset().top;
            var h = content.eq(i).outerHeight();
            if (o - childOffsetTop > maxHeight) break;
            else if (o - childOffsetTop + h > maxHeight) {

              if (content.eq(i).filter('table').length) { // rules defining how to cut <table>
                var thead = content.eq(i).find('thead');
                var tbody = content.eq(i).find('tbody');
                if (thead.length && thead.outerHeight() > 0) {
                  collapsedHeight = thead.outerHeight() + o;
                } else if (tbody.length && tbody.outerHeight() > 0) {
                  var tbodyHeight = tbody.outerHeight();
                  var tbodyOffsetTop = tbody.offset().top;
                  var strokes = tbody.children();
                  for (var j = 0; j < strokes.length; j++) {
                    var so = strokes.eq(j).offset().top;
                    var sh = strokes.eq(j).outerHeight();
                    if (so - childOffsetTop + sh > maxHeight && j > 0) break;
                    else {
                      if (so - childOffsetTop + sh < maxHeight * 1.25) collapsedHeight = so - childOffsetTop + sh;
                      else collapsedHeight = so - childOffsetTop;
                    }
                  }
                }
              } else if (content.eq(i).filter('blockquote').length) { // rules defining how to cut <blockquote>
                var bqCount = blockquoteRecursiveCounter(content.eq(i), childOffsetTop, maxHeight);
                collapsedHeight = maxHeight - bqCount % 2 * 10; // padding top of <blockquote> is 10px (half the size of a stroke). This prevents cut in half strings from appearing.
              }

            }
          }

          attrs.$set('calculatedCollapseHeight', collapsedHeight);
          scope.doc.ddCollapsed = false; // turns TRUE inside the scope.doc.toggle function below;
          scope.doc.toggle();
          var toggleButton = $compile('<span class="collapse-text-toggle" ng-click="doc.toggle()">{{doc.ddCollapsed?\'Читать дальше\':\'Свернуть\';}}</span>')(scope);
          child.after(toggleButton);
        }
      });
    }
  };
};
function ctrlEnter() {
  return {
    restrict: 'A',
    link: function (scope, elem, attrs) {
      elem.bind('keydown', function(event) {
        var code = event.keyCode || event.which;
        if (code === 13) {
          if (event.ctrlKey) {
            event.preventDefault();
            scope.$apply(attrs.ctrlEnter);
          }
        }
      });
    }
  }
}

directiveDocument.$inject = ['$compile', '$http', '$templateCache', 'Voting', '$state', 'Popup'];
function directiveDocument($compile, $http, $templateCache, Voting, $state, Popup) {
  var path = "/bundles/tretoportal/partials/doc_templ/";
  return {
    restrict: "E",
    replace: true,
    scope: {
      doc: "=",
      accessMode: "@?",
      nomenu: "@?"
    },
    link: function (scope, element, attrs) {
      var list = scope.$watch("doc", function(newValue,OldValue,scope){
        if (newValue && scope.doc.form){
          if(! scope.accessMode) { scope.accessMode = 'read'; }
          var addr = path
            + (scope.accessMode == 'write' ? 'write/' : '')
            + scope.doc.form.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase()
            + (scope.doc.DocumentType ? '/'+scope.doc.DocumentType : '')
            +'.html';
          //addr += "?d="+(new Date().getTime());
          var loader = $http.get(addr,{cache: true});
          var promise = loader.then(function (response) {
            if (response.status == 200) element.html(response.data);
            element.replaceWith($compile(element.html())(scope));
          });
          list();
        }
      });
    },
    controller: function($rootScope, $scope, Dictionary, Popup) {
      if(! $rootScope.statusListDict) {
        $rootScope.statusListDict = new Dictionary('StatusList', true, false, true);
      }
      if(! $rootScope.positionsDict) {
        $rootScope.positionsDict = new Dictionary('Positions', true, false, true);
      }
      if(! $rootScope.infoSourceDict) {
        $rootScope.infoSourceDict = new Dictionary('InformationSourceCatalog', true, false, true);
      }
      if(! $rootScope.countryDict) {
        $rootScope.countryDict = new Dictionary('Country', true);
      }
      if(!$rootScope.companyNameDict){
        $rootScope.companyNameDict = new Dictionary('companyName', true, false, true);
      }
      if(!$rootScope.autoTaskDict){
        $rootScope.autoTaskDict = new Dictionary('AutoTaskPersons', true, false, true);
      }

      // $scope.statusListDict = $rootScope.statusListDict;
      // $scope.positionsDict = $rootScope.positionsDict;
      // $scope.infoSourceDict = $rootScope.infoSourceDict;
      // $scope.countryDict = $rootScope.countryDict;
      // $scope.companyNameDict =  $rootScope.companyNameDict;
    }
  }
};

quoteSelection.$inject = ['$timeout'];
function quoteSelection($timeout) {
  return {
    restrict: 'A',
    link: function (scope, elem, attrs) {

      $(document)
        .off('.hideQuoteMenuOnOutsideClick')
        .on('click.hideQuoteMenuOnOutsideClick touch.hideQuoteMenuOnOutsideClick', function() {
          var selection = window.getSelection();
          if (!selection.toString().length) {
            $('.quoteLinkTo').remove();
          }
        });

      elem.on('mousedown.quoteSelection touchstart.quoteSelection', function() {
        $(document).one('mouseup.quoteSelection touchend.quoteSelection', function() {
          $timeout(function() { showQuoteMenu(scope); }, 0);
        });
      });

    }
  }
}

function showQuoteMenu(scope){
  $('.quoteLinkTo').remove();
  var selection = window.getSelection();
  var h = window.location.hash;
  if (selection.toString().length>0 && h != '#' && h != '#/' && h != '') {
    var quote = selection.toString();
    var range = selection.getRangeAt(0);
    var span = document.createElement('span');
    var div = document.createElement('div');

    var expandedSelRange = range.cloneRange();
    var createTask = document.createElement('span');
    var createProcess = document.createElement('span');
    var icon = document.createElement('span');
    var quoteStr = selection.toString();

    icon.addEventListener('click', function(e) {
      e.preventDefault();
      scope.discus.answer(scope.doc || scope.discus.main_doc, quote);
      scope.$apply();
    });
    createTask.addEventListener('click', function(e) {
      e.preventDefault();
      scope.$parent.$parent.$parent.showForm('formTask', null, null, scope.doc.unid, scope.doc, quoteStr);
      scope.$apply();
    });
    createProcess.addEventListener('click', function(e) {
      e.preventDefault();
      scope.$parent.$parent.$parent.showForm('formProcess', null, null, scope.doc.unid, scope.doc, quoteStr);
      scope.$apply();
    });

    range.collapse(false);

    span.className = 'touch quoteLinkTo';
    createTask.className = 'ico answer-quote';
    createProcess.className = 'ico answer-quote';
    icon.className = 'ico answer-quote';

    createTask.innerText = 'Создать просьбу на основании';
    createTask.textContent = 'Создать просьбу на основании';
    createProcess.innerText = 'Создать тему на основании';
    createProcess.textContent = 'Создать тему на основании';
    icon.innerText = 'Ответить с цитированием';
    icon.textContent = 'Ответить с цитированием';

    div.appendChild(icon);
    div.appendChild(document.createElement('br'));
    div.appendChild(createTask);
    div.appendChild(document.createElement('br'));
    div.appendChild(createProcess);
    span.appendChild(div);

    var frag = document.createDocumentFragment();
    frag.appendChild(span);
    range.insertNode(frag);
    selection.removeAllRanges();
    selection.addRange(expandedSelRange);
    var containerWidth = $('.quoteLinkTo > div').outerWidth(true);
    var screenWidth = $(window).width();
    var containerLeftOffset = $('.quoteLinkTo').offset().left;
    var rightOffset = screenWidth - (containerLeftOffset + containerWidth);
    if(rightOffset < 0){
      $('.quoteLinkTo > div').css({top:25, right: 0, left:'initial'});
    }
  }
}

//Copy body to buffer when saving a document
function copyBody($parse) {
  return function(scope, element, attrs) {
    $(element).on('click', function () {
      var id = 'cont-element-copy-text';

      var oldElement = document.getElementById(id);
      if(oldElement){
        oldElement.remove();
      }

      var body = '';
      if(typeof scope.doc != 'undefined' && typeof scope.doc.body != 'undefined'){
        body = scope.doc.body;
      }
      else if(typeof scope.discus != 'undefined' && typeof scope.discus.body != 'undefined'){
        body = scope.discus.body;
      }
      else if(typeof scope.discus != 'undefined' && typeof scope.discus.current != 'undefined'){
        body = scope.discus.current.body;
      }
      else if(typeof scope.discus != 'undefined' && typeof scope.discus.main_doc != 'undefined'){
        body = scope.discus.main_doc.body;
      }

      if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
        var div = document.createElement("div");
        div.innerHTML = body;

        div.id = id;
        div.style.visibility = 'hidden';
        document.body.appendChild(div);
        var range = document.createRange();
        var element = document.getElementById(id);
        div.style.visibility = 'visible';
        range.selectNode(element);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        try {
           document.execCommand('copy');
        } catch(err) {
          console.log('Error execCommand copy!');
        }
        div.style.visibility = 'hidden';
        window.getSelection().removeAllRanges();
        element.remove();
      }
      else if(window.clipboardData && window.clipboardData.setData){
        clipboardData.setData("Text", body);
      }
    });
  };
}

function group() {
  return{
    restrict: 'E',
    replace: true,
    scope: {
      recordsTree: '='
    },
    templateUrl: "/bundles/tretoportal/partials/directs/group.html",
  };
}

iso8601.$inject = ['$compile','$rootScope','localize'];
function iso8601($compile,$rootScope,localize) {
  return{
    restrict: 'E',
    replace: true,
    scope: {
      ngModel: '=',
      timeSupport: '@?',
      initialize: '@?',
      ngDisabled: '=?'
    },
    templateUrl: "/bundles/tretoportal/partials/tretoDate.html",
    link: function link(scope,element, attrs) {

      scope.available = {
        years: [].range((scope.sy || 2015)-100, 200),
        months: $rootScope.dpo.monthsFull,
        days: [].range(1,29),
        hours: [].range(24),
        minutes: [].range(0,60,5),
        seconds: [].range(0,60,5)
      };
      scope.parseInt = parseInt;

      if(scope.ngModel || scope.initialize) {
        scope.src = $rootScope.dpo.tretoDateTime.iso8601.toDateTime(scope.ngModel);
        scope.sy = scope.src.getFullYear();
        scope.sm = (scope.src.getMonth()+'');
        scope.sd= scope.src.getDate();
        if(scope.timeSupport) {
          scope.sh = scope.src.getHours();
          scope.si = scope.src.getMinutes() - scope.src.getMinutes() % 5;
          scope.ss = 0;
        }
        scope.available.days = [].range(1,(scope.sm ? $rootScope.dpo.daysInMonth[scope.sm] : 29));
      }

      scope.dtClear = function() {
        scope.sy = scope.sm = scope.sd = '';
        if(scope.timeSupport) {
          scope.sh = scope.si = scope.ss = '';
        }
        if(scope.ngModel) { scope.ngModel = undefined; }
        scope.dtUpdate(true);
      };

      scope.dtUpdate = function(ignoreModel) {
        var current = new Date();
        scope.src = new Date(
          scope.sy || current.getFullYear(),
          scope.sm ? parseInt(scope.sm) : current.getMonth(),
          scope.sd || current.getDate(),
          scope.sh || (scope.timeSupport ? current.getHours() : '0'),
          scope.si || (scope.timeSupport ? current.getMinutes() : '0'),
          scope.ss || (scope.timeSupport ? current.getSeconds() : '0')
        );
        if(!ignoreModel) {
          scope.ngModel = $rootScope.dpo.tretoDateTime.iso8601.fromDateTime(scope.src, !scope.timeSupport);
        }
        scope.available.days = [].range(1,(scope.sm ? $rootScope.dpo.daysInMonth[scope.sm] : 29));
      };

      if(scope.initialize) {
        scope.dtUpdate();
      }
      $compile(element)(scope);
    }
  };
}

groupul.$inject = ['$compile'];
function groupul ($compile) {
  return{
    restrict: "E",
    replace: true,
    scope: {
      record: '='
    },
    template: '<ul id="{{record.key}}"></ul>',
    link: function (scope, element, attrs){
      if (scope.record.children) {
        element.append('<group records-tree="record"></group>');
        $compile(element.contents())(scope);
      }
    }
  }
}

search.$inject = ['$compile', '$http', '$state', '$rootScope','SearchHelper'];
function search($compile,$http,$state, SearchHelper) {
  return {
    restrict: 'A',
    scope: {
      q:'=q'
    },
    controller: function ($scope, $rootScope, $state, SearchHelper) {

      $rootScope.searchHelper = new SearchHelper();
    },
    template: '<input type="submit" ng-click="$root.searchHelper.firstPage()" class="hide" >'
  };
}

function onFocusSelect() {
    return function (scope, element, attrs) {
      element.bind('focus', function () {
        this.select();
      });
    };
}


uploadbox.$inject = ['$rootScope','FileUploader','$compile', '$http'];
function uploadbox($rootScope, FileUploader, $compile, $http, $timeout) {
  return {
    restrict: 'E',
    replace: true,
    scope: {
      uploadOptions: '=',
      multiple : '=',
      model: "=",
      auto: "=",
      templ: "=",
      one: "=",
      votepichandler: '&'
    },
    controller:function ($rootScope, $scope, FileUploader, $timeout, Popup, $document) {
      $scope.votePic = function(){
        $scope.votepichandler();
      };

      $scope.auto = $scope.auto || false;
      if(!$scope.model) { $scope.model = []; }

      $scope.uploadOptions.url = 'api/fs/addRecord/'+ $scope.uploadOptions.collection +'/' +$scope.uploadOptions.unid;
      $scope.uploader = new FileUploader($scope.uploadOptions);
      $scope.uploader.autoUpload = $scope.auto;
      $scope.uploader.removeAfterUpload = $scope.auto;

      $scope.uploader.filters.push({name:'urlFilter', fn:function(item) {
        $scope.uploader.url =  'api/fs/addRecord/'+ $scope.uploadOptions.collection +'/' +$scope.uploadOptions.unid;
        return true;
      }});

      $scope.uploader.onCompleteItem = function(item, response, status, headers) {
        if(!$scope.model) { $scope.model = [];}
        if(response.data && response.data.length > 0){
          if ($scope.one) {
            $scope.model[0] = response.data;
          }else{
            $scope.model.push(response.data);
          }
        }else{
          alert("Ошибка при загрузке файла '"+item.file.name+"'");
        }
      }
      $scope.uploader.onCompleteAll = function() {
        if($scope.votepichandler){
          $scope.votePic();
        }
      };

      $scope.controller = {
        isImage: function(item) {
          return false;
//           var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
//           return '|jpg|png|jpeg|bmp|gif|'.indexOf(type) !== -1;
        }
      };
      $scope.removePlaceholder = function() {
        $('#placeholder-span').remove();
      };
      $scope.pasteImg = function(event){
        var blockText = 'Для вставки снимка экрана из буфера нажмите CTRL+V';
        $scope.removePlaceholder();
        var div = event.currentTarget;
        var formdata = new FormData();
        var clipData = (event.clipboardData || event.originalEvent.clipboardData)
        var items = clipData.items;

        if(items){
          for(var i in clipData.types){
            if(clipData.types[i] == 'Files'){
              var arrKey = i;
              break;
            }
          }
        }

        if (typeof arrKey != 'undefined') {
          var blob = items[arrKey].getAsFile();
          window.console.log(blob);
          var reader = new FileReader();

          reader.onload = function(event){ }

          reader.readAsDataURL(blob);
          $scope.uploader.addToQueue(blob);

          $timeout(function() {
            div.innerHTML = blockText;
          }, 200)
        } else {
          $timeout(function() {
            var child = null;

            for (var i in div.childNodes) {
              if (div.childNodes[i].tagName == "IMG")
                child = div.childNodes[i];
            }

            if (child) {
              var pastedImage = new Image();
              var canvas = document.createElement("canvas");

              if (typeof canvas.toBlob !== 'function') {//IE, Safari
                new Popup('Внимание!', "Ваш браузер не поддерживает данную функцию. Используйте Mozilla Firefox, Google Chrome или любой основанный на них браузер", 'notify');
                $scope.$parent.discus.current.body += '<br/>' + child.outerHTML;
                div.innerHTML = blockText;
              } else {
                pastedImage.onload = function() {

                canvas.width = this.width;
                canvas.height = this.height;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(this, 0, 0);

                canvas.toBlob(function(blob){$scope.uploader.addToQueue(blob);});
                  div.innerHTML = blockText;
                }
                pastedImage.src = child.src;
              }

            }else{
              new Popup('Внимание!', "Предварительно скопируейте нужный экран в буфер, нажав  клавиши CTRL + PrtSc", 'notify');
              div.innerHTML = blockText;
            }
          }, 1);
        }
      }
      $scope.pasteKeydown = function(event) {
        if (event.keyCode === 17)
          $scope.ctrl = true;
        if($scope.ctrl && event.keyCode === 86){
          $scope.ctrl = false;
          return true;
        }
        event.preventDefault();
      };
      $scope.pasteKeyup = function(event) {
        if (event.keyCode === 17)
          $scope.ctrl = false;
      }
    },
    link: function(scope, element, attrs) {
      templ = attrs.templ || 'default.html';
      url = '/bundles/tretoportal/partials/upload_templ/' + templ;
      $http.get(url)
        .then(function(response){
          element.html($compile(response.data)(scope));
        });
    }
  };
}

uploadList.$inject = ['$compile', '$http'];
function uploadList($compile, $http) {
  return{
    restrict: 'E',
    replace: true,
    scope: {
      model: '=',
      difftype: '=?'
    },
    link: function link(scope,element, attrs) {
      scope.insert = function(doc, html) {
        bodyfield = '';
        if(typeof doc.body !== undefined) {
          bodyfield = 'body';
        } else if(typeof doc.viewBody !== undefined) {
          bodyfield = 'viewBody';
        } else { return; }
        if(bodyfield) {
          doc[bodyfield] += html;
        }
      };
      scope.insertImage = function(doc, link, thumbnail, title, rightAlign) {
        thumbnail = thumbnail || link;
        scope.insert(doc,'<a href="'+link+'" target="_blank" title="'+title+'" class="'+(rightAlign ? 'pull-right' : '')+'">'
          +'<img alt="'+title+'" src="'+thumbnail+'"/></a> ',link);
      };
      scope.insertLink = function(doc, link, title) {
        scope.insert(doc,'<a href="'+link+'" target="_blank"><span class="glyphicon glyphicon-save-file"></span>'+(title || link)+'</a> ',link);
      };
      scope.insertAnswerImage = function(doc, link, thumbnail) {
        thumbnail = thumbnail || link;
        if(!doc.AnswersData.length) { return; }
        if(!doc._meta.focus || doc._meta.focus >= doc.AnswersData.length) {
          doc._meta.focus = 0;
        }
        var sp = doc.AnswersData[doc._meta.focus].split('|');
        if(! sp[1]) {
          doc.AnswersData[doc._meta.focus] += '|' + thumbnail;
        } else {
          doc.AnswersData[doc._meta.focus] = sp[0] + '|' + thumbnail;
        }
      };
      scope.deleteFile = function(doc, index, discus, all) {
        if (all){
          angular.forEach(doc.attachments, function(att, key) {
            $http.get("api/fs/remove-reference/"+att[0].doc.hash+"/"+doc.unid);
          });
          doc.attachments = [];
        }else{
          $http.get("api/fs/remove-reference/"+doc.attachments[index][0].doc.hash+"/"+doc.unid)
            .then(function(response){
              doc.attachments.splice(index, 1);
            });
        }
      };
      // $compile(element)(scope);
    },
    templateUrl: function(elem,attrs) {
      var res = '/bundles/tretoportal/partials/upload_templ/';
      res += attrs.templateUrl || 'listing';
      res += '.html';
      return res;
    }
  };
}

function headSearch ( $log ) {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: "/bundles/tretoportal/partials/directs/head-search.html"
  };
}

function headMobileMenu ( $log ) {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: "/bundles/tretoportal/partials/directs/head-mobile-menu.html"
  };
}

function headMenu ( $log, $parse ) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/head-menu.html",
    controller: function($scope) {
      $scope.hideTimeOut = function(time, varStr) {
        var model = $parse(varStr);

        setTimeout(function() {
          model.assign($scope, false);
          $scope.$apply();
        }, time);
      }
    }
  };
}

function createDoc ( $log ) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/create-doc.html",
    controller: function($scope) {
      $scope.close = function() {
        $scope.show.menuCreate = false;
      }
    }
  };
}

workPlanCell.$inject = ['$log'];
function workPlanCell ( $log ) {
  return {
    restrict: 'E',
    scope: {
      'day':'=',
      'plan':'=',
      'model':'=',
      'username':'=',
      'planLogin':'=',
      'override':'='
    },
    templateUrl: "/bundles/tretoportal/partials/directs/workplancell.html",
    controller: function($scope, $rootScope, $http) {
      $scope.isopen=false;
      $scope.toggleDropdown = function() { $scope.isopen = 1; };
      $scope.resType = '';
      $scope.submitted = false;
      $scope.responsibleEmplMenu = false;
      $scope.useDeputy = false;
      $scope.delEmpl = '';
      $scope.dateOptions = $rootScope.dateRangePickerConf;
      $scope.datePicker = {startDate: null, endDate: null};

      /**
       * Open "deputy select" menu
       * @param $event
       * @param type
         */
      $scope.selectResponsibleEmpl = function($event, type){
        $event.preventDefault();
        $event.stopPropagation();
        $scope.resType = type;
        $scope.responsibleEmplMenu = !$scope.responsibleEmplMenu;
      };

      /**
       * Change empl status for one day (no deputy)
       * @param type
         */
      $scope.changeStatus = function (type) {
        $scope.day.label = type;
        if($scope.day.deputyLogin){
          delete $scope.day.deputyLogin;
        }
        if($scope.day.deputySal){
          delete $scope.day.deputySal;
        }
      };

      /**
       * Save deputy empl
       * @param $event
       * @param useDeputy
       * @param deputySal
         */
      $scope.saveResponsibleEmpl = function($event, useDeputy, deputySal){
        $event.preventDefault();
        $event.stopPropagation();
        if(useDeputy){
          if($scope.delEmpl){
            if($scope.delEmpl == $scope.planLogin){
              $scope.submitted = true;
              $scope.modalForm.$setValidity('coincidenceNames', false);
              return;
            }
            else {
              $scope.day.deputyLogin = $scope.delEmpl;
              if($scope.datePicker.startDate && $scope.datePicker.endDate){
                $scope.day.deputyRange = $scope.datePicker
                $scope.datePicker = {startDate: null, endDate: null};
              }
            }
          }
          else {
            $scope.submitted = true;
            $scope.modalForm.$setValidity('responsibleEmpl', false);
            return;
          }
        } else {
          if($scope.day.deputyLogin){
            delete $scope.day.deputyLogin;
          }
          if($scope.day.deputySal){
            delete $scope.day.deputySal;
          }
        }
        $scope.day.deputySal = typeof deputySal == 'undefined'?false:deputySal;
        $scope.day.label = $scope.resType;
        $scope.updateFn($scope.$parent);
      };

      /**
       * Close "deputy select" menu
       * @param $event
         */
      $scope.cancelResponsibleEmpl = function($event){
        $event.preventDefault();
        $event.stopPropagation();
        $scope.resType = '';
        $scope.responsibleEmplMenu = false;
        $scope.useDeputy=false;
        $scope.deputySal=false;
      };

      /**
       * Update empl status for one day
       * @param $parent
         */
      $scope.updateFn = function($parent) {
        angular.element('#date-picker').scope().busy = true;
        var unid = $scope.plan.unid;
        var url = 'api/wp/save' + '/' + unid + '/' + $scope.model.dateLabel;
        var params = {method:'POST', url: url, data: {workPlan:$scope.plan}};
        $http(params).then(function(data) {
          for(var wgl in $scope.model.workGroupList){
            for(var unid in $scope.model.workGroupList[wgl].planList){
              if(unid == $scope.plan.unid){
                for (var i = 0; i<$scope.model.workGroupList[wgl].planList[unid].data.length; i++) {
                  if(data.data[i] && data.data[i].label){
                    $scope.model.workGroupList[wgl].planList[unid].data[i].label = data.data[i].label + '';
                  }
                }
              }
            }
          }
          angular.element('#date-picker').scope().busy = false;
        },
        function(data,status){
          httpErrorHandler(data,status);
          angular.element('#date-picker').scope().busy = false;
        });
      }
      $scope.classFn = function(dt) {
        switch(dt) {
          case 'р':return 'business'
          case 'к':return 'unavailable'
          case 'в':return 'weekend'
          case 'б':return 'illness'
          case 'о':return 'vacation'
          default: return ''
        }
      }
    }
  };
}
function parseHistoryXml() {
    var fields = [];
    for(q in arguments) {fields.push(arguments[q]);}
    var input = fields.shift();
    var xml = input || '', parsed = [];
    while(xml.match(/<Entry [^>]*>/)) {
      var e = xml.match(/<Entry [^>]*>/);
      var Entry = (e instanceof Array) && e[0];
      xml = xml.substring(Entry.length);
      var m = /[A-Za-z]+=['"]([^'"]*)['"]/;
      var out = {};
      for(var r=[];r = m.exec(Entry); ) {
        var k = r[0].substring(0,r[0].indexOf('='));
        out[k] = r[1];
        Entry = Entry.substring(Entry.indexOf(r[0])+r[0].length);
      }
      if(out == {}) break;
      parsed.push( out );
    }
    if(parsed.length) {
      var ret = [];
      for(var p in parsed) {
        var r = [(1.0*p+1)+')'];
        for (var g in parsed[p]) {
          if(fields.indexOf(g)!=-1) {
            r.push( parsed[p][g] );
          }
        }
        ret.push(r.join(' '));
      }
      return ret.join('\n');
    }
    return input;
}

ckEditor.$inject = ['$timeout'];
function ckEditor($timeout) {
  return {
    require: '?ngModel',
    link: function($scope, elm, attr, ngModel) {
      var ck = CKEDITOR.replace(elm[0], {
        enterMode: CKEDITOR.ENTER_BR,
        shiftEnterMode: CKEDITOR.ENTER_P,
        startupFocus: true,
        autoGrow_bottomSpace: 50,
        autoGrow_maxHeight: 720,
        autoGrow_onStartup: true,
        pasteFilter: 'p; a[!href]'
      });
      ck.on('instanceReady', function(e) {
        if(elm.hasClass('maximize')) {
          ck.execCommand('maximize');
        }
      });

      var updateModel = function() {
        $scope.$apply(function() { ngModel.$setViewValue(ck.getData()); });
      };
      ck.on('change', updateModel);
      ck.on('pasteState', updateModel);

      ngModel.$render = function(value) {
        ck.setData(ngModel.$viewValue);
      };
    }
  };
}

function preventKeyboardInput() {
  return {
    scope: false,
    restrict: 'A',
    link: function($scope, element, $attrs) {
      element.bind('keydown', function(e) {
        e.preventDefault();
      });
    }
  };
};

function limitedNumber() {
  return {
    scope: {
      min:'@',
      max:'@',
      model:'=ngModel'
    },
    restrict: 'A',
    link: function($scope, element, attrs) {
      function checker() {
        var v = +$scope.model;
        $scope.$apply(function() {
          if (!isFinite(v)) { $scope.model = ''; }
          else if (v < +$scope.min) { $scope.model = $scope.min; }
          else if (v > +$scope.max) { $scope.model = $scope.max; }
        });
      };

      element.bind('keydown', function() {
        setTimeout(checker, 0);
      });

      element.bind('keypress', function(e) {
        var char = typeof e.charCode === 'undefined' ? e.keyCode : e.charCode;
        if (char < 48 || char > 57) { e.preventDefault(); }
      });
    }
  };
}

function validNumber() {
  return {
    require: '?ngModel',
    link: function(scope, element, attrs, ngModelCtrl) {
      if(!ngModelCtrl) {
        return;
      }

      ngModelCtrl.$parsers.push(function(val) {
        if (angular.isUndefined(val)) {
          var val = '';
        }
        var clean = val.replace(/[^0-9\.]/g, '');
        var decimalCheck = clean.split('.');

        if(!angular.isUndefined(decimalCheck[1])) {
          decimalCheck[1] = decimalCheck[1].slice(0,2);
          clean =decimalCheck[0] + '.' + decimalCheck[1];
        }

        if (val !== clean) {
          ngModelCtrl.$setViewValue(clean);
          ngModelCtrl.$render();
        }
        return clean;
      });

      element.bind('keypress', function(event) {
        if(event.keyCode === 32) {
          event.preventDefault();
        }
      });
    }
  };
}

function whenScrolled() {
  return{
    restrict: 'A',
    link: function(scope, elem, attrs){
      // we get a list of elements of size 1 and need the first element
      raw = elem[0];
      // we load more elements when scrolled past a limit
      elem.bind("scroll", function(){
        if(raw.scrollTop+raw.offsetHeight+5 >= raw.scrollHeight){
          scope.itemsBusy = false;
          // we can give any function which loads more elements into the list
          scope.$apply(attrs.whenScrolled);
        }
      });
    }
  }
}

function focusMe($timeout) {
  return {
    scope: { trigger: '=focusMe' },
    link: function(scope, element) {
      scope.$watch('trigger', function(value) {
        if(value === true) {
          element[0].focus();
          scope.trigger = false;
        }
      });
    }
  };
}

function ngRightClick($parse) {
  return function(scope, element, attrs) {
    var fn = $parse(attrs.ngRightClick);
    element.bind('contextmenu', function(event) {
      scope.$apply(function() {
        event.preventDefault();
        fn(scope, {$event:event});
      });
    });
  };
}

function addParticipants($compile) {
  return {
    restrict:'E',
    scope: {
      ngmodel: '=',
      multiple: '=',
      section: '=',
      placeholder: '=',
      inputdisabled: '=',
      typeaheadexpr: '=',
      query: '=',
      initfocus: '=',
      addhandler: '&',
      removehandler: '&',
      security: '=',
      inpwidth:'=?',
      sharePortal:'=?', // made it optional
      shareEnable:'=?', // made it optional (defaults to false in the directive's controller)
      maxParticipants:'=?',
      participantstatus: '=',
      hideinfo: '=',
      hideallinfo: '=',
    },
    templateUrl: function($element, $attrs) {
      // We can feed a custom template by @template-url attribute
      // Or we can pass 'new' attribute to the directive to get template with md-chips
      // Else the default (old) template will be used
      return $attrs.templateUrl ||
             ('new' in $attrs ? '/bundles/tretoportal/partials/directs/add-participants-new.html' :
                                '/bundles/tretoportal/partials/directs/add-participants.html');
    },
    compile: function(teml, attrs) {
      teml.html(teml.html().replace(/\"\{\{typeaheadexpr\}\}\"/,attrs.typeaheadexpr));
      teml.html(teml.html().replace(/\"\{\{placeholder\}\}\"/,attrs.placeholder));
      if ('maxParticipants' in attrs)
        teml.html(teml.html().replace(/\"\{\{maxParticipants\}\}\"/,attrs.maxParticipants));
      
      // установка значения по умолчанию для атрибута 'participantstatus'
      if ( !('participantstatus' in attrs) ) {
        attrs.participantstatus = "'Участники'";
      }
      teml.html(teml.html().replace(/\"\{\{participantstatus\}\}\"/,attrs.participantstatus));

      if ( !('hideinfo' in attrs) ) {
        attrs.hideinfo = "false";
      }
      if ( !('hideallinfo' in attrs) ) {
        attrs.hideallinfo = "false";
      }
    },
    controller: function ($scope, $log, $timeout, $rootScope, Popup, DeputyPopup, $attrs, $element) {
      // флаг проверки открыто/закрыто всплывающее окно с выбором участников из команд и отделов (директива addTeamsdepartments)
      $scope.showAddTeamsdepartments = false;

      $rootScope.rebuildUsersArray();
      $scope.attrs = $attrs;
      $scope.md = {
        searchParticipant: null,
      };

      $scope.inpwidth = angular.isDefined($scope.inpwidth) ? $scope.inpwidth : '145px';
      if (!$scope.ngmodel) {
        $scope.ngmodel = [];
      }

      $scope.shareEnable = $scope.shareEnable || false; //defaults to false;
      if(!$scope.shareEnable || !$scope.sharePortal){
          $scope.sharePortal = {};
      }

      $scope.getShareCount = function(){
        var count = 0;
        if($scope.sharePortal){
          for(var domain in $scope.sharePortal){
            for(var i in $scope.sharePortal[domain]){
              count++;
            }
          }
        }

        return count;
      };

      // fix for use without Discus
      if ($scope.$parent.discus) {
        $scope.$parent.discus.sharePushArray = $scope.sharePortal;
        $scope.$parent.discus.shareEnable = $scope.shareEnable;
        $scope.$parent.discus.tempShareParticipants = [];
      }

      for(var i in $scope.security) {
        if($scope.security[i].username && $scope.ngmodel.indexOf($scope.security[i].username) == -1){
          $scope.ngmodel.push($scope.security[i].username);
        }
      }

      // заполнение объекта UserInfo с доменом и логином выбранного участника команды
      var user = [];
      $scope.userInfo = [];
      for(var domain in $scope.sharePortal) {
        for(var shareLogin in $scope.sharePortal[domain]) {
          user = {domain: domain, login: $scope.sharePortal[domain][shareLogin]};
          $scope.userInfo.push(user);
        }
      }
      $scope.fillUserInfo = function() {
        return $scope.userInfo;
      };


      $scope.findShareName = function(domain, login){
        return $scope.$parent.discus.findShareDataByLogins('fullName', domain, login);
      };

      $scope.removeSharePerson = function(findLogin, findDomain){
        for(var domain in $scope.sharePortal){
          if(findDomain == domain){
            for(var i = 0; i < $scope.sharePortal[domain].length; i++){
              if($scope.sharePortal[domain][i] == findLogin){
                $scope.sharePortal[domain].splice(i,1);
              }
            }
          }

          // удаление данных (домен, логин) из объекта UserInfo при удалении участника команды в объекте sharePortal
          for(var i = 0; i < $scope.userInfo.length; i++){
            if(findDomain == $scope.userInfo[i].domain && findLogin == $scope.userInfo[i].login){
              $scope.userInfo.splice(i,1);
            }
          }
        }

        $scope.$parent.discus.sharePushArray = $scope.sharePortal;
      };

      // When modifying this function, don't forget to symmetrically modify @$scope.addPersonChip(item, recursive) below
      $scope.addPerson = function(item, recursive) {
        if (!item.id) item.id = item.login;
        var inArr = false;
        if(!item.domain){
          for (key in $scope.ngmodel) {
            if ($scope.ngmodel[key] == item.id) {
              inArr = true;
              break;
            }
          }
          if (!inArr) {
            var status = false;
            if($rootScope.users[item.id] && $rootScope.users[item.id].status) status = $rootScope.users[item.id].status;
            if(status && status.deputyLogin && (typeof recursive == 'undefined' || !recursive)){
              new DeputyPopup(
                  item.id,
                  status.deputyLogin,
                  status.terms,
                  $scope.attrs && $scope.attrs.isParticipants,
                  function(login, recurs){ $scope.addPerson({id:login}, recurs); }
              );
            }
            else {
              $scope.ngmodel.push(item.id);
              if($scope.addhandler) {
                $scope.query != undefined ? $scope.addhandler({item:item, query:$scope.query}) : $scope.addhandler({item:item});
              }
            }
          }
        }
        else {
          if(!$scope.$parent.discus.sharePushArray[item.domain]){
            $scope.$parent.discus.sharePushArray[item.domain] = [];
          }

          var indexOfUsername = $scope.$parent.discus.sharePushArray[item.domain].indexOf(item.username);
          if(indexOfUsername == -1){
            $scope.$parent.discus.sharePushArray[item.domain].push(item.username);
          }

          // Добавление/удаление данных (домен, логин) из объекта userInfo при удалении/добавлении данных в объект $parent.discus.sharePushArray
          var user = [];
          $scope.userInfo.length = 0;
          for(var domain in $scope.$parent.discus.sharePushArray) {
            for(var i = 0; i < $scope.$parent.discus.sharePushArray[domain].length; i++) {
              var shareLogin = $scope.$parent.discus.sharePushArray[domain][i];
              user = {domain: domain, login: shareLogin};
              $scope.userInfo.push(user);
            }
          }
        }
      };

      // This is a bit modified version of @$scope.addPerson(item, recursive) for md-chips so that new template is backwards compatible
      // When modifying this function, don't forget to symmetrically modify @$scope.addPerson(item, recursive) above
      $scope.addPersonChip = function(item, recursive) {
        if (!item) return; //prevent double execute of function due to splice (see code '$scope.ngmodel.splice' below)
        if (!item.id) item.id = item.login;
        var inArr = false;
        if(!item.domain) {
          for (key in $scope.ngmodel) {
            if ($scope.ngmodel[key] == item.id) {
              inArr = true;
              break;
            }
          }
          if (!inArr) {
            var status = false;
            if($rootScope.users[item.id] && $rootScope.users[item.id].status) status = $rootScope.users[item.id].status;
            if(status && status.deputyLogin && (typeof recursive == 'undefined' || !recursive)){
              new DeputyPopup(
                  item.id,
                  status.deputyLogin,
                  status.terms,
                  $scope.attrs && $scope.attrs.isParticipants,
                  function(login, recurs){ $scope.addPersonChip({id:login}, recurs); }
              );
            }
            else {
              $scope.ngmodel.splice($scope.ngmodel.length-1, 1, item.id); //hack to replace whole item placed into ngmodel by md-chips with only item.id for compatibility
              if($scope.addhandler) {
                $scope.query != undefined ? $scope.addhandler({item:item, query:$scope.query}) : $scope.addhandler({item:item});
              }
            }
          } else $scope.ngmodel.splice($scope.ngmodel.length-1, 1); //remove automatically added item
        }
        else {
          if (!$scope.$parent.discus.sharePushArray[item.domain]) {
            $scope.$parent.discus.sharePushArray[item.domain] = [];
          }

          var indexOfUsername = $scope.$parent.discus.sharePushArray[item.domain].indexOf(item.username);
          if(indexOfUsername == -1){
            $scope.$parent.discus.sharePushArray[item.domain].push(item.username);
          } 
        }
      };

      // When modifying this function, don't forget to symmetrically modify @$scope.addRemoveChip(item, recursive) below
      $scope.removePerson = function(id) {
        for (key in $scope.ngmodel) {
          if ($scope.ngmodel[key] == id) {
            $scope.ngmodel.splice(key,1);
            if($scope.removehandler) {
              $scope.query != undefined ? $scope.removehandler({item:id, query:$scope.query}) : $scope.removehandler({item:null});
            }
            break;
          }
        }
      }

      // This is a bit modified version of @$scope.removePerson(id) for md-chips so that new template is backwards compatible
      // When modifying this function, don't forget to symmetrically modify @$scope.removePerson(id) above
      $scope.removePersonChip = function(id) {
        if (!id) return;
        if($scope.removehandler) {
          $scope.query != undefined ? $scope.removehandler({item:id, query:$scope.query}) : $scope.removehandler({item:null});
        }
      }

      $scope.focusOnMe = function() {
        $timeout(function() {
          $('#participantInput').focus();
        }, 200);
      }
    }
  }
}

function indexTasks($uibModal) {
  return{
    controller: function($scope, $timeout, $rootScope) {
      $rootScope.expiredPopup.modal = $uibModal.open({
        templateUrl: '/bundles/tretoportal/partials/directs/index-tasks.html',
        resolve: {}
      });
      $rootScope.expiredPopup.ok = function(){
        $rootScope.expiredPopup.show = false;
        $rootScope.expiredPopup.modal.close();
      };
    }
  };
}

function tMce(Discus, Socket, $rootScope) {
  return {
    priority: 10,
    restrict: "E",
    replace: true,
    scope: {
      model: '=',
      mentions: '=',
      toolbarContainer: '=?'
    },
    controller: function($scope, $timeout, $attrs, $rootScope) {
      var toolbar = typeof $attrs.toolbar !== 'undefined' && $attrs.toolbar == 'off'?false:'bold italic underline strikethrough removeformat | bullist numlist | forecolor backcolor | link media table | emoticons';
      $scope.tinymceOptions = {
        plugins : 'media advlist link paste lists charmap image imagetools print preview emoticons textcolor colorpicker table placeholder mention',
        selector: 'div',
        body_class: 'tmce_editor',
        skin: 'custom',
        theme : 'modern',
        statusbar: false,
        language: 'ru',
        browser_spellcheck: true,
        invalid_elements: 'header',
        extended_valid_elements:"div[title]",
        toolbar:toolbar,
        menubar: false,
        inline: true,
        // debounce: false,
        textcolor_rows: "1",
        textcolor_cols: "10",
        default_link_target: "_blank",
        target_list: false,
        table_default_attributes: {
          width: 150,
          height: 150,
          bordercolor: '#000000',
          border: 1
        },
        fixed_toolbar_container: $scope.toolbarContainer || '#mytoolbar',
        init_instance_callback: function () {
          if ('noAutofocus' in $attrs) return;
          tinymce.activeEditor.focus();
          var element = tinymce.activeEditor;
          element.selection.select(element.getBody(), true);
          element.selection.collapse(false);
          setTimeout(function() { $('[autofocus]').eq(0).focus(); }, 0);
        },
        paste_preprocess: function(plugin, args) {
          if (/api\/fs\/thumbnail/.test(args.content)){
            args.content = args.content.replace(/thumbnail/g,'src');
          }
        },
        mentions: {
          source: function(query, process, delimiter) {
            if (delimiter === '@') {
              this.query = query;
              var res = [];

              if ($rootScope.users) {
                if (query.length > 0) {
                  for(var i in $rootScope.users) {
                    if(this.matcher($rootScope.users[i])) {
                      res.push($rootScope.users[i]);
                    }
                  }

                  for(var domain in $rootScope.shareUsers){
                    for(var section in $rootScope.shareUsers[domain].data){
                      var sectionObj = $rootScope.shareUsers[domain].data[section].data;

                      for(var shareKey in sectionObj){
                        var shareUserObj = {
                          name:sectionObj[shareKey].name+' '+sectionObj[shareKey].LastName+' ('+domain+')',
                          domain:domain,
                          id:sectionObj[shareKey].username,
                          WorkGroup:sectionObj[shareKey].WorkGroup?sectionObj[shareKey].WorkGroup:[],
                        };

                        if(this.matcher(shareUserObj)){
                          res.push(shareUserObj);
                        }
                      }
                    }
                  }
                } else {

                  res = $.map($rootScope.users, function(value, index) {
                    return [value];
                  });

                }
                process(res);
              } else alert("Произошла ошибка, вам необходимо заново войти в учетную запись и обновить страницу.");
            }
          },
          matcher: function (item) {
            matchWorkGroup = function(query, user) {
              if (user.WorkGroup) {
                for (var i in user.WorkGroup) {
                  if (user.WorkGroup[i].toLowerCase().indexOf(query.toLowerCase()) > -1) {
                    return true;
                  }
                }
              }
              return false;
            };

            if(item.id.toLowerCase().indexOf(this.query.toLowerCase()) > -1   ||
               item.name.toLowerCase().indexOf(this.query.toLowerCase()) > -1 ||
               matchWorkGroup(this.query, item)) {
                 return true;
              }
            return false;
          },
          insert: function(item) {
            $scope.mentions = $scope.mentions ? $scope.mentions : [];
            $scope.mentions.push(item.id);
            var domain = typeof item.domain != 'undefined'?'data-domain="'+item.domain+'"':'';

            return '<span id="mention_'+item.id+'" '+domain+' data-mce-style="color: #00a7ed;" style="color: rgb(0, 167, 237);"><em>@'+item.name+', '+item.workgroup+'</em></span> &zwnj;';
          },
          items: 5,
        },
      };

      $scope.tinymceOptions.textcolor_map = [
        "000000", "Black",
        "FF6600", "Orange",
        "008000", "Green",
        "0000FF", "Blue",
        "00A7ED", "LightBlue",
        "808080", "Gray",
        "FF0000", "Red",
        "800080", "Purple",
        "FFFF00", "Yellow",
        "FFFFFF", "White"
        ];

      $scope.tinymceOptions.setup = function(editor) {
        editor
          .on("init", function() {})
          .on("keydown", function(e) {
            if (e.ctrlKey && e.keyCode == 13) return false;
            if ($rootScope.$state.current.name!=='body.notifications'){
              if (Discus.main_doc) {
                Socket.get(function(socket) {
                 socket.emit("typing", {login: $rootScope.user.username, discus: Discus.main_doc.unid});
                });
              }
            }
          });
        if ('eternalToolbar' in $attrs)
          editor.on('blur', function(e) {
            e.stopImmediatePropagation();
          });
      }
    },
    templateUrl: '/bundles/tretoportal/partials/directs/tMce.html'
  };
}

function moveWindow() {
  return {
    scope: {
      moveBy:'@moveWindow',
      dragBy:'@?moveWindowHandler'
    },
    restrict: 'A',
    link: function($scope, element, $attrs) {
      $scope.properties = $scope.moveBy || '';
      $scope.properties = $scope.properties.split(' ');
      $scope.properties = $scope.properties.filter(function(v) {
        var allowed = {bottom: 0, left: 0, right: 0, top: 0};
        return v in allowed;
      });

      $scope.handler = element.find($scope.dragBy);
      if (!$scope.handler.length) $scope.handler = element;

      $scope.handler
        .on('mousedown touchstart', function(e) {
          if (e.target !== e.currentTarget) return;
          $scope.windowPos = {};
          Array.prototype.forEach.call($scope.properties, function(property) {
            $scope.windowPos[property] = parseInt(element.css(property));
          });
          $scope.cursorStart = {
            x: e.pageX || e.originalEvent.changedTouches[0].pageX,
            y: e.pageY || e.originalEvent.changedTouches[0].pageY
          };

          $(document)
            .on('mouseup.moveWindow touchend.moveWindow', function() {
              $(document).off('.moveWindow');
            })
            .on('mousemove.moveWindow touchmove.moveWindow', function(e) {
              e.preventDefault();
              var cursor = {
                x: e.pageX || e.originalEvent.changedTouches[0].pageX,
                y: e.pageY || e.originalEvent.changedTouches[0].pageY
              };
              var move = {};
              Array.prototype.forEach.call($scope.properties, function(property) {
                switch (property) {
                  case 'bottom': move[property] = ($scope.windowPos[property] - cursor.y + $scope.cursorStart.y) + 'px'; break;
                  case 'left': move[property] = ($scope.windowPos[property] + cursor.x - $scope.cursorStart.x) + 'px'; break;
                  case 'right': move[property] = ($scope.windowPos[property] - cursor.x + $scope.cursorStart.x) + 'px'; break;
                  case 'top': move[property] = ($scope.windowPos[property] + cursor.y - $scope.cursorStart.y) + 'px'; break;
                }
              });
              element.css(move);
            });
        });
    }
  };
};

function fastReply($rootScope, Discus, Popup, GUID, Dictionary, $timeout, $state) {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: "/bundles/tretoportal/partials/directs/fast-reply.html",
    link: function($scope, element, $attrs) {
      $scope.isReplyFocusedOnce = false;
      $scope.showToolbar = false;
      $scope.fastReplyLength = 0;
      var oldpad = 0;
      var fastReplyParent = element.parent();
      var recalcPaddingTimer;
      var discusMode = false;

      if ( ~['body.discus'].indexOf($state.current.name) ) {
        console.log('fast-reply in discus mode');
        element.attr('id', 'fast-reply');
        discusMode = true;
      }
        
      var recalcPadding = function() {
        $scope.fastReplyLength = element.find('.reply').text().trim().length;
        $scope.hasAttach = element.find('.has-attachments').length > 0;
        var newpad = element.outerHeight();
        if (oldpad !== newpad) {
          fastReplyParent.css('padding-bottom', newpad);
          if (newpad > oldpad)
            $(window).scrollTop( $(window).scrollTop() + newpad - oldpad );
          oldpad = newpad;
        }
        recalcPaddingTimer = $timeout(recalcPadding, 200); // fix: timy-mce preventsDefault on ctrl+v and ctrl+c
      };
      recalcPadding();

      $scope.locale = new Dictionary('Locale', true);
      $scope.discus = discusMode ? Discus : $scope.$parent.discus;
      var d = $scope.discus;
      d.current = {};
      d.current.unid = GUID();
      d.fastReplyMsg = {};
      d.sending = false;

      var okSave = function(explicitEditing) {
        if ($rootScope.uploader) delete $rootScope.uploader;
        if ($scope.expanded && $scope.expanded[d.main_doc.unid] !== undefined)
          $scope.expanded[d.main_doc.unid] = false;
        d.sending = true;
        d.saveCurrent(explicitEditing);
        d.fastReplyMsg = {};
        d.fastReplyMsg.mentions = [];
        d.fastReplyMsg.attachments = [];
        d.fastReplyMsg.NotForSite = '1';
        d.current = {};
        d.current.unid = GUID();
      };

      $scope.ok = function(explicitEditing) {
        var optionalParams = [
          'subject',
          'body',
          'mentions',
          'attachments',
          'locale',
          'NotForSite'
        ];

        // for debounce
        d.fastReplyMsg.body = element.find("[id^='ui-tinymce']").html();
        element.find("[id^='ui-tinymce']").html('');

        d.prepareForWrite('message');
        for (var param = 0; param < optionalParams.length; param++)
          if (optionalParams[param] in d.fastReplyMsg)
            d.current[optionalParams[param]] = d.fastReplyMsg[optionalParams[param]];

        var main = d.main_doc;
        if ((main.ToSite === '1' || (main.form == 'Contact' && (main.ContactStatus.indexOf(7) !== -1 || main.ContactStatus.indexOf('7') !== -1 ||
            main.ContactStatus.indexOf(10) !== -1 && main.ContactStatus.indexOf('10') !== -1))) && d.current.NotForSite != '1')
          Popup("Публичная тема",
                "Внимание. Ответ публикуется на сайте! Публиковать?",
                '',
                true,
                function(){
                  okSave(explicitEditing);
                },
                function(){});
        else okSave(explicitEditing);
      };

      $scope.readOnType = _.throttle(d.readAllTheDocs, 500);
      
      if (discusMode) {
        var deboundedReadDocs = _.debounce(d.readAllTheDocs, 500);
        $(window).on('scroll.fastReply focus.fastReply', deboundedReadDocs);
      }
        
      $scope.$on('$destroy', function() {
        $timeout.cancel(recalcPaddingTimer);
        $(window).off('.fastReply');
      });
    }
  };
};

function floatingMenu() {
  return {
    restrict: 'A',
    replace: false,
    link: function($scope, element, $attrs) {
      var header = $('#header');
      var lastScrollTop = 0;
      var lastHeaderHeight = 0;
      var lastMenuHeight = 0;
      var offset = 0;
      var frame = null;

      element.addClass('animate-floating-block floating-menu');
      header.addClass('animate-floating-block');

      function onResize() {
        setTimeout(function() {
          var newHeaderHeight = header.outerHeight();
          var newMenuHeight = element.outerHeight();
          if (lastHeaderHeight !== newHeaderHeight) {
            element.css('top', newHeaderHeight + 'px');
            lastHeaderHeight = newHeaderHeight;
          }
          if (lastMenuHeight !== newMenuHeight) {
            element.parent().height(newMenuHeight);
            lastMenuHeight = newMenuHeight;
          }
        }, 0);
      };

      function onScroll(e) {

        if (cancelAnimationFrame) cancelAnimationFrame(frame);
        if (requestAnimationFrame) frame = requestAnimationFrame(draw);
        else draw();

        function draw() {
          onResize();
          var scrollTop = $(window).scrollTop();
          offset = Math.min(Math.max(0, offset + scrollTop - lastScrollTop), element.outerHeight());
          rule = 'translate3d(0,-'+offset+'px,0)';
          element.css({
            '-webkit-transform': rule,
            '-moz-transform': rule,
            '-ms-webkit-transform': rule,
            '-o-transform': rule,
            'transform': rule
          });
          if (scrollTop - offset === 0) element.addClass('no-float');
          else element.removeClass('no-float');

          lastScrollTop = scrollTop;
        };

      };

      var observer = new MutationObserver(onScroll);
      var config = {childList: true, subtree: true};
      observer.observe(element.get(0), config);

      onResize();
      $(window)
        .on('resize.floating-menu', onResize)
        .on('scroll.floating-menu', onScroll);
      $scope.$on('$destroy', function() {
        observer.disconnect();
        $(window).off('.floating-menu');
        header
          .removeClass('animate-floating-block')
          .css({
            '-webkit-transform': '',
            '-moz-transform': '',
            '-ms-webkit-transform': '',
            '-o-transform': '',
            'transform': ''
          })
      });
    }
  };
};

verticalScroll.$inject = ['RequestAnimationFrame', 'CancelAnimationFrame', 'WheelEventName'];
function verticalScroll(RequestAnimationFrame, CancelAnimationFrame, WheelEventName) {
  return {
      restrict: 'A',
      replace: false,
      link: function($scope, element, $attrs) {
        element
          .addClass('vs-viewport');

        if (!element.children().filter('.vs-screen').length)
          element.children().wrapAll('<div class="vs-screen">');

        element.append('<div class="vs-scroll-wrap"><div class="vs-scroll"></div></div>');

        var rAF = RequestAnimationFrame;
        var cAF = CancelAnimationFrame;
        var viewport = element;
        var screen = element.children().filter('.vs-screen');
        var scrollWrap = element.children().filter('.vs-scroll-wrap');
        var scroll = scrollWrap.find('.vs-scroll');
        var state = {
          scrollerTop: 0,
          screenTop: 0,
          screenHeight: screen.outerHeight(),
          viewportHeight: viewport.outerHeight(),
          scrollerHeight: null,
          startY: null,
          frame: null
        };

        var getScrollerHeight = function() {
          return Math.max((state.viewportHeight / state.screenHeight * state.viewportHeight) || 0, 26);
        };

        state.scrollerHeight = getScrollerHeight();
        scroll.css('height', state.scrollerHeight + 'px');

        var updateScrollSize = function() {
          state.screenHeight = screen.outerHeight();
          state.viewportHeight = viewport.outerHeight();

          if (state.screenHeight > state.viewportHeight) {
            state.scrollerHeight = getScrollerHeight();
            scroll.css('height', state.scrollerHeight + 'px');
            scrollWrap.show();
          } else {
            scrollWrap.hide();
          }
        };

        var observerTimer = null;
        var observer = new MutationObserver(function() {
          clearTimeout(observerTimer);
          observerTimer = setTimeout(updateScrollSize, 200);
        });
        var config = {childList: true, subtree: true};
        observer.observe(viewport.get(0), config);

        viewport
          .on('resizeScroll.verticalScroll', function() {
            clearTimeout(observerTimer);
            observerTimer = setTimeout(updateScrollSize, 200);
          })
          .on(WheelEventName+'.verticalScroll', function(e) {
            e.preventDefault();
            var deltaY = e.originalEvent.deltaY ||
                         e.originalEvent.detail ||
                         -e.originalEvent.wheelDeltaY || 0;
            
            cAF(state.frame);
            state.frame = rAF(function() {
              state.screenTop = Math.max(Math.min(state.screenTop + deltaY, state.screenHeight - state.viewportHeight), 0);
              state.scrollerTop = Math.min(Math.max(0, state.screenTop / (state.screenHeight - state.viewportHeight)) || 0, 1) * (state.viewportHeight - state.scrollerHeight);
              var scrollRule = 'translate3d(0,'+state.scrollerTop+'px,0)';
              var screenRule = 'translate3d(0,-'+state.screenTop+'px,0)';
              scroll.css({
                '-webkit-transform': scrollRule,
                '-moz-transform': scrollRule,
                '-ms-transform': scrollRule,
                '-o-transform': scrollRule,
                'transform': scrollRule
              });
              screen.css({
                '-webkit-transform': screenRule,
                '-moz-transform': screenRule,
                '-ms-transform': screenRule,
                '-o-transform': screenRule,
                'transform': screenRule
              });
            });
          });

        scroll
          .on('mousedown.verticalScroll touchstart.verticalScroll', function(e) {
            e.preventDefault();
            state.startY = e.pageY || e.originalEvent.changedTouches[0].pageY;

            $(document)
              .on('mouseup.verticalScroll touchend.verticalScroll', function(e) {
                e.preventDefault();
                $(document).off('.verticalScroll');
                var y = e.pageY || e.originalEvent.changedTouches[0].pageY;
                state.scrollerTop = Math.min(Math.max(0, state.scrollerTop + y - state.startY), state.viewportHeight - state.scrollerHeight);
                state.screenTop = Math.floor(state.scrollerTop / (state.viewportHeight - state.scrollerHeight) * (state.screenHeight - state.viewportHeight) || 0);
              })
              .on('mousemove.verticalScroll touchmove.verticalScroll', function(e) {
                e.preventDefault();
                var y = e.pageY || e.originalEvent.changedTouches[0].pageY;

                cAF(state.frame);
                state.frame = rAF(draw);

                function draw() {
                  var scrollMove = Math.min(Math.max(0, state.scrollerTop + y - state.startY), state.viewportHeight - state.scrollerHeight);
                  var screenMove = Math.floor((scrollMove / state.viewportHeight * state.screenHeight) || 0);
                  var scrollRule = 'translate3d(0,'+scrollMove+'px,0)';
                  var screenRule = 'translate3d(0,-'+screenMove+'px,0)';
                  scroll.css({
                    '-webkit-transform': scrollRule,
                    '-moz-transform': scrollRule,
                    '-ms-transform': scrollRule,
                    '-o-transform': scrollRule,
                    'transform': scrollRule
                  });
                  screen.css({
                    '-webkit-transform': screenRule,
                    '-moz-transform': screenRule,
                    '-ms-transform': screenRule,
                    '-o-transform': screenRule,
                    'transform': screenRule
                  });
                };
              })

          });

          $scope.$on('$destroy', function() {
            observer.disconnect();
            $(document).off('.verticalScroll');
          });
      }
  };
};

function keepInViewport(Viewport) {
  return {
    restrict: 'A',
    replace: false,
    link: function($scope, element, $attrs) {
      var elem;
      if (element.attr('keep-in-viewport') !== '') elem = element.find(element.attr('keep-in-viewport')).eq(0);
      if (!elem || !elem.length) elem = element;
      $scope.place = function(){
        if (placing) return;
        placing = true;
        var viewport = Viewport.get();
        var el = {
          height: elem.outerHeight(),
          width: elem.outerWidth(),
          offset: elem.offset()
        };

        var move = {
          top: Math.min(0, (viewport.bottom) - (el.height + el.offset.top - state.top) - borderOffset),
          left: Math.min(0, (viewport.right) - (el.width + el.offset.left - state.left) - borderOffset)
        };

        move.top = move.top - Math.min(0, el.offset.top + move.top - state.top - viewport.top - borderOffset);
        move.left = move.left - Math.min(0, el.offset.left + move.left - state.left - viewport.left - borderOffset);

        if (state.top !== move.top || state.left !== move.left) {
          state = {
            top: move.top,
            left: move.left
          };

          var rule = 'translate3d('+Math.floor(move.left)+'px,'+Math.floor(move.top)+'px,0)';

          elem.css({
            '-webkit-transform': rule,
            '-moz-transform': rule,
            '-ms-transform': rule,
            '-o-transform': rule,
            'transform': rule
          });
        }

        placing = false;
      };

      function checker() {
        if (!display && elem.css('display') !== 'none') {
          display = true;
          $scope.place();
        } else if (display && elem.css('display') === 'none') {
          display = false;
        } else if (display && (height != elem.outerHeight() || width != elem.outerWidth())) {
          height = elem.outerHeight();
          width = elem.outerWidth();
          $scope.place();
        }
      };

      var state = {
        left: 0,
        top: 0
      };
      var borderOffset = 5;
      var display = false;
      var height = 0;
      var width = 0;
      var timer = null;
      var placing = false;
      var observer = new MutationObserver(function() {
        clearTimeout(timer);
        checker();
        timer = setTimeout(checker, 0);
      });
      observer.observe(elem.get(0), {attributes: true, subtree: true});

      $(window).on('resize.kiv'+$scope.$id, function() {
        if (elem.css('display') !== 'none') $scope.place();
      });

      $scope.$on('$destroy', function() {
        observer.disconnect();
        $(window).off('.kiv'+$scope.$id);
      })
    }
  }
};

function fadingOut($compile) {
  return {
    restrict:'A',
    link: function ($scope, element, $attrs, $log, $timeout, $rootScope) {
      var child = element.children(':first');

      child.on('scroll', function() {
        if (child.scrollTop() > 0) element.addClass('fading-container');
        else element.removeClass('fading-container');
      });

      $scope.$on('$destroy', function() {
        child.off();
      });
    }
  }
};

function sidebarBlock ( $compile, $log, ListDiscus, TretoDateTime, Contacts, DiscusSharedSvc) {
  return {
    restrict: 'E',
    scope: {
      state: '=',
      title: '=',
      upperbtn: '=',
      upperbtnfn: '&',
      category: '=',
      lowerbtn: '=',
      lowerbtnfn: '&',
      index: '=?',
      withcomments: '=?',
    },
    templateUrl: "/bundles/tretoportal/partials/directs/sidebar-block.html",
    controller: function($scope, $attrs, $rootScope, ListDiscus, TretoDateTime, Contacts, DiscusSharedSvc) {
      $scope.listDiscus = new ListDiscus();
      
      var minusMonthDate = new Date(),
          minusYearDate = new Date();
      
      minusMonthDate.setMonth(minusMonthDate.getMonth() - 1);
      minusMonthDate = TretoDateTime.iso8601.fromDate(minusMonthDate);
      minusYearDate.setFullYear(1800+minusYearDate.getYear());
      minusYearDate = TretoDateTime.iso8601.fromDate(minusYearDate);
      
      var passData = function(docs) {
        $scope.data = docs;
        $scope.listDiscus.loading[$scope.category] = false;
      }
      
      if ($scope.withcomments != true) $scope.withcomments = false;
      
      if ($scope.category) {
        $scope.listDiscus.loading[$scope.category] = true;
        switch ($scope.category) {
          case 'Новые темы':
            $scope.listDiscus.byCategoryLimited('new', 0, 8, passData);
            break;
          case 'Подвешанные просьбы':
            $scope.listDiscus.getWaitPerformerTasks(0, 8, passData);
            break;
          case 'Запрос с сайта':
            Contacts({group: {name: "Запрос с сайта", search: "989DAD2607E12193C32577E000771DB1"}}, 0, 8, passData);
            break;
          case 'HR отдел,Вакансии':
            $scope.listDiscus.byCategoryFromDateLimited($scope.category, minusYearDate, 10, passData);
            break;
          case 'Blog':
            $scope.listDiscus.byTypeFromDateLimited('Blog', minusMonthDate, 8, passData);
            break;
          default:
            $scope.listDiscus.byCategoryFromDateLimited($scope.category, minusMonthDate, 8, passData, $scope.withcomments);
        }
      }
      
      $scope.getBlockState = function(blockName, change) {
        return DiscusSharedSvc.getBlockState(blockName, change);
      }
      
      $scope.upperButtonFunction  = function () {
        var form = 'formProcess';
        var type = null;
        var cats = $scope.category.split(',');
        if ($scope.category == "Blog") $scope.upperbtnfn && $scope.upperbtnfn({form:form, type: "Blog"});
        else                           $scope.upperbtnfn && $scope.upperbtnfn({form:form, type:type, cats:cats});
      }
      
      $scope.lowerButtonFunction  = function () {
        var subscribeID = $scope.index;
        $scope.lowerbtnfn && $scope.lowerbtnfn({subscribeID:subscribeID});
      }

      $scope.daysAgo = function(date, days) {
        dateDayAgo = new Date();
        dateDayAgo.setDate(dateDayAgo.getDate()-days);
        docDate = TretoDateTime.iso8601.toDate(date);
        return dateDayAgo < docDate;
      }
    }
  };
};

function sidebarCollections ( $compile, $log, ListDiscus, TretoDateTime, Contacts, DiscusSharedSvc) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/sidebar-collections.html",
    controller: function($scope, $attrs, $rootScope, TECollection, TretoDateTime, Contacts, DiscusSharedSvc) {
      $scope.getBlockState = function(blockName, change) {
        return DiscusSharedSvc.getBlockState(blockName, change);
      }
      
      $scope.teCollection = {
        lastThree: {}
      };
      TECollection.getLastThree('publication', function(data) {
        $scope.teCollection.lastThree.publication = data;
      });
      TECollection.getLastThree('delivery', function(data) {
        $scope.teCollection.lastThree.delivery = data;
      });
    }
  };
};

function sidebarTasks ( $compile, $log, Tasks, DiscusSharedSvc) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/sidebar-tasks.html",
    controller: function($scope, $attrs, $rootScope, Tasks) {
      $scope.getBlockState = function(blockName, change) {
        return DiscusSharedSvc.getBlockState(blockName, change);
      }
      
      $scope.myTasks = new Tasks();
      $scope.q = {$and: []};
      $scope.myTasks.performerMe(true, $scope.q);
      $scope.q.$and[1] = {status : 'open'};
      $scope.q.$and[2] = {$or: [
        { taskDateCompleted: ''},
        { taskDateCompleted: {$exists: false} }
      ]};

      $scope.myTasks.search(angular.toJson($scope.q));
    }
  };
};

function sidebarPopDiscus ( $compile, $log, Stat, DiscusSharedSvc) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/sidebar-pop-discus.html",
    controller: function($scope, $attrs, $rootScope, Stat, DiscusSharedSvc) {
      $scope.getBlockState = function(blockName, change) {
        return DiscusSharedSvc.getBlockState(blockName, change);
      }
      
      $scope.loadingPopDiscus = true;
      Stat.getPopularThemes(function(data) {
        $scope.popDiscus = data;
        $scope.loadingPopDiscus = false;
      });
      
      $scope.loadingMyPopDiscus = true;
      Stat.getPopularThemes(function(data) {
        $scope.popMyDiscus = data;
        $scope.loadingMyPopDiscus = false;
      }, $rootScope.user.username);
    }
  };
};

function sidebarOrganizations ( $compile, $log, Contacts, TretoDateTime, DiscusSharedSvc) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/sidebar-organizations.html",
    controller: function($scope, $attrs, $rootScope, Contacts, DiscusSharedSvc) {
      $scope.getBlockState = function(blockName, change) {
        return DiscusSharedSvc.getBlockState(blockName, change);
      }

      $scope.daysAgo = function(date, days) {
        dateDayAgo = new Date();
        dateDayAgo.setDate(dateDayAgo.getDate()-days);
        docDate = TretoDateTime.iso8601.toDate(date);
        return dateDayAgo < docDate;
      }

      $scope.loadingContacts = true;
      $scope.contacts = [];
      Contacts( {contact: {name: "Компании", search: "Organization"}}, 0, 7, function(conts) {
        $scope.loadingContacts = false;
        $scope.contacts = conts;
        if ($scope.contacts.length > 0) {
          $scope.statusOrganizations = "";
        } else {
          $scope.statusOrganizations = "Ничего не найдено.";
        }
      });
    }
  };
};

sidebarMainStat.$inject = ['DiscusSharedSvc', '$rootScope'];
function sidebarMainStat ( DiscusSharedSvc, $rootScope ) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/sidebar-main-stat.html",
    link: function($scope, $element, $attrs) {
      if ( !~$rootScope.user.portalData.role.indexOf('all') ) {
        $scope.denied = true;
        return;
      }

      $scope.getBlockState = function(blockName, change) {
        return DiscusSharedSvc.getBlockState(blockName, change);
      }
    }
  };
};

function votings ( $compile, $log) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/votings.html",
    controller: function($scope, $attrs, $rootScope) {
      var list = $rootScope.listDiscus || new ListDiscus();
      
      list.getVoting( function (docs) {
        $scope.votes = docs;
      });
    }
  };
};

topLikes.$inject = ['$compile', '$log', 'Stat', 'Discus'];
function topLikes($compile, $log, Stat, Discus) {
  return {
    restrict: 'E',
    templateUrl: "/bundles/tretoportal/partials/directs/top-likes.html",
    controller: function($scope, $attrs, $rootScope, Stat) {
      $scope.stat = Stat;
      $scope.discus = Discus;

      $scope.loadingStat = {
        topLikes: false,
        topDislikes: false
      };
      
      var getUserShortNames = function(likeArray) {
        likeArray.forEach(function(like) {
          if (!like.sendShareFrom) {
            var name = $rootScope && $rootScope.usersAll && $rootScope.usersAll[like.author] ? $rootScope.usersAll[like.author].name : like.author;
            var name = name.split(' ');
            like.shortName = name[0] + (name.length > 1 ? ' ' + name[1].split('')[0] + '.' : '');
          } else {
            var name = like.AuthorRus.split(' ');
            if (name.length < 2) like.shortName = like.AuthorRus;
            else {
              name[1] = name[1].slice(0,1) + '.';
              like.shortName = name.join(' ');
            }
          }
        });
      }
      
      $scope.stat.topList = $scope.stat.topList || {};
      $scope.topList = $scope.stat.topList;
      
      $scope.loadingStat.topLikes = true;
      $scope.stat.getLikes(function(likes) {
        $scope.stat.topList.likes = likes;
        getUserShortNames($scope.stat.topList.likes);
        $scope.loadingStat.topLikes = false;
      });

      $scope.loadingStat.topDislikes = true;
      $scope.stat.getDislikes(function(dislikes) {
        $scope.stat.topList.dislikes = dislikes;
        getUserShortNames($scope.stat.topList.dislikes);
        $scope.loadingStat.topDislikes = false;
      });
    }
  };
};

function gallery ($compile, $log) {
  return {
    restrict: 'E',
    scope: {
      titletext: '=',
      titlelink: '=',
      titlesidetext: '=',
      link: '=',
      description: '=',
      source: '=',
    },
    templateUrl: "/bundles/tretoportal/partials/directs/gallery.html"
  };
};

function informers ($compile, $log) {
  return {
    restrict: 'E',
    scope: {},
    templateUrl: "/bundles/tretoportal/partials/directs/informers.html"
  };
};

function lineGraph(Graph, $timeout) {
  return {
      restrict: 'E',
      replace: true,
      template: '<div class="line-graph-wrap"></div>',
      scope: {
        graphs: '=',
        mode: '=?'
      },
      link: function($scope, element, $attrs) {
        var graph = Graph;
        var width = element.width();
        var height = 420;
        var margin = 40;
        var graphWidth, graphHeight;
        var maxX, maxY;

        var drawFunc = function() {
          switch ($scope.mode) {
            case 'line': return 'getLine';
            case 'trendline': return 'getTrendline';
            case 'curve_and_trend': return 'getCurveAndTrend';
            case 'curve':
            default: return 'getCurve';
          };
        };

        var scaleX = function(x) {
          return Math.round(margin + graphWidth * x / maxX);
        };

        var scaleY = function(y) {
          return Math.round(height - (margin + graphHeight * y / maxY));
        };

        var getGrid = function() {
          var grid = angular.element('<g class="grid">');
          grid.append('<rect x="0" y="0" width="'+width+'" height="'+height+'" class="bg"/>');
          var xMinPixelsPerGridLine = 100;
          var pxPerXGrid = 100;
          var gridEveryX = 1;
          while (Math.floor(graphWidth / maxX * gridEveryX) < pxPerXGrid) {
            gridEveryX++;
          }

          for (var i = 5; i >= 0; i--) {
            var y = maxY / 5 * i;
            grid.append('<line x1="'+scaleX(0)+'" y1="'+scaleY(y)+'" x2="'+scaleX(maxX)+'" y2="'+scaleY(y)+'"'+(i === 0 ? ' class="axis"' : '')+'/>');
            if (i !== 0) {
              grid.append('<text x="'+(margin-5)+'" y="'+(scaleY(y)+3)+'" class="y-axis">'+y.toFixed(maxY < 5 ? 1 : 0)+'</text>');
            }
          }

          for (var i = 0; i <= maxX; i++) {
            if (i % gridEveryX === 0 || i === maxX) {
              var x = i;
              var xLabelIndex = $scope.graphs.length && $scope.graphs[0].points.length === 1 ? 0 : x;
              var xLabel = $scope.graphs.length && $scope.graphs[0].points[xLabelIndex].xLabel ? $scope.graphs[0].points[xLabelIndex].xLabel : ''+x;
              grid.append('<line x1="'+scaleX(x)+'" y1="'+scaleY(0)+'" x2="'+scaleX(x)+'" y2="'+scaleY(maxY)+'"'+(i === 0 ? ' class="axis"' : '')+'/>');
              grid.append([
                '<text x="'+scaleX(x)+'" y="'+(height-margin+5+14)+'" class="x-axis">',
                (~xLabel.indexOf('-') ?
                  [
                    '<tspan x="'+scaleX(x)+'" dy="0">С '+xLabel.split('-')[0]+'</tspan>',
                    '<tspan x="'+scaleX(x)+'" dy="1.3em">По '+xLabel.split('-')[1]+'</tspan>'
                  ].join('') :
                  xLabel
                ),
                '</text>'
              ].join(''));
            }
          }

          return grid;
        };

        var getGraphs = function() {
          var graphs = angular.element('<g class="graph-group">');
          for (var i = 0; i < $scope.graphs.length; i++) {
            var points = $scope.graphs[i].points.map(function(value) {
              return {x: scaleX(value.x), y: scaleY(value.y)};
            });
            if (points.length === 1 && maxX === 1) {
              points.push({x: scaleX(1), y: points[0].y});
            }
            var g = angular.element('<g class="graph" stroke="'+$scope.graphs[i].color+'">');
            g.append('<path d="'+graph[drawFunc()](points, scaleY(0))+'" class="graph"/>');
            for (var j = 0; j < points.length; j++) {
              var knot = angular.element('<g class="knot">');
              knot.append('<circle cx="'+points[j].x+'" cy="'+points[j].y+'" r="4">');

              var label = angular.element('<g class="knot-label">');
              var xLabelNotFound = j < $scope.graphs[i].points.length ? 0 : 1;
              var xLabel = $scope.graphs[i].points[j-xLabelNotFound].xLabel;
              if ( ~xLabel.indexOf('-') ) {
                var xLabelArr = xLabel.split('-');
                xLabel = '';
                for (var k = 0; k < xLabelArr[0].length; k++) {
                  xLabel += xLabelArr[0].split('.')[k] !== xLabelArr[1].split('.')[k] ? ((xLabel !== '' ? '.' : '') + xLabelArr[0].split('.')[k]) : '';
                }
                xLabel += '-'+xLabelArr[1];
              }

              var labelX = points[j].x - (j === 0 ? 35 : (j === maxX ? 65 : 50));
              var labelY = points[j].y - 45 - Math.min(0, points[j].y - 50);
              label.append('<rect x="'+labelX+'" y="'+labelY+'" width="100" height="35" rx="4" ry="4">');
              label.append([
                '<text x="'+(labelX + 4)+'" y="'+labelY+'">',
                  '<tspan x="'+(labelX + 4)+'" dy="1.3em">',
                    $scope.graphs[i].legend,
                  '</tspan>',
                  '<tspan x="'+(labelX + 4)+'" dy="1.3em">',
                    xLabel + ': ' + $scope.graphs[i].points[j-xLabelNotFound].y,
                  '</tspan>',
                '</text>'
              ].join(''));

              knot.append(label);
              g.append(knot);
            }
            graphs.append(g);
          }
          return graphs;
        };

        var getLineGraph = function() {
          var svg = angular.element('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '+width+' '+height+'" class="graph line-graph">');
          svg.append( getGrid() );
          svg.append( getGraphs() );
          return $.parseXML(svg.get(0).outerHTML);
        };

        var getSize = function() {
          width = element.width();
          graphWidth = width - 2 * margin;
          graphHeight = height - 2 * margin;
          return graphWidth > 0 && graphHeight > 0;
        };

        var getLimits = function() {
          maxX = 1;
          maxY = 1;
          for (var i = 0; i < $scope.graphs.length; i++) {
            for (var j = 0; j < $scope.graphs[i].points.length; j++) {
              maxX = Math.max(maxX, $scope.graphs[i].points[j].x);
              maxY = Math.max(maxY, $scope.graphs[i].points[j].y);
            }
          }
        };
        
        var draw = function() {
          $timeout(function() {
            if ( !$scope.graphs.length || !getSize() ) return;
            getLimits();
            var svg = getLineGraph();
            element.html(svg.documentElement);
          }, 0);
        };

        element.on('mouseenter.lineGraph', 'g.graph, g.knot', function(e) {
          $(this).parent().append(this);
        });
        $(window).on('resize.lineGraph', draw);
        $scope.$watchCollection('graphs', function(newValue, oldValue) {
          if (newValue !== oldValue) draw();
        });
        $scope.$on('$destroy', function() {
          $(window).off('.lineGraph');
        });
      }
  };
};

mainStat.$inject = ['Stat', '$filter'];
function mainStat(Stat, $filter) {
  return {
    restrict: 'E',
    replace: true,
    scope: true,
    templateUrl: '/bundles/tretoportal/partials/directs/main-stat.html',
    link: function($scope, $element, $attrs) {
      $scope.stat = Stat;
      $scope.showBlock = {
        employers: 'employers' in $attrs || 'allStat' in $attrs,
        employedFired: 'employedFired' in $attrs || 'allStat' in $attrs,
        messagesTotalCount: 'messagesTotalCount' in $attrs || 'allStat' in $attrs,
        loggedTodayCount: 'loggedTodayCount' in $attrs || 'allStat' in $attrs,
        topChatterboxes: 'topChatterboxes' in $attrs || 'allStat' in $attrs,
        topSilent: 'topSilent' in $attrs || 'allStat' in $attrs,
        topInTime: 'topInTime' in $attrs || 'allStat' in $attrs,
        topOverdue: 'topOverdue' in $attrs || 'allStat' in $attrs,
        topFastWorkers: 'topFastWorkers' in $attrs || 'allStat' in $attrs,
        topSlowWorkers: 'topSlowWorkers' in $attrs || 'allStat' in $attrs,
        bmometer: 'bmometer' in $attrs || 'allStat' in $attrs
      }
      $scope.loading = true;
      Stat.getMainStat(function(data) {
        $scope.mainStat = data;

        $scope.plural = Plural;

        // This code hides (removes from displayed) entries for portalrobot
        var removePortalRobotFromList = function(list) {
          if ( !angular.isArray(list) ) return;
          for (var i = list.length - 1; i >= 0; i--) {
            if ( !angular.isObject(list[i]) ) continue;
            if ( list[i].login && list[i].login === 'portalrobot' ) list.splice(i, 1);
          }
        };
        removePortalRobotFromList($scope.mainStat.chatterboxes);
        for (var list in $scope.mainStat.fastSlowWorkers)
          removePortalRobotFromList($scope.mainStat.fastSlowWorkers[list]);

        for (var user in $scope.mainStat.fastSlowWorkers.solved) {
          var entry = $scope.mainStat.fastSlowWorkers.solved[user];
          var S = 1;
          var M = 60 * S;
          var H = 60 * M;
          var D = 24 * H;
          var time = entry.avgSolveTime;
          var days = Math.floor(time / D);
          time -= days * D;
          var hours = Math.floor(time / H);
          time -= hours * H;
          var minutes = Math.floor(time / M);
          time -= minutes * M;
          var seconds = Math.floor(time / S);
          time -= seconds * S;
          entry.time = $filter('pluralize')(days, 'day') + ' ' + ('0'+hours).slice(-2) +':'+ ('0'+minutes).slice(-2) +':'+ ('0'+seconds).slice(-2);
        }

        var getLastDate = function(array) {
          var lastDate = null;
          for (var date in array) {
            if (lastDate < date)
              lastDate = date;
          }
          return lastDate;
        };

        $scope.firedLastDate = getLastDate($scope.mainStat.users.firedByDate);
        $scope.employedLastDate = getLastDate($scope.mainStat.users.employedByDate);

        $scope.topList = {
          chatterboxes: $scope.mainStat.chatterboxes.slice(0, 3),
          silent: $scope.mainStat.chatterboxes.slice(-3),
          inTime: $scope.mainStat.fastSlowWorkers.overdue.slice(-5),
          overdue: $scope.mainStat.fastSlowWorkers.overdue.slice(0, 5),
          fastWorkers: $scope.mainStat.fastSlowWorkers.solved.slice(-5),
          slowWorkers: $scope.mainStat.fastSlowWorkers.solved.slice(0, 5)
        }

        $scope.loading = false;
      });
    }
  };
};

bindCompiledHtml.$inject = ['$compile'];
function bindCompiledHtml($compile) {
  return {
    template: '<div></div>',
    scope: {
      rawHtml: '=bindCompiledHtml'
    },
    link: function($scope, $element, $attrs) {
      var placeholder = '<span>'+('htmlPlaceholder' in $attrs ? $attrs.htmlPlaceholder : '')+'</span>';
      $scope.$watch('rawHtml', function(value) {
        if (!value) value = placeholder;
        var newElem = $compile(value)($scope.$parent);
        $element.contents().remove();
        $element.append(newElem);
      });
    }
  };
};

function refresher() {
  return {
    transclude: true,
    controller: function($scope, $transclude,
                         $attrs, $element) {
      var childScope;

      $scope.$watch($attrs.condition, function(value) {
        $element.empty();
        if (childScope) {
          childScope.$destroy();
          childScope = null;
        }

        $transclude(function(clone, newScope) {
          childScope = newScope;
          $element.append(clone);
        });
      });
    }
  };
};

taskDateDifficultyModal.$inject = ['TretoDateTime'];
function taskDateDifficultyModal(TretoDateTime) {
  return {
    restrict: 'E',
    replace: true,
    scope: {
      date: '=dateModel',
      difficulty: '=difficultyModel',
      difficultyOptions: '=',
      show: '=',
      onSave: '&'
    },
    templateUrl: '/bundles/tretoportal/partials/directs/task-date-difficulty-modal.html',
    link: function($scope, $element, $attrs) {
      $scope.today = new Date();
      $scope.dt = {
        date: typeof $scope.date === 'string' ? TretoDateTime.iso8601.toDate($scope.date) : $scope.date,
      };

      $scope.save = function() {
        $scope.onSave({dEndDate: $scope.dt.date});
        $scope.show = false;
      };

      $scope.hideModal = function() {
        $scope.show = false;
      };

      $scope.setDifficulty = function(newDifficulty) {
        $scope.difficulty = newDifficulty;
      };
    }
  };
};

involvement.$inject = ['$rootScope', 'Popup', 'TretoDateTime', '$http', 'Socket', '$timeout'];
function involvement($rootScope, Popup, TretoDateTime, $http, Socket, $timeout) {
  return {
    restrict: 'E',
    replace: true,
    scope: {
      username: '='
    },
    templateUrl: '/bundles/tretoportal/partials/directs/involvement.html',
    link: function($scope, $element, $attrs) {
      $scope.profileMode = 'profileMode' in $attrs;
      $scope.showEdit = false;
      $scope.saving = false;

      var user = $rootScope.users[$scope.username];
      $scope.user = user;
      
      if (!user) return;

      $scope.edit = {};

      var renewTodayDateTimout = null;
      function renewTodayDate() {
        $scope.today = new Date();
        renewTodayDateTimout = $timeout(renewTodayDate, 60000);
        $scope.involvement = (typeof $scope.expireDate !== 'undefined' ||
                              $scope.expireDate <= TretoDateTime.iso8601.fromDate()) ?
                                user.involvement :
                                100;
      };

      var init = function() {
        $scope.expireDate = user.involvementExpireDate;

        $timeout.cancel(renewTodayDateTimout);
        renewTodayDate();
        if (!$scope.showEdit) {
          $scope.edit.date = $scope.expireDate !== '' ?
                              TretoDateTime.iso8601.toDate($scope.expireDate) :
                              undefined;
          $scope.edit.involvement = $scope.involvement;
        }
      }
      init();
      var reinit = init;

      $scope.showEditWnd = function() {
        $scope.showEdit = true;
      };

      $scope.hideEditWnd = function() {
        $scope.showEdit = false;
      };

      $scope.toggleEditWnd = function() {
        $scope.showEdit = !$scope.showEdit;
      };

      $scope.save = function() {
        $scope.saving = true;
        var data = {
          involvement: $scope.edit.involvement,
          involvementExpireDate: typeof $scope.edit.date !== 'undefined' ?
                                    TretoDateTime.iso8601.fromDate($scope.edit.date) : ''
        };
        $http({method: 'POST', url: 'api/user/save-involvement', data: data })
            .then(function(response) {
              if(response.data.success) {
                $scope.saving = false;
                $scope.showEdit = false;
                user.involvement = response.data.involvement;
                user.involvementExpireDate = response.data.involvementExpireDate;

                if ($scope.username === $rootScope.user.username) {
                  $rootScope.user.involvement = user.involvement;
                  $rootScope.user.involvementExpireDate = user.involvementExpireDate;
                }

                for (var i = 0; i < $rootScope.usersArr.length; i++) {
                  if ($rootScope.usersArr[i].id === $scope.username) {
                    $rootScope.usersArr[i].involvement = user.involvement;
                    $rootScope.usersArr[i].involvementExpireDate = user.involvementExpireDate;
                    break;
                  }
                }

                reinit();

                Socket.get(function(socket) {
                  socket.emit("refreshUsers", null);
                });
              } else {
                showSavingError(response.data.message);
              }
            }, function(response) { showSavingError(response.data.message); });
      };

      $scope.cancel = function() {
        $scope.showEdit = false;
        reinit();
      };

      function showSavingError(msg) {
        $scope.saving = false;
        new Popup('Ошибка при сохранении вовлечённости ', msg, 'error');
      };

      $scope.$on('$destroy', function() {
        $timeout.cancel(renewTodayDateTimout);
      });
    }
  }
};

scrollOnDiscusLinks.$inject = ['Scroll'];
function scrollOnDiscusLinks(Scroll) {
  return {
    restrict: 'A',
    replace: false,
    scope: false,
    link: function($scope, $element, $attrs) {
      $element.on('click.scrollOnDiscusLinks touch.scrollOnDiscusLinks', 'a', function() {
        var href = this.getAttribute('href');
        if ( (/#\/discus\/(-|\w)+\/$/ig).test(href) ) {
          var hrefArr = href.split('/');
          var unid = hrefArr[ hrefArr.length - 2 ];
          if ( Scroll.intoView('#'+unid) ) return false;
        }
      });

      $scope.$on('$destroy', function() {
        $element.off('.scrollOnDiscusLinks');
      })
    }
  };
};

function taskAdditions() {
  return {
    restrict: 'E',
    replace: true,
    scope: true,
    templateUrl: '/bundles/tretoportal/partials/doc_templ/menu/docs-list.html',
    link: function ($scope, $element, $attributes) {
      $scope.model = $scope.$eval($attributes.model);
      $scope.independent = $scope.$eval($attributes.independent);
    }
  };
};

logClick.$inject = ['Stat'];
function logClick(Stat) {
  return {
    restrict: 'A',
    replace: false,
    scope: false,
    link: function($scope, $element, $attrs) {
      if ( Stat.monthlyClickLog.isUserClickWatcher() ) {
        Stat.getMonthlyClickLog(function() {
          var btn = $attrs.logClick.replace(/[\.\$]/g, '');
          var count = Stat.monthlyClickLog.data[btn] || 0;
          $attrs.$set('title', count+' кликов за месяц');
          $attrs.$set('ngTitle', count+' кликов за месяц');
        });
      }

      $element.on('click.logClick touch.logClick', function(e) {
        Stat.logClick($attrs.logClick.replace(/[\.\$]/g, ''));
      });

      $scope.$on('$destroy', function() {
        $element.off('.logClick');
      });
    }
  };
};

function lightgallery($rootScope){
  return {
    restrict: 'A',
    link: function(scope, element, attrs) {
      setTimeout(function(){
        if ($rootScope.gallery) $rootScope.gallery.data('lightGallery').destroy(true);
        $rootScope.gallery = $("body").lightGallery({hideBarsDelay: 1000, selector: '.lightgallery-image'});
      }, 600);
    } 
  };
};

logClickTmceToolbarObserver.$inject = ['Stat'];
function logClickTmceToolbarObserver(Stat) {
  return {
    restrict: 'A',
    replace: false,
    scope: false,
    link: function($scope, $element, $attrs) {
      var btns = {
        'mce-i-bold'          : 'Оформление текста::Кнопка <Полужирный> в инструмнтах оформления текста',
        'mce-i-italic'        : 'Оформление текста::Кнопка <Курсив> в инструмнтах оформления текста',
        'mce-i-underline'     : 'Оформление текста::Кнопка <Подчёркнутый> в инструмнтах оформления текста',
        'mce-i-strikethrough' : 'Оформление текста::Кнопка <Зачёркнутый> в инструмнтах оформления текста',
        'mce-i-removeformat'  : 'Оформление текста::Кнопка <Очистить формат> в инструмнтах оформления текста',
        'mce-i-bullist'       : 'Оформление текста::Кнопка <Маркированный список> в инструмнтах оформления текста',
        'mce-i-numlist'       : 'Оформление текста::Кнопка <Нумерованный список> в инструмнтах оформления текста',
        'mce-i-forecolor'     : 'Оформление текста::Кнопка <Цвет текста> в инструмнтах оформления текста',
        'mce-i-backcolor'     : 'Оформление текста::Кнопка <Цвет фона> в инструмнтах оформления текста',
        'mce-i-link'          : 'Оформление текста::Кнопка <Вставить\редактировать ссылку> в инструмнтах оформления текста',
        'mce-i-media'         : 'Оформление текста::Кнопка <Вставить\редактировать видео> в инструмнтах оформления текста',
        'mce-i-table'         : 'Оформление текста::Кнопка <Таблица> в инструмнтах оформления текста',
        'mce-i-emoticons'     : 'Оформление текста::Кнопка <Добавить смайл> в инструмнтах оформления текста'
      };

      var toolbars = {
        'mytoolbar'           : ' во всплывающем окне',
        'fastReplyToolbar'    : ' в блоке быстрого ответа'
      };

      $element.on('click.logClickTmceToolbarObserver touch.logClickTmceToolbarObserver', '.mce-btn', function(e) {
        var icon = e.currentTarget.querySelector('.mce-ico');
        if (icon) {
          var toolbar = e.currentTarget.parentNode;
          var toolbarText = '';
          while ( !toolbarText && toolbar.tagName !== 'BODY' ) {
            if (toolbar.id in toolbars) toolbarText = toolbars[toolbar.id];
            else toolbar = toolbar.parentNode;
          }
          var classes = icon.className.split(' ');
          for (var i = 0; i < classes.length; i++)
            if ( classes[i] in btns )
              Stat.logClick( btns[classes[i]] + toolbarText );
        }
      });

      $scope.$on('$destroy', function() {
        $element.off('.logClickTmceToolbarObserver');
      });
    }
  };
};

function tagsList() {
  return {
    restrict: 'E',
    scope: {
      model: '=',
    },
    templateUrl: "/bundles/tretoportal/partials/directs/tagsList.html",
  }
}

function otherCommand() {
  return {
    restrict: 'E',
    templateUrl: '/bundles/tretoportal/partials/otherCommand.html',
    controller: function($rootScope, $scope, UserSettings, $timeout) {
      $scope.checkedCommand = UserSettings.getCheckedCommand();

      $scope.allCommandCheckbox = false;
      $scope.selectedShareCommand = {};
      for(var i in $scope.checkedCommand){
        $scope.selectedShareCommand[$scope.checkedCommand[i]] = true;
      }
      $scope.allCommandCount = Object.keys($rootScope.shareUsers).length;

      $scope.setSelectedCommand = function(){
        var selected = [];
        for(var domain in $scope.selectedShareCommand){
          if($scope.selectedShareCommand[domain]){
            selected.push(domain);
          }
        }

        UserSettings.setCheckedCommand(selected, function(){
          $scope.checkedCommand = selected;
          $rootScope.rebuildUsersArray();
          $scope.showCommandSelect = false;
        });
      };

      $scope.recountCheckedCommand = function(){
        var count = 0;
        for(var i in $scope.selectedShareCommand){
          if($scope.selectedShareCommand[i]){
            count++;
          }
        }

        return count;
      };

      $scope.selectAllCommand = function(){
        for(var i in $scope.selectedShareCommand){
          $scope.selectedShareCommand[i] = !$scope.allCommandCheckbox;
        }
      };
    }
  };
};

docDiff.$inject = ['$compile', '$http'];
function docDiff($compile, $http) {
  return{
    restrict: 'E',
    scope: true,
    templateUrl: '/bundles/tretoportal/partials/directs/doc-diff.html',
    link: function(scope, elem, attr, $rootScope) {
      scope.status = 'loading';
      
      $http({method:'POST', url: "api/discussion/diff/"+scope.doc.unid})
      .then(function(response) {
        if (!response.data.success) scope.status = 'error';
        else {
          scope.oldVersions = response.data.oldVersions;
          
          if (!scope.oldVersions || scope.oldVersions.length == 0) scope.status = 'empty';
          else {
            for(var i = 0; i < scope.oldVersions.length; i++) {
              if (scope.oldVersions[i + 1]) scope.oldVersions[i].newVersion = scope.oldVersions[i + 1].doc;
              else scope.oldVersions[i].newVersion = scope.doc;
            }
            
            for(var i = 0; i < scope.oldVersions.length; i++) {
              if (scope.oldVersions[i].doc.attachments || scope.oldVersions[i].newVersion.attachments) {
                var messyOldAttaches = scope.oldVersions[i].doc.attachments;
                var oldAttaches = [];
                for(var j in messyOldAttaches) {oldAttaches.push(messyOldAttaches[j][0].link);}
                
                var messyNewAttaches = scope.oldVersions[i].newVersion.attachments;
                var newAttaches = [];
                for(var j in messyNewAttaches) {newAttaches.push(messyNewAttaches[j][0].link);}
                
                var diffDeleted = $(oldAttaches).not(newAttaches).get();
                scope.oldVersions[i].attachesRemoved = [];
                var diffAdded = $(newAttaches).not(oldAttaches).get();
                scope.oldVersions[i].attachesAdded = [];
                
                for(var j in messyOldAttaches) {
                  if (diffDeleted.indexOf(messyOldAttaches[j][0].link) > -1)
                    scope.oldVersions[i].attachesRemoved.push(messyOldAttaches[j]);
                }
                for(var j in messyNewAttaches) {
                  if (diffAdded.indexOf(messyNewAttaches[j][0].link) > -1)
                    scope.oldVersions[i].attachesAdded.push(messyNewAttaches[j]);
                }
              }
            }
            
            scope.status = 'ok';
          }
        }
      });
    }
  }
};

function departmentsTeams() {
  return {
    restrict: 'E',
    templateUrl: '/bundles/tretoportal/partials/participantsCommand.html',
    controller: function ($scope, $element, $attrs) {

      // Добавление/удаление данных (домен, логин) из объекта userInfo при удалении/добавлении данных в объект ngdiscus.sharePushArray
      $scope.updateUserInfo = function() {
        var user = [];
        var matches = true;
        $scope.$parent.userInfo.length = 0;
        for(var domain in $scope.ngdiscus.sharePushArray) {
          for(var i=0; i < $scope.ngdiscus.sharePushArray[domain].length; i++) {
            var shareLogin = $scope.ngdiscus.sharePushArray[domain][i];
            user = {domain: domain, login: shareLogin};
            $scope.$parent.userInfo.push(user);
          }
        }
      }
    }
  }
};

function addTeamsdepartments($window) {
  return {
    restrict: 'E',
    scope: {
      ngdiscus: '=',
      showAddTeamsdepartments: '=',
    },
    controller: function($scope){
      $scope.windowWidth = angular.element($window).width();
    },
    templateUrl: "/bundles/tretoportal/partials/doc_templ/common/select-group-participants-popup.html",
  }
};

// добавление/скрытие плавного градиента на текст в окне со скроллом
function scrollList() {
  return {
    restrict: 'A',

    controller: function($scope, $element, $attrs, $timeout) {
      var raw = $element.find('ul');
      var scrolledDown = false;
      var maxHeigthList = 320;

      raw.on('scroll', function(){
        if (raw[0].scrollTop + raw[0].offsetHeight >= raw[0].scrollHeight) {
          $element.removeClass('show-gradient');
          scrolledDown = true;
          $scope.$apply(attrs.scrolly);
        }
        if( scrolledDown && raw[0].scrollTop + raw[0].offsetHeight < raw[0].scrollHeight ) {
          $element.addClass('show-gradient');
          scrolledDown = false;
          $scope.$apply(attrs.scrolly);
        }
      });

      $scope.$watch(function(scope) {
        return $attrs.ariaHidden
      },
      function(scope) {
        if( $attrs.ariaHidden == 'true' ) {
          $element.removeClass('show-gradient');
        }
        else {
          $timeout(function() {
            if ( raw[0].scrollHeight >= maxHeigthList ) {
              $element.addClass('show-gradient');
            }
          });
        }
      }
      );
    }
  }
};