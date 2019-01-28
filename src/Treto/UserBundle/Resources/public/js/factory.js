authApp
  .factory('Auth', auth)
  .factory('Profile', profile)
  .factory('ProfileList', profileList)
  .factory('ListLikes', listLikes)
  .factory('Access', Access);

/** Operates with $rootScope */
function auth($http, $location, $log, $rootScope, $state, $cookies, $cookieStore, Access, Socket, Discus, $timeout, $window) {
    var Auth = function() {
        var self = this;
        self.error = '';
        self.metaStates = { login: 1, logout: 1, error: 1 };

        /* Authorization check */
        self.auth = function() {
//             $log.log(1);
            $rootScope.virtual = {};
//             var key = '';
//             if ($location.search() && $location.search()['key']) key = '/'+$location.search()['key'];

//             console.log(key);
//             console.log('api/user/login'+key);
            return $http({
                method: 'POST',
                url: 'api/user/login',//+key,
                params: {}
            }).then(function(response) {
                $log.log(2);
                $rootScope.user = response.data.user;
                $rootScope.environment = response.data.environment;
                $rootScope.usersAll = response.data.users;
                $rootScope.shareUsers = response.data.shareUsers;
                $rootScope.shareForms = ['formProcess', 'formTask']; //@todo formVoting
                $rootScope.usersAllArrLastThreeYears = _.chain(response.data.users)
                                                        .map(function(v, i){ v.id = i; return v; })
                                                        .filter(function(v){
                                                         // return !v.DtDismiss || moment(v.DtDismiss).isAfter(moment().subtract(3, 'years'))
                                                            return true;
                                                        })
                                                        .value();


                $rootScope.getShareName = function(domain, username){
                    for(var portalDomain in $rootScope.shareUsers){
                        if(portalDomain == domain){
                            for(var sectionName in $rootScope.shareUsers[portalDomain].data){
                                var section = $rootScope.shareUsers[portalDomain].data[sectionName].data;
                                for(var empl in section){
                                    if(section[empl].username == username){
                                        return section[empl].LastName+ ' '+section[empl].name+' ('+$rootScope.shareUsers[portalDomain].name+')';
                                    }
                                }
                            }
                        }
                    }

                    return username?username+' ('+domain+')':'';
                };

                if(! response.data.success) {
                    self.error = 'not authorized';
                    $cookieStore.remove('PHPSESSID');
                    if(!$cookies.get('currentStateName')){
                        $cookies.put('redirectUrl', window.location.hash);
                    }
                    
                    if ($location.search() && $location.search()['key']) {
                      console.log($location.search()['key']);
                      
                    }

                    $location.path('/login');
                } else {
                    self.error = '';
                }

                $rootScope.$on('$stateChangeStart', function(event, toState, toParams, fromState, fromParams){
                  if(!self.metaStates[toState.name]) {
                    if((!$rootScope.user || !$rootScope.user.id) && toState.url != '/resetting'){
                      $cookies.put('currentState','');
                      $cookies.put('currentStateParams','');
                      $cookies.put('currentStateName','');
                      event.preventDefault();
                      $state.go('login');
                    } else {
                      $cookies.put('currentState', toState.url);
                      $cookies.put('currentStateParams', angular.toJson(toParams));
                      $cookies.put('currentStateName', toState.name != 'resetting' ? toState.name : 'body.index');
                    }
                  }

                  if (toState.name !== 'body.discus' && fromState.name == 'body.discus') {
                    console.log('leave Discus');
                    Socket.get(function(socket) {
                      socket.emit('leaveDiscus');
                    });
                  }

                  $rootScope.show = {mobileMenu: false, menuUser: false, menuCreate: false, Search: false};
                });
                $rootScope.$on('$stateChangeSuccess', function(event, toState, toParams){
                    window.scrollTo(0, 0);

                    if(!self.metaStates[toState.name] && ($cookies.get('currentState') != toState.url)) {
                        $cookies.put('currentState', toState.url);
                        $cookies.put('currentStateName', toState.name);
                        $cookies.put('currentStateParams', angular.toJson(toParams));
                    }
                    if ($rootScope.needReload){
                      $rootScope.needReload = false;
                      console.log('Reload Page!!!');
                      location.reload();
                    }
                });
                $log.log(4);
                return response.data.user;
            }, httpErrorHandler);
        };

        /* Login process */
        self.login = function(user) {
          $log.log(5);
          if(self.showCaptcha) console.log(grecaptcha.getResponse(widgetId1));
            $http({
                method: 'POST',
                url: 'api/user/login_check',
                data: $.param({
                    _username: user.username,
                    _password: user.password,
                    _remember_me: 'on',
                    _g_response: (self.showCaptcha ? grecaptcha.getResponse(widgetId1) : '')
                }),
                headers : {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}
            }).then(function(response) {
                $log.log(6);
                if(response.data.success) {
                    self.setEmailAccess(response.data.user.id, user.password);
                    self.loginToSite(user.username);
                    $rootScope.user = response.data.user;
                    if($cookies.get('currentStateName')) {
                      $state.go($cookies.get('currentStateName'), angular.fromJson($cookies.get('currentStateParams')));
                    } else if($cookies.get('redirectUrl')) {
                        var redirectUrl = $cookies.get('redirectUrl');
                        $timeout(function () {
                            $window.location.href = redirectUrl;
                            $cookies.remove('redirectUrl');
                        }, 500);
                    } else {
                      $location.path('/').replace();
                    }
                } else {
                    if(response.data.showCaptcha == true) self.showCaptcha = true;
                            self.error = response.data.message;
                }
            }, httpErrorHandler);
        };

        self.loginToSite = function(username){
            $http({
                method: 'POST',
                url: 'getSiteAuthConfig',
                data: {},
                headers : {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}
            }).then(function(response) {
                if(response.data && response.data.length){
                    for(var i in response.data){
                        $http.jsonp(response.data[i].url+'?hash='+response.data[i].hash+'&username='+username);
                    }
                }
            }, httpErrorHandler);
        };

        self.setEmailAccess = function(id, password){
            $http({
                method: 'POST',
                url: 'mailAccessSet',
                data: $.param({
                    id: id,
                    password: password
                }),
                headers : {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}
            }).then(function(response) {}, httpErrorHandler);
        };

        /* Logout process */
        self.logout = function() {
            $rootScope.user = null;
            $http({method: 'GET', url: 'api/user/logout', params: {}
            }).then(function(response) {
                if(response.data.success) {
                  $location.path('/login').replace();
                  $cookieStore.remove('PHPSESSID');
                  Socket.logout();
                }
            }, httpErrorHandler);
        };

        /* returns object with really changed fields */
        self.reloadUserPortalData = function(userData) {
          var changedFields = {};
          if(userData.portalData) {
            for(var field in userData.portalData) {
              if($rootScope.user.portalData[field] !== userData.portalData[field]) {
                $rootScope.user.portalData[field] = userData.portalData[field];
                changedFields[field] = true;
              }
            }
          }
          return changedFields;
        };

        self.getSessionId = function() { return $cookies.get('PHPSESSID'); };
    }
    return (Auth);
}

