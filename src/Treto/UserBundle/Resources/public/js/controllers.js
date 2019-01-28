authApp
    .controller('loginCtrl', loginCtrl)
    .controller('resettingCtrl', resettingCtrl)
    .controller('logoutCtrl', logoutCtrl)
    .controller('profileMenuCtrl', profileMenuCtrl)
    .controller('profileDisplayCtrl', profileDisplayCtrl)
    .controller('profileEditCtrl', profileEditCtrl)
    .filter('status', statusAuthFilter);
  
function loginCtrl($scope, Auth) {
    $scope.auth = new Auth();
}

function resettingCtrl($scope, $http, $state, $timeout, Auth, Popup) {
  $scope.auth = new Auth();
  $scope.user = {};
  $scope.user.username = '';
  $scope.user.fail = false;
  $scope.token = {};
  $scope.token.showInput = false;
  $scope.token.num = '';
  $scope.pwd = {};
  $scope.pwd.showPwd = false;
  $scope.pwd.first = '';
  $scope.pwd.second = '';
  $scope.pwd.success = false;
  $scope.pwd.Message = 'Пароль должен содержать как минимум 8 символов, в том числе: латинские буквы нижнего регистра (az), латинские буквы верхнего регистра (AZ), цифры (34) и спецсимволы (#%).';
  $scope.pwd.Strength = 0;
  
  $scope.sendSMS = function() {
    $http({method: 'POST', url: 'api/user/resetting/request', data: {'login':$scope.user.username} })
        .then(function(response) {
          console.log(response.data);
          if(response.data.success) {
            $scope.token.showInput = true;
          } else {
            $scope.user.fail = response.data.message;
          }
        });
  };
  
  $scope.verifyToken = function() {
    $http({method: 'POST', url: 'api/user/resetting/verify', data: {'login':$scope.user.username, 'token':$scope.token.num} })
        .then(function(response) {
          console.log(response.data);
          if(response.data.success) {
            $scope.token.showInput = false;
            $scope.pwd.showPwd = true;
          } else {
            $scope.user.fail = response.data.message;
          }
        });
  };
  
  $scope.resetPwd = function() {
    $http({method: 'POST', url: 'api/user/resetting/change', data: {'login':$scope.user.username, 'token':$scope.token.num, 'password':$scope.pwd.first} })
        .then(function(response) {
          console.log(response.data);
          if(response.data.success) {
            $scope.pwd.success = true;
            $timeout(function() {
              var user = {};
              user.username = $scope.user.username;
              user.password = $scope.pwd.first;
              $scope.auth.login(user);
            },2000);
          } else {
            $scope.user.fail = response.data.message;
          }
        });
  };
  
  $scope.submit = function() {
    $scope.user.fail = false;
    if ($scope.pwd.showPwd) {
      $scope.resetPwd();
    } else {
      if ($scope.token.showInput) {
        $scope.verifyToken();
      } else {
        $scope.sendSMS();
      }
    }
  };
  
  var strongRegularExp = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!@#\$%\^&\*])(?=.{8,})");

  var mediumRegularExp = new RegExp("^(((?=.*[a-z])(?=.*[A-Z]))|((?=.*[a-z])(?=.*[0-9]))|((?=.*[A-Z])(?=.*[0-9])))(?=.{6,})");

  $scope.validationInputPwdText = function(value) {
    if (strongRegularExp.test(value)) {
      $scope.pwd.Strength = 2;
    } else if (mediumRegularExp.test(value)) {
      $scope.pwd.Strength = 1;
    } else {
      $scope.pwd.Strength = 0;
    }
  };
  $scope.pwdAlert = function() {
    new Popup('Надежность пароля', $scope.pwd.Message, 'notify');
  };
}

function logoutCtrl($scope, $rootScope, Auth, $location) {
    delete $rootScope.notif;
    $scope.auth = new Auth();
    $scope.auth.logout();
}

