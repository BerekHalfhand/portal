portalApp
  .controller('headerCtrl', headerCtrl)
  .controller('indexCtrl', indexCtrl)
  .controller('modalMail', modalMail)
  .controller('popupCtrl', popupCtrl)
  .controller('tasksCtrl', tasksCtrl)
  .controller('taskSearchCtrl', taskSearchCtrl)
  .controller('discusListCtrl', discusListCtrl)
  .controller('discusCtrl', discusCtrl)
  .controller('discusParticipantsCtrl', discusParticipantsCtrl)
  .controller('discusLinkedToCtrl', discusLinkedToCtrl)
  .controller('inviteGuestCtrl', inviteGuestCtrl)
  .controller('notifCtrl', notifCtrl)
  .controller('adminCtrl', adminCtrl)
  .controller('adminDictionaryCtrl', adminDictionaryCtrl)
  .controller('historyCtrl', historyCtrl)
  .controller('serpCtrl', serpCtrl)
  .controller('voteCtrl', voteCtrl)
  .controller('favoritesCtrl', favoritesCtrl)
  .controller('linksCtrl', linksCtrl)
  .controller('filePreviewCtrl', filePreviewCtrl)
  .controller('workPlanCtrl', workPlanCtrl)
  .controller('tagsPageCtrl', tagsPageCtrl)
  .controller('adaptationCtrl', adaptationCtrl)
  .controller('votingListCtrl', votingListCtrl)
  .controller('discountCtrl', discountCtrl)
  .controller('changeLogCtrl', changeLogCtrl)
  .controller('criterionListCtrl', criterionListCtrl)
  .controller('criterionCtrl', criterionCtrl)
  .controller('criterionCreateCtrl', criterionCreateCtrl)
  .controller('questionaryCtrl', questionaryCtrl)
  .controller('questionaryCreateCtrl', questionaryCreateCtrl)
  .controller('questionCreateCtrl', questionCreateCtrl)
  .controller('questionCtrl',questionCtrl)
  .controller('showMessagesByUserCtrl',showMessagesByUserCtrl)
  .controller('c1logsCtrl', c1logsCtrl)
  .controller('teCollectionList', teCollectionList)
  .controller('calendarCtrl', calendarCtrl)
  .controller('emplFormCtrl', emplFormCtrl)
  .controller('portalSettingsCtrl', portalSettingsCtrl)
  .controller('factoryHolidayCtrl', factoryHolidayCtrl)
  .controller('bottomSheetCtrl', bottomSheetCtrl)
  .controller('statCtrl', statCtrl)
  .controller('messagesStatCtrl', messagesStatCtrl)
  .controller('likeStatCtrl', likeStatCtrl)
  .controller('userStatCtrl', userStatCtrl)
  .controller('popularThemesStatCtrl', popularThemesStatCtrl)
  .controller('clickStatCtrl', clickStatCtrl)
  ;

function headerCtrl($scope, $http, $state, $timeout, $parse, $window, $filter, $cookies, $log, Dictionary, Mail, $rootScope, Tasks, Tags, Favorites, History, Discus, Socket, Security, Auth, Popup, BatchHttp, $location, UserSettings) {
  if (typeof Notification != "undefined" && Notification.permission.toLowerCase() === "default" ) {
    Notification.requestPermission();
  }

  var header = angular.element('#header');
  var wind = angular.element($window);
  setWindowSize = function() {
    $rootScope.windowHeight = header.height();
    $rootScope.windowWidth = header.width();
    if(!$scope.$$phase) $scope.$apply();
  }
  setWindowSize();
  wind.on('resize', _.debounce(setWindowSize, 500));

  $rootScope.show = {create: {}, menuCreate: false, Search: false, menuUser: false,};
  $scope.chatUnreadMessages = 0;
  $scope.new_message = document.getElementById('new_message');
  $scope.commonDiscus = Discus;
  $scope.count = {};
  $scope.menus = {links: {}, tasks: {}, history: {}, favorites: {}, tags: {}};

  var auth = new Auth();
  $scope.openChatWindow = function(checkCook) {
    if(!$scope.$$phase) $scope.$apply();
    if (!checkCook || !($cookies.get('chatWindow') && $cookies.get('chatWindow') == "true")){
      $scope.$chatWindow = window.open($state.href('chat'), 'chat',
      'left=0,top=0,resizable=yes,scrollbars=yes,status=yes,width=400,height=600,menubar=no,toolbar=no,location=no,directories=no');
    }
  };
  // if (!$cookies.get('chatWindow')){
  //   $scope.openChatWindow(true);
  // }

  $rootScope.dateRangePickerConf = {
    'locale': {
      separator: ' - ',
      format: 'DD.MM.YYYY',
      daysOfWeek: ["В", "П", "В", "С", "Ч", "П", "С"],
      monthNames: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
      firstDay: 1
    },
    buttonClasses:'btn btn-sm wp-button',
    applyClass:'btn-info',
    cancelClass:''
  };

  $window.setUnreadMessages = function(n) {
    $scope.chatUnreadMessages = n;
    $scope.$apply();
  }
  $window.updateRootUsers = function(users, usersArr){
    $rootScope.users = users;
    $rootScope.usersArr = usersArr;
  }

  $scope.blurClick = function() {
//     $log.log('headerCtrl.blurClick');
    $rootScope.show.menuCreate = false;
    $rootScope.show.Search = false;
    $rootScope.show.menuUser = false;
    $rootScope.show.inputSearch = false;
  }

  $scope.showSearchParameters = function() {
    $rootScope.show.Search = true;
    $rootScope.show.menuCreate = false;
    $rootScope.show.menuUser = false;
  }

  $scope.searchClose = function () {
    $scope.blurClick();
  }
  $scope.searchOpen = function () {
    $rootScope.show.inputSearch = true;
  } 

  $rootScope.$on('$stateChangeStart', function() {
    for (var menu in $scope.menus) {
      $timeout.cancel($scope.menus[menu].hideTimeout);
      $scope.menus[menu].isopen = false;
      $scope.menus[menu].busy = false;
    }
    $('#bot-menu td').removeClass('open');
  });
  setTime = function() {
    var date = $rootScope.serverTime || new Date();
    $scope.timeMoscow = ('0'+((date.getUTCHours()+3)%24)).slice(-2)+':'+('0' + date.getMinutes()).slice(-2);
    $scope.timeMilan = ('0'+((date.getUTCHours()+2)%24)).slice(-2)+':'+('0' + date.getMinutes()).slice(-2);
    $rootScope.serverTimeMsk = new Date(date.getTime() + (date.getTimezoneOffset() + 180) * 60000);
    $rootScope.$applyAsync();
  }
  incrementTime = function() {
    $rootScope.serverTime = new Date($rootScope.serverTime.getTime() + 1000);
    if ($rootScope.serverTime.getSeconds() == 0)
      setTime();
  };

  countRelevantN = function(Notif) {
    if (!Notif) {
      Notif = angular.copy($rootScope.notif);
      Notif = $filter('relevantNotifications')(Notif);
      $scope.Notifications = angular.copy(Notif);
    }

    var res = 0;
    for(var i in Notif) { res++; }
    return res;
  };

  //loading Notif
  $scope.loadNotif = function() {
    $log.log('headerCtrl.loadNotif');
    BatchHttp({method: 'POST', url: 'api/notif/load/'+$rootScope.user.username/*, data: { 'tag': tag } */})
    .then(function(response) {
      if(response.data.success) {
        $rootScope.notif = response.data.notif;
        $scope.count.Notif = countRelevantN();
        $log.info('headerCtrl.loadNotif - success, count = '+$scope.count.Notif);
      } else {
        new Popup('Discus', response.data.message, 'error');
      }
    }, httpErrorHandler);
  }
  if (typeof $rootScope.notif == "undefined") $scope.loadNotif();
  else if (typeof $scope.Notifications == "undefined") $scope.count.Notif = countRelevantN();

  setTime();

  $scope.getShareUsersAsArray = function (byCheckedCommand) {
    var result = [];
    var userExist = function(domain, login){
      for(var inc in result){
        if(result[inc].domain == domain && result[inc].id == login){
          return inc;
        }
      }

      return false;
    };

    var checkedCommand = UserSettings.getCheckedCommand();

    for(var domain in $rootScope.shareUsers){
      var sections = $rootScope.shareUsers[domain].data;
      for(var section in sections){
        for(var i in sections[section].data){
          var user = sections[section].data[i];

          var exist = userExist(domain, user.username);
          if(exist !== false){
            result[exist].section.push(section);
          }
          else if(!byCheckedCommand || checkedCommand.indexOf(domain) != -1) {
            result.push({
              section:[section],
              domain:domain,
              id:'share_'+user.username,
              name: user.LastName + " " + user.name,
              LastName:user.LastName,
              MiddleName:user.MiddleName,
              WorkGroup:user.WorkGroup,
              username:user.username
            });
          }
        }
      }
    }

    return result;
  };

  Socket.get(function(socket) {
    socket.on("user_list", function(users) {
      $rootScope.users = {};
      $rootScope.usersArr = [];
      angular.forEach(users, function(usr) {
        $rootScope.users[usr.data.username] = {
          phone: usr.data.portalData.ContactWithMobileFhone?usr.data.portalData.ContactWithMobileFhone[0]:"",
          status: usr.status,
          name: usr.data.portalData.LastName + " " + usr.data.portalData.name,
          id: usr.data.username,
          unread_messages: 0,
          sockets: usr.sockets,
          section: usr.data.portalData.section,
          WorkGroup: usr.data.portalData.WorkGroup,
          involvement: usr.data.involvement,
          involvementExpireDate: usr.data.involvementExpireDate
        };
        $rootScope.usersArr.push($rootScope.users[usr.data.username]);
      });

      $rootScope.rebuildUsersArray();
    });

    $rootScope.rebuildUsersArray = function(){
      var newUserArray = [];
      for(var user in $rootScope.usersArr){
        if(!$rootScope.usersArr[user].domain){
          newUserArray.push($rootScope.usersArr[user]);
        }
      }

      var shareUsers = $scope.getShareUsersAsArray(true);
      for(var i in shareUsers){
        newUserArray.push(shareUsers[i]);
      }

      $rootScope.usersArr = newUserArray;
    };

    socket.on('notify', function(data) {
      $rootScope.notif = angular.copy(data);
      $scope.Notifications = angular.copy(data);
      $scope.Notifications = $filter('relevantNotifications')($scope.Notifications);
      var oldCount = $scope.count.Notif;
      $scope.count.Notif = countRelevantN($scope.Notifications);
      if ($rootScope.user.settings && $rootScope.user.settings.soundNotify && oldCount < $scope.count.Notif){
        $scope.new_message.play();
      }
      if ($rootScope.user.waitForUpdateNotif) $rootScope.user.toUpdateNotif = true;
      $rootScope.$evalAsync();
    });

    socket.on('reload', function(){
      $rootScope.needReload = true;
    })
    socket.on('message1', function(data) {
      if (data.from != $rootScope.user.username){
        if (!($cookies.get('chatWindow') && $cookies.get('chatWindow') == "true")){
          if(typeof Notification != "undefined" && Notification.permission.toLowerCase() === "granted" ){
            var notify = new Notification($rootScope.users[data.from].name, {
              tag : data.from,
              body : data.message,
              icon : "/public/img_site/thumb_"+data.from+".jpeg"
            });
            notify.onclick = function(e){
              e.preventDefault();
              $scope.openChatWindow();
            }
          }
          $scope.new_message.play();
          $scope.chatUnreadMessages += 1;
          $scope.$apply();
        }else{
          $scope.chatUnreadMessages = 0;
          $scope.$apply();
        }
      }

    });
    socket.on('logout', function() {
      auth.logout();
    });
    socket.on('serverTime', function(time){
      //console.log(time);
      if (!$rootScope.serverTime)
        setInterval(incrementTime, 1000);

      $rootScope.serverTime = new Date(time.time);
      setTime();
    });

    socket.on('mailCount', function(data) {
      if(typeof data.success != 'undefined' && data.success){
        $scope.$apply(function () {
          $scope.newMailCount = data.result;
        });
      }
      else {
        console.log(typeof data.result != 'undefined'?data.result:'Unknown error checking mail.');
      }
    });
  });
  $scope.links = new Dictionary('Links', true);
  $scope.$timeout = $timeout;

  $scope.hideMenuTimeout = function(time, menu) {
    if (!$scope.menus[menu]) return;
    $scope.menus[menu].hideTimeout = $timeout(function() {
      $scope.menus[menu].isopen = false;
    }, time);
  };
  $scope.cancelHideMenu = function(menu) {
    if (!$scope.menus[menu]) return;
    $timeout.cancel($scope.menus[menu].hideTimeout);
  };

  var generateTaskSearchLink = function(params) {
    return { 'jsonQuery': btoa(escape(angular.toJson(params))) };
  };

  $scope.taskSearchLinks = {
    myTasks: generateTaskSearchLink({
      iAmAuthor: true,
      iAmPerformer: true,
      status: 'all'
    }),
    inTasksAll: generateTaskSearchLink({
      iAmAuthor: false,
      iAmPerformer: true,
      status: 'all'
    }),
    inTasksIncomplete: generateTaskSearchLink({
      iAmAuthor: false,
      iAmPerformer: true,
      status: 'incomplete'
    }),
    inTasksCompleted: generateTaskSearchLink({
      iAmAuthor: false,
      iAmPerformer: true,
      status: 'completed'
    }),
    outTasksAll: generateTaskSearchLink({
      iAmAuthor: true,
      iAmPerformer: false,
      status: 'all'
    }),
    outTasksIncomplete: generateTaskSearchLink({
      iAmAuthor: true,
      iAmPerformer: false,
      status: 'incomplete'
    }),
    outTasksCompleted: generateTaskSearchLink({
      iAmAuthor: true,
      iAmPerformer: false,
      status: 'completed'
    })
  };

  $scope.getFavorites = function() {
    if (!$scope.menus.favorites.busy) {
      var favors = new Favorites();
      favors.myFavorites( function(docs) {
        $scope.favs = docs;
      });
    }
  };
  $scope.delFavorites = function(fav) {
    Popup("", 'Удалить "<strong>'+ (fav.subject||fav.subjVoting) + '</strong>" из избранного?', '', true, function(){
      var favors = new Favorites();
      favors.delFavorites(fav.unid, function(res) {
        if (res){
          favors.myFavorites(function(docs) {
            $scope.favs = docs;
          });
        }
      });
    }, null, {"ok":"ДА", "cancel":"<span class='grey'>НЕТ</span>"});
  }
  $scope.getHistory = function() {
    if (!$scope.menus.history.busy) {
      History.list( 0, 20,
        function(dataSource) {
          dataSource = dataSource ? dataSource : [];
          $scope.history = [];
          angular.forEach(dataSource, function (value, key) { $scope.history.push(value); })
      });
    }
  };
  $scope.getMyTags = function() {
    if (!$scope.menus.tags.busy) {
      Tags.loadMyTags(
        function(data) {
          $scope.myTags = data.tags;
        },
        10
      );
    }
  };

  $scope.bodyRight = function () {
    $('body').addClass('b_left');
  }
  $scope.bodyLeft = function () {
    $('.exit-mobile-menu,#mobile-menu a,#mobile-blur').on('click', function () {
      $('body').removeClass('b_left');
    })
  }
  $scope.getHistory();
  $scope.getFavorites();
  $scope.getMyTags();

  $scope.$on('showFormEvent', function (event, data) {
    var search = $location.search()
    if(typeof data.form != "undefined" && data.form == 'person' && typeof search.client != "undefined" && search.client == '1C'){
      $state.go('body.1cCreatePerson', {organization:data.data.organization, organizationId:data.data.organizationId}, {reload: true});
      return;
    }
    $scope.showForm(data.form, data.type, data.cats, data.linkedTo, data.parentDoc, data.quote, data.data);
  });

  $scope.showForm = function(form, type, cats, linkedTo, parentDoc, quote, data){
//     $log.log('headerCtrl.showForm');
    $scope.QuoteSubject = '';
    $scope.CreateBody = '';

    if(parentDoc && linkedTo){
      $scope.mdoc = Discus.main_doc == null?parentDoc:Discus.main_doc;
      $scope.parentSubject = parentDoc.subject;
      var body = parentDoc.body?parentDoc.body:'';
      $scope.QuoteSubject = quote?quote:'';

      if($scope.QuoteSubject){
        body = $scope.QuoteSubject;
        $scope.CreateBody = '<blockquote contenteditable="false">'+parentDoc.AuthorRus+ ' ('+$filter('date')(parentDoc.created, "yyyy.MM.dd HH:mm:ss") + ') <a href="#/discus/'+parentDoc.unid+'/">'+body+'<a/></blockquote><br/>';
      }
    }

    if(form == 'person' && data && typeof data.organization != "undefined" && typeof data.organizationId != "undefined"){
      $scope.linkToOrganization = data;
    } else {
      $scope.linkToOrganization = false;
    }

    $rootScope.show.create = $rootScope.show.create ? $rootScope.show.create : {form: '', formType: '', formCats: ''};
    $rootScope.show.create.form = form;
    $rootScope.show.create.formType = type;
    $rootScope.show.create.formCats = cats;
    $scope.linkedTo = linkedTo;
  };
  $scope.showSearch = false;
  $scope.searchFocus = function() {
    $timeout(function() {
      $('#searchField').focus();
    }, 100);
  };

  $rootScope.useNotificatorByDefault = localStorage.getItem('useNotificatorByDefault') ? true : false;
}