function profile($http, $q, BatchHttp, $rootScope, DictionaryPrototype, Dictionary, translit) {
    var roleDict = null;

    var Profile = function() {
        var self = this;

        self.error = '';

        self._initRoleDict = function() {
          if(!roleDict) {
            roleDict = new Dictionary('role', true);
          }
        };

        self.findUserById = function(
            id,
            handler // function(user), if error user == null, see .error
        ) {
            $http({
                method: 'GET',
                url: 'api/user/get/'+encodeURIComponent(id),
            }).then(function(response) {
                if(! response.data.success) {
                    self.error = response.data.message;
                    handler(null);
                } else {
                    handler(response.data.user);
                }
            }, httpErrorHandler);
        };

        self.loadMetaForUser = function(theUser, offset) {
          if(!theUser.portalData.FullNameRaw) { return; }
          if(!offset) offset = 0;
          if(!theUser._meta) { theUser._meta = {}; }
          if(theUser._meta.busy) { return; }
          theUser._meta.busy = true;
          theUser._meta.blogs = theUser._meta.blogs ? theUser._meta.blogs : [];

          var limit = 50;

          BatchHttp({
                method: 'GET',
                url: 'api/discussion/list',
                params: { type: 'Blog', author: theUser.portalData.FullNameRaw, limit: limit, offset: offset },
            }).then(function(response) {
                theUser._meta.busy = false;
                theUser._meta.loaded = offset + limit;
                for (var i in response.data) theUser._meta.blogs.push(response.data[i]);
            }, httpErrorHandler);
        }

        self.convertName = function (user) {
            user.portalData.FullNameInRus = [user.portalData.LastName, user.portalData.name, user.portalData.MiddleName].join(' ').trim();
            user.portalData.subject = user.portalData.FullNameInRus;
            user.portalData.FullNameRaw = translit(user.portalData.FullNameInRus);
            user.portalData.FullName = 'CN='+user.portalData.FullNameRaw+'/O=skvirel';

            return user;
        };

        self.saveUser = function(
            aUser,
            handler // function(success, errors)
        ) {
          if(!aUser) { aUser = $rootScope.user; }

            $http({
                method: 'POST',
                url: 'api/user/set',
                data: { 'user': self.convertName(aUser) }
            }).then(function(response) {
                if(! response.data.success) {
                    self.error = response.data.message;
                }
                handler && handler(response.data.success, response.data.messages);
            }, httpErrorHandler);
        };

        self.getRoleDict = function() {
          return roleDict;
        };

        self.portalDataAutocomplete = function (namePart, searchInWorkGroup, needSorting) {
          if (namePart.length > 2) {
            return $http.get('api/portal/users', { params: { query:{ name: namePart, searchInWorkGroup: searchInWorkGroup === true ? '1' : '0' } }}).then(function(response){
              if (needSorting !== true) return response.data.result;
              var res = response.data.result;
              res.sort(function(a, b) {
                var sortRes = $rootScope.users ?
                                (b.login in $rootScope.users) - (a.login in $rootScope.users) : 0;
                if (sortRes === 0) {
                    sortRes = String.prototype.localeCompare
                            && typeof String.prototype.localeCompare === 'function' ?
                                a.name.localeCompare(b.name) :
                                (a.name > b.name ? 1 : (a.name < b.name ? -1 : 0));
                }
                return sortRes;
              });
              return res;
            });
          } else {
            return [];
          }
        };

        self.usersAllAutocomplete = function(namePart, searchInWorkGroup, needSorting) {
            var users = $rootScope.usersAllArrLastThreeYears;
            if (namePart.length < 3 || !users || !users.length) return [];
            var res = [];
            var nameRegexp = new RegExp('.*'+namePart+'.*', 'i');
            for (var i = 0; i < users.length; i++)
                if ( users[i].name.match(nameRegexp)
                     || ( searchInWorkGroup === true
                          && _.isArray(users[i].WorkGroup)
                          && users[i].WorkGroup.join().match(nameRegexp) ))
                    res.push(users[i]);
            if (needSorting === true)
                res.sort(function(a, b) {
                    var sortRes = $rootScope.users ?
                                    (b.id in $rootScope.users) - (a.id in $rootScope.users) : 0;
                    if (sortRes === 0) {
                        sortRes = String.prototype.localeCompare
                                && typeof String.prototype.localeCompare === 'function' ?
                                    a.name.localeCompare(b.name) :
                                    (a.name > b.name ? 1 : (a.name < b.name ? -1 : 0));
                    }
                    return sortRes;
                });
            return res;
        };

        self.loadTranslations = function(sourceNames) {
          // deprecated
        };

        self.translateName = function(name, share) {
            if(name instanceof Array) {
                name = name[0];
            }
            if(typeof share != 'undefined'){
                if(share[0] && share[0].domain && share[0].login){
                    var domain = share[0].domain;
                    var login = share[0].login;
                }
                else {
                    var domain = share;
                    var login = name;
                }

                return $rootScope.getShareName(domain, login);
            }
            else if ($rootScope.usersAll && $rootScope.usersAll[name]) {
                return $rootScope.usersAll[name].name;
            }

            return name;
        };

        // constructor

        if(DictionaryPrototype.whenReady) {
          self._initRoleDict();
        } else {
          DictionaryPrototype.whenReady = self._initRoleDict;
        }

    };
    return (Profile);
}