function profileDisplayCtrl($scope, $rootScope, $stateParams, $cookies, $filter, $http, BatchHttp, Profile, Popup, Dictionary, History, ListDictionaries, Security, ListLikes, SMS, $state) {
  //force hook to display controller
  if ($stateParams.force !== 'force-display') {
    //hook to go directly into edit mode
    if ($rootScope.role('PM')) {
      $state.go('body.profileManagerEdit', { id: $stateParams.id });
    } else if ($stateParams.id == $rootScope.user.username) {
      $state.go('body.profileEdit', { id: $stateParams.id });
    }
  }

  if($stateParams.blogs){
    $scope.activeTab = 2;
  }

  var list = new ListDictionaries();
  var likes = new ListLikes();
  
  $scope.options = {};
  $scope.companyNameDict = new Dictionary('companyName', true, false, true);
  $scope.subscribes = {};
  $scope.security = null;
  
  $scope.subscribe = function() {
    if (!$scope.security) return false;
    if ($scope.security.hasPrivilege('subscribed', $rootScope.user.username, true))
      $scope.security.removePrivilege('subscribed', 'username', $rootScope.user.username);
    else
      $scope.security.addPrivilege('subscribed', 'username', $rootScope.user.username);
    
    $scope.userSubscribed = $scope.security.hasPrivilege('subscribed', $rootScope.user.username, true);
    

    BatchHttp({method: 'POST', url: 'api/user/save-security', data: {'unid': $scope.security.doc.unid,'security': $scope.security.doc.security}})
      .then(function(response) {
        if(!response.data.success) {
          console.log(response.data.message);
          new Popup('Discus', response.data.message, 'error');
        }else console.log(response.data);
      }, httpErrorHandler);

    
  }

  list.bySubtype('DiscusSection', 'subscription', function(subList) {
    $scope.listSubscribtion = subList.dictionary;
  });

  $scope.saveOptions = function() {
    $http({method: 'POST', url: 'api/user/save-settings', data: {'settings':$scope.theUser.settings} })
    .then(function(response) {
      if(response.data.success) {
        $rootScope.user.settings = $scope.theUser.settings;
      } else {
        new Popup('Ошибка при сохранении настроек ',response.data.message,'error');
      }
    });
  }
    
  $scope.countLikes = function(doc) {
    var count = 0;
    for(var i in doc.likes) {
      if (doc.likes[i].isLike == $scope.likeType) count++;
    }
    return count;
  };
  
  $scope.loadLikes = function(type, username, fullname) {
    $scope.loadingLikes = true;
    $scope.likeType = type;
    $scope.listLikes = [];
    likes.getLikes(type, username, fullname, function(result) {
      for (var i in result.likes) {
        var like = {};
        like.body = result.likes[i].subject || $filter('htmlToPlaintext')(result.likes[i].body);
        like.date = $scope.likeType ? result.likes[i].LikeDate : result.likes[i].LikeNotDate;
        like.count = $scope.countLikes(result.likes[i]);
        like.unid = result.likes[i].unid;
        $scope.listLikes.push(like);
      }
      $scope.loadingLikes = false;
    });
  }

  $scope.sortBy = function(predicate) {
    $scope.reverse = ($scope.predicate === predicate) ? !$scope.reverse : false;
    $scope.predicate = predicate;
  };
    
    $scope.treto_pic_host = treto_pic_host;
    $scope.profile = new Profile();
    $scope.profile.findUserById($stateParams.id, function(theUser) {
      if(! theUser) { new Popup('Невозможно найти пользователя',$scope.profile.error,'error'); return; }
      $scope.theUser = theUser;
      $scope.theUser.settings = $scope.theUser.settings ? $scope.theUser.settings : {};
        //$scope.loadLikes(true, theUser.username, theUser.portalData.FullName);
    
      if (!$scope.theUser.portalData.DtDismiss)
        date = $rootScope.convertObjDateToStr();
      else
        date = $scope.theUser.portalData.DtDismiss;
      
      diff = (date - $scope.theUser.portalData.DtWork)/10000;
      exp = Math.round(diff);

      if (diff - exp > 0.0601)
        $scope.theUser.portalData.half_experience = true;

      $scope.theUser.portalData.experience = [];
      for (var i=0; i<exp; i++)
        $scope.theUser.portalData.experience.push(i);
      
      $scope.security = new Security($scope.theUser.portalData);
      $scope.userSubscribed = $scope.security.hasPrivilege('subscribed', $rootScope.user.username, true);

      if($scope.theUser.portalData.Subscribe) {
        $scope.theUser.portalData.Subscribe.forEach(function(v) { $scope.subscribes[v] = true; });
      }
      $scope.departmentDict = new Dictionary('Department', true);
      $scope.sectionDict = new Dictionary('Section', true);
      $scope.sectionSiteDict = new Dictionary('SectionSite', true);
      $scope.RegionIDDict = new Dictionary('RegionID', true);
      $scope.profile.loadMetaForUser(theUser, 0);
      //History.add('Профиль: ' + theUser.portalData.FullNameInRus);
      History.add_full('Профиль: ' + theUser.portalData.FullNameInRus,
                      'profile',
                      theUser.username);
      $scope.geoObjects=[];
      if($scope.theUser.portalData.geoCoord != undefined) {
        for (var i = 0; i < $scope.theUser.portalData.geoCoord.length; i++) {
          coord = $scope.theUser.portalData.geoCoord[i].split(",");
          $scope.geoObjects.push({geometry:{ type: 'Point', coordinates: [coord[1]*1,coord[0]*1]},
          properties: { balloonContent: $scope.theUser.portalData.geoCity[i] }});
        }
      }

      $scope.sms = {
        from: $rootScope.user.portalData.LastName + ' ' + $rootScope.user.portalData.name,
        to: $scope.theUser.portalData.LastName + ' ' + $scope.theUser.portalData.name,
        text: '',
        showPopup: false
      }

      $scope.sendSMS = function() {
        if (!$scope.sms.text || $scope.sms.text == '') {
          new Popup('Error', 'Текс сообщения не может быть пустым');
          return false;
        }
        SMS.send($rootScope.user.username, $scope.theUser.username, $scope.sms.text);
      };
    });
}