function indexCtrl($scope, $uibModal, ListDiscus, $rootScope, $state, $log, Tasks, Contacts, TretoDateTime, Profile, Stat, TECollection, Settings) {
  var emptyContent = "Ничего не найдено.",
      minusMonthDate = new Date();
  $scope.company_name = company_name;
  var list = $rootScope.listDiscus || new ListDiscus();
  if(!$rootScope.listDiscus) { $rootScope.listDiscus = list; }
  $scope.treto_pic_host = treto_pic_host;
  minusMonthDate.setMonth(minusMonthDate.getMonth() - 1);
  minusMonthDate = TretoDateTime.iso8601.fromDate(minusMonthDate);
  $rootScope.Math = Math;
  
  list.lastLimited(8, function (docs) {
    $scope.newThemes = docs;
  });
  $scope.addCategoryItems = function(category, variable){
    if ($scope.itemsBusy) return;
    $scope.itemsBusy = true;
    if(!$scope['page' + variable]) $scope['page' + variable] = 15;
    $scope['status' + variable] = "Подождите...";
    list.byCategoryLimited(category, $scope['page' + variable], 15, function(items) {
      angular.forEach(items, function (value, key) {
        $scope[variable].push(value);
      });
      if ($scope[variable].length > 0){
        $scope['status' + variable] = "";
      }else{
        $scope['status' + variable] = emptyContent;
      }
      $scope['page' + variable] += 15;
      if (items.length > 0){
        $scope.itemsBusy = false;
      }
    })
  };
  
  Settings.get(function(data){
    $scope.blocksSettings = [];
    for (var i in data.indexBlocks) {
      $scope.blocksSettings[data.indexBlocks[i].blockId] = data.indexBlocks[i].value;
    };
  });

  $scope.orderCurrent = ['-Priority', 'taskDateRealEnd', 'subject'];
  $scope.orderCurrentTitle = 'Priority';
  $scope.order = function(predicate, reverse) {
    if (predicate == 'Priority')
    {
      $scope.orderCurrent = ['-Priority', 'taskDateRealEnd', 'subject'];
    } else
    {
      $scope.orderCurrent = ['taskDateRealEnd', '-Priority', 'subject'];
    }
    $scope.orderCurrentTitle = predicate;
  };

  $scope.itemID = [];
  $scope.showItem = function(itemID) {
    var index = $scope.itemID.indexOf(itemID);
    if (index == -1)
    {
      $scope.itemID.push(itemID);
    } else
    {
      $scope.itemID.splice(index,1)
    }
  };

  $scope.addContactsItems = function(variable){
    if ($scope.itemsBusy) return;
    $scope.itemsBusy = true;
    if(!$scope['page' + variable]) $scope['page' + variable] = 15;
    $scope['status' + variable] = "Подождите...";
    Contacts( {contact: {name: "Компании", search: "Organization"}}, $scope['page' + variable], 15, function(items) {
      angular.forEach(items, function (value, key) {
        $scope[variable].push(value);
      });
      if ($scope[variable].length > 0){
        $scope['status' + variable] = "";
      }else{
        $scope['status' + variable] = emptyContent;
      }
      $scope['page' + variable] += 15;
      if (items.length > 0){
        $scope.itemsBusy = false;
      }
    })
  };

  $scope.isRolePM = $rootScope.role('PM');
  if ($scope.isRolePM) {
    $scope.loadingResume = true;
    $scope.resume = [];
    Contacts({group: {name: "Минирезюме с сайта", search: "62F4B560590E6719C3257899003B3EA2"}}, 0, 8, function (conts) {
      $scope.loadingResume = false;
      $scope.resume = conts;
      if ($scope.resume.length > 0) {
        $scope.statusResume = "";
      } else {
        $scope.statusResume = "Ничего не найдено.";
      }
    });

    $scope.addResumeItems = function(variable){
      if ($scope.itemsBusy) return;
      $scope.itemsBusy = true;
      if(!$scope['page' + variable]) $scope['page' + variable] = 15;
      $scope['status' + variable] = "Подождите...";
      Contacts({group: {name: "Минирезюме с сайта", search: "62F4B560590E6719C3257899003B3EA2"}}, $scope['page' + variable], 15, function(items) {
        angular.forEach(items, function (value, key) {
          $scope[variable].push(value);
        });
        if ($scope[variable].length > 0){
          $scope['status' + variable] = "";
        }else{
          $scope['status' + variable] = emptyContent;
        }
        $scope['page' + variable] += 15;
        if (items.length > 0){
          $scope.itemsBusy = false;
        }
      })
    };
  }

  $scope.isRoleSiteReq = $rootScope.role('siteReq');
  if ($scope.isRoleSiteReq) {
    $scope.fromsite = [];
    Contacts({group: {name: "Запрос с сайта", search: "989DAD2607E12193C32577E000771DB1"}}, 0, 8, function (conts) {
      $scope.fromsite = conts;
      if ($scope.fromsite.length > 0) {
        $scope.statusFromsite = "";
      } else {
        $scope.statusFromsite = "Ничего не найдено.";
      }
    });
    $scope.addFromsiteItems = function(variable){
      if ($scope.itemsBusy) return;
      $scope.itemsBusy = true;
      if(!$scope['page' + variable]) $scope['page' + variable] = 15;
      $scope['status' + variable] = "Подождите...";
      Contacts({group: {name: "Запрос с сайта", search: "989DAD2607E12193C32577E000771DB1"}}, $scope['page' + variable], 15, function(items) {
        angular.forEach(items, function (value, key) {
          $scope[variable].push(value);
        });
        if ($scope[variable].length > 0){
          $scope['status' + variable] = "";
        }else{
          $scope['status' + variable] = emptyContent;
        }
        $scope['page' + variable] += 15;
        if (items.length > 0){
          $scope.itemsBusy = false;
        }
      })
    };
  }
  $scope.profile = new Profile();
  $scope.subscriber = function(subscribeID) {
    $log.log('indexCtrl.subscriber');
    var index = $scope.user.portalData.Subscribe.indexOf(subscribeID);
    if (index == -1)
    {
      $scope.user.portalData.Subscribe.push(subscribeID);
    } else
    {
      $scope.user.portalData.Subscribe.splice(index,1)
    }
    $scope.profile.saveUser($scope.user, function(success, messages) {
      $scope.success = success;
      $scope.messages = messages;
    });
  };
  $scope.daysAgo = function(date, days) {
    dateDayAgo = new Date();
    dateDayAgo.setDate(dateDayAgo.getDate()-days);
    docDate = TretoDateTime.iso8601.toDate(date);
    return dateDayAgo < docDate;
  }

  //Get and toggle blocks state (close and open) on main page 111
  $scope.getBlockState = function (blockName, change) {
    if($scope.collapseState == undefined){
      var collapseState = JSON.parse(localStorage.getItem('collapseState'));
      $scope.collapseState = !collapseState?{}:collapseState;
    }

    if($scope.collapseState[blockName] == undefined){
      $scope.collapseState[blockName] = true;
    }

    if(change){
      $scope.collapseState[blockName] = !$scope.collapseState[blockName];
      localStorage.setItem('collapseState', JSON.stringify($scope.collapseState));
    }

    return $scope.collapseState[blockName];
  };
};

function factoryHolidayCtrl($scope, $http, $rootScope){
  $scope.loading = false;
  $scope.factories = [];
  var getAllBm = function(callback){
    var result = [];

    for(var login in $rootScope.users){
      var user = $rootScope.users[login];

      if(user.section && user.section.indexOf('БМ') !== -1){
        result.push({login:login, name:user.name});
      }
    }

    callback && callback(result);
  };

  var intervalId = setInterval(function(){
    if($rootScope.users && Object.keys($rootScope.users).length > 0){
      clearInterval(intervalId);

      getAllBm(function(result){
        $scope.bms = result;
        $scope.selectedBm = false;
        $scope.loading = false;
      });
    }
    else {
      $scope.loading = true;
    }
  }, 200);

  $scope.getBmFactories = function(){
    $scope.loading = true;
    $http.post('api/contact/getBmFactories', {bmLogin:$scope.selectedBm}).then(function(response){
      $scope.loading = false;
      $scope.factories = response.data.factories;
    });
  };

  var dateObjToStr = function(str){
    var time = new Date(str);
    return time.getFullYear()+('0'+(time.getMonth()+1)).slice(-2)+('0'+time.getDate()).slice(-2);
  };

  $scope.createNewHoliday = function (factoryUnid, from, to) {
    if(from && to){
      $scope.loading = true;
      var param = {unid:factoryUnid, from:dateObjToStr(from), to:dateObjToStr(to)};
      $http.post('api/contact/addHoliday', param).then(function(response){
        $scope.loading = false;
        if(response.data && response.data.holiday){
          for(var i in $scope.factories){
            if($scope.factories[i].unid == factoryUnid){
              $scope.factories[i].holiday = response.data.holiday;
            }
          }
        }
      });
    }
  }
}

function portalSettingsCtrl($scope, $http, Settings, $timeout) {
  $scope.loading = true;

  Settings.get(function(data){
    $scope.allSettings = data;
    $scope.loading = Settings.loading;
  });

  $scope.checkShare = function(i){
    $scope.allSettings.sharePortal[i].check = 1;
    Settings.check($scope.allSettings.sharePortal[i], function(response){
      var result = response.result&&response.result.success&&response.result.success.success;
      $scope.allSettings.sharePortal[i].check = result?2:3;//0 - button, 1 - wait, 2 - success, 3 - fail
      $timeout(function(){
        $scope.allSettings.sharePortal[i].check = 0;
      }, 3000);
    })
  };

  $scope.addSharePortal = Settings.addSharePortal;

  $scope.save = function() {
    Settings.set(function(data){$scope.allSettings = data;});
  };
}

function popupCtrl($rootScope) {
    $rootScope.popup.ok = function() { $rootScope.popup.modal.close(); };
    $rootScope.popup.cancel = function() { $rootScope.popup.modal.dismiss('cancel'); };
}

function tasksCtrl($scope, $rootScope, Tasks, Profile, $state) {
  $scope.profile = new Profile();
  $scope.tasks = new Tasks();
  delete $rootScope.tasksUsersArr;
  $scope.search = function() {
    $state.go('body.taskSearch',{ 'jsonQuery': btoa(escape(angular.toJson($scope.tasks.params))) });
  };
}

function taskSearchCtrl($scope, $rootScope, Tasks, Profile, $stateParams, $state, $timeout) {
  if(! $scope.profile) { $scope.profile = new Profile(); }
  if(! $scope.tasks || typeof $scope.tasks.search == 'undefined') { $scope.tasks = new Tasks($rootScope.tasksUsersArr); }
  $scope.sort = $rootScope.tasksSortParams || {reverse:true, predicate:'created'};
  delete $rootScope.tasksSortParams;
  $scope.order = function(predicate) {
    $scope.sort.reverse = ($scope.sort.predicate === predicate) ? !$scope.sort.reverse : false;
    $scope.sort.predicate = predicate;
  }

  var t = $scope.tasks;

  var tmpParams = angular.fromJson(unescape(atob($stateParams.jsonQuery)));
  if (tmpParams.created && tmpParams.created.start) tmpParams.created.start = tmpParams.created.start ? new Date(tmpParams.created.start) : null;
  if (tmpParams.created && tmpParams.created.end) tmpParams.created.end = tmpParams.created.end ? new Date(tmpParams.created.end) : null;
  if (tmpParams.completed && tmpParams.completed.start) tmpParams.completed.start = tmpParams.completed.start ? new Date(tmpParams.completed.start) : null;
  if (tmpParams.completed && tmpParams.completed.end) tmpParams.completed.end = tmpParams.completed.end ? new Date(tmpParams.completed.end) : null;

  $.extend(t.params, tmpParams);
  if (t.params.iAmAuthor)
    t.setAuthor($rootScope.usersAll[$rootScope.user.username]);
  if (t.params.iAmPerformer)
    t.setPerformer($rootScope.usersAll[$rootScope.user.username]);
  t.search(null, 0, 800, $scope.sort);

  $scope.search = function() {
    $rootScope.tasksSortParams = $scope.sort;
    $state.go('body.taskSearch',{ 'jsonQuery': btoa(escape(angular.toJson(t.params))) });
  };

  var debouncedTimer = null;
  $scope.debouncedSearch = function() {
    $timeout.cancel(debouncedTimer);
    debouncedTimer = $timeout($scope.search, 300);
  };

  $scope.lastPressedOnAuthorIsKeyboard = false;
  $scope.lastPressedOnPerformerIsKeyboard = false;

  $scope.onChangeAuthor = function() {
    t.params.iAmAuthor = t.params.author
                      && t.params.author.id === $rootScope.user.username;
    if ($scope.lastPressedOnAuthorIsKeyboard && t.params.author === null) return;
    $scope.debouncedSearch();
  };

  $scope.onChangePerformer = function() {
    t.params.iAmPerformer = t.params.performer 
                         && t.params.performer.id === $rootScope.user.username;
    if ($scope.lastPressedOnPerformerIsKeyboard && t.params.performer === null) return;
    $scope.debouncedSearch();
  };

  $scope.toggleAuthorSwitch = function() {
    t.params.iAmAuthor = !t.params.iAmAuthor;
    if (t.params.iAmAuthor) t.setAuthor($rootScope.usersAll[$rootScope.user.username]);
    else t.removeAuthor();
  };

  $scope.togglePerformerSwitch = function() {
    t.params.iAmPerformer = !t.params.iAmPerformer;
    if (t.params.iAmPerformer) {
      if (t.params.status === 'suspended') t.params.status = 'all';
      t.setPerformer($rootScope.usersAll[$rootScope.user.username]);
    } else t.removePerformer();
  };
}