function profileList($http) {
  var self = this;

  self.users = [];
  self.error = '';

  self.findUsers = function(offset, limit, orderby, inversed, handler) {
    self.error = '';
    $http({
        method: 'GET',
        url: 'api/user/list/'+offset+ (limit ? '/'+limit+ (orderby ? '/'+orderby+ (inversed ? '/'+inversed : '') : '') : ''),
    }).then(function(response) {
        if(! response.data.success) {
            self.error = response.data.message;
        } else {
            self.users = response.data.users;
            handler && handler();
        }
    }, httpErrorHandler);
  };

  self.findUsersBySection = function(section, name, handler) {
    self.error = '';
    section = section?section:0;
    name = name?name:0;

    $http({
        method: 'GET',
        url: 'api/user/listbysection/'+section+'/'+name
    }).then(function(response) {
        if(! response.data.success) {
            self.error = response.data.message;
        } else {
            self.users = response.data.users;
            if(handler){
                var callbackResult = handler(self.users)
                if(callbackResult){
                    return callbackResult;
                }
            }
        }
    }, httpErrorHandler);
  }

  self.findUsersDismissed = function(name, handler) {
    name = name?name:0;
    self.error = '';
    $http({
        method: 'GET',
        url: 'api/user/listdismissed/'+name,
    }).then(function(response) {
        if(! response.data.success) {
            self.error = response.data.message;
        } else {
            self.users = response.data.users;
            handler && handler(self.users);
        }
    }, httpErrorHandler);
  }

  return self;
}