function profileEditCtrl($scope, $rootScope, $stateParams, $http, Profile, Popup, $state, Dictionary, ListDictionaries, Auth, SMS, MultiselectHelper) {
    var list = new ListDictionaries();
    $scope.multiselectHelper = new MultiselectHelper();
    $scope.pwdMessage = 'Пароль должен содержать как минимум 8 символов, в том числе: латинские буквы нижнего регистра (az), латинские буквы верхнего регистра (AZ), цифры (34) и спецсимволы (#%).';
    $scope.treto_pic_host = treto_pic_host;
    $scope.profile = new Profile();
    $scope.roles = {};
    $scope.companyNameDict = new Dictionary('companyName', true, false, true);
    $scope.subscribes = {};
    $scope.pwd = {};
    $scope.pwd.show = false;
    $scope.profile.findUserById($stateParams.id, function(theUser) {

    if(! theUser) { new Popup('Невозможно найти пользователя',$scope.profile.error,'error'); }
    $scope.theUser = theUser;
    if(typeof $scope.theUser.portalData.DepSubmiss == "undefined"){
        $scope.theUser.portalData.DepSubmiss = [];
    }

    $scope.theUser.settings = $scope.theUser.settings ? $scope.theUser.settings : {
      notifHistory: false,
      soundNotify: false,
    };

    list.bySubtype('DiscusSection', 'subscription', function(subList) {
        $scope.listSubscribtion = subList.dictionary;
    });

    if($scope.theUser.portalData.role) {
      $scope.theUser.portalData.role.forEach(function(v) { $scope.roles[v] = true; });
    }

    if($scope.theUser.portalData.Subscribe) {
        $scope.theUser.portalData.Subscribe.forEach(function(v) { $scope.subscribes[v] = true; });
    }
    if(typeof $scope.theUser.portalData.WorkGroup == 'string'){
        $scope.theUser.portalData.WorkGroup = [$scope.theUser.portalData.WorkGroup];
        if(typeof $scope.theUser.portalData.WorkGroupEng == 'string'){
            $scope.theUser.portalData.WorkGroupEng = [$scope.theUser.portalData.WorkGroupEng];
        }
    }
        
    $scope.positionsDict = new Dictionary('Positions', true);
	$scope.departmentDict = new Dictionary('Department', true);
	$scope.sectionDict = new Dictionary('Section', true);
	$scope.sectionSiteDict = new Dictionary('SectionSite', true);

    $scope.dropdownCheckboxChange = function(val, action, valueName){
        if(valueName == 'WorkGroup'){
            var workGroup = val;
            val = val.value;
        }
        if(!action){
            if($scope.theUser.portalData[valueName].length > 1 || valueName == 'DepSubmiss'){
                $scope.theUser.portalData[valueName].splice($scope.theUser.portalData[valueName].indexOf(val), 1);
                if(valueName == 'WorkGroup'){
                    $scope.theUser.portalData.WorkGroupEng.splice($scope.theUser.portalData.WorkGroupEng.indexOf(workGroup.key), 1);
                }
            }
            else {
                action = true;
            }
        }
        else {
            if(valueName == 'WorkGroup'){
                $scope.theUser.portalData.WorkGroupEng.push(workGroup.key);
            }
            $scope.theUser.portalData[valueName].push(val);
        }
        return action;
    };

    $scope.save = function(u) {
      u.portalData.FullNameInRus = u.portalData.LastName + ' ' + u.portalData.name + ' ' + u.portalData.MiddleName;
      if ($scope.$parent.theUser !== undefined) {
        var tmp1 = $scope.$parent.theUser.portalData.experience;
        var tmp2 = $scope.$parent.theUser.portalData.half_experience
        $scope.$parent.theUser.portalData = u.portalData;
        $scope.$parent.theUser.portalData.experience = tmp1;
        $scope.$parent.theUser.portalData.half_experience = tmp2;
      }

            if($rootScope.role('PM')) {
              u.portalData.role = [];
              for(var k in $scope.roles) {
                if($scope.roles[k]) {
                  u.portalData.role.push(k);
                }
              }
            }
            u.portalData.Subscribe = [];
            for(var k in $scope.subscribes) {
                if($scope.subscribes[k]) {
                    u.portalData.Subscribe.push(k);
                }
            }

            console.log(u);

            $scope.profile.saveUser(u, function(success, messages) {
                if(success) {
                  $http.get("api/contact/send-profile/"+theUser.username);
                  if(u.id == $rootScope.user.id) {
                    $scope.auth = new Auth();
                    $scope.auth.reloadUserPortalData(u);
                  }
                  $state.go('body.profileDisplay', { id: u.id });
                }
                $scope.success = success;
                $scope.messages = messages;

            });
        }

      $scope.sms = {
        from: $rootScope.user.portalData.LastName + ' ' + $rootScope.user.portalData.name,
        to: $scope.theUser.portalData.LastName + ' ' + $scope.theUser.portalData.name,
        text: '',
        showPopup: false
      }

      $scope.sendSMS = function() {
        if (!$scope.sms.text || $scope.sms.text == '') {
          new Popup('Error', 'Текс сообщения не может быть пустым');
          return false;
        }
        if (!$scope.theUser.portalData.ContactWithMobileFhone ||
            !$scope.theUser.portalData.ContactWithMobileFhone[0]) {
              new Popup('Error', 'Пользователь не указал контактный номер телефона');
              return false;
          }
        SMS.send($rootScope.user.username, $scope.theUser.username, $scope.sms.text);
      };
    });

      var strongRegularExp = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!@#\$%\^&\*])(?=.{8,})");

      var mediumRegularExp = new RegExp("^(((?=.*[a-z])(?=.*[A-Z]))|((?=.*[a-z])(?=.*[0-9]))|((?=.*[A-Z])(?=.*[0-9])))(?=.{6,})");

      $scope.validationInputPwdText = function(value) {
        if (strongRegularExp.test(value)) {
          $scope.pwdStrength = 2;
        } else if (mediumRegularExp.test(value)) {
          $scope.pwdStrength = 1;
        } else {
          $scope.pwdStrength = 0;
        }
      };
      
      $scope.pwdAlert = function() {
        new Popup('Надежность пароля', $scope.pwdMessage, 'notify');
      };
}

function profileMenuCtrl($scope) {
    if(! $scope.menu) {
        $scope.menu = { show: false };        
    }
}
function statusAuthFilter(Statuses) {
  this.$get = angular.noop;
}