function discusListCtrl($scope, $stateParams, ListDiscus, Contacts) {
  list = new ListDiscus();
  $scope.page = 0;
  $scope.docs = [];
  $scope.category = $stateParams.category;
  $scope.addHistory = function(){
    if ($scope.busy) return;
    $scope.busy = true;
    $scope.discusType = '';
    $scope.status = "Подождите...";
    function addToPage(docs){
      docs = docs ? docs : [];
      $scope.page += docs.length;
      $scope.docs = $scope.docs ? $scope.docs : [];
      angular.forEach(docs, function (value, key) {
        $scope.docs.push(value);
      });
      if(docs.length !== 0) $scope.busy = false;
      $scope.status = $scope.docs.length ? "": "Нет записей.";
    };
    if($stateParams.category == 'Blog'){
      list.byTypeLimited($stateParams.category, $scope.page, 100, addToPage);
      $scope.category = 'Блоги';
    } else if($stateParams.category == 'Новые темы'){
      list.byCategoryLimited('new', $scope.page, 100, addToPage);
    } else if($stateParams.category == 'Подвешанные просьбы'){
      list.getWaitPerformerTasks($scope.page, 100, addToPage);
    } else if($stateParams.category == 'Запрос с сайта'){
      $scope.discusType = 'contact';
      Contacts({group: {name: "Запрос с сайта", search: "989DAD2607E12193C32577E000771DB1"}}, $scope.page, 100, addToPage);
    } else {
      list.byCategoryLimited($stateParams.category, $scope.page, 100, addToPage);
    }
  };
}

function discusCtrl ($http, $rootScope, $scope, $state, $stateParams, $filter, $log, Discus,
                     Popup, Dictionary, Favorites, $location, DiscusSharedSvc, Viewport,
                     AutoComplete, Discounts, DiscountFields, History, Question, Mail, Contact, TretoDateTime, Socket, $timeout)
{
  $rootScope.autoComplete = new AutoComplete();
  $scope.selfUrl = $location.host();

  $scope.addEmplToCompany = function(companyId, emplId){
    if(companyId && emplId){
      $http({method: 'GET', url: 'api/contact/linkEmplToOrg/'+emplId+'/'+companyId}).then(function(response) {
        $state.go('body.discus', $state.params, {reload: true});
      }, httpErrorHandler);
    }
  };

  $scope.removeEmplFromCompany = function (companyId, emplId) {
    console.log(companyId, emplId);
    if(companyId && emplId){
      $http({method: 'GET', url: 'api/contact/removeEmplFromOrg/'+emplId+'/'+companyId}).then(function(response) {}, httpErrorHandler);
    }
  };

  $scope.showFormEmit = function(form, type, cats, linkedTo, parentDoc, quote, data){
    $scope.$emit('showFormEvent', {
      form:form,
      type:type,
      cats:cats,
      linkedTo:linkedTo,
      parentDoc:parentDoc,
      quote:quote,
      data:data
    });
  };

  $scope.getContactParams = function(personId){
    var result = { id: personId, type: 'Contact'};
    var search = $location.search();

    if(typeof search.client != "undefined" && search.client == '1C'){
      result['client'] = '1C'
    }
    return result;
  }

  $scope.locale = new Dictionary('Locale', true);
  $scope.currentLocale = typeof $state.params.locale != 'undefined'?$state.params.locale:false;

  $scope.selectLocale = function(locale){
    var goParam = $state.params;
    goParam.locale = locale == 'all'?undefined:locale;
    $state.go('body.discus', goParam, {reload: true});
  };

  $rootScope.Math = Math;
  $rootScope.mainDiscus = Discus;
  if ($stateParams.type == "contact"){
    $rootScope.autoComplete = new AutoComplete();
    var projection = $scope.statusListDict;
    $rootScope.projectContactStatus = function (input) {
      if(typeof input == 'string') {
        return projection.getRecordValue(input);
      }
      input = input || [], v = []
      if(typeof input == 'object' && input.hasOwnProperty('length')) {
        for(var w in input) {
          var inp = input[w];
          var re = inp;
          if(projection.records && projection.records)
            projection.records.forEach(function(el){ if(el.key == inp) re = el.value; });

          if(projection.recordsTree && projection.recordsTree.records)
            projection.recordsTree.records.forEach(function(el){ if(el.key == inp) re = el.value; });

          v.push(re)
        };
        return v.join('; ');
      }
      else
      if(typeof input == 'object') {
        for(var w in input) { v.push(w + ': ' + projection.getRecordValue(input[w])) }; return v.join('; ');
      }
      return input;
    };
    $rootScope.acceptedDiscounts = function () {
      var sure = confirm('Акцептовать скидки', 'Скидки');
      if(!sure) return;
      $http({method: 'GET', url: 'api/contact/accepted', params: {'id':$stateParams.id} })
          .then(function(response) {
            if(response.data.DiscountAccepted === '1') {
              $state.go('body.discus', {id: $stateParams.id, type: $stateParams.type});
              new Popup('Скидки', 'Скидки акцептованы', 'notify');
            }
          }, httpErrorHandler);
    }
  }

  Discus.isContact = ($stateParams.type == 'contact');
  Discus.show = {};
  Discus.show.Parts = false;
  Discus.loadDocuments($stateParams.id, function() {
    var cat = [];
    for (var i = 1;i<=5;i++)
    {
      if(Discus.main_doc['C'+i])
      {
        cat.push(Discus.main_doc['C'+i]);
      }
    }
    $scope.isMini = false;
    $rootScope.categoryToNewDiscus = cat.join(',');
    Discus.isBlog = Discus.main_doc.type == 'Blog';

    if (Discus.main_doc.ContactStatus && Discus.main_doc.ContactStatus.indexOf('11') > -1) {
      Discounts(Discus.main_doc.unid, function (discounts) {
        $rootScope.discounts = discounts;
        var dFields = new DiscountFields();
        $rootScope.UseDiscount = dFields.UseDiscount;
        $rootScope.ConditionDiscount1 = dFields.ConditionDiscount1;
        $rootScope.ConditionDiscount2 = dFields.ConditionDiscount2;
        $rootScope.ConditionDiscount4 = dFields.ConditionDiscount4;
        $rootScope.ConditionDiscount5 = dFields.ConditionDiscount5;
        $rootScope.ObjectDiscount = dFields.ObjectDiscount;
        $rootScope.symbols = dFields.symbols;
        $rootScope.parseInt = function (str) {
          return parseInt(str, 10);
        };
      });
    }
    if (Discus.main_doc.form == 'Contact') {
      Discus.contact = new Contact(Discus.main_doc);
      Question.getQuestionaries(function(quests) {
        Discus.main_doc.questionaries = quests;
      });
      Discus.main_doc.sendQuestionary = function(doc, quest, hitlist) {
        var sure = confirm('Вы уверены что хотите отправить опрос "' + quest.name + '" ?', 'Контакты');
        if (sure){
          Mail.sendQuestionary(doc.unid, quest.unid, function(hitlist) {
            doc.HRQuestionsLinkGeneratedBy = $rootScope.user.portalData.LastName +" "+ $rootScope.user.portalData.name;
            doc.HRQuestionsLinkGeneratedDate = $filter('date')(new Date(), "yyyyMMddTHHmmss");
          })
          if (hitlist) {
            Discus.main_doc.sendHitList(doc, true);
          }
        }
      }
      Discus.main_doc.sendHitList = function(doc, silent) {
        if (!silent) var sure = confirm('Вы уверены что хотите отправить ХитЛист?', 'Контакты');
        if (sure || silent){
          quest = '';
          angular.forEach(Discus.main_doc.questionaries, function(q) {
            if (q.name == 'ХитЛист') {
              quest = q.unid;
            }
          })
          Mail.sendQuestionary(doc.unid, quest, function(hitlist) {
            doc.HitListLinkGeneratedBy = $rootScope.user.portalData.LastName +" "+ $rootScope.user.portalData.name;
            doc.HitListLinkGeneratedDate = $filter('date')(new Date(), 'yyyyMMddTHHmmss');
          });
        }
      }
      Discus.main_doc.showQuestionaryLink = function(unid) {
        return prompt('Ссылка на опросник:', "http://dev.treto.ru/app_dev.php/ru/career/interview/"+unid);
      }
      Discus.main_doc.showHitListLink = function() {
        quest = '';
        angular.forEach(Discus.main_doc.questionaries, function(q) {
          if (q.name == 'ХитЛист') {
            quest = q.unid;
          }
        })
        return prompt('Ссылка на ХитЛист:', "http://dev.treto.ru/app_dev.php/ru/career/interview/"+quest);
      }
    }
    docId = $state.params.id;
    currentDoc = null;

    messages = [];
    for (var i in Discus.comments) {
      messages.push(Discus.comments[i].unid);
      if (Discus.comments[i]._id == docId || Discus.comments[i].unid == docId)
        currentDoc = Discus.comments[i].subject;
    }

    $http({method: 'POST', url: 'api/discussion/loadCommLinks', data: {messages: messages}})
            .then(function(response) {
              if (response.data.success) {
                //$log.info('loadCommLinks success');
                Discus.linked = {};
                Discus.linked.MesLinks = response.data.mesLinks;

                commentsWithLinks = [];
                for (var i in Discus.linked.MesLinks) {
                  if (Discus.linked.MesLinks[i].self) {
                    link = Discus.linked.MesLinks[i].self.linkedTo;
                    commentsWithLinks[link] = commentsWithLinks[link] ? commentsWithLinks[link] : [];
                    commentsWithLinks[link].push(Discus.linked.MesLinks[i]);
                  }
                }
                //console.dir(commentsWithLinks);
                for (var i in commentsWithLinks) {
                  for (var j in Discus.comments) {
                    if (Discus.comments[j].unid == i) {
                      Discus.comments[j].linked = {};
                      Discus.comments[j].linked.Children = commentsWithLinks[i];
                    }
                  }
                }

              } else {
                $log.error('loadCommLinks error');
              }
            }, httpErrorHandler);

    if (Discus.main_doc.unid)
      History.add_full(Discus.main_doc.subject || Discus.main_doc.subjVoting || Discus.main_doc.ContactName || currentDoc || 'Без темы',
                       Discus.main_doc.form,
                       Discus.main_doc.unid);
                       
    var discusScrollTimer = null;
    var discusReadTimer = null;
    var readComments = function() {
      $timeout.cancel(discusScrollTimer);
      $timeout.cancel(discusReadTimer);
      if (!$scope.discus || !$scope.discus.main_doc) return;
      discusScrollTimer = $timeout(function() {
        $scope.discus._meta = $scope.discus._meta || {};
        $scope.discus._meta.hasNewPostsDownViewport = $scope.discus.hasNewPostsDownViewport();
        $scope.discus._meta.hasNewPostsUpViewport = $scope.discus.hasNewPostsUpViewport();
        discusReadTimer = $timeout(function() {
          $scope.discus.readOnscreenDocs();
        }, 2000);
      }, 200);
    };
    $(window).on('scroll.discusReadComments focus.discusReadComments', readComments)
            .on('blur.discusReadComments', function() {
                $timeout.cancel(discusScrollTimer);
                $timeout.cancel(discusReadTimer);
            });
    $(document).on('mousemove.discusReadComments', readComments);
    readComments();
    $scope.$on('$destroy', function() {
      $(window).off('.discusReadComments');
      $(document).off('.discusReadComments');
    });

    $state.current.name === 'body.discus' && Discus.scrollOnThemeOpen();
  });
  $scope.discus = Discus;
  $rootScope.contactDelete = function(id, isDiscount) {
    var sure = confirm('Вы уверены что будете удалять', 'Контакты');
    if(!sure) return;
    $http({method: 'GET', url: 'api/contact/delete', params: {'id':id} })
      .then(function(response) {
        if(response.data.Status == 'deleted') {
          console.log(response);
          if (!isDiscount)
          {
            $state.go('body.contacts');
            new Popup('Контакты', 'Контакт удален', 'notify');
          }
        }
      }, httpErrorHandler);
  }
  $rootScope.contactUndelete = function(id) {
    var sure = confirm('Вы уверены что хотите восстановить', 'Контакты');
    if(!sure) return;
    $http({method: 'GET', url: 'api/contact/undelete', params: {'id':id} })
      .then(function(response) {
        if(response.data.Status === 'open') {
          $state.go('body.contacts');
          new Popup('Контакты', 'Контакт восстановлен', 'notify');
        }
      }, httpErrorHandler);
  }
  $rootScope.discountSetOld = function(id) {
    var sure = confirm('Вы уверены что скидка устарела', 'Контакты');
    if(!sure) return;
    $http({method: 'GET', url: 'api/contact/setold', params: {'id':id} })
        .then(function(response) {
          console.log(response);
        }, httpErrorHandler);
  }
  $rootScope.toReserve = function(contact) {
    contact.Group.push('Резервисты');
    $rootScope.persist(contact);
  }
  $rootScope.persist = function(contact, notGo) {
    $http({method: 'POST', url: 'api/contact/save', data: {'contact':contact} })
        .then(function(response) {
          if(response.data.success && !notGo) {
            $state.go('body.discus', {id: response.data.response._id, "type": "contact"});
          }
        }, function(data,status){
          httpErrorHandler(data,status);
        });
  };
  var svc = DiscusSharedSvc;

  $scope.add_favorites = svc.add_favorites;
  $scope.del_favorites = svc.del_favorites;
  $scope.isFavorite = svc.isFavorite;

  $scope.add_parts = function(doc) {
    $scope.discus.addParticipant($rootScope.user.username, 'username', doc, true);
    $scope.discus.saveParticipants(doc, function() {
      $scope.discus.include.shownThreadParticipants.refresh();
    }, true);
  }
  $scope.del_parts = function(doc) {
    $scope.discus.removeParticipant($rootScope.user.username, 'username', doc);
    $scope.discus.removeParticipant($rootScope.user.portalData.FullName, 'username', doc);
    $scope.discus.removeParticipant($rootScope.user.portalData.FullNameRaw, 'username', doc);
    $scope.discus.saveParticipants(doc, function() {
      $scope.discus.include.shownThreadParticipants.refresh();
    }, true);
  }

//   $scope.applyAdding = function(person) { //for the directive
//     var userList = [];
//     if (person) {
//       for (key in person) {
//         $scope.discus.addParticipant(person[key], 'username', $scope.discus.main_doc);
//         userList.push(person[key]);
//       }
//       $scope.discus.saveParticipants(discus.main_doc, function() {
//         $scope.discus.addUnreadedToUsers($scope.discus.main_doc, userList, false);
//         $scope.discus.include.shownThreadParticipants.refresh();
//       });
//     }
//
//     $scope.discus.show.Parts = false;
//   }

  $rootScope.$watch('contactName', function(v){
    if(v && $scope.discus.main_doc)
    {
      $scope.discus.main_doc.Company = v;
      $rootScope.persist($scope.discus.main_doc, true);
    }
  });
  $rootScope.$watch('EmployeeName', function(v){
    if(v && $scope.discus.main_doc)
    {
      var old = '';
      if($scope.discus.main_doc.Employee) {
        old = $scope.discus.main_doc.Employee;
        if ($scope.discus.main_doc.Employee.length) {
          v = ', ' + v;
        }
      }
      $scope.discus.main_doc.Employee = old + v;
      $rootScope.persist($scope.discus.main_doc, true);
    }
  });
}

function discusParticipantsCtrl ($scope, $rootScope, $log, Discus, Security, $timeout) {
  $scope.participants = []; //Discus.tempParticipants.slice();
  Discus.sharePushArray = {};
  $scope.shareEnable = true;
  $scope.discus = Discus;

  if($scope.$parent.discus &&
      $scope.$parent.discus.current &&
      $scope.$parent.discus.current.form != "formProcess" &&
      $scope.$parent.discus.current.form != "formTask" &&
      $scope.$parent.discus.main_doc &&
      $scope.$parent.discus.main_doc.form != "formProcess" &&
      $scope.$parent.discus.main_doc.form != "formTask"){
      $scope.shareEnable = false;
  }

  $scope.share = Discus.main_doc && Discus.main_doc.shareSecurity?Discus.main_doc.shareSecurity:Discus.current.shareSecurity;
  $scope.share = Discus.getShareParticipants($scope.share);

  $scope.save = function() {
    $log.log('discusParticipantsCtrl.save');

    Discus.tempParticipants = _.uniq(Discus.tempParticipants.concat($scope.participants.slice()));
    Discus.tempShareParticipants = Discus.sharePushArray;
    Discus.setNewSharePrivileges();

    if (Discus.participantsModal.edit){
      Discus.applyAdding(Discus.tempParticipants);
    }

    $scope.close();
  };

  $scope.close = function() {
    $log.log('discusParticipantsCtrl.close');
    if (Discus.participantsModal.edit) Discus.tempParticipants = [];
    else $scope.participants = Discus.tempParticipants.slice();
    Discus.participantsModal.show = false;
    Discus.participantsModal.showDsk = false;
    Discus.participantsModal.edit = false;
  };
}