function listLikes(BatchHttp) {
  var list = function() {
    var self = this;
    self.getLikes = function(type, username, fullname, callback) {
      if (username) {
        BatchHttp({
          method: 'POST',
          url: 'api/user/likes/list',
          data: {type: type, username: username, fullname: fullname}
        }).then(function (response) {
          callback(response.data)
        });
      }
    }
  };
  return list;
}

/** Access Control, for example:
        ng-show="can(theUser).read(theDocument)"
        ng-if="can().write(theDocument)"
        ng-if="can(user, 'deleted').object(theDocument)"
        ng-if="role(theUser, 'PM')"
        ng-if="role('SM')"
  if subject has no read privs it checks for write privileges. In other words, subject cannot write without reading.
*/
function Access($rootScope) {
    var self = this;

    var Query = function(_user, _privilege, _obj) {
        var self = this;

        self.user = _user ? _user : $rootScope.user;
        self.privilege = _privilege ? _privilege : null;
        self.obj = _obj ? _obj : null;

        self.read = function(theObject) {
            self.privilege = 'read';
            return self.object(theObject);
        };
        self.write = function(theObject) {
            self.privilege = 'write';
            return self.object(theObject);
        };
        self.vote = function(theObject) {
            self.privilege = 'vote';
            return self.object(theObject);
        };

        self.object = function(theObject) {
            self.obj = theObject;
            var result = self._check();
            if(!result && self.privilege == 'read') {
              return self.write(theObject);
            }
            return result;
        };

        /** example:
            security: { privileges: {
                read: [ { role: "all" }, { role: "manager" }, ],
                write: [ { role: "admin" }, { ldap: "CN=test me/O=domain" }, { fullname: "test me" } ]
            } }
        */
        self._check = function() {
            if(! self.privilege || ! self.obj) { return false; }
            if(! self.user || self.user.isEmpty() || ! self.user.portalData) {
                throw new Error('Access: '+self.privilege+': the user is corrupted!');
            }
            if(!self.obj.security || !self.obj.security.privileges || !self.obj.security.privileges[self.privilege]) {
                return true;
            }
            var uRoles = ((self.user.portalData.role instanceof Array)
                          ? self.user.portalData.role : [self.user.portalData.role]);
            if(uRoles.indexOf('all') < 0) { uRoles.push('all'); }
            var uLdap = self.user.portalData.FullName;
            var uFullname = uLdap.substr(3, uLdap.indexOf('/',3)-3);
            var op = self.obj.security.privileges;
            var result = false;
            if(uRoles.indexOf('PM') > -1 && self.privilege !== 'vote') { return true; }
            op[self.privilege].every(function(privilege) {
                if(    (privilege.role && (uRoles.indexOf(privilege.role) > -1))
                    || (privilege.ldap && privilege.ldap == uLdap)
                    || (privilege.fullname && privilege.fullname == uFullname)
                    || (privilege.username && privilege.username == self.user.username) )
                {
                    result = true;
                    return false;
                }
                return true;
            });
            return result;
        };
    };

    self.can = function(theUser, thePrivilege) {
      return new Query(theUser, thePrivilege);
    };
    self.role = function(theUserOrRole, theRole) {
      var u = $rootScope.user;
      var r = '';
      if(typeof theUserOrRole != 'object') {
        r = theUserOrRole;
      } else {
        if(! theRole) { throw new Error('Access: role: role not specified'); }
        r = theRole;
        u = theUserOrRole;
      }
      if(! u.portalData) {
        throw new Error('Access: role: '+r+': the user "'+u.username+'" has no portalData');
      }
      var uRoles = ((u.portalData.role instanceof Array) ? u.portalData.role : [u.portalData.role]);
      if(r instanceof Array) {
        var result = false;
        r.forEach(function(rv) {
          if(uRoles.indexOf(rv) > -1) {
            result = true;
          }
          return false;
        });
        return result;
      }
      return (uRoles.indexOf(r) > -1);
    };
    self.mynameis = function(n) {
      var u = $rootScope.user;
      var uLdap = u.portalData.FullName;
      var uFullname = uLdap.substr(3, uLdap.indexOf('/',3)-3);
      return n == u.username || n == uLdap || n == uFullname || n == u.id;
    }
    self.isEscalManager = function(task) {
      if (task.EscalationManagers){
        task.EscalationManagers.forEach(function(manager) {
          if ($rootScope.user.username === manager.login && manager.type == 'notify'){
            return true;
          }
        })
      }
      return false;
    }

    $rootScope.can = self.can;
    $rootScope.role = self.role;
    $rootScope.mynameis = self.mynameis;
    $rootScope.isEscalManager = self.isEscalManager;

    return (self);
}