function discusLinkedToCtrl($http, $rootScope, $scope, $uibModal, $state){
  $rootScope.popup = {};

  $scope.showMenu = function(unid){
    $rootScope.popup.modal = $uibModal.open({
      templateUrl: '/bundles/tretoportal/partials/modals/linkedToModal.html',
      size: 'lg',
      controller: function($scope){
        $scope.link = '';
        $scope.error = '';

        $scope.cancel = function(){
          $rootScope.popup.modal.close();
        };

        $scope.ok = function(){
          var toUnid = '';
          if($scope.link){
            $scope.link.replace(/\/discus\/(.*)\//gim, function replacer(str, p1) {
              toUnid = p1;
            });
          }
          else {
            $scope.error = 'Скопируйте ссылку в соответсвующую форму.'
          }

          if(!toUnid){
            $scope.error = 'Не верная ссылка.';
          }
          else {
            $http({method: 'POST', url: 'api/discussion/linkedTo', data: {from: unid, to: toUnid}})
                .then(function(response) {
                  if(typeof response.data != "undefined" && response.data){
                    var data = response.data
                    if(data && typeof data.error != "undefined"){
                      if(!data.error){
                        $rootScope.popup.modal.close();
                        $state.go($state.current, {}, {reload: true});
                      }
                      else {
                        $scope.error = data.error;
                      }
                    }
                  }
                  else {
                    $scope.error = 'Не известная ошибка.';
                  }
                })
          }
        }
      },
      resolve: {}
    });
    $rootScope.popup.modal.result.then();
  }
}

function inviteGuestCtrl($http, $rootScope, $scope, $uibModal, $state){
  $rootScope.popup = {};

  $scope.inviteGuestModal = function(main, doc){
    $rootScope.popup.modal = $uibModal.open({
      templateUrl: '/bundles/tretoportal/partials/modals/inviteGuestModal.html',
      size: 'lg',
      controller: function($scope){
        $scope.email = '';

        $scope.cancel = function(){
          $rootScope.popup.modal.close();
        };

        $scope.ok = function(){
          $scope.error = '';
          
          if($scope.enail == ''){
            $scope.error = 'Не указан email.'
          }
          
          if ($scope.error == '') {
            $http({method: 'POST', url: 'api/discussion/inviteGuest', data: { email: $scope.email,
                                                                              main: main.unid,
                                                                              doc: doc.unid
            }})
                .then(function(response) {
                  if(typeof response.data != "undefined" && response.data){
                    var data = response.data
                    if(data && typeof data.error != "undefined"){
                      if(!data.error){
                        $scope.url = document.location.origin + document.location.pathname
                        + $state.href('body.discus',{ id: doc.unid, type: (main.form==='Contact'?'contact':'') });
                        $scope.url += '?key='+data.key;
                      }
                      else {
                        $scope.error = data.error;
                      }
                    }
                  }
                  else {
                    $scope.error = 'Неизвестная ошибка.';
                  }
                })
          }
        }
      },
      resolve: {}
    });
    $rootScope.popup.modal.result.then();
  }
}

function modalMail($scope, $uibModalInstance, $rootScope, Mail) {
  var month = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  $scope.hdrs = [];
  $scope.query = {};
  $scope.mails = [];
  $scope.status = 'Для отображения писем выберите параметры поиска и нажмите \"найти\".';
  $scope.find = function() {
    $scope.hdrs = [];
    $scope.status = "Подождите, идет поиск писем...";
    // q = '';
    // if ($scope.query.SINCE&&$scope.query.SINCE.length==8){
    //   $scope.query.SINCE = [$scope.query.SINCE.substring(6,8), month[$scope.query.SINCE.substring(4,6)*1], $scope.query.SINCE.substring(0,4)].join("-");
    // }
    // if ($scope.query.BEFORE&&$scope.query.BEFORE.length==8){
    //   $scope.query.BEFORE = [$scope.query.BEFORE.substring(6,8), month[$scope.query.BEFORE.substring(4,6)*1], $scope.query.BEFORE.substring(0,4)].join("-");
    // }
    Mail.mailHeaders($scope.query, function(hdrs) {
      $scope.status = hdrs.length>0?'':'Ничего не найдено, попробуйте изменить условия поиска.';
      $scope.mails = [];
      $scope.hdrs = hdrs;
    })
  }

  $scope.ok = function() {
    $uibModalInstance.close($scope.mails);
  }
  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };
}

function notifCtrl($http, $scope, $timeout, $rootScope, $state, $log, $filter, Discus, DiscusInstance, Profile, Favorites, Notificator,
                                  DiscusSharedSvc, Popup, History, TretoDateTime, Socket) {

  $rootScope.Math = Math;
  $rootScope.user.toUpdateNotif = false;
  $rootScope.user.waitForUpdateNotif = false;

  $rootScope.show.notifLoading = false;

  Notificator.useNotificatorByDefault(false);

  $scope.getShareName = function(dataType, sendShareFrom, shareAuthorLogin){
    var result = '';
    if(sendShareFrom, shareAuthorLogin){
      var name = $rootScope.shareUsers[sendShareFrom].name;
      result = Discus.findShareDataByLogins(dataType, sendShareFrom, shareAuthorLogin) + ' ('+name+')';
    }
    return result;
  };

  $scope.$watch(function(scope) { return $rootScope.user.waitForUpdateNotif }, function (newValue, oldValue) {
    if (newValue === true) {
      $log.info('Watcher 1 fired');
      $log.info('waitForUpdateNotif: '+newValue+', toUpdateNotif: '+$rootScope.user.toUpdateNotif);
      if($rootScope.user.toUpdateNotif === true) {
        $log.warn('$scope.lastDoc: '+$scope.lastDoc);
        $scope.refreshNotifications($scope.lastDoc);
      }
    }
  }, true);

  $scope.$watch(function(scope) { return $rootScope.user.toUpdateNotif }, function (newValue, oldValue) {
    if (newValue === true) {
      $log.info('Watcher 2 fired');
      $log.info('waitForUpdateNotif: '+$rootScope.user.waitForUpdateNotif+', toUpdateNotif: '+newValue);
      if($rootScope.user.waitForUpdateNotif === true && newValue === true) {
        $log.warn('$scope.lastDoc: '+$scope.lastDoc);
        $scope.refreshNotifications($scope.lastDoc);
      }
    }
  }, true);

  $scope.$watch(function(scope) { return $rootScope.notif }, function (newValue, oldValue) {
    if (typeof oldValue == "undefined" && newValue != oldValue) {
      $log.info('Watcher 3 fired');
      $scope.refreshNotifications($scope.lastDoc);
    }
  }, true);

  $scope.countRelevant = function() {
    var n = $filter('relevantNotifications')($scope.Notifications);
    var res = 0;
    for(var i in n) { res++; }
    return res;
  };

  $scope.scrollToLine = function(selector) {
    $timeout(function(){
      $log.log('ScrollTo '+selector);
      e = document.getElementById(selector);
      if (e) {
        e.scrollIntoView(true);
        if ($rootScope.windowWidth > 950) {
          $log.log('Header is in fixed position');
          if ((window.innerHeight + window.scrollY) < document.body.offsetHeight)
            window.scrollBy(0, -($rootScope.windowHeight));
        }
      }
    }, 500);

  };

  $scope.headerCheck = function() {
    var showHeader = false;
    var keys = Object.keys($scope.checkedDocs);
    for (var i = keys.length - 1; i >= 0; i--) {
      var key = keys[i];
      if ($scope.checkedDocs[key] && $filter('relevantNotifications')($scope.Notifications)[key]) {
        showHeader = true;
        break;
      }
    }
    if (showHeader) $scope.showHeader = true;
    else $scope.showHeader = false;
  };

  $scope.expand = function(unid) {
    $log.log('expand '+unid);
    if(!$rootScope.notificationsDiscus[unid] || !$scope.expanded[unid]) {
      $scope.expanded[unid] = true;
      $rootScope.notifExp.Time[unid] = $rootScope.formatDateForReadBy($rootScope.serverTime || new Date());
      $rootScope.notifExp.TimeISO[unid] = $rootScope.formatDateForReadBy($rootScope.serverTime || new Date(), true);
      var docs = $scope.Notifications[unid].docs;

      Discus.getWithUnreadedComments(unid, docs, function(respDoc, comments) {
        Discus.prepareForReadAfter(respDoc);
        $scope.tmpDocs[unid] = new DiscusInstance();
        $rootScope.notificationsDiscus[unid] = $scope.tmpDocs[unid];
        $rootScope.notificationsDiscus[unid].unids = [];
        $scope.tmpDocs[unid].main_doc = respDoc;

        if (comments && comments.length > 0) {
          $scope.tmpDocs[unid].comments = comments;
          $scope.tmpDocs[unid].unreaded = [];
          for (var i in $scope.tmpDocs[unid].comments) {
            $rootScope.notificationsDiscus[unid].unids.push($scope.tmpDocs[unid].comments[i].unid);
            if ($scope.tmpDocs[unid].comments[i].form == 'formTask' ||
                $scope.tmpDocs[unid].comments[i].form == 'formVoting')
              Discus.prepareForReadAfter($scope.tmpDocs[unid].comments[i]);
          }
        }

        $rootScope.notificationsDiscus[unid].isLoaded = true;
        if ($rootScope.user.settings &&
            $rootScope.user.settings.notifHistory &&
            $scope.Notifications[unid].form != 'Empl') {
          History.add_full($scope.tmpDocs[unid].main_doc.subject,
                          $scope.Notifications[unid].parentForm,
                          $scope.tmpDocs[unid].main_doc.unid);
        }
        $scope.count.Loaded++;
      });

       $rootScope.notificationsDiscus[unid] = $scope.tmpDocs[unid];
       $scope.isMini = true;
    } else {
      $scope.expanded[unid] = false;
      delete $rootScope.notifExp.Time[unid];
      delete $rootScope.notifExp.TimeISO[unid];
      $rootScope.notificationsDiscus[unid].isLoaded = false;
      $rootScope.notificationsDiscus[unid].clear();
      $rootScope.notificationsDiscus[unid] = null;
    }
  };

  $scope.expandChunk = function(control) {
    $log.warn('Loading chunk...');
    var notif = $.map($scope.Notifications, function(value, index) {
      if (!value.notifyWhen)
        return [value];
      else {
        var date = new Date(value.notifyWhen);
        if (date < $rootScope.serverTimeMsk)
          return [value];
      }
    });

    notif = $filter('orderObjectBy')(notif, 'entryOrder', true);
    $scope.counterChunk = 0;
    $scope.cursor += $scope.expLimit;

    for (var i=$scope.counterNotif; $scope.counterChunk < $scope.expLimit && i < notif.length && i < $scope.cursor; i++) {
      $scope.expand(notif[i].parentUnid);
      $log.info("Notif #"+i+' parentUnid: '+notif[i].parentUnid);

      $scope.counterNotif++;
      $scope.counterChunk++;
    };

    $log.warn('Cursor = '+$scope.cursor+'; counterNotif = '+$scope.counterNotif);
  }

  $scope.expandAll = function(control) {
    $log.log('expandAll');
    $rootScope.notificationsDiscus = {};
    if (!$scope.Notifications || $scope.Notifications.length == 0) return false;

    $scope.counterNotif = 0;
    $scope.counterChunk = 0;

    $scope.cursor = 0;
    $scope.expLimit = 5; //Chunk size

    for (key in $scope.expanded) {
      $scope.expanded[key] = control;
    }

    if (control) {
      $scope.expandChunk(control);

    $scope.$watch(function(scope) { return scope.count.Loaded }, function (newValue, oldValue) {
      if (!$rootScope.notifExp.Control) return false;
      $log.log('Watch fired: '+newValue);
      if (newValue > 0 && newValue % $scope.expLimit == 0)
        $scope.expandChunk(control);
      }, true);
    }
  };

  $scope.refreshNotifications = function(scrollTo) {
    $log.log('refreshNotifications '+scrollTo);
    $scope.Notifications = angular.copy($rootScope.notif);
    $scope.Notifications = $filter('relevantNotifications')($scope.Notifications);
    $rootScope.user.waitForUpdateNotif = false;
    $rootScope.user.toUpdateNotif = false;
    $scope.tmpDocs = {};
    $scope.expanded = [];
    $scope.count.Notif = $scope.countRelevant();
    $rootScope.notificationsDiscus = {};
    $scope.headerCheck();
    $scope.lastDoc = null;
    $rootScope.show.notifLoading = false;
    if (scrollTo) $scope.scrollToLine(scrollTo);
    if ($rootScope.notifExp.Control) $scope.expandAll($rootScope.notifExp.Control);
  };

  $rootScope.notifExp = {};
  $rootScope.notifExp.Control = localStorage.getItem("expControl") ? angular.fromJson(localStorage.getItem("expControl")) : true;
  $rootScope.notifExp.Time = [];
  $rootScope.notifExp.TimeISO = [];
  $scope.checkedDocs = sessionStorage.getItem("checkedDocs") ? angular.fromJson(sessionStorage.getItem("checkedDocs")) : {};
  if (typeof $rootScope.notif != "undefined") $scope.refreshNotifications();
  $scope.count = {};
  $scope.count.Loaded = 0;
  $scope.count.Notif = 0;

  $scope.expanded = [];
  $scope.profile = new Profile();
  $scope.fullsize = ($state.current.name.indexOf('notifications') >= 0);
  $scope.commonDiscus = Discus;
  if(!$rootScope.notificationsDiscus) {
    $rootScope.notificationsDiscus = {};
  }

  $rootScope.unreadedIstances = $rootScope.unreadedIstances || {};
  $scope.tmpDocs = {};

  $scope.changeExpandState = function() {
    $rootScope.notifExp.Control = !$rootScope.notifExp.Control;
    localStorage.setItem("expControl", $rootScope.notifExp.Control);
    $scope.expandAll($rootScope.notifExp.Control);
  }

  $scope.changeCheckedState = function(unid) {
    $scope.checkedDocs[unid]=!$scope.checkedDocs[unid];
    sessionStorage.setItem("checkedDocs", angular.toJson($scope.checkedDocs));
    $scope.headerCheck();
  }

  $scope.uncheckAll = function() {
    $scope.checkedDocs = {};
    sessionStorage.setItem("checkedDocs", angular.toJson($scope.checkedDocs));
    $scope.showHeader = false;
  }

  $scope.saved = true;

  $scope.markAsRead = function(){
    $scope.spinner = true;
    var docs = [], lastDoc = '';
    var keys = Object.keys($scope.checkedDocs);
    for (var i = keys.length - 1; i >= 0; i--) {
      var key = keys[i];
      if ($scope.checkedDocs[key]) {
        var pointer = false;
        if (!$scope.Notifications[key]) {
          $log.warn('Wrong key! '+key);
          angular.forEach($scope.Notifications, function(valN, keyN){
            if (valN && valN.parentUnid && valN.parentUnid == key) pointer = keyN;
          });
        } else {
          pointer = key;
        }

        if (pointer === false) {
          $log.warn('parentUnid could not be determined');
        } else {
          parentUnid = $scope.Notifications[pointer].parentUnid ? $scope.Notifications[pointer].parentUnid : pointer;

          item = {unid: $scope.Notifications[pointer].unid, parentUnid: parentUnid, time: $rootScope.notifExp.Time[parentUnid], timeISO: $rootScope.notifExp.TimeISO[parentUnid]};

          $rootScope.notifExp.Time[parentUnid] = null;
          $rootScope.notifExp.TimeISO[parentUnid] = null;

          docs.push(item);
          lastDoc = item.parentUnid;
          if ($rootScope.user.settings && $rootScope.user.settings.notifHistory) {
            History.add_full($scope.Notifications[pointer].subject,
                             $scope.Notifications[pointer].parentForm,
                             $scope.Notifications[pointer].parentUnid);
          }
        }
      }
    };
    $rootScope.show.notifLoading = true;
    $rootScope.user.waitForUpdateNotif = true;

    Notificator.markAsRead(docs, function() {
      $scope.spinner = false;
      $scope.checkedDocs = {};
      sessionStorage.setItem("checkedDocs", angular.toJson($scope.checkedDocs));
      $scope.lastDoc = $( "#tr_"+lastDoc ).next().attr( "id" );
      console.log('Found last doc: ' + $scope.lastDoc);
    }, true);
  }

  var svc = DiscusSharedSvc;

  $scope.add_favorites = svc.add_favorites;
  $scope.del_favorites = svc.del_favorites;
  $scope.isFavorite = svc.isFavorite;

  $scope.delayFor = function(time){
    angular.forEach($scope.checkedDocs, function(val, key){
      if (val) {
        var pointer = false;
        if (!$scope.Notifications[key]) {
          $log.warn('Wrong key! '+key);
          angular.forEach($scope.Notifications, function(valN, keyN){
            if (valN && valN.parentUnid && valN.parentUnid == key) pointer = keyN;
          });
        } else {
          pointer = key;
        }

        if (pointer === false) {
          $log.warn('parentUnid could not be determined');
        } else {
          var unid = $scope.Notifications[pointer].parentUnid ? $scope.Notifications[pointer].parentUnid : pointer;
          $rootScope.user.waitForUpdateNotif = true;
          $rootScope.show.notifLoading = true;

          $http({method: 'GET', url: 'api/notif/delay/'+unid+'/'+time, params: {'id':unid, 'time':time} })
          .then(function(response) {
            if (!response.data.success)
              new Popup('Уведомления', 'Ошибка при откладывании уведомления', 'Error');
            else {
              $scope.checkedDocs = {};
              sessionStorage.setItem("checkedDocs", angular.toJson($scope.checkedDocs));
            }
          }, httpErrorHandler);
        }
      }
    });
  }

  $scope.toFavorites = function(){
    angular.forEach($scope.checkedDocs, function(val, key){
      if (val) {
        var pointer = false;
        if (!$scope.Notifications[key]) {
          $log.warn('Wrong key! '+key);
          angular.forEach($scope.Notifications, function(valN, keyN){
            if (valN && valN.parentUnid && valN.parentUnid == key) pointer = keyN;
          });
        } else {
          pointer = key;
        }

        if (pointer === false) {
          $log.warn('parentUnid could not be determined');
        } else {
          !$scope.isFavorite($scope.Notifications[pointer].parentUnid) ? $scope.add_favorites($scope.Notifications[pointer].parentUnid) : $scope.del_favorites($scope.Notifications[pointer].parentUnid);
        }
      }
    })
    $scope.checkedDocs = {};
    sessionStorage.setItem("checkedDocs", angular.toJson($scope.checkedDocs));
    $scope.refreshNotifications();
  }

  $scope.doNotNotify = function(){

    var list = '', listCount = 0;
    $scope.unsubscribeUnids = [],
    $scope.unsubscribeParentUnids = [];
    angular.forEach($scope.checkedDocs, function(val, key){
      if (val) {
        var pointer = false;
        if (!$scope.Notifications[key]) {
          $log.warn('Wrong key! '+key);
          angular.forEach($scope.Notifications, function(valN, keyN){
            if (valN && valN.parentUnid && valN.parentUnid == key) pointer = keyN;
          });
        } else {
          pointer = key;
        }

        if (pointer === false) {
          $log.warn('parentUnid could not be determined');
        } else {
          listCount++;
          list += listCount+'. '+$scope.Notifications[pointer].subject+"<br>";
          $scope.unsubscribeParentUnids.push($scope.Notifications[pointer].parentUnid);
        }

      }
    });
    Popup("Подтверждение", "Вы действительно хотите отписаться от следующих тем?<br><br>"+list, '', true,
    function(){
      for (var i in $scope.unsubscribeParentUnids) {
        Discus.getDocFromServerByUnid($scope.unsubscribeParentUnids[i], function(respDoc) {
          Discus.removeParticipant($rootScope.user.username, 'username', respDoc, null, true);
        });
      }

      $scope.markAsRead();

      delete $scope.unsubscribeParentUnids;

    }, function(){
      return false;
    });
  }
}

function historyCtrl($http, $scope, $stateParams, $rootScope, $state, History) {
  $scope.$state = $state;
  $scope.page = 0;
  $scope.history = [];
  $scope.addHistory = function(){
    if ($scope.busy) return;
    $scope.busy = true;
    $scope.status = "Подождите...";
    History.list( $scope.page, 100,
      function(dataSource) {
        dataSource = dataSource ? dataSource : [];
        $scope.page += dataSource.length;
        $scope.history = $scope.history ? $scope.history : [];
        angular.forEach(dataSource, function (value, key) {
          var date = value.time.date.trim();
          value.date = date.replace(' ', 'T');
          $scope.history.push(value);
        })
        if(dataSource.length !== 0) $scope.busy = false;
        $scope.status = $scope.history.length ? "": "Нет записей.";
      }
    );

  }
}

function adminCtrl($scope, $state) {
  $state.go('body.adminDictionary', { 'type': 'role' });
}

function adminDictionaryCtrl($scope, $state, $stateParams, Dictionary, DictionaryPrototype) {
  $scope.state = $state;

  var loadDictionary = function() {
    $scope.availableTypes = DictionaryPrototype.availableTypes;
    $scope.dict = new Dictionary($stateParams.type);
  };

  if(DictionaryPrototype.ready) {
    loadDictionary();
  } else {
    DictionaryPrototype.whenReady = loadDictionary;
  }
}

function serpCtrl($scope, $sce, AutoComplete, Profile, Dictionary, $stateParams, $state, FullSearch, $rootScope, Popup) {
  $rootScope.$on('$stateChangeStart', function(event, toState) {
    if (toState.name !== "body.serp") $scope.q.query = '';
  });

  $scope.statusListDict = new Dictionary('StatusList', true, false, true);
  $scope.searchCollection={title:'Поиск',name:'Portal,Contacts'};

  $scope.status = 'Укажите параметры поиска и нажмите "Найти".';
  if ($stateParams.jsonQuery){
    $scope.status = "Идет поиск, подождите...";
    $scope.q = angular.fromJson(unescape(atob($stateParams.jsonQuery)));

    FullSearch.srch($scope.q, function(docs, parents) {
      $scope.parents = parents;
      $scope.back_docs = docs;
      $scope.docs = $scope.back_docs.slice(0, 50);
      $scope.docs.length>0?$scope.status="Найдено" : $scope.status="Ничего не найдено.";
      $scope.searchResContacts = 0;

      for (var d in $scope.back_docs) {
        if ($scope.back_docs[d]._source.form == 'Contact')
          $scope.searchResContacts++;
      }
    });
  }else{
    $scope.q = localStorage.getItem("searchParams") ? angular.fromJson(localStorage.getItem("searchParams")) : {sort:'-created'};
  }
  $scope.lastQuery = $scope.q.query;
  $scope.FullSearch = FullSearch;
  $scope.profile = new Profile();
  $scope.author = [];

  if ($scope.q.params && $scope.q.params['Author'] && $scope.q.params['Author'].$in[0]!==$rootScope.user.portalData.Login){
    if ($rootScope.users && $rootScope.users[$scope.q.params['Author'].$in[0]])
      $scope.author[0] = $rootScope.users[$scope.q.params['Author'].$in[0]].name;
    else
      $scope.author[0] = $scope.q.params['Author'].$in[0];
  }

  $scope.searchInOptions = [{label:'Все сообщения', value:'all'}, {label:'Темы и просьбы', value:'main'}];
  $scope.sortByOptions = [{label:'Релевантность', value:'-score'}, {label:'Дата', value:'-created'}];
  $scope.searchIn = $scope.q.params&&$scope.q.params['form']?$scope.searchInOptions[1]:$scope.searchInOptions[0];
  $scope.sortBy = $scope.q.sort==='-score'?$scope.sortByOptions[0]:$scope.sortByOptions[1];

  $scope.infinite_scroll = function() {
    if ($scope.back_docs && !$scope.busy){
      $scope.busy = true;
      $scope.docs = $scope.docs.concat( $scope.back_docs.slice($scope.docs.length, $scope.docs.length + 20) );
      $scope.busy = false;
    }
  }

  $scope.clearSearch = function() {
    $scope.q.query = '';
    $scope.hideDropDown();
  }
  $scope.hideDropDown = function() {
    angular.element("#"+$('#searchField').attr("aria-owns")).scope().matches = [];
    if(!$scope.$$phase) $scope.$apply();
  }

  $(document).on('click',function(event){
    if( $(event.target).closest(".search-block").length ){
      return;
    }
    if(!$scope.$$phase) {
      $rootScope.$apply(function() {
        $rootScope.show.menuCreate = false;
        $rootScope.show.Search = false;
        $rootScope.show.menuUser = false;
        $rootScope.show.inputSearch = false;
      });
    }
    // event.stopPropagation();
  });

  $scope.goSearch = function() {
    if(!$scope.q){
      new Popup('Поиск','Не указан запрос');
    } else {
      toStorage = angular.copy($scope.q);
      toStorage.query = '';
      localStorage.setItem("searchParams", angular.toJson(toStorage));

      $scope.$parent.$parent.showSearch = false;
      if (!$scope.q.sort) $scope.q.sort = '-score';
      if (!$scope.q.collections) $scope.q.collections='Portal,Contacts';
      $state.go('body.serp',{ 'jsonQuery': btoa(escape(angular.toJson($scope.q))) });
    }
  };

  $scope.addAuthor = function(author) {
    FullSearch.addAuthor(author, $scope.q);
  };

  $scope.highlight = function(text, search) {
    if (text) {
      if (!search) {
        return $sce.trustAsHtml(text);
      }
      return $sce.trustAsHtml(text.replace(new RegExp(search, 'gi'), '<strong>$&</strong>'));
    }
  };

  $scope.getAutoCompleteList = function() {
    auto = new AutoComplete();
    return auto.main($scope.q);
  }

  $scope.closeDropdown = function() {
    $('#searchPanel .btn-group').removeClass('open');
  };

  $scope.onSelect = function (item, model, label, event){
    $scope.searchClose();
    $state.go('body.discus', { id: item.unid, type: '' });
  }
}

function voteCtrl($scope, Discus) {
  $scope.discus = Discus;
  Discus.prepareForReadAfter($scope.vote);
}

function favoritesCtrl($scope, Favorites, Popup) {
  $scope.offset = 0;
  $scope.status = "Подождите...";
  $scope.busy = true;
  $scope.favs = [];

  var addToPage = function(docs) {
    docs = docs ? docs : [];
    $scope.offset += docs.length;
    angular.forEach(docs, function (value, key) {
      $scope.favs.push(value);
    });
    if(docs.length !== 0) $scope.busy = false;
    $scope.status = $scope.favs.length ? "": "Нет записей.";
    //console.log($scope.favs);
  };

  var favors = new Favorites();
  favors.myFavorites(addToPage);

  $scope.requestMore = function() {
    if ($scope.busy) return;
    $scope.busy = true;
    favors.myFavorites(addToPage, $scope.offset);
  }
  $scope.del = function(fav) {
    Popup("", 'Удалить "<strong>'+ (fav.subject||fav.subjVoting) + '</strong>" из избранного?', '', true, function() {
      favors.delFavorites(fav.unid, function(res) {
        if (res){
          favors.myFavorites(function(docs) {
            $scope.favs = docs;
          });
        }
      });
    }, null, {"ok":"ДА", "cancel":"<span class='grey'>НЕТ</span>"});
  }
}

function linksCtrl($scope, Dictionary) {
  $scope.links = new Dictionary('Links', true);
}

function filePreviewCtrl ($scope, $rootScope, $state, $stateParams, $http) {
  $http.get('api/fs/data/'+$stateParams.hash).then( function(data){
    $scope.doc = data.data.doc;
      $http.get('api/fs/src/'+data.data.doc.hash).then( function(data){
        $scope.src = data.data;
      },httpErrorHandler);
  },httpErrorHandler);
}
/////////////////////////////////////////////////////////////////////////////////////

function workPlanCtrl ($scope, $rootScope, $state, $stateParams, $http, $log, Dictionary) {
  var pad = function (str) {
    str = '' + str;
    if(str.length<2) str = '0'+str;
    return str;
  }
  var getYearAndMonthOfNow = function(){
    var dateNow = new Date(),
        yearNow = dateNow.getFullYear(),
        monthNow = dateNow.getMonth() + 1;
    return {'year':yearNow, 'month':monthNow};
  }
  var monthShift = function (arg, shift) {
    var dir = shift / Math.abs(shift);
    for(var i=0; i < Math.abs(shift);i++) {
      arg.month += dir;
      if(arg.month < 1) {
        arg.month = 12;
        arg.year -= 1;
      }
      if(arg.month > 12) {
        arg.month = 1;
        arg.year += 1;
      }
    }
    return arg;
  }
  var getDateFromShift = function (shift) {
    var o = monthShift(getYearAndMonthOfNow(), shift);
    return new Date(o.year, o.month-1, 1, 4, 0, 0);
  }
  var formatDate = function (shift) {
    return pad(getDateFromShift(shift).getMonth()+1) + '.' +(getDateFromShift(shift).getFullYear())
  }
  //$scope.departmentDict = new Dictionary('Department', true);
  /** @param arg = {year:yNum, month:mNum} -- where month is unit-based, not zero-based month number
   *  @param actual   actual user's days data
   */
  var getMonthModel = function (arg, actual) {
    var defFull = getDefaultMonthModelFull(arg);
    var len = new Date(arg.year, arg.month,0).getDate();
    var ret = [];
    for(var i =0; i< len; i++) {
      var w = (actual && actual[i] != undefined && actual[i].hasOwnProperty('label'))&&actual[i].label;
      var p = defFull[i];
      if(w!=undefined) p.label = w;
      if(typeof actual[i] != 'undefined' && typeof actual[i].deputyLogin != 'undefined'){
        p.deputyLogin = actual[i].deputyLogin;
      }
      if(typeof actual[i] != 'undefined' && typeof actual[i].deputySal != 'undefined'){
        p.deputySal = actual[i].deputySal;
      }
      ret.push (p);
    }
    return ret;
  };
  var monthName = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
  var getMonthName = function (label) {
    var dt = label.split('.');
    return monthName[dt[0]-1] + ' ' + dt[1];
  };

  $scope.shift = 0;
  $scope.decrementMonth = function() {$scope.rebuild(-1)};
  $scope.incrementMonth = function() {$scope.rebuild(+1)};
  $scope.shift = 0;
  $scope.selectedSection = angular.isArray($scope.user.portalData.section)?$scope.user.portalData.section[0]:$scope.user.portalData.section;
  $scope.model = {dateLabel: formatDate($scope.shift)};
  $scope.sectionListInit = function () {
    $scope.sectionDict = new Dictionary('Section', true);
    var sections = [];
    angular.forEach ($scope.sectionDict.records, function(el,ind) {
      sections.indexOf(el.value)==-1 && sections.push({name:el.value});
    });
    return $scope.sectionList = sections;
  };

  var getDefaultMonthModelFull = function(arg) {
    var len = new Date(arg.year, arg.month,0).getDate();
    var ret = new Array(len);
    for(var w = 0 ; w<len; w++){
      var d = new Date(arg.year, arg.month-1, w+1);
      var wdn = d.getDay();
      wdn = (wdn + 6)%7;
      ret[w] = {};
      ret[w]['cl'] = ['w', 'w', 'w', 'w', 'w', 'e', 'e', ][wdn];
      ret[w]['c'] = ['w', 'w', 'w', 'w', 'w', 'w', 's', ][wdn];
      ret[w]['wd'] = ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс' ][wdn];
      ret[w]['md'] = d.getDate();
    }
    return ret;
  };

  var getDefaultMonthModel = function(arg){
    var len = new Date(arg.year, arg.month,0).getDate();
    var ret = new Array(len);
    for(var w = 0 ; w<len; w++) {
      var wdn = new Date(arg.year, arg.month-1, w+1).getDay();
      ret[w] = ['р', 'р', 'р', 'р', 'р', 'в', 'в', ][wdn];
    }
    return ret;
  };

  $scope.rebuild = function(shift) {
    $scope.busy = true;
    $scope.shift += shift || 0;
    var yearAndMonthObj = monthShift(getYearAndMonthOfNow(), $scope.shift);

    angular.extend($scope, yearAndMonthObj );
    var sections = [];
    $scope.sectionDict = new Dictionary('Section', true);
    $scope.model = { monthModel: getDefaultMonthModelFull(yearAndMonthObj), dateLabel:formatDate($scope.shift)};
    $scope.model.datePresentation = getMonthName($scope.model.dateLabel);
    var url = 'api/wp/section/'+ $scope.selectedSection +'/'+ formatDate($scope.shift);
    $http.get(url).then(function(data) {
      var def = getDefaultMonthModel(yearAndMonthObj);
      angular.forEach(data.data.model.workGroupList,function(e){
        angular.forEach(e.planList, function(pe){
          pe.data = getMonthModel(yearAndMonthObj, pe.data);
        });
      });
      $scope.model.workGroupList = data.data.model.workGroupList;
      $scope.busy = false;
    }, function(data) {
      httpErrorHandler(data);
      $scope.busy = false;
    })
  };
  $scope.rebuild();
}

function tagsPageCtrl ($scope, $rootScope, $state, $location, $stateParams, $http, $log, Dictionary, Tags, BatchHttp, Popup) {

  $scope.search = {};
  $scope.search.Docs = [];
  $scope.search.Query = '';
  $scope.search.tasksOnly = false;
  $scope.selected = {};
  $scope.selected.Tag = '';
  $scope.selected.Letter = '';
  $scope.selected.deleteMode = false;
  $scope.loading = {};
  $scope.loading.statusTags = 0;
  $scope.loading.statusDocs = 0;

  $scope.assignFontSize = function(tags, myOnly) {
    for (var i in tags) {
      var param = myOnly ? tags[i].usedBy[$rootScope.user.username] : tags[i].count;
      if(param>200) {
        tags[i].fontSize = 19;
      } else if (param>150) {
        tags[i].fontSize = 18;
      } else if (param>100) {
        tags[i].fontSize = 17;
      } else if (param>75) {
        tags[i].fontSize = 16;
      } else if (param>50) {
        tags[i].fontSize = 15;
      } else if (param>25) {
        tags[i].fontSize = 14;
      } else tags[i].fontSize = 13;
    }
    return tags;
  };

  $scope.loadTags = function() {
    $scope.loading.statusTags = 1;
    Tags.loadAllTags(
      function(data) {
        $scope.Tags = $scope.assignFontSize(data.tags);
        Tags.foundTags = $scope.Tags;
        $scope.Letters = $scope.loadLetters();
        Tags.foundLetters = $scope.Letters;
        $scope.loading.statusTags = $scope.Tags.length > 0 ? 0 : 2;
      }
    );
  }

  $scope.loadMyTags = function() {
    $scope.loading.statusTags = 1;
    Tags.loadMyTags(
      function(data) {
        $scope.Tags = $scope.assignFontSize(data.tags, true);
        Tags.foundTags = $scope.Tags;
        $scope.Letters = $scope.loadLetters();
        Tags.foundLetters = $scope.Letters;
        $scope.loading.statusTags = $scope.Tags.length > 0 ? 0 : 2;
      }
    );
  }

  $scope.loadPopularTags = function() {
    $scope.loading.statusTags = 1;
    Tags.loadPopularTags(
      function(data) {
        $scope.Tags = $scope.assignFontSize(data.tags);
        Tags.foundTags = $scope.Tags;
        $scope.Letters = $scope.loadLetters();
        Tags.foundLetters = $scope.Letters;
        $scope.loading.statusTags = $scope.Tags.length > 0 ? 0 : 2;
      }
    );
  }

  $scope.loadLetters = function(tags) {
    res = [];
    if (!tags) tags = $scope.Tags;
    for(var tag in tags) {
      tagName = tags[tag].name ? tags[tag].name : tags[tag];
      if ($.inArray(tagName[0], res) == -1)
        res.push(tagName[0]);
    }
    return res.sort();
  }

  $scope.searchFilter = function(text, isLetter) {
    if ($scope.search.Query.length > 0) {
      text = text.toLowerCase();
      if (isLetter) {
        if ($scope.search.Query[0].toLowerCase() === text[0])
          return true;
        else return false;
      } else {
        if (text.indexOf($scope.search.Query.toLowerCase()) >= 0)
          return true;
        else return false;
      }
    } else return true;
  }

  $scope.selectTag = function(params) {
    $state.go('body.tags', {'jsonQuery': btoa(escape(angular.toJson(params)))});
  }

  $scope.findByTag = function(params) {
    $scope.search.Docs = [];
    $scope.loading.statusDocs = 1;

    Tags.findByTag(params,
      function(data) {  //success handler
        $scope.loading.statusDocs = 0;
        $scope.search.Docs = data.docs;
        $scope.search.parents = data.parents;
        $scope.selectTag(params);
      },
      function(data) {  //fail handler
        $scope.loading.statusDocs = 2;
      }
    );
  }

  $scope.deleteTagManually = function(doc, tag) {
    BatchHttp({method: 'POST', url: 'api/tags/deleteTag/'+doc.unid, data: { 'tag': tag } })
    .then(function(response) {
      if(response.data.success) {
        $scope.search.Docs.splice($.inArray(doc, $scope.search.Docs),1);
      } else {
        new Popup('Discus', response.data.message, 'error');
      }
    }, httpErrorHandler);
  }

  $scope.deleteTagCompletely = function(tag, collectionToLoad) {
    if ($scope.selected.Tag == tag) {
      $scope.search.Docs = [];
      $scope.selected.Tag = '';
    }
    BatchHttp({method: 'POST', url: 'api/tags/deleteTagCompletely', data: { 'tag': tag } })
    .then(function(response) {
      $scope.loading.statusDocs = 0;
      if(response.data.success) {
        $scope.Tags = $.grep($scope.Tags, function(e){
          return e.name != tag;
        });
        $scope.loadLetters(collectionToLoad);
      } else {
        console.log(response.data.message);
      }
    }, httpErrorHandler);
  }

  $scope.resumeSession = function() {
    if (Tags.foundTags.length > 0 && Tags.foundLetters.length > 0) {
      $scope.Tags = Tags.foundTags;
      $scope.Letters = Tags.foundLetters;
      return true;
    } else return false;
  }

  var params = null;
  if ($stateParams.jsonQuery) params = angular.fromJson(unescape(atob($stateParams.jsonQuery)));

  if (params) {
    $scope.selected.Tag = params.tag;
    $scope.findByTag(params);
  } else if ($stateParams && $stateParams.selectedTag) {
    $scope.selected.Tag = $stateParams.selectedTag;
    $scope.findByTag({tag: $stateParams.selectedTag, myOnly: true, tagsTab: $scope.tagsTab});
  }

  $scope.tagsTab = params && params.tagsTab ? params.tagsTab : 2;

  switch ($scope.tagsTab) {
    case 1:
      if (!$scope.resumeSession()) $scope.loadPopularTags();
      break;
    case 2:
      if (!$scope.resumeSession()) $scope.loadMyTags();
      break;
    case 3:
      if (!$scope.resumeSession()) $scope.loadTags()
      break;
    default:
      if (!$scope.resumeSession()) $scope.loadMyTags()
      break;
  }

}

function adaptationCtrl($scope, $state, Profile, Adaptation) {
  $scope.adaptation = new Adaptation();
  $scope.adaptation.loadList();
}

function votingListCtrl($scope, Voting) {
  $scope.voting = new Voting(null);
  $scope.votes = [];
  $scope.offset = 0;
  var limit = 100;
  var done = true;
  var notFinished = true;
  $scope.requestMore = function() {
    if(!done) { return; }
    done = false;
    $scope.voting.loadVotingList($scope.offset, limit, function(list) {
      notFinished = (list.length > 0);
      $scope.votes = $scope.votes.concat(list);
      $scope.offset += limit;
      done = notFinished;
    });
  };
  $scope.requestMore();
}

function discountCtrl($scope, AutoComplete, Contact, DiscountFields) {
  $scope.autoComplete = new AutoComplete();
  var dFields = new DiscountFields();
  $scope.ConditionDiscount1 = dFields.ConditionDiscount1;
  $scope.ConditionDiscount2 =  dFields.ConditionDiscount2;
  $scope.ConditionDiscount4 =  dFields.ConditionDiscount4;
  $scope.ConditionDiscount5 = dFields.ConditionDiscount5;

  $scope.articles = {};
  $scope.series = {};
  $scope.sizes = {};

  $scope.addLine = function(i) {
    $scope.discount.SeriesDiscount[i] = '';
    $scope.discount.ArticleDiscount[i] = '';
    $scope.discount.SizeDiscount[i] = '';
  };

  if (!$scope.discount)
    $scope.discount = new Contact({form:'formDiscount'});
  else { //Putting Contact methods into the discount object
    $scope.tmpDiscount = new Contact({form:'formDiscount'});
    for (var attrname in $scope.discount) { $scope.tmpDiscount[attrname] = $scope.discount[attrname]; }
    $scope.discount = $scope.tmpDiscount;
    delete $scope.tmpDiscount;
  }

  if ($scope.contact.unid){
    dFields.getCollections($scope.contact.unid, function(collections) {
      for(var key in collections){
        var value = collections[key];

        if(value[0] && value.articles){
          $scope.series[value[0].code] = value[0].name;
          $scope.articles[value[0].code] = {};
          $scope.sizes[value[0].code] = {};

          for(var keyA in value.articles){
            var valueA = value.articles[keyA];
            $scope.articles[value[0].code][valueA.code] = valueA.name;
            $scope.sizes[value[0].code][valueA.code] = valueA.sizeX+'*'+valueA.sizeY;
          }
        }
      }

      //replace key series
      if ($scope.discount.SeriesDiscountId){
        $scope.discount.SeriesDiscountId.forEach(function(id, index){
          if (!$scope.series[id]){
            for (var key in $scope.series){
              var value = $scope.series[key];
              if (value === $scope.discount.SeriesDiscount[index]) {
                $scope.discount.SeriesDiscountId[index] = key;
              }
            }
          }
        })
      }
    });
  }

  if ($scope.discount.BasicDiscount !== true)
    $scope.discount.BasicDiscount = $scope.discount.BasicDiscount === '1' ? true : false;
  if ($scope.discount.SampleDiscount !== true)
    $scope.discount.SampleDiscount = $scope.discount.SampleDiscount === '1' ? true : false;

  if(!$scope.discount.id)
  {
    $scope.discount.ConditionDiscount_1 = '2';
    $scope.discount.ConditionDiscount_2 = '0';
    $scope.discount.ConditionDiscount_3 = '0';
    $scope.discount.ConditionDiscount_4 = '3';
    $scope.discount.ConditionDiscount_5 = '1';
    $scope.discount.ConditionDiscount_6 = '0';
    $scope.discount.FromDate = null;
    $scope.discount.ConditionDuration = null;
    $scope.discount.BasicDiscount = false;
    $scope.discount.SeriesDiscount = [];
    $scope.discount.ArticleDiscount = [];
    $scope.discount.SizeDiscount = [];
    $scope.addLine(0);
  }
}

function changeLogCtrl($scope) {
  var x2js = new X2JS(),
      changeLog = '';
  changeLog = x2js.xml_str2json($scope.$parent.$parent.contact.ChangeLog);
  if(changeLog.hasOwnProperty('par'))
  {
    changeLog = changeLog.par
  }
  if (changeLog.hasOwnProperty('History'))
  {
    changeLog = changeLog.History.Entry;
  } else
  {
    return '';
  }
  if( !isArray(changeLog) )
  {
    changeLog = [changeLog];
  }
  $scope.changeLog = changeLog;
  $scope.isTypeOf = isTypeOf;
}

function criterionListCtrl($scope, quest) {
  $scope.criterions = quest.criterions;
  $scope.questionaries = quest.questionaries;
  $scope.criterion = true;
  $scope.selectCriterions = [];

  $scope.criterionsAry = function() {
    var ary = [];
    angular.forEach($scope.criterions, function (val, key) {
        ary.push(val);
    });
    return ary;
  }
  $scope.questionariesAry = function() {
    var ary = [];
    angular.forEach($scope.questionaries, function (val, key) {
        ary.push(val);
    });
    return ary;
  }
  $scope.addCriterion = function(unid) {
    if ($scope.selectCriterions.indexOf(unid) === -1) {
      $scope.selectCriterions.push(unid);
    }else{
      $scope.selectCriterions.splice($scope.selectCriterions.indexOf(unid), 1);
    }
  }
}

function criterionCtrl($scope, $stateParams, $state, Question) {
  $scope.criterion = $scope.criterions[$stateParams.unid];
  $scope.changeCrit = false;

  $scope.save = function() {
    Question.setCriterion($scope.criterion, function(doc) {
      $scope.criterions[doc.unid].name = doc.name;
      $scope.criterions[doc.unid].Description = doc.Description;
      $scope.changeCrit = false;
    });
  };

  $scope.delete = function() {
    var sure = confirm('Вы уверены что будете удалять?', 'Банк вопросов');
    if(!sure) return;
    Question.delCriterion($scope.criterion.unid, function() {
      delete $scope.criterions[$scope.criterion.unid];
      $state.go('body.criterions');
    })
  }
}

function criterionCreateCtrl($scope, Question, $state) {
  $scope.criterion = {form:'Criterion'};


  $scope.save = function() {
    Question.setCriterion($scope.criterion, function(doc) {
      $scope.criterions[doc.unid] = doc;
      $scope.criterions[doc.unid].questions = [];
      $state.go('body.criterions.criterion', {unid: doc.unid});
    });
  };
}

function questionaryCtrl($scope, $stateParams, $state, Question) {
  $scope.questionary = $scope.questionaries[$stateParams.unid];
  $scope.crits = [];
  $scope.editQuestionay = false;
  $scope.doc = $scope.questionary;

  $scope.content = function() {
    return $scope.questionary.Content.split(';');
  }

  $scope.quests = function(cont) {
    return cont.split("~#");
  }

  $scope.delete = function() {
    var sure = confirm('Вы уверены что будете удалять?', 'Банк вопросов');
    if(!sure) return;
    Question.delCriterion($scope.questionary.unid, function() {
      delete $scope.questionaries[$scope.questionary.unid];
      $state.go('body.criterions');
    })
  }

  $scope.save = function() {
    $scope.doc['Content'] = '';
    angular.forEach($scope.crits, function(crit, key) {
      strQuests = '';
      angular.forEach(crit.questions, function(quest) {
        if (quest.check){
          strQuests += "~#"+quest.name;
        }
      })
      if (strQuests) {
        $scope.doc['Content'] += ($scope.doc['Content']=='' ? crit.name : ";"+crit.name);
        $scope.doc['Content'] += strQuests;
      }
    })
    Question.setCriterion($scope.doc, function(d) {
      $scope.questionaries[d.unid] = d;
      $scope.editQuestionay = false;
    })
  }

  $scope.addCriterion = function(critUnid) {
    $scope.crits.push(angular.copy($scope.criterions[critUnid]));
  }

  angular.forEach(angular.copy($scope.content($scope.questionary.Content)), function(crit) {  //parse object Questionry
    obj = {questions:[]};
    questNames = [];
    angular.forEach(angular.copy($scope.quests(crit)), function(quest, key) {
      if (key === 0) {
        obj.name = quest;
      }else{
        obj.questions.push({name: quest, Description: '', check: true});
        questNames.push(quest);
      }
    })
    angular.forEach($scope.criterions, function(mainCrit) { //add unchecked questions
      if (mainCrit.name === obj.name){
        angular.forEach(mainCrit.questions, function(qu) {
          if (questNames.indexOf(qu.name) === -1){
            obj.questions.push(angular.copy(qu));
          }
        })
      }
    })
    $scope.crits.push(obj);
  })
}

function questionCreateCtrl($scope, $state, $stateParams, Question) {
  $scope.unid = $stateParams.unid;
  $scope.question = {form:'Question', Criterions:[$scope.unid], Description:''};

  $scope.save = function() {
    Question.setCriterion($scope.question, function(doc) {
      angular.forEach($scope.question.Criterions, function(unid) {
        $scope.criterions[unid].questions.push(doc);
      });
      $state.go('body.criterions.criterion', {unid: $scope.unid} );
    });
  }

  $scope.addCriterion = function(unid) {
    if ($scope.question.Criterions.indexOf(unid) === -1) {
      $scope.question.Criterions.push(unid);
    }else{
      $scope.question.Criterions.splice($scope.question.Criterions.indexOf(unid), 1);
    }
  }
}

function questionCtrl($scope, $state, $stateParams, Question) {
  $scope.critUnid = $stateParams.critunid;
  $scope.changeQuest = false;

  br = false;
  angular.forEach($scope.criterions[$scope.critUnid].questions, function(q) {
    if (!br) {
      if (q.unid === $stateParams.unid) {
        $scope.question = q;
        br = true;
      }
    }
  })
  $scope.criterionsForCheck = angular.copy($scope.question.Criterions);

  $scope.save = function() {
    Question.setCriterion($scope.question, function(doc) {
      angular.forEach($scope.criterionsForCheck, function(cr) {
        if (doc.Criterions.indexOf(cr) == -1) {
          angular.forEach($scope.criterions[cr].questions, function(q, k) {
            if (q.unid === doc.unid){
              $scope.criterions[cr].questions.splice(k, 1);
            }
          })
        }
      })
      angular.forEach(doc.Criterions, function(unid) {
        angular.forEach($scope.criterions[unid].questions, function(q, k) {
          if (q.unid === doc.unid){
            $scope.criterions[unid].questions.splice(k, 1);
          }
        })
        $scope.criterions[unid].questions.push(doc);
      });
      $scope.changeQuest = false;
      $scope.criterionsForCheck = angular.copy(doc.Criterions);
    });
  }

  $scope.delete = function() {
    var sure = confirm('Вы уверены что будете удалять?', 'Банк вопросов');
    if(!sure) return;
    Question.delCriterion($scope.question.unid, function() {

      angular.forEach($scope.question.Criterions, function(unid) {
        angular.forEach($scope.criterions[unid].questions, function(value, key) {
          if (value.unid === $scope.question.unid){
            $scope.criterions[unid].questions.splice(key, 1);
          }
        });
      });
      $state.go('body.criterions.criterion', {unid: $scope.critUnid});

    })
  }

  $scope.addCriterion = function(unid) {
    if ($scope.question.Criterions.indexOf(unid) === -1) {
      $scope.question.Criterions.push(unid);
    }else{
      $scope.question.Criterions.splice($scope.question.Criterions.indexOf(unid), 1);
    }
  }
}

function questionaryCreateCtrl($scope, Question, $state) {
  $scope.crits = [];
  $scope.doc = {form: 'Questionary'};
  angular.forEach($scope.selectCriterions, function(unid) {
    $scope.crits.push(angular.copy($scope.criterions[unid]));
  });

  $scope.save = function() {
    $scope.doc['Content'] = '';
    angular.forEach($scope.crits, function(crit, key) {
      strQuests = '';
      angular.forEach(crit.questions, function(quest) {
        if (quest.check){
          strQuests += "~#"+quest.name;
        }
      })
      if (strQuests) {
        $scope.doc['Content'] += ($scope.doc['Content']=='' ? crit.name : ";"+crit.name);
        $scope.doc['Content'] += strQuests;
      }
    })
    Question.setCriterion($scope.doc, function(d) {
      $scope.questionaries[d.unid] = d;
      $state.go('body.criterions.questionary', {unid: d.unid});
    })
  }
}

statCtrl.$inject = ['$scope', '$rootScope', '$state', 'Stat', 'Discus', 'TretoDateTime'];
function statCtrl($scope, $rootScope, $state, Stat, Discus, TretoDateTime) {
  if ( !~$rootScope.user.portalData.role.indexOf('all') ) {
    $scope.denied = true;
    return;
  }
  var tabs = 'main messages likes dislikes popular-themes my-popular-themes click'.split(' ');
  if ( !~tabs.indexOf($state.params.tab) ) $state.go('body.stat', { tab: 'main'});

  $scope.discus = Discus;
  $scope.stat = Stat;
  
  $scope.sort = {};
  //$scope.usersTab = {}; Not in use as of May 16, 2017. To be deleted soon
  $scope.table = {};

  $scope.getDailyStat = function(callback) {
    $scope.loadingDailyStat = true;

    Stat.getStat(Stat.getQueryByDate(), function(data) {
      
      var dailyStat = data;
      Stat.dailyStat = dailyStat;
      Stat.dailyStatByDate = {};

      // This code hides (removes from displayed) entries for portalrobot
      var listsToScanForPortalRobot = 'users msg'.split(' ');
      var removePortalRobotFromDailyStatList = function(list) {
        if ( !angular.isObject(list) ) return;
        for (var entry in list)
          if ( entry === 'portalrobot' ) delete list[entry];
      };

      for (var i = 0; i < dailyStat.length; i++) {
        Stat.dailyStatByDate[ dailyStat[i].date ] = dailyStat[i];
        dailyStat[i].dateFormatted = TretoDateTime.iso8601.display(dailyStat[i].date, true);

        // This code hides (removes from displayed) entries for portalrobot
        for (var j = 0; j < listsToScanForPortalRobot.length; j++)
          for (var list in dailyStat[i][ listsToScanForPortalRobot[j] ])
            removePortalRobotFromDailyStatList(dailyStat[i][ listsToScanForPortalRobot[j] ][list]);

        for (var login in dailyStat[i].msg.tasks) {
          if (!(login in dailyStat[i].msg.themes))
            dailyStat[i].msg.themes[login] = dailyStat[i].msg.tasks[login];
          else
            dailyStat[i].msg.themes[login] += dailyStat[i].msg.tasks[login];
        }

        if (!$scope.stat.params.state.rocketChatMsgsParsed) {
          var tmpRocketChatMsgs = {};
          for (var hash in dailyStat[i].msg.rocketChatMsgs) {
            tmpRocketChatMsgs[dailyStat[i].msg.rocketChatMsgs[hash].login] = dailyStat[i].msg.rocketChatMsgs[hash].msgCount;
          }
          dailyStat[i].msg.rocketChatMsgs = tmpRocketChatMsgs;
        }
      }

      $scope.stat.params.state.rocketChatMsgsParsed = true;

      dailyStat.sort(function(a, b) {
        return a.date > b.date ? 1 : -1;
      });

      $scope.stat.fusedDailyStat = {};

      function getType(value) {
        if (Array.isArray(value))               return 'array';
        if (typeof value === 'function')        return 'function';
        if (typeof value === 'object' && value) return 'object';
        if (typeof value === 'string')          return 'string';
        if (typeof value === 'number')          return 'number';
        if (typeof value === 'boolean')         return 'boolean';
      };

      function deepFuse(out) {
        out = out || {};

        for (var i = 1; i < arguments.length; i++) {
          var obj = arguments[i];

          if (!obj) continue;

          for (var key in obj)
            if (obj.hasOwnProperty(key)) {
              if (typeof obj[key] === 'object')
                out[key] = deepFuse(out[key], obj[key]);
              else
                if (key in out && getType(obj[key]) === 'number')
                  out[key] += obj[key];
                else
                  out[key] = obj[key];
            }
        }

        return out;
      }

      for (line in dailyStat) {
        $scope.stat.fusedDailyStat = deepFuse($scope.stat.fusedDailyStat, dailyStat[line]);
      }

      var data = $scope.stat.fusedDailyStat;

      var messagesTableObject = {};
      $scope.table.messages = [];

      function collectMessagesTableObject(userLogin, dataObj) {
        if (typeof name == 'undefined') return;
        var entry = {
          user: userLogin,
          name: $rootScope.usersAll && $rootScope.usersAll[userLogin] ? $rootScope.usersAll[userLogin].name || userLogin : userLogin,
          messagesCount: 0,
          tasksCount: 0,
          themesCount: 0,
          tasksEndedCount: 0,
          tasksEndedDifficulty: 0,
          rocketChatMsgs: 0
        };
        entry = $.extend(entry, messagesTableObject[userLogin] ? messagesTableObject[userLogin] : {}, dataObj);
        messagesTableObject[userLogin] = entry;
      };

      for (user in data.msg.messages) collectMessagesTableObject(user, {'messagesCount': data.msg.messages[user]});
      for (user in data.msg.tasks) collectMessagesTableObject(user, {'tasksCount': data.msg.tasks[user]});
      for (user in data.msg.themes) collectMessagesTableObject(user, {'themesCount': data.msg.themes[user]});
      for (user in data.msg.tasksEnded) {
        var tasksEndedCount = data.msg.tasksEnded[user].count;
        var tasksEndedDifficulty = Math.round(data.msg.tasksEnded[user].difficulty / (tasksEndedCount < 2 ? 1 : tasksEndedCount) * 100) / 100;
        collectMessagesTableObject(user, {tasksEndedCount: tasksEndedCount, tasksEndedDifficulty: tasksEndedDifficulty});
      }
      for (user in data.msg.rocketChatMsgs) collectMessagesTableObject(user, {'rocketChatMsgs': data.msg.rocketChatMsgs[user]});

      for (var user in $scope.stat.fusedDailyStat.users.working) {
        if (!messagesTableObject[user])
          messagesTableObject[user] = {
            user: user,
            name: $rootScope.usersAll && $rootScope.usersAll[user] ? $rootScope.usersAll[user].name || user : user,
            messagesCount: 0,
            tasksCount: 0,
            themesCount: 0,
            tasksEndedCount: 0,
            tasksEndedDifficulty: 0,
            rocketChatMsgs: 0
          };
      }

      for (user in messagesTableObject) $scope.table.messages.push(messagesTableObject[user]);

      $scope.table.likes = [];
      $scope.table.dislikes = [];

      for (var key in $scope.stat.fusedDailyStat.like.likes) {
        var t = $scope.stat.fusedDailyStat.like.likes[key];
        if (!t.subject) t.subject = '';
        if (!t.body) t.body = '';
        t.parsedSubject = t.parsedSubject || t.subject || (t.body.length < 81 ? t.body : t.body.slice(0, t.body.lastIndexOf(' ', 80)) + '...') || '[без заголовка]';
        t.name = $rootScope.usersAll && $rootScope.usersAll[t.author] ? $rootScope.usersAll[t.author].name || t.author : t.author;
        $scope.table.likes.push(t);
      }

      for (var key in $scope.stat.fusedDailyStat.like.dislikes) {
        var t = $scope.stat.fusedDailyStat.like.dislikes[key];
        if (!t.subject) t.subject = '';
        if (!t.body) t.body = '';
        t.parsedSubject = t.parsedSubject || t.subject || (t.body.length < 81 ? t.body : t.body.slice(0, t.body.lastIndexOf(' ', 80)) + '...') || '[без заголовка]';
        t.name = $rootScope.usersAll && $rootScope.usersAll[t.author] ? $rootScope.usersAll[t.author].name || t.author : t.author;
        $scope.table.dislikes.push(t);
      }

      $scope.loadingDailyStat = false;
      callback && typeof callback === 'function' && callback();
    });
  };

  $scope.sortBy = function(property) {
    var sortAscendingFirst = 'author name NameInRus subject parsedSubject user workGroup'.split(' ');
    $scope.sort.reverse = ($scope.sort.property === property) ?
                            !$scope.sort.reverse :
                            ( ~sortAscendingFirst.indexOf(property) ? false : true );
    $scope.sort.property = property;
  };
};

messagesStatCtrl.$inject = ['$scope', '$rootScope', 'Stat', '$state', '$timeout', 'Color', 'TretoDateTime'];
function messagesStatCtrl($scope, $rootScope, Stat, $state, $timeout, Color, TretoDateTime) {
  $scope.isUserPM = ~$rootScope.user.portalData.role.indexOf('PM');
  $scope.minDate = new Date(2016,1,1);
  $scope.maxDate = new Date();
  $scope.period = Stat.params.state.period;
  if ($scope.period.start < $scope.minDate) $scope.period.start = new Date($scope.minDate);
  var $statScope = $scope.$parent.$parent.$parent;

  $scope.showGraphPromtAt = null;
  var graphForUsers = [];

  $scope.graph = {
    data: [],
    show: false,
    options: Stat.params.state.messagesStatGraph,
    drawWithData: [
      {name: 'Сообщения на портале', value: 'messages'},
      {name: 'Созданные темы и просьбы', value: 'themes'},
      {name: 'Выполненные задачи', value: 'tasksEnded'},
      {name: 'Сообщения в чате', value: 'rocketChatMsgs'}
    ],
    periodsPerKnots: [
      {name: 'День', value: 1},
      {name: 'Неделя', value: 7},
      {name: 'Месяц', value: 30}
    ],
    modes: [
      {name: 'Сглаженная линия', value: 'curve'},
      {name: 'Ломаная линия', value: 'line'},
      {name: 'Трендовая линия', value: 'trendline'},
      {name: 'График + тренд', value: 'curve_and_trend'}
    ]
  };

  var collectDataForUser = function(user) {
    var data = Stat.dailyStatByDate;
    var selectedField = $scope.graph.options.dataSelected;
    var points = [];
    var point = null;
    var date = new Date(Stat.params.state.period.start);
    var dateStr;
    var periodCount = 0;

    while (date <= Stat.params.state.period.end) {
      periodCount++;
      dateStr = TretoDateTime.iso8601.fromDate(date);
      if (!point) {
        point = {
          y: 0,
          x: points.length,
          xLabel: TretoDateTime.iso8601.display(dateStr, true),
        };
      }
      if (dateStr in data && !angular.isUndefined(data[dateStr].msg[selectedField][user])) {
        point.y += selectedField === 'tasksEnded' ?
                    data[dateStr].msg[selectedField][user].count :
                    data[dateStr].msg[selectedField][user];
      }
      date.setDate( date.getDate() + 1 );
      if (periodCount === $scope.graph.options.periodPerKnot || date > Stat.params.state.period.end) {
        periodCount = 0;
        if ($scope.graph.options.periodPerKnot > 1) point.xLabel += '-'+TretoDateTime.iso8601.display(dateStr, true);
        points.push(point);
        point = null;
      }
    }

    return {
      points: points,
      user: user,
      legend: $rootScope.usersAll && $rootScope.usersAll[user] ? $rootScope.usersAll[user].name : user,
      color: 'rgb('+Color.getPseudoRandomRgb(graphForUsers.length).join()+')'
    };
  };

  var addGraphData = function(user) {
    for (var i = 0; i < $scope.graph.data.length; i++)
      $scope.graph.data[i].color = 'rgb('+Color.getPseudoRandomRgb(i).join()+')';
    $scope.graph.data.push( collectDataForUser(user) );
    graphForUsers.push(user);
  };

  var removeGraphData = function(user) {
    var i = graphForUsers.indexOf(user);
    $scope.graph.data.splice(i, 1);
    graphForUsers.splice(i, 1);
  };

  var redrawGraph = function() {
    $scope.graph.data = [];
    $timeout(function() {
      var tmp = [];
      for (var i = 0; i < graphForUsers.length; i++) {
        tmp.push( collectDataForUser(graphForUsers[i]) );
        tmp[i].color = 'rgb('+Color.getPseudoRandomRgb(i).join()+')';
      }
      $scope.graph.data = tmp;
    }, 0);
  };
  $scope.redrawGraph = redrawGraph;

  $scope.graphPromtAnswer = function(answer) {
    $scope.showGraphPromtAt = null;
    if (true === answer) {
      $scope.graph.show = true;
      redrawGraph();
    }
  };

  $scope.checked = function(user) {
    return ~graphForUsers.indexOf(user);
  };

  $scope.toggleChecked = function(user) {
    $scope.showGraphPromtAt = user;
    if ( $scope.checked(user) ) removeGraphData(user);
    else addGraphData(user);
  };

  $scope.changeDate = function() {
    $statScope.getDailyStat(function() {
      graphForUsers = [];
      $scope.graph.data = [];
    });
  };
  $scope.changeDate();

  $(document).off('.messagesStatCtrl')
    .on('click.messagesStatCtrl touch.messagesStatCtrl', function() {
      $scope.showGraphPromtAt = null;
      $scope.graph.show = false;
      $scope.$apply();
    });

  $scope.$on('$destroy', function() {
    $(document).off('.messagesStatCtrl')
  });
};

popularThemesStatCtrl.$inject = ['$scope', "$rootScope", '$state'];
function popularThemesStatCtrl($scope, $rootScope, $state) {
    $scope.popularThemes = [];
    $scope.popularThemesMy = $state.params.tab === 'my-popular-themes';

    $scope.stat.getPopularThemes(function(data) {
      $scope.popularThemes = data;
    }, $scope.popularThemesMy ? $rootScope.user.username : null, 50);
};

likeStatCtrl.$inject = ['$scope', '$state', 'Stat'];
function likeStatCtrl($scope, $state, Stat) {
  $scope.minDate = new Date(2016,1,1);
  $scope.maxDate = new Date();
  $scope.period = Stat.params.state.period;
  if ($scope.period.start < $scope.minDate) $scope.period.start = new Date($scope.minDate);
  var $statScope = $scope.$parent.$parent.$parent;

  $scope.isLikes = $state.params.tab === 'likes';

  $statScope.getDailyStat(function() {
    $scope.table = $statScope.table;
    $scope.table.likeDataset = $scope.isLikes ? $scope.table.likes : $scope.table.dislikes;
  });
};

/*
 * Not in use as of May 16, 2017
 * To be deleted soon
 */
userStatCtrl.$inject = ['$scope'];
function userStatCtrl($scope) {
  $scope.stat.getMainStat(function(data) {
    var mainStat = data;

    $scope.usersTab.employed = [];
    $scope.usersTab.fired = [];

    for (var date in mainStat.users.employedByDate) {
      for (var i = 0; i < mainStat.users.employedByDate[date].length; i++) {
        $scope.usersTab.employed.push(mainStat.users.employedByDate[date][i]);
      }
    }

    for (var date in mainStat.users.firedByDate) {
      for (var i = 0; i < mainStat.users.firedByDate[date].length; i++) {
        $scope.usersTab.fired.push(mainStat.users.firedByDate[date][i]);
      }
    }

    $scope.sort.employed = {};
    $scope.sort.employed.property = 'DtWork';
    $scope.sort.employed.reverse = true;
    $scope.sort.fired = {};
    $scope.sort.fired.property = 'DtDismiss';
    $scope.sort.fired.reverse = true;
  });
};

function c1logsCtrl($scope, C1Logs, $location) {
  var parmas = $location.search();
  var limit = typeof parmas.limit != 'undefined'?parmas.limit:10;
  var offset = typeof parmas.offset != 'undefined'?parmas.offset:0;
  C1Logs.get(offset, limit, function(docs) {
    $scope.logs = docs;
  })
}

function teCollectionList($scope, $state, TECollection, Popup, TretoDateTime) {
  var supportedParams = {
    type: {
      publication: true,
      delivery: true
    },
    period: {
      day: true,
      week: true,
      month: true,
      quarter: true,
      year: true
    }
  }
  if (!($state.params.type in supportedParams.type)) {
    $state.go("body.teCollectionList", {type: 'publication', period: ($state.params.period in supportedParams.period ? $state.params.period : 'week' )});
  }
  if (!($state.params.period in supportedParams.period))
    $state.go("body.teCollectionList", {type: ($state.params.type in supportedParams.type ? $state.params.type : 'publication'), period: 'week' });
  $scope.list = {};
  $scope.sort = {
    property: 'dateOutput',
    reverse: true
  };
  $scope.since = null;
  $scope.until = null;
  $scope.type = $state.params.type;
  $scope.fastPeriod = $state.params.period;
  $scope.loading = 0;

  $scope.sortBy = function(property) {
    $scope.sort.reverse = ($scope.sort.property === property) ? !$scope.sort.reverse : (property === 'id' || property === 'name' || property === 'fname' ? false : true);
    $scope.sort.property = property;
  };

  $scope.fastSelectPeriod = function(period) {
    var now = new Date();
    var options = {
      'day'   : (new Date(now)).setDate(now.getDate() - 1),
      'week'  : (new Date(now)).setDate(now.getDate() - 7),
      'month' : (new Date(now)).setMonth(now.getMonth() - 1),
      'quarter': (new Date(now)).setMonth(now.getMonth() - 3),
      'year'  : (new Date(now)).setFullYear(now.getFullYear() - 1)
    };
    if (period !== 'custom' && options[period]) {
      $scope.fastPeriod = period;
      $scope.since = TretoDateTime.iso8601.fromDate(new Date(options[period]));
      $scope.until = TretoDateTime.iso8601.fromDate();
    }
  };
  $scope.fastSelectPeriod($scope.fastPeriod);

  $scope.loadCollections = function() {
    $scope.loading = true;
    TECollection.getCollections({type: $scope.type, since: $scope.since, until: $scope.until}, function(collections) {
      if (collections.collections && collections.collections.length)
        for (var i = 0; i < collections.collections.length; i++) {
          collections.collections[i].dateOutput = new Date(collections.collections[i].dateOutput);
          collections.collections[i].dateOutput = TretoDateTime.iso8601.fromDateTime(collections.collections[i].dateOutput);
        }
      $scope.list = collections;
      $scope.loading = false;
    });
  };

  $scope.loadCollections();
};

function calendarCtrl($scope, $rootScope, $compile) {
    events = function(start, end, timezone, callback) {
      res = [];
      angular.forEach($rootScope.usersArr, function(usr) {
        if (moment($rootScope.usersAll[usr.id].Birthday).year(moment().year()).isBetween(start, end))
          res.push({className: 'cal-event cal-birth back-striped',
                    title: usr.name,
                    hint: 'День рождение',
                    color: '#ffd06a',
                    textColor: 'black',
                    start: moment($rootScope.usersAll[usr.id].Birthday).year(moment().year()).format("YYYY-MM-DD") })
      });
      callback(res);
    }
    $scope.eventSources = [{
            events: events,
            color: 'blue',
            textColor: 'white'}];
    $scope.uiConfig = {
      calendar:{
        height: "auto",
        editable: true,
        locale: "ru",
        firstDay: 1,
        header:{
          left: 'today prev,next',
          center: 'title',
          right: 'month,basicWeek,basicDay,agendaWeek,agendaDay',
        },
        eventClick: $scope.alertEventOnClick,
        eventDrop: $scope.alertOnDrop,
        eventResize: $scope.alertOnResize,
        eventRender: function( event, element, view ) {
            element.attr({'title': event.hint}); //add title from hint
            $compile(element)($scope);
        }
      }
    };
}
function emplFormCtrl($rootScope, $scope, Popup, TretoDateTime, $http, Socket, Dictionary) {
  $scope.departmentDict = new Dictionary('Department', true);
  $scope.sectionDict = new Dictionary('Section', true);
  $scope.sectionSiteDict = new Dictionary('SectionSite', true);
  $scope.RegionIDDict = new Dictionary('RegionID', true);
  $scope.doc = $scope.$parent.$parent.doc;
};

function showMessagesByUserCtrl($scope, $rootScope, Stat, $state, TretoDateTime) {
  if ( !~$rootScope.user.portalData.role.indexOf('PM') ) {
    $scope.denied = true;
    return;
  }
  $scope.loading = true;
  $scope.query = angular.fromJson(atob($state.params.query));
  $scope.today = TretoDateTime.iso8601.fromDate()+'T000000';
  $scope.doesnotCount = 0;
  Stat.getMessagesByUser($state.params.query, function(data) {
    $scope.messages = data;
    for (var i = 0; i < $scope.messages.length; i++) {
      if ($scope.messages[i].created > $scope.today) $scope.doesnotCount++;
    }
    $scope.loading = false;
  });
};

function bottomSheetCtrl($scope, $mdBottomSheet, $mdDialog, doc, discus, action) {
  $scope.doc = doc;
  $scope.discus = discus;
  $scope.action = action;
  
  $scope.add = function() {
    $scope.discus.showEditForm('messagebb', null, $scope.doc.unid, $scope.action);
    $mdDialog.hide();
  }
  
  $scope.show = function() {
    $scope.discus.showBottomSheet($scope.action, $scope.doc);
    $mdDialog.hide();
  }
};

clickStatCtrl.$inject = ['$scope', '$rootScope', 'Stat', '$state', 'TretoDateTime', 'Popup', '$timeout', 'Color'];
function clickStatCtrl($scope, $rootScope, Stat, $state, TretoDateTime, Popup, $timeout, Color) {
  $scope.loading = true;
  $scope.minDate = new Date(2017, 3, 17);
  $scope.maxDate = new Date();
  $scope.period = Stat.params.state.period;
  if ($scope.period.start < $scope.minDate) $scope.period.start = new Date($scope.minDate);
    
  $scope.buttonGroups = ['Все'];
  $scope.groupToShow = 'Все';
  
  $scope.showGraphPromtAt = null;
  $scope.graph = {
    show: false,
    data: [],
    options: Stat.params.state.clickStatGraph,
    periodsPerKnots: [
      {name: 'День', value: 1},
      {name: 'Неделя', value: 7},
      {name: 'Месяц', value: 30}
    ],
    modes: [
      {name: 'Сглаженная линия', value: 'curve'},
      {name: 'Ломаная линия', value: 'line'},
      {name: 'Трендовая линия', value: 'trendline'},
      {name: 'График + тренд', value: 'curve_and_trend'}
    ]
  };

  var graphForBtns = [];

  var onLoadLogs = function(data) {
    var date = new Date($scope.period.start);
    var logs = [];
    var btns = {};
    var id = 0;
    graphForBtns = [];
    $scope.graph.data = [];
    $scope.buttonGroups = ['Все'];
    
    while (date <= $scope.period.end) {
      var dateStr = TretoDateTime.iso8601.fromDate(date);
      var log = {date: dateStr, data: {}};
      if (dateStr in data) {
        log.data = data[dateStr];
        for (var btn in data[dateStr]) {
          var btnData = btn.split('::');
          for (var i = 0; i < btnData.length; i++) btnData[i] = $.trim(btnData[i]);
          var btnName = btnData[btnData.length - 1];
          var btnGroup = btnData.length > 1 ? btnData[0] : 'Без группы';
          var btnValue = data[dateStr][btn];
          if (!btns[btnGroup]) {
            btns[btnGroup] = {};
            $scope.buttonGroups.push(btnGroup);
          }
          if (btnName in btns[btnGroup]) btns[btnGroup][btnName].value += btnValue;
          else btns[btnGroup][btnName] = {
            id: id++,
            btnInLog: btn,
            name: btnName,
            group: btnGroup,
            value: btnValue
          };
        }
      }
      logs.push(log);
      date.setDate( date.getDate() + 1 );
    }

    var btnsArr = [];
    for (var btnGroup in btns)
      for (var btnName in btns[btnGroup])
        btnsArr.push(btns[btnGroup][btnName]);

    btnsArr.sort(function(a, b) {
      return a.id - b.id;
    });

    $scope.btns = btnsArr;
    $scope.logs = logs;
    $scope.loading = false;
  };

  var collectGraphData = function(btnId) {
    return {
      legend: $scope.btns[btnId].name,
      color: 'rgb('+Color.getPseudoRandomRgb(graphForBtns.length).join()+')',
      points: (function() {
        var arr = [];
        var point = null;
        for (var i = 0, periodCount = 1; i < $scope.logs.length; i++, ++periodCount) {
          if (!point) {
            point = {
              x: arr.length,
              xLabel: TretoDateTime.iso8601.display($scope.logs[i].date, true),
              y: 0
            };
          }
          if ($scope.btns[btnId].btnInLog in $scope.logs[i].data)
            point.y += $scope.logs[i].data[$scope.btns[btnId].btnInLog];
          if (periodCount === $scope.graph.options.periodPerKnot || i + 1 === $scope.logs.length) {
            periodCount = 0;
            if ($scope.graph.options.periodPerKnot > 1)
              point.xLabel += '-'+TretoDateTime.iso8601.display($scope.logs[i].date, true);
            arr.push(point);
            point = null;
          };
        }
        return arr;
      }())
    };
  };

  var addGraphData = function(btnId) {
    for (var i = 0; i < $scope.graph.data.length; i++)
      $scope.graph.data[i].color = 'rgb('+Color.getPseudoRandomRgb(i).join()+')';

    $scope.graph.data.push( collectGraphData(btnId) );
    graphForBtns.push(btnId);
  };

  var removeGraphData = function(btnId) {
    var i = graphForBtns.indexOf(btnId);
    $scope.graph.data.splice(i, 1);
    graphForBtns.splice(i, 1);
  };

  var redrawGraph = function() {
    $scope.graph.data = [];
    $timeout(function() {
      var tmp = [];
      for (var i = 0; i < graphForBtns.length; i++) {
        tmp.push( collectGraphData(graphForBtns[i]) );
        tmp[i].color = 'rgb('+Color.getPseudoRandomRgb(i).join()+')';
      }
      $scope.graph.data = tmp;
    }, 0);
  };
  $scope.redrawGraph = redrawGraph;

  $scope.checked = function(btnId) {
    return ~graphForBtns.indexOf(btnId);
  };

  $scope.toggleChecked = function(btnId) {
    $scope.showGraphPromtAt = btnId;
    if ( $scope.checked(btnId) ) removeGraphData(btnId);
    else addGraphData(btnId);
  };

  $scope.clearGraphData = function() {
    graphForBtns = [];
    $scope.graph.data = [];
  };

  $scope.graphPromtAnswer = function(answer) {
    $scope.showGraphPromtAt = null;
    if (true === answer) {
      $scope.graph.show = true;
      redrawGraph();
    }
  };

  $scope.changeDate = function() {
    Stat.getClickStat(Stat.getQueryByDate(), onLoadLogs);
  };
  $scope.changeDate();

  $(document).off('.clickStatCtrl')
    .on('click.clickStatCtrl touch.clickStatCtrl', function() {
      $scope.showGraphPromtAt = null;
      $scope.graph.show = false;
      $scope.$apply();
    });

  $scope.$on('$destroy', function() {
    $(document).off('.clickStatCtrl')
  });
};
